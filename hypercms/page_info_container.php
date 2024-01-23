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
$from_page = getrequest ("from_page");
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$delete = getrequest ("delete", "array");
$token = getrequest ("token");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// publication management config
if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
{
  require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
}

// add slash if not present at the end of the location string
$location = correctpath ($location);

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

// initialize
$show = "";
$error = array();
$container = "";
$container_id = "";

// get name 
$fileinfo = getfileinfo ($site, $page, $cat);
$pagename = $fileinfo['name'];

// check and correct file name
$page = correctfile ($location, $page, $user);
  
// load page and read actual file info (to get associated template and content)
$pagedata = loadfile ($location, $page);

if ($pagedata != false)
{
  // get container
  $container = getfilename ($pagedata, "content");
  $container_id = substr ($container, 0, strpos ($container, ".xml")); 
}

// create secure token
$token_new = createtoken ($user);

// delete connected objects
if (is_array ($delete) && sizeof ($delete) > 0 && checktoken ($token, $user))
{
  foreach ($delete as $path)
  {
    if ($path != "")
    {
      $temp_site = getpublication ($path);
      $temp_location = getlocation ($path);
      $temp_object = getobject ($path);
      $temp_cat = getcategory ($temp_site, $temp_location);

      // check access permissions
      $temp_ownergroup = accesspermission ($temp_site, $temp_location, $temp_cat);
      $temp_setlocalpermission = setlocalpermission ($temp_site, $temp_ownergroup, $temp_cat);

      if ($temp_ownergroup != false && $temp_setlocalpermission['root'] == 1 && $temp_setlocalpermission['delete'] == 1)
      {
        // mark as deleted
        if (!empty ($mgmt_config['recyclebin'])) $result_delete = deletemarkobject ($temp_site, $temp_location, $temp_object, $user);
        // delete
        else $result_delete = deleteobject ($temp_site, $temp_location, $temp_object, $user);

        if (empty ($result_delete['result'])) $show = $result_delete['message'];
      }
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
<script>
function toggledelete (source)
{
  var checkboxes = document.getElementsByClassName('delete');
  
  for (var i=0; i<checkboxes.length; i++)
  {
    checkboxes[i].checked = source.checked;
  }
}

function warning_delete ()
{
  var form = document.forms['versionform'];
  
  check = confirm (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-the-selected-entries'][$lang]); ?>"));  
  if (check == true) form.submit();
  return check;
}
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php
if ($from_page != "objectlist") $button_close = $mgmt_config['url_path_cms']."page_info.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page);
else $button_close = "";

echo showtopbar ($hcms_lang['content-container'][$lang]." '".$container_id."' ".$hcms_lang['is-used-by'][$lang], $lang, $button_close, "objFrame");
?>

<!-- content -->
<div class="hcmsWorkplaceFrame">

<?php
echo showmessage ($show, 460, 70, $lang, "position:fixed; left:10px; top:50px;");
?>

<!-- delete connected copies -->
<form name="versionform" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />

  <table class="hcmsTableStandard" style="width:99%;">
    <tr>
      <td class="hcmsHeadline" style="width:20%; white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['name'][$lang]); ?></td>
      <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['location'][$lang]); ?></td>
      <td class="hcmsHeadline" style="white-space:nowrap; width:60px; text-align:center;"><label style="cursor:pointer;"><input type="checkbox" onclick="toggledelete(this);" style="display:none" /><?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?></label></td>
    </tr>

<?php
// ---------------------------- analyze links ------------------------------
// initialize
$color = false;
$found = false;

// get connected objects
$object_array = getconnectedobject ($container);

if (is_array ($object_array) && sizeof ($object_array) > 0)
{    
  foreach ($object_array as $result)
  {
    // correct file name
    $result['object'] = correctfile ($result['location'], $result['object'], $user);

    // get object info
    $file_info = getfileinfo ($result['publication'], $result['object'], $result['category']);
    
    if (valid_locationname ($result['location']) && valid_objectname ($result['object']) && is_file ($result['location'].$result['object']) && $file_info != false)
    {  
      $found = true;

      // location
      $location_obj_short = getlocationname ($result['publication'], $result['convertedlocation'], $result['category'], "path");  
        
      if ($file_info['type'] == "Folder")
      {
        $file_info['name'] = getobject ($location_obj_short);
        $location_obj_short = getlocation ($location_obj_short);
      }     

      // current object
      if ($result['location'].$file_info['file'] == $location.$pagename) $current_object = true;
      else $current_object = false;

      // define row color
      if ($current_object)
      {
        $rowcolor = "hcmsRowHead2";
      }
      elseif ($color == true)
      {
        $rowcolor = "hcmsRowData1";
        $color = false;
      }
      else
      {
        $rowcolor = "hcmsRowData2";
        $color = true;
      }

      // pages
      if ($result['category'] == "page")
      {
        // access
        if ($pageaccess && accesspermission ($result['publication'], $result['location'], "page") != false && !$current_object)
        {
          echo "
          <tr class=\"".$rowcolor."\">
            <td style=\"white-space:nowrap;\"><a href=\"javascript:void(0);\" ".(!$current_object ? "onclick=\"hcms_openWindow('".cleandomain ($mgmt_config['url_path_cms'])."frameset_content.php?ctrlreload=yes&site=".url_encode($result['publication'])."&cat=".url_encode($cat)."&location=".url_encode($result['convertedlocation'])."&page=".url_encode($result['object'])."', '', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes,status=no', ".windowwidth("object").", ".windowheight("object").");\"" : "")."><img src=\"".getthemelocation()."img/".$file_info['icon']."\" class=\"hcmsIconList\" />&nbsp; ".$file_info['name']."</a></td>
            <td style=\"white-space:nowrap;\">".$location_obj_short."</td>
            <td style=\"text-align:center; vertical-align:middle;\"><input type=\"checkbox\" name=\"delete[]\" class=\"delete\" value=\"".$result['convertedlocation'].$result['object']."\" ".($file_info['type'] == "Folder" ? "disabled" : "")." /></td>
          </tr>";
        }
        //preview       
        else
        {
          echo "
          <tr class=\"".$rowcolor."\">
            <td style=\"white-space:nowrap;\"><a href=\"javascript:void(0);\" ".(!$current_object ? "onclick=\"hcms_openWindow('".cleandomain ($mgmt_config['url_path_cms'])."page_preview.php?site=".url_encode($result['publication'])."&cat=".url_encode($cat)."&location=".url_encode($result['convertedlocation'])."&page=".url_encode($result['object'])."', 'preview', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes,status=no', ".windowwidth("object").", ".windowheight("object").");\"" : "")."><img src=\"".getthemelocation()."img/".$file_info['icon']."\" class=\"hcmsIconList\" />&nbsp; ".$file_info['name']."</a></td>
            <td style=\"white-space:nowrap;\">".$location_obj_short."</td>
            <td style=\"text-align:center; vertical-align:middle;\"><input type=\"checkbox\" name=\"dummy\" value=\"\" disabled /></td>
          </tr>";
        }
      }
      // assets/components
      elseif ($result['category'] == "comp")
      {
        // access
        if ($compaccess && accesspermission ($result['publication'], $result['location'], "comp") != false && !$current_object)
        {
          echo "
          <tr class=\"".$rowcolor."\">
            <td style=\"white-space:nowrap;\"><a href=\"javascript:void(0);\" ".(!$current_object ? "onclick=\"hcms_openWindow('".cleandomain ($mgmt_config['url_path_cms'])."frameset_content.php?ctrlreload=yes&site=".url_encode($result['publication'])."&cat=".url_encode($result['category'])."&location=".url_encode($result['convertedlocation'])."&page=".url_encode($result['object'])."', '', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes,status=no', ".windowwidth("object").", ".windowheight("object").");\"" : "")."><img src=\"".getthemelocation()."img/".$file_info['icon']."\" class=\"hcmsIconList\" />&nbsp; ".$file_info['name']."</a></td>
            <td style=\"white-space:nowrap;\">".$location_obj_short."</td>
            <td style=\"text-align:center; vertical-align:middle;\"><input type=\"checkbox\" name=\"delete[]\" class=\"delete\" value=\"".$result['convertedlocation'].$result['object']."\" ".($file_info['type'] == "Folder" ? "disabled" : "")." /></td>
          </tr>";
        }
        // preview        
        else
        {
          echo "
          <tr class=\"".$rowcolor."\">
            <td style=\"white-space:nowrap;\"><a href=\"javascript:void(0);\" ".(!$current_object ? "onclick=\"hcms_openWindow('".cleandomain ($mgmt_config['url_path_cms'])."page_preview.php?site=".url_encode($result['publication'])."&ctrlreload=yes&cat=".url_encode($result['category'])."&location=".url_encode($result['convertedlocation'])."&page=".url_encode($result['object'])."', 'preview', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes,status=no', ".windowwidth("object").", ".windowheight("object").");\"" : "")."><img src=\"".getthemelocation()."img/".$file_info['icon']."\" class=\"hcmsIconList\" />&nbsp; ".$file_info['name']."</a></td>
            <td style=\"white-space:nowrap;\">".$location_obj_short."</td>
            <td style=\"text-align:center; vertical-align:middle;\"><input type=\"checkbox\" name=\"dummy\" value=\"\" disabled /></td>
          </tr>";
        }
      }
    }
    // connected object does not exist
    else
    {
      $errcode = "20101";
      $error[] = $mgmt_config['today']."|page_info_container.php|error|".$errcode."|The connected object does not exist '".$result['convertedlocation'].$result['object']."'";    
    }  
  }
}
else
{
  echo "<script language=\"JavaScript\">
openBrWindow('popup_log.php?description=<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['functional-error-occured'][$lang])."</p>".getescapedtext ($hcms_lang['link-management-database-is-corrupt-or-you-do-not-have-read-permissions'][$lang])."', 'alert', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,status=no,width=600,height=220','600','220');
</script>\n";
  
  $errcode = "20102";
  $error[] = $mgmt_config['today']."|page_info_container.php|error|".$errcode."|Error in getconnectedobject for publication '".$site."'";        
}  

// if no items were found  
if ($found == false) echo "
  <tr class=\"hcmsRowData1\">
    <td colspan=\"3\">".getescapedtext ($hcms_lang['no-items-were-found'][$lang])."</td>
  </tr>";

// save log
savelog (@$error);
?>
  </table>
  <br />
  <table class="hcmsTableStandard">
    <tr>
      <td style="width:70px;"><?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?> </td>
      <td><img name="Button1" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="warning_delete();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" /></td>
    </tr>
  </table>
</form>

</div>

<?php includefooter(); ?>

</body>
</html>
