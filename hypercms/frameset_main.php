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
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=1024, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<!-- Jquery and Jquery UI Autocomplete -->
<script src="javascript/jquery/jquery-3.3.1.min.js" type="text/javascript"></script>
<script src="javascript/jquery-ui/jquery-ui-1.12.1.min.js" type="text/javascript"></script>
<link rel="stylesheet" href="javascript/jquery-ui/jquery-ui-1.12.1.css">
<?php
// set time zone for user
if (!empty ($_SESSION['hcms_timezone']) && $_SESSION['hcms_timezone'] != "standard")
{
  date_default_timezone_set ($_SESSION['hcms_timezone']);
}
  
$servertime = new servertime;
$servertime->InstallClockHead();
?>
<script type="text/javascript">
// search window state
var search = false;

function setviewport ()
{
  var width = hcms_getViewportWidth();

  if (width > 0)
  {
    // AJAX request to set viewport width
    $.post("<?php echo $mgmt_config['url_path_cms']; ?>service/setviewport.php", {viewportwidth: width});
    return true;
  }
  else return false;
}

function openInfo()
{
  hcms_openWindow('top_info.php', 'help', 'resizable=no,scrollbars=no', '640', '400');
}

function showHome ()
{
  minNavFrame(0);
  document.getElementById('workplFrame').src='home.php';
}

function showSearch ()
{
  search = true;
  parent.frames['navFrame'].showSearch();
  maxNavFrame();
}

function switchNav ()
{
  if (search == true)
  {
    search = false;
    parent.frames['navFrame'].showNav(); 
  }
  else if (document.getElementById('navLayer'))
  {
    if (document.getElementById('navLayer').style.width == '260px')
    {
      minNavFrame (0);
    }
    else
    {
      maxNavFrame ();
    }
  }
}

function minNavFrame (width)
{
  if (document.getElementById('navFrame'))
  {
    width = typeof width !== 'undefined' ? width : 32;
    
    document.getElementById('navLayer').style.width = width + 'px';
    document.getElementById('workplLayer').style.left = width + 'px';
  }
}

function maxNavFrame (width)
{
  if (document.getElementById('navFrame'))
  {
    width = typeof width !== 'undefined' ? width : 260;
    
    document.getElementById('navLayer').style.width = width + 'px';
    document.getElementById('workplLayer').style.left = width + 'px';
  }
}

function submitForm ()
{
  if (document.forms['searchform_general'])
  {
    //if (parent.frames['workplFrame'].document.getElementById('hcmsLoadScreen')) parent.frames['workplFrame'].document.getElementById('hcmsLoadScreen').style.display='inline';
    
    var form = document.forms['searchform_general'];  
    if (form.elements['search_expression'].value.trim() != '') form.submit();
  }
}

$(document).ready(function()
{
  <?php
  $keywords = getsearchhistory ($user);
  ?>
  var available_expressions = [<?php if (is_array ($keywords)) echo implode (",\n", $keywords); ?>];

  $("#search_expression").autocomplete({
    source: available_expressions
  });
  
  setviewport();
  
  // set window width and height for contextmenu
  localStorage.setItem ('windowwidth', <?php echo windowwidth ("object"); ?>);
  localStorage.setItem ('windowheight', <?php echo windowheight ("object"); ?>);
  
  window.onresize = function()
  {
    setviewport();
  };
});

<?php
// assetbrowser
if (!empty ($hcms_assetbrowser) && is_file ($mgmt_config['abs_path_cms']."connector/assetbrowser/config.inc.php"))
{
  require_once ($mgmt_config['abs_path_cms']."connector/assetbrowser/config.inc.php");
}
?>
</script>
</head>

<body>

<?php if (empty ($hcms_assetbrowser))
{
?>
<!-- top bar -->
<div class="hcmsWorkplaceTop" style="position:fixed; left:0px; top:0px; width:100%; height:32px;">
  <table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">
    <tr> 
      <td align="left" valign="middle" style="white-space:nowrap;">
        <img src="<?php if ($mgmt_config['logo_top'] != "") echo $mgmt_config['logo_top']; else echo getthemelocation()."img/logo_top.png"; ?>" class="hcmsButtonTiny hcmsLogoTop" onclick="openInfo();" title="hyper Content & Digital Asset Management Server" alt="hyper Content & Digital Asset Management Server" />
        <?php if (empty ($hcms_linking)) { ?>
        <img src="<?php echo getthemelocation(); ?>img/button_explorer.png" class="hcmsButtonTiny hcmsButtonSizeSquare" onclick="switchNav();" alt="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" />
        <img src="<?php echo getthemelocation(); ?>img/button_search.png" class="hcmsButtonTiny hcmsButtonSizeSquare" onclick="showSearch();" alt="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" />
        <img src="<?php echo getthemelocation(); ?>img/home.png" class="hcmsButtonTiny hcmsButtonSizeSquare" onclick="showHome();" alt="<?php echo getescapedtext ($hcms_lang['home'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['home'][$lang]); ?>" />
        <?php } ?>
        <img src="<?php echo getthemelocation(); ?>img/button_logout.png" class="hcmsButtonTiny hcmsButtonSizeSquare" onclick="top.location='userlogout.php';" alt="<?php echo getescapedtext ($hcms_lang['logout'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['logout'][$lang]); ?>" />
      </td>
      <td align="right" valign="middle" style="white-space:nowrap;">
        <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['user'][$lang]); ?></span>&nbsp;
        <span class="hcmsHeadlineTiny hcmsTextWhite"><?php echo getsession ('hcms_user'); ?></span>
      </td>
      <td width="20" style="white-space:nowrap;">&nbsp;&nbsp;</td>
      <td width="180" align="left" valign="middle" style="white-space:nowrap;">
        <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['datetime'][$lang]); ?></span>&nbsp;&nbsp;
        <?php $servertime->InstallClock(); ?>
      </td>
      <td width="20" style="white-space:nowrap;">&nbsp;&nbsp;</td>
      <td width="260" style="white-space:nowrap;">
      <?php if (!empty ($mgmt_config['db_connect_rdbms']) && empty ($hcms_linking['object'])) { ?>
        <form name="searchform_general" method="post" action="frameset_objectlist.php" target="workplFrame" style="margin:0; padding:0; border:0;">
          <input type="hidden" name="action" value="base_search" />
          <input type="hidden" name="search_dir" value="" />
          <input type="text" name="search_expression" id="search_expression" style="position:fixed; top:3px; right:40px; width:200px; height:20px; padding:2px;" maxlength="200" placeholder="<?php echo getescapedtext ($hcms_lang['search-expression'][$lang]); ?>" value="" />
          <img src="<?php echo getthemelocation(); ?>img/button_search.png" style="cursor:pointer; position:fixed; top:5px; right:42px; width:22px; height:22px;" onClick="submitForm();" title="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" />
        </form>
      <?php } ?>
      <?php if (isset ($mgmt_config['chat']) && $mgmt_config['chat'] == true) { ?>
        <img src="<?php echo getthemelocation(); ?>img/button_chat.png" class="hcmsButtonTiny  hcmsButtonSizeSquare" style="position:fixed; top:0px; right:3px;" onClick="hcms_openChat();" alt="<?php echo getescapedtext ($hcms_lang['chat'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['chat'][$lang]); ?>" />
      <?php } ?>
      </td>
    </tr>
  </table>
</div>
<?php
  $servertime->InstallClockBody();
}
?>

<?php if (is_array ($hcms_linking) && sizeof ($hcms_linking) > 0) { ?>
<!-- workplace -->
<div id="workplLayer" style="position:fixed; top:32px; bottom:0; left:0; width:100%; margin:0; padding:0;">
  <iframe id="workplFrame" name="workplFrame" scrolling="no" src="frameset_objectlist.php" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
</div>
<?php } elseif (!empty ($hcms_assetbrowser)) {
  // location set by assetbrowser
  if (!empty ($hcms_assetbrowser_location)) { ?>
<!-- explorer -->
<div id="navLayer" style="position:fixed; top:0; bottom:0; left:0; width:260px; margin:0; padding:0;">
  <iframe id="navFrame" name="navFrame" scrolling="yes" src="explorer.php" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
</div>

<!-- workplace -->
<div id="workplLayer" style="position:fixed; top:0; right:0; bottom:0; left:260px; margin:0; padding:0;">
  <iframe id="workplFrame" name="workplFrame" scrolling="no" src="frameset_objectlist.php?location=<?php echo url_encode($hcms_assetbrowser_location); ?>" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
</div>
<?php } 
  // no location set by assetbrowser
  else { ?>
<!-- explorer -->
<div id="navLayer" style="position:fixed; top:0; bottom:0; left:0; width:260px; margin:0; padding:0;">
  <iframe id="navFrame" name="navFrame" scrolling="yes" src="explorer.php" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
</div>

<!-- workplace -->
<div id="workplLayer" style="position:fixed; top:0; right:0; bottom:0; left:260px; margin:0; padding:0;">
  <iframe id="workplFrame" name="workplFrame" scrolling="no" src="empty.php" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
</div>
<?php }
} else { ?>
<!-- explorer -->
<div id="navLayer" style="position:fixed; top:32px; bottom:0; left:0; width:260px; margin:0; padding:0;">
  <iframe id="navFrame" name="navFrame" scrolling="yes" src="explorer.php?refresh=1" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
</div>

<!-- workplace -->
<div id="workplLayer" style="position:fixed; top:32px; right:0; bottom:0; left:260px; margin:0; padding:0;">
  <iframe id="workplFrame" name="workplFrame" scrolling="no" src="home.php" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
</div>
<?php } ?>

<!-- chat sidebar -->
<?php if (isset ($mgmt_config['chat']) && $mgmt_config['chat'] == true) { ?>
<div id="chatLayer" class="hcmsChatBar" style="position:fixed; top:32px; right:0; bottom:0; width:300px; z-index:100; display:none;">
  <iframe id="chatFrame" scrolling="auto" src="chat.php" border="0" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
</div>
<?php } ?>

</body>
</html>