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
<!-- 57 x 57 Android and iPhone 3 icon -->
<link rel="apple-touch-icon" media="screen and (resolution: 163dpi)" href="<?php echo getthemelocation(); ?>img/mobile_icon57.png" />
<!-- 114 x 114 iPhone 4 icon -->
<link rel="apple-touch-icon" media="screen and (resolution: 326dpi)" href="<?php echo getthemelocation(); ?>img/mobile_icon114.png" />
<!-- 57 x 57 Nokia icon -->
<link rel="shortcut icon" href="<?php echo getthemelocation(); ?>img/mobile_icon57.png" />

 <!-- main library -->
<script type="text/javascript" src="javascript/main.js"></script>

<!-- JQuery -->
<script type="text/javascript" src="javascript/jquery/jquery-1.12.4.min.js"></script>

<!-- JQuery Mobile -->
<link rel="stylesheet" href="javascript/jquery-mobile-theme/mobile.min.css" />
<link rel="stylesheet" href="javascript/jquery-mobile-theme/jquery.mobile.icons.min.css" />
<link rel="stylesheet" href="javascript/jquery-mobile/jquery.mobile.structure-1.4.5.min.css" />
<script type="text/javascript" src="javascript/jquery-mobile/jquery.mobile-1.4.5.min.js"></script>
</head> 

<script type="text/javascript">
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

function minNavFrame ()
{
  return true;
}

function maxNavFrame ()
{
  return true;
}

$(document).ready(function()
{
  $("#workplFrame").height($(window).height() - $("#topbar").height());
  $("#navContainer").height($(window).height() - 48);
  $("#navFrame").height($(window).height() - 48);
  if ($("#chatContainer")) $("#chatContainer").height($(window).height());
  if ($("#chatFrame")) $("#chatFrame").height($(window).height());
  setviewport();
  
  window.onresize = function()
  {
    // repetition
    $("#workplFrame").height($(window).height() - $("#topbar").height());
    $("#navContainer").height($(window).height() - 48);
    $("#navFrame").height($(window).height() - 48);
    if ($("#chatContainer")) $("#chatContainer").height($(window).height());
    if ($("#chatFrame")) $("#chatFrame").height($(window).height());
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

<body>

<div data-role="page" id="mainframe" data-fullscreen="true">

  <!-- header -->
  <div id="topbar" class="ui-header ui-bar-a" data-role="header">
    <div data-type="horizontal" data-role="controlgroup" class="ui-btn-left"> 
      <?php if (linking_valid() == false) { ?>
      <a href="#navigator" data-role="button" style="padding:2px; line-height:1; border:0;"><img src="<?php echo getthemelocation(); ?>img/button_explorer.png" style="widht:30px; height:30px;" alt="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" /></a>
      <?php } else { ?>
      <a href="frameset_objectlist.php?action=linking" data-role="button" id="navigate" target="workplFrame" style="display:none; padding:2px; line-height:1; border:0;" onclick="$('#navigate').hide(); $('#tasks').show();"><img src="<?php echo getthemelocation(); ?>img/button_view_gallery_medium.png" style="widht:30px; height:30px;" alt="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?>" /></a>
      <a href="task/task_list.php" data-role="button" id="tasks" target="workplFrame" style="padding:2px; line-height:1; border:0;" onclick="$('#tasks').hide(); $('#navigate').show();"><img src="<?php echo getthemelocation(); ?>img/task.png" style="widht:30px; height:30px;" alt="<?php echo getescapedtext ($hcms_lang['task-management'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['task-management'][$lang]); ?>" /></a>
      <?php } ?>
      <a href="userlogout.php" data-ajax="false" data-role="button" style="padding:2px; line-height:1; border:0;"><img src="<?php echo getthemelocation(); ?>img/button_logout.png" style="widht:30px; height:30px;" alt="<?php echo getescapedtext ($hcms_lang['logout'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['logout'][$lang]); ?>" /></a>
    </div>
    <h1><?php echo ucfirst ($hcms_themename); ?></h1>
    <div data-type="horizontal" data-role="controlgroup" class="ui-btn-right"> 
      <?php if (empty ($hcms_assetbrowser) && !empty ($mgmt_config['chat']) && !$is_iphone) { ?>
      <a href="#chat" data-role="button" style="padding:2px; line-height:1; border:0;"><img src="<?php echo getthemelocation(); ?>img/button_chat.png" style="widht:30px; height:30px;" alt="<?php echo getescapedtext ($hcms_lang['chat'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['chat'][$lang]); ?>" /></a>
      <?php } ?>
    </div>
  </div> 

  <?php if (linking_valid() == false) { ?>
  <!-- navigator panel -->
  <div id="navigator" data-role="panel" data-position="left" data-display="overlay" style="padding:0; margin:0; width:260px;">
    <div class="ui-bar-a" style="position:absolute; top:0; left:0; width:258px;">
      <form name="searchform_general" method="post" action="frameset_objectlist.php" target="workplFrame" style="margin:0; padding:0; border:0;">
        <input type="hidden" name="action" value="base_search" />
        <input type="hidden" name="search_dir" value="" />
        <input type="hidden" name="maxhits" value="100" />
        <table class="hcmsTableNarrow" style="width:100%;">
          <tr>
            <td>
              <input type="text" name="search_expression" <?php if (empty ($mgmt_config['db_connect_rdbms']) || linking_valid() == true) echo "readonly=\"readonly\""; ?> data-mini="true" maxlength="200" value="" placeholder="<?php echo getescapedtext ($hcms_lang['search-expression'][$lang]); ?>" />
            </td>
            <td>
              <button id="SearchButton" data-mini="true" style="width:34px; padding:2px; line-height:1; border:0;" <?php if (!empty ($mgmt_config['db_connect_rdbms']) && linking_valid() == false) echo "onclick=\"if (document.forms['searchform_general'].elements['search_expression'].value!='') document.forms['searchform_general'].submit();\""; else echo "disabled=\"disabled\""; ?>>
<img src="<?php echo getthemelocation(); ?>img/button_search.png" style="width:26px; height:26px;" alt="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" />
              </button>
            </td>
          </tr>
        </table>
      </form>
    </div>
    <div id="navContainer" style="position:absolute; top:0; left:0; padding:0; margin:50px 0px 0px 0px; border:0; width:260px; overflow:auto; -webkit-overflow-scrolling:touch;">
      <iframe id="navFrame" src="explorer.php" frameBorder="0" style="border:0; width:260px;"></iframe>
    </div>
  </div>
  <?php } ?>
  
  <!-- content -->
  <div id="content" data-role="content" style="padding:0; margin:0;">
    <?php if (linking_valid() == true) { ?>
    <iframe id="workplFrame" name="workplFrame" src="frameset_objectlist.php?action=linking" frameBorder="0" style="padding:0; margin:0; border:0; width:100%; overflow:auto;"></iframe>
    <?php } elseif (!empty ($hcms_assetbrowser)) { ?>
      <?php // location set by assetbrowser
      if (!empty ($hcms_assetbrowser_location)) { ?>
      <iframe id="workplFrame" name="workplFrame" src="frameset_objectlist.php?location=<?php echo url_encode ($hcms_assetbrowser_location); ?>" frameBorder="0" style="padding:0; margin:0; border:0; width:100%; overflow:auto;"></iframe>
      <?php } else { ?>
      <iframe id="workplFrame" name="workplFrame" src="empty.php" frameBorder="0" style="padding:0; margin:0; border:0; width:100%; overflow:auto;"></iframe>
      <?php } ?>
    <?php } else { ?>
    <iframe id="workplFrame" name="workplFrame" src="home.php" frameBorder="0" style="padding:0; margin:0; border:0; width:100%; overflow:auto;"></iframe>
    <?php } ?>
  </div>
  
  <!-- chat panel -->
  <?php if (empty ($hcms_assetbrowser) && !empty ($mgmt_config['chat']) && !$is_iphone) { ?>
  <div id="chat" data-role="panel" data-display="overlay" data-position="right" style="z-index:99999; padding:0; margin:0; width:300px;">    
    <div id="chatContainer" style="position:absolute; top:0; right:0; padding:0; margin:0; border:0; width:300px; overflow:auto; -webkit-overflow-scrolling:touch;">
      <iframe id="chatFrame" scrolling="yes" src="chat.php" frameBorder="0" style="border:0; width:300px;"></iframe>
    </div>
  </div>  
  <?php } ?>

</div>

</body>
</html>