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
    <div class="hcmsToolbar">
      <div class="hcmsToolbarBlock">
        <div class="hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare">
          <img onClick="parent.frames['mainFrame'].location='page.php?<?php echo 'plugin='.url_encode($plugin).'&page='.url_encode($page); ?>&content=featureA';" class="hcmsButtonSizeSquare" name="button1" src="../img/button_a.png" alt="<?php echo getescapedtext ($hcms_lang['feature-a'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['feature-a'][$lang]); ?>" />
        </div>
        <div class="hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare">
          <img onClick="parent.frames['mainFrame'].location='page.php?<?php echo 'plugin='.url_encode($plugin).'&page='.url_encode($page); ?>&content=featureB';" class="hcmsButtonSizeSquare" name="button2" src="../img/button_b.png" alt="<?php echo getescapedtext ($hcms_lang['feature-b'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['feature-b'][$lang]); ?>" />
        </div>
        <div class="hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare">
          <img onClick="parent.frames['mainFrame'].location='page.php?<?php echo 'plugin='.url_encode($plugin).'&page='.url_encode($page); ?>&content=featureC';" class="hcmsButtonSizeSquare" name="button3" src="../img/button_c.png" alt="<?php echo getescapedtext ($hcms_lang['feature-c'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['feature-c'][$lang]); ?>" />
        </div>
      </div>
    </div>

  </body>
  
</html>