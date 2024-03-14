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
// disk key
require ("include/diskkey.inc.php");


// input parameters
$action = getrequest ("action");
$site_name = getrequest_esc ("site_name", "publicationname");
$setting = getrequest ("setting", "array");
$token = getrequest ("token");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('site')) killsession ($user);

// edit permission defines view mode
if (checkrootpermission ('siteedit')) $preview = "no";
else $preview = "yes";

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";

// check group permissions
if (!checkrootpermission ('site') || !checkrootpermission ('siteedit'))
{
  $show = "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang])."</p>\n";
}

// check site permissions and save settings
if (checkrootpermission ('site') && checkrootpermission ('siteedit') && $action == "site_edit" && checktoken ($token, $user))
{
  $result = editpublication ($site_name, $setting, $user);

  $add_onload = $result['add_onload'];
  $show = $result['message'];  
}

// restore link DB if missing
if (!empty ($setting['linkengine']) && link_db_read ($site_name) == false)
{
  link_db_restore ($site_name);
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript">

function switchDAM ()
{
  if (document.getElementById('dam').checked == true)
  {
    document.getElementById('url_path_page').disabled = true;
    document.getElementById('abs_path_page').disabled = true;
    document.getElementById('url_publ_page').disabled = true;
    document.getElementById('abs_publ_page').disabled = true;
    document.getElementById('abs_publ_app').disabled = true;
    document.getElementById('linkengine').disabled = true;
    document.getElementById('crypt_content').disabled = false;
    document.getElementById('upload_pages').disabled = true;
    if (document.getElementById('storage_type1')) document.getElementById('storage_type1').disabled = false;
    if (document.getElementById('storage_type2')) document.getElementById('storage_type2').disabled = false;
    if (document.getElementById('storage_type3')) document.getElementById('storage_type3').disabled = false;
  }
  else
  {
    document.getElementById('url_path_page').disabled = false;
    document.getElementById('abs_path_page').disabled = false;
    document.getElementById('url_publ_page').disabled = false;
    document.getElementById('abs_publ_page').disabled = false;
    document.getElementById('abs_publ_app').disabled = false;
    document.getElementById('linkengine').disabled = false;
    document.getElementById('crypt_content').disabled = true;
    document.getElementById('upload_pages').disabled = false;
    if (document.getElementById('storage_type1')) document.getElementById('storage_type1').disabled = true;
    if (document.getElementById('storage_type2')) document.getElementById('storage_type2').disabled = true;
    if (document.getElementById('storage_type3')) document.getElementById('storage_type3').disabled = true;
  }
}

function switchLDAPsync ()
{
  if (document.getElementById('ldap_sync'))
  {
    if (document.getElementById('ldap_sync').checked == true)
    {
      document.getElementById('ldap_delete_user').disabled = false;
      document.getElementById('ldap_keep_groups').disabled = false;
      document.getElementById('ldap_user_attributes').disabled = false;
      document.getElementById('ldap_sync_groups_mapping').disabled = false;
    }
    else
    {
      document.getElementById('ldap_delete_user').disabled = true;
      document.getElementById('ldap_keep_groups').disabled = true;
      document.getElementById('ldap_user_attributes').disabled = true;
      document.getElementById('ldap_sync_groups_mapping').disabled = true;
    }
  }
}

function moveBoxEntry(box1, box2, max)
{
  var arrbox1 = new Array();
  var arrbox2 = new Array();
  var arrLookup = new Array();
  var i;
  
  for (i = 0; i < box2.options.length; i++)
  {
    arrLookup[box2.options[i].text] = box2.options[i].value;
    arrbox2[i] = box2.options[i].text;
  }

  var fLength = 0;
  var tLength = arrbox2.length;

  for(i = 0; i < box1.options.length; i++)
  {
    arrLookup[box1.options[i].text] = box1.options[i].value;
    if (box1.options[i].selected && box1.options[i].value != '')
    {
      arrbox2[tLength] = box1.options[i].text;
      tLength++;
    }
    else
    {
      arrbox1[fLength] = box1.options[i].text;
      fLength++;
    }
  }
     
  if (arrbox2.length > max)
  {
    alert ('<?php echo $hcms_lang['selected-languages'][$lang]; ?> <= 3');
    return false;
  }

  arrbox1.sort();
  arrbox2.sort();
  box1.length = 0;
  box2.length = 0;
  var c;

  for(c = 0; c < arrbox1.length; c++)
  {
    var no = new Option();
    no.value = arrLookup[arrbox1[c]];
    no.text = arrbox1[c];
    box1[c] = no;
  }

  for(c = 0; c < arrbox2.length; c++)
  {
    if (c < max)
    {
      var no = new Option();
      no.value = arrLookup[arrbox2[c]];
      no.text = arrbox2[c];
      box2[c] = no;
    }
  }
}

function submitLanguage (selectname, targetname)
{
  if (document.forms['siteform'].elements[selectname] && document.forms['siteform'].elements[targetname])
  {
    var content = '' ;
    var select = document.forms['siteform'].elements[selectname];
    var target = document.forms['siteform'].elements[targetname];
  
    if (select.options.length > 0)
    {
      for (var i=0; i<select.options.length; i++)
      {
        content = content + select.options[i].value + ',' ;
      }
    }
    else
    {
      content = '';
    }
  
    target.value = content;  
    return true;
  }
  else return false;
}

function savePublication ()
{
  submitLanguage ('list2', 'setting[translate]');
  submitLanguage ('ocr2', 'setting[ocr]');
  hcms_showFormLayer ('savelayer', 0);
  document.forms['siteform'].submit();
}

function hcms_saveEvent ()
{
  savePublication();
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" onload="<?php if ($preview != "yes") echo "switchDAM(); switchLDAPsync();"; ?> hcms_preloadImages('<?php echo getthemelocation(); ?>img/button_ok_over.png'); <?php if ($add_onload != "") echo $add_onload; ?>">

<!-- saving --> 
<div id="savelayer" class="hcmsLoadScreen"></div>

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<?php
echo showmessage ($show, 500, 70, $lang, "position:fixed; left:10px; top:10px;");
?>

<?php
if (checkrootpermission ('site') && checkrootpermission ('siteedit'))
{
  // check if site name is an attribute of a sent string
  if (strpos ($site_name, ".php") > 0)
  {
    // extract login
    $site_name = getattribute ($site_name, "site_name");
  }
  
  // check publication access permissions of user
  if (!array_key_exists ($site_name, $siteaccess)) $preview = "yes";
  
  // define php script for form action
  if ($preview == "no")
  {
    $formaction = "site_edit_form.php";
  }
  else
  {
    $formaction = "";
  }
  
  // initialize
  $mgmt_config[$site_name] = array();

  // load publication config file of management system
  if (valid_publicationname ($site_name) && is_file ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php"))
  {
    // copy publication configuration file to temp directory in order to avoid PHP file caching
    copy ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php", $mgmt_config['abs_path_temp'].$site_name.".conf.php");
    // load temp file
    require ($mgmt_config['abs_path_temp'].$site_name.".conf.php");
    // delete temp file
    unlink ($mgmt_config['abs_path_temp'].$site_name.".conf.php");

    if (empty ($mgmt_config[$site_name]['youtube_token'])) $mgmt_config[$site_name]['youtube_token'] = "";
  }
?>

<form name="siteform" action="<?php echo $formaction; ?>" method="post">
  <input type="hidden" name="action" value="site_edit" />
  <input type="hidden" name="site_name" value="<?php echo $site_name; ?>">
  <input type="hidden" name="setting[inherit_obj]" value="<?php echo $mgmt_config[$site_name]['inherit_obj']; ?>" />
  <input type="hidden" name="setting[inherit_comp]" value="<?php echo $mgmt_config[$site_name]['inherit_comp']; ?>" />
  <input type="hidden" name="setting[inherit_tpl]" value="<?php echo $mgmt_config[$site_name]['inherit_tpl']; ?>" />
  <input type="hidden" name="setting[youtube_token]" value="<?php echo $mgmt_config[$site_name]['youtube_token']; ?>" />
  <input type="hidden" name="setting[registration]" value="<?php if (!empty ($mgmt_config[$site_name]['registration'])) echo "true"; else echo "false"; ?>" />
  <input type="hidden" name="setting[registration_group]" value="<?php echo $mgmt_config[$site_name]['registration_group']; ?>" />
  <input type="hidden" name="setting[registration_notify]" value="<?php echo $mgmt_config[$site_name]['registration_notify']; ?>" />
  <input type="hidden" name="token" value="<?php echo createtoken ($user); ?>">
  
  <table class="hcmsTableStandard hcmsTableFlip" style="width:100%;">
    <tr> 
      <td style="white-space:nowrap; vertical-align:top;" colspan="2"><span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['configuration-of-publication'][$lang]); ?> <?php echo $site_name; ?></span><hr /></td>
    </tr>

    <!-- management configuration -->
    <tr> 
      <td style="white-space:nowrap; vertical-align:top;" colspan="2" class="hcmsHeadlineTiny"><div style="padding:10px 0px;"><?php echo getescapedtext ($hcms_lang['management-system-configuration'][$lang]); ?></div> </td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['display-name-optional'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="text" id="displayname" name="setting[displayname]" style="width:350px;" maxlength="100" value="<?php if (!empty ($mgmt_config[$site_name]['displayname'])) echo $mgmt_config[$site_name]['displayname']; else echo $site_name; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr> 
      <td style="width:20%; white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['grant-publication-management'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
      <label><input type="checkbox" name="setting[site_admin]" value="true" <?php if (@$mgmt_config[$site_name]['site_admin'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['url-of-the-website'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="text" id="url_path_page" name="setting[url_path_page]" style="width:350px;" value="<?php echo @$mgmt_config[$site_name]['url_path_page']; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['directory-path-of-the-website'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="text" id="abs_path_page" name="setting[abs_path_page]" style="width:350px;" value="<?php echo @$mgmt_config[$site_name]['abs_path_page']; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['folders-to-exclude'][$lang]); ?> <br />
        (<?php echo getescapedtext ($hcms_lang['use-as-delimiter'][$lang]); ?>)</td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <textarea name="setting[exclude_folders]" style="width:350px;" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> rows="3"><?php echo $mgmt_config[$site_name]['exclude_folders']; ?></textarea></td>
    </tr>
    <tr>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['allow-access-to-assets-only-for-certain-ip-addresses'][$lang]); ?> <br />
        (<?php echo getescapedtext ($hcms_lang['use-as-delimiter'][$lang]); ?>)</td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <textarea name="setting[allow_ip]" style="width:350px;" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> rows="3"><?php echo $mgmt_config[$site_name]['allow_ip']; ?></textarea></td>
    </tr>
    <?php if (is_dir ($mgmt_config['abs_path_cms']."webdav")) { ?>
    <tr> 
      <td style="vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['allow-access-through-webdav'][$lang]); ?> <br />
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
        <label><input type="checkbox" name="setting[webdav]" value="true" onclick="hcms_switchFormLayer ('webdavLayer');" <?php if (!empty ($mgmt_config[$site_name]['webdav'])) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
        <div id="webdavLayer" style="<?php if (!empty ($mgmt_config[$site_name]['webdav'])) echo "display:inline;"; else echo "display:none;"; ?>">
          <br/><label><input type="checkbox" name="setting[webdav_dl]" value="true" <?php if (!empty ($mgmt_config[$site_name]['webdav_dl'])) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
          <?php echo getescapedtext ($hcms_lang['download-link'][$lang]); ?></label>
          <br/><label><input type="checkbox" name="setting[webdav_al]" value="true" <?php if (!empty ($mgmt_config[$site_name]['webdav_al'])) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
          <?php echo getescapedtext ($hcms_lang['access-link'][$lang]); ?></label>
        </div>
      </td>
    </tr>
    <?php } ?>
    <tr>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['link-management'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
        <input type="checkbox" id="linkengine" name="setting[linkengine]" value="true" <?php if (!empty ($mgmt_config[$site_name]['linkengine'])) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['default-characterset'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="text" name="setting[default_codepage]" style="width:350px;" value="<?php if (@$mgmt_config[$site_name]['default_codepage'] != "") echo $mgmt_config[$site_name]['default_codepage']; else echo "UTF-8"; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['send-e-mail'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
        <input type="checkbox" name="setting[sendmail]" value="true" <?php if (!empty ($mgmt_config[$site_name]['sendmail'])) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['mail-server-name-has-effect-on-sendlink'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="text" name="setting[mailserver]" style="width:350px;" value="<?php echo @$mgmt_config[$site_name]['mailserver']; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ("Portal ".$hcms_lang['access-link'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
        <input type="checkbox" name="setting[portalaccesslink]" value="true" <?php if (@$mgmt_config[$site_name]['portalaccesslink'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['user-for-access-links'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
        <select name="setting[accesslinkuser]" style="width:350px;" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?>>
          <option value=""><?php echo $hcms_lang['please-select-a-user'][$lang]; ?></option>
        <?php
        // user information
        $user_array = getuserinformation ();
        $user_option = array();
        
        if (is_array ($user_array) && sizeof ($user_array) > 0)
        {
          foreach ($user_array[$site_name] as $login => $value)
          {
            if ($login != "admin" && $login != "sys")
            {
              if (@$mgmt_config[$site_name]['accesslinkuser'] == $login) $selected = "selected=\"selected\"";
              else $selected = "";
              
              $text = $login;
              if ($value['realname'] != "") $text .= " (".$value['realname'].")";
    
              $user_option[$text] = "
              <option value=\"".$login."\" ".$selected.">".$text."</option>";
            }
          }

          ksort ($user_option, SORT_STRING | SORT_FLAG_CASE);
          echo implode ("", $user_option);
        }
        ?>
        </select>
      </td>
    </tr>  
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['disable-special-characters-in-object-names'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
        <label><input type="checkbox" name="setting[specialchr_disable]" value="true" <?php if (@$mgmt_config[$site_name]['specialchr_disable'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['only-dam-functionality'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
      <label><input type="checkbox" id="dam" name="setting[dam]" onclick="switchDAM();" value="true" <?php if ($mgmt_config[$site_name]['dam'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['enable-taxonomy-browsing-and-search'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
      <label><input type="checkbox" name="setting[taxonomy]" value="true" <?php if (@$mgmt_config[$site_name]['taxonomy'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['user-must-provide-metadata-for-file-uploads'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
      <label><input type="checkbox" name="setting[upload_userinput]" value="true" <?php if (@$mgmt_config[$site_name]['upload_userinput'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>

    <?php if (is_dir ($mgmt_config['abs_path_cms']."connector")) {	?>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['enable-direct-file-uploads-in-pages'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
      <label><input type="checkbox" id="upload_pages" name="setting[upload_pages]" value="true" <?php if (@$mgmt_config[$site_name]['upload_pages'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <?php } ?>

    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['watermark-options-for-images'][$lang]); ?> <br/><span class="hcmsTextSmall">-wm /images/watermark.png->topleft->10</span></td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="text" name="setting[watermark_image]" style="width:350px;" value="<?php echo @$mgmt_config[$site_name]['watermark_image']; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['watermark-options-for-vidoes'][$lang]); ?> <br/><span class="hcmsTextSmall">-wm /images/watermark.png->topleft->10</span></td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="text" name="setting[watermark_video]" style="width:350px;" value="<?php echo @$mgmt_config[$site_name]['watermark_video']; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> /></td>
    </tr>
    <?php if (is_file ($mgmt_config['abs_path_cms']."connector/youtube/index.php")) { ?>
      <tr> 
        <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['social-media-sharing'][$lang]); ?> </td>
        <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
        <label><input type="checkbox" id="youtube" name="setting[sharesociallink]" value="true" <?php if (@$mgmt_config[$site_name]['sharesociallink'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
          <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
        </td>
      </tr>
      <tr> 
        <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['youtube-upload'][$lang]); ?> </td>
        <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
        <label><input type="checkbox" id="youtube" name="setting[youtube]" value="true" <?php if (@$mgmt_config[$site_name]['youtube'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
          <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
        </td>
      </tr>
    <?php	}	?>

    <?php if (empty ($mgmt_config['theme'])) { ?>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['theme'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
        <select name="setting[theme]" style="width:350px;" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?>>
          <option value=""><?php echo getescapedtext ($hcms_lang['select'][$lang]); ?></option>
        <?php
        $theme_array = getthemes ($site_name);

        if (is_array ($theme_array))
        {
          foreach ($theme_array as $theme_key => $theme_value)
          {
            echo "
            <option value=\"".$theme_key."\" ".($mgmt_config[$site_name]['theme'] == $theme_key ? "selected=\"selected\"" : "").">".$theme_value."</option>";
          }
        }
        ?>
        </select>
      </td>
    </tr>
    <?php	}	?>
    
    <?php if (is_dir ($mgmt_config['abs_path_cms']."connector/")) { ?>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['enable-languages-for-translation'][$lang]." / ".$hcms_lang['taxonomy'][$lang]); ?></td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
        <input type="hidden" name="setting[translate]" value="">
        
        <table class="hcmsTableNarrow hcmsTableNoFlip">
          <tr>
            <td>
              <?php echo getescapedtext ($hcms_lang['available-languages'][$lang]); ?><br />
              <select multiple name="list1" style="width:150px; height:120px;">
              <?php
              // get languages
              $langcode_array = getlanguageoptions();
              $list2_array = array();
      
              if ($langcode_array != false)
              {
                foreach ($langcode_array as $code => $lang_short)
                {
                  if (!empty ($mgmt_config[$site_name]['translate']) && substr_count (",".$mgmt_config[$site_name]['translate'].",", ",".$code.",") > 0)
                  {
                    $list2_array[] = "
                  <option value=\"".$code."\">".$lang_short."</option>";
                  }
                  else
                  {
                    echo "
                  <option value=\"".$code."\">".$lang_short."</option>";
                  }
                }
              }
              ?>
              </select>
            </td>
            <td class="text-align:center; vertical-align:middle;">
              <br />
              <button type="button" class="hcmsButtonBlue" style="width:40px; margin:5px; display:block;" onClick="moveBoxEntry(this.form.elements['list1'], this.form.elements['list2'], 1000)">&gt;&gt;</button>
              <button type="button" class="hcmsButtonBlue" style="width:40px; margin:5px; display:block;" onClick="moveBoxEntry(this.form.elements['list2'], this.form.elements['list1'], 1000)">&lt;&lt;</button>
            </td>
            <td>
              <?php echo getescapedtext ($hcms_lang['selected-languages'][$lang]); ?><br />
              <select multiple name="list2" style="width:150px; height:120px;">
              <?php
              if (!empty ($list2_array) && sizeof ($list2_array) > 0)
              {
                foreach ($list2_array as $temp)
                {
                  echo $temp;
                }
              }
              ?>
              </select>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <?php if (is_supported ($mgmt_parser, "test.png")) { ?>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['optical-character-recognition'][$lang]); ?> (OCR)</td>
      <td style="white-space:nowrap; padding-top:8px;">
        <input type="hidden" name="setting[ocr]" value="">

        <table class="hcmsTableNarrow hcmsTableNoFlip">
          <tr>
            <td>
              <?php echo getescapedtext ($hcms_lang['available-languages'][$lang]); ?><br />
              <select multiple name="ocr1" style="width:150px; height:120px;">
              <?php
              // get languages
              $langcode_array = getlanguageoptions();
              $ocr2_array = array();
      
              if ($langcode_array != false)
              {
                foreach ($langcode_array as $code => $lang_short)
                {
                  if (!empty ($mgmt_config[$site_name]['ocr']) && substr_count (",".$mgmt_config[$site_name]['ocr'].",", ",".$code.",") > 0)
                  {
                    $ocr2_array[] = "
                  <option value=\"".$code."\">".$lang_short."</option>";
                  }
                  else
                  {
                    echo "
                  <option value=\"".$code."\">".$lang_short."</option>";
                  }
                }
              }
              ?>
              </select>
            </td>
            <td class="text-align:center; vertical-align:middle;">
              <br />
              <button type="button" class="hcmsButtonBlue" style="width:40px; margin:5px; display:block;" onClick="moveBoxEntry(this.form.elements['ocr1'], this.form.elements['ocr2'], 3);">&gt;&gt;</button>
              <button type="button" class="hcmsButtonBlue" style="width:40px; margin:5px; display:block;" onClick="moveBoxEntry(this.form.elements['ocr2'], this.form.elements['ocr1'], 1000);">&lt;&lt;</button>
            </td>
            <td>
              <?php echo getescapedtext ($hcms_lang['selected-languages'][$lang]); ?><br />
              <select multiple name="ocr2" style="width:150px; height:120px;">
              <?php
              if (!empty ($ocr2_array) && sizeof ($ocr2_array) > 0)
              {
                foreach ($ocr2_array as $temp)
                {
                  echo $temp;
                }
              }
              ?>
              </select>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <?php } ?>
    <?php } ?>

    <tr> 
      <td style="white-space:nowrap; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['storage-limit-in-mb'][$lang]); ?> </td>
      <td style="white-space:nowrap; padding-top:8px;"> <input type="number" name="setting[storage_limit]" style="width:350px;" value="<?php echo @$mgmt_config[$site_name]['storage_limit']; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> /></td>
    </tr>

    <?php if (is_dir ($mgmt_config['abs_path_cms']."connector/") && is_cloudstorage()) {	?>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="radio" id="storage_type2" name="setting[storage_type]" value="local" <?php if (@$mgmt_config[$site_name]['storage_type'] == "local" || empty ($mgmt_config[$site_name]['storage_type'])) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> /> <?php echo getescapedtext ($hcms_lang['use-local-media-storage'][$lang]); ?></td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="radio" id="storage_type3" name="setting[storage_type]" value="cloud" <?php if (@$mgmt_config[$site_name]['storage_type'] == "cloud") echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> /> <?php echo getescapedtext ($hcms_lang['use-cloud-media-storage'][$lang]); ?></td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['media-storage-type'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="radio" id="storage_type1" name="setting[storage_type]" value="both" <?php if (@$mgmt_config[$site_name]['storage_type'] == "both") echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> /> <?php echo getescapedtext ($hcms_lang['use-local-and-cloud-media-storage'][$lang]); ?></td>
    </tr>
    <?php } ?>

    <?php if (is_file ($mgmt_config['abs_path_cms']."encryption/hypercms_encryption.inc.php")) {	?>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['encrypt-content'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
      <label><input type="checkbox" id="crypt_content" name="setting[crypt_content]" value="true" <?php if (@$mgmt_config[$site_name]['crypt_content'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <?php } ?>

    <?php if (is_dir ($mgmt_config['abs_path_cms']."connector/")) { ?>
      <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ("RESTful API"); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
      <label><input type="checkbox" id="connector_rest" name="setting[connector_rest]" value="true" <?php if (@$mgmt_config[$site_name]['connector_rest'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ("SOAP API"); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
      <label><input type="checkbox" id="connector_soap" name="setting[connector_soap]" value="true" <?php if (@$mgmt_config[$site_name]['connector_soap'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ("Google Cloud API Key (JSON)"); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
        <textarea name="setting[gs_access_json]" style="width:350px; height:80px;" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?>><?php if (is_file ($mgmt_config['abs_path_data']."config/".$site_name.".google_cloud_key.json")) echo loadfile ($mgmt_config['abs_path_data']."config/", $site_name.".google_cloud_key.json"); ?></textarea>
      </td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ("Google Vision (".$hcms_lang['image'][$lang]).")"; ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
      <label><input type="checkbox" name="setting[gs_analyze_image]" value="true" <?php if (@$mgmt_config[$site_name]['gs_analyze_image'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ("Google Video Intelligence (".$hcms_lang['video'][$lang]).")"; ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
      <label><input type="checkbox" name="setting[gs_analyze_video]" value="true" <?php if (@$mgmt_config[$site_name]['gs_analyze_video'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ("Google Speech-to-Text (".$hcms_lang['audio'][$lang].", ".$hcms_lang['video'][$lang].")"); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
      <label><input type="checkbox" name="setting[gs_speech2text]" value="true" <?php if (@$mgmt_config[$site_name]['gs_speech2text'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top;"><?php echo getescapedtext ($hcms_lang['language'][$lang]." (languageCode)"); ?><br/>
        <a href="https://cloud.google.com/speech-to-text/docs/languages" class="hcmsTextSmall" target="_blank">https://cloud.google.com/speech-to-text/docs/languages</a></td>
      <td style="white-space:nowrap; vertical-align:top;">
        <input type="text" name="setting[gs_speech2text_langcode]" style="width:80px;" value="<?php echo @$mgmt_config[$site_name]['gs_speech2text_langcode']; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
      </td>
    </tr>
    <?php } ?>

    <!-- publication target -->
    <tr> 
      <td style="white-space:nowrap; vertical-align:top;" colspan="2"><hr /></td>
    </tr>      
    <tr> 
      <td style="white-space:nowrap; vertical-align:top;" colspan="2" class="hcmsHeadlineTiny"><div style="padding:10px 0px;"><?php echo getescapedtext ($hcms_lang['publication-target-configuration'][$lang]); ?></div> </td>
    </tr>  
  <?php
  // load site config file of publication system
  if (valid_publicationname ($site_name) && file_exists ($mgmt_config['abs_path_rep']."config/".$site_name.".ini"))
  {
    $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site_name.".ini");
  }
  else
  {
    $publ_config = array();
  }    
  ?>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['url-of-the-website'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="text" id="url_publ_page" name="setting[url_publ_page]" style="width:350px;" value="<?php echo $publ_config['url_publ_page']; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['directory-path-of-the-website'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="text" id="abs_publ_page" name="setting[abs_publ_page]" style="width:350px;" value="<?php echo $publ_config['abs_publ_page']; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> /></td>
    </tr>  
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['repository-url'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="text" name="setting[url_publ_rep]" style="width:350px;" value="<?php echo $publ_config['url_publ_rep']; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['repository-directory-path'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="text" name="setting[abs_publ_rep]" style="width:350px;" value="<?php echo $publ_config['abs_publ_rep']; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['directory-path-of-the-application-for-jsp-asp'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="text" id="abs_publ_app"  name="setting[abs_publ_app]" style="width:350px;" value="<?php echo $publ_config['abs_publ_app']; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['os-on-publication-server'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <select name="setting[publ_os]" style="width:350px;" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?>>
          <option value="UNIX" <?php if ($publ_config['publ_os'] == "UNIX") echo "selected=\"selected\""; ?>>UNIX/Linux</option>
          <option value="WIN" <?php if ($publ_config['publ_os'] == "WIN") echo "selected=\"selected\""; ?>>WINDOWS</option>
        </select></td>
    </tr>      
    <tr>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['inclusion-of-components-via-http'][$lang]); ?><br />
        <span class="hcmsTextSmall"><?php echo getescapedtext ($hcms_lang['for-jsp-or-asp-only-http-method-is-supported'][$lang]); ?></span></td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
        <label><input type="checkbox" name="setting[http_incl]" value="true" <?php if ($publ_config['http_incl'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <tr> 
      <td style="vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['remote-client'][$lang]); ?><br />
      <span class="hcmsTextSmall"><?php echo getescapedtext ($hcms_lang['for-http-transport-use-urlremoteclientphp-configuration-ini-file-must-be-at-the-same-file-location'][$lang]); ?></span></td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="text" name="setting[remoteclient]" style="width:350px;" value="<?php echo $mgmt_config[$site_name]['remoteclient']; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> /></td>
    </tr>

    <!-- LDAP/AD -->
    <?php if (is_dir ($mgmt_config['abs_path_cms']."connector/") && !empty ($mgmt_config['authconnect']) && empty ($mgmt_config['authconnect_all'])) {	?>
    <tr>
      <td style="white-space:nowrap; vertical-align:top;" colspan="2"><hr /></td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top;" colspan="2" class="hcmsHeadlineTiny"><div style="padding:10px 0px;">LDAP / MS Active Directory</div> </td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">LDAP/AD <?php echo getescapedtext ($hcms_lang['server'][$lang]); ?> <br/><span class="hcmsTextSmall"><?php echo getescapedtext ($hcms_lang['a-value-is-required'][$lang]); ?></span></td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="text" id="ldap_servers" name="setting[ldap_servers]" style="width:350px;" value="<?php echo @$mgmt_config[$site_name]['ldap_servers']; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">LDAP/AD user domain <br/><span class="hcmsTextSmall">MS AD: <?php echo getescapedtext ($hcms_lang['a-value-is-required'][$lang]); ?></span></td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="text" id="ldap_userdomain" name="setting[ldap_userdomain]" style="width:350px;" value="<?php echo @$mgmt_config[$site_name]['ldap_userdomain']; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">Base Distinguished Name (DN) <br/><span class="hcmsTextSmall"><?php echo getescapedtext ($hcms_lang['a-value-is-required'][$lang]); ?></span></td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="text" id="ldap_base_dn" name="setting[ldap_base_dn]" style="width:350px;" value="<?php echo @$mgmt_config[$site_name]['ldap_base_dn']; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">LDAP/AD <?php echo getescapedtext ($hcms_lang['version'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> 
        <select name="setting[ldap_version]" style="width:350px;" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?>>
          <option <?php if (@$mgmt_config[$site_name]['ldap_version'] == "3") echo "selected=\"selected\""; ?>>3</option>
          <option <?php if (@$mgmt_config[$site_name]['ldap_version'] == "2") echo "selected=\"selected\""; ?>>2</option>
        </select>
      </td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">LDAP/AD Port <br/><span class="hcmsTextSmall"><?php echo getescapedtext ($hcms_lang['a-value-is-required'][$lang]); ?>: 389 (TLS) or 636 (SSL)</span></td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="number" id="ldap_port" name="setting[ldap_port]" style="width:350px;" value="<?php echo @$mgmt_config[$site_name]['ldap_port']; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">Follow referrals </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
      <label><input type="checkbox" id="ldap_follow_referrals" name="setting[ldap_follow_referrals]" value="true" <?php if (@$mgmt_config[$site_name]['ldap_follow_referrals'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">SSL </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
      <label><input type="checkbox" id="ldap_use_ssl" name="setting[ldap_use_ssl]" value="true" <?php if (@$mgmt_config[$site_name]['ldap_use_ssl'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">TLS </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
      <label><input type="checkbox" id="ldap_use_tls" name="setting[ldap_use_tls]" value="true" <?php if (@$mgmt_config[$site_name]['ldap_use_tls'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">User Base Distinguished Name for the LDAP bind <br/><span class="hcmsTextSmall"><?php echo getescapedtext ($hcms_lang['user-name'][$lang]); ?> = %user%: uid=%user%,cn=users</span></td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="text" id="ldap_username_dn" name="setting[ldap_username_dn]" style="width:350px;" value="<?php echo @$mgmt_config[$site_name]['ldap_username_dn']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">LDAP/AD user filter <br/><span class="hcmsTextSmall"><?php echo getescapedtext ($hcms_lang['a-value-is-required'][$lang]); ?>: sAMAccountName (MS AD)</span></td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="text" id="ldap_user_filter" name="setting[ldap_user_filter]" style="width:350px;" value="<?php echo @$mgmt_config[$site_name]['ldap_user_filter']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">LDAP/AD Sync (<?php echo getescapedtext ($hcms_lang['user-information'][$lang].", ".$hcms_lang['member-of-group'][$lang]); ?>) </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
      <label><input type="checkbox" id="ldap_sync" name="setting[ldap_sync]" onclick="switchLDAPsync();" value="true" <?php if (@$mgmt_config[$site_name]['ldap_sync'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"><?php echo getescapedtext ($hcms_lang['delete-user'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
      <label><input type="checkbox" id="ldap_delete_user" name="setting[ldap_delete_user]" value="true" <?php if (@$mgmt_config[$site_name]['ldap_delete_user'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">LDAP/AD + System <?php echo getescapedtext ($hcms_lang['groups'][$lang]." (Merge)"); ?> </td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
      <label><input type="checkbox" id="ldap_keep_groups" name="setting[ldap_keep_groups]" value="true" <?php if (@$mgmt_config[$site_name]['ldap_keep_groups'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
        <?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></label>
      </td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">LDAP/AD user attributes <br/><span class="hcmsTextSmall">'memberof', 'givenname', 'sn', 'telephonenumber', 'mail'</span></td>
      <?php
      if (is_array (@$mgmt_config[$site_name]['ldap_user_attributes']) && sizeof (@$mgmt_config[$site_name]['ldap_user_attributes']) > 0)
      {
        $mgmt_config[$site_name]['ldap_user_attributes'] = "'".implode ("','", $mgmt_config[$site_name]['ldap_user_attributes'])."'";
      }
      else $mgmt_config[$site_name]['ldap_user_attributes'] = "";
      ?>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;"> <input type="text" id="ldap_user_attributes" name="setting[ldap_user_attributes]" style="width:350px;" value="<?php echo @$mgmt_config[$site_name]['ldap_user_attributes']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr> 
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">Mapping 'LDAP <?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>' => '<?php echo getescapedtext ($hcms_lang['user-group'][$lang]); ?>' <br/><span class="hcmsTextSmall">'OU=MANAGER GROUP'=>'ChiefEditor,Editor'<br/>'OU=ALL GROUPS'=>'Editor'</span></td>
      <td style="white-space:nowrap; vertical-align:top; padding-top:8px;">
        <textarea type="text" id="ldap_sync_groups_mapping" name="setting[ldap_sync_groups_mapping]" style="width:350px; height:100px;" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?>><?php
          if (is_array (@$mgmt_config[$site_name]['ldap_sync_groups_mapping']))
          {
            $temp = implode ("\n", array_map(
              function ($v, $k) { return sprintf("'%s'=>'%s'", $k, $v); },
              @$mgmt_config[$site_name]['ldap_sync_groups_mapping'],
              array_keys(@$mgmt_config[$site_name]['ldap_sync_groups_mapping'])
            ));

            echo trim ($temp);
          }
          ?></textarea>
      </td>
    </tr>
    <?php } ?>

    <!-- save -->
    <?php if ($preview != "yes") { ?>         
    <tr>
      <td style="white-space:nowrap; vertical-align:middle; padding-top:10px;"><?php echo getescapedtext ($hcms_lang['save-publication-configuration'][$lang]); ?> </td>
      <td style="white-space:nowrap; vertical-align:middle; padding-top:10px;"><img name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="savePublication();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" /></td>
    </tr>
    <?php } ?>

  </table>
</form>
<?php } ?>

</div>

<?php includefooter(); ?>
</body>
</html>