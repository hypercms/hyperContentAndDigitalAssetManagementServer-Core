<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("include/session.inc.php");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");


// input parameters
$location = url_encode (getrequest ("location", "url"));
$page = url_encode (getrequest ("page", "url"));
$ctrlreload = url_encode (getrequest ("ctrlreload", "url"));

// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<meta name="viewport" content="width=600; initial-scale=0.9; maximum-scale=1; user-scalable=1;">
</head>
<frameset id="contentFrame" rows="100,*" frameborder="NO" border="0" framespacing="0">
  <?php
  // open an object 
  if (isset ($page) && $page != "")
  {
    echo "<frame name=\"controlFrame\" scrolling=\"NO\" src=\"loading.php\" noresize />\n";
    echo "<frame name=\"objFrame\" src=\"page_view.php?ctrlreload=".$ctrlreload."&location=".$location."&page=".$page."\" />\n";
  }
  // explorer/navigator  
  elseif (isset ($location) && $location != "")
  {
    echo "<frame name=\"controlFrame\" scrolling=\"NO\" src=\"control_content_menu.php?location=".$location."\" noresize />\n";
    echo "<frame name=\"objFrame\" src=\"empty.php\" />\n";
  }
  ?>
</frameset>
<noframes></noframes>
</html>