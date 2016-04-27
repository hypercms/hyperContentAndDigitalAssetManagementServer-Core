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


// input parameters
// location must provide the converted path
$site = getrequest ("site", "publicationname");
$lang = getrequest ("lang", "objectname");
$id = getrequest ("id");
$levels = getrequest ("levels", "numeric");

// publication management config
if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
{
  require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
}

// --------------------------------- logic section ----------------------------------

// collect keywords of a taxonomy and return as comma seperated list
if ($id >= 0)
{
  if ($levels < 0) $levels = 5;
  
  $keywords_array = gettaxonomy_childs ($site, $lang, $id, $levels);

  if (is_array ($keywords_array) && sizeof ($keywords_array) > 0) echo implode (",", $keywords_array);
  else echo "";
}
else echo "";
?>