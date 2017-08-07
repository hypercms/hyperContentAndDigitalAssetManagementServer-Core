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


// input parameters
$site = getrequest_esc ("site");  // site can be *Null* which is not a valid name!
$login = getrequest_esc ("login", "objectname");
$group = getrequest_esc ("group", "objectname");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if ($site == "*Null*" && (!checkrootpermission ('user')) || ($site != "*Null*" && !checkglobalpermission ($site, 'user'))) killsession ($user);
// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<script>
function checkForm (form, field)
{
  if (form.elements[field].value == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-select-a-user'][$lang]); ?>"));
    return false;
  }
  else
  {
    form.submit();
    return true;
  }
}
</script>
</head>

<body class="hcmsWorkplaceGeneric">
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<?php
if ($login != "" && $login != false)
{
  $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");

  $userrecord = selectcontent ($userdata, "<user>", "<login>", $login);

  $emailarray = getcontent ($userrecord[0], "<email>");
  $email = $emailarray[0];
  $realnamearray = getcontent ($userrecord[0], "<realname>");
  $realname = $realnamearray[0];
  
  // usergroup membership
  if ($site != "*Null*")
  {
    $memberofarray = selectcontent ($userrecord[0], "<memberof>", "<publication>", "$site");
    
    if ($memberofarray != false) 
    {
      $usergrouparray = getcontent ($memberofarray[0], "<usergroup>");
      $usergroup = str_replace ("|", ", ", substr ($usergrouparray[0], 1, strlen ($usergrouparray[0]) - 2));
    }
    else $usergroup = "";
  }
  // site membership
  else
  {
    $sitearray = getcontent ($userrecord[0], "<publication>");
    
    if ($sitearray != false)
    {
      foreach ($sitearray as $site_name)
      {
        $site_names .= $site_name.", ";
      }
      
      $site_names = substr ($site_names, 0, strlen ($site_names) - 2);
    }
    else $site_names = "";    
  }
}
?>
<form name="deleteuser" action="user_delete_script.php" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="login" value="<?php echo $login; ?>"/>
  <input type="hidden" name="group" value="<?php echo $group; ?>" />
  
  <table border="0" cellspacing="0" cellpadding="2">
    <tr>
      <td colspan="2" nowrap="nowrap" class="hcmsHeadline">
        <?php echo getescapedtext ($hcms_lang['user-information'][$lang]); ?>
      </td>
    </tr>
    <tr>
      <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['user-name'][$lang]); ?> </td>
      <td align="left" valign="top" class="hcmsHeadlineTiny"><?php echo $login; ?></td>
    </tr>
    <tr>
      <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['name'][$lang]); ?> </td>
      <td align="left" valign="top" class="hcmsHeadlineTiny"><?php echo $realname; ?></td>
    </tr>
    <tr>
      <td align="left" valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['e-mail'][$lang]); ?> </td>
      <td align="left" valign="top" class="hcmsHeadlineTiny"><?php echo $email; ?></td>
    </tr>
    <?php 
    if ($site != "*Null*")
    {
      echo "<tr>
      <td align=\"left\" valign=\"top\" nowrap=\"nowrap\">".getescapedtext ($hcms_lang['member-of-group'][$lang])." </td>
      <td align=\"left\" valign=\"top\" class=\"hcmsHeadlineTiny\">".$usergroup."</td>
    </tr>\n"; 
    } 
    else
    {
      echo "<tr>
      <td align=\"left\" valign=\"top\" nowrap=\"nowrap\">".getescapedtext ($hcms_lang['member-of-publication'][$lang])." </td>
      <td align=\"left\" valign=\"top\" class=\"hcmsHeadlineTiny\">".$site_names."</td>
    </tr>\n"; 
    }     
    ?>  
    <tr>
      <td nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['remove-user'][$lang]); ?> </td>
      <td><img name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm(document.forms['deleteuser'], 'login');" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" align="absmiddle" alt="OK" title="OK" /></td>
    </tr>
  </table>
</form>

</div>
</body>
</html>
