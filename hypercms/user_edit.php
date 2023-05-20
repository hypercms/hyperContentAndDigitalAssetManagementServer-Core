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
$site = getrequest_esc ("site"); // site can be *Null* which is not a valid name!
$login_cat = getrequest_esc ("login_cat");
$login = getrequest_esc ("login", "objectname");
$old_password = getrequest ("old_password");
$password = getrequest ("password");
$confirm_password = getrequest ("confirm_password");
$superadmin = getrequest_esc ("superadmin", "numeric", 0);
$nologon = getrequest_esc ("nologon", "numeric", 0);
$realname = getrequest_esc ("realname");
$language = getrequest_esc ("language");
$timezone = getrequest ("timezone");
$theme = getrequest_esc ("theme", "locationname");
$email = getrequest_esc ("email");
$phone = getrequest_esc ("phone");
$signature = getrequest_esc ("signature");
$validdatefrom = getrequest_esc ("validdatefrom");
$validdateto = getrequest_esc ("validdateto");
$usergroup = getrequest_esc ("usergroup");
$usersite = getrequest_esc ("usersite");
$homeboxes = getrequest ("homeboxes");
$token = getrequest ("token");

// define field width
if ($is_mobile) $width_field = 300; 
else $width_field = 480;

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (
     ($login_cat == "home" && ($login != $user || !checkrootpermission ('desktopsetting'))) || 
     ($login_cat != "home" && !valid_publicationname ($site) && (!checkrootpermission ('user') || !checkrootpermission ('useredit'))) || 
     (valid_publicationname ($site) && (!checkglobalpermission ($site, 'user') || !checkglobalpermission ($site, 'useredit')))
   ) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// initialize
$show = "";
$add_onload = "";

// save user
if ($action == "user_save" && (!valid_publicationname ($site) || checkpublicationpermission ($site)) && checktoken ($token, $user))
{
  // set super admin (only in main user administration)
  if (checkadminpermission () && $login != $user)
  {
    if ($superadmin != "1") $superadmin = "0";
  }
  else $superadmin = "*Leave*";

  // set design theme (only if no default theme has been defined in the main or publication config)
  if ((!valid_publicationname ($site) && empty ($mgmt_config['theme']) && empty ($config_theme)) || (valid_publicationname ($site) && empty ($mgmt_config['theme']) && empty ($mgmt_config[$site]['theme'])))
  {
    if (!valid_locationname ($theme)) $theme = "*Leave*";
  }
  else $theme = "*Leave*";

  // set valid dates (only if useredit permission)
  if ($login_cat != "home" && (!valid_publicationname ($site) && checkrootpermission ('user') && checkrootpermission ('useredit')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user') && checkglobalpermission ($site, "useredit")))
  {
    if (!is_date ($validdatefrom, "Y-m-d") && trim ($validdatefrom) != "") $validdatefrom = "*Leave*";
    if (!is_date ($validdateto, "Y-m-d") && trim ($validdateto) != "") $validdateto = "*Leave*";
  }
  else
  {
    $validdatefrom = "*Leave*";
    $validdateto = "*Leave*";
  }

  // set group membership
  if ($login_cat != "home" && valid_publicationname ($site) && checkglobalpermission ($site, 'user') && checkglobalpermission ($site, "useredit"))
  {
    if (empty ($usergroup)) $usergroup = "*Leave*";
  }
  else $usergroup = "*Leave*";

  // set publication membership
  if ($login_cat != "home" && !valid_publicationname ($site) && checkrootpermission ('user') && checkrootpermission ('useredit'))
  {
    if (empty ($usersite)) $usersite = "*Leave*";
  }
  else $usersite = "*Leave*";
  
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
      $add_onload = "setTimeout (function(){ top.location.reload(true); }, 1000);";
    }
    
    // change theme in session if user changed it
    if (!empty ($theme) && $theme != $hcms_themename)
    {
      $themeinvertcolors = false;

      // get design theme and primary color if a portal theme is used
      if (strpos ($theme, "/") > 0)
      {
        // load portal template if not loaded
        list ($portal_site, $portal_theme) = explode ("/", $theme);

        if (valid_objectname ($portal_theme))
        {
          $portal_template = $portal_theme.".portal.tpl";
          $portal_template = loadtemplate ($portal_site, $portal_template);
        }

        // get design theme and primary color
        if (!empty ($portal_template['content']))
        {
          $temp_portaltheme = getcontent ($portal_template['content'], "<designtheme>");
          $temp_portalcolor = getcontent ($portal_template['content'], "<primarycolor>");

          if (!empty ($temp_portaltheme[0]) && !empty ($temp_portalcolor[0]))
          {
            list ($portalsite, $portaltheme) = explode ("/", $temp_portaltheme[0]);
            $brightness = getbrightness ($temp_portalcolor[0]);

            if ($portaltheme == "day" && $brightness < 130) $themeinvertcolors = "night";
            elseif ($portaltheme == "night" && $brightness >= 130) $themeinvertcolors = "day";
          }
        }
      }

      setsession ('hcms_themename', $theme, true);
      setsession ('hcms_themeinvertcolors', $themeinvertcolors, true);
      $add_onload = "setTimeout (function(){ top.location.reload(true); }, 1000);";
    }
  }

  // set time zone
  if (!empty ($timezone) && $timezone != getsession ("hcms_timezone"))
  {
    setsession ('hcms_timezone', $timezone);
    $add_onload = "setTimeout (function(){ top.location.reload(true); }, 1000);";
  }

  // edit user settings
  $result = edituser ($site, $login, $old_password, $password, $confirm_password, $superadmin, $nologon, $realname, $language, $timezone, $theme, $email, $phone, $signature, $usergroup, $usersite, $validdatefrom, $validdateto, $user);

  // set home boxes of user
  if (!empty ($homeboxes)) setuserboxes ($homeboxes, $login);

  $show = $result['message'];

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
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>

<link rel="stylesheet" type="text/css" href="javascript/rich_calendar/rich_calendar.css">
<script type="text/javascript" src="javascript/rich_calendar/rich_calendar.min.js"></script>
<script type="text/javascript" src="javascript/rich_calendar/rc_lang_en.js"></script>
<script type="text/javascript" src="javascript/rich_calendar/rc_lang_de.js"></script>
<script type="text/javascript" src="javascript/rich_calendar/domready.js"></script>
<script type="text/javascript">

var cal_obj = null;
var cal_format = '%Y-%m-%d';
var cal_field = null;

// show calendar
function show_cal (el, field_id, format)
{
  if (cal_obj) return;
  
  cal_field = field_id;
  cal_format = format;
  var datefield = document.getElementById(field_id);

	cal_obj = new RichCalendar();
	cal_obj.start_week_day = 1;
	cal_obj.show_time = false;
	cal_obj.language = '<?php echo getcalendarlang ($lang); ?>';
	cal_obj.user_onchange_handler = cal_on_change;
  cal_obj.user_onclose_handler = cal_on_close;
	cal_obj.user_onautoclose_handler = cal_on_autoclose;
	cal_obj.parse_date(datefield.value, cal_format);
	cal_obj.show_at_element(datefield, "adj_left-bottom");
}

// user defined onchange handler
function cal_on_change (cal, object_code)
{
	if (object_code == 'day')
	{
		document.getElementById(cal_field).value = cal.get_formatted_date(cal_format);
		cal.hide();
		cal_obj = null;
	}
}

// user defined onclose handler (used in pop-up mode - when auto_close is true)
function cal_on_close(cal)
{
	cal.hide();
	cal_obj = null;
}

// user defined onautoclose handler
function cal_on_autoclose(cal)
{
	cal_obj = null;
}

function selectAll ()
{
  var assigned = "|";
  var form = document.forms['userform'];
  var select = form.elements['list2'];

  if (select)
  {
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

    if (form.elements['site'].value != "*Null*" && form.elements['site'].value != "*no_memberof*")
    {
      form.elements['usergroup'].value = assigned;
    }
    else if (form.elements['site'].value == "*Null*" || form.elements['site'].value == "*no_memberof*")  
    {
      form.elements['usersite'].value = assigned;  
    }

    return true;
  }
  else return true;
}

function checkForm_chars (text, exclude_chars)
{
  var exclude_chars = exclude_chars.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");  
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
  else return true;
}

function editGroup ()
{
  var select = document.forms['userform'].elements['list2'];

  if (select.value != "") 
  {
    hcms_openWindow('group_edit_form.php?site=<?php echo url_encode($site); ?>&group_name=' + select.value, 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes', <?php echo windowwidth("object"); ?>, <?php echo windowheight("object"); ?>);
  }
  else
  {
    alert ("<?php echo getescapedtext ($hcms_lang['please-select-an-option'][$lang]); ?>\n ");
  }
}

function setHomeBoxes ()
{
  var form = document.forms['userform'];

  if (form.elements['list4'])
  {
    var select = form.elements['list4'];
    var homeboxes = "|";

    if (select.options.length > 0)
    {
      for (var i=0; i<select.options.length; i++)
      {
        homeboxes = homeboxes + select.options[i].value + "|";
      }
    }

    form.elements['homeboxes'].value = homeboxes;
    return true;
  }
  else return false;
}

function saveUser ()
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
    if (!checkForm_chars (userform.elements['password'].value, "-_#+*[]%$ !?@"))
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
     
    if (!checkForm_chars (userform.elements['confirm_password'].value, "-_#+*[]%$ !?@"))
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
  
  if (userform.elements['list2']) selectall = selectAll (userform.elements['list2']);

  setHomeBoxes();
  
  if (selectall == true)
  {
    hcms_showFormLayer ('savelayer', 0);
    userform.submit();
  }
}

function hcms_saveEvent ()
{
  saveUser();
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" onload="<?php echo $add_onload; ?>">

<!-- saving --> 
<div id="savelayer" class="hcmsLoadScreen"></div>

<?php
echo showmessage ($show, 460, 70, $lang, "position:fixed; left:10px; top:10px;");
?>

<?php
// check if login is an attribute of a sent string
if (strpos ($login, ".php") > 0)
{
  // extract login
  $login = getattribute ($login, "login");
}

if (!empty ($login))
{
  $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");

  $userrecord = selectcontent ($userdata, "<user>", "<login>", $login);

  if (!empty ($userrecord[0]))
  {
    $superadminarray = getcontent ($userrecord[0], "<admin>");
    if (!empty ($superadminarray[0])) $superadmin = $superadminarray[0];
    else $superadmin = 0;

    $nologonarray = getcontent ($userrecord[0], "<nologon>");
    if (!empty ($nologonarray[0])) $nologon = $nologonarray[0];
    else $nologon = 0;

    $phonearray = getcontent ($userrecord[0], "<phone>");
    if (!empty ($phonearray[0])) $phone = $phonearray[0];
    else $phone = "";
    
    $emailarray = getcontent ($userrecord[0], "<email>");
    if (!empty ($emailarray[0])) $email = $emailarray[0];
    else $email = "";
    
    $realnamearray = getcontent ($userrecord[0], "<realname>");
    if (!empty ($realnamearray[0])) $realname = $realnamearray[0];
    else $realname = "";
    
    $hashcodearray = getcontent ($userrecord[0], "<hashcode>");
    if (!empty ($hashcodearray[0])) $hashcode = $hashcodearray[0];
    else $hashcode = "";
    
    $languagearray = getcontent ($userrecord[0], "<language>");
    if (!empty ($languagearray[0])) $userlanguage = $languagearray[0];
    else $userlanguage = "";
    
    $timezonearray = getcontent ($userrecord[0], "<timezone>");
    if (!empty ($timezonearray[0])) $usertimezone = $timezonearray[0];
    else $usertimezone = "";
    
    $themearray = getcontent ($userrecord[0], "<theme>");
    if (!empty ($themearray[0])) $usertheme = $themearray[0];
    else $usertheme = "standard";

    $validdatefromarray = getcontent ($userrecord[0], "<validdatefrom>");
    if (!empty ($validdatefromarray[0])) $uservaliddatefrom = $validdatefromarray[0];
    else $uservaliddatefrom = "";

    $validdatetoarray = getcontent ($userrecord[0], "<validdateto>");
    if (!empty ($validdatetoarray[0])) $uservaliddateto = $validdatetoarray[0];
    else $uservaliddateto = "";
    
    $signaturearray = getcontent ($userrecord[0], "<signature>");
    if (!empty ($signaturearray[0])) $signature = $signaturearray[0];
    else $signature = "";
    
    if (valid_publicationname ($site)) 
    {
      $memberofarray = selectcontent ($userrecord[0], "<memberof>", "<publication>", $site);
      
      $usergrouparray = getcontent ($memberofarray[0], "<usergroup>");
      
      if ($usergrouparray != false) $usergroup = $usergrouparray[0]; 
      else $usergroup = "";
    }

    $usersitearray = getcontent ($userrecord[0], "<publication>");
      
    if (is_array ($usersitearray)) $usersite = "|".implode ("|", $usersitearray)."|";    
    else $usersite = "";
  }
}
?>

<!-- top bar -->
<?php echo showtopbar ($hcms_lang['settings-for-user'][$lang].": ".$login, $lang); ?>

<div class="hcmsWorkplaceFrame">
  <form name="userform" action="" method="post">
    <input type="hidden" name="action" value="user_save" />
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <?php if ($login_cat == "home") echo "<input type=\"hidden\" name=\"login_cat\" value=\"".$login_cat."\" />\n"; ?>
    <input type="hidden" name="group" value="<?php echo $usergroup; ?>" />
    <input type="hidden" name="login" value="<?php echo $login; ?>" />
    <input type="hidden" name="homeboxes" value="" />
    <?php 
    if (valid_publicationname ($site) && $login_cat == "") echo "<input type=\"hidden\" name=\"usergroup\" value=\"".$usergroup."\" />\n";
    elseif ($login_cat == "") echo "<input type=\"hidden\" name=\"usersite\" value=\"".$usersite."\" />\n";
    ?>
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
    <?php if ($login_cat == "home" || $login == $user) { ?>
      <div class="hcmsFormRowContent"><?php echo getescapedtext ($hcms_lang['old-password'][$lang]); ?> </div>
      <div class="hcmsFormRowContent">
        <input type="password" name="old_password" style="width:<?php echo $width_field; ?>px;" />
      </div>
    <?php } ?> 
      <div class="hcmsFormRowContent"><?php echo getescapedtext ($hcms_lang['change-password'][$lang]); ?> </div>
      <div class="hcmsFormRowContent">
        <input type="password" name="password" style="width:<?php echo $width_field; ?>px;" maxlength="100" />
      </div>
      <div class="hcmsFormRowContent"><?php echo getescapedtext ($hcms_lang['confirm-password'][$lang]); ?> </div>
      <div class="hcmsFormRowContent">
        <input type="password" name="confirm_password" style="width:<?php echo $width_field; ?>px;" maxlength="100" />
      </div>
    <?php if ($login_cat != "home" && $login != $user) { ?>
      <div class="hcmsFormRowContent"><?php echo getescapedtext ($hcms_lang['hash-for-openapi'][$lang]); ?> </div>
      <div class="hcmsFormRowContent">
        <input type="text" style="width:<?php echo $width_field; ?>px;" value="<?php echo $hashcode; ?>" readonly="readonly" />
      </div>
    <?php } ?>
      <div class="hcmsFormRowContent"><?php echo getescapedtext ($hcms_lang['name'][$lang]); ?> </div>
      <div class="hcmsFormRowContent">
        <input type="text" name="realname" style="width:<?php echo $width_field; ?>px;" value="<?php echo $realname; ?>" maxlength="200" />
      </div>
      <div class="hcmsFormRowContent"><?php echo getescapedtext ($hcms_lang['e-mail'][$lang]); ?> </div>
      <div class="hcmsFormRowContent">
        <input type="text" name="email" style="width:<?php echo $width_field; ?>px;" value="<?php echo $email; ?>" maxlength="200" />
      </div>
      <div class="hcmsFormRowContent"><?php echo getescapedtext ($hcms_lang['phone'][$lang]); ?> </div>
      <div class="hcmsFormRowContent">
        <input type="text" name="phone" style="width:<?php echo $width_field; ?>px;" value="<?php echo $phone; ?>" maxlength="20" />
      </div>
      <div class="hcmsFormRowContent"><?php echo getescapedtext ($hcms_lang['signature'][$lang]); ?> </div>
      <div class="hcmsFormRowContent">
        <textarea name="signature" wrap="VIRTUAL" style="width:<?php echo $width_field; ?>px; height:80px;"><?php echo $signature; ?></textarea>
      </div>
      <div class="hcmsFormRowContent"><?php echo getescapedtext ($hcms_lang['language'][$lang]); ?> </div>
      <div class="hcmsFormRowContent">
        <select name="language" style="width:<?php echo $width_field; ?>px;">
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
      </div>
      <div class="hcmsFormRowContent"><?php echo getescapedtext ($hcms_lang['timezone'][$lang]); ?> </div>
      <div class="hcmsFormRowContent">
        <select name="timezone" style="width:<?php echo $width_field; ?>px;">
          <option value=""><?php echo getescapedtext ($hcms_lang['standard'][$lang]); ?></option>
        <?php
        $timezone_array = timezone_identifiers_list();

        if (is_array ($timezone_array) && sizeof ($timezone_array) > 0)
        {
          $timezone_array = array_unique ($timezone_array);
          natcasesort ($timezone_array);
          
          foreach ($timezone_array as $tz)
          {
            if ($usertimezone == $tz) $selected = "selected=\"selected\"";
            else $selected = "";
            
            echo "
            <option value=\"".$tz."\" ".$selected.">".$tz."</option>";
          }
        }
        ?>
        </select>
      </div>

    <?php
    // check if any publication defines a theme
    foreach ($siteaccess as $entry => $displayname)
    {
      if (!empty ($mgmt_config[$entry]['theme']))
      {
        $config_theme = $mgmt_config[$entry]['theme'];
        break;
      }
    }

    if ((!valid_publicationname ($site) && empty ($mgmt_config['theme']) && empty ($config_theme)) || (valid_publicationname ($site) && empty ($mgmt_config['theme']) && empty ($mgmt_config[$site]['theme']))) {
    ?>
      <div class="hcmsFormRowContent"><?php echo getescapedtext ($hcms_lang['theme'][$lang]); ?> </div>
      <div class="hcmsFormRowContent">
        <select name="theme" style="width:<?php echo $width_field; ?>px;">
        <?php
        // get themes of user
        if ($superadmin == "1") $theme_array = getthemes ($siteaccess);
        elseif (!empty ($usersitearray)) $theme_array = getthemes ($usersitearray);
        else $theme_array = getthemes ();

        if (is_array ($theme_array) && sizeof ($theme_array) > 0)
        {
          foreach ($theme_array as $theme_key => $theme_value)
          {
            echo "
            <option value=\"".$theme_key."\" ".($usertheme == $theme_key ? "selected=\"selected\"" : "").">".$theme_value."</option>";
          }
        }
        ?>
        </select>
      </div>
    <?php } ?>

    <?php if ($login_cat != "home" && (!valid_publicationname ($site) && checkrootpermission ('user') && checkrootpermission ('useredit')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user') && checkglobalpermission ($site, "useredit"))) { ?>
    <!-- valid dates -->
      <div class="hcmsFormRowContent" style="margin-top:10px;"><span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['period-of-validity'][$lang]); ?></span> </div>
      <div class="hcmsFormRowContent">
        <table class="hcmsTableStandard">
          <tr>
            <td><?php echo getescapedtext ($hcms_lang['start'][$lang]); ?> </td>
            <td stlye="white-space:nowrap;">
              <input type="text" name="validdatefrom" id="validdatefrom" readonly="readonly" style="width:92px;" value="<?php echo showdate ($uservaliddatefrom, "Y-m-d", "Y-m-d"); ?>" /><img name="datepicker1" src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'validdatefrom', '%Y-%m-%d', false);" class="hcmsButtonTiny hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" />
              <img onclick="document.getElementById('validdatefrom').value='';" class="hcmsButtonTiny hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_delete.png" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" />
            </td>
            <td style="width:12px;"> </td>
            <td><?php echo getescapedtext ($hcms_lang['end'][$lang]); ?> </td>
            <td stlye="white-space:nowrap;">
              <input type="text" name="validdateto" id="validdateto" readonly="readonly" style="width:92px;" value="<?php echo showdate ($uservaliddateto, "Y-m-d", "Y-m-d"); ?>" /><img name="datepicker2" src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'validdateto', '%Y-%m-%d', false);" class="hcmsButtonTiny hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" />
              <img onclick="document.getElementById('validdateto').value='';" class="hcmsButtonTiny hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_delete.png" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" />
            </td>
          </tr>
        </table>
      </div>
    <?php } ?>

    <?php
    // user group membership
    if ($login_cat != "home" && valid_publicationname ($site) && checkglobalpermission ($site, 'user') && checkglobalpermission ($site, "useredit"))
    {    
      echo "
      <div class=\"hcmsFormRowContent\">
        <table class=\"hcmsTableNarrow\" style=\"margin-top:10px;\">
          <tr>
            <td>
              <span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['groups'][$lang])."</span><br />
              <select multiple name=\"list1\" style=\"width:".($width_field / 2 - 40)."px; height:100px;\" size=\"10\">";
  
              $groupdata = loadfile ($mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");
      
              if ($groupdata != false)
              {
                $grouprecord_array = getcontent ($groupdata, "<groupname>");
    
                natcasesort ($grouprecord_array);
                reset ($grouprecord_array);
                
                $list1_array = array();
                $list2_array = array();
                          
                foreach ($grouprecord_array as $grouprecord)
                {
                  if ($grouprecord != "")
                  {
                    // unselected groups      
                    if (substr_count ($usergroup, "|".$grouprecord."|") == 0)
                    {
                      $list1_array[] = "
                      <option value=\"".$grouprecord."\">".$grouprecord."</option>";
                    }
                    // selected groups
                    else
                    {
                      $list2_array[] = "
                      <option value=\"".$grouprecord."\">".$grouprecord."</option>";
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
  
              echo "
              </select>
            </td>
            <td style=\"text-align:center; vertical-align:middle;\">
              <br />
              <input type=\"button\" class=\"hcmsButtonBlue\" style=\"width:40px; margin:5px; display:block;\" onClick=\"hcms_moveFromToSelect(this.form.elements['list1'], this.form.elements['list2'], true)\" value=\"&gt;&gt;\" />
              <input type=\"button\" class=\"hcmsButtonBlue\" style=\"width:40px; margin:5px; display:block;\" onClick=\"hcms_moveFromToSelect(this.form.elements['list2'], this.form.elements['list1'], true)\" value=\"&lt;&lt;\" />
            </td>
            <td>
              ".getescapedtext ($hcms_lang['assigned-to-group'][$lang])."<br />
              <select multiple name=\"list2\" style=\"width:".($width_field / 2 - 40)."px; height:100px;\" size=\"10\">";
  
              if (is_array ($list2_array) && sizeof ($list2_array) >= 1)
              {
                foreach ($list2_array as $list2)
                {
                  echo $list2;
                }
              }
  
              echo "</select>
            </td>
            <td style=\"width:32px; text-align:left; vertical-align:middle;\">
              <img onClick=\"editGroup();\" src=\"".getthemelocation()."img/button_edit.png\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" />
            </td>
          </tr>
        </table>
      </div>";
    }
    // publication membership
    elseif ($login_cat != "home" && !valid_publicationname ($site) && checkrootpermission ('user') && checkrootpermission ('useredit'))
    {    
      echo "
      <div class=\"hcmsFormRowContent\">
        <table class=\"hcmsTableNarrow\" style=\"margin-top:10px;\">
          <tr>
            <td>
              <span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['publications'][$lang])."</span><br />
              <select multiple name=\"list1\" style=\"width:".($width_field / 2 - 40)."px; height:100px;\" size=\"10\">";
  
              $inherit_db = inherit_db_read ($user);
              
              $list1_array = array();
              $list2_array = array();
      
              if ($inherit_db != false && is_array ($inherit_db))
              {                        
                foreach ($inherit_db as $inherit_db_record)
                {
                  // check if user has siteaccess
                  if (!empty ($inherit_db_record['parent']) && is_array ($siteaccess) && array_key_exists ($inherit_db_record['parent'], $siteaccess))
                  {
                    $site_name = $inherit_db_record['parent'];

                    // unselected sites
                    if (substr_count ($usersite, "|".$inherit_db_record['parent']."|") == 0)
                    {
                      $list1_array[] = "
                      <option value=\"".$inherit_db_record['parent']."\" title=\"".$site_name."\">".$siteaccess[$site_name]."</option>";
                    }
                    // selected sites
                    else
                    {
                      $list2_array[] = "
                      <option value=\"".$inherit_db_record['parent']."\" title=\"".$site_name."\">".$siteaccess[$site_name]."</option>";
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
  
              echo "
              </select>
            </td>
            <td style=\"width:50px; text-align:center; vertical-align:middle;\">
              <br />
              <input type=\"button\" class=\"hcmsButtonBlue\" style=\"width:40px; margin:5px; display:block;\" onClick=\"hcms_moveFromToSelect(this.form.elements['list1'], this.form.elements['list2'], true)\" value=\"&gt;&gt;\" />
              <input type=\"button\" class=\"hcmsButtonBlue\" style=\"width:40px; margin:5px; display:block;\" onClick=\"hcms_moveFromToSelect(this.form.elements['list2'], this.form.elements['list1'], true)\" value=\"&lt;&lt;\" />
            </td>
            <td>
              ".getescapedtext ($hcms_lang['assigned-to-publication'][$lang])."<br />
              <select multiple name=\"list2\" style=\"width:".($width_field / 2 - 40)."px; height:100px;\" size=\"10\">";
  
              if (is_array ($list2_array) && sizeof ($list2_array) > 0)
              {
                natcasesort ($list2_array);
                reset ($list2_array);
  
                foreach ($list2_array as $list2) echo $list2;
              }
  
              echo "
              </select>
            </td>
          </tr>
        </table>   
      </div>";
    }    
    ?>

    <?php if ($login_cat != "home" && (!valid_publicationname ($site) && checkrootpermission ('user') && checkrootpermission ('useredit')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user') && checkglobalpermission ($site, "useredit"))) { ?>
    <!-- Home boxes -->
    <div class="hcmsFormRowContent">
      <table class="hcmsTableNarrow" style="margin-top:10px;">
        <tr>
          <td>
            <span class="hcmsHeadline" style="padding:3px 0px 3px 0px; display:block;"><?php echo getescapedtext ($hcms_lang['home'][$lang]." ".$hcms_lang['objects'][$lang]); ?></span>
            <select multiple name="list3" style="width:<?php echo ($width_field / 2 - 40); ?>px; height:100px;" size="10">
            <?php
            $list3_array = array();
            $list4_array = array();
  
            // get home boxes for selection
            if ($login_cat == "home" && $login == $user) $homebox_array = gethomeboxes ($siteaccess);
            elseif (!empty ($usersitearray)) $homebox_array = gethomeboxes ($usersitearray);
            else $homebox_array = gethomeboxes ();
  
            // get home boxes of user
            $userbox_array = getuserboxes ($login);
  
            if (is_array ($homebox_array) && sizeof ($homebox_array) > 0)
            {
              foreach ($homebox_array as $homebox_key => $homebox_name)
              {
                // unselected home boxes
                if (!in_array ($homebox_name, $userbox_array))
                {
                  $list3_array[] = "
                  <option value=\"".$homebox_key."\" title=\"".$homebox_name."\">".showshorttext($homebox_name, 30, false)."</option>";
                }
              }
  
              natcasesort ($list3_array);
              reset ($list3_array);
              
              if (is_array ($list3_array) && sizeof ($list3_array) > 0)
              {
                foreach ($list3_array as $list3) echo $list3;
              }
            }
            ?>
            </select>
          </td>
          <td style="width:50px; text-align:center; vertical-align:middle;">
            <br />
            <input type="button" class="hcmsButtonBlue" style="width:40px; margin:5px; display:block;" onClick="hcms_moveFromToSelect(this.form.elements['list3'], this.form.elements['list4'], false)" value="&gt;&gt;" />
            <input type="button" class="hcmsButtonBlue" style="width:40px; margin:5px; display:block;" onClick="hcms_moveFromToSelect(this.form.elements['list4'], this.form.elements['list3'], false)" value="&lt;&lt;" />
          </td>
          <td>
          <span style="padding:3px 0px 3px 0px; display:block;"><?php echo getescapedtext ($hcms_lang['selected-object'][$lang]); ?></span>
            <select multiple name="list4" style="width:<?php echo ($width_field / 2 - 40); ?>px; height:100px;" size="10">
            <?php
            // selected home boxes
            if (is_array ($userbox_array) && sizeof ($userbox_array) > 0)
            {
              foreach ($userbox_array as $userbox_key => $userbox_name) echo "
              <option value=\"".$userbox_key."\" title=\"".$userbox_name."\">".showshorttext($userbox_name, 30, false)."</option>";
            }
            ?>
            </select>
          </td>
          <td style="width:32px; text-align:left; vertical-align:middle;">
            <img onClick="hcms_moveSelected(document.forms['userform'].elements['list4'], false);" src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButtonTiny hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['move-up'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['move-up'][$lang]); ?>" /><br />                             
            <img onClick="hcms_moveSelected(document.forms['userform'].elements['list4'], true);" src="<?php echo getthemelocation(); ?>img/button_movedown.png" class="hcmsButtonTiny hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['move-down'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['move-down'][$lang]); ?>" />
          </td>
        </tr>
      </table>
    </div>
    <?php } ?>

    <?php if ($login_cat != "home" && !valid_publicationname ($site) && checkadminpermission () && $login != $user) { ?>
    <!-- Super admin -->
    <div class="hcmsFormRowContent" style="padding-top:10px;"><span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['administration'][$lang]); ?></span> </div>
    <div class="hcmsFormRowContent">
      <label><input type="checkbox" name="superadmin" value="1" <?php if ($superadmin == "1") echo "checked=\"checked\""; ?>/> <?php echo getescapedtext ($hcms_lang['super-administrator'][$lang]); ?></label>
    </div>
    <?php } ?>

    <?php if ($login_cat != "home" && $login != $user) { ?>
    <!-- no logon -->
    <div class="hcmsFormRowContent" style="padding-top:10px;"><span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['sign-in'][$lang]); ?></span> </div>
    <div class="hcmsFormRowContent">
      <label><input type="checkbox" name="nologon" value="1" <?php if ($nologon == "1") echo "checked=\"checked\""; ?>/> <?php echo getescapedtext ($hcms_lang['user-for-access-links'][$lang]); ?></label>
    </div>
    <?php } ?>

    <!-- Save -->
    <div class="hcmsFormRowContent" style="white-space:nowrap; padding-top:10px;">
      <?php echo getescapedtext ($hcms_lang['save-settings'][$lang]); ?> 
      <img name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" onclick="saveUser();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" />
    </div>
    
  </form>

</div>

<?php includefooter(); ?>

</body>
</html>