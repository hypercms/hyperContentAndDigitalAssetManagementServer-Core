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
// format definitions
require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");


// input parameters
$location = getrequest_esc ("location", "locationname");
$folder = getrequest_esc ("folder", "objectname");
$page = getrequest_esc ("page", "objectname");

// location and object is set by assetbrowser
if ($location == "" && !empty ($hcms_assetbrowser_location) && !empty ($hcms_assetbrowser_object))
{
  $location = $hcms_assetbrowser_location;
  $page = $hcms_assetbrowser_object;
}

// add slash if not present at the end of the location string
$location = correctpath ($location);

// add folder
if ($folder != "")
{
  $location = $location.$folder."/";
  $page = ".folder";
}

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// set dafault character set if no object is provided
$charset = $hcms_lang_codepage[$lang];

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && is_file ($location.$page))
{
  // ------------------------------ permission section --------------------------------

  // check access permissions (DAM)
  if (!empty ($mgmt_config[$site]['dam']))
  {
    $ownergroup = accesspermission ($site, $location, $cat);
    $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);
    if ($setlocalpermission['root'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);
  }
  // check permissions
  else
  {
    if (($cat != "page" && $cat != "comp") || ($cat == "comp" && !checkglobalpermission ($site, 'component')) || ($cat == "page" && !checkglobalpermission ($site, 'page')) || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);
  }

  // check session of user
  checkusersession ($user);
  
  // --------------------------------- logic section ----------------------------------
  
  // write and close session (non-blocking other frames)
  if (session_id() != "") session_write_close();

  $file_info = getfileinfo ($site, $location.$page, $cat);
  $object_info = getobjectinfo ($site, $location, $page, $user);

  // load container
  $contentdata = loadcontainer ($object_info['content'], "work", "sys");
  
  // get character set and content-type
  $charset_array = getcharset ($site, $contentdata);
  
  // set character set
  if (!empty ($charset_array['charset'])) $charset = $charset_array['charset'];
  else $charset = $mgmt_config[$site]['default_codepage'];
  
  $hcms_charset = $charset;

  if (!empty ($charset)) header ('Content-Type: text/html; charset='.$charset);
  
  // convert object name
  $name = convertchars ($file_info['name'], "UTF-8", $charset);

  // --------------------------------- statistics --------------------------------- 
  $container_id = $object_info['container_id'];

  if (!empty ($container_id))
  {
    $views = rdbms_externalquery ('SELECT SUM(dailystat.count) AS count FROM dailystat WHERE id='.intval($container_id).' AND activity="view"');
    $downloads = rdbms_externalquery ('SELECT SUM(dailystat.count) AS count FROM dailystat WHERE id='.intval($container_id).' AND activity="download"');
    $uploads = rdbms_externalquery ('SELECT SUM(dailystat.count) AS count FROM dailystat WHERE id='.intval($container_id).' AND activity="upload"');

    if (empty ($views[0]['count'])) $views[0]['count'] = 0;
    if (empty ($downloads[0]['count'])) $downloads[0]['count'] = 0;
    if (empty ($uploads[0]['count'])) $uploads[0]['count'] = 0;

    $stats = "
    <div class=\"hcmsTextSmall\" style=\"white-space:nowrap;\">
      <img src=\"".getthemelocation()."img/button_file_liveview.png\" class=\"hcmsIconList\" /> ".$views[0]['count']." ".getescapedtext ($hcms_lang['views'][$lang], $charset, $lang)."
      &nbsp;&nbsp;<img src=\"".getthemelocation()."img/button_file_download.png\" class=\"hcmsIconList\" /> ".$downloads[0]['count']." ".getescapedtext ($hcms_lang['downloads'][$lang], $charset, $lang)."
      &nbsp;&nbsp;<img src=\"".getthemelocation()."img/button_file_upload.png\" class=\"hcmsIconList\" /> ".$uploads[0]['count']." ".getescapedtext ($hcms_lang['uploads'][$lang], $charset, $lang)."
    </div>";
  }

  // ----------------------------------- workflow -----------------------------------
  if (!empty ($container_id))
  {
    // workflow status
    $workflow = rdbms_getworkflow ($container_id);

    if (!empty ($workflow) && strpos ($workflow['workflowstatus'], "/") > 0)
    {
      list ($workflow_stage, $workflow_maxstage) = explode ("/", $workflow['workflowstatus']);

      if (intval ($workflow_stage) == intval ($workflow_maxstage)) $workflow_status = "passed";
      elseif (intval ($workflow_stage) < intval ($workflow_maxstage)) $workflow_status = "inprogress";

      // workflow icon image
      if ($workflow_status == "passed")
      {
        $workflow_icon = "<img src=\"".getthemelocation()."img/workflow_accept.png\" class=\"hcmsIconList\" alt=\"".getescapedtext ($hcms_lang['finished'][$lang], $charset, $lang)."\" /> ".getescapedtext ($hcms_lang['finished'][$lang], $charset, $lang)." ".showdate ($workflow['workflowdate'], "Y-m-d H:i", $hcms_lang_date[$lang])." ".getescapedtext ($hcms_lang['by-user'][$lang], $charset, $lang)." ".$workflow['workflowuser'];
      }
      elseif ($workflow_status == "inprogress")
      {
        $workflow_icon = "<img src=\"".getthemelocation()."img/workflow_inprogress.png\" class=\"hcmsIconList\" alt=\"".getescapedtext ($hcms_lang['in-progress'][$lang], $charset, $lang)."\" /> ".getescapedtext ($hcms_lang['in-progress'][$lang], $charset, $lang)." ".showdate ($workflow['workflowdate'], "Y-m-d H:i", $hcms_lang_date[$lang])." ".getescapedtext ($hcms_lang['by-user'][$lang], $charset, $lang)." ".$workflow['workflowuser'];
      }
      else $workflow_icon = "";

      $workflow = "
      <div class=\"hcmsTextSmall\" style=\"white-space:nowrap;\">
        <img src=\"".getthemelocation()."img/workflow.png\" class=\"hcmsIconList\" title=\"".getescapedtext ($hcms_lang['workflow'][$lang], $charset, $lang)."\" alt=\"".getescapedtext ($hcms_lang['workflow'][$lang], $charset, $lang)."\" /> ".$workflow_icon."
      </div>";
    }
  }

  // --------------------------------- media preview --------------------------------- 
  if (is_array ($object_info) && !empty ($object_info['media']))
  {
    $mediaview = "preview_no_rendering";
    $mediafile = $object_info['media'];
    $mediaview = showmedia ($site."/".$mediafile, $name, $mediaview, "", 320);
  }
  // page or component preview (no multimedia file)
  else
  {
    $mediaview = showobject ($site, $location, $page, $cat, $name);
  }

  if ($mediaview != "") $mediaview = str_replace ("<td>", "<td style=\"width:120px; vertical-align:top;\">", $mediaview);

  //--------------------------------- meta data --------------------------------- 
  $metadata_array = getmetadata ($location, $page, $contentdata, "array", $site."/".$object_info['template']);

  if (is_array ($metadata_array))
  {
    $rows = "";
    
    foreach ($metadata_array as $key => $value)
    {
      // don't display base64 encoded images
      if (strpos ($key, ":image") < 1 && strpos ("_".$value, "image/gif;base64") < 1 && strpos ("_".$value, "image/png;base64") < 1 && strpos ("_".$value, "image/jpeg;base64") < 1 &&  strpos ("_".$value, "image/svg;base64") < 1 && strpos ("_".$value, "image/webp;base64") < 1)
      {
        $rows .= "
      <tr>
        <td style=\"width:120px; vertical-align:top;\">".$key."&nbsp;</td><td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top;\">".showshorttext ($value, 280)."</td>
      </tr>";
      }
      
      if ($rows != "") $metadata = "
      <hr />
      <table class=\"hcmsTableNarrow\">
      ".$rows."
      </table>
      ";
    }
  }

  // --------------------------------- connected copies --------------------------------- 
  $temp_array = rdbms_getobjects ($container_id);

  if (is_array ($temp_array) && sizeof ($temp_array) > 1) 
  {
    $connected_copy = "<a href=\"javascript:void(0);\" onclick=\"parent.openPopup('page_info_container.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&from_page=objectlist');\"><span class=\"hcmsHeadlineTiny\">&gt; ".getescapedtext ($hcms_lang['show-where-used'][$lang])."</span></a>";

    $connectedview = "
    <hr />
    <table class=\"hcmsTableNarrow\">
      <tr>
        <td style=\"width:120px; vertical-align:top;\">".getescapedtext ($hcms_lang['connected-copy'][$lang])."&nbsp;</td><td style=\"vertical-align:top;\">".$connected_copy."</td>
      </tr>
    </table>";
  }

  //--------------------------------- related assets (only childs) --------------------------------- 
  if (!empty ($mgmt_config['relation_source_id']))
  {
    // read content from content container
    $temp_array = selectcontent ($contentdata, "<component>", "<component_id>", $mgmt_config['relation_source_id']);

    if (!empty ($temp_array[0]))
    {
      $temp_array = getcontent ($temp_array[0], "<componentfiles>");

      // convert object ID to object path
      if (!empty ($temp_array[0]))
      {
        $components = getobjectlink ($temp_array[0]);
        $components_array = explode ("|", trim ($components, "|"));
      }

      // gallery
      if (!empty ($components))
      {
        $relatedview = "
      <hr />
      <table class=\"hcmsTableNarrow\">
        <tr>
          <td>".getescapedtext ($hcms_lang['related-assets'][$lang])."</td>
        </tr>
      </table><br/>
      ".showgallery ($components_array, 92, true, $user);
      }
    }
  }

  //---------------------------------  file links (only if linking is not used and public download is enabled) --------------------------------- 
  if (empty ($file_info['deleted']) && linking_valid() == false && !empty ($mgmt_config['publicdownload']))
  {
    // wrapper link
    if ($cat == "page" || (!empty ($setlocalpermission['download']) && $setlocalpermission['download'] == 1))
    {
      if (!empty ($mgmt_config['db_connect_rdbms'])) $filewrapperlink = createwrapperlink ($site, $location, $page, $cat);
      elseif (!empty ($mediafile)) $filewrapperlink = createviewlink ($site, $mediafile, $page);
    }
    
    // download link
    if ($cat == "comp" && !empty ($setlocalpermission['download']) && $setlocalpermission['download'] == 1)
    {
      if (!empty ($mgmt_config['db_connect_rdbms'])) $filewrapperdownload = createdownloadlink ($site, $location, $page, $cat);
      elseif (!empty ($mediafile)) $filewrapperdownload = createviewlink ($site, $mediafile, $page, false, "download");
    }
    
    // object access link
    if ($cat == "page" || (!empty ($setlocalpermission['download']) && $setlocalpermission['download'] == 1))
    {
      if (!empty ($mgmt_config['db_connect_rdbms']) && !empty ($mgmt_config[$site]['accesslinkuser'])) $fileaccesslink = createobjectaccesslink ($site, $location, $page, $cat);
    }

    if (!empty ($filedirectlink) || !empty ($filewrapperlink) || !empty ($filewrapperdownload) || !empty ($fileaccesslink))
    {
      $linksview = "
      <hr />
      <table class=\"hcmsTableNarrow\">";

      if (!empty ($filedirectlink)) $linksview .= "<tr><td>".getescapedtext ($hcms_lang['direct-link'][$lang])." <br/><span class=\"hcmsHeadlineTiny\">".$filedirectlink."</span></td></tr>\n";
      if (!empty ($filewrapperlink)) $linksview .= "<tr><td>".getescapedtext ($hcms_lang['wrapper-link'][$lang])." <br/><span class=\"hcmsHeadlineTiny\">".$filewrapperlink."</span></td></tr>\n";
      if (!empty ($filewrapperdownload)) $linksview .= "<tr><td>".getescapedtext ($hcms_lang['download-link'][$lang])." <br/><span class=\"hcmsHeadlineTiny\">".$filewrapperdownload."</span></td></tr>\n";
      if (!empty ($fileaccesslink)) $linksview .= "<tr><td>".getescapedtext ($hcms_lang['access-link'][$lang])." </br><span class=\"hcmsHeadlineTiny\">".$fileaccesslink."</span></td></tr>\n";

      $linksview .= "
      </table>";
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo $charset; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<?php if (!empty ($file_info['ext']) && is_audio ($file_info['ext'])) echo showaudioplayer_head (false, true); ?>
<?php if (!empty ($file_info['ext']) && is_video ($file_info['ext'])) echo showvideoplayer_head (false, false, true); ?>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- content -->
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
  <?php if (!empty ($stats)) echo $stats; ?>
  <?php if (!empty ($workflow)) echo $workflow; ?>
  <div style="margin:16px auto 0px auto;">
  <?php
  if (!empty ($mediaview)) echo $mediaview;
  if (!empty ($metadata)) echo $metadata;
  if (!empty ($linksview)) echo $linksview;
  if (!empty ($connectedview)) echo $connectedview;
  if (!empty ($relatedview)) echo $relatedview;
  ?>
  </div>
</div>

</body>
</html>
