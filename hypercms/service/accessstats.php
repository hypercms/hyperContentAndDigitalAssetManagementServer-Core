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
$location_esc = getrequest ("location", "locationname");
$date_from = getrequest ("date_from");
$date_to = getrequest ("date_to");

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

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

// define default date
if ($date_from == "" || $date_to == "")
{
  if (!empty ($_SESSION['hcms_timezone'])) date_default_timezone_set ($_SESSION['hcms_timezone']);
  $time = time();
  $date_from = date ("Y-m-01", $time);
  $date_to = date ("Y-m-t", $time);
  $date_year = date ("Y", $time);
  $date_month = date ("m", $time);
}
else
{
  list ($date_year, $date_month, $date_day) = explode ("-", $date_from);
}

// define previous and next dates
$previous_date_from = date ("Y-m-01", strtotime ("-1 month", strtotime ($date_from)));
$previous_date_to = date ("Y-m-t", strtotime ("-1 month", strtotime ($date_from)));
$next_date_from = date ("Y-m-01", strtotime ("+1 month", strtotime ($date_from)));
$next_date_to = date ("Y-m-t", strtotime ("+1 month", strtotime ($date_from)));
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=0.9, maximum-scale=1.0, user-scalable=0" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script>
function previous ()
{
  document.getElementById('hcmsLoadScreen').style.display='inline';
  document.forms['previousform'].submit();
}

function next ()
{
  document.getElementById('hcmsLoadScreen').style.display='inline';
  document.forms['nextform'].submit();
}
</script>
</head>

<body>

<!-- load screen --> 
<div id="hcmsLoadScreen" class="hcmsLoadScreen" style="background-color:transparent;"></div>

<div class="hcmsTextWhite">

<!-- navigation forms -->
<form name="previousform" action="" method="post">
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="date_from" value="<?php echo $previous_date_from; ?>" />
  <input type="hidden" name="date_to" value="<?php echo $previous_date_to; ?>" />
</form>
<form name="nextform" action="" method="post">
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="date_from" value="<?php echo $next_date_from; ?>" />
  <input type="hidden" name="date_to" value="<?php echo $next_date_to; ?>" />
</form>

<div class="hcmsHeadline" style="width:150px; margin:0px auto; text-align:center; white-space:nowrap;">
  <img src="<?php echo getthemelocation("night"); ?>img/button_arrow_left.png" class="hcmsButton hcmsIconList" onclick="previous();" alt="<?php echo getescapedtext ($hcms_lang['previous-month'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['previous-month'][$lang]); ?>" />
  <div style="float:left; width:100px; padding:2px; text-align:center;">&nbsp;<?php echo $date_month."/".$date_year; ?>&nbsp;</div>
  <?php if ($date_month != date ("m", time()) || $date_year != date ("Y", time())) { ?>
  <img src="<?php echo getthemelocation("night"); ?>img/button_arrow_right.png" class="hcmsButton hcmsIconList" onclick="next();" alt="<?php echo getescapedtext ($hcms_lang['next-month'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['next-month'][$lang]); ?>"/>
  <?php } else { ?>
  <img src="<?php echo getthemelocation("night"); ?>img/button_arrow_right.png" class="hcmsButtonOff hcmsIconList" />
  <?php } ?>
</div>
<div style="clear:both;"></div>

<!-- chart -->
<?php
if (!empty ($location_esc))
{
  $page = getobject ($location_esc);
  
  $result_view = rdbms_getmediastat ($date_from, $date_to, "view", "", $location_esc, "", false);
  $result_download = rdbms_getmediastat ($date_from, $date_to, "download", "", $location_esc, "", true);
  $result_upload = rdbms_getmediastat ($date_from, $date_to, "upload", "", $location_esc, "", true);
  
  $date_axis = array();
  $view_axis = array();
  $download_axis = array();
  $upload_axis = array();
  $view_total_count = 0;
  $download_total_filesize = 0;
  $download_total_count = 0;
  $upload_total_filesize = 0;
  $upload_total_count = 0;

  // loop through days of month
  for ($i=1; $i<=date("t", strtotime($date_from)); $i++)
  {
    $date_axis[$i] = $i;
    
    if (strlen ($i) == 1) $day = "0".$i;
    else $day = $i;
          
    // views
    $view_axis[$i]['value'] = 0;
    $view_axis[$i]['text'] = "";
    $view_axis[$i]['onclick'] = "";
    
    if (isset ($result_view) && is_array ($result_view)) 
    {
      // collect data for same day
      foreach ($result_view as $row)
      {
        if ($row['date'] == $date_year."-".$date_month."-".$day)
        {
          if ($view_axis[$i]['text'] != "") $delimiter = ", ";
          else $delimiter = "";
   
          $view_axis[$i]['value'] = $view_axis[$i]['value'] + $row['count'];
          if (strpos (" ".$view_axis[$i]['text'].",", " ".$row['user'].",") === false) $view_axis[$i]['text'] .= $delimiter.$row['user'];

          // container ID for link
          if ($page == ".folder")
          {
            if (strpos ("|".$view_axis[$i]['onclick']."|", "|".intval ($row['container_id'])."|") === false && strlen ($view_axis[$i]['onclick']) < 2000)
            {
              $view_axis[$i]['onclick'] .= intval ($row['container_id'])."|";
            }
          }
          
          // total
          $view_total_count = $view_total_count + $row['count'];
        }
      }

      // link for popup
      if (!empty ($view_axis[$i]['onclick'])) $view_axis[$i]['onclick'] = "window.parent.openPopup('".cleandomain ($mgmt_config['url_path_cms'])."popup_gallery.php?container_id=".url_encode (trim ($view_axis[$i]['onclick'], "|"))."', '".showdate ($date_year."-".$date_month."-".$day, "Y-m-d", $hcms_lang_date[$lang]).getescapedtext (" ".$hcms_lang['views'][$lang])."');";

      // bar text
      $view_axis[$i]['text'] = $date_year."-".$date_month."-".$day."   \n".$view_axis[$i]['value']." ".getescapedtext ($hcms_lang['views'][$lang])."   \n".getescapedtext ($hcms_lang['users'][$lang]).": ".$view_axis[$i]['text'];
    }

    // downloads
    $download_axis[$i]['value'] = 0;
    $download_axis[$i]['text'] = "";
    $download_axis[$i]['onclick'] = "";

    if (isset ($result_download) && is_array ($result_download)) 
    {
      // collect data for same day
      foreach ($result_download as $row)
      {
        if ($row['date'] == $date_year."-".$date_month."-".$day)
        {
          if ($download_axis[$i]['text'] != "") $delimiter = ", ";
          else $delimiter = "";
   
          $download_axis[$i]['value'] = $download_axis[$i]['value'] + $row['count'];
          if (strpos (" ".$download_axis[$i]['text'].",", " ".$row['user'].",") === false) $download_axis[$i]['text'] .= $delimiter.$row['user'];

          // container ID for link
          if ($page == ".folder")
          {
            if (strpos ("|".$download_axis[$i]['onclick']."|", "|".intval ($row['container_id'])."|") === false && strlen ($download_axis[$i]['onclick']) < 2000)
            {
              $download_axis[$i]['onclick'] .= intval ($row['container_id'])."|";
            }
          }
          
          // total
          $download_total_count = $download_total_count + $row['count'];
          $download_total_filesize = $download_total_filesize + $row['totalsize'];
        }
      }

      // link for popup
      if (!empty ($download_axis[$i]['onclick'])) $download_axis[$i]['onclick'] = "window.parent.openPopup('".cleandomain ($mgmt_config['url_path_cms'])."popup_gallery.php?container_id=".url_encode (trim ($download_axis[$i]['onclick'], "|"))."', '".showdate ($date_year."-".$date_month."-".$day, "Y-m-d", $hcms_lang_date[$lang]).getescapedtext (" ".$hcms_lang['downloads'][$lang])."');";

      // bar text
      $download_axis[$i]['text'] = showdate ($date_year."-".$date_month."-".$day, "Y-m-d", $hcms_lang_date[$lang])."   \n".$download_axis[$i]['value']." ".$hcms_lang['downloads'][$lang]."   \n".$hcms_lang['users'][$lang].": ".$download_axis[$i]['text'];
    }
    
    // uploads
    $upload_axis[$i]['value'] = 0;
    $upload_axis[$i]['text'] = "";
    $upload_axis[$i]['onclick'] = "";
      
    if (isset ($result_upload) && is_array ($result_upload)) 
    {
      // collect data for same day
      foreach ($result_upload as $row)
      {
        if ($row['date'] == $date_year."-".$date_month."-".$day)
        {
          if ($upload_axis[$i]['text'] != "") $delimiter = ", ";
          else $delimiter = "";
                  
          $upload_axis[$i]['value'] = $upload_axis[$i]['value'] + $row['count'];
          if (strpos (" ".$upload_axis[$i]['text'].",", " ".$row['user'].",") === false) $upload_axis[$i]['text'] .= $delimiter.$row['user'];

          // container ID for link
          if ($page == ".folder")
          {
            if (strpos ("|".$upload_axis[$i]['onclick']."|", "|".intval ($row['container_id'])."|") === false && strlen ($upload_axis[$i]['onclick']) < 2000)
            {
              $upload_axis[$i]['onclick'] .= intval ($row['container_id'])."|";
            }
          }

          // total
          $upload_total_count = $upload_total_count + $row['count'];
          $upload_total_filesize = $upload_total_filesize + $row['totalsize'];
        }
      }

      // link for popup
      if (!empty ($upload_axis[$i]['onclick'])) $upload_axis[$i]['onclick'] = "window.parent.openPopup('".cleandomain ($mgmt_config['url_path_cms'])."popup_gallery.php?container_id=".url_encode (trim ($upload_axis[$i]['onclick'], "|"))."', '".showdate ($date_year."-".$date_month."-".$day, "Y-m-d", $hcms_lang_date[$lang]).getescapedtext (" ".$hcms_lang['uploads'][$lang])."');";

      // bar text
      $upload_axis[$i]['text'] = showdate ($date_year."-".$date_month."-".$day, "Y-m-d", $hcms_lang_date[$lang])."   \n".$upload_axis[$i]['value']." ".$hcms_lang['uploads'][$lang]."   \n".$hcms_lang['users'][$lang].": ".$upload_axis[$i]['text'];   
    }
  }
    
  if (is_array ($view_axis) || is_array ($download_axis) || is_array ($upload_axis))
  {
    $chart = buildbarchart ("chart", $chart_width, $chart_height, 8, 40, $date_axis, $view_axis, $download_axis, $upload_axis, "border:1px solid #666666; background:white; font-size:10px;", "background:#6fae30; font-size:10px; cursor:pointer;", "background:#108ae7; font-size:10px; cursor:pointer;", "background:#ff8219; font-size:10px; cursor:pointer;");
    echo $chart;
  }

  echo '
  <div style="margin:35px 0px 0px 40px;">
    <div style="height:16px; white-space:nowrap;"><div style="width:16px; height:16px; background:#6fae30; float:left;"></div>&nbsp;'.getescapedtext ($hcms_lang['views'][$lang]).' ('.number_format ($view_total_count, 0, ".", " ").' Hits)</div>
    <div style="height:16px; margin-top:2px; white-space:nowrap;"><div style="width:16px; height:16px; background:#108ae7; float:left;"></div>&nbsp;'.getescapedtext ($hcms_lang['downloads'][$lang]).' ('.number_format ($download_total_count, 0, ".", " ").' Hits / '.number_format (($download_total_filesize / 1024), 0, ".", " ").' MB)</div>
    <div style="height:16px; margin-top:2px; white-space:nowrap;"><div style="width:16px; height:16px; background:#ff8219; float:left;"></div>&nbsp;'.getescapedtext ($hcms_lang['uploads'][$lang])." (".number_format ($upload_total_count, 0, ".", " ").' Hits / '.number_format (($upload_total_filesize / 1024), 0, ".", " ").' MB)</div>
  </div>';
}
?>
</div>

<?php includefooter(); ?>

</body>
</html>