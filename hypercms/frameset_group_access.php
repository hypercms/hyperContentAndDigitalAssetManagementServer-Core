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
$site = url_encode (getrequest ("site", "url"));
$cat = url_encode (getrequest ("cat", "url"));
$group_name = url_encode (getrequest ("group_name", "url"));

// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
</head>
  <frameset cols="240,*" frameborder="no" framespacing="0" border="0" class="hcmsNavigator">
    <?php echo "<frame name=\"navFrame2\" scrolling=\"auto\" src=\"group_access_explorer.php?site=".$site."&group_name=".$group_name."&cat=".$cat."\" />\n"; ?>
    <?php echo "<frame name=\"mainFrame2\" scrolling=\"auto\" src=\"group_access_form.php?site=".$site."&group_name=".$group_name."&cat=".$cat."\" />\n"; ?>
  </frameset>
<noframes></noframes>
</html>