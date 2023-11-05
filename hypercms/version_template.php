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
$site = getrequest_esc ("site", "publicationname");
$cat = getrequest_esc ("cat", "objectname");
$template = getrequest_esc ("template", "objectname");
$template_recent  = getrequest ("template_recent", "objectname");
$actual = getrequest ("actual");
$delete  = getrequest ("delete");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
{
  require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
}

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'template') || !checkglobalpermission ($site, 'tpl') || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// initialize
$error = array();
$add_onload = "";

// write and close session (non-blocking other frames)
suspendsession ();

// template directory
if (valid_publicationname ($site)) $versiondir = $mgmt_config['abs_path_template'].$site."/";
else $versiondir = "";

// define template name
if (strpos ($template, ".inc.tpl") > 0)
{
  $tpl_name = substr ($template, 0, strpos ($template, ".inc.tpl"));
  $pagecomp = getescapedtext ($hcms_lang['template-component'][$lang]);
}
elseif (strpos ($template, ".page.tpl") > 0)
{
  $tpl_name = substr ($template, 0, strpos ($template, ".page.tpl"));
  $pagecomp = getescapedtext ($hcms_lang['page-template'][$lang]);
}
elseif (strpos ($template, ".comp.tpl") > 0)
{
  $tpl_name = substr ($template, 0, strpos ($template, ".comp.tpl"));
  $pagecomp = getescapedtext ($hcms_lang['component-template'][$lang]);
}
elseif (strpos ($template, ".meta.tpl") > 0)
{
  $tpl_name = substr ($template, 0, strpos ($template, ".meta.tpl"));
  $pagecomp = getescapedtext ($hcms_lang['meta-data-template'][$lang]);
}

// define icon
$file_info = getfileinfo ($site, $template, "tpl");

// create secure token
$token_new = createtoken ($user);

// change to version
if ($versiondir != "" && $actual != "" && checktoken ($token, $user))
{
  // make version of actual template file
  $template_v = fileversion ($template);
  $rename_1 = rename ($versiondir.$template_recent, $versiondir.$template_v);

  // make version actual
  if ($rename_1 != false)
  {
    $rename_2 = rename ($versiondir.$actual, $versiondir.$template_recent);

    if ($rename_2 == false) echo "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['could-not-change-version'][$lang])."</p>\n".getescapedtext ($hcms_lang['file-is-missing-or-you-do-not-have-write-permissions'][$lang])."\n";
    else $add_onload = "parent.frames['mainFrame1'].location.href='template_edit.php?site=".$site."&cat=".$cat."&save=no&template=".$template."';";
  }
  else echo "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['could-not-change-version'][$lang])."</p>\n".getescapedtext ($hcms_lang['file-is-missing-or-you-do-not-have-write-permissions'][$lang])."\n";
}

// delete versions
if (checkglobalpermission ($site, 'tpldelete') == 1 && is_array ($delete) && sizeof ($delete) > 0 && checktoken ($token, $user))
{
  foreach ($delete as $file_v_del)
  {
    if (valid_objectname ($file_v_del) && is_file ($versiondir.$file_v_del))
    {
      $deletefile = deletefile ($versiondir, $file_v_del, false);
    
      if ($deletefile == true)
      {
        // extract date and time from file extension
        $file_v_ext = substr (strrchr ($file_v_del, "."), 3);
        $date = substr ($file_v_ext, 0, strpos ($file_v_ext, "_"));
        $time = substr ($file_v_ext, strpos ($file_v_ext, "_") + 1);
        $time = str_replace ("-", ":", $time);
        $datetime = $date." ".$time;
        
        $errcode = "00200";
        $error[] = $mgmt_config['today']."|version_template.php|information|".$errcode."|Version '".$datetime."' of template '".$site."/".str_replace (".v_".$file_v_ext, "", $file_v_del)."' has been deleted by user '".$user."'";           
      }
    }
  }     
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
<script type="text/javascript" src="javascript/click.min.js"></script>
<script type="text/javascript">

function warning_versions_update()
{
  var form = document.forms['versionform'];
  
  check = confirm(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-to-switch-to-a-previous-template-version'][$lang]); ?>\r<?php echo getescapedtext ($hcms_lang['andor-delete-the-selected-versions'][$lang]); ?>"));
  if (check == true) form.submit();
  return check;
}

function compare_select (version)
{
  var form = document.forms['compareform'];
  var compare_1 = form.elements['compare_1'];
  var compare_2 = form.elements['compare_2'];
  
  if (version != "")
  {
    if (compare_1.value == "")
    {
      compare_1.value = version;
      return true;
    }
    else if (compare_1.value == version)
    {
      compare_1.value = "";
      return false;
    }    
    else if (compare_2.value == "") 
    {
      compare_2.value = version;
      return true;
    }
    else if (compare_2.value == version)
    {
      compare_2.value = "";
      return false;
    }     
    else
    {
      alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['only-two-versions-can-be-compared'][$lang]); ?>"));
      return false;
    }
  }
}

function compare_submit ()
{
  var form = document.forms['compareform'];
  var compare_1 = form.elements['compare_1'];
  var compare_2 = form.elements['compare_2'];
  
  if (compare_1.value != "" && compare_2.value != "")
  {
    hcms_openWindow ('', 'compare', 'location=no,menubar=no,toolbar=no,titlebar=no,resizable=yes,status=yes,scrollbars=yes', <?php echo windowwidth ("object"); ?>, <?php echo windowheight ("object"); ?>);
    form.target = 'compare';
    form.submit();
    return false;
  }
  else
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['two-versions-need-to-be-selected-for-comparison'][$lang]); ?>"));
    return false; 
  }
}

function toggledelete (source)
{
  var checkboxes = document.getElementsByClassName('delete');
  
  for (var i=0; i<checkboxes.length; i++)
  {
    checkboxes[i].checked = source.checked;
  }
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" onload="hcms_preloadImages('<?php echo getthemelocation(); ?>img/button_ok_over.png'); <?php echo $add_onload; ?>">

<div class="hcmsWorkplaceFrame">
<!-- change versions -->
<form name="versionform" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="template" value="<?php echo $template; ?>" />
  <input type="hidden" name="template_recent" value="<?php echo $template; ?>" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table class="hcmsTableStandard" style="table-layout:auto; width:100%;">
    <tr>
      <td style="white-space:nowrap; width:160px;" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['version-date'][$lang]); ?></td>
      <td style="white-space:nowrap;" class="hcmsHeadline"><?php echo $pagecomp; ?></td>
      <?php if (!empty ($mgmt_config['version_owner'])) { ?>
      <td style="white-space:nowrap; width:120px;" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['owner'][$lang]); ?></td>
      <?php } ?>
      <td style="white-space:nowrap; width:60px; text-align:center;" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['compare'][$lang]); ?></td>
      <td style="white-space:nowrap; width:60px; text-align:center;" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['current'][$lang]); ?></td>
      <td style="white-space:nowrap; width:60px; text-align:center;" class="hcmsHeadline"><label style="cursor:pointer;"><input type="checkbox" onclick="toggledelete(this);" style="display:none" /><?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?></label></td>
    </tr>
    <?php
    // select all template version files in directory sorted by date
    $files_v = gettemplateversions ($site, $template);

    if (is_array ($files_v) && sizeof ($files_v) > 0)
    {
      reset ($files_v);

      $rowcolor = "";

      foreach ($files_v as $date_v => $file_v)
      {
        // get owner
        if (!empty ($mgmt_config['version_owner']))
        {
          $owner = "";

          // load template version
          $templatedata = loadtemplate ($site, $file_v);

          if (!empty ($templatedata['content']))
          {
            $temp = getcontent ($templatedata['content'], "<user>");
            if (!empty ($temp[0])) $owner = $temp[0];
          }
        }

        // define row color
        if ($rowcolor == "hcmsRowData1") $rowcolor = "hcmsRowData2";
        else $rowcolor = "hcmsRowData1";

        echo "
        <tr class=\"".$rowcolor."\">
          <td style=\"white-space:nowrap;\">".showdate ($date_v, "Y-m-d H:i:s", $hcms_lang_date[$lang])."</td>
          <td style=\"white-space:nowrap;\"><a href=\"javascript:void(0);\" onClick=\"hcms_openWindow('template_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&template=".url_encode($file_v)."', 'preview', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes', ".windowwidth("object").", ".windowheight("object").")\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" class=\"hcmsIconList\" />&nbsp; ".$tpl_name."</a> <a href=\"javascript:void(0);\" onClick=\"hcms_openWindow('template_source.php?site=".url_encode($site)."&template=".url_encode($file_v)."', '', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes', ".windowwidth("object").", ".windowheight("object").")\"><span class=\"hcmsTextSmall\">(Source Code)</span</a></td>";
          if (!empty ($mgmt_config['version_owner'])) echo "
          <td style=\"white-space:nowrap;\">".$owner."</td>";
        echo "
          <td style=\"text-align:center; vertical-align:middle;\"><input type=\"checkbox\" name=\"dummy\" value=\"".$file_v."\" onclick=\"if (compare_select('".$file_v."')) this.checked=true; else this.checked=false;\" /></td>
          <td style=\"text-align:center; vertical-align:middle;\"><input type=\"radio\" name=\"actual\" value=\"".$file_v."\" /></td>
          <td style=\"text-align:center; vertical-align:middle;\"><input type=\"checkbox\" name=\"delete[]\" value=\"".$file_v."\" class=\"delete\" ".(checkglobalpermission ($site, 'tpldelete') != 1 ? "disabled=\"disabled\"" : "")."/></td>
        </tr>";
      }
    }

    // get owner
    if (!empty ($mgmt_config['version_owner']))
    {
      $owner = "";

      // load template
      $contentdata = loadtemplate ($site, $template); 

      if (!empty ($templatedata['content']))
      {
        $temp = getcontent ($templatedata['content'], "<user>");
        if (!empty ($temp[0])) $owner = $temp[0];
      }
    }

    echo "
    <tr class=\"hcmsRowHead2\">
      <td style=\"white-space:nowrap;\">".getescapedtext ($hcms_lang['current-version'][$lang])."</td>
      <td style=\"white-space:nowrap;\"><a href=\"javascript:void(0);\" onClick=\"hcms_openWindow('template_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&template=".url_encode($template)."', 'preview', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes', ".windowwidth("object").", ".windowheight("object").")\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" class=\"hcmsIconList\" />&nbsp; ".$tpl_name."</a> <a href=\"javascript:void(0);\" onClick=\"hcms_openWindow('template_source.php?site=".url_encode($site)."&template=".url_encode($template)."', 'sourceview', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes', ".windowwidth("object").", ".windowheight("object").")\"><span class=\"hcmsTextSmall\">(Source Code)</span></a></td>";
      if (!empty ($mgmt_config['version_owner'])) echo "
      <td style=\"white-space:nowrap;\">".$owner."</td>";
    echo "
      <td style=\"text-align:center; vertical-align:middle;\"><input type=\"checkbox\" name=\"dummy\" value=\"\" onclick=\"if (compare_select('".$template."')) this.checked=true; else this.checked=false;\" /></td>
      <td style=\"text-align:center; vertical-align:middle;\"><input type=\"radio\" name=\"actual\" value=\"\" checked=\"checked\" /></td>
      <td style=\"text-align:center; vertical-align:middle;\"><input type=\"checkbox\" name=\"dummy\" value=\"\" disabled=\"disabled\" /></td>
    </tr>";
    
    // save log
    savelog (@$error);  
    ?>
  </table>
  <br />
  <table class="hcmsTableStandard">
    <tr>
      <td style="width:260px;"><?php echo getescapedtext ($hcms_lang['submit-changes-to-versions'][$lang]); ?> </td>
      <td><img name="Button1" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="warning_versions_update();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" /></td>
    </tr>
  </table>
</form>

<!-- compare versions -->
<form name="compareform" action="version_template_compare.php" method="post" style="margin-top:4px;">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="template" value="<?php echo $template; ?>" />
  <input type="hidden" name="compare_1" value="" />
  <input type="hidden" name="compare_2" value="" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table class="hcmsTableStandard">
    <tr>
      <td style="width:260px;"><?php echo getescapedtext ($hcms_lang['compare-selected-versions'][$lang]); ?> </td>
      <td><img name="Button2" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="compare_submit();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button2','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" /></td>
    </tr>
  </table>
</form>
</div>

<?php includefooter(); ?>
</body>
</html>