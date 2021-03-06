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


// plugin file
if (is_file ($mgmt_config['abs_path_data']."config/plugin.conf.php"))
{
  require ($mgmt_config['abs_path_data']."config/plugin.conf.php");
}
else $mgmt_plugin = array();
  
// input parameters
$action = getrequest ("action");
$active = getrequest ("active", "array");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('site')) killsession ($user);

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
      avoidfilecollision ("temp", true);
      break;
      
    case "change":
      if (!is_array ($mgmt_plugin)) $show = getescapedtext ($hcms_lang['could-not-determine-which-addons-to-activate'][$lang]);

      foreach ($mgmt_plugin as $key => &$data)
      {
        $data['active'] = (is_array ($active) && array_key_exists ($key, $active) && $active[$key] == "1"); 
      }

      plugin_saveconfig ($mgmt_plugin);
      avoidfilecollision ("temp", true);

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
  <link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
  <script type="text/javascript" src="javascript/main.min.js"></script>
  <script type="text/javascript" src="javascript/click.min.js"></script>
</head>

<body class="hcmsWorkplaceGeneric">

  <!-- top bar -->
  <?php
  $help = showhelpbutton ("pluginguide", true, $lang, "");

  echo showtopbar ($hcms_lang['plugin-management'][$lang], $lang, "", "", $help);
  echo showmessage ($show, 500, 40, $lang, "position:fixed; left:15px; top:40px;");
  ?>
    
  <!-- content -->
  <div style="width:100%; height:calc(100% - 42px); overflow:auto;">
    <div class="hcmsWorkplaceFrame">
    <form action="" method="POST" name="editplugins">
      <input type="hidden" name="action" value="change" />
      
      <table class="hcmsTableStandard" style="width:98%; table-layout:auto;">
        <tbody>
          <tr>
            <th class="hcmsHeadline" style="text-align:center; white-space:nowrap; width:20px;">#</th>
            <th class="hcmsHeadline" style="text-align:left; white-space:nowrap; width:180px;"><?php echo getescapedtext ($hcms_lang['plugin-name'][$lang]); ?></th>
            <th class="hcmsHeadline" style="text-align:left; white-space:nowrap; width:180px;"><?php echo getescapedtext ($hcms_lang['author'][$lang]); ?></th>
            <th class="hcmsHeadline" style="text-align:left; white-space:nowrap; width:100px;"><?php echo getescapedtext ($hcms_lang['version'][$lang]); ?></th>
            <th class="hcmsHeadline" style="text-align:left; white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['description'][$lang]); ?></th>
            <th class="hcmsHeadline" style="text-align:center; white-space:nowrap; width:40px;"><?php echo getescapedtext ($hcms_lang['active'][$lang]); ?></th>
          </tr>
        <?php
        $cnt = 0;
        
        if (is_array ($mgmt_plugin) && sizeof ($mgmt_plugin) > 0)
        {
          foreach ($mgmt_plugin as $temp_name => $temp_array)
          {
            $cnt++;
          ?>
          <tr class="hcmsRowData<?php echo ($cnt%2)+1; ?>">
            <td style="text-align:center;"><?php echo $cnt; ?></td>
            <td><?php echo $temp_array['name']; ?></td>
            <td><?php echo $temp_array['author']; ?></td>
            <td style="white-space:nowrap;"><?php echo $temp_array['version']; ?></td>
            <td><?php echo $temp_array['description']; ?></td>
            <td style="text-align:center; vertical-align:middle;">
              <input type="checkbox" name="active[<?php echo $temp_name; ?>]" value="1" <?php if (!empty ($temp_array['active'])) echo "checked=\"checked\""; ?>/>
            </td>
          </tr>
        <?php
          }
        }
        ?>
        </tbody>
      </table>
      <br/>
      <table class="hcmsTableStandard" style="margin-top:10px;">
        <tr>
          <td style="width:260px;"><?php echo getescapedtext ($hcms_lang['apply-changes'][$lang]); ?> </td>
          <td><img alt="OK" title="OK" onmouseover="hcms_swapImage('Button1','', '<?php echo getthemelocation(); ?>/img/button_ok_over.png',1)" onmouseout="hcms_swapImgRestore()" onclick="document.forms['editplugins'].submit();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_ok.png" name="Button1" /></td>
        </tr>
        <tr>
          <td style="width:260px;"><?php echo getescapedtext ($hcms_lang['check-for-new-or-changed-plugins'][$lang]); ?> </td>
          <td><img alt="OK" title="OK" onmouseover="hcms_swapImage('Button2','', '<?php echo getthemelocation(); ?>/img/button_ok_over.png',1)" onmouseout="hcms_swapImgRestore()" onclick="window.location='?action=reparse'" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_ok.png" name="Button2" /></td>
        </tr>
      </table>
        
    </form>
    </div>
  </div>

<?php includefooter(); ?>   
</body>
</html>
