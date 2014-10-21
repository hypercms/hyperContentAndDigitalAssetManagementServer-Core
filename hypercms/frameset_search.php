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

// check session of user
checkusersession ($user, false);
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<script type="text/javascript">
<!--
function toggleview (view)
{
  return true;
}
//-->
</script>
</head>
<frameset id="searchFrame" cols="240,*" frameborder="no" framespacing="0" border="0" class="hcmsNavigator">
  <?php
  echo "<frame name=\"navFrame2\" scrolling=\"auto\" src=\"search_explorer.php?dir=".$location."\">\n";
  echo "<frame name=\"mainFrame2\" scrolling=\"auto\" src=\"search_form_rdbms.php?location=".$location."\">\n";
  ?>
</frameset>
<noframes></noframes>
</html>
