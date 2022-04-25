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
$site = getrequest ("site", "publicationname");
$action = getrequest ("action");
$eventlog_notify = getrequest_esc ("eventlog_notify");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if ((!valid_publicationname ($site) && !checkrootpermission ('site')) && !checkrootpermission ('user')) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// initialize
$show = "";
$add_onload = "";

if ((checkrootpermission ('site') || checkrootpermission ('user')) && checktoken ($token, $user))
{
  // clear event log
  if ($action == "clear")
  {
    if (valid_publicationname ($site)) $result = deletelog ($site.".publication");
    else $result = deletelog ();
  
    $add_onload .= $result['add_onload'];
    $show = $result['message'];
  }
  // notification settings
  elseif ($action == "notification" && $site != "*Null*" && checkglobalpermission ($site, 'user'))
  {
    $settings = array('eventlog_notify'=>$eventlog_notify);
    
    $result = editpublicationsetting ($site, $settings, $user);

    // reload publication management config
    if (!empty ($result['result']) && valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

    $add_onload .= $result['add_onload'];
    $show = $result['message'];
  }
}

// get user names
$user_array = getuserinformation ();

if (!empty ($user_array[$site]) && is_array ($user_array[$site]) && sizeof ($user_array[$site]) > 0)
{
  $username_array = array_keys ($user_array[$site]);
  $usernames = "['".implode ("', '", $username_array)."']";
  $tagit = "availableTags:".$usernames.", beforeTagAdded: function(event, ui) { if ($.inArray(ui.tagLabel, ".$usernames.") == -1) { return false; } }, ";

  $add_onload .= " $('#users').tagit({".$tagit."readOnly:false, singleField:true, allowSpaces:false, singleFieldDelimiter:',', singleFieldNode:$('#users')});";
}

// security token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
<!-- JQuery and JQuery UI -->
<script type="text/javascript" src="javascript/jquery/jquery-3.5.1.min.js"></script>
<script type="text/javascript" src="javascript/jquery-ui/jquery-ui-1.12.1.min.js"></script>
<link rel="stylesheet" href="javascript/jquery-ui/jquery-ui-1.12.1.css" type="text/css" />
<!-- Tagging -->
<script type="text/javascript" src="javascript/tag-it/tag-it.min.js"></script>
<link rel="stylesheet" type="text/css" href="javascript/tag-it/jquery.tagit.css" />
<link rel="stylesheet" type="text/css" href="javascript/tag-it/tagit.ui-zendesk.css" />
<?php
// invert colors
if (!empty ($hcms_themeinvertcolors))
{
  echo "<style>";
  echo invertcolorCSS ($hcms_themeinvertcolors);
  echo "</style>";
}
?>
<style>
ul.tagit
{
  width: 280px;
  height: 28px;
}
</style>
<script type="text/javascript">

function warning_delete()
{
  check = confirm(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-remove-all-events'][$lang]); ?>"));

  if (check == true)
  {  
    document.location='<?php echo "control_log_menu.php?action=clear&site=".url_encode($site)."&token=".$token_new; ?>';
  }
}
</script>
</head>

<body class="hcmsWorkplaceControlWallpaper" onload="<?php echo $add_onload; ?>">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:10px;"); ?>

<?php echo showmessage ($show, 660, 70, $lang, "position:fixed; left:10px; top:10px;"); ?>

<div class="hcmsLocationBar">
  <?php if (!$is_mobile) { ?>
  <table class="hcmsTableNarrow">
    <tr>
      <td class="hcmsHeadline"> <?php if (valid_publicationname ($site)) echo getescapedtext ($site." &gt; "); echo getescapedtext ($hcms_lang['system-events'][$lang]); ?> </td>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>  
  </table>
  <?php } else { ?>
  <span style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php if (valid_publicationname ($site)) echo getescapedtext ($site." &gt; "); echo getescapedtext ($hcms_lang['system-events'][$lang]); ?></span>
  <?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar">
  <div class="hcmsToolbarBlock">
    <img onClick="location='log_export.php?site=<?php echo url_encode ($site); ?>';" class="hcmsButton hcmsButtonSizeSquare" name="media_export" src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_export_page.png" alt="<?php echo getescapedtext ($hcms_lang['export-list-comma-delimited'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['export-list-comma-delimited'][$lang]); ?>" />
    <img onClick="warning_delete();" class="hcmsButton hcmsButtonSizeSquare" name="media_delete" src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_delete.png" alt="<?php echo getescapedtext ($hcms_lang['clear-all-events'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['clear-all-events'][$lang]); ?>" />
     <?php
    // Notification (only per publication)
    if ($site != "*Null*" && checkglobalpermission ($site, 'user'))
    {
      echo "<img ".
             "class=\"hcmsButton hcmsButtonSizeSquare\" ".
             "onClick=\"hcms_showHideLayers('notificationLayer','','show','hcms_messageLayer','','hide');\" ".
             "src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_notify.png\" alt=\"".getescapedtext ($hcms_lang['notify-users'][$lang])."\" title=\"".getescapedtext ($hcms_lang['notify-users'][$lang])."\" />\n";
    }    
    else
    {
      echo "<img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_notify.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>
  </div>
  <div class="hcmsToolbarBlock">
    <img onClick="parent['mainFrame'].location='log_list.php?site=<?php echo url_encode ($site); ?>';" class="hcmsButton hcmsButtonSizeSquare" name="media_view" src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_view_refresh.png" alt="<?php echo getescapedtext ($hcms_lang['refresh'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['refresh'][$lang]); ?>" />
  </div>
  <div class="hcmsToolbarBlock">
    <?php echo showhelpbutton ("adminguide", true, $lang, ""); ?>
  </div>
</div>

<!-- notify users (overwrite z-index for tagit selectbox) -->
<div id="notificationLayer" class="hcmsMessage" style="position:absolute; z-index:99; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:80px; left:15px; top:4px; visibility:hidden;">
<form name="registrationform" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="action" value="notification" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table class="hcmsTableStandard" style="width:100%;">
    <tr>
      <td>
        <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['notify-users'][$lang]." (".$hcms_lang['error'][$lang].", ".$hcms_lang['warning'][$lang].")"); ?></span><br />
        <table class="hcmsTableNarrow">
          <tr>
            <?php if (!$is_mobile) echo "<td style=\"white-space:nowrap;\">".getescapedtext ($hcms_lang['notify-users'][$lang])."&nbsp;</td>"; ?>
            <td style="width:280px; white-space:nowrap;">
              <input id="users" name="eventlog_notify" value="<?php if (!empty ($mgmt_config[$site]['eventlog_notify'])) echo $mgmt_config[$site]['eventlog_notify']; ?>" style="width:<?php if ($is_mobile) echo "200px"; else echo "80%"; ?>" tabindex="1" placeholder="<?php echo getescapedtext ($hcms_lang['notify-users'][$lang]); ?>" />
            </td>
            <td style="white-space:nowrap;">
              &nbsp;<img name="Button2" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="document.forms['registrationform'].submit();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button2','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" tabindex="4" />
            </td>
          </tr>
        </table>
      </td>
      <td style="width:38px; text-align:right; vertical-align:top;">
        <img name="hcms_mediaClose1" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose1','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('notificationLayer','','hide');" />
      </td>        
    </tr>
  </table>
</form>
</div>

</body>
</html>
