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
$description = getrequest_esc ("description");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('site')) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// replace tab space with line break
$description = str_replace ("\t", "<br />", $description);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta name="viewport" content="width=device-width; initial-scale=1.0; user-scalable=1;">
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script language="JavaScript">
<!--
function popupfocus ()
{
  self.focus();
  setTimeout('popupfocus()', 10);
}

popupfocus ();
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric" leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>

<table width="100%" height="100%" border=0 cellpadding="3" cellspacing="0">
  <tr>
    <td class="hcmsWorkplaceControl" align="left" valign="top" width="20px"><img src="<?php echo getthemelocation(); ?>img/info.gif" align="absmiddle" /></td>
    <td align="left" valign="top"><?php echo $description; ?></td>
  </tr>
</table>

</body>
</html>