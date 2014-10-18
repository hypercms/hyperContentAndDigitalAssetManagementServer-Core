<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 

// session parameters
require ("../../../include/session.inc.php");
// management configuration
require ("../../../config.inc.php");
// hyperCMS API
require ("../../../function/hypercms_api.inc.php");
// hyperCMS UI
require ("../../../function/hypercms_ui.inc.php");
// language file
require_once ("../lang/page.inc.php");

// input parameters
$site = getrequest_esc ("site", "publicationname");
$content = getrequest_esc ("content");

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="../../../javascript/main.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric" background="<?php echo getthemelocation(); ?>img/backgrd_empty.png">

<!-- top bar -->
<?php echo showtopbar ($text0[$lang], $lang); ?>

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
  <div id="scrollFrame" style="width:98%; height:700px; overflow:auto;">

  <div style="display:block; margin-bottom:20px;">
  <table border="0" cellspacing="2" cellpadding="0">
 	  <tr align="left" valign="top">
      <td class="hcmsHeadline"><?php echo $text1[$lang]; ?>: </td>
      <td><?php if ($space = disk_total_space($mgmt_config['abs_path_cms'])) echo number_format (($space/1024/1024/1024), 2, ",", ".")." GB"; else echo "not available"; ?></td>
    </tr>
 	  <tr align="left" valign="top">
      <td class="hcmsHeadline"><?php echo $text2[$lang]; ?>: </td>
      <td><?php if ($space = disk_free_space($mgmt_config['abs_path_cms'])) echo number_format (($space/1024/1024/1024), 2, ",", ".")." GB"; else echo "not available"; ?></td>
    </tr>
  </table>
  </div>
  
  <div style="float:left; margin-right:20px;">
  <p class=hcmsHeadline><?php echo $text3[$lang]; ?></p>
  <table border="0" cellspacing="2" cellpadding="3">
 	  <tr align="left" valign="top">
      <td class="hcmsHeadline"><?php echo $text4[$lang]; ?></td><td class="hcmsHeadline" align="right"><?php echo $text5[$lang]; ?></td><td class="hcmsHeadline" align="right"><?php echo $text6[$lang]; ?></td>
    </tr>
    <?php
    $show = "";
    
    // connect to MySQL
    $mysqli = new mysqli ($mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname']);      
    if ($mysqli->connect_errno) $show .= "<tr class=\"hcmsRowData1\"><td colspan=3 class=hcmsHeadline>DB error (".$mysqli->connect_errno."): ".$mysqli->connect_error."</td></tr>\n";
    
    if ($show == "")
    {
      $sql = "SELECT dailystat.id, object.objectpath, SUM(dailystat.count) count, SUM(media.filesize) filesize FROM dailystat, object, media WHERE dailystat.activity='download' AND dailystat.id=object.id AND dailystat.id=media.id GROUP BY dailystat.id ORDER BY count DESC LIMIT 0,20";
    
      if ($result = $mysqli->query ($sql))
      { 
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
          
          if ($setlocalpermission['root']) $link = "<a href=\"#\" onClick=\"hcms_openBrWindowItem('../../../frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($object)."','".$row['id']."','status=yes,scrollbars=no,resizable=yes','800','600');\">".$info['name']."</a>";
          else $link = $info['name'];
             
          $show .= "<tr class=\"hcmsRowData1\"><td>".$link."</td><td align=\"right\">".$row['count']."</td><td align=\"right\">".number_format ($row['filesize'], 0, ",", ".")."</td></tr>\n";
        }
      }
      else $show .= "<tr class=\"hcmsRowData1\"><td colspan=3 class=hcmsHeadline>DB error (".$mysqli->errno."): ".$mysqli->error."</td></tr>\n";
      
      echo $show;
      
      $result->close();
      $mysqli->close();
    }
    ?>
  </table>  
  </div>
  
  <div style="float:left; margin-right:20px;">
  <p class=hcmsHeadline><?php echo $text7[$lang]; ?></p>
  <table border="0" cellspacing="2" cellpadding="3">
 	  <tr align="left" valign="top">
      <td class="hcmsHeadline"><?php echo $text4[$lang]; ?></td><td class="hcmsHeadline" align="right"><?php echo $text5[$lang]; ?></td><td class="hcmsHeadline" align="right"><?php echo $text6[$lang]; ?></td>
    </tr>
    <?php
    $show = "";
    
    // connect to MySQL
    $mysqli = new mysqli ($mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname']);      
    if ($mysqli->connect_errno) $show .= "<tr class=\"hcmsRowData1\"><td colspan=3 class=hcmsHeadline>DB error (".$mysqli->connect_errno."): ".$mysqli->connect_error."</td></tr>\n";
    
    if ($show == "")
    {
      $sql = "SELECT dailystat.id, object.objectpath, SUM(dailystat.count) count, SUM(media.filesize) filesize FROM dailystat, object, media WHERE dailystat.activity='upload' AND dailystat.id=object.id AND dailystat.id=media.id GROUP BY dailystat.id ORDER BY count DESC LIMIT 0,20";
    
      if ($result = $mysqli->query ($sql))
      { 
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
          
          if ($setlocalpermission['root']) $link = "<a href=\"#\" onClick=\"hcms_openBrWindowItem('../../../frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($object)."','".$row['id']."','status=yes,scrollbars=no,resizable=yes','800','600');\">".$info['name']."</a>";
          else $link = $info['name'];
          
          $show .= "<tr class=\"hcmsRowData1\"><td>".$link."</td><td align=\"right\">".$row['count']."</td><td align=\"right\">".number_format ($row['filesize'], 0, ",", ".")."</td></tr>\n";
        }
      }
      else $show .= "<tr class=\"hcmsRowData1\"><td colspan=3 class=hcmsHeadline>DB error (".$mysqli->errno."): ".$mysqli->error."</td></tr>\n";
      
      echo $show;
      
      $result->close();
      $mysqli->close();
    }
    ?>
  </table>
  </div>
  
  </div>
</div>

</body>
</html>