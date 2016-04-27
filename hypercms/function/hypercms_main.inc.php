<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// ============================================ MAIN FUNCTIONS ========================================

// ========================================== NUMBERS =======================================

// ------------------------------------- correctnumber ------------------------------------------

// function: correctnumber ()
// input: formated number
// output: correct mathematical number / false on error

function correctnumber ($number)
{
  global $mgmt_config;
  
  if ($number != "")
  {
    $comma = strpos ($number, ",");
    $dot = strpos ($number, ".");
  
    // example: 1.200,50 => 1200.50
    if ($comma > $dot)
    {
      return floatval (str_replace (',', '.', str_replace ('.', '', $number)));
    }
    // example: 1,200.50 => 1200.50
    elseif ($dot > $comma)
    {
      return floatval (str_replace (',', '', $number));
    }
    else return intval ($number);
  }
  else return false;
}

// ========================================== SPECIALCHARACTERS =======================================

// ------------------------------------- cleancontent ------------------------------------------

// function: cleancontent ()
// input: expression (mixed), character set (optional)
// output: converted expression / false on error

function cleancontent ($text, $charset="UTF-8")
{
  global $mgmt_config;
  
  if ($text != "" && $charset != "")
  {
    // remove tags
    $text = strip_tags ($text);
    
    // decode characters
    $text = html_decode ($text, $charset);

    // replace characters
    $text = str_replace (array(".....", "....", "...", ".."), ".", $text);
    $text = str_replace (array("_____", "____", "___", "__"), "_", $text);
    $text = str_replace (array("\"", "'", "(", ")", "{", "}", "[", "]", ".", ",", ";", "_", "\t", "\r\n", "\r", "\n"), " ", $text);
    $text = preg_replace ('/\s+/', " ", $text);

    return $text;
  }
  else return false;
}

// ------------------------------------- convertchars ------------------------------------------

// function: convertchars ()
// input: expression as string or array, input character set (optional), output character set (optional)
// output: converted expression / false on error

function convertchars ($expression, $charset_from="UTF-8", $charset_to="UTF-8")
{
  global $mgmt_config;
  
  if ($expression != "" && $charset_from != "" && $charset_to != "")
  {
    if ($charset_from == $charset_to)
    {
      return $expression;
    }
    else
    {
      if (!is_array ($expression))
      {
        // convert
        $expression = iconv ($charset_from, $charset_to, $expression);
      }
      elseif (is_array ($expression))
      {
        foreach ($expression as &$value)
        {
          // convert
          $value = convertchars ($value, $charset_from, $charset_to);
        }
      }
      
      if ($expression != "") return $expression;
      else return false;      
    }
  }
  else return false;
}

// ------------------------- specialchr -----------------------------
// function: specialchr()
// input: expression, list of characters to be excluded from search (optional) 
// output: true/false

// description:
// Tests if an expression includes special characters (true) or does not (false).
// Allows you to accept characters through including it into $accept (e.g. #$...)

function specialchr ($expression, $accept="")
{
  // escape chars:  . \ + * ? [ ^ ] $ ( ) { } = ! < > | : -
  $accept = preg_quote ($accept);
  
  // check if expression is on watch list 
  if (preg_match ("/^[a-zA-Z0-9".$accept."]+$/", $expression)) return false;
  else return true;
}

// ------------------------- specialchr_encode -----------------------------
// function: specialchr_encode()
// input: expression, remove all special characters [yes,no]
// output: expression without special characters (for file names)

// description:
// Renames all special characters for file names to an expression according to given rules

function specialchr_encode ($expression, $remove="no")
{
  global $user, $mgmt_config, $hcms_lang, $lang;
  
  if (is_string ($expression))
  {
    $path_parts = array();
    $result_parts = array();
    
    // check if expression holds a path
    if (substr_count ($expression, "/") > 0)
    {
      $expression_parts = explode ("/", $expression);
    }
    else $expression_parts[0] = $expression;
    
    foreach ($expression_parts as $expression)
    {
      if ($expression != "" && $expression != "%comp%" && $expression != "%page%" && $expression != "%media%" && $expression != "%tplmedia%" && $expression != "%media%" && $expression != "%object%")
      {
        // encode to UTF-8 if name is not utf-8 coded
        if (!is_utf8 ($expression)) $expression = utf8_encode (trim ($expression));    
        // replace ~ since this is the identifier and replace invalid file name characters (symbols)
        $strip = array ("~", "%", "`", "!", "@", "#", "$", "^", "&", "*", "=", 
                        "\\", "|", ";", ":", "\"", "&quot;", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
                        "Ã¢â‚¬â€", "Ã¢â‚¬â€œ", ",", "<", "&lt;", ">", "&gt;", "/", "?");
                         
        $expression = str_replace ($strip, "", strip_tags ($expression));  
        // replace multiple spaces
        $expression = preg_replace ('/\s+/', " ", $expression);  
        // replace all special characters
        if ($remove == "yes") $expression = preg_replace ("/[^a-zA-Z0-9_\\-]/", "", $expression);
        // url encoding for file name transformation (replace all special characters according to RFC 1738)
        $expression = rawurlencode ($expression); 
        // replace % to avoid urldecoding 
        $expression = str_replace ("%", "~", $expression);
      }
      
      $result_parts[] = $expression;
    }
  
    if (sizeof ($result_parts) > 1) return implode ("/", $result_parts);
    else return $result_parts[0];
  }
  else return false;
}

// ------------------------- specialchr_decode -----------------------------
// function: specialchr_decode()
// input: expression  
// output: expression with special characters (for file names) / false

// description:
// This is the decode function for function specialchr_encode

function specialchr_decode ($expression)
{
  global $user, $mgmt_config, $hcms_lang, $lang;
  
  if (is_string ($expression))
  { 
    // replace % to avoid urldecoding 
    $expression = str_replace ("~", "%", $expression);
    
    // url encoding for file name transformation (replace all special characters according to RFC 1738)
    $expression = rawurldecode ($expression);
    
    // encode to UTF-8 if name is not utf-8 coded
    if (!is_utf8 ($expression)) $expression = utf8_encode ($expression);
  
    return $expression;
  }
  else return false;
}

// ------------------------- is_utf8 -----------------------------
// function: is_utf8()
// input: expression  
// output: if string is utf-8 encoded true / false otherwise

// description:
// This function is an alternative to mb_check_encoding (which requires an extra PHP module).
// It is not failsave!

function is_utf8 ($str)
{
  $strlen = strlen ($str);
  
  for ($i=0; $i<$strlen; $i++)
  {
    $ord = ord ($str[$i]);
    
    if ($ord < 0x80) continue; // 0bbbbbbb
    elseif (($ord&0xE0)===0xC0 && $ord>0xC1) $n = 1; // 110bbbbb (exkl C0-C1)
    elseif (($ord&0xF0)===0xE0) $n = 2; // 1110bbbb
    elseif (($ord&0xF8)===0xF0 && $ord<0xF5) $n = 3; // 11110bbb (exkl F5-FF)
    else return false; // invalid UTF-8 character
    
    for ($c=0; $c<$n; $c++) // $n Folgebytes? // 10bbbbbb
    {
      if (++$i===$strlen || (ord ($str[$i])&0xC0)!==0x80) return false; // not a valid UTF-8 character
    }
  }
  
  return true; // no not valid UTF-8 character was found, string must be UTF-8
}

// ------------------------- is_latin1 -----------------------------
// function: is_latin1()
// input: expression  
// output: if string is latin 1 encoded true / false otherwise

// description:
// This function is an alternative to mb_check_encoding (which requires an extra PHP module).
// It is not failsave!

function is_latin1 ($str)
{
  return (preg_match ("/^[\\x00-\\xFF]*$/u", $str) === 1);
} 

// -------------------------------- makestring --------------------------------
// function: makestring()
// input: (multidimensional) array
// output: string including all array values / false on error

function makestring ($array)
{
  if (is_array ($array))
  {
    $result = "";
    
    foreach ($array as $item) 
    {
      if (is_array ($item)) $result .= makestring ($item);
      else $result .= $item;
    }
    
    return $result;
  }
  elseif (is_string ($array)) return $array;
  else return false;
}

// -------------------------------- splitstring --------------------------------
// function: splitstring()
// input: string with ";" or "," as seperator
// output: array with string splitted into array / false on error

function splitstring ($string)
{
  if ($string != "" && is_string ($string))
  {
    $string = str_replace ("\n", "", $string);
    $result_array = array();
    $array1 = explode (",", $string);
        
    foreach ($array1 as $entry1)
    {
      $entry1 = trim ($entry1);
      
      if ($entry1 != "")
      {
        $array2 = explode (";", $entry1);
            
        foreach ($array2 as $entry2)
        {
          $entry2 = trim ($entry2);
          
          if ($entry2 != "")
          {
            $result_array[] = $entry2;
          }
        }
      }
    }
    
    if (is_array ($result_array)) return $result_array;
    else return false;
  }
  else return false;
}

// ------------------------- is_emptyfolder -----------------------------
// function: is_emptyfolder()
// input: path to folder
// output: true / false

// description:
// Checks if a directory/folder is empty (has no published objects or other files)

function is_emptyfolder ($dir)
{
  global $mgmt_config;
  
  // deconvert path
  if (substr_count ($dir, "%page%") == 1 || substr_count ($dir, "%comp%") == 1)
  {
    $dir = deconvertpath ($dir, "file");
  }
    
  if ($dir != "" && is_dir ($dir))
  {
    $handle = opendir ($dir);

    while (false !== ($entry = readdir ($handle)))
    {
      if ($entry != "." && $entry != ".." && $entry != ".folder" && substr ($entry, -4) != ".off") return false;
    }
    
    return true;
  }
  else return false;
}

// -------------------------------- is_supported --------------------------------
// function: is_supported()
// input: preview array holding the supported file extensions as key and references to executables as value, file name or file extension
// output: true / false

// description:
// This function determines if a certain file type by its file extension is supported by the systems media conversion

function is_supported ($preview_array, $file)
{
  if (is_array ($preview_array) && $file != "")
  {
    // get file extension
    if (substr_count ($file, ".") > 0) $ext = strtolower (trim (strrchr ($file, "."), "."));
    else $ext = $file;
    
    foreach ($preview_array as $preview_ext => $preview_exec)
    {
      // check file extension
      if (substr_count ($preview_ext.".", ".".$ext.".") > 0 && trim ($preview_exec) != "") return true;
    }
  }
  
  return false;
}

// -------------------------------- is_cloudstorage --------------------------------
// function: is_cloudstorage()
// input: publication name (optional)
// output: true / false

// description:
// This function determines if a cloud storage has been defined in the main configuration or for a specific publication

function is_cloudstorage ($site="")
{
  global $mgmt_config;
  
  // if cloud connector is available
  if (is_array ($mgmt_config) && is_file ($mgmt_config['abs_path_cms']."connector/cloud/hypercms_cloud.inc.php"))
  {
    if (valid_publicationname ($site))
    {
      // load publication config if not available
      if (!isset ($mgmt_config[$site]['storage_type']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
      {
        require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
      }

      // if the cloud storage is disabled for the publication
      if (!isset ($mgmt_config[$site]['storage_type']) || strtolower ($mgmt_config[$site]['storage_type']) == "local")
      {
        return false;
      }
    }
  
    // AWS S3 cloud storage
    if (!empty ($mgmt_config['aws_access_key_id']) && !empty ($mgmt_config['aws_secret_access_key']) && !empty ($mgmt_config['aws_bucket']))
    {
      return true;
    }
    
    // Google cloud storage
    if (!empty ($mgmt_config['gs_access_key_id']) && !empty ($mgmt_config['gs_secret_access_key']) && !empty ($mgmt_config['gs_bucket']))
    {
      return true;
    }
  }
  
  return false;
}

// ---------------------- is_cloudobject -----------------------------
// function: is_cloudobject()
// input: path to media file or media file name
// output: true / false

// description:
// This functions verifies if an object/file is available in the cloud storage

function is_cloudobject ($file)
{
  global $mgmt_config;

  if (valid_locationname ($file) && getmediacontainerid ($file))
  {
    $result = false;

    // AWS S3 cloud storage
    if (!empty ($mgmt_config['aws_access_key_id']) && !empty ($mgmt_config['aws_secret_access_key']) && !empty ($mgmt_config['aws_bucket']) && function_exists ("is_S3object"))
    {
      $result = is_S3object ($mgmt_config['aws_bucket'], getobject ($file));
    }
    // Google cloud storage
    elseif (!empty ($mgmt_config['gs_access_key_id']) && !empty ($mgmt_config['gs_secret_access_key']) && !empty ($mgmt_config['gs_bucket']) && function_exists ("is_GSobject"))
    {
      $result = is_GSobject ($mgmt_config['gs_bucket'], getobject ($file));
    }
    
    return $result;
  }
  else return false;
}

// -------------------------------- is_date --------------------------------
// function: is_date()
// input: date as string, date format (optional)
// output: true / false

// description:
// This function determines if a string represents a valid date format

function is_date ($date, $format="Y-m-d")
{
  if ($date != "" && $format != "")
  {
    $date_check = DateTime::createFromFormat ($format, $date);
    
    if ($date_check && $date_check->format($format) == $date) return true;
    else return false;
  }
  else return false;
}

// -------------------------------------- is_tempfile -------------------------------------------
// function: is_tempfile()
// input: file name or path [string]
// output: if file is a temp file true / false on error

// description:
// This functions checks if the provided file name is a temporary file

function is_tempfile ($path)
{
  global $user, $mgmt_config, $hcms_lang, $lang;
  
  @require ($mgmt_config['abs_path_cms']."include/tempfilepatterns.inc.php");

  if ($path != "" && is_array ($tempfile_patterns))
  {
    // extract the file name
    $object = getobject ($path);

    foreach ($tempfile_patterns as $pattern)
    {
      if (preg_match ($pattern, $object)) return true;
    }
  
    return false;
  }
  else return false;
}

// -------------------------------------- is_thumbnail -------------------------------------------
// function: is_thumbnail()
// input: file name or path [string], only thumbnail images should be considered as thumbnail [true,false]
// output: if file is a thumbnail file true / false on error

// description:
// This functions checks if the provided file name is a thumbnail file

function is_thumbnail ($media, $images_only=true)
{
  global $user, $mgmt_config, $hcms_lang, $lang;
  
  if ($media != "")
  {
    $container_id = getmediacontainerid ($media);
    
    if ($container_id != "")
    {
      if ($images_only == true && substr_count ($media, "hcm".$container_id.".thumb.jpg") > 0) return true;
      elseif ($images_only == false && substr_count ($media, "hcm".$container_id.".thumb.") > 0) return true;
      elseif ($images_only == false && substr_count($media, "hcm".$container_id.".orig.") > 0) return true;
      else return false;
    }
    else return false;
  }
  else return false;
}

// -------------------------------------- is_config -------------------------------------------
// function: is_config()
// input: file name or path [string]
// output: if file is a config file true / false if not

// description:
// This functions checks if the provided file name is a config file

function is_config ($media)
{
  global $user, $mgmt_config, $hcms_lang, $lang;
  
  if ($media != "")
  {
    $container_id = getmediacontainerid ($media);
    
    if ($container_id != "")
    {
      if (substr_count($media, "hcm".$container_id.".config.") > 0) return true;
      else return false;
    }
    else return false;
  }
  else return false;
}

// ---------------------- is_encryptedfile -----------------------------
// function: is_encryptedfile()
// input: path to file [string], file name [string]
// output: true / false

// description:
// This functions checks if the provided file is encrypted

function is_encryptedfile ($location, $file)
{
  global $user, $mgmt_config, $hcms_lang, $lang;
  
  if (valid_locationname ($location) && valid_objectname ($file))
  {
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";
    
    // load media file header
    if (is_file ($location.$file))
    {
      $data = loadfile_header ($location, $file);

      // encrypt data if media file is not encypted
      if (strpos ("_".$data, "<!-- hyperCMS:encrypted -->") > 0) return true;
      else return false;
    }
    else return false;
  }
  else return false;
}

// -------------------------------- is_document --------------------------------
// function: is_document()
// input: file name or file extension
// output: true / false

// description:
// This function determines if a certain file is a document (binary and text based)

function is_document ($file)
{
  global $mgmt_config, $hcms_ext;
  
  if ($file != "")
  {
    // load file extensions
    if (empty ($hcms_ext) || !is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
    
    // get file extension
    if (substr_count ($file, ".") > 0) $ext = strtolower (trim (strrchr ($file, "."), "."));
    else $ext = $file;
    
    if (substr_count (strtolower ($hcms_ext['cleartxt'].$hcms_ext['bintxt']).".", ".".$ext.".") > 0) return true;
    else return false;
  }
  else return false;
}

// -------------------------------- is_image --------------------------------
// function: is_image()
// input: file name or file extension
// output: true / false

// description:
// This function determines if a certain file is an image

function is_image ($file)
{
  global $mgmt_config, $hcms_ext;
  
  if ($file != "")
  {
    // load file extensions
    if (empty ($hcms_ext) || !is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
    
    // get file extension
    if (substr_count ($file, ".") > 0) $ext = strtolower (trim (strrchr ($file, "."), "."));
    else $ext = $file;
    
    if (substr_count (strtolower ($hcms_ext['image']).".", ".".$ext.".") > 0) return true;
    else return false;
  }
  else return false;
}

// -------------------------------- is_rawimage --------------------------------
// function: is_rawimage()
// input: file name or file extension
// output: true / false

// description:
// This function determines if a certain file is a raw image

function is_rawimage ($file)
{
  global $mgmt_config, $hcms_ext;
  
  if ($file != "")
  {
    // load file extensions
    if (empty ($hcms_ext) || !is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
    
    // get file extension
    if (substr_count ($file, ".") > 0) $ext = strtolower (trim (strrchr ($file, "."), "."));
    else $ext = $file;
    
    if (substr_count (strtolower ($hcms_ext['rawimage']).".", ".".$ext.".") > 0) return true;
    else return false;
  }
  else return false;
}

// -------------------------------- is_aiimage --------------------------------
// function: is_aiimage()
// input: file name or file extension
// output: true / false

// description:
// This function determines if a certain file is a vector-based Adobe Illustrator (AI) or AI-compatible EPS file

function is_aiimage ($file)
{
  if ($file != "")
  {
    // get file extension
    if (substr_count ($file, ".") > 0) $ext = strtolower (trim (strrchr ($file, "."), "."));
    else $ext = $file;
    
    if (substr_count (".ai.eps.", ".".$ext.".") > 0) return true;
    else return false;
  }
  else return false;
}

// -------------------------------- is_video --------------------------------
// function: is_video()
// input: file name or file extension
// output: true / false

// description:
// This function determines if a certain file is a video

function is_video ($file)
{
  global $mgmt_config, $hcms_ext;
  
  if ($file != "")
  {
    // load file extensions
    if (empty ($hcms_ext) || !is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
    
    // get file extension
    if (substr_count ($file, ".") > 0) $ext = strtolower (trim (strrchr ($file, "."), "."));
    else $ext = $file;
    
    if (substr_count (strtolower ($hcms_ext['video']).".", ".".$ext.".") > 0) return true;
    else return false;
  }
  else return false;
}

// -------------------------------- is_audio --------------------------------
// function: is_audio()
// input: file name or file extension
// output: true / false

// description:
// This function determines if a certain file is an audio file

function is_audio ($file)
{
  global $mgmt_config, $hcms_ext;
  
  if ($file != "")
  {
    // load file extensions
    if (empty ($hcms_ext) || !is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
    
    // get file extension
    if (substr_count ($file, ".") > 0) $ext = strtolower (trim (strrchr ($file, "."), "."));
    else $ext = $file;
    
    if (substr_count (strtolower ($hcms_ext['audio']).".", ".".$ext.".") > 0) return true;
    else return false;
  }
  else return false;
}

// ---------------------- is_mobilebrowser -----------------------------
// function: is_mobilebrowser()
// input: %
// output: true / false

// description:
// Detects if a mobile browser is used

function is_mobilebrowser ()
{
  global $user, $mgmt_config, $hcms_lang, $lang;
  
  if ($_SERVER['HTTP_USER_AGENT'])
  {
    $useragent = $_SERVER['HTTP_USER_AGENT'];
    
    if (preg_match ('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr ($useragent,0,4)))
    {
      return true;
    }
    else return false;
  }
  else return false;
}

// -------------------------------- copyrecursive --------------------------------
// function: copyrecursive()
// input: source directory, destination directory
// output: true/false

// description:
// This function copyies all directories and files from source to destination directory

function copyrecursive ($src, $dst)
{
  $result = true;
  $dir = opendir ($src);
  if (!is_dir ($dst)) @mkdir ($dst);
  
  if ($dir)
  {
    while (false !== ($file = readdir ($dir)))
    {
      if (($file != '.') && ($file != '..'))
      {
        if (is_dir ($src.$file)) $result = copyrecursive ($src.$file."/", $dst.$file."/");
        else $result = copy ($src.$file, $dst.$file);
        
        if ($result == false) break;
      }
    }
    
    closedir ($dir);
  }
  
  return $result;
} 

// ========================================== FILES AND LINKS =======================================

// ---------------------- correctfile -----------------------------
// function: correctfile()
// input: path to file or directory, file or directory name, user name
// output: correct filename/false

function correctfile ($abs_path, $filename, $user="")
{
  global $mgmt_config, $hcms_lang, $lang;

  if (valid_locationname ($abs_path) && valid_objectname ($filename))
  {
    // add slash if not present at the end of the location string
    if (substr ($abs_path, -1) != "/") $abs_path = $abs_path."/";    
  
    // deconvert path
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }
    
    // if given file or directory exists
    if (file_exists ($abs_path.$filename))
    {
      return $filename;
    }
    // if file was unpublished
    elseif (is_file ($abs_path.$filename.".off")) 
    {
      $filename = $filename.".off";
      return $filename;
    }
    // if file was published
    elseif (substr ($filename, strrpos ($filename, ".")) == ".off")
    {
      $filename = substr ($filename, 0, strrpos ($filename, "."));
      
      if (@is_file ($abs_path.$filename))
      {
        return $filename;
      }
      else return false;
    }
    // if file is locked by the same user (only for management files, like content containers, link index files, user index file, ...)
    elseif (substr_count ($abs_path.$filename, $mgmt_config['abs_path_data']) == 1 && valid_objectname ($user) && is_file ($abs_path.$filename.".@".$user)) 
    {
      $filename = $filename.".@".$user;
      return $filename;
    }
    // file doesn't exist
    else return false;
  }
  else return false;
}  

// ---------------------------------- correctpath -------------------------------------------
// function: correctpath()
// input: path to folder, directory seperator (optional)
// output: correct path/false

function correctpath ($path, $slash="/")
{
  if (valid_locationname ($path) && $slash != "")
  {
    // correct all backslashes
    $path = str_replace ("\\", $slash, $path);
    
    // append $slash at the end of the path
    if (substr ($path, strlen ($path)-1, 1) != $slash)
    {
      $path .= $slash;
    } 
    
    return $path; 
  }
  else return false;
}

// ---------------------------------- convertpath -------------------------------------------
// function: convertpath()
// input: publication, content management path to folder or object, object category ['page, comp']
// output: converted path or URL / false on error

// description:
// This function replaces object pathes of the content management config with %page% and %comp% path variables

function convertpath ($site, $path, $cat)
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  if (@substr_count ($path, "%page%") > 0 || @substr_count ($path, "%comp%") > 0)
  {
    return $path;
  }
  elseif (valid_publicationname ($site) && $path != "" && is_array ($mgmt_config))
  {  
    if (@substr_count ($path, "%page%") == 0 && @substr_count ($path, "%comp%") == 0)
    {
      // load config if not available
      if ((!isset ($mgmt_config[$site]) || !is_array ($mgmt_config[$site])) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
      {
        require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
      }

      // define category if undefined
      if ($cat == "") $cat = getcategory ($site, $path);
      
      // convert path
      if (strtolower ($cat) == "page" && is_array ($mgmt_config[$site])) 
      {
        // URL can be with our without http://domain
        $path_page_url = $mgmt_config[$site]['url_path_page'];
        $path_page_abs = $mgmt_config[$site]['abs_path_page'];
        
        if ($path_page_url[strlen ($path_page_url)-1] == "/") $root_var_url = "%page%/".$site."/";
        else $root_var_url = "%page%/".$site;
        
        if ($path_page_abs[strlen ($path_page_abs)-1] == "/") $root_var_abs = "%page%/".$site."/";
        else $root_var_abs = "%page%/".$site;
        
        // abs path
        if (substr_count ($path, "://") == 0 && substr_count ($path, $path_page_abs) > 0)
        {
          $path = str_replace ($path_page_abs, $root_var_abs, $path);
        }
        else
        {
          // URL
          if ((substr_count ($path, "://") == 1 && substr_count ($path_page_url, "://") == 1) || (substr_count ($path, "://") == 0 && substr_count ($path_page_url, "://") == 0))
          {
            $path = str_replace ($path_page_url, $root_var_url, $path);
          }
          else 
          {
            $path_page_url = cleandomain ($path_page_url);
            $path = cleandomain ($path);
            
            $path = str_replace ($path_page_url, $root_var_url, $path);
          }
        }
      }
      elseif (strtolower ($cat) == "comp") 
      {
        // URL can be with our without http://domain
        $path_comp_url = $mgmt_config['url_path_comp'];
        $path_comp_abs = $mgmt_config['abs_path_comp']; 
        
        if ($path_comp_url[strlen ($path_comp_url)-1] == "/") $root_var_url = "%comp%/";
        else $root_var_url = "%comp%";  
        
        if ($path_comp_abs[strlen ($path_comp_abs)-1] == "/") $root_var_abs = "%comp%/";
        else $root_var_abs = "%comp%";         
      
        // abs. path
        if (substr_count ($path, "://") == 0 && substr_count ($path, $path_comp_abs) > 0)
        {
          $path = str_replace ($path_comp_abs, $root_var_abs, $path);
        }
        else
        {
          // URL
          if ((substr_count ($path, "://") == 1 && substr_count ($path_comp_url, "://") == 1) || (substr_count ($path, "://") == 0 && substr_count ($path_comp_url, "://") == 0))
          {
            $path = str_replace ($path_comp_url, $root_var_url, $path);
          }
          else 
          {
            $path_comp_url = cleandomain ($path_comp_url);
            $path = cleandomain ($path);
            
            $path = str_replace ($path_comp_url, $root_var_url, $path);
          }
        }     
      }
  
      if ($path != "") return $path;
      else return false;
    }
    else return $path;
  }
  else return false;
}

// ---------------------------------- convertlink -------------------------------------------
// function: convertlink()
// input: publication, publication management path to folder or object, object category ['page, comp']
// output: converted path or URL / false on error

// description:
// This function replaces pathes of the publication management config with %page% and %comp% path variables.

function convertlink ($site, $path, $cat)
{
  global $user, $mgmt_config, $publ_config, $hcms_lang, $lang;

  if (valid_publicationname ($site) && $path != "" && is_array ($mgmt_config))
  {  
    if (@substr_count ($path, "%page%") == 0 && @substr_count ($path, "%comp%") == 0 && is_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"))
    {
      // load ini
      $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini");     

      // define category if undefined
      if ($cat == "") $cat = getcategory ($site, $path);
    
      // convert path
      if (strtolower ($cat) == "page") 
      {
        // URL can be with our without http://domain
        $path_page_url = $publ_config['url_publ_page'];
        $path_page_abs = $publ_config['abs_publ_page'];
        
        if ($path_page_url[strlen ($path_page_url)-1] == "/") $root_var_url = "%page%/".$site."/";
        else $root_var_url = "%page%/".$site;
        
        if ($path_page_abs[strlen ($path_page_abs)-1] == "/") $root_var_abs = "%page%/".$site."/";
        else $root_var_abs = "%page%/".$site;
        
        // abs path
        if (substr_count ($path, "://") == 0 && substr_count ($path, $path_page_abs) > 0)
        {
          $path = str_replace ($path_page_abs, $root_var_abs, $path);
        }
        // URL
        else
        {
          if ((substr_count ($path, "://") == 1 && substr_count ($path_page_url, "://") == 1) || (substr_count ($path, "://") == 0 && substr_count ($path_page_url, "://") == 0))
          {
            // convert
            $path = str_replace ($path_page_url, $root_var_url, $path);
          }
          else 
          {
            $path_page_url = cleandomain ($path_page_url);
            $path = cleandomain ($path);
            
            $path = str_replace ($path_page_url, $root_var_url, $path);
          }
        }
      }
      elseif (strtolower ($cat) == "comp") 
      {
        // URL can be with our without http://domain
        $path_comp_url = $publ_config['url_pupl_comp'];
        $path_comp_abs = $publ_config['abs_publ_comp']; 
        
        if ($path_comp_url[strlen ($path_comp_url)-1] == "/") $root_var_url = "%comp%/";
        else $root_var_url = "%comp%";  
        
        if ($path_comp_abs[strlen ($path_comp_abs)-1] == "/") $root_var_abs = "%comp%/";
        else $root_var_abs = "%comp%";         
      
        // abs. path
        if (substr_count ($path, "://") == 0 && substr_count ($path, $path_comp_abs) > 0)
        {
          $path = str_replace ($path_comp_abs, $root_var_abs, $path);
        }
        else
        {
          // URL
          if ((substr_count ($path, "://") == 1 && substr_count ($path_comp_url, "://") == 1) || (substr_count ($path, "://") == 0 && substr_count ($path_comp_url, "://") == 0))
          {
            $path = str_replace ($path_comp_url, $root_var_url, $path);
          }
          else 
          {
            $path_comp_url = cleandomain ($path_comp_url);
            $path = cleandomain ($path);
            
            $path = str_replace ($path_comp_url, $root_var_url, $path);
          }
        }     
      }
  
      if ($path != "") return $path;
      else return false;
    }
    else return $path;
  }
  else return false;
}

// ---------------------------------- deconvertpath -------------------------------------------
// function: deconvertpath ()
// input: string including path to folder or object, convert to file system path or URL [file, url] (optional), transform special characters using specialchr_encode [treu,false] (optional)
// output: deconverted path/false

// description:
// This function replaces all %page% and %comp% path variables with the path of the content management config.
// It converts the path only on content management side not for the publication target.
// It optionally transform special characters as well.

function deconvertpath ($path, $type="file", $specialchr_transform=false)
{
  global $user, $mgmt_config, $hcms_lang, $lang;
  
  // BE AWARE: path could hold template data and therefore valid_publicationname could cause problems!
  if ($path != "" && (strtolower ($type) == "file" || strtolower ($type) == "url") && is_array ($mgmt_config))
  {
    $type = strtolower ($type);
     
    // page and component root variable
    if (@substr_count ($path, "%page%") > 0)
    {
      $root_var = "%page%/";
    }
    elseif (@substr_count ($path, "%comp%") > 0)
    {
      $root_var = "%comp%/";
    }
    else $root_var = false;
  
    
    if ($root_var != false)
    {
      // test if path includes special characters
      if ($specialchr_transform == true && specialchr ($path, ".-_~%") == true)
      {      
        $path = specialchr_encode ($path, "no");
      }
    
      // extract publication from the converted path for page locations (first found path entry only!)
      if (@substr_count ($path, "%page%") > 0 )
      {
        $pos1 = @strpos ($path, $root_var) + strlen ($root_var);
        
        if ($pos1 != false) $pos2 = @strpos ($path, "/", $pos1);
        else $pos2 = false;
        
        if ($pos1 != false && $pos2 != false) $site = @substr ($path, $pos1, $pos2-$pos1);
        else $site = false;
        
        // load publication config if not available
        if (valid_publicationname ($site) && !isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
        {
          require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
        }        
      }
    
      // if absolute file path is reuquested
      if ($type == "file") 
      {   
        // deconvert page locations
        if (@substr_count ($path, "%page%") > 0 && valid_publicationname ($site) && !empty ($mgmt_config[$site]['abs_path_page']))
        {
          if ($mgmt_config[$site]['abs_path_page'][strlen ($mgmt_config[$site]['abs_path_page'])-1] == "/") $root_var = "%page%/".$site."/";
          else $root_var = "%page%/".$site;

          $path = str_replace ($root_var, $mgmt_config[$site]['abs_path_page'], $path);      
        }
              
        // deconvert component locations 
        if (@substr_count ($path, "%comp%") > 0 && !empty ($mgmt_config['abs_path_comp']))
        {
          if ($mgmt_config['abs_path_comp'][strlen ($mgmt_config['abs_path_comp'])-1] == "/") $root_var = "%comp%/";
          else $root_var = "%comp%";
  
          $path = str_replace ($root_var, $mgmt_config['abs_path_comp'], $path);          
        }    
      }
      // if URL is reuquested
      elseif ($type == "url") 
      {
        // deconvert page locations
        if (@substr_count ($path, "%page%") > 0 && valid_publicationname ($site) && !empty ($mgmt_config[$site]['url_path_page']))
        {
          if ($mgmt_config[$site]['url_path_page'][strlen ($mgmt_config[$site]['url_path_page'])-1] == "/") $root_var = "%page%/".$site."/";
          else $root_var = "%page%/".$site;
          
          $path = str_replace ($root_var, $mgmt_config[$site]['url_path_page'], $path);
        }
           
        // deconvert component locations
        if (@substr_count ($path, "%comp%") > 0 && !empty ($mgmt_config['url_path_comp']))
        {
          if ($mgmt_config['url_path_comp'][strlen ($mgmt_config['url_path_comp'])-1] == "/") $root_var = "%comp%/";
          else $root_var = "%comp%";
          
          $path = str_replace ($root_var, $mgmt_config['url_path_comp'], $path);
        }
      }
      
      // return result
      if ($path != "") return $path;
      else return false;      
    }
    // nothing to deconvert
    else return $path;    
  }
  // wrong input
  else return false;
}

// ---------------------------------- deconvertlink -------------------------------------------
// function: deconvertlink ()
// input: path to folder or object, convert to file system path or URL [file, url]
// output: converted absolute link without host/false

// description:
// This function deconverts the path only for the publication target.
// It should be used for page linking, otherwise the function will return the absolute link including the host for component links.

function deconvertlink ($path, $type="url")
{
  global $user, $mgmt_config, $publ_config, $hcms_lang, $lang;
  
  if (valid_locationname ($path) && isset ($mgmt_config) && ($type == "url" || $type == "file"))
  {
    $path = trim ($path);
    
    // extract publication from the converted path (first found path entry only!)
    if (@substr ($path, 0, 6) == "%page%") $root_var = "%page%/";
    elseif (@substr ($path, 0, 6) == "%comp%") $root_var = "%comp%/";
    else $root_var = false;
  
    // get publication from path
    if ($root_var != false)
    {
      $pos1 = @strpos ($path, $root_var) + strlen ($root_var);
      
      if ($pos1 != false) $pos2 = @strpos ($path, "/", $pos1);
      else $pos2 = false;
      
      if ($pos1 != false && $pos2 != false) $site = @substr ($path, $pos1, $pos2-$pos1);
      else $site = false;
    }

    // convert path
    if ($root_var != false && valid_publicationname ($site) && is_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"))
    {
      // load ini
      $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini");      

      if ($root_var == "%page%/")
      {
        if ($publ_config['url_publ_page'][strlen ($publ_config['url_publ_page'])-1] == "/") $root_var = "%page%/".$site."/";
        else $root_var = "%page%/".$site;
        
        // convert
        if ($type == "url") $path = str_replace ($root_var, $publ_config['url_publ_page'], $path);
        elseif ($type == "file") $path = str_replace ($root_var, $publ_config['abs_publ_page'], $path);
        
        // cut of host/domain
        if ($type == "url") $path = cleandomain ($path);
      }      
      elseif ($root_var == "%comp%/")
      {
        if ($publ_config['url_publ_comp'][strlen ($publ_config['url_publ_comp'])-1] == "/") $root_var = "%comp%/";
        else $root_var = "%comp%";
        
        // convert
        if ($type == "url") $path = str_replace ($root_var, $publ_config['url_publ_comp'], $path);
        elseif ($type == "file") $path = str_replace ($root_var, $publ_config['abs_publ_comp'], $path);
      } 
      
      return $path;
    }
    else return $path;    
  }
  else return false;
}

// ---------------------- createviewlink -----------------------------
// function: createviewlink()
// input: publication, media file name, media name (optional), force reload [true,false] (optional), link type [wrapper,download] (optional)
// output: URL for download of the multimedia file / false on error

// description:
// The view link is mainly used inside the system in order to reference and load a multimedia file. 
// The database is not required since the object hash or ID is not needed to create the view link. 

function createviewlink ($site, $mediafile, $name="", $force_reload=false, $type="wrapper")
{
  global $user, $mgmt_config;
  
  // if mediafile is provided as path, extract the media file name
  if ($mediafile != "" && substr_count ($mediafile, "/") > 0) $mediafile = getobject ($mediafile);

  if (isset ($mgmt_config) && valid_publicationname ($site) && valid_objectname ($mediafile))
  {
    if ($force_reload) $add = "&ts=".time();
    else $add = "";
    
    if (strtolower ($type) == "download") $servicename = "mediadownload";
    else $servicename = "mediawrapper";
    
    return $mgmt_config['url_path_cms']."service/".$servicename.".php?site=".urlencode($site)."&media=".urlencode($site."/".$mediafile)."&token=".hcms_crypt ($site."/".$mediafile)."&name=".urlencode($name).$add;
  }
  else return false;
}

// ---------------------- createaccesslink -----------------------------
// function: createaccesslink()
// input: publication, location (optional), object (optional), category [page,comp] (optional), object-ID (optional), user login, link type [al,dl] (optional), token lifetime in seconds (optional), formats (optional)
// output: URL for access to given object / false on error

function createaccesslink ($site, $location="", $object="", $cat="", $object_id="", $login, $type="al", $lifetime=0, $formats="")
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  if (((valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($object) && $cat != "") || $object_id != "") && (($type == "al" && valid_objectname ($login)) || $type == "dl") && isset ($mgmt_config) && $mgmt_config['db_connect_rdbms'] != "")
  {
    // check if object is folder or page/component
    if ($site != "" && $location != "" && $object != "")
    {
      $location = deconvertpath ($location, "file"); 
      
      if (@is_dir ($location.$object))
      {
        $location = $location.$object."/";
        $object = ".folder";
      } 
         
      // get object id
      $objectpath = convertpath ($site, $location.$object, $cat);
      $object_id = rdbms_getobject_id ($objectpath);
    }

    // create link
    if ($object_id != "")
    {
      // crypt object id
      // deprecated since version 5.5.8:
      // $object_id_encrypted = hcms_crypt ($object_id, 3, 12);
      
      // create hash
      $hash = createuniquetoken ();

      // create access link in DB
      $result_db = rdbms_createaccesslink ($hash, $object_id, $type, $login, $lifetime, $formats);
   
      // object link
      // deprecated since version 5.6.1:
      // if ($mgmt_config['secure_links'] == true) return $mgmt_config['url_path_cms']."?hcms_user_token=".hcms_encrypt ($object_id.":".$timetoken."@".$login.":".$password_crypted);
      // else return $mgmt_config['url_path_cms']."?hcms_user=".$login."&hcms_pass=".$password_crypted."&hcms_objref=".$object_id."&hcms_objcode=_".hcms_encrypt ($object_id.":".$timetoken);
      if ($result_db) return $mgmt_config['url_path_cms']."?".$type."=".$hash;
      else return false;
    }
    else
    {
      $errcode = "40911";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|createaccesslink failed due to missing object id for: $objectpath";
      
      savelog (@$error);  
      
      return false;
    }
  }
  else return false;
}

// ---------------------- createwrapperlink -----------------------------
// function: createwrapperlink()
// input: publication (optional), location (optional), object (optional), category [page,comp] (optional), object ID (optional), container-ID (optional)
// output: URL for download of the multimedia file of the given object or folder / false on error

function createwrapperlink ($site="", $location="", $object="", $cat="", $object_id="", $container_id="")
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  if (isset ($mgmt_config) && $mgmt_config['db_connect_rdbms'] != "")
  {
    $object_hash = false;
    
    // check if object is folder or page/component
    if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($object) && $cat != "")
    {
      $location = deconvertpath ($location, "file"); 
      
      if (@is_dir ($location.$object))
      {
        $location = $location.$object."/";
        $object = ".folder";
      } 
      
      $objectpath = convertpath ($site, $location.$object, $cat);
      
      // get object id
      $object_hash = rdbms_getobject_hash ($objectpath);
      
      // try to recreate object entry in database
      if ($object_hash == false)
      {
        $object_info = getobjectinfo ($site, $location, $object, $user);
        
        if (!empty ($object_info['container_id']) && !empty ($object_info['template']))
        {
          $container_id = $object_info['container_id'];
          rdbms_createobject ($object_info['container_id'], $objectpath, $object_info['template'], $object_info['content'], $user);
          
          // get object id
          $object_hash = rdbms_getobject_hash ($objectpath);
        }
      }
    }
    // if object id
    elseif ($object_id != "")
    {
      $object_hash = rdbms_getobject_hash ($object_id);
    }
    // if container id
    elseif ($container_id != "")
    {
      $object_hash = rdbms_getobject_hash ("", $container_id);
    }

    if ($object_hash != false)
    {  
      // object link
      // deprecated since version 5.5.8: return $mgmt_config['url_path_cms']."explorer_download.php?hcms_objref=".$object_id."&hcms_objcode=".hcms_crypt ($object_id, 3, 12);
      // deprecated since version 5.6.1: 
      // if ($mgmt_config['secure_links'] == true) return $mgmt_config['url_path_cms']."?hcms_id_token=".hcms_encrypt ($object_id.":".$timetoken);
      // else return $mgmt_config['url_path_cms']."?hcms_objid=".$object_id."&hcms_token=_".hcms_encrypt ($object_id.":".$timetoken);
      return $mgmt_config['url_path_cms']."?wl=".$object_hash;
      
    }
    else
    {
      $errcode = "40912";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|createwrapperlink failed due to missing object id for: $objectpath";
      
      savelog (@$error);  
      
      return false;
    }
  }
  else return false;
}

// ---------------------- createdownloadlink -----------------------------
// function: createdownloadlink()
// input: publication name (optional), location (optional), object (optional), category [page,comp] (optional), object ID (optional), container-ID (optional)
// output: URL for download of the multimedia file of the given object or folder / false on error

function createdownloadlink ($site="", $location="", $object="", $cat="", $object_id="", $container_id="")
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  if (isset ($mgmt_config) && $mgmt_config['db_connect_rdbms'] != "")
  {
    $object_hash = false;
    
    // check if object is folder or page/component
    if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($object) && $cat != "")
    {
      $location = deconvertpath ($location, "file"); 
      
      if (@is_dir ($location.$object))
      {
        $location = $location.$object."/";
        $object = ".folder";
      } 

      $objectpath = convertpath ($site, $location.$object, $cat);
      
      // get object id
      $object_hash = rdbms_getobject_hash ($objectpath);
      
      // try to recreate object entry in database
      if ($object_hash == false)
      {
        $object_info = getobjectinfo ($site, $location, $object, $user);
        
        if (!empty ($object_info['container_id']) && !empty ($object_info['template']))
        {
          $container_id = $object_info['container_id'];
          rdbms_createobject ($object_info['container_id'], $objectpath, $object_info['template'], $object_info['content'], $user);
          
          // get object id
          $object_hash = rdbms_getobject_hash ($objectpath);
        }
      }
    }
    // if object id
    elseif ($object_id != "")
    {
      $object_hash = rdbms_getobject_hash ($object_id);
    }
    // if container id
    elseif ($container_id != "")
    {
      $object_hash = rdbms_getobject_hash ("", $container_id);
    }
    
    if ($object_hash != false)
    {
      // object link
      // deprecated since version 5.5.8
      // return $mgmt_config['url_path_cms']."explorer_download.php?hcms_objref=".$object_id."&hcms_objcode=".hcms_crypt ($object_id, 3, 12);
      // deprecated since version 5.6.1:
      // if ($mgmt_config['secure_links'] == true) return $mgmt_config['url_path_cms']."?type=dl&hcms_id_token=".hcms_encrypt ($object_id.":".$timetoken);
      // else return $mgmt_config['url_path_cms']."?type=dl&hcms_objid=".$object_id."&hcms_token=_".hcms_encrypt ($object_id.":".$timetoken);
      return $mgmt_config['url_path_cms']."?dl=".$object_hash;
    }
    else
    {
      $errcode = "40912";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|createdownloadlink failed due to missing object id for: $objectpath";
      
      savelog (@$error);  
      
      return false;
    }
  }
  else return false;
}

// --------------------------------------- createmultidownloadlink -------------------------------------------
// function: createmultidownloadlink ()
// input: publication name, multiobject string (optional), media file name (optional), location (optional), presentation name (optional), user name, conversion type (format, e.g: jpg), media configuration used for conversion (e.g.: 1024x768px), link type [wrapper,download] (optional)
// output: download link / false on error

// description:
// Generates a download link of a single media file, folder or multi objects.
// Priority if multiple input parameters for media file, folder or multi objects are given:
// 1st...multi objects
// 2nd...media file
// 3rd...folder

function createmultidownloadlink ($site, $multiobject="", $media="", $location="", $name="", $user, $type="", $mediacfg="", $linktype="download")
{
  global $mgmt_config, $mgmt_compress, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $globalpermission, $setlocalpermission, $hcms_lang, $lang;
  
  if (valid_publicationname ($site) && valid_objectname ($user) && (valid_locationname ($location) || valid_locationname ($multiobject) || valid_objectname ($media)))
  {
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";      
    
    // download zip-file for multiobjects
    if ($multiobject != "" && substr_count ($multiobject, "|") > 1)
    {
      // unique name for zip-file to download
      $zip_filename = uniqid ("tmp");
  
      // temp directory holding the zip-file
      $mediadir = $mgmt_config['abs_path_temp'];
  
      // generate temp dir
      if (!is_dir ($mediadir)) mkdir ($mediadir, $mgmt_config['fspermission'], true);
  
      // split multiobject into array
      if ($multiobject != "") $multiobject_array = link_db_getobject ($multiobject);
     
      // zip files
      $result_zip = zipfiles ($site, $multiobject_array, $mediadir, $zip_filename, $user);
      
      if ($location == "") $location = $multiobject_array[0];
      
      $media = $zip_filename.".zip";
      
      if ($location != "")
      {
        $media_info = getfileinfo ($site, getobject ($location).".zip", "comp");
        $name = $media_info['name'];
      }
      else $name = "Download.zip";
    }
    // for a single file
    elseif ($media != "")
    {
      $result_zip = true;
      // multimedia file (publication/file)
      $media = $site."/".$media;
    }
    // download zip-file for single folder
    elseif (is_dir ($location))
    {
      // unique name for zip-file to download
      $zip_filename = uniqid ("tmp");
  
      // temp directory holding the zip-file
      $mediadir = $mgmt_config['abs_path_temp'];
  
      // generate temp dir
      if (!is_dir ($mediadir)) mkdir ($mediadir, $mgmt_config['fspermission'], true);
  
      // set multiobject array
      $multiobject_array[0] = $location;
     
      $result_zip = zipfiles ($site, $multiobject_array, $mediadir, $zip_filename, $user);
      
      $media = $zip_filename.".zip";
      $media_info = getfileinfo ($site, getobject ($location).".zip", "comp");
      $name = $media_info['name'];
    }    
    
    $add = "";    
    if ($type) $add .= '&type='.url_encode($type);
    if ($mediacfg) $add .= '&mediacfg='.url_encode($mediacfg);
    
    // return result
    if ($media != "" && $result_zip) return createviewlink ($site, $media, $name, "", $linktype).$add;
    else return false;
  }
  else return false;
}

// ---------------------- cleandomain -----------------------------
// function: cleandomain()
// input: string to clean from http(s)://domain
// output: cleanded string / false on error

// description:
// Returns the URL notation without the protocoll://domain.

function cleandomain ($path)
{
  global $mgmt_config, $hcms_lang, $lang;
  
  if ($path != "")
  {
    if (substr_count ($path, "://") == 1 && substr_count ($path, "/") > 2) $path = substr ($path, strpos ($path, "/", 9));

    if ($path != "") return $path;
    else return false;
  }
  else return false;
}  

// ======================================= VERSIONING ==========================================

// -------------------------------------- fileversion -------------------------------------------
// function: fileversion()
// input: file name [string]
// output: versioned file name [string] / false on error

// description:
// Creates a version file name

function fileversion ($file)
{
  if (valid_objectname ($file))
  {
    // get local date today (jjjj-mm-dd)
    $versiondate = date ("Y-m-d_H-i-s", time());
  
    // create file name
    $file_v = $file.".v_".$versiondate;
  
    return $file_v;
  }
  else return false;
}

// -------------------------------------- createversion -------------------------------------------
// function: createversion()
// input: publication name, media file name or container name, user name (optional)
// output: true / false

// description:
// Creates a new version of a multimedia file and container

function createversion ($site, $file, $user="sys")
{
  global $mgmt_config, $mgmt_mediaoptions, $mgmt_docoptions, $hcms_ext, $user;

  if (valid_publicationname ($site) && valid_objectname ($file))
  {
    // create version of previous content file and media file (not for thumbnails)
    if ((empty ($mgmt_config['contentversions']) || $mgmt_config['contentversions'] == true) && !is_thumbnail ($file, false))
    {
      // try to get container ID from multimedia file
      if ($container_id = getmediacontainerid ($file))
      {
        $media_root = getmedialocation ($site, $file, "abs_path_media").$site."/";

        // verify media file
        if (!is_cloudobject ($media_root.$file) && !is_file ($media_root.$file)) return true;
      
        $file_info = getfileinfo ($site, $file, "comp");
        
        // create new version of file name
        $file_v = fileversion ($file);
        
        // get the file extension of the version file
        $ext_v = strrchr ($file_v, ".");
          
        // create thumbnail
        $thumb = $file_info['filename'].".thumb.jpg";
        $thumb_v = $thumb.$ext_v;
        
        $thumb_root = getmedialocation ($site, $thumb, "abs_path_media").$site."/";

        // create new version of thumbnail file
        // move thumbnail (important for versioning!)  
        if (is_file ($thumb_root.$thumb) && filesize ($thumb_root.$thumb) > 0)
        {
          @rename ($thumb_root.$thumb, $thumb_root.$thumb_v);
        }

        // rename in cloud storage
        if (function_exists ("renamecloudobject")) renamecloudobject ($site, $thumb_root, $thumb, $thumb_v, $user);
        
        // create new version of original file
        // copy media file (important for image editing!)
        if (is_file ($media_root.$file) && filesize ($media_root.$file) > 0)
        {
          // copy to media repository in case media file has been exported
          @copy ($media_root.$file, $thumb_root.$file_v);
        }

        // copy in cloud storage
        if (function_exists ("copycloudobject")) copycloudobject ($site, $thumb_root, $file, $file_v, $user);
        
        // delete all other media file derivatives (individual or videplayer thumbnail files)
        deletemediafiles ($site, $file, false);
            
        // create new version of container and keep source container file as well
        $contentlocation = getcontentlocation ($container_id, 'abs_path_content');
        
        // get working container name
        $containerinfo = getcontainername ($container_id);
        $contentfile_wrk = $containerinfo['container'];

        if (is_file ($contentlocation.$contentfile_wrk) && filesize ($contentlocation.$contentfile_wrk) > 0)
        {
          return @copy ($contentlocation.$contentfile_wrk, $contentlocation.$file_v);
        }
        else return false;
      }
      // try container
      elseif (intval ($file) > 0 || strpos ($file, ".xml") > 0)
      {
        // get container ID
        if (strpos ($file, ".xml") > 0)
        {
          $container_id = substr ($file, 0, strpos ($file, ".xml"));
        }
        else
        {
          $container_id = $file;
        }

        // create new version of file name
        $file_v = fileversion ($container_id.".xml");

        // create new version of container
        $contentlocation = getcontentlocation ($container_id, 'abs_path_content');
        
        // get working container name
        $containerinfo = getcontainername ($container_id);
        $contentfile_wrk = $containerinfo['container'];

        if (is_file ($contentlocation.$contentfile_wrk) && filesize ($contentlocation.$contentfile_wrk) > 0)
        {
          return @copy ($contentlocation.$contentfile_wrk, $contentlocation.$file_v);
        }
        else return false;
      }
      else return false;
    }
    // versioning is disabled
    else return true;
  }
  else return false;
}

// -------------------------------------- rollbackversion -------------------------------------------
// function: rollbackversion()
// input: publication name, location, object name, container version name, user name (optional)
// output: result_array

// description:
// Makes an older object version to the current version

function rollbackversion ($site, $location, $page, $container_version, $user="sys")
{
  global $mgmt_config, $mgmt_mediaoptions, $mgmt_docoptions, $hcms_ext, $hcms_lang, $lang;
  
  $result = array();
  $result['result'] = false;
  $result['publication'] = $site;
  $result['location'] = $location;
  $result['object'] = $page;
  $result['container_id'] = "";
  $result['message'] = "";
  
  if (empty ($lang)) $lang = "en";

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($container_version) && strpos ($container_version, ".v_") > 0)
  {
    $cat = getcategory ($site, $location);
  
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";  
    
    // convert location
    $location = deconvertpath ($location, "file");
    
    // read actual object info (to get associated content)
    $objectinfo = getobjectinfo ($site, $location, $page);
    
    if (is_array ($objectinfo))
    {
      $contentfile = $objectinfo['content'];
      $container_id = $objectinfo['container_id'];
      $mediafile = $objectinfo['media'];
      
      // get content container location
      $versiondir = getcontentlocation ($container_id, 'abs_path_content');
      
      // get media file locations
      if ($mediafile != "")
      {
        $mediadir = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";
        $thumbdir = getmedialocation ($site, "dummy.".$mediafile, "abs_path_media").$site."/";
      }
      
      // change version
      if ($versiondir != "")
      {
        // STEP 1
        // create new version of current/last object version 
        if ($mediafile != "" && preg_match ("/_hcm".$container_id."./i", $container_version))
        {
          // create version of current container and media file
          $createversion = createversion ($site, $mediafile, $user);
          
          // remove original files (that will be kept by function createversion for image editing)
          if (!empty ($createversion)) deletefile ($thumbdir, $mediafile, 0);
        }
        else
        {
          // create version of current container
          $createversion = createversion ($site, $container_id, $user);
        }
        
        // STEP 2
        // make the selected older version the actual/latest version
        if (!empty ($createversion))
        {  
          // load working container
          $getcontainername = getcontainername ($container_id);
          $contentfile_wrk = $getcontainername['container'];
          $tempdata = loadcontainer ($contentfile_wrk, "work", $user);  
  
          // get current objects
          if ($tempdata != false) $contentobjects = getcontent ($tempdata, "<contentobjects>");
          
          // make old container the last container version
          $rename_2 = @rename ($versiondir.$container_version, $versiondir.$contentfile);
          $copy_2 = @copy ($versiondir.$contentfile, $versiondir.$contentfile_wrk);

          // change media file version
          if ($rename_2 != false && $mediafile != "" && @preg_match ("/_hcm".$container_id."./i", $container_version))
          {
            // get file extension
            $ext_version = strrchr ($container_version, ".");
          
            // get media file name from container version
            $mediafile_current = substr ($container_version, 0, strrpos ($container_version, "."));
            
            // get media file extension
            $ext_current = strrchr ($mediafile_current, ".");
            
            // move media file to media repository in case media file has been exported
            $rename_2 = @rename ($mediadir.$container_version, $thumbdir.$mediafile_current);
        
            // remove symbolic link (if exported media file)
            if (is_link ($thumbdir.$container_version)) deletefile ($thumbdir, $container_version, 0);

            // rename in cloud storage
            if (function_exists ("renamecloudobject")) renamecloudobject ($site, $thumbdir, $container_version, $mediafile_current, $user);  
            
            // thumbnail
            $thumbnail = substr ($mediafile_current, 0, strrpos ($mediafile_current, ".")).".thumb.jpg";
            $thumbnail_version = $thumbnail.$ext_version;
    
            // rename old version to last version of thumbnail file
            if (is_file ($thumbdir.$thumbnail_version) && filesize ($thumbdir.$thumbnail_version) > 0)
            {
              @rename ($thumbdir.$thumbnail_version, $thumbdir.$thumbnail);
            }
    
            // rename in cloud storage
            if (function_exists ("renamecloudobject")) renamecloudobject ($site, $thumbdir, $thumbnail_version, $thumbnail, $user);
            
            // create preview (thumbnail for images, previews for video/audio files)
            createmedia ($site, $thumbdir, $thumbdir, $mediafile_current, "", "origthumb");
            // reindex
            indexcontent ($site, $thumbdir, $mediafile_current, $container_id, "", $user);
          }
          
          // load working container of restored version
          $tempdata = loadcontainer ($contentfile_wrk, "work", $user); 
  
          if ($tempdata != false) 
          {
            // insert new object into content container
            $tempdata = setcontent ($tempdata, "<hyperCMS>", "<contentobjects>", $contentobjects[0], "", "");
              
            // save working container 
            $test = savecontainer ($contentfile_wrk, "work", $tempdata, $user);
          }
          else
          {
            $errcode = "10100";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|savecontainer failed for container ".$contentfile_wrk;           
          }
          
          // rename object if file extension has been changed
          if (!empty ($ext_current) && !empty ($ext_version) && $ext_current != $ext_version)
          {
             // write new reference in object file
            $filedata = loadfile ($location, $page);
            if ($filedata != false) $filedata = setfilename ($filedata, "media", $mediafile_current);

            if ($filedata != false)
            {
              $result_save = savefile ($location, $page, $filedata);
              // remote client
              remoteclient ("save", "abs_path_".$cat, $site, $location, "", $page, "");
            }
            else $result_save = false;

            // on success
            if ($result_save == true)
            {
              // get object name without extension
              $page_nameonly = substr ($page, 0, strrpos ($page, "."));
              
              // rename media object, if file extension has changed
              $result_rename = renameobject ($site, $location, $page, $page_nameonly, $user);

              if ($result_rename['result'] == true)
              {
                // set new page name
                $page = $page_nameonly.$ext_current;
                $result['object'] = $page;
              }
              // on error
              else
              {
                $show = $hcms_lang['the-file-could-not-be-renamed'][$lang]."\n";
              }
            }
          }
  
          if (empty ($rename_2) || empty ($copy_2))
          {
            $show = "<p class=hcmsHeadline>".getescapedtext ($hcms_lang['could-not-change-version'][$lang])."</p>\n".getescapedtext ($hcms_lang['file-is-missing-or-you-do-not-have-write-permissions'][$lang])."<br /><br />\n";
          }
        }
        else
        {
          $show = "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['could-not-change-version'][$lang])."</p>\n".getescapedtext ($hcms_lang['file-is-missing-or-you-do-not-have-write-permissions'][$lang])."<br /><br />\n";
        }
      }
    }
  }

  // write log
  savelog (@$error);
  
  // return result
  if (empty ($show)) $result['result'] = true;
  else $result['message'] = $show;
  
  return $result;
}

// -------------------------------------- deleteversion -------------------------------------------
// function: deleteversion()
// input: publication name, location, object name, container version name, user name (optional)
// output: true / false

// description:
// Removes the version of an object

function deleteversion ($site, $container_version, $user="sys")
{
  global $mgmt_config;

  if (valid_publicationname ($site) && valid_objectname ($container_version) && strpos ($container_version, ".v_") > 0)
  {
    // get container ID
    if (strpos ($container_version, "_hcm") > 0) $container_id = getmediacontainerid ($container_version);
    elseif (strpos ($container_version, ".xml") > 0) $container_id = substr ($container_version, 0, strpos ($container_version, ".xml"));
  
    if (intval ($container_id) > 0)
    {
      // get locations
      $versiondir = getcontentlocation ($container_id, 'abs_path_content');
      $thumbdir = getmedialocation ($site, "dummy.".$container_version, "abs_path_media").$site."/";
      $mediadir = getmedialocation ($site, $container_version, "abs_path_media").$site."/";
      
      // delete media file version
      if (is_file ($mediadir.$container_version) || is_cloudobject ($container_version))
      {
        // delete media file
        if (is_file ($mediadir.$container_version)) $media_result = deletefile ($mediadir, $container_version, 0);

        // delete symbolic link to media file (if exported file)
        if (is_link ($thumbdir.$container_version)) deletefile ($thumbdir, $container_version, 0);
        
        // cloud storage
        if (function_exists ("deletecloudobject")) deletecloudobject ($site, $thumbdir, $container_version, $user);
      }
      else $media_result = true;
      
      // delete thumbnail file
      $fileinfo = getfileinfo ($site, $container_version, "comp");
      $thumbnail = $fileinfo['filename'].".thumb.jpg".strrchr ($container_version, ".");
        
      if (is_file ($thumbdir.$thumbnail))
      {
        $thumbnail_result = deletefile ($thumbdir, $thumbnail, 0);
        
        // cloud storage
        if (function_exists ("deletecloudobject")) deletecloudobject ($site, $thumbdir, $thumbnail, $user);
      }
      else $thumbnail_result = true;

      // delete container version
      if ($media_result == true && $thumbnail_result == true && is_file ($versiondir.$container_version))
      {
        // delete media file
        return deletefile ($versiondir, $container_version, 0);
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// --------------------------------------- deleteversions -------------------------------------------
// function: deleteversions()
// input: type [content, template] or valid path in filesystem, report [yes,no], user name (optional)
// output: true [report=no] or report [report=yes], false on error

// description:
// Removes all versions of all objects or templates

function deleteversions ($type, $report, $user="sys")
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  if (strtolower ($type) == "content") $versiondir = $mgmt_config['abs_path_content'];
  elseif (strtolower ($type) == "template") $versiondir = $mgmt_config['abs_path_template'];
  elseif ($type != "" && is_dir ($type)) $versiondir = $type;
  else return false; 
  
  $versionhandler = opendir ($versiondir);
  
  if ($versionhandler != false)
  {    
    while ($entry = readdir ($versionhandler))
    {
      // content container directory
      if ($entry != "." && $entry != ".." && is_dir ($versiondir.$entry))
      {
        $report_str = deleteversions ($versiondir.$entry."/", $report, $user);
      }
      // suitable for templates and containers
      elseif ($entry != "." && $entry != ".." && is_file ($versiondir.$entry) && (preg_match ("/.v_/i", $entry) || @preg_match ("/_hcm/i", $entry)))
      {
        // remove container and media file version
        if (@preg_match ("/_hcm/i", $entry))
        {
          $entrydata = loadfile ($versiondir, $entry);
          
          if ($entrydata != "")
          {
            $contentobjects = getcontent ($entrydata, "<contentobjects>");
            
            if (is_array ($contentobjects))
            {
              $site = getpublication ($contentobjects[0]);

              if (valid_publicationname ($site))
              {
                $test = deleteversion ($site, $entry, $user);
              }
            }
          }
        }
        // remove template or other version
        else
        {
          $test = deletefile ($versiondir, $entry, 0);
        }
        
        // report  
        if (strtolower ($report) == "yes") 
        {
          if (empty ($test)) $report_str .= "failed to delete ".$entry."<br />\n";
          else $report_str .= "deleted ".$entry." successfully<br />\n";          
        }
      }
    }
    
    closedir ($versionhandler);
    
    if ($report_str != false && $report == "yes") return $report_str;
    elseif ($report_str != false) return true;
    else return false;
  }
  else return false;
}


// ========================================== FILE OPERATION =======================================

// description:
// loadfile and savefile function load and save files without locking them.
// loadfile will wait 10 seconds for loading locked files.
// loadlockfile and savelockfile includes a locking mechanismen for files.
// every time you want to lock a file during your operations use loadlockfile.
// it is important to use savelockfile to save and unlock the file again.
// savelockfile requires the file to be opened by loadlockfile before.
// deletefile removes files and empty directories.
// appendfile appends the given content at the end of the file content.

// ------------------------------------------- loadfile_header -------------------------------------------
// function: loadfile_header()
// input: path to file, file name 
// output: file content

function loadfile_header ($abs_path, $filename)
{
  global $user, $mgmt_config, $hcms_lang, $lang;
  
  $filedata = false;
  
  // check and correct file
  if (valid_locationname ($abs_path) && valid_objectname ($filename))
  {
    $headersize = 2048;
    
    // add slash if not present at the end of the location string
    if (substr ($abs_path, -1) != "/") $abs_path = $abs_path."/";  
    
    // deconvert path
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }
    
    // check and correct file
    $filename = correctfile ($abs_path, $filename, $user);
    
    // get file size
    $filesize = filesize ($abs_path.$filename);
    
    // compare filesize with headersize
    if ($filesize < $headersize) $headersize = $filesize;
    
    // load header
    if (is_file ($abs_path.$filename))
    {
      $filehandle = @fopen ($abs_path.$filename, "rb");

      if ($filename != false)
      {
        if ($filehandle != false)
        {
          $filedata = @fread ($filehandle, 2048);
          @fclose ($filehandle);
        }
      }
    }
  } 
  
  return $filedata;
}

// ------------------------------------------- loadfile_fast -------------------------------------------
// function: loadfile_fast()
// input: path to file, file name 
// output: file content

// description:
// This functions is identical to loadfile, but it does not wait for locked files to be unlocked again.
// It should only be used on files that won't be locked by the system. It is therefore recommended to use loadfile.

function loadfile_fast ($abs_path, $filename)
{
  global $user, $mgmt_config, $hcms_lang, $lang;
  
  $filedata = false;

  // check and correct file
  if (valid_locationname ($abs_path) && valid_objectname ($filename))
  {    
    // add slash if not present at the end of the location string
    if (substr ($abs_path, -1) != "/") $abs_path = $abs_path."/";  
      
    // deconvert path
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }
    
    // check and correct file
    $filename = correctfile ($abs_path, $filename, $user);    
    
    // load file
    if ($filename != false)
    {   
      $filehandle = fopen ($abs_path.$filename, "rb");
  
      if ($filehandle != false)
      {
        @flock ($filehandle, LOCK_EX);
        $filedata = @fread ($filehandle, filesize ($abs_path.$filename));
        @flock ($filehandle, LOCK_UN);
        @fclose ($filehandle);
      }
    }   
  }
  
  return $filedata;
}

// ------------------------------------------- loadfile -------------------------------------------
// function: loadfile()
// input: path to file, file name 
// output: file content

// description:
// This function loads a file and waits up to 3 seconds for locked files to be unlocked

function loadfile ($abs_path, $filename)
{
  global $user, $mgmt_config, $hcms_lang, $lang;
  
  $filedata = false;

  if (valid_locationname ($abs_path) && valid_objectname ($filename))
  {
    // add slash if not present at the end of the location string
    if (substr ($abs_path, -1) != "/") $abs_path = $abs_path."/";  
    
    // deconvert path
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }
    
    // check and correct file
    $filename_unlocked = $filename;
    $filename = correctfile ($abs_path, $filename, $user);
     
    // load file
    if ($filename != false)
    {    
      $filehandle = fopen ($abs_path.$filename, "rb");

      if ($filehandle != false)
      {
        @flock ($filehandle, LOCK_EX);
        $filedata = @fread ($filehandle, filesize ($abs_path.$filename));
        @flock ($filehandle, LOCK_UN);
        @fclose ($filehandle);
      }
    }    
    // if file is locked by other user or system, wait 3 seconds
    elseif ($filename_unlocked != ".folder")
    {
      // set time stamp (now + 3 sec)
      $end = time() + 3;
  
      while (time() <= $end)
      {
        $filename = $filename_unlocked;
        $filename = correctfile ($abs_path, $filename, $user);
        
        if (is_file ($abs_path.$filename) || is_file ($abs_path.$filename.".off"))
        {
          // if file is offline
          if (is_file ($abs_path.$filename.".off")) $filename = $filename.".off";    
                  
          $filehandle = @fopen ($abs_path.$filename, "rb");
  
          if ($filehandle != false)
          {
            @flock ($filehandle, LOCK_EX);
            $filedata = @fread ($filehandle, filesize ($abs_path.$filename));
            @flock ($filehandle, LOCK_UN);
            @fclose ($filehandle);
            
            break;
          }
        }
        // sleep for 0 - 100 milliseconds, to avoid collision and CPU load
        else usleep (round (rand (0, 100) * 1000));
      }
    }
  }

  return $filedata;
}

// ---------------------------------------- loadlockfile ---------------------------------------------
// function: loadlockfile()
// input: user, path to file, file name, force unlock of file after x seconds [integer]
// output: file content

// description:
// This function loads and locks a file for a sepecific user. It waits up to 3 seconds for locked files to be unlocked.

function loadlockfile ($user, $abs_path, $filename, $force_unlock=3)
{
  global $mgmt_config, $hcms_lang, $lang;
  
  $filedata = false;

  if (valid_objectname ($user) && valid_locationname ($abs_path) && valid_objectname ($filename))
  {
    // add slash if not present at the end of the location string
    if (substr ($abs_path, -1) != "/") $abs_path = $abs_path."/";   
    
    // deconvert path
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }
    
    // check and correct file name
    if (substr_count ($filename, ".@".$user) == 1) $filename = str_replace (".@".$user, "", $filename);
    $filename_unlocked = $filename;
    $filename = correctfile ($abs_path, $filename, $user);
    
    // if file exists
    if ($filename != false)
    {
      // if file is not locked by the user
      if (!is_file ($abs_path.$filename_unlocked.".@".$user))
      {
        // lock file
        $locked = rename ($abs_path.$filename, $abs_path.$filename.".@".$user);
        $filename = $filename.".@".$user;
      }
      else $locked = true;
      
      // if file is locked
      if ($locked == true)
      {
        $filehandle = @fopen ($abs_path.$filename, "rb");
    
        if ($filehandle != false)
        {
          @flock ($filehandle, LOCK_EX);
          $filedata = @fread ($filehandle, filesize ($abs_path.$filename));
          @flock ($filehandle, LOCK_UN);
          @fclose ($filehandle);
        }
        else
        {
          // unlock file
          rename ($abs_path.$filename, $abs_path.$filename_unlocked);
        }
      }
    }
    // if file is locked by other user
    elseif ($filename_unlocked != ".folder")
    {
      // set default end time stamp for loading (now + 3 sec)
      if ($force_unlock > 0 && is_int ($force_unlock)) $timestamp = $force_unlock;
      else $timestamp = 3;
      
      $end = time() + $timestamp;
      $found = false;
  
      // try to load file
      while (time() <= $end)
      {
        $filename = $filename_unlocked;
        $filename = correctfile ($abs_path, $filename, $user);  
          
        if ($filename != false)
        {
          // if file is not locked by the user
          if (!is_file ($abs_path.$filename_unlocked.".@".$user))
          {
            // lock file
            $locked = rename ($abs_path.$filename, $abs_path.$filename.".@".$user);
            $filename = $filename.".@".$user;
          }
          else $locked = true;
          
          // if file is locked
          if ($locked == true)
          {  
            $filehandle = @fopen ($abs_path.$filename, "rb");
            
            // file can be loaded   
            if ($filehandle != false)
            {
              @flock ($filehandle, LOCK_EX);
              $filedata = @fread ($filehandle, filesize ($abs_path.$filename));
              @flock ($filehandle, LOCK_UN);
              @fclose ($filehandle);
              
              break; 
            }
            // file can not be loaded
            else
            {
              // unlock file
              rename ($abs_path.$filename, $abs_path.$filename_unlocked);
            }
          }
        }
        // sleep for 0 - 100 milliseconds, to avoid collision and CPU load
        else usleep (round (rand (0, 100) * 1000));
      }
      
      // force unlock
      if ($force_unlock > 0)
      {
        $file_info = getlockedfileinfo ($abs_path, $filename_unlocked);

        if (is_array ($file_info))
        {
          // unlock file
          $result_rename = rename ($abs_path.$file_info['file'], $abs_path.$filename_unlocked);
          
          // load file
          if ($result_rename)
          {
            // lock and load file
            $filedata = loadlockfile ($user, $abs_path, $filename_unlocked);
          }
        }
      }
    }
  }

  return $filedata; 
}

// --------------------------------------- savefile ------------------------------------------------
// function: savefile()
// input: path to file, file name, file content 
// output: true/false

// description:
// This function saves content to a file

function savefile ($abs_path, $filename, $filedata)
{
  global $user, $mgmt_config, $hcms_lang, $lang;
  
  if (valid_locationname ($abs_path) && valid_objectname ($filename))
  {
    // add slash if not present at the end of the location string
    if (substr ($abs_path, -1) != "/") $abs_path = $abs_path."/";   
      
    // deconvert path
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }

    // if file is locked by the same user file can be saved
    if (is_file ($abs_path.$filename.".@".$user)) $filename = $filename.".@".$user;
    // if file is offline
    elseif (is_file ($abs_path.$filename.".off")) $filename = $filename.".off";

    $filehandle = fopen ($abs_path.$filename, "wb");
  
    if ($filehandle != false)
    {    
      @flock ($filehandle, LOCK_EX);
      @fwrite ($filehandle, $filedata);  
      @flock ($filehandle, LOCK_UN);  
      @fclose ($filehandle);

      return true;
    }
    else return false;
  }
  else return false;
}

// ------------------------------------ savelockfile --------------------------------------------
// function: savelockfile()
// input: user, path to file, file name, file content 
// output: true/false

// description:
// Saves content to a locked file. It requires the file to be opened by loadlockfile.

function savelockfile ($user, $abs_path, $filename, $filedata)
{
  global $mgmt_config, $hcms_lang, $lang;
  
  if (valid_objectname ($user) && valid_locationname ($abs_path) && valid_objectname ($filename))
  {
    // add slash if not present at the end of the location string
    if (substr ($abs_path, -1) != "/") $abs_path = $abs_path."/";   
    
    // deconvert path 
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }
    
    // check and define unlocked file name
    if (substr_count ($filename, ".@".$user) == 1)
    {
      $filename_unlocked = str_replace (".@".$user, "", $filename);
    }
    else
    {
      $filename_unlocked = $filename;
      $filename = $filename.".@".$user;
    }
    
    // if locked file exists
    if (is_file ($abs_path.$filename))
    {
      $filehandle = fopen ($abs_path.$filename, "wb");

      if ($filehandle != false)
      {
        @flock ($filehandle, LOCK_EX);
        @fwrite ($filehandle, $filedata);        
        @flock ($filehandle, LOCK_UN);
        @fclose ($filehandle);
      }
      else return false;

      // unlock file
      rename ($abs_path.$filename, $abs_path.$filename_unlocked);

      return true;
    }
    else return false;
  }
  else return false;
}

// -------------------------------------- appendfile -----------------------------------------
// function: appendfile()
// input: path to file, file name, file content 
// output: true/false

// description: 
// Appends data to a file but cannot create a new file!
// Waits up to 3 seconds for locked files to be unlocked again.
// Files won't be unlocked if the file is already locked.

function appendfile ($abs_path, $filename, $filedata)
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  if (valid_locationname ($abs_path) && valid_objectname ($filename) && $filedata != "")
  {
    // add slash if not present at the end of the location string
    if (substr ($abs_path, -1) != "/") $abs_path = $abs_path."/";  
    
    // deconvert path 
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }
    
    // check and correct file
    $filename_unlocked = $filename;
    $filename_test = correctfile ($abs_path, $filename, $user);     
    if ($filename_test != false) $filename = $filename_test; 
       
    // if file exists
    if (is_file ($abs_path.$filename))
    {      
      $filehandle = fopen ($abs_path.$filename, "a");
  
      if ($filehandle != false)
      {
        @flock ($filehandle, LOCK_EX);     
        @fwrite ($filehandle, $filedata);    
        @flock ($filehandle, LOCK_UN);
        @fclose ($filehandle);
               
        return true;
      }
      else return false;
    }
    // if file is locked by other user or system, wait 3 seconds
    elseif ($filename_unlocked != ".folder")
    {
      // set time stamp (now + 3 sec)
      $end = time() + 3;
  
      while (time() <= $end)
      {
        $filename = $filename_unlocked;
        $filename = correctfile ($abs_path, $filename, $user);
    
        if ($filename != false)
        {   
          $filehandle = fopen ($abs_path.$filename, "a");
              
          if ($filehandle != false)
          {
            @flock ($filehandle, LOCK_EX);
            @fwrite ($filehandle, $filedata);
            @flock ($filehandle, LOCK_UN);
            @fclose ($filehandle);
                             
            return true;
          }
          else return false;
        }
        // sleep for 0 - 100 milliseconds, to avoid collision and CPU load
        else usleep (round (rand (0, 100) * 1000));
      }
    }
    else return false;
  }
  else return false;
}

// ------------------------------------------- lockfile --------------------------------------------
// function: lockfile()
// input: user, path to file, file name
// output: true/false

// description:
// This functions lockes a file for a specific user

function lockfile ($user, $abs_path, $filename)
{
  global $mgmt_config, $hcms_lang, $lang;
  
  if (valid_objectname ($user) && valid_locationname ($abs_path) && valid_objectname ($filename))
  {
    // add slash if not present at the end of the location string
    if (substr ($abs_path, -1) != "/") $abs_path = $abs_path."/";   
    
    // deconvert path 
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }
    
    // check and correct file name
    if (strpos ($filename, ".@") > 0) $filename = substr ($filename, 0, strpos ($filename, ".@"));
    
    // file is already locked by same user
    if (is_file ($abs_path.$filename.".@".$user))
    {
      return true;
    }
    // lock file
    elseif (is_file ($abs_path.$filename))
    {
      return rename ($abs_path.$filename, $abs_path.$filename.".@".$user);
    }
    // file cannot be found
    else return false;
  }
  else return false;
}

// ------------------------------------------ unlockfile -------------------------------------------
// function: unlockfile()
// input: user, path to file, file name
// output: true/false

// description:
// This functions unlockes a file for a specific user

function unlockfile ($user, $abs_path, $filename)
{
  global $mgmt_config, $hcms_lang, $lang;
  
  if (valid_objectname ($user) && valid_locationname ($abs_path) && valid_objectname ($filename))
  {
    // add slash if not present at the end of the location string
    if (substr ($abs_path, -1) != "/") $abs_path = $abs_path."/";   
    
    // deconvert path 
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }
    
    // check and correct file name
    if (strpos ($filename, ".@") > 0) $filename = substr ($filename, 0, strpos ($filename, ".@"));    

    // file is already unlocked
    if (is_file ($abs_path.$filename))
    {
      return true;
    }
    // unlock file
    elseif (is_file ($abs_path.$filename.".@".$user))
    {
      return rename ($abs_path.$filename.".@".$user, $abs_path.$filename);
    }
    // file cannot be found
    else return false;
  }
  else return false;
}

// ------------------------------------------ deletefile --------------------------------------------
// function: deletefile()
// input: path to file, file or directory name, delete all files in directory recursively including symbolic links [true,false] 
// output: true/false

function deletefile ($abs_path, $filename, $recursive=false)
{
  global $user, $mgmt_config, $hcms_lang, $lang;
  
  if (valid_locationname ($abs_path) && valid_objectname ($filename))
  {    
    // add slash if not present at the end of the location string
    if (substr ($abs_path, -1) != "/") $abs_path = $abs_path."/";       
    
    // deconvert path 
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }

    // if selected file is a directory
    if (is_dir ($abs_path.$filename))
    { 
      $test = true;
          
      // check if directory is empty      
      if (!empty ($recursive)) 
      {    
        $dir = opendir ($abs_path.$filename);
        
        while ($dirfile = readdir ($dir))
        {
          if ($dirfile != "." && $dirfile != "..")
          {
            // directory
            if (is_dir ($abs_path.$filename."/".$dirfile)) 
            {
              $test = deletefile ($abs_path.$filename."/", $dirfile, $recursive);
                          
              if ($test == false)
              {
                $errcode = "10107";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|deletefile failed for directory ".$abs_path.$filename."/".$dirfile;
                break;
              }
            }
            // file
            elseif (is_file ($abs_path.$filename."/".$dirfile))
            {
              $test = deletefile ($abs_path.$filename."/", $dirfile, $recursive);    
                      
              if ($test == false)
              {
                $errcode = "10108";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|deletefile failed for file ".$abs_path.$filename."/".$dirfile;
                break;
              }
            }    
          }
        }
        
        @closedir ($dir);
      }
      
      // delete directory itself
      if ($test != false && is_dir ($abs_path.$filename))
      {
        $test = @rmdir ($abs_path.$filename);
        
        if ($test == false)
        {
          $errcode = "10109";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|deletefile failed for directory ".$abs_path.$filename;
        }
      }
    }
    // if selected file is a file
    elseif (is_file ($abs_path.$filename) || is_file ($abs_path.$filename.".off") || is_file ($abs_path.$filename.".@".$user))
    {    
      // if file is offline (for objects)
      if (is_file ($abs_path.$filename.".off")) $filename = $filename.".off"; 
        
      // if file is locked (for containers)
      if (is_file ($abs_path.$filename.".@".$user)) $filename = $filename.".@".$user;   
        
      // remove selected file
      $test = unlink ($abs_path.$filename);
      
      if ($test == false)
      {
        $errcode = "10110";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|deletefile failed for file ".$abs_path.$filename;
      }
    }
    // if selected file is a symbolic link
    elseif (is_link ($abs_path.$filename))
    {
      // delete target file
      if (!empty ($recursive))
      {
        $targetpath = readlink ($abs_path.$filename);
        deletefile ($abs_path, $filename, $recursive);
      }
      
      // remove symbolic link
      $test = unlink ($abs_path.$filename);
      
      if ($test == false)
      {
        $errcode = "10110";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|deletefile failed for symbolic link ".$abs_path.$filename;
      }
    }
    else $test = false;
    
    // write log
    savelog (@$error);

    // return result    
    if ($test == true) return true;
    else return false;
  }
  else return false;
}


// ------------------------------------------ restoremediafile --------------------------------------------
// function: restoremediafile()
// input: publication name, media file name
// output: result array

// description:
// Moves an exported media file back to the media repository

function restoremediafile ($site, $mediafile)
{
  global $mgmt_config;
  
  $success = true;
  $restored = false;
  $medialocation = "";

  if (valid_publicationname ($site) && valid_objectname ($mediafile) && !empty ($mgmt_config['abs_path_media']))
  {  
    // get media location
    $medialocation = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";
    
    // get media repository directory by using a dummy media file with same ID
    $mediaroot = getmedialocation ($site, "dummy.".$mediafile, "abs_path_media").$site."/";

    // move media file back to repository
    if ($mediaroot != $medialocation && is_file ($mediaroot.$mediafile))
    {
      $rename = rename ($medialocation.$mediafile, $mediaroot.$mediafile);

      if (!$rename)
      {
        $errcode = 10602;
        $error[] = date('Y-m-d H:i').'|hypercms_main.inc.php|error|'.$errcode.'|could not restore exported media file '.$mediafile.' to repository';
      
        $success = false;
      }
      else
      {
        // remove symbolic link
        if (is_link ($mediaroot.$mediafile)) deletefile ($mediaroot, $mediafile, 0);
    
        avoidfilecollision();
        
        // reset location path
        $medialocation = $mediaroot;
        $restored = true;
      }
    }
  }
  
  // write log
  savelog (@$error);

  $result = array();
  $result['result'] = $success;
  $result['restored'] = $restored;
  $result['publication'] = $site;
  $result['location'] = $medialocation;
  $result['mediafile'] = $mediafile;
  
  return $result;
}

// ------------------------------------------ preparemediafile --------------------------------------------
// function: preparemediafile()
// input: publication name, media file location, media file name, user name (optional)
// output: result array / false on error

// description:
// Prepares a media file for use in the system (load from cloud, decrypt content)

function preparemediafile ($site, $medialocation, $mediafile, $user="")
{
  global $mgmt_config;

  if (valid_publicationname ($site) && valid_locationname ($medialocation) && valid_objectname ($mediafile) && !empty ($mgmt_config['abs_path_media']))
  {
    // restore exported media file
    $restoremediafile = restoremediafile ($site, $mediafile);
    
    if (!empty ($restoremediafile['result']) && !empty ($restoremediafile['restored']))
    {
      $site = $restoremediafile['publication'];
      $medialocation = $restoremediafile['location'];
      $mediafile = $restoremediafile['mediafile'];
    }
    
    // load from cloud storage
    if (function_exists ("loadcloudobject")) loadcloudobject ($site, $medialocation, $mediafile, $user);

    // create temp file if file is encrypted
    $createtempfile = createtempfile ($medialocation, $mediafile);
    
    // if media file has been restored
    if (!empty ($restoremediafile['restored'])) $createtempfile['restored'] = true;
    else $createtempfile['restored'] = false;
    
    return $createtempfile;
  }
  else return false;
}  
    
// ------------------------------------------ deletemediafiles --------------------------------------------
// function: deletemediafiles()
// input: publication name, mediafile name, delete original media file [true,false] (optional)
// output: true/false

// description:
// Deletes all derivates (thumbnails, config files, converted versions of the file) of a specific media file resource. Deletes the original media file optionally .

function deletemediafiles ($site, $mediafile, $delete_original=false)
{
  global $user, $mgmt_config, $mgmt_mediaoptions, $mgmt_docoptions, $hcms_ext, $hcms_lang, $lang;
  
  if (valid_publicationname ($site) && valid_objectname ($mediafile) && !empty ($mgmt_config['abs_path_cms']) && !empty ($mgmt_config['abs_path_rep']))
  {
    // define media location in repository
    $medialocation = getmedialocation ($site, strrpos ($mediafile, ".").".thumb.jpg", "abs_path_media");

    // original media file
    if ($delete_original == true)
    {
      // define media location of original file (may have been exported)
      $medialocation_orig = getmedialocation ($site, $mediafile, "abs_path_media");
    
      // local media file
      $deletefile = deletefile ($medialocation_orig.$site."/", $mediafile, 0);
      // remove symbolic link of exported media file
      if ($deletefile && is_link ($medialocation.$site."/".$mediafile)) unlink ($medialocation.$site."/".$mediafile);
      // cloud storage
      if (function_exists ("deletecloudobject")) deletecloudobject ($site, $medialocation.$site."/", $mediafile, $user);
      // remote client
      remoteclient ("delete", "abs_path_media", $site, $medialocation.$site."/", "", $mediafile, "");
      // delete media file in temp/view as well (copied by 360 viewer)
      if (is_file ($mgmt_config['abs_path_view'].$mediafile)) deletefile ($mgmt_config['abs_path_view'], $mediafile, 0);
    }
    
    // image thumbnail file
    $mediafile_thumb = substr ($mediafile, 0, strrpos ($mediafile, ".")).".thumb.jpg";
    // local media file
    deletefile ($medialocation.$site."/", $mediafile_thumb, 0);
    // cloud storage
    if (function_exists ("deletecloudobject")) deletecloudobject ($site, $medialocation.$site."/", $mediafile_thumb, $user);
    // remote client
    remoteclient ("delete", "abs_path_media", $site, $medialocation.$site."/", "", $mediafile_thumb, "");

    // image file from RAW image
    if (is_rawimage ($mediafile))
    {
      $mediafile_raw = substr ($mediafile, 0, strrpos ($mediafile, ".")).".jpg";
      // local media file
      deletefile ($medialocation.$site."/", $mediafile_raw, 0);
      // cloud storage
      if (function_exists ("deletecloudobject")) deletecloudobject ($site, $medialocation.$site."/", $mediafile_raw, $user);
    }
    
    // document thumbnail files
    if (is_array ($mgmt_docoptions))
    {
      foreach ($mgmt_docoptions as $docoptions_ext => $docoptions)
      {
        if ($docoptions_ext != "")
        {
          // document thumbnail file
          $mediafile_thumb = substr ($mediafile, 0, strrpos ($mediafile, ".")).".thumb".$docoptions_ext;
          // local media file
          deletefile ($medialocation.$site."/", $mediafile_thumb, 0);
          // cloud storage
          if (function_exists ("deletecloudobject")) deletecloudobject ($site, $medialocation.$site."/", $mediafile_thumb, $user);
          // remote client
          remoteclient ("delete", "abs_path_media", $site, $medialocation.$site."/", "", $mediafile_thumb, "");
        }
      }
    }
    
    // video thumbnail files (original, media player thumbs, individual video files and configs) 
    if (is_array ($mgmt_mediaoptions))
    {
      foreach ($mgmt_mediaoptions as $mediaoptions_ext => $mediaoptions)
      {
        if ($mediaoptions_ext != "" && $mediaoptions_ext != "thumbnail-video" && $mediaoptions_ext != "thumbnail-audio")
        {
          // original thumbnail video file
          $mediafile_orig = substr ($mediafile, 0, strrpos ($mediafile, ".")).".orig".$mediaoptions_ext;
          // local media file
          deletefile ($medialocation.$site."/", $mediafile_orig, 0);
          // cloud storage
          if (function_exists ("deletecloudobject")) deletecloudobject ($site, $medialocation.$site."/", $mediafile_orig, $user);
          // remote client
          remoteclient ("delete", "abs_path_media", $site, $medialocation.$site."/", "", $mediafile_orig, "");
          
          // video thumbnail files
          $mediafile_thumb = substr ($mediafile, 0, strrpos ($mediafile, ".")).".thumb".$mediaoptions_ext;
          // local media file
          deletefile ($medialocation.$site."/", $mediafile_thumb, 0);
          // cloud storage
          if (function_exists ("deletecloudobject")) deletecloudobject ($site, $medialocation.$site."/", $mediafile_thumb, $user);
          // remote client
          remoteclient ("delete", "abs_path_media", $site, $medialocation.$site."/", "", $mediafile_thumb, "");
          
          // video individual files
          $mediafile_video = substr ($mediafile, 0, strrpos ($mediafile, ".")).".media".$mediaoptions_ext;
          // local media file
          deletefile ($medialocation.$site."/", $mediafile_video, 0);
          // cloud storage
          if (function_exists ("deletecloudobject")) deletecloudobject ($site, $medialocation.$site."/", $mediafile_video, $user);
          // remote client
          remoteclient ("delete", "abs_path_media", $site, $medialocation.$site."/", "", $mediafile_video, "");
          
          // media player config file
          $mediafile_config = substr ($mediafile, 0, strrpos ($mediafile, ".")).".config".$mediaoptions_ext;                
          // local media file
          deletefile ($medialocation.$site."/", $mediafile_config, 0);
          // cloud storage
          if (function_exists ("deletecloudobject")) deletecloudobject ($site, $medialocation.$site."/", $mediafile_config, $user);
          // remote client
          remoteclient ("delete", "abs_path_media", $site, $medialocation.$site."/", "", $mediafile_config, "");
        }
      }       
    }
    
    // delete original media player config
    $mediafile_config = substr ($mediafile, 0, strrpos ($mediafile, ".")).".config.orig";  
    // local media file
    deletefile ($medialocation.$site."/", $mediafile_config, 0);
    // cloud storage
    if (function_exists ("deletecloudobject")) deletecloudobject ($site, $medialocation.$site."/", $mediafile_config, $user);
    // remote client
    remoteclient ("delete", "abs_path_media", $site, $medialocation.$site."/", "", $mediafile_config, "");
    
    // delete general audio player config
    $mediafile_config = substr ($mediafile, 0, strrpos ($mediafile, ".")).".config.audio";
    // local media file
    deletefile ($medialocation.$site."/", $mediafile_config, 0);
    // cloud storage
    if (function_exists ("deletecloudobject")) deletecloudobject ($site, $medialocation.$site."/", $mediafile_config, $user);
    // remote client
    remoteclient ("delete", "abs_path_media", $site, $medialocation.$site."/", "", $mediafile_config, "");
    
    // delete general video player config
    $mediafile_config = substr ($mediafile, 0, strrpos ($mediafile, ".")).".config.video";
    // local media file
    deletefile ($medialocation.$site."/", $mediafile_config, 0);
    // cloud storage
    if (function_exists ("deletecloudobject")) deletecloudobject ($site, $medialocation.$site."/", $mediafile_config, $user);
    // remote client
    remoteclient ("delete", "abs_path_media", $site, $medialocation.$site."/", "", $mediafile_config, "");
    
    return true;
  }
  else return false;             
}

// ---------------------- avoidfilecollision -----------------------------
// function: avoidfilecollision()
// input: data string (optional)
// output: true / false on error

// description:
// Appending data to a file ensures that the previous write process is finished (required due to issue when editing encrypted files)

function avoidfilecollision ($data="tempdata")
{
  global $mgmt_config, $site;
  
  if (!valid_publicationname ($site) || (isset ($mgmt_config[$site]['crypt_content']) && $mgmt_config[$site]['crypt_content'] == true))
  {
    // save empty temp file initally (to clear previous data)
    savefile ($mgmt_config['abs_path_temp'], "writefile.tmp", "");
    
    // append data to file
    if (is_file ($mgmt_config['abs_path_temp']."writefile.tmp")) return appendfile ($mgmt_config['abs_path_temp'], "writefile.tmp", $data);
    else return false;
  }
  else return false;
}

// -------------------------------------- substr_in_array -------------------------------------------
// function: substr_in_array()
// input: search-string, array
// output: array with found values / false

// description:
// Searches for substring in array

function substr_in_array ($search, $array)
{
  if (is_array ($array) && $search != "")
  {
    $found = array();
    
    foreach ($array as $key => $value)
    {
      if ($value != "" && strpos ("_".$value, $search) == 1)
      {
        $found[$key] = $value;
      }
    }
    
    if (sizeof ($found) > 0) return $found;
    else return false;
  }
  else return false;
}

// -------------------------------------- downloadobject -------------------------------------------
// function: downloadobject()
// input: location, object name, content container, language (optional), user name (optional)
// output: stream of file content / false on error

// description:
// This functions provides an object via http for viewing, not suitable for multimedia objects!

function downloadobject ($location, $object, $container="", $lang="en", $user="")
{
  global $mgmt_config, $eventsystem, $hcms_lang, $lang;
  
  $location = deconvertpath ($location, "file");

  if (valid_locationname ($location) && valid_objectname ($object) && is_file ($location.$object))
  {
    $prefix = uniqid();
    
    // eventsystem
    if ($eventsystem['onfiledownload_pre'] == 1) onfiledownload_pre ($site, $location, $media, $name, $user);

    // copy to temp/view and execute
    copy ($location.$object, $mgmt_config['abs_path_view'].$prefix."_".$object);
    
    // get content via HTTP in order ro render page
    $content = @file_get_contents ($mgmt_config['url_path_view'].$prefix."_".$object);
    
    // remove temp file
    unlink ($mgmt_config['abs_path_view'].$prefix."_".$object);
    
    // return rendered content
    if ($content != "")
    {
      // get container if not provided
      if ($container == "")
      {
        $objectdata = loadfile ($location, $object);
        $container = getfilename ($objectdata, "content");
      }
      
      // get container id
      if (strpos ($container, ".xml") > 0) $container_id = substr ($container, 0, strpos ($container, ".xml"));
      elseif (is_numeric ($container)) $container_id = $container;
         
      // write stats
      if (is_numeric ($container_id) && $container_id > 0) rdbms_insertdailystat ("download", intval($container_id), $user);
      
      // echo content
      echo $content;
    }
    // return info page
    else echo showinfopage ($hcms_lang['the-requested-object-can-not-be-provided'][$lang], $lang);
    
    // eventsystem
    if ($eventsystem['onfiledownload_post'] == 1) onfiledownload_post ($site, $location, $media, $name, $user);
  }
  else return false;
}

// -------------------------------------- downloadfile -------------------------------------------
// function: downloadfile()
// input: path to file [string], file name to show for download via http, force file wrapper or download or no file headers for WebDAV [download,wrapper,noheader], user name (optional)
// output: stream of file content / false on error

// description:
// This functions provides a file via http for view or download

function downloadfile ($filepath, $name, $force="wrapper", $user="")
{
  global $mgmt_config, $eventsystem, $hcms_lang, $lang, $is_iphone;

  $stream = "";
  $buffer = 102400;
  $start = -1;
  $end = -1;
  $size = 0;
  $range = false;

  if (valid_locationname ($filepath) && $name != "")
  {
    // get publication, location and media object
    $site = getpublication ($filepath);
    $location = getlocation ($filepath);
    $media = getobject ($filepath);

    // prepare media file
    $temp = preparemediafile ($site, $location, $media, $user);
    
    if ($temp['result'] && $temp['crypted'])
    {
      $location = $temp['templocation'];
      $media = $temp['tempfile'];
      
      // set new file path
      $filepath = $location.$media;
    }
    elseif ($temp['restored'])
    {
      $location = $temp['location'];
      $media = $temp['file'];
      
      // set new file path
      $filepath = $location.$media;
    }

    // verify local media file
    if (!is_file ($filepath)) return false;

    // eventsystem
    if ($eventsystem['onfiledownload_pre'] == 1) onfiledownload_pre ($site, $location, $media, $name, $user);

    // get browser information/version
    $user_client = getbrowserinfo ();
    
    // if browser is IE then we need to encode it (does not detect IE 11)
    if (isset ($user_client['msie']) && $user_client['msie'] > 0) $name = rawurlencode ($name);
    
    // read file without headers, no streaming supported (used by WebDAV)
    if ($force == "noheader")
    {
      $filedata = file_get_contents ($location.$media);
    }
    // stream file and provide headers
    else
    {
      // --------------------------------- open stream --------------------------------------

      if (!($stream = fopen ($filepath, 'rb')))
      {
        // can't read the file
        header ("HTTP/1.1 500 Internal Server Error", true, 500);
        $errcode = 20602;
        $error[] = date('Y-m-d H:i').'|hypercms_main.inc.php|error|'.$errcode.'|downloadfile -> Could not open '.$filepath.')';
        // write log
        savelog (@$error);
        exit;
      }

      ob_get_clean();
      
      // get file start and end bytes
      $start = 0;
      $size = filesize ($filepath);
      $end = $size - 1;
  
      // ------------------------- define proper header -------------------------
      // force download of file
      if ($force == "download")
      {
        // iOS Safari does not support file downloads, so the file need to be opened instead
        if (!$is_iphone)
        {
          header ("Content-Type: application/octet-stream", false);
          header ("Content-Type: application/octetstream", false);
          header ("Content-Type: application/force-download", false);
          header ("Content-Disposition: attachment; filename=\"".$name."\"");
        }
          
        // check for IE only headers
        if (isset ($user_client['msie']) && $user_client['msie'] > 0)
        {
          header ('Pragma: public');
          header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        }
        else
        {
          header ('Pragma: no-cache');
        }  
            
        header ("Expires: 0");      
      }
      // provide content of file inline for wrapper
      elseif ($force == "wrapper")
      {
        // content-type
        header ("Content-Disposition: inline; filename=\"".$name."\"");
        header ("Content-Type: ".getmimetype ($filepath));
        header ("Cache-Control: max-age=2592000, public");
        header ("Expires: ".gmdate ('D, d M Y H:i:s', time() + 2592000) . ' GMT');
      }
      
      header ("Last-Modified: ".gmdate ('D, d M Y H:i:s', @filemtime ($filepath)) . ' GMT' );
      header ("Accept-Ranges: 0-".$end);
      
      // partial file download
      if (isset ($_SERVER['HTTP_RANGE']))
      {
        $c_start = $start;
        $c_end = $end;
  
        list ($rest, $range) = explode ('=', $_SERVER['HTTP_RANGE'], 2);
        
        if (strpos ($range, ',') !== false)
        {
          header ('HTTP/1.1 416 Requested Range Not Satisfiable');
          header ("Content-Range: bytes ".$start."-".$end."/".$size);
          exit;
        }
        
        if ($range == '-')
        {
          $c_start = $size - substr ($range, 1);
        }
        else
        {
          $range = explode ('-', $range);
          $c_start = $range[0];
           
          $c_end = (isset ($range[1]) && is_numeric ($range[1])) ? $range[1] : $c_end;
        }
        
        $c_end = ($c_end > $end) ? $end : $c_end;
        
        if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size)
        {
          header ('HTTP/1.1 416 Requested Range Not Satisfiable');
          header ("Content-Range: bytes ".$start."-".$end."/".$size);
          exit;
        }
        
        $start = $c_start;
        $end = $c_end;
        $length = $end - $start + 1;
        fseek ($stream, $start);
        header ('HTTP/1.1 206 Partial Content');
        header ("Content-Length: ".$length);
        header ("Content-Range: bytes ".$start."-".$end."/".$size);
      }
      // standard file download
      else
      {
        if (!$is_iphone)
        {
          header ("Content-Length: ".$size);
          header ("Connection: close");
        }
      }
  
      // ----------------- perform the streaming of calculated range --------------------
      $i = $start;
      set_time_limit (0);

      while (!feof ($stream) && $i <= $end)
      {
        $bytesToRead = $buffer;
        
        if (($i + $bytesToRead) > $end)
        {
          $bytesToRead = $end - $i + 1;
        }
        
        $data = fread ($stream, $bytesToRead);
        echo $data;
        flush ();
        $i += $bytesToRead;
      }
      
      // ----------------------------- close stream -----------------------------------
      fclose ($stream);
    }

    // write stats for partial file download (range has been provided) only if start of file or end of file has been requested 
    if (!is_thumbnail ($location.$media) && (($range && ($start == 0 || $end == ($size - 1))) || !$range))
    {
      $container_id = getmediacontainerid ($media);
      if ($container_id > 0) rdbms_insertdailystat ("download", $container_id, $user);
    }
    
    // eventsystem
    if ($eventsystem['onfiledownload_post'] == 1) onfiledownload_post ($site, $location, $media, $name, $user);

    // write log
    savelog (@$error);

    // return result
    if ($force == "noheader" && !empty ($filedata)) return $filedata;
    elseif ($force != "noheader" && empty ($error)) return true;
    else return false;
  }
  else return false;
}

 // ================================ LOAD/SAVE CONTAINER FUNCTIONS =====================================

// ----------------------------------------- loadcontainer ---------------------------------------------
// function: loadcontainer()
// input: container file name or container id (working container will be loaded by default), optional container type [published, work, version], user name
// output: XML content of container / false on error

// description:
// This functions loads a content container

function loadcontainer ($container, $type="work", $user)
{
  global $mgmt_config, $hcms_lang, $lang;
  
  $contentdata = false;

  if (valid_objectname ($container) && ($type == "work" || $type == "published" || $type == "version") && valid_objectname ($user))
  {
    $restored = false;
    
    // use temporary cache to reduce I/O if save is disabled
    if (getsession ("hcms_temp_save", "yes") == "no")
    {
      // container data exists in cache
      if (getsession ("hcms_temp_cache") != "") return $contentdata = getsession ("hcms_temp_cache");
    }
      
    // if container holds file name
    if (strpos ($container, ".xml") > 0)
    {
      $container_id = substr ($container, 0, strpos ($container, ".xml"));
    }
    // if container is media file version
    elseif (strpos ($container, "_hcm") > 0)
    {
      $container_id = getmediacontainerid ($container);
    }
    else $container_id = $container;
    
    // if container id
    if ($container_id != "" && is_numeric ($container_id))
    { 
      if (strpos ($container, ".xml.wrk") > 0)
      {
        if ($type == "published") $container = $container_id.".xml";
      }    
      else
      {
        if ($type == "published") $container = $container_id.".xml";
        elseif ($type == "work") $container = $container_id.".xml.wrk";
      }
    
      // container location
      $location = getcontentlocation ($container_id, 'abs_path_content');
      
      // get container info
      $container_info = getcontainername ($container);
  
      // try to load container if it is locked by another user and current user is superadmin
      if ($type == "work" && !empty ($_SESSION['hcms_superadmin']) && $_SESSION['hcms_superadmin'] == 1 && !empty ($container_info['container']) && is_file ($location.$container_info['container']))
      {
        $contentdata = loadfile ($location, $container_info['container']);
      }
      // load unlocked container
      elseif (is_file ($location.$container))
      {
        $contentdata = loadfile ($location, $container);
      }
      // load locked container for current user
      elseif (valid_objectname ($user) && is_file ($location.$container.".@".$user))
      {
        $contentdata = loadfile ($location, $container.".@".$user);
      }
      // working container is not locked and is missing -> restore container
      elseif (empty ($container_info['user']) && $type == "work" && is_file ($location.$container_id.".xml"))
      {
        // try to restore working from live container
        $result_copy = copy ($location.$container_id.".xml", $location.$container_id.".xml.wrk");

        if ($result_copy == false)
        {
          $errcode = "10198";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|working container ".$container." could not be restored";
          
          savelog (@$error);
        }
        else
        {
          $contentdata = loadfile ($location, $container_id.".xml.wrk");
          $restored = true;
        }      
      }
      // live/published container is missing -> restore container
      elseif ($type == "published" && is_file ($location.$container_id.".xml.wrk"))
      {
        // try to restore live from working container
        $result_copy = copy ($location.$container_id.".xml.wrk", $location.$container_id.".xml");

        if ($result_copy == false)
        {
          $errcode = "10199";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|live container ".$container." could not be restored";
          
          savelog (@$error);
        }
        else
        {
          $contentdata = loadfile ($location, $container_id.".xml");
          $restored = true;
        }
      }

      // decrypt container if it is encrypted
      if (!empty ($contentdata) && strpos ("_".$contentdata, "<!-- hyperCMS:encrypted -->") > 0)
      {
        $contentdata = str_replace ("<!-- hyperCMS:encrypted -->", "", $contentdata);
        $contentdata = hcms_decrypt ($contentdata, "", "strong", "none");

        // set status to "restored"
        if ($restored) $contentdata = setcontent ($contentdata, "<hyperCMS>", "<contentstatus>", "restored", "", "");
      }
    }
  }

  // use temporary cache to reduce I/O if save if disabled
  if (getsession ("hcms_temp_save", "yes") == "no")
  {
    // save container data in cache
    if ($contentdata != false) setsession ("hcms_temp_cache", $contentdata);
    // container is not available but exists in cache
    elseif ($contentdata == false && getsession ("hcms_temp_cache") != "") $contentdata = getsession ("hcms_temp_cache");
  }

  // return container content
  return $contentdata;
}

// ----------------------------------------- savecontainer ---------------------------------------------
// function: savecontainer()
// input: container file name or container id (working container will be loaded by default), optional container type [published,work,version], container content, user, 
//        save container initally [true,false] (optional)
// output: true / false on error
// requires: config.inc.php to be loaded before

// description:
// Saves data into existing content container by default. Only if $init is set to true it will initally save a non existing container.

function savecontainer ($container, $type="work", $data, $user, $init=false)
{
  global $mgmt_config, $hcms_lang, $lang;

  if (valid_objectname ($container) && $data != "" && ($type == "work" || $type == "published" || $type == "version") && valid_objectname ($user))
  {
    // use temporary cache to reduce I/O if save is disabled
    if (getsession ("hcms_temp_save", "yes") == "no") 
    {
      setsession ("hcms_temp_cache", $data);
      // dont save data to file if saving is disabled
      if (getsession ("hcms_temp_save", "yes") == "no") return true;
    }
   
    // if container file name (given container file will be saved)
    if (strpos ($container, ".xml") > 0)
    {
      $container_id = substr ($container, 0, strpos ($container, ".xml"));
    }
    // if container id (working container will be saved)
    elseif (intval ($container) > 0)
    {
      $container_id = $container;
    }
    
    if (!empty ($container_id))
    {
      if ($type == "published") $container = $container_id.".xml";
      elseif ($type == "work") $container = $container_id.".xml.wrk";
      elseif ($type == "version") $container = $container;
      
      $location = getcontentlocation ($container_id, 'abs_path_content');
      
      // get publication from container (the publication where the content has been initally created will be used)
      $origin = getcontent ($data, "<contentorigin>");
      
      if (!empty ($origin[0])) $site = getpublication ($origin[0]);
      else $site = false;
      
      // encrypt data
      if (
           is_file ($mgmt_config['abs_path_cms']."encryption/hypercms_encryption.inc.php") && 
           (!empty ($site) && isset ($mgmt_config[$site]['crypt_content']) && $mgmt_config[$site]['crypt_content'] == true) && 
           $data != "" && strpos ("_".$data, "<!-- hyperCMS:encrypted -->") == 0
         )
      {
        $data = hcms_encrypt (trim ($data), "", "strong", "none");
        if (!empty ($data)) $data = "<!-- hyperCMS:encrypted -->".$data; 
      }

      // save data
      if ($init == true) return savefile ($location, $container, $data);
      elseif (valid_objectname ($user) && is_file ($location.$container.".@".$user)) return savefile ($location, $container.".@".$user, $data);
      elseif (is_file ($location.$container)) return savefile ($location, $container, $data);
      else return false;
    }
    else return false;
  }
  else return false;
}

// ========================================= WORKFLOW ============================================

// -------------------------------------- getworkflowitem ----------------------------------------
// function: getworkflowitem()
// input: publication name [string], location name [string], object name [string], workflow file name [string], workflow [XML-string], user name [string]
// output: workflow item [XML-string]
// requires: config.inc.php, editcontent

function getworkflowitem ($site, $workflow_file, $workflow, $user)
{
  global $mgmt_config, $hcms_lang, $lang;
  
  if (valid_publicationname ($site) && valid_objectname ($workflow_file) && $workflow != "" && valid_objectname ($user))
  {
    // get usergroup users
    $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");
    $buffer_array = selectcontent ($userdata, "<user>", "<login>", "$user");  
    $buffer_array = selectcontent ($buffer_array[0], "<memberof>", "<publication>", "$site");
    $buffer_array = getcontent ($buffer_array[0], "<usergroup>");  
    $group_str = substr ($buffer_array[0], 1, strlen ($buffer_array[0])-2);
    $group_array = explode ("|", $group_str);
    
    // check if user owns workflow items
    $item_array = getxmlcontent ($workflow, "<item>");
    
    foreach ($item_array as $item)
    {
      $type_array = getcontent ($item, "<type>");
      
      if ($type_array[0] == "user")
      {
        $buffer_array = getcontent ($item, "<user>");
        
        if ($buffer_array[0] == $user) $useritem_array[] = $item;
      }
      elseif ($type_array[0] == "usergroup")
      {
        $buffer_array = getcontent ($item, "<group>");
        
        if (in_array ($buffer_array[0], $group_array)) $useritem_array[] = $item;
      }
    }
    
    // if user own items and the predecessors have not passed their items
    if (is_array ($useritem_array) && sizeof ($useritem_array) > 0)
    { 
      // check if predecessors are available and if they passed their item
      foreach ($useritem_array as $useritem)
      {
        $id_array = getcontent ($useritem, "<id>");        
        $passed_array = getcontent ($useritem, "<passed>");
        $pre_array = getcontent ($useritem, "<pre>");
  
        // if item has predecessors
        if ($pre_array != false)
        {
          foreach ($pre_array as $pre)
          {
            $buffer_array = selectcontent ($workflow, "<item>", "<id>", $pre);   
            
            // if a predecessor was found
            if ($buffer_array != false) 
            {
              $prepassed_array = getcontent ($buffer_array[0], "<passed>");
    
              if ($prepassed_array != false)
              {
                // check if the predecessor has passed the workflow (this is a must)
                if ($prepassed_array[0] == 1) 
                {
                  $buffer_array = selectcontent ($workflow, "<item>", "<pre>", $id_array[0]);
                  $sucpassed_array = getcontent ($buffer_array[0], "<passed>");
                  
                  // if item has sucessors
                  if ($sucpassed_array != false) 
                  {
                    // check if the sucessor has not already passed the workflow
                    if ($sucpassed_array[0] != 1) 
                    {
                      if ($passed_array[0] != 1) $freeitem_array[] = $useritem;
                      else $passeditem_array[] = $useritem;
                    }
                  }
                  // otherwise item is last instance in workflow branch
                  else
                  {
                    if ($passed_array[0] != 1) $freeitem_array[] = $useritem;
                    else $passeditem_array[] = $useritem;                
                  }
                }     
              }     
            }  
          }   
        }
        // if item has no predecessors, this must be the user who owns start item
        else
        {
          $buffer_array = selectcontent ($workflow, "<item>", "<pre>", $id_array[0]);
          $sucpassed_array = getcontent ($buffer_array[0], "<passed>");
  
          // if item has sucessors
          if ($sucpassed_array != false) 
          {
            // check if the sucessor has not already passed the workflow
            if ($sucpassed_array[0] != 1) 
            {
              if ($passed_array[0] != 1) $freeitem_array[] = $useritem;
              else $passeditem_array[] = $useritem;   
            }
            // sucessor passed his item
            else
            {
              // find a last passed instance in workflow (end of workflow or a branch was reached)
              foreach ($item_array as $item)
              {
                $buffer_array = getcontent ($item, "<id>");
                $buffer_array = selectcontent ($workflow, "<item>", "<pre>", $buffer_array[0]);  
                
                if ($buffer_array == false)
                {
                  $buffer_array = getcontent ($item, "<passed>");
                  
                  if ($buffer_array[0] == 1)
                  {
                    $passeditem_array[] = $useritem;
                    break;
                  }
                } 
              }         
            }
          }
          // otherwise item is last instance in workflow branch
          else
          {
            if ($passed_array[0] != 1) $freeitem_array[] = $useritem;
            else $passeditem_array[] = $useritem;           
          }
        }        
      }
    }
    else return false;
   
    // check for free items of the user
    if (is_array ($freeitem_array) && sizeof ($freeitem_array) > 0)
    {
      return $freeitem_array[0];
    }
    // check for passed items of the user
    elseif (is_array ($passeditem_array) && sizeof ($passeditem_array) > 0)
    {
      return $passeditem_array[0];
    }
    else return false;
  }
  else return false;
}

// -------------------------------------------- checkworkflow -------------------------------------------
// function: checkworkflow()
// input: publication name [string], location [string], object name [string], category [page,comp] (optional), container name [string] (optional), container [XML string] (optional), view name [string] (optional), view store [string] (optional), user name [string]
// output: result array
// requires: config.inc.php, fileoperation

// description:
// Help function for function buildview to evaluate the workflow of an object and return the manipulated view store, view name, workflow ID, workflow role and the encrypted workflow token.

function checkworkflow ($site, $location, $page, $cat="", $contentfile="", $contentdata="", $buildview="cmsview", $viewstore="", $user)
{
  global $mgmt_config;

  $checkworkflow_result = false;
  $workflow_name = "";
  $workflow_xml = "";
  $wf_id = "";
  $wf_role = "";
  $wf_token = "";
  
  // do not execute for container versions
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && ($contentfile == "" || strpos ("_".strrchr ($contentfile, "."), ".v_") == 0) && $buildview != "" && valid_objectname ($user))
  {
    // check category
    if ($cat == "") $cat = getcategory ($site, $location);
    
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";
    
    // check contentfile and viewstore
    if ($contentfile == "" || $viewstore == "")
    {
      $objectinfo = getobjectinfo ($site, $location, $page, $user);
      
      if (!empty ($objectinfo['container'])) $contentfile = $objectinfo['container'];
      if (!empty ($objectinfo['template'])) $templatefile = $objectinfo['template'];
      
      if ($viewstore == "") $viewstore = loadtemplate ($site, $templatefile);
    }
    
    // check contentdata
    if ($contentfile != "" && $contentdata == "")
    {
      $contentdata = loadcontainer ($contentfile, "work", $user);
    }

    // get all hyperCMS tags
    $hypertag_array = gethypertag ($viewstore, "workflow", 0);
    
    // check view
    if (in_array ($buildview, array("cmsview","inlineview","publish","formedit","formmeta")) && ($cat == "page" || $cat == "comp") && $contentfile != "" && $contentdata != "" && $viewstore != "")
    {
      // get applied workflows on folders
      if (is_file ($mgmt_config['abs_path_data']."workflow_master/".$site.".".$cat.".folder.dat"))
      {
        $wf_data = loadfile_fast ($mgmt_config['abs_path_data']."workflow_master/", $site.".".$cat.".folder.dat");
        
        if ($wf_data != false)
        {
          $wf_array = explode ("\n", trim ($wf_data));                 
        
          if (is_array ($wf_array) && sizeof ($wf_array) > 0)
          {   
            $folder_current = convertpath ($site, $location, $cat);
          
            // find workflows that would apply on the current folder
            foreach ($wf_array as $wf_folder)
            {
              list ($workflow, $object_id) = explode ("|", $wf_folder);
              
              // versions before 5.6.3 used folder path instead of object id
              if (substr_count ($object_id, "/") == 0) $folder = rdbms_getobject ($object_id);
              else $folder = $object_id;
              
              // remove .folder from folder location
              if (substr (trim ($folder), -8) == "/.folder") $folder = substr (trim ($folder), 0, strlen(trim ($folder)) - 7);

              // compare workflow folder with current location
              if (@substr_count ($folder_current, $folder) == 1) $wf_apply_array[] = $wf_folder;          
            }
            
            // compare workflows that matched before and find the nearest
            if (isset ($wf_apply_array) && is_array ($wf_apply_array))
            {
              $compare = 0;
              
              foreach ($wf_apply_array as $wf_folder)
              {
                list ($workflow, $folder) = explode ("|", $wf_folder);
                
                if (strlen ($folder) > $compare) 
                {
                  $compare = strlen ($folder);
                  $workflow_name = $workflow;
                }        
              }
            }
          }
        }    
      }

      // check workflow of object
      if ($hypertag_array != false || $workflow_name != "")
      {  
        // check if workflow file exists for object
        if (is_file ($mgmt_config['abs_path_data']."workflow/".$site."/".$contentfile))
        {
          // get workflow
          $stop = false;
          
          if ($hypertag_array != false)
          {
            foreach ($hypertag_array as $hypertag)
            {    
              $workflow_name = getattribute ($hypertag, "name");  
                  
              // search for a valid workflow declared in the template    
              if ($stop == false)
              {      
                $workflow_xml = loadfile ($mgmt_config['abs_path_data']."workflow/".$site."/", $contentfile);
                             
                // check if current workflow in use is the same workflow as set in the template
                if ($workflow_xml != false && $workflow_xml != "") 
                {
                  $workflow_name_current = getcontent ($workflow_xml, "<name>");
                  
                  if ($workflow_name_current != false && $workflow_name_current[0] == $workflow_name) 
                  {
                    $update_workflow = false;
                  }  
                  else $update_workflow = true;
                  
                  // set stop flag to break loop    
                  $stop = true;              
                }
              }
            }  
          }  
          elseif ($workflow_name != "")
          {
            $workflow_xml = loadfile ($mgmt_config['abs_path_data']."workflow/".$site."/", $contentfile);
        
            // check if current workflow in use is the same workflow as set in template
            if ($workflow_xml != false && $workflow_xml != "") 
            {
              $workflow_name_current = getcontent ($workflow_xml, "<name>");
              
              if ($workflow_name_current != false && $workflow_name_current[0] == $workflow_name) 
              {
                $update_workflow = false;
              }  
              else $update_workflow = true;
              
              // set stop flag to break loop    
              $stop = true;              
            }
            else $update_workflow = true;        
          }
        }

        // if workflow doesn't exist for the object or must be updated
        if (!is_file ($mgmt_config['abs_path_data']."workflow/".$site."/".$contentfile) || $update_workflow == true)
        {
          // get workflow
          $stop = false;
          
          if ($hypertag_array != false)
          {      
            foreach ($hypertag_array as $hypertag)
            {    
              $workflow_name = getattribute ($hypertag, "name");  
              $workflow_file = $site.".".$workflow_name.".xml";  
                
              if ($stop != true)
              {                       
                if ($workflow_name != false && $workflow_name != "" && is_file ($mgmt_config['abs_path_data']."workflow_master/".$workflow_file))
                { 
                  // load workflow 
                  $workflow_xml = loadfile ($mgmt_config['abs_path_data']."workflow_master/", $workflow_file);
                  
                  // if master workflow doesn't exist
                  if ($workflow_xml != false)
                  {
                    // get user of start item
                    $start_item_array = selectcontent ($workflow_xml, "<item>", "<id>", "u.1");
                    
                    if ($start_item_array != false) $start_user_array = getcontent ($start_item_array[0], "<user>");              
                              
                    // if start user is not set in workflow
                    if ($start_user_array[0] == "") 
                    {
                      // set start user in workflow if was not set in workflow
                      $workflow_xml = setcontent ($workflow_xml, "<item>", "<user>", $user, "<id>", "u.1");
      
                      // reset passed status in workflow
                      $workflow_xml = setcontent ($workflow_xml, "<item>", "<passed>", 0, "", "");   
      
                      // save workflow
                      $test = savefile ($mgmt_config['abs_path_data']."workflow/".$site."/", $contentfile, $workflow_xml);               
                    }  
                    // if start user is set but not the same logged in user
                    elseif ($start_user_array[0] != false && $start_user_array[0] != $user)
                    {
                      // user has no right to create the page due to workflow
                      $test = false;
                    }
                    // if start user is set and the same logged in user
                    elseif ($start_user_array[0] != false && $start_user_array[0] == $user)
                    {
                      // user has no right to create the page due to workflow
                      $test = true;
                    }                
                    else $test = false;
                            
                    // set workflow in content container
                    if ($test != false) 
                    { 
                      $contentdata = setcontent ($contentdata, "<hyperCMS>", "<contentworkflow>", $workflow_name, "", "");
                      savecontainer ($contentfile, "work", $contentdata, $user);
                    }
                    
                    // set stop flag to break loop after first entry      
                    $stop = true;    
                  }
                }  
              }
            }
          }
          elseif ($workflow_name != "")
          {
            $workflow_file = $site.".".$workflow_name.".xml";  
          
            if (is_file ($mgmt_config['abs_path_data']."workflow_master/".$workflow_file))
            { 
              // load workflow 
              $workflow_xml = loadfile ($mgmt_config['abs_path_data']."workflow_master/", $workflow_file);
              
              // if master workflow doesn't exist
              if ($workflow_xml != false)
              {
                // get user of start item
                $start_item_array = selectcontent ($workflow_xml, "<item>", "<id>", "u.1");
                
                if ($start_item_array != false) $start_user_array = getcontent ($start_item_array[0], "<user>");              
                          
                // if start user is not set in workflow
                if ($start_user_array[0] == "") 
                {
                  // set start user in workflow if was not set in workflow
                  $workflow_xml = setcontent ($workflow_xml, "<item>", "<user>", $user, "<id>", "u.1");
    
                  // reset passed status in workflow
                  $workflow_xml = setcontent ($workflow_xml, "<item>", "<passed>", 0, "", "");   
    
                  // save workflow
                  $test = savefile ($mgmt_config['abs_path_data']."workflow/".$site."/", $contentfile, $workflow_xml);               
                }  
                // if start user is set but not the same logged in user
                elseif ($start_user_array[0] != false && $start_user_array[0] != $user)
                {
                  // user has no right to create the page due to workflow
                  $test = false;
                }
                // if start user is set and the same logged in user
                elseif ($start_user_array[0] != false && $start_user_array[0] == $user)
                {
                  // user has no right to create the page due to workflow
                  $test = true;
                }                
                else $test = false;
                        
                // set workflow in content container
                if ($test != false) 
                { 
                  $contentdata = setcontent ($contentdata, "<hyperCMS>", "<contentworkflow>", $workflow_name, "", "");
                  savecontainer ($contentfile, "work", $contentdata, $user);
                }
              }
            }
          }
        }

        // check if user is a member of the workflow
        if ($workflow_xml != "")
        {
          $wfitem = getworkflowitem ($site, $contentfile, $workflow_xml, $user);
  
          // user is a member of the workflow
          if ($wfitem != false)
          { 
            $wf_id_array = getcontent ($wfitem, "<id>");
            $wf_id = $wf_id_array[0];
          
            $role_array = getcontent ($wfitem, "<role>");
            if ($role_array != false) $role = $role_array[0];
            
            // check cases and set view and control parameters:
            
            // user is not a member of workflow = 0 (no permissions)
            // r = 1
            // r + w = 2
            // r + x = 3
            // r + w + x = 4
            // no workflow = 5 (users permissions apply)
            
            if ($role == "r") 
            {
              // correct view only if cmsview is set and set workflow permission
              if ($buildview == "cmsview" || $buildview == "inlineview") $buildview = "preview";
              elseif ($buildview == "formedit" || $buildview == "formmeta") $buildview = "formlock";
              $wf_role = 1;       
            }
            elseif ($role == "rw") 
            {
              $wf_role = 2;      
            }   
            elseif ($role == "rx" || $role == "x") 
            {
              if ($buildview == "cmsview") $buildview = "preview";
              elseif ($buildview == "formedit" || $buildview == "formmeta") $buildview = "formlock";
              $wf_role = 3;
            }  
            elseif ($role == "rwx") 
            {       
              $wf_role = 4;
            }           
          }
          // if user is not a member of the workflow
          else
          {
            if ($buildview == "cmsview" || $buildview == "inlineview") $buildview = "preview";
            elseif ($buildview == "formedit" || $buildview == "formmeta") $buildview = "formlock";
  
            $wf_role = 0;
          }       
        }
        // if workflow could not be loaded
        elseif ($buildview == "cmsview" || $buildview == "inlineview" || $buildview == "formedit" || $buildview == "formmeta")
        {
          if ($buildview == "cmsview" || $buildview == "inlineview") $buildview = "preview";
          elseif ($buildview == "formedit" || $buildview == "formmeta") $buildview = "formlock";
  
          $wf_role = 0;
        }  
      }
      // if there is no workflow attached
      else 
      {
        $wf_role = 5;
      }
      
      // define workflow token
      if ($wf_id != "" && $wf_role != "") $wf_token = hcms_encrypt ($wf_id.":".$wf_role);
      elseif ($wf_role != "") $wf_token = hcms_encrypt ("Null:".$wf_role);
    }

    // replace all hyperCMS workflow tags in viewstore   
    if ($hypertag_array != false)
    {
      foreach ($hypertag_array as $hypertag)
      {
        if ($buildview != "template") 
        {
          $viewstore = str_replace ($hypertag, "", $viewstore);
        }
        else 
        {
          $workflow_name = getattribute ($hypertag, "name"); 
          $viewstore = str_replace ($hypertag, "<table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">\n  <tr>\n    <td>\n      <font face=\"Verdana, Arial, Helvetica, sans-serif\" size=1 color=#000000><b>workflow: </b>".$workflow_name."</font>\n    </td>\n  </tr>\n</table>\n", $viewstore);
        }      
      }  
    } 
    
    $checkworkflow_result = true;
  }
  // if container is a version
  elseif (valid_objectname ($contentfile) && strpos ("_".strrchr ($contentfile, "."), ".v_") == 0)
  {
    // only read
    $wf_role = 1;
    $checkworkflow_result = true;
  }

  // return result
  $result = array();
  $result['result'] = $checkworkflow_result;
  $result['viewstore'] = $viewstore;
  $result['viewname'] = $buildview;
  $result['wf_name'] = $workflow_name;
  $result['wf_id'] = $wf_id;
  $result['wf_role'] = $wf_role;
  $result['wf_token'] = $wf_token;
  
  return $result;
}   

// ======================================= INHERITANCE DATABASE ==========================================

// ----------------------------------------- inherit_db_load ---------------------------------------------
// function: inherit_db_load()
// input: nothing
// output: inheritance database [2 dim. array]/false
// requires: hypercms_api.inc.php, config.inc.php

// description:
// This function loads and locks the inheritance database.
// Each record of the inherit management database has the following design:
// xml-content container :| absolute path to 1-n objects :| 1-m inherits used by 1-n objects
// Important: The inherit management database needs to be saved or closed after loading it

function inherit_db_load ($user)
{
  global $siteaccess, $mgmt_config, $hcms_lang, $lang;  

  $inherit_db_data = loadlockfile ($user, $mgmt_config['abs_path_data']."config/", "inheritance.dat", 3);
  
  if ($inherit_db_data != false && trim ($inherit_db_data) != "")
  {
    $inherit_db = array();
    $inherit_db_array = explode ("\n", $inherit_db_data);
    
    if (is_array ($inherit_db_array))
    {
      foreach ($inherit_db_array as $inherit_db_record)
      {
        $inherit_db_record = trim ($inherit_db_record);
       
        if ($inherit_db_record != "")
        {
          list ($parent, $childs) = explode (":|", $inherit_db_record);
          
          $inherit_db[$parent]['parent'] = $parent;
          $inherit_db[$parent]['child'] = $childs;
        }
        else 
        {
          $errcode = "10902";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|inherit_db_record is corrupt (null), inherit_db_load failed";           
        }        
      }
    }
  }
  elseif (trim ($inherit_db_data) == "")
  {
    $inherit_db = array();
    $inherit_db['hcms_empty']['parent'] = "";
    $inherit_db['hcms_empty']['child'] = "";
  }
  else 
  {
    $errcode = "10901";
    $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|loadlockfile failed in inherit_db_load";         
  
    $inherit_db = false;
  }
  
  // save log
  savelog (@$error);
  
  return $inherit_db;  
}

// ----------------------------------------- inherit_db_read ---------------------------------------------
// function: inherit_db_read()
// input: nothing
// output: inheritance database [2 dim. array]/false
// requires: hypercms_api.inc.php, config.inc.php

// description:
// This function loads the inheritance database for reading

function inherit_db_read ()
{ 
  global $user, $mgmt_config, $hcms_lang, $lang;  

  $inherit_db_data = loadfile ($mgmt_config['abs_path_data']."config/", "inheritance.dat");
  
  if ($inherit_db_data != false)
  {
    $inherit_db_array = explode ("\n", $inherit_db_data);
    
    foreach ($inherit_db_array as $inherit_db_record)
    {
      $inherit_db_record = trim ($inherit_db_record);
     
      if ($inherit_db_record != "")
      {
        list ($parent, $childs) = explode (":|", $inherit_db_record);
        
        $inherit_db[$parent]['parent'] = $parent;
        $inherit_db[$parent]['child'] = $childs;
      }
      else 
      {
        $errcode = "10912";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|inherit_db_record is corrupt (null), inherit_db_read failed";           
      }        
    }
  }
  elseif (trim ($inherit_db_data) == "")
  {
    $inherit_db = array();
    $inherit_db['hcms_empty']['parent'] = "";
    $inherit_db['hcms_empty']['child'] = "";
  }
  else 
  {
    $errcode = "10911";
    $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|loadfile failed in inherit_db_read";         
  
    $inherit_db = false;
  }
  
  // save log
  savelog (@$error); 
  
  return $inherit_db;  
}

// ---------------------------------------- inherit_db_close --------------------------------------------
// function: inherit_db_close()
// input: %
// output: true/false
// requires: hypercms_api.inc.php

// description:
// Closes and unlocks the inheritance management database

function inherit_db_close ($user)
{
  global $mgmt_config, $hcms_lang, $lang;  
  
  return unlockfile ($user, $mgmt_config['abs_path_data']."config/", "inheritance.dat");
}  

// ---------------------------------------- inherit_db_save --------------------------------------------
// function: inherit_db_save()
// input: inherit database array 
// output: true/false
// requires: hypercms_api.inc.php, config.inc.php

// description:
// This function saves und unlocks the inheritance management database

function inherit_db_save ($inherit_db, $user)
{
  global $mgmt_config, $hcms_lang, $lang;
  
  if (sizeof ($inherit_db) >= 1)
  {    
    foreach ($inherit_db as $inherit_db_record)
    {
      if (is_array ($inherit_db_record))
      {
        $inherit_db_array[] = implode (":|", $inherit_db_record);
      }
    }
    
    if (is_array ($inherit_db_array)) 
    {      
      $inherit_db_data = implode ("\n", $inherit_db_array);
          
      if ($inherit_db_data != false) 
      {
        return savelockfile ($user, $mgmt_config['abs_path_data']."config/", "inheritance.dat", $inherit_db_data);
      } 
      else 
      {
        unlockfile ($user, $mgmt_config['abs_path_data']."config/", "inheritance.dat");
        return false;
      } 
    }
    else return false;
  }  
  elseif (is_array ($inherit_db))
  {
    return savelockfile ($user, $mgmt_config['abs_path_data']."config/", "inheritance.dat", "parent:|child|\n");
  }
  else return false;
}

// ---------------------------------------- inherit_db_getparent --------------------------------------------
// function: inherit_db_getparent()
// input: inherit database [2 dim. array], child [string]
// output: all parents of given child [1 dim. array] / false
  
function inherit_db_getparent ($inherit_db, $child)
{
  if (is_array ($inherit_db))
  {
    $parents = array();
    
    foreach ($inherit_db as $inherit_db_record)
    {
      if ($inherit_db_record['parent'] != "" && @substr_count ("|".$inherit_db_record['child'], "|".$child."|") > 0)
      {
        $parents[] = $inherit_db_record['parent'];
      } 
    }  
    
    if (is_array ($parents) && sizeof ($parents) > 0) return $parents;
    else return false;
  }  
  else return false;
}

// ---------------------------------------- inherit_db_getchild--------------------------------------------
// function: inherit_db_getchild()
// input: parent [string]
// output: all childs of given parent [1 dim. array] / false
  
function inherit_db_getchild ($inherit_db, $parent)
{
  if (is_array ($inherit_db))
  {
    $childs = array();
    
    if (is_array ($inherit_db[$parent]) && @substr_count ($inherit_db[$parent]['child'], "|") > 0)
    {
      $child_str = substr ($inherit_db[$parent]['child'], 0, strlen ($inherit_db[$parent]['child'])-1);
      $childs = explode ("|", $child_str);
    }
    
    if (is_array ($childs) && sizeof ($childs) > 0) return $childs;
    else return false;
  }  
  else return false;
}

// ---------------------------------------- inherit_db_setparent --------------------------------------------
// function: inherit_db_setparent()
// input: inherit database [2 dim. array], child [string], parents [Array], 
// output: inherit database [2 dim. array]

// description:
// This function updates and insert all references from a child an its parents

function inherit_db_setparent ($inherit_db, $child, $parent_array)
{
  if (is_array ($inherit_db))
  {
    foreach ($inherit_db as $inherit_db_record)
    {
      if (in_array ($inherit_db_record['parent'], $parent_array) && @substr_count ("|".$inherit_db_record['child'], "|".$child."|") == 0)
      {
        $parent = $inherit_db_record['parent'];
        $inherit_db[$parent]['child'] .= $child."|";
      }
      elseif (!in_array ($inherit_db_record['parent'], $parent_array) && @substr_count ("|".$inherit_db_record['child'], "|".$child."|") >= 1)
      {
        $parent = $inherit_db_record['parent'];
        $inherit_db[$parent]['child'] = str_replace ($child."|", "", $inherit_db[$parent]['child']);      
      }
    }
    
    return $inherit_db;
  }
  else return false;
}  

// ---------------------------------------- inherit_db_insertparent --------------------------------------------
// function: inherit_db_insertparent()
// input: inherit database [2 dim. array], parent [string], childs [Array], 
// output: inherit database [2 dim. array]
  
function inherit_db_insertparent ($inherit_db, $parent, $child_array)
{
  if (!isset ($inherit_db[$parent]))
  {
    if (is_array ($child_array))
    {
      foreach ($child_array as $child)
      {
        $childs .= $child."|";
      }
    }
    else $childs = "";
  
    $inherit_db[$parent]['parent'] = $parent;
    $inherit_db[$parent]['child'] = $childs;
  }    
  
  return $inherit_db;
}

// ---------------------------------------- inherit_db_deleteparent --------------------------------------------
// function: inherit_db_deleteparent()
// input: inherit database [2 dim. array], parent [string]
// output: inherit database [2 dim. array]
  
function inherit_db_deleteparent ($inherit_db, $parent)
{
  if (isset ($inherit_db[$parent]))
  {
    unset ($inherit_db[$parent]);
    
    if (is_array ($inherit_db))
    {
      foreach ($inherit_db as $inherit_db_record)
      {
        if (@substr_count ("|".$inherit_db_record['child'], "|".$parent."|") >= 1)
        {
          $thisparent = $inherit_db_record['parent'];
          $inherit_db[$thisparent]['child'] = str_replace ($parent."|", "", $inherit_db[$thisparent]['child']);
        }
      }
    }    
  }    
  
  return $inherit_db;
}

// ======================================= INSTANCE OPERATIONS ==========================================

// ------------------------- getconfigvalue -----------------------------
// function: getconfigvalue()
// input: settings array, value/substring in array key (optional)
// output: value of setting

// description:
// Help function for createinstance

function getconfigvalue ($config, $in_key="")
{
  if (is_array ($config))
  {
    foreach ($config as $key => $value)
    {
      if ($in_key != "" && substr_count ($key, $in_key) > 0 && $value != "") return $value;
      elseif ($in_key == "" && $value != "") return $value;
    }
    
    return "";
  }
  else return "";
}

// ------------------------- createinstance -----------------------------
// function: createinstance()
// input: instance name, settings array, user name  
// output: result array

// description:
// This function creates a new instance with all its files and the mySQL database

function createinstance ($instance_name, $settings, $user="sys")
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;
  
  // eventsystem
  if ($eventsystem['oncreateinstance_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
    oncreateinstance_pre ($instance_name, $settings, $user); 
    
  $result_ok = false;
  $add_onload = "";  
  $show = "";
  
  // set default language
  if ($lang == "") $lang = "en";
  
  // check if input data is available
  if (
       empty ($mgmt_config['instances']) || 
       empty ($mgmt_config['abs_path_cms']) || !is_dir ($mgmt_config['abs_path_cms']) ||
       !valid_publicationname ($instance_name) || strlen ($instance_name) > 100 || specialchr ($instance_name, "-_") || 
       empty ($settings['abs_path_data']) || empty ($settings['abs_path_rep']) || 
       empty ($settings['password']) || empty ($settings['confirm_password']) || 
       !isset ($settings['realname']) || empty ($settings['language']) || empty ($settings['email']) || 
       empty ($settings['db_host']) || empty ($settings['db_username']) || empty ($settings['db_password']) || empty ($settings['db_name']) || 
       empty ($settings['smtp_host']) || empty ($settings['smtp_username']) || empty ($settings['smtp_password']) || empty ($settings['smtp_port']) || empty ($settings['smtp_sender']) ||  
       !valid_objectname ($user) || trim ($instance_name) == "config"
     )
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['required-input-is-missing'][$lang]."</span>\n";
  }
  // test if input data includes special characters incl. white spaces
  elseif (
           $instance_name == "config" || 
           specialchr ($instance_name, "-_") == true || 
           preg_match ('/\s/', $settings['db_host']) > 0 || preg_match ('/\s/', $settings['db_username']) > 0 || preg_match ('/\s/', $settings['db_name']) > 0 || 
           preg_match ('/\s/', $settings['smtp_host']) > 0 || preg_match ('/\s/', $settings['smtp_username']) > 0 || preg_match ('/\s/', $settings['smtp_port']) > 0 || preg_match ('/\s/', $settings['smtp_sender']) > 0
         )
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['special-characters-in-expressions-are-not-allowed'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-try-another-expression'][$lang]."\n";
  }
  // check write permissions in CMS
  elseif (!is_writeable ($mgmt_config['instances']) || !is_writeable ($mgmt_config['abs_path_temp']) || !is_writeable ($mgmt_config['abs_path_view']))
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['you-do-not-have-write-permissions'][$lang]."</span>\n";
  }
  // check if instance name exists already
  elseif (is_file ($mgmt_config['instances'].trim ($instance_name).".inc.php"))
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['the-instance-exists-already'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-try-another-expression'][$lang]."\n";
  }
  else
  {
    // load main config to access all settings
    require ($mgmt_config['abs_path_cms']."config/config.inc.php");
    
    $instance_name = trim ($instance_name);
    
    // correct and define path variables
    if (isset ($settings['url_path_rep']))
    {
      $url_path_rep = correctpath ($settings['url_path_rep'], "/");
      $url_path_rep = substr ($url_path_rep, strpos ($url_path_rep, "://")+3);
    }
    else $url_path_rep = "";
    
    if (isset ($settings['abs_path_rep']))
    {
      $abs_path_rep = correctpath ($settings['abs_path_rep'], "/");
    }
    else $abs_path_rep = "";
    
    if (isset ($settings['url_path_data']))
    {
      $url_path_data = correctpath ($settings['url_path_data'], "/");
      $url_path_data = substr ($url_path_data, strpos ($url_path_data, "://")+3);
    }
    else $url_path_data = "";
    
    if (isset ($settings['abs_path_data']))
    {
      $abs_path_data = correctpath ($settings['abs_path_data'], "/");
    }
    else $abs_path_data = "";

    // check write permissions in repositories
    if (!is_dir ($abs_path_data) || !is_writeable ($abs_path_data) || !is_dir ($abs_path_rep) || !is_writeable ($abs_path_rep))
    {
      if (!is_dir ($abs_path_data)) $result_data = mkdir ($abs_path_data, $mgmt_config['fspermission']);
      else $result_data = true;
      
      if (!is_dir ($abs_path_rep)) $result_rep = mkdir ($abs_path_rep, $mgmt_config['fspermission']);
      else $result_rep = true;
    
      if (!$result_data || !$result_rep)
      {
        $add_onload = "";
        $show = "<span class=hcmsHeadline>".$hcms_lang['you-do-not-have-write-permissions'][$lang]."</span>\n";
        
        $errcode = "10701";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|createinstance could not create ".$abs_path_data." or ".$abs_path_rep;
      }
    }
    
    // copy structure to internal repository
    if ($show == "" && is_dir ($abs_path_data))
    {
      $result = copyrecursive ($mgmt_config['abs_path_cms']."install/data/", $abs_path_data);
      if ($result == false) $show = "<span class=hcmsHeadline>".$hcms_lang['the-instance-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
    }
    else $show = "<span class=hcmsHeadline>".$hcms_lang['information-is-missing-or-you-do-not-have-write-permissions'][$lang]."</span><br />\n";
    
    // copy structure to external repository
    if ($show == "" && is_dir ($abs_path_rep))
    {
      $result = copyrecursive ($mgmt_config['abs_path_cms']."install/repository/", $abs_path_rep);
      if ($result == false) $show = "<span class=hcmsHeadline>".$hcms_lang['the-instance-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
    }
    else $show = "<span class=hcmsHeadline>".$hcms_lang['information-is-missing-or-you-do-not-have-write-permissions'][$lang]."</span><br />\n";
    
    // create database
    if ($show == "")
    {
      // connect to MySQL
      $mysqli = new mysqli ($settings['db_host'], $settings['db_username'], $settings['db_password']);      
      if ($mysqli->connect_errno) $show = "<span class=hcmsHeadline>DB error (".$mysqli->connect_errno."): ".$mysqli->connect_error."</span><br />\n";
      
      if ($show == "")
      {
        // select and create database
        if (!$mysqli->select_db ($settings['db_name']))
        {
          $sql = "CREATE DATABASE ".$settings['db_name'];
        
          if (!$mysqli->query ($sql)) $show = "<span class=hcmsHeadline>DB error (".$mysqli->errno."): ".$mysqli->error."</span><br />\n";
          elseif (!$mysqli->select_db ($settings['db_name'])) $show = "<span class=hcmsHeadline>DB error (".$mysqli->errno."): ".$mysqli->error."</span><br />\n";
        }
        
        // create tables
        if ($show == "")
        {
          $sql = loadfile ($mgmt_config['abs_path_cms']."database/rdbms/", "createtables.sql");
          
          if ($sql != "")
          {
            if (!$mysqli->multi_query ($sql)) $show = "<span class=hcmsHeadline>DB error (".$mysqli->errno."): ".$mysqli->error."</span><br />\n";
          }
          else $show = "<span class=hcmsHeadline>DB error: createtables.sql is missing</span><br />\n";
        }
        
        $mysqli->close();
      }
    }
    
    // create main config
    if ($show == "")
    {
      $config = loadfile ($mgmt_config['abs_path_cms']."install/", "config.inc.php");
      
      if ($config != "")
      {      
        $config = str_replace ("%url_path_cms%", substr ($mgmt_config['url_path_cms'], strpos ($mgmt_config['url_path_cms'], "://")+3), $config);
        $config = str_replace ("%abs_path_cms%", $mgmt_config['abs_path_cms'], $config);
        $config = str_replace ("%url_path_rep%", $url_path_rep, $config);
        $config = str_replace ("%abs_path_rep%", $abs_path_rep, $config);
        $config = str_replace ("%url_path_data%", $url_path_data, $config);
        $config = str_replace ("%abs_path_data%", $abs_path_data, $config);
        
        $config = str_replace ("%os_cms%", $mgmt_config['os_cms'], $config);
        
        $config = str_replace ("%instances%", $mgmt_config['instances'], $config);
        
        $config = str_replace ("%pdftotext%", $mgmt_parser['.pdf'], $config);
        $config = str_replace ("%antiword%", $mgmt_parser['.doc'], $config);
        $config = str_replace ("%gunzip%", $mgmt_uncompress['.gz'], $config);
        $config = str_replace ("%unzip%", $mgmt_uncompress['.zip'], $config);
        $config = str_replace ("%zip%", $mgmt_compress['.zip'], $config);
        $config = str_replace ("%unoconv%", getconfigvalue ($mgmt_docpreview), $config);
        $config = str_replace ("%convert%", getconfigvalue ($mgmt_imagepreview), $config);
        $config = str_replace ("%ffmpeg%", getconfigvalue ($mgmt_mediapreview), $config);
        $config = str_replace ("%yamdi%", $mgmt_mediametadata['.flv'], $config);
        $config = str_replace ("%exiftool%", getconfigvalue ($mgmt_mediametadata, '.jpg'), $config);
        
        $config = str_replace ("%dbhost%", $settings['db_host'], $config);
        $config = str_replace ("%dbuser%", $settings['db_username'], $config);
        $config = str_replace ("%dbpasswd%", $settings['db_password'], $config);
        $config = str_replace ("%dbname%", $settings['db_name'], $config);
          
        $config = str_replace ("%smtp_host%", $settings['smtp_host'], $config);
        $config = str_replace ("%smtp_username%", $settings['smtp_username'], $config);
        $config = str_replace ("%smtp_password%", $settings['smtp_password'], $config);
        $config = str_replace ("%smtp_port%", $settings['smtp_port'], $config);
        $config = str_replace ("%smtp_sender%", $settings['smtp_sender'], $config);
        
        $result = savefile ($mgmt_config['instances'], $instance_name.".inc.php", $config);
        
        if ($result == false)
        {
          $show = "<span class=hcmsHeadline>".$hcms_lang['the-instance-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
        
          $errcode = "10702";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|createinstance could not create ".$instance_name.".inc.php";
        }
      }
    }
    
    // set path for search engine
    if ($show == "")
    {
      // create main config
      $config = loadfile ($mgmt_config['abs_path_cms']."install/repository/search/", "search_config.inc.php");
      
      if ($config != "")
      {
        $config = str_replace ("%url_path_rep%", $url_path_rep, $config);
        $config = str_replace ("%abs_path_rep%", $abs_path_rep, $config);
        
        $result = savefile ($abs_path_rep."search/", "search_config.inc.php", $config);
        
        if ($result == false)
        {
          $show = "<span class=hcmsHeadline>".$hcms_lang['the-instance-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
          
          $errcode = "10703";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|createinstance could not create search/search_config.inc.php";
        }
      }
    }

    // create admin user
    if ($show == "")
    {
      // load new config before manipulating user
      require ($mgmt_config['instances'].$instance_name.".inc.php");
    
      if (!empty ($settings['user'])) $username = $settings['user'];
      else $username = "admin";
      
      // create admin user
      $result = createuser ("*Null*", $username, $settings['password'], $settings['confirm_password'], $user);
      if ($result['result'] == false) $show = "<span class=hcmsHeadline>".$result['message']."</span><br />\n";
    
      // edit admin user
      if ($show == "")
      {
        $result = edituser ("*Null*", $username, "", $settings['password'], $settings['confirm_password'], "1", $settings['realname'], $settings['language'], "standard", $settings['email'], "", "", "", $user);
        if ($result['result'] == false) $show = "<span class=hcmsHeadline>".$result['message']."</span><br />\n";
      }
    }

    // new instance was successfully created
    if ($show == "")
    {
      // eventsystem
      if ($eventsystem['oncreateinstance_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
        oncreateinstance_post ($instance_name, $settings, $user); 
    
      $result_ok = true;
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-instance-was-created-successfully'][$lang]."</span><br />\n".$hcms_lang['now-you-can-login-using-the-admin-user'][$lang]."\n";
    }
  }
  
  // save log
  include ($mgmt_config['abs_path_cms']."config.inc.php");
  savelog (@$error);

  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;  
  
  return $result;
}

// ------------------------- editinstance -----------------------------
// function: editinstance()
// input: instance name, content as string, user name  
// output: result array

// description:
// This function saves the instance configuration in the config file

function editinstance ($instance_name, $content, $user="sys")
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;
  
  // eventsystem
  if ($eventsystem['onsaveinstance_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
    onsaveinstance_pre ($instance_name, $content, $user); 
    
  $result_ok = false;
  $add_onload = "";  
  $show = "";
  
  // set default language
  if ($lang == "") $lang = "en";
  
  // check if input data is available
  // check if sent data is available
  if (!is_array ($mgmt_config) || trim ($content) == "" || !valid_publicationname ($instance_name) || empty ($mgmt_config['instances']) || !is_file ($mgmt_config['instances'].$instance_name.".inc.php") || !valid_objectname ($user) || trim ($instance_name) == "config")
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['required-input-is-missing'][$lang]."</span><br />\n".$hcms_lang['please-select-an-instance'][$lang]."\n";
  }
  else
  {
    // save content in file
    $result = savefile ($mgmt_config['instances'], $instance_name.".inc.php", trim ($content));
    
    if ($result == false)
    {
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-instance-could-not-be-saved'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
      
      $errcode = "10721";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|editinstance could not save ".$instance_name.".inc.php";
    }
    
    // load configuration of instance
    require ($mgmt_config['instances'].$instance_name.".inc.php");

    // instance was successfully deleted
    if ($show == "")
    {
      // eventsystem
      if ($eventsystem['onsaveinstance_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
        onsaveinstance_post ($instance_name, $content, $user); 
    
      $result_ok = true;
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-instance-configuration-was-saved-successfully'][$lang]."</span><br />\n";
    }
  }
  
  // save log
  include ($mgmt_config['abs_path_cms']."config.inc.php");
  savelog (@$error);

  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;  
  
  return $result;
}

// ------------------------- deleteinstance -----------------------------
// function: deleteinstance()
// input: instance name, settings array, user name  
// output: result array

// description:
// This function creates a new instance with all its files and the mySQL database

function deleteinstance ($instance_name, $user="sys")
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // eventsystem
  if ($eventsystem['ondeleteinstance_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
    ondeleteinstance_pre ($instance_name, $user); 
    
  $result_ok = false;
  $add_onload = "";  
  $show = "";
  
  // set default language
  if ($lang == "") $lang = "en";
  
  // check if file name is an attribute of a sent string
  if (strpos ($instance_name, ".php?") > 0)
  {
    // extract file name
    $instance_name = getattribute ($instance_name, "config");
  }
  
  // check if instance name holds the file name
  if (strpos ($instance_name, ".inc.php") > 0)
  {
    // extract instance name
    $instance_name = substr ($instance_name, 0, strpos ($instance_name, ".inc.php"));
  }

  // check if input data is available
  if (!is_array ($mgmt_config) || !valid_publicationname ($instance_name) || empty ($mgmt_config['instances']) || !is_file ($mgmt_config['instances'].$instance_name.".inc.php") || !valid_objectname ($user) || trim ($instance_name) == "config")
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['required-input-is-missing'][$lang]."</span><br />\n".$hcms_lang['please-select-an-instance'][$lang]."\n";
  }
  elseif (is_file ($mgmt_config['instances'].$instance_name.".inc.php"))
  {
    // load configuration of instance
    require ($mgmt_config['instances'].$instance_name.".inc.php");
    
    // delete internal repository
    if (is_dir ($mgmt_config['abs_path_data']))
    {
      $result = deletefile (getlocation ($mgmt_config['abs_path_data']), getobject ($mgmt_config['abs_path_data']), 1);
      
      if ($result == false)
      {
        $errcode = "10731";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|deleteinstance could not fully remove ".$mgmt_config['abs_path_data'];
      }
    }
    else
    {
      $errcode = "10732";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|deleteinstance could not access ".$mgmt_config['abs_path_data'];
    }
    
    // delete external repository
    if (is_dir ($mgmt_config['abs_path_rep']))
    {
      $result = deletefile (getlocation ($mgmt_config['abs_path_rep']), getobject ($mgmt_config['abs_path_rep']), 1);
      
      if ($result == false)
      {
        $errcode = "10733";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|deleteinstance could not fully remove ".$mgmt_config['abs_path_rep'];
      }
    }
    else
    {
      $errcode = "10734";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|deleteinstance could not access ".$mgmt_config['abs_path_rep'];
    }
    
    // delete database
    if (!empty ($mgmt_config['dbhost']) && !empty ($mgmt_config['dbuser']) && !empty ($mgmt_config['dbpasswd']) && !empty ($mgmt_config['dbname']))
    {
      // connect to MySQL
      $mysqli = new mysqli ($mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname']);      
      if ($mysqli->connect_errno) $show = "<span class=hcmsHeadline>DB error (".$mysqli->connect_errno."): ".$mysqli->connect_error."</span><br />\n";
      
      if ($show == "")
      {
        // delete database
        $sql = "DROP DATABASE IF EXISTS ".$mgmt_config['dbname'];

        if (!$mysqli->query ($sql)) $show = "<span class=hcmsHeadline>DB error (".$mysqli->errno."): ".$mysqli->error."</span><br />\n";
        
        $mysqli->close();
      }
    }
    else
    {
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-instance-information-cannot-be-accessed'][$lang]."</span><br />\n";
      
      $errcode = "10736";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|deleteinstance could not connect to the database ".$mgmt_config['dbname'];
    }
    
    // delete main config of instance
    if ($show == "")
    {
      $result = deletefile ($mgmt_config['instances'], $instance_name.".inc.php", 0);
      
      if ($result == false)
      {
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-instance-could-not-be-removed'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
      
        $errcode = "10711";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|deleteinstance could not delete ".$instance_name.".inc.php";
      }
    }
    
    // instance was successfully deleted
    if ($show == "")
    {
      // eventsystem
      if ($eventsystem['ondeleteinstance_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
        ondeleteinstance_post ($instance_name, $user); 
    
      $result_ok = true;
      $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-instance-was-deleted-successfully'][$lang]."</span><br />\n".$hcms_lang['all-instance-entries-were-removed-successfully'][$lang]."\n";
    }
  }
  else $show = "<span class=hcmsHeadline>".$hcms_lang['the-instance-information-cannot-be-accessed'][$lang]."</span><br />\n";
  
  // save log
  include ($mgmt_config['abs_path_cms']."config.inc.php");
  savelog (@$error);

  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;  
  
  return $result;
}

// ======================================= PUBLICATION OPERATIONS ==========================================

// ------------------------- createpublication -----------------------------
// function: createpublication()
// input: publication name, user name  
// output: result array

// description:
// This function creates a new publication with all its files

function createpublication ($site_name, $user="sys")
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // eventsystem
  if ($eventsystem['oncreatepublication_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
    oncreatepublication_pre ($site_name, $user); 
    
  $result_ok = false;
  
  // set default language
  if ($lang == "") $lang = "en";
  
  // check if sent data is available
  if (!valid_publicationname ($site_name) || strlen ($site_name) > 100 || !valid_objectname ($user))
  {
    $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
    $show = "<span class=hcmsHeadline>".$hcms_lang['required-publication-name-is-missing'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-enter-a-name'][$lang]."\n";
  }
  // test if site name includes special characters
  elseif (specialchr ($site_name, "-_") == true)
  {
    $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
    $show = "<span class=hcmsHeadline>".$hcms_lang['special-characters-in-expressions-are-not-allowed'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-try-another-expression'][$lang]."\n";
  }
  else
  {
    $site_name = trim ($site_name); 
  
    // load inheritance database
    $inherit_db = inherit_db_load ($user);
    
    if ($inherit_db != false)
    { 
      $test = true;
    
      // check if site already exists
      if (is_array ($inherit_db) && sizeof ($inherit_db) > 0)
      {
        foreach ($inherit_db as $inherit_db_record)
        {    
          if ($site_name == $inherit_db_record['parent'])
          {
            $test = false;
            
            // unlock file
            inherit_db_close ($user);
            
            // return message if site exists already
            $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
            $show = "<span class=hcmsHeadline>".$hcms_lang['the-publication-exists-already'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-try-another-expression'][$lang]."\n";
                  
            break;
          }
        }
      }
  
      if ($test != false) 
      {          
        // -------------------------------------- create new files for site ------------------------------------------    
        // usergroup       
        if ($test != false) 
        {
          $errcode = "10102";
          $test = @copy ($mgmt_config['abs_path_cms']."xmlschema/usergroup.schema.xml.php", $mgmt_config['abs_path_data']."user/".$site_name.".usergroup.xml.php");
          
          if ($test == false) $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|copy failed for /data/user/".$site_name.".usergroup.xml.php";
          else
          {
            $data = loadfile ($mgmt_config['abs_path_data']."user/", $site_name.".usergroup.xml.php");
            
            if ($data != "")
            {
              // add page access
              $data = str_replace ("<pageaccess></pageaccess>", "<pageaccess>%page%/".$site_name."/|</pageaccess>", $data);
              // add comp access
              $data = str_replace ("<compaccess></compaccess>", "<compaccess>%comp%/".$site_name."/|</compaccess>", $data);
              // save usergroups
              if ($data != "") $data = savefile ($mgmt_config['abs_path_data']."user/", $site_name.".usergroup.xml.php", $data);
            }
          }
        }
        
        // template media index
        if ($test != false) 
        {
          $errcode = "10104";
          $test = savefile ($mgmt_config['abs_path_data']."media/", $site_name.".media.tpl.dat", "");
          if ($test == false) $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|savefile failed for /data/media/".$site_name.".media.tpl.dat";
        }
        
        // media mapping file
        if ($test != false) 
        {
          // get default mapping definition
          $mapping_data = getmapping ($site_name);
          // creating mapping from definition
          $mapping_data = createmapping ($site_name, $mapping_data);
                  
          $errcode = "10134";
          $test = savefile ($mgmt_config['abs_path_data']."media/", $site_name.".media.map.php", $mapping_data);
          if ($test == false) $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|savefile failed for /data/media/".$site_name.".media.map.php";
        }

        // link
        if ($test != false) 
        {
          $errcode = "10135";
          $test = savefile ($mgmt_config['abs_path_data']."link/", $site_name.".link.dat", "container:|object|:|link|\n");
          if ($test == false) $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|savefile failed for /data/link/".$site_name.".link.dat";
        }
        
        // internal template repository  
        $dir_temp = $mgmt_config['abs_path_template'].$site_name;
        
        if ($test != false && @!is_dir ($dir_temp)) 
        {
          $errcode = "10137";
          $test = @mkdir ($dir_temp, $mgmt_config['fspermission']);
          if ($test != false) $test = @copy ($mgmt_config['abs_path_cms']."xmlschema/template_default.schema.xml.php", $dir_temp."/default.meta.tpl");
          if ($test == false) $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|mkdir failed for ".$dir_temp;
        }    
        
        // internal customer repository  
        $dir_temp = $mgmt_config['abs_path_data']."customer/".$site_name;
        
        if ($test != false && @!is_dir ($dir_temp)) 
        {
          $errcode = "10138";
          $test = @mkdir ($dir_temp, $mgmt_config['fspermission']);
          if ($test == false) $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|mkdir failed for ".$dir_temp;
        }   
        
        // internal workflow repository  
        $dir_temp = $mgmt_config['abs_path_data']."workflow/".$site_name;
        
        if ($test != false && @!is_dir ($dir_temp)) 
        {
          $errcode = "10139";
          $test = @mkdir ($dir_temp, $mgmt_config['fspermission']);
          if ($test == false) $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|mkdir failed for ".$dir_temp;
        }    
          
        // content media repository  
        if ($test != false)
        {
          if (is_array ($mgmt_config['abs_path_media'])) $dir_temp_array = $mgmt_config['abs_path_media'];
          else $dir_temp_array[] = $mgmt_config['abs_path_media'];
          
          foreach ($dir_temp_array as $dir_temp)
          {
            if (@!is_dir ($dir_temp.$site_name)) 
            {
              $errcode = "10140";
              $test = @mkdir ($dir_temp.$site_name, $mgmt_config['fspermission']);
              
              if ($test == false) $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|mkdir failed for ".$dir_temp.$site_name;
              // remote client
              else remoteclient ("save", "abs_path_media", $site_name, $dir_temp, "", $site_name, "");   
            }
          }
        }   
      
        // template media repository  
        $dir_temp = $mgmt_config['abs_path_tplmedia'].$site_name;
        
        if ($test != false && @!is_dir ($dir_temp)) 
        {
          $errcode = "10141";
          $test = @mkdir ($dir_temp, $mgmt_config['fspermission']);
          
          if ($test == false) $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|mkdir failed for ".$dir_temp;
          // remote client
          else remoteclient ("save", "abs_path_tplmedia", $site_name, $mgmt_config['abs_path_tplmedia'], "", $site_name, "");           
        }  
        
        // component repository
        $dir_temp = $mgmt_config['abs_path_comp'].$site_name;
        
        if ($test != false && @!is_dir ($dir_temp)) 
        {
          $errcode = "10142";
          $test = @mkdir ($dir_temp, $mgmt_config['fspermission']);
          
          if ($test == false) $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|createfolder failed for ".$dir_temp;
          // remote client
          else remoteclient ("save", "abs_path_comp", $site_name, $mgmt_config['abs_path_comp'], "", $site_name, "");           
        }  
        
        // create publication config so the explorer shows the new publication
        if ($test != false)
        {
          $setting = array();
          editpublication ($site_name, $setting, $user);
        }
        
        if ($test != false)
        {
          // grant current user administrator permissions for the new site
          $userdata = loadlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php", 5);
       
          if ($userdata != false)
          {
            $user_array = selectcontent ($userdata, "<user>", "<login>", $user);
                   
            if (is_array ($user_array)) $user_node = $user_array[0];
            else $user_node = "";   
       
            // check publication access
            if ($user_node != "" && substr_count ($user_node, "<publication>".$site_name."</publication>") == 0) 
            {
              // get password to reregister session after adding permissions
              $passwd_node = getcontent ($user_node, "<password>");
              if (!empty ($passwd_node[0])) $passwd = $passwd_node[0];
              else $passwd = "";
              
              $memberof_schema_xml = loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "memberof.schema.xml.php");          
              $memberof_node = setcontent ($memberof_schema_xml, "<memberof>", "<publication>", $site_name, "", "");
              $memberof_node = setcontent ($memberof_node, "<memberof>", "<usergroup>", "|Administrator|", "", "");
              
              $user_node_new = insertcontent ($user_node, $memberof_node, "<user>");
         
              if ($user_node_new != false) 
              {
                $userdata = updatecontent ($userdata, $user_node, $user_node_new);          
             
                if ($userdata != false)
                {
                  $test = savelockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php", $userdata);
                  
                  if ($test == false)
                  {
                    $errcode = "10322";
                    $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|savelockfile failed for user $user in user.xml.php";     
                           
                    unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");             
                  }
                }    
                else 
                {
                  $errcode = "20301";
                  $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|updatecontent failed for user $user in user.xml.php";
                       
                  unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
                  $test = false;
                }                
              }   
              else
              {
                $errcode = "403101";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|publication access for user $user exists already in user.xml.php";
                     
                unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
                $test = false;            
              }   
            }
            else 
            {
              $errcode = "10302";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|user $user does not exist in user.xml.php";
              
              unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
              $test = false;
            }           
          }
          else 
          {
            $errcode = "10303";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|loadlockfile failed for user $user for user.xml.php";
            
            unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
            $test = false;
          } 
        }     
      
        // insert site_name value into inheritance database
        if ($test != false) 
        {
          $inherit_db = inherit_db_insertparent ($inherit_db, $site_name, "");
        
          if ($inherit_db != false)
          {
            // save site file
            $test = inherit_db_save ($inherit_db, $user);        
        
            if ($test != false)
            {        
              // set user permission for new publication and register them in session
              $_SESSION['hcms_siteaccess'][] = $site_name;
              $_SESSION['hcms_pageaccess'][$site_name]['Administrator'] = deconvertpath ("%page%/".$site_name."/|", "file");
              $_SESSION['hcms_compaccess'][$site_name]['Administrator'] = deconvertpath ("%comp%/".$site_name."/|", "file");
        
              // get usergroup information
              $usergroupdata = loadfile ($mgmt_config['abs_path_cms']."xmlschema/", "usergroup.schema.xml.php");
              
              if ($usergroupdata != false)
              {
                $usergroupnode = selectcontent ($usergroupdata, "<usergroup>", "<groupname>", "Administrator");
                
                if ($usergroupnode != false)
                {
                  $userpermission = getcontent ($usergroupnode[0], "<permission>");
                              
                  $permission_str[$site_name]['Administrator'] = $userpermission[0];
                  $globalpermission_new = globalpermission ($site_name, $permission_str);
                  $localpermission_new = localpermission ($site_name, $permission_str);
                  
                  if ($globalpermission_new != false)
                  {
                    if (is_array ($_SESSION['hcms_globalpermission'])) $_SESSION['hcms_globalpermission'] = array_merge ($_SESSION['hcms_globalpermission'], $globalpermission_new);
                    else $_SESSION['hcms_globalpermission'] = $globalpermission_new;
                  } 
                  
                  if ($localpermission_new != false)
                  {
                    if (is_array ($_SESSION['hcms_localpermission'])) $_SESSION['hcms_localpermission'] = array_merge ($_SESSION['hcms_localpermission'], $localpermission_new);
                    else $_SESSION['hcms_localpermission'] = $localpermission_new;
                  }
                }
              }
              
              // register new checksum
              killsession ($user, false);
              writesession ($user, $passwd, createchecksum ());
              
              // create root folder objects
              createobject ($site_name, $mgmt_config['abs_path_comp'].$site_name."/", ".folder", "default.meta.tpl", "sys");
              
              // eventsystem
              if ($eventsystem['oncreatepublication_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
                oncreatepublication_post ($site_name, $user);            
             
              $add_onload = "parent.frames['mainFrame'].location='site_edit_form.php?site=*Null*&preview=no&site_name=".url_encode($site_name)."'; setTimeout (function(){parent.frames['controlFrame'].location='control_site_menu.php?site=".url_encode($site_name)."'}, 1000); ";
              $show = "<span class=hcmsHeadline>".$hcms_lang['the-publication-was-created-successfully'][$lang]."</span><br />\n".$hcms_lang['now-you-can-edit-the-publication'][$lang]."\n";
              
              // success
              $result_ok = true;
            }
            else
            {
              // unlock file
              inherit_db_close ($user);
              
              $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
              $show = "<span class=hcmsHeadline>".$hcms_lang['the-publication-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";          
            }
          }
          else
          {
            // unlock file
            inherit_db_close ($user);
        
            $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
            $show = "<span class=hcmsHeadline>".$hcms_lang['the-publication-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['an-error-occured-in-the-data-manipulation'][$lang]."\n";
          }
        }
        else
        {
          // unlock file
          inherit_db_close ($user);
        }
      }
    }
    else
    {
      // unlock file
      inherit_db_close ($user);
    
      $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-publication-information-cannot-be-accessed'][$lang]."</span><br />\n".$hcms_lang['the-publication-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
    }
  }
  
  // save log
  savelog (@$error);

  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;  
  
  return $result;  
}

// ------------------------- editpublication -----------------------------
// function: editpublication()
// input: publication name, publication settings array, user name  
// output: result array

// description:
// This function saves the settings of a publication

function editpublication ($site_name, $setting, $user="sys")
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  $result_ok = false;
  $exclude_folders_new = "";

  // set default language
  if ($lang == "") $lang = "en";
  
  if (valid_publicationname ($site_name) && is_array ($setting) && valid_objectname ($user))
  {
    // set boolean values
    if (array_key_exists ('site_admin', $setting) && $setting['site_admin'] == true) $site_admin_new = "true";
    else $site_admin_new = "false";
    
    if (array_key_exists ('linkengine', $setting) && $setting['linkengine'] == true) $linkengine_new = "true";
    else $linkengine_new = "false";
    
    if (array_key_exists ('sendmail', $setting) && $setting['sendmail'] == true) $sendmail_new = "true";
    else $sendmail_new = "false";
    
    if (array_key_exists ('webdav', $setting) && $setting['webdav'] == true) $webdav_new = "true";
    else $webdav_new = "false";
    
    if (array_key_exists ('http_incl', $setting) && $setting['http_incl'] == true) $http_incl_new = "true";
    else $http_incl_new = "false";
    
    if (array_key_exists ('inherit_obj', $setting) && $setting['inherit_obj'] == true) $inherit_obj_new = "true";
    else $inherit_obj_new = "false";
    
    if (array_key_exists ('inherit_comp', $setting) && $setting['inherit_comp'] == true) $inherit_comp_new = "true";
    else $inherit_comp_new = "false";
    
    if (array_key_exists ('inherit_tpl', $setting) && $setting['inherit_tpl'] == true) $inherit_tpl_new = "true";
    else $inherit_tpl_new = "false";
    
    if (array_key_exists ('specialchr_disable', $setting) && $setting['specialchr_disable'] == true) $specialchr_disable_new = "true";
    else $specialchr_disable_new = "false";
    
    if (array_key_exists ('dam', $setting) && $setting['dam'] == true) $dam_new = "true";
    else $dam_new = "false";
    
    if (array_key_exists ('taxonomy', $setting) && $setting['taxonomy'] == true) $taxonomy_new = "true";
    else $taxonomy_new = "false";
  
    if (array_key_exists ('upload_userinput', $setting) && $setting['upload_userinput'] == true) $upload_userinput = "true";
    else $upload_userinput = "false";
    
    if (array_key_exists ('upload_pages', $setting) && $setting['upload_pages'] == true) $upload_pages = "true";
    else $upload_pages = "false";
    
    if (array_key_exists ('crypt_content', $setting) && $setting['crypt_content'] == true) $crypt_content = "true";
    else $crypt_content = "false";
    
    // create htaccess and web.config files for DAM usage
    if ($dam_new == "true")
    {
      if (!is_file ($mgmt_config['abs_path_media'].$site_name."/.htaccess")) savefile ($mgmt_config['abs_path_media'].$site_name."/", ".htaccess", "Deny from all");
      if (!is_file ($mgmt_config['abs_path_media'].$site_name."/web.config")) savefile ($mgmt_config['abs_path_media'].$site_name."/", "web.config", '<?xml version="1.0" encoding="utf-8" ?>
<configuration>  
  <system.webServer>
      <security>
          <authorization>
              <remove users="*" roles="" verbs="" />
              <add accessType="Allow" roles="Administrators" />
          </authorization>
      </security>
  </system.webServer>
</configuration>');
    }
    // or delete files
    else
    {
      if (is_file ($mgmt_config['abs_path_media'].$site_name."/.htaccess")) deletefile ($mgmt_config['abs_path_media'].$site_name."/", ".htaccess");
      if (is_file ($mgmt_config['abs_path_media'].$site_name."/web.config")) deletefile ($mgmt_config['abs_path_media'].$site_name."/", "web.config");
    }
    
    // share social media links
    if (array_key_exists ('sharesociallink', $setting) && $setting['sharesociallink'] == true) $sharesociallink_new = "true";
    else $sharesociallink_new = "false";
    
    // YouTube
    if (array_key_exists ('youtube', $setting) && $setting['youtube'] == true)  $youtube_new = "true";
    else $youtube_new = "false";
      
    // theme
    if (array_key_exists ('theme', $setting)) $theme_new = $setting['theme'];
    else $theme_new = "Standard";    
    
    // storage limit
    if (array_key_exists ('storage_limit', $setting) && is_numeric ($setting['storage_limit'])) $storage_limit_new = $setting['storage_limit'];
    else $storage_limit_new = "''";
    
    // storage type
    if (array_key_exists ('storage_type', $setting) && trim ($setting['storage_type']) != "") $storage_type_new = $setting['storage_type'];
    else $storage_type_new = "''";
    
    // set codepage if none is given
    if (!array_key_exists ('default_codepage', $setting) || $setting['default_codepage'] == "") $default_codepage_new = "UTF-8";
    else $default_codepage_new = $setting['default_codepage'];
    
    // watermark for images
    if (!array_key_exists ('watermark_image', $setting) || $setting['watermark_image'] == "") $watermark_image = "";
    else $watermark_image = $setting['watermark_image'];
    
    // watermark for videos
    if (!array_key_exists ('watermark_video', $setting) || $setting['watermark_video'] == "") $watermark_video = "";
    else $watermark_video = $setting['watermark_video'];
    
    // correct path for excluded folders
    if (array_key_exists ('url_path_page', $setting)) $url_path_page_new = correctpath ($setting['url_path_page'], "/");
    else $url_path_page_new = "";
    if (array_key_exists ('abs_path_page', $setting)) $abs_path_page_new = correctpath ($setting['abs_path_page'], "/");
    else $abs_path_page_new = "";

    if (array_key_exists('exclude_folders', $setting) && $setting['exclude_folders'] != "")
    {
      $folder_array = explode (";", $setting['exclude_folders']);

      foreach ($folder_array as $folder)
      {
        if (substr_count ($folder, "\\") > 0)
        {
          $folder = correctpath ($folder, "/");
        }
    
        if ($folder != "") $exclude_folders_new .= $folder.";";
      }
    }
    
    // allowed IP addresses
    if (is_array ($setting) && array_key_exists ('allow_ip', $setting) && $setting['allow_ip'] != "")
    {
      $allow_ip_new = splitstring ($setting['allow_ip']);
      $allow_ip_new = implode (";", $allow_ip_new);
    }
    else $allow_ip_new = "";
    
    // set mailserver
    if (array_key_exists('mailserver', $setting)) $mailserver_new = $setting['mailserver'];
    else $mailserver_new = "";
    
    // set OS
    if (array_key_exists('publ_os', $setting)) $publ_os_new = $setting['publ_os'];
    else $publ_os_new = "UNIX";
    
    //set remote client
    if (array_key_exists('remoteclient', $setting)) $remoteclient_new = $setting['remoteclient'];
    else $remoteclient_new = "";
    
    // config file of management system
    $site_mgmt_config = "<?php
// ---------------------------------- content management server ----------------------------------------
// Define if users can access the publication
// configuration.
// If you are an ISP deactivate (false) this
// feature for you customers, so that they are
// not able to create additional publications.
// The ISP itself must have this feature
// enabled (true) to create new websites.
\$mgmt_config['".$site_name."']['site_admin'] = ".$site_admin_new.";

// URL and absolute path to the website
// (e.g. http://www.yourdomain.com/)
// (e.g. /home/domain/)
\$mgmt_config['".$site_name."']['url_path_page'] = \"".$url_path_page_new."\";
\$mgmt_config['".$site_name."']['abs_path_page'] = \"".$abs_path_page_new."\";

// Exclude directories (folders) for hyperCMS Navigator view
// absolute path required!
// use ';' as delimiter.
// e.g. \$mgmt_config['Publication']['exclude_folders'] = \"/home/domain/directory1/;/home/domain/directory2/;\"
// Be aware: if you exclude the doc root of your (virtual) webserver you will see no folders at all!
\$mgmt_config['".$site_name."']['exclude_folders'] = \"".$exclude_folders_new."\";

// Allow access for the follwomg IP addresses.
// use ';' as delimiter.
\$mgmt_config['".$site_name."']['allow_ip'] = \"".$allow_ip_new."\";

// Activate multimedia component access through hyperCMS native WebDAV server.
// true = WebDAV active, false = WebDAV inactive
\$mgmt_config['".$site_name."']['webdav'] = ".$webdav_new.";

// Activate the link mangement engine (true)
// or deactivate if (false)
// the link management engine will serve a
// xml file where all links will be stored an
// updated if changes
// may occur. without this engine broken links
// can't be deactivated.
// true = on, false = off
\$mgmt_config['".$site_name."']['linkengine'] = ".$linkengine_new.";

// Default codepage, if no codepage is
// defined in template
\$mgmt_config['".$site_name."']['default_codepage'] = \"".$default_codepage_new."\";

// Activate (true) or deactivate (false)
// sendmail (user will be informed via SMTP)
\$mgmt_config['".$site_name."']['sendmail'] = ".$sendmail_new.";

// Mailserver name, necessary if sendmail is activated
// (an account named hyperCMS@mailserver should be available)
\$mgmt_config['".$site_name."']['mailserver'] = \"".$mailserver_new."\";

// Special characters in object and folder names
// allow (false) or forbid (true) special characters in object and folder names.
\$mgmt_config['".$site_name."']['specialchr_disable'] = ".$specialchr_disable_new.";

// Use only as DAM
// enable (false) or disable (true) restricted system usage as DAM
\$mgmt_config['".$site_name."']['dam'] = ".$dam_new.";

// Use taxonomy
// enable (false) or disable (true) taxonomy browsing and search integration
\$mgmt_config['".$site_name."']['taxonomy'] = ".$taxonomy_new.";

// User must provide metadata for file uploads
// enable (true) or disable (false) user input for metadata right after file upload
\$mgmt_config['".$site_name."']['upload_userinput'] = ".$upload_userinput.";

// Enable direct file uploads in page structure (if used as CMS)
// enable (true) or disable (false) file upload (files are not managed by the system!) 
\$mgmt_config['".$site_name."']['upload_pages'] = ".$upload_pages.";

// Storage limit
// storage limit for all multimedia files (assets) in MB
\$mgmt_config['".$site_name."']['storage_limit'] = ".$storage_limit_new.";

";

  if (is_dir ($mgmt_config['abs_path_cms']."connector/")) $site_mgmt_config .= "
// Storage type
// storage type for all multimedia files (assets), possible values are 'local', 'cloud' and 'both'
\$mgmt_config['".$site_name."']['storage_type'] = \"".$storage_type_new."\";

";
  $site_mgmt_config .= "
// Encrypt content on server
// enable (false) or disable (true) encryption of content
\$mgmt_config['".$site_name."']['crypt_content'] = ".$crypt_content.";

// Watermark 
// watermark options for images and videos
\$mgmt_config['".$site_name."']['watermark_image'] = \"".$watermark_image."\";
\$mgmt_config['".$site_name."']['watermark_video'] = \"".$watermark_video."\";

";

  if (is_dir ($mgmt_config['abs_path_cms']."connector/")) $site_mgmt_config .= "
  // Allow sharing of social media links
// enable (false) or disable (true) restricted system usage of social media link sharing. 
\$mgmt_config['".$site_name."']['sharesociallink'] = ".$sharesociallink_new.";

// Allow upload to Youtube
// enable (false) or disable (true) restricted system usage of youtube uploader. 
// youtube_token is the permanent session key for the upload interface
\$mgmt_config['".$site_name."']['youtube'] = ".$youtube_new.";

";
  $site_mgmt_config .= "
// Theme
\$mgmt_config['".$site_name."']['theme'] = \"".$theme_new."\";

// Set component inclusion type:
// Components can be included using file system access of via HTTP
// Set the value of this parameter true if you want to use HTTP inclusion.
// Please note: On Win32 OS the HTTP inclusion won't work!
\$mgmt_config['".$site_name."']['http_incl'] = ".$http_incl_new.";

// Enable (true) or disable (false) cut, copy and paste of objects
\$mgmt_config['".$site_name."']['inherit_obj'] = ".$inherit_obj_new.";

// Enable (true) or disable (false) inheritance of components
\$mgmt_config['".$site_name."']['inherit_comp'] = ".$inherit_comp_new.";

// Enable (true) or disable (false) inheritance of templates
\$mgmt_config['".$site_name."']['inherit_tpl'] = ".$inherit_tpl_new.";

// Enable (true) or disable (false) Remote Client 
\$mgmt_config['".$site_name."']['remoteclient'] = \"".$remoteclient_new."\";
?>";
    
    // set path values
    if (isset ($setting['url_publ_page'])) $url_publ_page_new = correctpath ($setting['url_publ_page'], "/");
    else $url_publ_page_new = "";
    
    if (isset ($setting['abs_publ_page'])) $abs_publ_page_new = correctpath ($setting['abs_publ_page'], "/");
    else $abs_publ_page_new = "";
    
    if (isset ($setting['url_publ_rep'])) $url_publ_rep_new = correctpath ($setting['url_publ_rep'], "/");
    else $url_publ_rep_new = "";
    
    if (isset ($setting['abs_publ_rep'])) $abs_publ_rep_new = correctpath ($setting['abs_publ_rep'], "/");
    else $abs_publ_rep_new = "";
    
    if (isset ($setting['abs_publ_app'])) $abs_publ_app_new = correctpath ($setting['abs_publ_app'], "/");
    else $abs_publ_app_new = "";
    
    // config file of publication system
    $site_publ_config_ini = "; ---------------------------------- publication server ----------------------------------------
; This config file is used on the publication server.
; This configuration defines the local path values for the link management.
; Please note: This configuration file and the LiveLink function file
; must be located in the certain directories, see manual!
; Please note: Add a \"/\" to the end of each path value!

; Set URL and absolute path of the website root:
url_publ_page = \"".$url_publ_page_new."\"
abs_publ_page = \"".$abs_publ_page_new."\"

; Set URL and absolute path to the repository:
; The local repository includes:
; - component repository
; - content media repository
; - template media repository
; - link index repository
; - local configuration
url_publ_rep = \"".$url_publ_rep_new."\"
abs_publ_rep = \"".$abs_publ_rep_new."\"

; Set component inclusion type:
; Components can be included using file system access of via HTTP
; Set the value of this parameter true if you want to use HTTP inclusion.
; Please note: On Win32 OS the HTTP inclusion won't work!
http_incl = ".$http_incl_new."

; URL and absolute path to the configuration:
url_publ_config = \"".$url_publ_rep_new."config/\"
abs_publ_config = \"".$abs_publ_rep_new."config/\"

; URL, relative and absolute path to the component repository:
url_publ_comp = \"".$url_publ_rep_new."component/\"
abs_publ_comp = \"".$abs_publ_rep_new."component/\"
rel_publ_comp = \"".str_replace ($abs_publ_app_new, "/", $abs_publ_rep_new."component/")."\"

; URL and absolute path to the link index repository:
url_publ_link = \"".$url_publ_rep_new."link/\"
abs_publ_link = \"".$abs_publ_rep_new."link/\"
";
$site_publ_config_ini .= "; URL and absolute path to the content media repository:
url_publ_media = \"".$url_publ_rep_new."media_cnt/\"
abs_publ_media = \"".$abs_publ_rep_new."media_cnt/\"

; URL and absolute path to the template media repository:
url_publ_tplmedia = \"".$url_publ_rep_new."media_tpl/\"
abs_publ_tplmedia = \"".$abs_publ_rep_new."media_tpl/\"

; absolute path of the application on the file system of the application server:
abs_publ_app = \"".$abs_publ_app_new."\"

; OS on presentation server. this setting is necessary for the method of
; including components via http depending on OS.
; use 'WIN' for Windows OS (works also for UNIX derivates)
; use 'UNIX' for all UNIX derivates (for better performance on HTTP inclusion of components)
publ_os = \"".$publ_os_new."\"

; Allow access for the follwomg IP addresses.
allow_ip = \"".$allow_ip_new."\";";

    // config file of publication system for java
    $site_publ_config_prop = "# ---------------------------------- publication server ----------------------------------------
# This config file is used on the publication server.
# This configuration defines the local path values for the link management.
# Please note: This configuration file and the LiveLink function file
# must be located in certain directories, see manual!
# Please note: Add a \"/\" to the end of each path value!

# Set URL and absoulte path of the website root:
url_publ_page = ".$url_publ_page_new."
abs_publ_page = ".$abs_publ_page_new."

# Set URL and absolute path to the repository:
# The local repository includes:
# - component repository
# - content media repository
# - template media repository
# - link index repository
# - local configuration
url_publ_rep = ".$url_publ_rep_new."
abs_publ_rep = ".$abs_publ_rep_new."

# Set component inclusion type:
# Components can be included using file system access of via HTTP
# Set the value of this parameter true if you want to use HTTP inclusion.
# Please note: On Win32 OS the HTTP inclusion won't work!
http_incl = ".$http_incl_new."

# URL and absolute path to the configuration:
url_publ_config = ".$url_publ_rep_new."config/
abs_publ_config = ".$abs_publ_rep_new."config/

# URL, relative and absolute path to the component repository:
url_publ_comp = ".$url_publ_rep_new."component/
abs_publ_comp = ".$abs_publ_rep_new."component/
rel_publ_comp = ".str_replace ($abs_publ_app_new, "/", $abs_publ_rep_new."component/")."

# URL and absolute path to the link index repository:
url_publ_link = ".$url_publ_rep_new."link/
abs_publ_link = ".$abs_publ_rep_new."link/

# URL and absolute path to the content media repository:
url_publ_media = ".$url_publ_rep_new."media_cnt/
abs_publ_media = ".$abs_publ_rep_new."media_cnt/

# URL and absolute path to the template media repository:
url_publ_tplmedia = ".$url_publ_rep_new."media_tpl/
abs_publ_tplmedia = ".$abs_publ_rep_new."media_tpl/

# absolute path of the application on the file system of the application server:
abs_publ_app = ".$abs_publ_app_new."

# OS on presentation server. this setting is necessary for the method of
# including components via http depending on OS.
# use 'WIN' for Windows OS (works also for UNIX derivates)
# use 'UNIX' for all UNIX derivates (for better performance on HTTP inclusion of components)
publ_os = ".$publ_os_new."

# Allow access for the follwomg IP addresses.
allow_ip = ".$allow_ip_new;
    
    
    // save config files of management system and publication target
    if ($site_mgmt_config != "" && $site_publ_config_ini != "" && $site_publ_config_prop != "")
    {
      // eventsystem
      if ($eventsystem['onsavepublication_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0))
        onsavepublication_pre ($site_name, $site_mgmt_config, $site_publ_config_ini, $site_publ_config_prop, $user);
    
      // Management Config
      $test = savefile ($mgmt_config['abs_path_data']."config/", $site_name.".conf.php", trim ($site_mgmt_config));

      // Publication Config INI
      $test = savefile ($mgmt_config['abs_path_rep']."config/", $site_name.".ini", trim ($site_publ_config_ini));
  
      if ($test == true)
      {
        // remote client
        remoteclient ("save", "abs_path_rep", $site_name, $mgmt_config['abs_path_rep']."config/", "", $site_name.".ini", "");    
      }
      
      // Publication Config PROP   
      $test = savefile ($mgmt_config['abs_path_rep']."config/", $site_name.".properties", trim ($site_publ_config_prop));    
      
      if ($test == true)
      {      
        // remote client
        remoteclient ("save", "abs_path_rep", $site_name, $mgmt_config['abs_path_rep']."config/", "", $site_name.".properties", "");      
      }
      
      // try to create page root directory
      if (!is_file ($abs_path_page_new))
      {
        @mkdir ($abs_path_page_new, $mgmt_config['fspermission']);

        // remote client (special case, the root equals location and folder, therefore only the root path may be submitted)
        remoteclient ("save", "abs_path_page", $site_name, "", "", "", "");       
      }
      
      // try to create page root folder object
      if (!is_file ($abs_path_page_new.".folder"))
      {
        createobject ($site_name, $abs_path_page_new, ".folder", "default.meta.tpl", "sys");
      }
    
      if ($test == true)
      {        
        // eventsystem
        if ($eventsystem['onsavepublication_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0))
          onsavepublication_post ($site_name, $site_mgmt_config, $site_publ_config_ini, $site_publ_config_prop, $user);
    
        $add_onload = "";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-publication-configuration-was-saved-successfully'][$lang]."</span>\n";
        
        // success
        $result_ok = true;
      }
      else
      {
        $add_onload = "";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-publication-information-cannot-be-accessed'][$lang]."</span>\n".$hcms_lang['the-publication-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
      }
    }
    else
    {
      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-publication-information-cannot-be-accessed'][$lang]."</span>\n".$hcms_lang['the-publication-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
    }
  }
  else
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['required-input-is-missing'][$lang]."</span>\n";
  }
  
  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;  
  
  return $result;    
}

// ------------------------- editpublicationsetting -----------------------------
// function: editpublicationsetting()
// input: publication name, publication settings name ['site_admin','linkengine','sendmail','webdav','http_incl','inherit_obj','inherit_comp','inherit_tpl','specialchr_disable','default_codepage','exclude_folders'], value, user name  
// output: true/false

// description:
// This function can be used to edit a single settings of a publication

function editpublicationsetting ($site_name, $setting, $value, $user="sys")
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  $result_ok = false;
  
  if (valid_publicationname ($site_name) && is_string ($setting) && valid_objectname ($user))
  {        
    // load Management Config
    $site_mgmt_config = loadfile ($mgmt_config['abs_path_data']."config/", $site_name.".conf.php");
    
    // eventsystem
    if ($eventsystem['onsavepublication_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0))
      onsavepublication_pre ($site_name, $site_mgmt_config, "", "", $user);
    
    if ($site_mgmt_config != "")
    {
      $site_mgmt_records = explode (PHP_EOL, $site_mgmt_config);
      $site_mgmt_config = "";
      
      foreach ($site_mgmt_records as $record)
      {
        if (substr_count ($record, "\$mgmt_config['".$site_name."']['".$setting."']") == 1)
        {
          $site_mgmt_config .= "\$mgmt_config['".$site_name."']['".$setting."'] = \"".str_replace ("\"", "\\\"", $value)."\";\n";
        }
        else $site_mgmt_config .= $record."\n";
      }
  
      if ($site_mgmt_config != "")
      {
        // save Management Config
        $test = savefile ($mgmt_config['abs_path_data']."config/", $site_name.".conf.php", trim ($site_mgmt_config));
  
        if ($test == true)
        {        
          // eventsystem
          if ($eventsystem['onsavepublication_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0))
            onsavepublication_post ($site_name, $site_mgmt_config, "", "", $user);
      
          $add_onload = "";
          $show = "<span class=hcmsHeadline>".$hcms_lang['the-publication-configuration-was-saved-successfully'][$lang]."</span>\n";
          
          // success
          $result_ok = true;
        }
        else
        {
          $add_onload = "";
          $show = "<span class=hcmsHeadline>".$hcms_lang['the-publication-information-cannot-be-accessed'][$lang]."</span>\n".$hcms_lang['the-publication-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
        }
      }
      else
      {
        $add_onload = "";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-publication-information-cannot-be-accessed'][$lang]."</span>\n".$hcms_lang['the-publication-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
      }
    }
    else
    {
      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-publication-information-cannot-be-accessed'][$lang]."</span>\n".$hcms_lang['the-publication-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
    }
  }
  else
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['required-input-is-missing'][$lang]."</span>\n";
  }
  
  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;  
  
  return $result; 
}

// ------------------------- deletepublication -----------------------------
// function: deletepublication()
// input: publication name, user name  
// output: result array

// description:
// This function deletes a publication with all its files

function deletepublication ($site_name, $user="sys")
{
  global $mgmt_config, $eventsystem, $hcms_lang, $lang;

  $result_ok = false;
  
  // set default language
  if ($lang == "") $lang = "en";
  
  // check if login is an attribute of a sent string
  if (strpos ($site_name, ".php?") > 0)
  {
    // extract login
    $site_name = getattribute ($site_name, "site_name");
  }
  
  $file_count = 0;
  
  // check if component folder is empty
  $comp_root = deconvertpath ("%comp%/".$site_name."/", "file");

  $handle = @opendir ($comp_root);
  
  if ($handle != false)
  {
    while ($file = @readdir ($handle))
    {
      if ($file != "." && $file != ".." && $file != ".folder") $file_count++;
    }    
    @closedir ($handle);
  }
  
  // check if page folder is empty
  $page_root = deconvertpath ("%page%/".$site_name."/", "file");

  $handle = @opendir ($page_root);
  
  if ($handle != false)
  {
    while ($file = @readdir ($handle))
    {
      if ($file != "." && $file != ".." && $file != ".folder") $file_count++;
    }    
    @closedir ($handle);
  }

  if ($file_count > 0)
  {
    $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
    $show = "<span class=hcmsHeadline>".$hcms_lang['the-publication-cannot-be-removed'][$lang]."</span><br />\n".$hcms_lang['the-publication-still-holds-folders-or-objects'][$lang]."\n";
  }
  // check if sent data is available
  elseif (!valid_publicationname ($site_name) || !valid_objectname ($user))
  {
    $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
    $show = "<span class=hcmsHeadline>".$hcms_lang['required-input-is-missing'][$lang]."</span><br />\n".$hcms_lang['please-select-a-publication'][$lang]."\n";
  }
  else
  {
    // eventsystem
    if ($eventsystem['ondeletepublication_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
      ondeletepublication_pre ($site_name, $user); 

    // load publication list from inheritance database
    $inherit_db = inherit_db_load ($user);
    
    if ($inherit_db != false && valid_publicationname ($site_name))
    {
      // -------------------------------------- delete files of site ------------------------------------------
    
      // page root
      deleteobject ($site_name, "%page%/".$site_name."/", ".folder", "sys");
      deletefile ($mgmt_config['abs_path_comp'], $site_name, 1);
      
      // component root
      deleteobject ($site_name, "%comp%/".$site_name."/", ".folder", "sys");
      deletefile ($mgmt_config['abs_path_comp'], $site_name, 1);
      
      // template media
      deletefile ($mgmt_config['abs_path_rep']."media_tpl/", $site_name, 1);
      
      // content media
      deletefile ($mgmt_config['abs_path_rep']."media_cnt/", $site_name, 1);
      
      // user
      $errcode = "10101";
      $userdata = loadlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php", 5);
      
      if ($userdata != false)
      {
        // delete user
        $userdata = deletecontent ($userdata, "<memberof>", "<publication>", $site_name);    
            
        if ($userdata != false)
        {
          // save user xml file
          $test = savelockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php", $userdata);   
          
          if ($test == false) 
          {
            $errcode = "10121";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|savelockfile failed for user.xml.php";
          
            // unlock file
            unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");           
          }     
        }     
        else 
        {  
          $errcode = "10123";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|deletecontent failed for user.xml.php";
     
          // unlock file
          unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");       
        }  
      }
      else 
      {
        $errcode = "10122";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|loadfile failed for user.xml.php";
            
        // unlock file
        unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
      }
      
      // usergroup
      deletefile ($mgmt_config['abs_path_data']."user/", $site_name.".usergroup.xml.php", 0);
    
      // media   
      deletefile ($mgmt_config['abs_path_data']."media/", $site_name.".media.tpl.dat", 0);

      // link
      deletefile ($mgmt_config['abs_path_data']."link/", $site_name.".link.dat", 0);
       
      // templates
      deletefile ($mgmt_config['abs_path_data']."template/", $site_name, 1);    
      
      // workflow
      deletefile ($mgmt_config['abs_path_data']."workflow/", $site_name, 1);   
    
      $dir_temp = $mgmt_config['abs_path_data']."workflow_master/";
      $files = @dir ($dir_temp);
    
      if (@is_object ($files))
      {
        while ($entry = $files->read())
        {
          if (preg_match ("/^".$site_name."./", $entry))
          {
            deletefile ($dir_temp, $entry, 0);
          }
        }
    
        $files->close();
      }  
      
      // customer files
      deletefile ($mgmt_config['abs_path_data']."customer/", $site_name, 1);      
       
      // config files
      deletefile ($mgmt_config['abs_path_data']."config/", $site_name.".conf.php", 0);
      deletefile ($mgmt_config['abs_path_rep']."config/", $site_name.".ini", 0);
      deletefile ($mgmt_config['abs_path_rep']."config/", $site_name.".properties", 0);

      // mapping configuration file
      if (is_file ($mgmt_config['abs_path_data']."config/".$site_name.".media.map.php"))
      {
        deletefile ($mgmt_config['abs_path_data']."config/", $site_name.".media.map.php", 0);  
      }
     
      // remove site_name value from inheritance database
      $inherit_db = inherit_db_deleteparent ($inherit_db, $site_name);
    
      if ($inherit_db != false)
      {
        // save site file
        $test = inherit_db_save ($inherit_db, $user);
    
        if ($test != false)
        {
          // remove publication information from user session
          if (is_array ($_SESSION['hcms_siteaccess']))
          {
            $buffer = null;
            
            foreach ($_SESSION['hcms_siteaccess'] as $value)
            {
              if ($value != $site_name) $buffer[] = $value;
            }
            
            $_SESSION['hcms_siteaccess'] = $buffer;
            
            // get crypted password of current user
            $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");
         
            if ($userdata != false)
            {
              $user_node = selectcontent ($userdata, "<user>", "<login>", $user);
              
              if (!empty ($user_node[0])) 
              {
                $passwd_node = getcontent ($user_node[0], "<password>");
                
                if (!empty ($passwd_node[0]))
                {
                  $passwd = $passwd_node[0];
                
                  // register new checksum
                  killsession ($user, false);
                  writesession ($user, $passwd, createchecksum ());
                }
              }
            }
          }
        
          // eventsystem
          if ($eventsystem['oncreatepublication_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
            ondeletepublication_post ($site_name, $user);        
        
          $add_onload = "top.frames['navFrame'].location='explorer.php?refresh=1'; parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
          $show = "<span class=hcmsHeadline>".$hcms_lang['the-publication-was-deleted-successfully'][$lang]."</span><br />\n".$hcms_lang['all-publication-entries-were-removed-successfully'][$lang]."<br>\n";
          
          // success
          $result_ok = true;
        }
        else
        {
          // unlock file
          inherit_db_close ($user);
        }
      }
      else
      {
        // unlock file
        inherit_db_close ($user);
    
        $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-publication-cannot-be-removed'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
      }
    }
    else
    {
      // unlock file
      inherit_db_close ($user);
    
      $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-publication-information-cannot-be-accessed'][$lang]."</span><br />\n".$hcms_lang['the-publication-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
    }
  }
  
  // save log
  savelog (@$error);

  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;  
  
  return $result;
}

// ======================================= PERSONALIZATION OPERATIONS ==========================================

// ----------------------------------------- createpersonalization ---------------------------------------------
// function: createpersonalization()
// input: site, personalization profile or tracking name, category [profile,tracking]
// output: result array
// requires: config.inc.php to be loaded before

// description:
// This function creates a new customer personalization tracking or profile

function createpersonalization ($site, $pers_name, $cat)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  $result_ok = false;
  
  // set default language
  if ($lang == "") $lang = "en";
  
  // set default language
  if ($lang == "") $lang = "en";
  
  if (valid_publicationname ($site) && valid_objectname ($pers_name) && strlen ($pers_name) <= 100 && ($cat == "tracking" || $cat == "profile"))
  {
    // check if file is customer registration (reg.dat), customer profile (.prof.dat) and define extension
    if ($cat == "tracking") $ext = ".track.dat";
    elseif ($cat == "profile") $ext = ".prof.dat";
    
    // create pers file name
    $pers_name = trim ($pers_name);
    $persfile = $pers_name.$ext;
    
    // upload template file
    if (is_file ($mgmt_config['abs_path_data']."customer/".$site."/".$persfile))
    {
      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-object-exists-already'][$lang]."</span>
      ".$hcms_lang['please-try-another-template-name'][$lang]."\n";
    }
    else
    {
      // save template file
      $test = savefile ($mgmt_config['abs_path_data']."customer/".$site."/", $persfile, "");
    
      if ($test == false)
      {
        $add_onload = "";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-object-could-not-be-created'][$lang]."</span>
        ".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
      }
      else
      {
        $add_onload = "parent.frames['mainFrame'].location='pers_form.php?site=".url_encode($site)."&cat=".url_encode($cat)."&save=no&preview=no&persfile=".url_encode($persfile)."'; ";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-object-was-created-successfully'][$lang]."</span>\n";
        
        // success
        $result_ok = true;
      }
    }
  }
  else 
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['a-name-is-required'][$lang]."</span>";
  }    

  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;  
  
  return $result;  
}    

// ----------------------------------------- deletepersonalization ---------------------------------------------
// function: deletepersonalization()
// input: site, personalization profile or tracking name, category [profile,tracking]
// output: result array
// requires: config.inc.php to be loaded before

// description:
// This function deletes a customer personalization tracking or profile

function deletepersonalization ($site, $pers_name, $cat)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;
 
  $result_ok = false;
  
  // set default language
  if ($lang == "") $lang = "en";
  
  // set default language
  if ($lang == "") $lang = "en";
  
  if (valid_publicationname ($site) && valid_objectname ($pers_name) && ($cat == "tracking" || $cat == "profile"))
  {
    // check if file name is an attribute of a sent string
    if (strpos ($pers_name, ".php?") > 0)
    {
      // extract file name
      $pers_name = getattribute ($pers_name, "persfile");
    }
    
     // define file name and extract personalization name
    if (strpos ($pers_name, ".track.dat") > 0)
    {
      $persfile = $pers_name;
      $pers_name = substr ($persfile, 0, strpos ($persfile, ".track.dat"));
    }

    elseif (strpos ($pers_name, ".prof.dat") > 0)
    {
      $persfile = $pers_name;
      $pers_name = substr ($persfile, 0, strpos ($persfile, ".prof.dat"));
    }
    elseif ($cat == "tracking")
    {
      $persfile = $pers_name.".track.dat";
    }
    elseif ($cat == "profile")
    {
      $persfile = $pers_name.".prof.dat";
    }
    
    if (is_file ($mgmt_config['abs_path_data']."customer/".$site."/".$persfile))
    {
      $test = deletefile ($mgmt_config['abs_path_data']."customer/".$site."/", $persfile, 0);
    
      if ($test == true)
      {
        $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-object-was-removed'][$lang]."</span>\n";
        
        // success
        $result_ok = true;
      }
      else
      {
        $add_onload = "";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-object-could-not-be-removed'][$lang]."</span><br />".$hcms_lang['the-object-does-not-exist-or-you-do-not-have-permissions'][$lang]."\n";
      }  
    }
    else
    {
      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-object-could-not-be-removed'][$lang]."</span><br />\n".$hcms_lang['the-object-does-not-exist-or-you-do-not-have-permissions'][$lang]."\n";
    }
  }
  else 
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['a-name-is-required'][$lang]."</span>";
  }    
  
  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;  
  
  return $result;  
}

// ======================================= TEMPLATE FUNCTIONS ===========================================

// ----------------------------------------- createtemplate ---------------------------------------------
// function: createtemplate()
// input: site, template name, category [page,comp,meta,inc]
// output: result array
// requires: config.inc.php to be loaded before

// description:
// This function creates a new template

function createtemplate ($site, $template, $cat)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  $result_ok = false;
  
  // set default language
  if ($lang == "") $lang = "en";

  if (valid_publicationname ($site) && valid_objectname ($template) && strlen ($template) <= 60 && in_array ($cat, array("page","comp","inc","meta")))
  {
    // check if file is page template (.tpl), page component template (.comp.tpl) or template component (.inc.tpl),
    // define extension and template category name
    if ($cat == "page")
    {
      $ext = ".page.tpl";
    }
    elseif ($cat == "comp")
    {
      $ext = ".comp.tpl";
    }
    elseif ($cat == "inc")
    {
      $ext = ".inc.tpl";
    }
    elseif ($cat == "meta")
    {
      $ext = ".meta.tpl";
    }    
    
    // trim name
    $template = trim ($template);
    
    // create template file name
    $tpl_name = $template;
    $template = $template.$ext;
    
    // upload template file
    if (is_file ($mgmt_config['abs_path_template'].$site."/".$template))
    {
      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-tempate-exists-already'][$lang]."</span><br />\n".$hcms_lang['please-try-another-template-name'][$lang]."\n";
    }
    else
    {
      // load template xml schema
      $tpl_data = loadfile ($mgmt_config['abs_path_cms']."xmlschema/", "template.schema.xml.php");
      $tpl_data = setcontent ($tpl_data, "", "<name>", $tpl_name, "", "");
      $tpl_data = setcontent ($tpl_data, "", "<category>", $cat, "", "");
    
      // save template file
      $test = savefile ($mgmt_config['abs_path_template'].$site."/", $template, $tpl_data);
    
      if ($test == false)
      {
        $add_onload = "";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-template-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
      }
      else
      {
        $add_onload = "parent.frames['mainFrame'].location='frameset_template_edit.php?site=".url_encode($site)."&cat=".url_encode($cat)."&save=no&template=".url_encode($template)."'; ";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-template-was-created'][$lang]."</span><br />\n".$hcms_lang['now-you-can-edit-the-template'][$lang]."\n";
        
        // success
        $result_ok = true;
      }
    }
  }
  else 
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['a-template-name-is-required'][$lang]."</span>";
  }
  
  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;  
  
  return $result;     
}

// ----------------------------------------- gettemplates ---------------------------------------------
// function: gettemplates()
// input: publication name, object category [page,comp,meta]
// output: template file name list as array / false on error
// requires: config.inc.php to be loaded before

// description:
// This function returns a list of all templates for pages or components.
// Based on the inheritance settings of the publication the template will be loaded with highest priority from the own publication and if not available from a parent publication.

function gettemplates ($site, $cat)
{
  global $user, $mgmt_config, $hcms_lang, $lang;
  
  $result = array();
  
  // set default language
  if ($lang == "") $lang = "en";
  
  if (valid_publicationname ($site) && ($cat == "page" || $cat == "comp" || $cat == "meta"))
  {
    $site_array = array();
    
    // load publication inheritance setting
    if ($mgmt_config[$site]['inherit_tpl'] == true)
    {
      $inherit_db = inherit_db_read ();
      $site_array = inherit_db_getparent ($inherit_db, $site);
      
      // add own publication
      $site_array[] = $site;
    }
    else $site_array[] = $site;
    
    $template_array = array();

    foreach ($site_array as $site_source)
    {
      $dir_template = dir ($mgmt_config['abs_path_template'].$site_source."/");

      if ($dir_template != false)
      {
        while ($entry = $dir_template->read())
        {
          if ($entry != "." && $entry != ".." && !is_dir ($entry) && !preg_match ("/.inc.tpl/", $entry) && !preg_match ("/.tpl.v_/", $entry))
          {
            if ($cat == "page" && strpos ($entry, ".page.tpl") > 0)
            {
              $template_array[] = $entry;
            }
            elseif ($cat == "comp" && strpos ($entry, ".comp.tpl") > 0)
            {
              $template_array[] = $entry;
            }
            elseif ($cat == "meta" && strpos ($entry, ".meta.tpl") > 0)
            {
              $template_array[] = $entry;
            }            
          }
        }

        $dir_template->close();
      }
    }

    if (is_array ($template_array) && sizeof ($template_array) > 0)
    {
      // remove double entries (double entries due to parent publications won't be listed)
      $template_array = array_unique ($template_array);
      natcasesort ($template_array);
      reset ($template_array);
      
      return $template_array;
    }
    else return false;
  }
  else return false;
}

// ----------------------------------------- loadtemplate ---------------------------------------------
// function: loadtemplate()
// input: publication name, template file name
// output: array (template content [XML string], publication, result[true/false]) / false on error
// requires: config.inc.php to be loaded before

// description:
// This function loads templates by given name.
// Based on the inheritance settings of the publication the template will be loaded
// with highest priority from the own publication and if not available from a parent
// publication. If the parent publications have double entries the sort mechanism will
// define the priority. First priority have numbers, second are upper case letters and
// last priority have lower case letters.

function loadtemplate ($site, $template)
{
  global $user, $mgmt_config, $hcms_lang, $lang;
  
  $result = array();
  
  if (valid_publicationname ($site) && valid_objectname ($template))
  {
    if (is_file ($mgmt_config['abs_path_template'].$site."/".$template))
    {
      $data = loadfile ($mgmt_config['abs_path_template'].$site."/", $template);
      
      $result['content'] = $data;
      $result['publication'] = $site;    
      
      if ($data != false) $result['result'] = true;
      else $result['result'] = false; 
      
      return $result; 
    }
    else
    {
      $inherit_db = inherit_db_read ();
      $parent_array = inherit_db_getparent ($inherit_db, $site); 
      
      if (is_array ($parent_array))
      {
        sort ($parent_array);
        reset ($parent_array);
        
        foreach ($parent_array as $parent)
        {
          if (is_file ($mgmt_config['abs_path_template'].$parent."/".$template))
          {
            $data = loadfile ($mgmt_config['abs_path_template'].$parent."/", $template);
            
            $result['content'] = $data;
            $result['publication'] = $parent;    
            
            if ($data != false) $result['result'] = true;
            else $result['result'] = false; 
            
            return $result; 
            break;
          }
        }
      }   
    }
  }
  else return false;
}

// ----------------------------------------- edittemplate ---------------------------------------------
// function: edittemplate()
// input: site, template file name, category [page,comp,meta,inc], template content (optional), template extension (optional), temlate application (optional)
// output: result array
// requires: config.inc.php to be loaded before

// description:
// This function edites a template

function edittemplate ($site, $template, $cat, $user, $content="", $extension="", $application="")
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;
 
  $result_save = false;
  $add_onload = "";
  $show = "";
  
  // set default language
  if ($lang == "") $lang = "en";
  
  if (valid_publicationname ($site) && valid_objectname ($template) && in_array ($cat, array("page","comp","inc","meta")) && valid_objectname ($user))
  { 
    $contentfield_save = $content;

    // unescape special characters (transform all special chararcters into their html/xml equivalents)
    if ($contentfield_save != "")
    {
      $contentfield_save = str_replace ("&amp;", "&", $contentfield_save);
      $contentfield_save = str_replace ("&lt;", "<", $contentfield_save);
      $contentfield_save = str_replace ("&gt;", ">", $contentfield_save);
      $contentfield_save = str_replace ("<![CDATA[", "&lt;![CDATA[", $contentfield_save); 
      $contentfield_save = str_replace ("]]>", "]]&gt;", $contentfield_save);
    }
    
    // get charset before transformation of < and >
    $result_charset = getcharset ($site, $contentfield_save); 
    
    // add CDATA section 
    $contentfield_save = "<![CDATA[".trim ($contentfield_save)."]]>";   

    // load template and insert values
    $result_load = loadtemplate ($site, $template);
    
    if ($result_load['result'] == true)
    {
      $templatedata = $result_load['content'];
      $extension = str_replace (".", "", trim ($extension));
      $application = strtolower (trim ($application));
      $user = trim ($user);
      
      if (strpos ($templatedata, "</user>") > 0) $templatedata = setcontent ($templatedata, "", "<user>", $user, "", "");
      else  $templatedata = insertcontent ($templatedata, "<user>".$user."</user>\n", "<template>");
      
      if ($extension != "") $templatedata = setcontent ($templatedata, "", "<extension>", strtolower ($extension), "", "");
            
      if (in_array ($application, array("asp","aspx","htm","jsp","php","xml","generator","media"))) $templatedata = setcontent ($templatedata, "", "<application>", $application, "", "");
      
      $templatedata = setcontent ($templatedata, "", "<content>", $contentfield_save, "", "");
    }
    else $templatedata = false;
  
    // save new template file
    if ($templatedata != "" && is_file ($mgmt_config['abs_path_template'].$site."/".$template)) 
    {
      // create version of previous template file
      $template_v = fileversion ($template);
      rename ($mgmt_config['abs_path_template'].$site."/".$template, $mgmt_config['abs_path_template'].$site."/".$template_v);

      // save template
      $result_save = savefile ($mgmt_config['abs_path_template'].$site."/", $template, $templatedata);
    
      if ($result_save == false)
      {
        $show = "<p class=hcmsHeadline>".$hcms_lang['template-could-not-be-saved'][$lang]."</p>\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
      }
    }
    else
    {
      $show = "<p class=hcmsHeadline>".$hcms_lang['functional-error-occured'][$lang]."</p>\n".$hcms_lang['an-error-occured-in-function-setcontent'][$lang]."\n"; 
    }
  }
  
  $result = array();
  $result['result'] = $result_save;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  $result['content'] = $content;  
  
  return $result;    
}

// ----------------------------------------- deletetemplate ---------------------------------------------
// function: deletetemplate()
// input: site, template file name, category [page,comp,meta,inc]
// output: result array
// requires: config.inc.php to be loaded before

// description:
// This function deletes a template

function deletetemplate ($site, $template, $cat)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;
 
  $result_ok = false;
  
  // set default language
  if ($lang == "") $lang = "en";
  
  if (valid_publicationname ($site) && valid_objectname ($template) && in_array ($cat, array("page","comp","inc","meta")))
  {  
    // extract template file name
    if (strpos ($template, ".php?") > 0) $template = getattribute ($template, "template");
    
    // define template name
    if (strpos ($template, ".inc.tpl") > 0) $tpl_name = substr ($template, 0, strpos ($template, ".inc.tpl"));
    elseif (strpos ($template, ".meta.tpl") > 0) $tpl_name = substr ($template, 0, strpos ($template, ".meta.tpl"));
    elseif (strpos ($template, ".page.tpl") > 0) $tpl_name = substr ($template, 0, strpos ($template, ".page.tpl"));
    elseif (strpos ($template, ".comp.tpl") > 0) $tpl_name = substr ($template, 0, strpos ($template, ".comp.tpl"));
    elseif ($cat == "page" || $cat == "comp" || $cat == "meta" || $cat == "inc") 
    {
      $tpl_name = $template;
      $template = $template.".".$cat.".tpl";
    }
    
    if (is_file ($mgmt_config['abs_path_template'].$site."/".$template))
    {
      $dir_template = dir ($mgmt_config['abs_path_template'].$site."/");
      
      if (is_object ($dir_template))
      {
        while ($entry = $dir_template->read())
        {
          if ($entry == $template || substr_count ($entry, $template.".v_") == 1)
          {
            $test = deletefile ($mgmt_config['abs_path_template'].$site."/", $entry, 0);
          }
        }
      }
    
      $dir_template->close();
      
      if ($test == true)
      {
        $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-template-was-removed'][$lang]."</span>\n";
        
        // success
        $result_ok = true;
      }
      else
      {
        $add_onload = "";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-template-could-not-be-removed'][$lang]."</span><br />\n".$hcms_lang['the-template-does-not-exist-or-you-do-not-have-write-permissions'][$lang]."\n";
      }  
    }
    else
    {
      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-template-could-not-be-removed'][$lang]."</span><br />\n".$hcms_lang['the-template-does-not-exist-or-you-do-not-have-write-permissions'][$lang]."\n";
    }
  }
  else 
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['a-template-name-is-required'][$lang]."</span>";
  }
  
  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;  
  
  return $result;   
}

// ====================================== USER FUNCTIONS ==========================================

// ---------------------------------------- createuser --------------------------------------------
// function: createuser()
// input: publication name (optional), login name, password, confirmed password, user name
// output: array

// description:
// This function creates a new user

function createuser ($site, $login, $password, $confirm_password, $user="sys")
{
  global $eventsystem, $mgmt_config, $mgmt_lang_shortcut_default, $hcms_lang, $lang;
 
  $add_onload = "";
  $show = "";

  // set default language
  if ($lang == "") $lang = "en";
  
  // default theme
  if ($mgmt_config['theme'] != "") $theme = $mgmt_config['theme'];
  else $theme = "standard";
    
  // check if sent data is available
  if (!valid_objectname ($login) || strlen ($login) > 60 || $password == "" || strlen ($password) > 20 || $confirm_password == "")
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['necessary-user-information-is-missing'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-fill-out-all-fields'][$lang]."\n";
  }
  // check if user it not admin or sys
  elseif ($login == "admin" || $login == "sys")
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-user-exists-already'][$lang]."!</span><br />\n".$hcms_lang['please-try-another-user-name'][$lang]."\n";
  }
  // check if submitted passwords has at least 8 digits
  elseif (strlen ($password) < 8 || strlen ($confirm_password) < 8)
  {  
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['your-submitted-passwords-has-less-than-8-digits'][$lang]."</span><br />\n".$hcms_lang['please-select-a-password-with-at-least-8-digits'][$lang]."\n";
  }    
  // test if login name contains special characters
  elseif (specialchr ($login, "-_") == true)
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['special-characters-in-expressions-are-not-allowed'][$lang]."</span><br />\n".$hcms_lang['onhcms_lang'][$lang]."\n";
  }
  // check if submitted passwords are equal
  elseif ($password != $confirm_password)
  {  
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['your-submitted-passwords-are-not-equal'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-try-it-again-'][$lang]."\n";
  }    
  // check for strong password (if enabled)
  elseif ($mgmt_config['strongpassword'] == true && strlen (checkpassword ($password)) > 1)
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['password-insufficient'][$lang]."</span><br />\n".checkpassword ($password)."\n";
  }
  else
  {     
    // eventsystem
    if ($eventsystem['oncreateuser_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
      oncreateuser_pre ($login, $user);  
  
    $login = trim ($login);
    
    // load user xml file
    $userdata = loadlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php", 5);

    if ($userdata != false)
    {
      // Updates in XML nodes:
      // before version 5.4.6 new hashcode nodes needs to be inserted
      if (substr_count ($userdata, "<hashcode>") == 0)
      {
        $userdata = str_replace ("</password>", "</password>\n<hashcode></hashcode>", $userdata);
      }       
      // before version 5.5.11 new admin nodes needs to be inserted
      if (substr_count ($userdata, "<admin>") == 0)
      {
        $userdata = str_replace ("</hashcode>", "</hashcode>\n<admin>0</admin>", $userdata);
      }
      // before version 5.5.15 new theme nodes needs to be inserted
      if (substr_count ($userdata, "<theme>") == 0)
      {
        $userdata = str_replace ("</language>", "</language>\n<theme></theme>", $userdata);
      }      
          
      // check if user already exists
      $testlogin = selectcontent ($userdata, "<user>", "<login>", $login);
    
      // if user exists or has the session_id() name used for system locking of files
      if ($testlogin != false || $login == session_id())
      {
        // unlock file
        unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
            
        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-user-exists-already'][$lang]."!</span><br />\n".$hcms_lang['please-try-another-user-name'][$lang]."\n";
      }

      if ($show == "")
      {
        // load user xml schema
        $user_schema_xml = loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "user.schema.xml.php");
      
        if ($user_schema_xml != false)
        {
          // generate hashcode
          $hashcode = md5 ($login.":hyperdav:".$password);
          
          // crypt password
          $password = crypt ($password, substr ($password, 1, 2));

          // insert values into xml schema
          $newuser = setcontent ($user_schema_xml, "<user>", "<login>", $login, "", "");
          $newuser = setcontent ($newuser, "<user>", "<password>", $password, "", "");
          $newuser = setcontent ($newuser, "<user>", "<hashcode>", $hashcode, "", "");
          $newuser = setcontent ($newuser, "<user>", "<userdate>", date ("Y-m-d", time()), "", "");
          $newuser = setcontent ($newuser, "<user>", "<language>", $mgmt_lang_shortcut_default, "", "");
          $newuser = setcontent ($newuser, "<user>", "<theme>", $theme, "", "");
          
          if (isset ($site) && $site != "*Null*") 
          {
            $newuser = setcontent ($newuser, "<memberof>", "<publication>", $site, "", "");
          }
          else 
          {
            $newuser = deletecontent ($newuser, "<memberof>", "", "");
          }
        
          // add new user
          if ($newuser != false) $userdatanew = insertcontent ($userdata, $newuser, "<userlist>");
          else $userdatanew = false;
        }
        else
        {
          // unlock file
          unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");      
        
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-user-xml-schema-cannot-be-loaded'][$lang]."</span><br />\n".$hcms_lang['the-schema-is-missing-or-you-do-not-have-read-permissions'][$lang]."\n";  
        }
      
        if ($show == "" && $userdatanew != false)
        {
          // save user xml file
          $test = savelockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php", $userdatanew);     
      
          if ($show == "" && $test != false)
          {
            // eventsystem
            if ($eventsystem['oncreateuser_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
              oncreateuser_post ($login, $user);              
          
            $add_onload = "window.open('user_edit.php?site=".url_encode($site)."&login=".url_encode($login)."','','status=yes,scrollbars=no,resizable=yes,width=500,height=500'); parent.frames['mainFrame'].location.reload(); ";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-new-user-was-created'][$lang]."</span><br />\n".$hcms_lang['now-you-can-edit-the-user'][$lang]."<br />\n";              
            $error_switch = "no";
          }
          else
          {
            // unlock file
            unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
          }
        }
        else 
        {
          // unlock file
          unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
             
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['user-information-cannot-be-inserted'][$lang]."</span><br />\n";
        }
      }
    }
    else
    {
      // unlock file
      unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
    
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-user-information-cannot-be-accessed'][$lang]."</span><br />\n".$hcms_lang['the-user-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
    }
  }
  
  // return results
  $result = array();
  
  if (isset ($error_switch) && $error_switch == "no") $result['result'] = true;
  else $result['result'] = false;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;  
  
  return $result;
}

// ------------------------------------------- edituser --------------------------------------------
// function: edituser()
// input: publication name, login name, new login name, password, confirmed password, super administrator [0,1], real name, language setting [de,en], 
//        theme name (optional), email, usergroup string [group1|group2], member of site(s) string [site1|site2]], user name
// output: array

// description:
// This function edits a user

function edituser ($site, $login, $old_password="", $password="", $confirm_password="", $superadmin="0", $realname="", $language="en", $theme="", $email="", $signature="", $usergroup="", $usersite="", $user="sys")
{
  global $eventsystem, $login_cat, $group, $mgmt_config, $hcms_lang, $lang;

  $add_onload = "";
  $show = "";
  
  // set default language
  if ($lang == "") $lang = "en";
  
  if (valid_objectname ($login) && valid_objectname ($user))
  { 
    // load user xml file
    $userdata = loadlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php", 5);
      
    // default theme
    if ($theme == "")
    {
      if (!empty ($mgmt_config['theme'])) $theme = $mgmt_config['theme'];
      elseif (!empty ($mgmt_config[$site]['theme'])) $theme = $mgmt_config[$site]['theme'];
      else $theme = "standard";
    }
    
    if ($userdata != false)
    {
      // Updates in XML nodes:
      // before version 5.4.6 new hashcode nodes needs to be inserted
      if (substr_count ($userdata, "<hashcode>") == 0)
      {
        $userdata = str_replace ("</password>", "</password>\n<hashcode></hashcode>", $userdata);
      }       
      // before version 5.5.11 new admin nodes needs to be inserted
      if (substr_count ($userdata, "<admin>") == 0)
      {
        $userdata = str_replace ("</hashcode>", "</hashcode>\n<admin>0</admin>", $userdata);
      }
      // before version 5.5.15 new theme nodes needs to be inserted
      if (substr_count ($userdata, "<theme>") == 0)
      {
        $userdata = str_replace ("</language>", "</language>\n<theme>".$theme."</theme>", $userdata);
      }         
            
      // check if password was changed
      if ($password != "")
      {        
        // check if submitted old password is valid if user changes his own password
        if ($login == $user)
        {
          // get user information
          $usernode = selectcontent ($userdata, "<user>", "<login>", $login);        
          $userpasswd = getcontent ($usernode[0], "<password>");
          $usersuperadmin = getcontent ($usernode[0], "<admin>");
        }
              
        if ($login == $user && !empty ($userpasswd[0]) && crypt ($old_password, substr ($old_password, 1, 2)) != $userpasswd[0])
        {
          //unlock file
          unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
                
          $add_onload = "";
          $show = "<span class=hcmsHeadline>".$hcms_lang['the-old-password-is-not-valid'][$lang]."</span>\n";
        }
        // check if submitted passwords has at least 8 digits
        elseif (strlen ($password) < 8)
        {
          //unlock file
          unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
                
          $add_onload = "";
          $show = "<span class=hcmsHeadline>".$hcms_lang['the-password-has-less-than-8-digits'][$lang]."</span><br />".$hcms_lang['please-select-a-password-with-at-least-8-digits'][$lang]."\n";
        }  
        // check if submitted passwords are equal
        elseif ($password != $confirm_password)
        {
          //unlock file
          unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
                
          $add_onload = "";
          $show = "<span class=hcmsHeadline>".$hcms_lang['your-submitted-passwords-are-not-equal'][$lang]."</span><br />".$hcms_lang['please-try-it-again'][$lang]."\n";
        }
        // check for strong password (if enabled)
        elseif ($mgmt_config['strongpassword'] == true && strlen (checkpassword ($password)) > 1)
        {
          //unlock file
          unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
                  
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['password-insufficient'][$lang]."</span><br />\n".checkpassword ($password)."\n";
        }        
        // password is correct
        else
        {
          // generate hashcode
          $hashcode = md5 ($login.":hyperdav:".$password);
          
          // crypt password
          $password = crypt ($password, substr ($password, 1, 2));
      
          // insert values into xml schema
          $userdata = setcontent ($userdata, "<user>", "<password>", $password, "<login>", $login);         
          
          // insert values into xml schema )
          $userdata = setcontent ($userdata, "<user>", "<hashcode>", $hashcode, "<login>", $login);
        }
      }
      
      // check if super admin was changed
      if ($superadmin == "1" || $superadmin == "0")
      {              
        // insert values into xml schema
        $userdata = setcontent ($userdata, "<user>", "<admin>", $superadmin, "<login>", $login); 
      }     

      // check if realname was changed
      if (isset ($realname) && $show == "")
      {
        // escape special characters
        $realname = strip_tags ($realname);
        $realname = html_encode ($realname);
    
        // insert values into xml schema
        $userdata = setcontent ($userdata, "<user>", "<realname>", "<![CDATA[".$realname."]]>", "<login>", $login);
      }
    
      // check if lanuage was changed
      if (valid_objectname ($language) && $show == "")
      {
        // escape special characters
        $language = strip_tags ($language);
        $language = html_encode ($language);
              
        // insert values into xml schema
        $userdata = setcontent ($userdata, "<user>", "<language>", $language, "<login>", $login);
      }
      
      // check if theme was changed
      if (valid_objectname ($theme) && $show == "")
      {
        // escape special characters
        $theme = strip_tags ($theme);
        $theme = html_encode ($theme);
              
        // insert values into xml schema
        $userdata = setcontent ($userdata, "<user>", "<theme>", $theme, "<login>", $login);
      }      
  
      // check if email was changed
      if (isset ($email) && $show == "")
      {
        // escape special characters
        $email = strip_tags ($email);
              
        // insert values into xml schema
        $userdata = setcontent ($userdata, "<user>", "<email>", "<![CDATA[".$email."]]>", "<login>", $login);
      }  
      
      // check if email was changed
      if (isset ($signature) && $show == "")
      {
        // escape special characters
        $signature = strip_tags ($signature);
              
        // insert values into xml schema
        $userdata = setcontent ($userdata, "<user>", "<signature>", "<![CDATA[".$signature."]]>", "<login>", $login);
      }      
    
      // check if usergroup was changed
      if (isset ($usergroup) && valid_objectname ($usergroup) && $show == "")
      {
        if ($usergroup == "*Null*") $usergroup = "";
        
        // insert values into xml schema
        $user_node = selectcontent ($userdata, "<user>", "<login>", $login);
        if (is_array ($user_node)) $user_node = setcontent ($user_node[0], "<memberof>", "<usergroup>", $usergroup, "<publication>", $site);    
        if ($user_node != "") $userdata = setcontent ($userdata, "<user>", "<user>", $user_node, "<login>", $login);
      }  

      // check if usersite was changed
      if (isset ($usersite) && $usersite != "" && $show == "")
      {
        if ($usersite == "*Null*") 
        {
          $new_usersite[0] = "";
        }  
        else
        { 
          $usersite = substr ($usersite, 1, strlen ($usersite) - 2);
          $new_usersite = explode ("|", $usersite);
        }
        
        // get user node
        $user_array = selectcontent ($userdata, "<user>", "<login>", $login);    
        
        $user_node = $user_node_new = $user_array[0];
        
        $set_memberof_array = getxmlcontent ($user_node, "<memberof>");
        
        // load memberof schema
        $memberof_schema_xml = loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "memberof.schema.xml.php");    

        // remove site access of user
        if ($set_memberof_array != false)
        {
          foreach ($set_memberof_array as $set_memberof_node)
          {
            $set_usersite = getcontent ($set_memberof_node, "<publication>");

            if (!in_array ($set_usersite[0], $new_usersite)) 
            {   
              // delete current memberof node
              $user_node_new = deletecontent ($user_node_new, "<memberof>", "<publication>", $set_usersite[0]);
            }
          }
        }
        
        // membership of publications
        $memberof_node = "";
        
        foreach ($new_usersite as $new_site)
        {   
          if ($new_site != "" && substr_count ($user_node, "<publication>".$new_site."</publication>") == 0)
          { 
            $memberof_node = $memberof_node.setcontent ($memberof_schema_xml, "<memberof>", "<publication>", $new_site, "", "")."\n";
          }       
        }
        
        // insert new site access of user
        if ($memberof_node != "") $user_node_new = insertcontent ($user_node_new, $memberof_node, "<user>");
            
        // update user node
        if ($user_node_new != false) 
        {
          $userdata = updatecontent ($userdata, $user_node, $user_node_new);
        }
      }   
      
      // save user xml file
      if ($userdata != false && $show == "")
      {
        // eventsystem
        if ($eventsystem['onsaveuser_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
          onsaveuser_pre ($login, $user_node, $user);      
      
        $test = savelockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php", $userdata);
  
        if ($test != false)
        {
          // eventsystem
          if ($eventsystem['onsaveuser_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
            onsaveuser_post ($login, $user_node, $user);        
        
          $add_onload = "";
          $show = "<span class=hcmsHeadline>".$hcms_lang['the-user-information-was-saved-successfully'][$lang]."</span>";
          
          $error_switch = "no";
        }
        else
        {
          //unlock file
          unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
          
          $add_onload = "";
          $show = "<span class=hcmsHeadline>".$hcms_lang['the-user-information-cannot-be-saved'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";        
        }
      }
      // error in XML manipulation
      elseif ($show == "")
      {
        //unlock file
        unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
        
        $add_onload = "";
        $show = "<span class=hcmsHeadline>".$hcms_lang['an-error-occured-in-xml-manipulation'][$lang]."</span><br />\n";
      }
    
      // check if lanuage was changed and register new language
      // must be registered at last, otherwise a language settings mixture in this script would occure
      if ($language != "" && $login_cat == "home")
      {
        $lang = $language;
        $_SESSION['hcms_lang'] = $lang;
      }
    }
    else
    {
      //unlock file
      unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
    
      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-user-information-cannot-be-accessed'][$lang]."</span><br />\n".$hcms_lang['the-user-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
    }
  }
  // required input is missing
  else
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['the-user-information-cannot-be-accessed'][$lang]."</span><br />\n".$hcms_lang['the-user-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
  }
  
  // return results
  $result = array();
  
  if (isset ($error_switch) && $error_switch == "no") $result['result'] = true;
  else $result['result'] = false;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  
  return $result;  
}    

// ---------------------------------------- deleteuser --------------------------------------------
// function: deleteuser()
// input: publication where the user should be removed [*Null*] for all publications, login name, user name
// output: array

// description:
// This function removes a user

function deleteuser ($site, $login, $user="sys")
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  $add_onload = "";
  $show = "";
  
  // set default language
  if ($lang == "") $lang = "en";
  
  // site can be *Null*, which is naot a valid publication name
  if ($site != "" && valid_objectname ($login) && valid_objectname ($user))
  {
    // eventsystem
    if ($eventsystem['ondeleteuser_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
      ondeleteuser_pre ($login, $user);  
    
    // load user xml file
    $userdata = loadlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php", 5);
    
    if ($userdata != false)
    { 
      if ($site == "*Null*")
      {
        // delete user
        $userdata = deletecontent ($userdata, "<user>", "<login>", $login);
        
        // remove task list file of user (before version 5.8.4)
        if (is_file ($mgmt_config['abs_path_data']."task/".$login.".xml.php"))
        {
          deletefile ($mgmt_config['abs_path_data']."task/", $login.".xml.php", 0);
        }
        
        // remove task list file of user (since version 5.8.4)
        rdbms_deletetask ("", "", "", $login);
        
        // remove checked out list file of user
        deletefile ($mgmt_config['abs_path_data']."checkout/", $login.".dat", 0);
      }
      elseif (valid_publicationname ($site))
      {
        // remove site membership
        $user_node = selectcontent ($userdata, "<user>", "<login>", $login);
        $user_node_new = deletecontent ($user_node[0], "<memberof>", "<publication>", $site);
        $userdata = updatecontent ($userdata, $user_node[0], $user_node_new);
      }  
    
      if ($userdata != false)
      {
        // save user xml file
        $test = savelockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php", $userdata);
    
        if ($test != false)
        {
          // delete user from notify table
          rdbms_deletenotification ("", "", $login);
          
          // eventsystem
          if ($eventsystem['ondeleteuser_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
            ondeleteuser_post ($login, $user);      
    
          $add_onload = "parent.frames['mainFrame'].location.reload();";
          $show = "<span class=hcmsHeadline>".$hcms_lang['all-user-information-was-removed-successfully'][$lang]."</span>\n";          
          $error_switch = "no";
        }
        else
        {
          // unlock file
          unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
        }
      }
      else
      {
        // unlock file
        unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
        
        $add_onload = "";
        $show = "<span class=hcmsHeadline>".$hcms_lang['an-error-occured-in-function-deletecontent'][$lang]."</span><br />\n";    
      }
    }
    else
    {
      // unlock file
      unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
      
      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-user-information-cant-be-accessed'][$lang]."</span><br />\n".$hcms_lang['the-user-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
    }
  }
  // input paramaters missing
  else
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['necessary-user-information-is-missing'][$lang]."</span><br />".$hcms_lang['please-go-back-and-select-a-user'][$lang]."\n";
  }  
  
  // return results
  $result = array();
  
  if (isset ($error_switch) && $error_switch == "no") $result['result'] = true;
  else $result['result'] = false;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;    
  
  return $result;
}

// ====================================== GROUP FUNCTIONS ==========================================

// ---------------------------------------- creategroup --------------------------------------------
// function: creategroup()
// input: publication anem, group name, user name
// output: array

// description:
// This function creates a new user group

function creategroup ($site, $group_name, $user="sys")
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  $add_onload = "";
  $show = "";
  
  // set default language
  if ($lang == "") $lang = "en";
  
  // check if sent data is available
  if (!valid_publicationname ($site) || !valid_objectname ($group_name) || strlen ($group_name) > 100 || !valid_objectname ($user))
  {
    $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
    $show = "<span class=hcmsHeadline>".$hcms_lang['necessary-group-name-is-missing'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-enter-a-name'][$lang]."\n";
  }
  // test if group name includes special characters
  elseif (specialchr ($group_name, "-_") == true)
  {
    $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
    $show = "<span class=hcmsHeadline>".$hcms_lang['special-characters-in-expressions-are-not-allowed'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-try-another-expression'][$lang]."\n";
  }
  else
  {
    // eventsystem
    if ($eventsystem['oncreategroup_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
      oncreategroup_pre ($group_name, $user);
      
    $group_name = trim ($group_name);
  
    // load usergroup xml file
    $usergroupdata = loadlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php", 3);
    
    if ($usergroupdata != false)
    {
      // check if usergroup exists already
      $testlogin = selectcontent ($usergroupdata, "<usergroup>", "<groupname>", $group_name);
  
      if ($testlogin != false)
      {
        //unlock file
        unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");
            
        $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-group-exists-already'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-try-another-name'][$lang]."\n";
      }
      else
      {
        // load usergroup xml schema
        $usergroup_schema_xml = loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "usergroup.schema.xml.php");
      
        // insert values into xml schema
        $usergroupnew = setcontent ($usergroup_schema_xml, "<usergroup>", "<groupname>", $group_name, "", "");
      
        // add new usergroup
        $usergroupdatanew = insertcontent ($usergroupdata, $usergroupnew, "<usergrouplist>");
      
        if ($usergroupdatanew != false)
        {
          // save usergroup xml file
          $test = savelockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php", $usergroupdatanew);
      
          if ($test != false)
          {
            // eventsystem
            if ($eventsystem['oncreategroup_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
              oncreategroup_post ($group_name, $user);  
              
            $usergroupdata = $usergroupdatanew;
            
            $add_onload = "parent.frames['mainFrame'].location='group_edit_form.php?site=".url_encode($site)."&preview=no&group_name=".url_encode($group_name)."'; ";
            $show = "<span class=hcmsHeadline>".$hcms_lang['group'][$lang]." '".$group_name."' ".$hcms_lang['was-created'][$lang]."</span><br />\n".$hcms_lang['now-you-can-edit-the-group'][$lang]."<br />\n";            
            $error_switch = "no";
          }
          else
          {
            //unlock file
            unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");
            
            $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
            $show = "<span class=hcmsHeadline>".$hcms_lang['group-information-cannot-be-saved'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";          
          }
        }
        else
        {
          //unlock file
          unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");
          
          $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
          $show = "<span class=hcmsHeadline>".$hcms_lang['group-information-could-not-be-inserted'][$lang]."</span>\n";         
        }
      }  
    }
    else
    {
      //unlock file
      unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");
    
      $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
      $show = "<span class=hcmsHeadline>".$hcms_lang['group-information-cannot-be-accessed'][$lang]."</span><br />\n".$hcms_lang['group-information-is-missing-or-you-do-not-have-read-permission'][$lang]."\n";
    }
  }
  
  // return results
  $result = array();
  
  if (isset ($error_switch) && $error_switch == "no") $result['result'] = true;
  else $result['result'] = false;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;  
  
  return $result;  
}

// ---------------------------------------- editgroup --------------------------------------------
// function: editgroup()
// input: publication name, group name, page folder access array, component folder access array, permissions array, user name
// output: array

// description:
// This function removes a user group

function editgroup ($site, $group_name, $pageaccess, $compaccess, $permission, $user)
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  $add_onload = "";
  $show = "";
  
  // set default language
  if ($lang == "") $lang = "en";
  
    // check if sent data is available
  if (!valid_publicationname ($site) || !valid_objectname ($group_name) || !valid_objectname ($user))
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['ontext0'][$lang]."</span><br />\n".$hcms_lang['ontext0'][$lang]."\n";
  }
  else
  {
    // eventsystem
    if ($eventsystem['ondeletegroup_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
      ondeletegroup_pre ($group_name, $user);   
      
    // check if login is an attribute of a sent string and extract group name
    if (strpos ($group_name, ".php") > 0) $group_name = getattribute ($group_name, "group_name");
    
    $group_name = trim ($group_name);
    
    // load user xml file
    $groupdata = loadlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php", 3);

    // set permissions
    if (is_array ($permission) && $groupdata != false)
    {
      // user permissions
      if (!isset ($permission['desktopglobal']) || $permission['desktopglobal'] != 1) {$permission['desktopglobal'] = 0;}
      if (!isset ($permission['desktopsetting']) || $permission['desktopsetting'] != 1) {$permission['desktopsetting'] = 0;}
      if (!isset ($permission['desktoptaskmgmt']) || $permission['desktoptaskmgmt'] != 1) {$permission['desktoptaskmgmt'] = 0;}
      if (!isset ($permission['desktopcheckedout']) || $permission['desktopcheckedout'] != 1) {$permission['desktopcheckedout'] = 0;}
      if (!isset ($permission['desktoptimetravel']) || $permission['desktoptimetravel'] != 1) {$permission['desktoptimetravel'] = 0;}
      if (!isset ($permission['desktopfavorites']) || $permission['desktopfavorites'] != 1) {$permission['desktopfavorites'] = 0;}
      if (!isset ($permission['desktopprojectmgmt']) || $permission['desktopprojectmgmt'] != 1) {$permission['desktopprojectmgmt'] = 0;} // added in version 6.0.1
      
      $desktoppermissions = "desktop=".$permission['desktopglobal'].$permission['desktopsetting'].$permission['desktoptaskmgmt'].$permission['desktopcheckedout'].$permission['desktoptimetravel'].$permission['desktopfavorites'].$permission['desktopprojectmgmt'];      

      // user permissions
      if (!isset ($permission['userglobal']) || $permission['userglobal'] != 1) {$permission['userglobal'] = 0;}
      if (!isset ($permission['usercreate']) || $permission['usercreate'] != 1) {$permission['usercreate'] = 0;}
      if (!isset ($permission['userdelete']) || $permission['userdelete'] != 1) {$permission['userdelete'] = 0;}
      if (!isset ($permission['useredit']) || $permission['useredit'] != 1) {$permission['useredit'] = 0;}
      
      $userpermissions = "user=".$permission['userglobal'].$permission['usercreate'].$permission['userdelete'].$permission['useredit'];
      
      // group permissions
      if (!isset ($permission['groupglobal']) || $permission['groupglobal'] != 1) {$permission['groupglobal'] = 0;}
      if (!isset ($permission['groupcreate']) || $permission['groupcreate'] != 1) {$permission['groupcreate'] = 0;}
      if (!isset ($permission['groupdelete']) || $permission['groupdelete'] != 1) {$permission['groupdelete'] = 0;}
      if (!isset ($permission['groupedit']) || $permission['groupedit'] != 1) {$permission['groupedit'] = 0;}
      
      $grouppermissions = "group=".$permission['groupglobal'].$permission['groupcreate'].$permission['groupdelete'].$permission['groupedit'];
      
      // site permissions
      if (!isset ($permission['siteglobal']) || $permission['siteglobal'] != 1) {$permission['siteglobal'] = 0;}
      if (!isset ($permission['sitecreate']) || $permission['sitecreate'] != 1) {$permission['sitecreate'] = 0;}
      if (!isset ($permission['sitedelete']) || $permission['sitedelete'] != 1) {$permission['sitedelete'] = 0;}
      if (!isset ($permission['siteedit']) || $permission['siteedit'] != 1) {$permission['siteedit'] = 0;}
      
      $sitepermissions = "site=".$permission['siteglobal'].$permission['sitecreate'].$permission['sitedelete'].$permission['siteedit'];
      
      // personalization permissions
      if (!isset ($permission['persglobal']) || $permission['persglobal'] != 1) {$permission['persglobal'] = 0;}
      if (!isset ($permission['perstrack']) || $permission['perstrack'] != 1) {$permission['perstrack'] = 0;}
      if (!isset ($permission['perstrackcreate']) || $permission['perstrackcreate'] != 1) {$permission['perstrackcreate'] = 0;}
      if (!isset ($permission['perstrackdelete']) || $permission['perstrackdelete'] != 1) {$permission['perstrackdelete'] = 0;}
      if (!isset ($permission['perstrackedit']) || $permission['perstrackedit'] != 1) {$permission['perstrackedit'] = 0;}
      if (!isset ($permission['persprof']) || $permission['persprof'] != 1) {$permission['persprof'] = 0;}
      if (!isset ($permission['persprofcreate']) || $permission['persprofcreate'] != 1) {$permission['persprofcreate'] = 0;}
      if (!isset ($permission['persprofdelete']) || $permission['persprofdelete'] != 1) {$permission['persprofdelete'] = 0;}
      if (!isset ($permission['persprofedit']) || $permission['persprofedit'] != 1) {$permission['persprofedit'] = 0;}
      
      $perspermissions = "pers=".$permission['persglobal'].$permission['perstrack'].$permission['perstrackcreate'].$permission['perstrackdelete'].$permission['perstrackedit'].$permission['persprof'].$permission['persprofcreate'].$permission['persprofdelete'].$permission['persprofedit'];

      // workflow permissions
      if (!isset ($permission['workflowglobal']) || $permission['workflowglobal'] != 1) {$permission['workflowglobal'] = 0;}
      if (!isset ($permission['workflowproc']) || $permission['workflowproc'] != 1) {$permission['workflowproc'] = 0;}
      if (!isset ($permission['workflowproccreate']) || $permission['workflowproccreate'] != 1) {$permission['workflowproccreate'] = 0;}
      if (!isset ($permission['workflowprocdelete']) || $permission['workflowprocdelete'] != 1) {$permission['workflowprocdelete'] = 0;}
      if (!isset ($permission['workflowprocedit']) || $permission['workflowprocedit'] != 1) {$permission['workflowprocedit'] = 0;}
      if (!isset ($permission['workflowprocfolder']) || $permission['workflowprocfolder'] != 1) {$permission['workflowprocfolder'] = 0;}
      if (!isset ($permission['workflowscript']) || $permission['workflowscript'] != 1) {$permission['workflowscript'] = 0;}
      if (!isset ($permission['workflowscriptcreate']) || $permission['workflowscriptcreate'] != 1) {$permission['workflowscriptcreate'] = 0;}
      if (!isset ($permission['workflowscriptdelete']) || $permission['workflowscriptdelete'] != 1) {$permission['workflowscriptdelete'] = 0;}
      if (!isset ($permission['workflowscriptedit']) || $permission['workflowscriptedit'] != 1) {$permission['workflowscriptedit'] = 0;}
      
      $workflowpermissions = "workflow=".$permission['workflowglobal'].$permission['workflowproc'].$permission['workflowproccreate'].$permission['workflowprocdelete'].$permission['workflowprocedit'].$permission['workflowprocfolder'].$permission['workflowscript'].$permission['workflowscriptcreate'].$permission['workflowscriptdelete'].$permission['workflowscriptedit'];  

      // template permissions
      if (!isset ($permission['templateglobal']) || $permission['templateglobal'] != 1) {$permission['templateglobal'] = 0;}
      if (!isset ($permission['tpl']) || $permission['tpl'] != 1) {$permission['tpl'] = 0;}
      if (!isset ($permission['tplcreate']) || $permission['tplcreate'] != 1) {$permission['tplcreate'] = 0;}
      if (!isset ($permission['tpldelete']) || $permission['tpldelete'] != 1) {$permission['tpldelete'] = 0;}
      if (!isset ($permission['tpledit']) || $permission['tpledit'] != 1) {$permission['tpledit'] = 0;}
      
      $templatepermissions = "template=".$permission['templateglobal'].$permission['tpl'].$permission['tplcreate'].$permission['tpldelete'].$permission['tpledit'];

      // template media permissions
      if (!isset ($permission['tplmedia']) || $permission['tplmedia'] != 1) {$permission['tplmedia'] = 0;}
      if (!isset ($permission['tplmediacatcreate']) || $permission['tplmediacatcreate'] != 1) {$permission['tplmediacatcreate'] = 0;}
      if (!isset ($permission['tplmediacatdelete']) || $permission['tplmediacatdelete'] != 1) {$permission['tplmediacatdelete'] = 0;}
      if (!isset ($permission['tplmediacatrename']) || $permission['tplmediacatrename'] != 1) {$permission['tplmediacatrename'] = 0;}
      if (!isset ($permission['tplmediaupload']) || $permission['tplmediaupload'] != 1) {$permission['tplmediaupload'] = 0;}
      if (!isset ($permission['tplmediadelete']) || $permission['tplmediadelete'] != 1) {$permission['tplmediadelete'] = 0;}
      
      $mediapermissions = "media=".$permission['tplmedia'].$permission['tplmediacatcreate'].$permission['tplmediacatdelete'].$permission['tplmediacatrename'].$permission['tplmediaupload'].$permission['tplmediadelete'];

      // component permissions
      if (!isset ($permission['componentglobal']) || $permission['componentglobal'] != 1) {$permission['componentglobal'] = 0;}    
      if (!isset ($permission['compupload']) || $permission['compupload'] != 1) {$permission['compupload'] = 0;}
      if (!isset ($permission['compdownload']) || $permission['compdownload'] != 1) {$permission['compdownload'] = 0;}
      if (!isset ($permission['compsendlink']) || $permission['compsendlink'] != 1) {$permission['compsendlink'] = 0;}  
      if (!isset ($permission['compfoldercreate']) || $permission['compfoldercreate'] != 1) {$permission['compfoldercreate'] = 0;}
      if (!isset ($permission['compfolderdelete']) || $permission['compfolderdelete'] != 1) {$permission['compfolderdelete'] = 0;}
      if (!isset ($permission['compfolderrename']) || $permission['compfolderrename'] != 1) {$permission['compfolderrename'] = 0;}
      if (!isset ($permission['compcreate']) || $permission['compcreate'] != 1) {$permission['compcreate'] = 0;}
      if (!isset ($permission['compdelete']) || $permission['compdelete'] != 1) {$permission['compdelete'] = 0;}
      if (!isset ($permission['comprename']) || $permission['comprename'] != 1) {$permission['comprename'] = 0;}
      if (!isset ($permission['comppublish']) || $permission['comppublish'] != 1) {$permission['comppublish'] = 0;}
      
      $componentpermissions = "component=".$permission['componentglobal'].$permission['compupload'].$permission['compdownload'].$permission['compsendlink'].$permission['compfoldercreate'].$permission['compfolderdelete'].$permission['compfolderrename'].$permission['compcreate'].$permission['compdelete'].$permission['comprename'].$permission['comppublish'];

      // content permissions
      if (!isset ($permission['pageglobal']) || $permission['pageglobal'] != 1) {$permission['pageglobal'] = 0;}
      if (!isset ($permission['pagesendlink']) || $permission['pagesendlink'] != 1) {$permission['pagesendlink'] = 0;}    
      if (!isset ($permission['pagefoldercreate']) || $permission['pagefoldercreate'] != 1) {$permission['pagefoldercreate'] = 0;}
      if (!isset ($permission['pagefolderdelete']) || $permission['pagefolderdelete'] != 1) {$permission['pagefolderdelete'] = 0;}
      if (!isset ($permission['pagefolderrename']) || $permission['pagefolderrename'] != 1) {$permission['pagefolderrename'] = 0;}
      if (!isset ($permission['pagecreate']) || $permission['pagecreate'] != 1) {$permission['pagecreate'] = 0;}
      if (!isset ($permission['pagedelete']) || $permission['pagedelete'] != 1) {$permission['pagedelete'] = 0;}
      if (!isset ($permission['pagerename']) || $permission['pagerename'] != 1) {$permission['pagerename'] = 0;}
      if (!isset ($permission['pagepublish']) || $permission['pagepublish'] != 1) {$permission['pagepublish'] = 0;}
      
      $pagepermissions = "page=".$permission['pageglobal'].$permission['pagesendlink'].$permission['pagefoldercreate'].$permission['pagefolderdelete'].$permission['pagefolderrename'].$permission['pagecreate'].$permission['pagedelete'].$permission['pagerename'].$permission['pagepublish'];
    
      // permission string
      $permission_str = $desktoppermissions."&".$sitepermissions."&".$userpermissions."&".$grouppermissions."&".$perspermissions."&".$workflowpermissions."&".$templatepermissions."&".$mediapermissions."&".$componentpermissions."&".$pagepermissions;
      
      // insert values into xml object
      if ($permission_str != "") $groupdata = setcontent ($groupdata, "<usergroup>", "<permission>", $permission_str, "<groupname>", $group_name);      
    }
    
    // page folder access
    if (is_array ($pageaccess) && $groupdata != "")
    {
      $pageaccess_str = "";
      
      foreach ($pageaccess as $entry)
      {
        if ($entry != "")
        {
          // versions before 5.6.3 used folder path instead of object id
          if (substr_count ($entry, "/") > 0) $object_id = rdbms_getobject_id ($entry);
          else $object_id = $entry;
          
          // in case the database doesn't hold the object ID
          if (!$object_id) $object_id = $entry;
          
          $pageaccess_str .= convertpath ($site, $object_id, "page")."|";
        }
      }
      
      // insert values into xml object
      $groupdata = setcontent ($groupdata, "<usergroup>", "<pageaccess>", $pageaccess_str, "<groupname>", $group_name); 
    }
    
    // component folder access
    if (is_array ($compaccess) && $groupdata != "")
    {
      $compaccess_str = "";
      
      foreach ($compaccess as $entry)
      {
        if ($entry != "")
        {
          // versions before 5.6.3 used folder path instead of object id
          if (substr_count ($entry, "/") > 0) $object_id = rdbms_getobject_id ($entry);
          else $object_id = $entry;
          
          // in case the database doesn't hold the object ID
          if (!$object_id) $object_id = $entry;
          
          $compaccess_str .= convertpath ($site, $object_id, "comp")."|";
        }
      }
      
      // insert values into xml object
      $groupdata = setcontent ($groupdata, "<usergroup>", "<compaccess>", $compaccess_str, "<groupname>", $group_name); 
    }

    // save and unlock file
    if ($groupdata != false)
    {
      // eventsystem
      if ($eventsystem['onsavegroup_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
        onsavegroup_pre ($group_name, $groupdata, $user);       
    
      $test = savelockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php", $groupdata);
      
      if ($test != false)
      {
        // eventsystem
        if ($eventsystem['onsavegroup_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
          onsavegroup_post ($group_name, $groupdata, $user);  
                
        $add_onload = "parent.location.href='group_edit_form.php?site=".url_encode($site)."&group_name=".url_encode($group_name)."&preview=no'; ";
        $show = "<span class=hcmsHeadline>".$hcms_lang['group-settings-were-updated'][$lang]."</span>\n";
        $error_switch = "no";
      }
      else
      {
        //unlock file
        unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");
        
        $add_onload = "";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-group-information-cant-be-accessed'][$lang]."</span><br />\n".$hcms_lang['the-group-information-is-missing-or-you-have-no-write-permission'][$lang]."\n";

      }
    }
    else
    {
      //unlock file
      unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");
      
      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-group-information-cant-be-accessed'][$lang]."</span><br />\n".$hcms_lang['an-error-occured-in-function-setcontent'][$lang]."\n";
    }
  }
  
  // return results
  $result = array();
  
  if (isset ($error_switch) && $error_switch == "no") $result['result'] = true;
  else $result['result'] = false;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;  
  
  return $result;  
}

// ---------------------------------------- deletegroup --------------------------------------------
// function: deletegroup()
// input: publication name, group name, user name
// output: array

// description:
// This function removes a user group

function deletegroup ($site, $group_name, $user)
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  $add_onload = "";
  $show = "";
  
  // set default language
  if ($lang == "") $lang = "en";
  
  // check if sent data is available
  if (!valid_publicationname ($site) || !valid_objectname ($group_name) || !valid_objectname ($user))
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['necessary-group-name-is-missing'][$lang]."</span><br />\n".$hcms_lang['please-select-a-group-to-remove'][$lang]."\n";
  }
  else
  {
    // eventsystem
    if ($eventsystem['ondeletegroup_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
      ondeletegroup_pre ($group_name, $user);   
      
    // check if login is an attribute of a sent string and extract group name
    if (strpos ($group_name, ".php") > 0) $group_name = getattribute ($group_name, "group_name");
    
    $group_name = trim ($group_name);
      
    // load users and check if users are still group members
    $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php"); 
    
    if ($userdata != false && $userdata != "")
    {
      $member_array = selectxmlcontent ($userdata, "<memberof>", "<publication>", "$site");
      
      if (is_array ($member_array))
      {
        $member_string = implode ("\n", $member_array);
        $member_array = selectxmlcontent ($member_string, "<memberof>", "<usergroup>", "*|$group_name|*");
  
        if (is_array ($member_array) && sizeof ($member_array) > 0) $user_exists = true;
        else $user_exists = false;
      }
    }
    else $user_exists = true;
  
    // load groups and delete group node
    if ($user_exists == false)
    {
      if (@fopen ($mgmt_config['abs_path_data']."user/".$site.".usergroup.xml.php", "r+"))
      {
        // load user xml file
        $usergroupdata = loadlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php", 3);
        
        if ($usergroupdata != false)
        {
          // delete user
          $usergroupdatanew = deletecontent ($usergroupdata, "<usergroup>", "<groupname>", $group_name);
             
          if ($usergroupdatanew != false)
          {
            // save user xml file
            $test = savelockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php", $usergroupdatanew);
      
            if ($test != false)
            {
              // eventsystem
              if ($eventsystem['ondeletegroup_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
                ondeletegroup_post ($group_name, $user);           
            
              $usergroupdata = $usergroupdatanew;
            
              $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
              $show = "<span class=hcmsHeadline>".$hcms_lang['the-group-was-removed'][$lang]."</span><br />\n".$hcms_lang['all-group-information-was-successfully-removed'][$lang]."\n";
              $error_switch = "no";
            }
            else
            {
              //unlock file
              unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");          
            
              $add_onload = "";
              $show = "<span class=hcmsHeadline>".$hcms_lang['group-information-cannot-be-accessed'][$lang]."</span><br />\n".$hcms_lang['group-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";          
            }
          }
          else
          {
            //unlock file
            unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");
        
            $add_onload = "";
            $show = "<span class=hcmsHeadline>".$hcms_lang['group-information-could-not-be-removed'][$lang]."</span><br />\n";
          }
        } 
        else
        {
          //unlock file
          unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");
        
          $add_onload = "";
          $show = "<span class=hcmsHeadline>".$hcms_lang['group-information-cannot-be-accessed'][$lang]."</span><br />\n".$hcms_lang['group-information-is-missing-or-you-do-not-have-read-permissions'][$lang]."\n";
        }   
      }
      else
      {
        //unlock file
        unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");
      
        $add_onload = "";
        $show = "<span class=hcmsHeadline>".$hcms_lang['group-information-cannot-be-accessed'][$lang]."</span><br />\n".$hcms_lang['group-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
      }
    }
    else
    {
      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['group-information-could-not-be-removed'][$lang]."</span><br />\n".$hcms_lang['users-are-still-members-of-this-group'][$lang]."\n";
    }  
  }

  // return results
  $result = array();
  
  if (isset ($error_switch) && $error_switch == "no") $result['result'] = true;
  else $result['result'] = false;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;  
  
  return $result;  
}

// ---------------------------------------- renamegroupfolder --------------------------------------------
// function: renamegroupfolder()
// input: site, cat[page,comp], old location, new location, user
// output: true / false on error

// description:
// This function renames a workgroup folder

function renamegroupfolder ($site, $cat, $folder_curr, $folder_new, $user)
{
  global $mgmt_config, $hcms_lang, $lang;
  
  // if database is used the object ID's will be used instead of pathes (since version 5.6.4)
  if ($mgmt_config['db_connect_rdbms'] != "") return true;
  
  if (valid_publicationname ($site) && ($cat == "page" || $cat == "comp") && valid_locationname ($folder_curr) && valid_locationname ($folder_new) && valid_objectname ($user))
  {
    // rename folder in usergroup folderaccess
    $usergroupdata = loadlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php", 3);
  
    if ($usergroupdata != false)
    {
      $folder_curr = convertpath ($site, $folder_curr, $cat);
      $folder_new = convertpath ($site, $folder_new, $cat);
      
      if (getobject ($folder_curr) == ".folder") $folder_curr = getlocation ($folder_curr);
      if (getobject ($folder_new) == ".folder") $folder_new = getlocation ($folder_new);
    
      $usergroupdata = str_replace ($folder_curr, $folder_new, $usergroupdata);
      
      if ($usergroupdata != false) return savelockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php", $usergroupdata);
      else return unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");
    }
    // file is locked or does not exist
    else
    {
      // unlock file
      unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");
      return true;
    }
  }
  else return false;
}

// ---------------------------------------- deletegroupfolder --------------------------------------------
// function: deletegroupfolder()
// input: site, cat[page,comp], path to the folder, user
// output: true / false on error

// description:
// This function removes a group folder

function deletegroupfolder ($site, $cat, $folderpath, $user)
{
  global $mgmt_config, $hcms_lang, $lang;

  // if database is used the object ID's will be used instead of pathes (since version 5.6.4)
  if ($mgmt_config['db_connect_rdbms'] != "") return true;
  
  if (valid_publicationname ($site) && ($cat == "page" || $cat == "comp") && valid_locationname ($folderpath) && valid_objectname ($user))
  {
    $folderpath = convertpath ($site, $folderpath, $cat);
    if (getobject ($folderpath) == ".folder") $folderpath = getlocation ($folderpath);
  
    // rename folder in usergroup folderaccess
    $usergroupdata = loadlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php", 3);
  
    if ($usergroupdata != false)
    {
      $folderpos_offset = 0;
      
      for ($i = 1; $i <= substr_count ($usergroupdata, $folderpath); $i++)
      {
        $folderpos1 = strpos ($usergroupdata, $folderpath, $folderpos_offset);
        $folderpos2 = strpos ($usergroupdata, "|", $folderpos1);
        $usergroupdata = substr_replace ($usergroupdata, "", $folderpos1, $folderpos2+1-$folderpos1);
        $folderpos_offset = $folderpos2;
      }
      
      if ($usergroupdata != false) return savelockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php", $usergroupdata);
      else return unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");
    }
    else
    {
      // unlock file
      unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");
      return true;
    }
  }
  else return false;
}

// ---------------------------------------- renameworkflowfolder --------------------------------------------
// function: renameworkflowfolder()
// input: site, cat[page,comp], old location, new location, user
// output: true / false on error

// description:
// This function renames a workgroup folder

function renameworkflowfolder ($site, $cat, $folder_curr, $folder_new, $user)
{
  global $mgmt_config, $hcms_lang, $lang;
  
  // if database is used the object ID's will be used instead of pathes (since version 5.6.4)
  if ($mgmt_config['db_connect_rdbms'] != "") return true;

  if (valid_publicationname ($site) && ($cat == "page" || $cat == "comp") && valid_locationname ($folder_curr) && valid_locationname ($folder_new) && valid_objectname ($user))
  {
    // rename folder in user folderaccess
    $workflowdata = loadlockfile ($user, $mgmt_config['abs_path_data']."workflow_master/", $site.".".$cat.".folder.dat", 3);
  
    if ($workflowdata != false)
    {
      $folder_curr = convertpath ($site, $folder_curr, $cat);
      $folder_new = convertpath ($site, $folder_new, $cat);
    
      $workflowdata = str_replace ($folder_curr, $folder_new, $workflowdata);
      
      if ($workflowdata != false) return savelockfile ($user, $mgmt_config['abs_path_data']."workflow_master/", $site.".".$cat.".folder.dat", $workflowdata);
      else return unlockfile ($user, $mgmt_config['abs_path_data']."workflow_master/", $site.".".$cat.".folder.dat");
    }
    // file is locked or does not exist
    else
    {
      // unlock file
      unlockfile ($user, $mgmt_config['abs_path_data']."workflow_master/", $site.".".$cat.".folder.dat");
      return true;
    }
  }
  else return false;
}

// ---------------------------------------- deleteworkflowfolder --------------------------------------------
// function: deleteworkflowfolder()
// input: site, cat[page,comp], location of folder, user
// output: true / false on error

// description:
// This function removes a workgroup folder

function deleteworkflowfolder ($site, $cat, $folderpath, $user)
{
  global $mgmt_config, $hcms_lang, $lang;
  
  // if database is used the object ID's will be used instead of pathes (since version 5.6.4)
  if ($mgmt_config['db_connect_rdbms'] != "") return true;
  
  if (valid_publicationname ($site) && ($cat == "page" || $cat == "comp") && valid_locationname ($folderpath) && valid_objectname ($user))
  {
    // rename folder in user folderaccess
    $workflowdata = loadlockfile ($user, $mgmt_config['abs_path_data']."workflow_master/", $site.".".$cat.".folder.dat", 3);
  
    if ($workflowdata != false)
    {
      $folderpath = convertpath ($site, $folderpath, $cat);
      
      $wf_array = explode ("\n", trim ($workflowdata));                 
                
      if (is_array ($wf_array) && sizeof ($wf_array) >= 1)
      {
        for ($i = 0; $i < sizeof ($wf_array); $i++)
        {
          if (strpos ($wf_array[$i], $folderpath) > 0) unset ($wf_array[$i]);
        }
      
        $workflowdata = implode ("\n", $wf_array); 
      
        if ($workflowdata != false) return savelockfile ($user, $mgmt_config['abs_path_data']."workflow_master/", $site.".".$cat.".folder.dat", $workflowdata);
        else return unlockfile ($user, $mgmt_config['abs_path_data']."workflow_master/", $site.".".$cat.".folder.dat");
      }
      else return unlockfile ($user, $mgmt_config['abs_path_data']."workflow_master/", $site.".".$cat.".folder.dat");
    }
    else
    {
      // unlock file
      unlockfile ($user, $mgmt_config['abs_path_data']."workflow_master/", $site.".".$cat.".folder.dat");
      return true;
    }
  }
  else return false;
}

// ========================================= MEDIA CATEGORIES ============================================

// This is mainly used for the simple file categorization and handling of template media

// ------------------------- createmediacat -----------------------------
// function: createmediacat()
// input: publication, media category name  
// output: Array with onload JS-code and message

// description:
// Creates a new media category in the template media index file. Only used for template media.

function createmediacat ($site, $mediacat_name)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  if (session_id() != "") $session_id = session_id();
  else $session_id = createuniquetoken ();
  
  $show = "";
  $add_onload = "";
  
  // set default language
  if ($lang == "") $lang = "en";
  
  // check if folder exists already
  if (!valid_publicationname ($site) || $mediacat_name == "" || strlen ($mediacat_name) > 100)
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['please-fill-in-a-category-name'][$lang]."</span>\n";
  }
  // test if folder name includes special characters
  elseif (specialchr ($mediacat_name, " -_") == true)
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['special-characters-in-expressions-are-not-allowed'][$lang]."</span>\n";
  }
  else
  {
    $mediacat_name = trim ($mediacat_name);
    
    // define media index file name
    $datafile = $site.".media.tpl.dat";
    
    // load categories
    $mediacat_data = loadlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile, 3);  
    $mediacat_array_work = explode ("\n", $mediacat_data);
  
    foreach ($mediacat_array_work as $mediacat_record)
    {
      list ($category, $files) = explode (":|", $mediacat_record);
  
      if ($category == $mediacat_name)
      {
        $add_onload = "";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-media-category-exists-already'][$lang]."</span><br />\n".$hcms_lang['please-try-another-category-name'][$lang]."\n";
  
        break;
      }
    }
  }
  
  // check if folder could be created
  if ($show == "")
  {
    // append new category
    if (sizeof ($mediacat_array_work) >= 1)
    {
      $mediacat_array_work[sizeof ($mediacat_array_work)] = $mediacat_name.":|";
      $mediacat_data = implode ("\n", $mediacat_array_work);
    }
    else
    {
      $mediacat_array_work[0] = $mediacat_name.":|";
      $mediacat_data = $mediacat_array_work[0];
    }
  
    // save file
    $savefile = savelockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile, $mediacat_data);
  
    if ($savefile != false)
    {
      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['media-category-was-created'][$lang]."</span><br />\n";
    }
    else
    {
      // unlock file
      unlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile);
  
      // folder could't not be created due to missing write permission
      $add_onload =  "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['media-category-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
    }
  }
  else
  {
    // unlock file
    unlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile);
  }
  
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  
  return $result;
}

// ------------------------- renamemediacat -----------------------------
// function: renamemediacat()
// input: publication, old media category name, new media category name
// output: Array with onload JS-code and message

// description:
// Renames a new media category in the template media index file

function renamemediacat ($site, $mediacat_name_curr, $mediacat_name)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  if (session_id() != "") $session_id = session_id();
  else $session_id = createuniquetoken ();
  
  $show = "";
  $add_onload = "";
  
  // set default language
  if ($lang == "") $lang = "en";

  // check if folder exists already
  if (!valid_publicationname ($site) || $mediacat_name_curr == "" || $mediacat_name == "" || strlen ($mediacat_name) > 100)
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['please-fill-in-a-category-name'][$lang]."</span>\n";
  }
  // test if folder name includes special characters
  elseif (specialchr ($mediacat_name, " -_") == true)
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['special-characters-in-expressions-are-not-allowed'][$lang]."</span>\n";
  }
  else
  {
    // define media index file name
    $datafile = $site.".media.tpl.dat";
      
    // load categories
    $mediacat_data = loadlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile, 3);
    $mediacat_array_work = explode ("\n", $mediacat_data);
  
    $i = 0;
  
    foreach ($mediacat_array_work as $mediacat_record)
    {
      list ($category, $files) = explode (":|", $mediacat_record);
  
      if ($category == $mediacat_name_curr)
      {
        // make entry in media database file
        $mediacat_array_work[$i] = $mediacat_name.":|".$files;
        break;
      }
  
      $i++;
    }
  }
  
  // check if folder could be created
  if ($show == "")
  {
    // append new category
    $mediacat_data = implode ("\n", $mediacat_array_work);
  
    // save file
    $savefile = savelockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile, $mediacat_data);
  
    if ($savefile != false)
    {
      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-media-category-was-successfully-renamed'][$lang]."</span>\n";
    }
    else
    {
      // unlock file
      unlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile);
  
      // folder could't not be created due to missing write permission
      $add_onload =  "";
      $show = "<span class=hcmsHeadline> ".$hcms_lang['the-media-category-could-not-be-renamed'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
    }
  }
  else
  {
    // unlock file
    unlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile);
  }
  
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  
  return $result;
}

// ------------------------- deletemediacat -----------------------------
// function: deletemediacat()
// input: publication, media category name  
// output: Array with onload JS-code and message

// description:
// Deletes a new media category in the template media index file

function deletemediacat ($site, $mediacat_name)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  if (session_id() != "") $session_id = session_id();
  else $session_id = createuniquetoken ();
  
  $show = "";
  $add_onload = "";
  
  // set default language
  if ($lang == "") $lang = "en";
  
  // check if mediacat was sent
  if (!valid_publicationname ($site) || $mediacat_name == "")
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['please-select-a-media-category'][$lang]."</span>\n";
  }
  else
  {
    // define media index file name
    $datafile = $site.".media.tpl.dat";
    
    // load categories
    $mediacat_data = loadlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile, 3);  
    $mediacat_array_work = explode ("\n", $mediacat_data);
  
    $i = 0;
  
    foreach ($mediacat_array_work as $mediacat_record)
    {
      list ($category, $files) = explode (":|", $mediacat_record);
  
      if ($category == $mediacat_name && ($files == "\n" || $files == ""))
      {
        // make entry in media database file
        array_splice ($mediacat_array_work, $i, 1);  
        $delete = true;  
        break;
      }
      else $delete = false;
  
      $i++;
    }
  }
  
  if ($show == "" && $delete == true)
  {
    // implode array
    $mediacat_data = implode ("\n", $mediacat_array_work);
  
    // save file
    $savefile = savelockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile, $mediacat_data);
  
    if ($savefile != false)
    {
      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-media-category-was-deleted'][$lang]."</span>\n";
    }
    else
    {
      // unlock file
      unlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile);
  
      // folder could not be deleted due to missing write permission
      $add_onload =  "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-media-category-could-not-be-deleted'][$lang]."</span><br />\n".$hcms_lang['you-have-no-write-permission'][$lang]."\n";
    }
  }
  else
  {
    // unlock file
    unlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile);
  
    // folder could not be deleted due to not empty category
    $add_onload =  "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['the-media-category-could-not-be-deleted'][$lang]."</span><br />\n".$hcms_lang['the-category-still-holds-files'][$lang]."\n";
  }
  
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  
  return $result;  
}

// ------------------------- uploadtomediacat -----------------------------
// function: uploadtomediacat()
// input: publication, media category name, PHP FILES array
// output: Array with onload JS-code and message

// description:
// Uploads a media file into a given template media category

function uploadtomediacat ($site, $mediacat_name, $global_files)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  if (session_id() != "") $session_id = session_id();
  else $session_id = createuniquetoken ();
  
  // set default language
  if ($lang == "") $lang = "en";
  
  // define variables
  $sizelim = "yes"; //do you want size limitations yes or no
  $size = "10000000"; //if you want size limited how many bytes
  $certtype = "no"; //do you want certain type of file, no recommended
  $type = "image/gif;image/jpeg;image/png;image/pjpeg;application/x-shockwave-flash"; //what types of file would you like, use ; as seperator 
  
  if (valid_publicationname ($site))
  {
    $datafile = $site.".media.tpl.dat";
    $mediadir = $mgmt_config['abs_path_tplmedia'].$site."/";
    $mediaurl = $mgmt_config['url_path_tplmedia'].$site."/";
  }
  
  $show = "";
  
  // replace freespaces with underline
  if (preg_match ("/ /", $global_files['file']['name']))
  {
    $filename_new = str_replace (" ", "_", $global_files['file']['name']);
  }
  else $filename_new = $global_files['file']['name'];
  
  // error if no file is selected
  if (!valid_publicationname ($site) || !valid_objectname ($mediacat_name) || $global_files['file']['name'] == "")
  {
    $show = "<span class=hcmsHeadline>".$hcms_lang['no-file-selected-to-upload'][$lang]."</span>\n";
  }
  // test if folder name includes special characters
  elseif (specialchr ($filename_new, ".-_") == true)
  {
    $show = "<span class=hcmsHeadline>".$hcms_lang['special-characters-in-file-names-are-not-allowed'][$lang]."</span>\n";
  }
  // error if file is to big
  elseif ($sizelim == "yes" && $global_files["file"]["size"] > $size)
  {
    $show = "<span class=hcmsHeadline>".$hcms_lang['the-file-you-are-trying-to-upload-is-too-big'][$lang]."</span>\n";
  }
  // error if file isn't certain type
  elseif ($certtype == "yes" && substr_count ($type, $global_files["file"]["type"]) > 0)
  {
    $show = "<span class=hcmsHeadline>".$hcms_lang['the-file-you-are-trying-to-upload-is-wrong-type'][$lang]." (".$global_files["file"]["type"].")</span>\n";
  }
  // error if file exists
  elseif (is_file ($mediadir.$filename_new))
  {
    $show = "<span class=hcmsHeadline>".$hcms_lang['the-file-you-are-trying-to-upload-already-exists'][$lang]."</span><br />".$hcms_lang['please-note-the-media-file-name-in-the-media-database-must-be-unique'][$lang]."\n";
  }
  // upload file
  else
  {
    // upload file
    @move_uploaded_file ($global_files['file']['tmp_name'], $mediadir.$filename_new) or $show = "<span class=hcmsHeadline>".$hcms_lang['the-file-you-are-trying-to-upload-couldnt-be-copied-to-the-server'][$lang]."</span>\n";
  
    if ($show == "")
    {
      // remote client
      remoteclient ("save", "abs_path_tplmedia", $site, $mediadir, "", $filename_new, ""); 
        
      // load categories
      $mediacat_data = loadlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile, 3);
    
      $mediacat_array_work = explode ("\n", $mediacat_data);
    
      $i = 0;
    
      foreach ($mediacat_array_work as $mediacat_record)
      {
        list ($mediacategory, $files) = explode (":|", $mediacat_record);
    
        if ($mediacategory == $mediacat_name)
        {
          // make entry in media database file
          $mediacat_array_work[$i] = chop ($mediacat_array_work[$i]).$global_files['file']['name']."|";
    
          // append new media file
          $mediacat_data = implode ("\n", $mediacat_array_work);
    
          // save file
          if ($mediacat_data != "") 
          {
            $savefile = savelockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile, $mediacat_data);
            if ($savefile == false) unlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile);
          }
          else unlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile);
    
          break;
        }
    
        $i++;
      }
    
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['uploaded-file'][$lang]."</span><br />
      ".$hcms_lang['file-name'][$lang].": <span class=\"hcmsHeadlineTiny\">".$global_files['file']['name']."</span><br />
      ".$hcms_lang['file-size'][$lang].": <span class=\"hcmsHeadlineTiny\">".$global_files['file']['size']." bytes</span><br />
      ".$hcms_lang['file-type'][$lang].": <span class=\"hcmsHeadlineTiny\">".$global_files['file']['type']."</span>\n";
    }
  }
  
  $result['add_onload'] = "";
  $result['message'] = $show;
  
  return $result;  
}

// ------------------------- deletefrommediacat -----------------------------
// function: deletefrommediacat()
// input: publication, media file name
// output: Array with onload JS-code and message

// description:
// Deletes a media file from the template media category index

function deletefrommediacat ($site, $mediafile)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  if (session_id() != "") $session_id = session_id();
  else $session_id = createuniquetoken ();  
  
  // set default language
  if ($lang == "") $lang = "en";        

  if (valid_publicationname ($site) && valid_objectname ($mediafile))
  {
    // define media index file name
    $datafile = $site.".media.tpl.dat";
    $mediadir = $mgmt_config['abs_path_tplmedia'];
    $mediaurl = $mgmt_config['url_path_tplmedia']; 
    
    if (is_file ($mediadir.$mediafile))
    {    
      // load media database index
      $mediacat = loadlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile, 3);
  
      // remove media file from index
      $mediacat = str_replace (getobject ($mediafile)."|", "", $mediacat);
  
      // save media database index
      $test = savelockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile, $mediacat);
  
      // remove media file
      if ($test != false) 
      {
        $test = deletefile ($mediadir, $mediafile, 0);
      
        // remote client
        remoteclient ("delete", "abs_path_media", $site, $mediadir, "", $mediafile, "");
                            
        $add_onload = "goToURL('parent.frames[\'mainFrame2\']','".$mgmt_config['url_path_cms']."empty.php'); return document.returnValue; ";
  
        $show = "<table width=\"400\" border=0 cellspacing=1 cellpadding=3 class=\"hcmsMessage\">
        <tr>
         <td><span class=hcmsHeadline>".$hcms_lang['the-selected-media-file-was-removed'][$lang]."</span></td>
        </tr>
      </table>\n";      
      }  
      else
      {
        unlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile);
          
        $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php?site=".url_encode($site)."'; ";
    
        $show = "<table width=\"400\" border=0 cellspacing=1 cellpadding=3 class=\"hcmsMessage\">
        <tr>
         <td><span class=hcmsHeadline>".$hcms_lang['the-selected-media-file-could-not-be-removed'][$lang]."</span></td>
        </tr>
      </table>\n";    
      }
    }
    else
    {
      $add_onload = "";
  
      $show = "<table width=\"400\" border=0 cellspacing=1 cellpadding=3 class=\"hcmsMessage\">
      <tr>
       <td><span class=hcmsHeadline>".$hcms_lang['the-selected-media-file-could-not-be-removed'][$lang]."</span></td>
      </tr>
      </table>
      </td>
    </tr>
  </table>\n";
    }    
  }
  else
  {
    $add_onload = "";

    $show = "<table width=\"400\" border=0 cellspacing=1 cellpadding=3 class=\"hcmsMessage\">
    <tr>
     <td><span class=hcmsHeadline>".$hcms_lang['the-selected-media-file-was-removed'][$lang]." '".$mediafile."' ".$hcms_lang['the-selected-media-file-could-not-be-removed'][$lang]."</span></td>
    </tr>
    </table>
    </td>
  </tr>
</table>\n";
  }
  
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  
  return $result;    
}         

// ====================================== OBJECT FUNCTIONS ==========================================

// ---------------------------------------- createfolder --------------------------------------------
// function: createfolder()
// input: site, location, folder, user
// output: array

// description:
// This function creates a new folder

function createfolder ($site, $location, $foldernew, $user)
{
  global $eventsystem, $mgmt_config, $cat, $hcms_lang, $lang;
  
  if (!is_int ($mgmt_config['max_digits_filename'])) $mgmt_config['max_digits_filename'] = 200;
  
  $add_onload = "";
  $show = "";
  $foldernew_orig = "";
  $contentfile = "";
  $container_id = "";
  $page_box_xml = "";
  
  // set default language
  if ($lang == "") $lang = "en";
  
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($foldernew) && accessgeneral ($site, $location, "") && strlen ($foldernew) <= $mgmt_config['max_digits_filename'] && valid_objectname ($user))
  {
    // publication management config
    if (!is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    
    // deconvertpath location
    $location = deconvertpath ($location, "file");
          
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";
    
    // trim folder name
    $foldernew = trim ($foldernew);
    
    // eventsystem
    if ($eventsystem['oncreatefolder_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
      oncreatefolder_pre ($site, $cat, $location, $foldernew, $user);   
    
    // buffer new folder name
    $foldernew_orig = $foldernew; 
    
    // test if folder name includes special characters
    if (specialchr ($foldernew, ".-_") == true)
    {
      $foldernew = specialchr_encode ($foldernew, "no");
    }
    
    // check if folder exists already
    if (@is_dir ($location.$foldernew))
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-folder-exists-already'][$lang]."</span><br />\n".$hcms_lang['please-try-another-folder-name'][$lang]."\n";
    }
    // check if folder can be created
    elseif (@is_dir ($location))
    {
      // create new folder
      $test = @mkdir ($location.$foldernew, $mgmt_config['fspermission']);
      
      if ($test != false)
      {    
        // remote client
        remoteclient ("save", "abs_path_".$cat, $site, $location, "", $foldernew, ""); 
              
        // create folder object
        $folderfile = createobject ($site, $location.$foldernew, ".folder", "default.meta.tpl", $user);       
        
        if ($folderfile['result'] != false)
        {
          $add_onload = "parent.frames['mainFrame'].location.reload(); ";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-folder-was-created'][$lang]."</span><br />\n";
          
          // add origin folder name as file parameter
          $filedata = loadfile ($location.$foldernew, ".folder");
          
          if ($filedata != "") $filedata = setfilename ($filedata, "name", $foldernew_orig);
          if ($filedata != "") $savefile = savefile ($location.$foldernew, ".folder", $filedata);
          else $savefile = false;
          
          if ($savefile == false)
          {
            $errcode = "10262";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|folder name '".$foldernew_orig."' could not be saved for ".$location.$foldernew;      
          }          
          
          $container = $folderfile['container'];
          $site = $folderfile['publication'];
          $location = $folderfile['location'];
          $cat = $folderfile['cat'];
          $contentfile = $folderfile['container'];
          $container_id = $folderfile['container_id'];
          $page_box_xml = $folderfile['container_content'];
          
          $error_switch = "no";
        }
      }
      // directory could not be created due to missing write permission
      else
      {
        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-folder-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";  
      }
    }
    // location is not a valid directory
    else
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-folder-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
    }
    
    // eventsystem
    if ($eventsystem['oncreatefolder_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0) && $error_switch == "no") 
      oncreatefolder_post ($site, $cat, $location, $foldernew, $user);
  }
  else
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-folder-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['please-try-another-folder-name'][$lang]."\n";
  } 
  
  // save log
  savelog (@$error);      

  // return results
  $result = array();
  
  if (isset ($error_switch) && $error_switch == "no") $result['result'] = true;
  else $result['result'] = false;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  $result['folder'] = $foldernew;
  $result['name'] = $foldernew_orig;
  $result['publication'] = $site;
  $result['location'] = $location;
  $result['cat'] = $cat;
  $result['container'] = $contentfile;
  $result['container_id'] = $container_id;
  $result['container_content'] = $page_box_xml;
  
  return $result;  
}

// ---------------------------------------- createfolders --------------------------------------------
// function: createfolders()
// input: site, location, folder, user
// output: array

// description:
// This function creates all folders recursively

function createfolders ($site, $location, $foldernew, $user)
{
  global $eventsystem, $mgmt_config, $cat, $hcms_lang, $lang;

  if (!is_int ($mgmt_config['max_digits_filename'])) $mgmt_config['max_digits_filename'] = 200;
  
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($foldernew) && accessgeneral ($site, $location, $cat) && strlen ($foldernew) <= $mgmt_config['max_digits_filename'] && valid_objectname ($user))
  {        
        
    // publication management config
    if (!is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");     
    
    // deconvertpath location
    $location = deconvertpath ($location, "file");
          
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";    
    
    // folder exists
    if (is_dir ($location.$foldernew)) return $result['result'] = true;
    
    // folder can be created
    $result = createfolder ($site, $location, $foldernew, $user);
    if ($result['result'] == true) return $result;
    
    // folder cannot be created, create parent folder
    $result = createfolders ($site, dirname ($location), getobject ($location), $user);
    
    if ($result['result'] == true) $result = createfolder ($site, $location, $foldernew, $user);
    if ($result['result'] == true) return $result;
  }
  else return $result['result'] = false;
}

// ---------------------------------------- collectfolders --------------------------------------------
// function: collectfolders ()
// input: site, location, folder name
// output: result array / false

// description:
// Help function to create the collection of folders

function collectfolders ($site, $location, $folder)
{
  global $user, $mgmt_config, $hcms_lang, $lang;
       
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($folder) && is_dir ($location.$folder))  
  {   
    // set folder     
    $folder_array[] = $site."|".$location."|".$folder;
  
    // find and create subfolders
    $dir = opendir ($location.$folder);
    
    if ($dir != false)
    {
      while ($subfolder = readdir ($dir))
      {
        if ($subfolder != "" && $subfolder != "." && $subfolder != ".." && is_dir ($location.$folder."/".$subfolder))
        {          
          $folder_array_new = collectfolders ($site, $location.$folder."/", $subfolder);
          
          if ($folder_array_new == false) 
          {
            $folder_array = false;
            break;
          }
          else $folder_array = array_merge ($folder_array, $folder_array_new);
        }
      }
      
      closedir ($dir);
    }
  }
  else $folder_array = false;
  
  return $folder_array;
}

// ---------------------------------------- copyfolders --------------------------------------------
// function: copyfolders ()
// input: site, location (source), new location (destination), folder
// output: result array equal to createfolder

// description:
// This function copies/creates all folders of the source location using mkdir (only directories will be created!). used for pasteobject function.

function copyfolders ($site, $location, $locationnew, $folder, $user)
{   
  global $mgmt_config, $cat, $hcms_lang, $lang;

  if (valid_publicationname ($site) && valid_locationname ($location) && $locationnew != "" && $folder != "")
  {  
    // add slash if not present at the end of the location string
    if (substr ($location, strlen ($location)-1, 1) != "/") $location = $location."/";  
    if (substr ($locationnew, -1) != "/") $locationnew = $locationnew."/";  
         
    if (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1)
      $location = deconvertpath ($location, "file");  
      
    if (substr_count ($locationnew, "%page%") == 1 || substr_count ($locationnew, "%comp%") == 1)
      $locationnew = deconvertpath ($locationnew, "file");   
      
    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location);              
       
    // collect folders to copy
    $folder_array = collectfolders ($site, $location, $folder);
    
    if ($folder_array != false && is_array ($folder_array))
    {
      $i = 0;
       
      foreach ($folder_array as $folder)
      {
        list ($thissite, $thislocation, $thisfolder) = explode ("|", $folder);
  
        if (is_dir ($thislocation))
        {
          // for the root folder do
          if ($i == 0)
          {
            // define rootlocation
            $rootlocationold = $location;
            $rootlocationnew = $locationnew;
            
            // rename root folder to "copy of" if it exsits already
            if (is_dir ($rootlocationnew.$thisfolder))
            {
              $rootfolderold = $thisfolder;
              $rootfoldernew = $thisfolder."-Copy";
    
              $c = 2;
                
              while ($c < 50)
              {           
                if (is_dir ($rootlocationnew.$rootfoldernew))
                {
                  $rootfoldernew = $thisfolder."-Copy".$c;
                  $c++;
                }
                else break;
              }         
            }   
            else $rootfolderold = $rootfoldernew = $thisfolder;  

            $result['result'] = @mkdir ($rootlocationnew.$rootfoldernew, $mgmt_config['fspermission']);
            
            // remote client
            remoteclient ("save", "abs_path_".$cat, $site, $rootlocationnew, "", $rootfoldernew, "");             
          }
          // create all subfolders inside the root folder
          else 
          {
            // define new location
            $thislocation = str_replace ($rootlocationold.$rootfolderold, $rootlocationnew.$rootfoldernew, $thislocation);

            $result['result'] = @mkdir ($thislocation.$thisfolder, $mgmt_config['fspermission']);
            
            // remote client
            remoteclient ("save", "abs_path_".$cat, $site, $thislocation, "", $thisfolder, "");             
          }
        }
        else $result['result'] = false;      
        
        if ($result['result'] == false) break;
        $i++;
      }
    }
    else $result['result'] = false;
    
    $result['rootfolderold'] = $rootfolderold;
    $result['rootfoldernew'] = $rootfoldernew;
    $result['rootlocationold'] = $location;
    $result['rootlocationnew'] = $locationnew;    
    
    // return result
    return $result;
  }
  else $result['result'] = false; 
}

// ---------------------------------------- deletefolder --------------------------------------------
// function: deletefolder()
// input: site, location, folder
// output: array

// description:
// This function removes a folder

function deletefolder ($site, $location, $folder, $user)
{
  global $eventsystem, $mgmt_config, $cat, $hcms_lang, $lang;
         
  $add_onload = "";
  $show = "";
  
  // set default language
  if ($lang == "") $lang = "en";

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($folder) && accessgeneral ($site, $location, $cat) && $user != "")
  {  
    
    // publication management config
    if (!is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php"); 
    
    // deconvertpath location
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, $cat);
        
    $show = "";
    
    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location);
    
    // check given folder name
    if ($folder == ".folder")
    {
      $folder = getobject ($location);
      $location = getlocation ($location);
    }   
    
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";
    
    // eventsystem
    if ($eventsystem['ondeletefolder_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
      ondeletefolder_pre ($site, $cat, $location, $folder, $user);
      
      
    // check folder for objects
    $is_empty = true;
    $fh = opendir ($location.$folder);
    
    if ($fh)
    {
      while ($file = readdir ($fh))
      {
        if ($file != "." && $file != ".." && $file != ".folder")
        {
          $is_empty = false;
          break;
        }
      }
    }
    
    // folder exists
    if ($is_empty && is_dir ($location.$folder) && is_file ($location.$folder."/.folder"))
    {
      // delete folder object
      $result_delete = deleteobject ($site, $location.$folder, ".folder", $user);
    
      // delete directory
      if ($result_delete['result']) $result_delete['result'] = deletefile ($location, $folder, 0);     
   
      if ($result_delete['result'] == true)
      {
        // remove folder from workflow and group folder access
        deletegroupfolder ($site, $cat, $location.$folder, $user);
        deleteworkflowfolder ($site, $cat, $location.$folder, $user);
        
        // remote client
        remoteclient ("delete", "abs_path_".$cat, $site, $location, "", $folder, "");
 
        $add_onload = "parent.frames['mainFrame'].location.reload(); ";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-folder-was-removed'][$lang]."</span><br />\n";
    
        $error_switch = "no";
      
        // log delete
        $errcode = "00110";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|$errcode|folder ".$location_esc.$folder." has been deleted by user '".$user."' (".getuserip().")";
      }
      else
      {
        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-folder-could-not-be-removed'][$lang]."</span><br />".$hcms_lang['the-folder-still-holds-items-please-delete-these-items'][$lang]."\n";
      }
    }
    // folder doesn't exist and/or write permission is missing
    else
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-folder-doesnt-exist'][$lang]."</span><br />".$hcms_lang['or-you-have-no-write-permission'][$lang]."\n";
    }
  
    // eventsystem
    if ($eventsystem['ondeletefolder_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0) && $error_switch == "no") 
      ondeletefolder_post ($site, $cat, $location, $folder, $user);
  }
  else $error_switch = "yes";
  
  // save log
  savelog (@$error);     
 
  // return results
  $result = array();
    
  if (isset ($error_switch) && $error_switch == "no") 
  {
    $folder = "";
    $result['result'] = true;
  }
  else $result['result'] = false;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  $result['folder'] = $folder;  
  
  return $result;    
}

// ---------------------------------------- renamefolder --------------------------------------------
// function: renamefolder()
// input: site, location, folder, new folder name, user
// output: array

// description:
// This function renames a folder

function renamefolder ($site, $location, $folder, $foldernew, $user)
{
  global $eventsystem, $mgmt_config, $cat, $hcms_lang, $lang;
  
  if (!is_int ($mgmt_config['max_digits_filename'])) $mgmt_config['max_digits_filename'] = 200;
  
  $add_onload = "";
  $show = "";
  $foldernew_orig = "";
  
  // set default language
  if ($lang == "") $lang = "en";
  
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($folder) && valid_objectname ($foldernew) && strlen ($foldernew) <= $mgmt_config['max_digits_filename'] && valid_objectname ($user))
  {
    
    // publication management config
    if (!is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php"); 
  
    $show = "";
    
    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location);   
    
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";  
    
    // deconvertpath location
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, $cat);
    
    // trim folder name
    $foldernew = trim ($foldernew);
    
    // keep new folder name
    $foldernew_orig = $foldernew;  
   
    // test if folder name includes special characters
    if (specialchr ($folder, ".-_~") == true) $folder = specialchr_encode ($folder, "no");
    if (specialchr ($foldernew, ".-_") == true) $foldernew = specialchr_encode ($foldernew, "no");

    // folder doesn't exist
    if (!is_dir ($location.$folder))
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-folder-does-not-exist'][$lang]."</span><br />\n";
    }
    // folder with the new name exists already
    elseif (is_dir ($location.$foldernew))
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['a-folder-with-the-same-name-exists-already'][$lang]."</span><br />\n".$hcms_lang['please-try-another-expression'][$lang]."\n";
    }    
    // folder is not writeable
    elseif (@rename ($location.$folder, $location.$foldernew) == false)
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['you-do-not-have-write-permissions-for-the-folder'][$lang]."</span><br />\n";  
    }
    else
    {
      // eventsystem
      if ($eventsystem['onrenamefolder_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
        onrenamefolder_pre ($site, $cat, $location, $folder, $foldernew, $user);    
            
      // if inheritance of components is used, every child publication must also be updated
      $site_array[0] = $site;
      
      if (strtolower ($cat) == "comp")
      {
        // load publication inheritance setting
        if (is_file ($mgmt_config['abs_path_data']."config/inheritance.dat"))
        {
          $inherit_db = inherit_db_read ();

          if (sizeof ($inherit_db) >= 1)
          {
            $child_array = inherit_db_getchild ($inherit_db, $site);

            if ($child_array != false)
            {
              $site_array = array_merge ($site_array, $child_array);
            }
          }        
        }
      }
      
      // loop for each site
      foreach ($site_array as $site)
      {
        // include configuration
        if ((!isset ($mgmt_config[$site]) || !is_array ($mgmt_config[$site])) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
        {
          include_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");  
        }
        
        // convert folder locations
        $folder_curr = convertpath ($site, $location.$folder."/", $cat);
        $folder_new = convertpath ($site, $location.$foldernew."/", $cat);        
      
        // ---------------------------- update links ------------------------------
        // lock and load link management database
        $link_db = link_db_load ($site, $user);
      
        if (is_array ($link_db) && sizeof ($link_db) > 0)
        {      
          // update links in content files
          foreach ($link_db as $link_db_record)
          {
            $count_obj = @substr_count ($link_db_record['object'], $folder_curr); 
            $count_lnk = @substr_count ($link_db_record['link'], $folder_curr); 

            if ($count_obj > 0 || $count_lnk > 0)
            {
              // get content container name
              $container = $link_db_record['container'];
      
              // update link in content container and link file
              $test = link_update ($site, $container, $folder_curr, $folder_new);
      
              if ($test == false)
              {
                $add_onload = "";
                $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-write-data-into-content-container-or-link-index'][$lang]."</span><br />\n".$hcms_lang['content-container-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
                
                // log error
                $errcode = "20815";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|$errcode|folder ".$folder_curr." could not be renamed to ".$folder_new;                
                break;          
              }
              else
              {
                // update link in link management       
                $link_db = link_db_update ($site, $link_db, "link", $container, $cat, $folder_curr, $folder_new, "all");         
      
                // update actual record in link management
                $link_db = link_db_update ($site, $link_db, "object", $container, $cat, $folder_curr, $folder_new, "all");                      
              }      
            }
          }
      
          // on error in link_db 
          if ($link_db == false)
          {
            // unlock file
            link_db_close ($site, $user);          
          
            $add_onload = "";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['link-management-error'][$lang]."</span><br />\n".$hcms_lang['an-error-occured-while-writing-data-to-the-database'][$lang]."\n";
          }
        }
        elseif ($link_db == false)
        {
          // unlock file
          link_db_close ($site, $user);     
        
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-load-link-management-database'][$lang]."</span><br />\n".$hcms_lang['link-management-database-is-missing-or-you-do-not-have-read-permissions'][$lang]."\n";
        }
      
        if ($show == "")
        {
          // ------------------------------------ folderaccess for groups --------------------------------------  
          // rename folder in user folderaccess
          $test = renamegroupfolder ($site, $cat, $folder_curr, $folder_new, $user);

          if ($test == false)
          {           
            $add_onload = "";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-folder-could-not-be-renamed'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions-on-user-groups-settings'][$lang]."\n";            
          }
          
          // ----------------------------------------- workflow folders --------------------------------------  
          // rename folder in user folderaccess
          $test = renameworkflowfolder ($site, $cat, $folder_curr, $folder_new, $user);
        
          if ($test == false)
          {
            $add_onload = "";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-folder-could-not-be-renamed'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions-on-workflow-folder-settings'][$lang]."\n";               
          } 
        }
        
        // ------------------------------------- save files ----------------------------------------------- 
        if ($show == "" && $link_db != false)
        {      
          if ($test != false) 
          {
            $test = link_db_save ($site, $link_db, $user);
      
            // if an error occured while loading or saving the link management file
            if ($test == false)
            {
              // unlock file
              link_db_close ($site, $user);   
      
              $add_onload = "";
              $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-save-link-management-database'][$lang]."</span><br />\n".$hcms_lang['link-management-database-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
            }
          } 
        }  
    
        // if successful
        if ($show == "")
        {
          // save new folder name incl. special characters as file paramater
          $filedata = loadfile ($location.$foldernew, ".folder");
          if ($filedata != "") $filedata = setfilename ($filedata, "name", $foldernew_orig);
          if ($filedata != "") $result = savefile ($location.$foldernew, ".folder", $filedata);
          else $result = false;
          
          if ($result == false)
          {
            $errcode = "10265";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|folder name '".$foldernew_orig."' could not be saved for ".$location_esc.$foldernew;      
          }
              
          // remote client
          remoteclient ("rename", "abs_path_".$cat, $site, $location, "", $folder, $foldernew); 
          
          // eventsystem
          if ($eventsystem['onrenamefolder_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
            onrenamefolder_post ($site, $cat, $location, $folder, $foldernew, $user);      
            
          // relational DB connectivity
          if ($mgmt_config['db_connect_rdbms'] != "")
          {
            rdbms_renameobject (convertpath ($site, $location.$folder, $cat), convertpath ($site, $location.$foldernew, $cat));                 
          }      

          $folderold = $folder;
          $folder = $foldernew;
      
          $add_onload = "parent.frames['mainFrame'].location.reload(); ";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-folder-was-renamed'][$lang]."</span><br />\n";
          
          $error_switch = "no";
        }
        // on error in link_db, usergroup or workflow folders
        else
        {
          // unlock file
          link_db_close ($site, $user);    
          
          // rollback rename folder in file system
          @rename ($location.$foldernew, $location.$folder);   
          
          break;    
        }   
      }   
    }
  }
  else $error_switch = "yes";  
  
  // save log
  savelog (@$error);      
  
  // return results
  $result = array();
  
  if (isset ($error_switch) && $error_switch == "no") $result['result'] = true;
  else $result['result'] = false;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  $result['folder'] = $folder;  
  $result['name'] = $foldernew_orig;  
  
  return $result;    
}

// ---------------------------------------- createobject --------------------------------------------
// function: createobject()
// input: site, location, object, template
// output: result array

// description:
// This function creates a new page or component

function createobject ($site, $location, $page, $template, $user)
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  if (!is_int ($mgmt_config['max_digits_filename'])) $mgmt_config['max_digits_filename'] = 200;
  
  $show = "";
  $add_onload = "";
  $cat = "";
  $page_orig = "";
  $filetype = "";
  $mediafile = "";
  $contentfile = "";
  $container_id = "";
  $page_box_xml = "";
  
  // set default language
  if ($lang == "") $lang = "en";
  
  // include hypermailer class
  if (!class_exists ("HyperMailer")) include_once ($mgmt_config['abs_path_cms']."function/hypermailer.class.php");  

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && accessgeneral ($site, $location, "") && strlen ($page) <= $mgmt_config['max_digits_filename'] && valid_objectname ($template) && valid_objectname ($user))
  {
    // publication management config
    if (!is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";  
    
    // convert location
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, $cat); 
    
    // trim object name
    $page = trim ($page);
    
    //  check if location exists
    if (!is_dir ($location))
    {
      $add_onload = "parent.frames['objFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-create-new-item'][$lang]."</span><br />\n".$hcms_lang['the-location-holding-the-new-object-does-not-exist'][$lang]."\n";
    }      
    
    if ($show == "")
    {
      // check if page name includes special characters
      if (specialchr ($page, ".-_") == true)
      {
        $page_orig = $page;
        $page = specialchr_encode ($page, "no");  
      }
      // check if page is a folder
      elseif ($page == ".folder")
      {
        // extract folder name
        $page_orig = getobject ($location);
      }
      else $page_orig = $page;
          
      // extract template file name from sent template information
      if (@substr_count ($template, ".php") >= 1) 
      {
        $templatefile = getattribute ($template, "template");
        $catpos1 = strpos ($templatefile, ".") + 1;
        $catpos2 = strpos ($templatefile, ".tpl");
        $template_cat = substr ($templatefile, $catpos1, $catpos2 - $catpos1);
        $cat = getcategory ($site, $location);
        
        // if multimedia file
        if ($template_cat == "meta" && $page != ".folder") $mediatype = true;
        else $mediatype = false;  
      }
      elseif (@substr_count ($template, ".tpl") >= 1) 
      {
        $templatefile = $template;      
        $catpos1 = strpos ($templatefile, ".") + 1;
        $catpos2 = strpos ($templatefile, ".tpl");
        $template_cat = substr ($templatefile, $catpos1, $catpos2 - $catpos1);   
        $cat = getcategory ($site, $location);
        
        // if multimedia file
        if ($template_cat == "meta" && $page != ".folder") $mediatype = true;
        else $mediatype = false;         
      }
      else 
      {
        $cat = getcategory ($site, $location);
        $templatefile = $template.".".$cat.".tpl";
      }
     
      // eventsystem
      if ($eventsystem['oncreateobject_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
        oncreateobject_pre ($site, $cat, $location, $page, $template, $user);  
      
      // define variables depending on content category
      if ($cat == "page")
      {
        $dir_name = "dir";
      }
      elseif ($cat == "comp")
      {
        $dir_name = "comp_dir";
      }       
    
      // ------------------------------- read template file information -------------------------------- 
      // load template file
      $result = loadtemplate ($site, $templatefile);
    
      if ($result['result'] == true)
      {
        $templatestore = $result['content'];
      
        // get file extension from template
        $bufferarray = getcontent ($templatestore, "<extension>");
        
        // for all pages and components
        if ($mediatype == false && $page != ".folder")
        {
          $file_ext = $bufferarray[0];      
          // add extension to page name
          $pagefile = $page.".".$file_ext.".off";
          $pagename = $page.".".$file_ext;
          // original file name
          $page_orig = $page_orig.".".$file_ext;              
        }
        // for all multimedia assets and folders
        elseif ($mediatype == true || $page == ".folder")
        {
          // get the file extension of the file
          $file_ext = strtolower (strrchr ($page, "."));
          $pagefile = $page;
          $pagename = $page;
          // original file name
          // $page_orig is defined already         
        }

        // check if page already exists
        if (!is_file ($location.$pagefile) && !is_file ($location.$pagename))
        {
          // ----------------------------- build content file (xml structure)----------------------------
          $contentstore = "";
    
          // --------------------------------- hyperCMS content ------------------------------------
          // create the content file name:
          
          // load content count file and add the new page
          $filedata = loadlockfile ($user, $mgmt_config['abs_path_data'], "contentcount.dat", 5);
    
          if ($filedata != "")
          {
            $contentcount = trim ($filedata);
            // add 1 to content count
            $contentcount++;
            // write
            $test = savelockfile ($user, $mgmt_config['abs_path_data'], "contentcount.dat", $contentcount);
  
            if ($test == false)
            {
              unlockfile ($user, $mgmt_config['abs_path_data'], "contentcount.dat");
              
              $add_onload = "parent.frames['objFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
              $show = "<span class=\"hcmsHeadline\">".$hcms_lang['severe-error-occured'][$lang]."</span><br />\n".$hcms_lang['contentcount-failure'][$lang]."\n";
            }  
          }
          else
          {
            // unlock file
            unlockfile ($user, $mgmt_config['abs_path_data'], "contentcount.dat");
       
            $add_onload = "parent.frames['objFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['severe-error-occured'][$lang]."</span><br />\n".$hcms_lang['contentcount-failure'][$lang]."\n";
          }
        }
        else
        {
            $add_onload = "parent.frames['objFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-exists-already'][$lang]."</span><br />\n".$hcms_lang['please-try-another-name'][$lang]."\n";
        }
        
        if ($show == "")
        {
          // create the name of the content file based on the unique content count value
          $contentcountlen = strlen ($contentcount);
          $zerolen = 7 - $contentcountlen;
          $zerostring =  "";
    
          for ($i = 1; $i <= $zerolen; $i++)
          {
            $zerostring = $zerostring."0";
          }
          
          // define content container id and name
          $container_id = $zerostring.$contentcount;
          $contentfile = $zerostring.$contentcount.".xml";
    
          // define page URL for contentorigin
          $contentorigin = convertpath ($site, $location.$pagename, "$cat");
    
          // define content-encoding for content container
          $result = getcharset ($site, $templatestore);

          $contenttype = $result['contenttype'];
          
          // character set for meta-data of multimedia assets must be UTF-8
          if ($mediatype == true) $charset = "UTF-8";
          else $charset = $result['charset'];

          // --------------------------- load page xml schema -----------------------
          // there is just one page xml schema including different xml sub schemas for text, media and link information          
          $page_box_xml = chop (loadfile ($mgmt_config['abs_path_cms']."xmlschema/", "object.schema.xml.php"));      
    
          if ($page_box_xml != false)
          {
            // write XML declaration parameter for text encoding
            if ($charset != "") $page_box_xml = setxmlparameter ($page_box_xml, "encoding", $charset);
      
            // write <hyperCMS> content in xml structure
            if ($page_box_xml != false) $page_box_xml = setcontent ($page_box_xml, "<hyperCMS>", "<contentcontainer>", $contentfile, "", "");
            if ($page_box_xml != false) $page_box_xml = setcontent ($page_box_xml, "<hyperCMS>", "<contentxmlschema>", "object/".$cat, "", "");
            if ($page_box_xml != false) $page_box_xml = setcontent ($page_box_xml, "<hyperCMS>", "<contentorigin>", $contentorigin, "", "");
            if ($page_box_xml != false) $page_box_xml = setcontent ($page_box_xml, "<hyperCMS>", "<contentobjects>", $contentorigin."|", "", "");
            if ($page_box_xml != false) $page_box_xml = setcontent ($page_box_xml, "<hyperCMS>", "<contentuser>", $user, "", "");
            if ($page_box_xml != false) $page_box_xml = setcontent ($page_box_xml, "<hyperCMS>", "<contentdate>", $mgmt_config['today'], "", "");
            if ($page_box_xml != false) $page_box_xml = setcontent ($page_box_xml, "<hyperCMS>", "<contentstatus>", "active", "", "");
         
            // ------------------------ set workflow --------------------------------
            // set master workflow is set in template and create workflow
            $workflow_array = gethypertag ($templatestore, "workflow", 0);
            
            if ($workflow_array != false && $workflow_array[0] != "")
            {
              $workflow_name = getattribute ($workflow_array[0], "name");
              $workflow_file = $site.".".$workflow_name.".xml";
              
              if ($workflow_name != "" && fopen ($mgmt_config['abs_path_data']."workflow_master/".$workflow_file, "r+"))
              { 
                // load workflow 
                $workflow = loadfile ($mgmt_config['abs_path_data']."workflow_master/", $workflow_file);
                
                // get user of start item
                $start_item_array = selectcontent ($workflow, "<item>", "<id>", "u.1");
                if ($start_item_array != false) $start_user_array = getcontent ($start_item_array[0], "<user>");                
                
                if ($start_user_array[0] == "") 
                {
                  // set start user in workflow if was not set already
                  $workflow = setcontent ($workflow, "<item>", "<user>", $user, "<id>", "u.1");
                 
                  // reset passed status and date in workflow
                  $workflow = setcontent ($workflow, "<item>", "<passed>", 0, "", "");   
                  $workflow = setcontent ($workflow, "<item>", "<date>", "-", "", "");           
                  
                  // save workflow
                  $workflow_save = savefile ($mgmt_config['abs_path_data']."workflow/".$site."/", $contentfile, $workflow);              
                }  
                // if start user is set but not the same logged in user
                elseif ($start_user_array[0] != false && $start_user_array[0] != $user)
                {
                  // user has no right to create the page due to workflow
                  $workflow_save = false;
                }
                else $workflow_save = false;
                          
                // set workflow in content container
                if ($workflow_save != false) $page_box_xml = setcontent ($page_box_xml, "<hyperCMS>", "<contentworkflow>", $workflow_name, "", "");              
              }
            }
            else $workflow_save = true;
          }
          else
          {
            $add_onload = "parent.frames['objFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['severe-error-occured'][$lang]."</span><br />\n".$hcms_lang['could-not-create-new-content-container'][$lang]."\n";
          }            
    
          // ------------------------ add record in link management file --------------------------------
    
          if ($page_box_xml != false && $workflow_save != false && $mgmt_config[$site]['linkengine'] == true)
          {
            // define new link database record
            $object = convertpath ($site, $location.$pagename, $cat)."|";
            
            $link_db_record = "\n".$contentfile.":|".$object.":|";

            // append new record into link management file
            $link_db_append = appendfile ($mgmt_config['abs_path_data']."link/", $site.".link.dat", $link_db_record);
            
            // user file could not be loaded (might be locked by a user)
            if ($link_db_append == false)
            {
              // get locked file info
              $result_locked = getlockedfileinfo ($mgmt_config['abs_path_data']."link/", $site.".link.dat");

              if (!empty ($result_locked['user']))
              {
                // unlock file
                $result_unlock = unlockfile ($result_locked['user'], $mgmt_config['abs_path_data']."link/", $site.".link.dat");
              }
              else
              {
                // send mail
                $mailer = new HyperMailer();
                $mailer->AddAddress("support@hypercms.net");
                $mailer->FromName = "hyperCMS link index failed on server: ".$_SERVER['SERVER_NAME'];
                $mailer->Body = "Link index ".$site.".link.dat is locked!\nhyperCMS Host: ".$_SERVER['SERVER_NAME']."\n";
                $mailer->Send();
                
                $result_unlock = false;
                $result['message'] = $hcms_lang['could-not-insert-into-link-management'][$lang];
                $auth = false;
                
                $errcode = "10885";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|link index file ".$site.".link.dat could not be unlocked";     
              }
        
              if ($result_unlock == true)
              {
                // insert new record into link management file
                $link_db_append = appendfile ($mgmt_config['abs_path_data']."link/", $site.".link.dat", $link_db_record);
              }
              else $link_db_append = false;
            }              
    
            if ($link_db_append == false)
            {
              $add_onload = "parent.frames['objFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
              $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-insert-into-link-management'][$lang]."</span><br />\n".$hcms_lang['link-management-file-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
            }
          }
          else $link_db_append = true;

          // save working xml content container and published container
          if ($link_db_append != false && $show == "")
          {   
            $container_location = getcontentlocation ($container_id, 'abs_path_content');
            
            // create container directory
            $test = @mkdir ($container_location, $mgmt_config['fspermission']);

            // save container initally since savecontainer only saves data to existing containers
            if ($test != false)
            {
              $test = savecontainer ($container_id, "work", $page_box_xml, $user, true);
            }
            
            if ($test == false)
            {
              $errcode = "10882";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|working container $contentfile.wrk could not be saved";                
            }
                    
            // save container initally since savecontainer only saves data to existing containers
            if ($test != false)
            {
              $test = savecontainer ($container_id, "published", $page_box_xml, $user, true);
            }
            
            if ($test == false)
            {
              $errcode = "10883";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|container $contentfile could not be saved";                
            } 

            if ($test != false)
            {
              // ------------------------------------ build page file ------------------------------------
              // insert template and content file name into page
              $sourcefiles = "<!-- hyperCMS:template file=\"".$templatefile."\" -->\n<!-- hyperCMS:content file=\"".$contentfile."\" -->\n<!-- hyperCMS:name file=\"".$page_orig."\" -->\n";
              
              if ($mediatype == true && $page != ".folder")
              {
                if (substr_count ($page, ".") > 0)
                {
                  $file_name = substr ($page, 0, strrpos ($page, "."));
                  $file_ext = strtolower (strrchr ($page, "."));
                  $mediafile = $file_name."_hcm".$container_id.$file_ext;   
                }
                else
                {
                  $file_name = $page;
                  $file_ext = "";
                  $mediafile = $file_name."_hcm".$container_id.$file_ext;  
                }
                             
                $sourcefiles .= "<!-- hyperCMS:media file=\"".$mediafile."\" -->\n";
              }
      
              clearstatcache ();             
      
              // save object file
              $savefile = savefile ($location, $pagefile, $sourcefiles);
              $filetype = "cms";
      
              // if object file could not be saved
              if ($savefile == false)
              {
                $add_onload = "parent.frames['objFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
                $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-create-new-item'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
              }
              // on success
              else
              {
                // relational DB connectivity
                if ($mgmt_config['db_connect_rdbms'] != "")
                {
                  rdbms_createobject ($container_id, $contentorigin, $templatefile, $contentfile, $user);             
                } 
              
                $page = $pagefile;
      
                $add_onload = "parent.frames['objFrame'].location='page_view.php?ctrlreload=yes&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&pagename=".url_encode($page_orig)."'; ";
                $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-created'][$lang]."</span><br />\n".$hcms_lang['now-you-can-edit-the-content'][$lang]."\n";
                
                // remote client
                remoteclient ("save", "abs_path_".$cat, $site, $location, "", $pagefile, "");                 
                
                // eventsystem
                if ($eventsystem['oncreateobject_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0) && $error_switch == "no") 
                  oncreateobject_post ($site, $cat, $location, $page, $template, $user);                  

                // notification
                notifyusers ($site, $location, $page, "oncreate", $user);

                $error_switch = "no";
              }
            }
            else
            {
              $add_onload = "parent.frames['objFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
              $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-create-new-content-container'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";            
            }
          }
          // if user has no access to the workflow or link management failed
          else
          {
            $add_onload = "parent.frames['objFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-create-new-item'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-workflow-access-permissions'][$lang]."\n";        
          } 
        }     
      }
      else
      {
        $add_onload = "parent.frames['objFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['you-selected-no-template'][$lang]."</span><br />\n".$hcms_lang['please-select-a-template'][$lang]."\n";
      }      
    }
  }
  else
  {
    $add_onload = "parent.frames['objFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['required-input-is-missing'][$lang]."</span><br />\n".$hcms_lang['please-fill-in-a-name'][$lang]."\n";
  }   
  
  // save log
  savelog (@$error);  
  
  // return results
  $result = array();
  if (isset ($error_switch) && $error_switch == "no") $result['result'] = true;
  else $result['result'] = false;
  $result['add_onload'] = @$add_onload;  
  $result['message'] = @$show;
  $result['publication'] = @$site;
  $result['location'] = @$location;
  $result['location_esc'] = @$location_esc;
  $result['cat'] = @$cat;
  $result['object'] = @$page;
  $result['name'] = @$page_orig;
  $result['objecttype'] = @$filetype;
  $result['mediafile'] = @$mediafile;
  $result['container'] = @$contentfile;
  $result['container_id'] = @$container_id;
  $result['container_content'] = @$page_box_xml;
  
  return $result;
}

// ---------------------------------------- uploadfile --------------------------------------------
// function: uploadfile()
// input: publication name, destination location, category [page/comp], uploaded file (array as defined by PHP autoglobale $_FILES), unzip [1/0], object name (only for media file update of existing object), 
//        create only a new thumbnail [1/0], imageresize [percentage, null], imagepercentage (%-value as integer), user name, check for duplicates [true,false], versioning of file [true,false]
// output: result array
// requires: config.inc.php, $pageaccess, $compaccess, $hiddenfolder, $localpermission
 
// description:
// This function manages all file uploads, like unzip files, create media objects and resize images.
// The container name will be extracted from the media file name for updating an existing multimedia file.

function uploadfile ($site, $location, $cat, $global_files, $page="", $unzip=0, $createthumbnail=0, $imageresize="", $imagepercentage="", $user="sys", $checkduplicates=true, $versioning=false)
{
  global $mgmt_config, $mgmt_uncompress, $mgmt_imagepreview, $mgmt_mediapreview, $mgmt_mediaoptions, $mgmt_imageoptions, $mgmt_maxsizepreview, $mgmt_parser, $eventsystem,
         $pageaccess, $compaccess, $hiddenfolder, $localpermission, $hcms_lang, $lang;

  if (session_id() != "") $session_id = session_id();
  else $session_id = createuniquetoken ();
  
  $show = "";
  $show_command = "";
  $result = array();
  $result['result'] = false;

  // set default language
  if ($lang == "") $lang = "en";

  if (valid_publicationname ($site) && valid_locationname ($location) && $cat != "" && accessgeneral ($site, $location, $cat) && is_array ($global_files) && valid_objectname ($user))
  {
    // publication management config
    if (!is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php"); 
    
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";
    
    // deconvert location
    $location = deconvertpath ($location, "file");
    
    // convert path
    $location_esc = $result['location_esc'] = convertpath ($site, $location, $cat);
        
    // set local permissions
    $ownergroup = accesspermission ($site, $location, $cat);
    
    if ($ownergroup != false) $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);
    else $setlocalpermission = false;

    if ($setlocalpermission['root'] != 1 || $setlocalpermission['upload'] != 1)
    {
      $errcode = 30501;
      $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile() -> no permissions to upload file to ".$location." for user '".$user."'";
        
      // write log
      savelog (@$error);
      
      $result['header'] = "HTTP/1.1 500 Internal Server Error";
      $result['message'] = $hcms_lang['you-dont-have-permissions-to-use-this-function'][$lang];
      return $result;
    }
    
    // get media file name if page has been provided and media file will be updated
    if (valid_objectname ($page) && is_file ($location.$page))
    {
      $object_info = getobjectinfo ($site, $location, $page, $user);
      
      if (!empty ($object_info['media'])) $media_update = $object_info['media'];
      else $media_update = "";
    }
    else $media_update = "";

    // define variables
    $updir = $location; //absolute path to where files are uploaded, no trailing slash
    $size = $mgmt_config['maxfilesize'] * 1024 * 1024; // size limit in bytes
    
    // check if global_files contains an URL or FTP-link as source and download file temporarily
    $is_remote_file = false;
    
    // define path of temp file
    $temp_file = $mgmt_config['abs_path_temp'].uniqid();

    // temporary PROXY file
    if (substr ($global_files['Filedata']['tmp_name'], 0, 6) == "proxy_")
    {
      $temp_file = $mgmt_config['abs_path_temp'].$global_files['Filedata']['tmp_name'];
      
      if (is_file ($temp_file))
      {
        $global_files['Filedata']['error'] = UPLOAD_ERR_OK;
        $global_files['Filedata']['tmp_name'] = $temp_file;
        $global_files['Filedata']['type'] = mime_content_type ($temp_file);
        $global_files['Filedata']['size'] = filesize ($temp_file);
        $is_remote_file = true;
      }
      else
      {
        $errcode = 20502;
        $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile() -> the file could not be obtained from the source";
          
        // write log
        savelog (@$error);
        
        $result['header'] = "HTTP/1.1 501 Internal Server Error";
        $result['message'] = $hcms_lang['file-could-not-be-downloaded'][$lang];
        
        return $result;
      }
    }
    // remote HTTP file
    elseif (substr ($global_files['Filedata']['tmp_name'], 0, 4) == "http")
    {
      // get remote file
      $filedata = file_get_contents ($global_files['Filedata']['tmp_name']);
      
      if ($filedata && file_put_contents ($temp_file, $filedata) && is_file ($temp_file))
      {
        $global_files['Filedata']['error'] = UPLOAD_ERR_OK;
        $global_files['Filedata']['tmp_name'] = $temp_file;
        $global_files['Filedata']['type'] = mime_content_type ($temp_file);
        $global_files['Filedata']['size'] = filesize ($temp_file);
        $is_remote_file = true;
      }
      else
      {
        $errcode = 20502;
        $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile() -> the file could not be obtained from the source";
          
        // write log
        savelog (@$error);
        
        $result['header'] = "HTTP/1.1 501 Internal Server Error";
        $result['message'] = $hcms_lang['file-could-not-be-downloaded'][$lang];
        
        return $result;
      }
    }
    // remote FTP file
    elseif (substr ($global_files['Filedata']['tmp_name'], 0, 3) == "ftp")
    {
      $filedata = false;
      
      // get FTP server name
      $ftp_info = parse_url ($global_files['Filedata']['tmp_name']);
      $ftp_server = $ftp_info['host'];

      // check for existing FTP connection data
      if ($ftp_server != "")
      {
        $ftp_array = getsession ($ftp_server);
        
        // set FTP logon data
        $sentserver = $ftp_server;
        if (!empty ($ftp_array['ftp_user'])) $sentuser = $ftp_array['ftp_user'];
        if (!empty ($ftp_array['ftp_password'])) $sentpasswd = $ftp_array['ftp_password'];
        if (!empty ($ftp_array['ftp_ssl'])) $ssl = $ftp_array['ftp_ssl'];
        
        if (!empty ($sentserver) && !empty ($sentuser) && !empty ($sentpasswd))
        {
          if (!empty ($ssl)) $ssl = true;
          else $ssl = false;
          
          $conn_id = ftp_userlogon ($sentserver, $sentuser, $sentpasswd, $ssl);
          
          $remote_file = cleandomain ($global_files['Filedata']['tmp_name']);

          // get remote file
          $ftp_getfile = ftp_getfile ($conn_id, $remote_file, $temp_file);
        }
      }
      
      if ($ftp_getfile && is_file ($temp_file))
      {
        $global_files['Filedata']['error'] = UPLOAD_ERR_OK;
        $global_files['Filedata']['tmp_name'] = $temp_file;
        $global_files['Filedata']['type'] = mime_content_type ($temp_file);
        $global_files['Filedata']['size'] = filesize ($temp_file);
        $is_remote_file = true;
      }
      else
      {
        $errcode = 10503;
        $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile() -> the file could not be obtained from the source";
          
        // write log
        savelog (@$error);
        
        $result['header'] = "HTTP/1.1 501 Internal Server Error";
        $result['message'] = $hcms_lang['file-could-not-be-downloaded'][$lang];
        
        return $result;
      }
    }
    
    // eventsystem
    if ($eventsystem['onfileupload_pre'] == 1) onfileupload_pre ($site, $cat, $location, $global_files['Filedata']['name'], $user);
  
    // error during file upload
    if ($global_files['Filedata']['error'] != UPLOAD_ERR_OK)
    {
      $errcode = 20504;
      $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile() -> the file '".$global_files['Filedata']['name']."' could not be saved or only partialy-saved";
        
      // write log
      savelog (@$error);
        
      $result['header'] = "HTTP/1.1 501 Internal Server Error";
      $result['message'] = $hcms_lang['file-could-not-be-saved-or-only-partialy-saved'][$lang];
      return $result;
    }
  
    // error if no file is selected
    if (empty ($global_files['Filedata']['name']) || empty ($global_files['Filedata']['tmp_name']))
    {
      $errcode = 20505;
      $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile() -> no file selected for upload";
        
      // write log
      savelog (@$error);
      
      $result['header'] = "HTTP/1.1 502 Internal Server Error";
      $result['message'] = $hcms_lang['no-file-selected-to-upload'][$lang];
      return $result;
    }
    
    // error if file name is too long
    if (!is_int ($mgmt_config['max_digits_filename'])) $mgmt_config['max_digits_filename'] = 200;
    
    if (strlen ($global_files['Filedata']['name']) > $mgmt_config['max_digits_filename'])
    {
      $errcode = 20506;
      $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile() -> the file name '".$global_files['Filedata']['name']."' has too many digits";
        
      // write log
      savelog (@$error);
      
      $result['header'] = "HTTP/1.1 503 Internal Server Error";
      $result['message'] = str_replace ("%maxdigits%", $mgmt_config['max_digits_filename'], $hcms_lang['the-file-name-has-more-than-maxdigits-digits'][$lang]);
      return $result;
    }    
    
    // check if page name includes special characters
    if (specialchr ($global_files['Filedata']['name'], ".-_") == true)
    {
      $file_renamed = specialchr_encode ($global_files['Filedata']['name'], "no");
    }
    else
    {
      $file_renamed = $global_files['Filedata']['name'];
    }

    // error if file exists
    if ($media_update == "" && is_file ($location.$file_renamed))
    {      
      $result['header'] = "HTTP/1.1 504 Internal Server Error";
      $result['message'] = $hcms_lang['the-file-you-are-trying-to-upload-already-exists'][$lang];
      return $result;
    }
  
    // error if file is to big
    if ($mgmt_config['maxfilesize'] > 0)
    {
      if (filesize ($global_files['Filedata']['tmp_name']) > $size)
      {
        $errcode = 20508;
        $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile() -> the file '".$global_files['Filedata']['name']."' is too big (max. ".$mgmt_config['maxfilesize'].")";
          
        // write log
        savelog (@$error);
      
        $result['header'] = "HTTP/1.1 505 Internal Server Error";
        $result['message'] = $hcms_lang['the-file-you-are-trying-to-upload-is-too-big'][$lang];
        return $result;
      }
    }
  
    // error if file isn't certain type
    if ($mgmt_config['exclude_files'] != "")
    {
      // MIME-Type based: if ($global_files['Filedata']['type'] != $type)
      if (substr_count ($mgmt_config['exclude_files'], substr($global_files['Filedata']['name'], strrpos($global_files['Filedata']['name'], '.'))) > 0)
      {
        $result['header'] = "HTTP/1.1 506 Internal Server Error";
        $result['message'] = $hcms_lang['the-file-you-are-trying-to-upload-is-of-wrong-type'][$lang];
        return $result;
      }
    }

    // Check the md5 Hash with the one in the database
    if (!empty ($checkduplicates))
    {
      $md5_hash = md5_file ($global_files['Filedata']['tmp_name']);
      $duplicates = rdbms_getduplicate_file ($site, $md5_hash);
      $links = array();
      
      if ($duplicates != false)
      {
        foreach ($duplicates as $duplicate)
        {
          if ($media_update == "" || ($media_update != "" && $duplicate['objectpath'] != convertpath ($site, $location, $cat).$page))
          {
            $dup_location = getlocation ($duplicate['objectpath']);
            $dup_object = getobject ($duplicate['objectpath']);
            $dup_name = specialchr_decode ($dup_object);
            $links[] = '<a href="#" onclick="hcms_openWindow(\''.$mgmt_config['url_path_cms'].'frameset_content.php?site='.$site.'&ctrlreload=yes&cat=comp&location='.urlencode($dup_location).'&page='.urlencode($dup_object).'\',\''.uniqid().'\',\'status=yes,scrollbars=no,resizable=yes\',\'800\',\'600\');">'.$dup_name.'</a>';
          }
        }
        
        if (sizeof ($links) > 0)
        {
          $result['header'] = "HTTP/1.1 510 Internal Server Error";
          $result['message'] = str_replace ('%files%', implode(", ", $links), $hcms_lang['there-are-files-with-the-same-content-files'][$lang]);
  
          return $result;
        }
      }
    }
    
    // check if file can be uncompressed
    if ($unzip == 1 && is_array ($mgmt_uncompress))
    {
      $check_unzip = false;

      // extension of uploaded file
      $file_ext = strtolower (strrchr ($global_files['Filedata']['name'], "."));

      reset ($mgmt_uncompress);
 
      for ($i = 1; $i <= sizeof ($mgmt_uncompress); $i++)
      {
        // supported extension
        $extension = key ($mgmt_uncompress);
    
        if (substr_count ($extension, $file_ext) > 0) $check_unzip = true;
        next ($mgmt_uncompress);
      }
    }

    // compressed file that holds multimedia files
    if (isset ($check_unzip) && $check_unzip == true)
    {   
      $result_unzip = unzipfile ($site, $global_files['Filedata']['tmp_name'], $location, $global_files['Filedata']['name'], $cat, $user);

      if ($result_unzip == false)
      {
        $result['header'] = "HTTP/1.1 507 Internal Server Error";
        $result['message'] = $hcms_lang['file-could-not-be-extracted'][$lang];
        return $result;
      }
      elseif (is_array ($result_unzip))
      {
        $result['object'] = implode ("|", $result_unzip);
      }
    }
    // standard multimedia file upload in pages
    elseif ($cat == "page")
    {
      // move uploaded file
      if (!$is_remote_file)
      {
        $result_save = @move_uploaded_file ($global_files['Filedata']['tmp_name'], $location.$global_files['Filedata']['name']);
      }
      else $result_save = false;
      
      // save file from URL or if file has already been saved in the temp directory (WebDAV saves files in temp directory)
      if ($is_remote_file || $result_save == false)
      {
        $result_save = @rename ($global_files['Filedata']['tmp_name'], $location.$global_files['Filedata']['name']);
      }
      
      // eventsystem
      if ($eventsystem['onfileupload_post'] == 1) onfileupload_post ($site, $cat, $location, $page, "", "", $user);
    }
    // standard multimedia file upload in assets/components
    elseif ($cat == "comp")
    {
      // ---------------- create new multimedia object ------------------
      if ($media_update == "")
      {
        // if original image should not be resized
        if ($imageresize != "percentage") $imagepercentage = 0;
        
        $result = createmediaobject ($site, $location, $global_files['Filedata']['name'], $global_files['Filedata']['tmp_name'], $user, $imagepercentage);

        if ($result['result'] == false)
        {
          $errcode = 20511;
          $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile() -> the file '".$global_files['Filedata']['name']."' could not be created by createmediaobject (".$result_createobject['message'].")";
            
          // write log
          savelog (@$error);

          $show = $result_createobject['message'];
        }
      }
      // -------------- update existing multimedia object -----------------
      elseif ($media_update != "")
      {
        // update thumbnail file (uploaded file must be of type JPEG)
        if ($createthumbnail == 1)
        {
          // get file name without extension
          $file_name = substr ($media_update, 0, strrpos ($media_update, "."));

          // get the file extension
          $file_ext = strtolower (strrchr ($global_files['Filedata']['name'], "."));

          // temporary directory for extracting files
          $temp_dir = $mgmt_config['abs_path_temp'].$session_id."/";

          if ($file_ext == ".jpg" || $file_ext == ".jpeg")
          {
            // create temporary directory for extraction
            $test = @mkdir ($temp_dir, $mgmt_config['fspermission']);

            if ($test == true)
            {
              // copy to temporary directory
              if ($is_remote_file)
              {
                $result_upload = @rename ($global_files['Filedata']['tmp_name'], $temp_dir.$file_name.".jpg");
                
                if (!$result_upload)
                {
                  $show = "<span class=hcmsHeadline>".$hcms_lang['the-file-you-are-trying-to-upload-couldnt-be-copied-to-the-server'][$lang]."</span>\n";
                }
              }
              else
              {
                $result_upload = @move_uploaded_file ($global_files['Filedata']['tmp_name'], $temp_dir.$file_name.".jpg");
                
                if (!$result_upload)
                {
                  $show = "<span class=hcmsHeadline>".$hcms_lang['the-file-you-are-trying-to-upload-couldnt-be-copied-to-the-server'][$lang]."</span>\n";
                }
              }
              
              if ($result_upload == true)
              {
                $result_createthumb = createmedia ($site, $temp_dir, getmedialocation ($site, $file_name.".jpg", "abs_path_media").$site."/", $file_name.".jpg", "", "thumbnail");

                // if thumbnail creation failed use uploaded image as thumbnail image
                if ($result_createthumb == false)
                {
                  @copy ($temp_dir.$media_update, getmedialocation ($site, $file_name.".thumb.jpg", "abs_path_media").$site."/".$file_name.".thumb.jpg");

                  // remote client
                  remoteclient ("save", "abs_path_media", $site, getmedialocation ($site, $file_name.".thumb.jpg", "abs_path_media").$site."/", "", $file_name.".thumb.jpg", "");
                }
              }

              // delete temporary directory
              deletefile ($mgmt_config['abs_path_temp'], $session_id, 1);
            }
          }
        }
        // update multimedia file
        else
        {
          // get media root directory
          $media_root = getmedialocation ($site, $media_update, "abs_path_media").$site."/";
          $thumb_root = getmedialocation ($site, "dummy.".$media_update, "abs_path_media").$site."/";
          
          // prepare media file (in case it has been exported)
          $temp = preparemediafile ($site, $media_root, $media_update, $user);
          
          // reset location if restored
          if ($temp['restored'])
          {
            $media_root = $temp['location'];
            $media_update = $temp['file'];
          }

          // get container id
          $container_id = getmediacontainerid ($media_update);
          $contentfile = $container_id.".xml";

          // create version of previous content and media file
          if (!empty ($versioning)) $createversion = createversion ($site, $media_update);
          else $createversion = true;

          // if versioning was successful
          $result_save = false;
          
          if ($createversion && !empty ($media_root))
          {
            // delete old file and symbolic link
            deletefile ($media_root, $media_update, 0);
            deletefile ($thumb_root, $media_update, 0);
          
            // remember original file name
            $media_orig = $media_update;
            // get file name without extension of the old file
            $file_name_old = strrev (substr (strstr (strrev ($media_update), "."), 1));
            // get the file extension of the old file
            $file_ext_old = strtolower (strrchr ($media_update, "."));
            // get the file extension of the new file
            $file_ext_new = strtolower (strrchr ($global_files['Filedata']['name'], "."));
            // define new file name
            $media_update = $file_name_old.$file_ext_new;
            // get object name without extension
            $page_nameonly = specialchr_decode (strrev (substr (strstr (strrev ($page), "."), 1)));
            // get converted location
            $location_conv = convertpath ($site, $location, $cat);
            
            // save new multimedia file
            // move uploaded file
            if (!$is_remote_file)
            {
              $result_save = @move_uploaded_file ($global_files['Filedata']['tmp_name'], $media_root.$media_update);
            }

            // save file from URL or if file has already been saved in the temp directory (WebDAV saves files in temp directory)
            if ($is_remote_file || $result_save == false)
            {
              $result_save = @rename ($global_files['Filedata']['tmp_name'], $media_root.$media_update);
            }
          }

          if ($result_save == true)
          {
            // write stats for upload
            if ($container_id != "" && !is_thumbnail ($media_update, false))
            {
              rdbms_insertdailystat ("upload", $container_id, $user);
            }

            // get media size
            $media_size = @getimagesize ($media_root.$media_update);
            
            if (!empty ($media_size[0]) && !empty ($media_size[1]))
            {
              $imagewidth = round ($media_size[0], 0);
              $imageheight = round ($media_size[1], 0);
            }
            else
            {
              $imagewidth = 0;
              $imageheight = 0;
            }

            // get new rendering settings and set image options (if provided)
            if ($imagewidth > 0 && $imageheight > 0 && !empty ($imageformat))
            {
              $formats = "";

              while (list ($formatstring, $settingstring) = each ($mgmt_imageoptions))
              {
                if (substr_count ($formatstring, ".".$imageformat) > 0)
                {
                  $formats = $formatstring;
                }
              }

              if ($formats != "")
              {
                // convert the image file (remoteclient is used in createmedia)
                // Options:
                // -s ... size in pixels (width x height)
                // -f ... image output format
                $mgmt_imageoptions[$formats]['original'] = "-s ".$imagewidth."x".$imageheight." -f ".$imageformat;
                createmedia ($site, $media_root, $media_root, $media_update, "", "original");
              }
            }
            // create preview (thumbnail for images, previews for video/audio files)
            else
            {
              createmedia ($site, $media_root, $media_root, $media_update, "", "origthumb");
            }

            // remote client for uploaded original image
            remoteclient ("save", "abs_path_media", $site, $media_root, "", $media_update, "");

            // remove indexed content
            if ($file_ext_old != $file_ext_new)
            {
              // use file name before renaming to remove textnodes from DB
              unindexcontent ($site, $media_root, $media_orig, $contentfile, "", $user);
            }
            else unindexcontent ($site, $media_root, $media_update, $contentfile, "", $user);

            // index content of readable documents
            indexcontent ($site, $media_root, $media_update, $contentfile, "", $user);
          }

          // rename objects file extension, if file extension has changed
          if ($result_save == true && $file_ext_old != $file_ext_new)
          {
            // write new reference in object file
            $filedata = loadfile ($location, $page);
            if ($filedata != false) $filedata = setfilename ($filedata, "media", $media_update);

            if ($filedata != false)
            {
              $result_save = savefile ($location, $page, $filedata);
              // remote client
              remoteclient ("save", "abs_path_".$cat, $site, $location, "", $page, "");
            }
            else $result_save = false;

            // on success
            if ($result_save == true)
            {
              // rename media object, if file extension has changed
              $result_rename = renameobject ($site, $location, $page, $page_nameonly, $user);

              if ($result_rename['result'] == true)
              {
                // set new page name
                $page = $pagename = $page_nameonly.$file_ext_new;
                // define new page
                $result['object'] = specialchr_encode ($page);
              }
              // on error
              else
              {
                $show = $hcms_lang['the-file-could-not-be-renamed'][$lang]."\n";
              }
            }
          }
          
          // encrypt and save data
          if (is_file ($mgmt_config['abs_path_cms']."encryption/hypercms_encryption.inc.php") && isset ($mgmt_config[$site]['crypt_content']) && $mgmt_config[$site]['crypt_content'] == true)
          {
            $data = encryptfile ($media_root, $media_update);
            if (!empty ($data)) savefile ($media_root, $media_update, $data);
          }
          
          // save in cloud storage
          if (is_file ($media_root.$media_update))
          {
            if (function_exists ("savecloudobject")) savecloudobject ($site, $media_root, $media_update, $user);
          }

          // eventsystem
          if ($eventsystem['onfileupload_post'] == 1) onfileupload_post ($site, $cat, $location, $page, $media_update, $contentfile, $user);

          // notification
          notifyusers ($site, $location, $page, "oncreate", $user);
        }
      }
    }
    
    // define message on success
    if ($show == "") $show = $hcms_lang['uploaded-file-successfully'][$lang];
    
    // remove temp file downloaded from Dropbox or FTP server
    if (is_file ($temp_file)) unlink ($temp_file);
    
    // include object name or object paths (uncompressed ZIP files) in message
    if (empty ($show_command) && !empty ($result['object'])) $show_command = "[".$result['location_esc'].$result['object']."]";
  
    // return message and command to flash object
    $result['result'] = true;
    $result['header'] = "HTTP/1.1 200 OK";
    $result['message'] = strip_tags ($show).$show_command;
    return $result;
  }
  // required input is missing
  else
  {
    $result['header'] = "HTTP/1.1 509 Internal Server Error";
    $result['message'] = $hcms_lang['invalid-input-parameters'][$lang];
    return $result;  
  }
}

// ---------------------------------------- createmediaobject --------------------------------------------
// function: createmediaobject()
// input: site, destination location, file name, path to source multimedia file (uploaded file in temp directory), user, resize original image (100%) by percentage (optional)
// output: Array

// description:
// This function creates an asset (multimedia object) by reading a given source file

function createmediaobject ($site, $location, $file, $path_source_file, $user, $imagepercentage=0)
{
  global $mgmt_config, $mgmt_imageoptions, $eventsystem, $hcms_lang, $lang;     
  
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($file) && accessgeneral ($site, $location, "comp") && $path_source_file != "")
  {
    if (!valid_objectname ($user)) $user = "sys";
    
    // publication management config
    if (!is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");     
    
    // deconvert path
    if (substr_count ($path_source_file, "%page%") == 1 || substr_count ($path_source_file, "%comp%") == 1)
      $path_source_file = deconvertpath ($path_source_file, "file");  
    
    if (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1)
      $location = deconvertpath ($location, "file");  
      
    $location_esc = convertpath ($site, $location, "comp");
      
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";
    
    // information log entry
    $errcode = "00101";
    $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|$errcode|new multimedia file created by user '$user' ($site, $location_esc, $file, $path_source_file, $user)";     

    if (is_file ($path_source_file))
    {
      // create multimedia object
      $result = createobject ($site, $location, $file, "default.meta.tpl", $user);

      // copy file
      if ($result['result'] == true && !empty ($result['mediafile']) && !empty ($result['container_id']) && !empty ($result['container_content']))
      {
        $file_name = substr ($result['object'], 0, strrpos ($result['object'], "."));
        $file_ext = strtolower (strrchr ($result['object'], "."));
        $mediafile = $result['mediafile'];
        $container_id = $result['container_id'];
        $container_content = $result['container_content'];

        // define media location
        $medialocation = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";
        
        // move multimedia file to content media repository
        // case "upload": move uploaded file from temp directory
        $result_move = @move_uploaded_file ($path_source_file, $medialocation.$mediafile);
        
        // case "import": move import file from source directory 
        if (!$result_move) $result_move = @rename ($path_source_file, $medialocation.$mediafile);

        if ($result_move)
        {
          // write stats for upload
          if (!is_thumbnail ($mediafile, false))
          {
            rdbms_insertdailystat ("upload", $container_id, $user);
          }
          
          // index content
          indexcontent ($site, $medialocation, $mediafile, $container_id, $container_content, $user);

          // resize original image if requested
          if (!empty ($mediafile) && $imagepercentage > 0 && $imagepercentage <= 200)
          {
            // convert uploaded original images only of given formats
            // note: output rendering must be supported and the render format must be the same format as the original file!
            $media_supported_ext = ".gif.jpg.jpeg.png";
            // get media size
            $media_size = @getimagesize ($medialocation.$mediafile);
            // get file extension
            $media_ext = strtolower (strrchr ($mediafile, "."));
  
            // get new rendering settings for original images and set image options (if given)
            if (substr_count ($media_supported_ext.".", $media_ext.".") > 0 && $media_size != false)
            {
              $imagewidth = round ($media_size[0] * $imagepercentage / 100, 0);
              $imageheight = round ($media_size[1] * $imagepercentage / 100, 0);
              
              if ($imagewidth != "" && $imageheight != "")
              {
                $formats = "";
                reset ($mgmt_imageoptions);
  
                while (list ($formatstring, $settingstring) = each ($mgmt_imageoptions))
                {
                  if (substr_count ($formatstring.".", $media_ext.".") > 0)
                  {
                    $formats = $formatstring;
                  }
                }
                
                if ($formats != "")
                {
                  // convert the image file (remoteclient is used in createmedia)
                  // Options:
                  // -s ... size in pixels (width x height)
                  // -f ... image output format
                  $mgmt_imageoptions[$formats]['original'] = "-s ".$imagewidth."x".$imageheight." -f ".str_replace (".", "", $media_ext);
                  createmedia ($site, $medialocation, $medialocation, $mediafile, str_replace (".", "", $media_ext), "original");
                }
              }
            }
          }
          // create thumbnail/preview of original media file
          else
          {
            // create preview
            createmedia ($site, $medialocation, $medialocation, $mediafile, "", "origthumb", false);
          }

          // remote client
          remoteclient ("save", "abs_path_media", $site, $medialocation, "", $mediafile, "");
          
          // encrypt data
          if (is_file ($mgmt_config['abs_path_cms']."encryption/hypercms_encryption.inc.php") && isset ($mgmt_config[$site]['crypt_content']) && $mgmt_config[$site]['crypt_content'] == true)
          {
            $data = encryptfile ($medialocation, $mediafile);
            if (!empty ($data)) savefile ($medialocation, $mediafile, $data);
          } 
          
          // save in cloud storage
          if (is_file ($medialocation.$mediafile) && function_exists ("savecloudobject")) savecloudobject ($site, $medialocation, $mediafile, $user);

          // eventsystem
          if ($eventsystem['onfileupload_post'] == 1) onfileupload_post ($site, $result['cat'], $location, $result['object'], $mediafile, $result['container'], $user);            
        }
        else
        {
          $errcode = "10501";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|createmediaobject failed to move '".$path_source_file."' to  '".getmedialocation ($site, $mediafile, "abs_path_media").$site."/".$mediafile."', return value: $result_move"; 
          
          $result['result'] = false;
        }
      }
      else
      {
        $errcode = "10502";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|createmediaobject failed to successfully execute createobject ($site, $location_esc, $file, 'default.meta.tpl', $user)"; 
          
        $result['result'] = false;
      }
    }
    else
    {
      $errcode = "10503";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|createmediaobject could not find source file '$path_source_file'"; 
           
      $result['result'] = false;
    }
  }
  else $result['result'] = false;
  
  // error log
  savelog (@$error);
  
  return $result;
}

// ---------------------------------------- createmediaobjects --------------------------------------------
// function: createmediaobjects()
// input: site, source location, destination location, user
// output: result array with all objects created  / false

// description:
// This function creates media objects by reading all media files from a given source location (used after unzipfile)

function createmediaobjects ($site, $location_source, $location_destination, $user)
{
  global $mgmt_config, $mgmt_imageoptions, $eventsystem, $hcms_lang, $lang;

  if (valid_publicationname ($site) && valid_locationname ($location_source) && valid_locationname ($location_destination))
  {
    $result = array();
    
    if (!valid_objectname ($user)) $user = "sys";
    
    // publication management config
    if (!is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");     
    
    // deconvert path
    if (substr_count ($location_source, "%page%") == 1 || substr_count ($location_source, "%comp%") == 1)
      $location_source = deconvertpath ($location_source, "file");  
    
    if (substr_count ($location_destination, "%page%") == 1 || substr_count ($location_destination, "%comp%") == 1)
      $location_destination = deconvertpath ($location_destination, "file");  
      
    // add slash if not present at the end of the location string
    if (substr ($location_source, -1) != "/") $location_source = $location_source."/";
    if (substr ($location_destination, -1) != "/") $location_destination = $location_destination."/";      
    
    if (is_dir ($location_destination))
    {
      $dir = @opendir ($location_source);
  
      if ($dir != false)
      { 
        // loop through source file
        while ($file = readdir ($dir))
        {
          // skip Mac OS files .DS_Store and ._whatever
          if (valid_objectname ($file) && $file != '.' && $file != '..' && !is_tempfile ($file)) 
          {
            if (is_dir ($location_source.$file))
            {
              $folder = $folder_new = $file;
              
              // correct file namens which were decoded by unzip
              if (substr_count ($folder_new, "#U") > 0) $folder_new = json_decode (str_replace ('#U', '\u', $folder_new)); 
  
              // create folder          
              $createfolder = createfolder ($site, $location_destination, $folder_new, $user);
              if ($createfolder['result'] == true) $result = createmediaobjects ($site, $location_source.$folder."/", $location_destination.$createfolder['folder']."/", $user);
            }
            elseif (is_file ($location_source.$file))
            {
              $objectname = $file;
              
              // correct file namens which were decoded by unzip
              if (substr_count ($objectname, "#U") > 0) $objectname = json_decode (str_replace ('#U', '\u', $objectname));
  
              // create multimedia object
              $createmediaobject = createmediaobject ($site, $location_destination, $objectname, $location_source.$file, $user);
              if ($createmediaobject['result'] == true) $result[] = $createmediaobject['location_esc'].$createmediaobject['object'];
            }
          }
        }
        
        @closedir ($dir);
        return $result;
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// ---------------------- editmediaobject -----------------------------
// function: editmediaobject()
// input: publication, locationr, object name, format (file extension w/o dot) (optional), 
//        type of image/video/audio file [thumbnail,origthumb(thumbail made from original video/audio),original,any other string present in $mgmt_imageoptions] (optional)
// output: result array / false on error (saves original or thumbnail media file of an object, for thumbnail only jpeg format is supported as output), user name

// description:
// This function mainly uses function createmedia to render the objects media, but at the same time takes care of versioning and the object name, if the file extension has been changed

function editmediaobject ($site, $location, $page, $format="jpg", $type="thumbnail", $user)
{
  global $wf_token, $mgmt_config, $mgmt_imagepreview, $mgmt_mediapreview, $mgmt_mediaoptions, $mgmt_imageoptions, $mgmt_maxsizepreview, $mgmt_mediametadata, $hcms_ext, $hcms_lang, $lang;

  $processresult = false;
  $show = "";
  $add_onload = "";
  $cat = "";
  $mediafile_new = "";
  $container = "";
  
  // set default language
  if ($lang == "") $lang = "en";
  
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($user))
  {
    $cat = getcategory ($site, $location);
    $location_esc = convertpath ($site, $location, $cat);
    
    $pagefile_info = getfileinfo ($site, $page, $cat);
    $pageobject_info = getobjectinfo ($site, $location, $page);
    $container_id = $pageobject_info['container_id'];
    $container = $container_id.".xml";
    $mediafile_orig = $pageobject_info['media'];
    $mediafile_location = getmedialocation ($site, $mediafile_orig, "abs_path_media").$site."/";

    // create new version
    $createversion = createversion ($site, $mediafile_orig);

    // render media file of object
    if ($createversion) $mediafile_new = createmedia ($site, $mediafile_location, $mediafile_location, $mediafile_orig, $format, $type);
    else $mediafile_new = false;

    // if successful
    if ($mediafile_new && $createversion)
    {
      $processresult = true;

      // get the file extension of the old file      
      $file_ext_old = strtolower (strrchr ($mediafile_orig, "."));
      // get the file extension of the new file
      $file_ext_new = strtolower (strrchr ($mediafile_new, "."));
      // get file name without extension of the old file
      $mediafile_nameonly = strrev (substr (strstr (strrev ($mediafile_orig), "."), 1));
      // get object name without extension
      $page_nameonly = strrev (substr (strstr (strrev ($pagefile_info['name']), "."), 1));

      // rename object file extension if file extension has changed due to coversion
      if ($file_ext_old != $file_ext_new)
      {
        // reset result
        $processresult = false;
        
        // write new media reference in object file
        $filedata = $filedata_orig = loadfile ($location, $page);
        if ($filedata != false) $filedata = setfilename ($filedata, "media", getobject ($mediafile_nameonly).$file_ext_new); 
        
        if ($filedata != false)
        {
          $savepage = savefile ($location, $page, $filedata);
          // remote client
          remoteclient ("save", "abs_path_".$cat, $site, $location, "", $page, "");                
        }
        else $savepage = false;

        // on success
        if ($savepage == true)
        { 
          // rename media object, if file extension has changed  
          $rename = renameobject ($site, $location, $page, $page_nameonly, $user);

          if ($rename['result'] == true)
          {
            $processresult = true;
          
            // set new page name and media file name
            $page = $rename['object'];
            $mediafile = $mediafile_nameonly.$file_ext_new;

            // remote client
            remoteclient ("save", "abs_path_".$cat, $site, $location, "", $page, "");
            
            $show = $hcms_lang['the-file-was-processed-successfully'][$lang];
            
            // add onload
            $add_onload = "parent.frames['controlFrame'].location='".$mgmt_config['url_path_cms']."control_content_menu.php?site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."';\n";
          }
          // on error
          else
          {
            // revert changes back
            savefile ($location, $page, $filedata_orig);
            $show = $hcms_lang['a-value-is-required'][$lang]."\n";         
          }           
        }
        // on error
        else
        {
          $show = $hcms_lang['file-could-not-be-saved-or-only-partialy-saved'][$lang]."\n";         
        }
      }
    }
    // media file could not be created
    else
    {
      $show = $hcms_lang['an-error-occured-in-the-data-manipulation'][$lang]."\n"; 
    }
  }
  
  // return results
  $result = array();
  $result['result'] = @$processresult;
  $result['add_onload'] = @$add_onload;  
  $result['message'] = @$show;
  $result['publication'] = @$site;
  $result['location'] = @$location;
  $result['cat'] = @$cat;
  $result['object'] = @$page;
  $result['mediafile'] = @$mediafile_new;
  $result['container'] = @$container;
  $result['container_id'] = @$container;
  
  return $result;
}

// ---------------------------------------- manipulateobject --------------------------------------------
// function: manipulateobject()
// input: site, location, object name, new object name (exkl. extension except for action "file_rename"), user, 
//        action [page_delete, page_rename, file_rename, page_paste, page_unpublish]
// output: array

// description:
// This function removes, unpublishs, renames and pastes objects and is used by other functions which works as a shell for this function

function manipulateobject ($site, $location, $page, $pagenew, $user, $action)
{
  global $wf_token, $eventsystem,
         $mgmt_config, $mgmt_mediaoptions, $mgmt_docoptions, $hcms_ext,
         $pageaccess, $compaccess, $hiddenfolder,     
         $cat, $temp_clipboard, 
         $hcms_lang, $lang;
         
  // default values for action = paste before loading the clipboard
  $error_switch = "";
  $method = "";
  $site_source = "";
  $cat_source = "";
  $location_source_esc = "";
  $add_onload = "";
  $show = "";
  $allow_delete = true;
  
  // set default language as "en" if not set
  if (empty ($lang)) $lang = "en";
 
  if (valid_publicationname ($site) && valid_locationname ($location) && accessgeneral ($site, $location, $cat) && valid_objectname ($user) && $action != "")
  {
    // load file extensions
    if (empty ($hcms_ext) || !is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
 
    // publication management config
    if (empty ($mgmt_config[$site]) || !is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php"); 

    // convert location
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, $cat);

    // site buffer variable holds current site
    $site_buffer = $site;

    // load publication inheritance setting
    $inherit_db = inherit_db_read ();
    $parent_array = inherit_db_getparent ($inherit_db, $site);
    
    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location);   
    
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";  
    
      // eventsystem for paste
    if ($action == "page_paste" && $eventsystem['onpasteobject_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0))
      onpasteobject_pre ($site, $cat, $location_source, $location, $page, $user);     
   
    // get object information of the selected item
    if ($action != "page_paste" && valid_objectname ($page))
    {
      // correct object file name
      $page = correctfile ($location, $page, $user);

      // redefine location and object if page is a directory 
      if ($page != "" && $page != ".folder" && is_dir ($location.$page) && is_file ($location.$page."/.folder"))
      {
        $page = ".folder";
        $location = $location.$page."/";
        $location_esc = $location_esc.$page."/";
      }         

      // get file info
      $fileinfo = getfileinfo ($site, $page, $cat);    
      $pagename = $fileinfo['file'];    
      $pagename_orig = $fileinfo['name'];
      $filetype = $fileinfo['type'];
      $fileext = $fileinfo['ext'];

      // load page file
      $pagedata = loadfile ($location, $page);
  
      if ($pagedata != false) 
      {  
        // get name of content, template and media file
        $contentfile_self = getfilename ($pagedata, "content");
        $templatefile_self = getfilename ($pagedata, "template");
        $mediafile_self = getfilename ($pagedata, "media");
        $namefile_self = getfilename ($pagedata, "name");
      }
      else 
      {
        $test = false;
        
        $errcode = "10219";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|loadfile failed for ".$location_esc.$page;                  
      }
    }
    elseif ($action == "page_paste")
    {
      if (isset ($_SESSION['hcms_temp_clipboard']) || isset ($temp_clipboard))
      {
        // the clipboard has the following structure:
        // method|site|cat|location|object|object name|filetype  
        if (!empty ($_SESSION['hcms_temp_clipboard'])) $clipboard = $_SESSION['hcms_temp_clipboard'];
        elseif ($temp_clipboard != "") $clipboard = $temp_clipboard;
        else $clipboard = "";

        if ($clipboard != "")
        {
          list ($method, $site_source, $cat_source, $location_source_esc, $page, $pagename, $filetype) = explode ("|", chop ($clipboard));
  
          if (!is_array ($mgmt_config[$site_source])) require ($mgmt_config['abs_path_data']."config/".$site_source.".conf.php");
          
          $location_source = deconvertpath ($location_source_esc, "file");
          
          // correct object file name
          $page = correctfile ($location_source, $page, $user);
          
          // redefine location and object if page is a directory 
          if ($page != "" && $page != ".folder" && is_dir ($location_source.$page) && is_file ($location_source.$page."/.folder"))
          {
            $page = ".folder";
            $location_source = $location_source.$page."/";
            $location_source_esc = $location_source_esc.$page."/";
          }          
          
          // check if object may be pasted in the current publication
          if ($site == $site_source || ($mgmt_config[$site]['inherit_obj'] == true && $parent_array != false && in_array ($site_source, $parent_array)))
          { 
            // if the category of the object (page or component) is different for cut/copy and paste
            if ($cat_source != $cat) 
            {
               $add_onload = "";
               $show = "<span class=\"hcmsHeadline\">".$hcms_lang['it-is-not-possible-to-paste-the-objects-here'][$lang]."</span><br />\n";
            }
            // if the cutted object will be pasted in the source location
            elseif ($method == "cut" && substr_count ($location_esc, $location_source_esc) > 0)
            {
              $add_onload = "";
              $show = "<span class=\"hcmsHeadline\">".$hcms_lang['it-is-not-possible-to-cut-and-paste-objects-in-the-same-destination'][$lang]."</span><br />\n";         
            }
          }  
          else
          {
            $add_onload = "";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['you-do-not-have-permissions-to-paste-objects-from-the-other-publication-in-this-publication'][$lang]."</span><br />\n";
          }           
          
          // get file info
          $fileinfo = getfileinfo ($site, $page, $cat);    
          $pagename = $fileinfo['file'];
          $pagename_orig = $fileinfo['name'];
          $filetype = $fileinfo['type'];   
          $fileext = $fileinfo['ext'];                 
          
          // load object file
          $pagedata = loadfile ($location_source, $page);  
         
          if ($pagedata != false) 
          {
            // get media and template file name
            $mediafile_self = getfilename ($pagedata, "media");
            $contentfile_self = getfilename ($pagedata, "content");
            $templatefile_self = getfilename ($pagedata, "template");
          }
          else 
          {
            $test = false;
            
            $errcode = "10209";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|loadfile failed for ".$location_source.$page;                  
          }
        }
        else
        {
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['clipboard-is-empty'][$lang]."</span><br />\n";
        }
      }
      else
      {
        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['please-select-an-item-first'][$lang]."</span><br />\n";
      }
    }
    
    // define variables depending on content category
    if ($filetype != "Page" && $filetype != "Component")
    {
      $dir_name = "dir";
      $access = $compaccess;
    }
    elseif ($filetype != "Page")
    {
      $dir_name = "dir";
      $access = $pageaccess;
    }
    elseif ($cat == "comp")
    {
      $dir_name = "comp_dir";
      $access = $compaccess;
    }
    
    // ----------------------------- define file name and check if file is writeable ------------------------------
    // pagenewname ... file name without management file extension
    // pagenew ... real filename with management extension (.off)
    if ($show == "")
    {
      if ($action == "page_delete")
      {
        if (is_file ($location.$page)) $file_writeable = true;
        else $file_writeable = false;
      }
      elseif ($action == "page_unpublish")
      {   
        if (is_file ($location.$page)) $file_writeable = true;
        else $file_writeable = false;

        // define new page file name
        if ($fileinfo['published'] == false)
        {
          $pagenewname = $pagename;
          $pagenew = $page;
        }
        else
        {
          $pagenewname = $pagename;
          // add .off extension
          $pagenew = $page.".off";
        }
      }
      elseif ($action == "page_rename" || $action == "file_rename")
      {
        if (is_file ($location.$page)) $file_writeable = true;
        else $file_writeable = false;
        
        if ($pagenew != "")
        {
          // buffer new object name
          $pagenew_orig = $pagenew;
          $page_orig = $page;

          // encode object names
          if (specialchr ($page, ".-_~") == true) $page = specialchr_encode ($page, "no");    
          if (specialchr ($pagenew, ".-_") == true) $pagenew = specialchr_encode ($pagenew, "no");

          // if pagenew holds only the name
          if ($action == "page_rename")
          {
            // if multimedia component
            if ($mediafile_self != "")
            {
              // get extension of multimedia file
              $mediafile_self_info = getfileinfo ($site, $mediafile_self, "comp");
              $fileext = $mediafile_self_info['ext'];
              $pagenew = $pagenewname = $pagenew.$fileext;
              $pagenewname_orig = $pagenew_orig.$fileext;
            }
            // if object (page or component)
            else
            {
              $pagenew = $pagenewname = $pagenew.$fileext;
              $pagenewname_orig = $pagenew_orig.$fileext;
            }
            
            // if object is unpublished
            if ($fileinfo['published'] == false) $pagenew = $pagenew.".off";
          }
          // if pagenew includes extension
          elseif ($action == "file_rename")
          {
            $pagenewname = $pagenew;
            $pagenewname_orig = $pagenew_orig;

            // if object is unpublished
            if ($fileinfo['published'] == false) $pagenew = $pagenew.".off"; 
          }
          
          // check both names (published or unpublished)
          if (substr ($pagenew, -4) == ".off")
          {
            $pagenew_unpub = $pagenew;
            $pagenew_pub = substr ($pagenew, 0, -3);
          }
          else
          {
            $pagenew_unpub = $pagenew.".off";
            $pagenew_pub = $pagenew;
          }
          
          // if file doesn't exist
          if (!is_file ($location.$page))
          {
            $add_onload = "";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-doesnt-exist-or-you-do-not-have-write-permissions'][$lang]."</span><br />\n";
          }
          // if new object exists already (published or unpublished)
          elseif ((is_file ($location.$pagenew_pub) || is_file ($location.$pagenew_unpub)) && strtolower ($location.$page) != strtolower ($location.$pagenew))
          {
            $add_onload = "";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-exists-already'][$lang]."</span><br />\n";
          }        
        }
        else 
        {
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-new-name-must-not-be-empty'][$lang]."</span><br />\n";
        }
      }  
      elseif ($action == "page_paste" && ($method == "copy" || $method == "linkcopy"))
      {
        // check if user has access to paste file in current location
        if (!empty ($access) && accesspermission ($site, $location, $cat) == false)
        {
          // no access permission
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['you-do-not-have-permissions-to-paste-items-in-the-current-location'][$lang]."</span><br />\n";
        }
        else
        {
          if (is_file ($location_source.$page)) $file_writeable = true;
          else $file_writeable = false;
    
          // define secondary names
          $page_sec = $page;
          $pagename_sec = $pagename;
          
          $page_sec_info = getfileinfo ($site, $page_sec, $cat);
          $pagename_sec_info = getfileinfo ($site, $pagename_sec, $cat);
          
          // if object is not published the .off extension has to be added
          if ($page_sec_info['published'] == false) $add_ext = ".off";
          else $add_ext = "";
    
          // define file name after pasting
          if (is_file ($location.$page_sec) && !is_file ($location.$page_sec_info['filename']."-Copy".$page_sec_info['ext']))
          {
            // define new file name with copy suffix
            $page_sec = $page_sec_info['filename']."-Copy".$page_sec_info['ext'].$add_ext;
            $pagename_sec = $pagename_sec_info['filename']."-Copy".$pagename_sec_info['ext'];
          }
          elseif (is_file ($location.$page_sec_info['filename']."-Copy".$page_sec_info['ext']))
          {
            for ($c=2; $c<=100; $c++)
            {
              // define new file name with copy suffix
              $page_sec = $page_sec_info['filename']."-Copy".$c.$page_sec_info['ext'].$add_ext;
              $pagename_sec = $pagename_sec_info['filename']."-Copy".$c.$pagename_sec_info['ext'];
              
              if (!is_file ($location.$page_sec))
              {
                $add_onload = "";
                $show = "";
                break;
              }
              else
              {
                $add_onload = "";
                $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-exists-already'][$lang]."</span><br />\n";              
              }
            }
          }
        }
      }
      elseif ($action == "page_paste" && $method == "cut")
      {
        // check if user has access to paste file in current location
        if (!empty ($access) && accesspermission ($site, $location, $cat) == false)
        {
          // no access permission
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['you-do-not-have-permissions-to-paste-items-in-the-current-location'][$lang]."</span><br />\n";
        }
        else
        {
          if (is_writable ($location_source.$page)) $file_writeable = true;
          else $file_writeable = false;
    
          // if source location = destination location
          if ($location_source == $location)
          {
            $add_onload = "";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['you-cannot-cut-and-paste-an-item-in-the-same-location'][$lang]."</span><br />\n";
          }
          elseif (is_file ($location.$page))
          {
            $add_onload = "";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-exists-already'][$lang]."</span><br />\n";
          }
        }
      }
      
      // get content container id
      if (!empty ($contentfile_self)) $contentfile_id = substr ($contentfile_self, 0, strpos ($contentfile_self, ".xml"));
      else $contentfile_id = "";
           
      // --------------------------------- convert location and define object paths ---------------------------------
      if (isset ($location)) $location_esc = convertpath ($site, $location, $cat);     

      // ============================== delete/update records in link management ==============================
      if ($show == "" && $file_writeable)
      {      
        // -------------------- if action is unpublish, delete, rename, cut & paste ---------------------    
        if ($method != "copy" && $method != "linkcopy")
        {
          $site_array[0] = $site;
          
          // if inheritance of components is used, every child publication must also be updated
          if (strtolower ($cat) == "comp")
          {
            // load publication inheritance setting
            if (is_file ($mgmt_config['abs_path_data']."config/inheritance.dat")) 
            {
              $inherit_db = inherit_db_read ();
        
              if (sizeof ($inherit_db) >= 1)
              {
                $child_array = inherit_db_getchild ($inherit_db, $site);
                
                if ($child_array != false)
                {
                  $site_array = array_merge ($site_array, $child_array);
                }
              }        
            }
          }
          
          // loop for each site
          foreach ($site_array as $site)
          {    
            // include configurations
            if ((!isset ($mgmt_config[$site]) || !is_array ($mgmt_config[$site])) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
            {
              include_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");  
            }
          
            // lock and read link management file
            $link_db = link_db_load ($site, $user);
          
            // each record of the link management database has the following structure:
            // xml-index :| absolute path to 1-n object files :| 1-m links used in 1-n object files
            // please note: each object file uses the same xml-object-file.      
      
            if (is_array ($link_db) && sizeof ($link_db) > 0)
            {
              // define old page url
              if ($action == "page_rename" || $action == "file_rename" || $action == "page_unpublish" || $action == "page_delete") $obj_location = $location_esc.$pagename;
              elseif ($action == "page_paste" && $method == "cut") $obj_location = $location_source_esc.$pagename;
      
              // define new page url
              if ($action == "page_rename" || $action == "file_rename" || $action == "page_unpublish") $obj_location_new = $location_esc.$pagenewname;
              elseif ($action == "page_paste" && $method == "cut") $obj_location_new = $location_esc.$pagename;

              // ------------------- update links in content files (set link -> null) -----------------------
      
              // loop for each link_db record
              foreach ($link_db as $link_db_record)
              {
                // update links in content containers

                // check if manipulated object is in the linklist of other objects
                if (@substr_count ($link_db_record['link'], $obj_location."|") > 0)
                {
                  // get content container
                  $contentcontainer = $link_db_record['container'];
                  
                  // get container id
                  $container_id = substr ($contentcontainer, 0, strpos ($contentcontainer, ".xml")); 
              
                  // remove link to page or component from content container
                  if ($action == "page_delete" || ($action == "page_unpublish" && $cat == "page")) $test = link_update ($site, $contentcontainer, $obj_location, "");
                  // update link in content container
                  elseif ($action == "page_rename" || $action == "file_rename" || ($action == "page_paste" && $method == "cut")) $test = link_update ($site, $contentcontainer, $obj_location, $obj_location_new);
                  else $test = true;

                  if ($test == false) 
                  {
                    $errcode = "20101";
                    $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|link_update failed for ".$contentcontainer;
                  }
      
                  // remove link to deleted page in link management
                  if ($action == "page_delete" || ($action == "page_unpublish" && $cat == "page"))
                  {
                    $link_db = link_db_update ($site, $link_db, "link", $contentcontainer, $cat, $obj_location, "", "all");
                  }
                  // update link
                  else
                  {
                    $link_db = link_db_update ($site, $link_db, "link", $contentcontainer, $cat, $obj_location, $obj_location_new, "all");
                  }
 
                  // ---------------------------------- create new task -----------------------------------     
                  // load task file of page user, set new task and save task file
                  if ($action == "page_delete" || ($action == "page_unpublish" && $cat == "page"))
                  {
                    // get user name
                    $container_data = loadcontainer ($contentcontainer, "version", $user);
                    $contentuser_array = getcontent ($container_data, "<contentuser>");
                    $contentuser = $contentuser_array[0];
                    
                    if ($contentuser != false)
                    {
                      // send mail to user
                      if ($mgmt_config[$site]['sendmail'] == true)
                      {
                        if (!isset ($user_data) || $user_data == "") $user_data = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");
                        $user_record = selectcontent ($user_data, "<user>", "<login>", $contentuser);
                        $to_email_array = getcontent ($user_record[0], "<email>");
                        $to_email = $to_email_array[0];
                      }
      
                      // set message
                      $message = $hcms_lang['there-is-a-new-task-due-to-broken-link'][$lang];
                       
                      // get objects
                      $page_path_array = link_db_getobject ($link_db_record['object']);
                      
                      // sender
                      if ($mgmt_config[$site]['mailserver'] != "") $from_email = "hyperCMS@".$mgmt_config[$site]['mailserver'];
                      else $from_email = "automailer@hypercms.net";                      
                    
                      // loop for each object of the same content container using the link
                      if (is_array ($page_path_array))
                      {
                        foreach ($page_path_array as $page_path)
                        {                        
                          // set new task for object owner
                          if (function_exists ("createtask")) createtask ($site, "System", $from_email, $contentuser, $to_email, "", "", "link", $page_path, $hcms_lang['link-management'][$lang], $message, $mgmt_config[$site]['sendmail'], "medium");
                        }
                      }
                    }
                  }
                  // ---------------------------------- end new task -----------------------------------
                }
    
                // delete/update actual record in link management of the manipulated object
                if ($link_db_record['container'] == $contentfile_self)
                {
                  // check if link record may be deleted, only if no other object uses the same content container
                  if ($action == "page_delete") 
                  {
                    // get objects
                    $page_path_array = link_db_getobject ($link_db_record['object']);
                  
                    // check if more than 1 objects use the same container
                    if (sizeof ($page_path_array) > 1) 
                    {
                      $link_db = link_db_update ($site, $link_db, "object", $contentfile_self, $cat, $location.$pagename, "", "all");                  
                      $allow_delete = false;
                    }
                    // if not, delete whole record
                    else 
                    {
                      $link_db = link_db_delete ($site, $link_db, $contentfile_self);
                      $allow_delete = true;
                    }
                    
                    $test = true;
                  }
                  elseif ($action == "page_rename" || $action == "file_rename") 
                  {
                    $link_db = link_db_update ($site, $link_db, "object", $contentfile_self, $cat, $location.$pagename, $location.$pagenewname, "all");
                    $test = link_update ($site, $contentfile_self, $obj_location, $obj_location_new);
                  }
                  elseif ($action == "page_paste" && $method == "cut") 
                  {
                    $link_db = link_db_update ($site, $link_db, "object", $contentfile_self, $cat, $location_source.$pagename, $location.$pagename, "all");  
                    $test = link_update ($site, $contentfile_self, $obj_location, $obj_location_new);
                  }
                  else $test = true;
                
                  if ($test == false) 
                  {
                    $errcode = "20104";
                    $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|link_update failed for ".$contentcontainer;
                  }             
                }
              } // end foreach link_db_record loop
        
              // --------------------------------- save link management db ---------------------------------------
              if (is_array ($link_db))
              {
                // unlock and save link management file
                $savelinkmgmt = link_db_save ($site, $link_db, $user);

                // if an error occured while loading or saving the link management file
                if ($savelinkmgmt == false)
                {
                  link_db_close ($site, $user);
                  
                  $errcode = "20102";
                  $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|link_db_save failed for $site.link.dat";
      
                  $add_onload = "";
                  $show = "<span class=\"hcmsHeadline\">".$hcms_lang['link-management-error'][$lang]."</span><br />".$hcms_lang['link-management-database-could-not-be-saved'][$lang]."\n";
                }
              }
              else
              {
                link_db_close ($site, $user);
                
                $errcode = "20103";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|link_db is not array and failed for $site.link.dat";
        
                $add_onload = "";
                $show = "<span class=\"hcmsHeadline\">".$hcms_lang['link-management-error'][$lang]."</span><br />".$hcms_lang['an-error-occured-while-writing-data-to-the-link-management-database'][$lang]."\n";        
              }
            }
            elseif ($link_db == false)
            {
              // unlock file
              link_db_close ($site, $user);
              
              $errcode = "20301";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|link_db_load failed for ".$site;              
      
              $add_onload = "";
              $show = "<span class=\"hcmsHeadline\">".$hcms_lang['link-management-error'][$lang]."</span><br />".$hcms_lang['link-management-database-could-not-be-loaded'][$lang]."\n";
            }
          }
        }
      }
      elseif ($show == "")
      {
        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-doesnt-exist-or-you-do-not-have-write-permissions'][$lang]."</span><br />\n";
      }
    } 
    
    // ============================== make changes on page or component  ===================================
    if ($show == "")
    {
      // get original site from buffer
      $site = $site_buffer;
      
      // reload the configuration of the current publication
      if ((!isset ($mgmt_config[$site]) || !is_array ($mgmt_config[$site])) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
      {
        include_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");  
      }   
   
      // --------------------------------- update content container status ------------------------------
      // if an object is copied and pasted leave the container as it is
      if (($method != "copy" || $method != "linkcopy") && $contentfile_self != "")
      {      
        // load content container from repository
        $result = getcontainername ($contentfile_self);
        
        if (!empty ($result['container']))
        {
          $contentfile_self_wrk = $result['container'];
          $bufferdata = loadcontainer ($contentfile_self_wrk, "work", $user);
        }
  
        if (!empty ($bufferdata))
        {
          // insert new content status and objects references into content file
          if ($action == "page_delete") 
          {          
            $objects = getcontent ($bufferdata, "<contentobjects>");
            // remove object reference ion container (except last entry, since the object will be deleted completetly)
            if (!empty ($objects[0]) && substr_count ($objects[0], "|") > 0)
            {
              $objects_str = str_replace ($location_esc.$pagename."|", "", $objects[0]);
              $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentobjects>", $objects_str, "", "");
            }
          
            if ($allow_delete == true)
            {
              $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentstatus>", "deleted", "", "");
            } 
          }
          // insert new objects references into content container
          elseif ($action == "page_rename" || $action == "file_rename") 
          {
            $objects = getcontent ($bufferdata, "<contentobjects>");

            if (!empty ($objects[0]) && substr_count ($objects[0], "|") > 0)
            {
              $objects_str = str_replace ($location_esc.$pagename."|", $location_esc.$pagenewname."|", $objects[0]);
              $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentobjects>", $objects_str, "", "");
            }
          }
          // insert new objects references into content container
          elseif ($action == "page_paste" && $method == "cut") 
          {
            $objects = getcontent ($bufferdata, "<contentobjects>");
            
            if (!empty ($objects[0]) && substr_count ($objects[0], "|") > 0)
            {
              $objects_str = str_replace ($location_source_esc.$pagename."|", $location_esc.$pagename."|", $objects[0]);
              $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentobjects>", $objects_str, "", "");
            }
          }
    
          // insert user into content file
          $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentuser>", $user, "", "");
  
          // insert new date into content file
          $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentdate>", $mgmt_config['today'], "", "");
  
          // save content container
          if ($bufferdata != "" && $bufferdata != false)
          {              
            $test = savecontainer ($contentfile_self_wrk, "work", $bufferdata, $user);
            
            // final container data
            $containerdata = $bufferdata;          
  
            if ($test == false)
            {
              $errcode = "10105";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|savefile failed for ".$contentfile_self;
            }
          }
        }              
      }
  
      // ---------------------------------------- delete object -------------------------------------
      if ($show == "" && $action == "page_delete")
      {
        if ($allow_delete == true && ($cat == "page" || $cat == "comp"))
        {       
          // delete page file
          $test = deletefile ($location, $page, 0); 
  
          if ($test != false)
          {
            // relational DB connectivity
            if ($mgmt_config['db_connect_rdbms'] != "")
            {       
              rdbms_deleteobject (convertpath ($site, $location.$page, $cat));                     
            }
          
            // remote client
            remoteclient ("delete", "abs_path_".$cat, $site, $location, "", $page, "");
            
            // delete media file derivates
            if (!empty ($mediafile_self)) deletemediafiles ($site, $mediafile_self, true);

            // delete thumbnail file (versions before 5.0)
            $object_thumb = substr ($page, 0, strrpos ($page, ".")).".thumb".substr ($page, strrpos ($page, "."));
            
            if (is_file ($location.$object_thumb))
            {
              deletefile ($location, $object_thumb, 0);            
              // remote client
              remoteclient ("delete", "abs_path_".$cat, $site, $location, "", $object_thumb, "");
            }
                   
            // delete workflow
            if (is_file ($mgmt_config['abs_path_data']."workflow/".$site."/".$contentfile_self))
            {
              deletefile ($mgmt_config['abs_path_data']."workflow/".$site."/", $contentfile_self, 0);
            }
            
            // delete working container
            if (!empty ($contentfile_id))
            {
              $contentlocation = getcontentlocation ($contentfile_id, 'abs_path_content');
              
              $test_temp = deletefile ($contentlocation, $contentfile_self, 0);    
              
              // rename working container
              if ($test_temp != false) @rename ($contentlocation.$contentfile_self_wrk, $contentlocation.$contentfile_self);
              
              // delete link file
              if (is_file ($mgmt_config['abs_path_link'].$contentfile_id))
              {
                $test_temp = deletefile ($mgmt_config['abs_path_link'], $contentfile_id, 0);    
                
                if ($test_temp != false)
                {
                  // remote client
                  remoteclient ("delete", "abs_path_link", $site, $mgmt_config['abs_path_link'], "", $contentfile_id, "");            
                }
                else
                {
                  $errcode = "10119";
                  $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|deletefile failed for ".$mgmt_config['abs_path_link'].$contentfile_id;
                }
              }

              // load language code index file
              $langcode_array = getlanguageoptions ();
              
              // delete all VTT files of videos
              if ($langcode_array != false)
              {
                foreach ($langcode_array as $code => $language)
                {
                  if (is_file ($mgmt_config['abs_path_temp']."view/".$contentfile_id."_".trim($code).".vtt"))
                  {
                    deletefile ($mgmt_config['abs_path_temp']."view/", $contentfile_id."_".trim($code).".vtt", 0);
                  }
                }
              }

              // define media location
              $medialocation = getmedialocation ($site, $mediafile_self, "abs_path_media");
            
              // delete all content and media version files
              $dir_version = $contentlocation;

              if ($dir_version != false)
              {
                $handle_version = @dir ($dir_version);
                                 
                if ($handle_version != false)
                {
                  while ($entry = $handle_version->read())
                  {
                    if ($entry != "." && $entry != ".." && $contentfile_self != "" && (substr_count ($entry, $contentfile_self.".v_") == 1 || substr_count ($entry, "_hcm".$contentfile_id) == 1))
                    {
                      // container version
                      deletefile ($dir_version, $entry, 0);

                      // media file version
                      if (!empty ($mediafile_self) && !empty ($medialocation))
                      {
                        // media file version
                        deletefile ($medialocation.$site."/", $entry, 0);
       
                        // cloud storage
                        if (function_exists ("deletecloudobject")) deletecloudobject ($site, $medialocation.$site."/", $entry, $user);
                      
                        // thumbnail file version
                        $entry_info = getfileinfo ($site, $entry, $cat);
                        $entry_thumb = $entry_info['filename'].".thumb.jpg".strtolower (strrchr ($entry, "."));

                        deletefile ($medialocation.$site."/", $entry_thumb, 0);

                        // cloud storage
                        if (function_exists ("deletecloudobject")) deletecloudobject ($site, $medialocation.$site."/", $entry_thumb, $user);
                      }   
                    }
                  }
                  
                  $handle_version->close();
                }
              }
            }
          }
        }
        elseif ($allow_delete == false && ($filetype == "Page" || $filetype == "Component"))
        {
          // delete page file
          deletefile ($location, $page, 0); 
          
          // remote client
          remoteclient ("delete", "abs_path_".$cat, $site, $location, "", $page, "");           
          
          // thumbnail (for older versions before 5.0)
          $object_thumb = substr ($page, 0, strrpos ($page, ".")).".thumb".substr ($page, strrpos ($page, "."));
          
          if (is_file ($location.$object_thumb)) 
          {
            deletefile ($location, $object_thumb, 0); 
            // remote client
            remoteclient ("delete", "abs_path_".$cat, $site, $location, "", $object_thumb, "");
          }     
        }
        elseif ($filetype != "Page" && $filetype != "Component")
        {
          // relational DB connectivity
          if ($mgmt_config['db_connect_rdbms'] != "")
          {
            rdbms_deleteobject (convertpath ($site, $location.$page, $cat));                     
          } 
            
          // delete file
          deletefile ($location, $page, 0);
          
          // remote client
          remoteclient ("delete", "abs_path_".$cat, $site, $location, "", $page, "");           
          
          // thumbnail (for older versions before 5.0)
          $object_thumb = substr ($page, 0, strrpos ($page, ".")).".thumb".substr ($page, strrpos ($page, "."));
          
          if (is_file ($location.$object_thumb))
          {
            deletefile ($location, $object_thumb, 0);
            // remote client
            remoteclient ("delete", "abs_path_".$cat, $site, $location, "", $object_thumb, "");
          }
        }
        else $test = false;
  
        if ($test != false)
        {
          $add_onload = "if (opener.parent.frames['mainFrame']) {opener.parent.frames['controlFrame'].location='control_objectlist_menu.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."'; opener.parent.frames['mainFrame'].location.reload();} else if (opener.parent.frames['objFrame']) {opener.parent.frames['controlFrame'].location='control_content_menu.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&wf_token=".url_encode($wf_token)."'; opener.parent.frames['objFrame'].location='".$mgmt_config['url_path_cms']."empty.php';}";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-deleted'][$lang]."</span><br />\n";
  
          // log delete
          $errcode = "00111";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|$errcode|object ".$location_esc.$page." has been deleted by user '".$user."' (".getuserip().")";
  
          $page = "";
          $pagename = "";
          $filetype = "";          
          $error_switch = "no";
        }
        else
        {  
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-perform-action-due-to-missing-write-permission'][$lang]."</span><br />\n";
        }
      }
  
      // --------------------------------------- rename object -------------------------------------
      elseif ($show == "" && ($action == "page_rename" || $action == "file_rename"))
      {
        // rename object
        $test = @rename ($location.$page, $location.$pagenew); 

        if ($test != false)
        {
          // remote client
          remoteclient ("rename", "abs_path_".$cat, $site, $location, "", $page, $pagenew); 
          
          // if object is managed by the system
          if ($contentfile_self > 0)
          {
            // relational DB connectivity
            if ($mgmt_config['db_connect_rdbms'] != "")
            {
              rdbms_renameobject (convertpath ($site, $location.$page, $cat), convertpath ($site, $location.$pagenew, $cat));                     
            }
          
            // save new object name incl. special characters as file paramater
            $filedata = loadlockfile ($user, $location, $pagenew, 3);
            if ($filedata != "") $filedata = setfilename ($filedata, "name", $pagenew_orig);
            if ($filedata != "") $result = savelockfile ($user, $location, $pagenew, $filedata);
            else $result = false;
            
            if ($result == false)
            {
              $errcode = "10354";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|object name '".$pagenew_orig."' could not be saved for ".$location_esc.$pagenew;      
            }         
          
            // thumbnail (for support of versions before 5.0)
            $object_thumb = substr ($page, 0, strrpos ($page, ".")).".thumb".substr ($page, strrpos ($page, "."));
            $object_thumbnew = substr ($pagenew, 0, strrpos ($pagenew, ".")).".thumb".substr ($pagenew, strrpos ($pagenew, "."));
            
            if (is_file ($location.$object_thumb))
            {
              @rename ($location.$object_thumb, $location.$object_thumbnew);        
              // remote client
              remoteclient ("rename", "abs_path_".$cat, $site, $location, "", $object_thumb, $object_thumbnew);
            }
          }
        
          $pageold = $page;
          $pageoldname = $pagename;
          $page = $pagenew;
          $pagename = $pagenewname;
          $pagename_orig = $pagenewname_orig;
          
          $add_onload = "if (parent.frames['mainFrame']) parent.frames['mainFrame'].location.reload(); else if (parent.frames['objFrame']) parent.frames['objFrame'].location='page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."'; ";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-renamed'][$lang]."</span><br />\n";
          
          $error_switch = "no";
        }
        else
        {
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-perform-action-due-to-missing-write-permission'][$lang]."</span><br />\n";
          
          $errcode = "10201";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|rename failed for ".$location_esc.$page;
        }
      }
  
      // --------------------------------------- unpublish object -------------------------------------
      elseif ($show == "" && $action == "page_unpublish")
      {
        // read actual file info (to get associated content)
        $pagestore = loadfile ($location, $page);
        
        // get file names from template and content pointer
        $tpl_pointer = getfilename ($pagestore, "template");
        $cnt_pointer = getfilename ($pagestore, "content");

        // if object is a file and not a page or component
        if ($cnt_pointer != false && $tpl_pointer != false)
        {
          // remove all code except template and content pointer
          $pagestore = "<!-- hyperCMS:template file=\"".$tpl_pointer."\" -->\n<!-- hyperCMS:content file=\"".$cnt_pointer."\" -->\n";
          if ($namefile_self != "") $pagestore .= "<!-- hyperCMS:name file=\"".$namefile_self."\" -->\n";
  
          // save object file
          $test = savefile ($location, $page, $pagestore);
          
          // rename object file
          if ($test != false)
          {
            // remote client
            remoteclient ("save", "abs_path_".$cat, $site, $location, "", $page, "");  
           
            // rename object file
            $test = @rename ($location.$page, $location.$pagenew);
          
            // remote client
            if ($test != false) remoteclient ("rename", "abs_path_".$cat, $site, $location, "", $page, $pagenew);           
          }            
        }
        else $test = true;
  
        if ($test != false)
        {
          $pageold = $page;
          $pageoldname = $pagename;
          $page = $pagenew;
          $pagename = $pagenewname;
  
          $add_onload = "opener.parent.frames['controlFrame'].location.reload(); if (opener.parent.frames['mainFrame']) opener.parent.frames['mainFrame'].location.reload();";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-unpublished'][$lang]."</span><br />\n";
        
          $error_switch = "no";
        }
        else
        {
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-perform-action-due-to-missing-write-permission'][$lang]."</span><br />\n";
          
          $errcode = "10202";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|savefile or rename failed for ".$location.$page;            
        }
      }
  
      // ----------------------------------------- cut and paste object -------------------------------------
      elseif ($show == "" && $action == "page_paste" && $method == "cut")
      {
        // relational DB connectivity
        if ($mgmt_config['db_connect_rdbms'] != "")
        {
          rdbms_renameobject (convertpath ($site, $location_source.$page, $cat), convertpath ($site, $location.$page, $cat));                     
        }  
              
        $test = copy ($location_source.$page, $location.$page);

        if ($test == true)
        {
          // remote client
          remoteclient ("copy", "abs_path_".$cat, $site, $location_source, $location, $page, $page);
        }
        else
        {
          $errcode = "10203";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|copy failed for ".$location_esc.$page;           
        }
  
        if ($test == true)
        {
          $test = deletefile ($location_source, $page, 0);
          
          // notification
          notifyusers ($site, $location, $page, "onmove", $user); 
          
          // remote client
          if ($test == true)
          {
            remoteclient ("delete", "abs_path_".$cat, $site, $location_source, "", $page, "");  
                
            // thumbnail (for support of versions before 5.0)
            $object_thumb = substr ($page, 0, strrpos ($page, ".")).".thumb".substr ($page, strrpos ($page, "."));  
            
            if (is_file ($location_source.$object_thumb))
            {
              deletefile ($location_source, $object_thumb, 0);
              // remote client
              remoteclient ("delete", "abs_path_".$cat, $site, $location_source, "", $object_thumb, "");             
            }
          }
        }
        else
        {
          $errcode = "10204";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|deletefile failed for ".$location_source.$page;           
        }          
  
        if ($test != false)
        {
          $add_onload = "if (opener.parent.frames['mainFrame']) opener.parent.frames['mainFrame'].location.reload(); ";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-cut-and-pasted'][$lang]."</span><br />\n";
          
          $error_switch = "no";
        }
        else
        {
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-perform-action-due-to-missing-write-permission'][$lang]."</span><br />\n";           
        }
      }
      
      // --------------------------------------- connected copy and paste object -----------------------------------
      elseif ($show == "" && $action == "page_paste" && $method == "linkcopy")
      {
        // load link db
        $link_db = link_db_load ($site, $user);
        
        // add new object to link database
        if (is_array ($link_db) && sizeof ($link_db) >= 1)
        {
          $new_object = $location_esc.$pagename_sec;
          
          $link_db = link_db_update ($site, $link_db, "object", $contentfile_self, $cat, "", $new_object, "all"); 
              
          if ($link_db != false) $test = link_db_save ($site, $link_db, $user);  
          else $test = false;
        }
        // error loading link database
        elseif ($link_db == false)
        {
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['link-management-error'][$lang]."</span><br />\n".$hcms_lang['link-management-database-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";          
        }
        
        // update container and execute action if link database returned a valid result
        if ($link_db)
        {
          // load container from file system
          $bufferdata = loadcontainer ($contentfile_self_wrk, "work", $user);  
          
          // get current objects
          if ($bufferdata != false) $objects = getcontent ($bufferdata, "<contentobjects>");
  
          // insert new object into content container
          if ($bufferdata != false) $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentobjects>", $objects[0].$new_object."|", "", "");               
              
          if ($bufferdata != false) 
          {         
            // save working container 
            $test = savecontainer ($contentfile_self_wrk, "work", $bufferdata, $user);  
            
            // final container data
            $containerdata = $bufferdata;                   
          }
          else $test = false;         
          
          if ($test == false)
          {
            $errcode = "10276";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|savecontainer failed for container ".$contentfile_self;           
          }        
          
          if ($test != false)
          {
            // relational DB connectivity
            if ($mgmt_config['db_connect_rdbms'] != "")
            {
              rdbms_createobject ($contentfile_id, convertpath ($site, $location.$page_sec, $cat), $templatefile_self, "", "");                     
            }
                        
            // copy connected object
            $test = @copy ($location_source.$page, $location.$page_sec);          
            
            if ($test != false)
            {
              // remote client
              remoteclient ("copy", "abs_path_".$cat, $site, $location_source, $location, $page, $page_sec);  
            }
            else
            {
              $errcode = "10205";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|copy failed for ".$location_source.$page;           
            }              
    
            if ($test != false)
            {
              $add_onload = "if (opener.parent.frames['mainFrame']) opener.parent.frames['mainFrame'].location.reload(); ";
              $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-copied-and-pasted'][$lang]."</span><br />\n";              
              $page = $page_sec;
              
              $error_switch = "no";
            }
            else
            {
              $add_onload = "";
              $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-perform-action-due-to-missing-write-permission'][$lang]."</span><br />\n";
            }
          }
          else
          {
            $add_onload = "";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['link-management-error'][$lang]."</span><br />\n".$hcms_lang['an-error-occured-while-writing-data-to-the-link-management-database'][$lang]."\n";          
          }
        }           
      }      
  
      // ----------------------------------------- copy and paste object -------------------------------------
      elseif ($show == "" && $action == "page_paste" && $method == "copy")
      {
        if ($contentfile_self != "")
        {
          // load content count file and add new page
          $contentcount = loadlockfile ($user, $mgmt_config['abs_path_data'], "contentcount.dat", 5);
  
          if ($contentcount != "")
          {
            $contentcount = trim ($contentcount);
  
            // add 1 to content count
            $contentcount++;
  
            // write
            $test = savelockfile ($user, $mgmt_config['abs_path_data'], "contentcount.dat", $contentcount);
  
            if ($test == false) 
            {
              // unlock file
              unlockfile ($user, $mgmt_config['abs_path_data'], "contentcount.dat");     
                     
              exit ("severe error: contentcount save failure!");
            }
          }
          else
          {
            // unlock file
            unlockfile ($user, $mgmt_config['abs_path_data'], "contentcount.dat");
  
            exit ("severe error: contentcount empty!");
          }

          // create the name of the content container based on the unique content count value
          $contentcountlen = strlen ($contentcount);
          $zerolen = 7 - $contentcountlen;
          $zerostring =  "";
  
          for ($i = 1; $i <= $zerolen; $i++)
          {
            $zerostring = $zerostring."0";
          }
  
          $contentfile_new_id = $zerostring.$contentcount;
          $contentfile_new = $contentfile_new_id.".xml";          

          // create new container folder
          @mkdir (getcontentlocation ($contentfile_new_id, 'abs_path_content'), $mgmt_config['fspermission']);       
          
          // load container from file system
          $bufferdata = loadcontainer ($contentfile_self_wrk, "work", $user);
          
          // insert content container name
          if ($bufferdata != false) $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentcontainer>", $contentfile_new, "", "");
          
          // insert user into content container
          if ($bufferdata != false) $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentuser>", $user, "", "");
  
          // insert new date into content container
          if ($bufferdata != false) $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentdate>", $mgmt_config['today'], "", "");   
          
          // insert new object into content container
          if ($bufferdata != false) $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentobjects>", convertpath ($site, $location.$pagename_sec, $cat)."|", "", "");               
              
          if ($bufferdata != false) 
          {
            // save published container 
            $test = savecontainer ($contentfile_new_id, "published", $bufferdata, $user, true);

            // save working container 
            $test = savecontainer ($contentfile_new_id, "work", $bufferdata, $user, true);   

            // final container data
            $containerdata = $bufferdata;
          }
          else $test = false;
          
          if ($test == false)
          {
            $errcode = "10206";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|savecontainer failed for container ".$contentfile_new;           
          }
        }
        elseif ($filetype != "cms")
        {
          $test = true;
        }
        else $test = false;
  
        // add record in link management database and copy object
        if ($test != false && $contentfile_self != "")
        {
          // insert new record into link management file
          $link_db = link_db_load ($site, $user);
          
          if (is_array ($link_db) && sizeof ($link_db) > 0)
          {               
            $link_db = link_db_insert ($site, $link_db, $contentfile_new, $cat, $location.$pagename_sec); 
    
            if ($link_db != false) $link_db[$contentfile_new]['link'] = $link_db[$contentfile_self]['link'];          
            
            if ($link_db != false) $test = link_db_save ($site, $link_db, $user);
            else $test = false;
  
            if ($test == false)
            {
              // unlock file
              link_db_close ($site, $user);
  
              $add_onload = "";
              $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-write-link-management-data'][$lang]."</span><br />".$hcms_lang['link-management-database-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
            }
          }
          // error loading link database
          elseif ($link_db == false)
          {
            // unlock file
            link_db_close ($site, $user);
  
            $add_onload = "";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['link-management-error'][$lang]."</span><br />".$hcms_lang['new-record-for-link-management-is-missing'][$lang]."\n";
          }
          
          // update container and execute action if link database returned a valid result
          if ($link_db)
          {        
            // set new media file name
            if ($mediafile_self != false) $mediafile_new = substr ($mediafile_self, 0, strpos ($mediafile_self, "_hcm"))."_hcm".$contentfile_new_id.strtolower (strrchr ($mediafile_self, "."));              
          
            // update content file reference, set content container pointer
            $pagedata = setfilename ($pagedata, "content", $contentfile_new);
            if ($mediafile_self != false && $mediafile_new != "") $pagedata = setfilename ($pagedata, "media", $mediafile_new);  

            // relational DB connectivity
            if ($mgmt_config['db_connect_rdbms'] != "")
            {
              // extract text content
              $textnode = null;
              $textnode = getcontent ($containerdata, "<text>");
              
              if (is_array ($textnode) && sizeof ($textnode) > 0)
              {
                foreach ($textnode as $text)
                {
                  $text_id = getcontent ($text, "<text_id>");
                  $id = $text_id[0];
                  $textcontent = getcontent ($text, "<textcontent>");
                  if ($id != "" && $textcontent[0] != "") $text_array[$id] = $textcontent[0];
                }
              }
              
              // extract media content
              $textnode = null;
              $textnode = getcontent ($containerdata, "<multimedia>");
              
              if (is_array ($textnode) && sizeof ($textnode) > 0)
              {
                foreach ($textnode as $text)
                {
                  $text_id = getcontent ($text, "<file>");
                  $id = $text_id[0];
                  $textcontent = getcontent ($text, "<content>");
                  if ($id != "" && $textcontent[0] != "") $text_array[$id] = $textcontent[0];
                }
              }                

              // relational DB connectivity
              if ($mgmt_config['db_connect_rdbms'] != "")
              {
                rdbms_createobject ($contentfile_new_id, convertpath ($site, $location.$page_sec, $cat), $templatefile_self, $contentfile_new, $user);
                if (isset ($text_array) && is_array ($text_array) && sizeof ($text_array) > 0) rdbms_setcontent ($site, $contentfile_new_id, $text_array, $user);
              }           
            }                
            
            // save object
            if ($pagedata != false)
            {
              $test = savefile ($location, $page_sec, $pagedata);
              // remote client
              remoteclient ("save", "abs_path_".$cat, $site, $location, "", $page_sec, "");                  
            }
            
            // copy media files
            if ($mediafile_self != false && $mediafile_new != "")
            {
              @copy (getmedialocation ($site, $mediafile_self, "abs_path_media").$site."/".$mediafile_self, getmedialocation ($site, $mediafile_new, "abs_path_media").$site."/".$mediafile_new);
              $mediafile_self_thumb = substr ($mediafile_self, 0, strrpos ($mediafile_self, ".")).".thumb.jpg";
              $mediafile_new_thumb = substr ($mediafile_new, 0, strrpos ($mediafile_new, ".")).".thumb.jpg";
              
              // copy/write media information to DB
              if ($contentcount != "" && $contentfile_id != "")
              {
                $rdbms_media = rdbms_getmedia (intval ($contentfile_id));
                if (is_array ($rdbms_media)) rdbms_setmedia ($contentcount, $rdbms_media['filesize'], $rdbms_media['filetype'], $rdbms_media['width'], $rdbms_media['height'], $rdbms_media['red'], $rdbms_media['green'], $rdbms_media['blue'], $rdbms_media['colorkey'], $rdbms_media['imagetype'], $rdbms_media['md5_hash']);
              }
              
              // remote client
              remoteclient ("copy", "abs_path_media", $site, getmedialocation ($site, $mediafile_self, "abs_path_media").$site."/", "", $mediafile_self, $mediafile_new);                 
              
              // copy thumbnail images
              if (is_file (getmedialocation ($site, $mediafile_self_thumb, "abs_path_media").$site."/".$mediafile_self_thumb))
              {
                @copy (getmedialocation ($site, $mediafile_self_thumb, "abs_path_media").$site."/".$mediafile_self_thumb, getmedialocation ($site, $mediafile_new_thumb, "abs_path_media").$site."/".$mediafile_new_thumb);
                
                // remote client
                remoteclient ("copy", "abs_path_media", $site, getmedialocation ($site, $mediafile_self_thumb, "abs_path_media").$site."/", "", $mediafile_self_thumb, $mediafile_new_thumb);                    
              }
              // copy thumbnail videos and video configs
              else
              {
                $mediafile_self_name = substr ($mediafile_self, 0, strrpos ($mediafile_self, "."));
                $mediafile_new_name = substr ($mediafile_new, 0, strrpos ($mediafile_new, "."));
                
                // all video formats
                if (is_array ($mgmt_mediaoptions))
                {
                  foreach ($mgmt_mediaoptions as $video_ext => $value)
                  {
                    if ($video_ext != "" && $video_ext != "thumbnail-video" && $video_ext != "thumbnail-audio")
                    {
                      // thumbnail video files
                      $mediafile_self_thumb = $mediafile_self_name.".thumb".$video_ext;
                      $mediafile_new_thumb = $mediafile_new_name.".thumb".$video_ext;
  
                      if (is_file (getmedialocation ($site, $mediafile_self_thumb, "abs_path_media").$site."/".$mediafile_self_thumb))
                      {
                        @copy (getmedialocation ($site, $mediafile_self_thumb, "abs_path_media").$site."/".$mediafile_self_thumb, getmedialocation ($site, $mediafile_new_thumb, "abs_path_media").$site."/".$mediafile_new_thumb);
                        
                        // remote client
                        remoteclient ("copy", "abs_path_media", $site, getmedialocation ($site, $mediafile_self_thumb, "abs_path_media").$site."/", "", $mediafile_self_thumb, $mediafile_new_thumb);                     
                      }
                      
                      // individiual video files
                      $mediafile_self_video = $mediafile_self_name.".video".$video_ext;
                      $mediafile_new_video = $mediafile_new_name.".video".$video_ext;
                      
                      if (is_file (getmedialocation ($site, $mediafile_self_video, "abs_path_media").$site."/".$mediafile_self_video))
                      {
                        @copy (getmedialocation ($site, $mediafile_self_video, "abs_path_media").$site."/".$mediafile_self_video, getmedialocation ($site, $mediafile_new_video, "abs_path_media").$site."/".$mediafile_new_video);
                        
                        // remote client
                        remoteclient ("copy", "abs_path_media", $site, getmedialocation ($site, $mediafile_self_video, "abs_path_media").$site."/", "", $mediafile_self_video, $mediafile_new_video);                     
                      }
                      
                      // individiual video config files
                      $mediafile_self_video = $mediafile_self_name.".config".$video_ext;
                      $mediafile_new_video = $mediafile_new_name.".config".$video_ext;
                      
                      if (is_file (getmedialocation ($site, $mediafile_self_video, "abs_path_media").$site."/".$mediafile_self_video))
                      {
                        @copy (getmedialocation ($site, $mediafile_self_video, "abs_path_media").$site."/".$mediafile_self_video, getmedialocation ($site, $mediafile_new_video, "abs_path_media").$site."/".$mediafile_new_video);
                        
                        // remote client
                        remoteclient ("copy", "abs_path_media", $site, getmedialocation ($site, $mediafile_self_video, "abs_path_media").$site."/", "", $mediafile_self_video, $mediafile_new_video);                     
                      }
                    }
                  }
                }
                
                // video player config for thumbnail videos
                $mediafile_self_thumb = $mediafile_self_name.".config.video";
                $mediafile_new_thumb = $mediafile_new_name.".config.video";
                
                if (is_file (getmedialocation ($site, $mediafile_self_thumb, "abs_path_media").$site."/".$mediafile_self_thumb))
                {
                  @copy (getmedialocation ($site, $mediafile_self_thumb, "abs_path_media").$site."/".$mediafile_self_thumb, getmedialocation ($site, $mediafile_new_thumb, "abs_path_media").$site."/".$mediafile_new_thumb);
                  
                  // remote client
                  remoteclient ("copy", "abs_path_media", $site, getmedialocation ($site, $mediafile_self_thumb, "abs_path_media").$site."/", "", $mediafile_self_thumb, $mediafile_new_thumb);                     
                }
              }
            }                 

            if ($test != false)
            {
              $add_onload = "if (opener.parent.frames['mainFrame']) opener.parent.frames['mainFrame'].location.reload(); ";
              $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-copied-and-pasted'][$lang]."</span><br />\n";
  
              $page = $page_sec;
              $pagename = $pagename_sec;
              
              $error_switch = "no";
            }
            else
            {
              $add_onload = "";
              $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-perform-action-due-to-missing-write-permission'][$lang]."</span><br />\n";

              $errcode = "10208";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|savefile failed for ".$location_esc.$page_sec;                             
            }
          }  
        }
        // copy file that is not managed by hyperCMS
        elseif ($test != false && $contentfile_self == "")
        {           
          // copy object
          $test = @copy ($location_source.$page, $location.$page_sec);

          if ($test != false)
          {
            // remote client
            remoteclient ("copy", "abs_path_".$cat, $site, $location_source, $location, $page, $page_sec);             
          
            $add_onload = "if (opener.parent.frames['mainFrame']) opener.parent.frames['mainFrame'].location.reload(); ";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-copied-and-pasted'][$lang]."</span><br />\n";
            
            $page = $page_sec;
            
            $error_switch = "no";
          }
          else
          {
            $add_onload = "";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-perform-action-due-to-missing-write-permission'][$lang]."</span><br />\n";    
            
            $errcode = "10207";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|copy failed for ".$location_source.$page;                      
          }
        }
      }
    }  
      
    // save log
    savelog (@$error);
    
    // eventsystem for paste
    if ($action == "page_paste" && $eventsystem['onpasteobject_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0) && $error_switch == "no") 
      onpasteobject_post ($site, $cat, $location_source, $location, $page, $user);
  }
  else $error_switch = "yes";      
        
  // return results
  $result = array();
  
  if (isset ($error_switch) && $error_switch == "no") $result['result'] = true;
  else $result['result'] = false;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  $result['publication'] = $site;
  $result['location'] = $location;
  $result['object'] = $page;
  if (!empty ($pagename_orig)) $result['name'] = $pagename_orig;
  else $result['name'] = false;
  if (!empty ($filetype)) $result['objecttype'] = $filetype;
  else $result['objecttype'] = false;
  if (!empty ($contentfile_self)) $result['container'] = $contentfile_self;
  else $result['container'] = false;
  if (!empty ($containerdata)) $result['containerdata'] = $containerdata;
  else $result['containerdata'] = false;

  return $result;
}

// ---------------------------------------- deleteobject --------------------------------------------
// function: deleteobject()
// input: site, location, object
// output: array

// description:
// This function removes page or component and calls the function manipulateobject

function deleteobject ($site, $location, $page, $user)
{      
  global $eventsystem, $mgmt_config, $cat, $hcms_lang, $lang;

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($user))
  {
    // publication management config
    if (!is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
      
    if (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1)
      $location = deconvertpath ($location, "file");
        
    // eventsystem
    if ($eventsystem['ondeleteobject_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
      ondeleteobject_pre ($site, $cat, $location, $page, $user);
           
    $result = manipulateobject ($site, $location, $page, "", $user, "page_delete");
    
    // notification
    notifyusers ($site, $location, $page, "ondelete", $user);  

    // eventsystem
    if ($eventsystem['ondeleteobject_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0) && $result['result'] == true) 
      ondeleteobject_post ($site, $cat, $location, $page, $user);

    // return results 
    return $result;
  }
  else return $result['result'] = false; 
}

// ---------------------------------------- renameobject --------------------------------------------
// function: renameobject()
// input: site, location, object, new object name exkl. file extension, user
// output: array

// description:
// This function renames a page or component and calls the function manipulateobject

function renameobject ($site, $location, $page, $pagenew, $user)
{      
  global $eventsystem, $mgmt_config, $cat, $hcms_lang, $lang;
  
  if (!is_int ($mgmt_config['max_digits_filename'])) $mgmt_config['max_digits_filename'] = 200;
  
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($pagenew) && strlen ($pagenew) <= $mgmt_config['max_digits_filename'] && valid_objectname ($user))
  { 
    // publication management config
    if (!is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php"); 
       
    if (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1)
      $location = deconvertpath ($location, "file");
      
    // eventsystem
    if ($eventsystem['onrenameobject_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
      onrenameobject_pre ($site, $cat, $location, $page, $pagenew, $user);  
      
    // trim object name
    $pagenew = trim ($pagenew);  
     
    $result = manipulateobject ($site, $location, $page, $pagenew, $user, "page_rename");    
    
    // eventsystem
    if ($eventsystem['onrenameobject_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0) && $result['result'] == true) 
      onrenameobject_post ($site, $cat, $location, $page, $pagenew, $user);       
           
    // return results 
    return $result;
  }
  else return $result['result'] = false;      
}

// ---------------------------------------- renamefile --------------------------------------------
// function: renamefile()
// input: publication name, location, object, new object including file extension, user name
// output: array

// description:
// This function renames a file (NOT a page or component) and calls the function manipulateobject. 
// This function renames the file name including the extension and not only the name of an object.
// The event that will be executed in the event system is the same as renameobject.

function renamefile ($site, $location, $page, $pagenew, $user)
{      
  global $eventsystem, $mgmt_config, $cat, $hcms_lang, $lang;

  if (!is_int ($mgmt_config['max_digits_filename'])) $mgmt_config['max_digits_filename'] = 200;
  
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($pagenew) && strlen ($pagenew) <= $mgmt_config['max_digits_filename'] && valid_objectname ($user))
  {    
      // publication management config
    if (!is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php"); 
    
    if (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1)
      $location = deconvertpath ($location, "file");
        
    // eventsystem
    if ($eventsystem['onrenameobject_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
      onrenameobject_pre ($site, $cat, $location, $page, $pagenew, $user);             

    $result = manipulateobject ($site, $location, $page, $pagenew, $user, "file_rename");    
    
    // eventsystem
    if ($eventsystem['onrenameobject_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0) && $result['result'] == true) 
      onrenameobject_post ($site, $cat, $location, $page, $pagenew, $user);       
           
    // return results 
    return $result;
  }
  else return $result['result'] = false;    
}

// ---------------------------------------- cutobject --------------------------------------------
// function: cutobject()
// input: publication name[string], location[string], object[string], user[string], add to clipboard to save more entries (optional)
// output: array

// description:
// This function cuts a page or component

function cutobject ($site, $location, $page, $user, $clipboard_add=false)
{      
  global $eventsystem, $mgmt_config, $cat, $temp_clipboard, $hcms_lang, $lang;
         
  $add_onload = "";
  $show = "";
  $filetype = ""; 
  $clipboard = "";
  
  // set default language
  if ($lang == "") $lang = "en";

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($user))
  { 
    // get clipboard entries
    if ($clipboard_add == true)
    {
      if (isset ($_SESSION['hcms_temp_clipboard'])) $clipboard = $_SESSION['hcms_temp_clipboard'];
      elseif (isset ($temp_clipboard)) $clipboard = $temp_clipboard;
      else $clipboard = "";
    }
    else $clipboard = "";
    
    // publication management config
    if (!is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php"); 
    
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";          
    
    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location);
        
    // convert location
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, $cat);

    // eventsystem
    if ($eventsystem['oncutobject_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
      oncutobject_pre ($site, $cat, $location, $page, $user);
    
    // correct file or folder
    if (!file_exists ($location.$page)) $page = correctfile ($location, $page, $user);
    
    if ($page != false)
    {
      // get file info
      $fileinfo = getfileinfo ($site, $location.$page, $cat);    
      $pagename = $fileinfo['name'];
      $filetype = $fileinfo['type'];
        
      // define clipboard entry
      $clipboard = $clipboard."cut|$site|$cat|$location_esc|$page|$pagename|$filetype\n";
    }
    
    // save clipboard
    if (isset ($clipboard) && $clipboard != "")
    {    
      // add entries to clipboard
      $_SESSION['hcms_temp_clipboard'] = $clipboard;

      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['objects-are-copied-to-clipboard'][$lang]."</span><br />";
        
      $error_switch = "no";    
    }
    else
    {
      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-selected-item-does-not-exist'][$lang]."</span><br />\n";  
    }
    
    // eventsystem
    if ($eventsystem['oncutobject_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0) && $error_switch == "no") 
      oncutobject_post ($site, $cat, $location, $page, $user);
  }
  else $error_switch = "yes";      
         
  // return results
  $result = array();
  
  if (isset ($error_switch) && $error_switch == "no") $result['result'] = true;
  else $result['result'] = false;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  $result['object'] = $page;
  $result['objecttype'] = $filetype;  
  $result['clipboard'] = $clipboard; 
  
  return $result;
}

// ---------------------------------------- copyobject --------------------------------------------
// function: copyobject()
// input: publication name[string], location[string], object[string], user[string], add to clipboard to save more entries (optional)
// output: array

// description:
// This function copies a page or component

function copyobject ($site, $location, $page, $user, $clipboard_add=false)
{      
  global $eventsystem, $mgmt_config, $cat, $temp_clipboard, $hcms_lang, $lang;
 
  $add_onload = "";
  $show = "";
  $filetype = "";
  $clipboard = "";
  
  // set default language
  if ($lang == "") $lang = "en";
  
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($user))
  {
    // get clipboard entries
    if ($clipboard_add == true)
    {
      if (isset ($_SESSION['hcms_temp_clipboard'])) $clipboard = $_SESSION['hcms_temp_clipboard'];
      elseif (isset ($temp_clipboard)) $clipboard = $temp_clipboard;
      else $clipboard = "";
    }
    else $clipboard = "";
    
    // publication management config
    if (!is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";           
    
    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location);    
    
    // convert location
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, $cat);
    
    // check location (only components of given publication are allowed)
    if (substr_count ($location, $mgmt_config['abs_path_rep']) == 0 || substr_count ($location, $mgmt_config['abs_path_comp'].$site."/") > 0)
    {
      // eventsystem
      if ($eventsystem['oncopyobject_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
        oncopyobject_pre ($site, $cat, $location, $page, $user);    
  
      // correct file or folder
      if (!file_exists ($location.$page)) $page = correctfile ($location, $page, $user);
      
      if ($page != false)
      {
        // get file info
        $fileinfo = getfileinfo ($site, $location.$page, $cat);    
        $pagename = $fileinfo['name'];
        $filetype = $fileinfo['type'];
          
        // define new clipboard entry
        $clipboard = $clipboard."copy|$site|$cat|$location_esc|$page|$pagename|$filetype\n";
      }
  
      // save clipboard
      if (isset ($clipboard) && $clipboard != "")
      {    
        // add entries to clipboard
        $_SESSION['hcms_temp_clipboard'] = $clipboard;

        $add_onload = "";
        $show = "<span class=hcmsHeadline>".$hcms_lang['objects-are-copied-to-clipboard'][$lang]."</span><br />";
          
        $error_switch = "no";   
      }
      else
      {
        $add_onload = "";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-selected-item-does-not-exist'][$lang]."</span><br />\n";  
      }  
          
      // eventsystem
      if ($eventsystem['oncopyobject_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0) && $error_switch == "no") 
        oncopyobject_post ($site, $cat, $location, $page, $user);
    }
    else $error_switch = "yes";
  }
  else $error_switch = "yes";
         
  // return results
  $result = array();
  
  if (isset ($error_switch) && $error_switch == "no") $result['result'] = true;
  else $result['result'] = false;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  $result['object'] = $page;
  $result['objecttype'] = $filetype;   
  $result['clipboard'] = $clipboard;
  
  return $result;
}    

// ---------------------------------------- copyconnectedobject --------------------------------------------
// function: copyconnectedobject()
// input: publication name[string], location[string], object[string], user[string], add to clipboard to save more entries (optional)
// output: array

// description:
// This function makes a connected copy of a page or component

function copyconnectedobject ($site, $location, $page, $user, $clipboard_add=false)
{      
  global $eventsystem, $mgmt_config, $cat, $temp_clipboard, $hcms_lang, $lang;  

  $add_onload = "";
  $show = "";
  $filetype = "";
  $clipboard = "";
  
  // set default language
  if ($lang == "") $lang = "en";
  
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($user))
  {
    // get clipboard entries
    if ($clipboard_add == true)
    {
      if (isset ($_SESSION['hcms_temp_clipboard'])) $clipboard = $_SESSION['hcms_temp_clipboard'];
      elseif (isset ($temp_clipboard)) $clipboard = $temp_clipboard;
      else $clipboard = "";
    }
    else $clipboard = "";
    
    // publication management config
    if (!is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");    
    
    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location);
    
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";
    
    // convert location
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, $cat); 
    
    // eventsystem
    if ($eventsystem['oncopyconnectedobject_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
      oncopyconnectedobject_pre ($site, $cat, $location, $page, $user);
        
    // check location (only components of given publication are allowed)
    if (substr_count ($location, $mgmt_config['abs_path_rep']) == 0 || substr_count ($location, $mgmt_config['abs_path_comp'].$site."/") > 0)
    {
      // correct file or folder
      if (!file_exists ($location.$page)) $page = correctfile ($location, $page, $user);
      
      if ($page != false)
      {
        // get file info
        $fileinfo = getfileinfo ($site, $location.$page, $cat);    
        $pagename = $fileinfo['name'];
        $filetype = $fileinfo['type'];
          
        // define clipboard entry  
        $clipboard = $clipboard."linkcopy|$site|$cat|$location_esc|$page|$pagename|$filetype\n";
      }
  
      // save clipboard
      if (isset ($clipboard) && $clipboard != "")
      {    
        // add entries to clipboard
        $_SESSION['hcms_temp_clipboard'] = $clipboard;

        $add_onload = "";
        $show = "<span class=hcmsHeadline>".$hcms_lang['objects-are-copied-to-clipboard'][$lang]."</span><br />";
          
        $error_switch = "no";     
      }
      else
      {
        $add_onload = "";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-selected-item-does-not-exist'][$lang]."</span><br />\n";  
      }
      
      // eventsystem
      if ($eventsystem['oncopyconnectedobject_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0) && $error_switch == "no") 
        oncopyconnectedobject_post ($site, $cat, $location, $page, $user);
    }
    else $error_switch = "yes";
  }
  else $error_switch = "yes";
           
  // return results
  $result = array();
  
  if (isset ($error_switch) && $error_switch == "no") $result['result'] = true;
  else $result['result'] = false;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  $result['object'] = $page;
  $result['objecttype'] = $filetype;  
  $result['clipboard'] = $clipboard; 
  
  return $result;
} 

// ---------------------------------------- pasteobject --------------------------------------------
// function: pasteobject()
// input: publication name[string], location[string], user[string]
// output: array

// description:
// This function pastes a page or component and calls the function manipulateobject

function pasteobject ($site, $location, $user)
{      
  global $eventsystem, $mgmt_config, $cat, $temp_clipboard, $hcms_lang, $lang;
  
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($user))
  {  
    // publication management config
    if (!is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php"); 
    
    if (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1)
      $location = deconvertpath ($location, "file");

    // check location (only components of given publication are allowed)
    if (substr_count ($location, $mgmt_config['abs_path_rep']) == 0 || substr_count ($location, $mgmt_config['abs_path_comp'].$site."/") > 0)
    {
      $result = manipulateobject ($site, $location, "", "", $user, "page_paste");
    }
    else
    {
      $result['result'] = false;
    }
  
    // eventsystem will be executed in manipulateobject to get access to the pasted object 
           
    // return results 
    return $result;
  }
  else return $result['result'] = false;    
}

// ---------------------------------------- lockobject --------------------------------------------
// function: lockobject()
// input: site, location, object, user
// output: array

// description:
// This function locks a page, component or asset

function lockobject ($site, $location, $page, $user)
{      
  global $eventsystem, $mgmt_config, $cat, $temp_clipboard, $hcms_lang, $lang;
         
  $add_onload = "";
  $show = "";
  $pagename = "";
  $usedby = "";
  $filetype = "";
  
  // set default language
  if ($lang == "") $lang = "en";

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($user))
  {
    $dir = $mgmt_config['abs_path_data']."checkout/";
    $file = $user.".dat";
    
    // publication management config
    if (!is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");     
    
    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location);
    
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";  
    
    if (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1)
      $location = deconvertpath ($location, "file");    
    
    // define object file name
    if (is_dir ($location.$page))
    {
      $location = $location.$page."/";
      $page = ".folder";
      $fileinfo = getfileinfo ($site, $location.$page, $cat);
      $filetype = $fileinfo['type'];   
      $pagename = specialchr_decode (objectname ($location)); 
    }
    else
    {
      $page = correctfile ($location, $page, $user);
      $fileinfo = getfileinfo ($site, $location.$page, $cat);
      $filetype = $fileinfo['type'];   
      $pagename = specialchr_decode ($fileinfo['name']); 
    }
    
    // eventsystem
    if ($eventsystem['onlockobject_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
      onlockobject_pre ($site, $cat, $location, $page, $user);       
    
    // collect object info
    // load page file
    $pagedata = loadfile ($location, $page);
    
    if ($pagedata != "")
    {      
      // get name of content file
      $container = getfilename ($pagedata, "content");
      $container_id = substr ($container, 0, strpos ($container, ".xml")); 
      
      // define variables depending on content category
      $object = $site."|".$cat."|".$container."\n";

      if ($container_id != "")
      {
        // lock content container
        if (!is_file (getcontentlocation ($container_id, 'abs_path_content').$container.".wrk.@".$user))
        {
          $test = lockfile ($user, getcontentlocation ($container_id, 'abs_path_content'), $container.".wrk");
          
          // add new checked out object to list
          if ($test == true)
          {
            if (is_file ($dir.$file))
            {
              $test = appendfile ($dir, $file, $object);
            }
            else
            {
              $test = savefile ($dir, $file, $object);
            }
          } 
        }
        else $test = true;
      }
      else $test = false;
      
      if ($test != false)
      {
        $usedby = $user;
      
        $add_onload = "parent.frames['objFrame'].location.reload();";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-is-checked-out'][$lang]."</span><br />";
        
        $usedby = $user;
        $error_switch = "no";
      }
      else
      { 
        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['object-could-not-be-checked-out'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
      }         
    
      // eventsystem
      if ($eventsystem['onlockobject_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0) && $error_switch == "no") 
        onlockobject_post ($site, $cat, $location, $page, $user);    
    }
    else $error_switch = "yes";  
  }
  else $error_switch = "yes";  
  
  // return results
  $result = array();
  
  if (isset ($error_switch) && $error_switch == "no") $result['result'] = true;
  else $result['result'] = false;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  $result['object'] = $page;
  $result['name'] = $pagename;
  $result['usedby'] = $usedby;
  $result['objecttype'] = $filetype;   
  
  return $result;
} 

// ---------------------------------------- getlockobjects --------------------------------------------
// function: getlockobjects()
// input: user name
// output: object path array / false

function getlockedobjects ($user)
{      
  global $mgmt_config;
  
  if (valid_objectname ($user))
  {
    $dir = $mgmt_config['abs_path_data']."checkout/";
    $file = $user.".dat";
    
    $save = false;
    
    // get checked out objects of user
    $data = loadfile_fast ($dir, $file);
    
    if ($data != "")
    {
      $checkedout_array = explode ("\n", $data);
      
      if (is_array ($checkedout_array))
      {
        $object_array = array();
        
        foreach ($checkedout_array as $checkedout_rec)
        {
          if (substr_count ($checkedout_rec, "|") > 0)
          {
            // get container name            
            list ($site, $cat, $container) = explode ("|", trim ($checkedout_rec));
  
            // if no corresponding siteaccess for this user
            if (!checkpublicationpermission ($site))
            {
              // get container id
              $container_id = substr ($container, 0, strpos ($container, ".xml"));
      
              // check-in content container
              $test = unlockfile ($user, getcontentlocation ($container_id, 'abs_path_content'), $container.".wrk");
            
              // remove entry from list
              if ($test == true)
              {
                $data = str_replace ($checkedout_rec."\n", "", $data);              
                $save = true;
              }
            }
            // user has access
            else
            {
              // find corresponding objects in link management database
              $result_array = getconnectedobject ($container);
              
              if ($result_array != false)
              {  
                foreach ($result_array as $result)
                {
                  $location = $result['convertedlocation'];
                  $page = $result['object'];
                  $page = correctfile ($location, $page, $user);
                  
                  // check if file exists
                  if ($page != false)
                  {
                    $object_array[] = $location.$page;
                  }
                }
              }
            }
          }
        }
        
        // update checked out list if necessary
        if ($save)
        {
          savefile ($dir, $file, $data);
        }
        
        if (sizeof ($object_array) > 0)
        {
          natcasesort ($object_array);
          return $object_array;
        }
        else return false;
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// ---------------------------------------- unlockobject --------------------------------------------
// function: unlockobject()
// input: site, location, object, user
// output: array

// description:
// This function unlocks a page or component

function unlockobject ($site, $location, $page, $user)
{      
  global $eventsystem, $mgmt_config, $cat, $temp_clipboard, $hcms_lang, $lang;
         
  $add_onload = "";
  $show = "";
  $pagename = "";
  $usedby = "";
  $filetype = "";
  
  // set default language
  if ($lang == "") $lang = "en";

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($user))
  {
    $dir = $mgmt_config['abs_path_data']."checkout/";
    $file = $user.".dat";
      
    // publication management config
    if (!is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");     
    
    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location);  
    
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";
    
    if (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1)
      $location = deconvertpath ($location, "file");    
    
    // define object file name
    if (is_dir ($location.$page))
    {
      $location = $location.$page."/";
      $page = ".folder";
      $fileinfo = getfileinfo ($site, $location.$page, $cat);
      $filetype = $fileinfo['type'];   
      $pagename = specialchr_decode (objectname ($location)); 
    }
    else
    {
      $page = correctfile ($location, $page, $user);
      $fileinfo = getfileinfo ($site, $location.$page, $cat);
      $filetype = $fileinfo['type'];   
      $pagename = specialchr_decode ($fileinfo['name']); 
    }
    
    // eventsystem
    if ($eventsystem['onunlockobject_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
      onunlockobject_pre ($site, $cat, $location, $page, $user);    
    
    // collect object info
    // load page file
    $pagedata = loadfile ($location, $page);
    
    if ($pagedata != "")
    {
      // get name of content file
      $container = getfilename ($pagedata, "content");
      $container_id = substr ($container, 0, strpos ($container, ".xml"));
      
      // define variables depending on object category
      $unlock_entry = $site."|".$cat."|".$container."\n";
      
      // unlock content container
      if ($container_id != "")
      {
        $test = unlockfile ($user, getcontentlocation ($container_id, 'abs_path_content'), $container.".wrk");
        
        // add new checked out object to list
        if ($test && is_file ($dir.$file))
        {
          $checkout_data = loadfile ($dir, $file);
          
          $checkout_data = str_replace ($unlock_entry, "", $checkout_data);
          
          $test = savefile ($dir, $file, $checkout_data);
        }
        else $test = true;      
      }
      else $test = false;
      
      if ($test != false)
      {
        $add_onload = "if (opener.parent.frames['mainFrame']) opener.parent.frames['mainFrame'].location.reload();
if (parent.frames['objFrame']) parent.frames['objFrame'].location.reload();
if (parent.frames['mainFrame']) parent.frames['mainFrame'].location.reload();";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-is-checked-in'][$lang]."</span><br />";
        
        $usedby = "";
        $error_switch = "no";
      }
      else
      {  
        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['object-could-not-be-checked-out'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
      } 
      
      // eventsystem
      if ($eventsystem['onunlockobject_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0) && $error_switch == "no") 
        onunlockobject_post ($site, $cat, $location, $page, $user);
    }
    else $error_switch = "yes";
  }
  else $error_switch = "yes";
           
  // return results
  $result = array();
  
  if (isset ($error_switch) && $error_switch == "no") $result['result'] = true;
  else $result['result'] = false;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  $result['object'] = $page;
  $result['name'] = $pagename;
  $result['usedby'] = $usedby;
  $result['objecttype'] = $filetype;   
  
  return $result;
}     

// ---------------------------------------- publishobject --------------------------------------------
// function: publishobject()
// input: site, location, object (full name incl. extension)
// output: array

// description:
// This function publishes a page, component or asset

function publishobject ($site, $location, $page, $user)
{      
  global $eventsystem, $mgmt_config, $cat, $ctrlreload, $hcms_lang, $lang;
         
  $buffer_site = "";
  $buffer_location = "";
  $buffer_page = "";
  $show = "";
  $add_onload = "";
  $release = false;
  $contentdata = "";
  $container_id = "";
  $media = "";
  $template = "";
  $application = "";
  $result_save = false;
  
  // set default language
  if ($lang == "") $lang = "en";

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($user))
  {
    // load template engine (it is not included by API and needs to be loaded seperately!)
    require_once ($mgmt_config['abs_path_cms']."function/hypercms_tplengine.inc.php");
  
    // publication management config
    if (!is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php"); 
      
    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location);        
    
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";
    
    if (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1)
      $location = deconvertpath ($location, "file");
      
    // check location (only components of given publication are allowed)
    if (substr_count ($location, $mgmt_config['abs_path_rep']) == 0 || substr_count ($location, $mgmt_config['abs_path_comp'].$site."/") > 0)
    {
      // define object file name
      $page = correctfile ($location, $page, $user);
      
      // buffer input variables
      $buffer_site = $site;
      $buffer_location = $location;
      $buffer_page = $page;
      
      // get all connected objects
      $pagedata = loadfile ($location, $page);
      $container = getfilename ($pagedata, "content");
      $container_id = substr ($container, 0, strpos ($container, ".xml"));
      $template = getfilename ($pagedata, "template");
      $media = getfilename ($pagedata, "media");
      $application = "";
  
      if ($template != "")
      {
        $result = loadtemplate ($site, $template);
        
        if (is_array ($result))
        {
          $bufferdata = getcontent ($result['content'], "<application>");
          $application = $bufferdata[0];
        }
      }

      // if object is not a multimedia file or folder (test if .folder and directory) and 
      if ($container != false && $template != false && $page != ".folder" && !is_dir ($location.$page) && $application != "") 
      {
        // get connected objects 
        $object_array = getconnectedobject ($container);

        // no object reference was found in container (object might have been deleted and restored)
        if ($object_array == false)
        {    
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
          ".$hcms_lang['information-about-connected-items-of-the-container-is-missing'][$lang]."\n";
      
          $errcode = "20877";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|object reference in link management is missing for container $container used by $location$page";     

          // define current object for publishing
          $object_array[0]['publication'] = $site;
          $object_array[0]['location'] = $location;
          $object_array[0]['object'] = $page;
          $object_array[0]['category'] = $cat;
          
          $link_db_correct = false;
        }
        // one object reference were found in container
        elseif (is_array ($object_array) && sizeof ($object_array) == 1)
        {
          $object_array = null;
          
          // redefine current object for publishing
          $object_array[0]['publication'] = $site;
          $object_array[0]['location'] = $location;
          $object_array[0]['object'] = $page;
          $object_array[0]['category'] = $cat; 
                
          $link_db_correct = true;
        }
        // multiple object references were found in container
        elseif (is_array ($object_array))
        {
          $link_db_correct = true;
        }

        if (is_array ($object_array) && sizeof ($object_array) > 0)
        {
          $object_published = false;        
          $object_counter = 0;

          foreach ($object_array as $object)
          {
            $object_counter++;
            
            if (is_array ($object) && ($object_published == false || $object_counter <= sizeof ($object_array)))
            {
              $site = $object['publication'];
              $location = $object['location'];
              $cat = $object['category'];         
              $page = $object['object'];
              $page = correctfile ($location, $page, $user);

              // check for input object
              if ($site == $buffer_site && $location == $buffer_location && $page = $buffer_page) $object_published = true;
  
              // if object file exists
              if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && is_file ($location.$page))
              {
                // ---------------------------- call template engine ---------------------------
                $result = buildview ($site, $location, $page, $user, "publish", "no");    

                if (is_array ($result))
                {
                  $viewstore = $result['view'];
                  $release = $result['release'];
                  $contentfile = $result['container'];
                  $contentdata = $result['containerdata'];
                  $templatefile = $result['template'];
                  $templatedata = $result['templatedata'];
                  $templateext = $result['templateext'];
                  $application = $result['application'];
                  $pagename = $result['name'];
                  $filetype = $result['objecttype'];
                  
                  // error occured
                  if (isset ($result['view']) && strpos ("_".$result['view'], "<!-- hyperCMS:Error -->") > 0)
                  {
                    $viewstore = false;
                    $release = false;
                    $add_onload = "";
                    $show = $hcms_lang['an-error-occured-in-building-the-view'][$lang];
                  }
                }
                else
                {
                  $viewstore = false;
                  $release = false;
                  $add_onload = "";
                  $show = $hcms_lang['an-error-occured-in-building-the-view'][$lang];
                }

                // eventsystem
                if ($eventsystem['onpublishobject_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0))
                  onpublishobject_pre ($site, $cat, $location, $page, $contentfile, $contentdata, $templatefile, $templatedata, $viewstore, $user);

                // -------------------------------- publish page -------------------------------
                // if user has the workflow permission to publish or no workflow is attached
                // for media files the object file will not be touched (application might be empty or "media") or the media generator is used
                if ($show == "" && $release >= 3 && $application != "" && $application != "media" && ($viewstore != "" || $application == "generator"))
                {                  
                  // get the file extension of the object file
                  $file_info = getfileinfo ($site, $page, $cat);
  
                  // define new object file name
                  $page_new = $file_info['filename'].".".$templateext;
  
                  // rename object file if offline (unpublished)
                  if ($file_info['published'] == false)
                  {
                    $test = rename ($location.$page, $location.$page_new);
                    
                    // remote client
                    remoteclient ("rename", "abs_path_".$cat, $site, $location, "", $page, $page_new);
                    
                    if ($test == false)
                    {
                      $errcode = "10541";
                      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|rename failed for ".$location_esc.$page; 
                    }             
                  }  
                  // rename object file if file extension has changed
                  elseif (strtolower ($file_info['ext']) != strtolower (".".$templateext))
                  {
                    // rename file if template extension changed
                    $test = renamefile ($site, $location, $page, $page_new, $user);
       
                    // remote client
                    remoteclient ("rename", "abs_path_".$cat, $site, $location, "", $page, $page_new);         
                    
                    if ($test['result'] == false)
                    {
                      $errcode = "20111";
                      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|renamefile failed for ".$location_esc.$page;
                    }
                    else
                    {
                      if ($test['containerdata'] != false) $contentdata = $test['containerdata'];
                    }                              
                  }
                  
                  // save object file
                  $result_save = savefile ($location, $page_new, $viewstore);
       
                  // remote client
                  remoteclient ("save", "abs_path_".$cat, $site, $location, "", $page_new, "");
                  
                  if ($result_save != false)
                  {
                    // check application. if no dynamic inclusion of components is possible, publish also all 
                    // objects which use the given object.
                    if ($cat == "comp")
                    {
                      $test = publishlinkedobject ($site, $location, $page, $user);
                      
                      if ($test['result'] == false)
                      {
                        $errcode = "20199";
                        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|publishlinkedobject failed for ".$location_esc.$page; 
                      }                
                    }
                  }
                  else
                  {
                    $add_onload = "";
                    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
                    ".$hcms_lang['you-do-not-have-write-permissions-for-the-item'][$lang]."\n";
                    
                    break;
                  }               
                }
              }
              // if object file does not exist
              else
              {     
                $errcode = "20191";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|object reference in link management for container $container refers to a non existing object ".$location_esc.$page;
                $location = $buffer_location;                            
              }            
            }
  
            // ------------------------------- generate link index and update container ------------------------------
            if ($show == "" && $result_save == true && $release >= 3 && $application != "media"  && ($viewstore != "" || $application == "generator"))
            {
              if ($container != false && $template != false)
              {   
                // check if an error occured during inclusions
                if ($viewstore != "" || $application == "generator")
                {  
                  // -------------------- add livelink function for active link management ---------------------------

                  // include functions for link management  
                  if ($mgmt_config[$site]['linkengine'] == true || @substr_count (strtolower ($templatedata), "hypercms:link") >= 1 || @substr_count (strtolower ($templatedata), "hypercms:artlink") >= 1 || @substr_count (strtolower ($templatedata), "hypercms:component") >= 1 || @substr_count (strtolower ($templatedata), "hypercms:artcomponent") >= 1)
                  {
                    // ----------------------------- create and save link index file ---------------------------------  
                    // links
                    $link_data = "";
                    $link_db_entry = $container.":|";
                    
                    $objref_array = getcontent ($contentdata, "<contentobjects>");

                    if (is_array ($objref_array) && $objref_array[0] != "") $link_db_entry .= $objref_array[0].":|";
                    else
                    {
                      $objref_restored = convertpath ($site, $location, $cat).$page;
                      $link_db_entry .= $objref_restored.":|";
                      $contentdata = setcontent ($contentdata, "<hyperCMS>", "<contentobjects>", $objref_restored, "", "");
                      $contentdata = setcontent ($contentdata, "<hyperCMS>", "<contentstatus>", "restored", "", "");
                    }
                    
                    $linkobj_array = getcontent ($contentdata, "<link>");         
                    
                    if (is_array ($linkobj_array) && sizeof ($linkobj_array) > 0)
                    {
                      foreach ($linkobj_array as $linkobj)  
                      {
                        $link_id = getcontent ($linkobj, "<link_id>");
                        $link_href = getcontent ($linkobj, "<linkhref>");
                        
                        if (substr ($link_href[0], 0, 6) == "%page%")
                        {
                          $link_href[0] = deconvertlink (trim ($link_href[0])); 
                        }
                        
                        if ($link_id != false && $link_id != "")
                          $link_data .= "page|".$link_id[0]."|".$link_href[0]."\n";
                          
                        $link_db_entry .= $link_href[0]."|";
                      }
                    }
                    
                    // components       
                    $compobj_array = getcontent ($contentdata, "<component>");   
                    
                    if (is_array ($compobj_array) && sizeof ($compobj_array) > 0)
                    {      
                      foreach ($compobj_array as $compobj)  
                      {
                        $component_id = getcontent ($compobj, "<component_id>");
                        $component_files = getcontent ($compobj, "<componentfiles>");
                        $component_files = trim ($component_files[0]);
                        
                        // free array
                        $component_file_array = null;
                        
                        // if multi component
                        if (@substr_count ($component_files, "|") >= 1)
                        {
                          if ($component_files[strlen ($component_files)-1] == "|")
                          { 
                            $component_files = substr ($component_files, 0, strlen ($component_files)-1);
                          }
                            
                          $component_file_array = explode ("|", $component_files);
                        }
                        // if single component
                        else $component_file_array[0] = $component_files;
                        
                        if ($component_files != "") $link_db_entry .= $component_files."|";
                    
                        if ($component_id != false && $component_id[0] != "")
                        {
                          foreach ($component_file_array as $component_file)
                          {
                            if (substr ($component_file, 0, 6) == "%comp%") $component_file = substr ($component_file, 6);
                            $link_data .= "comp|".$component_id[0]."|".$component_file."\n";              
                          }
                        }  
                      }   
                    }
                    
                    $linkfile = substr ($container, 0, strpos ($container, ".xml")); 
                    
                    // save link index file in repository
                    if (!empty ($link_data))
                    {
                      $test = savefile ($mgmt_config['abs_path_link'], $linkfile, $link_data);   
                      
                      if ($test != false)
                      {
                        // remote client
                        remoteclient ("save", "abs_path_link", $site, $mgmt_config['abs_path_link'], "", $linkfile, "");                  
                      }
                      else
                      {
                        $errcode = "10879";
                        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|link index file of container $container could not be saved";                
                      }
                    }
                  }
                  
                  // show message
                  $add_onload = "opener.parent.frames['controlFrame'].location.reload(); if (opener.parent.frames['mainFrame']) opener.parent.frames['mainFrame'].location.reload();";
                  $show = "<span class=\"hcmsHeadline\">".$hcms_lang['published-item-successfully'][$lang]."</span><br />\n";
                    
                  $error_switch = "no";
                }
                else
                {
                  $add_onload = "";
                  $show = "<span class=\"hcmsHeadline\">".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
                  ".$hcms_lang['an-error-occured-in-building-the-view'][$lang]."\n";
                }
              }
              else
              {
                $add_onload = "";
                $show = "<span class=\"hcmsHeadline\">".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
                ".$hcms_lang['item-is-not-managed-by-hypercms'][$lang]."\n";
                
                $error_switch = "no";
              }  
  
              // eventsystem
              if ($eventsystem['onpublishobject_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0) && $error_switch == "no") 
                onpublishobject_post ($site, $cat, $location, $page_new, $contentfile, $contentdata, $templatefile, $templatedata, $viewstore, $user);          
            }
            // if we publish a media file we dont use the link management
            elseif ($application == "media")
            {
              $add_onload = "";
              $show = "<span class=\"hcmsHeadline\"".$hcms_lang['published-item-successfully'][$lang]."</span><br />\n";
              
              $error_switch = "no";
            }   
            else
            {
              $add_onload = "";
              $show = "<span class=\"hcmsHeadline\"".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
              ".$hcms_lang['you-do-not-have-permissions-to-publish-the-item'][$lang]."\n";
            }            
          }             
        }
        
        // correct link management database if it is corrupt
        if ($link_db_correct == false && $mgmt_config[$site]['linkengine'] == true)
        {
          $link_db_data = loadlockfile ($user, $mgmt_config['abs_path_data']."link/", $site.".link.dat", 10);
          
          if ($link_db_data != false && substr_count ($link_db_data, $container.":|") == 0)
          {
             $link_db_data = $link_db_data."\n".$link_db_entry;
             savelockfile ($user, $mgmt_config['abs_path_data']."link/", $site.".link.dat", $link_db_data);
          }
          else link_db_close ($site, $user);
        }
      }
      // object has no container, template or application (therefore not managed by hyperCMS or application is missing in template)
      else
      {
        // try to load container
        if ($container_id) $contentdata = loadcontainer ($container_id, "work", $user);

        // execute eventsystem
        if ($eventsystem['onpublishobject_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
          onpublishobject_pre ($site, $cat, $location, $page, $container, $contentdata, $template, "", "", $user);
          
        // execute eventsystem
        if ($media != false && $application != "generator" && $eventsystem['onpublishobject_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
          onpublishobject_post ($site, $cat, $location, $page, $container, $contentdata, $template, "", "", $user);    
  
        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
        ".$hcms_lang['item-is-not-managed-by-hypercms'][$lang]."\n";
   
        $error_switch = "no";
      }
      
      // ------------------------ publish VTT files of video ------------------------
      if (!empty ($contentdata) && !empty ($container_id))
      {
        $vtt_textnodes = selectcontent ($contentdata, "<text>", "<text_id>", "VTT-*");
        
        if (is_array ($vtt_textnodes))
        {
          foreach ($vtt_textnodes as $vtt_textnode)
          {
            if (!empty ($vtt_textnode))
            {
              $vtt_id = getcontent ($vtt_textnode, "<text_id>");
              list ($vtt, $vtt_langcode) = explode ("-", $vtt_id[0]);              
              $vtt_string = getcontent ($vtt_textnode, "<textcontent>");

              if (!empty ($vtt_string[0])) savefile ($mgmt_config['abs_path_temp']."view/", $container_id."_".trim($vtt_langcode).".vtt", $vtt_string[0]);
            }
          }
        }
      }
              
      // ------------------------------ update and save container ------------------------------
      if (!empty ($contentdata) && !empty ($container))
      {
        // create version of previous content file
        if (empty ($mgmt_config['contentversions']) || $mgmt_config['contentversions'] == true)
        {
          $contentfile_v = fileversion ($container);
          $contentlocation = getcontentlocation ($container_id, 'abs_path_content');
    
          if (is_file ($contentlocation.$container))
          {
            @copy ($contentlocation.$container, $contentlocation.$contentfile_v);
          }
        }

        // update information in content container
        $contentdata = setcontent ($contentdata, "<hyperCMS>", "<contentpublished>", $mgmt_config['today'], "", "");
        
        // write content container
        if ($contentdata != false)
        {   
          // save working xml content container file
          $test = savecontainer ($container, "work", $contentdata, $user, true);
          
          if ($test == false)
          {
            $errcode = "10880";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|working container $container.wrk could not be saved";                
          }            
          
          // save published xml content container file     
          $test = savecontainer ($container, "published", $contentdata, $user, true);

          if ($test == false)
          {
            $errcode = "10881";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|published container $container could not be saved";                
          }
        }
        else $test = false;
        
        if ($test == false)
        {
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
          ".$hcms_lang['you-do-not-have-write-permissions-for-the-container'][$lang]."\n";
          
          $error_switch = "yes";
        }
      }
    }
    // location is not valid
    else
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['you-do-not-have-write-permissions-for-the-item'][$lang]."</span><br />
      ".$hcms_lang['the-parameters-for-publishing-are-missing'][$lang]."\n";
    }             
  }
  // input is not valid
  else
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
    ".$hcms_lang['the-parameters-for-publishing-are-missing'][$lang]."\n";
  }
  
  // save log
  savelog (@$error);    

  // return results
  if ((isset ($error_switch) && $error_switch == "no") || $release < 3) $result['result'] = true;
  else $result['result'] = false;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  
  return $result;
}

// ------------------------------------------- processobjects -------------------------------------------
// function: processobjects()
// input: action [publish, unpublish, delete], publication, location, object, only published objects [pub, all], user name
// output: true/false on error

// description:
// Publish, unpublish or delete all objects recursively.
// Should not be used in CMS GUI, only for queue processing, since it does not provide feedback about the process state!

function processobjects ($action, $site, $location, $file, $published_only="0", $user)
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  if ($action != "" && valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($user))
  {
    // publication management config
    if (empty ($mgmt_config[$site]) || !is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php"); 
    
    $action = strtolower ($action);
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, "");
    
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";
    
    // define object file name
    if (!file_exists ($location.$file))
    {
      $file = correctfile ($location, $file, $user);
    }        
 
    // if folder
    if (($file == ".folder" && is_dir ($location)) || is_dir ($location.$file))
    {  
      if ($file == ".folder") $file = "";
      else $file = $file."/";
      
      // check if directory is empty
      $handle = @opendir ($location.$file);
      
      if ($handle != false)
      {
        while ($dirfile = @readdir ($handle))
        {
          if ($dirfile != ".folder" && $dirfile != "." && $dirfile != "..")
          {
            processobjects ($action, $site, $location.$file, $dirfile, $published_only, $user);
          }
        }
        
        @closedir ($handle);        
        return true;
      }
      else return false;
    }
    // if object
    elseif ($file != ".folder" && is_file ($location.$file))
    {
      $result = getfileinfo ($site, $file, "");

      // process object
      if ($result['published'] == true || $published_only == "0")
      {
        if ($action == "publish") $result = publishobject ($site, $location, $file, $user);
        elseif ($action == "unpublish") $result = unpublishobject ($site, $location, $file, $user);
        elseif ($action == "delete") $result = deleteobject ($site, $location, $file, $user);

        // error
        if ($result['result'] == false)
        {
          $errcode = "20421";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|un/publishobject failed for ".$location_esc.$file;
          
          // save log
          savelog (@$error);          
          
          return false;
        }
        else return true;
      }
      // nothing to process
      else return true;
    }
    // if location does not exist
    else return false;
  }  
  else return false; 
}

// ------------------------------------- publishlinkedobject -----------------------------------------
// function: publishlinkedobject()
// input: site, location, object, user name
// output: array

// description:
// This function publishes all linked objects of a given object.
// All objects with component links (references) to the given object will be published.
// This funtion is only used by publishobject.

function publishlinkedobject ($site, $location, $page, $user)
{      
  global $eventsystem, $mgmt_config, $cat, $ctrlreload, $hcms_lang, $lang;

  // set default language
  if ($lang == "") $lang = "en";
  
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($user))
  {  
    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location);

    if ($cat != "page") 
    {
      $object_array = getlinkedobject ($site, $location, $page, $cat);
      
      if ($object_array != false && is_array ($object_array) && sizeof ($object_array) > 0)
      {
        foreach ($object_array as $object)
        {
          if (is_array ($object))
          {
            $site = $object['publication'];
            $location = $object['location'];
            $page = $object['object'];
            
            // get application from template
            $pagedata = loadfile ($location, $page);
            $template = getfilename ($pagedata, "template");    
            $bufferdata = loadtemplate ($site, $template);          
            $bufferdata = getcontent ($bufferdata['content'], "<application>");
            $application = $bufferdata[0];              
                  
            if (strtolower ($application) == "htm" || strtolower ($application) == "xml")
            {
              $result = publishobject ($site, $location, $page, $user);
            }
            else
            {
              $result['result'] = true;
              $result['add_onload'] = "";
              $result['message'] = "<span class=\"hcmsHeadline\">".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
              ".$hcms_lang['the-page-which-uses-this-component-doesnt-need-to-be-republished'][$lang]."\n";               
            }  
          }        
        }
      }
      else
      {
        $result['result'] = true;
        $result['add_onload'] = "";
        $result['message'] = "<span class=\"hcmsHeadline\">".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
        ".$hcms_lang['found-no-items-using-the-component'][$lang]."\n";    
      }
    }
    else
    {
      $result['result'] = true;
      $result['add_onload'] = "";

      $result['message'] = "<span class=\"hcmsHeadline\">".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
      ".$hcms_lang['the-selected-item-is-a-page'][$lang]."\n";    
    }      
  }
  else
  {
    $result['result'] = false;
    $result['add_onload'] = "";
    $result['message'] = "<span class=\"hcmsHeadline\">".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
    ".$hcms_lang['the-parameters-for-publishing-are-missing'][$lang]."\n";    
  }  
  
  // return result 
  return $result;        
}

// ---------------------------------------- unpublishobject --------------------------------------------
// function: unpublishobject()
// input: site, location, object
// output: array

// description:
// This function unpublishes a page or component and calls the function manipulateobject

function unpublishobject ($site, $location, $page, $user)
{      
  global $eventsystem, $mgmt_config, $cat, $ctrlreload, $hcms_lang, $lang;   

  // set default language
  if ($lang == "") $lang = "en";
  
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($user))
  {
    // publication management config
    if (!is_array ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php"); 
    
    $cat = getcategory ($site, $location);    
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, "");
    
    // check location (only components of given publication are allowed)
    if (substr_count ($location, $mgmt_config['abs_path_rep']) == 0 || substr_count ($location, $mgmt_config['abs_path_comp'].$site."/") > 0)
    {               
      // eventsystem
      if ($eventsystem['onunpublishobject_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
        onunpublishobject_pre ($site, $cat, $location, $page, $user);              
        
      // get all connected objects
      $pagedata = loadfile ($location, $page);
      $container = getfilename ($pagedata, "content");
      $container_id = substr ($container, 0, strpos ($container, ".xml"));
      $template = getfilename ($pagedata, "template");
      $media = getfilename ($pagedata, "media");
      
      // check template
      $templatedata = loadtemplate ($site, $template);
        
      if (is_array ($templatedata))
      {
        if ($templatedata['content'] != "")
        {
          $buffer = getcontent ($templatedata['content'], "<application>");
          
          if ($buffer[0] != "") $application = $buffer[0];
          else $application = "";
        }
      }
      else $template = false;
      
      // delete all VTT files of videos
      if ($container_id != "")
      {
        // load language code index file
        $langcode_array = getlanguageoptions ();
  
        if ($langcode_array != false)
        {
          foreach ($langcode_array as $code => $language)
          {
            if (is_file ($mgmt_config['abs_path_temp']."view/".$container_id."_".trim($code).".vtt"))
            {
              deletefile ($mgmt_config['abs_path_temp']."view/", $container_id."_".trim($code).".vtt", 0);
            }
          }
        }
      }

      // if object is a page or component and not a multimedia file
      if ($container != false && $template != false && ($media == false || $application == "generator") && $page != ".folder")
      {
        $object_array = getconnectedobject ($container);
        
        if ($object_array == false)
        {    
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
          ".$hcms_lang['information-about-connected-items-of-the-container-is-missing'][$lang]."\n";
      
          $errcode = "20897";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|object reference in link management is missing for container $container used by ".$location_esc.$page;     
        
          // define current object for publishing
          $object_array[0]['publication'] = $site;
          $object_array[0]['location'] = $location;
          $object_array[0]['object'] = $page;
          $object_array[0]['category'] = $cat;
          
          $link_db_correct = false;
        }
        // one object reference were found in container
        elseif (is_array ($object_array) && sizeof ($object_array) == 1)
        {
          $object_array = null;
          
          // redefine current object for publishing
          $object_array[0]['publication'] = $site;
          $object_array[0]['location'] = $location;
          $object_array[0]['object'] = $page;
          $object_array[0]['category'] = $cat; 
                
          $link_db_correct = true;
        }
        // multiple object references were found in container
        elseif (is_array ($object_array))
        {
          $link_db_correct = true;
        }     
    
        if (is_array ($object_array) && sizeof ($object_array) > 0)
        {    
          $object_counter = 0;
          
          foreach ($object_array as $object)
          {
            $object_counter++;
            
            if (is_array ($object) && $object_counter <= sizeof ($object_array))
            {
              $site = $object['publication'];
              $location = $object['location'];
              $cat = $object['category'];         
              $page = $object['object'];
              $page = correctfile ($location, $page, $user);

              // if object file exists
              if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && is_file ($location.$page))
              {
                $result = manipulateobject ($site, $location, $page, "", $user, "page_unpublish");  
            
                if ($result['result'] != false)
                {      
                  // check application, if no dynamic inclusion of components is possible publish also all 
                  // objects which use the given object.
                  if ($cat == "comp")
                  {
                    $test = publishlinkedobject ($site, $location, $page, $user);
                    
                    if ($test['result'] == false)
                    {
                      $errcode = "20198";
                      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|publishlinkedobject failed for ".convertpath ($site, $location, $cat).$page; 
                    }                
                  }
                }
                else
                {
                  $add_onload = "";
                  $show = "<span class=\"hcmsHeadline\">".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
                  ".$hcms_lang['you-do-not-have-write-permissions-for-the-item'][$lang]."\n";
                  
                  break;
                }
              }      
            }
          }
        }
        else
        {
          $result['result'] = false; 
          $result['add_onload'] = "";
          $result['message'] = "<span class=\"hcmsHeadline\">".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
          ".$hcms_lang['information-about-connected-items-of-the-container-is-missing'][$lang]."\n";
      
          $errcode = "20878";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|object reference in link management is missing for container $container used by ".convertpath ($site, $location, $cat).$page;     
        }       
      }
      else
      {
        $result['result'] = true; 
        $result['add_onload'] = "";
        $result['message'] = "<span class=\"hcmsHeadline\">".$hcms_lang['published-item-successfully'][$lang]."</span>";
      } 
      
      // eventsystem
      if ($eventsystem['onunpublishobject_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0) && $result['result'] == true) 
        onunpublishobject_post ($site, $cat, $location, $page, $user);
    }
    // location is not valid
    else
    {
      $result['result'] = false;
      $result['add_onload'] = "";
      $result['message'] = "<span class=\"hcmsHeadline\">".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
      ".$hcms_lang['you-do-not-have-write-permissions-for-the-item'][$lang]."\n";    
    } 
  }
  // input parameters are invalid
  else
  {
    $result['result'] = false;
    $result['add_onload'] = "";
    $result['message'] = "<span class=\"hcmsHeadline\">".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
    ".$hcms_lang['the-parameters-for-publishing-are-missing'][$lang]."\n";    
  }                
         
  // return results 
  return $result;
}    

// ------------------------------------------ collectobjects --------------------------------------------
// function: collectobjects()
// input: root ID, publication name, category [page,comp], location, collect only published objects [0,1] 
// output: result array / false

// description:
// Help function used to create a list of all objects inside a folder

function collectobjects ($root_id, $site, $cat, $location, $published_only="0")
{     
  global $user, $pageaccess, $compaccess, $mgmt_config, $hiddenfolder, $hcms_lang, $lang;
 
  // if selected file is a directory
  if (isset ($root_id) && valid_publicationname ($site) && $cat != "" && valid_locationname ($location))
  {
    $list = array ();
    
    // deconvert path
    $location = deconvertpath ($location, "file");
      
    // if folder
    if (is_dir ($location) && accesspermission ($site, $location, $cat) != false)
    {         
      // check if directory is empty
      $dir = @opendir ($location);
      
      // add slash if not present at the end of the location string
      if (substr ($location, -1) != "/") $location = $location."/";      
      
      if ($dir != false)
      {
        while ($dirfile = @readdir ($dir))
        {
          if ($dirfile != "." && $dirfile != "..")
          {
            // check access permissions
            if (is_file ($location.$dirfile) || (is_dir ($location.$dirfile) && accesspermission ($site, $location.$dirfile."/", $cat) != false)) 
            {
              $list_add = collectobjects ($root_id, $site, $cat, $location.$dirfile, $published_only);                   
              if ($list_add != false) $list = array_merge ($list, $list_add);
            }
          }
        }
        
        @closedir ($dir);
      }
    }
    // if object
    elseif (is_file ($location))
    {
      $object = getobject ($location);
      $location = getlocation ($location);
      $result = getfileinfo ($site, $object, $cat);
      
      if ($result['published'] == true || $published_only == "0")
      {
        $list[] = $root_id."|".$site."|".convertpath ($site, $location, $cat)."|".$object;
      }
    }
    // if location does not exist
    else return false;
 
    // return list array
    return $list;
  }  
  else return false; 
}
  
// ------------------------------------------ manipulateallobjects --------------------------------------------
// function: manipulateallobjects()
// input: action [publish, unpublish, delete, paste], objectpath (array),  
//        method (only for paste action) [copy, linkcopy, cut], force [start, stop, continue], 
//        collect only published objects [0, 1], user name, temporary collection file name (optional)
// output: true/false

// description:
// This function is used to perform actions on folders with several items. the function will be called by
// popup_status.php. To work correctly the functions needs several variables to be passed. please take a
// look at the $result array of this function.

function manipulateallobjects ($action, $objectpath_array, $method, $force, $published_only, $user, $tempfile="")
{
  global $eventsystem, $mgmt_config, $pageaccess, $compaccess, $hiddenfolder, $cat, $hcms_lang, $lang;
      
  // set default language
  if ($lang == "") $lang = "en";
  
  $result = array();
  $result['result'] = false;
  $result['maxcount'] = 0;
  $result['count'] = 0;
  $result['working'] = false;
  $result['message'] = "";
  $result['tempfile'] = "";
  $result['method'] = "";
  
  // get object pathes from the session if is not set      
  if (!is_array ($objectpath_array) && (isset ($_SESSION['clipboard_multiobject']) && is_array ($_SESSION['clipboard_multiobject']))) $objectpath_array = $_SESSION['clipboard_multiobject'];
  if ((!isset ($rootpathdelete_array) || !is_array ($rootpathdelete_array)) && (isset ($_SESSION['clipboard_rootpathdelete']) && is_array ($_SESSION['clipboard_rootpathdelete']))) $rootpathdelete_array = $_SESSION['clipboard_rootpathdelete'];
  else $rootpathdelete_array = Null;
  if ((!isset ($rootpathold_array) || !is_array ($rootpathold_array)) && (isset ($_SESSION['clipboard_rootpathold']) && is_array ($_SESSION['clipboard_rootpathold']))) $rootpathold_array = $_SESSION['clipboard_rootpathold'];
  else $rootpathold_array = Null;
  if ((!isset ($rootpathnew_array) || !is_array ($rootpathnew_array)) && (isset ($_SESSION['clipboard_rootpathnew']) && is_array ($_SESSION['clipboard_rootpathnew']))) $rootpathnew_array = $_SESSION['clipboard_rootpathnew'];
  else $rootpathnew_array = Null;

  if (is_array ($objectpath_array) && valid_objectname ($user) && $action != "")
  {
    $collection = array ();
    $test['result'] = false;
    $i = 0;
    
    // set session
    $_SESSION['clipboard_multiobject'] = $objectpath_array;

    // -------------------------- load or create collection  -------------------------------
    // check if collection file exists and load collection
    if ($force != "start" && is_file ($mgmt_config['abs_path_temp'].$tempfile))
    { 
      $collection_data = loadfile_fast ($mgmt_config['abs_path_temp'], $tempfile);
  
      if ($collection_data != false && $collection_data != "")
      {
        // get items
        $collection = explode ("\n", trim ($collection_data));
        $count = sizeof ($collection);        
      }   
      else $count = 0;
    }
    // create collection initally
    elseif ($force == "start")
    {
      foreach ($objectpath_array as $objectpath)
      {
        if ($objectpath != "")
        {   
          $site = getpublication ($objectpath);            
          $location = deconvertpath ($objectpath, "file"); 
          
          // check for .folder file and remove it, otherwise the folder is treated as a file
          if (getobject ($location) == ".folder") $location = getlocation ($location);
              
          $object = getobject ($location); // could be a file or a folder                 
          $location = getlocation ($location); // location without object 
          $object = correctfile ($location, $object, $user); 
       
          if (valid_publicationname ($site) && valid_locationname ($location) && $object != "")
          {
            // define category if undefined
            if ($cat == "") $cat = getcategory ($site, $location);
                
            // add slash if not present at the end of the location string
            if (substr ($location, -1) != "/") $location = $location."/";          
          
            // read clipboard if action = paste
            if ($action == "paste")
            {            
              // get clipboard from session
              $clipboard = $_SESSION['hcms_temp_clipboard'];
              $clipboard_array = explode ("\n", $clipboard);
              $collection = array ();
              $j = 0;

              foreach ($clipboard_array as $clipboard_entry)
              {
                if ($clipboard_entry != "")
                {
                  // get clipboard entries
                  list ($method, $site_source, $cat_source, $location_source_esc, $object_source, $objectname_source, $filetype_source) = explode ("|", $clipboard_entry); 

                  $location_source = deconvertpath ($location_source_esc, "file");               

                  // check sites
                  if ($site_source == $site)
                  {
                    // check category
                    if (strtolower ($cat_source) == strtolower ($cat))
                    {        
                      // if a folder should be pasted
                      if ($site_source != "" && $location_source != "" && $object_source != "") 
                      {          
                        // collect all items (2-dimensional array)
                        $collection_add = collectobjects ($j, $site_source, $cat, $location_source.$object_source, $published_only);                  
                        if ($collection_add != false) $collection = array_merge ($collection, $collection_add);           

                        // create all folders in new location
                        if (is_dir ($location_source.$object_source))
                        {
                          if (is_dir ($location.$object)) $location_dest = $location.$object."/";
                          else $location_dest = $location;
        
                          $result = copyfolders ($site, $location_source, $location_dest, $object_source, $user);

                          if ($result['result'] == false)
                          {
                            return $result;
                          } 
                          else
                          {  
                            $rootpathold_array[$j] = convertpath ($site_source, $result['rootlocationold'], $cat).$result['rootfolderold']."/";
                            $rootpathnew_array[$j] = convertpath ($site_source, $result['rootlocationnew'], $cat).$result['rootfoldernew']."/";
                            
                            if ($method == "cut")
                            {
                              $rootpathdelete_array[$j] = $rootpathold_array[$j];
                            }                            
                          } 
                        }
                        // if object_source is a file then define rootlocation and rootfolders from given objectpath and clipboard
                        // the destination location (new) must be the same for all collection entries
                        else
                        {   
                          $rootpathold_array[$j] = convertpath ($site_source, $location_source, $cat);
                          
                          if (is_dir ($location.$object)) $rootpathnew_array[$j] = convertpath ($site_source, $location.$object."/", $cat); // fixed for all collection entries
                          else $rootpathnew_array[$j] = convertpath ($site_source, $location, $cat);             
                        }
                      }
                      
                      $j++;
                    }
                    // if categories are not equal
                    else
                    {
                      $result['result'] = false;
                      $result['message'] = $hcms_lang['it-is-not-possible-to-paste-the-objects-here'][$lang];
                      return $result;
                    } 
                  }
                  // if sites are not equal
                  else
                  {
                    $result['result'] = false;
                    $result['message'] = $hcms_lang['it-is-not-possible-to-cut-copy-and-paste-objects-across-different-publications'][$lang];
                    return $result;
                  }
                }
              }   
            }
            // all other cases except paste
            else
            {          
              // collect all items
              $collection_add = collectobjects ($i, $site, $cat, $location.$object, $published_only);
              
              if ($collection_add != false)
              {
                $collection = array_merge ($collection, $collection_add);
              }
          
              if ($action == "delete" && is_dir ($location.$object))
              {
                $rootpathdelete_array[] = convertpath ($site, $location, $cat).$object;
              }
            }                     
          }  
          
          $i++;         
        }
      }
    }
 
    // set rootpathes in the session
    if ($action == "delete")
    {
      if (is_array ($rootpathdelete_array)) $_SESSION['clipboard_rootpathdelete'] = $rootpathdelete_array;
    }
    elseif ($action == "paste")
    {
      if (is_array ($rootpathdelete_array)) $_SESSION['clipboard_rootpathdelete'] = $rootpathdelete_array;  
      if (is_array ($rootpathold_array)) $_SESSION['clipboard_rootpathold'] = $rootpathold_array;
      if (is_array ($rootpathnew_array)) $_SESSION['clipboard_rootpathnew'] = $rootpathnew_array;      
    }
    
    // count collection items and set force paramater
    if ($collection != false && is_array ($collection) && sizeof ($collection) > 0)
    {
      $maxcount = $count = sizeof ($collection);       
      $force = "continue";
    }  
    else 
    {
      $maxcount = $count = 0;
      $test['result'] = true;        
      $force = "stop";
    }    

    // ------------------------ process items in collection ---------------------------
    
    if (is_array ($collection) && $count > 0)
    {
      for ($i = 0; $i <= 4; $i++)
      {
        if (isset ($collection[$i]) && $collection[$i] != "")
        {
          // get location and object
          list ($root_id, $site_source, $location_source_esc, $object_source) = explode ("|", $collection[$i]); 
          $location_source = deconvertpath ($location_source_esc, "file");  

          // execute actions for files
          if ($location_source != "" && $object_source != "" && is_file ($location_source.$object_source))
          {
            if ($action == "publish") 
            {       
              $test = publishobject ($site_source, $location_source, $object_source, $user); 

              if ($test['result'] != false) unset ($collection[$i]);
              else 
              {
                $errcode = "20108";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|publishobject failed for ".convertpath ($site, $location_source, $cat).$object_source;
                break;
              }                   
            }
            elseif ($action == "unpublish")
            {
              $test = unpublishobject ($site_source, $location_source, $object_source, $user);
       
              if ($test['result'] != false) unset ($collection[$i]);
              else 
              {
                $errcode = "20109";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|unpublishobject failed for ".convertpath ($site, $location_source, $cat).$object_source;
                break;
              }        
            } 
            elseif ($action == "delete")
            {
              $test = deleteobject ($site_source, $location_source, $object_source, $user);
    
              if ($test['result'] != false) unset ($collection[$i]);
              else 
              {
                $errcode = "20109";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|deleteobject failed for ".convertpath ($site, $location_source, $cat).$object_source;
                break;
              }            
            }    
            elseif ($action == "paste")
            {
              // for action copy and paste
              if ($method == "copy") 
              {            
                // reset clipboard
                $result = copyobject ($site_source, $location_source, $object_source, $user);              

                /*
                // PRESERVE LINKED OBJECTS ON COPY IS NOT SUPPORTED SINCE Version 5.5.3
                // action copy and paste requires a special handling due to connected objects
                // if the connection of the copied objects should be preserved.                
                $tempdata = loadfile ($location_source, $object_source);
                                
                if ($tempdata != false) 
                {
                  $container = getfilename ($tempdata, "content");
                  
                  if ($container != false)
                  {
                    $connectedobject_array = getconnectedobject ($container);              
                     
                    if ($connectedobject_array != false && sizeof ($connectedobject_array) > 1)
                    {
                      $copy_done = false;
                           
                      foreach ($connectedobject_array as $connectedobject)
                      {                           
                        // check if object is in the scope of the selection
                        for ($j = 0; $j < sizeof ($collection); $j++)
                        {
                          if (substr_count ($collection[$j], $connectedobject['publication']."|".$connectedobject['location']."|".$connectedobject['object']) == 1)
                          {
                            // get site, location and object
                            list ($temp_id, $temp_site, $temp_location_esc, $temp_object) = explode ("|", $collection[$j]); 
                            
                            // define destination location for paste action
                            $location_dest_esc = str_replace ($rootpathold_array[$temp_id], $rootpathnew_array[$temp_id], $temp_location_esc);                            
                            $location_dest = deconvertpath ($location_dest_esc, "file");
                            $site_dest = getpublication ($location_dest_esc);

                            // if copy has not been made
                            if ($copy_done == false && $location_dest != "")
                            {      
                              $result = copyobject ($connectedobject['publication'], $connectedobject['location'], $connectedobject['object'], $user);
           
                              if ($result['result'] == true) 
                              {                 
                                $test = pasteobject ($site_dest, $location_dest, $user);   
                              }
                              else $test['result'] = false;
                              
                              if ($test['result'] == true)
                              {
                                $tempdata = loadfile ($location_dest, $object_source);
                                
                                if ($tempdata != false)
                                {
                                  $container = getfilename ($tempdata, "content");
                                  $copy_done = true;
                                }
                                else $container = false;
                              }
                            }
                            // if a copy is done
                            else
                            {
                              if ($container != false)
                              {
                                $tempdata = loadfile ($connectedobject['location'], $connectedobject['object']);
                                $tempdata = setfilename ($tempdata, "content", $container);
      
                                if ($tempdata != false)
                                {
                                  // load link db
                                  $link_db = link_db_load ($site_dest, $user);
                                  
                                  // add new object and save
                                  if (is_array ($link_db) && sizeof ($link_db) > 0)
                                  {
                                    $new_object = convertpath ($site_dest, $location_dest, $cat).$connectedobject['object'];
                                    
                                    $link_db = link_db_update ($site_dest, $link_db, "object", $container, $cat, "", $new_object, "all"); 
                                        
                                    if ($link_db != false) $test = link_db_save ($site_dest, $link_db, $user);  
                                    else $test = false;      
                                    
                                    // load container from file system
                                    $result = getcontainername ($container);
                                    $container_wrk = $result['container'];
                                    $bufferdata = loadcontainer ($container_wrk, "work", $user);  
                                    
                                    // get current objects
                                    if ($bufferdata != false) $objects = getcontent ($bufferdata, "<contentobjects>");
                            
                                    // insert new object into content container
                                    if ($bufferdata != false) $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentobjects>", $objects[0].$new_object."|", "", "");               
                                        
                                    if ($bufferdata != false) 
                                    {         
                                      // save working container 
                                      $test = savecontainer ($container_wrk, "work", $bufferdata, $user);           
                                    }
                                    else $test = false;         
                                    
                                    if ($test == false)
                                    {
                                      $errcode = "10677";
                                      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|savefile failed for ".getcontentlocation ($contentfile_id, 'abs_path_content').$container;           
                                    }        
                                  }
                                  
                                  // save object
                                  $test['result'] = savefile ($location_dest, $connectedobject['object'], $tempdata);
                                }
                                else $test['result'] = false;
                              }
                            }
                            
                            if ($test['result'] != false)
                            {
                              $collection[$j] = null;
                            }
                            else
                            {
                              $errcode = "20111";
                              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|pasteobject failed for $location_source$object_source";
                              break;
                            }  
                          }
                        }
                      }
                    }
                    // no connected objects were found
                    else
                    {
                      $result = copyobject ($site_source, $location_source, $object_source, $user);
                    }
                  }
                  // object is not managed by hyperCMS
                  else 
                  {
                    $result = copyobject ($site_source, $location_source, $object_source, $user);
                  }
                }
                // object cannot be loaded
                else $result['result'] = false;
                */
              }
              // for action cut and paste
              elseif ($method == "cut")
              {
                // reset clipboard
                $result = cutobject ($site_source, $location_source, $object_source, $user);
              }     
              // for action connected copy and paste
              elseif ($method == "linkcopy")
              {
                // reset clipboard
                $result = copyconnectedobject ($site_source, $location_source, $object_source, $user);
              }
              
              // paste object
              if ($result['result'] == true) 
              {
                // define destination location for paste action                      
                $location_dest = str_replace ($rootpathold_array[$root_id], $rootpathnew_array[$root_id], $location_source_esc);

                // copy_done is deprected since linked copies are not supported anymore
                if (!isset ($copy_done) || (isset ($copy_done) && $copy_done != true))
                {
                  $site_dest = getpublication ($rootpathnew_array[$root_id]);
                  $test = pasteobject ($site_dest, $location_dest, $user);
                }
                else $test['result'] = true;
              }
              else $test['result'] = false;
  
              if ($test['result'] != false)
              {
                unset ($collection[$i]);
              }
              else 
              {
                $errcode = "20110";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|pasteobject failed for ".convertpath ($site, $location_source, $cat).$object_source;
                break;
              }                   
            }  
          }                        
          
          // ========================================== error log =============================================
          // save log
          savelog (@$error);   
        }
        else $test['result'] = true;
      }
    
      // ------------------------------ update and save collection ----------------------------
      $result['working'] = false;
      
      if (sizeof ($collection) > 0)
      {
        $collection = trim (implode ("\n", $collection));
        
        // define temp file name
        if ($tempfile == "") $tempfile = uniqid().".coll.dat";

        // go on 
        if (strlen ($collection) > 0) 
        {
          savefile ($mgmt_config['abs_path_temp'], $tempfile, $collection);
          
          // define result array (will allow popup_status.php to continue!)
          $result['working'] = true;
        }
        // finished
        else 
        {
          deletefile ($mgmt_config['abs_path_temp'], $tempfile, 1);       
        }
      }
      // finished
      else
      { 
        deletefile ($mgmt_config['abs_path_temp'], $tempfile, 1);              
      }
    }
    // finished, no items were found (could also mean directory without files!)
    else
    {      
      // nothing was done on files, set test['result'] to be OK!
      $result['working'] = false;
      $test['result'] = true;
    }    

    // --------------------------------- execute action for directories --------------------------
    if (isset ($result['working']) && $result['working'] == false && $test['result'] != false)
    {
      // action = delete
      if ($action == "delete")
      {
        // if roothpath array for deleting folders is set, else use input of multiobject saved in objectpath array
        // if (is_array ($rootpathdelete_array)) $objectpath_array = $rootpathdelete_array;
        
        if (is_array ($rootpathdelete_array) && sizeof ($rootpathdelete_array) > 0)
        {
          foreach ($rootpathdelete_array as $objectpath)
          {
            if ($objectpath != "")
            {
              $site = getpublication ($objectpath);                 
              $folder = getobject ($objectpath); // could be a file or a folder              
              $location = getlocation ($objectpath);  // location without folder&nbsp;&nbsp;
              $location = deconvertpath ($location, "file");                
  
              if (valid_publicationname ($site) && valid_locationname ($location) && $folder != "" && is_dir ($location.$folder))
              {
                // eventsystem
                if (isset ($eventsystem['ondeletefolder_pre']) && $eventsystem['ondeletefolder_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
                  ondeletefolder_pre ($site, $cat, $location, $folder, $user);
                            
                $test['result'] = deletefile ($location, $folder, 1);

                // remote client
                remoteclient ("delete", "abs_path_".$cat, $site, $location, "", $folder, "");              
                
                // eventsystem
                if (isset ($eventsystem['ondeletefolder_post']) && $eventsystem['ondeletefolder_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0) && $test['result'] != false) 
                  ondeletefolder_post ($site, $cat, $location, $folder, $user);                 
              }
            }
          }
        }
      }
      // action = cut & paste
      elseif ($action == "paste" && $method == "cut" && is_array ($rootpathdelete_array) && sizeof ($rootpathdelete_array) > 0)
      {
        reset ($rootpathdelete_array);
        
        for ($i = 1; $i <= sizeof ($rootpathdelete_array); $i++)
        {
          if ($rootpathdelete_array != "")
          {
            $temp_id = key ($rootpathdelete_array);
            $location = deconvertpath ($rootpathdelete_array[$temp_id], "file");  
            $folder = getobject ($location); // could be a file or a folder   
            $location = getlocation ($location);  // location without folder  
             
            if (valid_locationname ($location) && $folder != "" && is_dir ($location.$folder))
            {
              $test['result'] = deletefile ($location, $folder, 1); 

              $test_renamegroup = renamegroupfolder ($site, $cat, $rootpathdelete_array[$temp_id], $rootpathnew_array[$temp_id], $user);
              
              if ($test_renamegroup == false)
              {
                $errcode = "10713";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|renamegroupfolder failed for ".$rootpathdelete_array[$temp_id]." to ".$rootpathnew_array[$temp_id]; 
              }
              
              $test_renameworkflow = renameworkflowfolder ($site, $cat, $rootpathdelete_array[$temp_id], $rootpathnew_array[$temp_id], $user); 
               
              if ($test_renameworkflow == false)
              {
                $errcode = "10713";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|renameworkflowfolder failed for ".$rootpathdelete_array[$temp_id]." to ".$rootpathnew_array[$temp_id]; 
              }
                         
              // remote client
              remoteclient ("delete", "abs_path_".$cat, $site, $location, "", $folder, ""); 
            }
          }
          
          next ($rootpathdelete_array);
        }    
      }
    }

    // ----------------------------- clear session variables ----------------------------------
    if (isset ($result['working']) && $result['working'] == false)
    {
      $_SESSION['clipboard_multiobject'] = null;
      $_SESSION['clipboard_rootpathdelete'] = null; 
      $_SESSION['clipboard_rootpathold'] = null;
      $_SESSION['clipboard_rootpathnew'] = null;        
    }
      
    // ------------------------------ define result array ------------------------------------
    if (isset ($test['result']) && $test['result'] != false) $result['result'] = true;
    else $result['result'] = false;    
    $result['maxcount'] = $maxcount;
    $result['count'] = $count;   
    if (isset ($result['working']) && $result['working'] != true) $result['working'] = false;    
    if (!empty ($test['message'])) $result['message'] = $test['message'];  
    $result['tempfile'] = $tempfile;  
    
    if ($action == "paste") 
    {
      $result['method'] = $method;         
    }
  }
  
  return $result;
}   

// ---------------------- remoteclient -----------------------------
// function: remoteclient()
// input: action [save, copy, delete, rename, get], root [abs_path_link, abs_path_media, abs_path_comp, abs_path_page, abs_path_rep], publication, location, locationnew, page, pagenew
// output: http answer [string] or false

// description:
// Sends data to remote client via http post

function remoteclient ($action, $root, $site, $location, $locationnew, $page, $pagenew)
{
  global $user, $mgmt_config, $hcms_lang, $lang;
  
  if (!empty($mgmt_config[$site]['remoteclient']))
  {
    if ($action != "" && $root != "" && valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page))
    {
      $content = "";
     
      // load site config file of publication system
      if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"))
      {   
        $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini");
      }
   
      // data of pages or components
      if ($root == "abs_path_comp" || $root == "abs_path_page")
      {
        // page or component
        if (is_file ($location.$page))
        {
          $pagedata = loadfile ($location, $page);
          $bufferdata = getfilename ($pagedata, "template");    
          $bufferdata = loadtemplate ($site, $bufferdata); 
          $template = getcharset ($site, $bufferdata);
          $charset = $template['charset'];
          if ($action == "save") $content = $pagedata;
        }
      }
      // data of link index file
      elseif ($root == "abs_path_link")
      {
        if (is_file ($location.$page))
        {
          if ($action == "save") $content = loadfile ($location, $page);
        }   
      }
      // data of multimedia files
      elseif ($root == "abs_path_media" || $root == "abs_path_tplmedia")
      {
        if (is_file ($location.$page))
        {
          if ($action == "save") 
          {
            $encoding = "multipart/form-data";
            
            $handle = fopen ($location.$page, "rb");
            
            if ($handle != false)
            {
              $content = fread ($handle, filesize ($location.$page));
              fclose ($handle);    
            }        
          }
        }
      }
      
      if ($root == "abs_path_page") $root_path = $mgmt_config[$site][$root];
      else $root_path = $mgmt_config[$root];
      
      // define data for transport
      $data['action'] = $action;
      $data['root'] = $root;
      $data['site'] = $site;
      $data['location'] = str_replace ($root_path, "", $location);
      $data['locationnew'] = str_replace ($root_path, "", $locationnew);
      $data['page'] = $page;
      $data['pagenew'] = $pagenew;
      $data['content'] = $content;
      $data['passcode'] = hcms_crypt ($data['location'].$data['page']);
         
      // call remote client
      $result = HTTP_Post ($mgmt_config[$site]['remoteclient'], $data, $encoding="application/x-www-form-urlencoded", $charset="UTF-8");
      
      // error log
      if (substr_count ($result, "HTTP/1.1 200 OK") == 0 || substr_count ($result, "ERROR") == 1)
      {
        $errcode = "10601";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|remoteclient failed for '".$action."' on ".$location.$page;
        
        // save log
        savelog (@$error);           
      }
      
      return $result;
    }
    else return false;
  }
  else return false;
}

// ---------------------- HTTP_Post -----------------------------
// function: HTTP_Post()
// input: URL[string], $data[array] (raw data), content-type [application/x-www-form-urlencoded, multipart/form-data], character set [string]
// output: http response [string] / false on error

// description:
// Sends data via http post and returns response

function HTTP_Post ($URL, $data, $contenttype="application/x-www-form-urlencoded", $charset="UTF-8", $referrer="") 
{
  if ($URL != "" && substr_count ($URL, "://") > 0)
  {
    $request = "";
    
    // parsing the given URL
    $URL_Info = parse_url ($URL);
    
    // if SSL is used
    if (substr_count ($URL, "https://")==1)
    {
      $Host_protocol = "ssl://";
      
      // Find out which port is needed - if not given use standard (=443)
      if (!isset ($URL_Info["port"])) $URL_Info["port"] = 443;    
    }
    else
    {
      $Host_protocol = "";
      
      // Find out which port is needed - if not given use standard (=80)
      if (!isset ($URL_Info["port"])) $URL_Info["port"] = 80;    
    }
  
    // if not given use this script as referrer
    if ($referrer == "" && isset ($_SERVER["SCRIPT_URI"])) $referrer = $_SERVER["SCRIPT_URI"];
  
    // building POST-request
    // for content-type = application/x-www-form-urlencoded 
    if ($contenttype == "application/x-www-form-urlencoded")
    {
      // making string from $data
      $data_string = "";
      
      if (is_array ($data))
      {    
        foreach ($data as $key => $value) $values[] = $key."=".urlencode ($value);
        $data_string = implode ("&", $values);
      }
        
      $request .= "POST ".$URL_Info["path"]." HTTP/1.1\n";
      $request .= "Host: ".$URL_Info["host"]."\n";
      $request .= "Referer: ".$referrer."\n";
      $request .= "Content-type: ".$contenttype."; charset=".$charset."\n";
      $request .= "Content-length: ".strlen ($data_string)."\n";
      $request .= "Connection: close\n";
      $request .= "\n";
      $request .= $data_string."\n";
    }
    // for content-type = multipart/form-data
    elseif ($contenttype == "multipart/form-data")
    {
      $boundary = "---------------------".substr(md5(rand(0,32000)),0,10);
      
      // making string from $data
      $data_string = "";
       
      if (is_array ($data))
      {    
        foreach ($data as $key => $val)
        {
          if ($key != "content")
          {
            $data_string .= "--$boundary\n";
            $data_string .= "Content-Disposition: form-data; name=\"".$key."\"\n\n".$val."\n";
          }
        }
       
        $data_string .= "--$boundary\n";
      }    
      
      $request .= "POST ".$URL_Info["path"]." HTTP/1.0\n";
      $request .= "Host: ".$URL_Info["host"]."\n";
      $request .= "Referer: ".$referrer."\n";
      //$request .= "Keep-Alive: 300\n";
      //$request .= "Connection: keep-alive\n";
      $request .= "Content-type: multipart/form-data; boundary=".$boundary."\n";
    
      // collect FILE data
      if ($data['page'] != "")
      {      
        $data_string .= "Content-Disposition: form-data; name=\"Filedata\"; filename=\"".$data['page']."\"\n";
        $data_string .= "Content-Type: ".getmimetype ($data['page'])."\n";
        $data_string .= "Content-Transfer-Encoding: binary\n\n";
        $data_string .= $data['content']."\n";
        $data_string .= "--$boundary--\n";
      }
      
      $request .= "Content-length: ".strlen($data_string)."\n";
      $request .= "\n";
      $request .= $data_string;
    }
   
    $fp = @fsockopen ($Host_protocol.$URL_Info["host"], $URL_Info["port"]);
    
    $result = "";
          
    if ($fp)
    {
      @fputs ($fp, $request);
      
      while (!feof ($fp)) 
      {
        $result .= @fgets ($fp, 128);
      }
      
      // remove header information from the xml/html-document
      if (strpos ($result, "<") > 0) $result = substr ($result, strpos ($result, "<"), strrpos ($result, ">") - strpos ($result, "<") + 1);
      
      @fclose ($fp);
    }
    else $result = false;
    
    return $result;
  }
  else return false;
}

// ---------------------- HTTP_Get -----------------------------
// function: HTTP_Get()
// input: URL[string], $data[array] (raw data) (optional), content-type[string excl. charset] (optional), character set[string] (optional)
// output: http response [string] / false on error

// description:
// Sends data via http get and returns response

function HTTP_Get ($URL, $data="", $contenttype="application/x-www-form-urlencoded", $charset="UTF-8") 
{
  if ($URL != "" && substr_count ($URL, "://") > 0)
  {
    // parsing the given URL
    $URL_Info = parse_url ($URL);

    // making string from $data 
    if (is_array ($data))
    {
      foreach ($data as $key => $value) $values[] = $key."=".urlencode ($value);    
      $data_string = implode ("&", $values);
    }
  
    // Find out which port is needed - if not given use standard (=80)
    if(!isset ($URL_Info["port"])) $URL_Info["port"] = 80;
  
    // building POST-request:
    $request .= "GET ".$URL_Info["path"].$data_string." HTTP/1.1\n";

    $request .= "Connection: close\n";
    $request .= "User-Agent: Mozilla/4.05C-SGI [en] (X11; I; IRIX 6.5 IP22)\n";
    $request .= "Cache-Control: no-cache\n";
    $request .= "Content-Type: ".$contenttype."; charset=".$charset."\r\n";
    
    if (strlen ($URL_Info['user']) > 0 && strlen($URL_Info['pass']) > 0) 
    {
      $authString = $URL_Info['user'].":".$URL_Info['pass'];
      $request .= "Authorization: Basic ".base64_encode($authString)."\r\n";
    }
    
    $request .= "\r\n";
  
    $fp = @fsockopen ($URL_Info["host"], $URL_Info["port"]);
    
    if ($fp)
    {
      // send request
      @fputs ($fp, $request);
      
      // get result
      while (!feof ($fp)) 
      {
        $result .= @fgets ($fp, 128);
      }
      
      // remove header information from the xml/html-document
      if (strpos ($result, "<") > 0) $result = substr ($result, strpos ($result, "<"), strrpos ($result, ">") - strpos ($result, "<") + 1);
      
      @fclose ($fp);
    }
    else $result = false;
    
    return $result;
  }
  else return false;
}

// ---------------------- HTTP_Proxy -----------------------------
// function: HTTP_Proxy()
// input: URL[string], enable post of files [true,false] (optional)
// output: http response [string] / false on error
// requires: PHP CURL

// description:
// Sends all global POST/GET and FILES data via http post and returns response

function HTTP_Proxy ($URL, $enable_file=false) 
{
  global $mgmt_config;
  
  if ($URL != "" && substr_count ($URL, "://") > 0 && !empty ($_REQUEST) && is_array ($_REQUEST))
  {    
    // define data to be posted/redirected
    $data = $_REQUEST;
  
    if (!empty ($_FILES['Filedata']['tmp_name']))
    {
      // add file and its contents to post data, prefix '@' is required (will cause additional traffic due to file upload to other server)
      if ($enable_file == true)
      {
        $data['Filedata'] = "@".realpath ($_FILES['Filedata']['tmp_name']);
      }
      // save file in temp directory and send identifier (recommended for better performance)
      else
      {
        // define temp file name
        $temp_file = "proxy_".uniqid();
        
        // save uploaded file
        $result_save = @move_uploaded_file ($_FILES['Filedata']['tmp_name'], $mgmt_config['abs_path_temp'].$temp_file);
        
        if ($result_save == false)
        {
          $result_save = @rename ($_FILES['Filedata']['tmp_name'], $mgmt_config['abs_path_temp'].$temp_file);
        }
        
        // add file info
        if ($result_save == true)
        {
          $data['proxy_file']['name'] = $_FILES['Filedata']['name'];
          $data['proxy_file']['link'] = $temp_file;
        }
        else
        {
          $errcode = "10991";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|uploaded file ".$_FILES['Filedata']['name']." could not be saved in temp directory";
          
          savelog (@$error);
        }
      }
    }
    
    
    // It is important to notice that when using curl to post form data and you use an array for CURLOPT_POSTFIELDS option, the post will be in multipart format
    // Setting CURLOPT_POSTFIELDS as follow produce a standard post header CURLOPT_POSTFIELDS => http_build_query ($data)
    
    $options = array(
        CURLOPT_POST           => true, // send a POST request 
        CURLOPT_RETURNTRANSFER => true, // to receive the response that the site gives after it receives the request
        CURLOPT_HEADER         => false, // return HTTP headers
        CURLOPT_ENCODING       => "", // handle all encodings
        CURLOPT_AUTOREFERER    => true, // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 15, // timeout on connect
        CURLOPT_MAXREDIRS      => 10, // stop after 10 redirects
        CURLOPT_HTTPHEADER     => array('Connection: close'), // close connection
        CURLOPT_POSTFIELDS     => http_build_query ($data)
    );

    // setup cURL
    $ch = curl_init ($URL);

    // set options
    curl_setopt_array ($ch, $options);

    // write and close session (important: curl_exec might hang otherwise)
    session_write_close();

    // send the request.
    $message = curl_exec ($ch);
    
    // start session again
    session_start();

    // get http response code after EXEC
    $info = curl_getinfo ($ch);
    $error_no = curl_errno ($ch);
    $error_message = curl_error ($ch);
    
    // close the cURL session
    curl_close ($ch);

    // on error
    if ($error_no > 0)
    {
      $errcode = "20921";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|HTTP_Proxy failed with error (".$error_no.") ".$error_message;
      
      savelog (@$error);
    }
    
    // define http header
    if (!empty ($info['http_code'])) $header = "HTTP/1.1 ".$info['http_code']." ".($info['http_code'] == 200 ? "OK" : "Internal Server Error");
    else $header = "HTTP/1.1 500 Internal Server Error";
  }
  // invalid input
  else
  {
    $header = "HTTP/1.1 500 Internal Server Error";
    $result['message'] = "Invalid input for PROXY service";
  }
  
  // return result
  $result = array();
  $result['header'] = $header;
  $result['message'] = $message;
    
  return $result;
}

// ================================= LOAD BALANCING =====================================

// ---------------------- loadbalancer -----------------------------
// function: loadbalancer()
// input: type [renderimage,rendervideo,uploadfile]
// output: http response [string] / false on error or if disabled
// requires: HTTP_Proxy

// description:
// Balances the load by sending all global POST/GET and FILES to one service ressource of a given array of service ressources.
// Don't define and use the same server ressources in $mgmt_config['url_path_service'], this can lead to an infinite loop.

function loadbalancer ($type) 
{
  global $mgmt_config;
  
  // if hyperCMS load balancer is used $mgmt_config['url_path_service'] must hold an array
  if (in_array ($type, array("renderimage", "rendervideo", "uploadfile")) && !empty ($mgmt_config['url_path_service']) && is_array ($mgmt_config['url_path_service']) && sizeof ($mgmt_config['url_path_service']) > 0)
  {
    // define service file
    if ($type == "renderimage") $file = "renderimage.php";
    elseif ($type == "rendervideo") $file = "rendervideo.php";
    elseif ($type == "uploadfile") $file = "uploadfile.php";
    
    // prepare service array
    $count = 0;
    
    foreach ($mgmt_config['url_path_service'] as $service)
    {
      if ($service != "")
      {
        $count++;
        $service_url[$count] = $service.$file;
      }
    }
    
    // select service ressource
    $balancer_id = getsession ("hcms_temp_balancer_id", 1);
    
    // save next balancer ID in session
    if ($balancer_id < $count) $next_id = $balancer_id + 1;
    else $next_id = 1;
    
    setsession ("hcms_temp_balancer_id", $next_id);
    
    // add session ID since the user has no session on servers providing the service for the PROXY
    if (session_id() != "") $_REQUEST['PHPSESSID'] = session_id();

    // use PROXY and return response
    $result = HTTP_Proxy ($service_url[$balancer_id]);

    // return header and message to uploader
    header ($result['header']);
    echo $result['message'];

    exit();
  }
  else return false;
}

// ================================= LOG FILE OPERATIONS =====================================

// --------------------------------------- savelog -------------------------------------------
// function: savelog()
// input: error messages array, name of log file without extension (optional)
// output: true / false on error

// description: adds new entries to log file
// An error entry must be formed like:
// date[YYYY-MM-DD hh:mm]|name of scipt file|error type: "error", "warning" or "information"|unique error code in script file|error message

function savelog ($error, $logfile="event")
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;
         
  if (is_array ($error) && sizeof ($error) > 0 && $logfile != "")
  {  
    // file name of event log
    $logfile = $logfile.".log";
    
    // replace newlines with tab space
    foreach ($error as &$value)
    {
      $value = str_replace ("\n\r", "\t", $value);
      $value = str_replace ("\r\n", "\t", $value);
      $value = str_replace ("\r", "\t", $value);
      $value = str_replace ("\n", "\t", $value);
    }
  
    if (is_file ($mgmt_config['abs_path_data']."log/".$logfile))
    { 
      return appendfile ($mgmt_config['abs_path_data']."log/", $logfile, implode ("\n", $error)."\n");
    }
    else
    {
      return savefile ($mgmt_config['abs_path_data']."log/", $logfile, implode ("\n", $error)."\n");
    }
  }  
  else return false;
}

// --------------------------------------- deletelog -------------------------------------------
// function: deletelog()
// input: logname (optional)
// output: result array

// description:
// Deletes the log file

function deletelog ($logname="")
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // set default language
  if ($lang == "") $lang = "en";
  
  // file name of event log
  if ($logname != "") $logfile = $logname.".log";
  else $logfile = "event.log";
  
  if (is_file ($mgmt_config['abs_path_data']."log/".$logfile))
  {
    $test = savefile ($mgmt_config['abs_path_data']."log/", $logfile, "");
  
    if ($test == true)
    {
      $add_onload = "parent.frames['mainFrame'].location='log_list.php'; ";
      $show = "<span class=hcmsHeadline>".$hcms_lang['cleared-all-events-from-list'][$lang]."</span>\n";
    }
    else
    {
      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['events-list-could-not-be-cleared'][$lang]."</span><br />\n".$hcms_lang['event-log-does-not-exist-or-you-do-not-have-write-permissions'][$lang]."\n";
    }  
  }
  else
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['events-list-could-not-be-cleared'][$lang]."</span><br />\n".$hcms_lang['event-log-does-not-exist-or-you-do-not-have-write-permissions'][$lang]."\n";
  }
  
  $result = array();
  
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;  
  
  return $result;    
}

// ---------------------- debuglog -----------------------------
// function: debuglog()
// input: code to write to debug file
// output: true / false

// description:
// Writes code lines into debug file in data/log/debug.log

function debuglog ($code)
{
  global $user, $mgmt_config, $hcms_lang, $lang;
  
  // save log
  if ($code != "")
  {  
    $code = "\r\n<debug>\r\n<timestamp>".$mgmt_config['today']."</timestamp>\r\n<code>".$code."</code>\r\n</debug>\r\n";
    
    if (is_file ($mgmt_config['abs_path_data']."log/debug.log")) return appendfile ($mgmt_config['abs_path_data']."log/", "debug.log", $code);
    else return savefile ($mgmt_config['abs_path_data']."log/", "debug.log", $code);  
  }
  else return false;
}

// ====================================== SPECIAL NOTIFICATIONS =========================================

// --------------------------------------- notifyusers -------------------------------------------
// function: notifyusers()
// input: publication name, location, object name, event name [oncreate,onedit,onmove,ondelete], user name
// output: true / false on error

// description:
// Notifies all users based on the given event and location

function notifyusers ($site, $location, $object, $event, $user_from)
{
  global $user, $mgmt_config, $hcms_lang_codepage, $hcms_lang, $lang;
  
  // set default language
  if ($lang == "") $lang = "en";
  
  // include hypermailer class
  if (!class_exists ("HyperMailer")) require ($mgmt_config['abs_path_cms']."function/hypermailer.class.php");
  
  if ($event != "" && valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($object) && valid_objectname ($user_from))
  {
    $mail_sent = false;
    
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";
    // convert location
    $cat = getcategory ($site, $location);
    $location_esc = convertpath ($site, $location, $cat);
    // get notifications
    $notify_array = rdbms_getnotification ($event, $location_esc.$object);
   
    if (is_array ($notify_array) && sizeof ($notify_array) > 0)
    {
      // load user file
      $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");
      
      if ($userdata != "")
      {
        $user_memory = array();
      
        // collect e-mail addresses
        foreach ($notify_array as $notify)
        {
          // dont notify the same user multiple times and don't inform the user if he took the action
          if (!empty ($notify['user']) && !in_array ($notify['user'], $user_memory) && $notify['user'] != $user_from)
          {        
            // get user node and extract required information    
            $usernode = selectcontent ($userdata, "<user>", "<login>", $notify['user']);
            
            // add user to memory to avoid multiple notifications for the same user
            $user_memory[] = $notify['user'];
  
            if (is_array ($usernode))
            {
              // email
              $temp = getcontent ($usernode[0], "<email>");
              if (!empty ($temp[0])) $email_to = $temp[0];
              else $email_to = "";
            
              // language
              $temp = getcontent ($usernode[0], "<language>");            
              if (!empty ($temp[0])) $lang_to = $temp[0];
              else $lang_to = "en";
            }

            // load language of user if it has not been loaded
            if (!empty ($lang_to) && empty ($hcms_lang['this-is-an-automatically-generated-mail-notification'][$lang_to]))
            {
              require_once ($mgmt_config['abs_path_cms']."language/".getlanguagefile ($lang_to));
            }

            if ($email_to != "")
            {
              // text options
              if ($event == "oncreate")
              {
                $text_opt = $hcms_lang['user-user-createduploaded-the-following-object'][$lang_to];
                $object_name = getlocationname ($site, $location_esc.$object, $cat);
                $accesslink = createaccesslink ($site, $location_esc, $object, $cat, "", $notify['user'], "al");
              }
              elseif ($event == "onedit")
              {
                $text_opt = $hcms_lang['user-user-edited-the-following-object'][$lang_to];
                $object_name = getlocationname ($site, $location_esc.$object, $cat);
                $accesslink = createaccesslink ($site, $location_esc, $object, $cat, "", $notify['user'], "al");
              }
              elseif ($event == "onmove")
              {
                $text_opt = $hcms_lang['user-user-moved-the-following-object'][$lang_to];
                $object_name = getlocationname ($site, $location_esc.$object, $cat);
                $accesslink = createaccesslink ($site, $location_esc, $object, $cat, "", $notify['user'], "al");
              }
              elseif ($event == "ondelete")
              {
                $text_opt = $hcms_lang['user-user-deleted-the-following-object'][$lang_to];
                $object_name = getlocationname ($site, $location_esc.$object, $cat);
                $accesslink = "";
              }
            
              // mail notification
              $mail_title = $hcms_lang['hypercms-notification'][$lang_to];
              $mail_fullbody = "<b>".str_replace ("%user%", $user_from, $text_opt)."</b>\n";
              $mail_fullbody .= $mgmt_config['today']." ";
              if ($cat == "comp") $mail_fullbody .= $hcms_lang['in-assets'][$lang_to];
              elseif ($cat == "page") $mail_fullbody .= $hcms_lang['in-pages'][$lang_to];
              $mail_fullbody .= ": ".$object_name;
              if ($accesslink != "") $mail_fullbody .=  " (".$accesslink.")";          
              $mail_fullbody .= "\n\n".$hcms_lang['this-is-an-automatically-generated-mail-notification'][$lang_to];
              $mail_fullbody = "<span style=\"font-family:Verdana, Arial, Helvetica, sans-serif; font-size:14px;\">".$mail_fullbody."</span>";
          
              $mailer = new HyperMailer();
             
              // if the mailserver config entry is empty, the email address of the user will be used for FROM
              $mailer->IsHTML(true);
              $mailer->CharSet = $hcms_lang_codepage[$lang_to]; 
              if (!empty ($mgmt_config[$site]['mailserver'])) $mailer->From = "automailer@".$mgmt_config[$site]['mailserver'];
              else $mailer->From = "automailer@hypercms.net";
              $mailer->FromName = "hyperCMS Automailer";
              $mailer->AddAddress ($email_to);
              $mailer->Subject = html_decode ($mail_title, $hcms_lang_codepage[$lang_to]);
              $mailer->Body = html_decode (nl2br ($mail_fullbody), $hcms_lang_codepage[$lang_to]);
             
              // send mail
              if ($mailer->Send())
              {
                $mail_sent = true;
                
                // log notification
                $errcode = "00802";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|info|$errcode|notification has been sent to ".$notify['user']." (".$email_to.") on object ".$location_esc.$object; 
              }
              else
              {
                $errcode = "50802";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|notification failed for ".$notify['user']." (".$email_to.") on object ".$location_esc.$object." (mail could not be sent)";  
              }
            }
          }
        }

        // save log
        savelog (@$error);
        
        return $mail_sent;
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// --------------------------------------- sendlicensenotification -------------------------------------------
// function: sendlicensenotification()
// input: publication name, category [page,comp], folder path, text ID for text field, search from date (YYYY-MM-DD), search till date (YYYY-MM-DD), user name string or array (optional), 
//        date format (optional), 
// output: true / false on error

// description:
// Searches for objects with a date in a defined text field that has to be between the defined date limits and sends a message to the defined users.
// This is a helper function for function licensenotification.

function sendlicensenotification ($site, $cat, $folderpath, $text_id, $date_begin, $date_end, $user, $format="%Y-%m-%d")
{
  global $eventsystem, $mgmt_config, $hcms_lang_codepage, $hcms_lang, $lang;
  
  // set default language
  if ($lang == "") $lang = "en";
  
  // include hypermailer class
  if (!class_exists ("HyperMailer")) require ($mgmt_config['abs_path_cms']."function/hypermailer.class.php");
  
  if (valid_publicationname ($site) && $cat != "" && valid_locationname ($folderpath) && valid_objectname ($text_id) && $date_begin != "" && $date_end != "" && (valid_objectname ($user) || is_array ($user)))
  {
    $mail_sent = false;
    
    // convert path
    $folderpath = convertpath ($site, $folderpath, $cat);
    
    // query license date
    $result_array = rdbms_licensenotification ($folderpath, $text_id, $date_begin, $date_end, $format);

    if (is_array ($result_array))
    {
      // load user data
      $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");
      
      if (!is_array ($user)) $user_array = array($user);
      else $user_array = $user;
         
      if ($userdata != false && is_array ($user))
      {
        foreach ($user_array as $user)
        { 
          // get user node and extract required information
          $mail_receiver_array = selectcontent ($userdata, "<user>", "<login>", $user);
            
          if ($mail_receiver_array != false)
          {     
            // email
            $buffer_array = getcontent ($mail_receiver_array[0], "<email>");
            
            if ($buffer_array != false && $buffer_array[0] != "") $email_to = $buffer_array[0];
            else $email_to = "";
            
            // language
            $buffer_array = getcontent ($mail_receiver_array[0], "<language>");
            
            if ($buffer_array != false && $buffer_array[0] != "") $lang_to = $buffer_array[0];
            else $lang_to = "en";
            
            if ($email_to != "")
            {
              // load language of user if it has not been loaded
              if (!empty ($lang_to) && empty ($hcms_lang['hypercms-warning-regarding-copyrights'][$lang_to]))
              {
                require_once ($mgmt_config['abs_path_cms']."language/".getlanguagefile ($lang_to));
              }

              // mail notification
              $mail_title = $hcms_lang['hypercms-warning-regarding-copyrights'][$lang_to];
              $mail_fullbody = "<b>".$hcms_lang['the-following-copyrights-are-due-shortly'][$lang_to]."</b>\n";
              
              foreach ($result_array as $result)
              { 
                $result['link'] = createaccesslink ($result['publication'], $result['location'], $result['object'], $result['category'], "", $user, "al");
                $mail_fullbody .= $result['date'].": ".$result['link']."\n";
              }
              
              $mail_fullbody .= "\n".$hcms_lang['this-is-an-automatically-generated-mail-notification'][$lang_to];              
              $mail_fullbody = "<span style=\"font-family:Verdana, Arial, Helvetica, sans-serif; font-size:14px;\">".$mail_fullbody."</span>";
             
              $mailer = new HyperMailer();
             
              // if the mailserver config entry is empty, the email address of the user will be used for FROM
              $mailer->IsHTML(true);
              $mailer->CharSet = $hcms_lang_codepage[$lang]; 
              if (!empty ($mgmt_config[$site]['mailserver'])) $mailer->From = "automailer@".$mgmt_config[$site]['mailserver'];
              else $mailer->From = "automailer@hypercms.net";
              $mailer->FromName = "hyperCMS Automailer";
              $mailer->AddAddress ($email_to);
              $mailer->Subject = html_decode ($mail_title, $hcms_lang_codepage[$lang]);
              $mailer->Body = html_decode (nl2br ($mail_fullbody), $hcms_lang_codepage[$lang]);
             
              // send mail
              if ($mailer->Send())
              {
                $mail_sent = true;
                
                // log notification
                $errcode = "00900";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|$errcode|license notification was sent to $email_to for $folderpath, $text_id, $date_begin, $date_end, $user";
              }
              else
              {
                $errcode = "50902";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|license notification failed for $folderpath, $text_id, $date_begin, $date_end, $user (mail could not be sent)";  
              }
            }
            else
            {
              $errcode = "50903";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|license notification failed for $folderpath, $text_id, $date_begin, $date_end, $user (e-mail address does not exist)";  
            }
          }
          else
          {
            $errcode = "50904";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|license notification failed for $folderpath, $text_id, $date_begin, $date_end, $user (user does not exist)";  
          }
        }
      }
      else return false;

      // save log
      savelog (@$error); 
      
      return $mail_sent;
    }
    else return false;    
  }
  else return false;
}

// --------------------------------------- licensenotification -------------------------------------------
// function: licensenotification()
// input: % 
// output: true / false on error

// description:
// This function reads the license notification configuration and looks up all objects with a date in a defined text field 
// that has to be between the defined date limits and sends a message to the defined users.

function licensenotification ()
{
  global $eventsystem, $mgmt_config, $hcms_lang_codepage, $hcms_lang, $lang;
  
  // license notification configuration file
  $config_dir = opendir ($mgmt_config['abs_path_data']."config/");
      
  if ($config_dir)
  {
    while ($file = @readdir ($config_dir))
    {
      if (strpos ($file, ".msg.dat") > 0 && is_file ($mgmt_config['abs_path_data']."config/".$file))
      {
        // load config file
        $config_array = file ($mgmt_config['abs_path_data']."config/".$file);
        
        if (is_array ($config_array) && sizeof ($config_array) > 0)
        {
          sort ($config_array);
        
          foreach ($config_array as $config_folder)
          {
            $date_begin = "";
            $date_end = "";
          
            list ($object_id, $text_id, $format, $period, $users) = explode ("|", $config_folder);
             
            $location = rdbms_getobject ($object_id);

            // define format string (international date format that is used for queries in the database)
            $format_db = "Y-m-d";

            if ($location != "" && $text_id != "" && $period != "" && $users != "")
            {
              // for each first day of the month
              if ($period == "monthly" && date ("d", time()) == "01") 
              {
                // current month plus 1 month
                $month = intval (date ("m", time())) + 1;
                // current year
                $year = intval (date ("Y", time()));
                // correct month and year
                if ($month == 13)
                {
                  $month = 1;
                  $year = $year + 1;
                }      
                // 1st day of month
                $date_begin = date ($format_db, mktime (0, 0, 0, $month, 1, $year));
                // one month later
                $date_end = date ($format_db, mktime (0, 0, 0, ($month + 1), 0, $year));
              }
              // for each sunday
              elseif ($period == "weekly" && strtolower (date ("D", time())) == "sun") 
              {
                // one week later
                $date_begin = date ($format_db, time() + (60*60*24*7));
                // two weeks later
                $date_end = date ($format_db, time() + (60*60*24*14));
              }
              // for each day
              elseif ($period == "daily") 
              {
                // tomorrow
                $date_end = $date_begin = date ($format_db, time() + (60*60*24));
              }
              
              // split users into array
              $user_array = splitstring ($users);
            
              // send notifications tu users
              if ($date_begin != "" && $date_end != "")
              {
                // .folder object must be removed!
                $site = getpublication ($location);
                $cat = getcategory ($site, $location);
                $location = getlocation ($location);

                sendlicensenotification ($site, $cat, $location, $text_id, $date_begin, $date_end, $user_array, $format);
              }
            }
          }
        }
      }
    }
    
    return true;
  }
  else
  {
    $errcode = "10742";
    $error[] = $mgmt_config['today']."|daily.php|error|$errcode|license notification can not be executed. Config directory is missing.";
    
    // save log
    savelog (@$error); 
    
    return false;
  }
}

// ====================================== TEXT DIFF =========================================

// --------------------------------------- html_diff -------------------------------------------
// function: html_diff ()
// input: old text, new text, maximum words to compare
// output: result text showing deleted and inserted words/differences / false on error

// description:
// Paul's Simple Diff Algorithm v 0.1
// Function html_diff is a wrapper for the diff command, it takes two strings and 
// returns the differences in HTML. The tags used are <ins> and <del>,
// which can easily be styled with CSS. 

function string_diff ($old, $new, $maxwords=1600)
{
  if (is_array ($old) && is_array ($new))
  {
    $maxlen = 0;
            
    foreach ($old as $oindex => $ovalue)
    {
      if (strlen ($ovalue) > 0 && !in_array ($ovalue, array("&nbsp;", "\r\n", "\n", "\r", "\t")) && $oindex <= $maxwords)
      {
        $nkeys = array_keys ($new, $ovalue);
        
        $counter = 0;
        
        foreach ($nkeys as $nindex)
        {
          if ($counter < 1000)
          {
            $matrix[$oindex][$nindex] = isset ($matrix[$oindex - 1][$nindex - 1]) ? $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
            
            if ($matrix[$oindex][$nindex] > $maxlen)
            {
              $maxlen = $matrix[$oindex][$nindex];
              $omax = $oindex + 1 - $maxlen;
              $nmax = $nindex + 1 - $maxlen;
            }
            
            $counter++;
          }
          else
          {
            break;
          }
        }
      }	
    }

    if ($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
    
    return array_merge
    (
      string_diff (array_slice ($old, 0, $omax), array_slice ($new, 0, $nmax)),
      array_slice ($new, $nmax, $maxlen),
      string_diff (array_slice($old, $omax + $maxlen), array_slice ($new, $nmax + $maxlen))
    );
  }
  else return false;
}
  
function html_diff ($old, $new)
{
  $diff = string_diff (explode (' ', $old), explode (' ', $new));
  $result = "";
  
  foreach ($diff as $k)
  {
    if (is_array($k))
    {
      $result .= (!empty ($k['d']) ? "<del>".implode (' ', $k['d'])."</del> ":'').
      (!empty ($k['i']) ? "<ins>".implode (' ', $k['i'])."</ins> ":'');
    }
    else $result .= $k . ' ';
  }
  
  return $result;
}

// ====================================== FAVORITES =========================================

// --------------------------------------- createfavorite -------------------------------------------
// function: createfavorite ()
// input: publication name (optional), location (optional), object name (optional), identifier (object ID, object hash) (optional), user name
// output: true / false

function createfavorite ($site="", $location="", $page="", $id="", $user)
{
  global $mgmt_config;
  
  if (valid_objectname ($user))
  {
    $dir = $mgmt_config['abs_path_data']."checkout/";
    $file = $user.".fav"; 
    
    if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page))
    {
      // define category if undefined
      if ($cat == "") $cat = getcategory ($site, $location);
      
      // add slash if not present at the end of the location string
      if (substr ($location, -1) != "/") $location = $location."/";  
      
      if (substr_count ($location, "%page%") != 1 && substr_count ($location, "%comp%") != 1) $location = convertpath ($site, $location, $cat);
      
      $object_id = rdbms_getobject_id ($location.$page);
    }
    // object ID or hash
    elseif ($id != "")
    {
      // object ID
      if (is_numeric ($id)) $object_id = $id;
      // object path or hash
      else $object_id = rdbms_getobject_id ($id);
    }
    
    // save object id in favorite file
    if ($object_id > 0)
    {
      if (is_file ($dir.$file))
      {
        $data = loadfile ($dir, $file);
        
        if ($data != false && trim ($data) != "") $data = str_replace ("|".$object_id."|", "|", $data).$object_id."|";
        else $data = "|".$id."|";
        
        return savefile ($dir, $file, $data);
      }
      else
      {
        return savefile ($dir, $file, "|".$object_id."|");
      }
    }
    else return false;
  }
  else return false;
}

// --------------------------------------- getfavorites -------------------------------------------
// function: getfavorites ()
// input: user name, output [path,id] (optional)
// output: object path or id array of users favorites / false

function getfavorites ($user, $output="path")
{
  global $mgmt_config;
  
  if (valid_objectname ($user))
  {
    $dir = $mgmt_config['abs_path_data']."checkout/";
    $file = $user.".fav";
    
    if (is_file ($dir.$file))
    {
      $data = loadfile ($dir, $file);
      
      if ($data != false && trim ($data) != "")
      {
        $data = trim ($data, "|");
        $object_id_array = explode ("|", $data);
        
        if (is_array ($object_id_array))
        {
          if (strtolower ($output) == "id")
          {
            sort ($object_id_array);
            return $object_id_array;
          }
          else
          {
            $object_path_array = array();
            
            foreach ($object_id_array as $object_id)
            {
              if ($object_id != "")
              {
                $object_path = rdbms_getobject ($object_id);
                if (!empty ($object_path)) $object_path_array[] = $object_path;
              }
            }
            
            if (sizeof ($object_path_array) > 0)
            {
              natcasesort ($object_path_array);
              return $object_path_array;
            }
            else return false;
          }
        }
        else return false;
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// --------------------------------------- deletefavorite -------------------------------------------
// function: deletefavorite ()
// input: publication name (optional), location (optional), object name (optional), identifier (object ID, object hash) (optional), user name
// output: true / false

function deletefavorite ($site="", $location="", $page="", $id="", $user)
{
  global $mgmt_config;
  
  if (valid_objectname ($user))
  {
    $dir = $mgmt_config['abs_path_data']."checkout/";
    $file = $user.".fav"; 
    
    if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page))
    {
      // define category if undefined
      $cat = getcategory ($site, $location);
      
      // add slash if not present at the end of the location string
      if (substr ($location, -1) != "/") $location = $location."/";  
      
      if (substr_count ($location, "%page%") != 1 && substr_count ($location, "%comp%") != 1) $location = convertpath ($site, $location, $cat);
      
      $object_id = rdbms_getobject_id ($location.$page);
    }
    // object ID or hash
    elseif ($id != "")
    {
      // object ID
      if (is_numeric ($id)) $object_id = $id;
      // object path or hash
      else $object_id = rdbms_getobject_id ($id);
    }
    
    // remove object id from favorite file
    if ($object_id > 0)
    {
      if (is_file ($dir.$file))
      {
        $data = loadfile ($dir, $file);
        
        if ($data != false && trim ($data) != "") $data = str_replace ("|".$object_id."|", "|", $data);

        return savefile ($dir, $file, $data);
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// ====================================== HOME BOXES =========================================

// --------------------------------------- setboxes -------------------------------------------
// function: setboxes ()
// input: home box names as array or string, user name
// output: true / false

function setboxes ($name_array, $user)
{
  global $mgmt_config;
  
  if (valid_objectname ($user))
  {
    $dir = $mgmt_config['abs_path_data']."checkout/";
    $file = $user.".home.dat";
    
    // set input as array if it is string and not empty
    if (!is_array ($name_array))
    {
      $name_array = trim ($name_array, "|");
      
      // provided input has seperators
      if (substr_count ($name_array, "|") > 0)
      {
        $name_array = explode ("|", $name_array);
      }
      // provided input is single value as string
      else
      {
        $temp = $name_array;      
        $name_array = array();
        $name_array[0] = $temp;
      }
    }
    
    $data = "|";

    // save box in file
    foreach ($name_array as $name)
    {
      if (valid_objectname ($name) && is_file ($mgmt_config['abs_path_cms']."box/".$name.".inc.php"))
      {
        $data .= $name."|";
      }
    }
    
    // save file
    return savefile ($dir, $file, $data);
  }
  else return false;
}

// --------------------------------------- getboxes -------------------------------------------
// function: getboxes ()
// input: user name
// output: selected home box names of user as array / false

function getboxes ($user)
{
  global $mgmt_config;
  
  if (valid_objectname ($user))
  {
    $dir = $mgmt_config['abs_path_data']."checkout/";
    $file = $user.".home.dat";
    
    if (is_file ($dir.$file))
    {
      $data = loadfile ($dir, $file);
      
      if ($data != false && trim ($data) != "")
      {
        $data = trim ($data, "|");
        $name_array = explode ("|", $data);
        
        if (is_array ($name_array) && sizeof ($name_array) > 0)
        {
          return $name_array;
        }
        else return array();
      }
      else return array();
    }
    else return false;
  }
  else return false;
}

// ========================================== URL REWRITING =======================================

// ------------------------------------- rewrite_targetURI ------------------------------------------

// function: rewrite_targetURI ()
// input: publication name, text ID array (text-ID as key and URL paramaters as value), requested URI as string, exclude path as array (optional), 
//        rewrite type [none,forward,include] (optional)
// output: target URI / false on error

function rewrite_targetURI ($site, $text_id, $uri, $exclude_dir_esc="", $rewrite_type="include")
{
  global $mgmt_config, $publ_config;
  
  if (valid_publicationname ($site) && is_array ($text_id) && $uri != "")
  {
    $hypercms_session = array();
    
    // include publication target settings
    $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"); 
    
    foreach ($text_id as $id=>$parameter)
    {
      if ($id != "")
      {
        $search_textnode = array();
        $search_textnode[$id] = $uri;
        
        // search for objectpath for the provided permanenent link (only first valid result will be used, disable search log)
        $object_array = rdbms_searchcontent ("%page%/".$site."/", $exclude_dir_esc, "", "", "", "", $search_textnode, "", "", "", "", "", "", "", "", 1, false, false);
        
        if (is_array ($object_array))
        {
          // get first element of array
          $targetPath = reset ($object_array);
          
          if ($targetPath != "")
          {
            $targetFile = str_replace ("%page%/".$site."/", $publ_config['abs_publ_page'], $targetPath);
            $targetURI = str_replace ("%page%/".$site."/", $publ_config['url_publ_page'], $targetPath);

            // add paramaters
            if ($parameter != "")
            {
              // set parameter in hypercms array variable, so it can be set in session later
              if ($rewrite_type == "include")
              {
                parse_str ($parameter, $output);
                
                foreach ($output as $key=>$value) $hypercms_session[$key] = $value;
              }
              // set GET parameter
              else $targetURI .= "?".$parameter;
            }
            
            // remove domain
            $result = cleandomain ($targetURI);
            
            if ($result != "") break;
          }
        }
      }
    }

    if (!empty ($result))
    {
      // include page file
      if ($rewrite_type == "include" && is_file ($targetFile))
      {
        include ($targetFile);
      }
      // URL forwarding
      elseif ($rewrite_type == "forward")
      {
        header ("Location:".$result);
      }

      return $result;
    }
    else return false;
  }
  else return false;
}

// ------------------------------------- rewrite_homepage ------------------------------------------

// function: rewrite_homepage ()
// input: publication name, rewrite type [none,forward] (optional)
// output: target URI / false on error

// description:
// Uses the page root directory of the publication configuration and forwards to the default index page. No page include supported!

function rewrite_homepage ($site, $rewrite_type="forward")
{
  global $mgmt_config, $publ_config;
  
  if (valid_publicationname ($site))
  {
    // include publication target settings
    $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"); 
    
    // remove domain
    $result = cleandomain ($publ_config['url_publ_page']);
    $targetFile = $publ_config['abs_publ_page'];
    
    if (!empty ($result))
    {
      // URL forwarding
      if ($rewrite_type == "forward")
      {
        header ("Location:".$result);
      }
      
      return $result;
    }
  }
  else return false;
}

// ------------------------------------- create_csv ------------------------------------------

// function: create_csv ()
// input: associative data array, file name (optonal), file path for saving the CSV file (optional), delimiter (optional), enclosure (optional), character set (optional)
// output: true / false on error

// description:
// Creates a CSV file from an associative data array and returns the file as download or writes the file to the file system if a valid path to a directory has been provided

function create_csv ($assoc_array, $filename="export.csv", $filepath="php://output", $delimiter=";", $enclosure='"', $charset="utf-8")
{
  if (!is_dir ($filepath))
  {
    ob_clean();
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private', false);
    header('Content-Type: text/csv; charset='.$charset);
    header('Content-Disposition: attachment;filename='.$filename);
  }
  
  if (isset ($assoc_array['0']))
  {
    $fp = fopen ($filepath, 'w');
    
    if ($fp)
    {
      fputcsv ($fp, array_keys ($assoc_array['0']), $delimiter, $enclosure);
      
      foreach ($assoc_array as $values)
      {
        fputcsv ($fp, $values, $delimiter, $enclosure);
      }
      
      fclose ($fp);
    }
    else return false;
  }
  
  if (!is_dir ($filepath))
  {
    ob_flush ();
  }
  
  return true;
}

// ---------------------------------------------- sendmessage ----------------------------------------------
// function: sendmessage()
// input: from_user name, to_user name, title, message, object ID or object path (optional)
// output: true/false
// requires: config.inc.php

// description:
// Sends a message via e-mail to a user

function sendmessage ($from_user, $to_user, $title, $message, $object_id="")
{
  global $mgmt_config, $hcms_lang_codepage, $hcms_lang, $lang;
  
  // include hypermailer class
  if (!class_exists ("HyperMailer")) include_once ($mgmt_config['abs_path_cms']."function/hypermailer.class.php");  

  if ($from_user != "" && $to_user != "" && $title != "" && strlen ($title) < 360 && $message != ""  && strlen ($message) < 3600)
  {
    $result = false;
    $object_link = "";
    
    // get local date today (jjjj-mm-dd hh:mm)
    $mgmt_config['today'] = date ("Y-m-d H:i", time());

    // try to get object_id from object path
    if ($object_id != "" && intval ($object_id) < 1)
    {
      // convert object path if necessary
      if ($site != "") $object_id = convertpath ($site, $object_id, "");
      
      // get object id
      $object_id = rdbms_getobject_id ($object_id);
      
      // object link
      $object_link = createaccesslink ("", "", "", "", $object_id, $to_user, "al");
    }

    // load user file
    $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");
    
    // get e-mail and language of user
    if ($userdata != "")
    {       
      // get user node and extract required information    
      $usernode = selectcontent ($userdata, "<user>", "<login>", $from_user);

      if (!empty ($usernode[0]))
      {
        // email
        $temp = getcontent ($usernode[0], "<email>");
        if (!empty ($temp[0])) $from_email = $temp[0];
        else $from_email = "";

        // language
        $temp = getcontent ($usernode[0], "<language>");            
        if (!empty ($temp[0])) $from_lang = $temp[0];
        else $from_lang = "en";
      }
      
      // get user node and extract required information    
      $usernode = selectcontent ($userdata, "<user>", "<login>", $to_user);

      if (!empty ($usernode[0]))
      {
        // email
        $temp = getcontent ($usernode[0], "<email>");
        if (!empty ($temp[0])) $to_email = $temp[0];
        else $to_email = "";
 
        // language
        $temp = getcontent ($usernode[0], "<language>");            
        if (!empty ($temp[0])) $to_lang = $temp[0];
        else $to_lang = "en";
      }
    }
  
    // send e-mail to user
    if (!empty ($to_email))
    {
      // load language of user if it has not been loaded
      if (!empty ($to_lang) && empty ($hcms_lang['new-task-from-user'][$to_lang]))
      {
        require_once ($mgmt_config['abs_path_cms']."language/".getlanguagefile ($to_lang));
      }

      // email schema
      if ($from_email != "") $email_schema = " [<a href='mailto:".$from_email."'>".$from_email."</a>]";
      else $email_schema = "";
      
      $body = "
  <span style=\"font-family:Verdana, Arial, Helvetica, sans-serif; font-size:14px;\">
    <b>".$hcms_lang['message'][$to_lang]." ".$hcms_lang['from'][$to_lang]." '".$from_user."'".$email_schema."</b><br/><br/>
    ".nl2br ($message)."<br/><br/>
    ".$object_link."
  </span>";
  
      $mailer = new HyperMailer();
      $mailer->IsHTML(true);
      $mailer->AddAddress ($to_email, $to_user);
      $mailer->AddReplyTo ($from_email, $from_user);
      $mailer->From = $from_email;
      $mailer->Subject = "hyperCMS: ".$title;
      $mailer->CharSet = $hcms_lang_codepage[$to_lang];
      $mailer->Body = html_decode ($body, $hcms_lang_codepage[$to_lang]);
      
      // send mail
      if ($mailer->Send())
      {
        $errcode = "00202";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|info|$errcode|message has been sent to ".$to_user." (".$to_email.")";
        $result = true;
      }
      else
      {
        $errcode = "50202";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|$errcode|message could not be sent to ".$to_user." (".$to_email.")";  
      }

      // save log
      savelog (@$error);
    }
    
    return $result;
  }
  else return false;
}
?>