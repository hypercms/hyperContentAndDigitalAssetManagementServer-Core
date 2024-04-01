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
$location = getrequest ("location", "locationname");
$totalcount = getrequest ("totalcount");
$media_update = getrequest ("media_update");
$createthumbnail = getrequest ("createthumbnail");
$contentfile = getrequest ("contentfile", "objectname");
$page = getrequest ("page", "objectname");
$imageresize = getrequest ("imageresize");
$imagepercentage = getrequest ("imagepercentage");
$checkduplicates = getrequest ("checkduplicates");
$overwrite = getrequest ("overwrite");
$versioning = getrequest ("versioning");
$deletedate = getrequest ("deletedate");
$token = getrequest ("token");
// relative file path array
$filepath = getrequest ("filepath", "array");
// unzip and zip files
$unzip = getrequest ("unzip");
$zipname = getrequest ("zipname");
$zipcount = getrequest ("zipcount");
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
// set HTTP header in response
$http_header = getrequest ("http_header", "bool", true);
// provide message in response
$response_message = getrequest ("response_message", "bool", true);

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

// initialize
$error = array();
$result = array();

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// upload file
if ($token != "" && checktoken ($token, $user))
{
  // create upload log entry for user
  insertuploadlog ($location_esc, $user, $totalcount);

  // from hyperCMS PROXY service (no support of file chunks)
  if ($proxy_file)
  {
    $file['Filedata']['tmp_name'] = $mgmt_config['abs_path_temp'].$proxy_file['link'];
    $file['Filedata']['name'] = $proxy_file['name'];

    $result = uploadfile ($site, $location, $cat, $file, $page, $unzip, $createthumbnail, $imageresize, $imagepercentage, $user, $checkduplicates, $overwrite, $versioning, $zipname, $zipcount);
  }
  // from Dropbox (no support of file chunks)
  elseif ($dropbox_file)
  {
    $file['Filedata']['tmp_name'] = $dropbox_file['link'];
    $file['Filedata']['name'] = $dropbox_file['name'];

    $result = uploadfile ($site, $location, $cat, $file, $page, $unzip, $createthumbnail, $imageresize, $imagepercentage, $user, $checkduplicates, $overwrite, $versioning, $zipname, $zipcount);
  }
  // from FTP server (no support of file chunks)
  elseif ($ftp_file)
  {
    $file['Filedata']['tmp_name'] = $ftp_file['link'];
    $file['Filedata']['name'] = $ftp_file['name'];

    $result = uploadfile ($site, $location, $cat, $file, $page, $unzip, $createthumbnail, $imageresize, $imagepercentage, $user, $checkduplicates, $overwrite, $versioning, $zipname, $zipcount);
  }
  // from local file system of user (support of file chunks)
  elseif (!empty ($_FILES['Filedata']['name']))
  {
    // target path for the uploaded file chunks (unique hash based on the location and file name, required for resume file operation)
    $upload_tempdir = "chunkupload_".md5($location_esc.":".$_FILES['Filedata']['name'].":".$user);

    // upload handler for chunk files
    $filechunkinfo = getuploadfilechunkinfo();

    // if file chunks are uploaded
    if (!empty ($filechunkinfo['range']))
    {
      // turn on output buffering in order to remove the last output of the upload handler
      ob_start();

      // create target directory für upload handler if it does not exit
      if (!is_dir ($mgmt_config['abs_path_temp'].$upload_tempdir))
      {
        mkdir ($mgmt_config['abs_path_temp'].$upload_tempdir);
      }

      if (is_dir ($mgmt_config['abs_path_temp'].$upload_tempdir))
      {  
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

        // chunk files upload handler (for files greater than 10 MB)
        $upload_handler = new UploadHandler($options);

        $_FILES['Filedata']['tmp_name'] = $mgmt_config['abs_path_temp'].$upload_tempdir."/".$_FILES['Filedata']['name'];
        $_FILES['Filedata']['name'] = $_FILES['Filedata']['name'];

        // get current file size and verify if the file has been fully uploaded
        if (is_file ($mgmt_config['abs_path_temp'].$upload_tempdir."/".$_FILES['Filedata']['name']) && intval ($filechunkinfo['size']) == filesize ($mgmt_config['abs_path_temp'].$upload_tempdir."/".$_FILES['Filedata']['name']))
        {
          $filechunks_finished = true;

          // remove HTTP headers not sent
          if (!headers_sent())
          {
            header_remove ();
          }

          // remove/clean previous output
          ob_end_clean();
        }
      }
    }

    // if the file has been uploaded or all file chunks have been uploaded
    if (empty ($filechunkinfo) || !empty ($filechunks_finished))
    {
      // verify or create folders based on the provided relative path
      $filename = $_FILES['Filedata']['name'];

      if (!empty ($filepath[$filename]) && strpos ($filepath[$filename], "/") > 0)
      {
        $relpath = dirname ($filepath[$filename]);

        if (trim ($relpath) != "")
        {
          if (strpos ($relpath, "/") > 0)
          {
            $folder = basename ($relpath);
            $relpath = substr ($relpath, 0, strrpos ($relpath, "/") + 1);
            $location = $location.$relpath;
          }
          else $folder = $relpath;

          // create missing folders
          $createfolders = createfolders ($site, $location, $folder, $user);

          // set the new location path based on the resuls (do not verify the result array key since it might be false if the folder exists already)
          if (!empty ($createfolders['location']) && !empty ($createfolders['folder']))
          {
            $location = $createfolders['location'].$createfolders['folder']."/";
          }
        }
      }

      $result = uploadfile ($site, $location, $cat, $_FILES, $page, $unzip, $createthumbnail, $imageresize, $imagepercentage, $user, $checkduplicates, $overwrite, $versioning, $zipname, $zipcount);

      // remove temp directory used for uploaded file chunks
      if (is_dir ($mgmt_config['abs_path_temp'].$upload_tempdir))
      {
        deletefile ($mgmt_config['abs_path_temp'], $upload_tempdir, 1);
      }
    }
  }

  // additional text content
  if (!empty ($result['objectpath']) && is_array ($text_array) && sizeof ($text_array) > 0)
  {
    $objects = link_db_getobject ($result['objectpath']);

    if (is_array ($objects) && sizeof ($objects) > 0)
    {
      foreach ($objects as $temp_path)
      {
        if ($temp_path != "")
        {
          $temp_info = getobjectinfo (getpublication ($temp_path), getlocation ($temp_path), getobject ($temp_path), $user);
          $temp_container_id = $temp_info['container_id'];
          $temp_contentdata = loadcontainer ($temp_container_id, "work", $user);
          
          // set text in container
          $temp_contentdata = settext (getpublication ($temp_path), $temp_contentdata, $temp_container_id.".xml", $text_array, "u", "no", $user, $user);

          // save working xml content container file
          if (!empty ($temp_contentdata)) $savefile = savecontainer ($temp_container_id, "work", $temp_contentdata, $user);
        }
      }
    }
  }
  
  // make new entry in queue in order to delete object
  if (is_date ($deletedate, "Y-m-d H:i") && !empty ($result['object']))
  {
    createqueueentry ("delete", $location_esc.$result['object'], $deletedate, 0, "", $user);
  }
}
// invalid token
else
{
  $errcode = "20101";
  $error[] = $mgmt_config['today']."|uploadfile.php|error|".$errcode."|The security token provided by user '".$user."' it not valid: ".$token;

  savelog (@$error);

  $result['header'] = "HTTP/1.1 500 Internal Server Error";
  $result['message'] = "Invalid token";
}

// return HTTP header and message to uploader
if ($http_header && !empty ($result['header'])) 
{
  header ($result['header']);
}

if ($response_message && !empty ($result['message']))
{
  // JSON response
  //header ('Content-Type: application/json; charset=utf-8');
  //echo json_encode (array("message" => $result['message']));
  //exit;

  // plain text response
  header ('Content-Type: text/plain; charset=utf-8');
  echo $result['message'];
}

exit;
?>