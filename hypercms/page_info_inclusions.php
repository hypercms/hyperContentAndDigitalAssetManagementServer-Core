<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("include/session.inc.php");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");
// language file
require_once ("language/page_info_inclusions.inc.php");


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

// get name 
$fileinfo = getfileinfo ($site, $location.$page, $cat);
$pagename = $fileinfo['name'];
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php
echo showtopbar ($pagename." ".$text1[$lang], $lang, $mgmt_config['url_path_cms']."page_info.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame");
?>

<!-- content -->
<div style="padding:0; width:100%; z-index:1;">
<?php
$site_buffer = $site;

echo "<table border=\"0\" cellspacing=\"2\" cellpadding=\"3\" width=\"99%\">
<tr>
  <td class=\"hcmsHeadline\" width=\"15%\" nowrap=\"nowrap\">".$text2[$lang]."</td>
  <td class=\"hcmsHeadline\">".$text3[$lang]."</td>
  <td class=\"hcmsHeadline\" width=\"15%\" nowrap=\"nowrap\">".$text9[$lang]."</td>
</tr>";
// ---------------------------- analyze links ------------------------------
// get linked objects
$result_array = getlinkedobject ($site, $location, $page, $cat);

$found = false;
$color = false;

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
          echo "<tr class=\"".$rowcolor."\"><td nowrap=\"nowrap\"><a href=# onClick=\"hcms_openBrWindowItem('".$mgmt_config['url_path_cms']."frameset_content.php?ctrlreload=yes&site=".url_encode($result['publication'])."&cat=".url_encode($result['category'])."&location=".url_encode($result['convertedlocation'])."&page=".url_encode($result['object'])."','','scrollbars=yes,resizable=yes,status=yes','800','600');\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" border=0 width=16 height=16 align=\"absmiddle\" />&nbsp; ".$file_info['name']."</a></td><td nowrap=\"nowrap\">".$location_obj_short."</td><td>".$result['publication']."</td></tr>\n";
        }          
        else
        {
          echo "<tr class=\"".$rowcolor."\"><td nowrap=\"nowrap\"><a href=# onClick=\"hcms_openBrWindowItem('".$mgmt_config['url_path_cms']."page_preview.php?site=".url_encode($result['publication'])."&cat=".url_encode($result['category'])."&location=".url_encode($result['convertedlocation'])."&page=".url_encode($result['object'])."','preview','scrollbars=yes,resizable=yes','800','600');\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" border=0 width=16 height=16 align=\"absmiddle\" />&nbsp; ".$file_info['name']."</a></td><td nowrap=\"nowrap\">".$location_obj_short."</td><td>".$result['publication']."</td></tr>\n";
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
          echo "<tr class=\"".$rowcolor."\"><td nowrap=\"nowrap\"><a href=# onClick=\"hcms_openBrWindowItem('".$mgmt_config['url_path_cms']."frameset_content.php?ctrlreload=yes&site=".url_encode($result['publication'])."&cat=".url_encode($result['category'])."&location=".url_encode($result['convertedlocation'])."&page=".url_encode($result['object'])."','','scrollbars=yes,resizable=yes,status=yes','800','600');\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" border=0 width=16 height=16 align=\"absmiddle\" />&nbsp; ".$file_info['name']."</a></td><td nowrap=\"nowrap\">".$location_obj_short."</td><td>".$result['publication']."</td></tr>\n";
        }          
        else
        {
          echo "<tr class=\"".$rowcolor."\"><td nowrap=\"nowrap\"><a href=# onClick=\"hcms_openBrWindowItem('".$mgmt_config['url_path_cms']."page_preview.php?site=".url_encode($result['publication'])."&cat=".url_encode($result['category'])."&location=".url_encode($result['convertedlocation'])."&page=".url_encode($result['object'])."','preview','scrollbars=yes,resizable=yes','800','600');\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" border=0 width=16 height=16 align=\"absmiddle\" />&nbsp; ".$file_info['name']."</a></td><td nowrap=\"nowrap\">".$location_obj_short."</td><td>".$result['publication']."</td></tr>\n";
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
if ($found == false) echo "<tr class=\"hcmsRowData1\"><td colspan=\"4\">".$text7[$lang]." ".$addtext."</td></tr>\n";

echo "</table>\n";

// save log
savelog (@$error);
?>
</div>

</body>
</html>
