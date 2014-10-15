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
// version info
require ("version.inc.php");

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<meta name="viewport" content="width=device-width; initial-scale=0.9; maximum-scale=1.0; user-scalable=0;"></meta>
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/jquery/jquery-1.9.1.min.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
<!--
// callback for hcms_geolocation
function hcms_geoposition (position)
{
  if (position)
  {
    var latitude = position.coords.latitude;
    var longitude = position.coords.longitude;
  }
  else return false;
  
  if (latitude != "" && longitude != "")
  {
    // AJAX request to set geo location
    $.post("<?php echo $mgmt_config['url_path_cms']; ?>/service/setgeolocation.php", {latitude: latitude, longitude: longitude});

    return true;
  }
  else return false;
}
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric" onload="<?php echo "hcms_geolocation();"; ?>">

<div style="width:90%; margin:auto; padding:10px;">
  <img src="<?php echo getthemelocation(); ?>img/logo_welcome.gif" style="width:<?php if ($is_mobile) echo "260px"; else echo "320px"; ?>" /> <?php echo $version; ?><br /><br />
  <?php
  if ($mgmt_config['welcome'] != "") 
  {
    echo "<iframe width=\"100%\" height=\"600\" src=\"".$mgmt_config['welcome']."\" scrolling=\"no\" frameborder=\"0\" seamless=\"seamless\"></iframe>\n";
  }
  ?>
</div>

</body>
</html>