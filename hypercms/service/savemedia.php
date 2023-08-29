<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */

// session
define ("SESSION", "create");
// management configuration
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");
// file formats extensions
require ("../include/format_ext.inc.php");


// input parameters
$savetype = getrequest ("savetype");
$wf_token = getrequest_esc ("wf_token");
$token = getrequest ("token");
// object
$site = getrequest ("site", "publicationname");
$location = getrequest ("location", "locationname");
$page = getrequest ("page", "objectname");
$mediafile = getrequest ("media");
// base64 encoded media file
$mediadata = getrequest ("mediadata");
// JSON encoded image data from MiniPaint
$jsondata = getrequest ("jsondata");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// get media file
if ($location != "" && $page != "")
{
  $objectinfo = getobjectinfo ($site, $location, $page);
  $mediafile = $objectinfo['media'];
}
// reset media file
else
{
  $mediafile = "";
  $mediadata = "";
}

// output is used to return data
$output = new stdClass();

// publication management config
if (valid_publicationname ($site))
{
  if (is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
  {
    require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
  }
  else
  {
    header ('HTTP/1.0 403 Forbidden', true, 403);
    exit;
  }
}

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- load balancer ----------------------------------

// call load balancer only for management server where user is logged in
if (checktoken ($token, $user)) loadbalancer ("renderimage");

// --------------------------------- logic section ----------------------------------

// initialize
$show = "";
$add_onload = "";

// save media file 
if (checktoken ($token, $user) && !empty ($mediadata))
{
  $editmediaobject = editmediaobject ($site, $location, $page, "", "", $mediadata, $user);
}
// save JSON file from MiniPaint
elseif (checktoken ($token, $user) && !empty ($jsondata))
{
  // get thumbnail file location
  $media_location = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";
  
  // if symbolic link
  if (is_link ($media_location.$mediafile))
  {
    $target_path = readlink ($media_location.$mediafile);
    $target_location = getlocation ($target_path);
  }
  else $target_location = $media_location;

  // JSON image editor file
  $mediafile_json = substr ($mediafile, 0, strrpos ($mediafile, ".")).".json";
  
  $editmediaobject = array();
  $editmediaobject['result'] = savefile ($target_location, $mediafile_json, $jsondata);
  $editmediaobject['add_onload'] = "";
  $editmediaobject['message'] = "";
  $editmediaobject['object'] = $page;
  $editmediaobject['mediafile'] = $mediafile;

  // save to cloud storage
  if (!empty ($editmediaobject['result']) && function_exists ("savecloudobject")) savecloudobject ($site, $media_location, $mediafile_json, $user);
}
else
{
  $show = $hcms_lang['required-parameters-are-missing'][$lang];
}

// on success
if (!empty ($editmediaobject['result'])) 
{ 
  $output->success = true;
  $output->add_onload = $add_onload = $editmediaobject['add_onload'];
  $output->message = $show = $editmediaobject['message'];
  $output->objectpath = $location_esc.$page;
  $output->location = $location_esc;
  $output->object = $page = $editmediaobject['object'];
  $output->mediafile = $mediafile = $editmediaobject['mediafile'];

  // add timestamp to ensure the new image will be loaded
  $output->imagelink = createviewlink ($site, $mediafile, "", true);
}
// on error
else
{
  $output->success = false;
  $output->objectpath = $location_esc.$page;
  $output->location = $location_esc;
  $output->object = $page;
  $output->mediafile = $mediafile;

  if (empty ($show))
  {
    $output->message = $show = $hcms_lang['error-during-conversion'][$lang];
  }
  else $output->message = $show;
}

// return json encoded data for AJAX call
if ($savetype == "auto" || $savetype == "")
{
  header ('Content-Type: application/json; charset=utf-8');
  echo json_encode ($output);
  exit;
}
// refresh after save and open
elseif ($savetype == "editor_so")
{
  $add_onload .=  "document.location='../image_minipaint.php?site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."';\n";
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript">
<?php echo $add_onload; ?>
</script>
<script type="text/javascript" src="../javascript/main.min.js"></script>
<script type="text/javascript" src="../javascript/click.min.js"></script>
</head>
<body class="hcmsWorkplaceGeneric">
<div style="padding:4px;">
  <?php echo $show; ?>
</div>
</body>
</html>
<?php 
}
?>