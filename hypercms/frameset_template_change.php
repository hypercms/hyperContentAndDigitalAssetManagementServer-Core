<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");


// input parameters
$site = url_encode (getrequest ("site", "url"));
$location = url_encode (getrequest ("location", "url"));
$page = url_encode (getrequest ("page", "url"));
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
<meta name="viewport" content="width=800, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" language="JavaScript" type="text/javascript"></script>
</head>

<body style="width:100%; height:100%; margin:0; padding:0;">
  <iframe id="controlFrame2" name="controlFrame2" scrolling="no" src="<?php echo "template_change.php?location=".$location."&page=".$page; ?>" frameBorder="0" style="position:fixed; top:0; left:0; width:100%; height:80px; border:0; margin:0; padding:0;"></iframe>
  <div style="position:fixed; top:80px; right:0; bottom:0; left:0; margin:0; padding:0;">
    <iframe id="mainFrame2" name="mainFrame2" scrolling="auto" src="<?php echo "template_view.php?site=".$site."&cat=".$cat."&template=".$template; ?>" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
  </div>
</body>
</html>