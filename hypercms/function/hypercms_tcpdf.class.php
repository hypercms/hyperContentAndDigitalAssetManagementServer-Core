<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// include TCPDF library
if (is_file ($mgmt_config['abs_path_cms']."library/tcpdf/tcpdf.php"))
{
  require_once ($mgmt_config['abs_path_cms']."library/tcpdf/tcpdf.php");
}

class hcmsPDF extends TCPDF
{
  // ----------------------------------------- TCPDFdrawCropbox ---------------------------------------------
  // function: TCPDFdrawCropbox()
  // input: internal hcmsPDF/TCPDF object, slug, cropmark [true,false] (optional), crop-mark [true,false] (optional), registration-mark [true,false] (optional), color-registration-bar [true,false] (optional)
  // output: hcmsPDF/TCPDF object
  
  // description:
  // Enlarges the MediaBox by the slug of the document in all directions and draws cropmarks, registrations marks and color bars
  
  public function TCPDFdrawCropbox ($tcpdf, $slug=6, $cropmark=true, $registrationmark=true, $registrationbar=true)
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