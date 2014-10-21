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
require_once ("language/template_view.inc.php");


// input parameters
$site = getrequest ("site", "publicationname");
$cat = getrequest ("cat", "objectname");
$template = getrequest ("template", "objectname");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// force cat for DAM usage and check requested template
if ($mgmt_config[$site]['dam'] == true)
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
  echo "<p class=hcmsHeadline>".$text0[$lang]."</p>\n";
  echo $text1[$lang]."\n";
  echo "</body>\n</html>";
}
// check if an error occured during inclusions
elseif ($viewstore == false)
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
  echo "<p class=hcmsHeadline>".$text0[$lang]."</p>\n";
  echo $text2[$lang]."\n";
  echo "</body>\n</html>";
}
else echo $viewstore;
?>