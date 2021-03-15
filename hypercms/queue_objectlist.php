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
$site = getrequest_esc ("site", "publicationname");
$queueuser = getrequest_esc ("queueuser", "objectname");
$start = getrequest ("start", "numeric", 0);

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (
     ($queueuser != "" && !checkrootpermission ('desktop')) || 
     ($queueuser == "" && !checkrootpermission ('site') && !checkrootpermission ('user'))
   ) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// initialize
$objects_counted = 0;
$objects_total = 0;
$items_row = -1;
$listview = "";

// create secure token
$token = createtoken ($user);

// default value for inital max items in list
if (empty ($mgmt_config['explorer_list_maxitems'])) $mgmt_config['explorer_list_maxitems'] = 100; 

// define next max number of items on the list 
if (is_numeric ($start)) $end = $start + $mgmt_config['explorer_list_maxitems'];
else $end = $mgmt_config['explorer_list_maxitems'];

// generate object list
$queue_array = rdbms_getqueueentries ("", $site, "", $queueuser);

// write object entries
if (is_array ($queue_array) && sizeof ($queue_array) > 0)
{
  $objects_total = sizeof ($queue_array); 

  foreach ($queue_array as $queue)
  {
    // break loop if maximum has been reached
    if (($items_row + 1) >= $end) break;

    if ($queue['queue_id'] != "" && $queue['action'] != "" && ($queue['object_id'] > 0 || $queue['objectpath'] != "") && $queue['user'] != "")
    {  
      $queue_id = $queue['queue_id'];
      $queue_action = $queue['action'];
      $queue_user = $queue['user'];
      $queue_date = date ("Y-m-d H:i", strtotime($queue['date']));

      // object
      if ($queue['objectpath'] != "")
      {
        $temp_site = getpublication ($queue['objectpath']);  
        $temp_location_esc = getlocation ($queue['objectpath']);
        $temp_cat = getcategory ($temp_site, $temp_location_esc);
        $temp_location = deconvertpath ($temp_location_esc, "file");
        $temp_location_name = getlocationname ($temp_site, $temp_location_esc, $temp_cat, "path");
        
        $temp_object = getobject ($queue['objectpath']);
        $temp_object = correctfile ($temp_location, $temp_object, $user);
        
        // if object exists based on correctfile
        if (valid_locationname ($temp_location) && valid_objectname ($temp_object))
        {
          // count valid objects 
          $items_row++;
      
          // skip rows for paging
          if (!empty ($mgmt_config['explorer_paging']) && $items_row < $start) continue;
                    
          $file_info = getfileinfo ($temp_site, $temp_location.$temp_object, $temp_cat);
          
          // transformation for folders
          if ($temp_object == ".folder") 
          {
            $temp_object_name = getobject ($temp_location_name);
            $temp_location_name = getlocation ($temp_location_name);
          }
          else $temp_object_name = $file_info['name'];
  
          // open on double click
          $openObject = "onDblClick=\"hcms_openWindow('frameset_content.php?ctrlreload=yes&site=".url_encode($temp_site)."&cat=".url_encode($temp_cat)."&location=".url_encode($temp_location_esc)."&page=".url_encode($temp_object)."&token=".$token."', '".$queue_id."', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes,status=no', ".windowwidth("object").", ".windowheight("object").");\"";
          
          // onclick for marking objects
          $selectclick = "onClick=\"hcms_selectObject(this.id, event); hcms_updateControlQueueMenu();\"";
          
          // set context
          $hcms_setObjectcontext = "style=\"display:block;\" onMouseOver=\"hcms_setQueuecontext('".$temp_site."', '".$temp_cat."', '".$temp_location_esc."', '".$temp_object."', '".$temp_object_name."', '".$file_info['type']."', '".$queue_user."', '".$queue_id."', '".$token."');\" onMouseOut=\"hcms_resetContext();\" ";
  
          // listview - view option for un/published objects
          if ($file_info['published'] == false) $class_image = "class=\"hcmsIconList hcmsIconOff\"";
          else $class_image = "class=\"hcmsIconList\"";
    
          $listview .= "
                <tr id=\"g".$items_row."\" style=\"cursor:pointer;\" ".$selectclick.">
                  <td id=\"h".$items_row."_0\" class=\"hcmsCol1 hcmsCell\" style=\"width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\">
                    <div id=\"".$items_row."\" class=\"hcmsObjectListMarker\" ".$hcms_setObjectcontext." ".$openObject." >
                      <a data-objectpath=\"".$queue_id."\" data-href=\"javascript:void(0);\">
                        <img src=\"".getthemelocation()."img/".$file_info['icon']."\" ".$class_image." /> ".$temp_object_name."&nbsp;
                      </a>
                    </div>
                  </td>";
                  
          if (!$is_mobile) $listview .= "
                  <td id=\"h".$items_row."_1\" class=\"hcmsCol2 hcmsCell\" style=\"width:100px;\"><span ".$hcms_setObjectcontext." title=\"".$temp_site."\">&nbsp;&nbsp;".$temp_site."</span></td>
                  <td id=\"h".$items_row."_2\" class=\"hcmsCol3 hcmsCell\" style=\"width:200px;\"><span ".$hcms_setObjectcontext." title=\"".$temp_location_name."\">&nbsp;&nbsp;".$temp_location_name."</span></td>
                  <td id=\"h".$items_row."_3\" class=\"hcmsCol4 hcmsCell\" style=\"width:140px;\"><span style=\"display:none;\">".date ("YmdHi", strtotime ($queue_date))."</span><span ".$hcms_setObjectcontext.">&nbsp;&nbsp;".showdate ($queue_date, "Y-m-d H:i", $hcms_lang_date[$lang])."</span></td>
                  <td id=\"h".$items_row."_4\" class=\"hcmsCol5 hcmsCell\" style=\"width:80px;\"><span ".$hcms_setObjectcontext.">&nbsp;&nbsp;".$queue_action."</span></td>
                  <td id=\"h".$items_row."_5\" class=\"hcmsCol6 hcmsCell\" style=\"\"><span ".$hcms_setObjectcontext.">&nbsp;&nbsp;".$queue_user."</span></td>";
                  
          $listview .= "
                </tr>";
        }
      }
      // mail
      elseif ($queue['object_id'] > 0 && $user == $queue_user)
      {
        // count valid objects 
        $items_row++;
    
        // skip rows for paging
        if (!empty ($mgmt_config['explorer_paging']) && $items_row < $start) continue;
        
        $mailfile = $queue['object_id'].".".$queue_user.".mail";
        $temp_cat = "comp";
        
        $file_info = getfileinfo ("", $mailfile, $temp_cat);
        $temp_object_name = $file_info['name'];
        
        // open on double click
        $openObject = "onDblClick=\"hcms_openWindow('user_sendlink.php?mailfile=".url_encode($mailfile)."&token=".$token."', '".$queue_id."', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes,status=no', 600, 900);\"";
        
        // onclick for marking objects
        $selectclick = "onClick=\"hcms_selectObject(this.id, event); hcms_updateControlQueueMenu();\"";
        
        // set context
        $hcms_setObjectcontext = "style=\"display:block;\" onMouseOver=\"hcms_setQueuecontext('', '".$temp_cat."', '', '".$mailfile."', '".$temp_object_name."', 'mail', '".$queue_user."', '".$queue_id."', '".$token."');\" onMouseOut=\"hcms_resetContext();\" ";

        // listview
        $class_image = "class=\"hcmsIconList\"";
    
        $listview .= "
              <tr id=\"g".$items_row."\" style=\"cursor:pointer;\" ".$selectclick.">
                <td id=\"h".$items_row."_0\" class=\"hcmsCol1 hcmsCell\" style=\"width:180px;\">
                  <div id=\"".$items_row."\" class=\"hcmsObjectListMarker\" ".$hcms_setObjectcontext." ".$openObject.">
                    <a data-objectpath=\"".$queue_id."\" data-href=\"javascript:void(0);\">
                      <img src=\"".getthemelocation()."img/".$file_info['icon']."\" ".$class_image." /> <span title=\"".getescapedtext ($hcms_lang['e-mail'][$lang])."\">".$temp_object_name."</span>&nbsp;
                    </a>
                  </div>
                </td>";
                
          if (!$is_mobile) $listview .= "
                <td id=\"h".$items_row."_1\" class=\"hcmsCol2 hcmsCell\" style=\"width:100px;\"><span ".$hcms_setObjectcontext.">&nbsp;</span></td>
                <td id=\"h".$items_row."_2\" class=\"hcmsCol3 hcmsCell\" style=\"width:200px;\"><span ".$hcms_setObjectcontext.">&nbsp;</span></td>
                <td id=\"h".$items_row."_3\" class=\"hcmsCol4 hcmsCell\" style=\"width:140px;\"><span style=\"display:none;\">".date ("YmdHi", strtotime ($queue_date))."</span><span ".$hcms_setObjectcontext.">".showdate ($queue_date, "Y-m-d H:i", $hcms_lang_date[$lang])."</span></td>
                <td id=\"h".$items_row."_4\" class=\"hcmsCol5 hcmsCell\" style=\"width:80px;\"><span ".$hcms_setObjectcontext.">".$queue_action."</span></td>
                <td id=\"h".$items_row."_5\" class=\"hcmsCol6 hcmsCell\" style=\"\"><span ".$hcms_setObjectcontext.">".$queue_user."</span></td>";
                
          $listview .= "
              </tr>";
      }
      // queue entry not valid for user
      else $objects_total--;
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
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script type="text/javascript" src="javascript/main.min.js"></script>
<script type="text/javascript" src="javascript/contextmenu.min.js"></script>
<script type="text/javascript" src="javascript/jquery/jquery-3.5.1.min.js"></script>
<script type="text/javascript" src="javascript/jquery/plugins/colResizable.min.js"></script>
<style type="text/css">
.hcmsHead
{
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.hcmsCell
{
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  padding-left: 3px; 
}
</style>
<script type="text/javascript">

// define global variable for popup window name used in contextmenu.js
var session_id = '<?php echo session_id(); ?>';

function confirm_delete ()
{
  return confirm(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-this-entry'][$lang]); ?>"));
}

function buttonaction (action)
{
  multiobject = document.forms['contextmenu_queue'].elements['multiobject'].value;
  object = document.forms['contextmenu_queue'].elements['page'].value;
  
  if (action == "edit" && object != "") return true;
  else if (action == "delete" && object != "") return true;
  else return false;
}

function resizecols()
{
  // get width of table header columns
  var c1 = $('#c1').width();
  var c2 = $('#c2').width();
  var c3 = $('#c3').width();
  var c4 = $('#c4').width();
  var c5 = $('#c5').width();
  var c6 = $('#c6').width();

  // set width for table columns
  $('.hcmsCol1').width(c1);
  $('.hcmsCol2').width(c2);
  $('.hcmsCol3').width(c3);
  $('.hcmsCol4').width(c4);
  $('.hcmsCol5').width(c5);
  $('.hcmsCol6').width(c6);
}

function initialize ()
{
  // resize columns
  $("#objectlist_head").colResizable({liveDrag:true, onDrag:resizecols});

  // select area
  selectarea = document.getElementById('selectarea');
}
</script>
</head>

<body id="hcmsWorkplaceObjectlist" class="hcmsWorkplaceObjectlist" onresize="resizecols();">

<!-- select area --> 
<div id="selectarea" class="hcmsSelectArea"></div>

<!-- context menu --> 
<div id="contextLayer" style="position:absolute; min-width:150px; max-width:200px; height:80px; z-index:10; left:20px; top:20px; visibility:hidden;"> 
  <form name="contextmenu_queue" method="post" action="" target="">
    <input type="hidden" name="contextmenustatus" value="" />
    <input type="hidden" name="contextmenulocked" value="false" />
    <input type="hidden" name="action" value="" />
    <input type="hidden" name="xpos" value="" />
    <input type="hidden" name="ypos" value="" />
    <input type="hidden" name="site" value="" />
    <input type="hidden" name="cat" value="" />
    <input type="hidden" name="location" value="" />
    <input type="hidden" name="page" value="" />
    <input type="hidden" name="pagename" value="" />
    <input type="hidden" name="filetype" value="" />  
    <input type="hidden" name="queueuser" value="<?php echo $queueuser; ?>" />  
    <input type="hidden" name="queue_id" value="" />
    <input type="hidden" name="multiobject" value="" />
    <input type="hidden" name="token" value="" />
    <input type="hidden" name="convert_type" value="" />
    <input type="hidden" name="convert_cfg" value="" />
    
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
<div id="detailviewLayer" style="position:fixed; top:0; left:0; margin:0; padding:0; width:100%; z-index:2; visibility:visible;">
  <table id="objectlist_head" cols="6" style="border-collapse:collapse; border:0; border-spacing:0; padding:0; width:100%; height:20px;"> 
    <tr>
      <td id="c1" onClick="hcms_sortTable(0);" class="hcmsTableHeader hcmsHead" style="width:180px;">
        &nbsp;<?php echo getescapedtext ($hcms_lang['name'][$lang]); ?>&nbsp;
      </td>
      <?php if (!$is_mobile) { ?>
      <td id="c2" onClick="hcms_sortTable(1);" class="hcmsTableHeader hcmsHead" style="width:100px;">
        &nbsp;<?php echo getescapedtext ($hcms_lang['publication'][$lang]); ?>&nbsp;
      </td>
      <td id="c3" onClick="hcms_sortTable(2);" class="hcmsTableHeader hcmsHead" style="width:200px;">
        &nbsp;<?php echo getescapedtext ($hcms_lang['location'][$lang]); ?>&nbsp;
      </td> 
      <td id="c4" onClick="hcms_sortTable(3);" class="hcmsTableHeader hcmsHead" style="width:140px;">
        &nbsp;<?php echo getescapedtext ($hcms_lang['date'][$lang]); ?>&nbsp;
      </td>
      <td id="c5" onClick="hcms_sortTable(4);" class="hcmsTableHeader hcmsHead" style="width:80px;">
        &nbsp;<?php echo getescapedtext ($hcms_lang['action'][$lang]); ?>&nbsp;
      </td>
      <td id="c6" onClick="hcms_sortTable(5);" class="hcmsTableHeader hcmsHead">
        &nbsp;<?php echo getescapedtext ($hcms_lang['user'][$lang]); ?>&nbsp;
      </td>
      <?php } ?>
    </tr>
  </table>
  
  <div id="objectLayer" style="position:fixed; top:20px; left:0; bottom:32px; margin:0; padding:0; width:100%; z-index:2; visibility:visible; overflow-x:hidden; overflow-y:scroll;">
    <table id="objectlist" name="objectlist" style="table-layout:fixed; border-collapse:collapse; border:0; border-spacing:0; padding:0; width:100%;">
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
<div id="ButtonMore" class="hcmsMore" style="position:fixed; bottom:0; width:100%; height:30px; z-index:4; visibility:visible; text-align:left;" onclick="if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display='inline'; window.location='<?php echo "?site=".url_encode($site)."&queueuser=".url_encode($queueuser)."&start=".url_encode($next_start); ?>';" onMouseOver="hcms_hideContextmenu();" title="<?php echo getescapedtext ($hcms_lang['more'][$lang]); ?>">
  <div style="padding:8px; float:left;"><?php echo $next_start." / ".number_format ($objects_total, 0, ".", " ")." ".(!$is_mobile ? getescapedtext ($hcms_lang['objects'][$lang]) : ""); ?></div>
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
<div id="ButtonPrevious" class="hcmsMore" style="position:fixed; bottom:0; left:0; right:50%; height:30px; z-index:4; visibility:visible; text-align:left;" <?php if ($start > 0) { ?>onclick="if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display='inline'; window.location='<?php echo "?site=".url_encode($site)."&queueuser=".url_encode($queueuser)."&start=".url_encode($previous_start); ?>';"<?php } ?> onMouseOver="hcms_hideContextmenu();" title="<?php echo getescapedtext ($hcms_lang['back'][$lang]); ?>">
  <div style="padding:8px; float:left;"><?php echo ($start + 1)."-".$next_start." / ".number_format ($objects_total, 0, ".", " ")." ".(!$is_mobile ? getescapedtext ($hcms_lang['objects'][$lang]) : ""); ?></div>
  <div style="margin:0 auto; text-align:center;"><img src="<?php echo getthemelocation(); ?>img/button_arrow_up.png" class="hcmsButtonSizeSquare" style="border:0;" /></div>
</div>
<div id="ButtonNext" class="hcmsMore" style="position:fixed; bottom:0; left:50%; right:0; height:30px; z-index:4; visibility:visible; text-align:left;" <?php if ($objects_total > $end) { ?>onclick="if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display='inline'; window.location='<?php echo "?site=".url_encode($site)."&queueuser=".url_encode($queueuser)."&start=".url_encode($next_start); ?>';"<?php } ?> onMouseOver="hcms_hideContextmenu();" title="<?php echo getescapedtext ($hcms_lang['forward'][$lang]); ?>">
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
<div id="StatusBar" class="hcmsStatusbar" style="position:fixed; bottom:0; width:100%; height:30px; z-index:4; visibility:visible; text-align:left;" onMouseOver="hcms_hideContextmenu();">
  <div style="margin:auto; padding:8px; float:left;"><?php echo $next_start." / ".number_format ($objects_total, 0, ".", " ")." ".(!$is_mobile ? getescapedtext ($hcms_lang['objects'][$lang]) : ""); ?></div>
</div>
<?php
}
?>

<!-- initialize -->
<script type="text/javascript">
initialize();
</script>

<?php includefooter(); ?>
</body>
</html>
