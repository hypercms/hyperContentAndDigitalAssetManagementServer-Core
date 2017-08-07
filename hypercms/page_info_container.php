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
$fileinfo = getfileinfo ($site, $page, $cat);
$pagename = $fileinfo['name'];

// check and correct file
$page = correctfile ($location, $page, $user);
  
// load page and read actual file info (to get associated template and content)
$pagedata = loadfile ($location, $page);

if ($pagedata != false)
{
  // get container
  $container = getfilename ($pagedata, "content");
}
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
<?php
echo showtopbar ($hcms_lang['content-container'][$lang]." '".$container."' ".$hcms_lang['is-used-by'][$lang], $lang, $mgmt_config['url_path_cms']."page_info.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame");
?>

<!-- content -->
<div class="hcmsWorkplaceFrame">
<?php
echo "<table border=\"0\" cellspacing=\"2\" cellpadding=\"3\" width=\"99%\">
  <tr>
    <td class=\"hcmsHeadline\" width=\"15%\" nowrap=\"nowrap\">".getescapedtext ($hcms_lang['name'][$lang])."</td>
    <td class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['location'][$lang])."</td>
    <td class=\"hcmsHeadline\" width=\"15%\" nowrap=\"nowrap\">".getescapedtext ($hcms_lang['publication'][$lang])."</td>
  </tr>";
// ---------------------------- analyze links ------------------------------
// get connected objects
$result_array = getconnectedobject ($container);

if ($result_array != false && sizeof ($result_array) > 0)
{
  $color = false;
  $found = false;
    
  foreach ($result_array as $result)
  { 
    // get object info
    $file_info = getfileinfo ($result['publication'], $result['object'], $result['category']);
    
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

        // access
        if ($pageaccess && accesspermission ($result['publication'], $result['location'], "page") != false && $result['location'].$file_info['filename'] != $location.$pagename)
        {
          echo "<tr class=\"".$rowcolor."\"><td nowrap=\"nowrap\"><a href=# onClick=\"hcms_openWindow('".$mgmt_config['url_path_cms']."frameset_content.php?ctrlreload=yes&site=".url_encode($result['publication'])."&cat=".url_encode($cat)."&location=".url_encode($result['convertedlocation'])."&page=".url_encode($result['object'])."', '', 'scrollbars=yes,resizable=yes,status=yes', 800, 600);\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" border=0 width=16 height=16 align=\"absmiddle\" />&nbsp; ".$file_info['name']."</a></td><td nowrap=\"nowrap\">".$location_obj_short."</td><td>".$result['publication']."</td></tr>\n";
        }
        //preview       
        else
        {
          echo "<tr class=\"".$rowcolor."\"><td nowrap=\"nowrap\"><a href=# onClick=\"hcms_openWindow('".$mgmt_config['url_path_cms']."page_preview.php?site=".url_encode($result['publication'])."&cat=".url_encode($cat)."&location=".url_encode($result['convertedlocation'])."&page=".url_encode($result['object'])."', 'preview', 'scrollbars=yes,resizable=yes', 800, 600);\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" border=0 width=16 height=16 align=\"absmiddle\" />&nbsp; ".$file_info['name']."</a></td><td nowrap=\"nowrap\">".$location_obj_short."</td><td>".$result['publication']."</td></tr>\n";
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

        // access
        if ($compaccess && accesspermission ($result['publication'], $result['location'], "comp")  != false && $result['location'].$file_info['filename'] != $location.$pagename)
        {
          echo "<tr class=\"".$rowcolor."\"><td nowrap=\"nowrap\"><a href=# onClick=\"hcms_openWindow('".$mgmt_config['url_path_cms']."frameset_content.php?ctrlreload=yes&site=".url_encode($result['publication'])."&cat=".url_encode($result['category'])."&location=".url_encode($result['convertedlocation'])."&page=".url_encode($result['object'])."', '', 'scrollbars=yes,resizable=yes,status=yes', 800, 600);\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" border=0 width=16 height=16 align=\"absmiddle\" />&nbsp; ".$file_info['name']."</a></td><td nowrap=\"nowrap\">".$location_obj_short."</td><td>".$result['publication']."</td></tr>\n";
        }
        // preview        
        else
        {
          echo "<tr class=\"".$rowcolor."\"><td nowrap=\"nowrap\"><a href=# onClick=\"hcms_openWindow('".$mgmt_config['url_path_cms']."page_preview.php?site=".url_encode($result['publication'])."&ctrlreload=yes&cat=".url_encode($result['category'])."&location=".url_encode($result['convertedlocation'])."&page=".url_encode($result['object'])."', 'preview', 'scrollbars=yes,resizable=yes', 800, 600);\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" border=0 width=16 height=16 align=\"absmiddle\" />&nbsp; ".$file_info['name']."</a></td><td nowrap=\"nowrap\">".$location_obj_short."</td><td>".$result['publication']."</td></tr>\n";
        }
      }
    }    
  }
}
else
{
  echo "<script language=\"JavaScript\">
<!--
openBrWindow('popup_log.php?description=<p class=hcmsHeadline>".getescapedtext ($hcms_lang['functional-error-occured'][$lang])."</p>".getescapedtext ($hcms_lang['link-management-database-is-corrupt-or-you-do-not-have-read-permissions'][$lang])."', 'alert', 'scrollbars=yes,width=600,height=200','600','200');
-->
</script>\n";
  
  $errcode = "20102";
  $error[] = $mgmt_config['today']."|page_info_container.php|error|$errcode|error in getconnectedobject for publication ".$site;        
}  

// if no items were found  
if ($found == false) echo "<tr class=\"hcmsRowData1\"><td colspan=\"4\">".getescapedtext ($hcms_lang['no-items-were-found'][$lang])."</td></tr>\n";
echo "</table>\n";

// save log
savelog (@$error);
?>
</div>

</body>
</html>
