<?php
// ---------------------- RECENT TASKS ---------------------
if (checkrootpermission ('desktoptaskmgmt'))
{
  if ($is_mobile) $width = "92%";
  else $width = "320px";

  //load task file and get all task entries
  $task_data = loadfile ($mgmt_config['abs_path_data']."task/", $user.".xml.php");
  
  // get all tasks
  if ($task_data != "")
  {
    $task_array = getcontent ($task_data, "<task>");
  
    if (is_array ($task_array) && sizeof ($task_array) > 0)
    {
      echo "<div id=\"task\" onclick=\"document.location.href='task_list.php';\" class=\"hcmsInfoBox\" style=\"overflow:auto; margin:10px; width:".$width."; height:400px; float:left; cursor:pointer;\">\n";

      echo "<div class=\"hcmsHeadline\" style=\"margin:2px;\">".getescapedtext ($hcms_lang['my-recent-tasks'][$lang])."</div>
      <table width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">";

      foreach ($task_array as $task_node)
      {
        $task_date_array = getcontent ($task_node, "<task_date>");
        $task_priority_array = getcontent ($task_node, "<priority>");
        $task_descr_array = getcontent ($task_node, "<description>");
        
        // define row color
        if (!empty ($task_priority_array[0]))
        {
          if ($task_priority_array[0] == "high") $rowcolor = "hcmsPriorityHigh";
          elseif ($task_priority_array[0] == "medium") $rowcolor = "hcmsPriorityMedium";
          else $rowcolor = "hcmsPriorityLow"; 
        }
        
        // define date
        if (!empty ($task_date_array[0])) $date = str_replace (array ("-", ":"), array ("", ""), $task_date_array[0]);
        
        if (!empty ($date)) $task[$date] = "
        <tr class=\"".$rowcolor."\">
          <td valign=\"top\">".$task_date_array[0]."</td>
          <td valign=\"top\">".str_replace ("\n", "<br />", $task_descr_array[0])."</td>
        </tr>";
      }
      
      if (!empty ($task) && is_array ($task))
      {
        krsort ($task);
        reset ($task);
        $i = 0;
    
        foreach ($task as $content)
        {
          if ($i < 3)
          {
            echo $content;
            $i++;
          }
        }
      }
      
      echo "
      </table>
      </div>\n";
    }
  }
}
?>