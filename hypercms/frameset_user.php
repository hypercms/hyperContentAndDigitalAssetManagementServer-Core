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
</head>

<body style="width:100%; height:100%; margin:0; padding:0;">
  <iframe id="controlFrame" name="controlFrame" scrolling="no" src="control_user_menu.php?site=<?php echo url_encode ($site); ?>&group=*all*" frameBorder="0" style="position:fixed; top:0; left:0; width:100%; height:100px; border:0; margin:0; padding:0;"></iframe>
  <div style="position:fixed; top:100px; right:0; bottom:0; left:0; margin:0; padding:0;">
    <iframe id="mainFrame" name="mainFrame" scrolling="no" src="user_objectlist.php?site=<?php echo url_encode ($site); if (valid_publicationname ($site)) echo "&group=*all*"; ?>" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
  </div>
</body>
</html>