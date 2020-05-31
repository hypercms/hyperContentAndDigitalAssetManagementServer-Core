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
// quality
$bitrate = getrequest ("bitrate");
$audiobitrate = getrequest ("audiobitrate");
// size
$videosize = getrequest ("videosize");
$width = getrequest ("width", "numeric");
$height = getrequest ("height", "numeric");
// cut
$cut = getrequest ("cut", "numeric", 0);
$cut_begin = getrequest ("cut_begin");
$cut_end = getrequest ("cut_end");
// thumbnail
$thumb = getrequest ("thumb", "numeric", 0);
$thumb_frame = getrequest ("thumb_frame");
// effects
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

$show = "";

// load object file and get container and media file
$objectdata = loadfile ($location, $page);
$mediafile = getfilename ($objectdata, "media");

// get object file information
$pagefile_info = getfileinfo ($site, $page, $cat);

// get media file info
$media_root = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";
$file_info = getfileinfo ($site, $mediafile, $cat);

// if audio file
$is_audio = is_audio ($file_info['ext']);

// read supported formats
$available_extensions = array();

if (is_array ($mgmt_mediaoptions))
{
  foreach ($mgmt_mediaoptions as $ext => $options)
  {
    if ($ext != "thumbnail-video" && $ext != "thumbnail-audio" && $ext != "autorotate-video")
    {
    	// remove the dot
    	$name = strtolower (trim ($ext, "."));    
    	$available_extensions[$name] = strtoupper ($name);
    }
  }
}

// include media options
require ($mgmt_config['abs_path_cms']."include/mediaoptions.inc.php");

// verify input parameters and define video settings
if ($filetype != "" && (array_key_exists ($filetype, $available_extensions) || strtolower ($filetype) == 'videoplayer' || strtolower ($filetype) == 'original')) $filetype = strtolower ($filetype);
else $filetype = "videoplayer";

if ($format != "" && array_key_exists ($format, $available_formats)) $format = $format;
else $format = "fs";

if ($bitrate != "" && array_key_exists ($bitrate, $available_bitrates)) $bitrate = $bitrate;
else $bitrate = "";

if ($audiobitrate != "" && array_key_exists ($audiobitrate, $available_audiobitrates)) $audiobitrate = $audiobitrate;
else $audiobitrate = "";

if ($videosize != "" && array_key_exists ($videosize, $available_videosizes)) $videosize = $videosize;
else $videosize = "";

// generate media preview (media player)
if ($hcms_ext['video'] != "" && $hcms_ext['audio'] != "")
{
  // set default width for video preview
  $mediawidth = 576;
  $mediaheight = 0;

  // generate player code
  $playercode = showmedia ($site."/".$mediafile, $pagefile_info['name'], "preview_download", "hcms_mediaplayer_edit", $mediawidth, $mediaheight, "");
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
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" type="text/css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/jquery/jquery-1.12.4.min.js"></script>
<script src="javascript/jquery-ui/jquery-ui-1.12.1.min.js"></script>
<link rel="stylesheet" href="javascript/jquery-ui/jquery-ui-1.12.1.css" type="text/css" />
<?php 
if ($is_audio) echo showaudioplayer_head (false);
else echo showvideoplayer_head (false); 
?>
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

#renderOptions input[type=text], #renderOptions select
{
  padding: 3px;
}
</style>
<script type="text/javascript">
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

<?php if (!$is_audio) { ?>
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

function submitform ()
{
  var errors = '';
  
  if (document.getElementById('cut_yes') && document.getElementById('cut_yes').checked == true)
  {
    if (document.getElementById('cut_begin').value == "") errors += '- <?php echo getescapedtext ($hcms_lang['start'][$lang]).": ".getescapedtext ($hcms_lang['a-value-is-required'][$lang]); ?>\n';
    if (document.getElementById('cut_end').value == "") errors += '- <?php echo getescapedtext ($hcms_lang['end'][$lang]).": ".getescapedtext ($hcms_lang['a-value-is-required'][$lang]); ?>\n';
  }
  
  if (document.getElementById('thumb_yes') && document.getElementById('thumb_yes').checked == true)
  {
    if (document.getElementById('thumb_frame').value == "") errors += '- <?php echo getescapedtext ($hcms_lang['frame'][$lang]).": ".getescapedtext ($hcms_lang['a-value-is-required'][$lang]); ?>\n';
  }
  
  if (document.getElementById('videosize_i') && document.getElementById('videosize_i').checked == true)
  {
    if (document.getElementById('width_i').value == "") errors += '- <?php echo getescapedtext ($hcms_lang['width'][$lang]).": ".getescapedtext ($hcms_lang['a-value-is-required'][$lang]); ?>\n';
    if (document.getElementById('height_i').value == "") errors += '- <?php echo getescapedtext ($hcms_lang['height'][$lang]).": ".getescapedtext ($hcms_lang['a-value-is-required'][$lang]); ?>\n';
  }
  
  if (errors) 
  { 
    alert(hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['the-following-error-occurred'][$lang]); ?>\n ' + errors));
    return false;
  }
  else
  {
    var filetype = document.getElementById('filetype');
    
    if (filetype.options[filetype.selectedIndex].value == "original")
    {
      if (!confirm(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-overwrite-the-original-file'][$lang]); ?>"))) return false;
    }
  
    hcms_showHideLayers('savelayer','','show');
    document.forms['mediaconfig'].submit();
  }
}

function openerReload ()
{
  // reload main frame
  if (opener != null && eval (opener.parent.frames['mainFrame']))
  {
    opener.parent.frames['mainFrame'].location.reload();
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

function toggle_rotate () 
{
  var rotate = $('#rotate');
  var chbxflip = $('#chbx_flip');
  var degree = $('#degree');
  
  if(rotate.prop('checked')) 
  {
    chbxflip.prop('checked', false);
    degree.prop('disabled', false);
    
    toggle_flip();   
  }
  else
  {
    degree.prop('disabled', true);
  }
}

function toggle_flip () 
{
  var rotate = $('#rotate');
  var chbxflip = $('#chbx_flip');
  var flip = $('#flip');
  var crop = $('#crop');
  
  if (chbxflip.prop('checked')) 
  {
    rotate.prop('checked', false);
    flip.prop('disabled', false);
    crop.prop('checked', false);
    
    toggle_rotate();
    toggle_crop();
  }
  else
  {
    flip.prop('disabled', true);
  }
}

function toggle_options (caller, element)
{
  var options = $(element);
  caller = $(caller);
  var time = 500;
    
  if (options.css('display') == 'none')
  {
    caller.addClass('hcmsButtonMenuActive');
    activate();
    options.slideDown(time);
    window.scrollTo(0,0);
  }
  else
  {
    caller.removeClass('hcmsButtonMenuActive');
    options.slideUp(time);
  }
}

function activate ()
{
  toggle_sharpen();
  toggle_gamma();
  toggle_brightness();
  toggle_contrast();
  toggle_saturation();
  toggle_flip();
  toggle_rotate();
}

function updateField (field)
{
  if (getplayertime() !== false) field.value = getplayertime();
  else field.value = "00:00:00.00";
}

function getplayertime ()
{ 
  <?php  
  // if we use projekktor we need to check for the state beforehand
  if (strtolower ($mgmt_config['videoplayer']) == "projekktor") { 
  ?>
  var player = projekktor('hcms_mediaplayer_edit');
  
  if (player.getState('PLAYING') || player.getState('PAUSED'))
  {
    var playerseconds = player.getPosition();
  }
  else
  {
    alert (hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['videoplayer-must-be-playing-or-paused-to-set-start-and-end-positions'][$lang]); ?>'));
    return 0;
  }
  <?php 
  // VIDEO JS
  } else {
  ?>
  var player = videojs("hcms_mediaplayer_edit");  
  var playerseconds = player.currentTime();
  <?php 
  }
  ?>

  if (playerseconds > 0)
  {
    var hours = Math.floor(playerseconds / 3600) % 24;    
    if (hours < 10) hours = "0" + hours;
    
    var minutes = Math.floor(playerseconds / 60) % 60;
    if (minutes < 10) minutes = "0" + minutes;
    
    var seconds = Math.floor(playerseconds) % 60;
    if (seconds < 10) seconds = "0" + seconds;

    var milliseconds = Math.round(playerseconds * 1000) / 1000;
    var comma = milliseconds.toString().indexOf('.');
    var milliseconds = milliseconds.toString().substring(comma + 1);
    milliseconds = parseInt(milliseconds);
    if (milliseconds < 10) milliseconds = "00" + milliseconds;
    else if (milliseconds < 100) milliseconds = "0" + milliseconds;

    return hours + ':' + minutes + ':' + seconds + '.' + milliseconds;
  }
  else return false;
}

function getplayerduration ()
{
  if ($('#mediaplayer_duration').val())
  {
    return $('#mediaplayer_duration').val();
  }
  else return false;
}

function convert2seconds (time)
{
  if (time != "")
  {
    // cut off milliseconds (since we want to use the previous video frame)
    if (time.indexOf('.') > 0)
    {
      var ms = time.substring(time.indexOf('.'));
      time = time.substring(0, time.indexOf('.'));
    }
    else var ms = 0;
    
    if (time.indexOf(':') > 0)
    {
      // split time at the colons
      var parts = time.split(':');

      // minutes are worth 60 seconds, hours are worth 60 minutes
      var seconds = (+parts[0]) * 60 * 60 + (+parts[1]) * 60 + (+parts[2]) + ms;
    }
    else var seconds = time + ms;
    
    return seconds;
  }
  else return false;
}

// add 0 to add up to given max length
function addzeros (str, max)
{
  str = str.toString();
  return str.length < max ? addzeros ("0" + str, max) : str;
}

// create id as string
function createid (str)
{
  var max = 10;
  str = str.toString();
  
  // correct comma digits
  var n = str.lastIndexOf('.');
  var number = str.substring(0, n);
  var commas = str.substring(n + 1);

  if (commas.length < 2) commas = commas + "00";
  else if (commas.length < 3) commas = commas + "0";
  else if (commas.length > 3) commas = commas.substring(0, 2);
  
  str = number + commas;

  // add 0 to add up to given max length
  return addzeros (str, max);
}

var segments = {};

function setbreakpoint ()
{
  var time = getplayertime ();
  var duration_time = getplayerduration();
  
  if (time != "" && duration_time != "")
  {
    var duration_ms = convert2seconds (duration_time);
    var seconds_ms = convert2seconds (time);
    var id = createid (seconds_ms);
    var width_bar = document.getElementById('mediaplayer_segmentbar').offsetWidth;
    var left = Math.floor(seconds_ms / duration_ms * width_bar);

    // time for split must be greater than zero
    if (parseFloat(seconds_ms) > 0 && parseFloat(seconds_ms) < parseFloat(duration_ms))
    {
      // limits for left
      if (parseInt(left) < 1) left = 1;
      else if (parseInt(left) > parseInt(width_bar)) left = width_bar;

      // save split time in object of segment
      segments[id] = { time:time, seconds:seconds_ms, left:left, keep:'1' };

      // add last segment if it does not exist
      var duration_id = createid (duration_ms);

      if (segments.hasOwnProperty(duration_id) == false)
      {
        segments[duration_id] = { time:duration_time, seconds:duration_ms, left:width_bar, keep:'1' };
      }
      
      setsegements();
    }
    else return false;
  }
  else return false;
}

function deletebreakpoint (id)
{
  if (id != "" && typeof segments === 'object')
  {
    delete segments[id];
    setsegements();
    return true;
  }
  else return false;
}

function setsegements ()
{
  if (typeof segments === 'object')
  {
    var width_bar = document.getElementById('mediaplayer_segmentbar').offsetWidth;
    
    // clean segment bar
    document.getElementById('mediaplayer_segmentbar').innerHTML = '';
    
    // sort segments by plsit time
    segments = hcms_sortObjectKey (segments);
    
    // write segments as JSON string to hidden field
    var json_string = JSON.stringify (segments);
    $('#segments').val(json_string);

    var left = 0;

    for (var id in segments)
    {
      if (segments.hasOwnProperty(id))
      {
        var segment = segments[id];
        var time = segment.time;
        var width = segment.left - left;
        var left = segment.left;
        var keep = segment.keep;

        // color of segment
        if (parseInt(keep) < 1) var segment_color = 'background-color:#FC6F65; ';
        else var segment_color = '';
      
        // create div for segment
        var div = document.createElement('div');
        div.style.cssText = segment_color + 'cursor:pointer; display:inline-block; width:' + width + 'px; height:22px;';
        
        // define delete button for split
        var segment_delete = '<div style="float:right; line-height:22px;"><img src="<?php echo getthemelocation(); ?>img/button_delete.png" onclick="deletebreakpoint(\'' + id + '\')" class="hcmsButtonTiny hcmsIconList" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" /></div>';    

        // define split
        if ((parseInt(width_bar) - parseInt(left)) > 0)
        {
          var segment_split = '<div style="background-color:#A20303; display:inline-block; width:1px; height:100%;" onmouseover="$(\'#popup_' + id + '\').show();" onmouseout="$(\'#popup_' + id + '\').hide();"><div style="background-color:#A20303; position:relative; top:22px; left:-3px; width:7px; height:7px;"><div id="popup_' + id + '" class="hcmsInfoBox" style="white-space:nowrap; position:absolute; top:7px; left:-60px; width:120px; height:22px; padding:4px; display:none;"><div style="float:left; line-height:22px;">' + time + '&nbsp;</div>' + segment_delete + '</div></div></div>';
        }
        else var segment_split = '';
        
        // define segment bar
        var segment_bar = '<div style="display:inline-block; width:' + (width - 1) + 'px; height:100%; text-align:center;" onclick="keepsegment(\'' + id + '\')" title="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>"></div>';
        
        div.innerHTML = segment_bar + segment_split;
        
        document.getElementById('mediaplayer_segmentbar').appendChild(div);
      }
    }
  }
  else return false;
}

function keepsegment (id)
{
  if (id != "" && typeof segments === 'object' && segments.hasOwnProperty(id))
  {
    var segment = segments[id];
    var keep = segment['keep'];

    if (keep < 1) keep = 1;
    else keep = 0;
    
    segments[id]['keep'] = keep;
    
    setsegements();
    return true;
  }
  else return false;
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

$().ready(function() {
  <?php if (!$is_audio) { ?>checkThumb();<?php } ?>
  
  // hide mebed button
  $('#mediaplayer_embed').hide();
  
  // show videoplayers segment bar and buttons
  $('#mediaplayer_segmentbar').show();
  $('#mediaplayer_cut').show();
  $('#mediaplayer_options').show();
});
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- saving --> 
<div id="savelayer" class="hcmsWorkplaceGeneric" style="position:fixed; width:100%; height:100%; margin:0; padding:0; left:0px; top:0px; visibility:hidden; z-index:10;">
	<span style="position:absolute; top:50%; height:150px; margin-top:-75px; width:200px; left:50%; margin-left:-100px;">
		<b><?php echo getescapedtext ($hcms_lang['the-file-is-being-processed'][$lang]);?></b>
		<br />
		<br />
		<img src="<?php echo getthemelocation(); ?>img/loading.gif" />
	</span>
</div>

<?php
echo showinfobox ($hcms_lang['use-options-to-edit-the-video'][$lang], $lang, "position:fixed; top:40px; left:10px; width:760px;", "hcms_infoLayer");
echo showmessage ($show, 600 , 80, $lang, "position:fixed; left:50px; top:150px;");
?> 

<!-- top bar -->
<?php
echo showtopmenubar ($hcms_lang['video'][$lang], array($hcms_lang['options'][$lang] => 'onclick="toggle_options(this, \'#renderOptions\'); hcms_hideFormLayer(\'hcms_infoLayer\')"'), $lang, $mgmt_config['url_path_cms']."page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page));
?>

<!-- rendering settings -->
<div id="renderOptions" style="padding:0px 5px 10px 5px; width:740px; vertical-align:top; z-index:1; display:none; margin:-4px 10px 0px 10px;" class="hcmsMediaRendering">
  <form name="mediaconfig" action="service/rendervideo.php" method="post">
  	<input type="hidden" name="action" value="rendermedia" />
    <input type="hidden" name="savetype" value="editor_so">
  	<input type="hidden" name="site" value="<?php echo $site; ?>" />
  	<input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  	<input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  	<input type="hidden" name="page" value="<?php echo $page; ?>" />
  	<input type="hidden" name="media" value="<?php echo $mediafile; ?>" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
    <input type="hidden" id="segments" name="segments" value="" />
    
    <?php if (!$is_audio) { ?>
    <div class="cell" style="width:260px;">
      <!-- video screen format -->
      <div class="row">
    		<strong><?php echo getescapedtext ($hcms_lang['formats'][$lang]); ?></strong>
  		<?php foreach ($available_formats as $format => $data) { ?>
        <div class="row">
          <input type="radio" id="format_<?php echo $format; ?>" name="format" value="<?php echo $format; ?>" <?php if ($data['checked']) echo "checked=\"checked\""; ?> />
          <label for="format_<?php echo $format; ?>"><?php echo $data['name']; ?></label>
        </div>
  		<?php } ?>
  	  </div>
    
      <!-- video size -->
    	<div class="row">
    		<strong><?php echo getescapedtext ($hcms_lang['video-size'][$lang]); ?></strong>
      </div>
  		<?php foreach ($available_videosizes as $videosize => $data) { ?>
      <div class="row">
  			<input type="radio" id="videosize_<?php echo $videosize; ?>" name="videosize" value="<?php echo $videosize; ?>" <?php if ($data['checked']) echo "checked=\"checked\"";?> /> <label for="videosize_<?php echo $videosize; ?>" <?php if ($data['individual']) echo 'onclick="document.getElementById(\'width_'.$videosize.'\').focus();document.getElementById(\'videosize_'.$videosize.'\').checked=true;return false;"'; ?>><?php echo $data['name']; ?></label>
  			<?php if ($data['individual']) { ?>
  		  <input type="text" name="width" size=4 maxlength=4 id="width_<?php echo $videosize;?>" value=""><span> x </span><input type="text" name="height" size="4" maxlength=4 id="height_<?php echo $videosize;?>" value="" /><span> px</span>
  			<?php }	?>
  		</div>
  		<?php }	?>
  	</div>
    <?php } ?>

    <?php if (!$is_audio) { ?>
    <!-- sharpness / gamma / brigthness / contrast / saturation -->
    <div class="cell">
      <div class="row">
        <strong><?php echo getescapedtext ($hcms_lang['adjust'][$lang]); ?></strong>
      </div>
      <div>
        <input type="checkbox" id="chbx_sharpen" name="use_sharpen" value="1" onclick="toggle_sharpen();" />
        <label style="width:70px; display:inline-block;" for="chbx_sharpen"><?php echo getescapedtext ($hcms_lang['sharpen'][$lang]); ?></label>
        <input name="sharpen" type="text" id="sharpen" size="4" value="0" />
      </div>
      <div>
        <input type="checkbox" id="chbx_gamma" name="use_gamma" value="1" onclick="toggle_gamma();" />
        <label style="width:70px; display:inline-block;" for="chbx_gamma"><?php echo getescapedtext ($hcms_lang['gamma'][$lang]); ?></label>
        <input name="gamma" type="text" id="gamma" size="4" value="0" />
      </div>
      <div>
        <input type="checkbox" id="chbx_brightness" name="use_brightness" value="0" onclick="toggle_brightness();" />
        <label style="width:70px; display:inline-block;" for="chbx_brightness"><?php echo getescapedtext ($hcms_lang['brightness'][$lang]); ?></label>
        <input name="brightness" type="text" id="brightness" size="4" value="0" />
      </div>
      <div>
         <input type="checkbox" id="chbx_contrast" name="use_contrast" value="1" onclick="toggle_contrast();" />
        <label style="width:70px; display:inline-block;" for="chbx_contrast"><?php echo getescapedtext ($hcms_lang['contrast'][$lang]); ?></label>
        <input name="contrast" type="text" id="contrast" size="4" value="0" />
      </div>
      <div>
        <input type="checkbox" id="chbx_saturation" name="use_saturation" value="1" onclick="toggle_saturation();" />
        <label style="width:70px; display:inline-block;" for="chbx_saturation"><?php echo getescapedtext ($hcms_lang['saturation'][$lang]); ?></label>
        <input name="saturation" type="text" id="saturation" size="4" value="0" />
      </div>
    </div>
    <?php }	?>
 
    <?php if (!$is_audio) { ?>
    <div class="cell">      
      <!-- video thumbnail -->
      <div class="row"> 
        <input type="checkbox" name="thumb" id="thumb_yes" onclick="checkThumb();" value="1" />
        <strong><label for="thumb_yes" onclick="checkThumb();" /><?php echo getescapedtext ($hcms_lang['pick-preview-image'][$lang]); ?></label></strong>
      </div>
      <div id="thumb_area" style="display:none;">
          <label for="thumb_frame_select" style="width:70px; display:inline-block; vertical-align:middle;"><?php echo getescapedtext ($hcms_lang['frame'][$lang]); ?></label>
          <input id="thumb_frame_select" type="button" value="<?php echo getescapedtext ($hcms_lang['set'][$lang]); ?>" onclick="updateField(document.getElementById('thumb_frame'));" class="cellButton" />
          <input type="text" name="thumb_frame" id="thumb_frame" READONLY style="width:70px; text-align:center; vertical-align:middle;" />
      </div>
       
      <!-- rotate -->
      <div class="row">
        <input type="checkbox" id="rotate" name="rotate" value="rotate" onclick="toggle_rotate();" />
        <strong><label for="rotate" style="width:65px; display:inline-block; vertical-align:middle;"><?php echo getescapedtext ($hcms_lang['rotate'][$lang]); ?></label></strong>
        <select name="degree" id="degree" style="margin-left:20px">
          <option value="90" selected="selected" >90&deg;</option>
          <option value="180" >180&deg;</option>
          <option value="-90" title="-90&deg;">270&deg;</option>
        </select>
      </div>

      <!-- vflip hflip -->
      <div class="row">
        <input type="checkbox" id="chbx_flip" name="rotate" value="flip" onclick="toggle_flip();" />
        <strong><label for="chbx_flip" style="width:65px; display:inline-block; vertical-align:middle;"><?php echo getescapedtext ($hcms_lang['flip'][$lang]); ?></label></strong>
        <select name="flip" id="flip" style="margin-left:20px">
          <?php 
            foreach ($available_flip as $value => $name)
            {
            ?>
            <option value="<?php echo $value; ?>"><?php echo $name ?></option>
            <?php
            }
          ?>
        </select>
      </div>
    </div>
    <?php } ?>
    
    <?php if (!$is_audio) { ?>    
    <!-- video bitrate -->
  	<div class="cell" style="width:260px;">
      <div class="row">
  		  <strong><?php echo getescapedtext ($hcms_lang['video-quality'][$lang]); ?></strong>
      </div>
  		<?php foreach ($available_bitrates as $bitrate => $data) { ?>
      <div class="row">
  			<input type="radio" id="bitrate_<?php echo $bitrate; ?>" name="bitrate" value="<?php echo $bitrate; ?>" <?php if ($data['checked']) echo "checked=\"checked\""; ?> /> <label for="bitrate_<?php echo $bitrate; ?>"><?php echo $data['name']; ?></label><br />
      </div>
  		<?php } ?>
  	</div>
    <?php } ?>
    
    <!-- audio bitrate -->
    <div class="cell">
      <div class="row">
  		  <strong><?php echo getescapedtext ($hcms_lang['audio-quality'][$lang]); ?></strong>
      </div>
  		<?php foreach ($available_audiobitrates as $bitrate => $data) { ?>
      <div class="row">
  			<input type="radio" id="audiobitrate_<?php echo $bitrate; ?>" name="audiobitrate" value="<?php echo $bitrate; ?>" <?php if ($data['checked']) echo "checked=\"checked\""; ?> /> <label for="audiobitrate_<?php echo $bitrate; ?>"><?php echo $data['name']; ?></label><br />
      </div>
  		<?php } ?>
  	</div>

    <div class="cell">
      <!-- save as video format -->
      <div class="row">
    		<strong><?php echo getescapedtext ($hcms_lang['save-as'][$lang]);?></strong><br />
    		<label for="filetype"><?php echo getescapedtext ($hcms_lang['file-type'][$lang]);?></label>
    		<select id="filetype" name="filetype">
          <?php
          // check supported extensions to render and overwrite original media file
          if (is_array ($mgmt_mediaoptions))
          {
            foreach ($mgmt_mediaoptions as $mediaoptions_ext => $mediaoptions)
            {
              // if media option exists for the media file
              if (substr_count ($mediaoptions_ext.".", $file_info['ext'].".") > 0)
              {
              ?>
          <option value="original" ><?php echo getescapedtext ($hcms_lang['original'][$lang]); ?></option>
              <?php
              }
            }
          }

          if (!$is_audio)
          {
          ?>
          <option value="videoplayer" ><?php echo getescapedtext ($hcms_lang['for-videoplayer'][$lang]); ?></option>
    			<?php
          }
          
          foreach ($available_extensions as $ext => $name)
          { 
            if (!$is_audio || is_audio (strtolower($name)))
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
      <div class="row" style="margin-top:6px;">
  		  <button type="button" class="hcmsButtonGreen" name="save" onclick="submitform();"><img src="<?php echo getthemelocation()."img/button_save.png"; ?>" class="hcmsIconList" /> <?php echo getescapedtext ($hcms_lang['save'][$lang]);?></button>
      </div>
  	</div>
    
  </form>
</div>

<!-- media view -->
<div class="hcmsWorkplaceFrame">
  <!-- show video -->
  <?php echo $playercode; ?>
</div>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>