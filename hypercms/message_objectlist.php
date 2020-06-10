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


// input parameters
$search = getrequest ("search");
$start = getrequest ("start", "numeric", 0);

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('desktop')) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// initalize
$objects_counted = 0;
$objects_total = 0;
$listview = "";
$items_row = -1;

// create secure token
$token = createtoken ($user);

// default value for inital max items in list
if (empty ($mgmt_config['explorer_list_maxitems'])) $mgmt_config['explorer_list_maxitems'] = 100; 

// define next max number of items on the list 
if (is_numeric ($start)) $end = $start + $mgmt_config['explorer_list_maxitems'];
else $end = $mgmt_config['explorer_list_maxitems'];

// message directory
$dir = $mgmt_config['abs_path_data']."message/";

// scan messages directory
$message_array = array();

if (is_dir ($dir))
{
  $scandir = scandir ($dir);

  foreach ($scandir as $entry)
  {
    if ($entry != "." && $entry != ".." && strpos ($entry, ".".$user.".mail.php") > 0) $message_array[] = $entry;
  }
  
  rsort ($message_array);
}

// write object entries
if (is_array ($message_array) && sizeof ($message_array) > 0)
{
  $objects_total = sizeof ($message_array); 

  foreach ($message_array as $message_file)
  {
    // break loop if maximum has been reached
    if (($items_row + 1) >= $end) break;

    // extract data from file
    list ($message_time, $message_user, $message_type, $ext) = explode (".", $message_file);
    
    if ($message_time > 0)
    {
      // message date and time
      $date = date ("Y-m-d H:i", $message_time);

      // message file
      $mailfile = $message_time.".".$message_user.".mail";
      
      // file info
      $file_info = getfileinfo ("", $mailfile, "comp");
      
      // open on double click
      $openObject = "onDblClick=\"hcms_openWindow('user_sendlink.php?mailfile=".url_encode($mailfile)."&token=".$token."', '".$message_time."', 'status=yes,scrollbars=no,resizable=yes', 600, 900);\"";
      
      // onclick for marking objects
      $selectclick = "onClick=\"hcms_selectObject(this.id, event); hcms_updateControlMessageMenu();\"";
      
      // set context
      $hcms_setObjectcontext = "style=\"display:block;\" onMouseOver=\"hcms_setMessagecontext('".$message_user."', '".$mailfile."', '".$token."');\" onMouseOut=\"hcms_resetContext();\" ";

      // listview
      $class_image = "class=\"hcmsIconList\"";
      
      // message
      if (is_file ($dir.$message_file))
      {
        // initalize
        $email_title = "";
        
        // include message file
        include ($dir.$message_file);
      
        // recipients
        $recipients = array();
        if (is_array ($user_login) && sizeof ($user_login) > 0) $recipients[] = implode (", ", $user_login);
        if (is_array ($email_to) && sizeof ($email_to) > 0) $recipients[] = implode (", ", $email_to);
        if (!empty ($group_login)) $recipients[] = $group_login;
        $recipients = implode (", ", $recipients);
        
        // new variable names since version 8.0.0 (map old to new ones)
        if (!empty ($mail_title)) $email_title = $mail_title;
        if (!empty ($mail_body)) $email_body = $mail_body;

        // search
        if (trim ($search) != "" && stripos (" ".$email_title." ".$email_body." ".$recipients, trim ($search)) > 0) $found = true;
        else $found = false;
      }

      if ($found == true || trim ($search) == "")
      {
        // count valid objects 
        $items_row++;
    
        // skip rows for paging
        if (!empty ($mgmt_config['explorer_paging']) && $items_row < $start) continue;

        $listview .= "
            <tr id=\"g".$items_row."\" align=\"left\" style=\"cursor:pointer;\" ".$selectclick.">
              <td id=\"h".$items_row."_0\" class=\"hcmsCol1 hcmsCell\" style=\"padding-left:3px; width:160px;\">
                <div id=\"".$items_row."\" class=\"hcmsObjectListMarker\" ".$hcms_setObjectcontext." ".$openObject." > 
                  <a data-objectpath=\"".$mailfile."\" data-href=\"javascript:void(0);\">
                    <img src=\"".getthemelocation()."img/".$file_info['icon']."\" ".$class_image." /> <span title=\"".getescapedtext ($hcms_lang['e-mail'][$lang])."\">".$email_title."</span>
                  </a>
                </div>
              </td>
              <td id=\"h".$items_row."_1\" class=\"hcmsCol3 hcmsCell\" style=\"padding-left:3px; width:200px;\"><span ".$hcms_setObjectcontext.">".$recipients."</span></td>
              <td id=\"h".$items_row."_2\" class=\"hcmsCol4 hcmsCell\" style=\"padding-left:3px; width:120px;\"><span style=\"display:none;\">".date ("YmdHi", strtotime ($date))."</span><span ".$hcms_setObjectcontext.">".showdate ($date, "Y-m-d H:i", $hcms_lang_date[$lang])."</span></td>
              <td id=\"h".$items_row."_3\" class=\"hcmsCol5 hcmsCell\" style=\"padding-left:3px; width:60px;\"><span ".$hcms_setObjectcontext.">sent</span></td>
              <td id=\"h".$items_row."_4\" class=\"hcmsCol6 hcmsCell\" style=\"padding-left:3px;\"><span ".$hcms_setObjectcontext.">".$message_user."</span></td>
            </tr>"; 
      }
    }
    // message not valid
    else $objects_total--;
  }
}

// objects counted
if ($items_row > 0) $objects_counted = $items_row;
else $objects_counted = 0;
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/contextmenu.js" type="text/javascript"></script>
<script type="text/javascript" src="javascript/jquery/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="javascript/jquery/plugins/colResizable.min.js"></script>
<style type="text/css">
.hcmsCell
{
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
<script type="text/javascript">

// select area
var selectarea;

// context menu
contextenable = true;
is_mobile = <?php if (!empty ($is_mobile)) echo "true"; else echo "false"; ?>;
contextxmove = true;
contextymove = true;

// define global variable for popup window name used in contextmenu.js
var session_id = '<?php echo session_id(); ?>';

function confirm_delete ()
{
  return confirm(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-the-selected-entries'][$lang]); ?>"));
}

function buttonaction (action)
{
  multiobject = document.forms['contextmenu_message'].elements['multiobject'].value;
  object = document.forms['contextmenu_message'].elements['message_id'].value;
  
  if (action == "edit" && object != "") return true;
  else if (action == "delete" && object != "") return true;
  else return false;
}

function resizecols()
{
  // get width of table header columns
  var c1 = $('#c1').width();
  var c2 = $('#c2').width();
  var c3 = $('#c3').width() ;
  var c4 = $('#c4').width() ;
  var c5 = $('#c5').width();

  // set width for table columns
  $('.hcmsCol1').width(c1);
  $('.hcmsCol2').width(c2);
  $('.hcmsCol3').width(c3);
  $('.hcmsCol4').width(c4);
  $('.hcmsCol5').width(c5);
}

function initalize ()
{
  // resize columns
  $("#objectlist_head").colResizable({liveDrag:true, onDrag:resizecols});

  // select area
  selectarea = document.getElementById('selectarea')

  // parent load screen
  if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display='none';

  // load screen
  if (document.getElementById('hcmsLoadScreen')) document.getElementById('hcmsLoadScreen').style.display='none';
}
</script>
</head>

<body id="hcmsWorkplaceObjectlist" class="hcmsWorkplaceObjectlist" onresize="resizecols();">

<!-- load screen --> 
<div id="hcmsLoadScreen" class="hcmsLoadScreen" style="display:inline;"></div>

<!-- select area --> 
<div id="selectarea" class="hcmsSelectArea"></div>

<!-- context menu --> 
<div id="contextLayer" style="position:absolute; min-width:150px; max-width:200px; height:100px; z-index:10; left:20px; top:20px; visibility:hidden;"> 
  <form name="contextmenu_message" method="post" action="" target="">
    <input type="hidden" name="contextmenustatus" value="" />
    <input type="hidden" name="contextmenulocked" value="false" />
    <input type="hidden" name="action" value="" />
    <input type="hidden" name="xpos" value="" />
    <input type="hidden" name="ypos" value="" />
    <input type="hidden" name="messageuser" value="<?php echo $user; ?>" />  
    <input type="hidden" name="message_id" value="" />
    <input type="hidden" name="multiobject" value="" />
    <input type="hidden" name="token" value="" />
    
    <table class="hcmsContextMenu hcmsTableStandard" style="width:100%;">
      <tr>
        <td>
          <a href="javascript:void(0);" id="href_edit" onClick="if (buttonaction ('edit')) hcms_createContextmenuItem ('edit');"><img src="<?php echo getthemelocation(); ?>img/button_edit.png" id="img_edit" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['edit'][$lang]); ?></a><br />     
          <hr />
          <a href="javascript:void(0);" id="href_delete" onClick="if (buttonaction ('delete')) hcms_createContextmenuItem ('delete');"><img src="<?php echo getthemelocation(); ?>img/button_delete.png" id="img_delete" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?></a><br />
          <hr />
          <a href="javascript:void(0);" id="href_refresh" onClick="document.location.reload();"><img src="<?php echo getthemelocation(); ?>img/button_view_refresh.png" id="img_refresh" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['refresh'][$lang]); ?></a>
        </td>
      </tr>    
    </table>
  </form>
</div>

<!-- Table Header -->
<div id="detailviewLayer" style="position:fixed; top:0; left:0; bottom:32px; margin:0; padding:0; width:100%; z-index:3; visibility:visible;">
  <table id="objectlist_head" cols="5" style="border-collapse:collapse; border:0; border-spacing:0; padding:0; width:100%; height:20px;"> 
    <tr>
      <td id="c1" onClick="hcms_sortTable(1);" class="hcmsTableHeader hcmsCell" style="width:160px;">
        &nbsp;<?php echo getescapedtext ($hcms_lang['subject'][$lang]); ?>&nbsp;
      </td>
      <td id="c2" onClick="hcms_sortTable(2);" class="hcmsTableHeader hcmsCell" style="width:200px;">
        &nbsp;<?php echo getescapedtext ($hcms_lang['recipient'][$lang]); ?>&nbsp;
      </td> 
      <td id="c3" onClick="hcms_sortTable(3);" class="hcmsTableHeader hcmsCell" style="width:120px;">
        &nbsp;<?php echo getescapedtext ($hcms_lang['date'][$lang]); ?>&nbsp;
      </td>
      <td id="c4" onClick="hcms_sortTable(4);" class="hcmsTableHeader hcmsCell" style="width:60px;">
        &nbsp;<?php echo getescapedtext ($hcms_lang['action'][$lang]); ?>&nbsp;
      </td>
      <td id="c5" onClick="hcms_sortTable(5);" class="hcmsTableHeader hcmsCell">
        &nbsp;<?php echo getescapedtext ($hcms_lang['sender'][$lang]); ?>&nbsp;
      </td>
    </tr>
  </table>
  
  <div id="objectLayer" style="position:fixed; top:20px; left:0; bottom:32px; margin:0; padding:0; width:100%; z-index:2; visibility:visible; overflow-x:hidden; overflow-y:scroll;">
    <table id="objectlist" name="objectlist" cols="5" style="table-layout:fixed; border-collapse:collapse; border:0; border-spacing:0; padding:0; width:100%;">
    <?php 
    echo $listview;
    ?>
    </table>
    <br />
    <div id="detailviewReset" style="width:100%; height:20px; z-index:3; visibility:visible;" onMouseOver="hcms_hideContextmenu();"></div>
  </div>
</div>

<?php
// expanding
if (empty ($mgmt_config['explorer_paging']) && $objects_total >= $end)
{
  $next_start = $objects_counted + 1;
?>
<!-- status bar incl. more button -->
<div id="ButtonMore" class="hcmsMore" style="position:fixed; bottom:0; width:100%; height:30px; z-index:4; visibility:visible; text-align:left;" onclick="if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display='inline'; window.location='<?php echo "?start=".url_encode($next_start)."&search=".url_encode($search); ?>';" onMouseOver="hcms_hideContextmenu();" title="<?php echo getescapedtext ($hcms_lang['more'][$lang]); ?>">
  <div style="padding:8px; float:left;"><?php echo $next_start." / ".$objects_total." ".(!$is_mobile ? getescapedtext ($hcms_lang['objects'][$lang]) : ""); ?></div>
  <div style="margin:0 auto; text-align:center;"><img src="<?php echo getthemelocation(); ?>img/button_arrow_down.png" class="hcmsButtonSizeSquare" style="border:0;" /></div>
</div>
<?php
}
// paging
elseif (!empty ($mgmt_config['explorer_paging']) && ($start > 0 || $objects_total > $end))
{
  // start positions (inital start is 0 and not 1)
  $previous_start = $start - intval ($mgmt_config['explorer_list_maxitems']);
  $next_start = $objects_counted + 1;
?>
<!-- status bar incl. previous and next buttons -->
<div id="ButtonPrevious" class="hcmsMore" style="position:fixed; bottom:0; left:0; right:50%; height:30px; z-index:4; visibility:visible; text-align:left;" <?php if ($start > 0) { ?>onclick="if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display='inline'; window.location='<?php echo "?start=".url_encode($previous_start)."&search=".url_encode($search); ?>';"<?php } ?> onMouseOver="hcms_hideContextmenu();" title="<?php echo getescapedtext ($hcms_lang['back'][$lang]); ?>">
  <div style="padding:8px; float:left;"><?php echo ($start + 1)."-".$next_start." / ".$objects_total." ".(!$is_mobile ? getescapedtext ($hcms_lang['objects'][$lang]) : ""); ?></div>
  <div style="margin:0 auto; text-align:center;"><img src="<?php echo getthemelocation(); ?>img/button_arrow_up.png" class="hcmsButtonSizeSquare" style="border:0;" /></div>
</div>
<div id="ButtonNext" class="hcmsMore" style="position:fixed; bottom:0; left:50%; right:0; height:30px; z-index:4; visibility:visible; text-align:left;" <?php if ($objects_total > $end) { ?>onclick="if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display='inline'; window.location='<?php echo "?start=".url_encode($next_start)."&search=".url_encode($search); ?>';"<?php } ?> onMouseOver="hcms_hideContextmenu();" title="<?php echo getescapedtext ($hcms_lang['forward'][$lang]); ?>">
  <div style="margin:0 auto; text-align:center;"><img src="<?php echo getthemelocation(); ?>img/button_arrow_down.png" class="hcmsButtonSizeSquare" style="border:0;" /></div>
</div>
<?php
}
// status bar without buttons
else
{
  if ($objects_counted > 0) $next_start = $objects_counted + 1;
  else $next_start = 0;
?>
<!-- status bar -->
<div id="StatusBar" class="hcmsStatusbar" style="position:fixed; bottom:0; width:100%; height:30px; z-index:3; visibility:visible; text-align:left;" onMouseOver="hcms_hideContextmenu();">
  <div style="margin:auto; padding:8px; float:left;"><?php echo $next_start." / ".$objects_total." ".(!$is_mobile ? getescapedtext ($hcms_lang['objects'][$lang]) : ""); ?></div>
</div>
<?php
}
?>

<!-- initalize -->
<script type="text/javascript">
initalize();
</script>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>
