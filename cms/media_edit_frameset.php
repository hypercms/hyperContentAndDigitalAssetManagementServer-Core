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
$action = url_encode (getrequest ("action", "url"));
$view = url_encode (getrequest ("view", "url"));
$savetype = url_encode (getrequest ("savetype", "url"));
$site = url_encode (getrequest ("site", "url"));
$cat = url_encode (getrequest ("cat", "url"));
$location = url_encode (getrequest ("location", "url"));
$page = url_encode (getrequest ("page", "url"));
$db_connect = url_encode (getrequest ("db_connect", "url"));
$contenttype = url_encode (getrequest ("contenttype", "url"));
$tagname = url_encode (getrequest ("tagname", "url"));
$id = url_encode (getrequest ("id", "url"));
$label = url_encode (getrequest ("label", "url"));
$mediacat = url_encode (getrequest ("mediacat", "url"));
$mediadir = url_encode (getrequest ("mediadir", "url"));
$mediatype = url_encode (getrequest ("mediatype", "url")); 
$mediafile = url_encode (getrequest ("mediafile", "url"));
$mediaobject_curr = url_encode (getrequest ("mediaobject_curr", "url"));
$mediaobject = url_encode (getrequest ("mediaobject", "url"));
$mediaalttext = url_encode (getrequest ("mediaalttext", "url"));
$mediaalign = url_encode (getrequest ("mediaalign", "url"));
$mediawidth = url_encode (getrequest ("mediawidth", "url"));
$mediaheight = url_encode (getrequest ("mediaheight", "url"));
$scaling = url_encode(getrequest ("scaling", "numeric"));


// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
</head>

<frameset id="assetFrame" cols="250,*" framespacing="2" frameborder="no" scrolling="no" border="0" class="hcmsNavigator">
  <?php
  if ($mediacat != "comp") echo "<frame id=\"navFrame2\" name=\"navFrame2\" src=\"media_edit_explorer.php?site=".$site."&mediacat=".$mediacat."&mediatype=".$mediatype."\" />";
  elseif ($mediacat == "comp") echo "<frame id=\"navFrame2\" name=\"navFrame2\" src=\"component_edit_explorer.php?site=".$site."&cat=".$cat."&location=".$location."&page=".$page."&mediatype=".$mediatype."&scaling=".$scaling."&compcat=media\" />";
  ?>
  <frameset rows="180,*" frameborder="no" border="0" framespacing="0">
    <?php
    if ($action == "mediafile_delete")
    {
      echo "<frame name=\"controlFrame2\" noresize scrolling=\"NO\" src=\"media_delete.php?site=".$site."&mediacat=".$mediacat."\" />";
    }
    elseif ($action == "mediafile_preview")
    {
      echo "<frame name=\"controlFrame2\" noresize scrolling=\"NO\" src=\"media_preview.php?site=".$site."&cat=".$cat."\" />";
    }
    else
    {
      echo "<frame name=\"controlFrame2\" noresize scrolling=\"NO\" src=\"media_edit_page.php?view=".$view."&savetype=".$savetype."&site=".$site."&cat=".$cat."&location=".$location."&page=".$page."&db_connect=".$db_connect."&id=".$id."&label=".$label."&tagname=".$tagname."&mediafile=".$mediafile."&mediaobject_curr=".$mediaobject."&mediaobject=".$mediaobject."&mediaalttext=".$mediaalttext."&mediaalign=".$mediaalign."&mediawidth=".$mediawidth."&mediaheight=".$mediaheight."&scaling=".$scaling."&mediatype=".$mediatype."&contenttype=".$contenttype."\" />";
    }
    ?>
    <frame name="mainFrame2" noresize scrolling="yes" src="<?php echo "media_view.php?site=".$site."&mediacat=".$mediacat."&mediafile=".$mediafile."&mediaobject=".$mediaobject."&mediatype=".$mediatype."&scaling=".$scaling; ?>" />
  </frameset>
</frameset>
<noframes></noframes>

</html>