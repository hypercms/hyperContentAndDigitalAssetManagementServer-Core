<?php
// ---------------------- RECENT TASKS ---------------------
if (checkrootpermission ('desktoptaskmgmt'))
{
  if ($is_mobile) $width = "92%";
  else $width = "320px";

  $task_array = rdbms_gettask ("", "", "", "", $user);

  if (is_array ($task_array) && sizeof ($task_array) > 0)
  {
    echo "<div id=\"task\" onclick=\"document.location.href='taskmgmt/task_list.php';\" class=\"hcmsInfoBox\" style=\"overflow:auto; margin:10px; width:".$width."; height:400px; float:left; cursor:pointer;\">\n";

    echo "<div class=\"hcmsHeadline\" style=\"margin:2px;\">".getescapedtext ($hcms_lang['my-recent-tasks'][$lang])."</div>
    <table width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">";

    foreach ($task_array as $task_record)
    {
      if ($task_record['status'] < 100)
      {
        // define row color
        if (!empty ($task_record['priority']))
        {
          if ($task_record['priority'] == "high") $rowcolor = "hcmsPriorityHigh";
          elseif ($task_record['priority'] == "medium") $rowcolor = "hcmsPriorityMedium";
          else $rowcolor = "hcmsPriorityLow"; 
        }
      
        // empty finish date if not set
        if ($task_record['finishdate'] == "0000-00-00") $task_record['finishdate'] = "";
        // compare today with finish date
        elseif ((time()-(60*60*24)) >= strtotime($task_record['finishdate'])) $rowcolor = "hcmsPriorityAlarm";

        echo "
      <tr class=\"".$rowcolor."\">
        <td valign=\"top\">".$task_record['startdate']." &#x0203A; ".$task_record['finishdate']."</td>
        <td valign=\"top\">".str_replace ("\n", "<br />", $task_record['description'])."</td>
      </tr>";
      }
    }
    
    echo "
    </table>
    </div>\n";
  }
}
?>