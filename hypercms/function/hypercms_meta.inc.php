<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */
 
// =================================== META DATA FUNCTIONS =======================================

// --------------------------------------- importCSVtextcontent -------------------------------------------
// function: importCSVtextcontent ()
// input: publication name [string], location [string], path to CSV file [string], user name [string], type array or string [u,f,l,c,d,k] (optional), delimiter [string] (optional), enclosure [string] (optional), character set [string] (optional),
//        create folders if missing [boolean] (optional), create new object if the object can not be found [bollean] (optional), template name without extension to be used for the new object [string] (optional), 
//        update content of existing objects [boolean] (optional), print report [boolean] (optional), set PHP ini and headers for output buffering [bollean] (optional)
// output: true / false

// description:
// Imports metadata from a CSV file for various assets linked by name or conatiner ID. Empty rows or rows without a delimiter will be ignored.
// In order to identify an asset the file name as "Name" or the container ID as "containerID" must be provided in the first row before the content rows.

function importCSVtextcontent ($site, $location, $file_csv, $user, $type="", $delimiter=";", $enclosure='"', $charset="utf-8", $createfolders=false, $createobject=false, $template="default", $updateobject=true, $report=false, $set_headers=false)
{
  global $mgmt_config, $eventsystem;

  // initalize
  $error = array();
  $row = 1;
  $header = false;
  $id_filename = "";
  $id_containerid = "";
  $id_content = array();
  $art = array ();

  // define delimiters and enclosures
  $delimiters_csv = array (",", ";", "\t", "|");
  $enclosures_csv = array ('"', "'");
  $delimiters_keywords = array (",", ";", "\t", "|", "º", ":");

  // output buffering
  if (!empty ($report))
  {
    if ($set_headers)
    {
      // turn off output buffering
      ini_set ('output_buffering', 'off');
      // turn off PHP output compression (must be set before any output)
      ini_set ('zlib.output_compression', false);

      // recommended to prevent caching of event data
      header ('Cache-Control: no-cache');
    }
        
    // flush (send) the output buffer and turn off output buffering
    while (@ob_end_flush());
        
    // implicitly flush the buffer(s)
    ini_set ('implicit_flush', true);
    ob_implicit_flush (true);

    // report title
    echo "<br /><strong>Import report for ".str_replace (".folder", "", $location)." using CSV file ".$file_csv." (".date("Y-m-d H:i:s").")</strong><br />\n";

    // create output and flush buffer
    // Some browser wait for a minimum number of characters to arrive from the server before starting the actual rendering.
    for ($i = 0; $i < 1000; $i++) echo " ";

    // output 
    if (ob_get_level() > 0) ob_flush();
    flush();
  }

  if (valid_publicationname ($site) && valid_locationname ($location) && $file_csv != "" && is_file ($file_csv))
  {
    $cat = getcategory ($site, $location);

    // convert to input character set
    $data = HTTP_Get_contents ($file_csv);

    if ($data != "")
    {
      // detect
      $charset_csv = mb_detect_encoding ($data, mb_detect_order(), true);

      // convert if not the same character set
      if ($charset_csv != "" && strtolower ($charset_csv) != strtolower ($charset))
      {
        $data = convertchars ($data, "", $charset);

        if ($data != "")
        {
          $save = file_put_contents ($file_csv, $data);

          if ($save == false)
          {
            $errcode = "10119";
            $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|CSV file ".$file_csv." could not be saved after character set conversion (".$charset_csv." to ".$charset.")";
          }
        }
      }
    }

    if (($handle = fopen ($file_csv, "r")) !== false)
    {
      // analyze CSV file
      if ($delimiter == "" || $enclosure == "")
      {
        if (filesize ($file_csv) > 0) $filedata = @fread ($handle, filesize ($file_csv));
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
            else $enclosure = '"';
          }
        }
      }

      rewind ($handle);

      if ($delimiter != "")
      {
        while (($data = fgetcsv ($handle, 0, $delimiter, $enclosure)) !== false)
        {
          // get number of colums
          $cols = count ($data);

          // reset header switch
          if (is_array ($data) && $cols < 2) $header = false;

          // verify if row holds columns
          if (is_array ($data) && $cols > 1)
          {
            // first valid row holds content IDs
            if ($header == false)
            {
              // find asset identifier and content IDs
              for ($c = 0; $c < $cols; $c++)
              {
                // use object path or file name
                if (strtolower ($data[$c]) == "objectpath" || strtolower ($data[$c]) == "name")
                {
                  $id_filename = $c;
                  $header = true;
                }
                // use container ID
                elseif (strtolower ($data[$c]) == "containerid" || strtolower ($data[$c]) == "container-id" || strtolower ($data[$c]) == "container_id")
                {
                  $id_container = $c;
                  $header = true;
                }
                // must be content ID
                else
                {
                  // replace special characters and spaces in IDs
                  if (function_exists ("iconv")) $temp = iconv ('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $data[$c]);

                  // reset on conversion error
                  if (empty ($temp)) $temp = $data[$c];

                  // replace spaces in IDs
                  $temp = str_replace (array(" - ", " "), array("-", "_"), $temp);

                  // remove special characters in IDs
                  $temp = preg_replace ("/[^a-zA-Z0-9_\\-]/", "", $temp);

                  // assign content ID
                  $id_content[$c] = $temp;

                  // assign article
                  if (strpos ($data[$c], ":") > 0) $art[$id_content[$c]] = "yes";
                  else $art[$id_content[$c]] = "no";
                }
              }
            }
            // all other rows hold metadata
            else
            {
              $text = array();
              if ($type == "") $type = array();
              $object = "";
              $contentfile = "";
              $contendata = "";
              $loaded = false;

              for ($c = 0; $c < $cols; $c++)
              {
                // load container by its ID
                if (!empty ($id_container) && $c == $id_container && $data[$c] != "" && (is_numeric ($data[$c]) || strpos ($data[$c], ".xml") > 0))
                {
                  $contentfile = $data[$c];
                  $contendata = loadcontainer ($contentfile, "work", $user);

                  if (!empty ($contendata)) $loaded = true;
                }

                // load container from object
                if ($c == $id_filename && $data[$c] != "" && $loaded == false)
                {
                  $location = deconvertpath ($location, "file", true);

                  // object path has been provided
                  if (substr_count ($data[$id_filename], "/") > 0)
                  {
                    $temp_location = $location.getlocation (trim ($data[$id_filename], "/"));
                    $object = getobject ($data[$id_filename]);
                  }
                  // object name has been provided
                  else
                  {
                    $temp_location = $location;
                    $object = $data[$id_filename];
                  }

                  $temp_object = createfilename ($object, true);

                  // update content
                  if ($updateobject == true)
                  {
                    $temp_object = correctfile ($temp_location, $temp_object, "sys");

                    if (is_file (deconvertpath ($temp_location.$temp_object, "file", true)))
                    {
                      // get and load container from object
                      $object_info = getobjectinfo ($site, $temp_location, $temp_object, $user);
                      $contentfile = $object_info['content'];
                      $contentdata = loadcontainer ($contentfile, "work", $user);

                      if (!empty ($report) && empty ($contentdata))
                      {
                        echo "<span style=\"color:red;\">The content of object '".convertpath ($site, $temp_location.$temp_object, $cat)."' can't be loaded</span><br />\n";
                      }
                    }
                    else
                    {
                      if (!empty ($report)) echo "<span style=\"color:red;\">The object '".convertpath ($site, $temp_location.$temp_object, $cat)."' does not exist</span><br />\n";
                    }
                  }
                  // create new object
                  elseif ($createobject == true)
                  {
                    // create folder if missing
                    if ($createfolders == true && substr_count ($data[$id_filename], "/") > 0 && !is_dir ($temp_location))
                    {
                      $temp = createfolders ($site, getlocation ($temp_location), getobject ($temp_location), $user);

                      if (!empty ($report))
                      {
                        if (!empty ($temp['result'])) echo "<span style=\"color:green;\">Created new folder '".convertpath ($site, $temp_location, $cat)."' for the import</span><br />\n";
                        else echo "<span style=\"color:red;\">The folder '".convertpath ($site, $temp_location, $cat)."' could not be created</span><br />\n";
                      }
                    }

                    // create new object
                    $temp = createobject ($site, $temp_location, $object, $template, $user);

                    if (!empty ($temp['result']) && !empty ($temp['container_id']))
                    {
                      if (!empty ($report)) echo "<span style=\"color:green;\">Created new object '".$object."' for the import</span><br />\n";

                      $contentfile = $temp['container'];
                      $contentdata = loadcontainer ($temp['container_id'], "work", $user);
                    }
                    else
                    {
                      if (!empty ($report)) echo "<span style=\"color:red;\">The object '".$object."' could not be created. (".strip_tags ($temp['message']).")</span><br />\n";
                    }
                  }
                }

                // get text nodes content and text type
                if ($c != $id_filename && $c != $id_containerid)
                {
                  if ($data[$c] != "")
                  {
                    // detect content text type if it has not already been analyzed and is not "k", "d", or "f"
                    if (empty ($type[$id_content[$c]]) || (!empty ($type[$id_content[$c]]) && $type[$id_content[$c]] != "f" && $type[$id_content[$c]] != "k" && $type[$id_content[$c]] != "d"))
                    {
                      // unformatted text
                      $type[$id_content[$c]] = "u";

                      // formatted text
                      if (strpos ("_".$data[$c], "<") > 0 && strpos ("_".$data[$c], ">") > 0)
                      {
                        $type[$id_content[$c]] = "f";
                      }
                      // date
                      elseif (substr_count ("_".$data[$c], "/") == 2 || substr_count ("_".$data[$c], "-") == 2 && strlen ($data[$c]) < 20)
                      {
                        $type[$id_content[$c]] = "d";
                      }
                      // keywords
                      else
                      {
                        // try to detect keywords
                        $count = array();
                        reset ($delimiters_keywords);
  
                        foreach ($delimiters_keywords as $key)
                        {
                          $count[$key] = substr_count ($data[$c], $key);
                        }
  
                        if (max ($count) > 0)
                        {
                          // use highest count for delimiter
                          $temp = array_keys ($count, max ($count));
    
                          if (!empty ($temp[0]))
                          {
                            $seperator = $temp[0];
    
                            // verify if text holds keywords based on ratio 12:1 and convert to comma
                            if (mb_strlen ($data[$c]) < ((substr_count ($data[$c], $seperator) + 1) * 12))
                            {
                              $data[$c] = str_replace ($seperator, ",", $data[$c]);
                              $type[$id_content[$c]] = "k";
                            }
                          }
                        }
                      }
                    }

                    // conversions based on text type
                    if ($type[$id_content[$c]] == "k")
                    {
                      // try to detect keywords
                      $count = array();
                      reset ($delimiters_keywords);

                      foreach ($delimiters_keywords as $key)
                      {
                        $count[$key] = substr_count ($data[$c], $key);
                      }

                      if (max ($count) > 0)
                      {
                        // use highest count for delimiter
                        $temp = array_keys ($count, max ($count));
  
                        if (!empty ($temp[0]))
                        {
                          $seperator = $temp[0];
  
                          // verify if text holds keywords based on ratio 12:1 and convert to comma
                          if (mb_strlen ($data[$c]) < ((substr_count ($data[$c], $seperator) + 1) * 12))
                          {
                            $data[$c] = str_replace ($seperator, ",", $data[$c]);
                          }
                        }
                      }
                    }

                    // assign text
                    $text[$id_content[$c]] = $data[$c];
                  }
                }
              }

              if (!empty ($contentdata) && !empty ($contentfile) && sizeof ($text) > 0 && !empty ($type) && !empty ($art) && !empty ($user))
              {
                $contentdata_new = settext ($site, $contentdata, $contentfile, $text, $type, $art, $user, $user, $charset);

                // on error
                if ($contentdata_new == false)
                {
                  if (!empty ($report)) echo "<span style=\"color:red;\">CSV content for '".$contentfile."' could not be imported into container</span><br />\n";
                  
                  $errcode = "10198";
                  $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|CSV content for '".$contentfile."' could not be imported into container";
                }
                // save container on success
                else
                {
                  // eventsystem
                  if (!empty ($eventsystem['onsaveobject_pre']) && empty ($eventsystem['hide']) && function_exists ("onsaveobject_pre")) 
                  {
                    $contentdataevent = onsaveobject_pre ($site, $cat, $temp_location, $temp_object, $contentfile, $contentdata_new, $user);

                    // check if event returns a string, if so, the event returns the container and not true or false 
                    if (!empty ($contentdataevent) && strlen ($contentdataevent) > 10) $contentdata_new = $contentdataevent;
                  }

                  // date 
                  $date = date ("Y-m-d H:i:s", time());

                  // insert new date into content file
                  $contentdata_new = setcontent ($contentdata_new, "<hyperCMS>", "<contentdate>", $date, "", "", true);

                  // set encoding
                  $charset_old = getcharset ("", $contentdata_new); 

                  if (empty ($charset_old['charset']) || strtolower ($charset_old['charset']) != strtolower ($charset))
                  {
                    // write XML declaration parameter for text encoding
                    if ($charset != "") $contentdatanew = setxmlparameter ($contentdata_new, "encoding", $charset);
                  }

                  // save working xml content container file
                  $savefile = savecontainer ($contentfile, "work", $contentdata_new, $user);

                  if ($savefile == false)
                  {
                    if (!empty ($report)) echo "<span style=\"color:red;\">Container ".$contentfile." could not be saved after CSV import</span><br />\n";

                    $errcode = "10199";
                    $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Container ".$contentfile." could not be saved after CSV import";
                  }
                  else
                  {
                    // eventsystem
                    if (!empty ($eventsystem['onsaveobject_post']) && empty ($eventsystem['hide']) && function_exists ("onsaveobject_post")) 
                    {
                      onsaveobject_post ($site, $cat, $temp_location, $temp_object, $contentfile, $contentdata_new, $user);
                    }

                    if (!empty ($report)) echo "<span style=\"color:green;\">CSV content for ".convertpath ($site, $temp_location.$object, $cat)." (".$contentfile.") has been successfully imported</span><br />\n";

                    $errcode = "00199";
                    $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|information|".$errcode."|CSV content for ".convertpath ($site, $location.$object, $cat)." (".$contentfile.") has been successfully imported and saved into container by user '".$user."' (".getuserip().")";
                  }
                }
              }
            }

            $row++;
          }
        }
      }

      fclose ($handle);
      savelog (@$error);

      if ($row > 1 && !empty ($savefile)) return true;
      else return false;
    }
    else
    {
      if (!empty ($report)) echo "<span style=\"color:red;\">CSV file '".$file_csv."' could not be loaded</span><br />\n";
      return false;
    }
  }
  else
  {
    if (!empty ($report)) echo "<span style=\"color:red;\">The location '".str_replace (".folder", "", $location)."' or the CSV file '".$file_csv."' is missing</span><br />\n";
    return false;
  }
}


 // ----------------------------------------- importtaxonomy ------------------------------------------
// function: importtaxonomy()
// input: publication name [string], recreate taxonomy relations for all objects of the publication [boolean] (optional)
// output: true / false

// description:
// Executes the import job for the taxonomy of a publication.

function importtaxonomy ($site, $recreate=false)
{
  global $mgmt_config;

  if (valid_publicationname ($site))
  {
    // target CSV file
    $file_csv = $mgmt_config['abs_path_data']."include/".$site.".taxonomy.csv";

    // import profile file
    $import_profile = $mgmt_config['abs_path_data']."config/".$site.".taxonomy.import.dat";

    if (!empty ($mgmt_config['abs_path_data']) && is_file ($import_profile))
    {
      // load import jobs
      $record_array = file ($import_profile);

      if (is_array ($record_array) && sizeof ($record_array) > 0)
      {
        foreach ($record_array as $record)
        {
          if (trim ($record) != "")
          {
            // taxonomy import profile since version 10.0.7
            list ($site, $job, $period, $importtype, $importfile, $delimiter, $enclosure, $deletefiles) = explode ("|", trim ($record));

            // if job is activated
            if (
                  !empty ($job) && 
                  (
                    // for each first day of the month
                    ($period == "monthly" && date ("d", time()) == "01") ||
                    // for each sunday
                    ($period == "weekly" && strtolower (date ("D", time())) == "sun") || 
                    // for each day
                    $period == "daily"
                  )
            )
            {
              // get file contents
              if (!empty ($importfile))
              {
                $filedata = HTTP_Get_contents ($importfile);

                // load or get file
                if (!empty ($filedata)) $save = savefile (getlocation ($file_csv), getobject ($file_csv), $filedata);

                // remove source file
                if (!empty ($deletefiles) && is_file ($importfile)) unlink ($importfile);
          
                if (!empty ($save) && is_file ($file_csv))
                {
                  // load imported CSV file and try to detect enclosure and character set
                  $import = load_csv ($file_csv, $delimiter , "", "", "utf-8");

                  // the index starts with 1
                  if (is_array ($import) && !empty ($import[1]['level']))
                  {
                    $result = create_csv ($import, $site.".taxonomy.csv", $mgmt_config['abs_path_data']."include/", ";", '"', "utf-8", "utf-8", false);

                    if ($result && $recreate == true)
                    {
                      // remove taxonomy relations for all objects
                      if (function_exists ("rdbms_deletepublicationtaxonomy")) rdbms_deletepublicationtaxonomy ($site, true);

                      // set taxonomies relations for all objects
                      if (function_exists ("rdbms_setpublicationtaxonomy")) rdbms_setpublicationtaxonomy ($site, true);
                    }
                  }
                }
              }
            }

            break;
          }
        }
      }
    }
  }

  return false;
}

// --------------------------------------- loadtaxonomy -------------------------------------------
// function: loadtaxonomy ()
// input: publication name [string], return rows starting with row number [integer] (optional), return number of rows [integer] (optional), return total number of rows [boolean] (optional), load default taxonomy [bollean] (optional)
// output: true / false

// description:
// Generates an array from a taxonomy definition file located in data/include/ to be used for presentation or CSV export.

function loadtaxonomy ($site, $start=1, $perpage=100000, $count=false, $load_default=false)
{
  global $mgmt_config;

  // load languages
  $languages = getlanguageoptions ();

  // load CSV source of taxonomy of publication (if available)
  if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."include/".$site.".taxonomy.csv") && empty ($load_default))
  {
    $csv = $mgmt_config['abs_path_data']."include/".$site.".taxonomy.csv";
  }
  // load default taxonomy (if available)
  elseif (is_file ($mgmt_config['abs_path_data']."include/default.taxonomy.csv") && !empty ($load_default))
  {
    $csv = $mgmt_config['abs_path_data']."include/default.taxonomy.csv";
  }
  else
  {
    $csv = false;
  }

  // deployed taxonomy of the publication (if available)
  if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."include/".$site.".taxonomy.inc.php") && empty ($load_default))
  {
    $deployed = $mgmt_config['abs_path_data']."include/".$site.".taxonomy.inc.php";
  }
  // load default taxonomy (if available)
  elseif (is_file ($mgmt_config['abs_path_data']."include/default.taxonomy.inc.php") && !empty ($load_default))
  {
    $deployed = $mgmt_config['abs_path_data']."include/default.taxonomy.inc.php";
  }
  else
  {
    $deployed = false;
  }

  // load taxonomy of publication (CSV source)
  if (!empty ($csv) && is_file ($csv))
  {
    // load CSV file
    $taxonomy = load_csv ($csv, ";", '"', "utf-8", "utf-8");

    // prepare taxonomy
    if (!empty ($taxonomy) && is_array ($taxonomy))
    {
      // collect rows from existing taxonomy
      $result = array();

      // count rows
      if (!empty ($count)) $result['count'] = sizeof ($taxonomy);

      reset ($taxonomy);

      foreach ($taxonomy as $row => $col_array)
      { 
        // paging
        if ($row > ($start + $perpage - 1)) break;

        if ($row >= $start)
        {
          $result[$row]  = $col_array;
        }
      }
    }
  }
  // load taxonomy of publication (deployed PHP source)
  elseif (!empty ($deployed) && is_file ($deployed))
  {
    // load PHP file
    include ($deployed);

    // prepare taxonomy
    if (!empty ($taxonomy) && is_array ($taxonomy))
    {
      // collect rows from existing taxonomy
      $result = array();

      // count rows
      if (!empty ($count)) $result['count'] = sizeof ($taxonomy);

      reset ($taxonomy);
      $i = 1;
      $langcode = getfirstkey ($taxonomy);

      foreach ($taxonomy[$langcode] as $levelpath => $label)
      {
        // paging
        if ($i > ($start + $perpage - 1)) break;

        if ($i >= $start)
        {
          $level = substr_count ($levelpath, "/") - 1;
          $result[$i]['level'] = $level;
        }

        $i++;
      }

      reset ($taxonomy);

      foreach ($languages as $langcode => $langname)
      {
        // cells with values
        if (!empty ($taxonomy[$langcode]) && is_array ($taxonomy[$langcode]))
        {
          $i = 1;

          foreach ($taxonomy[$langcode] as $levelpath => $label)
          {
            // paging
            if ($i > ($start + $perpage - 1)) break;

            if ($i >= $start)
            {
              $result[$i][$langcode] = trim ($label);
            }

            $i++;
          }
        }
      }
    }
  }

  if (!empty ($result) && is_array ($result) && sizeof ($result) > 0) return $result;
  else return false;
}

// --------------------------------------- savetaxonomy -------------------------------------------
// function: savetaxonomy ()
// input: publication name [string], new taxonomy with row number and languages as keys [array], replace rows starting with row number [integer], replace rows ending with row number [integer], 
//        text IDs to analyze [array] (optional)
// output: true / false

// description:
// Generates an array from a taxonomy definition file located in data/include/ to be used for presentation or CSV export.

function savetaxonomy ($site, $taxonomy, $saveindex_start, $saveindex_stop, $text_ids="*Null*")
{
  global $mgmt_config;

  // save text IDs
  if ($text_ids != "*Null*" && valid_publicationname ($site))
  {
    savefile ($mgmt_config['abs_path_data']."include/", $site.".taxonomy.filter.csv", $text_ids);
  }

  // load taxonomy
  $taxonomy_old = loadtaxonomy ($site);

  if (is_array ($taxonomy) && !empty ($taxonomy[$saveindex_start]))
  {
    // get languages from first entry of the new taxonomy
    $lang_array = array_keys ($taxonomy[$saveindex_start]);

    // get languages from same entry of the loaded old taxonomy
    if (is_array ($taxonomy_old))
    {
      $lang_old_array = array_keys ($taxonomy_old[$saveindex_start]);

      // new languages have been added to the taxonomy
      if (sizeof ($lang_old_array) < sizeof ($lang_array))
      {
        $lang_new_array = array_diff ($lang_array, $lang_old_array);
      }
      // languages have been removed from the taxonomy
      elseif (sizeof ($lang_old_array) > sizeof ($lang_array))
      {
        $lang_remove_array = array_diff ($lang_old_array, $lang_array);
      }
    }

    // update taxonomy
    if (valid_publicationname ($site) && $saveindex_start >= 0 && $saveindex_stop >= 0)
    {
      // merge old and new taxonomy definition
      if (is_array ($taxonomy_old))
      {
        $id = 1;

        foreach ($taxonomy_old as $row => $old_array)
        {
          // untouched rows (outside of saveindex)
          if ($row < $saveindex_start || $row > $saveindex_stop)
          {
            // add new languages
            if (!empty ($lang_new_array)) foreach ($lang_new_array as $temp) $old_array[$temp] = "";

            // sort by language
            ksort ($old_array);

            // move 'level' key to first psoition
            $temp = $old_array['level'];
            unset ($old_array['level']);
            $old_array = array_merge (array('level'=>$temp), $old_array);

            $taxonomy_new[$id] = $old_array;
            $id++;
          }
          // edited/changed rows
          elseif (is_array ($taxonomy) && empty ($updated))
          {
            foreach ($taxonomy as $new_array)
            {
              // remove languages
              if (!empty ($lang_remove_array) && is_array ($lang_remove_array))
              {
                foreach ($lang_remove_array as $temp) unset ($new_array[$temp]);
              }

              // sort by language
              ksort ($new_array);

              // move 'level' key to first psoition
              $temp = $new_array['level'];
              unset ($new_array['level']);
              $new_array = array_merge (array('level'=>$temp), $new_array);

              $taxonomy_new[$id] = $new_array;
              $id++;
            }

            $updated = true;
          }
        }
      }
      // no old taxonomy definition
      else
      {
        $taxonomy_new = $taxonomy;
      }

      // verify taxonomy languages
      if (!empty ($taxonomy_new) && is_array ($taxonomy_new))
      {
        // keep language
        $keep = array();

        reset ($taxonomy_new);

        foreach ($taxonomy_new as $row => $temp_array)
        {
          foreach ($temp_array as $langcode => $label)
          {
            if (trim ($label) != "" && is_activelanguage ($site, $langcode)) $keep[$langcode] = true;

            $taxonomy_new[$row][$langcode] = trim ($label);
          }
        }

        // set English as default if no language option has been defined for the publication
        if (sizeof ($keep) < 1) $keep['en'] = true;

        // remove empty language columns
        if (sizeof ($keep) > 0)
        {
          reset ($taxonomy_new);

          foreach ($taxonomy_new as $row => $temp_array)
          {
            foreach ($temp_array as $langcode => $label)
            {
              if (empty ($keep[$langcode]) && $langcode != "level") unset ($taxonomy_new[$row][$langcode]);
            }
          }
        }
  
        // save data
        return create_csv ($taxonomy_new, $site.".taxonomy.csv", $mgmt_config['abs_path_data']."include/", ";", '"', "utf-8", "utf-8", false);
      }
      // nothing to update
      else return true;
    }
    // save new taxonomy
    elseif (valid_publicationname ($site) && is_array ($taxonomy))
    {
      // save data
      return create_csv ($taxonomy, $site.".taxonomy.csv", $mgmt_config['abs_path_data']."include/", ";", '"', "utf-8", "utf-8", false);
    }
  }

  return false;
}

// --------------------------------------- createtaxonomy -------------------------------------------
// function: createtaxonomy ()
// input: publication name [string] (optional), recreate taxonomy file [boolean] (optional)
// output: true / false

// description:
// Generates an array from a taxonomy definition file (CSV) and saves the PHP file in data/include/publication-name.taxonomy.inc.php.
// Recreates the taxonomy for all objects if the taxonomy defintion has been uodated.

function createtaxonomy ($site_name="", $recreate=false)
{
  global $mgmt_config, $user;

  // collect and compare files
  $dir = $mgmt_config['abs_path_data']."include/";

  // initialize
  $error = array();
  $file_array = array();
  $site_memory = array();

  $scandir = scandir ($dir);

  if ($scandir)
  {
    foreach ($scandir as $file)
    {
      // only taxonomy definition files
      if ($file != "." && $file != ".." && is_file ($dir.$file) && strpos ($file, ".taxonomy.csv") > 0)
      {
        list ($site, $rest) = explode (".", $file);

        if (empty ($site_name) || $site == $site_name)
        {
          // if definition file is younger than generated PHP file
          if (!is_file ($dir.$site.".taxonomy.inc.php") || (is_file ($dir.$site.".taxonomy.inc.php") && filemtime ($dir.$file) > filemtime ($dir.$site.".taxonomy.inc.php")))
          {
            $recreate = true;
          }

          // define file
          $file_array[$site]= $dir.$file;
          $site_memory[] = $site;
        }
      }
    }
  }

	// create taxonomy
  if (is_array ($file_array) && sizeof ($file_array) > 0)
  {
    foreach ($file_array as $site => $file)
    {
      $result = array();

      if (valid_publicationname ($site) && is_file ($file))
      {
        // load CSV file
        $data = load_csv ($file, ";", '"', "utf-8", "utf-8");

        if (is_array ($data) && sizeof ($data) > 0)
        {
          // taxonomy ID of each taxonomy element must be unique
          $id = 1;

          // rows
          foreach ($data as $row => $temp_array)
          {
            // columns
            foreach ($temp_array as $lang => $label)
            {
              // hierarchy level of the keyword item
              if ($lang == "level")
              {
                $level = $label;

                // define parent ID based on level comparison
                if ($level == 1)
                {
                  $path = "";
                }
                // next level
                elseif ($level > $level_prev)
                {
                  $path = $path."/".$id_prev;
                }
                // previous level
                elseif ($level < $level_prev)
                {
                  $diff = $level_prev - $level;
                  for ($i=1; $i<=$diff; $i++) $path = substr ($path, 0, strrpos ($path, "/"));
                }

                // set previous memory
                $level_prev = $level;
                $id_prev = $id;
              }
              // keyword for a specfic entry for a language
              else
              {
                // clean text
                $label = str_replace (array("\""), array(""), $label);

                // escape commas
                $label = str_replace (",", "¸", $label);

                // create array element
                $result[] = "\$taxonomy['".$lang."']['".$path."/".$id."/'] = \"".trim ($label)."\";";
              }
            }

            // id of next row
            $id++;
          }

          // save result for publication
          if (sizeof ($result) > 0)
          {
            $resultdata = "<?php\n\$taxonomy = array();\n".implode ("\n", $result)."\n?>";

            $savefile = savefile ($dir, $site.".taxonomy.inc.php", $resultdata);

            if (!empty ($recreate) && $savefile == true)
            {
              // write and close session (important for non-blocking: any page that needs to access a session now has to wait for the long running script to finish execution before it can begin)
              $session_id = suspendsession ("createtaxonomy.".$site, $user);

              // remove and recreate the taxonomy for all objects of the publication
              if (in_array ("default", $site_memory) && empty ($done_setpublicationtaxonomy))
              {
                rdbms_setpublicationtaxonomy ("", true);
                $done_setpublicationtaxonomy = true;
              }
              elseif ($site != "default")
              {
                rdbms_setpublicationtaxonomy ($site, true);
              }

              $errcode = "00209";
              $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|information|".$errcode."|Taxonomy of publication '".$site."' has been created";

              // restart session (that has been previously closed for non-blocking procedure)
              revokesession ("createtaxonomy.".$site, $user, $session_id);
            }
            else
            {
              $errcode = "10209";
              $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Taxonomy of publication '".$site."' could not be created";
            }
          }
        }
      }
    }

    // save log
    savelog (@$error);

    return true;
  }
  
  return false;
}

// --------------------------------------- deletetaxonomy -------------------------------------------
// function: deletetaxonomy ()
// input: publication name [string]
// output: true / false

// description:
// Deletes the taxonomy definition file (CSV) and PHP file in data/include/publication-name.taxonomy.inc.php.
// Removes the index of the taxonomy for all objects.

function deletetaxonomy ($site)
{
  global $mgmt_config;

  // initialize
  $error = array();

  if (valid_publicationname ($site))
  {
    // collect and compare files
    $dir = $mgmt_config['abs_path_data']."include/";

    // delete CSV file
    if (is_file ($dir.$site.".taxonomy.csv")) deletefile ($dir, $site.".taxonomy.csv", 0);

    // delete PHP file
    if (is_file ($dir.$site.".taxonomy.inc.php")) deletefile ($dir, $site.".taxonomy.inc.php", 0);

    // remove all taxonomy entries from publication
    $result = rdbms_deletepublicationtaxonomy ($site, true);

    if ($result)
    {
      $errcode = "00219";
      $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|information|".$errcode."|Taxonomy of publication '".$site."' has been removed";
    }
    else
    {
      $errcode = "10219";
      $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Taxonomy of publication '".$site."' could not be removed";
    }

    // save log
    savelog (@$error);

    return $result;
  }
  
  return false;
}

// --------------------------------------- splitkeywords -------------------------------------------
// function: splitkeywords ()
// input: comma seperated keywords [string], character set [string] (optional)
// output: keywords as array / false on error

// description:
// Generates a keyword list from a text by splitting and transforming the comma separated string.

function splitkeywords ($keywords, $charset="UTF-8")
{
  if (trim ($keywords) != "")
  {
    $result_array = array();
    $keyword_array = explode (",", $keywords);

    foreach ($keyword_array as $keyword)
    {
      // max. length of keyword must not exceed 100 and must not include tags
      if (is_keyword ($keyword))
      {
        $tag_start = strpos ("_".$keyword, "<");
        $tag_end = strpos ("_".$keyword, ">");

        if ($tag_start > 0 && $tag_end > 0 && $tag_start < $tag_end) $tag_included = true;
        else $tag_included = false;

        if (!$tag_included)
        {
          $keyword = cleancontent ($keyword, $charset);
          $result_array[] = trim ($keyword);
        }
      }
    }

    if (is_array ($result_array) && sizeof ($result_array) > 0) return $result_array;
  }

  return false;
}

// ---------------------- copymetadata -----------------------------
// function: copymetadata()
// input: path to source file [string], path to destination file [string]
// output: true / false

// description:
// Copies all meta data from source to destination file using EXIFTOOL

function copymetadata ($file_source, $file_dest)
{
  global $mgmt_config, $mgmt_mediametadata, $user;
  
	if ($file_source != "" && $file_dest != "" && !empty ($mgmt_mediametadata) && is_array ($mgmt_mediametadata))
  {
    // initialize
    $error = array();

    // get source file extension
    $file_source_ext = strtolower (strrchr ($file_source, "."));

    // copy metadata from original file using EXIFTOOL
    foreach ($mgmt_mediametadata as $extensions => $executable)
    {
      if (substr_count ($extensions.".", $file_source_ext.".") > 0 && $executable != "")
      {
        // ------------- get source publication, location and media file name ---------------
        $site_source = getpublication ($file_source);
        $location_source = getlocation ($file_source);
        $media_source = getobject ($file_source);

        // prepare source media file
        $temp_source = preparemediafile ($site_source, $location_source, $media_source, $user);

        // if encrypted
        if (!empty ($temp_source['result']) && !empty ($temp_source['crypted']) && !empty ($temp_source['templocation']) && !empty ($temp_source['tempfile']))
        {
          $file_source = $temp_source['templocation'].$temp_source['tempfile'];
        }
        // if restored
        elseif (!empty ($temp_source['result']) && !empty ($temp_source['restored']) && !empty ($temp_source['location']) && !empty ($temp_source['file']))
        {
          $file_source = $temp_source['location'].$temp_source['file'];
        }

        // verify source media file
        if (!is_file ($file_source)) return false;

        // --------------- get destination publication, location and media file name ---------------
        $site_dest = getpublication ($file_dest);
        $location_dest = getlocation ($file_dest);
        $media_dest = getobject ($file_dest);

        // prepare destination media file
        $temp_dest = preparemediafile ($site_dest, $location_dest, $media_dest, $user);

        // if encrypted
        if (!empty ($temp_dest['result']) && !empty ($temp_dest['crypted']) && !empty ($temp_dest['templocation']) && !empty ($temp_dest['tempfile']))
        {
          $file_dest = $temp_dest['templocation'].$temp_dest['tempfile'];
        }
        // if restored
        elseif (!empty ($temp_dest['result']) && !empty ($temp_dest['restored']) && !empty ($temp_dest['location']) && !empty ($temp_dest['file']))
        {
          $file_dest = $temp_dest['location'].$temp_dest['file'];
        }

        // verify destination media file
        if (!is_file ($file_dest)) return false;

        // get dimensions of destination file
        $imagesize = getmediasize ($file_dest);
  
        // correct EXIF width and height
        $exif_widthheight = "";

        if (!empty ($imagesize['width']) && !empty ($imagesize['height']))
        {
          $exif_widthheight = "-ExifImageWidth=".intval($imagesize['width'])." -ExifImageHeight=".intval ($imagesize['height']);
        }

        // copy meta data without orientation since the image will be autorotated by function createmedia
        $cmd = $executable." -overwrite_original -TagsFromFile \"".shellcmd_encode ($file_source)."\" \"-all:all>all:all\" --Orientation --Rotation --ImageWidth --ImageHeight ".$exif_widthheight." \"".shellcmd_encode ($file_dest)."\"";

        // execute and redirect stderr (2) to stdout (1)
        @exec ($cmdv." 2>&1", $output, $errorCode);

        // delete temp files
        if (!empty ($temp_source['crypted']) && !empty ($temp_source['templocation']) && !empty($temp_source['tempfile']) && is_file ($temp_source['templocation'].$temp_source['tempfile']))
        {
          deletefile ($temp_source['templocation'], $temp_source['tempfile'], 0);
        }

        if (!empty ($temp_dest['crypted']) && !empty ($temp_source['templocation']) && !empty($temp_source['tempfile']) && is_file ($temp_source['templocation'].$temp_source['tempfile']))
        {
          deletefile ($temp_dest['templocation'], $temp_dest['tempfile'], 0);
        }

        // on error
        if ($errorCode)
        {
          $errcode = "20241";
          $error[] = $mgmt_config['today']."|hypercms_meta.php|error|".$errcode."|Execution of EXIFTOOL (code:".$errorCode.", command:".$cmd.") failed in copy metadata to file '".getobject ($file_dest)."' \t".implode ("\t", $output);

          // save log
          savelog (@$error);
        }
        else
        {
          return true;
        }
      }
    }
	}
  
  return false;
}

// ------------------------- extractmetadata -----------------------------
// function: extractmetadata()
// input: path to image file [string]
// output: result array / false on error

// description:
// Extracts all meta data from a file using EXIFTOOL

function extractmetadata ($file)
{
  global $user, $mgmt_config, $mgmt_mediametadata;

  if (is_file ($file) && is_array ($mgmt_mediametadata))
  {
    // initialize
    $error = array();
    $result = array();
    $hide_properties = array ("version number", "file name", "directory", "file permissions", "app14 flags", "thumbnail", "xmp toolkit");

    // get file info
    $file_info = getfileinfo ("", $file, "comp");

    // define executable
    foreach ($mgmt_mediametadata as $extensions => $executable)
    {
      if (substr_count ($extensions.".", $file_info['ext'].".") > 0 && $executable != "")
      {
        // get publication, location and media file name
        $site = getpublication ($file);
        $location = getlocation ($file);
        $media = getobject ($file);

        // prepare media file
        $temp = preparemediafile ($site, $location, $media, $user);

        // if encrypted
        if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
        {
          $file = $temp['templocation'].$temp['tempfile'];
        }
        // if restored
        elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
        {
          $file = $temp['location'].$temp['file'];
        }

        // get image information using EXIFTOOL
        $cmd = $executable." -G \"".shellcmd_encode ($file)."\"";

        // execute and redirect stderr (2) to stdout (1)
        @exec ($cmd." 2>&1", $output, $errorCode);

        // delete temp file
        if (!empty ($temp['result']) && !empty ($temp['created']) && is_file ($temp['templocation'].$temp['tempfile']))
        {
          deletefile ($temp['templocation'], $temp['tempfile'], 0);
        }

        // on error
        if ($errorCode)
        {
          $errcode = "20247";
          $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Execution of EXIFTOOL (code:".$errorCode.", command:".$cmd.") failed for file '".getobject ($file)."' \t".implode ("\t", $output);
        }
        elseif (is_array ($output))
        {
          // EXIFTOOL result array
          foreach ($output as $line)
          {
            if (strpos ($line, " : ") > 0)
            {
              list ($property, $value) = explode (" : ", $line);

              if (trim ($property) != "" && trim ($value) != "")
              {
                $property = trim ($property);

                // extract group and property name
                if (strpos ($property, "[") == 0 && strpos ($property, "] ") > 0)
                {
                  list ($group, $property) = explode ("] ", substr ($property, 1));
                  $group = trim ($group);
                  $property = trim ($property);
                }
                else $group = "File";

                $hide = false;

                foreach ($hide_properties as $hide_property)
                {
                  if (substr_count (strtolower($property), strtolower($hide_property)) > 0)
                  {
                    $hide = true;
                    break;
                  }
                }

                if ($hide == false && $group != "" && $property != "") $result[$group][$property] = trim ($value);
              }
            }
          }

          if (sizeof ($result) > 0) return $result;
        }
      }
    }
  }
  
  return false;
}

// ------------------------- xmlobject2array -----------------------------
// function: xmlobject2array()
// input: XML [object], namespace [array] (optional)
// output: result array / false

// description:
// Converts an xmlobject to an array, provided by xaviered at gmail dot com

function xmlobject2array ($obj, $namespace="")
{
  if (is_object ($obj))
  {
    // get namespace
    if (!is_array ($namespace))
    {
      $namespace = $obj->getDocNamespaces (true);
      $namespace[NULL] = NULL;
    }

    $children = array();
    $attributes = array();
    $result = array();

    // XML tag name and text-content
    $name = (string)$obj->getName(); 
    $text = trim ((string)$obj);
    if (strlen ($text) <= 0) $text = NULL;
 
    // get info for all namespaces
    foreach ($namespace as $ns => $nsUrl)
    {
      /*
      // atributes
      $objAttributes = $obj->attributes ($ns, true);

      foreach ($objAttributes as $attributeName => $attributeValue)
      {
        $attribName = trim ((string)$attributeName);
        $attribVal = trim ((string)$attributeValue);
        if (!empty ($ns)) $attribName = $ns.':'.$attribName;
        $attributes[$attribName] = $attribVal;
      }
      */

      if (!empty ($ns)) $fullname = $ns.':'.$name;
 
      // children
      $objChildren = $obj->children ($ns, true);

      $result_sub = array();

      if (sizeof ($objChildren) > 0)
      {
        foreach ($objChildren as $childname => $child)
        {
          $childname = (string)$childname;
          if (!empty ($ns)) $fullchildname = $ns.':'.$childname;
          $childnamespace[$ns] = $nsUrl;

          $children = xmlobject2array ($child);
          if (is_array ($children)) $result_sub[$childname] = $children;
        }
      }
      elseif ($text != "")
      {
        $result_sub[$name] = $text;
      }

      if (sizeof ($result_sub) > 0) $result = array_merge ($result, $result_sub);
    }

    if (sizeof ($result) > 0) return $result;
    else return false;
  }
  else return false;
} 

// ------------------------- id3_getdata -----------------------------
// function: id3_getdata()
// input: path to audio file [string]
// output: result array / false on error

// description:
// Requires getID3 library since EXIFTOOL cannot write ID3 tags so far

function id3_getdata ($file)
{
  global $mgmt_config, $hcms_ext;

	if (is_file ($file) && is_file ($mgmt_config['abs_path_cms']."library/getID3/getid3/getid3.php"))
  {
    //initialize
    $result = array();

    if (!is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

    // load getID3 library
    require_once ($mgmt_config['abs_path_cms']."library/getID3/getid3/getid3.php");

    // get file info
    $file_info = getfileinfo ("", $file, "comp");

    // check file extension
    if (substr_count (strtolower ($hcms_ext['audio']).".", $file_info['ext'].".") == 0) return false;

    // time limit
    set_time_limit (30);

    $file = realpath ($file);

    // initialize getID3 engine
    $getID3 = new getID3;

    // analyze file
		$file_info = $getID3->analyze ($file);

    // combine all/any available tag formats in 'comments'
		getid3_lib::CopyTagsToComments ($file_info);

    // Example outputs can be:
    // $file_info['comments_html']['artist'][0] ... artist from any/all available tag formats
    // $file_info['tags']['id3v2']['title'][0]  ... title from ID3v2
    // $file_info['audio']['bitrate']           ... audio bitrate
    // $file_info['playtime_string']            ... playtime in minutes:seconds, formatted string
    // $file_info['comments']['picture'][0]['data'] ... album art

    if (is_array ($file_info))
    {
      // extract album art image
      if (isset ($file_info['comments']['picture'][0]) && !empty ($file_info['comments']['picture'][0]['data']))
      {
        // binary data
        $result['imagedata'] = $file_info['comments']['picture'][0]['data'];
        // mime-type
        if (!empty ($file_info['comments']['picture'][0]['image_mime']))
          $result['imagemimetype'] = trim (strtolower ($file_info['comments']['picture'][0]['image_mime']));
        else $result['imagemimetype'] = "image/jpeg";
        // width and height
        if (!empty ($file_info['comments']['picture'][0]['image_width'])) $result['imagewidth'] = $file_info['comments']['picture'][0]['image_width'];
        else $result['imagewidth'] = "";
        if (!empty ($file_info['comments']['picture'][0]['image_height'])) $result['imageheight'] = $file_info['comments']['picture'][0]['image_height'];
        else $result['imageheight'] = "";
        // image type (e.g. cover)
        if (!empty ($file_info['comments']['picture'][0]['picturetype'])) $result['imagetype'] = $file_info['comments']['picture'][0]['picturetype'];
        else $result['imagetype'] = "";
        // image description
        if (!empty ($file_info['comments']['picture'][0]['description'])) $result['description'] = $file_info['comments']['picture'][0]['description'];
        else $result['description'] = "";
        // image data length
        if (!empty ($file_info['comments']['picture'][0]['datalength'])) $result['datalength'] = $file_info['comments']['picture'][0]['datalength'];
        else $result['datalength'] = "";
      }

      // encoding
      if (!empty ($file_info['encoding'])) $result['encoding'] = $file_info['encoding'];
      else $result['encoding'] = "UTF-8";

      // extract text based data from 'comments'
      if (!empty ($file_info['comments']) && is_array ($file_info['comments']))
      {
        // title
        if (!empty ($file_info['comments']['title'][0])) $result['title'] = $file_info['comments']['title'][0];
        else $result['title'] = "";
        // artist
        if (!empty ($file_info['comments']['artist'][0])) $result['artist'] = $file_info['comments']['artist'][0];
        else $result['artist'] = "";
        // album
        if (!empty ($file_info['comments']['album'][0])) $result['album'] = $file_info['comments']['album'][0];
        else $result['album'] = "";
        // year
        if (!empty ($file_info['comments']['year'][0])) $result['year'] = $file_info['comments']['year'][0];
        else $result['year'] = "";
        // comment
        if (!empty ($file_info['comments']['comment'][0])) $result['comment'] = $file_info['comments']['comment'][0];
        else $result['comment'] = "";
        // track
        if (!empty ($file_info['comments']['track'][0])) $result['track'] = $file_info['comments']['track'][0];
        else $result['track'] = "";
        // genre (multiple entries possible)
        if (!empty ($file_info['comments']['genre'][0])) $result['genre'] = implode (", ", $file_info['comments']['genre']);
        else $result['genre'] = "";
        // track number
        if (!empty ($file_info['comments']['track_number'][0])) $result['tracknumber'] = $file_info['comments']['track_number'][0];
        else $result['tracknumber'] = "";
        // band
        if (!empty ($file_info['comments']['band'][0])) $result['band'] = $file_info['comments']['band'][0];
        else $result['band'] = "";
      }

      // return result
      if (sizeof ($result) > 0) return $result;
      else return false;
    }
    else return false;
  }
  else return false;
} 

// ------------------------- id3_writefile -----------------------------
// function: id3_writefile()
// input: abs. path to audio file [string], ID3 tag [array], keep existing ID3 data of file [boolean] (optional), move tempoarary file from unecrypted to encrypted [boolean] (optional)
// output: true / false on error

// description:
// Writes ID3 tags into audio file for supported file types and keeps the existing ID3 tags

function id3_writefile ($file, $id3, $keep_data=true, $movetempfile=true)
{
  global $user, $mgmt_config, $mgmt_mediametadata, $hcms_ext;

  $error = array();

  if (is_file ($file) && is_array ($id3) && is_array ($hcms_ext) && is_file ($mgmt_config['abs_path_cms']."library/getID3/getid3/getid3.php"))
  {
    // initialize
    $result = false;

    if (!is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

    // get container ID
    $container_id = getmediacontainerid ($file);

    // get file info
    $file_info = getfileinfo ("", $file, "comp");

    // check file extension
    if (substr_count (strtolower ($hcms_ext['audio']).".", $file_info['ext'].".") == 0) return false;

    // get publication, location and media file name
    $site = getpublication ($file);
    $location = getlocation ($file);
    $media = getobject ($file);

    // prepare media file
    $temp = preparemediafile ($site, $location, $media, $user);

    // if encrypted
    if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
    {
      $file = $temp['templocation'].$temp['tempfile'];
    }
    // if restored
    elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
    {
      $file = $temp['location'].$temp['file'];
    }

    if (is_file ($file))
    {
      $encoding = "UTF-8";

      // load getID3 library
      require_once ($mgmt_config['abs_path_cms']."library/getID3/getid3/getid3.php");
      require_once ($mgmt_config['abs_path_cms']."library/getID3/getid3/write.php");

      set_time_limit (30);
      $file = realpath ($file);

      // initialize getID3 engine
      $getID3 = new getID3;
      $getID3->setOption(array('encoding'=>$encoding));

      // initialize getID3 tag-writing module
      $tagwriter = new getid3_writetags;

      $tagwriter->filename = $file;

      // set tagformat version 'id3v1', 'id3v2.3';
      $tagwriter->tagformats = array('id3v2.3');

      // set options
      // if true will erase existing tag data and write only passed data; if false will merge passed data with existing tag data (experimental)
      $tagwriter->overwrite_tags = true;
      // if true removes other tag formats (e.g. ID3v1, ID3v2, APE, Lyrics3, etc) that may be present in the file and only write the specified tag format(s).
      // if false leaves any unspecified tag formats as-is.
      if ($keep_data == true || $keep_data == 1) $tagwriter->remove_other_tags = false;
      else $tagwriter->remove_other_tags = true;

      $tagwriter->tag_encoding = $encoding;

      $tagdata = array();

      // populate ID3 data array
      foreach ($id3 as $tag => $value)
      {
        if (strpos ($tag, ":") > 0) list ($namespace, $tag) = explode (":", $tag);

        if ($tag != "" && $namespace == "id3")
        {
          // correct tag for track number
          if ($tag == "tracknumber") $tag = "track_number";

          $tagdata[$tag] = array(html_decode ($value, $encoding));
        }
      }

      if (sizeof ($tagdata) > 0)
      {
        // set tags
        $tagwriter->tag_data = $tagdata;

        // write tags into file
        if ($tagwriter->WriteTags())
        {
          $result = true;
 
          // on warning
        	if (!empty ($tagwriter->warnings))
          {
            $errcode = "20280";
            $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|warning|".$errcode."|There were warnings when writing ID3 tags to file: ".getobject($file)."\t".implode("\t", $tagwriter->warnings);
        	}

          // save media stats and move temp file
          if ($movetempfile)
          {
            // write media information to DB with function rdbms_setmedia is used by function and service savecontent for the original media file only

            // encrypt and save file if required
            if ($temp['result']) movetempfile ($location, $media, true);

            // save to cloud storage
            if (function_exists ("savecloudobject")) savecloudobject ($site, $location, $media, $user);
          }
        }
        // on error
        else
        {
          $errcode = "20281";
          $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Failed to write ID3 tags to file: ".getobject($file);
        }
      }

      // save log
      savelog (@$error);
    }

    return $result;
  }
  else return false;
}

// ------------------------- id3_create -----------------------------
// function: id3_create()
// input: publication name [string], text from content container [array]
// output: ID3 tag array / false on error

// description:
// Defines ID3 tag array based on the media mapping of a publication.

function id3_create ($site, $text)
{
  global $mgmt_config;

  $error = array();

  if (valid_publicationname ($site) && is_array ($text) && !empty ($mgmt_config['abs_path_data']))
  {
    // try to load mapping configuration file of publication
    if (is_file ($mgmt_config['abs_path_data']."config/".$site.".media.map.php"))
    {
      @include ($mgmt_config['abs_path_data']."config/".$site.".media.map.php");
    }

    $result = array();

    // look in mapping definition (name => id)
    if (isset ($mapping) && is_array ($mapping))
    {
      foreach ($mapping as $tag => $id)
      {
        // extract type prefix and text ID
        if (strpos ($id, ":") > 0) list ($type, $id) = explode (":", $id); 

        // set ID3 tag (tag => value)
        if ($tag != "" && $id != "" && isset ($text[$id]) && substr ($tag, 0, 4) == "id3:")
        {
          $result[$tag] = $text[$id];
        }
      }

      return $result;
    }
    else
    {
      $errcode = "10109";
      $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Media mapping of publication '".$site."' could not be loaded";

      savelog (@$error);

      return false;
    }
  }
  else return false;
}

// ------------------------- xmp_getdata -----------------------------
// function: xmp_getdata()
// input: path to image file [string]
// output: result array / false on error

function xmp_getdata ($file)
{
  global $user, $mgmt_config, $hcms_ext;

	if (is_file ($file))
  {
    if (!is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

    // get file info
    $file_info = getfileinfo ("", $file, "comp");

    // check file extension
    if (substr_count (strtolower ($hcms_ext['image']).".", $file_info['ext'].".") == 0) return false;

    // get publication, location and media file name
    $site = getpublication ($file);
    $location = getlocation ($file);
    $media = getobject ($file);

    // prepare media file
    $temp = preparemediafile ($site, $location, $media, $user);

    // if encrypted
    if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
    {
      $file = $temp['templocation'].$temp['tempfile'];
    }
    // if restored
    elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
    {
      $file = $temp['location'].$temp['file'];
    }

    // load file
    $content = HTTP_Get_contents ($file);

    // delete temp file
    if (!empty ($temp['result']) && !empty ($temp['created']) && is_file ($temp['templocation'].$temp['tempfile']))
    {
      deletefile ($temp['templocation'], $temp['tempfile'], 0);
    }

    $result = array();

    if ($content != "" && strpos ($content, "</x:xmpmeta>") > 0)
    {
      $xmp_data_start = strpos ($content, '<x:xmpmeta');
      $xmp_data_end = strpos ($content, '</x:xmpmeta>');
      $xmp_length = $xmp_data_end - $xmp_data_start;
      $xmp_data = substr ($content, $xmp_data_start, $xmp_length + 12);

      if ($xmp_data != "")
      {
        $xmp = simplexml_load_string ($xmp_data);
        $result = xmlobject2array ($xmp);

        if (sizeof ($result) > 0) return $result;
        else return false;
      }
      else return false;
    }
    else return false;
	}
  else return false;
}

// ------------------------- xmp_writefile -----------------------------
// function: xmp_writefile()
// input: abs. path to image file [string], XMP tag [array], keep existing XMP data of file [boolean] (optional), move tempoarary file from unecrypted to encrypted [boolean] (optional)
// output: true / false on error

// description:
// Writes XMP tags into image file for supported file types and keeps the existing XMP tags

function xmp_writefile ($file, $xmp, $keep_data=true, $movetempfile=true)
{
  global $user, $mgmt_config, $mgmt_mediametadata, $hcms_ext;

  if (is_file ($file) && is_array ($xmp) && is_array ($mgmt_mediametadata))
  {
    if (!is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

    // get container ID
    $container_id = getmediacontainerid ($file);

    // get file info
    $file_info = getfileinfo ("", $file, "comp");

    // check file extension
    if (substr_count ($hcms_ext['image'].".", $file_info['ext'].".") == 0) return false;

    // get publication, location and media file name
    $site = getpublication ($file);
    $location = getlocation ($file);
    $media = getobject ($file);

    // prepare media file
    $temp = preparemediafile ($site, $location, $media, $user);

    // if encrypted
    if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
    {
      $file = $temp['templocation'].$temp['tempfile'];
    }
    // if restored
    elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
    {
      $file = $temp['location'].$temp['file'];
    }

    if (is_file ($file))
    {
      // define executable
      foreach ($mgmt_mediametadata as $extensions => $executable)
      {
        if (substr_count ($extensions.".", $file_info['ext'].".") > 0 && $executable != "")
        {
          // remove all XMP tags from file
          if ($keep_data == false || $keep_data == 0)
          {
            $cmd = $executable." -overwrite_original -r -XMP-crss:all= \"".shellcmd_encode ($file)."\"";

            // execute and redirect stderr (2) to stdout (1)
            @exec ($cmd." 2>&1", $output, $errorCode);

            // on error
            if ($errorCode)
            {
              $errcode = "20242";
              $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Execution of EXIFTOOL (code:".$errorCode.", command:".$cmd.") failed for XMP injection into file '".getobject ($file)."' \t".implode ("\t", $output);
            }
          }

          // inject XMP tags into file
          foreach ($xmp as $tag => $value)
          {
            if (strpos ($tag, ":") > 0) list ($namespace, $tag) = explode (":", $tag);

            if ($tag != "" && ($namespace == "dc" || $namespace == "photoshop"))
            {
              $cmd = $executable." -overwrite_original -xmp:".$tag."=\"".shellcmd_encode (html_decode ($value, "UTF-8"))."\" \"".shellcmd_encode ($file)."\"";

              // execute and redirect stderr (2) to stdout (1)
              @exec ($cmd." 2>&1", $output, $errorCode);

              // on error
              if ($errorCode)
              {
                $errcode = "20243";
                $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Execution of EXIFTOOL (code:".$errorCode.", command:".$cmd.") failed for XMP injection into file '".getobject ($file)."' \t".implode ("\t", $output);
              }
            }
          }

          // appending data to a file ensures that the previous write process is finished
          avoidfilecollision ($media);

          // save media stats and move temp file on success
          if ($movetempfile && (empty ($errorCode) || $errorCode < 1))
          {
            // write media information to DB with function rdbms_setmedia is used by function and service savecontent for the original media file only

            // encrypt and save file if required
            if ($temp['result']) movetempfile ($location, $media, true);

            // save to cloud storage
            if (function_exists ("savecloudobject")) savecloudobject ($site, $location, $media, $user);
          }

          // save log
          savelog (@$error);

          return true;
        }
      }
    }

    return false;
  }
  else return false;
}

// ------------------------- xmp_create -----------------------------
// function: xmp_create()
// input: publication name [string], text from content container [array]
// output: XMP tag array / false on error

// description:
// Defines XMP tag array based on the media mapping of a publication

function xmp_create ($site, $text)
{
  global $mgmt_config;

  if (valid_publicationname ($site) && is_array ($text) && !empty ($mgmt_config['abs_path_data']))
  {
    // try to load mapping configuration file of publication
    if (is_file ($mgmt_config['abs_path_data']."config/".$site.".media.map.php"))
    {
      @include ($mgmt_config['abs_path_data']."config/".$site.".media.map.php");
    }

    $result = array();

    // look in mapping definition (name => id)
    if (isset ($mapping) && is_array ($mapping))
    {
      foreach ($mapping as $tag => $id)
      {
        // extract type prefix and text ID
        if (strpos ($id, ":") > 0) list ($type, $id) = explode (":", $id); 

        // set XMP tag (tag => value)
        if ($tag != "" && $id != "" && isset ($text[$id]) && (substr ($tag, 0, 3) == "dc:" || substr ($tag, 0, 10) == "photoshop:"))
        {
          $result[$tag] = $text[$id];
        }
      }

      return $result;
    }
    else
    {
      $errcode = "10101";
      $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Media mapping of publication '".$site."' could not be loaded";

      savelog (@$error);

      return false;
    }
  }
  else return false;
}

// ------------------------- geo2decimal -----------------------------
// function: geo2decimal()
// input: GPS location [degrees,minutes,seconds], hemisphere [N,O,S,W]
// output: decimal result / false

function geo2decimal ($coordinate, $hemisphere)
{
  if (is_string ($coordinate))
  {
    $coordinate = array_map ("trim", explode (",", $coordinate));
  }

  for ($i = 0; $i < 3; $i++)
  {
    $part = explode ('/', $coordinate[$i]);

    if (count ($part) == 1)
    {
      $coordinate[$i] = $part[0];
    }
    else if (count ($part) == 2)
    {
      $coordinate[$i] = floatval ($part[0]) / floatval ($part[1]);
    }
    else
    {
      $coordinate[$i] = 0;
    }
  }

  list ($degrees, $minutes, $seconds) = $coordinate;
  $sign = ($hemisphere == 'W' || $hemisphere == 'S') ? -1 : 1;

  return $sign * ($degrees + $minutes/60 + $seconds/3600);
}


// ------------------------- krita_getdata -----------------------------
// function: krita_getdata()
// input: path to image file [string]
// output: result array / false on error

function krita_getdata ($file)
{
  global $user, $mgmt_config, $mgmt_uncompress, $hcms_ext;

	if (is_file ($file) && !empty ($mgmt_uncompress['.zip']))
  {
    if (!is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

    $result = array();

    // get file info
    $file_info = getfileinfo ("", $file, "comp");

    // check file extension
    if (substr_count (strtolower ($hcms_ext['image']).".", $file_info['ext'].".") == 0) return false;

    // get publication, location and media file name
    $site = getpublication ($file);
    $location = getlocation ($file);
    $media = getobject ($file);

    // prepare media file
    $temp = preparemediafile ($site, $location, $media, $user);

    // if encrypted
    if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
    {
      $file = $temp['templocation'].$temp['tempfile'];
    }
    // if restored
    elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
    {
      $file = $temp['location'].$temp['file'];
    }

    // temporary directory for extracting file
    $temp_dir = $mgmt_config['abs_path_temp'].uniqid ("krita_")."/";

    // create temporary directory for extraction
    @mkdir ($temp_dir, $mgmt_config['fspermission']);

    // kra is a ZIP-file with the images preview.png, mergedimage.png, and content placed in the file documentinfo.xml
    $cmd = $mgmt_uncompress['.zip']." \"".shellcmd_encode ($file)."\" -d \"".shellcmd_encode ($temp_dir)."\"";

    // execute and redirect stderr (2) to stdout (1)
    @exec ($cmd." 2>&1", $output, $errorCode);

    if ($errorCode && is_array ($output))
    {
      $errcode = "20141";
      $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Execution of unzip (code:".$errorCode.", command".$cmd.") failed for '".$file."' \t".implode ("\t", $output); 
    } 
    elseif (is_file ($temp_dir."documentinfo.xml"))
    {
      // load file
      $content = HTTP_Get_contents ($temp_dir."documentinfo.xml");

      if ($content != false)
      {
        // get encoding/charset
        $xml_encoding = gethtmltag ($content, "?xml");
        if ($xml_encoding != false) $charset_temp = getattribute ($xml_encoding, "encoding");
        
        // remove multiple white spaces
        $content = preg_replace ('/\s+/', ' ', $content);

        // convert content if source charset is not UTF-8 (XML-Containers of multimedia files must use UTF-8 encoding)
        if (!empty ($charset_temp) && strtolower ($charset_temp) != "utf-8")
        {
          $content = convertchars ($content, $charset_temp, "UTF-8");
        }

        $title = getcontent ($content, "<title>");
        $description = getcontent ($content, "<description>");
        $subject = getcontent ($content, "<subject>");
        $abstract = getcontent ($content, "<abstract>");
        $keyword = getcontent ($content, "<keyword>");
        $initialcreator = getcontent ($content, "<initial-creator>");
        $language = getcontent ($content, "<language>");
        $license = getcontent ($content, "<license>");
        
        if (!empty ($title[0])) $result['title'] = $title[0];
        if (!empty ($description[0])) $result['description'] = $description[0];
        if (!empty ($subject[0])) $result['subject'] = $subject[0];
        if (!empty ($abstract[0])) $result['abstract'] = $abstract[0];
        if (!empty ($keyword[0])) $result['keyword'] = $keyword[0];
        if (!empty ($initialcreator[0])) $result['initial-creator'] = $initialcreator[0];
        if (!empty ($language[0])) $result['language'] = $language[0];
        if (!empty ($license[0])) $result['license'] = $license[0];
      }
    }

    // remove temp directory
    if (is_dir ($temp_dir)) deletefile (getlocation ($temp_dir), getobject ($temp_dir), 1);

    // delete temp file
    if (!empty ($temp['result']) && !empty ($temp['created']) && is_file ($temp['templocation'].$temp['tempfile']))
    {
      deletefile ($temp['templocation'], $temp['tempfile'], 0);
    }

    return $result;
	}
  else return false;
}

// ------------------------- krita_create -----------------------------
// function: krita_create()
// input: publication name [string], text from content container [array]
// output: KRA tag array / false on error

// description:
// Defines XMP tag array based on the media mapping of a publication

function krita_create ($site, $text)
{
  global $mgmt_config;

  if (valid_publicationname ($site) && is_array ($text) && !empty ($mgmt_config['abs_path_data']))
  {
    // try to load mapping configuration file of publication
    if (is_file ($mgmt_config['abs_path_data']."config/".$site.".media.map.php"))
    {
      @include ($mgmt_config['abs_path_data']."config/".$site.".media.map.php");
    }

    $result = array();

    // look in mapping definition (name => id)
    if (isset ($mapping) && is_array ($mapping))
    {
      foreach ($mapping as $tag => $id)
      {
        // extract type prefix and text ID
        if (strpos ($id, ":") > 0) list ($type, $id) = explode (":", $id); 

        // set XMP tag (tag => value)
        if ($tag != "" && $id != "" && isset ($text[$id]) && (substr ($tag, 0, 4) == "kra:"))
        {
          $result[$tag] = $text[$id];
        }
      }

      return $result;
    }
    else
    {
      $errcode = "10144";
      $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Media mapping of publication '".$site."' could not be loaded";

      savelog (@$error);

      return false;
    }
  }
  else return false;
}

// ------------------------- exif_getdata -----------------------------
// function: exif_getdata()
// input: path to image file [string]
// output: result array / false

function exif_getdata ($file)
{
  global $user, $mgmt_config, $hcms_ext;

	if (is_file ($file))
  {
    if (!is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

    $result = array();

    // get file info
    $file_info = getfileinfo ("", $file, "comp");

    // check file extension
    if (substr_count (strtolower ($hcms_ext['image']).".", $file_info['ext'].".") == 0) return false;

    // get publication, location and media file name
    $site = getpublication ($file);
    $location = getlocation ($file);
    $media = getobject ($file);

    // prepare media file
    $temp = preparemediafile ($site, $location, $media, $user);

    // if encrypted
    if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
    {
      $file = $temp['templocation'].$temp['tempfile'];
    }
    // if restored
    elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
    {
      $file = $temp['location'].$temp['file'];
    }

    // set encoding for EXIF to UTF-8
    ini_set ('exif.encode_unicode', 'UTF-8');
    error_reporting (0);

    // read exif data
		$exif = exif_read_data ($file, 0, true);

    // delete temp file
    if (!empty ($temp['result']) && !empty ($temp['created']) && is_file ($temp['templocation'].$temp['tempfile']))
    {
      deletefile ($temp['templocation'], $temp['tempfile'], 0);
    }
		
    // date and time
		if (isset ($exif["EXIF"]["DateTimeOriginal"]))
    {
			$dateoriginal = str_replace (":", "-", substr ($exif["EXIF"]["DateTimeOriginal"], 0, 10));
			$timeoriginal = substr ($exif["EXIF"]["DateTimeOriginal"], 10);
			if (trim ($dateoriginal." ".$timeoriginal) != "") $result['Camera']['Date and time'] = $dateoriginal." ".$timeoriginal;
		}

    // GPS / geo location
    if (is_array ($exif['GPS']['GPSLongitude']) && is_array ($exif['GPS']['GPSLatitude']))
    {
      $egeoLong = $exif['GPS']['GPSLongitude'];
      $egeoLat = $exif['GPS']['GPSLatitude'];
      $egeoLongR = $exif['GPS']['GPSLongitudeRef'];
      $egeoLatR = $exif['GPS']['GPSLatitudeRef'];

      // GPS coordinates in degress, minutes, seconds
      $result['GPS']['GPSLatitude'] = $egeoLat[0].",".$egeoLat[1].",".$egeoLat[2];
      $result['GPS']['GPSLongitude'] = $egeoLong[0].",".$egeoLong[1].",".$egeoLong[2];
      $result['GPS']['GPSLatitudeRef'] = $egeoLatR;
      $result['GPS']['GPSLongitudeRef'] = $egeoLongR;
      $result['GPS']['GPSAltitudeRef'] = $exif['GPS']['GPSAltitudeRef'];
      $result['GPS']['GPSAltitude'] = $exif['GPS']['GPSAltitude'];
      $result['GPS']['latitude'] = geo2decimal ($egeoLat[0].",".$egeoLat[1].",".$egeoLat[2], $egeoLatR);
      $result['GPS']['longitude'] = geo2decimal ($egeoLong[0].",".$egeoLong[1].",".$egeoLong[2], $egeoLongR);
      $result['GPS']['coordinates'] = $result['GPS']['latitude'].",".$result['GPS']['longitude'];
    }

    // shutter
		if (isset ($exif["EXIF"]["FNumber"]))
    {
			list ($num, $den) = explode ("/", $exif["EXIF"]["FNumber"]);
			$aperture  = "F/".($num/$den);
			if (trim ($aperture) != "") $result['Camera']['Shutter dissolve'] = trim ($aperture);
		}
		
    // exposure time
		if (isset ($exif["EXIF"]["ExposureTime"]))
    {
			list ($num, $den) = explode ("/", $exif["EXIF"]["ExposureTime"]);

			if ($num > $den)
      {
				$exposure = $num." s";
			}
      else
      {
				$den = round ($den/$num);
				$exposure = "1/".$den." s";
			}

      if (trim ($exposure) != "") $result['Camera']['Exposure time'] = trim ($exposure);
		}
		
    // focal length
		if (isset ($exif["EXIF"]["FocalLength"]))
    {
			list ($num, $den) = explode ("/", $exif["EXIF"]["FocalLength"]);
			$focallength  = ($num/$den)." mm";
			if (trim ($focallength) != "") $result['Camera']['Focal length'] = trim ($focallength);
		}
		
		if (isset ($exif["EXIF"]["FocalLengthIn35mmFilm"]))
    {
			$focallength35 = $exif["EXIF"]["FocalLengthIn35mmFilm"];
			if (trim ($focallength35) != "") $result['Camera']['Focal length in 35mm film'] = trim ($focallength35)." mm";
		}
		
    // ISO
		if (isset ($exif["EXIF"]["ISOSpeedRatings"]))
    {
      $iso = $exif["EXIF"]["ISOSpeedRatings"];
			if (trim ($iso) != "") $result['Camera']['ISO'] = trim ($iso);
		}

    // white balance
		if (isset ($exif["EXIF"]["WhiteBalance"]))
    {
			switch ($exif["EXIF"]["WhiteBalance"])
      {
				case 0:
					$whitebalance = "Auto";
					break;
				case 1:
					$whitebalance = "Daylight";
					break;
				case 2:
					$whitebalance = "Fluorescent";
					break;
				case 3:
					$whitebalance = "Incandescent";
					break;
				case 4:
					$whitebalance = "Flash";
					break;
				case 9:
					$whitebalance = "Fine Weather";
					break;
				case 10:
					$whitebalance = "Cloudy";
					break;
				case 11:
					$whitebalance = "Shade";
					break;
				default:
					$whitebalance = "";
					break;
			}

			if (trim ($whitebalance) != "") $result['Camera']['White balance'] = $whitebalance;
		}
    
    // flash light
		if (isset ($exif["EXIF"]["Flash"]))
    {
			switch ($exif["EXIF"]["Flash"])
      {
				case 0:
					$flash = 'Flash did not fire';
					break;
				case 1:
					$flash = 'Flash fired';
					break;
				case 5:
					$flash = 'Strobe return light not detected';
					break;
				case 7:
					$flash = 'Strobe return light detected';
					break;
				case 9:
					$flash = 'Flash fired, compulsory flash mode';
					break;
				case 13:
					$flash = 'Flash fired, compulsory flash mode, return light not detected';
					break;
				case 15:
					$flash = 'Flash fired, compulsory flash mode, return light detected';
					break;
				case 16:
					$flash = 'Flash did not fire, compulsory flash mode';
					break;
				case 24:
					$flash = 'Flash did not fire, auto mode';
					break;
				case 25:
					$flash = 'Flash fired, auto mode';
					break;
				case 29:
					$flash = 'Flash fired, auto mode, return light not detected';
					break;
				case 31:
					$flash = 'Flash fired, auto mode, return light detected';
					break;
				case 32:
					$flash = 'No flash function';
					break;
				case 65:
					$flash = 'Flash fired, red-eye reduction mode';
					break;
				case 69:
					$flash = 'Flash fired, red-eye reduction mode, return light not detected';
					break;
				case 71:
					$flash = 'Flash fired, red-eye reduction mode, return light detected';
					break;
				case 73:
					$flash = 'Flash fired, compulsory flash mode, red-eye reduction mode';
					break;
				case 77:
					$flash = 'Flash fired, compulsory flash mode, red-eye reduction mode, return light not detected';
					break;
				case 79:
					$flash = 'Flash fired, compulsory flash mode, red-eye reduction mode, return light detected';
					break;
				case 89:
					$flash = 'Flash fired, auto mode, red-eye reduction mode';
					break;
				case 93:
					$flash = 'Flash fired, auto mode, return light not detected, red-eye reduction mode';
					break;
				case 95:
					$flash = 'Flash fired, auto mode, return light detected, red-eye reduction mode';
					break;
				default:
					$flash = '';
					break;
			}

			if (trim ($flash) != "") $result['Camera']['Flash'] = trim ($flash);
    }
    
    // IDF0
		if (isset ($exif["IFD0"]) && is_array ($exif["IFD0"]))
    {
      foreach ($exif["IFD0"] as $key => $value)
      {
        if (trim ($value) != "") $result['IFD0'][$key] = trim ($value);
      }
    }
		
		if (isset ($exif["IFD0"]["Make"]) && isset ($exif["IFD0"]["Model"]))
    {
			$make = ucwords (strtolower ($exif["IFD0"]["Make"]));
			$model = ucwords ($exif["IFD0"]["Model"]);
			if (trim ($make) != "") $result['Camera']['Camera/Scanner make'] = trim ($make);
			if (trim ($model) != "") $result['Camera']['Camera/Scanner model'] = trim ($model);
		}

    // comment
		if (isset ($exif["COMMENT"]))
    {
      if (is_array ($exif["COMMENT"]))
      {
        foreach ($exif["COMMENT"] as $key => $value)
        {
          if (trim ($value) != "") $result['Comment'][$key] = trim ($value);
        }
      }
    }

    // computed
		if (isset ($exif["COMPUTED"]) && is_array ($exif["COMPUTED"]))
    {
      foreach ($exif["COMPUTED"] as $key => $value)
      {
        if (trim ($value) != "") $result['Computed'][$key] = trim ($value);
      }
    }

    if (sizeof ($result) > 0) return $result;
    else return false;
	}
  else return false;
}

// ------------------------- iptc_getdata -----------------------------
// function: iptc_getdata()
// input: path to image file [string]
// output: result array / false

function iptc_getdata ($file)
{
  global $user, $mgmt_config, $hcms_ext;

  if (is_file ($file))
  {
    if (!is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

    // get file info
    $file_info = getfileinfo ("", $file, "comp");

    // check file extension
    if (substr_count (strtolower ($hcms_ext['image']).".", $file_info['ext'].".") == 0) return false;

    // get publication, location and media file name
    $site = getpublication ($file);
    $location = getlocation ($file);
    $media = getobject ($file);

    // prepare media file
    $temp = preparemediafile ($site, $location, $media, $user);

    // if encrypted
    if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
    {
      $file = $temp['templocation'].$temp['tempfile'];
    }
    // if restored
    elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
    {
      $file = $temp['location'].$temp['file'];
    }

    $size = getimagesize ($file, $info);

    if ($size) $iptc = iptcparse ($info['APP13']);
    else $iptc = false;

    // delete temp file
    if (!empty ($temp['result']) && !empty ($temp['created']) && is_file ($temp['templocation'].$temp['tempfile']))
    {
      deletefile ($temp['templocation'], $temp['tempfile'], 0);
    }

    $result = array();

    if (is_array ($iptc))
    {
      if (trim ($iptc['2#003'][0]) != "") $result['Object type'] = trim ($iptc['2#003'][0]);
      if (trim ($iptc['2#005'][0]) != "") $result['Title'] = trim ($iptc['2#005'][0]);
      if (trim ($iptc['2#007'][0]) != "") $result['Edit status'] = trim ($iptc['2#007'][0]);
      if (trim ($iptc['2#010'][0]) != "") $result['Urgency'] = trim ($iptc['2#010'][0]);
      if (trim ($iptc['2#015'][0]) != "") $result['Category'] = trim ($iptc['2#015'][0]);
      if (trim ($iptc['2#020'][0]) != "") $result['Supplemental category'] = trim ($iptc['2#020'][0]);
      if (is_array ($iptc["2#025"]) && trim ($iptc["2#025"][0]) != "") $result['Keywords'] = implode (", ", $iptc["2#025"]);
      if (trim ($iptc['2#030'][0]) != "") $result['Release date'] = trim ($iptc['2#030'][0]);
      if (trim ($iptc['2#035'][0]) != "") $result['Release time'] = trim ($iptc['2#035'][0]);
      if (trim ($iptc['2#037'][0]) != "") $result['Expiriation date'] = trim ($iptc['2#037'][0]);
      if (trim ($iptc['2#038'][0]) != "") $result['Expiriation time'] = trim ($iptc['2#038'][0]);
      if (trim ($iptc['2#040'][0]) != "") $result['Special instructions'] = trim ($iptc['2#040'][0]);
      if (trim ($iptc['2#055'][0]) != "") $result['Creation date'] = trim ($iptc['2#055'][0]);
      if (trim ($iptc['2#060'][0]) != "") $result['Creation time'] = trim ($iptc['2#060'][0]);
      if (trim ($iptc['2#063'][0]) != "") $result['Digital creation date'] = trim ($iptc['2#063'][0]);
      if (trim ($iptc['2#065'][0]) != "") $result['Digital creation time'] = trim ($iptc['2#065'][0]);
      if (trim ($iptc['2#080'][0]) != "") $result['Photographer'] = trim ($iptc['2#080'][0]);
      if (trim ($iptc['2#085'][0]) != "") $result['Photographer title'] = trim ($iptc['2#085'][0]);
      if (trim ($iptc['2#090'][0]) != "") $result['City'] = trim ($iptc['2#090'][0]);
      if (trim ($iptc['2#095'][0]) != "") $result['State'] = trim ($iptc['2#095'][0]);
      if (trim ($iptc['2#101'][0]) != "") $result['Country'] = trim ($iptc['2#101'][0]);
      if (trim ($iptc['2#103'][0]) != "") $result['OTR'] = trim ($iptc['2#103'][0]);
      if (trim ($iptc['2#105'][0]) != "") $result['Headline'] = trim ($iptc['2#105'][0]);
      if (trim ($iptc['2#110'][0]) != "") $result['Credit'] = trim ($iptc['2#110'][0]);
      if (trim ($iptc['2#115'][0]) != "") $result['Source'] = trim ($iptc['2#115'][0]);
      if (trim ($iptc['2#116'][0]) != "") $result['Copyright'] = trim ($iptc['2#116'][0]);
      if (trim ($iptc['2#118'][0]) != "") $result['Contact'] = trim ($iptc['2#118'][0]);
      if (trim ($iptc['2#120'][0]) != "") $result['Description'] = trim ($iptc['2#120'][0]);
      if (trim ($iptc['2#122'][0]) != "") $result['Description author'] = trim ($iptc['2#122'][0]);
      if (trim ($iptc['2#130'][0]) != "") $result['Image type'] = trim ($iptc['2#130'][0]);
      if (trim ($iptc['2#131'][0]) != "") $result['Image orientation'] = trim ($iptc['2#131'][0]);
      if (trim ($iptc['2#135'][0]) != "") $result['Language'] = trim ($iptc['2#135'][0]);

      if (sizeof ($result) > 0) return $result;
      else return false;
    }
    else return false;
  }
  else return false;
}

// ------------------------- iptc_getcharset -----------------------------
// function: iptc_getcharset()
// input: iptc tag that holds character set information [string]
// output: character set as string / false on error

// description:
// Copied from MediaWiki!
// Warning, this function does not (and is not intended to) detect all iso 2022 escape codes.
// In practise, the code for utf-8 is the only code that seems to have wide use. It does detect that code.
// According to iim standard, charset is defined by the tag 1:90.
// in which there are iso 2022 escape sequences to specify the character set.
// the iim standard seems to encourage that all necessary escape sequences are
// in the 1:90 tag, but says it doesn't have to be.
// This is in need of more testing probably. This is definitely not complete.
// however reading the docs of some other iptc software, it appears that most iptc software
// only recognizes utf-8. If 1:90 tag is not present content is
// usually ascii or iso-8859-1 (and sometimes utf-8), but no guarantee.
// This also won't work if there are more than one escape sequence in the 1:90 tag
// or if something is put in the G2, or G3 charsets, etc. It will only reliably recognize utf-8.
// This is just going through the charsets mentioned in appendix C of the iim standard.

function iptc_getcharset ($tag)
{
  // \x1b = ESC.
  switch ($tag)
  {
  	case "\x1b%G": //utf-8
  	//Also call things that are compatible with utf-8, utf-8 (e.g. ascii)
  	case "\x1b(B": // ascii
  	case "\x1b(@": // iso-646-IRV (ascii in latest version, $ different in older version)
  		$c = 'UTF-8';
  		break;
  	case "\x1b(A": //like ascii, but british.
  		$c = 'ISO646-GB';
  		break;
  	case "\x1b(C": //some obscure sweedish/finland encoding
  		$c = 'ISO-IR-8-1';
  		break;
  	case "\x1b(D":
  		$c = 'ISO-IR-8-2';
  		break;
  	case "\x1b(E": //some obscure danish/norway encoding
  		$c = 'ISO-IR-9-1';
  		break;
  	case "\x1b(F":
  		$c = 'ISO-IR-9-2';
  		break;
  	case "\x1b(G":
  		$c = 'SEN_850200_B'; // aka iso 646-SE; ascii-like
  		break;
  	case "\x1b(I":
  		$c = "ISO646-IT";
  		break;
  	case "\x1b(L":
  		$c = "ISO646-PT";
  		break;
  	case "\x1b(Z":
  		$c = "ISO646-ES";
  		break;
  	case "\x1b([":
  		$c = "GREEK7-OLD";
  		break;
  	case "\x1b(K":
  		$c = "ISO646-DE";
  		break;
  	case "\x1b(N":  //crylic
  		$c = "ISO_5427";
  		break;
  	case "\x1b(`": //iso646-NO
  		$c = "NS_4551-1";
  		break;
  	case "\x1b(f": //iso646-FR
  		$c = "NF_Z_62-010";
  		break;
  	case "\x1b(g":
  		$c = "PT2"; //iso646-PT2
  		break;
  	case "\x1b(h":
  		$c = "ES2";
  		break;
  	case "\x1b(i": //iso646-HU
  		$c = "MSZ_7795.3";
  		break;
  	case "\x1b(w":
  		$c = "CSA_Z243.4-1985-1";
  		break;
  	case "\x1b(x":
  		$c = "CSA_Z243.4-1985-2";
  		break;
  	case "\x1b\$(B":
  	case "\x1b\$B":
  	case "\x1b&@\x1b\$B":
  	case "\x1b&@\x1b\$(B":
  		$c = "JIS_C6226-1983";
  		break;
  	case "\x1b-A": // iso-8859-1. at least for the high code characters.
  	case "\x1b(@\x1b-A":
  	case "\x1b(B\x1b-A":
  		$c = 'ISO-8859-1';
  		break;
  	case "\x1b-B": // iso-8859-2. at least for the high code characters.
  		$c = 'ISO-8859-2';
  		break;
  	case "\x1b-C": // iso-8859-3. at least for the high code characters.
  		$c = 'ISO-8859-3';
  		break;
  	case "\x1b-D": // iso-8859-4. at least for the high code characters.
  		$c = 'ISO-8859-4';
  		break;
  	case "\x1b-E": // iso-8859-5. at least for the high code characters.
  		$c = 'ISO-8859-5';
  		break;
  	case "\x1b-F": // iso-8859-6. at least for the high code characters.
  		$c = 'ISO-8859-6';
  		break;
  	case "\x1b-G": // iso-8859-7. at least for the high code characters.
  		$c = 'ISO-8859-7';
  		break;
  	case "\x1b-H": // iso-8859-8. at least for the high code characters.
  		$c = 'ISO-8859-8';
  		break;
  	case "\x1b-I": // CSN_369103. at least for the high code characters.
  		$c = 'CSN_369103';
  		break;
  	default:
  		//at this point just give up and refuse to parse iptc?
  		$c = false;
  }

  return $c;
}

// ------------------------- iptc_maketag -----------------------------
// function: iptc_maketag()
// input: type of tag (e.g. 2) [integer], code of tag (e.g. 025) [string], value of tag [string]
// output: binary IPTC tag / false on error

// description:
// Convert the IPTC tag to binary code

function iptc_maketag ($record=2, $tag=0, $value="")
{
  if ($record >= 0 && $tag != "")
  {
    $length = strlen ($value);
    $retval = chr(0x1C).chr($record).chr($tag);

    if ($length < 0x8000)
    {
      $retval .= chr($length >> 8).chr($length & 0xFF);
    }
    else
    {
      $retval .= chr(0x80) . 
                 chr(0x04) . 
                 chr(($length >> 24) & 0xFF) . 
                 chr(($length >> 16) & 0xFF) . 
                 chr(($length >> 8) & 0xFF) . 
                 chr($length & 0xFF);
    }

    return $retval.$value;
  }
  else return false;
}

// ------------------------- iptc_writefile -----------------------------
// function: iptc_writefile()
// input: abs. path to image file [string], IPTC tag [array], keep existing IPTC data of file [boolean] (optional), move tempoarary file from unecrypted to encrypted [boolean] (optional)
// output: true / false on error

// description:
// Writes IPTC tags into image file for supported file types and keeps the existing IPTC tags

function iptc_writefile ($file, $iptc, $keep_data=true, $movetempfile=true)
{
  global $user, $mgmt_config, $mgmt_mediametadata;

  $error = array();

  // write meta data only for the following file extensions
  $allowed_ext = array (".jpg", ".jpeg", ".pjpeg");

  if (is_file ($file) && is_array ($iptc))
  {
    // set default encoding to UTF-8
    $encoding = "UTF-8";

    // get container ID
    $container_id = getmediacontainerid ($file);

    // get file info
    $file_info = getfileinfo ("", $file, "comp");

    // check file extension
    if (!in_array ($file_info['ext'], $allowed_ext)) return false;

    // get publication, location and media file name
    $site = getpublication ($file);
    $location = getlocation ($file);
    $media = getobject ($file);

    // prepare media file
    $temp = preparemediafile ($site, $location, $media, $user);

    // if encrypted
    if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
    {
      $file = $temp['templocation'].$temp['tempfile'];
    }
    // if restored
    elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
    {
      $file = $temp['location'].$temp['file'];
    }

    if (is_file ($file))
    {
      // get IPTC info stored in file
      $imagesize = getimagesize ($file, $info);

      if (isset ($info['APP13']))
      {
        // parse binary IPTC block
        $iptc_old = iptcparse ($info['APP13']);

        // get charset info contained in tag 1:90.
        if (isset ($iptc_old["1#090"][0])) $encoding = iptc_getcharset (trim ($iptc_old["1#090"][0]));

        // compare old and new information to keep existing data
        if ($keep_data == true || $keep_data == 1)
        {
          if (is_array ($iptc_old))
          {
            // compare old and new tags
            foreach ($iptc_old as $tag => $value)
            {
              if (!isset ($iptc[$tag]))
              {
                // add old tags to iptc array
                if ($tag == "2#025" && is_array ($iptc_old[$tag])) $iptc[$tag] = implode (", ", $iptc_old[$tag]);
                else $iptc[$tag] = trim ($iptc_old[$tag][0]);
              }
            }
          }
        }
      }

      // remove IPTC data from file before embedding IPTC
      if (is_array ($mgmt_mediametadata))
      {
        // get file info
        $file_info = getfileinfo ("", $file, "comp");

        // define executable
        foreach ($mgmt_mediametadata as $extensions => $executable)
        {
          if (substr_count ($extensions.".", $file_info['ext'].".") > 0 && $executable != "")
          {
            // remove all IPTC tags from file
            $cmd = $executable." -overwrite_original -r -IPTC:all= \"".shellcmd_encode ($file)."\"";

            // execute and redirect stderr (2) to stdout (1)
            @exec ($cmd." 2>&1", $output, $errorCode);

            // on error
            if ($errorCode)
            {
              $errcode = "20242";
              $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Execution of EXIFTOOL (code:".$errorCode.", command:".$cmd.") failed clearing IPTC of file '".getobject ($file)."' \t".implode ("\t", $output);
            }
          }
        }
      }

      // convert the IPTC tags into binary code
      $data = "";

      if (sizeof ($iptc) > 0)
      {
        foreach ($iptc as $tag => $value)
        {
          $record = substr ($tag, 0, 1);
          $tag = substr ($tag, 2);
          $value = strip_tags ($value);
          $data .= iptc_maketag ($record, $tag, html_decode ($value, $encoding)); 
        }

        // embed the IPTC data (only JPEG files)
        if (is_file ($file)) $content = iptcembed ($data, $file);
      }

      // write the new image data to the file
      if (!empty ($content))
      {
        $fp = fopen ($file, "wb");

        if ($fp)
        {
          @flock ($fp, LOCK_EX);
          @fwrite ($fp, $content);
          @flock ($fp, LOCK_UN);
          @fclose ($fp);

          // save media stats and move temp file
          if ($movetempfile)
          {
            // write media information to DB with function rdbms_setmedia is used by function and service savecontent for the original media file only

            // encrypt and save file if required
            if ($temp['result']) movetempfile ($location, $media, true);

            // save to cloud storage
            if (function_exists ("savecloudobject")) savecloudobject ($site, $location, $media, $user);
          }

          return true;
        }
        else return false;
      }
      // on error
      else
      {
        $errcode = "20244";
        $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Injection of IPTC failed for file: ".getobject($file);
      }
    }

    return false;
  }
  else return false;
}


// ------------------------- iptc_create -----------------------------
// function: iptc_create()
// input: publication name [string], text from content container [array]
// output: IPTC tag array / false on error

// description:
// Defines IPTC tag array based on the media mapping of a publication

function iptc_create ($site, $text)
{
  global $mgmt_config;

  if (valid_publicationname ($site) && is_array ($text) && !empty ($mgmt_config['abs_path_data']))
  {
    // try to load mapping configuration file of publication
    if (is_file ($mgmt_config['abs_path_data']."config/".$site.".media.map.php"))
    {
      include ($mgmt_config['abs_path_data']."config/".$site.".media.map.php");
    }

    $iptc_info = array();

    // list of most common IPTC tags (name => tag)
    $iptc_info['iptc:object_type'] = "2#003";
    $iptc_info['iptc:title'] = "2#005";
    $iptc_info['iptc:edit_status'] = "2#007";
    $iptc_info['iptc:urgency'] = "2#010";
    $iptc_info['iptc:category'] = "2#015";
    $iptc_info['iptc:supplemental_categories'] = "2#020";
    $iptc_info['iptc:keywords'] = "2#025";
    $iptc_info['iptc:release_date'] = "2#030";
    $iptc_info['iptc:release_time'] = "2#035";
    $iptc_info['iptc:expiriation_date'] = "2#037";
    $iptc_info['iptc:expiriation_time'] = "2#038";
    $iptc_info['iptc:special_instructions'] = "2#040";
    $iptc_info['iptc:creation_date'] = "2#055";
    $iptc_info['iptc:creation_time'] = "2#060";
    $iptc_info['iptc:digital_creation_date'] = "2#063";
    $iptc_info['iptc:digital_creation_time'] = "2#065";
    $iptc_info['iptc:photographer'] = "2#080";
    $iptc_info['iptc:photographer_title'] = "2#085";
    $iptc_info['iptc:city'] = "2#090";
    $iptc_info['iptc:state'] = "2#095";
    $iptc_info['iptc:country'] = "2#101";
    $iptc_info['iptc:otr'] = "2#103";
    $iptc_info['iptc:headline'] = "2#105";
    $iptc_info['iptc:credit'] = "2#110";
    $iptc_info['iptc:source'] = "2#115";
    $iptc_info['iptc:copyright'] = "2#116";
    $iptc_info['iptc:contact'] = "2#118";
    $iptc_info['iptc:description'] = "2#120";
    $iptc_info['iptc:description_author'] = "2#122";
    $iptc_info['iptc:image_type'] = "2#130";
    $iptc_info['iptc:image_orientation'] = "2#131";
    $iptc_info['iptc:language'] = "2#135";

    $result = array();

    // look in mapping definition (name => id)
    if (isset ($mapping) && is_array ($mapping))
    {
      foreach ($mapping as $name => $id)
      {
        $name = strtolower ($name);

        // extract type prefix and text ID
        if (strpos ($id, ":") > 0) list ($type, $id) = explode (":", $id); 

        // set IPTC tag (tag => value)
        if ($id != "" && isset ($text[$id]) && !empty ($iptc_info[$name]))
        {
          $tag = $iptc_info[$name];
          if ($tag != "") $result[$tag] = $text[$id];
        }
      }

      return $result;
    }
    else return false;
  }
  else return false;
}
 
// ------------------------- createmapping -----------------------------
// function: createmapping()
// input: publication name [string], mapping definition [string]
// output: true / false on error

// description:
// Prepares the PHP mapping array from the provided mapping definition and saves the media mapping file

function createmapping ($site, $mapping)
{
  global $mgmt_config;

  if ($mapping != "")
  {
    $mapping_result = "";
    // unescape >
    $mapping = str_replace ("&gt;", ">", $mapping);
    // create array
    $lines = explode ("\n", $mapping); 

    if (is_array ($lines))
    {
      foreach ($lines as $line)
      {
        if ($line != "" && substr_count ($line, "=>") == 1 && substr_count ($line, "//") == 0)
        {
          list ($key, $value) = explode ("=>", $line);

          if ($key != "" && $value != "")
          {
            if (substr_count ($key, "\"") > 0) $key_array = explode ("\"", $key);
            elseif (substr_count ($key, "'") > 0) $key_array = explode ("'", $key);
            else $key_array[1] = $key;

            if (is_array ($key_array)) $map_tag = "\$mapping['".trim (addslashes (strip_tags ($key_array[1])))."']";
            else $map_tag = false;

            if (substr_count ($value, "\"") > 0) $value_array = explode ("\"", $value);
            elseif (substr_count ($value, "'") > 0) $value_array = explode ("'", $value);
            else $value_array[1] = $value;

            if (is_array ($value_array) && $value_array[1] != "") $map_id = "\"".trim (addslashes (strip_tags ($value_array[1])))."\";\n";
            else $map_id = false;

            if ($map_tag != false && $map_id != false) $mapping_result .= $map_tag." = ".$map_id;
          }
        }
      }
    }

    if ($mapping != "")
    {
      // remove all tags
      $mapping_data_info = strip_tags ($mapping);
      $mapping_data_save = "/*\n".$mapping_data_info."\n*/\n/* hcms_mapping */\n\$mapping = array();\n".trim ($mapping_result);

      // save mapping file
      return savefile ($mgmt_config['abs_path_data']."config/", $site.".media.map.php", "<?php\n".$mapping_data_save."\n?>");
    }
    else return false;
  }
  else return false;
}

// ------------------------- getmapping -----------------------------
// function: getmapping()
// input: publication name [string]
// output: mapping code for display / false

// description:
// Load the mapping file of the provided publication.

function getmapping ($site)
{
  global $mgmt_config;

  $mapping_data = "";

  if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."config/".$site.".media.map.php"))
  {
    // load mapping file
    $mapping_data = loadfile ($mgmt_config['abs_path_data']."config/", $site.".media.map.php");

    if ($mapping_data != "")
    {
      list ($mapping_data) = explode ("/* hcms_mapping */", $mapping_data);

      // convert from older version to 5.5.8
      if (substr_count ($mapping_data, "\$mapping[") > 0)
      {
        $mapping_data = str_replace (array("\$mapping['", "']", "=", "\"", ";"), array("", "", "=>", "", ""), $mapping_data);
      }

      // convert from older version to 7.0.4 (double to single quotes)
      if (substr_count ($mapping_data, '"') > 1)
      {
        $mapping_data = str_replace ('"', "'", $mapping_data);
      }

      // remove comment tags
      $mapping_data = str_replace (array("/*", "*/"), array("", ""), $mapping_data);

      // remove php tags
      $mapping_data = str_replace (array("<?php", "?>"), array("", ""), $mapping_data);

      // trim
      return trim ($mapping_data);
    }
  }

  // exif mapping based on EXIFTOOL output by function extractmetadata (as an example or alternative to the ones of function exif_getdata)
  /*
  // exif namespace tags
exif:Image Description => 'textu:Description'
exif:Make => ''
exif:Camera Model Name => ''
exif:Orientation => ''
exif:X Resolution => ''
exif:Y Resolution => ''
exif:Resolution Unit => ''
exif:Software => ''
exif:Modify Date => ''
exif:Exposure Time => ''
exif:F Number => ''
exif:Exposure Program => ''
exif:ISO => ''
exif:Exif Version => ''
exif:Date/Time Original => ''
exif:Create Date => ''
exif:Components Configuration => ''
exif:Compressed Bits Per Pixel => ''
exif:Exposure Compensation => ''
exif:Max Aperture Value => ''
exif:Metering Mode => ''
exif:Light Source => ''
exif:Flash => ''
exif:Focal Length => ''
exif:Sub Sec Time => ''
exif:Flashpix Version => ''
exif:Color Space => ''
exif:Exif Image Width => ''
exif:Exif Image Height => ''
exif:Sensing Method => ''
exif:File Source => ''
exif:Scene Type => ''
exif:Custom Rendered => ''
exif:Exposure Mode => ''
exif:White Balance => ''
exif:Digital Zoom Ratio => ''
exif:Focal Length In 35mm Format => ''
exif:Scene Capture Type => ''
exif:Gain Control => ''
exif:Contrast => ''
exif:Saturation => ''
exif:Sharpness => ''
exif:Subject Distance Range => ''
exif:GPS Version ID => ''
exif:GPS Latitude Ref => ''
exif:GPS Longitude Ref => ''
exif:GPS Altitude Ref => ''
exif:GPS Time Stamp => ''
exif:GPS Processing Method => ''
exif:GPS Date Stamp => ''
exif:Compression => ''

// Composite namespace tags
composite:Aperture => ''
composite:GPS Altitude => ''
composite:GPS Date/Time => ''
composite:GPS Latitude => ''
composite:GPS Longitude => ''
composite:GPS Position => ''
composite:Image Size => ''
composite:Megapixels => ''
composite:Scale Factor To 35 mm Equivalent => ''
composite:Shutter Speed => ''
composite:Create Date => ''
composite:Date/Time Original => ''
composite:Modify Date => ''
composite:Circle Of Confusion => ''
composite:Field Of View => ''
composite:Focal Length => ''
composite:Hyperfocal Distance => ''
composite:Light Value => ''
*/

  // default mapping
  if ($mapping_data == "")
  {
    return "// Mapping definition: The metadata tag will be assigned to a specific text tag-ID and text-type

// IPTC tags (JPG, TIFF, PNG, MIFF, PS, PDF, PSD, XCF and DNG image files)
iptc:title => 'textu:Title'
iptc:keywords => 'textk:Keywords'
iptc:description => 'textu:Description'
iptc:photographer => 'textu:Creator'
iptc:source => 'textu:Copyright'
iptc:urgency => ''
iptc:category => ''
iptc:supp_categories => ''
iptc:spec_instr => ''
iptc:creation_date => ''
iptc:credit_byline_title => ''
iptc:city => ''
iptc:state => ''
iptc:country => ''
iptc:otr => ''
iptc:headline => ''
iptc:source => ''
iptc:photo_number => ''
iptc:photo_source => ''
iptc:charset => ''

// XMP Dublin Core namespace tags (JPG, JP2, TIFF, GIF, EPS, PDF, PSD, IND, INX, PNG, DJVU, SVG, PGF, MIFF, XCF, CRW, DNG and proprietary TIFF-based RAW images, as well as MOV, AVI, ASF, WMV, FLV, SWF and MP4 videos, and WMA audio files)
dc:title => 'textu:Title'
dc:subject => 'textk:Keywords'
dc:description => 'textu:Description'
dc:creator => 'textu:Creator'
dc:rights => 'textu:Copyright'
dc:contributor => ''
dc:coverage => ''
dc:date => ''
dc:format =>''
dc:identifier => ''
dc:language => ''
dc:publisher => ''
dc:relation => ''
dc:rights => ''
dc:source => ''
dc:type => ''

// XMP Adobe PhotoShop namespace tags
photoshop:AuthorsPosition => ''
photoshop:CaptionWriter => ''
photoshop:Category => ''
photoshop:City => ''
// ColorMode:
// 0 = Bitmap
// 1 = Grayscale
// 2 = Indexed
// 3 = RGB
// 4 = CMYK
// 7 = Multichannel
// 8 = Duotone
// 9 = Lab
photoshop:ColorMode => ''
photoshop:Country => ''
photoshop:Credit => ''
photoshop:DateCreated => ''
photoshop:DocumentAncestors => ''
photoshop:DocumentAncestorID => ''
photoshop:Headline => ''
photoshop:History => ''
photoshop:ICCProfileName => ''
photoshop:Instructions => ''
photoshop:LegacyIPTCDigest => ''
photoshop:SidecarForExtension => ''
photoshop:Source => ''
photoshop:State => ''
photoshop:SupplementalCategories => ''
photoshop:TextLayers => ''
photoshop:TextLayerName => ''
photoshop:TextLayerText => ''
photoshop:TransmissionReference => ''
photoshop:Urgency => ''

// XMP Adobe Lightroom namespace tags
lr:hierarchicalSubject => 'automatic'

// EXIF tags (JPG, TIFF, PNG, JP2, PGF, MIFF, HDP, PSP, XCF, TIFF-based RAW images, and even AVI and MOV video files)
// EXIF-Sections:
// FILE ...	FileName, FileSize, FileDateTime, SectionsFound
// GPS ... GPS coordinates
// COMPUTED ... html, Width, Height, IsColor, and more if available. Height and Width are computed the same way getimagesize() does so their values must not be part of any header returned. Also, html is a height/width text string to be used inside normal HTML.
// ANY_TAG ...	Any information that has a Tag e.g. IFD0, EXIF, ...
// IFD0 ... All tagged data of IFD0. In normal imagefiles this contains image size and so forth.
// THUMBNAIL ...	A file is supposed to contain a thumbnail if it has a second IFD. All tagged information about the embedded thumbnail is stored in this section.
// COMMENT ...	Comment headers of JPEG images.
// EXIF ... The EXIF section is a sub section of IFD0. It contains more detailed information about an image. Most of these entries are digital camera related.

// exif:SECTION.Tag-Name
exif:FILE.FileName => ''
exif:FILE.FileDateTime => ''
exif:FILE.FileSize => ''
exif:FILE.FileType => ''
exif:FILE.MimeType => ''
exif:FILE.SectionsFound => ''
exif:GPS.longitude => ''
exif:GPS.latitude => ''
exif:GPS.coordinates => ''
exif:COMPUTED.html => ''
exif:COMPUTED.Height => ''
exif:COMPUTED.Width => ''
exif:COMPUTED.IsColor => ''
exif:COMPUTED.ByteOrderMotorola => ''
exif:COMPUTED.Thumbnail.FileType => ''
exif:COMPUTED.Thumbnail.MimeType => ''
exif:IFD0.DateTime => ''
exif:IFD0.Artist => ''
exif:IFD0.Exif_IFD_Pointer => ''
exif:IFD0.Title => 'textu:Title'
exif:IFD0.ImageDescription => 'textu:Description'
exif:THUMBNAIL.Compression => ''
exif:THUMBNAIL.XResolution => ''
exif:THUMBNAIL.YResolution => ''
exif:THUMBNAIL.ResolutionUnit => ''
exif:THUMBNAIL.JPEGInterchangeFormat => ''
exif:THUMBNAIL.JPEGInterchangeFormatLength => ''
exif:EXIF.DateTimeOriginal => ''
exif:EXIF.DateTimeDigitized => ''

// QuickTime namespace tags (MOV and MP4 video files)
quicktime:Duration => ''
quicktime:ContentDistributor => ''
quicktime:Producer => 'textu:Creator'
quicktime:Director => ''
quicktime:Publisher => ''
quicktime:Genre => ''
quicktime:Year => ''

// ASF namespace tags (WMA audio, WMV and DIVX video files)
asf:Duration => ''
asf:ContentDistributor => ''
asf:Producer => 'textu:Creator'
asf:Director => ''
asf:Publisher => ''
asf:Author => ''
asf:Genre => ''
asf:Year => ''
asf:Language => ''

// RIFF namespace tags (AVI video and WAV audio files)
riff:Software => ''
riff:Keywords => ''
riff:Comment => ''
riff:Subject => ''
riff:Source => ''
riff:Artist => 'textu:Creator'
riff:Year => ''
riff:Language => ''

// ID3 namespace tags (MP3, MPEG, AIFF, OGG, FLAC, APE, MPC and RealAudio files)
id3:title => 'textu:Title'
id3:artist => 'textu:Creator'
id3:album => ''
id3:year => ''
id3:comment => 'textu:Description'
id3:track => ''
id3:genre => ''
id3:tracknumber => ''
id3:band => ''

// KRITA namespace tags (KRA files)
kra:title => 'textu:Title'
kra:description => 'textu:Description'
kra:subject => ''
kra:abstract => ''
kra:keyword => 'textk:Keywords'
kra:initial-creator => 'textu:Creator'
kra:license => 'textu:Copyright'
kra:language => ''

// Image Resolution defines Quality [Print, Web]
hcms:quality => 'textl:Quality'

// Google Video Intelligence (auto tagging for videos) and Google Vision (auto tagging for images)
google:description => 'textu:Description'
google:keywords => 'textk:Keywords'";
  }
}

// ------------------------- metadata_exists -----------------------------
// function: metadata_exists()
// input: mapping [array:metadata-tag-name => text-id], text content [array:metadata-text-id => content]
// output: true / false

// description:
// Verifies if the content of a specific text ID that triggers a Cloud API call exists already.
// This function is used to reduce/trigger Cloud API calls in case the content exists already and the media file does not need to be analyzed by a cloud service.

function metadata_exists ($mapping, $text_array)
{
  $result = true;

  if (is_array ($mapping))
  {
    reset ($mapping);

    foreach ($mapping as $key => $text_id)
    {
      // only for google namespace
      if (strpos ("_".$key, "google:") > 0 && $text_id != "")
      {
        // get type and text ID
        if (strpos ($text_id, ":") > 0) list ($type, $text_id) = explode (":", $text_id);

        // verify content in textnode
        if (empty ($text_array[$text_id])) $result = false;
      }
    }
  }

  return $result;
}

// ------------------------- setmetadata -----------------------------
// function: setmetadata()
// input: publication name [string], location path [string] (optional), object name [string] (optional), media file name [string] (optional), mapping [array:metadata-tag-name => text-id] (optional), 
//        container content as XML [string] (optional), user name [string], save content container [boolean] (optional)
// output: container content as XML string / false

// description:
// Saves meta data of a multimedia file using a provided mapping in the proper fields of the content container. 
// If no mapping is given a default mapping will be used.

function setmetadata ($site, $location="", $object="", $mediafile="", $mapping="", $containerdata="", $user="", $savecontainer=true)
{
  global $eventsystem, $mgmt_config, $hcms_ext;

  // initalize
  $error = array();
  
  if (!is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

  // IPTC-tag and xml-tag name from multimedia file is mapped with text-id of the content container.
  // text-ids need to be defined in the meta data defintion. 
  if (valid_publicationname ($site) && (!is_array ($mapping) || sizeof ($mapping) == 0) && !empty ($mgmt_config['abs_path_data']))
  {
    if (!is_file ($mgmt_config['abs_path_data']."config/".$site.".media.map.php"))
    {
      $mapping_data = getmapping ($site);
      createmapping ($site, $mapping_data);
    }

    // try to load mapping configuration file of publication
    if (is_file ($mgmt_config['abs_path_data']."config/".$site.".media.map.php"))
    {
      include ($mgmt_config['abs_path_data']."config/".$site.".media.map.php");
    }
  }

  if (valid_publicationname ($site) && ((valid_locationname ($location) && valid_objectname ($object)) || valid_objectname ($mediafile)) && is_array ($mapping) && valid_objectname ($user))
  {
    // get media file if not given
    if ($mediafile == "" || !valid_objectname ($mediafile))
    {
      // deconvert path
      $location = deconvertpath ($location, "file");

      $objectdata = loadfile ($location, $object);

      if ($objectdata != "") $mediafile = getfilename ($objectdata, "media");
      else return false;
    }

    // get the file extension
    $mediafile_ext = strtolower (@strrchr ($mediafile, "."));

    // check file extension
    if (substr_count (strtolower ($hcms_ext['audio'].$hcms_ext['image'].$hcms_ext['video']).".", $mediafile_ext.".") > 0)
    {
      // get container information
      $container_id = getmediacontainerid ($mediafile);
      $container = getmediacontainername ($mediafile);

      // media location
      $medialocation = getmedialocation ($site, $mediafile, "abs_path_media");

      // if symbolic link is used,the full file path will be returned
      if (is_file ($medialocation))
      {
        $mediafile = getobject ($medialocation);
        $medialocation = getlocation ($medialocation);
      }
      else $medialocation = $medialocation.$site."/";

      // load multimedia file
      $mediadata = decryptfile ($medialocation, $mediafile);

      // load container if not provided
      if ($containerdata == "")
      {
        $containerdata = loadcontainer ($container, "work", $user);
      }

      // get destination character set
      $charset_array = getcharset ($site, $containerdata);

      // set to UTF-8 if not available
      if (is_array ($charset_array)) $charset_dest = $charset_array['charset'];
      else $charset_dest = "UTF-8";

      // read metadata based on EXIF, IPTC, XMP in this order (so XMP will overwrite EXIF and IPTC if not empty)
      // XMP should be UTF-8 encoded but Adobe does not provide proper encoding of special characters 
      if ($mediadata != false && $containerdata != false)
      {
        // load text XML-schema
        $text_schema_xml = chop (loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "text.schema.xml.php"));

        // prepare media file
        $temp = preparemediafile ($site, $medialocation, $mediafile, $user);

        // if encrypted
        if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
        {
          $medialocation = $temp['templocation'];
          $mediafile = $temp['tempfile'];
        }
        // if restored
        elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
        {
          $medialocation = $temp['location'];
          $mediafile = $temp['file'];
        }


        // ------------------- extract metadata using EXIFTOOL -------------------
        // Can be used for all metadata formats supported by EXIFTOOL
        // New tags need to be defined in function getmapping
        $exiftool_data = extractmetadata ($medialocation.$mediafile);

        // inject meta data based on mapping
        if (!empty ($exiftool_data) && is_array ($exiftool_data))
        {
          // source charset
          $charset_source = "UTF-8";

          $exiftool_data = array_change_key_case ($exiftool_data, CASE_LOWER);

          reset ($mapping);

          foreach ($mapping as $key => $text_id)
          {
            // only for tags
            if (strpos ($key, ":") > 0 && $text_id != "")
            {
              // get tag name
              list ($namespace, $key) = explode (":", $key);
              $namespace = strtolower ($namespace);
              $key = trim ($key);

              // verify if namespace exists in extracted metadata (compare small caps)
              if (!empty ($exiftool_data[$namespace]))
              {
                // get array for name space
                $temp_data = $exiftool_data[$namespace];

                // add space between words, e.g. ContentDistributor tag 
                $temp_array = preg_split ('/(?=[A-Z])/', $key);
                if (is_array ($temp_array) && sizeof ($temp_array) > 1) $key = trim (implode (" ", $temp_array));

                // get type and text ID
                if (strpos ($text_id, ":") > 0) list ($type, $text_id) = explode (":", $text_id);
                elseif (substr_count (strtolower ($text_id), "keyword") > 0) $type = "textk";
                else $type = "textu";

                if (!empty ($type)) $type_array[$text_id] = $type;

                // get data
                if (!empty ($temp_data[$key])) $temp_str = $temp_data[$key];
                else $temp_str = "";

                if ($temp_str != "")
                {
                  // clean keywords
                  if ($type == "textk")
                  {
                    $keywords = splitkeywords ($temp_str, $charset_dest);

                    if (is_array ($keywords)) $temp_str = implode (",", $keywords);
                    else $temp_str = "";
                  }

                  // remove tags
                  $temp_str = strip_tags ($temp_str);

                  // convert string for container
                  if ($charset_source != "" && $charset_dest != "" && $charset_source != $charset_dest)
                  {
                    $temp_str = convertchars ($temp_str, $charset_source, $charset_dest);
                  }
                  elseif ($charset_dest == "UTF-8")
                  {
                    // encode to UTF-8
                    if (!is_utf8 ($temp_str)) $temp_str = utf8_encode ($temp_str);
                  }

                  // textnodes search index in database
                  $text_array[$text_id] = $temp_str;
                            
                  $containerdata_new = setcontent ($containerdata, "<text>", "<textcontent>", "<![CDATA[".$temp_str."]]>", "<text_id>", $text_id, true);

                  if ($containerdata_new == false)
                  {
                    $containerdata_new = addcontent ($containerdata, $text_schema_xml, "", "", "", "<textcollection>", "<text_id>", $text_id);
                    $containerdata_new = setcontent ($containerdata_new, "<text>", "<textcontent>", "<![CDATA[".$temp_str."]]>", "<text_id>", $text_id, true);
                    $containerdata_new = setcontent ($containerdata_new, "<text>", "<textuser>", $user, "<text_id>", $text_id, true);
                  }

                  if ($containerdata_new != false) $containerdata = $containerdata_new;
                  else
                  {
                    $errcode = "20604";
                    $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Failed to write meta data to container with ID: ".$container_id;
                  }
                }
              }
            }
          }
        }

        
        // ------------------- EXIF -------------------

        // set encoding for EXIF to UTF-8
        ini_set ('exif.encode_unicode', 'UTF-8');
        error_reporting(0);

        if (exif_imagetype ($medialocation.$mediafile))
        {
          // read EXIF data
          $exif_data = exif_getdata ($medialocation.$mediafile);

          if (is_array ($exif_data))
          {
            $exif_info = array();

            foreach ($exif_data as $key => $section)
            {
              foreach ($section as $name => $value)
              {
                $exif_info['exif:'.$key.'.'.$name] = $value;
              }
            }
          }

          // set GPS latitude and longitude in database
          if (!empty ($mgmt_config['gps_save']) && !empty ($exif_info['exif:GPS.latitude']) && is_numeric ($exif_info['exif:GPS.latitude']) && !empty ($exif_info['exif:GPS.longitude']) && is_numeric ($exif_info['exif:GPS.longitude']))
          {
            $sql = "UPDATE object SET latitude=".floatval($exif_info['exif:GPS.latitude']).", longitude=".floatval($exif_info['exif:GPS.longitude'])." WHERE id=".intval($container_id);                
            $result = rdbms_externalquery ($sql);
          }

          // inject meta data based on mapping
          reset ($mapping);

          foreach ($mapping as $key => $text_id)
          {
            // only for EXIF tags
            if (strpos ("_".$key, "exif:") > 0 && $text_id != "")
            { 
              if ($exif_info[$key] != "")
              {
                // get type and text ID
                if (strpos ($text_id, ":") > 0) list ($type, $text_id) = explode (":", $text_id);
                elseif (substr_count (strtolower ($text_id), "keyword") > 0) $type = "textk";
                else $type = "textu";

                // clean keywords
                if ($type == "textk")
                {
                  $keywords = splitkeywords ($exif_info[$key], $charset_dest);

                  if (is_array ($keywords)) $exif_info[$key] = implode (",", $keywords);
                  else $exif_info[$key] = "";
                }

                // remove tags
                $exif_info[$key] = strip_tags ($exif_info[$key]);

                // we set encoding for EXIF to UTF-8
                $charset_source = "UTF-8";

                // convert string for container
                if ($charset_source != "" && $charset_dest != "" && $charset_source != $charset_dest)
                {
                  $exif_info[$key] = convertchars ($exif_info[$key], $charset_source, $charset_dest);
                }

                // textnodes search index in database
                $text_array[$text_id] = $exif_info[$key];

                $containerdata_new = setcontent ($containerdata, "<text>", "<textcontent>", "<![CDATA[".$exif_info[$key]."]]>", "<text_id>", $text_id, true);

                if ($containerdata_new == false)
                {
                  $containerdata_new = addcontent ($containerdata, $text_schema_xml, "", "", "", "<textcollection>", "<text_id>", $text_id);
                  $containerdata_new = setcontent ($containerdata_new, "<text>", "<textcontent>", "<![CDATA[".$exif_info[$key]."]]>", "<text_id>", $text_id, true);
                  $containerdata_new = setcontent ($containerdata_new, "<text>", "<textuser>", $user, "<text_id>", $text_id, true);
                }

                if ($containerdata_new != false) $containerdata = $containerdata_new;
                else
                {
                  $errcode = "20606";
                  $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Failed to write EXIF meta data to container with ID: ".$container_id;
                }
              }
            }
          }
        }

        // ------------------- ID3 -------------------
 
        // get ID3 data from file
        $id3_data = id3_getdata ($medialocation.$mediafile);

        // inject meta data based on mapping
        if (is_array ($id3_data))
        {
          // get source charset
          if (!empty ($id3_data['encoding'])) $charset_source = $id3_data['encoding'];
          else $charset_source = "UTF-8"; 

          reset ($mapping);

          foreach ($mapping as $key => $text_id)
          {
            // only for ID3 tags (audio files)
            if (strpos ("_".$key, "id3:") > 0 && $text_id != "")
            {
              // get ID3 tag name
              if (strpos ($key, ":") > 0) list ($namespace, $key) = explode (":", $key);

              // get type and text ID
              if (strpos ($text_id, ":") > 0) list ($type, $text_id) = explode (":", $text_id);
              elseif (substr_count (strtolower ($text_id), "keyword") > 0) $type = "textk";
              else $type = "textu";

              if (!empty ($type)) $type_array[$text_id] = $type;

              // get data
              if (!empty ($id3_data[$key])) $id3str = $id3_data[$key];
              else $id3str = "";

              if ($id3str != "")
              {
                // clean keywords
                if ($type == "textk")
                {
                  $keywords = splitkeywords ($id3str, $charset_dest);

                  if (is_array ($keywords)) $id3str = implode (",", $keywords);
                  else $id3str = "";
                }

                // remove tags
                $id3str = strip_tags ($id3str);

                // convert string for container
                if ($charset_source != "" && $charset_dest != "" && $charset_source != $charset_dest)
                {
                  $id3str = convertchars ($id3str, $charset_source, $charset_dest);
                }
                elseif ($charset_dest == "UTF-8")
                {
                  // encode to UTF-8
                  if (!is_utf8 ($id3str)) $id3str = utf8_encode ($id3str);
                }

                // textnodes search index in database
                $text_array[$text_id] = $id3str;
                          
                $containerdata_new = setcontent ($containerdata, "<text>", "<textcontent>", "<![CDATA[".$id3str."]]>", "<text_id>", $text_id, true);

                if ($containerdata_new == false)
                {
                  $containerdata_new = addcontent ($containerdata, $text_schema_xml, "", "", "", "<textcollection>", "<text_id>", $text_id);
                  $containerdata_new = setcontent ($containerdata_new, "<text>", "<textcontent>", "<![CDATA[".$id3str."]]>", "<text_id>", $text_id, true);
                  $containerdata_new = setcontent ($containerdata_new, "<text>", "<textuser>", $user, "<text_id>", $text_id, true);
                }

                if ($containerdata_new != false) $containerdata = $containerdata_new;
                else
                {
                  $errcode = "20605";
                  $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Failed to write ID3 meta data to container with ID: ".$container_id;
                }
              }
            }
          }
        }

        // ------------------- XMP-based -------------------

        // inject meta data based on mapping
        reset ($mapping);

        foreach ($mapping as $key => $text_id)
        {
          // only for XMP (XML-based) tags (DC, Adobe ...)
          if (substr_count ($key, "iptc:") == 0 && substr_count ($key, "hcms:") == 0 && substr_count ($key, "exif:") == 0 && $key != "" && $text_id != "")
          {
            $dcstr = "";

            // get type and text ID
            if (strpos ($text_id, ":") > 0) list ($type, $text_id) = explode (":", $text_id);
            elseif (substr_count (strtolower ($text_id), "keyword") > 0) $type = "textk";
            else $type = "textu";

            if (!empty ($type)) $type_array[$text_id] = $type;

            // extract XMP information
            $dc_nodes = getcontent ($mediadata, "<".$key.">");

            if (!empty ($dc_nodes[0])) $dc = getcontent ($dc_nodes[0], "<rdf:li *>");
            else $dc = Null;

            unset ($dc_nodes);

            if (is_array ($dc) && sizeof ($dc) > 0)
            {
              // if Adobe Lightroom hierarchicalSubject should be indexed automatically
              if ($key == "lr:hierarchicalSubject" && strtolower ($text_id) == "automatic")
              {
                $dc_hierarchs = array();

                foreach ($dc as $temp)
                {
                  if (strpos ($temp, "|") > 0)
                  {
                    list ($temp_name, $temp_value) = explode ("|", $temp);

                    if (trim ($temp_name) != "" && trim ($temp_value) != "")
                    {
                      if (!empty ($dc_hierarchs[$temp_name])) $dc_hierarchs[$temp_name] .= ",".$temp_value;
                      else $dc_hierarchs[$temp_name] = $temp_value;
                    }
                  }
                }

                // save content as keywords using settext
                if (is_array ($dc_hierarchs) && sizeof ($dc_hierarchs) > 0)
                {
                  $containerdata_new = settext ($site, $containerdata, $container, $dc_hierarchs, "textk", "no", $user, $user, $charset_dest);

                  if ($containerdata_new != false) $containerdata = $containerdata_new;
                  else return false;
                }

                unset ($dc_hierarchs);
                $dcstr = "";
              }
              // assign to text ID
              else
              {
                // for keywords
                if ($type == "textk")
                {
                  $dc_reduced = array();

                  // max. length of keyword must not exceed 100 and must not include tags
                  foreach ($dc as $keyword)
                  {
                    if (is_string ($keyword) && strlen (trim ($keyword)) > 1 && strlen (trim ($keyword)) <= 100)
                    {
                      $tag_start = strpos ("_".$keyword, "<");
                      $tag_end = strpos ("_".$keyword, ">");

                      if ($tag_start > 0 && $tag_end > 0 && $tag_start < $tag_end) $tag_included = true;
                      else $tag_included = false;

                      if (!$tag_included) $dc_reduced[] = $keyword;
                    }
                  }

                  $dcstr = implode (",", $dc_reduced);
                  unset ($dc_reduced);
                }
                else $dcstr = implode (", ", $dc);
              }
            }

            if ($dcstr != "")
            {
              // remove tags
              $dcstr = strip_tags ($dcstr);

              // XMP always uses UTF-8 so should any other XML-based format
              $charset_source = "UTF-8";

              // convert string for container
              if ($charset_source != "" && $charset_dest != "" && $charset_source != $charset_dest)
              {
                $dcstr = convertchars ($dcstr, $charset_source, $charset_dest);
              }
              elseif ($charset_dest == "UTF-8")
              {
                // encode to UTF-8
                if (!is_utf8 ($dcstr)) $dcstr = utf8_encode ($dcstr);
              }

              // textnodes search index in database
              $text_array[$text_id] = $dcstr;
        
              $containerdata_new = setcontent ($containerdata, "<text>", "<textcontent>", "<![CDATA[".$dcstr."]]>", "<text_id>", $text_id, true);

              if ($containerdata_new == false)
              {
                $containerdata_new = addcontent ($containerdata, $text_schema_xml, "", "", "", "<textcollection>", "<text_id>", $text_id);
                $containerdata_new = setcontent ($containerdata_new, "<text>", "<textcontent>", "<![CDATA[".$dcstr."]]>", "<text_id>", $text_id, true);
                $containerdata_new = setcontent ($containerdata_new, "<text>", "<textuser>", $user, "<text_id>", $text_id, true);
              }

              if ($containerdata_new != false) $containerdata = $containerdata_new;
              else
              {
                $errcode = "20607";
                $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Failed to write XMP meta data to container with ID: ".$container_id;
              }
            }
          }
        }

        // ------------------- KRITA -------------------
 
        // get KRITA data from file
        $kra_data = krita_getdata ($medialocation.$mediafile);

        // inject meta data based on mapping
        if (is_array ($kra_data))
        {
          // source charset
          $charset_source = "UTF-8"; 

          reset ($mapping);

          foreach ($mapping as $key => $text_id)
          {
            // only for KRITA tags (audio files)
            if (strpos ("_".$key, "kra:") > 0 && $text_id != "")
            {
              // get KRITA tag name
              if (strpos ($key, ":") > 0) list ($namespace, $key) = explode (":", $key);

              // get type and text ID
              if (strpos ($text_id, ":") > 0) list ($type, $text_id) = explode (":", $text_id);
              elseif (substr_count (strtolower ($text_id), "keyword") > 0) $type = "textk";
              else $type = "textu";

              if (!empty ($type)) $type_array[$text_id] = $type;

              // get data
              if (!empty ($kra_data[$key])) $kra3str = $kra_data[$key];
              else $kra3str = "";

              if ($kra3str != "")
              {
                // clean keywords
                if ($type == "textk")
                {
                  $keywords = splitkeywords ($kra3str, $charset_dest);

                  if (is_array ($keywords)) $kra3str = implode (",", $keywords);
                  else $kra3str = "";
                }

                // remove tags
                $kra3str = strip_tags ($kra3str);

                // encode to UTF-8
                if (!is_utf8 ($kra3str)) $kra3str = utf8_encode ($kra3str);

                // textnodes search index in database
                $text_array[$text_id] = $kra3str;
                          
                $containerdata_new = setcontent ($containerdata, "<text>", "<textcontent>", "<![CDATA[".$kra3str."]]>", "<text_id>", $text_id, true);

                if ($containerdata_new == false)
                {
                  $containerdata_new = addcontent ($containerdata, $text_schema_xml, "", "", "", "<textcollection>", "<text_id>", $text_id);
                  $containerdata_new = setcontent ($containerdata_new, "<text>", "<textcontent>", "<![CDATA[".$kra3str."]]>", "<text_id>", $text_id, true);
                  $containerdata_new = setcontent ($containerdata_new, "<text>", "<textuser>", $user, "<text_id>", $text_id, true);
                }

                if ($containerdata_new != false) $containerdata = $containerdata_new;
                else
                {
                  $errcode = "20645";
                  $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Failed to write KRITA meta data to container with ID: ".$container_id;
                }
              }
            }
          }
        }

        // ------------------- binary IPTC block -------------------

        $size = getimagesize ($medialocation.$mediafile, $info);

        if (isset ($info['APP13']))
        {
          // parse binary IPTC block
          $iptc = iptcparse ($info['APP13']);

          if (is_array ($iptc))
          {
            $iptc_info = array();

            // list of most common IPTC tags
            // charset info contained in tag 1:90.
            if (isset ($iptc["1#090"][0])) $iptc_info['iptc:charset'] = trim ($iptc["1#090"][0]);
            else $iptc_info['iptc:charset'] = "";
            if (isset ($iptc["2#005"][0])) $iptc_info['iptc:title'] = trim ($iptc["2#005"][0]);
            else $iptc_info['iptc:title'] = "";
            if (isset ($iptc["2#010"][0])) $iptc_info['iptc:urgency'] = trim ($iptc["2#010"][0]);
            else $iptc_info['iptc:urgency'] = "";
            if (isset ($iptc["2#015"][0])) $iptc_info['iptc:category'] = trim ($iptc["2#015"][0]);
            else $iptc_info['iptc:category'] = "";
            // note that sometimes supplemental_categories contains multiple entries
            if (isset ($iptc["2#020"][0])) $iptc_info['iptc:supplemental_categories'] = trim ($iptc["2#020"][0]);
            else $iptc_info['iptc:supplemental_categories'] = "";
            // get keywords saved in tag 2:25 and generate keyword list
            if (isset ($iptc["2#025"]) && is_array ($iptc["2#025"])) $iptc_info['iptc:keywords'] = implode (", ", $iptc["2#025"]);
            else $iptc_info['iptc:keywords'] = "";
            if (isset ($iptc["2#037"][0])) $iptc_info['iptc:expiriation_date'] = trim ($iptc["2#037"][0]);
            else $iptc_info['iptc:expiriation_date'] = "";
            if (isset ($iptc["2#038"][0])) $iptc_info['iptc:expiriation_time'] = trim ($iptc["2#038"][0]);
            else $iptc_info['iptc:expiriation_time'] = "";
            if (isset ($iptc["2#040"][0])) $iptc_info['iptc:special_instructions'] = trim ($iptc["2#040"][0]);
            else $iptc_info['iptc:special_instructions'] = "";
            if (isset ($iptc["2#055"][0])) $iptc_info['iptc:creation_date'] = trim ($iptc["2#055"][0]);
            else $iptc_info['iptc:creation_date'] = "";
            if (isset ($iptc["2#060"][0])) $iptc_info['iptc:creation_time'] = trim ($iptc["2#060"][0]);
            else $iptc_info['iptc:creation_time'] = "";
            if (isset ($iptc["2#063"][0])) $iptc_info['iptc:digital_creation_date'] = trim ($iptc["2#063"][0]);
            else $iptc_info['iptc:digital_creation_date'] = "";
            if (isset ($iptc["2#065"][0])) $iptc_info['iptc:digital_creation_time'] = trim ($iptc["2#065"][0]);
            else $iptc_info['iptc:digital_creation_time'] = "";
            if (isset ($iptc["2#080"][0])) $iptc_info['iptc:photographer'] = trim ($iptc["2#080"][0]);
            else $iptc_info['iptc:photographer'] = "";
            if (isset ($iptc["2#085"][0])) $iptc_info['iptc:photographer_title'] = trim ($iptc["2#085"][0]);
            else $iptc_info['iptc:photographer_title'] = "";
            if (isset ($iptc["2#090"][0])) $iptc_info['iptc:city'] = trim ($iptc["2#090"][0]);
            else $iptc_info['iptc:city'] = "";
            if (isset ($iptc["2#095"][0])) $iptc_info['iptc:state'] = trim ($iptc["2#095"][0]);
            else $iptc_info['iptc:state'] = "";
            if (isset ($iptc["2#101"][0])) $iptc_info['iptc:country'] = trim ($iptc["2#101"][0]);
            else $iptc_info['iptc:country'] = "";
            if (isset ($iptc["2#103"][0])) $iptc_info['iptc:otr'] = trim ($iptc["2#103"][0]);
            else $iptc_info['iptc:otr'] = "";
            if (isset ($iptc["2#105"][0])) $iptc_info['iptc:headline'] = trim ($iptc["2#105"][0]);
            else $iptc_info['iptc:headline'] = "";
            if (isset ($iptc["2#110"][0])) $iptc_info['iptc:credit'] = trim ($iptc["2#110"][0]);
            else $iptc_info['iptc:credit'] = "";
            if (isset ($iptc["2#115"][0])) $iptc_info['iptc:source'] = trim ($iptc["2#115"][0]);
            else $iptc_info['iptc:source'] = "";
            if (isset ($iptc["2#116"][0])) $iptc_info['iptc:copyright'] = trim ($iptc["2#116"][0]);
            else $iptc_info['iptc:copyright'] = "";
            if (isset ($iptc["2#118"][0])) $iptc_info['iptc:contact'] = trim ($iptc["2#118"][0]);
            else $iptc_info['iptc:contact'] = "";
            if (isset ($iptc["2#120"][0])) $iptc_info['iptc:description'] = trim ($iptc["2#120"][0]);
            else $iptc_info['iptc:description'] = "";
            if (isset ($iptc["2#122"][0])) $iptc_info['iptc:description_author'] = trim ($iptc["2#122"][0]);
            else $iptc_info['iptc:description_author'] = "";
            if (isset ($iptc["2#130"][0])) $iptc_info['iptc:image_type'] = trim ($iptc["2#130"][0]);
            else $iptc_info['iptc:image_type'] = "";
            if (isset ($iptc["2#131"][0])) $iptc_info['iptc:image_orientation'] = trim ($iptc["2#131"][0]);
            else $iptc_info['iptc:image_orientation'] = "";
            if (isset ($iptc["2#135"][0])) $iptc_info['iptc:language'] = trim ($iptc["2#135"][0]);
            else $iptc_info['iptc:language'] = "";

            if (!empty ($iptc_info['iptc:charset']))
            {
              $charset_source = iptc_getcharset ($iptc_info['iptc:charset']);
            }
            else $charset_source = "";

            // inject meta data based on mapping
            reset ($mapping);

            foreach ($mapping as $key => $text_id)
            {
              // only for IPTC tags
              if (strpos ("_".$key, "iptc:") > 0 && $text_id != "")
              { 
                if ($iptc_info[$key] != "")
                {
                  // get type and text ID
                  if (strpos ($text_id, ":") > 0) list ($type, $text_id) = explode (":", $text_id);
                  elseif (substr_count (strtolower ($text_id), "keyword") > 0) $type = "textk";
                  else $type = "textu";

                  if (!empty ($type)) $type_array[$text_id] = $type;

                  // importing data from some Mac applications, they may put chr(213) into strings to access a closing quote character.
                  // This prints as a captial O with a tilde above it in a web browser or on Windows. 
                  $iptc_info[$key] = str_replace (chr(213), "'",  $iptc_info[$key]);

                  // clean keywords
                  if ($type == "textk")
                  {
                    $keywords = splitkeywords ($iptc_info[$key], $charset_dest);

                    if (is_array ($keywords)) $iptc_info[$key] = implode (",", $keywords);
                    else $iptc_info[$key] = "";
                  }

                  // remove tags
                  $iptc_info[$key] = strip_tags ($iptc_info[$key]);

                  // convert string since IPTC supports different charsets
                  if ($charset_source != "" && $charset_dest != "" && $charset_source != $charset_dest)
                  {
                    $iptc_info[$key] = convertchars ($iptc_info[$key], $charset_source, $charset_dest);
                  }
                  elseif ($charset_source == false && $charset_dest == "UTF-8")
                  {
                    // encode to UTF-8
                    if (!is_utf8 ($iptc_info[$key])) $iptc_info[$key] = utf8_encode ($iptc_info[$key]);
                  }

                  // textnodes for search index in database
                  $text_array[$text_id] = $iptc_info[$key];

                  $containerdata_new = setcontent ($containerdata, "<text>", "<textcontent>", "<![CDATA[".$iptc_info[$key]."]]>", "<text_id>", $text_id, true);

                  if ($containerdata_new == false)
                  {
                    $containerdata_new = addcontent ($containerdata, $text_schema_xml, "", "", "", "<textcollection>", "<text_id>", $text_id);
                    $containerdata_new = setcontent ($containerdata_new, "<text>", "<textcontent>", "<![CDATA[".$iptc_info[$key]."]]>", "<text_id>", $text_id, true);
                    $containerdata_new = setcontent ($containerdata_new, "<text>", "<textuser>", $user, "<text_id>", $text_id, true);
                  }

                  if ($containerdata_new != false) $containerdata = $containerdata_new;
                  else
                  {
                    $errcode = "20608";
                    $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Failed to write IPTC meta data to container with ID: ".$container_id;
                  }
                }
              }
            }
          } 
        }

        // ------------------- use Google Speech Service -------------------
        if (is_audio ($mediafile) || is_video ($mediafile))
        {
          if (function_exists ("GCspeech2text")) $google_data = GCspeech2text ($site, $medialocation.$mediafile);
          else $google_data = "";

          if (!empty ($google_data) && is_array ($google_data))
          {
            // source charset
            $charset_source = "UTF-8";

            $vtt_str = "";

            // create VTT
            foreach ($google_data as $record)
            {
              // only for tags
              if (!empty ($record['description']))
              {
                if (empty ($langcode)) $langcode = $record['language'];

                $temp_str = $record['description'];

                // remove tags
                $temp_str = strip_tags ($temp_str);

                // convert string for container
                if ($charset_source != "" && $charset_dest != "" && $charset_source != $charset_dest)
                {
                  $temp_str = convertchars ($temp_str, $charset_source, $charset_dest);
                }
                elseif ($charset_dest == "UTF-8")
                {
                  // encode to UTF-8
                  if (!is_utf8 ($temp_str)) $temp_str = utf8_encode ($temp_str);
                }

                $vtt_str .= sec2time ($record['starttime'])." --> ".sec2time ($record['endtime'])."\n";
                $vtt_str .= $temp_str."\n\n";
              }
            }

            if (!empty ($langcode) && !empty ($vtt_str))
            {
              $text_id = "VTT-".$langcode;
              $vtt_str = "WEBVTT\n\n".$vtt_str;

              // textnodes search index in database
              $text_array[$text_id] = $vtt_str;
                        
              $containerdata_new = setcontent ($containerdata, "<text>", "<textcontent>", "<![CDATA[".$vtt_str."]]>", "<text_id>", $text_id);

              if ($containerdata_new == false)
              {
                $containerdata_new = addcontent ($containerdata, $text_schema_xml, "", "", "", "<textcollection>", "<text_id>", $text_id);
                $containerdata_new = setcontent ($containerdata_new, "<text>", "<textcontent>", "<![CDATA[".$vtt_str."]]>", "<text_id>", $text_id);
                $containerdata_new = setcontent ($containerdata_new, "<text>", "<textuser>", $user, "<text_id>", $text_id);
              }

              if ($containerdata_new != false) $containerdata = $containerdata_new;
              else
              {
                $errcode = "20600";
                $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Failed to write Google Speech meta data to container with ID: ".$container_id;
              }
            }
          }
        }

        // ------------------- use Google Vision Cloud Service -------------------
        if (is_image ($mediafile) && !metadata_exists ($mapping, $text_array))
        {
          if (function_exists ("GCanalyzeimage")) $google_data = GCanalyzeimage ($site, $medialocation.$mediafile);
          else $google_data = "";

          if (!empty ($google_data) && is_array ($google_data))
          {
            // source charset
            $charset_source = "UTF-8";

            // inject meta data based on mapping
            reset ($mapping);

            foreach ($mapping as $key => $text_id)
            {
              // only for tags
              if (strpos ("_".$key, "google:") > 0 && $text_id != "")
              {
                // get tag name
                list ($namespace, $key) = explode (":", $key);
                $key = trim ($key);

                // get type and text ID
                if (strpos ($text_id, ":") > 0) list ($type, $text_id) = explode (":", $text_id);
                elseif (substr_count (strtolower ($text_id), "keyword") > 0) $type = "textk";
                else $type = "textu";

                if (!empty ($type)) $type_array[$text_id] = $type;

                // get data
                if (!empty ($google_data[$key])) $temp_str = $google_data[$key];
                else $temp_str = "";

                if ($temp_str != "")
                {
                  // clean keywords
                  if ($type == "textk")
                  {
                    $keywords = splitkeywords ($temp_str, $charset_dest);

                    if (is_array ($keywords)) $temp_str = implode (",", $keywords);
                    else $temp_str = "";
                  }

                  // remove tags
                  $temp_str = strip_tags ($temp_str);

                  // convert string for container
                  if ($charset_source != "" && $charset_dest != "" && $charset_source != $charset_dest)
                  {
                    $temp_str = convertchars ($temp_str, $charset_source, $charset_dest);
                  }
                  elseif ($charset_dest == "UTF-8")
                  {
                    // encode to UTF-8
                    if (!is_utf8 ($temp_str)) $temp_str = utf8_encode ($temp_str);
                  }

                  // textnodes search index in database
                  $text_array[$text_id] = $temp_str;
                            
                  $containerdata_new = setcontent ($containerdata, "<text>", "<textcontent>", "<![CDATA[".$temp_str."]]>", "<text_id>", $text_id, true);

                  if ($containerdata_new == false)
                  {
                    $containerdata_new = addcontent ($containerdata, $text_schema_xml, "", "", "", "<textcollection>", "<text_id>", $text_id);
                    $containerdata_new = setcontent ($containerdata_new, "<text>", "<textcontent>", "<![CDATA[".$temp_str."]]>", "<text_id>", $text_id, true);
                    $containerdata_new = setcontent ($containerdata_new, "<text>", "<textuser>", $user, "<text_id>", $text_id, true);
                  }

                  if ($containerdata_new != false) $containerdata = $containerdata_new;
                  else
                  {
                    $errcode = "20602";
                    $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Failed to write Google Vision meta data to container with ID: ".$container_id;
                  }
                }
              }
            }
          }
        }

        // ------------------- use Google Video Intelligence Cloud Service -------------------
        if (is_video ($mediafile) && !metadata_exists ($mapping, $text_array))
        {
          if (function_exists ("GCanalyzevideo")) $google_data = GCanalyzevideo ($site, $medialocation.$mediafile);
          else $google_data = "";

          if (!empty ($google_data) && is_array ($google_data))
          {
            // source charset
            $charset_source = "UTF-8";

            // thumbnail file is always in repository
            $thumb_root = getmedialocation ($site, ".hcms.".$mediafile, "abs_path_media").$site."/";
            $file_info = getfileinfo ($site, $mediafile, "comp");

            // get video dimensions
            avoidfilecollision ();
            $config = readmediaplayer_config ($thumb_root, $file_info['filename'].".config.orig");

            if (empty ($config['width'])) $config['width'] = 360;
            if (empty ($config['height'])) $config['height'] = 240;

            $faces_array = array();
            $google_data_collect = array();

            foreach ($google_data as $temp_array)
            {
              if (is_array ($temp_array))
              {
                // remove tags
                $temp_array['description'] = strip_tags ($temp_array['description']);
                $temp_array['keywords'] = strip_tags ($temp_array['keywords']);
 
                // convert string for container
                if ($charset_source != "" && $charset_dest != "" && $charset_source != $charset_dest)
                {
                  $temp_array['description'] = convertchars ($temp_array['description'], $charset_source, $charset_dest);
                  $temp_array['keywords'] = convertchars ($temp_array['keywords'], $charset_source, $charset_dest);
                }
                elseif ($charset_dest == "UTF-8")
                {
                  // encode to UTF-8
                  if (!is_utf8 ($temp_array['description'] )) $temp_array['description'] = utf8_encode ($temp_array['description']);
                  if (!is_utf8 ($temp_array['keywords'] )) $temp_array['keywords'] = utf8_encode ($temp_array['keywords']);
                } 

                // JSON substring
                $faces_array[] = "{\"videowidth\":".$config['width'].", \"videoheight\":".$config['height'].", \"time\":".$temp_array['starttime'].", \"x\":10, \"y\":10, \"width\":".(intval ($config['width']) - 20).", \"height\":".(intval ($config['height']) - 120).", \"name\":\"".$temp_array['keywords']."\"}";

                // collect data
                if (!empty ($google_data_collect['description'])) $google_data_collect['description'] .= ", ".$temp_array['description'];
                else $google_data_collect['description'] = $temp_array['description'];

                if (!empty ($google_data_collect['keywords'])) $google_data_collect['keywords'] .= ",".$temp_array['keywords'];
                else $google_data_collect['keywords'] = $temp_array['keywords'];
              }
            }

            // save JSON string to container
            if (sizeof ($faces_array) > 0)
            {
              $text_id = "Faces-JSON";
              $faces_json = "[".implode (", ", $faces_array)."]";

              // textnodes search index in database
              $text_array[$text_id] = $faces_json;
                        
              $containerdata_new = setcontent ($containerdata, "<text>", "<textcontent>", "<![CDATA[".$faces_json."]]>", "<text_id>", $text_id, true);

              if ($containerdata_new == false)
              {
                $containerdata_new = addcontent ($containerdata, $text_schema_xml, "", "", "", "<textcollection>", "<text_id>", $text_id);
                $containerdata_new = setcontent ($containerdata_new, "<text>", "<textcontent>", "<![CDATA[".$faces_json."]]>", "<text_id>", $text_id, true);
                $containerdata_new = setcontent ($containerdata_new, "<text>", "<textuser>", $user, "<text_id>", $text_id, true);
              }

              if ($containerdata_new != false) $containerdata = $containerdata_new;
              else return false;
            }

            // inject meta data based on mapping
            reset ($mapping);

            foreach ($mapping as $key => $text_id)
            {
              // only for tags
              if (strpos ("_".$key, "google:") > 0 && $text_id != "")
              {
                // get tag name
                list ($namespace, $key) = explode (":", $key);
                $key = trim ($key);

                // get type and text ID
                if (strpos ($text_id, ":") > 0) list ($type, $text_id) = explode (":", $text_id);
                elseif (substr_count (strtolower ($text_id), "keyword") > 0) $type = "textk";
                else $type = "textu";

                if (!empty ($type)) $type_array[$text_id] = $type;

                // get data
                if (!empty ($google_data_collect[$key])) $temp_str = $google_data_collect[$key];
                else $temp_str = "";

                if ($temp_str != "")
                {
                  // clean keywords
                  if ($type == "textk")
                  {
                    $keywords = splitkeywords ($temp_str, $charset_dest);

                    if (is_array ($keywords)) $temp_str = implode (",", $keywords);
                    else $temp_str = "";
                  }

                  // remove tags
                  $temp_str = strip_tags ($temp_str);

                  // convert string for container
                  if ($charset_source != "" && $charset_dest != "" && $charset_source != $charset_dest)
                  {
                    $temp_str = convertchars ($temp_str, $charset_source, $charset_dest);
                  }
                  elseif ($charset_dest == "UTF-8")
                  {
                    // encode to UTF-8
                    if (!is_utf8 ($temp_str)) $temp_str = utf8_encode ($temp_str);
                  }

                  // textnodes search index in database
                  $text_array[$text_id] = $temp_str;
                            
                  $containerdata_new = setcontent ($containerdata, "<text>", "<textcontent>", "<![CDATA[".$temp_str."]]>", "<text_id>", $text_id, true);

                  if ($containerdata_new == false)
                  {
                    $containerdata_new = addcontent ($containerdata, $text_schema_xml, "", "", "", "<textcollection>", "<text_id>", $text_id);
                    $containerdata_new = setcontent ($containerdata_new, "<text>", "<textcontent>", "<![CDATA[".$temp_str."]]>", "<text_id>", $text_id, true);
                    $containerdata_new = setcontent ($containerdata_new, "<text>", "<textuser>", $user, "<text_id>", $text_id, true);
                  }

                  if ($containerdata_new != false) $containerdata = $containerdata_new;
                  else
                  {
                    $errcode = "20603";
                    $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Failed to write Google Video Intelligence meta data to container with ID: ".$container_id;
                  }
                }
              }
            }
          }
        }

        // ------------------- define and set image quality -------------------
        if (!empty ($mapping['hcms:quality']))
        {
          // get dpi from XMP tag
          $xres_array = getcontent ($mediadata, "<tiff:XResolution>");

          if ($xres_array != false)
          {
            if (substr_count ($xres_array[0], "/") == 1)
            {
              list ($x1, $x2) = explode ("/", $xres_array[0]);
              $xres = $x1 / $x2;
            }
            else $xres = $xres_array[0];
          }
          // get dpi from EXIF (only for JPEG and TIFF)
          elseif (exif_imagetype ($medialocation.$mediafile))
          {
            if (is_array ($exif_data))
            {
              reset ($exif_data);

              foreach ($exif_data as $key => $section)
              {
                if (is_array ($section))
                {
        	        foreach ($section as $name => $val)
                  {
                    if (strtolower($key) != "thumbnail" && strtolower($name) == "xresolution")
                    {
                      $xres = $val;

                      if (substr_count ($xres, "/") == 1)
                      {
                        list ($x1, $x2) = explode ("/", $xres);
                        $xres = $x1 / $x2;
                      }
                    }
                  }
            	  }
              }
            }
          }

          if (!empty ($xres))
          {
            // get type and text ID
            $text_id = $mapping['hcms:quality'];

            if (strpos ($text_id, ":") > 0) list ($type, $text_id) = explode (":", $text_id);
            else $type = "textl";

            if (!empty ($type)) $type_array[$text_id] = $type;

            if ($xres >= 300) $quality = "Print";
            else $quality = "Web";

            // textnodes for search index in database
            $text_array[$text_id] = $quality;

            $containerdata_new = setcontent ($containerdata, "<text>", "<textcontent>", "<![CDATA[".$quality."]]>", "<text_id>", $text_id, true);

            if ($containerdata_new == false)
            {
              $containerdata_new = addcontent ($containerdata, $text_schema_xml, "", "", "", "<textcollection>", "<text_id>", $text_id);
              $containerdata_new = setcontent ($containerdata_new, "<text>", "<textcontent>", "<![CDATA[".$quality."]]>", "<text_id>", $text_id, true);
              $containerdata_new = setcontent ($containerdata_new, "<text>", "<textuser>", $user, "<text_id>", $text_id, true);
            }

            if ($containerdata_new != false) $containerdata = $containerdata_new;
            else
            {
              $errcode = "20609";
              $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Failed to write QUALITY meta data to container with ID: ".$container_id;
            }
          }
        }

        // delete temp file
        if (!empty ($temp['result']) && !empty ($temp['created']) && is_file ($temp['templocation'].$temp['tempfile']))
        {
          deletefile ($temp['templocation'], $temp['tempfile'], 0);
        }

        if ($containerdata != false)
        {
          // save content in database (Important!)
          if (!empty ($mgmt_config['db_connect_rdbms']) && is_array ($text_array))
          {
            if (!function_exists ("rdbms_setcontent")) include_once ($mgmt_config['abs_path_cms']."database/db_connect/".$mgmt_config['db_connect_rdbms']);
            rdbms_setcontent ($site, $container_id, $text_array, $type_array, $user);
          }

          // save container
          if ($savecontainer == true)
          {
            $save = savecontainer ($container, "work", $containerdata, $user);

            if ($save == false)
            {
              $errcode = "20581";
              $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|".$errcode."|Failed to write meta data to container with ID: ".$container_id;
            }
          }
        }
      }
    }
  }

  // save log
  savelog ($error);

  // return content container on success
  if (!empty ($containerdata)) return $containerdata;
  else return false;
}
?>