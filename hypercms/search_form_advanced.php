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
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");
// template engine
require ("function/hypercms_tplengine.inc.php");


// input parameters
$template = getrequest_esc ("template", "objectname");
$location = getrequest_esc ("location", "locationname");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);

if (!valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($template)) killsession ($user);
// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// generate form
if ($site != "" && $template != "")
{
  // ---------------------------- call template engine ---------------------------    
  $viewstore = buildsearchform ($site, $template, $ownergroup);

  // show form
  if ($viewstore != false)
  {
    echo $viewstore;
  }
  // check if an error occured during building view
  else
  {
    echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<title>hyperCMS</title>\n";
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$mgmt_config[$site]['default_codepage']."\">\n";
    echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\">\n";
    echo "</head>\n";
    echo "<body class=\"hcmsWorkplaceGeneric\">\n";
    echo "<p class=hcmsHeadline>".$hcms_lang['could-not-create-view-of-the-object'][$lang]."</p>\n";
    echo $hcms_lang['an-error-occured-while-creating-the-view'][$lang]."\n";
    echo "</body>\n";
    echo "</html>";
  }
}
?>
