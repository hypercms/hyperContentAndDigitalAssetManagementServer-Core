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
$view = url_encode (getrequest ("view", "url"));
$savetype = url_encode (getrequest ("savetype", "url"));
$site = url_encode (getrequest ("site", "url"));
$cat = url_encode (getrequest ("cat", "url"));
$location = url_encode (getrequest ("location", "url"));
$page = url_encode (getrequest ("page", "url"));
$db_connect = url_encode (getrequest ("db_connect", "url"));
$contenttype = url_encode (getrequest ("contenttype", "url"));
$tagname = url_encode (getrequest ("tagname", "url"));
$id = url_encode (getrequest ("id", "url"));
$label = url_encode (getrequest ("label", "url"));
$mediacat = url_encode (getrequest ("mediacat", "url"));
$mediadir = url_encode (getrequest ("mediadir", "url"));
$mediatype = url_encode (getrequest ("mediatype", "url")); 
$mediafile = url_encode (getrequest ("mediafile", "url"));
$mediaobject_curr = url_encode (getrequest ("mediaobject_curr", "url"));
$mediaobject = url_encode (getrequest ("mediaobject", "url"));
$mediaalttext = url_encode (getrequest ("mediaalttext", "url"));
$mediaalign = url_encode (getrequest ("mediaalign", "url"));
$mediawidth = url_encode (getrequest ("mediawidth", "url"));
$mediaheight = url_encode (getrequest ("mediaheight", "url"));
$scaling = url_encode(getrequest ("scaling", "numeric"));


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
  
  setheight = setheight - 180;
  
  document.getElementById('mainFrame2').style.height = setheight + "px";
}

function adjust_width (navFrameWidth)
{
  var width = hcms_getDocWidth();  
  if (!navFrameWidth) navFrameWidth = 250;  
  var setwidth = width - navFrameWidth;
  
  document.getElementById('controlFrame2').style.width = setwidth + "px";
  document.getElementById('mainFrame2').style.width = setwidth + "px";
}

function minNavFrame ()
{
  if (document.getElementById('navFrame2'))
  {
    var width = 42;
    
    document.getElementById('navFrame2').style.width = width + 'px';
    document.getElementById('controlFrame2').style.left = width + 'px';
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
    document.getElementById('controlFrame2').style.left = width + 'px';
    document.getElementById('mainFrame2').style.left = width + 'px';
    adjust_width (width);
  }
}
-->
</script>
</head>

<body style="width:100%; height:100%; margin:0; padding:0;" onload="adjust_height(); adjust_width();" onresize="adjust_height(); adjust_width();">
  <?php
  if ($mediacat != "comp") echo "<iframe id=\"navFrame2\" name=\"navFrame2\" scrolling=\"auto\" src=\"media_edit_explorer.php?site=".$site."&mediacat=".$mediacat."&mediatype=".$mediatype."\" style=\"position:fixed; top:0px; left:0px; width:250px; height:100%; border:0; margin:0; padding:0;\"></iframe>\n";
  elseif ($mediacat == "comp") echo "<iframe id=\"navFrame2\" name=\"navFrame2\" scrolling=\"auto\" src=\"component_edit_explorer.php?site=".$site."&cat=".$cat."&location=".$location."&page=".$page."&mediatype=".$mediatype."&scaling=".$scaling."&compcat=media\" style=\"position:fixed; top:0px; left:0px; width:250px; height:100%; border:0; margin:0; padding:0;\"></iframe>\n";

  if ($action == "mediafile_delete")
  {
    echo "<iframe id=\"controlFrame2\" name=\"controlFrame2\" scrolling=\"no\" src=\"media_delete.php?site=".$site."&mediacat=".$mediacat."\" style=\"position:fixed; top:0px; left:250px; width:100%; height:180px; border:0; margin:0; padding:0;\"></iframe>\n";
  }
  elseif ($action == "mediafile_preview")
  {
    echo "<iframe id=\"controlFrame2\" name=\"controlFrame2\" scrolling=\"no\" src=\"media_preview.php?site=".$site."&cat=".$cat."\" style=\"position:fixed; top:0px; left:250px; width:100%; height:180px; border:0; margin:0; padding:0;\"></iframe>\n";
  }
  else
  {
    echo "<iframe id=\"controlFrame2\" name=\"controlFrame2\" scrolling=\"no\" src=\"media_edit_page.php?view=".$view."&savetype=".$savetype."&site=".$site."&cat=".$cat."&location=".$location."&page=".$page."&db_connect=".$db_connect."&id=".$id."&label=".$label."&tagname=".$tagname."&mediafile=".$mediafile."&mediaobject_curr=".$mediaobject."&mediaobject=".$mediaobject."&mediaalttext=".$mediaalttext."&mediaalign=".$mediaalign."&mediawidth=".$mediawidth."&mediaheight=".$mediaheight."&scaling=".$scaling."&mediatype=".$mediatype."&contenttype=".$contenttype."\" style=\"position:fixed; top:0px; left:250px; width:100%; height:180px; border:0; margin:0; padding:0;\"></iframe>\n";
  }
  ?>
  <iframe id="mainFrame2" name="mainFrame2" scrolling="auto" src="<?php echo "media_view.php?site=".$site."&mediacat=".$mediacat."&mediafile=".$mediafile."&mediaobject=".$mediaobject."&mediatype=".$mediatype."&scaling=".$scaling; ?>" style="position:fixed; top:180px; left:250px; width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
</body>
</html>