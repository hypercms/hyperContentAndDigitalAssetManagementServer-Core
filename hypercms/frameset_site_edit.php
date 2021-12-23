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
$site_name = url_encode (getrequest ("site_name", "url"));

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// write and close session (non-blocking other frames)
if (session_id() != "") session_write_close();
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=<?php echo windowwidth ("object"); ?>, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
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
      <a href="javascript:void(0);" onClick="hcms_elementbyIdStyle('tab1','hcmsTabActive'); hcms_elementbyIdStyle('tab2','hcmsTabPassive'); hcms_displayLayers('settingsLayer','','show', 'inheritanceLayer','','hide');" title="<?php echo getescapedtext ($hcms_lang['configuration'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['configuration'][$lang]); ?></a>
    </div>
    <div id="tab2" class="hcmsTabPassive">
      <a href="javascript:void(0);" onClick="hcms_elementbyIdStyle('tab1','hcmsTabPassive'); hcms_elementbyIdStyle('tab2','hcmsTabActive'); hcms_displayLayers('settingsLayer','','hide', 'inheritanceLayer','','show');" title="<?php echo getescapedtext ($hcms_lang['inheritance'][$lang]); ?>"><?php echo getescapedtext ($hcms_lang['inheritance'][$lang]); ?></a>
    </div>
  </div>

  <!-- publication settings -->
  <div id="settingsLayer" style="position:fixed; top:24px; right:0; bottom:0; left:0; margin:0; padding:0; <?php echo $css_iphone; ?>">
    <iframe name="mainFrame1" src="<?php echo "site_edit_form.php?site_name=".$site_name; ?>" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; <?php if (!$is_mobile) echo "overflow:auto;"; else echo "overflow:scroll;"; ?>"></iframe>
  </div>

  <!-- publication inheritance -->
  <div id="inheritanceLayer" style="position:fixed; top:24px; right:0; bottom:0; left:0; margin:0; padding:0; display:none; <?php echo $css_iphone; ?>">
    <iframe name="mainFrame2" src="<?php echo "site_edit_inheritance.php?site_name=".$site_name; ?>" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; <?php if (!$is_mobile) echo "overflow:auto;"; else echo "overflow:scroll;"; ?>"></iframe>
  </div>

</body>
</html>