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
$action = getrequest ("action");
$site = getrequest_esc ("site"); // site can be *Null* which is not a valid name!
$login_cat = getrequest_esc ("login_cat");
$login = getrequest_esc ("login", "objectname");
$old_password = getrequest ("old_password");
$password = getrequest ("password");
$confirm_password = getrequest ("confirm_password");
$superadmin = getrequest_esc ("superadmin");
$realname = getrequest_esc ("realname");
$language = getrequest_esc ("language");
$theme = getrequest_esc ("theme", "objectname");
$email = getrequest_esc ("email");
$phone = getrequest_esc ("phone");
$signature = getrequest_esc ("signature");
$usergroup = getrequest_esc ("usergroup");
$usersite = getrequest_esc ("usersite");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (
     ($site == "*Null*" && $login_cat == "home" && $login == $user && !checkrootpermission ('desktopsetting')) || 
     ($site == "*Null*" && $login_cat != "home" && (!checkrootpermission ('user') || !checkrootpermission ('useredit'))) || 
     ($site != "*Null*" && $login_cat != "home" && (!checkglobalpermission ($site, 'user') || !checkglobalpermission ($site, 'useredit')))
   ) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";

// save user
if ($action == "user_save" && ($site == "*Null*" || checkpublicationpermission ($site)) && checktoken ($token, $user))
{
  // check permissions
  if (
       ($login_cat == "home" && $login == $user && checkrootpermission ('desktopsetting')) || 
       ($site == "*Null*" && checkrootpermission ('user') && checkrootpermission ('useredit')) || 
       ($site != "*Null*" && checkglobalpermission ($site, 'user') && checkglobalpermission ($site, 'useredit'))
     )
  {
    // set super admin (only in main user administration)
    if ($site == "*Null*" && (checkadminpermission () || $user == "admin"))
    {
      if ($superadmin != "1") $superadmin = "0";
    }
    else $superadmin = "";
    
    // reload GUI
    $add_onload = "";
        
    if ($login_cat == "home" && $login == $user)
    {
      // load new language if user changed it
      if (!empty ($language) && $lang != $language)
      {
        $lang = $language;
        
        // language file
        require_once ("language/".getlanguagefile ($lang));
        $add_onload = "setTimeout (function(){ top.location.reload(true); }, 2000);";
      }
      
      // change theme in session if user changed it
      if (!empty ($theme) && $hcms_themename != $theme)
      {
        setsession ('hcms_themename', $theme, true);
        $add_onload = "setTimeout (function(){ top.location.reload(true); }, 2000);";
      }
    }

    // edit user settings
    $result = edituser ($site, $login, $old_password, $password, $confirm_password, $superadmin, $realname, $language, $theme, $email, $phone, $signature, $usergroup, $usersite, $user);

    $show = $result['message'];
  }
  else
  {
    $errcode = "30010";
    $error[] = $mgmt_config['today']."|user_edit.inc.php|error|$errcode|unauthorized access of user ".$user;
    
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".getescapedtext ($hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang])."</span>\n";
  }
  
  // save log
  savelog (@$error);
}

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=0.7, maximum-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<script>
function selectAll ()
{
  var assigned = "|";
  var form = document.forms['userform'];
  var select = form.elements['list2'];

  if (select.options.length > 0)
  {
    for (var i=0; i<select.options.length; i++)
    {
      assigned = assigned + select.options[i].value + "|" ;
    }
  }
  else
  {
    assigned = "*Null*";
  }

  if (form.elements['site'].value != "*Null*")
  {
    form.elements['usergroup'].value = assigned;
  }
  else if (form.elements['site'].value == "*Null*")  
  {
    form.elements['usersite'].value = assigned;  
  }

  return true;
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
		alert ("<?php echo getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters-in-password'][$lang]); ?>\n " + addText);
		return false;
	}
  else
  {
		return true;
	}
}

function checkForm ()
{
  var userform = document.forms['userform'];
  var selectall = true;
  
  if (userform.elements['password'].value != userform.elements['confirm_password'].value)
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['your-submitted-passwords-are-not-equal'][$lang]); ?>"));
    userform.elements['confirm_password'].focus();
    return false;
  }
    
  if (userform.elements['password'].value != "" || userform.elements['confirm_password'].value != "")
  {
    if (!checkForm_chars (userform.elements['password'].value, "-_#+*[]%$�!?@"))
    {
      userform.elements['password'].focus();
      return false;
    }
    
    if (userform.elements['confirm_password'].value == "")
    {
      alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['assigned-to-publication'][$lang]); ?>"));
      userform.elements['confirm_password'].focus();
      return false;
    } 
     
    if (!checkForm_chars (userform.elements['confirm_password'].value, "-_#+*[]%$�!?@"))
    {
      userform.elements['confirm_password'].focus();
      return false;
    }
  }
  
  if (userform.elements['email'].value == "" || (userform.elements['email'].value != "" && (userform.elements['email'].value.indexOf('@') == -1 || userform.elements['email'].value.indexOf('.') == -1)))
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-insert-a-valid-e-mail-adress'][$lang]); ?>"));
    userform.elements['email'].focus();
    return false;
  }
  
  if (eval (userform.elements['list2'])) selectall = selectAll (userform.elements['list2']);
  
  if (selectall == true) userform.submit();
  return true;
}

function move(fbox, tbox)
{
  var arrFbox = new Array();
  var arrTbox = new Array();
  var arrLookup = new Array();
  var i;

  for (i = 0; i < tbox.options.length; i++)
  {
    arrLookup[tbox.options[i].text] = tbox.options[i].value;
    arrTbox[i] = tbox.options[i].text;
  }

  var fLength = 0;
  var tLength = arrTbox.length;

  for (i = 0; i < fbox.options.length; i++)
  {
    arrLookup[fbox.options[i].text] = fbox.options[i].value;
    if (fbox.options[i].selected && fbox.options[i].value != "")
    {
      arrTbox[tLength] = fbox.options[i].text;
      tLength++;
    }
    else
    {
      arrFbox[fLength] = fbox.options[i].text;
      fLength++;
    }
  }

  arrFbox.sort();
  arrTbox.sort();  
  fbox.length = 0;
  tbox.length = 0;
  var c;

  for(c = 0; c < arrFbox.length; c++)
  {
    var no = new Option();
    no.value = arrLookup[arrFbox[c]];
    no.text = arrFbox[c];
    fbox[c] = no;
  }

  for(c = 0; c < arrTbox.length; c++)
  {
    var no = new Option();
    no.value = arrLookup[arrTbox[c]];
    no.text = arrTbox[c];
    tbox[c] = no;
  }
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" onload="<?php echo $add_onload; ?>">

<?php
echo showmessage ($show, 460, 70, $lang, "position:fixed; left:15px; top:15px;");
?>  

<?php
// check if login is an attribute of a sent string
if (strpos ($login, ".php") > 0)
{
  // extract login
  $login = getattribute ($login, "login");
}

if ($login != "" && $login != false)
{
  $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");

  $userrecord = selectcontent ($userdata, "<user>", "<login>", "$login");

  $superadminarray = getcontent ($userrecord[0], "<admin>");
  $superadmin = $superadminarray[0];

  $phonearray = getcontent ($userrecord[0], "<phone>");
  $phone = $phonearray[0];
  
  $emailarray = getcontent ($userrecord[0], "<email>");
  $email = $emailarray[0];
  
  $realnamearray = getcontent ($userrecord[0], "<realname>");
  $realname = $realnamearray[0];
  
  $hashcodearray = getcontent ($userrecord[0], "<hashcode>");
  $hashcode = $hashcodearray[0];
  
  $languagearray = getcontent ($userrecord[0], "<language>");
  $userlanguage = $languagearray[0];
  
  $themearray = getcontent ($userrecord[0], "<theme>");
  $usertheme = $themearray[0];
  
  $signaturearray = getcontent ($userrecord[0], "<signature>");
  $signature = $signaturearray[0];
  
  if ($site != "*Null*") 
  {
    $memberofarray = selectcontent ($userrecord[0], "<memberof>", "<publication>", "$site");
    
    $usergrouparray = getcontent ($memberofarray[0], "<usergroup>");
    
    if ($usergrouparray != false) $usergroup = $usergrouparray[0]; 
    else $usergroup = "";
  }
  elseif ($site == "*Null*")
  {
    $usersitearray = getcontent ($userrecord[0], "<publication>");
    
    if ($usersitearray != false) $usersite = "|".implode ("|", $usersitearray)."|";    
    else $usersite = "";
  }
}
?>

<!-- top bar -->
<?php echo showtopbar ($hcms_lang['settings-for-user'][$lang].": ".$login, $lang); ?>

<div class="hcmsWorkplaceFrame">
<form name="userform" action="" method="post">
  <input type="hidden" name="action" value="user_save">
  <input type="hidden" name="site" value="<?php echo $site; ?>">
  <?php if ($login_cat == "home") echo "<input type=\"hidden\" name=\"login_cat\" value=\"".$login_cat."\">\n"; ?>
  <input type="hidden" name="group" value="<?php echo $usergroup; ?>">
  <input type="hidden" name="login" value="<?php echo $login; ?>">
  <?php 
  if ($site != "*Null*" && $login_cat == "") echo "<input type=\"hidden\" name=\"usergroup\" value=\"".$usergroup."\">\n";
  elseif ($login_cat == "") echo "<input type=\"hidden\" name=\"usersite\" value=\"".$usersite."\">\n";
  ?>
  <input type="hidden" name="token" value="<?php echo $token_new; ?>">
  
  <table border="0" cellspacing="0" cellpadding="3">
    <?php if ($login_cat == "home" || $login == $user) { ?>
    <tr>
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['old-password'][$lang]); ?> </td>
      <td align="right">
        <input type="password" name="old_password" style="width:200px;" />
      </td>
    </tr>
    <?php } ?> 
    <tr>
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['change-password'][$lang]); ?> </td>
      <td align="right">
        <input type="password" name="password" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['confirm-password'][$lang]); ?> </td>
      <td align="right">
        <input type="password" name="confirm_password" style="width:200px;" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['hash-for-openapi'][$lang]); ?> </td>
      <td align="right">
        <input type="text" style="width:200px;" value="<?php echo $hashcode; ?>" readonly="readonly" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['name'][$lang]); ?> </td>
      <td align="right">
        <input type="text" name="realname" style="width:200px;" value="<?php echo $realname; ?>" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['e-mail'][$lang]); ?> </td>
      <td align="right">
        <input type="text" name="email" style="width:200px;" value="<?php echo $email; ?>" />
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['phone'][$lang]); ?> </td>
      <td align="right">
        <input type="text" name="phone" style="width:200px;" value="<?php echo $phone; ?>" />
      </td>
    </tr>
    <tr>
      <td valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['signature'][$lang]); ?> </td>
      <td align="right" valign="top">
        <textarea name="signature" wrap="VIRTUAL" style="width:200px; height:50px;"><?php echo $signature; ?></textarea>
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['language'][$lang]); ?> </td>
      <td align="right">
        <select name="language" style="width:210px;">
        <?php
        if (!empty ($mgmt_lang_shortcut) && is_array ($mgmt_lang_shortcut))
        {
          foreach ($mgmt_lang_shortcut as $lang_opt)
          {
            if ($userlanguage == $lang_opt) $selected = "selected=\"selected\"";
            else $selected = "";
            
            echo "<option value=\"".$lang_opt."\" ".$selected.">".$mgmt_lang_name[$lang_opt]."</option>\n";
          }
        }
        // for older versions before 5.7.3
        if (!empty ($lang_shortcut) && is_array ($lang_shortcut))
        {
          foreach ($lang_shortcut as $lang_opt)
          {
            if ($userlanguage == $lang_opt) $selected = "selected=\"selected\"";
            else $selected = "";
            
            echo "<option value=\"".$lang_opt."\" ".$selected.">".$lang_name[$lang_opt]."</option>\n";
          }
        }
        ?>
        </select>
      </td>
    </tr>
    <?php
    // check if publication defines a theme
    foreach ($siteaccess as $entry) if (!empty ($mgmt_config[$entry]['theme'])) { $config_theme = $mgmt_config[$entry]['theme']; break; }
    
    if (($site == "*Null*" && empty ($mgmt_config['theme']) && empty ($config_theme)) || ($site != "*Null*" && empty ($mgmt_config['theme']) && empty ($mgmt_config[$site]['theme']))) {
    ?>
    <tr>
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['theme'][$lang]); ?> </td>
      <td align="right">
        <select name="theme" style="width:210px;">
        <?php
        $theme_dir = $mgmt_config['abs_path_cms']."theme/";
        $dir_handler = opendir ($theme_dir);
        
        if ($dir_handler != false)
        {
          $theme_array = array();
          
          while ($theme_opt = @readdir ($dir_handler))
          {
            if (strtolower($theme_opt) != "mobile" && $theme_opt != "." && $theme_opt != ".." && is_dir ($theme_dir.$theme_opt) && is_dir ($theme_dir.$theme_opt."/img") && is_dir ($theme_dir.$theme_opt."/css"))
            {
              if ($usertheme == $theme_opt) $selected = "selected=\"selected\"";
              else $selected = "";
              
              $theme_array[] = $theme_opt;
            }
          }
          
          if (sizeof ($theme_array) > 0)
          {
            natcasesort ($theme_array);
            reset ($theme_array);
            
            foreach ($theme_array as $theme)
            {
              if ($usertheme == $theme) $selected = "selected=\"selected\"";
              else $selected = "";
              
              echo "<option value=\"".$theme."\" ".$selected.">".ucfirst ($theme)."</option>\n";
            }
          }
        }
        ?>
        </select>
      </td>
    </tr>
    <?php } ?>  
    <?php
    if ($site != "*Null*" && $login_cat != "home")
    {    
      echo "<tr>
      <td colspan=2>
        <table border=0 cellspacing=0 cellpadding=0>
          <tr>
            <td>
              ".getescapedtext ($hcms_lang['groups'][$lang]).":<br /><br />
              <select multiple size=\"10\" name=\"list1\" style=\"width:200px; height:140px;\">\n";

              $groupdata = loadfile ($mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");
      
              if ($groupdata != false)
              {
                $grouprecord_array = getcontent ($groupdata, "<groupname>");
    
                natcasesort ($grouprecord_array);
                reset ($grouprecord_array);
                
                $list2_array = array();
                          
                foreach ($grouprecord_array as $grouprecord)
                {
                  // unselected groups      
                  if (substr_count ($usergroup, "|".$grouprecord."|") == 0)
                  {
                    echo "<option value=\"".$grouprecord."\">".$grouprecord."</option>\n";
                  }
                  // selected groups
                  else
                  {
                    $list2_array[] = "<option value=\"".$grouprecord."\">".$grouprecord."</option>\n";
                  }
                }
              }

              echo "</select>
            </td>
            <td align=\"center\" valign=\"middle\">
              <br />
              <input type=\"button\" class=\"hcmsButtonBlue\" style=\"width:40px; margin:5px; display:block;\" onClick=\"move(this.form.elements['list2'], this.form.elements['list1'])\" value=\"&lt;&lt;\" />
              <input type=\"button\" class=\"hcmsButtonBlue\" style=\"width:40px; margin:5px; display:block;\" onClick=\"move(this.form.elements['list1'], this.form.elements['list2'])\" value=\"&gt;&gt;\" />
            </td>
            <td>
              ".getescapedtext ($hcms_lang['assigned-to-group'][$lang]).":<br /><br />
              <select multiple size=\"10\" name=\"list2\" style=\"width:200px; height:140px;\">\n";

              if (is_array ($list2_array) && sizeof ($list2_array) >= 1)
              {
                foreach ($list2_array as $list2)
                {
                  echo $list2;
                }
              }

              echo "</select>
            </td>
          </tr>
        </table>      
      </td>
    </tr>\n";
    }
    elseif ($site == "*Null*" && $login_cat != "home")
    {    
      echo "<tr>
      <td colspan=2>
        <table border=0 cellspacing=0 cellpadding=0>
          <tr>
            <td>
              ".getescapedtext ($hcms_lang['publications'][$lang]).":<br /><br />
              <select multiple size=\"10\" name=\"list1\" style=\"width:200px; height:140px;\">\n";

              $inherit_db = inherit_db_read ($user);
              
              $list1_array = array();
              $list2_array = array();
      
              if ($inherit_db != false && is_array ($inherit_db))
              {                        
                foreach ($inherit_db as $inherit_db_record)
                {
                  // check if user has siteaccess
                  if (in_array ($inherit_db_record['parent'], $siteaccess))
                  {
                    // unselected sites
                    if (substr_count ($usersite, "|".$inherit_db_record['parent']."|") == 0)
                    {
                      $list1_array[] = "<option value=\"".$inherit_db_record['parent']."\">".$inherit_db_record['parent']."</option>\n";
                    }
                    // selected sites
                    else
                    {
                      $list2_array[] = "<option value=\"".$inherit_db_record['parent']."\">".$inherit_db_record['parent']."</option>\n";
                    }
                  }
                }
                
                natcasesort ($list1_array);
                reset ($list1_array);
                
                if (is_array ($list1_array) && sizeof ($list1_array) > 0)
                {
                  foreach ($list1_array as $list1) echo $list1;
                }
              }

              echo "</select>
            </td>
            <td align=\"center\" valign=\"middle\">
              <br />
              <input type=\"button\" class=\"hcmsButtonBlue\" style=\"width:40px; margin:5px; display:block;\" onClick=\"move(this.form.elements['list2'], this.form.elements['list1'])\" value=\"&lt;&lt;\" />
              <input type=\"button\" class=\"hcmsButtonBlue\" style=\"width:40px; margin:5px; display:block;\" onClick=\"move(this.form.elements['list1'], this.form.elements['list2'])\" value=\"&gt;&gt;\" />
            </td>
            <td>
              ".getescapedtext ($hcms_lang['assigned-to-publication'][$lang]).":<br /><br />
              <select multiple size=\"10\" name=\"list2\" style=\"width:200px; height:140px;\">\n";

              if (is_array ($list2_array) && sizeof ($list2_array) > 0)
              {
                natcasesort ($list2_array);
                reset ($list2_array);
                
                foreach ($list2_array as $list2) echo $list2;
              }

              echo "</select>
            </td>
          </tr>
        </table>      
      </td>
    </tr>\n";
    }    
    ?>
    <?php if ($site == "*Null*" && checkadminpermission ()) { ?>
    <tr>
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['administration'][$lang]); ?> </td>
      <td align="left">
        <input type="checkbox" name="superadmin" value="1" <?php if ($superadmin == "1") echo "checked=\"checked\""; ?>/> <?php echo getescapedtext ($hcms_lang['super-administrator'][$lang]); ?>
      </td>
    </tr>
    <?php } ?>    
    <tr>
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['save-settings'][$lang]); ?> </td>
      <td>
        <img name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" onclick="checkForm();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" align="absmiddle" title="OK" alt="OK" />
      </td>
    </tr>
  </table>
</form>
</div>

</body>
</html>
