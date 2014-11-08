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
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$token = getrequest ("token");
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
$add_onload = "";

// load object file and get container and media file
$objectdata = loadfile ($location, $page);
$mediafile = getfilename ($objectdata, "media");

// set a zoom factor for the thumbnail image
$thumb_size_percent = 200; // e.g. 200 % of the normal thumb size.

// get file information of original media file
$mediafile_info = getfileinfo ($site, $mediafile, "");
$media_root = getmedialocation ($site, $mediafile_info['name'], "abs_path_media").$site."/";

// get file information of original component file
$pagefile_info = getfileinfo ($site, $page, $cat); 

// render new image
$media_size = @getimagesize ($media_root.$mediafile);

// Read all possible formats to convert to from the mediaoptions
$convert_formats = array();

if (isset ($mgmt_imageoptions) && is_array ($mgmt_imageoptions) && !empty ($mgmt_imageoptions))
{
  foreach ($mgmt_imageoptions as $format => $configs)
  {
    if (array_key_exists ('original', $configs))
    {
      $tmp = explode (".", $format);
      $convert_formats[] = $tmp[1];
    }
  }
}

// Add gif, jpg and png because these are our default conversion
if (!in_array ('gif', $convert_formats)) $convert_formats[] = 'gif';
  
if (!in_array ('jpg', $convert_formats) && !in_array ('jpeg', $convert_formats)) $convert_formats[] = 'jpg';

if (!in_array ('png', $convert_formats)) $convert_formats[] = 'png';

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


// render image
if ($action == 'rendermedia' && checktoken ($token, $user) && $media_size != false && valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page))
{
  $search = deconvertpath ($location).$pagefile_info['filename'].".".strtolower ($imageformat);
  
  if (!file_exists ($search) && !file_exists ($search."off") || $pagefile_info['ext'] == ".".$imageformat)
  {
    ini_set ("max_execution_time", "300"); // sets the maximum execution time of this script to 300 sec.
    
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
          ($effect == "sepia" && $sepia_treshold > 0 && $sepia_treshold <= 99.9) ||
          ($effect == "blur" && $blur_sigma > 0.1 && $blur_sigma <= 3 && $blur_radius !== NULL ) ||
          ($effect == "sharpen" && $sharpen_sigma > 0.1 && $sharpen_sigma <= 3 && $sharpen_radius !== NULL) ||
          ($effect == "sketch" && $sketch_sigma !== NULL && $sketch_radius !== NULL && $sketch_angle !== NULL) ||
          ($effect == "paint" && $paintvalue !== NULL)
        )
       )
    {
      $formats = "";

      // We need to reset here
      reset($mgmt_imageoptions);
      
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
        
        $mgmt_imageoptions[$formats]['original'] = "";
        
        if ($imageresize == "crop")
        {
          $mgmt_imageoptions[$formats]['original'] .= " -s ".$imagecropwidth."x".$imagecropheight." -c ".$imagex."x".$imagey;
        } 
        elseif (in_array ($imageresize, array("percentage", "imagewidth", "imageheight")))
        {
          $mgmt_imageoptions[$formats]['original'] .= " -s ".$imagewidth."x".$imageheight;
        }
        
        if ($rotate == "rotate") 
        {
          $mgmt_imageoptions[$formats]['original'] .= " -r ".$angle;
        }
        elseif ($rotate == "flip")
        {
           $mgmt_imageoptions[$formats]['original'] .= " ".$flip;
        }
        
        if ($use_brightness == 1)
        {
          $mgmt_imageoptions[$formats]['original'] .= " -b ".$brightness;
        } 
        
        if ($use_contrast == 1)
        {
          $mgmt_imageoptions[$formats]['original'] .= " -k ".$contrast;
        }

        if ($colorspace == 1) 
        {
          $mgmt_imageoptions[$formats]['original'] .= " -cs ".$imagecolorspace;
        }
        
        if ($effect == "sepia") 
        {
          $mgmt_imageoptions[$formats]['original'] .= " -sep ".$sepia_treshold."%";
        }
        elseif ($effect == "blur") 
        {
          $mgmt_imageoptions[$formats]['original'] .= " -bl ".$blur_radius."x".$blur_sigma;
        }
        elseif ($effect == "sharpen") 
        {
          $mgmt_imageoptions[$formats]['original'] .= " -sh ".$sharpen_radius."x".$sharpen_sigma;
        }
        elseif ($effect == "sketch") 
        {
          if ($sketch_angle > -1)
          {
            $sketch_angle = "+".$sketch_angle;
          }
          
          $mgmt_imageoptions[$formats]['original'] .= " -sk ".$sketch_radius."x".$sketch_sigma.$sketch_angle;
        }
        elseif ($effect == "paint") 
        {
          $mgmt_imageoptions[$formats]['original'] .= " -pa ".$paintvalue;
        }
        
        // Add the mandatory format
        $mgmt_imageoptions[$formats]['original'] .= " -f ".$imageformat;
        
        $result = createmedia ($site, $media_root, $media_root, $mediafile, $imageformat, 'original');  
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

    // if successful
    if ($result)
    {
      // get the file extension of the old file      
      $file_ext_old = strtolower (strrchr ($mediafile, "."));
      // get the file extension of the new file
      $file_ext_new = ".".$imageformat;
      // get file name without extension of the old file
      $mediafile_nameonly = strrev (substr (strstr (strrev ($mediafile), "."), 1));
      // get object name without extension
      $page_nameonly = strrev (substr (strstr (strrev ($pagefile_info['name']), "."), 1));
      
      $add_onload = "";
      
      // rename object file extension if file extension has changed due to coversion
      if ($file_ext_old != $file_ext_new)
      {
        // write new reference in object file
        $filedata = $filedata_orig = loadfile ($location, $page);
        if ($filedata != false) $filedata = setfilename ($filedata, "media", getobject ($mediafile_nameonly).$file_ext_new); 
        
        if ($filedata != false)
        {
          $test = savefile ($location, $page, $filedata);
          // remote client
          remoteclient ("save", "abs_path_".$cat, $site, $location, "", $page, "");                
        }
        else $test = false;     

        // on success
        if ($test == true)
        { 
          // rename media object after file extension has changed  
          $test = renameobject ($site, $location, $page, $page_nameonly, $user);
                 
          if ($test['result'] == true)
          {
            // remote client
            remoteclient ("save", "abs_path_".$cat, $site, $location, "", $page, "");                       
          
            // set new page name and media file name 
            $page = $test['object'];
            $mediafile = $mediafile_nameonly.$file_ext_new;

            // add onload
            $add_onload = "parent.frames['controlFrame'].location.href='control_content_menu.php?site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."';\n";
          }
          // on error
          else
          {
            // revert changes back
            $test = savefile ($location, $page, $filedata_orig);
            $show = $text18[$lang]."\n";         
          }           
        }
        // on error
        else
        {
          $show = $text26[$lang]."\n";         
        }
      }
      
      // create new thumbnail
      createmedia ($site, $media_root, $media_root, $mediafile, $imageformat, "thumbnail");
      
      $show = $text2[$lang];
    }
  }
  else
  {
    $show = $text27[$lang];
  }
}

// get file information of original component file
$pagefile_info = getfileinfo ($site, $location.$page, $cat); 

// get file information of new original media file
$media_size = @getimagesize ($media_root.$mediafile);

// image information
$thumb_size = array();
$media_thumb = $mediafile_info['filename'].".thumb.jpg";

if (file_exists ($media_root.$media_thumb))
{
  $media_thumb = $mediafile_info['filename'].".thumb.jpg";  
  $thumb_size = @getimagesize ($media_root.$media_thumb);
  
  $thumb_size[0] = 740;
  $imgratio =  $thumb_size[0] / $media_size[0];
  $thumb_size[1] = $media_size[1] * $imgratio;

  $mediaview = showmedia ($site."/".$mediafile, $pagefile_info['name'], "preview_no_rendering", "cropbox", $thumb_size[0], $thumb_size[1], "");
}

// security token
$token_new = createtoken ($user);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" type="text/css">
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/jquery/jquery-1.9.1.min.js"></script>
<script src="javascript/jquery/plugins/jquery.color.js"></script>
<script src="javascript/jquery-ui/jquery-ui-1.10.2.min.js"></script>
<script src="javascript/jcrop/jquery.Jcrop.min.js"></script>
<link rel="stylesheet" href="javascript/jquery-ui/jquery-ui-1.10.2.css" type="text/css" />
<link rel="stylesheet" href="javascript/jcrop/jquery.Jcrop.css" type="text/css" />

<script language="Javascript">
<!--
var jcrop;
var crop_active = false;
var thumbWidth = <?php echo $thumb_size[0] ?>;
var thumbHeight= <?php echo $thumb_size[1] ?>;
var imgWidth = <?php echo $media_size[0] ?>;
var imgHeight= <?php echo $media_size[1] ?>;
var thumbImgRatio = thumbWidth / imgWidth;
var imgRatio = imgWidth / imgHeight;
var test = <?php echo $imgratio; ?>;

function updateCoords(c)
{
	$('#imagecropheight').val(parseInt(c.h / thumbImgRatio));
	$('#imagecropwidth').val(parseInt(c.w  / thumbImgRatio));
	$('#imagex').val(parseInt(c.x / thumbImgRatio));
	$('#imagey').val(parseInt(c.y / thumbImgRatio));

	$("#imageresize0").attr('checked', true);
};

function updateSelection()
{
	var x = parseInt($('#imagex').val());
	x = Math.min(imgWidth, Math.max(0, x));
	$('#imagex').val(x);
	var y = parseInt($('#imagey').val());
	y = Math.min(imgHeight, Math.max(0, y));
	$('#imagey').val(y);

  var width = parseInt($('#imagecropwidth').val());
	width = Math.max(1, width);
  width = Math.min(imgWidth - x, width);
	$('#imagecropwidth').val(width);
	var height = parseInt($('#imagecropheight').val());
	height = Math.max(1, height);
  height = Math.min(imgHeight- y, height);
	$('#imagecropheight').val(height);

	jcrop.animateTo([ x*thumbImgRatio, y*thumbImgRatio, (x+width)*thumbImgRatio, (y+height)*thumbImgRatio ]);
	
	$("#imageresize0").attr('checked', true);
}

function releaseSelection()
{
	jcrop.release();
}

function initJcrop()
{
  if (crop_active == false)
  {
  	jcrop = $.Jcrop('#cropbox',{ onSelect: updateCoords });
    crop_active = true;
  
  	jcrop.setSelect([0, 0, thumbWidth, thumbHeight]);
  	$("#imagex").val(0);
  	$("#imagey").val(0);
  	$("#imagecropwidth").val(imgWidth);
  	$("#imagecropheight").val(imgHeight);
  
  	$("#imagecropwidth").change( updateSelection );
  	$("#imagecropheight").change( updateSelection );
  	$("#imagex").change( updateSelection );
  	$("#imagey").change( updateSelection );
  
  	$("#imageresize0").click( updateSelection );
  	$("#imageresize1").click( releaseSelection );
  	$("#imageresize2").click( releaseSelection );
  	$("#imageresize3").click( releaseSelection );
  }
}

function destroyJcrop ()
{
  jcrop.destroy();
  crop_active = false;
}

function validateForm() 
{
  var i,p,q,nm,test,num,min,max,errors='',args=validateForm.arguments;
  
  for (i=0; i<(args.length-2); i+=3) 
  { 
    test=args[i+2]; val=hcms_findObj(args[i]);
    
    if (val) 
    { 
      nm=val.name;
      nm=nm.substring(nm.indexOf('_')+1, nm.length);
      
      if ((val=val.value)!='') 
      {
        if (test.indexOf('isEmail')!=-1) 
        { 
          p=val.indexOf('@');
          if (p<1 || p==(val.length-1)) errors += nm+'-<?php echo $text15[$lang]; ?>.\n';
        } 
        else if (test!='R') 
        { 
          num = parseFloat(val);
          if (isNaN(val)) errors += '-<?php echo $text16[$lang]; ?>.\n';
          if (test.indexOf('inRange') != -1) 
          { 
            p=test.indexOf(':');
            min=test.substring(8,p); 
            max=test.substring(p+1);
            if (num<min || max<num) errors += '-<?php echo $text17[$lang]; ?> '+min+' - '+max+'.\n';
          } 
        } 
      } 
      else if (test.charAt(0) == 'R') errors += '-<?php echo $text18[$lang]; ?>.\n'; 
    }
  } 
  
  if (errors) 
  {
    alert(hcms_entity_decode('<?php echo $text19[$lang]; ?>:\n'+errors));
    return false;
  }  
  else return true;
}

function checkform()
{
  var result = true;
  var checked = false;
  
  if ($('#percentage').prop('checked'))
  {
    checked = true;
    result = validateForm ('imagepercentage','','RinRange1:200');
  }
  if (result && $('#width').prop('checked'))
  {
    checked = true;
    result = validateForm ('imagewidth','','RisNum');
  }
  if (result && $('#height').prop('checked'))
  {
    checked = true;
    result = validateForm ('imageheight','','RisNum');
  }
  if (result && $('#rotate').prop('checked'))
  {
    checked = true;
    result = true;
  } 
  if (result && $('#crop').prop('checked'))
  {
    checked = true;
    result = validateForm('imagecropwidth', '', 'RisNum', 'imagecropheight', '', 'RisNum', 'imagex', '', 'RisNum', 'imagey', '', 'RisNum');
  }
  if (result && $('#chbx_brightness').prop('checked'))
  {
    checked = true;
    result = validateForm('brightness', '', 'RinRange-100:100')
  } 
  
  if (result && $('#chbx_contrast').prop('checked'))
  {
    checked = true;
    result = validateForm('contrast', '', 'RinRange-100:100')
  } 
  
  if (result && $('#chbx_colorspace').prop('checked'))
  {
    checked = true;
    result = true;
  }
  if (result && $('#chbx_flip').prop('checked'))
  {
    checked = true;
    result = true;
  }
  if (result && $('#sepia').prop('checked'))
  {
    checked = true;
    result = validateForm('sepia_treshold', '', 'RinRange0:99.9');
  }
  if(result && $('#blur').prop('checked')) 
  {
    checked = true;
    result = validateForm('blur_radius', '', 'RisNum', 'blur_sigma', '', 'RinRange0.1:3');
  }
  if(result && $('#sharpen').prop('checked')) 
  {
    checked = true;
    result = validateForm('sharpen_radius', '', 'RisNum', 'sharpen_sigma', '', 'RinRange0.1:3');
  }
  if(result && $('#sketch').prop('checked')) 
  {
    checked = true;
    result = validateForm('sketch_radius', '', 'RisNum', 'sketch_sigma', '', 'RisNum', 'sketch_angle', '', 'RisNum');
  }
  if(result && $('#paint').prop('checked')) 
  {
    checked = true;
    result = validateForm('paint_value', '', 'RisNum');
  }
  
  if(!checked) {
    alert("<?php echo $text48[$lang]; ?>");
    result = false;
  }
  
  return result;
}

function submitform (check)
{
  if (check == true)
  {
    if (!confirm(hcms_entity_decode("<?php echo $text23[$lang] ?>"))) return false;
  }
  
  var result = checkform();
  
  if (result == true)
  {
    hcms_showHideLayers('savelayer','','show');
    document.mediaconfig.submit();
  }
  else return false;
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

function toggle_crop ()
{
  var crop = $('#crop');
  var cropwidth = $('#imagecropwidth');
  var cropheight = $('#imagecropheight');
  var x = $('#imagex');
  var y = $('#imagey');
  var percentage = $('#percentage');
  var width = $('#width');
  var height = $('#height');
  var sepia = $('#sepia');
  var blur = $('#blur');
  var sharpen = $('#sharpen');
  var sketch = $('#sketch');
  var paint = $('#paint');
  var chbxflip = $('#chbx_flip');
  
  if (crop.prop('checked')) 
  {
    cropwidth.prop('disabled', false);
    cropheight.prop('disabled', false);
    x.prop('disabled', false);
    y.prop('disabled', false);
    percentage.prop('checked', false);
    width.prop('checked', false);
    height.prop('checked', false);
    sepia.prop('checked', false);
    blur.prop('checked', false);
    sharpen.prop('checked', false);
    sketch.prop('checked', false);
    paint.prop('checked', false);
    chbxflip.prop('checked', false);
    
    initJcrop();
    
    toggle_size_height();
    toggle_size_width();
    toggle_percentage();
    toggle_sepia();
    toggle_sharpen();
    toggle_blur();
    toggle_sketch();
    toggle_paint();
    toggle_flip();
  } 
  else 
  {
    cropwidth.prop('disabled', true);
    cropheight.prop('disabled', true);
    
    x.prop('disabled', "disabled");
    y.prop('disabled', "disabled");
    
    destroyJcrop();
  }
}

function toggle_percentage () 
{
  var crop = $('#crop');
  var percentage = $('#percentage');
  var width = $('#width');
  var height = $('#height');
  var percent = $('#imagepercentage');
  
  if (percentage.prop('checked')) 
  {
    percent.prop('disabled', false);
    crop.prop('checked', false);
    width.prop('checked', false);
    height.prop('checked', false);
    
    toggle_size_height();
    toggle_size_width();
    toggle_crop();
  }
  else 
  {
    percent.prop('disabled', true);
  }
}

function toggle_size_width () 
{
  var crop = $('#crop');
  var percentage = $('#percentage');
  var width = $('#width');
  var height = $('#height');
  var imagewidth = $('#imagewidth');
  
  if (width.prop('checked')) 
  {
    imagewidth.prop('disabled', false);
    crop.prop('checked', false);
    percentage.prop('checked', false);
    height.prop('checked', false);
    
    toggle_size_height();
    toggle_percentage();
    toggle_crop();
  }
  else
  {
    imagewidth.prop('disabled', true);
  }
}

function toggle_size_height () 
{
  var crop = $('#crop');
  var percentage = $('#percentage');
  var width = $('#width');
  var height = $('#height');
  var imageheight = $('#imageheight');
  
  if (height.prop('checked')) 
  {
    imageheight.prop('disabled', false);
    crop.prop('checked', false);
    width.prop('checked', false);
    percentage.prop('checked', false);
    
    toggle_size_width();
    toggle_percentage();
    toggle_crop();
  }
  else
  {
    imageheight.prop('disabled', true);
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

function toggle_colorspace () 
{
  var chbx = $('#chbx_colorspace');
  var space = $('#colorspace');
  
  if (chbx.prop('checked'))
  {
    space.prop('disabled', false);
  }
  else
  {
    space.prop('disabled', true);
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

function toggle_sepia () 
{
  var sepia = $('#sepia');
  var treshold = $('#sepia_treshold');
  var blur = $('#blur');
  var sharpen = $('#sharpen');
  var sketch = $('#sketch');
  var paint = $('#paint');
  var crop = $('#crop');
  
  if (sepia.prop('checked')) 
  {
    treshold.prop('disabled', false);
    blur.prop('checked', false);
    sharpen.prop('checked', false);
    sketch.prop('checked', false);
    paint.prop('checked', false);
    crop.prop('checked', false);
    
    treshold.spinner("option", "disabled", false);
    
    toggle_blur();
    toggle_sharpen();
    toggle_sketch();
    toggle_paint();
    toggle_crop();
  }
  else
  {
    treshold.prop('disabled', true);
    
    treshold.spinner("option", "disabled", true);
  }
}

function toggle_blur () 
{
  var sepia = $('#sepia');
  var radius = $('#blur_radius');
  var sigma = $('#blur_sigma');
  var blur = $('#blur');
  var sharpen = $('#sharpen');
  var sketch = $('#sketch');
  var paint = $('#paint');
  var crop = $('#crop');
  
  if (blur.prop('checked'))
  {
    radius.prop('disabled', false);
    sigma.prop('disabled', false);
    sepia.prop('checked', false);
    sharpen.prop('checked', false);
    sketch.prop('checked', false);
    paint.prop('checked', false);
    crop.prop('checked', false);
    
    sigma.spinner("option", "disabled", false);
    
    toggle_sepia();
    toggle_sharpen();
    toggle_sketch();
    toggle_paint();
    toggle_crop();
  }
  else
  {
    sigma.prop('disabled', true);
    radius.prop('disabled', true);
    
   sigma.spinner("option", "disabled", true);
  }
}

function toggle_sharpen ()
{
  var sepia = $('#sepia');
  var radius = $('#sharpen_radius');
  var sigma = $('#sharpen_sigma');
  var blur = $('#blur');
  var sharpen = $('#sharpen');
  var sketch = $('#sketch');
  var paint = $('#paint');
  var crop = $('#crop');
  
  if (sharpen.prop('checked'))
  {
    radius.prop('disabled', false);
    sigma.prop('disabled', false);
    sepia.prop('checked', false);
    blur.prop('checked', false);
    sketch.prop('checked', false);
    paint.prop('checked', false);
    crop.prop('checked', false);
    
    sigma.spinner("option", "disabled", false);
    
    toggle_sepia();
    toggle_blur();
    toggle_sketch();
    toggle_paint();
    toggle_crop();
  }
  else
  {
    sigma.prop('disabled', true);
    radius.prop('disabled', true);
    
   sigma.spinner("option", "disabled", true);
  }
}

function toggle_sketch ()
{
  var sepia = $('#sepia');
  var radius = $('#sketch_radius');
  var sigma = $('#sketch_sigma');
  var angle = $('#sketch_angle');
  var blur = $('#blur');
  var sharpen = $('#sharpen');
  var sketch = $('#sketch');
  var paint = $('#paint');
  var crop = $('#crop');
  
  if (sketch.prop('checked'))
  {
    radius.prop('disabled', false);
    sigma.prop('disabled', false);
    angle.prop('disabled', false);
    sepia.prop('checked', false);
    blur.prop('checked', false);
    sharpen.prop('checked', false);
    paint.prop('checked', false);
    crop.prop('checked', false);
        
    toggle_sepia();
    toggle_blur();
    toggle_sharpen();
    toggle_paint();
    toggle_crop();
    
  }
  else
  {
    sigma.prop('disabled', true);
    radius.prop('disabled', true);
    angle.prop('disabled', true);
  }
}

function toggle_paint () 
{
  var sepia = $('#sepia');
  var value = $('#paint_value');
  var blur = $('#blur');
  var sharpen = $('#sharpen');
  var sketch = $('#sketch');
  var paint = $('#paint');
  var crop = $('#crop');
  
  if (paint.prop('checked'))
  {
    value.prop('disabled', false);
    sepia.prop('checked', false);
    blur.prop('checked', false);
    sketch.prop('checked', false);
    sharpen.prop('checked', false);
    crop.prop('checked', false);
        
    toggle_sepia();
    toggle_blur();
    toggle_sharpen();
    toggle_sketch();
    toggle_crop();
  }
  else
  {
    value.prop('disabled', true);
  }
}

function showPreview ()
{  
  if (!checkform()) return false;
  
  hcms_showHideLayers('savelayer','','show');
  
  var link = "<?php echo $mgmt_config['url_path_cms']; ?>service/generate_image_preview.php?media=<?php echo $mediafile; ?>&site=<?php echo $site; ?>&cat=<?php echo $cat; ?>&location=<?php echo $location_esc; ?>";
    
  var changed = false;
  
  var perc = $('#percentage');
  var width = $('#width');
  var height = $('#height')
  var crop = $('#crop');
  var rotate = $('#rotate');
  var chbx_brightness = $('#chbx_brightness');
  var chbx_contrast = $('#chbx_contrast');
  var chbx_colorspace = $('#chbx_colorspace');
  var chbx_flip = $('#chbx_flip');
  var sepia = $('#sepia');
  var blur = $('#blur');
  var sharpen = $('#sharpen');
  var sketch = $('#sketch');
  var paint = $('#paint');
  var format = $('#imageformat');
    
  if (perc.prop('checked'))
  {
    var percentage = $('#imagepercentage');
    
    changed = true;
    link += '&'+perc.getGeneratorParameter()+'&'+percentage.getGeneratorParameter();
  }
  else if (width.prop('checked'))
  {
    var imagewidth = $('#imagewidth');
    
    changed = true;
    link += '&'+width.getGeneratorParameter()+'&'+imagewidth.getGeneratorParameter();
  }
  else if (height.prop('checked'))
  {
    var imageheight = $('#imageheight');
    
    changed = true;
    link += '&'+height.getGeneratorParameter()+'&'+imageheight.getGeneratorParameter();
  }
  else if (crop.prop('checked')) 
  {
    var cropWidth = $('#imagecropwidth');
    var cropHeight = $('#imagecropheight');
    var x = $('#imagex');
    var y = $('#imagey');
    
    changed = true;
    link += '&'+crop.getGeneratorParameter()+'&'+cropWidth.getGeneratorParameter()+'&'+cropHeight.getGeneratorParameter();
    link += '&'+x.getGeneratorParameter()+'&'+y.getGeneratorParameter();
  }
  
  if (rotate.prop('checked'))
  {
    var degree = $('#degree');
    
    changed = true;
    link += '&'+rotate.getGeneratorParameter()+'&'+degree.getGeneratorParameter();
  }
  else if (chbx_flip.prop('checked'))
  {
    var flip = $('#flip');
    
    changed = true;
    link += '&'+chbx_flip.getGeneratorParameter()+'&'+flip.getGeneratorParameter();
  }
  
  if (chbx_brightness.prop('checked'))
  {
    var brightness = $('#brightness');
    
    changed = true;
    link += '&'+chbx_brightness.getGeneratorParameter()+'&'+brightness.prop('name')+'='+Math.round(brightness.val());
  }
  
  if (chbx_contrast.prop('checked'))
  {
    var contrast = $('#contrast');
    
    changed = true;
    link += '&'+chbx_contrast.getGeneratorParameter()+'&'+contrast.prop('name')+'='+Math.round(contrast.val());
  }
  
  if (chbx_colorspace.prop('checked'))
  {
    var colorspace = $('#colorspace');
    
    changed = true;
    link += '&'+chbx_colorspace.getGeneratorParameter()+'&'+colorspace.getGeneratorParameter();
  }
  
  if (sepia.prop('checked'))
  {
    var sepia_treshold = $('#sepia_treshold');
    
    changed = true;
    link += '&'+sepia.getGeneratorParameter()+'&'+sepia_treshold.getGeneratorParameter();
  }
  else if (blur.prop('checked'))
  {
    var radius = $('#blur_radius');
    var sigma = $('#blur_sigma');
    
    changed = true;
    link += '&'+blur.getGeneratorParameter()+'&'+radius.getGeneratorParameter()+'&'+sigma.getGeneratorParameter();
  }
  else if (sharpen.prop('checked'))
  {
    var radius = $('#sharpen_radius');
    var sigma = $('#sharpen_sigma');
    
    changed = true;
    link += '&'+sharpen.getGeneratorParameter()+'&'+radius.getGeneratorParameter()+'&'+sigma.getGeneratorParameter();
  }
  else if (sketch.prop('checked'))
  {
    var radius = $('#sketch_radius');
    var sigma = $('#sketch_sigma');
    var angle = $('#sketch_angle');
    
    changed = true;
    link += '&'+sketch.getGeneratorParameter()+'&'+radius.getGeneratorParameter()+'&'+sigma.getGeneratorParameter()+'&'+angle.getGeneratorParameter();
  }
  else if (paint.prop('checked'))
  {
    var value = $('#paint_value')
    
    changed = true;
    link += '&'+paint.getGeneratorParameter()+'&'+value.getGeneratorParameter();
  }
  
  link += '&'+format.getGeneratorParameter();
  
  link += '&thumbwidth='+thumbWidth;
  link += '&thumbheight='+thumbHeight;
  
  if(!changed)
    return false;
  
   $.ajax({
    url: link,
    dataType: 'json'
   })
   .success(function(data) {
     hcms_showHideLayers('savelayer','','hide');
     if(data.success) {
       hcms_openWindow(data.imagelink, 'preview', '', data.imagewidth, data.imageheight);
     } else {
       alert(data.message);
     }
   });
}

function activate ()
{
  $('#crop').attr('checked', true);
  toggle_crop();
  toggle_sepia();
  toggle_blur();
  toggle_sharpen();
  toggle_sketch();
  toggle_paint();
  toggle_flip();
  toggle_rotate();
  toggle_brightness();
  toggle_contrast();
  toggle_colorspace();
}

function toggleDivAndButton (caller, element)
{
  var options = $(element);
  caller = $(caller);  
  var time = 500;
    
  if (options.css('display') == 'none')
  {
    caller.addClass('hcmsButtonActive');
    document.forms['mediaconfig'].crop.checked = true;
    activate();
    options.fadeIn(time);
  }
  else
  {
    caller.removeClass('hcmsButtonActive');
    destroyJcrop();
    options.fadeOut(time);
  }
}

$(window).load( function()
{
  var spinner_config_bc = { step: 1, min: -100, max: 100}
  var spinner_config_sep = { step: 0.1, min: 0, max: 99.9}
  var spinner_config_sigma = { step: 0.1, min: 0.1, max: 3}
  $('#brightness').spinner(spinner_config_bc);
  $('#contrast').spinner(spinner_config_bc);
  $('#sepia_treshold').spinner(spinner_config_sep);
  $('#blur_sigma').spinner(spinner_config_sigma);
  $('#sharpen_sigma').spinner(spinner_config_sigma);
  
  // Add our special function
  $.fn.getGeneratorParameter = function() {
    return this.prop('name')+'='+this.val();
  }
  
});

<?php echo $add_onload; ?>
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
  width: 230px;
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
<div id="savelayer" class="hcmsWorkplaceGeneric" style="position:absolute; width:100%; height:100%; z-index:999; left:0px; top:0px; visibility:hidden;">
  <table width="100%" height="100%" border="0" cellpadding="3" cellspacing="1">
    <tr>
      <td align="center" valign="middle"><b><?php echo $text13[$lang]; ?></b><br /><br /><img src="<?php echo getthemelocation(); ?>img/loading.gif"></td>
    </tr>
  </table>
</div>

<?php
if ($show != "") echo showmessage ($show, 600, 80, $lang, "position:absolute; left:50px; top:150px;");
?>

<!-- top bar -->
<?php
echo showtopmenubar ($text0[$lang], array($text33[$lang] => 'onclick="toggleDivAndButton(this, \'#renderOptions\');"'), $lang, $mgmt_config['url_path_cms']."page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page));
?>

<!-- rendering settings -->
<div id="renderOptions" style="padding:10px; width:730px; display:none; vertical-align:top; z-index:1; margin-left:10px" class="hcmsMediaRendering">    
  <!-- start edit image -->
  <form name="mediaconfig" id="mediaconfig" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
    <input type="hidden" id="action" name="action" value="rendermedia">
    <input type="hidden" name="site" value="<?php echo $site; ?>">
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>">
    <input type="hidden" name="cat" value="<?php echo $cat; ?>">
    <input type="hidden" name="page" value="<?php echo $page; ?>">
    <input type="hidden" name="media" value="<?php echo $mediafile; ?>">
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
    
    <!-- crop -->
    <div class="cell">
      <div class="row">
        <input type="checkbox" id="crop" name="imageresize" value="crop" checked="checked" onclick="toggle_crop();" />
        <strong><label for='crop'><?php echo $text20[$lang]; ?></label></strong>
      </div>
      <div style="margin-left:20px;">
        <label style="width:111px; display:inline-block;" for="imagecropwidth"><?php echo $text6[$lang]; ?></label>
        <input name="imagecropwidth" type="text" id="imagecropwidth" size="5" maxlength="5" value="<?php echo $imagecropwidth; ?>" /> px
      </div>
      <div style="margin-left:20px;">
        <label style="width: 111px; display:inline-block;" for="imagecropheight"><?php echo $text7[$lang]; ?></label>
        <input name="imagecropheight" type="text" id="imagecropheight" size="5" maxlength="5" value="<?php echo $imagecropheight?>" /> px
      </div>
      <div style="margin-left:20px;">
        <label style="width:111px; display:inline-block;" for="imagex"><?php echo $text21[$lang]; ?></label>
        <input name="imagex" type="text" size="5" id="imagex" value="<?php echo $imagex; ?>" /> px
      </div>
      <div style="margin-left:20px;">
        <label style="width:111px; display:inline-block;" for="imagey"><?php echo $text22[$lang]; ?></label>
        <input name="imagey" type="text" size="5" id="imagey" value="<?php echo $imagey; ?>" /> px
      </div>
    </div>
      
    <!-- width or height -->
    <div class="cell">
      <div class="row" style="margin-left:20px;">
        <strong><?php echo $text5[$lang]; ?></strong>
      </div>
      <div class="row">
        <input type="checkbox" id="percentage" name="imageresize" value="percentage" onclick="toggle_percentage();" />
        <label style="width:80px; display:inline-block;" for="percentage"><?php echo $text4[$lang]; ?></label>
        <input name="imagepercentage" type="text" id="imagepercentage" size="5" maxlength="3" value="100" /> %
      </div>
      <div class="row">
        <input type="checkbox" id="width" name="imageresize" value="imagewidth" onclick="toggle_size_width();" />
        <label style="width:80px; display:inline-block;" for="width"><?php echo $text6[$lang]; ?></label>
        <input name="imagewidth" type="text" id="imagewidth" size="5" maxlength="5" value="<?php echo $media_size[0]; ?>" /> px
      </div>
      <div class="row">
        <input type="checkbox" id="height" name="imageresize" value="imageheight" onclick="toggle_size_height();" />
        <label style="width:80px; display:inline-block;" for="height"><?php echo $text7[$lang]; ?></label>
        <input name="imageheight" type="text" id="imageheight" size="5" maxlength="5" value="<?php echo $media_size[1]; ?>" /> px
      </div>
    </div>
    
    <?php if (getimagelib () != "GD") { ?>
    <!-- Effects -->
    <div class="cell">
      <div class="row" style="margin-left:20px;">
        <strong><?php echo $text39[$lang]; ?></strong>
      </div>
      <div class="row">
        <input type="checkbox" id="sepia" name="effect" value="sepia" onclick="toggle_sepia();" />
        <label style="width:60px; display:inline-block;" for="sepia"><?php echo $text40[$lang]; ?></label>
        <input name="sepia_treshold" type="text" id="sepia_treshold" size="2" maxlength="2" value="80" /> %
      </div>
      <div class="row">
        <input type="checkbox" id="blur" name="effect" value="blur" onclick="toggle_blur();" />
        <label style="width:60px; display:inline-block;" for="blur"><?php echo $text41[$lang]; ?></label>
        <input name="blur_radius" type="text" id="blur_radius" size="2" maxlength="2" value="0"  title="<?php echo $text44[$lang]; ?>" />
        <label style="width:6px; display:inline-block;"for="blur_sigma">x</label>
        <input name="blur_sigma" type="text" id="blur_sigma" size="3" maxlength="1" value="0.1"  title="<?php echo $text45[$lang]; ?>" />
      </div>
      <div class="row">
        <input type="checkbox" id="sharpen" name="effect" value="sharpen" onclick="toggle_sharpen();" />
        <label style="width:60px; display:inline-block;" for="sharpen"><?php echo $text42[$lang]; ?></label>
        <input name="sharpen_radius" type="text" id="sharpen_radius" size="2" maxlength="2" value="0"  title="<?php echo $text44[$lang]; ?>" />
        <label style="width:6px; display:inline-block;"for="sharpen_sigma">x</label>
        <input name="sharpen_sigma" type="text" id="sharpen_sigma" size="3" maxlength="1" value="0.1"  title="<?php echo $text45[$lang]; ?>" />
      </div>
      <div class="row">
        <input type="checkbox" id="sketch" name="effect" value="sketch" onclick="toggle_sketch();" />
        <label style="width:60px; display:inline-block;" for="sketch"><?php echo $text43[$lang]; ?></label>
        <input name="sketch_radius" type="text" id="sketch_radius" size="2" maxlength="2" value="0"  title="<?php echo $text44[$lang]; ?> "/>
        <label style="width:6px; display:inline-block;"for="sketch_sigma">x</label>
        <input name="sketch_sigma" type="text" id="sketch_sigma" size="2" maxlength="2" value="0" title="<?php echo $text45[$lang]; ?>" />
        <input name="sketch_angle" type="text" id="sketch_angle" size="3" maxlength="3" value="0" title="<?php echo $text46[$lang]; ?>" />
      </div>
      <div class="row">
        <input type="checkbox" id="paint" name="effect" value="paint" onclick="toggle_paint();" />
        <label style="width:60px; display:inline-block;" for="paint"><?php echo $text47[$lang]; ?></label>
        <input name="paint_value" type="text" id="paint_value" size="2" maxlength="3" value="0" />
      </div>
    </div>
    <?php } ?>
    
    <div class="cell">    
      <!-- rotate -->
      <div class="row">
        <input type="checkbox" id="rotate" name="rotate" value="rotate" onclick="toggle_rotate();" />
        <strong><label for="rotate"><?php echo $text24[$lang]; ?></label></strong>
      </div>
      <div style="margin-left:20px">
        <label for="degree"><?php echo $text25[$lang]; ?></label>
        <select name="degree" id="degree">
          <option value="90" selected="selected" >90&deg;</option>
          <option value="180" >180&deg;</option>
          <option value="-90" title="-90&deg;">270&deg;</option>
        </select>
      </div>
      
      <?php if (getimagelib () != "GD") { ?>
      <!-- flip flop -->
      <div class="row">
        <input type="checkbox" id="chbx_flip" name="rotate" value="flip" onclick="toggle_flip();" />
        <strong><label for="chbx_flip"><?php echo $text35[$lang]; ?></label></strong>
      </div>
      <div style="margin-left:20px">
        <select name="flip" id="flip">
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
      <?php } ?>      
    </div>
    
    <?php if (getimagelib () != "GD") { ?>
    <!-- brigthness / contrast -->
    <div class="cell">
      <div style="margin-left:20px" class="row">
        <strong><?php echo $text30[$lang].'/'.$text31[$lang]; ?></strong>
      </div>
      <div>
        <input type="checkbox" id="chbx_brightness" name="use_brightness" value="1" onclick="toggle_brightness();" />
        <label style="width:70px; display:inline-block;" for="chbx_brightness"><?php echo $text30[$lang]; ?></label>
        <input name="brightness" type="text" id="brightness" size="4" value="0" />
      </div>
      <div>
         <input type="checkbox" id="chbx_contrast" name="use_contrast" value="1" onclick="toggle_contrast();" />
        <label style="width:70px; display:inline-block;" for="chbx_contrast"><?php echo $text31[$lang]; ?></label>
        <input name="contrast" type="text" id="contrast" size="4" value="0" />
      </div>
    </div>
    <?php } ?>
    
    <div class="cell">
    <?php if (getimagelib () != "GD") { ?>
      <!-- colorspace -->
      <div class="row">
        <input type="checkbox" id="chbx_colorspace" name="colorspace" value="1" onclick="toggle_colorspace();" />
        <strong><label for="chbx_colorspace"><?php echo $text34[$lang]; ?></label></strong>
      </div>
      <div style="margin-left:20px">
        <select name="imagecolorspace" id="colorspace">
          <?php 
            foreach ($available_colorspaces as $value => $name)
            {
            ?>
            <option value="<?php echo $value; ?>"><?php echo $name ?></option>
            <?php
            }
          ?>
          </select>
      </div>
      <?php } ?>
      
      <!-- format -->
      <div style="margin-left:20px;">
        <strong><label for="imageformat"><?php echo $text8[$lang]; ?></label></strong>
      </div>
      <div style="margin-left:20px">
        <label for="imageformat"><?php echo $text9[$lang]; ?></label>
        <select name="imageformat" id="imageformat">
          <?php 
            $file_ext_old = strtolower (strrchr ($mediafile, ".")); 
            
            foreach ($convert_formats as $format)
            {
            ?>
            <option value="<?php echo strtolower($format); ?>" <?php if ($file_ext_old == ".".strtolower($format)) echo "selected=\"selected\""; ?>><?php echo strtoupper($format); ?></option>
            <?php
            }
          ?>
          </select>
      </div>
    </div>
    <br/>
    
    <div class="cell">
      <input class="hcmsButtonGreen" type="button" name="save" onclick="submitform(true);" value="<?php echo $text12[$lang]; ?>">
      <input class="hcmsButtonGreen" type="button" name="preview" onclick="showPreview();" value="<?php echo $text29[$lang]; ?>"> 
    </div>
  </form>
  <!-- end edit image -->
</div>
<!-- media view -->
<div style="margin:0; padding:10px; width:380px; height:500px; display:inline-block; z-index:1;">
  <!-- show image -->
  <?php echo $mediaview; ?>
</div>
</body>
</html>

