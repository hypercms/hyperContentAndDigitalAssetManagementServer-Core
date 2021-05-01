<?php
// use this fnuction to define rules for splitting multimedia files over
// several content media repositories (an array must be defined in config.inc.php).
// Prepare the rule and uncomment the function in order to use it.

// function: getmedialocation_rule ()

// input parameters:
// site ... publication name [string]
// file ... name of the multimedia file [string]
// type ... fixed value [abs_path_media]
// container_id ... ID of the multimedia file (e.g. 0000112) [string or integer]

// output parameter:
// The function must return a valid path to the media repository as string

// globals:
// $mgmt_config ... main configuration settings

/*
function getmedialocation_rule ($site, $file, $type, $container_id)
{
  global $mgmt_config;
  
  // Example code:
  // If you are not using LVM for addnig mutiple HDDs to a logical volume (the logilc volume will fail if one HDD fails) you need to define
  // a rule for spreading the files in the content media repository over multiple harddisks or mountpoints.
  // This can be done by definnig the max. number of files per harddisk/mountpoint.
  // Keep in mind that you can't change the number anymore since the rule must remain the same in order to locate the correct harddsisk/mountpoint for a media file.
  // You can calculate the average file size for your publication(s) using the asset folder properties (file site / file number = average file size in MB).
  // Divide the storage space by the average file size and reduce the number by 10% (due to thumbnail and version files) to get the max. number of files.
  // You will also see the average file size in the home box "Free storage per publication".
  $maxfiles = 175000;
  
  // Define the rule that stores the max. number of files each media repository (filling up each repository before using the next) based on the files container ID
  $container_id = intval ($container_id);
  
  // Number of repositories
  $rep_size = sizeof ($mgmt_config[$type]);
  
  for ($i=1; $i<=$rep_size; $i++)
  {
    if ($container_id <= $i * $maxfiles) return $mgmt_config[$type][$i];
  }
}
*/
?>