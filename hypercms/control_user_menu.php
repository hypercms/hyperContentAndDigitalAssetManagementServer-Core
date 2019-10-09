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
$action = getrequest_esc ("action");
$multiobject = getrequest ("multiobject");
$site = getrequest_esc ("site"); // site can be *Null* or "no_memberof* which is not a valid name!
$group = getrequest_esc ("group", "objectname", "", true);
$login = getrequest_esc ("login", "objectname", "", true);
$password = getrequest ("password");
$confirm_password = getrequest ("confirm_password");
$registration = getrequest_esc ("registration");
$registration_notify = getrequest_esc ("registration_notify");
$registration_group = getrequest_esc ("registration_group");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if ((!valid_publicationname ($site) && !checkrootpermission ('user')) || (valid_publicationname ($site) && !checkglobalpermission ($site, 'user'))) killsession ($user);

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
         (!valid_publicationname ($site) && checkrootpermission ('user') && checkrootpermission ('usercreate')) || 
         (valid_publicationname ($site) && checkglobalpermission ($site, 'user') && checkglobalpermission ($site, 'usercreate'))
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
             (!valid_publicationname ($site) && checkrootpermission ('user') && checkrootpermission ('userdelete')) || 
             (valid_publicationname ($site) && checkglobalpermission ($site, 'user') && checkglobalpermission ($site, 'userdelete'))
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
  // reset password of selected user
  elseif ($action == "resetpassword" && $login != "" && ((!valid_publicationname ($site) && checkrootpermission ('user')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user'))))
  {
    $show = sendresetpassword ($login, false);
  }
  // delete session file of user
  elseif ($action == "killsession" && $login != "" && ((!valid_publicationname ($site) && checkrootpermission ('user')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user'))))
  {
    $result = killsession ($login, false, true);

    if ($result == true)
    {
      $add_onload = "parent.frames['mainFrame'].location.reload();";
      $show = getescapedtext ($login." ".$hcms_lang['logged-out'][$lang]);
    }
    else $show = getescapedtext ($login." ".$hcms_lang['session-cannot-be-closed'][$lang]);
  }
  // registration settings
  elseif ($action == "registration" && valid_publicationname ($site) && checkglobalpermission ($site, 'user'))
  {
    $settings = array('registration'=>$registration, 'registration_notify'=>$registration_notify, 'registration_group'=>$registration_group);
    
    $result = editpublicationsetting ($site, $settings, $user);
    
    // reload publication management config
    if ($result['result'] == true && valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    
    $add_onload = $result['add_onload'];
    $show = $result['message'];  
  }
}

// define name: publication or usergroup
if (valid_publicationname ($site)) $item_name = getescapedtext ($hcms_lang['group'][$lang]);
else $item_name = getescapedtext ($hcms_lang['publication'][$lang]);

// count multiobjects
if (!empty ($multiobject))
{
  $multiobject_count = sizeof (link_db_getobject ($multiobject));
}
else $multiobject_count = 0;

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
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
		alert("<?php echo getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters'][$lang]); ?>\n " + addText);
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
  
  if (userlogin.value.trim() == "")
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
  
  if (!checkForm_chars (userlogin.value, ".-_@"))
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
  
  if (!checkForm_chars (userpassword.value, ".-_#+*[]%$�!?@"))
  {
    userpassword.focus();
    return false;
  }
  
  if (userconfirm_password.value.trim() == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-confirm-the-password'][$lang]); ?>"));
    userconfirm_password.focus();
    return false;
  } 
  
  if (!checkForm_chars (userconfirm_password.value, "-_#+*[]%$�!?@"))
  {
    userconfirm_password.focus();
    return false;
  }
  
  form.submit();
  return true;
}

function resetPassword ()
{
  var form = document.forms['actionform'];
  form.elements['action'].value = "resetpassword";
  form.submit();
}

function killSession ()
{
  var form = document.forms['actionform'];
  form.elements['action'].value = "killsession";
  form.submit();
}

function submitTo (url, action, target, features, width, height)
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
</script>
</head>

<body class="hcmsWorkplaceControlWallpaper" onLoad="<?php echo $add_onload; ?>">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:20px;"); ?>

<div class="hcmsLocationBar">
  <?php if (!$is_mobile) { ?>
  <table class="hcmsTableNarrow">
    <tr>
      <td><b><?php echo getescapedtext ($hcms_lang['user-management'][$lang]); ?></b></td>
    </tr>
    <tr>
      <td>
        <b><?php if ($login != "") echo getescapedtext ($hcms_lang['user'][$lang]); ?>&nbsp;</b>
        <span class="hcmsHeadlineTiny">
          <?php            
            if ($multiobject_count > 1)
            {
              echo $multiobject_count." ".getescapedtext ($hcms_lang['users-selected'][$lang]);
            }
            elseif ($multiobject_count == 1)
            {
              echo str_replace ("|", "", $multiobject);
            }
            elseif (!empty ($login))
            {
              echo $login;
            }
          ?>
        </span>
      </td>
    </tr>  
  </table>
  <?php } else { ?>
  <span class="hcmsHeadlineTiny" style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo getescapedtext ($hcms_lang['user-management'][$lang])." &gt; ".(!empty ($login) ? $login : ""); ?></span>
  <?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar">
  <div class="hcmsToolbarBlock">
    <?php
    if ((!valid_publicationname ($site)  && checkrootpermission ('user') && checkrootpermission ('usercreate')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user') && checkglobalpermission ($site, 'usercreate')))
    {
      echo "<img ".
             "class=\"hcmsButton hcmsButtonSizeSquare\" ".
             "onClick=\"hcms_showHideLayers('createuserLayer','','show','registrationLayer','','hide','hcms_messageLayer','','hide');\" ".
             "name=\"media_new\" src=\"".getthemelocation()."img/button_user_new.png\" alt=\"".getescapedtext ($hcms_lang['create-new-user'][$lang])."\" title=\"".getescapedtext ($hcms_lang['create-new-user'][$lang])."\" />\n";
    }
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_user_new.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>
    <?php
    // DELETE BUTTON
    if (($login != "" || $multiobject != "") && ((!valid_publicationname ($site)  && checkrootpermission ('user') && checkrootpermission ('userdelete')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user')  && checkglobalpermission ($site, 'userdelete'))))
    {
      echo "<img ".
        "class=\"hcmsButton hcmsButtonSizeSquare\" ".
        "onClick=\"if (warning_delete()==true) ".
        "submitTo('control_user_menu.php', 'delete', 'controlFrame'); \" ".
        "name=\"media_delete\" src=\"".getthemelocation()."img/button_user_delete.png\" alt=\"".getescapedtext ($hcms_lang['remove-user'][$lang])."\" title=\"".getescapedtext ($hcms_lang['remove-user'][$lang])."\" />\n";
    }    
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_user_delete.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>
    <?php
    // USER EDIT
    if ($login != "" && (!$multiobject || $multiobject_count <= 1) && ((!valid_publicationname ($site)  && checkrootpermission ('user')  && checkrootpermission ('useredit')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user')  && checkglobalpermission ($site, 'useredit'))))
    {
      echo "<img ".
             "class=\"hcmsButton hcmsButtonSizeSquare\" ";

             if (!empty ($mgmt_config['user_newwindow'])) echo "onClick=\"hcms_openWindow('user_edit.php?site=".url_encode($site)."&group=".url_encode($group)."&login=".url_encode($login)."', '', 'status=yes,scrollbars=yes,resizable=yes', 560, 800);\" ";
             else echo "onClick=\"parent.openPopup('user_edit.php?site=".url_encode($site)."&group=".url_encode($group)."&login=".url_encode($login)."');\" ";
 
      echo "name=\"media_edit\" src=\"".getthemelocation()."img/button_user_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit-user'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit-user'][$lang])."\" />\n";
    }    
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_user_edit.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    // RESET PASSWORD
    if ((!$multiobject || $multiobject_count <= 1) && $login != "" && ((!valid_publicationname ($site) && checkrootpermission ('user')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user'))))
    {
      echo "<img ".
             "class=\"hcmsButton hcmsButtonSizeSquare\" ".
             "onClick=\"resetPassword();\" ".
             "src=\"".getthemelocation()."img/workflow_permission.png\" alt=\"".getescapedtext ($hcms_lang['reset-password'][$lang])."\" title=\"".getescapedtext ($hcms_lang['reset-password'][$lang])."\" />\n";
    }    
    else
    {
      echo "<img src=\"".getthemelocation()."img/workflow_permission.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>
    <?php
    // KILL USER SESSION
    // get online users
    $user_online_array = getusersonline ();

    if ((!$multiobject || $multiobject_count <= 1) && $login != "" && is_array ($user_online_array) && in_array ($login, $user_online_array) && ((!valid_publicationname ($site) && checkrootpermission ('user')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user'))))
    {
      echo "<img ".
             "class=\"hcmsButton hcmsButtonSizeSquare\" ".
             "onClick=\"killSession();\" ".
             "src=\"".getthemelocation()."img/button_logout.png\" alt=\"".getescapedtext ($hcms_lang['logout'][$lang])."\" title=\"".getescapedtext ($hcms_lang['logout'][$lang])."\" />\n";
    }    
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_logout.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>
    <?php
    // USER FILES
    if ((!$multiobject || $multiobject_count <= 1) && $mgmt_config['db_connect_rdbms'] != "" && $login != "" && ((!valid_publicationname ($site) && checkrootpermission ('user')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user'))))
    {
      echo "<img ".
             "class=\"hcmsButton hcmsButtonSizeSquare\" ".
             "onClick=\"parent.location='frameset_objectlist.php?site=".url_encode($site)."&login=".url_encode($login)."&action=user_files';\" ".
             "src=\"".getthemelocation()."img/button_user_files.png\" alt=\"".getescapedtext ($hcms_lang['created-objects-of-user'][$lang])."\" title=\"".getescapedtext ($hcms_lang['created-objects-of-user'][$lang])."\" />\n";
    }    
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_user_files.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    // REGISTRATION (only per publication)
    if (valid_publicationname ($site) && checkglobalpermission ($site, 'user'))
    {
      echo "<img ".
             "class=\"hcmsButton hcmsButtonSizeSquare\" ".
             "onClick=\"hcms_showHideLayers('createuserLayer','','hide','registrationLayer','','show','hcms_messageLayer','','hide');\" ".
             "src=\"".getthemelocation()."img/button_sessionreg.png\" alt=\"".getescapedtext ($hcms_lang['registration-of-new-users'][$lang])."\" title=\"".getescapedtext ($hcms_lang['registration-of-new-users'][$lang])."\" />\n";
    }    
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_sessionreg.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"parent.frames['mainFrame'].location.reload();\" name=\"pic_obj_refresh\" src=\"".getthemelocation()."img/button_view_refresh.png\" alt=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" title=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" />\n";
    ?> 
  </div>
  <div class="hcmsToolbarBlock">
    <div style="padding:3px; float:left;">  
      <select name="group" onChange="hcms_jumpMenu('parent.frames[\'mainFrame\']',this,0)" title="<?php echo $item_name; ?>" style="width:180px;">
        <?php
        // select users by group membership
        if (valid_publicationname ($site))
        {
          echo "
        <option value=\"user_objectlist.php?site=".url_encode($site)."&group=*all*\" ".($group == "*all*" ? "selected" : "").">".getescapedtext ($hcms_lang['all-users'][$lang])."</option>";
          echo "
        <option value=\"user_objectlist.php?site=".url_encode($site)."&group=*none*\" ".($group == "*none*" ? "selected" : "").">".getescapedtext ($hcms_lang['group'][$lang])." &gt; ".getescapedtext ($hcms_lang['none'][$lang])."</option>";
                  
          $groupdata = loadfile ($mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");

          if ($groupdata != false)
          {
            $group_array = getcontent ($groupdata, "<groupname>");

            if ($group_array != false && sizeof ($group_array) > 0)
            {
              natcasesort ($group_array);
              reset ($group_array);
  
              foreach ($group_array as $group_item)
              {
                if ($group_item != "")
                {
                  if ($group == $group_item) $selected = "selected=\"selected\"";
                  else $selected = "";
                  
                  echo "
          <option value=\"user_objectlist.php?site=".url_encode($site)."&group=".url_encode($group_item)."\" ".$selected.">".$group_item."</option>\n";
                }
              }
            }
          }
        }
        // select users by publication
        elseif (!valid_publicationname ($site))
        {
          echo "
          <option value=\"user_objectlist.php?site=*Null*\" ".($site == "*Null*" ? "selected" : "").">".getescapedtext ($hcms_lang['all-users'][$lang])."</option>";
          echo "
          <option value=\"user_objectlist.php?site=*no_memberof*\" ".($site == "*no_memberof*" ? "selected" : "").">".getescapedtext ($hcms_lang['publication'][$lang])." &gt; ".getescapedtext ($hcms_lang['none'][$lang])."</option>";
        
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
                              
                echo "
            <option value=\"user_objectlist.php?site=".url_encode($site_item)."\" ".$selected.">".$site_item."</option>";
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
    if (file_exists ("help/adminguide_".$hcms_lang_shortcut[$lang].".pdf"))
    {
      echo "<img onClick=\"hcms_openWindow('help/adminguide_".$hcms_lang_shortcut[$lang].".pdf','help','scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");\" name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.png\" class=\"hcmsButton hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" />\n";
    }
    elseif (file_exists ("help/adminguide_en.pdf"))
    {
      echo "<img onClick=\"hcms_openWindow('help/adminguide_en.pdf','help','scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");\" name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.png\" class=\"hcmsButton hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" />\n";
    }
    ?>
  </div>
</div>

<?php
echo showmessage ($show, 650, 60, $lang, "position:fixed; left:15px; top:15px; ");
?>

<div id="createuserLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:86px; z-index:4; left:15px; top:4px; visibility:hidden;">
<form name="userform" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="group" value="<?php echo $group; ?>" />
  <input type="hidden" name="action" value="create" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table class="hcmsTableStandard" style="width:100%;">
    <tr>
      <td>
        <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['create-new-user'][$lang]); ?></span><br/>
        <table class="hcmsTableNarrow">
          <tr>
            <?php if (!$is_mobile) echo "<td style=\"white-space:nowrap;\">".getescapedtext ($hcms_lang['user-name'][$lang])."&nbsp;</td>"; ?><td><input type="text" name="login" style="width:232px;" maxlength="100" value="<?php if ($action == "create") echo $login; ?>" tabindex="1" placeholder="<?php echo getescapedtext ($hcms_lang['user-name'][$lang]); ?>" /></td>
          </tr>
          <tr>
            <?php if (!$is_mobile) echo "<td style=\"white-space:nowrap;\">".getescapedtext ($hcms_lang['password'][$lang])."&nbsp;</td>"; ?><td style="white-space:nowrap;"><input type="password" name="password" maxlength="100" style="width:113px;" placeholder="<?php echo getescapedtext ($hcms_lang['password'][$lang]); ?>" tabindex="2" />
            <input type="password" name="confirm_password" maxlength="100" style="width:113px;" placeholder="<?php echo getescapedtext ($hcms_lang['confirm-password'][$lang]); ?>" tabindex="3" />
            <img name="Button1" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" tabindex="4" /></td>
          </tr>
        </table>
      </td>
      <td style="width:38px; text-align:right; vertical-align:top;">
        <img name="hcms_mediaClose1" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose1','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('createuserLayer','','hide');" />
      </td>        
    </tr>
  </table>
</form>
</div>

<div id="registrationLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:86px; z-index:4; left:15px; top:4px; visibility:hidden;">
<form name="registrationform" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="group" value="<?php echo $group; ?>" />
  <input type="hidden" name="action" value="registration" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table class="hcmsTableStandard" style="width:100%;">
    <tr>
      <td>
        <label><input type="checkbox" name="registration" value="true" tabindex="1" <?php if (!empty ($mgmt_config[$site]['registration'])) echo "checked"; ?> /> <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['registration-of-new-users'][$lang]); ?></span></label>
        <span class="hcmsTextSmall"><?php if (!$is_mobile) echo $mgmt_config['url_path_cms']."userregister.php?site=".url_encode($site); ?></span><br/>
        <table class="hcmsTableNarrow">
          <tr>
            <?php if (!$is_mobile) echo "<td style=\"white-space:nowrap;\">".getescapedtext ($hcms_lang['notify-users'][$lang]." ".$hcms_lang['comma-seperated'][$lang])."&nbsp;</td>"; ?><td style="white-space:nowrap;"><input name="registration_notify" value="<?php if (!empty ($mgmt_config[$site]['registration_notify'])) echo $mgmt_config[$site]['registration_notify']; ?>" style="width:230px;" tabindex="2" placeholder="<?php echo getescapedtext ($hcms_lang['notify-users'][$lang]." ".$hcms_lang['comma-seperated'][$lang]); ?>" /></td>
          </tr>
          <tr>
            <?php if (!$is_mobile) echo "<td style=\"white-space:nowrap;\">".getescapedtext ($hcms_lang['assign-registered-users-to-group'][$lang])."&nbsp;</td>"; ?><td style="white-space:nowrap;"><select name="registration_group" style="width:230px;" tabindex="3">
            <?php
            if ($is_mobile) echo "
            <option value=\"\" disabled>".getescapedtext ($hcms_lang['assign-registered-users-to-group'][$lang])."</option>";
            ?>
            <option value="" <?php if (empty ($mgmt_config[$site]['registration_group'])) echo "selected"; ?>><?php echo getescapedtext ($hcms_lang['none'][$lang]); ?></option>
            <?php 
            if (!empty ($group_array) && sizeof ($group_array) > 0)
            {
              reset ($group_array);
              
              foreach ($group_array as $group)
              {
                echo "
                <option value=\"".$group."\" ".((!empty ($mgmt_config[$site]['registration_group']) && $mgmt_config[$site]['registration_group'] == $group) ? "selected=\"selected\"" : "").">".$group."</option>";
              }
            }
            ?>
          </select>
          <img name="Button2" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="document.forms['registrationform'].submit();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button2','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" tabindex="4" /></td>
          </tr>
        </table>
      </td>
      <td style="width:38px; text-align:right; vertical-align:top;">
        <img name="hcms_mediaClose2" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose2','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('registrationLayer','','hide');" />
      </td>        
    </tr>
  </table>
</form>
</div>

<form name="actionform" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="group" value="<?php echo $group; ?>" />
  <input type="hidden" name="login" value="<?php echo $login; ?>" />
  <input type="hidden" name="action" value="" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
</form>

</body>
</html>
