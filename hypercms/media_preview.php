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
$site = getrequest_esc ("site", "publicationname");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

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
</head>

<body class="hcmsWorkplaceGeneric">

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
  <p class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['media-file-view'][$lang]); ?></p>
  <form name="media">
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="media_name" />
    
    <table class="hcmsTableStandard">
      <tr>
        <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['selected-media-file'][$lang]); ?> </td>
        <td>
          <input type="text" style="width:300px" name="mediafile" />
        </td>
      </tr>
    </table>
  </form>
  <hr/>
</div>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>