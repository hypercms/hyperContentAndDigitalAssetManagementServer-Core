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
// language file
require_once ("language/media_rendering.inc.php");


function startConversion ($videotype) 
{
  // Needed for createmedia
  global $mgmt_config, $mgmt_imagepreview, $mgmt_mediapreview, $mgmt_mediaoptions, $mgmt_imageoptions, $mgmt_maxsizepreview, $mgmt_mediametadata;
  // Used for $mgmt_mediaoptions
  global $filetype, $cut_add, $bitrate, $audiobitrate, $width, $height, $ffmpeg_options;
  // Used for createmedia
  global $site, $media_root, $file_info;
  // Used for createthumbnail_video
  global $thumb_frame, $thumb;
   // Used for Output
  global $text1, $text2, $text30, $text31, $lang;
    
  // FFMPEG options
  $mgmt_mediaoptions['.'.$filetype] = $cut_add.str_replace (array('%bitrate%', '%audiobitrate%', '%width%', '%height%'), array($bitrate, $audiobitrate, $width, $height), $ffmpeg_options[$filetype]);
    
  // create video
  if ($videotype == "videoplayer") $videotype = "thumbnail";
  else $videotype = "video";
  
  $result = createmedia ($site, $media_root, $media_root, $file_info['file'], $filetype, $videotype);

  if ($result == false)
  {
    $show = str_replace ('%filetype%', $filetype, $text1[$lang]);
  }
  else
  {
    if ($thumb == 0 || ($match1 = preg_match ("/\d{1,2}:\d{1,2}:\d{1,2}(.\d){0,3}/", $thumb_frame)) && ($match2 = createthumbnail_video ($site, $media_root, $media_root, $file_info['file'], $thumb_frame)))
    {
      $show = str_replace ('%filetype%', $filetype, $text2[$lang]);
    } 
    else
    {
      if (!$match1) $show = $text30[$lang];
      else $show = $text31[$lang];
    }
  }
  
  return $show;
}


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
$cut = getrequest("cut", "numeric", 0);
$cut_begin = getrequest("cut_begin");
$cut_end = getrequest("cut_end");
$thumb = getrequest("thumb", "numeric", 0);
$thumb_frame = getrequest("thumb_frame");

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
	'name'					 => $text3[$lang],
	'checked'				 => false
);

$available_formats['ws'] = array(
	'name'					 => $text4[$lang],
	'checked'				 => true
);

// available bitrates
$available_bitrates = array();

$available_bitrates['200k'] = array(
	'name'					=> $text8[$lang].' (200k)',
	'checked'				=> false
);

$available_bitrates['768k'] = array(
	'name'					=> $text9[$lang].' (768k)',
	'checked'				=> true
);

$available_bitrates['1856k'] = array(
	'name'		 => $text10[$lang].' (1856k)',
	'checked'	 => false
);

// availbale video sizes
$available_videosizes = array();

$available_videosizes['s'] = array(
	'name'					=> $text6[$lang],
	'checked'				=> false,
	'individual'		=> false
);

$available_videosizes['l'] = array(
	'name'					=> $text7[$lang],
	'checked'				=> true,
	'individual'		=> false
);

$available_videosizes['xl'] = array(
	'name'					=> $text18[$lang],
	'checked'				=> false,
	'individual'		=> false
);

$available_videosizes['i'] = array(
	'name'		 => $text19[$lang],
	'checked'	 => false,
	'individual' => true
);

//available bitrates for the audio
$available_audiobitrates = array();

$available_audiobitrates['64k'] = array(
  'name'    => $text8[$lang].' (64k)',
  'checked' => true
);

$available_audiobitrates['128k'] = array(
  'name'    => $text9[$lang].' (128k)',
  'checked' => false
);

$available_audiobitrates['192k'] = array(
  'name'    => $text10[$lang].' (192k)',
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
  // convert the media file with FFMPEG
  // Audio Options:
  // -ac ... channels
  // -acodec ... audio codec
  // -ab ... audio bitrate (default = 64k)
  // -ar ... audio sampling frequency (default = 44100 Hz)
  // Video Options:
  // -b:v ... video bitrate in bit/s (default = 200 kb/s)
  // -r ... frame rate in Hz (default = 25)
  // -s ... frame size in pixel (w x h) 
$ffmpeg_options['flv'] = "-b:v %bitrate% -s:v %width%x%height% -f flv -c:a libmp3lame -ab %audiobitrate% -ac 2 -ar 22050";
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
  $cut_add = '';
  
  if ($cut == 1)
  {
    $starttime = DateTime::createFromFormat('H:i:s.u', $cut_begin);
    $endtime = DateTime::createFromFormat('H:i:s.u', $cut_end);
    $duration = $starttime->diff($endtime);
    
    list ($duh, $startmsec) = explode (".", $cut_begin);
    list ($duh, $endmsec) = explode (".", $cut_end);
    
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
        
    $cut_add = '-ss '.$starttime->format('H:i:s').'.'.$startmsec.' -t '.$duration->format('%H:%I:%S').'.'.$durationmsec.' '; 
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
        // only capture video screen for tzhumbnail image for the first video
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
  $playercode = showmedia ($site."/".$mediafile, $pagefile_info['name'], "preview_no_rendering", "cut_video", $mediawidth, $mediaheight, "");
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
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang];?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" type="text/css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/jquery/jquery-1.9.1.min.js"></script>
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
    alert (hcms_entity_decode('<?php echo $text26[$lang]; ?>'));
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
    opener.parent.frames['objFrame'].location.href='page_view.php?ctrlreload=yes&site=<?php echo $site; ?>&cat=<?php echo $cat; ?>&location=<?php echo $location_esc; ?>&page=<?php echo $page; ?>';
  }
  
  return true;
}

function toggleDivAndButton (caller, element)
{
  var options = $(element);
  caller = $(caller);
  var time = 500;
    
  if (options.css('display') == 'none')
  {
    caller.addClass('hcmsButtonActive');
    options.fadeIn(time);
  }
  else
  {
    caller.removeClass('hcmsButtonActive');
    options.fadeOut(time);
  }
}

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
		<b><?php echo $text17[$lang];?></b>
		<br />
		<br />
		<img src="<?php echo getthemelocation(); ?>img/loading.gif" />
	</span>
</div>

<?php
echo showmessage ($show, 600 , 80, $lang, "position:absolute; left:50px; top:150px;");
?> 

<!-- top bar -->
<?php
echo showtopmenubar ($text0[$lang], array($text33[$lang] => 'onclick="toggleDivAndButton(this, \'#renderOptions\');"'), $lang, $mgmt_config['url_path_cms']."page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page));
?>

<!-- rendering settings -->
<div id="renderOptions" style="padding:10px; width:730px; vertical-align:top; z-index:1; display: none; margin-left: 10px" class="hcmsMediaRendering">
  <form name="mediaconfig" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
  	<input type="hidden" name="action" value="rendermedia" />
  	<input type="hidden" name="site" value="<?php echo $site; ?>" />
  	<input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  	<input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  	<input type="hidden" name="page" value="<?php echo $page; ?>" />
  	<input type="hidden" name="media" value="<?php echo $mediafile; ?>" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
    
    <?php if(!$audio) { ?>
  	<div class="cell">
  		<strong><?php echo $text11[$lang]; ?></strong><br />
  		<?php foreach ($available_formats as $format => $data) { ?>
        <div class="row">
          <input type="radio" id="format_<?php echo $format; ?>" name="format" value="<?php echo $format; ?>" <?php if ($data['checked']) echo "checked=\"checked\""; ?> /> <label for="format_<?php echo $format; ?>"><?php echo $data['name']; ?></label>
        </div>
  		<?php } ?>
  	</div>
  	<div class="cell">
  		<strong><?php echo $text13[$lang]; ?></strong><br />
  		<?php foreach ($available_bitrates as $bitrate => $data) { ?>
      <div class="row">
  			<input type="radio" id="bitrate_<?php echo $bitrate; ?>" name="bitrate" value="<?php echo $bitrate; ?>" <?php if ($data['checked']) echo "checked=\"checked\""; ?> /> <label for="bitrate_<?php echo $bitrate; ?>"><?php echo $data['name']; ?></label><br />
      </div>
  		<?php } ?>
  	</div>
  	<div class="cell" style="width:260px;">
  		<strong><?php echo $text12[$lang]; ?></strong><br />
  		<?php foreach ($available_videosizes as $videosize => $data) { ?>
      <div class="row">
  			<input type="radio" id="videosize_<?php echo $videosize; ?>" name="videosize" value="<?php echo $videosize; ?>" <?php if ($data['checked']) echo "checked=\"checked\"";?> /> <label for="videosize_<?php echo $videosize; ?>"<?php if($data['individual']) echo 'onclick="document.getElementById(\'width_'.$videosize.'\').focus();document.getElementById(\'videosize_'.$videosize.'\').checked=true;return false;"'; ?>><?php echo $data['name']; ?></label>
  			<?php if ($data['individual']) { ?>
  				<input type="text" name="width" size=4 maxlength=4 id="width_<?php echo $videosize;?>" value=""><span> x </span><input type="text" name="height" size="4" maxlength=4 id="height_<?php echo $videosize;?>" value="" /><span> <?php echo $text20[$lang]; ?></span>
  			<?php }	?>
  		</div>
  		<?php }	?>
  	</div>
    <?php } ?>
    <div class="cell">
      <input type="checkbox" name="cut" id="cut_yes" onclick="checkCut();" value="1"><strong><label for="cut_yes" onclick="checkCut();" /><?php echo ($audio) ? $text34[$lang] : $text23[$lang]; ?></label></strong>
      <div id="cut_area" style="display:none;">
        <div>
          <label for="cut_start" style="width: 70px; display:inline-block; vertical-align: middle;"><?php echo $text25[$lang]; ?></label>
          <input id="cut_start" type="button" value="<?php echo $text24[$lang]; ?>" onclick="updateField(document.getElementById('cut_begin'));" class="cellButton" />
          <input type="text" name="cut_begin" id="cut_begin" READONLY style="width:70px; text-align:center; vertical-align:middle;" />
        </div>
        <div class="row">
          <label for="cut_stop" style="width: 70px; display:inline-block; vertical-align: middle;"><?php echo $text27[$lang]; ?></label>
          <input id="cut_stop" type="button" value="<?php echo $text24[$lang]; ?>" onclick="updateField(document.getElementById('cut_end'));" class="cellButton" />
          <input type="text" name="cut_end" id="cut_end" READONLY style="width:70px; text-align:center; vertical-align:middle;" />
        </div>
      </div>
    </div>
    <?php if(!$audio) { ?>
    <div class="cell">
      <input type="checkbox" name="thumb" id="thumb_yes" onclick="checkThumb();" value="1"><strong><label for="thumb_yes" onclick="checkThumb();" /><?php echo $text28[$lang]; ?></label></strong>
      <div id="thumb_area" style="display:none;">
        <div>
          <label for="thumb_frame_select" style="display:inline-block; vertical-align: middle;"><?php echo $text29[$lang]; ?></label>
          <input id="thumb_frame_select" type="button" value="<?php echo $text24[$lang]; ?>" onclick="updateField(document.getElementById('thumb_frame'));" class="cellButton" />
          <input type="text" name="thumb_frame" id="thumb_frame" READONLY style="width:70px; text-align:center; vertical-align:middle;" />
        </div>
      </div>
    </div>
    <?php } ?>
    <div class="cell">
  		<strong><?php echo $text35[$lang]; ?></strong><br />
  		<?php foreach ($available_audiobitrates as $bitrate => $data) { ?>
      <div class="row">
  			<input type="radio" id="audiobitrate_<?php echo $bitrate; ?>" name="audiobitrate" value="<?php echo $bitrate; ?>" <?php if ($data['checked']) echo "checked=\"checked\""; ?> /> <label for="audiobitrate_<?php echo $bitrate; ?>"><?php echo $data['name']; ?></label><br />
      </div>
  		<?php } ?>
  	</div>
    <div class="cell">
  		<strong><?php echo $text21[$lang];?></strong><br />
  		<label for="filetype"><?php echo $text22[$lang];?></label>
  		<select name="filetype">
        <?php if (!$audio) { ?>
        <option value="videoplayer" ><?php echo $text32[$lang]; ?></option>
  			<?php 
        }
        foreach ($available_extensions as $ext => $name)
        { 
          if (!$audio || in_array($name, array('MP3')))
          { 
          ?>
  				<option value="<?php echo $ext; ?>"><?php echo $name; ?></option>
          <?php  
          } 
        }
        ?>
  		</select>
  	</div>
  	<div class="cell">
  		<input class="hcmsButtonGreen" type="button" name="save" onclick="hcms_showHideLayers('savelayer','','show'); document.forms['mediaconfig'].submit();" value="<?php echo $text14[$lang];?>"/>
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