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
require ("../../../config.inc.php");
// hyperCMS API
require ("../../../function/hypercms_api.inc.php");
// language file of plugin
require_once ("../lang/page.inc.php");


// input parameters
$site = getrequest_esc ("site", "publicationname");
$action = getrequest_esc ("action");
$token = getrequest_esc ("token");

// only german and english language is supported by plugin
if ($lang != "en" && $lang != "de") $lang = "en";

// define text-ID to looks for keywords
$text_id = "Keywords";

// regernate keyword list after X days
$days = 5;

// keyword file
$keywordfile = $mgmt_config['abs_path_data']."config/".$site.".keyword.php";

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// function to generate sorted keyword list
function showkeywordlist ($keywords, $cat="", $sort_type="value", $css="hcmsButtonOrange")
{
  global $site, $text_id;
  
  // sort array and define output
  if (is_array ($keywords) && sizeof ($keywords) > 0 && ($cat == "page" || $cat == "comp" || $cat == ""))
  {
    // list of mostly used keywords
    if ($sort_type == "key") krsort ($keywords);
    // list of all keywords sorted alphabetically
    else natcasesort ($keywords);
    
    reset ($keywords);
    $show = "";

    foreach ($keywords as $key=>$value)
    {
      list ($count, $i) = explode ("-", $key);
      
      if ($count > 0) $count = "(".number_format ($count, 0, ",", ".").")";
      else $count = "";
      
      if ($cat != "") $search_dir = "&search_dir=".urlencode("%".$cat."%/".$site."/");
      else $search_dir = "";
      
      $show .= "<tr><td><button class=\"".$css."\" style=\"max-width:240px;\" onclick=\"location='frameset_objectlist.php?site=".urlencode($site)."&action=keyword_search".$search_dir."&search_textnode[".$text_id."]=".urlencode($value)."&maxhits=1000';\">".$value." ".$count."</button></td></tr>\n";
    }
      
    return $show;
  }
  else return false;
}

// set default values
$show = "";
$show_comp_rank = "";
$show_comp_sort = "";
$show_page_rank = "";
$show_page_sort = "";

// get keywords from database
if ($action == "regenerate" && checktoken ($token, $user) && valid_objectname ($text_id) && valid_publicationname ($site))
{
  $store = "";
  
  // component keywords
  $keywords = rdbms_getkeywords ("%comp%/".$site."/", $text_id);
  
  if (is_array ($keywords) && sizeof ($keywords) > 0)
  {
    $i = 0;
    
    foreach ($keywords as $keyword=>$count)
    {
      $store .= "\$keywords_comp['".$count."-".$i."'] = \"".$keyword."\";\n";
      $i++;
    }
  }

  // component keywords
  $keywords = rdbms_getkeywords ("%page%/".$site."/", $text_id);

  if (is_array ($keywords) && sizeof ($keywords) > 0)
  {
    $i = 0;
    
    foreach ($keywords as $keyword=>$count)
    {
      $store .= "\$keywords_page['".$count."-".$i."'] = \"".$keyword."\";\n";
      $i++;
    }
  }
    
  // save keywords
  if ($store != "") savefile (getlocation ($keywordfile), getobject ($keywordfile), "<?php\n".$store."?>\n");
}

// days to seconds
$frequenzy = 60 * 60 * 24 * intval ($days);
$limit = time() - $frequenzy;

// check keywords file
if (is_file ($keywordfile) && filemtime ($keywordfile) > $limit) $regenerate = false;
else $regenerate = true;

// load keywords
if (is_file ($keywordfile))
{
  $keywords_comp = array();
  $keywords_page = array();
  
  //load keywords file
  include ($keywordfile);

  // generate key word lists
  $show_comp_rank = showkeywordlist ($keywords_comp, "comp", "key", "hcmsButtonOrange");
  $show_page_rank = showkeywordlist ($keywords_page, "page", "key", "hcmsButtonBlue");
  $show_comp_sort = showkeywordlist ($keywords_comp, "comp", "value", "hcmsButtonOrange");
  $show_page_sort = showkeywordlist ($keywords_page, "page", "value", "hcmsButtonBlue");
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="../../../javascript/main.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric" background="<?php echo getthemelocation(); ?>img/backgrd_empty.png">

<div id="hcmsLoadScreen" class="hcmsLoadScreen"></div>

<!-- top bar -->
<?php echo showtopbar ($hcms_lang['keyword-analysis'][$lang], $lang); ?>


<div id="scrollFrame" style="width:98%; height:95%; overflow:auto;">
  <div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
  <?php echo showmessage ($show, 560, 120, $lang, "position:absolute; left:15px; top:15px;"); ?>
  
  <?php if ($regenerate) { ?>
  <button class="hcmsButtonGreen" onclick="document.getElementById('hcmsLoadScreen').style.display='block'; location='?action=regenerate&site=<?php echo html_encode ($site); ?>&token=<?php echo createtoken ($user); ?>';"><?php echo getescapedtext ($hcms_lang['refresh'][$lang]); ?></button>
  <?php } ?>

  <?php if (!empty ($show_comp_rank)) { ?>
  <div style="float:left; margin-right:20px;">
  <p class=hcmsHeadline><?php echo getescapedtext ($hcms_lang['keyword-frequency'][$lang]); ?></p>
  <span style="padding-left:4px;"><?php echo getescapedtext ($hcms_lang['please-click-the-links-below-to-access-the-files'][$lang]); ?></span>
  <table border="0" cellspacing="2" cellpadding="2">
 	  <tr align="left" valign="top">
      <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['assets'][$lang]); ?></td>
    </tr>
    <?php
    echo $show_comp_rank;
    ?>
  </table>
  </div>
  <?php } ?>
  
  <?php if (!empty ($show_page_rank)) { ?>
  <div style="float:left; margin-right:20px;">
  <p class=hcmsHeadline><?php echo getescapedtext ($hcms_lang['keyword-frequency'][$lang]); ?></p>
  <span style="padding-left:4px;"><?php echo getescapedtext ($hcms_lang['please-click-the-links-below-to-access-the-files'][$lang]); ?></span>
  <table border="0" cellspacing="2" cellpadding="2">
 	  <tr align="left" valign="top">
      <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['pages'][$lang]); ?></td>
    </tr>
    <?php
    echo $show_page_rank;
    ?>
  </table>
  </div>
  <?php } ?>
  
  <?php if (!empty ($show_comp_sort)) { ?>
  <div style="float:left; margin-right:20px;">
  <p class=hcmsHeadline><?php echo getescapedtext ($hcms_lang['keywords-sorted-alphabetically'][$lang]); ?></p>
  <span style="padding-left:4px;"><?php echo getescapedtext ($hcms_lang['please-click-the-links-below-to-access-the-files'][$lang]); ?></span>
  <table border="0" cellspacing="2" cellpadding="2">
 	  <tr align="left" valign="top">
      <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['assets'][$lang]); ?></td>
    </tr>
    <?php
    echo $show_comp_sort;
    ?>
  </table>
  </div>
  <?php } ?>
  
  <?php if (!empty ($show_page_sort)) { ?>
  <div style="float:left; margin-right:20px;">
  <p class=hcmsHeadline><?php echo getescapedtext ($hcms_lang['keywords-sorted-alphabetically'][$lang]); ?></p>
  <span style="padding-left:4px;"><?php echo getescapedtext ($hcms_lang['please-click-the-links-below-to-access-the-files'][$lang]); ?></span>
  <table border="0" cellspacing="2" cellpadding="2">
 	  <tr align="left" valign="top">
      <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['pages'][$lang]); ?></td>
    </tr>
    <?php
    echo $show_page_sort;
    ?>
  </table>
  </div>
  <?php } ?>
  
  </div>
</div>

</body>
</html>