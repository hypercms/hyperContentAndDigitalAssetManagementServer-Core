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
$queueuser = url_encode (getrequest ("queueuser", "url"));

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// save selected queue user in session
if ($queueuser != "") setsession ('hcms_temp_user', $queueuser);
else setsession ('hcms_temp_user', Null);

// write and close session (non-blocking other frames)
if (session_id() != "") session_write_close();
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=<?php echo windowwidth ("object"); ?>, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript">

function openPopup (link)
{
  if (link != "")
  {
    document.getElementById('objectview').src = link;
    hcms_showFormLayer('objectviewLayer',0);
  }
}

function closePopup ()
{
  document.getElementById('objectview').src = '';
  hcms_hideFormLayer('objectviewLayer');
}
</script>
</head>

<body class="hcmsTransBackground">

<!-- popup for preview/live-view and forms (do not used nested fixed positioned div-layers due to MS IE and Edge issue) -->
<div id="objectviewLayer" style="display:none;">
  <div style="position:fixed; right:4px; top:4px; z-index:8001;">
    <img name="hcms_mediaClose" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="closePopup();" />
  </div>
  <div class="hcmsWorkplaceExplorer" style="<?php if ($is_mobile) echo '-webkit-overflow-scrolling:touch !important; overflow-y:scroll !important;'; else echo 'overflow:hidden;'; ?> position:fixed; margin:0; padding:0; left:0; top:0; right:0; bottom:0; z-index:8000;">
   <iframe id="objectview" name="objectview" src="" framevorder="0" <?php if (!$is_mobile) echo 'scrolling="auto"'; else echo 'scrolling="yes"'; ?> style="width:100%; height:100%; margin:0; padding:0; border:0; <?php if (!$is_mobile) echo "overflow:auto;"; else echo "overflow:scroll;"; ?>" sandbox="allow-same-origin allow-scripts allow-forms" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
  </div>
</div>

<!-- frames -->
<iframe id="controlFrame" name="controlFrame" src="control_queue_menu.php?queueuser=<?php echo $queueuser; ?>" framevorder="0" scrolling="no" style="position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0; overflow:hidden;"></iframe>
<div style="position:fixed; top:100px; right:0; bottom:0; left:0; margin:0; padding:0;">
  <iframe id="mainFrame" name="mainFrame" src="queue_objectlist.php?queueuser=<?php echo $queueuser; ?>" framevorder="0" scrolling="no" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:hidden;"></iframe>
</div>

</body>
</html>