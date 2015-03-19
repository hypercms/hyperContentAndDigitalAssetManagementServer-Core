<?php
/**
 * Enlarges the MediaBox by the slug of the document in all directions and draws cropmarks,
 * registrations marks and color bars
 *
 * @param $tcpdf the internal tcpdf object
 *
 * @access public
 */
 
function TCPDFdrawCropbox ($tcpdf, $slug = 6)
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
    $registrationHeight  = $barHeight / 2;

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

    //Crop left top
    $tcpdf->cropMark(
      $x = 0,
      $y = $outerWidth - $height,
      $w = $slug,
      $h = $slug,
      $type = 'A',
      $color = array(0, 0, 0)
    );

    //Crop right top
    $tcpdf->cropMark(
      $x = $width,
      $y = $outerWidth - $height,
      $w = $slug,
      $h = $slug,
      $type = 'B',
      $color = array(0, 0, 0)
    );

    //Crop left bottom
    $tcpdf->cropMark(
      $x = 0,
      $y = $outerWidth,
      $w = $slug,
      $h = $slug,
      $type = 'C',
      $color = array(0, 0, 0)
    );

    //Crop right bottom
    $tcpdf->cropMark(
      $x = $width,
      $y = $outerWidth,
      $w = $slug,
      $h = $slug,
      $type = 'D',
      $color = array(0, 0, 0)
    );

    //Registration left
    $tcpdf->registrationMark(
      $x = -$slug / 2,
      $y = $width - $height / 2 + 2 * $slug,
      $registrationHeight,
      FALSE,
      array(0, 0, 0),
      array(255, 255, 255)
    );

    //Registration top
    $tcpdf->registrationMark(
      $x = $width / 2,
      $y = $outerWidth - $height - $slug / 2,
      $registrationHeight,
      FALSE,
      array(0, 0, 0),
      array(255, 255, 255)
    );

    //Registration right
    $tcpdf->registrationMark(
      $x = $width + $slug / 2,
      $y = $width - $height / 2 + 2 * $slug,
      $registrationHeight,
      FALSE,
      array(0, 0, 0),
      array(255, 255, 255)
    );

    //Registration bottom
    $tcpdf->registrationMark(
      $x = $width / 2,
      $y = $outerWidth + $slug / 2,
      $registrationHeight,
      FALSE,
      array(0, 0, 0),
      array(255, 255, 255)
    );

    //Color Registration Bar
    $tcpdf->colorRegistrationBar(
      $x = $width - $barWidth - $barHeight,
      $y = $outerWidth - $outerHeight + $slug,
      $w = $barWidth,
      $h = $barHeight,
      FALSE,
      TRUE,
      'A,W,R,G,B,C,M,Y,K'
    );

    //Gray Registration Bar
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
  
  return $tcpdf;
}
?>