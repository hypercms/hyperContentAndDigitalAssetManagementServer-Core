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
// formats/file extensions
require ("include/format_ext.inc.php");


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
     @$globalpermission[$site]['component'] != 1 || 
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

// prepare media preview, link and media dimensions
if (!empty ($mediafile) && $mediafile != "Null_media.png")
{
  $file_info = getfileinfo ($site, $mediafile, "comp");
  
  // define parameters returned to edtor window
  if ($mediacat == "cnt") 
  {
    $mediadir = getmedialocation ($site, $mediafile, "abs_path_media");
    $mediaurl = getmedialocation ($site, $mediafile, "url_path_media");
  }

  // media link
  if (!empty ($mgmt_config[$site]['dam']))
  {
    // use wrapper link for DAM configuration
    $medialink = createwrapperlink ("", "", "", "", "", getmediacontainerid ($mediafile));
  }
  else
  {
    // use direct URL
    $medialink = $mediaurl.$mediafile;
  }
  
  // show media
  $show = showmedia ($mediafile, $object_info['name'], "preview_no_rendering", "", 288);

  // try to extract width and height from content
  if ($show != "")
  {
    $mediawidth = getfilename ($show, "width");
    $mediaheight = getfilename ($show, "height");
  }

  // fallback: try to extract width and height from source file
  if (empty ($mediawidth) || empty ($mediaheight))
  {
    $media_size = getmediasize ($mediadir.$mediafile);
    
    if (!empty ($media_size['width']) && !empty ($media_size['height']))
    {
      $mediawidth = $media_size['width'];
      $mediaheight = $media_size['height'];
    }
  }

  // scale width and height according to the dpi setting
  if (!empty ($mediawidth) && !empty ($mediaheight))
  {
    $mediawidth = round (($mediawidth * $scaling), 0);
    $mediaheight = round (($mediaheight * $scaling), 0);
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script type="text/javascript" src="javascript/main.min.js"></script>
<script type="text/javascript">

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
    var url = document.forms['media'].medialink.value;
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
</script>
<?php
if (!empty ($mediafile))
{
  if (is_audio ($mediafile)) echo showaudioplayer_head (false, true);
  elseif (is_video ($mediafile)) echo showvideoplayer_head (false, false, true);
}
?>
</head>

<body class="hcmsWorkplaceGeneric">

<div class="hcmsWorkplaceFrame">
  <?php echo showtopbar ($hcms_lang['selected-file'][$lang], $lang); ?>
  
  <?php
  if (!empty ($medialink))
  {
    // output information
    echo "
  <form name=\"media\" target=\"_parent\" method=\"post\">";
    if ($mediatype == "video") echo "
    <input type=\"hidden\" name=\"mediafile\" value=\"".$mediafile."\" />
    <input type=\"hidden\" name=\"medialink\" value=\"".$mediafile."\" />";
    else echo "
    <input type=\"hidden\" name=\"mediafile\" value=\"".$mediaurl.$mediafile."\" />
    <input type=\"hidden\" name=\"medialink\" value=\"".$medialink."\" />";
    echo "
    <input type=\"hidden\" name=\"mediawidth\" value=\"".$mediawidth."\" />
    <input type=\"hidden\" name=\"mediaheight\" value=\"".$mediaheight."\" />
    <input type=\"hidden\" name=\"mediatype\" value=\"".$mediatype."\" />
    <input type=\"hidden\" name=\"scaling\" value=\"".$scaling."\" />
  
    <table class=\"hcmsTableStandard\">
      <tr>
        <td style=\"vertical-align:top;\">";
          
    if ($show == "" || substr_count ($mediafile, "Null_media.png") == 1)
    {
      echo "
        <p class=\"hcmsHeadline\">".$hcms_lang['no-file-selected'][$lang]."</p>";
    }
    else
    {
      echo $show; 
    }
    
    echo "
          </td>
        </tr>
      <tr>
      <tr>
        <td style=\"vertical-align:middle;\">
          ".$hcms_lang['confirm-selection'][$lang].":&nbsp;
          <img src=\"".getthemelocation()."img/button_ok.png\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" onClick=\"submitMedia();\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('ButtonOK','','".getthemelocation()."img/button_ok_over.png',1)\" name=\"ButtonOK\" alt=\"OK\" title=\"OK\" />
        </td>
      </tr>
    </table>
  </form>";
  }
  ?>
</div>
  
</body>
</html>