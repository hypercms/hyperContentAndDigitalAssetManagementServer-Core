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
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<meta name="viewport" content="width=800; initial-scale=1.0; user-scalable=1;">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
function minNavFrame ()
{
  if (document.getElementById('navFrame2'))
  {
    var width = 42;
    
    document.getElementById('navLayer').style.width = width + 'px';
    document.getElementById('mainLayer').style.left = width + 'px';
  }
}

function maxNavFrame ()
{
  if (document.getElementById('navFrame2'))
  {
    var width = 260;
    
    document.getElementById('navLayer').style.width = width + 'px';
    document.getElementById('mainLayer').style.left = width + 'px';
  }
}
-->
</script>
</head>

<body style="width:100%; height:100%; margin:0; padding:0;">
  <div id="navLayer" style="position:fixed; top:0; bottom:0; left:0; width:260px; margin:0; padding:0;">
    <iframe id="navFrame2" name="navFrame2" scrolling="auto" src="<?php echo "link_edit_explorer.php?site=".$site."&cat=".$cat; ?>" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
  </div>
  <div id="mainLayer" style="position:fixed; top:0; right:0; bottom:0; left:260px; margin:0; padding:0;">
    <iframe id="mainFrame2" name="mainFrame2" scrolling="auto" src="<?php echo "link_edit_page.php?view=".$view."&savetype=".$savetype."&site=".$site."&cat=".$cat."&location=".$location."&page=".$page."&db_connect=".$db_connect."&tagname=".$tagname."&id=".$id."&label=".$label."&linkhref_curr=".$linkhref_curr."&linkhref=".$linkhref."&linktarget=".$linktarget."&targetlist=".$targetlist."&linktext=".$linktext."&contenttype=".$contenttype; ?>" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
  </div>
</body>
</html>