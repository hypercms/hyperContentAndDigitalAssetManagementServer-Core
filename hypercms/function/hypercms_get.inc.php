<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// ======================================== SERVER PARAMETERS ===========================================

// ---------------------- getserverload -----------------------------
// function: getserverload()
// input: %
// output: Returns the average system load (the number of processes in the system run queue) over the last minute and the number of CPU cores as array

function getserverload ()
{
  $cpu_num = 2;
  $load_total = 0;
    
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
      
      $load = round ($load_total / $cpu_num);
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
     
      $cmd = "wmic cpu get loadpercentage /all";
      @exec ($cmd, $output);

      if ($output)
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
      
      $load = round ($load_total / $cpu_num);
    }
  }
  // for UNIX
  elseif (function_exists ('sys_getloadavg'))
  {
    $sys_load = sys_getloadavg ();
    $load = $sys_load[0];
    
    exec ("cat /proc/cpuinfo | grep processor | wc -l", $processors);
    $cpu_num = $processors[0];
  }
 
  $result = array();
  $result['load'] = $load;
  $result['cpu'] = $cpu_num;
  
  return $result;
}

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
 
// =========================================== REQUESTS AND SESSION ==============================================
 
// ------------------------- getsession -----------------------------
// function: getsession()
// input: session variable name, default session value (optional)
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
// input: request variable name, must be of certain type [numeric,array,publicationname,locationname,objectname,url,bool] (optional), default value (optional)
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
      elseif ($force_type == "array" && !is_array ($result)) $result = $default;
      elseif ($force_type == "publicationname" && !valid_publicationname ($result)) $result = $default;
      elseif ($force_type == "locationname" && !valid_locationname ($result)) $result = $default;
      elseif ($force_type == "objectname" && !valid_objectname ($result)) $result = $default;
      elseif ($force_type == "url" && strpos ("_".strtolower (urldecode ($result)), "<script") > 0) $result = $default;
      elseif ($force_type == "bool") 
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
// input: request variable name, must be of certain type [numeric,array,publicationname,locationname,objectname] (optional), default value (optional), 
//        remove characters to avoid JS injection [true,false] (optional)
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

// ----------------------------------------- getuserip ------------------------------------------
// function: getuserip()
// input: %
// output: IP address of client / false on error

// description:
// Retrieves the IP address of the client/user.

function getuserip ()
{
  if (!isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $client_ip = $_SERVER['REMOTE_ADDR'];
  else $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  
  if ($client_ip != "") return $client_ip;
  else return false;
}

// ----------------------------------------- getobjectlistcells ------------------------------------------
// function: getobjectlistcells()
// input: width of viewport or window in pixels, is mobile device [0,1] (optional)
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
      else return false;
    }
    else return false;
  }
  else return false;
}

// ----------------------------------------- getlanguagefile ------------------------------------------
// function: getlanguagefile()
// input: language code (optional)
// output: language file name

function getlanguagefile ($lang="en")
{
  global $mgmt_config;
  
  if ($lang != "" && $mgmt_config['abs_path_cms'] != "")
  {
    if (is_file ($mgmt_config['abs_path_cms']."language/".$lang.".inc.php")) return $lang.".inc.php";
    else return "en.inc.php";
  }
  else return "en.inc.php";
}

// ----------------------------------------- getcodepage ------------------------------------------
// function: getcodepage()
// input: language code (optional)
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
    else return "UTF-8";
  }
  else return "UTF-8";
}

// ----------------------------------------- getcalendarlang ------------------------------------------
// function: getcalendarlang()
// input: language code (optional)
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
    else return "en";
  }
  else return "en";
}

// ----------------------------------------- getescapedtext ------------------------------------------
// function: getescapedtext()
// input: text as string, character set of text, 2-digit language code
// output: HTML escaped text

// description:
// If the destination character set is not supported by the language set of the presebtation, the text need to be HTML escaped.

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
  return html_encode ($text);
}

// ----------------------------------------- getsearchhistory ------------------------------------------
// function: getsearchhistory()
// input: user name (optional)
// output: array holding all expressions (in single quotes) of the search history of a user / false on error

function getsearchhistory ($user="")
{
  global $mgmt_config;

  if (is_file ($mgmt_config['abs_path_data']."log/search.log"))
  {
    // load search log
    $data = file ($mgmt_config['abs_path_data']."log/search.log");
  
    if (is_array ($data) && sizeof ($data) > 0)
    {
      $keywords = array();
      
      foreach ($data as $record)
      {
        if (substr_count ($record, "|") > 0)
        {
          list ($date, $searchuser, $keyword_add) = explode ("|", $record);
    
          if ($searchuser == $user || $user == "") $keywords[] = "'".str_replace ("'", "\\'", trim ($keyword_add))."'";
        }
      }
      
      // only unique expressions
      if (sizeof ($keywords) > 0) $keywords = array_unique ($keywords);
      
      return $keywords;
    }
    else return false;
  }
  else return false;
}

// ----------------------------------------- getsynonym ------------------------------------------
// function: getescapedtext()
// input: word as string, 2-digit language code (optional)
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
// input: publication name, language code (optional), taxonomy parent ID (optional)
// output: array holding all keywords of the next taxonomy level / false on error

// description:
// Returns sorted keywords of a taxonomy level (multilingual support based on taxonomies).
// Global variable $taxonomy can be used to pass the taxonomy as array.

function gettaxonomy_sublevel ($site, $lang="en", $tax_id="0")
{
  global $mgmt_config, $mgmt_lang_shortcut_default, $taxonomy;

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
    if (!empty ($taxonomy[$lang]))
    {
      // get childs
      $result = gettaxonomy_childs ($site, $lang, $tax_id, 1, true);

      // remove root element
      if (!empty ($result[$tax_id])) unset ($result[$tax_id]);

      // return sorted array
      natcasesort ($result);
      return $result;
    }
    else return false;
  }
  else return false;
}

// ----------------------------------------- gettaxonomy_childs ------------------------------------------
// function: gettaxonomy_childs()
// input: publication name (optional), language code (optional), 
//        taxonomy ID or expression or taxonomy path in the form %taxonomy%/publication-name or 'default'/language-code/taxonomy-ID/taxonomy-child-levels as string, 
//        taxonomy child levels as integer (optional), only return taxonomy IDs without language and keyword information [true,false] (optional)
// output: array holding all taxonomy IDs / false on error

// description:
// Returns keywords based on taxonomy defintion and synonyms if expression is a keyword (multilingual support based on taxonomies and synonyms).
// The expression can be a taxonomy path in the form of %taxonomy%/site/language-code/taxonomy-ID/taxonomy-child-levels (use "all" for all languages and "0" for all taxonomy-IDs on first level).
// Global variable $taxonomy can be used to pass the taxonomy as array.

function gettaxonomy_childs ($site="", $lang="", $expression, $childlevels=1, $id_only=true)
{
  global $mgmt_config, $mgmt_lang_shortcut_default, $taxonomy;

  if ($childlevels >= 0 && is_array ($mgmt_config))
  {
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
    
    $result = array();

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
      // verify language in taxonomy and set en as default
      if (!empty ($lang) && empty ($taxonomy[$lang])) $lang = $mgmt_lang_shortcut_default;

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
              $path_temp = substr ($path, 0, -1);
              $id = substr ($path_temp, strrpos ($path_temp, "/") + 1);
              
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
                $path_temp = substr ($path, 0, -1);
                $id = substr ($path_temp, strrpos ($path_temp, "/") + 1);
                
                if ($id_only == true) $result[$id] = $keyword;
                else $result[$tax_lang][$id] = $keyword;
              }
            }
            // look up expression and get taxonomy ID if keyword is included in expressions array
            elseif (!empty ($expression_array) && array_search (strtolower ($keyword), $expression_array) !== false)
            {
              // get ID
              $path_temp = substr ($path, 0, -1);
              $id = substr ($path_temp, strrpos ($path_temp, "/") + 1);

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

// ----------------------------------------- gethierarchy_defintion ------------------------------------------
// function: gethierarchy_defintion()
// input: publication name, hierarchy name (optional) 
// output: hierarchy array in form of array[name][level][text-id][language] = label / false on error

// description:
// Reads the metadata/content hierarchy defintion and returns a multidimensinal array.

function gethierarchy_defintion ($site, $selectname="")
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
// input: hierarchy URL in form  of %hierarchy%/publication-name/hierarchy-name/hierarchy-level-of-last-element/text-ID-1=value-1/text-ID-2=value-2/text-ID-3
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
      if (strpos ($last_text_id, "=") > 0)
      {
        $hierarchy = gethierarchy_defintion ($site, $name);
        
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
      elseif ($last_text_id != "")
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
// input: publication name (optional) 
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
// input: text as string, language to be used for stop word list [de,en,...] (optional), character set (optional) 
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
    if (is_array ($stopwords[$language])) $text = str_ireplace ($stopwords[$language]." ", "", $text);
    
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
// input: text as string
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
// input: publication anme, directory path, URL to directory, GET parameters to use for new versions of the URL as array (optional), permanent links text-ID to use for location as array (optional), 
//        frequency of google scrawler [never,weekly,daily] (optional), priority [1 or less] (optional), 
//        ignore file names as array (optional), allowed file types as array (optional), include frequenzy tag [true,false] (optional), include priority tag [true,false] (optional)
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
    if (substr ($dir, -1) != "/") $dir = $dir."/";
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
                  $textcontent = getcontent ($textnode[0], "<textcontent>");
      
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

// ---------------------- getlistelements -----------------------------
// function: getlistelements()
// input: content of file attribute of list of keyword tag, seperator of list elements as string (optional)
// output: string with list/keyword elements sperated by commas / false

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
      else $language = "en";

      // reset source file to service/getkeywords
      if (!empty ($publication) && !empty ($language) && isset ($taxonomy_id))
      {
        if ($taxonomy_id == "") $taxonomy_id = 0;

        $list_sourcefile = $mgmt_config['url_path_cms']."service/getkeywords.php?site=".url_encode($publication)."&lang=".url_encode($language)."&id=".url_encode($taxonomy_id)."&levels=".url_encode($taxonomy_levels);
      }
      else $list_sourcefile = "";
      
      // get keywords
      if (!empty ($list_sourcefile)) $list .= @file_get_contents ($list_sourcefile);
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
// input: location, object (both optional if container is given), container name/ID or container content (optional), 
//         seperator of meta data fields [any string,array] (optional), publication name/template name to extract label names (optional)
// output: string with all metadata from given object based on container / false

function getmetadata ($location, $object, $container="", $seperator="\n", $template="")
{
	global $mgmt_config;
  
  // deconvert location
  if (@substr_count ($location, "%page%") > 0 || @substr_count ($location, "%comp%") > 0)
  {
    $site = getpublication ($location);
    $cat = getcategory ($site, $location);
    $location = deconvertpath ($location, "file");
  }
      
  // if object includes special characters
  if (specialchr ($object, ".-_~") == true) $object = specialchr_encode ($object, "no");
  
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
      if ($template != "" && strpos ($template, "/") > 0)
      {
        list ($site, $template) = explode ("/", $template);
        
        if ($site != "" && $template != "")
        {
          $result = loadtemplate ($site, $template);
          
          if ($result['content'] != "")
          {
            $hypertag_array = gethypertag ($result['content'], "text", 0);
            
            if (is_array ($hypertag_array))
            {
              $labels = array();
              
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
                
                if ($id != "" && $label != "") $labels[$id] = $label;
                elseif ($id != "") $labels[$id] = str_replace ("_", " ", $id);
              }
            }
          }
        }
      }
      
			if ($contentdata != false)
      {
        $metadata = Null;
      
        // if no template and no labels are defined
        if (!is_array ($labels))
        {
  				$textnode = getcontent ($contentdata, "<text>");
          
  				if ($textnode != false)
          {
  					if (strtolower ($seperator) != "array") $metadata = "";
            else $metadata = array();
            
  					foreach ($textnode as $buffer)
            {
              // get info from container
  						$text_id = getcontent ($buffer, "<text_id>");
              
              // dont include comments or articles (they use :)
              if (strpos ($text_id[0], ":") == 0)
              {
                $label = str_replace ("_", " ", $text_id[0]);
              
    						$text_content = getcontent ($buffer, "<textcontent>");
    						$text_content[0] = str_replace ("\"", "'", strip_tags ($text_content[0]));
                
                // add space after comma
                if (strpos ($text_content[0], ",") > 0 && strpos ($text_content[0], ", ") < 1)
                {
                  $text_content[0] = str_replace (",", ", ", $text_content[0]);
                }
                
    						if (strtolower ($seperator) != "array") $metadata .= $label.": ".$text_content[0].$seperator;
                else $metadata[$label] = $text_content[0];
              }
  					}
  				}
        }
        // if labels were defined
        elseif (is_array ($labels) && sizeof ($labels) > 0)
        {
          foreach ($labels as $id => $label)
          {
            $text_str = "";
            
            // dont include comments or articles (they use :)
            if (strpos ($id, ":") == 0)
            {
              $textnode = selectcontent ($contentdata, "<text>", "<text_id>", $id);
              
              if ($textnode != false)
              {
                $text_content = getcontent ($textnode[0], "<textcontent>");
                $text_content[0] = str_replace ("\"", "'", strip_tags ($text_content[0]));
                
                // add space after comma
                if (strpos ($text_content[0], ",") > 0 && strpos ($text_content[0], ", ") < 1)
                {
                  $text_content[0] = str_replace (",", ", ", $text_content[0]);
                }
                
    						$text_str = $text_content[0];
              }
            }
        
						if (strtolower ($seperator) != "array") $metadata .= $label.": ".$text_str.$seperator;
            else $metadata[$label] = $text_str;
          }
        }
        
				return $metadata;
			}
      else return false;
		}
    else return false;
	}
  else return false;
}

// ---------------------- getmetadata_multiobjects -----------------------------
// function: getmetadata_multiobjects()
// input: converted path of multiple objects as array, user name
// output: assoziatve array with all text content and meta data / false

// description:
// Extracts all metadata including media information for a provided list of objects.
// This function is used for the CSV export in the objectlist views and also evaluates the access permissions of the user.

function getmetadata_multiobjects ($multiobject_array, $user)
{
  global $mgmt_config, $siteaccess, $pageaccess, $compaccess, $hiddenfolder, $adminpermission, $localpermission;
  
  // exclude attributes from result (always exclude 'id')
  $exclude_attributes = array ("id", "object_id", "hash");

  if (is_array ($multiobject_array) && sizeof ($multiobject_array) > 0 && $user != "")
  {
    $result = array();
    $text_ids = array();
    $intermediate = array();

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
          
          // for pages and components
          if ($objectinfo['media'] == "") $objectdata = rdbms_externalquery ('SELECT * FROM object INNER JOIN container ON object.id=container.id WHERE object.id='.intval($objectinfo['container_id']));
          // for media assets
          else $objectdata = rdbms_externalquery ('SELECT * FROM object INNER JOIN container ON object.id=container.id LEFT JOIN media ON object.id=media.id WHERE object.id='.intval($objectinfo['container_id']));
          
          if (is_array ($objectdata) && sizeof ($objectdata) > 0)
          {
            $id = $objectdata[0]['id'];
            
            // add container ID again
            $result[$multiobject]['Container-ID'] = $id;
            
            // exclude attributes
            foreach ($objectdata[0] as $key => $value)
            {
              if (!in_array ($key, $exclude_attributes))
              {
                $result[$multiobject][ucfirst($key)] = $value;
              }
            }
            
            $textnodes = rdbms_externalquery ('SELECT text_id, textcontent FROM textnodes WHERE id='.intval($id).' AND type!="file" AND type!="media" AND type!="page" AND type!="comp"');
            
            // text content
            if (is_array ($textnodes) && sizeof ($textnodes) > 0)
            {
              foreach ($textnodes as $textnode)
              {
                if (is_array ($textnode))
                {
                  $intermediate[$multiobject][$textnode['text_id']] = $textnode['textcontent'];
                  
                  // collect text IDs
                  if (!in_array ($textnode['text_id'], $text_ids)) $text_ids[] = $textnode['text_id'];
                }
              }
            }
          }
        }
      }
    }

    if (is_array ($result) && sizeof ($result) > 0)
    {
      // create new table with all text IDs for proper export
      if (is_array ($intermediate) && sizeof ($intermediate) > 0)
      {
        foreach ($intermediate as $key => $textarray)
        {
          foreach ($text_ids as $text_id)
          {
            if (isset ($textarray[$text_id])) $result[$key]['Content:'.$text_id] = $textarray[$text_id];
            else $result[$key]['Content:'.$text_id] = "";
          }
        }
      }
      
      return $result;
    }
    else return false;
  }
  else return false;
}

// ---------------------- getmetadata_container -----------------------------
// function: getmetadata_container()
// input: container ID, array of text IDs
// output: assoziatve array with all text content and meta data / false

// description:
// Extracts container, media, and metadata information of a container.
// This function is used for the presentation of metadata for objectlist views.

function getmetadata_container ($container_id, $text_id_array)
{
  global $mgmt_config, $labels;

  if ($container_id > 0 && is_array ($text_id_array) && sizeof ($text_id_array) > 0)
  {
    $result = array();

    // use database
    if ($mgmt_config['db_connect_rdbms'] != "")
    {
      // collect container and media info
      $select = "";
      
      // date created
      if (in_array ("createdate", $text_id_array))
      {
        if ($select != "") $select .= ', ';
        $select .= 'container.createdate';
      }
      
      // date modified
      if (in_array ("modifieddate", $text_id_array))
      {
        if ($select != "") $select .= ', ';
        $select .= 'container.date';
      }
      
      // user/owner
      if (in_array ("owner", $text_id_array))
      {
        if ($select != "") $select .= ', ';
        $select .= 'container.user';
      }
      
      // file size
      if (in_array ("filesize", $text_id_array))
      {
        if ($select != "") $select .= ', ';
        $select .= 'media.filesize';
      }
    
      if ($select != "")
      {
        $objectdata = rdbms_externalquery ('SELECT '.$select.' FROM container LEFT JOIN media ON media.id=container.id WHERE container.id='.intval($container_id));
 
        // reduce array
        if (is_array ($objectdata) && sizeof ($objectdata) > 0) $result = $objectdata[0];
      }
      
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
        $sql = 'SELECT text_id, textcontent FROM textnodes WHERE id='.intval($container_id);
         
        // query
        $textnodes = rdbms_externalquery ($sql.$conditions);
   
        // text content
        if (is_array ($textnodes) && sizeof ($textnodes) > 0)
        {
          foreach ($textnodes as $textnode)
          {
            if (is_array ($textnode))
            {
              $result['text:'.$textnode['text_id']] = $textnode['textcontent'];
            }
          }
        }
      }
    }
    // user content container
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
          else $result['createdate'] = "";
        }
        
        // date modified
        if (in_array ("modifieddate", $text_id_array))
        {
          $temp = getcontent ($contentdata, "<contentdate>");
          
          if (!empty ($temp[0])) $result['date'] = $temp[0];
          else $result['date'] = "";
        }
        
        // user/owner
        if (in_array ("owner", $text_id_array))
        {
          $temp = getcontent ($contentdata, "<contentuser>");
          
          if (!empty ($temp[0])) $result['user'] = $temp[0];
          else $result['user'] = "";
        }
        
        if (is_array ($text_id_array) && sizeof ($text_id_array) > 0)
        {
				  $textnode = getcontent ($contentdata, "<text>");
          
					foreach ($textnode as $buffer)
          {
            // get info from container
						$text_id = getcontent ($buffer, "<text_id>");
            
            // only include requested text IDs
            if (in_array ("text:".$text_id[0], $text_id_array))
            {
  						$text_content = getcontent ($buffer, "<textcontent>");
  						$text_content[0] = cleancontent ($text_content[0]);
              
              $result['text:'.$text_id[0]] = $text_content[0];
            }
					}
				}
      }
    }

    if (is_array ($result) && sizeof ($result) > 0) return $result;
    else return false;
  }
  else return false;
}

 // ========================================= LOAD CONTENT ============================================

// ---------------------------------------- getobjectcontainer ----------------------------------------
// function: getobjectcontainer()
// input: publication [string], location [string], object [string], user [string]
// output: Content Container [XML]/false
// requires: config.inc.php

// description:
// Loads the content container of a given object (page, component, folder)

function getobjectcontainer ($site, $location, $object, $user)
{
  global $mgmt_config;

  // deconvert location
  if (@substr_count ($path, "%page%") > 0 || @substr_count ($path, "%comp%") > 0)
  {
    $cat = getcategory ($site, $location);
    $location = deconvertpath ($location, $cat);
  }
  
  // if object includes special characters
  if (specialchr ($object, ".-_~") == true)
  {      
    $object = specialchr_encode ($object, "no");
  }

  if (valid_publicationname ($site) && valid_locationname ($location) && $object != "" && valid_objectname ($user))
  {
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";

    // evaluate if object is a file or a folder
    if (@is_dir ($location.$object))
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

      $data = loadcontainer ($container, "work", $user);
      
      if ($data != false && $data != "") return $data;
      else return false;
    }    
  }
  else return false;
}

// ------------------------------------------ getcontainer --------------------------------------------
// function: getcontainer()
// input: container name or container ID, container type [published, work]
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
// input: %
// output: URL of wallpaper image / false
// requires: config.inc.php

// description:
// Provides a wallpaper image used for the logon screen.

function getwallpaper ()
{
  global $mgmt_config;
  
  // get wallpaper name
  $wallpaper_name = @file_get_contents ("http://cms.hypercms.com/wallpaper/?action=name");
  
  if (!empty ($wallpaper_name))
  {
    // if file does not exist in temp view directory
    if (!is_file ($mgmt_config['abs_path_temp']."view/".$wallpaper_name))
    {
      // get wallpaper file
      $wallpaper_file = @file_get_contents ("http://cms.hypercms.com/wallpaper/?action=get&name=".urlencode($wallpaper_name));
      
      if (!empty ($wallpaper_file))
      {
        if (savefile ($mgmt_config['abs_path_temp']."view/", $wallpaper_name, $wallpaper_file)) return $mgmt_config['url_path_temp']."view/".$wallpaper_name;
        else return false;
      }
      else return false;
    }
    else return $mgmt_config['url_path_temp']."view/".$wallpaper_name;
  }
  else return false;
}
 
// ======================================== GET INFORMATION ===========================================

// --------------------------------------- getcontainername -------------------------------------------
// function: getcontainername()
// input: container name (e.g. 0000112.xml.wrk) or container ID
// output: Array with file name of the working content container (locked or unlocked!) and username if locked
// requires: config.inc.php to be loaded

function getcontainername ($container)
{
  global $mgmt_config;
  
  $result = array();
  $result['result'] = false;
  
  if (valid_objectname ($container))
  {
    // define container ID and container name
    if (strpos ($container, ".xml") > 0)
    {
      $container_id = substr ($container, 0, strpos ($container, ".xml"));
    }
    else
    {
      $container_id = $container;
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
      // return result
      $result['result'] = true;
      $result['container'] = $containerwrk;
      $result['user'] = "";    
      return $result;
    }
    // container exists and is locked by current user
    elseif (is_file ($location.$containerwrk.".@".$_SESSION['hcms_user']))
    {
      // return result
      $result['result'] = true;
      $result['container'] = $containerwrk.".@".$_SESSION['hcms_user'];
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
// input: publication name, location path (as absolute path or converted path), category [page,comp], source for name [path,name]
// output: location with readable names instead of file names / false on error

function getlocationname ($site, $location, $cat, $source="path")
{
  global $mgmt_config, $lang, $hcms_lang_codepage;
  
  if (valid_locationname ($location))
  {
    // load config is not available
    if (valid_publicationname ($site) && (empty ($mgmt_config[$site]) || !is_array ($mgmt_config[$site])) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }
    
    // check for .folder and remove it
    if (getobject ($location) == ".folder") $location = getlocation ($location);

    // input is converted location
    if (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1 && isset ($mgmt_config[$site]) && is_array ($mgmt_config[$site]))
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
    else return false;
   
    if (valid_publicationname ($site) && $location_esc != "" && $location_abs != "")
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
        
        if ($cat == "page") $location_name = "/".$site."/".$location_name;
        elseif ($cat == "comp") $location_name = "/".$location_name;        
      }
      // get names from decoding the file path
      else
      {
        if ($cat == "page") $root_abs = "%page%";
        elseif ($cat == "comp") $root_abs = "%comp%";
        else $root_abs = "";
        
        if ($root_abs != "") $location_name = str_replace ($root_abs, "", $location_esc);
        $location_name = specialchr_decode ($location_name);
      }
      
      if ($location_name != "") return $location_name;
      else return false;
    }
    else return false;
  }
  else return false;
}

// --------------------------------------- getthemelocation -------------------------------------------
// function: getthemelocation ()
// input: theme name (optional)
// output: path to theme / false

// description:
// Returns the absolute path (URL) of the theme (css and images).

function getthemelocation ($theme="")
{
  global $mgmt_config;

  // input parameter
  if (valid_objectname ($theme) && is_dir ($mgmt_config['abs_path_cms']."theme/".$theme))
  {
    return cleandomain ($mgmt_config['url_path_cms']."theme/".$theme."/");
  }
  // theme path from session
  elseif (!empty ($_SESSION['hcms_themepath']))
  {
    return $_SESSION['hcms_themepath'];
  }
  // theme name from session
  elseif (!empty ($_SESSION['hcms_themename']) && is_dir ($mgmt_config['abs_path_cms']."theme/".$_SESSION['hcms_themename']))
  {
    return cleandomain ($mgmt_config['url_path_cms']."theme/".$_SESSION['hcms_themename']."/");
  }    
  // from main config 
  elseif (valid_objectname ($mgmt_config['theme']) && is_dir ($mgmt_config['abs_path_cms']."theme/".$mgmt_config['theme']))
  {
    return cleandomain ($mgmt_config['url_path_cms']."theme/".$mgmt_config['theme']."/");
  }
  // default theme
  else
  {
    return cleandomain ($mgmt_config['url_path_cms']."theme/standard/");
  }
}

// ---------------------- getcategory -----------------------------
// function: getcategory()
// input: publication name (optional), location path
// output: category ['page, comp'] / false on error
// requires: config.inc.php

// description:
// Evaluates the category ['page, comp'] of a location

function getcategory ($site="", $location)
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
        if (@substr_count ($location, $mgmt_config['abs_path_comp']) == 1) $cat = "comp";
        elseif (@substr_count ($location, $mgmt_config[$site]['abs_path_page']) == 1) $cat = "page";
      }  
      else
      {
        if (@substr_count ($location, $mgmt_config[$site]['abs_path_page']) == 1) $cat = "page";            
        elseif (@substr_count ($location, $mgmt_config['abs_path_comp']) == 1) $cat = "comp";
      }
    }
    elseif (@substr_count ($location, "://") > 0 && valid_publicationname ($site))
    {
      if (!empty ($mgmt_config['abs_path_comp']) && !empty ($mgmt_config[$site]['abs_path_page']) && strlen ($mgmt_config['url_path_comp']) > strlen ($mgmt_config[$site]['url_path_page']))
      {
        if (@substr_count ($location, $mgmt_config['url_path_comp']) == 1) $cat = "comp";
        elseif (@substr_count ($location, $mgmt_config[$site]['url_path_page']) == 1) $cat = "page";
      }  
      else
      {
        if (@substr_count ($location, $mgmt_config[$site]['url_path_page']) == 1) $cat = "page";            
        elseif (@substr_count ($location, $mgmt_config['url_path_comp']) == 1) $cat = "comp";
      }
      
      if (!isset ($cat))
      {
        if (!is_array ($publ_config) && valid_publicationname ($site) && @is_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"))
        {
          // load ini
          $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini");
        }
         
        if (!empty ($url_publ_comp) && !empty ($mgmt_config[$site]['url_publ_page']) && strlen ($url_publ_comp) > strlen ($publ_config['url_publ_page']))
        {
          if (@substr_count ($location, $publ_config['url_publ_comp']) == 1) $cat = "comp";
          elseif (@substr_count ($location, $publ_config['url_publ_page']) == 1) $cat = "page";
        }  
        else
        {
          if (@substr_count ($location, $publ_config['url_publ_page']) == 1) $cat = "page";            
          elseif (@substr_count ($location, $publ_config['url_publ_comp']) == 1) $cat = "comp";
        }
      } 
    }
    else return false;
  }
  else return false;
  
  if (!empty ($cat) && ($cat == "page" || $cat == "comp")) return $cat;
  else return false;
}

// ---------------------- getpublication -----------------------------
// function: getpublication()
// input: converted location path
// output: publication name

// description:
// Extract the publication name of a location path

function getpublication ($path)
{
  if ($path != "")
  {
    $site = false;
    
    // extract publication from the converted path (first found path entry only!)
    if (@substr_count ($path, "%page%") > 0) $root_var = "%page%/";
    elseif (@substr_count ($path, "%comp%") > 0) $root_var = "%comp%/";
    elseif (@substr_count ($path, "%media%") > 0) $root_var = "%media%/";
    else $root_var = false;
  
    if ($root_var != false)
    {
      $pos1 = @strpos ($path, $root_var) + strlen ($root_var);
      
      if ($pos1 != false) $pos2 = @strpos ($path, "/", $pos1);
      else $pos2 = false;
      
      if ($pos1 != false && $pos2 != false) $site = @substr ($path, $pos1, $pos2-$pos1);
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
  else return false;
}

// ------------------------- getlocation ---------------------------------
// function: getlocation()
// input: location path
// output: location (without object or folder)

// description:
// Extract the location excluding object or folder of a location path

function getlocation ($path)
{
  if ($path != "")
  {
    // if object has no slash at the end
    if (substr ($path, -1) != "/")
    {
      $location = substr ($path, 0, strrpos ($path, "/")+1);
    }
    // else remove slash
    else
    {
      // remove slash at the end of the objectpath string
      $location = substr ($path, 0, strlen ($path)-1);
      $location = substr ($location, 0, strrpos ($location, "/")+1);          
    }
    
    return $location;
  }
  else return false;
}

// ------------------------- getobject ---------------------------------
// function: getobject()
// input: location path
// output: object or folder name

// description:
// Extract the object or folder of a location path

function getobject ($path)
{
  if ($path != "")
  {
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
        $path = substr ($path, 0, strlen ($path) - 1);
        $object = substr ($path, strrpos ($path, "/") + 1);          
      }
      
      // if path holds parameters (URL)
      if (strpos ($object, "?") > 0)
      {
        $object = substr ($object, 0, strrpos ($object, "?"));
      }
    }
    else $object = $path;
    
    return $object;
  }
  else return false;
}

// ---------------------- getmediacontainername -----------------------------
// function: getmediacontainername()
// input: file name
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
    else return false;
  }
  else return false;
}

// ---------------------- getmediacontainerid -----------------------------
// function: getmediacontainernid()
// input: media file name
// output: container ID / false on error

// description:
// Extract the container ID from a multimedia file name by using the hcms-ID

function getmediacontainerid ($file)
{
  if (valid_objectname ($file) && strpos ("_".$file, "_hcm") > 0)
  {
    $startpos = strrpos ($file, "_hcm") + 4;
    
    if (strpos ($file, ".", $startpos) > 0) $endpos = strpos ($file, ".", $startpos);
    else $endpos = strlen ($file);
    
    $length = $endpos - $startpos;
    $id = substr ($file, $startpos, $length);
    
    if (is_int (intval ($id))) return $id;
    else return false;
  }
  else return false;
}

// ---------------------- getmediafileversion -----------------------------
// function: getmediafileversion()
// input: container name or container ID
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
      $container_id = substr ($container, 0, strpos ($container, ".xml"));
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
        $scandir = scandir ($version_dir);
        
        $version_container = array();
    
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
        else return false;
      }
    }
    else return false;
  }
  else return false;
}

// ---------------------- getobjectid -----------------------------
// function: getobjectid()
// input: converted object path or pathes separated by |
// output: object ID

// description:
// Converts the object path to the object ID of any object

function getobjectid ($objectlink)
{
  if ($objectlink != "")
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
    return $objectlink_conv;
  }
  else return $objectlink;
}

// ---------------------- getobjectlink -----------------------------
// function: getobjectlink()
// input: converted object ID or IDs separated by |
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
    return $objectid_conv;
  }
  else return $objectid;
}

// ---------------------- getcontainerversions -----------------------------
// function: getcontainerversions()
// input: container ID or container name
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
    else return false;
  }
  else return false;
}

// ---------------------- getlocaltemplates -----------------------------
// function: getlocaltemplates()
// input: publication name, template category [page,comp,meta,inc] (optional)
// output: array with all template names / false

// description:
// This function returns a list of all templates of a publication without inherited templates from other publications.

function getlocaltemplates ($site, $cat="")
{
  global $mgmt_config;
  
  if (valid_publicationname ($site))
  {
    $scandir = scandir ($mgmt_config['abs_path_template'].$site."/");

    $template_files = array();
  
    if ($scandir)
    {
      foreach ($scandir as $entry)
      {
        if ($entry != "." && $entry != ".." && !is_dir ($entry) && substr_count ($entry, ".tpl.v_") == 0 && substr_count ($entry, ".bak") == 0)
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
          elseif ($cat == "")
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
    else return false;
  }
  else return false;
}

// ----------------------------------------- gettemplates ---------------------------------------------
// function: gettemplates()
// input: publication name, object category [all,page,comp,meta] (optional)
// output: template file name list as array / false on error
// requires: config.inc.php to be loaded before

// description:
// This function returns a list of all templates for pages or components.
// Based on the inheritance settings of the publication the template will be loaded with highest priority from the own publication and if not available from a parent publication.

function gettemplates ($site, $cat="all")
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  if (valid_publicationname ($site) && ($cat == "all" || $cat == "page" || $cat == "comp" || $cat == "meta"))
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
            elseif ($cat == "all") $template_array[] = $entry;   
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
    else return false;
  }
  else return false;
}
            
// ---------------------- gettemplateversions -----------------------------
// function: gettemplateversions()
// input: publication name, template name
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
    else return false;
  }
  else return false;
}

// ---------------------- getfileinfo -----------------------------
// function: getfileinfo()
// input: publication name (optional), file name incl. extension, category [page,comp] (optional)
// output: array/false

// description:
// defines file properties based on the file extension and returns file info as an array:
//    $result['file']: file name without hypercms management extension
//    $result['name']: readable file name without hypercms management extension
//    $result['filename']: file name without file extensions
//    $result['icon']: file name of the file icon
//    $result['type']: file type
//    $result['ext']: file extension incl. dot in lower case
//    $result['published']: if file is published = true else = false
//    $result['deleted']: if file is deleted = true else = false

function getfileinfo ($site, $file, $cat="comp")
{
  global $mgmt_config;
  
  if ($file != "" && (valid_publicationname ($site) || ($cat == "page" || $cat == "comp")))
  {
    include ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
    
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
      // CASE: file
      else
      {
        // if file holds a path
        if (substr_count ($file, "/") > 0) $file = getobject ($file);
        
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
        // objects in recycle bin 
        elseif ($file_ext == ".recycle")
        {
          $file_name = substr ($file, 0, -8);
          // get file name without extensions
          $file_nameonly = strrev (substr (strstr (strrev ($file_name), "."), 1));
          // get file extension of file name minus .recycle
          $file_ext = strtolower (strrchr ($file_name, "."));
          
          $file_published = true;
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
        
        // MS Word
        if ($file_ext == ".doc" || $file_ext == ".docx" || $file_ext == ".docm" || $file_ext == ".dot" || $file_ext == ".dotx")
        {
          $file_icon = "file_doc.png";
          $file_type = "MS Word";
        }
        // MS Powerpoint
        elseif ($file_ext == ".ppt" || $file_ext == ".pptx" || $file_ext == ".pps" || $file_ext == ".ppsx" || $file_ext == ".pot" || $file_ext == ".potm" || $file_ext == ".potx")
        {
          $file_icon = "file_ppt.png";
          $file_type = "MS Powerpoint";
        }
        // MS Excel
        elseif ($file_ext == ".xls" || $file_ext == ".xlsx" || $file_ext == ".xlst" || $file_ext == ".xlsm" ||$file_ext == ".csv")
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
        // text based documents in proprietary format    
        elseif (@substr_count (strtolower ($hcms_ext['bintxt']).".", $file_ext.".") > 0)
        {
          $file_icon = "file_txt.png";
          $file_type = "Text";
        }
        // text based documents in clear text  
        elseif (@substr_count (strtolower ($hcms_ext['cleartxt']).".", $file_ext.".") > 0)
        {
          $file_icon = "file_txt.png";
          $file_type = "Text";
        }        
        // image files 
        elseif (@substr_count (strtolower ($hcms_ext['image']).".", $file_ext.".") > 0)
        {
          $file_icon = "file_image.png";
          $file_type = "Image";
        }
        // Adobe Flash
        elseif (@substr_count (strtolower ($hcms_ext['flash']).".", $file_ext.".") > 0)
        {
          $file_icon = "file_flash.png";
          $file_type = "Macromedia Flash";
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
        elseif (@substr_count (strtolower ($hcms_ext['video']).".", $file_ext.".") > 0)
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
        // all other files    
        else
        {
          $file_icon = "file_binary.png";
          $file_type = substr ($file_ext, 1);
        }
      } 
    }
    // no extension available
    else 
    {
      // if file holds a path
      if (@substr_count ($file, "/") > 0) $file = getobject ($file);
          
      $file_name = $file;
      $file_nameonly = $file;
      $file_icon = "file_binary.png";
      $file_type = "unknown";
      $file_ext = "";
      $file_published = true;
      $file_deleted = false;
    }

    // set result array
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
// input: publication name, location, object name, user name (optional), container version (optional)
// output: result array / false on error
// requires: config.inc.php

// description:
// Get all file pointers (container, media, template) and object name from object file and collect info from container version, if provided

function getobjectinfo ($site, $location, $object, $user="sys", $container_version="")
{
  global $mgmt_config;
  
  // deconvert location
  $location = deconvertpath ($location, "file"); 
      
  // if object includes special characters
  if (specialchr ($object, ".-_~") == true) $object = specialchr_encode ($object, "no");

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
    if (substr ($location, -1) != "/") $location = $location."/";
    
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
            if (getlocation ($contentobject) == $location_esc)
            {
              $object = getobject ($contentobject);
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
    else return false;
  }
  else return false;
}

// ---------------------- getfilesize -----------------------------
// function: getfilesize()
// input: converted path to file or directory
// output: result array with file size in kB and file count / false on error

// description:
// This function won't give you a proper result of the file size of multimedia components, if there is no Database installed.

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
      return rdbms_getfilesize ("", $file);
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
    else return false;
  }
  else return false;
}

// ---------------------- getmimetype -----------------------------
// function: getmimetype()
// input: file name incl. extension  
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

// ---------------------- getfiletype -----------------------------
// function: getfiletype()
// input: file extension or file name
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
    elseif (substr_count (strtolower ($hcms_ext['bintxt'].$hcms_ext['cleartxt']).".", $file_ext.".") > 0) $filetype = "document";
    elseif (substr_count (strtolower ($hcms_ext['cms'].$hcms_ext['cleartxt']), $file_ext.".") > 0) $filetype = "text";
    elseif (substr_count (strtolower ($hcms_ext['image']).".", $file_ext.".") > 0) $filetype = "image";
    elseif (substr_count (strtolower ($hcms_ext['video']).".", $file_ext.".") > 0) $filetype = "video";
    elseif (substr_count (strtolower ($hcms_ext['flash']).".", $file_ext.".") > 0) $filetype = "flash";
    elseif (substr_count (strtolower ($hcms_ext['compressed']).".", $file_ext.".") > 0) $filetype = "compressed";
    elseif (substr_count (strtolower ($hcms_ext['binary']).".", $file_ext.".") > 0) $filetype = "binary";
    else $filetype = "unknown";
    
    return $filetype;
  }
  elseif ($file_ext == "")
  {
    return "unknown";
  }
  else return false;
}

// ---------------------- getpdfinfo -----------------------------
// function: getpdfinfo()
// input: path to PDF file, box attribute [BleedBox,CropBox,MediaBox] (optional)
// output: result array with width and height as keys / false on error

// description:
// Extracts width and height in pixel of a PDF file based on the MediaBox in the filex content or ImageMagick as fallback

function getpdfinfo ($filepath, $box="MediaBox")
{
  global $mgmt_config, $mgmt_imagepreview, $user;
  
  if (valid_locationname ($filepath))
  {
    $result = false;
    
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
    if ((empty ($result["width"]) || empty ($result["height"])) && is_supported ($mgmt_imagepreview, $media))
    {
      $cmdresult = exec ("identify -format \"%wx%h\" \"".shellcmd_encode ($filepath)."[0]\"");

      if (strpos ($cmdresult, "x") > 0) list ($result["width"], $result["height"]) = explode ("x", $cmdresult);
    }

    return $result;
  }
  else return false;
}

// ---------------------- getvideoinfo -----------------------------
// function: getvideoinfo()
// input: path to video file
// output: video file information as result array / false on error

// description:
// Extract video metadata from video file.

function getvideoinfo ($mediafile)
{
  global $mgmt_config, $mgmt_mediapreview, $user;
  
  // read media information from media files
  if ($mediafile != "")
  {
  	$dimensionRegExp = "/, ([0-9]+x[0-9]+)/";
  	$durationRegExp = "/Duration: ([0-9\:\.]+)/i";
  	$bitRateRegExp = "/bitrate: ([0-9]+ [a-z]+\/s)/i";

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

    $site = getpublication ($mediafile);
    $location = getlocation ($mediafile);
    $media = getobject ($mediafile);

    // prepare media file
    $temp = preparemediafile ($site, $location, $media, $user);
    
    // reset location if restored
    if ($temp['restored'])
    {
      $location = $temp['location'];
      $media = $temp['file'];
    }
    
    if ($temp['result'] && $temp['crypted'])
    {
      $mediafile = $temp['templocation'].$temp['tempfile'];
    }
    
    // verify media file
    if (!is_file ($location.$media)) return false;
    
    // get video file size in MB
    $filesize = round (@filesize ($mediafile) / 1024 / 1024, 0)." MB";
    if ($filesize < 1) $filesize = "<1 MB";
    
    // file extension
    $file_info = getfileinfo ("", $mediafile, "comp");
    
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
      	$cmd = $mgmt_mediapreview[$mediapreview_ext]." -i \"".shellcmd_encode ($mediafile)."\" -y -f rawvideo -vframes 1 /dev/null 2>&1";
        
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
          
          // video roation in degrees
          reset ($metadata);
          
          foreach ($metadata as $line)
          {
            if (strpos ("_".$line, "rotate") > 0)
            {
              // Rotate    : 180
              $line = substr ($line, strpos ($line, "rotate") + 7);
              
              // audio (audio bitrate might be missing in flac files)
              @list ($rest, $rotate) = explode (":", $line);
              
              // clean
              if (!empty ($rotate)) $rotate = trim ($rotate);

              break;
            }
          }

          // audio and video information (codec, bitrate and frequenzy)
          reset ($metadata);
          
          foreach ($metadata as $line)
          {
            if (strpos ("_".$line, "Audio: ") > 0)
            {
              // Audio: aac (mp4a / 0x6134706D), 11025 Hz, mono, s16, 53 kb/s
              $line = substr ($line, strpos ($line, "Audio: ") + 7);
              
              // audio (audio bitrate might be missing in flac files)
              @list ($audio_codec, $audio_frequenzy, $audio_channels, $audio_sample, $audio_bitrate) = explode (", ", $line);
              
              // clean codec name
              if (strpos ($audio_codec, "(") > 0) $audio_codec = substr ($audio_codec, 0, strpos ($audio_codec, "("));
              $audio_codec = strtoupper (trim ($audio_codec));

              break;
            }
          }
          
          reset ($metadata);
          
          foreach ($metadata as $line)
          {
            if (strpos ("_".$line, "Video: ") > 0)
            {
              // Video: wmv2 (WMV2 / 0x32564D57), yuv420p, 320x240, 409 kb/s, 25 tbr, 1k tbn, 1k tbc
              
              // tbn = the time base in AVStream that has come from the container
              // tbc = the time base in AVCodecContext for the codec used for a particular stream
              // tbr = tbr is guessed from the video stream and is the value users want to see when they look for the video frame rate

              $line = substr ($line, strpos ($line, "Video: ") + 7);
              
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
    $result['filesize'] = $filesize;
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
  else return false;
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
    // works only for IE < 11
    if (preg_match ('/MSIE/i', $u_agent) && !preg_match ('/Opera/i', $u_agent))
    {
      $bname = 'msie';
      $ub = "MSIE";
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
      $bname = 'Netscape';
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
      if (strripos ($u_agent, "Version") < strripos ($u_agent, $ub))
      {
        $version = $matches['version'][0];
      }
      else
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
  else return false;
}

// ---------------------- getcontentlocation -----------------------------
// function: getcontentlocation()
// input: container id, type [url_path_content, abs_path_content]
// output: location of the container file / false on error

// description:
// Gets the content location based on the given container id.
// The use of various directories is necessary since the number of directories is limited by the filesystem, e.g. Linux ext3 is limited to 32000.

function getcontentlocation ($container_id, $type="abs_path_content")
{
  global $mgmt_config;
  
  if (intval ($container_id) > 0 && ($type == "url_path_content" || $type == "abs_path_content") && is_array ($mgmt_config))
  {
    // correct container ID (add zeros)
    if (strlen ($container_id) < 7)
    {
      $multiplier = 7 - strlen ($container_id);
      $container_id = str_repeat ("0", $multiplier).$container_id;
    }
    
    // directory block size of 10.000
    $limitbase = 10000;
    
    // max. 32000 subdirectories
    for ($i=0; $i<32000; $i++)
    {
      $limit = $limitbase * (1 + $i);

      if (intval ($container_id) < $limit)
      {
        if (!is_dir ($mgmt_config['abs_path_content'].$i)) @mkdir ($mgmt_config['abs_path_content'].$i, $mgmt_config['fspermission']);
        return $mgmt_config[$type].$i."/".$container_id."/";
      }
    }
  }
  else return false;
} 

// ---------------------- getmedialocation -----------------------------
// function: getmedialocation()
// input: publication name, multimedia file name (including hcm-ID), type [url_path_media, abs_path_media, url_publ_media, abs_publ_media]
// output: location of the multimedia file / false on error

// description:
// Gets the media repsitory location from $mgmt_config array.
// The function supports up to 10 media repositories.
// Any other rules for splitting the media files on multiple devices can be implemented as well by the function getmedialocation_rule.

function getmedialocation ($site, $file, $type)
{
  global $mgmt_config, $publ_config;
  
  if (valid_locationname ($file) && $type != "" && is_array ($mgmt_config))
  {
    // include rule from external file (must return a value)   
    if (is_file ($mgmt_config['abs_path_data']."media/getmedialocation.inc.php"))
    {
      include ($mgmt_config['abs_path_data']."media/getmedialocation.inc.php");
    }
    
    // get file name
    $file = getobject ($file);
  
    // management configuration
    if ($type == "url_path_media" || $type == "abs_path_media")
    {
      // if media repository path is available
      if (!empty ($mgmt_config[$type]))
      {
        // multiple media harddisk/mountpoint support
        if (is_array ($mgmt_config[$type]))
        {
          // get container id as integer
          $container_id = intval (getmediacontainerid ($file));
          
          // get last digit of container id
          $no = substr ($container_id, -1);
        
          if (function_exists ("getmedialocation_rule"))
          {
            $result = getmedialocation_rule ($site, $file, $type, $container_id);
            
            if ($result != "")
            {
              // symbolic link
              if (is_link ($result.$site."/".$file))
              {
                // get link target
                $targetpath = readlink ($result.$site."/".$file);
                $targetroot = getlocation (getlocation ($targetpath));
                
                return $targetroot;
              } 
              // file
              else
              {
                return $result;
              }
            }
          }
          
          $hdarray_size = sizeof ($mgmt_config[$type]);
          
          if ($hdarray_size == 1)
          {
            return $mgmt_config[$type][1];
          }
          elseif ($hdarray_size  > 1) 
          {
            $j = 1;
            
            for ($i=1; $i<=10; $i++)
            {
              if (substr ($i, -1) == $no)
              {
                if ($mgmt_config[$type][$j] != "")
                {
                  // symbolic link
                  if (is_link ($mgmt_config[$type][$j].$site."/".$file))
                  {
                    // get link target
                    $targetpath = readlink ($mgmt_config[$type][$j].$site."/".$file);
                    $targetroot = getlocation (getlocation ($targetpath));
                    
                    return $targetroot;
                  }
                  // file
                  else
                  {
                    return $mgmt_config[$type][$j];
                  }
                }
              }
              
              if ($j == $hdarray_size) $j = 1;
              else $j++;
            }
          }
        }
        // single media harddisk/mountpoint
        else
        {
          // symbolic link
          if (is_link ($mgmt_config[$type].$site."/".$file))
          {
            // get link target
            $targetpath = readlink ($mgmt_config[$type].$site."/".$file);
            $targetroot = getlocation (getlocation ($targetpath));
            
            return $targetroot;
          }
          // file
          else
          {
            return $mgmt_config[$type];
          }
        }
      }
      else return false;
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
      else return false;
    }
    else return false;
  }
  else return false;
} 

// ---------------------- getlockedfileinfo -----------------------------
// function: getlockedfileinfo()
// input: location to file, file name
// output: Array holding file name incl. lock extension and user name / false on error

// description:
// Finds the locked file and returns the name and user as array

function getlockedfileinfo ($location, $file)
{
  global $mgmt_config;
  
  if (valid_locationname ($location) && valid_objectname ($file) && is_dir ($location))
  {
    // file is locked
    if (!is_file ($location.$file))
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
      else return false;
    }
    // file exists (is not locked)
    else
    {
      $result['file'] = $file;
      $result['user'] = "";
      
      return $result;
    }
  }
  else return false; 
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
                  if ($page !== false)
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
                $object_hash = rdbms_getobject_hash ($object_id);
                if (!empty ($object_path)) $object_path_array[$object_hash] = $object_path;
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

// ====================================== HOME BOXES =========================================

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

// =========================== CHAT ==================================

// ---------------------- getusersonline -----------------------------
// function: getusersonline()
// input: %
// output: Array of online user names / false

function getusersonline ()
{
  global $mgmt_config, $siteaccess;
  
  $session_dir = $mgmt_config['abs_path_data']."session/";
  
  if (is_dir ($session_dir) && $scandir = scandir ($session_dir))
  {
    // add slash if not present at the end
    if (substr ($session_dir, -1) != "/") $session_dir = $session_dir."/";           

    $result = array();
    
    foreach ($scandir as $user)
    {
      if (is_file ($session_dir.$user) && $user != "." && $user != ".." && strpos ($user, ".dat") > 0 && strpos ($user, "hyperdav_") === false)
      {
        // only users that have been logged in the past 8 hours are online users
        $now = time();
        $last_logon_time = filemtime ($session_dir.$user);
        $max = 8 * 60 * 60;
        
        if ($now - $last_logon_time < $max)
        {
          $result[] = substr ($user, 0, -4);
        }
      }
    }
    
    if (sizeof ($result) > 0)
    {
      $result = array_unique ($result);
      $result_filtered = array();
      
      // filter users based on their publication access
      if (!empty ($siteaccess) && is_array ($siteaccess))
      {
        $users = getuserinformation ();
        
        foreach ($result as $logon)
        {
          foreach ($siteaccess as $site)
          {
            if (!empty ($users[$site][$logon])) $result_filtered[] = $logon;
          }
        }
        
        // reset result
        $result = array_unique ($result_filtered);
      }
      
      return $result;
    }
    else return false;
  }
  else return false;
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
    else return false;
  }
  else return false;
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
    else return false;
  }
  else return false;
}

// ======================================== GET FILEPOINTER =====================================

// ------------------------------------------ getfilename ---------------------------------------
// function: getfilename()
// input: file content, hyperCMS tag name in page or component 
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
    else return false;
  }
  else return false;
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
      else return false;    
    }
    else return false;
  }
  else return false;
}

// ------------------------- gethypertagname ---------------------------
// function: gethypertagname()
// input: full hyperCMS tag
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
    else return false;
  }
  else return false;
}

// ------------------------ gethtmltag ------------------------------
// function: gethtmltag()
// input: file content, full hyperCMS tag (or other identifier)
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
          $tags = 0; // we allow max. 2 nested script tags to appear before we break the routine
           
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
        else return false;
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// ------------------------ gethtmltags ------------------------------
// function: gethtmltags()
// input: file content, full hyperCMS tag or other identifier in html tag
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
        else return false;
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// ------------------------- getattribute --------------------------------
// function: getattribute()
// input: string including attributes, attribute name, secure attribute value reg. XSS (optional)
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
        if ($secure)
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
    
          // " indicates end of attribute value
          if ($checkedstring[0] == "\"" && strpos (substr ($checkedstring, 1), "\"") >= 0)
          {
            // get the length of the value
            $vallen = strpos (substr ($checkedstring, 1), "\"") + 2;
          }  
          // ' indicates end of attribute value
          elseif ($checkedstring[0] == "'" && strpos (substr ($checkedstring, 1), "'") >= 0)
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
          
          // secure value
          if ($secure)
          {
            $value = strip_tags ($value);
            $value = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $value);
          }
          
          return $value;
        }
        else return false;
      }
      else return false;
    }
  }
  else return false;
}

// ----------------------------- getoption --------------------------------
// function: getoption()
// input: string including options, option name
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
    else return false;                  
  }
  else return false;
}
                      
// ------------------------------ getcharset ----------------------------------
// function: getcharset()
// input: publication, data from template or content container [string]
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
      // meta tag http-equiv
      if (strpos (strtolower ($data), " http-equiv=") > 0 && strpos (strtolower ($data), "content-type") > 0)
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
      // meta tag charset (HTML5)
      elseif (strpos (strtolower ($data), " charset=") > 0)
      {
        // get tag defined by the value of attribute charset=""
        $contenttypetag = gethtmltag (strtolower ($data), "charset");
    
        if ($contenttypetag != false)
        {
          $charset = getattribute ($data, "charset");
        }
      }
    } 
 
    // if hypertag is used to set the character set (e.g. components)
    if ($contenttype == false && strpos (strtolower ($data), "compcontenttype") > 0)
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
    if ($contenttype == false && @substr_count (strtolower ($data), " encoding=") > 0)
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
    if ($contenttype == false && @substr_count (strtolower ($data), "<pagecontenttype>") > 0)
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
    if ($contenttype == false && @substr_count (strtolower ($data), "charset") > 0)
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
    
    // no character set could be found in $data
    if (($contenttype == "" || $contenttype == false) && valid_publicationname ($site))
    {
      $contenttype = "text/html; charset=".$mgmt_config[$site]['default_codepage'];
      $charset = $mgmt_config[$site]['default_codepage'];
    }  

    // return result
    if ($contenttype != "") $result['contenttype'] = $contenttype;
    if ($charset != "") $result['charset'] = $charset;

    if (is_array ($result)) return $result;
    else return false;
  }
  elseif (valid_publicationname ($site))
  {
    $result['contenttype'] = "text/html; charset=".$mgmt_config[$site]['default_codepage'];
    $result['charset'] = $mgmt_config[$site]['default_codepage'];
    
    if (!empty ($result) && is_array ($result)) return $result;
    else return false;    
  }
  else return false;
}  

// ------------------------------ getartid ----------------------------------
// function: getartid()
// input: string including id
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
// input: string including id
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
// input: array
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
// input: path to directory, pattern as string (optional)
// output: sorted array of all files macthing the pattern / false on error

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
    else return false;
  }
  else return false;
}

// ---------------------------------------------- getuserinformation ----------------------------------------------
// function: getuserinformation()
// input: %
// output: assoziative array with basic user information [publication->username->attribute] / false
// requires: config.inc.php

// description:
// This function creates an assoziative array with user information, e.g. for a user select box.

function getuserinformation ()
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
    $usernode = getcontent ($userdata, "<user>");
  
    foreach ($usernode as $temp)
    {
      if ($temp != "")
      {
        $login = getcontent ($temp, "<login>");
        if ($user == "sys") $hashcode = getcontent ($temp, "<hashcode>");
        $admin = getcontent ($temp, "<admin>");
        $email = getcontent ($temp, "<email>");
        $realname = getcontent ($temp, "<realname>");
        $signature = getcontent ($temp, "<signature>");
        $language = getcontent ($temp, "<language>");
        $publication = getcontent ($temp, "<publication>");
        
        // standard user
        if (!empty ($login[0]) && (empty ($admin[0]) || $admin[0] == 0) && is_array ($publication))
        {
          foreach ($publication as $pub_temp)
          {
            if ($pub_temp != "")
            {
              $username = $login[0];
              if (!empty ($hashcode[0])) $user_array[$pub_temp][$username]['hashcode'] = $hashcode[0];
              $user_array[$pub_temp][$username]['email'] = $email[0];
              $user_array[$pub_temp][$username]['realname'] = $realname[0];
              $user_array[$pub_temp][$username]['signature'] = $signature[0];
              $user_array[$pub_temp][$username]['language'] = $language[0];
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
              $user_array[$pub_temp][$username]['email'] = $email[0];
              $user_array[$pub_temp][$username]['realname'] = $realname[0];
              $user_array[$pub_temp][$username]['signature'] = $signature[0];
              $user_array[$pub_temp][$username]['language'] = $language[0]; 
            }
          }
        }
      }
    }
  }
  
  if (!empty ($user_array) && is_array ($user_array)) return $user_array;
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
    if (!empty ($freeitem_array) && is_array ($freeitem_array) && sizeof ($freeitem_array) > 0)
    {
      return $freeitem_array[0];
    }
    // check for passed items of the user
    elseif (!empty ($passeditem_array) && is_array ($passeditem_array) && sizeof ($passeditem_array) > 0)
    {
      return $passeditem_array[0];
    }
    else return false;
  }
  else return false;
}
?>