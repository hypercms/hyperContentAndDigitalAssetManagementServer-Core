<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session
define ("SESSION", "create");
// management configuration
require ("../../../config.inc.php");
// hyperCMS API
require ("../../../function/hypercms_api.inc.php");


// input parameters
$site = getrequest ("site", "publicationname");
$action = getrequest ("action");
$token = getrequest ("token"); 

// ------------------------------ permission section --------------------------------

// check plugin permissions
if (!checkpluginpermission ($site, 'logviewer'))
{
  echo showinfopage ($hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang], $lang);
  exit;
}

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// initialize
$show = "";
$add_onload = "";
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="../../../javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript" src="../../../javascript/click.min.js"></script>
<style type="text/css">
<?php
// inverted main colors
if (!empty ($hcms_themeinvertcolors))
{
  if (!empty ($hcms_hoverinvertcolors)) $invertonhover = false;
  else $invertonhover = true;

  echo invertcolorCSS ($hcms_themeinvertcolors, ".hcmsInvertColor", true, $invertonhover);
}
// inverted hover colors
elseif (!empty ($hcms_hoverinvertcolors))
{
  echo invertcolorCSS ($hcms_hoverinvertcolors, ".hcmsInvertColor", false, true);
  echo invertcolorCSS ($hcms_hoverinvertcolors, ".hcmsInvertHoverColor", true, false);
}
?>
</style>
</head>

<body class="hcmsWorkplaceControl" onload="<?php echo $add_onload; ?>">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:20px;", "hcms_infobox_mouseover"); ?>

<?php echo showmessage ($show, 650, 60, $lang, "position:fixed; left:10px; top:10px;"); ?>

<div class="hcmsLocationBar">
  <table class="hcmsTableNarrow">
    <tr>
      <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['custom-system-events'][$lang]); ?></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>  
  </table>
</div>

<!-- toolbar -->
<div class="hcmsToolbar">
  <div class="hcmsToolbarBlock">
    <div class="hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare">
      <img onclick="location='log_export.php?site=<?php echo url_encode ($site); ?>';" class="hcmsButtonSizeSquare" id="media_export" src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_export_page.png" alt="<?php echo getescapedtext ($hcms_lang['export-list-comma-delimited'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['export-list-comma-delimited'][$lang]); ?>" />
    </div>
    <div class="hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare">
      <img onclick="parent['mainFrame'].location='log_list.php?site=<?php echo url_encode ($site); ?>';" class="hcmsButtonSizeSquare" id="media_view" src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_view_refresh.png" alt="<?php echo getescapedtext ($hcms_lang['refresh'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['refresh'][$lang]); ?>" />
    </div>
  </div>
</div>

</body>
</html>
