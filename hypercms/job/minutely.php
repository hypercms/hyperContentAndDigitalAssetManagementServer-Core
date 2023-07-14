<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 * 
 */
 
// limit max entries to process from the queue
$max_queue = 100;


// main configuration file must exist
if (is_file ("../config/config.inc.php"))
{
  // management configuration
  require ("../config.inc.php");
  // hyperCMS API
  require ("../function/hypercms_api.inc.php");


  // initialize
  $error = array();
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

          $i = 0;

          foreach ($queue_array as $queue)
          {
            if ($i <= $max_queue && !empty ($queue['queue_id']) && !empty ($queue['action']) && (!empty ($queue['object_id']) || !empty ($queue['objectpath'])) && !empty ($queue['user']))
            {
              // execute PHP command
              if ($queue['action'] == "execute" && !empty ($queue['cmd']))
              {
                $queue_id = $queue['queue_id'];
                $queue_date = $queue['date'];
                $cmd = $queue['cmd'];
                $user = $queue['user'];

                // set new execution date in order to prevent multiple executions
                $new_date = date ('Y-m-d H:i:s', strtotime ($queue_date . ' +1 day'));
                rdbms_setqueueentry ($queue_id, $new_date);
                
                // execute
                if (trim ($cmd) != "") eval ($cmd);

                $result = true;
              }
              // process e-mail
              elseif ($queue['action'] == "mail" && !empty ($queue['object_id']))
              {
                $queue_id = $queue['queue_id'];
                $queue_date = $queue['date'];
                $action = $queue['action'];
                $mail_id = $queue['object_id'];
                $user = $queue['user'];

                // set new execution date in order to prevent multiple executions
                $new_date = date ('Y-m-d H:i:s', strtotime ($queue_date . ' +1 day'));
                rdbms_setqueueentry ($queue_id, $new_date);

                // process objects
                $result = processobjects ($action, "", "", $mail_id, "", $user);
              }
              // process object
              elseif ($queue['action'] != "execute" && $queue['action'] != "mail" && !empty ($queue['objectpath']))
              { 
                $queue_id = $queue['queue_id'];
                $queue_date = $queue['date'];
                $action = $queue['action']; 
                $site = getpublication ($queue['objectpath']);
                $location = getlocation ($queue['objectpath']);
                $file = getobject ($queue['objectpath']);
                $published_only = $queue['published_only'];
                $user = $queue['user'];

                // set new execution date in order to prevent multiple executions
                $new_date = date ('Y-m-d H:i:s', strtotime ($queue_date . ' +1 day'));
                rdbms_setqueueentry ($queue_id, $new_date);

                // if folder object remove .folder
                if ($file == ".folder")
                {
                  $file = getobject ($location);
                  $location = getlocation ($location);
                }

                // process objects
                $result = processobjects ($action, $site, $location, $file, $published_only, $user);
              }

              // remove entry from queue on success
              if ($result == true)
              {
                rdbms_deletequeueentry ($queue_id);
              }
              // try again in 10 minutes on error
              elseif (!empty ($queue_id) && !empty ($queue_date))
              {
                // set new execution date
                $new_date = date ('Y-m-d H:i:s', strtotime ($queue_date . ' +10 minutes'));
                rdbms_setqueueentry ($queue_id, $new_date);

                $errcode = "20910";
                $error[] = $mgmt_config['today']."|minutely.php|error|".$errcode."|Processing of queue ID '".$queue_id."' with datetime ".$queue_date." failed, process will be restarted again in 10 minutes";
              }

              $i++;

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
    $serverload = array ($mgmt_config['today']."|".str_replace("|", "&#124;", $report['load'])."|".str_replace("|", "&#124;", $report['cpu'])."|".str_replace("|", "&#124;", $report['memory']));

    savelog ($serverload, "serverload");

    // warning in system event log
    if ($report['load'] > 0.9)
    {
      $errcode = "00911";
      $error[] = $mgmt_config['today']."|minutely.php|warning|".$errcode."|Server load is ".round ($report['load'] * 100)."%";

      savelog (@$error);
    }
  }
}
else echo "Main configuration file is missing";
?>
