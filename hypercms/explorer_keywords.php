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


// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=<?php echo windowwidth ("object"); ?>, initial-scale=1.0, maximum-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script src="javascript/main.js" type="text/javascript"></script>
</head>

<body id="hcms_htmlbody" onload="parent.hcms_showPage('keywordsFrame', 'keywordsTarget'); parent.document.getElementById('keywordsTarget').style.background='';">
  <!--
  <label for="publication"><?php echo getescapedtext ($hcms_lang['publication'][$lang]); ?></label><br />
  <select id="publication" name="site" style="width:230px;">
    <option value=""><?php echo getescapedtext ($hcms_lang['select-all'][$lang]); ?></option>
  <?php
  $keywords = array();
  
  if (!empty ($siteaccess) && is_array ($siteaccess))
  {
    $template_array = array();
    
    foreach ($siteaccess as $site)
    {
      if (!empty ($site)) echo "
    <option value=\"".$site."\">".$site."</option>";
    }
  }
  ?>
  </select><br /> 
  -->
  
  <table class="hcmsTableNarrow" style="width:100%; margin-top:4px;">
  <?php
  $count = rdbms_getemptykeywords ($siteaccess);
  ?>
    <tr class="hcmsRowData1"><td style="text-align:left;" title="<?php echo getescapedtext ($hcms_lang['none'][$lang]); ?>"><label><input type="checkbox" onclick="startSearch('auto')" name="search_textnode[]" value="%keyword%/" />&nbsp;<?php echo getescapedtext ($hcms_lang['none'][$lang]); ?></label></td><td style="text-align:right;"><?php echo $count; ?>&nbsp;</td></tr>
  <?php
  $keywords = getkeywords ($siteaccess);
  
  if (!empty ($keywords) && is_array ($keywords) && sizeof ($keywords) > 0)
  {
    $color = false;
    
    foreach ($keywords as $keyword_id => $keyword_array)
    {
      foreach ($keyword_array as $count => $keyword)
      {
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
    <tr class=\"".$rowcolor."\"><td style=\"text-align:left;\" title=\"".$keyword."\"><label><input type=\"checkbox\" onclick=\"startSearch('auto')\" name=\"search_textnode[]\" value=\"%keyword%/".$keyword_id."\" />&nbsp;".getescapedtext (showshorttext ($keyword, 32))."</label></td><td style=\"text-align:right;\">".$count."&nbsp;</td></tr>";
      }
    }
  }
  else
  {
    echo "
    <tr><td>".getescapedtext ($hcms_lang['no-items-were-found'][$lang])."</td></tr>";
  }
  ?>
  </table>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>