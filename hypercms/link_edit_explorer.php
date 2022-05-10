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
$site = getrequest_esc ("site", "publicationname");
$dir = getrequest_esc ("dir", "locationname");
$search_expression = getrequest ("search_expression");

// get publication and category
if ($dir != "") $site = getpublication ($dir);

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check access permission
if ((!empty ($dir) && !accessgeneral ($site, $dir, "page")) || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// initialize
$cat = "page";
$entry_dir = array();
$entry_file = array();

// convert location
$dir = deconvertpath ($dir, "file");

// get last location in page structure
if (!valid_locationname ($dir) && !empty ($temp_pagelocation[$site])) 
{
  $dir = $temp_pagelocation[$site];
  
  if (!is_dir ($dir))
  {
    $dir = "";

    unset ($temp_pagelocation[$site]);

    setsession ('hcms_temp_pagelocation', $temp_pagelocation);
  }
}
// use provided page location
elseif (valid_locationname ($dir))
{
  if (!isset ($temp_pagelocation)) $temp_pagelocation = array();

  $temp_pagelocation[$site] = $dir;

  setsession ('hcms_temp_pagelocation', $temp_pagelocation);
}

// define root location if no location data is available
if (!valid_locationname ($dir) && !empty ($mgmt_config[$site]['abs_path_page']))
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
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
<!-- Jquery and Jquery UI Autocomplete -->
<script type="text/javascript" src="javascript/jquery/jquery.min.js"></script>
<script type="text/javascript" src="javascript/jquery-ui/jquery-ui.min.js"></script>
<link rel="stylesheet" href="javascript/jquery-ui/jquery-ui.css" />
<script type="text/javascript">

function sendInput(text, value)
{
  parent.frames['mainFrame2'].document.forms['link'].elements['link_name'].value = text;
  parent.frames['mainFrame2'].document.forms['link'].elements['linkhref'].value = value;
  parent.frames['mainFrame2'].refreshPreview();
}

function submitForm ()
{
  if (document.forms['searchform_general'])
  {
    var form = document.forms['searchform_general'];  
    form.submit();
  }
}

$(document).ready(function()
{
  <?php
  $keywords = getsearchhistory ($user);
  ?>
  var available_expressions = [<?php if (is_array ($keywords)) echo implode (",\n", $keywords); ?>];

  $("#search_expression").autocomplete({
    source: available_expressions
  });
});
</script>
</head>

<body class="hcmsWorkplaceObjectlist">

<div id="NavFrameButtons" style="position:fixed; right:0; top:45%; margin:0; padding:0;">
  <img onclick="parent.minNavFrame();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_arrow_left.png" /><br />
  <img onclick="parent.maxNavFrame();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_arrow_right.png" />
</div>

<div id="Navigator" class="hcmsWorkplaceFrame">
  <span class="hcmsHeadline" style="padding:3px 0px 3px 0px; display:block;"><?php echo getescapedtext ($hcms_lang['select-object'][$lang]); ?></span>
  <span class="hcmsHeadlineTiny" style="padding:3px 0px 3px 0px; display:block; white-space:nowrap;"><?php echo $location_name; ?></span>
  
  <?php if ($mgmt_config['db_connect_rdbms'] != "") { ?>
  <div id="searchForm" style="padding:2px; width:100%;">
    <form name="searchform_general" action="" method="post">
      <input type="hidden" name="dir" value="<?php echo $dir_esc; ?>" />
      <input type="hidden" name="site" value="<?php echo $site; ?>" />
      <input type="text" name="search_expression" id="search_expression" placeholder="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" value="<?php if (!empty ($search_expression)) echo html_encode ($search_expression); ?>" 
      style="width:184px;" maxlength="2000" /><img name="SearchButton" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="submitForm();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('SearchButton','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
    </form>
  </div>
  <?php } ?>

  <table class="hcmsTableNarrow" style="table-layout:auto; min-width:218px;">
<?php
if (!empty ($dir) && !empty ($site))
{  
  // parent directory
  if (substr_count ($dir, $mgmt_config[$site]['abs_path_page']) > 0 && $dir != $mgmt_config[$site]['abs_path_page'])
  {
    //get parent directory
    $updir_esc = getlocation ($dir_esc);
  
    echo "
    <tr>
      <td colspan=\"2\" style=\"text-align:left; white-space:nowrap;\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode($updir_esc)."&site=".url_encode($site)."\"><img src=\"".getthemelocation()."img/back.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['back'][$lang])."</a></td>
    </tr>";
  }
  
  // search results
  if (trim ($search_expression) != "")
  {
    $object_array = rdbms_searchcontent ($dir_esc, "", array("page"), "", "", "", array($search_expression), $search_expression, "", "", "", "", "", "", "", "", 100);
    
    if (is_array ($object_array))
    {
      foreach ($object_array as $hash=>$object_item)
      {
        $entry = $object_item['objectpath'];
        
        if ($hash != "count" && $entry != "" && accessgeneral ($site, $entry, "page"))
        {
          $entry_location = getlocation ($entry);
          $entry_object = getobject ($entry);
          $entry_object = correctfile ($entry_location, $entry_object, $user);
          
          if ($entry_object !== false)
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
        if ($entry != "" && $entry != "." && $entry != ".." && accessgeneral ($site, $dir.$entry, "page"))
        {        
          if (is_dir ($dir.$entry))
          {
            $entry_dir[] = $dir_esc.$entry."/.folder";
          }
          elseif (is_file ($dir.$entry) && !is_hiddenfile ($entry))
          {
            $entry_file[] = $dir_esc.$entry;
          }
        }
      }
      
      $outdir->close();
    }
  }
  
  // directory
  if (is_array ($entry_dir) && sizeof ($entry_dir) > 0)
  {
    natcasesort ($entry_dir);
    reset ($entry_dir);
  
    foreach ($entry_dir as $dirname)
    {
      // folder info
      $folder_info = getfileinfo ($site, $dirname, "page");
      
      if ($dirname != "" && $folder_info['deleted'] == false)
      {
        $folder_path = getlocation ($dirname);
        $location_name = getlocationname ($site, $folder_path, "page", "path"); 
    
        if ($folder_info != false && $folder_info['deleted'] == false)
        {
          echo "
      <tr>
        <td colspan=\"2\" style=\"text-align:left; white-space:nowrap;\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".$folder_path."\" title=\"".$location_name."\"><img src=\"".getthemelocation()."img/folder.png\" class=\"hcmsIconList\" /> ".showshorttext($folder_info['name'], 24)."</a></td>
      </tr>";
        }
      }
    }
  }
  
  // file
  if (is_array ($entry_file) && sizeof ($entry_file) > 0)
  {
    natcasesort ($entry_file);
    reset ($entry_file);
  
    foreach ($entry_file as $file)
    {
      // object info
      $file_info = getfileinfo ($site, $file, "page");
      
      if ($file != "" && $file_info['deleted'] == false)
      {
        $file_name = getlocationname ($site, $file, "page", "path");
        $file_path = $file;
    
        if ($file_info != false && $file_info['published'] == true && $file_info['deleted'] == false)
        {
          echo "
      <tr>
        <td style=\"text-align:left; white-space:nowrap;\"><a href=\"javascript:void(0);\" onClick=\"sendInput('".$file_name."', '".$file_path."')\" title=\"".$file_name."\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" class=\"hcmsIconList\" /> ".showshorttext($file_info['name'], 24)."</a></td>
        <td style=\"width:20px; text-align:right;\"><a href=\"javascript:void(0);\" onClick=\"sendInput('".$file_name."', '".$file_path."')\"><img src=\"".getthemelocation()."img/button_ok.png\" class=\"hcmsIconList\" alt=\"OK\" title=\"OK\" /></a></td>
      </tr>";
        }
      }
    }
  }
}
?>
</table>
</div>

</body>
</html>
