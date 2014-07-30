<?php
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
                                                     // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                          // HTTP/1.0




/**************************************************************************************************/
function get_col($array, $num) { //extracts collon from two-dimensional array
/**************************************************************************************************/
	return $array[$num];
}
/**************************************************************************************************/
function get_row($array, $num) { //extracts row from two-dimensional array
/**************************************************************************************************/
	$retmas = Array();
		$size_x = sizeof($array);
		for($x=0;$x<$size_x;$x++) {
			$retmas[] = $array[$x][$num];
		}
	return $retmas;
}

/**************************************************************************************************/
function get_dispersion($array, $bg) { //gets dispersion from one-dimensional array using average $bg
/**************************************************************************************************/
	
	$size = sizeof($array);
	if ($size>0) {
		$dispersion = 0.0;
		for($c=0;$c<$size;$c++) {
			$val = $bg-$array[$c];
			$dispersion+=$val*$val;
		}
		return $dispersion;
	}
	else {
		echo "error!!!!!!!!!!";
		return -1;
	}
}
/**************************************************************************************************/
function parse_letters($row, $max_x, $max_y, $bg) {
/**************************************************************************************************/
global $conf;
	$min_dispersion = $conf['letters_min_dispersion'];
	$letter = -1;
	$adding = false;
	for($x=0;$x<=$max_x;$x++) {
		$col = get_row($row, $x);
		$dispersion = get_dispersion($col, $bg);
		if ($dispersion > $min_dispersion) {
			if ($adding==false)
				$letter++;
			$letters[$letter][]=$col;
			$adding = true;
		}
		else {
				$adding = false;
		}
	}
	$size = sizeof($letters);
	for($c=0;$c<$size;$c++)
		$letters[$c] = transpon($letters[$c]);
	return $letters;
}
/**************************************************************************************************/
function parse_word_lines($mas, $max_x, $max_y, $bg) {
/**************************************************************************************************/
global $conf;
	$min_dispersion = $conf['word_lines_min_dispersion'];
	$line = -1;
	$adding = false;
	for($y=0;$y<=$max_y;$y++) {
		$row = get_row($mas, $y);
		$dispersion = get_dispersion($row, $bg);

		if ($dispersion > $min_dispersion) {
			if ($adding==false)
				$line++;
			$lines[$line][]=$row;
			$adding = true;
		}
		else {
				$adding = false;
		}
	}
	return $lines;
}
/**************************************************************************************************/
function test_char_verbose($letter, $template_index,$bg) {
/**************************************************************************************************/
global $char_mas;
global $max_color;

$size = $char_mas[$template_index]['size'];
$char = $char_mas[$template_index]['char'];

	$height=sizeof($letter);
	$width=sizeof($letter[0]);
	$sum = 0;
	$x_diff = $size[0]/$width;
	$y_diff = $size[1]/$height;
	$color_diff = 255/$max_color;

	echo "<pre>";
	for($y=0;$y<$height;$y++) {
		echo "<br>";
		for($x=0;$x<$width;$x++) {
				$x_rel = ceil($x*$x_diff); //translate $x, $y to relative coords
				$y_rel = ceil($y*$y_diff);
			$val = diff_char($letter, $char,$x_diff,$y_diff, $x,$y,$bg);
			$sum+= $val;

			if ($letter[$y][$x]!=$char[$y_rel][$x_rel]) {
				echo '<font color="red">'.$letter[$y][$x].'-'.$char[$y_rel][$x_rel]."</font>&nbsp;";
			}else {
				echo $letter[$y][$x].'-'.$char[$y_rel][$x_rel]."</font>&nbsp;";
			}
			//echo ($letter[$y][$x]*$color_diff).':'.$char[$y_rel][$x_rel]."\t";

		}
	}
	echo "</pre>";
	return $sum;
}
/**************************************************************************************************/
function test_chars($letter,$bg,$char_mas,$max_color) {
/**************************************************************************************************/
//global $char_mas;
//global $max_color;

	$lib_size = sizeof($char_mas);
	$min_disp = -1;
	for($c=0; $c<$lib_size;$c++) {
		$disp = test_char($letter, $c, $bg,$char_mas,$max_color);
		//echo 'AT:'.$c.':'.$disp.'<br>';
		if (($disp<$min_disp)||($min_disp==-1)) {
			$min_disp = $disp;
			$min_disp_at = $c;
		}
	}
	//$disp = test_char_verbose($letter, $min_disp_at,$bg);
	return Array($min_disp,$min_disp_at);
	//return $min_disp_at;
}

/**************************************************************************************************/
function test_char($letter, $template_index,$bg,$char_mas,$max_color) {
/**************************************************************************************************/
//global $char_mas;
//global $max_color;

$size = $char_mas[$template_index]['size'];
$char = $char_mas[$template_index]['char'];

	$height=sizeof($letter);
	$width=sizeof($letter[0]);
	$sum = 0;
	$color_diff = 255/$max_color;
	$x_diff = $size[0]/$width;
	$y_diff = $size[1]/$height;

	for($y=0;$y<$height;$y++) {
		for($x=0;$x<$width;$x++) {
			$sum+= diff_char($letter, $char,$x_diff,$y_diff, $x,$y, $bg);
		}
	}
	return $sum;
}
/**************************************************************************************************/
function diff_char($l_mas, $c_mas,$x_diff,$y_diff, $x,$y, $bg) { //returns dispersion of one pixel in letter's coord. system
/**************************************************************************************************/

	global $color_diff;

	$x_rel = ceil($x*$x_diff); //translate $x, $y to relative coords
	$y_rel = ceil($y*$y_diff);
	$char_val = $c_mas[$y_rel+1][$x_rel]+$c_mas[$y_rel-1][$x_rel]+$c_mas[$y_rel][$x_rel+1]+$c_mas[$y_rel][$x_rel-1];
	$char_val = $char_val/4;
	//echo '['.($l_mas[$y][$x]*$color_diff)-$c_mas[$y_rel][$x_rel])).']';
	$val_m[] = abs(($l_mas[$y][$x])-($c_mas[$y_rel][$x_rel]));

	$val_m[] = abs(($l_mas[$y][$x])-($c_mas[$y_rel+1][$x_rel]));
	$val_m[] = abs(($l_mas[$y][$x])-($c_mas[$y_rel-1][$x_rel]));
	$val_m[] = abs(($l_mas[$y][$x])-($c_mas[$y_rel][$x_rel+1]));
	$val_m[] = abs(($l_mas[$y][$x])-($c_mas[$y_rel][$x_rel-1]));

	$val_m[] = abs(($l_mas[$y][$x])-($c_mas[$y_rel+1][$x_rel+1]));
	$val_m[] = abs(($l_mas[$y][$x])-($c_mas[$y_rel-1][$x_rel-1]));
	$val_m[] = abs(($l_mas[$y][$x])-($c_mas[$y_rel+1][$x_rel-1]));
	$val_m[] = abs(($l_mas[$y][$x])-($c_mas[$y_rel-1][$x_rel+1]));

	$val = min($val_m);
	return $val*$val*$val*$val*$val;
}

function getmicrotime(){ 
	$tmp = explode(" ",microtime());
    return ((float)$tmp[0] + (float)$tmp[1]); 
    } 

/**************************************************************************************************/
function print_matrix($letter) {
/**************************************************************************************************/
	$ret = '';
	foreach($letter as $row) {
		$ret.= "\n";
		foreach($row as $pixel) {
				$ret.= $pixel." ";
		}
	}
	return $ret;
}

/**************************************************************************************************/
function print_array($letter,$bg) {
/**************************************************************************************************/
	$ret = '';
	$letter_size = sizeof($letter);
	$c=1;
$ret.= "\n\t".'Array("char"=>Array('."\n";
	foreach($letter as $row) {
		$ret.= "\t\tArray(";
		$row_size= sizeof($row);
		$c1=1;
		foreach($row as $pixel) {
			$ret.= $pixel;
			if (($row_size-$c1)!=0)
				$ret.= ",";
			$c1++;
		}
	
		if (($letter_size-$c)!=0)
			$ret.= "),\n";
		else
			$ret.= ")\n";

		$c++;
	}
$ret.= "\t\t".'), "size"=>Array('.($c1-1).','.($c-1)."))";

	return $ret;
}
/**************************************************************************************************/
function flatten_matrix($letter, $bg, $max_color) { //replaces background with 0 and 1 otherwice
/**************************************************************************************************/
//global $max_color;

	$y_size = sizeof($letter);
	$x_size = sizeof($letter[0]);
	
	for($c=0;$c<$y_size;$c++) {
		for($c1=0;$c1<$x_size;$c1++) {
				$tmp_val = (abs($bg-$letter[$c][$c1])/$max_color)*3;
				$letter[$c][$c1]=round($tmp_val*$tmp_val);
		}
	}
	return $letter;
}
/**************************************************************************************************/
function write_template($filename) { //returns template
/**************************************************************************************************/
	header("Content-type: application/octet-stream");
	header("Connection: close");

	$image_data=extract_mas($filename);
	
	$max_x		= $image_data['imagewidth']-1;
	$max_y		= $image_data['imageheight']-1;
	$mas		= $image_data['mas'];
	$max_color	= $image_data['max_color'];
	$bg			= $image_data['bg'];

	
	$lines = parse_word_lines($mas, $max_x, $max_y, $bg);
	$ret = '';
	$ret.= '<?php';
	foreach($lines as $word_line) {
		$letters = parse_letters($word_line, $max_x, $max_y, $bg);
		$ret.="\n".'$'.'char_global_mas[] = Array (';
		$letters_size = sizeof($letters);
		$c = 1;
		foreach($letters as $letter) {
			$letter = flatten_matrix($letter,$bg,$max_color);
			$ret.= print_array($letter,$bg);
			if (($letters_size-$c)!=0)
				$ret.=',';
			$c++;
		}
		$ret.="\n".');'."\n";
	}
	$ret.= '?>';
	return $ret;

}
/**************************************************************************************************/
function transpon($mas) { //returns a tronsponed matrix
/**************************************************************************************************/
	$retmas = Array();
	foreach($mas as $row) {
		$size = sizeof($row);
		for($c=0;$c<$size;$c++) {
			$retmas[]= get_row($mas, $c);
		}
		return $retmas; //nasty should think of cleaner solution
	}
	return $retmas;
}
/**************************************************************************************************/
function extract_mas($filename) { //returns a two dimensional matrix with parsed data
/**************************************************************************************************/
	$imagehw = @GetImageSize($filename) or die('ERROR occured while trying to stat image file: '.$filename); 
	$imagewidth = $imagehw[0]; 
	$imageheight = $imagehw[1]; 
	$imagetype = $imagehw[2];

	if($imagetype==1) {			//GIF

//	Uncoment next three lines if you want to use GIF+gif2png. Insecure!!!! anything could be passed to shell.	
//		$ret = `cat $filename|/usr/local/bin/gif2png`;
//		$filename = 'noname.png';
//		$im = imagecreatefrompng($filename);

//btw if anyone has a good idea how to integrate other image formats please let me know

		echo 'ERROR: GIF support is not in by default! If you really want to enable this check parse_image function in index.php';
		exit(0);
	}
	elseif($imagetype==2) {		//JPG
		$im = imagecreatefromjpeg($filename);
	}
	elseif($imagetype==3) {		//PNG
		$im = imagecreatefrompng($filename);
	}
	else {
		echo 'ERROR:Unsuported image type code:'.$imagetype.'. phpOCR only supports PNG, JPG, GIF(see doc.)';
		exit(0);
	}


	$max_color=-1;
	for($y=0;$y<$imageheight;$y++) {
		for($x=0;$x<$imagewidth;$x++) {
			$rgb = ImageColorAt($im, $x, $y);
			if($rgb>$max_color) $max_color=$rgb;
			$mas[$x][$y] = $rgb;
		}
	}
	$bg = $mas[0][0];		//we detect background by checking the first top pixel

return Array('mas'=>$mas,'max_color'=>$max_color,'imagewidth'=>$imagewidth,'imageheight'=>$imageheight,'bg'=>$bg);
}
/**************************************************************************************************/
function parse_image($filename,$font_file) { //returns a two dimensional matrix with parsed data
/**************************************************************************************************/

	$image_data=extract_mas($filename);
	
	$max_x		= $image_data['imagewidth']-1;
	$max_y		= $image_data['imageheight']-1;
	$mas		= $image_data['mas'];
	$max_color	= $image_data['max_color'];
	$bg			= $image_data['bg'];

	$lines = parse_word_lines($mas, $max_x, $max_y, $bg);
	require($font_file);		//font library
	$line_index=0;
	foreach($lines as $word_line) {
		//echo "&lt;LINE&gt;\n";
		$letters = parse_letters($word_line, $max_x, $max_y, $bg);
		//echo "<br>";
		$letter_index=0;
		if (sizeof($char_global_mas)>1) echo 'WARNING!!! The output will contain only result of the second font<br>';
		foreach($char_global_mas as $char_mas) {	//for testing purposes. tests w different fonts
			foreach($letters as $letter) {
				$letter = flatten_matrix($letter,$bg,$max_color); //global $letter
					$tmp = test_chars($letter,$bg,$char_mas,$max_color);
					$disp = $tmp[0];	//relative number of trust
					$num = $tmp[1];		//parsed number
					$retmas[$line_index][$letter_index]=Array($num,$disp);
					//echo '[[[['.$num.']]]]';
					$letter_index++;
			}
		}
		$line_index++;
	}
	return $retmas;
}
/**************************************************************************************************/
//MAIN
/**************************************************************************************************/

//If you create a new font include file replace char_inc_6.php with your own
$conf['font_file']					= 'char_inc_6.php';

//The defoult image that gets parsed when no parameter filename is given
$conf['default_image']				= 'tmp1.png';

//The default output format. You can chose from xml,html,plain,template.
$conf['default_output_format']		= 'html';

//You shold probably not need to change thees
$conf['word_lines_min_dispersion']	= 0;
$conf['letters_min_dispersion']		= 0;


$out=isset($_REQUEST['out'])?$_REQUEST['out']:$conf['default_output_format'];
$filename = isset($_REQUEST['filename'])?$_REQUEST['filename']:$conf['default_image'];


if ($filename == 'upload') {
	$filename = $_FILES['userfile']['tmp_name'];
}


$time_start = getmicrotime();
if($out!='template') {

	//*******************************************************
	//This is the main function. Format of the output array is $retmas[$line_number][$letter_number][$type]
	//where $type is 0 for digit and 1 for relative closeness
	$retmas = parse_image($filename,$conf['font_file']);
	//*******************************************************

	$time = getmicrotime() - $time_start;	//execution time
}

if($out=='xml')
	echo print_output_xml($retmas,$filename,$time);
elseif ($out=='html')
	echo print_output_html($retmas,$filename,$time);
elseif ($out=='plain')
	echo print_output_plain($retmas);
elseif ($out=='template')
	echo write_template($filename);	//this is different from xml,html or plain

/**************************************************************************************************/
function print_output_html($retmas,$filename,$time) {
/**************************************************************************************************/
	$ret = '';
	$ret.='<html>';
	$ret.='<body>';
	$ret.='<center>';
	$ret.='<table border=0>';
	$ret.='<tr><td valign="top" ><center><font size="+3">phpOCR</font></center></td><td></td></tr>';

	$ret.='<tr><td valign="top">Original image:</td><td><img src="'.$filename.'"></td></tr>';
	//$ret.='<p class="f">';

	$ret.='<tr><td valign="top">Line_count:</td><td>'.sizeof($retmas).'</td></tr>';
	$ret.='<tr><td valign="top">Parsed content:</td><td>';
	foreach($retmas as $line) {
		foreach($line as $digit) {
			$ret.='<font size="+2"><B>'.$digit[0].'</B></font>('.$digit[1].')';
		}
		$ret.='<br>';
	}
	$ret.='</td></tr>';
	$ret.='<tr><td>Time consumed:</td><td>'.$time.'s</td></tr>';
	$ret.='<tr><td></td><td>&nbsp;<br>&nbsp;</td></tr>';

	$ret.='<form enctype="multipart/form-data" action="index.php" method="post">';
	$ret.='<tr><td colspan=2>';
	$ret.='<input type="hidden" name="filename" value="upload">';
	$ret.='Send this file: <input name="userfile" type="file">';
	$ret.=', output type:<SELECT NAME="out"><option value="html">html<option value="plain">plain<option value="xml">xml<option value="template">template</SELECT>';
	$ret.='<input type="submit" value="Parse">';
	$ret.='</td></tr>';
	$ret.='</form>';

	$ret.='<tr><td colspan=2>or</td></tr>';

	$ret.='<tr><td colspan=2>';
	$ret.='<form enctype="multipart/form-data" action="index.php" method="post">';
	$ret.='Type in URL: <input name="filename" type="text" value="http://">';
	$ret.=', output type:<SELECT NAME="out"><option value="html">html<option value="plain">plain<option value="xml">xml<option value="template">template</SELECT>';
	$ret.='<input type="submit" value="Parse">';
	$ret.='</td></tr>';
	$ret.='</form>';

	$ret.='<tr><td colspan=2>or</td></tr>';

	$ret.='<tr><td colspan=2>';
	$ret.='<form enctype="multipart/form-data" action="index.php" method="post">';
	$ret.='Use default image.';
	$ret.=' output type:<SELECT NAME="out"><option value="html">html<option value="plain">plain<option value="xml">xml<option value="template">template</SELECT>';
	$ret.='<input type="submit" value="Parse">';
	$ret.='</form>';
	$ret.='</td></tr>';

	$ret.='<tr><td></td><td>&nbsp;<br>&nbsp;</td></tr>';

	$ret.='<tr><td colspan=2>';
	$ret.='<B>Note:</B> For use in outomated scripts try something like:<br>';
	$ret.='curl -s -o out1.txt http://YOUR_URL/index.php?out=plain&filename=http://REMOTE_URL/image.png<br>';
	$ret.='or better just hack the index.php :)';
	$ret.='</td></tr></table>';
	$ret.='</center>';
	$ret.='</body>';
	$ret.='</html>';
	return $ret;
}
/**************************************************************************************************/
function print_output_plain($retmas) {
/**************************************************************************************************/
header("Content-type: application/octet-stream");
header("Connection: close");

	$ret = '';
	foreach($retmas as $line) {
		foreach($line as $digit) {
			$ret.=$digit[0];
		}
		$ret.="\n";
	}
return substr($ret,0,strlen($ret)-1);
}
/**************************************************************************************************/
function print_output_xml($retmas,$filename,$time) {
/**************************************************************************************************/
header("Content-type: text/xml");
header("Connection: close");
	$ret.='<?xml version="1.0" encoding="utf-8" ?>'."\n\n";

	$ret.='<output time="'.$time.'">'."\n";
	$ret.="\t".'<line_count>'.sizeof($retmas).'</line_count>'."\n";

	for($c=0;$c<sizeof($retmas);$c++) {
		$ret.="\t".'<line number="'.($c+1).'">'."\n";
		$ret.="\t\t".'<digit_count>'.sizeof($retmas[$c]).'</digit_count>'."\n";
		for($c1=0;$c1<sizeof($retmas[$c]);$c1++) {
			$ret.="\t\t".'<digit number="'.($c1+1).'">'."\n";
				$ret.="\t\t\t".'<value>'.$retmas[$c][$c1][0].'</value>'."\n";
				$ret.="\t\t\t".'<closeness>'.$retmas[$c][$c1][1].'</closeness>'."\n";
			$ret.="\t\t".'</digit>'."\n";
		}
		$ret.="\t".'</line>'."\n";
	}
	$ret.='</output>'."\n";

return $ret;
}
?>


