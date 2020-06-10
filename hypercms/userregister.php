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
// version info
require ("version.inc.php");


// kill session if user is not logged in
if ($user == "") killsession ();

// input parameters
$action = getrequest ("action");
$site = getrequest_esc ("site"); // site can be *Null* which is not a valid name!
$login = getrequest_esc ("login", "objectname");
$password = getrequest ("password");
$confirm_password = getrequest ("confirm_password");
$realname = getrequest_esc ("realname");
$lang = getrequest ("lang", "objectname", $mgmt_lang_shortcut_default);
$language = $lang = getrequest ("language", "objectname", $mgmt_lang_shortcut_default);
$timezone = getrequest ("timezone");
$theme = getrequest_esc ("theme", "objectname");
$email = getrequest_esc ("email");
$phone = getrequest_esc ("phone");
$signature = getrequest_esc ("signature");
$usersite = getrequest_esc ("usersite");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php")) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// include language file
if (!empty ($lang) && is_file ($mgmt_config['abs_path_cms']."language/".getlanguagefile ($lang)))
{
  require_once ($mgmt_config['abs_path_cms']."language/".getlanguagefile ($lang));
}

// ------------------------------ permission section --------------------------------

if (empty ($mgmt_config['userregistration']))
{
  echo showinfopage ($hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang], $lang);
  exit;
}

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";

// detect browser and set theme
if (is_mobilebrowser () || $is_mobile == "1" || $is_mobile == "yes") $themename = "mobile";
elseif (!empty ($theme)) $themename = $theme;
else $themename = "";

// save user
if ($action == "user_register" && checktoken ($token, "sys") && !empty ($mgmt_config['userregistration']))
{
  // reload GUI
  $add_onload = "";
  $reg_info = "";
  
  // create user
  $result = createuser ($site, $login, $password, $confirm_password, "sys");

  // edit user settings
  if ($result['result'] == true)
  {
    // theme
    if (!empty ($mgmt_config[$site]['theme'])) $theme = $mgmt_config[$site]['theme'];
    elseif (!empty ($mgmt_config['theme'])) $theme = $mgmt_config['theme'];
    
    // user group
    if (!empty ($mgmt_config[$site]['registration_group'])) 
    {
      $usergroup = $mgmt_config[$site]['registration_group'];
      $reg_info = getescapedtext ($hcms_lang['please-sign-in'][$lang]);
    }
    else
    {
      $usergroup = "";
      $reg_info = getescapedtext ($hcms_lang['please-wait-for-the-approval'][$lang]);
    }
    
    $result = edituser ($site, $login, "*Leave*", "*Leave*", "*Leave*", "0", $realname, $language, $timezone, $theme, $email, $phone, $signature, $usergroup, $site, "", "", "sys");
    
    if (!empty ($mgmt_config[$site]['registration_notify'])) 
    {
      $user_array = splitstring ($mgmt_config[$site]['registration_notify']);
      $userinfo = getuserinformation ();
    
      foreach ($user_array as $to_user)
      {
        $user_lang = $userinfo[$site][$to_user]['language']; 
        sendmessage ("", $to_user, $hcms_lang['registration-of-new-users'][$user_lang], $hcms_lang['the-new-user-was-created'][$user_lang].": ".$login, "", $site);
      }
    }
  }

  // message
  $show = $result['message']." ".$reg_info;
  
  // save log
  savelog (@$error);
}

// wallpaper
$wallpaper = "";

if ($themename != "mobile")
{
  if (is_file ($mgmt_config['abs_path_cms']."theme/".$hcms_themename."/img/wallpaper.jpg")) $wallpaper = cleandomain ($mgmt_config['url_path_cms']."theme/".$hcms_themename."/img/wallpaper.jpg");
  elseif (is_file ($mgmt_config['abs_path_cms']."theme/".$hcms_themename."/img/wallpaper.png")) $wallpaper = cleandomain ($mgmt_config['url_path_cms']."theme/".$hcms_themename."/img/wallpaper.png");
  elseif (!empty ($mgmt_config['wallpaper'])) $wallpaper = $mgmt_config['wallpaper'];
  else $wallpaper = getwallpaper ($mgmt_config['version']);
}

// create secure token
$token_new = createtoken ("sys");
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=380, initial-scale=0.9, maximum-scale=1.0, user-scalable=0" />

<link rel="stylesheet" href="<?php echo getthemelocation($themename); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />

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
  background: url('<?php echo getthemelocation($theme); ?>/img/backgrd_start.png') no-repeat;
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

<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<script type="text/javascript">
function is_mobilebrowser()
{
  if (document.forms['userform'] && hcms_mobileBrowser())
  {
    document.forms['userform'].elements['is_mobile'].value = '1';
    return true;
  }
  else return false;
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
  
  if (userform.elements['login'].value.trim() == "")
  {
    userform.elements['login'].className = 'hcmsRequiredInput';
    userform.elements['login'].focus();
    return false;
  }
  else userform.elements['login'].className = '';
  
  if (userform.elements['password'].value.trim() == "")
  {
    userform.elements['password'].className = 'hcmsRequiredInput'
    userform.elements['password'].focus();
    return false;
  }
  else userform.elements['password'].className = '';
  
  if (userform.elements['confirm_password'].value.trim() == "")
  {
    userform.elements['confirm_password'].className = 'hcmsRequiredInput'
    userform.elements['confirm_password'].focus();
    return false;
  }
  else userform.elements['confirm_password'].className = '';
  
  if (userform.elements['password'].value != userform.elements['confirm_password'].value)
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['your-submitted-passwords-are-not-equal'][$lang]); ?>"));
    userform.elements['password'].className = 'hcmsRequiredInput'
    userform.elements['confirm_password'].className = 'hcmsRequiredInput'
    userform.elements['password'].focus();
    return false;
  }
  else
  {
    userform.elements['password'].className = '';
    userform.elements['confirm_password'].className = '';
  }
  
  if (!checkForm_chars (userform.elements['password'].value, "-_#+*[]%$�!?@"))
  {
    userform.elements['password'].className = 'hcmsRequiredInput'
    userform.elements['password'].focus();
    return false;
  }
  else userform.elements['password'].className = '';
   
  if (!checkForm_chars (userform.elements['confirm_password'].value, "-_#+*[]%$�!?@"))
  {
    userform.elements['confirm_password'].className = 'hcmsRequiredInput'
    userform.elements['confirm_password'].focus();
    return false;
  }
  else userform.elements['confirm_password'].className = '';
  
  if (userform.elements['realname'].value.trim() == "")
  {
    userform.elements['realname'].className = 'hcmsRequiredInput'
    userform.elements['realname'].focus();
    return false;
  }
  else userform.elements['realname'].className = '';
  
  if (userform.elements['email'].value == "" || (userform.elements['email'].value != "" && (userform.elements['email'].value.indexOf('@') == -1 || userform.elements['email'].value.indexOf('.') == -1)))
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-insert-a-valid-e-mail-adress'][$lang]); ?>"));
    userform.elements['email'].className = 'hcmsRequiredInput'
    userform.elements['email'].focus();
    return false;
  }
  else userform.elements['email'].className = '';
  
  if (userform.elements['sites']) selectall = selectAll (userform.elements['sites']);
  
  if (selectall == true)
  {
    userform.submit();
  }
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

<body onload="setwallpaper(); is_mobilebrowser();">

<div id="startScreen" class="hcmsStartScreen">
  <?php if (!empty ($wallpaper) && is_video ($wallpaper)) { ?>
  <video id="videoScreen" playsinline="true" preload="auto" autoplay="true" loop="loop" muted="true" volume="0" poster="<?php echo getthemelocation($themename); ?>/img/backgrd_start.png">
    <source src="<?php echo $wallpaper; ?>" type="video/mp4">
  </video>
  <?php } ?>
</div>

<div class="hcmsStartBar">
  <div style="position:absolute; top:15px; left:15px; float:left; text-align:left;"><img src="<?php echo getthemelocation($themename); ?>img/logo.png" style="border:0; height:48px;" alt="hypercms.com" /></div>
  <div style="position:absolute; top:15px; right:15px; text-align:right;"></div>
</div>

<div class="hcmsLogonScreen" style="width:300px; margin-left:-150px; margin-top:-240px;" onkeyup="blurbackground(true);" onmouseout="blurbackground(false);">

<?php
if ($show != "") echo "<div class=\"hcmsPriorityAlarm hcmsTextWhite\" style=\"width:290px; padding:5px;\">".$show."</div>\n";
?>  

<?php if (empty ($result['result'])) { ?>
  <form name="userform" action="" method="post" onsubmit="return checkForm();">
    <input type="hidden" name="action" value="user_register">
    <input type="hidden" name="is_mobile" value="0" />
    <input type="hidden" name="site" value="<?php echo $site; ?>">
    <input type="hidden" name="token" value="<?php echo $token_new; ?>">
    
    <div class="hcmsTextWhite hcmsTextShadow"><?php echo getescapedtext ($hcms_lang['user-name'][$lang]); ?> </div>
    <div><input type="text" name="login" style="width:300px;" value="<?php echo $login; ?>" /></div>
    
    <div class="hcmsTextWhite hcmsTextShadow"><?php echo getescapedtext ($hcms_lang['password'][$lang]); ?> </div>
    <div><input type="password" name="password" style="width:300px;" value="<?php echo $password; ?>" /></div>

    <div class="hcmsTextWhite hcmsTextShadow"><?php echo getescapedtext ($hcms_lang['confirm-password'][$lang]); ?> </div>
    <div><input type="password" name="confirm_password" style="width:300px;" value="<?php echo $confirm_password; ?>" /></div>

    <div class="hcmsTextWhite hcmsTextShadow"><?php echo getescapedtext ($hcms_lang['name'][$lang]); ?> </div>
    <div><input type="text" name="realname" style="width:300px;" value="<?php echo $realname; ?>" /></div>

    <div class="hcmsTextWhite hcmsTextShadow"><?php echo getescapedtext ($hcms_lang['e-mail'][$lang]); ?> </div>
    <div><input type="text" name="email" style="width:300px;" value="<?php echo $email; ?>" /></div>

    <div class="hcmsTextWhite hcmsTextShadow"><?php echo getescapedtext ($hcms_lang['phone'][$lang]); ?> </div>
    <div><input type="text" name="phone" style="width:300px;" value="<?php echo $phone; ?>" /></div>

    <div class="hcmsTextWhite hcmsTextShadow"><?php echo getescapedtext ($hcms_lang['signature'][$lang]); ?> </div>
    <div><textarea name="signature" wrap="VIRTUAL" style="width:300px; height:60px;"><?php echo $signature; ?></textarea></div>

    <div class="hcmsTextWhite hcmsTextShadow"><?php echo getescapedtext ($hcms_lang['language'][$lang]); ?> </div>
    <div>
      <select name="language" style="width:300px;">
      <?php
      if (!empty ($mgmt_lang_shortcut) && is_array ($mgmt_lang_shortcut))
      {
        foreach ($mgmt_lang_shortcut as $lang_opt)
        {
          if ($language == $lang_opt) $selected = "selected=\"selected\"";
          else $selected = "";
          
          echo "<option value=\"".$lang_opt."\" ".$selected.">".$mgmt_lang_name[$lang_opt]."</option>\n";
        }
      }
      ?>
      </select>
    </div>

    <div class="hcmsTextWhite hcmsTextShadow"><?php echo getescapedtext ($hcms_lang['timezone'][$lang]); ?> </div>
    <div>
      <select name="timezone" style="width:300px;">
      <?php
      $timezone_array = timezone_identifiers_list();

      if (is_array ($timezone_array) && sizeof ($timezone_array) > 0)
      {
        $timezone_array = array_unique ($timezone_array);
        natcasesort ($timezone_array);
        
        foreach ($timezone_array as $tz)
        {
          if ($timezone == $tz) $selected = "selected=\"selected\"";
          else $selected = "";
          
          echo "
          <option value=\"".$tz."\" ".$selected.">".$tz."</option>";
        }
      }
      ?>
      </select>
    </div>

    <?php
    // check if publication defines a theme
    if (empty ($mgmt_config['theme']) && empty ($mgmt_config[$site]['theme'])) {
    ?>
    <div class="hcmsTextWhite hcmsTextShadow"><?php echo getescapedtext ($hcms_lang['theme'][$lang]); ?> </div>
    <div>
      <select name="theme" style="width:300px;">
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
            if ($theme == $theme_opt) $selected = "selected=\"selected\"";
            else $selected = "";
            
            $theme_array[] = $theme_opt;
          }
        }
        
        if (sizeof ($theme_array) > 0)
        {
          natcasesort ($theme_array);
          reset ($theme_array);
          
          foreach ($theme_array as $temp)
          {
            if ($temp == $theme) $selected = "selected=\"selected\"";
            else $selected = "";
            
            echo "<option value=\"".$temp."\" ".$selected.">".ucfirst ($temp)."</option>\n";
          }
        }
      }
      ?>
      </select>
    </div>
    <?php } ?>
    
    <?php
    if (empty ($site))
    {    
      echo "
    <div class=\"hcmsTextWhite hcmsTextShadow\">".getescapedtext ($hcms_lang['publication'][$lang])."</div>
    <div><select name=\"site\" style=\"width:300px;\">";

      $inherit_db = inherit_db_read ("sys");      
      $site_array = array();

      if ($inherit_db != false && is_array ($inherit_db))
      {                        
        foreach ($inherit_db as $inherit_db_record)
        {
          if ($inherit_db_record['parent'] != "")
          {
            $site = $inherit_db_record['parent'];
            
            // publication management config
            if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php")) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
            
            if (!empty ($mgmt_config[$site]['registration'])) $site_array[] = $site;
          }
        }
        
        natcasesort ($site_array);
        reset ($site_array);
        
        if (is_array ($site_array) && sizeof ($site_array) > 0)
        {
          foreach ($site_array as $temp) echo "
      <option value=\"".$temp."\">".$temp."</option>";
        }
      }

      echo "
    </select></div>";
    }    
    ?>  
    <div><button type="submit" class="hcmsButtonGreen hcmsButtonSizeHeight" style="width:300px; margin-top:10px;"><?php echo getescapedtext ($hcms_lang['sign-up'][$lang]); ?></button></div>
<?php } ?>

    <div class="hcmsTextWhite hcmsTextShadow" style="padding:4px 0px; font-size:small; font-weight:normal; cursor:pointer;" onclick="document.location.href='userlogin.php';"><?php echo getescapedtext ($hcms_lang['sign-in'][$lang]); ?></div>

</div>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>