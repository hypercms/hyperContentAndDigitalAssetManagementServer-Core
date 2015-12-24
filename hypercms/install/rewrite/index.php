<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// definitions (examples)
// publication name
$site = "hyperCMS";
// text ID array (text-ID as key and URL paramaters as value)
$text_id = array ("PermaLink_EN"=>"langcode=EN", "PermaLink_DE"=>"langcode=DE");
// alternative URI on error
$alt_uri = "/home/";

// management configuration
require_once ("/hypercms/config.inc.php");
// hyperCMS API
require_once ($mgmt_config['abs_path_cms']."function/hypercms_api.inc.php");


// get URI
$uri = $_SERVER['REQUEST_URI'];

// split path on slashes
$elements = explode ('/', trim ($uri, "/"));

// no path elements means home
if (empty ($uri) || $uri == "/" || count ($elements) == 0)
{
  $result = rewrite_homepage ($site);
  if (!$result) header ("Location: ".$alt_uri);
}
// forward to target URI
else
{
  $result = rewrite_targetURI ($site, $text_id, $uri);
  if (!$result) header ("Location: ".$alt_uri);
}
?>