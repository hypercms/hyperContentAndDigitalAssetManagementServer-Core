<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */

// management configuration
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");


// input parameters
$pass = getrequest ("pass");
$logname = getrequest ("logname");

// default support passcode for support service access
if (empty ($mgmt_config['support_pass'])) $mgmt_config['support_pass'] = "hypercms";

// ------------------------------ permission section --------------------------------

// check session of user
if (!empty ($mgmt_config['support_pass']) && $pass != $mgmt_config['support_pass']) exit;

// --------------------------------- logic section ----------------------------------

// get log file
if (valid_objectname ($logname) && is_file ($mgmt_config['abs_path_data']."log/".$logname.".log"))
{
  $file = $mgmt_config['abs_path_data']."log/".$logname.".log";
  $filesize   = filesize ($file);

  header ('Content-Description: File Transfer');
  header ('Content-Type: application/octet-stream');
  header ('Content-Disposition: attachment; filename='.$logname.".log"); 
  header ('Content-Transfer-Encoding: binary');
  header ('Connection: Keep-Alive');
  header ('Expires: 0');
  header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
  header ('Pragma: public');
  header ('Content-Length: '.$filesize);

  readfile ($mgmt_config['abs_path_data']."log/".$logname.".log");
}
?>