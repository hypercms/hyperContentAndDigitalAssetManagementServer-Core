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


// input parameters
$plugin = getrequest ("plugin");
$page = getrequest ("page", "locationname");
$control = getrequest ("control", "locationname", false);
$site = getrequest ("site", "publicationname");

// load plugin config file
if (file_exists ($mgmt_config['abs_path_data'].'config/plugin.conf.php'))
{
  require ($mgmt_config['abs_path_data'].'config/plugin.conf.php');
}
else $mgmt_plugin = array();

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// pass all GET parameters to the plugins pages
if (is_array ($_GET)) 
{
  $add_parameters = "";
  
  foreach ($_GET as $key => $value)
  {
    if ($key != "" && $key != "plugin" && $key != "page" && $key != "control")
    {
      if ($add_parameters != "") $add_parameters = $add_parameters."&";
      $add_parameters .= $key."=".url_encode ($value);
    }
  }
}
else $add_parameters = "";

// show plugin pages
if (is_array ($mgmt_plugin) && array_key_exists ($plugin, $mgmt_plugin) && is_array ($mgmt_plugin[$plugin]) && array_key_exists ('folder', $mgmt_plugin[$plugin]) && is_file ($mgmt_plugin[$plugin]['folder'].$page))
{
  // show frameset if plugin defines a workplace control
  if ($control && is_file ($mgmt_plugin[$plugin]['folder'].$control) )
  {
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=800; initial-scale=1.0; user-scalable=1;" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" language="JavaScript" type="text/javascript"></script>
<script language="JavaScript">
<!--
function adjust_height ()
{
  var height = hcms_getDocHeight();  
  
  setheight = height - 100;
  if (document.getElementById('mainFrame')) document.getElementById('mainFrame').style.height = setheight + "px";
}
-->
</script>
</head>

<body style="width:100%; height:100%; margin:0; padding:0;" onload="adjust_height();" onresize="adjust_height();">
  <iframe id="controlFrame" name="controlFrame" scrolling="no" src="<?php echo plugin_generatelink ($plugin, $control, false, $add_parameters); ?>" style="position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0;"></iframe>
  <div style="position:fixed; top:100px; right:0; bottom:0; left:0; margin:0; padding:0;">
    <iframe id="mainFrame" name="mainFrame" scrolling="auto" src="<?php echo plugin_generatelink ($plugin, $page, false, $add_parameters); ?>" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
  </div>
</body>
</html>
<?php
  }
  // show page only if no workplace control is given
  else
  {
    // require ($mgmt_plugin[$plugin]['folder'].$page);
    header ("Location: ".$mgmt_config['url_path_cms']."plugin/".$plugin."/".$page."?plugin=".url_encode($plugin)."&page=".url_encode($page).($add_parameters ? "&".$add_parameters : ""));
  }
}
// show error page
else
{
?>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">
  <?php 
  echo showmessage ($hcms_lang['couldnt-find-the-requested-page-in-this-plugin'][$lang], 500, 40, $lang, "position:fixed; left:15px; top:40px;");
  ?>
</body>
</html>
<?php
}
?>