<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
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


// input parameters
$location = getrequest ("location", "locationname");
$page = getrequest ("page", "objectname");
$screenwidth = getrequest ("width", "numeric");
$screenheight = getrequest ("height", "numeric");

// set default width and height
if ($screenwidth < 1) $screenwidth = 800;
$width = $screenwidth - 80;

if ($screenheight < 1) $screenheight = 600;
$height = $screenheight - 80;

// location and object is set by assetbrowser
if ($location == "" && !empty ($hcms_assetbrowser_location) && !empty ($hcms_assetbrowser_object))
{
  $location = $hcms_assetbrowser_location;
  $page = $hcms_assetbrowser_object;
}

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && is_file ($location.$page))
{
  // ------------------------------ permission section --------------------------------
  
  // check access permissions (DAM)
  if ($mgmt_config[$site]['dam'] == true)
  {
    $ownergroup = accesspermission ($site, $location, $cat);
    $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);
    if ($setlocalpermission['root'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);
  }
  // check permissions
  else
  {
    if (($cat != "page" && $cat != "comp") || ($cat == "comp" && !checkglobalpermission ($site, 'component')) || ($cat == "page" && !checkglobalpermission ($site, 'page')) || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);
  }

  // check session of user
  checkusersession ($user);
  
  // --------------------------------- logic section ----------------------------------
  
  $file_info = getfileinfo ($site, $location.$page, $cat);
  $object_info = getobjectinfo ($site, $location, $page, $user);

  // media preview
  if (is_array ($object_info) && $object_info['media'] != "")
  {
    $mediafile = $site."/".$object_info['media'];
    $mediaview = showmedia ($mediafile, $file_info['name'], "media_only", "mediacontainer", $width, $height);
  }
  // page or component preview (no multimedia file)
  else
  {
    $mediaview = showobject ($site, $location, $page, $cat, $name);
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/explorer.css" />
<script type="text/javascript" language="JavaScript" src="javascript/main.js"></script>
<script type="text/javascript" language="JavaScript" src="javascript/click.js"></script>
<?php if (!empty ($file_info['ext']) && is_audio ($file_info['ext'])) echo showaudioplayer_head (false); ?>
<?php if (!empty ($file_info['ext']) && is_video ($file_info['ext'])) echo showvideoplayer_head (false, false); ?>
</head>

<body class="hcmsWorkplaceObjectlist">

<div id="container">
<?php
if (!empty ($mediaview)) echo $mediaview;
?>
</div>

<?php if ($screenwidth > 0 && $screenheight > 0) { ?>
<script language="JavaScript">
// get width of media container
var mediawidth = document.getElementById('mediacontainer').offsetWidth;
var mediaheight = document.getElementById('mediacontainer').offsetHeight;

<?php if (!empty ($file_info['ext']) && is_audio ($file_info['ext'])) { ?>
// correct size of adio player
if (mediawidth < 300 || mediaheight < 60)
{
  mediawidth = 320;
  mediaheight = 320;
}
<?php } ?>

// screen width and height
var screenwidth = <?php echo $screenwidth; ?>;
var screenheight = <?php echo $screenheight; ?>;

// calculate margins
var marginleft = Math.floor((screenwidth - mediawidth) / 2);
var margintop = Math.floor((screenheight - mediaheight) / 2);

// set margins
if (marginleft > 0 && margintop > 0)
{
  document.getElementById('container').style.marginLeft = marginleft+"px";
  document.getElementById('container').style.marginTop = margintop+"px";
}
</script>
<?php } ?>

</body>
</html>
