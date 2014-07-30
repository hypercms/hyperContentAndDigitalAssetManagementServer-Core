<?
/*============================================================
gencoder v1.0 copyright (c) 2001
==============================================================
gencoder is a simple php encoder that use base64 algorithm to
encode and decode the script. the drawback of this script:
- it doesn't have a key for encryption / decryption method.
- run slower then normal php script (because it need
  to re-read the script and decode it).
- only work on single complete php tag (one open php tag and
  one close php tag) because of limitation of eval function.
unlike zend encoder, this script won't solve your problem
from hiding the source code from advance programmer but
at least you're one step closer to making your code tougher
for people to steal.
==============================================================
this is free software. you can redistribute it and/or
modify it under the terms of the gnu general public license
as published by the free software foundation.
==============================================================
author: R. Galuh Prasetyo <rgprasetyo@yahoo.com>
============================================================*/

//detects if launch from shell or browser
if ($_SERVER['PHP_SELF'] != "") {
  //from browser
  if (!isset ($source)) {
    echo "Syntax: gencode.php?site=$site&source=&lt;php_sorce_script&gt;[&dest=&lt;php_dest_script&gt;]";
    exit;
  }
  if (!isset ($dest)) $dest = $source;
} else {
  //from shell
  if (!isset ($HTTP_SERVER_VARS[argv][1])) {
    echo "\nSyntax: gencode.php?site=$site& <php_source_script> [php_dest_script']\n";
    exit;
  }
  $source = $HTTP_SERVER_VARS[argv][1];
  if (isset ($HTTP_SERVER_VARS[argv][2])) $dest = $HTTP_SERVER_VARS[argv][2];
}

//retrieve source code
$fs = fopen ($source, "r");
$code = fread ($fs, filesize ($source));
fclose ($fs);

//check if source already encoded
if (ereg ("^<\?/\*gencoder", $code)) {
  echo "\nWarnning, \"$source\" already encrypted by gencode!!\n";
  exit;
}

//remove php tag
$code = preg_replace("<\?|<\?php|\?>", "", $code);

//encode by base64
$code = chunk_split (base64_encode ($code));

//generate encoded string
$decript_code = 'if($_SERVER[\'PHP_SELF\']!="")$s=file($PATH_TRANSLATED);else$s=file($HTTP_SERVER_VARS[argv][0]);foreach($s as $l){if(ereg("^\*/",$l))$b=false;if($b)$ss.=$l;if(ereg("^<\?/\*gencoder",$l))$b=true;}eval(base64_decode($ss));?>';
$encoded = "<?/*gencoder\n$code*/$decript_code";

//write encoded string to destination file
$fd = fopen ($dest, "w");
fwrite ($fd, $encoded);
fclose ($fd);
?> 