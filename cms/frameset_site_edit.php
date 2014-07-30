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
$site = url_encode (getrequest ("site", "url")); // site can be *Null* which is not a valid name!
$site_name = url_encode (getrequest ("site_name", "url"));
$preview = url_encode (getrequest ("preview", "url"));

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
  <frame name="menuFrame" scrolling="NO" noresize src="<?php echo "site_edit_menu.php?site=".$site."&preview=".$preview."&site_name=".$site_name; ?>" />
  <frame name="mainFrame2" src="<?php echo "site_edit_form.php?site=".$site."&preview=".$preview."&site_name=".$site_name; ?>" />
</frameset>
<noframes></noframes>
</html>