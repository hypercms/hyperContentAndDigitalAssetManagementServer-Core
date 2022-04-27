<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */
 
// ====================================== MAIN FUNCTIONS ========================================

// ========================================== NUMBERS ===========================================

// ------------------------------------- correctnumber ------------------------------------------

// function: correctnumber ()
// input: formated number  [string]
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
// input: text [string or array], character set [string] (optional)
// output: cleaned text / false on error

// description:
// Removes all HTML tags, scripts and other special characters from the content in order to create a plain text

function cleancontent ($text, $charset="UTF-8")
{
  global $mgmt_config;

  // list of preg* regular expression patterns to search for, used in conjunction with $replace
  $search = array(
    "/\r/",                                  // Non-legal carriage return
    "/[\n\t]+/",                             // Newlines and tabs
    '/<head[^>]*>.*?<\/head>/i',             // <head>
    '/<script[^>]*>.*?<\/script>/i',         // <script>s -- which strip_tags supposedly has problems with
    '/<style[^>]*>.*?<\/style>/i',           // <style>s -- which strip_tags supposedly has problems with
    '/<p[^>]*>/i',                           // <P>
    '/<br[^>]*>/i',                          // <br>
    '/<i[^>]*>(.*?)<\/i>/i',                 // <i>
    '/<em[^>]*>(.*?)<\/em>/i',               // <em>
    '/(<ul[^>]*>|<\/ul>)/i',                 // <ul> and </ul>
    '/(<ol[^>]*>|<\/ol>)/i',                 // <ol> and </ol>
    '/(<dl[^>]*>|<\/dl>)/i',                 // <dl> and </dl>
    '/<li[^>]*>(.*?)<\/li>/i',               // <li> and </li>
    '/<dd[^>]*>(.*?)<\/dd>/i',               // <dd> and </dd>
    '/<dt[^>]*>(.*?)<\/dt>/i',               // <dt> and </dt>
    '/<li[^>]*>/i',                          // <li>
    '/<hr[^>]*>/i',                          // <hr>
    '/<div[^>]*>/i',                         // <div>
    '/(<table[^>]*>|<\/table>)/i',           // <table> and </table>
    '/(<tr[^>]*>|<\/tr>)/i',                 // <tr> and </tr>
    '/<td[^>]*>(.*?)<\/td>/i',               // <td> and </td>
    '/<span class="_html2text_ignore">.+?<\/span>/i'  // <span class="_html2text_ignore">...</span>
  );

  // list of pattern replacements corresponding to patterns searched
  $replace = array(
    '',                                     // Non-legal carriage return
    ' ',                                    // Newlines and tabs
    '',                                     // <head>
    '',                                     // <script>s -- which strip_tags supposedly has problems with
    '',                                     // <style>s -- which strip_tags supposedly has problems with
    "\n\n",                                 // <P>
    "\n",                                   // <br>
    '_\\1_',                                // <i>
    '_\\1_',                                // <em>
    "\n\n",                                 // <ul> and </ul>
    "\n\n",                                 // <ol> and </ol>
    "\n\n",                                 // <dl> and </dl>
    "\t* \\1\n",                            // <li> and </li>
    " \\1\n",                               // <dd> and </dd>
    "\t* \\1",                              // <dt> and </dt>
    "\n\t* ",                               // <li>
    "\n-------------------------\n",        // <hr>
    "<div>\n",                              // <div>
    "\n\n",                                 // <table> and </table>
    "\n",                                   // <tr> and </tr>
    "\t\t\\1\n",                            // <td> and </td>
    ""                                      // <span class="_html2text_ignore">...</span>
  );

  // list of preg* regular expression patterns to search for, used in conjunction with $entReplace
  $entSearch = array(
    '/&(nbsp|#160);/i',                      // Non-breaking space
    '/&(quot|rdquo|ldquo|#8220|#8221|#147|#148);/i', // Double quotes
    '/&(apos|rsquo|lsquo|#8216|#8217);/i',   // Single quotes
    '/&gt;/i',                               // Greater-than
    '/&lt;/i',                               // Less-than
    '/&(copy|#169);/i',                      // Copyright
    '/&(trade|#8482|#153);/i',               // Trademark
    '/&(reg|#174);/i',                       // Registered
    '/&(mdash|#151|#8212);/i',               // mdash
    '/&(ndash|minus|#8211|#8722);/i',        // ndash
    '/&(bull|#149|#8226);/i',                // Bullet
    '/&(pound|#163);/i',                     // Pound sign
    '/&(euro|#8364);/i',                     // Euro sign
    '/&(amp|#38);/i'                         // Ampersand
    );

  // list of pattern replacements corresponding to patterns searched
  $entReplace = array(
    ' ',                                    // Non-breaking space
    '"',                                    // Double quotes
    "'",                                    // Single quotes
    '>',                                    // Greater-than
    '<',                                    // Less-than
    '©',                                    // Copyright
    '™',                                    // Trademark
    '®',                                    // Registered
    '—',                                    // mdash
    '-',                                    // ndash
    '*',                                    // Bullet
    '£',                                    // Pound sign
    '€',                                    // Euro sign
    '&'                                     // Ampersand
  );

  // replace characters
  if ($text != "")
  {
    if (is_string ($text))
    {
      // clean up all tags
      $text = preg_replace ($search, $replace, $text);
      $text = strip_tags ($text);
      $text = preg_replace ($entSearch, $entReplace, $text);

      // decode characters
      if ($charset != "") $text = html_decode ($text, $charset);
  
      // removed in version 8.0.6: $text = str_replace (array(".....", "....", "...", ".."), ".", $text);
      // removed in version 8.0.6: $text = str_replace (array(",,,,,", ",,,,", ",,,", ",,"), ",", $text);
      // removed in version 8.0.6: $text = str_replace (array("_____", "____", "___", "__"), "_", $text);

      // double quotes and line feeds
      $text = str_replace (array("&quot;", "&#xA;", "&#10;"), array("\"", "\n", "\n"), $text);

      // multiple free spaces
      $text = preg_replace ('/\s+/', " ", $text);
    }
    elseif (is_array ($text))
    {
      foreach ($text as &$value)
      {
        $value = cleancontent ($value, $charset);
      }
    }

    if (!empty ($text)) return $text;
    else return false;
  }
  else return false;
}

// ------------------------------------- remove_utf8_bom ------------------------------------------

// function: remove_utf8_bom ()
// input: text  [string]
// output: cleaned text / false on error

// description:
// Remove UTF-8 BOM sequences

function remove_utf8_bom ($text)
{
  $bom = pack ('H*','EFBBBF');
  $text = preg_replace ("/^$bom/", "", $text);

  if (!empty ($text)) return $text;
  else return false;
}

// ------------------------------------- convert_utf8 ------------------------------------------

// function: convert_utf8 ()
// input: expression [string or array]
// output: UTF-8 converted expression / original expression on error

function convert_utf8 ($in)
{
  if (is_array ($in))
  {
    foreach ($in as $key => $value)
    {
      $out[convert_utf8($key)] = convert_utf8 ($value);
    }
  }
  elseif (is_string ($in))
  {
    if (function_exists ("mb_detect_encoding") && mb_detect_encoding ($in) != "UTF-8") return utf8_encode ($in);
    elseif (!is_utf8 ($in)) return utf8_encode ($in);
    else return $in;
  }
  else
  {
    return $in;
  }
  
  return $out;
}

// ------------------------------------- convert_unicode2utf8 ------------------------------------------

// function: convert_unicode2utf8 ()
// input: expression [string]
// output: UTF-8 converted expression / original expression on error

// description:
// This function is used to correct files names that have been encoded by ZIP.

function convert_unicode2utf8 ($in)
{
  if (is_string ($in))
  {
    $out = str_replace ('#U', '\u', $in);
    $out = str_replace ("\n", "<br>", $out);
    $temp = json_decode ('["'.$out.'"]');

    if (!empty ($temp[0])) $out = str_replace ("<br>", "\n", $temp[0]);
    else $out = $in;

    return $out;
  }
  else return $in;
}

// ------------------------------------- convertchars ------------------------------------------

// function: convertchars ()
// input: expression [string or array], input character set [string] (optional), output character set [string] (optional)
// output: converted expression / original expression on error

function convertchars ($expression, $charset_from="UTF-8", $charset_to="UTF-8")
{
  global $mgmt_config;

  if ($expression != "" && $charset_to != "")
  {
    $expression_orig = $expression;

    // detect character set if not provided
    if (function_exists ("mb_detect_encoding") && $charset_from == "")
    {
      if (!is_array ($expression))
      {
        $charset_from = mb_detect_encoding ($expression, mb_detect_order(), true);
      }
      elseif (is_array ($expression))
      {
        $charset_from = mb_detect_encoding (implode ("", $expression), mb_detect_order(), true);
      }
    }

    if ($charset_from != "")
    {
      // same character sets
      if (strtolower ($charset_from) == strtolower ($charset_to))
      {
        return $expression;
      }
      else
      {
        if (!is_array ($expression))
        {
          // verify character sets
          $valid_from = true;
          $valid_to = true;

          if (function_exists ("mb_list_encodings"))
          {
            $valid_charsets = mb_list_encodings ();
            $valid_charsets = array_map ('strtolower', $valid_charsets);

            if (is_array ($valid_charsets))
            {
              $valid_from = in_array (strtolower ($charset_from), $valid_charsets);
              $valid_to = in_array (strtolower ($charset_to), $valid_charsets);
            }
          }

          // convert
          if (function_exists ("mb_convert_encoding") && !empty ($valid_from) && !empty ($valid_to)) $expression = mb_convert_encoding ($expression, $charset_to, $charset_from);
          elseif (function_exists ("iconv") && !empty ($valid_from) && !empty ($valid_to)) $expression = iconv ($charset_from, $charset_to, $expression);
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
        else return $expression_orig;
      }
    }
    else return false;
  }
  else return false;
}

// ------------------------- specialchr -----------------------------
// function: specialchr()
// input: expression [string], list of characters to be excluded from search [string] (optional) 
// output: true/false

// description:
// Tests if an expression includes special characters (true) or does not (false).
// Allow characters through including them in $accept (e.g. #$...)

function specialchr ($expression, $accept="")
{
  if ($expression != "")
  {
    // escape chars:  . \ + * ? [ ^ ] $ ( ) { } = ! < > | : -
    $accept = preg_quote ($accept);

    // check if expression is on watch list 
    if (preg_match ("/^[a-zA-Z0-9".$accept."]+$/", $expression)) return false;
    else return true;
  }
  else return true;
}

// ------------------------- specialchr_encode -----------------------------
// function: specialchr_encode()
// input: expression [string], remove all special characters [yes,no]
// output: expression without special characters (for file names)

// description:
// Renames all special characters for file names to an expression according to given rules

function specialchr_encode ($expression, $remove="no")
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  if (is_string ($expression))
  {
    $expression_parts = array();
    $result_parts = array();

    // check if expression holds a path
    if (substr_count ($expression, "/") > 0)
    {
      $expression_parts = explode ("/", $expression);
    }
    else $expression_parts[0] = $expression;

    foreach ($expression_parts as $expression)
    {
      // verify conditions before encoding
      if ($expression != "" && specialchr ($expression, "~_-.") && $expression != "%comp%" && $expression != "%page%" && $expression != "%media%" && $expression != "%tplmedia%" && $expression != "%publication%" && $expression != "%object%")
      {
        // encode to UTF-8 if name is not utf-8 coded
        if (!is_utf8 ($expression)) $expression = utf8_encode (trim ($expression));

        // replace ~ since this is the identifier and replace invalid file name characters (symbols)
        $strip = array ("~", "%", "`", "!", "@", "#", "$", "^", "&", "*", "=", 
                        "\\", "|", ";", ":", "\"", "&quot;", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
                        "Ã¢â‚¬â€", "Ã¢â‚¬â€œ", ",", "<", "&lt;", ">", "&gt;", "?");
 
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
// input: expression [string]
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

// ------------------------- convertdate -----------------------------
// function: convertdate()
// input: date and time [string], time zone source [string], source date format [string] (optional), time zone target [string], target date format [string] (optional)
// output: converted date as tring / false

// description:
// This function converts a date to a different time zone and format.

function convertdate ($date, $timezone1="", $dateformat1="Y-m-d H:i:s", $timezone2="", $dateformat2="Y-m-d H:i:s")
{
  // recreate date 
  $date = date ($dateformat1, strtotime ($date));
  
  // verify input
  if (!empty ($date) && is_date ($date, $dateformat1) && in_array ($timezone1, timezone_identifiers_list()) && in_array ($timezone2, timezone_identifiers_list()))
  {
    // create DateTime object
    $result = DateTime::createFromFormat ($dateformat1, $date, new DateTimeZone ($timezone1));

    // convert timezone
    $result -> setTimeZone (new DateTimeZone ($timezone2));
    
    // convert dateformat
    return $result -> format ($dateformat2);
  }
  else return false;
}

// ------------------------- offsettime -----------------------------
// function: offsettime()
// input: %
// output: offset time in hh:mm from UTC

// description:
// This function calculates the offset time from UTC (Coordinated Universal Time).

function offsettime ()
{
  // set time zone
  $now = new DateTime();
  $mins = $now->getOffset() / 60;
  $sgn = ($mins < 0 ? -1 : 1);
  $mins = abs ($mins);
  $hrs = floor ($mins / 60);
  $mins -= $hrs * 60;

  return $offset = sprintf ('%+d:%02d', $hrs*$sgn, $mins);
} 

// ------------------------- object_exists -----------------------------
// function: object_exists()
// input: path to an object [string]
// output: true / false

// description:
// This function verifies if an object exists already.

function object_exists ($path)
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  if ($path != "")
  {
    // get file extension
    $file_ext = strrchr ($path, ".");

    $location = getlocation ($path);
    $file = getobject ($path);

    // transform special characters 
    if (substr ($location, 0, 7) == "%page%/" || substr ($location, 0, 7) == "%comp%/") $location = specialchr_encode ($location, "no");
    $file = specialchr_encode ($file, "no");

    // absolute path
    $location = deconvertpath ($location, "file");

    // recycled object exists
    if ($file_ext == ".recycle" && (is_file ($location.$file) || is_file ($location.substr ($file, -8)))) return true;

    // unpublished object exists
    if ($file_ext == ".off" && (is_file ($location.$file) || is_file ($location.substr ($file, -4)))) return true;

    // object file exists
    if (is_file ($location.$file) || is_file ($location.$file.".off") || is_file ($location.$file.".recycle")) return true;

    // folder exists
    if (is_dir ($location.$file) || is_file ($location.$file."/.folder") || is_file ($location.$file."/.folder.recycle")) return true;

    // does not exist
    return false;
  }
  else return false;
}

// ------------------------- is_utf8 -----------------------------
// function: is_utf8()
// input: expression [string]
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
// input: expression [string]
// output: if string is latin 1 encoded true / false otherwise

// description:
// This function is an alternative to mb_check_encoding (which requires the mbstring PHP extension).
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
// input: string with ";" or "," as seperator [string]
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

// ------------------------- is_folder -----------------------------
// function: is_folder()
// input: path to any object [string]
// output: true / false

// description:
// Checks if the provided path to an object is a folder

function is_folder ($path)
{
  global $mgmt_config;

  if ($path != "")
  {
    if (getobject ($path) == ".folder") return true;

    // deconvert path
    if (substr_count ($path, "%page%") == 1 || substr_count ($path, "%comp%") == 1)
    {
      $path = deconvertpath ($path, "file");
    }

    if (is_dir ($path)) return true;

    return false;
  }
  else return false;
}

// ------------------------- is_emptyfolder -----------------------------
// function: is_emptyfolder()
// input: path to folder [string]
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
    $scandir = scandir ($dir);

    foreach ($scandir as $entry)
    {
      if ($entry != "." && $entry != ".." && $entry != ".folder" && substr ($entry, -4) != ".off") return false;
    }

    return true;
  }
  else return false;
}

// -------------------------------- is_supported --------------------------------
// function: is_supported()
// input: preview array holding the supported file extensions as key and references to executables as value [array], file name or file extension [string]
// output: true / false

// description:
// This function determines if a certain file type by its file extension is supported by the systems media conversion

function is_supported ($preview_array, $file)
{
  global $mgmt_config;

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
// input: publication name [string] (optional)
// output: true / false

// description:
// This function determines if a cloud storage has been defined in the main configuration or for a specific publication

function is_cloudstorage ($site="")
{
  global $mgmt_config;

  // if cloud connector is available
  if (is_array ($mgmt_config) && is_file ($mgmt_config['abs_path_cms']."connector/cloud/hypercms_cloud.inc.php"))
  {
    // is cloud storage enabled
    if (valid_publicationname ($site))
    {
      // load publication config if not available
      if (!isset ($mgmt_config[$site]['storage_type']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
      {
        require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
      }

      // if the cloud storage is disabled for the publication
      if (!isset ($mgmt_config[$site]['storage_type']) || strtolower ($mgmt_config[$site]['storage_type']) == "local")
      {
        return false;
      }
    }

    // AWS S3 cloud storage
    if (!empty ($mgmt_config[$site]['aws_access_key_id']) && !empty ($mgmt_config[$site]['aws_secret_access_key']) && !empty ($mgmt_config[$site]['aws_bucket']))
    {
      return true;
    }
    elseif (!empty ($mgmt_config['aws_access_key_id']) && !empty ($mgmt_config['aws_secret_access_key']) && !empty ($mgmt_config['aws_bucket']))
    {
      return true;
    }

    // Google cloud storage
    if (!empty ($mgmt_config[$site]['gs_access_key_id']) && !empty ($mgmt_config[$site]['gs_secret_access_key']) && !empty ($mgmt_config[$site]['gs_bucket']))
    {
      return true;
    }
    elseif (!empty ($mgmt_config['gs_access_key_id']) && !empty ($mgmt_config['gs_secret_access_key']) && !empty ($mgmt_config['gs_bucket']))
    {
      return true;
    }

    // MS Azure cloud storage
    if (!empty ($mgmt_config[$site]['azure_access_key']) && !empty ($mgmt_config[$site]['azure_container']))
    {
      return true;
    }
    elseif (!empty ($mgmt_config['azure_access_key']) && !empty ($mgmt_config['azure_container']))
    {
      return true;
    }
  }

  return false;
}

// ---------------------- is_cloudobject -----------------------------
// function: is_cloudobject()
// input: path to media file or media file name [string]
// output: true / false

// description:
// This function verifies if an object/file is available in the cloud storage

function is_cloudobject ($file)
{
  global $mgmt_config;

  if (valid_locationname ($file) && getmediacontainerid ($file))
  {
    $result = false;

    // get publication
    $site = getpublication ($file);

    // AWS S3 cloud storage
    if (function_exists ("is_S3object") && !empty ($mgmt_config['aws_bucket']))
    {
      $result = is_S3object ($site, $mgmt_config['aws_bucket'], getobject ($file));
    }
    // Google cloud storage
    elseif (function_exists ("is_GSobject") && !empty ($mgmt_config['gs_bucket']))
    {
      $result = is_GSobject ($site, $mgmt_config['gs_bucket'], getobject ($file));
    }
    // MS Azure cloud storage
    elseif (function_exists ("is_AZUREobject") && !empty ($mgmt_config['azure_container']))
    {
      $result = is_AZUREobject ($site, $mgmt_config['azure_container'], getobject ($file));
    }

    return $result;
  }
  else return false;
}

// -------------------------------- is_date --------------------------------
// function: is_date()
// input: date [string], date format [string] (optional)
// output: true / false

// description:
// This function determines if a string represents a valid date format

function is_date ($date, $format="Y-m-d")
{
  global $mgmt_config;

  if ($date != "" && $date != "0000-00-00 00:00:00" && $format != "")
  {
    $date = strtotime ($date);

    if (!empty ($date)) $date = date ($format, $date);

    if (!empty ($date)) return true;
    else return false;
  }
  else return false;
}

// -------------------------------------- is_tempfile -------------------------------------------
// function: is_tempfile()
// input: file name or path [string]
// output: if file is a temp file true / false on error

// description:
// This function checks if the provided file name is a temporary file that should not be uploaded in the system

function is_tempfile ($path)
{
  global $mgmt_config;

  // patterns
  $tempfile_patterns = array (
    '/^.*\.recycle$/',    // object in recycle bin
    '/^__MACOSX$/',    // OS/X folder
    '/^\._(.*)$/',     // OS/X resource forks
    '/^.DS_Store$/',   // OS/X custom folder settings
    '/^\.(.*)-Spotlight$/', // OS/X Spotlight files
    '/^desktop.ini$/', // Windows custom folder settings
    '/^Thumbs.db$/',   // Windows thumbnail cache
    '/^.(.*).swp$/',   // ViM temporary files
    '/^\.dat(.*)$/',   // Smultron seems to create these
    '/^~lock.(.*)#$/' // Windows 7 lockfiles
  );

  if ($path != "" && is_array ($tempfile_patterns))
  {
    // extract the file name
    $object = getobject ($path);

    foreach ($tempfile_patterns as $pattern)
    {
      if (preg_match ($pattern, $object)) return true;
    }
  }
  
  return false;
}

// -------------------------------------- is_hiddenfile -------------------------------------------
// function: is_hiddenfile()
// input: file name or path [string]
// output: if file is a hidden file true / false on error

// description:
// This function checks if the provided file name is a hidden file that should not be displayed

function is_hiddenfile ($path)
{
  global $mgmt_config;

  // patterns
  $hiddenfile_patterns = array (
    '/^.folder$/', // folder file
    '/^.htaccess$/', // Apache htaccess file
    '/^web.config$/' // MS IIS web.config file
  );

  if ($path != "" && is_array ($hiddenfile_patterns))
  {
    // extract the file name
    $object = getobject ($path);

    foreach ($hiddenfile_patterns as $pattern)
    {
      if (preg_match ($pattern, $object)) return true;
    }
  }
  
  return false;
}

// -------------------------------------- is_keyword -------------------------------------------
// function: is_keyword()
// input: keyword [string]
// output: if expression can be used as a keyword true / false on error

// description:
// This function checks if the provided expression can be used as a keyword

function is_keyword ($keyword)
{
  $keyword = trim ($keyword);

  if (is_string ($keyword) && strlen (trim ($keyword)) > 2 && strlen (trim ($keyword)) <= 80 && strpos ("_".$keyword, "???") < 1 && strpos ("_".$keyword, "...") < 1 && strpos ("_".$keyword, "?>") < 1 && (ctype_alnum ($keyword[0]) || $keyword[0] == "#"))
  {
    return true;
  }
  else return false;
}

// -------------------------------------- is_thumbnail -------------------------------------------
// function: is_thumbnail()
// input: file name or path [string], only thumbnail images should be considered as thumbnail [boolean]
// output: if file is a thumbnail file true / false on error

// description:
// This function checks if the provided file name is a thumbnail file

function is_thumbnail ($media, $images_only=true)
{
  if ($media != "")
  {
    $container_id = getmediacontainerid ($media);

    if ($container_id != "")
    {
      if ($images_only == true && substr_count ($media, "_hcm".$container_id.".thumb.jpg") > 0) return true;
      elseif ($images_only == false && substr_count ($media, "_hcm".$container_id.".thumb.") > 0) return true;
      elseif ($images_only == false && substr_count($media, "_hcm".$container_id.".orig.") > 0) return true;
      else return false;
    }
    else return false;
  }
  else return false;
}

// -------------------------------------- is_preview -------------------------------------------
// function: is_preview()
// input: file name or path [string]
// output: if file is a preview file true / false on error

// description:
// This function checks if the provided file name is a preview file

function is_preview ($media)
{
  if ($media != "")
  {
    $container_id = getmediacontainerid ($media);

    if ($container_id != "")
    {
      if (substr_count ($media, "_hcm".$container_id.".preview.") > 0) return true;
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
// This function checks if the provided file name is a config file

function is_config ($media)
{
  if ($media != "")
  {
    $container_id = getmediacontainerid ($media);

    if ($container_id != "")
    {
      if (substr_count($media, "_hcm".$container_id.".config.") > 0) return true;
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
// This function checks if the provided file is encrypted

function is_encryptedfile ($location, $file)
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  if (valid_locationname ($location) && valid_objectname ($file))
  {
    // add slash if not present at the end of the location string
    $location = correctpath ($location);

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
// input: file name or file extension [string]
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
// input: file name or file extension [string]
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
// input: file name or file extension [string]
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
// input: file name or file extension [string]
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
// input: file name or file extension [string]
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

// -------------------------------- is_rawvideo --------------------------------
// function: is_rawvideo()
// input: file name or file extension [string]
// output: true / false

// description:
// This function determines if a certain file is a RAW video

function is_rawvideo ($file)
{
  global $mgmt_config, $hcms_ext;

  if ($file != "")
  {
    // load file extensions
    if (empty ($hcms_ext) || !is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

    // get file extension
    if (substr_count ($file, ".") > 0) $ext = strtolower (trim (strrchr ($file, "."), "."));
    else $ext = $file;

    if (substr_count (strtolower ($hcms_ext['rawvideo']).".", ".".$ext.".") > 0) return true;
    else return false;
  }
  else return false;
}

// -------------------------------- is_audio --------------------------------
// function: is_audio()
// input: file name or file extension [string]
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

// -------------------------------- is_compressed --------------------------------
// function: is_compresseddocument()
// input: file name or file extension [string]
// output: true / false

// description:
// This function determines if a certain file is compressed

function is_compressed ($file)
{
  global $mgmt_config, $hcms_ext;

  if ($file != "")
  {
    // load file extensions
    if (empty ($hcms_ext) || !is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

    // get file extension
    if (substr_count ($file, ".") > 0) $ext = strtolower (trim (strrchr ($file, "."), "."));
    else $ext = $file;

    if (substr_count (strtolower ($hcms_ext['compressed']).".", ".".$ext.".") > 0) return true;
    else return false;
  }
  else return false;
}

// ---------------------- is_mobilebrowser -----------------------------
// function: is_mobilebrowser()
// input: %
// output: true / false

// description:
// Detects mobile browsers (smartphones and tablets)

function is_mobilebrowser ()
{
  global $user, $mgmt_config;

  if (!empty ($_SERVER['HTTP_USER_AGENT']))
  {
    $useragent = $_SERVER['HTTP_USER_AGENT'];

    if (
       preg_match ('/android|playbook|silk|(bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ipad|iphone|ipod|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent) || 
       preg_match ('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr ($useragent, 0, 4))
       )
    {
      return true;
    }
    else return false;
  }
  else return false;
}

// ---------------------- is_iOS -----------------------------
// function: is_iOS()
// input: %
// output: true / false

// description:
// Detects if a mobile browser is an iPhone, iPad or IPod

function is_iOS ()
{
  global $user, $mgmt_config;

  if (!empty ($_SERVER['HTTP_USER_AGENT']))
  {
    $useragent = $_SERVER['HTTP_USER_AGENT'];

    if (preg_match ('/ipad|iphone|ipod/i',$useragent))
    {
      return true;
    }
    else return false;
  }
  else return false;
}

// -------------------------------- is_activelanguage --------------------------------
// function: is_activelanguage()
// input: publication name [string], 2-digits language code [string]
// output: true / false

// description:
// This function determines if a language has been enabled for automatic translation in the publication settings

function is_activelanguage ($site, $langcode)
{
  global $mgmt_config;

  if (valid_publicationname ($site) && empty ($mgmt_config[$site]['translate']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
  {
    require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
  }

  if (valid_publicationname ($site) && $langcode != "")
  {
    if (substr_count  (",".$mgmt_config[$site]['translate'].",", ",".$langcode.",") > 0) return true;
    else return false;
  }
  else return false;
}

// -------------------------------- is_annotation --------------------------------
// function: is_annotation()
// input: %
// output: true / false

// description:
// This function determines if the annotation feature has been enabled and is installed.

function is_annotation ()
{
  global $mgmt_config;

  if (!empty ($mgmt_config['annotation']) && is_dir ($mgmt_config['abs_path_cms']."workflow/") && is_file ($mgmt_config['abs_path_cms']."javascript/annotate/annotate.css"))
  {
    return true;
  }
  else return false;
}

// -------------------------------- is_facerecognition --------------------------------
// function: is_facerecognition()
// input: user or service user name [string]
// output: true / false

// description:
// This function determines if the face recognition feature and service has been enabled for the automatic face recognition.
// Provide system user "sys" for general verification of the face recognition feature (if enabled and is supported by the browser).

function is_facerecognition ($user)
{
  global $mgmt_config, $is_mobile;

  // verify browser
  $user_client = getbrowserinfo ();

  if ($user != "" && empty ($is_mobile) && !empty ($mgmt_config['facerecognition']) && is_dir ($mgmt_config['abs_path_cms']."javascript/facerecognition") && empty ($user_client['msie']))
  {
    // system user
    if ($user == "sys")
    {
      return true;
    }
    // verify system service "sys:service-name:16-digit-servicehash"
    elseif (substr ($user, 0, 4) == "sys:" && substr_count ($user, ":") == 2)
    {
      // verify that service is registered
      list ($sys, $servicename, $servicehash) = explode (":", $user);

      if (is_file ($mgmt_config['abs_path_data']."session/".$servicename.".".$servicehash.".dat"))
      {
        return true;
      }
    }
    // verify standard user
    elseif (empty ($mgmt_config['facerecognition_service_users']) || strpos ("_;".$mgmt_config['facerecognition_service_users'].";", ";".$user.";") > 0)
    {
      // verify that user is logged in
      if (is_file ($mgmt_config['abs_path_data']."session/".$user.".dat"))
      {
        return true;
      }
    }
  }
  
  return false;
}

// -------------------------------- createfacerecognitionservice --------------------------------
// function: createfacerecognitionservice()
// input: user name [string]
// output: URL of face recognition service / false

// description:
// This function registers the face recognition service and creates the service URL.

function createfacerecognitionservice ($user)
{
  global $mgmt_config;

  if (is_facerecognition ($user))
  {
    // register servive for user
    $servicehash = registerservice ("recognizefaces", $user);

    // create token
    $timetoken = createtimetoken (10, 3);

    // define service URL root
    if (!empty ($mgmt_config['facerecognition_service_url']))
    {
      $serviceurl = $mgmt_config['facerecognition_service_url'];

      // add slash if missing
      if (substr ($serviceurl, -1) != "/") $serviceurl = $serviceurl."/";
    }
    // use default (blocking service)
    else $serviceurl = $mgmt_config['url_path_cms'];

    // return service link
    if ($serviceurl != "" && $servicehash != "" && $timetoken != "")
    {
      return $serviceurl."frameset_recognizefaces.php?servicehash=".urlencode($servicehash)."&token=".urlencode($timetoken);
    }
  }
  
  return false;
}

// -------------------------------- copyrecursive --------------------------------
// function: copyrecursive()
// input: source directory [string], destination directory [string]
// output: true/false

// description:
// This function copyies all directories and files from source to destination directory

function copyrecursive ($src, $dst)
{
  $result = true;

  // create directory
  if (!is_dir ($dst)) @mkdir ($dst);

  $scandir = scandir ($src);

  if ($scandir)
  {
    foreach ($scandir as $file)
    {
      if ($file != '.' && $file != '..')
      {
        if (is_dir ($src.$file)) $result = copyrecursive ($src.$file."/", $dst.$file."/");
        else $result = copy ($src.$file, $dst.$file);

        if ($result == false) break;
      }
    }
  }

  return $result;
}

// -------------------------------- array_iunique --------------------------------
// function: array_iunique()
// input: array [array]
// output: unique array / false

// description:
// This function is the case-insensitive form of PHPs array_unique function

function array_iunique ($array)
{
  if (is_array ($array) && sizeof ($array) > 0)
  {
    return array_intersect_key ($array, array_unique (array_map ("StrToLower",$array)));
  }
  else return false;
}

// -------------------------------- in_array_substr --------------------------------
// function: in_array_substr()
// input: search expression [string], array [array]
// output: true / false

// description:
// This function is supporting the search of substrings in the array values compared to PHPs in_array function

function in_array_substr ($search, $array)
{
  if ($search != "" && is_array ($array) && sizeof ($array) > 0)
  {
    foreach ($array as $value)
    {
      if (strpos (" ".$value, $search) > 0)  return true;
    }

    return false;
  }
  else return false;
}

// ========================================== FOOTER =======================================

// ---------------------- includefooter -----------------------------
// function: includefooter()
// input: %
// output: true / false

// description:
// Includes the footer for GA code integration

function includefooter ()
{
  global $mgmt_config;

  if (is_file ($mgmt_config['abs_path_cms']."include/footer.inc.php"))
  {
    include_once ($mgmt_config['abs_path_cms']."include/footer.inc.php");

    return true;
  }
  else return false;
}

// ========================================== FILES AND LINKS =======================================

// ---------------------- createfilename -----------------------------
// function: createfilename()
// input: path to file or directory [string], file or directory name [string]
// output: new filename/false

// description:
// Creates a valid file name without special characters that does not exceed the maximum file name length

function createfilename ($filename)
{
  global $mgmt_config, $hcms_lang, $lang;

  // initialize
  $error = array();

  // trim
  $filename_new = trim ($filename);

  // exclude multimedia file names
  if (strpos ($filename_new, "_hcm") > 0)
  {
    return $filename_new;
  }
  // object name
  elseif (valid_objectname ($filename_new))
  {
    // default value
    if (empty ($mgmt_config['max_digits_filename']) || intval ($mgmt_config['max_digits_filename']) < 1) $mgmt_config['max_digits_filename'] = 236;

    // check if filename includes special characters
    if (specialchr ($filename_new, ".-_") == true)
    {
      $filename_new = specialchr_encode ($filename_new, "no");
    }

    // escaped or input file name is too long (plus 11 digits for the hcms media object identifier or the file extension identifiers like .off and .recycle)
    // exclude objects in recycle bin
    if ((strlen ($filename_new) + 11) > $mgmt_config['max_digits_filename'] && substr ($filename_new, -8) != ".recycle")
    {
      if (substr_count ($filename_new, ".") > 0)
      {
        // get the file extension of the file
        $file_ext = strrchr ($filename_new, ".");
        // get file name without extensions
        $filename_new = strrev (substr (strstr (strrev ($filename_new), "."), 1));
      }
      else $file_ext  = "";

      $filename_new = substr ($filename_new, 0, ($mgmt_config['max_digits_filename'] - 11 - strlen ($file_ext)));

      // remove escaped character at the end of the file name that is not fully presented
      if (substr ($filename_new, -2, 1) == "~") $filename_new = substr ($filename_new, 0, -2);
      if (substr ($filename_new, -1, 1) == "~") $filename_new = substr ($filename_new, 0, -1);

      // escaped character must be even for multibyte characters (does not work if single and multibyte characters are mixed) and has been removed in version 8.0.5
      // if (substr_count ($filename_new, "~") % 2 != 0) $filename_new = substr ($filename_new, 0, strrpos ($filename_new, "~"));

      // verify string and cut off last escaped character in order to correct the string (3 times)
      if (strpos ("_".specialchr_decode ($filename), specialchr_decode ($filename_new)) < 1) $filename_new = substr ($filename_new, 0, strrpos ($filename_new, "~"));
      if (strpos ("_".specialchr_decode ($filename), specialchr_decode ($filename_new)) < 1) $filename_new = substr ($filename_new, 0, strrpos ($filename_new, "~"));
      if (strpos ("_".specialchr_decode ($filename), specialchr_decode ($filename_new)) < 1) $filename_new = substr ($filename_new, 0, strrpos ($filename_new, "~"));

      // use "noname"
      if (trim ($filename_new) == "") $filename_new = "noname";

      // add file extension
      $filename_new = $filename_new.$file_ext;

      $errcode = "00911";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|Object name '".specialchr_decode ($filename)."' has been truncated to '".specialchr_decode ($filename_new)."'";

      savelog (@$error);

      if (strlen ($filename_new) > 0) return $filename_new;
      else return "noname";
    }
    else
    {
      if (strlen ($filename_new) > 0) return $filename_new;
      else return "noname";
    }
  }
  else return false;
}

// ---------------------- createlockname -----------------------------
// function: createlockname()
// input: user name [string]
// output: correct user name for locking / false

function createlockname ($user)
{
  global $mgmt_config;

  if (valid_objectname ($user))
  {
    return str_replace (".@", "@", $user);
  }
  else return false;
}

// ---------------------- correctfile -----------------------------
// function: correctfile()
// input: path to file or directory [string], file or directory name [string], user name [string] (optional)
// output: correct filename/false

function correctfile ($abs_path, $filename, $user="")
{
  global $mgmt_config, $hcms_lang, $lang;

  // correct user name for file lock
  $lock = createlockname ($user);

  // create valid file name if container file is not locked by a user (this would mean the correct container file name has been provided)
  if (strpos ($filename, ".@") < 1) $filename = createfilename ($filename);
  
  if (valid_locationname ($abs_path) && valid_objectname ($filename))
  {
    // add slash if not present at the end of the location string
    $abs_path = correctpath ($abs_path);

    // deconvert path
    if (substr_count ($abs_path , "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file", true);
    }

    // if given file or directory exists
    if (is_file ($abs_path.$filename) || is_dir ($abs_path.$filename) || is_link ($abs_path.$filename))
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

      if (is_file ($abs_path.$filename))
      {
        return $filename;
      }
      else return false;
    }
    // if file is locked by the same user (only for management files, like content containers, link index files, user index file, ...)
    elseif (substr_count ($abs_path.$filename, $mgmt_config['abs_path_data']) == 1 && valid_objectname ($user) && is_file ($abs_path.$filename.".@".$lock)) 
    {
      $filename = $filename.".@".$lock;
      return $filename;
    }
    // file doesn't exist
    else return false;
  }
  else return false;
}

// ---------------------------------- correctpath -------------------------------------------
// function: correctpath()
// input: path to folder [string], directory seperator [string] (optional)
// output: corrected path / false

// description:
// This function should not be used for a path that includes a file

function correctpath ($path, $slash="/")
{
  if ($path != "" && $slash != "")
  {
    // correct all backslashes
    $path = str_replace ("\\", $slash, $path);

    // get object
    $object = getobject ($path);

    // get file extension
    $file_ext = strrchr ($object, ".");

    // append $slash at the end of the path if it seems to be a directory
    if (substr ($path, -7) != ".folder" && (empty ($file_ext) || is_dir (realpath ($path))) && substr ($path, -1) != $slash) $path .= $slash; 

    return $path; 
  }
  else return false;
}

// ---------------------------------- convertpath -------------------------------------------
// function: convertpath()
// input: publication name [string], content management path to folder or object [string], object category [page,comp]
// output: converted path or URL / false on error

// description:
// This function replaces object pathes of the content management config with %page% and %comp% path variables

function convertpath ($site, $path, $cat="")
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  if (@substr_count ($path, "%page%") > 0 || @substr_count ($path, "%comp%") > 0)
  {
    return correctpath ($path);
  }
  elseif (valid_publicationname ($site) && trim ($path) != "" && is_array ($mgmt_config))
  {
    // add slash if not present at the end of the location string
    if (substr ($path, -1) != "/")
    {
      $path = $path."/";
      $remove_slash = true;
    }

    $path = correctpath ($path);

    if (substr_count ($path, "%page%") == 0 && substr_count ($path, "%comp%") == 0)
    {
      // load config if not available
      if (!isset ($mgmt_config[$site]['url_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
      {
        require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
      }

      // define category if undefined
      if ($cat == "") $cat = getcategory ($site, $path);

      // convert path
      if (strtolower ($cat) == "page" && is_array ($mgmt_config[$site])) 
      {
        // URL can be with our without http://domain
        $path_page_url = trim ($mgmt_config[$site]['url_path_page']);
        $path_page_abs = trim ($mgmt_config[$site]['abs_path_page']);

        if (substr ($path_page_url, -1) == "/") $root_var_url = "%page%/".$site."/";
        else $root_var_url = "%page%/".$site;

        if (substr ($path_page_abs, -1) == "/") $root_var_abs = "%page%/".$site."/";
        else $root_var_abs = "%page%/".$site;

        // abs path
        if (substr_count ($path, "://") == 0 && !empty ($path_page_abs) && substr_count ($path, $path_page_abs) > 0)
        {
          $path = str_replace ($path_page_abs, $root_var_abs, $path);
        }
        // URL
        elseif (!empty ($path_page_url))
        {
          // absolute
          if ((substr_count ($path, "://") == 1 && substr_count ($path_page_url, "://") == 1) || (substr_count ($path, "://") == 0 && substr_count ($path_page_url, "://") == 0))
          {
            $path = str_replace ($path_page_url, $root_var_url, $path);
          }
          // relative
          elseif (substr_count ($path, $path_page_url) > 0)
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
        $path_comp_url = trim ($mgmt_config['url_path_comp']);
        $path_comp_abs = trim ($mgmt_config['abs_path_comp']); 

        if (substr ($path_comp_url, -1) == "/") $root_var_url = "%comp%/";
        else $root_var_url = "%comp%";

        if (substr ($path_comp_abs, -1) == "/") $root_var_abs = "%comp%/";
        else $root_var_abs = "%comp%"; 

        // abs. path
        if (substr_count ($path, "://") == 0 && !empty ($path_comp_abs) && substr_count ($path, $path_comp_abs) > 0)
        {
          $path = str_replace ($path_comp_abs, $root_var_abs, $path);
        }
        // URL
        elseif (!empty ($path_comp_url))
        {
          // absolute
          if ((substr_count ($path, "://") == 1 && substr_count ($path_comp_url, "://") == 1) || (substr_count ($path, "://") == 0 && substr_count ($path_comp_url, "://") == 0))
          {
            $path = str_replace ($path_comp_url, $root_var_url, $path);
          }
          // relative
          elseif (substr_count ($path, $path_comp_url) > 0)
          {
            $path_comp_url = cleandomain ($path_comp_url);
            $path = cleandomain ($path);

            $path = str_replace ($path_comp_url, $root_var_url, $path);
          }
        } 
      }

      // remove added slash
      if (!empty ($remove_slash) && substr ($path, -1) == "/") $path = substr ($path, 0, -1);

      if ($path != "") return $path;
      else return false;
    }
    else return $path;
  }
  else return false;
}

// ---------------------------------- convertlink -------------------------------------------
// function: convertlink()
// input: publication name [string], publication management path to folder or object [string], object category [page,comp]
// output: converted path or URL / false on error

// description:
// This function replaces pathes of the publication management config with %page% and %comp% path variables.

function convertlink ($site, $path, $cat)
{
  global $user, $mgmt_config, $publ_config, $hcms_lang, $lang;

  if (valid_publicationname ($site) && $path != "" && is_array ($mgmt_config))
  {
    // add slash if not present at the end of the location string
    if (substr ($path, -1) != "/")
    {
      $path = $path."/";
      $remove_slash = true;
    }

    $path = correctpath ($path);

    if (substr_count ($path, "%page%") == 0 && substr_count ($path, "%comp%") == 0 && is_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"))
    {
      // load ini
      $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"); 

      // define category if undefined
      if ($cat == "") $cat = getcategory ($site, $path);

      // convert path
      if (strtolower ($cat) == "page") 
      {
        // URL can be with our without http://domain
        $path_page_url = trim ($publ_config['url_publ_page']);
        $path_page_abs = trim ($publ_config['abs_publ_page']);

        if (substr ($path_page_url, -1) == "/") $root_var_url = "%page%/".$site."/";
        else $root_var_url = "%page%/".$site;

        if (substr ($path_page_abs, -1) == "/") $root_var_abs = "%page%/".$site."/";
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
        $path_comp_url = trim ($publ_config['url_pupl_comp']);
        $path_comp_abs = trim ($publ_config['abs_publ_comp']);

        if (substr ($path_comp_url, -1) == "/") $root_var_url = "%comp%/";
        else $root_var_url = "%comp%";

        if (substr ($path_comp_abs, -1) == "/") $root_var_abs = "%comp%/";
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

      // remove added slash
      if (!empty ($remove_slash) && substr ($path, -1) == "/") $path = substr ($path, 0, -1);

      if ($path != "") return $path;
      else return false;
    }
    else return $path;
  }
  else return false;
}

// ---------------------------------- deconvertpath -------------------------------------------
// function: deconvertpath ()
// input: string including path to folder or object [string], convert to file system path or URL [file,url] (optional), transform special characters using specialchr_encode [boolean] (optional)
// output: deconverted path/false

// description:
// This function replaces all %page% and %comp% path variables with the path of the content management config.
// It converts the path only on content management side not for the publication target.
// It optionally transform special characters as well.
// BE AWARE: The input path must not provide template data since valid_publicationname might return false.

function deconvertpath ($objectpath, $type="file", $specialchr_transform=true)
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  if (is_string ($objectpath) && $objectpath != "" && (strtolower ($type) == "file" || strtolower ($type) == "url") && is_array ($mgmt_config))
  {
    $type = strtolower ($type);

    $path_parts = array();
    $result_parts = array();

    // check if expression holds a path seperator for multiple pathes and is not some sort of code
    if (substr_count ($objectpath, "|") > 0 && substr_count ($objectpath, "<") == 0 && substr_count ($objectpath, ">") == 0 && substr_count ($objectpath, "<") == 0 && substr_count ($objectpath, " || ") == 0)
    {
      $path_parts = explode ("|", trim ($objectpath, "|"));
    }
    else $path_parts[0] = $objectpath;

    foreach ($path_parts as $path)
    {
      if ($path != "")
      {
        // page and component root variable
        if (substr_count ($path, "%page%") > 0 || substr_count ($path, "%comp%") > 0) $root_var = true;
        else $root_var = false;

        if ($root_var != false)
        {
          // test if path includes special characters
          if ($specialchr_transform == true && specialchr ($path, ".-_~%") == true)
          {
            $path = specialchr_encode ($path, "no");
          }

          // extract publication from the converted path for page locations (first found path entry only!)
          if (substr_count ($path, "%page%") > 0)
          {
            $site = getpublication ($path);

            // load publication config if not available
            if (valid_publicationname ($site) && !isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
            {
              require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
            }
          }

          // if absolute file path is reuquested
          if ($type == "file") 
          { 
            // deconvert page locations
            if (substr_count ($path, "%page%") > 0 && valid_publicationname ($site) && !empty ($mgmt_config[$site]['abs_path_page']))
            {
              if (substr ($mgmt_config[$site]['abs_path_page'], -1) == "/") $root_var = "%page%/".$site."/";
              else $root_var = "%page%/".$site;

              $path = str_replace ($root_var, $mgmt_config[$site]['abs_path_page'], $path);
            }
 
            // deconvert component locations 
            if (substr_count ($path, "%comp%") > 0 && !empty ($mgmt_config['abs_path_comp']))
            {
              if (substr ($mgmt_config['abs_path_comp'], -1) == "/") $root_var = "%comp%/";
              else $root_var = "%comp%";

              $path = str_replace ($root_var, $mgmt_config['abs_path_comp'], $path);
            }
          }
          // if URL is reuquested
          elseif ($type == "url") 
          {
            // deconvert page locations
            if (substr_count ($path, "%page%") > 0 && valid_publicationname ($site) && !empty ($mgmt_config[$site]['url_path_page']))
            {
              if (substr ($mgmt_config[$site]['url_path_page'], -1) == "/") $root_var = "%page%/".$site."/";
              else $root_var = "%page%/".$site;

              $path = str_replace ($root_var, $mgmt_config[$site]['url_path_page'], $path);
            }

            // deconvert component locations
            if (substr_count ($path, "%comp%") > 0 && !empty ($mgmt_config['url_path_comp']))
            {
              if (substr ($mgmt_config['url_path_comp'], -1) == "/") $root_var = "%comp%/";
              else $root_var = "%comp%";

              $path = str_replace ($root_var, $mgmt_config['url_path_comp'], $path);
            }
          }
        }

        // assign path to result array
        $result_parts[] = $path;
      }
    }

    // merge and return result
    if (sizeof ($result_parts) > 1) return implode ("|", $result_parts);
    elseif (!empty ($result_parts[0])) return $result_parts[0];
    else return false;
  }
  // wrong input
  else return false;
}

// ---------------------------------- deconvertlink -------------------------------------------
// function: deconvertlink ()
// input: path to folder or object [string], convert to file system path or URL [file,url]
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

      if ($pos1 !== false) $pos2 = @strpos ($path, "/", $pos1);
      else $pos2 = false;

      if ($pos1 !== false && $pos2 !== false) $site = @substr ($path, $pos1, $pos2-$pos1);
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

// ---------------------- mediapublicaccess -----------------------------
// function: mediapublicaccess()
// input: media file name [string]
// output: true / false

// description:
// Is the media file public accessible. Has it been published or has the setting 'publicdownload' in the main configuration been enabled.
// This function does not include direct links to the media files (used in websites).

function mediapublicaccess ($mediafile)
{
  global $mgmt_config;

  if ($mediafile != "")
  {
    // if public download is enabled the asset does not need to be published
    if (!empty ($mgmt_config['publicdownload'])) return true;

    // if mediafile is provided as path, extract the media file name
    if (substr_count ($mediafile, "/") > 0) $mediafile = getobject ($mediafile);

    $container_id = getmediacontainerid ($mediafile);

    if ($container_id != "")
    {
      $contentdata = loadcontainer ($container_id, "published", "sys");

      if ($contentdata != "")
      {
        $published = getcontent ($contentdata, "<contentpublished>");

        if (!empty ($published[0])) return true;
        else return false;
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// ---------------------- createviewlink -----------------------------
// function: createviewlink()
// input: publication name [string], media file name [string], media name [string] (optional), force reload [boolean] (optional), link type [wrapper,download] (optional)
// output: URL for download of the multimedia file / false on error

// description:
// The view link is mainly used inside the system as a reference to a multimedia file. 
// The database is not required since the object hash or ID is not needed to create the view link.

function createviewlink ($site, $mediafile, $name="", $force_reload=false, $type="wrapper")
{
  global $user, $mgmt_config;

  // if mediafile is provided as path, extract the media file name
  if (substr_count ($mediafile, "/") > 0)
  {
    // extract possible publication name or container ID 
    if (substr_count ($mediafile, "/") == 1) $temp_site = substr ($mediafile, 0, strpos ($mediafile, "/"));
    else $temp_site = getobject (getlocation ($mediafile));

    if (intval ($temp_site) < 1) $mediafile = getobject ($mediafile);
  }
  
  if (isset ($mgmt_config) && valid_publicationname ($site) && valid_objectname ($mediafile))
  {
    $add = "";

    if (is_string ($name) && trim ($name) != "") $add .= "&name=".urlencode($name);
    if ($force_reload) $add .= "&ts=".time();

    if (strtolower ($type) == "download") $servicename = "mediadownload";
    else $servicename = "mediawrapper";

    return $mgmt_config['url_path_cms']."service/".$servicename.".php?site=".urlencode($site)."&media=".urlencode($site."/".$mediafile)."&token=".hcms_crypt($site."/".$mediafile).$add;
  }
  else return false;
}

// ---------------------- createportallink -----------------------------
// function: createportallink()
// input: publication name [string], portal name [string]
// output: URL for download of the multimedia file / false on error

// description:
// The portal access link is used to provide public access without logon based on a certain user profile and user hash that is connected with the portal. 

function createportallink ($site, $portal)
{
  global $mgmt_config;

   if (valid_publicationname ($site) && trim ($portal) != "") return $mgmt_config['url_path_cms']."?portal=".url_encode ($site.".".$portal);
   else return false;
}

// ---------------------- createaccesslink -----------------------------
// function: createaccesslink()
// input: publication name [string], location [string] (optional), object [string] (optional), category [page,comp] (optional), object-ID [string] (optional), user name of the user account to be used for access [string], link type [al,dl] (optional), token lifetime in seconds [integer] (optional), formats defined in main configuration [JSON-string] (optional)
// output: URL for access to given object / false on error

// description:
// Creates an access link to any object based on the permissions of the provided user account.

function createaccesslink ($site, $location="", $object="", $cat="", $object_id="", $login="", $type="al", $lifetime=0, $formats="")
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  $error = array();

  // deconvert location
  $location = deconvertpath ($location, "file"); 

  // if object includes special characters
  if (specialchr ($object, ".-_~") == true) $object = createfilename ($object);

  // check permissions (only if a publication and location has been provided)
  if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']) && valid_publicationname ($site) && valid_locationname ($location))
  {
    $ownergroup = accesspermission ($site, $location, $cat);
    $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

    if (empty ($setlocalpermission['root']))
    {
      return false;
    }
  }

  if (((valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($object) && $cat != "") || $object_id != "") && (($type == "al" && valid_objectname ($login)) || $type == "dl"))
  {
    // check if object is folder or page/component
    if ($site != "" && $location != "" && $object != "")
    {
      if (is_dir ($location.$object))
      {
        $location = $location.$object."/";
        $object = ".folder";
      } 
 
      // get object id
      $objectpath = convertpath ($site, $location.$object, $cat);
      $object_id = rdbms_getobject_id ($objectpath);
    }

    // object has been marked as deleted
    if ($object_id == "hcms:deleted") 
    {
      return false;
    }
    // object access link
    elseif ($object_id != "")
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
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|createaccesslink failed due to missing object id for: $objectpath";

      savelog (@$error);

      return false;
    }
  }
  else return false;
}

// ---------------------- createobjectaccesslink -----------------------------
// function: createobjectaccesslink()
// input: publication name [string] (optional), location [string] (optional), object [string] (optional), category [page,comp] (optional), object ID [string] (optional), container-ID or repository media file [string] (optional)
// output: URL for download of the multimedia file of the given object or folder / false on error

// description:
// Creates an access link to any object based on the permissions of the user account defined in the publication settings.

function createobjectaccesslink ($site="", $location="", $object="", $cat="", $object_id="", $container_id="")
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  $error = array();

  if (isset ($mgmt_config) && !empty ($mgmt_config['db_connect_rdbms']))
  {
    // initialize
    $object_hash = false;

    // check permissions (only if a publication and location has been provided)
    if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']) && valid_publicationname ($site) && valid_locationname ($location))
    {
      $ownergroup = accesspermission ($site, $location, $cat);
      $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

      if (empty ($setlocalpermission['root'])) return false;
    }

    // deconvert location
    $location = deconvertpath ($location, "file");

    // if object includes special characters
    $object = trim ($object);
    $object = trim ($object, "/");
    if (specialchr ($object, ".-_~") == true) $object = createfilename ($object);

    // check if object is folder or page/component
    if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($object) && $cat != "")
    {
      if (is_dir ($location.$object))
      {
        $location = $location.$object."/";
        $object = ".folder";
      } 

      $objectpath = convertpath ($site, $location.$object, $cat);

      // get object id
      $object_hash = rdbms_getobject_hash ($objectpath);

      // recreate object entry in database
      if ($object_hash == false)
      {
        $object_info = getobjectinfo ($site, $location, $object, $user);

        if (!empty ($object_info['container_id']) && !empty ($object_info['template']))
        {
          $container_id = $object_info['container_id'];
          rdbms_createobject ($object_info['container_id'], $objectpath, $object_info['template'], "", $object_info['content'], $user);

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
      // if media file with container ID
      if (strpos ($container_id, "_hcm") > 0) $container_id = getmediacontainerid ($container_id);
      $object_hash = rdbms_getobject_hash ("", $container_id);
    }

    // object has been marked as deleted
    if ($object_id == "hcms:deleted") 
    {
      return false;
    }
    // object access link
    elseif ($object_hash != false)
    {
      return $mgmt_config['url_path_cms']."?oal=".$object_hash;
    }
    else
    {
      $errcode = "40912";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|createobjectaccesslink failed due to missing object id for: ".$location.$object.", ".$object_id.", ".$container_id;

      savelog (@$error);

      return false;
    }
  }
  else return false;
}

// ---------------------- createwrapperlink -----------------------------
// function: createwrapperlink()
// input: publication name [string] (optional), location [string] (optional), object [string] (optional), category [page,comp] (optional), object ID [string] (optional), container-ID or repository media file [string] (optional), 
//        media file extension or type based on main config settings [string] (optional), media configuration based on main config settings [string] (optional)
// output: URL for download of the multimedia file of the given object or folder / false on error

// description:
// In order to track and include external user IDs in the daily statistics you need to manually add the 'user' parameter to the link in the form of: &user=[user-ID]

function createwrapperlink ($site="", $location="", $object="", $cat="", $object_id="", $container_id="", $type="", $mediaconfig="")
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  $error = array();

  if (isset ($mgmt_config) && !empty ($mgmt_config['db_connect_rdbms']))
  {
    // check permissions (only if a publication and location has been provided)
    if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']) && valid_publicationname ($site) && valid_locationname ($location))
    {
      $ownergroup = accesspermission ($site, $location, $cat);
      $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

      if (empty ($setlocalpermission['root'])) return false;
    }

    // deconvert location
    $location = deconvertpath ($location, "file");

    // if object includes special characters
    $object = trim ($object);
    $object = trim ($object, "/");
    if (specialchr ($object, ".-_~") == true) $object = createfilename ($object);

    // check if object is folder or page/component
    if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($object) && $cat != "")
    {
      if (is_dir ($location.$object))
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
          rdbms_createobject ($object_info['container_id'], $objectpath, $object_info['template'], "", $object_info['content'], $user);

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
      // if media file with container ID
      if (strpos ($container_id, "_hcm") > 0) $container_id = getmediacontainerid ($container_id);
      $object_hash = rdbms_getobject_hash ("", $container_id);
    }

    // object has been marked as deleted
    if (!empty ($object_hash) && $object_hash == "hcms:deleted") 
    {
      return false;
    }
    // object wrapper link
    elseif (!empty ($object_hash))
    {
      // add media type and configuration without verification of the values (see main config for conversion settings)
      if ($type != "" || $mediaconfig != "")
      {
        $object_hash = "hcms.".hcms_encrypt ($object_hash.":".$type.":".$mediaconfig);
      }

      // deprecated since version 5.5.8: return $mgmt_config['url_path_cms']."explorer_download.php?hcms_objref=".$object_id."&hcms_objcode=".hcms_crypt ($object_id, 3, 12);
      // deprecated since version 5.6.1: 
      // if ($mgmt_config['secure_links'] == true) return $mgmt_config['url_path_cms']."?hcms_id_token=".hcms_encrypt ($object_id.":".$timetoken);
      // else return $mgmt_config['url_path_cms']."?hcms_objid=".$object_id."&hcms_token=_".hcms_encrypt ($object_id.":".$timetoken);
      return $mgmt_config['url_path_cms']."?wl=".$object_hash;
    }
    else
    {
      $errcode = "40913";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|createwrapperlink failed due to missing object id for: ".$location.$object.", ".$object_id.", ".$container_id;

      savelog (@$error);

      return false;
    }
  }
  else return false;
}

// ---------------------- createdownloadlink -----------------------------
// function: createdownloadlink()
// input: publication name [string] (optional), location [string] (optional), object [string] (optional), category [page,comp] (optional), object ID [string] (optional), container-ID or repository media file [string] (optional),
//        media file extension or type based on main config settings [string] (optional), media configuration based on main config settings [string] (optional)
// output: URL for download of the multimedia file of the given object or folder / false on error

// description:
// In order to track and include external user IDs in the daily statistics you need to manually add the 'user' parameter to the link in the form of: &user=[user-ID]

function createdownloadlink ($site="", $location="", $object="", $cat="", $object_id="", $container_id="", $type="", $mediaconfig="")
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  $error = array();

  if (isset ($mgmt_config) && !empty ($mgmt_config['db_connect_rdbms']))
  {
    $object_hash = false;

    // check permissions (only if a publication and location has been provided)
    if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']) && valid_publicationname ($site) && valid_locationname ($location))
    {
      $ownergroup = accesspermission ($site, $location, $cat);
      $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);
  
      if (empty ($setlocalpermission['root'])) return false;
    }

    // deconvert location
    $location = deconvertpath ($location, "file");

    // if object includes special characters
    $object = trim ($object);
    $object = trim ($object, "/");
    if (specialchr ($object, ".-_~") == true) $object = createfilename ($object);

    // check if object is folder or page/component
    if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($object) && $cat != "")
    {
      if (is_dir ($location.$object))
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
          rdbms_createobject ($object_info['container_id'], $objectpath, $object_info['template'], "", $object_info['content'], $user);

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
      // if media file with container ID
      if (strpos ($container_id, "_hcm") > 0) $container_id = getmediacontainerid ($container_id);
      $object_hash = rdbms_getobject_hash ("", $container_id);
    }

    // object has been marked as deleted
    if ($object_hash == "hcms:deleted") 
    {
      return false;
    }
    // object download link
    elseif ($object_hash != false)
    {
      // add media type and configuration without verification of the values (see main config for conversion settings)
      if ($type != "" || $mediaconfig != "")
      {
        $object_hash = "hcms.".hcms_encrypt ($object_hash.":".$type.":".$mediaconfig);
      }

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
      $errcode = "40914";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|createdownloadlink failed due to missing object id for: ".$objectpath.", ".$object_id.", ".$container_id;

      savelog (@$error);

      return false;
    }
  }
  else return false;
}

// ---------------------- createmultiaccesslink -----------------------------
// function: createmultiaccesslink()
// input: multiobject [path1|path2|path3] or [array], user name of the user account to be used for access [string], link type [al,dl] (optional), token lifetime in seconds [integer] (optional), formats [string] (optional)
// output: URL for access to the requested objects / false on error

// description:
// Creates an access link to any object based on the permissions of the provided user account.

function createmultiaccesslink ($multiobject, $login, $type="al", $lifetime=0, $formats="")
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  $error = array();

  if (!empty ($multiobject) && is_array ($multiobject) && (($type == "al" && valid_objectname ($login)) || $type == "dl"))
  {
    if (!is_array ($multiobject))
    {
      // split multiobject into array
      $multiobject = link_db_getobject ($multiobject);
    }

    // object access link
    if (is_array ($multiobject) && sizeof ($multiobject) > 0)
    {
      // check permissions (only if a publication and location has been provided as path)
      if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
      {
        foreach ($multiobject as $path)
        {
          if (valid_locationname ($path) && !is_numeric ($path))
          {
            $site = getpublication ($path);
            $location = getlocation ($path);

            $ownergroup = accesspermission ($site, $location, $cat);
            $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);
    
            if (empty ($setlocalpermission['root']))
            {
              return false;
            }
          }
        }
      }

      // create hash
      $hash = createuniquetoken ();

      // create access link in DB
      $result_db = rdbms_createaccesslink ($hash, $multiobject, $type, $login, $lifetime, $formats);
 
      // object link
      if ($result_db)
      {
        return $mgmt_config['url_path_cms']."?".$type."=".$hash;
      }
      else
      {
        $errcode = "20914";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|createmultiaccesslink failed to create access link for hash=".$hash.", multiobject=".implode("|", $multiobject).", type=".$type.", login=".$login.", lifetime=".$lifetime.", formats=".$formats;
  
        savelog (@$error);

        return false;
      }
    }
    else
    {
      $errcode = "40915";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|createmultiaccesslink failed due to missing input: $multiobject";

      savelog (@$error);

      return false;
    }
  }
  else return false;
}

// --------------------------------------- createmultidownloadlink -------------------------------------------
// function: createmultidownloadlink ()
// input: publication name [string], multiobject using | as seperator [string] or [array] (optional), presentation name [string] (optional), user name [string], conversion type example: jpg [string], 
//        media configuration used for conversion (e.g.: 1024x768px) [string], link type [wrapper,download] (optional), flat hierarchy means no directories [boolean] (optional) 
// output: URL for download of the requested objects / false on error

// description:
// Generates a download link of a selection of media objects. Pages and components will be ignored.
// There is a difference in the file conversion functionality: 
// Case 1: multiobject (min. 2 objects or object is a folder) without the support of file conversion
// Case 2: multiobject with only one object (must not be a folder) with support of file conversion

function createmultidownloadlink ($site, $multiobject, $name="", $user="", $type="", $mediacfg="", $linktype="download", $flatzip=false)
{
  global $mgmt_config, $mgmt_compress, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $globalpermission, $setlocalpermission, $hcms_lang, $lang;

  if (valid_publicationname ($site) && valid_objectname ($user) && !empty ($multiobject))
  {
    // get multiobject array
    $multiobject_array = link_db_getobject ($multiobject);

    // get first object reference
    if (is_array ($multiobject_array)) $firstobject = reset ($multiobject_array);
    else $firstobject = false;

    // CASE 1: download zip-file for multiobjects (must include at least 2 objects) or a folder
    if (!empty ($multiobject_array) && is_array ($multiobject_array) && (sizeof ($multiobject_array) > 1 || is_folder ($firstobject)))
    {
      // check permissions (only if a publication and location has been provided as path)
      if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
      {
        foreach ($multiobject as $path)
        {
          if (valid_locationname ($path) && !is_numeric ($path))
          {
            $site = getpublication ($path);
            $location = getlocation ($path);

            $ownergroup = accesspermission ($site, $location, $cat);
            $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);
    
            if (empty ($setlocalpermission['root']))
            {
              return false;
            }
          }
        }
      }

      // create hash that represents all objects
      sort ($multiobject_array);
      $hash = md5 (implode ("", $multiobject_array));

      // unique name for zip-file to download
      if (!empty ($hash)) $zip_filename = "tmp".$hash;
      else $zip_filename = uniqid ("tmp");

      // temp directory holding the zip-file
      $mediadir = $mgmt_config['abs_path_temp'];

      // generate temp dir
      if (!is_dir ($mediadir)) mkdir ($mediadir, $mgmt_config['fspermission'], true);
 
      // zip files
      $result_zip = zipfiles ($site, $multiobject_array, $mediadir, $zip_filename, $user, $linktype, $flatzip);

      // set location (used for name)
      $location = $multiobject_array[0];

      // zip file name
      $media = $zip_filename.".zip";

      if ($location != "")
      {
        $media_info = getfileinfo ($site, getobject ($location).".zip", "comp");
        $name = $media_info['name'];
      }
      else $name = "Download.zip";

      // reset the additional parameters for conversion (not supported)
      $type = "";
      $mediacfg = "";
    }
    // CASE 2:  treat a single multiobject like a media object
    elseif (!empty ($multiobject_array) && is_array ($multiobject_array) && sizeof ($multiobject_array) > 0)
    {
      // get first object reference
      $object = reset ($multiobject_array);

      // get object info
      $objectinfo = getobjectinfo ($site, getlocation ($object), getobject ($object));

      // media object
      if (!empty ($objectinfo['media']))
      {
        $result_zip = true;

        // multimedia file (publication/file)
        $media = $site."/".$objectinfo['media'];
      }
    }

    // additional parameters for conversion
    $add = "";
    if ($type) $add .= '&type='.url_encode($type);
    if ($mediacfg) $add .= '&mediacfg='.url_encode($mediacfg);

    // return result
    if ($media != "" && $result_zip) return createviewlink ($site, $media, $name, true, $linktype).$add;
    else return false;
  }
  else return false;
}

// ---------------------- cleandomain -----------------------------
// function: cleandomain()
// input: string to clean from http(s)://domain [string]
// output: cleanded string / false on error

// description:
// Returns the URL notation without the protocoll://domain.com
// If you wan't to integrate hyperCMS API code into a webapplication operated in a different domain, 
// you need to define the main configuation setting $mgmt_config['cleandomain'] = false;

function cleandomain ($path)
{
  global $mgmt_config, $hcms_lang, $lang;

  // set default
  if (!isset ($mgmt_config['cleandomain'])) $mgmt_config['cleandomain'] = true;

  if ($path != "" && !empty ($mgmt_config['cleandomain']))
  {
    if (substr_count ($path, "://") == 1 && substr_count ($path, "/") > 2) $path = substr ($path, strpos ($path, "/", 9));

    if ($path != "") return $path;
    else return false;
  }
  else return $path;
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
// input: publication name [string], media file name or container name [string], user name [string] (optional)
// output: true / false

// description:
// Creates a new version of a multimedia file and container

function createversion ($site, $file, $user="sys")
{
  global $mgmt_config, $mgmt_mediaoptions, $mgmt_docoptions, $hcms_ext, $user;

  if (valid_publicationname ($site) && valid_objectname ($file))
  {
    // create version of previous content file and media file (not for thumbnails)
    if (!empty ($mgmt_config['contentversions']) && !is_thumbnail ($file, false))
    {
      // try to get container ID from multimedia file
      if ($container_id = getmediacontainerid ($file))
      {
        $media_root = getmedialocation ($site, $file, "abs_path_media").$site."/";

        // verify media file
        if (!is_cloudobject ($media_root.$file) && (!is_file ($media_root.$file) || filesize ($media_root.$file) < 10)) return true;

        $file_info = getfileinfo ($site, $file, "comp");

        // create new version of the file name
        $file_v = fileversion ($file);

        // get the file extension of the version file
        $ext_v = strrchr ($file_v, ".");

        // thumbnail
        $thumb = $file_info['filename'].".thumb.jpg";
        $thumb_v = $thumb.$ext_v;

        $thumb_root = getmedialocation ($site, $thumb, "abs_path_media").$site."/";

        // create new version of thumbnail file
        // move thumbnail (important for versioning!)
        if (is_file ($thumb_root.$thumb) && filesize ($thumb_root.$thumb) > 0)
        {
          rename ($thumb_root.$thumb, $thumb_root.$thumb_v);
        }

        // rename in cloud storage
        if (function_exists ("renamecloudobject")) renamecloudobject ($site, $thumb_root, $thumb, $thumb_v, $user);

        // create new version of original file
        // copy media file (important for image editing!)
        if (is_file ($media_root.$file) || is_link ($media_root.$file))
        {
          if (is_link ($media_root.$file)) $symlinktarget_path = readlink ($media_root.$file);
          else $symlinktarget_path = $media_root.$file;

          // copy to media repository in case media file has been exported
          @copy ($symlinktarget_path, $thumb_root.$file_v);
        }

        // copy in cloud storage
        if (function_exists ("copycloudobject")) copycloudobject ($site, $thumb_root, $file, $file_v, $user);

        // delete all other media file derivates (individual or videoplayer thumbnail files)
        // except the original media file
        deletemediafiles ($site, $file, false, false);

        // create new version of container and keep source container file as well
        $contentlocation = getcontentlocation ($container_id, 'abs_path_content');

        // get working container name
        $containerinfo = getcontainername ($container_id);
        $contentfile_wrk = $containerinfo['container'];

        if (is_file ($contentlocation.$contentfile_wrk) && filesize ($contentlocation.$contentfile_wrk) > 0)
        {
          // copy current working container
          $result = @copy ($contentlocation.$contentfile_wrk, $contentlocation.$file_v);

          // remove old versions
          if (!empty ($result) && !empty ($mgmt_config['contentversions_max']) && intval ($mgmt_config['contentversions_max']) > 0)
          {
            // select all content version files in directory sorted by date
            $files_v = getcontainerversions ($container_id);

            if (is_array ($files_v) && sizeof ($files_v) > 0)
            {
              reset ($files_v);
              $i = sizeof ($files_v);

              foreach ($files_v as $date_v => $file_v)
              {
                if (valid_objectname ($file_v) && $i > intval ($mgmt_config['contentversions_max'])) deleteversion ($site, $file_v, $user);
                $i--;
              }
            }
          }

          return $result;
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
          // copy current working container
          $result = @copy ($contentlocation.$contentfile_wrk, $contentlocation.$file_v);

          // remove old versions
          if (!empty ($result) && !empty ($mgmt_config['contentversions_max']) && intval ($mgmt_config['contentversions_max']) > 0)
          {
            // select all content version files in directory sorted by date
            $files_v = getcontainerversions ($container_id);

            if (is_array ($files_v) && sizeof ($files_v) > 0)
            {
              reset ($files_v);
              $i = sizeof ($files_v);

              foreach ($files_v as $date_v => $file_v)
              {
                if (valid_objectname ($file_v) && $i > intval ($mgmt_config['contentversions_max'])) deleteversion ($site, $file_v, $user);
                $i--;
              }
            }
          }

          return $result;
        }
        else return false;
      }
      else return false;
    }
    // versioning is disabled
    else
    {
      // delete all other media file derivates (individual or videoplayer thumbnail files)
      // except the original media file
      deletemediafiles ($site, $file, false, false);
      
      return true;
    }
  }
  else return false;
}

// -------------------------------------- rollbackversion -------------------------------------------
// function: rollbackversion()
// input: publication name [string], location [string], object name [string], container version name [string], user name [string] (optional)
// output: result_array

// description:
// Makes an older object version the current version.

function rollbackversion ($site, $location, $page, $container_version, $user="sys")
{
  global $mgmt_config, $mgmt_mediaoptions, $mgmt_docoptions, $hcms_ext, $hcms_lang, $lang;

  // initialize
  $error = array();
  $result = array();
  $result['result'] = false;
  $result['publication'] = $site;
  $result['location'] = $location;
  $result['object'] = $page;
  $result['container_id'] = "";
  $result['message'] = "";

  // set default language
  if (empty ($lang)) $lang = "en";

  // restore files to the media repository if requested
  if (!isset ($mgmt_config['restore_exported_media'])) $mgmt_config['restore_exported_media'] = true;

  // deconvert location
  $location = deconvertpath ($location, "file");

  // create valid file name
  if (strpos ($page, ".@") > 1) $page = createfilename ($page);

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($container_version) && strpos ($container_version, ".v_") > 0)
  {
    $cat = getcategory ($site, $location);

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

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
        $thumbdir = getmedialocation ($site, ".hcms.".$mediafile, "abs_path_media").$site."/";

        // if current version is a symbolic link to an external media file without an ID
        if (is_link ($thumbdir.$mediafile)  && strpos (readlink ($thumbdir.$mediafile), "_hcm".$container_id) < 1)
        {
          $currentversion_path = readlink ($thumbdir.$mediafile);
        }
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

          // remove all original files (that will be kept by function createversion for image editing)
          if (!empty ($createversion)) deletemediafiles ($site, $mediafile, true, true);
        }
        else
        {
          // create version of current container
          $createversion = createversion ($site, $container_id, $user);
        }

        // STEP 2
        // make the selected, older version the actual/latest version
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

            // get media file name from container version that will be used for the restored media file
            $mediafile_current = substr ($container_version, 0, strrpos ($container_version, "."));

            // get media file extension
            $ext_current = strrchr ($mediafile_current, ".");

            // if older version is a symbolic link
            if (is_link ($thumbdir.$container_version))
            {
              $oldversion_path = readlink ($thumbdir.$container_version);
            }
            else
            {
              $oldversion_path = $thumbdir.$container_version;
            }

            if (is_file ($oldversion_path))
            {
              // media file has _hcm in its file name will be moved to media repository
              if (empty ($currentversion_path))
              {
                // restore old media file
                $rename_2 = @rename ($oldversion_path, $thumbdir.$mediafile_current);

                // retry using copy and delete
                if ($rename_2 == false)
                {
                  $rename_2 = @copy ($oldversion_path, $thumbdir.$mediafile_current);
                  @unlink ($oldversion_path);
                }

                // delete restored file
                deletefile ($thumbdir, $container_version, 0);
              }
              // imported/linked external media file without container ID in its file name
              // will be restorted in the external location
              elseif (!empty ($currentversion_path))
              {
                // restored media file will be saved in same external location
                $target_location = getlocation ($currentversion_path);
                $target_file = getobject ($currentversion_path);
                $target_file = substr ($target_file, 0, strrpos ($target_file, ".")).$ext_current;

                // restore old media file
                $rename_2 = @rename ($oldversion_path, $target_location.$target_file);

                // retry using copy and delete
                if ($rename_2 == false)
                {
                  $rename_2 = @copy ($oldversion_path, $target_location.$target_file);
                  @unlink ($oldversion_path);
                }

                // delete restored file
                deletefile ($thumbdir, $container_version, 0);

                // create new symbolic link
                if (is_file ($target_location.$target_file))
                {
                  @symlink ($target_location.$target_file, $thumbdir.$mediafile_current);
                }
              }
            }

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
            createmedia ($site, $thumbdir, $thumbdir, $mediafile_current, "", "origthumb", true, true);
            
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

            // delete annotation images
            deleteannotationimages ($site, $mediafile);
          }
          else
          {
            $errcode = "10100";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|savecontainer failed for container ".$contentfile_wrk; 
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

              // relational DB connectivity
              if (!empty ($mgmt_config['db_connect_rdbms']))
              { 
                if (!function_exists ("rdbms_setmedianame")) include_once ($mgmt_config['abs_path_cms']."database/db_connect/".$mgmt_config['db_connect_rdbms']);
                rdbms_setmedianame ($container_id, $mediafile_current);
              }
            }
            else $result_save = false;

            // on success
            if ($result_save == true)
            {
              // get object name without extension
              $page_nameonly = substr ($page, 0, strrpos ($page, "."));

              // rename media object, if file extension has changed
              $result_rename = renameobject ($site, $location, $page, $page_nameonly, $user);

              if (!empty ($result_rename['result']))
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
            $show = "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['could-not-change-version'][$lang])."</p>\n".getescapedtext ($hcms_lang['file-is-missing-or-you-do-not-have-write-permissions'][$lang])."<br /><br />\n";
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
// input: publication name [string], location [string], object name [string], container version name [string], user name [string] (optional)
// output: true / false

// description:
// Removes the version of an object

function deleteversion ($site, $container_version, $user="sys")
{
  global $mgmt_config;

  // initialize
  $error = array();

  if (valid_publicationname ($site) && valid_objectname ($container_version) && strpos ($container_version, ".v_") > 0)
  {
    // get container ID
    if (strpos ($container_version, "_hcm") > 0) $container_id = getmediacontainerid ($container_version);
    elseif (strpos ($container_version, ".xml") > 0) $container_id = substr ($container_version, 0, strpos ($container_version, ".xml"));

    if (intval ($container_id) > 0)
    {
      // get locations
      $versiondir = getcontentlocation ($container_id, 'abs_path_content');
      $thumbdir = getmedialocation ($site, ".hcms.".$container_version, "abs_path_media").$site."/";
      $mediadir = getmedialocation ($site, $container_version, "abs_path_media").$site."/";
      
      // get file info
      $fileinfo = getfileinfo ($site, $container_version, "comp");

      // extract date and time from file extension
      $file_v_ext = substr (strrchr ($container_version, "."), 3);
      $date = substr ($file_v_ext, 0, strpos ($file_v_ext, "_"));
      $time = substr ($file_v_ext, strpos ($file_v_ext, "_") + 1);
      $time = str_replace ("-", ":", $time);
      $datetime = $date." ".$time;

      // delete media file version
      if (is_file ($mediadir.$container_version) || is_cloudobject ($container_version))
      {
        // delete media file and symbolic link to media file (if exported file)
        if (is_file ($mediadir.$container_version)) $media_result = deletefile ($mediadir, $container_version, false);

        // fallback delete of symbolic link to media file (if exported file)
        if (is_link ($thumbdir.$container_version)) deletefile ($thumbdir, $container_version, 0);

        // cloud storage
        if (function_exists ("deletecloudobject")) deletecloudobject ($site, $thumbdir, $container_version, $user);
      }
      else $media_result = true;

      // delete thumbnail file
      $thumbnail = $fileinfo['filename'].".thumb.jpg".strrchr ($container_version, ".");

      if (is_file ($thumbdir.$thumbnail))
      {
        $thumbnail_result = deletefile ($thumbdir, $thumbnail, false);

        // cloud storage
        if (function_exists ("deletecloudobject")) deletecloudobject ($site, $thumbdir, $thumbnail, $user);
      }
      else $thumbnail_result = true;

      // delete container version
      if ($media_result == true && $thumbnail_result == true && is_file ($versiondir.$container_version))
      {
        // log
        $errcode = "00786";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|Version '".$datetime."' of container ".$container_id." with name '".$fileinfo['filename']."' has been deleted by user '".$user."'";
        savelog ($error);

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
// input: type [content,template] or valid path in filesystem, report [yes,no], user name [string] (optional)
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

  $scandir = scandir ($versiondir);

  if ($scandir)
  {
    foreach ($scandir as $entry)
    {
      // content container directory
      if ($entry != "." && $entry != ".." && is_dir ($versiondir.$entry))
      {
        $report_str = deleteversions ($versiondir.$entry."/", $report, $user);
      }
      // suitable for templates and containers
      elseif ($entry != "." && $entry != ".." && is_file ($versiondir.$entry) && (preg_match ("/.v_/i", $entry) || preg_match ("/_hcm/i", $entry)))
      {
        // remove container and media file version
        if (preg_match ("/_hcm/i", $entry))
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
          $test = deletefile ($versiondir, $entry, false);
        }

        // report
        if (strtolower ($report) == "yes") 
        {
          if (empty ($test)) $report_str .= "failed to delete ".$entry."<br />\n";
          else $report_str .= "deleted ".$entry." successfully<br />\n";
        }
      }
    }

    if ($report_str != false && $report == "yes") return $report_str;
    elseif ($report_str != false) return true;
    else return false;
  }
  else return false;
}


// ========================================== FILE OPERATION =======================================

// ------------------------------------------- loadfile_header -------------------------------------------
// function: loadfile_header()
// input: path to file [string], file name [string]
// output: file content

// description:
// Loads the file headerbased on a defined header size.

function loadfile_header ($abs_path, $filename)
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  // correct user name for file lock
  $lock = createlockname ($user);

  // check and correct file
  if (valid_locationname ($abs_path) && valid_objectname ($filename))
  {
    $headersize = 2048;

    // add slash if not present at the end of the location string
    $abs_path = correctpath ($abs_path);

    // deconvert path
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }

    // symbolic link
    if (is_link ($abs_path.$filename))
    {
      $symlinktarget_path = readlink ($abs_path.$filename);

      if (is_file ($symlinktarget_path))
      {
        $abs_path = getlocation ($symlinktarget_path);
        $filename = getobject ($symlinktarget_path);
      }
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

          return $filedata;
        }
      }

      $errcode = "00883";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|warning|".$errcode."|File header of file ".$abs_path.$filename." can't be loaded";

      // save log
      savelog ($error);
    }
  } 

  return false;
}

// ------------------------------------------- loadfile_fast -------------------------------------------
// function: loadfile_fast()
// input: path to file [string], file name [string]
// output: file content

// description:
// This functions is identical to loadfile, but it does not wait for locked files to be unlocked again.
// It should only be used on files that won't be locked by the system. It is therefore recommended to use loadfile.

function loadfile_fast ($abs_path, $filename)
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  // correct user name for file lock
  $lock = createlockname ($user);

  // check and correct file
  if (valid_locationname ($abs_path) && valid_objectname ($filename))
  {
    // add slash if not present at the end of the location string
    $abs_path = correctpath ($abs_path);

    // deconvert path
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }

    // symbolic link
    if (is_link ($abs_path.$filename))
    {
      $symlinktarget_path = readlink ($abs_path.$filename);

      if (is_file ($symlinktarget_path))
      {
        $abs_path = getlocation ($symlinktarget_path);
        $filename = getobject ($symlinktarget_path);
      }
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

        if (filesize ($abs_path.$filename) > 0) $filedata = @fread ($filehandle, filesize ($abs_path.$filename));
        else $filedata = "";

        @flock ($filehandle, LOCK_UN);
        @fclose ($filehandle);

        return $filedata;
      }

      $errcode = "00884";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|warning|".$errcode."|File ".$abs_path.$filename." can't be loaded";

      // save log
      savelog ($error);
    } 
  }

  return false;
}

// ------------------------------------------- loadfile -------------------------------------------
// function: loadfile()
// input: path to file [string], file name [string]
// output: file content

// description:
// This function loads a file and waits up to 3 seconds for locked files to be unlocked

function loadfile ($abs_path, $filename)
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  // correct user name for file lock
  $lock = createlockname ($user);

  if (valid_locationname ($abs_path) && valid_objectname ($filename))
  {
    // add slash if not present at the end of the location string
    $abs_path = correctpath ($abs_path);

    // deconvert path
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }

    // symbolic link
    if (is_link ($abs_path.$filename))
    {
      $symlinktarget_path = readlink ($abs_path.$filename);

      if (is_file ($symlinktarget_path))
      {
        $abs_path = getlocation ($symlinktarget_path);
        $filename = getobject ($symlinktarget_path);
      }
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

        if (filesize ($abs_path.$filename) > 0) $filedata = @fread ($filehandle, filesize ($abs_path.$filename));
        else $filedata = "";

        @flock ($filehandle, LOCK_UN);
        @fclose ($filehandle);

        return $filedata;
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

        if (is_file ($abs_path.$filename))
        {
          $filehandle = @fopen ($abs_path.$filename, "rb");

          if ($filehandle != false)
          {
            @flock ($filehandle, LOCK_EX);

            if (filesize ($abs_path.$filename) > 0) $filedata = @fread ($filehandle, filesize ($abs_path.$filename));
            else $filedata = "";

            @flock ($filehandle, LOCK_UN);
            @fclose ($filehandle);

            return $filedata;
          }
        }
        // sleep for 0 - 100 milliseconds, to avoid collision and CPU load
        else usleep (round (rand (0, 100) * 1000));
      }

      $errcode = "00885";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|warning|".$errcode."|File ".$abs_path.$filename." can't be loaded within 3 seconds";

      // save log
      savelog ($error);
    }
  }

  return false;
}

// ---------------------------------------- loadlockfile ---------------------------------------------
// function: loadlockfile()
// input: user name [string], path to file [string], file name [string], force unlock of file after x seconds [integer]
// output: file content

// description:
// This function loads and locks a file for a specific user. It waits up to x seconds for locked files to be unlocked.
// Function loadlockfile and savelockfile includes a locking mechanismn for files.
// Every time you want to lock a file during your operations use loadlockfile.
// It is important to use savelockfile to save and unlock the file again.
// Function savelockfile requires the file to be opened by loadlockfile.
// Keep in mind that the locking is based on the user. The same user can therefore read a file that has been locked for him.

function loadlockfile ($user, $abs_path, $filename, $force_unlock=3)
{
  global $mgmt_config, $hcms_lang, $lang;

  // initialize
  $error = array();

  if (valid_objectname ($user) && valid_locationname ($abs_path) && valid_objectname ($filename))
  {
    // correct user name for file lock
    $lock = createlockname ($user);

    // add slash if not present at the end of the location string
    $abs_path = correctpath ($abs_path);

    // deconvert path
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }

    // symbolic link
    if (is_link ($abs_path.$filename))
    {
      $symlinktarget_path = readlink ($abs_path.$filename);

      if (is_file ($symlinktarget_path))
      {
        $abs_path = getlocation ($symlinktarget_path);
        $filename = getobject ($symlinktarget_path);
      }
    }

    // correct file name if locked or offline/unpublished
    if (substr_count ($filename, ".@") > 0) $filename = substr ($filename, 0, strpos ($filename, ".@"));
    if (!is_file ($abs_path.$filename) && is_file ($abs_path.$filename.".off")) $filename = $filename.".off";

    // unlocked file name
    $filename_unlocked = $filename;

    // if file exists
    if (!empty ($filename_unlocked) && (is_file ($abs_path.$filename_unlocked) || is_file ($abs_path.$filename_unlocked.".@".$lock)))
    {
      // if file is not locked by the user
      if (is_file ($abs_path.$filename_unlocked) && !is_file ($abs_path.$filename_unlocked.".@".$lock))
      {
        // lock file
        $locked = @rename ($abs_path.$filename_unlocked, $abs_path.$filename_unlocked.".@".$lock);
      }
      else $locked = true;

      // locked file name
      $filename = $filename_unlocked.".@".$lock;

      // if file is locked
      if ($locked == true)
      {
        $filehandle = @fopen ($abs_path.$filename, "rb");

        // read file data
        if ($filehandle != false)
        {
          @flock ($filehandle, LOCK_EX);

          if (filesize ($abs_path.$filename) > 0) $filedata = @fread ($filehandle, filesize ($abs_path.$filename));
          else $filedata = "";

          @flock ($filehandle, LOCK_UN);
          @fclose ($filehandle);

          return $filedata;
        }
        // unlock file on read error
        else
        {
          rename ($abs_path.$filename, $abs_path.$filename_unlocked);
        }
      }
    }
    // if file is locked by the user
    elseif ($filename_unlocked != ".folder")
    {
      // set default end time stamp for loading (now + X sec)
      if ($force_unlock > 0 && is_int ($force_unlock)) $wait = $force_unlock;
      else $wait = 3;

      $end = time() + $wait;
      $found = false;

      // try to load file
      while (time() <= $end)
      {
        $filename = $filename_unlocked;

        // file can be accessed by user (is not locked by another user)
        if (!empty ($filename_unlocked) && (is_file ($abs_path.$filename_unlocked) || is_file ($abs_path.$filename_unlocked.".@".$lock)))
        {
          // if file is not locked by the user
          if (is_file ($abs_path.$filename_unlocked) && !is_file ($abs_path.$filename_unlocked.".@".$lock))
          {
            // lock file
            $locked = rename ($abs_path.$filename_unlocked, $abs_path.$filename_unlocked.".@".$lock);
          }
          else $locked = true;

          // locked file name
          $filename = $filename_unlocked.".@".$lock;

          // if file is locked
          if ($locked == true)
          {
            $filehandle = @fopen ($abs_path.$filename, "rb");

            // file can be loaded 
            if ($filehandle != false)
            {
              @flock ($filehandle, LOCK_EX);

              if (filesize ($abs_path.$filename) > 0) $filedata = @fread ($filehandle, filesize ($abs_path.$filename));
              else $filedata = "";

              @flock ($filehandle, LOCK_UN);
              @fclose ($filehandle);

              return $filedata;
            }
            // file can not be loaded
            else
            {
              // unlock file
              rename ($abs_path.$filename, $abs_path.$filename_unlocked);
            }
          }
        }
        // sleep for 0 - 100 milliseconds to avoid collision and CPU load
        else usleep (round (rand (0, 100) * 1000));
      }

      $errcode = "00888";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|warning|".$errcode."|File ".$abs_path.$filename." can't be loaded within 3 seconds";

      // save log
      savelog ($error);

      // force unlock if locked by other user
      if ($force_unlock > 0)
      {
        $file_info = getlockedfileinfo ($abs_path, $filename_unlocked);

        if (is_array ($file_info))
        {
          // unlock file
          $result_rename = rename ($abs_path.$file_info['file'], $abs_path.$filename_unlocked);

          $errcode = "00886";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|warning|".$errcode."|File ".$abs_path.$filename_unlocked." was locked by user '".$file_info['user']."' and has been unlocked for user '".$user."'";

          // save log
          savelog ($error);

          // load file
          if ($result_rename)
          {
            // lock and load file
            $filedata = loadlockfile ($user, $abs_path, $filename_unlocked);

            return $filedata; 
          }
        }
      }
    }
  }

  return false; 
}

// --------------------------------------- savefile ------------------------------------------------
// function: savefile()
// input: path to file [string], file name [string], file content [string]
// output: true/false

// description:
// This function saves content to a file

function savefile ($abs_path, $filename, $filedata)
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  // correct user name for file lock
  $lock = createlockname ($user);

  if (valid_locationname ($abs_path) && valid_objectname ($filename))
  {
    // add slash if not present at the end of the location string
    $abs_path = correctpath ($abs_path);

    // deconvert path
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }

    // symbolic link
    if (is_link ($abs_path.$filename))
    {
      $symlinktarget_path = readlink ($abs_path.$filename);

      if (is_file ($symlinktarget_path))
      {
        $abs_path = getlocation ($symlinktarget_path);
        $filename = getobject ($symlinktarget_path);
      }
    }

    // if file is locked by the same user
    if (is_file ($abs_path.$filename.".@".$lock)) $filename = $filename.".@".$lock;
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
// input: user name [string], path to file [string], file name [string], file content [string]
// output: true/false

// description:
// Saves content to a locked file. It requires the file to be opened by loadlockfile.
// Function loadlockfile and savelockfile includes a locking mechanismen for files.
// Every time you want to lock a file during your operations use loadlockfile.
// It is important to use savelockfile to save and unlock the file again.
// savelockfile requires the file to be opened by loadlockfile before.

function savelockfile ($user, $abs_path, $filename, $filedata)
{
  global $mgmt_config, $hcms_lang, $lang;

  if (valid_objectname ($user) && valid_locationname ($abs_path) && valid_objectname ($filename))
  {
    // correct user name for file lock
    $lock = createlockname ($user);

    // add slash if not present at the end of the location string
    $abs_path = correctpath ($abs_path);

    // deconvert path 
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }

    // symbolic link
    if (is_link ($abs_path.$filename))
    {
      $symlinktarget_path = readlink ($abs_path.$filename);

      if (is_file ($symlinktarget_path))
      {
        $abs_path = getlocation ($symlinktarget_path);
        $filename = getobject ($symlinktarget_path);
      }
    }

    // define unlocked file name
    if (substr_count ($filename, ".@") > 0) $filename_unlocked = substr ($filename, 0, strpos ($filename, ".@"));
    else $filename_unlocked = $filename;

    // locked file name of user
    $filename = $filename_unlocked.".@".$lock;

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

      // unlock file
      rename ($abs_path.$filename, $abs_path.$filename_unlocked);

      if ($filehandle != false) return true;
      else return false;
    }
    // if file is unlocked (same user might access the same file using different processes)    
    elseif (is_file ($abs_path.$filename_unlocked))
    {
      $filehandle = fopen ($abs_path.$filename_unlocked, "wb");

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
  else return false;
}

// -------------------------------------- appendfile -----------------------------------------
// function: appendfile()
// input: path to file [string], file name [string], file content [string]
// output: true/false

// description: 
// Appends data to a file but cannot create a new file!
// Waits up to 3 seconds for locked files to be unlocked again.
// Files won't be unlocked if the file is already locked.

function appendfile ($abs_path, $filename, $filedata)
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  // correct user name for file lock
  $lock = createlockname ($user);

  if (valid_locationname ($abs_path) && valid_objectname ($filename) && $filedata != "")
  {
    // add slash if not present at the end of the location string
    $abs_path = correctpath ($abs_path);

    // deconvert path 
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }

    // symbolic link
    if (is_link ($abs_path.$filename))
    {
      $symlinktarget_path = readlink ($abs_path.$filename);

      if (is_file ($symlinktarget_path))
      {
        $abs_path = getlocation ($symlinktarget_path);
        $filename = getobject ($symlinktarget_path);
      }
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

        if ($filename !== false)
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
        // sleep for 0 - 100 milliseconds, to avoid colission and CPU load
        else usleep (round (rand (0, 100) * 1000));
      }
    }
    else return false;
  }
  else return false;
}

// ------------------------------------------- lockfile --------------------------------------------
// function: lockfile()
// input: user name [string], path to file [string], file name [string]
// output: true/false

// description:
// This functions lockes a file for a specific user

function lockfile ($user, $abs_path, $filename)
{
  global $mgmt_config, $hcms_lang, $lang;

  if (valid_objectname ($user) && valid_locationname ($abs_path) && valid_objectname ($filename))
  {
    // correct user name for file lock
    $lock = createlockname ($user);

    // add slash if not present at the end of the location string
    $abs_path = correctpath ($abs_path);

    // deconvert path 
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }

    // symbolic link
    if (is_link ($abs_path.$filename))
    {
      $symlinktarget_path = readlink ($abs_path.$filename);

      if (is_file ($symlinktarget_path))
      {
        $abs_path = getlocation ($symlinktarget_path);
        $filename = getobject ($symlinktarget_path);
      }
    }

    // check and correct file name
    if (strpos ($filename, ".@") > 0) $filename = substr ($filename, 0, strpos ($filename, ".@"));

    // file is already locked by same user
    if (is_file ($abs_path.$filename.".@".$lock))
    {
      return true;
    }
    // lock file
    elseif (is_file ($abs_path.$filename))
    {
      return rename ($abs_path.$filename, $abs_path.$filename.".@".$lock);
    }
    // file cannot be found
    else return false;
  }
  else return false;
}

// ------------------------------------------ unlockfile -------------------------------------------
// function: unlockfile()
// input: user name [string], path to file [string], file name [string]
// output: true/false

// description:
// This functions unlockes a file for a specific user

function unlockfile ($user, $abs_path, $filename)
{
  global $mgmt_config, $hcms_lang, $lang;

  if (valid_objectname ($user) && valid_locationname ($abs_path) && valid_objectname ($filename))
  {
    // correct user name for file lock
    $lock = createlockname ($user);

    // add slash if not present at the end of the location string
    $abs_path = correctpath ($abs_path);

    // deconvert path 
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }

    // symbolic link
    if (is_link ($abs_path.$filename))
    {
      $symlinktarget_path = readlink ($abs_path.$filename);

      if (is_file ($symlinktarget_path))
      {
        $abs_path = getlocation ($symlinktarget_path);
        $filename = getobject ($symlinktarget_path);
      }
    }

    // check and correct file name
    if (strpos ($filename, ".@") > 0) $filename = substr ($filename, 0, strpos ($filename, ".@"));

    // file is already unlocked
    if (is_file ($abs_path.$filename))
    {
      return true;
    }
    // unlock file
    elseif (is_file ($abs_path.$filename.".@".$lock))
    {
      return rename ($abs_path.$filename.".@".$lock, $abs_path.$filename);
    }
    // file cannot be found
    else return false;
  }
  else return false;
}

// ------------------------------------------ deletefile --------------------------------------------
// function: deletefile()
// input: path to file [string], file or directory name [string], delete all files in directory recursively including symbolic links [boolean] 
// output: true/false

// description:
// Deletes a file or directory. If parameter recursive is et to true all items of a directory will be removed as well.

function deletefile ($abs_path, $filename, $recursive=false)
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $error = array();

  // correct user name for file lock
  $lock = createlockname ($user);

  if (valid_locationname ($abs_path) && valid_objectname ($filename))
  {
    // add slash if not present at the end of the location string
    $abs_path = correctpath ($abs_path);

    // deconvert path 
    if (substr_count ($abs_path, "%page%") == 1 || substr_count ($abs_path, "%comp%") == 1)
    {
      $abs_path = deconvertpath ($abs_path, "file");
    }

    // if selected file is a symbolic link
    if (is_link ($abs_path.$filename))
    {
      // delete target file
      if (!empty ($recursive))
      {
        $symlinktarget_path = readlink ($abs_path.$filename);
        deletefile (getlocation ($symlinktarget_path), getobject ($symlinktarget_path), $recursive);
      }

      // remove symbolic link
      $test = unlink ($abs_path.$filename);

      if ($test == false)
      {
        $errcode = "10110";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|deletefile failed for symbolic link ".$abs_path.$filename;
      }
    }
    // if selected file is a file
    elseif (is_file ($abs_path.$filename) || is_file ($abs_path.$filename.".off") || is_file ($abs_path.$filename.".@".$lock))
    { 
      // if file is offline (for objects)
      if (is_file ($abs_path.$filename.".off")) $filename = $filename.".off"; 

      // if file is locked (for containers)
      if (is_file ($abs_path.$filename.".@".$lock)) $filename = $filename.".@".$lock; 

      // remove selected file
      $test = unlink ($abs_path.$filename);

      if ($test == false)
      {
        $errcode = "10110";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|deletefile failed for file ".$abs_path.$filename;
      }
    }
    // if selected file is a directory
    elseif (is_dir ($abs_path.$filename))
    { 
      $test = true;

      // check if directory is empty
      if (!empty ($recursive)) 
      {
        $dirfiles = scandir ($abs_path.$filename);

        foreach ($dirfiles as $key => $dirfile)
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
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|deletefile failed for directory ".$abs_path.$filename."/".$dirfile;
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
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|deletefile failed for file ".$abs_path.$filename."/".$dirfile;
                break;
              }
            }
          }
        }
      }

      // delete directory itself
      if ($test != false && is_dir ($abs_path.$filename))
      {
        $test = @rmdir ($abs_path.$filename);

        if ($test == false)
        {
          $errcode = "10109";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|deletefile failed for directory ".$abs_path.$filename;
        }
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
// input: publication name [string], media file name [string]
// output: result array

// description:
// Moves an exported media file back to the media repository.

function restoremediafile ($site, $mediafile)
{
  global $mgmt_config;

  $error = array();
  $success = true;
  $restored = false;
  $medialocation = "";

  // restore files to the media repository if requested
  if (!isset ($mgmt_config['restore_exported_media'])) $mgmt_config['restore_exported_media'] = true;

  if (valid_publicationname ($site) && valid_objectname ($mediafile) && !empty ($mgmt_config['abs_path_media']))
  {
    // get media repository directory by using a dummy media file name with same ID
    $mediaroot = getmedialocation ($site, ".hcms.".$mediafile, "abs_path_media").$site."/";

    // get media file location (can be outside of repository if a symbolic link is used)
    $medialocation = getmedialocation ($site, $mediafile, "abs_path_media", true);

    // if symbolic link is used,the full file path will be returned
    if (is_file ($medialocation))
    {
      $targetfile = $medialocation;
    }
    else
    {
      $medialocation = $medialocation.$site."/";
      $targetfile = $medialocation.$mediafile;
    }

    // move media file back to repository
    if ($mediaroot != $medialocation && is_file ($targetfile) && !empty ($mgmt_config['restore_exported_media']))
    {
      // rename symbolic link
      if (is_link ($mediaroot.$mediafile)) rename ($mediaroot.$mediafile, $mediaroot.$mediafile.".tmp");

      // use copy since cross partition move/rename of files is not supported by PHP!
      $copy = copy ($targetfile, $mediaroot.$mediafile);

      if (!$copy)
      {
        // rename/restore the original name of the symbolic link
        if (is_link ($mediaroot.$mediafile.".tmp")) rename ($mediaroot.$mediafile.".tmp", $targetfile);

        $errcode = "10602";
        $error[] = date('Y-m-d H:i').'|hypercms_main.inc.php|error|'.$errcode.'|Could not restore exported media file '.$mediafile.' to repository';

        $success = false;
      }
      else
      {
        // remove symbolic link
        if (is_link ($mediaroot.$mediafile.".tmp")) deletefile ($mediaroot, $mediafile.".tmp", 0);

        // remove external media file outside repository
        // cross partition move/rename of files is not supported by PHP!
        if (is_file ($targetfile)) deletefile (getlocation ($targetfile), getobject ($targetfile), 0);

        avoidfilecollision();

        // reset location path
        $medialocation = $mediaroot;
        $restored = true;

        $errcode = "00602";
        $error[] = date('Y-m-d H:i').'|hypercms_main.inc.php|information|'.$errcode.'|Restored exported media file '.$mediafile.' to repository';
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
// input: publication name [string], media file location [string], media file name [string], user name [string] (optional)
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

    // if media file has been restored
    if (!empty ($restoremediafile['result']) && !empty ($restoremediafile['restored']))
    {
      // set new values
      $site = $restoremediafile['publication'];
      $medialocation = $restoremediafile['location'];
      $mediafile = $restoremediafile['mediafile'];
    }

    // load from cloud storage
    if (function_exists ("loadcloudobject")) loadcloudobject ($site, $medialocation, $mediafile, $user);

    // create temp file if file is encrypted
    $createtempfile = createtempfile ($medialocation, $mediafile);

    // set restore array element
    if (!empty ($restoremediafile['result']) && !empty ($restoremediafile['restored'])) $createtempfile['restored'] = true;
    else $createtempfile['restored'] = false;

    return $createtempfile;
  }
  else return false;
}

// ------------------------------------------ deleteannotationimages --------------------------------------------
// function: deleteannotationimages()
// input: publication name [string], mediafile name [string]
// output: true/false

// description:
// Deletes all annoation images which have been created for the source images and documents

function deleteannotationimages ($site, $mediafile)
{
  global $user, $mgmt_config;

  if (valid_publicationname ($site) && valid_objectname ($mediafile) && !empty ($mgmt_config['abs_path_cms']) && !empty ($mgmt_config['abs_path_rep']))
  {
    // define media location in repository
    $medialocation = getmedialocation ($site, strrpos ($mediafile, ".").".thumb.jpg", "abs_path_media");

    // image annotation file
    $mediafile_annotation = substr ($mediafile, 0, strrpos ($mediafile, ".")).".annotation.jpg";
    // local media file
    deletefile ($medialocation.$site."/", $mediafile_annotation, 0);
    // cloud storage
    if (function_exists ("deletecloudobject")) deletecloudobject ($site, $medialocation.$site."/", $mediafile_annotation, $user);
    // remote client
    remoteclient ("delete", "abs_path_media", $site, $medialocation.$site."/", "", $mediafile_annotation, "");

    // documents annotation files (test for 1st page)
    $docfile_annotation = substr ($mediafile, 0, strrpos ($mediafile, ".")).".annotation";

    if (is_file ($medialocation.$site."/".$docfile_annotation."-0.jpg") || is_cloudobject ($medialocation.$site."/".$docfile_annotation."-0.jpg"))
    { 
      for ($p=0; $p<=100000; $p++)
      {
        $temp = $docfile_annotation."-".$p.".jpg";
        // local media file
        $delete_1 = deletefile ($medialocation.$site."/", $temp, false);
        // cloud storage
        if (function_exists ("deletecloudobject")) $delete_2 = deletecloudobject ($site, $medialocation.$site."/", $temp, $user);
        else $delete_2 = false;
        // remote client
        remoteclient ("delete", "abs_path_media", $site, $medialocation.$site."/", "", $temp, "");
        // break if no more page is available
        if (empty ($delete_1) && empty ($delete_2)) break;
      }
    }

    return true;
  }
  else return false; 
}

// ------------------------------------------ deletemediafiles --------------------------------------------
// function: deletemediafiles()
// input: publication name [string], mediafile name [string], delete original media file [boolean] (optional), delete JSON image editor file [boolean] (optional)
// output: true/false

// description:
// Deletes all derivates (thumbnails, config files, converted versions of the file) of a specific media file resource. Deletes the original media file optionally.

function deletemediafiles ($site, $mediafile, $delete_original=false, $delete_json_image=false)
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

      // symbol link is used
      if (is_link ($medialocation_orig.$site."/".$mediafile))
      {
        $target = readlink ($medialocation_orig.$site."/".$mediafile);
        if (is_file ($target)) unlink ($target);
      }

      // local media file or symbolic link to media file (due to import/export)
      $deletefile = deletefile ($medialocation_orig.$site."/", $mediafile, false);

      // remove symbolic link of exported media file (deprecated since version 7.0.7)
      // if ($deletefile && is_link ($medialocation.$site."/".$mediafile)) unlink ($medialocation.$site."/".$mediafile);
      // cloud storage
      if (function_exists ("deletecloudobject")) deletecloudobject ($site, $medialocation.$site."/", $mediafile, $user);

      // remote client
      remoteclient ("delete", "abs_path_media", $site, $medialocation.$site."/", "", $mediafile, "");

      // delete media file in temp/view as well (copied by 360 viewer)
      if (is_file ($mgmt_config['abs_path_view'].$mediafile)) deletefile ($mgmt_config['abs_path_view'], $mediafile, 0);
    }

    // delete JSON image editor file
    if ($delete_json_image == true)
    {
      $mediafile_json = substr ($mediafile, 0, strrpos ($mediafile, ".")).".json";
      // local media file
      deletefile ($medialocation.$site."/", $mediafile_json, 0);
      // cloud storage
      if (function_exists ("deletecloudobject")) deletecloudobject ($site, $medialocation.$site."/", $mediafile_json, $user);
      // remote client
      remoteclient ("delete", "abs_path_media", $site, $medialocation.$site."/", "", $mediafile_json, "");
    }

    // image thumbnail file
    $mediafile_thumb = substr ($mediafile, 0, strrpos ($mediafile, ".")).".thumb.jpg";
    // local media file
    deletefile ($medialocation.$site."/", $mediafile_thumb, 0);
    // cloud storage
    if (function_exists ("deletecloudobject")) deletecloudobject ($site, $medialocation.$site."/", $mediafile_thumb, $user);
    // remote client
    remoteclient ("delete", "abs_path_media", $site, $medialocation.$site."/", "", $mediafile_thumb, "");

    // delete annotation images
    deleteannotationimages ($site, $mediafile);

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

      // get container ID
      $container_id = getmediacontainerid ($mediafile);

      // video thumbnail images
      if (intval ($container_id) > 0 && is_dir ($medialocation.$site."/".$container_id)) deletefile ($medialocation.$site."/", $container_id, true);
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
// input: data string [string] (optional), force execution [boolean]
// output: true / false on error

// description:
// Appending data to a file ensures that the previous write process is finished (required due to issue when editing encrypted files)

function avoidfilecollision ($data="tempdata", $force=false)
{
  global $mgmt_config, $site;

  if (!empty ($force) || !valid_publicationname ($site) || (isset ($mgmt_config[$site]['crypt_content']) && $mgmt_config[$site]['crypt_content'] == true))
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
// input: search-string [string], array [array]
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
// input: location [string], object name [string], content container [string], language [string] (optional), user name [string] (optional)
// output: stream of file content / false on error

// description:
// This functions provides an object via http for viewing. Not suitable for multimedia objects!

function downloadobject ($location, $object, $container="", $lang="en", $user="")
{
  global $mgmt_config, $eventsystem, $hcms_lang, $lang;

  $location = deconvertpath ($location, "file");

  // if object includes special characters
  if (specialchr ($object, ".-_~") == true) $object = createfilename ($object);

  if (valid_locationname ($location) && valid_objectname ($object) && is_file ($location.$object))
  {
    $prefix = uniqid();

    // eventsystem
    if (!empty ($eventsystem['onfiledownload_pre'])) onfiledownload_pre ($site, $location, $media, $name, $user);

    // session ID
    if (!session_id()) $add = "?PHPSESSID=".session_id();
    else $add = "";

    // file extension
    $file_ext = strtolower (strrchr ($object, "."));

    // copy to temp/view
    if (!is_file ($mgmt_config['abs_path_view'].$prefix.$file_ext) || filemtime ($mgmt_config['abs_path_view'].$prefix.$file_ext) < filemtime ($location.$object))
    {
      copy ($location.$object, $mgmt_config['abs_path_view'].$prefix.$file_ext);
    }

    // get content via HTTP in order ro render page
    $content = @file_get_contents ($mgmt_config['url_path_view'].$prefix.$file_ext.$add);

    // deprecated since version 9.0.4 for performance improvemenents with presentation components:
    // remove temp file
    // unlink ($mgmt_config['abs_path_view'].$prefix."_".$object);

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
      if ($user == "sys") $user_stats = getuserip();
      else $user_stats = $user;

      if (is_numeric ($container_id) && $container_id > 0) rdbms_insertdailystat ("download", intval($container_id), $user_stats, false);

      // echo content
      echo $content;
    }
    // return info page
    else echo showinfopage ($hcms_lang['the-requested-object-can-not-be-provided'][$lang], $lang);

    // eventsystem
    if (!empty ($eventsystem['onfiledownload_post'])) onfiledownload_post ($site, $location, $media, $name, $user);
  }
  else return false;
}

// -------------------------------------- downloadfile -------------------------------------------
// function: downloadfile()
// input: path to file [string], file name to show for download via http [string], force file wrapper or download or no file headers for WebDAV [download,wrapper,noheader], user name [string] (optional)
// output: stream of file content / false on error

// description:
// This functions provides a file via http for view or download

function downloadfile ($filepath, $name, $force="wrapper", $user="")
{
  global $mgmt_config, $eventsystem, $hcms_lang, $lang, $is_iphone;

  // initialize
  $error = array();
  $stream = "";
  $buffer = 102400;
  $start = -1;
  $end = -1;
  $size = 0;
  $range = false;
  $session_id = "";

  if (valid_locationname ($filepath) && $name != "")
  {
    // get publication, location and media object
    $site = getpublication ($filepath);
    $location = getlocation ($filepath);
    $media = getobject ($filepath);

    // prepare media file
    $temp = preparemediafile ($site, $location, $media, $user);

    // if encrypted
    if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
    {
      $location = $temp['templocation'];
      $media = $temp['tempfile'];

      // set new file path
      $filepath = $location.$media;
    }
    // if restored
    elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
    {
      $location = $temp['location'];
      $media = $temp['file'];

      // set new file path
      $filepath = $location.$media;
    }

    // verify local media file
    if (!is_file ($filepath)) return false;

    // write and close session (important for non-blocking: any page that needs to access a session now has to wait for the long running script to finish execution before it can begin)
    if (session_id() != "")
    {
      $session_id = session_id();
      session_write_close();
    }

    // eventsystem
    if (!empty ($eventsystem['onfiledownload_pre'])) onfiledownload_pre ($site, $location, $media, $name, $user);

    // get browser information/version
    $user_client = getbrowserinfo ();

    // if browser is MS IE then we need to encode it (does not detect IE 11)
    if (isset ($user_client['msie']) && $user_client['msie'] > 0) $name = rawurlencode ($name);

    // read file without headers, no streaming supported (used by WebDAV)
    if ($force == "noheader")
    {
      $filedata = @file_get_contents ($location.$media);
    }
    // stream file and provide headers
    else
    {
      // --------------------------------- open stream --------------------------------------

      if (!($stream = fopen ($filepath, 'rb')))
      {
        // can't read the file
        header ("HTTP/1.1 500 Internal Server Error", true, 500);
        $errcode = "20602";
        $error[] = date('Y-m-d H:i').'|hypercms_main.inc.php|error|'.$errcode.'|downloadfile -> Could not open '.$filepath.')';

        // write log
        savelog (@$error);
        exit;
      }

      @ob_get_clean();

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
      // provide the file content inline for wrapper
      elseif ($force == "wrapper")
      {
        // display inline
        header ("Content-Disposition: inline; filename=\"".$name."\"");
        // content-type
        header ("Content-Type: ".getmimetype ($filepath));
        // keep in cache for 30 days
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
          header ("HTTP/1.1 416 Requested Range Not Satisfiable");
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
          header ("HTTP/1.1 416 Requested Range Not Satisfiable");
          header ("Content-Range: bytes ".$start."-".$end."/".$size);
          exit;
        }

        $start = $c_start;
        $end = $c_end;
        $length = $end - $start + 1;
        fseek ($stream, intval ($start));
        header ("HTTP/1.1 206 Partial Content");
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

      // ----------------- perform the streaming of the calculated range --------------------
      $i = $start;
      set_time_limit (0);

      while (!feof ($stream) && $i <= $end)
      {
        $bytesToRead = $buffer;

        if (($i + $bytesToRead) > $end) $bytesToRead = $end - $i + 1;

        if ($bytesToRead > 0)
        {
          $data = fread ($stream, $bytesToRead);
          echo $data;
          flush ();
        }

        $i += $bytesToRead;
      }

      // ----------------------------- close stream -----------------------------------
      fclose ($stream);
    }

    // --------------------------------- write statistics ---------------------------------
    // write stats for partial file download (range has been provided) only if start of file or end of file has been requested 
    if (!is_thumbnail ($location.$media) && substr ($user, 0, 4) != "sys:" && (($range && ($start == 0 || $end == ($size - 1))) || !$range))
    {
      if ($user == "sys") $user_stats = getuserip();
      else $user_stats = $user;

      // get temp file for statistics of ZIP file contents
      if (is_file ($mgmt_config['abs_path_temp'].$media.".dat")) $container_id = file ($mgmt_config['abs_path_temp'].$media.".dat");
      // get container ID from file name
      else $container_id = getmediacontainerid ($media);

      // save data in database
      if (!empty ($container_id))
      {
        // if container is a folder all included objects will receive a new download hit
        if ($force == "download") rdbms_insertdailystat ("download", $container_id, $user_stats, true);
        else rdbms_insertdailystat ("view", $container_id, $user_stats, false);
      }
    }

    // eventsystem
    if (!empty ($eventsystem['onfiledownload_post'])) onfiledownload_post ($site, $location, $media, $name, $user);

    // write log
    savelog (@$error);

    // restart session (that has been previously closed for non-blocking procedure)
    if (empty (session_id()) && $session_id != "") createsession();

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
// input: container file name or container id (working container will be loaded by default) [string], container type [published,work,version] (optional), user name [string]
// output: XML content of container / false on error

// description:
// This function loads a content container

function loadcontainer ($container, $type="work", $user="")
{
  global $mgmt_config, $hcms_lang, $lang;

  $error = array();
  $contentdata = false;

  if (valid_objectname ($container) && ($type == "work" || $type == "published" || $type == "version") && valid_objectname ($user))
  {
    $restored = false;

    // correct user name for file lock
    $lock = createlockname ($user);

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
    else $container_id = correctcontainername ($container);

    // if container id
    if (!empty ($container_id) && is_numeric ($container_id))
    { 
      // load container using the provided file name
      if ($type == "version")
      {
        $container = trim ($container);
      }
      // if working container name has been provided bu
      elseif (strpos ($container, ".xml.wrk") > 0)
      {
        if ($type == "published") $container = trim ($container_id).".xml";
      }
      else
      {
        if ($type == "published") $container = trim ($container_id).".xml";
        elseif ($type == "work") $container = trim ($container_id).".xml.wrk";
      }

      // container location
      $location = getcontentlocation ($container_id, 'abs_path_content');

      // get container info
      $container_info = getcontainername ($container);

      // try to load container if it is locked by another user and current user is superadmin or sys-user
      if ($type == "work" && ($user == "sys" || !empty ($_SESSION['hcms_superadmin']) && $_SESSION['hcms_superadmin'] == 1) && !empty ($container_info['container']) && is_file ($location.$container_info['container']))
      {
        $contentdata = loadfile ($location, $container_info['container']);
      }
      // load unlocked container or provided container version
      elseif (is_file ($location.$container))
      {
        $contentdata = loadfile ($location, $container);
      }
      // load locked container for current user
      elseif (valid_objectname ($user) && is_file ($location.$container.".@".$lock))
      {
        $contentdata = loadfile ($location, $container.".@".$lock);
      }
      // working container is not locked and is missing -> restore container
      elseif (empty ($container_info['user']) && $type == "work" && is_file ($location.$container_id.".xml") && !is_file ($location.$container_id.".xml.wrk"))
      {
        // try to restore working from live container
        $result_copy = copy ($location.$container_id.".xml", $location.$container_id.".xml.wrk");

        if ($result_copy == false)
        {
          $errcode = "10198";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Working container ".$container." could not be restored";

          savelog (@$error);
        }
        else
        {
          $contentdata = loadfile ($location, $container_id.".xml.wrk");
          $restored = true;
        }
      }
      // live/published container is missing -> restore container
      elseif ($type == "published" && is_file ($location.$container_id.".xml.wrk") && !is_file ($location.$container_id.".xml"))
      {
        // try to restore live from working container
        $result_copy = copy ($location.$container_id.".xml.wrk", $location.$container_id.".xml");

        if ($result_copy == false)
        {
          $errcode = "10199";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Published container ".$container." could not be restored";

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
// input: container file name or container id (working container will be loaded by default) [string], container type [published,work,version] (optional), container content [XML], user name [string], 
//        save container initally [boolean] (optional)
// output: true / false on error
// requires: config.inc.php to be loaded before

// description:
// Saves data into existing content container by default. Only if $init is set to true it will initally save a non existing container.

function savecontainer ($container, $type="work", $data="", $user="", $init=false)
{
  global $mgmt_config, $hcms_lang, $lang;

  if (valid_objectname ($container) && strpos ($data, "<container>") > 0 && ($type == "work" || $type == "published" || $type == "version") && valid_objectname ($user))
  {
    // correct user name for file lock
    $lock = createlockname ($user);

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
      // container type
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

      // save data in
      // locked container by user
      if (valid_objectname ($user) && is_file ($location.$container.".@".$lock)) return savefile ($location, $container.".@".$lock, $data);
      // unlocked container
      elseif (is_file ($location.$container)) return savefile ($location, $container, $data);
      // new container
      elseif ($init == true) return savefile ($location, $container, $data);
      // on error
      else return false;
    }
    else return false;
  }
  else return false;
}

// ========================================= WORKFLOW ============================================

// -------------------------------------------- checkworkflow -------------------------------------------
// function: checkworkflow()
// input: publication name [string], location [string], object name [string], category [page,comp] (optional), container name [string] (optional), container [XML string] (optional), view name [string] (optional), view store [string] (optional), user name [string]
// output: result array
// requires: config.inc.php, fileoperation

// description:
// Help function for function buildview to evaluate the workflow of an object and return the manipulated view store, view name, workflow ID, workflow role and the encrypted workflow token.
// Workflow roles:
// 0 ... user is not a member of workflow (no permissions)
// 1 ... read
// 2 ... read + edit
// 3 ... read + publish
// 4 ... read + edit + publish 
// 5 ... no workflow  (users permissions apply)

function checkworkflow ($site, $location, $page, $cat="", $contentfile="", $contentdata="", $buildview="cmsview", $viewstore="", $user="")
{
  global $mgmt_config;

  // initialize result values
  $checkworkflow_result = false;
  $workflow_name = "";
  $workflow_xml = "";
  $wf_id = "";
  $wf_role = 5;
  $wf_token = hcms_encrypt ("Null:".$wf_role);

  // do not execute if no workflow module is present or for container versions
  if (is_file ($mgmt_config['abs_path_cms']."workflow/frameset_workflow.php") && function_exists ("getworkflowitem"))
  {
    if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && ($contentfile == "" || strpos ("_".strrchr ($contentfile, "."), ".v_") == 0) && $buildview != "" && valid_objectname ($user))
    {
      // check category
      if ($cat == "") $cat = getcategory ($site, $location);

      // add slash if not present at the end of the location string
      $location = correctpath ($location);

      // check contentfile and viewstore
      if ($contentfile == "" || $viewstore == "")
      {
        $objectinfo = getobjectinfo ($site, $location, $page, $user);

        if (!empty ($objectinfo['container'])) $contentfile = $objectinfo['container'];
        if (!empty ($objectinfo['template'])) $templatefile = $objectinfo['template'];

        if ($viewstore == "")
        {
          $temp = loadtemplate ($site, $templatefile);

          if (!empty ($temp['content']))
          {
            $temp = getcontent ($temp['content'], "<content>", true);
            if (!empty ($temp[0])) $viewstore = $temp[0];
          }
        }
      }

      // check contentdata
      if ($contentfile != "" && $contentdata == "")
      {
        $contentdata = loadcontainer ($contentfile, "work", $user);
      }

      // get all hyperCMS tags
      if ($viewstore != "") $hypertag_array = gethypertag ($viewstore, "workflow", 0);

      // check view
      if (in_array ($buildview, array("cmsview","inlineview","publish","formedit","formmeta")) && ($cat == "page" || $cat == "comp") && !empty ($contentfile) && !empty ($contentdata) && !empty ($viewstore))
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
                if (substr (trim ($folder), -8) == "/.folder") $folder = substr (trim ($folder), 0, -7);

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
            if (!empty ($wfitem))
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
            $viewstore = str_replace ($hypertag, "<table style=\"width: 200px; padding: 0px; border: 1px solid #000000; background-color: #FFFFFF;\">\n  <tr>\n    <td>\n      <span style=\"font-family:'Verdana, Arial, Helvetica, sans-serif'; font-size:9px; color:#000000;\"><b>workflow </b>".$workflow_name."</span>\n    </td>\n  </tr>\n</table>\n", $viewstore);
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
// input: %
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

  $error = array();

  $inherit_db_data = loadlockfile ($user, $mgmt_config['abs_path_data']."config/", "inheritance.dat", 3);

  if ($inherit_db_data != false && trim ($inherit_db_data) != "")
  {
    $inherit_db = array();
    $inherit_db_array = explode ("\n", trim ($inherit_db_data));

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
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|inherit_db_record is corrupt (null), inherit_db_load failed"; 
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
    $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|loadlockfile failed in inherit_db_load"; 

    $inherit_db = false;
  }

  // save log
  savelog (@$error);

  return $inherit_db;
}

// ----------------------------------------- inherit_db_read ---------------------------------------------
// function: inherit_db_read()
// input: %
// output: inheritance database [2 dim. array]/false
// requires: hypercms_api.inc.php, config.inc.php

// description:
// This function loads the inheritance database for reading

function inherit_db_read ()
{ 
  global $user, $mgmt_config, $hcms_lang, $lang;

  $error = array();

  $inherit_db_data = loadfile ($mgmt_config['abs_path_data']."config/", "inheritance.dat");

  if ($inherit_db_data != false && trim ($inherit_db_data) != "")
  {
    $inherit_db_array = explode ("\n", trim ($inherit_db_data));

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
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|inherit_db_record is corrupt (null), inherit_db_read failed"; 
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
    $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|loadfile failed in inherit_db_read"; 

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
// input: inherit database [array]
// output: true/false
// requires: hypercms_api.inc.php, config.inc.php

// description:
// This function saves und unlocks the inheritance management database

function inherit_db_save ($inherit_db, $user)
{
  global $mgmt_config, $hcms_lang, $lang;

  if (is_array ($inherit_db) && sizeof ($inherit_db) > 0)
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
// input: inherit database [2 dim. array], child [string], parents [array]
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
// input: inherit database [2 dim. array], parent [string], childs [array]
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

// ------------------------- createinstance -----------------------------
// function: createinstance()
// input: instance name [string], settings array [array], user name [string]
// output: result array

// description:
// This function creates a new instance with all its files and the mySQL database

function createinstance ($instance_name, $settings, $user="sys")
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $error = array();
  $result_ok = false;
  $add_onload = "";
  $show = "";

  // eventsystem
  if (!empty ($eventsystem['oncreateinstance_pre']) && empty ($eventsystem['hide'])) 
    oncreateinstance_pre ($instance_name, $settings, $user); 

  // set default language
  if (empty ($lang)) $lang = "en";

  // check if input data is available
  if (
       empty ($mgmt_config['instances']) || 
       !valid_objectname ($instance_name) || strlen ($instance_name) > 100 || specialchr ($instance_name, "-_") || trim ($instance_name) == "config" || 
       empty ($mgmt_config['abs_path_cms']) || !is_dir ($mgmt_config['abs_path_cms']) ||
       empty ($settings['abs_path_data']) || empty ($settings['abs_path_rep']) || 
       empty ($settings['password']) || empty ($settings['confirm_password']) || 
       !isset ($settings['realname']) || empty ($settings['language']) || empty ($settings['email']) || 
       empty ($settings['db_host']) || empty ($settings['db_username']) || empty ($settings['db_password']) || empty ($settings['db_name']) || 
       empty ($settings['smtp_host']) || empty ($settings['smtp_username']) || empty ($settings['smtp_password']) || empty ($settings['smtp_port']) || empty ($settings['smtp_sender']) ||
       !valid_objectname ($user) 
     )
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['required-input-is-missing'][$lang]."</span>\n";
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
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['special-characters-in-expressions-are-not-allowed'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-try-another-expression'][$lang]."\n";
  }
  // check write permissions in CMS
  elseif (!is_writeable ($mgmt_config['instances']) || !is_writeable ($mgmt_config['abs_path_temp']) || !is_writeable ($mgmt_config['abs_path_view']))
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['you-do-not-have-write-permissions'][$lang]."</span>\n";
  }
  // check if instance name exists already
  elseif (is_file ($mgmt_config['instances'].trim ($instance_name).".inc.php"))
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-instance-exists-already'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-try-another-expression'][$lang]."\n";
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
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['you-do-not-have-write-permissions'][$lang]."</span>\n";

        $errcode = "10701";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|createinstance could not create ".$abs_path_data." or ".$abs_path_rep;
      }
    }

    // copy structure to internal repository
    if ($show == "" && is_dir ($abs_path_data))
    {
      $result = copyrecursive ($mgmt_config['abs_path_cms']."install/data/", $abs_path_data);
      if ($result == false) $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-instance-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
    }
    else $show = "<span class=\"hcmsHeadline\">".$hcms_lang['information-is-missing-or-you-do-not-have-write-permissions'][$lang]."</span><br />\n";

    // copy structure to external repository
    if ($show == "" && is_dir ($abs_path_rep))
    {
      $result = copyrecursive ($mgmt_config['abs_path_cms']."install/repository/", $abs_path_rep);
      if ($result == false) $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-instance-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
    }
    else $show = "<span class=\"hcmsHeadline\">".$hcms_lang['information-is-missing-or-you-do-not-have-write-permissions'][$lang]."</span><br />\n";

    // create database
    if ($show == "")
    {
      // connect to MySQL
      $mysqli = new mysqli ($settings['db_host'], $settings['db_username'], $settings['db_password']);
      
      if ($mysqli->connect_errno) $show = "<span class=\"hcmsHeadline\">DB error (".$mysqli->connect_errno."): ".$mysqli->connect_error."</span><br />\n";

      if ($show == "")
      {
        // select and create database
        if (!$mysqli->select_db ($settings['db_name']))
        {
          $sql = "CREATE DATABASE ".$settings['db_name'];

          if (!$mysqli->query ($sql)) $show = "<span class=\"hcmsHeadline\">DB error (".$mysqli->errno."): ".$mysqli->error."</span><br />\n";
          elseif (!$mysqli->select_db ($settings['db_name'])) $show = "<span class=\"hcmsHeadline\">DB error (".$mysqli->errno."): ".$mysqli->error."</span><br />\n";
        }

        // create tables
        if ($show == "")
        {
          $sql = loadfile ($mgmt_config['abs_path_cms']."database/rdbms/", "createtables.sql");

          if ($sql != "")
          {
            if (!$mysqli->multi_query ($sql)) $show = "<span class=\"hcmsHeadline\">DB error (".$mysqli->errno."): ".$mysqli->error."</span><br />\n";
          }
          else $show = "<span class=\"hcmsHeadline\">DB error: createtables.sql is missing</span><br />\n";
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
        // Pathes
        $config = str_replace ("%url_path_cms%", substr ($mgmt_config['url_path_cms'], strpos ($mgmt_config['url_path_cms'], "://")+3), $config);
        $config = str_replace ("%abs_path_cms%", $mgmt_config['abs_path_cms'], $config);
        $config = str_replace ("%url_path_rep%", $url_path_rep, $config);
        $config = str_replace ("%abs_path_rep%", $abs_path_rep, $config);
        $config = str_replace ("%url_path_data%", $url_path_data, $config);
        $config = str_replace ("%abs_path_data%", $abs_path_data, $config);

        $config = str_replace ("%os_cms%", $mgmt_config['os_cms'], $config);

        $config = str_replace ("%instances%", $mgmt_config['instances'], $config);

        // Executables
        if (!empty ($settings['pdftotext'])) $config = str_replace ("%pdftotext%", $settings['pdftotext'], $config);
        else $config = str_replace ("%pdftotext%", $mgmt_parser['.pdf'], $config);

        if (!empty ($settings['antiword'])) $config = str_replace ("%antiword%", $settings['antiword'], $config);
        else $config = str_replace ("%antiword%", $mgmt_parser['.doc'], $config);

        if (!empty ($settings['unzip'])) $config = str_replace ("%unzip%", $settings['unzip'], $config);
        else $config = str_replace ("%unzip%", $mgmt_uncompress['.zip'], $config);

        if (!empty ($settings['zip'])) $config = str_replace ("%zip%", $settings['zip'], $config);
        else $config = str_replace ("%zip%", $mgmt_compress['.zip'], $config);

        if (!empty ($settings['unoconv'])) $config = str_replace ("%unoconv%", $settings['unoconv'], $config);
        else $config = str_replace ("%unoconv%", getconfigvalue ($mgmt_docpreview), $config);

        if (!empty ($settings['convert'])) $config = str_replace ("%convert%", $settings['convert'], $config);
        else $config = str_replace ("%convert%", getconfigvalue ($mgmt_imagepreview), $config);

        if (!empty ($settings['ffmpeg'])) $config = str_replace ("%ffmpeg%", $settings['ffmpeg'], $config);
        else $config = str_replace ("%ffmpeg%", getconfigvalue ($mgmt_mediapreview), $config);

        if (!empty ($settings['yamdi'])) $config = str_replace ("%yamdi%", $settings['yamdi'], $config);
        else $config = str_replace ("%yamdi%", $mgmt_mediametadata['.flv'], $config);

        if (!empty ($settings['exiftool'])) $config = str_replace ("%exiftool%", $settings['exiftool'], $config);
        else $config = str_replace ("%exiftool%", getconfigvalue ($mgmt_mediametadata, '.jpg'), $config);

        if (!empty ($settings['tesseract'])) $config = str_replace ("%tesseract%", $settings['tesseract'], $config);
        else $config = str_replace ("%tesseract%", getconfigvalue ($mgmt_parser, ".jpg"), $config);

        if (!empty ($settings['html2pdf'])) $config = str_replace ("%html2pdf%", $settings['html2pdf'], $config);
        else $config = str_replace ("%html2pdf%", $mgmt_config['html2pdf'], $config);

        if (!empty ($settings['x11'])) $config = str_replace ("%x11%", $settings['x11'], $config);
        else $config = str_replace ("%x11%", $mgmt_config['x11'], $config);

        if (!empty ($settings['mergepdf'])) $config = str_replace ("%mergepdf%", $settings['mergepdf'], $config);
        else $config = str_replace ("%mergepdf%", $mgmt_config['mergepdf'], $config);

        // Database
        $config = str_replace ("%dbhost%", $settings['db_host'], $config);
        $config = str_replace ("%dbuser%", $settings['db_username'], $config);
        $config = str_replace ("%dbpasswd%", $settings['db_password'], $config);
        $config = str_replace ("%dbname%", $settings['db_name'], $config);

        // SMTP
        $config = str_replace ("%smtp_host%", $settings['smtp_host'], $config);
        $config = str_replace ("%smtp_username%", $settings['smtp_username'], $config);
        $config = str_replace ("%smtp_password%", $settings['smtp_password'], $config);
        $config = str_replace ("%smtp_port%", $settings['smtp_port'], $config);
        $config = str_replace ("%smtp_sender%", $settings['smtp_sender'], $config);

        $result = savefile ($mgmt_config['instances'], $instance_name.".inc.php", $config);

        if ($result == false)
        {
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-instance-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";

          $errcode = "10702";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|createinstance could not create ".$instance_name.".inc.php";
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
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-instance-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";

          $errcode = "10703";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|createinstance could not create search/search_config.inc.php";
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
      if ($username != "admin")
      {
        $result = createuser ("*Null*", $username, trim ($settings['password']), trim ($settings['confirm_password']), 0, $user);
        if (empty ($result['result'])) $show = "<span class=\"hcmsHeadline\">".$result['message']."</span><br />\n";
      }

      // edit admin user
      if ($show == "")
      {
        $result = edituser ("*Null*", $username, "", trim ($settings['password']), trim ($settings['confirm_password']), 1, 0, $settings['realname'], $settings['language'], "*Leave*", "standard", trim ($settings['email']), "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", $user);
        if (empty ($result['result'])) $show = "<span class=\"hcmsHeadline\">".$result['message']."</span><br />\n";
      }
    }

    // new instance was successfully created
    if ($show == "")
    {
      // eventsystem
      if (!empty ($eventsystem['oncreateinstance_pre']) && empty ($eventsystem['hide'])) 
        oncreateinstance_post ($instance_name, $settings, $user); 

      $result_ok = true;
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-instance-was-created-successfully'][$lang]."</span><br />\n".$hcms_lang['now-you-can-login-using-the-admin-user'][$lang]."\n";
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
// input: instance name [string], content [string], user name [string]
// output: result array

// description:
// This function saves the instance configuration in the config file

function editinstance ($instance_name, $content, $user="sys")
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $error = array();
  $result_ok = false;
  $add_onload = "";
  $show = "";
  
  // eventsystem
  if (!empty ($eventsystem['onsaveinstance_pre']) && empty ($eventsystem['hide'])) 
    onsaveinstance_pre ($instance_name, $content, $user); 

  // set default language
  if (empty ($lang)) $lang = "en";

  // check if input data is available
  // check if sent data is available
  if (!is_array ($mgmt_config) || trim ($content) == "" || !valid_publicationname ($instance_name) || empty ($mgmt_config['instances']) || !is_file ($mgmt_config['instances'].$instance_name.".inc.php") || !valid_objectname ($user) || trim ($instance_name) == "config")
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['required-input-is-missing'][$lang]."</span><br />\n".$hcms_lang['please-select-an-instance'][$lang]."\n";
  }
  else
  {
    // save content in file
    $result = savefile ($mgmt_config['instances'], $instance_name.".inc.php", trim ($content));

    if ($result == false)
    {
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-instance-could-not-be-saved'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";

      $errcode = "10721";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|editinstance could not save ".$instance_name.".inc.php";
    }

    // load configuration of instance
    require ($mgmt_config['instances'].$instance_name.".inc.php");

    // instance was successfully deleted
    if ($show == "")
    {
      // eventsystem
      if (!empty ($eventsystem['onsaveinstance_pre']) && empty ($eventsystem['hide'])) 
        onsaveinstance_post ($instance_name, $content, $user); 

      $result_ok = true;
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-instance-configuration-was-saved-successfully'][$lang]."</span><br />\n";
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
// input: instance name [string], settings [array], user name [string]
// output: result array

// description:
// This function creates a new instance with all its files and the mySQL database

function deleteinstance ($instance_name, $user="sys")
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $error = array();
  $result_ok = false;
  $add_onload = "";
  $show = "";

  // eventsystem
  if (!empty ($eventsystem['ondeleteinstance_pre']) && empty ($eventsystem['hide'])) 
    ondeleteinstance_pre ($instance_name, $user); 

  // set default language
  if (empty ($lang)) $lang = "en";

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
  if (!is_array ($mgmt_config) || !valid_objectname ($instance_name) || empty ($mgmt_config['instances']) || !is_file ($mgmt_config['instances'].$instance_name.".inc.php") || !valid_objectname ($user) || trim ($instance_name) == "config")
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['required-input-is-missing'][$lang]."</span><br />\n".$hcms_lang['please-select-an-instance'][$lang]."\n";
  }
  elseif (is_file ($mgmt_config['instances'].$instance_name.".inc.php"))
  {
    // load configuration of instance
    require ($mgmt_config['instances'].$instance_name.".inc.php");

    // delete internal repository
    if (is_dir ($mgmt_config['abs_path_data']))
    {
      $result = deletefile (getlocation ($mgmt_config['abs_path_data']), getobject ($mgmt_config['abs_path_data']), true);

      if ($result == false)
      {
        $errcode = "10731";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|deleteinstance could not fully remove ".$mgmt_config['abs_path_data'];
      }
    }
    else
    {
      $errcode = "10732";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|deleteinstance could not access ".$mgmt_config['abs_path_data'];
    }

    // delete external repository
    if (is_dir ($mgmt_config['abs_path_rep']))
    {
      $result = deletefile (getlocation ($mgmt_config['abs_path_rep']), getobject ($mgmt_config['abs_path_rep']), true);

      if ($result == false)
      {
        $errcode = "10733";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|deleteinstance could not fully remove ".$mgmt_config['abs_path_rep'];
      }
    }
    else
    {
      $errcode = "10734";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|deleteinstance could not access ".$mgmt_config['abs_path_rep'];
    }

    // delete database
    if (!empty ($mgmt_config['dbhost']) && !empty ($mgmt_config['dbuser']) && !empty ($mgmt_config['dbpasswd']) && !empty ($mgmt_config['dbname']))
    {
      // connect to MySQL
      $mysqli = new mysqli ($mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname']);
      if ($mysqli->connect_errno) $show = "<span class=\"hcmsHeadline\">DB error (".$mysqli->connect_errno."): ".$mysqli->connect_error."</span><br />\n";

      if ($show == "")
      {
        // delete database
        $sql = "DROP DATABASE IF EXISTS ".$mgmt_config['dbname'];

        if (!$mysqli->query ($sql)) $show = "<span class=\"hcmsHeadline\">DB error (".$mysqli->errno."): ".$mysqli->error."</span><br />\n";

        $mysqli->close();
      }
    }
    else
    {
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-instance-information-cannot-be-accessed'][$lang]."</span><br />\n";

      $errcode = "10736";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|deleteinstance could not connect to the database ".$mgmt_config['dbname'];
    }

    // delete main config of instance
    if ($show == "")
    {
      $result = deletefile ($mgmt_config['instances'], $instance_name.".inc.php", false);

      if ($result == false)
      {
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-instance-could-not-be-removed'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";

        $errcode = "10711";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|deleteinstance could not delete ".$instance_name.".inc.php";
      }
    }

    // instance was successfully deleted
    if ($show == "")
    {
      // eventsystem
      if (!empty ($eventsystem['ondeleteinstance_pre']) && empty ($eventsystem['hide'])) 
        ondeleteinstance_post ($instance_name, $user); 

      $result_ok = true;
      $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-instance-was-deleted-successfully'][$lang]."</span><br />\n".$hcms_lang['all-instance-entries-were-removed-successfully'][$lang]."\n";
    }
  }
  else $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-instance-information-cannot-be-accessed'][$lang]."</span><br />\n";

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
// input: publication name [string], user name [string] (optional)
// output: result array

// description:
// This function creates a new publication with all its files

function createpublication ($site_name, $user="sys")
{
  global $siteaccess, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $error = array();
  $result_ok = false;
  $add_onload = "";
  $show = "";

  // check permissions
  if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
  {
    if (!checkrootpermission ('site') || !checkrootpermission ('sitecreate'))
    {
      $result = array();
      $result['result'] = false;
      $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
      return $result;
    }
  }
    
  // eventsystem
  if (!empty ($eventsystem['oncreatepublication_pre']) && empty ($eventsystem['hide'])) 
    oncreatepublication_pre ($site_name, $user); 

  // set default language
  if (empty ($lang)) $lang = "en";

  $forbidden = array();

  // forbidden publication names since used for main config settings
  if (is_array ($mgmt_config)) $forbidden = array_keys ($mgmt_config);

  // IMPORTANT: siteaccess must be empty, otherwise valid_publicationname will return false
  $siteaccess = "";

  // check if sent data is valid
  if (!is_array ($mgmt_config) || !valid_publicationname ($site_name) || strlen ($site_name) > 100 || in_array ($site_name, $forbidden) || !preg_match ('/[a-zA-Z]/', $site_name) || !valid_objectname ($user))
  {
    $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-input-is-not-valid'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-enter-a-name'][$lang]."\n";
  }
  // test if site name includes special characters
  elseif (specialchr ($site_name, "-_") == true)
  {
    $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['special-characters-in-expressions-are-not-allowed'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-try-another-expression'][$lang]."\n";
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
            $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-publication-exists-already'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-try-another-expression'][$lang]."\n";

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
          $errcode = "10112";
          $test = @copy ($mgmt_config['abs_path_cms']."xmlschema/usergroup.schema.xml.php", $mgmt_config['abs_path_data']."user/".$site_name.".usergroup.xml.php");

          if ($test == false) $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|copy failed for /data/user/".$site_name.".usergroup.xml.php";
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
          if ($test == false) $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|savefile failed for /data/media/".$site_name.".media.tpl.dat";
        }

        // media mapping file
        if ($test != false)
        {
          // get default mapping definition
          $mapping_data = getmapping ($site_name);

          // creating mapping from definition
          $test = createmapping ($site_name, $mapping_data);

          $errcode = "10134";
          if ($test == false) $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|savefile failed for /data/config/".$site_name.".media.map.php";
        }

        // link
        if ($test != false) 
        {
          $errcode = "10135";
          $test = savefile ($mgmt_config['abs_path_data']."link/", $site_name.".link.dat", "container:|object|:|link|\n");
          if ($test == false) $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|savefile failed for /data/link/".$site_name.".link.dat";
        }

        // internal template repository
        $dir_temp = $mgmt_config['abs_path_template'].$site_name;

        if ($test != false && @!is_dir ($dir_temp)) 
        {
          $errcode = "10137";
          $test = @mkdir ($dir_temp, $mgmt_config['fspermission']);
          if ($test != false) $test = @copy ($mgmt_config['abs_path_cms']."xmlschema/template_default.schema.xml.php", $dir_temp."/default.meta.tpl");
          if ($test == false) $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|mkdir failed for ".$dir_temp;
        }

        // internal customer repository
        $dir_temp = $mgmt_config['abs_path_data']."customer/".$site_name;

        if ($test != false && @!is_dir ($dir_temp)) 
        {
          $errcode = "10138";
          $test = @mkdir ($dir_temp, $mgmt_config['fspermission']);
          if ($test == false) $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|mkdir failed for ".$dir_temp;
        } 

        // internal workflow repository
        $dir_temp = $mgmt_config['abs_path_data']."workflow/".$site_name;

        if ($test != false && @!is_dir ($dir_temp)) 
        {
          $errcode = "10139";
          $test = @mkdir ($dir_temp, $mgmt_config['fspermission']);
          if ($test == false) $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|mkdir failed for ".$dir_temp;
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

              if ($test == false) $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|mkdir failed for ".$dir_temp.$site_name;
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

          if ($test == false) $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|mkdir failed for ".$dir_temp;
          // remote client
          else remoteclient ("save", "abs_path_tplmedia", $site_name, $mgmt_config['abs_path_tplmedia'], "", $site_name, ""); 
        }

        // component repository
        $dir_temp = $mgmt_config['abs_path_comp'].$site_name;

        if ($test != false && @!is_dir ($dir_temp)) 
        {
          $errcode = "10142";
          $test = @mkdir ($dir_temp, $mgmt_config['fspermission']);

          if ($test == false) $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|createfolder failed for ".$dir_temp;
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
                    $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|savelockfile failed for user $user in user.xml.php"; 
     
                    unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php"); 
                  }
                }
                else 
                {
                  $errcode = "20301";
                  $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|updatecontent failed for user $user in user.xml.php";
 
                  unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
                  $test = false;
                }
              } 
              else
              {
                $errcode = "403101";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Publication access for user $user exists already in user.xml.php";
 
                unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
                $test = false;
              } 
            }
            else 
            {
              $errcode = "10302";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|User $user does not exist in user.xml.php";

              unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");
              $test = false;
            } 
          }
          else 
          {
            $errcode = "10303";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|loadlockfile failed for user $user for user.xml.php";

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
              // set user permission for new publication and register them in the session
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
                    if (!empty ($_SESSION['hcms_globalpermission']) && is_array ($_SESSION['hcms_globalpermission'])) $_SESSION['hcms_globalpermission'] = array_merge ($_SESSION['hcms_globalpermission'], $globalpermission_new);
                    else $_SESSION['hcms_globalpermission'] = $globalpermission_new;
                  } 

                  if ($localpermission_new != false)
                  {
                    if (!empty ($_SESSION['hcms_localpermission']) && is_array ($_SESSION['hcms_localpermission'])) $_SESSION['hcms_localpermission'] = array_merge ($_SESSION['hcms_localpermission'], $localpermission_new);
                    else $_SESSION['hcms_localpermission'] = $localpermission_new;
                  }
                }
              }

              // register new checksum
              killsession ($user, false);
              writesession ($user, $passwd, createchecksum (), $_SESSION['hcms_siteaccess']);

              // create root folder objects
              createobject ($site_name, $mgmt_config['abs_path_comp'].$site_name."/", ".folder", "default.meta.tpl", "sys");

              // eventsystem
              if (!empty ($eventsystem['oncreatepublication_post']) && empty ($eventsystem['hide'])) 
                oncreatepublication_post ($site_name, $user);
 
              $add_onload = "parent.frames['mainFrame'].location='site_edit_form.php?preview=no&site_name=".url_encode($site_name)."'; setTimeout (function(){parent.frames['controlFrame'].location='control_site_menu.php?site_name=".url_encode($site_name)."'}, 2000); ";
              $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-publication-was-created-successfully'][$lang]."</span><br />\n".$hcms_lang['now-you-can-edit-the-publication'][$lang]."\n";

              // success
              $result_ok = true;
            }
            else
            {
              $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
              $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-publication-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
            }
          }
          else
          {
            $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-publication-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['an-error-occurred-in-the-data-manipulation'][$lang]."\n";
          }
        }
      }
    }
    else
    {
      $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-publication-information-cannot-be-accessed'][$lang]."</span><br />\n".$hcms_lang['the-publication-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
    }
  }

  // unlock file
  inherit_db_close ($user);

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
// input: publication name [string], publication settings with setting name as key and parameter as value [array], user name [string] (optional)
// output: result array

// description:
// This function saves all settings of a publication. It is a good advice to load the settings of a publication and manipulate the values in order to provide all settings as input.
//
// Settings (keys for settings array):
// inherit_comp ... inherit components [boolean]
// inherit_tpl ... inherit templates [boolean]
// youtube_token ... Youtube token [string]
// registration ... enable registration of new users [boolean]
// registration_group ... assign new users to the group [string]
// registration_notify ... notify the users whenever a new user registered [string]
// site_admin0 ... enable publication management [true]
// url_path_page ... URL to page root on management server [string]
// abs_path_page .... absolute path to page root on management server  [string]
// exclude_folders ... exlude folders from the system [string]
// allow_ip ... only enable access for the provided IP addresses [string]
// webdav ... enable WebDAV access [boolean]
// webdav_dl ... enable download links for WebDAV [boolean]
// webdav_al ... enable access links for WebDAV  [boolean]
// default_codepage ... deafult charcter set of the publication [string]
// sendmail ... enable send mail [boolean]
// mailserver ... mailserver name [string]
// portalaccesslink ... enable portal access via public link [boolean]
// accesslinkuser ... user to be used for access links [string]
// watermark_image ... watermark settings for images (-wm /home/hypercms/public_html/logo_watermark.png->topleft->10) [string]
// watermark_video ... watermark settings for videos (-wm /home/hypercms/public_html/logo_watermark.png->topleft->10) [string]
// sharesociallink ... enable social media link sharing [boolean]
// youtube ... enbale Youtube upload [boolean]
// theme ... standard design theme of the publication [string]
// translate ... languges that can be used for automated translation (en,fr,de) [string]
// ocr ... languges that can be used for OCR (en,de,...) [string]
// crypt_content ... encrypt content [boolean]
// connector_rest ... Enable RESTful API [boolean]
// connector_soap ... enable SOAP API [boolean]
// storage_limit ... storage limit in MB [integer]
// gs_access_json ... Google Cloud service JSON access code [string]
// gs_analyze_image ... Enable automatted image tagging [boolean]
// gs_analyze_video ... Enable automatted video tagging [boolean]
// gs_speech2text ... Enable automatted speech to text translation for video and audio files [boolean]
// gs_speech2text_langcode ... language code to be used for Google Speech2Text Cloud service (en-US) [string]
// url_publ_page ... URL to page root on publication server [string]
// abs_publ_page ... absolute path to page root on publication server [string]
// url_publ_rep ... URL to repository root on publication server [string]
// abs_publ_rep ... absolute path to repository root on publication server [string]
// abs_publ_app ... absolute path to application root on publication server [string]
// publ_os ... operating system used (UNIX or WIN) [string]
// remoteclient ... URL to remote client on remote webserver [string]
// ldap_servers ... The domain controllers option is an array of servers located on your network that serve Active Directory [string]
// ldap_base_dn ... The base distinguished name is the base distinguished name you'd like to perform operations on. An example base DN would be `DC=corp,DC=acme,DC=org`. [string]
// ldap_userdomain ... The user domain is the suffix of your user accounts in AD [string]
// ldap_version ... LDAP protocol version [2,3]
// ldap_port ... The port option is used for authenticating and binding to your AD/LDAP server. The default ports are already used for non SSL and SSL connections (389 and 636). Only insert a port if your AD server uses a unique port. [integer]
// ldap_follow_referrals ... The follow referrals option is a boolean to tell active directory to follow a referral to another server on your network if the server queried knows the information you are asking for exists, but does not yet contain a copy of it locally. This option is defaulted to false. [boolean]
// ldap_use_ssl ... If you need to be able to change user passwords on your server, then an SSL or TLS connection is required [boolean]
// ldap_use_tls ... If you need to be able to change user passwords on your server, then an SSL or TLS connection is required [boolean]
// ldap_sync ... Synchronize LDAP users with system user (create and edit user) [boolean]
// ldap_delete_user ... Delete user that do not exist in LDAP [boolean]
// ldap_keep_groups ... Keep existing group memberships of user [boolean]
// ldap_username_dn ... Define the user DN for the LDAP bind (required by some LDAP servers, e.g.: uid=%user%,cn=users) [string]
// ldap_user_filter ... Define the user filter for the search in LDAP/AD (sAMAccountName) [string]
// ldap_user_attributes ... Define the user attributes you want so sync with LDAP/AD ('memberof', 'givenname', 'sn', 'telephonenumber', 'mail') [array]
// ldap_sync_groups_mapping ... Mapping based on a search string that defines the users group membership like "OU=MANAGER GROUP"=>"ChiefEditor" [array]

function editpublication ($site_name, $setting, $user="sys")
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $result_ok = false;
  $add_onload = "";
  $show = "";
  $exclude_folders_new = "";

  // check permissions
  if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
  {
    if (!checkrootpermission ('site') || !checkrootpermission ('siteedit'))
    {
      $result = array();
      $result['result'] = false;
      $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
      return $result;
    }
  }

  // set default language
  if (empty ($lang)) $lang = "en";

  // check if publication name is an attribute of the request string
  if (strpos ($site_name, ".php?") > 0)
  {
    // extract login
    $site_name = getattribute ($site_name, "site_name");
  }

  if (valid_publicationname ($site_name) && is_array ($setting) && valid_objectname ($user))
  {
    // html decode all settings
    $setting = html_decode ($setting);

    // set boolean values
    if (array_key_exists ('site_admin', $setting) && $setting['site_admin'] == true) $site_admin_new = "true";
    else $site_admin_new = "false";

    if (array_key_exists ('linkengine', $setting) && $setting['linkengine'] == true) $linkengine_new = "true";
    else $linkengine_new = "false";

    if (array_key_exists ('sendmail', $setting) && $setting['sendmail'] == true) $sendmail_new = "true";
    else $sendmail_new = "false";

    if (array_key_exists ('portalaccesslink', $setting) && $setting['portalaccesslink'] == true) $portalaccesslink_new = "true";
    else $portalaccesslink_new = "false";

    if (array_key_exists ('webdav', $setting) && $setting['webdav'] == true) $webdav_new = "true";
    else $webdav_new = "false";

    if (array_key_exists ('webdav_dl', $setting) && $setting['webdav_dl'] == true) $webdav_dl_new = "true";
    else $webdav_dl_new = "false";

    if (array_key_exists ('webdav_al', $setting) && $setting['webdav_al'] == true) $webdav_al_new = "true";
    else $webdav_al_new = "false";

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

    if (array_key_exists ('upload_userinput', $setting) && $setting['upload_userinput'] == true) $upload_userinput_new = "true";
    else $upload_userinput_new = "false";

    if (array_key_exists ('upload_pages', $setting) && $setting['upload_pages'] == true) $upload_pages_new = "true";
    else $upload_pages_new = "false";

    if (array_key_exists ('crypt_content', $setting) && $setting['crypt_content'] == true) $crypt_content_new = "true";
    else $crypt_content_new = "false";

    if (array_key_exists ('connector_rest', $setting) && $setting['connector_rest'] == true) $connector_rest_new = "true";
    else $connector_rest_new = "false";

    if (array_key_exists ('connector_soap', $setting) && $setting['connector_soap'] == true) $connector_soap_new = "true";
    else $connector_soap_new = "false";

    if (array_key_exists ('gs_analyze_image', $setting) && $setting['gs_analyze_image'] == true) $gs_analyze_image_new = "true";
    else $gs_analyze_image_new = "false";

    if (array_key_exists ('gs_analyze_video', $setting) && $setting['gs_analyze_video'] == true) $gs_analyze_video_new = "true";
    else $gs_analyze_video_new = "false";

    if (array_key_exists ('gs_speech2text', $setting) && $setting['gs_speech2text'] == true) $gs_speech2text_new = "true";
    else $gs_speech2text_new = "false";

    if (array_key_exists ('ldap_follow_referrals', $setting) && $setting['ldap_follow_referrals'] == true) $ldap_follow_referrals_new = "true";
    else $ldap_follow_referrals_new = "false";

    if (array_key_exists ('ldap_use_ssl', $setting) && $setting['ldap_use_ssl'] == true) $ldap_use_ssl_new = "true";
    else $ldap_use_ssl_new = "false";

    if (array_key_exists ('ldap_use_tls', $setting) && $setting['ldap_use_tls'] == true) $ldap_use_tls_new = "true";
    else $ldap_use_tls_new = "false";

    if (array_key_exists ('ldap_sync', $setting) && $setting['ldap_sync'] == true) $ldap_sync_new = "true";
    else $ldap_sync_new = "false";

    if (array_key_exists ('ldap_delete_user', $setting) && $setting['ldap_delete_user'] == true) $ldap_delete_user_new = "true";
    else $ldap_delete_user_new = "false";

    if (array_key_exists ('ldap_keep_groups', $setting) && $setting['ldap_keep_groups'] == true) $ldap_keep_groups_new = "true";
    else $ldap_keep_groups_new = "false";

    // create htaccess and web.config files for DAM usage
    if ($dam_new == "true")
    {
      if (!is_file ($mgmt_config['abs_path_media'].$site_name."/.htaccess")) savefile ($mgmt_config['abs_path_media'].$site_name."/", ".htaccess", "Require all denied");
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
    if (array_key_exists ('theme', $setting)) $theme_new = trim ($setting['theme']);
    else $theme_new = "";

    // storage limit
    if (array_key_exists ('storage_limit', $setting) && is_numeric ($setting['storage_limit'])) $storage_limit_new = trim ($setting['storage_limit']);
    else $storage_limit_new = "\"\"";

    // storage type
    if (array_key_exists ('storage_type', $setting) && trim ($setting['storage_type']) != "") $storage_type_new = trim ($setting['storage_type']);
    else $storage_type_new = "";

    // set codepage if none is given
    if (!array_key_exists ('default_codepage', $setting) || $setting['default_codepage'] == "") $default_codepage_new = "UTF-8";
    else $default_codepage_new = $setting['default_codepage'];

    // watermark for images
    if (!array_key_exists ('watermark_image', $setting) || $setting['watermark_image'] == "") $watermark_image_new = "";
    else $watermark_image_new = trim ($setting['watermark_image']);

    // watermark for videos
    if (!array_key_exists ('watermark_video', $setting) || $setting['watermark_video'] == "") $watermark_video_new = "";
    else $watermark_video_new = trim ($setting['watermark_video']);
 
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
    if (array_key_exists('mailserver', $setting)) $mailserver_new = trim ($setting['mailserver']);
    else $mailserver_new = "";

    // set user account for general accesslinks
    if (array_key_exists('accesslinkuser', $setting)) $accesslinkuser_new = trim ($setting['accesslinkuser']);
    else $accesslinkuser_new = "";

    // set OS
    if (array_key_exists('publ_os', $setting)) $publ_os_new = trim ($setting['publ_os']);
    else $publ_os_new = "UNIX";

    // set remote client
    if (array_key_exists('remoteclient', $setting)) $remoteclient_new = trim ($setting['remoteclient']);
    else $remoteclient_new = "";

    // set languages for translation
    if (array_key_exists('translate', $setting)) $translate_new = trim ($setting['translate']);
    else $translate_new = "";

    // set languages for OCR
    if (array_key_exists('ocr', $setting)) $ocr_new = trim ($setting['ocr']);
    else $ocr_new = "";

    // set registration of new users
    if (array_key_exists('registration', $setting) && $setting['registration'] == true) $registration_new = "true";
    else $registration_new = "false";

    // set user group assignment for newly registered users
    if (array_key_exists('registration_group', $setting)) $registrationgroup_new = trim ($setting['registration_group']);
    else $registrationgroup_new = "";

    // set user notification if a new user has been registered
    if (array_key_exists('registration_notify', $setting)) $registrationnotify_new = trim ($setting['registration_notify']);
    else $registrationnotify_new = "";

     // set user notification if an error or warning has been logged
    if (array_key_exists('eventlog_notify', $setting)) $eventlognotify_new = trim ($setting['eventlog_notify']);
    else $eventlognotify_new = "";

    // set and save Google Cloud API Key file (JSON string)
    if (array_key_exists('gs_access_json', $setting)) $gs_access_json_new = $setting['gs_access_json'];
    else $gs_access_json_new = "";

    if (trim ($gs_access_json_new) != "")
    {
      savefile ($mgmt_config['abs_path_data']."config/", $site_name.".google_cloud_key.json", $gs_access_json_new);
      $gs_access_json_file_new = $mgmt_config['abs_path_data']."config/".$site_name.".google_cloud_key.json";
    }
    else
    {
      deletefile ($mgmt_config['abs_path_data']."config/", $site_name.".google_cloud_key.json", false);
      $gs_access_json_file_new = "";
    }

    // set Google Speech-to-Text language code
    if (array_key_exists('gs_speech2text_langcode', $setting)) $gs_speech2text_langcode_new = trim ($setting['gs_speech2text_langcode']);
    else $gs_speech2text_langcode_new = "";

    // AD Domain controllers
    if (is_array ($setting) && array_key_exists ('ldap_servers', $setting) && $setting['ldap_servers'] != "")
    {
      $ldap_servers_new = splitstring ($setting['ldap_servers']);
      $ldap_servers_new = implode (", ", $ldap_servers_new);
    }
    else $ldap_servers_new = "";

    // LDAP base DN
    if (array_key_exists('ldap_base_dn', $setting)) $ldap_base_dn_new = trim ($setting['ldap_base_dn']);
    else $ldap_base_dn_new = "";

    // LDAP user domain
    if (array_key_exists('ldap_userdomain', $setting)) $ldap_userdomain_new = trim ($setting['ldap_userdomain']);
    else $ldap_userdomain_new = "";

    // LDAP version
    if (array_key_exists('ldap_version', $setting)) $ldap_version_new = trim ($setting['ldap_version']);
    else $ldap_version_new = "";

    // LDAP port
    if (array_key_exists('ldap_port', $setting)) $ldap_port_new = trim ($setting['ldap_port']);
    else $ldap_port_new = "";

    // LDAP user DN
    if (array_key_exists('ldap_username_dn', $setting)) $ldap_username_dn_new = trim ($setting['ldap_username_dn']);
    else $ldap_username_dn_new = "";

    // LDAP user filter
    if (array_key_exists('ldap_user_filter', $setting)) $ldap_user_filter_new = trim ($setting['ldap_user_filter']);
    else $ldap_user_filter_new = "";

    // LDAP user attributes
    $ldap_user_attributes_new = "";

    if (array_key_exists('ldap_user_attributes', $setting))
    {
      $temp_array = splitstring ($setting['ldap_user_attributes']);
      $temp_result = array();

      if (is_array ($temp_array))
      {
        foreach ($temp_array as $temp)
        {
          // clean
          $temp = trim ($temp, " '\"\t\n\r\0\x0B");
          $temp = str_replace ("'", "\\'", $temp);

          $temp_result[] = "'".$temp."'";
        }

        if (sizeof ($temp_result) > 0) $ldap_user_attributes_new = implode (", ", $temp_result);
      }
    }

    // LDAP mapping for user group sync
    $ldap_sync_groups_mapping_new = "";

    if (array_key_exists('ldap_sync_groups_mapping', $setting) && strpos ($setting['ldap_sync_groups_mapping'], "=>") > 0)
    {
      $temp_array = explode ("\n", $setting['ldap_sync_groups_mapping']);
      $temp_result = array();

      if (is_array ($temp_array))
      {
        foreach ($temp_array as $temp)
        {
          list ($key, $value) = explode ("=>", $temp);

          // clean
          $key = trim ($key, " '\"\t\n\r\0\x0B");
          $key = str_replace ("'", "\\'", $key);
          $value = trim ($value, " '\"\t\n\r\0\x0B");
          $value = str_replace ("'", "\\'", $value);

          $temp_result[] = "'".$key."'=>'".$value."'";
        }

        if (sizeof ($temp_result) > 0) $ldap_sync_groups_mapping_new = implode (", ", $temp_result);
      }
    }
    
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
\$mgmt_config['".$site_name."']['url_path_page'] = \"".str_replace ("\"", "", $url_path_page_new)."\";
\$mgmt_config['".$site_name."']['abs_path_page'] = \"".str_replace ("\"", "", $abs_path_page_new)."\";

// Exclude directories (folders) for hyperCMS Navigator view
// absolute path required!
// use ';' as delimiter.
// e.g. \$mgmt_config['Publication']['exclude_folders'] = \"/home/domain/directory1/;/home/domain/directory2/;\"
// Be aware: if you exclude the doc root of your (virtual) webserver you will see no folders at all!
\$mgmt_config['".$site_name."']['exclude_folders'] = \"".str_replace ("\"", "", $exclude_folders_new)."\";

// Allow access for the follwomg IP addresses.
// use ';' as delimiter.
\$mgmt_config['".$site_name."']['allow_ip'] = \"".str_replace ("\"", "", $allow_ip_new)."\";

// Activate multimedia component access through hyperCMS native WebDAV server.
// true = WebDAV active, false = WebDAV inactive
\$mgmt_config['".$site_name."']['webdav'] = ".$webdav_new.";
// Download links in virtual HTML files
\$mgmt_config['".$site_name."']['webdav_dl'] = ".$webdav_dl_new.";
// Access links in virtual HTML files
\$mgmt_config['".$site_name."']['webdav_al'] = ".$webdav_al_new.";

// Activate (true) or deactivate (false) the link mangement engine
\$mgmt_config['".$site_name."']['linkengine'] = ".$linkengine_new.";

// Default codepage, if no codepage is defined in template
\$mgmt_config['".$site_name."']['default_codepage'] = \"".str_replace ("\"", "", $default_codepage_new)."\";

// Activate (true) or deactivate (false) sendmail (user will be informed via SMTP)
\$mgmt_config['".$site_name."']['sendmail'] = ".$sendmail_new.";

// Mailserver name, necessary if sendmail is activated
// (an account named hyperCMS@mailserver should be available)
\$mgmt_config['".$site_name."']['mailserver'] = \"".str_replace ("\"", "", $mailserver_new)."\";

// Activate (true) or deactivate (false) Portal access links for public access to themed portals
\$mgmt_config['".$site_name."']['portalaccesslink'] = ".$portalaccesslink_new.";

// Access link user account, necessary to generate and use general accesslinks
// (user account must be created and assigned to a user group)
\$mgmt_config['".$site_name."']['accesslinkuser'] = \"".str_replace ("\"", "", $accesslinkuser_new)."\";

// Special characters in object and folder names
// Allow (false) or forbid (true) special characters in object and folder names.
\$mgmt_config['".$site_name."']['specialchr_disable'] = ".$specialchr_disable_new.";

// Use only as DAM
// Enable (false) or disable (true) restricted system usage as DAM
\$mgmt_config['".$site_name."']['dam'] = ".$dam_new.";

// Use taxonomy
// Enable (false) or disable (true) taxonomy browsing and search integration
\$mgmt_config['".$site_name."']['taxonomy'] = ".$taxonomy_new.";

// User must provide metadata for file uploads
// Enable (true) or disable (false) user input for metadata right after file upload
\$mgmt_config['".$site_name."']['upload_userinput'] = ".$upload_userinput_new.";

// Enable direct file uploads in page structure (if used as CMS)
// Enable (true) or disable (false) file upload (files are not managed by the system!) 
\$mgmt_config['".$site_name."']['upload_pages'] = ".$upload_pages_new.";

// Storage limit for all multimedia files (assets) in MB
\$mgmt_config['".$site_name."']['storage_limit'] = ".$storage_limit_new.";

";

  // only if the connector module is installed
  if (is_dir ($mgmt_config['abs_path_cms']."connector/")) $site_mgmt_config .= "
// Storage type for all multimedia files (assets), possible values are 'local', 'cloud' and 'both'
\$mgmt_config['".$site_name."']['storage_type'] = \"".$storage_type_new."\";

// RESTful API
// Enable (true) or disable (false) the access to the API
\$mgmt_config['".$site_name."']['connector_rest'] = ".$connector_soap_new.";

// SOAP API
// Enable (true) or disable (false) the access to the API
\$mgmt_config['".$site_name."']['connector_soap'] = ".$connector_soap_new.";

";

  $site_mgmt_config .= "
// Encrypt content on server
// Enable (true) or disable (false) encryption of content
\$mgmt_config['".$site_name."']['crypt_content'] = ".$crypt_content_new.";

// Watermark options for images and videos
\$mgmt_config['".$site_name."']['watermark_image'] = \"".str_replace ("\"", "'", $watermark_image_new)."\";
\$mgmt_config['".$site_name."']['watermark_video'] = \"".str_replace ("\"", "'", $watermark_video_new)."\";

";

  // only if the connector module is installed
  if (is_dir ($mgmt_config['abs_path_cms']."connector/")) $site_mgmt_config .= "
// Allow sharing of social media links
// Enable (false) or disable (true) restricted system usage of social media link sharing. 
\$mgmt_config['".$site_name."']['sharesociallink'] = ".$sharesociallink_new.";

// Allow upload to Youtube
// Enable (false) or disable (true) restricted system usage of youtube uploader
// youtube_token is the permanent session key for the upload interface
\$mgmt_config['".$site_name."']['youtube'] = ".$youtube_new.";

// Enable translation
// Enabled languages for translation service
\$mgmt_config['".$site_name."']['translate'] = \"".str_replace ("\"", "", $translate_new)."\";

// Enable OCR languages
// Enabled languages for OCR
\$mgmt_config['".$site_name."']['ocr'] = \"".str_replace ("\"", "", $ocr_new)."\";

";

  $site_mgmt_config .= "
// Design theme
\$mgmt_config['".$site_name."']['theme'] = \"".str_replace ("\"", "", $theme_new)."\";

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
\$mgmt_config['".$site_name."']['remoteclient'] = \"".str_replace ("\"", "", $remoteclient_new)."\";

// Enable (true) or disable (false) registration of new users
\$mgmt_config['".$site_name."']['registration'] = ".$registration_new.";

// Set user group assignment for newly registered users
\$mgmt_config['".$site_name."']['registration_group'] = \"".str_replace ("\"", "", $registrationgroup_new)."\";

// Set user notification if a new user has been registered (comma-speratated list of users)
\$mgmt_config['".$site_name."']['registration_notify'] = \"".str_replace ("\"", "", $registrationnotify_new)."\";

// Set user notification if an error or warning has been logged
\$mgmt_config['".$site_name."']['eventlog_notify'] = \"".str_replace ("\"", "", $eventlognotify_new)."\";

// Google Cloud API Key file
\$mgmt_config['".$site_name."']['gs_access_json'] = \"".str_replace ("\"", "'", $gs_access_json_file_new)."\";
// Google Vision
\$mgmt_config['".$site_name."']['gs_analyze_image'] = ".$gs_analyze_image_new.";
// Google Video Intelligence
\$mgmt_config['".$site_name."']['gs_analyze_video'] = ".$gs_analyze_video_new.";
// Google Speech-to-Text
\$mgmt_config['".$site_name."']['gs_speech2text'] = ".$gs_speech2text_new.";
// Google Speech-to-Text language code
\$mgmt_config['".$site_name."']['gs_speech2text_langcode'] = \"".str_replace ("\"", "'", $gs_speech2text_langcode_new)."\";
";

  // only if the a auth connector has been defined and the connector file does exist
  if (
      !empty ($mgmt_config['authconnect']) && empty ($mgmt_config['authconnect_all']) && 
      (is_file ($mgmt_config['abs_path_cms']."connector/authconnect/".$mgmt_config['authconnect'].".inc.php") || is_file ($mgmt_config['abs_path_data']."connect/".$mgmt_config['authconnect'].".inc.php"))
  ) $site_mgmt_config .= "
// LDAP/AD Domain controllers (required)
\$mgmt_config['".$site_name."']['ldap_servers'] = \"".str_replace ("\"", "", $ldap_servers_new)."\";

// LDAP/AD Base DN (required)
\$mgmt_config['".$site_name."']['ldap_base_dn'] = \"".str_replace ("\"", "", $ldap_base_dn_new)."\";

// LDAP/AD User domain (required)
\$mgmt_config['".$site_name."']['ldap_userdomain'] = \"".str_replace ("\"", "", $ldap_userdomain_new)."\";

// LDAP/AD Version (required)
\$mgmt_config['".$site_name."']['ldap_version'] = ".intval ($ldap_version_new).";

// LDAP/AD Port (optional)
\$mgmt_config['".$site_name."']['ldap_port'] = ".intval ($ldap_port_new).";

// LDAP/AD Follow referrals (optional)
// Enable (true) or disable (false)
\$mgmt_config['".$site_name."']['ldap_follow_referrals'] = ".$ldap_follow_referrals_new.";

// LDAP/AD Use SSL (optional)
// Enable (true) or disable (false)
\$mgmt_config['".$site_name."']['ldap_use_ssl'] = ".$ldap_use_ssl_new.";

// LDAP/AD Use TLS (optional)
// Enable (true) or disable (false)
\$mgmt_config['".$site_name."']['ldap_use_tls'] = ".$ldap_use_tls_new.";

// LDAP/AD Sync users (optional)
// Enable (true) or disable (false) the sync of LDAP users with the system users
\$mgmt_config['".$site_name."']['ldap_sync'] = ".$ldap_sync_new.";

// Delete the user if it does not exist in the LDAP/AD directory (true) or leave user (false)
\$mgmt_config['".$site_name."']['ldap_delete_user'] = ".$ldap_delete_user_new.";

// Keep existing group memberships of user (true) or not (false)
\$mgmt_config['".$site_name."']['ldap_keep_groups'] = ".$ldap_keep_groups_new.";

// Define the user DN for the LDAP bind
\$mgmt_config['".$site_name."']['ldap_username_dn'] = \"".str_replace ("\"", "'", $ldap_username_dn_new)."\";

// Define the user filter for the search in LDAP/AD
\$mgmt_config['".$site_name."']['ldap_user_filter'] = \"".str_replace ("\"", "'", $ldap_user_filter_new)."\";

// Define the user attributes you want so sync with LDAP/AD
\$mgmt_config['".$site_name."']['ldap_user_attributes'] = array(".$ldap_user_attributes_new.");

// Do not synchronize AD/LDAP groups with publications of the user
\$mgmt_config['".$site_name."']['ldap_sync_publications_mapping'] = array();

// Synchronize AD/LDAP groups with user groups of the user
// Define mapping based on a search string that defines the users group membership
\$mgmt_config['".$site_name."']['ldap_sync_groups_mapping'] = array(".$ldap_sync_groups_mapping_new.");
";

  // publication management config
  if (valid_publicationname ($site_name) && is_file ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php"))
  {
    require ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php");
  }

  if (!empty ($mgmt_config[$site_name]['hierarchy'])) $site_mgmt_config .= "
// Metadata/Content Hierarchy
\$mgmt_config['".$site_name."']['hierarchy'][] = \"".$hierarchy."\";
";
  $site_mgmt_config .= "
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
url_publ_page = \"".str_replace ("\"", "", $url_publ_page_new)."\"
abs_publ_page = \"".str_replace ("\"", "", $abs_publ_page_new)."\"

; Set URL and absolute path to the repository:
; The local repository includes:
; - component repository
; - content media repository
; - template media repository
; - link index repository
; - local configuration
url_publ_rep = \"".str_replace ("\"", "", $url_publ_rep_new)."\"
abs_publ_rep = \"".str_replace ("\"", "", $abs_publ_rep_new)."\"

; Set component inclusion type:
; Components can be included using file system access of via HTTP
; Set the value of this parameter true if you want to use HTTP inclusion.
; Please note: On Win32 OS the HTTP inclusion won't work!
http_incl = ".$http_incl_new."

; URL and absolute path to the configuration:
url_publ_config = \"".str_replace ("\"", "", $url_publ_rep_new)."config/\"
abs_publ_config = \"".str_replace ("\"", "", $abs_publ_rep_new)."config/\"

; URL, relative and absolute path to the component repository:
url_publ_comp = \"".str_replace ("\"", "", $url_publ_rep_new)."component/\"
abs_publ_comp = \"".str_replace ("\"", "", $abs_publ_rep_new)."component/\"
rel_publ_comp = \"".str_replace ($abs_publ_app_new, "/", $abs_publ_rep_new."component/")."\"

; URL and absolute path to the link index repository:
url_publ_link = \"".str_replace ("\"", "", $url_publ_rep_new)."link/\"
abs_publ_link = \"".str_replace ("\"", "", $abs_publ_rep_new)."link/\"
";
$site_publ_config_ini .= "; URL and absolute path to the content media repository:
url_publ_media = \"".str_replace ("\"", "", $url_publ_rep_new)."media_cnt/\"
abs_publ_media = \"".str_replace ("\"", "", $abs_publ_rep_new)."media_cnt/\"

; URL and absolute path to the template media repository:
url_publ_tplmedia = \"".str_replace ("\"", "", $url_publ_rep_new)."media_tpl/\"
abs_publ_tplmedia = \"".str_replace ("\"", "", $abs_publ_rep_new)."media_tpl/\"

; absolute path of the application on the file system of the application server:
abs_publ_app = \"".str_replace ("\"", "", $abs_publ_app_new)."\"

; OS on presentation server. this setting is necessary for the method of
; including components via http depending on OS.
; use 'WIN' for Windows OS (works also for UNIX derivates)
; use 'UNIX' for all UNIX derivates (for better performance on HTTP inclusion of components)
publ_os = \"".$publ_os_new."\"

; Allow access for the follwomg IP addresses.
allow_ip = \"".str_replace ("\"", "", $allow_ip_new)."\";";

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
      if (!empty ($eventsystem['onsavepublication_pre']) && empty ($eventsystem['hide']))
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
        if (!empty ($eventsystem['onsavepublication_post']) && empty ($eventsystem['hide']))
          onsavepublication_post ($site_name, $site_mgmt_config, $site_publ_config_ini, $site_publ_config_prop, $user);

        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-publication-configuration-was-saved-successfully'][$lang]."</span>\n";

        // success
        $result_ok = true;
      }
      else
      {
        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-publication-information-cannot-be-accessed'][$lang]."</span>\n".$hcms_lang['the-publication-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
      }
    }
    else
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-publication-information-cannot-be-accessed'][$lang]."</span>\n".$hcms_lang['the-publication-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
    }
  }
  else
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['required-input-is-missing'][$lang]."</span>\n";
  }

  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;

  return $result;
}

// ------------------------- editpublicationsetting -----------------------------
// function: editpublicationsetting()
// input: publication name [string], publication settings with setting name as key and setting parameter as value  (see publication config file for details) [array], user name [string] (optional)
// output: result array

// description:
// This function can be used to edit a single setting of a publication

function editpublicationsetting ($site_name, $setting, $user="sys")
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $result_ok = false;
  $add_onload = "";
  $show = "";

  // check permissions
  if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
  {
    if (!checkrootpermission ('site') || !checkrootpermission ('siteedit'))
    {
      $result = array();
      $result['result'] = false;
      $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
      return $result;
    }
  }

  // check if publication name is an attribute of the request string
  if (strpos ($site_name, ".php?") > 0)
  {
    // extract login
    $site_name = getattribute ($site_name, "site_name");
  }

  if (valid_publicationname ($site_name) && is_array ($setting) && valid_objectname ($user))
  {
    // load Management Config
    $site_mgmt_config = loadfile ($mgmt_config['abs_path_data']."config/", $site_name.".conf.php");

    // eventsystem
    if (!empty ($eventsystem['onsavepublication_pre']) && empty ($eventsystem['hide']))
      onsavepublication_pre ($site_name, $site_mgmt_config, "", "", $user);

    if ($site_mgmt_config != "")
    {
      $site_mgmt_records = explode (PHP_EOL, $site_mgmt_config);
      $site_mgmt_config = "";

      foreach ($site_mgmt_records as $record)
      {
        $found = false;

        foreach ($setting as $key=>$value)
        {
          if (substr_count ($record, "\$mgmt_config['".$site_name."']['".$key."']") == 1)
          {
            if ((is_bool ($value) && $value === true) || $value == "true") $site_mgmt_config .= "\$mgmt_config['".$site_name."']['".$key."'] = true;\n";
            elseif (((is_bool ($value) && $value === false) || $value == "false") && $value != "") $site_mgmt_config .= "\$mgmt_config['".$site_name."']['".$key."'] = false;\n";
            else $site_mgmt_config .= "\$mgmt_config['".$site_name."']['".$key."'] = \"".str_replace ("\"", "\\\"", $value)."\";\n";

            $found = true;
          }
        }

        if (empty ($found)) $site_mgmt_config .= $record."\n";
      }

      if ($site_mgmt_config != "")
      {
        // save Management Config
        $test = savefile ($mgmt_config['abs_path_data']."config/", $site_name.".conf.php", trim ($site_mgmt_config));

        if ($test == true)
        {
          // eventsystem
          if (!empty ($eventsystem['onsavepublication_post']) && empty ($eventsystem['hide']))
            onsavepublication_post ($site_name, $site_mgmt_config, "", "", $user);

          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-publication-configuration-was-saved-successfully'][$lang]."</span>\n";

          // reload publication management configuration after changes
          if (is_file ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php"))
          {
            // empty file cache
            opcache_invalidate ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php");

            // reset
            $mgmt_config[$site_name] = array();

            // reload
            include ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php");
          }

          // success
          $result_ok = true;
        }
        else
        {
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-publication-information-cannot-be-accessed'][$lang]."</span>\n".$hcms_lang['the-publication-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
        }
      }
      else
      {
        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-publication-information-cannot-be-accessed'][$lang]."</span>\n".$hcms_lang['the-publication-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
      }
    }
    else
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-publication-information-cannot-be-accessed'][$lang]."</span>\n".$hcms_lang['the-publication-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
    }
  }
  else
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['required-input-is-missing'][$lang]."</span>\n";
  }

  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;

  return $result; 
}

// ------------------------- deletepublication -----------------------------
// function: deletepublication()
// input: publication name [string], user name [string] (optional)
// output: result array

// description:
// This function deletes a publication with all its files

function deletepublication ($site_name, $user="sys")
{
  global $mgmt_config, $eventsystem, $hcms_lang, $lang;

  // initialize
  $error = array();
  $result_ok = false;
  $add_onload = "";
  $show = "";

  // check permissions
  if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
  {
    if (!checkrootpermission ('site') || !checkrootpermission ('sitedelete'))
    {
      $result = array();
      $result['result'] = false;
      $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
      return $result;
    }
  }

  // set default language
  if (empty ($lang)) $lang = "en";

   // check if publication name is an attribute of the request string
  if (strpos ($site_name, ".php?") > 0)
  {
    // extract login
    $site_name = getattribute ($site_name, "site_name");
  }

  if (valid_publicationname ($site_name) && valid_objectname ($user))
  { 
    $file_count = 0;

    // check if component folder is empty
    $comp_root = deconvertpath ("%comp%/".$site_name."/", "file");

    if (is_dir ($comp_root))
    {
      $scandir = scandir ($comp_root);

      if ($scandir)
      {
        foreach ($scandir as $file)
        {
          if ($file != "." && $file != ".." && $file != ".folder") $file_count++;
        }
      }
    }

    // check if page folder is empty
    $page_root = deconvertpath ("%page%/".$site_name."/", "file");

    if (is_dir ($page_root))
    {
      $scandir = scandir ($page_root);

      if ($scandir)
      {
        foreach ($scandir as $file)
        {
          if ($file != "." && $file != ".." && $file != ".folder") $file_count++;
        }
      }
    }

    if ($file_count > 0)
    {
      $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-publication-cannot-be-removed'][$lang]."</span><br />\n".$hcms_lang['the-publication-still-holds-folders-or-objects'][$lang]." (".$hcms_lang['empty-recycle-bin'][$lang].")\n";
    }
    else
    {
      // eventsystem
      if (!empty ($eventsystem['ondeletepublication_pre']) && empty ($eventsystem['hide'])) 
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

        // portal media
        deletefile ($mgmt_config['abs_path_rep']."portal/", $site_name, 1);

        // content media
        deletefile ($mgmt_config['abs_path_rep']."media_cnt/", $site_name, 1);

        // load user file
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
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|savelockfile failed for user.xml.php";

              // unlock file
              unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php"); 
            } 
          } 
          else 
          {
            $errcode = "10123";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|deletecontent failed for user.xml.php";
 
            // unlock file
            unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php"); 
          }
        }
        else 
        {
          $errcode = "10122";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|loadfile failed for user.xml.php";

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
        $scandir = scandir ($dir_temp);

        if ($scandir)
        {
          foreach ($scandir as $entry)
          {
            if (is_file ($dir_temp.$entry) && preg_match ("/^".$site_name."./", $entry))
            {
              deletefile ($dir_temp, $entry, 0);
            }
          }
        }

        // customer files
        deletefile ($mgmt_config['abs_path_data']."customer/", $site_name, 1);
 
        // config files
        deletefile ($mgmt_config['abs_path_data']."config/", $site_name.".conf.php", 0);
        deletefile ($mgmt_config['abs_path_rep']."config/", $site_name.".ini", 0);
        deletefile ($mgmt_config['abs_path_rep']."config/", $site_name.".properties", 0);

        // media to meta data mapping file
        if (is_file ($mgmt_config['abs_path_data']."config/".$site_name.".media.map.php"))
        {
          deletefile ($mgmt_config['abs_path_data']."config/", $site_name.".media.map.php", 0);
        }

        // hierarchy configuration file
        if (is_file ($mgmt_config['abs_path_data']."config/".$site_name.".hierarchy.dat"))
        {
          deletefile ($mgmt_config['abs_path_data']."config/", $site_name.".hierarchy.dat", 0);
        }

        // Google Cloud key file
        if (is_file ($mgmt_config['abs_path_data']."config/".$site_name.".google_cloud_key.json"))
        {
          deletefile ($mgmt_config['abs_path_data']."config/", $site_name.".google_cloud_key.json", 0);
        }

        // license notification file
        if (is_file ($mgmt_config['abs_path_data']."config/".$site_name.".comp.msg.dat"))
        {
          deletefile ($mgmt_config['abs_path_data']."config/", $site_name.".comp.msg.dat", 0);
        }

        if (is_file ($mgmt_config['abs_path_data']."config/".$site_name.".page.msg.dat"))
        {
          deletefile ($mgmt_config['abs_path_data']."config/", $site_name.".page.msg.dat", 0);
        }

        // taxonomy configuration file
        $dir_temp = $mgmt_config['abs_path_data']."include/";
        $scandir = scandir ($dir_temp);

        if ($scandir)
        {
          foreach ($scandir as $entry)
          {
            if (is_file ($dir_temp.$entry) && strpos ("_".$entry, $site_name.".") > 0 && (strpos ($entry, ".taxonomy.dat") > 0 || strpos ($entry, ".taxonomy.inc.php") > 0))
            {
              deletefile ($dir_temp, $entry, 0);
            }
          }
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
                    writesession ($user, $passwd, createchecksum (), $_SESSION['hcms_siteaccess']);
                  }
                }
              }
            }

            // eventsystem
            if (!empty ($eventsystem['oncreatepublication_post']) && empty ($eventsystem['hide'])) 
              ondeletepublication_post ($site_name, $user);

            $add_onload = "top.frames['navFrame'].location='explorer.php?refresh=1'; parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-publication-was-deleted-successfully'][$lang]."</span><br />\n".$hcms_lang['all-publication-entries-were-removed-successfully'][$lang]."<br>\n";

            // success
            $result_ok = true;
          }
        }
        else
        {
          $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-publication-cannot-be-removed'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
        }
      }
      else
      {
        $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-publication-information-cannot-be-accessed'][$lang]."</span><br />\n".$hcms_lang['the-publication-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
      }
    }
  }
  // check if sent data is available
  else
  {
    $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['required-input-is-missing'][$lang]."</span><br />\n".$hcms_lang['please-select-a-publication'][$lang]."\n";
  }

  // unlock file
  inherit_db_close ($user);

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
// input: publication name [string], personalization profile or tracking name [string], category [profile,tracking]
// output: result array
// requires: config.inc.php to be loaded before

// description:
// This function creates a new customer personalization tracking or profile

function createpersonalization ($site, $pers_name, $cat)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $result_ok = false;

  // set default language
  if (empty ($lang)) $lang = "en";

  if (valid_publicationname ($site) && valid_objectname ($pers_name) && strlen ($pers_name) <= 100 && ($cat == "tracking" || $cat == "profile"))
  {
    // check permissions
    if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
    {
      if (
        ($cat == "profile" && (!checkglobalpermission ($site, 'persprof') || !checkglobalpermission ($site, 'persprofcreate'))) || 
        ($cat == "tracking" && (!checkglobalpermission ($site, 'perstrack') || !checkglobalpermission ($site, 'perstrackcreate')))
      )
      {
        $result = array();
        $result['result'] = false;
        $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
        return $result;
      }
    }

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
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-exists-already'][$lang]."</span>
      ".$hcms_lang['please-try-another-template-name'][$lang]."\n";
    }
    else
    {
      // save template file
      $test = savefile ($mgmt_config['abs_path_data']."customer/".$site."/", $persfile, "");

      if ($test == false)
      {
        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-could-not-be-created'][$lang]."</span>
        ".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
      }
      else
      {
        $add_onload = "parent.frames['mainFrame'].location='pers_form.php?site=".url_encode($site)."&cat=".url_encode($cat)."&save=no&preview=no&persfile=".url_encode($persfile)."'; ";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-created-successfully'][$lang]."</span>\n";

        // success
        $result_ok = true;
      }
    }
  }
  else 
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['a-name-is-required'][$lang]."</span>";
  }

  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;

  return $result;
}

// ----------------------------------------- deletepersonalization ---------------------------------------------
// function: deletepersonalization()
// input: publication name [string], personalization profile or tracking name [string], category [profile,tracking]
// output: result array
// requires: config.inc.php to be loaded before

// description:
// This function deletes a customer personalization tracking or profile

function deletepersonalization ($site, $pers_name, $cat)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;
 
  // initialize
  $result_ok = false;

  // set default language
  if (empty ($lang)) $lang = "en";

  if (valid_publicationname ($site) && valid_objectname ($pers_name) && ($cat == "tracking" || $cat == "profile"))
  {
    // check permissions
    if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
    {
      if (
        ($cat == "profile" && (!checkglobalpermission ($site, 'persprof') || !checkglobalpermission ($site, 'persprofdelete'))) || 
        ($cat == "tracking" && (!checkglobalpermission ($site, 'perstrack') || !checkglobalpermission ($site, 'perstrackdelete')))
      )
      {
        $result = array();
        $result['result'] = false;
        $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
        return $result;
      }
    }

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
      $test = deletefile ($mgmt_config['abs_path_data']."customer/".$site."/", $persfile, false);

      if ($test == true)
      {
        $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-deleted'][$lang]."</span>\n";

        // success
        $result_ok = true;
      }
      else
      {
        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-could-not-be-removed'][$lang]."</span><br />".$hcms_lang['the-object-does-not-exist-or-you-do-not-have-permissions'][$lang]."\n";
      }
    }
    else
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-could-not-be-removed'][$lang]."</span><br />\n".$hcms_lang['the-object-does-not-exist-or-you-do-not-have-permissions'][$lang]."\n";
    }
  }
  else 
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['a-name-is-required'][$lang]."</span>";
  }

  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;

  return $result;
}

// ======================================= TEMPLATE FUNCTIONS ===========================================

// ----------------------------------------- loadtemplate ---------------------------------------------
// function: loadtemplate()
// input: publication name [string], template file name [string]
// output: array (template content [XML string], publication, result[true/false]) / false on error
// requires: config.inc.php to be loaded before

// description:
// This function loads a template by the provided name.
// Based on the inheritance settings of the publication the template will be loaded
// with highest priority from the own publication and if not available from a parent
// publication. If the parent publications have double entries the sort mechanism will
// define the priority. First priority have numbers, second are upper case letters and
// last priority have lower case letters.

function loadtemplate ($site, $template)
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $result = array();

  if (valid_publicationname ($site) && valid_objectname ($template))
  {
    // 1. template of the publication
    if (is_file ($mgmt_config['abs_path_template'].$site."/".$template))
    {
      $data = loadfile ($mgmt_config['abs_path_template'].$site."/", $template);

      $result['content'] = $data;
      $result['publication'] = $site;

      if ($data != false) $result['result'] = true;
      else $result['result'] = false; 

      return $result; 
    }
    // 2. template of another publicaton (inheritance)
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

    // 3. home box component template
    if (!empty ($mgmt_config['homeboxes_directory']) && $template == "System-HomeBox.comp.tpl")
    {
      // remove trailing slashes
      $mgmt_config['homeboxes_directory'] = trim ($mgmt_config['homeboxes_directory'], "/");

      if (valid_locationname ($mgmt_config['homeboxes_directory']) && is_dir ($mgmt_config['abs_path_comp'].$site."/".$mgmt_config['homeboxes_directory']) && is_file ($mgmt_config['abs_path_cms']."/xmlschema/template_homebox.schema.xml.php"))
      {
        $data = loadfile ($mgmt_config['abs_path_cms']."/xmlschema/", "template_homebox.schema.xml.php");

        $result['content'] = $data;
        $result['publication'] = $site;

        if ($data != false) $result['result'] = true;
        else $result['result'] = false; 

        return $result; 
      }
    }

    // 4. brand guideline templates
    if (!empty ($mgmt_config[$site]['dam']))
    {
      if ($template == "System-BrandGuidelines.comp.tpl" && is_file ($mgmt_config['abs_path_cms']."/xmlschema/template_brandguidelines.schema.xml.php"))
      {
        $data = loadfile ($mgmt_config['abs_path_cms']."/xmlschema/", "template_brandguidelines.schema.xml.php");
      }
      elseif ($template == "System-BrandGuidePage.comp.tpl" && is_file ($mgmt_config['abs_path_cms']."/xmlschema/template_brandguidepage.schema.xml.php"))
      {
        $data = loadfile ($mgmt_config['abs_path_cms']."/xmlschema/", "template_brandguidepage.schema.xml.php");
      }
      elseif ($template == "System-BrandColor.comp.tpl" && is_file ($mgmt_config['abs_path_cms']."/xmlschema/template_brandcolor.schema.xml.php"))
      {
        $data = loadfile ($mgmt_config['abs_path_cms']."/xmlschema/", "template_brandcolor.schema.xml.php");
      }
      elseif ($template == "System-BrandGuideDownload.comp.tpl" && is_file ($mgmt_config['abs_path_cms']."/xmlschema/template_brandguidedownload.schema.xml.php"))
      {
        $data = loadfile ($mgmt_config['abs_path_cms']."/xmlschema/", "template_brandguidedownload.schema.xml.php");
      }
      else $data = "";
        
      $result['content'] = $data;
      $result['publication'] = $site;

      if ($data != false) $result['result'] = true;
      else $result['result'] = false; 

      return $result; 
    }
  }

  return false; 
}

// ----------------------------------------- createtemplate ---------------------------------------------
// function: createtemplate()
// input: publication name [string], template name [string], category [page,comp,meta,inc]
// output: result array
// requires: config.inc.php to be loaded before

// description:
// This function creates a new template

function createtemplate ($site, $template, $cat)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $result_ok = false;
  $add_onload = "";
  $show = "";

  // set default language
  if (empty ($lang)) $lang = "en";

  if (valid_publicationname ($site) && valid_objectname ($template) && strlen ($template) <= 100 && in_array ($cat, array("page","comp","inc","meta")))
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

    // test if site name includes special characters
    if (specialchr ($tpl_name, "-_") == true)
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['special-characters-in-expressions-are-not-allowed'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-try-another-expression'][$lang]."\n";
    }
    // template file exists already
    elseif (is_file ($mgmt_config['abs_path_template'].$site."/".$template))
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-template-exists-already'][$lang]."</span><br />\n".$hcms_lang['please-try-another-template-name'][$lang]."\n";
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
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-template-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
      }
      else
      {
        $add_onload = "parent.frames['mainFrame'].location='frameset_template_edit.php?site=".url_encode($site)."&cat=".url_encode($cat)."&save=no&template=".url_encode($template)."'; ";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-template-was-created'][$lang]."</span><br />\n".$hcms_lang['now-you-can-edit-the-template'][$lang]."\n";

        // success
        $result_ok = true;
      }
    }
  }
  else 
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['a-template-name-is-required'][$lang]."</span>";
  }

  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;

  return $result; 
}

// ----------------------------------------- edittemplate ---------------------------------------------
// function: edittemplate()
// input: publication name [string], template file name [string], category [page,comp,meta,inc], user name [string], template content [string] (optional), template extension [string] (optional), template application [string] (optional)
// output: result array
// requires: config.inc.php to be loaded before

// description:
// This function edites a template

function edittemplate ($site, $template, $cat, $user, $content="", $extension="", $application="")
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $result_save = false;
  $add_onload = "";
  $show = "";

  // set default language
  if (empty ($lang)) $lang = "en";

  if (valid_publicationname ($site) && valid_objectname ($template) && in_array ($cat, array("page","comp","inc","meta")) && valid_objectname ($user))
  { 
    $contentfield_save = $content;

    // escape special characters
    if ($contentfield_save != "")
    {
      $contentfield_save = str_replace ("&lt;", "<", $contentfield_save);
      $contentfield_save = str_replace ("&gt;", ">", $contentfield_save);
      $contentfield_save = str_replace ("<![CDATA[", "&lt;![CDATA[", $contentfield_save); 
      $contentfield_save = str_replace ("]]>", "]]&gt;", $contentfield_save);
    }

    // escape hyperCMS template XML tags
    $contentfield_save = escape_xmltags ($contentfield_save, "template");

    // get charset before transformation of < and >
    $result_charset = getcharset ($site, $contentfield_save); 

    // add CDATA section 
    $contentfield_save = "<![CDATA[".$contentfield_save."]]>"; 

    // load template and insert values
    $result_load = loadtemplate ($site, $template);

    if (!empty ($result_load['result']))
    {
      $templatedata = $result_load['content'];
      $extension = str_replace (".", "", trim ($extension));
      $application = strtolower (trim ($application));
      $user = trim ($user);

      if (strpos ($templatedata, "</user>") > 0) $templatedata = setcontent ($templatedata, "", "<user>", $user, "", "");
      else  $templatedata = insertcontent ($templatedata, "<user>".$user."</user>\n", "<template>");

      if ($extension != "") $templatedata = setcontent ($templatedata, "", "<extension>", strtolower ($extension), "", "");

      if (in_array ($application, array("asp","aspx","htm","jsp","php","xml","generator","media"))) $templatedata = setcontent ($templatedata, "", "<application>", $application, "", "");

      $templatedata = setcontent ($templatedata, "", "<content>", $contentfield_save, "", "", false);
    }
    else $templatedata = false;

    // save new template file
    if ($templatedata != "" && is_file ($mgmt_config['abs_path_template'].$site."/".$template)) 
    {
      // create version of previous template file
      $template_v = fileversion ($template);

      if (!empty ($template_v)) rename ($mgmt_config['abs_path_template'].$site."/".$template, $mgmt_config['abs_path_template'].$site."/".$template_v);

      // save template
      $result_save = savefile ($mgmt_config['abs_path_template'].$site."/", $template, $templatedata);

      if ($result_save == false)
      {
        $show = "<p class=\"hcmsHeadline\">".$hcms_lang['template-could-not-be-saved'][$lang]."</p>\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
      }
    }
    else
    {
      $show = "<p class=\"hcmsHeadline\">".$hcms_lang['functional-error-occured'][$lang]."</p>\n".$hcms_lang['an-error-occurred-in-function-setcontent'][$lang]."\n"; 
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
// input: publication name [string], template file name [string], category [page,comp,meta,inc]
// output: result array
// requires: config.inc.php to be loaded before

// description:
// This function deletes a template

function deletetemplate ($site, $template, $cat)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $result_ok = false;

  // set default language
  if (empty ($lang)) $lang = "en";

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
      $scandir = scandir ($mgmt_config['abs_path_template'].$site."/");

      if ($scandir)
      {
        foreach ($scandir as $entry)
        {
          if ($entry == $template || substr_count ($entry, $template.".v_") == 1)
          {
            $test = deletefile ($mgmt_config['abs_path_template'].$site."/", $entry, false);
          }
        }
      }

      if ($test == true)
      {
        $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-template-was-removed'][$lang]."</span>\n";

        // success
        $result_ok = true;
      }
      else
      {
        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-template-could-not-be-removed'][$lang]."</span><br />\n".$hcms_lang['the-template-does-not-exist-or-you-do-not-have-write-permissions'][$lang]."\n";
      }
    }
    else
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-template-could-not-be-removed'][$lang]."</span><br />\n".$hcms_lang['the-template-does-not-exist-or-you-do-not-have-write-permissions'][$lang]."\n";
    }
  }
  else 
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['a-template-name-is-required'][$lang]."</span>";
  }

  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;

  return $result; 
}

// ----------------------------------------- createportal ---------------------------------------------
// function: createportal()
// input: publication name [string], portal template name [string]
// output: result array
// requires: config.inc.php to be loaded before

// description:
// This function creates a new portal template

function createportal ($site, $template)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $result_ok = false;
  $add_onload = "";
  $show = "";

  // set default language
  if (empty ($lang)) $lang = "en";

  if (valid_publicationname ($site) && valid_objectname ($template) && strlen ($template) <= 100)
  {
    // check if file is page template (.tpl), page component template (.comp.tpl) or template component (.inc.tpl),
    // define extension and template category name
    $ext = ".portal.tpl";

    // trim name
    $template = trim ($template);

    // create template file name
    $tpl_name = $template;
    $template = $template.$ext;

    // test if site name includes special characters
    if (specialchr ($tpl_name, "-_") == true)
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['special-characters-in-expressions-are-not-allowed'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-try-another-expression'][$lang]."\n";
    } 
    // if template file exists already
    elseif (is_file ($mgmt_config['abs_path_template'].$site."/".$template))
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-template-exists-already'][$lang]."</span><br />\n".$hcms_lang['please-try-another-template-name'][$lang]."\n";
    }
    else
    {
      // load template xml schema
      $tpl_data = loadfile ($mgmt_config['abs_path_cms']."xmlschema/", "portal.schema.xml.php");
      $tpl_data = setcontent ($tpl_data, "", "<name>", $tpl_name, "", "");

      // save template file
      $test1 = savefile ($mgmt_config['abs_path_template'].$site."/", $template, $tpl_data);

      // create portal directory
      if (!is_dir ($mgmt_config['abs_path_rep']."portal/".$site."/")) mkdir ($mgmt_config['abs_path_rep']."portal/".$site."/", $mgmt_config['fspermission']);
      $test2 = mkdir ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name, $mgmt_config['fspermission']);
      $test3 = mkdir ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads", $mgmt_config['fspermission']);

      if ($test1 == false || $test2 == false || $test3 == false)
      {
        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-template-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
      }
      else
      {
        $add_onload = "parent.frames['mainFrame'].location='portal_edit.php?site=".url_encode($site)."&save=no&template=".url_encode($template)."'; ";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-template-was-created'][$lang]."</span><br />\n".$hcms_lang['now-you-can-edit-the-template'][$lang]."\n";

        // success
        $result_ok = true;
      }
    }
  }
  else 
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['a-template-name-is-required'][$lang]."</span>";
  }

  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;

  return $result; 
}

// ----------------------------------------- editportal ---------------------------------------------
// function: editportal()
// input: publication name [string], template name or file name [string], portal user name [string], design theme name [string] (optional), primary color as hex code [string] (optional),
//        PHP global FILES variable for file upload/remove [array] (optional), navigation tree names [array] (optional), download formats [JSON-string] (optional), user name [string] (optional)
// output: result array
// requires: config.inc.php to be loaded before

// description:
// This function edites the settings of a portal.

function editportal ($site, $template, $portaluser, $design="day", $primarycolor="", $global_files=array(), $navigation=array(), $formats="", $user="sys")
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;
 
  // initialize
  $result_save = false;
  $add_onload = "";
  $show = "";

  // set default language
  if (empty ($lang)) $lang = "en";

  // create template file name
  $tpl_name = str_replace (".portal.tpl", "", $template);
  $template = $tpl_name.".portal.tpl";

  if (valid_publicationname ($site) && valid_objectname ($template) && valid_objectname ($user) && $design != "" && is_dir ($mgmt_config['abs_path_cms']."theme/".$design))
  {
    // copy design theme
    copyrecursive ($mgmt_config['abs_path_cms']."theme/".$design."/", $mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/");

    // delete media file
    if (!empty ($global_files['logo_top']['delete']))
    {
      if (is_file ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/logo_top.png")) unlink ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/logo_top.png");
    }
    // upload media file
    if (!empty ($global_files['logo_top']['tmp_name']))
    {
      // error if file isn't of certain type
      if (substr_count ("image/png", $global_files["logo_top"]["type"]) < 1)
      {
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-file-you-are-trying-to-upload-is-wrong-type'][$lang]." (".$global_files["logo_top"]["type"].")</span>\n";
      }
      // upload file
      else
      {
        @move_uploaded_file ($global_files['logo_top']['tmp_name'], $mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/logo_top.png")
        or $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-file-you-are-trying-to-upload-couldnt-be-copied-to-the-server'][$lang]."</span>\n";
      }
    }

    // delete media file
    if (!empty ($global_files['logo']['delete']))
    {
      if (is_file ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/logo.png")) unlink ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/logo.png");
    }
    // upload media file
    if (!empty ($global_files['logo']['tmp_name']))
    {
      // error if file isn't of certain type
      if (substr_count ("image/png", $global_files["logo"]["type"]) < 1)
      {
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-file-you-are-trying-to-upload-is-wrong-type'][$lang]." (".$global_files["logo"]["type"].")</span>\n";
      }
      // upload file
      else
      {
        @move_uploaded_file ($global_files['logo']['tmp_name'], $mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/logo.png")
        or $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-file-you-are-trying-to-upload-couldnt-be-copied-to-the-server'][$lang]."</span>\n";
      }
    }

    // delete media file
    if (!empty ($global_files['wallpaper']['delete']))
    {
      if (is_file ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/wallpaper.png")) unlink ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/wallpaper.png");
      if (is_file ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/wallpaper.jpg")) unlink ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/wallpaper.jpg");
      if (is_file ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/img/wallpaper.png")) unlink ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/img/wallpaper.png");
      if (is_file ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/img/wallpaper.jpg")) unlink ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/img/wallpaper.jpg");
    }
    // upload media file
    if (!empty ($global_files['wallpaper']['tmp_name']))
    {
      // error if file isn't of certain type
      if (substr_count ("image/png;image/jpeg", $global_files["wallpaper"]["type"]) < 1)
      {
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-file-you-are-trying-to-upload-is-wrong-type'][$lang]." (".$global_files["wallpaper"]["type"].")</span>\n";
      }
      else
      {
        // remove old files
        if (is_file ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/wallpaper.png")) unlink ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/wallpaper.png");
        if (is_file ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/wallpaper.jpg")) unlink ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/wallpaper.jpg");
        if (is_file ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/img/wallpaper.png")) unlink ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/img/wallpaper.png");
        if (is_file ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/img/wallpaper.jpg")) unlink ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/img/wallpaper.jpg");

        // upload PNG file
        if (substr_count ("image/png", $global_files["wallpaper"]["type"]) > 0)
        {
          @move_uploaded_file ($global_files['wallpaper']['tmp_name'], $mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/wallpaper.png")
          or $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-file-you-are-trying-to-upload-couldnt-be-copied-to-the-server'][$lang]."</span>\n";
        }
        // upload JPG file
        elseif (substr_count ("image/jpeg", $global_files["wallpaper"]["type"]) > 0)
        {
          @move_uploaded_file ($global_files['wallpaper']['tmp_name'], $mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/wallpaper.jpg")
          or $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-file-you-are-trying-to-upload-couldnt-be-copied-to-the-server'][$lang]."</span>\n";
        }
      }
    }

    // set primary color in CSS
    if ($primarycolor != "")
    {
      if ($design == "day") $search = "#aaaaaa";
      elseif ($design == "night") $search = "#636363";

      $css_main = loadfile ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/css/", "main.css");

      if (!empty ($css_main) && !empty ($search))
      {
        $css_main = str_ireplace ($search, "#".$primarycolor, $css_main);
        if (!empty ($css_main)) savefile ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/css/", "main.css", $css_main);
      }
    }

    // copy uploaded media files
    if (is_file ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/logo_top.png"))
    {
      copy ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/logo_top.png", $mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/img/logo_top.png");
      copy ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/logo_top.png", $mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/img/mobile_icon57.png");
      copy ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/logo_top.png", $mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/img/mobile_icon114.png");
    }

    if (is_file ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/logo.png"))
    {
      copy ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/logo.png", $mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/img/logo.png");
      copy ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/logo.png", $mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/img/logo_server.png");
    }

    if (is_file ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/wallpaper.jpg"))
    {
      copy ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/wallpaper.jpg", $mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/img/wallpaper.jpg");
    }

    if (is_file ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/wallpaper.png"))
    {
      copy ($mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/uploads/wallpaper.png", $mgmt_config['abs_path_rep']."portal/".$site."/".$tpl_name."/img/wallpaper.png");
    }

    // load template and insert values
    $result_load = loadtemplate ($site, $template);

    if (!empty ($result_load['result']))
    {
      $templatedata = $result_load['content'];

      $templatedata = setcontent ($templatedata, "", "<name>", $tpl_name, "", "");

      if ($user != "") $templatedata = setcontent ($templatedata, "", "<user>", $user, "", "");

      $templatedata = setcontent ($templatedata, "", "<portaluser>", $portaluser, "", "");

      if ($design != "") $templatedata = setcontent ($templatedata, "", "<designtheme>", $site."/".$design, "", "");

      if ($primarycolor != "") $templatedata = setcontent ($templatedata, "", "<primarycolor>", $primarycolor, "", "");

      if ($formats != "") $templatedata = setcontent ($templatedata, "", "<downloadformats>", $formats, "", "");

      // add navigation node (added in version 8.1.3)
      if (strpos ($templatedata, "<navigation>") < 1)
      {
        $templatedata = str_replace ("</downloadformats>", "</downloadformats>\n<navigation></navigation>", $templatedata);
      }

      if (is_array ($navigation)) $templatedata = setcontent ($templatedata, "", "<navigation>", implode ("|", $navigation), "", "");
    }
    else $templatedata = false;

    // save new template file
    if ($templatedata != "" && is_file ($mgmt_config['abs_path_template'].$site."/".$template)) 
    {
      // save template
      $result_save = savefile ($mgmt_config['abs_path_template'].$site."/", $template, $templatedata);

      if ($result_save == false)
      {
        $show = "<p class=\"hcmsHeadline\">".$hcms_lang['template-could-not-be-saved'][$lang]."</p>\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
      }
    }
    else
    {
      $show = "<p class=\"hcmsHeadline\">".$hcms_lang['functional-error-occured'][$lang]."</p>\n".$hcms_lang['an-error-occurred-in-function-setcontent'][$lang]."\n"; 
    }
  }

  $result = array();
  $result['result'] = $result_save;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;

  return $result;
}

// ----------------------------------------- deleteportal ---------------------------------------------
// function: deleteportal()
// input: publication name [string], template file name [string]
// output: result array
// requires: config.inc.php to be loaded before

// description:
// This function deletes a portal

function deleteportal ($site, $template)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;
 
  // initialize
  $result_ok = false;

  // set default language
  if (empty ($lang)) $lang = "en";

  // extract template file name
  if (strpos ($template, ".php?") > 0) $template = getattribute ($template, "template");

  // create template file name
  $tpl_name = str_replace (".portal.tpl", "", $template);
  $template = $tpl_name.".portal.tpl";

  if (valid_publicationname ($site) && valid_objectname ($template))
  {
    if (is_file ($mgmt_config['abs_path_template'].$site."/".$template))
    {
      $test1 = deletefile ($mgmt_config['abs_path_template'].$site."/", $template, false);
      $test2 = deletefile ($mgmt_config['abs_path_rep']."portal/".$site."/", $tpl_name, true);

      // error
      if ($test1 == false || $test2 == false)
      {
        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-template-could-not-be-removed'][$lang]."</span><br />\n".$hcms_lang['the-template-does-not-exist-or-you-do-not-have-write-permissions'][$lang]."\n";
      }
      // success
      else
      {
        $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-template-was-removed'][$lang]."</span>\n";

        $result_ok = true;
      }
    }
    else
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-template-could-not-be-removed'][$lang]."</span><br />\n".$hcms_lang['the-template-does-not-exist-or-you-do-not-have-write-permissions'][$lang]."\n";
    }
  }
  else 
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['a-template-name-is-required'][$lang]."</span>";
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
// input: publication name [string] (optional), user login name [string], password [string], confirmed password [string], no logon allowed [0,1] (optional), user name [string] (optional)
// output: result array

// description:
// This function creates a new user. Use *Null* for publication name to remove access to all publications.

function createuser ($site, $login, $password, $confirm_password, $nologon=0, $user="sys")
{
  global $eventsystem, $mgmt_config, $mgmt_lang_shortcut_default, $hcms_lang, $lang;
 
  // initialize
  $error = array();
  $success = false;
  $add_onload = "";
  $show = "";

  // set default language
  if (empty ($lang)) $lang = "en";

  // set default
  if (!isset ($mgmt_config['passwordminlength'])) $mgmt_config['passwordminlength'] = 10;

  // check permissions
  if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
  {
    if (
      (!valid_publicationname ($site) && (!checkrootpermission ('user') || !checkrootpermission ('usercreate'))) || 
      (valid_publicationname ($site) && (!checkglobalpermission ($site, 'user') || !checkglobalpermission ($site, 'usercreate')))
    )
    {
      $result = array();
      $result['result'] = false;
      $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
      return $result;
    }
  }

  // default theme
  if ($mgmt_config['theme'] != "") $theme = $mgmt_config['theme'];
  elseif (valid_publicationname ($site) && !empty ($mgmt_config[$site]['theme'])) $theme = $mgmt_config[$site]['theme'];
  else $theme = "standard";

  // check if sent data is available
  if (!valid_objectname ($login) || strlen ($login) < 4 || strlen ($login) > 100 || $password == "" || strlen ($password) > 100 || $confirm_password == "")
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-input-is-not-valid'][$lang]."</span><br/>\n(User name: 4 to 100 digits, Password: 10 to 100 digits)<br />\n".$hcms_lang['please-go-back-and-try-it-again-'][$lang]."\n";
  }
  // check if user is not admin or sys
  elseif (strtolower ($login) == "admin" || strtolower ($login) == "sys")
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-user-exists-already'][$lang]." '".$login."'</span><br />\n".$hcms_lang['please-try-another-user-name'][$lang]."\n";
  }
  // test if login name contains special characters except ".-_@"
  elseif (specialchr ($login, ".-_@") == true || strpos ("_".$login, ".@") > 0)
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['special-characters-in-expressions-are-not-allowed'][$lang]."</span><br />\n".$hcms_lang['please-try-another-user-name'][$lang]."\n";
  }
  // check for strong password (if enabled)
  elseif (!empty ($mgmt_config['strongpassword']) && checkpassword ($password) !== true)
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['password-insufficient'][$lang]."</span><br />\n".checkpassword ($password)."\n";
  }
  // check if submitted passwords has at least 10 digits
  elseif (strlen ($password) < intval ($mgmt_config['passwordminlength']) || strlen ($confirm_password) < intval ($mgmt_config['passwordminlength']))
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['your-submitted-passwords-has-less-than-digits'][$lang]."</span><br />\n".$hcms_lang['please-select-a-password-with-at-least-digits'][$lang]."\n";
  }
  // check if submitted passwords are equal
  elseif ($password != $confirm_password)
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['your-submitted-passwords-are-not-equal'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-try-it-again-'][$lang]."\n";
  }
  else
  { 
    // eventsystem
    if (!empty ($eventsystem['oncreateuser_pre']) && empty ($eventsystem['hide'])) 
      oncreateuser_pre ($login, $user);

    $login = trim ($login);

    // load user xml file
    $userdata = loadlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php", 5);

    if ($userdata != false)
    {
      // check if user already exists
      $testlogin = selectcontent ($userdata, "<user>", "<login>", $login);

      // if user exists or has the session_id() name used for system locking of files
      if (!empty ($testlogin[0]) || $login == session_id())
      {
        // unlock file
        unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");

        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-user-exists-already'][$lang]." '".$login."'</span><br />\n".$hcms_lang['please-try-another-user-name'][$lang]."\n";
      }

      if ($show == "")
      {
        // load user xml schema
        $user_schema_xml = loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "user.schema.xml.php");

        if (!empty ($user_schema_xml))
        {
          // generate hashcode
          $hashcode = md5 ($login.":hyperdav:".$password);

          // crypt password
          // depracted since version 7.0.6: $password = crypt ($password, substr ($password, 1, 2));
          $password = password_hash ($password, PASSWORD_BCRYPT);

          // save password in history log
          if (!empty ($mgmt_config['passwordhistory']) || !empty ($mgmt_config['passwordexpires']))
          {
            savelog (array($mgmt_config['today']."|".$password), $login.".password");
          }

          // insert values into xml schema
          $newuser = setcontent ($user_schema_xml, "<user>", "<login>", $login, "", "");
          $newuser = setcontent ($newuser, "<user>", "<password>", $password, "", "");
          $newuser = setcontent ($newuser, "<user>", "<hashcode>", $hashcode, "", "");
          $newuser = setcontent ($newuser, "<user>", "<userdate>", date ("Y-m-d", time()), "", "");
          $newuser = setcontent ($newuser, "<user>", "<language>", $mgmt_lang_shortcut_default, "", "");
          $newuser = setcontent ($newuser, "<user>", "<theme>", $theme, "", "");


          // in order to prevent the logon users that have been created for access links
          if (empty ($nologon)) $nologon = "0";
          else $nologon = "1";

          $newuser = setcontent ($newuser, "<user>", "<nologon>", $nologon, "", "");

          // member of publication
          if (isset ($site) && valid_publicationname ($site)) 
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
            if (!empty ($eventsystem['oncreateuser_post']) && empty ($eventsystem['hide'])) 
              oncreateuser_post ($login, $user);

            // log
            $errcode = "00010";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|New user '".$login."' has been created by user '".$user."' (".getuserip().")";

            if (!empty ($mgmt_config['user_newwindow'])) $add_onload = "window.open('user_edit.php?site=".url_encode($site)."&login=".url_encode($login)."','','location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes,width=520,height=680'); parent.frames['mainFrame'].location.reload(); ";
            else $add_onload = "parent.openPopup('user_edit.php?site=".url_encode($site)."&login=".url_encode($login)."'); ";

            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-new-user-was-created'][$lang]."</span><br />\n".$hcms_lang['now-you-can-edit-the-user'][$lang]."<br />\n";
            $success = true;
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


          // log
          $errcode = "20010";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|New user '".$login."' could not be inserted in user directory by '".$user."' (".getuserip().")";
 
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

  // save log
  savelog (@$error);

  // return results
  $result = array();
  $result['result'] = $success;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;

  return $result;
}

// ------------------------------------------- edituser --------------------------------------------
// function: edituser()
// input: publication name [string], user login name [string], new login name [string] (optional), password [string] (optional), confirmed password [string] (optional), super administrator [0,1,*Leave*] (optional), no logon allowed [0,1,*Leave*] (optional), real name [string] (optional), language setting [en,de,...] (optional), time zone [string] (optional), 
//        theme name (optional), email [string] (optional), phone [string] (optional), signature [string] (optional), member of usergroup string [group1|group2] or usergroup array [array] (optional), member of publications string [site1|site2] or publications array [array] (optional), 
//        valid date from [date] (optional), valid date to [date] (optional), user name [string] (optional)
// output: result array

// description:
// This function edits a user. Use *Leave* as input if a value should not be changed. 
// Use *Null* for the publication membership to remove access to all publications.
// Use *Null* for the user group membership to remove user from all user groups of the publication.

function edituser ($site="*Null*", $login="", $old_password="", $password="", $confirm_password="", $superadmin="*Leave*", $nologon="*Leave*", $realname="*Leave*", $language="en", $timezone="*Leave*", $theme="*Leave*", $email="*Leave*", $phone="*Leave*", $signature="*Leave*", $usergroup="*Leave*", $usersite="*Leave*", $validdatefrom="*Leave*", $validdateto="*Leave*", $user="sys")
{
  global $eventsystem, $login_cat, $group, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $error = array();
  $success = false;
  $update = false;
  $add_onload = "";
  $show = "";
  $password_saved = array();
  $hashcode_saved = array();
  $superadmin_saved = array();
  $nologon_saved = array();
  $language_saved = array();
  $realname_saved = array();
  $timezone_saved = array();
  $theme_saved = array();
  $email_saved = array();
  $phone_saved = array();
  $signature_saved = array();
  $validdatefrom_saved = array();
  $validdateto_saved = array();

  // set default language
  if (empty ($lang)) $lang = "en";

  // set default
  if (!isset ($mgmt_config['passwordminlength'])) $mgmt_config['passwordminlength'] = 10;

  // check permissions
  if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
  {
    if (
      ($login != $user || !checkrootpermission ('desktopsetting')) &&  
      (!valid_publicationname ($site) && (!checkrootpermission ('user') || !checkrootpermission ('useredit'))) && 
      (valid_publicationname ($site) && (!checkglobalpermission ($site, 'user') || !checkglobalpermission ($site, 'useredit')))
    )
    {
      $result = array();
      $result['result'] = false;
      $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
      return $result;
    }
  }

  if (valid_objectname ($login) && valid_objectname ($user))
  { 
    // load user xml file
    $userdata = loadlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php", 5);

    // default theme
    if ($theme == "")
    {
      if (!empty ($mgmt_config['theme'])) $theme = $mgmt_config['theme'];
      elseif (valid_publicationname ($site) && !empty ($mgmt_config[$site]['theme'])) $theme = $mgmt_config[$site]['theme'];
      else $theme = "standard";
    }

    // get saved user data for comparison
    if (!empty ($userdata))
    {
      // get user node
      $temp = selectcontent ($userdata, "<user>", "<login>", $login);

      if (!empty ($temp[0]))
      {
        // get user information
        $password_saved = getcontent ($temp[0], "<password>");
        $hashcode_saved = getcontent ($temp[0], "<hashcode>");
        $superadmin_saved = getcontent ($temp[0], "<admin>");
        $nologon_saved = getcontent ($temp[0], "<nologon>");
        $language_saved = getcontent ($temp[0], "<language>");
        $realname_saved = getcontent ($temp[0], "<realname>", true);
        $timezone_saved = getcontent ($temp[0], "<timezone>");
        $theme_saved = getcontent ($temp[0], "<theme>");
        $email_saved = getcontent ($temp[0], "<email>");
        $phone_saved = getcontent ($temp[0], "<phone>");
        $signature_saved = getcontent ($temp[0], "<signature>", true);
        $validdatefrom_saved = getcontent ($temp[0], "<validdatefrom>");
        $validdateto_saved = getcontent ($temp[0], "<validdateto>");
      }

      // set values if missing in container
      if (empty ($password_saved[0])) $password_saved[0] = "";
      if (empty ($hashcode_saved[0])) $hashcode_saved[0] = "";
      if (empty ($superadmin_saved[0])) $superadmin_saved[0] = "";
      if (empty ($nologon_saved[0])) $nologon_saved[0] = "";
      if (empty ($language_saved[0])) $language_saved[0] = "";
      if (empty ($realname_saved[0])) $realname_saved[0] = "";
      if (empty ($timezone_saved[0])) $timezone_saved[0] = "";
      if (empty ($theme_saved[0])) $theme_saved[0] = "";
      if (empty ($email_saved[0])) $email_saved[0] = "";
      if (empty ($phone_saved[0])) $phone_saved[0] = "";
      if (empty ($signature_saved[0])) $signature_saved[0] = "";
      if (empty ($validdatefrom_saved[0])) $validdatefrom_saved[0] = "";
      if (empty ($validdateto_saved[0])) $validdateto_saved[0] = "";
    }

    if (!empty ($userdata))
    {
      // ---------------- password ----------------
      if ($password != "" && $password != "*Leave*")
      {
        // check if submitted old password is valid if user changes his own password (only for logged in user)
        if ($login == $user && !empty ($password_saved[0]) && !password_verify ($old_password, $password_saved[0]) && crypt ($old_password, substr ($old_password, 1, 2)) != $password_saved[0])
        {
          //unlock file
          unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");

          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-old-password-is-not-valid'][$lang]."</span>\n";
        }
        // check for strong password (if enabled)
        elseif (!empty ($mgmt_config['strongpassword']) && checkpassword ($password) !== true)
        {
          //unlock file
          unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");

          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['password-insufficient'][$lang]."</span><br />\n".checkpassword ($password, $login)."\n";
        }
        // check if submitted passwords has at least X digits
        elseif (strlen ($password) < intval ($mgmt_config['passwordminlength']))
        {
          //unlock file
          unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");

          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-password-has-less-than-digits'][$lang]."</span><br />".$hcms_lang['please-select-a-password-with-at-least-digits'][$lang]."\n";
        }
        // check if submitted passwords are equal
        elseif ($password != $confirm_password)
        {
          //unlock file
          unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");

          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['your-submitted-passwords-are-not-equal'][$lang]."</span><br />".$hcms_lang['please-try-it-again'][$lang]."\n";
        }
        // password is correct
        else
        {
          // generate hashcode
          $hashcode = md5 ($login.":hyperdav:".$password);

          // crypt password
          $password = password_hash ($password, PASSWORD_BCRYPT);

          // save password in history log
          if (!empty ($mgmt_config['passwordhistory']) || !empty ($mgmt_config['passwordexpires']))
          {
            savelog (array($mgmt_config['today']."|".$password), $login.".password");
          }

          // delete temp file for the password reset after login
          deletefile ($mgmt_config['abs_path_temp'], $login.".resetpassword.dat");

          if ($password_saved[0] != $password)
          {
            // insert values into xml schema
            $userdata = setcontent ($userdata, "<user>", "<password>", $password, "<login>", $login);
            $update = true;
          }

          if ($hashcode_saved[0] != $hashcode)
          {
            // insert values into xml schema
            $userdata = setcontent ($userdata, "<user>", "<hashcode>", $hashcode, "<login>", $login);
            $update = true;
          }
        }
      }

      // ---------------- super admin ----------------
      if ($show == "" && !empty ($userdata) && (intval ($superadmin) == 1 || intval ($superadmin) == 0) && $superadmin != "*Leave*" && intval ($superadmin) != intval ($superadmin_saved[0]))
      {
        // insert values into xml schema
        $userdata = setcontent ($userdata, "<user>", "<admin>", intval ($superadmin), "<login>", $login);
        $update = true;
      }

      // ---------------- no logon ----------------
      if ($show == "" && !empty ($userdata) && (intval ($nologon) == 1 || intval ($nologon) == 0) && $nologon != "*Leave*" && intval ($nologon) != intval ($nologon_saved[0]))
      {
        // insert values into xml schema
        $userdata = setcontent ($userdata, "<user>", "<nologon>", intval ($nologon), "<login>", $login);
        $update = true;
      } 

      // ---------------- realname ----------------
      if ($show == "" && !empty ($userdata) && isset ($realname) && $realname != "*Leave*")
      {
        // escape special characters
        $realname = strip_tags ($realname);
        $realname = html_encode ($realname);

        if ($realname != $realname_saved[0])
        {
          // insert values into xml schema
          $userdata = setcontent ($userdata, "<user>", "<realname>", "<![CDATA[".$realname."]]>", "<login>", $login);
          $update = true;
        }
      }

      // ---------------- language ----------------
      if ($show == "" && !empty ($userdata) && valid_objectname ($language) && $language != "*Leave*")
      {
        // escape special characters
        $language = strip_tags ($language);
        $language = html_encode ($language);

        if ($language != $language_saved[0])
        {
          // insert values into xml schema
          $userdata = setcontent ($userdata, "<user>", "<language>", $language, "<login>", $login);
          $update = true;
        }
      }

      // ---------------- timezone ----------------
      if ($show == "" && !empty ($userdata) && isset ($timezone) && $timezone != "*Leave*")
      {
        // escape special characters
        $timezone = strip_tags ($timezone);

        if ($timezone != $timezone_saved[0])
        {
          // insert values into xml schema
          $userdata = setcontent ($userdata, "<user>", "<timezone>", "<![CDATA[".$timezone."]]>", "<login>", $login, true);
          $update = true;
        }
      }

      // ---------------- design theme ----------------
      if ($show == "" && !empty ($userdata) && valid_objectname ($theme) && $theme != "*Leave*")
      {
        // escape special characters
        $theme = strip_tags ($theme);
        $theme = html_encode ($theme);

        if ($theme != $theme_saved[0])
        {
          // insert values into xml schema
          $userdata = setcontent ($userdata, "<user>", "<theme>", $theme, "<login>", $login);
          $update = true;
        }
      }

      // ---------------- email ----------------
      if ($show == "" && !empty ($userdata) && isset ($email) && $email != "*Leave*")
      {
        // escape special characters
        $email = strip_tags ($email);

        if ($email != $email_saved[0])
        {
          // insert values into xml schema
          $userdata = setcontent ($userdata, "<user>", "<email>", "<![CDATA[".$email."]]>", "<login>", $login, true);
          $update = true;
        }
      }

      // ---------------- phone ----------------
      if ($show == "" && !empty ($userdata) && isset ($phone) && $phone != "*Leave*")
      {
        // escape special characters
        $phone = strip_tags ($phone);

        if ($phone != $phone_saved[0])
        {
          // insert values into xml schema
          $userdata = setcontent ($userdata, "<user>", "<phone>", "<![CDATA[".$phone."]]>", "<login>", $login, true);
          $update = true;
        }
      }

      // ---------------- signature ----------------
      if ($show == "" && !empty ($userdata) && isset ($signature) && $signature != "*Leave*")
      {
        // escape special characters
        $signature = strip_tags ($signature);

        if ($signature != $signature_saved[0])
        {
          // insert values into xml schema
          $userdata = setcontent ($userdata, "<user>", "<signature>", "<![CDATA[".$signature."]]>", "<login>", $login, true);
          $update = true;
        }
      }

      // ---------------- valid date start ----------------
      if ($show == "" && !empty ($userdata) && isset ($validdatefrom) && $validdatefrom != "*Leave*")
      {
        // escape special characters
        $validdatefrom = strip_tags ($validdatefrom);

        if ($validdatefrom != $validdatefrom_saved[0])
        {
          // insert values into xml schema
          $userdata = setcontent ($userdata, "<user>", "<validdatefrom>", $validdatefrom, "<login>", $login, true);
          $update = true;
        }
      }

      // ---------------- valid date end ----------------
      if ($show == "" && !empty ($userdata) && isset ($validdateto) && $validdateto != "*Leave*")
      {
        // escape special characters
        $validdateto = strip_tags ($validdateto);

        if ($validdateto != $validdateto_saved[0])
        {
          // insert values into xml schema
          $userdata = setcontent ($userdata, "<user>", "<validdateto>", $validdateto, "<login>", $login, true);
          $update = true;
        }
      }

      // ---------------- user publication access ----------------
      if ($show == "" && !empty ($userdata) && !empty ($usersite) && (is_array ($usersite) || $usersite != "*Leave*"))
      {
        if ($usersite == "*Null*") 
        {
          $new_usersite = array();
        }
        elseif (is_string ($usersite))
        { 
          $usersite = trim ($usersite, "|");
          $new_usersite = explode ("|", $usersite);
        }
        elseif (is_array ($usersite))
        {
          $new_usersite = $usersite;
        }

        // remove duplicates
        $new_usersite = array_unique ($new_usersite);

        // get user node
        $user_array = selectcontent ($userdata, "<user>", "<login>", $login);

        $user_node = $user_node_new = $user_array[0];

        $set_memberof_array = getxmlcontent ($user_node, "<memberof>");

        // load memberof schema
        $memberof_schema_xml = loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "memberof.schema.xml.php");

        // remove publication access
        if ($set_memberof_array != false)
        {
          foreach ($set_memberof_array as $set_memberof_node)
          {
            $set_usersite = getcontent ($set_memberof_node, "<publication>");

            if (!empty ($set_usersite[0]) && !in_array ($set_usersite[0], $new_usersite)) 
            { 
              // delete current memberof node
              $user_node_new = deletecontent ($user_node_new, "<memberof>", "<publication>", $set_usersite[0]);
            }
          }
        }

        // assign publication access
        $memberof_node = "";

        foreach ($new_usersite as $temp)
        { 
          if ($temp != "" && substr_count ($user_node, "<publication>".$temp."</publication>") == 0)
          { 
            $memberof_node = $memberof_node.setcontent ($memberof_schema_xml, "<memberof>", "<publication>", $temp, "", "")."\n";
          } 
        }

        // insert new site access of user
        if ($memberof_node != "")
        {
          $user_node_new = insertcontent ($user_node_new, $memberof_node, "<user>");
        }

        // update user node
        if (!empty ($user_node_new) && trim ($user_node) != trim ($user_node_new)) 
        {
          $userdata = updatecontent ($userdata, $user_node, $user_node_new);
          $update = true;
        }
      }

      // ---------------- usergroup membership ----------------
      if ($show == "" && !empty ($userdata) && isset ($usergroup) && (valid_objectname ($usergroup) || is_array ($usergroup)) && $usergroup != "*Leave*")
      {
        if ($usergroup == "*Null*")
        {
          $usergroup = "";
        }
        elseif (is_string ($usergroup))
        {
          $usergroup = trim ($usergroup, "|");
          $usergroup = explode ("|", $usergroup);
          $usergroup = array_unique ($usergroup);

          if (is_array ($usergroup) && sizeof ($usergroup) > 0) $usergroup = "|".implode ("|", $usergroup)."|";
          else $usergroup = "";
        }
        elseif (is_array ($usergroup))
        {
          $usergroup = array_unique ($usergroup);

          if (is_array ($usergroup) && sizeof ($usergroup) > 0) $usergroup = "|".implode ("|", $usergroup)."|";
          else $usergroup = "";
        }

        // get user node
        $user_node = selectcontent ($userdata, "<user>", "<login>", $login);

        // insert values into user node
        if (!empty ($user_node[0]))
        {
          $user_node = $user_node_new = $user_node[0];

          // for all publications provided
          if (!empty ($new_usersite) && is_array ($new_usersite) && sizeof ($new_usersite) > 0)
          {
            foreach ($new_usersite as $temp)
            {
              if (valid_publicationname ($temp)) $user_node_new = setcontent ($user_node_new, "<memberof>", "<usergroup>", $usergroup, "<publication>", $temp);
            }

            if (!empty ($user_node_new) && trim ($user_node) != trim ($user_node_new))
            {
              $userdata = setcontent ($userdata, "<user>", "<user>", $user_node_new, "<login>", $login);
              $update = true;
            }
          }
          // for one publication
          elseif (valid_publicationname ($site))
          {
            $user_node_new = setcontent ($user_node_new, "<memberof>", "<usergroup>", $usergroup, "<publication>", $site);

            if (!empty ($user_node_new) && trim ($user_node) != trim ($user_node_new))
            {
              $userdata = setcontent ($userdata, "<user>", "<user>", $user_node_new, "<login>", $login);
              $update = true;
            }
          }
        }
      }

      // on any error
      if ($show != "")
      {
        // unlock file
        unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");

        // log
        $errcode = "20022";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|User '".$login."' could not be edited by user '".$user."' (".getuserip().") due to an error: ".strip_tags($show);

        $add_onload = "";
      }
      // save user xml file if changes have been made
      elseif ($show == "" && !empty ($userdata))
      {
        // eventsystem
        if (!empty ($eventsystem['onsaveuser_pre']) && empty ($eventsystem['hide'])) 
          onsaveuser_pre ($login, $user_node, $user);

        $test = savelockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php", $userdata);

        if ($test != false)
        {
          // eventsystem
          if (!empty ($eventsystem['onsaveuser_post']) && empty ($eventsystem['hide'])) 
            onsaveuser_post ($login, $user_node, $user);

          // log
          $errcode = "00020";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|User '".$login."' has been edited by user '".$user."' (".getuserip().")";

          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-user-information-was-saved-successfully'][$lang]."</span>";

          $success = true;
        }
        else
        {
          // unlock file
          unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");

          // log
          $errcode = "20021";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|User '".$login."' could not be edited by user '".$user."' (".getuserip().")";

          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-user-information-cannot-be-saved'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
        }
      }
      // error in XML manipulation
      elseif ($show == "" && $update == true)
      {
        // unlock file
        unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");

        // log
        $errcode = "20022";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|User '".$login."' could not be edited by user '".$user."' (".getuserip().") due to an XML error";

        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['an-error-occurred-in-xml-manipulation'][$lang]."</span><br />\n";
      }
      // nothing has been changed
      else
      {
        // unlock file
        unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");

        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-user-information-was-saved-successfully'][$lang]."</span>";

        $success = true;
      }

      // check if lanuage was changed and register new language
      // must be registered at last, otherwise a language settings mixture in this script would occure
      if ($language != "" && $login_cat == "home")
      {
        $lang = $language;
        $_SESSION['hcms_lang'] = $lang;
      }
    }
    elseif ($show == "")
    {
      // unlock file
      unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");

      // log
      $errcode = "20023";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|User '".$login."' could not be edited by user '".$user."' (".getuserip().") due to missing user data";

      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-user-information-cannot-be-accessed'][$lang]."</span><br />\n".$hcms_lang['the-user-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
    }
  }
  // required input is missing
  else
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-user-information-cannot-be-accessed'][$lang]."</span><br />\n".$hcms_lang['the-user-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
  }

  // save log
  savelog (@$error);

  // return results
  $result = array();
  $result['result'] = $success;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;

  return $result;
}

// ---------------------------------------- deleteuser --------------------------------------------
// function: deleteuser()
// input: publication where the user should be removed [*Null*] for all publications [string], login name of the account that will be removed [string], user name [string]
// output: result array

// description:
// This function removes a user

function deleteuser ($site, $login, $user="sys")
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $add_onload = "";
  $show = "";
  $success = false;

  // set default language
  if (empty ($lang)) $lang = "en";

  // check permissions
  if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
  {
    if (
      (!valid_publicationname ($site) && (!checkrootpermission ('user') || !checkrootpermission ('userdelete'))) || 
      (valid_publicationname ($site) && (!checkglobalpermission ($site, 'user') || !checkglobalpermission ($site, 'userdelete')))
    )
    {
      $result = array();
      $result['result'] = false;
      $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
      return $result;
    }
  }

  // site can be *Null*, which is naot a valid publication name
  if ($site != "" && valid_objectname ($login) && valid_objectname ($user))
  {
    // eventsystem
    if (!empty ($eventsystem['ondeleteuser_pre']) && empty ($eventsystem['hide'])) 
      ondeleteuser_pre ($login, $user);

    // load user xml file
    $userdata = loadlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php", 5);

    if ($userdata != false)
    { 
      if (!valid_publicationname ($site))
      {
        // delete user
        $userdata = deletecontent ($userdata, "<user>", "<login>", $login);

        // remove task list file of user (before version 5.8.4)
        if (is_file ($mgmt_config['abs_path_data']."task/".$login.".xml.php")) deletefile ($mgmt_config['abs_path_data']."task/", $login.".xml.php", 0);

        // remove task list file of user (since version 5.8.4)
        rdbms_deletetask ("", "", "", $login);

        // remove checked out list file of user
        if (is_file ($mgmt_config['abs_path_data']."checkout/".$login.".dat")) deletefile ($mgmt_config['abs_path_data']."checkout/", $login.".dat", 0);

        // remove favorites file of user
        if (is_file ($mgmt_config['abs_path_data']."checkout/".$login.".fav")) deletefile ($mgmt_config['abs_path_data']."checkout/", $login.".fav", 0);

        // remove home screen configuration file of user
        if (is_file ($mgmt_config['abs_path_data']."checkout/".$login.".home.dat")) deletefile ($mgmt_config['abs_path_data']."checkout/", $login.".home.dat", 0);

        // remove objectlist definition file of user
        if (is_file ($mgmt_config['abs_path_data']."checkout/".$login.".objectlistcols.json")) deletefile ($mgmt_config['abs_path_data']."checkout/", $login.".objectlistcols.json", 0);

        // remove GUI definition file of user
        if (is_file ($mgmt_config['abs_path_data']."checkout/".$login.".gui.dat")) deletefile ($mgmt_config['abs_path_data']."checkout/", $login.".gui.dat", 0);

        // remove toolbar customization definition file of user
        if (is_file ($mgmt_config['abs_path_data']."checkout/".$login.".toolbar.json")) deletefile ($mgmt_config['abs_path_data']."checkout/", $login.".toolbar.json", 0);

        // remove saved searches of user
        deletelog ($login.".search");

        // remove user event log
        deletelog ($login.".user");

        // remove password log
        deletelog ($login.".password");
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
          if (!empty ($eventsystem['ondeleteuser_post']) && empty ($eventsystem['hide'])) 
            ondeleteuser_post ($login, $user);

          // log
          $errcode = "00030";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|User '".$login."' has been deleted by user '".$user."' (".getuserip().")";

          $add_onload = "parent.frames['mainFrame'].location.reload();";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['all-user-information-was-removed-successfully'][$lang]."</span>\n";
          
          $success = true;
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

        // log
        $errcode = "20030";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|User '".$login."' could not be deleted by user '".$user."' (".getuserip().")";

        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['an-error-occurred-in-function-deletecontent'][$lang]."</span><br />\n";
      }
    }
    else
    {
      // unlock file
      unlockfile ($user, $mgmt_config['abs_path_data']."user/", "user.xml.php");

      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-user-information-cant-be-accessed'][$lang]."</span><br />\n".$hcms_lang['the-user-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
    }
  }
  // input parameters missing
  else
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['necessary-user-information-is-missing'][$lang]."</span><br />".$hcms_lang['please-go-back-and-select-a-user'][$lang]."\n";
  }

  // save log
  savelog (@$error);

  // return results
  $result = array();
  $result['result'] = $success;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;

  return $result;
}

// ====================================== GROUP FUNCTIONS ==========================================

// ---------------------------------------- creategroup --------------------------------------------
// function: creategroup()
// input: publication name [string], group name [string], user name [string]
// output: result array

// description:
// This function creates a new user group

function creategroup ($site, $groupname, $user="sys")
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $error = array();
  $success = false;
  $add_onload = "";
  $show = "";

  // set default language
  if (empty ($lang)) $lang = "en";
  
  // check permissions
  if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
  {
    if (!valid_publicationname ($site) || !checkglobalpermission ($site, 'group') || !checkglobalpermission ($site, 'groupcreate'))
    {
      $result = array();
      $result['result'] = false;
      $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
      return $result;
    }
  }

  // check if sent data is available
  if (!valid_publicationname ($site) || !valid_objectname ($groupname) || strlen ($groupname) > 100 || !valid_objectname ($user))
  {
    $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['necessary-group-name-is-missing'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-enter-a-name'][$lang]."\n";
  }
  // test if group name includes special characters
  elseif (specialchr ($groupname, "-_") == true)
  {
    $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['special-characters-in-expressions-are-not-allowed'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-try-another-expression'][$lang]."\n";
  }
  else
  {
    // eventsystem
    if (!empty ($eventsystem['oncreategroup_pre']) && empty ($eventsystem['hide'])) 
      oncreategroup_pre ($groupname, $user);

    $groupname = trim ($groupname);

    // load usergroup xml file
    $usergroupdata = loadlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php", 3);

    if ($usergroupdata != false)
    {
      // check if usergroup exists already
      $testlogin = selectcontent ($usergroupdata, "<usergroup>", "<groupname>", $groupname);

      if ($testlogin != false)
      {
        //unlock file
        unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");

        $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-group-exists-already'][$lang]."</span><br />\n".$hcms_lang['please-go-back-and-try-another-name'][$lang]."\n";
      }
      else
      {
        // load usergroup xml schema
        $usergroup_schema_xml = loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "usergroup.schema.xml.php");

        // insert values into xml schema
        $usergroupnew = setcontent ($usergroup_schema_xml, "<usergroup>", "<groupname>", $groupname, "", "");

        // add new usergroup
        $usergroupdatanew = insertcontent ($usergroupdata, $usergroupnew, "<usergrouplist>");

        if ($usergroupdatanew != false)
        {
          // save usergroup xml file
          $test = savelockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php", $usergroupdatanew);

          if ($test != false)
          {
            // eventsystem
            if (!empty ($eventsystem['oncreategroup_post']) && empty ($eventsystem['hide'])) 
              oncreategroup_post ($groupname, $user);

            $usergroupdata = $usergroupdatanew;

            // log
            $errcode = "00040";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|New group '".$groupname."' has been created by user '".$user."' (".getuserip().")";

            $add_onload = "parent.frames['mainFrame'].location='group_edit_form.php?site=".url_encode($site)."&preview=no&group_name=".url_encode($groupname)."'; ";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['group'][$lang]." '".$groupname."' ".$hcms_lang['was-created'][$lang]."</span><br />\n".$hcms_lang['now-you-can-edit-the-group'][$lang]."<br />\n";
            
            $success = true;
          }
          else
          {
            //unlock file
            unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");

            $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['group-information-cannot-be-saved'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
          }
        }
        else
        {
          //unlock file
          unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");

          // log
          $errcode = "20040";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|New group '".$groupname."' could not be created by user '".$user."' (".getuserip().")";

          $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['group-information-could-not-be-inserted'][$lang]."</span>\n"; 
        }
      }
    }
    else
    {
      //unlock file
      unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");

      $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['group-information-cannot-be-accessed'][$lang]."</span><br />\n".$hcms_lang['group-information-is-missing-or-you-do-not-have-read-permission'][$lang]."\n";
    }
  }

  // save log
  savelog (@$error);

  // return results
  $result = array();
  $result['result'] = $success;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;

  return $result;
}

// ---------------------------------------- editgroup --------------------------------------------
// function: editgroup()
// input: publication name [string], group name [string], page folder access array [array], component folder access array [array], permissions with permission name as key and value of 1 or 0 [array], enabled plugin names [array], user name [string]
// output: result array

// description:
// This function edits the settings of a user group.
// 
// The following permissions can be used:
// desktopglobal
// desktopsetting
// desktoptaskmgmt
// desktopcheckedout
// desktoptimetravel
// userglobal
// usercreate
// userdelete
// useredit
// groupglobal
// groupcreate
// groupdelete
// groupedit
// siteglobal
// sitecreate
// sitedelete
// siteedit
// persglobal
// perstrack
// perstrackcreate
// perstrackdelete
// perstrackedit
// persprof
// persprofcreate
// persprofdelete
// persprofedit
// workflowglobal
// workflowproc
// workflowproccreate
// workflowprocdelete
// workflowprocedit
// workflowprocfolder
// workflowscript
// workflowscriptcreate
// workflowscriptdelete
// workflowscriptedit
// templateglobal
// tpl
// tplcreate
// tpldelete
// tpledit
// tplmedia
// tplmediacatcreate
// tplmediacatdelete
// tplmediacatrename
// tplmediaupload
// tplmediadelete
// componentglobal
// compupload
// compdownload
// compsendlink
// compfoldercreate
// compfolderdelete
// compfolderrename
// compcreate
// compdelete
// comprename
// comppublish
// pageglobal
// pagesendlink
// pagefoldercreate
// pagefolderdelete
// pagefolderrename
// pagecreate
// pagedelete
// pagerename
// pagepublish
// 
// The value of each parameter can be 1 for granted permission or 0 for no permission. 
// If you need further explanation about the permissions, please have a look at the Administrators Guide.

function editgroup ($site, $groupname, $pageaccess, $compaccess, $permission, $plugins, $user)
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $success = false;
  $add_onload = "";
  $show = "";

  // set default language
  if (empty ($lang)) $lang = "en";

  // check permissions
  if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
  {
    if (!valid_publicationname ($site) || !checkglobalpermission ($site, 'group') || !checkglobalpermission ($site, 'groupedit'))
    {
      $result = array();
      $result['result'] = false;
      $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
      return $result;
    }
  }

    // check if sent data is available
  if (!valid_publicationname ($site) || !valid_objectname ($groupname) || !valid_objectname ($user))
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['ontext0'][$lang]."</span><br />\n".$hcms_lang['ontext0'][$lang]."\n";
  }
  else
  {
    // eventsystem
    if (!empty ($eventsystem['ondeletegroup_pre']) && empty ($eventsystem['hide'])) 
      ondeletegroup_pre ($groupname, $user); 

    // check if login is an attribute of a sent string and extract group name
    if (strpos ($groupname, ".php") > 0) $groupname = getattribute ($groupname, "group_name");

    $groupname = trim ($groupname);

    // load user xml file
    $groupdata = loadlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php", 3);

    // set permissions
    if (is_array ($permission) && $groupdata != false)
    {
      // user permissions
      if (!isset ($permission['desktopglobal']) || $permission['desktopglobal'] != 1) $permission['desktopglobal'] = 0;
      if (!isset ($permission['desktopsetting']) || $permission['desktopsetting'] != 1) $permission['desktopsetting'] = 0;
      if (!isset ($permission['desktoptaskmgmt']) || $permission['desktoptaskmgmt'] != 1) $permission['desktoptaskmgmt'] = 0;
      if (!isset ($permission['desktopcheckedout']) || $permission['desktopcheckedout'] != 1) $permission['desktopcheckedout'] = 0;
      if (!isset ($permission['desktoptimetravel']) || $permission['desktoptimetravel'] != 1) $permission['desktoptimetravel'] = 0;
      if (!isset ($permission['desktopfavorites']) || $permission['desktopfavorites'] != 1) $permission['desktopfavorites'] = 0;
      if (!isset ($permission['desktopprojectmgmt']) || $permission['desktopprojectmgmt'] != 1) $permission['desktopprojectmgmt'] = 0; // added in version 6.0.1

      $desktoppermissions = "desktop=".$permission['desktopglobal'].$permission['desktopsetting'].$permission['desktoptaskmgmt'].$permission['desktopcheckedout'].$permission['desktoptimetravel'].$permission['desktopfavorites'].$permission['desktopprojectmgmt'];

      // user permissions
      if (!isset ($permission['userglobal']) || $permission['userglobal'] != 1) $permission['userglobal'] = 0;
      if (!isset ($permission['usercreate']) || $permission['usercreate'] != 1) $permission['usercreate'] = 0;
      if (!isset ($permission['userdelete']) || $permission['userdelete'] != 1) $permission['userdelete'] = 0;
      if (!isset ($permission['useredit']) || $permission['useredit'] != 1) $permission['useredit'] = 0;

      $userpermissions = "user=".$permission['userglobal'].$permission['usercreate'].$permission['userdelete'].$permission['useredit'];

      // group permissions
      if (!isset ($permission['groupglobal']) || $permission['groupglobal'] != 1) $permission['groupglobal'] = 0;
      if (!isset ($permission['groupcreate']) || $permission['groupcreate'] != 1) $permission['groupcreate'] = 0;
      if (!isset ($permission['groupdelete']) || $permission['groupdelete'] != 1) $permission['groupdelete'] = 0;
      if (!isset ($permission['groupedit']) || $permission['groupedit'] != 1) $permission['groupedit'] = 0;

      $grouppermissions = "group=".$permission['groupglobal'].$permission['groupcreate'].$permission['groupdelete'].$permission['groupedit'];

      // site permissions
      if (!isset ($permission['siteglobal']) || $permission['siteglobal'] != 1) $permission['siteglobal'] = 0;
      if (!isset ($permission['sitecreate']) || $permission['sitecreate'] != 1) $permission['sitecreate'] = 0;
      if (!isset ($permission['sitedelete']) || $permission['sitedelete'] != 1) $permission['sitedelete'] = 0;
      if (!isset ($permission['siteedit']) || $permission['siteedit'] != 1) $permission['siteedit'] = 0;

      $sitepermissions = "site=".$permission['siteglobal'].$permission['sitecreate'].$permission['sitedelete'].$permission['siteedit'];

      // personalization permissions
      if (!isset ($permission['persglobal']) || $permission['persglobal'] != 1) $permission['persglobal'] = 0;
      if (!isset ($permission['perstrack']) || $permission['perstrack'] != 1) $permission['perstrack'] = 0;
      if (!isset ($permission['perstrackcreate']) || $permission['perstrackcreate'] != 1) $permission['perstrackcreate'] = 0;
      if (!isset ($permission['perstrackdelete']) || $permission['perstrackdelete'] != 1) $permission['perstrackdelete'] = 0;
      if (!isset ($permission['perstrackedit']) || $permission['perstrackedit'] != 1) $permission['perstrackedit'] = 0;
      if (!isset ($permission['persprof']) || $permission['persprof'] != 1) $permission['persprof'] = 0;
      if (!isset ($permission['persprofcreate']) || $permission['persprofcreate'] != 1) $permission['persprofcreate'] = 0;
      if (!isset ($permission['persprofdelete']) || $permission['persprofdelete'] != 1) $permission['persprofdelete'] = 0;
      if (!isset ($permission['persprofedit']) || $permission['persprofedit'] != 1) $permission['persprofedit'] = 0;

      $perspermissions = "pers=".$permission['persglobal'].$permission['perstrack'].$permission['perstrackcreate'].$permission['perstrackdelete'].$permission['perstrackedit'].$permission['persprof'].$permission['persprofcreate'].$permission['persprofdelete'].$permission['persprofedit'];

      // workflow permissions
      if (!isset ($permission['workflowglobal']) || $permission['workflowglobal'] != 1) $permission['workflowglobal'] = 0;
      if (!isset ($permission['workflowproc']) || $permission['workflowproc'] != 1) $permission['workflowproc'] = 0;
      if (!isset ($permission['workflowproccreate']) || $permission['workflowproccreate'] != 1) $permission['workflowproccreate'] = 0;
      if (!isset ($permission['workflowprocdelete']) || $permission['workflowprocdelete'] != 1) $permission['workflowprocdelete'] = 0;
      if (!isset ($permission['workflowprocedit']) || $permission['workflowprocedit'] != 1) $permission['workflowprocedit'] = 0;
      if (!isset ($permission['workflowprocfolder']) || $permission['workflowprocfolder'] != 1) $permission['workflowprocfolder'] = 0;
      if (!isset ($permission['workflowscript']) || $permission['workflowscript'] != 1) $permission['workflowscript'] = 0;
      if (!isset ($permission['workflowscriptcreate']) || $permission['workflowscriptcreate'] != 1) $permission['workflowscriptcreate'] = 0;
      if (!isset ($permission['workflowscriptdelete']) || $permission['workflowscriptdelete'] != 1) $permission['workflowscriptdelete'] = 0;
      if (!isset ($permission['workflowscriptedit']) || $permission['workflowscriptedit'] != 1) $permission['workflowscriptedit'] = 0;

      $workflowpermissions = "workflow=".$permission['workflowglobal'].$permission['workflowproc'].$permission['workflowproccreate'].$permission['workflowprocdelete'].$permission['workflowprocedit'].$permission['workflowprocfolder'].$permission['workflowscript'].$permission['workflowscriptcreate'].$permission['workflowscriptdelete'].$permission['workflowscriptedit'];

      // template permissions
      if (!isset ($permission['templateglobal']) || $permission['templateglobal'] != 1) $permission['templateglobal'] = 0;
      if (!isset ($permission['tpl']) || $permission['tpl'] != 1) $permission['tpl'] = 0;
      if (!isset ($permission['tplcreate']) || $permission['tplcreate'] != 1) $permission['tplcreate'] = 0;
      if (!isset ($permission['tpldelete']) || $permission['tpldelete'] != 1) $permission['tpldelete'] = 0;
      if (!isset ($permission['tpledit']) || $permission['tpledit'] != 1) $permission['tpledit'] = 0;

      $templatepermissions = "template=".$permission['templateglobal'].$permission['tpl'].$permission['tplcreate'].$permission['tpldelete'].$permission['tpledit'];

      // template media permissions
      if (!isset ($permission['tplmedia']) || $permission['tplmedia'] != 1) $permission['tplmedia'] = 0;
      if (!isset ($permission['tplmediacatcreate']) || $permission['tplmediacatcreate'] != 1) $permission['tplmediacatcreate'] = 0;
      if (!isset ($permission['tplmediacatdelete']) || $permission['tplmediacatdelete'] != 1) $permission['tplmediacatdelete'] = 0;
      if (!isset ($permission['tplmediacatrename']) || $permission['tplmediacatrename'] != 1) $permission['tplmediacatrename'] = 0;
      if (!isset ($permission['tplmediaupload']) || $permission['tplmediaupload'] != 1) $permission['tplmediaupload'] = 0;
      if (!isset ($permission['tplmediadelete']) || $permission['tplmediadelete'] != 1) $permission['tplmediadelete'] = 0;

      $mediapermissions = "media=".$permission['tplmedia'].$permission['tplmediacatcreate'].$permission['tplmediacatdelete'].$permission['tplmediacatrename'].$permission['tplmediaupload'].$permission['tplmediadelete'];

      // component permissions
      if (!isset ($permission['componentglobal']) || $permission['componentglobal'] != 1) $permission['componentglobal'] = 0;
      if (!isset ($permission['compupload']) || $permission['compupload'] != 1) $permission['compupload'] = 0;
      if (!isset ($permission['compdownload']) || $permission['compdownload'] != 1) $permission['compdownload'] = 0;
      if (!isset ($permission['compsendlink']) || $permission['compsendlink'] != 1) $permission['compsendlink'] = 0;
      if (!isset ($permission['compfoldercreate']) || $permission['compfoldercreate'] != 1) $permission['compfoldercreate'] = 0;
      if (!isset ($permission['compfolderdelete']) || $permission['compfolderdelete'] != 1) $permission['compfolderdelete'] = 0;
      if (!isset ($permission['compfolderrename']) || $permission['compfolderrename'] != 1) $permission['compfolderrename'] = 0;
      if (!isset ($permission['compcreate']) || $permission['compcreate'] != 1) $permission['compcreate'] = 0;
      if (!isset ($permission['compdelete']) || $permission['compdelete'] != 1) $permission['compdelete'] = 0;
      if (!isset ($permission['comprename']) || $permission['comprename'] != 1) $permission['comprename'] = 0;
      if (!isset ($permission['comppublish']) || $permission['comppublish'] != 1) $permission['comppublish'] = 0;

      $componentpermissions = "component=".$permission['componentglobal'].$permission['compupload'].$permission['compdownload'].$permission['compsendlink'].$permission['compfoldercreate'].$permission['compfolderdelete'].$permission['compfolderrename'].$permission['compcreate'].$permission['compdelete'].$permission['comprename'].$permission['comppublish'];

      // content permissions
      if (!isset ($permission['pageglobal']) || $permission['pageglobal'] != 1) $permission['pageglobal'] = 0;
      if (!isset ($permission['pagesendlink']) || $permission['pagesendlink'] != 1) $permission['pagesendlink'] = 0;
      if (!isset ($permission['pagefoldercreate']) || $permission['pagefoldercreate'] != 1) $permission['pagefoldercreate'] = 0;
      if (!isset ($permission['pagefolderdelete']) || $permission['pagefolderdelete'] != 1) $permission['pagefolderdelete'] = 0;
      if (!isset ($permission['pagefolderrename']) || $permission['pagefolderrename'] != 1) $permission['pagefolderrename'] = 0;
      if (!isset ($permission['pagecreate']) || $permission['pagecreate'] != 1) $permission['pagecreate'] = 0;
      if (!isset ($permission['pagedelete']) || $permission['pagedelete'] != 1) $permission['pagedelete'] = 0;
      if (!isset ($permission['pagerename']) || $permission['pagerename'] != 1) $permission['pagerename'] = 0;
      if (!isset ($permission['pagepublish']) || $permission['pagepublish'] != 1) $permission['pagepublish'] = 0;

      $pagepermissions = "page=".$permission['pageglobal'].$permission['pagesendlink'].$permission['pagefoldercreate'].$permission['pagefolderdelete'].$permission['pagefolderrename'].$permission['pagecreate'].$permission['pagedelete'].$permission['pagerename'].$permission['pagepublish'];

      // permission string
      $permission_str = $desktoppermissions."&".$sitepermissions."&".$userpermissions."&".$grouppermissions."&".$perspermissions."&".$workflowpermissions."&".$templatepermissions."&".$mediapermissions."&".$componentpermissions."&".$pagepermissions;

      // insert values into xml object
      if ($permission_str != "") $groupdata = setcontent ($groupdata, "<usergroup>", "<permission>", $permission_str, "<groupname>", $groupname);
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
          if (empty ($object_id)) $object_id = $entry;

          $pageaccess_str .= convertpath ($site, $object_id, "page")."|";
        }
      }

      // insert values into xml object
      $groupdata = setcontent ($groupdata, "<usergroup>", "<pageaccess>", $pageaccess_str, "<groupname>", $groupname); 
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
          if (empty ($object_id)) $object_id = $entry;

          $compaccess_str .= convertpath ($site, $object_id, "comp")."|";
        }
      }

      // insert values into xml object
      $groupdata = setcontent ($groupdata, "<usergroup>", "<compaccess>", $compaccess_str, "<groupname>", $groupname); 
    }

    // plugins
    if ($groupdata != "")
    {
      if (is_array ($plugins) && sizeof ($plugins) > 0) $plugins_str = implode ("|", $plugins)."|";
      else $plugins_str = "";

      // update group to version 9.1.6
      if (strpos ($groupdata, "<plugins>") < 1)
      {
        $groupdata = str_replace ("</compaccess>", "</compaccess>\n<plugins></plugins>", $groupdata);
      }

      // insert values into xml object
      $groupdata = setcontent ($groupdata, "<usergroup>", "<plugins>", $plugins_str, "<groupname>", $groupname);
    }

    // save and unlock file
    if ($groupdata != false)
    {
      // eventsystem
      if (!empty ($eventsystem['onsavegroup_pre']) && empty ($eventsystem['hide'])) 
        onsavegroup_pre ($groupname, $groupdata, $user); 

      $test = savelockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php", $groupdata);

      if ($test != false)
      {
        // eventsystem
        if (!empty ($eventsystem['onsavegroup_post']) && empty ($eventsystem['hide'])) 
          onsavegroup_post ($groupname, $groupdata, $user);

        // log
        $errcode = "00050";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|Group '".$groupname."' has been edited by user '".$user."' (".getuserip().")";

        $add_onload = "parent.location.href='group_edit_form.php?site=".url_encode($site)."&group_name=".url_encode($groupname)."&preview=no'; ";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['group-settings-were-updated'][$lang]."</span>\n";
        
        $success = true;
      }
      else
      {
        //unlock file
        unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");

        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-group-information-cant-be-accessed'][$lang]."</span><br />\n".$hcms_lang['the-group-information-is-missing-or-you-have-no-write-permission'][$lang]."\n";

      }
    }
    else
    {
      //unlock file
      unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");

      // log
      $errcode = "20050";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Group '".$groupname."' could not be edited by user '".$user."' (".getuserip().")";

      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-group-information-cant-be-accessed'][$lang]."</span><br />\n".$hcms_lang['an-error-occurred-in-function-setcontent'][$lang]."\n";
    }
  }

  // save log
  savelog (@$error);

  // return results
  $result = array();
  $result['result'] = $success;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;

  return $result;
}

// ---------------------------------------- deletegroup --------------------------------------------
// function: deletegroup()
// input: publication name [string], group name [string], user name [string]
// output: result array

// description:
// This function removes a user group

function deletegroup ($site, $groupname, $user)
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $error = array();
  $success = false;
  $add_onload = "";
  $show = "";

  // set default language
  if (empty ($lang)) $lang = "en";

  // check permissions
  if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
  {
    if (!valid_publicationname ($site) || !checkglobalpermission ($site, 'group') || !checkglobalpermission ($site, 'groupdelete'))
    {
      $result = array();
      $result['result'] = false;
      $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
      return $result;
    }
  }

  // check if sent data is available
  if (!valid_publicationname ($site) || !valid_objectname ($groupname) || !valid_objectname ($user))
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['necessary-group-name-is-missing'][$lang]."</span><br />\n".$hcms_lang['please-select-a-group-to-remove'][$lang]."\n";
  }
  else
  {
    // eventsystem
    if (!empty ($eventsystem['ondeletegroup_pre']) && empty ($eventsystem['hide'])) 
      ondeletegroup_pre ($groupname, $user); 

    // check if login is an attribute of a sent string and extract group name
    if (strpos ($groupname, ".php") > 0) $groupname = getattribute ($groupname, "group_name");

    $groupname = trim ($groupname);

    // load users and check if users are still group members
    $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php"); 

    if ($userdata != false && $userdata != "")
    {
      $member_array = selectxmlcontent ($userdata, "<memberof>", "<publication>", "$site");

      if (is_array ($member_array))
      {
        $member_string = implode ("\n", $member_array);
        $member_array = selectxmlcontent ($member_string, "<memberof>", "<usergroup>", "*|$groupname|*");

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
          $usergroupdatanew = deletecontent ($usergroupdata, "<usergroup>", "<groupname>", $groupname);
 
          if ($usergroupdatanew != false)
          {
            // save user xml file
            $test = savelockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php", $usergroupdatanew);

            if ($test != false)
            {
              // eventsystem
              if (!empty ($eventsystem['ondeletegroup_post']) && empty ($eventsystem['hide'])) 
                ondeletegroup_post ($groupname, $user); 

              $usergroupdata = $usergroupdatanew;

              // log
              $errcode = "00060";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|Group '".$groupname."' has been deleted by user '".$user."' (".getuserip().")";

              $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
              $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-group-was-removed'][$lang]."</span><br />\n".$hcms_lang['all-group-information-was-successfully-removed'][$lang]."\n";
              
              $success = true;
            }
            else
            {
              //unlock file
              unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");

              $add_onload = "";
              $show = "<span class=\"hcmsHeadline\">".$hcms_lang['group-information-cannot-be-accessed'][$lang]."</span><br />\n".$hcms_lang['group-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
            }
          }
          else
          {
            //unlock file
            unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");

            // log
            $errcode = "20060";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Group '".$groupname."' could not be deleted by user '".$user."' (".getuserip().")";

            $add_onload = "";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['group-information-could-not-be-removed'][$lang]."</span><br />\n";
          }
        } 
        else
        {
          //unlock file
          unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");

          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['group-information-cannot-be-accessed'][$lang]."</span><br />\n".$hcms_lang['group-information-is-missing-or-you-do-not-have-read-permissions'][$lang]."\n";
        } 
      }
      else
      {
        //unlock file
        unlockfile ($user, $mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");

        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['group-information-cannot-be-accessed'][$lang]."</span><br />\n".$hcms_lang['group-information-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
      }
    }
    else
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['group-information-could-not-be-removed'][$lang]."</span><br />\n".$hcms_lang['users-are-still-members-of-this-group'][$lang]."\n";
    }
  }

  // save log
  savelog (@$error);

  // return results
  $result = array();
  $result['result'] = $success;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;

  return $result;
}

// ---------------------------------------- renamegroupfolder --------------------------------------------
// function: renamegroupfolder()
// input: publication name [string], category [page,comp], old location [string], new location [string], user name [string]
// output: true / false on error

// description:
// This function renames a workgroup folder

function renamegroupfolder ($site, $cat, $folder_curr, $folder_new, $user)
{
  global $mgmt_config, $hcms_lang, $lang;

  // if a database is used the object IDs will be used instead of pathes (since version 5.6.4)
  if (!empty ($mgmt_config['db_connect_rdbms'])) return true;

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
// input: publication name [string], category [page,comp], path to the folder [string], user name [string]
// output: true / false on error

// description:
// This function removes a group folder.

function deletegroupfolder ($site, $cat, $folderpath, $user)
{
  global $mgmt_config, $hcms_lang, $lang;

  // if a database is used the object IDs will be used instead of pathes (since version 5.6.4)
  if (!empty ($mgmt_config['db_connect_rdbms'])) return true;

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
// input: publication name [string], category [page,comp], old location [string], new location [string], user name [string]
// output: true / false on error

// description:
// This function renames a workgroup folder

function renameworkflowfolder ($site, $cat, $folder_curr, $folder_new, $user)
{
  global $mgmt_config, $hcms_lang, $lang;

  // if a database is used the object IDs will be used instead of pathes (since version 5.6.4)
  if (!empty ($mgmt_config['db_connect_rdbms'])) return true;

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
// input: publication name [string], category [page,comp], location of folder [string], user name [string]
// output: true / false on error

// description:
// This function removes a workgroup folder

function deleteworkflowfolder ($site, $cat, $folderpath, $user)
{
  global $mgmt_config, $hcms_lang, $lang;

  // if a database is used the object IDs will be used instead of pathes (since version 5.6.4)
  if (!empty ($mgmt_config['db_connect_rdbms'])) return true;

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
// input: publication name [string], media category name [string]
// output: Array with onload JS-code and message

// description:
// Creates a new media category in the template media index file. Only used for template media.

function createmediacat ($site, $mediacat_name)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $show = "";
  $add_onload = "";
  $result = array();

  if (session_id() != "") $session_id = session_id();
  else $session_id = createuniquetoken ();

  // set default language
  if (empty ($lang)) $lang = "en";

  // verify input
  if (!valid_publicationname ($site) || $mediacat_name == "" || strlen ($mediacat_name) > 100)
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['please-fill-in-a-category-name'][$lang]."</span>\n";
  }
  // test if folder name includes special characters
  elseif (specialchr ($mediacat_name, " -_") == true)
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['special-characters-in-expressions-are-not-allowed'][$lang]."</span>\n";
  }
  else
  {
    $mediacat_name = trim ($mediacat_name);

    // define media index file name
    $datafile = $site.".media.tpl.dat";

    // load categories
    $mediacat_data = loadlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile, 3);
    $mediacat_array_work = explode ("\n", $mediacat_data);

    if (is_array ($mediacat_array_work))
    {
      foreach ($mediacat_array_work as $mediacat_record)
      {
        if (strpos ($mediacat_record, ":|") > 0)
        {
          list ($category, $files) = explode (":|", $mediacat_record);

          if ($category == $mediacat_name)
          {
            $add_onload = "";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-media-category-exists-already'][$lang]."</span><br />\n".$hcms_lang['please-try-another-category-name'][$lang]."\n";

            break;
          }
        }
      }
    }
  }

  // check if folder could be created
  if ($show == "")
  {
    // append new category
    if (is_array ($mediacat_array_work) && sizeof ($mediacat_array_work) > 0)
    {
      $mediacat_array_work[sizeof ($mediacat_array_work)] = $mediacat_name.":|";
      $mediacat_data = implode ("\n", $mediacat_array_work);
    }
    else
    {
      $mediacat_array_work = array();
      $mediacat_array_work[0] = $mediacat_name.":|";
      $mediacat_data = $mediacat_array_work[0];
    }

    // save file
    $savefile = savelockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile, $mediacat_data);

    if ($savefile != false)
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['media-category-was-created'][$lang]."</span><br />\n";
    }
    else
    {
      // unlock file
      unlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile);

      // folder could't not be created due to missing write permission
      $add_onload =  "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['media-category-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
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
// input: publication name [string], old media category name [string], new media category name [string]
// output: Array with onload JS-code and message

// description:
// Renames a new media category in the template media index file

function renamemediacat ($site, $mediacat_name_curr, $mediacat_name)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  //initialize
  $show = "";
  $add_onload = "";
  $result = array();
  
  if (session_id() != "") $session_id = session_id();
  else $session_id = createuniquetoken ();

  // set default language
  if (empty ($lang)) $lang = "en";

  // define media index file name
  $datafile = $site.".media.tpl.dat";

  // load categories
  $mediacat_data = loadlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile, 3);
  $mediacat_array_work = explode ("\n", $mediacat_data);

  // verify input
  if (!valid_publicationname ($site) || $mediacat_name_curr == "" || $mediacat_name == "" || strlen ($mediacat_name) > 100)
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['please-fill-in-a-category-name'][$lang]."</span>\n";
  }
  // check if category exists already
  elseif (is_array ($mediacat_array_work) && sizeof ($mediacat_array_work) > 0)
  {
    foreach ($mediacat_array_work as $mediacat_record)
    {
      if (strpos ($mediacat_record, ":|") > 0)
      {
        list ($category, $files) = explode (":|", $mediacat_record);

        if ($category == $mediacat_name)
        {
          // make entry in media database file
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-media-category-exists-already'][$lang]."</span><br />\n".$hcms_lang['please-try-another-category-name'][$lang]."\n";
          break;
        }
      }
    }
  }
  // test if folder name includes special characters
  elseif (specialchr ($mediacat_name, " -_") == true)
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['special-characters-in-expressions-are-not-allowed'][$lang]."</span>\n";
  }

  // check if category could be created
  if ($show == "")
  {
    // rename category
    $i = 0;

    foreach ($mediacat_array_work as $mediacat_record)
    {
      if (strpos ($mediacat_record, ":|") > 0)
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

    // append new category
    $mediacat_data = implode ("\n", $mediacat_array_work);

    // save file
    $savefile = savelockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile, $mediacat_data);

    if ($savefile != false)
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-media-category-was-successfully-renamed'][$lang]."</span>\n";
    }
    else
    {
      // unlock file
      unlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile);

      // folder could't not be created due to missing write permission
      $add_onload =  "";
      $show = "<span class=\"hcmsHeadline\"> ".$hcms_lang['the-media-category-could-not-be-renamed'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
    }
  }
  elseif (!empty ($datafile))
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
// input: publication name [string], media category name [string]
// output: Array with onload JS-code and message

// description:
// Deletes a new media category in the template media index file

function deletemediacat ($site, $mediacat_name)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $show = "";
  $add_onload = "";

  if (session_id() != "") $session_id = session_id();
  else $session_id = createuniquetoken ();

  // set default language
  if (empty ($lang)) $lang = "en";

  // check if mediacat was sent
  if (!valid_publicationname ($site) || $mediacat_name == "")
  {
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['please-select-a-media-category'][$lang]."</span>\n";
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
      if (strpos ($mediacat_record, ":|") > 0)
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
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-media-category-was-deleted'][$lang]."</span>\n";
    }
    else
    {
      // unlock file
      unlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile);

      // folder could not be deleted due to missing write permission
      $add_onload =  "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-media-category-could-not-be-deleted'][$lang]."</span><br />\n".$hcms_lang['you-have-no-write-permission'][$lang]."\n";
    }
  }
  else
  {
    // unlock file
    unlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile);

    // folder could not be deleted due to not empty category
    $add_onload =  "";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-media-category-could-not-be-deleted'][$lang]."</span><br />\n".$hcms_lang['the-category-still-holds-files'][$lang]."\n";
  }

  $result['add_onload'] = $add_onload;
  $result['message'] = $show;

  return $result;
}

// ------------------------- uploadtomediacat -----------------------------
// function: uploadtomediacat()
// input: publication name [string], media category name [string], PHP global FILES variable [array]
// output: Array with onload JS-code and message

// description:
// Uploads a media file into a given template media category

function uploadtomediacat ($site, $mediacat_name, $global_files)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $show = "";
  $result = array();

  if (session_id() != "") $session_id = session_id();
  else $session_id = createuniquetoken ();

  // set default language
  if (empty ($lang)) $lang = "en";

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

  // replace freespaces with underline
  if (preg_match ("/ /", $global_files['file']['name']))
  {
    $filename_new = str_replace (" ", "_", $global_files['file']['name']);
  }
  else $filename_new = $global_files['file']['name'];

  // file extension
  $file_ext = strtolower (strrchr ($filename_new, "."));

  // error if no file is selected
  if (!valid_publicationname ($site) || !valid_objectname ($mediacat_name) || $global_files['file']['name'] == "")
  {
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['no-file-selected-to-upload'][$lang]."</span>\n";
  }
  // test if category name includes special characters
  elseif (specialchr ($filename_new, ".-_") == true)
  {
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['special-characters-in-file-names-are-not-allowed'][$lang]."</span>\n";
  }
  // error if file is to big
  elseif ($sizelim == "yes" && $global_files["file"]["size"] > $size)
  {
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-file-you-are-trying-to-upload-is-too-big'][$lang]."</span>\n";
  }
  // error if file is a server side script
  elseif (substr_count (".asp.aspx.jsp.php.pl.xhtml.html.htm", $file_ext) > 0)
  {
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-file-you-are-trying-to-upload-is-wrong-type'][$lang]."</span>\n";
  }
  // error if file isn't certain type
  elseif ($certtype == "yes" && substr_count ($type, $global_files["file"]["type"]) > 0)
  {
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-file-you-are-trying-to-upload-is-wrong-type'][$lang]." (".$global_files["file"]["type"].")</span>\n";
  }
  // error if file exists
  elseif (is_file ($mediadir.$filename_new))
  {
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-file-you-are-trying-to-upload-already-exists'][$lang]."</span><br />".$hcms_lang['please-note-the-media-file-name-in-the-media-database-must-be-unique'][$lang]."\n";
  }
  // upload file
  else
  {
    // upload file
    @move_uploaded_file ($global_files['file']['tmp_name'], $mediadir.$filename_new) or $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-file-you-are-trying-to-upload-couldnt-be-copied-to-the-server'][$lang]."</span>\n";

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
        if (strpos ($mediacat_record, ":|") > 0)
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
// input: publication name [string], media file name [string]
// output: Array with onload JS-code and message

// description:
// Deletes a media file from the template media category index

function deletefrommediacat ($site, $mediafile)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  if (session_id() != "") $session_id = session_id();
  else $session_id = createuniquetoken ();

  // set default language
  if (empty ($lang)) $lang = "en";

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
        $test = deletefile ($mediadir, $mediafile, false);

        // remote client
        remoteclient ("delete", "abs_path_media", $site, $mediadir, "", $mediafile, "");
      
        $add_onload = "goToURL('parent.frames[\'mainFrame2\']','".cleandomain ($mgmt_config['url_path_cms'])."empty.php'); return document.returnValue; ";

        $show = $hcms_lang['the-selected-media-file-was-removed'][$lang];
      }
      else
      {
        unlockfile ($session_id, $mgmt_config['abs_path_data']."media/", $datafile);

        $add_onload = "parent.frames['mainFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php?site=".url_encode($site)."'; ";

        $show = $hcms_lang['the-selected-media-file-could-not-be-removed'][$lang];
      }
    }
    else
    {
      $add_onload = "";

      $show = $hcms_lang['the-selected-media-file-could-not-be-removed'][$lang];
    }
  }
  else
  {
    $add_onload = "";

    $show = $hcms_lang['the-selected-media-file-was-removed'][$lang]." '".$mediafile."' ".$hcms_lang['the-selected-media-file-could-not-be-removed'][$lang];
  }

  $result['add_onload'] = $add_onload;
  $result['message'] = $show;

  return $result;
} 

// ====================================== OBJECT FUNCTIONS ==========================================

// ---------------------------------------- createfolder --------------------------------------------
// function: createfolder()
// input: publication name [string], location [string], folder name [string], user name [string]
// output: result array

// description:
// This function creates a new folder. The folder name must not match any temp file pattern.

function createfolder ($site, $location, $folder, $user)
{
  global $eventsystem, $mgmt_config, $cat, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $hcms_lang, $lang;

  // default max length
  if (empty ($mgmt_config['max_digits_filename']) || intval ($mgmt_config['max_digits_filename']) < 1) $mgmt_config['max_digits_filename'] = 236;

  // initialize
  $success = false;
  $add_onload = "";
  $show = "";
  $folder_orig = "";
  $contentfile = "";
  $container_id = "";
  $page_box_xml = "";

  // set default language
  if (empty ($lang)) $lang = "en";

  // buffer new folder name
  $folder_orig = $folder; 

  // replace .recycle extension since it is used to mark objects in the recycle bin
  $folder = trim ($folder);
  if (substr ($folder, -8) == ".recycle") $folder = str_replace (".recycle", "_recycle", $folder);

  // folder name
  $folder = createfilename ($folder);

  // the max chacracters for an objectpath is 16,000 (due to the database attribute objectpath)
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($folder) && accessgeneral ($site, $location, "") && strlen ($folder) <= $mgmt_config['max_digits_filename'] && valid_objectname ($user) && !is_tempfile ($folder))
  {
    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_comp']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // deconvertpath location
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, $cat);

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // check permissions
    if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
    {
      $cat = getcategory ($site, $location);
      $ownergroup = accesspermission ($site, $location, $cat);
      $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);
      
      if (empty ($setlocalpermission['root']) || empty ($setlocalpermission['foldercreate']))
      {
        $result = array();
        $result['result'] = false;
        $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
        return $result;
      }
    }

    // eventsystem
    if (!empty ($eventsystem['oncreatefolder_pre']) && empty ($eventsystem['hide'])) 
      oncreatefolder_pre ($site, $cat, $location, $folder, $user);

    // check if folder exists already
    if (is_dir ($location.$folder))
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-folder-exists-already'][$lang]."</span><br />\n".$hcms_lang['please-try-another-folder-name'][$lang]."\n";
    }
    // check if folder can be created
    elseif (is_dir ($location))
    {
      // create new folder
      $test = @mkdir ($location.$folder, $mgmt_config['fspermission']);

      if ($test != false)
      {
        // remote client
        remoteclient ("save", "abs_path_".$cat, $site, $location, "", $folder, "");

        // define template
        $template =  "default.meta.tpl";

        $folderinfo = getobjectinfo ($site, $location, ".folder", "sys");

        if (!empty ($folderinfo['template']))
        {
          $template = $folderinfo['template'];
        }

        // create folder object
        $folderfile = createobject ($site, $location.$folder, ".folder", $template, $user); 

        if (!empty ($folderfile['result']))
        {
          $errcode = "10263";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|New folder '".$folder_orig."' was created in '".$location_esc."' by user '".$user."'";

          $add_onload = "parent.frames['mainFrame'].location.reload(); ";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-folder-was-created'][$lang]."</span><br />\n";

          // add origin folder name as file parameter
          $filedata = loadfile ($location.$folder, ".folder");

          if ($filedata != "") $filedata = setfilename ($filedata, "name", $folder_orig);
          if ($filedata != "") $savefile = savefile ($location.$folder, ".folder", $filedata);
          else $savefile = false;

          if ($savefile == false)
          {
            $errcode = "10262";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Folder object for '".$folder_orig."' could not be saved in '".$location_esc."' by user '".$user."'";
          }

          $container = $folderfile['container'];
          $site = $folderfile['publication'];
          $location = $folderfile['location'];
          $cat = $folderfile['cat'];
          $contentfile = $folderfile['container'];
          $container_id = $folderfile['container_id'];
          $page_box_xml = $folderfile['container_content'];

          $success = true;
        }
      }
      // directory could not be created due to missing write permission
      else
      {
        $errcode = "10263";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Folder '".$folder_orig."' could not be created in '".$location_esc."' by user '".$user."'";

        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-folder-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
      }
    }
    // location is not a valid directory
    else
    {
      $errcode = "10264";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Folder '".$folder_orig."' could not be created in '".$location_esc."' by user '".$user."'";

      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-folder-could-not-be-created'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
    }

    // eventsystem
    if (!empty ($eventsystem['oncreatefolder_post']) && empty ($eventsystem['hide']) && !empty ($success)) 
      oncreatefolder_post ($site, $cat, $location, $folder, $user);
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
  $result['result'] = $success;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  $result['folder'] = $folder;
  $result['name'] = $folder_orig;
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
// input: publication name [string], location [string], folder name [string], user name [string]
// output: result array

// description:
// This function creates all folders recursively

function createfolders ($site, $location, $folder, $user)
{
  global $eventsystem, $mgmt_config, $cat, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $hcms_lang, $lang;

  // default max length
  if (empty ($mgmt_config['max_digits_filename']) || intval ($mgmt_config['max_digits_filename']) < 1) $mgmt_config['max_digits_filename'] = 236;

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($folder) && accessgeneral ($site, $location, $cat) && strlen ($folder) <= $mgmt_config['max_digits_filename'] && valid_objectname ($user))
  {
    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // deconvertpath location
    $location = deconvertpath ($location, "file");

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // folder exists
    if (is_dir ($location.$folder)) return $result['result'] = true;

    // folder can be created
    $result = createfolder ($site, $location, $folder, $user);
    if (!empty ($result['result'])) return $result;

    // folder cannot be created, create parent folder
    $result = createfolders ($site, dirname ($location), getobject ($location), $user);

    if (!empty ($result['result'])) $result = createfolder ($site, $location, $folder, $user);
    if (!empty ($result['result'])) return $result;
  }
  else return $result['result'] = false;
}

// ---------------------------------------- collectfolders --------------------------------------------
// function: collectfolders ()
// input: publication name [string], location [string], folder name [string]
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
    $scandir = scandir ($location.$folder);

    if ($scandir)
    {
      foreach ($scandir as $subfolder)
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
    }
  }
  else $folder_array = false;

  return $folder_array;
}

// ---------------------------------------- copyfolders --------------------------------------------
// function: copyfolders ()
// input: publication name [string], location (source) [string], new location (destination) [string], folder name [string], user name [string], do not create a copy of the folder if it exist already [boolean] (optional)
// output: result array equal to createfolder

// description:
// This function copies/creates all folders of the source location using mkdir (only directories will be created!). Used by pasteobject function.

function copyfolders ($site, $location, $locationnew, $folder, $user, $no_duplicates=true)
{ 
  global $mgmt_config, $cat, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $hcms_lang, $lang;

  if (valid_publicationname ($site) && valid_locationname ($location) && $locationnew != "" && $folder != "")
  {
    // add slash if not present at the end of the location string
    $location = correctpath ($location);
    $locationnew = correctpath ($locationnew);
 
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

            // define root folder
            $rootfolderold = $rootfoldernew = $thisfolder;

            // if folder exists already
            if (is_dir ($rootlocationnew.$thisfolder))
            {
              // rename root folder and add "-Copy" if it exists already
              if ($no_duplicates == false)
              {
                // reset rootfoldernew name
                $rootfoldernew = $thisfolder."-Copy";

                for ($c=2; $c<=100; $c++)
                { 
                  if (is_dir ($rootlocationnew.$rootfoldernew))
                  {
                    // reset rootfoldernew name
                    $rootfoldernew = $thisfolder."-Copy".$c;
                  }
                  else break;
                }

                // create folder
                $result['result'] = @mkdir ($rootlocationnew.$rootfoldernew, $mgmt_config['fspermission']);

                // remote client
                remoteclient ("save", "abs_path_".$cat, $site, $rootlocationnew, "", $rootfoldernew, "");
              }
              // nothing to do since the folder exists already
              else
              {
                $result['result'] = true;
              }
            }
            // the folder does not exist in the new location and need to be created
            else
            {
              // create folder
              $result['result'] = @mkdir ($rootlocationnew.$rootfoldernew, $mgmt_config['fspermission']);

              // remote client
              remoteclient ("save", "abs_path_".$cat, $site, $rootlocationnew, "", $rootfoldernew, "");
            }
          }
          // create all subfolders inside the root folder
          else 
          {
            // define new location
            $thislocation = str_replace ($rootlocationold.$rootfolderold, $rootlocationnew.$rootfoldernew, $thislocation);

            // create folder
            $result['result'] = @mkdir ($thislocation.$thisfolder, $mgmt_config['fspermission']);

            // remote client
            remoteclient ("save", "abs_path_".$cat, $site, $thislocation, "", $thisfolder, ""); 
          }
        }
        else $result['result'] = false;

        if (empty ($result['result'])) break;
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
// input: publication name [string], location [string], folder name [string], user name [string]
// output: result array

// description:
// This function removes a folder. The folder must be empty in order to be removed from the system.

function deletefolder ($site, $location, $folder, $user)
{
  global $eventsystem, $mgmt_config, $cat, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $hcms_lang, $lang;
 
  // initialize
  $error = array();
  $success = false;
  $add_onload = "";
  $show = "";

  // set default language
  if (empty ($lang)) $lang = "en";

  $folder = trim ($folder);

  // folder name
  if (specialchr ($folder, ".-_~") == true) $folder = createfilename ($folder);

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($folder) && accessgeneral ($site, $location, $cat) && $user != "")
  {
    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // deconvertpath location
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, $cat);

    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location);

    // check given folder name
    if ($folder == ".folder")
    {
      $folder = getobject ($location);
      $location = getlocation ($location);
    }

    // check permissions
    if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
    {
      $ownergroup = accesspermission ($site, $location, $cat);
      $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);
      
      if (empty ($setlocalpermission['root']) || empty ($setlocalpermission['folderdelete']))
      {
        $result = array();
        $result['result'] = false;
        $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
        return $result;
      }
    }

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // eventsystem
    if (!empty ($eventsystem['ondeletefolder_pre']) && empty ($eventsystem['hide'])) 
      ondeletefolder_pre ($site, $cat, $location, $folder, $user);

    // folder exists
    if (!empty ($folder) && is_dir ($location.$folder))
    {
      // check folder for objects
      $is_empty = is_emptyfolder ($location.$folder);

      if ($is_empty == true)
      {
        // delete folder object
        if (is_file ($location.$folder."/.folder")) $result_delete = deleteobject ($site, $location.$folder, ".folder", $user);

        // delete directory
        $result_delete['result'] = deletefile ($location, $folder, false); 
  
        if (!empty ($result_delete['result']))
        {
          // remove folder from workflow and group folder access
          deletegroupfolder ($site, $cat, $location.$folder, $user);
          deleteworkflowfolder ($site, $cat, $location.$folder, $user);

          // remote client
          remoteclient ("delete", "abs_path_".$cat, $site, $location, "", $folder, "");
  
          $add_onload = "parent.frames['mainFrame'].location.reload(); ";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-folder-was-removed'][$lang]."</span><br />\n";

          $success = true;

          // log delete
          $errcode = "00110";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|Folder ".$location_esc.$folder." has been deleted by user '".$user."' (".getuserip().")";
        }
        // folder doesn't exist and/or write permission is missing
        else
        {
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-folder-could-not-be-removed'][$lang]."</span><br />".$hcms_lang['the-folder-doesnt-exist'][$lang]." ".$hcms_lang['or-you-have-no-write-permission'][$lang]."\n";
        }
      }
      else
      {
        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-folder-could-not-be-removed'][$lang]."</span><br />".$hcms_lang['the-folder-still-holds-items-please-delete-these-items'][$lang]."\n";
      }
    }
    // folder doesn't exist
    else
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-folder-doesnt-exist'][$lang]."</span>\n";
    }

    // eventsystem
    if (!empty ($eventsystem['ondeletefolder_post']) && empty ($eventsystem['hide']) && !empty ($success)) 
      ondeletefolder_post ($site, $cat, $location, $folder, $user);
  }

  // save log
  savelog (@$error); 
 
  // return results
  $result = array();
  if (!empty ($success)) $folder = "";
  $result['result'] = $success;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  $result['folder'] = $folder;

  return $result;
}

// ---------------------------------------- renamefolder --------------------------------------------
// function: renamefolder()
// input: publication name [string], location [string], folder name [string], new folder name [string], user name [string]
// output: result array

// description:
// This function renames a folder

function renamefolder ($site, $location, $folder, $foldernew, $user)
{
  global $eventsystem, $mgmt_config, $cat, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $hcms_lang, $lang;

  // default max length
  if (empty ($mgmt_config['max_digits_filename']) || intval ($mgmt_config['max_digits_filename']) < 1) $mgmt_config['max_digits_filename'] = 236;

  // initialize
  $error = array();
  $success = false;
  $add_onload = "";
  $show = "";
  $foldernew_orig = "";

  // set default language
  if (empty ($lang)) $lang = "en";

  // replace .recycle extension since it is used to mark objects in the recycle bin
  $foldernew = trim ($foldernew);
  if (substr ($foldernew, -8) == ".recycle") $foldernew = str_replace (".recycle", "_recycle", $foldernew);

  // keep new folder name
  $foldernew_orig = $foldernew;

  // test if folder name includes special characters
  if (specialchr ($folder, ".-_~") == true) $folder = createfilename ($folder);
  $foldernew = createfilename ($foldernew);

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($folder) && valid_objectname ($foldernew) && strlen ($foldernew) <= $mgmt_config['max_digits_filename'] && valid_objectname ($user))
  {
    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location); 

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // deconvertpath location
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, $cat);

    // check permissions
    if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
    {
      $ownergroup = accesspermission ($site, $location, $cat);
      $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat); 
      
      if (empty ($setlocalpermission['root']) || empty ($setlocalpermission['folderrename']))
      {
        $result = array();
        $result['result'] = false;
        $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
        return $result;
      }
    }

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
      if (!empty ($eventsystem['onrenamefolder_pre']) && empty ($eventsystem['hide'])) 
        onrenamefolder_pre ($site, $cat, $location, $folder, $foldernew, $user);

      // if inheritance of components is used, every child publication must also be updated
      $site_array = array();
      $site_array[0] = $site;

      if (strtolower ($cat) == "comp")
      {
        // load publication inheritance setting
        $inherit_db = inherit_db_read ();

        if (sizeof ($inherit_db) > 0)
        {
          $child_array = inherit_db_getchild ($inherit_db, $site);

          if ($child_array != false)
          {
            $site_array = array_merge ($site_array, $child_array);
          }
        }
      }

      // loop for each site
      foreach ($site_array as $site)
      {
        // publication management config
        if (valid_publicationname ($site) && !isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
        {
          require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
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
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Folder ".$folder_curr." could not be renamed to ".$folder_new;
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
          // save new folder name incl. special characters as file parameter
          $filedata = loadfile ($location.$foldernew, ".folder");
          if ($filedata != "") $filedata = setfilename ($filedata, "name", $foldernew_orig);
          if ($filedata != "") $result = savefile ($location.$foldernew, ".folder", $filedata);
          else $result = false;

          if ($result == false)
          {
            $errcode = "10265";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Folder name '".$foldernew_orig."' could not be saved for ".$location_esc.$foldernew;
          }

          // remote client
          remoteclient ("rename", "abs_path_".$cat, $site, $location, "", $folder, $foldernew); 

          // eventsystem
          if (!empty ($eventsystem['onrenamefolder_post']) && empty ($eventsystem['hide'])) 
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

          // information log entry
          $errcode = "00103";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|Folder '".$location_esc.$folderold."' has been renamed to '".$location_esc.$foldernew."' by user '".$user."'";

          $success = true;
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

  // save log
  savelog (@$error);

  // return results
  $result = array();
  $result['result'] = $success;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  $result['folder'] = $folder;
  $result['name'] = $foldernew_orig;

  return $result;
}

// ---------------------------------------- correctcontainername --------------------------------------------
// function: correctcontainername()
// input: container ID [string]
// output: corrected name / false on error

// description:
// This function adds zeros to the container ID to create the correct file name of the content container.

function correctcontainername ($container_id)
{
  if ($container_id > 0)
  {
    $contentcountlen = strlen ($container_id);
    $zerolen = 7 - $contentcountlen;
    $zerostring =  "";

    for ($i = 1; $i <= $zerolen; $i++)
    {
      $zerostring = $zerostring."0";
    }

    // correct content container id file name
    return $zerostring.$container_id;
  }
  else return false;
}

// ------------------------------------------ contentcount -------------------------------------------
// function: contentcount()
// input: user name [string]
// output: true/false

// description:
// This functions reads and defines the contentcount for the creation of a new container
// It will unlock the contentcoount file if it has been locked by a user

function contentcount ($user)
{
  global $mgmt_config;

  // initialize
  $error = array();
  $contentcount = false;
  $lock = uniqid();

  // load contentcount file and add the new page
  $filedata = loadlockfile ($lock, $mgmt_config['abs_path_data'], "contentcount.dat", 5);

  // try to restore contentcount from database
  if (empty ($filedata))
  {
    $temp = rdbms_externalquery ("SELECT MAX(id) AS contentcount FROM object");

    if (!empty ($temp[0]['contentcount']))
    {
      $filedata = intval ($temp[0]['contentcount']);

      if ($filedata > 0)
      {
        // add 100 for safety reasons
        $filedata = $filedata + 100;

        $errcode = "00887";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|warning|".$errcode."|contentcount.dat was recreated by the database with max id='".$filedata."' for user '".$user."'";
      }
    }
  }

  // define new contentcount / container ID
  if (!empty ($filedata) || strval ($filedata) == "0")
  {
    $contentcount = intval ($filedata);

    // add 1 to contentcount
    $contentcount++;

    // write to file
    $test = savelockfile ($lock, $mgmt_config['abs_path_data'], "contentcount.dat", $contentcount);

    // on error
    if ($test == false)
    {
      // reset contentcount
      $contentcount = false;

      // unlock file
      unlockfile ($lock, $mgmt_config['abs_path_data'], "contentcount.dat");

      $errcode = "20885";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|contentcount failure (contentcount.dat could not be saved for user '".$user."')";
    }
  }
  // contentcount could not be loaded
  else
  {
    // unlock file
    unlockfile ($lock, $mgmt_config['abs_path_data'], "contentcount.dat");

    $errcode = "20886";
    $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|contentcount failure (contentcount.dat could not be loaded for user '".$user."')";
  }

  // save log
  savelog ($error);

  return $contentcount;
}

// ---------------------------------------- createobject --------------------------------------------
// function: createobject()
// input: publication name [string], location [string], object name without file extension [string], template name [string], user name [string]
// output: result array

// description:
// This function creates a new page or component.

function createobject ($site, $location, $page, $template, $user)
{
  global $eventsystem, $mgmt_config, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $hcms_lang, $lang;

  // default max length
  if (empty ($mgmt_config['max_digits_filename']) || intval ($mgmt_config['max_digits_filename']) < 1) $mgmt_config['max_digits_filename'] = 236;

  // initialize
  $error = array();
  $success = false;
  $object_exists = false;
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
  if (empty ($lang)) $lang = "en";

  // include hypermailer class
  if (!class_exists ("HyperMailer")) include_once ($mgmt_config['abs_path_cms']."function/hypermailer.class.php");

  // trim
  $page = trim ($page);

  // replace .recycle extension since it is used to mark objects in the recycle bin
  if (substr ($page, -8) == ".recycle") $page = str_replace (".recycle", "_recycle", $page);

  // replace _hcm identifier since it is used to for media files
  if (strpos ($page, "_hcm") > 0) $page = str_replace ("_hcm", "hcm", $page);

  // create valid object file name
  $page_orig = $page;
  $page = createfilename ($page);

  // the max chacracters for an objectpath is 16,000 (due to the database attribute objectpath)
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && accessgeneral ($site, $location, "") && strlen ($page) <= $mgmt_config['max_digits_filename'] && valid_objectname ($template) && valid_objectname ($user))
  {
    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // convert location
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, $cat); 

    //  check if location exists
    if (!is_dir ($location))
    {
      $add_onload = "parent.frames['objFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-create-new-item'][$lang]."</span><br />\n".$hcms_lang['the-location-holding-the-new-object-does-not-exist'][$lang]."\n";
    }

    if ($show == "")
    {
      // check if page is a folder
      if ($page == ".folder")
      {
        // extract folder name
        $page_orig = getobject ($location);
      }
      else
      {
        $page_orig = $page;
      }

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
        $mediatype = false;
      }

      // check permissions
      if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
      {
        $ownergroup = accesspermission ($site, $location, $cat);
        $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);
        
        if (empty ($setlocalpermission['root']) || empty ($setlocalpermission['create']))
        {
          $result = array();
          $result['result'] = false;
          $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
          return $result;
        }
      }

      // eventsystem
      if (!empty ($eventsystem['oncreateobject_pre']) && empty ($eventsystem['hide'])) 
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

      if (!empty ($result['result']))
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
          // get contentcount and create the content file name
          $contentcount = contentcount ($user);

          if (empty ($contentcount))
          {
            $add_onload = "parent.frames['objFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['severe-error-occured'][$lang]."</span><br />\n".$hcms_lang['contentcount-failure'][$lang]."\n";
          }
        }
        else
        {
          // get media file name
          if (is_file ($location.$pagefile)) $object_info = getobjectinfo ($site, $location, $page, $user);
          elseif (is_file ($location.$pagename)) $object_info = getobjectinfo ($site, $location, $pagename, $user);

          if (!empty ($object_info['media'])) $mediafile = $object_info['media'];
          else $mediafile = "";

          $add_onload = "parent.frames['objFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-exists-already'][$lang]."</span><br />\n".$hcms_lang['please-try-another-name'][$lang]."\n";
          $object_exists = true;
        }

        if ($show == "")
        {
          // create the name of the content file based on the unique content count value
          $container_id = correctcontainername ($contentcount);
          $contentfile = $container_id.".xml";

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

            // date 
            $date = date ("Y-m-d H:i:s", time());

            // write <hyperCMS> content in xml structure
            if ($page_box_xml != false) $page_box_xml = setcontent ($page_box_xml, "<hyperCMS>", "<contentcontainer>", $contentfile, "", "");
            if ($page_box_xml != false) $page_box_xml = setcontent ($page_box_xml, "<hyperCMS>", "<contentxmlschema>", "object/".$cat, "", "");
            if ($page_box_xml != false) $page_box_xml = setcontent ($page_box_xml, "<hyperCMS>", "<contentorigin>", $contentorigin, "", "");
            if ($page_box_xml != false) $page_box_xml = setcontent ($page_box_xml, "<hyperCMS>", "<contentobjects>", $contentorigin."|", "", "");
            if ($page_box_xml != false && substr ($user, 0, 4) != "sys:") $page_box_xml = setcontent ($page_box_xml, "<hyperCMS>", "<contentuser>", $user, "", "");
            if ($page_box_xml != false) $page_box_xml = setcontent ($page_box_xml, "<hyperCMS>", "<contentcreated>", $date, "", "");
            if ($page_box_xml != false) $page_box_xml = setcontent ($page_box_xml, "<hyperCMS>", "<contentdate>", $date, "", "");
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
            $add_onload = "parent.frames['objFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['severe-error-occured'][$lang]."</span><br />\n".$hcms_lang['could-not-create-new-content-container'][$lang]."\n";

            $errcode = "20887";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|New object could not be created by user '".$user."' (".$site.", ".$location_esc.", ".$page.") since the object XML schema could not be loaded"; 
          }

          // ------------------------ add record in link management file --------------------------------

          if ($page_box_xml != false && $workflow_save != false && $mgmt_config[$site]['linkengine'] == true)
          {
            // define new link database record
            $object = convertpath ($site, $location.$pagename, $cat)."|";

            $link_db_record = "\n".$contentfile.":|".$object.":|";

            // append new record into link management file (faster than function link_db_insert)
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
                $result_unlock = false;
                $result['message'] = $hcms_lang['could-not-insert-into-link-management'][$lang];
                $auth = false;

                $errcode = "10885";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Link index file ".$site.".link.dat could not be unlocked"; 
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
              $add_onload = "parent.frames['objFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
              $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-insert-into-link-management'][$lang]."</span><br />\n".$hcms_lang['link-management-file-is-missing-or-you-do-not-have-write-permissions'][$lang]."\n";
            }
          }
          else $link_db_append = true;

          // save working xml content container and published container
          if ($link_db_append != false && $show == "")
          { 
            // create container directory and get container path
            $container_location = getcontentlocation ($container_id, 'abs_path_content');
            
            /* deprected since function getcontentlocation created the container directory
            $test = mkdir ($container_location, $mgmt_config['fspermission']);
            */

            if (!is_dir ($container_location))
            {
              $errcode = "10881";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Directory for container ".$contentfile." is missing";
            }

            // save container initally since savecontainer only saves data to existing containers
            $test = savecontainer ($container_id, "work", $page_box_xml, $user, true);

            if ($test == false)
            {
              $errcode = "10882";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Working container ".$contentfile.".wrk could not be saved";
            }

            // save container initally since savecontainer only saves data to existing containers
            $test = savecontainer ($container_id, "published", $page_box_xml, $user, true);

            if ($test == false)
            {
              $errcode = "10883";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Published container ".$contentfile." could not be saved";
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
                // revert modifications
                // remove workflow
                if (is_file ($mgmt_config['abs_path_data']."workflow/".$site."/".$contentfile))
                {
                  deletefile ($mgmt_config['abs_path_data']."workflow/".$site."/", $contentfile, false);
                }
                
                // remove container from link management database
                if (!empty ($link_db_append))
                {
                   $link_db = link_db_load ($site, $user);
                   $link_db = link_db_delete ($site, $link_db, $contentfile);
                   link_db_save ($site, $link_db, $user);
                }

                // remove content containers
                if (is_dir ($container_location))
                {
                  deletefile (getlocation ($container_location), getobject ($container_location), true);
                }

                $add_onload = "parent.frames['objFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
                $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-create-new-item'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";

                // log entry
                $errcode = "10101";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|New object '".$pagefile."' with container ID '".$container_id."' could not be created by user '".$user."' (".$site.", ".$location_esc.", ".$page.") due to missing write permissions for the object file";
              }
              // on success
              else
              {
                // relational DB connectivity
                if ($mgmt_config['db_connect_rdbms'] != "")
                {
                  rdbms_createobject ($container_id, $contentorigin, $templatefile, $mediafile, $contentfile, $user); 
                } 

                $page = $pagefile;

                $add_onload = "parent.frames['objFrame'].location='page_view.php?ctrlreload=yes&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&pagename=".url_encode($page_orig)."'; ";
                $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-created'][$lang]."</span><br />\n".$hcms_lang['now-you-can-edit-the-content'][$lang]."\n";

                // information log entry
                $errcode = "00102";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|New object created by user '".$user."' (".$site.", ".$location_esc.", ".$page.")"; 

                // remote client
                remoteclient ("save", "abs_path_".$cat, $site, $location, "", $pagefile, ""); 

                // eventsystem
                if (!empty ($eventsystem['oncreateobject_post']) && empty ($eventsystem['hide']) && !empty ($success)) 
                  oncreateobject_post ($site, $cat, $location, $page, $template, $user);

                // notification
                notifyusers ($site, $location, $page, "oncreate", $user);

                $success = true;
              }
            }
            else
            {
              $add_onload = "parent.frames['objFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
              $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-create-new-content-container'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";

              // log entry
              $errcode = "10102";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|New object could not be created by user '".$user."' (".$site.", ".$location_esc.", ".$page.") due to missing write permissions for the container"; 
            }
          }
          // if user has no access to the workflow or link management failed
          else
          {
            $add_onload = "parent.frames['objFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-create-new-item'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-workflow-access-permissions'][$lang]."\n";

            // log entry
            $errcode = "30102";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|New object could not be created by user '".$user."' (".$site.", ".$location_esc.", ".$page.") due to missing workflow access permissions"; 
          } 
        } 
      }
      else
      {
        $add_onload = "parent.frames['objFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['you-selected-no-template'][$lang]."</span><br />\n".$hcms_lang['please-select-a-template'][$lang]."\n";

        // log entry
        $errcode = "20211";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|New object could not be created by user '".$user."' (".$site.", ".$location_esc.", ".$page.")  due to a missing template";
      }
    }
  }
  // only report error if basic input has not been provided
  elseif ($site != "" && $location != "" && $page != "" && $user != "")
  {
    $add_onload = "parent.frames['objFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; ";
    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['required-input-is-missing'][$lang]."</span><br />\n".$hcms_lang['please-fill-in-a-name'][$lang]."\n";

    // log entry
    $errcode = "20212";
    $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|New object could not be created by user '".$user."' (".$site.", ".$location.", ".$page.") due to wrong or missing input or permissions (accessgeneral=".accessgeneral ($site, $location, "").")";
  } 

  // save log
  savelog (@$error);

  // return results
  $result = array();
  $result['result'] = $success;
  $result['add_onload'] = @$add_onload;
  $result['message'] = @$show;
  $result['object_exists'] = $object_exists;
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
// input: publication name [string], destination location [string], category [page,comp], uploaded file (array as defined by PHP autoglobale $_FILES) [array], unzip/zip [%,unzip,zip], object name (only for media file update of existing object) [string], 
//        create only a new thumbnail from the media file [1,0] (optional), resize image [percentage,null] (optional), image resize percentage value [integer] (optional), user name [string] (optional), check for duplicates [boolean] (optional), 
//        overwrite existing files [boolean] (optional), versioning of file [boolean] (optional), name of zip file [string] (optional), number of files to be compressed [integer] (optional), create media files in the background [boolean] (optional)
// output: result array
// requires: config.inc.php, $pageaccess, $compaccess, $hiddenfolder, $localpermission
 
// description:
// This function manages all file uploads, like unzip files, zip a collection of files, create media objects and resize images.
// The container name will be extracted from the media file name for updating an existing multimedia file.

function uploadfile ($site, $location, $cat, $global_files, $page="", $unzip="", $createthumbnail=0, $imageresize="", $imagepercentage="", $user="sys", $checkduplicates=true, $overwrite=false, $versioning=false, $zipfilename="", $zipfilecount=0, $createmedia_in_background=false)
{
  global $mgmt_config, $mgmt_uncompress, $mgmt_compress, $mgmt_imagepreview, $mgmt_mediapreview, $mgmt_mediaoptions, $mgmt_imageoptions, $mgmt_maxsizepreview, $mgmt_parser, $eventsystem,
         $pageaccess, $compaccess, $hiddenfolder, $localpermission, $hcms_lang, $lang;

  // initialize
  $error = array();
  $show = "";
  $show_command = "";
  $media_update = "";
  $result = array();
  $result['result'] = false;

  // write and close session (important for non-blocking: any page that needs to access a session now has to wait for the long running script to finish execution before it can begin)
  if (session_id() != "")
  {
    $session_id = session_id();
    session_write_close();
  }
  else $session_id = createuniquetoken ();

  // set default language
  if (empty ($lang)) $lang = "en";

  if (valid_publicationname ($site) && valid_locationname ($location) && $cat != "" && accessgeneral ($site, $location, $cat) && is_array ($global_files) && !is_tempfile ($global_files['Filedata']['name']) && valid_objectname ($user))
  {
    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_comp']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // convert location
    $location = deconvertpath ($location, "file");
    $location_esc = $result['location_esc'] = convertpath ($site, $location, $cat);

    // log entry
    $errcode = "00011";
    $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|uploadfile(".$site.", ".$location_esc.", ".$page.", ".$cat."): new multimedia file upload '".$global_files['Filedata']['name']."' by user '".$user."'";
 
    // write log
    savelog (@$error);

    // result
    $result['publication'] = $site;

    // check local upload permission
    $ownergroup = accesspermission ($site, $location, $cat);

    if ($ownergroup != false) $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);
    else $setlocalpermission = false;

    if (empty ($setlocalpermission['root']) || $setlocalpermission['root'] != 1 || empty ($setlocalpermission['upload']) || $setlocalpermission['upload'] != 1)
    {
      $errcode = "30501";
      $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile failed: no permissions to upload file to '".$location."' for user '".$user."'";

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
    }

    // define variables
    $updir = $location; //absolute path to where files are uploaded, no trailing slash

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
        $errcode = "20502";
        $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile failed: the file could not be obtained from the source";

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
      $filedata = @file_get_contents ($global_files['Filedata']['tmp_name']);

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
        $errcode = "20502";
        $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile failed: the file could not be obtained from the source";

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
        $errcode = "10503";
        $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile failed: the file could not be obtained from the source";

        // write log
        savelog (@$error);

        $result['header'] = "HTTP/1.1 501 Internal Server Error";
        $result['message'] = $hcms_lang['file-could-not-be-downloaded'][$lang];

        return $result;
      }
    }

    // eventsystem
    if (!empty ($eventsystem['onfileupload_pre'])) onfileupload_pre ($site, $cat, $location, $global_files['Filedata']['name'], $user);

    // error during file upload
    if (!empty ($global_files['Filedata']['error']) && $global_files['Filedata']['error'] != UPLOAD_ERR_OK)
    {
      $errcode = "20504";
      $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile failed: the file '".$location_esc.$global_files['Filedata']['name']."' could not be saved or only partialy-saved. Please check upload_max_filesize in your php.ini. Upload input details: temp-name:".$global_files['Filedata']['tmp_name']. ", file-name:".$global_files['Filedata']['name'].", file-size:".$global_files['Filedata']['size'].", file-type:".$global_files['Filedata']['type'];

      // write log
      savelog (@$error);

      $result['header'] = "HTTP/1.1 501 Internal Server Error";
      $result['message'] = $hcms_lang['file-could-not-be-saved-or-only-partialy-saved'][$lang];
      return $result;
    }

    // error if no file is selected
    if (empty ($global_files['Filedata']['name']) || empty ($global_files['Filedata']['tmp_name']))
    {
      $errcode = "20505";
      $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile failed: no file selected for upload";

      // write log
      savelog (@$error);

      $result['header'] = "HTTP/1.1 502 Internal Server Error";
      $result['message'] = $hcms_lang['no-file-selected-to-upload'][$lang];
      return $result;
    }

    // default max length
    if (empty ($mgmt_config['max_digits_filename']) || intval ($mgmt_config['max_digits_filename']) < 1) $mgmt_config['max_digits_filename'] = 236;

    // error if file name is too long
    if (strlen ($global_files['Filedata']['name']) > $mgmt_config['max_digits_filename'])
    {
      $errcode = "20506";
      $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile failed: the file name '".$location_esc.$global_files['Filedata']['name']."' has too many digits";

      // write log
      savelog (@$error);

      $result['header'] = "HTTP/1.1 503 Internal Server Error";
      $result['message'] = str_replace ("%maxdigits%", $mgmt_config['max_digits_filename'], $hcms_lang['the-file-name-has-more-than-maxdigits-digits'][$lang]);
      return $result;
    }

    // create valid file name
    $filename_new = createfilename ($global_files['Filedata']['name']);

    // if file exists and is not an update
    if (!empty ($filename_new) && is_file ($location.$filename_new) && $media_update == "")
    {
      // overwrite existing multimedia file
      if ($overwrite == true)
      {
        // get media file name
        $object_info = getobjectinfo ($site, $location, $filename_new, $user);

        if (!empty ($object_info['media'])) $media_update = $object_info['media'];
      }
      // error if file exists
      else
      {
        $result['header'] = "HTTP/1.1 504 Internal Server Error";
        $result['message'] = $hcms_lang['the-file-you-are-trying-to-upload-already-exists'][$lang];
        return $result;
      }
    }

    // error if file is to big
    if (!empty ($mgmt_config['maxfilesize']) && $mgmt_config['maxfilesize'] > 0)
    {
      // convert size limit from MB to bytes
      $maxfilesize = $mgmt_config['maxfilesize'] * 1024 * 1024;

      if (filesize ($global_files['Filedata']['tmp_name']) > $maxfilesize)
      {
        $errcode = "20508";
        $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile failed: the file '".$location_esc.$global_files['Filedata']['name']."' is too big (max. ".$mgmt_config['maxfilesize'].")";

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

    // check the md5 hash with the one in the database
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
            $links[] = '<a href="javascript:void(0);" onclick="hcms_openWindow(\''.$mgmt_config['url_path_cms'].'frameset_content.php?site='.$site.'&ctrlreload=yes&cat=comp&location='.urlencode($dup_location).'&page='.urlencode($dup_object).'\', \''.uniqid().'\', \'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes\', '.windowwidth ("object").', '.windowheight ("object").');">'.$dup_name.'</a>';
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
    if ($unzip == "unzip" && is_array ($mgmt_uncompress))
    {
      $check_unzip = false;

      // extension of uploaded file
      $file_ext = strtolower (strrchr ($global_files['Filedata']['name'], "."));

      reset ($mgmt_uncompress);
 
      foreach ($mgmt_uncompress as $extension => $temp)
      {
        if (substr_count ($extension, $file_ext) > 0) $check_unzip = true;
      }
    }

    // compressed file that holds multimedia files
    if (!empty ($check_unzip))
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
    // collect multimedia files and pack files into ZIP
    elseif ($unzip == "zip" && is_array ($mgmt_compress) && sizeof ($mgmt_compress) > 0)
    {
      // temporary directory for collecting files
      $temp_dir = $mgmt_config['abs_path_temp']."zip_".$session_id."/";

      // create temporary directory for extraction
      if (!is_dir ($temp_dir)) $test = @mkdir ($temp_dir, $mgmt_config['fspermission']);
      else $test = true;

      // copy files to temporary directory
      if ($test == true)
      {
        if (!$is_remote_file)
        {
          $result_upload = @move_uploaded_file ($global_files['Filedata']['tmp_name'], $temp_dir.$global_files['Filedata']['name']);
        }

        if ($is_remote_file || empty ($result_upload))
        {
          $result_upload = @rename ($global_files['Filedata']['tmp_name'], $temp_dir.$global_files['Filedata']['name']);

          if ($result_upload == false)
          {
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-file-you-are-trying-to-upload-couldnt-be-copied-to-the-server'][$lang]."</span>\n";
          }
        }

        // on error
        if (empty ($result_upload))
        {
          $errcode = "20510";
          $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile failed: the new file '".$global_files['Filedata']['name']."' could not be copied to the server";

          // write log
          savelog (@$error);
        }
      }

      // ---------------------- collect multimedia files for compression ----------------------
      if (intval ($zipfilecount) > 0 && is_dir ($temp_dir) && !is_emptyfolder ($temp_dir))
      {
        // count files (excluding . and ..)
        $filescount = count (scandir ($temp_dir)) - 2;

        // ZIP files
        if ($filescount >= intval ($zipfilecount))
        {
          // ZIP file name
          if ($zipfilename != "") $zipfilename = createfilename ($zipfilename).".zip";
          else $zipfilename = getobject ($location).".zip";

          // Windows
          if ($mgmt_config['os_cms'] == "WIN")
          { 
            $cmd = "cd \"".shellcmd_encode ($location)."\" & ".$mgmt_compress['.zip']." -r -0 \"".shellcmd_encode ($temp_dir.$zipfilename)."\" ".shellcmd_encode ($temp_dir);
            $cmd = str_replace ("/", "\\", $cmd);
          }
          // UNIX
          else $cmd = "cd \"".shellcmd_encode ($temp_dir)."\" ; ".$mgmt_compress['.zip']." -r -0 \"".shellcmd_encode ($temp_dir.$zipfilename)."\" *";

          // compress files to ZIP format
          @exec ($cmd, $output, $errorCode);

          // errors during compressions of files
          if ($errorCode && is_array ($output))
          {
            $error_message = implode ("\t", $output);
            $error_message = str_replace ($mgmt_config['abs_path_temp'], "/", $error_message);

            $errcode = "10645";
            $error[] = $mgmt_config['today']."|hypercms_media.inc.php|error|".$errcode."|Execution of zip (code:".$errorCode.", command:".$cmd.") failed for '".$filename."' \t".$error_message;

            // save log
            savelog (@$error);
          }
          // create multimedia object
          else
          {
            $result_createobject = createmediaobject ($site, $location, $zipfilename, $temp_dir.$zipfilename, $user);

            // on success, add location
            if (!empty ($result_createobject['result']))
            {
              $show = $hcms_lang['the-object-was-created-successfully'][$lang];
              $result_createobject['object'] = $location_esc.$result_createobject['object'];
              $result['container_id'] = $result_createobject['container_id'];
            }
            // on error
            else
            {
              $errcode = "20509";
              $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile failed: the file '".$zipfilename."' could not be created by createmediaobject (".strip_tags ($result_createobject['message']).")";

              $show = $result_createobject['message'];
            }
          }

          // remove temp files
          deletefile ($mgmt_config['abs_path_temp'], "zip_".$session_id, 1);
        }
      }
    }
    // ---------------------- standard multimedia file upload in pages ----------------------
    elseif ($cat == "page")
    {
      // move uploaded file
      if (!$is_remote_file)
      {
        $result_save = @move_uploaded_file ($global_files['Filedata']['tmp_name'], $location.$global_files['Filedata']['name']);
      }

      // save file from URL or if file has already been saved in the temp directory (WebDAV saves files in temp directory)
      if ($is_remote_file || empty ($result_save))
      {
        $result_save = @rename ($global_files['Filedata']['tmp_name'], $location.$global_files['Filedata']['name']);
      }

      // on error
      if (empty ($result_save))
      {
        $errcode = "20508";
        $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile failed: the page file '".$global_files['Filedata']['name']."' could not be uploaded to the server";

        // write log
        savelog (@$error);

        $show = $result_createobject['message'];
      }

      // eventsystem
      if (!empty ($eventsystem['onfileupload_post'])) onfileupload_post ($site, $cat, $location, $page, "", "", $user);
    }
    // ---------------------- standard multimedia file upload in assets/components ----------------------
    elseif ($cat == "comp")
    {
      // ---------------- create new multimedia object ------------------
      if ($media_update == "")
      {
        // if original image should not be resized
        if ($imageresize != "percentage") $imagepercentage = 0;

        $result_createobject = createmediaobject ($site, $location, $global_files['Filedata']['name'], $global_files['Filedata']['tmp_name'], $user, $imagepercentage, false, true, $createmedia_in_background);

        // on success, add location
        if (!empty ($result_createobject['result']))
        {
          $result['object'] = $result_createobject['object'] = $location_esc.$result_createobject['object'];
          $result['container_id'] = $result_createobject['container_id'];
        }
        // on error
        else
        {
          $errcode = "20511";
          $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile failed: the new file '".$global_files['Filedata']['name']."' could not be created by createmediaobject (".strip_tags ($result_createobject['message']).")";

          // write log
          savelog (@$error);

          $show = $result_createobject['message'];
        }
      }

      // -------------- update existing multimedia object -----------------
      if ($media_update != "")
      {
        // get container id
        $result['container_id'] = $container_id = getmediacontainerid ($media_update);
        $contentfile = $container_id.".xml";

        $result['object'] = $location_esc.$page;

        // update thumbnail file (uploaded file must be of type JPEG)
        if ($createthumbnail == 1)
        {
          // get file name without extension
          $file_name = substr ($media_update, 0, strrpos ($media_update, "."));

          // get the file extension
          $file_ext = strtolower (strrchr ($global_files['Filedata']['name'], "."));

          // temporary directory for extracting files
          $temp_dir = $mgmt_config['abs_path_temp'].$session_id."/";

          if ($file_ext != "")
          {
            // create temporary directory for extraction
            $test = @mkdir ($temp_dir, $mgmt_config['fspermission']);

            if ($test == true)
            {
              // move uploaded file for standard file upload
              if (!$is_remote_file)
              {
                $result_upload = @move_uploaded_file ($global_files['Filedata']['tmp_name'], $temp_dir.$file_name.$file_ext);
              }

              // copy to temporary directory
              if ($is_remote_file || empty ($result_upload))
              {
                $result_upload = @rename ($global_files['Filedata']['tmp_name'], $temp_dir.$file_name.$file_ext);

                if ($result_upload == false)
                {
                  $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-file-you-are-trying-to-upload-couldnt-be-copied-to-the-server'][$lang]."</span>\n";
                }
              }

              // create thumbnail on success
              if ($result_upload == true)
              {
                // add createmedia command to queue
                if (!empty ($createmedia_in_background)) createqueueentry ("execute", $location_esc.$page, date("Y-m-d H:i:s"), 0, "createmedia (\"".$site."\", \"".$temp_dir."\", \"".getmedialocation ($site, $file_name.".jpg", "abs_path_media").$site."/"."\", \"".$file_name.$file_ext."\", \"jpg\", \"thumbnail\", true, true);", $user);
                // create preview
                else $result_createthumb = createmedia ($site, $temp_dir, getmedialocation ($site, $file_name.".jpg", "abs_path_media").$site."/", $file_name.$file_ext, "jpg", "thumbnail", true, true);

                // if thumbnail creation failed use uploaded image as thumbnail image
                if ($result_createthumb == false)
                {
                  @copy ($temp_dir.$media_update, getmedialocation ($site, $file_name.".thumb.jpg", "abs_path_media").$site."/".$file_name.".thumb.jpg");

                  // remote client
                  remoteclient ("save", "abs_path_media", $site, getmedialocation ($site, $file_name.".thumb.jpg", "abs_path_media").$site."/", "", $file_name.".thumb.jpg", "");
                }
              }
              // on error
              else
              {
                $errcode = "20512";
                $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile failed: the thumbnail file '".$global_files['Filedata']['tmp_name']."' for object '".$location_esc.$page."' could not be copied to the server";
      
                // write log
                savelog (@$error);
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
          $thumb_root = getmedialocation ($site, ".hcms.".$media_update, "abs_path_media").$site."/";

          // force a restore if media file has been exported (on any change of the media file it should be restored)
          $mgmt_config['restore_exported_media'] = true;

          // prepare media file (in case it has been exported)
          $temp = preparemediafile ($site, $media_root, $media_update, $user);

          // reset location if restored
          if (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
          {
            $media_root = $temp['location'];
            $media_update = $temp['file'];
          }

          // create version of previous content and media file
          // createversion removes all media derivative files as well
          if (!empty ($versioning)) $createversion = createversion ($site, $media_update);
          else $createversion = true;

          // check if symbolic link
          if (is_link ($media_root.$media_update)) 
          {
            // get the real file path
            $symlinktarget_path = readlink ($media_root.$media_update);
          }
          else
          {
            $symlinktarget_path = $media_root.$media_update;
          }

          // if versioning was successful
          $result_save = false;

          if ($createversion && !empty ($media_root))
          {
            // delete all existing media files of the current version
            deletemediafiles ($site, $media_update, true, true);

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
            $symlinktarget_path = substr_replace ($symlinktarget_path, $file_ext_new, strrpos ($symlinktarget_path , "."));
            // get object name without extension
            $page_nameonly = specialchr_decode (strrev (substr (strstr (strrev ($page), "."), 1)));
            // get converted location
            $location_conv = convertpath ($site, $location, $cat);

            // save new multimedia file
            // move uploaded file for standard file upload
            if (!$is_remote_file)
            {
              $result_save = @move_uploaded_file ($global_files['Filedata']['tmp_name'], $symlinktarget_path);
            }

            // save file from URL or if file has already been saved in the temp directory (WebDAV saves files in temp directory)
            if ($is_remote_file || empty ($result_save))
            {
              // move
              $result_save = @rename ($global_files['Filedata']['tmp_name'], $symlinktarget_path);

              // if move fails try copy and delete
              if ($result_save == false)
              {
                $result_save = @copy ($global_files['Filedata']['tmp_name'], $symlinktarget_path);
                @unlink ($global_files['Filedata']['tmp_name']);
              }
            }

            if ($result_save == false)
            {
              $errcode = "20513";
              $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile failed: the updated file '".$global_files['Filedata']['tmp_name']."' for object '".$location_esc.$page."' could not be copied to the server";
    
              // write log
              savelog (@$error);
            }
          }

          if ($result_save == true)
          {
            // update symbolic link
            if (is_link ($media_root.$media_orig)) 
            {
              unlink ($media_root.$media_orig);
              $symlink = symlink ($symlinktarget_path, $thumb_root.$media_update);

              if (!$symlink)
              {
                $errcode = "10521";
                $error[] = date('Y-m-d H:i')."|hypercms_main.inc.php|error|".$errcode."|uploadfile failed: the symbolic link for '".$media_update."' could not be created";
              }
            }

            // write stats for upload
            if ($container_id != "" && !is_thumbnail ($media_update, false))
            {
              if ($user == "sys") $user_stats = getuserip();
              else $user_stats = $user;

              rdbms_insertdailystat ("upload", $container_id, $user_stats, false);
            }

            // get media size
            $media_size = getmediasize ($symlinktarget_path);

            if (!empty ($media_size['width']) && !empty ($media_size['height']))
            {
              $imagewidth = round ($media_size['width'], 0);
              $imageheight = round ($media_size['height'], 0);
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

              foreach ($mgmt_imageoptions as $formatstring => $settingstring)
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
                
                // add createmedia command to queue
                if (!empty ($createmedia_in_background)) createqueueentry ("execute", $location_esc.$page, date("Y-m-d H:i:s"), 0, "createmedia (\"".$site."\", \"".$media_root."\", \"".$media_root."\", \"".$media_update."\", \"\", \"original\", false, true);", $user);
                // convert image
                else createmedia ($site, $media_root, $media_root, $media_update, "", "original", false, true);
              }
            }
            // create preview (thumbnail for images, previews for video/audio files)
            else
            {
              // add createmedia command to queue
              if (!empty ($createmedia_in_background)) createqueueentry ("execute", $location_esc.$page, date("Y-m-d H:i:s"), 0, "createmedia (\"".$site."\", \"".$media_root."\", \"".$media_root."\", \"".$media_update."\", \"\", \"origthumb\", true, true);", $user);
              // create preview
              else createmedia ($site, $media_root, $media_root, $media_update, "", "origthumb", true, true);
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

            // remove face detection data
            $contentdata = loadcontainer ($contentfile, "work", $user);
            $faces = selectcontent ($contentdata, "<text>", "<text_id>", "Faces-JSON");

            if (!empty ($faces) && is_array ($faces))
            {
              $textu['Faces-JSON'] = "";
              $contentdata = settext ($site, $contentdata, $contentfile, $textu, "u", "no", $user, $user);
              if (!empty ($contentdata)) savecontainer ($contentfile, "work", $contentdata, $user);
            }
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

              // relational DB connectivity
              if (!empty ($mgmt_config['db_connect_rdbms']))
              { 
                if (!function_exists ("rdbms_setmedianame")) include_once ($mgmt_config['abs_path_cms']."database/db_connect/".$mgmt_config['db_connect_rdbms']);
                rdbms_setmedianame ($container_id, $media_update);
              }
            }
            else $result_save = false;

            // on success
            if ($result_save == true)
            {
              // rename media object, if file extension has changed
              $result_rename = renameobject ($site, $location, $page, $page_nameonly, $user);

              if (!empty ($result_rename['result']))
              {
                // set new page name
                $page = $pagename = $page_nameonly.$file_ext_new;
                // define new page
                $result['object'] = $location_esc.createfilename ($page);
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
          if (!empty ($eventsystem['onfileupload_post'])) onfileupload_post ($site, $cat, $location, $page, $media_update, $contentfile, $user);

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
    if (empty ($show_command) && !empty ($result['object'])) $show_command = "[".$result['object']."]";
 
    // write log
    savelog (@$error);
    
    // restart session (that has been previously closed for non-blocking procedure)
    if (empty (session_id()) && $session_id != "") createsession();

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
// input: publication name [string], destination location [string], file name [string], path to source multimedia file (uploaded file in temp directory) [string], user name [string], resize original image (100%) by percentage [integer] (optional), 
//        leave file in the source directory and create a symbolic link to the file [boolean] (optional), delete file in the source directory if no symbolic link is used [boolean] (optional), create media files in background [boolean] (optional)
// output: result array

// description:
// This function creates an asset (multimedia object) by reading a given source file. The file name must not match any temp file pattern.
// The metadata template is based on the template of the folder the objects resides in.

function createmediaobject ($site, $location, $file, $path_source_file, $user, $imagepercentage=0, $leavefile=false, $deletefile=true, $createmedia_in_background=false)
{
  global $mgmt_config, $mgmt_imagepreview, $mgmt_mediapreview, $mgmt_mediaoptions, $mgmt_imageoptions, $mgmt_maxsizepreview, $mgmt_mediametadata, 
         $mgmt_parser, $mgmt_imagepreview, $mgmt_uncompress, $hcms_ext, 
         $eventsystem, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $hcms_lang, $lang;

  // initialize
  $error = array();

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($file) && accessgeneral ($site, $location, "comp") && $path_source_file != "" && !is_tempfile ($file))
  {
    if (!valid_objectname ($user)) $user = "sys";

    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_comp']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // deconvert path
    if (substr_count ($path_source_file, "%page%") == 1 || substr_count ($path_source_file, "%comp%") == 1)
      $path_source_file = deconvertpath ($path_source_file, "file");

    if (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1)
      $location = deconvertpath ($location, "file");

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // convert location
    $location_esc = convertpath ($site, $location, "comp");

    if (is_file ($path_source_file))
    {
      // define template
      $template =  "default.meta.tpl";

      $folderinfo = getobjectinfo ($site, $location, ".folder", "sys");

      if (!empty ($folderinfo['template']))
      {
        $template = $folderinfo['template'];
      }

      // create new multimedia object
      $result = createobject ($site, $location, $file, $template, $user);

      // move uploaded file into media repository
      if (!empty ($result['result']) && !empty ($result['mediafile']) && !empty ($result['container_id']) && !empty ($result['container_content']))
      {
        $file = $result['object'];
        $file_name = substr ($result['object'], 0, strrpos ($result['object'], "."));
        $file_ext = strtolower (strrchr ($result['object'], "."));
        $mediafile = $result['mediafile'];
        $container_id = $result['container_id'];
        $container_content = $result['container_content'];

        // log entry
        $errcode = "00101";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|New multimedia file created by user '$user' ($site, $location_esc, $file, $path_source_file, $user)"; 

        // define media location
        $medialocation = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";

        // leave media file and create a symbolic link to the file
        if (!empty ($leavefile))
        {
          // create symbolic link
          $result_move = symlink ($path_source_file, $medialocation.$mediafile);
        }
        // move or copy media file to repository
        else
        {
          // remove source file
          if (!empty ($deletefile))
          {
            // move multimedia file to content media repository
            // case "upload": move uploaded file from temp directory
            $result_move = move_uploaded_file ($path_source_file, $medialocation.$mediafile);

            // case "import": move import file from source directory 
            if (!$result_move) $result_move = rename ($path_source_file, $medialocation.$mediafile);
          }
          // keep source file (copy file)
          else
          {
            $result_move = copy ($path_source_file, $medialocation.$mediafile);
          }
        }

        if ($result_move)
        {
          // write stats for upload
          if (!is_thumbnail ($mediafile, false))
          {
            rdbms_insertdailystat ("upload", $container_id, $user, false);
          }

          // resize original image if requested
          if (!empty ($mediafile) && $imagepercentage > 0 && $imagepercentage <= 200)
          {
            // convert uploaded original images only of given formats
            // note: output rendering must be supported and the render format must be the same format as the original file!
            $media_supported_ext = ".gif.jpg.jpeg.png";
            // get media size
            $media_size = getmediasize ($medialocation.$mediafile);
            // get file extension
            $media_ext = strtolower (strrchr ($mediafile, "."));

            // get new rendering settings for original images and set image options (if given)
            if (substr_count ($media_supported_ext.".", $media_ext.".") > 0 && !empty ($media_size['width']) && !empty ($media_size['height']))
            {
              $imagewidth = round ($media_size['width'] * $imagepercentage / 100, 0);
              $imageheight = round ($media_size['height'] * $imagepercentage / 100, 0);

              if ($imagewidth != "" && $imageheight != "")
              {
                $formats = "";
                reset ($mgmt_imageoptions);

                foreach ($mgmt_imageoptions as $formatstring => $settingstring)
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
                  
                  // add createmedia command to queue
                  if (!empty ($createmedia_in_background)) createqueueentry ("execute", $location_esc.$$file, date("Y-m-d H:i:s"), 0, "createmedia (\"".$site."\", \"".$medialocation."\", \"".$medialocation."\", \"".$mediafile."\", \"".str_replace (".", "", $media_ext)."\", \"original\", false, true);", $user);
                  // convert image
                  else createmedia ($site, $medialocation, $medialocation, $mediafile, str_replace (".", "", $media_ext), "original", false, true);
                }
              }
            }
          }
          // create thumbnail/preview of original media file
          else
          {
            // add createmedia command to queue
            if (!empty ($createmedia_in_background)) createqueueentry ("execute", $location_esc.$file, date("Y-m-d H:i:s"), 0, "createmedia (\"".$site."\", \"".$medialocation."\", \"".$medialocation."\", \"".$mediafile."\", \"\", \"origthumb\", true, true);", $user);
            // create preview
            else createmedia ($site, $medialocation, $medialocation, $mediafile, "", "origthumb", true, true);
          }

          // remote client
          remoteclient ("save", "abs_path_media", $site, $medialocation, "", $mediafile, "");

          // index content
          indexcontent ($site, $medialocation, $mediafile, $container_id, $container_content, $user);

          // encrypt data
          if (is_file ($mgmt_config['abs_path_cms']."encryption/hypercms_encryption.inc.php") && isset ($mgmt_config[$site]['crypt_content']) && $mgmt_config[$site]['crypt_content'] == true)
          {
            $data = encryptfile ($medialocation, $mediafile);
            if (!empty ($data)) savefile ($medialocation, $mediafile, $data);
          } 

          // save in cloud storage
          if (is_file ($medialocation.$mediafile) && function_exists ("savecloudobject")) savecloudobject ($site, $medialocation, $mediafile, $user);

          // eventsystem
          if (!empty ($eventsystem['onfileupload_post'])) onfileupload_post ($site, $result['cat'], $location, $result['object'], $mediafile, $result['container'], $user);
        }
        else
        {
          $errcode = "10501";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|createmediaobject failed to move '".$path_source_file."' to '".getmedialocation ($site, $mediafile, "abs_path_media").$site."/".$mediafile."' or create the symbolic link"; 

          $result['result'] = false;
        }
      }
      else
      {
        $errcode = "10502";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|createmediaobject failed to successfully execute createobject ($site, $location_esc, $file, $template, $user)"; 

        $result['result'] = false;
      }
    }
    else
    {
      $errcode = "10503";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|createmediaobject could not find source file '$path_source_file'"; 
 
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
// input: publication name [string], source location [string], destination location [string], user name [string]
// output: result array with all objects created / false on error

// description:
// This function creates media objects by reading all media files from a given source location (used after unzipfile). 
// The file name must not match any temp file pattern.

function createmediaobjects ($site, $location_source, $location_destination, $user)
{
  global $mgmt_config, $mgmt_imageoptions, $eventsystem, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $hcms_lang, $lang;

  // initialize
  $error = array();

  if (valid_publicationname ($site) && $location_source != "" && valid_locationname ($location_destination))
  {
    $result = array();

    if (!valid_objectname ($user)) $user = "sys";

    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    } 

    // deconvert path
    if (substr_count ($location_source, "%page%") == 1 || substr_count ($location_source, "%comp%") == 1)
      $location_source = deconvertpath ($location_source, "file");

    if (substr_count ($location_destination, "%page%") == 1 || substr_count ($location_destination, "%comp%") == 1)
      $location_destination = deconvertpath ($location_destination, "file");

    // check if destination directory exists
    if (!is_dir ($location_source) || !is_dir ($location_destination)) return false;

    // add slash if not present at the end of the location string
    $location_source = correctpath ($location_source);
    $location_destination = correctpath ($location_destination);

    // open directory
    $scandir = scandir ($location_source);

    if ($scandir)
    {
      foreach ($scandir as $file)
      {
        // skip Mac OS files .DS_Store and ._whatever
        if ($file != '.' && $file != '..' && !is_tempfile ($file))
        {
          // directory
          if (is_dir ($location_source.$file))
          {
            $folder = $folder_new = $file;

            // correct file namens which were encoded by unzip to unicode escape characters
            if (substr_count ($folder_new, "#U") > 0) $folder_new = convert_unicode2utf8 ($folder_new); 

            // check if folder exists already 
            if (!object_exists ($location_destination.$folder_new))
            {
              // create folder
              $createfolder = createfolder ($site, $location_destination, $folder_new, $user);
            }
            else
            {
              // set folder values
              $createfolder['result'] = true;
              $createfolder['folder'] = createfilename ($folder_new);
            }

            // create objects
            if (!empty ($createfolder['result']))
            {
              $result = createmediaobjects ($site, $location_source.$folder."/", $location_destination.$createfolder['folder']."/", $user);
            }
            else
            {
              $errcode = "10511";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|createfolder failed to create '".convertpath ($site, $location_destination, "").$folder_new;
            }
          }
          // file
          elseif (is_file ($location_source.$file))
          {
            $objectname = $file;

            // correct file namens which were decoded by unzip
            if (substr_count ($objectname, "#U") > 0) $objectname = convert_unicode2utf8 ($objectname);

            // upload local file
            $global_files = array();
            $global_files['Filedata'] = array();
            $global_files['Filedata']['name'] = $objectname;
            $global_files['Filedata']['tmp_name'] = $location_source.$file;

            $uploadfile = uploadfile ($site, $location_destination, "comp", $global_files, "", "", 0, "", "", $user, false, true, true);

            if (!empty ($uploadfile['result']))
            {
              $result[] = $uploadfile['object'];
            }
            else
            {
              $errcode = "10512";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|uploadfile failed to create '".convertpath ($site, $location_destination, "comp").$objectname;
            }

            /* deprecated since version 9.1.5
            // remove existing object
            if (object_exists ($location_destination.$objectname)) deleteobject ($site, $location_destination, $objectname, $user);

            // create multimedia object
            $createmediaobject = createmediaobject ($site, $location_destination, $objectname, $location_source.$file, $user);

            if (!empty ($createmediaobject['result']))
            {
              $result[] = $createmediaobject['location_esc'].$createmediaobject['object'];
            }
            else
            {
              $errcode = "10512";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|createmediaobject failed to create '".convertpath ($site, $location_destination, "comp").$objectname;
            }
            */
          }
        }
      }
    }

    // error log
    savelog (@$error);

    return $result;
  }
  else return false;
}

// ---------------------- editmediaobject -----------------------------
// function: editmediaobject()
// input: publication name [string], location [string], object name [string], format (file extension w/o dot) [string] (optional), 
//        type of image/video/audio file [thumbnail,origthumb(thumbnail made from original video/audio),original,any other string present in $mgmt_imageoptions] (optional),
//        base64 encoded media data as alternative to server-side conversion using createmedia [string] (optional), user name [string]
// output: result array / false on error (saves original or thumbnail media file of an object, for thumbnail only jpeg format is supported as output), user name

// description:
// This function mainly uses function createmedia to render the objects media, but at the same time takes care of versioning and the object name, if the file extension has been changed.

function editmediaobject ($site, $location, $page, $format="jpg", $type="thumbnail", $mediadata="", $user="")
{
  global $wf_token, $mgmt_config, $mgmt_imagepreview, $mgmt_mediapreview, $mgmt_mediaoptions, $mgmt_imageoptions, $mgmt_maxsizepreview, $mgmt_mediametadata, $hcms_ext, $hcms_lang, $lang;

  // initialize
  $processresult = false;
  $show = "";
  $add_onload = "";
  $cat = "";
  $mediafile_new = "";
  $container = "";

  // set default language
  if (empty ($lang)) $lang = "en";

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($user))
  {
    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    $cat = getcategory ($site, $location);
    $location_esc = convertpath ($site, $location, $cat);

    $pagefile_info = getfileinfo ($site, $page, $cat);
    $pageobject_info = getobjectinfo ($site, $location, $page);
    $container_id = $pageobject_info['container_id'];
    $container = $container_id.".xml";
    $mediafile_orig = $pageobject_info['media'];
    $mediafile_location = getmedialocation ($site, $mediafile_orig, "abs_path_media").$site."/";

    // get the file extension of the old file
    $file_ext_old = strtolower (strrchr ($mediafile_orig, "."));
    // get file name without extension of the old file
    $mediafile_nameonly = strrev (substr (strstr (strrev ($mediafile_orig), "."), 1));
    // get object name without extension
    $page_nameonly = strrev (substr (strstr (strrev ($pagefile_info['name']), "."), 1));

    // create new version of the media file
    // deletes all media derivates as well, except the original media file and JSON image editor file
    $createversion = createversion ($site, $mediafile_orig);

    // write provided base64 encoded media file
    // used by MiniPaint client image editor to save the modified image
    if ($createversion && !empty ($mediafile_orig) && !empty ($mediadata))
    {
      // if symbolic link
      if (is_link ($mediafile_location.$mediafile_orig))
      {
        $target_path = readlink ($mediafile_location.$mediafile_orig);
        $target_location = getlocation ($target_path);
      }
      else $target_location = $mediafile_location;

      // detect file extension by its mime-type
      $file_ext_new = getbase64fileextension ($mediadata);

      // new media file name
      if ($file_ext_old != $file_ext_new)
      {
        $mediafile_new = $mediafile_nameonly.$file_ext_new;

        // delete all existing media files of the current version if the image editor MiniPaint is in use (input parameter mediadata is not empty)
        deletemediafiles ($site, $mediafile_orig, true, false);
      }
      else $mediafile_new = $mediafile_orig;

      // write media data to file
      $result_save = base64_to_file ($mediadata, $target_location, $mediafile_new);

      // create new thumbnail
      createmedia ($site, $mediafile_location, $mediafile_location, $mediafile_new, "jpg", "thumbnail", true, true);
 
      // remote client
      remoteclient ("save", "abs_path_media", $site, $mediafile_location, "", $mediafile_new, "");

      // save to cloud storage
      if (!empty ($result_save) && function_exists ("savecloudobject")) savecloudobject ($site, $mediafile_location, $mediafile_new, $user);
    }
    // render media file of the object
    elseif ($createversion)
    {
      // createmedia save the media file in the cloud object and with the remote client as well
      $mediafile_new = createmedia ($site, $mediafile_location, $mediafile_location, $mediafile_orig, $format, $type, false, true);
    }
    else $mediafile_new = false;

    // if successful
    if ($mediafile_new && $createversion)
    {
      $processresult = true;

      // get the file extension of the new file
      $file_ext_new = strtolower (strrchr ($mediafile_new, "."));

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

          // relational DB connectivity
          if (!empty ($mgmt_config['db_connect_rdbms']))
          { 
            if (!function_exists ("rdbms_setmedianame")) include_once ($mgmt_config['abs_path_cms']."database/db_connect/".$mgmt_config['db_connect_rdbms']);
            rdbms_setmedianame ($container_id, getobject ($mediafile_nameonly).$file_ext_new);
          }
        }
        else $savepage = false;

        // on success
        if ($savepage == true)
        { 
          // rename media object, if file extension has changed
          $rename = renameobject ($site, $location, $page, $page_nameonly, $user);

          if (!empty ($rename['result']))
          {
            $processresult = true;

            // set new page name and media file name
            $page = $rename['object'];
            $mediafile = $mediafile_nameonly.$file_ext_new;

            // remote client
            remoteclient ("save", "abs_path_".$cat, $site, $location, "", $page, "");

            $show = $hcms_lang['the-file-was-processed-successfully'][$lang];

            // add onload
            $add_onload = "parent.frames['controlFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."control_content_menu.php?site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."';\n";
          }
          // on error
          else
          {
            // rollback changes
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
      $show = $hcms_lang['an-error-occurred-in-the-data-manipulation'][$lang]."\n"; 
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
// input: publication name [string], location [string], object name [string], new object name (exkl. extension except for action "file_rename") [string], user name [string], 
//        action [page_delete, page_rename, file_rename, page_paste, page_unpublish], clipboard items [array] (optional)
// output: array

// description:
// This function removes, unpublishs, renames and pastes objects and is used by other functions which works as a shell for this function

function manipulateobject ($site, $location, $page, $pagenew, $user, $action, $clipboard_array=array())
{
  global $wf_token, $eventsystem,
         $mgmt_config, $mgmt_mediaoptions, $mgmt_docoptions, $hcms_ext,
         $pageaccess, $compaccess, $hiddenfolder, $hcms_linking,
         $cat, $hcms_lang, $lang;
 
  // default values for action = paste before loading the clipboard
  $error = array();
  $success = false;
  $method = "";
  $site_source = "";
  $cat_source = "";
  $location_source_esc = "";
  $mediafile_new = "";
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
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

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
    $location = correctpath ($location);

      // eventsystem for paste
    if ($action == "page_paste" && $eventsystem['onpasteobject_pre'] == 1 && empty ($eventsystem['hide']))
      onpasteobject_pre ($site, $cat, $location_source, $location, $page, $user); 
 
    // get object information of the selected item
    if ($action != "page_paste" && valid_objectname ($page))
    {
      // correct object file name
      $page = correctfile ($location, $page, $user);

      // redefine location and object if page is a directory 
      if ($page !== false && $page != ".folder" && is_dir ($location.$page) && is_file ($location.$page."/.folder"))
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
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|loadfile failed for ".$location_esc.$page;
      }
    }
    elseif ($action == "page_paste")
    {
      $clipboard = "";

      // get clipboard entries from session
      if ((empty ($clipboard_array) || !is_array ($clipboard_array)) && !empty ($_SESSION['hcms_temp_clipboard']) && is_array ($_SESSION['hcms_temp_clipboard']) && sizeof ($_SESSION['hcms_temp_clipboard']) > 0)
      {
        $clipboard_array = $_SESSION['hcms_temp_clipboard'];
      }

      // if clipboard entries are available
      if (is_array ($clipboard_array) && sizeof ($clipboard_array) > 0)
      {
        // paste all clipboard items 
        if (sizeof ($clipboard_array) > 0)
        {
          foreach ($clipboard_array as $clipboard)
          {
            // a clipboard array item has the following structure:
            // method|site|cat|location|object|object name|filetype
            list ($method, $site_source, $cat_source, $location_source_esc, $page_source, $pagename, $filetype) = explode ("|", chop ($clipboard));

            $page = $page_source;

            if (empty ($mgmt_config[$site]) || !is_array ($mgmt_config[$site_source])) require ($mgmt_config['abs_path_data']."config/".$site_source.".conf.php");

            $location_source = deconvertpath ($location_source_esc, "file");

            // correct object file name
            $page = correctfile ($location_source, $page, $user);

            // redefine location and object if page is a directory 
            if ($page !== false && $page != ".folder" && is_dir ($location_source.$page) && is_file ($location_source.$page."/.folder"))
            {
              $page = ".folder";
              $location_source = $location_source.$page."/";
              $location_source_esc = $location_source_esc.$page."/";
            }

            // check if object may be pasted in the current publication
            if ($site == $site_source || ($mgmt_config[$site]['inherit_obj'] == true && $parent_array !== false && in_array ($site_source, $parent_array)))
            { 
              // if the category of the object (page or component) is different for cut/copy and paste
              if ($cat_source != $cat) 
              {
                 $add_onload = "";
                 $show = "<span class=\"hcmsHeadline\">".$hcms_lang['it-is-not-possible-to-paste-the-objects-here'][$lang]."</span><br />\n";
              }
              // if the cutted object will be pasted in the source location
              elseif ($page == ".folder" && $method == "cut" && substr_count ($location_esc, $location_source_esc) > 0)
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
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|loadfile failed for ".$location_source.$page;
            }
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
          // trim
          $pagenew = trim ($pagenew);

          // replace .recycle extension since it is used to mark objects in the recycle bin
          if (substr ($pagenew, -8) == ".recycle") $pagenew = str_replace (".recycle", "_recycle", $pagenew);
                  
          // replace _hcm identifier since it is used to for media files
          if (strpos ($pagenew, "_hcm") > 0) $pagenew = str_replace ("_hcm", "hcm", $pagenew);
          
          // remember new object name
          $pagenew_orig = $pagenew;
          $page_orig = $page;

          // create valid file names for
          // existing object name
          if (specialchr ($page, ".-_~") == true) $page = createfilename ($page);
          // new object name
          $pagenew = createfilename ($pagenew);

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
            $pagenew_pub = substr ($pagenew, 0, -4);
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
      elseif ($action == "page_paste" && ($method == "copy" || $method == "linkcopy" || $method == "cut"))
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
          if (empty ($page_sec_info['published'])) $add_ext = ".off";
          else $add_ext = "";

          // if source location = destination location
          if ($location_source == $location && $method == "cut")
          {
            $add_onload = "";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['you-cannot-cut-and-paste-an-item-in-the-same-location'][$lang]."</span><br />\n";
          }
          // define file name after pasting if the file exists already
          elseif ((is_file ($location.$page_sec) || is_file ($location.$page_sec.$add_ext)) && $page_sec != ".folder")
          {
            // define new file name with copy suffix
            $page_sec = $page_sec_info['filename']."-Copy".$page_sec_info['ext'].$add_ext;
            $pagename_sec = $pagename_sec_info['filename']."-Copy".$pagename_sec_info['ext'];

            for ($c=2; $c<=100; $c++)
            {
              if (is_file ($location.$page_sec) || is_file ($location.$page_sec.$add_ext))
              {
                // define new file name with copy suffix
                $page_sec = $page_sec_info['filename']."-Copy".$c.$page_sec_info['ext'].$add_ext;
                $pagename_sec = $pagename_sec_info['filename']."-Copy".$c.$pagename_sec_info['ext'];

                $add_onload = "";
                $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-exists-already'][$lang]."</span><br />\n";
              }
              else
              {
                $add_onload = "";
                $show = "";
                break;
              }
            }
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
          $site_array = array();
          $site_array[0] = $site;

          // if inheritance of components is used, every child publication must also be updated
          if (strtolower ($cat) == "comp")
          {
            // load publication inheritance setting
            $inherit_db = inherit_db_read ();

            if (sizeof ($inherit_db) > 0)
            {
              $child_array = inherit_db_getchild ($inherit_db, $site);

              if ($child_array != false)
              {
                $site_array = array_merge ($site_array, $child_array);
              }
            }
          }

          // loop for each site
          foreach ($site_array as $site)
          {
            // publication management config
            if (valid_publicationname ($site) && !isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
            {
              require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
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
                    $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|link_update failed for ".$contentcontainer;
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
                    $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|link_update failed for ".$contentcontainer;
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
                  $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|link_db_save failed for $site.link.dat";

                  $add_onload = "";
                  $show = "<span class=\"hcmsHeadline\">".$hcms_lang['link-management-error'][$lang]."</span><br />".$hcms_lang['link-management-database-could-not-be-saved'][$lang]."\n";
                }
              }
              else
              {
                link_db_close ($site, $user);

                $errcode = "20103";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|link_db is not array and failed for $site.link.dat";

                $add_onload = "";
                $show = "<span class=\"hcmsHeadline\">".$hcms_lang['link-management-error'][$lang]."</span><br />".$hcms_lang['an-error-occured-while-writing-data-to-the-link-management-database'][$lang]."\n";
              }
            }
            elseif ($link_db == false)
            {
              // unlock file
              link_db_close ($site, $user);

              $errcode = "20301";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|link_db_load failed for ".$site;

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

      // publication management config
      if (valid_publicationname ($site) && !isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
      {
        require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
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
          // insert new content status and object references into content file
          if ($action == "page_delete") 
          {
            $objects = getcontent ($bufferdata, "<contentobjects>");

            // remove object reference in container (except last entry, since the object will be deleted completetly)
            if (!empty ($objects[0]) && substr_count ($objects[0], "|") > 0)
            {
              // remove .recycle directory or file extension from the path since it is not saved in the XML container
              $temp_search = str_replace (".recycle/", "/", $location_esc.$pagename."/");
              $temp_search = substr ($temp_search, 0, -1);

              $objects_str = str_replace ($temp_search."|", "", $objects[0]);
              $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentobjects>", $objects_str, "", "");

              // check for connected objects and reset allow_delete
              if (substr_count ($objects_str, "|") > 0) $allow_delete = false;
            } 

            if ($allow_delete == true)
            {
              $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentstatus>", "deleted", "", "");
            } 
          }
          // insert new objects references into content container
          elseif ($action == "page_rename" || $action == "file_rename") 
          {
            // remove .recycle directory or file extension from the path since it is not saved in the XML container
            $temp_search = str_replace (".recycle/", "/", $location_esc.$pagename."/");
            $temp_search = substr ($temp_search, 0, -1);
            $temp_replace = str_replace (".recycle/", "/", $location_esc.$pagenewname."/");
            $temp_replace = substr ($temp_replace, 0, -1);
            
            $objects = getcontent ($bufferdata, "<contentobjects>");

            if (!empty ($objects[0]) && substr_count ($objects[0], "|") > 0)
            {
              $objects_str = str_replace ($temp_search."|", $temp_replace."|", $objects[0]);
              $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentobjects>", $objects_str, "", "");
            }
          }
          // insert new objects references into content container
          elseif ($action == "page_paste" && $method == "cut") 
          {
            // remove .recycle directory or file extension from the path since it is not saved in the XML container
            $temp_search = str_replace (".recycle/", "/", $location_source_esc.$pagename."/");
            $temp_search = substr ($temp_search, 0, -1);
            $temp_replace = str_replace (".recycle/", "/", $location_esc.$pagename."/");
            $temp_replace = substr ($temp_replace, 0, -1);

            $objects = getcontent ($bufferdata, "<contentobjects>");

            if (!empty ($objects[0]) && substr_count ($objects[0], "|") > 0)
            {
              $objects_str = str_replace ($temp_search."|", $temp_replace."|", $objects[0]);
              $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentobjects>", $objects_str, "", "");
            }
          }

          // date 
          $date = date ("Y-m-d H:i:s", time());

          // update database
          $container_id = substr ($contentfile_self, 0, strpos ($contentfile_self, ".xml")); 
          rdbms_setcontent ($site, $container_id, "", "", $user, true, false);

          // insert user into content file
          if ($user != "sys" && substr ($user, 0, 4) != "sys:") $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentuser>", $user, "", "");

          // insert new date into content file
          $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentdate>", $date, "", "", true);

          // save content container
          if ($bufferdata != "" && $bufferdata != false)
          {
            $test = savecontainer ($contentfile_self_wrk, "work", $bufferdata, $user);

            // final container data
            $containerdata = $bufferdata;

            if ($test == false)
            {
              $errcode = "10105";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|savefile failed for ".$contentfile_self;
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
          $test = deletefile ($location, $page, false); 

          if ($test != false)
          {
            // relational DB connectivity
            if (!empty ($mgmt_config['db_connect_rdbms']))
            { 
              rdbms_deleteobject (convertpath ($site, $location.$page, $cat)); 
            }

            // remote client
            remoteclient ("delete", "abs_path_".$cat, $site, $location, "", $page, "");

            // delete media file derivates
            if (!empty ($mediafile_self)) deletemediafiles ($site, $mediafile_self, true, true);

            // delete thumbnail file (for versions before 5.0)
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

              $test_temp = deletefile ($contentlocation, $contentfile_self, false);

              // rename working container
              if ($test_temp != false) @rename ($contentlocation.$contentfile_self_wrk, $contentlocation.$contentfile_self);

              // delete link file
              if (is_file ($mgmt_config['abs_path_link'].$contentfile_id))
              {
                $test_temp = deletefile ($mgmt_config['abs_path_link'], $contentfile_id, false);

                if ($test_temp != false)
                {
                  // remote client
                  remoteclient ("delete", "abs_path_link", $site, $mgmt_config['abs_path_link'], "", $contentfile_id, "");
                }
                else
                {
                  $errcode = "10119";
                  $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|deletefile failed for ".$mgmt_config['abs_path_link'].$contentfile_id;
                }
              }

              // load language code index file
              $langcode_array = getlanguageoptions ();

              // delete all VTT files of videos
              if ($langcode_array != false)
              {
                foreach ($langcode_array as $code => $language)
                {
                  if (is_file ($mgmt_config['abs_path_view'].$contentfile_id."_".trim($code).".vtt"))
                  {
                    deletefile ($mgmt_config['abs_path_view'], $contentfile_id."_".trim($code).".vtt", 0);
                  }
                }
              }

              // define media location
              $medialocation = getmedialocation ($site, $mediafile_self, "abs_path_media");

              // delete all content and media version files
              $dir_version = $contentlocation;

              if ($dir_version != false)
              {
                $scandir = scandir ($dir_version);
           
                if ($scandir)
                {
                  foreach ($scandir as $entry)
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
          if (!empty ($mgmt_config['db_connect_rdbms']))
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
          $add_onload = "if (parent.frames['mainFrame']) { parent.frames['controlFrame'].location='control_objectlist_menu.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."'; parent.frames['mainFrame'].location.reload(); } else if (parent.frames['objFrame']) { parent.frames['controlFrame'].location='control_content_menu.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&wf_token=".url_encode($wf_token)."'; parent.frames['objFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; }";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-deleted'][$lang]."</span><br />\n";

          // log delete
          $errcode = "00111";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|Object ".$location_esc.$page." has been deleted by user '".$user."' (".getuserip().")";

          $page = "";
          $pagename = "";
          $filetype = "";
          $success = true;
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

            // save new object name incl. special characters as file parameter
            $filedata = loadlockfile ($user, $location, $pagenew, 3);
            if ($filedata != "") $filedata = setfilename ($filedata, "name", $pagenew_orig);
            if ($filedata != "") $result = savelockfile ($user, $location, $pagenew, $filedata);
            else $result = false;

            if ($result == false)
            {
              $errcode = "10354";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Object name '".$pagenew_orig."' could not be saved for ".$location_esc.$pagenew;
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

          // information log
          $errcode = "00106";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|Object '".$location_esc.$pageold."' has been renamed to '".$location_esc.$pagenew."' by user '".$user."'";

          $success = true;
        }
        else
        {
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-perform-action-due-to-missing-write-permission'][$lang]."</span><br />\n";

          $errcode = "10201";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Rename failed for ".$location_esc.$page;
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

          $add_onload = "if (parent.frames['controlFrame'])parent.frames['controlFrame'].location.reload(); if (parent.frames['mainFrame']) parent.frames['mainFrame'].location.reload();";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-unpublished'][$lang]."</span><br />\n";

          $success = true;
        }
        else
        {
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-perform-action-due-to-missing-write-permission'][$lang]."</span><br />\n";

          $errcode = "10202";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|savefile or rename failed for ".$location.$page;
        }
      }

      // ----------------------------------------- cut and paste object -------------------------------------
      elseif ($show == "" && $action == "page_paste" && $method == "cut")
      {
        // copy object file (foler must have been created by function copyfolders before)
        // folder files might exist already and should not be copied
        // all other files will be renamed with the -Copy suffix if they exist in the same location
        if (!is_file ($location.$page))
        {
          $test = copy ($location_source.$page, $location.$page);

          if ($test == true)
          {
            // remote client
            remoteclient ("copy", "abs_path_".$cat, $site, $location_source, $location, $page, $page);
          }
          else
          {
            $errcode = "10203";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Copy failed for ".$location_esc.$page; 
          }
        }
        // object exists already in the target location
        else $test = true;

        // on success
        if ($test == true)
        {
          // relational DB connectivity
          if (!empty ($mgmt_config['db_connect_rdbms']))
          {
            rdbms_renameobject (convertpath ($site, $location_source.$page, $cat), convertpath ($site, $location.$page, $cat)); 
          }

          // remove source object file
          $test = deletefile ($location_source, $page, false);

          // notification
          notifyusers ($site, $location, $page, "onmove", $user); 
          
          if ($test == true)
          {
            // remote client
            remoteclient ("delete", "abs_path_".$cat, $site, $location_source, "", $page, "");

            // log info
            $errcode = "00204";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|Object has been moved from ".$location_source.$page." to ".$location.$page." by user '".$user."'"; 

            // thumbnail (for support of versions before 5.0)
            $object_thumb = substr ($page, 0, strrpos ($page, ".")).".thumb".substr ($page, strrpos ($page, "."));

            if (is_file ($location_source.$object_thumb))
            {
              deletefile ($location_source, $object_thumb, 0);
              // remote client
              remoteclient ("delete", "abs_path_".$cat, $site, $location_source, "", $object_thumb, ""); 
            }

            $add_onload = "if (parent.frames['mainFrame']) parent.frames['mainFrame'].location.reload(); ";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-cut-and-pasted'][$lang]."</span><br />\n";
  
            $success = true; 
          }
          else
          {
            $add_onload = "";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-perform-action-due-to-missing-write-permission'][$lang]." (delete)</span><br />\n"; 
          }
        }
        // on error
        else
        {
          $errcode = "10204";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Copy failed for ".$location_source.$page." to ".$location;

          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-perform-action-due-to-missing-write-permission'][$lang]." (copy)</span><br />\n"; 
        }
      }

      // --------------------------------------- connected copy and paste object -----------------------------------
      elseif ($show == "" && $action == "page_paste" && $method == "linkcopy")
      {
        // new object
        $new_object = $location_esc.$pagename_sec;

        // load link db
        $link_db = link_db_load ($site, $user);

        // add new object to link database
        if (is_array ($link_db) && sizeof ($link_db) >= 1)
        {
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
          if ($bufferdata != false && !empty ($objects[0])) $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentobjects>", $objects[0].$new_object."|", "", ""); 

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
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|savecontainer failed for container ".$contentfile_self; 
          }

          if ($test != false)
          {
            // relational DB connectivity
            if (!empty ($mgmt_config['db_connect_rdbms']))
            {
              // create new connected object
              rdbms_createobject ($contentfile_id, convertpath ($site, $location.$page_sec, $cat), $templatefile_self, $mediafile_self, $contentfile_self, $user, "", "", true);
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
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Copy failed for ".$location_source.$page;
            }

            if ($test != false)
            {
              $add_onload = "if (parent.frames['mainFrame']) parent.frames['mainFrame'].location.reload(); ";
              $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-copied-and-pasted'][$lang]."</span><br />\n";
              $page = $page_sec;

              $success = true;
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
          // get contentcount and create the content file name
          $contentcount = contentcount ($user);

          // on contentcount error
          if (empty ($contentcount))
          {
            $add_onload = "";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['severe-error-occured'][$lang]."</span><br />\n".$hcms_lang['contentcount-failure'][$lang]."\n";
            
            $success = false;
          }
          // on contentcount success
          else
          {
            // create the name of the content container based on the unique content count value
            $contentfile_new_id = correctcontainername ($contentcount);
            $contentfile_new = $contentfile_new_id.".xml";

            // create new container folder
            @mkdir (getcontentlocation ($contentfile_new_id, 'abs_path_content'), $mgmt_config['fspermission']); 

            // date 
            $date = date ("Y-m-d H:i:s", time());

            // load container from file system
            $bufferdata = loadcontainer ($contentfile_self_wrk, "work", $user);

            // insert content container name
            if ($bufferdata != false) $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentcontainer>", $contentfile_new, "", "");

            // insert user into content container
            if ($bufferdata != false && $user != "sys" && substr ($user, 0, 4) != "sys:")  $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentuser>", $user, "", "");

            // insert new date into content container
            if ($bufferdata != false) $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentdate>", $date, "", ""); 

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
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|savecontainer failed for container ".$contentfile_new; 
            }
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
            if (!empty ($mediafile_self)) $mediafile_new = substr ($mediafile_self, 0, strpos ($mediafile_self, "_hcm"))."_hcm".$contentfile_new_id.strtolower (strrchr ($mediafile_self, "."));

            // update content file reference, set content container pointer
            if (!empty ($contentfile_new)) $pagedata = setfilename ($pagedata, "content", $contentfile_new);
            if (!empty ($mediafile_self) && !empty ($mediafile_new)) $pagedata = setfilename ($pagedata, "media", $mediafile_new);

            // relational DB connectivity
            if (!empty ($mgmt_config['db_connect_rdbms']))
            {
              // create new object in DB
              rdbms_createobject ($contentfile_new_id, convertpath ($site, $location.$page_sec, $cat), $templatefile_self, $mediafile_new, $contentfile_new, $user);

              // copy content in DB
              rdbms_copycontent ($contentfile_id, $contentfile_new_id, $user);
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
              // copy original media file
              if (is_link (getmedialocation ($site, $mediafile_self, "abs_path_media").$site."/".$mediafile_self)) $temp_path = readlink (getmedialocation ($site, $mediafile_self, "abs_path_media").$site."/".$mediafile_self);
              else $temp_path = getmedialocation ($site, $mediafile_self, "abs_path_media").$site."/".$mediafile_self;

              @copy ($temp_path, getmedialocation ($site, $mediafile_new, "abs_path_media").$site."/".$mediafile_new);
              $mediafile_self_thumb = substr ($mediafile_self, 0, strrpos ($mediafile_self, ".")).".thumb.jpg";
              $mediafile_new_thumb = substr ($mediafile_new, 0, strrpos ($mediafile_new, ".")).".thumb.jpg";

              // remote client
              remoteclient ("copy", "abs_path_media", $site, getlocation ($temp_path), "", getobject ($temp_path), $mediafile_new); 

              // copy thumbnail images (always in media repository)
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
                      // thumbnail video files (is always in media repository)
                      $mediafile_self_thumb = $mediafile_self_name.".thumb".$video_ext;
                      $mediafile_new_thumb = $mediafile_new_name.".thumb".$video_ext;

                      if (is_file (getmedialocation ($site, $mediafile_self_thumb, "abs_path_media").$site."/".$mediafile_self_thumb))
                      {
                        @copy (getmedialocation ($site, $mediafile_self_thumb, "abs_path_media").$site."/".$mediafile_self_thumb, getmedialocation ($site, $mediafile_new_thumb, "abs_path_media").$site."/".$mediafile_new_thumb);
  
                        // remote client
                        remoteclient ("copy", "abs_path_media", $site, getmedialocation ($site, $mediafile_self_thumb, "abs_path_media").$site."/", "", $mediafile_self_thumb, $mediafile_new_thumb); 
                      }

                      // individiual video files (can 
                      $mediafile_self_video = $mediafile_self_name.".video".$video_ext;
                      $mediafile_new_video = $mediafile_new_name.".video".$video_ext;

                      if (is_file (getmedialocation ($site, $mediafile_self_video, "abs_path_media").$site."/".$mediafile_self_video))
                      {
                        if (is_link (getmedialocation ($site, $mediafile_self_video, "abs_path_media").$site."/".$mediafile_self_video)) $temp_path = readlink (getmedialocation ($site, $mediafile_self_video, "abs_path_media").$site."/".$mediafile_self_video);
                        else $temp_path = getmedialocation ($site, $mediafile_self_video, "abs_path_media").$site."/".$mediafile_self_video;
  
                        @copy ($temp_path, getmedialocation ($site, $mediafile_new_video, "abs_path_media").$site."/".$mediafile_new_video);
  
                        // remote client
                        remoteclient ("copy", "abs_path_media", $site, getlocation ($temp_path), "", getobject ($temp_path), $mediafile_new_video); 
                      }

                      // individiual video config files
                      $mediafile_self_video = $mediafile_self_name.".config".$video_ext;
                      $mediafile_new_video = $mediafile_new_name.".config".$video_ext;

                      if (is_file (getmedialocation ($site, $mediafile_self_video, "abs_path_media").$site."/".$mediafile_self_video))
                      {
                        if (is_link (getmedialocation ($site, $mediafile_self_video, "abs_path_media").$site."/".$mediafile_self_video)) $temp_path = readlink (getmedialocation ($site, $mediafile_self_video, "abs_path_media").$site."/".$mediafile_self_video);
                        else $temp_path = getmedialocation ($site, $mediafile_self_video, "abs_path_media").$site."/".$mediafile_self_video;

                        @copy ($temp_path, getmedialocation ($site, $mediafile_new_video, "abs_path_media").$site."/".$mediafile_new_video);
  
                        // remote client
                        remoteclient ("copy", "abs_path_media", $site, getlocation ($temp_path), "", getobject ($temp_path), $mediafile_new_video);
                      }
                    }
                  }
                }

                // video player config for thumbnail videos
                $mediafile_self_thumb = $mediafile_self_name.".config.video";
                $mediafile_new_thumb = $mediafile_new_name.".config.video";

                if (is_file (getmedialocation ($site, $mediafile_self_thumb, "abs_path_media").$site."/".$mediafile_self_thumb))
                {
                  if (is_link (getmedialocation ($site, $mediafile_self_thumb, "abs_path_media").$site."/".$mediafile_self_thumb)) $temp_path = readlink (getmedialocation ($site, $mediafile_self_thumb, "abs_path_media").$site."/".$mediafile_self_thumb);
                  else $temp_path = getmedialocation ($site, $mediafile_self_thumb, "abs_path_media").$site."/".$mediafile_self_thumb;

                  @copy ($temp_path, getmedialocation ($site, $mediafile_new_thumb, "abs_path_media").$site."/".$mediafile_new_thumb);

                  // remote client
                  remoteclient ("copy", "abs_path_media", $site, getlocation ($temp_path), "", getobject ($temp_path), $mediafile_new_thumb);
                }
              }
            } 

            if ($test != false)
            {
              $add_onload = "if (parent.frames['mainFrame']) parent.frames['mainFrame'].location.reload(); ";
              $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-copied-and-pasted'][$lang]."</span><br />\n";

              $page = $page_sec;
              $pagename = $pagename_sec;

              $success = true;
            }
            else
            {
              $add_onload = "";
              $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-perform-action-due-to-missing-write-permission'][$lang]."</span><br />\n";

              $errcode = "10208";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|savefile failed for ".$location_esc.$page_sec;       
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

            $add_onload = "if (parent.frames['mainFrame']) parent.frames['mainFrame'].location.reload(); ";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-copied-and-pasted'][$lang]."</span><br />\n";

            $page = $page_sec;

            $success = true;
          }
          else
          {
            $add_onload = "";
            $show = "<span class=\"hcmsHeadline\">".$hcms_lang['could-not-perform-action-due-to-missing-write-permission'][$lang]."</span><br />\n";

            $errcode = "10207";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Copy failed for ".$location_source.$page;
          }
        }
      }

      // ----------------------------------------- set relationship -------------------------------------
      if (!empty ($success) && $cat == "comp" && !empty ($mediafile_self) && $contentfile_self != "" && $action == "page_paste" && $method == "copy" && (!empty ($mgmt_config['relation_source_id']) || !empty ($mgmt_config['relation_destination_id'])))
      {
        setrelation ($site, $location_source, $page_source, $mgmt_config['relation_source_id'], $location, $page_sec, $mgmt_config['relation_destination_id'], $user);
      }
    }

    // save log
    savelog (@$error);

    // eventsystem for paste
    if ($action == "page_paste" && $eventsystem['onpasteobject_post'] == 1 && empty ($eventsystem['hide']) && !empty ($success)) 
      onpasteobject_post ($site, $cat, $location_source, $location, $page, $user);
  }

  // return results
  $result = array();
  $result['result'] = $success;
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

// ---------------------------------------- deletemarkobject --------------------------------------------
// function: deletemarkobject()
// input: publication name [string], location [string], object name [string], user name [string]
// output: result array

// description:
// This function marks a page, asset, or component as deleted.

function deletemarkobject ($site, $location, $page, $user)
{
  global $wf_token, $eventsystem, $mgmt_config, $cat, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $hcms_lang, $lang;

  // initialize
  $error = array();
  $show = "";
  $add_onload = "";
  $result = array();
  $result['result'] = false;

  // set default language
  if (empty ($lang)) $lang = "en";

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($user))
  {
    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // deconvert location
    $location = deconvertpath ($location, "file");

    // convert path
    $location_esc = convertpath ($site, $location, $cat);

    // eventsystem
    if (!empty ($eventsystem['ondeleteobject_pre']) && empty ($eventsystem['hide'])) 
      ondeleteobject_pre ($site, $cat, $location, $page, $user);

    // unpublish object
    unpublishobject ($site, $location, $page, $user);

    // Important: the object path in the XML content container will not be renamed

    // mark as deleted
    $marked = rdbms_setdeletedobjects (array($location_esc.$page), $user, "set");

    if (!empty ($marked))
    {
      $result['result'] = true;
      $add_onload = "if (parent.frames['mainFrame']) { parent.frames['controlFrame'].location='control_objectlist_menu.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."'; parent.frames['mainFrame'].location.reload(); } else if (parent.frames['objFrame']) { parent.frames['controlFrame'].location='control_content_menu.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&wf_token=".url_encode($wf_token)."'; parent.frames['objFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; }";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-deleted'][$lang]."</span><br />\n";

      // log delete
      $errcode = "00311";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|Object ".$location_esc.$page." has been moved to the recycle bin by user '".$user."' (".getuserip().")";
    }

    // notification
    notifyusers ($site, $location, $page, "ondelete", $user);

    // eventsystem
    if (!empty ($eventsystem['ondeleteobject_post']) && empty ($eventsystem['hide']) && !empty ($result['result'])) 
      ondeleteobject_post ($site, $cat, $location, $page, $user);
  }

  // save log
  savelog (@$error); 

  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  $result['publication'] = $site;
  $result['location'] = $location;
  $result['location_esc'] = $location_esc;
  $result['object'] = $page;

  return $result;
}

// ---------------------------------------- deleteunmarkobject --------------------------------------------
// function: deleteunmarkobject()
// input: publication name [string], location [string], object name [string], user name [string]
// output: result array

// description:
// This function unmarks a page, asset, or component as deleted.

function deleteunmarkobject ($site, $location, $page, $user)
{
  global $wf_token, $eventsystem, $mgmt_config, $cat, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $hcms_lang, $lang;

  // initialize
  $error = array();
  $show = "";
  $add_onload = "";
  $result = array();
  $result['result'] = false;

  // set default language
  if (empty ($lang)) $lang = "en";

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($user))
  {
    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // convert location
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, $cat);

    // unmark as deleted
    $marked = rdbms_setdeletedobjects (array($location_esc.$page), $user, "unset");

    if (!empty ($marked))
    {
      $result['result'] = true;
      $add_onload = "if (parent.frames['mainFrame']) { parent.frames['controlFrame'].location='control_objectlist_menu.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."'; parent.frames['mainFrame'].location.reload(); } else if (opener.parent.frames['objFrame']) { parent.frames['controlFrame'].location='control_content_menu.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&wf_token=".url_encode($wf_token)."'; parent.frames['objFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."empty.php'; }";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-created'][$lang]."</span><br />\n";

      // log delete
      $errcode = "00312";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|Object ".$location_esc.$page." has been restored from the recycle bin by user '".$user."' (".getuserip().")";
    }
  }

  // save log
  savelog (@$error); 

  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  $result['publication'] = $site;
  $result['location'] = $location;
  $result['location_esc'] = $location_esc;
  $result['object'] = $page;

  return $result;
}

// ---------------------------------------- deleteobject --------------------------------------------
// function: deleteobject()
// input: publication name [string], location [string], object name [string], user name [string]
// output: result array

// description:
// This function removes a page, asset, or component by calling the function manipulateobject.

function deleteobject ($site, $location, $page, $user)
{
  global $eventsystem, $mgmt_config, $cat, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $hcms_lang, $lang;

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($user))
  {
    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    if (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1)
      $location = deconvertpath ($location, "file");

    // check permissions
    if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
    {
      $cat = getcategory ($site, $location);
      $ownergroup = accesspermission ($site, $location, $cat);
      $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat); 
      
      if (empty ($setlocalpermission['root']) || empty ($setlocalpermission['delete']))
      {
        $result = array();
        $result['result'] = false;
        $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
        return $result;
      }
    }

    // eventsystem
    if (!empty ($eventsystem['ondeleteobject_pre']) && empty ($eventsystem['hide'])) 
      ondeleteobject_pre ($site, $cat, $location, $page, $user);
 
    $result = manipulateobject ($site, $location, $page, "", $user, "page_delete");

    // notification
    notifyusers ($site, $location, $page, "ondelete", $user);

    // eventsystem
    if (!empty ($eventsystem['ondeleteobject_post']) && empty ($eventsystem['hide']) && !empty ($result['result'])) 
      ondeleteobject_post ($site, $cat, $location, $page, $user);

    // return results 
    return $result;
  }
  else return $result['result'] = false; 
}

// ---------------------------------------- renameobject --------------------------------------------
// function: renameobject()
// input: publication name [string], location [string], object name [string], new object name without file extension [string], user name [string]
// output: result array

// description:
// This function renames a page, component or asset and calls the function manipulateobject

function renameobject ($site, $location, $page, $pagenew, $user)
{
  global $eventsystem, $mgmt_config, $cat, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $hcms_lang, $lang;

  // default max length
  if (empty ($mgmt_config['max_digits_filename']) || intval ($mgmt_config['max_digits_filename']) < 1) $mgmt_config['max_digits_filename'] = 236;

  // trim
  $pagenew = trim ($pagenew);

  // remove .recycle from object name since it is used for the recycle bin
  if (substr ($pagenew, -8) == ".recycle") $pagenew = str_replace (".recycle", "_recycle", $pagenew);

  // replace _hcm identifier since it is used to for media files
  if (strpos ($pagenew, "_hcm") > 0) $pagenew = str_replace ("_hcm", "hcm", $pagenew);

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($pagenew) && strlen ($pagenew) <= $mgmt_config['max_digits_filename'] && valid_objectname ($user))
  { 
    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }
 
    if (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1)
      $location = deconvertpath ($location, "file");

    // check permissions
    if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
    {
      $cat = getcategory ($site, $location);
      $ownergroup = accesspermission ($site, $location, $cat);
      $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat); 

      if (empty ($setlocalpermission['root']) || empty ($setlocalpermission['rename']))
      {
        $result = array();
        $result['result'] = false;
        $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
        return $result;
      }
    }

    // eventsystem
    if (!empty ($eventsystem['onrenameobject_pre']) && empty ($eventsystem['hide'])) 
      onrenameobject_pre ($site, $cat, $location, $page, $pagenew, $user);

    // trim object name
    $pagenew = trim ($pagenew);
 
    $result = manipulateobject ($site, $location, $page, $pagenew, $user, "page_rename");

    // eventsystem
    if (!empty ($eventsystem['onrenameobject_post']) && empty ($eventsystem['hide']) && !empty ($result['result'])) 
      onrenameobject_post ($site, $cat, $location, $page, $pagenew, $user); 
 
    // return results 
    return $result;
  }
  else return $result['result'] = false;
}

// ---------------------------------------- renamefile --------------------------------------------
// function: renamefile()
// input: publication name [string], location [string], object name [string], new object including file extension [string], user name [string]
// output: result array

// description:
// This function renames a file (NOT a page, component or asset) and calls the function manipulateobject. 
// This function renames the file name including the extension and not only the name of an object.
// The event that will be executed in the event system is the same as for renameobject.

function renamefile ($site, $location, $page, $pagenew, $user)
{
  global $eventsystem, $mgmt_config, $cat, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $hcms_lang, $lang;

  // initialize
  $error = array();

  // default max length
  if (empty ($mgmt_config['max_digits_filename']) || intval ($mgmt_config['max_digits_filename']) < 1) $mgmt_config['max_digits_filename'] = 236;

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($pagenew) && strlen ($pagenew) <= $mgmt_config['max_digits_filename'] && valid_objectname ($user))
  {
    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    if (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1)
      $location = deconvertpath ($location, "file");

    // check permissions
    if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
    {
      $cat = getcategory ($site, $location);
      $ownergroup = accesspermission ($site, $location, $cat);
      $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat); 
      
      if (empty ($setlocalpermission['root']) || empty ($setlocalpermission['rename']))
      {
        $result = array();
        $result['result'] = false;
        $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
        return $result;
      }
    }

    // eventsystem
    if (!empty ($eventsystem['onrenameobject_pre']) && empty ($eventsystem['hide'])) 
      onrenameobject_pre ($site, $cat, $location, $page, $pagenew, $user); 

    $result = manipulateobject ($site, $location, $page, $pagenew, $user, "file_rename");

    // eventsystem
    if (!empty ($eventsystem['onrenameobject_post']) && empty ($eventsystem['hide']) && !empty ($result['result'])) 
      onrenameobject_post ($site, $cat, $location, $page, $pagenew, $user); 
 
    // return results 
    return $result;
  }
  else return $result['result'] = false;
}

// ---------------------------------------- cutobject --------------------------------------------
// function: cutobject()
// input: publication name [string], location [string], object name [string], user name [string], 
//        add to existing clipboard entries [boolean] (optional), save clipboard in session [boolean] (optional)
// output: result array

// description:
// This function cuts a page, component or asset.

function cutobject ($site, $location, $page, $user, $clipboard_add=false, $clipboard_session=true)
{
  global $eventsystem, $mgmt_config, $cat, $hcms_lang, $lang;
 
  // initialize
  $error = array();
  $success = false;
  $add_onload = "";
  $show = "";
  $filetype = ""; 
  $clipboard = array();

  // set default language
  if (empty ($lang)) $lang = "en";

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($user))
  {
    // get clipboard entries
    if ($clipboard_add == true && !empty ($_SESSION['hcms_temp_clipboard']))
    {
      $clipboard = $_SESSION['hcms_temp_clipboard'];
    }

    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location);

    // convert location
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, $cat);

    // check permissions
    if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
    {
      $ownergroup = accesspermission ($site, $location, $cat);
      $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat); 
      
      if (empty ($setlocalpermission['root']) || (!is_folder ($location.$page) && empty ($setlocalpermission['rename'])) || (is_folder ($location.$page) && empty ($setlocalpermission['folderrename'])))
      {
        $result = array();
        $result['result'] = false;
        $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
        return $result;
      }
    }

    // eventsystem
    if (!empty ($eventsystem['oncutobject_pre']) && empty ($eventsystem['hide'])) 
      oncutobject_pre ($site, $cat, $location, $page, $user);

    // correct file or folder
    $page = correctfile ($location, $page, $user);

    if ($page !== false)
    {
      // get file info
      $fileinfo = getfileinfo ($site, $location.$page, $cat);
      $pagename = $fileinfo['name'];
      $filetype = $fileinfo['type'];

      // define clipboard entry
      $clipboard[] = "cut|$site|$cat|$location_esc|$page|$pagename|$filetype";
    }

    // save clipboard
    if (is_array ($clipboard) && sizeof ($clipboard) > 0)
    {
      // remove duplicates
      $clipboard = array_unique ($clipboard);

      // add entries to clipboard
      if ($clipboard_session == true) $_SESSION['hcms_temp_clipboard'] = $clipboard;

      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['objects-are-copied-to-clipboard'][$lang]."</span><br />";

      $success = true;
    }
    else
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-selected-item-does-not-exist'][$lang]."</span><br />";
    }

    // eventsystem
    if (!empty ($eventsystem['oncutobject_post']) && empty ($eventsystem['hide']) && !empty ($success)) 
      oncutobject_post ($site, $cat, $location, $page, $user);
  }
 
  // return results
  $result = array();
  $result['result'] = $success;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  $result['object'] = $page;
  $result['objecttype'] = $filetype;
  $result['clipboard'] = $clipboard; 

  return $result;
}

// ---------------------------------------- copyobject --------------------------------------------
// function: copyobject()
// input: publication name [string], location [string], object name [string], user name [string], 
//        add to existing clipboard entries [boolean] (optional), save clipboard in session [boolean] (optional)
// output: result array

// description:
// This function copies a page, component or asset.

function copyobject ($site, $location, $page, $user, $clipboard_add=false, $clipboard_session=true)
{
  global $eventsystem, $mgmt_config, $cat, $hcms_lang, $lang;
 
  // initialize
  $error = array();
  $success = false;
  $add_onload = "";
  $show = "";
  $filetype = "";
  $clipboard = array();

  // set default language
  if (empty ($lang)) $lang = "en";

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($user))
  {
    // get clipboard entries
    if ($clipboard_add == true && !empty ($_SESSION['hcms_temp_clipboard']))
    {
      $clipboard = $_SESSION['hcms_temp_clipboard'];
    }

    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // add slash if not present at the end of the location string
    $location = correctpath ($location); 

    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location);

    // convert location
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, $cat);

    // check permissions
    if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
    {
      $ownergroup = accesspermission ($site, $location, $cat);
      $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat); 
      
      if (empty ($setlocalpermission['root']) || (!is_folder ($location.$page) && empty ($setlocalpermission['rename'])) || (is_folder ($location.$page) && empty ($setlocalpermission['folderrename'])))
      {
        $result = array();
        $result['result'] = false;
        $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
        return $result;
      }
    }

    // check location (only components of given publication are allowed)
    if (substr_count ($location, $mgmt_config['abs_path_rep']) == 0 || substr_count ($location, $mgmt_config['abs_path_comp'].$site."/") > 0)
    {
      // eventsystem
      if (!empty ($eventsystem['oncopyobject_pre']) && empty ($eventsystem['hide'])) 
        oncopyobject_pre ($site, $cat, $location, $page, $user);

      // correct file or folder
      $page = correctfile ($location, $page, $user);

      if ($page !== false)
      {
        // get file info
        $fileinfo = getfileinfo ($site, $location.$page, $cat);
        $pagename = $fileinfo['name'];
        $filetype = $fileinfo['type'];

        // define new clipboard entry
        $clipboard[] = "copy|$site|$cat|$location_esc|$page|$pagename|$filetype";
      }

      // save clipboard
      if (is_array ($clipboard) && sizeof ($clipboard) > 0)
      {
        // remove duplicates
        $clipboard = array_unique ($clipboard);

        // add entries to clipboard
        if ($clipboard_session == true) $_SESSION['hcms_temp_clipboard'] = $clipboard;

        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['objects-are-copied-to-clipboard'][$lang]."</span><br />";

        $success = true; 
      }
      else
      {
        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-selected-item-does-not-exist'][$lang]."</span><br />";
      }

      // eventsystem
      if (!empty ($eventsystem['oncopyobject_post']) && empty ($eventsystem['hide']) && !empty ($success)) 
        oncopyobject_post ($site, $cat, $location, $page, $user);
    }
  }
 
  // return results
  $result = array();
  $result['result'] = $success;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  $result['object'] = $page;
  $result['objecttype'] = $filetype; 
  $result['clipboard'] = $clipboard;

  return $result;
}

// ---------------------------------------- copyconnectedobject --------------------------------------------
// function: copyconnectedobject()
// input: publication name [string], location [string], object name [string], user name [string], 
//        add to existing clipboard entries [boolean] (optional), save clipboard in session [boolean] (optional)
// output: result array

// description:
// This function makes a connected copy of a page, component or asset.

function copyconnectedobject ($site, $location, $page, $user, $clipboard_add=false, $clipboard_session=true)
{
  global $eventsystem, $mgmt_config, $cat, $hcms_lang, $lang;

  // initialize
  $error = array();
  $success = false;
  $add_onload = "";
  $show = "";
  $filetype = "";
  $clipboard = array();

  // set default language
  if (empty ($lang)) $lang = "en";

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($user))
  {
    // get clipboard entries
    if ($clipboard_add == true && !empty ($_SESSION['hcms_temp_clipboard']))
    {
      $clipboard = $_SESSION['hcms_temp_clipboard'];
    }

    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    } 

    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location);

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // convert location
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, $cat);

    // check permissions
    if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
    {
      $fileinfo = getfileinfo ($site, $location.$page, $cat);
      $ownergroup = accesspermission ($site, $location, $cat);
      $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

      if (empty ($setlocalpermission['root']) || (!is_folder ($location.$page) && empty ($setlocalpermission['rename'])) || (is_folder ($location.$page) && empty ($setlocalpermission['folderrename'])))
      {
        $result = array();
        $result['result'] = false;
        $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
        return $result;
      }
    }

    // eventsystem
    if (!empty ($eventsystem['oncopyconnectedobject_pre']) && empty ($eventsystem['hide'])) 
      oncopyconnectedobject_pre ($site, $cat, $location, $page, $user);

    // check location (only components of given publication are allowed)
    if (substr_count ($location, $mgmt_config['abs_path_rep']) == 0 || substr_count ($location, $mgmt_config['abs_path_comp'].$site."/") > 0)
    {
      // correct file or folder
      $page = correctfile ($location, $page, $user);

      if ($page !== false)
      {
        // get file info
        $fileinfo = getfileinfo ($site, $location.$page, $cat);
        $pagename = $fileinfo['name'];
        $filetype = $fileinfo['type'];

        // define clipboard entry
        $clipboard[] = "linkcopy|$site|$cat|$location_esc|$page|$pagename|$filetype";
      }

      // save clipboard
      if (is_array ($clipboard) && sizeof ($clipboard) > 0)
      {
        // remove duplicates
        $clipboard = array_unique ($clipboard);

        // add entries to clipboard
        if ($clipboard_session == true) $_SESSION['hcms_temp_clipboard'] = $clipboard;

        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['objects-are-copied-to-clipboard'][$lang]."</span><br />";

        $success = true; 
      }
      else
      {
        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-selected-item-does-not-exist'][$lang]."</span><br />";
      }

      // eventsystem
      if (!empty ($eventsystem['oncopyconnectedobject_post']) && empty ($eventsystem['hide']) && !empty ($success)) 
        oncopyconnectedobject_post ($site, $cat, $location, $page, $user);
    }
  }
 
  // return results
  $result = array();
  $result['result'] = $success;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  $result['object'] = $page;
  $result['objecttype'] = $filetype;
  $result['clipboard'] = $clipboard; 

  return $result;
} 

// ---------------------------------------- pasteobject --------------------------------------------
// function: pasteobject()
// input: publication name [string], location [string], user name [string], clipboard entries [array] (optional)
// output: result array

// description:
// This function pastes an object by calling and calls the function manipulateobject

function pasteobject ($site, $location, $user, $clipboard_array=array())
{
  global $eventsystem, $mgmt_config, $cat, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $hcms_lang, $lang;

  $error = array();

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($user))
  {
    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    if (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1) $location = deconvertpath ($location, "file");

    // check permissions
    if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
    {
      $cat = getcategory ($site, $location);
      $ownergroup = accesspermission ($site, $location, $cat);
      $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat); 
      
      if (empty ($setlocalpermission['root']) || (empty ($setlocalpermission['rename']) && empty ($setlocalpermission['folderrename'])))
      {
        $result = array();
        $result['result'] = false;
        $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
        return $result;
      }
    }

    // check location (only components of given publication are allowed)
    if (substr_count ($location, $mgmt_config['abs_path_rep']) == 0 || substr_count ($location, $mgmt_config['abs_path_comp'].$site."/") > 0)
    {
      $result = manipulateobject ($site, $location, "", "", $user, "page_paste", $clipboard_array);
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
// input: publication name [string], location [string], object name [string], user name [string]
// output: result array

// description:
// This function locks an object for a specific user

function lockobject ($site, $location, $page, $user)
{
  global $eventsystem, $mgmt_config, $cat, $hcms_lang, $lang;
 
  // initialize
  $error = array();
  $success = false;
  $add_onload = "";
  $show = "";
  $pagename = "";
  $usedby = "";
  $filetype = "";

  // set default language
  if (empty ($lang)) $lang = "en";

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($user))
  {
    $dir = $mgmt_config['abs_path_data']."checkout/";
    $file = $user.".dat";

    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    } 

    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location);

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    if (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1)  $location = deconvertpath ($location, "file");

    // define object file name
    if (is_dir ($location.$page))
    {
      $location = $location.$page."/";
      $page = ".folder";
      $fileinfo = getfileinfo ($site, $location.$page, $cat);
      $filetype = $fileinfo['type']; 
      $pagename = specialchr_decode (getobject ($location)); 
    }
    else
    {
      $page = correctfile ($location, $page, $user);
      $fileinfo = getfileinfo ($site, $location.$page, $cat);
      $filetype = $fileinfo['type']; 
      $pagename = specialchr_decode ($fileinfo['name']); 
    }

    // check permissions
    if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
    {
      $ownergroup = accesspermission ($site, $location, $cat);
      $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat); 
      
      if (empty ($setlocalpermission['root']) || (!is_folder ($location.$page) && empty ($setlocalpermission['create'])) || (is_folder ($location.$page) && empty ($setlocalpermission['foldercreate'])))
      {
        $result = array();
        $result['result'] = false;
        $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
        return $result;
      }
    }

    // eventsystem
    if (!empty ($eventsystem['onlockobject_pre']) && empty ($eventsystem['hide'])) 
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
        $success = true;
      }
      else
      { 
        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['object-could-not-be-checked-out'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
      } 

      // eventsystem
      if (!empty ($eventsystem['onlockobject_post']) && empty ($eventsystem['hide']) && !empty ($success)) 
        onlockobject_post ($site, $cat, $location, $page, $user);
    }
  }

  // return results
  $result = array();
  $result['result'] = $success;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  $result['object'] = $page;
  $result['name'] = $pagename;
  $result['usedby'] = $usedby;
  $result['objecttype'] = $filetype; 

  return $result;
}

// ---------------------------------------- unlockobject --------------------------------------------
// function: unlockobject()
// input: publication name [string], location [string], object name [string], user name [string]
// output: array

// description:
// This function unlocks an object of a specific user

function unlockobject ($site, $location, $page, $user)
{
  global $eventsystem, $mgmt_config, $cat, $hcms_lang, $lang;
 
  // initialize
  $error = array();
  $success = false;
  $add_onload = "";
  $show = "";
  $pagename = "";
  $usedby = "";
  $filetype = "";

  // set default language
  if (empty ($lang)) $lang = "en";

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && valid_objectname ($user))
  {
    $dir = $mgmt_config['abs_path_data']."checkout/";
    $file = $user.".dat";

    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location);

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    if (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1) $location = deconvertpath ($location, "file");

    // define object file name
    if (is_dir ($location.$page))
    {
      $location = $location.$page."/";
      $page = ".folder";
      $fileinfo = getfileinfo ($site, $location.$page, $cat);
      $filetype = $fileinfo['type']; 
      $pagename = specialchr_decode (getobject ($location)); 
    }
    else
    {
      $page = correctfile ($location, $page, $user);
      $fileinfo = getfileinfo ($site, $location.$page, $cat);
      $filetype = $fileinfo['type']; 
      $pagename = specialchr_decode ($fileinfo['name']); 
    }

    // check permissions
    if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
    {
      $ownergroup = accesspermission ($site, $location, $cat);
      $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat); 
      
      if (empty ($setlocalpermission['root']) || (!is_folder ($location.$page) && empty ($setlocalpermission['create'])) || (is_folder ($location.$page) && empty ($setlocalpermission['foldercreate'])))
      {
        $result = array();
        $result['result'] = false;
        $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
        return $result;
      }
    }

    // eventsystem
    if (!empty ($eventsystem['onunlockobject_pre']) && empty ($eventsystem['hide'])) 
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
        $add_onload = "if (parent.frames['objFrame']) parent.frames['objFrame'].location.reload(); if (parent.frames['mainFrame']) parent.frames['mainFrame'].location.reload();";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-is-checked-in'][$lang]."</span><br />";

        $usedby = "";
        $success = true;
      }
      else
      {
        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['object-could-not-be-checked-out'][$lang]."</span><br />\n".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
      } 

      // eventsystem
      if (!empty ($eventsystem['onunlockobject_post']) && empty ($eventsystem['hide']) && !empty ($success)) 
        onunlockobject_post ($site, $cat, $location, $page, $user);
    }
  }
 
  // return results
  $result = array();
  $result['result'] = $success;
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
// input: publication name [string], location [string], object name (full name incl. extension) [string], user name [string]
// output: result array

// description:
// This function publishes a page, component or asset

function publishobject ($site, $location, $page, $user)
{
  global $eventsystem, $mgmt_config, $cat, $ctrlreload, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $hcms_lang, $lang;
 
  // initialize
  $error = array();
  $success = false;
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
  if (empty ($lang)) $lang = "en";

  // create file name
  if (specialchr ($page, ".-_~") == true) $page = createfilename ($page);

  // exclude objects in the recycle bin
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && strpos ($location.$page."/", ".recycle/") < 1 && valid_objectname ($user))
  {
    // load template engine (it is not included by API and needs to be loaded seperately!)
    require_once ($mgmt_config['abs_path_cms']."function/hypercms_tplengine.inc.php");

    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location);

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // convert location
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, $cat);

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
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Object reference in link management is missing for container $container used by $location$page"; 

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

              // check permissions
              if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
              {
                $ownergroup = accesspermission ($site, $location, $cat);
                $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat); 
                
                if (empty ($setlocalpermission['root']) || empty ($setlocalpermission['publish']))
                {
                  $result = array();
                  $result['result'] = false;
                  $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
                  return $result;
                }
              }

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

                  // error occured if error comments can be found
                  if (isset ($result['view']) && strpos ("_".$result['view'], "<!-- hyperCMS:Error") > 0)
                  {
                    // save object file with errors
                    $error_file = date("Y-m-d-H-i-s").".".$page.".error";
                    savefile ($mgmt_config['abs_path_temp'], $error_file, $viewstore);

                    $errcode = "20201";
                    $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Error in code of object ".$location_esc.$page.", see temp file: ".$error_file; 

                    $viewstore = false;
                    $release = false;
                    $add_onload = "";
                    $show = $hcms_lang['an-error-occurred-in-building-the-view'][$lang]."<br/>Error file: ".$error_file;
                  }
                }
                else
                {
                  $errcode = "20202";
                  $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Error in code of object ".$location_esc.$page.", no error code available"; 

                  $viewstore = false;
                  $release = false;
                  $add_onload = "";
                  $show = $hcms_lang['an-error-occurred-in-building-the-view'][$lang];
                }

                // eventsystem
                if (!empty ($eventsystem['onpublishobject_pre']) && empty ($eventsystem['hide']))
                  onpublishobject_pre ($site, $cat, $location, $page, @$contentfile, @$contentdata, @$templatefile, @$templatedata, @$viewstore, $user);

                // -------------------------------- publish page -------------------------------
                // if user has the workflow permission to publish or no workflow is attached
                // for media files the object file will not be touched (application might be empty or "media") or the media generator is used
                if ($show == "" && $release >= 3 && $application != "" && $application != "media" && ($viewstore != "" || $application == "generator"))
                {
                  // get the file extension of the object file
                  $file_info = getfileinfo ($site, $location.$page, $cat);

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
                      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Rename failed for ".$location_esc.$page; 
                    } 
                  }
                  // rename object file if file extension has changed
                  elseif (strtolower ($file_info['ext']) != strtolower (".".$templateext))
                  {
                    // rename file if template extension changed
                    $test = renamefile ($site, $location, $page, $page_new, $user);
 
                    // remote client
                    remoteclient ("rename", "abs_path_".$cat, $site, $location, "", $page, $page_new); 

                    if (empty ($test['result']))
                    {
                      $errcode = "20111";
                      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|renamefile failed for ".$location_esc.$page;
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

                      if (empty ($test['result']))
                      {
                        $errcode = "20199";
                        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|publishlinkedobject failed for ".$location_esc.$page; 
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
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Object reference in link management for container $container refers to a non existing object ".$location_esc.$page;
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
                  if (!empty ($mgmt_config[$site]['linkengine']) || @substr_count (strtolower ($templatedata), "hypercms:link") >= 1 || @substr_count (strtolower ($templatedata), "hypercms:artlink") >= 1 || @substr_count (strtolower ($templatedata), "hypercms:component") >= 1 || @substr_count (strtolower ($templatedata), "hypercms:artcomponent") >= 1)
                  {
                    // ----------------------------- create and save link index file ---------------------------------
                    // links
                    $link_data = "";
                    $link_db_entry = $container.":|";

                    $objref_array = getcontent ($contentdata, "<contentobjects>");

                    if (is_array ($objref_array) && $objref_array[0] != "")
                    {
                      $link_db_entry .= $objref_array[0].":|";
                    }
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
  
                        if (!empty ($link_href[0]) && substr ($link_href[0], 0, 6) == "%page%")
                        {
                          $link_href[0] = deconvertlink (trim ($link_href[0])); 
                        }
  
                        if (!empty ($link_id[0]) && !empty ($link_href[0])) $link_data .= "page|".$link_id[0]."|".$link_href[0]."\n";
    
                        if (!empty ($link_href[0])) $link_db_entry .= $link_href[0]."|";
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
                        $component_file_array = array();
  
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
                        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Link index file of container $container could not be saved";
                      }
                    }
                  }

                  // show message
                  $add_onload = "if (parent.frames['controlFrame']) parent.frames['controlFrame'].location.reload(); if (parent.frames['mainFrame']) parent.frames['mainFrame'].location.reload();";
                  $show = "<span class=\"hcmsHeadline\">".$hcms_lang['published-item-successfully'][$lang]."</span><br />\n";

                  $success = true;
                }
                else
                {
                  $add_onload = "";
                  $show = "<span class=\"hcmsHeadline\">".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
                  ".$hcms_lang['an-error-occurred-in-building-the-view'][$lang]."\n";
                }
              }
              else
              {
                $add_onload = "";
                $show = "<span class=\"hcmsHeadline\">".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
                ".$hcms_lang['item-is-not-managed-by-hypercms'][$lang]."\n";

                $success = true;
              }

              // eventsystem
              if (!empty ($eventsystem['onpublishobject_post']) && empty ($eventsystem['hide']) && !empty ($success)) 
                onpublishobject_post ($site, $cat, $location, $page_new, $contentfile, $contentdata, $templatefile, $templatedata, $viewstore, $user);
            }
            // if we publish a media file we dont use the link management
            elseif ($application == "media")
            {
              $add_onload = "";
              $show = "<span class=\"hcmsHeadline\"".$hcms_lang['published-item-successfully'][$lang]."</span><br />\n";

              $success = true;
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
        if (!empty ($eventsystem['onpublishobject_pre']) && empty ($eventsystem['hide'])) 
          onpublishobject_pre ($site, $cat, $location, $page, $container, $contentdata, $template, "", "", $user);

        // execute eventsystem
        if ($media != false && $application != "generator" && !empty ($eventsystem['onpublishobject_post']) && empty ($eventsystem['hide'])) 
          onpublishobject_post ($site, $cat, $location, $page, $container, $contentdata, $template, "", "", $user);

        $add_onload = "";
        $show = "<span class=\"hcmsHeadline\">".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
        ".$hcms_lang['item-is-not-managed-by-hypercms'][$lang]."\n";
 
        $success = true;
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
              $vtt_string = getcontent ($vtt_textnode, "<textcontent>", true);

              if (!empty ($vtt_string[0])) savefile ($mgmt_config['abs_path_view'], $container_id."_".trim($vtt_langcode).".vtt", $vtt_string[0]);
            }
          }
        }
      }

      // ------------------------------ update and save container and database ------------------------------
      if (!empty ($contentdata) && !empty ($container))
      {
        // create version of previous content file
        if (!empty ($mgmt_config['contentversions']))
        {
          createversion ($site, $container, $user);
        }

        // update database
        rdbms_setcontent ($site, $container_id, "", "", "", false, true);

        // date 
        $date = date ("Y-m-d H:i:s", time());

        // update information in content container
        $contentdata = setcontent ($contentdata, "<hyperCMS>", "<contentpublished>", $date, "", "");

        // write content container
        if ($contentdata != false)
        { 
          // save working xml content container file
          $test = savecontainer ($container, "work", $contentdata, $user, true);

          if ($test == false)
          {
            $errcode = "10880";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Working container $container.wrk could not be saved";
          }
          else
          {
            $errcode = "00880";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|User '".$user."' published the object ".$location_esc.$page; 
          }

          // save published xml content container file 
          $test = savecontainer ($container, "published", $contentdata, $user, true);

          if ($test == false)
          {
            $errcode = "10341";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Published container $container could not be saved";
          }
        }
        else $test = false;

        if ($test == false)
        {
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['item-could-not-be-published'][$lang]."</span><br />
          ".$hcms_lang['you-do-not-have-write-permissions-for-the-container'][$lang]."\n";
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
  // result array has been defined before, do not reset here
  if (!empty ($success) || $release < 3 || substr ($page, -8) == ".recycle") $result['result'] = true;
  else $result['result'] = false;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;

  return $result;
}

// ------------------------------------- publishlinkedobject -----------------------------------------
// function: publishlinkedobject()
// input: publication name [string], location [string], object name [string], user name [string]
// output: array

// description:
// This function publishes all linked objects of a given object.
// All objects with component links (references) to the given object will be published.
// This funtion is only used by publishobject.

function publishlinkedobject ($site, $location, $page, $user)
{
  global $eventsystem, $mgmt_config, $cat, $ctrlreload, $hcms_lang, $lang;

  // initialize
  $error = array();

  // set default language
  if (empty ($lang)) $lang = "en";

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
// input: publication name [string], location [string], object name [string], user name [string]
// output: result array

// description:
// This function unpublishes a page, component, or asset and calls the function manipulateobject

function unpublishobject ($site, $location, $page, $user)
{
  global $eventsystem, $mgmt_config, $cat, $ctrlreload, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $hcms_lang, $lang; 

  // initialize
  $error = array();
  $result = array();

  // set default language
  if (empty ($lang)) $lang = "en";

  // create file name
  if (specialchr ($page, ".-_~") == true) $page = createfilename ($page);

  // exclude objects in the recycle bin
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && strpos ($location.$page."/", ".recycle/") < 1 && valid_objectname ($user))
  {
    // load template engine (it is not included by API and needs to be loaded seperately!)
    require_once ($mgmt_config['abs_path_cms']."function/hypercms_tplengine.inc.php");

    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    $cat = getcategory ($site, $location);
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, $cat);

    // check permissions
    if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
    {
      $ownergroup = accesspermission ($site, $location, $cat);
      $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat); 
      
      if (empty ($setlocalpermission['root']) || empty ($setlocalpermission['publish']))
      {
        $result['result'] = false;
        $result['message'] = $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
        return $result;
      }
    }

    // check location (only components of given publication are allowed)
    if (substr_count ($location, $mgmt_config['abs_path_rep']) == 0 || substr_count ($location, $mgmt_config['abs_path_comp'].$site."/") > 0)
    { 
      // eventsystem
      if (!empty ($eventsystem['onunpublishobject_pre']) && empty ($eventsystem['hide'])) 
        onunpublishobject_pre ($site, $cat, $location, $page, $user);

      // get all connected objects
      $pagedata = loadfile ($location, $page);
      $container = getfilename ($pagedata, "content");
      $container_id = substr ($container, 0, strpos ($container, ".xml"));
      $template = getfilename ($pagedata, "template");
      $media = getfilename ($pagedata, "media");

      // check template
      /*$templatedata = loadtemplate ($site, $template);

      if (is_array ($templatedata))
      {
        if ($templatedata['content'] != "")
        {
          $buffer = getcontent ($templatedata['content'], "<application>");

          if ($buffer[0] != "") $application = $buffer[0];
          else $application = "";
        }
      }
      else $template = false;*/

      // delete all VTT files of videos
      if ($container_id != "")
      {
        // load language code index file
        $langcode_array = getlanguageoptions ();

        if ($langcode_array != false)
        {
          foreach ($langcode_array as $code => $language)
          {
            if (is_file ($mgmt_config['abs_path_view'].$container_id."_".trim($code).".vtt"))
            {
              deletefile ($mgmt_config['abs_path_view'], $container_id."_".trim($code).".vtt", 0);
            }
          }
        }
      }

      // if object is a page, component, or multimedia file (and not a folder)
      if ($container != false && $template != false && $page != ".folder")
      {
        $object_array = getconnectedobject ($container);

        if ($object_array == false)
        {
          $add_onload = "";
          $show = "<span class=\"hcmsHeadline\">".$hcms_lang['error-occured'][$lang]."</span><br />
          ".$hcms_lang['information-about-connected-items-of-the-container-is-missing'][$lang]."\n";

          $errcode = "20897";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Object reference in link management is missing for container $container used by ".$location_esc.$page; 

          // define current object for unpublishing
          $object_array = array();
          $object_array[0]['publication'] = $site;
          $object_array[0]['location'] = $location;
          $object_array[0]['object'] = $page;
          $object_array[0]['category'] = $cat;

          $link_db_correct = false;
        }
        // one object reference were found in container
        elseif (is_array ($object_array) && sizeof ($object_array) == 1)
        {
          // redefine current object for unpublishing
          $object_array = array();
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
              $location_esc = convertpath ($site, $location, $cat);
              $cat = $object['category']; 
              $page = $object['object'];
              $page = correctfile ($location, $page, $user);

              // if object file exists
              if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && is_file ($location.$page))
              {
                // ---------------------------- call template engine ---------------------------
                $result = buildview ($site, $location, $page, $user, "unpublish", "no");

                if (is_array ($result))
                {
                  $viewstore = $result['view'];
                  $application = $result['application'];
                  $contentdata = $result['containerdata'];

                  // error occured if error comments can be found
                  if (isset ($result['view']) && strpos ("_".$result['view'], "<!-- hyperCMS:Error") > 0)
                  {
                    // save object file with errors
                    $error_file = date("Y-m-d-H-i-s").".".$page.".error";
                    savefile ($mgmt_config['abs_path_temp'], $error_file, $viewstore);

                    $errcode = "20301";
                    $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Error in code of object ".$location_esc.$page.", see temp file: ".$error_file; 

                    $viewstore = false;
                    $release = false;
                    $add_onload = "";
                    $show = $hcms_lang['an-error-occurred-in-building-the-view'][$lang]."<br/>Error file: ".$error_file;
                  }

                  // update database
                  rdbms_setcontent ($site, $container_id, "", "", "", false, "null");

                  // update information in content container
                  $contentdata = setcontent ($contentdata, "<hyperCMS>", "<contentpublished>", "", "", "");

                  // write content container
                  if ($contentdata != false)
                  {
                    // save working xml content container file
                    $test = savecontainer ($container, "work", $contentdata, $user, true);

                    if ($test == false)
                    {
                      $errcode = "10980";
                      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Working container $container.wrk could not be saved";
                    }

                    // remove content
                    $contentdata = deletecontent ($contentdata, "<text>");
                    $contentdata = deletecontent ($contentdata, "<media>");
                    $contentdata = deletecontent ($contentdata, "<link>");
                    $contentdata = deletecontent ($contentdata, "<component>");
                    $contentdata = deletecontent ($contentdata, "<article>");

                    // save published xml content container file 
                    if ($contentdata != "") $test = savecontainer ($container, "published", $contentdata, $user, true);
                    else $test = false;

                    // on error
                    if ($test == false)
                    {
                      $errcode = "10331";
                      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Published container $container could not be saved";
                    }
                  }
                }
                // on error
                else
                {
                  $errcode = "20302";
                  $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Error in code of object ".$location_esc.$page.", no error code available"; 

                  $viewstore = false;
                  $release = false;
                  $add_onload = "";
                  $show = $hcms_lang['an-error-occurred-in-building-the-view'][$lang];
                }

                // ---------------------------- unpublish object ---------------------------
                // only for pages, components, and generated multimedia files
                if (empty ($media) || (!empty ($application) && $application == "generator"))
                {
                  $result = manipulateobject ($site, $location, $page, "", $user, "page_unpublish");

                  if (!empty ($result['result']))
                  {
                    // check application, if no dynamic inclusion of components is possible publish also all 
                    // objects which use the given object.
                    if ($cat == "comp")
                    {
                      $test = publishlinkedobject ($site, $location, $page, $user);

                      if (empty ($test['result']))
                      {
                        $errcode = "20198";
                        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|publishlinkedobject failed for '".$location_esc.$page."'"; 
                      }
                    }
                  }
                  // on error
                  else
                  {
                    $add_onload = "";
                    $show = "<span class=\"hcmsHeadline\">".$hcms_lang['error-occured'][$lang]."</span><br />
                    ".$hcms_lang['you-do-not-have-write-permissions-for-the-item'][$lang]."\n";

                    break;
                  }
                }

                // log entry
                if (!empty ($result['result']))
                {
                  $errcode = "00198";
                  $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|User '".$user."' unpublished the object '".$location_esc.$page."'";
                }
              } 
            }
          }

          // result on success
          if (!empty ($result['result']))
          { 
            $result['add_onload'] = "";
            $result['message'] = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-unpublished'][$lang]."</span>";
          }
        }
        else
        {
          $result['result'] = false; 
          $result['add_onload'] = "";
          $result['message'] = "<span class=\"hcmsHeadline\">".$hcms_lang['error-occured'][$lang]."</span><br />
          ".$hcms_lang['information-about-connected-items-of-the-container-is-missing'][$lang]."\n";

          $errcode = "20878";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Object reference in link management is missing for container ".$container." used by '".$location_esc.$page."'"; 
        } 
      }
      else
      {
        $result['result'] = true; 
        $result['add_onload'] = "";
        $result['message'] = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-unpublished'][$lang]."</span>";

        $errcode = "00870";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|User '".$user."' unpublished the object '".$location_esc.$page."'";
      } 

      // eventsystem
      if (!empty ($eventsystem['onunpublishobject_post']) && empty ($eventsystem['hide']) && !empty ($result['result'])) 
        onunpublishobject_post ($site, $cat, $location, $page, $user);
    }
    // location is not valid
    else
    {
      $result['result'] = false;
      $result['add_onload'] = "";
      $result['message'] = "<span class=\"hcmsHeadline\">".$hcms_lang['error-occured'][$lang]."</span><br />
      ".$hcms_lang['you-do-not-have-write-permissions-for-the-item'][$lang]."\n";

      $errcode = "20879";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Invalid object location of '".convertpath ($site, $location, $cat).$page."' with container ".$container; 
    } 
  }
  // object is in recycle bin
  elseif (substr ($page, -8) == ".recycle")
  {
    $result['result'] = true;
    $result['add_onload'] = "";
    $result['message'] = "<span class=\"hcmsHeadline\">".$hcms_lang['the-object-was-unpublished'][$lang]."</span>";
  }
  // input parameters are invalid
  else
  {
    $result['result'] = false;
    $result['add_onload'] = "";
    $result['message'] = "<span class=\"hcmsHeadline\">".$hcms_lang['error-occured'][$lang]."</span><br />
    ".$hcms_lang['required-parameters-are-missing'][$lang]."\n";
  }
  
  // save log
  savelog (@$error);
 
  // return results 
  return $result;
}


// ------------------------------------------- processobjects -------------------------------------------
// function: processobjects()
// input: action [publish,unpublish,delete,exec], publication name [string], location [string], object name or mail ID [string], only published objects [boolean], user name [string]
// output: true/false

// description:
// Publish, unpublish or delete all objects recursively, and send mails stored in the queue. This function is used by the job 'minutely' to process all objects of the queue.
// In order to process all objects recursively a folder name need to be provided and not the .folder file.
// This function should not be used for the graphical user interface since it does not provide feedback about the process state!

function processobjects ($action, $site, $location, $file, $published_only=false, $user="")
{
  global $eventsystem, $mgmt_config, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $hcms_lang, $lang;

  // initialize
  $error = array();

  if ($action == "mail" && is_numeric ($file) && valid_objectname ($user))
  {
    // post data
    $data = array();
    $data['service'] = true;
    $data['mailfile'] = $file.".".$user.".mail";
    $data['action'] = "sendmail";
    $data['token'] = createtoken ($user);

    // call sendmail service
    HTTP_Post ($mgmt_config['url_path_cms']."service/sendmail.php", $data, "application/x-www-form-urlencoded", "UTF-8");

    return true;
  }
  elseif ($action != "" && valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($user))
  {
    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }
 
    // add slash if not present at the end of the location string
    $location = correctpath ($location);
 
    $action = strtolower ($action);
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, "");

    // define object file name
    $file_orig = $file;
    $file = correctfile ($location, $file, $user);

    // -------------------------- process objects -------------------------------
    if (!empty ($location) && !empty ($file))
    {
      // if folder
      if (is_dir ($location.$file))
      {
        // process all objects in folder
        $scandir = scandir ($location.$file);

        if ($scandir)
        {
          foreach ($scandir as $dirfile)
          {
            if ($dirfile != "." && $dirfile != ".." && $dirfile != ".folder")
            {
              processobjects ($action, $site, $location.$file."/", $dirfile, $published_only, $user);
            }
          }

          // process .folder file always at last since action "delete" will trigger deletefolder that can only delete empty folders
          if (is_file ($location.$file."/.folder"))
          {
            processobjects ($action, $site, $location.$file."/", ".folder", $published_only, $user);
          }

          return true;
        }
        else return false;
      }
      // if object
      elseif (is_file ($location.$file))
      {
        // initialize
        $result = array();
        $result['result'] = true;

        $fileinfo = getfileinfo ($site, $file, "");

        // process object
        if (!empty ($fileinfo['published']) || $published_only == false)
        {
          if ($action == "publish")
          {
            $result = publishobject ($site, $location, $file, $user);
          }
          elseif ($action == "unpublish")
          {
            $result = unpublishobject ($site, $location, $file, $user);
          }
          elseif ($action == "delete")
          {
            // delete object
            if ($file != ".folder") $result = deleteobject ($site, $location, $file, $user);
            // delete folder
            else $result = deletefolder ($site, getlocation ($location), getobject ($location), $user);
          }

          // on error
          if (empty ($result['result']))
          {
            $errcode = "20420";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Processing ($action) failed for ".$location_esc.$file;

            // save log
            savelog (@$error); 

            return false;
          }
          else return true;
        }
      }
      // if provided location or object does not exist
      else
      {
        $errcode = "20421";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Processing ($action) failed since location '".$location_esc."' or object '".$file_orig."' does not exist";

        // save log
        savelog (@$error); 

        return false;
      }
    }
    // if provided location or object does not exist
    else
    {
      $errcode = "20422";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Processing ($action) failed since location '".$location_esc."' or object '".$file_orig."' does not exist";

      // save log
      savelog (@$error); 

      return false;
    }
  }
  else return false; 
}

// ------------------------------------------ collectobjects --------------------------------------------
// function: collectobjects()
// input: root ID [string], publication name [string], category [page,comp], location [string], collect only published objects [boolean] 
// output: result array / false

// description:
// Help function used to create a list of all objects inside the provided location.

function collectobjects ($root_id, $site, $cat, $location, $published_only=false)
{ 
  global $user, $pageaccess, $compaccess, $mgmt_config, $hiddenfolder, $hcms_lang, $lang;

  $error = array();
 
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
      $scandir = scandir ($location);

      // add slash if not present at the end of the location string
      $location = correctpath ($location);

      if ($scandir)
      {
        foreach ($scandir as $dirfile)
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
      }
    }
    // if object
    elseif (is_file ($location))
    {
      $object = getobject ($location);
      $location = getlocation ($location);
      $result = getfileinfo ($site, $object, $cat);

      if ($result['published'] == true || $published_only == false)
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
// input: action [publish, unpublish, deletemark, deleteunmark/restore, emptypin, delete, paste], objectpath [array],
//        method (only for paste action) [copy,linkcopy,cut], force [start,stop,continue], 
//        collect only published objects [boolean], user name [string], temporary collection file name [string] (optional), max. number of items processed per second [integer] (optional)
// output: true/false

// description:
// This function is used to perform actions on multiple objects and is mainly used by popup_status.php.
// This function should only be used in connection with the GUI of the system.

function manipulateallobjects ($action, $objectpath_array, $method="", $force="start", $published_only=false, $user="", $tempfile="", $maxitems=20)
{
  global $eventsystem, $mgmt_config, $cat, $pageaccess, $compaccess, $hiddenfolder, $hcms_lang, $lang;

  // initialize
  $error = array();
  $result = array();
  $result['result'] = false;
  $result['maxcount'] = 0;
  $result['count'] = 0;
  $result['working'] = false;
  $result['message'] = "";
  $result['tempfile'] = "";
  $result['method'] = "";
  $result['report'] = array();

  // set default language
  if (empty ($lang)) $lang = "en";

  // --------------------------empty recycle bin -------------------------------
  if ($action == "emptybin")
  {
    $objectpath_array = array();

    // reset array of all objects based on recycle bin
    $objectinfo_array = rdbms_getdeletedobjects ($user);

    if (is_array ($objectinfo_array) && sizeof ($objectinfo_array) > 0)
    {
      foreach ($objectinfo_array as $hash => $objectinfo)
      {
        $objectpath_array[$hash] = $objectinfo['objectpath'];
      }
    }

    // reset action in order to delete objects in recycle bin
    $action = "delete";
  }

  // get object pathes from the session if it is not set
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

    // -------------------------- mark or unmark objects as deleted -------------------------------
    if ($action == "deletemark" || $action == "deleteunmark" || $action == "restore")
    {
      // restore and deleteunmark are exactly the same actions
      if ($action == "deletemark") $set = "set";
      elseif ($action == "deleteunmark" || $action == "restore") $set = "unset";

      // mark or unmark objects as deleted
      $marked = rdbms_setdeletedobjects ($objectpath_array, $user, $set);

      // on success
      if (!empty ($marked))
      {
        // mark as deleted
        if ($action == "deletemark")
        {
          if (sizeof ($objectpath_array) > 0)
          {
            manipulateallobjects ("unpublish", $objectpath_array, "", "start", 0, $user, "", 1000000);
          }

          // log
          $errcode = "00315";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|Objects have been moved to the recycle bin by user '".$user."' (".getuserip().")";
        }
        // unmark / restore
        else
        {
          // log
          $errcode = "00316";
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|Objects have been restored from the recycle bin by user '".$user."' (".getuserip().")";
        }

        // results
        $result['result'] = true;
        $result['maxcount'] = $result['count'] = sizeof ($objectpath_array);
      }
      // on error
      else
      {
        // log
        $errcode = "20315";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Failed to process objects of the recycle bin for user '".$user."' (".getuserip().")";

        $result['result'] = false;
        $result['maxcount'] = $result['count'] = sizeof ($objectpath_array);
        $result['message'] = $hcms_lang['a-folder-with-the-same-name-exists-already'][$lang]." / ".$hcms_lang['the-object-exists-already'][$lang];
      }

      // save log
      savelog (@$error);

      return $result;
    }

    // set session
    $_SESSION['clipboard_multiobject'] = $objectpath_array;

    // -------------------------- load or create collection -------------------------------
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
 
          if (valid_publicationname ($site) && valid_locationname ($location) && $object !== false)
          {
            // define category if undefined
            if ($cat == "") $cat = getcategory ($site, $location);

            // add slash if not present at the end of the location string
            $location = correctpath ($location);

            // read clipboard if action = paste
            if ($action == "paste" && !empty ($_SESSION['hcms_temp_clipboard']) && is_array ($_SESSION['hcms_temp_clipboard']))
            {
              // get clipboard from session
              $collection = array ();
              $j = 0;

              foreach ($_SESSION['hcms_temp_clipboard'] as $clipboard_entry)
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

                          // if the cutted folder will be pasted in the source location
                          if ($method == "cut" && is_dir ($location_source.$object_source) && substr_count ($location_dest, $location_source.$object_source."/") > 0)
                          {
                            $result['result'] = false;
                            $result['message'] = "<span class=\"hcmsHeadline\">".$hcms_lang['it-is-not-possible-to-cut-and-paste-objects-in-the-same-destination'][$lang]."</span><br />\n";
                            return $result;
                          }
                          // on success
                          else
                          {
                            if ($method == "cut") $no_duplicates = true;
                            else $no_duplicates = false;

                            $result = copyfolders ($site, $location_source, $location_dest, $object_source, $user, $no_duplicates);

                            if (empty ($result['result']))
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
            elseif ($action != "paste")
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

    // set rootpathes in the session (do not use function setsession here)
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

    // count collection items and set force paremater
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
      // verify collection size
      if (sizeof ($collection) < $maxitems) $maxitems = sizeof ($collection);

      // process items
      for ($i = 0; $i <= ($maxitems-1); $i++)
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

              // remove object from collection in any case
              unset ($collection[$i]);

              // on error
              if (empty ($test['result'])) 
              {
                $errcode = "20108";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|publishobject failed for ".convertpath ($site_source, $location_source, $cat).$object_source;
                // deprecated: break;
                // avoid break of process
                $test['result'] = true;
                $result['report'][] = $test['message']." Error: publishobject failed for ".convertpath ($site_source, $location_source, $cat).$object_source;
              } 
            }
            elseif ($action == "unpublish")
            {
              $test = unpublishobject ($site_source, $location_source, $object_source, $user);
 
              // remove object from collection in any case
              unset ($collection[$i]);

              // on error
              if (empty ($test['result'])) 
              {
                $errcode = "20109";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|unpublishobject failed for ".convertpath ($site_source, $location_source, $cat).$object_source;
                // deprecated: break;
                // avoid break of process
                $test['result'] = true;
                $result['report'][] = $test['message']." Error: unpublishobject failed for ".convertpath ($site_source, $location_source, $cat).$object_source;
              }
            } 
            elseif ($action == "delete")
            {
              $test = deleteobject ($site_source, $location_source, $object_source, $user);

              // remove object from collection in any case
              unset ($collection[$i]);

              // on error
              if (empty ($test['result'])) 
              {
                $errcode = "20109";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|deleteobject failed for ".convertpath ($site_source, $location_source, $cat).$object_source;
                // deprecated: break;
                // avoid break of process
                $test['result'] = true;
                $result['report'][] = $test['message']." Error: deleteobject failed for ".convertpath ($site_source, $location_source, $cat).$object_source;
              }
            }
            elseif ($action == "paste")
            {
              // for action copy and paste
              if ($method == "copy") 
              {
                // do not overwrite clipboard
                $result = copyobject ($site_source, $location_source, $object_source, $user, false, false);
              }
              // for action cut and paste
              elseif ($method == "cut")
              {
                // do not overwrite clipboard
                $result = cutobject ($site_source, $location_source, $object_source, $user, false, false);
              } 
              // for action connected copy and paste
              elseif ($method == "linkcopy")
              {
                // do not overwrite clipboard
                $result = copyconnectedobject ($site_source, $location_source, $object_source, $user, false, false);
              }

              // paste object
              if (!empty ($result['result'])) 
              {
                // define destination location for paste action
                $location_dest = str_replace ($rootpathold_array[$root_id], $rootpathnew_array[$root_id], $location_source_esc);
                $site_dest = getpublication ($rootpathnew_array[$root_id]);

                // paste object using the result clipboard entries as input without touching the session clipboard
                $test = pasteobject ($site_dest, $location_dest, $user, $result['clipboard']);
              }
              else
              {
                $test['result'] = false;
                $test['message'] = $result['message'];
              }

              if (!empty ($test['result']))
              {
                unset ($collection[$i]);
              }
              else 
              {
                $errcode = "20110";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|pasteobject failed for ".$location_source_esc.$object_source;
                
                // break if method is cut since the source object will be deleted
                if ($method == "cut") break;
                // avoid break of process
                else
                {
                  $test['result'] = true;
                  $result['report'][] = $test['message']." Error: pasteobject failed for ".$location_source_esc.$object_source;
                }
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

        // continue
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
    if (isset ($result['working']) && $result['working'] == false && !empty ($test['result']) && is_array ($rootpathdelete_array) && sizeof ($rootpathdelete_array) > 0)
    {
      reset ($rootpathdelete_array);

      // action = delete
      if ($action == "delete")
      {
        foreach ($rootpathdelete_array as $objectpath)
        {
          if ($objectpath != "")
          {
            $site = getpublication ($objectpath); 
            $folder = getobject ($objectpath); // could be a object file or a folder
            $location = getlocation ($objectpath);  // location without folder
            $location = deconvertpath ($location, "file");

            if (valid_publicationname ($site) && valid_locationname ($location) && $folder != "" && is_dir ($location.$folder))
            {
              // eventsystem
              if (isset ($eventsystem['ondeletefolder_pre']) && $eventsystem['ondeletefolder_pre'] == 1 && empty ($eventsystem['hide'])) 
                ondeletefolder_pre ($site, $cat, $location, $folder, $user);
    
              // remove all in the root folder
              $test['result'] = deletefile ($location, $folder, true);

              // remote client
              remoteclient ("delete", "abs_path_".$cat, $site, $location, "", $folder, "");

              // eventsystem
              if (isset ($eventsystem['ondeletefolder_post']) && $eventsystem['ondeletefolder_post'] == 1 && empty ($eventsystem['hide']) && !empty ($test['result'])) 
                ondeletefolder_post ($site, $cat, $location, $folder, $user); 
            }
          }
        }
      }
      // action = cut & paste
      elseif ($action == "paste" && $method == "cut")
      {
        foreach ($rootpathdelete_array as $temp_id => $temp_path)
        {
          if ($temp_path != "")
          {
            $site = getpublication ($temp_path);
            $location = deconvertpath ($temp_path, "file");
            $folder = getobject ($location); // could be a file or a folder 
            $location = getlocation ($location);  // location without folder
 
            if (valid_locationname ($location) && $folder != "" && is_dir ($location.$folder))
            {
              // remove all in the root folder
              $test['result'] = deletefile ($location, $folder, true);

              if (!empty ($test['result']))
              {
                $errcode = "00713";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|Root folder ".$location.$folder." has been removed after cut & paste action of user '".$user."'"; 
              }

              $test_renamegroup = renamegroupfolder ($site, $cat, $rootpathdelete_array[$temp_id], $rootpathnew_array[$temp_id], $user);

              if ($test_renamegroup == false)
              {
                $errcode = "10714";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|renamegroupfolder failed for ".$rootpathdelete_array[$temp_id]." to ".$rootpathnew_array[$temp_id]; 
              }

              $test_renameworkflow = renameworkflowfolder ($site, $cat, $rootpathdelete_array[$temp_id], $rootpathnew_array[$temp_id], $user); 
 
              if ($test_renameworkflow == false)
              {
                $errcode = "10715";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|renameworkflowfolder failed for ".$rootpathdelete_array[$temp_id]." to ".$rootpathnew_array[$temp_id]; 
              }
   
              // remote client
              remoteclient ("delete", "abs_path_".$cat, $site, $location, "", $folder, ""); 
            }
          }
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
    if (isset ($test['result']) && !empty ($test['result'])) $result['result'] = true;
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

    // save log
    savelog (@$error);
  }

  return $result;
}

// ---------------------- createqueueentry -----------------------------
// function: createqueueentry()
// input: action [publish,unpublish,delete,mail,exec], object path [string] or object ID [integer], date and time [YYY-MM-DD HH:MM], publish only published objects [boolean], PHP command as string or data as array to be saved in the queue [string or array] (optional), user name [string]
// output: true / false

// description:
// Creates a new item in the queue

function createqueueentry ($action, $object, $date, $published_only, $data="", $user="")
{
  global $mgmt_config;

  // initialize
  $error = array();

  if ($action != "" && ($object == "" || substr_count ($object, "%page%") > 0 || substr_count ($object, "%comp%") > 0 || intval ($object) > 0) && is_date ($date, "Y-m-d H:i") && valid_objectname ($user))
  {
    // correct date and time based on users time zone
    if ($date != "" && !empty ($_SESSION['hcms_timezone']) && ini_get ('date.timezone'))
    {
      $datenew = convertdate ($date, $_SESSION['hcms_timezone'], "Y-m-d H:i", ini_get ('date.timezone'), "Y-m-d H:i");
      if (!empty ($datenew)) $date = $datenew;
    }

    // queue entry with additional queue data in the database
    if (!empty ($data) && is_string ($data) && trim ($data) != "" && strlen ($data) <= 21000)
    {
      return rdbms_createqueueentry ($action, $object, $date, $published_only, $data, $user);
    }
    // queue entry with additional external queue data
    elseif (!empty ($data) && is_array ($data) && sizeof ($data) > 0)
    {
      // define php variables
      $data_str = "";

      foreach ($data as $key=>$value)
      {
        $data_str  .= "\$".$key." = ".var_export ($value, true).";\n";
      }

      if ($data_str != "")
      {
        $data_str = "<?php\n".$data_str."?>";

        // create document ID
        if (intval ($object) < 1) $queue_id = rand_secure (10000, 99999999);
        else $queue_id = intval ($object);

        // create queue directory
        if (!is_dir ($mgmt_config['abs_path_data']."queue/")) mkdir ($mgmt_config['abs_path_data']."queue/", $mgmt_config['fspermission']);

        // save file in queue
        $queue_file = $queue_id.".".$user.".".strtolower($action).".php";
        $savefile = savefile ($mgmt_config['abs_path_data']."queue/", $queue_file, $data_str);

        // create queue entry
        if ($savefile) return rdbms_createqueueentry ($action, $queue_id, $date, $published_only, "", $user);
        else return false;
      }
    }
    // queue entry with no additional data
    else
    {
      return rdbms_createqueueentry ($action, $object, $date, $published_only, "", $user);
    }
  }
  else return false;
}

// ---------------------- savemessage -----------------------------
// function: savemessage()
// input: data to be saved in queue [array], message type [mail,chat] (optional), user name [string]
// output: true / false

// description:
// Saves the data of a sent e-mail message.

function savemessage ($data, $type="mail", $user="")
{
  global $mgmt_config;

  $error = array();

  if (!empty ($data) && is_array ($data) && (strtolower ($type) == "mail" || strtolower ($type) == "chat") && valid_objectname ($user))
  {
    // define php variables
    $data_str = "";

    foreach ($data as $key=>$value)
    {
      $data_str  .= "\$".$key." = ".var_export ($value, true).";\n";
    }

    if ($data_str != "")
    {
      $data_str = "<?php\n".$data_str."?>";

      // create mail document ID
      $mail_id = time ();

      // create mail directory
      if (!is_dir ($mgmt_config['abs_path_data']."message/")) mkdir ($mgmt_config['abs_path_data']."message/", $mgmt_config['fspermission']);

      // save file
      $mail_file = $mail_id.".".$user.".".strtolower ($type).".php";
      return savefile ($mgmt_config['abs_path_data']."message/", $mail_file, $data_str);
    }
    else return false;
  }
  else return false;
}

// ---------------------- remoteclient -----------------------------
// function: remoteclient()
// input: action [save,copy,delete,rename,get], root [abs_path_link,abs_path_media,abs_path_comp,abs_path_page,abs_path_rep], publication name [string], location [string], new location [string], object name [string], new object name [string]
// output: HTTP response body [string] / false

// description:
// Sends data to remote client via http post

function remoteclient ($action, $root, $site, $location, $locationnew, $page, $pagenew)
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  $error = array();

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
        // page, component or asset
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
              if (filesize ($location.$page) > 0) $content = fread ($handle, filesize ($location.$page));
              else $content = "";

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
      $result = HTTP_Post ($mgmt_config[$site]['remoteclient'], $data, $encoding="application/x-www-form-urlencoded", "UTF-8", "", "full");

      // error log
      if (substr_count ($result, "HTTP/1.1 200 OK") == 0 || substr_count ($result, "ERROR") == 1)
      {
        $errcode = "10601";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|remoteclient failed for '".$action."' on ".$location.$page;

        // save log
        savelog (@$error); 
      }

      return $result;
    }
    else return false;
  }
  else return false;
}

// ---------------------- HTTP_getheader -----------------------------
// function: HTTP_getheader()
// input: HTTP response [string]
// output: HTTP header [array] / false on error

// description:
// Extracts the HTTP headers from the response string (header and body are seperated by an empty line)

function HTTP_getheader ($response)
{
  if (strpos ($response, "\r\n\r\n") > 0)
  {
    $headers = array();
    $headertext = substr ($response, 0, strpos ($response, "\r\n\r\n"));

    foreach (explode ("\r\n", $headertext) as $i => $line)
    {
      if ($i === 0)
      {
        $headers['http_code'] = $line;
      }
      else
      {
        list ($key, $value) = explode (': ', $line);
        $headers[$key] = $value;
      }
    }

    return $headers;
  }
  else return false;
}

// ---------------------- HTTP_getbody -----------------------------
// function: HTTP_getbody()
// input: HTTP response [string]
// output: HTTP body [string] / false on error

// description:
// Extracts the HTTP body from the response string (header and body are seperated by an empty line)

function HTTP_getbody ($response)
{
  if (strpos ($response, "\r\n\r\n") > 0)
  {
    $body = substr ($response, strpos ($response, "\r\n\r\n") + 4);

    return trim ($body);
  }
  else return false;
}

// ---------------------- HTTP_Post -----------------------------
// function: HTTP_Post()
// input: URL [string], data (raw data) [array], content-type [application/x-www-form-urlencoded,multipart/form-data] (optional), character set [string] (optional), referrer [string] (optional), response type [full,header,body] (optional)
// output: HTTP response [string], header [array], or body [string] based on the requested response type / false on error

// description:
// Sends data via HTTP post and returns the response body

function HTTP_Post ($URL, $data, $contenttype="application/x-www-form-urlencoded", $charset="UTF-8", $referrer="", $response_type="body") 
{
  global $mgmt_config;

  $error = array();

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
        $data_string = http_build_query ($data);
      }

      $request .= "POST ".$URL_Info["path"]." HTTP/1.1\r\n";
      $request .= "Host: ".$URL_Info["host"]."\r\n";
      $request .= "Referer: ".$referrer."\r\n";
      $request .= "Content-type: ".$contenttype."; charset=".$charset."\r\n";
      $request .= "Content-length: ".strlen ($data_string)."\r\n";
      $request .= "Connection: close\r\n";
      $request .= "\r\n";
      $request .= $data_string;
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
            $data_string .= "--$boundary\r\n";
            $data_string .= "Content-Disposition: form-data; name=\"".$key."\"\r\n\r\n".$val."\r\n";
          }
        }
 
        $data_string .= "--$boundary\r\n";
      }

      $request .= "POST ".$URL_Info["path"]." HTTP/1.0\r\n";
      $request .= "Host: ".$URL_Info["host"]."\r\n";
      if (!empty ($referrer)) $request .= "Referer: ".$referrer."\r\n";
      //$request .= "Keep-Alive: 300\n";
      //$request .= "Connection: keep-alive\n";
      $request .= "Content-type: multipart/form-data; boundary=".$boundary."\r\n";

      // collect FILE data
      if ($data['page'] != "")
      {
        $data_string .= "Content-Disposition: form-data; name=\"Filedata\"; filename=\"".$data['page']."\"\r\n";
        $data_string .= "Content-Type: ".getmimetype ($data['page'])."\r\n";
        $data_string .= "Content-Transfer-Encoding: binary\r\n\r\n";
        $data_string .= $data['content']."\r\n";
        $data_string .= "--$boundary--\r\n";
      }

      $request .= "Content-length: ".strlen($data_string)."\r\n";
      $request .= "\r\n";
      $request .= $data_string;
    }
 
    $fp = @fsockopen ($Host_protocol.$URL_Info["host"], $URL_Info["port"]);

    $result = "";

    if ($fp)
    {
      @fwrite ($fp, $request);

      while (!feof ($fp)) 
      {
        $result .= @fgets ($fp, 1024);
      }

      @fclose ($fp);

      // deprecated since version 9.1.5
      // remove HTTP header information from the xml/html-document
      // if (strpos ($result, "<") > 0) $result = substr ($result, strpos ($result, "<"), strrpos ($result, ">") - strpos ($result, "<") + 1);
      // remove HTTP header information from the JSON string
      // elseif (strpos ($result, "{") > 0) $result = substr ($result, strpos ($result, "{"), strrpos ($result, "}") - strpos ($result, "{") + 1);

      // remove HTTP body information from the response
      if (strtolower ($response_type) == "header") $result = HTTP_getheader ($result);
      // remove HTTP header information from the response
      elseif (strtolower ($response_type) == "body") $result = HTTP_getbody ($result);
    }
    else $result = false;
 
    return $result;
  }
  else return false;
}

// ---------------------- HTTP_Get -----------------------------
// function: HTTP_Get()
// input: URL [string],  data (raw data) [array] (optional), content-type [string excl. charset] (optional), character set [string] (optional), response type [full,header,body] (optional)
// output: HTTP response [string], header [array], or body [string] based on the requested response type / false on error

// description:
// Sends data via http get and returns response

function HTTP_Get ($URL, $data="", $contenttype="application/x-www-form-urlencoded", $charset="UTF-8", $response_type="body") 
{
  global $mgmt_config;
  
  $error = array();

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

      @fclose ($fp);

      // remove HTTP body information from the response
      if (strtolower ($response_type) == "header") $result = HTTP_getheader ($result);
      // remove HTTP header information from the response
      elseif (strtolower ($response_type) == "body") $result = HTTP_getbody ($result);
    }
    else $result = false;

    return $result;
  }
  else return false;
}

// ---------------------- HTTP_Proxy -----------------------------
// function: HTTP_Proxy()
// input: URL [string], enable post of files [boolean] (optional)
// output: http response [string] / false on error
// requires: PHP CURL

// description:
// Sends all global POST/GET and FILES data via http post and returns response

function HTTP_Proxy ($URL, $enable_file=false) 
{
  global $mgmt_config;
  
  $error = array();

  if ($URL != "" && substr_count ($URL, "://") > 0 && !empty ($_REQUEST) && is_array ($_REQUEST))
  {
    // define data to be posted/redirected (GET/POST/HEADER)
    $data = $_REQUEST;

    // define file
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
          $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Uploaded file ".$_FILES['Filedata']['name']." could not be saved in temp directory";

          savelog (@$error);
        }
      }
    }


    // It is important to notice that when using curl to post form data and you use an array for CURLOPT_POSTFIELDS option, the post will be in multipart format
    // Setting CURLOPT_POSTFIELDS as follow produce a standard post header CURLOPT_POSTFIELDS => http_build_query ($data)

    $options = array(
        CURLOPT_POST           => true, // send a POST request 
        CURLOPT_RETURNTRANSFER => true, // to receive the response that the site gives after it receives the request
        CURLOPT_HEADER         => false, // return HTTP headers in response
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

    // send the request
    $response = curl_exec ($ch);

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
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|HTTP_Proxy failed with error (".$error_no.") ".$error_message;

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
    $response = "Invalid input for PROXY service";
  }

  // return result
  $result = array();
  $result['header'] = $header;
  $result['message'] = $response;

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
  
  $error = array();

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
// input: error log entries [array], name of log file without extension [string] (optional)
// output: true / false on error

// description:
// Adds new entries to a log file.
// A log entry must be formed like:
// date [YYYY-MM-DD hh:mm]|name of script file [string]|type ["error", "warning" or "information"]|unique code in script file [integer]|description [string]
// Use a tabspace /t for a line break in the description for the full view using popup_log.

function savelog ($error, $logfile="event")
{
  global $user, $login, $site, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // archive log file when it reached a certain file size in MB
  $archive_size = 100;

  // verify event logging based on log level
  if (empty ($mgmt_config['loglevel']) || strtolower ($mgmt_config['loglevel']) == "all")  $log_event = true;
  elseif (strtolower ($mgmt_config['loglevel']) == "warning" && strpos ($log, "|error|") > 0 || strpos ($log, "|warning|") > 0) $log_event = true;
  elseif (strtolower ($mgmt_config['loglevel']) == "error" && strpos ($log, "|error|") > 0) $log_event = true;
  elseif (strtolower ($mgmt_config['loglevel']) == "none") $log_event = false;

  if (is_array ($error) && sizeof ($error) > 0 && $logfile != "" && !empty ($log_event))
  {
    // publication management config
    if (valid_publicationname ($site) && !isset ($mgmt_config[$site]['eventlog_notify']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // get user
    if (empty ($user) && getsession ("hcms_user")) $user = getsession ("hcms_user");
    elseif (empty ($user) && !empty ($login)) $user = $login;

    // file name of log
    $logfile = $logfile.".log";

    // save error message
    $log_array = $error;

    // replace newlines with tab space
    foreach ($error as &$value)
    {
      $value = str_replace ("\n\r", "\t", $value);
      $value = str_replace ("\r\n", "\t", $value);
      $value = str_replace ("\r", "\t", $value);
      $value = str_replace ("\n", "\t", $value);
    }

    // save log
    // append log data to file or archive file if the file size is too big
    if (is_file ($mgmt_config['abs_path_data']."log/".$logfile) && filesize ($mgmt_config['abs_path_data']."log/".$logfile) < ($archive_size * 1024 * 1024))
    { 
      $result = appendfile ($mgmt_config['abs_path_data']."log/", $logfile, implode ("\n", $error)."\n");
    }
    else
    {
      // archive log
      if (is_file ($mgmt_config['abs_path_data']."log/".$logfile)) copy ($mgmt_config['abs_path_data']."log/".$logfile, $mgmt_config['abs_path_data']."log/".date("Y-m-d").".".$logfile);

      // start new log
      $result = savefile ($mgmt_config['abs_path_data']."log/", $logfile, implode ("\n", $error)."\n");
    }

    // save publication log
    if (!empty ($mgmt_config['publication_log']) && valid_publicationname ($site) && $logfile == "event.log")
    {
      // append log data to file or archive file if the file size is too big
      if (is_file ($mgmt_config['abs_path_data']."log/".$site.".publication.log") && filesize ($mgmt_config['abs_path_data']."log/".$site.".publication.log") < ($archive_size * 1024 * 1024))
      { 
        appendfile ($mgmt_config['abs_path_data']."log/", $site.".publication.log", implode ("\n", $error)."\n");
      }
      else
      {
        // archive log
        if (is_file ($mgmt_config['abs_path_data']."log/".$site.".publication.log")) copy ($mgmt_config['abs_path_data']."log/".$site.".publication.log", $mgmt_config['abs_path_data']."log/".date("Y-m-d").".".$site.".publication.log");

        // start new log
        savefile ($mgmt_config['abs_path_data']."log/", $site.".publication.log", implode ("\n", $error)."\n");
      }
    }

    // save user log (exclude system service users)
    if (!empty ($mgmt_config['user_log']) && valid_objectname ($user) && substr ($user, 0, 4) != "sys:" && $logfile == "event.log")
    {
      // append log data to file or archive file if the file size is too big
      if (is_file ($mgmt_config['abs_path_data']."log/".$user.".user.log") && filesize ($mgmt_config['abs_path_data']."log/".$user.".user.log") < ($archive_size * 1024 * 1024))
      { 
        appendfile ($mgmt_config['abs_path_data']."log/", $user.".user.log", implode ("\n", $error)."\n");
      }
      else
      {
        // archive log
        if (is_file ($mgmt_config['abs_path_data']."log/".$user.".user.log")) copy ($mgmt_config['abs_path_data']."log/".$user.".user.log", $mgmt_config['abs_path_data']."log/".date("Y-m-d").".".$user.".user.log");

        // start new log
        savefile ($mgmt_config['abs_path_data']."log/", $user.".user.log", implode ("\n", $error)."\n");
      }
    }

    // send notifications to users in case of errors or warnings
    if (!empty ($mgmt_config['eventlog_notify']) || !empty ($mgmt_config[$site]['eventlog_notify']))
    {
      foreach ($log_array as $log)
      {
        if (is_string ($log) && strpos ($log, "|error|") > 0 || strpos ($log, "|warning|") > 0)
        {
          // prepare log data
          list ($date, $source, $type, $errorcode, $description) = explode ("|", trim ($log));

          // define message
          $message = "URL: ".$mgmt_config['url_path_cms']."\nDate: ".$date."\nSource: ".$source."\nType: ".$type."\nCode: ".$errorcode."\nDescription: ".$description."\n";

          // get users from string (1st priority for publication specific user entries)
          if (!empty ($mgmt_config[$site]['eventlog_notify'])) $to_user_array = splitstring ($mgmt_config[$site]['eventlog_notify']);
          elseif (!empty ($mgmt_config['eventlog_notify'])) $to_user_array = splitstring ($mgmt_config['eventlog_notify']);

          // remove duplicates
          $to_user_array = array_unique ($to_user_array);

          if (is_array ($to_user_array) && sizeof ($to_user_array) > 0)
          {
            foreach ($to_user_array as $to_user)
            {
              sendmessage ("", $to_user, "System Error or Warning", $message, "", $site);
            }
          }
        }
      }
    }

    return $result;
  }
  else return false;
}

// --------------------------------------- loadlog -------------------------------------------
// function: loadlog()
// input: name of log file without extension [string] (optional), return type [string,array] (optional)
// output: true / false on error

// description: 
// Loads a log file an returns the data as string or array for all log records.

function loadlog ($logfile="event", $return_type="array")
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;
 
  if ($logfile != "" && is_file ($mgmt_config['abs_path_data']."log/".$logfile.".log"))
  {
    // file name of log
    $logfile = $logfile.".log";

    if (strtolower ($return_type) == "string")
    {
      return loadfile ($mgmt_config['abs_path_data']."log/", $logfile);
    }
    else
    {
      return file ($mgmt_config['abs_path_data']."log/".$logfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
  }
  else return false;
}

// --------------------------------------- deletelog -------------------------------------------
// function: deletelog()
// input: log name [string] (optional)
// output: result array

// description:
// Deletes a log file.

function deletelog ($logname="")
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $error = array();
  $result = array();
  $add_onload = "";
  $show = "";

  // set default language
  if (empty ($lang)) $lang = "en";

  // file name of log
  if ($logname != "") $logfile = $logname.".log";
  else $logfile = "event.log";

  if (is_file ($mgmt_config['abs_path_data']."log/".$logfile))
  {
    $test = deletefile ($mgmt_config['abs_path_data']."log/", $logfile);

    if ($test == true)
    {
      if (strpos ($logname, ".") > 0) $site = substr ($logname, 0, strpos ($logname, "."));
      else $site = "";

      $add_onload = "parent.frames['mainFrame'].location='log_list.php?site=".url_encode($site)."'; ";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['cleared-all-events-from-list'][$lang]."</span>\n";

      $errcode = "00821";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|Log ".$logname." has been deleted by user ".$user;
    }
    else
    {
      $add_onload = "";
      $show = "<span class=\"hcmsHeadline\">".$hcms_lang['events-list-could-not-be-cleared'][$lang]."</span><br />\n".$hcms_lang['event-log-does-not-exist-or-you-do-not-have-write-permissions'][$lang]."\n";

      $errcode = "10822";
      $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Log ".$logname." could not be deleted by user ".$user;
    }
  }

  // save log
  savelog (@$error);

  $result['add_onload'] = $add_onload;
  $result['message'] = $show;

  return $result;
}

// ---------------------- debuglog -----------------------------
// function: debuglog()
// input: code to write to debug file [string]
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

// --------------------------------------- checksoftwareversion -------------------------------------------
// function: checksoftwareversion()
// input: software version for comparison with installed version [string]
// output: true / false

// description: 
// Compares the installed or updated software version with a provided version and returns true if the provided version has been installed or updated.

function checksoftwareversion ($version)
{
  global $mgmt_config;

  $logdata = loadlog ("update", "string");
 
  // get installed version
  if (!empty ($version) && !empty ($logdata))
  {
    // extract version number from input
    if (strpos ($version, " ") > 0) $version_compare = substr ($version, (strpos ($version, " ") + 1));
    else $version_compare = $version;

    // extract installed version number from update log
    if (strpos ($logdata, "|installation|") > 0)
    {
      $start = strpos ($logdata, "|installation|") + strlen ("|installation|");
      $end = strpos ($logdata, "|", $start);
      $version_installed = substr ($logdata, $start, ($end - $start));
    }

    // compare with updated version
    if (strpos ($logdata, "|".$version_compare."|") > 0) return true;
    // compare with installed version
    elseif (!empty ($version_installed) && version_compare ($version_installed, $version_compare, '>=')) return true;
  }

  return false;
}

// ====================================== SPECIAL NOTIFICATIONS =========================================

// --------------------------------------- notifyusers -------------------------------------------
// function: notifyusers()
// input: publication name [string], location [string], object name [string], event name [oncreate,onedit,onmove,ondelete], user name [string]
// output: true / false on error

// description:
// Notifies all users based on the given event and location

function notifyusers ($site, $location, $object, $event, $user_from)
{
  global $user, $mgmt_config, $hcms_lang_codepage, $hcms_lang, $lang;

  // initialize
  $error = array();

  // set default language
  if (empty ($lang)) $lang = "en";
  $lang_to = "en";

  $location = deconvertpath ($location, "file"); 

  // create file name
  if (specialchr ($object, ".-_~") == true) $object = createfilename ($object);

  // include hypermailer class
  if (!class_exists ("HyperMailer")) require ($mgmt_config['abs_path_cms']."function/hypermailer.class.php");

  if ($event != "" && valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($object) && valid_objectname ($user_from) && (empty ($mgmt_config['notify_exclude_users']) || strpos ("_;".$mgmt_config['notify_exclude_users'].";", ";".$user_from.";") < 1))
  {
    $mail_sent = false;

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

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
            // get user node of sender and extract required information
            $usernode_from = selectcontent ($userdata, "<user>", "<login>", $user_from);

            if (!empty ($usernode_from[0])) $temp = getcontent ($usernode_from[0], "<email>");

            if (!empty ($temp[0])) $email_from = $temp[0];
            else $email_from = "";
            
            // get user node of recipient and extract required information
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

            if (!empty ($email_to))
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
              if (!empty ($email_from)) $sender = $user_from." [<a href=\"mailto:".$email_from."\">".$email_from."</a>]";
              else $sender = $user_from;

              $mail_title = $hcms_lang['hypercms-notification'][$lang_to];
              $mail_fullbody = "<b>".str_replace ("%user%", $sender, $text_opt)."</b>\n";
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
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|Notification has been sent to ".$notify['user']." (".$email_to.") on object ".$location_esc.$object; 
              }
              else
              {
                $errcode = "50802";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Notification failed for ".$notify['user']." (".$email_to.") on object ".$location_esc.$object." (mail could not be sent)";
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
// input: publication name [string], category [page,comp], folder path [string], text ID for text field [string], search from date [YYYY-MM-DD], search till date [YYYY-MM-DD], user name [string or array] (optional), 
//        date format (optional), 
// output: true / false on error

// description:
// Searches for objects with a date in a defined text field that has to be between the defined date limits and sends a message to the defined users.
// This is a helper function for function licensenotification.

function sendlicensenotification ($site, $cat, $folderpath, $text_id, $date_begin, $date_end, $user, $format="%Y-%m-%d")
{
  global $eventsystem, $mgmt_config, $hcms_lang_codepage, $hcms_lang, $lang;

  // initialize
  $error = array();
  $mail_sent = false;

  // set default language
  if (empty ($lang)) $lang = "en";

  // include hypermailer class
  if (!class_exists ("HyperMailer")) require ($mgmt_config['abs_path_cms']."function/hypermailer.class.php");

  if (valid_publicationname ($site) && $cat != "" && valid_locationname ($folderpath) && valid_objectname ($text_id) && $date_begin != "" && $date_end != "" && (valid_objectname ($user) || is_array ($user)))
  {
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
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|information|".$errcode."|License notification was sent to $email_to for $folderpath, $text_id, $date_begin, $date_end, $user";
              }
              else
              {
                $errcode = "50902";
                $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|License notification failed for $folderpath, $text_id, $date_begin, $date_end, $user (mail could not be sent)";
              }
            }
            else
            {
              $errcode = "50903";
              $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|License notification failed for $folderpath, $text_id, $date_begin, $date_end, $user (e-mail address does not exist)";
            }
          }
          else
          {
            $errcode = "50904";
            $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|License notification failed for $folderpath, $text_id, $date_begin, $date_end, $user (user does not exist)";
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

  $error = array();

  // license notification configuration file
  $scandir = scandir ($mgmt_config['abs_path_data']."config/");

  if ($scandir)
  {
    foreach ($scandir as $file)
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
    $error[] = $mgmt_config['today']."|hypercms_main.php|error|".$errcode."|License notification can not be executed. Config directory is missing.";

    // save log
    savelog (@$error); 

    return false;
  }
}

// --------------------------------------- sendresetpassword ------------------------------------------------
// function: sendresetpassword()
// input: user name [string], type of password reset [multifactorauth,passwordreset] (optional), instance name [string] (optional)
// output: message as string

// description:
// Send a new password to the users e-mail address.

function sendresetpassword ($login, $type="passwordreset", $instance="")
{
  global $eventsystem, $mgmt_config, $hcms_lang, $lang;

  // initialize
  $error = array();
  $message = "";

  if (empty ($lang)) $lang = "en";

  // verifications
  if (empty ($mgmt_config['sso']) && empty ($mgmt_config['passwordreset']) && empty ($mgmt_config['multifactorauth'])) return $hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang];
  if ($login == "") return $hcms_lang['a-user-name-is-required'][$lang];

  // create new password
  if (strtolower ($type) == "multifactorauth")
  {
    // get time of last password reset
    if (is_file ($mgmt_config['abs_path_temp'].$login.".passwordtime.dat")) $resettime = loadfile ($mgmt_config['abs_path_temp'], $login.".passwordtime.dat");

    // define new password after timeout (due to WebDAV client)
    if (empty ($resettime) || (intval ($resettime) > 0 && intval ($resettime) < (time() - 3))) $password = "At".createpassword (8);
  }
  // reset password with support of old an new value for the type
  elseif (strtolower ($type) == "passwordreset" || strtolower ($type) == "resetpassword")
  {
    $password = "At".createpassword (8);
  }

  // get e-mail and first publication of user
  $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");
  $usernode = selectcontent ($userdata, "<user>", "<login>", $login);

  // if user node does not exists
  if (empty ($usernode[0]))
  {
    return $hcms_lang['the-user-information-cant-be-accessed'][$lang];
  }
  else
  {
    $email = getcontent ($usernode[0], "<email>");
    $nologon = getcontent ($usernode[0], "<nologon>");
  }

  // no logon allowed
  if (!empty ($nologon[0]))
  {
    return str_replace ("%user%", $login, $hcms_lang['you-dont-have-permissions-to-use-this-function'][$lang]);
  }
  // e-mail and new password are available
  elseif (!empty ($email[0]) && !empty ($password))
  {
    // do not apply strong password rules for automatically created password
    $mgmt_config['strongpassword'] = false;

    // change password
    $result = edituser ("*Leave*", $login, "", $password, $password, "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "sys");

    if (empty ($result['result'])) return $result['message'];

    // 2-factor authentication logon using the provided password via e-mail (access link will not be provided since version 9.0.6 for improved security)
    if (strtolower ($type) == "multifactorauth")
    {
      // $message .= $hcms_lang['link'][$lang].": <a href=\"".$mgmt_config['url_path_cms']."?login=".url_encode($login)."&instance=".url_encode($instance)."\">".$hcms_lang['access-link'][$lang]."</a>\n\n";
      $message .= $hcms_lang['password'][$lang].": ".$password."\n\n";

      // create temp file in order to save time stamp for user and avoid multiple resets (due to WebDAV client)
      savefile ($mgmt_config['abs_path_temp'], $login.".passwordtime.dat", time());
    }
    // temporary password for the password reset
    else
    {
      // $message .= $hcms_lang['link'][$lang].": <a href=\"".$mgmt_config['url_path_cms']."resetpassword.php?instance=".url_encode($instance)."&old_password=".url_encode($password)."\">".$hcms_lang['reset-password'][$lang]."</a>\n\n";
      $message .= $hcms_lang['password'][$lang].": ".$password."\n\n";

      // create temp file in order to force password reset after login
      savefile ($mgmt_config['abs_path_temp'], $login.".resetpassword.dat", "1");
    }

    $message .= $hcms_lang['this-is-an-automatically-generated-mail-notification'][$lang];

    // send mail
    $mail = sendmessage ("", $login, $hcms_lang['password'][$lang], $message);

    // save log
    $errcode = "00750";
    $error[] = $mgmt_config['today']."|hypercms_main.php|information|".$errcode."|A new password has been sent to user '".$login."'";
    savelog (@$error); 

    if ($mail == false) return $hcms_lang['there-was-an-error-sending-the-e-mail-to-'][$lang]." ".$email[0];
    else return $hcms_lang['e-mail-was-sent-successfully-to-'][$lang]." ".$email[0];
  }
  // if e-mail is empty
  else
  {
    return str_replace ("%user%", $login, $hcms_lang['e-mail-address-of-user-s-is-missing'][$lang]);
  }
}

// ====================================== TEXT DIFF =========================================

// --------------------------------------- html_diff -------------------------------------------
// function: html_diff ()
// input: old text [string], new text [string], maximum words to compare [integer]
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
// input: publication name [string] (optional), location [string] (optional), object name [string] (optional), identifier (object ID, object hash) [string] (optional), user name [string]
// output: true / false

function createfavorite ($site="", $location="", $page="", $id="", $user="")
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
      $location = correctpath ($location);

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

    // save object id in favorites
    if ($object_id > 0)
    {
      // portal user session
      if (getsession ("hcms_portal"))
      {
        if (!isset ($_SESSION['hcms_favorites'])) $_SESSION['hcms_favorites'] = array();

        $_SESSION['hcms_favorites'][$object_id] = $object_id;

        // write session data for load balacing
        writesessiondata ();

        return true;
      }
      // standard user
      elseif (is_file ($dir.$file))
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

// --------------------------------------- deletefavorite -------------------------------------------
// function: deletefavorite ()
// input: publication name [string] (optional), location [string] (optional), object name [string] (optional), identifier (object ID, object hash) [string] (optional), user name [string]
// output: true / false

function deletefavorite ($site="", $location="", $page="", $id="", $user="")
{
  global $mgmt_config;

  if (valid_objectname ($user))
  {
    // get object ID
    if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page))
    {
      // define category if undefined
      $cat = getcategory ($site, $location);

      // add slash if not present at the end of the location string
      $location = correctpath ($location);

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
    if (!empty ($object_id))
    {
      // portal user session
      if (getsession ("hcms_portal"))
      {
        if (!empty ($_SESSION['hcms_favorites'][$object_id])) unset ($_SESSION['hcms_favorites'][$object_id]);
        return true;
      }
      // standard user
      else
      {
        $dir = $mgmt_config['abs_path_data']."checkout/";
        $file = $user.".fav";

        if (is_file ($dir.$file))
        {
          $data = loadfile ($dir, $file);

          if ($data != false && trim ($data) != "") $data = str_replace ("|".$object_id."|", "|", $data);

          return savefile ($dir, $file, $data);
        }
        else return false;
      }
    }
    else return false;
  }
  else return false;
}

// ========================================== URL REWRITING =======================================

// ------------------------------------- rewrite_targetURI ------------------------------------------

// function: rewrite_targetURI ()
// input: publication name [string], text ID array (text-ID as key and URL parameters as value) to search for in the database [string], 
//        requested URI [string], exclude path [array] (optional), rewrite type [none,forward,include] (optional)
// output: HTTP header with target URL as location / false on error

function rewrite_targetURI ($site, $text_id, $uri, $exclude_dir_esc="", $rewrite_type="include")
{
  global $mgmt_config, $publ_config;

  if (valid_publicationname ($site) && is_array ($text_id) && $uri != "")
  {
    $hypercms_session = array();

    // prepare search for URI
    $uri = cleandomain ($uri);

    // split parameters from URI
    $parts = explode ('?', $uri, 2);
    if (!empty ($parts[0])) $uri = $parts[0];
    if (!empty ($parts[1])) $uri_parameters = $parts[1];

    // include publication target settings
    $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"); 

    foreach ($text_id as $id=>$parameter)
    {
      if ($id != "")
      {
        $search_textnode = array();
        $search_textnode[$id] = trim ($uri);

        // disable search history log
        $mgmt_config['search_log'] = false;

        // search for exact term
        $mgmt_config['search_exact'] = true;

        // force "like" match (will be also set in function rdbms_searchcontent for search expressions which include / as character)
        $mgmt_config['search_query_match'] = "like";

        // search for objectpath for the provided permanenent link (only first valid result will be used, disable search log)
        $object_array = rdbms_searchcontent ("%page%/".$site."/", $exclude_dir_esc, "", "", "", "", $search_textnode, "", "", "", "", "", "", "", "", "", 1, false, false);

        if (is_array ($object_array))
        {
          // get first element of array
          $targetPath = reset ($object_array);

          if ($targetPath != "")
          {
            $targetFile = str_replace ("%page%/".$site."/", $publ_config['abs_publ_page'], $targetPath['objectpath']);
            $targetURI = str_replace ("%page%/".$site."/", $publ_config['url_publ_page'], $targetPath['objectpath']);

            // add parameters
            if ($parameter != "")
            {
              // set parameter in hypercms array variable, so it can be set in the session
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

            // add GET parameters from provided URI
            if (!empty ($uri_parameters))
            {
              if (strpos ($result, "?") > 0) $result .= "&".$uri_parameters;
              else $result .= "?".$uri_parameters;
            }

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
        chdir (getlocation ($targetFile));
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
// input: publication name [string], rewrite type [none,forward] (optional)
// output: HTTP header with target URL as location / false on error

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

// ========================================== CSV =======================================

// --------------------------------------- load_csv -------------------------------------------
// function: load_csv ()
// input: path to CSV file [string], delimiter [string] (optional), enclosure [string] (optional), character set of the source data [string] (optional), character set of the output data [string] (optional)
// output: array / false on error

// description:
// Analyzes the content from the CSV file and detects delimiter and enclosure characters if left empty. On success the data will be returned as array starting with a row index of 1.

function load_csv ($file, $delimiter=";", $enclosure='"', $charset_from="utf-8", $charset_to="utf-8")
{
  global $mgmt_config, $eventsystem;

  // initialize
  $result = array();
  $temp_file = false;

  // define possible delimiters and enclosures
  $delimiters_csv = array (",", ";", "\t", "|");
  $enclosures_csv = array ('"', "'");

  if ($file != "" && is_file ($file))
  {
    $data = file_get_contents ($file);

    // prepare and save CSV data
    if ($data != "")
    {
      // remove UTF-8 BOM
      $data_new = remove_utf8_bom ($data);

      // detect charset
      if (empty ($charset_from) && function_exists ("mb_detect_encoding")) 
      {
        $charset_from = mb_detect_encoding ($data_new, mb_detect_order(), true);

        // use UTF-8 as default
        if (empty ($charset_from)) $charset_from = "utf-8";
      }
      else $charset_from = "utf-8";

      // convert characters
      if (!empty ($charset_from) && !empty ($charset_to) && strtolower ($charset_from) != strtolower ($charset_to))
      {
        $data_new = convertchars ($data_new, $charset_from, $charset_to);
      }

      // save temp CSV file if changed
      if ($data_new != "" && $data_new != $data) 
      {
        $file = $mgmt_config['abs_path_temp'].uniqid().".csv";
        $temp_file = true;
        $save = file_put_contents ($file, $data_new);
      }
    }

    $row = 0;
    $header = false;

    if (($handle = fopen ($file, "r")) !== false)
    {
      // analyze CSV file
      if ($delimiter == "" || $enclosure == "")
      {
        if (filesize ($file) > 0) $filedata = @fread ($handle, filesize ($file));
        else $filedata = "";

        if ($filedata != "")
        {
          // find delimiter
          if ($delimiter == "")
          {
            $count = array();
            reset ($delimiters_csv);

            foreach ($delimiters_csv as $key)
            {
              $count[$key] = substr_count ($filedata, $key);
            }

            if (max ($count) > 0)
            {
              // use highest count for delimiter
              $temp = array_keys ($count, max ($count));

              if (!empty ($temp[0])) $delimiter = $temp[0];
            }
          }

          // find enclosure
          if ($enclosure == "")
          {
            $count = array();
            reset ($enclosures_csv);

            foreach ($enclosures_csv as $key)
            {
              $count[$key] = substr_count ($filedata, $key);
            }

            if (max ($count) > 0)
            {
              // use highest count for delimiter
              $temp = array_keys ($count, max ($count));

              if (!empty ($temp[0])) $enclosure = $temp[0];
            }
          }
        }
      }

      // define standard enclosure if not found
      if (empty ($enclosure)) $enclosure = '"';

      rewind ($handle);
      $i = 0;

      // delimiter has been identififed or provided
      if ($delimiter != "")
      {
        while (($data = fgetcsv ($handle, 0, $delimiter, $enclosure)) !== false)
        {
          // get number of colums
          $cols = count ($data);

          // verify if row holds columns
          if (is_array ($data) && $cols > 1)
          {
            // use values of first row as key values
            if ($i == 0)
            {
              $names = $data;
            }
            else
            {
              foreach ($data as $key => $value)
              {
                $k = $names[$key];
                $result[$i][$k] = $value;
              }
            }

            $i++;
          }
        }
      }
      // no delimiter
      else
      {
        while (($data = fgetcsv ($handle)) !== false)
        {
          // verify if row holds columns
          if (is_array ($data))
          {
            // use values of first row as key values
            if ($i == 0)
            {
              $names = $data;
            }
            else
            {
              foreach ($data as $key => $value)
              {
                $k = $names[$key];
                $result[$i][$k] = $value;
              }
            }

            $i++;
          }
        }
      }
    }

    // remove temp file
    if (!empty ($temp_file)) unlink ($file);
  }

  if (sizeof ($result) > 0) return $result;
  else return false;
}

// ------------------------------------- create_csv ------------------------------------------
// function: create_csv ()
// input: associative data with row-id and column name as keys [array], file name [string] (optonal), file path for saving the CSV file [string] (optional), delimiter [string] (optional), enclosure [string] (optional), character set of input data [string] (optional), character set of output data [string] (optional), add UTF-8 BOM [boolean] (optional)
// output: true / false on error

// description:
// Creates a CSV file from an associative data array and returns the file as download or writes the file to the file system if a valid path to a directory has been provided.
// For support of MS Excel the encoding should be UTF-16LE in older versions or an UTF-8 BOM need to be added for newer versions of Excel.

function create_csv ($assoc_array, $filename="export.csv", $filepath="php://output", $delimiter=";", $enclosure='"', $charset_from="utf-8", $charset_to="utf-8", $add_bom=false)
{
  if (is_array ($assoc_array) && sizeof ($assoc_array) > 0)
  {
    // remember input path
    $mempath = $filepath;

    // define new character set for MS Excel (not required anymore due to UTF-8 BOM)
    // if (!empty ($excel)) $charset_to = "UTF-16LE";
    // else $charset_to = $charset;

    // http header for file download
    if (!is_dir ($mempath))
    {
      @ob_clean();
      header('Pragma: public');
      header('Expires: 0');
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Cache-Control: private', false);
      header('Content-Type: text/csv; charset='.$charset_to);
      header('Content-Disposition: attachment;filename='.$filename);
    }
    // absolute path to file
    else
    {
      $filepath = correctpath ($filepath);
      $filepath .= $filename;
    }

    $fp = fopen ($filepath, 'w');

    if ($fp)
    {
      // Excel requires UTF-8 BOM
      if (!empty ($add_bom)) fputs ($fp, "\xEF\xBB\xBF");
      
      // use first record for header titles
      fputcsv ($fp, array_keys (reset ($assoc_array)), $delimiter, $enclosure);

      foreach ($assoc_array as $values)
      {
        // convert characters
        if (strtolower ($charset_from) != strtolower ($charset_to)) $values = convertchars ($values, $charset_from, $charset_to);

        // write CSV record based on array holding all values
        fputcsv ($fp, $values, $delimiter, $enclosure);
      }

      fclose ($fp);
    }
    else return false;

    if (!is_dir ($mempath))
    {
      @ob_flush ();
      exit;
    }

    return true;
  }
  else return false;
}

// ========================================== MESSAGING =======================================

// ---------------------------------------------- sendmessage ----------------------------------------------
// function: sendmessage()
// input: from user name [string] (optional), to user name [string], title [string], message [string], object ID or object path [string] (optional), publication name [string] (optional)
// output: true/false
// requires: config.inc.php

// description:
// Sends a message via e-mail to a user.

function sendmessage ($from_user="", $to_user="", $title="", $message="", $object_id="", $site="")
{
  global $mgmt_config, $hcms_lang_codepage, $hcms_lang, $lang;

  $error = array();

  // include hypermailer class
  if (!class_exists ("HyperMailer")) include_once ($mgmt_config['abs_path_cms']."function/hypermailer.class.php");

  if ($to_user != "" && $title != "" && strlen ($title) < 360 && $message != ""  && strlen ($message) < 3600)
  {
    $result = false;
    $object_link = "";

    // set default user name
    if ($from_user == "") $from_user = "hyper Content & Digital Asset Management Server";

    // try to get object_id from object path
    if ($object_id != "" && intval ($object_id) < 1)
    {
      // convert object path if necessary
      if (valid_publicationname ($site)) $object_id = convertpath ($site, $object_id, "");

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
      if ($from_user != "")
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

        // publication
        if ($site == "")
        {
          $temp = getcontent ($usernode[0], "<publication>");

          if (!empty ($temp[0])) $site = $temp[0];
        }
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
      if (!empty ($from_email)) $sender = $from_user." [<a href='mailto:".$from_email."'>".$from_email."</a>]";
      else $sender = $from_user;

      $body = "
  <span style=\"font-family:Verdana, Arial, Helvetica, sans-serif; font-size:14px;\">
    <b>".$hcms_lang['message'][$to_lang]." ".$hcms_lang['from'][$to_lang]." ".$sender."</b><br/><br/>
    ".nl2br ($message)."<br/><br/>
    ".$object_link."
  </span>";

      $mailer = new HyperMailer();
      $mailer->IsHTML(true);
      $mailer->AddAddress ($to_email, $to_user);

      if (!empty ($from_email) && !empty ($from_user))
      {
        $mailer->AddReplyTo ($from_email, $from_user);
        $mailer->From = $from_email;
      }
      elseif (!empty ($mgmt_config[$site]['mailserver']))
      {
        $mailer->From = "automailer@".$mgmt_config[$site]['mailserver'];
        $mailer->FromName = "hyperCMS Automailer";
      }
      else
      {
        $mailer->From = "automailer@hypercms.net";
        $mailer->FromName = "hyperCMS Automailer";
      }

      $mailer->Subject = "hyperCMS: ".$title;
      $mailer->CharSet = $hcms_lang_codepage[$to_lang];
      $mailer->Body = html_decode ($body, $hcms_lang_codepage[$to_lang]);

      // send mail
      if ($mailer->Send())
      {
        $errcode = "00202";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|info|".$errcode."|Message has been sent to ".$to_user." (".$to_email.")";
        $result = true;
      }
      else
      {
        $errcode = "50202";
        $error[] = $mgmt_config['today']."|hypercms_main.inc.php|error|".$errcode."|Message could not be sent to ".$to_user." (".$to_email.")";
      }

      // save log
      savelog (@$error);
    }

    return $result;
  }
  else return false;
}

// ========================================== ACCESS LINKING =======================================

// ---------------------------------------------- linking_valid ----------------------------------------------
// function: linking_valid()
// input: %
// output: true/false
// requires: config.inc.php

// description:
// Verifies if a valid access linking exists.

function linking_valid ()
{
  global $mgmt_config, $hcms_linking;

  if (!empty ($_SESSION['hcms_linking']) && is_array ($_SESSION['hcms_linking']) && sizeof ($_SESSION['hcms_linking']) > 0) return true;
  elseif (!empty ($hcms_linking) && is_array ($hcms_linking) && sizeof ($hcms_linking) > 0) return true;
  else return false;
}

// ---------------------------------------------- linking_objects ----------------------------------------------
// function: linking_objects()
// text IDs to be returned [array] (optional)
// output: result array / false on error
// requires: config.inc.php

// description:
// Returns the all objects of an access link as an array.

function linking_objects ($return_text_id=array())
{
  global $mgmt_config, $hcms_linking;

  // get objects form session
  if (!empty ($_SESSION['hcms_linking']) && is_array ($_SESSION['hcms_linking']) && sizeof ($_SESSION['hcms_linking']) > 0)
  {
    $hcms_linking = $_SESSION['hcms_linking'];
  }

  if (is_array ($hcms_linking) && sizeof ($hcms_linking) > 0)
  {
    $object_path_array = array();

    foreach ($hcms_linking as $hash=>$objectpath)
    {
      // collect requested content
      if ($hash != "" && is_array ($return_text_id) && sizeof ($return_text_id) > 0)
      {
        $object_info = rdbms_getobject_info ($hash, $return_text_id);
 
        if (!empty ($object_info['objectpath'])) 
        {
          $object_path_array[$hash] = $object_info;
        }
      }
      // object path only
      else
      {
        $object_path_array[$hash]['objectpath'] = $objectpath;
      }
    }

    if (sizeof ($object_path_array) > 0)
    {
      return $object_path_array;
    }
    else return false;
  }
  else return false;
}

// ---------------------------------------------- linking_inscope ----------------------------------------------
// function: linking_inscope()
// input: publication name [string] (optional), location [string], object name [string] (optional), object category [comp,page] (optional)
// output: true/false
// requires: config.inc.php

// description:
// Verifies if the provided location path is in the scope of the access linking.

function linking_inscope ($site, $location, $page="", $cat="")
{
  global $mgmt_config, $hcms_linking;

  // access linking available
  if (valid_locationname ($location) && !empty ($hcms_linking) && is_array ($hcms_linking) && sizeof ($hcms_linking) > 0)
  {
    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    if (valid_publicationname ($site)) $location = convertpath ($site, $location, $cat);

    // location must be a inside a linking location path (only links to folders)
    foreach ($hcms_linking as $temp)
    {
      if ($temp != "")
      {
        // link to folder
        if (getobject ($temp) == ".folder" || $page == ".folder")
        {
          $temp = getlocation ($temp);

          if (strpos ("_".$location, $temp) > 0) return true;
        }
        // exact link to object
        elseif ($page != "")
        {
          // remove extension from unpublished pages or components
          if (substr ($page, -4) == ".off") $page = substr ($page, 0, (strlen ($page) - 4));

          if ($location.$page == $temp) return true;
        }
        // link to object (must be in exact same location)
        else
        {
          $temp = getlocation ($temp);

          if ($location == $temp) return true;
        }
      }
    }
  }
  // no access linking available
  elseif (valid_locationname ($location)) return true;

  return false;
}

// ========================================== SAVE CONTENT =======================================

// ---------------------------------------------- savecontent ----------------------------------------------
// function: savecontent()
// input: publication name [string], location [string], object name [string], content array with 1st key as index number and 2nd keys as id or name according to the template tags [array], character set [string] (optional), user name [string], DB connectivity file name [string] (optional)
// output: true/false
// requires: config.inc.php

// description:
// Saves the provided content for a specific object. Only the provided content based on its ID will be saved. Existing content with a different ID will not be deleted.
// This is a simplified version of the service savecontent that is used by the multiedit feature and the OpenAPI (SOAP Webservice).
// Example of a page content array as content input:
// array(
//   array(
//     "pagetitle"=>"My Page", 
//     "pageauthor"=>"Thomas Tester", 
//     "pagedescription"=>"Just an example"
//   ),
//   array(
//     "id"=>"MyText", 
//     "textu"=>"I am a text"
//    ),
//   array(
//     "id"=>"MyKeywords", 
//     "textk"=>"apple,car,mobile",
//     "language"=>"en"
//    ), 
//   array(
//     "id"=>"MyImage", 
//     "mediaobject"=>"%comp%/myPublication/image.jpg", 
//     "mediaalttext"=>"This is my image",
//     "mediaalign"=>"top",
//     "mediawidth"=>"260",
//     "mediaheight"=>"140",
//   ),
//   array(
//     "id"=>"MyLink", 
//     "linkhref"=>"http://www.hypercms.com", 
//     "linktarget"=>"_SELF",
//     "linktext"=>"hyperCMS Home"
//   ),
//   array(
//     "id"=>"MyComponent", 
//     "componentm"=>"%comp%/comp_1.html|%comp%/comp_2.html", 
//     "condition"=>""
//   )
// )

function savecontent ($site, $location, $page, $content, $charset="UTF-8", $user="", $db_connect="")
{  
  global $mgmt_config, $lang, $hcms_lang, $eventsystem,
         $rootpermission, $globalpermission, $localpermission,
         $siteaccess, $pageaccess, $compaccess,
         $adminpermission, $hiddenfolder;

  // initialize
  $error = array();
  
  // set default language
  if (empty ($lang)) $lang = "en";

  // set content_array
  $content_array = $content;

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && is_array ($content_array) && $user != "")
  {
    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_comp']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // get category
    $cat = getcategory ($site, $location);

    // deconvertpath location
    $location = deconvertpath ($location, "file");

    // if folder
    if (is_dir ($location.$page)) 
    {
      $location = $objectpath;
      $page = ".folder";
    }
    
    // check permissions
    if ($user != "sys" && !empty ($mgmt_config['api_checkpermission']))
    {
      $ownergroup = accesspermission ($site, $location, $cat);
      $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat); 
      
      if (empty ($setlocalpermission['root']) || (!is_folder ($location.$page) && empty ($setlocalpermission['create'])) || (is_folder ($location.$page) && empty ($setlocalpermission['createfolder'])))
      {
        return false;
      }
    }
    
    // load object file and get container
    $pagedata = loadfile ($location, $page); 
    $contentfile = getfilename ($pagedata, "content");
    $mediafile = getfilename ($pagedata, "media");

    // check locked by user
    $result_containername = getcontainername ($contentfile);
    $usedby = $result_containername['user'];

    // if not locked by another user
    if ($usedby == "" || $usedby == $user)
    {
      // include hyperCMS Event System
      @include_once ($mgmt_config['abs_path_data']."eventsystem/hypercms_eventsys.inc.php");
      
      // ------------------------------------include db_connect functions ----------------------------------
      if (isset ($db_connect) && valid_objectname ($db_connect) && file_exists ($mgmt_config['abs_path_data']."db_connect/".$db_connect)) 
      {
        // include db_connect functions
        @include_once ($mgmt_config['abs_path_data']."db_connect/".$db_connect); 
      }

      // load content container
      $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml"));
      $contentdata = loadcontainer ($container_id, "work", $user);

      // check if content is not empty
      if ($contentdata != false)
      {
        $textmeta = array();
        $contentdatanew = $contentdata;

        foreach ($content_array as $key => $content)
        {
          // convert to array
          if (is_object ($content)) $content = (array) $content;

          // taxonomy tree selector returns an array
          $lang_taxonomy = array();

          if (!empty ($content['textk']) && !empty ($content['language']) && !empty ($content['id']))
          {
            // get and set language for text ID
            $lang_taxonomy[$content['id']] = $content['language'];

            // create unique keywords and convert array to comma separated keyword list
            if (is_array ($content['textk']))
            {
              $temp = implode (",", $content['textk']);
              $temp = explode (",", $temp);
              $temp = array_unique ($temp);
              $content['textk'] = implode (",", $temp);
            }
            else
            {
              $temp = explode (",", $content['textk']);
              $temp = array_unique ($temp);
              $content['textk'] = implode (",", $temp);
            }
          }

          if (!empty ($content['arttextk']) && !empty ($content['language']) && !empty ($content['id']))
          {
            // get and set language for text ID
            $lang_taxonomy[$content['id']] = $content['language'];

            // create unique keywords and convert array to comma separated keyword list
            if (is_array ($content['textk']))
            {
              $temp = implode (",", $content['arttextk']);
              $temp = explode (",", $temp);
              $temp = array_unique ($temp);
              $content['arttextk'] = implode (",", $temp);
            }
            else
            {
              $temp = explode (",", $content['arttextk']);
              $temp = array_unique ($temp);
              $content['arttextk'] = implode (",", $temp);
            }
          }

          // head content (unique for a content container)
          $headcontent = array();
          if (!empty ($content['pagetitle'])) $headcontent['pagetitle'] = $content['pagetitle'];
          if (!empty ($content['pageauthor'])) $headcontent['pageauthor'] = $content['pageauthor'];
          if (!empty ($content['pagedescription'])) $headcontent['pagedescription'] = $content['pagedescription'];
          if (!empty ($content['pagekeywords'])) $headcontent['pagekeywords'] = $content['pagekeywords'];
          if (!empty ($content['pagecontenttype'])) $headcontent['pagecontenttype'] = $content['pagecontenttype'];
          if (!empty ($content['pagelanguage'])) $headcontent['pagelanguage'] = $content['pagelanguage'];
          if (!empty ($content['pagerevisit'])) $headcontent['pagerevisit'] = $content['pagerevisit'];
          if (!empty ($content['pagetracking'])) $headcontent['pagetracking'] = $content['pagetracking'];
    
          if (sizeof ($headcontent) > 0 && $contentdatanew != false) $contentdatanew = sethead ($site, $contentdatanew, $contentfile, $headcontent, $user);
          
          if (!empty ($content['id']))
          {
            // content ID
            $id = $content['id'];

            // text content
            if (!empty ($content['textf']) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, array($id => $content['textf']), "f", "no", $user, $user, $charset);
            if (!empty ($content['arttextf']) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, array($id => $content['arttextf']), "f", "yes", $user, $user, $charset);
            if (!empty ($content['textu']) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, array($id => $content['textu']), "u", "no", $user, $user, $charset);
            if (!empty ($content['arttextu']) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, array($id => $content['arttextu']), "u", "yes", $user, $user, $charset);
            if (!empty ($content['textl']) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, array($id => $content['textl']), "l", "no", $user, $user, $charset);
            if (!empty ($content['arttextl']) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, array($id => $content['arttextl']), "l", "yes", $user, $user, $charset);
            if (!empty ($content['textc']) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, array($id => $content['textc']), "c", "no", $user, $user, $charset);
            if (!empty ($content['arttextc']) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, array($id => $content['arttextc']), "c", "yes", $user, $user, $charset);
            if (!empty ($content['textd']) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, array($id => $content['textd']), "d", "no", $user, $user, $charset);
            if (!empty ($content['arttextd']) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, array($id => $content['arttextd']), "d", "yes", $user, $user, $charset);
            // keywords usually only apply for metadata templates (support for articles added in version 8.1.3)
            if (!empty ($content['textk']) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, array($id => $content['textk']), "k", "no", $user, $user, $charset);
            if (!empty ($content['arttextk']) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, array($id => $content['arttextk']), "k", "yes", $user, $user, $charset);
            // only if autosaving is not used
            if (!empty ($content['commentu']) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, array($id => $content['commentu']), "u", "no", $user, $user, $charset, true);
            if (!empty ($content['commentf']) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, array($id => $content['commentf']), "f", "no", $user, $user, $charset, true);

            // media content
            if ((!empty ($content['mediafile']) || !empty ($content['mediaobject'])) && $contentdatanew != false) $contentdatanew = setmedia ($site, $contentdatanew, $contentfile, array($id => @$content['mediafile']), array($id => @$content['mediaobject']), array($id => @$content['mediaalttext']), array($id => @$content['mediaalign']), array($id => @$content['mediawidth']), array($id => @$content['mediaheight']), "no", $user, $user, $charset);
            if ((!empty ($content['artmediafile']) || !empty ($content['artmediaobject'])) && $contentdatanew != false) $contentdatanew = setmedia ($site, $contentdatanew, $contentfile, array($id => @$content['artmediafile']), array($id => @$content['artmediaobject']), array($id => @$content['artmediaalttext']), array($id => @$content['artmediaalign']), array($id => @$content['artmediawidth']), array($id => @$content['artmediaheight']), "yes", $user, $user, $charset);
        
            // page link content
            if (!empty ($content['linkhref']) && $contentdatanew != false) $contentdatanew = setpagelink ($site, $contentdatanew, $contentfile, array($id => $content['linkhref']), array($id => @$content['linktarget']), array($id => @$content['linktext']), "no", $user, $user, $charset);
            if (!empty ($content['artlinkhref']) && $contentdatanew != false) $contentdatanew = setpagelink ($site, $contentdatanew, $contentfile, array($id => $content['artlinkhref']), array($id => @$content['artlinktarget']), array($id => @$content['artlinktext']), "yes", $user, $user, $charset);    
        
            // component content
            if (!empty ($content['component']) && $contentdatanew != false) $contentdatanew = setcomplink ($site, $contentdatanew, $contentfile, array($id => $content['component']), array($id => @$content['condition']), "no", $user, $user);
            if (!empty ($content['artcomponent']) && $contentdatanew != false) $contentdatanew = setcomplink ($site, $contentdatanew, $contentfile, array($id => $content['artcomponent']), array($id => @$content['condition']), "yes", $user, $user);    
            if (!empty ($content['components']) && $contentdatanew != false) $contentdatanew = setcomplink ($site, $contentdatanew, $contentfile, array($id => $content['components']), array($id => @$content['condition']), "no", $user, $user);
            if (!empty ($content['artcomponents']) && $contentdatanew != false) $contentdatanew = setcomplink ($site, $contentdatanew, $contentfile, array($id => $content['artcomponents']), array($id => @$content['condition']), "yes", $user, $user);    
            if (!empty ($content['componentm']) && $contentdatanew != false) $contentdatanew = setcomplink ($site, $contentdatanew, $contentfile, array($id => $content['componentm']), array($id => @$content['condition']), "no", $user, $user);
            if (!empty ($content['artcomponentm']) && $contentdatanew != false) $contentdatanew = setcomplink ($site, $contentdatanew, $contentfile, array($id => $content['artcomponentm']), array($id => @$content['condition']), "yes", $user, $user);    

            // collect text for metadata of multimedia file
            if (!empty ($content['textf'])) $textmeta = array_merge ($textmeta, array($id => $content['textf']));
            if (!empty ($content['textu'])) $textmeta = array_merge ($textmeta, array($id => $content['textu']));
            if (!empty ($content['textl'])) $textmeta = array_merge ($textmeta, array($id => $content['textl']));
            if (!empty ($content['textc'])) $textmeta = array_merge ($textmeta, array($id => $content['textc']));
            if (!empty ($content['textd'])) $textmeta = array_merge ($textmeta, array($id => $content['textd']));
            if (!empty ($content['textk'])) $textmeta = array_merge ($textmeta, array($id => $content['textk']));
          }
        }

        if ($contentdatanew == false) return false;
        
        // eventsystem executed after settext since the container data will be saved at the end
        if (!empty ($eventsystem['onsaveobject_pre']) && empty ($eventsystem['hide']))
        {
          $contentdataevent = onsaveobject_pre ($site, $cat, $location, $page, $contentfile, $contentdatanew, $user);
        }

        // ----------------------------------- write metadata --------------------------------------  
        // write meta data to media file
        if (!empty ($mediafile))
        {
          // get media file location and name
          $mediafile_location = getmedialocation ($site, $mediafile, "abs_path_media");
          
          // correct path to media file
          if (!is_file ($mediafile_location.$mediafile)) $mediafile_location = $mediafile_location.$site."/";

          // prepare media file
          $temp = preparemediafile ($site, $mediafile_location, $mediafile, $user);
          
          // if encrypted
          if (!empty ($temp['result']) && !empty ($temp['crypted']))
          {
            $mediafile_path = $temp['templocation'].$temp['tempfile'];
          }
          // if restored
          elseif (!empty ($temp['result']) && !empty ($temp['restored']))
          {
            $mediafile_path = $temp['location'].$temp['file'];
          }
          else
          {
            $mediafile_path = $mediafile_location.$mediafile;
          }

          if (is_file ($mediafile_path))
          {
            // write IPTC data to media file
            $result_iptc = false;
            
            if (!empty ($mgmt_config['iptc_save']))
            {
              $iptc = iptc_create ($site, $textmeta);

              if (is_array ($iptc))
              {
                $result_iptc = iptc_writefile ($mediafile_path, $iptc, true, false);
              }
            }
            
            // write XMP data to media file
            $result_xmp = false;
            
            if (!empty ($mgmt_config['xmp_save']))
            {
              $xmp = xmp_create ($site, $textmeta);

              if (is_array ($xmp))
              {
                $result_xmp = xmp_writefile ($mediafile_path, $xmp, true, false);   
              }
            }
            
            // write ID3 data to media file
            $result_id3 = false;
            
            if (!empty ($mgmt_config['id3_save']))
            { 
              $id3 = id3_create ($site, $textmeta);

              if (is_array ($id3))
              {
                $result_id3 = id3_writefile ($mediafile_path, $id3, true, false);   
              }
            }
            
            // touch thumbnail file of documents to update the timestamp / avoid recreation of annotation images)
            if (is_document ($mediafile_path))
            {
              // get file name without extensions
              $thumbfile_path = strrev (substr (strstr (strrev ($mediafile_path), "."), 1)).".thump.pdf";
              
              // update timestamp
              if (is_file ($thumbfile_path)) touch ($thumbfile_path);
            }
              
            // save media stats and move temp file on success
            if (!empty ($result_iptc) || !empty ($result_xmp) || !empty ($result_id3))
            {
              // write updated media information to DB
              if (!empty ($container_id))
              {
                // $md5_hash = md5_file ($mediafile_path);
                $filesize = round (@filesize ($mediafile_path) / 1024, 0);

                // don't save the actual MD5 hash of the file since the search for duplicates is based on the MD5 hash
                rdbms_setmedia ($container_id, $filesize, "", "", "", "", "", "", "", "", "");
              }
              
              // encrypt and save file if required
              if ($temp['result']) movetempfile ($mediafile_location, $mediafile, true);

              // save to cloud storage
              if (function_exists ("savecloudobject")) savecloudobject ($site, $mediafile_location, $mediafile, $user);
            }
            
            // set modified date in DB
            rdbms_setcontent ($site, $container_id);
          }
        }
        
        // check if event returns a string, if so, the event returns the container and not true or false 
        if (!empty ($contentdataevent) && strlen ($contentdataevent) > 10) $contentdatanew = $contentdataevent;

        // insert new date into content file
        $contentdatanew = setcontent ($contentdatanew, "<hyperCMS>", "<contentdate>", $mgmt_config['today'], "", "");

        // set encoding 
        $charset_old = getcharset ("", $contentdatanew); 

        if ($charset_old == false || $charset_old == "" || $charset_old != $charset)
        {
          // write XML declaration parameter for text encoding
          if ($charset != "") $contentdatanew = setxmlparameter ($contentdatanew, "encoding", $charset);
        }
        
        // save working xml content container file
        $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml")); 
        $savefile = savecontainer ($container_id, "work", $contentdatanew, $user);
        
        // test if file could be saved
        if ($savefile == false)
        {
          return false;
        }
        else
        {
          // set taxonomy
          settaxonomy ($site, $container_id, $lang_taxonomy);
        
          // set keywords
          rdbms_setkeywords ($site, $container_id);

          // notification
          notifyusers ($site, $location, $page, "onedit", $user);
        }

        // ----------------------------------- DB Connectivity --------------------------------------   
        // db_connect will save content in provided database connectivity 
        if (!empty ($db_connect)) 
        {      
          // write data
          $test = db_write_container ($site, $contentfile, $contentdatanew, $user);    
          
          if ($test == false)
          {
            $errcode = "20101";
            $error[] = $mgmt_config['today']."|page_save.inc.php|error|".$errcode."|Unable to save data of container '$contentfile' using db_connect '$db_connect'";          
          }
        }

        // eventsystem
        if (!empty ($eventsystem['onsaveobject_post']) && empty ($eventsystem['hide']))
        {
          onsaveobject_post ($site, $cat, $location, $page, $contentfile, $contentdatanew, $user);
        }
        
        return true;
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// ========================================== CORS =======================================

// ---------------------------------------------- createCORSheader ----------------------------------------------
// function: createCORSheader()
// input: allowed origin [string]
// output: CORS http headers
// requires: config.inc.php

// description:
// Create the CORS HTTP header required for cross-domain services. It will allow any GET, POST, or OPTIONS requests from any allowed origin.

function createCORSheader ($allow_origins)
{
  // allow from any origin
  if (isset ($_SERVER['HTTP_ORIGIN']) && is_array ($allow_origins) && in_array ($_SERVER['HTTP_ORIGIN'], $allow_origins))
  {
    // if the origin in $_SERVER['HTTP_ORIGIN'] is one to allow, and if so:
    header ("Access-Control-Allow-Origin: ".$_SERVER['HTTP_ORIGIN']);
    header ('Access-Control-Allow-Credentials: true');
    // cache for 1 day
    header ('Access-Control-Max-Age: 86400');
    
    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS')
    {    
      if (isset ($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
      {
        // may also be using PUT, PATCH, HEAD etc
        header ("Access-Control-Allow-Methods: GET, POST, OPTIONS");
      }    
      
      if (isset ($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
      {
        header ("Access-Control-Allow-Headers: ".$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
      }
    }
  }
}
?>