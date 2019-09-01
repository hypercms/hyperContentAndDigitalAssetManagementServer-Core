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
$site = getrequest ("site", "url"); // site can be *Null* which is not a valid name!

// check session of user
checkusersession ($user, false);

// save selected publication in session
if (valid_publicationname ($site)) setsession ('hcms_temp_site', $site);
else setsession ('hcms_temp_site', Null);
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

<body style="width:100%; height:100%; margin:0; padding:0;">

<!-- popup for preview/live-view (do not used nested fixed positioned div-layers due to MS IE and Edge issue) --> 
<div id="objectviewLayer" style="display:none;">
  <div style="position:fixed; right:4px; top:4px; z-index:9001;">
    <img name="hcms_mediaClose" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="closepopup();" />
  </div>
  <div class="hcmsWorkplaceExplorer" style="overflow:hidden; position:fixed; margin:0; padding:0; left:0; top:0; right:0; bottom:0; z-index:9000;">
    <iframe id="objectview" name="objectview" frameBorder="0" src="" <?php if (!$is_mobile) echo 'scrolling="no"'; else echo 'scrolling="yes"'; ?> <?php if (!$is_iphone) echo 'style="width:100%; height:100%; border:0; margin:0; padding:0;"'; ?> sandbox="allow-top-navigation allow-same-origin allow-scripts allow-forms" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
  </div>
</div>

  <iframe id="controlFrame" name="controlFrame" scrolling="no" src="control_user_menu.php?site=<?php echo url_encode ($site); ?>&group=*all*" frameBorder="0" style="position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0;"></iframe>
  <div style="position:fixed; top:100px; right:0; bottom:0; left:0; margin:0; padding:0;">
    <iframe id="mainFrame" name="mainFrame" scrolling="no" src="user_objectlist.php?site=<?php echo url_encode ($site); if (valid_publicationname ($site)) echo "&group=*all*"; ?>" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
  </div>
</body>
</html>