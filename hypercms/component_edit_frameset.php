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
$view = url_encode (getrequest ("view", "url"));
$site = url_encode (getrequest ("site", "url"));
$cat = url_encode (getrequest ("cat", "url"));
$compcat = url_encode (getrequest ("compcat", "url"));
$location = url_encode (getrequest ("location", "url"));
$page = url_encode (getrequest ("page", "url"));
$id = url_encode (getrequest ("id", "url"));
$tagname = url_encode (getrequest ("tagname", "url"));
$component_curr = url_encode (getrequest ("component_curr", "url"));
$component = url_encode (getrequest ("component", "url"));
$condition = url_encode (getrequest ("condition", "url"));

// check session of user
checkusersession ($user);
?>
<!DOCTYPE HTML>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<meta name="viewport" content="width=800; initial-scale=1.0; user-scalable=1;">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
function adjust_height ()
{
  var setheight = hcms_getDocHeight();  
  
  document.getElementById('navFrame2').style.height = setheight + "px";
  document.getElementById('mainFrame2').style.height = setheight + "px";
}

function adjust_width (navFrameWidth)
{
  var width = hcms_getDocWidth();  
  if (!navFrameWidth) navFrameWidth = 250;  
  setwidth = width - navFrameWidth;
  
  document.getElementById('mainFrame2').style.width = setwidth + "px";
}

function minNavFrame ()
{
  if (document.getElementById('navFrame2'))
  {
    var width = 42;
    
    document.getElementById('navFrame2').style.width = width + 'px';
    document.getElementById('mainFrame2').style.left = width + 'px';
    adjust_width (width);
  }
}

function maxNavFrame ()
{
  if (document.getElementById('navFrame2'))
  {
    var width = 250;
    
    document.getElementById('navFrame2').style.width = width + 'px';
    document.getElementById('mainFrame2').style.left = width + 'px';
    adjust_width (width);
  }
}
-->
</script>
</head>

<body style="width:100%; height:100%; margin:0; padding:0;" onload="adjust_height(); adjust_width();" onresize="adjust_height(); adjust_width();">
  <?php
  echo "<iframe id=\"navFrame2\" name=\"navFrame2\" scrolling=\"auto\" src=\"component_edit_explorer.php?site=".$site."&cat=".$cat."&compcat=".$compcat."&location=".$location."&page=".$page."&mediatype=comp\" style=\"position:fixed; top:0px; left:0px; width:250px; height:100%; border:0; margin:0; padding:0;\"></iframe>\n";

  if ($compcat == "single")
  {
    echo "<iframe id=\"mainFrame2\" name=\"mainFrame2\" scrolling=\"auto\" src=\"component_edit_page_single.php?view=".$view."&site=".$site."&cat=".$cat."&location=".$location."&page=".$page."&id=".$id."&tagname=".$tagname."&compcat=".$compcat."&component_curr=".$component_curr."&component=".$component."&condition=".$condition."\" style=\"position:fixed; top:0px; left:250px; width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe>\n";
  }
  elseif ($compcat == "multi")
  {
    echo "<iframe id=\"mainFrame2\" name=\"mainFrame2\" scrolling=\"auto\" src=\"component_edit_page_multi.php?view=".$view."&site=".$site."&cat=".$cat."&location=".$location."&page=".$page."&id=".$id."&tagname=".$tagname."&compcat=".$compcat."&condition=".$condition."\" style=\"position:fixed; top:0px; left:250px; width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe>\n";
  }
  ?>
</body>
</html>