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
// hyperCMS UI
require ("function/hypercms_ui.inc.php");


// input parameters
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$delete_id = getrequest ("delete_id", "array");

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
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
function warning_recipients_delete()
{
  var form = document.forms['recipientform'];
  
  check = confirm (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-the-selected-entries'][$lang]); ?>"));
  if (check == true) form.submit();
  return check;
}
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php
echo showtopbar ($pagename." ".$hcms_lang['was-send-to'][$lang], $lang, $mgmt_config['url_path_cms']."page_info.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame");
?>

<!-- content -->
<div style="padding:0; width:100%; z-index:1;">
<?php
// delete recipients
if (is_array ($delete_id) && @sizeof ($delete_id) > 0 && $setlocalpermission['delete'] == 1)
{
  foreach ($delete_id as $recipient_id)
  {
    rdbms_deleterecipient ($recipient_id);
  }
}
?>
<form name="recipientform" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="page" value="<?php echo $page; ?>" />
  
  <table border="0" celspacing="2" cellpadding="1" width="99%">
    <tr>
      <td class="hcmsHeadline" width="150" nowrap="nowrap"><?php echo $hcms_lang['date'][$lang]; ?></td>
      <td class="hcmsHeadline" width="160" nowrap="nowrap"><?php echo $hcms_lang['sender'][$lang]; ?></td>
      <td class="hcmsHeadline" width="160" nowrap="nowrap"><?php echo $hcms_lang['recipient'][$lang]; ?></td>
      <td class="hcmsHeadline" nowrap="nowrap"><?php echo $hcms_lang['e-mail'][$lang]; ?></td>
      <td class="hcmsHeadline" width="120" nowrap="nowrap"><?php echo $hcms_lang['picked-up-on'][$lang]; ?></td>
      <td class="hcmsHeadline" width="100" nowrap="nowrap"><?php echo $hcms_lang['delete'][$lang]; ?></td>
    </tr>
<?php
// get recipients
$result_array = rdbms_getrecipients ($location_esc.$page);
$found = false;

// show results
if ($result_array != false && sizeof ($result_array) > 0)
{  
  $color = false;

  foreach ($result_array as $result)
  {          
    if ($result['recipient_id'] != "")
    {   
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
      
      // get download stats
      $object_info = getobjectinfo ($site, $location, $page, $user);

      if (is_array ($object_info))
      {
        $result_stats = rdbms_getmediastat ($result['date'], "", "download", $object_info['container_id'], "", $result['user'], "object");
      }
      else
      {
        $result_stats = rdbms_getmediastat ($result['date'], "", "download", "", $location_esc.$page, $result['user'], "object");
      }

      if (is_array ($result_stats))
      {
        $download_date = "";
        
        foreach ($result_stats as $stats)
        {
          if ($stats['date'] != "" && ($download_date == "" || strtotime($download_date) > strtotime($stats['date']))) $download_date = $stats['date'];
        }
        
        $result['download_date'] = $download_date;
      }
      else $result['download_date'] = "";
     
      echo "<tr class=\"".$rowcolor."\">
        <td nowrap=\"nowrap\">".$result['date']."</td>
        <td>".$result['sender']."</td>
        <td>".$result['user']."</td>
        <td>".$result['email']."</td>
        <td>".$result['download_date']."</td>
        <td align=\"middle\" valign=\"middle\"><input type=\"checkbox\" name=\"delete_id[]\" value=\"".$result['recipient_id']."\" /></td>
      </tr>\n";
    }     
  }
  
  echo "<tr>
      <td colspan=\"3\">&nbsp;</td>
    </tr>
    <tr>
      <td colspan=\"3\" nowrap=\"nowrap\">
        ".$hcms_lang['delete-selected-recipients'][$lang].":
        <img name=\"Button\" src=\"".getthemelocation()."img/button_OK.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" onclick=\"warning_recipients_delete();\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button','','".getthemelocation()."img/button_OK_over.gif',1)\" align=\"absmiddle\" title=\"OK\" alt=\"OK\" />
      </td>
    </tr>";  
}
// no results
else
{
  echo "<tr class=\"hcmsRowData1\">
        <td colspan=\"6\">".$hcms_lang['no-users-were-found'][$lang]."</td>
      </tr>\n";
}
?>
  </table>
</form>
</div>

</body>
</html>
