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
// version info
require ("version.inc.php");

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script>
function popupfocus ()
{
  self.focus();
  setTimeout('popupfocus()', 100);
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" onload="popupfocus();">

  <div style="width:100%; height:58px; background-color:#FFFFFF;">
    <div style="float:left; padding:5px;"><a href="http://www.hypercms.com" target="_blank"><img src="<?php echo getthemelocation(); ?>img/logo.png" style="border:0; height:48px;" alt="hypercms.com" /></a></div>
    <div style="float:right; padding:20px; color:#000000;"><?php echo $mgmt_config['version']; ?></div>
  </div>
  
  <div style="position:fixed; top:68px; bottom:20px; left:10px; right:20px;">
    <textarea name="textarea" style="width:100%; height:100%;"><?php @include ("license.txt"); ?></textarea>
  </div>
  
</body>
</html>
