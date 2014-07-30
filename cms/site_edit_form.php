<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("include/session.inc.php");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");
// disk key
require ("include/diskkey.inc.php");
// language file
require_once ("language/site_edit_form.inc.php");


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
if ($rootpermission['site'] != 1) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";

// check group permissions
if ($rootpermission['site'] != 1 || $rootpermission['siteedit'] != 1)
{
  $show = "<p class=hcmsHeadline>".$text25[$lang]."</p>\n";
}

// check site permissions and save settings
if ($rootpermission['site'] == 1 && $rootpermission['siteedit'] == 1 && $action == "site_edit" && checktoken ($token, $user))
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
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
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
  }
  else
  {
    document.getElementById('url_path_page').disabled = false;
    document.getElementById('abs_path_page').disabled = false;
    document.getElementById('url_publ_page').disabled = false;
    document.getElementById('abs_publ_page').disabled = false;
    document.getElementById('abs_publ_app').disabled = false;
  }
}
-->
</script>
</head>

<body class="hcmsWorkplaceGeneric" onLoad="<?php if ($preview != "yes") echo "switchDAM();"; ?> hcms_preloadImages('<?php echo getthemelocation(); ?>img/button_OK_over.gif'); <?php if ($add_onload != "") echo $add_onload; ?>">
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<?php
echo showmessage ($show, 500, 70, $lang, "position:absolute; left:15px; top:15px;");
?>

<?php
if ($rootpermission['site'] == 1 && $rootpermission['siteedit'] == 1)
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
  
  // load site config file of management system
  if (valid_publicationname ($site_name) && file_exists ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php"))
  {
    include ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php");
  }
  else
  {
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
    $mgmt_config[$site_name]['storage'] = "";
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
      <td nowrap colspan=2><p class="hcmsHeadline"><?php echo $text1[$lang]; ?>: <?php echo $site_name; ?></p></td>
    </tr>    
    <tr align="left" valign="top"> 
      <td nowrap="nowrap" colspan=2 class="hcmsHeadlineTiny"><?php echo $text14[$lang]; ?>: </td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo $text3[$lang]; ?>: </td>
      <td nowrap="nowrap"> <input type="checkbox" name="setting[site_admin]" value="true" <?php if ($mgmt_config[$site_name]['site_admin'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo $text4[$lang]; ?>: </td>
      <td nowrap="nowrap"> <input type="text" id="url_path_page" name="setting[url_path_page]" style="width:350px;" value="<?php echo $mgmt_config[$site_name]['url_path_page']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo $text5[$lang]; ?>: </td>
      <td nowrap="nowrap"> <input type="text" id="abs_path_page" name="setting[abs_path_page]" style="width:350px;" value="<?php echo $mgmt_config[$site_name]['abs_path_page']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td><?php echo $text6[$lang]; ?>: <br />
        <?php echo $text12[$lang]; ?></td>
      <td nowrap="nowrap"> <textarea name="setting[exclude_folders]" style="width:350px;" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> rows="3"><?php echo $mgmt_config[$site_name]['exclude_folders']; ?></textarea></td>
    </tr>
    <tr align="left" valign="top"> 
      <td><?php echo $text29[$lang]; ?>: <br />
        <?php echo $text12[$lang]; ?></td>
      <td nowrap="nowrap"> <textarea name="setting[allow_ip]" style="width:350px;" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> rows="3"><?php echo $mgmt_config[$site_name]['allow_ip']; ?></textarea></td>
    </tr>
    <?php if (is_dir ($mgmt_config['abs_path_cms']."webdav")) { ?>
    <tr align="left" valign="top"> 
      <td><?php echo $text13[$lang]; ?>: <br />
      <td nowrap="nowrap"> <input type="checkbox" name="setting[webdav]" value="true" <?php if ($mgmt_config[$site_name]['webdav'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <?php } ?>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo $text7[$lang]; ?>: </td>
      <td nowrap="nowrap"> <input type="checkbox" name="setting[linkengine]" value="true" <?php if ($mgmt_config[$site_name]['linkengine'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo $text8[$lang]; ?>: </td>
      <td nowrap="nowrap"> <input type="text" name="setting[default_codepage]" style="width:350px;" value="<?php if ($mgmt_config[$site_name]['default_codepage'] != "") echo $mgmt_config[$site_name]['default_codepage']; else echo "UTF-8"; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo $text9[$lang]; ?>: </td>
      <td nowrap="nowrap"> <input type="checkbox" name="setting[sendmail]" value="true" <?php if ($mgmt_config[$site_name]['sendmail'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo $text10[$lang]; ?>: </td>
      <td nowrap="nowrap"> <input type="text" name="setting[mailserver]" style="width:350px;" value="<?php echo $mgmt_config[$site_name]['mailserver']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo $text24[$lang]; ?>: </td>
      <td nowrap="nowrap"> <input type="checkbox" name="setting[specialchr_disable]" value="true" <?php if ($mgmt_config[$site_name]['specialchr_disable'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo $text26[$lang]; ?>: </td>
      <td nowrap="nowrap"> <input type="checkbox" id="dam" name="setting[dam]" onclick="switchDAM();" value="true" <?php if ($mgmt_config[$site_name]['dam'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo $text28[$lang]; ?>: </td>
      <td nowrap="nowrap"> <input type="text" name="setting[storage]" style="width:350px;" value="<?php echo $mgmt_config[$site_name]['storage']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo $text30[$lang]; ?>: </td>
      <td nowrap="nowrap">
        <select name="setting[theme]" style="width:350px;" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?>>
        <?php
        $theme_dir = $mgmt_config['abs_path_cms']."theme/";
        $dir_handler = opendir ($theme_dir);
        
        if ($dir_handler != false)
        {
          while ($theme_opt = @readdir ($dir_handler))
          {
            if ($theme_opt != "." && $theme_opt != ".." && is_dir ($theme_dir.$theme_opt))
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
    <?php 
	if(is_file($mgmt_config['abs_path_cms']."connector/youtube/index.php"))
	{
	?>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo $text27[$lang]; ?>: </td>
      <td nowrap="nowrap"> <input type="checkbox" id="youtube" name="setting[youtube]"  value="true" <?php if ($mgmt_config[$site_name]['youtube'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> />    </td>
    </tr>
    <?php 
	}
	else
	{
	?>
		<input type="hidden" id="youtube" name="setting[youtube]"  value="false"/>
	
    <?php	
	}
	?>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap" colspan=2>&nbsp;</td>
    </tr>      
    <tr align="left" valign="top"> 
      <td nowrap="nowrap" colspan=2 class="hcmsHeadlineTiny"><?php echo $text15[$lang]; ?>: </td>
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
      <td nowrap="nowrap"><?php echo $text4[$lang]; ?>: </td>
      <td nowrap="nowrap"> <input type="text" id="url_publ_page" name="setting[url_publ_page]" style="width:350px;" value="<?php echo $publ_config['url_publ_page']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo $text5[$lang]; ?>: </td>
      <td nowrap="nowrap"> <input type="text" id="abs_publ_page" name="setting[abs_publ_page]" style="width:350px;" value="<?php echo $publ_config['abs_publ_page']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>  
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo $text17[$lang]; ?>: </td>
      <td nowrap="nowrap"> <input type="text" name="setting[url_publ_rep]" style="width:350px;" value="<?php echo $publ_config['url_publ_rep']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo $text18[$lang]; ?>: </td>
      <td nowrap="nowrap"> <input type="text" name="setting[abs_publ_rep]" style="width:350px;" value="<?php echo $publ_config['abs_publ_rep']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top">
      <td nowrap="nowrap"><?php echo $text20[$lang]; ?>: </td>
      <td nowrap="nowrap"> <input type="text" id="abs_publ_app"  name="setting[abs_publ_app]" style="width:350px;" value="<?php echo $publ_config['abs_publ_app']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo $text21[$lang]; ?>: </td>
      <td nowrap="nowrap"> <select name="setting[publ_os]" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?>>
          <option value="UNIX" <?php if ($publ_config['publ_os'] == "UNIX") echo "selected=\"selected\""; ?>>UNIX/Linux</option>
          <option value="WIN" <?php if ($publ_config['publ_os'] == "WIN") echo "selected=\"selected\""; ?>>WINDOWS</option>
        </select></td>
    </tr>      
    <tr align="left" valign="top"> 
      <td nowrap="nowrap"><?php echo $text16[$lang]; ?>: <br />
      <span class="hcmsTextSmall"><?php echo $text19[$lang]; ?></span></td>
      <td valign="top" nowrap="nowrap"> <input type="checkbox" name="setting[http_incl]" value="true" <?php if ($publ_config['http_incl'] == true) echo "checked=\"checked\""; if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr>
    <tr align="left" valign="top"> 
      <td><?php echo $text22[$lang]; ?>: <br />
      <span class="hcmsTextSmall"><?php echo $text23[$lang]; ?></span></td>
      <td nowrap="nowrap"> <input type="text" name="setting[remoteclient]" style="width:350px;" value="<?php echo $mgmt_config[$site_name]['remoteclient']; ?>" <?php if ($preview == "yes") echo " disabled=\"disabled\""; ?> /></td>
    </tr> 
     <?php if ($preview != "yes") { ?>             
    <tr>
      <td nowrap="nowrap"><b><?php echo $text2[$lang]; ?>: </b></td>
      <td nowrap="nowrap"> <img name="Button" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="document.forms['siteform'].submit();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" title="OK" alt="OK" /></td>
    </tr>
    <?php } ?>
  </table>
</form>
<?php } ?>

</div>
</body>
</html>
