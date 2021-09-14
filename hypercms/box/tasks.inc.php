<?php
// ---------------------- RECENT TASKS ---------------------
if (checkrootpermission ('desktoptaskmgmt'))
{
  // box width
  if (!empty ($is_mobile)) $width = "320px";
  else $width = "320px";

  // get tasks of user
  if (!empty ($user)) $task_array = rdbms_gettask ("", "", "", "", $user);

  if (!empty ($task_array) && is_array ($task_array) && sizeof ($task_array) > 0)
  {
    echo "
  <div id=\"task\" onclick=\"document.location.href='task/task_list.php';\" class=\"hcmsHomeBox\" style=\"overflow:auto; margin:10px; width:".$width."; height:400px; float:left; cursor:pointer;\">";

    echo "
    <div class=\"hcmsHeadline\" style=\"margin:6px;\">".getescapedtext ($hcms_lang['my-recent-tasks'][$lang])."</div>
    <table class=\"hcmsTableStandard\" style=\"table-layout:auto; border-collapse:separate; border-spacing:2px; width:100%;\">";

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
        <td style=\"vertical-align:top;\">".showdate ($task_record['startdate'], "Y-m-d", $hcms_lang_date[$lang])." &#x0203A; ".showdate ($task_record['finishdate'], "Y-m-d", $hcms_lang_date[$lang])."</td>
        <td style=\"vertical-align:top;\">".str_replace ("\n", "<br />", $task_record['description'])."</td>
      </tr>";
      }
    }
    
    echo "
    </table>
  </div>";
  }
}
?>