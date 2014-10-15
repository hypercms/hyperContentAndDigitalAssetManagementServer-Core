<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 

// session parameters
require ("../../../include/session.inc.php");
// management configuration
require ("../../../config.inc.php");
// hyperCMS API
require ("../../../function/hypercms_api.inc.php");
// language file
require_once ("../lang/control.inc.php");

// input parameters
$plugin = getrequest_esc ("plugin");
$page = getrequest_esc ("page", "locationname");

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="../../../javascript/click.js" type="text/javascript"></script>
<script src="../../../javascript/main.js" type="text/javascript"></script>

</head>

  <body class="hcmsWorkplaceControlWallpaper">

    <!-- workplace control title -->
    <div class="hcmsLocationBar">
      <table border=0 cellspacing=0 cellpadding=0>
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
        <img onClick="parent.frames['mainFrame'].location.href='page.php?<?php echo 'plugin='.url_encode($plugin).'&page='.url_encode($page); ?>&content=featureA';" class="hcmsButton hcmsButtonSizeSquare" name="button1" src="../img/button_a.gif" alt="<?php echo $text0[$lang]; ?>" title="<?php echo $text0[$lang]; ?>" />
        <img onClick="parent.frames['mainFrame'].location.href='page.php?<?php echo 'plugin='.url_encode($plugin).'&page='.url_encode($page); ?>&content=featureB';" class="hcmsButton hcmsButtonSizeSquare" name="button2" src="../img/button_b.gif" alt="<?php echo $text1[$lang]; ?>" title="<?php echo $text1[$lang]; ?>" />
        <img onClick="parent.frames['mainFrame'].location.href='page.php?<?php echo 'plugin='.url_encode($plugin).'&page='.url_encode($page); ?>&content=featureC';" class="hcmsButton hcmsButtonSizeSquare" name="button3" src="../img/button_c.gif" alt="<?php echo $text2[$lang]; ?>" title="<?php echo $text2[$lang]; ?>" />
      </div>
    </div>

  </body>
  
</html>