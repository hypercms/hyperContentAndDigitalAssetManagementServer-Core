<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");


// input parameters
$action = url_encode (getrequest ("action", "url"));
$site = url_encode (getrequest ("site")); // site can be %Null%
$login = url_encode (getrequest ("login", "objectname"));
$location = url_encode (getrequest ("location", "url"));
$virtual = url_encode (getrequest ("virtual", "numeric"));
$search_dir = url_encode (getrequest ("search_dir", "url"));
$container_id = url_encode (getrequest ("container_id", "url"));
$search_expression = url_encode (getrequest ("search_expression", "url"));

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script type="text/javascript" src="javascript/main.min.js"></script>
<script type="text/javascript">

var hcms_objectpath;

function openMainView (link)
{
  parent.openMainView (link);
}

function openObjectView (location, object, view)
{
  var width = Math.max(document.documentElement.clientWidth, window.innerWidth || 0)
  var height = Math.max(document.documentElement.clientHeight, window.innerHeight || 0)

  document.getElementById('objectview').src = 'explorer_objectview.php?location=' + encodeURIComponent(location) + '&page=' + encodeURIComponent(object) + '&width=' + encodeURIComponent(width) + '&height=' + encodeURIComponent(height) + '&view=' + encodeURIComponent(view);
  
  // load screen
  if (document.getElementById('hcmsLoadScreen')) document.getElementById('hcmsLoadScreen').style.display = 'inline';
  
  // object view
  hcms_showFormLayer('objectviewLayer',0);
}

function openPopup (link)
{
  if (link != "")
  {
    document.getElementById('objectview').src = link;
    hcms_showFormLayer('objectviewLayer', 0);
  }
}

function closePopup ()
{
  document.getElementById('objectview').src = '';
  hcms_hideFormLayer('objectviewLayer');
}

function openBrWindowLink (url, winName, features)
{
  if (url != "")
  {
    hcms_openWindow (url, winName, features, <?php echo windowwidth ("object"); ?>, <?php echo windowheight ("object"); ?>);
  }
  else alert (hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['no-link-selected'][$lang]); ?>'));
}

function setSearchLocation (location, name)
{
  parent.setSearchLocation (location, name);
}
</script>
</head>

<body class="hcmsWorkplaceObjectlist">

<!-- load screen --> 
<div id="hcmsLoadScreen" class="hcmsLoadScreen" style="display:inline;"></div>

<!-- popup for preview/live-view and forms (do not use nested fixed positioned div-layers due to MS IE and Edge issue) -->
<div id="objectviewLayer" style="display:none;">
  <div style="position:fixed; right:2px; top:<?php if ($is_mobile) echo "2px;"; else echo "38px;"; ?>; z-index:8001;">
    <img name="hcms_mediaClose" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="closePopup();" />
  </div>
  <div class="hcmsWorkplaceExplorer" style="<?php if ($is_mobile) echo '-webkit-overflow-scrolling:touch !important; overflow-y:scroll !important;'; else echo 'overflow:hidden;'; ?> position:fixed; margin:0; padding:0; left:0; top:<?php if ($is_mobile) echo "0px;"; else echo "36px;"; ?> right:0; bottom:0; z-index:8000;">
    <iframe id="objectview" name="objectview" src="" frameborder="0" style="width:100%; height:100%; margin:0; padding:0; border:0; <?php if (!$is_mobile) echo "overflow:auto;"; else echo "overflow:scroll;"; ?>" sandbox="allow-same-origin allow-scripts allow-forms" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
  </div>
</div>

<!-- frames -->
<?php
// x-scrolling for control frame (HTML5 issue with overflow still requires deprecated scrolling attribute)
if ($is_mobile)
{
  $scrolling = "no";
  $overflow = "auto;";
}
else
{
  $scrolling = "no";
  $overflow = "hidden";
}

// object list width in pixel
if ($temp_sidebar && !$is_mobile) $sidebar_width = 350;
else $sidebar_width = 0;

// set action if not set (in case of access links)
if ($location == "" && $action == "" && linking_valid() == true) $action = "linking";

// search from top frame
if ($action == "base_search")
{
  // control
  echo "  <iframe id=\"controlFrame\" name=\"controlFrame\" src=\"loading.php\" frameborder=\"0\" scrolling=\"".$scrolling."\" style=\"position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0; overflow-x:".$overflow."; overflow-y:hidden;\"></iframe>\n";
  // object list
  echo "  <div id=\"mainLayer\" style=\"position:fixed; top:100px; bottom:0; left:0; right:".$sidebar_width."px; margin:0; padding:0;\"><iframe id=\"mainFrame\" name=\"mainFrame\" src=\"search_objectlist.php?action=".$action."&site=".$site."&search_dir=".$search_dir."&search_expression=".$search_expression."&container_id=".$container_id."\" frameBorder=\"0\" scrolling=\"no\" style=\"width:100%; height:100%; border:0; margin:0; padding:0; overflow:hidden;\"></iframe></div>\n";
  // sidebar
  if (!$is_mobile) echo "  <div id=\"sidebarLayer\" style=\"position:fixed; top:100px; right:0; bottom:0; width:".$sidebar_width."px; margin:0; padding:0;\"><iframe id=\"sidebarFrame\" name=\"sidebarFrame\" src=\"explorer_preview.php\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;\"></iframe></div>\n";
}
// search for files of a user
elseif ($action == "user_files")
{
  // control
  echo "  <iframe id=\"controlFrame\" name=\"controlFrame\" src=\"loading.php\" frameborder=\"0\" scrolling=\"".$scrolling."\" style=\"position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0; overflow-x:".$overflow."; overflow-y:hidden;\"></iframe>\n";
  // object list
  echo "  <div id=\"mainLayer\" style=\"position:fixed; top:100px; bottom:0; left:0; right:".$sidebar_width."px; margin:0; padding:0;\"><iframe id=\"mainFrame\" name=\"mainFrame\" src=\"search_objectlist.php?action=".$action."&site=".$site."&login=".$login."\" frameBorder=\"0\" scrolling=\"no\" style=\"width:100%; height:100%; border:0; margin:0; padding:0; overflow:hidden;\"></iframe></div>\n";
  // sidebar
  if (!$is_mobile) echo "  <div id=\"sidebarLayer\" style=\"position:fixed; top:100px; right:0; bottom:0; width:".$sidebar_width."px; margin:0; padding:0;\"><iframe id=\"sidebarFrame\" name=\"sidebarFrame\" src=\"explorer_preview.php\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;\"></iframe></div>\n";
}
// favorites, checked out, recycle bin or access link objects
elseif ($action == "favorites" || $action == "checkedout" || $action == "clipboard" || $action == "recyclebin" || $action == "linking")
{
  // control
  echo "  <iframe id=\"controlFrame\" name=\"controlFrame\" src=\"control_objectlist_menu.php?virtual=1&from_page=".$action."\" frameborder=\"0\" scrolling=\"".$scrolling."\" style=\"position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0; overflow-x:".$overflow."; overflow-y:hidden;\"></iframe></div>\n";
  // object list
  echo "  <div id=\"mainLayer\" style=\"position:fixed; top:100px; bottom:0; left:0; right:".$sidebar_width."px; margin:0; padding:0;\"><iframe id=\"mainFrame\" name=\"mainFrame\" src=\"search_objectlist.php?action=".$action."\" frameBorder=\"0\" scrolling=\"no\" style=\"width:100%; height:100%; border:0; margin:0; padding:0; overflow:hidden;\"></iframe></div>\n";
  // sidebar
  if (!$is_mobile) echo "  <div id=\"sidebarLayer\" style=\"position:fixed; top:100px; right:0; bottom:0; width:".$sidebar_width."px; margin:0; padding:0;\"><iframe id=\"sidebarFrame\" name=\"sidebarFrame\" src=\"explorer_preview.php\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;\"></iframe></div>\n";
}
// standard object explorer for given location
elseif ($location != "")
{
  if (!isset ($virtual)) $virtual = 0;

  // control
  echo "  <iframe id=\"controlFrame\" name=\"controlFrame\" src=\"control_objectlist_menu.php?location=".$location."&virtual=".$virtual."\" frameborder=\"0\" scrolling=\"".$scrolling."\" style=\"position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0; overflow-x:".$overflow."; overflow-y:hidden;\"></iframe>\n";
  // object list
  echo "  <div id=\"mainLayer\" style=\"position:fixed; top:100px; bottom:0; left:0; right:".$sidebar_width."px; margin:0; padding:0;\"><iframe id=\"mainFrame\" name=\"mainFrame\" src=\"explorer_objectlist.php?location=".$location."&virtual=".$virtual."\" frameBorder=\"0\" scrolling=\"no\" style=\"width:100%; height:100%; border:0; margin:0; padding:0; overflow:hidden;\"></iframe></div>\n";
  // sidebar
  if (!$is_mobile) echo "  <div id=\"sidebarLayer\" style=\"position:fixed; top:100px; right:0; bottom:0; width:".$sidebar_width."px; margin:0; padding:0;\"><iframe id=\"sidebarFrame\" name=\"sidebarFrame\" src=\"explorer_preview.php\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;\"></iframe></div>\n";
}
// no action
else
{
  // control
  echo "  <iframe id=\"controlFrame\" name=\"controlFrame\" src=\"loading.php\" frameborder=\"0\" scrolling=\"".$scrolling."\" style=\"position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0; overflow-x:".$overflow."; overflow-y:hidden;\"></iframe></div>\n";
  // object list
  echo "  <div id=\"mainLayer\" style=\"position:fixed; top:100px; bottom:0; left:0; right:".$sidebar_width."px; margin:0; padding:0;\"><iframe id=\"mainFrame\" name=\"mainFrame\" src=\"empty.php\" frameBorder=\"0\" scrolling=\"no\" style=\"width:100%; height:100%; border:0; margin:0; padding:0; overflow:hidden;\"></iframe></div>\n";
  // sidebar
  if (!$is_mobile) echo "  <div id=\"sidebarLayer\" style=\"position:fixed; top:100px; right:0; bottom:0; width:".$sidebar_width."px; margin:0; padding:0;\"><iframe id=\"sidebarFrame\" name=\"sidebarFrame\" src=\"explorer_preview.php\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;\"></iframe></div>\n";
}
?>
</body>
</html>