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
$site = getrequest ("site", "publicationname");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'workflow') || !checkglobalpermission ($site, 'workflowscript') || !checkglobalpermission ($site, 'workflowscriptedit') || !valid_publicationname ($site)) killsession ($user);

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
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php echo showtopbar ($hcms_lang['workflow-scripting'][$lang], $lang); ?>

<!-- content -->
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
<?php
echo "<p class=\"hcmsHeadlineTiny\">".getescapedtext ($hcms_lang['you-can-use-the-following-given-input-variables'][$lang]).":</p>
".getescapedtext ($hcms_lang['publication'][$lang]).": \$site<br />
".getescapedtext ($hcms_lang['location-of-the-object'][$lang]).": \$location<br /> 
".getescapedtext ($hcms_lang['object'][$lang]).": \$object<br />
<br />
<p class=\"hcmsHeadlineTiny\">".getescapedtext ($hcms_lang['also-the-following-system-constants-can-be-accessed'][$lang]).":</p>
\$mgmt_config<br />
<p class=\"hcmsHeadlineTiny\">".getescapedtext ($hcms_lang['the-result-of-the-workflow-script-must-be-in-the-form'][$lang]).":</p>
".getescapedtext ($hcms_lang['if-successful-accept-the-object-and-send-to-next-member'][$lang]).": return true;<br /> 
".getescapedtext ($hcms_lang['if-not-successful-reject-the-object-and-send-it-back'][$lang]).": return false;<br /> 
<p class=\"hcmsHeadlineTiny\">".getescapedtext ($hcms_lang['for-detailed-information-please-see-the-hypercms-programmers-guide-and-hypercms-workflow-guide'][$lang])."</p>";
?>
</div>

</body>
</html>
