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


// input parameters
$site = getrequest_esc ("site", "publicationname");
$dir = getrequest_esc ("dir", "locationname");
$search_expression = getrequest ("search_expression");

// get publication and category
if ($dir != "") $site = getpublication ($dir);
$cat = "page";

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check access permission
if ($mgmt_config[$site]['dam'] == true || ($dir != "" && !accessgeneral ($site, $dir, "page")) || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// convert location
$dir = deconvertpath ($dir, "file");

// get last location in page structure
if (!valid_locationname ($dir) && isset ($temp_pagelocation[$site])) 
{
  $dir = $temp_pagelocation[$site];
  
  if (!is_dir ($dir))
  {
    $dir = "";
    $temp_pagelocation[$site] = null;
    setsession ('hcms_temp_pagelocation', $temp_pagelocation);
  }
}
elseif (valid_locationname ($dir))
{
  $temp_pagelocation[$site] = $dir;
  setsession ('hcms_temp_pagelocation', $temp_pagelocation);
}

// define root location if no location data is available
if (!valid_locationname ($dir))
{
  $dir = $mgmt_config[$site]['abs_path_page'];
}

// convert path
$dir_esc = convertpath ($site, $dir, "page");
$location_name = getlocationname ($site, $dir_esc, "page", "path");
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/navigator.css" />
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
function sendInput(text, value)
{
  parent.frames['mainFrame2'].document.forms['link'].elements['link_name'].value = text;
  parent.frames['mainFrame2'].document.forms['link'].elements['linkhref'].value = value;
  parent.frames['mainFrame2'].refreshPreview();
}
//-->
</script>
</head>

<body class="hcmsWorkplaceObjectlist">

<div style="position:fixed; right:0; top:45%; margin:0; padding:0;">
  <img onclick="parent.minNavFrame();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_arrow_left.png" /><br />
  <img onclick="parent.maxNavFrame();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_arrow_right.png" />
</div>

<div class="hcmsWorkplaceFrame">
<table width="98%" border="0" cellspacing="2" cellpadding="0">
  <tr>
    <td class="hcmsHeadline" style="padding:3px 0px 3px 0px;" align="left" colspan="2"><?php echo getescapedtext ($hcms_lang['select-object'][$lang]); ?><td>
  </tr>
  <tr>
    <td class="hcmsHeadlineTiny" align="left" colspan="2" nowrap="nowrap"><?php echo $location_name; ?></td>
  </tr>
  <?php if ($mgmt_config['db_connect_rdbms'] != "") { ?>
  <tr>
    <td align="left" colspan="2">
    <form name="searchform_general" action="" method="post">
      <input type="hidden" name="dir" value="<?php echo $dir_esc; ?>" />
      <input type="hidden" name="site" value="<?php echo $site; ?>" />
      <input type="text" name="search_expression" value="<?php if ($search_expression != "") echo html_encode ($search_expression); else echo getescapedtext ($hcms_lang['search-expression'][$lang]); ?>" onblur="if (this.value=='') this.value='<?php echo getescapedtext ($hcms_lang['search-expression'][$lang]); ?>';" onfocus="if (this.value=='<?php echo getescapedtext ($hcms_lang['search-expression'][$lang]); ?>') this.value='';" style="width:190px;" maxlength="60" />
      <img name="SearchButton" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="document.forms['searchform_general'].submit();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('SearchButton','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="top" alt="OK" title="OK" />
    </form>
  </td>
 </tr>
 <?php } ?>

<?php
// parent directory
if (substr_count ($dir, $mgmt_config[$site]['abs_path_page']) > 0 && $dir != $mgmt_config[$site]['abs_path_page'])
{
  //get parent directory
  $updir_esc = getlocation ($dir_esc);

  echo "<tr><td align=\"left\" colspan=2 nowrap=\"nowrap\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode($updir_esc)."&site=".url_encode($site)."\"><img src=\"".getthemelocation()."img/back.gif\" style=\"border:0; width:16px; heigth:16px;\" align=\"absmiddle\" />&nbsp; ".getescapedtext ($hcms_lang['back'][$lang])."</a></td></tr>\n";
}

// search results
if ($search_expression != "")
{
  $object_array = rdbms_searchcontent ($dir_esc, "", array("page"), "", "", "", array($search_expression), $search_expression, "", "", "", "", "", "", "", 100);
  
  if (is_array ($object_array))
  {
    foreach ($object_array as $entry)
    {
      if ($entry != "" && accessgeneral ($site, $entry, "page"))
      {
        $entry_location = getlocation ($entry);
        $entry_object = getobject ($entry);
        $entry_object = correctfile ($entry_location, $entry_object, $user);
        
        if ($entry_object != false)
        {
          if ($entry_object == ".folder")
          {
            $entry_dir[] = $entry_location.$entry_object;
          }
          else
          {
            $entry_file[] = $entry_location.$entry_object;
          }
        }
      }
    }
  }
}
// file explorer
else
{
  // get all files in dir
  $outdir = @dir ($dir);

  // get all outdir entries in folder and file array
  if ($outdir != false)
  {
    while ($entry = $outdir->read())
    {
      if ($entry != "" && $entry != "." && $entry != ".." && $entry != ".folder" && accessgeneral ($site, $dir.$entry, "page"))
      {        
        if (is_dir ($dir.$entry))
        {
          $entry_dir[] = $dir_esc.$entry."/.folder";
        }
        elseif (is_file ($dir.$entry))
        {
          $entry_file[] = $dir_esc.$entry;
        }
      }
    }
    
    $outdir->close();
  }
}

// directory
if (isset ($entry_dir) && sizeof ($entry_dir) > 0)
{
  natcasesort ($entry_dir);
  reset ($entry_dir);

  foreach ($entry_dir as $dirname)
  {
    if ($dirname != "")
    {
      // folder info
      $folder_info = getfileinfo ($site, $dirname, "page");
      $folder_path = getlocation ($dirname);
      $location_name = getlocationname ($site, $folder_path, "page", "path"); 
  
      if ($folder_info != false)
      {
        echo "<tr><td align=\"left\" colspan=2 nowrap=\"nowrap\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".$folder_path."\" title=\"".$location_name."\"><img src=\"".getthemelocation()."img/folder.gif\" align=\"absmiddle\" style=\"border:0; width:16px; heigth:16px;\" />&nbsp;".showshorttext($folder_info['name'], 24)."</a></td></tr>\n";
      }
    }
  }
}

// file
if (isset ($entry_file) && sizeof ($entry_file) > 0)
{
  natcasesort ($entry_file);
  reset ($entry_file);

  foreach ($entry_file as $file)
  {
    if ($file != "")
    {
      // object info
      $file_info = getfileinfo ($site, $file, "page");
      $file_name = getlocationname ($site, $file, "page", "path");
      $file_path = $file;

      if ($file_info != false && $file_info['published'] == true)
      {
        echo "<tr><td width=\"85%\" align=\"left\" nowrap=\"nowrap\"><a href=# onClick=\"sendInput('".$file_name."', '".$file_path."')\" title=\"".$file_name."\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" align=\"absmiddle\" style=\"border:0; width:16px; heigth:16px;\" />&nbsp; ".showshorttext($file_info['name'], 24)."</a></td><td align=\"left\" nowrap=\"nowrap\"><a href=# onClick=\"sendInput('".$file_name."', '".$file_path."')\"><img src=\"".getthemelocation()."img/button_OK_small.gif\" style=\"border:0; width:16px; heigth:16px;\" align=\"absmiddle\" alt=\"OK\" title=\"OK\" /></a></td></tr>\n";
      }
    }
  }
}
?>
</table>
</div>

</body>
</html>
