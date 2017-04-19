<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// template engine
require ("function/hypercms_tplengine.inc.php");


// input parameters
$template = getrequest ("template", "objectname");
$location = getrequest ("location", "locationname");
$css_display = getrequest ("css_display", "objectname");

// extract publication and template name
if (substr_count ($template, "/") == 1) list ($site, $template) = explode ("/", $template);

if ($location != "")
{
  // get publication and category
  $site = getpublication ($location);
  $cat = getcategory ($site, $location); 
  
  // convert location
  $location = deconvertpath ($location, "file");
  $location_esc = convertpath ($site, $location, $cat);
}

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check access permissions
if ($location != "") $ownergroup = accesspermission ($site, $location, $cat);
else $ownergroup = "";

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$viewstore = false;

// generate form
if (valid_publicationname ($site) && valid_objectname ($template))
{
  // call template engine
  $viewstore = buildsearchform ($site, $template, "", $ownergroup, $css_display);
}

// show form
if ($viewstore != false)
{
  echo $viewstore;
}
// check if an error occured during building view
else
{
  echo "<!DOCTYPE html>\n";
  echo "<html lang=\"".getsession("hcms_lang", "en")."\">\n";
  echo "<head>\n";
  echo "<title>hyperCMS</title>\n";
  echo "<meta charset=\"".$mgmt_config[$site]['default_codepage']."\" />\n";
  echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />\n";
  echo "</head>\n";
  echo "<body id=\"hcms_htmlbody\" class=\"hcmsWorkplaceExplorer\" onload=\"parent.hcms_showPage('contentLayer');\">\n";
  echo "</body>\n";
  echo "</html>";
}
?>
