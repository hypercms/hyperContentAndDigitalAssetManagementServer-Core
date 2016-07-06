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
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");


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
<meta charset="<?php echo $mgmt_config[$site]['default_codepage']; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- saving --> 
<div id="savelayer" class="hcmsWorkplaceGeneric" style="position:fixed; width:100%; height:100%; margin:0; padding:0; left:0px; top:0px; visibility:hidden; z-index:10;">
	<span style="position:absolute; top:50%; height:150px; margin-top:-75px; width:200px; left:50%; margin-left:-100px;">
		<img src="<?php echo getthemelocation(); ?>img/loading.gif" />
	</span>
</div>

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<?php
// save mapping file
if (valid_publicationname ($site) && $save == "yes" && checktoken ($token, $user))
{
  // creating mapping from definition
  $mapping_data_save = createmapping ($site, $mapping_data);

  if ($mapping_data_save == false) $show = "<p class=hcmsHeadline>".getescapedtext ($hcms_lang['error-while-saving'][$lang])."</p>\n".getescapedtext ($hcms_lang['you-do-not-have-write-permissions'][$lang])."\n";
}
// load mapping file
else
{
  $mapping_data = getmapping ($site);
}

// reindex all media files
if (valid_publicationname ($site) && $save == "reindex" && checktoken ($token, $user))
{
  // creating mapping from definition
  $result = reindexcontent ($site);
  
  if ($result) $show = $hcms_lang['the-data-was-saved-successfully'][$lang];
  else $show = $hcms_lang['the-data-could-not-be-saved'][$lang];
}
?>
<p class=hcmsHeadline><?php echo getescapedtext ($hcms_lang['meta-data-mapping'][$lang]); ?></p>

<?php
echo showmessage ($show, 600, 70, $lang, "position:fixed; left:5px; top:50px;");
?>

<form id="editor" name="editor" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="save" value="yes" />
  <input type="hidden" name="token" value="<?php echo createtoken ($user); ?>" />
  
  <table border="0" cellspacing="0px" cellpadding="0px" style="border:1px solid #000000; margin:2px;">
    <tr>
      <td align="left">
        <img onclick="document.forms['editor'].submit();" name="save" src="<?php echo getthemelocation(); ?>img/button_save.gif" class="hcmsButton hcmsButtonSizeSquare" title="<?php echo getescapedtext ($hcms_lang['save'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['save'][$lang]); ?>" />
      </td>
    </tr>
    <tr>
      <td>
        <textarea name="mapping_data" wrap="VIRTUAL" style="width:750px;" rows=20><?php echo $mapping_data; ?></textarea>
      </td>
    </tr>
  </table>  
</form>

<div style="margin-top:10px; padding:2px;">
  <form id="reindex" name="reindex" action="" method="post">
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="save" value="reindex" />
    <input type="hidden" name="token" value="<?php echo createtoken ($user); ?>" />
    <?php echo getescapedtext ($hcms_lang['reindex-content-of-all-media-files'][$lang]); ?>: <img name="Button" onClick="hcms_showHideLayers('savelayer','','show'); document.forms['reindex'].submit();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_OK.gif" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" title="OK" alt="OK" /> 
  </form>
</div>

</div>
</body>
</html>
