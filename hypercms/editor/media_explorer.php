<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("../include/session.inc.php");
// management configuration
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");
// hyperCMS UI
require ("../function/hypercms_ui.inc.php");
// load formats/file extensions
require_once ("../include/format_ext.inc.php");
// load language file
require_once ("../language/component_edit_explorer.inc.php");


// input parameters
$site = getrequest_esc ("site", "publicationname");
$dir = getrequest_esc ("dir", "locationname");
$compcat = getrequest_esc ("compcat");
$mediatype = getrequest_esc ("mediatype");
$callback = getrequest_esc ("callback", false, "", true);
if ($lang == "") $lang = getrequest_esc ("lang");
$search_expression = getrequest ("search_expression");
$scaling = getrequest ("scaling", "numeric", "1");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check access permission
if ($dir != "" && $dir != "%comp%/") $site_temp = getpublication ($dir);
else $site_temp = $site;

if (
     ($mediatype != "image" && $mgmt_config[$site]['dam'] == true) || 
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
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/navigator.css">
<script src="../javascript/click.js" type="text/javascript"></script>
<script src="../javascript/main.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceObjectlist">
  <div style="position:fixed; right:0; top:45%; margin:0; padding:0;">
    <img onclick="parent.minNavFrame();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_arrow_left.png" /><br />
    <img onclick="parent.maxNavFrame();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_arrow_right.png" />
  </div>
  <div class="hcmsWorkplaceFrame">
    <?php echo showcompexplorer ($site, $dir, "", "", $compcat, $search_expression, $mediatype, $lang, $callback, $scaling); ?>
  </div>

</body>
</html>
