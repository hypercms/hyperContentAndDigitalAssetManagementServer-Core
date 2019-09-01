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
$realname = getrequest_esc ("realname");
$language = getrequest_esc ("language");
$timezone = getrequest ("timezone");
$theme = getrequest_esc ("theme", "objectname");
$email = getrequest_esc ("email");
$phone = getrequest_esc ("phone");
$signature = getrequest_esc ("signature");
$validdatefrom = getrequest_esc ("validdatefrom");
$validdateto = getrequest_esc ("validdateto");
$usergroup = getrequest_esc ("usergroup");
$usersite = getrequest_esc ("usersite");
$homeboxes = getrequest ("homeboxes");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (
     ($login_cat == "home" && ($login != $user || !checkrootpermission ('desktopsetting'))) || 
     (!valid_publicationname ($site) && (!checkrootpermission ('user') || !checkrootpermission ('useredit'))) || 
     (valid_publicationname ($site) && (!checkglobalpermission ($site, 'user') || !checkglobalpermission ($site, 'useredit')))
   ) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

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
    if (!is_date ($validdatefrom, "Y-m-d")) $validdatefrom = "*Leave*";
    if (!is_date ($validdateto, "Y-m-d")) $validdateto = "*Leave*";
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
      $add_onload = "setTimeout (function(){ top.location.reload(true); }, 2000);";
    }
    
    // change theme in session if user changed it
    if (!empty ($theme) && $hcms_themename != $theme)
    {
      setsession ('hcms_themename', $theme, true);
      $add_onload = "setTimeout (function(){ top.location.reload(true); }, 2000);";
    }
  }
  
  // set time zone
  if (!empty ($timezone) && $timezone != getsession ("hcms_timezone"))
  {
    setsession ('hcms_timezone', $timezone);
    $add_onload = "setTimeout (function(){ top.location.reload(true); }, 2000);";
  }

  // edit user settings
  $result = edituser ($site, $login, $old_password, $password, $confirm_password, $superadmin, $realname, $language, $timezone, $theme, $email, $phone, $signature, $usergroup, $usersite, $validdatefrom, $validdateto, $user);

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
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>

<link rel="stylesheet" type="text/css" href="javascript/rich_calendar/rich_calendar.css">
<script type="text/javascript" src="javascript/rich_calendar/rich_calendar.js"></script>
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
  else return true;
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
    hcms_showInfo ('savelayer', 0);
    userform.submit();
  }
}

function move (fbox, tbox)
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

function insertOption (newtext, newvalue)
{
  var form = document.forms['userform'];
  var selectbox = form.elements['box_array'];
  newentry = new Option (newtext, newvalue, false, true);
  var i;
  
  if (selectbox.length > 0)
  {  
    var position = -1;

    for (i=0; i<selectbox.length; i++)
    {
      if (selectbox.options[i].selected) position = i;
      // duplicate entry
      if (selectbox.options[i].value == newvalue) return false;
    }
    
    if (position != -1)
    {
      selectbox.options[selectbox.length] = new Option();
    
      for (i=selectbox.length-1; i>position; i--)
      {
        selectbox.options[i].text = selectbox.options[i-1].text;
        selectbox.options[i].value = selectbox.options[i-1].value;
      }
      
      selectbox.options[position+1] = newentry;
    }
    else selectbox.options[selectbox.length] = newentry;
  }
  else selectbox.options[selectbox.length] = newentry;
}

function moveSelected (select, down)
{
  if (select.selectedIndex != -1)
  {
    if (down)
    {
      if (select.selectedIndex != select.options.length - 1)
        var i = select.selectedIndex + 1;
      else
        return;
    }
    else
    {
      if (select.selectedIndex != 0)
        var i = select.selectedIndex - 1;
      else
        return;
    }

    var swapOption = new Object();

    swapOption.text = select.options[select.selectedIndex].text;
    swapOption.value = select.options[select.selectedIndex].value;
    swapOption.selected = select.options[select.selectedIndex].selected;

    for (var property in swapOption) select.options[select.selectedIndex][property] = select.options[i][property];
    for (var property in swapOption) select.options[i][property] = swapOption[property];
  }
}

function deleteSelected (select)
{
  if (select.length > 0)
  {
    for(var i=0; i<select.length; i++)
    {
      if (select.options[i].selected == true) select.remove(i);
    }
  }
}

function selectAllOptions (select)
{
  for (var i=0; i<select.options.length; i++)
  {
    select.options[i].selected = true;
  }
}

function setHomeBoxes ()
{
  var form = document.forms['userform'];

  if (form.elements['box_array'])
  {
    var select = form.elements['box_array'];
    var homeboxes = "|";

    if (select.options.length > 0)
    {
      for(var i=0; i<select.options.length; i++)
      {
        homeboxes = homeboxes + select.options[i].value + "|";
      }
    }

    form.elements['homeboxes'].value = homeboxes;
    return true;
  }
  else return false;
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" onload="<?php echo $add_onload; ?>">

<!-- saving --> 
<div id="savelayer" class="hcmsLoadScreen"></div>

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

  if (!empty ($userrecord[0]))
  {
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
    
    $timezonearray = getcontent ($userrecord[0], "<timezone>");
    $usertimezone = $timezonearray[0];
    
    $themearray = getcontent ($userrecord[0], "<theme>");
    $usertheme = $themearray[0];

    $validdatefromarray = getcontent ($userrecord[0], "<validdatefrom>");
    $uservaliddatefrom = $validdatefromarray[0];

    $validdatetoarray = getcontent ($userrecord[0], "<validdateto>");
    $uservaliddateto = $validdatetoarray[0];
    
    $signaturearray = getcontent ($userrecord[0], "<signature>");
    $signature = $signaturearray[0];
    
    if (valid_publicationname ($site)) 
    {
      $memberofarray = selectcontent ($userrecord[0], "<memberof>", "<publication>", "$site");
      
      $usergrouparray = getcontent ($memberofarray[0], "<usergroup>");
      
      if ($usergrouparray != false) $usergroup = $usergrouparray[0]; 
      else $usergroup = "";
    }

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
  
  <table class="hcmsTableStandard">
    <?php if ($login_cat == "home" || $login == $user) { ?>
    <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['old-password'][$lang]); ?> </td>
      <td>
        <input type="password" name="old_password" style="width:210px;" />
      </td>
    </tr>
    <?php } ?> 
    <tr>
      <td style="width:250px; white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['change-password'][$lang]); ?> </td>
      <td style="width:250px;">
        <input type="password" name="password" style="width:210px;" maxlength="100" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['confirm-password'][$lang]); ?> </td>
      <td>
        <input type="password" name="confirm_password" style="width:210px;" maxlength="100" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['hash-for-openapi'][$lang]); ?> </td>
      <td>
        <input type="text" style="width:210px;" value="<?php echo $hashcode; ?>" readonly="readonly" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['name'][$lang]); ?> </td>
      <td>
        <input type="text" name="realname" style="width:210px;" value="<?php echo $realname; ?>" maxlength="200" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['e-mail'][$lang]); ?> </td>
      <td>
        <input type="text" name="email" style="width:210px;" value="<?php echo $email; ?>" maxlength="200" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['phone'][$lang]); ?> </td>
      <td>
        <input type="text" name="phone" style="width:210px;" value="<?php echo $phone; ?>" maxlength="20" />
      </td>
    </tr>
    <tr>
      <td valign="top" style="vertical-align:top; white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['signature'][$lang]); ?> </td>
      <td style="vertical-align:top;">
        <textarea name="signature" wrap="VIRTUAL" style="width:210px; height:100px;"><?php echo $signature; ?></textarea>
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['language'][$lang]); ?> </td>
      <td>
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
    <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['timezone'][$lang]); ?> </td>
      <td>
        <select name="timezone" style="width:210px;">
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
      </td>
    </tr>

    <?php
    // check if any publication defines a theme
    foreach ($siteaccess as $entry)
    {
      if (!empty ($mgmt_config[$entry]['theme']))
      {
        $config_theme = $mgmt_config[$entry]['theme'];
        break;
      }
    }

    if ((!valid_publicationname ($site) && empty ($mgmt_config['theme']) && empty ($config_theme)) || (valid_publicationname ($site) && empty ($mgmt_config['theme']) && empty ($mgmt_config[$site]['theme']))) {
    ?>
    <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['theme'][$lang]); ?> </td>
      <td>
        <select name="theme" style="width:210px;">
        <?php
        // get themes of user
        if ($superadmin == "1") $theme_array = getthemes ($siteaccess);
        elseif (!empty ($usersitearray)) $theme_array = getthemes ($usersitearray);
        else $theme_array = false;

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
      </td>
    </tr>
    <?php } ?>

    <?php if ($login_cat != "home" && (!valid_publicationname ($site) && checkrootpermission ('user') && checkrootpermission ('useredit')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user') && checkglobalpermission ($site, "useredit"))) { ?>
    <!-- valid dates -->
    <tr>
      <td style="white-space:nowrap; vertical-align:top;"><?php echo getescapedtext ($hcms_lang['period-of-validity'][$lang]); ?> </td>
      <td>
        <table class="hcmsTableStandard">
          <tr>
            <td><?php echo getescapedtext ($hcms_lang['start'][$lang]); ?> </td>
            <td stlye="white-space:nowrap;"><input type="text" name="validdatefrom" id="validdatefrom" readonly="readonly" style="width:134px;" value="<?php echo showdate ($uservaliddatefrom, "Y-m-d", "Y-m-d"); ?>" /><img name="datepicker1" src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'validdatefrom', '%Y-%m-%d', false);" class="hcmsButtonTiny hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" /></td>
          </tr>
          <tr>
            <td><?php echo getescapedtext ($hcms_lang['end'][$lang]); ?> </td>
            <td stlye="white-space:nowrap;"><input type="text" name="validdateto" id="validdateto" readonly="readonly" style="width:134px;" value="<?php echo showdate ($uservaliddateto, "Y-m-d", "Y-m-d"); ?>" /><img name="datepicker2" src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'validdateto', '%Y-%m-%d', false);" class="hcmsButtonTiny hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" /></td>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <?php } ?>

    <?php
    // user group membership
    if ($login_cat != "home" && valid_publicationname ($site) && checkglobalpermission ($site, 'user') && checkglobalpermission ($site, "useredit"))
    {    
      echo "
    <tr>
      <td colspan=\"2\">
        <table class=\"hcmsTableNarrow\" style=\"margin-top:10px;\">
          <tr>
            <td>
            <span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['groups'][$lang])."</span><br />
              <select multiple name=\"list1\" style=\"width:210px; height:100px;\" size=\"10\">";

              $groupdata = loadfile ($mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");
      
              if ($groupdata != false)
              {
                $grouprecord_array = getcontent ($groupdata, "<groupname>");
    
                natcasesort ($grouprecord_array);
                reset ($grouprecord_array);
                
                $list2_array = array();
                          
                foreach ($grouprecord_array as $grouprecord)
                {
                  if ($grouprecord != "")
                  {
                    // unselected groups      
                    if (substr_count ($usergroup, "|".$grouprecord."|") == 0)
                    {
                      echo "
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
              }

              echo "
              </select>
            </td>
            <td style=\"text-align:center; vertical-align:middle;\">
              <br />
              <input type=\"button\" class=\"hcmsButtonBlue\" style=\"width:40px; margin:5px; display:block;\" onClick=\"move(this.form.elements['list2'], this.form.elements['list1'])\" value=\"&lt;&lt;\" />
              <input type=\"button\" class=\"hcmsButtonBlue\" style=\"width:40px; margin:5px; display:block;\" onClick=\"move(this.form.elements['list1'], this.form.elements['list2'])\" value=\"&gt;&gt;\" />
            </td>
            <td>
              ".getescapedtext ($hcms_lang['assigned-to-group'][$lang])."<br />
              <select multiple name=\"list2\" style=\"width:210px; height:100px;\" size=\"10\">";

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
    </tr>";
    }
    // publication membership
    elseif ($login_cat != "home" && !valid_publicationname ($site) && checkrootpermission ('user') && checkrootpermission ('useredit'))
    {    
      echo "
    <tr>
      <td colspan=\"2\">
        <table class=\"hcmsTableNarrow\" style=\"margin-top:10px;\">
          <tr>
            <td>
              <span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['publications'][$lang])."</span><br />
              <select multiple name=\"list1\" style=\"width:210px; height:100px;\" size=\"10\">";

              $inherit_db = inherit_db_read ($user);
              
              $list1_array = array();
              $list2_array = array();
      
              if ($inherit_db != false && is_array ($inherit_db))
              {                        
                foreach ($inherit_db as $inherit_db_record)
                {
                  // check if user has siteaccess
                  if ($inherit_db_record['parent'] != "" && is_array ($siteaccess) && in_array ($inherit_db_record['parent'], $siteaccess))
                  {
                    // unselected sites
                    if (substr_count ($usersite, "|".$inherit_db_record['parent']."|") == 0)
                    {
                      $list1_array[] = "
                      <option value=\"".$inherit_db_record['parent']."\">".$inherit_db_record['parent']."</option>";
                    }
                    // selected sites
                    else
                    {
                      $list2_array[] = "
                      <option value=\"".$inherit_db_record['parent']."\">".$inherit_db_record['parent']."</option>";
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
              <input type=\"button\" class=\"hcmsButtonBlue\" style=\"width:40px; margin:5px; display:block;\" onClick=\"move(this.form.elements['list2'], this.form.elements['list1'])\" value=\"&lt;&lt;\" />
              <input type=\"button\" class=\"hcmsButtonBlue\" style=\"width:40px; margin:5px; display:block;\" onClick=\"move(this.form.elements['list1'], this.form.elements['list2'])\" value=\"&gt;&gt;\" />
            </td>
            <td>
              ".getescapedtext ($hcms_lang['assigned-to-publication'][$lang])."<br />
              <select multiple name=\"list2\" style=\"width:210px; height:100px;\" size=\"10\">";

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
      </td>
    </tr>";
    }    
    ?>

    <?php if ($login_cat != "home" && (!valid_publicationname ($site) && checkrootpermission ('user') && checkrootpermission ('useredit')) || (valid_publicationname ($site) && checkglobalpermission ($site, 'user') && checkglobalpermission ($site, "useredit"))) { ?>
    <!-- Home boxes -->
    <tr>
      <td colspan="2">
        <table class="hcmsTableNarrow" style="margin-top:10px;">
          <tr>
            <td style="width:260px; vertical-align:top; text-align:left;">
              <span class="hcmsHeadline" style="padding:3px 0px 3px 0px; display:block;"><?php echo getescapedtext ($hcms_lang['home'][$lang]." ".$hcms_lang['objects'][$lang]); ?></span>
              <?php
              // get home boxes for selection
              if ($login_cat == "home" && $login == $user) $homebox_array = gethomeboxes ($siteaccess);
              elseif (!empty ($usersitearray)) $homebox_array = gethomeboxes ($usersitearray);
              else $homebox_array = false;

              if (is_array ($homebox_array) && sizeof ($homebox_array) > 0)
              {
                foreach ($homebox_array as $homebox_key => $homebox_name)
                {
                  echo "
                  <div onclick=\"insertOption('".$homebox_name."', '".$homebox_key."');\" style=\"display:block; cursor:pointer;\" title=\"".$homebox_name."\"><img src=\"".getthemelocation()."img/log_info.png\" class=\"hcmsIconList\" />&nbsp;".showshorttext($homebox_name, 30)."&nbsp;</div>";
                }
              }
              ?>
            </td>
            <td style="vertical-align:top; text-align:left;">
              <span style="padding:3px 0px 3px 0px; display:block;"><?php echo getescapedtext ($hcms_lang['selected-object'][$lang]); ?></span>
              <select id="box_array" name="box_array" style="width:210px; height:240px;" size="14">
                <?php
                // get home boxes of user
                $userbox_array = getuserboxes ($login);

                if (is_array ($userbox_array) && sizeof ($userbox_array) > 0)
                {
                  foreach ($userbox_array as $userbox_key => $userbox_name)
                  {
                    echo "
                    <option value=\"".$userbox_key."\">".showshorttext($userbox_name, 40)."</option>";
                  }
                }
                ?>
              </select>
            </td>
            <td style="width:32px; text-align:left; vertical-align:middle;">
              <img onClick="moveSelected(document.forms['userform'].elements['box_array'], false)" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonUp" src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['move-up'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['move-up'][$lang]); ?>" /><br />                     
              <img onClick="deleteSelected(document.forms['userform'].elements['box_array'])" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonDelete" src="<?php echo getthemelocation(); ?>img/button_delete.png" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" /><br />            
              <img onClick="moveSelected(document.forms['userform'].elements['box_array'], true)" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonDown" src="<?php echo getthemelocation(); ?>img/button_movedown.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['move-down'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['move-down'][$lang]); ?>" /><br />
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <?php } ?>

    <?php if ($login_cat != "home" && !valid_publicationname ($site) && checkadminpermission () && $login != $user) { ?>
    <!-- Super admin -->
    <tr>
      <td style="white-space:nowrap; padding-top:10px;"><span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['administration'][$lang]); ?></span> </td>
      <td style="padding:10px 0px 0px 8px;">
        <label><input type="checkbox" name="superadmin" value="1" <?php if ($superadmin == "1") echo "checked=\"checked\""; ?>/> <?php echo getescapedtext ($hcms_lang['super-administrator'][$lang]); ?></label>
      </td>
    </tr>
    <?php } ?>

    <!-- Save -->
    <tr>
      <td style="white-space:nowrap; padding-top:10px;"><?php echo getescapedtext ($hcms_lang['save-settings'][$lang]); ?> </td>
      <td style="padding-top:10px;">
        <img name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" onclick="checkForm();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" />
      </td>
    </tr>
  </table>
</form>

</div>

</body>
</html>
