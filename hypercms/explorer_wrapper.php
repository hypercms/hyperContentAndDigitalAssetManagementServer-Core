<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("include/session.inc.php");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");
// format file extensions
require ("include/format_ext.inc.php");  
// language file
require_once ("language/page_view.inc.php");


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
// new crpyted media parameter since version 5.6.3
$wm = getrequest ("wm", "url");
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
if ($wl != "" && $mgmt_config['db_connect_rdbms'] != "")
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
    echo showinfopage ($text8[$lang], $lang);
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
      echo showinfopage ($text8[$lang], $lang);
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
// get media from crypted video string
if ($wm != "")
{
  $media = hcms_decrypt ($wm);
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
      
    if ($user == "") $user = "sys";
    $zip_filename = uniqid ("tmp");
    
    // zip all files in temp folder
    $result = zipfiles ($site, array ($location), $mgmt_config['abs_path_cms']."temp/", $zip_filename, $user, "download");
    
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
// Publication
if (substr_count ($media, "/") == 1) $site = substr ($media, 0, strpos ($media, "/"));

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// read multimedia file (publication/file) and submit data
if (valid_objectname ($media) && ((hcms_crypt ($media) == $token && ($user != "" || is_thumbnail ($media, false) || !$mgmt_config[$site]['dam'])) || $media_approved))
{
  // check ip access if public access
  if ($user == "" && !allowuserip ($site))
  {
    header ('HTTP/1.0 403 Forbidden', true, 403);
    echo showinfopage ($text14[$lang], $lang);
    exit;
  }

  // location
  if (@is_file (getmedialocation ($site, $media, "abs_path_media").$media))
  {
    $media_root = getmedialocation ($site, $media, "abs_path_media");
  }
  elseif (@is_file ($mgmt_config['abs_path_tplmedia'].$media))
  {
    $media_root = $mgmt_config['abs_path_tplmedia'];
  }
  elseif (@is_file($mgmt_config['abs_path_cms'].'temp/'.getobject($media)) && $user != "")
  {
    $media_root = $mgmt_config['abs_path_cms'].'temp/';
    $media = getobject ($media);
  }
  else $media_root = "";

  // eventsystem
  if ($eventsystem['onfiledownload_pre'] == 1) onfiledownload_pre ($site, $media_root, $media, $name, $user); 
  
  $media_info_new = Null;

  if ($media_root != "")
  {
    // convert file
    if ($type != "")
    {
      // target path for the temporary file
      $media_target = $mgmt_config['abs_path_cms'].'temp/';
      $media_old = $site."/".getobject ($media);
      
      // information needed to extract the file name only
      $media_info_original = getfileinfo ($site, getobject ($media), "comp");
      
      // Predicting the name the file will get by createmedia
      $newname = $media_info_original['filename'].'.'.$media_config.'.'.$type;
      
      // convert-config is empty when we are using unoconv
      if ($media_config == "")
      {
        $result_conv = createdocument ($site, $media_root.$site."/", $media_target, getobject ($media), $type, true);
      }
      else 
      {
        // generate new file only if necessary
        if (!is_file ($media_target.$newname) || @filemtime ($media_root.$media_old) > @filemtime ($media_target.$newname)) 
        {
          $result_conv = createmedia ($site, $media_root.$site."/", $media_target, getobject ($media), $type, $media_config, true);
        }
        // use the existing file
        else $result_conv = $newname;
      }
      
      if ($result_conv != "") 
      { 
        $media_new = $media = $result_conv;
        $media_info_new = getfileinfo ($site, getobject ($media), "comp");
        
        // define new name
        if ($name != "")
        {
          $name_info = getfileinfo ($site, getobject ($name), "comp");
          $name = $name_info['filename'].$media_info_new['ext'];
        }
        
        $media_root = $media_target;
      }
      else $media = "";
    }  
  
    // don't show HTML-files directly in browser since JS-code can be embedded in the file 
    $media_info = getfileinfo ($site, $media_root.$media, "comp");
    
    if (is_array ($media_info) && substr_count ($hcms_ext['cms'].".", $media_info['ext'].".") > 0)
    {
      header ('HTTP/1.0 403 Forbidden', true, 403);
      echo showinfopage ($text11[$lang]."<br />".$text12[$lang], $lang);
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
        if (isset ($media_info_new['ext']) && $media_info_new['ext'] != "")
        {
          $name_info = getfileinfo ($site, getobject ($name), "comp");
          $name = $name_info['filename'].$media_info_new['ext'];
        }
      }
       
      // for IE the name must by urlencoded
      if (substr_count ($_SERVER['HTTP_USER_AGENT'], "MSIE") > 0) $name = rawurlencode ($name);

      // stream file content
      downloadfile ($media_root.$media, $name, "wrapper", $user);
      
      // eventsystem
      if ($eventsystem['onfiledownload_post'] == 1) onfiledownload_post ($site, $media_root, $media, $name, $user);
    }
  }
}
// page or component
elseif ($objectpath_esc != "" && is_file ($location.$object))
{
  downloadobject ($location, $object, $container, $lang, $user);
}
// not content available
else
{
  header ("HTTP/1.1 400 Invalid Request", true, 400);
  //echo showinfopage ($text14[$lang], $lang);
}
?>