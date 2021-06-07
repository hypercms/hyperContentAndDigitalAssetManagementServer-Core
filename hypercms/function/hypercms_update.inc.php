<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */
 
// ======================================== UPDATE FUNCTIONS ============================================

// ------------------------------------------ update_users_546 ----------------------------------------------
// function: update_users_546()
// input: %
// output: true / false

// description:
// Update to version 5.4.6 , 5.5.11 and 5.5.15 (used to be part of function creatuser in Main API)

function update_users_546 ()
{
  global $mgmt_config;

  $logdata = loadlog ("update", "string");

  if (empty ($logdata) || strpos ($logdata, "|5.4.6|") < 1)
  {
    // update users
    $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");

    if (!empty ($userdata))
    {
      // Updates in XML nodes:
      // before version 5.4.6 new hashcode nodes needs to be inserted
      if (substr_count ($userdata, "<hashcode>") == 0)
      {
        $userdata = str_replace ("</password>", "</password>\n<hashcode></hashcode>", $userdata);
        $updated = true;
      }

      // before version 5.5.11 new admin nodes needs to be inserted
      if (substr_count ($userdata, "<admin>") == 0)
      {
        $userdata = str_replace ("</hashcode>", "</hashcode>\n<admin>0</admin>", $userdata);
        $updated = true;
      }

      // before version 5.5.15 new theme nodes needs to be inserted
      if (substr_count ($userdata, "<theme>") == 0)
      {
        $userdata = str_replace ("</language>", "</language>\n<theme></theme>", $userdata);
        $updated = true;
      }

      if (!empty ($userdata) && !empty ($updated))
      {
        $savefile = savefile ($mgmt_config['abs_path_data']."user/", "user.xml.php", $userdata);

        // update log
        if ($savefile == true) savelog (array($mgmt_config['today']."|hypercms_update.inc.php|information|5.4.6|updated to version 5.4.6"), "update");
        // sys log
        else savelog (array($mgmt_config['today']."|hypercms_update.inc.php|error|10108|update to version 5.4.6 failed for 'user.xml.php'"));

        return $savefile;
      }
      else return false;
    }
    else return false;
  }
  else return false;
}
// ------------------------------------------ update_usergroups_v564 ----------------------------------------------
// function: update_usergroups_v564()
// input: publication name [string], user group data (XML) [string]
// output: true / false

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

    if ($datanew != "") return savefile ($mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php", $datanew);
    else return false;
  }
  else return false;
}

// ------------------------------------------ update_tasks ----------------------------------------------
// function: update_tasks_v584()
// input: %
// output: true / false

// description:
// Update of tasks to version 5.8.4 (from XML to RDBMS)

function update_tasks_v584 ()
{
  global $mgmt_config;

  $error = array();

  // connect to MySQL
  $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

  // check if table exists
  $sql = "SHOW TABLES LIKE 'task'"; 
  $errcode = "50011";
  $result = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'show');
  $tableexists = $db->rdbms_getnumrows ('show') > 0;

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

    $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'create');

    // save log
    savelog ($db->rdbms_geterror ());
    $db->rdbms_close();

    // move data from XML to RDBMS
    if (function_exists ("createtask") && is_dir ($mgmt_config['abs_path_data']."task/") && $scandir = scandir ($mgmt_config['abs_path_data']."task/"))
    {
      foreach ($scandir as $entry)
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
                  $error[] = $mgmt_config['today']."|hypercms_update.inc.php|info|".$errcode."|task '".$task_id[0]."' of user '".$to_user."' has been updated";
                }
                else
                {
                  $errcode = "50101";
                  $error[] = $mgmt_config['today']."|hypercms_update.inc.php|info|".$errcode."|task '".$task_id[0]."' of user '".$to_user."' has not been updated";
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
      return true;
    }
  }
  else return true;
}

// ------------------------------------------ update_database_v586 ----------------------------------------------
// function: update_database_v586()
// input: %
// output: true / false

// description:
// Update of database to version 5.8.6

function update_database_v586 ()
{
  global $mgmt_config;

  $error = array();

  // connect to MySQL
  $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

  // check for new column
  $sql = "SHOW COLUMNS FROM textnodes LIKE 'user'";

  $errcode = "50004";
  $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'show');

  if ($done)
  {
    $num_rows = $db->rdbms_getnumrows ('show');

    // column does not exist
    if ($num_rows < 1)
    { 
      // alter table textnodes
      $sql = "ALTER TABLE textnodes ADD object_id INT(11) AFTER textcontent;";

      $errcode = "50006";
      $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'alter');

      $sql = "ALTER TABLE textnodes ADD user CHAR(60) AFTER object_id;";

      $errcode = "50006";
      $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'alter');

      $sql = "ALTER TABLE textnodes ADD INDEX `textnodes_object_id` (`object_id`)";

      $errcode = "500021";
      $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'alter');

      // alter table accesslink
      $sql = "ALTER TABLE accesslink MODIFY user VARCHAR(600);";

      $errcode = "50007";
      $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'alter');

      // alter table recipient
      $sql = "ALTER TABLE recipient CHANGE sender from_user CHAR(60);";

      $errcode = "50007";
      $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'alter');

      $sql = "ALTER TABLE recipient CHANGE user to_user VARCHAR(600);";

      $errcode = "50008";
      $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'alter');

      // alter table container
      $sql = "ALTER TABLE container MODIFY user CHAR(60);";

      $errcode = "50009";
      $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'alter');

      // alter table queue
      $sql = "ALTER TABLE queue MODIFY user CHAR(60);";

      $errcode = "50010";
      $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'alter');

      // alter table dailystat
      $sql = "ALTER TABLE dailystat MODIFY user CHAR(60);";

      $errcode = "50011";
      $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'alter');

      // alter table notify
      $sql = "ALTER TABLE notify MODIFY user CHAR(60);";

      $errcode = "50012";
      $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'alter');

      // add users to new field
      $sql = "UPDATE textnodes INNER JOIN container ON textnodes.id = container.id SET textnodes.user = container.user;";

      $errcode = "50013";
      $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'alter');

      // drop unused linkreference table
      $sql = "DROP TABLE IF EXISTS `linkreference`;";

      $errcode = "50014";
      $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'drop');

      // create report data directory
      if (!is_dir ($mgmt_config['abs_path_data']."report"))
      {
        $mkdir = @mkdir ($mgmt_config['abs_path_data']."report", $mgmt_config['fspermission']);

        if (!$mkdir)
        {
          $errcode = "10201";
          $error[] = $mgmt_config['today']."|hypercms_update.inc.php|error|".$errcode."|report directory could not be created";
        }
      }
    }
  }

  // save log
  savelog ($db->rdbms_geterror ());
  savelog (@$error);
  $db->rdbms_close();

  return true;
}

// ------------------------------------------ update_database_v601 ----------------------------------------------
// function: update_database_v601()
// input: %
// output: true / false

// description: 
// Update of database to version 6.0.1

function update_database_v601 ()
{
  global $mgmt_config;

  $error = array();

  // connect to MySQL
  $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

  // check for new column
  $sql = "SHOW COLUMNS FROM task LIKE 'planned'";

  $errcode = "50060";
  $done = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'show');

  if ($done)
  {
    $num_rows = $db->rdbms_getnumrows ('show');

    // column does not exist
    if ($num_rows < 1)
    { 
      // alter table task
      $sql = "ALTER TABLE task CHANGE duration actual float(6,2);";

      $errcode = "50061";
      $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'alter');

      $sql = "ALTER TABLE task ADD planned float(6,2) AFTER status;";

      $errcode = "50062";
      $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'alter');

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
      $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'create');
    }
  }

  // save log
  savelog ($db->rdbms_geterror ());
  savelog (@$error);
  $db->rdbms_close();

  return true;
}

// ------------------------------------------ update_database_v614 ----------------------------------------------
// function: update_database_v614()
// input: %
// output: true / false

// description: 
// Update of database to version 6.1.4

function update_database_v614 ()
{
  global $mgmt_config;

  $error = array();

  // connect to MySQL
  $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

  // check if table exists
  $sql = "SHOW TABLES LIKE 'taxonomy'";
  $errcode = "50064";
  $result = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'show');
  $tableexists = $db->rdbms_getnumrows ('show') > 0;

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
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'create');
  }

  // save log
  savelog ($db->rdbms_geterror ());
  savelog (@$error);
  $db->rdbms_close();

  return true;
}

// ------------------------------------------ update_database_v6113 ----------------------------------------------
// function: update_database_v6113()
// input: %
// output: true / false

// description: 
// Update of database to version 6.1.13

function update_database_v6113 ()
{
  global $mgmt_config;

  $error = array();

  // connect to MySQL
  $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

  // check if table exists
  $sql = "SHOW TABLES LIKE 'keywords'";
  $errcode = "50066";
  $result = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'show');
  $tableexists = $db->rdbms_getnumrows ('show') > 0;

  if (!$tableexists)
  {
    // alter table textnodes
    $sql = "ALTER TABLE textnodes ADD type CHAR(6) AFTER object_id;";

    $errcode = "50067";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    $sql = "ALTER TABLE textnodes ADD INDEX `textnodes_id_type` (`id`,`type`);";

    $errcode = "50068";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);
 
    // create new table
    $sql = "CREATE TABLE `keywords` (
  `keyword_id` int(11) NOT NULL auto_increment,
  `keyword` char (100) NOT NULL default '',
  PRIMARY KEY (`keyword_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

    $errcode = "50069";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // create new table
    $sql = "CREATE TABLE `keywords_container` (
  `id` int(11) NOT NULL default '0',
  `keyword_id` int(11) NOT NULL default '0',
  PRIMARY KEY (`id`,`keyword_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

    $errcode = "50070";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // update media textnodes
    $sql = "UPDATE textnodes SET type=\"media\" WHERE text_id LIKE \"media:%\"";

    $errcode = "50072";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // update link textnodes
    $sql = "UPDATE textnodes SET type=\"link\" WHERE text_id LIKE \"link:%\"";

    $errcode = "50073";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // update link textnodes
    $sql = "UPDATE textnodes SET type=\"head\" WHERE text_id LIKE \"head:%\"";

    $errcode = "50074";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // update link textnodes
    $sql = "UPDATE textnodes SET type=\"file\" WHERE text_id LIKE \"%.%\"";

    $errcode = "50075";
    $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

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
                        $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);
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
  savelog ($db->rdbms_geterror ());
  savelog (@$error);
  $db->rdbms_close();

  return true;
}

// ------------------------------------------ update_database_v6115 ----------------------------------------------
// function: update_database_v6115()
// input: %
// output: true / false

// description: 
// Update of database to version 6.1.15. Clean and HTML decode all content.

function update_database_v6115 ()
{
  global $mgmt_config;

  $error = array();

  // connect to MySQL
  $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

  // select all content
  $sql = 'SELECT id, text_id, textcontent FROM textnodes WHERE textcontent!=""';
  $errcode = "50071";
  $result = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'select');

  if ($result)
  {
    while ($row = $db->rdbms_getresultrow('select'))
    {
      $cleaned = cleancontent ($row['textcontent'], "UTF-8");

      $cleaned = $db->rdbms_escape_string ($cleaned);

      // only update if content has changed and is not empty
      if (trim ($cleaned) != "" && $row['textcontent'] != $cleaned)
      {
        $sql = 'UPDATE textnodes SET textcontent="'.$cleaned.'" WHERE id="'.$row['id'].'" AND text_id="'.$row['text_id'].'"';
        $errcode = "50072";
        $result = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'update');

        // information
        $errcode = "00101";
        $error[] = $mgmt_config['today']."|hypercms_update.inc.php|information|".$errcode."|cleaned content of container '".$row['id']."' with text ID '".$row['text_id']."': ".$cleaned; 
      }
    }
  }

  // save log
  savelog ($db->rdbms_geterror ());
  savelog (@$error);
  $db->rdbms_close();

  return true;
}

// ------------------------------------------ update_container_v6118 ----------------------------------------------
// function: update_container_v6118()
// input: %
// output: true / false

// description: 
// Update of containers to version 6.1.18 (add date created to containers).

function update_container_v6118 ()
{
  global $mgmt_config;

  $error = array();

  $logdata = loadlog ("update", "string");

  if (!empty ($mgmt_config['abs_path_data']) && (empty ($logdata) || strpos ($logdata, "|6.1.18|") < 1))
  { 
    // connect to MySQL
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // content repository
    $loc = $mgmt_config['abs_path_data']."content/";

    // 1 st level (content container blocks)
    $blockdir = scandir ($loc);

    $i = 0;

    // browse all containers in the content repository
    foreach ($blockdir as $block)
    {
      if (is_dir ($loc.$block) && $block != "." && $block != ".." && is_numeric ($block))
      {
        // 2nd level (specific content container folder)
        $contdir = scandir ($loc.$block);

        foreach ($contdir as $container_id)
        {
          if (!empty ($updated)) break;

          if ($container_id > 0 && is_dir ($loc.$block."/".$container_id))
          {
            // select date created
            $sql = 'SELECT createdate FROM container WHERE id='.intval($container_id);
            $errcode = "50072";
            $result = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'select');

            if ($result && $row = $db->rdbms_getresultrow('select'))
            {
              $date_created = $row['createdate'];
            }
            else $date_created = "";

            // if date created is available
            if ($date_created != "")
            { 
              // 3rd level (content container XML files)
              $filedir = scandir ($loc.$block."/".$container_id);

              foreach ($filedir as $file)
              {
                if (!empty ($updated)) break;

                // update all containers
                if (is_file ($loc.$block."/".$container_id."/".$file) && strpos ($file, ".xml") > 0 && strpos ($file, ".bak") == 0)
                { 
                  $dirname = substr ($file, 0, strpos ($file, "."));

                  // load container
                  $data = loadfile ($loc.$block."/".$container_id."/", $file);

                  if ($data != false && substr_count ($data, "</contentuser>") > 0)
                  {
                    // container has not been updated
                    if (substr_count ($data, "</contentcreated>") == 0)
                    {
                      $data = str_replace ("</contentuser>", "</contentuser>\n<contentcreated>".$date_created."</contentcreated>", $data);

                      if ($data != "") $test = savefile ($loc.$block."/".$container_id."/", $file, $data);
                      else $test = false;

                      if (!$test)
                      {
                        // error
                        $errcode = "10105";
                        $error[] = $mgmt_config['today']."|hypercms_update.inc.php|error|".$errcode."|container '".$file."' could not be updated";
                      }

                      $i++;
                    }
                    // container has been updated
                    else
                    {
                      $updated = true;
                      break;
                    }
                  }
                }
              }
            }
          }
        }
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());
    savelog (array($mgmt_config['today']."|hypercms_update.inc.php|information|6.1.18|updated to version 6.1.18"), "update");
    savelog (@$error);
    $db->rdbms_close();

    return true;
  }
  else return false;
}

// ------------------------------------------ update_database_v6139 ----------------------------------------------
// function: update_database_v6139()
// input: %
// output: true / false

// description: 
// Update of index on table recipients to version 6.1.39 (add new indexes).

function update_database_v6139 ()
{
  global $mgmt_config;

  $error = array();

  $logdata = loadlog ("update", "string");

  if (empty ($logdata) || strpos ($logdata, "|6.1.39|") < 1)
  { 
    // connect to MySQL
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // create new index
    $sql = 'CREATE INDEX date ON recipient(object_id, date);';
    $errcode = "50081";
    $result = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // create new index
    $sql = 'CREATE INDEX from_user ON recipient(object_id, from_user);';
    $errcode = "50082";
    $result = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // create new index
    $sql = 'CREATE INDEX to_user ON recipient(object_id, to_user(200));';
    $errcode = "50083";
    $result = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->rdbms_geterror ());
    savelog (array($mgmt_config['today']."|hypercms_update.inc.php|information|6.1.39|updated to version 6.1.39"), "update");
    savelog (@$error);
    $db->rdbms_close();

    return true;
  }
  else return false;
}

// ------------------------------------------ update_database_v625 ----------------------------------------------
// function: update_database_v625()
// input: %
// output: true / false

// description: 
// Adds attribute 'deleteuser' and 'deletedate' to table objects for support of version 6.2.5.

function update_database_v625 ()
{
  global $mgmt_config;

  $error = array();

  $logdata = loadlog ("update", "string");

  if (empty ($logdata) || strpos ($logdata, "|6.2.5|") < 1)
  { 
    // connect to MySQL
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // alter table
    $sql = "ALTER TABLE object ADD deleteuser CHAR(60) DEFAULT '' AFTER template;";
    $errcode = "50091";
    $result = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    $sql = "ALTER TABLE object ADD deletedate DATE AFTER deleteuser;";
    $errcode = "50092";
    $result = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->rdbms_geterror ());
    savelog (array($mgmt_config['today']."|hypercms_update.inc.php|information|6.2.5|updated to version 6.2.5"), "update");
    savelog (@$error);
    $db->rdbms_close();

    return true;
  }
  else return false;
}

// ------------------------------------------ update_database_v705 ----------------------------------------------
// function: update_database_v705()
// input: path to component directory [string], alter table [boolean]
// output: true / false

// description: 
// Adds attribute 'media' to table objects for support of version 7.0.5

function update_database_v705 ($dir, $db_alter)
{
  global $mgmt_config;

  $error = array();

  $logdata = loadlog ("update", "string");

  if (empty ($logdata) || strpos ($logdata, "|7.0.5|") < 1)
  { 
    if (!empty ($db_alter))
    {
      // connect to MySQL
      $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

      // alter table
      $sql = "ALTER TABLE object ADD media CHAR(255) DEFAULT '' AFTER template;";
      $errcode = "50075";
      $result = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

      // save log
      savelog ($db->rdbms_geterror ());
      savelog (@$error);
      $db->rdbms_close();

      $db_alter = false;
    }

    $files = scandir ($dir);

    if (is_array ($files))
    {
      foreach ($files as $file)
      {
        $path = $dir.$file;

        if (is_file ($path) && $file != ".folder")
        {
          $data = loadfile_header ($dir, $file);

          if (!empty ($data))
          {
            $media = getfilename ($data, "media");
            $content = getfilename ($data, "content");

            if (!empty ($media) && !empty ($content))
            {
              if (strpos ($content, ".xml") > 0) $id = intval (substr ($content, 0, strpos ($content, ".xml")));
              rdbms_setmedianame ($id, $media);
            }
          }
        }
        elseif (is_dir ($path) && $file != "." && $file != "..")
        {
          update_database_v705 ($path."/", $db_alter);
        }
      }
    }

    return true;
  }
  else return false;
}

// ------------------------------------------ update_users_706 ----------------------------------------------
// function: update_users_706()
// input: %
// output: true / false

// description:
// Update to version 7.0.6 (add phone node to users)

function update_users_706 ()
{
  global $mgmt_config;

  $error = array();

  $logdata = loadlog ("update", "string");

  if (empty ($logdata) || strpos ($logdata, "|7.0.6|") < 1)
  {
    $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");

    if (!empty ($userdata) && strpos ($userdata, "</phone>") < 1)
    {
      $datanew = str_replace ("</email>", "</email>\n<phone></phone>", $userdata);

      if (!empty ($datanew))
      {
        $savefile = savefile ($mgmt_config['abs_path_data']."user/", "user.xml.php", $datanew);

        // update log
        if ($savefile == true) savelog (array($mgmt_config['today']."|hypercms_update.inc.php|information|7.0.6|updated to version 7.0.6"), "update");
        // sys log
        else savelog (array($mgmt_config['today']."|hypercms_update.inc.php|error|10100|update to version 7.0.6 failed"));

        return $savefile;
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// ------------------------------------------ update_database_v708 ----------------------------------------------
// function: update_database_v708()
// input: %
// output: true / false

// description: 
// Adds primary keys to table taxonomy and textnodes for support of version 7.0.8

function update_database_v708 ()
{
  global $mgmt_config;

  $error = array();

  $logdata = loadlog ("update", "string");

  if (empty ($logdata) || strpos ($logdata, "|7.0.8|") < 1)
  { 
    // connect to MySQL
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // alter table
    $sql = "ALTER TABLE textnodes ADD COLUMN textnodes_id INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (textnodes_id);";
    $errcode = "50085";
    $result = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // alter table
    $sql = "ALTER TABLE taxonomy ADD COLUMN taxonomykey_id INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (taxonomykey_id);";
    $errcode = "50708";
    $result = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->rdbms_geterror ());
    savelog (@$error);
    $db->rdbms_close();

    // update log
    savelog (array($mgmt_config['today']."|hypercms_update.inc.php|information|7.0.8|updated to version 7.0.8"), "update");

    return true;
  }
  else return false;
}

// ------------------------------------------ update_users_709 ----------------------------------------------
// function: update_users_709()
// input: %
// output: true / false

// description:
// Update to version 7.0.9 (add timezone to users and add new registration settings)

function update_users_709 ()
{
  global $mgmt_config;

  $error = array();

  $logdata = loadlog ("update", "string");

  if (empty ($logdata) || strpos ($logdata, "|7.0.9|") < 1)
  {
    // update management config
    $dir = $mgmt_config['abs_path_data']."config/";
    $files = scandir ($dir);

    if (is_array ($files))
    {
      foreach ($files as $file)
      {
        // only publication config files and not the plugin config file
        if (strpos ($file, ".conf.php") > 0 && $file != "plugin.conf.php")
        {
          $site_name = substr ($file, 0, strpos ($file, ".conf.php"));

          // load management config
          $site_mgmt_config = loadfile ($dir, $file);

          // update/add settings for version 9.0.7
          if ($site_mgmt_config != "" && strpos ($site_mgmt_config, "['registration']") < 1)
          {
            // new settings
            $code_add = "
// Enable (true) or disable (false) registration of new users
\$mgmt_config['".$site_name."']['registration'] = false;

// Set user group assignment for newly registered users
\$mgmt_config['".$site_name."']['registration_group'] = \"\";

// Set user notification if a new user has been registered (comma-speratated list of users)
\$mgmt_config['".$site_name."']['registration_notify'] = \"\";
";

            list ($code, $rest) = explode ("?>", $site_mgmt_config);

            // add new settings
            if ($code != "") $site_mgmt_config = $code.$code_add."?>";

            // save management config
            if ($site_mgmt_config != "") $savefile = savefile ($dir, $site_name.".conf.php", trim ($site_mgmt_config));
            else $savefile = false;

            // sys log
            if ($savefile != true) savelog (array($mgmt_config['today']."|hypercms_update.inc.php|error|10101|update to version 7.0.9 failed for management configuration '".$file."'"));
          }
        }
      }
    }

    // update users
    $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");

    if (!empty ($userdata) && strpos ($userdata, "</timezone>") < 1)
    {
      $datanew = str_replace ("</language>", "</language>\n<timezone></timezone>", $userdata);

      if (!empty ($datanew))
      {
        $savefile = savefile ($mgmt_config['abs_path_data']."user/", "user.xml.php", $datanew);

        // update log
        if ($savefile == true) savelog (array($mgmt_config['today']."|hypercms_update.inc.php|information|7.0.9|updated to version 7.0.9"), "update");
        // sys log
        else savelog (array($mgmt_config['today']."|hypercms_update.inc.php|error|10102|update to version 7.0.9 failed for 'user.xml.php'"));

        return $savefile;
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// ------------------------------------------ update_config_7010 ----------------------------------------------
// function: update_config_7010()
// input: %
// output: true / false

// description:
// Update to version 7.0.10 (add new notification settings)

function update_config_7010 ()
{
  global $mgmt_config;

  $error = array();

  $logdata = loadlog ("update", "string");

  if (empty ($logdata) || strpos ($logdata, "|7.0.10|") < 1)
  {
    // update management config
    $dir = $mgmt_config['abs_path_data']."config/";
    $files = scandir ($dir);

    if (is_array ($files))
    {
      foreach ($files as $file)
      {
        // only publication config files and not the plugin config file
        if (strpos ($file, ".conf.php") > 0 && $file != "plugin.conf.php")
        {
          $site_name = substr ($file, 0, strpos ($file, ".conf.php"));

          // load management config
          $site_mgmt_config = loadfile ($dir, $file);

          // update/add settings for version 9.0.7
          if ($site_mgmt_config != "" && strpos ($site_mgmt_config, "['eventlog_notify']") < 1)
          {
            // new settings
            $code_add = "
// Set user notification if an error or warning has been logged
\$mgmt_config['".$site_name."']['eventlog_notify'] = \"\";
";

            list ($code, $rest) = explode ("?>", $site_mgmt_config);

            // add new settings
            if ($code != "") $site_mgmt_config = $code.$code_add."?>";

            // save management config
            if ($site_mgmt_config != "") $savefile = savefile ($dir, $site_name.".conf.php", trim ($site_mgmt_config));
            else $savefile = false;

            // sys log
            if ($savefile != true) savelog (array($mgmt_config['today']."|hypercms_update.inc.php|error|10106|update to version 7.0.10 failed for management configuration '".$file."'"));
          }
        }
      }
    }

    // update log
    savelog (array($mgmt_config['today']."|hypercms_update.inc.php|information|7.0.10|updated to version 7.0.10"), "update");
  }
  else return false;
}

// ------------------------------------------ update_database_v800 ----------------------------------------------
// function: update_database_v800()
// input: %
// output: true / false

// description: 
// Modifies object_id of table accesslink for support of version 8.0.0

function update_database_v800 ()
{
  global $mgmt_config;

  $error = array();

  $logdata = loadlog ("update", "string");

  if (empty ($logdata) || strpos ($logdata, "|8.0.0|") < 1)
  { 
    // connect to MySQL
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // alter table
    $sql = "ALTER TABLE accesslink MODIFY object_id varchar(4000);";
    $errcode = "50800";
    $result = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->rdbms_geterror ());
    savelog (@$error);
    $db->rdbms_close();

    // update log
    savelog (array($mgmt_config['today']."|hypercms_update.inc.php|information|8.0.0|updated to version 8.0.0"), "update");

    return true;
  }
  else return false;
}

// ------------------------------------------ update_users_804 ----------------------------------------------
// function: update_users_804()
// input: %
// output: true / false

// description:
// Update users to version 8.0.4 (add valid begin and end date) and update/rename publication specific logs files to version 8.0.4

function update_users_804 ()
{
  global $mgmt_config;

  $error = array();

  $logdata = loadlog ("update", "string");

  if (empty ($logdata) || strpos ($logdata, "|8.0.4|") < 1)
  {
    // rename custom.log to publication.log (since version 8.0.4)
    $dir = $mgmt_config['abs_path_data']."log/";
    $files = scandir ($dir);

    if (is_array ($files))
    {
      foreach ($files as $file)
      {
        // only publication log files
        if (strpos ($file, ".custom.log") > 0)
        {
          $site = str_replace (".custom.log", "", $file);

          if (is_file ($dir.$site.".custom.log"))
          {
            $rename = rename ($dir.$site.".custom.log", $dir.$site.".publication.log");

            if (!$rename)
            {
              $errcode = "10801";
              $error[] = $mgmt_config['today']."|hypercms_update.inc.php|error|".$errcode."|publication log '".$site.".custom.log' could not be renamed to '".$site.".publication.log";
            }
          }
        }
      }
    }

    // create portal directory in repository
    if (!is_dir ($mgmt_config['abs_path_rep']."portal"))
    {
      $mkdir = @mkdir ($mgmt_config['abs_path_rep']."portal", $mgmt_config['fspermission']);

      if (!$mkdir)
      {
        $errcode = "10802";
        $error[] = $mgmt_config['today']."|hypercms_update.inc.php|error|".$errcode."|portal directory could not be created";
      }
    }

    // update users
    $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");

    if (!empty ($userdata) && strpos ($userdata, "</validdatefrom>") < 1)
    {
      $datanew = str_replace ("</theme>", "</theme>\n<validdatefrom></validdatefrom>\n<validdateto></validdateto>", $userdata);

      if (!empty ($datanew))
      {
        $savefile = savefile ($mgmt_config['abs_path_data']."user/", "user.xml.php", $datanew);

        // update log
        if ($savefile == true) savelog (array($mgmt_config['today']."|hypercms_update.inc.php|information|8.0.4|updated to version 8.0.4"), "update");
        // sys log
        else savelog (array($mgmt_config['today']."|hypercms_update.inc.php|error|10108|update to version 8.0.4 failed for 'user.xml.php'"));

        return $savefile;
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// ------------------------------------------ update_database_v805 ----------------------------------------------
// function: update_database_v805()
// input: %
// output: true / false

// description: 
// Update of database to version 8.0.5

function update_database_v805 ()
{
  global $mgmt_config;

  $error = array();

  $logdata = loadlog ("update", "string");

  if (empty ($logdata) || strpos ($logdata, "|8.0.5|") < 1)
  {
    // connect to MySQL
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // alter table
    $sql = "ALTER TABLE container ADD publishdate datetime AFTER date;";
    $errcode = "50805";
    $result = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    if (!empty ($mgmt_config['abs_path_data']))
    { 
      // content repository
      $loc = $mgmt_config['abs_path_data']."content/";
  
      // 1 st level (content container blocks)
      $blockdir = scandir ($loc);
  
      $i = 0;
  
      // browse all containers in the content repository
      foreach ($blockdir as $block)
      {
        if (is_dir ($loc.$block) && $block != "." && $block != ".." && is_numeric ($block))
        {
          // 2nd level (specific content container folder)
          $contdir = scandir ($loc.$block);
  
          foreach ($contdir as $container_id)
          {
            // read published container
            if (intval ($container_id) > 0 && is_file ($loc.$block."/".$container_id."/".$container_id.".xml"))
            {
              // load container
              $data = loadfile ($loc.$block."/".$container_id."/", $container_id.".xml");

              $date_published = getcontent ($data, "<contentpublished>");
              $contentstatus = getcontent ($data, "<contentstatus>");

              // if date is available and container status is active
              if (!empty ($date_published[0]) && !empty ($contentstatus[0]) && $contentstatus[0] == "active")
              {
                // set date
                $sql = 'UPDATE container SET publishdate="'.$date_published[0].'" WHERE id='.intval($container_id);
                $errcode = "50097";
                $result = $db->rdbms_query ($sql, $errcode, $mgmt_config['today'], 'update');
              }
            }
          }
        }
      }
    }

    // save log
    savelog ($db->rdbms_geterror ());
    savelog (@$error);
    $db->rdbms_close();

    // update log
    savelog (array($mgmt_config['today']."|hypercms_update.inc.php|information|8.0.5|updated to version 8.0.5"), "update");

    return true;
  }
  else return false;
}

// ------------------------------------------ update_database_v903 ----------------------------------------------
// function: update_database_v903()
// input: %
// output: true / false

// description: 
// Update of database to version 9.0.3

function update_database_v903 ()
{
  global $mgmt_config;

  $error = array();

  $logdata = loadlog ("update", "string");

  if (empty ($logdata) || strpos ($logdata, "|9.0.3|") < 1)
  {
    // connect to MySQL
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // alter table
    $sql = "ALTER TABLE textnodes MODIFY text_id CHAR(255);";
    $errcode = "50903";
    $result = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->rdbms_geterror ());
    savelog (@$error);
    $db->rdbms_close();

    // update log
    savelog (array($mgmt_config['today']."|hypercms_update.inc.php|information|9.0.3|updated to version 9.0.3"), "update");

    return true;
  }
  else return false;
}

// ------------------------------------------ update_database_v910 ----------------------------------------------
// function: update_database_v910()
// input: %
// output: true / false

// description: 
// Adds new column analyzed to table media for support of version 9.1.0

function update_database_v910 ()
{
  global $mgmt_config;

  $error = array();

  $logdata = loadlog ("update", "string");

  if (empty ($logdata) || strpos ($logdata, "|9.1.0|") < 1)
  { 
    // connect to MySQL
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // alter table
    $sql = "ALTER TABLE media ADD COLUMN analyzed tinyint(1) NOT NULL default '0';";
    $errcode = "50910";
    $result = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->rdbms_geterror ());
    savelog (@$error);
    $db->rdbms_close();

    // update log
    savelog (array($mgmt_config['today']."|hypercms_update.inc.php|information|9.1.0|updated to version 9.1.0"), "update");

    return true;
  }
  else return false;
}

// ------------------------------------------ update_plugin_v911 ----------------------------------------------
// function: update_plugin_v911()
// input: %
// output: true / false

// description: 
// Rename the plugin configuration file for version 9.1.1

function update_plugin_v911 ()
{
  global $mgmt_config;

  $error = array();

  $logdata = loadlog ("update", "string");

  if (empty ($logdata) || strpos ($logdata, "|9.1.1|") < 1)
  { 
    // update plugin management file name for version 9.1.1 
    if (is_file ($mgmt_config['abs_path_data']."config/plugin.conf.php"))
    {
      rename ($mgmt_config['abs_path_data']."config/plugin.conf.php", $mgmt_config['abs_path_data']."config/plugin.global.php");
    }

    // update log
    savelog (array($mgmt_config['today']."|hypercms_update.inc.php|information|9.1.1|updated to version 9.1.1"), "update");

    return true;
  }
  else return false;
}

// ------------------------------------------ update_database_v914 ----------------------------------------------
// function: update_database_v914()
// input: %
// output: true / false

// description: 
// Adds new column cmd to table queue for support of version 9.1.4

function update_database_v914 ()
{
  global $mgmt_config;

  $error = array();

  $logdata = loadlog ("update", "string");

  if (empty ($logdata) || strpos ($logdata, "|9.1.4|") < 1)
  { 
    // connect to MySQL
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // alter table
    $sql = "ALTER TABLE queue ADD COLUMN cmd varchar(21000) DEFAULT NULL AFTER published_only;";
    $errcode = "50914";
    $result = $db->rdbms_query ($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->rdbms_geterror ());
    savelog (@$error);
    $db->rdbms_close();

    // update log
    savelog (array($mgmt_config['today']."|hypercms_update.inc.php|information|9.1.4|updated to version 9.1.4"), "update");

    return true;
  }
  else return false;
}

// ------------------------------------------ updates_all ----------------------------------------------
// function: updates_all()
// input: %
// output: true / false

// description: 
// Calls all update functions

function updates_all ()
{
  global $mgmt_config;

  update_users_546 ();
  update_tasks_v584 ();
  update_database_v586 ();
  update_database_v601 ();
  update_database_v614 ();
  update_database_v6113 ();
  update_container_v6118 ();
  update_database_v6139 ();
  update_database_v625 ();
  $update = update_database_v705 ($mgmt_config['abs_path_comp'], true);
  if ($update) savelog (array($mgmt_config['today']."|hypercms_update.inc.php|information|7.0.5|updated to version 7.0.5"), "update");
  update_users_706 ();
  update_database_v708();
  update_users_709();
  update_config_7010();
  update_database_v800 ();
  update_users_804 ();
  update_database_v805 ();
  update_database_v903 ();
  update_database_v910 ();
  update_plugin_v911 ();;
  update_database_v914 ();
}
?>