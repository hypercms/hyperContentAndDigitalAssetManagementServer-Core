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
$location = getrequest ("location", "locationname");
$unzip = getrequest ("unzip");
$zipname = getrequest ("zipname");
$zipcount = getrequest ("zipcount");
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
// additional text content
$text_array = getrequest ("text", "array");
// Dropbox respond array
$dropbox_file = getrequest ("dropbox_file");
// FTP respond array
$ftp_file = getrequest ("ftp_file");
// PROXY file in case load balancing is used
$proxy_file = getrequest ("proxy_file");
$proxy_file_name = getrequest ("proxy_file_name");
$proxy_file_link = getrequest ("proxy_file_link");

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

// --------------------------------- load balancer ----------------------------------

// call load balancer only for management server where user is logged in
if (checktoken ($token, $user)) loadbalancer ("uploadfile");

// --------------------------------- logic section ----------------------------------

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// upload file
if ($token != "" && checktoken ($token, $user))
{
  // from hyperCMS PROXY service
  if ($proxy_file)
  {
    $file['Filedata']['tmp_name'] = $mgmt_config['abs_path_temp'].$proxy_file['link'];
    $file['Filedata']['name'] = $proxy_file['name'];
    
    $result = uploadfile ($site, $location, $cat, $file, $page, $unzip, $createthumbnail, $imageresize, $imagepercentage, $user, $checkduplicates, $versioning, $zipname, $zipcount);
  }
  // from Dropbox
  elseif ($dropbox_file)
  {
    $file['Filedata']['tmp_name'] = $dropbox_file['link'];
    $file['Filedata']['name'] = $dropbox_file['name'];
    
    $result = uploadfile ($site, $location, $cat, $file, $page, $unzip, $createthumbnail, $imageresize, $imagepercentage, $user, $checkduplicates, $versioning, $zipname, $zipcount);
  }
  // from FTP server
  elseif ($ftp_file)
  {
    $file['Filedata']['tmp_name'] = $ftp_file['link'];
    $file['Filedata']['name'] = $ftp_file['name'];
    
    $result = uploadfile ($site, $location, $cat, $file, $page, $unzip, $createthumbnail, $imageresize, $imagepercentage, $user, $checkduplicates, $versioning, $zipname, $zipcount);
  }
  // from local file system of user
  else
  {
    $result = uploadfile ($site, $location, $cat, $_FILES, $page, $unzip, $createthumbnail, $imageresize, $imagepercentage, $user, $checkduplicates, $versioning, $zipname, $zipcount);
  }

  // additional text content
  if (!empty ($result['object']) && is_array ($text_array) && sizeof ($text_array) > 0)
  {
    $objects = link_db_getobject ($result['object']);
    
    if (is_array ($objects) && sizeof ($objects) > 0)
    {
      foreach ($objects as $temp_path)
      {
        if ($temp_path != "")
        {
          $temp_info = getobjectinfo ($site, getlocation ($temp_path), getobject ($temp_path), $user);
          $temp_contentdata = getobjectcontainer ($site, getlocation ($temp_path), getobject ($temp_path), $user, "work");
          $temp_container_id = $temp_info['container_id'];
          
          // set text in container
          $temp_contentdata = settext ($site, $temp_contentdata, $temp_container_id.".xml", $text_array, "u", "no", $user, $user);
          
          // save working xml content container file
          if (!empty ($temp_contentdata)) $savefile = savecontainer ($temp_container_id, "work", $temp_contentdata, $user);
        }
      }
    }
  }
  
  // make new entry in queue to delete object
  if (is_date ($deletedate, "Y-m-d H:i") && !empty ($result['object']))
  {
    createqueueentry ("delete", $location_esc.$result['object'], $deletedate, 0, "", $user);
  }
}
// invalid token
else
{
  $header = "HTTP/1.1 500 Internal Server Error";
  $result['message'] = "Invalid token";
}

// return header and message to uploader
header ($result['header']);
echo $result['message'];
?>
