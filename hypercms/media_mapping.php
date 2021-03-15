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
$mapping_data = getrequest ("mapping_data"); // version before 7.0.2 posted plain text
$mapping_texttype = getrequest ("mapping_texttype", "array");
$mapping_textid = getrequest ("mapping_textid", "array");
$save = getrequest ("save");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'template') || !checkglobalpermission ($site, 'tpl') || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script type="text/javascript" src="javascript/main.min.js"></script>
<script type="text/javascript">

function checkReindex ()
{
  check = confirm ("<?php echo getescapedtext ($hcms_lang['reindex-content-of-all-media-files'][$lang]); ?>");

  if (check == true)
  {   
    hcms_showFormLayer ('savelayer', 0);
    document.forms['reindex'].submit();
  }
}
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- saving --> 
<div id="savelayer" class="hcmsLoadScreen"></div>

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<?php
// save mapping
if (valid_publicationname ($site) && $save == "yes" && checktoken ($token, $user))
{
  // create mapping from form fields
  if (!empty ($mapping_texttype) && is_array ($mapping_texttype) && sizeof ($mapping_texttype) > 0 && !empty ($mapping_textid) && is_array ($mapping_textid) && sizeof ($mapping_textid) > 0)
  {
    // unescape > and double quotes
    $mapping_data = str_replace ("&gt;", ">", stripslashes ($mapping_data));
    $mapping_array = explode (PHP_EOL, $mapping_data);

    // update values in mapping data
    foreach ($mapping_textid as $metatag => $textid)
    {
      if (trim ($textid) != "")
      {
        $value = trim ($mapping_texttype[$metatag]).":".trim ($textid);
  
        // replace values in mapping data
        foreach ($mapping_array as $key => $line)
        {
          if (substr_count ($line, $metatag) == 1 && substr_count ($line, "=>") == 1)
          {
            $mapping_array[$key] = $metatag." => '".$value."'";
          }
          else $mapping_array[$key] = $line;
        }
      }
    }
    
    // stringify array
    if (is_array ($mapping_array) && sizeof ($mapping_array) > 0) $mapping_data = implode (PHP_EOL, $mapping_array);
  }

  // create mapping from plain text
  if (!empty ($mapping_data))
  {
    $mapping_data_save = createmapping ($site, $mapping_data);
  }
  
  if ($mapping_data_save == false) $show = "<p class=hcmsHeadline>".getescapedtext ($hcms_lang['error-while-saving'][$lang])."</p>\n".getescapedtext ($hcms_lang['you-do-not-have-write-permissions'][$lang])."\n";
}

// load mapping
if (valid_publicationname ($site))
{
  $mapping_data = showmapping ($site, $lang);
}

// reindex all media files
if (valid_publicationname ($site) && $save == "reindex" && checktoken ($token, $user))
{
  // creating mapping from definition
  $result = reindexcontent ($site);
  
  if ($result) $show = $hcms_lang['the-data-was-saved-successfully'][$lang];
  else $show = $hcms_lang['the-data-could-not-be-saved'][$lang];
}
?>
<p class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['meta-data-mapping'][$lang]); ?></p>

<?php
echo showmessage ($show, 600, 70, $lang, "position:fixed; left:10px; top:50px;");
?>

<form id="editor" name="editor" action="" method="post" style="margin-top:10px; padding:2px;">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="save" value="yes" />
  <input type="hidden" name="token" value="<?php echo createtoken ($user); ?>" />
  
  <?php echo $mapping_data; ?>
  <br />
  
  <table class="hcmsTableStandard" style="margin-top:10px;">
    <tr>
      <td style="width:240px;"><?php echo getescapedtext ($hcms_lang['save-setting'][$lang]); ?> </td>
      <td><img name="Button1" onClick="document.forms['editor'].submit();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_ok.png" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" /></td> 
    </tr>
  </table>
</form>

<form id="reindex" name="reindex" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="save" value="reindex" />
  <input type="hidden" name="token" value="<?php echo createtoken ($user); ?>" />
  
  <table class="hcmsTableStandard" style="margin-top:10px;">
    <tr>
      <td style="width:240px;"><?php echo getescapedtext ($hcms_lang['reindex-content-of-all-media-files'][$lang]); ?> </td>
      <td><img name="Button2" onClick="checkReindex()" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_ok.png" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button2','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" /></td>
    </tr>
  </table>
</form>
  
</div>

<?php includefooter(); ?>
</body>
</html>
