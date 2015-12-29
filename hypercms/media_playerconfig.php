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
// extension definitions
include ("include/format_ext.inc.php");


// input parameters
$location = getrequest ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$type = getrequest ("type");

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
elseif($media_root && file_exists ($media_root.$file_info['filename'].".config.orig")) 
{
  $config = readmediaplayer_config ($media_root, $file_info['filename'].".config.orig");
  
  // We try to detect if we should use audio player
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
  if (intval ($config['version']) >= 2) 
  {
    $url = $mgmt_config['url_path_cms'].'videoplayer.php?media='.$mediafile.'&site='.$site;
    
    if ($audio) 
    {
      $size = 'height="320" width="320"';
      $fullscreen = '';
    }
    else 
    {
      $size = 'height="'.$config['height'].'" width="'.$config['width'].'"';
      $fullscreen = 'allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"';
    }
    
    $playercode = '<iframe id="'.$frameid.'" '.$size.' frameBorder="0" src="'.$url.'" '.$fullscreen.'></iframe>';
  }
  else
  {
    $head = showvideoplayer_head (false);
    $playercode = $config['data'];
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang);?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" type="text/css" />
<script src="javascript/main.js" type="text/javascript"></script>
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
  <?php } ?>
  var autoplay = document.getElementById("autoplay").checked;
  <?php if (!empty ($mgmt_config['videoplayer']) && strtolower ($mgmt_config['videoplayer']) == "projekktor") { ?>
  var pause = document.getElementById("pause").checked;
  var seek = document.getElementById("seek").checked;
  <?php } ?>
  var loop = document.getElementById("loop").checked;
  var muted = document.getElementById("muted").checked;
  var controls = document.getElementById("controls").checked;
  
  var url = "<?php	echo html_encode ($url, $hcms_lang_codepage[$lang]); ?>";
  var code = '<?php	echo html_encode ($playercode, $hcms_lang_codepage[$lang]); ?>';
  
  var newurl = url;
  
  <?php if (!$audio) { ?>
  if (title != "")
  {
    newurl += '&amp;title='+title;
  }
  <?php } ?>
  if (autoplay) newurl += '&amp;autoplay=true';
  else newurl += '&amp;autoplay=false';

  if (loop) newurl += '&amp;loop=true';
  else newurl += '&amp;loop=false';
  
  if (muted) newurl += '&amp;muted=true';
  else newurl += '&amp;muted=false';
  
  if (controls) newurl += '&amp;controls=true';
  else newurl += '&amp;controls=false';

  <?php if (!$audio) { ?>
  if (fullscreen) newurl += '&amp;fullscreen=true';
  else newurl += '&amp;fullscreen=false';
  <?php 
  }
  
  if (!empty ($mgmt_config['videoplayer']) && strtolower ($mgmt_config['videoplayer']) == "projekktor") { ?>
  if (pause) newurl += '&amp;pause=true';
  else newurl += '&amp;pause=false';
  
  if (seek) newurl += '&amp;seek=true';
  else newurl += '&amp;seek=false';
  <?php }
   
  if (!$audio) {
  ?>
  if (logo) newurl += '&amp;logo='+encodeURIComponent(logo);
  <?php } ?>

  document.getElementById("codesegment").innerHTML = code.replace(url, newurl);
  document.getElementById("<?php echo $frameid; ?>").src = decodeURI(newurl.replace(/\&amp\;/g, "&"));
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
</head>
    
<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php
echo showtopbar ($hcms_lang['media-player-configuration'][$lang], $lang, $mgmt_config['url_path_cms']."page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page));
?>

<!-- content -->
<div class="hcmsWorkplaceFrame">
  
  <?php
  if ($head)
  {
  ?>
  <div style="margin-left: 10px;margin-top: 10px;">
  	<strong><?php echo getescapedtext ($hcms_lang['html-head-segment'][$lang]);?></strong><br />
  	<?php echo getescapedtext ($hcms_lang['mark-and-copy-the-code-from-the-text-area-box-keys-ctrl-a-and-ctrl-c-for-copy-or-right-mouse-button-copy'][$lang]);?><br /><br />       
  	<textarea id="codesegment" style="height: 150px; width: 98%" wrap="VIRTUAL"><?php
    echo $head;
  	?></textarea>
  </div>
  <hr>
  <?php
  }
  
  if ($config && is_array ($config) && intval ($config['version']) >= 2)
  {
  ?>
    <div style="margin-left:10px; margin-top:10px; float:left; width:250px;">
      <?php if (!$audio) { ?>
      <div style="height:20px">
        <label for="title"><?php echo getescapedtext ($hcms_lang['title'][$lang]);?>: </label><br/>
      </div>
      <?php } ?>
      <div style="height:20px">
        <label for="autoplay"><?php echo getescapedtext ($hcms_lang['autoplay'][$lang]);?>: </label>
      </div>
      <?php if (!$audio) { ?>
      <div style="height:20px">
        <label for="fullscreen"><?php echo getescapedtext ($hcms_lang['enable-fullscreen'][$lang]);?>: </label>
      </div>
      <?php } ?>
      <div style="height:20px">
        <label for="loop"><?php echo getescapedtext ($hcms_lang['loop'][$lang]);?>: </label>
      </div>
      <div style="height:20px">
        <label for="muted"><?php echo getescapedtext ($hcms_lang['muted'][$lang]);?>: </label>
      </div>
      <div style="height:20px">
        <label for="controls"><?php echo getescapedtext ($hcms_lang['controls'][$lang]);?>: </label>
      </div>
      <?php if (!empty ($mgmt_config['videoplayer']) && strtolower ($mgmt_config['videoplayer']) == "projekktor") { ?>
      <div style="height:20px">
        <label for="keyboard"><?php echo getescapedtext ($hcms_lang['enable-keyboard-input'][$lang]);?>: </label>
      </div>
      <div style="height:20px">
        <label for="pause"><?php echo getescapedtext ($hcms_lang['enable-pause'][$lang]);?>: </label>
      </div>
      <div style="height:20px">
        <label for="seek"><?php echo getescapedtext ($hcms_lang['enable-seek'][$lang]);?>: </label>
      </div>
      <?php }
       
      if (!$audio) {
      ?>
      <div style="height:20px">
        <label for="logo"><?php echo getescapedtext ($hcms_lang['start-image'][$lang]);?>: </label>
      </div>
      <?php } ?>
    </div>
    
    <div style="float:left; margin-left:10px; margin-top:10px; width: 250px">
      <?php if (!$audio) { ?>
      <div style="height:20px">
        <input type="text" onchange="updateCodeSegment();" id="title" />
      </div>
      <?php } ?>
      <div style="height:20px">
        <input type="checkbox" onchange="updateCodeSegment();" id="autoplay" />
      </div>
      <?php if (!$audio) { ?>
      <div style="height:20px">
        <input type="checkbox" onchange="updateCodeSegment();" CHECKED id="fullscreen" />
      </div>
      <?php } ?>
      <div style="height:20px">
        <input type="checkbox" onchange="updateCodeSegment();" id="loop" />
      </div>
      <div style="height:20px">
        <input type="checkbox" onchange="updateCodeSegment();" id="muted" />
      </div>
      <div style="height:20px">
        <input type="checkbox" onchange="updateCodeSegment();" CHECKED id="controls" />
      </div>
      <?php if (!empty ($mgmt_config['videoplayer']) && strtolower ($mgmt_config['videoplayer']) == "projekktor") { ?>
      <div style="height:20px">
        <input type="checkbox" onchange="updateCodeSegment();" CHECKED id="keyboard" />
      </div>
      <div style="height:20px">
        <input type="checkbox" onchange="updateCodeSegment();" CHECKED id="pause" />
      </div>
      <div style="height:20px">
        <input type="checkbox" onchange="updateCodeSegment();" CHECKED id="seek" />
      </div>
      <?php }
      
      if (!$audio) { 
      ?>
      <div style="height:20px;">
        <input style="vertical-align: top;" type="text" onchange="updateCodeSegment();" id="logo" />
        <img class="hcmsButtonTiny hcmsButtonSizeSquare" title="<?php echo getescapedtext ($hcms_lang['select-image'][$lang]); ?>" style="cursor: pointer;" src="<?php echo getthemelocation(); ?>img/button_media.gif" onclick="hcms_openWindow('<?php echo $mgmt_config['url_path_cms']."editor/media_frameset.php?site=".url_encode($site)."&mediacat=cnt&mediatype=image&CKEditorFuncNum=123"; ?>', 'preview', '', 620, 550);" />
      </div>
      <?php } ?>
    </div>
    <div style="clear:both"></div>
    <hr>
  <?php
  }
  ?>
  <div style="margin-left:10px; margin-top:10px;">
  	<strong><?php echo getescapedtext ($hcms_lang['html-body-segment'][$lang]);?></strong><br />
  	<?php echo getescapedtext ($hcms_lang['mark-and-copy-the-code-from-the-text-area-box-keys-ctrl-a-and-ctrl-c-for-copy-or-right-mouse-button-copy'][$lang]);?><br /><br />
  	<textarea id="codesegment" style="height:80px; width:98%" wrap="VIRTUAL"><?php	echo html_encode($playercode, $hcms_lang_codepage[$lang]); ?></textarea>
  </div>
  <hr>
  <div style="margin-left:10px; margin-top:10px;">
    <strong><?php echo getescapedtext ($hcms_lang['preview'][$lang]);?></strong><br />
    <?php echo $playercode; ?>
  </div>
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
</body>
</html>