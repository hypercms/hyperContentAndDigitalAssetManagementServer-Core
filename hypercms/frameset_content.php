<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
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
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<meta name="viewport" content="width=800; initial-scale=1.0; user-scalable=1;">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" language="JavaScript" type="text/javascript"></script>
<script language="JavaScript">
<!--
function minControlFrame ()
{
  if (document.getElementById('controlLayer'))
  {
    var height = 44;
    
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
-->
</script>
</head>

<body style="width:100%; height:100%; margin:0; padding:0;">
<?php
// open an object 
if (isset ($page) && $page != "")
{
  echo "  <div id=\"controlLayer\" style=\"position:fixed; top:0; right:0; left:0; height:100px; margin:0; padding:0;\"><iframe id=\"controlFrame\" name=\"controlFrame\" src=\"loading.php\" scrolling=\"no\" style=\"width:100%; height:100px; border:0; margin:0; padding:0;\"></iframe></div>\n";
  echo "  <div id=\"objLayer\" class=\"hcmsWorkplaceObjectlist\" style=\"position:fixed; top:100px; right:0; bottom:0; left:0; margin:0; padding:0;\"><iframe id=\"objFrame\" name=\"objFrame\" src=\"page_view.php?ctrlreload=".$ctrlreload."&location=".$location."&page=".$page."\" scrolling=\"auto\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
}
// open a location  
elseif (isset ($location) && $location != "")
{
  echo "  <div id=\"controlLayer\" style=\"position:fixed; top:0; right:0; left:0; height:100px; margin:0; padding:0;\"><iframe id=\"controlFrame\" name=\"controlFrame\" src=\"control_content_menu.php?location=".$location."\" scrolling=\"no\" style=\"width:100%; height:100px; border:0; margin:0; padding:0;\"></iframe></div>\n";
  echo "  <div id=\"objLayer\" class=\"hcmsWorkplaceObjectlist\" style=\"position:fixed; top:100px; right:0; bottom:0; left:0; margin:0; padding:0;\"><iframe id=\"objFrame\" name=\"objFrame\" src=\"empty.php\" scrolling=\"auto\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
}
?>
</body>
</html>