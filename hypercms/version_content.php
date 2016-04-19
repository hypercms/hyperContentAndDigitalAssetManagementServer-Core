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


// input parameters
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$contentfile_recent = getrequest ("contentfile_recent", "objectname");
$actual = getrequest ("actual");
$delete  = getrequest ("delete", "array");
$wf_token = getrequest ("wf_token");
$token = getrequest ("token");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);  
if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";

// read actual file info (to get associated content)
$pagestore = loadfile ($location, $page);
$contentfile = getfilename ($pagestore, "content");
$media = getfilename ($pagestore, "media");

if ($contentfile == false)
{
  $show = "<p class=hcmsHeadline>".getescapedtext ($hcms_lang['item-is-not-managed-by-hypercms'][$lang])."</p>".getescapedtext ($hcms_lang['no-versions-available'][$lang])."\n";
}
elseif (valid_objectname ($contentfile))
{
  $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml")); 
  $versiondir = getcontentlocation ($container_id, 'abs_path_content');
  $mediadir = getmedialocation ($site, $media, "abs_path_media").$site."/";
  $thumbdir = getmedialocation ($site, "dummy.".$media, "abs_path_media").$site."/";
}

// create secure token
$token_new = createtoken ($user);

// change to version
if ($actual != "" && checktoken ($token, $user))
{
  $rollbackversion = rollbackversion ($site, $location, $page, $actual, $user);
  
  $show = $rollbackversion['message'];
  
  // reset object name
  if ($page != $rollbackversion['object'])
  {
    $page = $rollbackversion['object'];
    $add_onload = "if (eval (parent.frames['controlFrame'])) parent.frames['controlFrame'].location='".$mgmt_config['url_path_cms']."control_content_menu.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."'; ";
  }
}

// delete versions
if (is_array ($delete) && sizeof ($delete) > 0 && checktoken ($token, $user))
{
  foreach ($delete as $file_v_del)
  {
    if (valid_objectname ($file_v_del))
    {
      deleteversion ($site, $file_v_del, $user);
    }
  }
}

// get file info
$file_info = getfileinfo ($site, $location.$page, $cat);    
$pagename = $file_info['name'];
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
function warning_versions_update()
{
  var form = document.forms['versionform'];
  
  check = confirm (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-to-switch-to-a-previous-content-version'][$lang])." ".getescapedtext ($hcms_lang['andor-delete-the-selected-versions'][$lang]); ?>"));
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
    hcms_openWindow ('', 'compare', 'menubar=0,resizable=1,location=0,status=1,scrollbars=1', '800', '800');
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
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric" onLoad="<?php echo $add_onload; ?>hcms_preloadImages('<?php echo getthemelocation(); ?>img/button_OK_over.gif');">

<div class="hcmsWorkplaceFrame">
<!-- change versions -->
<form name="versionform" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="page" value="<?php echo $page; ?>" />
  <input type="hidden" name="contentfile_recent" value="<?php echo $contentfile; ?>" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table border="0" cellspacing="2" cellpadding="3" width="99%">
    <tr>
     <td width="30%" nowrap="nowrap" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['version-date'][$lang]); ?></td>
     <td width="30%" nowrap="nowrap" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['name'][$lang]); ?></td>
     <td width="30%" nowrap="nowrap" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['container'][$lang]); ?></td>
     <td nowrap="nowrap" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['compare'][$lang]); ?></td>
     <td nowrap="nowrap" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['current'][$lang]); ?></td>
     <td nowrap="nowrap" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?></td>
    </tr>
    <?php
    // select all content version files in directory sorted by date
    $files_v = getcontainerversions ($container_id);

    if (is_array ($files_v) && sizeof ($files_v) > 0)
    {
      reset ($files_v);

      $color = false;
      $i = 0;

      foreach ($files_v as $date_v => $file_v)
      {        
        // get object info of version
        $objectinfo_v = getobjectinfo ($site, $location, $page, $user, $file_v);
 
        if (!empty ($objectinfo_v['name'])) $pagename_v = $objectinfo_v['name'];
        else $pagename_v = $pagename;

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
          <td nowrap=\"nowrap\"><a href=\"#\" onClick=\"hcms_openWindow('page_preview.php?site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&container=".url_encode($file_v)."','preview','scrollbars=yes,resizable=yes','800','600')\"><img src=\"".getthemelocation()."img/".$objectinfo_v['icon']."\" width=16 height=16 border=0 align=\"absmiddle\" />&nbsp; ".$pagename_v."</a></td>
          <td nowrap=\"nowrap\"><a href=\"#\" onClick=\"hcms_openWindow('container_source.php?site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&container=".url_encode($file_v)."','preview','scrollbars=yes,resizable=yes','800','600')\">XML</a></td>
          <td align=\"middle\" valign=\"middle\"><input type=\"checkbox\" name=\"dummy\" value=\"".$file_v."\" onclick=\"if (compare_select('".$file_v."')) this.checked=true; else this.checked=false;\" /></td>
          <td align=\"middle\" valign=\"middle\"><input type=\"radio\" name=\"actual\" value=\"".$file_v."\" /></td>
          <td align=\"middle\" valign=\"middle\"><input type=\"checkbox\" name=\"delete[]\" value=\"".$file_v."\" /></td>
        </tr>";

        $i++;
      }
    }

    if ($media != "" || $page == ".folder")
    {
      $result = getcontainername ($contentfile);
      $contentfile = $result['container'];
    }
    
    echo "<tr class=\"hcmsRowHead2\">
      <td nowrap=\"nowrap\">".getescapedtext ($hcms_lang['current-version'][$lang])."</td>
      <td nowrap=\"nowrap\"><a href=\"#\" onClick=\"hcms_openWindow('page_preview.php?site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."','preview','scrollbars=yes,resizable=yes','800','600')\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" width=16 height=16 border=0 align=\"absmiddle\" />&nbsp; ".$pagename."</a></td>
      <td nowrap=\"nowrap\"><a href=\"#\" onClick=\"hcms_openWindow('container_source.php?site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."','preview','scrollbars=yes,resizable=yes','800','600')\">XML</a></td>
      <td align=\"middle\" valign=\"middle\"><input type=\"checkbox\" name=\"dummy\" value=\"\" onclick=\"if (compare_select('".$contentfile."')) this.checked=true; else this.checked=false;\" /></td>
      <td align=\"middle\" valign=\"middle\"><input type=\"radio\" name=\"actual\" value=\"\" checked=\"checked\" /></td>
      <td align=\"middle\" valign=\"middle\"><input type=\"checkbox\" name=\"dummy\" value=\"\" disabled=\"disabled\" /></td>
    </tr>";    

    // save log
    savelog (@$error);     
    ?>
  </table><br />
  <div style="width:300px; float:left;"><?php echo getescapedtext ($hcms_lang['submit-changes-to-versions'][$lang]); ?>:</div>
  <img name="Button1" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="warning_versions_update();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" title="OK" alt="OK" /><br />
</form>

<?php
echo showmessage ($show, 600, 70, $lang, "position:fixed; left:5px; top:100px;")
?>

<!-- compare versions -->
<form name="compareform" action="version_content_compare.php" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="page" value="<?php echo $page; ?>" />
  <input type="hidden" name="compare_1" value="" />
  <input type="hidden" name="compare_2" value="" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <div style="width:300px; float:left;"><?php echo getescapedtext ($hcms_lang['compare-selected-versions'][$lang]); ?>:</div>
  <img name="Button2" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="compare_submit();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button2','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" title="OK" alt="OK" />
</form>
</div>

</body>
</html>
