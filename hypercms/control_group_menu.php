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
// language file
require_once ("language/control_group_menu.inc.php"); 


// input parameters
$action = getrequest ("action");
$site = getrequest_esc ("site", "publicationname");
$group_name = getrequest ("group_name", "objectname");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check group permissions
if (!checkglobalpermission ($site, 'group') || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";

// check if template name is an attribute of a sent string
if (strpos ($group_name, ".php?") > 0)
{
  // extract template name
  $group_name = getattribute ($group_name, "group_name");
}

// include scripts
if (checkglobalpermission ($site, 'group') && checkglobalpermission ($site, 'groupcreate') && $action == "group_create")
{
  $result = creategroup ($site, $group_name, $user);
  
  $add_onload = $result['add_onload'];
  $show = $result['message'];
}
elseif (checkglobalpermission ($site, 'group') && checkglobalpermission ($site, 'groupdelete') && $action == "group_delete")
{
  $result = deletegroup ($site, $group_name, $user);
  
  $add_onload = $result['add_onload'];
  $show = $result['message'];  
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
<!--
function warning_delete()
{
  var form = document.forms['group_delete'];
  
  if (form.elements['group_name'].value == "empty.php")
  {
    alert (hcms_entity_decode("<?php echo $text17[$lang]; ?>"));
    return false;
  }
  else
  {
    check = confirm (hcms_entity_decode("<?php echo $text0[$lang]; ?>:\r<?php echo $text1[$lang]; ?>\r<?php echo $text2[$lang]; ?>")); 
    if (check == true) form.submit(); 
    return check;
  }
}

function checkForm_chars(text, exclude_chars)
{
  exclude_chars = exclude_chars.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
  
	var expr = new RegExp ("[^a-zA-Z0-9" + exclude_chars + "]", "g");
	var separator = ', ';
	var found = text.match(expr); 
	
  if (found)
  {
		var addText = '';
    
		for(var i = 0; i < found.length; i++)
    {
			addText += found[i]+separator;
		}
    
		addText = addText.substr(0, addText.length-separator.length);
		alert("<?php echo $text3[$lang]; ?>:\r"+addText);
		return false;
	}
  else
  {
		return true;
	}
}

function checkForm()
{
  var form = document.forms['group_create'];
  
  if (form.elements['group_name'].value == "")
  {
    alert (hcms_entity_decode("<?php echo $text4[$lang]; ?>"));
    form.elements['group_name'].focus();
    return false;
  }
  
  if (!checkForm_chars(form.elements['group_name'].value, "-_"))
  {
    form.elements['group_name'].focus();
    return false;
  }  
  
  form.submit();
  return true;
}

function goToURL()
{ 
  var i, args=goToURL.arguments; document.returnValue = false;
  for (i=0; i<(args.length-1); i+=2) eval(args[i]+".location='"+args[i+1]+"'");
}
//-->
</script>
</head>

<body class="hcmsWorkplaceControlWallpaper" onLoad="<?php echo $add_onload; ?>">

<table border=0 cellspacing=0 cellpadding=0>
  <tr>
    <td class="hcmsHeadline"><?php echo $text6[$lang]; ?></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
  </tr>  
</table>

<!-- toolbar -->
<div class="hcmsToolbar">
  <div class="hcmsToolbarBlock">
    <?php
    if (checkglobalpermission ($site, 'group') && checkglobalpermission ($site, 'groupcreate'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('creategroupLayer','','show','deletegroupLayer','','hide','editgroupLayer','','hide','hcms_messageLayer','','hide')\" name=\"media_new\" src=\"".getthemelocation()."img/button_usergroup_new.gif\" alt=\"".$text9[$lang]."\" title=\"".$text9[$lang]."\">";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_usergroup_new.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\">";}
    ?>
    <?php
    if (checkglobalpermission ($site, 'group') && checkglobalpermission ($site, 'groupdelete'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('creategroupLayer','','hide','deletegroupLayer','','show','editgroupLayer','','hide','hcms_messageLayer','','hide')\" name=\"media_delete\" src=\"".getthemelocation()."img/button_usergroup_delete.gif\" alt=\"".$text11[$lang]."\" title=\"".$text11[$lang]."\">";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_usergroup_delete.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\">";}
    ?>
    <?php
    if (checkglobalpermission ($site, 'group') && checkglobalpermission ($site, 'groupedit'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('creategroupLayer','','hide','deletegroupLayer','','hide','editgroupLayer','','show','hcms_messageLayer','','hide')\" name=\"media_edit\" src=\"".getthemelocation()."img/button_usergroup_edit.gif\" alt=\"".$text12[$lang]."\" title=\"".$text12[$lang]."\">";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_usergroup_edit.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\">";}
    ?>   
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if (!$is_mobile && file_exists ("help/adminguide_".$lang_shortcut[$lang].".pdf") && checkglobalpermission ($site, 'group'))
    {echo "<a href=# onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('pic_obj_help','','".getthemelocation()."img/button_help_over.gif',1)\" onClick=\"hcms_openWindow('help/adminguide_".$lang_shortcut[$lang].".pdf','help','scrollbars=no,resizable=yes','800','600');\"><img name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".$text50[$lang]."\" title=\"".$text50[$lang]."\"></a>\n";}
    ?>
  </div>
</div>

<?php
echo showmessage ($show, 650, 60, $lang, "position:fixed; left:15px; top:15px; ");
?>

<div id="creategroupLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:hidden">
<form name="group_create" action="control_group_menu.php" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="action" value="group_create" />
  
  <table width="100%" height="60" border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td valign="middle" nowrap="nowrap">
        <span class=hcmsHeadline><?php echo $text9[$lang]; ?></span><br />
        <?php echo $text7[$lang]; ?>:
        <input type="text" name="group_name" maxlength="100" style="width:150px;" />
        <img name="Button" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" />
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose1" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $text16[$lang]; ?>" title="<?php echo $text16[$lang]; ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose1','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('creategroupLayer','','hide');" />
      </td>         
    </tr>
  </table>
</form>
</div>

<div id="deletegroupLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:hidden">
<form name="group_delete" action="control_group_menu.php" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="action" value="group_delete" />
  
  <table width="100%" height="60" border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td valign="middle" nowrap="nowrap">
        <span class=hcmsHeadline><?php echo $text11[$lang]; ?></span><br />
        <?php echo $text13[$lang]; ?>:
        <select name="group_name" style="width:150px;" onChange="hcms_jumpMenu('parent.frames[\'mainFrame\']',this,0)">
          <option value="empty.php">--- <?php echo $text15[$lang]; ?> ---</option>
        <?php
          if (!isset ($usergroupdata) || $usergroupdata == false)
          {
            $usergroupdata = loadfile ($mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");
          }
          
          if ($usergroupdata != false)
          {
            $usergrouprecord = getcontent ($usergroupdata, "<groupname>");

            if ($usergrouprecord != false && sizeof ($usergrouprecord) > 0)
            {
              sort ($usergrouprecord);
              reset ($usergrouprecord);

              foreach ($usergrouprecord as $group)
              {
                echo "<option value=\"group_edit_form.php?site=".url_encode($site)."&preview=yes&group_name=".url_encode($group)."\">".$group."</option>\n";
                $item_option_edit[] = "<option value=\"group_edit_form.php?site=".url_encode($site)."&preview=no&group_name=".url_encode($group)."\">".$group."</option>\n";
              }
            }
          }
        ?>
        </select>
        <img name="Button3" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="warning_delete();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button3','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" />
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose2" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $text16[$lang]; ?>" title="<?php echo $text16[$lang]; ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose2','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('deletegroupLayer','','hide');" />
      </td>           
    </tr>
  </table>
</form>
</div>

<div id="editgroupLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility: hidden">
<form name="group_edit" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  
  <table width="100%" height="60" border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td valign="middle" nowrap="nowrap">
        <span class=hcmsHeadline><?php echo $text12[$lang]; ?></span><br />
        <?php echo $text13[$lang]; ?>:
        <select name="group_name" style="width:150" onChange="hcms_jumpMenu('parent.frames[\'mainFrame\']',this,0)">
          <option value="empty.php">--- <?php echo $text15[$lang]; ?> ---</option>
          <?php
          if (sizeof ($item_option_edit) > 0)
          {
            foreach ($item_option_edit as $edit_option)
            {
              echo $edit_option;
            }
          }
          ?>
        </select>
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose3" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $text16[$lang]; ?>" title="<?php echo $text16[$lang]; ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose3','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('editgroupLayer','','hide');" />
      </td>          
    </tr>
  </table>
</form>
</div>

</body>
</html>
