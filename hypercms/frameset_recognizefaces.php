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
// version info
require ("version.inc.php");


// input parameters
$servicehash = getrequest ("servicehash");
$token = getrequest ("token");

// define service name
$servicename = "recognizefaces";

// ------------------------------ permission section --------------------------------

// set user and session for the service if there is no user session available or
// a system service user "sys:service-name:16-digit-servicehash" is registered
if ((empty ($user) || substr ($user, 0, 4) == "sys:") && checktimetoken ($token, 3))
{
  $user = registerserviceuser ($servicename, $servicehash);
}

// check session of service
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// write and close session (non-blocking other frames)
if (session_id() != "") session_write_close();
?>
<!DOCTYPE HTML>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=1024, initial-scale=1.0, user-scalable=1" />
<script type="text/javascript">
// initialize (important for cross-domain service)
var hcms_service = true;
</script>
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
<!-- JQuery used for AJAX viewport set request -->
<script src="javascript/jquery/jquery.min.js" type="text/javascript"></script>

<?php if (is_facerecognition ($user)) { ?>
<!-- face recognition -->
<script>
// provide session ID due to issues with Chrome and MS Edge
var hcms_session_id = '<?php if (substr ($user, 0, 4) == "sys:") echo session_id(); ?>';
</script>
<script defer src="javascript/facerecognition/face-api.min.js"></script>
<script defer src="javascript/facerecognition/face-init.js"></script>
<?php } ?>

</head>

<body>

<?php if (is_facerecognition ($user)) { ?>
<!-- recognize faces service -->
<div id="recognizefacesLayer" style="position:absolute; top:0; bottom:0; right:0; left:0; right:0; margin:0; padding:0; visibility:hidden;">
  <iframe id="recognizefacesFrame" src="" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;"></iframe>
</div>
<script>
setTimeout (function() { document.getElementById('recognizefacesFrame').src='service/recognizefaces.php?PHPSESSID=' + hcms_session_id; }, 1500);
</script>
<?php } ?>

</body>
</html>