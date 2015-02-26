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
$ip = getrequest ("ip");

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

if ($ip != "")
{
  $json = file_get_contents ("http://ip-api.com/json/".$ip);
  $data = json_decode ($json, true);
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php
echo showtopbar ($hcms_lang['geo-location-of'][$lang]." ".$ip, $lang);
?>

<!-- content -->
<div class="hcmsWorkplaceFrame">

  <iframe width="760" height="400" frameborder="no" scrolling="no" style="margin:10px; border:0;" 
    src="http://maps.google.de/maps?ll=<?php echo $data['lat']; ?>,<?php echo $data['lon']; ?>&amp;ie=UTF8&amp;om=1&amp;iwloc=near
    &amp;z=13&amp;iwloc=addr&amp;output=embed">
  </iframe>
  
  <div style="margin:10px;">
  <?php
  if (is_array ($data)) 
  { 
    echo "<table border=\"0\" celspacing=\"2\" cellpadding=\"1\">\n";
    echo "  <tr class=\"hcmsRowData1\"><td width=\"180\">Country </td><td width=\"300\">".$data['country']." </td></tr>\n";
    echo "  <tr class=\"hcmsRowData2\"><td>Region </td><td>".$data['regionName']." </td></tr>\n";
    echo "  <tr class=\"hcmsRowData1\"><td>City </td><td>".$data['city']." </td></tr>\n";
    echo "  <tr class=\"hcmsRowData2\"><td>ZIP code </td><td>".$data['zip']." </td></tr>\n";
    echo "  <tr class=\"hcmsRowData1\"><td>Latitude </td><td>".$data['lat']." </td></tr>\n";
    echo "  <tr class=\"hcmsRowData2\"><td>Longitude </td><td>".$data['lon']." </td></tr>\n";   
    echo "</table>\n";
  }
  ?>
  </div>
  
</div>

</body>
</html>
