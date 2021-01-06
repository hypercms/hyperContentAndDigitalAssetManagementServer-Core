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


// input parameters
$template = getrequest_esc ("template", "objectname");
$site = getrequest_esc ("site", "publicationname");
$cat = getrequest_esc ("cat", "objectname");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'template') || !checkglobalpermission ($site, 'tpl') || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script type="text/javascript" src="javascript/main.min.js"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
  <table class="hcmsTableStandard">
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
    
    // load template
    $data = loadtemplate ($site, $template);
    $tpl_user = "";
    
    if (!empty ($data['content']))
    {
      $temp = getcontent ($data['content'], "<user>");
      if (!empty ($temp[0])) $tpl_user = $temp[0];
    }
    
    // modified date
    $date = date ("Y-m-d H:i", filemtime ($mgmt_config['abs_path_template'].$site."/".$template));
    $date = showdate ($date, "Y-m-d H:i", $hcms_lang_date[$lang]);
  
    echo "
    <tr><td>".getescapedtext ($hcms_lang['template'][$lang])." </td><td class=\"hcmsHeadlineTiny\">".$tpl_name."</td></tr>
    <tr><td>".getescapedtext ($hcms_lang['category'][$lang])." </td><td class=\"hcmsHeadlineTiny\">".$pagecomp."</td></tr>
    <tr><td>".getescapedtext ($hcms_lang['owner'][$lang])." </td><td class=\"hcmsHeadlineTiny\">".$tpl_user."</td></tr>
    <tr><td>".getescapedtext ($hcms_lang['last-updated'][$lang])." </td><td class=\"hcmsHeadlineTiny\">".$date."</td></tr>
    <tr><td>".getescapedtext ($hcms_lang['file-size'][$lang])." </td><td class=\"hcmsHeadlineTiny\">".filesize ($mgmt_config['abs_path_template'].$site."/".$template)." bytes</td></tr>";
  }
  ?>
  </table>
</div>

<?php includefooter(); ?>
</body>
</html>
