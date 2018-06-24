<?php
// ---------------------- STORAGE ---------------------
function usedstorage ($publication)
{
  global $mgmt_config;

  // factor to correct used storage due to annotation files, video previews, and so on
  if (!empty ($mgmt_config[$publication]['storagefactor'])) $factor = $mgmt_config[$publication]['storagefactor'];
  elseif (!empty ($mgmt_config['storagefactor'])) $factor = $mgmt_config['storagefactor'];
  else $factor = 1.2;
  
  if ($publication != "")
  {
    // memory for file size (should be kept for 24 hours)
    $filesize_mem = $mgmt_config['abs_path_temp'].$publication.".filesize.dat";
  
    if (!is_file ($filesize_mem) || (filemtime ($filesize_mem) + 86400) < time())
    {  
      // this function might require some time for the result in case of large databases
      $filesize = rdbms_getfilesize ("", "%comp%/".$publication."/");
      savefile ($mgmt_config['abs_path_temp'], $publication.".filesize.dat", $filesize['filesize']);
    }
    else $filesize['filesize'] = loadfile ($mgmt_config['abs_path_temp'], $publication.".filesize.dat");

    // file size in KB and number of files
    if (is_array ($filesize))
    {
      // file size in GB
      $filesize['filesize'] = round ((intval ($filesize['filesize']) / 1024 / 1024) * $factor, 2);
      return $filesize;
    }
    else return false;
  }
  else return false;
}

function maxstorage ($site)
{
  global $mgmt_config;
  
  if (!empty ($mgmt_config[$site]['storage_limit']))
  {
    // storage in GB
    $maxstorage = round (($mgmt_config[$site]['storage_limit'] / 1024), 2);
    return $maxstorage;
  }
  else return false;
}

if ($is_mobile) $width = "92%";
else $width = "320px";

if (is_array ($siteaccess))
{
  sort ($siteaccess);
  
  foreach ($siteaccess as $site)
  {
    if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      // publication management config
      require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    
      echo "
  <div id=\"free_storage_per_publication\" class=\"hcmsHomeBox\" style=\"margin:10px; width:".$width."; height:400px; float:left;\">
    <div class=\"hcmsHeadline\" style=\"margin:2px;\">Publication Storage Space</div>
    <hr />
    <div style=\"text-align:right; padding:20px;\">";
    
      $percentage = 0;
      $space_total = 0;
      $space_used = -1;
      $space_free = 0;
    
      // get used storage space
      $usedstorage = usedstorage ($site);
  
      if (is_array ($usedstorage))
      {
        $space_used = $usedstorage['filesize'];
      }
  
      // get total storage space
      $space_total = maxstorage ($site);
      
      if ($space_total > 0)
      {
        $space_free = $space_total - $space_used;
  
        if ($space_used > 0)
        {
          if ($space_used < $space_total) $percentage = $space_used / $space_total;
          elseif ($space_used >= $space_total) $percentage = 100;
        }
      }
      
      echo "<h2>".$site."</h2>\n";
      
      if ($space_total > 0)
      {
        echo "Total <span style=\"font-size:32px;\">".number_format ($space_total, 2, ".", ",")." GB</span>"; 
      }
      else echo "Total <span style=\"font-size:32px;\">No Limit</span>";
      
      echo "<br /><br />";
      
      if ($space_used > 0)
      {
        echo "Used <span style=\"font-size:32px;\">".number_format (($space_used), 2, ".", ",")." GB</span>";
      }
      else echo "Used <span style=\"font-size:32px;\"> 0 GB</span>";
      
      echo "<br /><br />";
      
      if ($space_free)
      {
        if ($space_free < 0) $style = "color:orange;";
        else $style = "";
        
        echo "Free <span style=\"font-size:32px;".$style."\">".number_format (($space_free), 2, ".", ",")." GB</span>";
      }
      else echo "Free <span style=\"font-size:32px;\">Not available</span>";
      
      echo "<br /><br /><br />";
      
      $percentage = round ((($space_total - $space_free) / $space_total), 4) * 100;
      if ($percentage > 100) $percentage = 100;
      
      if ($percentage  >= 0) echo "
      <table style=\"width:100%; padding:0; border:1px solid #000000; border-collapse:collapse;\">
        <tr> 
          <td>
            <div class=\"hcmsRowHead1\" style=\"width:".ceil($percentage)."%; height:32px; text-align:center; font-size:26px; line-height:32px; overflow:hidden;\">".ceil($percentage)." %</div>
          </td>
        </tr>
      </table>";
  
    echo "
    </div>
  </div>";
    }
  }
}
?>