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
// location must provide the converted path
$location = getrequest ("location", "locationname");
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$data = array('success' => false);

if (valid_locationname ($location) && valid_publicationname ($site) && ($cat == "page" || $cat == "comp"))
{   
  if (is_dir (deconvertpath ($location, "file")))
  {
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";   
    $location = $location.".folder";
  }
      
  $data['object_id'] = rdbms_getobject_id ($location);
  $data['object_hash'] = rdbms_getobject_hash ($location);

  if ($data['object_id'] > 0) $data['success'] = true;
}

header ('Content-Type: application/json; charset=utf-8');
print json_encode ($data);
?>