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
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");


// input parameters
$site = getrequest_esc ("site", "publicationname");
$dir = getrequest ("dir", "locationname");
$CKEditorFuncNum = getrequest_esc ("CKEditorFuncNum", false, "", true);
$search_expression = getrequest ("search_expression");
if ($lang == "" || empty ($_REQUEST['lang'])) $lang = getrequest_esc ("langCode");
elseif ($lang == "") $lang = getrequest_esc ("lang");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
// publication management config for live system
if (valid_publicationname ($site)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini");

// ------------------------------ permission section --------------------------------

// check access permission
if ($mgmt_config[$site]['dam'] == true || ($dir != "" && !accessgeneral ($site, $dir, "page"))) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$callback = $CKEditorFuncNum;

// convert path
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
<title>Page Browser</title>
<meta name="viewport" content="width=device-width; initial-scale=1.0; user-scalable=1;" />
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/navigator.css" />
<script src="../javascript/click.js" type="text/javascript"></script>
<script src="../javascript/main.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
function popupfocus ()
{
  self.focus();
  setTimeout('popupfocus()', 100);
}

popupfocus ();

function submitLink (url)
{
  window.top.opener.CKEDITOR.tools.callFunction(<?php echo $callback; ?>, url);
  window.top.close();
  return true;
}
//-->
</script>
</head>

<body class="hcmsWorkplaceObjectlist">
  
  <!-- top bar -->
  <?php echo showtopbar ($hcms_lang['select-object'][$lang], $lang); ?>
  
  <div class="hcmsWorkplaceFrame">
    <span class="hcmsHeadlineTiny" style="padding:0px 0px 5px 0px; display:block;"><?php echo $location_name; ?></span>
  
    <?php if ($mgmt_config['db_connect_rdbms'] != "") { ?>
    <div style="display:block;">
    <form name="searchform_general" method="post">
      <input type="hidden" name="site" value="<?php echo $site; ?>" />
      <input type="hidden" name="dir" value="<?php echo $dir_esc; ?>" />
      <input type="text" name="search_expression" value="<?php if ($search_expression != "") echo html_encode ($search_expression); else echo $hcms_lang['search-expression'][$lang]; ?>" onblur="if (this.value=='') this.value='<?php echo $hcms_lang['search-expression'][$lang]; ?>';" onfocus="if (this.value=='<?php echo $hcms_lang['search-expression'][$lang]; ?>') this.value='';" style="width:210px;" maxlength="60" />
      <img name="SearchButton" src="<?php echo getthemelocation(); ?>img/button_OK.gif" onclick="document.forms['searchform_general'].submit();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('SearchButton','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="top" alt="OK" title="OK" />
    </form>
    </div>
    <?php } ?>
    <hr />
    <table width="98%" border="0" cellspacing="0" cellpadding="0">
    <?php
    if ($site != "")
    {  
      // parent directory
      if (substr_count ($dir, $mgmt_config[$site]['abs_path_page']) > 0 && $dir != $mgmt_config[$site]['abs_path_page'])
      {
        //get parent directory
        $updir_esc = getlocation ($dir_esc);  
        
        echo "<tr class=\"hcmsWorkplaceGeneric\"><td align=\"left\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode($updir_esc)."&site=".url_encode($site)."&lang=".url_encode($lang)."&CKEditorFuncNum=".url_encode($callback)."\"><img src=\"".getthemelocation()."img/back.gif\" align=\"absmiddle\" style=\"border:0; width:16px; heigth:16px;\" />&nbsp;".$hcms_lang['back'][$lang]."</a></td></tr>\n";
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
              $location = getlocation ($entry);
              $object = getobject ($entry);
              $object = correctfile ($location, $object, $user);
              
              if ($object !== false)
              {
                if ($object == ".folder")
                {
                  $entry_dir[] = $location.$object;
                }
                else
                {
                  $entry_file[] = $location.$object;
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
        $scandir = scandir ($dir);
        
        // get all outdir entries in folder and file array
        if ($scandir)
        {
          foreach ($scandir as $entry)
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
        }
      }  
      
      // directory
      if (!empty ($entry_dir) && sizeof ($entry_dir) > 0)
      {
        natcasesort ($entry_dir);
        reset ($entry_dir);
      
        foreach ($entry_dir as $dirname)
        { 
          // folder info
          $folder_info = getfileinfo ($site, $dirname, "page");
          $folder_path = getlocation ($dirname);
          $location_name = getlocationname ($site, $folder_path, "page", "path");   
      
          if ($folder_info != false && $folder_info['deleted'] == false)
          {    
            echo "<tr><td align=\"left\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode($folder_path)."&site=".url_encode($site)."&lang=".url_encode($lang)."&CKEditorFuncNum=".url_encode($callback)."\" title=\"".$location_name."\"><img src=\"".getthemelocation()."img/folder.gif\" align=\"absmiddle\" style=\"border:0; width:16px; heigth:16px;\" />&nbsp;".showshorttext($folder_info['name'], 44)."</a></td></tr>\n";
          }
        }
      }
      
      // file
      if (!empty ($entry_file) && sizeof ($entry_file) > 0)
      {
        natcasesort ($entry_file);
        reset ($entry_file);
      
        foreach ($entry_file as $file)
        {
          // object info
          $file_info = getfileinfo ($site, $file, "page");      
          $file_url = str_replace ("%page%/".$site."/", $mgmt_config[$site]['url_path_page'], $file);
          $file_name = getlocationname ($site, $file, "page", "path");
  
          if ($file_info != false && $file_info['published'] == true && $file_info['deleted'] == false)
          {
            echo "<tr><td align=\"left\"><a href=\"javascript:submitLink('".$file_url."');\" title=\"".$file_name."\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" align=\"absmiddle\" style=\"border:0; width:16px; heigth:16px;\" />&nbsp;".showshorttext($file_info['name'], 44)."</a></td></tr>\n";
          }
        }
      }
    }
    ?>
  </table>
  </div>

  <?php
  if ($site == "") echo showmessage ($hcms_lang['required-input-is-missing'][$lang], 600, 70, $lang, "position:absolute; left:20px; top:20px;");
  ?>

</body>
</html>
