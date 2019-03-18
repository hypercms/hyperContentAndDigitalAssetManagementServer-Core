<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
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
$site = getrequest ("site", "publicationname");
$cat = getrequest ("cat", "objectname");
$template = getrequest ("template", "objectname");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// force cat for DAM usage and check requested template
if (!empty ($mgmt_config[$site]['dam']))
{
  if (substr_count ($template, ".meta.tpl") == 0 && substr_count ($template, ".comp.tpl") == 0) killsession ($user);
}

// check permissions
// Attention! template_view is also used for creating and changing templates in objects
if (!valid_publicationname ($site) || !valid_objectname ($template) || !checkpublicationpermission ($site)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// ------------------------------ call template engine ------------------------------
// script to build the view of the page 
// template is provided as global variable
$buildview = "template";

$result = buildview ($site, "", "", $user, $buildview, "no", $template);

$viewstore = $result['view'];
$contentfile = $result['container'];
$contentdata = $result['containerdata'];
$templatefile = $result['template'];
$templatedata = $result['templatedata'];  
$pagename = $result['name'];
$filetype = $result['objecttype'];  
// ---------------------------------------------------------------------------------

if ($templatedata == false || $templatedata == "")
{
  echo "<!DOCTYPE html>\n";
  echo "<html lang=\"".getsession("hcms_lang", "en")."\">\n";
  echo "<head>\n";
  echo "<title>hyperCMS</title>\n";
  echo "<meta charset=\"".getcodepage ($lang)."\" />\n";
  echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />\n";
  echo "<script src=\"javascript/click.js\" type=\"text/javascript\" />\n";
  echo "</script>\n";
  echo "</head>\n";
  echo "<body class=\"hcmsWorkplaceGeneric\">\n";
  echo "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['could-not-create-view-of-template'][$lang])."</p>\n";
  echo getescapedtext ($hcms_lang['the-template-holds-no-information'][$lang])."\n";
  echo "</body>\n</html>";
}
// check if an error occured during inclusions
elseif ($viewstore == false)
{
  echo "<!DOCTYPE html>\n";
  echo "<html lang=\"".getsession("hcms_lang", "en")."\">\n";
  echo "<head>\n";
  echo "<title>hyperCMS</title>\n";
  echo "<meta charset=\"".getcodepage ($lang)."\" />\n";
  echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />\n";
  echo "<script src=\"javascript/click.js\" type=\"text/javascript\" />\n";
  echo "</script>\n";
  echo "</head>\n";
  echo "<body class=\"hcmsWorkplaceGeneric\">\n";
  echo "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['could-not-create-view-of-template'][$lang])."</p>\n";
  echo getescapedtext ($hcms_lang['an-error-occured-during-inclusion-of-a-template-component'][$lang])."\n";
  echo "</body>\n</html>";
}
else echo $viewstore;
?>