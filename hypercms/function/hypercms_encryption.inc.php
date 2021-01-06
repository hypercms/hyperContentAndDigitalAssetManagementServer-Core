<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */
 
// =================================== EN/DECRYPTION FUNCTIONS FOR FREE EDITION =====================================

// ---------------------- encryptfile -----------------------------
// function: encryptfile()
// input: path to file [string], file name [string], key [string] (optional)
// output: false

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
      return loadfile ($location, $file);
    }
    else return false;
  }
  else return false;
}

// ---------------------- decryptfile -----------------------------
// function: decryptfile()
// input: path to file [string], file name [string], key [string] (optional)
// output: false

function decryptfile ($location, $file, $key="")
{
  if (valid_locationname ($location) && valid_objectname ($file))
  {
    // add slash if not present at the end of the location string
    $location = correctpath ($location);
    
    if (is_file ($location.$file))
    {
      // load file
      return loadfile ($location, $file);
    }
    else return false;
  }
  else return false;
}

// ---------------------- createtempfile -----------------------------
// function: createtempfile()
// input: path to file [string], file name [string], key [string] (optional)
// output: input as result array

function createtempfile ($location, $file, $key="")
{
  $result = array();
  $result['result'] = false;
  $result['crypted'] = false;
  $result['created'] = false;
  $result['location'] = $location;
  $result['file'] = $file;
  $result['templocation'] = "";
  $result['tempfile'] = "";

  // return result
  return $result;
}

// ---------------------- movetempfile -----------------------------
// function: movetempfile()
// input: path to file [string], file name [string], delete temp file [boolean] (optional), 
//        force encryption of file [boolean] (optional), key [string] (optional)
// output: input as result array

function movetempfile ($location, $file, $delete=false, $force_encrypt=false, $key="")
{
  $result = array();
  $result['result'] = false;
  $result['crypted'] = false;
  $result['location'] = $location;
  $result['file'] = $file;

  // return result
  return $result;
}
?>