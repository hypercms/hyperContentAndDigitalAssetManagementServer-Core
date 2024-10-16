<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */
 
// --------------------------------------- savelog ------------------------------------------------
// function: savelog()
// input: path to file, file name/directory name, file data
// output: true/false

// description:
// Saves logging information to file

function savelog ($abs_path, $filename, $filedata)
{
  if (is_file ($abs_path.$filename)) return appendfile ($abs_path, $filename, $filedata);
  else return savefile ($abs_path, $filename, $filedata); 
}

// ------------------------------------------ deletefile --------------------------------------------
// function: deletefiles()
// input: path to file, file name/directory name
// output: true/false

// description:
// Deletes given file or all files in given directory

function deletefiles ($location, $file)
{
  if ($location != "" && $file != "")
  {
    // if selected file is a directory
    if (is_dir ($location.$file))
    {
      $scandir = scandir ($location.$file);

      if ($scandir)
      {
        $result = true;

        foreach ($scandir as $dirfile)
        {
          if ($dirfile != "." && $dirfile != "..")
          {
            if (is_dir ($location.$file."/".$dirfile)) 
            {
              $result = deletefiles ($location.$file."/", $dirfile);
            } 
            else
            {
              $result = @unlink ($location.$file."/".$dirfile);
            } 
          }
        }
      }

      // delete directory itself
      if ($result != false)
      {
        return @rmdir ($location.$file);
      }
      else return false;
    }
    // if selected file is a file
    elseif (is_file ($location.$file))
    {
      // remove selected file
      return @unlink ($location.$file);
    }
    // file whether a file nor a dir
    else return false;
  }
  else return false;
}

// ---------------------- hcms_crypt -----------------------------
// function: hcms_crypt()
// input: string to encode, start position, length for string extraction
// output: encoded string / false on error

// description:
// Unidrectional encryption using sha1 and urlencode. Used to create tokens for simple view links in the system.
// The tokens can be verified by calculating the hash of the media file name and comparing the hash values.
// Don't use this function to secure any string or to for password hashing.

function hcms_crypt ($string, $start=0, $length=0)
{
  global $mgmt_config;

  if ($string != "")
  {
    // set default private key for hashing
    if (empty ($mgmt_config['crypt_key'])) $mgmt_config['crypt_key'] = "h1y2p3e4r5c6m7s8";

    // reduce string for faster encryption
    if ($start == 0 && $length == 0 && strlen ($string) > 32) $string = substr ($string, -32);

    // encoding algorithm
    $string_encoded = hash_hmac ("sha1", $string, $mgmt_config['crypt_key']);

    // extract substring
    if ($length != 0) $string_encoded = substr ($string_encoded, $start, $length);
    elseif ($start != 0) $string_encoded = substr ($string_encoded, $start);

    // urlencode string
    $string_encoded = urlencode ($string_encoded);

    if ($string_encoded != "") return $string_encoded;
    else return false;
  }
  else return false;
}

// --------------------------------------- savefile ------------------------------------------------
// function: savefile()
// input: path to file, file name, file content 
// output: true/false

function savefile ($abs_path, $filename, $filedata)
{
  global $user, $mgmt_config;

  if ($abs_path != "" && $filename != "")
  {
    $filehandle = @fopen ($abs_path.$filename, "wb");

    if ($filehandle != false)
    {
      if ($filedata != false)
      {
        @fwrite ($filehandle, $filedata);
      }

      @fclose ($filehandle);
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
// Function appendfile just appends data to a file but cannot create a new file!

function appendfile ($abs_path, $filename, $filedata)
{
  global $user, $mgmt_config;

  if ($abs_path != "" && $filename != "")
  {
    // if file exists
    if (is_file ($abs_path.$filename))
    {
      $filehandle = @fopen ($abs_path.$filename, "a");

      if ($filehandle != false)
      {
        if ($filedata != false)
        {
          @fwrite ($filehandle, $filedata);
        }

        @fclose ($filehandle);
        return true;
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// ------------------------- valid_publicationname -----------------------------
// function: valid_publicationname()
// input: expression
// output: true/false

// description:
// Test if an expression includes forbidden characters (true) or doesnt (false) to prevent directory browsing

function valid_publicationname ($expression)
{
  if ($expression != "")
  {
    if ($expression == "*Null*" || $variable == "*no_memberof*") return false;
    if (substr_count ($expression, "/") >  0) return false;
    if (substr_count ($expression, "\\") >  0) return false;
    return $expression;
  }
  else return false;
}

// ------------------------- valid_locationname -----------------------------
// function: valid_locationname()
// input: expression
// output: true/false

// description:
// Test if an expression includes forbidden characters (true) or doesnt (false) to prevent directory browsing

function valid_locationname ($expression)
{
  if ($expression != "")
  {
    if (substr_count ($expression, "../") > 0) return false;
    if (substr_count ($expression, "..\\") > 0) return false;
    if (substr_count ($expression, ".\\") > 0) return false;
    if (substr_count ($expression, ".\\") > 0) return false;
    if (substr_count ($expression, "\\0") > 0) return false;
    return $expression;
  }
  else return false;
}

// ------------------------- valid_objectname -----------------------------
// function: valid_objectname()
// input: expression
// output: true/false

// description:
// Test if an expression includes forbidden characters (true) or doesnt (false) to prevent directory browsing

function valid_objectname ($expression)
{
  if ($expression != "")
  {
    if (substr_count ($expression, "../") >  0) return false;
    if (substr_count ($expression, "\\") >  0) return false;
    return $expression;
  }
  else return false;
}

// ---------------------- remoteclient -----------------------------
// function: remoteclient()
// input: action [save, copy, delete, rename, get], passcode, root [abs_path_link, abs_path_media, abs_path_comp, abs_path_page, abs_path_rep], publication, location, locationnew, page, pagenew
// output: http answer [string] or false

// description:
// The remoteclient receives data from the CMS server and executes actions (file handling)
// It also can send back text-content if action=get. this content is packed inside <remotecontent> tags.

function remoteclient ($action, $passcode, $root, $site, $location, $locationnew, $page, $pagenew, $content, $filedata)
{
  if ($passcode == hcms_crypt ($location.$page)) 
  {
    if ($action != "" && $root != "" && valid_publicationname ($site))
    {
      // load publication config file of publication system
      if (valid_publicationname ($site) && is_file ($site.".ini")) $publ_config = parse_ini_file ($site.".ini");

      // correct root
      $root = str_replace ("_path_", "_publ_", $root);

      // actions
      if ($action == "save" && valid_locationname ($location) && valid_objectname ($page))
      {
        // save text file
        if ($content != "") return savefile ($publ_config[$root].$location, $page, $content);
        // save multimedia file (binary)
        elseif (is_array ($filedata)) return move_uploaded_file ($filedata['tmp_name'], $publ_config[$root].$location.$page);
        // make directory
        else return mkdir ($publ_config[$root].$location.$page);
      }
      elseif ($action == "copy" && valid_locationname ($location) && valid_locationname ($locationnew) && valid_objectname ($page) && valid_objectname ($pagenew))
      {
        if ($locationnew == "") $locationnew = $location;
        elseif ($pagenew == "") $pagenew = $page;

        return copy ($publ_config[$root].$location.$page, $publ_config[$root].$locationnew.$pagenew);
      }
      elseif ($action == "rename" && valid_locationname ($location) && valid_locationname ($locationnew) && valid_objectname ($page) && valid_objectname ($pagenew))
      {
        if ($locationnew == "") $locationnew = $location;

        return rename ($publ_config[$root].$location.$page, $publ_config[$root].$locationnew.$pagenew);
      }
      elseif ($action == "delete" && valid_locationname ($location) && valid_objectname ($page))
      {
        return deletefiles ($publ_config[$root].$location, $page);
      }
      elseif ($action == "get" && valid_locationname ($location) && valid_objectname ($page))
      {
        if (file_exists ($publ_config[$root].$location.$page))
        {
          echo "<remotecontent>";
          readfile ($publ_config[$root].$location.$page);
          echo "</remotecontent>";
          return true;
        }
        else return false;
      } 
    }
    else return false;
  }
  else return false;
}

// save log
// savelog ("", "log.txt", date ("Y-m-d H:i:s", time()).", ".$_POST['action'].", ".$_POST['passcode'].", ".$_POST['root'].", ".$_POST['site'].", ".$_POST['location'].", ".$_POST['locationnew'].", ".$_POST['page'].", ".$_POST['pagenew']."\r\n");

// call client
if (!empty ($_POST)) remoteclient ($_POST['action'], $_POST['passcode'], $_POST['root'], $_POST['site'], $_POST['location'], $_POST['locationnew'], $_POST['page'], $_POST['pagenew'], $_POST['content'], $_FILES['Filedata']);
?>
