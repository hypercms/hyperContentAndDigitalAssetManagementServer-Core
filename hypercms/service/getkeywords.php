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
$site = getrequest ("site", "publicationname");
$lang = getrequest ("lang", "objectname");
$id = getrequest ("id");
$levels = getrequest ("levels", "numeric");

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

// --------------------------------- logic section ----------------------------------

// write and close session (non-blocking other frames)
suspendsession ();

// collect keywords of a taxonomy and return as comma seperated list
if ($id >= 0)
{
  if ($levels < 0) $levels = 5;
  
  $keywords_array = gettaxonomy_childs ($site, $lang, $id, $levels);

  if (is_array ($keywords_array) && sizeof ($keywords_array) > 0)
  {
    $keywords_array = array_unique ($keywords_array);

    // escape commas
    foreach ($keywords_array as &$keyword)
    {
      $keyword = str_replace (",", "¸", $keyword);
    }

    echo implode (",", $keywords_array);
  }
  else echo "";
}
else echo "";
?>