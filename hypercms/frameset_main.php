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

if (empty ($lang)) $lang = "en";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
</head>
<frameset id="topFrame" rows="32,*" cols="*" frameborder="NO" border="0" framespacing="0">
  <frame name="topFrame" scrolling="NO" noresize src="top.php" />
  <frameset id="mainFrame" cols="260,*" frameborder="YES" border="1" framespacing="1">
    <frame name="navFrame" src="explorer.php?refresh=1" />
    <frame name="workplFrame" scrolling="NO" src="frameset_workplace.php" />
  </frameset>
</frameset>
<noframes></noframes>
</html>