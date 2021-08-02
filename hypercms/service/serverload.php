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

// input parameters
$start = getrequest ("start", "numeric", 0);

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// max records to display from log for paging
$paging = 30;

// chart size in pixels
if (!empty ($is_mobile))
{
  $chart_width = 300;
  $chart_height = 190;
}
else
{
  $chart_width = 600;
  $chart_height = 230;
}

// write and close session (non-blocking other frames)
if (session_id() != "") session_write_close();

if (isset ($siteaccess) && is_array ($siteaccess))
{ 
  // load log file
  $event_array = loadlog ("serverload");

  // number of log entries
  $counter_max = sizeof ($event_array);

  // collect log data for chart
  if (!empty ($event_array) && is_array ($event_array) && sizeof ($event_array) > 0)
  {
    // reverse array
    $event_array = array_reverse ($event_array);

    $i = $paging;
    $count = 0;
    $date_display = "";
    $date_axis = array();
    $load_axis = array();
    $mem_axis = array();
    
    foreach ($event_array as $event)
    {
      if ($event != "" && $count >= $start && $count <= ($start + $paging))
      {
        list ($datetime, $load, $cpu, $memory) = explode ("|", $event);
        
        list ($date, $time) = explode (" ", $datetime);
        if ($load > 1) $load = 1;

        if (empty ($date_display)) $date_display = $date;
        
        $date_axis[$i] = showdate ($time, "H:i", "H:i");
        $load_axis[$i]['value'] = round ($load * 100);
        $load_axis[$i]['text'] = round ($load * 100)."%";
        $mem_axis[$i]['value'] = round ($memory * 100);
        $mem_axis[$i]['text'] = round ($memory * 100)."%";
        $i--;
      }

      $count++;
    }
  }
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
<script>
function previous ()
{
  document.getElementById('hcmsLoadScreen').style.display='inline';
  location.href = '?start=<?php echo intval ($start + $paging); ?>';
}

function next ()
{
  document.getElementById('hcmsLoadScreen').style.display='inline';
  location.href = '?start=<?php echo intval ($start - $paging); ?>';
}
</script>
<body>

<!-- load screen --> 
<div id="hcmsLoadScreen" class="hcmsLoadScreen" style="background-color:transparent;"></div>

<div class="hcmsTextWhite">

<!-- navigation -->
<div class="hcmsHeadline" style="width:220px; margin:0px auto; text-align:center; white-space:nowrap;">
  <?php if (($start + $paging) < $counter_max) { ?>
  <img src="<?php echo getthemelocation("night"); ?>img/button_arrow_left.png" class="hcmsButton hcmsIconList" onclick="previous();" alt="<?php echo getescapedtext ($hcms_lang['back'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['back'][$lang]); ?>" />
  <?php } else { ?>
  <img src="<?php echo getthemelocation("night"); ?>img/button_arrow_left.png" class="hcmsButtonOff hcmsIconList" />
  <?php } ?>
  <div style="float:left; width:170px; padding:2px; text-align:center;">&nbsp;<?php echo showdate ($date_display, "Y-m-d", $hcms_lang_date[$lang]); ?>&nbsp;</div>
  <?php if ($start > 0) { ?>
  <img src="<?php echo getthemelocation("night"); ?>img/button_arrow_right.png" class="hcmsButton hcmsIconList" onclick="next();" alt="<?php echo getescapedtext ($hcms_lang['forward'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['forward'][$lang]); ?>"/>
  <?php } else { ?>
  <img src="<?php echo getthemelocation("night"); ?>img/button_arrow_right.png" class="hcmsButtonOff hcmsIconList" />
  <?php } ?>
</div>
<div style="clear:both;"></div>

<?php
// ---------------------- STATS ---------------------

// display log data
if (!empty ($date_axis) && !empty ($load_axis))
{
  // reload stats
  if ($start == 0) echo "
  <script type=\"text/javascript\">
  setInterval (function() { window.location.reload(); }, 300000); 
  </script>";
    
  if (is_array ($load_axis) || is_array ($mem_axis))
  {
    ksort ($date_axis);
    ksort ($load_axis);
    ksort ($mem_axis);
    
    $chart = buildbarchart ("chart", $chart_width, $chart_height, 8, 40, $date_axis, $load_axis, $mem_axis, "", "border:1px solid #666666; background:white; font-size:10px;", "background:#568A02; font-size:10px; cursor:pointer;", "background:#FCAA4D; font-size:10px; cursor:pointer;", "", false, 100);
    echo $chart;
  }

  echo '
  <div style="margin:60px 0px 0px 40px;">
    <div style="height:16px;"><div style="width:16px; height:16px; background:#568A02; float:left;"></div>&nbsp;Server load</div>
    <div style="height:16px; margin-top:2px;"><div style="width:16px; height:16px; background:#FCAA4D; float:left;"></div>&nbsp;Memory usage</div>
  </div>';
}
?>
</div>

<?php includefooter(); ?>

</body>
</html>