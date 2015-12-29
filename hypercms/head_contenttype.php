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

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric" leftmargin="3" topmargin="3" marginwidth="0" marginheight="0">

<span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['content-type'][$lang]); ?></span><br /><br />
<table width="100%" border="0" cellspacing="2" cellpadding="3">
  <tr>
    <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['character-set'][$lang]); ?></td>
    <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['description'][$lang]); ?></td>
    <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['language'][$lang]); ?></td>
  </tr>
  <?php
  //load code page index file
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
  
      echo "<tr class=\"".$rowcolor."\">
        <td nowrap=\"nowrap\">".$code."</td>
        <td>".$description."</td>
        <td>".$language."</td>
      </tr>\n";
    }
  }
  else echo "<p class=hcmsHeadline>".getescapedtext ($hcms_lang['could-not-find-code-page-index'][$lang])."</p>";
  ?>
</table>

</body>
</html>
