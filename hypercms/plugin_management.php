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

// plugin file
if (file_exists ($mgmt_config['abs_path_data']."config/plugin.conf.php"))
{
  require ($mgmt_config['abs_path_data']."config/plugin.conf.php");
}
else $mgmt_plugin = array();
  
// input parameters
$action = getrequest ("action");
$active = getrequest ("active");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('site'))  killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";

if ($action)
{
  switch ($action)
  {
    case "reparse":
      $mgmt_plugin = plugin_parse ($mgmt_plugin);
      plugin_saveconfig ($mgmt_plugin);
      
      // reload plugin config
      if (file_exists ($mgmt_config['abs_path_data'].'config/plugin.conf.php'))
      {
        require ($mgmt_config['abs_path_data'].'config/plugin.conf.php');
      }
      break;
      
    case "change":
      if (!is_array ($mgmt_plugin)) $show = getescapedtext ($hcms_lang['could-not-determine-which-addons-to-activate'][$lang]);

      foreach ($mgmt_plugin as $key => &$data)
      {
        $data['active'] = (is_array ($active) && array_key_exists ($key, $active) && $active[$key] == 1); 
      }
      
      plugin_saveconfig ($mgmt_plugin);
      
      // reload plugin config
      if (file_exists ($mgmt_config['abs_path_data'].'config/plugin.conf.php'))
      {
        require ($mgmt_config['abs_path_data'].'config/plugin.conf.php');
      }
      break;
      
    default:
      $show  = getescapedtext ($hcms_lang['this-action-is-not-supported'][$lang]);
      break;    
  }
}
?>
<!DOCTYPE html>
<html>
  <head>
    <title>hyperCMS</title>
    <meta charset="<?php echo getcodepage ($lang); ?>" />
    <link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
    <script src="javascript/main.js" type="text/javascript"></script>
    <script src="javascript/click.js" type="text/javascript"></script>
  </head>

  <body class="hcmsWorkplaceGeneric">

    <!-- top bar -->
    <?php
    if (!$is_mobile && file_exists ($mgmt_config['abs_path_cms']."help/pluginguide_".$hcms_lang_shortcut[$lang].".pdf"))
    {
      $help = "<a href=# onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('pic_obj_help','','".getthemelocation()."img/button_help_over.gif',1)\" onClick=\"hcms_openWindow('help/pluginguide_".$hcms_lang_shortcut[$lang].".pdf', 'help', 'scrollbars=no,resizable=yes', 800, 600);\"><img name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" /></a>";
    }
    elseif (!$is_mobile && file_exists ($mgmt_config['abs_path_cms']."help/pluginguide_en.pdf"))
    {
      $help = "<a href=# onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('pic_obj_help','','".getthemelocation()."img/button_help_over.gif',1)\" onClick=\"hcms_openWindow('help/pluginguide_en.pdf', 'help', 'scrollbars=no,resizable=yes', 800, 600);\"><img name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" /></a>";
    }
    else $help = "";

    echo showtopbar ($hcms_lang['plugin-management'][$lang], $lang, "", "", $help);
    echo showmessage ($show, 500, 40, $lang, "position:fixed; left:15px; top:40px;");
    ?>
    
    <!-- content -->    
    <form action="?action=change" method="POST" name="editplugins" class="hcmsWorkplaceFrame">
      <table cellspacing="2" cellpadding="3" border="0" width="98%">
        <tbody>
          <tr>
            <th class="hcmsHeadline" align="left" nowrap="nowrap" width="50"><?php echo getescapedtext ($hcms_lang['number'][$lang]); ?></th>
            <th class="hcmsHeadline" align="left" nowrap="nowrap" width="30%"><?php echo getescapedtext ($hcms_lang['plugin-name'][$lang]); ?></th>
            <th class="hcmsHeadline" align="left" nowrap="nowrap" width="30%"><?php echo getescapedtext ($hcms_lang['author'][$lang]); ?></th>
            <th class="hcmsHeadline" align="left" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['version'][$lang]); ?></th>
            <th class="hcmsHeadline" align="left" nowrap="nowrap" width="40%"><?php echo getescapedtext ($hcms_lang['description'][$lang]); ?></th>
            <th class="hcmsHeadline" align="left" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></th>
          </tr>
        <?php
        $cnt = 0;
        
        if (is_array ($mgmt_plugin) && sizeof ($mgmt_plugin) > 0)
        {
          foreach ($mgmt_plugin as $key => $data)
          {
            $cnt++;
          ?>
          <tr class="hcmsRowData<?php echo ($cnt%2)+1; ?>">
            <td nowrap="nowrap"><?php echo $cnt; ?></td>
            <td><?php echo $data['name']; ?></td>
            <td><?php echo $data['author']; ?></td>
            <td nowrap="nowrap"><?php echo $data['version']; ?></td>
            <td><?php echo $data['description']; ?></td>
            <td align="center" valign="middle">
              <input type="checkbox" name="active[<?php echo $key; ?>]" value="1" <?php if ($data['active'] == true) echo "checked=\"checked\""; ?>/>
            </td>
          </tr>
        <?php
          }
        }
        ?>
        </tbody>
      </table>
      
      <div style="margin-top:10px;">
        <div style="width:260px; float:left;"><?php echo getescapedtext ($hcms_lang['apply-changes'][$lang]); ?>:</div>
        <img align="absmiddle" alt="OK" title="OK" onmouseover="hcms_swapImage('Button1','', '<?php echo getthemelocation(); ?>/img/button_OK_over.gif',1)" onmouseout="hcms_swapImgRestore()" onclick="document.forms['editplugins'].submit();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_OK.gif" name="Button1">
        <div style="clear:both;"></div>
        <div style="width:260px; float:left;"><?php echo getescapedtext ($hcms_lang['check-for-new-or-changed-plugins'][$lang]); ?>:</div>
        <img align="absmiddle" alt="OK" title="OK" onmouseover="hcms_swapImage('Button2','', '<?php echo getthemelocation(); ?>/img/button_OK_over.gif',1)" onmouseout="hcms_swapImgRestore()" onclick="window.location='?action=reparse'" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_OK.gif" name="Button2">
      </div>
        
    </form>
    
  </body>
</html>
