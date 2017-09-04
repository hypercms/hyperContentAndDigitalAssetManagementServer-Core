<?php
// ---------------------- STORAGE ---------------------

if ($is_mobile) $width = "92%";
else $width = "320px";

if (function_exists ("disk_total_space") && function_exists ("disk_free_space"))
{
  echo "
  <div id=\"recent_downloads\" class=\"hcmsHomeBox\" style=\"margin:10px; width:".$width."; height:400px; float:left;\">
    <div class=\"hcmsHeadline\" style=\"margin:2px;\">Storage space</div>
    <hr />
    <div style=\"text-align:right; padding:20px;\">";
  
      $space_total = disk_total_space ($mgmt_config['abs_path_cms']);
      
      if ($space_total > 0)
      {
        // if other disk drives are used
        if (!empty ($mgmt_config['additional_storage'])) $add = intval ($mgmt_config['additional_storage']);
        else $add = 0;
        
        $space = $space_total/1024/1024/1024 + $add;
        echo "Total <span style=\"font-size:32px;\">".number_format ($space, 2, ",", ".")." GB</span>"; 
      }
      else echo "Not available";
      
      echo "<br /><br />";

      $space_free = disk_free_space ($mgmt_config['abs_path_cms']);
      
      if ($space_free)
      {
        echo "Free <span style=\"font-size:32px;\">".number_format (($space_free/1024/1024/1024), 2, ",", ".")." GB</span>";
      }
      else echo "Not available";
      
      echo "<br /><br /><br />";
      
      $bar = round ((($space_total - $space_free) / $space_total), 4) * 100;
      
      if ($bar > 0) echo "
      <table style=\"width:100%; padding:0; border:1px solid #000000; border-collapse:collapse;\">
        <tr> 
          <td>
            <div class=\"hcmsRowHead1\" style=\"width:".ceil($bar)."%; height:32px; text-align:center; font-size:26px; line-height:32px; overflow:hidden;\">".ceil($bar)." %</div>
          </td>
        </tr>
      </table>";
  
  echo "
    </div>
  </div>";
}
?>