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
  $thumbdir = getmedialocation ($site, ".hcms.".$media, "abs_path_media").$site."/";
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
    $add_onload = "if (parent.frames['controlFrame']) parent.frames['controlFrame'].location='".cleandomain ($mgmt_config['url_path_cms'])."control_content_menu.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."'; ";
  }
}

// delete versions
if ($setlocalpermission['delete'] == 1 && is_array ($delete) && sizeof ($delete) > 0 && checktoken ($token, $user))
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
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script type="text/javascript" src="javascript/main.min.js"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
<script type="text/javascript">

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

<body class="hcmsWorkplaceGeneric" onLoad="<?php echo $add_onload; ?>hcms_preloadImages('<?php echo getthemelocation(); ?>img/button_ok_over.png');">

<div class="hcmsWorkplaceFrame">
<!-- change versions -->
<form name="versionform" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="page" value="<?php echo $page; ?>" />
  <input type="hidden" name="contentfile_recent" value="<?php echo $contentfile; ?>" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table class="hcmsTableStandard" style="table-layout:auto; width:100%;">
    <tr>
     <td style="white-space:nowrap; width:160px;" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['version-date'][$lang]); ?></td>
     <td style="white-space:nowrap;" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['name'][$lang]); ?></td>
     <td style="white-space:nowrap; width:120px;" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['owner'][$lang]); ?></td>
     <?php if (!empty ($mgmt_config['version_owner'])) { ?>
     <td style="white-space:nowrap; width:80px;" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['container'][$lang]); ?></td>
     <?php } ?>
     <td style="white-space:nowrap; width:60px; text-align:center;" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['compare'][$lang]); ?></td>
     <td style="white-space:nowrap; width:60px; text-align:center;" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['current'][$lang]); ?></td>
     <td style="white-space:nowrap; width:60px; text-align:center;" class="hcmsHeadline"><label style="cursor:pointer;"><input type="checkbox" onclick="toggledelete(this);" style="display:none" /><?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?></label></td>
    </tr>
    <?php
    // select all content version files in directory sorted by date
    $files_v = getcontainerversions ($container_id);

    if (is_array ($files_v) && sizeof ($files_v) > 0)
    {
      reset ($files_v);

      $rowcolor = "";

      foreach ($files_v as $date_v => $file_v)
      {        
        // get object info of version
        $objectinfo_v = getobjectinfo ($site, $location, $page, $user, $file_v);
 
        if (!empty ($objectinfo_v['name'])) $pagename_v = $objectinfo_v['name'];
        else $pagename_v = $pagename;

        // get owner
        if (!empty ($mgmt_config['version_owner']))
        {
          $owner = "";
          
          // load container version
          $contentdata = loadcontainer ($file_v, "version", "sys"); 

          if (!empty ($contentdata))
          {
            $temp = getcontent ($contentdata, "<contentuser>");
            if (!empty ($temp[0])) $owner = $temp[0];
          }
        }

        // define row color
        if ($rowcolor == "hcmsRowData1") $rowcolor = "hcmsRowData2";
        else $rowcolor = "hcmsRowData1";

        echo "
        <tr class=\"".$rowcolor."\">
          <td style=\"white-space:nowrap;\">".showdate ($date_v, "Y-m-d H:i:s", $hcms_lang_date[$lang])."</td>
          <td style=\"white-space:nowrap;\"><a href=\"#\" onClick=\"hcms_openWindow('page_preview.php?site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&container=".url_encode($file_v)."', 'preview', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes', ".windowwidth("object").", ".windowheight("object").")\"><img src=\"".getthemelocation()."img/".$objectinfo_v['icon']."\" width=16 height=16 border=0 />&nbsp; ".$pagename_v."</a></td>";
          if (!empty ($mgmt_config['version_owner'])) echo "
          <td style=\"white-space:nowrap;\">".$owner."</td>";
        echo "
          <td style=\"white-space:nowrap;\"><a href=\"#\" onClick=\"hcms_openWindow('container_source.php?site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&container=".url_encode($file_v)."', 'preview', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes', ".windowwidth("object").", ".windowheight("object").")\">XML</a></td>
          <td style=\"text-align:center; vertical-align:middle;\"><input type=\"checkbox\" name=\"dummy\" value=\"".$file_v."\" onclick=\"if (compare_select('".$file_v."')) this.checked=true; else this.checked=false;\" /></td>
          <td style=\"text-align:center; vertical-align:middle;\"><input type=\"radio\" name=\"actual\" value=\"".$file_v."\" /></td>
          <td style=\"text-align:center; vertical-align:middle;\"><input type=\"checkbox\" name=\"delete[]\" value=\"".$file_v."\" class=\"delete\" ".($setlocalpermission['delete'] != 1 ? "disabled=\"disabled\"" : "")."/></td>
        </tr>";
      }
    }

    if ($media != "" || $page == ".folder")
    {
      $result = getcontainername ($contentfile);
      $contentfile = $result['container'];
    }

    // get owner
    if (!empty ($mgmt_config['version_owner']))
    {
      $owner = "";

      // load working container
      $contentdata = loadcontainer ($contentfile, "work", "sys"); 

      if (!empty ($contentdata))
      {
        $temp = getcontent ($contentdata, "<contentuser>");
        if (!empty ($temp[0])) $owner = $temp[0];
      }
    }
    
    echo "
    <tr class=\"hcmsRowHead2\">
      <td style=\"white-space:nowrap;\">".getescapedtext ($hcms_lang['current-version'][$lang])."</td>
      <td style=\"white-space:nowrap;\"><a href=\"#\" onClick=\"hcms_openWindow('page_preview.php?site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."', 'preview', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes', ".windowwidth("object").", ".windowheight("object").")\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" width=16 height=16 border=0 />&nbsp; ".$pagename."</a></td>";
      if (!empty ($mgmt_config['version_owner'])) echo "
      <td style=\"white-space:nowrap;\">".$owner."</td>";
    echo "
      <td style=\"white-space:nowrap;\"><a href=\"#\" onClick=\"hcms_openWindow('container_source.php?site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."', 'preview', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes', ".windowwidth("object").", ".windowheight("object").")\">XML</a></td>
      <td style=\"text-align:center; vertical-align:middle;\"><input type=\"checkbox\" name=\"dummy\" value=\"\" onclick=\"if (compare_select('".$contentfile."')) this.checked=true; else this.checked=false;\" /></td>
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

<?php
echo showmessage ($show, 600, 70, $lang, "position:fixed; left:10px; top:10px;")
?>

<!-- compare versions -->
<form name="compareform" action="version_content_compare.php" method="post" style="margin-top:4px;">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="page" value="<?php echo $page; ?>" />
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