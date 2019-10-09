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
$template = url_encode (getrequest ("template", "url"));

// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=<?php echo windowwidth ("object"); ?>, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
</head>

<body style="width:100%; height:100%; margin:0; padding:0;">
  <iframe id="controlFrame2" name="controlFrame2" scrolling="no" src="<?php echo "template_edit_menu.php?site=".$site."&cat=".$cat."&template=".$template; ?>" frameBorder="0" style="position:fixed; top:0; left:0; width:100%; height:24px; border:0; margin:0; padding:0;"></iframe>
  <div style="position:fixed; top:24px; right:0; bottom:0; left:0; margin:0; padding:0;">
    <iframe id="mainFrame2" name="mainFrame2" <?php if (!$is_mobile) echo 'scrolling="auto"'; else echo 'scrolling="yes"'; ?> src="<?php echo "template_edit.php?site=".$site."&cat=".$cat."&template=".$template; ?>" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
  </div>
</body>
</html>