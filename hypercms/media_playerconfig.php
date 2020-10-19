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
// extension definitions
include ("include/format_ext.inc.php");


// input parameters
$location = getrequest ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$type = getrequest ("type");
$title = getrequest ("title");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission( $site, $ownergroup, $cat );
if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1 || !valid_publicationname ($site)) killsession ($user);
// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// load object file and get container and media file
$objectdata = loadfile ($location, $page);
$mediafile = getfilename ($objectdata, "media");

// get file information of original component file
$pagefile_info = getfileinfo ($site, $page, $cat);

// get publication and file info
$media_root = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";
$file_info = getfileinfo ($site, $mediafile, "");

$audio = false;
$preview = false;

// video type/format
if ($type != "")
{
  $type = strtolower ($type);
}
elseif (is_audio ($file_info['ext']))
{
  $type = "audio";
  $audio = true;
}
else 
{
  $type = "video";
}

// read config
if ($media_root && file_exists ($media_root.$file_info['filename'].".config.".$type))
{
  $config = readmediaplayer_config ($media_root, $file_info['filename'].".config.".$type);
} 
elseif ($media_root && file_exists ($media_root.$file_info['filename'].".config.orig")) 
{
  $config = readmediaplayer_config ($media_root, $file_info['filename'].".config.orig");

  $preview = true;
  
  // We try to detect if we should use the audio player
  if (is_array ($config['mediafiles']))
  {
    list ($test, $duh) = explode (";", reset($config['mediafiles']));
    $testfinfo = getfileinfo ($site, $test, $cat);
    
    if (is_audio ($testfinfo['ext']))
    {
      $audio = true;
    }
  }
}
else
{
  $config = false;
  $playercode = getescapedtext ($hcms_lang['configuration-not-available'][$lang]);
}

$head = false;

$frameid = rand_secure() + time();

if ($config && is_array ($config))
{
  // video player
  // version 2+
  if (intval ($config['version']) >= 2) 
  {
    $url = $mgmt_config['url_path_cms'].'videoplayer.php?media='.$mediafile.'&site='.$site;
    
    // audio
    if ($audio) 
    {
      $config['width'] = 320;
      $config['height'] = 320;
      $fullscreen = '';
    }
    // video
    else 
    {
      $fullscreen = "allowFullScreen=\\\"true\\\" webkitallowfullscreen=\\\"true\\\" mozallowfullscreen=\\\"true\\\"";
    }
    
    $playercode = "<iframe id=\\\"".$frameid."\\\" style=\\\"width:\" + width + \"px; height:\" + height + \"px; border:0; overflow:hidden;\\\" frameborder=\\\"0\\\" src=\\\"".$url."\" + newurl + \"\\\" ".$fullscreen."></iframe>";
  }
  // older versions
  else
  {
    $head = showvideoplayer_head (false);
    $playercode = $config['data'];
  }

  // create view link for 360 image viewer / video player
  $link = "";

  // use original file if it is an MP4 video and the preview video is only available
  if ($preview == true && substr_count (".mp4.", $file_info['ext'].".") > 0 && (is_file ($media_root.$mediafile) || is_cloudobject ($media_root.$mediafile)))
  {
    $video_file = $mediafile;
  }
  // use file provided by video config
  elseif (is_array ($config['mediafiles']))
  {
    foreach ($config['mediafiles'] as $temp)
    {
      if (strpos ($temp, "video/mp4") > 0 || substr ($temp, -4) == ".mp4") 
      {
        if (strpos ($temp, ";") > 0) list ($video_file, $rest) = explode (";", $temp);
        else $video_file = $temp;
        break;
      }
    }
  }

  if (!empty ($video_file)) $link = createviewlink ($site, $video_file, $video_file, true);
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang);?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script type="text/javascript" src="javascript/main.min.js"></script>
<?php
if ($config && is_array ($config) && intval ($config['version']) >= 2)
{
?>
<script type="text/javascript">

function updateCodeSegment()
{
  <?php if (!$audio) { ?>
  var title = document.getElementById("title").value;
  var fullscreen = document.getElementById("fullscreen").checked;
  var logo = document.getElementById("logo").value;
  var muted = document.getElementById("muted").checked;
  <?php } ?>
  var width = document.getElementById("width").value;
  var height = document.getElementById("height").value;
  var autoplay = document.getElementById("autoplay").checked;
  var loop = document.getElementById("loop").checked;
  var controls = document.getElementById("controls").checked;

  var newurl = "";

  <?php if (!$audio) { ?>
  if (title != "") newurl += '&title='+title;  
  if (logo) newurl += '&logo='+encodeURIComponent(logo);

  if (fullscreen) newurl += '&fullscreen=true';
  else newurl += '&fullscreen=false';

  if (muted) newurl += '&muted=true';
  else newurl += '&muted=false';
  <?php } ?>

  if (width != "") newurl += '&width=' + width;
  if (height != "") newurl += '&height=' + height;

  if (autoplay) newurl += '&autoplay=true';
  else newurl += '&autoplay=false';

  if (loop) newurl += '&loop=true';
  else newurl += '&loop=false';
  
  if (controls) newurl += '&controls=true';
  else newurl += '&controls=false';

  var playercode = "<?php echo $playercode; ?>";

  document.getElementById('codesegment').innerHTML = playercode;
  document.getElementById('playercode').innerHTML = playercode;

  <?php if (!$is_mobile) { ?>
  var playercode360 = "<iframe id=\"<?php echo $frameid; ?>\" src=\"<?php echo $mgmt_config['url_path_cms']; ?>media_360view.php?type=video&link=<?php echo url_encode ($link); ?>" + newurl + "\" title=\"" + title + "\" frameborder=\"0\" style=\"width:" + width + "px; height:" + height + "px; border:0;\" allowFullScreen=\"true\" webkitallowfullscreen=\"true\" mozallowfullscreen=\"true\"></iframe>";

  document.getElementById('codesegment360').innerHTML = playercode360;
  document.getElementById('playercode360').innerHTML = playercode360;
  <?php } ?>
}

// The image selector expects to be a CKEDITOR.tools.callFunction function, so we fake it here
var CKEDITOR = { 
  tools: { 
    callFunction: 
      function(name, link, config) 
      {  
        if(name == 123) {
          document.getElementById("logo").value = link;
          updateCodeSegment();          
        }
      } 
  } 
};

</script>
<?php } ?>
<style>
#settings
{
  width: 24%;
  min-width: 280px;
}

#preview
{
  width: 72%;
  min-width: 640px;
  height: 700px; 
}

@media screen and (max-width: 1080px)
{
  #settings
  {
    width: 100%;
  }

  #preview
  {
    width: 100%;
  }
}
</style>
</head>
    
<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php
echo showtopbar ($hcms_lang['media-player-configuration'][$lang], $lang, $mgmt_config['url_path_cms']."page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page));
?>

<!-- content -->
<div class="hcmsWorkplaceFrame">
  <!-- form  -->
  <div id="settings" style="padding:0px 20px 10px 0px; float:left;">

  <?php
  if ($head)
  {
  ?>
  	<strong><?php echo getescapedtext ($hcms_lang['html-head-segment'][$lang]);?></strong><br />
  	<?php echo getescapedtext ($hcms_lang['mark-and-copy-the-code-from-the-text-area-box-keys-ctrl-a-and-ctrl-c-for-copy-or-right-mouse-button-copy'][$lang]);?><br /><br />       
  	<textarea id="codesegment" style="height: 150px; width: 98%" wrap="VIRTUAL"><?php echo $head; ?></textarea>
  <hr />
  <?php
  }
  
  if ($config && is_array ($config) && intval ($config['version']) >= 2)
  {
  ?>
    <?php if (!$audio) { ?>
    <div>
      <label for="title"><?php echo getescapedtext ($hcms_lang['title'][$lang]);?> </label><br/>
      <input type="text" onchange="updateCodeSegment();" id="title" value="<?php echo $title; ?>" style="width:280px;" />
    </div>
    <?php } ?>
    
    <div style="margin-top:10px;">
      <label for="width"><?php echo getescapedtext ($hcms_lang['width'][$lang]);?> </label> (px)<br/>
      <input type="number" onchange="updateCodeSegment();" id="width" value="<?php echo $config['width']; ?>" style="width:280px;" />
    </div>
    <div style="margin-top:10px;">
      <label for="height"><?php echo getescapedtext ($hcms_lang['height'][$lang]);?> </label> (px)<br/>
      <input type="number" onchange="updateCodeSegment();" id="height" value="<?php echo $config['height']; ?>" style="width:280px;" />
    </div>
    <div style="margin-top:10px;">
      <input type="checkbox" onchange="updateCodeSegment();" id="autoplay" /> <label for="autoplay"><?php echo getescapedtext ($hcms_lang['autoplay'][$lang]);?> </label>
    </div>
    <?php if (!$audio) { ?>
    <div style="margin-top:10px;">
      <input type="checkbox" onchange="updateCodeSegment();" CHECKED id="fullscreen" /> <label for="fullscreen"><?php echo getescapedtext ($hcms_lang['enable-fullscreen'][$lang]);?> </label>
    </div>
    <?php } ?>
    <div style="margin-top:10px;">
      <input type="checkbox" onchange="updateCodeSegment();" id="loop" /> <label for="loop"><?php echo getescapedtext ($hcms_lang['loop'][$lang]);?> </label>
    </div>
    <?php if (!$audio) { ?>
    <div style="margin-top:10px;">
      <input type="checkbox" onchange="updateCodeSegment();" id="muted" /> <label for="muted"><?php echo getescapedtext ($hcms_lang['muted'][$lang]);?> </label>
    </div>
    <?php } ?>
    <div style="margin-top:10px;">
      <input type="checkbox" onchange="updateCodeSegment();" CHECKED id="controls" /> <label for="controls"><?php echo getescapedtext ($hcms_lang['controls'][$lang]);?> </label>
    </div>

    <?php if (!$audio) { ?>
    <div style="margin-top:10px;">
      <label for="logo"><?php echo getescapedtext ($hcms_lang['start-image'][$lang]);?> </label><br />
      <input style="vertical-align:top; width:240px;" type="text" onchange="updateCodeSegment();" id="logo" />
      <img class="hcmsButtonTiny hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['select-image'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-image'][$lang]); ?>" src="<?php echo getthemelocation(); ?>img/button_media.png" onclick="hcms_openWindow('<?php echo $mgmt_config['url_path_cms']."editor/media_frameset.php?site=".url_encode($site)."&mediacat=cnt&mediatype=image&CKEditorFuncNum=123"; ?>', 'preview', '', 620, 550);" />
    </div>
    <?php } ?>

    <hr />
  <?php
  }
  ?>
  
  	<strong><?php echo getescapedtext ($hcms_lang['html-body-segment'][$lang]);?></strong> (<?php echo getescapedtext ($hcms_lang['character-set'][$lang])." ".strtoupper (getcodepage ($lang)); ?>)<br />
  	<?php echo getescapedtext ($hcms_lang['mark-and-copy-the-code-from-the-text-area-box-keys-ctrl-a-and-ctrl-c-for-copy-or-right-mouse-button-copy'][$lang]);?><br /><br />
    <?php echo getescapedtext ($hcms_lang['video'][$lang]); ?><br/>
  	<textarea id="codesegment" style="height:140px; width:98%" wrap="VIRTUAL"><?php	echo html_encode($playercode, $hcms_lang_codepage[$lang]); ?></textarea><br/><br/>
    <?php if (!$is_mobile) { ?>
    <?php echo getescapedtext ("360 ".$hcms_lang['video'][$lang]); ?><br/>
    <textarea id="codesegment360" style="height:140px; width:98%" wrap="VIRTUAL"></textarea>
    <?php } ?>
    <hr />
  </div>

  <!-- preview -->
  <div id="preview" style="float:left; scrolling:auto;">

    <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['preview'][$lang]); ?></span><br/><br/>
    <?php echo getescapedtext ($hcms_lang['video'][$lang]); ?><br/>
    <div id="playercode" style="margin-bottom:10px;"><?php echo $playercode; ?></div><br/>

    <?php if (!$is_mobile) { ?>
    <?php echo getescapedtext ("360 ".$hcms_lang['video'][$lang]); ?><br/>
    <div id="playercode360"><?php echo $playercode; ?></div>
    <?php } ?>

  </div>

<?php
if ($config && is_array ($config) && intval ($config['version']) >= 2) 
{
?>
<script type="text/javascript">
updateCodeSegment();
</script>
<?php
}
?>
</div>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>