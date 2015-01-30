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
// language file
require_once ("language/version_template.inc.php");


// input parameters
$site = getrequest_esc ("site", "publicationname");
$cat = getrequest_esc ("cat", "objectname");
$template = getrequest_esc ("template", "objectname");
$template_recent  = getrequest ("template_recent", "objectname");
$actual = getrequest ("actual");
$delete  = getrequest ("delete");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'template') || !checkglobalpermission ($site, 'tpl') || !valid_publicationname ($site)) killsession ($user);
// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// template directory
if (valid_publicationname ($site)) $versiondir = $mgmt_config['abs_path_template'].$site."/";
else $versiondir = "";

// define template name
if (strpos ($template, ".inc.tpl") > 0)
{
  $tpl_name = substr ($template, 0, strpos ($template, ".inc.tpl"));
  $pagecomp = $text12[$lang];
}
elseif (strpos ($template, ".page.tpl") > 0)
{
  $tpl_name = substr ($template, 0, strpos ($template, ".page.tpl"));
  $pagecomp = $text13[$lang];
}
elseif (strpos ($template, ".comp.tpl") > 0)
{
  $tpl_name = substr ($template, 0, strpos ($template, ".comp.tpl"));
  $pagecomp = $text14[$lang];
}
elseif (strpos ($template, ".meta.tpl") > 0)
{
  $tpl_name = substr ($template, 0, strpos ($template, ".meta.tpl"));
  $pagecomp = $text15[$lang];
}

// define icon
$file_info = getfileinfo ($site, $template, "tpl");

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="template-Type" template="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
function warning_versions_update()
{
  var form = document.forms['versionform'];
  
  check = confirm(hcms_entity_decode("<?php echo $text1[$lang]; ?>\r<?php echo $text2[$lang]; ?>"));
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
      alert (hcms_entity_decode("<?php echo $text17[$lang]; ?>"));
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
    hcms_openWindow ('', 'compare', 'menubar=0,resizable=1,location=0,status=1,scrollbars=1', '800', '800');
    form.target = 'compare';
    form.submit();
    return false;
  }
  else
  {
    alert (hcms_entity_decode("<?php echo $text16[$lang]; ?>"));
    return false; 
  }
}
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric" leftmargin=2 topmargin=2 marginwidth=0 marginheight=0 onLoad="hcms_preloadImages('<?php echo getthemelocation(); ?>img/button_OK_over.gif')">

<div class="hcmsWorkplaceFrame">
<!-- change versions -->
<form name="versionform" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="template" value="<?php echo $template; ?>" />
  <input type="hidden" name="template_recent" value="<?php echo $template; ?>" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table border="0" cellspacing="2" cellpadding="3">
    <tr>
     <td width="30%" nowrap="nowrap" class="hcmsHeadline"><?php echo $text3[$lang]; ?></td>
     <td width="60%" nowrap="nowrap" class="hcmsHeadline"><?php echo $pagecomp; ?></td>
     <td nowrap="nowrap" class="hcmsHeadline"><?php echo $text4[$lang]; ?></td>
     <td nowrap="nowrap" class="hcmsHeadline"><?php echo $text5[$lang]; ?></td>
     <td nowrap="nowrap" class="hcmsHeadline"><?php echo $text6[$lang]; ?></td>
    </tr>
    <?php
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

        if ($rename_2 == false) echo "<p class=hcmsHeadline>".$text7[$lang]."</p>\n".$text8[$lang]."\n";
      }
      else echo "<p class=hcmsHeadline>".$text7[$lang]."</p>\n".$text8[$lang]."\n";
    }

    // delete versions
    if (is_array ($delete) && sizeof ($delete) > 0)
    {
      foreach ($delete as $file_v_del)
      {
        if (valid_objectname ($file_v_del))
        {
          $test = deletefile ($versiondir, $file_v_del, 0);
        
          if ($test == false)
          {
            $errcode = "10200";
            $error[] = $mgmt_config['today']."|version_template.php|error|$errcode|deletefile failed for ".$versiondir.$file_v_del;           
          }
        }
      }     
    }

    // select all template version files in directory
    $dir_version = dir ($versiondir);

    while ($entry = $dir_version->read())
    {
      if ($entry != "." && $entry != ".." && @!is_dir ($entry) && @preg_match ("/".$template.".v_/i", $entry))
      {
        $files_v[] = $entry;
      }
    }
    $dir_version->close();

    if (@sizeof ($files_v) >= 1)
    {
      sort ($files_v);
      reset ($files_v);

      $color = false;
      $i = 0;

      foreach ($files_v as $file_v)
      {
        $file_v_ext = substr (strrchr ($file_v, "."), 3);

        $date = substr ($file_v_ext, 0, strpos ($file_v_ext, "_"));
        $time = substr ($file_v_ext, strpos ($file_v_ext, "_") + 1);
        $time = str_replace ("-", ":", $time);

        $date_v = $date." ".$time;

        // define row color
        if ($color == true)
        {
          $rowcolor = "hcmsRowData1";
          $color = false;
        }
        else
        {
          $rowcolor = "hcmsRowData2";
          $color = true;
        }

        echo "<tr class=\"".$rowcolor."\">
          <td nowrap=\"nowrap\">".$date_v."</td>
          <td nowrap=\"nowrap\"><a href=# onClick=\"hcms_openWindow('template_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&template=".url_encode($file_v)."','preview','scrollbars=yes,resizable=yes','800','600')\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" width=16 height=16 border=0 align=\"absmiddle\" />&nbsp; ".$tpl_name."</a> <a href=# onClick=\"hcms_openWindow('template_source.php?site=".url_encode($site)."&template=".url_encode($file_v)."','sourceview','scrollbars=yes,resizable=yes','800','600')\"><font size=\"-2\">(Source Code)</font></a></td>
          <td align=\"middle\" valign=\"middle\"><input type=\"checkbox\" name=\"dummy\" value=\"".$file_v."\" onclick=\"if (compare_select('".$file_v."')) this.checked=true; else this.checked=false;\" /></td>
          <td align=\"middle\" valign=\"middle\"><input type=\"radio\" name=\"actual\" value=\"".$file_v."\" /></td>
          <td align=\"middle\" valign=\"middle\"><input type=\"checkbox\" name=\"delete[]\" value=\"".$file_v."\" /></td>
        </tr>";

        $i++;
      }
    }

    echo "<tr class=\"hcmsRowHead2\">
      <td nowrap=\"nowrap\">".$text9[$lang]."</td>
      <td nowrap=\"nowrap\"><a href=# onClick=\"hcms_openWindow('template_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&template=".url_encode($template)."','preview','scrollbars=yes,resizable=yes','800','600')\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" width=16 height=16 border=0 align=\"absmiddle\" />&nbsp; ".$tpl_name."</a> <a href=# onClick=\"hcms_openWindow('template_source.php?site=".url_encode($site)."&template=".url_encode($template)."','sourceview','scrollbars=yes,resizable=yes','800','600')\"><font size=\"-2\">(Source Code)</font></a></td>
      <td align=\"middle\" valign=\"middle\"><input type=\"checkbox\" name=\"dummy\" value=\"\" onclick=\"if (compare_select('".$template."')) this.checked=true; else this.checked=false;\" /></td>
      <td align=\"middle\" valign=\"middle\"><input type=\"radio\" name=\"actual\" value=\"\" checked=\"checked\" /></td>
      <td align=\"middle\" valign=\"middle\"><input type=\"checkbox\" name=\"dummy\" value=\"\" disabled=\"disabled\" /></td>
    </tr>";
    
    // save log
    savelog (@$error);  
    ?>
  </table><br />
  <div style="width:350px; float:left;"><?php echo $text10[$lang]; ?> :</div>
  <img name="Button" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="warning_versions_update();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" title="OK" alt="OK" /><br />
</form>

<!-- compare versions -->
<form name="compareform" action="version_template_compare.php" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="template" value="<?php echo $template; ?>" />
  <input type="hidden" name="compare_1" value="" />
  <input type="hidden" name="compare_2" value="" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <div style="width:350px; float:left;"><?php echo $text18[$lang]; ?> :</div>
  <img name="Button" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="compare_submit();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" title="OK" alt="OK" />
</form>
</div>

</body>
</html>
