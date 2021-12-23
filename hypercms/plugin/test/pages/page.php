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
require_once ("../lang/page.inc.php");


// input parameters
$plugin = getrequest_esc ("plugin");
$page = getrequest_esc ("page", "locationname");
$content = getrequest_esc ("content");

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
</head>

<body class="hcmsWorkplaceGeneric" background="<?php echo getthemelocation(); ?>img/backgrd_empty.png">

<table class="hcmsTableStandard" style="width:100%; height:100%;">
  <tr>
    <td style="text-align:center; vertical-align:middle;">
      <img src="<?php echo getthemelocation(); ?>img/logo_server.png" style="width:<?php if ($is_mobile) echo "320px"; else echo "420px"; ?>; margin:40px;" />
      <div>
        <?php
        echo "<strong>".getescapedtext ($hcms_lang['plugin-name'][$lang]).": </strong>".$plugin."<br/>
        <strong>".getescapedtext ($hcms_lang['plugin-page'][$lang]).": </strong>".$page."<br/>
        <strong>".getescapedtext ($hcms_lang['plugin-content'][$lang]).": </strong>".$content."<br/>";
        ?>
      </div>
    </td>
  </tr>
</table>

<?php includefooter(); ?>

</body>
</html>