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
$cat = getrequest_esc ("cat", "objectname");
$dir = getrequest_esc ("dir", "locationname");

// publication management config
if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
{
  require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
}

// ------------------------------ permission section --------------------------------

// check permissions
if (!in_array ($cat, array("page","comp")) || ($dir != "" && $dir != "%".$cat."%/" && !accessgeneral ($site, $dir, $cat)) || !checkglobalpermission ($site, 'template') || !checkglobalpermission ($site, 'tpl') || !checkglobalpermission ($site, 'tpledit') || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/click.min.js"></script>
<script type="text/javascript">

function sendOption(folder_name, folder_location)
{
  parent.mainFrame2.insertOption(folder_name, folder_location);
}
</script>
</head>

<body class="hcmsWorkplaceObjectlist">

<div id="NavFrameButtons" style="position:fixed; right:0; top:45%; margin:0; padding:0;">
  <img onclick="parent.minNavFrame();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_arrow_left.png" /><br />
  <img onclick="parent.maxNavFrame();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_arrow_right.png" />
</div>

<div id="Navigator" class="hcmsWorkplaceFrame">
  <span class="hcmsHeadline" style="padding:3px 0px 3px 0px; display:block;"><?php echo getescapedtext ($hcms_lang['select-folder'][$lang]); ?></span>

  <table class="hcmsTableNarrow" style="table-layout:auto; min-width:218px;">
  <?php
  if ($cat == "page" || $cat == "comp")
  {  
    // define variables depending on content category
    if ($cat == "page")
    {
      $folder_name = getescapedtext ($hcms_lang['pages'][$lang]);
      $initialdir = $mgmt_config[$site]['abs_path_page'];
      $initialdir_esc = convertpath ($site, $initialdir, $cat);
    }
    else
    {
      $folder_name = getescapedtext ($hcms_lang['assets'][$lang]);
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
    if (!empty ($dir) && substr_count ($dir, $initialdir) == 0)
    {
      echo "
      <tr>
        <td style=\"text-align:left; white-space:nowrap;\"><a href=\"".$_SERVER['PHP_SELF']."?site=".url_encode($site)."&cat=".url_encode($cat)."&dir=".url_encode($initialdir_esc)."\"><img src=\"".getthemelocation()."img/folder_".$cat.".png\" class=\"hcmsIconList\" /> ".showshorttext($folder_name, 24, false)."</a></td>
        <td style=\"text-align:right; white-space:nowrap;\"><a href=\"javascript:sendOption('/".$site."/', '%".$cat."%/".$site."/')\"><img src=\"".getthemelocation()."img/button_ok.png\" class=\"hcmsIconList\" alt=\"OK\" /></a></td>
      </tr>";
    }
    else
    {
      // define root location if no location data is available
      if (!valid_locationname ($dir) || empty ($dir))
      {
        $dir = $initialdir;
        $dir_esc = $initialdir_esc;
      }
      
      if (!empty ($dir))
      {
        // location
        $location_name = getlocationname ($site, $dir, $cat, "path");   
         
        echo "
        <tr>
          <td colspan=\"2\" class=\"hcmsHeadlineTiny\" style=\"text-align:left; white-space:nowrap;\">".$location_name."</td>
        </tr>";
    
        // get (up) parent directory
        $updir_esc = getlocation ($dir_esc);
        
        // back to parent directory
        if (substr_count ($dir, $initialdir) > 0)
        {
          echo "
        <tr>
          <td colspan=\"2\" style=\"text-align:left; white-space:nowrap;\"><a href=\"".$_SERVER['PHP_SELF']."?cat=".url_encode($cat)."&dir=".url_encode($updir_esc)."&site=".url_encode($site)."\"><img src=\"".getthemelocation()."img/back.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['back'][$lang])."</a></td>
        </tr>";
        }

        echo "
        </table>
        
        <table class=\"hcmsTableNarrow\" style=\"table-layout:auto; min-width:218px;\">";
        
        // get all files in dir
        $scandir = scandir ($dir);

        $entry_dir = array();
        
        // get all outdir entries in folder and file array
        if (!empty ($scandir))
        {
          foreach ($scandir as $entry)
          {
            if ($entry != "" && $entry != "." && $entry != "..")
            {
              if (is_dir ($dir.$entry) && accessgeneral ($site, $dir.$entry, $cat) == true)
              {
                $entry_dir[] = $entry;
              }
            }
          }
          
          // directory
          if (!empty ($entry_dir) && sizeof ($entry_dir) > 0)
          {
            sort ($entry_dir);
            reset ($entry_dir);
          
            foreach ($entry_dir as $folder)
            {
              // folder info
            	$folder_info = getfileinfo ($site, $dir.$folder.'/.folder', $cat);
              
              if ($folder != "" && $folder_info['deleted'] == false)
              {
              	$folder_name = $folder_info['name'];
              	$icon = getthemelocation()."img/".$folder_info['icon'];
            
                if ($folder_info != false && $folder_info['deleted'] == false)
                {
                  echo "
        <tr>
          <td style=\"text-align:left; white-space:nowrap;\"><a href=\"".$_SERVER['PHP_SELF']."?cat=".url_encode($cat)."&dir=".url_encode($dir_esc.$folder)."/&site=".url_encode($site)."\"><img src=\"".$icon."\" class=\"hcmsIconList\" /> ".showshorttext($folder_name, 24, false)."</a></td>
          <td style=\"width:20px; text-align:right; white-space:nowrap;\"><a href=\"javascript:void(0);\" onclick=\"sendOption('".$location_name.$folder_name."/', '".$dir_esc.$folder."/');\"><img src=\"".getthemelocation()."img/button_ok.png\" class=\"hcmsIconList\" title=\"OK\" alt=\"OK\" /></a></td>
        </tr>";
                }
              }
            }
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
