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
if ($cat == "" || $setlocalpermission['root'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

$owner = "";
$date_modified = "";
$date_created = "";
$date_published = "";
$filedirectlink = "";
$filewrapperlink = "";
$filewrapperdownload = "";
$filesize = 0;
$filecount = 0;
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- content -->
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
<?php
// check and correct file
$page = correctfile ($location, $page, $user);
  
// load page and read actual file info (to get associated template and content)
$pagestore = loadfile ($location, $page);

if ($pagestore != false)
{
  // get template
  $template = getfilename ($pagestore, "template");
  
  // get media
  $media = getfilename ($pagestore, "media");
  
  // get container
  $contentfile = getfilename ($pagestore, "content");
  
  echo "<table class=\"hcmsTableStandard\">\n";

  if ($contentfile != "" && $template != "")
  {
    // locked by user
    $usedby_array = getcontainername ($contentfile);

    if (is_array ($usedby_array)) $usedby = $usedby_array['user'];
    else $usedby = "";
  
    // read associated content file
    $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml"));

    // load container
    if (!empty ($usedby) && $usedby != $user)
    {
      $contentdata = loadcontainer ($contentfile.".wrk.@".$usedby, "version", $usedby); 
    }
    else $contentdata = loadcontainer ($container_id, "work", $user); 

    // get dates and owner
    $container_info = getmetadata_container ($container_id, array("date", "createdate", "publishdate", "user"));
    
    if (is_array ($container_info))
    {
      $owner = $container_info['user'];
      $date_created = $container_info['createdate'];
      $date_modified = $container_info['date'];
      $date_published = $container_info['publishdate'];
    }
    // use content container
    else
    {
      $temp = getcontent ($contentdata, "<contentuser>");
      if (!empty ($temp[0])) $owner = $temp[0];
      $temp = getcontent ($contentdata, "<contentcreated>");
      if (!empty ($temp[0])) $date_created = $temp[0];
      $temp = getcontent ($contentdata, "<contentdate>");
      if (!empty ($temp[0])) $date_modified = $temp[0];
      $temp = getcontent ($contentdata, "<contentpublished>");
      if (!empty ($temp[0])) $date_published = $temp[0];
    }

    // ---------------------------- page info ---------------------------
    
    // get file information for
    // multimedia objects
    if (!empty ($media))
    {
      $mediadir = getmedialocation ($site, $media, "abs_path_media").$site."/";

      if (!empty ($mgmt_config['db_connect_rdbms']))
      {
        $media_info = rdbms_getmedia ($container_id);
        $date_modified = $media_info['date'];
        // don't use saved MD5 hash since it is not updated in order to support the search for duplicates
        // $fileMD5 = $media_info['md5_hash'];
        $filesize = $media_info['filesize'];
      }
      elseif (is_file ($mediadir.$media))
      {
        $date_modified = date ("Y-m-d H:i:s", filemtime ($mediadir.$media));
        $filesize = filesize ($mediadir.$media) / 1024;
      }

      // get actual MD5 hash of file
      $fileMD5 = md5_file ($mediadir.$media);

      // symbolic link
      if (is_link ($mediadir.$media))
      {
        $is_link = true;
        $symlink = readlink ($mediadir.$media);
      }
    }
    // folder objects
    elseif ($page == ".folder")
    {
      // multimedia and standard objects
      $filesize_array = getfilesize ($location_esc.$page);

      if (is_array ($filesize_array))
      {
        $filesize = $filesize_array['filesize'];
        $filecount = $filesize_array['count'];
      }
    } 
    // standard objects   
    elseif ($page != "")
    {
      $filesize = filesize ($location.$page) / 1024;
    }

    // if object will be deleted automatically
    $queue = rdbms_getqueueentries ("delete", "", "", "", $location_esc.$page);
    if (is_array ($queue) && !empty ($queue[0]['date'])) $date_delete = $queue[0]['date'];
   
    echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['owner'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".(!empty ($owner) ? $owner : "")."</td></tr>\n";
    echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['date-created'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".(!empty ($date_created) && is_date ($date_created) ? showdate ($date_created, "Y-m-d H:i", $hcms_lang_date[$lang]) : "")."</td></tr>\n";
    echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['date-modified'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".(!empty ($date_modified) && is_date ($date_modified) ? showdate ($date_modified, "Y-m-d H:i", $hcms_lang_date[$lang]) : "")."</td></tr>\n";
    echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['published'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".(!empty ($date_published) && is_date ($date_published) ? showdate ($date_published, "Y-m-d H:i", $hcms_lang_date[$lang]) : "")."</td></tr>\n"; 
    echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['will-be-removed'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".(!empty ($date_delete) && is_date ($date_delete) ? showdate ($date_delete, "Y-m-d H:i", $hcms_lang_date[$lang]) : "")."</td></tr>\n";
    
    // container
    if (!empty ($contentfile))
    {
      echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['container-id'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".$container_id."</td></tr>\n";    
    }
  
    if (!empty ($template))
    {
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
  
      echo "<tr><td style=\"vertical-align:top\">".$pagecomp." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".$tpl_name."</td></tr>\n";
    }
    
    // file size    
    $filesize = round ($filesize, 0);
    if ($filesize < 1) $filesize = 1;
    
    if ($filesize > 1000)
    {
      $filesize = $filesize / 1024;
      $unit = "MB";
    }
    else $unit = "KB";
    
    $filesize = number_format ($filesize, 0, ".", " ")." ".$unit;
    
    $filecount = number_format ($filecount, 0, ".", " ");
  
    if ($filesize > 0) echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['file-size'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".$filesize."</td></tr>\n";
    if ($filecount > 0) echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['number-of-files'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".$filecount."</td></tr>\n";

    // file links (only if linking is not used)
    if (linking_valid() == false)
    {
      // direct link
      if (!empty ($media) && empty ($mgmt_config[$site]['dam'])) $filedirectlink = getmedialocation ($site, $media, "url_path_media").$site."/".$media;
    
      // links
      if (!empty ($mgmt_config['publicdownload']))
      {
        // wrapper link
        if ($cat == "page" || $setlocalpermission['download'] == 1)
        {
          if (!empty ($mgmt_config['db_connect_rdbms'])) $filewrapperlink = createwrapperlink ($site, $location, $page, $cat, "", "", "", "", true);
          elseif (!empty ($media)) $filewrapperlink = createviewlink ($site, $media, $page);
        }
        
        // download link
        if ($cat == "comp" && $setlocalpermission['download'] == 1)
        {
          if (!empty ($mgmt_config['db_connect_rdbms']))$filewrapperdownload = createdownloadlink ($site, $location, $page, $cat, "", "", "", "", true);
          elseif (!empty ($media)) $filewrapperdownload = createviewlink ($site, $media, $page, false, "download");
        }
        
        // object access link
        if ($cat == "page" || $setlocalpermission['download'] == 1)
        {
          if (!empty ($mgmt_config['db_connect_rdbms']) && !empty ($mgmt_config[$site]['accesslinkuser'])) $fileaccesslink = createobjectaccesslink ($site, $location, $page, $cat, "", "", true);
        }
      }

      if (!empty ($filedirectlink)) echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['direct-link'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".$filedirectlink."</td></tr>\n";
      if (!empty ($filewrapperlink)) echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['wrapper-link'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".$filewrapperlink."</td></tr>\n";
      if (!empty ($filewrapperdownload)) echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['download-link'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".$filewrapperdownload."</td></tr>\n";
      if (!empty ($fileaccesslink)) echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['access-link'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".$fileaccesslink."</td></tr>\n";
    }
 
    // MD5 Checksum of media file
    if (!empty ($fileMD5)) echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['md5-hash-of-the-file'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".$fileMD5."</td></tr>\n";
    
    // symbolic link to external media file
    if (!empty ($is_link)) echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['multimedia-file'][$lang]." - ".$hcms_lang['export'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".(!empty ($symlink) ? $symlink : getescapedtext ($hcms_lang['error'][$lang]))."</td></tr>\n";
    
    // show connected objects button
    if ($cat == "comp" && !empty ($mgmt_config[$site]['linkengine']))
    {
      echo "<tr><td style=\"white-space:nowrap;\">".getescapedtext ($hcms_lang['show-where-used'][$lang])." </td><td><img name=\"Button1\" src=\"".getthemelocation()."img/button_ok.png\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" onClick=\"location='page_info_inclusions.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."';\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button1','','".getthemelocation()."img/button_ok_over.png',1)\" title=\"OK\" alt=\"OK\" /></td></tr>\n";
    }
    
    // show container usage button
    if (!empty ($contentfile))
    {
      echo "<tr><td style=\"white-space:nowrap;\">".getescapedtext ($hcms_lang['container-usage'][$lang])." </td><td><img name=\"Button2\" src=\"".getthemelocation()."img/button_ok.png\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" onClick=\"location='page_info_container.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."';\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button2','','".getthemelocation()."img/button_ok_over.png',1)\" title=\"OK\" alt=\"OK\" /></td></tr>\n";
    }
    
    // show access statistics button
    if ((!empty ($media) || $page == ".folder") && !empty ($mgmt_config['db_connect_rdbms']) && !empty ($container_id))
    {
      echo "<tr><td style=\"white-space:nowrap;\">".getescapedtext ($hcms_lang['access-statistics'][$lang])." </td><td><img name=\"Button3\" src=\"".getthemelocation()."img/button_ok.png\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" onClick=\"location='page_info_stats.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."';\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button3','','".getthemelocation()."img/button_ok_over.png',1)\" title=\"OK\" alt=\"OK\" /></td></tr>\n";
    }

    // show youtube statistics button (requires youtube connector)
    if ($cat == "comp" && is_dir ($mgmt_config['abs_path_cms']."connector/youtube"))
    {
      // YouTube functions
      require_once ($mgmt_config['abs_path_cms']."connector/youtube/youtube_api.inc.php");

      // get youtube video ID
      $temp = selectcontent ($contentdata, "<text>", "<text_id>", "Youtube-ID");
      if (!empty ($temp[0])) $temp = getcontent ($temp[0], "<textcontent>");

      if (!empty ($temp[0])) echo "<tr><td style=\"white-space:nowrap;\">Youtube Link </td><td><img name=\"Button6\" src=\"".getthemelocation()."img/button_ok.png\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" onClick=\"hcms_openWindow('".get_youtube_videourl($site, $temp[0])."', '', 'location=yes,menubar=yes,toolbar=yes,titlebar=yes,scrollbars=yes,resizable=yes', 640, 640);\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button6','','".getthemelocation()."img/button_ok_over.png',1)\" title=\"OK\" alt=\"OK\" /></td></tr>\n";
    }
    
    // show meta information button
    if ($cat == "comp" && !empty ($media))
    {
      echo "<tr><td style=\"white-space:nowrap;\">".getescapedtext ($hcms_lang['meta-information'][$lang])." </td><td><img name=\"Button4\" src=\"".getthemelocation()."img/button_ok.png\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" onClick=\"location='page_info_metadata.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."';\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button4','','".getthemelocation()."img/button_ok_over.png',1)\" title=\"OK\" alt=\"OK\" /></td></tr>\n";
    }
    
    // show recipients button
    if (!empty ($mgmt_config['db_connect_rdbms']))
    {
      echo "<tr><td style=\"white-space:nowrap;\">".getescapedtext ($hcms_lang['recipients'][$lang])." </td><td><img name=\"Button5\" src=\"".getthemelocation()."img/button_ok.png\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" onClick=\"location='page_info_recipients.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."';\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button5','','".getthemelocation()."img/button_ok_over.png',1)\" title=\"OK\" alt=\"OK\" /></td></tr>\n";
    }
  }
  else
  {
    echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['modified'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".date ("Y-m-d H:i", filemtime ($location.$page))."</td></tr>\n";
    echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['file-size'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".filesize ($location.$page)." bytes</td></tr>\n";
  }
  
  echo "</table>\n";
}

// -------------------------- workflow status ------------------------------
echo showworkflowstatus ($site, $location, $page); 
?>
</div>

<?php includefooter(); ?>
</body>
</html>