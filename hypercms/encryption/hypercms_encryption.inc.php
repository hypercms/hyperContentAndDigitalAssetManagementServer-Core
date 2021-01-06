<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */
 
// ============================================ EN/DECRYPTION FUNCTIONS ========================================

// ---------------------- encryptfile -----------------------------
// function: encryptfile()
// input: path to file [string], file name [string], key (optional)
// output: content of encrypted file / false on error

// description:
// Encryption of a file if it has not already been encrypted.
// Encryption level is strong since encryption must be binary-safe.

function encryptfile ($location, $file, $key="")
{
  global $user, $mgmt_config, $hcms_lang, $lang;
  
  if (valid_locationname ($location) && valid_objectname ($file))
  {
    // add slash if not present at the end of the location string
    $location = correctpath ($location);
    
    if (is_file ($location.$file))
    {
      // load file
      $data = loadfile ($location, $file);
      
      // encrypt data if file is not encypted
      if (!empty ($data) && strpos ("_".$data, "<!-- hyperCMS:encrypted -->") == 0)
      {
        // decrpyt content
        $data = hcms_encrypt ($data, $key, "strong", "none");
          
        if (!empty ($data)) return "<!-- hyperCMS:encrypted -->".$data;
        else return false;
      }
      else return $data;
    }
    else return false;
  }
  else return false;
}

// ---------------------- decryptfile -----------------------------
// function: decryptfile()
// input: path to file [string], file name [string], key (optional)
// output: content of decrypted file / false on error

// description:
// Decrypts of a file if it has not already been decrypted.
// Decryption level is strong since decryption must be binary-safe.

function decryptfile ($location, $file, $key="")
{
  global $user, $mgmt_config, $hcms_lang, $lang;

  if (valid_locationname ($location) && valid_objectname ($file))
  {
    // add slash if not present at the end of the location string
    $location = correctpath ($location);

    if (is_file ($location.$file))
    {
      // load file
      $data = loadfile ($location, $file);

      // decrypt data if file is encypted
      if (!empty ($data) && strpos ("_".$data, "<!-- hyperCMS:encrypted -->") > 0)
      {
        $data = str_replace ("<!-- hyperCMS:encrypted -->", "", $data);
        $data = hcms_decrypt ($data, $key, "strong", "none");
          
        if (!empty ($data)) return $data;
        else return false;
      }
      else return $data;
    }
    else return false;
  }
  else return false;
}

// ---------------------- createtempfile -----------------------------
// function: createtempfile()
// input: path to file [string], file name [string], key (optional)
// output: saves temporary decrypted file if the files content is encrypted and returns parh to file / false on error

// description:
// decrypts the provided file if it has not already been decrypted and saves it as temporary file.
// decryption level is strong since decryption must be binary-safe.

function createtempfile ($location, $file, $key="")
{
  global $user, $mgmt_config, $hcms_lang, $lang;
  
  $result = array();
  $result['result'] = false;
  $result['crypted'] = false;
  $result['created'] = false;
  $result['location'] = $location;
  $result['file'] = $file;
  $result['templocation'] = "";
  $result['tempfile'] = "";
  
  if (valid_locationname ($location) && valid_objectname ($file) && !empty ($mgmt_config['abs_path_cms']))
  {    
    // add slash if not present at the end of the location string
    $location = correctpath ($location);
    
    // define temporary file location to store decrypted file to
    $location_temp = $mgmt_config['abs_path_temp'];
    $file_temp = "stream.".$file;
    
    // check if file is encrypted and is not a thumbnail image
    if (!is_thumbnail ($file))
    {
      $is_encryptedfile = is_encryptedfile ($location, $file);
      if ($is_encryptedfile) $result['crypted'] = true;
    }
    else $is_encryptedfile = false;

    // get MD5 of unencrypted file
    $md5_orig = "";
    
    if ($is_encryptedfile)
    {
      $container_id = getmediacontainerid ($file);
      
      if ($mgmt_config['db_connect_rdbms'] != "" && !empty ($container_id))
      {
        $media_info = rdbms_getmedia ($container_id);
        $md5_orig = $media_info['md5_hash'];
      }
    }

    // file is not encrypted
    if (!$is_encryptedfile)
    {
      $result['result'] = true;
    }
    // file is encrypted and temp file exists already, is newer than encrypted file or MD5 hashes are equal and temp file is not encrypted
    elseif (
             $is_encryptedfile && 
             is_file ($location_temp.$file_temp) && 
             (filemtime ($location_temp.$file_temp) >= filemtime ($location.$file) || md5_file ($location_temp.$file_temp) == $md5_orig) && 
             !is_encryptedfile ($location_temp, $file_temp)
           )
    {
      $result['result'] = true;
      $result['templocation'] = $location_temp;
      $result['tempfile'] = $file_temp;
    }
    // decrypted temporary file must be created (if temporary file does not exist or is older than original file and MD5 hash is not equal)
    elseif (
             $is_encryptedfile && 
             (
               !file_exists ($location_temp.$file_temp) || 
               (
                 is_file ($location_temp.$file_temp) && 
                 filemtime ($location_temp.$file_temp) < filemtime ($location.$file) && 
                 md5_file ($location_temp.$file_temp) != $md5_orig
               ) || 
               is_encryptedfile ($location_temp, $file_temp)
             )
           )
    {
      // load file
      $data = loadfile ($location, $file);

      // decrypt data if file is encypted
      if (!empty ($data) && strpos ("_".$data, "<!-- hyperCMS:encrypted -->") > 0)
      {
        $data = str_replace ("<!-- hyperCMS:encrypted -->", "", $data);
        $data = hcms_decrypt ($data, $key, "strong", "none");
        
        // save decrypted file
        $save = savefile ($location_temp, $file_temp, $data);

        // file has been encrypted and saved
        if ($save)
        {
          $result['result'] = true;
          $result['created'] = true;
          $result['templocation'] = $location_temp;
          $result['tempfile'] = $file_temp;
        }
      }
      // file is not encrypted because it is empty
      else
      {
        $result['result'] = true;
      }
    }
  }

  // return result
  return $result;
}

// ---------------------- movetempfile -----------------------------
// function: movetempfile()
// input: path to file [string], file name [string], delete temp file [true/false] (optional), 
//        force encryption of file [true/false] (optional), key (optional)
// output: content of encrypted file / false on error

// description:
// encrypts the temporary file if it exists and copies or moves it to the location.
// encryption level is strong since encryption must be binary-safe.

function movetempfile ($location, $file, $delete=false, $force_encrypt=false, $key="")
{
  global $user, $mgmt_config, $hcms_lang, $lang;
  
  $result = array();
  $result['result'] = false;
  $result['crypted'] = false;
  $result['location'] = $location;
  $result['file'] = $file;
  
  // extract publication (get the directory name from location)
  // only works if file is stored in the repository
  $site = getobject ($location);

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($file) && !empty ($mgmt_config['abs_path_cms']))
  {
    // add slash if not present at the end of the location string
    $location = correctpath ($location);
    
    // define temporary file location to store decrypted file to
    $location_temp = $mgmt_config['abs_path_temp'];
    $file_temp = "stream.".$file;
    
    // temp file and source file exists
    if (is_file ($location_temp.$file_temp))
    {
      // load temp file
      $data = loadfile ($location_temp, $file_temp);

      // delete temp file
      if ($delete == true) deletefile ($location_temp, $file_temp);
      
      // encrypt data if file is not encypted or is not a thumbnail
      if (
           (
             (isset ($mgmt_config[$site]['crypt_content']) && $mgmt_config[$site]['crypt_content'] == true) || 
             $force_encrypt == true
           ) && 
           !is_thumbnail ($file_temp) && !empty ($data) && strpos ("_".$data, "<!-- hyperCMS:encrypted -->") == 0
         )
      {
        // encrpyt content
        $data = hcms_encrypt ($data, $key, "strong", "none");

        // add crypted information to files content
        if (!empty ($data))
        {
          $data = "<!-- hyperCMS:encrypted -->".$data;
          $result['crypted'] = true;
        }
      }
      
      // save file  
      if (!empty ($data))
      {
        $result['result'] = savefile ($location, $file, $data);
      }
    }
    // temp file has not been created and does not exist (encryption of content is not enabled)
    else
    {
      $result['result'] = true;
    }
  }
  
  // return result
  return $result;
}
?>