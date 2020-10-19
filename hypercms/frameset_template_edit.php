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
$site = url_encode (getrequest ("site", "url"));
$cat = url_encode (getrequest ("cat", "url"));
$template = url_encode (getrequest ("template", "url"));

// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=<?php echo windowwidth ("object"); ?>, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script type="text/javascript" src="javascript/main.min.js"></script>
<script type="text/javascript">
// disable transition effect
hcms_transitioneffect = false;
</script>
</head>

<body class="hcmsWorkplaceGeneric">
<?php
// iPad and iPhone requires special CSS settings
if ($is_iphone) $css_iphone = " overflow:scroll !important; -webkit-overflow-scrolling:touch !important;";
else $css_iphone = "";
?>
  <!-- tabs -->
  <div id="tabLayer" class="hcmsTabContainer hcmsWorkplaceControlWallpaper" style="position:fixed; visibility:visible; left:0; right:0; top:0; z-index:10;">
    <div id="tab1" class="hcmsTabActive">
      <a href="javascript:void(0);" onClick="hcms_elementbyIdStyle('tab1','hcmsTabActive'); hcms_elementbyIdStyle('tab2','hcmsTabPassive'); hcms_elementbyIdStyle('tab3','hcmsTabPassive'); hcms_displayLayers('editLayer','','show', 'versionLayer','','hide', 'infoLayer','','hide');" title="<?php echo getescapedtext ($hcms_lang['template'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['template'][$lang]); ?></a>
    </div>
    <div id="tab2" class="hcmsTabPassive">
      <a href="javascript:void(0);" onClick="hcms_elementbyIdStyle('tab1','hcmsTabPassive'); hcms_elementbyIdStyle('tab2','hcmsTabActive'); document.getElementById('mainFrame2').src='version_template.php?site=<?php echo $site; ?>&cat=<?php echo $cat; ?>&template=<?php echo $template; ?>'; hcms_elementbyIdStyle('tab3','hcmsTabPassive'); hcms_displayLayers('editLayer','','hide', 'versionLayer','','show', 'infoLayer','','hide');" title="<?php echo getescapedtext ($hcms_lang['version'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['version'][$lang]); ?></a>
    </div>
    <div id="tab3" class="hcmsTabPassive">
      <a href="javascript:void(0);" onClick="hcms_elementbyIdStyle('tab1','hcmsTabPassive'); hcms_elementbyIdStyle('tab2','hcmsTabPassive'); hcms_elementbyIdStyle('tab3','hcmsTabActive'); hcms_displayLayers('editLayer','','hide', 'versionLayer','','hide', 'infoLayer','','show');" title="<?php echo getescapedtext ($hcms_lang['information'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['information'][$lang]); ?></a>
    </div>
  </div>

  <!-- template editor -->
  <div id="editLayer" style="position:fixed; top:24px; right:0; bottom:0; left:0; margin:0; padding:0; <?php echo $css_iphone; ?>">
    <iframe name="mainFrame1" src="template_edit.php?site=<?php echo $site; ?>&cat=<?php echo $cat; ?>&save=no&template=<?php echo $template; ?>" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; <?php if (!$is_mobile) echo "overflow:auto;"; else echo "overflow:scroll;"; ?>"></iframe>
  </div>

  <!-- template versions -->
  <div id="versionLayer" style="position:fixed; top:24px; right:0; bottom:0; left:0; margin:0; padding:0; display:none; <?php echo $css_iphone; ?>">
    <iframe name="mainFrame2" id="mainFrame2" src="" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; <?php if (!$is_mobile) echo "overflow:auto;"; else echo "overflow:scroll;"; ?>"></iframe>
  </div>

  <!-- template info -->
  <div id="infoLayer" style="position:fixed; top:24px; right:0; bottom:0; left:0; margin:0; padding:0; display:none; <?php echo $css_iphone; ?>">
    <iframe name="mainFrame3" src="template_info.php?site=<?php echo $site; ?>&cat=<?php echo $cat; ?>&template=<?php echo $template; ?>" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; <?php if (!$is_mobile) echo "overflow:auto;"; else echo "overflow:scroll;"; ?>"></iframe>
  </div>

</body>
</html>