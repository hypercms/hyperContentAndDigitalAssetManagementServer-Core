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

// ------------------------------ permission section --------------------------------

// check permissions
if ((!valid_publicationname ($site) && !checkrootpermission ('site')) && !checkrootpermission ('user')) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// file name of event log
if (valid_publicationname ($site)) $logfile = $site.".custom";
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
<script type="text/javascript" src="javascript/jquery/plugins/colResizable-1.5.min.js"></script>
<script type="text/javascript">
function submitToWindow (url, description, windowname, features, width, height)
{
  if (features == undefined) features = 'scrollbars=yes,resizable=yes';
  if (width == undefined) width = 600;
  if (height == undefined) height = 200;
  if (windowname == '') windowname = Math.floor(Math.random()*9999999);
  
  hcms_openWindow('', windowname, features, width, height);
  
  var form = document.forms['log_details'];
  
  form.attributes['action'].value = url;
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
</script>
<style>
.hcmsCell
{
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
</head>

<body class="hcmsWorkplaceObjectlist" style="overflow:hidden;" onresize="resizecols();">

<!-- Table Header -->
<div id="detailviewLayer" style="position:fixed; top:0; left:0; bottom:0; width:100%; z-index:1; visibility:visible;">
  <table id="objectlist_head" style="table-layout:fixed; border-collapse:collapse; border:0; border-spacing:0; padding:0; width:100%; height:20px;"> 
    <tr>
      <td id="c1" onClick="hcms_sortTable(0);" class="hcmsTableHeader" style="width:105px; white-space:nowrap;">
        &nbsp; <?php echo getescapedtext ($hcms_lang['type'][$lang]); ?>
      </td>
      <?php if (!$is_mobile) { ?>
      <td id="c2" onClick="hcms_sortTable(1);" class="hcmsTableHeader" style="width:120px; white-space:nowrap;">
        &nbsp; <?php echo getescapedtext ($hcms_lang['datetime'][$lang]); ?>
      </td>
      <td id="c3" onClick="hcms_sortTable(2);" class="hcmsTableHeader" style="width:180px; white-space:nowrap;">
        &nbsp; <?php echo getescapedtext ($hcms_lang['source'][$lang]); ?>
      </td>
      <td id="c4" onClick="hcms_sortTable(3);" class="hcmsTableHeader" style="width:55px; white-space:nowrap;">
        &nbsp; <?php echo getescapedtext ($hcms_lang['code'][$lang]); ?>
      </td>
      <?php } ?>
      <td id="c5" onClick="hcms_sortTable(4);" class="hcmsTableHeader" style="white-space:nowrap;">
        &nbsp; <?php echo getescapedtext ($hcms_lang['description'][$lang]); ?>
      </td>
    </tr>
  </table>

  <div id="objectLayer" style="position:fixed; top:20px; left:0; bottom:0; width:100%; z-index:2; visibility:visible; overflow-x:hidden; overflow-y:scroll;">
    <table id="objectlist" name="objectlist" style="table-layout:fixed; border-collapse:collapse; border:0; border-spacing:0; padding:0; width:100%;">
<?php
if ($logfile != "" && is_file ($mgmt_config['abs_path_data']."log/".$logfile.".log"))
{
  $items_row = 0;
  
  // load log file
  $event_array = loadlog ($logfile);

  if ($event_array != false && sizeof ($event_array) > 0)
  {
    // reverse array
    $event_array = array_reverse ($event_array);
   
    foreach ($event_array as $event)
    {
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
<tr id=\"g".$items_row."\" style=\"text-align:left; vertical-align:top;\">
  <td id=\"h".$items_row."_0\" class=\"hcmsCol1 hcmsCell\" style=\"width:105px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; padding-left:3px;\">&nbsp;<a href=\"javascript:void(0);\" onClick=\"submitToWindow ('popup_log.php', '".$description."', 'info', 'scrollbars=yes,resizable=yes', '600', '200');\"><img src=\"".getthemelocation()."img/".$icon."\" class=\"hcmsIconList\"> ".$type_name."</a></td>";

      if (!$is_mobile) echo "
  <td id=\"h".$items_row."_1\" class=\"hcmsCol2 hcmsCell\" style=\"width:120px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; padding-left:3px;\"><span style=\"display:none;\">".date ("YmdHi", strtotime ($date))."</span>".showdate ($date, "Y-m-d H:i", $hcms_lang_date[$lang])."</td>
  <td id=\"h".$items_row."_2\" class=\"hcmsCol3 hcmsCell\" style=\"width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; padding-left:3px;\">".$source."</td>
  <td id=\"h".$items_row."_3\" class=\"hcmsCol4 hcmsCell\" style=\"width:55px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; padding-left:3px;\">".$errorcode."</td>";
  
      echo "
  <td id=\"h".$items_row."_4\" class=\"hcmsCol5 hcmsCell\" style=\"white-space:nowrap; overflow:hidden; text-overflow:ellipsis; padding-left:3px;\"><a href=\"javascript:void(0);\" onClick=\"submitToWindow ('popup_log.php', '".$description."', 'info', 'scrollbars=yes,resizable=yes', 600, 400);\">".$description_short."</a></td>
 </tr>";

      $items_row++;
      
      // break if row count is greater than 500
      if ($items_row > 500) break;
    }
  }
}
?>
    </table>
  </div>
</div>

<form target="_blank" method="post" action="" name="log_details">
  <input type="hidden" name="description" value="">
</form>

<!-- initalize -->
<script>
$("#objectlist_head").colResizable({liveDrag:true, onDrag: resizecols});
</script>

</body>
</html>
