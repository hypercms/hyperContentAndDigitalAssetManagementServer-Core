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
$savetype = url_encode (getrequest ("savetype", "url"));
$site = url_encode (getrequest ("site", "url"));
$cat = url_encode (getrequest ("cat", "url"));
$compcat = url_encode (getrequest ("compcat", "url"));
$location = url_encode (getrequest ("location", "url"));
$page = url_encode (getrequest ("page", "url"));
$db_connect = url_encode (getrequest ("db_connect", "url"));
$contenttype = url_encode (getrequest ("contenttype", "url"));
$id = url_encode (getrequest ("id", "url"));
$label = url_encode (getrequest ("label", "url"));
$tagname = url_encode (getrequest ("tagname", "url"));
$linkhref_curr = url_encode (getrequest ("linkhref_curr", "url"));
$linkhref = url_encode (getrequest ("linkhref", "url"));
$linktarget = url_encode (getrequest ("linktarget", "url"));
$targetlist = url_encode (getrequest ("targetlist", "url"));
$linktext = url_encode (getrequest ("linktext", "url"));

// check session of user
checkusersession ($user, false);
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
  <iframe id="navFrame2" name="navFrame2" scrolling="auto" src="<?php echo "link_edit_explorer.php?site=".$site."&cat=".$cat; ?>" style="position:fixed; top:0px; left:0px; width:250px; height:100%; border:0; margin:0; padding:0;"></iframe>
  <iframe id="mainFrame2" name="mainFrame2" scrolling="auto" src="<?php echo "link_edit_page.php?view=".$view."&savetype=".$savetype."&site=".$site."&cat=".$cat."&location=".$location."&page=".$page."&db_connect=".$db_connect."&tagname=".$tagname."&id=".$id."&label=".$label."&linkhref_curr=".$linkhref_curr."&linkhref=".$linkhref."&linktarget=".$linktarget."&targetlist=".$targetlist."&linktext=".$linktext."&contenttype=".$contenttype; ?>" style="position:fixed; top:0px; left:250px; width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
</body>
</html>