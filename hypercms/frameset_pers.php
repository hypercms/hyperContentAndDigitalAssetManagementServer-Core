<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");


// input parameters
$site = url_encode (getrequest ("site", "url"));
$cat = url_encode (getrequest ("cat", "url"));

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// write and close session (non-blocking other frames)
if (session_id() != "") session_write_close();
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=<?php echo windowwidth ("object"); ?>, initial-scale=1.0, maximum-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
</head>

<body>
  <iframe name="controlFrame" src="<?php echo "control_pers_menu.php?site=".$site."&cat=".$cat; ?>" frameBorder="0" scrolling="no" style="position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0; overflow:hidden;"></iframe>
  <div style="position:fixed; top:100px; right:0; bottom:0; left:0; margin:0; padding:0;">
    <iframe name="mainFrame" src="<?php echo "empty.php?site=".$site; ?>" frameborder="0" <?php if (!$is_mobile) echo 'scrolling="no"'; else echo 'scrolling="yes"'; ?> style="width:100%; height:100%; border:0; margin:0; padding:0; <?php if (!$is_mobile) echo "overflow:auto;"; else echo "overflow:scroll;"; ?>"></iframe>
  </div>
</body>
</html>