<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */
 
// include TCPDF library
if (is_file ($mgmt_config['abs_path_cms']."library/tcpdf/tcpdf.php"))
{
  require_once ($mgmt_config['abs_path_cms']."library/tcpdf/tcpdf.php");
}

class hcmsPDF extends TCPDF
{
  // ----------------------------------------- placeImage ---------------------------------------------
  // function: placeImage()
  // input: internal hcmsPDF/TCPDF [object], x-ccordinate [integer] (optional), y-coordinate [integer] (optional), width (optional), height (optional), image/file type [string] (optional), URL or identifier returned by AddLink [string] (optional), 
  //        alignment of the pointer next to image insertion relative to image height [T,M,B,N] (optional), align the image on the current line [L,C,R] (optional), 
  //        resize (reduce) the image to fit $w and $h [true,false] (optional), DPI [integer] (optional), border [integer] (optional), specifies whether to position the bounding box (true) or the complete canvas (false) at location (x,y) [string] (optional),
  //        if true remove values outside the bounding box (optional), 
  //        scale image dimensions proportionally to fit within the ($w, $h) box. $fitbox can be true or a 2 characters string indicating the image alignment inside the box. The first character indicate the horizontal alignment (L = left, C = center, R = right) the second character indicate the vertical algnment (T = top, M = middle, B = bottom),
  //        if true the image is resized to not exceed page dimensions (optional)
  // output: hcmsPDF/TCPDF object
  
  // description:
  // Places an image in the document
  
  public function placeImage ($tcpdf, $file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $palign='', $resize=false, $dpi=300, $border=0, $useBoundingBox=true, $fixoutvals=true, $fitbox=false, $fitonpage=false)
  {
    if (is_file ($file))
    {
      // get file extension
      $file_ext = strtolower (strrchr ($file, "."));
      
      // place AI or EPS file
      if ($file_ext == ".ai" || $file_ext == ".eps")
      {
        $tcpdf->ImageEps ($file, $x, $y, $w, $h, $link, $useBoundingBox, $align, $palign, $border, $fitonpage, $fixoutvals);
      }
      // place SVG file
      elseif ($file_ext == ".svg")
      {
        $tcpdf->ImageSVG ($file, $x, $y, $w, $h, $link, $align, $palign, $border, $fitonpage);
      }
      // place standard image file
      else
      {
        $tcpdf->Image ($file, $x, $y, $w, $h, $type, $link, $align, $resize, $dpi, $palign, $ismask=false, $imgmask=false, $border, $fitbox, $hidden=false, $fitonpage, $alt=false, $altimgs=array());
      }
    }
  }
  
  // ----------------------------------------- drawCropbox ---------------------------------------------
  // function: drawCropbox()
  // input: internal hcmsPDF/TCPDF [object], slug [integer], cropmark [true,false] (optional), crop-mark [true,false] (optional), registration-mark [true,false] (optional), color-registration-bar [true,false] (optional)
  // output: hcmsPDF/TCPDF object
  
  // description:
  // Enlarges the MediaBox by the slug of the document in all directions and draws cropmarks, registrations marks and color bars
  
  public function drawCropbox ($tcpdf, $slug=6, $cropmark=true, $registrationmark=true, $registrationbar=true)
  {
    for ($i = 1; $i <= $tcpdf->getNumPages(); $i++)
    {
      $tcpdf->setPage($i);
      $width = $tcpdf->getPageWidth();
      $height = $tcpdf->getPageHeight();
      $outerWidth = $width + 2 * $slug;
      $outerHeight = $height + 2 * $slug;
      $barHeight = min($slug - 1, 6);
      $barWidth = min(9 * $barHeight, ($width - $barHeight * 4)/ 2);
      $barHeight = max(1, $barWidth / 9);
      $registrationHeight  = $barHeight / 3;
  
      $tcpdf->setPageFormat(
        array(
          $outerWidth,
          $outerHeight,
          'Rotate'   => 0,
          'MediaBox' => array(
            'llx' => -$slug, 'lly' => $height + $slug, 'urx' => $width + $slug, 'ury' => -$slug
          ),
        )
      );
  
      // Crop marks
      if ($cropmark == true)
      {
        // Crop left top
        $tcpdf->cropMark(
          $x = 0,
          $y = $outerWidth - $height,
          $w = $slug,
          $h = $slug,
          $type = 'A',
          $color = array(0, 0, 0)
        );
    
        // Crop right top
        $tcpdf->cropMark(
          $x = $width,
          $y = $outerWidth - $height,
          $w = $slug,
          $h = $slug,
          $type = 'B',
          $color = array(0, 0, 0)
        );
    
        // Crop left bottom
        $tcpdf->cropMark(
          $x = 0,
          $y = $outerWidth,
          $w = $slug,
          $h = $slug,
          $type = 'C',
          $color = array(0, 0, 0)
        );
    
        // Crop right bottom
        $tcpdf->cropMark(
          $x = $width,
          $y = $outerWidth,
          $w = $slug,
          $h = $slug,
          $type = 'D',
          $color = array(0, 0, 0)
        );
      }

      // Registration marks
      if ($registrationmark == true)
      {
        // Registration left
        $tcpdf->registrationMark(
          $x = -$slug / 2,
          $y = $width - $height / 2 + 2 * $slug,
          $registrationHeight,
          FALSE,
          array(0, 0, 0),
          array(255, 255, 255)
        );
    
        // Registration top
        $tcpdf->registrationMark(
          $x = $width / 2,
          $y = $outerWidth - $height - $slug / 2,
          $registrationHeight,
          FALSE,
          array(0, 0, 0),
          array(255, 255, 255)
        );
    
        // Registration right
        $tcpdf->registrationMark(
          $x = $width + $slug / 2,
          $y = $width - $height / 2 + 2 * $slug,
          $registrationHeight,
          FALSE,
          array(0, 0, 0),
          array(255, 255, 255)
        );
    
        // Registration bottom
        $tcpdf->registrationMark(
          $x = $width / 2,
          $y = $outerWidth + $slug / 2,
          $registrationHeight,
          FALSE,
          array(0, 0, 0),
          array(255, 255, 255)
        );
      }

      // Color Registration Bar
      if ($registrationbar == true)
      {
        // Color Registration Bar
        $tcpdf->colorRegistrationBar(
          $x = $width - $barWidth - $barHeight,
          $y = $outerWidth - $outerHeight + $slug,
          $w = $barWidth,
          $h = $barHeight,
          FALSE,
          TRUE,
          'A,W,R,G,B,C,M,Y,K'
        );
    
        // Gray Registration Bar
        $tcpdf->colorRegistrationBar(
          $x = $barHeight,
          $y = $outerWidth - $outerHeight + $slug,
          $w = $barWidth,
          $h = $barHeight,
          TRUE,
          FALSE,
          'A'
        );
      }
    }
  }
}
?>