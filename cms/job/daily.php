<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
require ("../config.inc.php");
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
    
    if (!empty ($mgmt_config['abs_path_cms']) && !empty ($mgmt_config['abs_path_data']))
    {
      // delete temporary files and ZIP files older than the given value in seconds
      $location = $mgmt_config['abs_path_cms']."temp/";
      $timespan = 86400; // 24 hours
      
      if ($location != "" && $timespan != "" && $dir = opendir ($location))
      {
        while (($file = readdir ($dir)) !== false)
        {
          if ($file != "." && $file != ".." && $file != "" && strtolower ($file) != ".htaccess" && strtolower ($file) != "view")
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
      
      // check disk key
      checkdiskkey ();
      
      // send license notification to given users
      $config_dir = opendir ($mgmt_config['abs_path_data']."config/");
      
      if ($config_dir)
      {
        while ($file = @readdir ($config_dir))
        {
          if (strpos ($file, ".msg.dat") > 0 && is_file ($mgmt_config['abs_path_data']."config/".$file))
          {
            $config_data = loadfile_fast ($mgmt_config['abs_path_data']."config/", $file);
            
            if ($config_data != false)
            {
              $config_array = explode ("\n", trim ($config_data));                 
            
              if (is_array ($config_array) && sizeof ($config_array) >= 1)
              {
                sort ($config_array);
              
                foreach ($config_array as $config_folder)
                {
                  $date_begin = "";
                  $date_end = "";
                
                  list ($object_id, $text_id, $format, $period, $users) = explode ("|", $config_folder);
                   
                  $location = rdbms_getobject ($object_id);

                  // define format string (international date format that is used for queries in the database)
                  $format_db = "Y-m-d";
                  
                  if ($location != "" && $text_id != "" && $period != "" && $users != "")
                  {
                    // for each first day of the month
                    if ($period == "monthly" && date ("d", time()) == "01") 
                    {
                      // current month plus 1 month
                      $month = intval (date ("m", time())) + 1;
                      // current year
                      $year = intval (date ("Y", time()));
                      // correct month and year
                      if ($month == 13)
                      {
                        $month = 1;
                        $year = $year + 1;
                      }      
                      // 1st day of month
                      $date_begin = date ($format_db, mktime (0, 0, 0, $month, 1, $year));
                      // one month later
                      $date_end = date ($format_db, mktime (0, 0, 0, ($month + 1), 0, $year));
                    }
                    // for each sunday
                    elseif ($period == "weekly" && strtolower (date ("D", time())) == "sun") 
                    {
                      // one week later
                      $date_begin = date ($format_db, time() + (60*60*24*7));
                      // two weeks later
                      $date_end = date ($format_db, time() + (60*60*24*14));
                    }
                    // for each day
                    elseif ($period == "daily") 
                    {
                      // tomorrow
                      $date_end = $date_begin = date ($format_db, time() + (60*60*24));
                    }
                    
                    // split users into array
                    $user_array = splitstring ($users);
                  
                    // send notifications tu users
                    if ($date_begin != "" && $date_end != "")
                    {
                      // .folder object must be removed!
                      $site = getpublication ($location);
                      $cat = getcategory ($site, $location);
                      $location = getlocation ($location);
      
                      $result = licensenotification ($site, $cat, $location, $text_id, $date_begin, $date_end, $user_array, $format);
                    }
                  }
                }
              }
            }
          }
        }
      }
      else
      {
        $errcode = "10742";
        $error[] = $mgmt_config['today']."|daily.php|error|$errcode|license notification can not be executed. Config directory is missing.";
      }
    }
  }
}

// save log
savelog (@$error);  
?>
