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
$media = getrequest_esc ("media", "objectname");
$lang = getrequest_esc ("lang", false, "en");
$site = getrequest_esc ("site", "publicationname");
$logo = getrequest_esc ("logo", "url", NULL);
$title = getrequest_esc ("title", "objectname", NULL);
$autoplay = getrequest ("autoplay", "bool", false);
$fullscreen = getrequest ("fullscreen", "bool", true);
$width = getrequest_esc ("width", "numeric", 0);
$height = getrequest_esc ("height", "numeric", 0);
$loop = getrequest ("loop", "bool", false);
$muted = getrequest ("muted", "bool", false);
$controls = getrequest ("controls", "bool", true);
$theme = getrequest_esc ("theme", "locationname");

// language file
require ("language/".getlanguagefile ($lang));

$media_dir = false;
$config = false;
$playercode = false;

if (substr_count ($media, "/") == 1)
{
  // get publication name
  $site = substr ($media, 0, strpos ($media, "/"));
} 
else 
{
  // publication name is missing
  if ($site == "") 
  {
    echo showinfopage ($hcms_lang['couldnt-find-the-requested-video'][$lang], $lang);
    exit;
  }
  // add publication name
  else 
  {
    $media = $site.'/'.$media;
  }
}

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check user session if user is logged in
if ($user != "")
{
  checkusersession ($user, false);
}
// check ip access if public access (no user available)
elseif (!allowuserip ($site) || !mediapublicaccess ($media))
{
  echo showinfopage ($hcms_lang['the-requested-object-can-not-be-provided'][$lang], $lang);
  exit;
}

// --------------------------------- logic section ----------------------------------

// initialize
$audio = false;

// get media location
$media_dir = getmedialocation ($site, $media, "abs_path_media");

// read player config
$file_info = getfileinfo ($site, $media, "comp");

// IMPORTANT: do not change the priority order!
if ($media_dir != "")
{
  // 1st Priority: versions before 5.6.3 (for HTML5 video/audio player)
  if (is_file ($media_dir.$site."/".$file_info['filename'].".config.video") || is_cloudobject ($media_dir.$site."/".$file_info['filename'].".config.video"))
  {
    $config = readmediaplayer_config ($media_dir.$site."/", $file_info['filename'].".config.video");
  }
  elseif (is_file ($media_dir.$site."/".$file_info['filename'].".config.audio") || is_cloudobject ($media_dir.$site."/".$file_info['filename'].".config.audio"))
  {
    $config = readmediaplayer_config ($media_dir.$site."/", $file_info['filename'].".config.audio");
    $audio = true;
  }
  // 2nd Priority: versions from 5.6.3 (preview of original file if no HTML5 video files have been generated)
  elseif (is_file ($media_dir.$site."/".$file_info['filename'].".config.orig") || is_cloudobject ($media_dir.$site."/".$file_info['filename'].".config.orig"))
  {
    $config = readmediaplayer_config ($media_dir.$site."/", $file_info['filename'].".config.orig");
  
    // verify that the media files exist
    if (!empty ($config['mediafiles']) && is_array ($config['mediafiles']))
    {
      $temp_array = $config['mediafiles'];
      $config['mediafiles'] = array();

      foreach ($temp_array as $temp)
      {
        if (is_file ($thumb_root.$temp)) $config['mediafiles'] = $temp;
      }
    }
    // no media files
    else $config['mediafiles'] = array();
  
    // detect audio file
    if (is_array ($config['mediafiles']) && sizeof ($config['mediafiles']) > 0)
    {
      list ($test, $rest) = explode (";", reset($config['mediafiles']));
      $testfinfo = getfileinfo ($site, $test, 'comp');    
      if (is_audio ($testfinfo['ext'])) $audio = true;
    }

    // add original file as well if it is an MP4, WebM or OGG/OGV (supported formats by most of the browsers)
    if (empty ($config['mediafiles']) || !is_array ($config['mediafiles']) || $width > 854 || (sizeof ($config['mediafiles']) < 1 && $width <= 854))
    {
      if (substr_count (".aac.flac.mp4.mp3.ogg.ogv.wav.webm.", $file_info['ext'].".") > 0 && (is_file ($media_dir.$media) || is_cloudobject ($media_dir.$media)))
      {
        if (!is_array ($config['mediafiles'])) $config['mediafiles'] = array();
        $temp = $media.";".getmimetype ($media);
        array_unshift ($config['mediafiles'], $temp);
      }
    }
  }
  // 3rd Priority: older versions before 5.5.13
  elseif (is_file ($media_dir.$site."/".$file_info['filename'].".config.flv") || is_cloudobject ($media_dir.$site."/".$file_info['filename'].".config.flv"))
  {
    $config = readmediaplayer_config ($media_dir.$site."/", $file_info['filename'].".config.flv");
  }
  // 4th Priority: no media config file is available, try to create video thumbnail file
  elseif (is_file ($media_dir.$site."/".$file_info['file']) || is_cloudobject ($media_dir.$site."/".$file_info['file']))
  {
    // create thumbnail video of original file
    $create_media = createmedia ($site, $media_dir.$site."/", $media_dir.$site."/", $file_info['file'], "mp4", "origthumb", false, true);

    if ($create_media) $config = readmediaplayer_config ($media_dir.$site."/", $file_info['filename'].".config.orig");
  }
}

// reset width of video player by config value
if ($width < 1 && !empty ($config['width'])) $width = $config['width'];

// reset height of video player by config value
if ($height < 1 && !empty ($config['height'])) $height = $config['height'];

// get video player code
if (is_array ($config))
{
  // config version 2.0 and up
  if (intval ($config['version']) >= 2) 
  {
    if ($audio) $playercode = showaudioplayer ($site, $config['mediafiles'], $width, $height, $logo, "", $autoplay, $loop, $controls, false, false);
    else $playercode = showvideoplayer ($site, $config['mediafiles'], $width, $height, $logo, "", $title, $autoplay, $fullscreen, $loop, $muted, $controls, true, false);
  }
  // player code is embedded in config
  else
  {
    $playercode = $config['data'];
  }
}

// wallpaper
$wallpaper = "";

if (!$is_mobile)
{
  $wallpaper = getwallpaper ($theme);
}

// create unique id
$videocontainer_id = uniqid();

// create video player
if ($playercode != "") 
{
?>
<!DOCTYPE html>
<html>
  <head>
    <title>hyperCMS Videoplayer</title>
    <meta charset="UTF-8" />
    <link rel="stylesheet" href="<?php echo $mgmt_config['url_path_cms']; ?>theme/night/css/main.css?v=<?php echo getbuildnumber(); ?>" />
    <link rel="stylesheet" href="<?php echo $mgmt_config['url_path_cms']."theme/night/css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
    <?php
    if ($audio) echo showaudioplayer_head (false, false);
    else echo showvideoplayer_head (false, $fullscreen, false);
    ?>
    <script type="text/javascript">

    function iniframe ()
    {
      try
      {
        return window.self !== window.top;
      }
      catch (e)
      {
        return true;
      }
    }

    function hideloadscreen ()
    {
      document.getElementById('hcmsLoadScreen').style.display = 'none';
    }

    function setwallpaper ()
    {
      // display startScreen, center video and display logo and title
      document.getElementById('startScreen').style.display = 'block';
      document.getElementById('logo').style.display = 'block';
      document.getElementById('<?php echo $videocontainer_id; ?>_container').style.cssText = 'position:absolute; z-index:100; width:<?php echo $width; ?>px; height:<?php echo $height; ?>px; top:calc(50% - <?php echo round ($height/2); ?>px); left:calc(50% - <?php echo round ($width/2); ?>px);';
      <?php if ($title != "") { ?>document.getElementById('<?php echo $videocontainer_id; ?>_title').style.display = 'block';<?php } ?>

      // hide load screen
      hideloadscreen ();

      // set background image
      <?php if (!empty ($wallpaper) && is_image ($wallpaper)) { ?>
      document.getElementById('startScreen').style.backgroundImage = "url('<?php echo $wallpaper; ?>')";
      return true;
      <?php } else { ?>
      return false;
      <?php } ?>
    }
    </script>
  </head>
  <body style="padding:0; margin:0;" onload="if (iniframe() == false) setwallpaper(); else hideloadscreen();">

    <!-- wallpaper -->
    <div id="startScreen" class="hcmsStartScreen" style="display:none;"></div>

    <!-- load screen --> 
    <div id="hcmsLoadScreen" class="hcmsLoadScreen" style="display:block; filter:alpha(opacity=100); -moz-opacity:1; opacity:1;"></div>

    <!-- logo -->
    <div id="logo" style="display:none; position:fixed; top:10px; left:10px; z-index:2;">
      <img id="logoimage" src="<?php echo getthemelocation($theme); ?>img/logo_server.png" style="max-width:<?php if ($is_mobile) echo "320px"; else echo "420px"; ?>; max-height:80px;" />
    </div>

    <!-- video player -->
    <div id="<?php echo $videocontainer_id; ?>_container" style="position:absolute; z-index:100; width:<?php echo $width; ?>px; height:<?php echo $height; ?>px; top:0; left:0; padding:0; margin:0;">
      <div id="<?php echo $videocontainer_id; ?>_title" style="display:none; position:absolute; z-index:101; top:-32px; left:0; width:100%; font-size:20px; text-align:center;" class="hcmsTextWhite hcmsTextShadow"><?php echo $title; ?></div>

      <?php echo $playercode; ?>

    </div>

    <?php includefooter(); ?>
  </body>
</html>
<?php
}
else 
{
  echo showinfopage ($hcms_lang['couldnt-find-the-requested-video'][$lang], $lang);
}
?>
