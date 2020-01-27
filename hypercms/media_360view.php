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
// extension definitions
include ("include/format_ext.inc.php");


// input parameters
$link = getrequest ("link");
$type = getrequest ("type");
$autoplay = getrequest ("autoplay", "bool", false);
$loop = getrequest ("loop", "bool", false);
$muted = getrequest ("muted", "bool", false);
$controls = getrequest ("controls", "bool", true);

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

if ($link != "")
{
  if ($type == "video")
  {
    $option = array();

    if ($autoplay) $option[] = "autoplay:true";
    else $option[] = "autoplay:false";

    if ($loop) $option[] = "loop:true";
    else $option[] = "loop:false";

    if ($muted) $option[] = "muted:true";
    else $option[] = "muted:false";

    $code = "const panorama = new PANOLENS.VideoPanorama('".$link."', { ".implode (", ", $option)." });";
  }
  else
  {
    $code = "const panorama = new PANOLENS.ImagePanorama('".$link."');";
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang);?>" />
<meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width, shrink-to-fit=no">
<style>
html, body
{
  margin: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  background-color: #000;
}
a
{
  font-family: Verdana, Arial, Helvetica, sans-serif; 
  font-size: 12px; 
  font-weight: normal;
}
</style>
</head>

<body>
<script src="javascript/panolens/three.min.js" type="text/javascript"></script>
<script src="javascript/panolens/panolens.min.js" type="text/javascript"></script>
<script type="text/javascript">
<?php
if (!empty ($code))
{
  echo $code;

  if ($controls) $option = "{ controlBar:true }";
  else $option = "{ controlBar:false }";
?>
const viewer = new PANOLENS.Viewer(<?php echo $option; ?>);
viewer.add(panorama);
<?php } ?>
</script>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>