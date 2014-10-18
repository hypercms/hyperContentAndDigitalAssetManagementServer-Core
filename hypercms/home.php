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

<body class="hcmsWorkplaceGeneric" onload="<?php if (empty ($_SESSION['hcms_temp_latitude']) || empty ($_SESSION['hcms_temp_longitude'])) echo "hcms_geolocation();"; ?>">

<div class="hcmsWorkplaceFrame">

  <div id="logo" style="margin:10px; display:block;"><img src="<?php echo getthemelocation(); ?>img/logo_welcome.gif" style="width:<?php if ($is_mobile) echo "260px"; else echo "320px"; ?>" /> <?php echo $version; ?></div>

  <?php
  if ($mgmt_config['welcome'] != "") 
  {
    if ($is_mobile) $width = "92%";
    else $width = "660";
    
    echo "<iframe width=\"".$width."\" height=\"400\" src=\"".$mgmt_config['welcome']."\" scrolling=\"yes\" class=\"hcmsInfoBox\" style=\"margin:5px; float:left;\" seamless=\"seamless\"></iframe>\n";
  }
  ?>
  
  <?php
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
        echo "<div id=\"task\" onclick=\"document.location.href='task_list.php';\" class=\"hcmsInfoBox\" style=\"overflow:auto; margin:5px; width:".$width."; height:400px; float:left; cursor:pointer;\">\n";

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
  $object_array = rdbms_searchuser ("", $user);
  
  if (is_array ($object_array) && sizeof ($object_array) > 0)
  {
    if ($is_mobile) $width = "92%";
    else $width = "320px";
    
    echo "
    <div id=\"recent\" class=\"hcmsInfoBox\" style=\"margin:5px; width:".$width."; height:400px; float:left;\">
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
  </div>
</div>

</body>
</html>