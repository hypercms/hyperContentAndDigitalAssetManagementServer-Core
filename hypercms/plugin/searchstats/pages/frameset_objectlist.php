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
require ("../../../config.inc.php");
// hyperCMS API
require ("../../../function/hypercms_api.inc.php");


// input parameters
$action = url_encode (getrequest ("action", "url"));
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
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="../../../javascript/main.js" type="text/javascript"></script>
<meta name="viewport" content="width=1024; initial-scale=1.0; user-scalable=1;" />
</head>
<body style="width:100%; height:100%; margin:0; padding:0;">
<?php
// scrolling for control frame
if ($is_mobile) $scrolling = "YES";
else $scrolling = "NO";

// object list width in %
if ($temp_sidebar && !$is_mobile) $objectlist_width = 75;
else $objectlist_width = 100;

// search from top frame
if ($action == "base_search")
{
  // control
  echo "  <iframe id=\"controlFrame\" name=\"controlFrame\" scrolling=\"".$scrolling."\" src=\"../../../loading.php\" border=\"0\" frameBorder=\"0\" style=\"width:100%; height:100px; border:0; margin:0; padding:0; float:left;\"></iframe>\n";
  echo "  <div id=\"mainLayer\" style=\"position:fixed; top:100px; bottom:0; left:0; width:".$objectlist_width."%; margin:0; padding:0;\"><iframe id=\"mainFrame\" name=\"mainFrame\" scrolling=\"no\" src=\"../../../search_objectlist.php?action=".$action."&search_expression=".$search_expression."&maxhits=".$maxhits."\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
  
  // sidebar
  if (!$is_mobile) echo "  <div id=\"sidebarLayer\" style=\"position:fixed; top:100px; right:0; bottom:0; width:".(100 - $objectlist_width)."%; margin:0; padding:0;\"><iframe id=\"sidebarFrame\" scrolling=\"auto\" name=\"sidebarFrame\" src=\"../../../explorer_preview.php\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
}
?>
</body>
</html>