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
$view = getrequest ("view");

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

toggleview ($view);

// write and close session (non-blocking other frames)
suspendsession ();
?>
<!DOCTYPE html> 
<html lang="<?php if (!empty ($lang)) echo $lang; ?>">
<head> 
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0"></meta>
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<!-- 57 x 57 Android and iPhone 3 icon -->
<link rel="apple-touch-icon" media="screen and (resolution: 163dpi)" href="<?php echo getthemelocation(); ?>img/mobile_icon57.png" />
<!-- 114 x 114 iPhone 4 icon -->
<link rel="apple-touch-icon" media="screen and (resolution: 326dpi)" href="<?php echo getthemelocation(); ?>img/mobile_icon114.png" />
<!-- 57 x 57 Nokia icon -->
<link rel="shortcut icon" href="<?php echo getthemelocation(); ?>img/mobile_icon57.png" />
<!-- main library -->
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<!-- JQuery used for AJAX viewport set request -->
<script src="javascript/jquery/jquery.min.js" type="text/javascript"></script>
<style>
<?php
// invert colors
if (!empty ($hcms_themeinvertcolors))
{
  echo invertcolorCSS ($hcms_themeinvertcolors);
}
?>
</style>
<script type="text/javascript">
// reassign permissions for main.js and contextmenu.js
hcms_permission['shortcuts'] = false;
hcms_permission['minnavframe'] = false;

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

function switchNav ()
{
  if (document.getElementById('navLayer'))
  {
    if (document.getElementById('navLayer').style.left == '0px')
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
  var offset = 0;
  
  if (document.getElementById('navFrame'))
  {
    width = typeof width !== 'undefined' ? width : -280;

    document.getElementById('navLayer').style.transition = "0.3s";
    document.getElementById('navLayer').style.left = width + 'px';
  }
}

function maxNavFrame (width)
{
  var offset = 0;
  
  if (document.getElementById('navFrame'))
  {
    width = typeof width !== 'undefined' ? width : 0;

    document.getElementById('navLayer').style.transition = "0.3s";
    document.getElementById('navLayer').style.left = width + 'px';
  }
}

var popupwindows = 1;

function openPopup (url, id)
{
  if (url != "" && id != "")
  {
    // popup layer for same id exists
    if (document.getElementById(id))
    {
      maxPopup (id);
    }
    // create new popup layer
    else if (popupwindows <= 1)
    {
      var div = document.createElement("div");
      div.id = id;
      div.className = "hcmsContextMenu";
      div.style.cssText = "position:fixed; bottom:0px; left:0px; right:0px; height:36px; transition:height 0.3s;";
      div.innerHTML = '<div style="position:absolute; right:0px; top:0px; width:112px; height:35px; margin:0; padding:2px 0px 1px 2px; z-index:91;">' + 
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
      alert ('Max 1 popup');

      // stop execution
      return false;
    }
  }
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
      div.style.cssText = "position:fixed; right:0px; bottom:0px; width:100%; height:36px; transition:height 0.3s;";

      setTimeout(function() {
        div.style.cssText = "position:relative; width:300px; height:36px; transition:all 0.2s; float:right; z-index:80; overflow:hidden;";
      }, 200);

      // popup iframe
      var iframe = document.getElementById(id + 'Frame');

      // disable scrolling
      iframe.style.overflow = "hidden";
      iframe.scrolling = "no";
    }
  }
}

function maxPopup (id)
{
  if (id != "" && document.getElementById(id))
  {
    // popup div layer
    var div = document.getElementById(id);

    // full screen
    div.style.cssText = "position:fixed; left:0; right:0; bottom:0; height:calc(100% - 36px); transition:height 0.3s; z-index:90;";

    // popup iframe
    var iframe = document.getElementById(id + 'Frame');

    // enable scrolling
    iframe.style.overflow = "<?php if (!$is_mobile) echo "auto"; else echo "scroll" ?>";
    iframe.scrolling = "yes";
  }
}

function closePopup (id)
{
  var warning = "";

  // verify if objects being edited in popup layer
  if (document.getElementById(id+ 'Frame') && typeof document.getElementById(id+ 'Frame').contentWindow.showwarning !== "undefined")
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
  }
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

<body class="hcmsMainWindow hcmsWorkplaceObjectlist" onload="<?php if (getsession ('hcms_temp_latitude') == "" || getsession ('hcms_temp_longitude') == "") echo "hcms_geolocation(); "; ?>">

  <!-- header -->
  <div class="hcmsWorkplaceTop" style="position:fixed; left:0; top:0; width:100%; height:36px;">
    <div style="float:left; width:72px; text-align:left;"> 
    <?php if (linking_valid() == false) { ?>
      <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_explorer.png" onclick="switchNav();" class="hcmsButtonTiny hcmsButtonSizeSquare" style="float:left; padding:2px;" alt="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" />
    <?php } else { ?>
      <a href="frameset_objectlist.php?action=linking" id="navigateButton" target="workplFrame" onclick="this.style.display='none'; document.getElementById('tasksButton').style.display='inline';" style="display:none;">
        <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_view_gallery_medium.png" class="hcmsButtonTiny hcmsButtonSizeSquare" style="float:left; padding:2px;" alt="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" />
      </a>
      <a href="task/task_list.php" id="tasksButton" target="workplFrame" onclick="document.getElementById('navigateButton').style.display='inline'; this.style.display='none';">
        <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/task.png" class="hcmsButtonTiny hcmsButtonSizeSquare" style="float:left; padding:2px;" alt="<?php echo getescapedtext ($hcms_lang['task-management'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['task-management'][$lang]); ?>" />
      </a>
    <?php } ?>
    <?php if (empty ($hcms_assetbrowser)) { ?>
      <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_logout.png" onclick="top.location='userlogout.php';" class="hcmsButtonTiny hcmsButtonSizeSquare" style="float:left; padding:2px;" alt="<?php echo getescapedtext ($hcms_lang['logout'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['logout'][$lang]); ?>" />
    <?php } ?>
    </div>
    <div style="float:left; width:calc(100% - 144px); text-align:center;"><div class="hcmsHeadline" style="padding:8px;"></div></div>
    <div style="float:left; width:36px; text-align:right; margin-left:35px;"> 
    <?php if (empty ($hcms_assetbrowser) && !empty ($mgmt_config['chat'])) { ?>
      <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_chat.png" onclick="hcms_openChat();" class="hcmsButtonTiny hcmsButtonSizeSquare" style="float:left; padding:2px;" alt="<?php echo getescapedtext ($hcms_lang['chat'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['chat'][$lang]); ?>" />
    <?php } ?>
    </div>
  </div>

  <?php if (linking_valid() == false) { ?>
  <!-- navigator panel -->
  <div id="navLayer" class="hcmsWorkplaceExplorer hcmsBoxShadow" style="position:fixed; top:36px; bottom:0; left:-280px; width:260px !important; margin:0; padding:0; z-index:50; overflow:hidden;">
    <div style="width:100%; height:70px; overflow:hidden;">
      <form name="searchform_general" method="post" action="frameset_objectlist.php" target="workplFrame" style="margin:0; padding:0; border:0;">
        <input type="hidden" name="action" value="base_search" />
        <input type="hidden" name="search_dir" value="" />
        <input type="text" name="search_expression" <?php if (empty ($mgmt_config['db_connect_rdbms'])) echo "readonly=\"readonly\""; ?> style="margin:6px 0px 3px 3px; padding:7px; width:252px;" maxlength="2000" value="" placeholder="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" />
        <img src="<?php echo getthemelocation("day"); ?>img/button_search.png" <?php if (!empty ($mgmt_config['db_connect_rdbms']) && linking_valid() == false) echo "onclick=\"if (document.forms['searchform_general'].elements['search_expression'].value!='') document.forms['searchform_general'].submit();\""; ?> class="hcmsButtonTiny hcmsButtonSizeSquare" style="padding:2px; margin-left:-40px;" alt="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" />
        <div style="padding:2px 5px;">
          <label><input type="checkbox" name="search_cat" id="search_cat_text" value="text" onclick="if (this.checked) document.getElementById('search_cat_file').checked=false; else document.getElementById('search_cat_file').checked=true;" checked /> <?php echo getescapedtext ($hcms_lang['text'][$lang]); ?></label> &nbsp;
          <label><input type="checkbox" name="search_cat" id="search_cat_file" value="file" onclick="if (this.checked) document.getElementById('search_cat_text').checked=false; else document.getElementById('search_cat_text').checked=true;" /> <?php echo getescapedtext ($hcms_lang['object'][$lang]." ".$hcms_lang['name'][$lang]); ?></label>
        </div>
      </form>
    </div>
    <div id="navContainer" style="width:100%; height:calc(100% - 82px); margin:0; padding:0; <?php if ($is_iphone) echo "overflow:auto; -webkit-overflow-scrolling:touch;"; else echo "overflow:hidden;"; ?>">
      <iframe id="navFrame" src="explorer.php" frameborder="0" style="margin:0; padding:0; border:0; width:100%; height:100%; <?php if (!$is_iphone) echo "overflow:auto;"; ?>"></iframe>
    </div>
  </div>
  <?php } ?>
  
  <!-- content -->
  <div id="workplLayer" style="position:fixed; top:36px; bottom:0; left:0; right:0; margin:0; padding:0; <?php if ($is_iphone) echo "overflow:auto; -webkit-overflow-scrolling:touch;"; else echo "overflow:hidden;"; ?>">
    <?php if (linking_valid() == true) { ?>
    <iframe id="workplFrame" name="workplFrame" src="frameset_objectlist.php?action=linking" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; <?php if (!$is_iphone) echo "overflow:auto;"; ?>" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
    <?php } elseif (!empty ($hcms_assetbrowser)) { ?>
      <?php
      // location set by assetbrowser
      if (!empty ($hcms_assetbrowser_location)) { ?>
      <iframe id="workplFrame" name="workplFrame" src="frameset_objectlist.php?location=<?php echo url_encode ($hcms_assetbrowser_location); ?>" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; <?php if (!$is_iphone) echo "overflow:auto;"; ?>" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
      <?php } else { ?>
      <iframe id="workplFrame" name="workplFrame" src="empty.php" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; <?php if (!$is_iphone) echo "overflow:auto;"; ?>" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
      <?php } ?>
    <?php } else { ?>
    <iframe id="workplFrame" name="workplFrame" src="home.php" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; <?php if (!$is_iphone) echo "overflow:auto;"; ?>" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
    <?php } ?>
  </div>
  
  <!-- chat panel -->
  <?php if (empty ($hcms_assetbrowser) && !empty ($mgmt_config['chat'])) { ?>
  <div id="chatLayer" class="hcmsChatBar" style="position:fixed; top:36px; right:-320px; bottom:0; width:300px; z-index:100; overflow:hidden;">
    <iframe id="chatFrame" src="chat.php" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:hidden;"></iframe>
  </div>  
  <?php } ?>

  <!-- popups -->
  <div id="popupsLayer" style="position:fixed; bottom:0; right:0; max-width:100%; max-height:36px; margin:0; padding:0; z-index:10;"></div>

</body>
</html>