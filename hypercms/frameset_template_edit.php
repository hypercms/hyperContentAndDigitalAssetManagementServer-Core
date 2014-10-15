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
$template = url_encode (getrequest ("template", "url"));

// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
</head>
<frameset rows="24,*" frameborder="NO" border="0" framespacing="0">
  <frame name="menuFrame" scrolling="NO" noresize src="<?php echo "template_edit_menu.php?site=".$site."&cat=".$cat."&template=".$template; ?>" />
  <frame name="mainFrame2" src="<?php echo "template_edit.php?site=".$site."&cat=".$cat."&template=".$template; ?>" />
</frameset>
<noframes></noframes>
</html>