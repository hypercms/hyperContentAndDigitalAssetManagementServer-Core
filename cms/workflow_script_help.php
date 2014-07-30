<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("include/session.inc.php");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");
// language file
require_once ("language/workflow_script_help.inc.php");


// input parameters
$site = getrequest ("site", "publicationname");

// ------------------------------ permission section --------------------------------

// check permissions
if ($globalpermission[$site]['workflow'] != 1 || $globalpermission[$site]['workflowscript'] != 1 || $globalpermission[$site]['workflowscriptedit'] != 1 || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php echo showtopbar ($text0[$lang], $lang); ?>

<!-- content -->
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
<?php
echo "<p class=\"hcmsHeadlineTiny\">".$text1[$lang].":</p>
".$text2[$lang].": \$site<br />
".$text3[$lang].": \$location<br /> 
".$text4[$lang].": \$object<br />
<br />
<p class=\"hcmsHeadlineTiny\">".$text5[$lang].":</p>
\$mgmt_config<br />
<p class=\"hcmsHeadlineTiny\">".$text6[$lang].":</p>
".$text7[$lang].": return true;<br /> 
".$text8[$lang].": return false;<br /> 
<p class=\"hcmsHeadlineTiny\">".$text9[$lang]."</p>";
?>
</div>

</body>
</html>
