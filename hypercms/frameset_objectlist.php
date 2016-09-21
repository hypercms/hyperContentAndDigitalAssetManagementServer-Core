<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.comf
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
$action = url_encode (getrequest ("action", "url"));
$site = url_encode (getrequest ("site")); // site can be %Null%
$login = url_encode (getrequest ("login", "objectname"));
$location = url_encode (getrequest ("location", "url"));
$virtual = url_encode (getrequest ("virtual", "numeric"));
$search_dir = url_encode (getrequest ("search_dir", "url"));
$search_expression = url_encode (getrequest ("search_expression", "url"));
$maxhits = url_encode (getrequest ("maxhits", "url"));

// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script type="text/javascript" language="JavaScript" src="javascript/main.js"></script>
<script language="JavaScript">
function openobjectview (location, object, view)
{
  var width = Math.max(document.documentElement.clientWidth, window.innerWidth || 0)
  var height = Math.max(document.documentElement.clientHeight, window.innerHeight || 0)

  document.getElementById('objectview').src = 'explorer_objectview.php?location=' + location + '&page=' + object + '&width=' + width + '&height=' + height + '&view=' + view;
  hcms_showInfo('objectviewLayer',0);
}

function closeobjectview ()
{
  document.getElementById('objectview').src = '';
  hcms_hideInfo('objectviewLayer');
}
</script>
</head>

<body style="width:100%; height:100%; margin:0; padding:0;">

<!-- preview/live-view --> 
<div id="objectviewLayer" class="hcmsWorkplaceExplorer" style="display:none; overflow:hidden; position:fixed; margin:0; padding:0; left:0; top:0; right:0; bottom:0; z-index:8;">
  <div style="position:fixed; right:5px; top:5px; z-index:9;">
    <img name="hcms_mediaClose" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="closeobjectview();" />
  </div>
  <iframe id="objectview" src="" scrolling="no" frameBorder="0" <?php if (!$is_iphone) echo 'style="width:100%; height:100%; border:0; margin:0; padding:0;"'; ?>></iframe>
</div>

<?php
// scrolling for control frame
if ($is_mobile) $scrolling = "YES";
else $scrolling = "NO";

// object list width in pixel
if ($temp_sidebar && !$is_mobile) $sidebar_width = 330;
else $sidebar_width = 0;

// search from top frame
if ($action == "base_search")
{
  // control
  echo "  <iframe id=\"controlFrame\" name=\"controlFrame\" scrolling=\"".$scrolling."\" src=\"loading.php\" frameBorder=\"0\" style=\"position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0;\"></iframe>\n";
  // object list
  echo "  <div id=\"mainLayer\" style=\"position:fixed; top:100px; bottom:0; left:0; right:".$sidebar_width."px; margin:0; padding:0;\"><iframe id=\"mainFrame\" name=\"mainFrame\" scrolling=\"no\" src=\"search_objectlist.php?action=".$action."&site=".$site."&search_dir=".$search_dir."&search_expression=".$search_expression."&maxhits=".$maxhits."\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
  // sidebar
  if (!$is_mobile) echo "  <div id=\"sidebarLayer\" style=\"position:fixed; top:100px; right:0; bottom:0; width:".$sidebar_width."px; margin:0; padding:0;\"><iframe id=\"sidebarFrame\" name=\"sidebarFrame\" scrolling=\"auto\" src=\"explorer_preview.php\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
}
// search for files of a user
elseif ($action == "user_files")
{
  // control
  echo "  <iframe id=\"controlFrame\" name=\"controlFrame\" scrolling=\"".$scrolling."\" src=\"loading.php\" frameBorder=\"0\" style=\"position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0;\"></iframe>\n";
  // object list
  echo "  <div id=\"mainLayer\" style=\"position:fixed; top:100px; bottom:0; left:0; right:".$sidebar_width."px; margin:0; padding:0;\"><iframe id=\"mainFrame\" name=\"mainFrame\" scrolling=\"no\" src=\"search_objectlist.php?action=".$action."&site=".$site."&login=".$login."\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
  // sidebar
  if (!$is_mobile) echo "  <div id=\"sidebarLayer\" style=\"position:fixed; top:100px; right:0; bottom:0; width:".$sidebar_width."px; margin:0; padding:0;\"><iframe id=\"sidebarFrame\" scrolling=\"auto\" name=\"sidebarFrame\" src=\"explorer_preview.php\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
}
// favorites and checked out objects
elseif ($action == "favorites" || $action == "checkedout")
{
  // control
  echo "  <iframe id=\"controlFrame\" name=\"controlFrame\" scrolling=\"".$scrolling."\" src=\"control_objectlist_menu.php?virtual=1&from_page=checkedout\" frameBorder=\"0\" style=\"position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0;\"></iframe></div>\n";
  // object list
  echo "  <div id=\"mainLayer\" style=\"position:fixed; top:100px; bottom:0; left:0; right:".$sidebar_width."px; margin:0; padding:0;\"><iframe id=\"mainFrame\" name=\"mainFrame\" scrolling=\"no\" src=\"search_objectlist.php?action=".$action."\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
  // sidebar
  if (!$is_mobile) echo "  <div id=\"sidebarLayer\" style=\"position:fixed; top:100px; right:0; bottom:0; width:".$sidebar_width."px; margin:0; padding:0;\"><iframe id=\"sidebarFrame\" scrolling=\"auto\" name=\"sidebarFrame\" src=\"explorer_preview.php\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
}
// standard object explorer for given location
elseif ($location != "" || is_array ($hcms_linking))
{
  if (!isset ($virtual)) $virtual = 0;

  // control
  echo "  <iframe id=\"controlFrame\" name=\"controlFrame\" scrolling=\"".$scrolling."\" src=\"control_objectlist_menu.php?location=".$location."&virtual=".$virtual."\" frameBorder=\"0\" style=\"position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0;\"></iframe>\n";
  // object list
  echo "  <div id=\"mainLayer\" style=\"position:fixed; top:100px; bottom:0; left:0; right:".$sidebar_width."px; margin:0; padding:0;\"><iframe id=\"mainFrame\" name=\"mainFrame\" scrolling=\"no\" src=\"explorer_objectlist.php?location=".$location."&virtual=".$virtual."\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
  // sidebar
  if (!$is_mobile) echo "  <div id=\"sidebarLayer\" style=\"position:fixed; top:100px; right:0; bottom:0; width:".$sidebar_width."px; margin:0; padding:0;\"><iframe id=\"sidebarFrame\" name=\"sidebarFrame\" scrolling=\"auto\" src=\"explorer_preview.php\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
}
// no action
else
{
  // control
  echo "  <iframe id=\"controlFrame\" name=\"controlFrame\" scrolling=\"".$scrolling."\" src=\"loading.php\" frameBorder=\"0\" style=\"position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0;\"></iframe></div>\n";
  // object list
  echo "  <div id=\"mainLayer\" style=\"position:fixed; top:100px; bottom:0; left:0; right:".$sidebar_width."px; margin:0; padding:0;\"><iframe id=\"mainFrame\" name=\"mainFrame\" scrolling=\"no\" src=\"empty.php\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
  // sidebar
  if (!$is_mobile) echo "  <div id=\"sidebarLayer\" style=\"position:fixed; top:100px; right:0; bottom:0; width:".$sidebar_width."px; margin:0; padding:0;\"><iframe id=\"sidebarFrame\" scrolling=\"auto\" name=\"sidebarFrame\" src=\"explorer_preview.php\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
}
?>
</body>
</html>