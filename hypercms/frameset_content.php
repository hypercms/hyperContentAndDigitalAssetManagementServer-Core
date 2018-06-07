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
$location = url_encode (getrequest ("location", "url"));
$page = url_encode (getrequest ("page", "url"));
$ctrlreload = url_encode (getrequest ("ctrlreload", "url"));

// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=device-width, initial-scale=0.57, maximum-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script type="text/javascript" src="javascript/jquery/jquery-1.10.2.min.js"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<script>
function setviewport ()
{
  var width = hcms_getViewportWidth();
  alert (width);
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
    var height = 34;
    
    document.getElementById('controlLayer').style.height = height + 'px';
    document.getElementById('objLayer').style.top = height + 'px';
  }
}

function maxControlFrame ()
{
  if (document.getElementById('controlLayer'))
  {
    var height = 100;
    
    document.getElementById('controlLayer').style.height = height + 'px';
    document.getElementById('objLayer').style.top = height + 'px';
  }
}

function openobjectview (location, object, view)
{
  if (location != "" && object != "")
  {
    var width = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
    var height = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
  
    document.getElementById('objectview').src = 'explorer_objectview.php?location=' + location + '&page=' + object + '&width=' + width + '&height=' + height + '&view=' + view;
    hcms_showInfo('objectviewLayer',0);
  }
}

function openimageview (link)
{
  if (link != "")
  {
    var width = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
    var height = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
    
    document.getElementById('objectview').src = 'explorer_imageview.php?link=' + encodeURIComponent(link) + '&width=' + width + '&height=' + height;
    hcms_showInfo('objectviewLayer',0);
  }
}

function opengeoview (ip)
{
  if (ip != "")
  {
    document.getElementById('objectview').src = 'page_info_ip.php?ip=' + encodeURIComponent(ip);
    hcms_showInfo('objectviewLayer',0);
  }
}

function openpopup (link)
{
  if (link != "")
  {
    document.getElementById('objectview').src = link;
    hcms_showInfo('objectviewLayer',0);
  }
}

function closeobjectview ()
{
  document.getElementById('objectview').src = '';
  hcms_hideInfo('objectviewLayer');
}

function openBrWindowLink (url, winName, features)
{
  if (url != "")
  {
    hcms_openWindow (url, winName, features, <?php echo windowwidth ("object"); ?>, <?php echo windowheight ("object"); ?>);
  }
  else alert (hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['no-link-selected'][$lang]); ?>'));
}
</script>
</head>

<body onload="hcms_setViewportScale();">

<!-- popup for preview/live-view --> 
<div id="objectviewLayer" class="hcmsWorkplaceExplorer" style="display:none; overflow:hidden; position:fixed; width:100%; height:100%; margin:0; padding:0; left:0; top:0; z-index:8;">
  <div style="position:fixed; right:5px; top:5px; z-index:9;">
    <img name="hcms_mediaClose" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" align="absmiddle" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="closeobjectview();" />
  </div>
  <iframe id="objectview" src="" <?php if (!$is_mobile) echo 'scrolling="no"'; else echo 'scrolling="yes"'; ?> frameBorder="0" <?php if (!$is_iphone) echo 'style="width:100%; height:100%; border:0; margin:0; padding:0;"'; ?> sandbox="allow-top-navigation allow-same-origin allow-scripts allow-forms" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
</div>

<?php
// iPad and iPhone requires special CSS settings
if ($is_iphone) $css_iphone = " overflow:scroll !important; -webkit-overflow-scrolling:touch !important;";
else $css_iphone = "";

// open an object 
if (isset ($page) && $page != "")
{
  echo "  <div id=\"controlLayer\" style=\"position:fixed; top:0; right:0; left:0; height:100px; margin:0; padding:0;\"><iframe id=\"controlFrame\" name=\"controlFrame\" src=\"loading.php\" scrolling=\"no\" frameBorder=\"0\" style=\"width:100%; height:100px; border:0; margin:0; padding:0;\"></iframe></div>\n";
  echo "  <div id=\"objLayer\" class=\"hcmsWorkplaceObjectlist\" style=\"position:fixed; top:100px; right:0; bottom:0; left:0; margin:0; padding:0;".$css_iphone."\"><iframe allowfullscreen id=\"objFrame\" name=\"objFrame\" src=\"page_view.php?ctrlreload=".$ctrlreload."&location=".$location."&page=".$page."\" scrolling=\"auto\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
}
// open a location  
elseif (isset ($location) && $location != "")
{
  echo "  <div id=\"controlLayer\" style=\"position:fixed; top:0; right:0; left:0; height:100px; margin:0; padding:0;\"><iframe id=\"controlFrame\" name=\"controlFrame\" src=\"control_content_menu.php?location=".$location."\" scrolling=\"no\" frameBorder=\"0\" style=\"width:100%; height:100px; border:0; margin:0; padding:0;\"></iframe></div>\n";
  echo "  <div id=\"objLayer\" class=\"hcmsWorkplaceObjectlist\" style=\"position:fixed; top:100px; right:0; bottom:0; left:0; margin:0; padding:0;".$css_iphone."\"><iframe allowfullscreen id=\"objFrame\" name=\"objFrame\" src=\"empty.php\" scrolling=\"auto\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
}
?>
</body>
</html>