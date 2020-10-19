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
// template engine
require ("function/hypercms_tplengine.inc.php");


// input parameters
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$container = getrequest ("container", "objectname");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check access permissions (DAM)
if ($mgmt_config[$site]['dam'] == true)
{
  $ownergroup = accesspermission ($site, $location, $cat);
  $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);
  if ($setlocalpermission['root'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);
}
// check permissions
else
{
  if (($cat != "page" && $cat != "comp") || ($cat == "comp" && !checkglobalpermission ($site, 'component')) || ($cat == "page" && !checkglobalpermission ($site, 'page')) || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);
}

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// ------------------------------ call template engine ------------------------------
// script to build the view of the page 
$buildview = "preview";

$result = buildview ($site, $location, $page, $user, $buildview, "yes", "", $container);

$viewstore = $result['view'];
$contentfile = $result['container'];
$contentdata = $result['containerdata'];
$templatefile = $result['template'];
$templatedata = $result['templatedata'];  
// -----------------------------------------------------------------------------

if ($templatefile != false || $contentfile != false)
{ 
  // if template is empty
  if ($templatedata == "" || $templatedata == false)
  {
    echo "<!DOCTYPE html>\n";
    echo "<html lang=\"".getsession ("hcms_lang", "en")."\">\n";
    echo "<head>\n";
    echo "<title>hyperCMS</title>\n";
    echo "<meta charset=\"".getcodepage ($lang)."\" />\n";
    echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />\n";
    echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."\" />\n";
    echo "<script src=\"javascript/click.min.js\" type=\"text/javascript\"></script>\n";
    echo "</head>\n";
    echo "<body class=\"hcmsWorkplaceGeneric\">\n";
    echo "<p class=hcmsHeadline>".getescapedtext ($hcms_lang['could-not-create-view-of-page'][$lang])."</p>\n";
    echo "&nbsp;".getescapedtext ($hcms_lang['the-associated-template-holds-no-informations'][$lang])." -> '".$templatefile."'\n";
    echo "</body>\n</html>";
    exit;
  }
  // if content container is empty
  elseif ($contentdata == "" || $contentdata == false)
  {
    echo "<!DOCTYPE html>\n";
    echo "<html lang=\"".getsession ("hcms_lang", "en")."\">\n";
    echo "<head>\n";
    echo "<title>hyperCMS</title>\n";
    echo "<meta charset=\"".getcodepage ($lang)."\" />\n";
    echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />\n";
    echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."\" />\n";
    echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."\" />\n";
    echo "<script src=\"javascript/click.min.js\" type=\"text/javascript\"></script>\n";
    echo "</head>\n";
    echo "<body class=\"hcmsWorkplaceGeneric\">\n";
    echo "<p class=hcmsHeadline>".getescapedtext ($hcms_lang['could-not-create-view-of-page'][$lang])."</p>\n";
    echo "&nbsp;".getescapedtext ($hcms_lang['the-content-container-holds-no-informations'][$lang])." -> '".$contentfile."'\n";
    echo "</body>\n</html>";
    exit;
  }  

  // check if an error occured in buildview
  if ($viewstore == false)
  {
    echo "<!DOCTYPE html>\n";
    echo "<html lang=\"".getsession ("hcms_lang", "en")."\">\n";
    echo "<head>\n";
    echo "<title>hyperCMS</title>\n";
    echo "<meta charset=\"".getcodepage ($lang)."\" />\n";
    echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />\n";
    echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."\" />\n";
    echo "<script src=\"javascript/click.min.js\" type=\"text/javascript\"></script>\n";
    echo "</head>\n";
    echo "<body class=\"hcmsWorkplaceGeneric\">\n";
    echo "<p class=hcmsHeadline>".getescapedtext ($hcms_lang['could-not-create-view-of-page'][$lang])."</p>\n";
    echo "&nbsp;".getescapedtext ($hcms_lang['an-error-occured-while-creating-the-view'][$lang])."\n";
    echo "</body>\n</html>";
  }
  // output view
  else
  {
    echo $viewstore;
  }
}
else
{
  // ---------------------build view of live page-----------------------

  echo "<!DOCTYPE html>\n";
  echo "<html lang=\"".getsession ("hcms_lang", "en")."\">\n";
  echo "<head>\n";
  echo "<title>".getescapedtext ($hcms_lang['refresh-view'][$lang])."</title>\n";
  echo "<meta charset=\"".getcodepage ($lang)."\" />\n";

  $fowardurl = str_replace ($mgmt_config[$site]['abs_path_page'], $mgmt_config[$site]['url_path_page'], $location).$page;

  echo "<meta http-equiv=\"refresh\" content=\"2; URL=".$fowardurl."\" />\n";

  echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />\n";
  echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."\" />\n";
  echo "</head>\n";

  echo "<body class=\"hcmsWorkplaceGeneric\">\n";

  echo "<p class=hcmsHeadline>".getescapedtext ($hcms_lang['this-object-is-not-managed-by-hypercms'][$lang])."</p>\n";
  echo "&nbsp;".getescapedtext ($hcms_lang['you-wont-be-able-to-change-the-content-of-this-item'][$lang])."\n";

  echo "</body>\n";
  echo "</html>\n";
}
?>
