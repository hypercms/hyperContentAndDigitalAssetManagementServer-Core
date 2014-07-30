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
// hyperCMS UI
require ("../function/hypercms_ui.inc.php");
// formats/file extensions
require ("../include/format_ext.inc.php");
// load language file
require_once ("../language/media_view.inc.php");


// input parameters
$site = getrequest_esc ("site", "publicationname"); 
$mediadir = getrequest_esc ("mediadir", "locationname");
$mediaurl = getrequest_esc ("mediaurl", "url");
$mediafile = getrequest_esc ("mediafile", "objectname");
$mediaobject = getrequest_esc ("mediaobject", "locationname");
$mediacat = getrequest_esc ("mediacat");
$mediawidth = getrequest_esc ("mediawidth", "numeric");
$mediaheight = getrequest_esc ("mediaheight", "numeric");
$mediatype = getrequest_esc ("mediatype", false, "", true);
$callback = getrequest_esc ("callback", false, "", true);
$scaling = getrequest ("scaling", "numeric", "1");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (
     $globalpermission[$site]['component'] != 1 || 
     !valid_publicationname ($site) ||
     ($mediatype != "image" && $mgmt_config[$site]['dam'] == true)
   ) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$is_video = false;
$is_audio = false;
$config = false;
  
// read media file
if ($mediaobject != "")
{
  $site = getpublication ($mediaobject);
  $location = getlocation ($mediaobject);
  $object = getobject ($mediaobject);
  $object_info = getfileinfo ($site, $mediaobject, "comp");
  
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

if ($mediafile != "Null_media.gif" && $mediafile != "")
{
	// define parameters returned to edtor window
  if ($mediacat == "cnt") 
  {
    $mediadir = getmedialocation ($site, $mediafile, "abs_path_media");
    $mediaurl = getmedialocation ($site, $mediafile, "url_path_media");
  }
	
	// read video or audio config  
  if ($mediatype == "video" || $mediatype == "audio" || $mediatype == "") 
  {
    $file_info = getfileinfo ($site, $mediafile, "comp");
    
    // new since version 5.5.7 (config of videoplayer)
    if (is_file ($mediadir.$site."/".$file_info['filename'].".config.audio"))
    {
      $config = readmediaplayer_config ($mediadir.$site."/", $file_info['filename'].".config.audio");
      $is_audio = true;
    }
    elseif (is_file ($mediadir.$site."/".$file_info['filename'].".config.video"))
    {
      $config = readmediaplayer_config ($mediadir.$site."/", $file_info['filename'].".config.video");
      $is_video = true;
    }
    // new since version 5.6.3 (config/preview of original file)
    elseif (is_file ($mediadir.$site."/".$file_info['filename'].".config.orig"))
    {
      $config = readmediaplayer_config ($mediadir.$site."/", $file_info['filename'].".config.orig");
      
      if ($file_info['ext'] != "" && substr_count ($hcms_ext['audio'], $file_info['ext']) > 0) $is_audio = true;
      else $is_video = true;
    }
    // old version (only FLV support)
    elseif (is_file ($mediadir.$site."/".$file_info['filename'].".config.flv"))
    {
      $config = readmediaplayer_config ($mediadir.$site."/", $file_info['filename'].".config.flv");
      $is_video = true;
    }

    if ($config['width'] > 0 && $config['height'] > 0) 
    {
      $mediawidth = $config['width'];
      $mediaheight = $config['height'];
    }
    else
    {
      $mediawidth = 320;
      $mediaheight = 240;
    }
  }
  
  // all other media types
  if (!is_array ($config))
  {
    // get file information
    $media_size = @getimagesize ($mediadir.$mediafile);
    
    if ($media_size == false || $media_size[3] == "")
    {
      $mediawidth = 0;
      $mediaheight = 0;
    }
    else
    {
      $mediawidth = round ($media_size[0] * $scaling, 0);
      $mediaheight = round ($media_size[1] * $scaling, 0);
    }
  }
}

?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="../javascript/main.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
function checkType()
{
  var mediafile = document.forms['media'].mediafile.value;
  var mediatype = document.forms['media'].mediatype.value;
  
  if (mediafile != "" && mediatype != "")
  {
    var mediaext = mediafile.substring (mediafile.lastIndexOf("."), mediafile.length);
    mediaext = mediaext.toLowerCase();
   
    if (mediaext.length > 2)
    {
      if (mediatype == "audio") allowedext = "<?php echo $hcms_ext['audio']; ?>";
      else if (mediatype == "compressed") allowedext = "<?php echo $hcms_ext['compressed']; ?>";
      else if (mediatype == "flash") allowedext = "<?php echo $hcms_ext['flash']; ?>";
      else if (mediatype == "image") allowedext = "<?php echo $hcms_ext['image']; ?>";
      else if (mediatype == "text") allowedext = "<?php echo $hcms_ext['cms'].$hcms_ext['bintxt'].$hcms_ext['cleartxt']; ?>";
      else if (mediatype == "video") allowedext = "<?php echo $hcms_ext['video']; ?>";
      
      if (allowedext.indexOf(mediaext) < 0) 
      {
        alert(hcms_entity_decode("<?php echo $text11[$lang]." ".$mediatype; ?>"));
        return false;
      }
      else return true;   
    }
    else return true;
  }
  else return true;
}

function submitMedia ()
{
  test = checkType();

  if (test != false)
  {
    var url = document.forms['media'].mediafile.value;
    var width = document.forms['media'].mediawidth.value;
    var height = document.forms['media'].mediaheight.value;
    <?php if ($mediatype == "video" || $mediatype == "image") : ?>
    window.top.opener.CKEDITOR.tools.callFunction(<?php echo $callback ?>, url, width+"x"+height);
    <?php else : ?>
    window.top.opener.CKEDITOR.tools.callFunction(<?php echo $callback ?>, url);
    <?php endif; ?>
    parent.window.top.close();
    return true;
  }
  else return false;
}
//-->
</script>
<?php 
if ($mediatype == "video" || $is_video) echo showvideoplayer_head ($site, false); 
elseif ($mediatype == "audio" || $is_audio) echo showaudioplayer_head ();
?>
</head>

<body class="hcmsWorkplaceGeneric" leftmargin=3 topmargin=3 marginwidth=0 marginheight=0>

<?php
echo showtopbar ($text9[$lang], $lang);
?>

<?php
if ($mediafile != "Null_media.gif" && $mediafile != "")
{
  
  // output information
  echo "<form name=\"media\" target=\"_parent\" method=\"post\">\n";
  if($mediatype == "video") echo "<input type=\"hidden\" name=\"mediafile\" value=\"".$mediafile."\" />\n";
  else echo "<input type=\"hidden\" name=\"mediafile\" value=\"".$mediaurl.$mediafile."\" />\n";
  echo "<input type=\"hidden\" name=\"mediawidth\" value=\"".$mediawidth."\" />
  <input type=\"hidden\" name=\"mediaheight\" value=\"".$mediaheight."\" />
  <input type=\"hidden\" name=\"mediatype\" value=\"".$mediatype."\" />

  <table border=0 cellpadding= cellspacing=2>
    <tr>
      <td align=left valign=top>\n";
        
  if (substr_count ($mediafile, "Null_media.gif") == 1)
  {
    echo "<p class=hcmsHeadline>".$text0[$lang]."</p>";
  }
  elseif ($mediafile != "")
  {    
    if ($mediatype == "audio" || $is_audio) echo showmedia ($site."/".$file_info['filename'].'.config.audio', $object_info['name'], "preview_no_rendering", "", "", "");
    elseif ($mediatype == "video" || $is_video) echo showmedia ($site."/".$file_info['filename'].'.config.video', $object_info['name'], "preview_no_rendering", "", 320, "");
    else echo showmedia ($mediafile, $object_info['name'], "preview_no_rendering", "", 320, "");
  }
  
  echo "      </td>
      </tr>
    <tr>
    <tr>
      <td align=left valign=center>
        ".$text10[$lang].":&nbsp;
        <img src=\"".getthemelocation()."img/button_OK.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" onClick=\"submitMedia();\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('ButtonOK','','".getthemelocation()."img/button_OK_over.gif',1)\" name=\"ButtonOK\" align=\"absmiddle\" alt=\"OK\" title=\"OK\" />
      </td>
    </tr>
  </table>
</form>";
}
?>

</body>
</html>