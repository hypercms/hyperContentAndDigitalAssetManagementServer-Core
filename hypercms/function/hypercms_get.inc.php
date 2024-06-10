<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */
 
// ======================================== SERVER PARAMETERS ===========================================

// ---------------------- getbuildnumber -----------------------------
// function: getbuildnumber()
// input: %
// output: Returns the versions build number of the software

function getbuildnumber ()
{
  global $mgmt_config;

  // build number defined by the UNIX timestamp of ther version text file 
  if (is_file ($mgmt_config['abs_path_cms']."version.txt")) $build = filemtime ($mgmt_config['abs_path_cms']."version.txt");
  else $build = "";

  return url_encode ($build);
}

// ---------------------- getserverload -----------------------------
// function: getserverload()
// input: intervall for the average system load can be 1, 5 or 15 minutes [0,1,2] (optional)
// output: Returns the average system load (the number of processes in the system run queue) over the last minute, the number of CPU cores, and the memory usage as array

function getserverload ($interval=0)
{
  // initialize
  $cpu_num = 2;
  $load = 0;
  $memory_usage = 0;
  if ($interval != 0 || $interval != 1 || $interval != 2) $interval = 0;

  // for Windows
  if (stristr (PHP_OS, 'win'))
  {
    if (class_exists ('COM'))
    {
      $wmi = new COM ("Winmgmts://");
      $server = $wmi->execquery ("SELECT LoadPercentage FROM Win32_Processor");
 
      foreach ($server as $cpu)
      {
        $cpu_num++;
        $load_total += $cpu->loadpercentage;
      }
    }
    else
    {
      $process = @popen ('wmic cpu get NumberOfCores', 'rb');

      if (false !== $process)
      {
        fgets ($process);
        $cpu_num = intval (fgets ($process));
        pclose ($process);
      }

      $output = array();
      $cmd = "wmic cpu get loadpercentage /all";
      @exec ($cmd, $output);

      if (is_array ($output))
      {
        foreach ($output as $line)
        {
          if ($line && preg_match ("/^[0-9]+\$/", $line))
          {
            $load_total = $line;
            break;
          }
        }
      }
    }

    if (!empty ($load_total) && !empty ($cpu_num) && $load_total > 0 && $cpu_num > 0) $load = round ($load_total / $cpu_num);

    // server total memory
    $output = array();
    exec ('wmic memorychip get capacity', $output);
    if (is_array ($output)) $totalmem = array_sum ($output);

    // server memory usage
    $output = array();
    exec ('tasklist /FI "PID eq '.getmypid().'" /FO LIST', $output);
    if (!empty ($output[5])) $usedmem = preg_replace ( '/[^0-9]/', '', $output[5]);

    if (!empty ($usedmem) && !empty ($totalmem) && $usedmem > 0 && $totalmem > 0) $memory_usage = $usedmem / $totalmem;
  }
  // for UNIX
  elseif (function_exists ('sys_getloadavg'))
  {
    // server CPU cores
    $output = array();
    exec ("cat /proc/cpuinfo | grep processor | wc -l", $output);
    if (!empty ($output[0])) $cpu_num = $output[0];

    // server load
    $sys_load = sys_getloadavg ();
    if (!empty ($sys_load[$interval])) $load_total = $sys_load[$interval];

    if (!empty ($load_total) && !empty ($cpu_num) && $load_total > 0 && $cpu_num > 0) $load = $load_total / $cpu_num;

    // server memory usage
    $output = array();
    exec ('free', $output);

    if (!empty ($output[1]))
    {
      $mem = explode (" ", $output[1]);
      $mem = array_filter ($mem);
      $mem = array_merge ($mem);
    }

    if (!empty ($mem[2]) && !empty ($mem[1]) && $mem[2] > 0 && $mem[1] > 0) $memory_usage = $mem[2] / $mem[1];
  }

  $result = array();
  $result['load'] = $load;
  $result['cpu'] = $cpu_num;
  $result['memory'] = $memory_usage;

  return $result;
}

// ------------------------- getconfigvalue -----------------------------
// function: getconfigvalue()
// input: settings [array], value/substring in array key [string] (optional)
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
  }

  return "";
}
 
// =========================================== REQUESTS AND SESSION ==============================================
 
// ------------------------- getuploadfilechunkinfo -----------------------------
// function: getuploadfilechunkinfo()
// input: %
// output: result array / false on error

// description:
// This function returns the byte range and file size of uploaded file chunks (based on HTTP_CONTENT_RANGE)

function getuploadfilechunkinfo ()
{
  // parse the Content-Range header, which has the following form:
  // Content-Range: bytes 0-524287/2000000
  if (!empty ($_SERVER['HTTP_CONTENT_RANGE']) && strpos ($_SERVER['HTTP_CONTENT_RANGE'], "/") > 0)
  {
    $content_range = preg_split ('/[^0-9]+/', $_SERVER['HTTP_CONTENT_RANGE']);

    $result = array();
    if (isset ($content_range[1]) && isset ($content_range[2])) $result['range'] = $content_range[1]."-".$content_range[2];
    if (isset ($content_range[1])) $result['range-begin'] = fixintegeroverflow ($content_range[1]);
    if (isset ($content_range[2])) $result['range-end'] = fixintegeroverflow ($content_range[2]);
    if (isset ($content_range[3])) $result['size'] = fixintegeroverflow ($content_range[3]);

    if (sizeof ($result) > 0) return $result;
  }
  
  return false;
}

// ------------------------- getsession -----------------------------
// function: getsession()
// input: session variable name [string], default session value [string] (optional)
// output: session value

function getsession ($variable, $default="")
{
  if ($variable != "" && session_id() != "")
  {
    // get from session
    if (array_key_exists ("hcms_".$variable, $_SESSION)) $result = $_SESSION["hcms_".$variable];
    elseif (array_key_exists ($variable, $_SESSION)) $result = $_SESSION[$variable];
    else $result = $default;

    return $result;
  }
  else return $default;
}

// ------------------------- getrequest -----------------------------
// function: getrequest()
// input: request variable name [string], must be of certain type [numeric,array,publicationname,locationname,objectname,url,bool] (optional), default value [string] (optional)
// output: request value

// description:
// Returns the value of a POST, GET or COOKIE request, or a default value if not valid.

function getrequest ($variable, $force_type=false, $default="")
{
  if ($variable != "")
  {
    // get from request
    if (array_key_exists ($variable, $_POST)) $result = $_POST[$variable];
    elseif (array_key_exists ($variable, $_GET)) $result = $_GET[$variable];
    // elseif (array_key_exists ($variable, $_COOKIE)) $result = $_COOKIE[$variable];
    else $result = $default;

    // check for type
    if ($result != "" && ($force_type == "numeric" || $force_type == "array" || $force_type == "publicationname" || $force_type == "locationname" || $force_type == "objectname" || $force_type == "url" || $force_type == "bool"))
    {
      if ($force_type == "numeric" && !is_numeric ($result)) $result = $default;
      elseif ($force_type == "array" && !is_array ($result) && $default == "") $result = array();
      elseif ($force_type == "publicationname" && !valid_publicationname ($result)) $result = $default;
      elseif ($force_type == "locationname" && !valid_locationname ($result)) $result = $default;
      elseif ($force_type == "objectname" && !valid_objectname ($result)) $result = $default;
      elseif ($force_type == "url" && strpos ("_".strtolower (urldecode ($result)), "<script") > 0) $result = $default;
      elseif ($force_type == "bool" || $force_type == "boolean") 
      {
        if ($result == 1 || $result == "yes" || $result == "true" || $result == "1") $result = true;
        elseif($result == 0 || $result == "no" || $result == "false" || $result == "0") $result = false;
        else $result = $default;
      }
    }

    // return result
    return $result;
  }
  else return $default;
}

// ------------------------- getrequest_esc -----------------------------
// function: getrequest_esc()
// input: request variable name [string], must be of certain type [numeric,array,publicationname,locationname,objectname] (optional), default value [string] (optional), 
//        remove characters to avoid JS injection [boolean] (optional)
// output: request value

// description:
// Returns the escaped value in order to prevent XSS from POST, GET or COOKIE variables. Returns a default value if not valid.

function getrequest_esc ($variable, $force_type=false, $default="", $js_protection=false)
{
  if ($variable != "")
  {
    $result = getrequest ($variable, $force_type, $default);
    $result = html_encode ($result, "", $js_protection);

    return $result;
  }
  else return $default;
}

// ------------------------- getboolean -----------------------------
// function: getboolean()
// input: value [string]
// output: bollean value

// description:
// Returns the correct boolean value for a string value.

function getboolean ($value)
{
  if (is_string ($value) && $value != "")
  {
    if (strtolower ($value) == "false") $value = false;
    if (strtolower ($value) == "true") $value = true;
  }

  return $value;
}


// ----------------------------------------- getuserip ------------------------------------------
// function: getuserip()
// input: %
// output: IP address of client / false on error

// description:
// Retrieves the IP address of the client/user.

function getuserip ()
{
  if (!empty ($_SERVER['HTTP_X_FORWARDED_FOR'])) $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  elseif (!empty ($_SERVER['REMOTE_ADDR'])) $client_ip = $_SERVER['REMOTE_ADDR'];
  elseif (!empty ($_SERVER['HTTP_CLIENT_IP'])) $client_ip = $_SERVER['HTTP_CLIENT_IP'];
 
  if (!empty ($client_ip)) return $client_ip;
  else return false;
}

// ----------------------------------------- getobjectlistcells ------------------------------------------
// function: getobjectlistcells()
// input: width of viewport or window in pixels [integer], is mobile device [0,1] (optional)
// output: number of table cells/rows for the gallery view of object lists

function getobjectlistcells ($viewportwidth, $is_mobile=0)
{
  // max thumbnail size in pixels
  $maxthumbsize = 180;

  // for mobile screens
  if ($is_mobile)
  {
    // Navigator does not require space
    if ($viewportwidth > 0) $table_cells = floor ($viewportwidth / ($maxthumbsize + 10));
    else $table_cells = 3;
  }
  // for desktop/notebook screens
  else
  {
    // Navigator and also side bar might take space
    if ($viewportwidth > 0) $table_cells = floor (($viewportwidth - 260 - 330) / ($maxthumbsize + 10));
    else $table_cells = 5;
  }

  if ($table_cells >= 1) return $table_cells;
  else return 1;
}

// ----------------------------------------- getlanguageoptions ------------------------------------------
// function: getlanguageoptions()
// input: %
// output: array with 2-digit language code as key and language name in English as value / false on error

function getlanguageoptions ()
{
  global $mgmt_config;

  if (is_file ($mgmt_config['abs_path_cms']."include/languagecode.dat"))
  {
    $result = array();

    // load manguage code file
    $langcode_array = file ($mgmt_config['abs_path_cms']."include/languagecode.dat");

    if ($langcode_array != false)
    {
      foreach ($langcode_array as $langcode)
      {
        list ($code, $lang) = explode ("|", trim ($langcode));
        $result[$code] = $lang;
      }

      if (sizeof ($result) > 0)
      {
        asort ($result);
        return $result;
      }
    }
  }

  return false;
}

// ----------------------------------------- getlanguagefile ------------------------------------------
// function: getlanguagefile()
// input: language code [string] (optional)
// output: language file name

function getlanguagefile ($lang="en")
{
  global $mgmt_config;

  if ($lang != "" && !empty ($mgmt_config['abs_path_cms']))
  {
    if (is_file ($mgmt_config['abs_path_cms']."language/".$lang.".inc.php")) return $lang.".inc.php";
  }

  return "en.inc.php";
}

// ----------------------------------------- getcodepage ------------------------------------------
// function: getcodepage()
// input: language code [string] (optional)
// output: code page (character set)

function getcodepage ($lang="en")
{
  global $mgmt_config, $hcms_lang_codepage;

  if ($lang != "" && !empty ($hcms_lang_codepage[$lang]))
  {
    return $hcms_lang_codepage[$lang];
  }
  elseif ($lang != "" && empty ($hcms_lang_codepage[$lang]) && !empty ($mgmt_config['abs_path_cms']))
  {
    // try to include language file
    if (is_file ($mgmt_config['abs_path_cms']."language/".$lang.".inc.php")) require_once ($mgmt_config['abs_path_cms']."language/".$lang.".inc.php");

    if (!empty ($hcms_lang_codepage[$lang])) return $hcms_lang_codepage[$lang];
  }

  return "UTF-8";
}

// ----------------------------------------- getcalendarlang ------------------------------------------
// function: getcalendarlang()
// input: 2-digits language code [string] (optional)
// output: supported language code for calendar

function getcalendarlang ($lang="en")
{
  global $mgmt_config;

  if ($lang != "")
  {
    // define supported languages of calendar
    $lang_supported = array("de", "en", "fr", "pt", "ru");

    $lang = strtolower ($lang);

    if (in_array ($lang, $lang_supported)) return $lang;
  }

  return "en";
}

// ----------------------------------------- getscaytlang ------------------------------------------
// function: getscaytlang()
// input: 2-digits language code [string] (optional)
// output: supported language locale for CKEditor scayt plugin

function getscaytlang ($lang="en")
{
  global $mgmt_config;

  if ($lang != "")
  {
    // define supported languages pairs
    $lang_supported = array('da'=>'da_DK', 'de'=>'de_DE', 'el'=>'el_GR', 'en'=>'en_US', 'es'=>'es_ES', 'fi'=>'fi_FI', 'fr'=>'fr_FR', 'it'=>'it_IT', 'no'=>'nb_NO', 'nl'=>'nl_NL', 'sv'=>'sv_SE');

    $lang = strtolower ($lang);

    if (!empty ($lang_supported[$lang])) return $lang_supported[$lang];
  }

  return "en_US";
}

// ----------------------------------------- getlabel ------------------------------------------
// function: getlabel()
// input: label string from template tag in the form of en:Title;de:Titel [string], 2-digits language code [string] (optional)
// output: label value

function getlabel ($label, $lang="en")
{
  global $mgmt_config;

  if ($label != "" && $lang != "")
  {
    // multiple labels seperated by semicolon
    if (substr_count ($label, ";") > 0)
    {
      $labels_array = explode (";", $label);

      if (is_array ($labels_array) && sizeof ($labels_array) > 0)
      {
        $i = 0;
        
        foreach ($labels_array as $label_entry)
        {
          if (substr_count ($label_entry, ":") > 0)
          {
            list ($langcode, $text) = explode (":", $label_entry);

            if (strlen ($langcode) >= 2 && strlen ($langcode) <= 4)
            {
              $langcode = trim ($langcode);
              $result[$langcode] = trim ($text);

              if ($i == 0 || $langcode == "en") $result['default'] = $result[$langcode];

              $i++;
            }
          }
        }
      }
    }
    // single label and language
    else
    {
      if (substr_count ($label, ":") > 0)
      {
        list ($langcode, $text) = explode (":", $label);

        if (strlen ($langcode) >= 2 && strlen ($langcode) <= 4)
        {
          $result['default'] = $text;
        }
      }
    }

    if (!empty ($result[$lang])) return $result[$lang];
    elseif (!empty ($result['default'])) return $result['default'];
  }

  return $label;
}

// ----------------------------------------- getescapedtext ------------------------------------------
// function: getescapedtext()
// input: text [string], character set of text [string] (optional), 2-digit language code [string] (optional)
// output: HTML escaped text

// description:
// If the destination character set is not supported by the language set of the presentation, the text need to be HTML escaped.

function getescapedtext ($text, $charset="", $lang="")
{
  global $mgmt_config, $hcms_lang_codepage, $hcms_lang;

  if ($text != "" && $charset != "" && $lang != "" && !empty ($hcms_lang_codepage[$lang]))
  {
    // enocode all special characters if required
    if (strtolower ($charset) != strtolower ($hcms_lang_codepage[$lang]))
    {
      $text_encoded = html_encode ($text, "ASCII");

      // return ASCII encoded text
      if (!empty ($text_encoded)) return $text_encoded;
    }
  }

  // escape special characters <, >, &, ", '
  $text = html_encode ($text);

  // allow HTML line breaks
  $text = str_replace (array("&lt;br /&gt;", "&lt;br/&gt;"), "<br/>", $text);

  // escape special characters <, >, &, ", '
  return $text;
}

// ----------------------------------------- getsearchhistory ------------------------------------------
// function: getsearchhistory()
// input: user name [string] (optional), clean history [boolean] (optional)
// output: array holding all expressions (in single quotes) of the search history of a user / false on error

function getsearchhistory ($user="", $clean=false)
{
  global $mgmt_config;

  if (is_file ($mgmt_config['abs_path_data']."log/search.log"))
  {
    // initialize
    $searchlog = "";
    $keywords = array();

    // load search log
    $data = file ($mgmt_config['abs_path_data']."log/search.log");

    if (is_array ($data) && sizeof ($data) > 0)
    {
      foreach ($data as $record)
      {
        if (substr_count ($record, "|") > 0)
        {
          $record = trim ($record);

          list ($date, $searchuser, $keyword_add) = explode ("|", $record);

          // clean entries
          if (!empty ($clean) && !empty ($date) && !empty ($searchuser) && trim ($keyword_add) != "" && !is_numeric ($keyword_add) && strlen ($keyword_add) < 800)
          {
            $searchlog .= $record."\n";
          }

          // collect entries
          if (($searchuser == $user || $user == "") && !is_numeric (trim ($keyword_add)) && strlen ($keyword_add) < 800 && strpos ("_".$keyword_add, "<") < 1 && strpos ("_".$keyword_add, ">") < 1)
          {
            // replace backslash and single quotes
            $keywords[] = "'".str_replace (array("\\", "'"), array("", "\\'"), trim ($keyword_add))."'";
          }
        }
      }

      // save cleaned search log
      if (!empty ($clean) && !empty ($searchlog)) savefile ($mgmt_config['abs_path_data']."log/", "search.log", $searchlog);

      // only unique expressions
      if (sizeof ($keywords) > 0) $keywords = array_unique ($keywords);

      return $keywords;
    }
  }
  
  return false;
}

// ----------------------------------------- getsynonym ------------------------------------------
// function: getescapedtext()
// input: word [string], 2-digit language code [string] (optional)
// output: array holding all synonyms including the provided word / false on error

// description:
// Returns the synonyms of a word.

function getsynonym ($text, $lang="")
{
  global $mgmt_config;

  require ($mgmt_config['abs_path_data']."include/synonyms.inc.php");

  if ($text != "")
  {
    // remove wild card characters * and ?
    $text_clean = trim (str_replace (array("*", "?"), "", strtolower ($text)));

    // search in all languages
    if ($lang == "" && !empty ($synonym) && is_array ($synonym))
    {
      foreach ($synonym as $lang=>$lang_array)
      {
        foreach ($synonym[$lang] as $text_array)
        {
          if (in_array ($text_clean, $text_array))
          {
            $intermediate = $text_array;
            break;
          }
        }
      }
    }
    // search in provided language
    elseif ($lang != "" && !empty ($synonym[$lang]) && is_array ($synonym[$lang]))
    {
      foreach ($synonym[$lang] as $text_array)
      {
        if (in_array ($text_clean, $text_array))
        {
          $intermediate = $text_array;
          break;
        }
      }
    }

    if (!empty ($intermediate))
    {
      $result = array();

      foreach ($intermediate as $word)
      {
        // add wild card characters * and ?
        if (substr ($text, 0, 1) == "*") $word = "*".$word;
        elseif (substr ($text, 0, 1) == "?") $word = "?".$word;
        if (substr ($text, -1) == "*") $word = $word."*";
        elseif (substr ($text, -1) == "?") $word = $word."?";

        $result[] = $word;
      }

      return $result;
    }
    else return array ($text);
  }
  else return false;
}

// ----------------------------------------- gettaxonomy_sublevel ------------------------------------------
// function: gettaxonomy_sublevel()
// input: publication name [string], language code [string] (optional), taxonomy parent ID [string] (optional), sort by name [boolean] (optional)
// output: array holding all keywords of the next taxonomy level / false on error

// description:
// Returns the sorted keywords of a taxonomy level (multilingual support based on taxonomies).
// The global variable $taxonomy can be used to pass the taxonomy as array.

function gettaxonomy_sublevel ($site, $lang="en", $tax_id="0", $sort=true)
{
  global $mgmt_config, $taxonomy;

  if ($lang != "" && intval ($tax_id) >= 0 && is_array ($mgmt_config))
  {
    // load taxonomy of publication
    if (!is_array ($taxonomy) && valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."include/".$site.".taxonomy.inc.php"))
    {
      include ($mgmt_config['abs_path_data']."include/".$site.".taxonomy.inc.php");
    }
    // load default taxonomy
    elseif (!is_array ($taxonomy) && is_file ($mgmt_config['abs_path_data']."include/default.taxonomy.inc.php"))
    {
      include ($mgmt_config['abs_path_data']."include/default.taxonomy.inc.php");
    }

    // return key = taxonomy ID and value = keyword
    // get childs
    $result = gettaxonomy_childs ($site, $lang, $tax_id, 1, true);

    // remove root element
    if (!empty ($result[$tax_id])) unset ($result[$tax_id]);

    // sort array
    if ($sort == true) natcasesort ($result);
    
    // return array 
    return $result;
  }
  else return false;
}

// ----------------------------------------- gettaxonomy_childs ------------------------------------------
// function: gettaxonomy_childs()
// input: publication name [string] (optional), taxonomy language code [string] (optional), 
//        taxonomy ID or expression or taxonomy path in the form %taxonomy%/publication-name/language-code/taxonomy-ID/taxonomy-child-levels or 'default'/language-code/taxonomy-ID/taxonomy-child-levels [string], 
//        taxonomy child levels to include [integer] (optional), only return taxonomy IDs without language and keyword information [boolean] (optional), return taxonomy ID path [boolean] (optional)
// output: array holding all taxonomy IDs / false on error

// description:
// Returns the keywords based on taxonomy definition and synonyms if expression is a keyword (multilingual support based on taxonomies and synonyms).
// The expression can be a taxonomy path in the form of %taxonomy%/site/language-code/taxonomy-ID/taxonomy-child-levels (use "all" for all languages and "0" for all taxonomy-IDs on first level).
// The global variable $taxonomy can be used to pass the taxonomy as array.

function gettaxonomy_childs ($site, $lang, $expression, $childlevels=1, $id_only=true, $id_path=false)
{
  global $mgmt_config, $taxonomy;

  if ($childlevels >= 0 && is_array ($mgmt_config))
  {
    $result = array();
    
    // get taxonomy parameters from search expression
    if (strpos ("_".$expression, "%taxonomy%/") > 0)
    {
      $slice = explode ("/", $expression);

      if (!empty ($slice[0])) $domain = $slice[0];
      if (!empty ($slice[1])) $site = $slice[1];
      if (!empty ($slice[2])) $lang = $slice[2];
      if (isset ($slice[3])) $tax_id = $slice[3];
      if (isset ($slice[4])) $childlevels = $slice[4];

      if (empty ($lang) || strtolower ($lang) == "all") $lang = "";
    }
    // search expression is taxonomy ID
    elseif (is_numeric ($expression) && intval ($expression) >= 0)
    {
      $tax_id = strval ($expression);
    }
    // search expression is a keyword
    elseif (is_string ($expression) && $expression != "" && strpos ("_".$expression, "*") < 1 && strpos ("_".$expression, "?") < 1)
    {
      // get synonyms if enabled
      if (!empty ($mgmt_config['search_synonym'])) $expression_array = getsynonym ($expression);
      else $expression_array = array ($expression);

      // to lower case
      $expression_array = array_map ('strtolower', $expression_array);
    }
    // invalid
    else return false;

    // load publication management config
    if (valid_publicationname ($site) && !isset ($mgmt_config[$site]['taxonomy']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // load taxonomy of publication
    if (!is_array ($taxonomy) && valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."include/".$site.".taxonomy.inc.php"))
    {
      include ($mgmt_config['abs_path_data']."include/".$site.".taxonomy.inc.php");
    }
    // load default taxonomy
    elseif (!is_array ($taxonomy) && is_file ($mgmt_config['abs_path_data']."include/default.taxonomy.inc.php"))
    {
      include ($mgmt_config['abs_path_data']."include/default.taxonomy.inc.php");
    }

    // search for taxonomy keyword and its childs
    if (!empty ($taxonomy) && is_array ($taxonomy) && sizeof ($taxonomy) > 0)
    {
      // verify language in taxonomy and set language is requested language is not available
      if (!empty ($lang) && empty ($taxonomy[$lang]))
      {
        reset ($taxonomy);
        $lang = key ($taxonomy);
      }

      // return key = taxonomy ID and value = keyword
      foreach ($taxonomy as $tax_lang=>$tax_array)
      {
        // selected language or all languages
        if ($tax_lang == $lang || empty ($lang))
        {
          foreach ($tax_array as $path=>$keyword)
          {
            // look up all root keywords on first level
            if (isset ($tax_id) && $tax_id == "0" && (substr_count ($path, "/") - 2) < $childlevels)
            {
              // get ID
              if (empty ($id_path))
              {
                $path_temp = substr ($path, 0, -1);
                $id = substr ($path_temp, strrpos ($path_temp, "/") + 1);
              }
              else $id = $path;

              if ($id_only == true) $result[$id] = $keyword;
              else $result[$tax_lang][$id] = $keyword;
            }
            // look up taxonomy ID
            elseif (!empty ($tax_id) && strpos ("_".$path, "/".$tax_id."/") > 0)
            {
              // count sublevels following the current ID
              $levels = substr_count (substr ($path, strpos ($path, "/".$tax_id."/")), "/") - 2;

              // verify child level limit
              if ($levels <= $childlevels)
              {
                // get ID
                if (empty ($id_path))
                {
                  $path_temp = substr ($path, 0, -1);
                  $id = substr ($path_temp, strrpos ($path_temp, "/") + 1);
                }
                else $id = $path;

                if ($id_only == true) $result[$id] = $keyword;
                else $result[$tax_lang][$id] = $keyword;
              }
            }
            // look up expression and get taxonomy ID if keyword is included in expressions array
            elseif (!empty ($expression_array) && array_search (strtolower ($keyword), $expression_array) !== false)
            {
              // get ID
              if (empty ($id_path))
              {
                $path_temp = substr ($path, 0, -1);
                $id = substr ($path_temp, strrpos ($path_temp, "/") + 1);
              }
              else $id = $path;

              if ($id != "")
              {
                if ($id_only == true) $result[$id] = $keyword;
                else $result[$tax_lang][$id] = $keyword;

                // set ID
                $tax_id = $id;
              }
            }
          }
        }
      }

      // return result
      if (is_array ($result) && sizeof ($result) > 0) return $result;
      else return false;
    }
    // taxonomy not enabled
    else return false;
  }
  else return false;
}

// ----------------------------------------- gethierarchy_definition ------------------------------------------
// function: gethierarchy_definition()
// input: publication name [string], hierarchy name [string] (optional) 
// output: hierarchy array in form of array[name][level][text-id][language] = label / false on error

// description:
// Reads the metadata/content hierarchy defintion and returns a multidimensinal array.

function gethierarchy_definition ($site, $selectname="")
{
  global $mgmt_config;

  if (valid_publicationname ($site) && !empty ($mgmt_config['abs_path_data']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".hierarchy.dat"))
  {
    $result = array();

    // load hierarchy file
    $record_array = file ($mgmt_config['abs_path_data']."config/".$site.".hierarchy.dat");

    if (is_array ($record_array) && sizeof ($record_array) > 0)
    {
      foreach ($record_array as $record)
      {
        $hierarchy_array = explode ("|", trim ($record));

        if (is_array ($hierarchy_array) && sizeof ($hierarchy_array) > 0)
        {
          $name = $hierarchy_array[0];
          $result[$name] = array();

          if (empty ($selectname) || $selectname == $name)
          {
            foreach ($hierarchy_array as $hierarchy)
            {
              $label = array();

              if (strpos ($hierarchy, "->") > 0)
              {
                list ($level, $text_id, $labels) = explode ("->", $hierarchy);

                // get labels
                if (!empty ($labels))
                {
                  // multiple labels
                  if (substr_count ($labels, ";") > 0)
                  {
                    $labels_array = explode (";", $labels);

                    if (is_array ($labels_array) && sizeof ($labels_array) > 0)
                    {
                      foreach ($labels_array as $label_entry)
                      {
                        list ($langcode, $text) = explode (":", $label_entry);

                        $label[trim ($langcode)] = trim ($text);
                      }
                    }
                  }
                  // single label and language
                  else
                  {
                    if (substr_count ($labels, ":") > 0)
                    {
                      list ($langcode, $label['default']) = explode (":", $labels);
                    }
                    else $label['default'] = $labels;
                  }
                }
                // or use text ID
                else
                {
                  if (substr_count ($text_id, ":") > 0)
                  {
                    list ($type, $label['default']) = explode (":", $text_id);
                  }
                  else $label['default'] = trim ($text_id);
                }

                // result array
                $result[$name][$level][$text_id] = $label;
              }
            }
          }
        }

        if ($selectname == $name) break;
      }
    }

    if (is_array ($result) && sizeof ($result) > 0)
    {
      ksort ($result);
      return $result;
    }
    else return false;
  }
  else return false;
}

// ----------------------------------------- gethierarchy_sublevel ------------------------------------------
// function: gethierarchy_sublevel()
// input: hierarchy URL in form  of %hierarchy%/publication-name/hierarchy-name/hierarchy-level-of-last-element/text-ID-1=value-1/text-ID-2=value-2/text-ID-3 [string]
// output: array holding all hierarchy URLs as key and text content or label as value / false on error

// description:
// Returns sorted values of a metadata/content hierarchy level.

function gethierarchy_sublevel ($hierarchy_url)
{
  global $mgmt_config, $lang;

  if (is_string ($hierarchy_url) && strpos ($hierarchy_url, "/") > 0 && is_array ($mgmt_config))
  {
    $result = array();

    // analyze hierarchy
    $hierarchy_url = trim ($hierarchy_url, "/");
    $hierarchy_array = explode ("/", $hierarchy_url);

    if (is_array ($hierarchy_array))
    {
      $domain = $hierarchy_array[0];
      $site = $hierarchy_array[1];
      $name = $hierarchy_array[2];
      $level = $hierarchy_array[3];
      $last_text_id = end ($hierarchy_array);

      // create new hierarchy URL for next level
      $hierarchy_url_new = str_replace ("/".$site."/".$name."/".$level."/", "/".$site."/".$name."/".($level + 1)."/", $hierarchy_url);

      // last hierarchy element presents a value pair
      if (is_string ($last_text_id) && strpos ($last_text_id, "=") > 0)
      {
        $hierarchy = gethierarchy_definition ($site, $name);

        if (is_array ($hierarchy) && sizeof ($hierarchy) > 0)
        {
          foreach ($hierarchy as $level_array)
          {
            if ($level > 0 && !empty ($level_array[$level]))
            {
              // select elements of requested level
              foreach ($level_array[$level] as $text_id => $label_array)
              {
                if ($text_id != "")
                {
                  if (!empty ($lang) && !empty ($label_array[$lang])) $label = $label_array[$lang];
                  else $label = $label_array['default'];

                  // create new hierarchy URL
                  $url = $hierarchy_url."/".$text_id;

                  $result[$url] = $label;
                }
              }
            }
          }
        } 
      }
      // last hierarchy element presents a text ID
      elseif (is_string ($last_text_id) && $last_text_id != "")
      {
        $text_id_array = array();

        // get text ID and value pairs
        foreach ($hierarchy_array as $hierarchy_element)
        {
          if (strpos ($hierarchy_element, "=") > 0)
          {
            list ($text_id, $value) = explode ("=", $hierarchy_element);

            $text_id_array[$text_id] = $value;
          }
        }

        $values = rdbms_gethierarchy_sublevel ($site, $last_text_id, $text_id_array);

        if (is_array ($values) && sizeof ($values) > 0)
        {
          foreach ($values as $value)
          {
            // escape /, : and = in value
            $value = str_replace ("/", "&#47;", $value);
            $value = str_replace (":", "&#58;", $value);
            $value = str_replace ("=", "&#61;", $value);

            // create new hierarchy URL with same level number
            $url = $hierarchy_url_new."=".$value;

            $result[$url] = trim ($value);
          }
        }
      }

      // return key = taxonomy ID and value = keyword
      if (is_array ($result) && sizeof ($result) > 0)
      {
        // return sorted array
        natcasesort ($result);
        return $result;
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// --------------------------------------- getkeywords -------------------------------------------
// function: getkeywords ()
// input: publication name [string] (optional) 
// output: keywords as array / false on error

// description:
// Generates an array holding all keywords and the number as value and keyword ID as key.

function getkeywords ($site="")
{
  global $mgmt_config;

  return rdbms_getkeywords ($site);
}

// --------------------------------------- getmetakeywords -------------------------------------------
// function: getmetakeywords ()
// input: text [string], language to be used for stop word list [de,en,...] (optional), character set [string] (optional) 
// output: keywords as array /false on error

// description:
// Generates a keyword list from a plain text. Stop word lists are defined in data/include/stopwords.inc.php

function getmetakeywords ($text, $language="en", $charset="UTF-8")
{
  global $mgmt_config;

  if ($text != "")
  {
    // include stopword lists
    include ($mgmt_config['abs_path_data']."/include/stopwords.inc.php");

    $language = strtolower ($language);

    // remove hmtl tags
    $text = trim (strip_tags ($text));

    // clean content
    $text = cleancontent ($text, $charset);

    // remove stopwords
    if (!empty ($stopwords[$language]) && is_array ($stopwords[$language])) $text = str_ireplace ($stopwords[$language]." ", "", $text);

    // extract the keywords
    $pattern1 = '/\b[A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+ [A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+ [A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+|[A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+ [A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+|[A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+\b/';
    $pattern2 = '/\b[A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+ [A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+ [A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+\b/';
    $pattern3 = '/\b[A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+ [A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+\b/';
    $pattern4 = '/\b[A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+\b/';

    preg_match_all ($pattern1, $text, $array1);
    $tags2 = implode (', ', $array1[0]);
    preg_match_all ($pattern2, $tags2, $array2);
    $tags3 = implode (', ', $array1[0]);
    preg_match_all ($pattern3, $tags3, $array3);
    $tags4 = implode (', ', $array1[0]);
    preg_match_all ($pattern4, $tags4, $array4);

    $array1[0] = array_map ('ucwords', array_map ('strtolower', $array1[0]));
    $array2[0] = array_map ('ucwords', array_map ('strtolower', $array2[0]));
    $array3[0] = array_map ('ucwords', array_map ('strtolower', $array3[0]));
    $array4[0] = array_map ('ucwords', array_map ('strtolower', $array4[0]));

    $ausgabe1 = array_count_values ($array1[0]);
    $ausgabe2 = array_count_values ($array2[0]);
    $ausgabe3 = array_count_values ($array3[0]);
    $ausgabe4 = array_count_values ($array4[0]);

    $new_keys = array_merge ($ausgabe1, $ausgabe2, $ausgabe3, $ausgabe4);

    // sort array
    array_multisort ($new_keys, SORT_DESC);

    $new_keys = array_slice ($new_keys, 0, 39);
    $keywords = array_keys ($new_keys);

    return $keywords;
  }
  else return false;
}

// --------------------------------------- getmetadescription -------------------------------------------
// function: getmetadescription ()
// input: text [string]
// output: cleanded description of provided text /false on error

// description:
// Generates a description from a text, to be used as meta information.

function getmetadescription ($text, $charset="UTF-8")
{
  if ($text != "")
  {
    // remove hmtl tags
    $text = trim (strip_tags ($text));

    // remove multiple white spaces
    $text = preg_replace ('/\s+/', ' ', $text);

    // decode html special characters
    $text = html_entity_decode ($text, ENT_COMPAT, $charset);

    // remove newlines
    $text = preg_replace ("/[\n\r]/", "", $text); 

    // shorten text
    if (strlen ($text) > 155)
    {
      if (strpos ($text, ".") > 0) $text = substr ($text, 0, strpos ($text, ".", 125));
      else $text = substr ($text, 0, strpos ($text, " ", 125));
    }

    return $text;
  }
  else return false;
}

// --------------------------------------- getgooglesitemap -------------------------------------------
// function: getgooglesitemap ()
// input: publication name [string], directory path [string], URL to directory [string], GET parameters to use for new versions of the URL as array (optional), permanent links text-ID to use for location [array] (optional), 
//        frequency of google scrawler [never,weekly,daily] (optional), priority [1 or less] (optional), 
//        ignore file names [array] (optional), allowed file types [array] (optional), include frequenzy tag [boolean] (optional), include priority tag [boolean] (optional)
// output: xml sitemap / false on error

// description:
// Generates a google sitemap xml-output

// help function
function collecturlnodes ($site, $dir, $url, $getpara, $permalink, $chfreq, $prio, $ignore_array, $filetypes_array, $show_freq, $show_prio)
{
  global $mgmt_config, $publ_config;

  if (valid_publicationname ($site) && $dir != "" && is_dir ($dir) && $url != "")
  {
    // add slash if not present at the end of the location string
    $dir = correctpath ($dir);
    if (substr ($url, -1) != "/") $url = $url."/";

    // ignore these files
    $ignore_default = array('.', '..', 'sitemap.xml', '.folder', '.htaccess');

    if (is_array ($ignore_array) && sizeof ($ignore_array) > 0) $ignore = array_merge ($ignore_array, $ignore_default);
    else $ignore = $ignore_default;

    // the replace array, this works as file => replacement, so 'index.php' => '', would make the index.php be listed as just /
    $replace = array('index.php' => '', 'index.htm' => '', 'index.html' => '', 'index.xhtml' => '', 'index.jsp' => '', 'default.asp' => '', 'default.aspx' => '');

    // crawl dir
    $scandir = scandir ($dir);
    $result_array = array();

    if ($scandir)
    {
      foreach ($scandir as $file)
      {
        // check if this file needs to be ignored, if so, skip it
        if (in_array ($file, $ignore)) continue;

        if (is_dir ($dir.$file))
        {
          $resultnew_array = collecturlnodes ($site, $dir.$file.'/', $url.$file.'/', $getpara, $permalink, $chfreq, $prio, $ignore_array, $filetypes_array, $show_freq, $show_prio);
          if (is_array ($resultnew_array)) $result_array = array_merge ($result_array, $resultnew_array);
        }

        // check whether the file has one of the extensions allowed for this XML sitemap
        $fileinfo = pathinfo ($dir.$file);

        if (isset ($fileinfo['extension']) && in_array ($fileinfo['extension'], $filetypes_array))
        {
          // create a W3C valid date for use in the XML sitemap based on the file modification time
          $modified = date ('c', filemtime ($dir.$file));

          // replace the file with it's replacement from the settings, if needed
          if (in_array ($file, $replace)) $file = $replace[$file];

          // define priority if not given
          if ($prio == "" && $url != "")
          {
            $dirlevel = substr_count ($url, "/");
            if ($dirlevel <= 4) $setprio = 1;
            else $setprio = round ((4 / $dirlevel), 2);

            if ($setprio > 1) $setprio = 1;
          }
          else $setprio = 1;

          // use file path for location
          $navlink = array();
          $navlink[0] = $url.$file;

          // read location of permalink
          if (sizeof ($permalink) > 0)
          {
            $i = 0;

            // load content container of object
            $xmldata = getobjectcontainer ($site, $dir, $file, "sys");

            if ($xmldata != "")
            {
              reset ($permalink);

              foreach ($permalink as $text_id)
              {
                $textnode = selectcontent ($xmldata, "<text>", "<text_id>", $text_id);

                if ($textnode != false)
                {
                  $textcontent = getcontent ($textnode[0], "<textcontent>", true);

                  if (!empty ($textcontent[0]))
                  {
                    $navlink[$i] = substr ($url, 0, strpos ($url, "/", 8)).$textcontent[0];
                    $i++;
                  }
                }
              }
            }
          }

          // creating the url nodes
          if (is_array ($navlink) && sizeof ($navlink) > 0)
          {
            foreach ($navlink as $location)
            {
              $result_string = "
  <url>
    <loc>".$location."</loc>
    <lastmod>".$modified."</lastmod>";
          if ($show_freq) $result_string .= "
    <changefreq>".$chfreq."</changefreq>";
          if ($show_prio) $result_string .= "
    <priority>".$setprio."</priority>";
          $result_string .= "
  </url>";

              $result_array[] = $result_string;
            }
          }

          // if GET parameters should be added to create new versions of the URL
          if (is_array ($getpara) && sizeof ($getpara) > 0)
          {
            foreach ($getpara as $add)
            {
              if ($add != "") 
              {
                $result_string = "
    <url>
      <loc>".$url.$file."?".$add."</loc>
      <lastmod>".$modified."</lastmod>";
                if ($show_freq) $result_string .= "
      <changefreq>".$chfreq."</changefreq>";
                if ($show_prio) $result_string .= "
      <priority>".$setprio."</priority>";
                $result_string .= "
    </url>";

                $result_array[] = $result_string;
              }
            }
          }
        }
      }
    }

    if (sizeof ($result_array) > 0) return $result_array;
    else return false;
  }
  else return false;
}

function getgooglesitemap ($site, $dir, $url, $getpara=array(), $permalink=array(), $chfreq="weekly", $prio="", $ignore=array(), $filetypes=array('cfm','htm','html','xhtml','asp','aspx','jsp','php','pdf'), $show_freq=true, $show_prio=true)
{
  global $mgmt_config, $publ_config;

  if (valid_publicationname ($site) && $dir != "" && is_dir ($dir) && $url != "")
  {
    // cget url nodes
    $result_array = collecturlnodes ($site, $dir, $url, $getpara, $permalink, $chfreq, $prio, $ignore, $filetypes, $show_freq, $show_prio);

    if (sizeof ($result_array) > 0)
    {
      $result = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<urlset
  xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"
  xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
  xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9
      http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n";

      $result .= implode ("", $result_array)."\n";

      $result .= "</urlset>";

      return $result;
    }
    else return false;
  }
  else return false;
}

// --------------------------------------- getgoogleanalytics -------------------------------------------
// function: getgoogleanalytics ()
// input: google analytics key publication name [string]
// output: JS code as string / false on error

// description:
// Generates a google analytics code segment for embedding.

function getgoogleanalytics ($google_analytics_key)
{
  global $mgmt_config;

  if (is_string ($google_analytics_key) && trim ($google_analytics_key) != "" && specialchr ($google_analytics_key, "-") == false)
  {
    return "
  <!-- Google Analytics -->
  <script type=\"text/javascript\">
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
  
  ga('create', '".$google_analytics_key."', 'auto');
  ga('send','pageview');
  
  </script>
  <!-- End Google Analytics -->";
  }
  else return false;
}

// ---------------------- getlistelements -----------------------------
// function: getlistelements()
// input: content attribute value of list or keyword tag, seperator of list elements [string] (optional)
// output: string with list/keyword elements seperated by commas / false on error

function getlistelements ($list_sourcefile)
{
	global $mgmt_config, $lang;

	if (valid_locationname ($list_sourcefile))
  {
    $list = "";

    // get taxonomy parameters
    if (strpos ("_".$list_sourcefile, "%taxonomy%/") > 0)
    {
      $slice = explode ("/", $list_sourcefile);

      if (!empty ($slice[0])) $domain = $slice[0];
      if (!empty ($slice[1])) $publication = $slice[1];
      if (!empty ($slice[2])) $language = $slice[2];
      if (isset ($slice[3])) $taxonomy_id = $slice[3];
      if (isset ($slice[4])) $taxonomy_levels = $slice[4];

      // set user language as default
      if ((empty ($language) || strtolower ($language) == "all") && !empty ($lang)) $language = $lang;
      // use default language
      elseif (empty ($language)) $language = "en";

      // reset source file to service/getkeywords
      if (!empty ($publication) && !empty ($language) && intval ($taxonomy_id) >= 0)
      {
        if ($taxonomy_id == "") $taxonomy_id = 0;
        if ($taxonomy_levels < 0) $taxonomy_levels = 5;

        // deprecated since the service getkeywords might be blocked:
        // $list_sourcefile = $mgmt_config['url_path_cms']."service/getkeywords.php?site=".url_encode($publication)."&lang=".url_encode($language)."&id=".url_encode($taxonomy_id)."&levels=".url_encode($taxonomy_levels);
        // get keywords
        // if (!empty ($list_sourcefile)) $list .= @file_get_contents ($list_sourcefile);

        // collect keywords of a taxonomy and return as comma seperated list       
        $keywords_array = gettaxonomy_childs ($publication, $language, $taxonomy_id, $taxonomy_levels);

        if (is_array ($keywords_array) && sizeof ($keywords_array) > 0)
        {
          $keywords_array = array_unique ($keywords_array);

          // escape commas
          foreach ($keywords_array as &$keyword)
          {
            $keyword = str_replace (",", "¸", $keyword);
          }

          $list .= implode (",", $keywords_array);
        }
      }
    }
    // get folder structure parameters
    elseif (is_dir ($list_sourcefile) || strpos ("_".$list_sourcefile, "%comp%/") > 0 || strpos ("_".$list_sourcefile, "%page%/") > 0)
    {
      $sourcelocation = deconvertpath ($list_sourcefile, "file");

      if (is_dir ($sourcelocation))
      {
        $scandir = scandir ($sourcelocation);

        if ($scandir)
        {
          $folder_array = array();

          foreach ($scandir as $item) 
          {
            if (is_dir ($sourcelocation.$item) && $item != '.' && $item != '..') 
            {
              $folder_array[] = specialchr_decode ($item);
            }
          }

          if (is_array ($folder_array) && sizeof ($folder_array) > 0)
          {
            natcasesort ($folder_array);
            $list .= implode (",", $folder_array);
          }
        }
      }
    }
    // get parameters from file or service (must be comma-seperated)
    elseif (is_file ($list_sourcefile) || strpos ("_".$list_sourcefile, "://") > 0)
    {
      $list .= @file_get_contents ($list_sourcefile);
    }

    if ($list != "") return $list;
    else return false;
  }
  else return false;
}

// ---------------------- getmetadata -----------------------------
// function: getmetadata()
// input: location [string], object name (both optional if container is given) [string], container name/ID or container content [string] (optional), 
//         separator of meta data fields [any string,array] (optional), publication name/template name to extract label names [string] (optional)
// output: string with all metadata from given object based on container / false

function getmetadata ($location, $object, $container="", $separator="\r\n", $template="")
{
	global $mgmt_config, $lang;

  // deconvert location
  if (@substr_count ($location, "%page%") > 0 || @substr_count ($location, "%comp%") > 0)
  {
    $site = getpublication ($location);
    $cat = getcategory ($site, $location);
    $location = deconvertpath ($location, "file");
  }
  elseif (strpos ($template, "/") > 0)
  {
    list ($site, $template) = explode ("/", $template);
  }

  // if object includes special characters
  if (specialchr ($object, ".-_~") == true) $object = specialchr_encode ($object, false);

	if ((valid_locationname ($location) && valid_objectname ($object)) || $container != "")
  {
    // if object is folder
  	if (is_dir ($location.$object))
    {
  		$location = $location.$object."/";
  		$object = ".folder";
  	}

    // get all pointers from object file
    if (is_file ($location.$object))
    {
  		// read file
  		$objectdata = loadfile ($location, $object);

  		// get name of container
  		if ($objectdata != "")
      {
        $container = getfilename ($objectdata, "content");
        $template = getfilename ($objectdata, "template");
        $mediafile = getfilename ($objectdata, "media");
      }
    }

		// read meta data of media file
		if ($container != false)
    {
      // container need to be loaded
      if (valid_objectname ($container) && strpos ($container, "<container>") < 1)
      {
  			if (strpos ($container, ".xml") > 0)
        {
          $container_id = substr ($container, 0, strpos ($container, ".xml")); 
        }
        elseif (intval ($container) > 0)
        {
          $container_id = $container;
          $container = $container_id.".xml";
        }

  			$result = getcontainername ($container);
  			$container = $result['container'];
  			$contentdata = loadcontainer ($container, "version", "sys");
      }
      // container content is available
      else $contentdata = $container;

      $labels = Null;

      // load template and define labels
      if (!empty ($site) && !empty ($template))
      {
        $result = loadtemplate ($site, $template);

        if (!empty ($result['content']))
        {
          $position = array();

          $hypertag_array = gethypertag ($result['content'], "text", 0);

          if (is_array ($hypertag_array))
          {
            $labels = array();
            $i = 1;

            foreach ($hypertag_array as $tag)
            {
              // get mediatype
              $mediatype = getattribute ($tag, "mediatype");

              // verify mediatype for assets only
              if (!empty ($mediatype) && !empty ($mediafile))
              {
                $continue = true;

                if (strpos (strtolower ("_".$mediatype), "audio") > 0 && is_audio ($mediafile)) $continue = false;
                elseif (strpos (strtolower ("_".$mediatype), "image") > 0 && is_image ($mediafile)) $continue = false;
                elseif ((strpos (strtolower ("_".$mediatype), "document") > 0 || strpos (strtolower ("_".$mediatype), "text") > 0) && is_document ($mediafile)) $continue = false;
                elseif (strpos (strtolower ("_".$mediatype),"video") > 0 && is_video ($mediafile)) $continue = false;
                elseif (strpos (strtolower ("_".$mediatype), "compressed") > 0 && is_compressed ($mediafile)) $continue = false;

                if ($continue == true) continue;
              }

              $id = getattribute ($tag, "id");
              $label = getattribute ($tag, "label");

              if ($id != "")
              {
                if ($label != "") $labels[$id] = getlabel ($label, $lang);
                else $labels[$id] = str_replace ("_", " ", $id);

                $position[$id] = $i;
                $i++;
              }
            }
          }
        }
      }

			if (!empty ($contentdata))
      {
        $metadata = array();

        // if labels were defined (labels are not unique)
        if (is_array ($labels) && sizeof ($labels) > 0)
        {
          foreach ($labels as $id => $label)
          {
            $text_str = "";

            // dont include comments or articles (they use :) or JSON string of faces definition
            if (strpos ($id, ":") == 0 && $id != "Faces-JSON")
            {
              $textnode = selectcontent ($contentdata, "<text>", "<text_id>", $id);

              if ($textnode != false)
              {
                $text_content = getcontent ($textnode[0], "<textcontent>", true);

                // strip tags and replace double by single quotes
                if (!empty ($text_content[0]))
                {
                  $text_content[0] = str_replace ("\"", "'", strip_tags ($text_content[0]));
                }

                // add space after comma
                if (!empty ($text_content[0]) && strpos ($text_content[0], ",") > 0 && strpos ($text_content[0], ", ") < 1)
                {
                  $text_content[0] = str_replace (",", ", ", $text_content[0]);
                }

    						if (!empty ($text_content[0])) $text_str = $text_content[0];
              }
            }

            if (!empty ($position[$id])) $pos = $position[$id];

						if (strtolower ($separator) != "array") $metadata[$pos] = $label.": ".$text_str;
            else $metadata["<!--".$pos."-->".$label] = $text_str;
          }
        }
        // if no template and therefore no labels are defined
        else
        {
  				$textnode = getcontent ($contentdata, "<text>");

  				if ($textnode != false)
          {
  					foreach ($textnode as $buffer)
            {
              // get info from container
  						$text_id = getcontent ($buffer, "<text_id>");

              // dont include comments or articles (they use :) or JSON string of faces definition
              if (strpos ($text_id[0], ":") == 0 && $text_id[0] != "Faces-JSON")
              {
                $label = str_replace ("_", " ", $text_id[0]);

                $text_content = getcontent ($buffer, "<textcontent>", true);
                
                // strip tags and replace double by single quotes
                if (!empty ($text_content[0]) && strpos ("_".$text_content[0], "\"") > 0)
                {
                  $text_content[0] = str_replace ("\"", "'", strip_tags ($text_content[0]));
                }

                // add space after comma
                if (!empty ($text_content[0]) && strpos ($text_content[0], ",") > 0 && strpos ($text_content[0], ", ") < 1)
                {
                  $text_content[0] = str_replace (",", ", ", $text_content[0]);
                }

    						if (strtolower ($separator) != "array") $metadata[] = $label.": ".$text_content[0];
                else $metadata[$label] = $text_content[0];
              }
  					}
  				}
        }

        // convert array to string
        if (is_array ($metadata) && strtolower ($separator) != "array")
        {
          ksort ($metadata);
          $metadata = implode ($separator, $metadata);
        }

				return $metadata;
			}
		}
	}
  
  return false;
}

// ---------------------- getmetadata_multiobjects -----------------------------
// function: getmetadata_multiobjects()
// input: converted path of multiple objects [array], user name [string], include content of subfolders [boolean] (optional)
// output: assoziatve array with all text content and meta data / false

// description:
// Extracts all metadata including media information for a provided list of objects.
// This function is used for the CSV export in the objectlist views and also evaluates the access permissions of the user.

function getmetadata_multiobjects ($multiobject_array, $user, $include_subfolders=false)
{
  global $mgmt_config, $siteaccess, $pageaccess, $compaccess, $hiddenfolder, $adminpermission, $localpermission;

  if (is_array ($multiobject_array) && sizeof ($multiobject_array) > 0 && $user != "")
  {
    // initialze
    $result = array();
    $text_ids = array();
    $intermediate = array();

    // include content of subfolders
    if (!empty ($include_subfolders))
    {
      foreach ($multiobject_array as $multiobject)
      {
        if (getobject ($multiobject) == ".folder" || is_dir (deconvertpath ($multiobject, "file")))
        {
          if (getobject ($multiobject) == ".folder") $folderpath = getlocation ($multiobject);
          else $folderpath = $multiobject; 

          $temp_array = rdbms_externalquery ('SELECT objectpath FROM object WHERE objectpath LIKE "'.$folderpath.'%" AND objectpath!="'.$folderpath.'.folder"');

          if (is_array ($temp_array) && sizeof ($temp_array) > 0)
          {
            $multiobject_array_add = array();

            // convert to 1 dimensional array
            foreach ($temp_array as $temp) $multiobject_array_add[] = $temp['objectpath'];

            if (is_array ($multiobject_array_add) && sizeof ($multiobject_array_add) > 0)
            {
              $multiobject_array = array_merge ($multiobject_array, $multiobject_array_add);
              $multiobject_array = array_unique ($multiobject_array);
            }
          }
        }
      }
    }

    // sort
    sort ($multiobject_array);

    // query for each object
    foreach ($multiobject_array as $multiobject)
    {
      // only accept converted locations
      if ($multiobject != "" && (substr_count ($multiobject, "%page%") > 0 || substr_count ($multiobject, "%comp%") > 0))
      {
        $site = getpublication ($multiobject);
        $location = getlocation ($multiobject);
        $object = getobject ($multiobject);
        $cat = getcategory ($site, $location);

        // check access permission uf user
        $ownergroup = accesspermission ($site, $location, $cat);
        $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

        if ($ownergroup != false && $setlocalpermission['root'] == 1 && valid_locationname ($location) && valid_objectname ($object))
        {
          // get object info
          $objectinfo = getobjectinfo ($site, $location, $object);

          if (!empty ($objectinfo['container_id']))
          {
            $container_id = intval ($objectinfo['container_id']);

            // query
            $query_attritbutes = 'id AS "Container-ID", objectpath AS "Objectpath", template AS "Template", createdate AS "Date created", date AS "Date modified", publishdate AS "Date published", user AS "Owner", latitude AS "Latitude", longitude AS "Longitude", filesize AS "Size in KB", filetype AS "File type", width AS "Width in PX", height AS "Height in PX", red AS "Red", green AS "Green", blue AS "Blue", colorkey AS "Colorkey", imagetype AS "Image type", md5_hash AS "MD5 hash", analyzed AS "Analyzed"';

            $objectdata = rdbms_externalquery ('SELECT '.$query_attritbutes.' FROM object WHERE object.id='.$container_id);

            if (is_array ($objectdata) && sizeof ($objectdata) > 0)
            {
              // add container ID again
              $result[$multiobject]['Container-ID'] = $container_id;

              // unescape objectpath and remove .folder
              if (!empty ($objectdata[0]['Objectpath']))
              {
                $objectdata[0]['Objectpath'] = specialchr_decode ($objectdata[0]['Objectpath']);

                // if folder
                if (getobject ($objectdata[0]['Objectpath']) == ".folder")
                {
                  $objectdata[0]['Objectpath'] = getlocation ($objectdata[0]['Objectpath']);
                }
              }

              // prepare result array
              foreach ($objectdata[0] as $key => $value)
              {
                $result[$multiobject][$key] = $value;
              }

              // query all content excluding references and file content
              $textnodes = rdbms_externalquery ('SELECT text_id, textcontent FROM textnodes WHERE id='.$container_id.' AND type!="file" AND type!="media" AND type!="page" AND type!="comp"');
              
              // text content
              if (is_array ($textnodes) && sizeof ($textnodes) > 0)
              {
                foreach ($textnodes as $textnode)
                {
                  if (is_array ($textnode))
                  {
                    $intermediate[$multiobject][$textnode['text_id']] = strip_tags (html_entity_decode ($textnode['textcontent'], ENT_COMPAT));

                    // collect text IDs
                    if (!in_array ($textnode['text_id'], $text_ids)) $text_ids[] = $textnode['text_id'];
                  }
                }
              }
            }
          }
        }
      }
    }

    // create new array with all text IDs for all objects
    if (is_array ($result) && sizeof ($result) > 0)
    { 
      if (is_array ($intermediate) && sizeof ($intermediate) > 0 && is_array ($text_ids) && sizeof ($text_ids) > 0)
      {
        foreach ($result as $key => $temp)
        {
          foreach ($text_ids as $text_id)
          {
            if (isset ($intermediate[$key][$text_id])) $result[$key]['Content:'.$text_id] = $intermediate[$key][$text_id];
            else $result[$key]['Content:'.$text_id] = "";
          }
        }
      }

      return $result;
    }
  }
  
  return false;
}

// ---------------------- getmetadata_container -----------------------------
// function: getmetadata_container()
// input: container ID [string], array of text IDs [array] (optional)
// output: assoziatve array with all text content and meta data / false

// description:
// Extracts container, media, and metadata information of a container.
// This function is used for the presentation of metadata for objectlist views.

function getmetadata_container ($container_id, $text_id_array=array())
{
  global $mgmt_config, $labels;

  if (intval ($container_id) > 0 && is_array ($text_id_array))
  {
    $result = array();
    $result['createdate'] = "";
    $result['date'] = "";
    $result['publishdate'] = "";
    $result['user'] = "";

    // use database
    if (!empty ($mgmt_config['db_connect_rdbms']))
    {
      // collect container and media info
      $select = "";

      // date created
      if (in_array ("createdate", $text_id_array))
      {
        if ($select != "") $select .= ', ';
        $select .= 'createdate';
      }

      // date modified
      if (in_array ("modifieddate", $text_id_array) || in_array ("date", $text_id_array))
      {
        if ($select != "") $select .= ', ';
        $select .= 'date';
      }

      // date modified
      if (in_array ("publishdate", $text_id_array))
      {
        if ($select != "") $select .= ', ';
        $select .= 'publishdate';
      }

      // user/owner
      if (in_array ("owner", $text_id_array) || in_array ("user", $text_id_array))
      {
        if ($select != "") $select .= ', ';
        $select .= 'user';
      }

      // file size
      if (in_array ("filesize", $text_id_array))
      {
        if ($select != "") $select .= ', ';
        $select .= 'filesize';
      }

      // media dimensions
      if ($select != "") $select = ', '.$select;

      $sql = 'SELECT width, height'.$select.' FROM object WHERE id='.intval($container_id);
      $objectdata = rdbms_externalquery ($sql);

      // reduce array
      if (is_array ($objectdata) && sizeof ($objectdata) > 0) $result = $objectdata[0];

      // collect text content
      $conditions = "";

      if (is_array ($text_id_array) && sizeof ($text_id_array) > 0)
      {
        foreach ($text_id_array as $text_id)
        {
          if (substr ($text_id, 0, 5) == "text:")
          {
            if ($conditions != "") $conditions .= ' OR ';

            $conditions .= 'text_id="'.substr ($text_id, 5).'"';
          }
        }

        if ($conditions != "") $conditions = ' AND ('.$conditions.')';
      }

      if ($conditions != "")
      {
        $sql = 'SELECT text_id, textcontent FROM textnodes WHERE id='.intval($container_id).$conditions;

        // query
        $textnodes = rdbms_externalquery ($sql);

        // text content
        if (is_array ($textnodes) && sizeof ($textnodes) > 0)
        {
          foreach ($textnodes as $textnode)
          {
            if (!empty ($textnode['text_id']))
            {
              $result['text:'.$textnode['text_id']] = $textnode['textcontent'];
            }
          }
        }
      }
    }
    // use content container
    else
    {
      $contentdata = loadcontainer ($container_id, "work", "sys");

      if ($contentdata != "")
      {
        // date created
        if (in_array ("createdate", $text_id_array))
        {
          $temp = getcontent ($contentdata, "<contentcreated>");

          if (!empty ($temp[0])) $result['createdate'] = $temp[0];
        }

        // date modified
        if (in_array ("modifieddate", $text_id_array))
        {
          $temp = getcontent ($contentdata, "<contentdate>");

          if (!empty ($temp[0])) $result['date'] = $temp[0];
        }

        // date published
        if (in_array ("publishdate", $text_id_array))
        {
          $temp = getcontent ($contentdata, "<contentpublished>");

          if (!empty ($temp[0])) $result['publishdate'] = $temp[0];
        }

        // user/owner
        if (in_array ("owner", $text_id_array))
        {
          $temp = getcontent ($contentdata, "<contentuser>");

          if (!empty ($temp[0])) $result['user'] = $temp[0];
        }

        if (is_array ($text_id_array) && sizeof ($text_id_array) > 0)
        {
				  $textnode = getcontent ($contentdata, "<text>");

					foreach ($textnode as $buffer)
          {
            // get info from container
						$text_id = getcontent ($buffer, "<text_id>");

            // only include requested text IDs
            if (!empty ($text_id[0]) && in_array ("text:".$text_id[0], $text_id_array))
            {
              $text_content = getcontent ($buffer, "<textcontent>", true);

              if (!empty ($text_content[0]))
              {
                $text_content[0] = cleancontent ($text_content[0]);
                $result['text:'.$text_id[0]] = $text_content[0];
              }
            }
					}
				}
      }
    }

    if (is_array ($result) && sizeof ($result) > 0) return $result;
  }
  
  return false;
}

// ---------------------------------------- getobjectlist ----------------------------------------
// function: getobjectlist()
// input: publication name [string] (optional), location [string] (optional), folder hash code [string,array] (optional), search parameters [array] (optional), information and text IDs to be returned e.g. text:Title [array] (optional), 
//        verify RESTful API for the publication [boolean] (optional), return all levels even if the user has no access permission to the folder [boolean] (optional), return readable/decoded objectpath or encoded objectpath [boolean] (optional)
// output: result array / false on error
// requires: config.inc.php

// description:
// Get all objects of a location. This is a simplified wrapper for function rdbms_searchcontent.

function getobjectlist ($site="", $location="", $folderhash="", $search=array(), $objectlistcols=array(), $checkREST=false, $return_all_levels=true, $readable_objectpath=false)
{
  global $mgmt_config, $user, $lang, $hcms_lang,
  $rootpermission, $globalpermission, $localpermission,
  $siteaccess, $pageaccess, $compaccess,
  $adminpermission, $hiddenfolder;

  // initialize
  $result = array();
  if (!is_array ($search)) $search = array();
  $root_dir_esc = array();
  $search_dir_esc = array();
  $search_active = false;
  $search_dir_active = false;
  $search_format_folder = true;
  $return_site_access = false;
  $exclude_dir_esc = array();
  $setlocalpermission = array();

  // verify permission for the RESTful API
  if ($checkREST == true)
  {
    if (valid_publicationname ($site))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

      if (empty ($mgmt_config[$site]['connector_rest'])) return false;
    }
    else
    {
      $site = getpublication ($location);

      if (valid_publicationname ($site))
      {
        require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

        if (empty ($mgmt_config[$site]['connector_rest'])) return false;
      }
    }
  }

  // define default values
  // to add all text IDs use: "text:temp"
  if (empty ($objectlistcols)) $objectlistcols = array("object", "location", "template", "wrapperlink", "downloadlink", "thumbnail", "modifieddate", "createdate", "publisheddate", "owner", "filesize", "width", "height");

  // search is used (excluding certain search filters)
  if (is_array ($search))
  {
    foreach ($search as $key=>$value)
    {
      if (($key == "samelocation" && $value == false) || ($key != "samelocation" && $key != "format" && $key != "fileextension" && $key != "limit" && $value != ""))
      {
        $search_active = true;
        break;
      }
    }
  }

  // query result limit
  if (!empty ($search['limit']))
  {
    if (strpos ($search['limit'], ",") > 0)
    {
      list ($starthits, $endhits) = explode (",", $search['limit']);
      $maxresultsize = intval ($endhits) - intval ($starthits);
    }
    else $maxresultsize = intval ($search['limit']);
  }
  else $maxresultsize = $search['limit'] = 500;

  // search expression
  if (!empty ($search['expression']))
  {
    $search['expression_array'] = array();
    $search['expression_array'][0] = $search['expression'];
  }
  else $search['expression_array'] = "";

  // search for file or folder name
  if (empty ($search['filename'])) $search['filename'] = "*Null*";

  // format values: folder,audio,binary,compressed,document,flash,image,text,video,unknown
  $filter_names = array ("folder", "page", "comp", "image", "document", "text", "video", "audio", "flash", "compressed", "binary");

  if (!empty ($search['format']) && is_array ($search['format']))
  {
    foreach ($search['format'] as $key=>$value)
    {
      if (in_array (strtolower ($value), $filter_names)) $search['format'][$key] = strtolower (trim ($value));
      else unset ($search['format'][$key]);

      // folder format filter is set
      if ($value == "folder") $temp_folder = true;
    }

    // in order to exclude the users root access folders based on the requested format
    if (sizeof ($search['format']) > 0 && !empty ($temp_folder)) $search_format_folder = true;
    else $search_format_folder = false;
  }
  else $search['format'] = "";

  // file extensions
  if (empty ($search['fileextension']) || !is_array ($search['fileextension'])) $search['fileextension'] = "";

  // modified date
  if (empty ($search['date_modified_from'])) $search['date_modified_from'] = "";
  if (empty ($search['date_modified_to'])) $search['date_modified_to'] = "";

  // image width and height in pixel
  // parameter imagewidth can be used as general image size parameter (min-max)
  if (empty ($search['imagewidth'])) $search['imagewidth'] = "";
  if (empty ($search['imageheight'])) $search['imageheight'] = "";

  // color code that defines a primary color: K for Black, W for White, E for Grey, R for Red, G for Green, B for Blue, C for Cyan, M for Magenta, Y for Yellow, O for Orange, P for Pink, N for Brown
  if (empty ($search['imagecolor']) || !is_array ($search['imagecolor'])) $search['imagecolor'] = "";

  // image type: portrait,landscape,square
  if (!empty ($search['imagetype'])) $search['imagetype'] = strtolower ($search['imagetype']);
  else $search['imagetype'] = "";

  // object and container ID
  if (empty ($search['object_id'])) $search['object_id'] = "";
  if (empty ($search['container_id'])) $search['container_id'] = "";

  // geo location
  if (empty ($search['geo_border_sw'])) $search['geo_border_sw'] = "";
  if (empty ($search['geo_border_ne'])) $search['geo_border_ne'] = "";

  // convert string to array
  if (!empty ($folderhash))
  {
    if (!is_array ($folderhash)) $folderhash_array = array($folderhash);
    else $folderhash_array = $folderhash;
  }
  // create dummy array element
  else $folderhash_array = array (0 => "");

  foreach ($folderhash_array as $folderhash)
  {
    // get location from hash code
    if ($folderhash != "")
    {
      $folderpath = rdbms_getobject ($folderhash);
      $site = getpublication ($folderpath);
      $location = getlocation ($folderpath);
    }

    // use provided location and verify access permissions
    if (valid_publicationname ($site) && valid_locationname ($location))
    {
      // convert special characters
      $location = specialchr_encode ($location, false);

      // get category
      $cat = getcategory ($site, $location);

      // check access permission
      if ($return_all_levels == false)
      {
        $ownergroup = accesspermission ($site, $location, $cat);
        $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

        // positive access
        if (!empty ($setlocalpermission['root']))
        {
          // define location
          $search_dir_esc[] = convertpath ($site, $location, $cat);

          // search location has been defined
          $search_dir_active = true;
        }
      }
      // dont check access permission
      else
      {
        // define location
        $search_dir_esc[] = convertpath ($site, $location, $cat);

        // search location has been defined
        $search_dir_active = true;
      }
    }

    // favorites
    if ($location == "%favorites%" || $location == "%favorites%/")
    {
      // $root_dir_esc = getfavorites ($user, "path", $objectlistcols);
    }
    // publication and location not specified, use publication access
    elseif ($location == "%publication%" || $location == "%publication%/")
    {
      // favorites
      // $root_dir_esc['virtualfavorites']['objectpath'] = "%favorites%/".$hcms_lang['favorites'][$lang]."/";
      // $root_dir_esc['virtualfavorites']['location'] = getlocation ($root_dir_esc['virtualfavorites']['objectpath']);
      // $root_dir_esc['virtualfavorites']['object'] = getobject ($root_dir_esc['virtualfavorites']['objectpath']);

      if (!empty ($siteaccess) && is_array ($siteaccess))
      {
        $temp_sites = array_unique ($siteaccess);
        natcasesort ($temp_sites);

        foreach ($temp_sites as $temp_site => $temp_displayname)
        {
          // publication management config
          if (valid_publicationname ($temp_site) && is_file ($mgmt_config['abs_path_data']."config/".$temp_site.".conf.php"))
          {
            require_once ($mgmt_config['abs_path_data']."config/".$temp_site.".conf.php");

            if ($checkREST == false || !empty ($mgmt_config[$temp_site]['connector_rest']))
            {
              // assets
              if (strpos ("_".$location, "%comp%/") > 0)
              {
                $temp_objectpath_esc = "%comp%/".$temp_site."/.folder";
                $temp_hash = rdbms_getobject_hash ($temp_objectpath_esc);
                if (!empty ($temp_hash)) $root_dir_esc[$temp_hash]['objectpath'] = $temp_objectpath_esc;
              }
              // pages
              elseif (strpos ("_".$location, "%page%/") > 0)
              {
                $temp_objectpath_esc = "%page%/".$temp_site."/.folder";
                $temp_hash = rdbms_getobject_hash ($temp_objectpath_esc);
                if (!empty ($temp_hash)) $root_dir_esc[$temp_hash]['objectpath'] = $temp_objectpath_esc;
              }
              // both
              else
              {
                $temp_objectpath_esc = "%comp%/".$temp_site."/.folder";
                $temp_hash = rdbms_getobject_hash ($temp_objectpath_esc);
                if (!empty ($temp_hash)) $root_dir_esc[$temp_hash]['objectpath'] = $temp_objectpath_esc;

                $temp_objectpath_esc = "%page%/".$temp_site."/.folder";
                $temp_hash = rdbms_getobject_hash ($temp_objectpath_esc);
                if (!empty ($temp_hash)) $root_dir_esc[$temp_hash]['objectpath'] = $temp_objectpath_esc;
              }

              // return publications of user instead of folder access 
              $return_site_access = true;
            }
          }
        }
      }
    }
    // if a specific location has been requested or is empty
    else
    {
      // use compaccess (compaccess[publication][group]=[objectpath])
      if (!empty ($compaccess) && is_array ($compaccess) && (strpos ("_".$location, "%comp%/") > 0 || empty ($location)))
      {
        $temp_access = $compaccess;
        $temp_cat = "comp";

        // component access of user
        foreach ($compaccess as $temp_site => $value)
        {
          // verify publication
          if ((valid_publicationname ($site) && $site == $temp_site) || $site == "")
          {
            // publication management config
            if ($checkREST == true && valid_publicationname ($temp_site)) require_once ($mgmt_config['abs_path_data']."config/".$temp_site.".conf.php");

            // verify permission for the RESTful API
            if ($checkREST == false || !empty ($mgmt_config[$temp_site]['connector_rest']))
            {
              foreach ($value as $group_name => $pathes)
              {
                // split access-string into an array
                $path_array = link_db_getobject ($pathes);
                
                if (is_array ($path_array))
                {
                  foreach ($path_array as $path)
                  {
                    // add slash if missing
                    $path = correctpath ($path);
                    $temp_location_esc = convertpath ($temp_site, $path, "comp");

                    // verify location
                    if ((valid_locationname ($location) && (strpos ("_".$location, $temp_location_esc) > 0 || strpos ("_".$temp_location_esc, $location) > 0)) || $location == "")
                    {
                      $temp_hash = rdbms_getobject_hash ($temp_location_esc);

                      // check access permission
                      $ownergroup = accesspermission ($temp_site, $temp_location_esc, "comp");
                      $setlocalpermission = setlocalpermission ($temp_site, $ownergroup, "comp");

                      // check access permission
                      // set location if no search location has been requested
                      if ($search_dir_active == false && $return_all_levels == false && !empty ($setlocalpermission['root']))
                      {
                        // positive access that can be used for the search
                        if ($search_active == true) $search_dir_esc[] = $temp_location_esc;
                        // positive access that can be used as a root folder of the user
                        elseif ($search_active == false && $search_format_folder == true) $root_dir_esc[$temp_hash]['objectpath'] = $temp_location_esc.".folder";
                      }

                      // negative access
                      if (empty ($setlocalpermission['root']) && $return_all_levels == false) $exclude_dir_esc[] = $temp_location_esc;
                    }
                  }
                }
              }
            }
            // exclude publication 
            else
            {
              $exclude_dir_esc[] = "%comp%/".$temp_site."/";
            }
          }
        }
      }

      // use pageaccess (pageaccess[publication][group]=[objectpath])
      if (!empty ($pageaccess) && is_array ($pageaccess) && (strpos ("_".$location, "%page%/") > 0 || empty ($location)))
      {
        // page access of user
        foreach ($pageaccess as $temp_site => $value)
        {
          // verify publication
          if ((valid_publicationname ($site) && $site == $temp_site) || $site == "")
          {
            if ($checkREST == true &&  valid_publicationname ($temp_site)) require_once ($mgmt_config['abs_path_data']."config/".$temp_site.".conf.php");

            // verify permission for the RESTful API
            if ($checkREST == false || !empty ($mgmt_config[$temp_site]['connector_rest']))
            {
              foreach ($value as $group_name => $pathes)
              {
                // split access-string into an array
                $path_array = link_db_getobject ($pathes);
                
                if (is_array ($path_array))
                {
                  foreach ($path_array as $path)
                  {
                    // add slash if missing
                    $path = correctpath ($path);
                    $temp_location_esc = convertpath ($temp_site, $path, "page");

                    // verify location
                    if ((valid_locationname ($location) && (strpos ("_".$location, $temp_location_esc) > 0 || strpos ("_".$temp_location_esc, $location) > 0)) || $location == "")
                    {
                      $temp_hash = rdbms_getobject_hash ($temp_location_esc);

                      // check access permission
                      $ownergroup = accesspermission ($temp_site, $temp_location_esc, "page");
                      $setlocalpermission = setlocalpermission ($temp_site, $ownergroup, "page");

                      // check access permission
                      // set location if no search location has been requested
                      if ($search_dir_active == false && $return_all_levels == false && !empty ($setlocalpermission['root']))
                      {
                        // positive access that can be used for the search
                        if ($search_active == true) $search_dir_esc[] = $temp_location_esc;
                        // positive access that can be used as a root folder of the user
                        elseif ($search_active == false && $search_format_folder == true) $root_dir_esc[$temp_hash]['objectpath'] = $temp_location_esc.".folder";
                      }

                      // negative access
                      if (empty ($setlocalpermission['root']) && $return_all_levels == false) $exclude_dir_esc[] = $temp_location_esc;
                    }
                  }
                }
              }
            }
            // exclude publication 
            else
            {
              $exclude_dir_esc[] = "%page%/".$temp_site."/";
            }
          }
        }
      }
    }

    // use root folders of user
    if (is_array ($root_dir_esc) && sizeof ($root_dir_esc) > 0)
    {
      $result = $root_dir_esc;

      // set as site access since only folders with access permission of the user will be returned
      $return_site_access = true;
    }
    // search for objects
    elseif (!empty ($search_dir_esc) || is_array ($search))
    {
      // only search in the provided location and exclude the subfolders
      $mgmt_config['search_folderpath_level'] = true;

      // search in all subfolders if at least one search parameter is used
      if (is_array ($search) && sizeof ($search) > 0)
      {
        if (empty ($search['samelocation']) || strtolower ($search['samelocation']) == "false" || strtolower ($search['samelocation']) == "no" || $search['samelocation'] == "0")
        {
          $mgmt_config['search_folderpath_level'] = false;
        }
      }

      // search for object ID
      if (!empty ($search['object_id']))
      {
        $object_info = rdbms_getobject_info ($search['object_id'], $objectlistcols);
  
        if (is_array ($object_info) && !empty ($object_info['hash']))
        {
          $hash = $object_info['hash'];
          $result[$hash] = $object_info;
        }
      }
      // search
      elseif (!empty ($search_dir_esc) && is_array ($search_dir_esc) && sizeof ($search_dir_esc) > 0)
      {
        // we need to separate the search expression and the file name search due to performance issues since the search index will not be used if both are present in the same query
        // search for expression in content, requires $search['filename'] = "*Null*"
        if (!empty ($search['expression_array']) && is_array ($search['expression_array']) && sizeof ($search['expression_array']) > 0)
        {
          $search_filename = $search['filename'];
          $search['filename'] = "*Null*";
        }

        $result = rdbms_searchcontent ($search_dir_esc, $exclude_dir_esc, $search['format'], $search['date_modified_from'], $search['date_modified_to'], "", $search['expression_array'], $search['filename'], $search['fileextension'], "", $search['imagewidth'], $search['imageheight'], $search['imagecolor'], $search['imagetype'], $search['geo_border_sw'], $search['geo_border_ne'], $search['limit'], $objectlistcols, true);

        // search for file name in objectpath if the previous query has less than the max size of the result
        if ($maxresultsize < 1) $maxresultsize = 10;

        if ((empty ($result) || sizeof ($result) < $maxresultsize) && $search['filename'] == "*Null*" && !empty ($search_filename) && $search_filename != "*Null*")
        {
          // reset
          $search['expression_array'] = "";
          $search['filename'] = $search_filename;

          $result_add = rdbms_searchcontent ($search_dir_esc, $exclude_dir_esc, $search['format'], $search['date_modified_from'], $search['date_modified_to'], "", $search['expression_array'], $search['filename'], $search['fileextension'], "", $search['imagewidth'], $search['imageheight'], $search['imagecolor'], $search['imagetype'], $search['geo_border_sw'], $search['geo_border_ne'], $search['limit'], $objectlistcols, true);

          // merge results
          if (is_array ($result_add) && sizeof ($result_add) > 1)
          {
            if (is_array ($result))
            {
              // total count
              if (!empty ($result['count']) && !empty ($result_add['count'])) $count = $result['count'] + $result_add['count'];
              else $count = 0;

              $result = array_merge ($result, $result_add);

              if (!empty ($count)) $result['count'] = $count;
            }
            else $result = $result_add;
          }
        }
      }
    }

    // verify result
    if (is_array ($result))
    {
      foreach ($result as $hash=>$temp_array)
      {
        if (!empty ($hash) && !empty ($temp_array['objectpath']))
        {
          // favorites
          if (substr ($temp_array['objectpath'], 0, strpos ($temp_array['objectpath'], "/")) == "%favorites%")
          {
            $result[$hash]['permission'] = array('root'=>1);
          }
          // publications, folders and objects
          else
          {
            // get object information
            if (empty ($result[$hash]['location'])) $result[$hash] = rdbms_getobject_info ($hash, $objectlistcols);

            // get publication
            $temp_site = getpublication ($temp_array['objectpath']);
            // get category
            $temp_cat = getcategory ($temp_site, $temp_array['objectpath']); 
            // get location
            $temp_location_esc = getlocation ($temp_array['objectpath']);
            // get location in file system
            $temp_location = deconvertpath ($temp_location_esc, "file");               
            // get object name
            $temp_object = getobject ($temp_array['objectpath']);  
            $temp_object = correctfile ($temp_location, $temp_object, $user);

            // recheck access permission
            $temp_ownergroup = accesspermission ($temp_site, $temp_location, $temp_cat);
            $setlocalpermission = setlocalpermission ($temp_site, $temp_ownergroup, $temp_cat);

            // checked-out object information
            if (!empty ($result[$hash]['container_id']))
            {
              $container_id = $result[$hash]['container_id'];
              $temp_locked = getlockedfileinfo (getcontentlocation ($container_id, 'abs_path_content'), $container_id.".xml.wrk");

              if (isset ($temp_locked['user'])) $result[$hash]['usedby'] = $temp_locked['user'];
              else $result[$hash]['usedby'] = "";
            }

            // remove .folder
            if (substr ($temp_array['objectpath'], -7) == ".folder") $result[$hash]['objectpath'] = substr ($temp_array['objectpath'], 0, -7);

            // transform to readable path
            if ($readable_objectpath == true) $result[$hash]['objectpath'] = "%".$temp_cat ."%".getlocationname ($temp_site, $result[$hash]['objectpath'], $temp_cat, "path");

            // provide dates as UNIX timestanp
            if (!empty ($result[$hash]['createdate']))
            {
              $timestamp = strtotime ($result[$hash]['createdate']);
              $result[$hash]['createdate_unix'] = $timestamp;

              // convert to UTC
              date_default_timezone_set ("UTC");
              $result[$hash]['createdate_utc'] = date ("Y-m-d H:i:s", $timestamp);
            }

            if (!empty ($result[$hash]['date']))
            {
              $timestamp = strtotime ($result[$hash]['date']);
              $result[$hash]['date_unix'] = $timestamp;

              // convert to UTC
              date_default_timezone_set ("UTC");
              $result[$hash]['date_utc'] = date ("Y-m-d H:i:s", $timestamp);
            }

            // add permissions for valid result row
            if ((!empty ($setlocalpermission['root']) || $return_all_levels == true || $return_site_access == true) && is_file ($temp_location.$temp_object))
            {
              // add users local permissions
              $result[$hash]['permission'] = $setlocalpermission;
            }
            // remove invalid result row
            else
            {
              unset ($result[$hash]);
            }
          }
        }
      }
    }
  }

  // return result
  if (is_array ($result) && sizeof ($result) > 0) return $result;
  else return false;
}


// ---------------------------------------- getobjectpathlevel ----------------------------------------
// function: getobjectpathlevel()
// input: converted objectpath [string]
// output: level number / 0 on error

// description:
// Get the level number of an objectpath

function getobjectpathlevel ($objectpath)
{
  if (is_string ($objectpath) && $objectpath != "" && (substr_count ($objectpath, "*page*") == 1 || substr_count ($objectpath, "*comp*") == 1 || substr_count ($objectpath, "%page%") == 1 || substr_count ($objectpath, "%comp%") == 1))
  {
    $objectpath = trim ($objectpath);
    $objectpath = trim ($objectpath, "/");

    if (substr ($objectpath, -8) == "/.folder") $objectpath = substr ($objectpath, 0, -8);

    $level = substr_count ($objectpath, "/");

    if ($level > 0) return $level;
  }

  return 0;
}

// ---------------------------------------- getobjectpathname ----------------------------------------
// function: getobjectpathname()
// input: converted objectpath [string]
// output: location name / empty string on error

// description:
// Get the object name of an objectpath

function getobjectpathname ($objectpath)
{
  if (is_string ($objectpath) && $objectpath != "" && (substr_count ($objectpath, "*page*") == 1 || substr_count ($objectpath, "*comp*") == 1 || substr_count ($objectpath, "%page%") == 1 || substr_count ($objectpath, "%comp%") == 1))
  {
    $objectpath = trim ($objectpath);

    // correct object oath
    $objectpath = str_replace (array("*page*/", "*comp*/"), array("%page%/", "%comp%/"), $objectpath);

    $site = getpublication ($objectpath);
    $cat = getcategory ($site, $objectpath);
    $result = getlocationname ($site, $objectpath, $cat, "path");

    // replace characters by free space in order to have a word delimiter for the full-text search index
    $result = str_replace (array("_"), array(" "), $result);

    if ($result != "") return $result;
  }

  return "";
}

 // ========================================= LOAD CONTENT ============================================

// ---------------------------------------- getobjectcontainer ----------------------------------------
// function: getobjectcontainer()
// input: publication name [string], location [string], object name [string], user name [string], container type [work,published] (optional)
// output: Content Container [XML]/false
// requires: config.inc.php

// description:
// Loads the content container of a given object (page, component, folder).

function getobjectcontainer ($site, $location, $object, $user, $type="work")
{
  global $mgmt_config;
  
  // deconvert location
  if (is_string ($location) && (substr_count ($location, "%page%") > 0 || substr_count ($location, "%comp%") > 0))
  {
    $cat = getcategory ($site, $location);
    $location = deconvertpath ($location, "file");
  }

  // if object includes special characters
  if (specialchr ($object, ".-_~") == true)
  {
    $object = specialchr_encode ($object, false);
  }

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($object) && valid_objectname ($user) && ($type == "work" || $type == "published"))
  {
    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // evaluate if object is a file or a folder
    if (is_dir ($location.$object))
    {
      $location = $location.$object."/";
      $object = ".folder";
    }
    else
    { 
      $object = correctfile ($location, $object, $user); 
    }

    // load object file
    $data = loadfile ($location, $object);

    if ($data != "") $container = getfilename ($data, "content");
    else $container = false;

    // load container
    if ($container != false)
    {
      $container_id = substr ($container, 0, strpos ($container, ".xml"));

      $data = loadcontainer ($container, $type, $user);

      if (!empty ($data)) return $data;
    }
  }
  
  return false;
}

// ------------------------------------------ getcontainer --------------------------------------------
// function: getcontainer()
// input: container name or container ID [string], container type [published,work]
// output: Contant Container [XML]/false
// requires: config.inc.php

// description:
// Obsolete function used as an alias for the loadcontainer function without the possibility to load locked containers

function getcontainer ($containerid, $type)
{
  global $mgmt_config;

  return loadcontainer ($containerid, $type, "");
}

// ------------------------------------------ getwallpaper --------------------------------------------
// function: getwallpaper()
// input: design theme name [string] (optional), version number for the wallpaper service [string] (optional)
// output: URL of wallpaper image / false
// requires: config.inc.php

// description:
// Provides a wallpaper image or video used for the logon and home screen.

function getwallpaper ($theme="", $version="")
{
  global $mgmt_config, $is_mobile;

  // initialize
  $error = array();
  
  // 1. get system wallpaper
  if (!empty ($mgmt_config['wallpaper']))
  {
    return $mgmt_config['wallpaper'];
  }

  // get theme defined in main config
  if (empty ($theme) && !empty ($mgmt_config['theme'])) $theme = $mgmt_config['theme'];

  // get theme from session
  if (empty ($theme) && getsession ("hcms_themename") != "") $theme = getsession ("hcms_themename");

  // 2. wallpaper from design theme
  if (valid_locationname ($theme))
  {
    // get theme location (portal themes, system themes)
    if (strpos ($theme, "/") > 0 && is_dir ($mgmt_config['abs_path_rep']."portal/".$theme))
    {
      $root_abs = $mgmt_config['abs_path_rep']."portal/";
      $root_url = $mgmt_config['url_path_rep']."portal/";
    }
    else
    {
      $root_abs = $mgmt_config['abs_path_cms']."theme/";
      $root_url = $mgmt_config['url_path_cms']."theme/";
    }

    // get theme wallpaper
    if (is_file ($root_abs.$theme."/img/wallpaper.jpg")) $wallpaper = cleandomain ($root_url.$theme."/img/wallpaper.jpg");
    elseif (is_file ($root_abs.$theme."/img/wallpaper.png")) $wallpaper = cleandomain ($root_url.$theme."/img/wallpaper.png");

    if (!empty ($wallpaper)) return $wallpaper;
  }

  // 3. wallpaper service (not for mobile edition)
  if (empty ($is_mobile))
  {
    // get version
    if (empty ($version) && is_file ($mgmt_config['abs_path_cms']."version.inc.php"))
    {
      // version info
      include ($mgmt_config['abs_path_cms']."version.inc.php");
      if (!empty ($mgmt_config['version'])) $version = $mgmt_config['version'];
    }

    // get wallpaper name
    $wallpaper_name = HTTP_Get_contents ("https://cloud.hypercms.net/wallpaper/?action=name&version=".urlencode($version));

    if (!empty ($wallpaper_name) && strlen ($wallpaper_name) < 100)
    {
      // if file does not exist in temp view directory
      if (!is_file ($mgmt_config['abs_path_view'].$wallpaper_name))
      {
        // get wallpaper file
        $wallpaper_file = HTTP_Get_contents ("https://cloud.hypercms.net/wallpaper/?action=get&name=".urlencode($wallpaper_name));

        if (!empty ($wallpaper_file))
        {
          if (savefile ($mgmt_config['abs_path_view'], $wallpaper_name, $wallpaper_file)) return $mgmt_config['url_path_view'].$wallpaper_name;
        }
      }
      else return $mgmt_config['url_path_view'].$wallpaper_name;
    }
    // no connection to wallpaper service
    else
    { 
      $errcode = "00820";
      $error[] = $mgmt_config['today']."|hypercms_get.inc.php|warning|".$errcode."|Wallpaper service not available";

      savelog ($error);

      // define wallpaper (based on day number)
      $day = date ("z") + 1;

      if (is_file ($mgmt_config['abs_path_view'].$day.".jpg")) $file_name = $day.".jpg";
      elseif (is_file ($mgmt_config['abs_path_view'].$day.".mp4")) $file_name = $day.".mp4";

      // provide wallpaper name
      if (!empty ($file_name)) return $mgmt_config['url_path_view'].$file_name;
    }  
  }

  // 4. default wallpaper
  return getthemelocation($theme)."img/backgrd_start.jpg";
}
 
// ======================================== GET INFORMATION ===========================================

// --------------------------------------- getcontainername -------------------------------------------
// function: getcontainername()
// input: container name (e.g. 0000112.xml.wrk) or container ID [string]
// output: Array with file name of the working content container (locked or unlocked!) and username if locked
// requires: config.inc.php to be loaded

function getcontainername ($container)
{
  global $mgmt_config;

  // initialize
  $result = array();
  $result['result'] = false;

  // correct user name for file lock
  if (!empty ($_SESSION['hcms_user'])) $lock = createlockname ($_SESSION['hcms_user']);

  if (valid_objectname ($container))
  {
    // define container ID and container name
    if (strpos ($container, ".xml") > 0)
    {
      $container_id = getcontentcontainerid ($container);
    }
    else
    {
      $container_id = $container;
      // add zeros
      $container_id = sprintf ("%07d", $container_id);
      $container = $container_id.".xml";
    }

    if (strpos ($container, ".wrk") > 0)
    {
      // cut off version or user extension
      $container = substr ($container, 0, strpos ($container, ".wrk"));
      $containerwrk = $container.".wrk";
    }
    else $containerwrk = $container.".wrk";

    // get container location
    $location = getcontentlocation ($container_id, 'abs_path_content');

    // container exists and is not locked
    if (is_file ($location.$containerwrk))
    {
      $result['result'] = true;
      $result['container'] = $containerwrk;
      $result['user'] = "";

      return $result;
    }
    // container exists and is locked by current user
    elseif (!empty ($lock) && is_file ($location.$containerwrk.".@".$lock))
    {
      $result['result'] = true;
      $result['container'] = $containerwrk.".@".$lock;
      $result['user'] = $_SESSION['hcms_user'];

      return $result;
    }
    // container is locked or does not exist
    elseif (is_dir ($location))
    {
      $scandir = scandir ($location);

      if ($scandir)
      {
        foreach ($scandir as $entry)
        {
          // if locked working container was found
          if (preg_match ("/$container.wrk.@/", $entry))
          { 
            $containerwrk = $entry;
            $user = substr ($entry, strpos ($entry, "wrk.@") + 5);

            $result['result'] = true;
            $result['container'] = $containerwrk;
            $result['user'] = $user;

            return $result;
          } 
        }
      }
    }
  }

  return $result;
}

// ------------------------------------- getlocationname ------------------------------------------

// function: getlocationname()
// input: publication name [string], location path (as absolute path or converted path) [string], category [page,comp] (optional), source for name [path,name]
// output: location with readable names instead of directory and file names / false on error

// description:
// This functions create a readable path for the display in the user interface. The created path should not be used as input for any other API functions.

function getlocationname ($site, $location, $cat="", $source="path")
{
  global $mgmt_config, $siteaccess, $lang, $hcms_lang_codepage;

  if (valid_locationname ($location))
  {
    // use publication name as fallback
    if (empty ($siteaccess)) $siteaccess = array($site => $site);

    // publication management config
    if (valid_publicationname ($site) && !isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }

    // check for .folder and remove it
    if (getobject ($location) == ".folder") $location = getlocation ($location);

    // input is converted location
    if ((substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1) && isset ($mgmt_config[$site]) && is_array ($mgmt_config[$site]))
    {
      if ($site == "") $site = getpublication ($location);
      if ($cat == "") $cat = getcategory ($site, $location);

      $location_esc = $location;
      $location_abs = deconvertpath ($location, "file");
    }
    // input is not a converted location and publication name is valid
    elseif (valid_publicationname ($site) && isset ($mgmt_config[$site]) && is_array ($mgmt_config[$site]))
    {
      if ($cat == "") $cat = getcategory ($site, $location);

      $location_esc = convertpath ($site, $location, $cat);
      $location_abs = $location;
    }

    if (valid_publicationname ($site) && !empty ($location_esc) && !empty ($location_abs))
    {
      // get names from name file pointer
      if ($source == "name")
      {
        if ($cat == "page") $root_abs = $mgmt_config[$site]['abs_path_page'];
        elseif ($cat == "comp") $root_abs = $mgmt_config['abs_path_comp'];

        // loop while operating in home folder
        $location_folder = $location_abs;
        $location_name = "";

        while (substr_count ($root_abs, $location_folder) < 1)
        {
          // read file and define folder name
          $objectdata = loadfile ($location_folder, ".folder");
          if ($objectdata != false) $foldername = getfilename ($objectdata, "name");
          if ($foldername == false || $foldername == "") $foldername = getobject ($location_folder); 
          // define location name
          $location_name = $foldername."/".$location_name;
          // reset name
          $foldername = "";
          // get parent location 
          $location_folder = getlocation ($location_folder);
        }

        if ($cat == "page") $location_name = "/".$siteaccess[$site]."/".$location_name;
        elseif ($cat == "comp") $location_name = "/".$siteaccess[$site]."/".substr ($location_name, strpos ($location_name, "/"));
      }
      // get names from decoding the file path
      else
      {
        if ($cat == "page") $root_abs = "%page%/".$site."/";
        elseif ($cat == "comp") $root_abs = "%comp%/".$site."/";
        else $root_abs = "";

        // replace
        if ($root_abs != "") $location_name = str_replace ($root_abs, "/".$siteaccess[$site]."/", $location_esc);
        
        // remove root variables in case the root element without a publication name has been provided as location
        $location_name = str_replace (array("%comp%","%page%"), "", $location_esc);

        // correct/remove a single slash if the root element has been provided as lcoation
        if ($location_name == "/") $location_name = "";

        $location_name = specialchr_decode ($location_name);
      }

      if ($location_name != "") return $location_name;
    }
  }

  return false;
}

// --------------------------------------- getthemes -------------------------------------------
// function: getthemes ()
// input: publication name as string or array [string,array] (optional)
// output: all design theme names as array / false

// description:
// Returns all design theme names as values and the technical names (path) as key of the result array.

function getthemes ($site_array=array())
{
  global $mgmt_config, $siteaccess;

  $theme_array = array();

  // system themes
  $theme_dir = $mgmt_config['abs_path_cms']."theme/";

  if (is_dir ($theme_dir))
  {
    $scandir = scandir ($theme_dir);

    if ($scandir)
    {
      foreach ($scandir as $entry)
      {
        if (strtolower($entry) != "mobile" && $entry != "." && $entry != ".." && is_dir ($theme_dir.$entry) && is_dir ($theme_dir.$entry."/img") && is_dir ($theme_dir.$entry."/css"))
        { 
          $theme_array[$entry] = ucfirst ($entry);
        }
      }
    }

    // portal design themes of the publication
    if (!is_array ($site_array) && valid_publicationname ($site_array))
    {
      $site_array = array($site_array);
    }

    if (is_array ($site_array) && sizeof ($site_array) > 0)
    {
      foreach ($site_array as $site)
      {
        if (valid_publicationname ($site) && is_dir ($mgmt_config['abs_path_rep']."portal/".$site."/"))
        {
          $theme_dir = $mgmt_config['abs_path_rep']."portal/".$site."/";

          if (is_dir ($theme_dir))
          {
            $scandir = scandir ($theme_dir);

            if ($scandir)
            {
              foreach ($scandir as $entry)
              {
                if ($entry != "." && $entry != ".." && is_dir ($theme_dir.$entry) && is_dir ($theme_dir.$entry."/img") && is_dir ($theme_dir.$entry."/css"))
                {
                  if (!empty ($siteaccess[$site])) $sitename = $siteaccess[$site];
                  else $sitename = $site;

                  $theme_array[$site."/".$entry] = $sitename." &gt; ".$entry;
                }
              }
            }
          }
        }
      }
    }

    // prepare output
    if (is_array ($theme_array) && sizeof ($theme_array) > 0)
    {
      natcasesort ($theme_array);
      reset ($theme_array);
      return $theme_array;
    }
  }
  
  return false;
}

// --------------------------------------- getthemelocation -------------------------------------------
// function: getthemelocation ()
// input: theme name [string] (optional), location type [path,url] (optional)
// output: path to theme / false

// description:
// Returns the absolute path (URL) of the theme (css and images).
// If the main configuration setting $mgmt_config['theme'] defines a theme, this theme will be mandatory in case it exists.

function getthemelocation ($theme="", $type="url")
{
  global $mgmt_config;

  // get theme from session if no input is available
  if (trim ($theme) == "" && !empty ($_SESSION['hcms_themename']) && valid_locationname ($_SESSION['hcms_themename']))
  {
    $theme = $_SESSION['hcms_themename'];
  } 
  // mandatory theme defined in the main configuration
  elseif (trim ($theme) == "" && !empty ($mgmt_config['theme']) && valid_locationname ($mgmt_config['theme']))
  {
    $theme = $mgmt_config['theme'];
  }

  // get theme location for portal themes or system themes
  if (valid_locationname ($theme) && strpos ($theme, "/") > 0 && is_dir ($mgmt_config['abs_path_rep']."portal/".$theme."/css"))
  {
    $root_abs = $mgmt_config['abs_path_rep']."portal/";
    $root_url = $mgmt_config['url_path_rep']."portal/";
  }
  else
  {
    $root_abs = $mgmt_config['abs_path_cms']."theme/";
    $root_url = $mgmt_config['url_path_cms']."theme/";
  }

  // theme defined by session or input parameter
  if (valid_locationname ($theme) && is_dir ($root_abs.$theme."/css"))
  {
    if ($type == "path") return $root_abs.$theme."/";
    else return cleandomain ($root_url.$theme."/");
  }
  // default theme
  else
  {
    if ($type == "path") return $mgmt_config['abs_path_cms']."theme/standard/";
    else return cleandomain ($mgmt_config['url_path_cms']."theme/standard/");
  }
}

// ---------------------- getcategory -----------------------------
// function: getcategory()
// input: publication name [string] (optional), location path [string]
// output: category ['page, comp'] / false on error
// requires: config.inc.php

// description:
// Evaluates the category ['page, comp'] of a location

function getcategory ($site, $location)
{
  global $mgmt_config, $publ_config;

  if ($location != "" && is_array ($mgmt_config))
  {
    // define category
    if (@substr_count ($location, "%page%") >= 1)
    {
      $cat = "page";
    }
    elseif (@substr_count ($location, "%comp%") >= 1)
    {
      $cat = "comp";
    }
    elseif (@substr_count ($location, "://") == 0 && valid_publicationname ($site))
    {
      if (!empty ($mgmt_config['abs_path_comp']) && !empty ($mgmt_config[$site]['abs_path_page']) && strlen ($mgmt_config['abs_path_comp']) > strlen ($mgmt_config[$site]['abs_path_page']))
      {
        if (!empty ($mgmt_config['abs_path_comp']) && substr_count ($location, $mgmt_config['abs_path_comp']) == 1) $cat = "comp";
        elseif (!empty ($mgmt_config[$site]['abs_path_page']) && substr_count ($location, $mgmt_config[$site]['abs_path_page']) == 1) $cat = "page";
      }
      else
      {
        if (!empty ($mgmt_config[$site]['abs_path_page']) && substr_count ($location, $mgmt_config[$site]['abs_path_page']) == 1) $cat = "page";
        elseif (!empty ($mgmt_config['abs_path_comp']) && substr_count ($location, $mgmt_config['abs_path_comp']) == 1) $cat = "comp";
      }
    }
    elseif (@substr_count ($location, "://") > 0 && valid_publicationname ($site))
    {
      if (!empty ($mgmt_config['abs_path_comp']) && !empty ($mgmt_config[$site]['abs_path_page']) && strlen ($mgmt_config['url_path_comp']) > strlen ($mgmt_config[$site]['url_path_page']))
      {
        if (!empty ($mgmt_config['url_path_comp']) && substr_count ($location, $mgmt_config['url_path_comp']) == 1) $cat = "comp";
        elseif (!empty ($mgmt_config[$site]['url_path_page']) && substr_count ($location, $mgmt_config[$site]['url_path_page']) == 1) $cat = "page";
      }
      else
      {
        if (!empty ($mgmt_config[$site]['url_path_page']) && substr_count ($location, $mgmt_config[$site]['url_path_page']) == 1) $cat = "page";
        elseif (!empty ($mgmt_config['url_path_comp']) && substr_count ($location, $mgmt_config['url_path_comp']) == 1) $cat = "comp";
      }

      if (!isset ($cat))
      {
        if (!is_array ($publ_config) && valid_publicationname ($site) && is_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"))
        {
          // load ini
          $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini");
        }

        if (!empty ($url_publ_comp) && !empty ($mgmt_config[$site]['url_publ_page']) && strlen ($url_publ_comp) > strlen ($publ_config['url_publ_page']))
        {
          if (!empty ($publ_config['url_publ_comp']) && substr_count ($location, $publ_config['url_publ_comp']) == 1) $cat = "comp";
          elseif (!empty ($publ_config['url_publ_page']) && substr_count ($location, $publ_config['url_publ_page']) == 1) $cat = "page";
        }
        else
        {
          if (!empty ($publ_config['url_publ_page']) && substr_count ($location, $publ_config['url_publ_page']) == 1) $cat = "page";
          elseif (!empty ($publ_config['url_publ_comp']) && substr_count ($location, $publ_config['url_publ_comp']) == 1) $cat = "comp";
        }
      } 
    }
  }

  if (!empty ($cat) && ($cat == "page" || $cat == "comp")) return $cat;
  else return false;
}

// ---------------------- getpublication -----------------------------
// function: getpublication()
// input: converted location path [string]
// output: publication name

// description:
// Extract the publication name of a location path

function getpublication ($path)
{
  if (trim ($path) != "" && is_string ($path))
  {
    // initialize
    $site = false;

    // extract publication from the converted path (first found path entry only!)
    if (substr_count ($path, "%page%") > 0) $root_var = "%page%/";
    elseif (substr_count ($path, "%comp%") > 0) $root_var = "%comp%/";
    elseif (substr_count ($path, "%media%") > 0) $root_var = "%media%/";
    else $root_var = false;

    if (!empty ($root_var) && strpos ("_".$path, $root_var) > 0)
    {
      $pos1 = strpos ($path, $root_var) + strlen ($root_var);

      if ($pos1 > 0) $pos2 = strpos ($path, "/", $pos1);
      else $pos2 = false;

      if ($pos1 > 0 && $pos2 > 0) $site = substr ($path, $pos1, $pos2-$pos1);
      else $site = false;
    }
    // extract publication from the absolute path (requires media file name with hcm-ID)
    elseif (getmediacontainerid (getobject ($path)))
    {
      $location = getlocation ($path);
      $site = basename ($location);
    }

    return $site;
  }
  
  return false;
}

// ------------------------- getlocation ---------------------------------
// function: getlocation()
// input: location path [string]
// output: location (without object or folder)

// description:
// Extract the location excluding object or folder of a location path

function getlocation ($path)
{
  if (trim ($path) != "" && is_string ($path))
  {
    $path = trim ($path);

    // if object has no slash at the end
    if (substr ($path, -1) != "/")
    {
      $location = substr ($path, 0, strrpos ($path, "/") + 1);
    }
    // else remove slash
    else
    {
      // remove slash at the end of the objectpath string
      $location = substr ($path, 0, -1);
      $location = substr ($location, 0, strrpos ($location, "/") + 1);
    }

    return $location;
  }
  
  return false;
}

// ------------------------- getobject ---------------------------------
// function: getobject()
// input: location path [string]
// output: object or folder name

// description:
// Extract the object or folder of a location path

function getobject ($path)
{
  if (trim ($path) != "" && is_string ($path))
  {
    $path = trim ($path);

    // if given input is a path
    if (substr_count ($path, "/") > 0)
    {
      // if path has no slash at the end
      if (substr ($path, -1) != "/")
      {
        $object = substr ($path, strrpos ($path, "/") + 1);
      }
      // else remove slash
      else
      {
        // remove slash at the end of the objectpath string
        $path = substr ($path, 0, -1);
        $object = substr ($path, strrpos ($path, "/") + 1);
      }

      // if path holds GET parameters (URL)
      if (strpos ($object, "?") > 0)
      {
        $object = substr ($object, 0, strrpos ($object, "?"));
      }
    }
    else $object = $path;

    return $object;
  }
  
  return false;
}

// ---------------------- getmediacontainername -----------------------------
// function: getmediacontainername()
// input: file name [string]
// output: container name / false on error

// description:
// Extract the container name from a multimedia file name by using the hcm-ID

function getmediacontainername ($file)
{
  if (valid_objectname ($file))
  {
    $startpos = strrpos ($file, "_hcm") + 4;

    if (strpos ($file, ".", $startpos) > 0) $endpos = strpos ($file, ".", $startpos);
    else $endpos = strlen ($file);

    $length = $endpos - $startpos;
    $id = substr ($file, $startpos, $length);

    if (is_int (intval ($id))) return $id.".xml";
  }
  
  return false;
}

// ---------------------- getmediacontainerid -----------------------------
// function: getmediacontainernid()
// input: media file name [string]
// output: container ID / false on error

// description:
// Extract the container ID from a multimedia file name using the hcms-ID-

function getmediacontainerid ($file)
{
  if (valid_objectname ($file) && strpos ("_".$file, "_hcm") > 0)
  {
    $startpos = strrpos ($file, "_hcm") + 4;

    if (strpos ($file, ".", $startpos) > 0) $endpos = strpos ($file, ".", $startpos);
    else $endpos = strlen ($file);

    $length = $endpos - $startpos;
    $id = substr ($file, $startpos, $length);

    if (intval ($id) > 0) return $id;
  }
  
  return false;
}

// ---------------------- getcontentcontainerid -----------------------------
// function: getcontentcontainerid()
// input: container file name [string]
// output: container ID / false on error

// description:
// Extract the container ID from a container file name.

function getcontentcontainerid ($file)
{
  if (valid_objectname ($file) && strpos ($file, ".xml") > 0)
  {
    $id = substr ($file, 0, strpos ($file, ".xml"));
  }
  elseif (intval ($file) > 0)
  {
    $id = $file;
  }

  if (!empty ($id) && intval ($id) > 0) return $id;
  else return false;
}

// ---------------------- getmediafileversion -----------------------------
// function: getmediafileversion()
// input: container name or container ID [string]
// output: media file name / false on error

// description:
// Extracts the name from the multimedia file by container name or ID in order to get the media file of older content versions.
// if the result is false, there is no older media file version.

function getmediafileversion ($container)
{
  global $mgmt_config, $user;

  if (valid_objectname ($container))
  {
    // if container with media file version (media file name = container version name)
    if (strpos ("_".$container, "_hcm") > 0)
    {
      $container_id = getmediacontainerid ($container);

      if (@preg_match ("/_hcm".$container_id."/i", $container))
      {
        return $mediafile = $container;
      }
    }
    // if container name
    elseif (strpos ($container, ".xml") > 0)
    {
      $container_id = getcontentcontainerid ($container);
    }
    // if container ID
    else $container_id = $container;

    if ($container_id > 0)
    {
      // get container file extension
      if (strpos ($container, ".") > 0) $ext = substr ($container, strrpos ($container, "."));

      // if container version (object files might not exist anymore)
      if (!empty ($ext) && strpos ("_".$ext, ".v_") > 0)
      {
        // time stamp of supplied version (YYYYMMDDHHMMSS)
        $reference_timestamp = str_replace (array(".v_", "-", "_"), array("", "", ""), $ext);

        $version_dir = getcontentlocation ($container_id, 'abs_path_content');

        // select all content version files in directory
        if (is_dir ($version_dir))
        {
          $scandir = scandir ($version_dir);

          $version_container = array();

          if ($scandir)
          {
            foreach ($scandir as $entry)
            {
              // only select versions when media file has been changed
              if ($entry != "." && $entry != ".." && is_file ($version_dir.$entry) && preg_match ("/_hcm".$container_id."./i", $entry))
              {
                // get file extension of container version
                $version_ext = substr ($entry, strrpos ($entry, "."));

                // time stamp of version (YYYYMMDDHHMMSS)
                $version_timestamp = str_replace (array(".v_", "-", "_"), array("", "", ""), $version_ext);

                $version_container[$version_timestamp] = $entry;
              }
            }
          }

          // get media file
          if (sizeof ($version_container) > 0)
          {
            ksort ($version_container);
            $version_container = array_reverse ($version_container, true);
            reset ($version_container);

            $temp_mediafile = "";

            foreach ($version_container as $version_timestamp => $version_mediafile)
            {
              if ($reference_timestamp >= $version_timestamp) break;

              $mediafile = $version_mediafile;
            }
          }

          if (!empty ($mediafile)) return $mediafile;
        }
      }
    }
  }
  
  return false;
}

// ---------------------- getobjectid -----------------------------
// function: getobjectid()
// input: converted object path or pathes separated by | [string]
// output: object ID

// description:
// Converts the object path to the object ID of any object

function getobjectid ($objectlink)
{
  if (is_string ($objectlink) && $objectlink != "")
  {
    $objectlink_conv = "";

    // if multiple component
    if (strpos ("_".$objectlink, "|") > 0)
    {
      $objectpath_array = explode ("|", $objectlink);

      if (!empty ($objectpath_array) && sizeof ($objectpath_array) > 0)
      {
        foreach ($objectpath_array as $objectpath)
        {
          if ($objectpath != "")
          {
            // if object path is a converted path
            if (strpos ("_".$objectpath, "%page%") > 0 || strpos ("_".$objectpath, "%comp%") > 0)
            {
              $objectinfo = getobjectinfo (getpublication ($objectpath), getlocation ($objectpath), getobject ($objectpath));

              // if object is a multimedia object
              if (!empty ($objectinfo['media']))
              {
                $objectid = rdbms_getobject_id ($objectpath);

                // object ID exists
                if ($objectid > 0) $objectlink_conv .= $objectid."|";
                // no object ID -> use object path
                else $objectlink_conv .= $objectpath."|";
              }
              // if object is a component object
              else $objectlink_conv .= $objectpath."|";
            }
            // if object is a URL
            else $objectlink_conv .= $objectpath."|";
          }
        }
      }
    }
    // if single component
    else
    {
      // if object path is a converted path
      if (strpos ("_".$objectlink, "%page%") > 0 || strpos ("_".$objectlink, "%comp%") > 0)
      {
        $objectinfo = getobjectinfo (getpublication ($objectlink), getlocation ($objectlink), getobject ($objectlink));

        // if object is a multimedia object
        if (!empty ($objectinfo['media']))
        {
          $objectid = rdbms_getobject_id ($objectlink);

          // object ID exists
          if ($objectid > 0) $objectlink_conv = $objectid;
          // no object ID -> use object path
          else $objectlink_conv = $objectlink;
        }
        // if object is a component object
        else $objectlink_conv = $objectlink;
      }
      // if object is a URL
      else $objectlink_conv = $objectlink;
    }

    // return converted result 
    return trim ($objectlink_conv, "|");
  }
  else return $objectlink;
}

// ------------------------------------- getobjectpath ------------------------------------------

// function: getobjectpath()
// input: object identifier (object hash OR object ID OR access hash) [string]
// output: object path / false

// description:
// Returns the location path of an object as string. This function is an alias for function rdbms_getobject in DB Connect.

function getobjectpath ($object_identifier)
{
  global $mgmt_config;

  return rdbms_getobject ($object_identifier);
}

// ---------------------- getobjectlink -----------------------------
// function: getobjectlink()
// input: converted object ID or IDs separated by | [string]
// output: converted object link

// description:
// Converts the object ID to the object path of any object

function getobjectlink ($objectid)
{
  if ($objectid != "")
  {
    $objectid_conv = "";

    // if multiple component
    if (strpos ("_".$objectid, "|") > 0)
    {
      $object_id_array = explode ("|", $objectid);

      if (!empty ($object_id_array) && sizeof ($object_id_array) > 0)
      {
        foreach ($object_id_array as $object_id)
        {
          if ($object_id != "")
          {
            // if object ID (numeric)
            if (is_numeric ($object_id))
            {
              $objectpath = rdbms_getobject ($object_id);

              // object path exists
              // if no object path -> the object has been deleted
              if (!empty ($objectpath)) $objectid_conv .= $objectpath."|";
            }
            // if object path (string)
            else $objectid_conv .= $object_id."|";
          }
        }
      }
    }
    // if single component
    else
    {
      // if object ID (numeric)
      if (is_numeric ($objectid))
      {
        $objectpath = rdbms_getobject ($objectid);

        // object path exists
        // no object path -> the object has been deleted
        if (!empty ($objectpath)) $objectid_conv = $objectpath;
      }
      // if object path (string)
      else $objectid_conv = $objectid;
    }

    // return converted result 
    return trim ($objectid_conv, "|");
  }
  else return $objectid;
}

// ---------------------- getcontainerversions -----------------------------
// function: getcontainerversions()
// input: container ID or container name [string]
// output: array of all versions (array[version-extension] = file-name) / false

function getcontainerversions ($container)
{
  global $mgmt_config;

  if ($container != "")
  {
    $result = array();

    // get container ID
    if (strpos ($container, ".") > 0) $container_id = substr ($container, 0, strpos ($container, "."));
    else $container_id = $container;

    // get container location
    $versiondir = getcontentlocation ($container_id, 'abs_path_content');

    // select all content version files in directory
    if (is_dir ($versiondir))
    {
      $scandir = scandir ($versiondir);

      if ($scandir)
      {
        foreach ($scandir as $entry)
        {
          if ($entry != "." && $entry != ".." && is_file ($versiondir.$entry) && (preg_match ("/".$container_id.".xml.v_/i", $entry) || preg_match ("/_hcm".$container_id."./i", $entry)))
          {
            // extract date and time from file extension
            $file_v_ext = substr (strrchr ($entry, "."), 3);
            $date = substr ($file_v_ext, 0, strpos ($file_v_ext, "_"));
            $time = substr ($file_v_ext, strpos ($file_v_ext, "_") + 1);
            $time = str_replace ("-", ":", $time);
            $date_v = $date." ".$time;

            $result[$date_v] = $entry;
          }
        }
      }

      if (sizeof ($result) > 0)
      {
        ksort ($result);
        return $result;
      }
    }
  }
  
  return false;
}

// ---------------------- getlocaltemplates -----------------------------
// function: getlocaltemplates()
// input: publication name [string], template category [all,page,comp,meta,inc,portal] (optional)
// output: array with all template names / false

// description:
// This function returns a list of all templates of a publication without inherited templates from other publications.

function getlocaltemplates ($site, $cat="all")
{
  global $mgmt_config;

  if (valid_publicationname ($site) && is_dir ($mgmt_config['abs_path_template'].$site."/"))
  {
    $scandir = scandir ($mgmt_config['abs_path_template'].$site."/");

    $template_files = array();

    if ($scandir)
    {
      foreach ($scandir as $entry)
      {
        if ($entry != "." && $entry != ".." && !is_dir ($entry) && substr_count ($entry, ".tpl.v_") < 1 && substr_count ($entry, ".bak") < 1)
        {
          if ($cat == "page" && strpos ($entry, ".page.tpl") > 0)
          {
            $template_files[] = $entry;
          }
          elseif ($cat == "comp" && strpos ($entry, ".comp.tpl") > 0)
          {
            $template_files[] = $entry;
          }
          elseif ($cat == "meta" && strpos ($entry, ".meta.tpl") > 0)
          {
            $template_files[] = $entry;
          }
          elseif ($cat == "inc" && strpos ($entry, ".inc.tpl") > 0)
          {
            $template_files[] = $entry;
          }
          elseif ($cat == "portal" && strpos ($entry, ".portal.tpl") > 0)
          {
            $template_files[] = $entry;
          }
          elseif ($cat == "all" || $cat == "")
          {
            $template_files[] = $entry;
          }
        }
      }
    }

    if (sizeof ($template_files) > 0)
    {
      natcasesort ($template_files);
      reset ($template_files);
      return $template_files;
    }
  }
  
  return false;
}

// ----------------------------------------- gettemplates ---------------------------------------------
// function: gettemplates()
// input: publication name [string], object category [all,page,comp,meta] (optional)
// output: template file name list as array / false on error
// requires: config.inc.php to be loaded before

// description:
// This function returns a list of all templates for pages or components.
// Based on the inheritance settings of the publication the template will be loaded with highest priority from the own publication and if not available from a parent publication.
// Portal templates are not supoported by the template inheritance due to the fact that the portal access link permission is connected to a specific publication.

function gettemplates ($site, $cat="all")
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  if (valid_publicationname ($site) && ($cat == "all" || $cat == "page" || $cat == "comp" || $cat == "meta"))
  {
    $site_array = array();

    // load publication inheritance setting
    if (!empty ($mgmt_config[$site]['inherit_tpl']))
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
      // home box component template
      if (!empty ($mgmt_config['homeboxes_directory']) && $cat == "comp")
      {
        // remove trailing slashes
        $mgmt_config['homeboxes_directory'] = trim ($mgmt_config['homeboxes_directory'], "/");

        if (valid_locationname ($mgmt_config['homeboxes_directory']) && is_dir ($mgmt_config['abs_path_comp'].$site."/".$mgmt_config['homeboxes_directory']))
        {
          if (is_file ($mgmt_config['abs_path_cms']."/xmlschema/template_homebox.schema.xml.php")) $template_array[] = "System-HomeBox-Standard.comp.tpl";
          if (is_file ($mgmt_config['abs_path_cms']."/xmlschema/template_homeboxgallery.schema.xml.php")) $template_array[] = "System-HomeBox-Gallery.comp.tpl";
        }
      }

      // brand guidelines component templates
      if (!empty ($mgmt_config[$site]['dam']) && $cat == "comp")
      {
        if (is_file ($mgmt_config['abs_path_cms']."/xmlschema/template_brandguidelines.schema.xml.php")) $template_array[] = "System-BrandGuidelines.comp.tpl";
        if (is_file ($mgmt_config['abs_path_cms']."/xmlschema/template_brandguidepage.schema.xml.php")) $template_array[] = "System-BrandGuidePage.comp.tpl";
        if (is_file ($mgmt_config['abs_path_cms']."/xmlschema/template_brandcolor.schema.xml.php")) $template_array[] = "System-BrandColor.comp.tpl";
        if (is_file ($mgmt_config['abs_path_cms']."/xmlschema/template_brandguidedownload.schema.xml.php")) $template_array[] = "System-BrandGuideDownload.comp.tpl";
      }

      // standard templates
      if (is_dir ($mgmt_config['abs_path_template'].$site_source."/"))
      {
        $scandir = scandir ($mgmt_config['abs_path_template'].$site_source."/");

        if ($scandir)
        {
          foreach ($scandir as $entry)
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
              elseif ($cat == "all" || $cat == "")
              {
                $template_array[] = $entry; 
              }
            }
          }
        }
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
  }
  
  return false;
}

// ---------------------- gettemplateversions -----------------------------
// function: gettemplateversions()
// input: publication name [string], template name [string]
// output: array of all versions (array['YYYY-MM-DD HH:MM:SS'] = file-name) / false

function gettemplateversions ($site, $template)
{
  global $mgmt_config;

  if (valid_publicationname ($site) && valid_objectname ($template))
  {
    $result = array();

    // get container location
    $versiondir = $mgmt_config['abs_path_template'].$site."/";

    // select all template version files in directory
    if (is_dir ($versiondir))
    {
      $scandir = scandir ($versiondir);

      if ($scandir)
      {
        foreach ($scandir as $entry)
        {
          if ($entry != "." && $entry != ".." && is_file ($versiondir.$entry) && preg_match ("/".$template.".v_/i", $entry))
          {
            // extract date and time from file extension
            $file_v_ext = substr (strrchr ($entry, "."), 3);
            $date = substr ($file_v_ext, 0, strpos ($file_v_ext, "_"));
            $time = substr ($file_v_ext, strpos ($file_v_ext, "_") + 1);
            $time = str_replace ("-", ":", $time);
            $date_v = $date." ".$time;

            $result[$date_v] = $entry;
          }
        }
      }

      if (sizeof ($result) > 0)
      {
        ksort ($result);
        return $result;
      }
    }
  }
  
  return false;
}

// ---------------------- getfileinfo -----------------------------
// function: getfileinfo()
// input: publication name [string] (optional), file name incl. extension [string], category [page,comp] (optional)
// output: result array / false on error

// description:
// Defines file properties based on the file extension and returns the file info as an array:
//    $result['file'] ... file name without hypercms management extension
//    $result['name'] ... readable file name without hypercms management extension
//    $result['filename'] ... file name without file extensions
//    $result['icon'] ... file name of the file icon
//    $result['type'] ... file type
//    $result['ext'] ... file extension incl. dot in lower case
//    $result['published'] ... if page or component is published (true) or not (false), true in all other cases
//    $result['deleted'] ... if file is deleted = true else = false

function getfileinfo ($site, $file, $cat="comp")
{
  global $mgmt_config;

  if ($file != "" && (valid_publicationname ($site) || ($cat == "page" || $cat == "comp")))
  {
    include ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

    // if file holds a path
    if (substr_count ($file, "/") > 0) $temp = getobject ($file);
    else $temp = $file;

    // set default (if no file extension is available)
    $file_name = $temp;
    $file_nameonly = $temp;
    $file_icon = "file_binary.png";
    $file_type = "unknown";
    $file_ext = "";
    $file_published = true;
    $file_deleted = false;

    // if file has an extension or holds a path
    if (substr_count ($file, ".") > 0 || substr_count ($file, "/") > 0)
    {
      // get the file extension of the file
      $file_ext_orig = strrchr ($file, ".");
      $file_ext = strtolower ($file_ext_orig);

      // CASE: folder 
      if ($file_ext == ".folder" || (substr_count ($file, "/") > 0 && is_dir (deconvertpath ($file, "file"))))
      {
        // get folder name from location (location required!) 
        if (substr_count ($file, "/") > 0)
        {
          // add / if missing
          if ($file_ext != ".folder" && substr ($file, -1) != "/") $file = $file."/";
          $location_name = substr ($file, 0, strrpos ($file, "/"));
          $folder_name = substr ($location_name, strrpos ($location_name, "/") + 1);
        }
        else $folder_name = $file;

        if ($cat == "") $cat = getcategory ($site, $file);

        // if deleted folder
        if (substr ($folder_name, -8) == ".recycle")
        {
          $folder_name = substr ($folder_name, 0, -8);
          $file_deleted = true;
        }
        else $file_deleted = false;

        $file_name = $folder_name;
        $file_nameonly = $folder_name;

        if ($cat == "page") $file_icon = "folder_page.png";
        elseif ($cat == "comp") $file_icon = "folder_comp.png";
        else $file_icon = "folder.png";

        $file_type = "Folder";
        $file_published = true;
      }
      // CASE: file with extension
      else
      {
        // if file holds a path
        if (substr_count ($file, "/") > 0) $file = getobject ($file);

        if ($file_ext != "")
        {
          // object versions
          if (substr_count ($file, ".") > 0 && substr ($file_ext, 0, 3) == ".v_")
          {
            $file_name = substr ($file, 0, strpos ($file, ".v_"));
            // get file name without extensions
            $file_nameonly = strrev (substr (strstr (strrev ($file_name), "."), 1));
            // get file extension of file name minus version extension
            $file_ext = strtolower (strrchr ($file_name, "."));

            $file_published = false;
            $file_deleted = false;
          }
          // mail files 
          elseif ($file_ext == ".mail")
          {
            $file_name = "Message";
            // get file name without extensions
            $file_nameonly = "Message";

            $file_published = false;
            $file_deleted = false;
          }
          // objects in recycle bin 
          elseif ($file_ext == ".recycle")
          {
            $file_name = substr ($file, 0, -8);
            // get file name without extensions
            $file_nameonly = strrev (substr (strstr (strrev ($file_name), "."), 1));
            // get file extension of file name minus .recycle
            $file_ext = strtolower (strrchr ($file_name, "."));

            $file_published = false;
            $file_deleted = true;
          }
          // unpublished objects 
          elseif ($file_ext == ".off")
          {
            $file_name = substr ($file, 0, -4);
            // get file name without extensions
            $file_nameonly = strrev (substr (strstr (strrev ($file_name), "."), 1));
            // get file extension of file name minus .off
            $file_ext = strtolower (strrchr ($file_name, "."));

            $file_published = false;
            $file_deleted = false;
          }
          // published objects
          else
          {
            $file_name = $file; 
            // get file name without extension
            $file_nameonly = strrev (substr (strstr (strrev ($file), "."), 1));

            $file_published = true;
            $file_deleted = false;
          }

          // System E-mail
          if ($file_ext == ".mail")
          {
            $file_icon = "button_user_sendlink.png";
            $file_type = "E-mail";
          }
          // Standard E-mail formats
          elseif ($file_ext == ".eml" || $file_ext == ".mbox" || $file_ext == ".msg")
          {
            $file_icon = "file_mail.png";
            $file_type = "E-Mail";
          }
          // Standard Calendar formats
          elseif ($file_ext == ".ical" || $file_ext == ".ics" || $file_ext == ".ifb" || $file_ext == ".icalendar")
          {
            $file_icon = "file_calendar.png";
            $file_type = "Calendar";
          }
          // MS Word
          elseif ($file_ext == ".doc" || $file_ext == ".docx" || $file_ext == ".docm" || $file_ext == ".dot" || $file_ext == ".dotm" || $file_ext == ".dotx")
          {
            $file_icon = "file_doc.png";
            $file_type = "MS Word";
          }
          // MS Powerpoint
          elseif ($file_ext == ".ppt" || $file_ext == ".pptm" || $file_ext == ".pptx" || $file_ext == ".pps" || $file_ext == ".ppsx" || $file_ext == ".pot" || $file_ext == ".potm" || $file_ext == ".potx"  || $file_ext == ".thmx")
          {
            $file_icon = "file_ppt.png";
            $file_type = "MS Powerpoint";
          }
          // MS Excel
          elseif ($file_ext == ".xls" || $file_ext == ".xlsb" || $file_ext == ".xlst" || $file_ext == ".xlsx" || $file_ext == ".xlt" || $file_ext == ".xltx" || $file_ext == ".xlsm" || $file_ext == ".xla" || $file_ext == ".xlam" || $file_ext == ".xltm" ||$file_ext == ".csv")
          {
            $file_icon = "file_xls.png";
            $file_type = "MS Excel";
          }
          // Adobe PDF
          elseif ($file_ext == ".pdf")
          {
            $file_icon = "file_pdf.png";
            $file_type = "Adobe Acrobat";
          }
          // Adobe Illustrator
          elseif ($file_ext == ".ai" || $file_ext == ".ait" || $file_ext == ".dmw")
          {
            $file_icon = "file_ai.png";
            $file_type = "Adobe Illustrator";
          }
          // Adobe InDesign and InCopy
          elseif ($file_ext == ".indd" || $file_ext == ".icml" || $file_ext == ".idml" || $file_ext == ".idms")
          {
            $file_icon = "file_indd.png";
            $file_type = "Adobe InDesign";
          }
          // Adobe Photoshop
          elseif ($file_ext == ".psd" || $file_ext == ".psb" || $file_ext == ".psdt" || $file_ext == ".xmp")
          {
            $file_icon = "file_psd.png";
            $file_type = "Adobe Photoshop";
          }
          // Adobe After Effects
          elseif ($file_ext == ".aep")
          {
            $file_icon = "file_aep.png";
            $file_type = "Adobe After Effects";
          }
          // Adobe Premiere
          elseif ($file_ext == ".prproj")
          {
            $file_icon = "file_prproj.png";
            $file_type = "Adobe Premiere";
          }
          // Adobe Audition
          elseif ($file_ext == ".sesx")
          {
            $file_icon = "file_sesx.png";
            $file_type = "Adobe Audition";
          }
          // Open Office Text
          elseif ($file_ext == ".odt" || $file_ext == ".fodt")
          {
            $file_icon = "file_odt.png";
            $file_type = "OO Text";
          }
          // Open Office Spreadsheet
          elseif ($file_ext == ".ods" || $file_ext == ".fods")
          {
            $file_icon = "file_ods.png";
            $file_type = "OO Spreadsheet";
          }
          // Open Office Presentation
          elseif ($file_ext == ".odp" || $file_ext == ".fodp")
          {
            $file_icon = "file_odp.png";
            $file_type = "OO Presentation";
          }
          // HMTL
          elseif ($file_ext == ".htm" || $file_ext == ".html")
          {
            $file_icon = "file_htm.png";
            $file_type = "HTML";
          }
          // CSS
          elseif ($file_ext == ".css")
          {
            $file_icon = "file_css.png";
            $file_type = "CSS";
          }
          // JavaScript
          elseif ($file_ext == ".js")
          {
            $file_icon = "file_js.png";
            $file_type = "JavaScript";
          }
          // JavaScript
          elseif ($file_ext == ".exe")
          {
            $file_icon = "file_exe.png";
            $file_type = "EXE";
          }
          // text based documents in proprietary format or clear text 
          elseif (@substr_count (strtolower ($hcms_ext['bintxt']).".", $file_ext.".") > 0 || @substr_count (strtolower ($hcms_ext['cleartxt']).".", $file_ext.".") > 0)
          {
            $file_icon = "file_txt.png";
            $file_type = "Text";
          } 
          // image files 
          elseif (@substr_count (strtolower ($hcms_ext['image'].$hcms_ext['rawimage'].$hcms_ext['vectorimage']).".", $file_ext.".") > 0)
          {
            $file_icon = "file_image.png";
            $file_type = "Image";
          }
          // CAD files 
          elseif (@substr_count (strtolower ($hcms_ext['cad']).".", $file_ext.".") > 0)
          {
            $file_icon = "file_cad.png";
            $file_type = "Computer Aided Design";
          }
          // Adobe Flash
          elseif (@substr_count (strtolower ($hcms_ext['flash']).".", $file_ext.".") > 0)
          {
            $file_icon = "file_flash.png";
            $file_type = "Adobe Flash";
          }
          // Audio files
          elseif (@substr_count (strtolower ($hcms_ext['audio']).".", $file_ext.".") > 0)
          {
            $file_icon = "file_audio.png";
            $file_type = "Audio";
          }
          // Apple Quicktime files
          elseif ($file_ext == ".qt" || $file_ext == ".qtl" || $file_ext == ".mov")
          {
            $file_icon = "file_qt.png";
            $file_type = "Quicktime Video";
          }
          // Video files
          elseif (@substr_count (strtolower ($hcms_ext['video'].$hcms_ext['rawvideo']).".", $file_ext.".") > 0)
          {
            $file_icon = "file_mpg.png";
            $file_type = "Video";
          }
          // Compressed files
          elseif (@substr_count (strtolower ($hcms_ext['compressed']).".", $file_ext.".") > 0)
          {
            $file_icon = "file_zip.png";
            $file_type = "compressed";
          }
          // Fonts
          elseif (@substr_count (strtolower ($hcms_ext['font']).".", $file_ext.".") > 0)
          {
            $file_icon = "file_font.png";
            $file_type = "Font";
          }
          // CMS template files
          elseif (@substr_count (strtolower ($hcms_ext['template']).".", $file_ext.".") > 0)
          {
            if (@substr_count ($file, ".page.tpl"))
            {
              $file_icon = "template_page.png";
              $file_type = "Page Template";
            }
            elseif (@substr_count ($file, ".comp.tpl"))
            {
              $file_icon = "template_comp.png";
              $file_type = "Component Template";
            }
            elseif (@substr_count ($file, ".meta.tpl"))
            {
              $file_icon = "template_media.png";
              $file_type = "Meta Data Template";
            }
            elseif (@substr_count ($file, ".inc.tpl"))
            {
              $file_icon = "template_comp.png";
              $file_type = "Template Component";
            }

            $file_type = "Template";
          }
          // CMS files
          elseif (@substr_count (strtolower ($hcms_ext['cms']).".", $file_ext.".") > 0)
          {
            if ($cat == "page")
            {
              $file_icon = "file_page.png";
              $file_type = "Page";
            }
            elseif ($cat == "comp")
            {
              $file_icon = "file_comp.png";
              $file_type = "Component";
            }
            else
            {
              $file_icon = "file_page.png";
              $file_type = "Page";
            }
          }
        }
      }
    }
    
    // set result array
    $result = array();
    $result['file'] = $file_name;
    $result['name'] = specialchr_decode ($file_name);
    $result['filename'] = $file_nameonly;
    $result['icon'] = $file_icon;
    $result['type'] = $file_type;
    $result['ext'] = $file_ext;
    $result['published'] = $file_published;
    $result['deleted'] = $file_deleted;
  }
  else $result = false;

  return $result;
}

// ---------------------------------------------- getobjectinfo ----------------------------------------------
// function: getobjectinfo()
// input: publication name [string], location [string], object name [string], user name [string] (optional), container version [string] (optional)
// output: result array / false on error
// requires: config.inc.php

// description:
// Get all file pointers (container, media, template) and object name from object file and collect info from container version, if provided.

function getobjectinfo ($site, $location, $object, $user="sys", $container_version="")
{
  global $mgmt_config;

  // deconvert location
  $location = deconvertpath ($location, "file"); 

  // if object includes special characters
  if (specialchr ($object, ".-_~") == true) $object = specialchr_encode ($object, false);

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($object))
  {
    $result = array();
    $result['template'] = "";
    $result['content'] = $result['container'] = "";
    $result['media'] = "";
    $result['file'] = $object;
    $result['name'] = "";
    $result['filename'] = "";
    $result['container_id'] = "";
    $result['contentobjects'] = "";
    $result['icon'] = "";

    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    // get category
    $cat = getcategory ($site, $location.$object);

    // convert location
    $location_esc = convertpath ($site, $location, $cat);

    // evaluate if object is a file or a folder
    if (is_dir ($location.$object))
    {
      $location = $location.$object."/";
      $object = ".folder";
    }
    elseif (!is_file ($location.$object))
    { 
      $object = correctfile ($location, $object, $user); 
    }

    // get name
    if ($object == ".folder") $name = getobject ($location);
    else $name = $object;

    // get file icon
    $fileinfo = getfileinfo ($site, $location_esc.$object, $cat);
    $result['icon'] = $fileinfo['icon'];

    // load object file
    $data = loadfile ($location, $object);

    if ($data != "")
    {
      $result['template'] = getfilename ($data, "template");
      $result['content'] = $result['container'] = getfilename ($data, "content");
      $result['media'] = getfilename ($data, "media");
      $result['file'] = $object;
      $result['name'] = specialchr_decode ($name);
      $result['filename'] = getfilename ($data, "name");
      $result['container_id'] = substr ($result['content'], 0, strpos ($result['content'], ".xml"));
      $result['contentobjects'] = array ($location_esc.$object);
    }

    // collect information from container version
    if (trim ($container_version) != "")
    {
      // if container with media file version (media file name = container version name)
      // reset media name and container ID 
      if (strpos ("_".$container_version, "_hcm") > 0)
      {
        $container_id = getmediacontainerid ($container_version);

        if (@preg_match ("/_hcm".$result['container_id']."/i", $container_version))
        {
          $result['media'] = $container_version;
        }
      }
      // if container name
      elseif (strpos ($container_version, ".xml") > 0)
      {
        $container_id = substr ($container_version, 0, strpos ($container_version, ".xml"));
      }

      // if container ID of object and provided container version match
      if ($result['container_id'] == $container_id)
      {
        // reset container name and container ID
        $result['content'] = $container_version;
        $result['container_id'] = $container_id;

        // reset media file name
        $mediafile = getmediafileversion ($container_version);

        if (!empty ($mediafile)) $result['media'] = $mediafile;

        // get object name from container
        $container_data = loadcontainer ($container_version, "version", $user);
        $contentobjects_temp = getcontent ($container_data, "<contentobjects>");

        $contentobjects = array();

        if (!empty ($contentobjects_temp[0])) $contentobjects = link_db_getobject ($contentobjects_temp[0]);

        $result['contentobjects'] = $contentobjects;

        // if connected objects
        if (sizeof ($contentobjects) > 1)
        {
          foreach ($contentobjects as $contentobject_v)
          {
            // assuming that the object is still in the same location
            if (getlocation ($contentobject_v) == $location_esc)
            {
              $object = getobject ($contentobject_v);
              break;
            }
          }

          // if no connected object has been identified, use all names
          if (empty ($object))
          {
            $object = implode (", ", $contentobjects);
            $object = str_replace ("/.folder", "", $object);
          }
        }
        // if single object
        elseif (!empty ($contentobjects[0]))
        {
          $object = getobject ($contentobjects[0]);
        }

        // get name
        if ($object == ".folder") $name = getobject ($location);
        else $name = $object;
 
        // reset object name
        $result['filename'] = specialchr_decode ($object);
        $result['name'] = specialchr_decode ($name);
      }
    }

    // return result array
    if (sizeof ($result) > 0) return $result;
  }
  
  return false;
}

// ---------------------- getfilesize -----------------------------
// function: getfilesize()
// input: converted path to file or directory [string]
// output: result array with file size in kB and file count / false on error

// description:
// This function won't give you a proper result of the file size of multimedia components, if there is no database installed.

function getfilesize ($file)
{
  global $mgmt_config;

  if (is_array ($mgmt_config) && valid_locationname ($file) && (substr_count ($file, "%page%") == 1 || substr_count ($file, "%comp%") == 1))
  {
    $cat = getcategory ("", $file);
    $site = getpublication ($file);

    // get file size from DB (only works for media files!)
    if ($cat == "comp" && !empty ($mgmt_config['db_connect_rdbms']))
    { 
      // get file size
      return rdbms_getfilesize ("", $file, false);
    }
    // get file size from file system (won't work on multimedia components!)
    elseif ($cat == "page")
    {
      // get object file
      $object = getobject ($file);

      // cut off .folder
      if ($object == ".folder") $file = getlocation ($file);

      // deconvert path 
      $file_abs = deconvertpath ($file, "file");

      // if object
      if (is_file ($file_abs))
      {
        // get file size in kB
        $size = round ((filesize ($file_abs) / 1024), 0);

        return array('filesize'=>$size, 'count'=>0);
      }
      // if folder
      elseif (is_dir ($file_abs) && $scandir = scandir ($file_abs))
      {
        $size = 0;
        $n = 0;

        // add slash if not present at the end
        if (substr ($file, -1) != "/") $file = $file."/"; 

        foreach ($scandir as $item)
        {
          if ($item == "." || $item == ".." || $item == ".folder") continue;

          $n++;
          $data = getfilesize ($file.$item);
          $size += $data['filesize'];
          $n += $data['count'];
        }

        return array('filesize'=>$size, 'count'=>$n);
      }

      return array('filesize'=>0, 'count'=>0);
    }
  }
  
  return false;
}

// ---------------------- getmimetype -----------------------------
// function: getmimetype()
// input: file name incl. extension [string] 
// output: mime_type

// description:
// Gets the mime-type of the file by its extension.
// If file has a version file extension the next file extension will be used.

function getmimetype ($file)
{
  global $mgmt_config;

  if (valid_objectname ($file))
  {
    include ($mgmt_config['abs_path_cms']."include/format_mime.inc.php");

    // get the file extension of the file
    $file_ext = strtolower (strrchr ($file, "."));

    // avoid version file extension
    if (substr_count ($file, ".") > 0 && substr ($file_ext, 0, 3) == ".v_")
    {
      $file = substr ($file, 0, strpos ($file, ".v_"));
      $file_ext = strtolower (strrchr ($file, "."));
    }

    // check if mime-type for the given extension exists
    if (!empty ($mimetype[$file_ext])) return $mimetype[$file_ext];
    else return "application/octetstream";
  }
  else return "";
}

// ---------------------- getbase64fileextension -----------------------------
// function: getbase64fileextension()
// input: base 64 encoded file content [string] 
// output: file extension

// description:
// Returns the file extension based on the base64 encoded file content.

function getbase64fileextension ($base64)
{
  global $mgmt_config;

  if (!empty ($base64))
  {
    include ($mgmt_config['abs_path_cms']."include/format_mime.inc.php");

    // get the file extension (first entry will be returned)
    // keep in mind that the same mime-type is assoziated to different file extension
    foreach ($mimetype as $ext => $mimetype)
    {
      if (strpos ($base64, $mimetype.";") > 0) return $ext;
    }
  }

  // if mime-type has not been found
  return "";
}

// ---------------------- getfiletype -----------------------------
// function: getfiletype()
// input: file extension or file name [string]
// output: file type to be saved in database based on file extension

function getfiletype ($file_ext)
{
  global $mgmt_config, $hcms_ext; 

  // load file extensions
  if (!is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

  if ($file_ext != "" && is_array ($hcms_ext))
  {
    if (substr_count ($file_ext, ".") > 0) $file_ext = strrchr ($file_ext, ".");
    else $file_ext = ".".$file_ext;

    $file_ext = strtolower ($file_ext);

    if (substr_count (strtolower ($hcms_ext['audio']).".", $file_ext.".") > 0) $filetype = "audio";
    elseif (substr_count (strtolower ($hcms_ext['bintxt'].$hcms_ext['cleartxt'].$hcms_ext['font']).".", $file_ext.".") > 0) $filetype = "document";
    elseif (substr_count (strtolower ($hcms_ext['cms'].$hcms_ext['cleartxt']), $file_ext.".") > 0) $filetype = "text";
    elseif (substr_count (strtolower ($hcms_ext['image'].$hcms_ext['rawimage'].$hcms_ext['vectorimage'].$hcms_ext['cad']).".", $file_ext.".") > 0) $filetype = "image";
    elseif (substr_count (strtolower ($hcms_ext['video'].$hcms_ext['rawvideo']).".", $file_ext.".") > 0) $filetype = "video";
    elseif (substr_count (strtolower ($hcms_ext['flash']).".", $file_ext.".") > 0) $filetype = "flash";
    elseif (substr_count (strtolower ($hcms_ext['compressed']).".", $file_ext.".") > 0) $filetype = "compressed";
    elseif (substr_count (strtolower ($hcms_ext['binary']).".", $file_ext.".") > 0) $filetype = "binary";
    elseif (strtolower ($file_ext) == ".folder") $filetype = "folder";
    else $filetype = "unknown";

    return $filetype;
  }
  elseif ($file_ext == "")
  {
    return "unknown";
  }
  else return false;
}

// ---------------------- getpreviewwidth -----------------------------
// function: getpreviewwidth()
// input: publication name [string] (optional), path to file or file name [string], original width [string] (optional)
// output: result array with width and height / false on error

// description:
// Returns the default preview/annotation width in pixel of a document, image, or video

function getpreviewwidth ($site, $filepath, $width_orig="")
{
  global $mgmt_config, $hcms_ext;

  $default_width = 576;

  if (valid_locationname ($filepath))
  {
    // image
    if (is_video ($filepath))
    {
      if ($site != "" && !empty ($mgmt_config[$site]['preview_video_width']) && $mgmt_config[$site]['preview_video_width'] == "original" && $width_orig > 0)
      {
         $default_width = $width_orig;
      }
      elseif ($site != "" && !empty ($mgmt_config[$site]['preview_video_width']) && $mgmt_config[$site]['preview_video_width'] > 220)
      {
         $default_width = $mgmt_config[$site]['preview_video_width'];
      }
      elseif (!empty ($mgmt_config['preview_video_width']) && $mgmt_config['preview_video_width'] == "original" && $width_orig > 0)
      {
         $default_width = $width_orig;
      }
      elseif (!empty ($mgmt_config['preview_video_width']) && $mgmt_config['preview_video_width'] > 220)
      {
         $default_width = $mgmt_config['preview_video_width'];
      }
    }
    // video
    elseif (is_image ($filepath) || is_rawimage ($filepath))
    {
      if ($site != "" && !empty ($mgmt_config[$site]['preview_image_width']) && $mgmt_config[$site]['preview_image_width'] == "original" && $width_orig > 0)
      {
         $default_width = $width_orig;
      }
      elseif ($site != "" && !empty ($mgmt_config[$site]['preview_image_width']) && $mgmt_config[$site]['preview_image_width'] > 220)
      {
        $default_width = $mgmt_config[$site]['preview_image_width'];
      }
      elseif (!empty ($mgmt_config['preview_image_width']) && $mgmt_config['preview_image_width'] == "original" && $width_orig > 0)
      {
         $default_width = $width_orig;
      }
      elseif (!empty ($mgmt_config['preview_image_width']) && $mgmt_config['preview_image_width'] > 220)
      {
        $default_width = $mgmt_config['preview_image_width'];
      }
    }
    // document
    elseif (is_document ($filepath))
    {
      if ($site != "" && !empty ($mgmt_config[$site]['preview_document_width']) && $mgmt_config[$site]['preview_document_width'] == "original" && $width_orig > 0)
      {
         $default_width = $width_orig;
      }
      elseif ($site != "" && !empty ($mgmt_config[$site]['preview_document_width']) && $mgmt_config[$site]['preview_document_width'] > 220)
      {
         $default_width = $mgmt_config[$site]['preview_document_width'];
      }
      elseif (!empty ($mgmt_config['preview_document_width']) && $mgmt_config['preview_document_width'] == "original" && $width_orig > 0)
      {
         $default_width = $width_orig;
      }
      elseif (!empty ($mgmt_config['preview_document_width']) && $mgmt_config['preview_document_width'] > 220)
      {
         $default_width = $mgmt_config['preview_document_width'];
      }
    }
  }

  if ($default_width > 0) return $default_width;
  else return 576;
}

// ---------------------- getimagecolorkey -----------------------------
// function: getimagecolorkey()
// input: image resource [resource]
// output: color key of image / false on error

// description:
// Extracts the color key for an image that represents the 5 mostly used colors:
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

    // color key with the 5 mostly used colors in the image
    $colorkey = substr (strrev ($colorkey), 0, 5);

    return $colorkey;
  }
  
  return false;
}

// ---------------------- getimagecolorname -----------------------------
// function: getimagecolorname()
// input: image color key [string], 2-digit language code [string] (optional) 
// output: color name / false on error

// description:
// Translates the color key and returns the color name:
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

function getimagecolorname ($colorkey, $lang="en")
{
  global $mgmt_config, $hcms_lang;

  if ($lang != "" && empty ($hcms_lang['black'][$lang]) && !empty ($mgmt_config['abs_path_cms']))
  {
    // include language file
    if (is_file ($mgmt_config['abs_path_cms']."language/".$lang.".inc.php")) require_once ($mgmt_config['abs_path_cms']."language/".$lang.".inc.php");
  }

  // translate colors
  if ($colorkey != "" && is_array ($hcms_lang))
  {
    $colors = array (
      "K"=>$hcms_lang['black'][$lang], 
      "W"=>$hcms_lang['white'][$lang],  
      "E"=>$hcms_lang['grey'][$lang], 
      "R"=>$hcms_lang['red'][$lang], 
      "G"=>$hcms_lang['green'][$lang], 
      "B"=>$hcms_lang['blue'][$lang], 
      "C"=>$hcms_lang['cyan'][$lang], 
      "M"=>$hcms_lang['magenta'][$lang], 
      "Y"=>$hcms_lang['yellow'][$lang], 
      "O"=>$hcms_lang['orange'][$lang], 
      "P"=>$hcms_lang['pink'][$lang], 
      "N"=>$hcms_lang['brown'][$lang]
    );

    if (!empty ($colors[$colorkey])) return $colors[$colorkey];
  }

  return false;
}

// ---------------------- getimagecolors -----------------------------
// function: getimagecolors()
// input: publication name [string], media file name [string]
// output: result array

// description:
// Uses the thumbnail image to calculate the mean color (red, green, blue), defines the colorkey (5 most commonly used colors) and the image type (landscape, portrait, square)

function getimagecolors ($site, $file)
{
  global $mgmt_config, $user;

  // initialize
  $result = array();
  $result['red'] = NULL;
  $result['green'] = NULL;
  $result['blue'] = NULL;
  $result['colorkey'] = "";
  $result['imagetype'] = "";

  if (valid_publicationname ($site) && valid_objectname ($file))
  {
    $media_root = getmedialocation ($site, $file, "abs_path_media").$site."/";
    $file_info = getfileinfo ($site, $file, "comp");
    $file = $file_info['file'];

    // try thumbnail image first
    $thumbnail = $file_info['filename'].".thumb.jpg";

    // use thumbnail image file
    if (is_file ($media_root.$thumbnail) && (exif_imagetype ($media_root.$thumbnail) == IMAGETYPE_JPEG && valid_jpeg ($media_root.$thumbnail)))
    {
      $image = @imagecreatefromjpeg ($media_root.$thumbnail);
    }
    // try original image
    else
    {
      // remove faulty thumbnail image
      if (is_file ($media_root.$thumbnail)) unlink ($media_root.$thumbnail);

      // prepare media file
      $temp_source = preparemediafile ($site, $media_root, $file, $user);

      // if encrypted
      if (!empty ($temp_source['result']) && !empty ($temp_source['crypted']) && !empty ($temp_source['templocation']) && !empty ($temp_source['tempfile']))
      {
        $media_root = $temp_source['templocation'];
        $file = $temp_source['tempfile'];
      }
      // if restored
      elseif (!empty ($temp_source['result']) && !empty ($temp_source['restored']) && !empty ($temp_source['location']) && !empty ($temp_source['file']))
      {
        $media_root = $temp_source['location'];
        $file = $temp_source['file'];
      }

      // verify local media file
      if (!is_file ($media_root.$file)) return false;

      if ($file_info['ext'] == ".jpg") $image = @imagecreatefromjpeg ($media_root.$file);
      elseif ($file_info['ext'] == ".png") $image = @imagecreatefrompng ($media_root.$file);
      elseif ($file_info['ext'] == ".gif") $image = @imagecreatefromgif ($media_root.$file);
      else $image = false;

      // delete temp file
      if ($temp_source['result'] && $temp_source['created']) deletefile ($temp_source['templocation'], $temp_source['tempfile'], 0);
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

      $result['red'] = $totalred;
      $result['green'] = $totalgreen;
      $result['blue'] = $totalblue;
      $result['colorkey'] = $colorkey;
      $result['imagetype'] = $imagetype;
    }
  }

  return $result;
}

// --------------------------------------- getbrightness -------------------------------------------
// function: getbrightness()
// input: hex color code [string]
// output: Brightness level (dark < 130 and light > 130) / false on error

function getbrightness ($color)
{
  if (strlen ($color) >= 6)
  {
    if (strlen ($color) == 7) $color = substr ($color, 1);
    
    $r = hexdec (substr ($color, 0, 2));
    $g = hexdec (substr ($color, 2, 2));
    $b = hexdec (substr ($color, 4, 2));

    // Background Brightness < 130 => Textcolor '#FFFFFF' else '#000000'
    return sqrt ($r * $r * .241 + $g * $g * .691 + $b * $b * .068);
  }
  
  return false;
}

// --------------------------------------- getmediasize -------------------------------------------
// function: getmediasize()
// input: path to media file [string]
// output: Array with media width and height / false on error

function getmediasize ($filepath)
{
  global $mgmt_config, $mgmt_imagepreview, $user;

  if (valid_locationname ($filepath))
  {
    // initialize
    $result = array();
    $result['width'] = 0;
    $result['height'] = 0;

    // get publication, location and media object
    $site = getpublication ($filepath);
    $location = getlocation ($filepath);
    $media = getobject ($filepath);

    // use JPEG version of original image
    if (is_rawimage ($media) || is_kritaimage ($media))
    {
      $file_info = getfileinfo ($site, $media, "comp");

      // prepare media file
      $temp_file = preparemediafile ($site, $location, $file_info['filename'].".jpg", $user);

      // if encrypted
      if (!empty ($temp_file['result']) && !empty ($temp_file['crypted']) && !empty ($temp_file['templocation']) && !empty ($temp_file['tempfile']))
      {
        $location = $temp_file['templocation'];
        $media = $temp_file['tempfile'];
      }
      // if restored
      elseif (!empty ($temp_file['result']) && !empty ($temp_file['restored']) && !empty ($temp_file['location']) && !empty ($temp_file['file']))
      {
        $location = $temp_file['location'];
        $media = $temp_file['file'];
      }
      // use existing file
      else
      {
        $media = $file_info['filename'].".jpg";
      }

      // set new file path
      $filepath = $location.$media;

      // verify local media file
      if (!is_file ($filepath)) return false;

      // get file width and heigth in pixels
      $imagesize = getimagesize ($filepath);

      if (!empty ($imagesize))
      {
        $result['width'] = $imagesize[0];
        $result['height'] = $imagesize[1];
      }     
    }
    // other image formats
    else
    {
      // prepare media file
      $temp = preparemediafile ($site, $location, $media, $user);

      // if encrypted
      if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
      {
        $location = $temp['templocation'];
        $media = $temp['tempfile'];
      }
      // if restored
      elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
      {
        $location = $temp['location'];
        $media = $temp['file'];
      }

      // set new file path
      $filepath = $location.$media;

      // verify local media file
      if (!is_file ($filepath)) return false;

      // get file width and heigth in pixels
      $imagesize = @getimagesize ($filepath);

      if (!empty ($imagesize))
      {
        $result['width'] = $imagesize[0];
        $result['height'] = $imagesize[1];
      }
      // try to read values from source file (e.g. SVG files)
      else
      {
        // use ImageMagick
        if (!empty ($mgmt_imagepreview) && is_supported ($mgmt_imagepreview, $filepath))
        {
          // get size of first page if document with more than one page
          $cmdresult = exec ("identify -format \"%wx%h\" \"".shellcmd_encode ($filepath)."[0]\"");

          if (strpos ($cmdresult, "x") > 0) list ($result["width"], $result["height"]) = explode ("x", $cmdresult);
        }
        // extract from source
        else
        {
          $header = loadfile_header (getlocation ($filepath), getobject ($filepath));

          if (!empty ($header))
          {
            // get SVG tag
            $svg_tag = gethtmltag ($header, "svg");

            if (!empty ($svg_tag)) $header = $svg_tag;

            $result['width'] = getattribute ($header, "width", true);
            $result['height'] = getattribute ($header, "height", true);
          }
        }
      }
    }

    // use EXIF image orientation in order to correct width and height 
    // (converted image will be auto rotated by function createmedia)
    $exif = @exif_read_data ($filepath, 0, true);

    if (!empty ($exif['IFD0']['Orientation']))
    {
      $orientation = $exif['IFD0']['Orientation'];

      switch ($orientation)
      {
        // 180 rotate left (leave width and height)
        case 3:
          break;

        // 90 rotate right (switch width and height)
        case 6:
          $temp_width = $result['width'];
          $result['width'] = $result['height'];
          $result['height'] = $temp_width;
          break;

        // 90 rotate left (switch width and height)
        case 8:
          $temp_width = $result['width'];
          $result['width'] = $result['height'];
          $result['height'] = $temp_width;
          break;
      }
    }

    // delete temp file
    if (!empty ($temp['result']) && !empty ($temp['created']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
    {
      deletefile ($temp['templocation'], $temp['tempfile'], 0);
    }

    // return result
    return $result;
  }

  return false;
}

// --------------------------------------- getimageinfo -------------------------------------------
// function: getimageinfo()
// input: path to media file [string]
// output: Array with image information like md5 hash, file type, file size, width, height, colors / false on error

function getimageinfo ($filepath)
{
  global $mgmt_config, $mgmt_imagepreview, $user;

  if (valid_locationname ($filepath))
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

    // MD5 hash of the original file
    $md5_hash = md5_file ($filepath);

    // get file-type
    $filetype = getfiletype ($media);

    // file size in kB
    if (filesize ($filepath) > 0) $filesize = round (filesize ($filepath) / 1024, 0);
    else $filesize = 0;

    // image colors
    $imagecolors = getimagecolors ($site, $media);

    // file time (use thumbnail as first option)
    $media_root = getmedialocation ($site, $media, "abs_path_media").$site."/";
    $file_info = getfileinfo ($site, $media, "comp");
    if (!empty ($file_info['filename'])) $thumbnail = $file_info['filename'].".thumb.jpg";

    if (!empty ($thumbnail) && is_file ($media_root.$thumbnail)) $filetime = date ("Y-m-d H:i", filemtime ($media_root.$thumbnail));
    elseif (!empty ($filepath) && is_file ($filepath)) $filetime = date ("Y-m-d H:i", filemtime ($filepath));
    else $filetime = false;

    // image size
    $imagesize = getmediasize ($filepath);

    // delete temp file
    if ($temp['result'] && $temp['created']) deletefile ($temp['templocation'], $temp['tempfile'], 0);

    // initialize result
    $result = array();
    $result['red'] = 0;
    $result['green'] = 0;
    $result['blue'] = 0;
    $result['colorkey'] = "";
    $result['width'] = 0;
    $result['height'] = 0;
    $result['imagetype'] = "";

    if (is_array ($imagecolors) && is_array ($imagesize)) $result = array_merge ($imagecolors, $imagesize);
    elseif (is_array ($imagecolors)) $result = $imagecolors;
    elseif (is_array ($imagesize)) $result = $imagesize;

    $result['md5_hash'] = $md5_hash;
    $result['filetype'] = $filetype;
    $result['filesize'] = $filesize;
    $result['filetime'] = $filetime;

    return $result;
  }

  return false;
}

// ---------------------- getpdfinfo -----------------------------
// function: getpdfinfo()
// input: path to PDF file [string], box attribute [BleedBox,CropBox,MediaBox] (optional)
// output: result array with MD5 hash, file type, file size, last modfied date and time, width, height / false on error

// description:
// Extracts width and height in pixel of a PDF file based on the MediaBox in the files content or ImageMagick as fallback

function getpdfinfo ($filepath, $box="MediaBox")
{
  global $mgmt_config, $mgmt_imagepreview, $user;

  if (valid_locationname ($filepath))
  {
    $result = array();

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

    // MD5 hash of the original file
    $md5_hash = md5_file ($filepath);

    // get file-type
    $filetype = getfiletype ($media);

    // file size in kB
    if (filesize ($filepath) > 0) $filesize = round (filesize ($filepath) / 1024, 0);
    else $filesize = 0;

    // file time (use thumbnail as first option)
    $media_root = getmedialocation ($site, $media, "abs_path_media").$site."/";
    $file_info = getfileinfo ($site, $media, "comp");
    if (!empty ($file_info['filename'])) $thumbnail = $file_info['filename'].".thumb.jpg";

    if (!empty ($thumbnail) && is_file ($media_root.$thumbnail)) $filetime = date ("Y-m-d H:i", filemtime ($media_root.$thumbnail));
    elseif (!empty ($filepath) && is_file ($filepath)) $filetime = date ("Y-m-d H:i", filemtime ($filepath));
    else $filetime = false;

    // read dimensions from file stream
    $stream = new SplFileObject ($filepath); 

    while (!$stream->eof())
    {
      if (preg_match("/".$box."\[[0-9]{1,}.[0-9]{1,} [0-9]{1,}.[0-9]{1,} ([0-9]{1,}.[0-9]{1,}) ([0-9]{1,}.[0-9]{1,})\]/", $stream->fgets(), $matches))
      {
        $result["width"] = $matches[1];
        $result["height"] = $matches[2]; 
        break;
      }
    }

    $stream = null;

    // use ImageMagick if MediaBox failed
    if ((empty ($result["width"]) || empty ($result["height"])) && !empty ($mgmt_imagepreview) && is_supported ($mgmt_imagepreview, $media))
    {
      $cmdresult = exec ("identify -format \"%wx%h\" \"".shellcmd_encode ($filepath)."[0]\"");

      if (strpos ($cmdresult, "x") > 0) list ($result["width"], $result["height"]) = explode ("x", $cmdresult);
    }

    // delete temp file
    if ($temp['result'] && $temp['created']) deletefile ($temp['templocation'], $temp['tempfile'], 0);

    // result
    $result['md5_hash'] = $md5_hash;
    $result['filetype'] = $filetype;
    $result['filesize'] = $filesize;
    $result['filetime'] = $filetime;

    return $result;
  }

  return false;
}

// ---------------------- getvideoinfo -----------------------------
// function: getvideoinfo()
// input: path to video file [string]
// output: video file information as result array / false on error

// description:
// Extract video metadata from video file.

function getvideoinfo ($filepath)
{
  global $mgmt_config, $mgmt_mediapreview, $user;

  // read media information from media files
  if (valid_locationname ($filepath))
  {
    $dimensionRegExp = "/, ([0-9]+x[0-9]+)/";
    $durationRegExp = "/Duration: ([0-9\:\.]+)/i";
    $bitRateRegExp = "/bitrate: ([0-9]+ [a-z]+\/s)/i";
    $rotateRegExp = "/rotate +: ([0-9]+)/i";

    $dimension = "";
    $width = "";
    $height = "";
    $rotate = "0";
    $duration = "";
    $duration_no_ms = "";
    $video_bitrate = "";
    $imagetype = "";
    $video_codec = "";
    $audio_codec = "";
    $audio_bitrate = "";
    $audio_frequenzy = "";
    $audio_channels = "";

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

    // verify media file
    if (!is_file ($filepath)) return false;

    // MD5 hash of the original file
    $md5_hash = md5_file ($filepath);

    // get file-type
    $filetype = getfiletype ($media);

    // file size in MB
    $filesize = round (@filesize ($filepath) / 1024 / 1024, 0)." MB";
    if ($filesize < 1) $filesize = "<1 MB";

    // file time (use thumbnail as first option)
    $media_root = getmedialocation ($site, $media, "abs_path_media").$site."/";
    $file_info = getfileinfo ($site, $media, "comp");
    if (!empty ($file_info['filename'])) $thumbnail = str_replace (".orig.", ".", $file_info['filename']).".thumb.jpg";

    if (!empty ($thumbnail) && is_file ($media_root.$thumbnail)) $filetime = date ("Y-m-d H:i", filemtime ($media_root.$thumbnail));
    elseif (!empty ($filepath) && is_file ($filepath)) $filetime = date ("Y-m-d H:i", filemtime ($filepath));
    else $filetime = false;

    // file extension
    $file_info = getfileinfo ("", $filepath, "comp");

    if (!empty ($file_info['ext'])) $file_ext = $file_info['ext'];
    else $file_ext = "";

    foreach ($mgmt_mediapreview as $mediapreview_ext => $mediapreview)
    {
      // check file extension
      if ($file_ext != "" && substr_count ($mediapreview_ext.".", $file_ext.".") > 0)
      {
        $return = 1;
        $metadata = array();

        // get info from video file using FFMPEG
      	$cmd = $mgmt_mediapreview[$mediapreview_ext]." -i \"".shellcmd_encode ($filepath)."\" -y -f rawvideo -vframes 1 /dev/null 2>&1";

        exec ($cmd, $metadata, $return); 

        // parsing the values
        if (is_array ($metadata) && sizeof ($metadata) > 0)
        {
          // video dimension in pixels
    	    $matches = array();

          if (preg_match ($dimensionRegExp, implode ("\n", $metadata), $matches))
          {
            $dimension = $matches[1];

            if ($dimension != "")
            {
              list ($width, $height) = explode ("x", $dimension);

              $dimension = $dimension." px";
 
              // set 'portrait', 'landscape' or 'square' for the image type
              if ($width > 0 && $height > 0)
              {
              	if ($width > $height) $imagetype = "landscape";
              	elseif ($height > $width) $imagetype = "portrait";
              	elseif ($height == $width) $imagetype = "square";
              }
              else
              {
                $dimension = "";
                $width = "";
                $height = "";
              }
            }
          }

          // video duration in hours:minutes:seconds.milliseconds
          $matches = array();

          if (preg_match ($durationRegExp, implode ("\n", $metadata), $matches))
          {
            // cut of milliseconds
            if (strpos ($matches[1], ".") > 6) $duration_no_ms = substr ($matches[1], 0, -3);

            $duration = $matches[1];
          }

          // video bitrate in kB/s (flac file uses the same bitrate declaration as video streams)
    	  $matches = array();

    	  if (preg_match ($bitRateRegExp, implode ("\n", $metadata), $matches))
          {
            $video_bitrate = $matches[1];
          }

          // video rotation in degrees
          $matches = array();

          if (preg_match ($rotateRegExp, implode ("\n", $metadata), $matches))
          {
            $rotate = $matches[1];
          }

          // audio and video information (codec, bitrate and frequenzy)
          reset ($metadata);

          foreach ($metadata as $line)
          {
            if (strpos ("_".$line, " Audio: ") > 0)
            {
              // MP4 Audio: aac (mp4a / 0x6134706D), 11025 Hz, mono, s16, 53 kb/s
              // MPEG Audio: mp2, 0 channels, s16p
              $line = substr ($line, strpos ($line, " Audio: ") + 8);

              // audio (audio bitrate might be missing in flac files)
              if (substr_count ($line, ",") >= 4) list ($audio_codec, $audio_frequenzy, $audio_channels, $audio_sample, $audio_bitrate) = explode (", ", $line);
              elseif (substr_count ($line, ",") >= 2)  list ($audio_codec, $audio_channels, $audio_sample) = explode (", ", $line);

              // clean codec name
              if (strpos ($audio_codec, "(") > 0) $audio_codec = substr ($audio_codec, 0, strpos ($audio_codec, "("));
              $audio_codec = strtoupper (trim ($audio_codec));

              // verify frequenzy
              if (strpos (strtolower ($audio_frequenzy), "hz") < 1) $audio_frequenzy = "";

              // verify audio channels
              if (strlen ($audio_channels) > 10) $audio_channels = "";

              break;
            }
          }

          reset ($metadata);

          foreach ($metadata as $line)
          {
            if (strpos ("_".$line, " Video: ") > 0)
            {
              // Video: wmv2 (WMV2 / 0x32564D57), yuv420p, 320x240, 409 kb/s, 25 tbr, 1k tbn, 1k tbc

              // tbn = the time base in AVStream that has come from the container
              // tbc = the time base in AVCodecContext for the codec used for a particular stream
              // tbr = tbr is guessed from the video stream and is the value users want to see when they look for the video frame rate

              $line = substr ($line, strpos ($line, " Video: ") + 8);

              // video
              @list ($video_codec, $colorspace) = explode (", ", $line);

              // clean codec name
              if (strpos ($video_codec, "(") > 0) $video_codec = substr ($video_codec, 0, strpos ($video_codec, "("));
              $video_codec = strtoupper (trim ($video_codec));

              break;
            }
          }

          // use video bitrate if audio is not available (for flac audio files)
          if (empty ($audio_bitrate) && !empty ($video_bitrate)) $audio_bitrate = $video_bitrate;
        }
      }
    }

    // delete temp file
    if ($temp['result'] && $temp['created']) deletefile ($temp['templocation'], $temp['tempfile'], 0);

    // return result 
    $result = array();
    $result['md5_hash'] = $md5_hash;
    $result['filetype'] = $filetype;
    $result['filesize'] = $filesize;
    $result['filetime'] = $filetime;
    $result['dimension'] = $dimension;
    $result['width'] = $width;
    $result['height'] = $height;
    if ($height > 0) $result['ratio'] = round (($width / $height), 5);
    else $result['ratio'] = 0;
    $result['rotate'] = $rotate;
    $result['duration'] = $duration;
    $result['duration_no_ms'] = $duration_no_ms;
    $result['videobitrate'] = $video_bitrate;
    $result['imagetype'] = $imagetype;
    $result['videocodec'] = $video_codec;
    $result['audiocodec'] = $audio_codec;
    $result['audiobitrate'] = $audio_bitrate;
    $result['audiofrequenzy'] = $audio_frequenzy;
    $result['audiochannels'] = $audio_channels;

    return $result;
  }

  return false;
}

// --------------------------------------- getbrowserinfo -----------------------------------------------
// function: getbrowserinfo ()
// input: %
// output: client browser and version as array / false on error

function getbrowserinfo ()
{
  if (!empty ($_SERVER['HTTP_USER_AGENT']))
  {
    $u_agent = $_SERVER['HTTP_USER_AGENT'];
    $bname = 'unknown';
    $ub = "";
    $version = "";

    // get the browser name
    // works only for MS IE < 11
    if (preg_match ('/MSIE/i', $u_agent) && !preg_match ('/Opera/i', $u_agent))
    {
      $bname = 'msie';
      $ub = "MSIE";
    }
    // MS IE 11
    elseif (preg_match ('/Trident/i', $u_agent))
    {
      $bname = 'msie';
      $ub = "Trident";
    }
    // MS Edge
    elseif (preg_match ('/Edge/i', $u_agent))
    {
      $bname = 'msedge';
      $ub = "Edge";
    }
    elseif (preg_match ('/Firefox/i', $u_agent))
    {
      $bname = 'firefox';
      $ub = "Firefox";
    }
    elseif (preg_match ('/Chrome/i', $u_agent))
    {
      $bname = 'chrome';
      $ub = "Chrome";
    }
    elseif (preg_match ('/Safari/i', $u_agent))
    {
        $bname = 'safari';
        $ub = "Safari";
    }
    elseif (preg_match ('/Opera/i', $u_agent))
    {
      $bname = 'opera';
      $ub = "Opera";
    }
    elseif (preg_match ('/Netscape/i', $u_agent))
    {
      $bname = 'netscape';
      $ub = "Netscape";
    }
 
    // get the version number
    $known = array ('Version', $ub, 'other');

    $pattern = '#(?<browser>'.join('|', $known).')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';

    if (!preg_match_all ($pattern, $u_agent, $matches))
    {
      // we have no matching number just continue
    }
 
    // see how many we have
    $i = count ($matches['browser']);

    if ($i != 1)
    {
      // we will have two since we are not using 'other' argument yet
      // see if version is before or after the name
      if (strripos ($u_agent, "Version") < strripos ($u_agent, $ub) && !empty ($matches['version'][0]))
      {
        $version = $matches['version'][0];
      }
      elseif (!empty ($matches['version'][1]))
      {
        $version = $matches['version'][1];
      }
    }
    else
    {
      $version = $matches['version'][0];
    }

    if (substr_count ($version, ".") > 0) $version = intval (substr ($version, 0, strpos ($version, ".")));
 
    // check if we have a number
    if ($version == null || $version == "") $version = "?";

    // result
    return array ($bname => $version);
  }

  return false;
}

// ---------------------- getcontentlocation -----------------------------
// function: getcontentlocation()
// input: container id [string], type [url_path_content,abs_path_content]
// output: location of the container file / false on error

// description:
// Gets the content location based on the given container id.
// The use of various directories is necessary since the number of directories is limited by the filesystem, e.g. Linux ext3 is limited to 32000.

function getcontentlocation ($container_id, $type="abs_path_content")
{
  global $mgmt_config;

  if (intval ($container_id) > 0 && ($type == "url_path_content" || $type == "abs_path_content") && is_array ($mgmt_config))
  {
    // directory block size of 10.000
    $limitbase = 10000;

    // container block ID number
    $block_id = floor (intval ($container_id) / $limitbase);

    // correct container ID (add zeros)
    if (strlen ($container_id) < 7)
    {
      $multiplier = 7 - strlen ($container_id);
      $container_id = str_repeat ("0", $multiplier).$container_id;
    }

    // max. 32000 subdirectories for the block numbers
    if ($block_id >= 0 && $block_id <= 32000)
    {
      // if 1st level container block directory does not exist, try to create it
      if (!is_dir ($mgmt_config['abs_path_content'].$block_id)) mkdir ($mgmt_config['abs_path_content'].$block_id, $mgmt_config['fspermission']);

      // if 2nd level container directory does not exist, try to create it
      if (!is_dir ($mgmt_config['abs_path_content'].$block_id."/".$container_id)) mkdir ($mgmt_config['abs_path_content'].$block_id."/".$container_id, $mgmt_config['fspermission']);

      return $mgmt_config[$type].$block_id."/".$container_id."/";
    }
  }
  
  return false;
} 

// ---------------------- getmedialocation -----------------------------
// function: getmedialocation()
// input: publication name [string], multimedia file name (including hcm-ID) [string], type [url_path_media,abs_path_media,url_publ_media,abs_publ_media], resolve symbolik links [boolean] (optional)
// output: location of the multimedia file / false on error

// description:
// Gets the media repsitory location from $mgmt_config array. The function supports up to 10 media repositories.
// Any other rules for splitting the media files on multiple devices can be implemented as well by the function getmedialocation_rule.
// If the file resides outside the repository (symbolic link is used in the repository), the full path including the file name can be returned.
// Use the prefix .hcms. for the media file name if you want to media location withour verification of the media file.

function getmedialocation ($site, $file, $type, $resolve_symlink=false)
{
  global $mgmt_config, $publ_config;

  if (valid_locationname ($file) && $type != "" && is_array ($mgmt_config))
  {
    // include rule from external file (must return a value) 
    if (is_file ($mgmt_config['abs_path_data']."media/getmedialocation.inc.php"))
    {
      require ($mgmt_config['abs_path_data']."media/getmedialocation.inc.php");
    }

    // get file name
    $file = getobject ($file);

    // management configuration
    if ($type == "url_path_media" || $type == "abs_path_media")
    {
      // if media repository path is available
      if (!empty ($mgmt_config[$type]))
      {
        // ----------- multiple media harddisk/mountpoint support -----------
        if (is_array ($mgmt_config[$type]))
        {
          // get container id as integer
          $container_id = intval (getmediacontainerid ($file));

          // get last digit of container id
          $last_number = substr ($container_id, -1);

          // media location rule
          if (function_exists ("getmedialocation_rule"))
          {
            $result = getmedialocation_rule ($site, $file, $type, $container_id);

            if ($result != "")
            {
              // symbolic link
              if (is_link ($result.$site."/".$file) && !empty ($resolve_symlink))
              {
                // get link target
                $targetpath = readlink ($result.$site."/".$file);

                return $targetpath;
              } 
              // directory path
              else
              {
                return $result;
              }
            }
          }

          // harddisk/mountpoint array
          $hdarray_size = sizeof ($mgmt_config[$type]);

          if ($hdarray_size == 1)
          {
            if (!empty ($mgmt_config[$type][1])) return $mgmt_config[$type][1];
          }
          elseif ($hdarray_size > 1) 
          {
            $j = 1;

            for ($i=1; $i<=10; $i++)
            {
              if (substr ($i, -1) == $last_number)
              {
                if (!empty ($mgmt_config[$type][$j]))
                {
                  // symbolic link and NOT a dummy media file
                  if (substr ($file, 0, 6) != ".hcms." && is_link ($mgmt_config[$type][$j].$site."/".$file) && !empty ($resolve_symlink))
                  {
                    // get link target
                    $targetpath = readlink ($mgmt_config[$type][$j].$site."/".$file);

                    return $targetpath;
                  }
                  // file
                  else
                  {
                    return $mgmt_config[$type][$j];
                  }
                }
              }

              // reset harddisk/mountpoint array index
              if ($j == $hdarray_size) $j = 1;
              else $j++;
            }
          }
        }
        // ----------- single media harddisk/mountpoint -----------
        else
        {
          // symbolic link and NOT a dummy media file
          if (substr ($file, 0, 6) != ".hcms." && is_link ($mgmt_config[$type].$site."/".$file) && !empty ($resolve_symlink))
          {
            // get link target
            $targetpath = readlink ($mgmt_config[$type].$site."/".$file);
            $targetroot = $targetpath;

            return $targetroot;
          }
          // file
          else
          {
            return $mgmt_config[$type];
          }
        }
      }
    }
    // publication configuration
    elseif ($type == "url_publ_media" || $type == "abs_publ_media")
    {
      // load publication INI file
      if (valid_publicationname ($site) && !is_array ($publ_config))
      {
        $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"); 
      }

      // publication media repository path is available
      if (is_array ($publ_config) && !empty ($publ_config[$type]))
      {
        return $publ_config[$type];
      }
    }
  }

  return false;
} 

// ---------------------- getlockedfileinfo -----------------------------
// function: getlockedfileinfo()
// input: location path [string], file name [string]
// output: Array holding file name incl. lock extension and user name / false on error

// description:
// Finds the locked file and returns the name and user as array

function getlockedfileinfo ($location, $file)
{
  global $mgmt_config;

  if (valid_locationname ($location) && valid_objectname ($file) && is_dir ($location))
  {
    // file is locked
    if (!is_file ($location.$file) && is_dir ($location))
    {
      $scandir = scandir ($location);

      if ($scandir)
      {
        $result = array();

        foreach ($scandir as $entry)
        {
          if (preg_match ("/".preg_quote ($file.".@")."/", $entry))
          {
            $result['file'] = $entry;
            $result['user'] = substr ($entry, strrpos ($entry, ".@") + 2);

            return $result;
          } 
        }
      }
    }
    // file exists (is not locked)
    else
    {
      $result['file'] = $file;
      $result['user'] = "";

      return $result;
    }
  }

  return false; 
}

// ---------------------------------------- getlockobjects --------------------------------------------
// function: getlockobjects()
// input: user name [string], text IDs to be returned [array] (optional)
// output: object info array / false

// description:
// Returns an object info array of all locked objects of a specific user.

function getlockedobjects ($user, $return_text_id=array())
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

              if (is_array ($result_array))
              {
                foreach ($result_array as $result)
                {
                  $location = $result['convertedlocation'];
                  $page = $result['object'];
                  $page = correctfile ($location, $page, $user);

                  // check if file exists
                  if ($page !== false)
                  {
                    $temp = convertpath ($site, $location.$page, "");

                    if ($temp != "")
                    {
                      $object_hash = rdbms_getobject_hash ($temp);
                      $object_info = rdbms_getobject_info ($object_hash, $return_text_id);
                    }

                    if (is_array ($object_info))
                    {
                      $object_array[$object_hash] = $object_info;
                    }
                    else
                    {
                      $object_array[] = $location.$page;
                    }
                  }
                }
              }
            }
          }
        }

        // update checked out list if necessary
        if ($save) savefile ($dir, $file, $data);

        if (!empty ($object_array) && is_array ($object_array) && sizeof ($object_array) > 0)
        {
          return $object_array;
        }
      }
    }
  }

  return false;
}

// --------------------------------------- getfavorites -------------------------------------------
// function: getfavorites ()
// input: user name [string], output format [path,id] (optional), text IDs to be returned if output=path [array] (optional)
// output: object info or object id array of users favorites / false

function getfavorites ($user, $output="path", $return_text_id=array())
{
  global $mgmt_config;

  if (valid_objectname ($user))
  {
    // portal user session
    if (valid_objectname ($user) && getsession ("hcms_portal"))
    {
      $object_id_array = getsession ("hcms_favorites");
    }
    // standard user
    else
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
        }
      }
    }

    // prepare result
    if (!empty ($object_id_array) && is_array ($object_id_array))
    {
      $object_id_array = array_unique ($object_id_array);

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
            $object_info = rdbms_getobject_info ($object_id, $return_text_id);

            if (!empty ($object_info['objectpath'])) 
            {
              $hash = $object_info['hash'];
              $object_path_array[$hash] = $object_info;
            }
          }
        }

        if (is_array ($object_path_array) && sizeof ($object_path_array) > 0)
        {
          return $object_path_array;
        }
      }
    }
  }

  return false;
}

// --------------------------------------- getclipboard -------------------------------------------
// function: getclipboard ()
// input: output format [path,id] (optional), text IDs to be returned if output=path [array] (optional)
// output: object info or object id array of the users clipboard objects / false

function getclipboard ($output="path", $return_text_id=array())
{
  global $mgmt_config;

  if (getsession ("hcms_temp_clipboard"))
  {
    // initialize
    $object_id_array = array();
    $object_path_array = array();

    // get clipboard objects
    $clipboard_array = getsession ("hcms_temp_clipboard");

    // prepare result
    if (!empty ($clipboard_array) && is_array ($clipboard_array))
    {
      foreach ($clipboard_array as $temp)
      {
        if (trim ($temp) != "")
        {
          list ($method, $site, $cat, $location_esc, $page, $pagename, $filetype) = explode ("|", $temp);

          if (valid_publicationname ($site) && valid_locationname ($location_esc) && valid_objectname ($page))
          {
            // function cutobject, copyobject, and copylinkedobject provide converted path with tailing slash
            $object_path = $location_esc.$page;

            // if folder
            if (strtolower ($filetype) == "folder") $object_path = $object_path."/.folder";

            if (strtolower ($output) == "id")
            {
              $object_id_array[] = rdbms_getobject_id ($object_path);
            }
            else
            {
              $object_info = rdbms_getobject_info ($object_path, $return_text_id);

              if (!empty ($object_info['objectpath'])) 
              {
                $hash = $object_info['hash'];
                $object_path_array[$hash] = $object_info;
              }
            }
          }
        }
      }
      
      // return result
      if (strtolower ($output) == "id" && sizeof ($object_id_array) > 0)
      {
        sort ($object_id_array);
        return $object_id_array;
      }
      elseif (sizeof ($object_path_array) > 0)
      {
        return $object_path_array;
      }
    }
  }

  return false;
}

// ====================================== HOME BOXES =========================================

// ------------------------------------------ gethomeboxes --------------------------------------------
// function: gethomeboxes()
// input: publication names [array] (optional)
// output: All home boxes as array with technical name as key and readable name as value / false
// requires: config.inc.php

function gethomeboxes ($site_array=array())
{
  global $mgmt_config, $siteaccess;

  $result = array();

  // collect all system home boxes
  $boxes_dir = $mgmt_config['abs_path_cms']."box/";

  if (is_dir ($boxes_dir))
  {
    $scandir = scandir ($boxes_dir);

    if ($scandir)
    {
      foreach ($scandir as $entry)
      {
        if ($entry != "." && $entry != ".." && $entry != ".folder" && is_file ($boxes_dir.$entry) && substr ($entry, -8) == ".inc.php")
        {
          $box = str_replace (".inc.php", "", $entry);
          $name = ucfirst (str_replace ("_", " ", $box));

          $result[$box] = $name;
        }
      }
    }
  }

  // collect all individual home boxes of publications
  if (!empty ($mgmt_config['homeboxes_directory']) && valid_locationname ($mgmt_config['homeboxes_directory']) && is_array ($site_array) && sizeof ($site_array) > 0)
  {
    // remove trailing slashes
    $mgmt_config['homeboxes_directory'] = trim ($mgmt_config['homeboxes_directory'], "/");

    foreach ($site_array as $site)
    {
      if (valid_publicationname ($site))
      {
        $boxes_dir = $mgmt_config['abs_path_comp'].$site."/".$mgmt_config['homeboxes_directory']."/";

        if (is_dir ($boxes_dir))
        {
          $scandir = scandir ($boxes_dir);

          if ($scandir)
          {
            foreach ($scandir as $entry)
            {
              if ($entry != "." && $entry != ".." && $entry != ".folder" && is_file ($boxes_dir.$entry) && substr ($entry, -4) == ".php")
              {
                $box = str_replace (".php", "", $entry);
                $name = str_replace ("_", " ", $box);

                if (!empty ($siteaccess[$site])) $name = $siteaccess[$site]." &gt; ".$name;
                else $name = $site." &gt; ".$name;

                $result[$site."/".$box] = $name;
              }
            }
          }
        }
      } 
    }
  }

  // return result
  if (sizeof ($result) > 0)
  {
    ksort ($result);
    reset ($result);
    return $result;
  }
  else return false;
}

// --------------------------------------- getuserboxes -------------------------------------------
// function: getuserboxes ()
// input: user name [string]
// output: selected home box of a user with technical name as key and readable name as value [array]
// requires: config.inc.php

function getuserboxes ($user)
{
  global $mgmt_config, $siteaccess;

  // initialize
  $result = array();

  if (valid_objectname ($user))
  {
    // home boxes defined by user
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
          foreach ($name_array as $name)
          {
            // individual home boxes (publication/name)
            if (strpos ($name, "/") > 0)
            {
              list ($site_temp, $name_temp) = explode ("/", $name);
              $name_temp = str_replace ("_", " ", $name_temp);

              if (!empty ($siteaccess[$site_temp])) $result[$name] = $siteaccess[$site_temp]." &gt; ".$name_temp;
              else $result[$name] = $site_temp." &gt; ".$name_temp;
            }
            // system home boxes (name)
            else
            {
              $result[$name] = ucfirst (str_replace ("_", " ", $name));
            }
          }

          return $result;
        }
      }
    }
    // default home boxes defined in main config
    elseif (!empty ($mgmt_config['homeboxes']))
    {
      $name_array = splitstring ($mgmt_config['homeboxes']);

      if (is_array ($name_array) && sizeof ($name_array) > 0)
      {
        foreach ($name_array as $name)
        {
          // individual home boxes (publication/name)
          if (strpos ($name, "/") > 0) $result[$name] = str_replace ("_", " ", $name);
          // system home boxes (name)
          else $result[$name] = ucfirst (str_replace ("_", " ", $name));
        }
      }
    }
  }

  return $result;
}

// =========================== CHAT ==================================

// ---------------------- getusersonline -----------------------------
// function: getusersonline()
// input: publication names [array] (optional)
// output: Array of online user names / false

function getusersonline ($sites=array())
{
  global $mgmt_config, $siteaccess;

  $session_dir = $mgmt_config['abs_path_data']."session/";

  if (is_dir ($session_dir) && $scandir = scandir ($session_dir))
  {
    // add slash if not present at the end
    if (substr ($session_dir, -1) != "/") $session_dir = $session_dir."/"; 

    $result = array();

    // collect online users
    foreach ($scandir as $user)
    {
      if (is_file ($session_dir.$user) && $user != "." && $user != ".." && strpos ($user, ".dat") > 0 && strpos ($user, "hyperdav_") === false && $user != "sys.dat")
      {
        // only users that have been logged in the past 8 hours are online users
        $now = time();
        $last_logon_time = filemtime ($session_dir.$user);
        $max = 8 * 60 * 60;

        if ($now - $last_logon_time < $max)
        {
          // check publication access
          if (sizeof ($sites) > 0)
          {
            $approved = false;
            $temp_data = loadfile_fast ($session_dir, $user);

            if ($temp_data)
            {
              $temp_data = trim ($temp_data);
              $temp_array = explode ("\n", $temp_data);

              if ($temp_array)
              {
                $temp = end  ($temp_array);

                if ($temp != "" && substr_count ($temp, "|") >= 4)
                {
                  list ($regsessionid, $regsessiontime, $regpasswd, $regchecksum, $site_access) = explode ("|", $temp);

                  if ($site_access)
                  {
                    foreach ($sites as $temp_site)
                    {
                      if (strpos ("_:".$site_access.":", ":".$temp_site.":") > 0) $approved = true;
                    }
                  }
                }
              }
            }
          }
          // no publication access filters
          else $approved = true;

          if ($approved == true) $result[] = substr ($user, 0, -4);
        }
      }
    }

    // chat support user
    if (!empty ($mgmt_config['chat_support']))
    {
      if (!in_array ($mgmt_config['chat_support'], $result)) $result[] = $mgmt_config['chat_support'];
    }

    if (sizeof ($result) > 0)
    {
      $result = array_unique ($result);
      $result_filtered = array();

      // filter users based on the publication access of the current user
      if (!empty ($siteaccess) && is_array ($siteaccess))
      {
        $users = getuserinformation ();

        foreach ($result as $logon)
        {
          foreach ($siteaccess as $site => $displayname)
          {
            if (!empty ($users[$site][$logon]))
            {
              // select a user attribute that identifies the user
              if (!empty ($users[$site][$logon]['realname'])) $result_filtered[$logon] = $users[$site][$logon]['realname'];
              elseif (!empty ($users[$site][$logon]['email'])) $result_filtered[$logon] = $users[$site][$logon]['email'];
              else $result_filtered[$logon] = $logon;
            }
          }
        }

        // reset result
        $result = $result_filtered;
      }

      return $result;
    }
  }

  return false;
}

// ------------------------------ getchatstate ----------------------------------
// function: getchatstate ()
// input: register stat in session [true/false] (optional)
// output: state of chat / false on error

function getchatstate ($register=true)
{
  global $mgmt_config;

  // chat log file
  $chat_log = $mgmt_config['abs_path_data']."log/chat.log";

  if (file_exists ($chat_log))
  {
    $lines = file ($chat_log);
    $state = count ($lines);

    // register chat state in session
    if ($register == true && $state >= 0) $_SESSION['hcms_temp_chatstate'] = $state;

    if ($state >= 0) return $state;
  }

  return false;
}

// ------------------------------ getimagelib ----------------------------------
// function: getimagelib ()
// input: %
// output: name of image library used [GD, ImageMagick] / false on error

function getimagelib ()
{
  global $mgmt_imagepreview;

  if (isset ($mgmt_imagepreview) && is_array ($mgmt_imagepreview) && sizeof ($mgmt_imagepreview) > 0)
  {
    // there should be only one entry the main config. if there are more tha last entry will be taken.
    foreach ($mgmt_imagepreview as $key=>$value)
    {
      if (strtoupper ($value) == "GD") $result = "GD";
      else $result = "ImageMagick";
    }

    if (!empty ($result)) return $result;
  }

  return false;
}

// ======================================== GET FILEPOINTER =====================================

// ------------------------------------------ getfilename ---------------------------------------
// function: getfilename()
// input: file content [string], hyperCMS tag name in page or component  [string]
// output: file name

// description:
// Extracts the file name of the content and template pointer tags of an object file

function getfilename ($filedata, $tagname)
{
  if ($filedata != "" && $tagname != "")
  {
    // define comment tag for file pointers (changed since version 4.1 to <!-- pointer -->)
    if (strpos (strtolower ($filedata), "!-- hypercms:") > 0) 
    {
      $ctagbegin = "<!-- ";
      $ctagend = " -->";
    }
    else 
    {
      $ctagbegin = "<!";
      $ctagend = ">";
    }

    // find first positions of hyperCMS tag
    if (strpos (strtolower ($filedata), "hypercms:".strtolower ($tagname)." file=\"") > 0)
    {
      $len = strlen ($ctagbegin."hypercms:".$tagname." file=\"");
      $namestart = strpos (strtolower ($filedata), $ctagbegin."hypercms:".strtolower ($tagname)." file=\"") + $len;
      $nameend = strpos ($filedata, "\"".$ctagend, $namestart);
    }
    elseif (strpos (strtolower ($filedata), "hypercms:".strtolower ($tagname)." file = \"") > 0)
    {
      $len = strlen ($ctagbegin."hypercms:".$tagname." file = \"");
      $namestart = strpos (strtolower ($filedata), $ctagbegin."hypercms:".strtolower ($tagname)." file = \"") + $len;
      $nameend = strpos ($filedata, "\"".$ctagend, $namestart);
    }
    else $namestart = 0;

    if ($namestart > 0)
    {
      // get file name
      $filename = trim (substr ($filedata, $namestart, $nameend - $namestart));

      return $filename;
    }
  }

  return false;
}

// ======================================== TAG HANDLING ============================================

// ----------------------------------------- gethypertag --------------------------------------------
// function: gethypertag()
// input: file content [string], full/partly hyperCMS tag name (with or without hyperCMS:) [string], offset position [integer] 
// output: full hyperCMS tag array [array]/false on error

// description:
// Finds the hyperCMS tag start and end position and returns an array of the whole tags including all information.
// Offset value must be integer value and is used to skip search for hyperCMS tag till offset position of filedata.

function gethypertag ($filedata, $tagname, $offset=0)
{
  if ($filedata != "" && $tagname != "")
  {
    // add freespace at the beginning of filedata
    $filedata = " ".$filedata;

    // define full hyperCMS tag name, if not done by request
    if (@substr_count (strtolower ($tagname), "hypercms:") == 0) $tagname = "[hyperCMS:".$tagname;
    elseif (@substr_count (strtolower ($tagname), "hypercms:") == 1 && @substr_count (strtolower ($tagname), "[hypercms:") == 0) $tagname = "[".$tagname;

    // define offset if not set
    if ($offset == "") $offset = 0;

    if (@substr_count (strtolower ($filedata), strtolower ($tagname)) >= 1)
    {
      $endpos = 1;

      while ($endpos > 0 && $endpos != false)
      {
        // find start and end position of hyperCMS tag
        $startpos = strpos ($filedata, $tagname, $offset);
 
        if ($startpos != false) $endpos = strpos ($filedata, "]", $startpos);
        else $endpos = false;

        // get hyperCMS tag into array
        if ($startpos != false && $endpos != false && $endpos > $startpos)
        {
          $buffer = substr ($filedata, $startpos, $endpos + 1 - $startpos);
          if ($buffer != "") $hypertag[$startpos] = $buffer;
        }

        // define new offset
        $offset = $endpos;
      }

      // return hyperCMS tag array (the array key is the start position of the tag)
      if (is_array ($hypertag)) return $hypertag;
    }
  }

  return false;
}

// ------------------------- gethypertagname ---------------------------
// function: gethypertagname()
// input: full hyperCMS tag [string]
// output: full hyperCMS tag name/false on error

// description:
// Reads the name of the hyperCMS tag

function gethypertagname ($tagdata)
{
  if ($tagdata != "")
  {
    $namestart = strpos ($tagdata, "hyperCMS:") + strlen ("hyperCMS:");
 
    if ($namestart != false) 
    {
      $nameend = strpos ($tagdata, " ");
      if ($nameend == false) $nameend = strpos ($tagdata, "]");
    }
    else $nameend = false;
 
    if ($namestart != false && $nameend != false && ($nameend > $namestart)) 
    $hypertagname = substr ($tagdata, $namestart, $nameend - $namestart);
 
    if ($hypertagname != false && $hypertagname != "") return $hypertagname;
  }

  return false;
}

// ------------------------ gethtmltag ------------------------------
// function: gethtmltag()
// input: file content [string], full hyperCMS tag (or other identifier) [string]
// output: full html tag/false on error

// description:
// Finds the first html tag start and end position of a nested hyperCMS tag and returns the whole tag including all information.
// Works also if other script tags are nested in the HTML-tag.
// This function is not case sensitive!

function gethtmltag ($filedata, $tag)
{
  if ($filedata != "" && $tag != "")
  {
    $filedata = "__".$filedata."__";
    $filedata_lower = strtolower ($filedata);
    $tag_lower = strtolower ($tag);
    $abslen = strlen ($filedata_lower);

    if (@substr_count ($filedata_lower, $tag_lower) > 0)
    {
      // find positions of hyperCMS tag
      $cmstagstart = strpos ($filedata_lower, $tag_lower);

      if ($cmstagstart > 0)
      {
        // find first position of HTML tag, direction: <-
        $pos = $cmstagstart + 1; // go one digit -> to include < in $tag

        if ($pos > 0)
        {
          $found = 0; // position of the start html tag
          $intag = 0; // is there a nested script tag in the html tag
          $tags = 0; // we allow max. 2 nested script tags to appear before we break the loop
 
          while ($pos > 0 && $tags < 3)
          {
            if (!empty ($filedata_lower[$pos]))
            {
              // found HTML tag
              if ($filedata_lower[$pos] == "<" && $intag == 0)
              {
                $found = $pos;
                break;
              }
              // if some script tag <...> is nested in an HTML tag
              elseif ($filedata_lower[$pos] == ">" && $intag == 0)
              {
                $intag++;
                $tags++;
              }
              elseif ($filedata_lower[$pos] == "<" && $intag > 0)
              {
                $intag--;
              }
            }

            $pos--;
          }

          if ($found > 0) $tagstart = $found;
          else $tagstart = $cmstagstart;
        }
        else $tagstart = $cmstagstart;

        // define end positions of hyperCMS tag
        $cmstagend = $cmstagstart + strlen ($tag_lower);

        // find last positions of HTML tag, direction: ->
        $pos = $cmstagend - 1; // go one digit <- to include > in $tag

        if ($pos > 0 && $pos < $abslen)
        {
          $found = 0; // position of the start html tag
          $intag = 0; // is there a nested script tag in the html tag
          $tags = 0; // we allow max. 2 nested script tags to appear before we break the routine

          while ($pos <= $abslen && $tags < 3)
          {
            if (!empty ($filedata_lower[$pos]))
            {
              // found HTML tag
              if ($filedata_lower[$pos] == ">" && $intag == 0)
              {
                $found = $pos;
                break;
              }
              // if some script tag <...> is nested in an HTML tag
              elseif ($filedata_lower[$pos] == "<" && $intag == 0)
              {
                $intag++;
                $tags++;
              }
              elseif ($filedata_lower[$pos] == ">" && $intag > 0)
              {
                $intag--;
              }
            }

            $pos++;
          }

          if ($found > 0 && $found < $abslen) $tagend = $found;
          else $tagend = $cmstagend; 
        }
        else $tagend = $cmstagend;
 
        if ($tagstart > 0 && $tagend < $abslen)
        {
          // get full HTML tag
          $htmltag = substr ($filedata, $tagstart, $tagend + 1 - $tagstart);

          if (strlen ($htmltag) < strlen ($tag)) return $tag;
          else return $htmltag;
        }
      }
    }
  }

  return false;
}

// ------------------------ gethtmltags ------------------------------
// function: gethtmltags()
// input: file content [string], full hyperCMS tag or other identifier in html tag [string]
// output: string from html tag start to end tag/false on error

// description:
// Finds the nearest html tag start and end position of a nested hyperCMS tag and returns the whole tag including all information.
// This functions works also for html-tag pairs like <a href></a>, <div></div> and so on.

function gethtmltags ($filedata, $tag)
{
  if ($filedata != "" && $tag != "")
  {
    $filedata = "__".$filedata."__";
    $filedata_lower = strtolower ($filedata);
    $tag_lower = strtolower ($tag);
    $abslen = strlen ($filedata_lower);

    if (@substr_count ($filedata_lower, $tag_lower) > 0)
    {
      // find positions of hyperCMS tag
      $cmstagstart = strpos ($filedata_lower, $tag_lower);

      if ($cmstagstart > 0)
      {
        // find first position of HTML tag, direction: <-
        $pos = $cmstagstart + 1; // go one digit -> to include < in $tag

        if ($pos > 0)
        {
          $found = 0; // position of the start html tag
          $intag = 0; // is there a nested script tag in the html tag
          $tags = 0; // we allow max. 1 nested script tags to appear before we break the routine
 
          while ($pos > 0 && $tags < 2)
          {
            // found HTML tag
            if ($filedata_lower[$pos] == "<" && $intag == 0)
            {
              $found = $pos;
              break;
            }
            // if some script tag <...> is nested in an HTML tag
            elseif ($filedata_lower[$pos] == ">" && $intag == 0)
            {
              $intag++;
              $tags++;
            }
            elseif ($filedata_lower[$pos] == "<" && $intag > 0)
            {
              $found = $pos;
              $intag--;
              break;
            }

            $pos--;
          }

          if ($found > 0) $tagstart = $found;
          else $tagstart = $cmstagstart;
        }
        else $tagstart = $cmstagstart;

        // define end positions of hyperCMS tag
        $cmstagend = $cmstagstart + strlen ($tag_lower); 

        // find last positions of HTML tag, direction: ->
        $pos = $cmstagend - 1; // go one digit <- to include > in $tag

        if ($pos > 0 && $pos < $abslen)
        {
          $found = 0; // position of the start html tag
          $intag = 0; // is there a nested script tag in the html tag
          $tags = 0; // we allow max. 2 nested script tags to appear before we break the routine

          while ($pos < $abslen && $tags < 2)
          {
            // found HTML tag
            if ($filedata_lower[$pos] == ">" && $intag == 0)
            {
              $found = $pos;
              break;
            }
            // if some script tag <...> is nested in an HTML tag
            elseif ($filedata_lower[$pos] == "<" && $intag == 0)
            {
              $intag++;
              $tags++;
            }
            elseif ($filedata_lower[$pos] == ">" && $intag > 0)
            {
              $found = $pos;
              $intag--;
              break;
            }

            $pos++;
          }

          if ($found > 0 && $found < $abslen) $tagend = $found;
          else $tagend = $cmstagend;
        }
 
        if ($tagstart > 0 && $tagend < $abslen)
        {
          // get full HTML tag
          $htmltag = substr ($filedata, $tagstart, $tagend + 1 - $tagstart);

          // extract html tag name
          $htmltagname = substr ($htmltag, $tagstart + 1, strpos ($htmltag, " ") - $tagstart - 1);
          $htmlendtag = "</".$htmltagname.">";

          // search for end tag 
          $tagend_new = strpos ($filedata, $htmlendtag, $tagend) + strlen ($htmlendtag); 

          if ($tagend_new > 0 && $tagend_new > $tagend) $htmltag = substr ($filedata, $tagstart, $tagend_new - $tagstart); 

          if (strlen ($htmltag) < strlen ($tag)) return $tag;
          else return $htmltag;
        }
      }
    }
  }

  return false;
}

// ------------------------- getattribute --------------------------------
// function: getattribute()
// input: string including attributes [string], attribute name [string], secure attribute value reg. XSS [boolean] (optional)
// output: attribute value/false on error

// description:
// Get the value of a certain attribute out of a string (...attributname=value....)

function getattribute ($string, $attribute, $secure=true)
{
  if ($string != "" && $attribute != "")
  {
    // URL based query string (GET)
    if (substr_count ("_".strip_tags ($string), ".php?") > 0)
    {
      $string = substr ($string, strpos ($string, ".php?") + 5);
      $string = html_decode ($string);
      parse_str ($string, $result);

      if (!empty ($result[$attribute]))
      {
        $value = $result[$attribute];

        // secure value
        if (!empty ($secure))
        {
          $value = strip_tags ($value);
          $value = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $value);
        } 
      }
      else $value = ""; 

      return $value;
    }
    // html/xml based attribute
    else
    {
      $string = html_decode ($string);

      // remove freespaces
      $freespace_array = array();
      $freespace_array[0] = "   ";
      $freespace_array[1] = "  ";
      $freespace_array[2] = " ";

      foreach ($freespace_array as $freespace)
      {
        if (@substr_count (strtolower ($string), strtolower ($attribute).$freespace."=") > 0)
        {
          $string = str_replace ($attribute.$freespace."=", $attribute."=", $string);
        }
      }

      if (@substr_count (strtolower ($string), strtolower ($attribute)."=") > 0)
      {
        // get length of attribute name and add 1 for '='
        $attrlen = strlen ($attribute) + 1;

        while (@substr_count (strtolower ($string), strtolower ($attribute)."=") > 0)
        {
          // check if found attribute is not part of the name of another attribute
          $attr_seperator = substr ($string, strpos (strtolower ($string), strtolower ($attribute)."=") - 1, 1);
 
          if (strpos (strtolower ($string), strtolower ($attribute)."=") == 0 || $attr_seperator == "?" || $attr_seperator == "&" || $attr_seperator == " ")
          {
            // leave string as it is and exit loop
            $checkedstring = $string;
            break;
          }
          else
          {
            // cut off the first wrong occurence of the attribute name
            $string = substr (strstr ($string, $attribute."="), $attrlen);
          }
        }

        if (!empty ($checkedstring))
        {
          // cut off first part of the string till attribute value begins
          $checkedstring = substr (strstr ($checkedstring, $attribute."="), $attrlen);

          if (!empty ($checkedstring))
          {
            // " indicates start and end of attribute value
            if (substr ($checkedstring, 0, 1) == "\"" && strpos ("_".substr ($checkedstring, 1), "\"") > 0)
            {
              // get the length of the value
              $vallen = strpos (substr ($checkedstring, 1), "\"") + 2;
            }
            // ' indicates start and end of attribute value
            elseif ($checkedstring[0] == "'" && strpos ("_".substr ($checkedstring, 1), "'") > 0)
            {
              // get the length of the value
              $vallen = strpos (substr ($checkedstring, 1), "'") + 2;
            }
            //  '&' indicates end of attribute value
            elseif (strpos ($checkedstring, "&") > 0)
            {
              // get the length of the value
              $vallen = strpos ($checkedstring, "&");
            }
            // in case ot hyperCMS tags an end parenthesis indicates also end of attribute value
            elseif (strpos ($checkedstring, "]") > 0)
            {
              // get the length of the value
              $vallen = strpos ($checkedstring, "]");
            }
            // freespace indicates also end of attribute value
            elseif (strpos ($checkedstring, " ") > 0)
            {
              // get the length of the value
              $vallen = strpos ($checkedstring, " ");
            }
            // " indicates end of attribute value
            elseif (strpos ($checkedstring, "\"") > 0)
            {
              // get the length of the value
              $vallen = strpos ($checkedstring, "\"");
            }
            // ' indicates end of attribute value
            elseif (strpos ($checkedstring, "'") > 0)
            {
              // get the length of the value
              $vallen = strpos ($checkedstring, "'");
            }
            // or the value itself is the end of the string
            else
            {
              $vallen = strlen ($checkedstring);
            }

            // get the value of attribute
            $value = trim (substr ($checkedstring, 0, $vallen));

            // cut off double quotes from value
            if ((substr ($value, 0, 1) == "'" && substr ($value, strlen ($value)-1, 1) == "'") || (substr ($value, 0, 1) == "\"" && substr ($value, strlen ($value)-1, 1) == "\""))
            {
              $value = substr ($value, 1, strlen ($value)-2);
            }
          }
          else $value = "";

          // secure value
          if (!empty ($secure))
          {
            $value = strip_tags ($value);
            $value = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $value);
          }

          return $value;
        }
      }
    }
  }

  return false;
}

// ----------------------------- getoption --------------------------------
// function: getoption()
// input: string including options [string], option name [string]
// output: option value / false on error

// description:
// Get the value of a certain option out of a string (-c:v value -ar 44100)

function getoption ($string, $option)
{
  if ($string != "" && $option != "")
  {
    $option = trim ($option)." ";

    if (strpos ("_".$string, $option) > 0)
    {
      // extract value of option
      $temp = substr ($string, strpos ($string, $option) + strlen ($option));
      $value = substr ($temp, 0, strpos ($temp, " "));
      if ($value == "" || $value == false) $value = substr ($temp, 0);

      // remove " and '
      $value = str_replace (array ("\"", "'"), array ("", ""), $value);

      return $value;
    }
  }

  return false;
}

// ----------------------------- getthumbnailsize --------------------------------
// function: getthumbnailsize()
// input: %
// output: result array including the width and height for thumbnail images

// description:
// Returns the size to be used for thumbnail images defined by the main configuration.

function getthumbnailsize ()
{
  global $mgmt_config, $mgmt_imageoptions;

  // default values if setting is missing the main configuration
  $result = array();
  $result['width'] = 380;
  $result['height'] = 220;

  // if Image conversion software is provided
  if (is_array ($mgmt_imageoptions) && sizeof ($mgmt_imageoptions) > 0)
  {
    reset ($mgmt_imageoptions);

    // supported extensions for image rendering
    foreach ($mgmt_imageoptions as $imageoptions_ext => $imageoptions)
    {
      // check file extension
      if (substr_count (strtolower ($imageoptions_ext).".", ".jpg.") > 0 && !empty ($imageoptions['thumbnail']))
      {
        if (strpos ("_".$imageoptions['thumbnail'], "-s ") > 0)
        {
          $imagesize = getoption ($imageoptions['thumbnail'], "-s");

          if (!empty ($imagesize) && strpos ($imagesize, "x") > 0)
          {
            list ($imagewidth, $imageheight) = explode ("x", $imagesize);

            if (intval ($imagewidth) > 0 && intval ($imageheight) > 0)
            {
              $result['width'] = intval ($imagewidth);
              $result['height'] = intval ($imageheight);
            }
          }
        }
      }
    }
  }

  return $result;
}

// ------------------------------ getcharset ----------------------------------
// function: getcharset()
// input: publication name [string], data from template or content container [string]
// output: array with content-type and charset / false on error
// requires: config.inc.php

// description:
// Extract the content-type definition and the character set from the template (1st priority), content container (2nd priority) or publication settings (3rd priority)

function getcharset ($site, $data)
{
  global $mgmt_config;

  if ($data != "")
  {
    $charset = false;
    $contenttype = false;

    // if HTML page and no pagecontentype can be defined by the editor
    if (strpos (strtolower ($data), "pagecontenttype") == 0)
    {
      // meta tag charset (HTML5)
      if (strpos (strtolower ($data), " charset=") > 0)
      {
        // get tag defined by the value of attribute charset=""
        $contenttypetag = gethtmltag (strtolower ($data), "charset");

        if ($contenttypetag != false)
        {
          $charset = getattribute ($contenttypetag, "charset");
          if (!empty ($charset)) $contenttype = "text/html; charset=".$charset;
        }
      }
      // meta tag http-equiv
      elseif (strpos (strtolower ($data), " http-equiv=") > 0 && strpos (strtolower ($data), "content-type") > 0)
      {
        // get tag defined by the value of attribute http-equiv="content-type"
        $contenttypetag = gethtmltag (strtolower ($data), "content-type");

        if ($contenttypetag != false)
        {
          $start = strpos ($contenttypetag, "content=") + strlen ("content=");

          if (substr_count (substr ($contenttypetag, $start), "\"") > 0) $quotes = "\"";
          elseif (substr_count (substr ($contenttypetag, $start), "'") > 0) $quotes = "'";

          $end = strrpos ($contenttypetag, $quotes);
          $length = $end - $start;
          $contenttype = trim (substr ($contenttypetag, $start, $length));
          $contenttype = str_replace ($quotes, "", $contenttype);

          if (strpos ($contenttype, "charset") > 0) $charset = getattribute ($contenttype, "charset");
          else $charset = trim ($contenttype);
        }
      }
    } 
 
    // if hypertag is used to set the character set (e.g. components)
    if (empty ($contenttype) && strpos (strtolower ($data), "compcontenttype") > 0)
    {
      // get content-type from component template, if set
      $hypertag_array = gethypertag ($data, "compcontenttype", 0);

      if ($hypertag_array != false)
      {
        foreach ($hypertag_array as $hypertag)
        {
          $contenttype = getattribute ($hypertag, "content");
          if ($contenttype != false) break;
        }
      }
      else $contenttype = false;

      if ($contenttype != false && $contenttype != "")
      {
        $contenttype = trim ($contenttype);

        if (strpos ($contenttype, "charset") > 0) $charset = getattribute ($contenttype, "charset");
        else $charset = trim ($contenttype);
      }
      else 
      {
        $charset = false;
        $contenttype = false;
      }
    }

    // if XML page of from content container encoding
    if (empty ($contenttype) && @substr_count (strtolower ($data), " encoding=") > 0)
    {
      $xml_encoding = gethtmltag ($data, "?xml");

      if ($xml_encoding != false) 
      {
        $charset = getattribute ($xml_encoding, "encoding");

        if ($charset != "" && @substr_count (strtolower ($charset), "[hypercms:") == 0)
        {
          $contenttype = "text/html; charset=".$charset;
        }
        else 
        {
          $charset = false;
          $contenttype = false;
        }
      }
      else 
      {
        $charset = false;
        $contenttype = false;
      }
    }

    // if head information in container is set
    if (empty ($contenttype) && @substr_count (strtolower ($data), "<pagecontenttype>") > 0)
    {
      $contenttype_array = getcontent ($data, "<pagecontenttype>");

      if ($contenttype_array != false)
      {
        $contenttype = $contenttype_array[0];
        $charset = getattribute ($contenttype, "charset");

        if ($charset == false || $charset == "")
        {
          $charset = false;
          $contenttype = false;
        }
      }
      else 
      {
        $charset = false;
        $contenttype = false;
      }
    }

    // if content-type is given
    if (empty ($contenttype) && @substr_count (strtolower ($data), "charset") > 0)
    {
      $charset = getattribute ($data, "charset");

      if ($charset != "" && strlen ($charset) < 20)
      {
        if (strlen ($data) < 40 && @substr_count (strtolower ($data), "text/") > 0) $contenttype = $data;
        else $contenttype = "text/html; charset=".$charset;
      }
      else
      {
        $charset = false;
        $contenttype = false;
      }
    }

    // use publication settings if no character set could be found in $data
    if ((empty ($contenttype) || empty ($charset) || strlen ($charset) > 20) && valid_publicationname ($site))
    {
      $contenttype = "text/html; charset=".$mgmt_config[$site]['default_codepage'];
      $charset = $mgmt_config[$site]['default_codepage'];
    }

    // return result
    if (!empty ($contenttype)) $result['contenttype'] = $contenttype;
    if (!empty ($charset)) $result['charset'] = $charset;

    if (is_array ($result)) return $result;
  }
  // use publication settings
  elseif (valid_publicationname ($site))
  {
    $result['contenttype'] = "text/html; charset=".$mgmt_config[$site]['default_codepage'];
    $result['charset'] = $mgmt_config[$site]['default_codepage'];

    if (!empty ($result) && is_array ($result)) return $result;
  }

  return false;
}

// ------------------------------ getartid ----------------------------------
// function: getartid()
// input: string including id [string]
// output: article id/false on error

// description:
// Extract the article ID from the tag ID

function getartid ($id)
{
  if ($id != "")
  {
    if (@substr_count ($id, ":") == 1)
    {
      $artid = substr ($id, 0, strpos ($id, ":"));

      // cut off the quote if there is any at the beginning
      if ($artid[0] == "'") $artid = substr ($artid, 1);
    }
    else $artid = false;

    return $artid;
  }
  else return false;
}

// ------------------------------ getelementid ----------------------------------
// function: getelementid()
// input: string including id [string]
// output: element id/false on error

// description:
// Extract the element ID from the tag ID

function getelementid ($id)
{
  if ($id != "")
  {
    if (@substr_count ($id, ":") == 1)
    {
      $elementid = substr ($id, strpos ($id, ":") + 1);

      // cut off the quote if there is any at the beginning
      if ($elementid != "" && $elementid[strlen($elementid) - 1] == "'") $elementid = substr ($elementid, 0, strlen($elementid) - 1);
    }
    else $elementid = false;

    return $elementid;
  }
  else return false;
}

// ------------------------------ getfirstkey ----------------------------------
// function: getfirstkey()
// input: array [array]
// output: array key of first element in array if $value is not empty / false on error

function getfirstkey ($array)
{
  if (is_array ($array))
  {
    reset ($array);

    foreach ($array as $key => $value)
    {
      if ($key != "" && $value != "") return $key;
    }
  }
  else return false;
}

// ------------------------------ getdirectoryfiles ----------------------------------
// function: getdirectoryfiles()
// input: path to directory [string], pattern as string [string] (optional)
// output: sorted array of all files matching the pattern / false on error

function getdirectoryfiles ($dir, $pattern="")
{
  if (is_dir ($dir))
  {
    $scandir = scandir ($dir);
    $item_files = array();

    if ($scandir)
    {
      foreach ($scandir as $entry)
      {
        if ($entry != "." && $entry != ".." && is_file ($dir.$entry))
        {
          if ($pattern != "" && strpos ("_".$entry, $pattern) > 0) $item_files[] = $entry;
          else $item_files[] = $entry;
        }
      }

      if (sizeof ($item_files) > 0)
      {
         natcasesort ($item_files);
         reset ($item_files);
      }

      return $item_files;
    }
  }

  return false;
}

// ---------------------------------------------- getuserinformation ----------------------------------------------
// function: getuserinformation()
// input: user name [string] (optional), include permissions for each group [boolean] (optional)
// output: assoziative array with basic user information [publication->username->attribute] / false
// requires: config.inc.php

// description:
// This function creates an assoziative array with user information, e.g. for a user select box.

function getuserinformation ($login="", $include_permissions=false)
{
  global $mgmt_config, $user;

  // load user file
  $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");

  $user_array = array();

  if ($userdata != "")
  {
    // get publications
    $inherit_db = inherit_db_read ();
    $site_array = array();

    if ($inherit_db != false && sizeof ($inherit_db) > 0)
    {
      foreach ($inherit_db as $inherit_db_record)
      {
        if ($inherit_db_record['parent'] != "")
        {
          $site_array[] = $inherit_db_record['parent'];
        }
      }
    }

    // get user node and extract required information
    if ($login != "") $usernode = selectcontent ($userdata, "<user>", "<login>", $login);
    else $usernode = getcontent ($userdata, "<user>");

    if (is_array ($usernode))
    {
      foreach ($usernode as $temp)
      {
        if ($temp != "")
        {
          $login = getcontent ($temp, "<login>");
          if ($user == "sys") $hashcode = getcontent ($temp, "<hashcode>");
          $admin = getcontent ($temp, "<admin>");
          $nologon = getcontent ($temp, "<nologon>");
          $email = getcontent ($temp, "<email>");
          $realname = getcontent ($temp, "<realname>");
          $signature = getcontent ($temp, "<signature>");
          $language = getcontent ($temp, "<language>");
          $publication_array = getcontent ($temp, "<publication>");
          $usergroup_array = getcontent ($temp, "<usergroup>");

          if (is_array ($publication_array))
          {
            foreach ($publication_array as $key=>$pub_temp)
            {
              if (!empty ($pub_temp) && !empty ($usergroup_array[$key])) $usergroup[$pub_temp] = $usergroup_array[$key];
            }
          }

          // standard user
          if (!empty ($login[0]) && (empty ($admin[0]) || $admin[0] == 0) && is_array ($publication_array))
          {
            foreach ($publication_array as $pub_temp)
            {
              if ($pub_temp != "")
              {
                $username = $login[0];
                if (!empty ($hashcode[0])) $user_array[$pub_temp][$username]['hashcode'] = $hashcode[0];
                if (!empty ($nologon[0])) $user_array[$pub_temp][$username]['nologon'] = $nologon[0];
                else $user_array[$pub_temp][$username]['nologon'] = 0;
                $user_array[$pub_temp][$username]['email'] = $email[0];
                $user_array[$pub_temp][$username]['realname'] = $realname[0];
                $user_array[$pub_temp][$username]['signature'] = $signature[0];
                $user_array[$pub_temp][$username]['language'] = $language[0];
                if (!empty ($usergroup[$pub_temp])) $user_array[$pub_temp][$username]['usergroup'] = trim ($usergroup[$pub_temp]);
                else $user_array[$pub_temp][$username]['usergroup'] = "";

                // include permissions
                if ($include_permissions == true && !empty ($user_array[$pub_temp][$username]['usergroup']))
                {
                  $usergroup_array = link_db_getobject ($user_array[$pub_temp][$username]['usergroup']);

                  if (is_array ($usergroup_array) && sizeof ($usergroup_array) > 0)
                  {
                    foreach ($usergroup_array as $usergroup_temp)
                    {
                      $user_array[$pub_temp][$username]['permissions'][$usergroup_temp] = getgroupinformation ($pub_temp, $usergroup_temp);
                    }
                  }
                }
              }
            }
          }
          // super user
          elseif (!empty ($login[0]) && !empty ($admin[0]) && $admin[0] == 1)
          {
            foreach ($site_array as $pub_temp)
            {
              if ($pub_temp != "")
              {
                $username = $login[0];
                if (!empty ($hashcode[0])) $user_array[$pub_temp][$username]['hashcode'] = $hashcode[0];
                if (!empty ($nologon[0])) $user_array[$pub_temp][$username]['nologon'] = $nologon[0];
                else $user_array[$pub_temp][$username]['nologon'] = 0;
                $user_array[$pub_temp][$username]['email'] = $email[0];
                $user_array[$pub_temp][$username]['realname'] = $realname[0];
                $user_array[$pub_temp][$username]['signature'] = $signature[0];
                $user_array[$pub_temp][$username]['language'] = $language[0];
                if (!empty ($usergroup[$pub_temp])) $user_array[$pub_temp][$username]['usergroup'] = trim ($usergroup[$pub_temp]);
                else $user_array[$pub_temp][$username]['usergroup'] = "";

                // include permissions
                if ($include_permissions == true && !empty ($user_array[$pub_temp][$username]['usergroup']))
                {
                  $usergroup_array = link_db_getobject ($user_array[$pub_temp][$username]['usergroup']);

                  if (is_array ($usergroup_array) && sizeof ($usergroup_array) > 0)
                  {
                    foreach ($usergroup_array as $usergroup_temp)
                    {
                      $user_array[$pub_temp][$username]['permissions'][$usergroup_temp] = getgroupinformation ($pub_temp, $usergroup_temp);
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }

  if (!empty ($user_array) && is_array ($user_array)) return $user_array;
  else return false;
}

// ---------------------------------------------- getgroupinformation ----------------------------------------------
// function: getgroupinformation()
// input: publication name [string], user group name [string]
// output: assoziative array with the user group information [permission->value] / false
// requires: config.inc.php

// description:
// This function creates an assoziative array with the group information and permissions

function getgroupinformation ($site, $usergroup)
{
  global $mgmt_config;

  if (valid_publicationname ($site) && valid_objectname ($usergroup))
  {
    // initialize
    $result = array();

    // load config if site_admin is not set
    require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

    // load usergroup information
    $usergroupdata = loadfile ($mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");

    if (!empty ($usergroupdata))
    {
      // get usergroup information
      $usergroupnode = selectcontent ($usergroupdata, "<usergroup>", "<groupname>", $usergroup);

      if (!empty ($usergroupnode[0]))
      {
        // permission string
        $permission_str = array();
        $result['globalpermission'] = array();
        $result['globalpermission'] = array();
        $result['localpermission'] = array();

        $userpermission = getcontent ($usergroupnode[0], "<permission>");

        if (!empty ($userpermission[0])) $permission_str[$site][$usergroup] = trim ($userpermission[0]);
        else $permission_str[$site][$usergroup] = "";

        if (isset ($permission_str))
        {
          // deseralize the permission string and define root, global and local permissions
          $result['rootpermission'] = rootpermission ($site, $mgmt_config[$site]['site_admin'], $permission_str);

          $temp = globalpermission ($site, $permission_str);
          if (!empty ($temp[$site])) $result['globalpermission'] = $temp[$site];

          $temp = localpermission ($site, $permission_str);
          if (!empty ($temp[$site][$usergroup])) $result['localpermission'] = $temp[$site][$usergroup];
        }

        // page accsess
        $result['pageaccess'] = array();

        $userpageaccess = getcontent ($usergroupnode[0], "<pageaccess>");

        if (!empty ($userpageaccess[0]))
        {
          // versions before 5.6.3 used folder path instead of object id
          if (substr_count ($userpageaccess[0], "/") == 0)
          {
            $temp_array = explode ("|", $userpageaccess[0]);

            if (is_array ($temp_array))
            {
              $folder_path = array();

              foreach ($temp_array as $temp)
              {
                if ($temp != "")
                {
                  $temp_path = rdbms_getobject ($temp);
                  if ($temp_path != "") $result['pageaccess'][] = getlocation ($temp_path);
                }
              }
            }
          }
          else $result['pageaccess'][] = $userpageaccess[0];
        }

        // component access
        $result['compaccess'] = array();

        $usercompaccess = getcontent ($usergroupnode[0], "<compaccess>");

        if (!empty ($usercompaccess[0]))
        {
          // versions before 5.6.3 used folder path instead of object id
          if (substr_count ($usercompaccess[0], "/") == 0)
          {
            $temp_array = explode ("|", $usercompaccess[0]);

            if (is_array ($temp_array))
            {
              $folder_path = "";

              foreach ($temp_array as $temp)
              {
                if ($temp != "")
                {
                  $temp_path = rdbms_getobject ($temp);
                  if ($temp_path != "") $result['compaccess'][] = getlocation ($temp_path);
                }
              }
            }
          }
          else $result['compaccess'][]= $usercompaccess[0];
        }

        // plugin access
        $userpluginccess = getcontent ($usergroupnode[0], "<plugins>");

        if (!empty ($userpluginccess[0]))
        {
          $result['pluginaccess'] = link_db_getobject ($userpluginccess[0]);
        }

        return $result;
      }
    }
  }
  
  return false;
}

// ---------------------------------------------- getCSS ----------------------------------------------
// function: getCSS()
// input: CSS file path [string], provide a string to be removed values [array] (optional)
// output: assoziative array with the class and element names and properties as keys [class->property->value] / false on error
// requires: config.inc.php

// description:
// This function collects the CSS classes and their properties.

function getCSS ($file, $clean=array("!important"))
{
  if (is_file ($file))
  {
    // load CSS file
    $css = file_get_contents ($file);

    if (!empty ($css))
    {
      // master array to hold all values
      $css_array = array();
      $element = explode ("}", $css);

      foreach ($element as $element)
      {
        // get the class or element name of the CSS element
        $a_name = explode ("{", $element);
        $name = $a_name[0];

        // get all the key:value pair styles
        $a_styles = explode (';', $element);

        // remove element name from first property element
        $a_styles[0] = str_replace ($name."{", "", $a_styles[0]);

        // loop through each style and split apart the key from the value
        $count = count ($a_styles);

        for ($a = 0; $a < $count; $a++)
        {
          if (!empty ($a_styles[$a]) && strpos ($a_styles[$a], ":") > 0)
          {
            $a_key_value = explode (":", $a_styles[$a]);

            // clean value
            if (is_array ($clean) && sizeof ($clean) > 0)
            {
              foreach ($clean as $temp)
              {
                $a_key_value[1] = str_replace ($temp, "", $a_key_value[1]);
              }
            }

            // build the master css array
            $css_array[trim ($name)][trim ($a_key_value[0])] = trim ($a_key_value[1]);
          }
        }               
      }

      return $css_array;
    }
  }

  return false;
}
?>