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


// input parameters
$action = getrequest_esc ("action");
$site = getrequest_esc ("site", "publicationname");
$mediafile = getrequest ("mediafile", "objectname");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'tplmedia') || !checkglobalpermission ($site, 'tplmediadelete') || !valid_publicationname ($site)) killsession ($user);

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
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<script type="text/javascript">
function warning_media_delete ()
{
  var form = document.forms['media'];
  
  if (form.elements['mediafile'].value != "")
  {
    check = confirm (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['warning'][$lang]); ?>\n <?php echo getescapedtext ($hcms_lang['the-selected-file-will-be-removed'][$lang]); ?>"));
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
</script>
</head>
<?php
if (checkglobalpermission ($site, 'tplmedia') && checkglobalpermission ($site, 'tplmediadelete') && $action == "delete")
{
  $result = deletefrommediacat ($site, $mediafile);
  $add_onload = $result['add_onload'];
  $show = $result['message'];
}
?>
<body class="hcmsWorkplaceGeneric" onLoad="<?php echo $add_onload; ?>hcms_preloadImages('<?php echo getthemelocation(); ?>img/button_ok_over.png');">
  <p class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['delete-media-file'][$lang]); ?></p>
  <form name="media" action="">
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="mediafile" value="" />
    <input type="hidden" name="action" value="delete" />
    
    <table class="hcmsTableStandard">
      <tr>
        <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['selected-media-file'][$lang]); ?> </td>
        <td>
          <input type="text" style="width:300px;" name="media_name" />
          <img name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="warning_media_delete();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" />
        </td>
      </tr>
    </table><br />
  </form>
  <?php echo $show; ?>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>
