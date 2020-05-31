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
require ("../../../config.inc.php");
// hyperCMS API
require ("../../../function/hypercms_api.inc.php");
// language file of plugin
require_once ("../lang/page.inc.php");


// input parameters
$site = getrequest_esc ("site", "publicationname");
$content = getrequest_esc ("content");

// only german and english is supported by plugin
if ($lang != "en" && $lang != "de") $lang = "en";

// defintions
// add additonal storage in GB if an addtional external storage is used (e.g. multiple HDD or export of media files on other disk)
// you can also set this value in the main configuration file, so you can easily update the plugin in future
// the limitation of this plugin is that it only supports the harddrive storage where the webserver is operating.
if (empty ($mgmt_config['additional_storage'])) $mgmt_config['additional_storage'] = 0;

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="../../../javascript/main.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- load screen --> 
<div id="hcmsLoadScreen" class="hcmsLoadScreen" stlye="display:inline;"></div>

<!-- top bar -->
<?php
echo showtopbar ($hcms_lang['simple-statistics'][$lang], $lang); 

ob_flush();
flush();
ob_end_flush();
?>

<div class="hcmsWorkplaceFrame" style="padding:0; width:100%; height:100%; overflow:auto;">

  <table class="hcmsTableStandard" style="margin:10px;">
 	  <tr style="text-align:left; vertical-align:top;">
      <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['total-disk-space'][$lang]); ?> &nbsp;&nbsp;</td>
      <td style="text-align:right;"><?php
      $space_total = disk_total_space ($mgmt_config['abs_path_cms']);
      
      if ($space_total > 0)
      {
        $space = $space_total/1024/1024/1024 + intval ($mgmt_config['additional_storage']);
        echo number_format ($space, 2, ",", ".")." GB"; 
      }
      else echo "not available";
      ?></td>
    </tr>
 	  <tr style="text-align:left; vertical-align:top;">
      <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['free-disk-space'][$lang]); ?> &nbsp;&nbsp;</td>
      <td style="text-align:right;"><?php
      $space_free = disk_free_space ($mgmt_config['abs_path_cms']);
      
      if ($space_free)
      {
        echo number_format (($space_free/1024/1024/1024), 2, ",", ".")." GB";
      }
      else echo "not available";
      ?></td>
    </tr>
  </table>
  
  <?php
      $bar = round ((($space_total - $space_free) / $space_total), 4) * 100;
      
      if ($bar > 0) echo "
    <table style=\"width:300px; padding:0; margin:10px; border:1px solid #000000; border-collapse:collapse;\">
      <tr> 
        <td>
          <div class=\"hcmsRowHead1\" style=\"width:".ceil($bar)."%; height:32px; text-align:center; font-size:26px; line-height:32px; overflow:hidden;\">".ceil($bar)." %</div>
        </td>
      </tr>
    </table>";
  ?>
  
  <hr />
  
  <div style="float:left; margin:10px;">
    <p class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['top-downloaded-files'][$lang]); ?></p>
    <table class="hcmsTableStandard" style="min-width:400px;">
   	  <tr>
        <td class="hcmsHeadline hcmsRowHead1" style="text-align:left; vertical-align:top;"><?php echo getescapedtext ($hcms_lang['object'][$lang]); ?></td>
        <td class="hcmsHeadline hcmsRowHead1" style="text-align:right;"><?php echo getescapedtext ($hcms_lang['hits'][$lang]); ?></td>
        <td class="hcmsHeadline hcmsRowHead1" style="text-align:right;"><?php echo getescapedtext ($hcms_lang['traffic-in-mb'][$lang]); ?></td>
      </tr>
      <?php
      $show = "";
      
      // connect to MySQL
      $mysqli = new mysqli ($mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname']);
      
      if ($mysqli->connect_errno) $show .= "
      <tr class=\"hcmsRowData1\">
        <td colspan=\"3\" class=\"hcmsHeadline\">DB error (".$mysqli->connect_errno."): ".$mysqli->connect_error."</td>
      </tr>";
      
      if ($show == "")
      {
        $sql = "SELECT dailystat.id, object.objectpath, SUM(dailystat.count) count, SUM(media.filesize) filesize FROM dailystat, object, media WHERE dailystat.activity='download' AND dailystat.id=object.id AND dailystat.id=media.id GROUP BY dailystat.id ORDER BY count DESC LIMIT 0,10";
      
        if ($result = $mysqli->query ($sql))
        {
          $rowcolor = "";

          while ($row = $result->fetch_assoc())
          {
            $row['objectpath'] = str_replace ("*", "%", $row['objectpath']);
            $site = getpublication ($row['objectpath']);
            $location_esc = getlocation ($row['objectpath']);
            $object = getobject ($row['objectpath']);
            $cat = getcategory ($site, $row['objectpath']); 
            $info = getfileinfo ($site, $row['objectpath'], $cat);
            
            // check access permissions
            $ownergroup = accesspermission ($site, $location_esc, $cat);
            $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);
            
            if ($setlocalpermission['root']) $link = "<a href=\"#\" onclick=\"hcms_openWindow('../../../frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($object)."', '".$row['id']."', 'status=yes,scrollbars=no,resizable=yes', ".windowwidth ("object").", ".windowheight ("object").");\">".$info['name']."</a>";
            else $link = $info['name'];

            // define row color
            if ($rowcolor == "hcmsRowData1") $rowcolor = "hcmsRowData2";
            else $rowcolor = "hcmsRowData1";
               
            $show .= "
        <tr class=\"hcmsButtonTiny ".$rowcolor."\" style=\"cursor:pointer;\" onclick=\"hcms_openWindow('../../../frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($object)."', '".$row['id']."', 'status=yes,scrollbars=no,resizable=yes', ".windowwidth ("object").", ".windowheight ("object").");\">
          <td><img src=\"".getthemelocation()."img/".$info['icon']."\" class=\"hcmsIconList\" /> ".$info['name']."</td>
          <td style=\"text-align:right;\">".$row['count']."</td>
          <td style=\"text-align:right;\">".number_format ($row['filesize'], 0, ",", ".")."</td>
        </tr>";
          }
        }
        else $show .= "
        <tr class=\"".$rowcolor."\">
          <td colspan=\"3\" class=\"hcmsHeadline\">DB error (".$mysqli->errno."): ".$mysqli->error."</td>
        </tr>";
        
        echo $show;
        
        $result->close();
        $mysqli->close();
      }
      ?>
    </table>  
  </div>
  
  <div style="float:left; margin:10px;">
    <p class=hcmsHeadline><?php echo getescapedtext ($hcms_lang['top-uploaded-files'][$lang]); ?></p>
    <table class="hcmsTableStandard" style="min-width:400px;">
   	  <tr>
        <td class="hcmsHeadline hcmsRowHead1"><?php echo getescapedtext ($hcms_lang['object'][$lang]); ?></td>
        <td class="hcmsHeadline hcmsRowHead1" style="text-align:right;"><?php echo getescapedtext ($hcms_lang['hits'][$lang]); ?></td>
        <td class="hcmsHeadline hcmsRowHead1" style="text-align:right;"><?php echo getescapedtext ($hcms_lang['traffic-in-mb'][$lang]); ?></td>
      </tr>
      <?php
      $show = "";
      
      // connect to MySQL
      $mysqli = new mysqli ($mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname']);
      
      if ($mysqli->connect_errno) $show .= "
      <tr class=\"hcmsRowData1\">
        <td colspan=\"3\" class=\"hcmsHeadline\">DB error (".$mysqli->connect_errno."): ".$mysqli->connect_error."</td>
      </tr>";
      
      if ($show == "")
      {
        $sql = "SELECT dailystat.id, object.objectpath, SUM(dailystat.count) count, SUM(media.filesize) filesize FROM dailystat, object, media WHERE dailystat.activity='upload' AND dailystat.id=object.id AND dailystat.id=media.id GROUP BY dailystat.id ORDER BY count DESC LIMIT 0,10";
      
        if ($result = $mysqli->query ($sql))
        {
          $rowcolor = "";
          
          while ($row = $result->fetch_assoc())
          {
            $row['objectpath'] = str_replace ("*", "%", $row['objectpath']);
            $site = getpublication ($row['objectpath']);
            $location_esc = getlocation ($row['objectpath']);
            $object = getobject ($row['objectpath']);
            $cat = getcategory ($site, $row['objectpath']); 
            $info = getfileinfo ($site, $row['objectpath'], $cat);
            
            // check access permissions
            $ownergroup = accesspermission ($site, $location_esc, $cat);
            $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);
            
            // define row color
            if ($rowcolor == "hcmsRowData1") $rowcolor = "hcmsRowData2";
            else $rowcolor = "hcmsRowData1";

            $show .= "
        <tr class=\"hcmsButtonTiny ".$rowcolor."\" style=\"cursor:pointer;\" onclick=\"hcms_openWindow('../../../frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($object)."', '".$row['id']."', 'status=yes,scrollbars=no,resizable=yes', ".windowwidth ("object").", ".windowheight ("object").");\">
          <td><img src=\"".getthemelocation()."img/".$info['icon']."\" class=\"hcmsIconList\" /> ".$info['name']."</td>
          <td style=\"text-align:right;\">".$row['count']."</td>
          <td style=\"text-align:right;\">".number_format ($row['filesize'], 0, ",", ".")."</td>
        </tr>";
          }
        }
        else $show .= "
        <tr class=\"".$rowcolor."\">
          <td colspan=\"3\" class=\"hcmsHeadline\">DB error (".$mysqli->errno."): ".$mysqli->error."</td>
        </tr>";
        
        echo $show;
        
        $result->close();
        $mysqli->close();
      }
      ?>
    </table>
  </div>
  
</div>

<!-- initalize -->
<script type="text/javascript">
// load screen
if (document.getElementById('hcmsLoadScreen')) document.getElementById('hcmsLoadScreen').style.display='none';
</script>

<?php include_once ($mgmt_config['abs_path_cms']."include/footer.inc.php"); ?>
</body>
</html>