<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
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

// add folder
if ($folder != "") $location = $location.$folder."/";

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
  if ($mgmt_config[$site]['dam'] == true)
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
  
  // convert object name
  $name = convertchars ($file_info['name'], "UTF-8", $charset);

  // media preview
  if (is_array ($object_info) && !empty ($object_info['media']))
  {
    $mediaview = "preview_no_rendering";
    $mediafile = $site."/".$object_info['media'];
    $mediaview = showmedia ($mediafile, $name, $mediaview, "", 290);
  }
  // page or component preview (no multimedia file)
  else
  {
    $mediaview = showobject ($site, $location, $page, $cat, $name);
  }

  if ($mediaview != "") $mediaview = str_replace ("<td>", "<td style=\"width:20%; vertical-align:top;\">", $mediaview);

  // meta data
  $metadata_array = getmetadata ($location, $page, $contentdata, "array", $site."/".$object_info['template']);

  if (is_array ($metadata_array))
  {
    $rows = "";
    
    foreach ($metadata_array as $key => $value)
    {
      $rows .= "<tr><td style=\"width:120px; vertical-align:top;\">".$key."&nbsp;</td><td class=\"hcmsHeadlineTiny\">".$value."</td></tr>\n";
    }
    
    if ($rows != "") $metadata = "<hr /><table>\n".$rows."</table>\n";
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo $charset; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script type="text/javascript" src="javascript/main.js"></script>
<script type="text/javascript" src="javascript/click.js"></script>
<?php if (!empty ($file_info['ext']) && is_audio ($file_info['ext'])) echo showaudioplayer_head (false); ?>
<?php if (!empty ($file_info['ext']) && is_video ($file_info['ext'])) echo showvideoplayer_head (false, false); ?>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- content -->
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
  <div style="width:auto; display:table; margin:16px auto 0px auto;">
  <?php
  if (!empty ($mediaview)) echo $mediaview;
  if (!empty ($metadata)) echo $metadata;
  ?>
  </div>
</div>

</body>
</html>
