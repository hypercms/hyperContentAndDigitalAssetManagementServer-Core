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
require_once ("language/media_delete.inc.php");


// input parameters
$action = getrequest_esc ("action");
$site = getrequest_esc ("site", "publicationname");
$mediafile = getrequest ("mediafile", "objectname");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if ($globalpermission[$site]['tplmedia'] != 1 || $globalpermission[$site]['tplmediadelete'] != 1 || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
function warning_media_delete ()
{
  var form = document.forms['media'];
  
  if (form.elements['mediafile'].value != "")
  {
    check = confirm (hcms_entity_decode("<?php echo $text0[$lang]; ?>:\r<?php echo $text1[$lang]; ?>"));
    if (check == true) form.submit();
    return check;
  }
  else return false;
}

function goToURL ()
{
  var i, args=goToURL.arguments;
  document.returnValue = false;
  for (i=0; i<(args.length-1); i+=2) eval(args[i]+".location='"+args[i+1]+"'");
}
//-->
</script>
</head>
<?php
if ($globalpermission[$site]['tplmedia'] == 1 && $globalpermission[$site]['tplmediadelete'] == 1 && $action == "delete")
{
  $result = deletefrommediacat ($site, $mediafile);
  $add_onload = $result['add_onload'];
  $show = $result['message'];
}
?>
<body class="hcmsWorkplaceGeneric" onLoad="<?php echo $add_onload; ?>hcms_preloadImages('<?php echo getthemelocation(); ?>img/button_OK_over.gif');">
<p class="hcmsHeadline"><?php echo $text4[$lang]; ?></p>
  <table border="0">
  <form name="media" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="mediafile" value="" />
    <input type="hidden" name="action" value="delete" />
      <tr>
        <td nowrap="nowrap"><?php echo $text5[$lang]; ?>: </td>
        <td>
          <input type="text" style="width:300px;" name="media_name" />
          <img name="Button" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="warning_media_delete();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" title="OK" alt="OK" />
        </td>
      </tr>
  </form>    
  </table><br />
  <?php echo $show; ?>
</body>
</html>