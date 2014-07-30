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

// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
</head>

<frameset id="mainFrame" cols="240,*" framespacing="2" frameborder="no" border="0" class="hcmsNavigator">
  <frame name="navFrame2" src="<?php echo "folder_explorer.php?site=".$site."&cat=".$cat; ?>" />
  <frame name="mainFrame2" src="<?php echo "workflow_folder_form.php?site=".$site."&cat=".$cat; ?>" />
</frameset>
<noframes></noframes>

</html>