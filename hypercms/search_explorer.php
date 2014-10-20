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
require_once ("language/search_explorer.inc.php");


// input parameters
$dir = getrequest_esc ("dir", "locationname");

// get publication and category
if ($dir != "" && $dir != "%page%/" && $dir != "%comp%/")
{
  $site = getpublication ($dir);
  $cat = getcategory ($site, $dir);
}

// convert location
$dir = deconvertpath ($dir, "file");
$dir_esc = convertpath ($site, $dir, $cat);

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// access permissions
$ownergroup = accesspermission ($site, $dir, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

// check localpermissions
if (
     ($dir != "" && $dir_esc != "%page%/" && $dir_esc != "%comp%/" && valid_publicationname ($site) && !valid_publicationaccess ($site)) || 
     !valid_locationname ($dir)
   ) killsession ($user);
       
// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// load publication inheritance setting
$inherit_db = inherit_db_read ();
$parent_array = inherit_db_getparent ($inherit_db, $site);

// define variables depending on content category and generate non-virtual child folders
if (substr_count ($dir_esc, "%page%/") > 0)
{
  $initial_dir = $mgmt_config[$site]['abs_path_page'];
}
elseif (substr_count ($dir_esc, "%comp%/") > 0)
{
  $initial_dir = $mgmt_config['abs_path_comp'];
}
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
function sendInput (search_dir, folder)
{
  if (eval (parent.frames['mainFrame2'].document.forms['searchform_general']))
  {
    parent.frames['mainFrame2'].document.forms['searchform_general'].elements['search_dir'].value = search_dir;
    parent.frames['mainFrame2'].document.forms['searchform_general'].elements['folder'].value = folder;
  }
  
  if (eval (parent.frames['mainFrame2'].document.forms['searchform_advanced']))
  {
    parent.frames['mainFrame2'].document.forms['searchform_advanced'].elements['search_dir'].value = search_dir;
    parent.frames['mainFrame2'].document.forms['searchform_advanced'].elements['folder'].value = folder; 
  }
  
  if (eval (parent.frames['mainFrame2'].document.forms['searchform_replace']))
  {
    parent.frames['mainFrame2'].document.forms['searchform_replace'].elements['search_dir'].value = search_dir;
    parent.frames['mainFrame2'].document.forms['searchform_replace'].elements['folder'].value = folder; 
  }
  
  if (eval (parent.frames['mainFrame2'].document.forms['searchform_images']))
  {
    parent.frames['mainFrame2'].document.forms['searchform_images'].elements['search_dir'].value = search_dir;
    parent.frames['mainFrame2'].document.forms['searchform_images'].elements['folder'].value = folder; 
  }   
}
//-->
</script>
</head>

<body class="hcmsWorkplaceObjectlist">

<div style="position:fixed; right:0; top:45%; margin:0; padding:0;">
  <img onclick="parent.document.getElementById('searchFrame').cols='42,*';" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_arrow_left.png" /><br />
  <img onclick="parent.document.getElementById('searchFrame').cols='250,*';" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_arrow_right.png" />
</div>

<div class="hcmsWorkplaceFrame">
<table width="98%" border="0" cellspacing="0" cellpadding="0">
  <tr align="left">
    <td class="hcmsHeadline" colspan="2" style="padding: 0px 0px 8px 0px;"><?php echo $text0[$lang]; ?></td>
  </tr>
<?php
$location_name = getlocationname ($site, $dir, $cat, "path");

echo "<tr><td align=\"left\" colspan=2 class=\"hcmsHeadlineTiny\" nowrap=\"nowrap\">".$location_name."</p></td></tr>\n";

// parent directory
$updir = getlocation ($dir);
$updir_esc = getlocation ($dir_esc);

$ownergroup_down = accesspermission ($site, $updir, $cat);
$setlocalpermission_updir = setlocalpermission ($site, $ownergroup_down, $cat);

// back to parent directory
if (
     $dir_esc != "" && 
     ($mgmt_config[$site]['dam'] == true && $setlocalpermission_updir['root'] == 1) ||
     ($updir != "" && $initial_dir != "" && substr_count ($updir, $initial_dir) == 1) && 
     (($hcms_linking['location'] != "" && $hcms_linking['object'] == "" && substr_count ($hcms_linking['location'], $dir) < 1) || $hcms_linking['location'] == "")
   )
{
  echo "<tr><td align=\"left\" colspan=2 nowrap=\"nowrap\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode($updir_esc)."\"><img src=\"".getthemelocation()."img/back.gif\" class=\"hcmsIconList\" />&nbsp; ".$text2[$lang]."</a></td></tr>\n";
}

// set dir
if ($dir == "") $dir = $initial_dir;

// get all files in dir
$outdir = @dir ($dir);

// get all outdir entries in folder and file array
if ($outdir != false)
{
  while ($entry = $outdir->read())
  {
    if ($entry != "" && $entry != "." && $entry != "..")
    {      
      if ($cat == "page" || ($cat == "comp" && ($dir != $mgmt_config['abs_path_comp'] || ($dir == $mgmt_config['abs_path_comp'] && ($parent_array != false && in_array ($entry, $parent_array)) || $entry == $site))))
      {
        if (is_dir ($dir.$entry) && accessgeneral ($site, $dir.$entry, $cat) == true)
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
    
    // define icon and publication
    if ($cat == "comp" && $dir == $mgmt_config['abs_path_comp']) $icon = getthemelocation()."img/site.gif";
    else $icon = getthemelocation()."img/folder.gif";      

    foreach ($entry_dir as $dirname)
    {
      // define variables
      $sdirname = $dirname."/";
      $folder_name = $location_name.$sdirname;
      
      $folder_name = specialchr_decode ($dirname);
      
      if ($cat == "comp" && $dir == $mgmt_config['abs_path_comp']) $site_name = $dirname;
      else $site_name = $site;

      echo "<tr><td width=\"85%\" align=\"left\" nowrap=\"nowrap\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode($dir_esc.$sdirname)."\"><img src=\"".$icon."\" class=\"hcmsIconList\" />&nbsp; ".$folder_name."</a></td>
      <td width=\"18\" align=\"left\" nowrap=\"nowrap\"><a href=\"javascript:sendInput('".$dir_esc.$sdirname."', '".str_replace ($initial_dir, "/", $location_name.$folder_name."/")."')\"><img src=\"".getthemelocation()."img/button_OK_small.gif\" class=\"hcmsIconList\" title=\"OK\" alt=\"OK\" /></a></td></tr>\n";
    }
  }
}
?>
</table>
</div>

</body>
</html>
