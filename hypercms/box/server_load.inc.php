<?php
// ---------------------- STATS ---------------------
if (!$is_mobile && isset ($siteaccess) && is_array ($siteaccess))
{
  // language file
  require_once ("language/".getlanguagefile ($lang));
  
  // max history
  $maxcount = 30;
  
    // load log file
  $event_array = loadlog ("serverload");
  
  // collect log data
  if (is_array ($event_array) && sizeof ($event_array) > 0)
  {
    // reverse array
    $event_array = array_reverse ($event_array);
  
    $i = $maxcount;
    $date_axis = array();
    $load_axis = array();
    $mem_axis = array();
    
    foreach ($event_array as $event)
    {
      $i--;
      
      if ($event != "" && $i > 0)
      {
        list ($datetime, $load, $cpu, $memory) = explode ("|", $event);
        
        list ($date, $time) = explode (" ", $datetime);
        if ($load > 1) $load = 1;
        
        $date_axis[$i] = "<div style=\"transform:rotate(-45deg); transform-origin:right bottom 0; margin:2px 0px 0px -15px;\">".$time."</div>";
        $load_axis[$i]['value'] = round ($load * 100);
        $load_axis[$i]['text'] = round ($load * 100)."%";
        $mem_axis[$i]['value'] = round ($memory * 100);
        $mem_axis[$i]['text'] = round ($memory * 100)."%";
      }
    }

    // display log data
    if (!empty ($date_axis) && !empty ($load_axis))
    {
      if ($is_mobile) $width = "92%";
      else $width = "670px";
      
      echo "
      <div id=\"stats_serverload\" class=\"hcmsHomeBox\" style=\"overflow:auto; margin:10px; width:".$width."; height:400px; float:left;\">
        <div class=\"hcmsHeadline\">Server load and memory usage</div>";
        
      if (is_array ($load_axis) || is_array ($mem_axis))
      {
        ksort ($date_axis);
        ksort ($load_axis);
        ksort ($mem_axis);
        
        $chart = buildbarchart ("chart", 600, 270, 8, 40, $date_axis, $load_axis, $mem_axis, "", "border:1px solid #666666; background:white;", "background:#568A02; font-size:8px; cursor:pointer;", "background:#FCAA4D; font-size:8px; cursor:pointer;");
        echo $chart;
      }

      echo '
      <div style="margin:60px 0px 0px 40px;">
        <div style="height:16px;"><div style="width:16px; height:16px; background:#568A02; float:left;"></div>&nbsp;Server load</div>
        <div style="height:16px; margin-top:2px;"><div style="width:16px; height:16px; background:#FCAA4D; float:left;"></div>&nbsp;Memory usage</div>
      </div>';
  
      echo "
      </div>\n";
    }
  }
}
?>