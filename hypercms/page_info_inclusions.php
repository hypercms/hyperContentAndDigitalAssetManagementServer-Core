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
if ($ownergroup == false || $setlocalpermission['root'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// get object name 
$fileinfo = getfileinfo ($site, $location.$page, $cat);
$pagename = $fileinfo['name'];
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
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php
echo showtopbar ($pagename." ".$hcms_lang['is-used-by'][$lang], $lang, $mgmt_config['url_path_cms']."page_info.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame");
?>

<!-- content -->
<div class="hcmsWorkplaceFrame">
<?php
$site_buffer = $site;

echo  "<table class=\"hcmsTableStandard\" style=\"width:99%;\">
<tr>
  <td class=\"hcmsHeadline\" style=\"width:15%; white-space:nowrap;\">".getescapedtext ($hcms_lang['name'][$lang])."</td>
  <td class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['location'][$lang])."</td>
  <td class=\"hcmsHeadline\" style=\"width:15%; white-space:nowrap;\">".getescapedtext ($hcms_lang['publication'][$lang])."</td>
</tr>";
// ---------------------------- analyze links ------------------------------
// get linked objects
$result_array = getlinkedobject ($site, $location, $page, $cat);

$found = false;
$color = false;
$addtext = "";

if (is_array ($result_array) && sizeof ($result_array) > 0)
{
  // explore each record in link management database
  foreach ($result_array as $result)
  {  
    // get object info
    $file_info = getfileinfo ($result['publication'], $result['convertedlocation'].$result['object'], $result['category']);
          
    if ($file_info != false)
    {
      $found = true; 
    
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
    
      if ($result['category'] == "page")
      {          
        $location_obj_short = str_replace ("%page%", "", $result['convertedlocation']);
        
        if ($file_info['type'] == "Folder")
        {
          $file_info['name'] = getobject ($location_obj_short);
          $location_obj_short = getlocation ($location_obj_short);
        }        
      
        // if outside pageaccess
        if ($pageaccess != "" && accesspermission ($result['publication'], $result['location'], "page") != false)
        {
          echo "<tr class=\"".$rowcolor."\"><td style=\"white-space:nowrap;\"><a href=\"javascript:void(0);\" onClick=\"hcms_openWindow('".cleandomain ($mgmt_config['url_path_cms'])."frameset_content.php?ctrlreload=yes&site=".url_encode($result['publication'])."&cat=".url_encode($result['category'])."&location=".url_encode($result['convertedlocation'])."&page=".url_encode($result['object'])."', '', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes,status=no', ".windowwidth("object").", ".windowheight("object").");\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" class=\"hcmsIconList\" />&nbsp; ".$file_info['name']."</a></td><td style=\"white-space:nowrap;\">".$location_obj_short."</td><td>".$result['publication']."</td></tr>\n";
        }          
        else
        {
          echo "<tr class=\"".$rowcolor."\"><td style=\"white-space:nowrap;\"><a href=\"javascript:void(0);\" onClick=\"hcms_openWindow('".cleandomain ($mgmt_config['url_path_cms'])."page_preview.php?site=".url_encode($result['publication'])."&cat=".url_encode($result['category'])."&location=".url_encode($result['convertedlocation'])."&page=".url_encode($result['object'])."', 'preview', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes,status=no', ".windowwidth("object").", ".windowheight("object").");\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" class=\"hcmsIconList\" />&nbsp; ".$file_info['name']."</a></td><td style=\"white-space:nowrap;\">".$location_obj_short."</td><td>".$result['publication']."</td></tr>\n";
        }
      }
      elseif ($result['category'] == "comp")
      {          
        $location_obj_short = str_replace ("%comp%", "", $result['convertedlocation']);
        
        if ($file_info['type'] == "Folder")
        {
          $file_info['name'] = getobject ($location_obj_short);
          $location_obj_short = getlocation ($location_obj_short);
        }        
      
        // if outside compaccess
        if ($compaccess != "" && accesspermission ($result['publication'], $result['location'], "comp")  != false)
        {
          echo "<tr class=\"".$rowcolor."\"><td style=\"white-space:nowrap;\"><a href=\"javascript:void(0);\" onClick=\"hcms_openWindow('".cleandomain ($mgmt_config['url_path_cms'])."frameset_content.php?ctrlreload=yes&site=".url_encode($result['publication'])."&cat=".url_encode($result['category'])."&location=".url_encode($result['convertedlocation'])."&page=".url_encode($result['object'])."', '', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes,status=no', ".windowwidth("object").", ".windowheight("object").");\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" class=\"hcmsIconList\" />&nbsp; ".$file_info['name']."</a></td><td style=\"white-space:nowrap;\">".$location_obj_short."</td><td>".$result['publication']."</td></tr>\n";
        }          
        else
        {
          echo "<tr class=\"".$rowcolor."\"><td style=\"white-space:nowrap;\"><a href=\"javascript:void(0);\" onClick=\"hcms_openWindow('".cleandomain ($mgmt_config['url_path_cms'])."page_preview.php?site=".url_encode($result['publication'])."&cat=".url_encode($result['category'])."&location=".url_encode($result['convertedlocation'])."&page=".url_encode($result['object'])."', 'preview', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes,status=no', ".windowwidth("object").", ".windowheight("object").");\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" class=\"hcmsIconList\" />&nbsp; ".$file_info['name']."</a></td><td style=\"white-space:nowrap;\">".$location_obj_short."</td><td>".$result['publication']."</td></tr>\n";
        }
      }
    }    
  }
}
// link management is disabled
elseif ($result_array == true)
{
  $addtext = "(Link Management is disabled)";
} 

// if no items were found  
if ($found == false) echo "<tr class=\"hcmsRowData1\"><td colspan=\"4\">".getescapedtext ($hcms_lang['no-items-were-found'][$lang])." ".$addtext."</td></tr>\n";

echo "</table>\n";

// save log
savelog (@$error);
?>
</div>

<?php includefooter(); ?>
</body>
</html>
