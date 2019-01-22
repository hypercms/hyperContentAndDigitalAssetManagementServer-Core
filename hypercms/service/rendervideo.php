<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
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
// format extensions
require ("../include/format_ext.inc.php");


// input parameters
$savetype = getrequest ("savetype");
$wf_token = getrequest_esc ("wf_token");
$token = getrequest ("token");

// object
$site = getrequest ("site", "publicationname");
$location = getrequest ("location", "locationname");
$page = getrequest ("page", "objectname");
// video settings
$filetype = getrequest ("filetype");
$format = getrequest ("format");
// quality
$bitrate = getrequest ("bitrate");
$audiobitrate = getrequest ("audiobitrate");
// size
$videosize = getrequest ("videosize");
$width = getrequest ("width", "numeric");
$height = getrequest ("height", "numeric");
// cut (used when page_multiedit is used)
$cut = getrequest ("cut", "numeric", 0);
$cut_begin = getrequest ("cut_begin");
$cut_end = getrequest ("cut_end");
// segments
$mgmt_mediaoptions['segments'] = getrequest ("segments");
// thumbnail
$thumb = getrequest ("thumb", "numeric", 0);
$thumb_frame = getrequest ("thumb_frame");
// rotate
$rotate = getrequest("rotate");
$angle = getrequest ("degree", "numeric");
// flip
$flip = getrequest ("flip");
// effects
$sharpen = getrequest ("sharpen");
$gamma = getrequest ("gamma");
$brightness = getrequest ("brightness");
$contrast = getrequest ("contrast");
$saturation = getrequest ("saturation");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// get media file
if ($location != "" && $page != "")
{
  $objectinfo = getobjectinfo ($site, $location, $page);
  $mediafile = $objectinfo['media'];
}
else $mediafile = getrequest ("media", "objectname");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- load balancer ----------------------------------

// call load balancer only for management server where user is logged in
if (checktoken ($token, $user)) loadbalancer ("rendervideo");

// --------------------------------- logic section ----------------------------------

function startConversion ($videotype) 
{
  // Needed for createmedia
  global $mgmt_config, $mgmt_imagepreview, $mgmt_mediapreview, $mgmt_mediaoptions, $mgmt_imageoptions, $mgmt_maxsizepreview, $mgmt_mediametadata;
  // Used for $mgmt_mediaoptions
  global $filetype, $cut_add, $sh_add, $rotate_add, $gbcs_add, $bitrate, $audiobitrate, $width, $height;
  // Used for createmedia
  global $site, $media_root, $file_info;
  // Used for createthumbnail_video
  global $thumb_frame, $thumb;
   // Used for Output
  global $hcms_lang, $lang;
  
  $success = false;

  // set media type
  if ($videotype == "videoplayer") $videotype = "thumbnail";
  // set format if type is "original"
  if ($videotype == "original") $filetype = strtolower (substr ($file_info['ext'], 1));

  // define FFMPEG options
  if ($bitrate == "" || $bitrate == "original") $mgmt_mediaoptions['.'.$filetype] = str_replace ("-b:v %videobitrate%", "-q:v 5", $mgmt_mediaoptions['.'.$filetype]);
  if ($audiobitrate == "" || $audiobitrate == "original") $mgmt_mediaoptions['.'.$filetype] = str_replace ("-b:a %audiobitrate%", "", $mgmt_mediaoptions['.'.$filetype]);
  if ($width < 1 || $height < 1) $mgmt_mediaoptions['.'.$filetype] = str_replace ("-s:v %width%x%height%", "", $mgmt_mediaoptions['.'.$filetype]);

  $mgmt_mediaoptions['.'.$filetype] = $cut_add.$sh_add.$rotate_add.$gbcs_add.str_replace (array('%videobitrate%', '%audiobitrate%', '%width%', '%height%'), array($bitrate, $audiobitrate, $width, $height), $mgmt_mediaoptions['.'.$filetype]);

  // create video
  $createmedia = createmedia ($site, $media_root, $media_root, $file_info['file'], $filetype, $videotype);

  if ($createmedia == false)
  {
    $show = str_replace ('%filetype%', $filetype, $hcms_lang['the-file-could-not-be-converted-to-filetype'][$lang]);
  }
  else
  {
    if ($thumb == 0 || ($match1 = preg_match ("/\d{1,2}:\d{1,2}:\d{1,2}(.\d){0,3}/", $thumb_frame)) && ($match2 = createthumbnail_video ($site, $media_root, $media_root, $file_info['file'], $thumb_frame)))
    {
      $success = true;
      $show = str_replace ('%filetype%', $filetype, $hcms_lang['the-file-was-converted-successfully-to-filetype'][$lang]);
    } 
    else
    {
      if (!$match1) $show = $hcms_lang['could-not-determine-the-frame-for-the-preview-image'][$lang];
      else $show = $hcms_lang['could-not-extract-the-preview-image'][$lang];
    }
  }
  
  $result = array();
  $result['success'] = $success;
  $result['message'] = $show;
  
  return $result;
}


$show = "";
$add_onload = "";

// load object file and get container and media file
$objectdata = loadfile ($location, $page);
$mediafile = getfilename ($objectdata, "media");

// get file information of original component file
$pagefile_info = getfileinfo ($site, $page, $cat);

// read supported formats
$available_extensions = array();

foreach ($mgmt_mediaoptions as $ext => $options)
{
  if ($ext != "thumbnail-video" && $ext != "thumbnail-audio")
  {
  	// remove the dot
  	$name = strtolower (trim ($ext, "."));    
  	$available_extensions[$name] = strtoupper ($name);
  }
}

// include media options
require ($mgmt_config['abs_path_cms']."include/mediaoptions.inc.php");

// check input paramters and define video settings
if ($format != "" && array_key_exists ($format, $available_formats)) $format = $format;
else $format = "";

if ($bitrate != "" && array_key_exists ($bitrate, $available_bitrates)) $bitrate = $bitrate;
else $bitrate = "";

if ($audiobitrate != "" && array_key_exists ($audiobitrate, $available_audiobitrates)) $audiobitrate = $audiobitrate;
else $audiobitrate = "";

if ($videosize != "" && array_key_exists ($videosize, $available_videosizes)) $videosize = $videosize;
else $videosize = "";

// options for FFMPEG:
// Audio Options:
// -ac ... number of audio channels
// -an ... disable audio
// -ar ... audio sampling frequency (default = 44100 Hz)
// -b:a ... audio bitrate (default = 64 kb/s)
// -c:a ... audio codec (e.g. libmp3lame, libfaac, libvorbis)
// Video Options:
// -b:v ... video bitrate in bit/s (default = 200 kb/s)
// -c:v ... video codec (e.g. libx264)
// -cmp ... full pel motion estimation compare function (used for mp4)
// -f ... force file format (like flv, mp4, ogv, webm, mp3)
// -flags ... specific options for video encoding
// -mbd ... macroblock decision algorithm (high quality mode)
// -r ... frame rate in Hz (default = 25)
// -s:v ... frame size in pixel (WxH)
// -sh ... sharpness (blur -1 up to 1 sharpen)
// -gbcs ... gamma, brightness, contrast, saturation (neutral values are 1.0:1:0:0.0:1.0)
// -wm .... watermark image and watermark positioning (PNG-file-reference->positioning [topleft, topright, bottomleft, bottomright] e.g. image.png->topleft)

// get publication and file info
$media_root = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";
$file_info = getfileinfo ($site, $mediafile, $cat);

// define filetype
if ($filetype != "" && array_key_exists ($filetype, $available_extensions)) $filetype = strtolower ($filetype);
elseif (strtolower ($filetype) == 'videoplayer' || strtolower ($filetype) == 'original') $filetype = strtolower ($filetype);
else $filetype = "videoplayer";

// render media
if (checktoken ($token, $user) && valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page))
{
  // sets the maximum execution time to 2 hours
	ini_set ("max_execution_time", "7200");

	// Ultra HD
	if ($videosize == "uhd")
  {
    $width = "3840";
    $height = "2160";
	}
	// Full HD
	elseif ($videosize == "fhd")
  {
    $width = "1920";
    $height = "1080";
	}
	// HDTV 720p
	elseif ($videosize == "xl")
  {
    $width = "1280";
    $height = "720";
	}
	// VGA Resolution
	elseif ($videosize == "l")
  {
    $width = "640";
    if ($format == "fs") $height = "480";
    elseif ($format == "ws") $height = "360";
  }
	// Internet
  elseif ($videosize == "s")
  {
    $width = "320";
    if ($format == "fs") $height = "240";
    elseif ($format == "ws") $height = "180";
	}
  // Individual
  elseif ($videosize == "i")
  {
    $width = intval ($width);
    $height = intval ($height);
  }
  // Original
  else
  {
    $width = "0";
    $height = "0";
  }
  
  // check for max video size
	if ($width > 5000) $width = 3840;
	if ($height > 3000) $height = 2160;
  
  // Video montage
  $cut_add = "";
  
  if ($cut == 1 && $cut_begin != "" && $cut_end != "")
  {
    $starttime = DateTime::createFromFormat ('H:i:s.u', $cut_begin);
    $endtime = DateTime::createFromFormat ('H:i:s.u', $cut_end);
    
    $duration = $starttime->diff($endtime);
    
    // get msec
    list ($rest, $startmsec) = explode (".", $cut_begin);
    list ($rest, $endmsec) = explode (".", $cut_end);
    
    $durationmsec = $endmsec - $startmsec;
    
    if ($durationmsec < 0)
    {
      $durationmsec = 1000 + $durationmsec;
      $duration->s -=1;
      
      if ($duration->s < 0)
      {
        $duration->i -=1;
        
        if ($duration->i < 0)
        {
          $duration->h -=1;
        }
      }
    }
    
    if ($startmsec < 100) $startmsec = "0".$startmsec;
    if ($durationmsec < 100) $durationmsec = "0".$durationmsec;
        
    $cut_add = '-ss '.$starttime->format('H:i:s').'.'.$startmsec.' -t '.$duration->format('%H:%I:%S').'.'.$durationmsec.' '; 
  }
  
  // rotate
  if ($rotate == "rotate" && $angle != "")
  {
    $rotate_add = "-rotate ".$angle." ";
  }
  // flip
  elseif ($rotate == "flip" && array_key_exists ($flip, $available_flip))
  {
    $rotate_add = "-".$flip." ";
  }
  
  // sharpen
  $sh_add = "";
  
  if ($sharpen != "")
  {
    $sharpen = round (($sharpen / 100), 2);
            
    $sh_add = "-sh ".$sharpen." ";
  }
  
  // gamma, brightness, contrast, saturation
  $gbcs_add = "";
  
  if ($gamma != "" || $brightness != "" || $contrast != "" || $saturation != "")
  {
    // set default values
    if ($gamma == "") $gamma = "1";
    else $gamma = round ((($gamma + 100) / 100), 2);
    
    if ($brightness == "") $brightness = "0";
    else $brightness = round (($brightness / 100), 2);
    
    if ($contrast == "") $contrast = "1";
    else $contrast = round ((($contrast + 100) / 100), 2);
    
    if ($saturation == "") $saturation = "1";
    else $saturation = round ((($saturation + 100) / 100), 2);
                    
    $gbcs_add = "-gbcs ".$gamma.":".$brightness.":".$contrast.":".$saturation." ";
  }
  
  // conversion of videoplayer videos
  if ($filetype == "videoplayer")
  {
    $run = 0;
    
    foreach ($available_extensions as $filetype)
    {
      $filetype = strtolower ($filetype);
      
      // we only convert the commonly used video formats (MP4, OGV)
      if (in_array ($filetype, array('mp4', 'ogv')))
      {
        // only capture video screen for thumbnail image for the first video
        if ($run == 1) $thumb = 0;
          
        $result = startConversion ("videoplayer");
        
        $success = $result['success'];
        $show .= $result['message']."<br />\n";
        
        $run = 1;
      }
    }
  }
  // conversion of one video
  else
  {
    $result = startConversion ($filetype);
    
    $success = $result['success'];
    $show = $result['message']."<br />\n";
  }
}

// return json encoded data for AJAX call
if ($savetype == "auto" || $savetype == "")
{ 
  $output = array();
  $output['success'] = $success;
  $output['message'] = $show;
  
  header ('Content-Type: application/json; charset=utf-8');
  echo json_encode ($output);
}
// refresh after save and open
elseif ($savetype == "editor_so")
{
  $add_onload .=  "document.location='../media_rendering.php?site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."';\n";
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script type="text/javascript">
<?php echo $add_onload; ?>
</script>
<script src="../javascript/main.js" type="text/javascript"></script>
<script src="../javascript/click.js" type="text/javascript"></script>
</head>
<body class="hcmsWorkplaceGeneric">
<div style="padding:4px;">
  <?php echo $show; ?>
</div>
</body>
</html>
<?php 
}
?>
