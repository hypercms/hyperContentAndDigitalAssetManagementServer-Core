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
// language file
require_once ("language/".getlanguagefile ($lang)); 


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
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>

<?php
// invert button colors
if (!empty ($hcms_themeinvertcolors))
{
  echo "<style>";
  // invert all buttons
  echo invertcolorCSS ("div.hcmsToolbarBlock", 100);
  // revert on hover
  echo invertcolorCSS (".hcmsButton:hover, div.hcmsToolbarBlock select", 100);
  echo "</style>";
}
?>

<script type="text/javascript">

function selectgroup (selObj)
{
  if (selObj.options[selObj.selectedIndex].value != "")
  {
    parent.frames['mainFrame'].location.href = 'group_edit_form.php?site=<?php echo url_encode($site); ?>&group_name=' + selObj.options[selObj.selectedIndex].value;
  }
  else
  {
    parent.frames['mainFrame'].location.href = 'empty.php';
  }
}

function deletegroup ()
{
  var form = document.forms['group_delete'];
  
  if (form.elements['group_name'].value == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-select-an-option'][$lang]); ?>"));
    return false;
  }
  else
  {
    check = confirm (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['warning'][$lang]); ?> \n<?php echo getescapedtext ($hcms_lang['the-selected-item-will-be-removed'][$lang]); ?> \n<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-this-item'][$lang]); ?>")); 
    if (check == true) form.submit(); 
    return check;
  }
}

function checkForm_chars (text, exclude_chars)
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
		alert("<?php echo getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters'][$lang]); ?>\n" + addText);
		return false;
	}
  else
  {
		return true;
	}
}

function checkForm ()
{
  var form = document.forms['group_create'];
  
  if (form.elements['group_name'].value.trim() == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['a-name-is-required'][$lang]); ?>"));
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
</script>
</head>

<body class="hcmsWorkplaceControlWallpaper" onload="<?php echo $add_onload; ?>">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:20px;"); ?>

<?php echo showmessage ($show, 650, 60, $lang, "position:fixed; left:15px; top:15px; "); ?>

<div class="hcmsLocationBar">
  <?php if (!$is_mobile) { ?>
  <table class="hcmsTableNarrow">
    <tr>
      <td class="hcmsHeadline"> <?php echo getescapedtext ($site." &gt; ".$hcms_lang['group-management'][$lang]); ?> </td>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>  
  </table>
  <?php } else { ?>
  <span class="hcmsHeadlineTiny" style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo getescapedtext ($site." &gt; ".$hcms_lang['group-management'][$lang]); ?></span>
  <?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar" style="width:<?php if ($is_mobile) echo "380px;"; else echo "620px;"; ?>">
  <div class="hcmsToolbarBlock" style="padding:2px;">
    <form name="group_delete" action="control_group_menu.php" method="post">
      <input type="hidden" name="site" value="<?php echo $site; ?>" />
      <input type="hidden" name="action" value="group_delete" />
      <?php echo getescapedtext ($hcms_lang['group'][$lang]); ?>
      <select name="group_name" onChange="selectgroup(this);" style="width:<?php if ($is_mobile) echo "130px"; else echo "200px"; ?>;" title="<?php echo getescapedtext ($hcms_lang['group-name'][$lang]); ?>">
        <option value=""><?php echo getescapedtext ($hcms_lang['select'][$lang]); ?></option>
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
            natcasesort ($usergrouprecord);
            reset ($usergrouprecord);

            foreach ($usergrouprecord as $group)
            {
              echo "
            <option value=\"".url_encode($group)."\" ".($group_name == $group ? "selected=\"selected\"" : "").">".$group."</option>";
            }
          }
        }
      ?>
      </select>
    </form>
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if (checkglobalpermission ($site, 'group') && checkglobalpermission ($site, 'groupcreate'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('creategroupLayer','','show','hcms_messageLayer','','hide');\" name=\"media_new\" src=\"".getthemelocation()."img/button_usergroup_new.png\" alt=\"".getescapedtext ($hcms_lang['create'][$lang])."\" title=\"".getescapedtext ($hcms_lang['create'][$lang])."\">";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_usergroup_new.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\">";}
    ?>
    <?php
    if (checkglobalpermission ($site, 'group') && checkglobalpermission ($site, 'groupdelete'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"deletegroup();\" name=\"media_delete\" src=\"".getthemelocation()."img/button_usergroup_delete.png\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang])."\">";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_usergroup_delete.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\">";}
    ?> 
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if (file_exists ($mgmt_config['abs_path_cms']."help/adminguide_".$hcms_lang_shortcut[$lang].".pdf") && checkglobalpermission ($site, 'group'))
    {echo "<img onClick=\"hcms_openWindow('help/adminguide_".$hcms_lang_shortcut[$lang].".pdf', 'help', 'scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");\" name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.png\" class=\"hcmsButton hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\">\n";}
    elseif (file_exists ($mgmt_config['abs_path_cms']."help/adminguide_en.pdf") && checkglobalpermission ($site, 'group'))
    {echo "<img onClick=\"hcms_openWindow('help/adminguide_en.pdf', 'help', 'scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");\" name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.png\" class=\"hcmsButton hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\">\n";}
    ?>
  </div>
</div>

<div id="creategroupLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:hidden">
<form name="group_create" action="control_group_menu.php" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="action" value="group_create" />
  
  <table class="hcmsTableStandard" style="width:100%; height:60px;">
    <tr>
      <td style="white-space:nowrap;">
        <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['create'][$lang]); ?></span><br />
        <input type="text" name="group_name" maxlength="100" style="width:160px;" placeholder="<?php echo getescapedtext ($hcms_lang['group-name'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['group-name'][$lang]); ?>" />
        <img name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
      </td>
      <td style="width:38px; text-align:right; vertical-align:top;">
        <img name="hcms_mediaClose1" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose1','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('creategroupLayer','','hide');" />
      </td>         
    </tr>
  </table>
</form>
</div>

</body>
</html>
