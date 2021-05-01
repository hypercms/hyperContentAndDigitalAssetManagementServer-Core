<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */

// session
define ("SESSION", "create");
// management configuration
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");
// template engine
require ("../function/hypercms_tplengine.inc.php");


// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// chart size in pixels
if (!empty ($is_mobile))
{
  $chart_width = 480;
  $chart_height = 220;
}
else
{
  $chart_width = 600;
  $chart_height = 260;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=0.9, maximum-scale=1.0, user-scalable=0" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
</head>
<body>
<div class="hcmsTextWhite">
<?php
// ---------------------- STATS ---------------------
if (isset ($siteaccess) && is_array ($siteaccess))
{
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
      echo "
      <script type=\"text/javascript\">
      setInterval (function() { window.location.reload(); }, 300000); 
      </script>";
        
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
    }
  }
}
?>
</div>

<?php includefooter(); ?>

</body>
</html>