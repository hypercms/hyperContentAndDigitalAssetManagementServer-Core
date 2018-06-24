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


// input parameters
$search = getrequest ("search");
$maxhits = getrequest ("maxhits", "numeric");
$next = getrequest ("next");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('desktop')) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// create secure token
$token = createtoken ($user);

// default value for inital max items in list
if ($mgmt_config['explorer_list_maxitems'] == "") $mgmt_config['explorer_list_maxitems'] = 100; 

// define next max number of items on the list 
if ($next != "" && is_int ($next)) $next_max = $next + $mgmt_config['explorer_list_maxitems'];
else $next_max = $mgmt_config['explorer_list_maxitems'];

// generate object list
$objects_counted = 0;
$objects_total = 0;
$listview = "";
$items_row = 0;

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
    list ($message_time, $message_user, $message_type, $ext) = explode (".", $message_file);
    
    if ($message_time > 0 && $items_row < $next_max)
    {  
      $date = date ("Y-m-d H:i", $message_time);

      $mailfile = $message_time.".".$message_user.".mail";
      
      $file_info = getfileinfo ("", $mailfile, "comp");
      $object_name = $file_info['name'];
      
      // open on double click
      $openObject = "onDblClick=\"hcms_openWindow('user_sendlink.php?mailfile=".url_encode($mailfile)."&token=".$token."', '".$message_time."', 'status=yes,scrollbars=no,resizable=yes', 600, 800);\"";
      
      // onclick for marking objects
      $selectclick = "onClick=\"hcms_selectObject(this.id, event); hcms_updateControlMessageMenu();\"";
      
      // set context
      $hcms_setObjectcontext = "style=\"display:block;\" onMouseOver=\"hcms_setMessagecontext('".$message_user."', '".$mailfile."', '".$token."');\" onMouseOut=\"hcms_resetContext();\" ";

      // listview
      $class_image = "class=\"hcmsIconList\"";
      
      // include message file
      if (is_file ($dir.$message_file))
      {
        include ($dir.$message_file);
      
        $recipients = implode (", ", $user_login);
        
        // search
        if (trim ($search) != "" && strpos (" ".$mail_title." ".$mail_body." ".$recipients, trim ($search)) > 0) $found = true;
        else $found = false;
      }
  
      if ($found == true || trim ($search) == "")
      {
        $listview .= "
            <tr id=\"g".$items_row."\" align=\"left\" style=\"cursor:pointer;\" ".$selectclick.">
              <td id=\"h".$items_row."_0\" class=\"hcmsCol1\" style=\"width:100px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\">
                <input id=\"message_id\" type=\"hidden\" value=\"".$mailfile."\" />
                <div id=\"".$items_row."\" class=\"hcmsObjectListMarker\" ".$hcms_setObjectcontext." ".$openObject." >
                    <img src=\"".getthemelocation()."img/".$file_info['icon']."\" ".$class_image." align=\"absmiddle\" />&nbsp;<span title=\"".getescapedtext ($hcms_lang['e-mail'][$lang])."\">".$object_name."</span>&nbsp;
                </div>
              </td>
              <td id=\"h".$items_row."_1\" class=\"hcmsCol2\" style=\"width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\"><span ".$hcms_setObjectcontext." title=\"\">&nbsp;&nbsp;".$mail_title."</span></td>
              <td id=\"h".$items_row."_2\" class=\"hcmsCol3\" style=\"width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\"><span ".$hcms_setObjectcontext." title=\"\">&nbsp;&nbsp;".$recipients."</span></td>
              <td id=\"h".$items_row."_3\" class=\"hcmsCol4\" style=\"width:120px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\"><span ".$hcms_setObjectcontext.">&nbsp;&nbsp;".$date."</span></td>
              <td id=\"h".$items_row."_4\" class=\"hcmsCol5\" style=\"width:60px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\"><span ".$hcms_setObjectcontext.">&nbsp;&nbsp;sent</span></td>
              <td id=\"h".$items_row."_5\" class=\"hcmsCol6\" style=\"white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\"><span ".$hcms_setObjectcontext.">&nbsp;&nbsp;".$message_user."</span></td>
            </tr>";
            
        $items_row++;  
      }
      
      // limit results
      if ($items_row >= $next_max || ($maxhits > 0 && $items_row >= $maxhits)) break;
    }
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
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/navigator.css">
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/contextmenu.js" type="text/javascript"></script>
<script type="text/javascript" src="javascript/jquery/jquery-1.10.2.min.js"></script>
<script type="text/javascript" src="javascript/jquery/plugins/colResizable-1.5.min.js"></script>
<script type="text/javascript">
// context menu
var contextenable = 1;

// set contect menu move options
var contextxmove = 1;
var contextymove = 1;

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
  var c1 = $('#c1').width() + 3;
  var c2 = $('#c2').width() + 3;
  var c3 = $('#c3').width() + 3;
  var c4 = $('#c4').width() + 3;
  var c5 = $('#c5').width() + 3;
  var c6 = $('#c6').width() + 3;

  // set width for table columns
  $('.hcmsCol1').width(c1);
  $('.hcmsCol2').width(c2);
  $('.hcmsCol3').width(c3);
  $('.hcmsCol4').width(c4);
  $('.hcmsCol5').width(c5);
  $('.hcmsCol6').width(c6);
}
</script>
</head>

<body id="hcmsWorkplaceObjectlist" class="hcmsWorkplaceObjectlist" onresize="resizecols();">

<!-- load screen --> 
<div id="hcmsLoadScreen" class="hcmsLoadScreen" style="display:inline;"></div>

<!-- select area --> 
<div id="selectarea" class="hcmsSelectArea"></div>

<!-- context menu --> 
<div id="contextLayer" style="position:absolute; width:150px; height:100px; z-index:10; left:20px; top:20px; visibility:hidden;"> 
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
    
    <table width="150px" cellspacing="0" cellpadding="3" class="hcmsContextMenu">
      <tr>
        <td>
          <a href=# id="href_edit" onClick="if (buttonaction ('edit')) hcms_createContextmenuItem ('edit');"><img src="<?php echo getthemelocation(); ?>img/button_edit.png" id="img_edit" align="absmiddle" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['edit'][$lang]); ?></a><br />     
          <hr />
          <a href=# id="href_delete" onClick="if (buttonaction ('delete')) hcms_createContextmenuItem ('delete');"><img src="<?php echo getthemelocation(); ?>img/button_delete.png" id="img_delete" align="absmiddle" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?></a><br />
          <hr />
          <a href=# id="href_refresh" onClick="document.location.reload();"><img src="<?php echo getthemelocation(); ?>img/button_view_refresh.png" id="img_refresh" align="absmiddle" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['refresh'][$lang]); ?></a>
        </td>
      </tr>    
    </table>
  </form>
</div>

<div id="detailviewLayer" style="position:fixed; top:0; left:0; bottom:30px; margin:0; padding:0; width:100%; z-index:3; visibility:visible;">
  <table id="objectlist_head" cellpadding="0" cellspacing="0" style="border:0; width:100%; height:20px; table-layout:fixed;"> 
    <tr>
      <td id="c1" onClick="hcms_sortTable(0);" class="hcmsTableHeader" style="width:97px; white-space:nowrap;">
        &nbsp; <?php echo getescapedtext ($hcms_lang['name'][$lang]); ?>
      </td>
      <td id="c2" onClick="hcms_sortTable(1);" class="hcmsTableHeader" style="width:177px; white-space:nowrap;">
        &nbsp; <?php echo getescapedtext ($hcms_lang['subject'][$lang]); ?>
      </td>
      <td id="c3" onClick="hcms_sortTable(2);" class="hcmsTableHeader" style="width:197px; white-space:nowrap;">
        &nbsp; <?php echo getescapedtext ($hcms_lang['recipient'][$lang]); ?>
      </td> 
      <td id="c4" onClick="hcms_sortTable(3);" class="hcmsTableHeader" style="width:117px; white-space:nowrap;">
        &nbsp; <?php echo getescapedtext ($hcms_lang['date'][$lang]); ?>
      </td>
      <td id="c5" onClick="hcms_sortTable(4);" class="hcmsTableHeader" style="width:57px; white-space:nowrap;">
        &nbsp; <?php echo getescapedtext ($hcms_lang['action'][$lang]); ?>
      </td>
      <td id="c6" onClick="hcms_sortTable(5);" class="hcmsTableHeader" style="white-space:nowrap;">
        &nbsp; <?php echo getescapedtext ($hcms_lang['sender'][$lang]); ?>
      </td>
    </tr>
  </table>
  
  <div id="objectLayer" style="position:fixed; top:20px; left:0; bottom:30px; margin:0; padding:0; width:100%; z-index:2; visibility:visible; overflow-x:hidden; overflow-y:scroll;">
    <table id="objectlist" name="objectlist" cellpadding="0" cellspacing="0" cols="6" style="border:0; width:100%; table-layout:fixed;">
    <?php 
    echo $listview;
    ?>
    </table>
    <br /><div id="detailviewReset" style="width:100%; height:20px; z-index:3; visibility:visible;" onMouseOver="hcms_hideContextmenu();"></div>
  </div>
</div>

<?php
if ($objects_counted >= $next_max)
{
?>
<!-- status bar incl. more button -->
<div id="ButtonMore" class="hcmsMore" style="position:fixed; bottom:0px; width:100%; height:30px; z-index:4; visibility:visible; text-align:left;" onclick="window.location='<?php echo $_SERVER['PHP_SELF']."?next=".url_encode($objects_counted); ?>';" onMouseOver="hcms_hideContextmenu();" title="<?php echo getescapedtext ($hcms_lang['sionhcms_lang'][$lang]); ?>">
  <div style="padding:8px; float:left;"><?php echo $objects_counted." / ".$objects_total." ".getescapedtext ($hcms_lang['objects'][$lang]); ?></div>
  <div style="margin-left:auto; margin-right:auto; text-align:center; padding-top:3px;"><img src="<?php echo getthemelocation(); ?>img/button_explorer_more.png" style="border:0;" alt="<?php echo getescapedtext ($hcms_lang['more'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['more'][$lang]); ?>" /></div>
</div>
<?php
}
else
{
?>
<!-- status bar -->
<div id="StatusBar" class="hcmsStatusbar" style="position:fixed; bottom:0px; width:100%; height:30px; z-index:3; visibility:visible; text-align:left;" onMouseOver="hcms_hideContextmenu();">
    <div style="margin:auto; padding:8px; float:left;"><?php echo $objects_counted." / ".$objects_total." ".getescapedtext ($hcms_lang['objects'][$lang]); ?></div>
</div>
<?php
}
?>

<!-- initalize -->
<script>
// resize columns
$("#objectlist_head").colResizable({liveDrag:true, onDrag: resizecols});
// select area
var selectarea = document.getElementById('selectarea');
// load screen
if (document.getElementById('hcmsLoadScreen')) document.getElementById('hcmsLoadScreen').style.display='none';
</script>

</body>
</html>