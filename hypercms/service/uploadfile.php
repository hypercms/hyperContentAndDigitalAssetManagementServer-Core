<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
 // Code for Session Cookie workaround
if (!session_id() && isset ($_REQUEST["PHPSESSID"])) session_id ($_REQUEST["PHPSESSID"]);

// session parameters
require ("../include/session.inc.php");
// management configuration
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");
// hyperCMS UI
require ("../function/hypercms_ui.inc.php");


// input parameters
$location = getrequest ("location", "locationname");
$unzip = getrequest ("unzip");
$media_update = getrequest ("media_update");
$createthumbnail = getrequest ("createthumbnail");
$contentfile = getrequest ("contentfile", "objectname");
$page = getrequest ("page", "objectname");
$imageresize = getrequest ("imageresize");
$imagepercentage = getrequest ("imagepercentage");
$checkduplicates = getrequest ("checkduplicates");
$versioning = getrequest ("versioning");
$deletedate = getrequest ("deletedate");
$token = getrequest ("token");
// Dropbox respond array
$dropbox_file = getrequest("dropbox_file");
// FTP respond array
$ftp_file = getrequest("ftp_file");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);
// uploads works only for components
if ($cat != "comp" || $ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['upload'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// log entry
$errcode = "00011";
$error[] = $mgmt_config['today']."|hypercms_api.inc.php|information|$errcode|new multimedia file upload by user '$user' using token '$token' ($site, $location_esc, $page, $cat, update:$media_update)";       

// save log
savelog (@$error);

// upload file
if ($token != "" && checktoken ($token, $user))
{
  // from Dropbox
  if ($dropbox_file)
  {
    $file['Filedata']['tmp_name'] = $dropbox_file['link'];
    $file['Filedata']['name'] = $dropbox_file['name'];
    
    $result = uploadfile ($site, $location, $cat, $file, $unzip,  $media_update, $createthumbnail, $page, $imageresize, $imagepercentage, $user, $checkduplicates, $versioning);
  }
  // from fTP server
  elseif ($ftp_file)
  {
    $file['Filedata']['tmp_name'] = $ftp_file['link'];
    $file['Filedata']['name'] = $ftp_file['name'];
    
    $result = uploadfile ($site, $location, $cat, $file, $unzip,  $media_update, $createthumbnail, $page, $imageresize, $imagepercentage, $user, $checkduplicates, $versioning);
  }
  // from local file system of user
  else
  {
    $result = uploadfile ($site, $location, $cat, $_FILES, $unzip, $media_update, $createthumbnail, $page, $imageresize, $imagepercentage, $user, $checkduplicates, $versioning);
  }

  // make new entry in queue to delete object
  if (is_date ($deletedate, "Y-m-d H:i") && !empty ($result['object']))
  {
    rdbms_createqueueentry ("delete", $location_esc.$result['object'], $deletedate, 0, $user);
  }
}

// return header and message to uploader
header ($result['header']);
echo $result['message'];
?>
