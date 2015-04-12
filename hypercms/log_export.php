<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");


// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('site')) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// file name of event log
$logfile = "event.log";

if (@file_exists ($mgmt_config['abs_path_data']."log/".$logfile))
{
  $data = loadfile_fast ($mgmt_config['abs_path_data']."log/", $logfile);

  if ($data != false)
  {
    $data = str_replace ("|", ";", $data);
    
    $data = "date/time;source;code;description\n".$data;
    
    // define type
    header ("Content-type: application/octet-stream");

    // define filename
    header ("Content-Disposition: attachment; filename=eventlog.csv");

    echo $data;
  }
  else
  {
    // define type
    header ("Content-type: application/octet-stream");

    // define filename
    header ("Content-Disposition: attachment; filename=eventlog.csv");
      
    echo "";
  }  
}
else
{
  // define type
  header ("Content-type: application/octet-stream");

  // define filename
  header ("Content-Disposition: attachment; filename=eventlog.csv");
    
  echo "";
}
?>
