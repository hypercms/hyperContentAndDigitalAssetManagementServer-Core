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


// delete session file of user
$result = killsession ($user, true);

if (empty ($lang)) $lang = "en";

if ($result == true) 
{ 
  $answer = getescapedtext ($hcms_lang['logged-out'][$lang]);
}
else
{ 
  $answer = getescapedtext ($hcms_lang['session-cannot-be-closed'][$lang]);
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="utf-8" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=420; initial-scale=0.9; maximum-scale=1.0; user-scalable=0;">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
</head>

<body class="hcmsStartScreen" onload="<?php if ($result == true) echo "top.location.href='userlogin.php';"; ?>">

<div class="hcmsStartBar" style="width:100%;">
  <div style="position:absolute; top:15px; left:15px; float:left; text-align:left;"><img src="<?php echo getthemelocation(); ?>img/logo.png" style="border:0; height:48px;" alt="hypercms.com" /></div>
  <div style="position:absolute; top:48px; right:15px; text-align:right;"></div>
</div>

<div class="hcmsLogonScreen">
  <p class="hcmsTextGreen">
    <?php echo $user." ".$answer; ?>
    <img name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="location='userlogin.php';" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" />
  </p>
</div>

</body>
</html>