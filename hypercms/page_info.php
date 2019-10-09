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
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php echo showtopbar ($hcms_lang['information'][$lang], $lang); ?>

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
        $media_info = rdbms_getmedia ($container_id, true);
        $date_modified = $media_info['date'];
        $fileMD5 = $media_info['md5_hash'];
        $filesize = $media_info['filesize'];
      }
      elseif (is_file ($mediadir.$media))
      {
        $date_modified = date ("Y-m-d H:i:s", filemtime ($mediadir.$media));
        $fileMD5 = md5_file ($mediadir.$media);
        $filesize = filesize ($mediadir.$media) / 1024;
      }

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
   
    if (!empty ($owner)) echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['owner'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".$owner."</td></tr>\n";
    if (!empty ($date_created)) echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['date-created'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".showdate ($date_created, "Y-m-d H:i", $hcms_lang_date[$lang])."</td></tr>\n";
    if (!empty ($date_modified)) echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['date-modified'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".showdate ($date_modified, "Y-m-d H:i", $hcms_lang_date[$lang])."</td></tr>\n";
    if (!empty ($date_published)) echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['published'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".showdate ($date_published, "Y-m-d H:i", $hcms_lang_date[$lang])."</td></tr>\n"; 
 
    // if object will be deleted automatically
    $queue = rdbms_getqueueentries ("delete", "", "", "", $location_esc.$page);

    if (is_array ($queue) && !empty ($queue[0]['date']))
    {
      echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['will-be-removed'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".substr ($queue[0]['date'], 0, -3)."</td></tr>\n";
    }
    
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
  
    // direct link
    if (!empty ($media) && $mgmt_config[$site]['dam'] != true) $filedirectlink = getmedialocation ($site, $media, "url_path_media").$site."/".$media;
  
    // links
    if (!empty ($mgmt_config['publicdownload']) && ($cat == "page" || $setlocalpermission['download'] == 1))
    {
      // wrapper link
      if (!empty ($mgmt_config['db_connect_rdbms'])) $filewrapperlink = createwrapperlink ($site, $location, $page, $cat);
      elseif ($media != "") $filewrapperlink = createviewlink ($site, $media, $page);
      
      // download link  
      if (!empty ($mgmt_config['db_connect_rdbms']))$filewrapperdownload = createdownloadlink ($site, $location, $page, $cat);
      elseif ($media != "") $filewrapperdownload = createviewlink ($site, $media, $page, false, "download");
      
      // object access link
      if (!empty ($mgmt_config['db_connect_rdbms']) && !empty ($mgmt_config[$site]['accesslinkuser'])) $fileaccesslink = createobjectaccesslink ($site, $location, $page, $cat);
    }

    // file links
    if (linking_valid() == false)
    {
      if (!empty ($filedirectlink)) echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['direct-link'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".$filedirectlink."</td></tr>\n";
      if (!empty ($filewrapperlink)) echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['wrapper-link'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".$filewrapperlink."</td></tr>\n";
      if (!empty ($filewrapperdownload)) echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['download-link'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".$filewrapperdownload."</td></tr>\n";
      if (!empty ($fileaccesslink)) echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['access-link'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".$fileaccesslink."</td></tr>\n";
    }
 
    // MD5 Checksum of media file
    if (!empty ($fileMD5)) echo "<tr><td style=\"vertical-align:top\">".getescapedtext ($hcms_lang['md5-code-of-the-file'][$lang])." </td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top\">".$fileMD5."</td></tr>\n";
    
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
      require_once ($mgmt_config['abs_path_cms']."connector/youtube/functions.inc.php");

      // get youtube video ID
      $temp = selectcontent ($contentdata, "<text>", "<text_id>", "Youtube-ID");
      if (!empty ($temp[0])) $temp = getcontent ($temp[0], "<textcontent>");

      if (!empty ($temp[0])) echo "<tr><td style=\"white-space:nowrap;\">Youtube Link </td><td><img name=\"Button6\" src=\"".getthemelocation()."img/button_ok.png\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" onClick=\"hcms_openWindow('".get_youtube_videourl($site, $temp[0])."', '', 'scrollbars=yes,resizable=yes', 640, 640);\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button6','','".getthemelocation()."img/button_ok_over.png',1)\" title=\"OK\" alt=\"OK\" /></td></tr>\n";
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
if (!empty ($template) && $template != false)
{
  // read associated template file
  $result = loadtemplate ($site, $template);
  
  $templatedata = $result['content'];
  
  // get workflow from template
  $hypertag_array = gethypertag ($templatedata, "workflow", 0);
  
  // check if workflow is definded in template or workflow on folder must be applied
  if ($hypertag_array == false || $hypertag_array == "")
  {
    if (file_exists ($mgmt_config['abs_path_data']."workflow_master/".$site.".".$cat.".folder.dat")) $wf_exists = true;
    else $wf_exists = false;
  }
  else $wf_exists = true;  
  
  // collect workflow status information
  if ($wf_exists == true && file_exists ($mgmt_config['abs_path_data']."workflow/".$site."/".$contentfile))
  {
    // load workflow
    $workflow_data = loadfile ($mgmt_config['abs_path_data']."workflow/".$site."/", $contentfile);
    
    // build workflow stages
    $item_array = buildworkflow ($workflow_data);
    
    // count stages (1st dimension)
    $stage_max = sizeof ($item_array); 
    
    // set start stage (stage 0 can only exist if passive items exist)
    if (isset ($item_array[0]) && sizeof ($item_array[0]) >= 1) 
    {
      $stage_start = 1;
      $stage_max = $stage_max - 1;
    }
    else 
    {
      $stage_start = 1;
    }       
    
    echo "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['workflow-status'][$lang])."</p>\n";
    echo "<table class=\"hcmsTableStandard\" style=\"width:90%;\">\n";
    echo "<tr>
    <td class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['member-type'][$lang])."</td>
    <td class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['member'][$lang])."</td>
    <td class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['status'][$lang])."</td>
    <td class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['date'][$lang])."</td>
  </tr>\n"; 
    
    for ($stage=$stage_start; $stage<=$stage_max; $stage++)
    {
      if (is_array ($item_array[$stage]))
      {
        echo "<tr class=\"hcmsRowHead2\"><td colspan=\"4\">".getescapedtext ($hcms_lang['members-on-workflow-stage'][$lang])." ".$stage."</td></tr>\n"; 
        
        foreach ($item_array[$stage] as $item)
        {
          $type_array = getcontent ($item, "<type>"); // unique        
         
          if ($type_array[0] == "user")
          {
            $type = getescapedtext ($hcms_lang['user'][$lang]);
            $member_array = getcontent ($item, "<user>"); // unique
          }
          elseif ($type_array[0] == "usergroup")
          {
            $type = getescapedtext ($hcms_lang['user-group'][$lang]);
            $member_array = getcontent ($item, "<group>"); // unique
          }
          elseif ($type_array[0] == "script") 
          {
            $type = getescapedtext ($hcms_lang['robot-script'][$lang]);
            $member_array[0] = "-";
          }                      
          
          $passed_array = getcontent ($item, "<passed>"); // unique
          
          if ($passed_array[0] == 1) $passed = getescapedtext ($hcms_lang['accepted'][$lang]);
          else $passed = getescapedtext ($hcms_lang['pendingrejected'][$lang]);
          
          $date_array = getcontent ($item, "<date>"); // unique  
        
          echo "<tr class=\"hcmsRowData1\">
      <td style=\"width:25%;\">".$type."</td>
      <td style=\"width:25%;\">".$member_array[0]."</td>
      <td style=\"width:25%;\">".$passed."</td>
      <td>".$date_array[0]."</td>
    </tr>\n"; 
        }
      }
    }
    
    echo "</table>\n";
  }
}  
?>
</div>

</body>
</html>
