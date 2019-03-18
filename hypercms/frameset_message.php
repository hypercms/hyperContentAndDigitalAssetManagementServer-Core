<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");


// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=<?php echo windowwidth ("object"); ?>, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
function openpopup (link)
{
  if (link != "")
  {
    document.getElementById('objectview').src = link;
    hcms_showInfo('objectviewLayer',0);
  }
}

function closepopup ()
{
  document.getElementById('objectview').src = '';
  hcms_hideInfo('objectviewLayer');
}
</script>
</head>

<body>

  <!-- popup for preview/live-view and forms --> 
  <div id="objectviewLayer" class="hcmsWorkplaceExplorer" style="display:none; overflow:hidden; position:fixed; margin:0; padding:0; left:0; top:0; right:0; bottom:0; z-index:8;">
    <div style="position:fixed; right:5px; top:5px; z-index:9;">
      <img name="hcms_mediaClose" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="closepopup();" />
    </div>
    <iframe id="objectview" name="objectview" src="" frameBorder="0" <?php if (!$is_mobile) echo 'scrolling="auto"'; else echo 'scrolling="yes"'; ?> <?php if (!$is_iphone) echo 'style="width:100%; height:100%; border:0; margin:0; padding:0;"'; ?> sandbox="allow-same-origin allow-scripts allow-forms" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
  </div>

  <!-- frames -->
  <iframe id="controlFrame" name="controlFrame" scrolling="no" src="control_message_menu.php" frameBorder="0" style="position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0;"></iframe>
  <div style="position:fixed; top:100px; right:0; bottom:0; left:0; margin:0; padding:0;">
    <iframe id="mainFrame" name="mainFrame" scrolling="no" src="message_objectlist.php" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
  </div>
  
</body>
</html>