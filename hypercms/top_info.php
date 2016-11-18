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
<script src="javascript/click.js" type="text/javascript"></script>
<script>
<!--
function popupfocus ()
{
  self.focus();
  setTimeout('popupfocus()', 10);
}

popupfocus ();
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric" leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>

<div style="width:100%; padding:5px; background-color:#FFFFFF;">
  <img src="<?php echo getthemelocation(); ?>img/logo.png" alt="hyperCMS" />&nbsp;&nbsp;&nbsp;<?php echo $version; ?>
</div>

<div style="width:100%; padding:5px;">
  <textarea name="textarea" style="width:620px; height:290px;"><?php @include ("license.txt"); ?></textarea>
</div>
  
</body>
</html>
