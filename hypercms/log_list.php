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
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");


// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('site')) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// file name of event log
$logfile = "event.log";
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/navigator.css">
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceObjectlist">

<div id="detailviewLayer" style="position:absolute; top:0px; left:0px; width:100%; height:100%; z-index:1; visibility:visible;">
  <table cellpadding="0" cellspacing="0" cols="5" style="border:0; width:100%; height:20px; table-layout:fixed;"> 
    <tr>
      <td width="105" onClick="hcms_sortTable(0);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo getescapedtext ($hcms_lang['type'][$lang]); ?>
      </td>
      <td width="120" onClick="hcms_sortTable(1);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo getescapedtext ($hcms_lang['datetime'][$lang]); ?>
      </td>
      <?php if (!$is_mobile) { ?>
      <td width="180" onClick="hcms_sortTable(2);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo getescapedtext ($hcms_lang['source'][$lang]); ?>
      </td>
      <td width="55" onClick="hcms_sortTable(3);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo getescapedtext ($hcms_lang['code'][$lang]); ?>
      </td>    
      <td onClick="hcms_sortTable(4);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo getescapedtext ($hcms_lang['description'][$lang]); ?>
      </td>
      <td width="16" class="hcmsTableHeader">
        &nbsp;
      </td>
      <?php } ?>   
    </tr>
  </table>

  <div id="objectLayer" style="position:absolute; top:20px; left:0px; width:100%; height:100%; z-index:2; visibility:visible; overflow:scroll;">
    <table id="objectlist" name="objectlist" cellpadding="0" cellspacing="0" cols="5" style="border:0; width:100%; table-layout:fixed;">
<?php
if (@file_exists ($mgmt_config['abs_path_data']."log/".$logfile))
{
  $items_row = 0;
  
  // load log file
  $event_array = file ($mgmt_config['abs_path_data']."log/".$logfile);

  if ($event_array != false && sizeof ($event_array) >= 1)
  {
    foreach ($event_array as $event)
    {
      list ($date, $source, $type, $errorcode, $description) = explode ("|", trim ($event));
      
      $description = str_replace ("\\", "/", $description);
      $description = str_replace ("'", "`", $description);
      $description = str_replace ("\"", "`", $description);
      
      if (strlen ($description) > 50) 
      {
        $description_short = substr ($description, 0, 50)."...";
      }
      else $description_short = $description;
      
      // define event type name
      // error
      if ($type == "error")
      {
        $type_name = getescapedtext ($hcms_lang['error'][$lang]);
        $icon = "log_alert.gif";
      }
      // warning
      elseif ($type == "warning")
      {
        $type_name = getescapedtext ($hcms_lang['warning'][$lang]);
        $icon = "log_warning.gif";
      }
      // information
      else
      {
        $type_name = getescapedtext ($hcms_lang['information'][$lang]);
        $icon = "log_info.gif";
      }

      echo "<tr id=g".$items_row." align=\"left\" valign=\"top\">
  <td id=h".$items_row."_0 width=\"105\" nowrap=\"nowrap\">&nbsp; <a href=# onClick=\"hcms_openWindow('popup_log.php?description=".urlencode ($description)."','alert','scrollbars=yes','600','200');\"><img src=\"".getthemelocation()."img/".$icon."\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp; ".$type_name."</a></td>
  <td id=h".$items_row."_1 width=\"120\" nowrap=\"nowrap\">&nbsp; ".$date."</td>\n";
  if (!$is_mobile) echo "<td id=h".$items_row."_2 width=\"180\" nowrap=\"nowrap\">&nbsp; ".$source."</td>
  <td id=h".$items_row."_3 width=\"55\" nowrap=\"nowrap\">&nbsp; ".$errorcode."</td>
  <td id=h".$items_row."_4>&nbsp; <a href=# onClick=\"hcms_openWindow('popup_log.php?description=".urlencode ($description)."','alert','scrollbars=yes','600','200');\">".$description_short."</a></td>\n";
  echo "</tr>\n"; 

      $items_row++;      
    }
  }
}
?>
    </table>
  </div>
</div>

</body>
</html>
