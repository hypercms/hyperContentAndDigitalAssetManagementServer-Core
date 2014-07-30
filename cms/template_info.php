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
// language file
require_once ("language/template_info.inc.php");


// input parameters
$template = getrequest_esc ("template", "objectname");
$site = getrequest_esc ("site", "publicationname");
$cat = getrequest_esc ("cat", "objectname");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if ($globalpermission[$site]['template'] != 1 || $globalpermission[$site]['tpl'] != 1 || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric" style="width:90%; height:90%;">
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<table border=0 cellspacing=3 cellpadding=0>
<?php
// ---------------------------- template info ---------------------------
if ($template != "")
{
  // define template name
  if (strpos ($template, ".inc.tpl") > 0)
  {
    $tpl_name = substr ($template, 0, strpos ($template, ".inc.tpl"));
    $pagecomp = "template component";
  }
  elseif (strpos ($template, ".page.tpl") > 0)
  {
    $tpl_name = substr ($template, 0, strpos ($template, ".page.tpl"));
    $pagecomp = "page template";
  }
  elseif (strpos ($template, ".comp.tpl") > 0)
  {
    $tpl_name = substr ($template, 0, strpos ($template, ".comp.tpl"));
    $pagecomp = "component template";
  }
  elseif (strpos ($template, ".meta.tpl") > 0)
  {
    $tpl_name = substr ($template, 0, strpos ($template, ".meta.tpl"));
    $pagecomp = "meta data template";
  }  

  echo "<tr><td>".$text0[$lang].": </td><td class=\"hcmsHeadlineTiny\">".$tpl_name."</td></tr>\n";
  echo "<tr><td>".$text2[$lang].": </td><td class=\"hcmsHeadlineTiny\">".$pagecomp."</td></tr>\n";
  echo "<tr><td>".$text3[$lang].": </td><td class=\"hcmsHeadlineTiny\">".date ("Y-m-d H:i", filemtime ($mgmt_config['abs_path_template'].$site."/".$template))."</td></tr>\n";
  echo "<tr><td>".$text4[$lang].": </td><td class=\"hcmsHeadlineTiny\">".filesize ($mgmt_config['abs_path_template'].$site."/".$template)." bytes</td></tr>\n";
}
?>
</table>

</div>
</body>
</html>