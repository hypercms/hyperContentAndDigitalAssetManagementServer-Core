<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */

// Session will not be used, due to issues with mobile browsers.
// So only the IP address of the viewer/visitor can be tracked.
 
// management configuration
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");


// input parameters
// new crypted media parameter since version 5.6.3
$wm = getrequest ("wm", "url");

// --------------------------------- logic section ----------------------------------

// get media from crypted media string
if ($wm != "")
{
  // decrypt wrapper media string
  $media = hcms_decrypt ($wm);
  
  // get publication
  if (substr_count ($media, "/") == 1) list ($site, $mediafile) = explode ("/", $media);
  elseif (substr_count ($media, "/") == 2) list ($site, $container_id, $mediafile) = explode ("/", $media);
  else $mediafile = $media;
  
  // check media file name
  if (valid_objectname ($mediafile) || is_thumbnail ($media, false))
  {
    // check IP access
    if ($site != "" && !allowuserip ($site))
    {
      header ('HTTP/1.0 403 Forbidden', true, 403);
      exit;
    }

    // media location
    // ... of multimedia file in repository
    if (is_file (getmedialocation ($site, $media, "abs_path_media").$media) || is_cloudobject (getmedialocation ($site, getobject($media), "abs_path_media").$site."/".getobject ($media)))
    {
      $media_root = getmedialocation ($site, $media, "abs_path_media");
    }
    // ... of multimedia file with no valid container identifier in repository (cloud storage is not supported)
    elseif (!empty ($container_id) && is_file (getmedialocation ($site, ".hcms.dummy_hcm".$container_id.".hcm", "abs_path_media").$media))
    {
      $media_root = getmedialocation ($site, ".hcms.dummy_hcm".$container_id.".hcm", "abs_path_media");
    }
    // ... of temp file
    elseif (is_file ($mgmt_config['abs_path_temp'].getobject($media)))
    {
      $media_root = $mgmt_config['abs_path_temp'];
      $media = getobject ($media);
    }
    else $media_root = "";

    // if media is given
    if (valid_locationname ($media_root) && valid_objectname ($media))
    {
      // stream file content
      downloadfile ($media_root.$media, "hypercms-mediastream", "wrapper", "");
      exit;
    }
    else
    {
      header ("HTTP/1.1 400 Invalid Request", true, 400);
      exit;
    }
  }
  // no content available
  else
  {
    header ("HTTP/1.1 400 Invalid Request", true, 400);
    exit;
  }
}
// no input provided
else
{
  header ("HTTP/1.1 400 Invalid Request", true, 400);
  exit;
}
?>
