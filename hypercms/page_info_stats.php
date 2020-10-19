<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
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

// chart size in pixels
if (!empty ($is_mobile))
{
  $chart_width = 480;
  $chart_height = 280;
}
else
{
  $chart_width = 700;
  $chart_height = 400;
}

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
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script type="text/javascript" src="javascript/main.min.js"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
<script type="text/javascript">
function openPopup (link, title)
{
  if (link != "")
  {
    document.getElementById('popupTitle').innerHTML = title;
    document.getElementById('popupViewer').src = link;
    hcms_minMaxLayer('popupLayer');
  }
}

function closePopup ()
{
  document.getElementById('popupTitle').innerHTML = '';
  document.getElementById('popupViewer').src = '<?php echo $mgmt_config['url_path_cms']; ?>loading.php';
  hcms_minMaxLayer('popupLayer');
}
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- popup (do not used nested fixed positioned div-layers due to MS IE and Edge issue) -->
<div id="popupLayer" class="hcmsInfoBox" style="position:fixed; left:50%; bottom:0px; z-index:-1; overflow:hidden; width:0px; height:0px; visibility:hidden;">
  <div style="display:block; padding-bottom:5px;">
    <div id="popupTitle" class="hcmsHeadline" style="float:left; margin:6px;"></div>
    <div style="float:right;"><img name="closedailystatsviewer" src="<?php echo getthemelocation(); ?>img/button_close.png" onClick="closePopup();" class="hcmsButtonBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('closedailystatsviewer','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" /></div>
  </div>
  <div style="width:100%; height:calc(100% - 42px);">
    <iframe id="popupViewer" src="<?php echo $mgmt_config['url_path_cms']; ?>loading.php" style="width:100%; height:100%; border:1px solid #000000;"></iframe>
  </div>
</div>

<!-- top bar -->
<?php
echo showtopbar ($hcms_lang['access-statistics-for'][$lang]." ".$pagename, $lang, $mgmt_config['url_path_cms']."page_info.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame");
?>

<?php
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
<div class="hcmsWorkplaceFrame" style="width:<?php echo ($chart_width + 80); ?>px;">

  <div class="hcmsHeadline" style="width:240px; margin:8px auto 0px auto; text-align:center;">
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
    $result_view = rdbms_getmediastat ($date_from, $date_to, "view", "", $location_esc.$page, "", false);
    $result_download = rdbms_getmediastat ($date_from, $date_to, "download", "", $location_esc.$page, "", true);
    $result_upload = rdbms_getmediastat ($date_from, $date_to, "upload", "", $location_esc.$page, "", true);
  }
  elseif ($media != "")
  {
    $result_view = rdbms_getmediastat ($date_from, $date_to, "view", intval ($container_id), "", "", false);
    $result_download = rdbms_getmediastat ($date_from, $date_to, "download", intval ($container_id), "", "", true);
    $result_upload = rdbms_getmediastat ($date_from, $date_to, "upload", intval ($container_id), "", "", true);
  }
  else
  {
    $result_view = rdbms_getmediastat ($date_from, $date_to, "view", intval ($container_id), "", "", false);
    $result_download = rdbms_getmediastat ($date_from, $date_to, "download", intval ($container_id), "", "", false);
  }

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
            if (strpos ("|".$view_axis[$i]['onclick']."|", "|".intval ($row['container_id'])."|") === false && strlen ($view_axis[$i]['onclick']) < 2000) $view_axis[$i]['onclick'] .= intval ($row['container_id'])."|";
          }
          
          // total
          $view_total_count = $view_total_count + $row['count'];
        }
      }

      // link for popup
      if (!empty ($view_axis[$i]['onclick'])) $view_axis[$i]['onclick'] = "openPopup('".$mgmt_config['url_path_cms']."popup_gallery.php?container_id=".url_encode (trim ($view_axis[$i]['onclick'], "|"))."', '".showdate ($date_year."-".$date_month."-".$day, "Y-m-d", $hcms_lang_date[$lang]).getescapedtext (" ".$hcms_lang['views'][$lang])."');";

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
            if (strpos ("|".$download_axis[$i]['onclick']."|", "|".intval ($row['container_id'])."|") === false && strlen ($download_axis[$i]['onclick']) < 2000) $download_axis[$i]['onclick'] .= intval ($row['container_id'])."|";
          }
          
          // total
          $download_total_count = $download_total_count + $row['count'];
          $download_total_filesize = $download_total_filesize + $row['totalsize'];
        }
      }

      // link for popup
      if (!empty ($download_axis[$i]['onclick'])) $download_axis[$i]['onclick'] = "openPopup('".$mgmt_config['url_path_cms']."popup_gallery.php?container_id=".url_encode (trim ($download_axis[$i]['onclick'], "|"))."', '".showdate ($date_year."-".$date_month."-".$day, "Y-m-d", $hcms_lang_date[$lang]).getescapedtext (" ".$hcms_lang['downloads'][$lang])."');";

      // bar text
      $download_axis[$i]['text'] = $date_year."-".$date_month."-".$day."   \n".$download_axis[$i]['value']." ".getescapedtext ($hcms_lang['downloads'][$lang])."   \n".getescapedtext ($hcms_lang['users'][$lang]).": ".$download_axis[$i]['text'];
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
            if (strpos ("|".$upload_axis[$i]['onclick']."|", "|".intval ($row['container_id'])."|") === false && strlen ($upload_axis[$i]['onclick']) < 2000) $upload_axis[$i]['onclick'] .= intval ($row['container_id'])."|";
          }
     
          // total
          $upload_total_count = $upload_total_count + $row['count'];
          $upload_total_filesize = $upload_total_filesize + $row['totalsize'];
        }
      }

      // link for popup
      if (!empty ($upload_axis[$i]['onclick'])) $upload_axis[$i]['onclick'] = "openPopup('".$mgmt_config['url_path_cms']."popup_gallery.php?container_id=".url_encode (trim ($upload_axis[$i]['onclick'], "|"))."', '".showdate ($date_year."-".$date_month."-".$day, "Y-m-d", $hcms_lang_date[$lang]).getescapedtext (" ".$hcms_lang['uploads'][$lang])."');";

      // bar text
      $upload_axis[$i]['text'] = $date_year."-".$date_month."-".$day."   \n".$upload_axis[$i]['value']." ".getescapedtext ($hcms_lang['uploads'][$lang])."   \n".getescapedtext ($hcms_lang['users'][$lang]).": ".$upload_axis[$i]['text'];   
    }
  }
    
  if (is_array ($view_axis) || is_array ($download_axis) || is_array ($upload_axis))
  {
    $chart = buildbarchart ("chart", $chart_width, $chart_height, 10, 40, $date_axis, $view_axis, $download_axis, $upload_axis, "border:1px solid #666666; background:white;", "background:#6fae30; font-size:10px; cursor:pointer;", "background:#108ae7; font-size:10px; cursor:pointer;", "background:#ff8219; font-size:10px; cursor:pointer;");
    echo $chart;
  }
}
?>
  <div style="margin:30px 0px 0px 40px;">
    <div style="height:16px;"><div style="width:16px; height:16px; background:#6fae30; float:left;"></div>&nbsp;<?php echo getescapedtext ($hcms_lang['views'][$lang])." (".number_format ($view_total_count, 0, ".", " ")." Hits)"; ?></div>
    <div style="height:16px; margin-top:2px;"><div style="width:16px; height:16px; background:#108ae7; float:left;"></div>&nbsp;<?php echo getescapedtext ($hcms_lang['downloads'][$lang])." (".number_format ($download_total_count, 0, ".", " ")." Hits / ".number_format (($download_total_filesize / 1024), 0, ".", " ")." MB)"; ?></div>
    <div style="height:16px; margin-top:2px;"><div style="width:16px; height:16px; background:#ff8219; float:left;"></div>&nbsp;<?php echo getescapedtext ($hcms_lang['uploads'][$lang])." (".number_format ($upload_total_count, 0, ".", " ")." Hits / ".number_format (($upload_total_filesize / 1024), 0, ".", " ")." MB)"; ?></div>
  </div>
  
  <div style="margin:10px 0px 0px 40px;">
  <?php
  if ($page == ".folder")
  {
    $result_download = rdbms_getmediastat ($date_from, $date_to, "download", "", $location_esc.$page, "", false);
  }
  elseif ($media != "")
  {
    $result_download = rdbms_getmediastat ($date_from, $date_to, "download", intval ($container_id), "", "", false);
  }
  
  if (is_array ($result_download)) 
  { 
    echo "
    <table class=\"hcmsTableStandard\">
      <tr>
        <td class=\"hcmsHeadline\" style=\"width:150px; white-space:nowrap;\">".getescapedtext ($hcms_lang['download'][$lang])."</td>
        <td class=\"hcmsHeadline\" style=\"width:250px; white-space:nowrap;\">".getescapedtext ($hcms_lang['users'][$lang])."/IP </td>
        <td class=\"hcmsHeadline\" style=\"width:30px; white-space:nowrap;\">Hits</td>
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
        <tr class=\"".$rowcolor."\"><td>".$row['date']." </td><td>".$user." </td><td style=\"text-align:right;\">".$row['count']." </td></tr>";
    }
    
    echo "</table>";
  }
  ?>
  </div>
</div>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>