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
require_once ("language/plugin_showpage.inc.php");


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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
  <head>
    <title>hyperCMS</title>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
  </head>
  <frameset rows="100,*" frameborder="NO" border="0" framespacing="0">
    <frame name="controlFrame" scrolling="NO" src="<?php echo plugin_generatelink ($plugin, $control, false, $add_parameters); ?>" noresize />
    <frame name="mainFrame" src="<?php echo plugin_generatelink ($plugin, $page, false, $add_parameters); ?>" />
  </frameset>
  <noframes>
  </noframes>
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
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
    <link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
    <script src="javascript/main.js" type="text/javascript"></script>
    <script src="javascript/click.js" type="text/javascript"></script>
  </head>

  <body class="hcmsWorkplaceGeneric">
    <?php 
    echo showmessage ($text0[$lang], 500, 40, $lang, "position:absolute; left:15px; top:40px;");
    ?>
  </body>
</html>
<?php
}
?>