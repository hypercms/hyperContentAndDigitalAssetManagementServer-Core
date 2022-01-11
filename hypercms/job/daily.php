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
  // ------------------------------------------------ FOR EACH INSTANCE ---------------------------------------------
  
  foreach ($config_files as $config_file)
  {
    // load main config
    require ("../".$config_file);
    
    if (!empty ($mgmt_config['abs_path_cms']) && !empty ($mgmt_config['abs_path_data']))
    {      
      // ----------------------------------------------- DISK KEY ---------------------------------------------------
      
      // check disk key
      checkdiskkey ();
      
      // -------------------------------------------------- TASK ----------------------------------------------------
      
      // send task notification to users
      if (function_exists ("tasknotification")) tasknotification (date("Y-m-d"));
      
      // ------------------------------------------------ LICENSE ---------------------------------------------------
      
      // send license notification to users
      if (function_exists ("licensenotification")) licensenotification ();

      // ------------------------------------------ DELETE INVALID USERS --------------------------------------------
      
      // delete invalid users
      if (!empty ($mgmt_config['userdelete']))
      {
        // get todays date
        $todaydate = strtotime (date ("Y-m-d", time()));

        // load user file
        $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");

        if ($userdata != "")
        {
          // get user nodes
          $usernode = getcontent ($userdata, "<user>");

          foreach ($usernode as $temp)
          {
            if ($temp != "")
            {
              $validdatenode = getcontent ($temp, "<validdateto>");

              if (!empty ($validdatenode[0]))
              {
                $validdate = strtotime ($validdatenode[0]);

                if ($validdate < $todaydate) 
                {
                  $loginnode = getcontent ($temp, "<login>");

                  // delete user
                  if (!empty ($loginnode[0])) deleteuser ("*Null*", $loginnode[0], "sys");
                }
              }
            }
          }
        }
      }

      // ------------------------------------------------- EXPORT ---------------------------------------------------
      
      // export job
      if (function_exists ("exportobjects")) exportobjects ();
      
      // ------------------------------------------------- IMPORT ---------------------------------------------------
      
      // import job
      if (function_exists ("importobjects")) importobjects ();
  
      // ------------------------------------------- EMPTY RECYCLE BIN ----------------------------------------------
      
      // permanently delete all objects from recycle bin
      if (function_exists ("rdbms_getdeletedobjects") && !empty ($mgmt_config['recycledays']) && $mgmt_config['recycledays'] > 0)
      {
        // get date based on the maximum days objects may reside in the recycle bin
        $date = date ("Y-m-d", time() - $mgmt_config['recycledays']*24*60*60);
        
        // remove all obecjts permanently that have been marked for deletetion before the defined date
        $objectpath_array = rdbms_getdeletedobjects ("", $date, 1000000, "", false, false);
        
        if (is_array ($objectpath_array) && sizeof ($objectpath_array) > 0)
        {
          foreach ($objectpath_array as $objectpath)
          {
            if (!empty ($objectpath['objectpath']))
            {
              // if folder object remove .folder
              if (getobject ($objectpath['objectpath']) == ".folder") $objectpath = getlocation ($objectpath['objectpath']);

              if ($objectpath['objectpath'] != "") processobjects ("delete", getpublication($objectpath['objectpath']), getlocation($objectpath['objectpath']), getobject($objectpath['objectpath']), 0, "sys");
            }
          }
        }

        // remove objects from database in case they could not be deleted by function processobjects
        $objectpath_array = rdbms_getdeletedobjects ("", $date, 1000000, "", false, true);

        if (is_array ($objectpath_array) && sizeof ($objectpath_array) > 0)
        {
          foreach ($objectpath_array as $objectpath)
          {
            if (!empty ($objectpath['objectpath']))
            {
              rdbms_deleteobject ($objectpath['objectpath'], "");
            }
          }
        }
      }
      
      // ----------------------------------------------- CLOUD SYNC -------------------------------------------------
      
      // synchronize media files in repository with cloud storage
      if (function_exists ("synccloudobjects")) synccloudobjects ("sys");
      
      // ------------------------------------------ FOR EACH PUBLICATION --------------------------------------------
      
      // load inheritance DB
      $inherit_db = inherit_db_read ();
      
      if (is_array ($inherit_db))
      {
        foreach ($inherit_db as $site => $array)
        {
          // load publication config if not available
          if (!isset ($mgmt_config[$site]['abs_path_comp']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
          {
            require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
          }

          // ---------------------------------------------- UPDATE TAXONOMY ----------------------------------------------
          
          // remove disabled taxonomies from DB
          if (function_exists ("rdbms_deletepublicationtaxonomy")) rdbms_deletepublicationtaxonomy ($site, false);

          // set taxonomies in DB for objects with no taxonomy references
          if (function_exists ("rdbms_setpublicationtaxonomy")) rdbms_setpublicationtaxonomy ($site, false);

          // ----------------------------------------------- STORAGE SPACE -----------------------------------------------
          // calculate used storage space
          // create filesize.dat files in order to check storage limit (MB) for each publication
          if (isset ($mgmt_config[$site]['storage_limit']) && $mgmt_config[$site]['storage_limit'] > 0)
          {
            // memory for file size (should be kept for 24 hours)
            $filesize_mem = $mgmt_config['abs_path_temp'].$site.".filesize.dat";
            
            if (!is_file ($filesize_mem) || (filemtime ($filesize_mem) + 3600) < time())
            {  
              $starttime = time();
              
              // this function might require some time for the result in case of large database
              $filesize = rdbms_getfilesize ("", "%comp%/".$site."/");
              savefile ($mgmt_config['abs_path_temp'], $site.".filesize.dat", $filesize['filesize']);
              
              $endtime = time();
              $duration = $endtime - $starttime;
              
              // log if calculation needed more than 1 hour
              if ($duration > 3600)
              {
                $errcode = "00911";
                $error[] = $mgmt_config['today']."|daily.php|warning|".$errcode."|Used space calculation took ".round(($duration / 3600), 2)." hours for publication ".$site;
      
                savelog (@$error);
              }
            }
          }
        }
      }
    
      // ------------------------------------------- TEMP AND SESSION FILES ---------------------------------------------
      
      // delete temporary files and ZIP files older than the given value in seconds
      $location = $mgmt_config['abs_path_temp'];
      $timespan = 86400; // 24 hours
      
      if ($location != "" && $timespan != "" && $scandir = scandir ($location))
      {
        foreach ($scandir as $file)
        {
          if ($file != "." && $file != ".." && $file != "" && strtolower ($file) != ".htaccess" && strtolower ($file) != "web.config" && strtolower ($file) != "view")
          {
            // check media file age and keep_previews setting
            if (filemtime ($location.$file) + $timespan < time() && (empty ($mgmt_config['keep_previews']) || !is_preview ($file)))
            {
              deletefile ($location, $file, 1);
            }      
          }
        }
      }
      
      // delete hyperdav user session files older than the given value in seconds
      $location = $mgmt_config['abs_path_data']."session/";
      $timespan = 86400; // 24 hours
      
      if ($location != "" && $timespan != "" && $scandir = scandir ($location))
      {
        foreach ($scandir as $file)
        {
          if ($file != "." && $file != ".." && is_file ($location.$file))
          {
            if (filemtime ($location.$file) + $timespan < time())
            {
              deletefile ($location, $file, 0);
            }      
          }
        }
      }

      // ------------------------------------------- DATABASE OPTIMIZATION -------------------------------------------

      // optimize database on 1st of January
      if (!empty ($mgmt_config['rdbms_optimize']) && date("m-d") == "01-01") rdbms_optimizedatabase ();

    }
  }
}

// save log
savelog (@$error);  
?>