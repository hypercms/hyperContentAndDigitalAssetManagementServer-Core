<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
  
// ========================================== MEDIA FUNCTIONS =======================================

// ---------------------- createthumbnail_indesign -----------------------------
// function: createthumbnail_indesign()
// input: publication, path to source dir, path to destination dir, file name
// output: new file name / false on error (saves only thumbnail media file in destination location, only jpeg format is supported as output)

// description: creates a thumbnail by extracting the thumbnail from an indesign file and transferes the generated image via remoteclient.
// note for good results, InDesign Preferences must be set to save Preview image and at Extra Large size.

function createthumbnail_indesign ($site, $location_source, $location_dest, $file)
{
  global $mgmt_config;
  
  if (valid_publicationname ($site) && valid_locationname ($location_source) && valid_locationname ($location_dest) && valid_objectname ($file))
  {
    $filedata = file_get_contents ($location_source.$file);

    if ($filedata != "")
    {
      $result = "";
      
      // get file name without extension
      $file_name = strrev (substr (strstr (strrev ($file), "."), 1));      

      // try to extract data from XMP node
      // new method for XMP thumbnail extraction
      $regexp = "/<xmpGImg:image>.+<\/xmpGImg:image>/";
      preg_match_all ($regexp, $filedata, $result_array);

      if (isset ($result_array[0]) && count ($result_array[0]) > 0)
      {     
        $i = 0;
           
        foreach ($result_array[0] as $result_code)
        {
          // first thumbnails can not be properly extracted, so we use last thumbnail in indesign file
          $result = $result_code;
          $i++;
        }
      }
        
      // old method for XMP thumbnail extraction (deprecated)
      if ($result == "")
      {
        $xmpdata = getcontent ($filedata, "<xmp:PageInfo>");
        
        if ($xmpdata != false && $xmpdata[0] != "")
        {
          // get base64 encoded string from xml node
          $imgstr = getcontent ($xmpdata[0], "<xmpGImg:image>");

          if ($imgstr == false || $imgstr[0] == "")
          {
            // try attribute
            $result = getattribute ($xmpdata[0], "xmpGImg:image");           
          }
          else $result = $imgstr[0];
        }
      }
      
      // try to extract data from XAP node (deprecated)
      if ($result == "")
      {
        $xapdata = getcontent ($filedata, "<xap:Thumbnails>");
        
        if ($xapdata != false && $xapdata[0] != "")
        {
          // get base64 encoded string from xml node
          $imgstr = getcontent ($xapdata[0], "<xapGImg:image>");
  
          if ($imgstr == false || $imgstr[0] == "")
          {
            // try attribute
            $result = getattribute ($xapdata[0], "xapGImg:image");           
          }
          else $result = $imgstr[0]; 
        }
      }

      // save thumbnail file
      if ($result != "")
      {
        // prepare base64 encoded image string
        if ($result != false && $result != "")
        {
          $indd_thumbnail = strip_tags ($result);
          // remove decoded Line Feed character
        	$indd_thumbnail = str_replace ("#xA;", "", $indd_thumbnail);
        }
        
        // new file name
        $newfile = $file_name.".thumb.jpg";     
        $filehandler = fopen ($location_dest.$newfile, "wb");
      
        if ($filehandler)
        {
          fwrite ($filehandler, base64_decode ($indd_thumbnail));
          fclose ($filehandler);
          
          // remote client
          remoteclient ("save", "abs_path_media", $site, $location_dest, "", $newfile, "");
          
          return $newfile;
        }
        else
        {
          $errcode = "20221";
          $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|createthumbnail_indesign failed to save file: ".$location_dest.$newfile;           
       
          // save log
          savelog (@$error);
                 
          return false;
        }   
      }
      else
      {
        $errcode = "20222";
        $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|createthumbnail_indesign failed for file: ".$location_source.$file;           
     
        // save log
        savelog (@$error);
               
        return false;
      }     
    }
    else return false;
  }
  else return false;
}

// ---------------------- createthumbnail_video -----------------------------
// function: createthumbnail_video()
// input: publication, path to source dir, path to destination dir, file name, frame of video in the seconds or hh:mm:ss[.xxx]
// output: new file name / false on error (saves only thumbnail media file in destination location, only jpeg format is supported as output)

// description: creates a thumbnail picture of a video frame 

function createthumbnail_video ($site, $location_source, $location_dest, $file, $frame)
{
  if (valid_publicationname ($site) && valid_locationname ($location_source) && valid_locationname ($location_dest) && valid_objectname ($file) && $frame != "")
  {
    // remove .orig sub-file-extension
    if (strpos ($file, ".orig.") > 0) $newfile = str_replace (".orig.", ".", $file);
    else $newfile = $file;
    
    $fileinfo = getfileinfo ($site, $location_source.$newfile, "comp");
    $newfile = $fileinfo['filename'].".thumb.jpg";
    
    $cmd = 'ffmpeg -i "'.escapeshellcmd ($location_source.$file).'" -ss '.escapeshellcmd ($frame).' -f image2 -vframes 1 "'.escapeshellcmd ($location_dest.$newfile).'"';
    $output = array();
    $errcode = 1;
    
    // execute 
    exec ($cmd, $output, $errcode);
    
    if ($errcode) 
    {
      $error = array($mgmt_config['today'].'|hypercms_main.inc.php|error|'.$errcode.'|exec of ffmpeg (code:'.$errorCode.') (command:'.$cmd.') failed in createthumbnail_video for file: '.$file.' and frame: '.$frame);
      // save log
      savelog (@$error);
      return false;
    } 
    else
    {
      return $newfile;
    }
  }
  else return false;
}
    
// ---------------------- createmedia -----------------------------
// function: createmedia()
// input: publication, path to source dir, path to destination dir, file name, format (file extension w/o dot) of destination file (optional), 
//        type of image/video/audio file [thumbnail,origthumb(thumbail made from original video/audio),original,any other string present in $mgmt_imageoptions] (optional)
// output: new file name / false on error (saves original or thumbnail media file in destination location, for thumbnail only jpeg format is supported as output)

// description: creates an new image from original or creates a thumbnail and transferes the generated image via remoteclient  

function createmedia ($site, $location_source, $location_dest, $file, $format="", $type="thumbnail")
{
  global $mgmt_config, $mgmt_imagepreview, $mgmt_mediapreview, $mgmt_mediaoptions, $mgmt_imageoptions, $mgmt_maxsizepreview, $mgmt_mediametadata;

  if (valid_publicationname ($site) && valid_locationname ($location_source) && valid_locationname ($location_dest) && valid_objectname ($file))
  {
    include ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
    
    // add slash if not present at the end of the location string
    if (substr ($location_source, -1) != "/") $location_source = $location_source."/";
    if (substr ($location_dest, -1) != "/") $location_dest = $location_dest."/";    
  	//The GD Libary only supports jpg, png and gif
  	$GD_allowed_ext = array (".jpg", ".jpeg", ".gif", ".png");
    // get file name without extension
    $file_name = strrev (substr (strstr (strrev ($file), "."), 1));
    // get the file extension
    $file_ext = strtolower (strrchr ($file, "."));
    // normalize format
    if ($format != "") $format = strtolower ($format);
    // get file size of media file in kB
    $filesize_orig = round (@filesize ($location_source.$file) / 1024, 0);
    // get file width and heigth in pixels
    $imagesize_orig = @getimagesize ($location_source.$file);
    // get file-type
    $filetype_orig = getfiletype ($file_ext);
    // save input type
    $type_memory = $type;
    // MD5 hash of the original file
    $md5_hash = md5_file ($location_source.$file);

    if ($imagesize_orig != false)
    {
      $imagewidth_orig = $imagesize_orig[0];
      $imageheight_orig = $imagesize_orig[1];
    }
    else
    {
      $imagewidth_orig = 0;
      $imageheight_orig = 0;    
    }
    
    // Default jpg/jpeg options
    if (!array_key_exists ('.jpg.jpeg', $mgmt_imageoptions))
      $mgmt_imageoptions['.jpg.jpeg']= array();
    
    if (!array_key_exists ('original', $mgmt_imageoptions['.jpg.jpeg'])) 
      $mgmt_imageoptions['.jpg.jpeg']['original'] = "-f jpg";
      
    if (!array_key_exists ('thumbnail', $mgmt_imageoptions['.jpg.jpeg'])) 
      $mgmt_imageoptions['.jpg.jpeg']['thumbnail'] = "-s 180x180 -f jpg";
      
    // Default gif options
    if (!array_key_exists ('.gif', $mgmt_imageoptions))
      $mgmt_imageoptions['.gif']= array();
    
    if (!array_key_exists('original', $mgmt_imageoptions['.gif'])) 
      $mgmt_imageoptions['.gif']['original'] = "-f gif";
      
    // Default png options
    if (!array_key_exists ('.png', $mgmt_imageoptions))
      $mgmt_imageoptions['.png']= array();
    
    if (!array_key_exists ('original', $mgmt_imageoptions['.png'])) 
      $mgmt_imageoptions['.png']['original'] = "-f png";
    
    
    // if source file exists
    if (is_file ($location_source.$file))
    {      
      // check max file size in MB for certain file extensions
      if (is_array ($mgmt_maxsizepreview))
      {
        reset ($mgmt_maxsizepreview);   
        
        // defined extension for maximum file size restriction in MB
        foreach ($mgmt_maxsizepreview as $maxsizepreview_ext => $maxsizepreview)
        {        
          if ($file_ext != "" && substr_count ($maxsizepreview_ext.".", $file_ext.".") > 0)
          {
            if ($mgmt_maxsizepreview[$maxsizepreview_ext] > 0 && ($filesize_orig / 1024) > $mgmt_maxsizepreview[$maxsizepreview_ext]) return false;
          }
        }
      } 
      
      // ---------------------- if Adobe InDesign file ------------------------
      // no additional software necessary, the extracted thumbnail will be used as it is
      if ($file_ext == ".indd" && ($type == "thumbnail" || $type == "origthumb"))
      {
        $newfile = createthumbnail_indesign ($site, $location_source, $location_dest, $file);
        
        // get media information from thumbnail
        if ($newfile != false)
        {
          $id = getmediacontainerid ($newfile);
          $imagecolor = getimagecolors ($site, $newfile);
        
          // write media information to DB
          if ($id != "") rdbms_setmedia ($id, $filesize_orig, $filetype_orig, $imagewidth_orig, $imageheight_orig, $imagecolor['red'], $imagecolor['green'], $imagecolor['blue'], $imagecolor['colorkey'], $imagecolor['imagetype'], $md5_hash);
        }   
        
        return $newfile;  
      }
      
      // -------------- if image conversion software is given -----------------
      if (is_array ($mgmt_imagepreview) && sizeof ($mgmt_imagepreview) > 0)
      {
        // redefine type (for images thumbnail and preview are the same)
        if ($type == "origthumb") $type = "thumbnail";
              
        // define format if not set
        if ($format == "") $format_set = "jpg";
        else $format_set = $format;
            
        reset ($mgmt_imagepreview);    
        
        // supported extensions for image rendering
        foreach ($mgmt_imagepreview as $imagepreview_ext => $imagepreview)
        {
          // check file extension
          if ($file_ext != "" && substr_count ($imagepreview_ext.".", $file_ext.".") > 0)
          {
            reset ($mgmt_imageoptions);  
            $i = 1;
            
            // extensions for certain image rendering options
            foreach ($mgmt_imageoptions as $imageoptions_ext => $imageoptions)
            {
              // if we make a thumbnail we always use the thumbnail configuration from the jpg
              $check1 = $type == 'thumbnail' && substr_count ($imageoptions_ext.".", ".jpg.") > 0;
              // else we check the format we convert to
              $check2 = $type != 'thumbnail' && substr_count ($imageoptions_ext.".", ".".$format_set.".") > 0;
              // we also need to check if the type array is present
              $check3 = array_key_exists ($type, $mgmt_imageoptions[$imageoptions_ext]);
              
              // get image rendering options based on given destination format
              if (($check1 || $check2) && $check3)
              {
                // Image size (in pixel) definition
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-s ") > 0)
                {
                  $imagesize = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-s");
                  list ($imagewidth, $imageheight) = explode ("x", $imagesize);
                  
                  // ImageMagick resize parameter (resize will fit the image into the requested size, aspect ratio is preserved)
                  $imageresize = "-resize ".round ($imagewidth, 0)."x".round ($imageheight, 0);
                }
                else $imageresize = "";
             
                // Image crop
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-c ") > 0) $crop_mode = true;
                else $crop_mode = false;
                
                if ($crop_mode)
                {
                  $cropoffset = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-c");
                  list ($offsetX, $offsetY) = explode ("x", $cropoffset);
                }
                else
                {
                  if (empty ($imagewidth) || $imagewidth == false) $imagewidth = "180";
                  if (empty ($imageheight) || $imageheight == false) $imageheight = "180";
                }

                // image format (image file extension) definition
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-f ") > 0)
                {
                  $imageformat = strtolower (getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-f"));
                  if (empty ($imageformat) || $imageformat == false) $imageformat = "jpg";
                }
                else $imageformat = "jpg";
                
                // image rotation
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-r ") > 0) 
                {
                  $imagerotation = intval (getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-r"));
                  // ImageMagick rotate parameter
                  $imagerotate = "-rotate ".$imagerotation;
                  
                  // no resize if rotation is used
                  $imageresize = "";
                }
                else
                {
                  $imagerotate = "";
                  $imagerotation = "";
                }
                
                // image brightness
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-b ") > 0) 
                {
                  $imagebrightness = intval (getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-b"));
                  
                  if ($imagebrightness > 100) $imagebrightness = 100;
                  elseif ($imagebrightness < -100) $imagebrightness = -100;
                }
                else $imagebrightness = 0;
                
                // image contrast
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-k ") > 0) 
                {
                  $imagecontrast = intval (getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-k"));

                  if ($imagecontrast > 100) $imagecontrast = 100;
                  elseif ($imagecontrast < -100) $imagecontrast = -100;
                }
                else $imagecontrast = 0;
                
                // set image brightness parameters for ImageMagick
                $imageBrightnessContrast = "";
                
                if ($imagebrightness != 0 || $imagecontrast != 0)
                {
                  $imageBrightnessContrast = "-brightness-contrast ";
                  
                  if ($imagebrightness == 0) $imageBrightnessContrast .= "0x";
                  else $imageBrightnessContrast .= $imagebrightness."x";
                  
                  if ($imagecontrast == 0) $imageBrightnessContrast .= "0"; 
                  else $imageBrightnessContrast .= $imagecontrast;
                }
                
                // set image color space
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-cs ") > 0) 
                {
                  $imagecolorspace = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-cs");
                  
                  if ($imagecolorspace == "" || $imagecolorspace == false) $imagecolorspace = "";
                  else $imagecolorspace = "-colorspace ".$imagecolorspace;
                }
                else $imagecolorspace = "";
								
								// set image icc profile 
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-p ") > 0) 
                {
                  $iccprofile = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-p");
                  
                  if ($iccprofile == "" || $iccprofile == false) $iccprofile = "";
                  else $iccprofile = "-profile ".$iccprofile;
                }
                else $iccprofile = "";
                
                // set flip
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-fv ") > 0) 
                {
                  $imageflipv = "-flop";
                }
                else $imageflipv = "";
                
                // set flop
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-fh ") > 0) 
                {
                  $imagefliph = "-flip";
                }
                else $imagefliph = "";
                
                // Combine flip and flop into one
                $imageflip = $imageflipv." ".$imagefliph;
                
                // set sepia
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-sep ") > 0) 
                {
									$sepia = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-sep");
                  
                  if ($sepia == "" || $sepia == false) $sepia = "";
                  else $sepia = "-sepia-tone ".$sepia;
                }
                else $sepia = "";
                
                // set sharpen
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-sh ") > 0) 
                {
									$sharpen = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-sh");
                  
                  if ($sharpen == "" || $sharpen == false) $sharpen = "";
                  else $sharpen = "-sharpen ".$sharpen;
                }
                else $sharpen = "";
                
                // set blur
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-bl ") > 0) 
                {
									$blur = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-bl");
                  
                  if ($blur == "" || $blur == false) $blur = "";
                  else $blur = "-blur ".$blur;
                }
                else $blur = "";
                
                // set sketch
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-sk ") > 0) 
                {
									$sketch = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-sk");
                  
                  if ($sketch == "" || $sketch == false) $sketch = "";
                  else $sketch = "-sketch ".$sketch;
                }
                else $sketch = "";
                
                // set paint
                if (strpos ("_".$mgmt_imageoptions[$imageoptions_ext][$type], "-pa ") > 0) 
                {
									$paint = getoption ($mgmt_imageoptions[$imageoptions_ext][$type], "-pa");
                  
                  if ($paint == "" || $paint == false) $paint = "";
                  else $paint = "-paint ".$paint;
                }
                else $paint = "";

                // -------------------- convert image using ImageMagick ----------------------
                if (!empty ($mgmt_imagepreview[$imagepreview_ext]) && $mgmt_imagepreview[$imagepreview_ext] != "GD")
                {
                  // delete thumbnail
                  if ($type == "thumbnail" && @is_file ($location_dest.$file_name.".thumb.jpg"))
                  {
                    @unlink ($location_dest.$file_name.".thumb.jpg");
                  }
                  // copy original image before conversion to restore it if an error occured              
                  elseif ($type != "thumbnail" && @is_file ($location_source.$file))
                  {
                    // create buffer file
                    $buffer_file = $location_dest.$file_name.".buffer".$file_ext;
                    @copy ($location_source.$file, $buffer_file);
                    // delete the old file if we use original
                    if ($type == "original") @unlink ($location_source.$file);
                  }
               
                  // DOCUMENT: document-based formats
                  if ($file_ext == ".pdf" || $file_ext == ".eps")
                  {
                    if ($type == "thumbnail")
                    {
                      $newfile = $file_name.".thumb.jpg";
                      $cmd = $mgmt_imagepreview[$imagepreview_ext]." \"".escapeshellcmd ($location_source.$file)."[0]\" ".escapeshellcmd ($imageresize)." ".escapeshellcmd ($imagecolorspace)." ".escapeshellcmd ($iccprofile)." \"".escapeshellcmd ($location_dest.$newfile)."\"";
                    }
                    else 
                    {
                      if ($type == "original") $newfile = $file_name.".".$imageformat;
                      else $newfile = $file_name.".".$type.".".$imageformat;
                        
                      $cmd = $mgmt_imagepreview[$imagepreview_ext]." \"".escapeshellcmd ($buffer_file)."[0]\" ".escapeshellcmd ($imagerotate)." ".escapeshellcmd ($imageBrightnessContrast)." ".escapeshellcmd ($imageresize)." ".escapeshellcmd ($imagecolorspace)." ".escapeshellcmd ($iccprofile)." ".escapeshellcmd ($imageflip)." ".escapeshellcmd ($sepia)." ".escapeshellcmd ($sharpen)." ".escapeshellcmd ($blur)." ".escapeshellcmd ($sketch)." ".escapeshellcmd ($paint)." \"".escapeshellcmd ($location_dest.$newfile)."\"";
                    }
                    
                    @exec ($cmd, $buffer, $errorCode);
                    
                    // on error
                    if ($errorCode)
                    {
                      // restore original file
                      if ($type == "original") @copy ($buffer_file, $location_source.$file);

                      $errcode = "20231";
                      $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of imagemagick (code:$errorCode, command:$cmd) failed in createmedia for file: ".$file;
                    }
                    // on success
                    else
                    {
                      // copy metadata from original file using EXIFTOOL
                      if ($type == "original") copymetadata ($buffer_file, $location_dest.$newfile);
                      // remote client
                      if ($type == "thumbnail" || $type == "original") remoteclient ("save", "abs_path_media", $site, $location_dest, "", $newfile, "");
                    }      
                  }
                  // PHOTOSHOP: layered PSD-file
                  elseif ($file_ext == ".psd")
                  {
                    if ($type == "thumbnail") $cmd = $mgmt_imagepreview[$imagepreview_ext]." \"".escapeshellcmd ($location_source.$file)."\" -flatten \"".escapeshellcmd ($location_dest.$file_name).".buffer.jpg\"";
                    elseif ($type == "original") $cmd = $mgmt_imagepreview[$imagepreview_ext]." \"".escapeshellcmd ($buffer_file)."\" -flatten \"".escapeshellcmd ($location_dest.$file_name).".buffer.jpg\"";
                    else $cmd = $mgmt_imagepreview[$imagepreview_ext]." \"".escapeshellcmd ($buffer_file)."\" -flatten \"".escapeshellcmd ($location_dest.$file_name).".buffer.jpg\"";
      
                    @exec ($cmd, $buffer, $errorCode);
      
                    // on error
                    if ($errorCode)
                    {
                      // restore original file
                      if ($type == "original") @copy ($buffer_file, $location_source.$file);
                      
                      $errcode = "20232";
                      $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of imagemagick (code:$errorCode, command:$cmd) failed in createmedia for file: ".$file;   
                    }
                    // on success
                    else
                    {              
                      if (@is_file ($location_dest.$file_name.".buffer.jpg"))
                      {
                        if ($type == "thumbnail")
                        { 
                          $newfile = $file_name.".thumb.jpg";
                          $cmd = $mgmt_imagepreview[$imagepreview_ext]." \"".escapeshellcmd ($location_dest.$file_name).".buffer.jpg\" ".escapeshellcmd ($imageresize)." ".escapeshellcmd ($imagecolorspace)." ".escapeshellcmd ($iccprofile)." \"".escapeshellcmd ($location_dest.$newfile)."\"";
                        }
                        else
                        {
                          if ($type == "original") $newfile = $file_name.".".$imageformat;
                          else $newfile = $file_name.".".$type.".".$imageformat;
                            
                          if ($crop_mode)
                          {
                            $cmd = $mgmt_imagepreview[$imagepreview_ext]." \"".escapeshellcmd ($location_dest.$file_name).".buffer.jpg\" -crop ".escapeshellcmd ($imagewidth)."x".escapeshellcmd ($imageheight)."+".escapeshellcmd ($offsetX)."+".escapeshellcmd ($offsetY)." ".escapeshellcmd ($imageBrightnessContrast)." ".escapeshellcmd ($imagecolorspace)." ".escapeshellcmd ($iccprofile)." \"".escapeshellcmd ($location_dest.$newfile)."\"";
                          }
                          else
                          {
           		              $cmd = $mgmt_imagepreview[$imagepreview_ext]." \"".escapeshellcmd ($location_dest.$file_name).".buffer.jpg\" ".escapeshellcmd ($imageresize)." ".escapeshellcmd ($imagerotate)." ".escapeshellcmd ($imageBrightnessContrast)." ".escapeshellcmd ($imagecolorspace)." ".escapeshellcmd ($iccprofile)." ".escapeshellcmd ($imageflip)." ".escapeshellcmd ($sepia)." ".escapeshellcmd ($sharpen)." ".escapeshellcmd ($blur)." ".escapeshellcmd ($sketch)." ".escapeshellcmd ($paint)." \"".escapeshellcmd ($location_dest.$newfile)."\"";
                          }
                        }
                        
                        @exec ($cmd, $buffer, $errorCode);

                        // delete buffer file
                        @unlink ($location_dest.$file_name.".buffer.jpg");   
                        
                        // on error
                        if ($errorCode)
                        {
                          // restore original image
                          if ($type == "original") @copy ($buffer_file, $location_source.$file);
                                
                          $errcode = "20233";
                          $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of imagemagick (code:$errorCode, command:$cmd) failed in createmedia for file: ".$file;   
                        }
                        // on success
                        else
                        {
                          // copy metadata from original file using EXIFTOOL
                          if ($type == "original") copymetadata ($buffer_file, $location_dest.$newfile);
                          // remote client
                          if ($type == "thumbnail" || $type == "original") remoteclient ("save", "abs_path_media", $site, $location_dest, "", $newfile, "");
                        }       
                      }
                    }
                  }
                  // IMAGE: standard images
                  else
                  {
                    if ($type == "thumbnail")
                    {
                      $cmd = $mgmt_imagepreview[$imagepreview_ext]." -size ".escapeshellcmd ($imagewidth)."x".escapeshellcmd ($imageheight)." \"".escapeshellcmd ($location_source.$file)."[0]\" ".escapeshellcmd ($imageresize)." ".escapeshellcmd ($imagecolorspace)." ".escapeshellcmd ($iccprofile)." \"".escapeshellcmd ($location_dest.$file_name).".buffer.bmp\"";
                    }
                    else
                    {
                      if ($crop_mode)
                      {
                        $cmd = $mgmt_imagepreview[$imagepreview_ext]." -crop ".escapeshellcmd ($imagewidth)."x".escapeshellcmd ($imageheight)."+".escapeshellcmd ($offsetX)."+".escapeshellcmd ($offsetY)." ".escapeshellcmd ($imageBrightnessContrast)." \"".escapeshellcmd ($buffer_file)."[0]\" ".escapeshellcmd ($imagerotate)." ".escapeshellcmd ($imagecolorspace)." ".escapeshellcmd ($iccprofile)." \"".escapeshellcmd ($location_dest.$file_name).".buffer.bmp\"";
                      }
                      else
                      {
                        $cmd = $mgmt_imagepreview[$imagepreview_ext]." -size ".escapeshellcmd ($imagewidth)."x".escapeshellcmd ($imageheight)." \"".escapeshellcmd ($buffer_file)."[0]\" ".escapeshellcmd ($imageresize)." ".escapeshellcmd ($imagerotate)." ".escapeshellcmd ($imageBrightnessContrast)." ".escapeshellcmd ($imagecolorspace)." ".escapeshellcmd ($iccprofile)." ".escapeshellcmd ($imageflip)." ".escapeshellcmd ($sepia)." ".escapeshellcmd ($sharpen)." ".escapeshellcmd ($blur)." ".escapeshellcmd ($sketch)." ".escapeshellcmd ($paint)." \"".escapeshellcmd ($location_dest.$file_name).".buffer.bmp\"";
                      }
                    }

                    @exec ($cmd, $buffer, $errorCode);

                    // on error
                    if ($errorCode)
                    {
                      // restore original image
                      if ($type == "original") @copy ($buffer_file, $location_source.$file);
                                   
                      $errcode = "20234";
                      $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of imagemagick (code:$errorCode) (command:$cmd) failed in createmedia for file: ".$location_source.$file;   
                    }
                    // on success
                    else
                    {          
                      if (@is_file ($location_dest.$file_name.".buffer.bmp"))
                      {
                        if ($type == "thumbnail")
                        {
                          $newfile = $file_name.".thumb.jpg";
                          $cmd = $mgmt_imagepreview[$imagepreview_ext]." \"".escapeshellcmd ($location_dest.$file_name).".buffer.bmp\" ".escapeshellcmd ($imagecolorspace)." ".escapeshellcmd ($iccprofile)." \"".escapeshellcmd ($location_dest.$newfile)."\"";
                        }
                        else
                        {
                          if ($type == "original") $newfile = $file_name.".".$imageformat;
                          else $newfile = $file_name.".".$type.".".$imageformat;
                            
                          $cmd = $mgmt_imagepreview[$imagepreview_ext]." \"".escapeshellcmd ($location_dest.$file_name).".buffer.bmp\" ".escapeshellcmd ($imagecolorspace)." ".escapeshellcmd ($iccprofile)." \"".escapeshellcmd ($location_dest.$newfile)."\"";
                        }
                        
                        @exec ($cmd, $buffer, $errorCode);
                        
                        // delete buffer file
                        @unlink ($location_dest.$file_name.".buffer.bmp");
                        
                        // on error
                        if ($errorCode)
                        {
                          // restore original image
                          if ($type == "original") @copy ($buffer_file, $location_source.$file);
                      
                          $errcode = "20235";
                          $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of imagemagick (code:$errorCode, command:$cmd) failed in createmedia for file: ".$file;   
                        } 
                        // on success
                        else
                        {
                          // copy metadata from original file using EXIFTOOL
                          if ($type == "original") copymetadata ($buffer_file, $location_dest.$newfile);
                          // remote client
                          if ($type == "thumbnail" || $type == "original") remoteclient ("save", "abs_path_media", $site, $location_dest, "", $newfile, "");
                        }               
                      }
                    }
                  }

                  // delete buffer file
                  if ($type != "thumbnail") @unlink ($buffer_file);   
                  
                  // save log
                  savelog (@$error);           
                  
                  // return new file name if new file is larger than 5 bytes
                  if (!empty ($newfile) && @filesize ($location_dest.$newfile) > 5)
                  { 
                    // get media information from thumbnail
                    if ($type == "thumbnail")
                    {
                      $id = getmediacontainerid ($newfile);
                      $imagecolor = getimagecolors ($site, $newfile);
                    
                      // write media information to DB
                      if ($id != "") rdbms_setmedia ($id, $filesize_orig, $filetype_orig, $imagewidth_orig, $imageheight_orig, $imagecolor['red'], $imagecolor['green'], $imagecolor['blue'], $imagecolor['colorkey'], $imagecolor['imagetype'], $md5_hash);
                    }                
                          
                    return $newfile;
                  }
                  else
                  {
                    // delete thumbnail file
                    @unlink ($location_dest.$file_name.".thumb.jpg");
                    // remote client
                    if ($type == "thumbnail" || $type == "original") remoteclient ("delete", "abs_path_media", $site, $location_dest, "", $file_name.".thumb.jpg", "");
                    
                    return false;
                  }          
                }
                // -------------------- convert image using GD-Library -----------------------
                elseif ((empty ($mgmt_imagepreview[$imagepreview_ext]) || $mgmt_imagepreview[$imagepreview_ext] == "GD") && in_array (strtolower($file_ext), $GD_allowed_ext))
                {
                  // get image size
                  $imgdata = @getimagesize ($location_source.$file);
                  
                  // calculate aspect ratio
                  if (is_array ($imgdata))
                  {
                		$imgwidth = $imgdata[0];
                		$imgheight = $imgdata[1];
                    $imgratio = $imgwidth / $imgheight;
                  }
                  
                  if ($imgwidth > 0 && $imgheight > 0)
                  {    
                		if ($imgratio > 0)
                    {
                      (int)$resizedwidth = $imagewidth;
                		  (int)$resizedheight = $imageheight / $imgratio;
                    }
                    else
                    {
                      (int)$resizedwidth = $imagewidth * $imgratio;
                		  (int)$resizedheight = $imageheight;
                    }
                    
                		$imgresized = @ImageCreateTrueColor ($resizedwidth, $resizedheight);
                
                		if ($file_ext == ".gif") $imgsource = @ImageCreateFromGif ($location_source.$file);
                		elseif ($file_ext == ".jpg" || $file_ext == ".jpeg") $imgsource = @ImageCreateFromJpeg ($location_source.$file);
                    elseif ($file_ext == ".png") $imgsource = @ImageCreateFromPng ($location_source.$file);
                    else return false;
                
                    if ($crop_mode)
                    {
                      @imagecopyresampled ($imgresized, $imgsource, 0, 0, $offsetX, $offsetY, $imagewidth, $imageheight, $imagewidth, $imageheight);
                    }
                    else
                    {
                      @ImageCopyResized ($imgresized, $imgsource, 0, 0, 0, 0, $resizedwidth, $resizedheight, $imgwidth, $imgheight);
                    }        
        
                    if ($imageformat == "jpg" && function_exists ("imagejpeg"))
                    {
                      if ($type == "thumbnail")
                      {
                        $newfile = $file_name.".thumb.jpg";
                        $result = @imagejpeg ($imgresized, $location_dest.$newfile);
                      }
                      else
                      {
                        if ($type == "original") $newfile = $file_name.".jpg";
                        else $newfile = $file_name.".".$type.".jpg";
                        
                        $result = @imagejpeg ($imgresized, $location_dest.$newfile);
                        
                        // delete original file if file extension has changed
                        if ($result == true && $file_ext != ".".$format_set && $type == "original") @unlink ($location_source.$file);
                      }
                    }
                    elseif ($imageformat == "png" && function_exists("imagepng"))
                    {
                      if ($type == "thumbnail")
                      {
                        $newfile = $file_name.".thumb.png";
                        $result = @imagepng ($imgresized, $location_dest.$newfile);
                      }
                      else
                      {
                        if ($type == "original") $newfile = $file_name.".png";
                        else $newfile = $file_name.".".$type.".png";
                          
                        $result = @imagepng ($imgresized, $location_dest.$newfile);
                        
                        // delete original file if file extension has changed
                        if ($result == true && $file_ext != ".".$format_set && $type == "original") @unlink ($location_source.$file);
                      }
                    }
                    elseif ($imageformat == "gif" && function_exists("imagegif"))
                    {
                      if ($type == "thumbnail")
                      {
                        $newfile = $file_name.".thumb.gif";
                        $result = @imagegif ($imgresized, $location_dest.$newfile);
                      }
                      else
                      {
                        if ($type == "original") $newfile = $file_name.".gif";
                        else $newfile = $file_name.".".$type.".gif";
                          
                        $result = @imagegif ($imgresized, $location_dest.$newfile);
                        
                        // delete original file if file extension has changed
                        if ($result == true && $file_ext != ".".$format_set && $type == "original") @unlink ($location_source.$file);
                      }
                    }
                
                		@ImageDestroy ($imgsource);
                		@ImageDestroy ($imgresized);

                    if ($result == true)
                    {
                      // rotate image
                      if ($type != "thumbnail" && $imagerotation != "") $result = rotateimage ($site, $newfile, $imagerotation, $format_set);
                            
                      // remote client
                      if ($type == "thumbnail" || $type == "original") remoteclient ("save", "abs_path_media", $site, $location_dest, "", $newfile, "");
                      
                      // get media information from thumbnail
                      if ($type == "thumbnail")
                      {
                        $id = getmediacontainerid ($newfile);
                        $imagecolor = getimagecolors ($site, $newfile);
                      
                        // write media information to DB
                        if ($id != "") rdbms_setmedia ($id, $filesize_orig, $filetype_orig, $imagewidth_orig, $imageheight_orig, $imagecolor['red'], $imagecolor['green'], $imagecolor['blue'], $imagecolor['colorkey'], $imagecolor['imagetype'], $md5_hash);
                      }                       
                      
                      return $newfile;
                    }
                    else return false;
                  }
                  else return false;       
                }               
              }
            }      
          }
        }    
      }
      
      // --------------- if media conversion software is given ----------------
      if (is_array ($mgmt_mediapreview) && sizeof ($mgmt_mediapreview) > 0)
      {
        // convert the media file with FFMPEG
        // Audio Options:
        // -ac ... number of audio channels
        // -an ... disable audio
        // -ar ... audio sampling frequency (default = 44100 Hz)
        // -b:a ... audio bitrate (default = 64k)
        // -c:a ... audio codec (e.g. libmp3lame, libfaac, libvorbis)
        // Video Options:
        // -b:v ... video bitrate in bit/s (default = 200 kb/s)
        // -c:v ... video codec (e.g. libx264)
        // -cmp ... full pel motion estimation compare function (used for mp4)
        // -f ... force file format (like flv, mp4, ogv, webm, mp3)
        // -flags ... specific options for video encoding
        // -mbd ... macroblock decision algorithm (high quality mode)
        // -r ... frame rate in Hz (default = 25)
        // -s:v ... frame size in pixel (w x h) 
        
        // define default option for support of versions before 5.3.4
        // note: -acodec could be "mp3" or in newer ffmpeg versions "libmp3lame"!
        if ($mgmt_mediaoptions['.flv'] == "") $mgmt_mediaoptions['.flv'] = "-b:v 768k -s 320x240 -f flv -c:a libmp3lame -b:a 64k -ac 2 -ar 22050 -title \"".$file_name."\"";
        
        // reset type to input value
        $type = $type_memory;
        
        // define format if not set
        if ($format == "")
        {
          if (substr_count ($hcms_ext['audio'].".", $file_ext.".") > 0) $format_set = "mp3";
          else $format_set = "flv";
        }
        else $format_set = $format;
          
  		  // original video info
        $videoinfo = getvideoinfo ($location_source.$file);
              
        reset ($mgmt_mediapreview);
        
        // supported extensions for media rendering
        foreach ($mgmt_mediapreview as $mediapreview_ext => $mediapreview)
        {        
          // check file extension
          if ($file_ext != "" && substr_count ($mediapreview_ext.".", $file_ext.".") > 0)
          {
            reset ($mgmt_mediaoptions);  
            
            // extensions for certain media rendering options
            foreach ($mgmt_mediaoptions as $mediaoptions_ext => $mediaoptions)
            {
              // get media rendering options based on given destination format
              if (substr_count ($mediaoptions_ext.".", ".".$format_set.".") > 0)
              {        
                // media format (media file extension) definition
                if (strpos ("_".$mgmt_mediaoptions[$mediaoptions_ext], "-f ") > 0)
                {
                  $mediaformat = strtolower (getoption ($mgmt_mediaoptions[$mediaoptions_ext], "-f"));
                }
                
                if ($mediaformat == "" || $mediaformat == false) $mediaformat = "flv"; 
                
                // keep video ratio for original thumbnail video
                if ($type == "origthumb" && $videoinfo['ratio'] != "" && strpos ($mgmt_mediaoptions[$mediaoptions_ext], "-s:v ") > 0)
                {
                  $mediasize = getoption ($mgmt_mediaoptions[$mediaoptions_ext], "-s:v");
                  list ($mediawidth, $mediaheight) = explode ("x", $mediasize);
                  
                  if ($videoinfo['ratio'] > 1) $mediasize_new = intval($mediawidth)."x".round((intval($mediawidth)/$videoinfo['ratio']), 0);
                  else $mediasize_new = round((intval($mediaheight)*$videoinfo['ratio']), 0)."x".intval($mediaheight);
                  
                  $mgmt_mediaoptions[$mediaoptions_ext] = str_replace ("-s:v ".$mediasize, "-s:v ".$mediasize_new, $mgmt_mediaoptions[$mediaoptions_ext]);
                }
                
                // new file name
                if ($type == "thumbnail") $newfile = $file_name.".thumb.".$format_set;
                elseif ($type == "origthumb") $newfile = $file_name.".orig.".$format_set;
                else $newfile = $file_name.".media.".$format_set;
              
                $tmpfile = $file_name.".thumb.tmp";
              
                $cmd = $mgmt_mediapreview[$mediapreview_ext]." -i \"".escapeshellcmd ($location_source.$file)."\" ".$mgmt_mediaoptions[$mediaoptions_ext]." \"".escapeshellcmd ($location_dest.$tmpfile)."\"";
                
                @exec ($cmd, $buffer, $errorCode);
                
                // on error or new file is smaller than 500 bytes
                if ($errorCode || filesize ($location_dest.$tmpfile) < 500)
                {
                  @unlink ($location_dest.$tmpfile);
                
                  $errcode = "20236";
                  $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of ffmpeg (code:$errorCode) failed in createmedia for file: ".$location_source.$file;
                  
                  // save log
                  savelog (@$error);
                  
                  return false;               
                } 
                else
                {  
                  if (@filesize ($location_dest.$tmpfile) > 10)
                  {
                    // inject metadata into FLV file using YAMDI (/usr/bin/yamdi)
                    if ($mgmt_mediametadata['.flv'] != "" && $format_set == "flv")
                    {
                      $tmpfile2 = $file_name.".thumb.tmp2";
                      $cmd = $mgmt_mediametadata['.flv']." -i \"".escapeshellcmd ($location_dest.$tmpfile)."\" -o \"".escapeshellcmd ($location_dest.$tmpfile2)."\"";
                	    
                      @exec ($cmd, $buffer, $errorCode);
                      
                      @unlink ($location_dest.$tmpfile);
                      
                      if ($errorCode)
                      {
                        $errcode = "20237";
                        $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of yamdi (code:$errorCode) failed in createmedia for file: ".$location_source.$newfile;
                      }
                      else
                      {                  
                        if (@filesize ($location_dest.$tmpfile2) > 10) 
                        {
                          if (@is_file ($location_dest.$newfile)) @unlink ($location_dest.$newfile);
                          @rename ($location_dest.$tmpfile2, $location_dest.$newfile);
                        }
                        else 
                        {
                          @unlink ($location_dest.$tmpfile2);
                        }
                      }
                    }
                    else
                    {
                      if (@is_file ($location_dest.$newfile)) @unlink ($location_dest.$newfile);
                      @rename ($location_dest.$tmpfile, $location_dest.$newfile);
                    }
                    
                    // video width and height
                    if (strpos ($mgmt_mediaoptions[$mediaoptions_ext], "-s:v ") > 0)
                    {
                      $mediasize = getoption ($mgmt_mediaoptions[$mediaoptions_ext], "-s:v");
                      list ($mediawidth, $mediaheight) = explode ("x", $mediasize);
                    }
                    else
                    {
                      $mediawidth = "";
                      $mediaheight = "";
                    }

                    // generate video player config code for all video formates (thumbnails)
                    if ($type == "thumbnail")
                    {
                      // video thumbnail files
                      $video_extension_array = explode (".", substr ($hcms_ext['video'], 1));
                      
                      if (is_array ($video_extension_array))
                      {
                        // generate video file links for all available formats
                        $videos = array();
  
                        foreach ($video_extension_array as $video_extension)
                        {
                				  if ($video_extension != "" && @is_file ($location_dest.$file_name.".thumb.".$video_extension))
                          {
                            // thumbnail video
                            $videos[$video_extension] = $site."/".$file_name.".thumb.".$video_extension;
                          }
                        }
                      }
                      
                      // define config extension (audio or video)
                      if (substr_count ($hcms_ext['audio'].'.', $file_ext.'.') > 0) $config_extension = ".config.audio";
                      else $config_extension = ".config.video";
                    }
                    // generate video player config code for indivdual video
                    else
                    {
                      // generate video file links for individual generated video formats
                      $videos = array();
              				$videos[$format_set] = $site."/".$newfile;
                      
                      if ($type == "origthumb") $config_extension = ".config.orig";
                      else $config_extension = ".config.".$format_set;
                    }
                    
                    // capture screen from video to use as thumbnail image
                    if ($type == "origthumb") createthumbnail_video ($site, $location_dest, $location_dest, $newfile, "00:00:01");  
                                    
                    // save config
                    savemediaplayer_config ($location_dest, $file_name.$config_extension, $videos, $mediawidth, $mediaheight);
                    // save log
                    savelog (@$error);
                    
                    // get video information
                    if ($newfile != false)
                    {
                      $id = getmediacontainerid ($newfile);
                      
                      if (is_array ($videoinfo))
                      {
                        $mediawidth_orig = $videoinfo['width'];
                        $mediaheight_orig = $videoinfo['height'];
                        $imagetype_orig = $videoinfo['imagetype'];
                      }
                      else 
                      {
                        $mediawidth_orig = "";
                        $mediaheight_orig = "";
                        $imagetype_orig = "";
                      }
                      
                      // write media information to DB
                      if ($id != "") rdbms_setmedia ($id, $filesize_orig, $filetype_orig, $mediawidth_orig, $mediaheight_orig, "", "", "", "", $imagetype_orig, $md5_hash);
                    }                             
                    
                    // remote client
                    remoteclient ("save", "abs_path_media", $site, $location_dest, "", $newfile, "");                           
                          
                    return $newfile;
                  }
                  else
                  {
                    // delete media file
                    @unlink ($location_dest.$newfile);
                    
                    // remote client
                    remoteclient ("delete", "abs_path_media", $site, $location_dest, "", $newfile, "");
                    
                    return false;
                  }
                }
              }
            }     
          }
        }
      }
    }
    // source file does not exist
    else return false;
          
    // no option was found for given format or no media conversion software defined
    $id = getmediacontainerid ($file);
    if ($id != "") rdbms_setmedia ($id, $filesize_orig, $filetype_orig, $imagewidth_orig, $imageheight_orig, "", "", "", "", "", $md5_hash);
    
    return false;    
  }
  else return false;
}

// ---------------------- convertimage -----------------------------
// function: convertimage()
// input: publication, path to source image file, path to destination dir, format (file extension w/o dot) of destination file (optional), 
//        colorspace of new image [CMY, CMYK, Gray, HCL, HCLp, HSB, HSI, HSL, HSV, HWB, Lab, LCHab, LCHuv, LMS, Log, Luv, OHTA, Rec601YCbCr, Rec709YCbCr, RGB, scRGB, sRGB, Transparent, XYZ, YCbCr, YCC, YDbDr, YIQ, YPbPr, YUV] (optional), 
//        width in pixel (optional), height in pixel (optional)
// output: new file name / false on error

// description: converts and creates an new image from original. this is wrapper function for createmedia.

function convertimage ($site, $file_source, $location_dest, $format="jpg", $colorspace="CMYK", $iccprofile="",  $width="", $height="")
{
  global $mgmt_config, $mgmt_imagepreview, $mgmt_mediapreview, $mgmt_mediaoptions, $mgmt_imageoptions, $mgmt_maxsizepreview, $mgmt_mediametadata;
  
	$file = getobject ($file_source);
  $location_source = getlocation ($file_source);

  if (valid_publicationname ($site) && valid_locationname ($location_source) && valid_locationname ($location_dest) && ($colorspace != "" || $iccprofile != ""))
  {
    //get icc profile list
		include ($mgmt_config['abs_path_cms']."library/ICC_Profiles/ICC_Profiles.php");
		
		include ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
    
    // convert to abs. path of source media file
    if (strpos ($file_source, "_hcm") > 0)
    {
      $file_source = getmedialocation ($site, $file_source, "abs_path_media").$site."/".$file;
      if (!is_file ($file_source)) return false;
    }
    
    // add slash if not present at the end of the location string
    if (substr ($location_dest, -1) != "/") $location_dest = $location_dest."/";  
    
    // get file info
    $file_info = getfileinfo ($site, $file_source, "comp");
    
    // new image dimensions 
    if ($width > 0 && $height > 0)
    {
      $size = $width.'x'.$height;
      $size_para = " -s ".$size;
    }
    else
    {
      $size = "orig";
      $size_para = "";
    }
    
    // iccprofile or color space
    if ($iccprofile != "")
		{
			$color_para = " -p \"".$mgmt_config['abs_path_cms']."library/ICC_Profiles/".$ICC_Profiles[$iccprofile]."\"";
			$color = $iccprofile;
		}
		elseif ($colorspace != "")
    {
      $color_para = " -cs ".$colorspace;
			$color = $colorspace;
    }
    else
    {
      $color_para = "";
			$color = "orig";
    }
    
    // format
    if ($format != "")
    {
      $format_para = " -f ".$format;
    }
    else
    {
      // get the file extension
      $format = substr ($file_info['ext'], 1);
      $format_para = " -f ".$format;
    }
    
    // define type
    $type = $size."-".$color;

    // define image option
    $mgmt_imageoptions = array();
    $mgmt_imageoptions['.gif.jpg.jpeg.png'][$type] = $size_para.$color_para.$format_para;
 
    // new file name
    $file_name = $file_info['filename'].".".$type.".".$format;

    if (!is_file ($location_dest.$file_name) || @filemtime ($file_source) > @filemtime ($location_dest.$file_name))
    {
      return createmedia ($site, $location_source, $location_dest, $file, $format, $type);
    }
    else return $file_name;
  }
  else return false;
}

// ---------------------- rotateimage -----------------------------
// function: rotateimage()
// input: publication, path to source location, media file name, rotation angle, destination image format [jpg,png,gif]
// output: new image file name / false on error

// description: rotates an image (must be jpg, png or gif) using GD library. not used if ImageMagick is available.

function rotateimage ($site, $file, $angle, $imageformat)
{
  global $mgmt_config;
  
  if (valid_publicationname ($site) && valid_objectname ($file) && $angle < 360 && ($imageformat == "jpg" || $imageformat == "png" || $imageformat == "gif"))
  {  
    $media_root = getmedialocation ($site, $file, "abs_path_media");
    $file_info = getfileinfo ($site, $file, "comp");
    $id = getmediacontainerid ($file);
    
    if (@is_file ($media_root.$site."/".$file))
    {
      // create image from file
      if ($file_info['ext'] == ".jpg") $image = imagecreatefromjpeg ($media_root.$site."/".$file);
      elseif ($file_info['ext'] == ".png") $image = imagecreatefrompng ($media_root.$site."/".$file);
      elseif ($file_info['ext'] == ".gif") $image = imagecreatefromgif ($media_root.$site."/".$file);

      // if image resource 
      if ($image != false)
      {
    	  if ($angle == 270) $angle = -90;
    
        $src_x = imagesx ($image);
        $src_y = imagesy ($image);
        
        if ($angle == 90 || $angle == -90)
        {
            $dest_x = $src_y;
            $dest_y = $src_x;
        }
        else
        {
            $dest_x = $src_x;
            $dest_y = $src_y;
        }
    
        // create new image
        $rotate = imagecreatetruecolor ($dest_x, $dest_y);
        imagealphablending ($rotate, false);
    
        switch ($angle)
        {
          case 90:
            for ($y = 0; $y < ($src_y); $y++)
            {
              for ($x = 0; $x < ($src_x); $x++)
              {
                $color = imagecolorat ($image, $x, $y);
                imagesetpixel ($rotate, $dest_x - $y - 1, $x, $color);
              }
            }
            break;
          case -90:
            for ($y = 0; $y < ($src_y); $y++)
            {
              for ($x = 0; $x < ($src_x); $x++)
              {
                $color = imagecolorat ($image, $x, $y);
                imagesetpixel ($rotate, $y, $dest_y - $x - 1, $color);
              }
            }
            break;
          case 180:
            for ($y = 0; $y < ($src_y); $y++)
            {
              for ($x = 0; $x < ($src_x); $x++)
              { 
                $color = imagecolorat ($image, $x, $y); 
                imagesetpixel ($rotate, $dest_x - $x - 1, $dest_y - $y - 1, $color);
              }
            }
            break;
          default: $rotate = $image;
        };
        
        // save image
        if ($imageformat == "jpg" && function_exists ("imagejpeg"))
        {
          $result = @imagejpeg ($rotate, $media_root.$site."/".$file_info['filename'].".".$imageformat);
          
          // delete original file if file extension has changed
          if ($result == true && ".".$imageformat != $file_info['ext']) @unlink ($media_root.$site."/".$file);
        }
        elseif ($imageformat == "png" && function_exists ("imagepng"))
        {
          $result = @imagepng ($rotate, $media_root.$site."/".$file_info['filename'].".".$imageformat);
          
          // delete original file if file extension has changed
          if ($result == true && ".".$imageformat != $file_info['ext']) @unlink ($media_root.$site."/".$file);      
        }
        elseif ($imageformat == "gif" && function_exists ("imagegif"))
        {
          $result = @imagegif ($rotate, $media_root.$site."/".$file_info['filename'].".".$imageformat);
          
          // delete original file if file extension has changed
          if ($result == true && ".".$imageformat != $file_info['ext']) @unlink ($media_root.$site."/".$file);      
        }
        else $result = false;      
        
        // return result
        if ($result == true) return $file_info['filename'].".".$imageformat;
        else false;
      }
      // image resource error
      else return false;
    }
    // file does not exist
    else return false;
  }
  else return false;
}

// ---------------------- getimagecolors -----------------------------
// function: getimagecolors()
// input: publication, path to location, media file name, angle in grade
// output: result array / false on error

// description: calculate the mean color (red, green, blue), defines the colorkey (5 most commonly used colors) and the image type (landscape, portrait, square)

function getimagecolors ($site, $file)
{
  global $mgmt_config;
  
  if ($mgmt_config['db_connect_rdbms'] != "" && valid_publicationname ($site) && valid_objectname ($file))
  {  
    $media_root = getmedialocation ($site, $file, "abs_path_media");
    $file_info = getfileinfo ($site, $file, "comp");
    
    // try thumbnail image first
    if (@is_file ($media_root.$site."/".$file_info['filename'].".thumb.jpg"))
    {
      $image = imagecreatefromjpeg ($media_root.$site."/".$file_info['filename'].".thumb.jpg");
    }
    // try original image
    elseif (@is_file ($media_root.$site."/".$file))
    {
      if ($file_info['ext'] == ".jpg") $image = imagecreatefromjpeg ($media_root.$site."/".$file);
      elseif ($file_info['ext'] == ".png") $image = imagecreatefrompng ($media_root.$site."/".$file);
      elseif ($file_info['ext'] == ".gif") $image = imagecreatefromgif ($media_root.$site."/".$file);
      else $image = false;
    }

    if (is_resource ($image))
    {
    	$width = imagesx ($image);
      $height = imagesy ($image);
    	$totalred = 0;
    	$totalgreen = 0;
    	$totalblue = 0;
    	$total = 0;
    	
    	for ($y=0; $y<20; $y++)
    	{
    		for ($x=0; $x<20; $x++)
    	  {
    			$rgb = imagecolorat ($image, $x*($width/20), $y*($height/20));
    			$red = ($rgb >> 16) & 0xFF;
    			$green = ($rgb >> 8) & 0xFF;
    			$blue = $rgb & 0xFF;
    
    			// calculate deltas (remove brightness factor)
    			$cmax = max ($red, $green, $blue);
    			$cmin = min ($red, $green, $blue);
          // avoid division errors
          if ($cmax == $cmin)
          {
            $cmax = 10;
            $cmin = 0;
          } 
          
          // ignore gray, white and black
    			if (abs ($cmax - $cmin) >= 20) 
    			{
    				$red = floor ((($red - $cmin) /($cmax - $cmin)) * 255);
    				$green = floor ((($green - $cmin) / ($cmax - $cmin)) * 255);
    				$blue = floor ((($blue - $cmin) / ($cmax - $cmin)) * 255);
    
    				$total++;
    				$totalred += $red;
    				$totalgreen += $green;
    				$totalblue += $blue;
    			}
    		}
    	}
      
    	if ($total == 0) $total = 1;
    	$totalred = floor ($totalred / $total);
    	$totalgreen = floor ($totalgreen / $total);
    	$totalblue = floor ($totalblue / $total);
    	
    	$colorkey = getimagecolorkey ($image);
    
    	// set 'portrait', 'landscape' or 'square' for the image type
    	if ($width > $height) $imagetype = "landscape";
    	elseif ($height > $width) $imagetype = "portrait";
    	elseif ($height == $width) $imagetype = "square";
      
      // destroy image resource
      if (is_resource ($image)) imagedestroy ($image);
      
      $result = array();
      $result['red'] = $totalred;
      $result['green'] = $totalgreen;
      $result['blue'] = $totalblue;
      $result['colorkey'] = $colorkey;
      $result['imagetype'] = $imagetype;
      
      return $result;
    }
    else return false;
  }
  else return false;
}

// ---------------------- getimagecolorkey -----------------------------
// function: getimagecolorkey()
// input: image resource
// output: color key of image / false on error

// description: extracts the color key for an image that represents the 5 mostly used colors.
// K...black
// W...white
// E...grey
// R...red
// G...green
// B...blue
// C...cyan
// M...magenta
// Y...yellow
// O...orange
// P...pink
// N...brown

function getimagecolorkey ($image)
{
  global $mgmt_config;
  
  if ($image)
  {
  	$width = imagesx ($image);
    $height = imagesy ($image);
    
  	$colors = array (
  	"K"=>array(0,0,0), 			// Black
  	"W"=>array(255,255,255),	// White
  	"E"=>array(200,200,200),	// Grey
  	"E"=>array(140,140,140),	// Grey
  	"E"=>array(100,100,100),	// Grey
  	"R"=>array(255,0,0),		// Red
  	"R"=>array(128,0,0),		// Dark Red
  	"R"=>array(180,0,40),		// Dark Red
  	"G"=>array(0,255,0),		// Green
  	"G"=>array(0,128,0),		// Dark Green
  	"G"=>array(80,120,90),		// Faded Green
  	"G"=>array(140,170,90),		// Pale Green
  	"B"=>array(0,0,255),		// Blue
  	"B"=>array(0,0,128),		// Dark Blue
  	"B"=>array(90,90,120),		// Dark Blue
  	"B"=>array(60,60,90),		// Dark Blue
  	"B"=>array(90,140,180),		// Light Blue
  	"C"=>array(0,255,255),		// Cyan
  	"C"=>array(0,200,200),		// Cyan
  	"M"=>array(255,0,255),		// Magenta
  	"Y"=>array(255,255,0),		// Yellow
  	"Y"=>array(180,160,40),		// Yellow
  	"Y"=>array(210,190,60),		// Yellow
  	"O"=>array(255,128,0),		// Orange
  	"O"=>array(200,100,60),		// Orange
  	"P"=>array(255,128,128),	// Pink
  	"P"=>array(200,180,170),	// Pink
  	"P"=>array(200,160,130),	// Pink
  	"P"=>array(190,120,110),	// Pink
  	"N"=>array(110,70,50),		// Brown
  	"N"=>array(180,160,130),	// Pale Brown
  	"N"=>array(170,140,110),	// Pale Brown
  	);
    
  	$table = array();
  	$depth = 50;
    
  	for ($y=0; $y<$depth; $y++)
  	{
  		for ($x=0; $x<$depth; $x++)
  		{
  			$rgb = imagecolorat ($image, $x*($width/$depth), $y*($height/$depth));
  			$red = ($rgb >> 16) & 0xFF;
  			$green = ($rgb >> 8) & 0xFF;
  			$blue = $rgb & 0xFF;
  			// which color
  			$bestdist = 99999;
        $bestkey = "";
        
  			reset ($colors);
        
  			foreach ($colors as $key=>$value)
  			{
  				$distance = sqrt (pow (abs ($red - $value[0]), 2) + pow (abs ($green - $value[1]), 2) + pow (abs ($blue - $value[2]), 2));
          
  				if ($distance < $bestdist)
          {
            $bestdist = $distance;
            $bestkey = $key;
          }
  			}
        
  			// add this color to the color table
  			if (array_key_exists ($bestkey, $table)) $table[$bestkey]++;
        else $table[$bestkey] = 1;
  		}
  	}
    
  	asort ($table);
    reset ($table);
    $colorkey = "";
  	foreach ($table as $key=>$value) $colorkey .= $key;
    
    // color key with the 5 mostyl used colors in the image
  	$colorkey = substr (strrev ($colorkey), 0, 5);
    
  	return $colorkey;
  }
  else return false;
}

// ---------------------- hex2rgb -----------------------------
// function: hex2rgb()
// input: image color as hex-code
// output: RGB-color as array / false on error

function hex2rgb ($hex)
{
  if ($hex != "")
  {
    $hex = ereg_replace ("#", "", $hex);
    $color = array();
     
    if (strlen($hex) == 3)
    {
      $color['red'] = hexdec (substr ($hex, 0, 1) . $r);
      $color['green'] = hexdec (substr ($hex, 1, 1) . $g);
      $color['blue'] = hexdec (substr ($hex, 2, 1) . $b);
    }
    elseif(strlen($hex) == 6)
    {
      $color['red'] = hexdec(substr($hex, 0, 2));
      $color['green'] = hexdec(substr($hex, 2, 2));
      $color['blue'] = hexdec(substr($hex, 4, 2));
    }
     
    return $color;
  }
  else return false;
}

// ---------------------- rgb2hex -----------------------------
// function: rgb2hex()
// input: image color in RGB
// output: hex-color as string / false on error

function rgb2hex ($red, $green, $blue)
{
  if ($red >= 0 && $green >= 0 && $blue >= 0)
  {
    $hex = "#";
    $hex.= str_pad (dechex ($red), 2, "0", STR_PAD_LEFT);
    $hex.= str_pad (dechex ($green), 2, "0", STR_PAD_LEFT);
    $hex.= str_pad (dechex ($blue), 2, "0", STR_PAD_LEFT);
     
    return $hex;
  }
  else return false;
}

// ====================================== VIDEO PLAYER =========================================

// ------------------------- readvideoplayer_config -----------------------------
// function: readvideoplayer_config()
// input: path to media config file, config file name 
// output: config array / false on error

function readmediaplayer_config ($location, $configfile)
{ 
  global $mgmt_config;
  
  if (valid_locationname ($location) && valid_objectname ($configfile) && is_file ($location.$configfile))
  {
    // load config
    $configstring = loadfile ($location, $configfile);
    
    // Check which configuration is used
    $config = array();
    $media_array = array();
    $update = false;
    
    $test = explode ("\n", $configstring);
    
    // V2.0+ video player parameters in config
    if (substr ($test[0], 0, 1) == "V" && intval (substr ($test[0], 1, 1)) >= 2)
    {
      // new since version 5.5.13
      foreach ($test as $key => $value)
      {
        // version
        if ($key == 0) $config['version'] = substr ($value, 1);
        // width
        elseif ($key == 1) $config['width'] = $value;
        // height
        elseif ($key == 2) $config['height'] = $value;
        // video sources (V2.1: video-file;mime-type)
        elseif (strpos ($value, ";") > 0) $media_array[] = $value;
        // video sources (V2.0: video-file in wrapper-URL)
        elseif ($value != "" && strpos ($value, "?media=") > 0)
        {
          $media = getattribute ($value, "media");
          
          if ($media != "")
          {
            $type = ";".getmimetype ($media);
            $media_array[] = $media.$type;
            $update = true;
          }
        }
        // video sources (with missing mime-type)
        elseif ($value != "")
        {
          $type = ";".getmimetype ($value);
          $media_array[] = $value.$type;
          $update = true;
        }
      }
      
      $config['mediafiles'] = $media_array;
    }
    // V0.0/V1.0 older versions with video player code in config
    elseif (substr_count ($configstring, '<') > 0)
    {
      // V1.0 projekktor video player code in config
      if (substr_count ($configstring, '<span id="hcms_div_projekktor_') > 0) $config['version'] = '1.0';
      // old video player code in config
      else $config['version'] = '0.0';
      
      $config['width'] = getattribute ($configstring, "width");
      $config['height'] = getattribute ($configstring, "height"); 
      $config['data'] = $configstring;
      $media_array = array();

      if ($config['data'] != "")
      {
        $offset = 0;
        
        while (strpos ($config['data'], "?media=", $offset) > 0)
        {
          $start = strpos ($config['data'], "?media=", $offset);
          $stop = strpos ($config['data'], "\"", $start);
          $length = $stop - $start;
          $offset = $stop;
          
          if ($length > 0)
          {
            $uri = ".php".substr ($config['data'], $start, $length);                  
            $media = getattribute ($uri, "media");
            
            if ($media != "") $type = ";".getmimetype ($media);
            else $type = "";
            
            $media_array[] = $media.$type;
          }
        }
        
        $config['mediafiles'] = $media_array;
        $update = true;
      }
    }
    
    // update video config file
    if ($update && sizeof ($media_array) > 0) savemediaplayer_config ($location, $configfile, $media_array, $config['width'], $config['height']); 
    
    return $config;
  }
  else return false;
}

// ------------------------- savevideoplayer_config -----------------------------
// function: savevideoplayer_config()
// input: path to media config file, media config file name, media file name array or string, width in px (optional), height in px (optional)
// output: true / false on error

function savemediaplayer_config ($location, $configfile, $mediafiles, $width=320, $height=240)
{ 
  global $mgmt_config;
  
  if (valid_locationname ($location) && valid_objectname ($configfile) && (is_array ($mediafiles) || $mediafiles != ""))
  {
    $config = array();
    $config[0] = "V2.1";
    $config[1] = $width;
    $config[2] = $height;
    
    // array
    if (is_array ($mediafiles)) 
    {
      $i = 3;
      
      foreach ($mediafiles as $media)
      {
        if ($media != "")
        {
          // if mime-type is not supplied (standard case) 
          if (strpos ($media, ";") < 1)
          {
            $mimetype = ";".getmimetype ($media);
            $config[$i] = $media.$mimetype;
          }
          // dont add mime-type
          else $config[$i] = $media;

          
          $i++;
        }
      }
    }
    // string
    else
    {
      // if mime-type is not supplied (standard case) 
      if (strpos ($mediafiles, ";") < 1)
      {
        $mimetype = ";".getmimetype ($mediafiles);
        $config[3] = $mediafiles.$mimetype;
      }
      // dont add mime-type
      else $config[3] = $mediafiles;
    }
    
    return savefile ($location, $configfile, implode ("\n", $config));
  }
  else return false;  
}

// ========================================== DOCUMENT CREATION =======================================

// ---------------------- createdocument -----------------------------
// function: createdocument()
// input: publication, path to source location, path to destination location, file name, destination file format (extension w/o dot)
// output: new file name / false on error

// description: creates a new multimedia file of given format at source destination using UNOCONV and saves it as thumbnail file at the desitnation location

function createdocument ($site, $location_source, $location_dest, $file, $format="")
{
  global $mgmt_config, $mgmt_docpreview, $mgmt_docoptions, $mgmt_docconvert;
  
  if (valid_publicationname ($site) && valid_locationname ($location_source) && valid_locationname ($location_dest) && valid_objectname ($file))
  {
    // add slash if not present at the end of the location string
    if (substr ($location_source, -1) != "/") $location_source = $location_source."/";
    if (substr ($location_dest, -1) != "/") $location_dest = $location_dest."/";      
    // get file name without extension
    $file_name = strrev (substr (strstr (strrev ($file), "."), 1));
    // get the file extension
    $file_ext = strtolower (strrchr ($file, "."));
    // define format if not set
    if ($format == "") $format = "pdf";
    
    // if media conversion software is given, conversion supported and destination format is not the source format
    if (is_array ($mgmt_docpreview) && is_array ($mgmt_docconvert[$file_ext]) && sizeof ($mgmt_docpreview) > 0 && $format != str_replace (".", "", $file_ext) && in_array (".".$format, $mgmt_docconvert[$file_ext]) && @is_file ($location_source.$file))
    {
      // convert the media file with UNOCONV
      // unoconv is a command line utility that can convert any file format that OpenOffice can import, to any file format that OpenOffice is capable of exporting.
      // -d, --doctype ... Specify the OpenOffice document type of the backend format. Possible document types are: document, graphics, presentation, spreadsheet. Default document type is 'document'.
      // -e, --export ... Set specific export filter options (related to the used OpenOffice filter). eg. for the PDF output filter one can specify: -e PageRange=1-2
      // -f, --format ... Specify the output format for the document. You can get a list of possible output formats per document type by using the --show option. Default document type is 'pdf'.
      // -i, --import ... Set specific import filters options (related to the used OpenOffice filter). eg. for some input filters one can specify: -i utf8
      
      reset ($mgmt_docpreview);
      
      // supported extensions for document rendering
      foreach ($mgmt_docpreview as $docpreview_ext => $docpreview)
      { 
        // check file extension
        if ($file_ext != "" && substr_count ($docpreview_ext.".", $file_ext.".") > 0)
        {
          reset ($mgmt_docoptions);  
          
          // extensions for certain document rendering options
          foreach ($mgmt_docoptions as $docoptions_ext => $docoptions)
          { 
            // get media rendering options based on given destination format
            if (substr_count ($docoptions_ext.".", ".".$format.".") > 0)
            {
              // document format (document file extension) definition
              $docformat = strtolower (getoption ($mgmt_docoptions[$docoptions_ext], "-f"));
              
              if ($docformat == "" || $docformat == false) $docformat = strtolower (substr ($buffer, 0));
              if ($docformat == "" || $docformat == false) $docformat = "pdf";   
              
              // create new file
              $newfile = $file_name.".thumb.".$docformat;  
              
              // if thumbnail file exists in destination (temp) folder
              if (@is_file ($location_dest.$newfile))
              {
                // delete existing destination file if it is older than the source file
                if (@filemtime ($location_dest.$newfile) < @filemtime ($location_source.$file)) 
                {
                  @unlink ($location_dest.$newfile);
                }
                // or we return the filename
                else
                {                  
                  return $newfile;
                }
              }

              // if thumbnail file exits in source folder 
              if (@is_file ($location_source.$newfile))
              {
                // if existing thumbnail file is newer than the source file
                if (@filemtime ($location_source.$newfile) >= @filemtime ($location_source.$file)) 
                {
                  // copy to destination directory
                  $result_rename = @copy ($location_source.$newfile, $location_dest.$newfile);
                  if ($result_rename == true) return $newfile;
                }
              }
                
              $cmd = $mgmt_docpreview[$docpreview_ext]." ".$mgmt_docoptions[$docoptions_ext]." \"".escapeshellcmd ($location_source.$file)."\"";              
              @exec ($cmd." 2>&1", $buffer, $errorCode);
          
              // on error of new file is smalle than 500 bytes
              if ($errorCode || !is_file ($location_source.$file_name.".".$docformat))
              {
                $errcode = "20236";
                $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|exec of unoconv (code:$errorCode) to '$format' failed in createdocument for file: ".$location_source.$file." with message: ".implode(" ", $buffer);
                
                // save log
                savelog (@$error);
                
                return false;               
              } 
              else
              {  
                // rename converted
                $result_rename = @rename ($location_source.$file_name.".".$docformat, $location_dest.$newfile);

                if ($result_rename == false)
                {
                  $errcode = "20337";
                  $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|rename failed in createdocument for file: ".$location_source.$file_name.".".$docformat;
                  
                  // save log
                  savelog (@$error);
                  
                  return false;                 
                }
                else 
                {
                  // remote client
                  remoteclient ("save", "abs_path_media", $site, $location_dest, "", $newfile, "");           
                        
                  return $newfile;
                }
              }
            }
          }
        }
      }
    }
    
    // no option was found for given format or no media conversion software defined
    return false;    
  }
  else return false;
}

// ====================================== COMPRESSED FILE HANDLING ===================================

// ---------------------- unzipfile -----------------------------
// function: unzipfile()
// input: publication, path to source zip file, path to destination location, name of file for extraction, user
// output: true/false
// description: unzippes file and creates thumbnails of media files in destination location

function unzipfile ($site, $zipfilepath, $location, $filename, $user)
{
  global $mgmt_config, $mgmt_uncompress, $mgmt_imagepreview, $mgmt_mediapreview, $mgmt_mediaoptions;

  if ($mgmt_uncompress['.zip'] != "" && valid_publicationname ($site) && $zipfilepath != "" && valid_locationname ($location) && valid_objectname ($filename) && valid_objectname ($user))
  {
    // folder name for extraction
    $folder = substr ($filename, 0, strrpos ($filename, "."));  
    
    // extension of zip file
    $file_ext = strtolower (strrchr ($filename, "."));      
  
    // directory with name of the folder must not exist
    if (!is_dir ($location.$folder."/"))
    {
      // temporary directory for extracting files
      $temp_name = uniqid ("unzip");
      $temp_dir = $mgmt_config['abs_path_cms']."temp/".$temp_name."/";      

      reset ($mgmt_uncompress);
      
      for ($i = 1; $i <= sizeof ($mgmt_uncompress); $i++)
      {
        // supported extension
        $extension = key ($mgmt_uncompress);
        
        if (substr_count ($extension, $file_ext) > 0)
        {
          // create temporary directory for extraction
          $result = @mkdir ($temp_dir, $mgmt_config['fspermission']);

          // create destination folder for extraction
          if ($result == true)
          {
            $result = createfolder ($site, $location, $folder, $user);
          
            // remote client
            remoteclient ("save", "abs_path_comp", $site, $location, "", $folder, "");           
          }

          if ($result['result'] == true)
          {            
            $cmd = $mgmt_uncompress[$extension]." \"".escapeshellcmd ($zipfilepath)."\" -d \"".escapeshellcmd ($temp_dir)."\"";         
            
            @exec ($cmd, $error_array);

            if (is_array ($error_array))
            {
              $error_message = implode ($error_array, "<br />");
              
              if (substr_count ($error_message, "error") > 0)
              {
                $error_message = str_replace ($temp_dir, "/".$site."/", $error_message);
  
                $errcode = "10640";
                $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|unzipfile failed for $filename: $error_message";
                
                // save log
                savelog (@$error);  
              }                         
            }
            
            // extraction failed (check if files were extracted)
            $handle = opendir ($temp_dir);
            
            if ($handle != false)
            {    
              $check = 0;
              while ($buffer = @readdir ($handle)) $check++;
              closedir ($handle);              
              if ($check < 1) return false;
            }
            else return false;            
            
            // create media objects          
            $result = createmediaobjects ($site, $temp_dir, $location.$result['folder']."/", $user);
            deletefile ($mgmt_config['abs_path_cms']."temp/", $temp_name, 1);
            
            return $result;  
          }
        }
        
        next ($mgmt_uncompress);
      }
    }
    else return false;
  }
  else return false;
}    

// ---------------------- zipfiles -----------------------------
// function: zipfiles()
// input: publication, array with path to source zip files, destination location (if this is null then the $location where the zip-file resists will be used), 
//        name of ZIP-file, user name, activity that need to be set for daily stats [download] (optional)
// output: true/false

// help function that reads and copies all multimedia files from multimedia components to the structure based on the multimedia component names

function cloneFolder ($site, $source, $destination, $user, $activity="")
{
  global $mgmt_config, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $globalpermission, $setlocalpermission;
  
  if (is_array ($mgmt_config) && $source != "" && $destination != "")
  {  
    $destDir = $destination."/".specialchr_decode (getobject ($source));
    @mkdir ($destDir, $mgmt_config['fspermission'], true);
    
    if ($dir = opendir ($source))
    {
      while (($file = readdir ($dir)) !== false)
      {
        // check access permissions
        if (!is_array ($setlocalpermission) && $user != "sys")
        {
          $ownergroup = accesspermission ($site, $source, "comp");
          $setlocalpermission = setlocalpermission ($site, $ownergroup, "comp");
        }

        if (substr_compare ($file, ".", 0, 1) != 0 && ($user == "sys" || ($setlocalpermission['root'] == 1 && $setlocalpermission['download'] == 1)))
        {
          if (is_dir ($source."/".$file))
          {
            cloneFolder ($site, $source."/".$file, $destDir, $user, $activity);
          }
          else
          {  
            $objectdata = loadfile ($source."/", $file);
            
            if ($objectdata != false)
            {
              $mediafile = getfilename ($objectdata, "media");
              
              if ($mediafile != false)
              {
                $container_id = getmediacontainerid ($mediafile);
                $mediadir = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";
                $filePath = $mediadir.$mediafile;
                copy ($filePath, $destDir."/".specialchr_decode ($file));
                
                // write stats
                if ($container_id != "" && !is_thumbnail ($mediafile, false)) rdbms_insertdailystat ("download", $container_id, $user);
              }
            }        
          }
        }
      }
      
      closedir ($dir);
    }
    else return false;    
  }
  else return false;
}

function zipfiles ($site, $multiobject_array, $destination="", $zipfilename, $user, $activity="")
{
  global $mgmt_config, $mgmt_compress, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $globalpermission, $setlocalpermission;

  // valid_locationname ($destination) && valid_objectname ($zipfilename) && valid_objectname ($user)
  if (is_array ($mgmt_config) && $mgmt_compress['.zip'] != "" && valid_publicationname ($site) && is_array ($multiobject_array) && sizeof ($multiobject_array) > 0 && is_dir ($destination) && valid_locationname ($destination) && valid_objectname ($zipfilename) && valid_objectname ($user))
  {  
    // check max file size (set default value to 2000 MB)
    if (!isset ($mgmt_config['maxzipsize'])) $mgmt_config['maxzipsize'] = 2000;
    
    if ($mgmt_config['maxzipsize'] > 0 && $mgmt_config['db_connect_rdbms'] != "")
    {
      $filesize = 0;
      
      foreach ($multiobject_array as $multiobject)
      {
        $multiobject = convertpath ($site, $multiobject, "comp");
        // get file size in KB
        $filesize_array = rdbms_getfilesize ("", $multiobject);
        if (is_array ($filesize_array)) $filesize = $filesize + $filesize_array['filesize'];                
        // return false if max file size limit in MB is exceeded
        if (($filesize / 1024) > $mgmt_config['maxzipsize']) return false;
      }
    }  

    // temporary directory for file collection
    $tempDir = $mgmt_config['abs_path_cms']."temp/";
  
    $commonRoot = getlocation ($multiobject_array[0]);
  
    // find common root folder for different file paths
    if (sizeof ($multiobject_array) > 1)
    {
      for ($i=0; $i<sizeof($multiobject_array); $i++)
      {
        if ($multiobject_array[$i] != "")
        {
          $fileParts = explode ("/", $multiobject_array[$i]);
          $commonRootParts = explode ("/", $commonRoot);
    
          $commonRoot = "";
          $j = 0;
        
          while ($fileParts[$j] == $commonRootParts[$j] && $j < sizeof ($fileParts))
          {
            $commonRoot .= $fileParts[$j]."/";
            $j++;
          }
        }
      }
    }
   
    $commonRoot = deconvertpath ($commonRoot, "file");

    // create unique temp directory to collect the files for compression
    $tempFolderName = uniqid ("zip");
    $tempFolder = $tempDir.$tempFolderName;
    @mkdir ($tempFolder, $mgmt_config['fspermission'], true);
    
    // walk through objects and get the multimedia files reference
    for ($i=0; $i<sizeof($multiobject_array); $i++)
    {
      if ($multiobject_array[$i] != "")
      {    
        $filename = getobject ($multiobject_array[$i]);
        $location = getlocation ($multiobject_array[$i]);
        $location = deconvertpath ($location, "file");
        
        if (valid_locationname ($location) && valid_objectname ($filename))
        {
          $destinationFolder = str_replace ($commonRoot, "", $location);
          @mkdir ($tempFolder."/".$destinationFolder, $mgmt_config['fspermission'], true);
      
          if ($filename != ".folder" && @is_file ($location.$filename))
          {
            $objectdata = loadfile ($location, $filename);
        
            if ($objectdata != false)
            {
              $mediafile = getfilename ($objectdata, "media");
              
              if ($mediafile != false)
              {
                $mediadir = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";
                $filePath = $mediadir.$mediafile;
                copy ($filePath, $tempFolder."/".specialchr_decode ($destinationFolder.$filename));
              }
            }
          }
          elseif ($filename == ".folder" || is_dir ($location.$filename))
          {
            if ($filename == ".folder")
            {
              $filename = "";
              // cut off last /
              $location = substr ($location, 0, -1);
            }
            
            cloneFolder ($site, $location.$filename, $tempFolder."/".specialchr_decode ($destinationFolder), $user, $activity);
          }
        }
      }
    }
    
    // ZIP files
    // Windows
    if ($mgmt_config['os_cms'] == "WIN")
    { 
      $cmd = "cd \"".escapeshellcmd ($location)."\" & ".$mgmt_compress['.zip']." -r \"".escapeshellcmd ($destination.$zipfilename).".zip\" ".escapeshellcmd ($filesToZip);  
      $cmd = str_replace ("/", "\\", $cmd);
    }
    // UNIX
    else $cmd = "cd \"".escapeshellcmd ($tempFolder)."\" ; ".$mgmt_compress['.zip']." -r \"".escapeshellcmd ($destination.$zipfilename).".zip\" *";
    
    // compress files to ZIP format
    @exec ($cmd, $error_array);
  
    // remove temp files
    deletefile ($tempDir, $tempFolderName, 1);
    
    // errors during compressions of files
    if ($error_array != "")
    {
      $error_message = implode ($error_array, "<br />");
      
      if (substr_count ($error_message, "error") > 0)
      {
        $error_message = str_replace ($mgmt_config['abs_path_cms']."temp/", "/", $error_message);
  
        $errcode = "10645";
        $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|$errcode|zipfiles failed for $filename: $error_message";
        
        // save log
        savelog (@$error);
        
        return false;   
      } 
      else return true;                        
    }
    
    return true;
  }
  return false;
}

?>