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


// input parameters
$site = getrequest_esc ("site", "publicationname");
$content = getrequest_esc ("content");


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
<script src="../../../javascript/main.js" type="text/javascript"></script>
<script src="../../../javascript/click.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric" background="<?php echo getthemelocation(); ?>img/backgrd_empty.png">

<!-- top bar -->
<?php echo showtopbar ($hcms_lang['translate'][$lang], $lang); ?>

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
  <b>Order professional translations in 3 clicks!</b><br/>
  <b>Get free instant quotes on price and delivery for 500 language pairs.</b><br/>
  Please note that this is a manual translation service and is not part of the automated translations service of the system.<br/><br/>
  <iframe framborder="0" style="border:1px solid #000; width:800px; height:700px;"
     src="https://www.nativy.com/publicinterface/npi?nxpartneruser=info@hypercms.net">
  </iframe>
</div>

</body>
</html>