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
// hyperCMS UI
require ("function/hypercms_ui.inc.php");


// input parameters
$site = getrequest ("site", "publicationnname");
$cat = getrequest ("cat", "objectname");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// check permissions
if ($globalpermission[$site]['pers'] != 1 || ($globalpermission[$site]['perstrack'] != 1 && $globalpermission[$site]['persprof'] != 1) || $mgmt_config[$site]['dam'] == true || !valid_publicationname ($site)) killsession ($user);
// check session of user
checkusersession ($user, false);
// language file
require_once ("language/pers_help.inc.php");
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php echo showtopbar ($text0[$lang], $lang); ?>

<!-- content -->
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
<?php
if ($cat == "tracking")
{
  echo "<p class=\"hcmsHeadlineTiny\">".$text1[$lang].":</p>
  if (!\$customer) \$customer['private']=0; else \$customer['private']++;<br /><br /> 
  <p class=\"hcmsHeadlineTiny\">".$text4[$lang].":</p>
  \$_SESSION['customer'] = \$customer;<br /> 
  \$_SESSION['product'] = \$product;<br /> 
  <p class=\"hcmsHeadlineTiny\">".$text2[$lang].":</p>
  setcookie ('cookie['user']', \$username, time() + 31536000);
  <p class=\"hcmsHeadlineTiny\">".$text3[$lang].":</p>
  \$_COOKIE['cookie'];<br />
  \$username = \$cookie['user'];
  <p class=\"hcmsHeadlineTiny\">".$text6[$lang]."</p>\n"; 
}
elseif ($cat == "profile")
{
  echo "<p class=\"hcmsHeadlineTiny\">".$text5[$lang].":</p>
  (\$customer['private'] > \$customer['business'] AND \$count>=5) OR \$customer==\"business\"<br /><br />\n";
}
?>
</div>

</body>
</html>