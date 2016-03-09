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
// disk key
require ("include/diskkey.inc.php");


// input parameters
$action = getrequest ("action");
$site = getrequest_esc ("site"); // site can be *Null*
$site_name = getrequest_esc ("site_name", "publicationname");
$preview = getrequest ("preview");
$setting = getrequest ("setting", "array");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('site')) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";

// check group permissions
if (!checkrootpermission ('site') || !checkrootpermission ('siteedit'))
{
  $show = "<p class=hcmsHeadline>".getescapedtext ($hcms_lang['you-do-not-have-permissions-to-access-this-feature'][$lang])."</p>\n";
}

// check site permissions and save settings
if (checkrootpermission ('site') && checkrootpermission ('siteedit') && $action == "site_edit" && checktoken ($token, $user))
{
  $result = editpublication ($site_name, $setting, $user);
  
  $add_onload = $result['add_onload'];
  $show = $result['message'];  
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script language="JavaScript" type="text/javascript">
<!--
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
    document.getElementById('storage_type1').disabled = false;
    document.getElementById('storage_type2').disabled = false;
    document.getElementById('storage_type3').disabled = false;
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
    document.getElementById('storage_type1').disabled = true;
    document.getElementById('storage_type2').disabled = true;
    document.getElementById('storage_type3').disabled = true;
  }
}
-->
</script>
</head>

<body class="hcmsWorkplaceGeneric" onLoad="<?php if ($preview != "yes") echo "switchDAM();"; ?> hcms_preloadImages('<?php echo getthemelocation(); ?>img/button_OK_over.gif'); <?php if ($add_onload != "") echo $add_onload; ?>">
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<?php
echo showmessage ($show, 500, 70, $lang, "position:fixed; left:15px; top:15px;");
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
  if (!in_array ($site_name, $siteaccess)) $preview = "yes";
  
  // define php script for form action
  if ($preview == "no")
  {
    $formaction = "site_edit_form.php";
  }
  else
  {
    $formaction = "";
  }
  
  // initalize
  $mgmt_config[$site_name]['site_admin'] = "";
  $mgmt_config[$site_name]['url_path_page'] = "";
  $mgmt_config[$site_name]['abs_path_page'] = "";
  $mgmt_config[$site_name]['exclude_folders'] = "";
  $mgmt_config[$site_name]['allow_ip'] = "";
  $mgmt_config[$site_name]['webdav'] = "";
  $mgmt_config[$site_name]['linkengine'] = "";
  $mgmt_config[$site_name]['default_codepage'] = "";
  $mgmt_config[$site_name]['sendmail'] = "";
  $mgmt_config[$site_name]['mailserver'] = "";
  $mgmt_config[$site_name]['remoteclient'] = "";
  $mgmt_config[$site_name]['specialchr_disable'] = "";
  $mgmt_config[$site_name]['dam'] = "";
  $mgmt_config[$site_name]['upload_userinput'] = "";
  $mgmt_config[$site_name]['upload_pages'] = "";
  $mgmt_config[$site_name]['storage_limit'] = "";
  $mgmt_config[$site_name]['storage_type'] = "";
  $mgmt_config[$site_name]['crypt_content'] = "";
  $mgmt_config[$site_name]['watermark_image'] = "";
  $mgmt_config[$site_name]['watermark_video'] = "";
  
  // load site config file of management system
  if (valid_publicationname ($site_name) && file_exists ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php"))
  {
    include ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php");
    
    if (empty ($mgmt_config[$site_name]['youtube_token'])) $mgmt_config[$site_name]['youtube_token'] = "";
  }
?>

<form name="siteform" action="<?php echo $formaction; ?>" method="post">
  <input type="hidden" name="action" value="site_edit" />
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="site_name" value="<?php echo $site_name; ?>">
  <input type="hidden" name="setting[inherit_obj]" value="<?php echo $mgmt_config[$site_name]['inherit_obj']; ?>" />
  <input type="hidden" name="setting[inherit_comp]" value="<?php echo $mgmt_config[$site_name]['inherit_comp']; ?>" />
  <input type="hidden" name="setting[inherit_tpl]" value="<?php echo $mgmt_config[$site_name]['inherit_tpl']; ?>" />
  <input type="hidden" name="setting[youtube_token]" value="<?php echo $mgmt_config[$site_name]['youtube_token']; ?>" />
  <input type="hidden" name="token" value="<?php echo createtoken ($user); ?>">
  
  <table border="0" cellspacing="0" cellpadding="3" width="590px">
    <tr align="left" valign="top"> 
      <td nowrap colspan=2><p class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['configuration-of-publication'][$lang]); ?>: <?php echo $site_name; ?></p></td>
    </tr>    
    <tr align="left" valign="top"> 
      <td nowrap="nowrap" colspan=2 class="hcmsHeadlineTiny"><?php echo getescapedtext ($hcms_lang['management-system-configuration'][$lang]); ?>: </td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['grant-publication-management'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="checkbox" name="setting[site_admin]" value="true" <?php if (@$mgmt_config[$site_name]['site_admin'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['url-of-the-website'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="text" id="url_path_page" name="setting[url_path_page]" style="width:350px;" value="<?php echo @$mgmt_config[$site_name]['url_path_page']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['directory-path-of-the-website'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="text" id="abs_path_page" name="setting[abs_path_page]" style="width:350px;" value="<?php echo @$mgmt_config[$site_name]['abs_path_page']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td><?php echo getescapedtext ($hcms_lang['folders-to-exclude'][$lang]); ?>: <br />
        (<?php echo getescapedtext ($hcms_lang['use-as-delimiter'][$lang]); ?>)</td>
      <td nowrap="nowrap"> <textarea name="setting[exclude_folders]" style="width:350px;" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> rows="3"><?php echo $mgmt_config[$site_name]['exclude_folders']; ?></textarea></td>
    </tr>
    <tr align="left" valign="top"> 
      <td><?php echo getescapedtext ($hcms_lang['allow-access-to-assets-only-for-certain-ip-addresses'][$lang]); ?>: <br />
        (<?php echo getescapedtext ($hcms_lang['use-as-delimiter'][$lang]); ?>)</td>
      <td nowrap="nowrap"> <textarea name="setting[allow_ip]" style="width:350px;" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> rows="3"><?php echo $mgmt_config[$site_name]['allow_ip']; ?></textarea></td>
    </tr>
    <?php if (is_dir ($mgmt_config['abs_path_cms']."webdav")) { ?>
    <tr align="left" valign="top"> 
      <td><?php echo getescapedtext ($hcms_lang['allow-access-through-webdav'][$lang]); ?>: <br />
      <td nowrap="nowrap"> <input type="checkbox" name="setting[webdav]" value="true" <?php if (@$mgmt_config[$site_name]['webdav'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <?php } ?>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['link-management'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="checkbox" id="linkengine" name="setting[linkengine]" value="true" <?php if (@$mgmt_config[$site_name]['linkengine'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['default-characterset'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="text" name="setting[default_codepage]" style="width:350px;" value="<?php if (@$mgmt_config[$site_name]['default_codepage'] != "") echo $mgmt_config[$site_name]['default_codepage']; else echo "UTF-8"; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['send-e-mail'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="checkbox" name="setting[sendmail]" value="true" <?php if (@$mgmt_config[$site_name]['sendmail'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['mail-server-name-has-effect-on-sendlink'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="text" name="setting[mailserver]" style="width:350px;" value="<?php echo @$mgmt_config[$site_name]['mailserver']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['disable-special-characters-in-object-names'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="checkbox" name="setting[specialchr_disable]" value="true" <?php if (@$mgmt_config[$site_name]['specialchr_disable'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['only-dam-functionality'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="checkbox" id="dam" name="setting[dam]" onclick="switchDAM();" value="true" <?php if ($mgmt_config[$site_name]['dam'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['user-must-provide-metadata-for-file-uploads'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="checkbox" name="setting[upload_userinput]" value="true" <?php if (@$mgmt_config[$site_name]['upload_userinput'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <?php if (is_dir ($mgmt_config['abs_path_cms']."connector")) {	?>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['enable-direct-file-uploads-in-pages'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="checkbox" name="setting[upload_pages]" value="true" <?php if (@$mgmt_config[$site_name]['upload_pages'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <?php } ?>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['storage-limit-in-mb'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="text" name="setting[storage_limit]" style="width:350px;" value="<?php echo @$mgmt_config[$site_name]['storage_limit']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <?php if (is_cloudstorage()) {	?>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"> </td>
      <td nowrap="nowrap"> <input type="radio" id="storage_type2" name="setting[storage_type]" value="local" <?php if (@$mgmt_config[$site_name]['storage_type'] == "local" || empty ($mgmt_config[$site_name]['storage_type'])) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /> <?php echo getescapedtext ($hcms_lang['use-local-media-storage'][$lang]); ?></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"> </td>
      <td nowrap="nowrap"> <input type="radio" id="storage_type3" name="setting[storage_type]" value="cloud" <?php if (@$mgmt_config[$site_name]['storage_type'] == "cloud") echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /> <?php echo getescapedtext ($hcms_lang['use-cloud-media-storage'][$lang]); ?></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['media-storage-type'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="radio" id="storage_type1" name="setting[storage_type]" value="both" <?php if (@$mgmt_config[$site_name]['storage_type'] == "both") echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /> <?php echo getescapedtext ($hcms_lang['use-local-and-cloud-media-storage'][$lang]); ?></td>
    </tr>
    <?php } ?>
    <?php if (is_file ($mgmt_config['abs_path_cms']."encryption/hypercms_encryption.inc.php")) {	?>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['encrypt-content'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="checkbox" id="crypt_content" name="setting[crypt_content]" value="true" <?php if (@$mgmt_config[$site_name]['crypt_content'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <?php } ?>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['watermark-options-for-images'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="text" name="setting[watermark_image]" style="width:350px;" value="<?php echo @$mgmt_config[$site_name]['watermark_image']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['watermark-options-for-vidoes'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="text" name="setting[watermark_video]" style="width:350px;" value="<?php echo @$mgmt_config[$site_name]['watermark_video']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <?php if (is_file ($mgmt_config['abs_path_cms']."connector/youtube/index.php")) { ?>
      <tr align="left" valign="top"> 
        <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['social-media-sharing'][$lang]); ?>: </td>
        <td nowrap="nowrap"> <input type="checkbox" id="youtube" name="setting[sharesociallink]" value="true" <?php if (@$mgmt_config[$site_name]['sharesociallink'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
      </tr>
      <tr align="left" valign="top"> 
        <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['youtube-upload'][$lang]); ?>: </td>
        <td nowrap="nowrap"> <input type="checkbox" id="youtube" name="setting[youtube]" value="true" <?php if (@$mgmt_config[$site_name]['youtube'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
      </tr>
    <?php	}	?>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['theme'][$lang]); ?>: </td>
      <td nowrap="nowrap">
        <select name="setting[theme]" style="width:350px;" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?>>
        <?php
        $theme_dir = $mgmt_config['abs_path_cms']."theme/";
        $dir_handler = opendir ($theme_dir);
        
        if ($dir_handler != false)
        {
          while ($theme_opt = @readdir ($dir_handler))
          {
            if (strtolower($theme_opt) != "mobile" && $theme_opt != "." && $theme_opt != ".." && is_dir ($theme_dir.$theme_opt))
            {
              if ($mgmt_config[$site_name]['theme'] == $theme_opt)
              {
                echo "<option value=\"".$theme_opt."\" selected=\"selected\">".ucfirst ($theme_opt)."</option>\n";
              }
              elseif ($mgmt_config[$site_name]['theme'] == "" && strtolower ($theme_opt) == "standard")
              {
                echo "<option value=\"".$theme_opt."\" selected=\"selected\">".ucfirst ($theme_opt)."</option>\n";
              }
              else echo "<option value=\"".$theme_opt."\">".ucfirst ($theme_opt)."</option>\n";
            }
          }
        }
        ?>
        </select>
      </td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap" colspan=2>&nbsp;</td>
    </tr>      
    <tr align="left" valign="top"> 
      <td nowrap="nowrap" colspan=2 class="hcmsHeadlineTiny"><?php echo getescapedtext ($hcms_lang['publication-target-configuration'][$lang]); ?>: </td>
    </tr>  
  <?php
  // load site config file of publication system
  if (valid_publicationname ($site_name) && file_exists ($mgmt_config['abs_path_rep']."config/".$site_name.".ini"))
  {
    $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site_name.".ini");
  }
  else
  {
    $publ_config['url_publ_page'] = "";
    $publ_config['abs_publ_page'] = "";  
    $publ_config['url_publ_rep'] = "";
    $publ_config['abs_publ_rep'] = "";
    $publ_config['abs_publ_app'] = ""; 
    $publ_config['http_incl'] = "";
    $publ_config['publ_os'] = "";
  }    
  ?>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['url-of-the-website'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="text" id="url_publ_page" name="setting[url_publ_page]" style="width:350px;" value="<?php echo $publ_config['url_publ_page']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['directory-path-of-the-website'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="text" id="abs_publ_page" name="setting[abs_publ_page]" style="width:350px;" value="<?php echo $publ_config['abs_publ_page']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>  
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['repository-url'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="text" name="setting[url_publ_rep]" style="width:350px;" value="<?php echo $publ_config['url_publ_rep']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['repository-directory-path'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="text" name="setting[abs_publ_rep]" style="width:350px;" value="<?php echo $publ_config['abs_publ_rep']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top">
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['directory-path-of-the-application-for-jsp-asp'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <input type="text" id="abs_publ_app"  name="setting[abs_publ_app]" style="width:350px;" value="<?php echo $publ_config['abs_publ_app']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['os-on-publication-server'][$lang]); ?>: </td>
      <td nowrap="nowrap"> <select name="setting[publ_os]" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?>>
          <option value="UNIX" <?php if ($publ_config['publ_os'] == "UNIX") echo "selected=\"selected\""; ?>>UNIX/Linux</option>
          <option value="WIN" <?php if ($publ_config['publ_os'] == "WIN") echo "selected=\"selected\""; ?>>WINDOWS</option>
        </select></td>
    </tr>      
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['inclusion-of-components-via-http'][$lang]); ?>: <br />
      <span class="hcmsTextSmall"><?php echo getescapedtext ($hcms_lang['for-jsp-or-asp-only-http-method-is-supported'][$lang]); ?></span></td>
      <td valign="top" nowrap="nowrap"> <input type="checkbox" name="setting[http_incl]" value="true" <?php if ($publ_config['http_incl'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td><?php echo getescapedtext ($hcms_lang['remote-client'][$lang]); ?>: <br />
      <span class="hcmsTextSmall"><?php echo getescapedtext ($hcms_lang['for-http-transport-use-urlremoteclientphp-configuration-ini-file-must-be-at-the-same-file-location'][$lang]); ?></span></td>
      <td nowrap="nowrap"> <input type="text" name="setting[remoteclient]" style="width:350px;" value="<?php echo $mgmt_config[$site_name]['remoteclient']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr> 
     <?php if ($preview != "yes") { ?>             
    <tr>
      <td nowrap="nowrap"><b><?php echo getescapedtext ($hcms_lang['save-publication-configuration'][$lang]); ?>: </b></td>
      <td nowrap="nowrap"> <img name="Button" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="document.forms['siteform'].submit();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" title="OK" alt="OK" /></td>
    </tr>
    <?php } ?>
  </table>
</form>
<?php } ?>

</div>
</body>
</html>
