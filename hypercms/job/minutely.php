<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
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
  
  if ($location != "" && $dir = opendir ($location))
  {
    while (($file = readdir ($dir)) !== false)
    {
      if ($file != "" && is_file ($location.$file) && substr_count ($file, ".inc.php") > 0)
      {
        $config_files[] = $file;    
      }
    }
    
    closedir ($dir);
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
          if ($queue['queue_id'] != "" && $queue['action'] != "" && $queue['objectpath'] != "" && $queue['published_only'] != "" && $queue['user'] != "")
          {   
            $queue_id = $queue['queue_id'];
            $action = $queue['action']; 
            $site = getpublication ($queue['objectpath']);
            $location = getlocation ($queue['objectpath']);
            $file = getobject ($queue['objectpath']);
            $published_only = $queue['published_only'];
            $user = $queue['user'];
    
            // process objects
            $result = processobjects ($action, $site, $location, $file, $published_only, $user);
    
            // remove entry from queue
            if ($result == true)
            {
              rdbms_deletequeueentry ($queue_id);
            }
            else
            {
              $errcode = "50091";
              $error[] = $mgmt_config['today']."|publish.php|error|$errcode|action ".$action." failed on ".$location.$file;  
            }
            
            // save log
            savelog (@$error);       
          }
        }
      }
    }
  }
}
?>
