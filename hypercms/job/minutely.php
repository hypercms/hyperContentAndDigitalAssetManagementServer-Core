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


$config_files = array();

// if multiple instances are used
if ($mgmt_config['instances'])
{
  // collect instances
  $location = $mgmt_config['abs_path_cms']."config/";
  
  if ($location != "" && $scandir = scandir ($location))
  {
    foreach ($scandir as $file)
    {
      if ($file != "" && is_file ($location.$file) && substr_count ($file, ".inc.php") > 0)
      {
        $config_files[] = $file;    
      }
    }
  }
}
else $config_files[0] = "config.inc.php";


// execute jobs for each instance
if (sizeof ($config_files) > 0)
{
  foreach ($config_files as $config_file)
  {
    // load main config
    require ("../".$config_file);
    
    // process objects in queue
    if (!empty ($mgmt_config['db_connect_rdbms']) && !empty ($mgmt_config['abs_path_cms']) && !empty ($mgmt_config['abs_path_data']))
    {
      // load queue
      $now = date ("Y-m-d H:i", time());
      $queue_array = rdbms_getqueueentries ("", "", $now, "");
    
      if (is_array ($queue_array))
      {
        $result = false;
    
        foreach ($queue_array as $queue)
        {
          if ($queue['queue_id'] != "" && $queue['action'] != "" && ($queue['object_id'] != "" || $queue['objectpath'] != "") && $queue['user'] != "")
          {
            // mail
            if ($queue['action'] == "mail" && $queue['object_id'] != "")
            {
              $queue_id = $queue['queue_id'];
              $action = $queue['action'];
              $mail_id = $queue['object_id'];
              $user = $queue['user'];
              
              // process objects
              $result = processobjects ($action, "", "", $mail_id, "", $user);
            }
            // object
            elseif ($queue['objectpath'] != "")
            { 
              $queue_id = $queue['queue_id'];
              $action = $queue['action']; 
              $site = getpublication ($queue['objectpath']);
              $location = getlocation ($queue['objectpath']);
              $file = getobject ($queue['objectpath']);
              $published_only = $queue['published_only'];
              $user = $queue['user'];
      
              // if folder object remove .folder
              if ($file == ".folder")
              {
                $location = getlocation ($location);
                $file = getobject ($location);
              }
              
              // process objects
              $result = processobjects ($action, $site, $location, $file, $published_only, $user);
            }

            // remove entry from queue
            if ($result == true)
            {
              rdbms_deletequeueentry ($queue_id);
            }
            
            // save log
            savelog (@$error);       
          }
        }
      }
    }
  }
}

// save server load in log
$report = getserverload();

if (!empty ($report) && is_array ($report))
{
  $serverload = $mgmt_config['today']."|".$report['load']."|".$report['cpu']."|".$report['memory'];
  
  savelog (array ($serverload), "serverload");
  
  // warning in system event log
  if ($report['load'] > 0.9)
  {
    $errcode = "00911";
    $error = $mgmt_config['today']."|minutely.php|warning|".$errcode."|server load is ".round ($report['load'] * 100)."%";
    
    savelog (array ($error));
  }
}
?>
