<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// management configuration
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");


// input parameters
// new crpyted media parameter since version 5.6.3
$wm = getrequest ("wm", "url");

// --------------------------------- logic section ----------------------------------

// get media from crypted video string
if ($wm != "")
{
  $media = hcms_decrypt ($wm);
  $media_approved = true;  
}

// get publication
if (substr_count ($media, "/") == 1) $site = substr ($media, 0, strpos ($media, "/"));

// check media file name
if (valid_objectname ($media) && is_thumbnail ($media, false))
{
  // check IP access
  if ($site != "" && !allowuserip ($site))
  {
    header ('HTTP/1.0 403 Forbidden', true, 403);
    exit;
  }
  
  // location
  if (@is_file (getmedialocation ($site, $media, "abs_path_media").$media))
  {
    $media_root = getmedialocation ($site, $media, "abs_path_media");
  }
  elseif (@is_file ($mgmt_config['abs_path_temp'].getobject($media)))
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
  }
  else
  {
    header ("HTTP/1.1 400 Invalid Request", true, 400);
  }
}
// no content available
else
{
  header ("HTTP/1.1 400 Invalid Request", true, 400);
}
?>
