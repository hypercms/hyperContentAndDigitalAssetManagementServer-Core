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
// language file of plugin
require_once ("../lang/control.inc.php");


// input parameters
$plugin = getrequest_esc ("plugin");
$page = getrequest_esc ("page", "locationname");

// only german and english is supported by plugin
if ($lang != "en" && $lang != "de") $lang = "en";

// ------------------------------ permission section --------------------------------

// check plugin permissions
if (!checkpluginpermission ('', 'test'))
{
  echo showinfopage ($hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang], $lang);
  exit;
}

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------
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
<?php echo showdynamicCSS ($hcms_themeinvertcolors, $hcms_hoverinvertcolors); ?>
</style>
</head>

  <body class="hcmsWorkplaceControl">

    <!-- workplace control title -->
    <div class="hcmsLocationBar">
      <table class="hcmsTableNarrow">
        <tr>
          <td class="hcmsHeadline">Test Plugin</td>
        </tr>
        <tr>
          <td>&nbsp;</td>
        </tr>  
      </table>
    </div>

    <!-- toolbar -->
    <div class="hcmsToolbar hcmsWorkplaceControl" style="<?php echo gettoolbarstyle ($is_mobile); ?>">
      <div class="hcmsToolbarBlock">
        <div class="hcmsButton hcmsHoverColor hcmsInvertColor" onclick="parent.frames['mainFrame'].location='page.php?<?php echo 'plugin='.url_encode($plugin).'&page='.url_encode($page); ?>&content=featureA';">
          <img class="hcmsButtonSizeSquare hcmsFloatLeft" src="../img/button_a.png" alt="<?php echo getescapedtext ($hcms_lang['feature-a'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['feature-a'][$lang]); ?>" />
          <span class="hcmsButtonLabel"><?php echo getescapedtext($hcms_lang['feature-a'][$lang]); ?></span>
        </div>
        <div class="hcmsButton hcmsHoverColor hcmsInvertColor" onclick="parent.frames['mainFrame'].location='page.php?<?php echo 'plugin='.url_encode($plugin).'&page='.url_encode($page); ?>&content=featureB';">
          <img class="hcmsButtonSizeSquare hcmsFloatLeft" src="../img/button_b.png" alt="<?php echo getescapedtext ($hcms_lang['feature-b'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['feature-b'][$lang]); ?>" />
          <span class="hcmsButtonLabel"><?php echo getescapedtext($hcms_lang['feature-b'][$lang]); ?></span>
        </div>
        <div class="hcmsButton hcmsHoverColor hcmsInvertColor" onclick="parent.frames['mainFrame'].location='page.php?<?php echo 'plugin='.url_encode($plugin).'&page='.url_encode($page); ?>&content=featureC';">
          <img class="hcmsButtonSizeSquare hcmsFloatLeft" src="../img/button_c.png" alt="<?php echo getescapedtext ($hcms_lang['feature-c'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['feature-c'][$lang]); ?>" />
          <span class="hcmsButtonLabel"><?php echo getescapedtext($hcms_lang['feature-c'][$lang]); ?></span>
        </div>
      </div>
    </div>

  </body>
  
</html>