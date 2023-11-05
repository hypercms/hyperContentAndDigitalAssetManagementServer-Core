<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");


// input parameters
$site = getrequest_esc ("site", "publicationname");
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$container = getrequest ("container", "objectname");

// publication management config
if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
{
  require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
}

// ------------------------------ permission section --------------------------------

// define category if undefined
$cat = getcategory ($site, $location);

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

if ($ownergroup == false || $setlocalpermission['root'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// write and close session (non-blocking other frames)
suspendsession ();

// load object file and get container file
$objectdata = loadfile ($location, $page);
$contentfile = getfilename ($objectdata, "content");

// read multimedia file (publication/file) and submit data
if (valid_objectname ($contentfile))
{
  // get container id
  $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml"));
  
  // load given content container
  if ($container != "" && substr_count ($container, $container_id) == 1)
  {
    $contentfile = $container;
  }

  // get content location
  $container_root = getcontentlocation ($container_id, 'abs_path_content');

  if ($container_root != "" && is_file ($container_root.$contentfile))
  {     
    $bytelen = filesize ($container_root.$contentfile);
    
    // define header
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: XML Container");
    header("Content-Type: text/xml");
    header("Content-Disposition: inline; filename=\"".$container."\"");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".$bytelen);
    
    $xml = loadcontainer ($contentfile, "version", $user);

    // unescape characters & < >
    $xml = str_replace ("&amp;", "&", $xml);
    $xml = str_replace ("&lt;", "<", $xml);
    $xml = str_replace ("&gt;", ">", $xml);

    echo $xml;
  }
}
?>