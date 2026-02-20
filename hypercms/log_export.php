<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");


// input parameters
$site = getrequest ("site", "publicationname");
$login = getrequest ("login", "objectname");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('site') && !checkrootpermission ('user')) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// file name of event log
if (valid_objectname ($login)) $logfile = $login.".user.log";
elseif (valid_publicationname ($site)) $logfile = $site.".publication.log";
else $logfile = "event.log";

if ($logfile != "" && is_file ($mgmt_config['abs_path_data']."log/".$logfile))
{
  $data = loadfile_fast ($mgmt_config['abs_path_data']."log/", $logfile);

  if ($data != false)
  {
    $data = str_replace ("\t", " ", $data);
    $data = str_replace ("|", ";", $data);
    
    $data = "date/time;source;type;code;description\n".$data;
    
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
