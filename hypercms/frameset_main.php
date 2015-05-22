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
// servertime class
require ("function/servertime.class.php");

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE HTML>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<meta name="viewport" content="width=1024; initial-scale=1.0; user-scalable=1;">
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
  hcms_openWindow('top_info.php', 'help', 'resizable=no,scrollbars=no', '640', '400');
}

function minNavFrame ()
{
  if (document.getElementById('navFrame'))
  {
    var width = 42;
    
    document.getElementById('navLayer').style.width = width + 'px';
    document.getElementById('workplLayer').style.left = width + 'px';
  }
}

function maxNavFrame ()
{
  if (document.getElementById('navFrame'))
  {
    var width = 260;
    
    document.getElementById('navLayer').style.width = width + 'px';
    document.getElementById('workplLayer').style.left = width + 'px';
  }
}
-->
</script>
</head>

<body style="width:100%; height:100%; margin:0; padding:0;">

<!-- top bar -->
<div class="hcmsWorkplaceTop" style="position:fixed; left:0px; top:0px; width:100%; height:32px;">
  <table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">
    <tr> 
      <td width="5">&nbsp;</td>
      <td width="320" align="left" valign="middle" nowrap="nowrap"><a href="javascript:openInfo();"><img src="<?php if ($mgmt_config['logo_top'] != "") echo $mgmt_config['logo_top']; else echo getthemelocation()."img/logo_top.gif"; ?>" border="0" align="absmiddle" title="hyper Content Management Server" alt="hyper Content Management Server" /></a></td>
      <td nowrap="nowrap">&nbsp;</td>
      <td align="right" valign="middle" nowrap="nowrap"><span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['user'][$lang]); ?>: </span><span class="hcmsHeadlineTiny hcmsTextWhite"><?php echo getsession ('hcms_user'); ?></span></td>
      <td width="30" nowrap="nowrap">&nbsp;&nbsp;</td>
      <td width="220" align="left" valign="middle" nowrap="nowrap"><span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['server-time'][$lang]); ?>:</span>&nbsp;<?php $servertime->InstallClock(); ?></td>
      <td width="30" nowrap="nowrap">&nbsp;&nbsp;</td>
      <?php if (isset ($mgmt_config['chat']) && $mgmt_config['chat'] == true) { ?>
      <td width="40" align="right" valign="middle" nowrap="nowrap">
        <button id="chat" class="hcmsButtonOrange" style="margin-top:1px; heigth:20px;" onClick="hcms_openChat();"><?php echo getescapedtext ($hcms_lang['chat'][$lang]); ?></button>
      </td>
      <?php } ?>
      <?php if (!empty ($mgmt_config['db_connect_rdbms'])) { ?>
      <td width="240" align="right" valign="middle" nowrap="nowrap">
        <form name="searchform_general" method="post" action="frameset_objectlist.php" target="workplFrame" style="margin:0; padding:0px; border:0;">
          <input type="hidden" name="action" value="base_search" />
          <input type="hidden" name="search_dir" value="" />
          <input type="hidden" name="maxhits" value="1000" />
          <input type="text" name="search_expression" style="width:200px;" maxlength="60" value="<?php echo getescapedtext ($hcms_lang['search-expression'][$lang]); ?>" onfocus="if (this.value == '<?php echo getescapedtext ($hcms_lang['search-expression'][$lang]); ?>') this.value=''" onblur="if(this.value == '') this.value='<?php echo getescapedtext ($hcms_lang['search-expression'][$lang]); ?>'" />
          <img name="SearchButton" src="<?php echo getthemelocation(); ?>img/button_OK.gif" onClick="if (document.forms['searchform_general'].elements['search_expression'].value=='<?php echo getescapedtext ($hcms_lang['search-expression'][$lang]); ?>') document.forms['searchform_general'].elements['search_expression'].value=''; document.forms['searchform_general'].submit();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('SearchButton','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" style="border:0; cursor:pointer;" align="absmiddle" title="OK" alt="OK" />
        </form>
      </td>
      <?php } ?>
      <td width="5">&nbsp;</td>
    </tr>
  </table>
</div>
<?php
$servertime->InstallClockBody();
?>

<!-- explorer -->
<div id="navLayer" style="position:fixed; top:32px; bottom:0; left:0; width:260px; margin:0; padding:0;">
  <iframe id="navFrame" name="navFrame" scrolling="yes" src="explorer.php?refresh=1" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
</div>

<!-- workplace -->
<div id="workplLayer" style="position:fixed; top:32px; right:0; bottom:0; left:260px; margin:0; padding:0;">
  <iframe id="workplFrame" name="workplFrame" scrolling="no" src="home.php" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
</div>

<!-- chat sidebar -->
<?php if (isset ($mgmt_config['chat']) && $mgmt_config['chat'] == true) { ?>
<div id="chatLayer" class="hcmsChatBar" style="position:fixed; top:32px; right:0; bottom:0; width:300px; z-index:100; display:none;">
  <iframe id="chatFrame" scrolling="auto" src="chat.php" border="0" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
</div>
<?php } ?>

</body>
</html>