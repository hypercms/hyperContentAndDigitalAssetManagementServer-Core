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
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");


// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>hyperCMS</title>
</head>
<body style="font-family:Verdana;">
<h1>hyperCMS API Function Reference</h1>
<h2>Main API Functions</h2>
<?php
$file = "../function/hypercms_main.inc.php";
echo showAPIdocs ($file);
?>

<h2>Get API Functions</h2>
<?php
$file = "../function/hypercms_get.inc.php";
echo showAPIdocs ($file);
?>

<h2>Set API Functions</h2>
<?php
$file = "../function/hypercms_set.inc.php";
echo showAPIdocs ($file);
?>

<h2>Connect API Functions</h2>
<?php
$file = "../function/hypercms_connect.inc.php";
echo showAPIdocs ($file);
?>

<h2>Security API Functions</h2>
<?php
$file = "../function/hypercms_sec.inc.php";
echo showAPIdocs ($file);
?>

<h2>Media API Functions</h2>
<?php
$file = "../function/hypercms_media.inc.php";
echo showAPIdocs ($file);
?>

<h2>Metadata API Functions</h2>
<?php
$file = "../function/hypercms_meta.inc.php";
echo showAPIdocs ($file);
?>

<h2>Link API Functions</h2>
<?php
$file = "../function/hypercms_link.inc.php";
echo showAPIdocs ($file);
?>

<h2>Plugin API Functions</h2>
<?php
$file = "../function/hypercms_plugin.inc.php";
echo showAPIdocs ($file);
?>

<h2>User Interface API Functions</h2>
<?php
$file = "../function/hypercms_ui.inc.php";
echo showAPIdocs ($file);
?>

<h2>Template Engine API Functions</h2>
<?php
$file = "../function/hypercms_tplengine.inc.php";
echo showAPIdocs ($file);
?>

<h2>XML API Functions</h2>
<?php
$file = "../function/hypercms_xml.inc.php";
echo showAPIdocs ($file);
?>

<?php
if (is_file ($mgmt_config['abs_path_cms']."report/hypercms_report.inc.php"))
{
?>
<h2>Report API Functions</h2>
<?php
  $file = $mgmt_config['abs_path_cms']."report/hypercms_report.inc.php";
  echo showAPIdocs ($file);
}
?>

<?php
if (is_file ($mgmt_config['abs_path_cms']."project/hypercms_project.inc.php"))
{
?>
<h2>Project API Functions</h2>
<?php
  $file = $mgmt_config['abs_path_cms']."project/hypercms_project.inc.php";
  echo showAPIdocs ($file);
}
?>

<?php
if (is_file ($mgmt_config['abs_path_cms']."task/hypercms_task.inc.php"))
{
?>
<h2>Task API Functions</h2>
<?php
  $file = $mgmt_config['abs_path_cms']."task/hypercms_task.inc.php";
  echo showAPIdocs ($file);
}
?>

<?php
if (is_file ($mgmt_config['abs_path_cms']."workflow/hypercms_workflow.inc.php"))
{
?>
<h2>Workflow API Functions</h2>
<?php
  $file = $mgmt_config['abs_path_cms']."workflow/hypercms_workflow.inc.php";
  echo showAPIdocs ($file);
}
?>
</body>
</html>