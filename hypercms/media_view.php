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
// hyperCMS UI
require ("function/hypercms_ui.inc.php");
// formats/file extensions
require_once ("include/format_ext.inc.php");


// input parameters
$site = getrequest ("site", "publicationname");
$mediaobject = getrequest ("mediaobject", "locationname");
$mediafile = getrequest ("mediafile", "objectname");
$mediacat = getrequest ("mediacat", "objectname");
$scaling = getrequest ("scaling", "numeric");
$mediatype = getrequest ("mediatype", "objectname");
$save = getrequest ("save");
$mediafile = getrequest ("mediafile", "objectname");
$content = getrequest ("content");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'component') && !checkglobalpermission ($site, 'tplmedia') || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

$show = "";

// save content to file
if ($save == "yes" && valid_objectname ($mediafile) && checktoken ($token, $user) && checkglobalpermission ($site, 'tpledit'))
{
  // decode characters
  $content = html_decode ($content, $hcms_lang_codepage[$lang]);
  // save file
  $savefile = savefile ($mgmt_config['abs_path_rep']."media_tpl/", $mediafile, $content);

  if ($savefile == false) $show = "<span class=hcmsHeadline>".$hcms_lang['the-data-could-not-be-saved'][$lang]."</span>\n";
  else $show = "<span class=hcmsHeadline>".$hcms_lang['the-data-was-saved-successfully'][$lang]."</span>";
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<?php
echo showmessage ($show, 600, 70, $lang, "position:fixed; left:15px; top:100px;")
?>

<?php
// read media file
if ($mediaobject != "")
{
  $site = getpublication ($mediaobject);
  $location = getlocation ($mediaobject);
  $object = getobject ($mediaobject);
  $object_info = getfileinfo ($site, $object, "comp");
  
  // load object file
  if ($mediafile == "")
  {
    $pagedata = loadfile (deconvertpath ($location, "file"), $object);  
    
    if ($pagedata != false) 
    {
      // get media and template file name
      $mediafile = getfilename ($pagedata, "media");
      
      if ($mediafile != false) $mediafile = $site."/".$mediafile;
    }
  }
}

if (substr_count ($mediafile, "Null_media.gif") == 1)
{
  echo "<p class=hcmsHeadline>".$hcms_lang['no-file-selected'][$lang]."</p>";
}
elseif ($mediafile != "")
{
  if ($mediacat == "tpl" && checkglobalpermission ($site, 'tpledit')) $view = "template";
  else $view = "preview_no_rendering";
  
  if (!isset ($object_info['name'])) $object_info['name'] = getobject ($mediafile);

  echo showmedia ($mediafile, $object_info['name'], $view);
}

// retrieving image metrics to update the height and width field
if ($mediatype == "image") 
{
	// scalingfactor is 1 if not given
	if ($scaling == "") $scaling = 1;
	// initialize mediaheight and mediawidth
	$mediawidth = "";
	$mediaheight = "";
	// get file information
	$media_path = getmedialocation ($site, $mediafile, "abs_path_media").$mediafile;
	$media_size = @getimagesize ($media_path);

	if (!empty ($media_size[3]))
  {
		// scaling images to reach given dpi 
		$mediawidth = round ($media_size[0] * $scaling);
		$mediaheight = round ($media_size[1] * $scaling);
	}
?>
	<script type="text/javascript">
  <!--
		parent.frames['controlFrame2'].document.forms['media'].elements['mediawidth'].value = '<?php echo $mediawidth; ?>';
		parent.frames['controlFrame2'].document.forms['media'].elements['mediaheight'].value = '<?php echo $mediaheight; ?>';
  -->
	</script>
<?php		
}

?>

</div>

</body>
</html>