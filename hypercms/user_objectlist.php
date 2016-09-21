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
$site = getrequest_esc ("site"); // site can be *Null* which is not a valid name!
$group = getrequest_esc ("group", "objectname");
$next = getrequest ("next");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if ($site == "*Null*" && (!checkrootpermission ('user')) || ($site != "*Null*" && !checkglobalpermission ($site, 'user'))) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// create secure token
$token = createtoken ($user);

// default value for inital max items in list
if (empty ($mgmt_config['explorer_list_maxitems'])) $mgmt_config['explorer_list_maxitems'] = 100; 

// define next max number of items on the list 
if ($next != "" && is_numeric ($next)) $next_max = $next + $mgmt_config['explorer_list_maxitems'];
else $next_max = $mgmt_config['explorer_list_maxitems'];

// collect user data
$userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");

$i = 1;

if ($userdata != false && isset ($site))
{
  // get users of certain group
  if ($site != "*Null*" && $group != "")
  {     
    $usernode_array = selectxmlcontent ($userdata, "<user>", "<publication>", $site);
    
    if (is_array ($usernode_array))
    {
      foreach ($usernode_array as $usernode)
      {
        if ($group == "_all")
        {
          $buffer_array = getcontent ($usernode, "<login>");
          $object_array['login'][$i] = $buffer_array[0];
          $buffer_array = getcontent ($usernode, "<userdate>");
          $object_array['date'][$i] = $buffer_array[0];
          $buffer_array = getcontent ($usernode, "<realname>"); 
          $object_array['name'][$i] = $buffer_array[0];
          $buffer_array = getcontent ($usernode, "<email>");
          $object_array['email'][$i] = $buffer_array[0];  
          $i++;
        }
        elseif ($group != "")
        {
          // check publication and group membership
          $memberof_array = selectcontent ($usernode, "<memberof>", "<usergroup>", "*|".$group."|*");
          
          if (is_array ($memberof_array))
          {
            foreach ($memberof_array as $memberof)
            {
              $publication_array = getcontent ($memberof, "<publication>");
  
              if (is_array ($publication_array) && $publication_array[0] == $site)
              {
                $buffer_array = getcontent ($usernode, "<login>");
                $object_array['login'][$i] = $buffer_array[0];
                $buffer_array = getcontent ($usernode, "<userdate>");
                $object_array['date'][$i] = $buffer_array[0];
                $buffer_array = getcontent ($usernode, "<realname>");
                $object_array['name'][$i] = $buffer_array[0]; 
                $buffer_array = getcontent ($usernode, "<email>");
                $object_array['email'][$i] = $buffer_array[0];                   
                $i++;
              }
            }
          }
        } 
      } 
    }    
  }
  // get users of certain publication
  elseif (isset ($site) && $group == "")
  {
    if ($site == "*Null*")
    {
      if (checkadminpermission () || $user == "admin")
      {
        $object_array['login'] = getcontent ($userdata, "<login>");
        $object_array['date'] = getcontent ($userdata, "<userdate>");
        $object_array['name'] = getcontent ($userdata, "<realname>");
        $object_array['email'] = getcontent ($userdata, "<email>");
      }
      else
      {
        $userrecord_big_array = Array();
        
        foreach ($siteaccess as $site_entry)
        {
          $userrecord_array = selectcontent ($userdata, "<user>", "<publication>", $site_entry);
          $userrecord_big_array = array_merge ($userrecord_big_array,  $userrecord_array);
        }
        
        if ($userrecord_big_array != false)
        {
          foreach ($userrecord_big_array as $userrecord_big)
          {
            $buffer_array = getcontent ($userrecord_big, "<login>");
            $object_array['login'][$i] = $buffer_array[0];
            $buffer_array = getcontent ($userrecord_big, "<userdate>");
            $object_array['date'][$i] = $buffer_array[0];
            $buffer_array = getcontent ($userrecord_big, "<realname>");
            $object_array['name'][$i] = $buffer_array[0];
            $buffer_array = getcontent ($userrecord_big, "<email>");
            $object_array['email'][$i] = $buffer_array[0];                
            $i++;          
          }
          
          $object_array['login'] = array_unique ($object_array['login']);
        }        
      }
    }    
    elseif ($site != "*Null*")
    {        
      $userrecord_big_array = selectcontent ($userdata, "<user>", "<publication>", $site);
      
      if ($userrecord_big_array != false)
      {
        foreach ($userrecord_big_array as $userrecord_big)
        {
          $buffer_array = getcontent ($userrecord_big, "<login>");
          $object_array['login'][$i] = $buffer_array[0];
          $buffer_array = getcontent ($userrecord_big, "<userdate>");
          $object_array['date'][$i] = $buffer_array[0];
          $buffer_array = getcontent ($userrecord_big, "<realname>");
          $object_array['name'][$i] = $buffer_array[0];
          $buffer_array = getcontent ($userrecord_big, "<email>");
          $object_array['email'][$i] = $buffer_array[0];              
          $i++;          
        }
      }
    }    
  }
}

// generate user list
$objects_counted = 0;
$objects_total = 0;
$items_row = 0;
$listview = "";

if (@isset ($object_array) && @sizeof ($object_array) > 0)
{
  // get size of user array
  $objects_total = sizeof ($object_array['login']);

  natcasesort ($object_array['login']);
  reset ($object_array['login']);
  
  for ($i = 1; $i <= sizeof ($object_array['login']); $i++)
  {
    $key = key ($object_array['login']);
    
    // subtract admin user
    if ($object_array['login'][$key] == "admin" || $object_array['login'][$key] == "hcms_download") $objects_total--;
    
    if ($object_array['login'][$key] != "admin" && $object_array['login'][$key] != "sys" && $object_array['login'][$key] != "hcms_download" && $items_row < $next_max)
    {
      // open on double click
      if (checkrootpermission ('user') && checkrootpermission ('useredit') || ($site != "*Null*" && checkglobalpermission ($site, 'user') && checkglobalpermission ($site, 'useredit'))) 
      {
        $openUser = "onDblClick=\"window.open('user_edit.php?site=".url_encode($site)."&group=".url_encode($group)."&login=".url_encode($object_array['login'][$key])."&token=".$token."','','status=yes,scrollbars=no,resizable=yes,width=500,height=540');\"";
      }
      else $openUser = "";
      
      // onclick for marking objects
      $selectclick = "onClick=\"hcms_selectObject('".$items_row."', event); hcms_updateControlUserMenu();\" ";
      $setContext = "style=\"display:block;\" onMouseOver=\"hcms_setUsercontext('".$site."', '".$object_array['login'][$key]."', '".$token."');\" onMouseOut=\"hcms_resetContext();\" ";
 
      $listview .= "
            <tr id=\"g".$items_row."\" ".$selectclick." align=\"left\" style=\"cursor:pointer;\">
              <td id=\"h".$items_row."_0\" class=\"hcmsCol1\" style=\"width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\">
                <input id=\"login\" type=\"hidden\" value=\"".$object_array['login'][$key]."\">
                <div ".$openUser." ".$setContext.">
                    <img src=\"".getthemelocation()."img/user.gif\" width=16 height=16 border=0 align=\"absmiddle\" />&nbsp; ".
                    $object_array['login'][$key]."&nbsp;
                </div>
              </td>
              <td id=\"h".$items_row."_1\" class=\"hcmsCol2\" style=\"width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\"><span ".$setContext.">&nbsp;".$object_array['name'][$key]."</span></td>
              <td id=\"h".$items_row."_2\" class=\"hcmsCol3\" style=\"width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\"><span ".$setContext.">&nbsp;".$object_array['email'][$key]."</span></td>
              <td id=\"h".$items_row."_3\" class=\"hcmsCol4\" style=\"white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\"><span ".$setContext.">&nbsp;".$object_array['date'][$key]."</span></td>
            </tr>";
  
      $items_row++;  
    }
    
    next ($object_array['login']);
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
<script src="javascript/main.js" language="JavaScript" type="text/javascript"></script>
<script src="javascript/contextmenu.js" language="JavaScript" type="text/javascript"></script>
<script type="text/javascript" src="javascript/jquery/jquery-1.10.2.min.js"></script>
<script type="text/javascript" src="javascript/jquery/plugins/colResizable-1.5.min.js"></script>
<script language="JavaScript">
<!--
function confirm_delete ()
{
  return confirm(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-this-user'][$lang]); ?>"));
}

function buttonaction (action)
{
  multiobject = document.forms['contextmenu_user'].elements['multiobject'].value;
  object = document.forms['contextmenu_user'].elements['login'].value;
  
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

  // set width for table columns
  $('.hcmsCol1').width(c1);
  $('.hcmsCol2').width(c2);
  $('.hcmsCol3').width(c3);
  $('.hcmsCol4').width(c4);
}

// set contect menu option
var contextxmove = 1;
var contextymove = 1;

// define global variable for popup window name used in contextmenu.js
var session_id = '<?php session_id(); ?>';
//-->
</script>
</head>

<body class="hcmsWorkplaceObjectlist" style="overflow:hidden;">

<div id="contextLayer" style="position:absolute; width:150px; height:100px; z-index:10; left:20px; top:20px; visibility:hidden;"> 
  <form name="contextmenu_user" method="post" action="" target="">
    <input type="hidden" name="contextmenustatus" value="" />
    <input type="hidden" name="contextmenulocked" value="false" />
    <input type="hidden" name="action" value="" />
    <input type="hidden" name="xpos" value="" />
    <input type="hidden" name="ypos" value="" />
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="group" value="<?php echo $group; ?>" />
    <input type="hidden" name="login" value="" />
    <input type="hidden" name="multiobject" value="" />
    <input type="hidden" name="token" value="" />
    
    <table width="150" cellspacing="0" cellpadding="3" class="hcmsContextMenu">
      <tr>
        <td>
          <?php $tblrow = 1;  
          if (($site == "*Null*" && checkrootpermission ('user') && checkrootpermission ('useredit')) || ($site != "*Null*" && checkglobalpermission ($site, 'user') && checkglobalpermission ($site, 'useredit'))) { 
          ?>
          <a href=# id="href_edit" onClick="if (buttonaction('edit')) hcms_createContextmenuItem ('edit');"><img src="<?php echo getthemelocation(); ?>img/button_user_edit.gif" id="img_edit" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo getescapedtext ($hcms_lang['edit'][$lang]); ?></a><br />     
          <hr />
          <?php }
          if (($site == "*Null*" && checkrootpermission ('user') && checkrootpermission ('userdelete')) || ($site != "*Null*" && checkglobalpermission ($site, 'user') && checkglobalpermission ($site, 'userdelete'))) {
          ?>
          <a href=# id="href_delete" onClick="if (buttonaction('delete')) hcms_createContextmenuItem ('delete');"><img src="<?php echo getthemelocation(); ?>img/button_user_delete.gif" id="img_delete" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?></a><br />
          <hr />        
          <?php } ?>   
          <a href=# id="href_refresh" onClick="document.location.reload();"><img src="<?php echo getthemelocation(); ?>img/button_view_refresh.gif" id="img_refresh" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo getescapedtext ($hcms_lang['refresh'][$lang]); ?></a>
        </td>
      </tr>    
    </table>
  </form>
</div>

<div id="detailviewLayer" style="position:fixed; top:0px; left:0px; bottom:30px; width:100%; z-index:1; visibility:visible;">
  <table id="objectlist_head" cellpadding="0" cellspacing="0" cols="4" style="border:0; width:100%; height:20px; table-layout:fixed;"> 
    <tr>
      <td id="c1" onClick="hcms_sortTable(0);" class="hcmsTableHeader" style="width:180px; white-space:nowrap;">
        &nbsp; <?php echo getescapedtext ($hcms_lang['user'][$lang]); ?>
      </td>
      <td id="c2" onClick="hcms_sortTable(1);" class="hcmsTableHeader" style="width:180px; white-space:nowrap;">
        &nbsp; <?php echo getescapedtext ($hcms_lang['name'][$lang]); ?>
      </td>
      <td id="c3" onClick="hcms_sortTable(2);" class="hcmsTableHeader" style="width:300px; white-space:nowrap;">
        &nbsp; <?php echo getescapedtext ($hcms_lang['e-mail'][$lang]); ?>
      </td> 
      <td id="c4" onClick="hcms_sortTable(3);" class="hcmsTableHeader" style="white-space:nowrap;">
        &nbsp; <?php echo getescapedtext ($hcms_lang['date-created'][$lang]); ?>
      </td>
      <td class="hcmsTableHeader" style="width:16px;">
        &nbsp;
      </td>
    </tr>
  </table>
  
  <div id="objectLayer" style="position:fixed; top:20px; left:0px; bottom:30px; width:100%; z-index:2; visibility:visible; overflow-y:scroll;">
    <table id="objectlist" name="objectlist" cellpadding="0" cellspacing="0" cols="4" style="border:0; width:100%; table-layout:fixed;">
  <?php
  echo $listview;
  ?>
    </table>
    <div style="width:100%; height:2px; z-index:2; visibility:visible;" onMouseOver="hcms_hideContextmenu();"></div>
  </div>
</div>

<?php
if ($objects_counted >= $next_max)
{
?>
<!-- status bar incl. more button -->
<div id="ButtonMore" class="hcmsMore" style="position:fixed; bottom:0; width:100%; height:30px; z-index:4; visibility:visible; text-align:left;" onclick="window.location='<?php echo $_SERVER['PHP_SELF']."?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location)."&next=".url_encode($objects_counted); ?>';" onMouseOver="hcms_hideContextmenu();" title="<?php echo getescapedtext ($hcms_lang['versionhcms_lang'][$lang]); ?>">
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

<!-- initalize -->
<script language="JavaScript">
<!--
$("#objectlist_head").colResizable({liveDrag:true, onDrag: resizecols});
//-->
</script>
  
</body>
</html>
