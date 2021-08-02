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
$link = getrequest ("link");
$screenwidth = getrequest ("width", "numeric", 800);
$screenheight = getrequest ("height", "numeric", 600);

// get width and height of temporary preview image
$parts = parse_url ($link, PHP_URL_QUERY);
parse_str ($parts, $query);
$mediafile = $query['media'];

if (!empty ($mediafile) && is_file ($mgmt_config['abs_path_temp'].getobject ($mediafile)))
{
  $imagesize = getmediasize ($mgmt_config['abs_path_temp'].getobject ($mediafile));
  
  if (!empty ($imagesize['width']) && !empty ($imagesize['height']))
  {
    $imagewidth = $imagesize['width'];
    $imageheight = $imagesize['height'];

    // reset screen dimension if necessary
    if (($imagewidth + 80) > $screenwidth || ($imageheight + 80) > $screenheight)
    {
      $temp = mediasize2frame ($imagewidth, $imageheight, $screenwidth, $screenheight, true);
      $screenwidth = $temp['width'];
      $screenheight = $temp['height'];
    }
    else
    {
      $screenwidth = $imagewidth;
      $screenheight = $imageheight;
    }
  }
}

// set default width and height
if ($screenwidth < 1) $screenwidth = 800;
if ($screenheight < 1) $screenheight = 600;

// set width and height for the view
$width = $screenwidth - 80;
$height = $screenheight - 80;

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user);
  
// --------------------------------- logic section ----------------------------------

// write and close session (non-blocking other frames)
if (session_id() != "") session_write_close();
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script type="text/javascript" src="javascript/main.min.js"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
<script type="text/javascript" src="javascript/jquery/jquery-3.5.1.min.js"></script>
<script type="text/javascript" src="javascript/jquery/jquery-migrate-3.3.0.min.js"></script>
<script type="text/javascript" src="javascript/jquery-ui/jquery-ui-1.12.1.min.js"></script>
<script type="text/javascript" src="javascript/zoomcrop/jquery.zoomcrop.js"></script>

<link rel="stylesheet" type="text/css" href="javascript/jquery-ui/jquery-ui-1.12.1.min.css" />		
<link rel="stylesheet" type="text/css" href="javascript/zoomcrop/jquery.zoomcrop.css" />		
<style>
#cropper 
{
  margin: 40px auto;
  width: <?php echo $width; ?>px;
  height: <?php echo $height; ?>px;
}
</style>	
<script type="text/javascript">
$(function()
{
  $('#cropper').ZoomCrop(
  {
    image: '<?php echo $link; ?>'
  });
});
</script>
</head>

<body>

<div class="jquery-script-center">
  <div id="cropper"></div>
</div>

<?php includefooter(); ?>

</body>
</html>
