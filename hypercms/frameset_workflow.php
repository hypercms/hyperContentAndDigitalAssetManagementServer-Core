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
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<meta name="viewport" content="width=800; initial-scale=1.0; user-scalable=1;">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" language="JavaScript" type="text/javascript"></script>
</head>

<body style="width:100%; height:100%; margin:0; padding:0;">
  <iframe id="controlFrame" name="controlFrame" scrolling="NO" src="<?php echo "control_workflow_menu.php?site=".$site."&cat=".$cat; ?>" style="position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0;"></iframe>
  <div style="position:fixed; top:100px; right:0; bottom:0; left:0; margin:0; padding:0;">
    <iframe id="mainFrame" name="mainFrame" src="empty.php?site=<?php echo $site; ?>" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
  </div>
</body>
</html>