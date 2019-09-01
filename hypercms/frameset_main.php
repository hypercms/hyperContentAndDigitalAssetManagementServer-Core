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
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?ts=<?php echo time(); ?>" />
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>

<!-- JQuery used for AJAX viewport set request -->
<script src="javascript/jquery/jquery-3.3.1.min.js" type="text/javascript"></script>

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
      minNavFrame ();
    }
    else
    {
      maxNavFrame ();
    }
  }
}

function minNavFrame (width)
{
  var offset = 36;
  
  if (document.getElementById('navFrame'))
  {
    width = typeof width !== 'undefined' ? width : 0;
    
    document.getElementById('navLayer').style.width = width + 'px';
    document.getElementById('workplLayer').style.left = offset + 'px';
  }
}

function maxNavFrame (width)
{
  var offset = 36;
  
  if (document.getElementById('navFrame'))
  {
    width = typeof width !== 'undefined' ? width : 260;
    
    document.getElementById('navLayer').style.width = width + 'px';
    document.getElementById('workplLayer').style.left = (width + offset) + 'px';
  }
}

$(document).ready(function()
{
  setviewport();
  
  // set window width and height for contextmenu
  localStorage.setItem ('windowwidth', <?php echo windowwidth ("object"); ?>);
  localStorage.setItem ('windowheight', <?php echo windowheight ("object"); ?>);

  // set user popup or new window for contextmenu
  localStorage.setItem ('user_newwindow', <?php if (!empty ($mgmt_config['user_newwindow'])) echo "true"; else echo "false"; ?>);
  
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
<!-- top/left bar -->
<div class="hcmsWorkplaceTop" style="position:fixed; left:0; top:0; bottom:0; width:36px;">
  <img src="<?php if ($mgmt_config['logo_top'] != "") echo $mgmt_config['logo_top']; else echo getthemelocation()."img/logo_top.png"; ?>" class="hcmsButtonTiny hcmsLogoTop" onclick="openInfo();" title="hyper Content & Digital Asset Management Server" alt="hyper Content & Digital Asset Management Server" />
  <img src="<?php echo getthemelocation(); ?>img/home.png" class="hcmsButtonTiny hcmsButtonSizeSquare" style="padding:2px;" onclick="showHome();" alt="<?php echo getescapedtext ($hcms_lang['home'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['home'][$lang]); ?>" />

  <?php if (linking_valid() == false) { ?>
  <img src="<?php echo getthemelocation(); ?>img/button_explorer.png" class="hcmsButtonTiny hcmsButtonSizeSquare" style="padding:2px;" onclick="switchNav();" alt="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" />
  <img src="<?php echo getthemelocation(); ?>img/button_search.png" class="hcmsButtonTiny hcmsButtonSizeSquare" style="padding:2px;" onclick="showSearch();" alt="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" />
  <?php } ?>
  
  <?php if (linking_valid() == true)  { ?>
  <a href="frameset_objectlist.php?action=linking" target="workplFrame"><img src="<?php echo getthemelocation(); ?>img/button_view_gallery_medium.png" class="hcmsButtonTiny hcmsButtonSizeSquare" style="padding:2px;" alt="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" /></a>
  <?php if (checkrootpermission ('desktoptaskmgmt')) { ?>
  <a href="task/task_list.php" target="workplFrame"><img src="<?php echo getthemelocation(); ?>img/task.png" class="hcmsButtonTiny hcmsButtonSizeSquare" style="padding:2px;" alt="<?php echo getescapedtext ($hcms_lang['task-management'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['task-management'][$lang]); ?>" /></a>
  <?php } ?>
  <?php } ?>

  <?php if (empty ($hcms_assetbrowser) && !empty ($mgmt_config['chat'])) { ?>
  <img src="<?php echo getthemelocation(); ?>img/button_chat.png" class="hcmsButtonTiny  hcmsButtonSizeSquare" style="padding:2px;" onClick="hcms_openChat();" alt="<?php echo getescapedtext ($hcms_lang['chat'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['chat'][$lang]); ?>" />
  <?php } ?>
  
  <?php if (empty ($hcms_portal)) { ?>
  <img src="<?php echo getthemelocation(); ?>img/button_logout.png" class="hcmsButtonTiny hcmsButtonSizeSquare" style="padding:2px;" onclick="top.location='userlogout.php';" alt="<?php echo getescapedtext ($hcms_lang['logout'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['logout'][$lang]); ?>" />
  <?php } ?>
</div>

<!-- user info -->
<?php if (empty ($hcms_portal)) { ?>
<img src="<?php echo getthemelocation(); ?>img/button_info.png" class="hcmsButtonTiny hcmsButtonSizeSquare" style="position:absolute; left:0; bottom:0; padding:2px; margin:32px 0px;" onclick="hcms_showInfo ('userInfoLayer', 4);" alt="<?php echo getescapedtext ($hcms_lang['information'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['information'][$lang]); ?>" />
  
<div id="userInfoLayer" class="hcmsMessage" style="position:absolute; bottom:10px; left:32px; display:none; z-index:999; padding:4px; width:200px; min-height:80px; overflow:auto; overflow-x:hidden; overflow-y:auto; white-space:nowrap;">
  <img src="<?php echo getthemelocation()."img/user.png"; ?>" class="hcmsIconList" /> <span class="hcmsHeadline" style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['user'][$lang]); ?></span><br/>
  <span class="hcmsHeadlineTiny hcmsTextWhite">&nbsp;<?php echo getsession ('hcms_user'); ?></span><br/><br/>

  <img src="<?php echo getthemelocation()."img/button_time.png"; ?>" class="hcmsIconList" /> <span class="hcmsHeadline" style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['datetime'][$lang]); ?></span><br/>
  <span class="hcmsHeadlineTiny hcmsTextWhite">&nbsp;<?php $servertime->InstallClock(); ?></span>
</div>
<?php $servertime->InstallClockBody(); ?>
<?php } ?>

<?php
}
?>

<?php if (linking_valid() == true) { ?>
<!-- workplace -->
<div id="workplLayer" style="position:fixed; top:0; bottom:0; left:36px; right:0; margin:0; padding:0;">
  <iframe id="workplFrame" name="workplFrame" scrolling="no" src="frameset_objectlist.php?action=linking" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
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
  <iframe id="workplFrame" name="workplFrame" scrolling="no" src="frameset_objectlist.php?location=<?php echo url_encode ($hcms_assetbrowser_location); ?>" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
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
<div id="navLayer" style="position:fixed; top:0; bottom:0; left:36px; width:260px; margin:0; padding:0;">
  <iframe id="navFrame" name="navFrame" scrolling="yes" src="explorer.php?refresh=1" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
</div>

<!-- workplace -->
<div id="workplLayer" style="position:fixed; top:0; right:0; bottom:0; left:296px; margin:0; padding:0;">
  <iframe id="workplFrame" name="workplFrame" scrolling="no" src="home.php" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
</div>
<?php } ?>

<!-- chat sidebar -->
<?php if (!empty ($mgmt_config['chat']) && empty ($hcms_assetbrowser)) { ?>
<div id="chatLayer" class="hcmsChatBar" style="position:fixed; top:0px; right:0; bottom:0; width:300px; z-index:100; display:none;">
  <iframe id="chatFrame" scrolling="auto" src="chat.php" border="0" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
</div>
<?php } ?>

</body>
</html>