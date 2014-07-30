<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("../include/session.inc.php");
// management configuration
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");
// hyperCMS UI
require ("../function/hypercms_ui.inc.php");
// language file
require_once ("../language/image_rendering.inc.php");


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
$colorspace = getrequest( "colorspace" );
$imagecolorspace = getrequest("imagecolorspace");
// flip
$flip = getrequest( "flip" );
// Effects
$effect = getrequest ("effect");
// sepia_treshold
$sepia_treshold = getrequest( "sepia_treshold", "numeric" );
// blur data
$blur_radius = getrequest( "blur_radius", "numeric", NULL );
$blur_sigma = getrequest( "blur_sigma", "numeric", NULL );
// blur data
$sharpen_radius = getrequest( "sharpen_radius", "numeric", NULL );
$sharpen_sigma = getrequest( "sharpen_sigma", "numeric", NULL );
// sketch data
$sketch_radius = getrequest( "sketch_radius", "numeric", NULL );
$sketch_sigma = getrequest( "sketch_sigma", "numeric", NULL );
$sketch_angle = getrequest( "sketch_angle", "numeric", NULL );
// Paint Value
$paintvalue = getrequest( "paint_value", "numeric", NULL );

// output is used to send data back
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

// --------------------------------- logic section ----------------------------------

// get file information of original media file
$mediafile_info = getfileinfo ($site, $mediafile, "");
$media_root_src = getmedialocation ($site, $mediafile_info['file'], "abs_path_media").$site."/";
$media_root_target = $mgmt_config['abs_path_cms'].'temp/';

$available_colorspaces = array();
$available_colorspaces['CMYK'] = 'CMYK';
$available_colorspaces['GRAY'] = 'GRAY';
$available_colorspaces['CMY'] = 'CMY';
$available_colorspaces['RGB'] = 'RGB';
$available_colorspaces['sRGB'] = 'sRGB';
$available_colorspaces['Transparent'] = 'Transparent';
$available_colorspaces['XYZ'] = 'XYZ';

$available_flip = array();
$available_flip['-fv'] = $text36[$lang];
$available_flip['-fh'] = $text37[$lang];
$available_flip['-fv -fh'] = $text38[$lang];

$show = "";

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
      $imageformat != "" &&
      (
        (in_array ($imageresize, array ("percentage", "imagewidth", "imageheight")) && $imagewidth != "" && $imageheight != "") || 
        ($imageresize == "crop" && $imagecropwidth != "" && $imagecropheight != "") ||
        ($rotate == "rotate" && $angle != "" && $imageformat != "" && array_key_exists (0, $media_size) && array_key_exists(1, $media_size)) ||
        ($use_brightness == 1 && $imageformat != "" && $brightness != 0) ||
        ($use_contrast == 1 && $imageformat != "" && $contrast != 0) ||
        ($colorspace == 1 && is_array($available_colorspaces) && array_key_exists( $imagecolorspace, $available_colorspaces )) ||
        ($rotate == "flip" && array_key_exists($flip, $available_flip)) ||
        ($effect == "sepia" && $sepia_treshold > 0 && $sepia_treshold <= 99.9 ) ||
        ($effect == "blur" && $blur_sigma > 0.1 && $blur_sigma <= 3 && $blur_radius !== NULL ) ||
        ($effect == "sharpen" && $sharpen_sigma > 0.1 && $sharpen_sigma <= 3 && $sharpen_radius !== NULL) ||
        ($effect == "sketch" && $sketch_sigma !== NULL && $sketch_radius !== NULL && $sketch_angle !== NULL) ||
        ($effect == "paint" && $paintvalue !== NULL)
      )
     )
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
        
      if ($imageresize == "crop")
      {
        $mgmt_imageoptions[$formats]['preview'] .= " -s ".$imagecropwidth."x".$imagecropheight." -c ".$imagex."x".$imagey;
      } 
      elseif( in_array($imageresize, array("percentage", "imagewidth", "imageheight")))
      {
        $mgmt_imageoptions[$formats]['preview'] .= " -s ".$imagewidth."x".$imageheight;
      }
      
      $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -s ".$thumbwidth."x".$thumbheight;

      if ($rotate == "rotate") 
      {
        $mgmt_imageoptions[$formats]['preview'] .= " -r ".$angle;
        $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -r ".$angle;
      }
      elseif($rotate == "flip")
      {
         $mgmt_imageoptions[$formats]['preview'] .= " ".$flip;
         $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -r ".$angle;
      }

      if ($use_brightness == 1)
      {
        $mgmt_imageoptions[$formats]['preview'] .= " -b ".$brightness;
        $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -b ".$brightness;
      } 

      if($use_contrast == 1)
      {
        $mgmt_imageoptions[$formats]['preview'] .= " -k ".$contrast;
        $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -k ".$contrast;
      }


      if( $colorspace == 1) 
      {
        $mgmt_imageoptions[$formats]['preview'] .= " -cs ".$imagecolorspace;
        $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -cs ".$imagecolorspace;
      }

      if( $effect == "sepia") 
      {
        $mgmt_imageoptions[$formats]['preview'] .= " -sep ".$sepia_treshold."%";
        $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -sep ".$sepia_treshold."%";
      }
      elseif( $effect == "blur") 
      {
        $mgmt_imageoptions[$formats]['preview'] .= " -bl ".$blur_radius."x".$blur_sigma;
        $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -bl ".$blur_radius."x".$blur_sigma;
      }
      elseif( $effect == "sharpen") 
      {
        $mgmt_imageoptions[$formats]['preview'] .= " -sh ".$sharpen_radius."x".$sharpen_sigma;
        $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -bl ".$blur_radius."x".$blur_sigma;
      }
      elseif( $effect == "sketch") 
      {
        if($sketch_angle > -1) {
          $sketch_angle = "+".$sketch_angle;
        }
        $mgmt_imageoptions[$formats]['preview'] .= " -sk ".$sketch_radius."x".$sketch_sigma.$sketch_angle;
        $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -sk ".$sketch_radius."x".$sketch_sigma.$sketch_angle;
      }
      elseif( $effect == "paint") 
      {
        $mgmt_imageoptions[$formats]['preview'] .= " -pa ".$paintvalue;
        $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -pa ".$paintvalue;
      }

      // Add the mandatory format
      $mgmt_imageoptions[$formats]['preview'] .= " -f ".$imageformat;
      $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight] .= " -f png";
      
      $output->options = $mgmt_imageoptions[$formats]['preview'];
      $output->thumboptions = $mgmt_imageoptions[$thumbformat]['render.'.$thumbwidth.'x'.$thumbheight];
      
      $result = createmedia ($site, $media_root_src, $media_root_target, $mediafile_info['file'], $imageformat, 'preview');
      if($result)
      {
        list($output->imagewidth, $output->imageheight) = getimagesize($media_root_target.$result);
        if(($imageresize == "crop" || $output->imagewidth > $thumbwidth || $output->imageheight > $thumbheight))
          $resultthumb = createmedia ($site, $media_root_src, $media_root_target, $mediafile_info['file'], "png", 'render.'.$thumbwidth.'x'.$thumbheight);
        else
          $resultthumb = false;
      }
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

if ($result) 
{ 
  $output->success = true;
  $output->imagelink = $mgmt_config['url_path_cms']."explorer_wrapper.php?site=".$site."&media=".$result."&token=".hcms_crypt($result)."&ts=".time();
  
  if ($resultthumb) 
  {
    $output->thumblink = $mgmt_config['url_path_cms']."explorer_wrapper.php?site=".$site."&media=".$resultthumb."&token=".hcms_crypt($resultthumb)."&ts=".time();
    list($output->thumbwidth, $output->thumbheight) = getimagesize($media_root_target.$resultthumb);
  }
  else $output->thumblink = false;
}
else
{
  $output->success = false;
  
  if (empty($show)) $output->message =  $text32[$lang];
  else $output->message =  $show;
}
echo json_encode($output);
