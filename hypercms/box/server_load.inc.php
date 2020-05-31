<?php
// ---------------------- STATS ---------------------
if (isset ($siteaccess) && is_array ($siteaccess))
{
  // language file
  require_once ("language/".getlanguagefile ($lang));
  
  // chart size in pixels
  if (!empty ($is_mobile))
  {
    $chart_width = 480;
    $chart_height = 220;
  }
  else
  {
    $chart_width = 600;
    $chart_height = 270;
  }
  
  // max history
  $maxcount = 30;
  
    // load log file
  $event_array = loadlog ("serverload");
  
  // collect log data
  if (!empty ($event_array) && is_array ($event_array) && sizeof ($event_array) > 0)
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
        
        $date_axis[$i] = "<div style=\"transform:rotate(-45deg); transform-origin:right bottom 0; margin:2px 0px 0px -15px;\">".showdate ($time, "H:i","H:i")."</div>";
        $load_axis[$i]['value'] = round ($load * 100);
        $load_axis[$i]['text'] = round ($load * 100)."%";
        $mem_axis[$i]['value'] = round ($memory * 100);
        $mem_axis[$i]['text'] = round ($memory * 100)."%";
      }
    }

    // display log data
    if (!empty ($date_axis) && !empty ($load_axis))
    {
      if (!empty ($is_mobile)) $width = "92%";
      else $width = "670px";
      
      $browserinfo = getbrowserinfo ();

      // MS IE or Edge does not support video blur if a reload is used
      if (!isset ($user_client['msie'])) echo "
      <script type=\"text/javascript\">
      setInterval (function() { window.location.reload(); }, 600000); 
      </script>";

      echo "
      <div id=\"stats_serverload\" class=\"hcmsHomeBox\" style=\"overflow:auto; margin:10px; width:".$width."; height:400px; float:left;\">
        <div class=\"hcmsHeadline\" style=\"margin:6px;\">Server load and memory usage</div>";
        
      if (is_array ($load_axis) || is_array ($mem_axis))
      {
        ksort ($date_axis);
        ksort ($load_axis);
        ksort ($mem_axis);
        
        $chart = buildbarchart ("chart", $chart_width, $chart_height, 8, 40, $date_axis, $load_axis, $mem_axis, "", "border:1px solid #666666; background:white;", "background:#568A02; font-size:8px; cursor:pointer;", "background:#FCAA4D; font-size:8px; cursor:pointer;");
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