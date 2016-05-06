<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session
define ("SESSION", "create");
// management configuration
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");
// format file extensions
require ("../include/format_ext.inc.php");  


// input parameters
$site = getrequest_esc ("site", "publicationname");
$media = getrequest_esc ("media", "objectname");
$name = getrequest_esc ("name");
$token = getrequest ("token");
// alternative input parameters
$hcms_objref = getrequest ("hcms_objref"); // older versions before 5.5.8
$hcms_objcode = getrequest ("hcms_objcode"); // older versions before 5.5.8
$hcms_objid = getrequest ("hcms_objid");
$hcms_token = getrequest ("hcms_token");
// secure input parameters
$hcms_id_token = getrequest ("hcms_id_token");
// new hash parameter for wrapper-link since version 5.6.0
$wl = getrequest ("wl", "url");
// media conversion
$type = getrequest_esc ("type");
$media_config = getrequest_esc ("mediacfg");

// default language
if ($lang == "") $lang = "en";

// ------------------------------ permission section --------------------------------

// check user session if user is logged in
if ($user != "") checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$media_approved = false;

// ------------------------------- define objectpath --------------------------------
// get object ID
// if wrapper link hash is provided (since version 5.6.2)
if ($wl != "" && !empty ($mgmt_config['db_connect_rdbms']))
{
  $objectpath_esc = rdbms_getobject ($wl);
  $object_id = rdbms_getobject_id ($objectpath_esc);
}
// if id-token is provided (since version 5.5.13 support of id-token holding encrypted id and time)
elseif ($hcms_id_token != "")
{
  $hcms_id_string = hcms_decrypt ($hcms_id_token);
  if ($hcms_id_string != "" && strpos ($hcms_id_string, ":") > 0) list ($hcms_objid, $hcms_token) = explode (":", $hcms_id_string);

  // token since version 5.5.13 (time token is used instead of crypted token id)
  if ($hcms_objid != "" && checktimetoken ($hcms_token))
  {
    $object_id = $hcms_objid;
  }
  else
  {
    header ('HTTP/1.0 403 Forbidden', true, 403);
    echo showinfopage ($hcms_lang['the-provided-link-is-expired'][$lang], $lang);
    exit;
  }
}
// if object id is provided and token is valid (since version 5.5.13 support of time token)
elseif ($hcms_objid != "" && substr ($hcms_token, 0, 1) == "_")
{
  $hcms_id_string = hcms_decrypt (substr ($hcms_token, 1));
  
  // extract object id and time token (since version 5.5.13)
  if ($hcms_id_string != "" && strpos ($hcms_id_string, ":") > 0)
  {
    list ($hcms_objref_tmp, $hcms_timetoken) = explode (":", $hcms_id_string);
    
    // check time token and generate object code
    if ($hcms_objref_tmp == $hcms_objid && checktimetoken ($hcms_timetoken))
    {
      $object_id = $hcms_objid;
    }  
    else
    {
      header ('HTTP/1.0 403 Forbidden', true, 403);
      echo showinfopage ($hcms_lang['the-provided-link-is-expired'][$lang], $lang);
      exit;
    }
  }
}
// if object id is provided and token is valid (old versions before 5.5.13)
elseif ($hcms_objid != "" && substr ($hcms_token, 0, 1) != "_" && hcms_crypt ($hcms_objid) == $hcms_token)
{
  $object_id = $hcms_objid;
}
// if object reference is provided and is valid (older versions before 5.5.8)
elseif ($hcms_objref != "" && hcms_crypt ($hcms_objref, 3, 12) == $hcms_objcode)
{
  $object_id = $hcms_objref;
}
else
{
  $object_id = "";
  $objectpath_esc = "";
}

// get object from object id
if ($object_id != "" && $mgmt_config['db_connect_rdbms'] != "")
{ 
  $media = "";
	$objectpath_esc = rdbms_getobject ($object_id);
}

// ---------------------------------- define media -----------------------------------

// get media from object path
if ($objectpath_esc != "")
{
  $site = getpublication ($objectpath_esc);
  $cat = getcategory ($site, $objectpath_esc);
  $objectpath = deconvertpath ($objectpath_esc, "file");
  
    // if folder
  if (getobject ($objectpath) == ".folder")
  {
  	$location = getlocation ($objectpath);
  	$object = "";
      
    if ($user == "") $user = "sys";
    $zip_filename = uniqid ("tmp");
    
    // zip all files in temp folder
    $result = zipfiles ($site, array ($location), $mgmt_config['abs_path_temp'], $zip_filename, $user, "download");
    
    $media = $zip_filename.".zip";
    $media_info = getfileinfo ($site, getobject ($location).".zip", $cat);
    $name = $media_info['name'];
    $media_approved = true;    
  }
  // if object
  else
  {
  	$location = getlocation ($objectpath);
  	$object = getobject ($objectpath);
      
    // get media file from object file
		if (@is_file ($location.$object))
    {
			$objectdata = loadfile ($location, $object);
            
			if ($objectdata != false)
      {
        // add publication name since this file is located in the content media repository
				$media = getfilename ($objectdata, "media");
        $container = getfilename ($objectdata, "content");
        if ($media != "") $media = $site."/".$media;
        $media_approved = true; 
      }
    }       
  }  
}

// ---------------------------------- stream file -----------------------------------
// get publication
if (substr_count ($media, "/") == 1) $site = substr ($media, 0, strpos ($media, "/"));

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// read multimedia file (publication/file) and submit data
if (valid_objectname ($media) && ((hcms_crypt ($media) == $token && ($user != "" || is_thumbnail ($media, false) || !$mgmt_config[$site]['dam'])) || $media_approved))
{
  // check IP access
  if ($user == "" && !allowuserip ($site))
  {
    header ('HTTP/1.0 403 Forbidden', true, 403);
    exit;
  }

  // Location
  // ... of multimedia file in repository
  if (is_file (getmedialocation ($site, $media, "abs_path_media").$media) || is_cloudobject (getmedialocation ($site, getobject($media), "abs_path_media").$site."/".getobject ($media)))
  {
    $media_root = getmedialocation ($site, $media, "abs_path_media");
  }
  // ... of template media file
  elseif (is_file ($mgmt_config['abs_path_tplmedia'].$media))
  {
    $media_root = $mgmt_config['abs_path_tplmedia'];
  }
  // ... of zip file in temp
  elseif (is_file ($mgmt_config['abs_path_temp'].getobject($media)) && $user != "")
  {
    $media_root = $mgmt_config['abs_path_temp'];
    $media = getobject ($media);
  }
  else $media_root = "";

  $media_info_new = Null;

  if ($media_root != "")
  {
    // convert file if requested
    if ($type != "" && strtolower ($type) != "original")
    {
      // target path for the temporary file
      $media_target = $mgmt_config['abs_path_temp'];
      
      // convert file
      $result_conv = convertmedia ($site, $media_root.$site."/", $media_target, getobject ($media), $type, $media_config, true);
      
      // if new file has been converted successfully, set new media root path and new media file name
      if ($result_conv != "") 
      { 
        $media_new = $media = $result_conv;
        $media_info_new = getfileinfo ($site, getobject ($media), "comp");
        
        // define new name
        $name_info = getfileinfo ($site, getobject ($name), "comp");
        $name = $name_info['filename'].$media_info_new['ext'];
        
        $media_root = $media_target;
      }
      else $media = "";
    }  

    // don't show HTML-files directly in browser since JS-code can be embedded in the file 
    $media_info = getfileinfo ($site, $media_root.$media, "comp");
    
    if (is_array ($media_info) && substr_count (strtolower ($hcms_ext['cms']).".", $media_info['ext'].".") > 0)
    {
      header ('HTTP/1.0 403 Forbidden', true, 403);
      echo showinfopage ($hcms_lang['the-live-view-of-the-file-is-not-allowed'][$lang])."<br />".getescapedtext ($hcms_lang['please-download-the-file-in-order-to-view-its-content'][$lang], $lang);
      exit;
    }
  
    // if media is given
    if ($media != "")
    {
      // if no name is given
      if ($name == "")
      {
        if ($objectpath_esc != "") $media_info = getfileinfo ($site, $objectpath_esc, "comp");
        else $media_info = getfileinfo ($site, getobject ($media), "comp");

        $name = $media_info['name'];
        
        // replace file extension if file was converted
        if (!empty ($media_info_new['ext']))
        {
          $name_info = getfileinfo ($site, getobject ($name), "comp");
          $name = $name_info['filename'].$media_info_new['ext'];
        }
      }

      // stream file content
      downloadfile ($media_root.$media, $name, "wrapper", $user);
    }
    else
    {
      header ("HTTP/1.1 400 Invalid Request", true, 400);
    }
  }
  else
  {
    header ("HTTP/1.1 400 Invalid Request", true, 400);
  }
}
// page or component
elseif ($objectpath_esc != "" && is_file ($location.$object))
{
  downloadobject ($location, $object, $container, $lang, $user);
}
// no content available
else
{
  header ("HTTP/1.1 400 Invalid Request", true, 400);
}
?>