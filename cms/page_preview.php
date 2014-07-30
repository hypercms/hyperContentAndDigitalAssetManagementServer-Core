<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("include/session.inc.php");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");
// template engine
require ("function/hypercms_tplengine.inc.php");
// language file
require_once ("language/page_preview.inc.php");


// input parameters
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$container = getrequest ("container", "objectname");
$buildview = getrequest ("buildview");
$ctrlreload = getrequest_esc ("ctrlreload");

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
  if (($cat != "page" && $cat != "comp") || ($cat == "comp" && $globalpermission[$site]['component'] != 1) || ($cat == "page" && $globalpermission[$site]['page'] != 1) || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);
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

$result = buildview ($site, $location, $page, $user, $buildview, "no", "", $container);

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
    echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<title>hyperCMS</title>\n";
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$lang_codepage[$lang]."\">\n";
    echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\">\n";
    echo "<script src=\"javascript/click.js\" type=\"text/javascript\">\n";
    echo "</script>\n";
    echo "</head>\n";
    echo "<body class=\"hcmsWorkplaceGeneric\">\n";
    echo "<p class=hcmsHeadline>".$text1[$lang]."</p>\n";
    echo $text8[$lang]." -> '".$templatefile."'\n";
    echo "</body>\n</html>";
    exit;
  }
  // if content container is empty
  elseif ($contentdata == "" || $contentdata == false)
  {
    echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<title>hyperCMS</title>\n";
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$lang_codepage[$lang]."\">\n";
    echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\">\n";
    echo "<script src=\"javascript/click.js\" type=\"text/javascript\">\n";
    echo "</script>\n";
    echo "</head>\n";
    echo "<body class=\"hcmsWorkplaceGeneric\">\n";
    echo "<p class=hcmsHeadline>".$text1[$lang]."</p>\n";
    echo $text9[$lang]." -> '".$contentfile."'\n";
    echo "</body>\n</html>";
    exit;
  }  

  // check if an error occured in buildview
  if ($viewstore == false)
  {
    echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<title>hyperCMS</title>\n";
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$lang_codepage[$lang]."1\">\n";
    echo "</head>\n";
    echo "<body class=\"hcmsWorkplaceGeneric\">\n";
    echo "<p class=hcmsHeadline>".$text1[$lang]."</p>\n";
    echo $text2[$lang]."\n";
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

  echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";
  echo "<html>\n";
  echo "<head>\n";
  echo "<title>".$text3[$lang]."</title>\n";
  echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$lang_codepage[$lang]."\">\n";

  $fowardurl = str_replace ($mgmt_config[$site]['abs_path_page'], $mgmt_config[$site]['url_path_page'], $location).$page;

  echo "<meta http-equiv=\"refresh\" content=\"2; URL=".$fowardurl."\">\n";

  echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\">\n";
  echo "</head>\n";

  echo "<body class=\"hcmsWorkplaceGeneric\">\n";

  echo "<p class=hcmsHeadline>".$text4[$lang]."</p>\n";
  echo $text6[$lang]."\n".$text7[$lang]."\n";

  echo "</body>\n";
  echo "</html>\n";
}
?>
