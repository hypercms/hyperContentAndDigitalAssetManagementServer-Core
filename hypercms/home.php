<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("include/session.inc.php");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");
// template engine
require ("function/hypercms_tplengine.inc.php");
// version info
require ("version.inc.php");
// language file
require_once ("language/home.inc.php");

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<meta name="viewport" content="width=device-width; initial-scale=0.9; maximum-scale=1.0; user-scalable=0;"></meta>
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/jquery/jquery-1.9.1.min.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
<!--
// callback for hcms_geolocation
function hcms_geoposition (position)
{
  if (position)
  {
    var latitude = position.coords.latitude;
    var longitude = position.coords.longitude;
  }
  else return false;
  
  if (latitude != "" && longitude != "")
  {
    // AJAX request to set geo location
    $.post("<?php echo $mgmt_config['url_path_cms']; ?>/service/setgeolocation.php", {latitude: latitude, longitude: longitude});

    return true;
  }
  else return false;
}
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric" style="width:100%; height:100%;" onload="<?php if (empty ($_SESSION['hcms_temp_latitude']) || empty ($_SESSION['hcms_temp_longitude'])) echo "hcms_geolocation();"; ?>">

<div style="width:100%; height:100%; overflow:auto; display:block;">

  <div id="logo" style="position:fixed; top:10px; left:10px; display:block;">
    <img src="<?php echo getthemelocation(); ?>img/logo_welcome.gif" style="width:<?php if ($is_mobile) echo "260px"; else echo "320px"; ?>" />
    <div id="version" style="position:fixed; top:45px; left:240px;"><?php echo $version; ?></div>
  </div>
  
  <div id="spacer" style="width:94%; height:70px; display:block;"></div>

  <?php
  // ---------------------- NEWS / WELCOME BOX ---------------------
  if ($mgmt_config['welcome'] != "") 
  {
    if ($is_mobile) $width = "92%";
    else $width = "670";
    
    echo "<iframe width=\"".$width."\" height=\"400\" src=\"".$mgmt_config['welcome']."\" scrolling=\"yes\" class=\"hcmsInfoBox\" style=\"margin:10px; float:left;\" seamless=\"seamless\"></iframe>\n";
  }
  ?>
  
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

        echo "<div class=\"hcmsHeadline\" style=\"margin:2px;\">".$text0[$lang]."</div>
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
  
  <?php
  // ---------------------- RECENT OBJECTS ---------------------
  $object_array = rdbms_searchuser ("", $user);
  
  if (is_array ($object_array) && sizeof ($object_array) > 0)
  {
    if ($is_mobile) $width = "92%";
    else $width = "320px";
    
    echo "
    <div id=\"recent\" class=\"hcmsInfoBox\" style=\"margin:10px; width:".$width."; height:400px; float:left;\">
      <div class=\"hcmsHeadline\" style=\"margin:2px;\">".$text1[$lang]."</div>";
    
    array_reverse ($object_array);
    reset ($object_array);
    $i = 0;
    
    foreach ($object_array as $hash => $objectpath)
    {
      // show only object items
      if (getobject ($objectpath) != ".folder" && $i < 20)
      {
        // get site
        $item_site = getpublication ($objectpath);        
        // get category
        $item_cat = getcategory ($item_site, $objectpath); 
        
        if (valid_publicationname ($item_site) && $item_cat != "")
        {
          // get location
          $item_location_esc = getlocation ($objectpath);
          // get location in file system
          $item_location = deconvertpath ($item_location_esc, "file");           
          // get location name
          $item_locationname = getlocationname ($item_site, $item_location_esc, $item_cat, "path");        
          // get object name
          $item_object = getobject ($objectpath);  
          $item_object = correctfile ($item_location, $item_object, $user); 
          $item_fileinfo = getfileinfo ($item_site, $item_location.$item_object, $item_cat);
          $item_objectinfo = getobjectinfo ($item_site, $item_location, $item_object, $user);
          
          // check access permission
          $ownergroup = accesspermission ($item_site, $item_location, $item_cat);
          $setlocalpermission = setlocalpermission ($item_site, $ownergroup, $item_cat);
          
          if ($ownergroup != false && $setlocalpermission['root'] == 1 && valid_locationname ($item_location) && valid_objectname ($item_object))
          {
            // open on double click
            $openObject = "onClick=\"window.open('frameset_content.php?ctrlreload=yes&site=".url_encode($item_site)."&cat=".url_encode($item_cat)."&location=".url_encode($item_location_esc)."&page=".url_encode($item_object)."','".$item_objectinfo['container_id']."','status=yes,scrollbars=no,resizable=yes,width=800,height=600');\"";
          
            echo "
            <div ".$openObject." style=\"display:block; cursor:pointer;\" title=\"".$item_locationname.$item_fileinfo['name']."\"><img src=\"".getthemelocation()."img/".$item_fileinfo['icon']."\" align=\"absmiddle\" class=\"hcmsIconList\" />&nbsp;".showshorttext($item_fileinfo['name'], 30)."&nbsp;</div>";
            $i++;
          }
        }
      }
    }
    
    echo "
    </div>\n";
  }
  ?>
  
  <?php
  // ---------------------- STATS ---------------------
  if (!$is_mobile && isset ($mgmt_config['home_stats']) && $mgmt_config['home_stats'] == true && isset ($siteaccess) && is_array ($siteaccess))
  {
    $title = $text2[$lang];
    
    // language file
    require_once ("language/page_info_stats.inc.php");

    foreach ($siteaccess as $item_site)
    {
      // publication management config
      if (valid_publicationname ($item_site)) require ($mgmt_config['abs_path_data']."config/".$item_site.".conf.php");

      if (isset ($mgmt_config[$item_site]['dam']) && $mgmt_config[$item_site]['dam'] == true)
      {
        if ($is_mobile) $width = "92%";
        else $width = "670px";
        
        echo "
        <div id=\"stats_".$item_site."\" class=\"hcmsInfoBox\" style=\"overflow:auto; margin:10px; width:".$width."; height:400px; float:left;\">
          <div class=\"hcmsHeadline\" style=\"margin:2px;\">".$title." ".$item_site."</div>";
          
        $rootlocation_esc = "%comp%/".$item_site."/.folder";
          
        if (!empty ($rootlocation_esc))
        {
          $date_from = date ("Y-m-01", time());
          $date_to = date ("Y-m-t", time());
          $date_year = date ("Y", time());
          $date_month = date ("m", time());
                
          $result_download = rdbms_getmediastat ($date_from, $date_to, "download", "", $rootlocation_esc, "");
          $result_upload = rdbms_getmediastat ($date_from, $date_to, "upload", "", $rootlocation_esc, "");
          
          $date_axis = array();
          $download_axis = array();
          $upload_axis = array();
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
        
            // downloads
            $download_axis[$i]['value'] = 0;
            $download_axis[$i]['text'] = "";
        
            if (is_array ($result_download)) 
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
              $download_axis[$i]['text'] = $date_year."-".$date_month."-".$day."   \n".$download_axis[$i]['value']." ".$text4[$lang]."   \n".$text6[$lang].": ".$download_axis[$i]['text'];
            }
            
            // uploads
            $upload_axis[$i]['value'] = 0;
            $upload_axis[$i]['text'] = "";
              
            if (is_array ($result_upload)) 
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
              $upload_axis[$i]['text'] = $date_year."-".$date_month."-".$day."   \n".$upload_axis[$i]['value']." ".$text5[$lang]."   \n".$text6[$lang].": ".$upload_axis[$i]['text'];   
            }
          }
            
          if (is_array ($download_axis) || is_array ($upload_axis))
          {
            $chart = buildbarchart ("chart", 600, 300, 8, 40, $date_axis, $download_axis, $upload_axis, "", "border:1px solid #666666; background:white;", "background:#3577ce; font-size:8px; cursor:pointer;", "background:#ff8219; font-size:8px; cursor:pointer;", "background:#73bd73; font-size:8px; cursor:pointer;");
            echo $chart;
          }
        }

        echo '
        <div style="margin:35px 0px 0px 40px;">
          <div style="height:16px;"><div style="width:16px; height:16px; background:#3577ce; float:left;"></div>&nbsp;'.$text4[$lang].' ('.number_format ($download_total_count, 0, "", ".").' Hits / '.number_format (($download_total_filesize / 1024), 0, "", ".").' MB)</div>
          <div style="height:16px; margin-top:2px;"><div style="width:16px; height:16px; background:#ff8219; float:left;"></div>&nbsp;'.$text5[$lang]." (".number_format ($upload_total_count, 0, "", ".").' Hits / '.number_format (($upload_total_filesize / 1024), 0, "", ".").' MB)</div>
        </div>';
    
        echo "
        </div>\n";
      }
    }
  }
  ?>

</div>

</body>
</html>