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

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['content-type'][$lang]); ?></span>
<br /><br />
<table class="hcmsTableStandard" style="width:100%;">
  <tr>
    <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['character-set'][$lang]); ?></td>
    <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['description'][$lang]); ?></td>
    <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['language'][$lang]); ?></td>
  </tr>
  <?php
  // initalize
  $color = true;

  // load code page index file
  $codepage_array = file ($mgmt_config['abs_path_cms']."include/codepage.dat");

  if ($codepage_array != false)
  {
    foreach ($codepage_array as $codepage)
    {
      list ($code, $description, $language) = explode ("|", $codepage);
      
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
  
      echo "
      <tr class=\"".$rowcolor."\">
        <td style=\"white-space:nowrap;\">".$code."</td>
        <td>".$description."</td>
        <td>".$language."</td>
      </tr>";
    }
  }
  else echo "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['could-not-find-code-page-index'][$lang])."</p>";
  ?>
</table>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>
