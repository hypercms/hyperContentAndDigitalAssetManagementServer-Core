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
$location = url_encode (getrequest ("location", "url"));
$page = url_encode (getrequest ("page", "url"));
$ctrlreload = url_encode (getrequest ("ctrlreload", "url"));

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// write and close session (non-blocking other frames)
if (session_id() != "") session_write_close();
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=device-width, initial-scale=0.57, maximum-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/jquery/jquery-3.5.1.min.js"></script>
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript">

var hcms_objectpath;

function setviewport ()
{
  var width = hcms_getViewportWidth();

  if (width > 0)
  {
    // AJAX request to set viewport width
    $.post("<?php echo $mgmt_config['url_path_cms']; ?>service/setviewport.php", {viewportwidth: width});
    return true;
  }
  else return false;
}

function minControlFrame ()
{
  if (document.getElementById('controlLayer'))
  {
    var height = 36;
    
    document.getElementById('controlLayer').style.transition = "0.3s";
    document.getElementById('controlLayer').style.height = height + 'px';
    document.getElementById('objLayer').style.transition = "0.3s";
    document.getElementById('objLayer').style.top = height + 'px';
  }
}

function maxControlFrame ()
{
  if (document.getElementById('controlLayer'))
  {
    var height = 100;
    
    document.getElementById('controlLayer').style.transition = "0.3s";
    document.getElementById('controlLayer').style.height = height + 'px';
    document.getElementById('objLayer').style.transition = "0.3s";
    document.getElementById('objLayer').style.top = height + 'px';
  }
}

function openPopup (link)
{
  if (link != "")
  {
    document.getElementById('objectview').src = link;
    hcms_showFormLayer('objectviewLayer', 0);
  }
}

function openObjectView (location, object, view)
{
  if (location != "" && object != "")
  {
    var width = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
    var height = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);

    document.getElementById('objectview').src = 'explorer_objectview.php?location=' + encodeURIComponent(location) + '&page=' + encodeURIComponent(object) + '&width=' + width + '&height=' + height + '&view=' + encodeURIComponent(view);
    hcms_showFormLayer('objectviewLayer', 0);
  }
}

function openimageview (link)
{
  if (link != "")
  {
    var width = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
    var height = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);

    document.getElementById('objectview').src = 'explorer_imageview.php?link=' + encodeURIComponent(link) + '&width=' + width + '&height=' + height;
    hcms_showFormLayer('objectviewLayer', 0);
  }
}

function opengeoview (ip)
{
  if (ip != "")
  {
    document.getElementById('objectview').src = 'page_info_ip.php?ip=' + encodeURIComponent(ip);
    hcms_showFormLayer('objectviewLayer', 0);
  }
}

function closePopup ()
{
  document.getElementById('objectview').src = '';
  hcms_hideFormLayer('objectviewLayer');
}

var popupwindow;

function openBrWindowLink (url, winName, features)
{
  if (url != "")
  {
    hcms_openWindow (url, winName, features, <?php echo windowwidth ("object"); ?>, <?php echo windowheight ("object"); ?>);
  }
  else alert (hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['no-link-selected'][$lang]); ?>'));
}

function recognizeFaces (element)
{
  if (element && window.opener)
  {
    return window.opener.top.recognizeFaces (element);
  }
  else return false;
}
</script>
</head>

<body onload="hcms_setViewportScale();">

<!-- popup for preview/live-view (do not used nested fixed positioned div-layers due to MS IE and Edge issue) --> 
<div id="objectviewLayer" style="display:none;">
  <div style="position:fixed; right:18px; top:<?php if ($is_mobile) echo "22px"; else echo "40px"; ?>; z-index:9011;">
    <img name="hcms_mediaClose" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="closePopup();" />
  </div>
  <div class="hcmsWorkplaceExplorer" style="<?php if ($is_mobile) echo '-webkit-overflow-scrolling:touch !important; overflow-y:scroll !important;'; else echo 'overflow:hidden;'; ?> position:fixed; margin:0; padding:0; left:0; top:<?php if ($is_mobile) echo "20px"; else echo "36px"; ?>; right:0; bottom:0; z-index:9010;">
    <iframe id="objectview" name="objectview" src="" sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-popups-to-escape-sandbox" frameBorder="0" <?php if (!$is_mobile) echo 'scrolling="no"'; else echo 'scrolling="yes"'; ?> style="width:100%; height:100%; border:0; margin:0; padding:0; <?php if (!$is_mobile) echo "overflow:auto;"; else echo "overflow:scroll;"; ?>" sandbox="allow-top-navigation allow-same-origin allow-scripts allow-forms" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
  </div>
</div>

<?php
// iPad and iPhone requires special CSS settings
if ($is_iphone) $css_iphone = " overflow:scroll !important; -webkit-overflow-scrolling:touch !important;";
else $css_iphone = "";

// open an object 
if (isset ($page) && $page != "")
{
  echo "
  <div id=\"controlLayer\" style=\"position:fixed; top:0; right:0; left:0; height:100px; margin:0; padding:0;\">
    <iframe id=\"controlFrame\" name=\"controlFrame\" src=\"loading.php\" frameBorder=\"0\" scrolling=\"no\" style=\"width:100%; height:100px; border:0; margin:0; padding:0; overflow:hidden;\"></iframe>
  </div>
  <div id=\"objLayer\" class=\"hcmsWorkplaceObjectlist\" style=\"position:fixed; top:100px; right:0; bottom:0; left:0; margin:0; padding:0;".$css_iphone."\">
    <iframe allowfullscreen id=\"objFrame\" name=\"objFrame\" src=\"page_view.php?ctrlreload=".$ctrlreload."&location=".$location."&page=".$page."\" frameborder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;\"></iframe>
  </div>";
}
// open a location  
elseif (isset ($location) && $location != "")
{
  echo "
  <div id=\"controlLayer\" style=\"position:fixed; top:0; right:0; left:0; height:100px; margin:0; padding:0;\">
    <iframe id=\"controlFrame\" name=\"controlFrame\" src=\"control_content_menu.php?location=".$location."\" frameBorder=\"0\" scrolling=\"no\" style=\"width:100%; height:100px; border:0; margin:0; padding:0; overflow:hidden;\"></iframe>
  </div>
  <div id=\"objLayer\" class=\"hcmsWorkplaceObjectlist\" style=\"position:fixed; top:100px; right:0; bottom:0; left:0; margin:0; padding:0;".$css_iphone."\">
    <iframe allowfullscreen id=\"objFrame\" name=\"objFrame\" src=\"empty.php\" frameborder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;\"></iframe>
  </div>";
}
?>

</body>
</html>