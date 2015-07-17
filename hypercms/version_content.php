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
// hyperCMS UI
require ("function/hypercms_ui.inc.php");


// input parameters
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$contentfile_recent = getrequest ("contentfile_recent", "objectname");
$actual = getrequest ("actual");
$delete  = getrequest ("delete", "array");
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
  $show = "";
}

// get file info
$file_info = getfileinfo ($site, $location.$page, $cat);    
$pagename = $file_info['name'];

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
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

<body class="hcmsWorkplaceGeneric" onLoad="hcms_preloadImages('<?php echo getthemelocation(); ?>img/button_OK_over.gif')">

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
    // change to version
    if ($actual != "" && $versiondir != "" && checktoken ($token, $user))
    {
      if ($media != "" && preg_match ("/_hcm".$container_id."./i", $actual))
      {
        // create version of actual content file
        $media_v = fileversion ($media);
        $rename_1 = @rename ($versiondir.$contentfile_recent, $versiondir.$media_v);
        // create version of actual media file
        if ($rename_1) $rename_1 = @rename ($mediadir.$media, $mediadir.$media_v);
      }
      else
      {
        // create version of actual content file
        $contentfile_v = fileversion ($contentfile);
        $rename_1 = @rename ($versiondir.$contentfile_recent, $versiondir.$contentfile_v);
      }
      
      // make version actual
      if ($rename_1 != false)
      {
        // load working container from file system even if it is locked
        $result = getcontainername ($contentfile_recent);
        $contentfile_wrk = $result['container'];
        $bufferdata = loadcontainer ($contentfile_wrk, "work", $user);  

        // get current objects
        if ($bufferdata != false) $contentobjects = getcontent ($bufferdata, "<contentobjects>");    

        $bufferdata = false;
        
        // change container version
        $rename_2 = @rename ($versiondir.$actual, $versiondir.$contentfile_recent);
        $copy_2 = @copy ($versiondir.$contentfile_recent, $versiondir.$contentfile_wrk);
        
        // change media file version
        if ($rename_2 != false && $media != "" && @preg_match ("/_hcm".$container_id."./i", $actual))
        {
          $rename_2 = @rename ($mediadir.$actual, $mediadir.$media);          
          // create preview (thumbnail for images, previews for video/audio files)
          createmedia ($site, $mediadir, $mediadir, $media, "", "origthumb");
          // reindex
          indexcontent ($site, $mediadir, $media, $container_id, $bufferdata, $user);
        }
        
        // load working container from file system
        $bufferdata = loadcontainer ($contentfile_id, "work", $user); 

        if ($bufferdata != false) 
        {
          // insert new object into content container
          $bufferdata = setcontent ($bufferdata, "<hyperCMS>", "<contentobjects>", $contentobjects[0], "", "");
            
          // save working container 
          $test = savecontainer ($contentfile_id, "work", $bufferdata, $user);
        }
        else $test = false;         
        
        if ($test == false)
        {
          $errcode = "10100";
          $error[] = $mgmt_config['today']."|version_content.php|error|$errcode|savecontainer failed for container ".$contentfile_wrk;           
        }      

        if ($rename_2 == false || $copy_2 == false) echo "<p class=hcmsHeadline>".getescapedtext ($hcms_lang['could-not-change-version'][$lang])."</p>\n".getescapedtext ($hcms_lang['file-is-missing-or-you-do-not-have-write-permissions'][$lang])."<br /><br />\n";
      }
      else $show = "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['could-not-change-version'][$lang])."</p>\n".getescapedtext ($hcms_lang['file-is-missing-or-you-do-not-have-write-permissions'][$lang])."<br /><br />\n";
    }

    // delete versions
    if (is_array ($delete) && sizeof ($delete) > 0)
    {
      foreach ($delete as $file_v_del)
      {
        if (valid_objectname ($file_v_del))
        {
          // container version
          if (is_file ($versiondir.$file_v_del))
          {
            // delete media file
            $test = deletefile ($versiondir, $file_v_del, 0);

            if ($test == false)
            {
              $errcode = "10291";
              $error[] = $mgmt_config['today']."|version_content.php|error|$errcode|deletefile failed for ".$versiondir.$file_v_del;           
            }
          }
          
          // media file version
          if (is_file ($mediadir.$file_v_del))
          {
            $test = deletefile ($mediadir, $file_v_del, 0);
            
            if ($test == false)
            {
              $errcode = "10292";
              $error[] = $mgmt_config['today']."|version_content.php|error|$errcode|deletefile failed for ".$mediadir.$file_v_del;           
            }
          }
          
          // delete thumbnail file
          $file_info_v = getfileinfo ($site, $file_v_del, $cat);
          $thumb_v_del = $file_info_v['filename'].".thumb.jpg".strrchr ($file_v_del, ".");
            
          if (is_file ($mediadir.$thumb_v_del))
          {
            $test = deletefile ($mediadir, $thumb_v_del, 0);

            if ($test == false)
            {
              $errcode = "10291";
              $error[] = $mgmt_config['today']."|version_content.php|error|$errcode|deletefile failed for ".$versiondir.$thumb_v_del;           
            }
          }
        }
      }
    }

    // select all content version files in directory
    $dir_version = dir ($versiondir);

    while ($entry = $dir_version->read())
    {
      if ($entry != "." && $entry != ".." && is_file ($versiondir.$entry) && (preg_match ("/".$contentfile.".v_/i", $entry) || preg_match ("/_hcm".$container_id."./i", $entry)))
      {
        // get file extension of container version
        $ext_v = substr ($entry, strrpos ($entry, "."));
        $files_v[$ext_v] = $entry;
      }
    }
    
    $dir_version->close();

    if (sizeof ($files_v) > 0)
    {
      ksort ($files_v);
      reset ($files_v);

      $color = false;
      $i = 0;

      foreach ($files_v as $file_v)
      {
        // extract date and time from file extension
        $file_v_ext = substr (strrchr ($file_v, "."), 3);
        $date = substr ($file_v_ext, 0, strpos ($file_v_ext, "_"));
        $time = substr ($file_v_ext, strpos ($file_v_ext, "_") + 1);
        $time = str_replace ("-", ":", $time);
        $date_v = $date." ".$time;
        
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
        
        // get file info
        $file_info_v = getfileinfo ($site, $pagename_v, $cat);

        echo "<tr class=\"".$rowcolor."\">
          <td nowrap=\"nowrap\">".$date_v."</td>
          <td nowrap=\"nowrap\"><a href=\"#\" onClick=\"hcms_openWindow('page_preview.php?site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&container=".url_encode($file_v)."','preview','scrollbars=yes,resizable=yes','800','600')\"><img src=\"".getthemelocation()."img/".$file_info_v['icon']."\" width=16 height=16 border=0 align=\"absmiddle\" />&nbsp; ".$pagename_v."</a></td>
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
  <div style="width:300px; float:left;"><?php echo getescapedtext ($hcms_lang['submit-changes-to-versions'][$lang]); ?> :</div>
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
  
  <div style="width:300px; float:left;"><?php echo getescapedtext ($hcms_lang['compare-selected-versions'][$lang]); ?> :</div>
  <img name="Button2" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="compare_submit();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button2','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" title="OK" alt="OK" />
</form>
</div>

</body>
</html>
