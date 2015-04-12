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
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");
// extension definitions
include ("include/format_ext.inc.php");

// input parameters
$media = getrequest_esc ("media", "objectname");
$lang = getrequest_esc ("lang", false, "en");
$site = getrequest_esc ("site", "publicationname");
$logo = getrequest_esc ("logo", "url", NULL);
$title = getrequest_esc ("title", "objectname", NULL);
$autoplay = getrequest ("autoplay", "bool", false);
$enableFullScreen = getrequest ("fullscreen", "bool", true);
$enableKeyBoard = getrequest ("keyboard", "bool", true);
$enablePause = getrequest ("pause", "bool", true);
$enableSeek = getrequest ("seek", "bool", true);
$width = getrequest_esc ("width", "numeric", 0);
$height = getrequest_esc ("height", "numeric", 0);

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
  if (!$site) 
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
elseif (allowuserip ($site) == false)
{
  echo showinfopage ($hcms_lang['the-requested-object-can-not-be-provided'][$lang], $lang);
  exit;
}

// --------------------------------- logic section ----------------------------------

// get media location
if (is_file (getmedialocation ($site, $media, "abs_path_media").$media))
{
  $media_dir = getmedialocation ($site, $media, "abs_path_media");
}

// read player config
$file_info = getfileinfo ($site, $media, "comp");
$audio = false;


// IMPORTANT: do not change the priority order!

// 1st Priority: versions before 5.6.3 (for HTML5 video/audio player)
if ($media_dir != "" && is_file ($media_dir.$site."/".$file_info['filename'].".config.video"))
{
  $config = readmediaplayer_config ($media_dir.$site."/", $file_info['filename'].".config.video");
}
elseif ($media_dir != "" && is_file ($media_dir.$site."/".$file_info['filename'].".config.audio"))
{
  $config = readmediaplayer_config ($media_dir.$site."/", $file_info['filename'].".config.audio");
  $audio = true;
}
// 2nd Priority: versions from 5.6.3 (preview of original file if no HTML5 video files have been generated)
elseif ($media_dir != "" && is_file ($media_dir.$site."/".$file_info['filename'].".config.orig"))
{
  $config = readmediaplayer_config ($media_dir.$site."/", $file_info['filename'].".config.orig");
  // We try to detect if we should use audio player
  if (is_array ($config['mediafiles']))
  {
    list ($test, $duh) = explode (";", reset($config['mediafiles']));
    $testfinfo = getfileinfo ($site, $test, 'comp');
    
    if (is_audio ($testfinfo['ext']))
    {
      $audio = true;
    }
  }
}
// 3rd Priority: older versions before 5.5.13
elseif ($media_dir != "" && is_file ($media_dir.$site."/".$file_info['filename'].".config.flv"))
{
  $config = readmediaplayer_config ($media_dir.$site."/", $file_info['filename'].".config.flv");
}
// 4th Priority: no media config file is available, try to create video thumbnail file
elseif (is_file ($media_dir.$site."/".$file_info['file']))
{
  // create thumbnail video of original file
  $create_media = createmedia ($site, $media_dir.$site."/", $media_dir.$site."/", $file_info['file'], "flv", "origthumb");
  
  if ($create_media) $config = readmediaplayer_config ($media_dir.$site."/", $file_info['filename'].".config.orig");
}

// set width of video player
if ($width < 1 && !empty ($config['width'])) $width = $config['width'];

// set height of video player
if ($height < 1 && !empty ($config['height'])) $height = $config['height'];

// get video player code
if (is_array ($config))
{
  // config version 2.0 and up
  if (intval ($config['version']) >= 2) 
  {
    if ($audio) $playercode = showaudioplayer ($site, $config['mediafiles'], 'publish', "", $autoplay, false );
    else $playercode = showvideoplayer ($site, $config['mediafiles'], $width, $height, 'publish', $logo, "", $title, $autoplay, $enableFullScreen, $enableKeyBoard, $enablePause, $enableSeek, true);
  }
  // player code is embedded in config
  else
  {
    $playercode = $config['data'];
  }
}

// create video player
if ($playercode != "") 
{
?>
<!DOCTYPE html>
<html>
  <head>
    <title>hyperCMS Videoplayer</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <?php 
    if ($audio) echo showaudioplayer_head ();
    else echo showvideoplayer_head ($site, false, 'publish');
    ?>
  </head>
  <body style="padding: 0px; margin: 0px;">
    <?php echo $playercode; ?>
  </body>
</html>
<?php
}
else 
{
  echo showinfopage ($hcms_lang['couldnt-find-the-requested-video'][$lang], $lang);
}
?>
