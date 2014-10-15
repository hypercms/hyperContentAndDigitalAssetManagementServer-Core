<?php
// use this file to define rules for splitting multimedia files over
// several media repositories (must be defined in config.inc.php).

// input parameters are:
// $site ... publication name
// $file ... name of the multimedia file
// $type ... fixed value [abs_path_media]
// $container_id ... ID of the multimedia file (e.g. 0000112)

// output parameter:
// you have to return a valid path to the media repository!

// globals:
// $mgmt_config ... configuration settings

/* 
function getmedialocation_rule ($site, $file, $type, $container_id)
{
  global $mgmt_config;
  
  // example code:
  // define max. files per repository
  $maxfiles = 175000;
  
  // define rule which fills each repository to max. files based on it's ID
  $container_no = intval ($container_id);
  
  // number of repositories
  $rep_size = sizeof ($mgmt_config[$type]);
  
  for ($i=1; $i<=$rep_size; $i++)
  {
    if ($container_no <= $i * $maxfiles) return $mgmt_config[$type][$i];
  }
}
*/
?>