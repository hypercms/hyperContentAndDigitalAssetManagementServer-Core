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

// check session of user
checkusersession ($user, false);

// save selected publication in session
if (valid_publicationname ($site)) $_SESSION['hcms_temp_site'] = url_decode ($site);
else $_SESSION['hcms_temp_site'] = Null;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
</head>
<frameset rows="100,*" frameborder="NO" border="0" framespacing="0">
  <frame name="controlFrame" scrolling="NO" src="control_user_menu.php?site=<?php echo $site; ?>&selectedgroup=_all" noresize />
  <frame name="mainFrame" scrolling="NO" src="user_objectlist.php?site=<?php echo $site; if (url_decode ($site) != "*Null*") echo "&group=_all"; ?>" />
</frameset>
<noframes></noframes>
</html>