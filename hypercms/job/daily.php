<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
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
  // ---------------------------------------------------- FOR EACH INSTANCE -------------------------------------------------
  
  foreach ($config_files as $config_file)
  {
    // load main config
    require ("../".$config_file);
    
    if (!empty ($mgmt_config['abs_path_cms']) && !empty ($mgmt_config['abs_path_data']))
    {
      // load inheritance DB
      $inherit_db = inherit_db_read ();
      
      if (is_array ($inherit_db))
      {
        // ------------------------------------------- FOR EACH PUBLICATION ---------------------------------------------
        
        foreach ($inherit_db as $site => $array)
        {
          // load publication config if not available
          if (!isset ($mgmt_config[$site]['abs_path_comp']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
          {
            require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
          }
          
          // ---------------------------------------------- UPDATE TAXONOMY ----------------------------------------------
          
          // remove taxonomies from DB
          if (function_exists ("rdbms_deletepublicationtaxonomy")) rdbms_deletepublicationtaxonomy ($site, false);

          // set taxonomies in DB
          if (function_exists ("rdbms_setpublicationtaxonomy")) rdbms_setpublicationtaxonomy ($site, false);

          // ----------------------------------------------- STORAGE SPACE -----------------------------------------------
          // calculate used storage space
          // create filesize.dat files in order to check storage limit (MB) for each publication
          if (isset ($mgmt_config[$site]['storage_limit']) && $mgmt_config[$site]['storage_limit'] > 0)
          {
            // memory for file size (should be kept for 24 hours)
            $filesize_mem = $mgmt_config['abs_path_temp'].$site.".filesize.dat";
            
            if (!is_file ($filesize_mem) || (filemtime ($filesize_mem) + 86400) < time())
            {  
              // this function might require some time for the result in case of large databases
              $filesize = rdbms_getfilesize ("", "%comp%/".$site."/");
              savefile ($mgmt_config['abs_path_temp'], $site.".filesize.dat", $filesize['filesize']);
            }
          }
        }
      }
    
      // ------------------------------------------- TEMP AND SESSION FILES ---------------------------------------------
      
      // delete temporary files and ZIP files older than the given value in seconds
      $location = $mgmt_config['abs_path_temp'];
      $timespan = 86400; // 24 hours
      
      if ($location != "" && $timespan != "" && $dir = opendir ($location))
      {
        while (($file = readdir ($dir)) !== false)
        {
          if ($file != "." && $file != ".." && $file != "" && strtolower ($file) != ".htaccess" && strtolower ($file) != "web.config" && strtolower ($file) != "view")
          {
            if (filemtime ($location.$file) + $timespan < time())
            {
              deletefile ($location, $file, 1);
            }      
          }
        }
        
        closedir ($dir);
      }
      
      // delete hyperdav user session files older than the given value in seconds
      $location = $mgmt_config['abs_path_data']."session/";
      $timespan = 86400; // 24 hours
      
      if ($location != "" && $timespan != "" && $dir = opendir ($location))
      {
        while (($file = readdir ($dir)) !== false)
        {
          if ($file != "." && $file != ".." && is_file ($location.$file))
          {
            if (filemtime ($location.$file) + $timespan < time())
            {
              deletefile ($location, $file, 0);
            }      
          }
        }
        
        closedir ($dir);
      }
      
      // ----------------------------------------------- DISK KEY ----------------------------------------------------
      
      // check disk key
      checkdiskkey ();
      
      // -------------------------------------------------- TASK ----------------------------------------------------
      
      // send task notification to users
      if (function_exists ("tasknotification")) tasknotification (date("Y-m-d"));
      
      // ------------------------------------------------ LICENSE ---------------------------------------------------
      
      // send license notification to users
      if (function_exists ("licensenotification")) licensenotification ();

      // ------------------------------------------------- EXPORT ---------------------------------------------------
      
      // export job
      if (function_exists ("exportobjects")) exportobjects ();
      
      // ------------------------------------------------- IMPORT ---------------------------------------------------
      
      // import job
      if (function_exists ("importobjects")) importobjects ();
  
      // ----------------------------------------------- CLOUD SYNC -------------------------------------------------
      
      // synchronize media files in repository with cloud storage
      if (function_exists ("synccloudobjects")) synccloudobjects ("sys");
      
      // ------------------------------------------- TAXONOMY DEFINITIONS -------------------------------------------
      
      // create taxonomy defintion files
      if (function_exists ("createtaxonomy")) createtaxonomy (false);
    }
  }
}

// save log
savelog (@$error);  
?>