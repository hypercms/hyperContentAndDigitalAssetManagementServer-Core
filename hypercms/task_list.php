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


// input parameters
$action = getrequest ("action");
$site = getrequest_esc ("site", "publicationname");
$delete_id = getrequest ("delete_id", "array");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('desktop') || !checkrootpermission ('desktoptaskmgmt') || !valid_objectname ($user)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";

// delete tasks
if (checkrootpermission ('desktoptaskmgmt') && is_array ($delete_id) && $action == "task_delete" && checktoken ($token, $user)) 
{
  $result = deletetask ($user, $delete_id);
  
  $add_onload = $result['add_onload'];
  $show = $result['message'];  
}

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric" leftmargin=2 topmargin=2 marginwidth=0 marginheight=0 onLoad="hcms_preloadImages('<?php echo getthemelocation(); ?>img/button_OK_over.gif')">

<?php
echo showmessage ($show, 500, 70, $lang, "position:fixed; left:50px; top:100px;")
?>

<!-- top bar -->
<?php echo showtopbar ($hcms_lang['task-management'][$lang], $lang); ?>

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

  <div id="scrollFrame" style="width:98%; height:90%; overflow:auto;">
  <form name="taskform" action="" method="post">
    <input type="hidden" name="action" value="task_delete" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
    <table width="98%" border="0" cellspacing="2" cellpadding="3">
    <?php
    //load task file and get all task entries
    $task_data = loadlockfile ($user, $mgmt_config['abs_path_data']."task/", $user.".xml.php");
    
    // get all tasks
    if ($task_data != "") $task_array = getcontent ($task_data, "<task>");

    $savetasklist = false;
    $j = 1;

    if (isset ($task_array) && is_array ($task_array) && sizeof ($task_array) > 0)
    {
      echo "<tr>
        <td valign=\"top\" class=\"hcmsHeadline\" width=\"10\" nowrap=\"nowrap\">".$hcms_lang['no'][$lang]."</td>
        <td valign=\"top\" class=\"hcmsHeadline\" width=\"80\" nowrap=\"nowrap\">".$hcms_lang['date'][$lang]."</td>
        <td valign=\"top\" class=\"hcmsHeadline\" width=\"80\" nowrap=\"nowrap\">".$hcms_lang['name'][$lang]."</td>
        <td valign=\"top\" class=\"hcmsHeadline\" width=\"100\" nowrap=\"nowrap\">".$hcms_lang['location'][$lang]."</td>
        <td valign=\"top\" class=\"hcmsHeadline\" width=\"80\" nowrap=\"nowrap\">".$hcms_lang['publication'][$lang]."</td>
        <td valign=\"top\" class=\"hcmsHeadline\">".$hcms_lang['description'][$lang]."</td>
        <td valign=\"top\" class=\"hcmsHeadline\" width=\"80\" nowrap=\"nowrap\">".$hcms_lang['category'][$lang]."</td>
        <td valign=\"top\" class=\"hcmsHeadline\" width=\"80\" nowrap=\"nowrap\">".$hcms_lang['priority'][$lang]."</td>
        <td width=\"10\" class=\"hcmsHeadline\">".$hcms_lang['done'][$lang]."</td>
      </tr>\n";
      
      $site_memory = "";

      foreach ($task_array as $task_node)
      {
        $task_id_array = getcontent ($task_node, "<task_id>");
        $task_cat_array = getcontent ($task_node, "<task_cat>");
        $task_date_array = getcontent ($task_node, "<task_date>");
        $task_site_array = getcontent ($task_node, "<publication>");
        $task_page_array = getcontent ($task_node, "<object>");
        $task_pageid_array = getcontent ($task_node, "<object_id>");
        $task_priority_array = getcontent ($task_node, "<priority>");
        $task_descr_array = getcontent ($task_node, "<description>");
              
        // define site
        $site = $task_site_array[0];
     
        // remove tasks from sites without siteaccess of the current user
        if (!checkpublicationpermission ($site) && $task_id_array[0] != "") 
        {
          $task_data = deletecontent ($task_data, "<task>", "<task_id>", $task_id_array[0]);
          $savetasklist = true;
        }  
        // else display task       
        else
        {    
          // define row color
          if ($task_priority_array[0] == "high")
          {
            $rowcolor = "hcmsPriorityHigh";
            $priority = $hcms_lang['high'][$lang];
          }
          elseif ($task_priority_array[0] == "medium")
          {
            $rowcolor = "hcmsPriorityMedium";
            $priority = $hcms_lang['medium'][$lang];
          }
          else
          {
            $rowcolor = "hcmsPriorityLow";
            $priority = $hcms_lang['low'][$lang];
          }
          
          // load site config
          if ($site != $site_memory && valid_publicationname ($site)) include_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
          
          // set site memory for next entry in loop
          $site_memory = $site;
          
          // get object path from object id
          if ($mgmt_config['db_connect_rdbms'] != "")
          {
            $objectpath = rdbms_getobject ($task_pageid_array[0]);
            if ($objectpath == false) $objectpath = $task_page_array[0];
          }
          else $objectpath = $task_page_array[0];

          // task with object reference
          if ($objectpath != "")
          {
            // define location and corrected file
            $location_esc = getlocation ($objectpath);
            $location = deconvertpath ($location_esc, "file");            
            $file = getobject ($objectpath);
            $file = correctfile ($location, $file, $user);
            $cat = getcategory ($site, $objectpath);
              
            if (@is_file ($location.$file))
            {
              // get file info
              if ($file != "") $file_info = getfileinfo ($site, $location.$file, $cat);    
              else $file_info['icon'] = "Null_media.gif";
              
              // define short location
              if ($file == ".folder") $location_short = getlocationname ($site, getlocation ($location), $cat);
              else $location_short = getlocationname ($site, $location, $cat);
              
              // check access permissions
              if (accesspermission ($site, $location, $cat) != false) $onclick = "onClick=\"window.open('frameset_content.php?ctrlreload=yes&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($file)."','','status=yes,scrollbars=no,resizable=yes,width=800,height=600');\"";
              else $onclick = "onClick=\"window.open('page_preview.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($file)."','preview','status=yes,scrollbars=no,resizable=yes,width=800,height=600');\"";
              
              echo "<tr class=\"".$rowcolor."\">
                <td valign=\"top\" class=\"hcmsRowHead2 hcmsHeadline\" nowrap=\"nowrap\">".$j."</td>
                <td valign=\"top\" nowrap=\"nowrap\">".$task_date_array[0]."</td>
                <td valign=\"top\" nowrap=\"nowrap\"><a href=\"#\" ".$onclick."><img src=\"".getthemelocation()."img/".$file_info['icon']."\" style=\"height:16px; width:16px; border:0;\" align=\"top\" />&nbsp;".$file_info['name']."</a></td>
                <td valign=\"top\" nowrap=\"nowrap\">".$location_short."</td>
                <td valign=\"top\" nowrap=\"nowrap\">".$site."</td>
                <td valign=\"top\">".str_replace ("\n", "<br />", $task_descr_array[0])."</td>
                <td valign=\"top\" nowrap=\"nowrap\">".$task_cat_array[0]."</td>
                <td valign=\"top\" nowrap=\"nowrap\">".$priority."</td>
                <td valign=\"middle\" align=\"middle\" class=\"hcmsRowHead2\"><input type=\"checkbox\" name=\"delete_id[]\" value=\"".$task_id_array[0]."\" /></td>
              </tr>\n";       
              
              $j++;
            }
            // remove task if file does not exist anymore
            elseif ($task_id_array[0] != "") 
            {
              $task_data = deletecontent ($task_data, "<task>", "<task_id>", $task_id_array[0]);
              $savetasklist = true;
            }
          }
          // task without object reference
          else
          {
            echo "<tr class=\"".$rowcolor."\">
              <td valign=\"top\" class=\"hcmsRowHead2 hcmsHeadline\" nowrap=\"nowrap\">".$j."</td>
              <td valign=\"top\" nowrap=\"nowrap\">".$task_date_array[0]."</td>
              <td valign=\"top\" nowrap=\"nowrap\">-</td>
              <td valign=\"top\" nowrap=\"nowrap\">-</td>
              <td valign=\"top\" nowrap=\"nowrap\">".$task_site_array[0]."</td>
              <td valign=\"top\">".str_replace ("\n", "<br />", $task_descr_array[0])."</td>
              <td valign=\"top\" nowrap=\"nowrap\">".$task_cat_array[0]."</td>
              <td valign=\"top\" nowrap=\"nowrap\">".$priority."</td>
              <td valign=\"middle\" align=\"middle\" class=\"hcmsRowHead2\"><input type=\"checkbox\" name=\"delete_id[]\" value=\"".$task_id_array[0]."\" /></td>
            </tr>\n";   
            
            $j++;                   
          }
        }
      }
      
      // button
      $show_button = $hcms_lang['remove-finished-tasks-from-list'][$lang].": <img name=\"Button\" src=\"".getthemelocation()."img/button_OK.gif\" onClick=\"document.forms['taskform'].submit();\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button','','".getthemelocation()."img/button_OK_over.gif',1)\" style=\"border:0; cursor:pointer;\" align=\"absmiddle\" alt=\"OK\" title=\"OK\" />\n";
    }
    else
    {
      echo "<tr>
        <td>".$hcms_lang['your-task-queue-is-empty'][$lang]."</td>
      </tr>\n";
    }
    
    echo "</table>\n";
    
    // save task list
    if ($savetasklist == true)
    {
      savelockfile ($user, $mgmt_config['abs_path_data']."task/", $user.".xml.php", $task_data);
    }
    // or unlock
    else 
    {
      unlockfile ($user, $mgmt_config['abs_path_data']."task/", $user.".xml.php");
    }  
    ?>
  </form>
  </div><br />
  
  <div class="hcmsWorkplaceGeneric" style="position:fixed; bottom:0; width:100%; height:30px; z-index:4; visibility:visible; text-align:left;">
    <?php echo $show_button; ?>
  </div>
  
</div>

</body>
</html>
