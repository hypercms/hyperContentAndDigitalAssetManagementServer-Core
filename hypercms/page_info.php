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

$filedirectlink = "";
$filewrapperlink = "";
$filewrapperdownload = "";
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
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
  
  echo "<table border=0 cellspacing=4 cellpadding=0>\n";

  if ($contentfile != "" && $template != "")
  {
    // locked by user
    $usedby_array = getcontainername ($contentfile);

    if (is_array ($usedby_array)) $usedby = $usedby_array['user'];
    else $usedby = "";
  
    // read associated content file
    $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml")); 
    
    if (!empty ($usedby) && $usedby != $user)
    {
      $contentdata = loadcontainer ($contentfile.".wrk.@".$usedby, "version", $usedby); 
    }
    else $contentdata = loadcontainer ($container_id, "work", $user); 
  
    $owner = getcontent ($contentdata, "<contentuser>");
    $last_updated = getcontent ($contentdata, "<contentdate>");
    $last_published = getcontent ($contentdata, "<contentpublished>");
      
    // ---------------------------- page info ---------------------------
    
    if (!empty ($contentfile))
    {
      // get file time
      if ($media != "") $last_updated[0] = date ("Y-m-d H:i", @filemtime (getmedialocation ($site, $media, "abs_path_media").$site."/".$media));   
                
      echo "<tr><td valign=top>".getescapedtext ($hcms_lang['owner'][$lang]).": </td><td class=\"hcmsHeadlineTiny\" valign=top>".$owner[0]."</td></tr>\n";
      echo "<tr><td valign=top>".getescapedtext ($hcms_lang['modified'][$lang]).": </td><td class=\"hcmsHeadlineTiny\" valign=top>".$last_updated[0]."</td></tr>\n";
      echo "<tr><td valign=top>".getescapedtext ($hcms_lang['published'][$lang]).": </td><td class=\"hcmsHeadlineTiny\" valign=top>".$last_published[0]."</td></tr>\n"; 
    }
 
    // if object will be deleted automatically
    $queue = rdbms_getqueueentries ("delete", "", "", "", $location_esc.$page);

    if (is_array ($queue) && !empty ($queue[0]['date']))
    {
      echo "<tr><td valign=\"top\">".getescapedtext ($hcms_lang['will-be-removed'][$lang]).": </td><td class=\"hcmsHeadlineTiny\" valign=\"top\">".substr ($queue[0]['date'], 0, -3)."</td></tr>\n";
    }
    
    // container
    if (!empty ($contentfile))
    {
      echo "<tr><td valign=top>".getescapedtext ($hcms_lang['container-id'][$lang]).": </td><td class=\"hcmsHeadlineTiny\" valign=top>".$container_id."</td></tr>\n";    
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
  
      echo "<tr><td valign=top>".$pagecomp.": </td><td class=\"hcmsHeadlineTiny\" valign=top>".$tpl_name."</td></tr>\n";
    }
    
    // file size
    $filesize = 0;
    $filecount = 0;
    
    // get file size in kB for:
    // multimedia objects
    if ($media != false && !empty ($media))
    {
      if ($mgmt_config['db_connect_rdbms'] != "")
      {
        $media_info = rdbms_getmedia ($container_id);
        $fileMD5 = $media_info['md5_hash'];
        $filesize = $media_info['filesize'];
      }
      else
      {
        $mediadir = getmedialocation ($site, $media, "abs_path_media");          
        $fileMD5 = md5_file ($mediadir.$site."/".$media);
        $filesize = filesize ($mediadir.$site."/".$media) / 1024;
      }
     
      // direct link
      if ($mgmt_config[$site]['dam'] != true) $filedirectlink = getmedialocation ($site, $media, "url_path_media").$site."/".$media;
    }
    // folders objects
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
    
    $filesize = round ($filesize, 0);
    if ($filesize < 1) $filesize = 1;
    
    if ($filesize > 1000)
    {
      $filesize = $filesize / 1024;
      $unit = "MB";
    }
    else $unit = "KB";
    
    $filesize = number_format ($filesize, 0, "", ".")." ".$unit;
    
    $filecount = number_format ($filecount, 0, "", ".");
  
    if ($filesize > 0) echo "<tr><td valign=\"top\">".getescapedtext ($hcms_lang['file-size'][$lang]).": </td><td class=\"hcmsHeadlineTiny\" valign=\"top\">".$filesize."</td></tr>\n";
    if ($filecount > 1) echo "<tr><td valign=\"top\">".getescapedtext ($hcms_lang['number-of-files'][$lang]).": </td><td class=\"hcmsHeadlineTiny\" valign=\"top\">".$filecount."</td></tr>\n";
  
    // links
    if ($mgmt_config['publicdownload'] == true && ($cat == "page" || $setlocalpermission['download'] == 1))
    {
      // wrapper link
      if ($mgmt_config['db_connect_rdbms'] != "") $filewrapperlink = createwrapperlink ($site, $location, $page, $cat);
      elseif ($media != "") $filewrapperlink = $mgmt_config['url_path_cms']."explorer_wrapper.php?media=".url_encode($site."/".$media)."&token=".hcms_crypt($site."/".$media);
      // download link  
      if ($media != "")
      {
        if ($mgmt_config['db_connect_rdbms'] != "") $filewrapperdownload = createdownloadlink ($site, $location, $page, $cat);
        else $filewrapperdownload = $mgmt_config['url_path_cms']."explorer_download.php?media=".url_encode($site."/".$media)."&name=".url_encode($page)."&token=".hcms_crypt($site."/".$media);
      }
    }
    
    // file access links
    if (!empty ($filedirectlink)) echo "<tr><td valign=\"top\">".getescapedtext ($hcms_lang['direct-link'][$lang]).": </td><td class=\"hcmsHeadlineTiny\" valign=\"top\">".$filedirectlink."</td></tr>\n";
    if (!empty ($filewrapperlink)) echo "<tr><td valign=\"top\">".getescapedtext ($hcms_lang['wrapper-link'][$lang]).": </td><td class=\"hcmsHeadlineTiny\" valign=\"top\">".$filewrapperlink."</td></tr>\n";
    if (!empty ($filewrapperdownload)) echo "<tr><td valign=\"top\">".getescapedtext ($hcms_lang['download-link'][$lang]).": </td><td class=\"hcmsHeadlineTiny\" valign=\"top\">".$filewrapperdownload."</td></tr>\n";
    
    // MD5 Checksum of media file
    if (!empty ($fileMD5)) echo "<tr><td valign=\"top\">".getescapedtext ($hcms_lang['md5-code-of-the-file'][$lang]).": </td><td class=\"hcmsHeadlineTiny\" valign=\"top\">".$fileMD5."</td></tr>\n";
    
    // show connected objects button
    if ($cat == "comp" && $mgmt_config[$site]['linkengine'] == true)
    {
      echo "<tr><td nowrap=\"nowrap\">".getescapedtext ($hcms_lang['show-where-used'][$lang]).": </td><td><img name=\"Button1\" src=\"".getthemelocation()."img/button_OK.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" onClick=\"location.href='page_info_inclusions.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."';\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button1','','".getthemelocation()."img/button_OK_over.gif',1)\" align=\"absmiddle\" title=\"OK\" alt=\"OK\" /></td></tr>\n";
    }
    
    // show container usage button
    if (!empty ($contentfile))
    {
      echo "<tr><td nowrap=\"nowrap\">".getescapedtext ($hcms_lang['container-usage'][$lang]).": </td><td><img name=\"Button2\" src=\"".getthemelocation()."img/button_OK.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" onClick=\"location.href='page_info_container.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."';\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button2','','".getthemelocation()."img/button_OK_over.gif',1)\" align=\"absmiddle\" title=\"OK\" alt=\"OK\" /></td></tr>\n";
    }
    
    // show statistics button
    if ($cat == "comp" && $mgmt_config['db_connect_rdbms'] != "" && !empty ($container_id))
    {
      echo "<tr><td nowrap=\"nowrap\">".getescapedtext ($hcms_lang['access-statistics'][$lang]).": </td><td><img name=\"Button3\" src=\"".getthemelocation()."img/button_OK.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" onClick=\"location.href='page_info_stats.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."';\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button3','','".getthemelocation()."img/button_OK_over.gif',1)\" align=\"absmiddle\" title=\"OK\" alt=\"OK\" /></td></tr>\n";
    }
    
    // show youtube statistics button (requires youtube connector)
    if ($cat == "comp" && is_dir ($mgmt_config['abs_path_cms']."connector/socialmedia/youtube") && !empty ($mgmt_config[$site]['youtube_token']))
    {
      // YouTube functions
      require ("connector/socialmedia/youtube/functions.inc.php");

      // get youtube video ID
      $temp = selectcontent ($contentdata, "<text>", "<text_id>", "Youtube-ID");
      if (!empty ($temp[0])) $temp = getcontent ($temp[0], "<textcontent>");
    
      if (!empty ($temp[0])) echo "<tr><td nowrap=\"nowrap\">Youtube Link: </td><td><img name=\"Button6\" src=\"".getthemelocation()."img/button_OK.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" onClick=\"hcms_openWindow('".get_youtube_videourl ($site, $temp[0])."', '', 'scrollbars=yes,resizable=yes', 640, 640);\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button6','','".getthemelocation()."img/button_OK_over.gif',1)\" align=\"absmiddle\" title=\"OK\" alt=\"OK\" /></td></tr>\n";
    }
    
    // show meta information button
    if ($cat == "comp" && !empty ($media))
    {
      echo "<tr><td nowrap=\"nowrap\">".getescapedtext ($hcms_lang['meta-information'][$lang]).": </td><td><img name=\"Button4\" src=\"".getthemelocation()."img/button_OK.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" onClick=\"location.href='page_info_metadata.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."';\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button4','','".getthemelocation()."img/button_OK_over.gif',1)\" align=\"absmiddle\" title=\"OK\" alt=\"OK\" /></td></tr>\n";
    }
    
    // show recipients button
    if ($cat == "comp" && !empty ($mgmt_config['db_connect_rdbms']))
    {
      echo "<tr><td nowrap=\"nowrap\">".getescapedtext ($hcms_lang['recipients'][$lang]).": </td><td><img name=\"Button5\" src=\"".getthemelocation()."img/button_OK.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" onClick=\"location.href='page_info_recipients.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."';\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button5','','".getthemelocation()."img/button_OK_over.gif',1)\" align=\"absmiddle\" title=\"OK\" alt=\"OK\" /></td></tr>\n";
    }
  }
  else
  {
    echo "<tr><td valign=\"top\">".getescapedtext ($hcms_lang['modified'][$lang]).": </td><td class=\"hcmsHeadlineTiny\" valign=\"top\">".date ("Y-m-d H:i", filemtime ($location.$page))."</td></tr>\n";
    echo "<tr><td valign=\"top\">".getescapedtext ($hcms_lang['file-size'][$lang]).": </td><td class=\"hcmsHeadlineTiny\" valign=\"top\">".filesize ($location.$page)." bytes</td></tr>\n";
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
    echo "<table border=0 cellspacing=2 cellpadding=3 width=\"90%\">\n";
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
      <td width=\"25%\">".$type."</td>
      <td width=\"25%\">".$member_array[0]."</td>
      <td width=\"25%\">".$passed."</td>
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
