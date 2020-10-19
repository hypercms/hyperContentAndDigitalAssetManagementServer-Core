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
?>
<!DOCTYPE html> 
<html> 
<head> 
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0"></meta>
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?ts=<?php echo time(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?ts=<?php echo time(); ?>" />
<!-- 57 x 57 Android and iPhone 3 icon -->
<link rel="apple-touch-icon" media="screen and (resolution: 163dpi)" href="<?php echo getthemelocation(); ?>img/mobile_icon57.png" />
<!-- 114 x 114 iPhone 4 icon -->
<link rel="apple-touch-icon" media="screen and (resolution: 326dpi)" href="<?php echo getthemelocation(); ?>img/mobile_icon114.png" />
<!-- 57 x 57 Nokia icon -->
<link rel="shortcut icon" href="<?php echo getthemelocation(); ?>img/mobile_icon57.png" />
<!-- main library -->
<script type="text/javascript" src="javascript/main.min.js"></script>
<!-- JQuery used for AJAX viewport set request -->
<script src="javascript/jquery/jquery-3.5.1.min.js" type="text/javascript"></script>
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

function openChat ()
{
  // standard browser (open/close chat)
  if (document.getElementById('chatLayer'))
  {
    var chatsidebar = document.getElementById('chatLayer');
  }
  else if (parent.document.getElementById('chatLayer'))
  {
    var chatsidebar = parent.document.getElementById('chatLayer');
  }
  else var chatsidebar = false;

  if (chatsidebar)
  {
    chatsidebar.style.transition = "0.3s";
    if (chatsidebar.style.right == "0px") chatsidebar.style.right = "-320px";
    else chatsidebar.style.right = "0px";
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

<body onload="<?php if (getsession ('hcms_temp_latitude') == "" || getsession ('hcms_temp_longitude') == "") echo "hcms_geolocation(); "; ?>">

  <!-- header -->
  <div class="hcmsWorkplaceTop" style="position:fixed; left:0; top:0; width:100%; height:36px;">
    <div style="float:left; width:72px; text-align:left;"> 
    <?php if (linking_valid() == false) { ?>
      <img src="<?php echo getthemelocation(); ?>img/button_explorer.png" onclick="switchNav();" class="hcmsButtonTiny hcmsButtonSizeSquare" style="float:left; padding:2px;" alt="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" />
    <?php } else { ?>
      <a href="frameset_objectlist.php?action=linking" id="navigateButton" target="workplFrame" onclick="this.style.display='none'; document.getElementById('tasksButton').style.display='inline';" style="display:none;">
        <img src="<?php echo getthemelocation(); ?>img/button_view_gallery_medium.png" class="hcmsButtonTiny hcmsButtonSizeSquare" style="float:left; padding:2px;" alt="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" />
      </a>
      <a href="task/task_list.php" id="tasksButton" target="workplFrame" onclick="document.getElementById('navigateButton').style.display='inline'; this.style.display='none';">
        <img src="<?php echo getthemelocation(); ?>img/task.png" class="hcmsButtonTiny hcmsButtonSizeSquare" style="float:left; padding:2px;" alt="<?php echo getescapedtext ($hcms_lang['task-management'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['task-management'][$lang]); ?>" />
      </a>
    <?php } ?>
    <?php if (empty ($hcms_assetbrowser)) { ?>
      <img src="<?php echo getthemelocation(); ?>img/button_logout.png" onclick="top.location='userlogout.php';" class="hcmsButtonTiny hcmsButtonSizeSquare" style="float:left; padding:2px;" alt="<?php echo getescapedtext ($hcms_lang['logout'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['logout'][$lang]); ?>" />
    <?php } ?>
    </div>
    <div style="float:left; width:calc(100% - 144px); text-align:center;"><div class="hcmsHeadline" style="padding:8px;"></div></div>
    <div style="float:left; width:36px; text-align:right; margin-left:35px;"> 
    <?php if (empty ($hcms_assetbrowser) && !empty ($mgmt_config['chat'])) { ?>
      <img src="<?php echo getthemelocation(); ?>img/button_chat.png" onclick="openChat();" class="hcmsButtonTiny hcmsButtonSizeSquare" style="float:left; padding:2px;" alt="<?php echo getescapedtext ($hcms_lang['chat'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['chat'][$lang]); ?>" />
    <?php } ?>
    </div>
  </div> 

  <?php if (linking_valid() == false) { ?>
  <!-- navigator panel -->
  <div id="navLayer" class="hcmsWorkplaceExplorer" style="position:fixed; top:36px; bottom:0; left:-280px; width:260px; margin:0; padding:0; z-index:50;">
    <div style="width:100%; height:36px;">
      <form name="searchform_general" method="post" action="frameset_objectlist.php" target="workplFrame" style="margin:0; padding:0; border:0;">
        <input type="hidden" name="action" value="base_search" />
        <input type="hidden" name="search_dir" value="" />
        <input type="text" name="search_expression" <?php if (empty ($mgmt_config['db_connect_rdbms'])) echo "readonly=\"readonly\""; ?> style="margin:3px 0px 3px 3px; padding:3px; width:215px;" maxlength="200" value="" placeholder="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" />
        <img src="<?php echo getthemelocation(); ?>img/button_search.png" <?php if (!empty ($mgmt_config['db_connect_rdbms']) && linking_valid() == false) echo "onclick=\"if (document.forms['searchform_general'].elements['search_expression'].value!='') document.forms['searchform_general'].submit();\""; ?> class="hcmsButtonTiny hcmsButtonSizeSquare" style="padding:2px;" alt="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" />
      </form>
    </div>
    <div id="navContainer" style="width:100%; height:calc(100% - 36px); margin:0; padding:0; overflow:auto; -webkit-overflow-scrolling:touch;">
      <iframe id="navFrame" src="explorer.php" frameborder="0" style="margin:0; padding:0; border:0; width:100%; height:100%;"></iframe>
    </div>
  </div>
  <?php } ?>
  
  <!-- content -->
  <div id="workplLayer" style="position:fixed; top:36px; bottom:0; left:0; right:0; margin:0; padding:0;">
    <?php if (linking_valid() == true) { ?>
    <iframe id="workplFrame" name="workplFrame" src="frameset_objectlist.php?action=linking" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
    <?php } elseif (!empty ($hcms_assetbrowser)) { ?>
      <?php
      // location set by assetbrowser
      if (!empty ($hcms_assetbrowser_location)) { ?>
      <iframe id="workplFrame" name="workplFrame" src="frameset_objectlist.php?location=<?php echo url_encode ($hcms_assetbrowser_location); ?>" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
      <?php } else { ?>
      <iframe id="workplFrame" name="workplFrame" src="empty.php" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
      <?php } ?>
    <?php } else { ?>
    <iframe id="workplFrame" name="workplFrame" src="home.php" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
    <?php } ?>
  </div>
  
  <!-- chat panel -->
  <?php if (empty ($hcms_assetbrowser) && !empty ($mgmt_config['chat'])) { ?>
  <div id="chatLayer" class="hcmsChatBar" style="position:fixed; top:36px; right:-320px; bottom:0; width:300px; z-index:100; overflow:auto; -webkit-overflow-scrolling:touch;">
    <iframe id="chatFrame" src="chat.php" frameborder="0" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;"></iframe>
  </div>  
  <?php } ?>

</body>
</html>