<?php
// ---------------------- FREE STORAGE ---------------------
if (function_exists ("disk_total_space") && function_exists ("disk_free_space"))
{
  // box width
  if (!empty ($is_mobile)) $width = "320px";
  else $width = "320px";

  echo "
  <div id=\"free_storage\" class=\"hcmsHomeBox\" style=\"margin:10px; width:".$width."; height:400px;\">
    <div class=\"hcmsHeadline\" style=\"margin:6px 2px;\"><img src=\"".getthemelocation("night")."img/instance.png\" class=\"hcmsIconList\" /> Server Storage Space</div>
    <hr />
    <div style=\"text-align:right; padding:20px;\">";
  
      $space_total = disk_total_space ($mgmt_config['abs_path_rep']);
      
      if ($space_total > 0)
      {
        // if other disk drives are used
        if (!empty ($mgmt_config['additional_storage'])) $add = intval ($mgmt_config['additional_storage']);
        else $add = 0;
        
        $space = $space_total/1024/1024/1024 + $add;
        echo "Total <span style=\"font-size:32px;\">".number_format ($space, 2, ".", " ")." GB</span>"; 
      }
      else echo "Total <span style=\"font-size:32px;\">Not available</span>";
      
      echo "<br /><br />";

      $space_free = disk_free_space ($mgmt_config['abs_path_rep']);
      
      if ($space_free)
      {
        echo "Free <span style=\"font-size:32px;\">".number_format (($space_free/1024/1024/1024), 2, ".", " ")." GB</span>";
      }
      else echo "Free <span style=\"font-size:32px;\">Not available</span>";
      
      echo "<br /><br /><br />";
      
      $percentage = round ((($space_total - $space_free) / $space_total), 4) * 100;
      if ($percentage > 100) $percentage = 100;
      
      if ($percentage > 90) $css_color = "hcmsPriorityAlarm";
      elseif ($percentage > 80) $css_color = "hcmsPriorityHigh";
      elseif ($percentage > 70) $css_color = "hcmsPriorityMedium";
      else $css_color = "hcmsPriorityLow";
      
      if ($percentage >= 0) echo "
      <table style=\"width:100%; padding:0; border:1px solid #000000; border-collapse:collapse;\">
        <tr> 
          <td>
            <div class=\"".$css_color."\" style=\"width:".ceil($percentage)."%; height:32px; text-align:center; font-size:26px; line-height:32px; overflow:hidden;\">".ceil($percentage)." %</div>
          </td>
        </tr>
      </table>";
  
  echo "
    </div>
  </div>";
}
?>