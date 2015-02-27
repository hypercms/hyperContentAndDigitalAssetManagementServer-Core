<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("include/session.inc.php");
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
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<meta name="viewport" content="width=1024; initial-scale=1.0; user-scalable=1;">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" language="JavaScript" type="text/javascript"></script>
</head>

<body style="width:100%; height:100%; margin:0; padding:0;">
<?php
// scrolling for control frame
if ($is_mobile) $scrolling = "YES";
else $scrolling = "NO";

// object list width in %
if ($temp_sidebar && !$is_mobile) $objectlist_width = 75;
else $objectlist_width = 100;

// standard object explorer for given location
if (($location != "" || is_array ($hcms_linking)))
{
  if (!isset ($virtual)) $virtual = 0;

  // control
  echo "  <iframe id=\"controlFrame\" name=\"controlFrame\" scrolling=\"".$scrolling."\" src=\"control_objectlist_menu.php?location=".$location."&virtual=".$virtual."\" style=\"position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0;\"></iframe>\n";
  // object list
  echo "  <div id=\"mainLayer\" style=\"position:fixed; top:100px; bottom:0; left:0; width:".$objectlist_width."%; margin:0; padding:0;\"><iframe id=\"mainFrame\" name=\"mainFrame\" scrolling=\"no\" src=\"explorer_objectlist.php?location=".$location."&virtual=".$virtual."\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
  // sidebar
  if (!$is_mobile) echo "  <div id=\"sidebarLayer\" style=\"position:fixed; top:100px; right:0; bottom:0; width:".(100 - $objectlist_width)."%; margin:0; padding:0;\"><iframe id=\"sidebarFrame\" name=\"sidebarFrame\" scrolling=\"auto\" src=\"explorer_preview.php\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
}
// search from top frame
elseif ($action == "base_search")
{
  // control
  echo "  <iframe id=\"controlFrame\" name=\"controlFrame\" scrolling=\"".$scrolling."\" src=\"loading.php\" style=\"position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0;\"></iframe>\n";
  // object list
  echo "  <div id=\"mainLayer\" style=\"position:fixed; top:100px; bottom:0; left:0; width:".$objectlist_width."%; margin:0; padding:0;\"><iframe id=\"mainFrame\" name=\"mainFrame\" scrolling=\"no\" src=\"search_script_rdbms.php?action=".$action."&search_dir=".$search_dir."&search_expression=".$search_expression."&maxhits=".$maxhits."\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
  // sidebar
  if (!$is_mobile) echo "  <div id=\"sidebarLayer\" style=\"position:fixed; top:100px; right:0; bottom:0; width:".(100 - $objectlist_width)."%; margin:0; padding:0;\"><iframe id=\"sidebarFrame\" name=\"sidebarFrame\" scrolling=\"auto\" src=\"explorer_preview.php\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
}
// search for files of a user
elseif ($action == "user_files")
{
  // control
  echo "  <iframe id=\"controlFrame\" name=\"controlFrame\" scrolling=\"".$scrolling."\" src=\"loading.php\" style=\"position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0;\"></iframe>\n";
  // object list
  echo "  <div id=\"mainLayer\" style=\"position:fixed; top:100px; bottom:0; left:0; width:".$objectlist_width."%; margin:0; padding:0;\"><iframe id=\"mainFrame\" name=\"mainFrame\" scrolling=\"no\" src=\"search_script_rdbms.php?action=".$action."&site=".$site."&login=".$login."\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
  // sidebar
  if (!$is_mobile) echo "  <div id=\"sidebarLayer\" style=\"position:fixed; top:100px; right:0; bottom:0; width:".(100 - $objectlist_width)."%; margin:0; padding:0;\"><iframe id=\"sidebarFrame\" scrolling=\"auto\" name=\"sidebarFrame\" src=\"explorer_preview.php\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
}
// checked out objects
elseif ($action == "checkedout")
{
  // control
  echo "  <iframe id=\"controlFrame\" name=\"controlFrame\" scrolling=\"".$scrolling."\" src=\"control_objectlist_menu.php?virtual=1&from_page=checkedout\" style=\"position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0;\"></iframe></div>\n";
  // object list
  echo "  <div id=\"mainLayer\" style=\"position:fixed; top:100px; bottom:0; left:0; width:".$objectlist_width."%; margin:0; padding:0;\"><iframe id=\"mainFrame\" name=\"mainFrame\" scrolling=\"no\" src=\"page_checkedout.php\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
  // sidebar
  if (!$is_mobile) echo "  <div id=\"sidebarLayer\" style=\"position:fixed; top:100px; right:0; bottom:0; width:".(100 - $objectlist_width)."%; margin:0; padding:0;\"><iframe id=\"sidebarFrame\" scrolling=\"auto\" name=\"sidebarFrame\" src=\"explorer_preview.php\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
}
?>
</body>
</html>