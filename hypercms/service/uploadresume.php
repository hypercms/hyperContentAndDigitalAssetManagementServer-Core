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
// JQUERY file upload
require ('../function/uploadhandler.class.php');

// input parameters
$action = getrequest ("action", "objectname");
$location = getrequest ("location", "locationname");
$file = getrequest ("file");
$user = getrequest ("user");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

// uploads works only for components
if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['upload'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// target path for the uploaded file chunks (unique hash based on the location and file name, required for resume file operation)
$upload_tempdir = "chunkupload_".md5($location_esc.":".$file.":".$user);

// set options for JQuery file upload
// remove option 'accept_file_types' from uploadhandler.class.php
$options = array(
  'param_name' => "Filedata", 
  'upload_dir' => $mgmt_config['abs_path_temp'].$upload_tempdir."/", 
  'upload_url'=> $mgmt_config['url_path_temp'].$upload_tempdir."/", 
  'image_versions' => array(),
  'replace_dots_in_filenames' => '',
  'discard_aborted_uploads' => false,
);

// chunk files upload handler
$upload_handler = new UploadHandler($options);

// if a chunk file upload exists
if (is_file ($mgmt_config['abs_path_temp'].$upload_tempdir."/".$file))
{
  // delete file upload
  if ($action == "delete")
  {
    if (is_dir ($mgmt_config['abs_path_temp'].$upload_tempdir)) $delete = deletefile ($mgmt_config['abs_path_temp'], $upload_tempdir, 1);

    $errcode = "00101";
    $error[] = $mgmt_config['today']."|service/uploadresume.php|information|".$errcode."|The upload of file '".htmlspecialchars($location_esc.$file)."' (".($delete ? "deleted chunks" : "failed to delete chunks").") has been aborted by user '".htmlspecialchars($user)."'";
    savelog ($error);
  }
  // resume file upload
  else
  {
    $errcode = "00102";
    $error[] = $mgmt_config['today']."|service/uploadresume.php|information|".$errcode."|The upload of file '".htmlspecialchars($location_esc.$file)."' will be resumed for user '".htmlspecialchars($user)."'";
    savelog ($error);
  }
}
?>