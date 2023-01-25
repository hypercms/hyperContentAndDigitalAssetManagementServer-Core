<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */

// session
define ("SESSION", "create");
// management configuration
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");


// input parameters
$container_id = getrequest ("container_id", "objectname");
$tagname = getrequest ("tagname", "objectname");
$tagid = getrequest ("tagid", "objectname");

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// initialize
$data = array('success' => false, 'message' => "");
$update_file = $mgmt_config['abs_path_temp']."update.".$container_id.".dat";
$timeframe = 4;

// write and close session (non-blocking other frames)
suspendsession ();

if ($container_id != "")
{   
  if (is_file ($update_file))
  {
    // load temp file
    $temp_array = file ($update_file);

    if (is_array ($temp_array))
    {
      foreach ($temp_array as $temp)
      {
        if ($temp != "" && substr_count ($temp, ":") >= 3)
        {
          list ($temp_time, $temp_tagname, $temp_tagid, $temp_user) = explode (":", $temp);

          // if update has been made in the last 3 seconds and the user is not the same
          if (intval ($temp_time) > (time() - $timeframe) && trim ($temp_user) != $user)
          {
            // if the tag is the same or empty
            if (($tagname != "" && $tagname == $temp_tagname) || empty ($tagname))
            {
              // if the content Id is the same or empty
              if (($tagid != "" && $tagid == $temp_tagid) || empty ($tagid))
              {
                $data['success'] = true;
                $data['message'] = str_replace ("%user%", trim ($temp_user), $hcms_lang['content-modified-by-user'][$lang]);
              }
            }
          }
        }
      }
    }
  } 
}

header ('Content-Type: application/json; charset=utf-8');
print json_encode ($data);
exit;
?>