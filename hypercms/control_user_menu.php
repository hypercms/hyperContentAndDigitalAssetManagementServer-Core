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

// initialize
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
    $result = createuser ($site, $login, $password, $confirm_password, 0, $user);
    
    $add_onload .= $result['add_onload'];
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
    if (!empty ($multiobject))
    {
      $multiobject_array = explode ("|", $multiobject);
      $result['result'] = true;
      
      foreach ($multiobject_array as $login)
      {
        if ($login!= "" && !empty ($result['result']))
        {
          $result = deleteuser ($site, $login, $user);
          $add_onload .= $result['add_onload'];
          $show = $result['message'];
        }
      }
      
      if (!empty ($result['result']))
      {
        $multiobject = "";
        $login = "";
      }
    }
    else
    {
      $result = deleteuser ($site, $login, $user);
      $add_onload .= $result['add_onload'];
      $show = $result['message']; 
      if (!empty ($result['result'])) $login = "";
    }
  }
  // reset password of selected user
  elseif ($action == "resetpassword" && $login != "" && ((!valid_publicationname ($site) && checkrootpermission ('user')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user'))))
  {
    $show = sendresetpassword ($login, "resetpassword");
  }
  // delete session file of user
  elseif ($action == "killsession" && $login != "" && ((!valid_publicationname ($site) && checkrootpermission ('user')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user'))))
  {
    $result = killsession ($login, false, true);

    if ($result == true)
    {
      $add_onload .= "parent.frames['mainFrame'].location.reload();";
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
    if (!empty ($result['result']) && valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    
    $add_onload .= $result['add_onload'];
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

// get user names
$user_array = getuserinformation ();

if (!empty ($user_array[$site]) && is_array ($user_array[$site]) && sizeof ($user_array[$site]) > 0)
{
  $username_array = array_keys ($user_array[$site]);
  $usernames = "['".implode ("', '", $username_array)."']";
  $tagit = "availableTags:".$usernames.", beforeTagAdded: function(event, ui) { if ($.inArray(ui.tagLabel, ".$usernames.") == -1) { return false; } }, ";

  $add_onload .= "$('#users').tagit({".$tagit."readOnly:false, singleField:true, allowSpaces:false, singleFieldDelimiter:',', singleFieldNode:$('#users')});";
}

// create secure token
$token_new = createtoken ($user);
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
<!-- JQuery and JQuery UI -->
<script type="text/javascript" src="javascript/jquery/jquery.min.js"></script>
<script type="text/javascript" src="javascript/jquery-ui/jquery-ui.min.js"></script>
<link rel="stylesheet" href="javascript/jquery-ui/jquery-ui.css" type="text/css" />
<!-- Tagging -->
<script type="text/javascript" src="javascript/tag-it/tag-it.min.js"></script>
<link rel="stylesheet" type="text/css" href="javascript/tag-it/jquery.tagit.css" />
<link rel="stylesheet" type="text/css" href="javascript/tag-it/tagit.ui-zendesk.css" />
<style type="text/css">
<?php echo showdynamicCSS ($hcms_themeinvertcolors, $hcms_hoverinvertcolors); ?>

ul.tagit
{
  width: 280px;
  height: 28px;
}
</style>
<script type="text/javascript">

function startSearch ()
{
  if (document.forms['searchform'])
  {
    var form = document.forms['searchform'];

    // reset group/publication filter
    if (document.getElementById('groupfilter')) document.getElementById('groupfilter').selectedIndex = 0;

    // load screen
    if (parent.frames['mainFrame'].document.getElementById('hcmsLoadScreen')) parent.frames['mainFrame'].document.getElementById('hcmsLoadScreen').style.display = 'inline';
    
    // submit form
    form.submit();
  }
}

function warning_delete()
{
  check = confirm(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-this-user'][$lang]); ?>"));
  
  return check;
}

function checkForm_chars(text, exclude_chars)
{
  exclude_chars = exclude_chars.replace(/[-[\]{}()*+?.,;\\^$|#\s]/g, "\\$&");
  
	var expr = new RegExp ("[^a-zA-Z0-9" + exclude_chars + "]", "g");
	var separator = ', ';
	var found = text.match(expr); 
	
  if (found)
  {
		var addText = '';
    
		for (var i = 0; i < found.length; i++)
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
  
  if (userlogin.value.toLowerCase() == "admin" || userlogin.value.toLowerCase() == "sys" || userlogin.value.toLowerCase() == "hcms_download")
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
  
  if (!checkForm_chars (userpassword.value, ".,;-_#+*[]%$!?@"))
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
  
  if (!checkForm_chars (userconfirm_password.value, ".,;-_#+*[]%$!?@"))
  {
    userconfirm_password.focus();
    return false;
  }
  
  form.submit();
  return true;
}

function createuser ()
{
  hcms_showHideLayers('createuserLayer','','show','registrationLayer','','hide','hcms_messageLayer','','hide');
  if (typeof parent.hcms_openSubMenu == "function") parent.hcms_openSubMenu(108);
}

function registeruser ()
{
  hcms_showHideLayers('createuserLayer','','hide','registrationLayer','','show','hcms_messageLayer','','hide');
  if (typeof parent.hcms_openSubMenu == "function") parent.hcms_openSubMenu(108);
}

function closeuser (id)
{
  hcms_showHideLayers(id,'','hide');
  if (typeof parent.hcms_closeSubMenu == "function") parent.hcms_closeSubMenu(undefined, 580);
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

// init
parent.hcms_closeSubMenu(undefined, 580);
</script>
</head>

<body class="hcmsWorkplaceControl" onload="<?php echo $add_onload; ?>">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:10px;", "hcms_infobox_mouseover"); ?>

<?php
echo showmessage ($show, 660, 65, $lang, "position:fixed; left:5px; top:5px;");
?>

<div class="hcmsLocationBar">
  <?php if (!$is_mobile) { ?>
  <table class="hcmsTableNarrow">
    <tr>
      <td class="hcmsHeadline"> <?php if (valid_publicationname ($site)) echo getescapedtext ($site." &gt; "); echo getescapedtext ($hcms_lang['user-management'][$lang]); ?> </td>
    </tr>
    <tr>
      <td>
        <b><?php if ($login != "") echo "
          <img src=\"".getthemelocation()."img/user.png\" title=\"".getescapedtext ($hcms_lang['user'][$lang])."\" class=\"hcmsIconList\" style=\"margin-left:0;\" />";
          ?>
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
  <span style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php if (valid_publicationname ($site)) echo getescapedtext ($site." &gt; "); echo getescapedtext ($hcms_lang['user-management'][$lang])." &gt; ".(!empty ($login) ? $login : ""); ?></span>
  <?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar hcmsWorkplaceControl" style="<?php echo gettoolbarstyle ($is_mobile); ?>">
  <div class="hcmsToolbarBlock">
    <?php
    // create user
    if ((!valid_publicationname ($site)  && checkrootpermission ('user') && checkrootpermission ('usercreate')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user') && checkglobalpermission ($site, 'usercreate')))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"createuser();\">
        <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" ".
        "name=\"media_new\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_user_new.png\" alt=\"".getescapedtext ($hcms_lang['create-new-user'][$lang])."\" title=\"".getescapedtext ($hcms_lang['create-new-user'][$lang])."\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['create'][$lang])."</span>
      </div>";
    }
    else
    {
      echo "
      <div class=\"hcmsButtonOff hcmsInvertColor\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_user_new.png\" class=\"hcmsButtonSizeSquare hcmsFloatLeft\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['create'][$lang])."</span>
      </div>";
    }
    ?>
    <?php
    // delete user
    if (($login != "" || $multiobject != "") && ((!valid_publicationname ($site)  && checkrootpermission ('user') && checkrootpermission ('userdelete')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user')  && checkglobalpermission ($site, 'userdelete'))))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"if (warning_delete()==true) submitTo('control_user_menu.php', 'delete', 'controlFrame');\">
        <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" ".
        "name=\"media_delete\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_user_delete.png\" alt=\"".getescapedtext ($hcms_lang['remove-user'][$lang])."\" title=\"".getescapedtext ($hcms_lang['remove-user'][$lang])."\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['delete'][$lang])."</span>
      </div>";
    }    
    else
    {
      echo "
      <div class=\"hcmsButtonOff hcmsInvertColor\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_user_delete.png\" class=\"hcmsButtonSizeSquare hcmsFloatLeft\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['delete'][$lang])."</span>
      </div>";
    }
    ?>
    <?php
    // edit user
    if ($login != "" && (!$multiobject || $multiobject_count <= 1) && ((!valid_publicationname ($site)  && checkrootpermission ('user')  && checkrootpermission ('useredit')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user')  && checkglobalpermission ($site, 'useredit'))))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" ";
      if (!empty ($mgmt_config['user_newwindow'])) echo "onclick=\"hcms_openWindow('user_edit.php?site=".url_encode($site)."&group=".url_encode($group)."&login=".url_encode($login)."', '', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=yes,resizable=yes', 560, 880);\" ";
      else echo "onclick=\"parent.openPopup('user_edit.php?site=".url_encode($site)."&group=".url_encode($group)."&login=".url_encode($login)."');\" ";
      echo ">
        <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" ".
        "name=\"media_edit\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_user_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit-user'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit-user'][$lang])."\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['edit'][$lang])."</span>
      </div>";
    }    
    else
    {
      echo "
      <div class=\"hcmsButtonOff hcmsInvertColor\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_user_edit.png\" class=\"hcmsButtonSizeSquare hcmsFloatLeft\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['edit'][$lang])."</span>
      </div>";
    }
    ?>
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    //  reset password
    if ((!$multiobject || $multiobject_count <= 1) && $login != "" && ((!valid_publicationname ($site) && checkrootpermission ('user')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user'))))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"resetPassword();\">
        <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" ".
        "src=\"".getthemelocation($hcms_themeinvertcolors)."img/workflow_permission.png\" alt=\"".getescapedtext ($hcms_lang['reset-password'][$lang])."\" title=\"".getescapedtext ($hcms_lang['reset-password'][$lang])."\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['reset-password'][$lang])."</span>
      </div>";
    }    
    else
    {
      echo "
      <div class=\"hcmsButtonOff hcmsInvertColor\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/workflow_permission.png\" class=\"hcmsButtonSizeSquare hcmsFloatLeft\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['reset-password'][$lang])."</span>
      </div>";
    }
    ?>
    <?php
    // kill user session
    // get online users
    $user_online_array = getusersonline ();

    if ((!$multiobject || $multiobject_count <= 1) && $login != "" && is_array ($user_online_array) && array_key_exists ($login, $user_online_array) && ((!valid_publicationname ($site) && checkrootpermission ('user')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user'))))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"killSession();\">
        <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" ".
        "src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_logout.png\" alt=\"".getescapedtext ($hcms_lang['logout'][$lang])."\" title=\"".getescapedtext ($hcms_lang['logout'][$lang])."\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['logout'][$lang])."</span>
      </div>";
    }    
    else
    {
      echo "
      <div class=\"hcmsButtonOff hcmsInvertColor\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_logout.png\" class=\"hcmsButtonSizeSquare hcmsFloatLeft\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['logout'][$lang])."</span>
      </div>";
    }
    ?>
    <?php
    // get user files
    if ((!$multiobject || $multiobject_count <= 1) && $mgmt_config['db_connect_rdbms'] != "" && $login != "" && ((!valid_publicationname ($site) && checkrootpermission ('user')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user'))))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"parent.location='frameset_objectlist.php?site=".url_encode($site)."&login=".url_encode($login)."&action=user_files';\">
        <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" ".
        "src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_user_files.png\" alt=\"".getescapedtext ($hcms_lang['created-objects-of-user'][$lang])."\" title=\"".getescapedtext ($hcms_lang['created-objects-of-user'][$lang])."\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['created-objects-of-user'][$lang])."</span>
      </div>";
    }    
    else
    {
      echo "
      <div class=\"hcmsButtonOff hcmsInvertColor\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_user_files.png\" class=\"hcmsButtonSizeSquare hcmsFloatLeft\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['created-objects-of-user'][$lang])."</span>
      </div>";
    }
    ?>
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    // user registration (only per publication)
    if (valid_publicationname ($site) && checkglobalpermission ($site, 'user'))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"registeruser();\">
        <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" ".
        "src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_sessionreg.png\" alt=\"".getescapedtext ($hcms_lang['registration-of-new-users'][$lang])."\" title=\"".getescapedtext ($hcms_lang['registration-of-new-users'][$lang])."\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['registration-of-new-users'][$lang])."</span>
      </div>";
    }    
    else
    {
      echo "
      <div class=\"hcmsButtonOff hcmsInvertColor\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_sessionreg.png\" class=\"hcmsButtonSizeSquare hcmsFloatLeft\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['registration-of-new-users'][$lang])."</span>
      </div>";
    }
    ?>
  </div>
  <div class="hcmsToolbarBlock">
    <div class="hcmsButton hcmsHoverColor hcmsInvertColor" onclick="parent.frames['mainFrame'].location.reload();">
      <?php echo "<img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_view_refresh.png\" alt=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" title=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" />"; ?> 
      <span class="hcmsButtonLabel"><?php echo getescapedtext ($hcms_lang['refresh'][$lang]); ?></span>
    </div>
  </div>
  <div class="hcmsToolbarBlock">
    <div style="padding:3px; float:left;">
      <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_filter.png" class="hcmsIconList" style="vertical-align:middle;" />
      <select name="group" id="groupfilter" onchange="hcms_jumpMenu('parent.frames[\'mainFrame\']',this,0)" title="<?php echo $item_name; ?>" style="width:<?php if ($is_mobile) echo "120px"; else echo "180px"; ?>;">
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
              if ($inherit_db_record['parent'] != "" && array_key_exists ($inherit_db_record['parent'], $siteaccess))
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
            <option value=\"user_objectlist.php?site=".url_encode($site_item)."\" ".$selected." title=\"".$site_item."\">".$siteaccess[$site_item]."</option>";
              }
            }            
          }           
        }      
        ?>
      </select>
    </div>
  </div>
  <div class="hcmsToolbarBlock">
    <div style="padding:3px; float:left;">
      <form name="searchform" method="post" action="user_objectlist.php" target="mainFrame" style="margin:0; padding:0; border:0;">
        <input type="hidden" name="site" value="<?php echo $site; ?>" />
        <input type="hidden" name="group" value="<?php if (valid_publicationname ($site)) echo "*all*"; ?>" />
        <input type="text" name="search" onkeydown="if (hcms_enterKeyPressed(event)) startSearch();" style="float:left; width:<?php if ($is_mobile) echo "130px"; else echo "180px"; ?>;" maxlength="400" placeholder="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" value="" />
        <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_search_dark.png" onclick="startSearch();" style="float:left; cursor:pointer; width:22px; height:22px; margin:5px 0px 3px -26px; " title="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" />
      </form>
    </div>
  </div>
  <div class="hcmsToolbarBlock">
    <?php echo showhelpbutton ("adminguide", true, $lang, "", "hcmsHoverColor hcmsInvertColor"); ?>
  </div>
</div>

<!-- create user -->
<div id="createuserLayer" class="hcmsMessage" style="position:absolute; left:5px; top:3px; width:<?php if ($is_mobile) echo "95%"; else echo "650px"; ?>; visibility:hidden;">
  <form name="userform" action="" method="post" onsubmit="return checkForm();">
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="group" value="<?php echo $group; ?>" />
    <input type="hidden" name="action" value="create" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
    
    <table class="hcmsTableStandard" style="width:100%; min-height:40px;">
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
          <img name="hcms_mediaClose1" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose1','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="closeuser('createuserLayer');" />
        </td>        
      </tr>
    </table>
  </form>
</div>

<!-- registration (overwrite z-index for tagit selectbox) -->
<div id="registrationLayer" class="hcmsMessage" style="position:absolute; left:5px; top:3px; z-index:99; width:<?php if ($is_mobile) echo "95%"; else echo "650px"; ?>; visibility:hidden;">
  <form name="registrationform" action="" method="post">
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="group" value="<?php echo $group; ?>" />
    <input type="hidden" name="action" value="registration" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />

    <table class="hcmsTableStandard" style="width:100%; min-height:40px;">
      <tr>
        <td>
          <label><input type="checkbox" name="registration" value="true" tabindex="1" <?php if (!empty ($mgmt_config[$site]['registration'])) echo "checked"; ?> /> <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['registration-of-new-users'][$lang]); ?></span></label>
          <span class="hcmsTextSmall"><?php if (!$is_mobile) echo $mgmt_config['url_path_cms']."userregister.php?site=".url_encode($site); ?></span><br/>
          <table class="hcmsTableNarrow">
            <tr>
              <?php if (!$is_mobile) echo "<td style=\"white-space:nowrap;\">".getescapedtext ($hcms_lang['notify-users'][$lang])."&nbsp;</td>"; ?>
              <td style="width:220px; white-space:nowrap; vertical-align:middle;"><input id="users" name="registration_notify" value="<?php if (!empty ($mgmt_config[$site]['registration_notify'])) echo $mgmt_config[$site]['registration_notify']; ?>" style="width:100%;" tabindex="2" placeholder="<?php echo getescapedtext ($hcms_lang['notify-users'][$lang]); ?>" /></td>
              <td></td>
            </tr>
            <tr>
              <?php if (!$is_mobile) echo "<td style=\"white-space:nowrap;\">".getescapedtext ($hcms_lang['assign-registered-users-to-group'][$lang])."&nbsp;</td>"; ?>
              <td style="white-space:nowrap; vertical-align:middle;">
                <select name="registration_group" style="width:100%;" tabindex="3">
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
              </td>
              <td style="white-space:nowrap; vertical-align:middle;">
                &nbsp;<img name="Button2" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="document.forms['registrationform'].submit();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button2','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" tabindex="4" />
              </td>
            </tr>
          </table>
        </td>
        <td style="width:38px; text-align:right; vertical-align:top;">
          <img name="hcms_mediaClose2" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose2','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="closeuser('registrationLayer');" />
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
