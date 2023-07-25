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
if (!empty ($mgmt_config[$site]['dam']) && $setlocalpermission['root'] != 1) killsession ($user);
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

// initialize
$show = "";
$add_onload = "";
$usedby = "";
$is_favorite = false;
$wf_id = "";
$wf_role = "";
$contentfile = "";
$application = "";
$media_info['ext'] = "";
$doc_rendering = false;
$img_rendering = false;
$vid_rendering = false;

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
  
  if (!empty ($result['content']))
  {
    $temp = getcontent ($result['content'], "<application>");
    if (!empty ($temp[0])) $application = $temp[0];
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
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
<style type="text/css">
<?php
// inverted main colors
if (!empty ($hcms_themeinvertcolors))
{
  if (!empty ($hcms_hoverinvertcolors)) $invertonhover = false;
  else $invertonhover = true;

  echo invertcolorCSS ($hcms_themeinvertcolors, ".hcmsInvertColor", true, $invertonhover);
  echo invertcolorCSS ($hcms_themeinvertcolors, ".hcmsInvertPrimaryColor", true, false);
}
// inverted hover colors
elseif (!empty ($hcms_hoverinvertcolors))
{
  echo invertcolorCSS ($hcms_hoverinvertcolors, ".hcmsInvertColor", false, true);
  echo invertcolorCSS ($hcms_hoverinvertcolors, ".hcmsInvertHoverColor", true, false);
}
?>

.hcmsMenuItem
{
  padding: 2px 2px 1px 2px;
  min-width: 190px;
  width: 10%;
  float: left;
}

.hcmsMenu
{
  width: 100%;
}

@media screen and (min-width: 1201px)
{
  .hcmsMenu
  {
    width: 1200px;
  }
}
</style>
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

function openMenu (id)
{
  hcms_showFormLayer(id, 0);
  hcms_showHideLayers('objcreateLayer','','hide');
}

function closeMenu (id)
{
  hcms_hideFormLayer(id);
}

<?php echo $add_onload; ?>
</script>
</head>

<body class="hcmsWorkplaceControl">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:20px;", "hcms_infobox_mouseover"); ?>

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
      <td style=\"white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\" title=\"".str_replace ("/", " &gt; ", trim ($location_name, "/"))."\">".str_replace ("/", " &gt; ", trim ($location_name, "/"))."</td>";
      }
      else 
      {
        echo "
      <td style=\"white-space:nowrap; width:20px;\"><img src=\"".getthemelocation()."img/folder.png\" title=\"".getescapedtext ($hcms_lang['location'][$lang])."\" class=\"hcmsIconList\" />&nbsp;</td>
      <td style=\"white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\">".$pagecomp."</td>\n";    
      }
      ?>
    </tr>
    <tr>
      <?php
      // object
      if (empty ($file_info['icon'])) $file_info['icon'] = "Null_media.gif";

      echo "
      <td style=\"white-space:nowrap; width:20px;\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" title=\"".$item."\" class=\"hcmsIconList\" />&nbsp;</td>
      <td style=\"white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\">".$object_name."</td>";
      ?>
    </tr>
  </table>
  <?php } else { ?>
  <span style="width:100%; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo str_replace ("/", " &gt; ", trim ($location_name, "/"))." &gt; ".$object_name; ?></span>
  <?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar" style="<?php if (!$is_mobile) echo "white-space:nowrap; min-width:580px;"; else echo "max-height:100px;"; ?>">

  <div class="hcmsToolbarBlock">
    <?php
    // Preview Button
    if ($page != "" && $page != ".folder" && $cat != "" && $setlocalpermission['root'] == 1)
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img onClick=\"openObjectView('".$location_esc."', '".$page."', 'preview');\" class=\"hcmsButtonSizeSquare\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_preview.png\" alt=\"".getescapedtext ($hcms_lang['preview'][$lang])."\" title=\"".getescapedtext ($hcms_lang['preview'][$lang])."\" />
      </div>";
    }
    else echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_preview.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";

    // Live-View Button
    if (!empty ($file_info['published']) && $page != "" && $page != ".folder" && $setlocalpermission['root'] == 1 && $cat == "page" && empty ($media))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img onClick=\"openObjectView('".$location_esc."', '".$page."', 'liveview');\" class=\"hcmsButtonSizeSquare\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_liveview.png\" alt=\"".getescapedtext ($hcms_lang['view-live'][$lang])."\" title=\"".getescapedtext ($hcms_lang['view-live'][$lang])."\" />
      </div>";
    }
    else echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_liveview.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    ?>
  </div>
    
  <div class="hcmsToolbarBlock">
    <?php
    // Upload Button (HTML5 file upload)
    if (($usedby == "" || $usedby == $user) && $cat != "page" && !empty ($media) && $application != "generator" && $page != ".folder" && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1)
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img class=\"hcmsButtonSizeSquare\" onclick=\"openPopup('popup_upload_html.php?uploadmode=single&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."');\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_upload.png\" alt=\"".getescapedtext ($hcms_lang['upload-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['upload-file'][$lang])."\" />
      </div>";
    }
    else echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_upload.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    ?>    
    
    <?php
    // Download/Convert Button (also for folders) 
    $media_info = getfileinfo ($site, $media, $cat);

    // get media file extension
    if (!empty ($media) && !empty ($mgmt_imagepreview) && is_array ($mgmt_imagepreview))
    {
      if (!empty ($media_info['ext']))
      {
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
      }
    }

    // rendering options
    $perm_rendering = $setlocalpermission['root'] == 1 && $setlocalpermission['download'] == 1;
    $lock_rendering = ($usedby == "" || $usedby == $user);
    $dropbox_rendering = (is_array ($mgmt_config) && array_key_exists ("dropbox_appkey", $mgmt_config) && !empty ($mgmt_config['dropbox_appkey']));
    
    if (!empty ($page) && !empty ($media) && $perm_rendering && $lock_rendering && ($doc_rendering || $img_rendering || $vid_rendering || $dropbox_rendering))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_download.png\" class=\"hcmsButtonSizeSquare\" onclick=\"openMenu('downloadselectLayer');\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" />
      </div>";
    } 
    // folder/file download without options
    elseif ((!empty ($media) || $page == ".folder") && !empty ($page) && $perm_rendering && $lock_rendering)
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\" onclick=\"submitToSelf('download'); hcms_showHideLayers('downloadLayer','','show');\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_download.png\" class=\"hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" />
      </div>";
    }
    else
    {
      echo "
      <div class=\"hcmsButtonOff hcmsButtonSizeSquare\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_download.png\" class=\"hcmsButtonSizeSquare\" />
      </div>";
    }
    ?>
  </div>
    
  <div class="hcmsToolbarBlock">
    <?php    
    // Send Mail Button
    if ($page != "" && !empty ($mgmt_config['smtp_host']) && !empty ($mgmt_config[$site]['sendmail']) && $setlocalpermission['root'] == 1 && $setlocalpermission['sendlink'] == 1 && !empty ($mgmt_config['db_connect_rdbms']))
    {
        echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img class=\"hcmsButtonSizeSquare\" ";        
        if (!empty ($mgmt_config['message_newwindow'])) echo "onClick=\"submitToWindow('user_sendlink.php', '', 'sendlink', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=no', 540, 800);\" ";
        else echo "onclick=\"submitToFrame('user_sendlink.php', 'sendlink');\" ";
        
        echo "src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_user_sendlink.png\" ".
             "alt=\"".getescapedtext ($hcms_lang['send-mail-link'][$lang])."\" title=\"".getescapedtext ($hcms_lang['send-mail-link'][$lang])."\" />
      </div>";
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
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img onclick=\"if (locklayer == false) location='control_content_menu.php?action=page_favorite_add&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."';\" ".
        "class=\"hcmsButtonSizeSquare\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_favorites_delete.png\" alt=\"".getescapedtext ($hcms_lang['add-to-favorites'][$lang])."\" title=\"".getescapedtext ($hcms_lang['add-to-favorites'][$lang])."\" />
      </div>";
      }
      else echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img onclick=\"if (locklayer == false) location='control_content_menu.php?action=page_favorite_delete&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."';\" ".
        "class=\"hcmsButtonSizeSquare\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_favorites_new.png\" alt=\"".getescapedtext ($hcms_lang['delete-favorite'][$lang])."\" title=\"".getescapedtext ($hcms_lang['delete-favorite'][$lang])."\" />
      </div>";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_favorites_delete.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    ?>
    
    <?php
    // Checked out Button
    if ($page != "" && $wf_role == 5 && checkrootpermission ('desktopcheckedout') && linking_valid() == false && ((empty ($media) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1) || (!empty ($media) && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1)))
    {
      if ($usedby == "")
      {
        echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img onclick=\"if (locklayer == false) location='control_content_menu.php?action=page_lock&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."';\" ".
        "class=\"hcmsButtonSizeSquare\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_unlock.png\" alt=\"".getescapedtext ($hcms_lang['check-out'][$lang])."\" title=\"".getescapedtext ($hcms_lang['check-out'][$lang])."\" />
      </div>";
      }
      elseif ($usedby == $user)
      {
        echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img onclick=\"if (locklayer == false) location='control_content_menu.php?action=page_unlock&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."';\" ".
        "class=\"hcmsButtonSizeSquare\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_lock.png\" alt=\"".getescapedtext ($hcms_lang['check-in'][$lang])."\" title=\"".getescapedtext ($hcms_lang['check-in'][$lang])."\" />
      </div>";
      }
      else echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_lock.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\">";
    }
    else
    {
      echo "
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
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img onclick=\"if (locklayer == false) openPopup('popup_message.php?action=accept&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."');\" ".
        "class=\"cmsButtonSizeSquare\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_workflow_accept.png\" alt=\"".getescapedtext ($hcms_lang['accept-and-forward'][$lang])."\" title=\"".getescapedtext ($hcms_lang['accept-and-forward'][$lang])."\" />
      </div>";
      }
      else echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_workflow_accept.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
  
      if ($usedby == "" && ($wf_role >= 1 && $wf_role <= 4 && $wf_id != "u.1") && $page != "" && $setlocalpermission['root'] == 1)
      {
        echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img onclick=\"if (locklayer == false) openPopup('popup_message.php?action=reject&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."');\" ".
        "class=\"hcmsButtonSizeSquare\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_workflow_reject.png\" alt=\"".getescapedtext ($hcms_lang['reject-and-send-back'][$lang])."\" title=\"".getescapedtext ($hcms_lang['reject-and-send-back'][$lang])."\" />
      </div>";
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
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img onclick=\"if (locklayer == false) openPopup('popup_publish.php?action=publish&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."');\" ".
        "class=\"hcmsButtonSizeSquare\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_publish.png\" alt=\"".getescapedtext ($hcms_lang['publish'][$lang])."\" title=\"".getescapedtext ($hcms_lang['publish'][$lang])."\" />
      </div>";
      }
      else echo "<img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_publish.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
  
      // Unpublish
      if (($usedby == "" || $usedby == $user) && $filetype != "" && $wf_role >= 3 && $setlocalpermission['root'] == 1 && $setlocalpermission['publish'] == 1)
      {
        echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img onclick=\"if (locklayer == false) openPopup('popup_publish.php?action=unpublish&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."');\" ".
        "class=\"hcmsButtonSizeSquare\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_file_unpublish.png\" alt=\"".getescapedtext ($hcms_lang['unpublish'][$lang])."\" title=\"".getescapedtext ($hcms_lang['unpublish'][$lang])."\" />
      </div>";
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
    <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
      <img class=\"hcmsButtonSizeSquare\" onclick=\"if (locklayer == false) parent.frames['objFrame'].location.reload();\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_view_refresh.png\" alt=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" title=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" />
    </div>";
    ?>    
  </div>
  <div class="hcmsToolbarBlock">
    <?php echo showhelpbutton ("usersguide", $setlocalpermission['root'], $lang, "", "hcmsHoverColor hcmsInvertColor"); ?>
  </div>
</div>



<!-- Download select menu -->
<div id="downloadselectLayer" class="hcmsWorkplaceControl" style="position:absolute; left:0px; <?php if (!$is_mobile) echo "top:36px;"; else echo "top:0px;"; ?> width:100%; <?php if (!$is_mobile) echo "height:40px;"; else echo "height:100%;"; ?> padding:0; margin:0; z-index:1; display:none; overflow:auto;">
  <div style="position:fixed; right:2px; <?php if (!$is_mobile) echo "top:36px;"; else echo "top:2px;"; ?> width:32px; height:32px; z-index:91;">
    <img name="hcms_downloadselectLayerClose" src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_downloadselectLayerClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="closeMenu('downloadselectLayer');" />
  </div>

  <div class="hcmsMenu">
    <div class="hcmsInvertPrimaryColor hcmsMenuItem">
      <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['download'][$lang]); ?></span>
      <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_arrow_right.png" class="hcmsIconList" />
    </div>
    <?php
    if (!empty ($page) && !empty ($media) && $perm_rendering && $lock_rendering && ($doc_rendering || $img_rendering || $dropbox_rendering))
    {    
      // original file
      if (empty ($downloadformats) || (!is_document ($media_info['ext']) && !is_image ($media_info['ext']) && !is_video ($media_info['ext'])) || (is_document ($media_info['ext']) && !empty ($downloadformats['document']['original'])) || (is_image ($media_info['ext']) && !empty ($downloadformats['image']['original'])) || (is_video ($media_info['ext']) && !empty ($downloadformats['video']['original'])))
      {
        // function imgConvert must be used in order to reset the rendering options
        echo "
          <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"imgConvert ('','');\">
            <img src=\"".getthemelocation()."img/".$media_info['icon']."\" class=\"hcmsIconList\" />
            <span class=\"\">".getescapedtext ($hcms_lang['original'][$lang])."</span>
          </div>";
      }
      
      // document download options
      if ($doc_rendering && !empty ($mgmt_docoptions) && is_array ($mgmt_docoptions))
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
        <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"docConvert ('".$doc_type."');\">
          <img src=\"".getthemelocation()."img/".$doc_info['icon']."\" class=\"hcmsIconList\" />
          <span class=\"\">".$doc_info['type']." (".strtoupper($doc_type).")</span>
        </div>";
            }
          }
        }
      }
      
      // image download options
      if ($img_rendering && !empty ($mgmt_imageoptions) && is_array ($mgmt_imageoptions))
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
        <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"imgConvert ('".$image_type."', '".$config_name."');\">
          <img src=\"".getthemelocation()."img/".$img_info['icon']."\" class=\"hcmsIconList\" />
          <span class=\"\">".strtoupper($image_type)." ".$config_name."</span>
        </div>";
              }
            }
          }
        }
      }
      
      // video download options
      if ($vid_rendering && is_video ($media))
      {
        if (empty ($downloadformats) || !empty ($downloadformats['video']['origthumb'])) echo "
          <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"vidConvert('origthumb');\">
            <img src=\"".getthemelocation()."img/file_mpg.png\" class=\"hcmsIconList\" />
            <span class=\"\">".getescapedtext ($hcms_lang['preview'][$lang])."</span>
          </div>";
          
        if (empty ($downloadformats) || !empty ($downloadformats['video']['jpg'])) echo "
          <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"vidConvert('jpg');\">
            <img src=\"".getthemelocation()."img/file_image.png\" class=\"hcmsIconList\" />
            <span class=\"\">".getescapedtext ($hcms_lang['images'][$lang])." (JPG)</span>
          </div>";
          
        if (empty ($downloadformats) || !empty ($downloadformats['video']['png'])) echo "
          <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"vidConvert('png');\">
            <img src=\"".getthemelocation()."img/file_image.png\" class=\"hcmsIconList\" />
            <span class=\"\">".getescapedtext ($hcms_lang['images'][$lang])." (PNG)</span>
          </div>";
      }
			
			// save to dropbox
			if ($dropbox_rendering)
			{
				echo "
          <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsMenuItem\" onclick=\"submitToWindow('popup_save_dropbox.php', 'Save to Dropbox', '', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=yes,resizable=yes,width=600,height=400', 600, 400); document.getElementById('button_obj_convert').click();a\">
            <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/file_dropbox.png\" class=\"hcmsIconList\" />
            <span class=\"\">".getescapedtext ($hcms_lang['dropbox'][$lang])."</span>
          </div>";
			}

      echo "
        </div>
      </div>";
    }
    ?>
  </div>
</div>



<?php
echo showmessage ($show, 660, 75, $lang, "position:fixed; left:5px; top:5px; ");
?>



<?php
// Tabs
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

<!-- create object -->
<div id="objcreateLayer" class="hcmsMessage" style="position:absolute; left:5px; top:5px; width:<?php if ($is_mobile) echo "95%"; else echo "700px"; ?>; visibility:<?php if ($page != "") echo "hidden"; else echo "visible"; ?>">
  <form name="page_create" action="" method="post" onsubmit="return checkForm_page_create();">
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
    <input type="hidden" name="wf_token" value="<?php echo $wf_token; ?>" />
    <input type="hidden" name="wf_role" value="<?php echo $wf_role; ?>" />
    <input type="hidden" name="contentfile" value="<?php echo $contentfile; ?>" />
    <input type="hidden" name="action" value="page_create" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>">
    
    <table class="hcmsTableNarrow" style="width:100%; min-height:70px;">
      <tr>
        <td style="width:30%;">
          <?php
          if ($is_mobile) echo getescapedtext ($hcms_lang['new-object'][$lang]);
          else echo getescapedtext ($hcms_lang['new-object'][$lang]." (".$hcms_lang['name-without-ext'][$lang].")");
          ?> 
        </td>
        <td style="white-space:nowrap;">
          <input type="text" name="page" maxlength="<?php if (!empty ($mgmt_config['max_digits_filename']) && intval ($mgmt_config['max_digits_filename']) > 0) echo intval ($mgmt_config['max_digits_filename']); else echo "200"; ?>" style="width:<?php if ($is_mobile) echo "180px"; else echo "80%"; ?>;" placeholder="<?php echo getescapedtext ($hcms_lang['name'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['name'][$lang]); ?>" />
        </td>
      </tr>
      <tr>
        <td>
          <?php echo getescapedtext ($hcms_lang['template-auto-preview'][$lang]); ?> 
        </td>
        <td style="white-space:nowrap;">
          <select name="template" onChange="hcms_jumpMenu('parent.frames[\'objFrame\']',this,0)" style="width:<?php if ($is_mobile) echo "180px"; else echo "80%"; ?>;" title="<?php echo getescapedtext ($hcms_lang['template'][$lang]); ?>">
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

<!-- download -->
<div id="downloadLayer" class="hcmsMessage" style="position:absolute; left:5px; top:5px; width:<?php if ($is_mobile) echo "80%"; else echo "650px"; ?>; visibility:<?php echo ($action == 'download' ? 'visible' : 'hidden'); ?>;" >
  <form name="download" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
    <input type="hidden" name="action" value="download" />
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="page" value="<?php echo $page; ?>" />
    <input type="hidden" name="wf_token" value="<?php echo $wf_token; ?>" />
    <input type="hidden" name="convert_type" value="" />
    <input type="hidden" name="convert_cfg" value="" />

    <table class="hcmsTableNarrow" style="width:100%; min-height:40px;">
      <tr>
        <td>
          <div style="overflow:auto;">
            <?php
            // iOS (iPhone, iPad) download
            if ($action == "download" && $is_iphone)
            { 
              $downloadlink = createmultidownloadlink ($site, $multiobject, $pagename, $user, $convert_type, $convert_cfg, "wrapper");
              
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
  $downloadlink = createmultidownloadlink ($site, $multiobject, $pagename, $user, $convert_type, $convert_cfg, "download");

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
    echo showmessage (str_replace ("%filesize%", $mgmt_config['maxzipsize'], $hcms_lang['download-failed-max'][$lang]), 660, 70, $lang, "position:fixed; left:10px; top:10px; ");
  }
}
?>

</body>
</html>
