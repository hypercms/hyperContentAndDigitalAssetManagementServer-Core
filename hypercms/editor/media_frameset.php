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
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");


// input parameters
$site = url_encode (getrequest ("site", "url"));
$mediatype = url_encode (getrequest ("mediatype", "url"));
$langCode = url_encode (getrequest ("langCode", "url"));
$CKEditorFuncNum = url_encode (getrequest ("CKEditorFuncNum", "url"));
$scaling = url_encode (getrequest ("scaling", "numeric", "1"));

// check session of user
checkusersession ($user);
?>
<!DOCTYPE HTML>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#464646" />
<meta name="viewport" content="width=800; initial-scale=1.0; user-scalable=1;" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script language="JavaScript">
function minNavFrame ()
{
  if (document.getElementById('navFrame2'))
  {
    var width = 42;
    
    document.getElementById('navLayer').style.width = width + 'px';
    document.getElementById('mainLayer').style.left = width + 'px';
  }
}

function maxNavFrame ()
{
  if (document.getElementById('navFrame2'))
  {
    var width = 250;
    
    document.getElementById('navLayer').style.width = width + 'px';
    document.getElementById('mainLayer').style.left = width + 'px';
  }
}
</script>
</head>

<body style="width:100%; height:100%; margin:0; padding:0;">
  <div id="navLayer" style="position:fixed; top:0; bottom:0; left:0; width:250px; margin:0; padding:0;">
    <iframe id="navFrame2" name="navFrame2" scrolling="auto" src="<?php echo "media_explorer.php?site=".$site."&cat=comp&compcat=media&mediatype=".$mediatype."&lang=".$langCode."&callback=".$CKEditorFuncNum."&scaling=".$scaling; ?>" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
  </div>
  <div id="mainLayer" style="position:fixed; top:0; right:0; bottom:0; left:250px; margin:0; padding:0;">
    <iframe id="mainFrame2" name="mainFrame2" scrolling="auto" src="<?php echo "media_select.php?site=".$site."&lang=".$langCode."&mediatype=".$mediatype."&callback=".$CKEditorFuncNum."&scaling=".$scaling; ?>" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
  </div>
</body>
</html>