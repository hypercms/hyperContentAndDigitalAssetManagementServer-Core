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
require_once ("language/media_mapping.inc.php");


// input parameters
$site = getrequest_esc ("site", "publicationname");
$mapping_data = getrequest ("mapping_data");
$save = getrequest ("save");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'template') || !checkglobalpermission ($site, 'tpl') || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $mgmt_config[$site]['default_codepage']; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<?php
// save mapping file
if (valid_publicationname ($site) && $save == "yes" && checktoken ($token, $user))
{
  // creating mapping from definition
  $mapping_data_save = createmapping ($site, $mapping_data);

  if ($mapping_data_save == false) $show = "<p class=hcmsHeadline>".$text4[$lang]."</p>\n".$text5[$lang]."\n";
}
// load mapping file
else
{
  $mapping_data = getmapping ($site);
}

?>
<p class=hcmsHeadline><?php echo $text1[$lang]; ?></p>

<?php
echo showmessage ($show, 600, 70, $lang, "position:fixed; left:5px; top:50px;");
?>

<form id="editor" name="editor" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="save" value="yes" />
  <input type="hidden" name="token" value="<?php echo createtoken ($user); ?>" />
  
  <table border="0" cellspacing="0px" cellpadding="0px" style="border:1px solid #000000; margin:2px;">
    <tr>
      <td align="left">
        <img onclick="document.forms['editor'].submit();" name="save" src="<?php echo getthemelocation(); ?>img/button_save.gif" class="hcmsButton hcmsButtonSizeSquare" title="<?php echo $text3[$lang]; ?>" alt="<?php echo $text3[$lang]; ?>" />
      </td>
    </tr>
    <tr>
      <td>
        <textarea name="mapping_data" wrap="VIRTUAL" style="width:750px;" rows=20><?php echo $mapping_data; ?></textarea>
      </td>
    </tr>
  </table>  
</form>

</div>
</body>
</html>
