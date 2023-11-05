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
// load file extension defintions
require_once ("include/format_ext.inc.php");


// input parameters
$site = getrequest_esc ("site", "publicationname");
$dir = getrequest_esc ("dir", "locationname");
$compcat = getrequest_esc ("compcat", "objectname");
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$mediatype = getrequest_esc ("mediatype", "objectname");
$search_expression = getrequest ("search_expression");
$search_format = getrequest ("search_format", "array");
$scaling = getrequest ("scaling", "numeric", "1");

// publication management config
if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
{
  require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
}

// ------------------------------ permission section --------------------------------

// check access permission
if ($dir != "" && $dir != "%comp%/") $site_temp = getpublication ($dir);
else $site_temp = $site;

if (
     ($dir != "" && $dir != "%comp%/" && !accessgeneral ($site_temp, $dir, "comp")) || 
     !valid_publicationname ($site)
   ) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/click.min.js"></script>
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<link rel="stylesheet" href="javascript/jquery-ui/jquery-ui.css" />
</head>

<body class="hcmsWorkplaceObjectlist">

<div id="NavFrameButtons" style="position:fixed; right:0; top:45%; margin:0; padding:0;">
  <img onclick="parent.minNavFrame();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_arrow_left.png" /><br />
  <img onclick="parent.maxNavFrame();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_arrow_right.png" />
</div>

<div id="Navigator" class="hcmsWorkplaceFrame">
<?php
if ($compcat == "media" || !empty ($mgmt_config[$site]['dam'])) $view = "gallery";
else $view = "list";

echo showcompexplorer ($site, $dir, $location, $page, $compcat, $search_expression, $search_format, $mediatype, $lang, "", $scaling, $view, 120);
?>
</div>

</body>
</html>
