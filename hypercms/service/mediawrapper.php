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
// format file extensions
require ("../include/format_ext.inc.php");  


// input parameters
$site = getrequest_esc ("site", "publicationname");
$media = getrequest_esc ("media", "objectname");
$alt = getrequest_esc ("alt", "objectname");
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
$options = getrequest ("options");
// print HTML as PDF
$url_html2pdf = getrequest ("url_html2pdf");

// external user ID provided by request (Wrapper link)
if (empty ($user)) $extuser = getrequest ("user");

// default language
if (empty ($lang)) $lang = "en";

// ------------------------------ permission section --------------------------------

// check user session if user is logged in
if ($user != "") checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// initialize
$media_approved = false;

// new media configuration parameter since version 8.0.5
// get object hash, media type and config from encrypted cfg value
if (!empty ($wl) && substr ($wl, 0, 5) == "hcms.") 
{
  $cfg = substr ($wl, 5);
  $cfg = hcms_decrypt ($cfg);
  if ($cfg != "" && strpos ($cfg, ":") > 0) list ($wl, $type, $media_config) = explode (":", $cfg);
}

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
if ($object_id != "" && !empty ($mgmt_config['db_connect_rdbms']))
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
		if (is_file ($location.$object))
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

// if no name has been provided
if (empty ($name))
{
  if ($objectpath_esc != "") $media_info = getfileinfo ($site, $objectpath_esc, $cat);
  else $media_info = getfileinfo ($site, getobject ($media), "comp");
  
  $name = $media_info['name'];
}

// ---------------------------------- stream file -----------------------------------
// get publication
if (substr_count ($media, "/") == 1) $site = substr ($media, 0, strpos ($media, "/"));

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

// read multimedia file (publication/file) and submit data
if (valid_locationname ($media) && ((hcms_crypt ($media) == $token && ($user != "" || is_thumbnail ($media, false) || empty ($mgmt_config[$site]['dam']))) || $media_approved == true))
{
  // check IP and public access
  if ($user == "" && (!allowuserip ($site) || !mediapublicaccess ($media)))
  {
    header ('HTTP/1.0 403 Forbidden', true, 403);
    exit;
  }

  // verify that the requested file exists and define location
  // ... of multimedia file in repository
  $temp_media = getmedialocation ($site, getobject($media), "abs_path_media").$site."/".getobject ($media);

  if ((is_file ($temp_media) && (!is_thumbnail ($temp_media) || (is_thumbnail ($temp_media) && valid_jpeg ($temp_media)))) || is_cloudobject ($temp_media))
  {
    $media_root = getmedialocation ($site, $media, "abs_path_media");
  }
  // ... of template media file
  elseif (is_file ($mgmt_config['abs_path_tplmedia'].$media))
  {
    $media_root = $mgmt_config['abs_path_tplmedia'];
  }
  // ... of zip file or converted file in temp
  elseif (is_file ($mgmt_config['abs_path_temp'].getobject($media)) && !empty ($user))
  {
    $media_root = $mgmt_config['abs_path_temp'];
    $media = getobject ($media);
  }
  // use alternative media file from design theme
  elseif (!empty ($alt) && is_file (getthemelocation ("", "path")."img/".$alt))
  {
    // display inline
    header ("Content-Disposition: inline; filename=\"".$name."\"");
    // content-type
    header ("Content-Type: ".getmimetype ($alt));
    // keep in cache for 30 days
    header ("Cache-Control: max-age=2592000, public");
    header ("Expires: ".gmdate ('D, d M Y H:i:s', time() + 2592000) . ' GMT');

    readfile (getthemelocation("", "path")."img/".$alt);
    exit;
  }

  // download media file
  if (!empty ($media_root))
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

      // advanced image editing options used in download and wrapper links
      if (is_image ($media) && empty ($media_config) && !empty ($type))
      {
        if (!empty ($options))
        {
          // try to create a "somehow" unique media-config parameter
          $media_config = substr (md5 ($options), 0, 6);

          // reset image options and set format/type
          $mgmt_imageoptions = array();
          $mgmt_imageoptions[".".$type][$media_config] = "-f ".$type." ".$options;
        }
        else
        {
          // convert to requested file type without conversion options
          $media_config = "orig";

          // reset image options and set format/type
          $mgmt_imageoptions = array();
          $mgmt_imageoptions[".".$type][$media_config] = "-f ".$type;
        }
      }

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

    // don't show HTML-files directly in browser since JS-code can be embedded in the file 
    $media_info = getfileinfo ($site, $media_root.$media, "comp");

    if (is_array ($media_info) && substr_count (strtolower ($hcms_ext['cms']).".", $media_info['ext'].".") > 0)
    {
      header ('HTTP/1.0 403 Forbidden', true, 403);
      echo showinfopage ($hcms_lang['the-live-view-of-the-file-is-not-allowed'][$lang])."<br />".getescapedtext ($hcms_lang['please-download-the-file-in-order-to-view-its-content'][$lang], $lang);
      exit;
    }
  
    // if media is given
    if (!empty ($media))
    {
      // replace file extension if file was converted
      if (!empty ($media_info_new['ext']))
      {
        $name_info = getfileinfo ($site, getobject ($name), "comp");
        $name = $name_info['filename'].$media_info_new['ext'];
      }

      // reset user if a user ID has been provided by the request
      if (!empty ($extuser)) $user = $extuser;

      // stream file content
      downloadfile ($media_root.$media, $name, "wrapper", $user);
    }
    else
    {
      header ("HTTP/1.1 400 Invalid Request", true, 400);
      exit;
    }
  }
  else
  {
    header ("HTTP/1.1 400 Invalid Request", true, 400);
    exit;
  }
}
// page or component
elseif (!empty ($objectpath_esc) && is_file ($location.$object))
{
  // reset user if a user ID has been provided by the request
  if (!empty ($extuser)) $user = $extuser;

  // convert HTML to PDF
  if (strtolower ($type) == "pdf")
  {
    // temp pdf file
    $pdf_file = $mgmt_config['abs_path_temp'].$name.".pdf";
    
    // create PDF
    $pdf_result = html2pdf ($location.$object, $pdf_file);

    if ($pdf_result)
    {
      downloadfile ($pdf_file, $name.".pdf", "wrapper", $user);
      exit;
    }
    else
    {
      header ("HTTP/1.1 400 Invalid Request", true, 400);
      exit;
    }
  }
  // provide HTML
  else
  {
    downloadobject ($location, $object, $container, $lang, $user);
    exit;
  }
}
// no content available
else
{
  header ("HTTP/1.1 400 Invalid Request", true, 400);
  exit;
}
?>