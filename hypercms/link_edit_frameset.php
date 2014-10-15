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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
</head>
<frameset id="mainFrame" cols="250,*" framespacing="2" frameborder="no" border="0" class="hcmsNavigator">
  <?php echo "<frame name=\"navFrame2\" src=\"link_edit_explorer.php?site=".$site."&cat=".$cat."\" />"; ?>
  <?php echo "<frame name=\"mainFrame2\" src=\"link_edit_page.php?view=".$view."&savetype=".$savetype."&site=".$site."&cat=".$cat."&location=".$location."&page=".$page."&db_connect=".$db_connect."&tagname=".$tagname."&id=".$id."&label=".$label."&linkhref_curr=".$linkhref_curr."&linkhref=".$linkhref."&linktarget=".$linktarget."&targetlist=".$targetlist."&linktext=".$linktext."&contenttype=".$contenttype."\" />"; ?>
</frameset>
<noframes></noframes>
</html>