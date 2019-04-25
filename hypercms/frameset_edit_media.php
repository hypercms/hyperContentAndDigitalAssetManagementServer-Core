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
<!DOCTYPE HTML>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=<?php echo windowwidth ("object"); ?>, initial-scale=1.0, maximum-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script>
function minNavFrame ()
{
  if (document.getElementById('navFrame2'))
  {
    var width = 26;
    
    document.getElementById('navLayer').style.width = width + 'px';
    document.getElementById('controlLayer').style.left = width + 'px';
    document.getElementById('mainLayer').style.left = width + 'px';
    window.frames['navFrame2'].document.getElementById('Navigator').style.display = 'none';
    window.frames['navFrame2'].document.getElementById('NavFrameButtons').style.left = '0px';
    window.frames['navFrame2'].document.getElementById('NavFrameButtons').style.right = '';
  }
}

function maxNavFrame ()
{
  if (document.getElementById('navFrame2'))
  {
    var width = 260;
    
    document.getElementById('navLayer').style.width = width + 'px';
    document.getElementById('controlLayer').style.left = width + 'px';
    document.getElementById('mainLayer').style.left = width + 'px';
    window.frames['navFrame2'].document.getElementById('Navigator').style.display = 'block';
    window.frames['navFrame2'].document.getElementById('NavFrameButtons').style.left = '';
    window.frames['navFrame2'].document.getElementById('NavFrameButtons').style.right = '0px';
  }
}
</script>
</head>

<body>
  <?php
  if ($mediacat != "comp") echo "<div id=\"navLayer\" style=\"position:fixed; top:0; bottom:0; left:0; width:260px; margin:0; padding:0;\"><iframe id=\"navFrame2\" name=\"navFrame2\" scrolling=\"auto\" src=\"media_edit_explorer.php?site=".$site."&mediacat=".$mediacat."&mediatype=".$mediatype."\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe>\n";
  elseif ($mediacat == "comp") echo "<div id=\"navLayer\" style=\"position:fixed; top:0; bottom:0; left:0; width:260px; margin:0; padding:0;\"><iframe id=\"navFrame2\" name=\"navFrame2\" scrolling=\"auto\" src=\"component_edit_explorer.php?site=".$site."&cat=".$cat."&location=".$location."&page=".$page."&mediatype=".$mediatype."&scaling=".$scaling."&compcat=media\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe>\n";

  if ($action == "mediafile_delete")
  {
    echo "<div id=\"controlLayer\" style=\"position:fixed; top:0; right:0; left:260px; height:220px; margin:0; padding:0;\"><iframe id=\"controlFrame2\" name=\"controlFrame2\" scrolling=\"no\" src=\"media_delete.php?site=".$site."&mediacat=".$mediacat."\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
  }
  elseif ($action == "mediafile_preview")
  {
    echo "<div id=\"controlLayer\" style=\"position:fixed; top:0; right:0; left:260px; height:220px; margin:0; padding:0;\"><iframe id=\"controlFrame2\" name=\"controlFrame2\" scrolling=\"no\" src=\"media_preview.php?site=".$site."&cat=".$cat."\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
  }
  else
  {
    echo "<div id=\"controlLayer\" style=\"position:fixed; top:0; right:0; left:260px; height:220px; margin:0; padding:0;\"><iframe id=\"controlFrame2\" name=\"controlFrame2\" scrolling=\"no\" src=\"media_edit_page.php?view=".$view."&savetype=".$savetype."&site=".$site."&cat=".$cat."&location=".$location."&page=".$page."&db_connect=".$db_connect."&id=".$id."&label=".$label."&tagname=".$tagname."&mediaalttext=".$mediaalttext."&mediaalign=".$mediaalign."&mediawidth=".$mediawidth."&mediaheight=".$mediaheight."&scaling=".$scaling."&mediatype=".$mediatype."&contenttype=".$contenttype."&mediafile=".$mediafile."&mediaobject_curr=".$mediaobject_curr."&mediaobject=".$mediaobject."\" frameBorder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0;\"></iframe></div>\n";
  }
  ?>
  <div id="mainLayer" style="position:fixed; top:220px; right:0; bottom:0; left:260px; margin:0; padding:0;">
    <iframe id="mainFrame2" name="mainFrame2" scrolling="auto" src="<?php echo "media_view.php?site=".$site."&mediacat=".$mediacat."&mediafile=".$mediafile."&mediaobject=".$mediaobject."&mediatype=".$mediatype."&scaling=".$scaling; ?>" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
  </div>
</body>
</html>