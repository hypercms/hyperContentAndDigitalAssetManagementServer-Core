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


// input parameters
// location must provide the converted path
$media = getrequest ("media", "locationname");
$location = getrequest ("location", "locationname");
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// get publication
if (substr_count ($media, "/") == 1) list ($site, $mediafile) = explode ("/", $media);
else $mediafile = $media;

// publication management config
if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
{
  require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
}

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// initialize
$data = array('success' => false);

// collect keywords of a taxonomy and return as comma seperated list
if ($setlocalpermission['root'] == 1 && $setlocalpermission['delete'] == 1 && valid_objectname ($mediafile) && substr_count ($mediafile, ".media.") == 1)
{
  // define media location in repository
  $medialocation = getmedialocation ($site, strrpos ($mediafile, ".").".thumb.jpg", "abs_path_media");

  // file extension
  $mediafile_ext = strrchr ($mediafile, ".");

  // video individual files
  // local media file
  deletefile ($medialocation.$site."/", $mediafile, 0);
  // cloud storage
  if (function_exists ("deletecloudobject")) deletecloudobject ($site, $medialocation.$site."/", $mediafile, $user);
  // remote client
  remoteclient ("delete", "abs_path_media", $site, $medialocation.$site."/", "", $mediafile, "");

  // media player config file
  $mediafile_config = substr ($mediafile, 0, strrpos ($mediafile, ".media".$mediafile_ext)).".config".$mediafile_ext;
  // local media file
  deletefile ($medialocation.$site."/", $mediafile_config, 0);
  // cloud storage
  if (function_exists ("deletecloudobject")) deletecloudobject ($site, $medialocation.$site."/", $mediafile_config, $user);
  // remote client
  remoteclient ("delete", "abs_path_media", $site, $medialocation.$site."/", "", $mediafile_config, "");

  $data['success'] = true;
}

header ('Content-Type: application/json; charset=utf-8');
print json_encode ($data);
exit;
?>