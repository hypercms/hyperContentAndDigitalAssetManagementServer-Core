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

// read media file from media object
if ($mediaobject != "")
{
  $site = getpublication ($mediaobject);
  $location = getlocation ($mediaobject);
  $object = getobject ($mediaobject);
  $file_info = getfileinfo ($site, $object, "comp");
  $object_info = getobjectinfo ($site, $location, $object, $user);
  
  // media file
  $mediafile = $site."/".$object_info['media'];
  
  // load container
  $contentdata = loadcontainer ($object_info['content'], "work", "sys");
  
  // get character set and content-type
  $charset_array = getcharset ($site, $contentdata);
  
  // set character set
  if (!empty ($charset_array['charset'])) $charset = $charset_array['charset'];
  elseif ($site != "") $charset = $mgmt_config[$site]['default_codepage'];
  else $charset = "UTF-8";
  
  $hcms_charset = $charset;
  
  // convert object name
  $name = convertchars ($file_info['name'], "UTF-8", $charset);
}
// if publication name is not included in mediafile (function showmedia provides publication and media file name separately)
elseif ($mediafile != "")
{
  // media file
  if (strpos ($mediafile, "/") < 1) $mediafile = $site."/".$mediafile;
}

$show = "";

// save content to file
if ($save == "yes" && valid_objectname ($mediafile) && checktoken ($token, $user) && checkglobalpermission ($site, 'tpledit'))
{
  // decode characters
  $content = html_decode ($content, $hcms_lang_codepage[$lang]);
  // save file
  $savefile = savefile ($mgmt_config['abs_path_rep']."media_tpl/", $mediafile, $content);

  if ($savefile == false) $show = "<span class=hcmsHeadline>".getescapedtext ($hcms_lang['the-data-could-not-be-saved'][$lang])."</span>\n";
  else $show = "<span class=hcmsHeadline>".getescapedtext ($hcms_lang['the-data-was-saved-successfully'][$lang])."</span>";
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<?php
echo showmessage ($show, 500, 70, $lang, "position:fixed; left:15px; top:50px;")
?>

<?php
// media preview
if (substr_count ($mediafile, "Null_media.png") == 1)
{
  echo "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['no-file-selected'][$lang])."</p>";
}
elseif ($mediafile != "")
{
  if ($mediacat == "tpl" && checkglobalpermission ($site, 'tpledit')) $view = "template";
  else $view = "preview_no_rendering";
  
  if (!isset ($file_info['name'])) $file_info['name'] = getobject ($mediafile);

  echo showmedia ($mediafile, $file_info['name'], $view);
}

// metadata
if (!empty ($contentdata))
{
  $metadata_array = getmetadata ("", "", $contentdata, "array", $site."/".$object_info['template']);

  if (is_array ($metadata_array))
  {
    $rows = "";
    
    foreach ($metadata_array as $key => $value)
    {
      $rows .= "<tr><td style=\"width:120px; vertical-align:top;\">".$key."&nbsp;</td><td class=\"hcmsHeadlineTiny\">".$value."</td></tr>\n";
    }
    
    if ($rows != "") $metadata = "<hr /><table>\n".$rows."</table>\n";
  }
  
  if (!empty ($metadata)) echo $metadata;
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

  // define width and height
  if (!empty ($media_size[3]))
  {
    // scaling images to reach given dpi 
    $mediawidth = round ($media_size[0] * $scaling);
    $mediaheight = round ($media_size[1] * $scaling);
?>
  <script type="text/javascript">
  function updatesize ()
  {
    var inputfield;
    
    if (parent.frames['controlFrame2'].document.forms['media'].elements['mediawidth'])
    {
      inputfield = parent.frames['controlFrame2'].document.forms['media'].elements['mediawidth'];
      
      if (inputfield.value == "") inputfield.value = '<?php echo $mediawidth; ?>';
    }
    
    if (parent.frames['controlFrame2'].document.forms['media'].elements['mediaheight'])
    {
      inputfield = parent.frames['controlFrame2'].document.forms['media'].elements['mediaheight'];
      
      if (inputfield.value == "") inputfield.value = '<?php echo $mediaheight; ?>';
    }
  }
  
  setTimeout (updatesize, 500);
  </script>
<?php
  }
}
?>

</div>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>