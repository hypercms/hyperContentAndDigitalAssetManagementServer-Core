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
$location = getrequest ("location", "locationname");
$page = getrequest ("page", "objectname");
$view = getrequest ("view", "objectname");
$screenwidth = getrequest ("width", "numeric", 800);
$screenheight = getrequest ("height", "numeric", 600);

// set default width and height
if ($screenwidth < 1) $screenwidth = 800;
$width = $screenwidth - 100;

if ($screenheight < 1) $screenheight = 600;
$height = $screenheight - 100;

// location and object is set by assetbrowser
if ($location == "" && !empty ($hcms_assetbrowser_location) && !empty ($hcms_assetbrowser_object))
{
  $location = $hcms_assetbrowser_location;
  $page = $hcms_assetbrowser_object;
}

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && is_file ($location.$page))
{
  // ------------------------------ permission section --------------------------------
  
  // check access permissions (DAM)
  if ($mgmt_config[$site]['dam'] == true)
  {
    $ownergroup = accesspermission ($site, $location, $cat);
    $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);
    if ($setlocalpermission['root'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);
  }
  // check permissions
  else
  {
    if (($cat != "page" && $cat != "comp") || ($cat == "comp" && !checkglobalpermission ($site, 'component')) || ($cat == "page" && !checkglobalpermission ($site, 'page')) || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);
  }

  // check session of user
  checkusersession ($user);
  
  // --------------------------------- logic section ----------------------------------
  
  $file_info = getfileinfo ($site, $location.$page, $cat);
  $object_info = getobjectinfo ($site, $location, $page, $user);

  // media live-view
  if (!empty ($object_info['media']))
  {
    $mediafile = $site."/".$object_info['media'];
    $objectview = showmedia ($mediafile, $file_info['name'], "media_only", "objectcontainer", $width, $height);
  }
  // page live-view (no multimedia file)
  elseif ($view == "liveview" && $cat == "page")
  {
    // load publication configuration
    if (valid_publicationname ($site)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini");
      
    $url_page = str_ireplace ($mgmt_config[$site]['abs_path_page'], $publ_config['url_publ_page'], $location).$page;
    $objectview = "<div id=\"objectcontainer\" style=\"width:".($screenwidth - 100)."px; height:".($screenheight - 100)."px; border:1px #000000 solid;\"><iframe id=\"objectiframe\" scrolling=\"auto\" src=\"".$url_page."\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>";
  }
  // page or component preview (no multimedia file)
  else
  {
    $objectview = "<div id=\"objectcontainer\" style=\"width:".($screenwidth - 100)."px; height:".($screenheight - 100)."px; border:1px #000000 solid;\"><iframe scrolling=\"auto\" src=\"page_preview.php?location=".url_encode($location_esc)."&page=".url_encode($page)."\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>";
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script type="text/javascript" src="javascript/main.js"></script>
<script type="text/javascript" src="javascript/click.js"></script>
<?php if (!empty ($file_info['ext']) && is_audio ($file_info['ext'])) echo showaudioplayer_head (false); ?>
<?php if (!empty ($file_info['ext']) && is_video ($file_info['ext'])) echo showvideoplayer_head (false, false); ?>
<script>
function setscreensize (size)
{
  if (size != "")
  {
    var resolution = size.split('x');
    var width = resolution[0].trim();
    var height = resolution[1].trim();
    var iframe = document.getElementById('objectcontainer');

    iframe.style.width = width + 'px';
    iframe.style.height = height + 'px';
    
    centercontainer();
    closeselectors();
  }
  else return false;
}

function rotate ()
{
  // get width and height of container
  var mediawidth = document.getElementById('objectcontainer').offsetWidth;
  var mediaheight = document.getElementById('objectcontainer').offsetHeight;
  var iframe = document.getElementById('objectcontainer');

  if (iframe && mediawidth > 0 && mediaheight > 0)
  {
    // switch
    iframe.style.width = mediaheight + 'px';
    iframe.style.height = mediawidth + 'px';
    
    centercontainer();
  }
  else return false;
}

function centercontainer ()
{
  // get width and height of container
  var mediawidth = document.getElementById('objectcontainer').offsetWidth;
  var mediaheight = document.getElementById('objectcontainer').offsetHeight;
  
  <?php if (!empty ($file_info['ext']) && is_audio ($file_info['ext'])) { ?>
  // correct size of audio player
  if (mediawidth < 300 || mediaheight < 60)
  {
    mediawidth = 320;
    mediaheight = 320;
  }
  <?php } ?>

  // screen width and height
  var screenwidth = <?php if ($screenwidth > 0) echo $screenwidth; else echo 800; ?>;
  var screenheight = <?php if ($screenheight > 0) echo $screenheight; else echo 600; ?>;
  
  // calculate margins
  var marginleft = Math.floor((screenwidth - mediawidth) / 2);
  var margintop = Math.floor((screenheight - mediaheight) / 2);
  
  // set margins
  if (marginleft > 0) document.getElementById('container').style.marginLeft = marginleft+"px";
  else document.getElementById('container').style.marginLeft = "10px";
  
  if (margintop > 0) document.getElementById('container').style.marginTop = margintop+"px";
  else document.getElementById('container').style.marginTop = "30px";
}

function closeselectors ()
{
  var selector = document.getElementsByClassName('hcmsSelector');
  
  for (var i=0; i<selector.length; i++)
  {
    selector[i].style.visibility = 'hidden';
  }
}

function openBrWindowLink (winName, features)
{
  if (document.getElementById('objectiframe'))
  {
    var theURL = document.getElementById('objectiframe').src;

    if (theURL != "") hcms_openWindow (theURL, winName, features, <?php echo windowwidth ("object"); ?>, <?php echo windowheight ("object"); ?>);
  }
  else alert (hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['no-link-selected'][$lang]); ?>'));
}
</script>
</head>

<body onload="centercontainer();">

<div id="toolbar" style="position:fixed; top:5px; left:5px; text-align:left;">
<?php
if (empty ($mediafile) && !empty ($mgmt_config['screensize']) && is_array ($mgmt_config['screensize']))
{
  $i = 0;
  
  foreach ($mgmt_config['screensize'] as $device => $name_array)
  {
    echo "
  <div onClick=\"closeselectors(); hcms_switchSelector('select_view_".$device."');\" class=\"hcmsButton hcmsButtonSizeWide\"><img src=\"".getthemelocation()."img/icon_".$device.".png\" class=\"hcmsButtonSizeSquare\" id=\"pic_obj_view\" name=\"pic_obj_view\" alt=\"".getescapedtext(ucfirst($device))."\" title=\"".getescapedtext(ucfirst($device))."\" /><img src=\"".getthemelocation()."img/pointer_select.png\" class=\" hcmsButtonSizeNarrow\" alt=\"".getescapedtext(ucfirst($device))."\" title=\"".getescapedtext(ucfirst($device))."\" /></div>
    <div id=\"select_view_".$device."\" class=\"hcmsSelector\" style=\"position:absolute; top:26px; left:".(38 * $i + 5)."px; visibility:hidden; z-index:999; max-height:200px; overflow:auto; overflow-x:hidden; overflow-y:auto; white-space:nowrap;\">";

    foreach ($name_array as $name => $size)
    {
      echo "
    <div class=\"hcmsSelectorItem\" onclick=\"setscreensize('".$size."');\">".getescapedtext ($name." (".$size.")")."&nbsp;</div>";
    }
    
    echo "
  </div>";
  
    $i++;
  }
  
    echo "
  <div onClick=\"closeselectors(); rotate();\" class=\"hcmsButton hcmsButtonSizeSquare\"><img src=\"".getthemelocation()."img/icon_rotate.png\" class=\"hcmsButtonSizeSquare\" id=\"pic_rotate\" name=\"pic_rotate\" alt=\"".getescapedtext($hcms_lang['rotate'][$lang])."\" title=\"".getescapedtext($hcms_lang['rotate'][$lang])."\" /></div>";
}
?>
<?php if ($cat == "page") { ?>
  <div onClick="openBrWindowLink('preview', 'scrollbars=yes,resizable=yes')" class="hcmsButton hcmsButtonSizeSquare"><img name="ButtonView" src="<?php echo getthemelocation(); ?>img/icon_newwindow.png" class="hcmsButtonSizeSquare" align="absmiddle" alt="<?php echo getescapedtext ($hcms_lang['in-new-browser-window'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['in-new-browser-window'][$lang]); ?>" /></div>
<?php } ?>
</div>

<div id="container">
<?php if (!empty ($objectview)) echo $objectview; ?>
</div>

</body>
</html>
