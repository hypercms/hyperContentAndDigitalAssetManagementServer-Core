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
if ($action == "group_create" && checkglobalpermission ($site, 'group') && checkglobalpermission ($site, 'groupcreate'))
{
  $result = creategroup ($site, $group_name, $user);
  
  $add_onload = $result['add_onload'];
  $show = $result['message'];
}
elseif ($action == "group_delete" && checkglobalpermission ($site, 'group') && checkglobalpermission ($site, 'groupdelete'))
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
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
<?php
// invert colors
if (!empty ($hcms_themeinvertcolors))
{
  echo "<style>";
  echo invertcolorCSS ($hcms_themeinvertcolors);
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

<body class="hcmsWorkplaceControl" onload="<?php echo $add_onload; ?>">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:15px;"); ?>

<?php echo showmessage ($show, 660, 70, $lang, "position:fixed; left:10px; top:10px; "); ?>

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
  <span style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo getescapedtext ($site." &gt; ".$hcms_lang['group-management'][$lang]); ?></span>
  <?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar" style="width:<?php if ($is_mobile) echo "380px;"; else echo "620px;"; ?>">
  <div class="hcmsToolbarBlock" style="padding:2px;">
    <form name="group_delete" action="control_group_menu.php" method="post">
      <input type="hidden" name="site" value="<?php echo $site; ?>" />
      <input type="hidden" name="action" value="group_delete" />
      
      <span class="hcmsInvertColor"><?php echo getescapedtext ($hcms_lang['group'][$lang]); ?></span>
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
    {echo "<img class=\"hcmsButton hcmsHoverColor hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('creategroupLayer','','show','hcms_messageLayer','','hide');\" name=\"media_new\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_usergroup_new.png\" alt=\"".getescapedtext ($hcms_lang['create'][$lang])."\" title=\"".getescapedtext ($hcms_lang['create'][$lang])."\">";}
    else
    {echo "<img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_usergroup_new.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\">";}
    ?>
    <?php
    if (checkglobalpermission ($site, 'group') && checkglobalpermission ($site, 'groupdelete'))
    {echo "<img class=\"hcmsButton hcmsHoverColor hcmsButtonSizeSquare\" onClick=\"deletegroup();\" name=\"media_delete\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_usergroup_delete.png\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang])."\">";}
    else
    {echo "<img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_usergroup_delete.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\">";}
    ?> 
  </div>
  <div class="hcmsToolbarBlock">
    <?php echo showhelpbutton ("adminguide", checkglobalpermission ($site, 'group'), $lang, "", "hcmsHoverColor"); ?>
  </div>
</div>

<!-- create group -->
<div id="creategroupLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:70px; left:10px; top:10px; visibility:hidden">
  <form name="group_create" action="control_group_menu.php" method="post" onsubmit="return checkForm();">
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="action" value="group_create" />
    
    <table class="hcmsTableNarrow" style="width:100%; height:60px;">
      <tr>
        <td style="white-space:nowrap;">
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['create'][$lang]); ?></span><br />
          <input type="text" name="group_name" maxlength="100" style="width:<?php if ($is_mobile) echo "200px"; else echo "80%"; ?>;" placeholder="<?php echo getescapedtext ($hcms_lang['group-name'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['group-name'][$lang]); ?>" />
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
