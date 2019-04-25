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

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/click.js" type="text/javascript">
</script>
</head>

<body class="hcmsWorkplaceGeneric" leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>
  <table style="width:100%; height:100%;">
    <tr>
      <td style="text-align:center; vertical-align:middle;">
        <img src="<?php echo getthemelocation(); ?>img/logo_server.png" style="width:<?php if ($is_mobile) echo "320px"; else echo "420px"; ?>;" alt="hypercms.com" />
      </td>
    </tr>
  </table>
</body>
</html>