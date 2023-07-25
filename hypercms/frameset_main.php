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
// version info
require ("version.inc.php");


// input parameters (used to open framset_objectlist)
$location = url_encode (getrequest ("location", "url"));
$virtual = url_encode (getrequest ("virtual", "numeric"));

// layer size definitions
$width_top = 36;
$width_navigation = 300;
$width_search = 440;

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// write and close session (non-blocking other frames)
suspendsession ();
?>
<!DOCTYPE HTML>
<html lang="<?php if (!empty ($lang)) echo $lang; ?>">
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript" src="javascript/click.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<!-- JQuery used for AJAX viewport set request -->
<script src="javascript/jquery/jquery.min.js" type="text/javascript"></script>
<!-- chat -->
<script type="text/javascript" src="javascript/chat.min.js"></script>

<?php if (is_facerecognition ("sys")) { ?>
<!-- face recognition -->
<script defer src="javascript/facerecognition/face-api.min.js"></script>
<script defer src="javascript/facerecognition/face-init.js"></script>
<script>
// reassign permissions for main.js and contextmenu.js
hcms_permission['shortcuts'] = false;
hcms_permission['minnavframe'] = false;
</script>
<?php } ?>
<style type="text/css">
<?php
// inverted main colors
if (!empty ($hcms_themeinvertcolors))
{
  if (!empty ($hcms_hoverinvertcolors)) $invertonhover = false;
  else $invertonhover = true;

  echo invertcolorCSS ($hcms_themeinvertcolors, ".hcmsInvertColor", true, $invertonhover);
}
// inverted hover colors
elseif (!empty ($hcms_hoverinvertcolors))
{
  echo invertcolorCSS ($hcms_hoverinvertcolors, ".hcmsInvertColor", false, true);
  echo invertcolorCSS ($hcms_hoverinvertcolors, ".hcmsInvertHoverColor", true, false);
}
?>
</style>
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

// callback for hcms_geolocation
function hcms_geoposition (position)
{
  if (position)
  {
    var latitude = position.coords.latitude;
    var longitude = position.coords.longitude;
  }
  else return false;
  
  if (latitude != "" && longitude != "")
  {
    // AJAX request to set geo location
    $.post("<?php echo $mgmt_config['url_path_cms']; ?>service/setgeolocation.php", {latitude: latitude, longitude: longitude});

    return true;
  }
  else return false;
}

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
  hcms_openWindow('top_info.php', 'help', 'location=no,menubar=no,toolbar=no,titlebar=no,resizable=no,scrollbars=no', '640', '400');
}

function showHome ()
{
  document.getElementById('workplFrame').src='home.php';
  minNavFrame();
}

function showSearch ()
{
  parent.frames['navFrame'].showSearch();
  maxSearchFrame();
}

function switchNav ()
{
  if (parent.frames['navFrame'].hcms_isHiddenLayer('search') == false)
  {
    parent.frames['navFrame'].showNav();
    maxNavFrame();
  }
  else if (document.getElementById('navLayer'))
  {
    if (document.getElementById('navLayer').style.width == '<?php echo $width_navigation; ?>px')
    {
      minNavFrame();
    }
    else
    {
      maxNavFrame();
    }
  }
}

function switchSearch ()
{
  if (parent.frames['navFrame'].hcms_isHiddenLayer('search') == true)
  {
    parent.frames['navFrame'].showSearch();
    maxSearchFrame();
  }
  else if (document.getElementById('navLayer'))
  {
    if (document.getElementById('navLayer').style.width == '<?php echo $width_search; ?>px')
    {
      minNavFrame();
    }
    else
    {
      maxSearchFrame();
    }
  }
}

function minNavFrame (width)
{
  var offset = <?php echo $width_top; ?>;
  
  if (document.getElementById('navFrame'))
  {
    width = typeof width !== 'undefined' ? width : 0;
    
    if (hcms_transitioneffect == true) document.getElementById('navLayer').style.transition = "0.3s";
    document.getElementById('navLayer').style.width = width + 'px';
    if (hcms_transitioneffect == true) document.getElementById('workplLayer').style.transition = "0.3s";
    document.getElementById('workplLayer').style.left = offset + 'px';
  }
}

function maxNavFrame (width)
{
  var offset = <?php echo $width_top; ?>;
  
  if (document.getElementById('navFrame'))
  {
    width = typeof width !== 'undefined' ? width : <?php echo $width_navigation; ?>;
    
    if (hcms_transitioneffect == true) document.getElementById('navLayer').style.transition = "0.3s";
    document.getElementById('navLayer').style.width = width + 'px';
    if (hcms_transitioneffect == true) document.getElementById('workplLayer').style.transition = "0.3s";
    document.getElementById('workplLayer').style.left = (width + offset) + 'px';
  }
}

function maxSearchFrame (width)
{
  var offset = <?php echo $width_top; ?>;
  
  if (document.getElementById('navFrame'))
  {
    width = typeof width !== 'undefined' ? width : <?php echo $width_search; ?>;
    
    if (hcms_transitioneffect == true) document.getElementById('navLayer').style.transition = "0.3s";
    document.getElementById('navLayer').style.width = width + 'px';
    if (hcms_transitioneffect == true) document.getElementById('workplLayer').style.transition = "0.3s";
    document.getElementById('workplLayer').style.left = (width + offset) + 'px';
  }
}

function openMainView (link)
{
  if (link != "")
  {
    document.getElementById('objectviewMain').src = link;
  }

  hcms_showFormLayer('objectviewMainLayer', 0);
}

function closeMainView ()
{
  document.getElementById('objectviewMain').src = '';
  hcms_hideFormLayer('objectviewMainLayer');
}

var popupwindows = 1;

function openPopup (url, id)
{
  if (url != "" && id != "")
  {
    // popup layer for the same id exists
    if (document.getElementById(id))
    {
      return maxPopup (id);
    }
    // create new popup layer
    else if (popupwindows <= 5)
    {
      var div = document.createElement("div");
      div.id = id;
      div.className = "hcmsContextMenu";
      div.style.cssText = "position:fixed; right:20px; bottom:0px; width:300px; height:36px; transition:height 0.3s;";
      div.innerHTML = '<div style="position:absolute; right:0px; top:0px; width:106px; height:35px; margin:0; padding:2px 0px 1px 2px; z-index:91;">' + 
      '  <img src="<?php echo getthemelocation(); ?>img/button_arrow_up.png" onclick="maxPopup(\'' + id + '\');" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['collapse'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['collapse'][$lang]); ?>" />' + 
      '  <img src="<?php echo getthemelocation(); ?>img/button_arrow_down.png" onclick="minPopup(\'' + id + '\');" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['expand'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['expand'][$lang]); ?>" />' + 
      '  <img src="<?php echo getthemelocation(); ?>img/button_close.png" name="close' + id + '" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage(\'close' + id + '\',\'\',\'<?php echo getthemelocation(); ?>img/button_close_over.png\',1);" onClick="closePopup(\'' + id + '\');" />' + 
      '</div>' + 
      '<div class="hcmsWorkplaceGeneric" style="<?php if ($is_mobile) echo '-webkit-overflow-scrolling:touch !important; overflow-y:scroll !important;'; else echo 'overflow:hidden;'; ?> overflow:hidden; position:absolute; width:100%; height:100%; z-index:90;">' + 
      ' <iframe name="' + id + 'Frame" id="' + id + 'Frame" src="' + url + '" frameborder="0" style="width:100%; height:100%; margin:0; padding:0; border:0; <?php if (!$is_mobile) echo "overflow:auto;"; else echo "overflow:scroll;" ?>" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>' + 
      '</div>';
      document.getElementById('popupsLayer').appendChild(div);
      maxPopup (id);
      popupwindows++;

      return true;
    }
    // max popup layers reached
    else
    {
      alert ('Max 5 popups');
    }
  }

  return false;
}

function minPopup (id)
{
  if (id != "" && document.getElementById(id))
  {
    // popup div layer
    var div = document.getElementById(id);

    if (div.style.height != "36px")
    {
      // minimize popup layer
      div.style.cssText = "position:fixed; right:20px; bottom:0px; width:640px; height:36px; transition:height 0.3s;";

      setTimeout(function() {
        div.style.cssText = "position:relative; width:300px; height:36px; transition:all 0.2s; float:right; z-index:80; overflow:hidden;";
      }, 200);

      // popup iframe
      var iframe = document.getElementById(id + 'Frame');

      // disable scrolling
      iframe.style.overflow = "hidden";
      iframe.scrolling = "no";

      return true;
    }
  }

  return false;
}

function maxPopup (id)
{
  if (id != "" && document.getElementById(id))
  {
    // popup div layer
    var div = document.getElementById(id);

    // maximize popup layer
    if (div.style.height == "36px") div.style.cssText = "position:fixed; right:20px; bottom:0px; width:640px; height:600px; transition:height 0.3s; z-index:90;";
    // full screen
    else div.style.cssText = "position:fixed; left:0; right:0; bottom:0; height:100%; transition:height 0.3s; z-index:90;";

    // popup iframe
    var iframe = document.getElementById(id + 'Frame');

    // enable scrolling
    iframe.style.overflow = "<?php if (!$is_mobile) echo "auto"; else echo "scroll" ?>";
    iframe.scrolling = "yes";

    return true;
  }

  return false;
}

function closePopup (id)
{
  var warning = "";

  // verify if objects being edited in popup layer
  if (document.getElementById(id + 'Frame') && typeof document.getElementById(id + 'Frame').contentWindow.showwarning !== "undefined")
  {
    var warning = document.getElementById(id+ 'Frame').contentWindow.showwarning();

    if (warning != "") alert ("<?php echo getescapedtext ($hcms_lang['please-enter-the-metadata-for-your-uploads'][$lang]); ?>");
  }

  // close popup layer
  if (document.getElementById(id) && warning == "")
  {
    var div = document.getElementById(id); 
    div.parentNode.removeChild(div);
    popupwindows--;

    return true;
  }

  return false;
}

function showwarning ()
{
  return "<?php echo getescapedtext ($hcms_lang['warning'][$lang]); ?>";
}

function setSearchLocation (location, name)
{
  if (document.getElementById('navFrame'))
  {
    document.getElementById('navFrame').contentWindow.setSearchLocation (location, name);
    maxSearchFrame();

    return true;
  }

  return false;
}

function setGlobals ()
{
  // set window width and height for contextmenu
  localStorage.setItem('windowwidth', <?php echo windowwidth ("object"); ?>);
  localStorage.setItem('windowheight', <?php echo windowheight ("object"); ?>);

  // set object popup or new window for contextmenu
  localStorage.setItem('object_newwindow', <?php if (!empty ($mgmt_config['object_newwindow'])) echo "'true'"; else echo "'false'"; ?>);

  // set message popup or new window for contextmenu
  localStorage.setItem('message_newwindow', <?php if (!empty ($mgmt_config['message_newwindow'])) echo "'true'"; else echo "'false'"; ?>);

  // set user popup or new window for contextmenu
  localStorage.setItem('user_newwindow', <?php if (!empty ($mgmt_config['user_newwindow'])) echo "'true'"; else echo "'false'"; ?>);

  // set is_mobile
  localStorage.setItem('is_mobile', <?php if (!empty ($is_mobile)) echo "'true'"; else echo "'false'"; ?>);

  // reset values (initially set in main.js)
  if (localStorage.getItem('is_mobile') !== null && localStorage.getItem('is_mobile') == 'false')
  {
    is_mobile = false;
    hcms_transitioneffect = true;
  }
  else
  {
    is_mobile = true;
    hcms_transitioneffect = false;
  }
}

// start chat
var chat =  new Chat();

function sendtochat (text)
{
  if (text != "")
  {
    var username = '<?php echo $user; ?>';
    // strip tags
    username = username.replace(/(<([^>]+)>)/ig,"");
    chat.send(text, username);
  }
}

// init
$(document).ready(function()
{
  setviewport();
  setGlobals();
  
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

<body class="hcmsMainWindow hcmsWorkplaceObjectlist" onload="<?php if (getsession ('hcms_temp_latitude') == "" || getsession ('hcms_temp_longitude') == "") echo "hcms_geolocation(); "; ?>" onbeforeunload="return showwarning();">

<!-- popup for preview/live-view and forms (do not used nested fixed positioned div-layers due to MS IE and Edge issue) -->
<div id="objectviewMainLayer" style="display:none; z-index:20;">
  <div style="position:fixed; right:2px; top:2px; z-index:91;">
    <img name="hcms_mediaClose" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="closeMainView();" />
  </div>
  <div class="hcmsWorkplaceWorkflow" style="<?php if ($is_mobile) echo '-webkit-overflow-scrolling:touch !important; overflow-y:scroll !important;'; else echo 'overflow:hidden;'; ?> position:fixed; margin:0; padding:0; left:0; top:0; right:0; bottom:0; z-index:90;">
   <iframe id="objectviewMain" name="objectviewMain" src="" frameborder="0" style="width:100%; height:100%; margin:0; padding:0; border:0; <?php if (!$is_mobile) echo "overflow:auto;"; else echo "overflow:scroll;" ?>" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
  </div>
</div>

<!-- top/left bar -->
<div class="hcmsWorkplaceTop" style="position:fixed; left:0; top:0; bottom:0; width:<?php echo $width_top; ?>px;">
  <div class="hcmsButtonTinyBlank  hcmsButtonSizeSquare">
    <img src="<?php if (!empty ($mgmt_config['logo_top'])) echo $mgmt_config['logo_top']; else echo getthemelocation()."img/logo_top.png"; ?>" class="hcmsLogoTop" onclick="openInfo();" title="hyper Content & Digital Asset Management Server" alt="hyper Content & Digital Asset Management Server" />
  </div>

  <?php if (empty ($hcms_assetbrowser) && linking_valid() == false) { ?>
  <div class="hcmsButtonTiny hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare" style="padding:2px;">
    <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/home.png" class="hcmsButtonSizeSquare" onclick="showHome();" alt="<?php echo getescapedtext ($hcms_lang['home'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['home'][$lang]); ?>" />
  </div>
  <?php } ?>

  <?php if (linking_valid() == false) { ?>
  <div class="hcmsButtonTiny hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare" style="padding:2px;">
    <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_explorer.png" class="hcmsButtonSizeSquare" onclick="switchNav();" alt="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" />
  </div>
  <div class="hcmsButtonTiny hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare" style="padding:2px;">
    <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_search.png" class="hcmsButtonSizeSquare" onclick="switchSearch();" alt="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" />
  </div>
  <?php } ?>
  
  <?php if (linking_valid() == true)  { ?>
  <a href="frameset_objectlist.php?action=linking" target="workplFrame">
    <div class="hcmsButtonTiny hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare" style="padding:2px;">
      <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_view_gallery_medium.png" class="hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" />
    </div>
  </a>
  <?php if (checkrootpermission ('desktoptaskmgmt')) { ?>
  <a href="task/task_list.php" target="workplFrame">
    <div class="hcmsButtonTiny hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare" style="padding:2px;">
      <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/task.png" class="hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['task-management'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['task-management'][$lang]); ?>" />
    </div>
  </a>
  <?php } ?>
  <?php } ?>

  <?php if (empty ($hcms_assetbrowser) && !empty ($mgmt_config['chat'])) { ?>
  <div class="hcmsButtonTiny hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare" style="padding:2px;">
    <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_chat.png" class="hcmsButtonSizeSquare" onClick="hcms_openChat();" alt="<?php echo getescapedtext ($hcms_lang['chat'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['chat'][$lang]); ?>" />
  </div>
  <?php } ?>
  
  <?php if (empty ($hcms_assetbrowser) && empty ($hcms_portal)) { ?>
  <div class="hcmsButtonTiny hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare" style="padding:2px;">
    <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_logout.png" class="hcmsButtonSizeSquare" onclick="top.location='userlogout.php';" alt="<?php echo getescapedtext ($hcms_lang['logout'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['logout'][$lang]); ?>" />
  </div>
  <?php } ?>

  <?php if (empty ($hcms_assetbrowser) && empty ($hcms_portal)) { ?>
  <div class="hcmsButtonTiny hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare"  style="position:absolute; left:0; bottom:0; margin:32px 0px; padding:2px;">
    <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_info.png" class="hcmsButtonSizeSquare" onclick="hcms_showFormLayer('userInfoLayer', 4);" alt="<?php echo getescapedtext ($hcms_lang['information'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['information'][$lang]); ?>" />
  </div>
  <?php } ?>
</div>

<!-- user info -->
<?php if (empty ($hcms_assetbrowser) && empty ($hcms_portal)) { ?>
<div id="userInfoLayer" class="hcmsMessage" style="position:absolute; bottom:10px; left:32px; display:none; z-index:100; padding:4px; width:200px; min-height:80px; overflow-x:hidden; overflow-y:auto; white-space:nowrap;">
  <img src="<?php echo getthemelocation()."img/user.png"; ?>" class="hcmsIconList" /> <span class="hcmsHeadline" style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['user'][$lang]); ?></span><br/>
  <span class="hcmsHeadlineTiny hcmsTextWhite">&nbsp;<?php echo getsession ('hcms_user'); ?></span><br/><br/>
  <img src="<?php echo getthemelocation()."img/button_time.png"; ?>" class="hcmsIconList" /> <span class="hcmsHeadline" style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['datetime'][$lang]); ?></span><br/>
  <span class="hcmsHeadlineTiny hcmsTextWhite">&nbsp;<?php $servertime->InstallClock(); ?></span>
</div>
<?php $servertime->InstallClockBody(); ?>
<?php } ?>

<!-- Access Links -->
<?php if (linking_valid() == true) { ?>

  <!-- workplace -->
  <div id="workplLayer" style="position:fixed; top:0; bottom:0; left:<?php echo $width_top; ?>px; right:0; margin:0; padding:0;">
    <iframe id="workplFrame" name="workplFrame" src="frameset_objectlist.php?action=linking" frameborder="0" scrolling="no" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:hidden;" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
  </div>

<!-- Asset Browser -->
<?php } elseif (!empty ($hcms_assetbrowser)) {
  // location set by assetbrowser
  if (!empty ($hcms_assetbrowser_location)) { ?>

  <!-- explorer -->
  <div id="navLayer" style="position:fixed; top:0; bottom:0; left:<?php echo $width_top; ?>px; width:<?php echo $width_navigation; ?>px; margin:0; padding:0;">
    <iframe id="navFrame" name="navFrame" src="explorer.php" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;"></iframe>
  </div>

  <!-- workplace -->
  <div id="workplLayer" style="position:fixed; top:0; right:0; bottom:0; left:<?php echo ($width_top + $width_navigation); ?>px; margin:0; padding:0;">
    <iframe id="workplFrame" name="workplFrame" src="frameset_objectlist.php?location=<?php echo url_encode ($hcms_assetbrowser_location); ?>" frameborder="0" scrolling="no" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:hidden;" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
  </div>

  <?php }
    // no location set by assetbrowser
    else { ?>

  <!-- explorer -->
  <div id="navLayer" style="position:fixed; top:0; bottom:0; left:<?php echo $width_top; ?>px; width:<?php echo $width_navigation; ?>px; margin:0; padding:0;">
    <iframe id="navFrame" name="navFrame" src="explorer.php" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;"></iframe>
  </div>

  <!-- workplace -->
  <div id="workplLayer" style="position:fixed; top:0; right:0; bottom:0; left:<?php echo ($width_top + $width_navigation); ?>px; margin:0; padding:0;">
    <iframe id="workplFrame" name="workplFrame" src="empty.php" frameborder="0" scrolling="no" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:hidden;"></iframe>
  </div>

<!-- Standard -->
<?php }
// standard workplace with home screen
} else { ?>

  <!-- explorer -->
  <div id="navLayer" style="position:fixed; top:0; bottom:0; left:<?php echo $width_top; ?>px; width:<?php echo $width_navigation; ?>px; margin:0; padding:0;">
    <iframe id="navFrame" name="navFrame" src="explorer.php?refresh=1" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;"></iframe>
  </div>

  <!-- workplace -->
  <div id="workplLayer" style="position:fixed; top:0; right:0; bottom:0; left:<?php echo ($width_top + $width_navigation); ?>px; margin:0; padding:0;">
    <iframe id="workplFrame" name="workplFrame" src="<?php if (!empty ($location)) echo "frameset_objectlist.php?location=".$location."&virtual=".$virtual; else echo "home.php"; ?>" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
  </div>

<?php } ?>

<!-- chat sidebar -->
<?php if (!empty ($mgmt_config['chat']) && empty ($hcms_assetbrowser)) { ?>
<div id="chatLayer" class="hcmsChatBar" style="position:fixed; top:0; right:-320px; bottom:0; width:300px; z-index:100;">
  <iframe id="chatFrame" src="chat.php" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;"></iframe>
</div>
<?php } ?>

<!-- popups -->
<div id="popupsLayer" style="position:fixed; bottom:0; right:0; max-width:100%; max-height:36px; margin:0; padding:0; z-index:10;"></div>

<?php if (is_facerecognition ($user)) {
// Syntax: <iframe sandbox="value">
//
// Values:
// (no value) ... Applies all restrictions
// allow-forms ... Allows form submission
// allow-modals ...	Allows to open modal windows
// allow-orientation-lock ...	Allows to lock the screen orientation
// allow-pointer-lock ...	Allows to use the Pointer Lock API
// allow-popups ... Allows popups
// allow-popups-to-escape-sandbox ... Allows popups to open new windows without inheriting the sandboxing
// allow-presentation ... Allows to start a presentation session
// allow-same-origin ... Allows the iframe content to be treated as being from the same origin
// allow-scripts ... Allows to run scripts
// allow-top-navigation ... Allows the iframe content to navigate its top-level browsing context
// allow-top-navigation-by-user-activation ... Allows the iframe content to navigate its top-level browsing context, but only if initiated by user
?>
<!-- recognize faces service -->
<div id="recognizefacesLayer" style="position:fixed; bottom:0; right:22px; width:1200px; height:620px; margin:0; padding:0; z-index:-10; visibility:hidden;">
  <iframe src="<?php echo createfacerecognitionservice ($user); ?>" sandbox="allow-same-origin allow-scripts allow-forms" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;"></iframe>
</div>
<?php } ?>

</body>
</html>
