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
$action = getrequest ("action");
$login = getrequest_esc ("login", "objectname");
$old_password = getrequest ("old_password");
$password = getrequest ("password");
$confirm_password = getrequest ("confirm_password");
$forward = getrequest ("forward");
$token = getrequest ("token");


// ------------------------------ permission section --------------------------------

// check permissions
if ($login == $user && !checkrootpermission ('desktopsetting')) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";

// save user
if ($action == "user_save" && checktoken ($token, $user))
{
  if ($login == $user && checkrootpermission ('desktopsetting'))
  {
    // edit user settings
    $result = edituser ("*Leave*", $login, $old_password, $password, $confirm_password, "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", $user);

    if (!empty ($result['result'])) $add_onload = "location.href='".$mgmt_config['url_path_cms'].$forward."';";
    else $add_onload = "";

    $show = $result['message'];
  }
  else
  {
    $errcode = "30010";
    $error[] = $mgmt_config['today']."|user_edit.inc.php|error|$errcode|unauthorized access of user ".$user;

    savelog ($error);
    
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".getescapedtext ($hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang])."</span>\n";
  }
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
<script type="text/javascript">
function focusform()
{
  if (document.getElementById('old_password'))
  {
    document.forms['userform'].elements['old_password'].focus();
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
      alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-confirm-the-password'][$lang]); ?>"));
      userform.elements['confirm_password'].focus();
      return false;
    } 
     
    if (!checkForm_chars (userform.elements['confirm_password'].value, "-_#+*[]%$�!?@"))
    {
      userform.elements['confirm_password'].focus();
      return false;
    }
  }
  
  hcms_showInfo ('savelayer', 0);
  userform.submit();
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" onload="<?php echo $add_onload; ?>focusform();">

<!-- saving --> 
<div id="savelayer" class="hcmsLoadScreen"></div>

<?php
echo showmessage ($show, 460, 70, $lang, "position:fixed; left:15px; top:95px;");
?>  

<?php
// check if login is an attribute of a sent string
if (strpos ($login, ".php") > 0)
{
  // extract login
  $login = getattribute ($login, "login");
}
?>

<!-- top bar -->
<div class="hcmsStartBar hcmsWorkplaceWorkflow">
  <div style="position:absolute; top:15px; left:15px; float:left; text-align:left;"><img src="<?php echo getthemelocation(); ?>img/logo.png" style="border:0; height:48px;" alt="hypercms.com" /></div>
  <div style="position:absolute; top:15px; right:15px; text-align:right;"><?php echo getescapedtext ($hcms_lang['reset-password'][$lang]); ?></div>
</div>

<div class="hcmsLogonScreen">
  <form name="userform" action="" method="post">
    <input type="hidden" name="action" value="user_save" />
    <input type="hidden" name="login" value="<?php echo $user; ?>" />
    <input type="hidden" name="old_password" value="<?php echo $user; ?>" />
    <input type="hidden" name="forward" value="<?php echo $forward; ?>" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
    
    <div style="padding:4px 0px;">
      <?php echo getescapedtext ($hcms_lang['old-password'][$lang]); ?><br/>
      <input type="password" name="old_password" id="old_password" style="width:250px; margin:3px 0px; padding:8px 5px;" tabindex="1" />
    </div>
    <div style="padding:4px 0px;">
      <?php echo getescapedtext ($hcms_lang['change-password'][$lang]); ?><br/>
      <input type="password" name="password" style="width:250px; margin:3px 0px; padding:8px 5px;" tabindex="2" />
    </div>
    <div style="padding:4px 0px;">
      <?php echo getescapedtext ($hcms_lang['confirm-password'][$lang]); ?><br/>
      <input type="password" name="confirm_password" maxlength="100" style="width:250px; margin:3px 0px; padding:8px 5px;" tabindex="3" />
    </div>
    <!-- Save -->
    <div style="padding:4px 0px;">
      <button type="button" onclick="checkForm();" class="hcmsButtonGreen hcmsButtonSizeHeight" style="width:260px;" tabindex="4"><?php echo getescapedtext ($hcms_lang['save-settings'][$lang]); ?></button>
    </div>
  </form>
</div>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>
