<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// =================================== EN/DECRYPTION FUNCTIONS FOR FREE EDITION =====================================

// ---------------------- encryptfile -----------------------------
// function: encryptfile()
// input: path to file [string], file name [string], key (optional)
// output: false


function encryptfile ($location, $file, $key="")
{
  return false;
}

// ---------------------- decryptfile -----------------------------
// function: decryptfile()
// input: path to file [string], file name [string], key (optional)
// output: false

function decryptfile ($location, $file, $key="")
{
  return false;
}

// ---------------------- createtempfile -----------------------------
// function: createtempfile()
// input: path to file [string], file name [string], key (optional)
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
// input: path to file [string], file name [string], delete temp file [true/false] (optional), 
//        force encryption of file [true/false] (optional), key (optional)
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