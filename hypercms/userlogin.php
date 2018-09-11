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
$theme = getrequest ("theme");
// input parameters (assetbrowser)
$sentinstance = getrequest ("instance", "publicationname");
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

// include language file
if (!empty ($lang) && is_file ($mgmt_config['abs_path_cms']."language/".getlanguagefile ($lang)))
{
  require_once ($mgmt_config['abs_path_cms']."language/".getlanguagefile ($lang));
}

// detect browser and set theme
if (is_mobilebrowser () || $is_mobile == "1" || $is_mobile == "yes") $themename = "mobile";
elseif (!empty ($theme)) $themename = $theme;
else $themename = "";

// access link since version 5.6.2
$ignore_password = false;
$hcms_objformats = false;

if ($al != "")
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
$accesslink = false;
    
if ($oal != "" && !empty ($mgmt_config['db_connect_rdbms']))
{
  $objectpath_esc = rdbms_getobject ($oal);
  
  if ($objectpath_esc != "")
  {
    $accesslink = array();
    $accesslink['hcms_linking']['publication'] = $site = getpublication ($objectpath_esc);
    $accesslink['hcms_linking']['cat'] = getcategory ($site, $objectpath_esc);
    $objectpath = deconvertpath ($objectpath_esc, "file");
    
    if (getobject ($objectpath) == ".folder")
    {
      $accesslink['hcms_linking']['location'] = getlocation ($objectpath);
      $accesslink['hcms_linking']['object'] = "";
      $accesslink['hcms_linking']['type'] = "Folder";
    }
    else
    {
      $accesslink['hcms_linking']['location'] = getlocation ($objectpath);
      $accesslink['hcms_linking']['object'] = getobject ($objectpath);
      $accesslink['hcms_linking']['type'] = "Object";
    }

    // publication management config
    if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    
    // if a user is set for general accesslinks
    if (!empty ($mgmt_config[$site]['accesslinkuser']))
    {
      $accesslinkuser = $mgmt_config[$site]['accesslinkuser'];
      
      // get user data
      $user = "sys";
      $userdata = getuserinformation ();
      
      // get user hashcode
      if (!empty ($userdata[$site][$accesslinkuser]['hashcode'])) $userhash = $userdata[$site][$accesslinkuser]['hashcode'];
    }
  }
}

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
  $error[] = $mgmt_config['today']."|userlogin.php|warning|$errcode|deprecated user token provided for access (used before version 5.6.1)";
}

// deprecated since version 5.6.1 (will not work anymore due to the embedded object code in the token):
// extract object ID and token code (before version 5.5.13)
if ($hcms_id_token != "")
{
  $hcms_id_string = hcms_decrypt ($hcms_id_token);
  if ($hcms_id_string != "" && strpos ($hcms_id_string, ":") > 0) list ($hcms_objref, $hcms_objcode) = explode (":", $hcms_id_string);
  
  // warning
  $errcode = "00112";
  $error[] = $mgmt_config['today']."|userlogin.php|warning|$errcode|deprecated object token provided for access (used before version 5.5.13)";
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

// logon
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

// check IP and user logon name of client
if (checkuserip (getuserip ()) == true)
{
  // reset password
  if ($action == "reset")
  {
    $show = sendresetpassword ($sentuser, false);
  }
  // reset password and send login link
  elseif ($action == "request")
  {
    $show = sendresetpassword ($sentuser, true);
  }
  // login
  else
  {
    // hcms_linking logon /since version 5.6.2)
    if (!empty ($hcms_user) && !empty ($hcms_objref) && !empty ($hcms_objcode) && $ignore_password == true)
    {
      $login_result = userlogin ($hcms_user, "", "", $hcms_objref, $hcms_objcode, $ignore_password);
    }
    // hcms_linking logon (before version 5.6.2)
    elseif (!empty ($hcms_user) && !empty ($hcms_pass) && !empty ($hcms_objref) && !empty ($hcms_objcode))
    {
      $login_result = userlogin ($hcms_user, $hcms_pass, "", $hcms_objref, $hcms_objcode);
    }
    // user hash provided
    elseif (!empty ($userhash))
    {
      $login_result = userlogin ("", "", $userhash, "", "");
    }
    // standard user logon
    elseif (!empty ($sentuser) && !empty ($sentpasswd) && checktoken ($token, "sys"))
    {
      $login_result = userlogin ($sentuser, $sentpasswd);
    }
    else $login_result = false;
  
    if (!empty ($login_result['auth']))
    {	
      // register user in session
      $login_result = registeruser ($sentinstance, $login_result, $accesslink, $hcms_objformats, $is_mobile, $is_iphone, $html5support);
      
      // user hash is provided for the assetbrowser or object access links
      if (!empty ($userhash) && empty ($oal))
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
      $show = "
      <script type=\"text/javascript\">
      location.href='".$mgmt_config['url_path_cms'].$result_frameset."';
      </script>
    
      ".$login_result['message']."\n";
    }
    // login name or password are false
    elseif (empty ($login_result['auth']) || empty ($login_result['writesession']))
    {
      $show = str_replace ("%timeout%", $mgmt_config['logon_timeout'], $login_result['message']);
    }
  }

  // login form
  if (!isset ($login_result) || empty ($login_result['auth']))
  {
    if ($show != "") $show = "<div class=\"hcmsPriorityAlarm hcmsTextWhite\" style=\"padding:5px;\">".$show."</div>\n";
          
    if (!empty ($mgmt_config['instances']) && is_dir ($mgmt_config['instances'])) $show .= "
        <div id=\"sentinstance_container\" ".($require == "password" ? "style=\"position:absolute; visibility:hidden;\"" :  "").">
          <input type=\"text\" id=\"sentinstance\" name=\"sentinstance\" placeholder=\"".getescapedtext ($hcms_lang['instance'][$lang])."\" maxlength=\"100\" style=\"width:250px; margin:3px 0px; padding:8px 5px;\" tabindex=\"1\" /><br/>
        </div>";
          
    $show .= "
        <div id=\"sentuser_container\">
          <input type=\"".($require == "password" ? "hidden" : "text")."\" id=\"sentuser\" name=\"sentuser\" placeholder=\"".getescapedtext ($hcms_lang['user'][$lang])."\" value=\"".$sentuser."\" maxlength=\"100\" style=\"width:250px; margin:3px 0px; padding:8px 5px;\" tabindex=\"2\" />";
          
    if (empty ($mgmt_config['multifactorauth']) || $require == "password") $show .= "
         <br/>
         <input type=\"password\" id=\"sentpasswd\" name=\"sentpasswd\" placeholder=\"".getescapedtext ($hcms_lang['password'][$lang])."\" maxlength=\"100\" style=\"width:250px; margin:3px 0px; padding:8px 5px;\" tabindex=\"3\" />";
    
    $show .= "
        </div>

        <div class=\"hcmsTextWhite hcmsTextShadow\" style=\"padding:4px 2px;\">
          <label><input id=\"remember\" type=\"checkbox\" /> ".getescapedtext ($hcms_lang['remember-me'][$lang])."</label>
        </div>

        <div style=\"padding:4px 0px;\">
          <button type=\"submit\" class=\"hcmsButtonGreen hcmsButtonSizeHeight\" style=\"width:260px;\" tabindex=\"4\">".((empty ($mgmt_config['multifactorauth']) || $require == "password") ? getescapedtext ($hcms_lang['sign-in'][$lang]) : getescapedtext ($hcms_lang['send-e-mail'][$lang]))."</button>
        </div>

        <div class=\"hcmsTextWhite hcmsTextShadow\" style=\"padding:4px 0px; font-size:small; font-weight:normal;\">".getescapedtext ($hcms_lang['popups-must-be-allowed'][$lang])."</div>";
        
      if (!empty ($mgmt_config['resetpassword']) && empty ($mgmt_config['multifactorauth'])) $show .= "
        <div class=\"hcmsTextWhite hcmsTextShadow\" style=\"padding:4px 0px; font-size:small; font-weight:normal; cursor:pointer;\" onclick=\"resetpassword()\">".getescapedtext ($hcms_lang['reset-password'][$lang])."</div>";
        
      if (!empty ($mgmt_config['userregistration'])) $show .= "
        <div class=\"hcmsTextWhite hcmsTextShadow\" style=\"padding:4px 0px; font-size:small; font-weight:normal; cursor:pointer;\" onclick=\"location.href='userregister.php';\">".getescapedtext ($hcms_lang['sign-up'][$lang])."</div>";
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

  // save log
  savelog (@$error);
}
// client ip is banned
else
{
  $show = "<p class=\"hcmsPriorityAlarm hcmsTextWhite\" style=\"padding:5px;\">".str_replace ("%timeout%", $mgmt_config['logon_timeout'], $hcms_lang['you-have-been-banned'][$lang])."</p>\n";
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=380, initial-scale=0.9, maximum-scale=1.0, user-scalable=0" />

<link rel="stylesheet" href="<?php echo getthemelocation($themename); ?>css/main.css" type="text/css">

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

<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
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
  
    if (username.trim() != "" && password.trim() != "") return true;
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
    
    if (document.getElementById('sentpasswd')) document.getElementById('sentpasswd').className = '';
    
    if (username.trim() != "")
    {
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
  <?php if (!empty ($wallpaper) && is_image ($wallpaper)) { ?>
  document.body.style.backgroundImage = "url('<?php echo $wallpaper; ?>')";
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
</script>
</head>

<body class="hcmsStartScreen" onload="focusform(); is_mobilebrowser(); is_iOS(); html5support(); setwallpaper();">

  <?php if (!empty ($wallpaper) && is_video ($wallpaper)) { ?>
  <video playsinline autoplay muted loop poster="<?php echo getthemelocation($themename); ?>/img/backgrd_start.png" id="videoScreen">
    <source src="<?php echo $wallpaper; ?>" type="video/mp4">
  </video>
  <?php } ?>
  
  <div class="hcmsStartBar">
    <div style="position:absolute; top:15px; left:15px; float:left; text-align:left;"><img src="<?php echo getthemelocation($themename); ?>img/logo.png" style="border:0; height:48px;" alt="hypercms.com" /></div>
    <div style="position:absolute; top:15px; right:15px; text-align:right;"><?php echo $mgmt_config['version']; ?></div>
  </div>
  
  <div class="hcmsLogonScreen">
    <?php
    echo "
    <form name=\"login\" method=\"post\" onsubmit=\"return ".((empty ($mgmt_config['multifactorauth']) || $require == "password") ? "submitlogin()" : "requestpassword()").";\" style=\"opacity:0.9;\" action=\"\">
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

</body>
</html>