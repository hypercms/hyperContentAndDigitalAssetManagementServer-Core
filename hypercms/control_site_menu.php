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
require_once ("language/control_site_menu.inc.php"); 


// input parameters
$action = getrequest_esc ("action");
$site = getrequest_esc ("site"); // site can be *Null*
$site_name = getrequest_esc ("site_name", "publicationname");
$token = getrequest ("token"); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('site')) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";

// include scripts
if (checkrootpermission ('site') && checkrootpermission ('sitecreate') && $action == "site_create" && checktoken ($token, $user))
{
  $result = createpublication ($site_name, $user);
    
  $add_onload =  $result['add_onload'];
  $show = $result['message'];  
}
elseif (checkrootpermission ('site') && checkrootpermission ('sitedelete') && $action == "site_delete" && checktoken ($token, $user))
{
  $result = deletepublication ($site_name, $user);
  
  $add_onload =  $result['add_onload'];
  $show = $result['message'];  
}

// security token
$token_new = createtoken ($user);
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
  var form = document.forms['site_delete'];
  
  if (form.elements['site_name'].value == "empty.php")
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
		alert("<?php echo $text3[$lang]; ?>: "+addText);
		return false;
	}
  else
  {
		return true;
	}
}

function checkForm()
{
  var form = document.forms['site_create'];
  
  if(form.elements['site_name'].value == "")
  {
    alert(hcms_entity_decode("<?php echo $text4[$lang]; ?>"));
    form.elements['site_name'].focus();
    return false;
  }
  
  if (!checkForm_chars (form.elements['site_name'].value, "-_"))
  {
    form.elements['site_name'].focus();
    return false;
  }
  
  form.submit();
  return true;
}
//-->
</script>
</head>

<body class="hcmsWorkplaceControlWallpaper" onLoad="<?php echo $add_onload; ?>">

<div class="hcmsLocationBar">
  <table border=0 cellspacing=0 cellpadding=0>
    <tr>
      <td class="hcmsHeadline"><?php echo $text6[$lang]; ?></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>  
  </table>
</div>

<!-- toolbar -->
<div class="hcmsToolbar">
  <div class="hcmsToolbarBlock">
    <?php
    if (checkrootpermission ('site')  && checkrootpermission ('sitecreate'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createsiteLayer','','show','deletesiteLayer','','hide','editsiteLayer','','hide','hcms_messageLayer','','hide')\" name=\"media_new\" src=\"".getthemelocation()."img/button_site_new.gif\" alt=\"".$text9[$lang]."\" title=\"".$text9[$lang]."\" />";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_site_new.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";}
    ?>
    <?php
    if (checkrootpermission ('site')  && checkrootpermission ('sitedelete'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createsiteLayer','','hide','deletesiteLayer','','show','editsiteLayer','','hide','hcms_messageLayer','','hide')\" name=\"media_delete\" src=\"".getthemelocation()."img/button_site_delete.gif\" alt=\"".$text11[$lang]."\" title=\"".$text11[$lang]."\" />";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_site_delete.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";}
    ?>
    <?php
    if (checkrootpermission ('site')  && checkrootpermission ('siteedit'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createsiteLayer','','hide','deletesiteLayer','','hide','editsiteLayer','','show','hcms_messageLayer','','hide')\" name=\"media_edit\" src=\"".getthemelocation()."img/button_site_edit.gif\" alt=\"".$text12[$lang]."\" title=\"".$text12[$lang]."\" />";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_site_edit.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";}
    ?>
    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if (!$is_mobile && file_exists ("help/adminguide_".$lang_shortcut[$lang].".pdf") && checkrootpermission ('site'))
    {echo "<a href=# onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('pic_obj_help','','".getthemelocation()."img/button_help_over.gif',1)\" onClick=\"hcms_openWindow('help/adminguide_".$lang_shortcut[$lang].".pdf','help','scrollbars=no,resizable=yes','800','600');\"><img name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".$text50[$lang]."\" title=\"".$text50[$lang]."\" /></a>\n";}
    ?>
  </div>
</div>

<?php
if ($show != "") echo showmessage ($show, 650, 60, $lang, "position:absolute; left:15px; top:15px; ");
?>

<div id="createsiteLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:hidden;">
<form name="site_create" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="action" value="site_create" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table width="100%" height="60" border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td valign="middle">
        <span class=hcmsHeadline><?php echo $text9[$lang]; ?></span><br />
        <?php echo $text7[$lang]; ?>:
        <input type="text" name="site_name" maxlength="100" style="width:220px;" />
        <img name="Button" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" />
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose1" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $text16[$lang]; ?>" title="<?php echo $text16[$lang]; ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose1','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('createsiteLayer','','hide');" />
      </td>        
    </tr>
  </table>
</form>
</div>

<div id="deletesiteLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:hidden;">
<form name="site_delete" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="action" value="site_delete" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table width="100%" height="60" border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td valign="middle" nowrap="nowrap">
      <span class=hcmsHeadline><?php echo $text11[$lang]; ?></span><br />
        <?php echo $text13[$lang]; ?>:
        <select name="site_name" style="width:220px;" onChange="hcms_jumpMenu('parent.frames[\'mainFrame\']',this,0)">
          <option value="empty.php">--- <?php echo $text15[$lang]; ?> ---</option>
        <?php
          if (!isset ($inherit_db) || $inherit_db == false) $inherit_db = inherit_db_read ($user);
          
          $item_option_delete = array();
          $item_option_edit = array();

          if ($inherit_db != false && sizeof ($inherit_db) >= 1)
          {
            foreach ($inherit_db as $inherit_db_record)
            {
              if ($inherit_db_record['parent'] != "" && in_array ($inherit_db_record['parent'], $siteaccess))
              {
                $inherit_db_record['parent'] = trim ($inherit_db_record['parent']);
                if ($inherit_db_record['parent'] != $site) $item_option_delete[] = "<option value=\"site_edit_form.php?site=".url_encode($site)."&preview=yes&site_name=".url_encode($inherit_db_record['parent'])."\">".$inherit_db_record['parent']."</option>\n";
                $item_option_edit[] = "<option value=\"frameset_site_edit.php?site=".url_encode($site)."&preview=no&site_name=".url_encode($inherit_db_record['parent'])."\">".$inherit_db_record['parent']."</option>\n";
              }              
            }
          }
          
          if (is_array ($item_option_delete) && sizeof ($item_option_delete) > 0)
          {
            natcasesort ($item_option_delete);
            reset ($item_option_delete);
            
            foreach ($item_option_delete as $delete_option)
            {
              echo $delete_option;
            }
          }
        ?>
        </select>
        <img border=0 name="Button3" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="warning_delete();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button3','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" />
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose2" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $text16[$lang]; ?>" title="<?php echo $text16[$lang]; ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose2','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('deletesiteLayer','','hide');" />
      </td>        
    </tr>
  </table>
</form>
</div>

<div id="editsiteLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:hidden;">
<form name="site_edit" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  
  <table width="100%" height="60" border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td valign="middle" nowrap="nowrap">
        <span class=hcmsHeadline><?php echo $text12[$lang]; ?></span><br />
        <?php echo $text13[$lang]; ?>:
        <select name="site_name" style="width:220px;" onChange="hcms_jumpMenu('parent.frames[\'mainFrame\']',this,0)">
          <option value="empty.php">--- <?php echo $text15[$lang]; ?> ---</option>
          <?php
          if (is_array ($item_option_edit) && sizeof ($item_option_edit) > 0)
          {
            natcasesort ($item_option_edit);
            reset ($item_option_edit);
            
            foreach ($item_option_edit as $edit_option)
            {
              echo $edit_option;
            }
          }
          ?>
        </select>
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose3" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $text16[$lang]; ?>" title="<?php echo $text16[$lang]; ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose3','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('editsiteLayer','','hide');" />
      </td>        
    </tr>
  </table>
</form>
</div>

</body>
</html>