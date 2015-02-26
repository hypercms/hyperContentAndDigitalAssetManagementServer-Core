<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// start session
session_name( "hyperCMS" );
session_start();

// session parameters
require ("include/session.inc.php");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");
// format extensions
require ("include/format_ext.inc.php");


// input parameters
$action = getrequest_esc ("action");
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$token = getrequest ("token");
// video settings
$filetype = getrequest ("filetype");
$format = getrequest ("format");
$bitrate = getrequest ("bitrate");
$audiobitrate = getrequest ("audiobitrate");
$videosize = getrequest ("videosize");
$width = getrequest ("width", "numeric");
$height = getrequest ("height", "numeric");
$cut = getrequest ("cut", "numeric", 0);
$cut_begin = getrequest ("cut_begin");
$cut_end = getrequest ("cut_end");
$thumb = getrequest ("thumb", "numeric", 0);
$thumb_frame = getrequest ("thumb_frame");
$sharpen = getrequest ("sharpen");
$gamma = getrequest ("gamma");
$brightness = getrequest ("brightness");
$contrast = getrequest ("contrast");
$saturation = getrequest ("saturation");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// ------------------------------ permission section --------------------------------
// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);
if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

function startConversion ($videotype) 
{
  // Needed for createmedia
  global $mgmt_config, $mgmt_imagepreview, $mgmt_mediapreview, $mgmt_mediaoptions, $mgmt_imageoptions, $mgmt_maxsizepreview, $mgmt_mediametadata;
  // Used for $mgmt_mediaoptions
  global $filetype, $cut_add, $sh_add, $gbcs_add, $bitrate, $audiobitrate, $width, $height, $ffmpeg_options;
  // Used for createmedia
  global $site, $media_root, $file_info;
  // Used for createthumbnail_video
  global $thumb_frame, $thumb;
   // Used for Output
  global $text1, $text2, $text30, $text31, $lang;
    
  // FFMPEG options
  $mgmt_mediaoptions['.'.$filetype] = $cut_add.$sh_add.$gbcs_add.str_replace (array('%bitrate%', '%audiobitrate%', '%width%', '%height%'), array($bitrate, $audiobitrate, $width, $height), $ffmpeg_options[$filetype]);
    
  // create video
  if ($videotype == "videoplayer") $videotype = "thumbnail";
  else $videotype = "video";

  $result = createmedia ($site, $media_root, $media_root, $file_info['file'], $filetype, $videotype);

  if ($result == false)
  {
    $show = str_replace ('%filetype%', $filetype, $hcms_lang['the-file-could-not-be-converted-to-filetype'][$lang]);
  }
  else
  {
    if ($thumb == 0 || ($match1 = preg_match ("/\d{1,2}:\d{1,2}:\d{1,2}(.\d){0,3}/", $thumb_frame)) && ($match2 = createthumbnail_video ($site, $media_root, $media_root, $file_info['file'], $thumb_frame)))
    {
      $show = str_replace ('%filetype%', $filetype, $hcms_lang['the-file-was-converted-successfully-to-filetype'][$lang]);
    } 
    else
    {
      if (!$match1) $show = $hcms_lang['could-not-determine-the-frame-for-the-preview-image-when-'][$lang];
      else $show = $hcms_lang['could-not-extract-the-preview-image'][$lang];
    }
  }
  
  return $show;
}

$show = "";

// load object file and get container and media file
$objectdata = loadfile ($location, $page);
$mediafile = getfilename ($objectdata, "media");

// get file information of original component file
$pagefile_info = getfileinfo ($site, $page, $cat);

// read supported formats
$available_extensions = array();

foreach ($mgmt_mediaoptions as $ext => $options)
{
	// remove the dot
	$name = strtolower (substr ($ext, 1));

	$available_extensions[$name] = strtoupper ($name);
}

// availbale formats
$available_formats = array();

$available_formats['fs'] = array(
	'name'					 => $hcms_lang['standard-video-43'][$lang],
	'checked'				 => false
);

$available_formats['ws'] = array(
	'name'					 => $hcms_lang['widescreen-video-169'][$lang],
	'checked'				 => true
);

// available bitrates
$available_bitrates = array();

$available_bitrates['200k'] = array(
	'name'					=> $hcms_lang['low'][$lang].' (200k)',
	'checked'				=> false
);

$available_bitrates['768k'] = array(
	'name'					=> $hcms_lang['medium'][$lang].' (768k)',
	'checked'				=> true
);

$available_bitrates['1856k'] = array(
	'name'		 => $hcms_lang['high'][$lang].' (1856k)',
	'checked'	 => false
);

// availbale video sizes
$available_videosizes = array();

$available_videosizes['s'] = array(
	'name'					=> $hcms_lang['low-resolution-of-320-pixel-width'][$lang],
	'checked'				=> false,
	'individual'		=> false
);

$available_videosizes['l'] = array(
	'name'					=> $hcms_lang['medium-resolution-of-640-pixel-width'][$lang],
	'checked'				=> true,
	'individual'		=> false
);

$available_videosizes['xl'] = array(
	'name'					=> $hcms_lang['high-resoltion-of-1280x720-pixel'][$lang],
	'checked'				=> false,
	'individual'		=> false
);

$available_videosizes['i'] = array(
	'name'		 => $hcms_lang['individual-of-'][$lang],
	'checked'	 => false,
	'individual' => true
);

//available bitrates for the audio
$available_audiobitrates = array();

$available_audiobitrates['64k'] = array(
  'name'    => $hcms_lang['low'][$lang].' (64 kb/s)',
  'checked' => true
);

$available_audiobitrates['128k'] = array(
  'name'    => $hcms_lang['medium'][$lang].' (128 kb/s)',
  'checked' => false
);

$available_audiobitrates['192k'] = array(
  'name'    => $hcms_lang['high'][$lang].' (192 kb/s)',
  'checked' => false
);

// check input paramters and define video settings
if ($filetype != "" && (array_key_exists ($filetype, $available_extensions) || strtolower ($filetype) == 'videoplayer')) $filetype = strtolower ($filetype);
else $filetype = "videoplayer";

if ($format != "" && array_key_exists ($format, $available_formats)) $format = $format;
else $format = "fs";

if ($bitrate != "" && array_key_exists ($bitrate, $available_bitrates)) $bitrate = $bitrate;
else $bitrate = "768k";

if ($audiobitrate != "" && array_key_exists ($audiobitrate, $available_audiobitrates)) $audiobitrate = $audiobitrate;
else $audiobitrate = "64k";

if ($videosize != "" && array_key_exists ($videosize, $available_videosizes)) $videosize = $videosize;
else $videosize = "s";

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

$ffmpeg_options['flv'] = "-b:v %bitrate% -s:v %width%x%height% -f flv -c:a libmp3lame -b:a %audiobitrate% -ac 2 -ar 22050";
$ffmpeg_options['mp4'] = "-b:v %bitrate% -s:v %width%x%height% -f mp4 -c:a libfaac -b:a %audiobitrate% -ac 2 -c:v libx264 -mbd 2 -flags +loop+mv4 -cmp 2 -subcmp 2";
$ffmpeg_options['ogv'] = "-b:v %bitrate% -s:v %width%x%height% -f ogg -c:a libvorbis -b:a %audiobitrate% -ac 2";
$ffmpeg_options['webm'] = "-b:v %bitrate% -s:v %width%x%height% -f webm -c:a libvorbis -b:a %audiobitrate% -ac 2";
$ffmpeg_options['mp3'] = "-f mp3 -c:a libmp3lame -b:a %audiobitrate% -ar 44100";

// get publication and file info
$media_root = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";
$file_info = getfileinfo ($site, $mediafile, $cat);

$audio = (substr_count ($hcms_ext['audio'].'.', $file_info['ext'].'.') > 0);

// render media
if ($action == "rendermedia" && checktoken ($token, $user) && valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page))
{
	ini_set ("max_execution_time", "3600"); // sets the maximum execution time of this script to 1 hour.

	// HDTV 720p
	if ($videosize == "xl")
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
  else
  {
    $width = intval ($width);
    $height = intval ($height);
  }
  
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

  // check for max video size
	if ($width > 1920) $width = 1920;
	if ($height > 1080) $height = 1080;    

  // conversion of videoplayer videos
  if ($filetype == "videoplayer")
  {
    $run = 0;
    
    foreach ($available_extensions as $filetype)
    {
      $filetype = strtolower ($filetype);
      
      // we only convert the most used video formats (FLV, MP4, OGV)
      if (in_array ($filetype, array('flv', 'mp4', 'ogv')))
      {
        // only capture video screen for thumbnail image for the first video
        if ($run == 1) $thumb = 0;
          
        $show .= startConversion ("videoplayer")."<br />\n";
        $run = 1;
      }
    }
  }
  // conversion of one video
  else $show = startConversion ($filetype);
}

// generate media player config
if ($hcms_ext['video'] != "" && $hcms_ext['audio'] != "")
{
  $mediawidth = 0;
  $mediaheight = 0;

  // generate player code
  $playercode = showmedia ($site."/".$mediafile, $pagefile_info['name'], "preview_download", "cut_video", $mediawidth, $mediaheight, "");
}
else
{
  $playercode = "";
}

// security token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" type="text/css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/jquery/jquery-1.9.1.min.js"></script>
<script src="javascript/jquery-ui/jquery-ui-1.10.2.min.js"></script>
<link rel="stylesheet" href="javascript/jquery-ui/jquery-ui-1.10.2.css" type="text/css" />
<?php 
if ($audio) echo showaudioplayer_head ();
else echo showvideoplayer_head ($site, false, 'preview'); 
?>

<script type="text/javascript">
<!--

function checkCut()
{
  var area1 = $('#cut_area');
  
  if (document.getElementById('cut_yes').checked == true)
  {
    area1.show();
  }
  else
  {
    area1.hide();
  }
}

<?php if (!$audio) { ?>
function checkThumb()
{
  var area1 = $('#thumb_area');
  
  if (document.getElementById('thumb_yes').checked == true)
  {
    area1.show();
  }
  else
  {
    area1.hide();
  }
}
<?php } ?>
function updateField (field)
{ 
  <?php
  // if we use the audio player we check other values
  if ($audio) { ?>
  var player = {};
  
  for (var i = 0; i < audiojs.instanceCount; i++)
  {
    if (audiojs.instances['audiojs'+i].element.id == "hcms_audioplayer_cut_audio")
      player = audiojs.instances['audiojs'+i];
  }
  
  var time = player.element.currentTime;  
  <?php  
  }
  // if we use projekktor we need to check for the state beforehand
  elseif (strtolower ($mgmt_config['videoplayer']) == "projekktor") { 
  ?>
  var player = projekktor('hcms_mediaplayer_cut_video');
  
  if (player.getState('PLAYING') || player.getState('PAUSED'))
  {
    var time = player.getPosition();
  }
  else
  {
    alert (hcms_entity_decode('<?php echo $hcms_lang['videoplayer-must-be-playing-or-paused-to-set-start-and-end-positions'][$lang]; ?>'));
    return 0;
  }
  <?php 
  } else {
  ?>
  var player = videojs("hcms_mediaplayer_cut_video");
  var time = player.currentTime();
  <?php 
  }
  ?>
  var seconds = Math.floor(time)%60;
  var miliseconds= Math.floor((time % seconds)*1000);
  var minutes = Math.floor(time/60)%60;
  var hours = Math.floor(time/3600)%24;
  
  if (hours   < 10) {hours   = "0"+hours;}
  if (minutes < 10) {minutes = "0"+minutes;}
  if (seconds < 10) {seconds = "0"+seconds;}

  field.value = hours+':'+minutes+':'+seconds+'.'+miliseconds;
}

function openerReload ()
{
  // reload main frame
  if (opener != null && eval (opener.parent.frames['mainFrame']))
  {
    opener.parent.frames['mainFrame'].location.reload();
  }
  
  // reload object frame
  if (opener != null && eval (opener.parent.frames['objFrame']))
  { 
    opener.parent.frames['objFrame'].location.href='page_view.php?ctrlreload=yes&site=<?php echo url_encode ($site); ?>&cat=<?php echo url_encode ($cat); ?>&location=<?php echo url_encode ($location_esc); ?>&page=<?php echo url_encode ($page); ?>';
  }
  
  return true;
}

function toggle_sharpen () 
{
  var chbx = $('#chbx_sharpen');
  var sharpen = $('#sharpen');
  
  if (chbx.prop('checked')) 
  {
    sharpen.prop('disabled', false);
    sharpen.spinner("option", "disabled", false);
  }
  else 
  {
    sharpen.prop('disabled', true);
    sharpen.spinner("option", "disabled", true);
  }
}

function toggle_gamma () 
{
  var chbx = $('#chbx_gamma');
  var gamma = $('#gamma');
  
  if (chbx.prop('checked')) 
  {
    gamma.prop('disabled', false);
    gamma.spinner("option", "disabled", false);
  }
  else 
  {
    gamma.prop('disabled', true);
    gamma.spinner("option", "disabled", true);
  }
}

function toggle_brightness () 
{
  var chbx = $('#chbx_brightness');
  var brightness = $('#brightness');
  
  if (chbx.prop('checked')) 
  {
    brightness.prop('disabled', false);
    brightness.spinner("option", "disabled", false);
  }
  else 
  {
    brightness.prop('disabled', true);
    brightness.spinner("option", "disabled", true);
  }
}

function toggle_contrast () 
{
  var chbx = $('#chbx_contrast');
  var contrast = $('#contrast');
  
  if (chbx.prop('checked')) 
  {
    contrast.prop('disabled', false);
    contrast.spinner("option", "disabled", false);
  }
  else 
  {
    contrast.prop('disabled', true);
    contrast.spinner("option", "disabled", true);
  }
}

function toggle_saturation () 
{
  var chbx = $('#chbx_saturation');
  var saturation = $('#saturation');
  
  if (chbx.prop('checked')) 
  {
    saturation.prop('disabled', false);
    saturation.spinner("option", "disabled", false);
  }
  else 
  {
    saturation.prop('disabled', true);
    saturation.spinner("option", "disabled", true);
  }
}

function activate ()
{
  toggle_sharpen();
  toggle_gamma();
  toggle_brightness();
  toggle_contrast();
  toggle_saturation();
}

function toggleDivAndButton (caller, element)
{
  var options = $(element);
  caller = $(caller);
  var time = 500;
    
  if (options.css('display') == 'none')
  {
    caller.addClass('hcmsButtonActive');
    activate();
    options.fadeIn(time);
  }
  else
  {
    caller.removeClass('hcmsButtonActive');
    options.fadeOut(time);
  }
}

$(window).load( function()
{
  var spinner_config = { step: 1, min: -100, max: 100}
  
  $('#sharpen').spinner(spinner_config);
  $('#gamma').spinner(spinner_config);
  $('#brightness').spinner(spinner_config);
  $('#contrast').spinner(spinner_config);
  $('#saturation').spinner(spinner_config);
  
  // add special function
  $.fn.getGeneratorParameter = function() {
    return this.prop('name')+'='+this.val();
  } 
});

<?php if (!$audio) { ?>
$().ready(function() {
  checkCut();
  checkThumb();
});
<?php } ?>
-->  
</script>
<style>
.row
{
  margin-top: 1px;
}

.row *
{
  vertical-align: middle;
}

.row input[type="radio"]
{
  margin: 0px;
  padding: 0px;
}

.cell
{
  vertical-align: top;
  display: inline-block;
  margin-left: 10px;
  margin-top: 10px;
  width: 210px;
}

.cellButton
{
  vertical-align: middle;
  padding: 0px 2px;
}

.cell *
{
  font-size: 11px;
}
</style>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- saving --> 
<div id="savelayer" class="hcmsWorkplaceGeneric" style="position:fixed; width:100%; height:100%; margin:0; padding:0; left:0px; top:0px; visibility:hidden; z-index:10;">
	<span style="position:absolute; top:50%; height:150px; margin-top:-75px; width:200px; left:50%; margin-left:-100px;">
		<b><?php echo $hcms_lang['the-file-is-being-processed'][$lang];?></b>
		<br />
		<br />
		<img src="<?php echo getthemelocation(); ?>img/loading.gif" />
	</span>
</div>

<?php
echo showinfobox ($hcms_lang['use-ÂoptionsÂ-to-edit-the-video'][$lang], $lang, 8, "position:fixed; top:40px; left:10px; width:90%;", "hcms_infoLayer");
echo showmessage ($show, 600 , 80, $lang, "position:fixed; left:50px; top:150px;");
?> 

<!-- top bar -->
<?php
echo showtopmenubar ($hcms_lang['video-editing'][$lang], array($hcms_lang['options'][$lang] => 'onclick="toggleDivAndButton(this, \'#renderOptions\'); hcms_hideInfo (\'hcms_infoLayer\')"'), $lang, $mgmt_config['url_path_cms']."page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page));
?>

<!-- rendering settings -->
<div id="renderOptions" style="padding:10px; width:730px; vertical-align:top; z-index:1; display: none; margin-left: 10px" class="hcmsMediaRendering">
  <form name="mediaconfig" action="" method="post">
  	<input type="hidden" name="action" value="rendermedia" />
  	<input type="hidden" name="site" value="<?php echo $site; ?>" />
  	<input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  	<input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  	<input type="hidden" name="page" value="<?php echo $page; ?>" />
  	<input type="hidden" name="media" value="<?php echo $mediafile; ?>" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
    
    <?php if (!$audio) { ?>
    
    <!-- video screen format -->
  	<div class="cell">
  		<strong><?php echo $hcms_lang['type'][$lang]; ?></strong><br />
  		<?php foreach ($available_formats as $format => $data) { ?>
        <div class="row">
          <input type="radio" id="format_<?php echo $format; ?>" name="format" value="<?php echo $format; ?>" <?php if ($data['checked']) echo "checked=\"checked\""; ?> /> <label for="format_<?php echo $format; ?>"><?php echo $data['name']; ?></label>
        </div>
  		<?php } ?>
  	</div>
    
    <!-- video bitrate -->
  	<div class="cell">
  		<strong><?php echo $hcms_lang['video-quality'][$lang]; ?></strong><br />
  		<?php foreach ($available_bitrates as $bitrate => $data) { ?>
      <div class="row">
  			<input type="radio" id="bitrate_<?php echo $bitrate; ?>" name="bitrate" value="<?php echo $bitrate; ?>" <?php if ($data['checked']) echo "checked=\"checked\""; ?> /> <label for="bitrate_<?php echo $bitrate; ?>"><?php echo $data['name']; ?></label><br />
      </div>
  		<?php } ?>
  	</div>
    
    <!-- video size -->
  	<div class="cell" style="width:260px;">
  		<strong><?php echo $hcms_lang['video-size'][$lang]; ?></strong><br />
  		<?php foreach ($available_videosizes as $videosize => $data) { ?>
      <div class="row">
  			<input type="radio" id="videosize_<?php echo $videosize; ?>" name="videosize" value="<?php echo $videosize; ?>" <?php if ($data['checked']) echo "checked=\"checked\"";?> /> <label for="videosize_<?php echo $videosize; ?>"<?php if($data['individual']) echo 'onclick="document.getElementById(\'width_'.$videosize.'\').focus();document.getElementById(\'videosize_'.$videosize.'\').checked=true;return false;"'; ?>><?php echo $data['name']; ?></label>
  			<?php if ($data['individual']) { ?>
  				<input type="text" name="width" size=4 maxlength=4 id="width_<?php echo $videosize;?>" value=""><span> x </span><input type="text" name="height" size="4" maxlength=4 id="height_<?php echo $videosize;?>" value="" /><span> <?php echo $hcms_lang['pixel'][$lang]; ?></span>
  			<?php }	?>
  		</div>
  		<?php }	?>
  	</div>
    
    <?php } ?>
    
    <div class="cell">
    <!-- video cut -->
      <input type="checkbox" name="cut" id="cut_yes" onclick="checkCut();" value="1"><strong><label for="cut_yes" onclick="checkCut();" /><?php echo ($audio) ? $hcms_lang['audio-montage'][$lang] : $hcms_lang['video-montage'][$lang]; ?></label></strong>
      <div id="cut_area" style="display:none;">
        <div class="row">
          <label for="cut_start" style="width: 70px; display:inline-block; vertical-align: middle;"><?php echo $hcms_lang['start'][$lang]; ?></label>
          <input id="cut_start" type="button" value="<?php echo $hcms_lang['set'][$lang]; ?>" onclick="updateField(document.getElementById('cut_begin'));" class="cellButton" />
          <input type="text" name="cut_begin" id="cut_begin" READONLY style="width:70px; text-align:center; vertical-align:middle;" />
        </div>
        <div class="row">
          <label for="cut_stop" style="width: 70px; display:inline-block; vertical-align: middle;"><?php echo $hcms_lang['end'][$lang]; ?></label>
          <input id="cut_stop" type="button" value="<?php echo $hcms_lang['set'][$lang]; ?>" onclick="updateField(document.getElementById('cut_end'));" class="cellButton" />
          <input type="text" name="cut_end" id="cut_end" READONLY style="width:70px; text-align:center; vertical-align:middle;" />
        </div>
      </div>
      
    <?php if (!$audio) { ?>
    <!-- video thumbnail -->
      <div class="row"> 
        <input type="checkbox" name="thumb" id="thumb_yes" onclick="checkThumb();" value="1"><strong><label for="thumb_yes" onclick="checkThumb();" /><?php echo $hcms_lang['pick-preview-image'][$lang]; ?></label></strong>
        <div id="thumb_area" style="display:none;">
            <label for="thumb_frame_select" style="display:inline-block; vertical-align: middle;"><?php echo $hcms_lang['frame'][$lang]; ?></label>
            <input id="thumb_frame_select" type="button" value="<?php echo $hcms_lang['set'][$lang]; ?>" onclick="updateField(document.getElementById('thumb_frame'));" class="cellButton" />
            <input type="text" name="thumb_frame" id="thumb_frame" READONLY style="width:70px; text-align:center; vertical-align:middle;" />
        </div>
      </div>
    <?php } ?>
    </div>

    <!-- audio bitrate -->
    <div class="cell">
  		<strong><?php echo $hcms_lang['audio-quality'][$lang]; ?></strong><br />
  		<?php foreach ($available_audiobitrates as $bitrate => $data) { ?>
      <div class="row">
  			<input type="radio" id="audiobitrate_<?php echo $bitrate; ?>" name="audiobitrate" value="<?php echo $bitrate; ?>" <?php if ($data['checked']) echo "checked=\"checked\""; ?> /> <label for="audiobitrate_<?php echo $bitrate; ?>"><?php echo $data['name']; ?></label><br />
      </div>
  		<?php } ?>
  	</div>
    
    <?php if (!$audio) { ?>
    <!-- sharpness / gamma / brigthness / contrast / saturation -->
    <div class="cell">
      <div class="row">
        <strong><?php echo $hcms_lang['adjust'][$lang]; ?></strong>
      </div>
      <div>
        <input type="checkbox" id="chbx_sharpen" name="use_sharpen" value="1" onclick="toggle_sharpen();" />
        <label style="width:70px; display:inline-block;" for="chbx_sharpen"><?php echo $hcms_lang['sharpen'][$lang]; ?></label>
        <input name="sharpen" type="text" id="sharpen" size="4" value="0" />
      </div>
      <div>
        <input type="checkbox" id="chbx_gamma" name="use_gamma" value="1" onclick="toggle_gamma();" />
        <label style="width:70px; display:inline-block;" for="chbx_gamma"><?php echo $hcms_lang['gamma'][$lang]; ?></label>
        <input name="gamma" type="text" id="gamma" size="4" value="0" />
      </div>
      <div>
        <input type="checkbox" id="chbx_brightness" name="use_brightness" value="0" onclick="toggle_brightness();" />
        <label style="width:70px; display:inline-block;" for="chbx_brightness"><?php echo $hcms_lang['brightness'][$lang]; ?></label>
        <input name="brightness" type="text" id="brightness" size="4" value="0" />
      </div>
      <div>
         <input type="checkbox" id="chbx_contrast" name="use_contrast" value="1" onclick="toggle_contrast();" />
        <label style="width:70px; display:inline-block;" for="chbx_contrast"><?php echo $hcms_lang['contrast'][$lang]; ?></label>
        <input name="contrast" type="text" id="contrast" size="4" value="0" />
      </div>
      <div>
        <input type="checkbox" id="chbx_saturation" name="use_saturation" value="1" onclick="toggle_saturation();" />
        <label style="width:70px; display:inline-block;" for="chbx_saturation"><?php echo $hcms_lang['saturation'][$lang]; ?></label>
        <input name="saturation" type="text" id="saturation" size="4" value="0" />
      </div>
    </div>
    <?php } ?>
    
    <!-- save as video format -->
    <div class="cell">
  		<strong><?php echo $hcms_lang['save-as'][$lang];?></strong><br />
  		<label for="filetype"><?php echo $hcms_lang['file-type'][$lang];?></label>
  		<select name="filetype">
        <?php
        if (!$audio)
        {
        ?>
        <option value="videoplayer" ><?php echo $hcms_lang['for-videoplayer'][$lang]; ?></option>
  			<?php
        }
        
        foreach ($available_extensions as $ext => $name)
        { 
          if (!$audio || strpos ("_".$hcms_ext['audio'].".", ".".strtolower($name).".") > 0)
          { 
          ?>
  				<option value="<?php echo $ext; ?>"><?php echo $name; ?></option>
          <?php  
          } 
        }
        ?>
  		</select>
  	</div>
    
    <!-- save button -->
  	<div class="cell" style="vertical-align:bottom;">
  		<input class="hcmsButtonGreen" type="button" name="save" onclick="hcms_showHideLayers('savelayer','','show'); document.forms['mediaconfig'].submit();" value="<?php echo $hcms_lang['save'][$lang];?>"/>
  	</div>
    
  </form>
</div>

<!-- media view -->
<div style="margin:0; padding:10px; width:700px; height:500px; display:inline-block; z-index:1;">
  <!-- show video -->
  <?php echo $playercode; ?>
</div>

</body>
</html>