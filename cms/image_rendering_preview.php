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
require_once ("language/image_rendering.inc.php");


// input parameters
$action = getrequest_esc ("action");
$site = getrequest_esc ("site", "publicationname");
$cat = getrequest_esc ("cat", "objectname");
$location = getrequest_esc ("location", "locationname");
$mediafile = getrequest_esc ("media", "objectname");
// image format
$imageformat = getrequest ("imageformat");
// image resize
$imageresize = getrequest ("imageresize");
$imagepercentage = getrequest ("imagepercentage", "numeric");
$imagewidth = getrequest ("imagewidth", "numeric");
$imageheight = getrequest ("imageheight", "numeric");
// image crop
$imagecropwidth = getrequest ("imagecropwidth", "numeric");
$imagecropheight = getrequest ("imagecropheight", "numeric");
$imagex = getrequest ("imagex", "numeric");
$imagey = getrequest ("imagey", "numeric");
// image rotate
$angle = getrequest ("degree", "numeric");
// Brightness
$brightness = getrequest ("brightness", "numeric");
// Contrast
$contrast = getrequest ("contrast", "numeric");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);  
if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// get file information of original media file
$mediafile_info = getfileinfo ($site, $mediafile, "");
$media_root_src = getmedialocation ($site, $mediafile_info['file'], "abs_path_media").$site."/";
$media_root_target = $mgmt_config['abs_path_cms'].'temp/';

// render new image
$media_size = @getimagesize ($media_root_src.$mediafile);

if ($media_size != false && $site != "")
{ 
  ini_set ("max_execution_time", "3600"); // sets the maximum execution time of this script to 1 hour.
  
  if ($imageresize == "percentage")
  {
    $imagewidth = round ($media_size[0] * $imagepercentage / 100, 0);
    $imageheight = round ($media_size[1] * $imagepercentage / 100, 0);
  }
  elseif ($imageresize == "imagewidth")
  {
    $imageratio = $media_size[0] / $media_size[1];
    $imagewidth = round ($imagewidth, 0);
    $imageheight = round ($imagewidth / $imageratio, 0);
  }
  elseif ($imageresize == "imageheight")
  {
    $imageratio = $media_size[0] / $media_size[1];
    $imageheight = round ($imageheight, 0);
    $imagewidth = round ($imageheight * $imageratio, 0);
  }
  
  // get new rendering settings and set image options
  if (
      (in_array($imageresize, array("percentage", "imagewidth", "imageheight")) && $imagewidth != "" && $imageheight != "" && $imageformat != "") || 
      ($imageresize == "crop" && $imagecropwidth != "" && $imagecropheight != "" && $imageformat != "") ||
      ($imageresize == "rotate" && $angle != "" && $imageformat != "" && array_key_exists(0, $media_size) && array_key_exists(1, $media_size)) ||
      ($imageresize == "brightness_contrast" && $imageformat != "" && ($brightness != 0 || $contrast != 0))
      )
  {
    $formats = "";

    while (list ($formatstring, $settingstring) = each ($mgmt_imageoptions))
    {
      if (substr_count ($formatstring.".", ".".$imageformat.".") > 0)
      {
        $formats = $formatstring;
      }
    }
     
    if ($formats != "")
    {
      // convert the image file
      // Options:
      // -s ... size in pixels (width x height)
      // -c ... offset in x and y (x-offset x y-offset)
      // -f ... image output format      
 
      if ($imageresize == "crop")
      {
        $mgmt_imageoptions[$formats]['preview'] = "-s ".$imagecropwidth."x".$imagecropheight." -c ".$imagex."x".$imagey." -f ".$imageformat;
      } 
      elseif($imageresize == "rotate") 
      {
        $mgmt_imageoptions[$formats]['preview'] = "-r ".$angle." -f ".$imageformat;
      } 
      elseif($imageresize == "brightness_contrast") {
        $mgmt_imageoptions[$formats]['preview'] = "-b ".$brightness." -k ".$contrast." -f ".$imageformat;
      }
      else
      {
        $mgmt_imageoptions[$formats]['preview'] = "-s ".$imagewidth."x".$imageheight." -f ".$imageformat;
      }
      $result = createmedia ($site, $media_root_src, $media_root_target, $mediafile_info['file'], $imageformat, 'preview');
    }
    else
    {
      $show = $text1[$lang];
      $result = false;
    }
  }
  else
  {
    $show = $text28[$lang];
    $result = false;
  }
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" type="text/css">
<script src="javascript/main.js" type="text/javascript"></script>
</head>
<body class="hcmsWorkplaceGeneric">
<?php if($result) { ?>
<img src="explorer_wrapper.php?site=<?php echo $site; ?>&media=<?php echo $result; ?>&token=<?php echo hcms_crypt($result);?>" />
<?php } else { ?>
<span><?php echo $text32[$lang]; ?></span>
<?php } ?>
</body>
</html>