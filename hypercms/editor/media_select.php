<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session
define ("SESSION", "create");
// management configuration
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");
// hyperCMS UI
require ("../function/hypercms_ui.inc.php");
// formats/file extensions
require ("../include/format_ext.inc.php");


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
     !valid_publicationname ($site) ||
     $globalpermission[$site]['component'] != 1 || 
     ($mediatype != "image" && $mgmt_config[$site]['dam'] == true)
   ) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
  
// read object file
if ($mediaobject != "")
{
  $site = getpublication ($mediaobject);
  $location = getlocation ($mediaobject);
  $object = getobject ($mediaobject);
  $object_info = getfileinfo ($site, $mediaobject, "comp");
  
  // convert location
  $location = deconvertpath ($location, "file");
  $location_esc = convertpath ($site, $location, "comp");
  
  // access permissions
  $ownergroup = accesspermission ($site, $location, "comp");
  $setlocalpermission = setlocalpermission ($site, $ownergroup, "comp");
  
  // load object file
  if (valid_locationname ($location) && valid_objectname ($object))
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

if (!empty ($mediafile) && $mediafile != "Null_media.gif")
{
  $file_info = getfileinfo ($site, $mediafile, "comp");
  
	// define parameters returned to edtor window
  if ($mediacat == "cnt") 
  {
    $mediadir = getmedialocation ($site, $mediafile, "abs_path_media");
    $mediaurl = getmedialocation ($site, $mediafile, "url_path_media");
  }
	
	// show media
  $show = showmedia ($mediafile, $object_info['name'], "preview_no_rendering", "", 288);

  // extract width and height from content
  if ($show != "")
  {
    $mediawidth = getfilename ($show, "width");
    $mediaheight = getfilename ($show, "height");
  }
  
  // define with and height of media
  if (empty ($mediawidth) || empty ($mediaheight))
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
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
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
        alert(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['file-is-of-wrong-media-type-required-type'][$lang])." ".$mediatype; ?>"));
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
<?php if (!empty ($file_info) && substr_count ($hcms_ext['audio'], $file_info['ext']) > 0) echo showaudioplayer_head (); ?>
<?php if (!empty ($file_info) && substr_count ($hcms_ext['video'], $file_info['ext']) > 0) echo showvideoplayer_head ($site, false); ?>
</head>

<body class="hcmsWorkplaceGeneric" leftmargin=3 topmargin=3 marginwidth=0 marginheight=0>

<?php echo showtopbar ($hcms_lang['selected-file'][$lang], $lang); ?>

<?php
if (!empty ($mediafile))
{
  // output information
  echo "<form name=\"media\" target=\"_parent\" method=\"post\">\n";
  if ($mediatype == "video") echo "<input type=\"hidden\" name=\"mediafile\" value=\"".$mediafile."\" />\n";
  else echo "<input type=\"hidden\" name=\"mediafile\" value=\"".$mediaurl.$mediafile."\" />\n";
  echo "<input type=\"hidden\" name=\"mediawidth\" value=\"".$mediawidth."\" />
  <input type=\"hidden\" name=\"mediaheight\" value=\"".$mediaheight."\" />
  <input type=\"hidden\" name=\"mediatype\" value=\"".$mediatype."\" />

  <table border=0 cellpadding=0 cellspacing=2>
    <tr>
      <td align=left valign=top>\n";
        
  if ($show == "" || substr_count ($mediafile, "Null_media.gif") == 1)
  {
    echo "<p class=hcmsHeadline>".$hcms_lang['no-file-selected'][$lang]."</p>";
  }
  else
  {
    echo $show; 
  }
  
  echo "      </td>
      </tr>
    <tr>
    <tr>
      <td align=left valign=center>
        ".$hcms_lang['confirm-selection'][$lang].":&nbsp;
        <img src=\"".getthemelocation()."img/button_OK.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" onClick=\"submitMedia();\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('ButtonOK','','".getthemelocation()."img/button_OK_over.gif',1)\" name=\"ButtonOK\" align=\"absmiddle\" alt=\"OK\" title=\"OK\" />
      </td>
    </tr>
  </table>
</form>";
}
?>

</body>
</html>