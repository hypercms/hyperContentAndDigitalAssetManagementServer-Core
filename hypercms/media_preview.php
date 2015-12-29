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
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
</script>
</head>

<body class="hcmsWorkplaceGeneric">
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

  <p class=hcmsHeadline><?php echo getescapedtext ($hcms_lang['media-file-view'][$lang]); ?></p>
  
  <form name="media">
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="media_name" />
    
    <table border="0">
      <tr>
        <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['selected-media-file'][$lang]); ?>: </td>
        <td>
          <input type="text" style="width:300px" name="mediafile" />
        </td>
      </tr>
    </table>
  </form>

</div
</body>
</html>