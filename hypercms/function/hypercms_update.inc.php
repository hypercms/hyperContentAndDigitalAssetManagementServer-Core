<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// ======================================== UPDATE FUNCTIONS ============================================

// ------------------------------------------ update_groups ----------------------------------------------
// function: update_groups()
// input: publication name, user group data (XML)
// output: updated user group data (XML), false on error

// description: update to version 5.6.4 (group names will be replaced by object-IDs)

function update_usergroups_v564 ($site, $data)
{
  global $mgmt_config;

  if ($site != "" && $data != "")
  {
    $replace = array();    
    $pattern_array = array ("%page%/", "%comp%/");
    
    foreach ($pattern_array as $pattern)
    {
      $offset = 0;
      
      while (strpos ($data, $pattern, $offset) > 0)
      {
        $start = strpos ($data, $pattern, $offset);
        $stop = strpos ($data, "|", $start);
        $length = $stop - $start;
        $offset = $stop;
        
        if ($length > 0)
        {
          $path = substr ($data, $start, $length);
          $object_id = rdbms_getobject_id ($path);
          
          if ($object_id != "") $replace[$path] = $object_id;
        }
      }
    }
    
    // replace/update
    if (is_array ($replace) && sizeof ($replace) > 0)
    {
      $datanew = $data;
      
      foreach ($replace as $path => $object_id)
      {
        $datanew = str_replace ($path."|", $object_id."|", $datanew);
      }
    }
      
    // return container
    if ($datanew != "") return savefile ($mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php", $datanew);
    else return false;      
  }
  else return false;
}

// ------------------------------------------ update_tasks ----------------------------------------------
// function: update_groups()
// input: %
// output: updated tasks data (from XML to RDBMS), false on error

// description: update tasks to version 5.8.4

function update_tasks_v584 ()
{
  global $mgmt_config;
  
  // connect to MySQL
  $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
  
  // check if table exists
  $sql = "SHOW TABLES LIKE 'task'";
  $errcode = "50011";
  $result = $db->query ($sql, $errcode, $mgmt_config['today'], 'show');
  $tableexists = $db->getNumRows ('show') > 0;
  
  if (!$tableexists)
  {
    // create task table
    $sql = "CREATE TABLE `task` (
  `task_id` int(11) NOT NULL auto_increment,
  `project_id` int(11) DEFAULT NULL,
  `object_id` int(11),
  `task` varchar(200) NOT NULL DEFAULT 'undefined',
  `from_user` varchar(200) NOT NULL default '',
  `to_user` varchar(200) NOT NULL default '',
  `startdate` date NOT NULL,
  `finishdate` date DEFAULT NULL,
  `category` varchar(20) NOT NULL default 'user',
  `description` varchar(3600),
  `priority` varchar(10) NOT NULL default 'low',
  `status` tinyint(3) NOT NULL,
  `duration` time DEFAULT NULL,
  PRIMARY KEY  (`task_id`),
  KEY `task` (`to_user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

    $db->query ($sql, $errcode, $mgmt_config['today'], 'create');
    
    if (is_dir ($mgmt_config['abs_path_data']."task/") && $handle = opendir ($mgmt_config['abs_path_data']."task/"))
    {
      while (false !== ($entry = readdir($handle)))
      {
        if (strpos ($entry, ".xml.php") > 0)
        {
          //load task file and get all task entries
          $task_data = loadfile ($mgmt_config['abs_path_data']."task/", $entry);
        
          // get all tasks
          if ($task_data != "")
          {
            $to_user = substr ($entry, 0, strpos ($entry, ".xml.php"));
          
            $task_array = getcontent ($task_data, "<task>");
            
            if (is_array ($task_array))
            {
              foreach ($task_array as $task_node)
              {
                $task_id = getcontent ($task_node, "<task_id>");
                $task_cat = getcontent ($task_node, "<task_cat>");
                $task_date = getcontent ($task_node, "<task_date>");
                $task_site = getcontent ($task_node, "<publication>");
                $task_object = getcontent ($task_node, "<object>");
                $task_object_id = getcontent ($task_node, "<object_id>");
                $task_priority = getcontent ($task_node, "<priority>");
                $task_descr = getcontent ($task_node, "<description>");
  
                $result = rdbms_createtask ($task_object_id[0], "", $to_user, $task_date[0], "", $task_cat[0], getobject (str_replace ("/.folder", "", $task_object[0])), $task_descr[0], $task_priority[0]);
                
                if ($result)
                {
                  $errcode = "00101";
                  $error[] = $mgmt_config['today']."|hypercms_update.inc.php|info|$errcode|task ".$task_id[0]." of user ".$to_user." has been updated";
                }
                else
                {
                  $errcode = "50101";
                  $error[] = $mgmt_config['today']."|hypercms_update.inc.php|info|$errcode|task ".$task_id[0]." of user ".$to_user." has not been updated";
                }
              }
            }
            
            // rename task file
            rename ($mgmt_config['abs_path_data']."task/".$entry, $mgmt_config['abs_path_data']."task/".$to_user.".xml.bak");
          }
        }
      }
      
      // save log              
      savelog (@$error);
      closedir ($handle);
    }
  }
  else return true;
}
?>