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
// servertime class
require ("function/servertime.class.php");
// language file
require_once ("language/top.inc.php");


// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<?php
$servertime = new servertime;
$servertime->InstallClockHead();
?>
<script language="JavaScript">
<!--
function openInfo()
{
  hcms_openBrWindowItem('top_info.php', 'help', 'resizable=mo,scrollbars=no', '640', '400');
}
//-->
</script>
</head>

<body class="hcmsWorkplaceTop" leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr> 
    <td width="5">&nbsp;</td>
    <td width="320" align="left" valign="middle" nowrap="nowrap"><a href="javascript:openInfo();"><img src="<?php if ($mgmt_config['logo_top'] != "") echo $mgmt_config['logo_top']; else echo getthemelocation()."img/logo_top.gif"; ?>" border="0" align="absmiddle" title="hyper Content Management Server" alt="hyper Content Management Server" /></a></td>
    <td nowrap="nowrap">&nbsp;</td>
    <td align="right" valign="middle" nowrap="nowrap"><span class="hcmsHeadline"><?php echo $text0[$lang]; ?>: </span><span class="hcmsHeadlineTiny hcmsTextWhite"><?php echo $_SESSION['hcms_user']; ?></span></td>
    <td width="30" nowrap="nowrap">&nbsp;&nbsp;</td>
    <td width="220" align="left" valign="middle" nowrap="nowrap"><span class="hcmsHeadline"><?php echo $text1[$lang]; ?>:</span>&nbsp;<?php $servertime->InstallClock(); ?></td>
    <td width="16" align="left" valign="middle" nowrap="nowrap" onclick="location.href='top.php';"><img src="<?php echo getthemelocation(); ?>img/clock.gif" style="cursor:pointer; width:16px; height:16px; border:0;" title="<?php echo $text2[$lang]; ?>" alt="<?php echo $text2[$lang]; ?>" /></td>
    <td width="30" nowrap="nowrap">&nbsp;&nbsp;</td>
    <td width="240" align="right" valign="middle" nowrap="nowrap">
    <?php if ($mgmt_config['db_connect_rdbms'] != "") { ?>
      <form name="searchform_general" method="post" action="frameset_objectlist.php" target="workplFrame" style="margin:0; padding:0px; border:0;">
        <input type="hidden" name="action" value="base_search" />
        <input type="hidden" name="search_dir" value="" />
        <input type="hidden" name="maxhits" value="1000" />
        <input type="text" name="search_expression" style="width:200px;" maxlength="60" value="<?php echo $text3[$lang]; ?>" onfocus="if (this.value == '<?php echo $text3[$lang]; ?>') this.value=''" onblur="if(this.value == '') this.value='<?php echo $text3[$lang]; ?>'" />
        <img name="SearchButton" src="<?php echo getthemelocation(); ?>img/button_OK.gif" onClick="if (document.forms['searchform_general'].elements['search_expression'].value=='<?php echo $text3[$lang]; ?>') document.forms['searchform_general'].elements['search_expression'].value=''; document.forms['searchform_general'].submit();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('SearchButton','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" style="border:0; cursor:pointer;" align="absmiddle" title="OK" alt="OK" />
      </form>
    <?php } ?>
    </td>
    <td width="5">&nbsp;</td>
  </tr>
</table>

<?php
$servertime->InstallClockBody();
?>

</body>
</html>