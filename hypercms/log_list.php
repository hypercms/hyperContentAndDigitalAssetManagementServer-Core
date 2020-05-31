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
$site = getrequest ("site", "publicationname");
$start = getrequest ("start", "numeric", 0);

// ------------------------------ permission section --------------------------------

// check permissions
if ((!valid_publicationname ($site) && !checkrootpermission ('site')) && !checkrootpermission ('user')) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// initalize
$objects_counted = 0;
$objects_total = 0;
$items_row = 0;

// default value for inital max items in list
if (empty ($mgmt_config['explorer_list_maxitems'])) $mgmt_config['explorer_list_maxitems'] = 100; 

// define next max number of items on the list 
if (is_numeric ($start)) $end = $start + $mgmt_config['explorer_list_maxitems'];
else $end = $mgmt_config['explorer_list_maxitems'];

// file name of event log
if (valid_publicationname ($site)) $logfile = $site.".publication";
else $logfile = "event";
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/navigator.css">
<script type="text/javascript" src="javascript/click.js"></script>
<script type="text/javascript" src="javascript/main.js"></script>
<script type="text/javascript" src="javascript/jquery/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="javascript/jquery/plugins/colResizable.min.js"></script>
<style>
.hcmsCell
{
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
<script type="text/javascript">
function submitToWindow (date, source, type, errorcode, description)
{
  var features = 'scrollbars=yes,resizable=yes';
  var width = 700;
  var height = 700;
  var windowname = Math.floor(Math.random()*9999999);
  
  hcms_openWindow('', windowname, features, width, height);
  
  var form = document.forms['log_details'];
  
  form.attributes['action'].value = 'popup_log.php';
  form.elements['date'].value = date;
  form.elements['source'].value = source;
  form.elements['type'].value = type;
  form.elements['errorcode'].value = errorcode;
  form.elements['description'].value = description;
  form.target = windowname;
  form.submit();
}

function resizecols()
{
  // get width of table header columns
  var c1 = $('#c1').width();
  var c2 = $('#c2').width();
  var c3 = $('#c3').width();
  var c4 = $('#c4').width();
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
}
</script>
</head>

<body class="hcmsWorkplaceObjectlist" style="overflow:hidden;" onresize="resizecols();">

<!-- Table Header -->
<div id="detailviewLayer" style="position:fixed; top:0; left:0; bottom:32px; width:100%; z-index:1; visibility:visible;">
  <table id="objectlist_head" cols="5" style="border-collapse:collapse; border:0; border-spacing:0; padding:0; width:100%; height:20px;"> 
    <tr>
      <td id="c1" onClick="hcms_sortTable(0);" class="hcmsTableHeader hcmsCell" style="width:105px;">
        &nbsp; <?php echo getescapedtext ($hcms_lang['type'][$lang]); ?>
      </td>
      <?php if (!$is_mobile) { ?>
      <td id="c2" onClick="hcms_sortTable(1);" class="hcmsTableHeader hcmsCell" style="width:120px;">
        &nbsp; <?php echo getescapedtext ($hcms_lang['datetime'][$lang]); ?>
      </td>
      <td id="c3" onClick="hcms_sortTable(2);" class="hcmsTableHeader hcmsCell" style="width:180px;">
        &nbsp; <?php echo getescapedtext ($hcms_lang['source'][$lang]); ?>
      </td>
      <td id="c4" onClick="hcms_sortTable(3);" class="hcmsTableHeader hcmsCell" style="width:55px;">
        &nbsp; <?php echo getescapedtext ($hcms_lang['code'][$lang]); ?>
      </td>
      <?php } ?>
      <td id="c5" onClick="hcms_sortTable(4);" class="hcmsTableHeader hcmsCell">
        &nbsp; <?php echo getescapedtext ($hcms_lang['description'][$lang]); ?>
      </td>
    </tr>
  </table>

  <div id="objectLayer" style="position:fixed; top:20px; left:0; bottom:32px; width:100%; z-index:2; visibility:visible; overflow-x:hidden; overflow-y:scroll;">
    <table id="objectlist" name="objectlist" cols="5" style="table-layout:fixed; border-collapse:collapse; border:0; border-spacing:0; padding:0; width:100%;">
<?php
if ($logfile != "" && is_file ($mgmt_config['abs_path_data']."log/".$logfile.".log"))
{
  $items_row = -1;
  
  // load log file
  $event_array = loadlog ($logfile);

  // get size of user array
  $objects_total = sizeof ($event_array);

  if ($event_array != false && $objects_total > 0)
  {
    // reverse array
    $event_array = array_reverse ($event_array);
   
    foreach ($event_array as $event)
    {
      // break loop if maximum has been reached
      if (($items_row + 1) >= $end) break;

      if ($event != "")
      {
        // count valid objects 
        $items_row++;

        // skip rows for paging
        if (!empty ($mgmt_config['explorer_paging']) && $items_row < $start) continue;

        // extract data from log record
        list ($date, $source, $type, $errorcode, $description) = explode ("|", trim ($event));
        
        $description = str_replace ("\\", "/", $description);
        $description = str_replace ("'", "`", $description);
        $description = str_replace ("\"", "`", $description);
        
        // escape special characters
        $description = html_encode (specialchr_decode ($description));
        
        if (strlen ($description) > 150) 
        {
          $description_short = substr ($description, 0, 150)."...";
        }
        else $description_short = $description;

        // define event type name
        // error
        if ($type == "error")
        {
          $type_name = getescapedtext ($hcms_lang['error'][$lang]);
          $icon = "log_alert.png";
        }
        // warning
        elseif ($type == "warning")
        {
          $type_name = getescapedtext ($hcms_lang['warning'][$lang]);
          $icon = "log_warning.png";
        }
        // information
        else
        {
          $type_name = getescapedtext ($hcms_lang['information'][$lang]);
          $icon = "log_info.png";
        }

        echo "
  <tr id=\"g".$items_row."\" style=\"text-align:left; vertical-align:top; cursor:pointer;\" onClick=\"submitToWindow ('".$date."', '".$source."', '".$type."', '".$errorcode."', '".$description."');\">
    <td id=\"h".$items_row."_0\" class=\"hcmsCol1 hcmsCell\" style=\"padding-left:3px; width:105px;\"><img src=\"".getthemelocation()."img/".$icon."\" class=\"hcmsIconList\"> ".$type_name."</td>";

        if (!$is_mobile) echo "
    <td id=\"h".$items_row."_1\" class=\"hcmsCol2 hcmsCell\" style=\"padding-left:3px; width:120px;\"><span style=\"display:none;\">".date ("YmdHi", strtotime ($date))."</span>".showdate ($date, "Y-m-d H:i", $hcms_lang_date[$lang])."</td>
    <td id=\"h".$items_row."_2\" class=\"hcmsCol3 hcmsCell\" style=\"padding-left:3px; width:180px;\">".$source."</td>
    <td id=\"h".$items_row."_3\" class=\"hcmsCol4 hcmsCell\" style=\"padding-left:3px; width:55px;\">".$errorcode."</td>";

        echo "
    <td id=\"h".$items_row."_4\" class=\"hcmsCol5 hcmsCell\" style=\"padding-left:3px;\">".$description_short."</td>
  </tr>";
      }
      // subtract empty entries
      else $objects_total--;
    }
  }
}
?>
    </table>
  </div>
</div>

<?php
// objects counted
if ($items_row > 0) $objects_counted = $items_row;
else $objects_counted = 0;

// expanding
if (empty ($mgmt_config['explorer_paging']) && $objects_total >= $end)
{
  $next_start = $objects_counted + 1;
?>
<!-- status bar incl. more button -->
<div id="ButtonMore" class="hcmsMore" style="position:fixed; bottom:0; width:100%; height:30px; z-index:4; visibility:visible; text-align:left;" onclick="if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display='inline'; window.location='<?php echo "?site=".url_encode($site)."&start=".url_encode($next_start); ?>';" onMouseOver="hcms_hideContextmenu();" title="<?php echo getescapedtext ($hcms_lang['more'][$lang]); ?>">
  <div style="padding:8px; float:left;"><?php echo $next_start." / ".number_format ($objects_total, 0, ".", " ")." ".(!$is_mobile ? getescapedtext ($hcms_lang['items'][$lang]) : ""); ?></div>
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
<div id="ButtonPrevious" class="hcmsMore" style="position:fixed; bottom:0; left:0; right:50%; height:30px; z-index:4; visibility:visible; text-align:left;" <?php if ($start > 0) { ?>onclick="if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display='inline'; window.location='<?php echo "?site=".url_encode($site)."&start=".url_encode($previous_start); ?>';"<?php } ?> onMouseOver="hcms_hideContextmenu();" title="<?php echo getescapedtext ($hcms_lang['back'][$lang]); ?>">
  <div style="padding:8px; float:left;"><?php echo ($start + 1)."-".$next_start." / ".number_format ($objects_total, 0, ".", " ")." ".(!$is_mobile ? getescapedtext ($hcms_lang['items'][$lang]) : ""); ?></div>
  <div style="margin:0 auto; text-align:center;"><img src="<?php echo getthemelocation(); ?>img/button_arrow_up.png" class="hcmsButtonSizeSquare" style="border:0;" /></div>
</div>
<div id="ButtonNext" class="hcmsMore" style="position:fixed; bottom:0; left:50%; right:0; height:30px; z-index:4; visibility:visible; text-align:left;" <?php if ($objects_total > $end) { ?>onclick="if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display='inline'; window.location='<?php echo "?site=".url_encode($site)."&start=".url_encode($next_start); ?>';"<?php } ?> onMouseOver="hcms_hideContextmenu();" title="<?php echo getescapedtext ($hcms_lang['forward'][$lang]); ?>">
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
  <div style="margin:auto; padding:8px; float:left;"><?php echo $next_start." / ".number_format ($objects_total, 0, ".", " ")." ".(!$is_mobile ? getescapedtext ($hcms_lang['items'][$lang]) : ""); ?></div>
</div>
<?php
}
?>

<form target="_blank" method="post" action="" name="log_details">
  <input type="hidden" name="date" value="" />
  <input type="hidden" name="source" value="" />
  <input type="hidden" name="type" value="" />
  <input type="hidden" name="errorcode" value="" />
  <input type="hidden" name="description" value="" />
</form>

<!-- initalize -->
<script type="text/javascript">
initalize();
</script>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>
