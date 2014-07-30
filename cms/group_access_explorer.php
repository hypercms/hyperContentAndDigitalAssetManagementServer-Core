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
require_once ("language/group_access_explorer.inc.php");


// input parameters
$site = getrequest_esc ("site", "publicationname");
$cat = getrequest_esc ("cat", "objectname");
$dir = getrequest_esc ("dir", "locationname");
$group_name = getrequest_esc ("group_name", "objectname");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!in_array ($cat, array("page","comp")) || ($dir != "" && $dir != "%".$cat."%/" && !accessgeneral ($site, $dir, $cat)) || $globalpermission[$site]['group'] != 1 || ($globalpermission[$site]['groupcreate'] != 1 && $globalpermission[$site]['groupedit'] != 1) || !valid_publicationname ($site))  killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/navigator.css">
<script src="javascript/click.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
function sendOption(folder_name, folder_location)
{
  parent.mainFrame2.insertOption(folder_name, folder_location);
}
//-->
</script>
</head>

<body class="hcmsWorkplaceExplorer">

<table width="98%" border="0" cellspacing="2" cellpadding="0">
  <tr>
    <td class="hcmsHeadline" align="left" colspan="2" style="padding: 0px 0px 8px 0px;"><?php echo $text0[$lang]; ?></td>
  </tr>
<?php
if (($cat == "page" && $mgmt_config[$site]['abs_path_page'] != "") || $cat == "comp")
{    
  // define variables depending on content category
  if ($cat == "page")
  {
    $folder_name = $text3[$lang];
    $initialdir = $mgmt_config[$site]['abs_path_page'];
    $initialdir_esc = convertpath ($site, $initialdir, $cat);
  }
  elseif ($cat == "comp")
  {
    $folder_name = $text1[$lang];
    $initialdir = $mgmt_config['abs_path_comp'].$site."/";
    $initialdir_esc = convertpath ($site, $initialdir, $cat);
  }
  
  // convert path
  if ($dir != "")
  {
    $dir = deconvertpath ($dir, "file");
    $dir_esc = convertpath ($site, $dir, $cat);
  }
  
  // generate virtual root directories
  if (substr_count ($dir, $initialdir) == 0)
  {
    echo "<tr><td align=\"left\" nowrap=\"nowrap\"><a href=\"".$_SERVER['PHP_SELF']."?site=".url_encode($site)."&cat=".url_encode($cat)."&dir=".url_encode($initialdir_esc)."&group_name=".url_encode($group_name)."\"><img src=\"".getthemelocation()."img/folder_".$cat.".gif\" border=0 align=\"absmiddle\">&nbsp; ".$folder_name."</a></td><td align=\"right\" nowrap=\"nowrap\"><a href=\"javascript:sendOption('/".$site."/', '%".$cat."%/".$site."/');\"><img src=\"".getthemelocation()."img/button_OK_small.gif\" style=\"border:0; width:16px; height:16px;\" align=\"absmiddle\" alt=\"OK\" /></a></td></tr>\n";
  }
  else
  {
    // define root location if no location data is available
    if (!valid_locationname ($dir))
    {
      $dir = $initialdir;
      $dir_esc = $initialdir_esc;
    }
    
    // location
    $location_name = getlocationname ($site, $dir, $cat, "path");
    echo "<tr><td align=\"left\" colspan=2 class=\"hcmsHeadlineTiny\" nowrap=\"nowrap\">".$location_name."</td></tr>\n";  
    
    // get (up) parent directory
    $updir_esc = getlocation ($dir_esc);
  
    // back to parent directory
    if (substr_count ($dir, $initialdir) > 0)
    {
      echo "<tr><td align=\"left\" colspan=2 nowrap=\"nowrap\"><a href=\"".$_SERVER['PHP_SELF']."?site=".url_encode($site)."&cat=".url_encode($cat)."&dir=".url_encode($updir_esc)."&group_name=".url_encode($group_name)."\"><img src=\"".getthemelocation()."img/back.gif\" style=\"border:0; width:16px; heigth:16px;\" align=\"absmiddle\" />&nbsp;".$text2[$lang]."</a></td></tr>\n";
    }
  
    // get all files in dir
    $outdir = @dir ($dir);
  
    // get all outdir entries in folder and file array
    if ($outdir != false)
    {
      while ($entry = $outdir->read())
      {
        if ($entry != "." && $entry != ".." && $entry != "" && accessgeneral ($site, $dir.$entry, $cat))
        {     
          if ($cat == "page")
          {
            if (is_dir ($dir.$entry))
            {
              $entry_dir[] = $entry;
            }
          }
          elseif ($cat == "comp" && substr_count ($dir, $initialdir) > 0)
          {
            if (is_dir ($dir.$entry))
            {
              $entry_dir[] = $entry;
            }
          }        
        }
      }
      
      $outdir->close();
    
      // directory
      if (sizeof ($entry_dir) >= 1)
      {
        sort ($entry_dir);
        reset ($entry_dir);
        
        foreach ($entry_dir as $folder)
        {
          // folder name
          $folder_data = getfileinfo($site, $dir.$folder.'/.folder', $cat);
          $folder_name = $folder_data['name'];
          $icon = getthemelocation()."img/".$folder_data['icon'];
  
          echo "<tr><td width=\"90%\" align=\"left\" nowrap=\"nowrap\"><a href=\"".$_SERVER['PHP_SELF']."?site=".url_encode($site)."&cat=".url_encode($cat)."&dir=".url_encode($dir_esc.$folder)."/&group_name=".url_encode($group_name)."\"><img src=\"".$icon."\" border=0 width=16 heigth=16 align=\"absmiddle\" />&nbsp;".$folder_name."</a></td><td align=\"right\" nowrap=\"nowrap\"><a href=# onClick=\"sendOption('".$location_name.$folder_name."/', '".$dir_esc.$folder."/');\"><img src=\"".getthemelocation()."img/button_OK_small.gif\" border=0 width=16 height=16 align=\"absmiddle\" alt=\"OK\" /></a></td></tr>\n";
        }
      }
    }
  }
}
?>
</table>

</body>
</html>