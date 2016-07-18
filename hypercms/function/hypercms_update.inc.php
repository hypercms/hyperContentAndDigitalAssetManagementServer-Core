<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// ======================================== UPDATE FUNCTIONS ============================================

// ------------------------------------------ update_usergroups_v564 ----------------------------------------------
// function: update_usergroups_v564()
// input: publication name, user group data (XML)
// output: updated user group data (XML), false on error

// description:
// Update to version 5.6.4 (group names will be replaced by object-IDs)

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
// function: update_tasks_v584()
// input: %
// output: updated tasks data (from XML to RDBMS), false on error

// description:
// Update of tasks to version 5.8.4

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
      
    // save log
    savelog ($db->getError ());    
    $db->close();
    
    // move data from XML to RDBMS
    if (function_exists ("createtask") && is_dir ($mgmt_config['abs_path_data']."task/") && $handle = opendir ($mgmt_config['abs_path_data']."task/"))
    {
      while (false !== ($entry = readdir($handle)))
      {
        if (strpos ($entry, ".xml.php") > 0)
        {
          // load task file and get all task entries
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
  
                $result = rdbms_createtask ($task_object_id[0], 0, "", $to_user, $task_date[0], "", $task_cat[0], getobject (str_replace ("/.folder", "", $task_object[0])), $task_descr[0], $task_priority[0]);
                
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

// ------------------------------------------ update_database_v586 ----------------------------------------------
// function: update_database_v586()
// input: %
// output: updated database, false on error

// description:
// Update of database to version 5.8.6

function update_database_v586 ()
{
  global $mgmt_config;
  
  // connect to MySQL
  $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
  
  // check for new column
  $sql = "SHOW COLUMNS FROM textnodes LIKE 'user'";
  
  $errcode = "50004";
  $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'show');
  
  if ($done)
  {
    $num_rows = $db->getNumRows ('show');
    
    // column does not exist
    if ($num_rows < 1)
    { 
      // alter table textnodes
      $sql = "ALTER TABLE textnodes ADD object_id INT(11) AFTER textcontent;";
      
      $errcode = "50006";
      $db->query ($sql, $errcode, $mgmt_config['today'], 'alter');
      
      $sql = "ALTER TABLE textnodes ADD user CHAR(60) AFTER object_id;";
      
      $errcode = "50006";
      $db->query ($sql, $errcode, $mgmt_config['today'], 'alter');
      
      $sql = "ALTER TABLE textnodes ADD INDEX `textnodes_object_id` (`object_id`)";
      
      $errcode = "500021";
      $db->query ($sql, $errcode, $mgmt_config['today'], 'alter');
      
      // alter table accesslink
      $sql = "ALTER TABLE accesslink MODIFY user VARCHAR(600);";
      
      $errcode = "50007";
      $db->query ($sql, $errcode, $mgmt_config['today'], 'alter');
      
      // alter table recipient
      $sql = "ALTER TABLE recipient CHANGE sender from_user CHAR(60);";
      
      $errcode = "50007";
      $db->query ($sql, $errcode, $mgmt_config['today'], 'alter');
      
      $sql = "ALTER TABLE recipient CHANGE user to_user VARCHAR(600);";
      
      $errcode = "50008";
      $db->query ($sql, $errcode, $mgmt_config['today'], 'alter');
      
      // alter table container
      $sql = "ALTER TABLE container MODIFY user CHAR(60);";
      
      $errcode = "50009";
      $db->query ($sql, $errcode, $mgmt_config['today'], 'alter');
      
      // alter table queue
      $sql = "ALTER TABLE queue MODIFY user CHAR(60);";
      
      $errcode = "50010";
      $db->query ($sql, $errcode, $mgmt_config['today'], 'alter');
      
      // alter table dailystat
      $sql = "ALTER TABLE dailystat MODIFY user CHAR(60);";
      
      $errcode = "50011";
      $db->query ($sql, $errcode, $mgmt_config['today'], 'alter');
      
      // alter table notify
      $sql = "ALTER TABLE notify MODIFY user CHAR(60);";
      
      $errcode = "50012";
      $db->query ($sql, $errcode, $mgmt_config['today'], 'alter');
      
      // add users to new field
      $sql = "UPDATE textnodes INNER JOIN container ON textnodes.id = container.id SET textnodes.user = container.user;";
      
      $errcode = "50013";
      $db->query ($sql, $errcode, $mgmt_config['today'], 'alter');
      
      // drop unused linkreference table
      $sql = "DROP TABLE IF EXISTS `linkreference`;";
      
      $errcode = "50014";
      $db->query ($sql, $errcode, $mgmt_config['today'], 'drop');
      
      // create report data directory
      if (!is_dir ($mgmt_config['abs_path_data']."report"))
      {
        $mkdir = @mkdir ($mgmt_config['abs_path_data']."report", $mgmt_config['fspermission']);
        
        if (!$mkdir)
        {
          $errcode = "00201";
          $error[] = $mgmt_config['today']."|hypercms_update.inc.php|error|$errcode|report directory could not be created";
        }
      }
    }
  }
  
  // save log
  savelog ($db->getError ());
  savelog (@$error);
  $db->close();
  
  return true;
}

// ------------------------------------------ update_database_v601 ----------------------------------------------
// function: update_database_v601()
// input: %
// output: updated database, false on error

// description: 
// Update of database to version 6.0.1

function update_database_v601 ()
{
  global $mgmt_config;
  
  // connect to MySQL
  $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
  
  // check for new column
  $sql = "SHOW COLUMNS FROM task LIKE 'planned'";
  
  $errcode = "50060";
  $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'show');
  
  if ($done)
  {
    $num_rows = $db->getNumRows ('show');
    
    // column does not exist
    if ($num_rows < 1)
    { 
      // alter table task
      $sql = "ALTER TABLE task CHANGE duration actual float(6,2);";
      
      $errcode = "50061";
      $db->query ($sql, $errcode, $mgmt_config['today'], 'alter');
      
      $sql = "ALTER TABLE task ADD planned float(6,2) AFTER status;";
      
      $errcode = "50062";
      $db->query ($sql, $errcode, $mgmt_config['today'], 'alter');
      
      // create new table
      $sql = "CREATE TABLE `project` (
  `project_id` int(11) NOT NULL auto_increment,
  `subproject_id` int(11) NOT NULL default '0',
  `object_id` int(11) NOT NULL default '0',
  `createdate` datetime NOT NULL, 
  `project` char(200) NOT NULL DEFAULT 'undefined',
  `description` varchar(3600),
  `user` char(60) NOT NULL default '', 
  PRIMARY KEY  (`project_id`),
  KEY `project` (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
  
      $errcode = "50063";
      $db->query ($sql, $errcode, $mgmt_config['today'], 'create');
    }
  }
  
  // save log
  savelog ($db->getError ());
  savelog (@$error);
  $db->close();
  
  return true;
}

// ------------------------------------------ update_database_v614 ----------------------------------------------
// function: update_database_v614()
// input: %
// output: updated database, false on error

// description: 
// Update of database to version 6.1.4

function update_database_v614 ()
{
  global $mgmt_config;
  
  // connect to MySQL
  $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
  
  // check if table exists
  $sql = "SHOW TABLES LIKE 'taxonomy'";
  $errcode = "50064";
  $result = $db->query ($sql, $errcode, $mgmt_config['today'], 'show');
  $tableexists = $db->getNumRows ('show') > 0;
  
  if (!$tableexists)
  {  
    // create new table
    $sql = "CREATE TABLE `taxonomy` (
  `id` int(11) NOT NULL,
  `text_id` char(120) NOT NULL default '',
  `taxonomy_id` int(11) NOT NULL default '0',
  `lang` char(6) NOT NULL default '',
  KEY `taxonomy` (`id`,`taxonomy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
    
    $errcode = "50065";
    $db->query ($sql, $errcode, $mgmt_config['today'], 'create');
  }
  
  // save log
  savelog ($db->getError ());
  savelog (@$error);
  $db->close();
  
  return true;
}

// ------------------------------------------ update_database_v6113 ----------------------------------------------
// function: update_database_v6113()
// input: %
// output: updated database, false on error

// description: 
// Update of database to version 6.1.13

function update_database_v6113 ()
{
  global $mgmt_config;
  
  // connect to MySQL
  $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
  
  // check if table exists
  $sql = "SHOW TABLES LIKE 'keywords'";
  $errcode = "50066";
  $result = $db->query ($sql, $errcode, $mgmt_config['today'], 'show');
  $tableexists = $db->getNumRows ('show') > 0;
  
  if (!$tableexists)
  {
    // alter table textnodes
    $sql = "ALTER TABLE textnodes ADD type CHAR(6) AFTER object_id;";
    
    $errcode = "50067";
    $db->query ($sql, $errcode, $mgmt_config['today']);
    
    $sql = "ALTER TABLE textnodes ADD INDEX `textnodes_id_type` (`id`,`type`);";
    
    $errcode = "50068";
    $db->query ($sql, $errcode, $mgmt_config['today']);
   
    // create new table
    $sql = "CREATE TABLE `keywords` (
  `keyword_id` int(11) NOT NULL auto_increment,
  `keyword` char (100) NOT NULL default '',
  PRIMARY KEY (`keyword_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
    
    $errcode = "50069";
    $db->query ($sql, $errcode, $mgmt_config['today']);
    
    // create new table
    $sql = "CREATE TABLE `keywords_container` (
  `id` int(11) NOT NULL default '0',
  `keyword_id` int(11) NOT NULL default '0',
  PRIMARY KEY (`id`,`keyword_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
    
    $errcode = "50070";
    $db->query ($sql, $errcode, $mgmt_config['today']);
    
    // update media textnodes
    $sql = "UPDATE textnodes SET type=\"media\" WHERE text_id LIKE \"media:%\"";
    
    $errcode = "50072";
    $db->query ($sql, $errcode, $mgmt_config['today']);
    
    // update link textnodes
    $sql = "UPDATE textnodes SET type=\"link\" WHERE text_id LIKE \"link:%\"";
    
    $errcode = "50073";
    $db->query ($sql, $errcode, $mgmt_config['today']);
    
    // update link textnodes
    $sql = "UPDATE textnodes SET type=\"head\" WHERE text_id LIKE \"head:%\"";
    
    $errcode = "50074";
    $db->query ($sql, $errcode, $mgmt_config['today']);
    
    // update link textnodes
    $sql = "UPDATE textnodes SET type=\"file\" WHERE text_id LIKE \"%.%\"";
    
    $errcode = "50075";
    $db->query ($sql, $errcode, $mgmt_config['today']);
    
    // insert type in textnodes
    if (function_exists ("rdbms_setpublicationkeywords"))
    {
      ini_set('max_execution_time', 36000);
      $inherit_db = inherit_db_read ();

      if ($inherit_db != false && sizeof ($inherit_db) > 0)
      {
        foreach ($inherit_db as $inherit_db_record)
        {
          if ($inherit_db_record['parent'] != "")
          {
            $site = $inherit_db_record['parent'];
            
            // update standard media mapping
            if (valid_publicationname ($site) && @is_file ($mgmt_config['abs_path_data']."config/".$site.".media.map.php"))
            {
              $mapdata = loadfile ($mgmt_config['abs_path_data']."config/", $site.".media.map.php");
              
              $mapdata = str_replace (array("\"Title\"", "\"Keywords\"", "\"Description\"", "\"Creator\"", "\"Copyright\"", "\"Quality\""), array("\"textu:Title\"", "\"textk:Keywords\"", "\"textu:Description\"", "\"textu:Creator\"", "\"textu:Copyright\"", "\"textl:Quality\""), $mapdata);
              $mapdata = str_replace (array("=> Title", "=> Keywords", "=> Description", "=> Creator", "=> Copyright", "=> Quality"), array("=> textu:Title", "=> textk:Keywords", "=> textu:Description", "=> textu:Creator", "=> textu:Copyright", "=> textl:Quality"), $mapdata);
              
              if ($mapdata != "") savefile ($mgmt_config['abs_path_data']."config/", $site.".media.map.php", $mapdata);
            } 

            // collect template information
            $templates = getlocaltemplates ($site);
        
            if (is_array ($templates) && sizeof ($templates) > 0)
            {
              foreach ($templates as $template)
              {
                $template_data = loadtemplate ($site, $template);
                
                if (!empty ($template_data['content']))
                {
                  $hypertag_array = gethypertag ($template_data['content'], "text", 0);
                  
                  if ($hypertag_array != false)
                  {
                    foreach ($hypertag_array as $hypertag)
                    {
                      // get tag id
                      $text_id = getattribute ($hypertag, "id");
                      
                      // get tag name
                      $hypertagname = gethypertagname ($hypertag);
                      
                      // remove article prefix
                      if (substr ($hypertagname, 0, 3) == "art") $hypertagname = substr ($hypertagname, 3);
        
                      // update textnodes table
                      if ($text_id != "" && $hypertagname != "")
                      {
                        $sql = "UPDATE textnodes INNER JOIN object ON textnodes.id=object.id SET textnodes.type=\"".$hypertagname."\" WHERE textnodes.text_id=\"".$text_id."\" AND object.template=\"".$template."\"";
                        
                        $errcode = "50071";
                        $db->query ($sql, $errcode, $mgmt_config['today']);
                      }
                    }
                  }
                }
              }
            }
          
            // insert keywords
            rdbms_setpublicationkeywords ($site);
          }
        }
      }
    }
  }
  
  // save log
  savelog ($db->getError ());
  savelog (@$error);
  $db->close();

  return true;
}

// ------------------------------------------ update_database_v6115 ----------------------------------------------
// function: update_database_v6115()
// input: %
// output: updated database, false on error

// description: 
// Update of database to version 6.1.15. Clean and HTML decode all content.

function update_database_v6115 ()
{
  global $mgmt_config;

  // connect to MySQL
  $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
  
  // select all content
  $sql = 'SELECT id, text_id, textcontent FROM textnodes WHERE textcontent!=""';
  $errcode = "50071";
  $result = $db->query ($sql, $errcode, $mgmt_config['today'], 'select');
  
  if ($result)
  {
    while ($row = $db->getResultRow ('select'))
    {
      $cleaned = cleancontent ($row['textcontent'], "UTF-8");
      
      $cleaned = $db->escape_string ($cleaned);
      
      // only update if content has changed and is not empty
      if (trim ($cleaned) != "" && $row['textcontent'] != $cleaned)
      {
        $sql = 'UPDATE textnodes SET textcontent="'.$cleaned.'" WHERE id="'.$row['id'].'" AND text_id="'.$row['text_id'].'"';
        $errcode = "50072";
        $result = $db->query ($sql, $errcode, $mgmt_config['today'], 'update');
        
        // information
        $errcode = "00101";
        $error[] = $mgmt_config['today']."|hypercms_update.inc.php|information|$errcode|cleaned content of container ".$row['id']." with text ID '".$row['text_id']."': ".$cleaned;   
      }
    }
  }
  
  // save log
  savelog ($db->getError ());
  savelog (@$error);
  $db->close();

  return true;
}
?>