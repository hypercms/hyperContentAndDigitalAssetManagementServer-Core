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
// format definitions
require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");


// input parameters
$location = getrequest_esc ("location", "locationname");
$folder = getrequest_esc ("folder", "objectname");
$page = getrequest_esc ("page", "objectname");

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
  if (is_array ($object_info) && $object_info['media'] != "")
  {
    $mediaview = "preview_no_rendering";
    $mediafile = $site."/".$object_info['media'];
    $mediaview = showmedia ($mediafile, $name, $mediaview, "", 288);
  }
  // page or component preview (no multimedia file)
  else
  {
    $mediaview = showobject ($site, $location, $page, $cat, $name);
  }

  if ($mediaview != "") $mediaview = str_replace ("<td>", "<td style=\"width:140px; vertical-align:top;\">", $mediaview);

  // meta data
  $metadata_array = getmetadata ("", "", $contentdata, "array", $site."/".$object_info['template']);

  if (is_array ($metadata_array))
  {
    $rows = "";
    
    foreach ($metadata_array as $key => $value)
    {
      if (trim ($key) != "") $key = $key.":";
      $rows .= "<tr><td>".$key."&nbsp;&nbsp;</td><td class=\"hcmsHeadlineTiny\">".$value."</td></tr>\n";
    }
    
    if ($rows != "") $metadata = "<hr /><table>\n".str_replace ("<td>", "<td style=\"width:140px; vertical-align:top;\">", $rows)."</table>\n";
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<?php if (!empty ($file_info['ext']) && is_audio ($file_info['ext'])) echo showaudioplayer_head (false); ?>
<?php if (!empty ($file_info['ext']) && is_video ($file_info['ext'])) echo showvideoplayer_head (false, false); ?>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php echo showtopbar (getescapedtext ($hcms_lang['preview'][$lang], $charset, $lang), $lang); ?>

<!-- content -->
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
<?php
if (!empty ($mediaview)) echo $mediaview;
if (!empty ($metadata)) echo $metadata;
?>
</div>

</body>
</html>
