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

// input parameter for instance
$sentinstance = getrequest ("sentinstance", "publicationname");

// register instance in session and load main config of instance
registerinstance ($sentinstance);

// input parameters
$action = getrequest ("action");
$login = getrequest_esc ("login", "objectname");
$hash = getrequest ("hash");
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

$error = array();
$show = "";
$add_onload = "";

// check if login is an attribute of a sent string
if (strpos ($login, ".php") > 0)
{
  // extract login
  $login = getattribute ($login, "login");
}

// detect browser and set theme
if (is_mobilebrowser () || $is_mobile == "1" || $is_mobile == "yes") $is_mobile = 1;

if (!empty ($theme)) $themename = $theme;
else $themename = "";

// save user
if ($action == "user_save" && checktoken ($token, $user))
{
  if ($login == $user && checkrootpermission ('desktopsetting'))
  {
    // edit user settings
    $result = edituser ("*Leave*", $login, $old_password, $password, $confirm_password, "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", "*Leave*", $user);

    if (!empty ($result['result'])) $add_onload = "location.href='".cleandomain ($mgmt_config['url_path_cms']).$forward."';";
    else $add_onload = "";

    $show = $result['message'];
  }
  else
  {
    $errcode = "30010";
    $error[] = $mgmt_config['today']."|user_edit.inc.php|error|".$errcode."|Unauthorized access of user '".$user."'";

    savelog ($error);
    
    $add_onload = "";
    $show = "<span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang])."</span>\n";
  }
}

// wallpaper
$wallpaper = getwallpaper ();

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=0.7, maximum-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />

<!-- Standard icon -->
<link rel="shortcut icon" href="<?php echo getthemelocation(); ?>img/favicon.ico"> 
<!-- 57 x 57 Android and iPhone 3 icon -->
<link rel="apple-touch-icon" media="screen and (resolution: 163dpi)" href="<?php echo getthemelocation($themename); ?>img/mobile_icon57.png" />
<!-- 114 x 114 iPhone 4 icon -->
<link rel="apple-touch-icon" media="screen and (resolution: 326dpi)" href="<?php echo getthemelocation($themename); ?>img/mobile_icon114.png" />
<!-- 57 x 57 Nokia icon -->
<link rel="shortcut icon" href="<?php echo getthemelocation(); ?>img/mobile_icon57.png" />

<style>
video#videoScreen
{ 
    position: fixed;
    top: 50%;
    left: 50%;
    min-width: 100%;
    min-height: 100%;
    width: auto;
    height: auto;
    z-index: -100;
    -ms-transform: translateX(-50%) translateY(-50%);
    -moz-transform: translateX(-50%) translateY(-50%);
    -webkit-transform: translateX(-50%) translateY(-50%);
    transform: translateX(-50%) translateY(-50%);
    background: url('<?php echo getthemelocation($themename); ?>/img/backgrd_start.jpg') no-repeat;
    background-size: cover; 
}

@media screen and (max-device-width: 800px)
{
  #videoScreen
  {
    display: none;
  }
}
</style>

<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
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
  
  hcms_showFormLayer ('savelayer', 0);
  userform.submit();
}

function setwallpaper ()
{
  // set background image
  <?php if (!empty ($wallpaper) && is_image ($wallpaper)) { ?>
  document.getElementById('startScreen').style.backgroundImage = "url('<?php echo $wallpaper; ?>')";
  return true;
  <?php } elseif (!empty ($wallpaper) && is_video ($wallpaper)) { ?>
  if (html5support())
  {
    document.getElementById('videoScreen').src = "<?php echo $wallpaper; ?>";
  }
  return true;
  <?php } else { ?>
  return false;
  <?php } ?>
}

function blurbackground (blur)
{
  if (blur == true) document.getElementById('startScreen').classList.add('hcmsBlur');
  else document.getElementById('startScreen').classList.remove('hcmsBlur');
}
</script>
</head>

<body onload="<?php echo $add_onload; ?> focusform(); setwallpaper();">

  <!-- saving --> 
  <div id="savelayer" class="hcmsLoadScreen"></div>

  <!-- wallpaper -->
  <div id="startScreen" class="hcmsStartScreen">
    <?php if (!empty ($wallpaper) && is_video ($wallpaper)) { ?>
    <video id="videoScreen" playsinline="true" preload="auto" autoplay="true" loop="loop" muted="true" volume="0" poster="<?php echo getthemelocation($themename); ?>/img/backgrd_start.jpg">
      <source src="<?php echo $wallpaper; ?>" type="video/mp4">
    </video>
    <?php } ?>
  </div>

  <!-- top bar -->
  <div class="hcmsStartBar">
    <div style="position:absolute; top:15px; left:15px; float:left; text-align:left;"><img src="<?php echo getthemelocation($themename); ?>img/logo.png" style="border:0; max-width:420px; max-height:42px;" alt="hyper Content & Digital Asset Management Server - hypercms.com" /></div>
    <div class="hcmsTextWhite hcmsTextShadow" style="position:absolute; top:15px; right:15px; text-align:right;"><?php echo getescapedtext ($hcms_lang['reset-password'][$lang]); ?></div>
  </div>

  <?php
  echo showmessage ($show, 460, 70, $lang, "position:fixed; left:10px; top:95px;");
  ?>

  <!-- reset password form -->
  <div class="hcmsLogonScreen">
    <form name="userform" action="" method="post">
      <input type="hidden" name="action" value="user_save" />
      <input type="hidden" name="login" value="<?php echo $user; ?>" />
      <input type="hidden" name="forward" value="<?php echo $forward; ?>" />
      <input type="hidden" name="token" value="<?php echo $token_new; ?>" />

      <?php if ($user != "") { ?>
      <input type="hidden" name="login" value="<?php echo $user; ?>" />
      <?php } else { ?>
      <div class="hcmsTextWhite hcmsTextShadow" style="padding-top:8px;"><?php echo getescapedtext ($hcms_lang['user'][$lang]); ?> </div>
      <div><input type="text" name="login" id="login" style="width:100%; margin:3px 0px; padding:8px 5px;" tabindex="1" /> </div>
      <?php } ?>

      <?php if ($hash != "") { ?>
      <input type="hidden" name="old_password" value="<?php echo $hash; ?>" />
      <?php } else { ?>
      <div class="hcmsTextWhite hcmsTextShadow" style="padding-top:8px;"><?php echo getescapedtext ($hcms_lang['old-password'][$lang]); ?> </div>
      <div><input type="password" name="old_password" id="old_password" style="width:100%; margin:3px 0px; padding:8px 5px;" tabindex="1" /> </div>
      <?php } ?>

      <div class="hcmsTextWhite hcmsTextShadow" style="padding-top:8px;"><?php echo getescapedtext ($hcms_lang['change-password'][$lang]); ?> </div>
      <div><input type="password" name="password" style="width:100%; margin:3px 0px; padding:8px 5px;" tabindex="2" /> </div>

      <div class="hcmsTextWhite hcmsTextShadow" style="padding-top:8px;"><?php echo getescapedtext ($hcms_lang['confirm-password'][$lang]); ?> </div>
      <div><input type="password" name="confirm_password" maxlength="100" style="width:100%; margin:3px 0px; padding:8px 5px;" tabindex="3" /> </div>

      <!-- Save -->
      <div style="padding:4px 0px;">
        <button type="button" onclick="checkForm();" class="hcmsButtonGreen hcmsButtonSizeHeight" style="width:260px;" tabindex="4"><?php echo getescapedtext ($hcms_lang['save-settings'][$lang]); ?></button>
      </div>
    </form>
  </div>

<?php includefooter(); ?>
</body>
</html>
