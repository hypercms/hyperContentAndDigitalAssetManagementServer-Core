<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
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
// hyperCMS UI
require ("function/hypercms_ui.inc.php");


// input parameters
$site = getrequest ("site", "publicationnname");
$cat = getrequest ("cat", "objectname");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// check permissions
if (!checkglobalpermission ($site, 'pers') || (!checkglobalpermission ($site, 'perstrack') && !checkglobalpermission ($site, 'persprof')) || $mgmt_config[$site]['dam'] == true || !valid_publicationname ($site)) killsession ($user);
// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php echo showtopbar ($hcms_lang['personalization-scripting'][$lang], $lang); ?>

<!-- content -->
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
<?php
if ($cat == "tracking")
{
  echo "<p class=\"hcmsHeadlineTiny\">".getescapedtext ($hcms_lang['define-variables-and-set-their-values-see-example-for-passive-personalization'][$lang]).":</p>
  if (!\$customer) \$customer['private']=0; else \$customer['private']++;<br /><br /> 
  <p class=\"hcmsHeadlineTiny\">".getescapedtext ($hcms_lang['register-variables-in-session-see-example'][$lang]).":</p>
  \$_SESSION['customer'] = \$customer;<br /> 
  \$_SESSION['product'] = \$product;<br /> 
  <p class=\"hcmsHeadlineTiny\">".getescapedtext ($hcms_lang['define-a-cookie-and-set-its-value-and-an-expiration-time-see-example'][$lang]).":</p>
  setcookie ('cookie['user']', \$username, time() + 31536000);
  <p class=\"hcmsHeadlineTiny\">".getescapedtext ($hcms_lang['get-a-cookie-and-read-its-value-see-example'][$lang]).":</p>
  \$_COOKIE['cookie'];<br />
  \$username = \$cookie['user'];
  <p class=\"hcmsHeadlineTiny\">".getescapedtext ($hcms_lang['please-note-do-not-use-html-code-in-customer-tracking'][$lang])."</p>\n"; 
}
elseif ($cat == "profile")
{
  echo "<p class=\"hcmsHeadlineTiny\">".getescapedtext ($hcms_lang['define-constraints-for-the-display-of-components-see-example'][$lang]).":</p>
  (\$customer['private'] > \$customer['business'] AND \$count>=5) OR \$customer==\"business\"<br /><br />\n";
}
?>
</div>

</body>
</html>