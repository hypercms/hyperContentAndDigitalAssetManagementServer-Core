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


// input parameters
$object_id = getrequest ("object_id");
$container_id = getrequest ("container_id");
$multiobject = getrequest ("multiobject");

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$assets_array = array();

// split into array
if ($object_id != "")
{
  $assets_array = link_db_getobject ($object_id);
}
// convert container ID to object path
elseif ($container_id != "")
{
  $temp_array = rdbms_getobjects ($container_id);

  if (is_array ($temp_array) && sizeof ($temp_array) > 0)
  {
    foreach ($temp_array as $hash => $objectinfo_array)
    {
      $assets_array[] = $objectinfo_array['objectpath'];
    }
  }
}
// split into array
elseif ($multiobject != "")
{
  $assets_array = link_db_getobject ($multiobject);
}

// gallery
$show = showgallery ($assets_array, 140, true, $user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script type="text/javascript" src="javascript/main.min.js"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- content -->
<?php
if (!empty ($show)) echo $show;
?>

<?php includefooter(); ?>
</body>
</html>
