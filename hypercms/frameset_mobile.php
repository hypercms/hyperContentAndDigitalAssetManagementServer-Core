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
<meta name="viewport" content="width=device-width; initial-scale=1.0; user-scalable=0;"></meta>
<link rel="stylesheet" href="javascript/jquery-ui/jquery.mobile-1.3.1.min.css" />
<!-- 57 x 57 Android and iPhone 3 icon -->
<link rel="apple-touch-icon" media="screen and (resolution: 163dpi)" href="<?php echo getthemelocation(); ?>img/mobile_icon57.png" />
<!-- 114 x 114 iPhone 4 icon -->
<link rel="apple-touch-icon" media="screen and (resolution: 326dpi)" href="<?php echo getthemelocation(); ?>img/mobile_icon114.png" />
<!-- 57 x 57 Nokia icon -->
<link rel="shortcut icon" href="<?php echo getthemelocation(); ?>img/mobile_icon57.png" />
<script type="text/javascript" src="javascript/jquery/jquery-1.10.2.min.js"></script>
<script type="text/javascript" src="javascript/jquery/jquery.mobile-1.3.1.min.js"></script>
</head> 

<script type='text/javascript'>
<!--
$(document).ready(function()
{
  $("#workplFrame").height($(window).height() - $("#topbar").height());
  $("#navContainer").height($(window).height() - 48);
  $("#navFrame").height($(window).height() - 48);
  if ($("#chatContainer")) $("#chatContainer").height($(window).height());
  if ($("#chatFrame")) $("#chatFrame").height($(window).height());
  
  window.onresize = function()
  {
    // repetition
    $("#workplFrame").height($(window).height() - $("#topbar").height());
    $("#navContainer").height($(window).height() - 48);
    $("#navFrame").height($(window).height() - 48);
    if ($("#chatContainer")) $("#chatContainer").height($(window).height());
    if ($("#chatFrame")) $("#chatFrame").height($(window).height());
  };
});
-->
</script>

<body>

<div data-role="page" id="mainframe" data-fullscreen="true">

  <!-- header -->
  <div id="topbar" class="ui-header ui-bar-b" data-role="header">
    <a href="#navigator">Navigator</a>
    <h1>hyperCMS <?php echo ucfirst ($hcms_themename); ?></h1>
    <?php if (isset ($mgmt_config['chat']) && $mgmt_config['chat'] == true && !$is_iphone) { ?><a href="#chat"><?php echo getescapedtext ($hcms_lang['chat'][$lang]); ?></a><?php } ?>
  </div> 

  <!-- navigator panel -->
  <div id="navigator" data-role="panel" data-position="left" data-display="overlay" style="padding:0; margin:0; width:260px;">
    <?php if ($mgmt_config['db_connect_rdbms'] != "") { ?>
    <div class="ui-bar-b" style="position:absolute; top:0; left:0; width:258px;">
      <form name="searchform_general" method="post" action="frameset_objectlist.php" target="workplFrame" style="margin:0; padding:0; border:0;">
        <input type="hidden" name="action" value="base_search" />
        <input type="hidden" name="search_dir" value="" />
        <input type="hidden" name="maxhits" value="100" />
        <table style="padding:0; margin:0; width:100%;">
          <tr>
            <td>
              <input type="text" name="search_expression" data-mini="true" maxlength="60" value="" placeholder="<?php echo getescapedtext ($hcms_lang['search-expression'][$lang]); ?>" />
            </td>
            <td>
              <button id="SearchButton" data-mini="true" style="width:40px;" onclick="if (document.forms['searchform_general'].elements['search_expression'].value!='') document.forms['searchform_general'].submit();">OK</button>
            </td>
          </tr>
        </table>
      </form>
    </div>
    <?php } ?>
    <div id="navContainer" style="position:absolute; top:0; left:0; padding:0; margin:48px 0px 0px 0px; border:0; width:260px; overflow:auto; -webkit-overflow-scrolling:touch;">
      <iframe id="navFrame" src="explorer.php" frameBorder="0" style="border:0; width:260px;"></iframe>
    </div>
  </div>
  
  <!-- content -->
  <div id="content" data-role="content" style="padding:0; margin:0;">
    <?php if (is_array ($hcms_linking)) { ?>
    <iframe id="workplFrame" name="workplFrame" src="frameset_objectlist.php" frameBorder="0" style="padding:0; margin:0; border:0; width:100%; overflow:auto;"></iframe>
    <?php } else { ?>
    <iframe id="workplFrame" name="workplFrame" src="home.php" frameBorder="0" style="padding:0; margin:0; border:0; width:100%; overflow:auto;"></iframe>
    <?php } ?>
  </div>
  
  <!-- chat panel -->
  <?php if (isset ($mgmt_config['chat']) && $mgmt_config['chat'] == true && !$is_iphone) { ?>
  <div id="chat" data-role="panel" data-display="overlay" data-position="right" style="padding:0; margin:0; width:300px;">    
    <div id="chatContainer" style="position:absolute; top:0; right:0; padding:0; margin:0; border:0; width:300px; overflow:auto; -webkit-overflow-scrolling:touch;">
      <iframe id="chatFrame" scrolling="yes" src="chat.php" frameBorder="0" style="border:0; width:300px;"></iframe>
    </div>
  </div>  
  <?php } ?>

</div>

</body>
</html>