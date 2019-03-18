<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");


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
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script>
function popupfocus ()
{
  self.focus();
  setTimeout('popupfocus()', 10);
}

popupfocus ();
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<?php
echo showtopbar ("<img src=\"".getthemelocation()."img/info.png\" class=\"hcmsButtonSizeSquare\" />&nbsp;".getescapedtext ($hcms_lang['system-events'][$lang]), $lang);
?>
<div class="hcmsWorkplaceFrame">
  <?php echo $description; ?>
</div>

</body>
</html>