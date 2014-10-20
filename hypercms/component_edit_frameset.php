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
$view = url_encode (getrequest ("view", "url"));
$site = url_encode (getrequest ("site", "url"));
$cat = url_encode (getrequest ("cat", "url"));
$compcat = url_encode (getrequest ("compcat", "url"));
$location = url_encode (getrequest ("location", "url"));
$page = url_encode (getrequest ("page", "url"));
$id = url_encode (getrequest ("id", "url"));
$tagname = url_encode (getrequest ("tagname", "url"));
$component_curr = url_encode (getrequest ("component_curr", "url"));
$component = url_encode (getrequest ("component", "url"));
$condition = url_encode (getrequest ("condition", "url"));

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
  <?php
  echo "<frame name=\"navFrame2\" src=\"component_edit_explorer.php?site=".$site."&cat=".$cat."&compcat=".$compcat."&location=".$location."&page=".$page."&mediatype=comp\">";

  if ($compcat == "single")
  {
    echo "<frame name=\"mainFrame2\" src=\"component_edit_page_single.php?view=".$view."&site=".$site."&cat=".$cat."&location=".$location."&page=".$page."&id=".$id."&tagname=".$tagname."&compcat=".$compcat."&component_curr=".$component_curr."&component=".$component."&condition=".$condition."\">";
  }
  elseif ($compcat == "multi")
  {
    echo "<frame name=\"mainFrame2\" src=\"component_edit_page_multi.php?view=".$view."&site=".$site."&cat=".$cat."&location=".$location."&page=".$page."&id=".$id."&tagname=".$tagname."&compcat=".$compcat."&condition=".$condition."\">";
  }
  ?>
</frameset>
<noframes></noframes>

</html>