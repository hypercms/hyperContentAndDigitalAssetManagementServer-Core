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
$lang = getrequest ("lang");
$token = getrequest ("token");
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

// detect browser and set theme
if (is_mobilebrowser () || $is_mobile == "yes")
{
  $themename = "mobile";
}
else $themename = "";

// access link since version 5.6.2
$ignore_password = false;

if ($al != "")
{
  $result_al = rdbms_getaccessinfo ($al);
  
  if (is_array ($result_al))
  {
    $hcms_user = $result_al['user'];
    $hcms_pass = "";
    $hcms_objref = $result_al['object_id'];
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
}

// deprecated since version 5.6.1 but still supported:
// extract object ID and token code (before version 5.5.13)
if ($hcms_id_token != "")
{
  $hcms_id_string = hcms_decrypt ($hcms_id_token);
  if ($hcms_id_string != "" && strpos ($hcms_id_string, ":") > 0) list ($hcms_objref, $hcms_objcode) = explode (":", $hcms_id_string);
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
  
  // define frameset for mobile access (also via mail link)
  if ($is_mobile) $result_frameset = "frameset_mobile.php"; 
  // define frameset for access via mail link
  elseif (is_array ($hcms_linking)) $result_frameset = "frameset_main_linking.php";
  // frameset for standard logon
  else $result_frameset = "frameset_main.php";
  
  // forward to main frameset if check was passed
  header ("Location: ".$result_frameset);
}
// if a user is logged in and a mail-link is used, then kill existing session 
elseif ($user != "" && $hcms_user != "")
{
  killsession ($user);
}

// set default language
if (!isset ($lang) || $lang == false || $lang == "") $lang = $mgmt_lang_shortcut_default;

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
  // standard user logon
  elseif (!empty ($sentuser) && !empty ($sentpasswd) && checktoken ($token, "sys"))
  {
    $login_result = userlogin ($sentuser, $sentpasswd);
  }
  else $login_result = false;
  
  if (is_array ($login_result) && $login_result['auth'])
  {	
    // start session
    session_name ("hyperCMS");
    session_start ();

    // regenerate session id after successful logon
    session_regenerate_id ();

    // register instance in session without loading main config
    registerinstance ($sentinstance, false);

    // register root, global and local pemissions
    if (!empty ($login_result['rootpermission']))
    {
      setsession ('hcms_rootpermission', $login_result['rootpermission']);
    }
    
    if (!empty ($login_result['globalpermission']))
    {
      setsession ('hcms_globalpermission', $login_result['globalpermission']);
    }
    
    if (!empty ($login_result['localpermission']))
    {
      setsession ('hcms_localpermission', $login_result['localpermission']);
    }
      
    // register values for this session
    setsession ('hcms_user', $login_result['user']);
    setsession ('hcms_passwd', md5 ($login_result['passwd']));
    setsession ('hcms_siteaccess', $login_result['siteaccess']);
    setsession ('hcms_pageaccess', $login_result['pageaccess']);
    setsession ('hcms_compaccess', $login_result['compaccess']);
    setsession ('hcms_superadmin', $login_result['superadmin']);
    setsession ('hcms_lang', $login_result['lang']);
    setsession ('hcms_hiddenfolder', $login_result['hiddenfolder']);

    // register download formats in case of an access link
    if (!empty ($hcms_objformats)) setsession ('hcms_downloadformats', $hcms_objformats);
    
    // reset mobile settings by values of client side browser detection (JavaScript)
    if ($is_mobile == "1" || $is_mobile == "yes")
    {
      $login_result['mobile'] = true;
      $login_result['themename'] = "mobile";
    }
    
    // iphone setting
    if ($is_iphone == "1")
    {
      $login_result['iphone'] = true;
    }
    else $login_result['iphone'] = false;
    
    // register temporary view settings
    setsession ('hcms_temp_explorerview', $mgmt_config['explorerview']);
    setsession ('hcms_temp_objectview', $mgmt_config['objectview']);
    setsession ('hcms_temp_sidebar', $mgmt_config['sidebar']);
    // register permanent view settings
    setsession ('hcms_mobile', $login_result['mobile']);
    setsession ('hcms_iphone', $login_result['iphone']);
    // register chat state after logon
    setsession ('hcms_temp_chatstate', $login_result['chatstate']);
    // register theme settings
    setsession ('hcms_themename', $login_result['themename']);
    setsession ('hcms_themelocation', getthemelocation ($login_result['themename']));    
    // register HTML5 file support in session
    setsession ('hcms_html5file', $html5support);    
    // register server feedback
    setsession ('hcms_keyserver', $login_result['keyserver']);
    // register current timestamp in session
    setsession ('hcms_temp_sessiontime', time());
    
    // define frameset for access via mail link
    if (!empty ($login_result['hcms_linking']) && is_array ($login_result['hcms_linking']))
    {
      setsession ('hcms_linking', $login_result['hcms_linking']);

      if ($login_result['mobile']) $result_frameset = "frameset_mobile.php";
      else $result_frameset = "frameset_main_linking.php";
    }
    // frameset for standard logon
    else
    {
      setsession ('hcms_linking', Null);
      
      if ($login_result['mobile']) $result_frameset = "frameset_mobile.php";
      else $result_frameset = "frameset_main.php";
    }

    // write hypercms session file
    $login_result['writesession'] = writesession ($login_result['user'], $login_result['passwd'], $login_result['checksum']);

    // session info could not be saved
    if ($login_result['writesession'] == false)
    {  
      $login_result['message'] = getescapedtext ($hcms_lang['session-information-could-not-be-saved'][$lang]);  
    }
  }

  // user is logged in
  if (isset ($login_result) && isset ($login_result['writesession']) && $login_result['writesession'] == true)
  {
    $show = "<script language=\"JavaScript\">
    <!--
    location='".$mgmt_config['url_path_cms'].$result_frameset."';
    //-->
    </script>
  
    ".$login_result['message']."\n";
  }
  // login or password are false
  elseif (isset ($login_result) && is_array ($login_result) && ($login_result['auth'] == false || $login_result['writesession'] == false))
  {
    $show = str_replace ("%timeout%", $mgmt_config['logon_timeout'], $login_result['message'])."\n";
  }

  // login form
  if ((!isset ($login_result) || $login_result['auth'] != true))
  {    
    $show = "<form name=\"login\" method=\"post\" action=\"\">
        <input type=\"hidden\" name=\"token\" value=\"".$token_new."\" />
        <input type=\"hidden\" name=\"is_mobile\" value=\"0\" />
        <input type=\"hidden\" name=\"is_iphone\" value=\"0\" />
        <input type=\"hidden\" name=\"html5support\" value=\"0\" />
        <table style=\"border:0; padding:0; border-spacing:2; border-collapse:collapse;\">
          <tr>
            <td>&nbsp;</td>
            <td class=\"hcmsTextOrange\"><strong>".$show."</strong></td>
          </tr>\n";
          
    if (!empty ($mgmt_config['instances']) && is_dir ($mgmt_config['instances'])) $show .= "
          <tr id=\"sentinstance_container\">
            <td><b>".getescapedtext ($hcms_lang['instance'][$lang])."</b></td>
            <td>
              <input type=\"text\" id=\"sentinstance\" name=\"sentinstance\" maxlength=\"100\" style=\"width:150px; height:16px;\" />
            </td>
          </tr>\n";
          
    $show .= "
          <tr>
            <td><b>".getescapedtext ($hcms_lang['user'][$lang])."</b></td>
            <td>
              <input type=\"text\" id=\"sentuser\" name=\"sentuser\" maxlength=\"100\" style=\"width:150px; height:16px;\" />
            </td>
          </tr>
          <tr>
            <td><b>".getescapedtext ($hcms_lang['password'][$lang])."</b></td>
            <td>
              <input type=\"password\" id=\"sentpasswd\" name=\"sentpasswd\" maxlength=\"100\" style=\"width:150px; height:16px;\" />
            </td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td style=\"padding:4px 0px 4px 0px;\">
              <input id=\"remember\" type=\"checkbox\" /> ".getescapedtext ($hcms_lang['remember-me'][$lang])."
            </td>
          </tr>
          <tr>
            <td>&nbsp;</td>   
            <td>
              <button class=\"hcmsButtonGreen\" style=\"width:155px; heigth:20px;\" onClick=\"submitlogin()\">Log in</button>
            </td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td class=\"hcmsTextOrange\" style=\"font-size:small; font-weight:normal;\">Popups must be allowed</td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>          
        </table>
      </form>\n";
  }  
}
// client ip is banned
else
{
  $show .= "<p class=\"hcmsTextOrange\">".str_replace ("%timeout%", $mgmt_config['logon_timeout'], $hcms_lang['you-have-been-banned'][$lang])."</p>\n";
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta name="viewport" content="width=380; initial-scale=0.9; maximum-scale=1.0; user-scalable=0;" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<link rel="stylesheet" href="<?php echo getthemelocation($themename); ?>css/main.css" type="text/css">

<!-- Standard icon -->
<link rel="shortcut icon" href="<?php echo getthemelocation(); ?>img/favicon.ico"> 
<!-- 57 x 57 Android and iPhone 3 icon -->
<link rel="apple-touch-icon" media="screen and (resolution: 163dpi)" href="<?php echo getthemelocation($themename); ?>img/mobile_icon57.png" />
<!-- 114 x 114 iPhone 4 icon -->
<link rel="apple-touch-icon" media="screen and (resolution: 326dpi)" href="<?php echo getthemelocation($themename); ?>img/mobile_icon114.png" />
<!-- 57 x 57 Nokia icon -->
<link rel="shortcut icon" href="<?php echo getthemelocation(); ?>img/mobile_icon57.png" />

<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
function focusform()
{
  <?php if (!empty ($mgmt_config['instances'])) { ?>
  // if instance parameter has been provided via GET
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
  <?php } ?>

  // username and password from local storage
  if (localStorage.getItem('username') !== null) document.getElementById('sentuser').value = localStorage.getItem('username'); 
  if (localStorage.getItem('password') !== null) document.getElementById('sentpasswd').value = localStorage.getItem('password');
  
  document.forms['login'].elements['sentuser'].focus();
}

function is_mobilebrowser()
{
  if (eval (document.forms['login']) && hcms_mobileBrowser()) document.forms['login'].elements['is_mobile'].value = '1';
}

function is_iphone()
{
  if (eval (document.forms['login']) && hcms_iPhonePad()) document.forms['login'].elements['is_iphone'].value = '1';
}

function html5support()
{
  if (eval (document.forms['login']) && hcms_html5file()) document.forms['login'].elements['html5support'].value = '1';
}

function submitlogin()
{
  if (document.getElementById('sentinstance')) var instance = document.getElementById('sentinstance').value;
  var username = document.getElementById('sentuser').value;
  var password = document.getElementById('sentpasswd').value;

  if (document.getElementById('remember').checked == true)
  {
    if (document.getElementById('sentinstance')) localStorage.setItem('instance', instance);
    localStorage.setItem('username', username);
    localStorage.setItem('password', password);
  }

  document.forms['login'].submit();
}
//-->
</script>
</head>

<body class="hcmsStartScreen" onLoad="focusform(); is_mobilebrowser(); is_iphone(); html5support();">

<div class="hcmsStartBar">
  <div style="position:absolute; top:10px; left:10px; float:left; text-align:left;"><img src="<?php echo getthemelocation($themename); ?>img/logo.png" alt="hyperCMS" /></div>
  <div style="position:absolute; top:48px; right:10px; text-align:right;"><?php echo $version; ?></div>
</div>

<div class="hcmsLogonScreen">
  <?php echo $show; ?>
</div>

</body>
</html>