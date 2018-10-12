<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// template engine
require ("function/hypercms_tplengine.inc.php");


// input parameters
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$date_from = getrequest ("date_from");
$date_to = getrequest ("date_to");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);
if ($ownergroup == false || $setlocalpermission['root'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// get name 
$fileinfo = getfileinfo ($site, $location.$page, $cat);
$pagename = $fileinfo['name'];

// load page and read actual file info (to get associated template and content)
$pagedata = loadfile ($location, $page);

if ($pagedata != false)
{
  // get container
  $container = getfilename ($pagedata, "content");
  $media = getfilename ($pagedata, "media");
  $container_id = substr ($container, 0, strpos ($container, ".xml"));
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php
echo showtopbar ($hcms_lang['access-statistics-for'][$lang]." ".$pagename, $lang, $mgmt_config['url_path_cms']."page_info.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame");
?>

<?php
// define default date
if ($date_from == "" || $date_to == "")
{
  $date_from = date ("Y-m-01", time());
  $date_to = date ("Y-m-t", time());
  $date_year = date ("Y", time());
  $date_month = date ("m", time());
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

<!-- forms -->
<form name="previousform" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="page" value="<?php echo $page; ?>" />
  <input type="hidden" name="date_from" value="<?php echo $previous_date_from; ?>" />
  <input type="hidden" name="date_to" value="<?php echo $previous_date_to; ?>" />
</form>
<form name="nextform" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="page" value="<?php echo $page; ?>" />
  <input type="hidden" name="date_from" value="<?php echo $next_date_from; ?>" />
  <input type="hidden" name="date_to" value="<?php echo $next_date_to; ?>" />
</form>

<!-- content -->
<div class="hcmsWorkplaceFrame">

  <div class="hcmsHeadline" style="width:240px; padding:8px 0px 0px 290px; text-align:center;">
    <img src="<?php echo getthemelocation(); ?>img/button_arrow_left.png" class="hcmsButton hcmsButtonSizeSquare" onclick="document.forms['previousform'].submit();" alt="<?php echo getescapedtext ($hcms_lang['previous-month'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['previous-month'][$lang]); ?>" />
    <div style="float:left; width:140px; padding:2px;">&nbsp;<?php echo getescapedtext ($hcms_lang['time-frame'][$lang]).":<br />".$date_month."/".$date_year; ?>&nbsp;</div>
    <?php if ($date_month != date ("m", time()) || $date_year != date ("Y", time())) { ?>
    <img src="<?php echo getthemelocation(); ?>img/button_arrow_right.png" class="hcmsButton hcmsButtonSizeSquare" onclick="document.forms['nextform'].submit();" alt="<?php echo getescapedtext ($hcms_lang['next-month'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['next-month'][$lang]); ?>"/>
    <?php } else { ?>
    <img src="<?php echo getthemelocation(); ?>img/button_arrow_right.png" class="hcmsButtonOff hcmsButtonSizeSquare" />
    <?php } ?>
  </div>
  <div style="clear:both;"></div>

<?php 
// show results
// -------------------------- daily statistics --------------------------
if (!empty ($container_id))
{  
  if ($page == ".folder")
  {
    $result_view = rdbms_getmediastat ($date_from, $date_to, "view", "", $location_esc.$page, "");
    $result_download = rdbms_getmediastat ($date_from, $date_to, "download", "", $location_esc.$page, "");
    $result_upload = rdbms_getmediastat ($date_from, $date_to, "upload", "", $location_esc.$page, "");
  }
  elseif ($media != "")
  {
    $result_view = rdbms_getmediastat ($date_from, $date_to, "view", intval ($container_id), "", "");
    $result_download = rdbms_getmediastat ($date_from, $date_to, "download", intval ($container_id), "", "");
    $result_upload = rdbms_getmediastat ($date_from, $date_to, "upload", intval ($container_id), "", "");
  }
  else
  {
    $result_view = rdbms_getmediastat ($date_from, $date_to, "view", intval ($container_id), "", "", "object");
    $result_download = rdbms_getmediastat ($date_from, $date_to, "download", intval ($container_id), "", "", "object");
  }

  $date_axis = array();
  $view_axis = array();
  $download_axis = array();
  $upload_axis = array();
  $view_total_filesize = 0;
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
    
    if (isset ($result_view) && is_array ($result_view)) 
    { 
      foreach ($result_view as $row)
      {
        if ($row['date'] == $date_year."-".$date_month."-".$day)
        {
          if ($view_axis[$i]['text'] != "") $seperator = ", ";
          else $seperator = "";
   
          $view_axis[$i]['value'] = $view_axis[$i]['value'] + $row['count'];
          $view_axis[$i]['text'] = $view_axis[$i]['text'].$seperator.$row['user'];
          
          // total
          $view_total_count = $view_total_count + $row['count'];
          $view_total_filesize = $view_total_filesize + ($row['count'] * $row['filesize']);
        }
      }
      
      // bar text
      $view_axis[$i]['text'] = $date_year."-".$date_month."-".$day."   \n".$view_axis[$i]['value']." ".getescapedtext ($hcms_lang['views'][$lang])."   \n".getescapedtext ($hcms_lang['users'][$lang]).": ".$view_axis[$i]['text'];
    }

    // downloads
    $download_axis[$i]['value'] = 0;
    $download_axis[$i]['text'] = "";

    if (isset ($result_download) && is_array ($result_download)) 
    { 
      foreach ($result_download as $row)
      {
        if ($row['date'] == $date_year."-".$date_month."-".$day)
        {
          if ($download_axis[$i]['text'] != "") $seperator = ", ";
          else $seperator = "";
   
          $download_axis[$i]['value'] = $download_axis[$i]['value'] + $row['count'];
          $download_axis[$i]['text'] = $download_axis[$i]['text'].$seperator.$row['user'];
          
          // total
          $download_total_count = $download_total_count + $row['count'];
          $download_total_filesize = $download_total_filesize + ($row['count'] * $row['filesize']);
        }
      }
      
      // bar text
      $download_axis[$i]['text'] = $date_year."-".$date_month."-".$day."   \n".$download_axis[$i]['value']." ".getescapedtext ($hcms_lang['downloads'][$lang])."   \n".getescapedtext ($hcms_lang['users'][$lang]).": ".$download_axis[$i]['text'];
    }
    
    // uploads
    $upload_axis[$i]['value'] = 0;
    $upload_axis[$i]['text'] = "";
      
    if (isset ($result_upload) && is_array ($result_upload)) 
    {
      foreach ($result_upload as $row)
      {
        if ($row['date'] == $date_year."-".$date_month."-".$day)
        {
          if ($upload_axis[$i]['text'] != "") $seperator = ", ";
          else $seperator = "";
                  
          $upload_axis[$i]['value'] = $upload_axis[$i]['value'] + $row['count'];
          $upload_axis[$i]['text'] = $upload_axis[$i]['text'].$seperator.$row['user'];
     
          // total
          $upload_total_count = $upload_total_count + $row['count'];
          $upload_total_filesize = $upload_total_filesize + ($row['count'] * $row['filesize']);
        }
      }
      
      // bar text
      $upload_axis[$i]['text'] = $date_year."-".$date_month."-".$day."   \n".$upload_axis[$i]['value']." ".getescapedtext ($hcms_lang['uploads'][$lang])."   \n".getescapedtext ($hcms_lang['users'][$lang]).": ".$upload_axis[$i]['text'];   
    }
  }
    
  if (is_array ($download_axis) || is_array ($upload_axis))
  {
    $chart = buildbarchart ("chart", 700, 400, 10, 40, $date_axis, $view_axis, $download_axis, $upload_axis, "border:1px solid #666666; background:white;", "background:#6fae30; font-size:10px; cursor:pointer;", "background:#108ae7; font-size:10px; cursor:pointer;", "background:#ff8219; font-size:10px; cursor:pointer;");
    echo $chart;
  }
}
?>
  <div style="margin:30px 0px 0px 40px;">
    <div style="height:16px;"><div style="width:16px; height:16px; background:#6fae30; float:left;"></div>&nbsp;<?php echo getescapedtext ($hcms_lang['views'][$lang])." (".number_format ($view_total_count, 0, "", ".")." Hits / ".number_format (($view_total_filesize / 1024), 0, "", ".")." MB)"; ?></div>
    <div style="height:16px; margin-top:2px;"><div style="width:16px; height:16px; background:#108ae7; float:left;"></div>&nbsp;<?php echo getescapedtext ($hcms_lang['downloads'][$lang])." (".number_format ($download_total_count, 0, "", ".")." Hits / ".number_format (($download_total_filesize / 1024), 0, "", ".")." MB)"; ?></div>
    <div style="height:16px; margin-top:2px;"><div style="width:16px; height:16px; background:#ff8219; float:left;"></div>&nbsp;<?php echo getescapedtext ($hcms_lang['uploads'][$lang])." (".number_format ($upload_total_count, 0, "", ".")." Hits / ".number_format (($upload_total_filesize / 1024), 0, "", ".")." MB)"; ?></div>
  </div>
  
  <div style="margin:10px 0px 0px 40px;">
  <?php
  if (is_array ($result_download)) 
  { 
    echo "
    <table border=\"0\" celspacing=\"2\" cellpadding=\"1\">
      <tr>
        <td class=\"hcmsHeadline\" width=\"150\" nowrap=\"nowrap\">".getescapedtext ($hcms_lang['download'][$lang])."</td>
        <td class=\"hcmsHeadline\" width=\"250\" nowrap=\"nowrap\">".getescapedtext ($hcms_lang['users'][$lang])."/IP </td>
        <td class=\"hcmsHeadline\" width=\"80\" nowrap=\"nowrap\">Hits</td>
      </tr>";
  
    $color = false;
    
    foreach ($result_download as $row)
    {
      // define row color
      if ($color == true)
      {
        $rowcolor = "hcmsRowData1";
        $color = false;
      }
      else
      {
        $rowcolor = "hcmsRowData2";
        $color = true;
      }
      
      // ip address
      if (substr_count ($row['user'], ".") >= 3)
      {
        $ip_array = array();
        $user_array = array();
        
        if (strpos ($row['user'], ",") > 0) $ip_array = explode (",", $row['user']);
        else $ip_array[] = trim ($row['user']);
        
        foreach ($ip_array as $ip)
        {
          if (substr_count ($ip, ".") == 3)
          {
            $user_array[] = "<div style=\"cursor:pointer; color:green; float:left;\" onclick=\"parent.opengeoview('".trim($ip)."');\">".$ip."</div>";
          }
          elseif ($ip != "") $user_array[] = trim($ip);
        }
        
        if (sizeof ($user_array) > 0) $user = implode ("<div style=\"float:left;\">, </div>", $user_array);
        else $user = "";
      }
      // user name
      else $user = $row['user'];
      
      echo "
        <tr class=\"".$rowcolor."\"><td>".$row['date']." </td><td>".$user." </td><td>".$row['count']." </td></tr>";
    }
    
    echo "</table>";
  }
  ?>
  </div>
  
</div>

</body>
</html>
