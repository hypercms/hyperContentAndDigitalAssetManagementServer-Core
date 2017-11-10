<?php
// available formats
$available_formats = array();

$available_formats['fs'] = array(
	'name'					 => $hcms_lang['standard-video-43'][$lang],
	'checked'				 => false
);

$available_formats['ws'] = array(
	'name'					 => $hcms_lang['widescreen-video-169'][$lang],
	'checked'				 => true
);

// available video bitrates
$available_bitrates = array();

$available_bitrates['original'] = array(
	'name'					=> $hcms_lang['original'][$lang],
	'checked'				=> true
);

$available_bitrates['200k'] = array(
	'name'					=> $hcms_lang['low'][$lang].' (200k)',
	'checked'				=> false
);

$available_bitrates['768k'] = array(
	'name'					=> $hcms_lang['medium'][$lang].' (768k)',
	'checked'				=> false
);

$available_bitrates['1856k'] = array(
	'name'		 => $hcms_lang['high'][$lang].' (1856k)',
	'checked'	 => false
);

// availbale video sizes
$available_videosizes = array();

$available_videosizes['o'] = array(
	'name'					=> $hcms_lang['original'][$lang],
	'checked'				=> true,
	'individual'		=> false
);

$available_videosizes['s'] = array(
	'name'					=> $hcms_lang['low-resolution-of-320-pixel-width'][$lang],
	'checked'				=> false,
	'individual'		=> false
);

$available_videosizes['l'] = array(
	'name'					=> $hcms_lang['medium-resolution-of-640-pixel-width'][$lang],
	'checked'				=> false,
	'individual'		=> false
);

$available_videosizes['xl'] = array(
	'name'					=> $hcms_lang['high-resoltion-of-1280x720-pixel'][$lang],
	'checked'				=> false,
	'individual'		=> false
);

$available_videosizes['fhd'] = array(
	'name'					=> "Full HD 1920x1080 Pixel",
	'checked'				=> false,
	'individual'		=> false
);

$available_videosizes['uhd'] = array(
	'name'					=> "Ultra HD 3840x2160 Pixel",
	'checked'				=> false,
	'individual'		=> false
);

$available_videosizes['i'] = array(
	'name'		 => $hcms_lang['individual-of-'][$lang],
	'checked'	 => false,
	'individual' => true
);

// available audio bitrates
$available_audiobitrates = array();

$available_audiobitrates['original'] = array(
  'name'    => $hcms_lang['original'][$lang],
  'checked' => true
);

$available_audiobitrates['64k'] = array(
  'name'    => $hcms_lang['low'][$lang].' (64 kb/s)',
  'checked' => false
);

$available_audiobitrates['128k'] = array(
  'name'    => $hcms_lang['medium'][$lang].' (128 kb/s)',
  'checked' => false
);

$available_audiobitrates['192k'] = array(
  'name'    => $hcms_lang['high'][$lang].' (192 kb/s)',
  'checked' => false
);

// flip
$available_flip = array();
$available_flip['fv'] = $hcms_lang['vertical'][$lang];
$available_flip['fh'] = $hcms_lang['horizontal'][$lang];
?>