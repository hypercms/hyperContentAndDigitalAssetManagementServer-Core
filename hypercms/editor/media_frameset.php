<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("../include/session.inc.php");
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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
</head>

<frameset id="assetFrame" cols="250,*" frameborder="no" framespacing="0" border="0" class="hcmsNavigator">
  <frame name="navFrame2" src="<?php echo "media_explorer.php?site=".$site."&cat=comp&compcat=media&mediatype=".$mediatype."&lang=".$langCode."&callback=".$CKEditorFuncNum."&scaling=".$scaling; ?>">
  <frame name="mainFrame2" scrolling="YES" src="<?php echo "media_select.php?site=".$site."&lang=".$langCode."&mediatype=".$mediatype."&callback=".$CKEditorFuncNum."&scaling=".$scaling; ?>">
</frameset>
<noframes></noframes>

</html>