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
checkusersession ($user);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceControl">

  <div id="tabLayer" class="hcmsTabContainer" style="position:absolute; z-index:10; visibility:visible; left:0px; top:1px">
    <div id="tab1" class="hcmsTabActive">
      <a href="template_edit.php?site=<?php echo $site; ?>&cat=<?php echo $cat; ?>&save=no&template=<?php echo $template; ?>" target="mainFrame2" onClick="hcms_ElementbyIdStyle('tab1','hcmsTabActive'); hcms_ElementbyIdStyle('tab2','hcmsTabPassive'); hcms_ElementbyIdStyle('tab3','hcmsTabPassive');" title="<?php echo getescapedtext ($hcms_lang['template'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['template'][$lang]); ?></a>
    </div>
    <div id="tab2" class="hcmsTabPassive">
      <a href="version_template.php?site=<?php echo $site; ?>&cat=<?php echo $cat; ?>&template=<?php echo $template; ?>" target="mainFrame2" onClick="hcms_ElementbyIdStyle('tab1','hcmsTabPassive'); hcms_ElementbyIdStyle('tab2','hcmsTabActive'); hcms_ElementbyIdStyle('tab3','hcmsTabPassive');" title="<?php echo getescapedtext ($hcms_lang['version'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['version'][$lang]); ?></a>
    </div>
    <div id="tab3" class="hcmsTabPassive">
      <a href="template_info.php?site=<?php echo $site; ?>&cat=<?php echo $cat; ?>&template=<?php echo $template; ?>" target="mainFrame2" onClick="hcms_ElementbyIdStyle('tab1','hcmsTabPassive'); hcms_ElementbyIdStyle('tab2','hcmsTabPassive'); hcms_ElementbyIdStyle('tab3','hcmsTabActive');" title="<?php echo getescapedtext ($hcms_lang['information'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['information'][$lang]); ?></a>
    </div>
  </div>
  
</body>
</html>