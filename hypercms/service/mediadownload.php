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
// new hash parameter for download-link since version 5.6.0
$dl = getrequest ("dl", "url");
// new crpyted media parameter since version 5.6.3
$dm = getrequest ("dm", "url");
// media conversion
$type = getrequest_esc ("type");
$media_config = getrequest_esc ("mediacfg");

// external user ID provided by request (Download link)
if (empty ($user)) $extuser = getrequest ("user");

// default language
if ($lang == "") $lang = "en";

// ------------------------------ permission section --------------------------------

// check user session if user is logged in
if ($user != "") checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

$media_approved = false;

// new media configuration parameter since version 8.0.5
// get media type and config from encrypted cfg value
if (!empty ($dl) && substr ($dl, 0, 5) == "hcms.") 
{
  $cfg = substr ($dl, 5);
  $cfg = hcms_decrypt ($cfg);
  if ($cfg != "" && strpos ($cfg, ":") > 0) list ($dl, $type, $media_config) = explode (":", $cfg);
}

// ------------------------------- define objectpath --------------------------------
// if download link hash is provided (since version 5.6.2)
if ($dl != "" && !empty ($mgmt_config['db_connect_rdbms']))
{
  $result_dl = rdbms_getaccessinfo ($dl);
  
  // if download link uses access hash
  if (!empty ($result_dl['object_id']) && $result_dl['object_id'] > 0)
  {
    $object_id = $result_dl['object_id'];
    $objectpath_esc = rdbms_getobject ($object_id);
    
    // get download formats
    if (!empty ($result_dl['formats']))
    {
      $hcms_objformats = json_decode ($result_dl['formats'], true);

      if (is_document ($objectpath_esc))
      {
        // get first element in array   
        $type = getfirstkey ($hcms_objformats['document']);
        $media_config = "";
      }
      elseif (is_image ($objectpath_esc))
      {
        // get first element in array    
        $type = getfirstkey ($hcms_objformats['image']);
        $media_config = getfirstkey ($hcms_objformats['image'][$type]);
      }
      elseif (is_video ($objectpath_esc))
      {
        // get first element in array    
        $type = getfirstkey ($hcms_objformats['video']);
        $media_config = "";
      }
    }
  }
  // standard download link using object hash
  else
  {
    $objectpath_esc = rdbms_getobject ($dl);
    $object_id = rdbms_getobject_id ($objectpath_esc);
  }
}
// get object ID
// if id-token is provided (since version 5.5.13 support of id-token holding encrypted id and time)
elseif ($hcms_id_token != "")
{
  $hcms_id_string = hcms_decrypt ($hcms_id_token);
  if ($hcms_id_string != "" && strpos ($hcms_id_string, ":") > 0) list ($hcms_objid, $hcms_token) = explode (":", $hcms_id_string);

  // token since version 5.5.13 (time token is used instead of crypted token id)
  if ($hcms_objid != "" && $hcms_token != "" && checktimetoken ($hcms_token))
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
// if object reference is provided and is invalid (older versions before 5.5.13)
elseif ($hcms_objid != "" && substr ($hcms_token, 0, 1) != "_" && hcms_crypt ($hcms_objid) == $hcms_token)
{
  $object_id = $hcms_objid;
}
// if object reference is provided and is invalid (older versions before 5.5.8)
elseif ($hcms_objref != "" && hcms_crypt ($hcms_objref, 3, 12) == $hcms_objcode)
{
  $object_id = $hcms_objref;
}
else
{
  $object_id = "";
  $objectpath_esc = "";
}

// get object from object ID
if ($object_id != "" && $mgmt_config['db_connect_rdbms'] != "")
{ 
  $media = "";
	$objectpath_esc = rdbms_getobject ($object_id);
}

// ---------------------------------- define media -----------------------------------
// get media from crypted video string
if ($dm != "")
{
  $media = hcms_decrypt ($dm);
  $media_approved = true;  
}
// get media from object path
elseif ($objectpath_esc != "")
{
  $site = getpublication ($objectpath_esc);
  $cat = getcategory ($site, $objectpath_esc);
  $objectpath = deconvertpath ($objectpath_esc, "file");

  // if folder
  if (getobject ($objectpath) == ".folder")
  {
    $location = getlocation ($objectpath);
  	$object = "";
    
    // load object file
    $object_info = getobjectinfo ($site, $location, ".folder", $user);

    if (empty ($user)) $user_zip = "sys";
    else $user_zip = $user;
    
    $zip_filename = $object_id."_hcm".$object_info['container_id'];

    // zip all files
    $result_zip = zipfiles ($site, array ($location), $mgmt_config['abs_path_temp'], $zip_filename, $user_zip, "download");

    // zip file download
    if ($result_zip == true)
    {
      $media = $zip_filename.".zip";
      $media_info = getfileinfo ($site, getobject ($location).".zip", $cat);
      $name = $media_info['name'];
      $media_approved = true;
    }
    // access as default user and show files 
    else
    {
      if ($object_id) $accesslink = createaccesslink ($site, "", "", "", $object_id, "hcms_download", "al", 60);
      else $accesslink = createaccesslink ($site, getlocation ($objectpath_esc), getobject ($objectpath_esc), $cat, "", "hcms_download", "al", 60);

      if ($accesslink != "")
      {
        header ("Location: ".$accesslink);
        exit;
      }
      else
      {
        echo showinfopage ($hcms_lang['the-requested-object-can-not-be-provided'][$lang], $lang);
        exit;
      }
    }
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
        if ($media != "") $media = $site."/".$media;
        $media_approved = true;
      }
    }       
  }  
}

// ---------------------------------- download file -----------------------------------
// Publication
if (substr_count ($media, "/") == 1) $site = substr ($media, 0, strpos ($media, "/"));

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// read multimedia file (publication/file)
if (valid_locationname ($media) && ((hcms_crypt ($media) == $token && ($user != "" || is_thumbnail ($media, false) || empty ($mgmt_config[$site]['dam']))) || $media_approved == true))
{
  // check ip access if public access
  if ($user == "" && (!allowuserip ($site) || !mediapublicaccess ($media)))
  {
    header ('HTTP/1.0 403 Forbidden', true, 403);
    echo showinfopage ($hcms_lang['the-requested-object-can-not-be-provided'][$lang], $lang);
    exit;
  }

  // Location
  // ... of multimedia file in repository
  if (is_file (getmedialocation ($site, getobject($media), "abs_path_media").$media) || is_cloudobject (getmedialocation ($site, getobject($media), "abs_path_media").$site."/".getobject ($media)))
  {
    $media_root = getmedialocation ($site, getobject($media), "abs_path_media");
  }
  // ... of template media file
  elseif (is_file ($mgmt_config['abs_path_tplmedia'].$media))
  {
    $media_root = $mgmt_config['abs_path_tplmedia'];
  }
  // ... of zip file in temp
  elseif (is_file ($mgmt_config['abs_path_temp'].getobject($media)))
  {
    $media_root = $mgmt_config['abs_path_temp'];
    $media = getobject ($media);
  }  
  else $media_root = "";

  if ($media_root != "")
  {
    // provide thumbnail video file
    if (!empty ($type) && strtolower ($type) == "origthumb")
    {
      $media_info = getfileinfo ($site, getobject ($media), "comp");
      $media_new = $site."/".$media_info['filename'].".orig.mp4";
      
      // check media file
      if ($media_new != "" && is_file ($media_root.$media_new))
      {
        $media = $media_new;
        $media_info_new = getfileinfo ($site, getobject ($media_new), "comp");
      } 
    }
    // convert file if requested
    elseif (!empty ($type) && strtolower ($type) != "original")
    {
      // target path for the temporary file
      $media_target = $mgmt_config['abs_path_temp'];

      // convert file
      $media_new = convertmedia ($site, $media_root.$site."/", $media_target, getobject ($media), $type, $media_config, true);

      // if new file has been converted successfully, set new media root path and new media file name
      if ($media_new != "")
      {
        $media = $media_new;
        $media_info_new = getfileinfo ($site, getobject ($media_new), "comp");
        
        // recheck media location (due to changed location by function convertdocument)
        if (is_file ($media_target.$media_new)) $media_root = $media_target;
        elseif (is_file ($media_root.$site."/".$media_new)) $media_root = $media_root.$site."/";
        else $media = "";
      }
      else $media = "";
    }  
    
    // if media is given
    if ($media != "")
    {
      // if no name has been provided
      if ($name == "")
      {
        if ($objectpath_esc != "") $media_info = getfileinfo ($site, $objectpath_esc, "comp");
        else $media_info = getfileinfo ($site, getobject ($media), "comp");
        
        $name = $media_info['name'];
      }

      // replace file extension if file was converted
      if (!empty ($media_info_new['ext']))
      {
        $name_info = getfileinfo ($site, getobject ($name), "comp");
        $name = $name_info['filename'].$media_info_new['ext'];
      }

      // reset user if a user ID has been provided by the request
      if (!empty ($extuser)) $user = $extuser;

      // stream file content
      downloadfile ($media_root.$media, $name, "download", $user);
    }
    // no media file -> createdocument failed
    else
    {
      header ("HTTP/1.1 400 Internal Server Error", true, 400);
      echo showinfopage ($hcms_lang['the-requested-object-can-not-be-provided'][$lang], $lang);
    }
  }
  else
  {
    header ("HTTP/1.1 400 Invalid Request", true, 400);
    echo showinfopage ($hcms_lang['the-requested-object-can-not-be-provided'][$lang], $lang);
  }
}
else
{
  header ("HTTP/1.1 400 Invalid Request", true, 400);
  echo showinfopage ($hcms_lang['the-requested-object-can-not-be-provided'][$lang], $lang);
}
?>