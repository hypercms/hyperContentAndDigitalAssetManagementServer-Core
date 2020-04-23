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
    
    document.getElementById('navLayer').style.transition = "0.3s";
    document.getElementById('navLayer').style.width = width + 'px';
    document.getElementById('workplLayer').style.transition = "0.3s";
    document.getElementById('workplLayer').style.left = offset + 'px';
  }
}

function maxNavFrame (width)
{
  var offset = 36;
  
  if (document.getElementById('navFrame'))
  {
    width = typeof width !== 'undefined' ? width : 260;
    
    document.getElementById('navLayer').style.transition = "0.3s";
    document.getElementById('navLayer').style.width = width + 'px';
    document.getElementById('workplLayer').style.transition = "0.3s";
    document.getElementById('workplLayer').style.left = (width + offset) + 'px';
  }
}

function openMainView (link)
{
  if (link != "")
  {
    document.getElementById('objectviewMain').src = link;
  }

  hcms_showInfo('objectviewMainLayer', 0);
}

function closeMainView ()
{
  document.getElementById('objectviewMain').src = '';
  hcms_hideInfo('objectviewMainLayer');
}

function setwindows ()
{
  // set window width and height for contextmenu
  localStorage.setItem ('windowwidth', <?php echo windowwidth ("object"); ?>);
  localStorage.setItem ('windowheight', <?php echo windowheight ("object"); ?>);

  // set object popup or new window for contextmenu
  localStorage.setItem ('object_newwindow', <?php if (!empty ($mgmt_config['object_newwindow'])) echo "'true'"; else echo "'false'"; ?>);

  // set message popup or new window for contextmenu
  localStorage.setItem ('message_newwindow', <?php if (!empty ($mgmt_config['message_newwindow'])) echo "'true'"; else echo "'false'"; ?>);

  // set user popup or new window for contextmenu
  localStorage.setItem ('user_newwindow', <?php if (!empty ($mgmt_config['user_newwindow'])) echo "'true'"; else echo "'false'"; ?>);
}

var uploadwindows = 1;

function openUpload (site, cat, location, id)
{
  if (site != "" && cat != "" && location != "" && id != "")
  {
    // upload layer for location exists
    if (document.getElementById(id))
    {
      maxUpload (id);
    }
    // create new upload layer for location
    else if (uploadwindows <= 5)
    {
      var div = document.createElement("div");
      div.id = id;
      div.className = "hcmsContextMenu";
      div.style.cssText = "position:fixed; right:20px; bottom:0px; width:260px; height:36px; transition:height 0.3s;";
      div.innerHTML = '<div class="hcmsWorkplaceGeneric" style="position:absolute; right:0px; top:0px; width:106px; height:35px; margin:0; padding:2px 0px 1px 2px; z-index:91;">' + 
      '  <img src="<?php echo getthemelocation(); ?>img/button_arrow_up.png" onclick="maxUpload(\'' + id + '\');" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['collapse'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['collapse'][$lang]); ?>" />' + 
      '  <img src="<?php echo getthemelocation(); ?>img/button_arrow_down.png" onclick="minUpload(\'' + id + '\');" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['expand'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['expand'][$lang]); ?>" />' + 
      '  <img src="<?php echo getthemelocation(); ?>img/button_close.png" name="close' + id + '" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage(\'close' + id + '\',\'\',\'<?php echo getthemelocation(); ?>img/button_close_over.png\',1);" onClick="closeUpload(\'' + id + '\');" />' + 
      '</div>' + 
      '<div class="hcmsWorkplaceExplorer" style="<?php if ($is_mobile) echo '-webkit-overflow-scrolling:touch !important; overflow-y:scroll !important;'; else echo 'overflow:hidden;'; ?> overflow:hidden; position:absolute; width:100%; height:100%; z-index:90;">' + 
      ' <iframe id="uploadsFrame" src="popup_upload_html.php?uploadmode=multi&site=' + encodeURIComponent(site) + '&cat=' + encodeURIComponent(cat) + '&location=' + encodeURIComponent(location) + '" frameborder="0" style="width:100%; height:100%; margin:0; padding:0; border:0; <?php if (!$is_mobile) echo "overflow:auto;"; else echo "overflow:scroll;" ?>" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>' + 
      '</div>';
      document.getElementById('uploadsLayer').appendChild(div);
      maxUpload (id);
      uploadwindows++;
    }
    // max upload layers reached
    else
    {
      alert ('<?php echo getescapedtext ($hcms_lang['uploads'][$lang]); ?>: max 5');
    }
  }
}

function minUpload (id)
{
  if (id != "" && document.getElementById(id))
  {
    var div = document.getElementById(id);

    if (div.style.height != "36px")
    {
      // minimize upload layer
      div.style.cssText = "position:fixed; right:20px; bottom:0px; width:640px; height:36px; transition:height 0.3s;";

      setTimeout(function() {
        div.style.cssText = "position:relative; width:260px; height:36px; transition:all 0.2s; float:right; z-index:80; overflow:hidden;";
      }, 200);
    }
  }
}

function maxUpload (id)
{
  if (id != "" && document.getElementById(id))
  {
    var div = document.getElementById(id);

    // maximize upload layer
    if (div.style.height == "36px") div.style.cssText = "position:fixed; right:20px; bottom:0px; width:640px; height:600px; transition:height 0.3s; z-index:90;";
    // full screen
    else div.style.cssText = "position:fixed; left:0; right:0; bottom:0; height:100%; transition:height 0.3s; z-index:90;";
  }
}

function closeUpload (id)
{
  // verify if objects beeing edited in upload layer
  var warning = document.getElementById("uploadsFrame").contentWindow.showwarning();

  if (warning != "") alert ("<?php echo getescapedtext ($hcms_lang['please-enter-the-metadata-for-your-uploads'][$lang]); ?>");

  // close upload layer
  if (document.getElementById(id) && warning == "")
  {
    var div = document.getElementById(id); 
    div.parentNode.removeChild(div);
    uploadwindows--;
  }
}

function showwarning ()
{
  return "<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-remove-all-events'][$lang]); ?>";
}

$(document).ready(function()
{
  setviewport();
  setwindows();
  
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

<body onbeforeunload="return showwarning();">

<!-- popup for preview/live-view and forms (do not used nested fixed positioned div-layers due to MS IE and Edge issue) -->
<div id="objectviewMainLayer" style="display:none; z-index:20;">
  <div style="position:fixed; right:2px; top:2px; z-index:91;">
    <img name="hcms_mediaClose" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="closeMainView();" />
  </div>
  <div class="hcmsWorkplaceExplorer" style="<?php if ($is_mobile) echo '-webkit-overflow-scrolling:touch !important; overflow-y:scroll !important;'; else echo 'overflow:hidden;'; ?> position:fixed; margin:0; padding:0; left:0; top:0; right:0; bottom:0; z-index:90;">
   <iframe id="objectviewMain" name="objectviewMain" src="" frameborder="0" style="width:100%; height:100%; margin:0; padding:0; border:0; <?php if (!$is_mobile) echo "overflow:auto;"; else echo "overflow:scroll;" ?>" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
  </div>
</div>

<!-- top/left bar -->
<div class="hcmsWorkplaceTop" style="position:fixed; left:0; top:0; bottom:0; width:36px;">
  <img src="<?php if ($mgmt_config['logo_top'] != "") echo $mgmt_config['logo_top']; else echo getthemelocation()."img/logo_top.png"; ?>" class="hcmsButtonTiny hcmsLogoTop" onclick="openInfo();" title="hyper Content & Digital Asset Management Server" alt="hyper Content & Digital Asset Management Server" />
  
  <?php if (empty ($hcms_assetbrowser)) { ?>
  <img src="<?php echo getthemelocation(); ?>img/home.png" class="hcmsButtonTiny hcmsButtonSizeSquare" style="padding:2px;" onclick="showHome();" alt="<?php echo getescapedtext ($hcms_lang['home'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['home'][$lang]); ?>" />
  <?php } ?>

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
  
  <?php if (empty ($hcms_assetbrowser) && empty ($hcms_portal)) { ?>
  <img src="<?php echo getthemelocation(); ?>img/button_logout.png" class="hcmsButtonTiny hcmsButtonSizeSquare" style="padding:2px;" onclick="top.location='userlogout.php';" alt="<?php echo getescapedtext ($hcms_lang['logout'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['logout'][$lang]); ?>" />
  <?php } ?>
</div>

<!-- user info -->
<?php if (empty ($hcms_assetbrowser) && empty ($hcms_portal)) { ?>
<img src="<?php echo getthemelocation(); ?>img/button_info.png" class="hcmsButtonTiny hcmsButtonSizeSquare" style="position:absolute; left:0; bottom:0; padding:2px; margin:32px 0px;" onclick="hcms_showInfo ('userInfoLayer', 4);" alt="<?php echo getescapedtext ($hcms_lang['information'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['information'][$lang]); ?>" />
  
<div id="userInfoLayer" class="hcmsMessage" style="position:absolute; bottom:10px; left:32px; display:none; z-index:100; padding:4px; width:200px; min-height:80px; overflow-x:hidden; overflow-y:auto; white-space:nowrap;">
  <img src="<?php echo getthemelocation()."img/user.png"; ?>" class="hcmsIconList" /> <span class="hcmsHeadline" style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['user'][$lang]); ?></span><br/>
  <span class="hcmsHeadlineTiny hcmsTextWhite">&nbsp;<?php echo getsession ('hcms_user'); ?></span><br/><br/>

  <img src="<?php echo getthemelocation()."img/button_time.png"; ?>" class="hcmsIconList" /> <span class="hcmsHeadline" style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['datetime'][$lang]); ?></span><br/>
  <span class="hcmsHeadlineTiny hcmsTextWhite">&nbsp;<?php $servertime->InstallClock(); ?></span>
</div>
<?php $servertime->InstallClockBody(); ?>
<?php } ?>

<?php if (linking_valid() == true) { ?>
<!-- workplace -->
<div id="workplLayer" style="position:fixed; top:0; bottom:0; left:36px; right:0; margin:0; padding:0;">
  <iframe id="workplFrame" name="workplFrame" src="frameset_objectlist.php?action=linking" frameborder="0" scrolling="no" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:hidden;" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
</div>
<?php } elseif (!empty ($hcms_assetbrowser)) {
  // location set by assetbrowser
  if (!empty ($hcms_assetbrowser_location)) { ?>
<!-- explorer -->
<div id="navLayer" style="position:fixed; top:0; bottom:0; left:0; width:260px; margin:0; padding:0;">
  <iframe id="navFrame" name="navFrame" src="explorer.php" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;"></iframe>
</div>

<!-- workplace -->
<div id="workplLayer" style="position:fixed; top:0; right:0; bottom:0; left:260px; margin:0; padding:0;">
  <iframe id="workplFrame" name="workplFrame" src="frameset_objectlist.php?location=<?php echo url_encode ($hcms_assetbrowser_location); ?>" frameborder="0" scrolling="no" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:hidden;" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
</div>
<?php } 
  // no location set by assetbrowser
  else { ?>
<!-- explorer -->
<div id="navLayer" style="position:fixed; top:0; bottom:0; left:0; width:260px; margin:0; padding:0;">
  <iframe id="navFrame" name="navFrame" src="explorer.php" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;"></iframe>
</div>

<!-- workplace -->
<div id="workplLayer" style="position:fixed; top:0; right:0; bottom:0; left:260px; margin:0; padding:0;">
  <iframe id="workplFrame" name="workplFrame" src="empty.php" frameborder="0" scrolling="no" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:hidden;"></iframe>
</div>
<?php }
} else { ?>
<!-- explorer -->
<div id="navLayer" style="position:fixed; top:0; bottom:0; left:36px; width:260px; margin:0; padding:0;">
  <iframe id="navFrame" name="navFrame" src="explorer.php?refresh=1" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;"></iframe>
</div>

<!-- workplace -->
<div id="workplLayer" style="position:fixed; top:0; right:0; bottom:0; left:296px; margin:0; padding:0;">
  <iframe id="workplFrame" name="workplFrame" src="home.php" frameborder="0" scrolling="no" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:hidden;" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
</div>
<?php } ?>

<!-- chat sidebar -->
<?php if (!empty ($mgmt_config['chat']) && empty ($hcms_assetbrowser)) { ?>
<div id="chatLayer" class="hcmsChatBar" style="position:fixed; top:0; right:-320px; bottom:0; width:300px; z-index:100;">
  <iframe id="chatFrame" src="chat.php" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;"></iframe>
</div>
<?php } ?>

<!-- uploads -->
<div id="uploadsLayer" style="position:fixed; bottom:0; right:0; max-width:100%; max-height:36px; margin:0; padding:0; z-index:10;"></div>

</body>
</html>