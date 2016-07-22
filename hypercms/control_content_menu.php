<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");


// input parameters
$action = getrequest ("action");
$location = getrequest_esc ("location", "locationname");
$folder = getrequest_esc ("folder", "objectname");
$page = getrequest_esc ("page", "objectname");
$template = getrequest ("template");
$wf_token = getrequest_esc ("wf_token");
$token = getrequest ("token");
$convert_type = getrequest ("convert_type");
$convert_cfg = getrequest ("convert_cfg");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
// load publication configuration
if (valid_publicationname ($site)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini");

// ------------------------------ permission section --------------------------------

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

// check local permissions for DAM usage only
if ($mgmt_config[$site]['dam'] == true && $setlocalpermission['root'] != 1) killsession ($user);
// check for general root element access since local permissions are checked later
// Attention! variable page can be empty when a new object will be created
elseif (
         !checkpublicationpermission ($site) || 
         (!valid_objectname ($page) && ($setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1)) || 
         !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($cat)
       ) killsession ($user);
       
// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";
$usedby = "";
$is_favorite = false;
$wf_id = "";
$wf_role = "";
$contentfile = "";
$application = "";

// get workflow id and release
if ($wf_token != "")
{
  $wf_string = hcms_decrypt ($wf_token);
  if ($wf_string != "" && strpos ($wf_string, ":") > 0) list ($wf_id, $wf_role) = explode (":", $wf_string);
}

// load object file and get container and media file
if ($action != "page_create")
{
  $objectdata = loadfile ($location, $page);
  $contentfile = getfilename ($objectdata, "content");
  $media = getfilename ($objectdata, "media");
  $template = getfilename ($objectdata, "template");

  if ($template != "")
  {
    $result = loadtemplate ($site, $template);
    
    if (is_array ($result))
    {
      $bufferdata = getcontent ($result['content'], "<application>");
      $application = $bufferdata[0];
    }
  }
}

// get object ID (requires for favorites)
if ($folder != "") $object_id = rdbms_getobject_id ($location_esc.$folder);
else $object_id = rdbms_getobject_id ($location_esc.$page);

// define message if object is checked out by another user
if (!empty ($contentfile))
{
  $usedby_array = getcontainername ($contentfile);
  
  if (is_array ($usedby_array) && !empty ($usedby_array['user'])) $usedby = $usedby_array['user'];
  else $usedby = "";

  if ($usedby != "" && $usedby != $user) $show = getescapedtext ($hcms_lang['object-is-checked-out-by-user'][$lang])." '".$usedby."'";
  else $show = "";
}

// execute action
if ($action != "" && checktoken ($token, $user))
{
  // create object (token only holds location) 
  if ($setlocalpermission['create'] == 1 && $action == "page_create") 
  {
    $result = createobject ($site, $location, $page, $template, $user);
    
    $add_onload = $result['add_onload'];
    $show = $result['message'];  
    $page = $result['object'];
    $pagename = $result['name'];
  }
  // add to favorites
  elseif ($setlocalpermission['root'] == 1 && $action == "page_favorite_add") 
  {
    $is_favorite = createfavorite ("", "", "", $object_id, $user);

    $show = "";
  }
  // delete from favorites
  elseif ($setlocalpermission['root'] == 1 && $action == "page_favorite_delete") 
  {
    $result = deletefavorite ("", "", "", $object_id, $user);
    
    if ($result) $is_favorite = false;

    $show = "";
  } 
  // lock object
  elseif ($setlocalpermission['root'] == 1 && $action == "page_lock") 
  {
    $result = lockobject ($site, $location, $page, $user);

    $add_onload = $result['add_onload'];
    $show = "";  
    $page = $result['object'];
    $usedby = $result['usedby'];
    $pagename = $result['name'];
  }  
  // unlock object
  elseif ($setlocalpermission['root'] == 1 && $action == "page_unlock") 
  {
    if ($usedby == $user)
    {
      $result = unlockobject ($site, $location, $page, $user);
    }
    elseif ($usedby != $user && checkrootpermission ('user'))
    {
      $result = unlockobject ($site, $location, $page, $usedby);
    }
    
    $add_onload = $result['add_onload'];
    $show = "";  
    $page = $result['object'];
    $usedby = $result['usedby'];
    $pagename = $result['name'];
  }
}

// check if in favorites
$favs_id_array = getfavorites ($user, "id");

if (is_array ($favs_id_array) && in_array ($object_id, $favs_id_array))
{
  $is_favorite = true;
}

// get file info
if ($page != "") 
{
  // correct object file name
  $page = correctfile ($location, $page, $user);      
  // get file info
  $file_info = getfileinfo ($site, $location.$page, $cat);
  $filetype = $file_info['type'];
  $pagename = $file_info['name'];
}
else
{
  $file_info = Null;
  $filetype = "";
  $pagename = "";
}

// define object category name
if ($filetype == "Page")
{
  $pagecomp = getescapedtext ($hcms_lang['page'][$lang]);
}
elseif ($filetype == "Component")
{
  $pagecomp = getescapedtext ($hcms_lang['component'][$lang]);
}
elseif ($page == ".folder")
{
  $pagecomp = getescapedtext ($hcms_lang['folder'][$lang]);
}
else
{
  $pagecomp = getescapedtext ($hcms_lang['asset'][$lang]);
}

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<style>
a {behavior: url(#default#AnchorClick);}
</style>
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
<!--
var locklayer = false;

function submitToWindow (url, action, windowname, features, width, height)
{
  if (features == undefined) features = 'scrollbars=no,resizable=no';
  if (width == undefined) width = 400;
  if (height == undefined) height = 180;
  if (windowname == '') windowname = Math.floor(Math.random()*9999999);
  
  hcms_openWindow('', windowname, features, width, height);
  
  var form = /*parent.frames['objframe'].*/document.forms['pagemenu_object'];
  
  form.attributes['action'].value = url;
  form.elements['action'].value = action;
  form.elements['site'].value = '<?php echo $site; ?>';
  form.elements['cat'].value = '<?php echo $cat; ?>';
  form.elements['location'].value = '<?php echo $location_esc; ?>';
  form.elements['page'].value = '<?php echo $page; ?>';
  form.elements['pagename'].value = '<?php echo $pagename; ?>';
  form.elements['folder'].value = '<?php echo $folder; ?>';
  form.elements['force'].value = 'start';
  form.elements['token'].value = '<?php echo $token_new; ?>';
  form.target = windowname;
  form.submit();
}

function submitToSelf (action)
{
  var form = document.forms['download'];
  
  form.elements['action'].value = action;
  form.elements['location'].value = '<?php echo $location_esc; ?>';
  form.elements['page'].value = '<?php echo $page; ?>';
  form.elements['wf_token'].value = '<?php echo $wf_token; ?>';
  form.submit();
}

function checkForm_chars(text, exclude_chars)
{
  <?php if ($mgmt_config[$site]['specialchr_disable']) { ?>
  exclude_chars = exclude_chars.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
  
  var expr = new RegExp ("[^a-zA-Z0-9" + exclude_chars + "]", "g");
  <?php } else { ?>
  var expr = new RegExp ('[,;/\\\\~`!@#$%^&:*?<>{}=|]', "g");
  <?php } ?>
  var separator = ', ';
  var found = text.match(expr); 
  
  if (found)
  {
    var addText = '';
    
    for(var i = 0; i < found.length; i++)
    {
      addText += found[i]+separator;
    }
    
    addText = addText.substr(0, addText.length-separator.length);
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters'][$lang]); ?>: ") + addText);
    return false;
  }
  else
  {
    return true;
  }
}

function checkForm_page_create()
{
  var form = document.forms['page_create'];
  
  if (form.elements['page'].value.trim() == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['a-name-is-required'][$lang]); ?>"));
    form.elements['page'].focus();
    return false;
  }
  
  if (form.elements['template'].value == "empty.php")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-select-a-template'][$lang]); ?>"));
    form.elements['template'].focus();
    return false;  
  }
  
  if (!checkForm_chars(form.elements['page'].value, ".-_"))
  {
    form.elements['page'].focus();
    return false;
  }
  
  form.submit();
  return true;
}

function docConvert (type)
{
  if (type != "")
  {
    var form = document.forms['download'];
      
    form.elements['convert_type'].value = type;
    
    submitToSelf ('download');
    
    return true;
  }
  else return false; 
}

function imgConvert (type, config)
{
  if (type != "")
  {
    var form = document.forms['download'];
      
    form.elements['convert_type'].value = type;
    form.elements['convert_cfg'].value = config;
    
    submitToSelf ('download');
    
    return true;
  }
  else return false; 
}

function escapevalue (value)
{
  if (value != "")
  {
    return encodeURIComponent (value);
  }
  else return "";
}

<?php echo $add_onload; ?>
//-->
</script>
</head>

<body class="hcmsWorkplaceControlWallpaper">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:20px;"); ?>

<?php if (!$is_mobile) { ?>
<div style="position:absolute; right:0; top:0; margin:0; padding:0;">
  <img onclick="parent.minControlFrame()" class="hcmsButtonTinyBlank" style="width:18px; height:18px;" alt="<?php echo getescapedtext ($hcms_lang['collapse'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['collapse'][$lang]); ?>" src="<?php echo getthemelocation(); ?>img/button_arrow_up.png" /><br />
  <img onclick="parent.maxControlFrame();" class="hcmsButtonTinyBlank" style="width:18px; height:18px;" alt="<?php echo getescapedtext ($hcms_lang['expand'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['expand'][$lang]); ?>" src="<?php echo getthemelocation(); ?>img/button_arrow_down.png" />
</div>
<?php } ?>

<?php
// define location name  
$location_name = getlocationname ($site, $location, $cat, "path");
  
if ($page == ".folder")
{
  $pagename = getobject ($location_name);      
  $location_name = getlocation ($location_name);
}

// define object name
if ($page != "")
{
  $item = $pagecomp.":";
  $object_name = $pagename;
}  
// no object declared
else
{
  $item = "&nbsp;";
  $object_name = "&nbsp;";
}
?>

<div class="hcmsLocationBar">
  <?php if (!$is_mobile) { ?>
  <table border=0 cellspacing=0 cellpadding=0>
    <tr>
      <?php
      // location
      if ($cat == "page" || $cat == "comp")
      {
        echo "    <td class=\"hcmsHeadline\" nowrap=\"nowrap\">".getescapedtext ($hcms_lang['location'][$lang]).": </td>
      <td class=\"hcmsHeadlineTiny\" nowrap=\"nowrap\">".$location_name."</td>\n";
      }
      else 
      {
        echo "    <td class=\"hcmsHeadline\" nowrap=\"nowrap\">".getescapedtext ($hcms_lang['location'][$lang]).": </td>
      <td class=\"hcmsHeadlineTiny\" nowrap=\"nowrap\">".$pagecomp."</td>\n";    
      }
      ?>
    </tr>
    <tr>
      <?php
      // object
      echo "    <td class=\"hcmsHeadline\" nowrap=\"nowrap\">".$item."</td>
      <td class=\"hcmsHeadlineTiny\" nowrap=\"nowrap\">".$object_name."</td>\n";
      ?>
    </tr>
  </table>
  <?php } else { ?>
  <span class="hcmsHeadlineTiny" style="display:block;"><?php echo $location_name.$object_name; ?></span>
  <?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar">
  <div class="hcmsToolbarBlock">
    <?php if (empty ($media) && $page != ".folder") { ?>
    <img class="hcmsButton hcmsButtonSizeSquare" onClick="history.go(-2);" name="pic_obj_back" src="<?php echo getthemelocation(); ?>img/button_history_back.gif" alt="<?php echo getescapedtext ($hcms_lang['back'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['back'][$lang]); ?>" />
    <img class="hcmsButton hcmsButtonSizeSquare" onClick="history.go(2);" name="pic_obj_forward" src="<?php echo getthemelocation(); ?>img/button_history_forward.gif" alt="<?php echo getescapedtext ($hcms_lang['forward'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['forward'][$lang]); ?>" />
    <?php } else { ?>
    <img class="hcmsButtonOff hcmsButtonSizeSquare" name="pic_obj_back" src="<?php echo getthemelocation(); ?>img/button_history_back.gif" />
    <img class="hcmsButtonOff hcmsButtonSizeSquare" name="pic_obj_forward" src="<?php echo getthemelocation(); ?>img/button_history_forward.gif" />    
    <?php } ?>
    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    // Preview Button
    if ($page != "" && $page != ".folder" && $cat != "" && $setlocalpermission['root'] == 1 && empty ($media))
    {
      echo "<img onClick=\"hcms_openWindow('page_preview.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."','preview','location=no,scrollbars=yes,resizable=yes,titlebar=no', 800, 600);\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_preview\" src=\"".getthemelocation()."img/button_file_preview.gif\" alt=\"".getescapedtext ($hcms_lang['preview'][$lang])."\" title=\"".getescapedtext ($hcms_lang['preview'][$lang])."\" />\n";
    }
    else echo "<img src=\"".getthemelocation()."img/button_file_preview.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";

    // LiveView Button
    if ($file_info['published'] == true && $page != "" && $page != ".folder" && $setlocalpermission['root'] == 1 && $cat == "page" && empty ($media))
    {
      $url_location = str_ireplace ($mgmt_config[$site]['abs_path_page'], $publ_config['url_publ_page'], $location).$page;
      echo "<img onClick=\"hcms_openWindow('".$url_location."', 'preview', 'location=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,titlebar=no', 800, 600);\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_liveview\" src=\"".getthemelocation()."img/button_file_liveview.gif\" alt=\"".getescapedtext ($hcms_lang['view-live'][$lang])."\" title=\"".getescapedtext ($hcms_lang['view-live'][$lang])."\" />\n";
    }
    else echo "<img src=\"".getthemelocation()."img/button_file_liveview.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    
    // Download/Convert Button (also for folders) 
    // get media file extension
    if (!empty ($media))
    {
      $media_info = getfileinfo ($site, $media, $cat);
    }
    else $media_info = Null;
    
    $doc_rendering = false;
    $img_rendering = false;
    
    foreach ($mgmt_docpreview as $docpreview_ext => $docpreview)
    {
      // check file extension
      if (isset ($media_info['ext']) && substr_count ($docpreview_ext.".", $media_info['ext'].".") > 0 ) $doc_rendering = true;  
      else $doc_rendering = false;
    }
    
    $doc_rendering = $doc_rendering && is_array ($mgmt_docconvert) && array_key_exists ($media_info['ext'], $mgmt_docconvert);
    
    foreach ($mgmt_imagepreview as $imgpreview_ext => $imgpreview)
    {
      // check file extension
      if (substr_count (strtolower ($imgpreview_ext).".", $media_info['ext'].".") > 0 )
      {
        // check if there are more options for providing the image in other formats
        if (is_array ($mgmt_imageoptions) && !empty ($mgmt_imageoptions))
        {	
          foreach ($mgmt_imageoptions as $config_fileext => $config_array) 
          {
            foreach ($config_array as $config_name => $value) 
            {
              if ($config_name != "thumbnail" && $config_name != "original") 
              {
                $img_rendering = true;
                break 3;
              }
            }	
          }
        }
      }      
    }
    
    // rendering options
    $perm_rendering = $setlocalpermission['root'] == 1 && $setlocalpermission['download'] == 1;
    $lock_rendering = ($usedby == "" || $usedby == $user);
    $dropbox_rendering = (is_array ($mgmt_config) && array_key_exists ("dropbox_appkey", $mgmt_config) && !empty ($mgmt_config['dropbox_appkey']));
    
    if (!$is_mobile) $left = "135px";
    else $left = "140px";
    
    if ($perm_rendering && $lock_rendering && $page != "" && !empty ($media) && ($doc_rendering || $img_rendering || $dropbox_rendering))
    {
      echo "
        <div class=\"hcmsButton hcmsButtonSizeWide\" onClick=\"hcms_switchSelector('select_obj_convert');\"><img src=\"".getthemelocation()."img/button_file_download.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" id=\"pic_obj_convert\" name=\"pic_obj_convert\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" /><img src=\"".getthemelocation()."img/pointer_select.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeNarrow\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" /></div>
        <div id=\"select_obj_convert\" class=\"hcmsSelector\" style=\"position:absolute; top:5px; left:".$left."; visibility:hidden; z-index:999; max-height:80px; overflow:auto; overflow-x:hidden; overflow-y:auto; white-space:nowrap;\">\n";
      
      // original file
      if (empty ($downloadformats) || (!is_document ($media_info['ext']) && !is_image ($media_info['ext'])) || (is_document ($media_info['ext']) && !empty ($downloadformats['document']['original'])) || (is_image ($media_info['ext']) && !empty ($downloadformats['image']['original'])))
      {
        echo "
        <div class=\"hcmsSelectorItem\" onclick=\"submitToSelf('download');\"><img src=\"".getthemelocation()."img/".$media_info['icon']."\" style=\"border:0; margin:0; padding:1px;\" align=\"absmiddle\" />".getescapedtext ($hcms_lang['original'][$lang])."&nbsp;</div>\n";
      }

      // document download options
      if ($doc_rendering)
      {
        foreach ($mgmt_docoptions as $ext => $value)
        {
          if ($ext != "" && $value != "")
          {
            $ext_array = explode (".", trim ($ext, "."));
            $doc_type = $ext_array[0];          
            $doc_info = getfileinfo ($site, "file".$ext, "comp");

            if ((empty ($downloadformats) || !empty ($downloadformats['document'][$doc_type])) && in_array ($ext, $mgmt_docconvert[$media_info['ext']]))
            {
              echo "
        <div class=\"hcmsSelectorItem\" onclick=\"docConvert ('".$doc_type."');\"><img src=\"".getthemelocation()."img/".$doc_info['icon']."\" style=\"border:0; margin:0; padding:1px;\" align=\"absmiddle\" />".$doc_info['type']." (".strtoupper($doc_type).")&nbsp;</div>\n";
            }
          }
        }
      }
      
      // image download options
      if ($img_rendering)
      {
        foreach ($mgmt_imageoptions as $ext => $config_array) 
        {
          if (is_array ($config_array)) 
          {
            $ext_array = explode (".", trim ($ext, "."));
            $image_type = $ext_array[0];
            $img_info = getfileinfo ($site, $media_info['filename'].".".$image_type, $cat);
            
            foreach ($config_array as $config_name => $config_parameter) 
            {
              if ((empty ($downloadformats) || !empty ($downloadformats['image'][$image_type][$config_name])) && $config_name != "thumbnail" && $config_name != "original") 
              {
                echo "
         <div class=\"hcmsSelectorItem\" onclick=\"imgConvert ('".$image_type."', '".$config_name."');\"><img src=\"".getthemelocation()."img/".$img_info['icon']."\" style=\"border:0; margin:0; padding:1px;\" align=\"absmiddle\" />".strtoupper($image_type)." ".$config_name."&nbsp;</div>\n";
              }
            }
          }
        }
      }
      
      //save to dropbox
      if ($dropbox_rendering)
      {
        echo "
        <div class=\"hcmsSelectorItem\" onclick=\"submitToWindow('popup_save_dropbox.php', 'Save to Dropbox', '', 'status=yes,scrollbars=yes,resizable=yes,width=600,height=400', '600', '400');\"><img src=\"".getthemelocation()."img/file_dropbox.png\" style=\"border:0; margin:0; padding:1px;\" align=\"absmiddle\" />".getescapedtext ($hcms_lang['dropbox'][$lang])."&nbsp;</div>\n";
      }
      
      echo "
      </div>\n";
    } 
    // folder/file download without options
    elseif ($perm_rendering && $lock_rendering && (!empty ($media) || $page == ".folder") && $page != "")
    {
      echo "<div class=\"hcmsButton hcmsButtonSizeWide\" onClick=\"submitToSelf('download');\">".
      "<img class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" name=\"pic_obj_liveview\" src=\"".getthemelocation()."img/button_file_download.gif\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" /><img src=\"".getthemelocation()."img/pointer_select.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeNarrow\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" /></div>\n";
    }
    else
    {
      echo "<div class=\"hcmsButtonOff hcmsButtonSizeWide\"><img src=\"".getthemelocation()."img/button_file_download.gif\" class=\"hcmsButtonSizeSquare\" /><img src=\"".getthemelocation()."img/pointer_select.gif\" class=\"hcmsButtonSizeNarrow\" /></div>\n";
    }
    ?>
            
    <?php    
    // SendMail Button
    if ($page != "" && $mgmt_config[$site]['sendmail'] && $setlocalpermission['root'] == 1 && $setlocalpermission['sendlink'] == 1 && $mgmt_config['db_connect_rdbms'] != "")
    {
        echo "<img onClick=\"submitToWindow('user_sendlink.php', '', 'sendlink', 'scrollbars=yes,resizable=no','600','680');\" ".
                   "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_preview\" ".
                   "src=\"".getthemelocation()."img/button_user_sendlink.gif\" ".
                   "alt=\"".getescapedtext ($hcms_lang['send-mail-link'][$lang])."\" title=\"".getescapedtext ($hcms_lang['send-mail-link'][$lang])."\" />\n";
    }
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_user_sendlink.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    // Upload Button
    if ($html5file) $popup_upload = "popup_upload_html.php";
    else $popup_upload = "popup_upload_swf.php";
    
    if (($usedby == "" || $usedby == $user) && $cat != "page" && !empty ($media) && $application != "generator" && $page != ".folder" && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1)
    {
      echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_openWindow('".$popup_upload."?uploadmode=single&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."','','location=no,status=yes,scrollbars=no,resizable=yes,titlebar=no', 800 , 600);\" name=\"pic_obj_upload\" src=\"".getthemelocation()."img/button_file_upload.gif\" alt=\"".getescapedtext ($hcms_lang['upload-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['upload-file'][$lang])."\" />\n";
    }
    else echo "<img src=\"".getthemelocation()."img/button_file_upload.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    ?>    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    // Favorite Button
    if ($page != "" && checkrootpermission ('desktopfavorites') && $setlocalpermission['root'] == 1)
    {
      if (!$is_favorite)
      {
        echo "<img onClick=\"if (locklayer == false) location='control_content_menu.php?action=page_favorite_add&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."';\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_lock\" src=\"".getthemelocation()."img/button_favorites_delete.gif\" alt=\"".getescapedtext ($hcms_lang['add-to-favorites'][$lang])."\" title=\"".getescapedtext ($hcms_lang['add-to-favorites'][$lang])."\" />\n";
      }
      else echo "<img onClick=\"if (locklayer == false) location='control_content_menu.php?action=page_favorite_delete&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."';\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_unlock\" src=\"".getthemelocation()."img/button_favorites_new.gif\" alt=\"".getescapedtext ($hcms_lang['delete-favorite'][$lang])."\" title=\"".getescapedtext ($hcms_lang['delete-favorite'][$lang])."\" />\n";
    }
    else echo "<img src=\"".getthemelocation()."img/button_favorites_delete.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    ?>    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    // Checked out Button
    if ($page != "" && $wf_role == 5 && checkrootpermission ('desktopcheckedout') && (($media == "" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1) || (!empty ($media) && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1)))
    {
      if ($usedby == "")
      {
        echo "<img onClick=\"if (locklayer == false) location='control_content_menu.php?action=page_lock&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."';\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_lock\" src=\"".getthemelocation()."img/button_file_unlock.gif\" alt=\"".getescapedtext ($hcms_lang['check-out'][$lang])."\" title=\"".getescapedtext ($hcms_lang['check-out'][$lang])."\" />\n";
      }
      elseif ($usedby == $user)
      {
        echo "<img onClick=\"if (locklayer == false) location='control_content_menu.php?action=page_unlock&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."';\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_unlock\" src=\"".getthemelocation()."img/button_file_lock.gif\" alt=\"".getescapedtext ($hcms_lang['check-in'][$lang])."\" title=\"".getescapedtext ($hcms_lang['check-in'][$lang])."\" />\n";
      }
      else echo "<img src=\"".getthemelocation()."img/button_file_lock.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\">\n";
    }
    ?>    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if (is_file ($mgmt_config['abs_path_cms']."workflow/frameset_workflow.php"))
    {
      // Workflow Buttons
      if ($usedby == "" && ($wf_role >= 1 && $wf_role <= 4) && $page != "" && $setlocalpermission['root'] == 1)
      {
        echo "<img onClick=\"if (locklayer == false) hcms_openWindow('popup_message.php?action=accept&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."','popup_workflow','location=no,scrollbars=no,resizable=no,titlebar=no', 400 , 200);\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_accept\" src=\"".getthemelocation()."img/button_workflow_accept.gif\" alt=\"".getescapedtext ($hcms_lang['accept-and-forward'][$lang])."\" title=\"".getescapedtext ($hcms_lang['accept-and-forward'][$lang])."\" />\n";
      }
      else echo "<img src=\"".getthemelocation()."img/button_workflow_accept.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
  
      if ($usedby == "" && ($wf_role >= 1 && $wf_role <= 4 && $wf_id != "u.1") && $page != "" && $setlocalpermission['root'] == 1)
      {
        echo "<img onClick=\"if (locklayer == false) hcms_openWindow('popup_message.php?action=reject&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."','popup_workflow','location=no,scrollbars=no,resizable=no,titlebar=no', 400, 200);\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_reject\" src=\"".getthemelocation()."img/button_workflow_reject.gif\" alt=\"".getescapedtext ($hcms_lang['reject-and-send-back'][$lang])."\" title=\"".getescapedtext ($hcms_lang['reject-and-send-back'][$lang])."\" />\n";
      }
      else echo "<img src=\"".getthemelocation()."img/button_workflow_reject.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_workflow_accept.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
      echo "<img src=\"".getthemelocation()."img/button_workflow_reject.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>    
  </div>
  <div class="hcmsToolbarBlock">  
    <?php
    // Un/Publish	Buttons
    if ($page != "")
    {
      // Publish
      if (($usedby == "" || $usedby == $user) && $filetype != "" && $wf_role >= 3 && $setlocalpermission['root'] == 1 && $setlocalpermission['publish'] == 1)
      {
        echo "<img onClick=\"if (locklayer == false) hcms_openWindow('";
        if ($mgmt_config['db_connect_rdbms'] != "") echo "popup_publish.php";
        else echo "popup_action.php";
        echo "?action=publish&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."', '".$object_id."', 'location=no,scrollbars=no,resizable=no,titlebar=no', 400, 370);\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_publish\" src=\"".getthemelocation()."img/button_file_publish.gif\" alt=\"".getescapedtext ($hcms_lang['publish'][$lang])."\" title=\"".getescapedtext ($hcms_lang['publish'][$lang])."\" />\n";
      }
      else echo "<img src=\"".getthemelocation()."img/button_file_publish.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
  
      // Unpublish
      if (($usedby == "" || $usedby == $user) && $filetype != "" && $wf_role >= 3 && $setlocalpermission['root'] == 1 && $setlocalpermission['publish'] == 1)
      {
        echo "<img onClick=\"if (locklayer == false) hcms_openWindow('";
        if ($mgmt_config['db_connect_rdbms'] != "") echo "popup_publish.php";
        else echo "popup_action.php";        
        echo "?action=unpublish&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."', '".$object_id."', 'location=no,scrollbars=no,resizable=no,titlebar=no', 400, 370);\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_unpublish\" src=\"".getthemelocation()."img/button_file_unpublish.gif\" alt=\"".getescapedtext ($hcms_lang['unpublish'][$lang])."\" title=\"".getescapedtext ($hcms_lang['unpublish'][$lang])."\" />\n";
      }
      else echo "<img src=\"".getthemelocation()."img/button_file_unpublish.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_file_publish.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
      echo "<img src=\"".getthemelocation()."img/button_file_unpublish.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>    
  </div>
  <div class="hcmsToolbarBlock">    
    <?php
    // Reload button
    echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"if (locklayer == false) parent.frames['objFrame'].location.reload();\" name=\"pic_obj_refresh\" src=\"".getthemelocation()."img/button_view_refresh.gif\" alt=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" title=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" />\n";
    ?>    
  </div>
  <div class="hcmsToolbarBlock">    
    <?php
    if (!$is_mobile && file_exists ($mgmt_config['abs_path_cms']."help/usersguide_".$hcms_lang_shortcut[$lang].".pdf") && $setlocalpermission['root'] == 1)
    {
      echo "<a href=# onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('pic_obj_help','','".getthemelocation()."img/button_help_over.gif',1)\" onClick=\"hcms_openWindow('help/usersguide_".$hcms_lang_shortcut[$lang].".pdf', 'help', 'location=no,scrollbars=no,resizable=yes,titlebar=no', 800, 600);\"><img name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" /></a>\n";
    }
    elseif (!$is_mobile && file_exists ($mgmt_config['abs_path_cms']."help/usersguide_en.pdf") && $setlocalpermission['root'] == 1)
    {
      echo "<a href=# onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('pic_obj_help','','".getthemelocation()."img/button_help_over.gif',1)\" onClick=\"hcms_openWindow('help/usersguide_en.pdf', 'help', 'location=no,scrollbars=no,resizable=yes,titlebar=no', 800, 600);\"><img name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" /></a>\n";
    }
    ?>
  </div>
</div>

<?php
echo showmessage ($show, 650, 60, $lang, "position:fixed; left:15px; top:15px; ");
?>

<!-- form used by send_link function -->
<form target="_blank" method="post" action="" name="pagemenu_object">
  <input type="hidden" name="action" value="">
  <input type="hidden" name="force" value="start">
  <input type="hidden" name="site" value="<?php echo $site; ?>">
  <input type="hidden" name="cat" value="<?php echo $cat; ?>">
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>">
  <input type="hidden" name="page" value="<?php echo $cat; ?>">
  <input type="hidden" name="pagename" value="<?php echo $pagename; ?>">
  <input type="hidden" name="folder" value="<?php echo $folder; ?>">
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" >
</form>

<div id="objcreateLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:<?php if ($page != "") echo "hidden"; else echo "visible"; ?>">
<form name="page_create" action="" method="post" onsubmit="return checkForm_page_create();">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="wf_token" value="<?php echo $wf_token; ?>" />
  <input type="hidden" name="wf_role" value="<?php echo $wf_role; ?>" />
  <input type="hidden" name="contentfile" value="<?php echo $contentfile; ?>" />
  <input type="hidden" name="action" value="page_create" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>">
  
  <table border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td>
        <?php echo getescapedtext ($hcms_lang['new-object'][$lang]." (".$hcms_lang['name-without-ext'][$lang].")"); ?>:
      </td>
      <td>
        <input type="text" name="page" maxlength="<?php if (!is_int ($mgmt_config['max_digits_filename'])) echo $mgmt_config['max_digits_filename']; else echo "200"; ?>" style="width:220px;" />
        <img name="Button3" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_page_create();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button3','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" />
      </td>
    </tr>
    <tr>
      <td>
        <?php echo getescapedtext ($hcms_lang['template-auto-preview'][$lang]); ?>:
      </td>
      <td>
        <select name="template" onChange="hcms_jumpMenu('parent.frames[\'objFrame\']',this,0)" style="width:220px;">
          <option value="empty.php">--- <?php echo getescapedtext ($hcms_lang['select-template'][$lang]); ?> ---</option>
          <?php
          $template_array = gettemplates ($site, $cat);
          
          if (is_array ($template_array))
          {
            foreach ($template_array as $value)
            {
              if ($value != "")
              {
                if ($cat == "page" || strpos ($value, ".page.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".page.tpl"));
                elseif ($cat == "comp" || strpos ($value, ".comp.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".comp.tpl"));
              
                echo "<option value=\"template_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&template=".url_encode($value)."\">".$tpl_name."</option>\n";
              }
            }
          }          
          ?>
        </select>
      </td>
    </tr>
  </table>
</form>
</div>

<?php
if ($page != "")
{
  echo "<div id=\"Layer_tab\" class=\"hcmsTabContainer\" style=\"position:absolute; z-index:10; visibility:visible; left:0px; top:77px\">
    <table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" style=\"z-index:1;\">
      <tr>
        <td style=\"width:3px;\"><img src=\"".getthemelocation()."img/backgrd_tabs_spacer.gif\" style=\"width:3px; height:3px; border:0;\" /></td>
        <td align=\"left\" valign=\"top\" class=\"hcmsTab\">&nbsp;
          <a href=\"page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&ctrlreload=no\" target=\"objFrame\" onClick=\"hcms_showHideLayers('Layer_tab1','','show','Layer_tab2','','hide','Layer_tab3','','hide','Layer_tab4','','hide')\" title=\"".$pagecomp."\">".$pagecomp."</a>
        </td>
        <td style=\"width:3px;\"><img src=\"".getthemelocation()."img/backgrd_tabs_spacer.gif\" style=\"width:3px; height:3px; border:0;\" /></td>
        <td align=\"left\" valign=\"top\" class=\"hcmsTab\">&nbsp;\n";
          if (($usedby == "" || $usedby == $user) && ($wf_role >= 4 || $wf_role == 2) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1) 
            echo "<a href=\"frameset_template_change.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&template=".url_encode($template)."&wf_token=".url_encode($wf_token)."\" target=\"objFrame\" onClick=\"hcms_showHideLayers('Layer_tab1','','hide','Layer_tab2','','show','Layer_tab3','','hide','Layer_tab4','','hide')\" title=\"".getescapedtext ($hcms_lang['template'][$lang])."\">".getescapedtext ($hcms_lang['template'][$lang])."</a>";
          else echo "<b class=\"hcmsButtonTinyOff\">".getescapedtext ($hcms_lang['template'][$lang])."</b>";
        echo "</td>
        <td style=\"width:3px;\"><img src=\"".getthemelocation()."img/backgrd_tabs_spacer.gif\" style=\"width:3px; height:3px; border:0;\" /></td>
        <td align=\"left\" valign=\"top\" class=\"hcmsTab\">&nbsp;\n";
          if (($usedby == "" || $usedby == $user) && ($wf_role >= 4 || $wf_role == 2) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
            echo "<a href=\"version_content.php?site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."\" target=\"objFrame\" onClick=\"hcms_showHideLayers('Layer_tab1','','hide','Layer_tab2','','hide','Layer_tab3','','show','Layer_tab4','','hide')\" title=\"".getescapedtext ($hcms_lang['version'][$lang])."\">".getescapedtext ($hcms_lang['version'][$lang])."</a>";
          else echo "<b class=\"hcmsButtonTinyOff\">".getescapedtext ($hcms_lang['version'][$lang])."</b>";
        echo "</td>
        <td style=\"width:3px;\"><img src=\"".getthemelocation()."img/backgrd_tabs_spacer.gif\" style=\"width:3px; height:3px; border:0;\" /></td>
        <td align=\"left\" valign=\"top\" class=\"hcmsTab\">&nbsp;\n";
          if ($wf_role >= 1 && $setlocalpermission['root'] == 1) 
            echo "<a href=\"page_info.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."\" target=\"objFrame\" onClick=\"hcms_showHideLayers('Layer_tab1','','hide','Layer_tab2','','hide','Layer_tab3','','hide','Layer_tab4','','show')\" title=\"".getescapedtext ($hcms_lang['information'][$lang])."\">".getescapedtext ($hcms_lang['information'][$lang])."</a>";
          else echo "<b class=\"hcmsButtonTinyOff\">".getescapedtext ($hcms_lang['information'][$lang])."</b>";
        echo "</td>
      </tr>
    </table>
  </div>\n";
   
  echo "<div id=\"Layer_tab1\" class=\"hcmsWorkplaceGeneric\" style=\"position:absolute; width:118px; height:2px; z-index:20; visibility:visible; left:4px; top:99px;\"> </div>\n";
  echo "<div id=\"Layer_tab2\" class=\"hcmsWorkplaceGeneric\" style=\"position:absolute; width:118px; height:2px; z-index:20; visibility:hidden; left:127px; top:99px;\"> </div>\n";
  echo "<div id=\"Layer_tab3\" class=\"hcmsWorkplaceGeneric\" style=\"position:absolute; width:118px; height:2px; z-index:20; visibility:hidden; left:250px; top:99px;\"> </div>\n";
  echo "<div id=\"Layer_tab4\" class=\"hcmsWorkplaceGeneric\" style=\"position:absolute; width:118px; height:2px; z-index:20; visibility:hidden; left:373px; top:99px;\"> </div>\n"; 
}
?>

<div id="downloadLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "80%"; else echo "650px"; ?>; height:60px; z-index:5; left:15px; top:10px; visibility:<?php echo ($action == 'download' ? 'visible' : 'hidden'); ?>;" >
<form name="download" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
  <input type="hidden" name="action" value="download" />
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="page" value="<?php echo $page; ?>" />
  <input type="hidden" name="wf_token" value="<?php echo $wf_token; ?>" />
  <input type="hidden" name="convert_type" value="" />
  <input type="hidden" name="convert_cfg" value="" />

  <table width="100%" height="60" border=0 cellspacing=0 cellpadding=3 class="hcmsMessage">
    <tr>
      <td align="left" valign="middle">
        <div style="width:100%; height:100%; z-index:10; overflow:auto;">
          <?php
          // iPhone download
          if ($action == "download" && $is_iphone)
          { 
            $downloadlink = createmultidownloadlink ($site, $multiobject, $media, $location.$folder, $pagename, $user, $convert_type, $convert_cfg);
            
            echo "<a href=\"".$downloadlink."\" class=\"button hcmsButtonGreen\" target=\"_blank\">".getescapedtext ($hcms_lang['downloadview-file'][$lang])."</a>";
          }
          else
          {
            echo getescapedtext ($hcms_lang['please-wait-while-your-download-is-being-processed'][$lang]);
          }
          ?>
        </div>
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose5" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose5','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('downloadLayer','','hide');" />
      </td>        
    </tr>
  </table>
</form>
</div>
<?php 
if ($action == "download" && !$is_iphone)
{
  $downloadlink = createmultidownloadlink ($site, "", $media, $location, $pagename, $user, $convert_type, $convert_cfg);
  
  if ($downloadlink != "")
  {
?>
<script type="text/javascript">
<!--
function downloadFile()
{
  hcms_showHideLayers('downloadLayer','','hide');
  location.replace('<?php echo $downloadlink; ?>');
}

setTimeout('downloadFile()', 1000);
-->
</script>  
<?php
  }
  // download failed (zip file could not be created)
  else
  {
    echo showmessage (str_replace ("%filesize%", $mgmt_config['maxzipsize'], $hcms_lang['download-failed-max'][$lang]), 650, 60, $lang, "position:fixed; left:15px; top:15px; ");
  }
}
?>

</body>
</html>
