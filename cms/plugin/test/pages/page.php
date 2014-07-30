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
require_once ("../lang/page.inc.php");

// input parameters
$plugin = getrequest_esc ("plugin");
$page = getrequest_esc ("page", "locationname");
$content = getrequest_esc ("content");

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
</script>
</head>

<body class="hcmsWorkplaceGeneric" background="<?php echo getthemelocation(); ?>img/backgrd_empty.png">
<table width="100%" height="100%">
  <tr>
    <td align="middle" valign="middle">
      <img src="<?php echo getthemelocation(); ?>img/logo_welcome.gif" />
      <div><?php echo "<strong>".$text0[$lang].": </strong>".$plugin."<br/>\n<strong>".$text1[$lang].": </strong>".$page."<br/>\n<strong>".$text2[$lang].": </strong>".$content."<br/>\n"; ?></div>
    </td>
  </tr>
</table>
</body>
</html>