<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session
define ("SESSION", "create");
// management configuration
require ("../config.inc.php");
// hyperCMS API
require ($mgmt_config['abs_path_cms']."function/hypercms_api.inc.php");


// input parameters
$tab = getrequest ("tab");
$action = getrequest ("action");
$start_mytasks = getrequest ("start_mytasks", "numeric", 0);
$start_mgmt = getrequest ("start_mgmt", "numeric", 0);
$delete_id = getrequest ("delete_id", "array", array());
$to_user = getrequest ("to_user", "array");
$startdate = getrequest ("startdate", "array");
$finishdate = getrequest ("finishdate", "array");
$taskname = getrequest_esc ("taskname", "array");
$description = getrequest_esc ("description", "array");
$priority = getrequest ("priority", "array");
$status = getrequest ("status", "array");
$planned = getrequest ("planned", "array");
$actual = getrequest ("actual", "array");
$token = getrequest ("token");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('desktop') || !checkrootpermission ('desktoptaskmgmt') || !valid_objectname ($user)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$paging = 100;
$show = "";
$action_field = "";

if ($action == "task_save" && checktoken ($token, $user) && checkrootpermission ('desktoptaskmgmt'))
{
  // delete tasks
  if (is_array ($delete_id)) 
  {
    $result = deletetask ($delete_id);
    $show = $result['message'];  
  }
  
  // save status of assigned tasks
  if (is_array ($status)) 
  {
    foreach ($status as $task_id => $status_value)
    {
      if ($task_id != "") $result = settask ($task_id, "", "", "", "", "", true, "", $status_value, "", $actual[$task_id]);
    }
    
    $show = $result['message'];  
  }
  
  // save managed tasks
  if (is_array ($priority)) 
  {
    foreach ($priority as $task_id => $priority_value)
    {
      if ($task_id != "" && !in_array ($task_id, $delete_id)) $result = settask ($task_id, $to_user[$task_id], $startdate[$task_id], $finishdate[$task_id], $taskname[$task_id], $description[$task_id], true, $priority_value, "", $planned[$task_id], "");
    }
    
    $show = $result['message'];
  }
}

// load user file and define user array
$userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");

$user_array = array();

if ($userdata != "")
{
  // get publications
  $inherit_db = inherit_db_read ();  
  $site_array = array();
  
  if ($inherit_db != false && sizeof ($inherit_db) > 0)
  {
    foreach ($inherit_db as $inherit_db_record)
    {
      if ($inherit_db_record['parent'] != "")
      {
        $site_array[] = $inherit_db_record['parent'];
      }
    }
  }

  // get user node and extract required information    
  $usernode = getcontent ($userdata, "<user>");

  foreach ($usernode as $temp)
  {
    if ($temp != "")
    {
      $login = getcontent ($temp, "<login>");
      $admin = getcontent ($temp, "<admin>");
      $email = getcontent ($temp, "<email>");
      $realname = getcontent ($temp, "<realname>");
      $publication = getcontent ($temp, "<publication>");
      
      // standard user
      if (!empty ($login[0]) && (empty ($admin[0]) || $admin[0] == 0) && is_array ($publication))
      {
        foreach ($publication as $pub_temp)
        {
          if ($pub_temp != "")
          {
            $username = $login[0];
            $user_array[$pub_temp][$username]['email'] = $email[0];
            $user_array[$pub_temp][$username]['realname'] = $realname[0];
          }
        }
      }
      // super user
      elseif (!empty ($login[0]) && !empty ($admin[0]) && $admin[0] == 1)
      {
        foreach ($site_array as $pub_temp)
        {
          if ($pub_temp != "")
          {
            $username = $login[0];
            $user_array[$pub_temp][$username]['email'] = $email[0];
            $user_array[$pub_temp][$username]['realname'] = $realname[0];
          }
        }
      }
    }
  }
}

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="../javascript/main.js" type="text/javascript"></script>

<link rel="stylesheet" type="text/css" href="../javascript/rich_calendar/rich_calendar.css" />
<script language="JavaScript" type="text/javascript" src="../javascript/rich_calendar/rich_calendar.js"></script>
<script language="JavaScript" type="text/javascript" src="../javascript/rich_calendar/rc_lang_en.js"></script>
<script language="JavaScript" type="text/javascript" src="../javascript/rich_calendar/rc_lang_de.js"></script>
<script language="Javascript" type="text/javascript" src="../javascript/rich_calendar/domready.js"></script>
<script type="text/javascript">
<!--
var cal_obj = null;
var cal_format = '%Y-%m-%d';
var cal_field = null;

// show calendar
function show_cal (el, field_id, format)
{
  if (cal_obj) return;
  
  cal_field = field_id;
  cal_format = format;
  var datefield = document.getElementById(field_id);

	cal_obj = new RichCalendar();
	cal_obj.start_week_day = 1;
	cal_obj.show_time = false;
	cal_obj.language = '<?php echo getcalendarlang ($lang); ?>';
  cal_obj.user_onchange_handler = cal_on_change;
  cal_obj.user_onclose_handler = cal_on_close;
  cal_obj.user_onautoclose_handler = cal_on_autoclose;
  cal_obj.parse_date(datefield.value, cal_format);
	cal_obj.show_at_element(datefield, "adj_left-top");
}

// user defined onchange handler
function cal_on_change(cal, object_code)
{
	if (object_code == 'day')
	{
		document.getElementById(cal_field).value = cal.get_formatted_date(cal_format);
		cal.hide();
		cal_obj = null;
	}
}

// user defined onclose handler (used in pop-up mode - when auto_close is true)
function cal_on_close(cal)
{
	cal.hide();
	cal_obj = null;
}

// user defined onautoclose handler
function cal_on_autoclose(cal)
{
	cal_obj = null;
}

function showFinished ()
{
  var show = document.getElementById("showFinished").checked;
  var rows = document.getElementsByClassName("hcmsRowFinished");
  
  if (show == true) var display = "table-row";
  else var display = "none";

  for (var i=0; i<rows.length; i++)
  {
    rows[i].style.display = display;
  }
}

function showUser (name)
{
  var show = document.getElementById(name).checked;
  var rows = document.getElementsByClassName(name);
  
  if (show == true) var display = "table-row";
  else var display = "none";

  for (var i=0; i<rows.length; i++)
  {
    rows[i].style.display = display;
  }
}

function showAllUser ()
{
  var show = document.getElementById('allUser').checked;
  
  // check/uncheck users
  var rows = document.getElementsByClassName('deleteUser');
  
  for (var i=0; i<rows.length; i++)
  {
    rows[i].checked = show;
  }
  
  // show/hide tabel rows
  var rows = document.getElementsByClassName('User');
  
  if (show == true) var display = "table-row";
  else var display = "none";

  for (var i=0; i<rows.length; i++)
  {
    rows[i].style.display = display;
  }
}

function save ()
{
  document.getElementById('saveLayer').style.display = 'block';
  
  if (document.getElementById("action_task") && document.getElementById("action_task").value == "task_save" && document.getElementById("taskFrame").style.visibility != "hidden")
  {
    document.forms['taskForm'].submit();
  }
  else if (document.getElementById("action_mgmt") && document.getElementById("action_mgmt").value == "task_save")
  {
    document.forms['mgmtForm'].submit();
  }
}
//-->
</script>
<style type="text/css">
.hcmsRowFinished
{
  display: none;
}
</style>
</head>

<body class="hcmsWorkplaceGeneric" onLoad="<?php if ($tab == "management") echo "hcms_showHideLayers('Layer_tab1','','hide','Layer_tab2','','show','taskFrame','','hide','mgmtFrame','','show','mytasksMore','','hide','mgmtMore','','show','userFilter','','show');"; ?>">

<?php
echo showmessage ($show, 500, 70, $lang, "position:fixed; left:50px; top:50px;");
?>

<!-- load screen -->
<div id="saveLayer" class="hcmsLoadScreen"></div>

<!-- top bar -->
<?php echo showtopbar ($hcms_lang['task-management'][$lang], $lang); ?>

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

  <div id="toolbar">
    <img name="Button_so" src="<?php echo getthemelocation(); ?>img/button_save.gif" class="hcmsButton hcmsButtonSizeSquare" onClick="save()" alt="<?php echo getescapedtext ($hcms_lang['save'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['save'][$lang]); ?>" />
    <div class="hcmsButton" style="height:22px;">
      <input type="checkbox" name="showFinished" id="showFinished" onclick="showFinished()" style="margin:0px 0px 0px 5px;" />&nbsp;<label for="showFinished"><?php echo getescapedtext ($hcms_lang['show-finished-tasks'][$lang]); ?></label>&nbsp;
    </div>
  </div>

  <div id="Layer_tab" class="hcmsTabContainer" style="position:absolute; z-index:10; visibility:visible; left:0px; top:66px;">
    <table border="0" cellspacing="0" cellpadding="0" style="z-index:1;">
      <tr>
        <td style="width:3px;"><img src="<?php echo getthemelocation(); ?>img/backgrd_tabs_spacer.gif" style="width:3px; height:3px; border:0;" /></td>
        <td align="left" valign="top" class="hcmsTab">&nbsp;
          <a href="#" onClick="hcms_showHideLayers('Layer_tab1','','show','Layer_tab2','','hide','taskFrame','','show','mgmtFrame','','hide','mytasksMore','','show','mgmtMore','','hide','userFilter','','hide')"><?php echo getescapedtext ($hcms_lang['my-tasks'][$lang]); ?></a>
        </td>
        <td style="width:3px;"><img src="<?php echo getthemelocation(); ?>img/backgrd_tabs_spacer.gif" style="width:3px; height:3px; border:0;" /></td>
        <td align="left" valign="top" class="hcmsTab">&nbsp;
          <a href="#" onClick="hcms_showHideLayers('Layer_tab1','','hide','Layer_tab2','','show','taskFrame','','hide','mgmtFrame','','show','mytasksMore','','hide','mgmtMore','','show','userFilter','','show')"><?php echo getescapedtext ($hcms_lang['management'][$lang]); ?></a>
        </td>
      </tr>
    </table>
  </div>
  
  <div id="Layer_tab1" class="hcmsWorkplaceGeneric" style="position:absolute; width:118px; height:2px; z-index:20; visibility:visible; left:4px; top:88px;"> </div>
  <div id="Layer_tab2" class="hcmsWorkplaceGeneric" style="position:absolute; width:118px; height:2px; z-index:20; visibility:hidden; left:127px; top:88px;"> </div>
  
  <div id="taskFrame" style="position:absolute; left:5px; right:5px; top:95px; bottom:30px; z-index:10; visibility:visible; overflow:auto;">
    <form name="taskForm" action="" method="post">
    <input type="hidden" name="tab" value="my-tasks" />
    <input type="hidden" name="start_mytasks" value="<?php echo $start_mytasks; ?>" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
    <table width="100%" border="0" cellspacing="1" cellpadding="3">
    <?php
    // get tasks of user 
    $task_array = rdbms_gettask ("", "", "", "", $user, "", "", "startdate DESC LIMIT ".intval($start_mytasks).",".$paging);

    if (is_array ($task_array) && sizeof ($task_array) > 0)
    {
      echo "
      <tr class=\"hcmsRowHead2\">
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"10\" nowrap=\"nowrap\">#</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"120\" nowrap=\"nowrap\">".getescapedtext ($hcms_lang['name'][$lang])."</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"120\">".ucfirst (getescapedtext ($hcms_lang['from'][$lang]))."</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"80\" nowrap=\"nowrap\">".getescapedtext ($hcms_lang['start'][$lang])."</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"80\" nowrap=\"nowrap\">".getescapedtext ($hcms_lang['end'][$lang])."</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"80\" nowrap=\"nowrap\">".getescapedtext ($hcms_lang['object'][$lang])."</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['description'][$lang])."</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"80\" nowrap=\"nowrap\">".getescapedtext ($hcms_lang['category'][$lang])."</td>
        <td align=\"center\" align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"80\" nowrap=\"nowrap\">".getescapedtext ($hcms_lang['priority'][$lang])."</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"40\">".getescapedtext ($hcms_lang['status'][$lang])."</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"40\">".getescapedtext ($hcms_lang['actual-effort'][$lang])." (".$mgmt_config['taskunit'].")</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"40\">".getescapedtext ($hcms_lang['planned-effort'][$lang])." (".$mgmt_config['taskunit'].")</td>
      </tr>";
      
      $site_memory = "";
      $counter = 0;

      foreach ($task_array as $task_record)
      {
        if ($task_record['task_id'] != "")
        {
          // define row color
          if ($task_record['priority'] == "high")
          {
            $rowcolor = "hcmsPriorityHigh";
            $priority = getescapedtext ($hcms_lang['high'][$lang]);
          }
          elseif ($task_record['priority'] == "medium")
          {
            $rowcolor = "hcmsPriorityMedium";
            $priority = getescapedtext ($hcms_lang['medium'][$lang]);
          }
          else
          {
            $rowcolor = "hcmsPriorityLow";
            $priority = getescapedtext ($hcms_lang['low'][$lang]);
          }
          
          // task with object reference
          if ($task_record['objectpath'] != "")
          {
            // define site
            $site = getpublication ($task_record['objectpath']);
         
            // remove tasks from sites without siteaccess of the current user
            if (!checkpublicationpermission ($site)) 
            {
              $task_data = rdbms_deletetask ($task_record['task_id']);
            }
        
            // load site config
            if ($site != $site_memory && valid_publicationname ($site)) include_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
          
            // set site memory for next entry in loop
            $site_memory = $site;
            
            // task work progress status selector
            $select_status = "
            <select name=\"status[".$task_record['task_id']."]\">";
            
            for ($i=0; $i<=4; $i++)
            {
              $value = $i * 25;
              
              if ($task_record['status'] == $value) $selected = "selected=\"selected\"";
              else $selected = "";
              
              $select_status .= "
              <option value=\"".$value."\" ".$selected.">".$value."%</option>";
            }
            
            $select_status .= "
            </select>";
            
            // define location and corrected file
            $location_esc = getlocation ($task_record['objectpath']);
            $location = deconvertpath ($location_esc, "file");            
            $file = getobject ($task_record['objectpath']);
            $file = correctfile ($location, $file, $user);
            $cat = getcategory ($site, $task_record['objectpath']);
              
            if (@is_file ($location.$file))
            {
              // get file info
              if ($file != "") $file_info = getfileinfo ($site, $location.$file, $cat);    
              else $file_info['icon'] = "Null_media.gif";
              
              // define short location
              if ($file == ".folder") $location_short = getlocationname ($site, getlocation ($location), $cat);
              else $location_short = getlocationname ($site, $location, $cat);
              
              // check access permissions
              if (accesspermission ($site, $location, $cat) != false) $onclick = "onClick=\"window.open('../frameset_content.php?ctrlreload=yes&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($file)."','','status=yes,scrollbars=no,resizable=yes,width=800,height=600');\"";
              else $onclick = "onClick=\"window.open('../page_preview.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($file)."','preview','status=yes,scrollbars=no,resizable=yes,width=800,height=600');\"";
              
              // empty finish date if not set
              if ($task_record['finishdate'] == "0000-00-00") $task_record['finishdate'] = "";
              // compare today with finish date
              elseif ((time()-(60*60*24)) >= strtotime($task_record['finishdate'])) $rowcolor = "hcmsPriorityAlarm";
              
              $addclass = "";
              
              // add class if task is finished (100%)
              if ($task_record['status'] == 100) $addclass .= " hcmsRowFinished";
              
              // add class for user
              if (!empty ($task_record['to_user'])) $addclass .= " ".$task_record['to_user'];
              
              // from user
              if (!empty ($user_array[$site][$task_record['from_user']]['realname']))
              {
                $task_from_user = $user_array[$site][$task_record['from_user']]['realname'];
              }
              elseif (!empty ($task_record['from_user']) && !filter_var ($task_record['from_user'], FILTER_VALIDATE_IP))
              {
                $task_from_user = $task_record['from_user'];
              }
              else $task_from_user = "System";
              
              if (!empty ($user_array[$site][$task_record['from_user']]['email']))
              {
                $task_from_user = "<a href=\"mailto:".$user_array[$site][$task_record['from_user']]['email']."\">".$task_from_user."</a>";
              }
              
              echo "
              <tr class=\"".$rowcolor.$addclass."\">
                <td valign=\"middle\" align=\"center\" class=\"hcmsRowHead2 hcmsHeadline\" nowrap=\"nowrap\">".$task_record['task_id']."</td>
                <td valign=\"middle\" align=\"left\">".specialchr_decode ($task_record['taskname'])."</td>
                <td valign=\"middle\" align=\"left\">".$task_from_user."</td>
                <td valign=\"middle\" align=\"center\">".$task_record['startdate']."</td>
                <td valign=\"middle\" align=\"center\">".$task_record['finishdate']."</td>
                <td valign=\"middle\" align=\"left\" nowrap=\"nowrap\"><a href=\"#\" ".$onclick." title=\"".$location_short.$file_info['name']."\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" style=\"height:16px; width:16px; border:0;\" align=\"top\" />&nbsp;".showshorttext($file_info['name'], 20)."</a></td>
                <td valign=\"middle\" align=\"left\">".str_replace ("\n", "<br />", $task_record['description'])."</td>
                <td valign=\"middle\" align=\"center\">".$task_record['category']."</td>
                <td valign=\"middle\" align=\"center\">".$task_record['priority']."</td>
                <td valign=\"middle\" align=\"center\">".$select_status."</td>
                <td valign=\"middle\" align=\"center\"><input name=\"actual[".$task_record['task_id']."]\" value=\"".$task_record['actual']."\" style=\"width:40px;\" /></td>
                <td valign=\"middle\" align=\"center\">".$task_record['planned']."</td>
              </tr>";
            }
            // remove task if file does not exist anymore
            else
            {
              $task_data = rdbms_deletetask ($task_record['task_id']);
            }
          }
          // task without object reference
          else
          {
            echo "
            <tr class=\"".$rowcolor."\">
              <td valign=\"middle\" align=\"center\" class=\"hcmsRowHead2 hcmsHeadline\" nowrap=\"nowrap\">".$task_record['task_id']."</td>
              <td valign=\"middle\" align=\"left\">".specialchr_decode ($task_record['taskname'])."</td>
              <td valign=\"middle\" align=\"left\">".$task_from_user."</td>
              <td valign=\"middle\" align=\"center\">".$task_record['date']."</td>
              <td valign=\"middle\" align=\"center\">".$task_record['finishdate']."</td>
              <td valign=\"middle\" align=\"center\">-</td>
              <td valign=\"middle\" align=\"left\">".str_replace ("\n", "<br />", $task_record['description'])."</td>
              <td valign=\"middle\" align=\"center\">".$task_record['category']."</td>
              <td valign=\"middle\" align=\"center\">".$task_record['priority']."</td>
              <td valign=\"middle\" align=\"center\">".$select."</td>
              <td valign=\"middle\" align=\"center\"><input name=\"actual[".$task_record['task_id']."]\" value=\"".$task_record['actual']."\" style=\"width:40px;\" /></td>
              <td valign=\"middle\" align=\"center\">".$task_record['planned']."</td>
            </tr>";                 
          }
          
          $counter++;
        }
      }
      
      $action_field = "<input type=\"hidden\" name=\"action\" id=\"action_task\" value=\"task_save\" />";
    }
    else
    {
      echo "
      <tr>
        <td>".showmessage (getescapedtext ($hcms_lang['your-task-queue-is-empty'][$lang]), 500, 50, $lang, "position:fixed; left:10px; top:100px;", "hcmsMessageBoxTask")."</td>
      </tr>";
    }
    
    echo "
    </table>
    ".$action_field;
    ?>
    </form>
    
    <!-- paging buttons -->
    <div id="mytasksMore" style="position:fixed; bottom:0; width:100%; height:30px; z-index:4; visibility:hidden; text-align:center;">
      <div style="padding:8px; float:left;"><?php echo $start_mytasks." - ".($start_mytasks+$counter)." ".getescapedtext ($hcms_lang['objects'][$lang]); ?></div>
      <?php if ($start_mgmt > 0) { ?><button class="hcmsButtonBlue" style="width:160px;" onclick="window.location.href='<?php echo "?tab=my-tasks&start_mytasks=".($start_mytasks-$paging); ?>';"><?php echo getescapedtext ($hcms_lang['back'][$lang]); ?></button><?php } ?>
      <?php if ($counter >= $paging) { ?><button class="hcmsButtonBlue" style="width:160px;" onclick="window.location.href='<?php echo "?tab=my-tasks&start_mytasks=".($start_mytasks+$paging); ?>';"><?php echo getescapedtext ($hcms_lang['forward'][$lang]); ?></button><?php } ?>
    </div>

  </div>
  
  <div id="mgmtFrame" style="position:absolute; left:5px; right:5px; top:180px; bottom:30px; z-index:10; visibility:hidden; overflow:auto;">
    <form name="mgmtForm" action="" method="post">
    <input type="hidden" name="tab" value="management" />
    <input type="hidden" name="start_mgmt" value="<?php echo $start_mgmt; ?>" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
    
    <table width="100%" border="0" cellspacing="1" cellpadding="3">
    <?php
    // get tasks of user 
    if (checkadminpermission ()) $task_array = rdbms_gettask ("", "", "", "", "", "", "", "startdate DESC LIMIT ".intval($start_mgmt).",".$paging);
    else $task_array = rdbms_gettask ("", "", "", $user, "", "", "", "startdate DESC LIMIT ".intval($start_mgmt).",".$paging);

    if (is_array ($task_array) && sizeof ($task_array) > 0)
    {
      echo "
      <tr class=\"hcmsRowHead2\">
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"10\" nowrap=\"nowrap\">#</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"120\" nowrap=\"nowrap\">".getescapedtext ($hcms_lang['name'][$lang])."</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"120\" >".getescapedtext ($hcms_lang['user'][$lang])."</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"80\" nowrap=\"nowrap\">".getescapedtext ($hcms_lang['start'][$lang])."</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"80\" nowrap=\"nowrap\">".getescapedtext ($hcms_lang['end'][$lang])."</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"80\">".getescapedtext ($hcms_lang['object'][$lang])."</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['description'][$lang])."</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"80\" nowrap=\"nowrap\">".getescapedtext ($hcms_lang['priority'][$lang])."</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"40\">".getescapedtext ($hcms_lang['status'][$lang])."</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"40\">".getescapedtext ($hcms_lang['planned-effort'][$lang])." (".$mgmt_config['taskunit'].")</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"40\">".getescapedtext ($hcms_lang['actual-effort'][$lang])." (".$mgmt_config['taskunit'].")</td>
        <td align=\"center\" valign=\"top\" class=\"hcmsHeadline\" width=\"40\">".getescapedtext ($hcms_lang['delete'][$lang])."</td>
      </tr>";
      
      $site_memory = "";
      $filter_user = array();
      $counter = 0;

      foreach ($task_array as $task_record)
      {
        if ($task_record['task_id'] != "")
        {
          // define row color
          if ($task_record['priority'] == "high")
          {
            $rowcolor = "hcmsPriorityHigh";
            $priority = getescapedtext ($hcms_lang['high'][$lang]);
          }
          elseif ($task_record['priority'] == "medium")
          {
            $rowcolor = "hcmsPriorityMedium";
            $priority = getescapedtext ($hcms_lang['medium'][$lang]);
          }
          else
          {
            $rowcolor = "hcmsPriorityLow";
            $priority = getescapedtext ($hcms_lang['low'][$lang]);
          }
          
          // task with object reference
          if ($task_record['objectpath'] != "")
          {
            // define site
            $site = getpublication ($task_record['objectpath']);
         
            // remove tasks from sites without siteaccess of the current user
            if (!checkpublicationpermission ($site)) 
            {
              $task_data = rdbms_deletetask ($task_record['task_id']);
            }
        
            // load site config
            if ($site != $site_memory && valid_publicationname ($site)) include_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
          
            // set site memory for next entry in loop
            $site_memory = $site;
            
            // task priority selector
            $priority_array = array();
            $priority_array['low'] = $hcms_lang['low'][$lang];
            $priority_array['medium'] = $hcms_lang['medium'][$lang];
            $priority_array['high'] = $hcms_lang['high'][$lang];
            
            $priority_select = "
            <select name=\"priority[".$task_record['task_id']."]\">";
            
            foreach ($priority_array as $key=>$value)
            {
              if ($task_record['priority'] == $key) $selected = "selected=\"selected\"";
              else $selected = "";
              
              $priority_select .= "
              <option value=\"".$key."\" ".$selected.">".$value."</option>";
            }
            
            $priority_select .= "
            </select>";
            
            // user select
            $user_select = "
            <select name=\"to_user[".$task_record['task_id']."]\" style=\"width:120px;\">";
            
            reset ($user_array);
            $user_option = array();
            
            foreach ($user_array[$site] as $login=>$value)
            {
              if ($task_record['to_user'] == $login) $selected = "selected=\"selected\"";
              else $selected = "";
              
              $text = "";
              if ($value['realname'] != "") $text .= $value['realname']." ";
              if ($text == "") $text .= $login." ";
              if ($value['email'] != "") $text .= "(".$value['email'].")";
              
              if (!isset ($user_option[$text])) $user_option[$text] = "";

              $user_option[$text] .= "
              <option value=\"".$login."\" ".$selected.">".$text."</option>";
            }
            
            ksort ($user_option, SORT_STRING | SORT_FLAG_CASE);
            $user_select .= implode ("", $user_option);
            
            $user_select .= "
            </select>";

            // define location and corrected file
            $location_esc = getlocation ($task_record['objectpath']);
            $location = deconvertpath ($location_esc, "file");            
            $file = getobject ($task_record['objectpath']);
            $file = correctfile ($location, $file, $user);
            $cat = getcategory ($site, $task_record['objectpath']);
              
            if (@is_file ($location.$file))
            {
              // get file info
              if ($file != "") $file_info = getfileinfo ($site, $location.$file, $cat);    
              else $file_info['icon'] = "Null_media.gif";
              
              // define short location
              if ($file == ".folder") $location_short = getlocationname ($site, getlocation ($location), $cat);
              else $location_short = getlocationname ($site, $location, $cat);
              
              // check access permissions
              if (accesspermission ($site, $location, $cat) != false) $onclick = "onClick=\"window.open('../frameset_content.php?ctrlreload=yes&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($file)."','','status=yes,scrollbars=no,resizable=yes,width=800,height=600');\"";
              else $onclick = "onClick=\"window.open('../page_preview.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($file)."','preview','status=yes,scrollbars=no,resizable=yes,width=800,height=600');\"";
              
              // empty finish date if not set
              if ($task_record['finishdate'] == "0000-00-00") $task_record['finishdate'] = "";
              // compare today with finish date
              elseif ((time()-(60*60*24)) >= strtotime($task_record['finishdate'])) $rowcolor = "hcmsPriorityAlarm";
              
              $addclass = "";
              
              // if task is finished (100%) add class
              if ($task_record['status'] == 100) $addclass .= " hcmsRowFinished";
              
              // add class for user
              if (!empty ($task_record['to_user'])) $addclass .= " ".$task_record['to_user'];
              
              // add to user filter
              if (!empty ($task_record['to_user']))
              {
                $login = $task_record['to_user'];
                
                if (!empty ($user_array[$site][$login]['realname'])) $text = $user_array[$site][$login]['realname'];
                elseif (!empty ($user_array[$site][$login]['email'])) $text = $user_array[$site][$login]['email'];
                else $text = $login;
                
                if (empty ($filter_user[$text])) $filter_user[$text] = "
  <div style=\"display:inline-block; width:200px;\"><input id=\"".$task_record['to_user']."\" class=\"deleteUser\" type=\"checkbox\" checked=\"checked\" onclick=\"showUser('".$task_record['to_user']."')\"/> <label for=\"".$task_record['to_user']."\">".$text."</label></div>";
              }
              
              echo "
              <tr class=\"".$rowcolor." User".$addclass."\">
                <td valign=\"middle\" align=\"center\" class=\"hcmsRowHead2 hcmsHeadline\" nowrap=\"nowrap\">".$task_record['task_id']."</td>
                <td valign=\"middle\" align=\"center\"><input name=\"taskname[".$task_record['task_id']."]\" value=\"".specialchr_decode ($task_record['taskname'])."\" style=\"width:120px;\" /></td>
                <td valign=\"middle\" align=\"center\">".$user_select."</td>
                <td valign=\"middle\" align=\"center\" nowrap=\"nowrap\">
                  <input name=\"startdate[".$task_record['task_id']."]\" id=\"startdate_".$task_record['task_id']."\" value=\"".$task_record['startdate']."\" readonly=\"readonly\" style=\"width:80px;\" /><img src=\"".getthemelocation()."img/button_datepicker.gif\" onclick=\"show_cal(this, 'startdate_".$task_record['task_id']."', '%Y-%m-%d');\" alt=\"".getescapedtext ($hcms_lang['select-date'][$lang])."\" title=\"".getescapedtext ($hcms_lang['select-date'][$lang])."\" align=\"top\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" />
                </td>
                <td valign=\"middle\" align=\"center\" nowrap=\"nowrap\">
                  <input name=\"finishdate[".$task_record['task_id']."]\" id=\"finishdate_".$task_record['task_id']."\" value=\"".$task_record['finishdate']."\" readonly=\"readonly\" style=\"width:80px;\" /><img src=\"".getthemelocation()."img/button_datepicker.gif\" onclick=\"show_cal(this, 'finishdate_".$task_record['task_id']."', '%Y-%m-%d');\" alt=\"".getescapedtext ($hcms_lang['select-date'][$lang])."\" title=\"".getescapedtext ($hcms_lang['select-date'][$lang])."\" align=\"top\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" />
                </td>
                <td valign=\"middle\" align=\"left\" nowrap=\"nowrap\"><a href=\"#\" ".$onclick." title=\"".$location_short.$file_info['name']."\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" style=\"height:16px; width:16px; border:0;\" align=\"top\" />&nbsp;".showshorttext($file_info['name'], 20)."</a></td>
                <td valign=\"middle\" align=\"left\"><textarea name=\"description[".$task_record['task_id']."]\" style=\"width:99%;height:60px;\">".$task_record['description']."</textarea></td>
                <td valign=\"middle\" align=\"center\">".$priority_select."</td>
                <td valign=\"middle\" align=\"center\"><b>".$task_record['status']."%</b></td>
                <td valign=\"middle\" align=\"center\"><input name=\"planned[".$task_record['task_id']."]\" value=\"".$task_record['planned']."\" style=\"width:40px;\" /></td>
                <td valign=\"middle\" align=\"center\">".$task_record['actual']."</td>
                <td valign=\"middle\" align=\"center\" class=\"hcmsRowHead2\"><input type=\"checkbox\" name=\"delete_id[]\" value=\"".$task_record['task_id']."\" /></td>
              </tr>";
            }
            // remove task if file does not exist anymore
            else
            {
              $task_data = rdbms_deletetask ($task_record['task_id']);
            }
          }
          // task without object reference
          else
          {
            echo "
            <tr class=\"".$rowcolor."\">
              <td valign=\"middle\" align=\"center\" class=\"hcmsRowHead2 hcmsHeadline\" nowrap=\"nowrap\">".$task_record['task_id']."</td>
              <td valign=\"middle\" align=\"center\"><input name=\"taskname[".$task_record['task_id']."]\" value=\"".specialchr_decode ($task_record['taskname'])."\" style=\"width:120px;\" /></td>
              <td valign=\"middle\" align=\"center\">".$user_select."</td>
              <td valign=\"middle\" align=\"center\"><input name=\"startdate[".$task_record['task_id']."]\" value=\"".$task_record['startdate']."\" style=\"width:80px;\" /></td>
              <td valign=\"middle\" align=\"center\"><input name=\"finishdate[".$task_record['task_id']."]\" value=\"".$task_record['finishdate']."\" style=\"width:80px;\" /></td>
              <td valign=\"middle\" align=\"center\">-</td>
              <td valign=\"middle\" align=\"left\"><textarea name=\"description[".$task_record['task_id']."]\" style=\"width:99%;height:60px;\">".$task_record['description']."</textarea></td>
              <td valign=\"middle\" align=\"center\">".$priority_select."</td>
              <td valign=\"middle\" align=\"center\"><b>".$task_record['status']."%</b></td>
              <td valign=\"middle\" align=\"center\"><input name=\"planned[".$task_record['task_id']."]\" value=\"".$task_record['planned']."\" style=\"width:40px;\" /></td>
              <td valign=\"middle\" align=\"center\">".$task_record['actual']."</td>
              <td valign=\"middle\" align=\"center\" class=\"hcmsRowHead2\"><input type=\"checkbox\" name=\"delete_id[]\" value=\"".$task_record['task_id']."\" /></td>
            </tr>";                 
          }
          
          $counter++;
        }
      }
      
      $action_field = "<input type=\"hidden\" name=\"action\" id=\"action_mgmt\" value=\"task_save\" />";
    }
    
    echo "
    </table>
    ".$action_field;
    ?>
    </form>
    
    <!-- paging buttons -->
    <div id="mgmtMore" style="position:fixed; bottom:0; width:100%; height:30px; z-index:4; visibility:hidden; text-align:center;">
      <div style="padding:8px; float:left;"><?php echo $start_mgmt." - ".($start_mgmt+$counter)." ".getescapedtext ($hcms_lang['objects'][$lang]); ?></div>
      <?php if ($start_mgmt > 0) { ?><button class="hcmsButtonBlue" style="width:160px;" onclick="window.location.href='<?php echo "?tab=management&start_mgmt=".($start_mgmt-$paging); ?>';"><?php echo getescapedtext ($hcms_lang['back'][$lang]); ?></button><?php } ?>
      <?php if ($counter >= $paging) { ?><button class="hcmsButtonBlue" style="width:160px;" onclick="window.location.href='<?php echo "?tab=management&start_mgmt=".($start_mgmt+$paging); ?>';"><?php echo getescapedtext ($hcms_lang['forward'][$lang]); ?></button><?php } ?>
    </div>
    
  </div>  
</div>

<?php
// user filter
if (!empty ($filter_user) && sizeof ($filter_user) > 0)
{
  ksort ($filter_user, SORT_STRING | SORT_FLAG_CASE);
  
  echo "
  <div id=\"userFilter\" style=\"border:1px solid #999; padding:3px; visibility:hidden; position:absolute; left:5px; right:5px; top:94px; height:70px; overflow:auto;\">
    <div style=\"display:block;\"><input id=\"allUser\" type=\"checkbox\" checked=\"checked\" onclick=\"showAllUser()\"/> <b>".$hcms_lang['all-users'][$lang]."</b></div>
    ".implode ("", $filter_user)."
  </div>";
}
?>

</body>
</html>
