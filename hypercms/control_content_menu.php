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
}
else 
{
  $contentfile = "";
  $media = "";
}

// load template
if (!empty ($template))
{
  $result = loadtemplate ($site, $template);
  
  if (is_array ($result))
  {
    $bufferdata = getcontent ($result['content'], "<application>");
    $application = $bufferdata[0];
  }
}

// get object ID (required for favorites) and define multiobject for download
if ($folder != "")
{
  $object_id = rdbms_getobject_id ($location_esc.$folder);
  $multiobject = $location_esc.$folder;
}
else
{
  $object_id = rdbms_getobject_id ($location_esc.$page);
  $multiobject = $location_esc.$page;
}

// define message if object is checked out by another user
if (!empty ($contentfile))
{
  $usedby_array = getcontainername ($contentfile);
  
  if (is_array ($usedby_array) && !empty ($usedby_array['user'])) $usedby = $usedby_array['user'];

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
  elseif (checkrootpermission ("desktopcheckedout") && $setlocalpermission['root'] == 1 && $action == "page_lock") 
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
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script type="text/javascript" src="javascript/main.min.js"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
<?php
// invert colors
if (!empty ($hcms_themeinvertcolors))
{
  echo "<style>";
  echo invertcolorCSS ($hcms_themeinvertcolors);
  echo "</style>";
}
?>
<script type="text/javascript">
var locklayer = false;

function submitToWindow (url, action, windowname, features, width, height)
{
  if (document.forms['pagemenu_object'])
  {
    if (features == undefined) features = 'scrollbars=no,resizable=no';
    if (width == undefined) width = 400;
    if (height == undefined) height = 220;
    if (windowname == '') windowname = Math.floor(Math.random()*9999999);
    
    hcms_openWindow('', windowname, features, width, height);
    
    var form = document.forms['pagemenu_object'];
    
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
}

function submitToFrame (url, action)
{
  if (document.forms['pagemenu_object'])
  {
    var form = document.forms['pagemenu_object'];
    
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
    form.target = "objectview";
    
    parent.openPopup('empty.php');
    form.submit();
  }
}

function submitToSelf (action)
{
  if (document.forms['download'])
  {
    var form = document.forms['download'];
    
    form.elements['action'].value = action;
    form.elements['location'].value = '<?php echo $location_esc; ?>';
    form.elements['page'].value = '<?php echo $page; ?>';
    form.elements['wf_token'].value = '<?php echo $wf_token; ?>';
    form.submit();
  }
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
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters'][$lang]); ?>") + "\n" + addText);
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
  if (document.forms['download'])
  {
    var form = document.forms['download'];
      
    form.elements['convert_type'].value = type;
    
    submitToSelf ('download');
    hcms_showHideLayers('downloadLayer','','show');
  }
  else return false; 
}

function imgConvert (type, config)
{
  if (document.forms['download'])
  {
    var form = document.forms['download'];
      
    form.elements['convert_type'].value = type;
    form.elements['convert_cfg'].value = config;
    
    submitToSelf ('download');
    hcms_showHideLayers('downloadLayer','','show');
  }
  else return false; 
}

function vidConvert (type)
{
  if (document.forms['download'])
  {
    var form = document.forms['download'];
      
    form.elements['convert_type'].value = type;
    
    submitToSelf ('download');
    hcms_showHideLayers('downloadLayer','','show');
  }
  else return false; 
}

function openObjectView (location, object, view)
{
  if (location != "" && object != "" && parent.document.getElementById('objectview'))
  {
    parent.openObjectView(location, object, view);
  }
  else return false;
}

function openPopup (link)
{
  if (link != "")
  {
    parent.openPopup(link);
  }
  else return false;
}

<?php echo $add_onload; ?>
</script>
</head>

<body class="hcmsWorkplaceControlWallpaper">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:20px;"); ?>

<?php if (!$is_mobile) { ?>
<div style="position:absolute; right:40px; top:0; margin:0; padding:0;">
  <img onclick="parent.minControlFrame();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['collapse'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['collapse'][$lang]); ?>" src="<?php echo getthemelocation(); ?>img/button_arrow_up.png" />
  <img onclick="parent.maxControlFrame();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" style="margin:0px 4px 0px 0px;" alt="<?php echo getescapedtext ($hcms_lang['expand'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['expand'][$lang]); ?>" src="<?php echo getthemelocation(); ?>img/button_arrow_down.png" />
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
  $item = $pagecomp;
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
  <table class="hcmsTableNarrow">
    <tr>
      <?php
      // location
      if ($cat == "page" || $cat == "comp")
      {
        echo "
      <td style=\"white-space:nowrap; width:20px;\"><img src=\"".getthemelocation()."img/folder.png\" title=\"".getescapedtext ($hcms_lang['location'][$lang])."\" class=\"hcmsIconList\" />&nbsp;</td>
      <td class=\"hcmsHeadlineTiny\" style=\"white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\">".str_replace ("/", " &gt; ", trim ($location_name, "/"))."</td>";
      }
      else 
      {
        echo "
      <td style=\"white-space:nowrap; width:20px;\"><img src=\"".getthemelocation()."img/folder.png\" title=\"".getescapedtext ($hcms_lang['location'][$lang])."\" class=\"hcmsIconList\" />&nbsp;</td>
      <td class=\"hcmsHeadlineTiny\" style=\"white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\">".$pagecomp."</td>\n";    
      }
      ?>
    </tr>
    <tr>
      <?php
      // object
      if (empty ($file_info['icon'])) $file_info['icon'] = "Null_media.gif";

      echo "
      <td style=\"white-space:nowrap; width:20px;\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" title=\"".$item."\" class=\"hcmsIconList\" />&nbsp;</td>
      <td class=\"hcmsHeadlineTiny\" style=\"white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\">".$object_name."</td>";
      ?>
    </tr>
  </table>
  <?php } else { ?>
  <span class="hcmsHeadlineTiny" style="width:100%; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo str_replace ("/", " &gt; ", trim ($location_name, "/"))." &gt; ".$object_name; ?></span>
  <?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar">

  <div class="hcmsToolbarBlock">
    <?php
    // Preview Button
    if ($page != "" && $page != ".folder" && $cat != "" && $setlocalpermission['root'] == 1)
    {
      echo "<img onClick=\"openObjectView('".$location_esc."', '".$page."', 'preview');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_preview\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_preview.png\" alt=\"".getescapedtext ($hcms_lang['preview'][$lang])."\" title=\"".getescapedtext ($hcms_lang['preview'][$lang])."\" />\n";
    }
    else echo "<img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_preview.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";

    // Live-View Button
    if (!empty ($file_info['published']) && $page != "" && $page != ".folder" && $setlocalpermission['root'] == 1 && $cat == "page" && empty ($media))
    {
      echo "<img onClick=\"openObjectView('".$location_esc."', '".$page."', 'liveview');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_liveview\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_liveview.png\" alt=\"".getescapedtext ($hcms_lang['view-live'][$lang])."\" title=\"".getescapedtext ($hcms_lang['view-live'][$lang])."\" />\n";
    }
    else echo "<img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_liveview.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    ?>
  </div>
    
  <div class="hcmsToolbarBlock">
    <?php
    // Upload Button (HTML5 file upload)
    $popup_upload = "popup_upload_html.php";
    
    if (($usedby == "" || $usedby == $user) && $cat != "page" && !empty ($media) && $application != "generator" && $page != ".folder" && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1)
    {
      echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"openPopup('".$popup_upload."?uploadmode=single&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."');\" name=\"pic_obj_upload\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_upload.png\" alt=\"".getescapedtext ($hcms_lang['upload-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['upload-file'][$lang])."\" />\n";
    }
    else echo "<img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_upload.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    ?>    
    
    <?php
    // Download/Convert Button (also for folders) 
    // get media file extension
    if (!empty ($media))
    {
      $media_info = getfileinfo ($site, $media, $cat);
    }
    else $media_info = Null;
    
    $doc_rendering = false;
    $img_rendering = false;
    $vid_rendering = false;
    
    $doc_rendering = is_supported ($mgmt_docpreview, $media_info['ext']) && is_array ($mgmt_docconvert) && array_key_exists ($media_info['ext'], $mgmt_docconvert);
    
    foreach ($mgmt_imagepreview as $imgpreview_ext => $imgpreview)
    {
      // check file extension
      if (substr_count (strtolower ($imgpreview_ext).".", $media_info['ext'].".") > 0 && trim ($imgpreview) != "")
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
    
    $vid_rendering = is_supported ($mgmt_mediapreview, $media_info['ext']);
    
    // rendering options
    $perm_rendering = $setlocalpermission['root'] == 1 && $setlocalpermission['download'] == 1;
    $lock_rendering = ($usedby == "" || $usedby == $user);
    $dropbox_rendering = (is_array ($mgmt_config) && array_key_exists ("dropbox_appkey", $mgmt_config) && !empty ($mgmt_config['dropbox_appkey']));
    
    if (!empty ($page) && !empty ($media) && $perm_rendering && $lock_rendering && ($doc_rendering || $img_rendering || $vid_rendering || $dropbox_rendering))
    {
      echo "
      <div id=\"button_obj_convert\" class=\"hcmsButton hcmsButtonSizeWide\" onClick=\"hcms_switchSelector('select_obj_convert');\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_download.png\" class=\"hcmsButtonSizeSquare\" id=\"pic_obj_convert\" name=\"pic_obj_convert\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" /><img src=\"".getthemelocation($hcms_themeinvertcolors)."img/pointer_select.png\" class=\"hcmsButtonSizeNarrow\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" />
        
        <div id=\"select_obj_convert\" class=\"hcmsSelector\" style=\"position:relative; top:-52px; left:36px; visibility:hidden; z-index:999; width:180px; max-height:".($is_mobile ? "50" : "72")."px; overflow:auto; overflow-x:hidden; overflow-y:auto; white-space:nowrap;\">";
      
      // original file
      if (empty ($downloadformats) || (!is_document ($media_info['ext']) && !is_image ($media_info['ext']) && !is_video ($media_info['ext'])) || (is_document ($media_info['ext']) && !empty ($downloadformats['document']['original'])) || (is_image ($media_info['ext']) && !empty ($downloadformats['image']['original'])) || (is_video ($media_info['ext']) && !empty ($downloadformats['video']['original'])))
      {
        // function imgConvert must be used in order to reset the rendering options
        echo "
          <div class=\"hcmsSelectorItem\" onclick=\"imgConvert ('','');\"><img src=\"".getthemelocation()."img/".$media_info['icon']."\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['original'][$lang])."</div>";
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
        <div class=\"hcmsSelectorItem\" onclick=\"docConvert('".$doc_type."');\"><img src=\"".getthemelocation()."img/".$doc_info['icon']."\" class=\"hcmsIconList\" /> ".$doc_info['type']." (".strtoupper($doc_type).")</div>";
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
          <div class=\"hcmsSelectorItem\" onclick=\"imgConvert('".$image_type."', '".$config_name."');\"><img src=\"".getthemelocation()."img/".$img_info['icon']."\" class=\"hcmsIconList\" /> ".strtoupper($image_type)." ".$config_name."</div>";
              }
            }
          }
        }
      }
      
      // video download options
      if ($vid_rendering && is_video ($media))
      {
        if (empty ($downloadformats) || !empty ($downloadformats['video']['origthumb'])) echo "
          <div class=\"hcmsSelectorItem\" onclick=\"vidConvert('origthumb');\"><img src=\"".getthemelocation()."img/file_mpg.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['preview'][$lang])."</div>";
          
        if (empty ($downloadformats) || !empty ($downloadformats['video']['jpg'])) echo "
          <div class=\"hcmsSelectorItem\" onclick=\"vidConvert('jpg');\"><img src=\"".getthemelocation()."img/file_image.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['images'][$lang])." (JPG)</div>";
          
        if (empty ($downloadformats) || !empty ($downloadformats['video']['png'])) echo "
          <div class=\"hcmsSelectorItem\" onclick=\"vidConvert('png');\"><img src=\"".getthemelocation()."img/file_image.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['images'][$lang])." (PNG)</div>";
      }
      
      // save to dropbox
      if ($dropbox_rendering)
      {
        echo "
          <div class=\"hcmsSelectorItem\" onclick=\"submitToWindow('popup_save_dropbox.php', 'Save to Dropbox', '', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=yes,resizable=yes,width=600,height=400', '600', '400');\"><img src=\"".getthemelocation()."img/file_dropbox.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['dropbox'][$lang])."</div>";
      }
      
      echo "
        </div>
      </div>";
    } 
    // folder/file download without options
    elseif ((!empty ($media) || $page == ".folder") && !empty ($page) && $perm_rendering && $lock_rendering)
    {
      echo "
      <div class=\"hcmsButton hcmsButtonSizeWide\" onClick=\"submitToSelf('download'); hcms_showHideLayers('downloadLayer','','show');\"><img class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" name=\"pic_obj_liveview\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_download.png\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" /><img src=\"".getthemelocation()."img/pointer_select.png\" class=\"hcmsButtonTinyBlank hcmsButtonSizeNarrow\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" /></div>";
    }
    else
    {
      echo "
      <div class=\"hcmsButtonOff hcmsButtonSizeWide\"><img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_download.png\" class=\"hcmsButtonSizeSquare\" /><img src=\"".getthemelocation($hcms_themeinvertcolors)."img/pointer_select.png\" class=\"hcmsButtonSizeNarrow\" /></div>";
    }
    ?>
  </div>
    
  <div class="hcmsToolbarBlock">
    <?php    
    // Send Mail Button
    if ($page != "" && !empty ($mgmt_config['smtp_host']) && !empty ($mgmt_config[$site]['sendmail']) && $setlocalpermission['root'] == 1 && $setlocalpermission['sendlink'] == 1 && !empty ($mgmt_config['db_connect_rdbms']))
    {
        echo "
        <img class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_preview\" ";
        
        if (!empty ($mgmt_config['message_newwindow'])) echo "onClick=\"submitToWindow('user_sendlink.php', '', 'sendlink', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=no', 540, 800);\" ";
        else echo "onClick=\"submitToFrame('user_sendlink.php', 'sendlink');\" ";
        
        echo "src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_user_sendlink.png\" ".
                "alt=\"".getescapedtext ($hcms_lang['send-mail-link'][$lang])."\" title=\"".getescapedtext ($hcms_lang['send-mail-link'][$lang])."\" />";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_user_sendlink.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    ?>    

    <?php
    // Favorite Button
    if ($page != "" && checkrootpermission ('desktopfavorites') && $setlocalpermission['root'] == 1 && linking_valid() == false)
    {
      if (!$is_favorite)
      {
        echo "
      <img onClick=\"if (locklayer == false) location='control_content_menu.php?action=page_favorite_add&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."';\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_lock\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_favorites_delete.png\" alt=\"".getescapedtext ($hcms_lang['add-to-favorites'][$lang])."\" title=\"".getescapedtext ($hcms_lang['add-to-favorites'][$lang])."\" />";
      }
      else echo "
      <img onClick=\"if (locklayer == false) location='control_content_menu.php?action=page_favorite_delete&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."';\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_unlock\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_favorites_new.png\" alt=\"".getescapedtext ($hcms_lang['delete-favorite'][$lang])."\" title=\"".getescapedtext ($hcms_lang['delete-favorite'][$lang])."\" />";
    }
    else echo "
    <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_favorites_delete.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    ?>
    
    <?php
    // Checked out Button
    if ($page != "" && $wf_role == 5 && checkrootpermission ('desktopcheckedout') && linking_valid() == false && ((empty ($media) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1) || (!empty ($media) && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1)))
    {
      if ($usedby == "")
      {
        echo "
      <img onClick=\"if (locklayer == false) location='control_content_menu.php?action=page_lock&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."';\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_lock\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_unlock.png\" alt=\"".getescapedtext ($hcms_lang['check-out'][$lang])."\" title=\"".getescapedtext ($hcms_lang['check-out'][$lang])."\" />";
      }
      elseif ($usedby == $user)
      {
        echo "
      <img onClick=\"if (locklayer == false) location='control_content_menu.php?action=page_unlock&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."';\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_unlock\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_lock.png\" alt=\"".getescapedtext ($hcms_lang['check-in'][$lang])."\" title=\"".getescapedtext ($hcms_lang['check-in'][$lang])."\" />";
      }
      else echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_lock.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\">";
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
        echo "
      <img onClick=\"if (locklayer == false) openPopup('popup_message.php?action=accept&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_accept\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_workflow_accept.png\" alt=\"".getescapedtext ($hcms_lang['accept-and-forward'][$lang])."\" title=\"".getescapedtext ($hcms_lang['accept-and-forward'][$lang])."\" />";
      }
      else echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_workflow_accept.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
  
      if ($usedby == "" && ($wf_role >= 1 && $wf_role <= 4 && $wf_id != "u.1") && $page != "" && $setlocalpermission['root'] == 1)
      {
        echo "
      <img onClick=\"if (locklayer == false) openPopup('popup_message.php?action=reject&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_reject\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_workflow_reject.png\" alt=\"".getescapedtext ($hcms_lang['reject-and-send-back'][$lang])."\" title=\"".getescapedtext ($hcms_lang['reject-and-send-back'][$lang])."\" />";
      }
      else echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_workflow_reject.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    else
    {
      echo "
    <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_workflow_accept.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />
    <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_workflow_reject.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
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
        echo "
        <img onClick=\"if (locklayer == false) hcms_openWindow('";
        
        if ($mgmt_config['db_connect_rdbms'] != "") echo "popup_publish.php";
        else echo "popup_action.php";
        
        echo "?action=publish&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."', '".$object_id."', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=no,resizable=no', 400, 400);\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_publish\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_publish.png\" alt=\"".getescapedtext ($hcms_lang['publish'][$lang])."\" title=\"".getescapedtext ($hcms_lang['publish'][$lang])."\" />";
      }
      else echo "<img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_publish.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
  
      // Unpublish
      if (($usedby == "" || $usedby == $user) && $filetype != "" && $wf_role >= 3 && $setlocalpermission['root'] == 1 && $setlocalpermission['publish'] == 1)
      {
        echo "
        <img onClick=\"if (locklayer == false) hcms_openWindow('";

        if ($mgmt_config['db_connect_rdbms'] != "") echo "popup_publish.php";
        else echo "popup_action.php";

        echo "?action=unpublish&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."', '".$object_id."', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=no,resizable=no', 400, 400);\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_unpublish\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_unpublish.png\" alt=\"".getescapedtext ($hcms_lang['unpublish'][$lang])."\" title=\"".getescapedtext ($hcms_lang['unpublish'][$lang])."\" />";
      }
      else echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_unpublish.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_publish.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_unpublish.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    ?>    
  </div>
  <div class="hcmsToolbarBlock">    
    <?php
    // Reload button
    echo "
    <img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"if (locklayer == false) parent.frames['objFrame'].location.reload();\" name=\"pic_obj_refresh\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_view_refresh.png\" alt=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" title=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" />";
    ?>    
  </div>
  <div class="hcmsToolbarBlock">
    <?php echo showhelpbutton ("usersguide", $setlocalpermission['root'], $lang, ""); ?>
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

<div id="objcreateLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "95%"; else echo "700px"; ?>; height:76px; z-index:4; left:15px; top:10px; visibility:<?php if ($page != "") echo "hidden"; else echo "visible"; ?>">
<form name="page_create" action="" method="post" onsubmit="return checkForm_page_create();">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="wf_token" value="<?php echo $wf_token; ?>" />
  <input type="hidden" name="wf_role" value="<?php echo $wf_role; ?>" />
  <input type="hidden" name="contentfile" value="<?php echo $contentfile; ?>" />
  <input type="hidden" name="action" value="page_create" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>">
  
  <table class="hcmsTableStandard" style="width:100%; height:76px;">
    <tr>
      <td>
        <?php
        if ($is_mobile) echo getescapedtext ($hcms_lang['new-object'][$lang]);
        else echo getescapedtext ($hcms_lang['new-object'][$lang]." (".$hcms_lang['name-without-ext'][$lang].")");
        ?> 
      </td>
      <td style="white-space:nowrap">
        <input type="text" name="page" maxlength="<?php if (!empty ($mgmt_config['max_digits_filename']) && intval ($mgmt_config['max_digits_filename']) > 0) echo intval ($mgmt_config['max_digits_filename']); else echo "200"; ?>" style="width:180px;"  title="<?php echo getescapedtext ($hcms_lang['new-object'][$lang]); ?>"/>
      </td>
    </tr>
    <tr>
      <td>
        <?php echo getescapedtext ($hcms_lang['template-auto-preview'][$lang]); ?> 
      </td>
      <td style="white-space:nowrap">
        <select name="template" onChange="hcms_jumpMenu('parent.frames[\'objFrame\']',this,0)" style="width:180px;" title="<?php echo getescapedtext ($hcms_lang['template'][$lang]); ?>">
          <option value="empty.php"><?php echo getescapedtext ($hcms_lang['select-template'][$lang]); ?></option>
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
              
                echo "
                <option value=\"template_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&template=".url_encode($value)."\">".$tpl_name."</option>";
              }
            }
          }          
          ?>
        </select>
        <img name="Button3" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_page_create();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button3','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
      </td>
    </tr>
  </table>
</form>
</div>

<?php
// tabs
if ($page != "")
{
  echo "
    <div id=\"tabLayer\" class=\"hcmsTabContainer\" style=\"position:absolute; z-index:10; visibility:visible; left:0px; top:77px; white-space:nowrap;\">
      <div id=\"tab1\" class=\"hcmsTabActive\">
        <a href=\"page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."\" target=\"objFrame\" onClick=\"hcms_elementbyIdStyle('tab1','hcmsTabActive'); hcms_elementbyIdStyle('tab2','hcmsTabPassive'); hcms_elementbyIdStyle('tab3','hcmsTabPassive'); hcms_elementbyIdStyle('tab4','hcmsTabPassive');\" title=\"".$pagecomp."\">".$pagecomp."</a>
      </div>
      <div id=\"tab2\" class=\"hcmsTabPassive\">";
        if (($usedby == "" || $usedby == $user) && ($wf_role >= 4 || $wf_role == 2) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1) 
          echo "
          <a href=\"frameset_template_change.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&template=".url_encode($template)."&wf_token=".url_encode($wf_token)."\" target=\"objFrame\" onClick=\"hcms_elementbyIdStyle('tab1','hcmsTabPassive'); hcms_elementbyIdStyle('tab2','hcmsTabActive'); hcms_elementbyIdStyle('tab3','hcmsTabPassive'); hcms_elementbyIdStyle('tab4','hcmsTabPassive');\" title=\"".getescapedtext ($hcms_lang['template'][$lang])."\">".getescapedtext ($hcms_lang['template'][$lang])."</a>";
          else echo "
          <b class=\"hcmsButtonTinyOff\">".getescapedtext ($hcms_lang['template'][$lang])."</b>";
      echo "
      </div>
      <div id=\"tab3\" class=\"hcmsTabPassive\">";
        if (($usedby == "" || $usedby == $user) && ($wf_role >= 4 || $wf_role == 2) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
          echo "<a href=\"version_content.php?site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."\" target=\"objFrame\" onClick=\"hcms_elementbyIdStyle('tab1','hcmsTabPassive'); hcms_elementbyIdStyle('tab2','hcmsTabPassive'); hcms_elementbyIdStyle ('tab3','hcmsTabActive'); hcms_elementbyIdStyle('tab4','hcmsTabPassive');\" title=\"".getescapedtext ($hcms_lang['version'][$lang])."\">".getescapedtext ($hcms_lang['version'][$lang])."</a>";
        else echo "<b class=\"hcmsButtonTinyOff\">".getescapedtext ($hcms_lang['version'][$lang])."</b>";
      echo "</div>
      <div id=\"tab4\" class=\"hcmsTabPassive\">";
        if ($wf_role >= 1 && $setlocalpermission['root'] == 1) 
          echo "
          <a href=\"page_info.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."\" target=\"objFrame\" onClick=\"hcms_elementbyIdStyle('tab1','hcmsTabPassive'); hcms_elementbyIdStyle('tab2','hcmsTabPassive'); hcms_elementbyIdStyle('tab3','hcmsTabPassive'); hcms_elementbyIdStyle('tab4','hcmsTabActive');\" title=\"".getescapedtext ($hcms_lang['information'][$lang])."\">".getescapedtext ($hcms_lang['information'][$lang])."</a>";
          else echo "
          <b class=\"hcmsButtonTinyOff\">".getescapedtext ($hcms_lang['information'][$lang])."</b>";
      echo "</div>
  </div>";
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

    <table class="hcmsTableStandard" style="width:100%; height:60px;">
      <tr>
        <td>
          <div style="overflow:auto;">
            <?php
            // iOS (iPhone, iPad) download
            if ($action == "download" && $is_iphone)
            { 
              $downloadlink = createmultidownloadlink ($site, $multiobject, $pagename, $user, $convert_type, $convert_cfg);
              
              echo "<a href=\"".$downloadlink."\" class=\"button hcmsButtonGreen\" target=\"_blank\">".getescapedtext ($hcms_lang['downloadview-file'][$lang])."</a>";
            }
            else
            {
              echo getescapedtext ($hcms_lang['please-wait-while-your-download-is-being-processed'][$lang]);
            }
            ?>
          </div>
        </td>
        <td style="width:38px; text-align:right; vertical-align:top;">
          <img name="hcms_mediaClose5" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose5','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('downloadLayer','','hide');" />
        </td>        
      </tr>
    </table>
  </form>
</div>

<?php
// download for non iOS devices
if ($action == "download" && !$is_iphone)
{
  $downloadlink = createmultidownloadlink ($site, $multiobject, $pagename, $user, $convert_type, $convert_cfg);

  if ($downloadlink != "")
  {
?>
<script type="text/javascript">
function downloadFile()
{
  hcms_showHideLayers('downloadLayer','','hide');
  location.replace('<?php echo $downloadlink; ?>');
}

setTimeout('downloadFile()', 1000);
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
