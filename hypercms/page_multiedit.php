<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// template engine
require ("function/hypercms_tplengine.inc.php");
// file formats extensions
require ("include/format_ext.inc.php");

// input parameters
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$db_connect = getrequest ("db_connect");
$multiobject = getrequest ("multiobject");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// load publication configuration
if (valid_publicationname ($site) && empty ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// token
$token = createtoken ($user);

// function to collect tag data
function gettagdata ($tag_array)
{
  global $mgmt_config, $site;
  
  $return = array();

  foreach ($tag_array as $tagDefinition)
  {
    // get tag id
    $id = getattribute ($tagDefinition, "id");
    
    // get visibility on edit
    $onedit = getattribute (strtolower ($tagDefinition), "onedit");
    
    // We only use the first occurence of each element
    if (array_key_exists($id, $return) && isset($return[$id]->onedit))
    {
      // combine group access when needed
      $groups = getattribute ($tagDefinition, "groups");

      $return[$id]->groupaccess = trim ($return[$id]->groupaccess."|".$groups, "|"); 
      continue;
    }
    // We completely ignore values which are onEdit hidden
    elseif ($onedit == "hidden") continue;
    
    $return[$id] = new stdClass();
    $return[$id]->onedit = $onedit;
    
    // get tag name
    $hypertagname = gethypertagname ($tagDefinition);
    $return[$id]->hypertagname = $hypertagname;
    
    $return[$id]->type = substr ($hypertagname, strlen($hypertagname)-1);
        
    $label = getattribute ($tagDefinition, "label");
    
    if (substr ($return[$id]->hypertagname, 0, strlen ("arttext")) == "arttext")
    {
      $return[$id]->article = true;
      // get article id
      $artid = getartid ($id);

      // get element id
      $elementid = getelementid ($id);           

      // define label
      if ($label == "") $labelname = $artid." - ".$elementid;
      else $labelname = $artid." - ".$label;
      
      $return[$id]->labelname = $labelname;
    }
    else
    {
      $return[$id]->article = false;
      
      // define label
      if ($label == "") $labelname = $id;
      else $labelname = $label;
      
      $return[$id]->labelname = $labelname;
    }

    // get DPI
    $return[$id]->dpi = getattribute ($tagDefinition, "dpi");

    // get constraint
    $constraint = getattribute ($tagDefinition, "constraint");
    
    if ($constraint != "") $constraint = "'".$hypertagname."[".$id."]','".$labelname."','".$constraint."'";

    $return[$id]->constraint = $constraint;

    // extract text value of checkbox
    $return[$id]->value = getattribute ($tagDefinition, "value");  

    // get value of tag
    $return[$id]->defaultvalue = getattribute ($tagDefinition, "default");

    // get format (if date)
    $return[$id]->format = getattribute ($tagDefinition, "format");  

    // get toolbar
    if ($mgmt_config[$site]['dam'] == false) $toolbar = getattribute ($tagDefinition, "toolbar");
    else $toolbar = "DAM";

    if ($toolbar == false) $toolbar = "DefaultForm";
    
    $return[$id]->toolbar = $toolbar;
    
    // get height in pixel of text field
    $sizeheight = getattribute ($tagDefinition, "height");

    if ($sizeheight == false || $sizeheight <= 0) $sizeheight = "300";

    $return[$id]->height = $sizeheight;
    
    // get width in pixel of text field
    $sizewidth = getattribute ($tagDefinition, "width");

    if ($sizewidth == false || $sizewidth <= 0) $sizewidth = "600";

    $return[$id]->width = $sizewidth;
    
    // get language attribute
    $return[$id]->language_info = getattribute ($tagDefinition, "language");

    // get group access
    $return[$id]->groupaccess = getattribute ($tagDefinition, "groups"); 
    if ($return[$id]->groupaccess == "") $return[$id]->groupaccess = "";
    
    // get list entries
    $return[$id]->list = getattribute ($tagDefinition, "list");
    
    // get file entry (if keywords)
    $return[$id]->file = getattribute ($tagDefinition, "file");
    
    // get onlylist setting for mandatory keywords list (if keywords)
    $return[$id]->onlylist = getattribute ($tagDefinition, "onlylist");
  }
  
  return $return;
}


// get multiple objects
$multiobject_array = explode ("|", $multiobject);

$add_onload = "";
$js_tpl_code = "";
$mediafile = "";
$is_image = false;
$is_video = false;
$is_audio = false;
$mediapreview = "";
$template = "";
$templatedata = "";
$error = false;
$groups = array();
$count = 0;
$allTexts = array();

// run through each object
foreach ($multiobject_array as $object) 
{
  // ignore empty entries
  $object = trim ($object);
  if (empty ($object)) continue;

  $count++;
  $site_item = getpublication ($object);
  $location_item_esc = getlocation ($object);
  $location_item= deconvertpath ($location_item_esc, "file");
  $cat_item = getcategory ($site_item, $object);
  $file_item = getobject ($object);
  
  if (empty ($site))
  {
    $site = $site_item;
  }
  elseif ($site != $site_item)
  {
    $error = getescapedtext ($hcms_lang['the-files-must-be-from-the-same-publication'][$lang]);
    break;
  }
  
  // ------------------------------ permission section --------------------------------
  
  // check access permissions
  $ownergroup = accesspermission ($site_item, $location_item_esc, $cat_item);
  $setlocalpermission = setlocalpermission ($site_item, $ownergroup, $cat_item);
  
  // check localpermissions for DAM usage only
  if ($mgmt_config[$site]['dam'] == true && $setlocalpermission['root'] != 1)
  {
    killsession ($user);
    break;
  }
  // check for general root element access since localpermissions are checked later
  elseif (
           !checkpublicationpermission ($site) || 
           (!valid_objectname ($file_item) && ($setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1)) || 
           !valid_publicationname ($site) || !valid_locationname ($location_item) || !valid_objectname ($cat_item)
         ) 
  {
    killsession ($user);
    break;
  }
  
  // --------------------------------- logic section ----------------------------------

  $groups[] = $ownergroup;
  
  // object information
  $objectinfo_item = getobjectinfo ($site_item, $location_item, $file_item);

  // location name
  $locationname = getlocationname ($site_item, $location_item_esc, $cat_item);

  // define link to open object
  if ($setlocalpermission['root'] == 1)
  {
    $openobject = "onclick=\"hcms_openWindow('frameset_content.php?ctrlreload=yes&site=".url_encode($site_item)."&cat=".url_encode($cat_item)."&location=".url_encode($location_item_esc)."&page=".url_encode($file_item)."&token=".$token."', '".$objectinfo_item['container_id']."', 'status=yes,scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").")\"";
  }
  else $openobject = "";
  
  // media  
  if (!empty ($objectinfo_item['media']))
  {
    $mediafile = $objectinfo_item['media'];
    $media_info = getfileinfo ($site, $mediafile, "comp");
    $thumbnail = $media_info['filename'].".thumb.jpg";
    $mediadir = getmedialocation ($site, $objectinfo_item['media'], "abs_path_media").$site."/";
    
    // check media
    if (is_image ($media_info['ext'])) $is_image = true;
    if (is_video ($media_info['ext'])) $is_video = true;
    if (is_audio ($media_info['ext'])) $is_audio = true;
    
    // prepare media file
    $temp = preparemediafile ($site, $mediadir, $thumbnail, $user);
    
    // if encrypted
    if (!empty ($temp['result']) && !empty ($temp['crypted']) && is_file ($temp['templocation'].$temp['tempfile']))
    {
      $mediadir = $temp['templocation'];
      $thumbnail = $temp['tempfile'];
    }
    // if restored
    elseif (!empty ($temp['result']) && !empty ($temp['restored']) && is_file ($temp['location'].$temp['file']))
    {
      $mediadir = $temp['location'];
      $thumbnail = $temp['file'];
    }

    // thumbnails preview
    if (is_file ($mediadir.$thumbnail))
    {
      $imgsize = getimagesize ($mediadir.$thumbnail);
      
      // calculate image ratio to define CSS for image container div-tag
      if (is_array ($imgsize))
      {
    		$imgwidth = $imgsize[0];
    		$imgheight = $imgsize[1];
        $imgratio = $imgwidth / $imgheight;   
        
        // image width >= height
        if ($imgratio >= 1) $ratio = "width:100px;";
        // image width < height
        else $ratio = "height:100px;";
      }
      // default value
      else
      {
        $ratio = "width:100px;";
      }
      
      // if thumbnail is smaller than defined thumbnail size
      if ($imgwidth < 100 && $imgheight < 100) $style_size = "";
      else $style_size = $ratio;
      
      $mediapreview .= "<div id=\"image".$count."\" style=\"margin:3px; height:100px; float:left; cursor:pointer;\" ".$openobject."><img src=\"".createviewlink ($site, $thumbnail, $objectinfo_item['name'])."\" class=\"hcmsImageItem\" style=\"".$style_size."\" alt=\"".$locationname.$objectinfo_item['name']."\" title=\"".$locationname.$objectinfo_item['name']."\" /></div>";;
    }
    // no thumbnail available
    else
    {                 
      $mediapreview .= "<div id=\"image".$count."\" style=\"margin:3px; height:100px; float:left; cursor:pointer;\" ".$openobject."><img src=\"".getthemelocation()."img/".$objectinfo_item['icon']."\" style=\"border:0; width:100px;\" alt=\"".$locationname.$objectinfo_item['name']."\" title=\"".$locationname.$objectinfo_item['name']."\" /></div>";
    }
  }
  // standard thumbnail for non-multimedia objects
  else
  {                 
    $mediapreview .= "<div id=\"image".$count."\" style=\"margin:3px; height:100px; float:left; cursor:pointer;\" ".$openobject."><img src=\"".getthemelocation()."img/".$objectinfo_item['icon']."\" style=\"border:0; width:100px;\" alt=\"".$locationname.$objectinfo_item['name']."\" title=\"".$locationname.$objectinfo_item['name']."\" /></div>";
  }
  
  // container
  $content = loadcontainer ($objectinfo_item['container_id'], "work", $user);
  
  if (empty ($template))
  {
    $template = $objectinfo_item['template'];
  }
  elseif ($template != $objectinfo_item['template'])
  {
    $error = getescapedtext ($hcms_lang['the-objects-must-use-the-same-template'][$lang]);
    break;
  }
  
  if (empty ($templatedata))
  {
    // load template
    $tcontent = loadtemplate ($site_item, $template);
    $templatedata = $tcontent['content'];
    
    // try to get DB connectivity
    $db_connect = "";
    $dbconnect_array = gethypertag ($templatedata, "dbconnect", 0);
    
    if ($dbconnect_array != false)
    {
      foreach ($dbconnect_array as $hypertag)
      {
        $db_connect = getattribute ($hypertag, "file");
        
        if (!empty ($db_connect) && is_file ($mgmt_config['abs_path_data']."db_connect/".$db_connect)) 
        { 
          // include db_connect function
          @include_once ($mgmt_config['abs_path_data']."db_connect/".$db_connect);
          break;
        }
      }
    }
    
    // =========================================== JavaScript code ============================================

    // only for form views
    if (preg_match ("/\[JavaScript:scriptbegin/i", $templatedata))
    {
      // replace hyperCMS script code                  
      while (@substr_count (strtolower($templatedata), "[javascript:scriptbegin") > 0)
      {
        $jstagstart = strpos (strtolower($templatedata), "[javascript:scriptbegin");
        $jstagend = strpos (strtolower($templatedata), "scriptend]", $jstagstart + strlen ("[javascript:scriptbegin")) + strlen ("scriptend]");
        $jstag = substr ($templatedata, $jstagstart, $jstagend - $jstagstart);

                  
        // remove JS code
        $templatedata = str_replace ($jstag, "", $templatedata);
          
        // assign code
        if (trim ($jstag))
        {
          // remove tags
          $jstag = str_ireplace ("[javascript:scriptbegin", "", $jstag);
          $jstag = str_ireplace ("scriptend]", "", $jstag);
          $js_tpl_code .= "\n".$jstag;
        }
      }
    }
  }
  
  $texts = getcontent ($content, "<text>");
  
  // Means that there where no entries found so we make an empty array
  if (!is_array ($texts)) $texts = array();
  
  $newtext = array();
  
  foreach ($texts as $text)
  {
    $id = getcontent ($text, "<text_id>");
    
    // read content using db_connect
    $db_connect_data = false; 
    
    if (isset ($db_connect) && $db_connect != "") 
    {
      $db_connect_data = db_read_text ($site, $objectinfo_item['container_id'], $content, $id, "", $user);
      
      if ($db_connect_data != false) 
      {
        $textcontent = $db_connect_data['text'];      
        // set true
        $db_connect_data = true;                    
      }
    }
    
    // read content from content container         
    if ($db_connect_data == false) $textcontent = getcontent ($text, "<textcontent>");
    
    // If we didn't find anything we stop
    if (!is_array ($id) || !is_array ($textcontent)) continue;
    
    $id = $id[0];
    $textcontent = trim ($textcontent[0]);
    
    // We ignore comments
    if (substr ($id, 0, strlen ('comment')) == "comment") continue;
    
    $newtext[$id] = $textcontent;
  }
  
  $allTexts[] = $newtext;
}

// fetch all texts
$text_array = gethypertag ($templatedata, "text", 0);
if (!is_array ($text_array)) $text_array = array();

// fetch all articles
$art_array = gethypertag ($templatedata, "arttext", 0);
if (!is_array ($art_array)) $art_array = array();

$all_array = array_merge ($text_array, $art_array);
$tagdata_array = gettagdata ($all_array);

// get character set
$result = getcharset ($site, $templatedata);

// for media files character set must be UTF-8
if ($mediafile != "")
{
  $charset = "UTF-8";
  $contenttype = "text/html; charset=".$charset;
}
elseif (!empty ($result['charset']))
{
  $charset = $result['charset'];
  $contenttype = $result['contenttype'];
}
elseif ($site != "")
{
  $charset = $mgmt_config[$site]['default_codepage'];
  $contenttype = "text/html; charset=".$charset;
}
else
{
  $charset = getcodepage ($lang);
  $contenttype = "text/html; charset=".$charset;
}

// loop through each tagdata array
foreach ($tagdata_array as $id => $tagdata) 
{
  // we kick the fields out if there should be groups checked and the user isn't allowed to view/edit it
  if ($tagdata->groupaccess) 
  {
    // if we don't have access through groups we will remove the field completely
    foreach ($groups as $group)
    {
      
      if (!checkgroupaccess ($tagdata->groupaccess, $group))
      {
        unset ($tagdata_array[$id]);
        continue 2;
      }
    }
  }
  
  foreach ($allTexts as $object) 
  {
    // if the current element isn't ignored we continue
    if (isset ($tagdata->ignore) && $tagdata->ignore == true) continue;
    
    // calculate the value we use
    $value = (array_key_exists ($id, $object) ? $object[$id] : $tagdata->defaultvalue);
    
    if (!isset ($tagdata->fieldvalue)) 
    {
      $tagdata->fieldvalue = $value;
      $tagdata->ignore = false;
      $tagdata->samecontent = false;
    }
    else
    {
      // content will be appended instead of edited/replaced (new since version 8.0.1)
      if (getsession ("temp_appendcontent") == true)
      {
        // do not lock any field
        $tagdata->locked = false;
        $tagdata->fieldvalue = "";
        $tagdata->constraint = "";

        // content is different
        if ($tagdata->fieldvalue != $value)
        {
          // do not ignore field
          $tagdata->ignore = true;
          $tagdata->samecontent = false;
        }
        else
        {
          $tagdata->ignore = false;
          $tagdata->samecontent = true;
        }
      }
      // content will be edited/replaced or locked
      else
      {
        // content is different and unlocked
        if ($tagdata->fieldvalue != $value)
        {
          $tagdata->ignore = true;
          $tagdata->samecontent = false;
          $tagdata->locked = true;
          $tagdata->constraint = "";
        }
        // content is the same and locked
        else
        {
          $tagdata->ignore = false;
          $tagdata->samecontent = true;
          $tagdata->locked = false;
        }
      }
    }
  }
}

// define form function call for unformated text constraints
$add_constraint = "";

foreach ($tagdata_array as $key => $tagdata)
{
  if (trim ($tagdata->constraint) != "")
  {
    if ($add_constraint == "") $add_constraint = $tagdata->constraint;
    else $add_constraint = $add_constraint.",".$tagdata->constraint;
  }
}

if ($add_constraint != "") $add_constraint = "checkcontent = validateForm(".$add_constraint.");";

// check session of user
checkusersession ($user);

// ------------------ image parameters ------------------------
if ($is_image)
{
  // read all possible formats to convert to from the mediaoptions
  $convert_formats = array();
  
  if (isset ($mgmt_imageoptions) && is_array ($mgmt_imageoptions) && !empty ($mgmt_imageoptions))
  {
    foreach ($mgmt_imageoptions as $format => $configs)
    {
      if (array_key_exists ('original', $configs))
      {
        $tmp = explode (".", $format);
        $convert_formats[] = $tmp[1];
      }
    }
  }
  
  // add gif, jpg and png because these are our default conversions
  if (!in_array ('gif', $convert_formats)) $convert_formats[] = 'gif';  
  if (!in_array ('jpg', $convert_formats) && !in_array ('jpeg', $convert_formats)) $convert_formats[] = 'jpg';
  if (!in_array ('png', $convert_formats)) $convert_formats[] = 'png';
  
  $available_colorspaces = array();
  $available_colorspaces['CMYK'] = 'CMYK';
  $available_colorspaces['GRAY'] = 'GRAY';
  $available_colorspaces['CMY'] = 'CMY';
  $available_colorspaces['RGB'] = 'RGB';
  $available_colorspaces['sRGB'] = 'sRGB';
  $available_colorspaces['Transparent'] = 'Transparent';
  $available_colorspaces['XYZ'] = 'XYZ';
  
  $available_flip = array();
  $available_flip['fv'] = $hcms_lang['vertical'][$lang];
  $available_flip['fh'] = $hcms_lang['horizontal'][$lang];
  $available_flip['fv fh'] = $hcms_lang['both'][$lang];
}
// ------------------ video/audio parameters ------------------------
elseif ($is_video || $is_audio)
{
  // read supported formats
  $available_extensions = array();
  
  foreach ($mgmt_mediaoptions as $ext => $options)
  {
    if ($ext != "thumbnail-video" && $ext != "thumbnail-audio" && $ext != "autorotate-video")
    {
    	// remove the dot
    	$name = strtolower (trim ($ext, "."));    
    	$available_extensions[$name] = strtoupper ($name);
    }
  }
  
  // include media options
  require ($mgmt_config['abs_path_cms']."include/mediaoptions.inc.php");
}

// set character set in header
if (!empty ($charset)) header ('Content-Type: text/html; charset='.$charset);
?>
<!DOCTYPE html>
<html>
<head>
  <title>hyperCMS</title>
  <meta charset="<?php echo $charset; ?>" />
  <meta name="viewport" content="width=580, initial-scale=0.9, maximum-scale=1.0, user-scalable=1" />
  
  <!-- JQuery and JQuery UI -->
  <script type="text/javascript" src="<?php echo $mgmt_config['url_path_cms']; ?>javascript/jquery/jquery-1.12.4.min.js"></script>
  <script type="text/javascript" src="javascript/jquery/plugins/jquery.color.js"></script>
  <script type="text/javascript" src="javascript/jquery-ui/jquery-ui-1.12.1.min.js"></script>
  <link rel="stylesheet" href="javascript/jquery-ui/jquery-ui-1.12.1.css" type="text/css" />
  
  <!-- Tag it script -->
  <script src="javascript/tag-it/tag-it.min.js" type="text/javascript" charset="utf-8"></script>
  <link href="javascript/tag-it/jquery.tagit.css" rel="stylesheet" type="text/css" />
  <link href="javascript/tag-it/tagit.ui-zendesk.css" rel="stylesheet" type="text/css" />

  <!-- CKEditor -->
  <script type="text/javascript" src="<?php echo $mgmt_config['url_path_cms']; ?>editor/ckeditor/ckeditor.js"></script>
  <script> CKEDITOR.disableAutoInline = true;</script>
  
  <!-- Richcalendar -->
  <link rel="stylesheet" href="<?php echo $mgmt_config['url_path_cms']; ?>javascript/rich_calendar/rich_calendar.css" />
  <script type="text/javascript" src="<?php echo $mgmt_config['url_path_cms']; ?>javascript/rich_calendar/rich_calendar.js"></script>
  <script type="text/javascript" src="<?php echo $mgmt_config['url_path_cms']; ?>javascript/rich_calendar/rc_lang_en.js"></script>
  <script type="text/javascript" src="<?php echo $mgmt_config['url_path_cms']; ?>javascript/rich_calendar/rc_lang_de.js"></script>
  <script type="text/javascript" src="<?php echo $mgmt_config['url_path_cms']; ?>javascript/rich_calendar/domready.js"></script>
  
  <link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
  
  <style>
  .row
  {
    margin-top: 1px;
  }
  
  .row *
  {
    vertical-align: middle;
  }
  
  .row input[type="radio"]
  {
    margin: 0px;
    padding: 0px;
  }
  
  .cell
  {
    vertical-align: top;
    display: inline-block;
    margin-left: 3px;
    margin-top: 3px;
    <?php if ($is_image) { ?>width: 230px;<?php } else { ?>width: 210px;<?php } ?>
  }
  
  .cellButton
  {
    vertical-align: middle;
    padding: 0px 2px;
  }
  
  .cell *
  {
    font-size: 11px;
  }
  
  #renderOptions input[type=text], #renderOptions select
  {
    padding: 3px;
  }
  </style>
 
  <script type="text/javascript" src="javascript/main.js"></script>

  <script type="text/javascript">
  
  var image_checked = false;
  var video_checked = false;
  
  // ----- Form view lock and unlock -----
  function unlockFormBy (element)
  {
    if (element)
    {
      // form locked
      if (element.checked == true)
      {
        // AJAX request to set appendcontent
        $.post("<?php echo $mgmt_config['url_path_cms']; ?>service/setappendcontent.php", {appendcontent: false});
      }
      // form unlocked
      else
      {
        // AJAX request to set appendcontent
        $.post("<?php echo $mgmt_config['url_path_cms']; ?>service/setappendcontent.php", {appendcontent: true});
      }
      
      // reload
      setTimeout (function(){ location.reload(true); }, 500);
    }
  }
  
  // ----- Field controls for form views -----
  
  // Alisa for checkFieldValue
  function checkValue (id, value)
  {
    return checkFieldValue (id, value);
  }
  
  function checkFieldValue (id, value)
  {
    if (document.getElementById(id).type === 'checkbox')
    {
      if (document.getElementById(id).checked == true && document.getElementById(id).value == value) return true;
    }
    else if (document.getElementById(id).value)
    {
      if (document.getElementById(id).value == value || document.getElementById(id).value.indexOf(value) > -1) return true;
    }
    
    return false;
  }

  // Alias for lockField
  function lockEdit (id)
  {
    lockField (id);
  }
  
  function lockField (id)
  {
    if (document.getElementById(id))
    {
      document.getElementById(id).disabled = true;
    }
    
    if (document.getElementById(id+'_controls'))
    {
      document.getElementById(id+'_controls').style.display = 'none';
    }
    
    var elements = document.getElementsByClassName(id);
    
    if (elements.length > 0)
    {
      for (var i = 0; i < elements.length; i++)
      {
        elements[i].disabled = true;
      }
    }
    
    if (document.getElementById(id+'_protect'))
    {
      document.getElementById(id+'_protect').style.display = 'inline';
    }
  }
  
  // Alias for unlockField
  function unlockEdit (id)
  {
    unlockField (id);
  }
  
  function unlockField (id)
  {
    if (document.getElementById(id))
    {
      document.getElementById(id).disabled = false;
    }
    
    if (document.getElementById(id+'_controls'))
    {
      document.getElementById(id+'_controls').style.display = 'inline-block';
    }

    var elements = document.getElementsByClassName(id);
    
    if (elements.length > 0)
    {
      for (var i = 0; i < elements.length; i++)
      {
        elements[i].disabled = false;
      }
    }

    if (document.getElementById(id+'_protect'))
    {
      document.getElementById(id+'_protect').style.display = 'none';
    }
  }
  
  function hideField (id)
  {   
    var elements = document.getElementsByClassName(id);
    
    if (elements.length > 0)
    {
      for (var i = 0; i < elements.length; i++)
      {
        elements[i].style.display = 'none';
      }
    }
    
    lockEdit (id);
  }
  
  function showField (id)
  {   
    var elements = document.getElementsByClassName(id);
    
    if (elements.length > 0)
    {
      for (var i = 0; i < elements.length; i++)
      {
        elements[i].style.display = '';
      }
    }
    
    unlockEdit (id);
  } 

  // ----- Rich Calendar -----
  
  var cal_obj = null;
  var cal_format = null;
  var cal_field = null;
  
  function show_cal (el, field_id, format)
  {
    if (cal_obj) return;
    
    cal_field = field_id;
    cal_format = format;
    var datefield = document.getElementById(field_id);
    
    cal_obj = new RichCalendar();
    cal_obj.start_week_day = 1;
    cal_obj.show_time = false;
    cal_obj.language = '<?php echo getcalendarlang ($lang); ?>';
    cal_obj.user_onchange_handler = cal_on_change;
    cal_obj.user_onclose_handler = cal_on_close;
    cal_obj.user_onautoclose_handler = cal_on_autoclose;
    cal_obj.parse_date(datefield.value, cal_format);
    cal_obj.show_at_element(datefield, 'adj_left-bottom');
  }
  
  // onchange handler
  function cal_on_change (cal, object_code)
  {
    if (object_code == 'day')
    {
      document.getElementById(cal_field).value = cal.get_formatted_date(cal_format);
      cal.hide();
      cal_obj = null;
    }
  }
  
  // user defined onclose handler (used in pop-up mode - when auto_close is true)
  function cal_on_close (cal)
  {
  	cal.hide();
  	cal_obj = null;
  }
  
  // user defined onautoclose handler
  function cal_on_autoclose (cal)
  {
  	cal_obj = null;
  }
  
  function save (reload)
  {
    var checkcontent = true;
    var checkimage = false;
    var checkvideo = false;
    
    <?php echo $add_constraint; ?>
    
    <?php if ($is_image) { ?>
    if (checkcontent == true) checkcontent = checkimage = checkImageForm();
    <?php } elseif ($is_video) { ?>     
    if (checkcontent == true) checkcontent = checkvideo = checkVideoForm();
    <?php } ?>
  
    if (checkcontent == true)
    {
      // update all CKEDitor instances
      for (var instanceName in CKEDITOR.instances)
        CKEDITOR.instances[instanceName].updateElement();
      
      // get objects from multiobject and content fields
      var obj = $('#objs').val().split("|");
      var fields = $('#fields').val().split("|");

      // init content post data
      var postdata_content = {
        'savetype' : 'auto',
        'db_connect': '<?php echo $db_connect; ?>',
        'contenttype': '<?php echo $contenttype; ?>',
        'token': '<?php echo $token; ?>',
        'appendcontent': '<?php if (getsession ("temp_appendcontent") == true) echo "yes"; ?>'
      };
      
      for (var nr in fields)
      {
        var field = $('[id="'+fields[nr]+'"]');
        
        if (!field.prop) 
        {
          alert ('<?php echo getescapedtext ($hcms_lang['could-not-find-the-value-for-one-of-the-fields'][$lang], $charset, $lang); ?>');
        }
        
        var name = field.prop('name');
        var value = '';
        
        // for input we get the type
        if (field.prop('tagName').toUpperCase() == 'INPUT' && field.prop('type').toUpperCase() == 'CHECKBOX') 
          value = (field.prop('checked') ? field.prop('value') : '');
        // formatted fields doesn't need to be encoded as this is already done by CKEDitor
        // use direct value for selectboxes
        else if ( field.attr('id').slice(0, 'textf_'.length) == 'textf_' || field.prop('tagName').toUpperCase() == 'SELECT')
          value = field.prop('value');
        else if (field.prop('value') == "")
          value = "";
        else
          value = field.prop('value');
        
        postdata_content[name] = value;
      }
      
      // collect image form data
      if (checkimage == true)
      {
        // init image post data
        var postdata_image = {
          'savetype' : 'auto',
          'token': '<?php echo $token; ?>'
        };
        
        // get all image form elements
        var imageoptions = document.forms['imageoptions'].elements;
        
        if (imageoptions)
        {
          var name;
          
          for (var i=0; i < imageoptions.length; i+=1) 
          {
            if (imageoptions[i].disabled == false)
            {
              name = imageoptions[i].name;
              
              // checkbox
              if (imageoptions[i].type == "checkbox" && imageoptions[i].checked == true) postdata_image[name] = imageoptions[i].value;
              // select box
              else if (imageoptions[i].type == "select-one") postdata_image[name] = imageoptions[i].options[imageoptions[i].selectedIndex].value;
              // text or hidden input
              else if (imageoptions[i].type == "text" || imageoptions[i].type == "hidden") postdata_image[name] = imageoptions[i].value;
            }
          }
        }
      }
      
      // collect video/audio form data
      if (checkvideo == true)
      {
        // init video post data
        var postdata_video = {
          'savetype' : 'auto',
          'token': '<?php echo $token; ?>'
        };
        
        // get all video form elements
        var videooptions = document.forms['videooptions'].elements;
        
        if (videooptions)
        {
          var name;
          
          for (var i=0; i < videooptions.length; i+=1) 
          {
            if (videooptions[i].disabled == false)
            {
              name = videooptions[i].name;
              
              // checkbox
              if (videooptions[i].type == "checkbox" && videooptions[i].checked == true) postdata_video[name] = videooptions[i].value;
              // radio
              else if (videooptions[i].type == "radio" && videooptions[i].checked == true) postdata_video[name] = videooptions[i].value;
              // select box
              else if (videooptions[i].type == "select-one") postdata_video[name] = videooptions[i].options[videooptions[i].selectedIndex].value;
              // text or hidden input
              else if (videooptions[i].type == "text" || videooptions[i].type == "hidden") postdata_video[name] = videooptions[i].value;
            }
          }
        }
      }
      
      // show savelayer across the whole page
      $('#savelayer').show();
      
      // save each object
      for (nr in obj) 
      {
        file = obj[nr];
        
        // ignore empty values
        if($.trim(file) == "") continue;
        
        // for each selected object the location and object name must be provided
        var len = file.lastIndexOf('/')+1;
        
        postdata_content['page'] = file.slice(len);
        postdata_content['location'] = file.slice(0, len);

        // save content
        $.ajax({
            'type': "POST",
            'url': "<?php echo $mgmt_config['url_path_cms']; ?>service/savecontent.php",
            'data': postdata_content,
            'async': false,
            'dataType': 'json'
          }).error(function(data) {
            // server message
            if (data.message && data.message.length !== 0)
            {
              alert(hcms_entity_decode(data.message));
            }
            else
            {
              alert('Internal Error');
            }
          }).success(function(data) {
            // server message
            if (data.message && data.message.length !== 0)
            {
              alert(hcms_entity_decode(data.message));
            }
        });
        
        // render and save image
        if (image_checked == true)
        {
          var multiobject = document.forms['reloadform'].elements['multiobject'];
          postdata_image['page'] = postdata_content['page'];
          postdata_image['location'] = postdata_content['location'];
          
          $.ajax({
              'type': "POST",
              'url': "<?php echo $mgmt_config['url_path_cms']; ?>service/renderimage.php",
              'data': postdata_image,
              'async': false,
              'dataType': 'json'
            }).error(function(data) {
              // server message
              if (data.message && data.message.length !== 0)
              {
                alert(hcms_entity_decode(data.message));
              }
              else
              {
                alert('Internal Error');
              }
            }).success(function(data) {
              // object name after rendering
              if (data.object && data.object.length !== 0)
              {
                var multiobject_new = multiobject.value.replace(file, data.object);

                // update multiobjects
                if (multiobject_new != multiobject.value) multiobject.value = multiobject_new;
              }
            
              // server message
              if (data.success == false && data.message && data.message.length !== 0)
              {
                alert(hcms_entity_decode(data.message));
              }
          });
        }
        
        // render and save video/audio
        if (video_checked == true)
        {
          postdata_video['page'] = postdata_content['page'];
          postdata_video['location'] = postdata_content['location'];

          $.ajax({
              'type': "POST",
              'url': "<?php echo $mgmt_config['url_path_cms']; ?>service/rendervideo.php",
              'data': postdata_video,
              'async': false,
              'dataType': 'json'
            }).error(function(data) {
              // server message
              if (data.message && data.message.length !== 0)
              {
                alert(hcms_entity_decode(data.message));
              }
              else
              {
                alert('Internal Error');
              }
            }).success(function(data) {
              // server message
              if (data.success == false && data.message && data.message.length !== 0)
              {
                alert(hcms_entity_decode(data.message));
              }
          });
        }
      }
      
      if (reload == true) $('#reloadform').submit();
      return true;
    }
    else return false;
  }
    
  function validateForm() 
  {
    var i,p,q,nm,test,num,min,max,errors='',args=validateForm.arguments;
    
    for (i=0; i<(args.length-2); i+=3) 
    { 
      test = args[i+2];
      contentname = args[i+1];
      val = hcms_findObj(args[i]);
      
      if (val) 
      { 
        if (contentname != '')
        {
          nm = contentname;
        }
        else
        {
          nm = val.name;
          nm = nm.substring(nm.indexOf('_')+1, nm.length);
        }
        
        if ((val=val.value) != '' && test != '') 
        {
          if (test == 'audio' || test == 'compressed' || test == 'flash' || test == 'image' || test == 'text' || test == 'video') 
          { 
            errors += checkMediaType(val, contentname, test);
          } 
          else if (test.indexOf('isEmail')!=-1) 
          { 
            p=val.indexOf('@');
            if (p<1 || p==(val.length-1)) errors += nm+' - <?php echo getescapedtext ($hcms_lang['value-must-contain-an-e-mail-address'][$lang], $charset, $lang); ?>\n';
          } 
          else if (test!='R') 
          { 
            num = parseFloat(val);
            if (isNaN(val)) errors += nm+' - <?php echo getescapedtext ($hcms_lang['value-must-contain-a-number'][$lang], $charset, $lang); ?>\n';
            if (test.indexOf('inRange') != -1) 
            { 
              p=test.indexOf(':');
              if(test.substring(0,1) == 'R')
              {
                min=test.substring(8,p); 
              } else {
                min=test.substring(7,p); 
              }
              max=test.substring(p+1);
              if (num<min || max<num) errors += nm+' - <?php echo getescapedtext ($hcms_lang['value-must-contain-a-number-between'][$lang], $charset, $lang); ?> '+min+' - '+max+'.\n';
            } 
          } 
        } 
        else if (test.charAt(0) == 'R') errors += nm+' - <?php echo getescapedtext ($hcms_lang['a-value-is-required'][$lang], $charset, $lang); ?>\n'; 
      }
    } 
    
    if (errors) 
    {
      alert (hcms_entity_decode ('<?php echo getescapedtext ($hcms_lang['the-input-is-not-valid'][$lang], $charset, $lang); ?>\n ' + errors));
      return false;
    }  
    else return true;
  }
  
  function toggleDivAndButton (caller, element)
  {
    var options = $(element);
    caller = $(caller);  
    var time = 500;
      
    if (options.css('display') == 'none')
    {
      caller.addClass('hcmsButtonActive');
      activate();
      options.fadeIn(time);
    }
    else
    {
      caller.removeClass('hcmsButtonActive');
      options.fadeOut(time);
    }
  }
  
  function openerReload ()
  {
    // reload main frame
    if (opener != null && eval (opener.parent.frames['mainFrame']))
    {
      opener.parent.frames['mainFrame'].location.reload();
    }
    
    return true;
  } 

<?php if ($is_image) { ?>
  <!-- image -->
  
  function validateImageForm() 
  {
    var i,p,q,nm,test,num,min,max,errors='',args=validateImageForm.arguments;
    
    for (i=0; i<(args.length-2); i+=3) 
    { 
      test=args[i+2]; val=hcms_findObj(args[i]);
      
      if (val) 
      { 
        nm=val.name;
        nm=nm.substring(nm.indexOf('_')+1, nm.length);
        
        if ((val=val.value)!='') 
        {
          if (test.indexOf('isEmail')!=-1) 
          { 
            p=val.indexOf('@');
            if (p<1 || p==(val.length-1)) errors += nm+'-<?php echo getescapedtext ($hcms_lang['value-must-contain-an-e-mail-address'][$lang], $charset, $lang); ?>.\n';
          } 
          else if (test!='R') 
          { 
            num = parseFloat(val);
            if (isNaN(val)) errors += '-<?php echo getescapedtext ($hcms_lang['value-must-contain-a-number'][$lang], $charset, $lang); ?>.\n';
            if (test.indexOf('inRange') != -1) 
            { 
              p=test.indexOf(':');
              min=test.substring(8,p); 
              max=test.substring(p+1);
              if (num<min || max<num) errors += '-<?php echo getescapedtext ($hcms_lang['value-must-contain-a-number-between'][$lang], $charset, $lang); ?> '+min+' - '+max+'.\n';
            } 
          } 
        } 
        else if (test.charAt(0) == 'R') errors += '-<?php echo getescapedtext ($hcms_lang['a-value-is-required'][$lang], $charset, $lang); ?>.\n'; 
      }
    } 
    
    if (errors) 
    {
      alert(hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['the-input-is-not-valid'][$lang], $charset, $lang); ?>\n ' + errors));
      return false;
    }  
    else return true;
  }
  
  function checkImageForm()
  {
    var result = true;

    if ($('#percentage').prop('checked'))
    {
      image_checked = true;
      result = validateImageForm ('imagepercentage','','RinRange1:200');
    }
    
    if (result && $('#width').prop('checked'))
    {
      image_checked = true;
      result = validateImageForm ('imagewidth','','RisNum');
    }
    
    if (result && $('#height').prop('checked'))
    {
      image_checked = true;
      result = validateImageForm ('imageheight','','RisNum');
    }
    
    if (result && $('#rotate').prop('checked'))
    {
      image_checked = true;
      result = true;
    }
    
    if (result && $('#chbx_brightness').prop('checked'))
    {
      image_checked = true;
      result = validateImageForm('brightness', '', 'RinRange-100:100');
    } 
    
    if (result && $('#chbx_contrast').prop('checked'))
    {
      image_checked = true;
      result = validateImageForm('contrast', '', 'RinRange-100:100');
    } 
    
    if (result && $('#chbx_colorspace').prop('checked'))
    {
      image_checked = true;
      result = true;
    }
    
    if (result && $('#chbx_flip').prop('checked'))
    {
      image_checked = true;
      result = true;
    }
    
    if (result && $('#sepia').prop('checked'))
    {
      image_checked = true;
      result = validateImageForm('sepia_treshold', '', 'RinRange0:99.9');
    }
    
    if (result && $('#blur').prop('checked')) 
    {
      image_checked = true;
      result = validateImageForm('blur_radius', '', 'RisNum', 'blur_sigma', '', 'RinRange0.1:3');
    }
    
    if (result && $('#sharpen').prop('checked')) 
    {
      image_checked = true;
      result = validateImageForm('sharpen_radius', '', 'RisNum', 'sharpen_sigma', '', 'RinRange0.1:3');
    }
    
    if (result && $('#sketch').prop('checked')) 
    {
      image_checked = true;
      result = validateImageForm('sketch_radius', '', 'RisNum', 'sketch_sigma', '', 'RisNum', 'sketch_angle', '', 'RisNum');
    }
    
    if (result && $('#paint').prop('checked')) 
    {
      image_checked = true;
      result = validateImageForm('paint_value', '', 'RisNum');
    }
    
    if (result && image_checked && $('#renderimage').prop('checked'))
    {
      image_checked = true;
    }
    else image_checked = false;
    
    // display warning if any image option is checked
    if (image_checked)
    {
      if (!confirm (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-overwrite-the-original-file'][$lang], $charset, $lang); ?>"))) return false;
    }
    // set image checked to enable rendering
    else image_checked = true;
    
    return result;
  }
  
  function toggle_percentage () 
  {
    var percentage = $('#percentage');
    var width = $('#width');
    var height = $('#height');
    var percent = $('#imagepercentage');
    
    if (percentage.prop('checked')) 
    {
      percent.prop('disabled', false);
      width.prop('checked', false);
      height.prop('checked', false);
      
      toggle_size_height();
      toggle_size_width();
    }
    else 
    {
      percent.prop('disabled', true);
    }
  }
  
  function toggle_size_width () 
  {
    var percentage = $('#percentage');
    var width = $('#width');
    var height = $('#height');
    var imagewidth = $('#imagewidth');
    
    if (width.prop('checked')) 
    {
      imagewidth.prop('disabled', false);
      percentage.prop('checked', false);
      height.prop('checked', false);
      
      toggle_size_height();
      toggle_percentage();
    }
    else
    {
      imagewidth.prop('disabled', true);
    }
  }
  
  function toggle_size_height () 
  {
    var percentage = $('#percentage');
    var width = $('#width');
    var height = $('#height');
    var imageheight = $('#imageheight');
    
    if (height.prop('checked')) 
    {
      imageheight.prop('disabled', false);
      width.prop('checked', false);
      percentage.prop('checked', false);
      
      toggle_size_width();
      toggle_percentage();
    }
    else
    {
      imageheight.prop('disabled', true);
    }
  }
  
  function toggle_rotate () 
  {
    var rotate = $('#rotate');
    var chbxflip = $('#chbx_flip');
    var degree = $('#degree');
    
    if(rotate.prop('checked')) 
    {
      chbxflip.prop('checked', false);
      degree.prop('disabled', false);
      
      toggle_flip();   
    }
    else
    {
      degree.prop('disabled', true);
    }
  }
  
  function toggle_brightness () 
  {
    var chbx = $('#chbx_brightness');
    var brightness = $('#brightness');
    
    if (chbx.prop('checked')) 
    {
      brightness.prop('disabled', false);
      brightness.spinner("option", "disabled", false);
    }
    else 
    {
      brightness.prop('disabled', true);
      brightness.spinner("option", "disabled", true);
    }
  }
  
  function toggle_contrast () 
  {
    var chbx = $('#chbx_contrast');
    var contrast = $('#contrast');
    
    if (chbx.prop('checked')) 
    {
      contrast.prop('disabled', false);
      contrast.spinner("option", "disabled", false);
    }
    else 
    {
      contrast.prop('disabled', true);
      contrast.spinner("option", "disabled", true);
    }
  }
  
  function toggle_colorspace () 
  {
    var chbx = $('#chbx_colorspace');
    var space = $('#colorspace');
    
    if (chbx.prop('checked'))
    {
      space.prop('disabled', false);
    }
    else
    {
      space.prop('disabled', true);
    }
  }
  
  function toggle_flip () 
  {
    var rotate = $('#rotate');
    var chbxflip = $('#chbx_flip');
    var flip = $('#flip');
    
    if (chbxflip.prop('checked')) 
    {
      rotate.prop('checked', false);
      flip.prop('disabled', false);
      
      toggle_rotate();
    }
    else
    {
      flip.prop('disabled', true);
    }
  }
  
  function toggle_sepia () 
  {
    var sepia = $('#sepia');
    var treshold = $('#sepia_treshold');
    var blur = $('#blur');
    var sharpen = $('#sharpen');
    var sketch = $('#sketch');
    var paint = $('#paint');
    
    if (sepia.prop('checked')) 
    {
      treshold.prop('disabled', false);
      blur.prop('checked', false);
      sharpen.prop('checked', false);
      sketch.prop('checked', false);
      paint.prop('checked', false);
      
      treshold.spinner("option", "disabled", false);
      
      toggle_blur();
      toggle_sharpen();
      toggle_sketch();
      toggle_paint();
    }
    else
    {
      treshold.prop('disabled', true);
      
      treshold.spinner("option", "disabled", true);
    }
  }
  
  function toggle_blur () 
  {
    var sepia = $('#sepia');
    var radius = $('#blur_radius');
    var sigma = $('#blur_sigma');
    var blur = $('#blur');
    var sharpen = $('#sharpen');
    var sketch = $('#sketch');
    var paint = $('#paint');
    
    if (blur.prop('checked'))
    {
      radius.prop('disabled', false);
      sigma.prop('disabled', false);
      sepia.prop('checked', false);
      sharpen.prop('checked', false);
      sketch.prop('checked', false);
      paint.prop('checked', false);
      
      sigma.spinner("option", "disabled", false);
      
      toggle_sepia();
      toggle_sharpen();
      toggle_sketch();
      toggle_paint();
    }
    else
    {
      sigma.prop('disabled', true);
      radius.prop('disabled', true);
      
     sigma.spinner("option", "disabled", true);
    }
  }
  
  function toggle_sharpen ()
  {
    var sepia = $('#sepia');
    var radius = $('#sharpen_radius');
    var sigma = $('#sharpen_sigma');
    var blur = $('#blur');
    var sharpen = $('#sharpen');
    var sketch = $('#sketch');
    var paint = $('#paint');
    
    if (sharpen.prop('checked'))
    {
      radius.prop('disabled', false);
      sigma.prop('disabled', false);
      sepia.prop('checked', false);
      blur.prop('checked', false);
      sketch.prop('checked', false);
      paint.prop('checked', false);
      
      sigma.spinner("option", "disabled", false);
      
      toggle_sepia();
      toggle_blur();
      toggle_sketch();
      toggle_paint();
    }
    else
    {
      sigma.prop('disabled', true);
      radius.prop('disabled', true);
      
     sigma.spinner("option", "disabled", true);
    }
  }
  
  function toggle_sketch ()
  {
    var sepia = $('#sepia');
    var radius = $('#sketch_radius');
    var sigma = $('#sketch_sigma');
    var angle = $('#sketch_angle');
    var blur = $('#blur');
    var sharpen = $('#sharpen');
    var sketch = $('#sketch');
    var paint = $('#paint');
    
    if (sketch.prop('checked'))
    {
      radius.prop('disabled', false);
      sigma.prop('disabled', false);
      angle.prop('disabled', false);
      sepia.prop('checked', false);
      blur.prop('checked', false);
      sharpen.prop('checked', false);
      paint.prop('checked', false);
          
      toggle_sepia();
      toggle_blur();
      toggle_sharpen();
      toggle_paint();
    }
    else
    {
      sigma.prop('disabled', true);
      radius.prop('disabled', true);
      angle.prop('disabled', true);
    }
  }
  
  function toggle_paint () 
  {
    var sepia = $('#sepia');
    var value = $('#paint_value');
    var blur = $('#blur');
    var sharpen = $('#sharpen');
    var sketch = $('#sketch');
    var paint = $('#paint');
    
    if (paint.prop('checked'))
    {
      value.prop('disabled', false);
      sepia.prop('checked', false);
      blur.prop('checked', false);
      sketch.prop('checked', false);
      sharpen.prop('checked', false);
          
      toggle_sepia();
      toggle_blur();
      toggle_sharpen();
      toggle_sketch();
    }
    else
    {
      value.prop('disabled', true);
    }
  }
  
  function activate ()
  {
    toggle_percentage();
    toggle_size_width();
    toggle_size_height();
    toggle_sepia();
    toggle_blur();
    toggle_sharpen();
    toggle_sketch();
    toggle_paint();
    toggle_flip();
    toggle_rotate();
    toggle_brightness();
    toggle_contrast();
    toggle_colorspace();
  }
  
  $(window).load( function()
  {
    var spinner_config_bc = { step: 1, min: -100, max: 100}
    var spinner_config_sep = { step: 0.1, min: 0, max: 99.9}
    var spinner_config_sigma = { step: 0.1, min: 0.1, max: 3}
    $('#brightness').spinner(spinner_config_bc);
    $('#contrast').spinner(spinner_config_bc);
    $('#sepia_treshold').spinner(spinner_config_sep);
    $('#blur_sigma').spinner(spinner_config_sigma);
    $('#sharpen_sigma').spinner(spinner_config_sigma);
    
    // Add our special function
    $.fn.getGeneratorParameter = function() {
      return this.prop('name')+'='+this.val();
    } 
  });
  
  <?php } elseif ($is_video || $is_audio) { ?>
  <!-- video/audio -->
  
  function checkVideoForm()
  {
    var result = true;
    
    if ($('#rendervideo').prop('checked'))
    {
      var errors = '';
            
      if (document.getElementById('cut_yes').checked == true)
      {
        if (document.getElementById('cut_begin').value == "") errors += '- <?php echo getescapedtext ($hcms_lang['start'][$lang], $charset, $lang).": ".getescapedtext ($hcms_lang['a-value-is-required'][$lang], $charset, $lang); ?>\n';
        if (document.getElementById('cut_end').value == "") errors += '- <?php echo getescapedtext ($hcms_lang['end'][$lang], $charset, $lang).": ".getescapedtext ($hcms_lang['a-value-is-required'][$lang], $charset, $lang); ?>\n';
      }
      
      if (document.getElementById('thumb_yes').checked == true)
      {
        if (document.getElementById('thumb_frame').value == "") errors += '- <?php echo getescapedtext ($hcms_lang['frame'][$lang], $charset, $lang).": ".getescapedtext ($hcms_lang['a-value-is-required'][$lang], $charset, $lang); ?>\n';
      }
      
      if (document.getElementById('videosize_i').checked == true)
      {
        if (document.getElementById('width_i').value == "") errors += '- <?php echo getescapedtext ($hcms_lang['width'][$lang], $charset, $lang).": ".getescapedtext ($hcms_lang['a-value-is-required'][$lang], $charset, $lang); ?>\n';
        if (document.getElementById('height_i').value == "") errors += '- <?php echo getescapedtext ($hcms_lang['height'][$lang], $charset, $lang).": ".getescapedtext ($hcms_lang['a-value-is-required'][$lang], $charset, $lang); ?>\n';
      }
      
      if (errors) 
      { 
        alert(hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['the-following-error-occurred'][$lang], $charset, $lang); ?>\n ' + errors));
        
        result = false;
      }
      
      video_checked = true;
    }
    
    return result;
  }
  
  function checkCut()
  {
    var area1 = $('#cut_area');
    
    if (document.getElementById('cut_yes').checked == true)
    {
      area1.show();
    }
    else
    {
      area1.hide();
    }
  }
  
  <?php if (!$is_audio) { ?>
  function checkThumb()
  {
    var area1 = $('#thumb_area');
    
    if (document.getElementById('thumb_yes').checked == true)
    {
      area1.show();
    }
    else
    {
      area1.hide();
    }
  }
  <?php } ?>

  function toggle_sharpen () 
  {
    var chbx = $('#chbx_sharpen');
    var sharpen = $('#sharpen');
    
    if (chbx.prop('checked')) 
    {
      sharpen.prop('disabled', false);
      sharpen.spinner("option", "disabled", false);
    }
    else 
    {
      sharpen.prop('disabled', true);
      sharpen.spinner("option", "disabled", true);
    }
  }
  
  function toggle_gamma () 
  {
    var chbx = $('#chbx_gamma');
    var gamma = $('#gamma');
    
    if (chbx.prop('checked')) 
    {
      gamma.prop('disabled', false);
      gamma.spinner("option", "disabled", false);
    }
    else 
    {
      gamma.prop('disabled', true);
      gamma.spinner("option", "disabled", true);
    }
  }
  
  function toggle_brightness () 
  {
    var chbx = $('#chbx_brightness');
    var brightness = $('#brightness');
    
    if (chbx.prop('checked')) 
    {
      brightness.prop('disabled', false);
      brightness.spinner("option", "disabled", false);
    }
    else 
    {
      brightness.prop('disabled', true);
      brightness.spinner("option", "disabled", true);
    }
  }
  
  function toggle_contrast () 
  {
    var chbx = $('#chbx_contrast');
    var contrast = $('#contrast');
    
    if (chbx.prop('checked')) 
    {
      contrast.prop('disabled', false);
      contrast.spinner("option", "disabled", false);
    }
    else 
    {
      contrast.prop('disabled', true);
      contrast.spinner("option", "disabled", true);
    }
  }
  
  function toggle_saturation () 
  {
    var chbx = $('#chbx_saturation');
    var saturation = $('#saturation');
    
    if (chbx.prop('checked')) 
    {
      saturation.prop('disabled', false);
      saturation.spinner("option", "disabled", false);
    }
    else 
    {
      saturation.prop('disabled', true);
      saturation.spinner("option", "disabled", true);
    }
  }
  
  function toggle_rotate () 
  {
    var rotate = $('#rotate');
    var chbxflip = $('#chbx_flip');
    var degree = $('#degree');
    
    if(rotate.prop('checked')) 
    {
      chbxflip.prop('checked', false);
      degree.prop('disabled', false);
      
      toggle_flip();   
    }
    else
    {
      degree.prop('disabled', true);
    }
  }
  
  function toggle_flip () 
  {
    var rotate = $('#rotate');
    var chbxflip = $('#chbx_flip');
    var flip = $('#flip');
    var crop = $('#crop');
    
    if (chbxflip.prop('checked')) 
    {
      rotate.prop('checked', false);
      flip.prop('disabled', false);
      crop.prop('checked', false);
      
      toggle_rotate();
      toggle_crop();
    }
    else
    {
      flip.prop('disabled', true);
    }
  }
  
  function activate ()
  {
    toggle_sharpen();
    toggle_gamma();
    toggle_brightness();
    toggle_contrast();
    toggle_saturation();
    toggle_flip();
    toggle_rotate();
  }

  // ----- onload -----
  
  $(window).load( function()
  {
    var spinner_config = { step: 1, min: -100, max: 100}
    
    $('#sharpen').spinner(spinner_config);
    $('#gamma').spinner(spinner_config);
    $('#brightness').spinner(spinner_config);
    $('#contrast').spinner(spinner_config);
    $('#saturation').spinner(spinner_config);
    
    // add special function
    $.fn.getGeneratorParameter = function() {
      return this.prop('name')+'='+this.val();
    } 
  });
      
  <?php if (!$is_audio) { ?>
  $().ready(function() {
    checkCut();
    checkThumb();
  });
  <?php } ?>
  <?php } ?>
  </script>
</head>
  
<body class="hcmsWorkplaceGeneric" style="height:auto;">
  
    <!-- save layer --> 
    <div id="savelayer" class="hcmsLoadScreen"></div>
    
    <!-- top bar -->
    <div id="bar" class="hcmsWorkplaceBar">
      <table style="width:100%; height:100%; padding:0; border-spacing:0; border-collapse:collapse;">
        <tr>
          <td class="hcmsHeadline" style="text-align:left; vertical-align:middle; padding:0px 1px 0px 2px">
            <img name="Button_so" src="<?php echo getthemelocation(); ?>img/button_save.png" class="hcmsButton hcmsButtonSizeSquare" onClick="save(true);" alt="<?php echo getescapedtext ($hcms_lang['save'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['save'][$lang], $charset, $lang); ?>" />
            <?php if ($is_image || $is_video) { ?>
            <div class="hcmsButtonMenu" onclick="toggleDivAndButton(this, '#renderOptions');"><?php echo getescapedtext ($hcms_lang['options'][$lang], $charset, $lang); ?></div>
            <?php } ?>
          </td>
          <td style="width:26px; text-align:right; vertical-align:middle;">
            &nbsp;
          </td>
        </tr>
      </table>
    </div>
    
    
    <!-- rendering settings -->
    <div id="renderOptions" style="padding:2px 5px 10px 5px; width:740px; display:none; vertical-align:top; z-index:1; margin:36px 10px 0px 10px;" class="hcmsMediaRendering">
    
      <?php if ($is_image) { ?>
      <!-- start edit image -->
      <form name="imageoptions" id="imageoptions" action="" method="post">
        <input type="hidden" id="action" name="action" value="rendermedia">
          
        <!-- width or height -->
        <div class="cell">
          <div class="row" style="margin-left:20px;">
            <strong><?php echo getescapedtext ($hcms_lang['pixel-size'][$lang], $charset, $lang); ?></strong>
          </div>
          <div class="row">
            <input type="checkbox" id="percentage" name="imageresize" value="percentage" onclick="toggle_percentage();" />
            <label style="width:80px; display:inline-block;" for="percentage"><?php echo getescapedtext ($hcms_lang['percentage'][$lang], $charset, $lang); ?></label>
            <input name="imagepercentage" type="text" id="imagepercentage" size="5" maxlength="3" value="100" /> %
          </div>
          <div class="row">
            <input type="checkbox" id="width" name="imageresize" value="imagewidth" onclick="toggle_size_width();" />
            <label style="width:80px; display:inline-block;" for="width"><?php echo getescapedtext ($hcms_lang['width'][$lang], $charset, $lang); ?></label>
            <input name="imagewidth" type="text" id="imagewidth" size="5" maxlength="5" value="" /> px
          </div>
          <div class="row">
            <input type="checkbox" id="height" name="imageresize" value="imageheight" onclick="toggle_size_height();" />
            <label style="width:80px; display:inline-block;" for="height"><?php echo getescapedtext ($hcms_lang['height'][$lang], $charset, $lang); ?></label>
            <input name="imageheight" type="text" id="imageheight" size="5" maxlength="5" value="" /> px
          </div>
        </div>
        
        <?php if (getimagelib () != "GD") { ?>
        <!-- Effects -->
        <div class="cell">
          <div class="row" style="margin-left:20px;">
            <strong><?php echo getescapedtext ($hcms_lang['effects'][$lang], $charset, $lang); ?></strong>
          </div>
          <div class="row">
            <input type="checkbox" id="sepia" name="effect" value="sepia" onclick="toggle_sepia();" />
            <label style="width:60px; display:inline-block;" for="sepia"><?php echo getescapedtext ($hcms_lang['sepia'][$lang], $charset, $lang); ?></label>
            <input name="sepia_treshold" type="text" id="sepia_treshold" size="2" maxlength="2" value="80" /> %
          </div>
          <div class="row">
            <input type="checkbox" id="blur" name="effect" value="blur" onclick="toggle_blur();" />
            <label style="width:60px; display:inline-block;" for="blur"><?php echo getescapedtext ($hcms_lang['blur'][$lang], $charset, $lang); ?></label>
            <input name="blur_radius" type="text" id="blur_radius" size="2" maxlength="2" value="0"  title="<?php echo getescapedtext ($hcms_lang['radius'][$lang], $charset, $lang); ?>" />
            <label style="width:6px; display:inline-block;" for="blur_sigma">x</label>
            <input name="blur_sigma" type="text" id="blur_sigma" size="3" maxlength="1" value="0.1"  title="<?php echo getescapedtext ($hcms_lang['sigma'][$lang], $charset, $lang); ?>" />
          </div>
          <div class="row">
            <input type="checkbox" id="sharpen" name="effect" value="sharpen" onclick="toggle_sharpen();" />
            <label style="width:60px; display:inline-block;" for="sharpen"><?php echo getescapedtext ($hcms_lang['sharpen'][$lang], $charset, $lang); ?></label>
            <input name="sharpen_radius" type="text" id="sharpen_radius" size="2" maxlength="2" value="0"  title="<?php echo getescapedtext ($hcms_lang['radius'][$lang], $charset, $lang); ?>" />
            <label style="width:6px; display:inline-block;" for="sharpen_sigma">x</label>
            <input name="sharpen_sigma" type="text" id="sharpen_sigma" size="3" maxlength="1" value="0.1"  title="<?php echo getescapedtext ($hcms_lang['sigma'][$lang], $charset, $lang); ?>" />
          </div>
          <div class="row">
            <input type="checkbox" id="sketch" name="effect" value="sketch" onclick="toggle_sketch();" />
            <label style="width:60px; display:inline-block;" for="sketch"><?php echo getescapedtext ($hcms_lang['sketch'][$lang], $charset, $lang); ?></label>
            <input name="sketch_radius" type="text" id="sketch_radius" size="2" maxlength="2" value="0"  title="<?php echo getescapedtext ($hcms_lang['radius'][$lang], $charset, $lang); ?> "/>
            <label style="width:6px; display:inline-block;" for="sketch_sigma">x</label>
            <input name="sketch_sigma" type="text" id="sketch_sigma" size="2" maxlength="2" value="0" title="<?php echo getescapedtext ($hcms_lang['sigma'][$lang], $charset, $lang); ?>" />
            <input name="sketch_angle" type="text" id="sketch_angle" size="3" maxlength="3" value="0" title="<?php echo getescapedtext ($hcms_lang['angle'][$lang], $charset, $lang); ?>" />
          </div>
          <div class="row">
            <input type="checkbox" id="paint" name="effect" value="paint" onclick="toggle_paint();" />
            <label style="width:60px; display:inline-block;" for="paint"><?php echo getescapedtext ($hcms_lang['oil'][$lang], $charset, $lang); ?></label>
            <input name="paint_value" type="text" id="paint_value" size="2" maxlength="3" value="0" />
          </div>
        </div>
        <?php } ?>
        
        <div class="cell">    
          <!-- rotate -->
          <div class="row">
            <input type="checkbox" id="rotate" name="rotate" value="rotate" onclick="toggle_rotate();" />
            <strong><label for="rotate" style="width:65px; display:inline-block; vertical-align:middle;"><?php echo getescapedtext ($hcms_lang['rotate'][$lang], $charset, $lang); ?></label></strong>
            <select name="degree" id="degree" style="margin-left:20px">
              <option value="90" selected="selected" >90&deg;</option>
              <option value="180" >180&deg;</option>
              <option value="-90" title="-90&deg;">270&deg;</option>
            </select>
          </div>
          
          <?php if (getimagelib () != "GD") { ?>
          <!-- flip flop -->
          <div class="row">
            <input type="checkbox" id="chbx_flip" name="rotate" value="flip" onclick="toggle_flip();" />
            <strong><label for="chbx_flip" style="width:65px; display:inline-block; vertical-align:middle;"><?php echo getescapedtext ($hcms_lang['flip'][$lang], $charset, $lang); ?></label></strong>
            <select name="flip" id="flip" style="margin-left:20px">
              <?php 
                foreach ($available_flip as $value => $name)
                {
                ?>
                <option value="<?php echo getescapedtext ($value, $charset, $lang); ?>"><?php echo getescapedtext ($name, $charset, $lang); ?></option>
                <?php
                }
              ?>
            </select>
          </div>
          <?php } ?>      
        </div>
        
        <?php if (getimagelib () != "GD") { ?>
        <!-- brigthness / contrast -->
        <div class="cell">
          <div style="margin-left:20px" class="row">
            <strong><?php echo getescapedtext ($hcms_lang['adjust'][$lang], $charset, $lang); ?></strong>
          </div>
          <div>
            <input type="checkbox" id="chbx_brightness" name="use_brightness" value="1" onclick="toggle_brightness();" />
            <label style="width:70px; display:inline-block;" for="chbx_brightness"><?php echo getescapedtext ($hcms_lang['brightness'][$lang], $charset, $lang); ?></label>
            <input name="brightness" type="text" id="brightness" size="4" value="0" />
          </div>
          <div>
             <input type="checkbox" id="chbx_contrast" name="use_contrast" value="1" onclick="toggle_contrast();" />
            <label style="width:70px; display:inline-block;" for="chbx_contrast"><?php echo getescapedtext ($hcms_lang['contrast'][$lang], $charset, $lang); ?></label>
            <input name="contrast" type="text" id="contrast" size="4" value="0" />
          </div>
        </div>
        <?php } ?>
        
        <?php if (getimagelib () != "GD") { ?>
        <!-- colorspace -->
        <div class="cell">
          <div class="row">
            <input type="checkbox" id="chbx_colorspace" name="colorspace" value="1" onclick="toggle_colorspace();" />
            <strong><label for="chbx_colorspace"><?php echo getescapedtext ($hcms_lang['change-colorspace'][$lang], $charset, $lang); ?></label></strong>
          </div>
          <div style="margin-left:20px">
            <select name="imagecolorspace" id="colorspace">
              <?php 
                foreach ($available_colorspaces as $value => $name)
                {
                ?>
                <option value="<?php echo getescapedtext ($value, $charset, $lang); ?>"><?php echo getescapedtext ($name, $charset, $lang) ?></option>
                <?php
                }
              ?>
              </select>
          </div>
        </div>
          <?php } ?>
          
        <!-- format -->
        <div class="cell" style="width:200px;">
          <div>
            <input type="checkbox" id="renderimage" name="renderimage" value="1" />
            <strong><label for="imageformat"><?php echo getescapedtext ($hcms_lang['save-as'][$lang], $charset, $lang); ?></label></strong>
          </div>
          <div style="margin-left:20px">
            <label for="imageformat"><?php echo getescapedtext ($hcms_lang['file-type'][$lang], $charset, $lang); ?></label>
            <select name="imageformat" id="imageformat">
              <?php
              foreach ($convert_formats as $format)
              {
              ?>
              <option value="<?php echo strtolower($format); ?>"><?php echo strtoupper ($format); ?></option>
              <?php
              }
              ?>
              </select>
          </div>
        </div>
        
      </form>
      <!-- end edit image -->
      
      <?php } elseif ($is_video || $is_audio) { ?>
      
      <!-- start edit video/audio -->
      <form name="videooptions" action="" method="post">
      	<input type="hidden" id="action" name="action" value="rendermedia">
            
        <?php if (!$is_audio) { ?>
        <div class="cell" style="width:260px;">
          <!-- video screen format -->
          <div class="row">
        		<strong><?php echo getescapedtext ($hcms_lang['formats'][$lang], $charset, $lang); ?></strong>
          </row>
      		<?php foreach ($available_formats as $format => $data) { ?>
            <div class="row">
              <input type="radio" id="format_<?php echo $format; ?>" name="format" value="<?php echo $format; ?>" <?php if ($data['checked']) echo "checked=\"checked\""; ?> />
              <label for="format_<?php echo $format; ?>"><?php echo getescapedtext ($data['name'], $charset, $lang); ?></label>
            </div>
      		<?php } ?>
      	  </div>
        
          <!-- video size -->
        	<div class="row">
        		<strong><?php echo getescapedtext ($hcms_lang['video-size'][$lang], $charset, $lang); ?></strong>
          </div>
      		<?php foreach ($available_videosizes as $videosize => $data) { ?>
          <div class="row">
      			<input type="radio" id="videosize_<?php echo $videosize; ?>" name="videosize" value="<?php echo $videosize; ?>" <?php if ($data['checked']) echo "checked=\"checked\"";?> /> <label for="videosize_<?php echo $videosize; ?>"<?php if ($data['individual']) echo 'onclick="document.getElementById(\'width_'.$videosize.'\').focus();document.getElementById(\'videosize_'.$videosize.'\').checked=true;return false;"'; ?>><?php echo getescapedtext ($data['name'], $charset, $lang); ?></label>
      			<?php if ($data['individual']) { ?>
      		  <input type="text" name="width" size=4 maxlength=4 id="width_<?php echo $videosize;?>" value=""><span> x </span><input type="text" name="height" size="4" maxlength=4 id="height_<?php echo $videosize; ?>" value="" /><span> px</span>
      			<?php }	?>
      		</div>
      		<?php }	?>
      	</div>
        <?php } ?>
    
        <?php if (!$is_audio) { ?>
        <!-- sharpness / gamma / brigthness / contrast / saturation -->
        <div class="cell">
          <div class="row">
            <strong><?php echo getescapedtext ($hcms_lang['adjust'][$lang]); ?></strong>
          </div>
          <div>
            <input type="checkbox" id="chbx_sharpen" name="use_sharpen" value="1" onclick="toggle_sharpen();" />
            <label style="width:70px; display:inline-block;" for="chbx_sharpen"><?php echo getescapedtext ($hcms_lang['sharpen'][$lang], $charset, $lang); ?></label>
            <input name="sharpen" type="text" id="sharpen" size="4" value="0" />
          </div>
          <div>
            <input type="checkbox" id="chbx_gamma" name="use_gamma" value="1" onclick="toggle_gamma();" />
            <label style="width:70px; display:inline-block;" for="chbx_gamma"><?php echo getescapedtext ($hcms_lang['gamma'][$lang], $charset, $lang); ?></label>
            <input name="gamma" type="text" id="gamma" size="4" value="0" />
          </div>
          <div>
            <input type="checkbox" id="chbx_brightness" name="use_brightness" value="0" onclick="toggle_brightness();" />
            <label style="width:70px; display:inline-block;" for="chbx_brightness"><?php echo getescapedtext ($hcms_lang['brightness'][$lang], $charset, $lang); ?></label>
            <input name="brightness" type="text" id="brightness" size="4" value="0" />
          </div>
          <div>
             <input type="checkbox" id="chbx_contrast" name="use_contrast" value="1" onclick="toggle_contrast();" />
            <label style="width:70px; display:inline-block;" for="chbx_contrast"><?php echo getescapedtext ($hcms_lang['contrast'][$lang], $charset, $lang); ?></label>
            <input name="contrast" type="text" id="contrast" size="4" value="0" />
          </div>
          <div>
            <input type="checkbox" id="chbx_saturation" name="use_saturation" value="1" onclick="toggle_saturation();" />
            <label style="width:70px; display:inline-block;" for="chbx_saturation"><?php echo getescapedtext ($hcms_lang['saturation'][$lang], $charset, $lang); ?></label>
            <input name="saturation" type="text" id="saturation" size="4" value="0" />
          </div>
        </div>
        <?php }	?>
        
        <div class="cell">
          <!-- video cut -->
          <div class="row">
            <input type="checkbox" name="cut" id="cut_yes" onclick="checkCut();" value="1" />
            <strong><label for="cut_yes" onclick="checkCut();" /><?php echo ($is_audio) ? getescapedtext ($hcms_lang['audio-montage'][$lang], $charset, $lang) : getescapedtext ($hcms_lang['video-montage'][$lang], $charset, $lang); ?></label></strong>
          </div>
          <div id="cut_area" style="display:none;">
            <div class="row">
              <label for="cut_begin" style="width:70px; display:inline-block; vertical-align:middle;"><?php echo getescapedtext ($hcms_lang['start'][$lang], $charset, $lang); ?></label>
              <input type="text" name="cut_begin" id="cut_begin" value="00:00:00.00" style="width:70px; text-align:center; vertical-align:middle;" />
            </div>
            <div class="row">
              <label for="cut_end" style="width:70px; display:inline-block; vertical-align:middle;"><?php echo getescapedtext ($hcms_lang['end'][$lang], $charset, $lang); ?></label>
              <input type="text" name="cut_end" id="cut_end" value="00:00:00.00" style="width:70px; text-align:center; vertical-align:middle;" />
            </div>
          </div>
          
          <?php if (!$is_audio) { ?>
          <!-- video thumbnail -->
          <div class="row"> 
            <input type="checkbox" name="thumb" id="thumb_yes" onclick="checkThumb();" value="1" />
            <strong><label for="thumb_yes" onclick="checkThumb();" /><?php echo getescapedtext ($hcms_lang['pick-preview-image'][$lang], $charset, $lang); ?></label></strong>
          </div>
          <div id="thumb_area" style="display:none;">
              <label for="thumb_frame" style="width:70px; display:inline-block; vertical-align:middle;"><?php echo getescapedtext ($hcms_lang['frame'][$lang], $charset, $lang); ?></label>
              <input type="text" name="thumb_frame" id="thumb_frame" value="00:00:00.00" style="width:70px; text-align:center; vertical-align:middle;" />
          </div>
           
          <!-- rotate -->
          <div class="row">
            <input type="checkbox" id="rotate" name="rotate" value="rotate" onclick="toggle_rotate();" />
            <strong><label for="rotate" style="width:65px; display:inline-block; vertical-align:middle;"><?php echo getescapedtext ($hcms_lang['rotate'][$lang], $charset, $lang); ?></label></strong>
            <select name="degree" id="degree" style="margin-left:20px">
              <option value="90" selected="selected" >90&deg;</option>
              <option value="180" >180&deg;</option>
              <option value="-90" title="-90&deg;">270&deg;</option>
            </select>
          </div>
    
          <!-- vflip hflip -->
          <div class="row">
            <input type="checkbox" id="chbx_flip" name="rotate" value="flip" onclick="toggle_flip();" />
            <strong><label for="chbx_flip" style="width:65px; display:inline-block; vertical-align:middle;"><?php echo getescapedtext ($hcms_lang['flip'][$lang], $charset, $lang); ?></label></strong>
            <select name="flip" id="flip" style="margin-left:20px">
              <?php 
                foreach ($available_flip as $value => $name)
                {
                ?>
                <option value="<?php echo $value; ?>"><?php echo getescapedtext ($name, $charset, $lang); ?></option>
                <?php
                }
              ?>
            </select>
          </div>
          <?php } ?>
        </div>
        
        <?php if (!$is_audio) { ?>    
        <!-- video bitrate -->
      	<div class="cell" style="width:260px;">
          <div class="row">
      		  <strong><?php echo getescapedtext ($hcms_lang['video-quality'][$lang], $charset, $lang); ?></strong>
          </div>
      		<?php foreach ($available_bitrates as $bitrate => $data) { ?>
          <div class="row">
      			<input type="radio" id="bitrate_<?php echo $bitrate; ?>" name="bitrate" value="<?php echo $bitrate; ?>" <?php if ($data['checked']) echo "checked=\"checked\""; ?> /> <label for="bitrate_<?php echo $bitrate; ?>"><?php echo getescapedtext ($data['name'], $charset, $lang); ?></label><br />
          </div>
      		<?php } ?>
      	</div>
        
        <!-- audio bitrate -->
        <div class="cell">
          <div class="row">
      		  <strong><?php echo getescapedtext ($hcms_lang['audio-quality'][$lang], $charset, $lang); ?></strong>
          </div>
      		<?php foreach ($available_audiobitrates as $bitrate => $data) { ?>
          <div class="row">
      			<input type="radio" id="audiobitrate_<?php echo $bitrate; ?>" name="audiobitrate" value="<?php echo $bitrate; ?>" <?php if ($data['checked']) echo "checked=\"checked\""; ?> /> <label for="audiobitrate_<?php echo $bitrate; ?>"><?php echo getescapedtext ($data['name'], $charset, $lang); ?></label><br />
          </div>
      		<?php } ?>
      	</div>
        <?php } ?>
        
        <!-- save as video format -->
        <div class="cell" style="witth:200px;">
      		<input type="checkbox" id="rendervideo" name="rendervideo" value="1" />
          <strong><?php echo getescapedtext ($hcms_lang['save-as'][$lang], $charset, $lang); ?></strong><br />
      		<label for="filetype"><?php echo getescapedtext ($hcms_lang['file-type'][$lang], $charset, $lang); ?></label>
      		<select name="filetype">
            <?php
            if (!$is_audio)
            {
            ?>
            <option value="videoplayer" ><?php echo getescapedtext ($hcms_lang['for-videoplayer'][$lang], $charset, $lang); ?></option>
      			<?php
            }
            
            foreach ($available_extensions as $ext => $name)
            { 
              if (!$is_audio || is_audio (strtolower($name)))
              { 
              ?>
      				<option value="<?php echo $ext; ?>"><?php echo getescapedtext ($name, $charset, $lang); ?></option>
              <?php  
              } 
            }
            ?>
      		</select>
      	</div>
        
      </form>
      <!-- end edit video/audio -->
      <?php } ?>
      
    </div>
    
    
    <!-- message or gallery -->
    <div style="margin:42px 4px 4px 4px;">
    <?php
    if ($error != "")
    {
      echo showmessage (getescapedtext ($error, $charset, $lang));
    }
    else
    {
      // show media preview if available
      if ($mediapreview != "") echo $mediapreview."<div style=\"clear:both;\"></div>\n";
    ?>
    </div>

    <form id="reloadform" style="display:none" method="POST" action="<?php echo $mgmt_config['url_path_cms']; ?>page_multiedit.php">
      <?php
      foreach ($_POST as $pkey => $pvalue)
      {
        ?>
        <input type="hidden" name="<?php echo $pkey; ?>" value="<?php echo $pvalue; ?>" />
        <?php
      }
      ?>
    </form>
    
    <div class="hcmsWorkplaceFrame">
    <form id="sendform">
      <div style="display:block; margin-top:8px;">
        <span class="hcmsHeadline">
          <label><input type="checkbox" id="unlockform" value="1" <?php if (getsession ("temp_appendcontent") == false) echo "checked=\"checked\""; ?> onclick="unlockFormBy(this)" style="margin-left:4px;" /> <?php echo getescapedtext ($hcms_lang['only-fields-marked-with-*-hold-the-same-content-may-be-changed'][$lang], $charset, $lang); ?></label>
        </span>
        <?php
        $ids = array();
        
        foreach ($tagdata_array as $key => $tagdata)
        {
          $disabled = ($tagdata->locked == true ? 'DISABLED="DISABLED" READONLY="READONLY"' : "");
          $id = $tagdata->hypertagname.'_'.$key;
          $label = $tagdata->labelname;
          
          if ($tagdata->locked == false) $ids[] = $id;
          ?>
          <div class="hcmsFormRowLabel <?php echo $id; ?>">
            <label for="<?php echo $id; ?>"><b><?php if (trim ($label) != "") { echo $label; if ($tagdata->samecontent == true) echo " *"; } ?></b></label>
          </div>
          <div class="hcmsFormRowContent <?php echo $id; ?>">
          <?php
          if ($tagdata->type == "u") 
          {
          ?>
            <textarea id="<?php echo $id; ?>" name="<?php echo $tagdata->hypertagname; ?>[<?php echo $key; ?>]" <?php if (!empty ($disabled)) echo "class=\"hcmsPriorityMedium\""; ?> style="width:<?php echo $tagdata->width; ?>px; height:<?php echo $tagdata->height; ?>px;" <?php echo $disabled; ?>><?php if ($tagdata->locked == false) echo $tagdata->fieldvalue; ?></textarea>
          <?php 
          } 
          elseif ($tagdata->type == "k") 
          {
            $list = "";
            
            if ($disabled == "")
            {
              // extract text list
              $list .= $tagdata->list;
              
              // extract source file (file path or URL) for text list
              if ($tagdata->file != "")
              {
                $list_add = getlistelements ($tagdata->file);
                
                if ($list_add != "") $list .= ",".$list_add;
              }
  
              // extract text list
              $onlylist = strtolower ($tagdata->onlylist);
              
              // get list entries
              if ($list != "")
              {
                // replace line breaks
                $list = str_replace ("\r\n", ",", $list);
                $list = str_replace ("\n", ",", $list);
                $list = str_replace ("\r", ",", $list);
                // escape single quotes
                $list = str_replace ("'", "\\'", $list);
                // create array
                $list_array = explode (",", $list);
                // create keyword string for Javascript
                $keywords = "['".implode ("', '", $list_array)."']";
                
                $keywords_tagit = "availableTags:".$keywords.", ";
  
                if ($onlylist == "true" || $onlylist == "yes" || $onlylist == "1")
                {
                  $keywords_tagit .= "beforeTagAdded: function(event, ui) { if ($.inArray(ui.tagLabel, ".$keywords.") == -1) { return false; } }, ";
                }
              }
              else $keywords_tagit = "";
              
              $add_onload .= "
              $('#".$id."').tagit({".$keywords_tagit."singleField:true, allowSpaces:true, singleFieldDelimiter:',', singleFieldNode:$('#".$id."')});";
            }
          ?>
            <div style="display:inline-block; width:<?php echo $tagdata->width; ?>px;">
              <input type="text" id="<?php echo $id; ?>" name="<?php echo $tagdata->hypertagname; ?>[<?php echo $key; ?>]" <?php if (!empty ($disabled)) echo "class=\"hcmsPriorityMedium\""; ?> <?php echo $disabled; ?> value="<?php if ($tagdata->locked == false) echo $tagdata->fieldvalue; ?>" />
            </div>
          <?php 
          } 
          elseif ($tagdata->type == "f")
          {
            if ($tagdata->locked == false)
            {
              echo showeditor ($site, $tagdata->hypertagname, $key, $tagdata->fieldvalue, $tagdata->width, $tagdata->height, $tagdata->toolbar, $lang, $tagdata->dpi);
            }
            else
            {
              echo "<textarea id=\"".$id."\" name=\"".$tagdata->hypertagname."[".$key."]\" class=\"hcmsPriorityMedium\" style=\"width:".$tagdata->width."px; height:".$tagdata->height."px;\" ".$disabled."></textarea>";
            }
          }
          elseif ($tagdata->type == "d")
          {
            // get date format
            $format = $tagdata->format;
            if ($format == "") $format = "%Y-%m-%d";
          
            if (empty ($disabled)) $showcalendar = "onclick=\"show_cal(this, '".$id."', '".$format."');\"";
            else $showcalendar = "";
            ?>
            <input type="text" id="<?php echo $id; ?>" name="<?php echo $tagdata->hypertagname; ?>[<?php echo $key; ?>]" <?php if (!empty ($disabled)) echo "class=\"hcmsPriorityMedium\""; ?> value="<?php if ($tagdata->locked == false) echo $tagdata->fieldvalue; ?>" readonly="readonly" <?php echo $disabled; ?> />
            <?php
            if ($tagdata->locked == false) 
            {
            ?>
            <img name="datepicker" src="<?php echo getthemelocation(); ?>img/button_datepicker.png" <?php echo $showcalendar; ?> class="hcmsButtonTiny hcmsButtonSizeSquare" style="z-index:9999999;" alt="<?php echo getescapedtext ($hcms_lang['pick-a-date'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['pick-a-date'][$lang], $charset, $lang); ?>" <?php echo $disabled; ?> />
          <?php
            }
          } 
          elseif ($tagdata->type == "l")
          {
            $list = "";
            
            // extract text list
            $list .= $tagdata->list;
            
            // extract source file (file path or URL) for text list
            if ($tagdata->file != "")
            {
              $list_add = getlistelements ($tagdata->file);
              
              if ($list_add != "")
              {
                $list_add = str_replace (",", "|", $list_add);
                $list .= "|".$list_add;
              }
            }
            
            // get list entries
            $list_array = explode ("|", $list);
            ?>
            <select name="<?php echo $tagdata->hypertagname."[".$key."]"; ?>" id="<?php echo $id; ?>" <?php if (!empty ($disabled)) echo "class=\"hcmsPriorityMedium\""; ?> <?php echo $disabled; ?>>
            <?php
            foreach ($list_array as $list_entry)
            {
              $end_val = strlen ($list_entry)-1;
              
              if (($start_val = strpos ($list_entry, "{")) > 0 && strpos ($list_entry, "}") == $end_val)
              {
                $diff_val = $end_val-$start_val-1;
                $list_value = substr ($list_entry, $start_val+1, $diff_val);
                $list_text = substr ($list_entry, 0, $start_val);
              } 
              else $list_value = $list_text = $list_entry;
              ?>
              <option value="<?php echo $list_value; ?>"
              <?php if ($tagdata->locked == false && $list_value == $tagdata->fieldvalue) echo " selected"; ?>>
              <?php echo $list_text; ?>
              </option>
              <?php
            }

            ?>
            </select>
          <?php
          } 
          elseif ($tagdata->type == "c")
          {
            ?>
            <input type="checkbox" name="<?php echo $tagdata->hypertagname."[".$key."]"; ?>" id="<?php echo $id; ?>" value="<?php echo $tagdata->value; ?>" <?php if ($tagdata->locked == false && $tagdata->value == $tagdata->fieldvalue) echo "checked"; echo $disabled; ?> /> <?php echo $tagdata->value; ?>
            <?php
          }
          else
          {
            echo "UNKNOWN TYPE: ".var_export ($tagdata->type, true)." for ".var_export ($tagdata->hypertagname, true)."<br>\n";
          }
          ?>
        </div>
      <?php
      }
      ?>
      </div>
      <input type="hidden" id="objs" value="<?php echo $multiobject; ?>" />
      <input type="hidden" id="fields" value="<?php echo implode('|', $ids); ?>" />
    </form>
    </div>
    <?php
    }

  // onload event / document ready
  if ($add_onload != "") echo "
  <script language=\"JavaScript\" type=\"text/javascript\">
  $(document).ready(function() {
    // Protect images
    $('#annotation').bind('contextmenu', function(e){
        return false;
    });
    $('img').bind('contextmenu', function(e){
        return false;
    });
    
    // Execute onload events
    ".$add_onload."
    
    // Execute code from template
    ".$js_tpl_code."
  });
  </script>
  ";
    ?>
</body>

<?php include_once ("include/footer.inc.php"); ?>
</html>