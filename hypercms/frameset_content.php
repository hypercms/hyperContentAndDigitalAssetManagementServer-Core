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
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<meta name="viewport" content="width=800; initial-scale=1.0; user-scalable=1;">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" language="JavaScript" type="text/javascript"></script>
<script language="JavaScript">
<!--
function adjust_height ()
{
  var height = hcms_getDocHeight();  
  
  setheight = height - 100;
  if (document.getElementById('objFrame')) document.getElementById('objFrame').style.height = setheight + "px";
}
-->
</script>
</head>

<body style="width:100%; height:100%; margin:0; padding:0;" onload="adjust_height();" onresize="adjust_height();">
<?php
// open an object 
if (isset ($page) && $page != "")
{
  echo "<iframe id=\"controlFrame\" name=\"controlFrame\" src=\"loading.php\" scrolling=\"no\" style=\"position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0;\"></iframe>\n";
  echo "<iframe id=\"objFrame\" name=\"objFrame\" src=\"page_view.php?ctrlreload=".$ctrlreload."&location=".$location."&page=".$page."\" scrolling=\"auto\" style=\"position:fixed; top:100px; left:0; width:100%; height:80%; border:0; margin:0; padding:0;\"></iframe>\n";
}
// explorer/navigator  
elseif (isset ($location) && $location != "")
{
  echo "<iframe id=\"controlFrame\" name=\"controlFrame\" src=\"control_content_menu.php?location=".$location."\" scrolling=\"no\" style=\"position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0;\"></iframe>\n";
  echo "<iframe id=\"objFrame\" name=\"objFrame\" src=\"empty.php\" scrolling=\"auto\" style=\"position:fixed; top:100px; left:0; width:100%; height:90%; border:0; margin:0; padding:0;\"></iframe>\n";
}
?>
</body>
</html>