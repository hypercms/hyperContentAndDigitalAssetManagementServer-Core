<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("include/session.inc.php");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");
// language file
require ("language/videoplayer.inc.php");
// extension definitions
include ("include/format_ext.inc.php");

// input parameters
$media = getrequest_esc ("media", "objectname");
$lang = getrequest_esc ("lang", false, 'en');
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
    echo showinfopage ($text1[$lang], $lang);
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
  echo showinfopage ($text2[$lang], $lang);
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

// versions before 5.6.3 (for video player)
if ($media_dir != "" && is_file ($media_dir.$site."/".$file_info['filename'].".config.video"))
{
  $config = readmediaplayer_config ($media_dir.$site."/", $file_info['filename'].".config.video");
}
elseif($media_dir != "" && is_file ($media_dir.$site."/".$file_info['filename'].".config.audio"))
{
  $config = readmediaplayer_config ($media_dir.$site."/", $file_info['filename'].".config.audio");
  $audio = true;
}
// older versions before 5.5.13
elseif ($media_dir != "" && is_file ($media_dir.$site."/".$file_info['filename'].".config.flv"))
{
  $config = readmediaplayer_config ($media_dir.$site."/", $file_info['filename'].".config.flv");
}
// versions from 5.6.3 (only preview fo original file)
elseif ($media_dir != "" && is_file ($media_dir.$site."/".$file_info['filename'].".config.orig"))
{
  $config = readmediaplayer_config ($media_dir.$site."/", $file_info['filename'].".config.orig");
  // We try to detect if we should use audio player
  if(is_array($config['mediafiles'])) {
    list($test, $duh) = explode(";", reset($config['mediafiles']));
    $testfinfo = getfileinfo($site, $test, 'comp');
    if(substr_count ($hcms_ext['audio'].'.', $testfinfo['ext'].'.') > 0) {
      $audio = true;
    }
  }
}

// set width of video player
if ($width == 0) $width = $config['width'];

// set height of video player
if ($height == 0) $height = $config['height'];

// get video player code
if (is_array ($config))
{
  // config version 2.0 and up
  if (intval ($config['version']) >= 2) 
  {
    if($audio)
      $playercode = showaudioplayer( $site, $config['mediafiles'], 'publish', "", $autoplay, false );
    else  
      $playercode = showvideoplayer ($site, $config['mediafiles'], $width, $height, 'publish', $logo, "", $title, $autoplay, $enableFullScreen, $enableKeyBoard, $enablePause, $enableSeek, true);
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
    if($audio)
      echo showaudioplayer_head ();
    else
      echo showvideoplayer_head ($site, false, 'publish');
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
  echo showinfopage ($text1[$lang], $lang);
}
?>
