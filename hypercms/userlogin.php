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

// input parameter for instance
$sentinstance = getrequest ("sentinstance", "publicationname");

// register instance in session and load main config of instance
registerinstance ($sentinstance);

// input parameters (standard logon)
$sentuser = getrequest ("sentuser", "objectname");
$sentpasswd = getrequest ("sentpasswd");
$is_mobile = getrequest ("is_mobile");
$is_iphone = getrequest ("is_iphone");
$html5support = getrequest ("html5support");
$lang = getrequest ("lang", "objectname", $mgmt_lang_shortcut_default);
$token = getrequest ("token");
$action = getrequest ("action");
$require = getrequest ("require");
$theme = getrequest_esc ("theme", "objectname");
// input parameters (public portal access using user hash code)
$portal = getrequest ("portal");
// input parameters (asset browser)
$userhash = getrequest ("userhash");
$objecthash = url_encode (getrequest ("objecthash"));
$filter = getrequest ("filter");
// deprecated since version 5.6.1 but still supported:
// input parameters (mail-link logon)
$hcms_user = getrequest ("hcms_user");
$hcms_pass = getrequest ("hcms_pass");
$hcms_objref = getrequest ("hcms_objref");
$hcms_objcode = getrequest ("hcms_objcode");
// deprecated since version 5.6.1 but still supported:
// secure input parameters (mail-link logon)
$hcms_user_token = getrequest ("hcms_user_token");
$hcms_id_token = getrequest ("hcms_id_token");
// input parameters (unique hash is used for access-link)
$al = getrequest ("al");
$oal = getrequest ("oal");

// initialize
$ignore_password = false;
$hcms_objformats = false;
$accesslink = false;
$rootpermission = null;
$globalpermission = null;
$localpermission = null;
$user = null;
$passwd = null;
$siteaccess = null;
$pageaccess = null;
$compaccess = null;
$superadmin = null;
$hiddenfolder = null;
$result_frameset = "";
$show = "";
$onload = "";

// include language file
if (!empty ($lang) && is_file ($mgmt_config['abs_path_cms']."language/".getlanguagefile ($lang)))
{
  require_once ($mgmt_config['abs_path_cms']."language/".getlanguagefile ($lang));
}

// detect browser and set theme
if (is_mobilebrowser () || $is_mobile == "1" || $is_mobile == "yes") $is_mobile = 1;

if (!empty ($theme)) $themename = $theme;
else $themename = "";

$error = array();

// -------------------- link types ---------------------

// portal access link since version 8.0.4
if (strpos ($portal, ".") > 0)
{
  list ($portal_site, $portal_theme) = explode (".", $portal);

  if (valid_objectname ($portal_theme))
  {
    $portal_template = $portal_theme.".portal.tpl";

    $portal_template = loadtemplate ($portal_site, $portal_template);

    // get portal user and download formats
    if (!empty ($portal_template['content']))
    {
      $temp_array = getcontent ($portal_template['content'], "<portaluser>");
      if (!empty ($temp_array[0])) $portaluser = $temp_array[0];

      if (!empty ($portaluser))
      {
        $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");

        $temp_array = selectcontent ($userdata, "<user>", "<login>", $portaluser);
      
        if (!empty ($temp_array[0]))
        {
          $temp_array = getcontent ($temp_array[0], "<hashcode>");
          if (!empty ($temp_array[0])) $userhash = $temp_array[0];
        }
      }

      $temp_array = getcontent ($portal_template['content'], "<downloadformats>");

      if (!empty ($temp_array[0]))
      {
        $hcms_objformats = json_decode ($temp_array[0], true);
      }
    }
  }
}
// access link since version 5.6.2
elseif ($al != "" && !empty ($mgmt_config['db_connect_rdbms']))
{
  $result_al = rdbms_getaccessinfo ($al);
  
  if (is_array ($result_al))
  {
    $hcms_user = $result_al['user'];
    $hcms_pass = "";
    $hcms_objref = $result_al['object_id'];
    // encrypt object ID
    $hcms_objcode = hcms_crypt ($hcms_objref);
    $ignore_password = true;

    // get download formats
    if (!empty ($result_al['formats']))
    {
      $hcms_objformats = json_decode ($result_al['formats'], true);
    }

    // if type is download link forward to file download
    if ($result_al['type'] == "dl") header ("Location: service/mediadownload.php?dl=".url_encode($al));
  }
}
// object access link since version 6.1.12
// only works if a access link user has been defined for the publication (must have a valid user hashcode for access)
elseif ($oal != "" && !empty ($mgmt_config['db_connect_rdbms']))
{
  $objectpath_esc = rdbms_getobject ($oal);
  $objecthash = $oal;

  if ($objectpath_esc != "")
  {
    // access link
    $accesslink['hcms_linking'][$objecthash] = $objectpath_esc;

    // get publication
    $site = getpublication ($objectpath_esc);

    // publication management config
    if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

    // if a user is set for general accesslinks
    if (!empty ($mgmt_config[$site]['accesslinkuser']))
    {
      $accesslinkuser = $mgmt_config[$site]['accesslinkuser'];
      
      // get user data
      $memory_user = $user;
      $user = "sys";
      $userdata = getuserinformation ();
      $user = $memory_user;
      
      // get user hashcode
      if (!empty ($userdata[$site][$accesslinkuser]['hashcode'])) $userhash = $userdata[$site][$accesslinkuser]['hashcode'];
    }
  }
}

// ------------------- deprecated link types -----------------------

// deprecated since version 5.6.1 but still supported:
// extract user and object information
if ($hcms_user_token != "")
{
  $hcms_user_string = hcms_decrypt ($hcms_user_token);
  // extract object and user information (since version 5.5.13)
  if ($hcms_user_string != "" && strpos ($hcms_user_string, "@") > 0) list ($hcms_id_string, $hcms_user_string) = explode ("@", $hcms_user_string);
  // extract user name and crypted passcode (before and after version 5.5.13)
  if ($hcms_user_string != "" && strpos ($hcms_user_string, ":") > 0) list ($hcms_user, $hcms_pass) = explode (":", $hcms_user_string);
  // extract object id and time token (since version 5.5.13)
  if ($hcms_id_string != "" && strpos ($hcms_id_string, ":") > 0)
  {
    list ($hcms_objref, $hcms_timetoken) = explode (":", $hcms_id_string);  
    // check time token and generate object code
    if (checktimetoken ($hcms_timetoken)) $hcms_objcode = hcms_crypt ($hcms_objref);
    else $hcms_objcode = "invalid";
  }

  // warning
  $errcode = "00111";
  $error[] = $mgmt_config['today']."|userlogin.php|warning|".$errcode."|deprecated user token provided for access (used before version 5.6.1)";
}

// deprecated since version 5.6.1 (will not work anymore due to the embedded object code in the token):
// extract object ID and token code (before version 5.5.13)
if ($hcms_id_token != "")
{
  $hcms_id_string = hcms_decrypt ($hcms_id_token);
  if ($hcms_id_string != "" && strpos ($hcms_id_string, ":") > 0) list ($hcms_objref, $hcms_objcode) = explode (":", $hcms_id_string);
  
  // warning
  $errcode = "00112";
  $error[] = $mgmt_config['today']."|userlogin.php|warning|".$errcode."|deprecated object token provided for access (used before version 5.5.13)";
}

// deprecated since version 5.6.1 but still supported:
// extract object ID and time token code (since version 5.5.13)
if ($hcms_objcode != "" && substr ($hcms_objcode, 0, 1) == "_")
{
  $hcms_id_string = hcms_decrypt (substr ($hcms_objcode, 1));
  // extract object id and time token (since version 5.5.13)
  if ($hcms_id_string != "" && strpos ($hcms_id_string, ":") > 0)
  {
    list ($hcms_objref_tmp, $hcms_timetoken) = explode (":", $hcms_id_string);
    // check time token and generate object code
    if (checktimetoken ($hcms_timetoken)) $hcms_objcode = hcms_crypt ($hcms_objref_tmp);    
  }
}

// if user is logged in already and mail link is not used, then forward to main frameset
if ($user != "" && $passwd != "" && $hcms_user == "")
{
  // check session of user
  checkusersession ($user);
  
  // define frameset for mobile access (also via access link)
  if ($is_mobile || getsession ("hcms_mobile")) $result_frameset = "frameset_mobile.php";
  // frameset for standard logonor access links
  else $result_frameset = "frameset_main.php";

  // forward to main frameset if check was passed
  header ("Location: ".$result_frameset);
}
// if a user is logged in and a mail-link is used, then kill existing session 
elseif ($user != "" && $hcms_user != "")
{
  killsession ($user);
}

// set filter
$filter_array = splitstring ($filter);
$filter_set = array();

if (is_array ($filter_array) && sizeof ($filter_array) > 0)
{
  foreach ($filter_array as $filter_name)
  {
    $filter_set[$filter_name] = 1;
  }
  
  setfilter ($filter_set);
}

// create secure token
$token_new = createtoken ("sys");

// --------------------------- logon ---------------------------

// check IP and user logon name of client
if (checkuserip (getuserip ()) == true)
{
  // reset password without login link (forgot password)
  if ($action == "reset" && !empty ($mgmt_config['resetpassword']))
  {
    $show = sendresetpassword ($sentuser, "resetpassword");
  }
  // reset password and send login link (for 2 factor authentication)
  elseif ($action == "request" && !empty ($mgmt_config['multifactorauth']))
  {
    $show = sendresetpassword ($sentuser, "multifactorauth");
  }
  // login
  else
  {
    // hcms_linking logon (since version 5.6.2)
    if (!empty ($hcms_user) && !empty ($hcms_objref) && !empty ($hcms_objcode) && $ignore_password == true)
    {
      $login_result = userlogin ($hcms_user, "", "", $hcms_objref, $hcms_objcode, $ignore_password);
    }
    // hcms_linking logon (before version 5.6.2)
    elseif (!empty ($hcms_user) && !empty ($hcms_pass) && !empty ($hcms_objref) && !empty ($hcms_objcode))
    {
      $login_result = userlogin ($hcms_user, $hcms_pass, "", $hcms_objref, $hcms_objcode);
    }
    // user hash provided (provided by portal or asset browser)
    elseif (!empty ($userhash))
    {
      $login_result = userlogin ("", "", $userhash, "", "", false, true, $portal);
    }
    // standard user logon
    elseif (!empty ($sentuser) && !empty ($sentpasswd) && checktoken ($token, "sys"))
    {
      $login_result = userlogin ($sentuser, $sentpasswd);
    }
    // get username and password from Basic HTTP Authentication used for SSO (if passed through)
    elseif (!empty ($_SERVER['PHP_AUTH_USER']) && !empty ($_SERVER['PHP_AUTH_PW']))
    {
      $sentuser = $_SERVER['PHP_AUTH_USER'];
      $sentpasswd = $_SERVER['PHP_AUTH_PW'];

      if (strpos ($sentuser, "/") > 0) list ($domain, $sentuser) = explode ("/", $sentuser);
      elseif (strpos ($sentuser, "\\") > 0) list ($domain, $sentuser) = explode ("\\", $sentuser);

      $login_result = userlogin ($sentuser, $sentpasswd);
    }
    // get authenticated username from webserver (if passed through)
    elseif (!empty ($_SERVER['LOGON_USER']) || !empty ($_SERVER['REMOTE_USER']) || !empty ($_SERVER['AUTH_USER']))
    {
      if (!empty ($_SERVER['LOGON_USER'])) $user = $_SERVER['LOGON_USER'];
      elseif (!empty ($_SERVER['REMOTE_USER'])) $sentuser = $_SERVER['REMOTE_USER'];
      elseif (!empty ($_SERVER['AUTH_USER'])) $sentuser = $_SERVER['AUTH_USER'];

      if (strpos ($sentuser, "/") > 0) list ($domain, $sentuser) = explode ("/", $sentuser);
      elseif (strpos ($sentuser, "\\") > 0) list ($domain, $sentuser) = explode ("\\", $sentuser);

      $login_result = userlogin ($sentuser, "*Null*");
    }
    // get authenticated user name from OAuth client (requires the Connector module and a general LDAP/AD user account)
    elseif (!empty ($mgmt_config['ldap_admin_username']) && !empty ($mgmt_config['ldap_admin_password']) && function_exists ("verifyoauthclient"))
    {
      $sentuser = verifyoauthclient ();

      if (!empty ($sentuser))
      {
        if (strpos ($sentuser, "/") > 0) list ($domain, $sentuser) = explode ("/", $sentuser);
        elseif (strpos ($sentuser, "\\") > 0) list ($domain, $sentuser) = explode ("\\", $sentuser);

        $login_result = userlogin ($sentuser, "*Null*");
      }
    }
    else $login_result = false;

    // if logon was successful and user account has not been expired
    if (!empty ($login_result['auth']))
    {
      // register user in session
      $login_result = registeruser ($sentinstance, $login_result, $accesslink, $hcms_objformats, $is_mobile, $is_iphone, $html5support);

      // register asset browser if a user hash is provided for the asset browser and no access linking or portal is used
      if (!empty ($userhash) && empty ($oal) && empty ($portal))
      {
        registerassetbrowser ($userhash, $objecthash);
      }

      // define frameset
      if (!empty ($login_result['mobile'])) $result_frameset = "frameset_mobile.php";
      else $result_frameset = "frameset_main.php";
    }

    // user is logged in (forward)
    if (!empty ($login_result['writesession']))
    {
      $onload = "location.href='".cleandomain ($mgmt_config['url_path_cms']).$result_frameset."';";
    }
  
    // user password is expired (forward) and no access link is used
    if (!empty ($login_result['resetpassword']) && empty ($portal) && empty ($al) && empty ($oal))
    {
      $onload = "location.href='".cleandomain ($mgmt_config['url_path_cms'])."resetpassword.php?hash=".url_encode ($sentpasswd)."&forward=".url_encode ($result_frameset)."';";
    }

    // message from function userlogin
    if (!empty ($login_result['message'])) $show = $login_result['message'];
  }
  
  // login form (if login is missing or failed)
  if (!isset ($login_result) || empty ($login_result['auth']))
  {
    if ($show != "") $show = "<div class=\"hcmsPriorityAlarm hcmsTextWhite\" style=\"padding:5px;\">".$show."</div>\n";

    if (!empty ($mgmt_config['instances']) && is_dir ($mgmt_config['instances'])) $show .= "
        <div id=\"sentinstance_container\" ".($require == "password" ? "style=\"position:absolute; visibility:hidden;\"" :  "").">
          <input type=\"text\" id=\"sentinstance\" name=\"sentinstance\" placeholder=\"".getescapedtext ($hcms_lang['instance'][$lang])."\" value=\"".$sentinstance."\" maxlength=\"100\" style=\"box-sizing:border-box; width:100%; margin:3px 0px; padding:8px 5px;\" tabindex=\"1\" /><br/>
        </div>";
          
    $show .= "
        <div id=\"sentuser_container\">
          <input type=\"".($require == "password" ? "hidden" : "text")."\" id=\"sentuser\" name=\"sentuser\" placeholder=\"".getescapedtext ($hcms_lang['user'][$lang])."\" value=\"".$sentuser."\" maxlength=\"100\" style=\"box-sizing:border-box; width:100%; margin:3px 0px; padding:8px 5px;\" tabindex=\"2\" />";
          
    if (empty ($mgmt_config['multifactorauth']) || $require == "password") $show .= "
         <br/>
         <input type=\"password\" id=\"sentpasswd\" name=\"sentpasswd\" placeholder=\"".getescapedtext ($hcms_lang['password'][$lang])."\" value=\"".$sentpasswd."\" maxlength=\"100\" style=\"box-sizing:border-box; width:100%; margin:3px 0px; padding:8px 5px;\" tabindex=\"3\" />";
    
    $show .= "
        </div>

        <div class=\"hcmsTextWhite hcmsTextShadow\" style=\"padding:4px 2px;\">
          <label><input id=\"remember\" type=\"checkbox\" /> ".getescapedtext ($hcms_lang['remember-me'][$lang])."</label>
        </div>

        <div style=\"padding:4px 0px;\">
          <button type=\"submit\" class=\"hcmsButtonGreen hcmsButtonSizeHeight\" style=\"width:100%;\" tabindex=\"4\">".((empty ($mgmt_config['multifactorauth']) || $require == "password") ? getescapedtext ($hcms_lang['sign-in'][$lang]) : getescapedtext ($hcms_lang['send-e-mail'][$lang]))."</button>
        </div>

        <div class=\"hcmsTextWhite hcmsTextShadow\" style=\"padding:4px 0px; font-size:small; font-weight:normal;\">".getescapedtext ($hcms_lang['popups-must-be-allowed'][$lang])."</div>";
        
      if (!empty ($mgmt_config['resetpassword']) && empty ($mgmt_config['multifactorauth'])) $show .= "
        <div class=\"hcmsTextWhite hcmsTextShadow\" style=\"box-sizing:border-box; padding:4px 0px; font-size:small; font-weight:normal; cursor:pointer;\" onclick=\"resetpassword()\">".getescapedtext ($hcms_lang['reset-password'][$lang])."</div>";
        
      if (!empty ($mgmt_config['userregistration'])) $show .= "
        <div class=\"hcmsTextWhite hcmsTextShadow\" style=\"box-sizing:border-box; padding:4px 0px; font-size:small; font-weight:normal; cursor:pointer;\" onclick=\"location.href='userregister.php';\">".getescapedtext ($hcms_lang['sign-up'][$lang])."</div>";
  }
  // login successful
  else
  {
    $show = "<div class=\"hcmsTextWhite\" style=\"padding:5px;\">".$show."</div>\n";
  }
}
// client ip is banned
else
{
  $show = "<div class=\"hcmsPriorityAlarm hcmsTextWhite\" style=\"padding:5px;\">".str_replace ("%timeout%", $mgmt_config['logon_timeout'], $hcms_lang['you-have-been-banned'][$lang])."</div>\n";
}

// wallpaper
$wallpaper = getwallpaper ();

// save log
savelog (@$error);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=380, initial-scale=0.9, maximum-scale=1.0, user-scalable=0" />

<link rel="stylesheet" href="<?php echo getthemelocation($themename); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation($themename)."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />

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
    background: url('<?php echo getthemelocation($themename); ?>/img/backgrd_start.png') no-repeat;
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

<script type="text/javascript" src="javascript/main.min.js"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
<script type="text/javascript">

function focusform()
{
  <?php if (!empty ($mgmt_config['instances'])) { ?>
  // if instance parameter has been provided via GET
  if (document.getElementById('sentinstance'))
  {
    if (hcms_getURLparameter('i') !== null)
    {
      document.getElementById('sentinstance').value = hcms_getURLparameter('i');
      document.getElementById('sentinstance_container').style.display = "none";
    }
    // if local storage saved instance
    else if (localStorage.getItem('instance') !== null)
    {
      document.getElementById('sentinstance').value = localStorage.getItem('instance');
      document.getElementById('sentinstance_container').style.display = "none";
    }
  }
  <?php } ?>

  // username and password from local storage
  if (document.getElementById('sentuser'))
  {
    if (localStorage.getItem('username') !== null) document.getElementById('sentuser').value = localStorage.getItem('username'); 
    if (localStorage.getItem('password') !== null && document.getElementById('sentpasswd')) document.getElementById('sentpasswd').value = localStorage.getItem('password');
    
    document.forms['login'].elements['sentuser'].focus();
  }
}

function is_mobilebrowser()
{
  if (hcms_mobileBrowser())
  {
    if (document.forms['login']) document.forms['login'].elements['is_mobile'].value = '1';
    return 1;
  }
  else return 0;
}

function is_iOS()
{
  if (hcms_iOS())
  {
    if (document.forms['login']) document.forms['login'].elements['is_iphone'].value = '1';
    return 1;
  }
  else return 0;
}

function html5support()
{
  if (hcms_html5file())
  {
    if (document.forms['login']) document.forms['login'].elements['html5support'].value = '1';
    return 1;
  }
  else return 0;
}

function submitlogin()
{
  if (document.forms['login'])
  {
    document.forms['login'].elements['action'].value = 'login';
    
    if (document.getElementById('sentinstance')) var instance = document.getElementById('sentinstance').value;
    else var instance = '';
    if (document.getElementById('sentuser')) var username = document.getElementById('sentuser').value;
    else var username = '';
    if (document.getElementById('sentpasswd')) var password = document.getElementById('sentpasswd').value;
    else var password = '';
    
    if (username.trim() == "") document.getElementById('sentuser').className = 'hcmsRequiredInput';
    else document.getElementById('sentuser').className = '';
    
    if (password.trim() == "") document.getElementById('sentpasswd').className = 'hcmsRequiredInput';
    else document.getElementById('sentpasswd').className = '';
  
    if ((instance != "" || username != "" || password != "") && document.getElementById('remember').checked == true)
    {
      if (document.getElementById('sentinstance')) localStorage.setItem('instance', instance);
      localStorage.setItem('username', username);
      localStorage.setItem('password', password);
    }
  
    if (username.trim() != "" && password.trim() != "")
    {
      // local load screen
      if (document.getElementById('hcmsLoadScreen')) document.getElementById('hcmsLoadScreen').style.display='inline';
      return true;
    }
    else return false;
  }
  else return false;
}

function resetpassword()
{
  if (document.forms['login'])
  {
    document.forms['login'].elements['action'].value = 'reset';
    
    if (document.getElementById('sentinstance')) var instance = document.getElementById('sentinstance').value;
    else var instance = '';

    if (document.getElementById('sentuser')) var username = document.getElementById('sentuser').value;
    else var username = '';
    
    if (username.trim() == "") document.getElementById('sentuser').className = 'hcmsRequiredInput';
    else document.getElementById('sentuser').className = '';
    
    if (document.getElementById('sentpasswd'))
    {
      document.getElementById('sentpasswd').value = '';
      document.getElementById('sentpasswd').className = '';
    }
    
    if (username.trim() != "")
    {
      // local load screen
      if (document.getElementById('hcmsLoadScreen')) document.getElementById('hcmsLoadScreen').style.display='inline';
      document.forms['login'].submit();
    }
    else return false;
  }
  else return false;
}

function requestpassword()
{
  if (document.forms['login'])
  {
    document.forms['login'].elements['action'].value = 'request';
    document.forms['login'].elements['require'].value = 'password';
    
    if (document.getElementById('sentinstance')) var instance = document.getElementById('sentinstance').value;
    else var instance = '';
    if (document.getElementById('sentuser')) var username = document.getElementById('sentuser').value;
    else var username = '';
    
    if (username.trim() == "") document.getElementById('sentuser').className = 'hcmsRequiredInput';
    else document.getElementById('sentuser').className = '';
    
    if (document.getElementById('sentpasswd')) document.getElementById('sentpasswd').className = '';
    
    if (username.trim() != "") return true;
    else return false;
  }
  else return false;
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

<?php if (!empty ($onload)) echo $onload; ?>
</script>
</head>

<body onload="focusform(); is_mobilebrowser(); is_iOS(); html5support(); setwallpaper();">

  <!-- load screen --> 
  <div id="hcmsLoadScreen" class="hcmsLoadScreen"></div>

  <!-- wallpaper -->
  <div id="startScreen" class="hcmsStartScreen">
    <?php if (!empty ($wallpaper) && is_video ($wallpaper)) { ?>
    <video id="videoScreen" playsinline="true" preload="auto" autoplay="true" loop="loop" muted="true" volume="0" poster="<?php echo getthemelocation($themename); ?>/img/backgrd_start.png">
      <source src="<?php echo $wallpaper; ?>" type="video/mp4">
    </video>
    <?php } ?>
  </div>

  <!-- top bar -->
  <div class="hcmsStartBar">
    <div style="position:absolute; top:15px; left:15px; float:left; text-align:left;"><img src="<?php echo getthemelocation($themename); ?>img/logo.png" style="border:0; max-width:420px; max-height:42px;" alt="hyper Content & Digital Asset Management Server - hypercms.com" /></div>
    <div style="position:absolute; top:15px; right:15px; text-align:right;"></div>
  </div>
  
  <!-- logon form -->
  <div class="hcmsLogonScreen" onkeyup="blurbackground(true);" onmouseout="blurbackground(false);">
    <?php
    echo "
    <form name=\"login\" method=\"post\" onsubmit=\"return ".((empty ($mgmt_config['multifactorauth']) || $require == "password") ? "submitlogin()" : "requestpassword()").";\" style=\"width:260px; opacity:0.9;\" action=\"\">
      <input type=\"hidden\" name=\"token\" value=\"".$token_new."\" />
      <input type=\"hidden\" name=\"action\" value=\"login\" />
      <input type=\"hidden\" name=\"require\" value=\"\" />
      <input type=\"hidden\" name=\"is_mobile\" value=\"0\" />
      <input type=\"hidden\" name=\"is_iphone\" value=\"0\" />
      <input type=\"hidden\" name=\"html5support\" value=\"0\" />
      ".$show."
    </form>\n";
   ?>
  </div>

<?php includefooter(); ?>
</body>
</html>