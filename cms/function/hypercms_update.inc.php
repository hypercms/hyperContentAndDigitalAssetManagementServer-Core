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

// update to version 5.6.4 (group names will be replaced by object-IDs)

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
?>