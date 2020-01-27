<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */

// session
define ("SESSION", "create");
// management configuration
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");
// file formats extensions
require ("../include/format_ext.inc.php");


// input parameters
$savetype = getrequest ("savetype");
$wf_token = getrequest_esc ("wf_token");
$token = getrequest ("token");

// object
$site = getrequest ("site", "publicationname");
$location = getrequest ("location", "locationname");
$page = getrequest ("page", "objectname");

// image format
$imageformat = getrequest ("imageformat");
// image density
$imagedensity = getrequest ("imagedensity");
// image quality
$imagequality= getrequest ("imagequality");
// image resize
$imageresize = getrequest ("imageresize");
$imagepercentage = getrequest ("imagepercentage", "numeric");
$imagewidth = getrequest ("imagewidth", "numeric");
$imageheight = getrequest ("imageheight", "numeric");
// Thumb size
$thumbwidth = getrequest ("thumbwidth", "numeric", 740);
$thumbheight = getrequest ("thumbheight", "numeric", 555);
// image crop
$imagecropwidth = getrequest ("imagecropwidth", "numeric");
$imagecropheight = getrequest ("imagecropheight", "numeric");
$imagex = getrequest ("imagex", "numeric");
$imagey = getrequest ("imagey", "numeric");
// Rotate
$rotate = getrequest("rotate");
$angle = getrequest ("degree", "numeric");
// Brightness
$use_brightness = getrequest("use_brightness", "numeric");
$brightness = getrequest ("brightness", "numeric");
// Contrast
$use_contrast = getrequest("use_contrast", "numeric");
$contrast = getrequest ("contrast", "numeric");
// Colorspace
$colorspace = getrequest ("colorspace");
$imagecolorspace = getrequest ("imagecolorspace");
// flip
$flip = getrequest ("flip");
// Effects
$effect = getrequest ("effect");
// sepia_treshold
$sepia_treshold = getrequest ("sepia_treshold", "numeric");
// blur data
$blur_radius = getrequest ("blur_radius", "numeric", NULL);
$blur_sigma = getrequest ("blur_sigma", "numeric", NULL);
// blur data
$sharpen_radius = getrequest ("sharpen_radius", "numeric", NULL);
$sharpen_sigma = getrequest ("sharpen_sigma", "numeric", NULL);
// sketch data
$sketch_radius = getrequest ("sketch_radius", "numeric", NULL);
$sketch_sigma = getrequest ("sketch_sigma", "numeric", NULL);
$sketch_angle = getrequest ("sketch_angle", "numeric", NULL);
// Paint Value
$paintvalue = getrequest ("paint_value", "numeric", NULL);

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

// output is used to return data
$output = new stdClass();

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
if (checktoken ($token, $user)) loadbalancer ("renderimage");

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";
$result = false;

// definitions
$available_colorspaces = array();
$available_colorspaces['CMYK'] = 'CMYK';
$available_colorspaces['GRAY'] = 'GRAY';
$available_colorspaces['CMY'] = 'CMY';
$available_colorspaces['RGB'] = 'RGB';
$available_colorspaces['sRGB'] = 'sRGB';
$available_colorspaces['Transparent'] = 'Transparent';
$available_colorspaces['XYZ'] = 'XYZ';

$available_flip = array();
$available_flip['fv'] = $hcms_lang['vertical'][$lang];
$available_flip['fh'] = $hcms_lang['horizontal'][$lang];
$available_flip['fv fh'] = $hcms_lang['both'][$lang];

if (checktoken ($token, $user))
{
  // get file information of original media file
  $mediafile_info = getfileinfo ($site, $mediafile, "comp");
  
  // source media location
  $media_root_source = getmedialocation ($site, $mediafile_info['file'], "abs_path_media").$site."/";
  
  // destination media location
  if ($savetype == "auto") $media_root_target = $media_root_source;
  else $media_root_target = $mgmt_config['abs_path_temp'];
  
  $mediafile_orig = $mediafile;

  // if RAW image, use equivalent JPEG image
  if (is_rawimage ($mediafile_info['ext']))
  {
    // reset media file
    $mediafile_raw = $mediafile_info['filename'].".jpg";
    
    // prepare media file
    $temp_source = preparemediafile ($site, $media_root_source, $mediafile_raw, $user);
    
    // if encrypted
    if (!empty ($temp_source['result']) && !empty ($temp_source['crypted']) && is_file ($temp_source['templocation'].$temp_source['tempfile']))
    {
      $media_root_source = $temp_source['templocation'];
      $mediafile = $temp_source['tempfile'];
    }
    // if restored
    elseif (!empty ($temp_source['result']) && !empty ($temp_source['restored']) && is_file ($temp_source['location'].$temp_source['file']))
    {
      $media_root_source = $temp_source['location'];
      $mediafile = $temp_source['file'];
    }
    // if JPEG of RAW file exists
    elseif (is_file ($media_root_source.$mediafile_raw))
    {
      // reset media file
      $mediafile = $mediafile_raw;
    }
    // use RAW file
    else
    {
      // reset media file
      $mediafile = $mediafile_orig;
      $mediafile_failed = true;
    }
  }
  
  // if not a RAW image or no equivalent JPEG image is available
  if (!is_rawimage ($mediafile_info['ext']) || !empty ($mediafile_failed))
  {
    // prepare media file
    $temp_source = preparemediafile ($site, $media_root_source, $mediafile, $user);
  }

  // if encrypted
  if (!empty ($temp_source['result']) && !empty ($temp_source['crypted']) && is_file ($temp_source['templocation'].$temp_source['tempfile']))
  {
    $media_size = @getimagesize ($temp_source['templocation'].$temp_source['tempfile']);
  }
  // if restored
  elseif (!empty ($temp_source['result']) && !empty ($temp_source['restored']) && is_file ($temp_source['location'].$temp_source['file']))
  {
    $media_size = @getimagesize ($temp_source['location'].$temp_source['file']);
  }
  else
  {
    $media_size = @getimagesize ($media_root_source.$mediafile);
  }

  // render image
  if ($media_size != false && valid_publicationname ($site))
  { 
    // sets the maximum execution time for the script to 300 sec.
    ini_set ("max_execution_time", "300");
    
    // image resize options
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
    if ($imageformat != "")
    {
      $formats = "";
      $thumbformat = "";

      while (list ($formatstring, $settingstring) = each ($mgmt_imageoptions))
      {
        if (substr_count ($formatstring.".", ".".$imageformat.".") > 0)
        {
          $formats = $formatstring;
        }

        if (substr_count ($formatstring.".", ".png.") > 0)
        {
          $thumbformat = $formatstring;
        }
      }

      if ($thumbformat == "") $thumbformat = ".png";

      if ($formats != "")
      {
        // convert the image file
        // Options:
        // -s ... size in pixels (width x height)
        // -c ... offset in x and y (x-offset x y-offset)
        // -f ... image output format      

        $mgmt_imageoptions[$formats]['preview'] = "";
        $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] = "";

        // crop image
        if ($imageresize == "crop" && $imagecropwidth > 0 && $imagecropheight > 0 && $imagex >= 0 && $imagey >= 0)
        {
          $mgmt_imageoptions[$formats]['preview'] .= " -s ".$imagecropwidth."x".$imagecropheight." -c ".$imagex."x".$imagey;
        }
        // resize image
        elseif (in_array ($imageresize, array("percentage", "imagewidth", "imageheight")) && $imagewidth > 0 && $imageheight > 0)
        {
          $mgmt_imageoptions[$formats]['preview'] .= " -s ".$imagewidth."x".$imageheight;
        }

        if ($thumbwidth > 0 && $thumbheight > 0)
        {
          $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -s ".$thumbwidth."x".$thumbheight;
        }

        // rotate
        if ($rotate == "rotate" && $angle !== NULL) 
        {
          $mgmt_imageoptions[$formats]['preview'] .= " -rotate ".$angle;
          $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -rotate ".$angle;
        }
        // flip
        elseif ($rotate == "flip" && array_key_exists ($flip, $available_flip))
        {
          $flipflop = str_replace (array("fv", "fh"), array("-fv", "-fh"), $flip);

          $mgmt_imageoptions[$formats]['preview'] .= " ".$flipflop;
          $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " ".$flipflop;
        }

        // image density (DPI)
        if ($imagedensity >= 72 && $imagedensity <= 1200) 
        {
          $mgmt_imageoptions[$formats]['preview'] .= " -d ".$imagedensity;
          $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -d ".$imagedensity;
        }
        
        // image quality / compression
        if ($imagequality >= 1 && $imagequality <= 100) 
        {
          $mgmt_imageoptions[$formats]['preview'] .= " -q ".$imagequality;
          $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -q ".$imagequality;
        }
  
        // brightness
        if ($use_brightness == 1 && $brightness != 0)
        {
          $mgmt_imageoptions[$formats]['preview'] .= " -b ".$brightness;
          $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -b ".$brightness;
        }
        
        // contrast
        if ($use_contrast == 1 && $contrast != 0)
        {
          $mgmt_imageoptions[$formats]['preview'] .= " -k ".$contrast;
          $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -k ".$contrast;
        }
        
        // colorspace
        if ($colorspace == 1 && array_key_exists ($imagecolorspace, $available_colorspaces)) 
        {
          $mgmt_imageoptions[$formats]['preview'] .= " -cs ".$imagecolorspace;
          $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -cs ".$imagecolorspace;
        }
        
        // effects
        if ($effect == "sepia" && $sepia_treshold > 0 && $sepia_treshold <= 99.9) 
        {
          $mgmt_imageoptions[$formats]['preview'] .= " -sep ".$sepia_treshold."%";
          $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -sep ".$sepia_treshold."%";
        }
        elseif ($effect == "blur" && $blur_sigma >= 0.1 && $blur_sigma <= 3 && $blur_radius !== NULL) 
        {
          $mgmt_imageoptions[$formats]['preview'] .= " -bl ".$blur_radius."x".$blur_sigma;
          $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -bl ".$blur_radius."x".$blur_sigma;
        }
        elseif ($effect == "sharpen" && $sharpen_sigma >= 0.1 && $sharpen_sigma <= 3 && $sharpen_radius !== NULL) 
        {
          $mgmt_imageoptions[$formats]['preview'] .= " -sh ".$sharpen_radius."x".$sharpen_sigma;
          $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -bl ".$blur_radius."x".$blur_sigma;
        }
        elseif ($effect == "sketch" && $sketch_sigma !== NULL && $sketch_radius !== NULL && $sketch_angle !== NULL) 
        {
          if ($sketch_angle > -1) $sketch_angle = "+".$sketch_angle;
          $mgmt_imageoptions[$formats]['preview'] .= " -sk ".$sketch_radius."x".$sketch_sigma.$sketch_angle;
          $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -sk ".$sketch_radius."x".$sketch_sigma.$sketch_angle;
        }
        elseif ($effect == "paint" && $paintvalue !== NULL) 
        {
          $mgmt_imageoptions[$formats]['preview'] .= " -pa ".$paintvalue;
          $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -pa ".$paintvalue;
        }
  
        // add the mandatory format
        $mgmt_imageoptions[$formats]['preview'] .= " -f ".$imageformat;
        $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -f png";

        // edit and save media file
        if ($savetype == "auto" || $savetype == "editor_so")
        {
          $mgmt_imageoptions[$formats]['original'] = $mgmt_imageoptions[$formats]['preview'];

          $editmediaobject = editmediaobject ($site, $location, $page, $imageformat, "original", $user);

          if ($editmediaobject['result']) $result = $editmediaobject['mediafile'];
          else $result = false;
        }
        // create preview for image editing
        else
        {
          // preview image in original size
          $result = createmedia ($site, $media_root_source, $media_root_target, $mediafile_info['file'], $imageformat, 'preview', true);

          if ($result)
          {
            list ($output->imagewidth, $output->imageheight) = getimagesize ($media_root_target.$result);
            
            if (($imageresize == "crop" || $output->imagewidth > $thumbwidth || $output->imageheight > $thumbheight))
            {
              // reduzed image for image editor
              $resultthumb = createmedia ($site, $media_root_source, $media_root_target, $mediafile_info['file'], "png", 'render.'.$thumbwidth.'x'.$thumbheight);
            }
            else $resultthumb = false;
          }
        }
      }
      else
      {
        $show = $hcms_lang['the-file-could-not-be-processed'][$lang];
      }
    }
    else
    {
      $show = $hcms_lang['required-parameters-are-missing'][$lang];
    }
  }
}

// on success
if (!empty ($result)) 
{ 
  $output->success = true;
  
  if (!empty ($editmediaobject) && is_array ($editmediaobject))
  {
    $add_onload = $editmediaobject['add_onload'];
    $show = $editmediaobject['message'];
    $page = $editmediaobject['object'];
    $mediafile = $editmediaobject['mediafile'];
    
    $output->object = $location_esc.$page;
  }
  
  // add timestamp to ensure the new image will be loaded
  $output->imagelink = createviewlink ($site, $result, "", true);
  
  if (!empty ($resultthumb)) 
  {
    // add timestamp to ensure the new image will be loaded
    $output->thumblink = createviewlink ($site, $resultthumb, "", true);

    // prepare media file
    $temp_target = preparemediafile ($site, $media_root_target, $resultthumb, $user);
    
    // get file information of new original media file
    if (!empty ($temp_target['result']) && !empty ($temp_target['crypted']) && is_file ($temp_target['templocation'].$temp_target['tempfile']))
    {
      $media_size = @getimagesize ($temp_target['templocation'].$temp_target['tempfile']);
    }
    elseif (!empty ($temp_target['result']) && !empty ($temp_target['restored']) && is_file ($temp_target['location'].$temp_target['file']))
    {
      $media_size = @getimagesize ($temp_target['location'].$temp_target['file']);
    }
    else
    {
      $media_size = @getimagesize ($media_root_source.$mediafile);
    }

    // set image size
    list ($output->thumbwidth, $output->thumbheight) = getimagesize ($media_root_target.$resultthumb);
  }
  else $output->thumblink = false;
  
  if (empty ($show)) $output->message = "";
  else $output->message = $show;
}
// on error
else
{
  $output->success = false;

  if (empty ($show))
  {
    $show = $hcms_lang['error-during-conversion'][$lang];
    $output->message = $show;
  }
  else $output->message = $show;
}

// return json encoded data for AJAX call
if ($savetype == "auto" || $savetype == "")
{
  header ('Content-Type: application/json; charset=utf-8');
  echo json_encode ($output);
}
// refresh after save and open
elseif ($savetype == "editor_so")
{
  $add_onload .=  "document.location='../image_rendering.php?site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."';\n";
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