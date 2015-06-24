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


// input parameters
$site = getrequest_esc ("site", "publicationname");
$queueuser = getrequest_esc ("queueuser", "objectname");
$next = getrequest ("next");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (
     ($queueuser != "" && !checkrootpermission ('desktop')) || 
     ($queueuser == "" && !checkrootpermission ('site')) || 
     (valid_publicationname ($site) && $mgmt_config[$site]['dam'] == true)
   ) killsession ($user);

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

$queue_array = rdbms_getqueueentries ("", $site, "", $queueuser);

// write object entries
if (is_array ($queue_array) && @sizeof ($queue_array) >= 1)
{
  $objects_total = sizeof ($queue_array); 

  foreach ($queue_array as $queue)
  {
    if ($queue['queue_id'] != "" && $queue['action'] != "" && $queue['objectpath'] != "" && $queue['user'] != "" && $items_row < $next_max)
    {  
      $queue_id = $queue['queue_id'];
      $action = $queue['action']; 
      $queueuser = $queue['user'];
      $date = substr ($queue['date'], 0, -3);

      $site = getpublication ($queue['objectpath']);

      $location_esc = getlocation ($queue['objectpath']);
      $cat = getcategory ($site, $location_esc);
      $location = deconvertpath ($location_esc, "file");
      $location_name = getlocationname ($site, $location_esc, $cat, "path");
      
      $object = getobject ($queue['objectpath']);
      $object = correctfile ($location, $object, $user);
      
      // if objet exists based on correctfile
      if (valid_locationname ($location) && valid_objectname ($object))
      {              
        $file_info = getfileinfo ($site, $location.$object, $cat);
        
        // transformation for folders
        if ($object == ".folder") 
        {
          $object_name = getobject ($location_name);
          $location_name = getlocation ($location_name);
        }
        else $object_name = $file_info['name'];

        // open on double click
        $openObject = "onDblClick=\"hcms_openWindow('frameset_content.php?ctrlreload=yes&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($object)."&token=".$token."', '".$queue_id."', 'status=yes,scrollbars=no,resizable=yes', '800', '600');\"";
        // onclick for marking objects
        $selectclick = "onClick=\"hcms_selectObject('".$items_row."', event); hcms_updateControlQueueMenu();\"";
        // set context
        $hcms_setObjectcontext = "style=\"display:block;\" onMouseOver=\"hcms_setQueuecontext('".$site."', '".$cat."', '".$location_esc."', '".$object."', '".$object_name."', '".$file_info['type']."', '".$queueuser."', '".$queue_id."', '".$token."');\" onMouseOut=\"hcms_resetContext();\" ";

        // listview - view option for un/published objects
        if ($file_info['published'] == false) $class_image = "class=\"hcmsIconList hcmsIconOff\"";
        else $class_image = "class=\"hcmsIconList\"";
  
        $listview .= "<tr id=g".$items_row." align=\"left\" style=\"cursor:pointer;\" ".$selectclick.">
                <td id=h".$items_row."_0 width=\"180\" nowrap=\"nowrap\">
                  <input id=\"queue_id\" type=\"hidden\" value=\"".$queue_id."\" />
                  <div ".$hcms_setObjectcontext." ".$openObject." >
                      <img src=\"".getthemelocation()."img/".$file_info['icon']."\" ".$class_image." align=\"absmiddle\" />&nbsp;
                      <span title=\"".$object_name."\">".showshorttext($object_name, 20)."</span>&nbsp;
                  </div>
                </td>\n";
        if (!$is_mobile) $listview .= "<td id=h".$items_row."_1 width=\"100\" nowrap=\"nowrap\"><span ".$hcms_setObjectcontext." title=\"".$site."\">&nbsp;&nbsp;".showshorttext($site, 20)."</span></td>
                <td id=h".$items_row."_2 width=\"200\" nowrap=\"nowrap\"><span ".$hcms_setObjectcontext." title=\"".$location_name."\">&nbsp;&nbsp;".showshorttext($location_name, 20)."</span></td>
                <td id=h".$items_row."_3 width=\"120\" nowrap=\"nowrap\"><span ".$hcms_setObjectcontext.">&nbsp;&nbsp;".$date."</span></td>
                <td id=h".$items_row."_4 width=\"60\" nowrap=\"nowrap\"><span ".$hcms_setObjectcontext.">&nbsp;&nbsp;".$action."</span></td>
                <td id=h".$items_row."_5 nowrap=\"nowrap\"><span ".$hcms_setObjectcontext.">&nbsp;&nbsp;".$queueuser."</span></td>\n";
        $listview .= "</tr>\n";
    
        $items_row++;  
      }
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
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/navigator.css">
<script src="javascript/main.js" language="JavaScript" type="text/javascript"></script>
<script src="javascript/contextmenu.js" language="JavaScript" type="text/javascript"></script>
<script language="JavaScript">
<!--
// context menu
var contextenable = 1;

// set contect menu move options
var contextxmove = 1;
var contextymove = 1;

// define global variable for popup window name used in contextmenu.js
var session_id = '<?php echo session_id(); ?>';

function confirm_delete ()
{
  return confirm(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-this-entry'][$lang]); ?>"));
}

function getdoc_height ()
{
	if (self.innerHeight) // all except Explorer
	{
		return self.innerHeight;
	}
	else if (document.documentElement && document.documentElement.clientHeight) // Explorer 6 Strict Mode	
	{
		return document.documentElement.clientHeight;
	}
	else if (document.body) // other Explorers
	{
		return document.body.clientHeight;
	}
  else return false;
}

function buttonaction (action)
{
  multiobject = document.forms['contextmenu_queue'].elements['multiobject'].value;
  object = document.forms['contextmenu_queue'].elements['page'].value;
  
  if (action == "edit" && object != "") return true;
  else if (action == "delete" && object != "") return true;
  else return false;
}

function adjust_height ()
{
  height = getdoc_height();
  
  setheight = height - 20 - 30;
  document.getElementById('objectLayer').style.height = setheight + "px";
}
//-->
</script>
</head>

<body id="hcmsWorkplaceObjectlist" style="overflow:hidden;" class="hcmsWorkplaceObjectlist" onload="adjust_height();" onresize="adjust_height();">

<div id="contextLayer" style="position:absolute; width:150px; height:100px; z-index:10; left:20px; top:20px; visibility:hidden;"> 
  <form name="contextmenu_queue" method="post" action="" target="">
    <input type="hidden" name="contextmenustatus" value="" />
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
    
    <table width="150px" cellspacing="0" cellpadding="3" class="hcmsContextMenu">
      <tr>
        <td>
          <a href=# id="href_edit" onClick="if (buttonaction ('edit')) hcms_createContextmenuItem ('edit');"><img src="<?php echo getthemelocation(); ?>img/button_file_edit.gif" id="img_edit" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo getescapedtext ($hcms_lang['edit'][$lang]); ?></a><br />     
          <hr />
          <a href=# id="href_delete" onClick="if (buttonaction ('delete')) hcms_createContextmenuItem ('delete');"><img src="<?php echo getthemelocation(); ?>img/button_file_delete.gif" id="img_delete" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?></a><br />
          <hr />
          <a href=# id="href_refresh" onClick="document.location.reload();"><img src="<?php echo getthemelocation(); ?>img/button_view_refresh.gif" id="img_refresh" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo getescapedtext ($hcms_lang['refresh'][$lang]); ?></a>
        </td>
      </tr>    
    </table>
  </form>
</div>

<div id="detailviewLayer" style="position:absolute; top:0px; left:0px; margin:0; padding:0; width:100%; height:100%; z-index:3; visibility:visible;">
  <table cellpadding="0" cellspacing="0" cols="6" style="border:0; width:100%; height:20px; table-layout:fixed;"> 
    <tr>
      <td width="180" onClick="hcms_sortTable(0);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo getescapedtext ($hcms_lang['name'][$lang]); ?>
      </td>
      <?php if (!$is_mobile) { ?>
      <td width="100" onClick="hcms_sortTable(1);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo getescapedtext ($hcms_lang['publication'][$lang]); ?>
      </td>
      <td width="200" onClick="hcms_sortTable(2);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo getescapedtext ($hcms_lang['location'][$lang]); ?>
      </td> 
      <td width="120" onClick="hcms_sortTable(3);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo getescapedtext ($hcms_lang['date'][$lang]); ?>
      </td>
      <td width="60" onClick="hcms_sortTable(4);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo getescapedtext ($hcms_lang['action'][$lang]); ?>
      </td>
      <td onClick="hcms_sortTable(5);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo getescapedtext ($hcms_lang['user'][$lang]); ?>
      </td>                    
      <td width="16" class="hcmsTableHeader">
        &nbsp;
      </td>
      <?php } ?>   
    </tr>
  </table>
  
  <div id="objectLayer" style="position:absolute; width:100%; height:100%; top:20px; left:0px; margin:0; padding:0; z-index:2; visibility:visible; overflow:scroll;">
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
<div id="ButtonMore" class="hcmsMore" style="position:fixed; bottom:0; width:100%; height:30px; z-index:4; visibility:visible; text-align:left;" onclick="window.location.href='<?php echo $_SERVER['PHP_SELF']."?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&next=".url_encode($objects_counted); ?>';" onMouseOver="hcms_hideContextmenu();" title="<?php echo getescapedtext ($hcms_lang['sionhcms_lang'][$lang]); ?>">
  <div style="padding:8px; float:left;"><?php echo $objects_counted." / ".$objects_total." ".getescapedtext ($hcms_lang['objects'][$lang]); ?></div>
  <div style="margin-left:auto; margin-right:auto; text-align:center; padding-top:3px;"><img src="<?php echo getthemelocation(); ?>img/button_explorer_more.gif" style="border:0;" alt="<?php echo getescapedtext ($hcms_lang['more'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['more'][$lang]); ?>" /></div>
</div>
<?php
}
else
{
?>
<!-- status bar -->
<div id="StatusBar" class="hcmsStatusbar" style="position:fixed; bottom:0; width:100%; height:30px; z-index:3; visibility:visible; text-align:left;" onMouseOver="hcms_hideContextmenu();">
    <div style="margin:auto; padding:8px; float:left;"><?php echo $objects_counted." / ".$objects_total." ".getescapedtext ($hcms_lang['objects'][$lang]); ?></div>
</div>
<?php
}
?>

<!-- adjust height -->
<script language="JavaScript">
<!--
adjust_height();
//-->
</script>

</body>
</html>
