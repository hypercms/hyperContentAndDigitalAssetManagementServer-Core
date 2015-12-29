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


// input parameters
$action = getrequest_esc ("action");
$multiobject = getrequest ("multiobject");
$site = getrequest_esc ("site"); // site can be *Null* which is not a valid name!
$group = getrequest_esc ("group", "objectname", "", true);
$login = getrequest_esc ("login", "objectname", "", true);
$password = getrequest ("password");
$confirm_password = getrequest ("confirm_password");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (($site == "*Null*" && !checkrootpermission ('user')) || ($site != "*Null*" && !checkglobalpermission ($site, 'user'))) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";

if ($action != "" && checktoken ($token, $user))
{
  // create new user
  if ($action == "create" &&
       (
         ($site == "*Null*" && checkrootpermission ('user') && checkrootpermission ('usercreate')) || 
         ($site != "*Null*" && checkglobalpermission ($site, 'user') && checkglobalpermission ($site, 'usercreate'))
        )
      )
  {
    $result = createuser ($site, $login, $password, $confirm_password, $user);
    
    $add_onload = $result['add_onload'];
    $show = $result['message'];  
  }
  // delete user
  elseif ($action == "delete" && $login != "admin" && $login != "sys" && $login != "hcms_download" && 
           (
             ($site == "*Null*" && checkrootpermission ('user') && checkrootpermission ('userdelete')) || 
             ($site != "*Null*" && checkglobalpermission ($site, 'user') && checkglobalpermission ($site, 'userdelete'))
           )
         )
  {
    if ($_REQUEST['multiobject'] != "")
    {
      $multiobject_array = explode ("|", $_REQUEST['multiobject']);
      $result['result'] = true;
      
      foreach ($multiobject_array as $login)
      {
        if ($login!= "" && $result['result'] == true)
        {
          $result = deleteuser ($site, $login, $user);
          $add_onload = $result['add_onload'];
          $show = $result['message'];
        }
      }
      
      if ($result['result'] == true)
      {
        $multiobject = "";
        $login = "";
      }
    }
    else
    {
      $result = deleteuser ($site, $login, $user);
      $add_onload = $result['add_onload'];
      $show = $result['message']; 
      if ($result['result'] == true) $login = "";
    }
  }
}

// define name: publication or usergroup
if ($temp_site != "*Null*") $item_name = getescapedtext ($hcms_lang['group'][$lang]);
else $item_name = getescapedtext ($hcms_lang['publication'][$lang]);

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
<!--
function warning_delete()
{
  check = confirm(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-this-user'][$lang]); ?>"));
  
  return check;
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
		alert("<?php echo getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters'][$lang]); ?>: "+addText);
		return false;
	}
  else
  {
		return true;
	}
}

function checkForm()
{ 
  var form = document.forms['userform'];
  var userlogin = form.elements['login'];
  var userpassword = form.elements['password'];
  var userconfirm_password = form.elements['confirm_password'];
  
  if (userlogin.value == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['a-user-name-is-required'][$lang]); ?>"));
    userlogin.focus();
    return false;
  }
  
  if (userlogin.value == "admin" || userlogin.value == "sys" || userlogin.value == "hcms_download")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['this-user-name-exists-already'][$lang]); ?>"));
    userlogin.focus();
    return false;
  }    
  
  if (!checkForm_chars (userlogin.value, "-_"))
  {
    userlogin.focus();
    return false;
  }
  
  if (userpassword.value != userconfirm_password.value)
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['your-submitted-passwords-are-not-equal'][$lang]); ?>"));
    document.userform.confirm_password.focus();
    return false;
  }
  
  if (!checkForm_chars (userpassword.value, "-_#+*[]%$§!?@"))
  {
    userpassword.focus();
    return false;
  }
  
  if (userconfirm_password.value == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-confirm-the-password'][$lang]); ?>"));
    userconfirm_password.focus();
    return false;
  } 
  
  if (!checkForm_chars (userconfirm_password.value, "-_#+*[]%$§!?@"))
  {
    userconfirm_password.focus();
    return false;
  }
  
  form.submit();
  return true;
}

function submitTo(url, action, target, features, width, height)
{
  if (features == undefined)
  {
    features = 'scrollbars=no,resizable=no,width=400,height=120';
  }
  if (width == undefined)
  {
    width = 400;
  }
  if (height == undefined)
  {
    height = 120;
  }

  var form = parent.frames['mainFrame'].document.forms['contextmenu_user'];
  
  form.attributes['action'].value = url;
  form.elements['action'].value = action;
  form.elements['group'].value = '<?php echo $group; ?>';
  form.elements['login'].value = '<?php echo $login; ?>';
  form.elements['token'].value = '<?php echo $token_new; ?>';
  form.target = target;
  form.submit();
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

<div class="hcmsLocationBar">
  <table border=0 cellspacing=0 cellpadding=0>
    <tr>
      <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['user-management'][$lang]); ?></td>
    </tr>
    <tr>
      <td>
        <span class="hcmsHeadline"><?php if ($login != "") echo getescapedtext ($hcms_lang['user'][$lang]).":"; ?>&nbsp;</span>
        <span class="hcmsHeadlineTiny">
          <?php
            if ($multiobject != "")
            {
              $multiobject_count = sizeof (link_db_getobject ($multiobject));
            }
            else $multiobject_count = 0;
            
            if ($multiobject_count > 1)
            {
              echo $multiobject_count." ".getescapedtext ($hcms_lang['users-selected'][$lang]);
            }
            elseif ($multiobject_count == 1)
            {
              echo str_replace ("|", "", $multiobject);
            }
            elseif ($login != "")
            {
              echo $login;
            }
          ?>
        </span>
      </td>
    </tr>  
  </table>
</div>

<!-- toolbar -->
<div class="hcmsToolbar">
  <div class="hcmsToolbarBlock">
    <?php
    if (($site == "*Null*" && checkrootpermission ('user') && checkrootpermission ('usercreate')) || ($site != "*Null*" && checkglobalpermission ($site, 'user') && checkglobalpermission ($site, 'usercreate')))
    {
      echo "<img ".
             "class=\"hcmsButton hcmsButtonSizeSquare\" ".
             "onClick=\"hcms_showHideLayers('createuserLayer','','show','hcms_messageLayer','','hide');\" ".
             "name=\"media_new\" src=\"".getthemelocation()."img/button_user_new.gif\" alt=\"".getescapedtext ($hcms_lang['create-new-user'][$lang])."\" title=\"".getescapedtext ($hcms_lang['create-new-user'][$lang])."\" />\n";
    }
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_user_new.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>
    <?php
    // DELETE BUTTON
    if ($login != "" && (($site == "*Null*" && checkrootpermission ('user') && checkrootpermission ('userdelete')) || ($site != "*Null*" && checkglobalpermission ($site, 'user')  && checkglobalpermission ($site, 'userdelete'))))
    {
      echo 
      "<img ".
        "class=\"hcmsButton hcmsButtonSizeSquare\" ".
        "onClick=\"if (warning_delete()==true) ".
        "submitTo('control_user_menu.php', 'delete', 'controlFrame'); \" ".
        "name=\"media_delete\" src=\"".getthemelocation()."img/button_user_delete.gif\" alt=\"".getescapedtext ($hcms_lang['remove-user'][$lang])."\" title=\"".getescapedtext ($hcms_lang['remove-user'][$lang])."\" />\n";
    }    
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_user_delete.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>
    <?php
    // USER EDIT
    if ($login != "" && (!$multiobject || $multiobject_count <= 1) && (($site == "*Null*" && checkrootpermission ('user')  && checkrootpermission ('useredit')) || ($site != "*Null*" && checkglobalpermission ($site, 'user')  && checkglobalpermission ($site, 'useredit'))))
    {
      echo "<img ".
             "class=\"hcmsButton hcmsButtonSizeSquare\" ".
             "onClick=\"hcms_openWindow('user_edit.php?site=".url_encode($site)."&group=".url_encode($group)."&login=".url_encode($login)."','','status=yes,scrollbars=no,resizable=yes', '500', '540');\" ".
             "name=\"media_edit\" src=\"".getthemelocation()."img/button_user_edit.gif\" alt=\"".getescapedtext ($hcms_lang['edit-user'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit-user'][$lang])."\" />\n";
    }    
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_user_edit.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    // USER FILES
    if ((!$multiobject || $multiobject_count <= 1) && $mgmt_config['db_connect_rdbms'] != "" && $login != "" && (($site == "*Null*" && checkrootpermission ('user')) || ($site != "*Null*" && checkglobalpermission ($site, 'user'))))
    {
      echo "<img ".
             "class=\"hcmsButton hcmsButtonSizeSquare\" ".
             "onClick=\"parent.location.href='frameset_objectlist.php?site=".url_encode($site)."&login=".url_encode($login)."&action=user_files';\" name=\"media_userfiles\" ".
             "src=\"".getthemelocation()."img/button_user_files.gif\" alt=\"".getescapedtext ($hcms_lang['created-objects-of-user'][$lang])."\" title=\"".getescapedtext ($hcms_lang['created-objects-of-user'][$lang])."\" />\n";
    }    
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_user_files.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    echo "<td><img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"parent.frames['mainFrame'].location.reload();\" name=\"pic_obj_refresh\" src=\"".getthemelocation()."img/button_view_refresh.gif\" alt=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" title=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" /></a></td>\n";
    ?> 
  </div>
  <div class="hcmsToolbarBlock">
    <div style="padding:3px; float:left;">  
      <?php if (!$is_mobile) echo $item_name.":"; ?>
      <select name="group" onChange="hcms_jumpMenu('parent.frames[\'mainFrame\']',this,0)">
        <?php
        // select users by group membership
        if ($temp_site != "*Null*")
        {
          if ($group == "_all") $selected = "selected=\"selected\"";
          else $selected = "";        
        
          echo "<option value=\"user_objectlist.php?site=".url_encode($site)."&group=_all\" ".$selected.">".getescapedtext ($hcms_lang['all-users'][$lang])."</option>\n";
                  
          $groupdata = loadfile ($mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");

          if ($groupdata != false)
          {
            $group_array = getcontent ($groupdata, "<groupname>");

            if ($group_array != false && sizeof ($group_array) >= 1)
            {
              natcasesort ($group_array);
              reset ($group_array);
  
              foreach ($group_array as $group_item)
              {
                if ($group_item != "")
                {
                  if ($group == $group_item) $selected = "selected=\"selected\"";
                  else $selected = "";
                  
                  echo "<option value=\"user_objectlist.php?site=".url_encode($site)."&group=".url_encode($group_item)."\" ".$selected.">".$group_item."</option>\n";
                }
              }
            }
          }
        }
        // select users by publication
        elseif ($temp_site == "*Null*")
        {    
          if ($site == "*Null*") $selected = "selected=\"selected\"";
          else $selected = "";    
             
          echo "<option value=\"user_objectlist.php?site=*Null*\" ".$selected.">".getescapedtext ($hcms_lang['all-users'][$lang])."</option>\n";
        
          $inherit_db = inherit_db_read ();
          
          $site_array = array();
          
          if ($inherit_db != false && sizeof ($inherit_db) > 0)
          {
            foreach ($inherit_db as $inherit_db_record)
            {
              if ($inherit_db_record['parent'] != "" && in_array ($inherit_db_record['parent'], $siteaccess))
              {
                $site_array[] = $inherit_db_record['parent'];
              }
            }
            
            if (is_array ($site_array) && sizeof ($site_array) > 0)
            {
              natcasesort ($site_array);
              reset ($site_array);
                        
              foreach ($site_array as $site_item)
              {
                if ($site == $site_item) $selected = "selected=\"selected\"";
                else $selected = "";
                              
                echo "<option value=\"user_objectlist.php?site=".url_encode($site_item)."\" ".$selected.">".$site_item."</option>\n";
              }
            }            
          }           
        }      
        ?>
      </select>
    </div>
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if (!$is_mobile && file_exists ("help/adminguide_".$hcms_lang_shortcut[$lang].".pdf"))
    {echo "<a href=# onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('pic_obj_help','','".getthemelocation()."img/button_help_over.gif',1)\" onClick=\"hcms_openWindow('help/adminguide_".$hcms_lang_shortcut[$lang].".pdf','help','scrollbars=no,resizable=yes','800','650');\"><img name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" /></a>\n";}
    elseif (!$is_mobile && file_exists ("help/adminguide_en.pdf"))
    {echo "<a href=# onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('pic_obj_help','','".getthemelocation()."img/button_help_over.gif',1)\" onClick=\"hcms_openWindow('help/adminguide_en.pdf','help','scrollbars=no,resizable=yes','800','650');\"><img name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" /></a>\n";}
    ?>
  </div>
</div>

<?php
echo showmessage ($show, 650, 60, $lang, "position:fixed; left:15px; top:15px; ");
?>

<div id="createuserLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:90px; z-index:4; left:15px; top:2px; visibility:hidden;">
<form name="userform" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="group" value="<?php echo $group; ?>" />
  <input type="hidden" name="action" value="create" />
  <input type="hidden" name="token" value="<?php echo createtoken ($user); ?>" />
  
  <table width="100%" border="0" cellspacing="1" cellpadding="0">
    <tr>
      <td colspan="2"><span class=hcmsHeadline><?php echo getescapedtext ($hcms_lang['create-new-user'][$lang]); ?></span></td>
      <td rowspan="2" width="16" align="right" valign="top">
        <img name="hcms_mediaClose1" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose1','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('createuserLayer','','hide');" />
      </td>        
    </tr>    
    <tr>
      <td width="100" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['user-name'][$lang]); ?>: </td>
      <td>
        <input type="text" name="login" style="width:150px;" maxlength="20" value="" tabindex="1" />
        <img name="Button" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" tabindex="4" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['password'][$lang]); ?>: </td>
      <td>
        <input type="password" name="password" maxlength="20" style="width:150px;" tabindex="2" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['confirm-password'][$lang]); ?>: </td>
      <td>
        <input type="password" name="confirm_password" maxlength="20" style="width:150px;" tabindex="3" />
      </td>     
    </tr>
  </table>
</form>
</div>

</body>
</html>
