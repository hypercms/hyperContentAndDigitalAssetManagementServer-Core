<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");


// input parameters
$site = getrequest_esc ("site"); // site can be *Null*
$site_name = getrequest_esc ("site_name", "publicationname");
$preview = getrequest_esc ("preview");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('site')) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceControl">

  <div id="Layer_tab" class="hcmsTabContainer" style="position:absolute; z-index:10; visibility:visible; left:0px; top:1px">
    <table border="0" cellspacing="0" cellpadding="0" style="z-index:1;">
      <tr>
        <td style="width:3px;"><img src="<?php echo getthemelocation(); ?>img/backgrd_tabs_spacer.gif" style="width:3px; height:3px; border:0;" /></td>
        <td align="left" valign="top" class="hcmsTab">
          &nbsp;<a href="site_edit_form.php?site=<?php echo $site; ?>&preview=<?php echo $preview; ?>&site_name=<?php echo $site_name; ?>" target="mainFrame2" onClick="hcms_showHideLayers('Layer_tab1','','show','Layer_tab2','','hide');"><?php echo $hcms_lang['configuration'][$lang]; ?></a>
        </td>
        <td style="width:3px;"><img src="<?php echo getthemelocation(); ?>img/backgrd_tabs_spacer.gif" style="width:3px; height:3px; border:0;"></td>
        <td align="left" valign="top" class="hcmsTab">
          &nbsp;<a href="site_edit_inheritance.php?site=<?php echo $site; ?>&preview=<?php echo $preview; ?>&site_name=<?php echo $site_name; ?>" target="mainFrame2" onClick="hcms_showHideLayers('Layer_tab1','','hide','Layer_tab2','','show');"><?php echo $hcms_lang['inheritance'][$lang]; ?></a>
        </td>
      </tr>
    </table>
  </div>

  <div id="Layer_tab1" class="hcmsWorkplaceGeneric" style="position:absolute; width:118px; height:2px; z-index:20; visibility:visible; left:4px; top:23px; visibility:visible;"> </div>
  <div id="Layer_tab2" class="hcmsWorkplaceGeneric" style="position:absolute; width:118px; height:2px; z-index:20; visibility:visible; left:127px; top:23px; visibility:hidden;"> </div>

</body>
</html>