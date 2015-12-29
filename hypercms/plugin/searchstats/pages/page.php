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
require ("../../../config.inc.php");
// hyperCMS API
require ("../../../function/hypercms_api.inc.php");
// language file of plugin
require_once ("../lang/page.inc.php");


// input parameters
$action = getrequest_esc ("action");
$token = getrequest_esc ("token");

// only german and english language is supported by plugin
if ($lang != "en" && $lang != "de") $lang = "en";

// regernate keyword list after X days
$days = 1;

// keyword file
$keywordfile = $mgmt_config['abs_path_data']."config/searchexpressions.php";

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// function to generate sorted keyword list
function showkeywordlist ($keywords, $sort_type="value", $css="hcmsButtonGreen")
{
  global $site, $text_id;
  
  // sort array and define output
  if (is_array ($keywords) && sizeof ($keywords) > 0)
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
      
      $show .= "<tr><td><button class=\"".$css."\" style=\"max-width:240px;\" onclick=\"location.href='frameset_objectlist.php?action=base_search&search_expression=".urlencode($value)."&maxhits=1000';\">".$value." ".$count."</button></td></tr>\n";
    }
      
    return $show;
  }
  else return false;
}

// function to transform keyword 
function transformkeyword ($keyword)
{
  if (trim ($keyword) != "")
  {
    $search = array ("\"");
    $replace = array ("\\\"");
    
    return $keyword = str_replace ($search, $replace, trim ($keyword));
  }
  else return "";
}

// set default values
$show = "";
$show_rank = "";
$show_sort = "";

// collect search expressions from log
if ($action == "regenerate" && checktoken ($token, $user) && is_file ($mgmt_config['abs_path_data']."log/search.log"))
{
  // load search log
  $data = file ($mgmt_config['abs_path_data']."log/search.log");

  if (is_array ($data))
  {
    $keywords = array();

    foreach ($data as $record)
    {
      list ($date, $user, $keyword_add) = explode ("|", $record);

      $keywords[] = $keyword_add;
    }
    
    if (sizeof ($keywords) > 0)
    {
      // list of mostly used keywords
      $keywords_tmp = array_count_values ($keywords);
      
      $store = "";
      $i = 0;
      
      foreach ($keywords_tmp as $keyword=>$count)
      {
        if (is_string ($keyword) && strlen ($keyword) > 1)
        {
          $store .= "\$expressions['".$count."-".$i."'] = \"".transformkeyword ($keyword)."\";\n";
          $i++;
        }
      }
      
      // save keywords
      if ($store != "") savefile (getlocation ($keywordfile), getobject ($keywordfile), "<?php\n".$store."?>\n");
    }
  }
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
  $expressions = array();
  
  //load keywords file
  include ($keywordfile);

  // generate key word lists
  $show_rank = showkeywordlist ($expressions, "key");
  $show_sort = showkeywordlist ($expressions, "value");
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="../../../javascript/main.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric" background="<?php echo getthemelocation(); ?>img/backgrd_empty.png">

<div id="hcmsLoadScreen" class="hcmsLoadScreen"></div>

<!-- top bar -->
<?php echo showtopbar ($hcms_lang['search-expression-analysis'][$lang], $lang); ?>


<div id="scrollFrame" style="width:98%; height:95%; overflow:auto;">
  <div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
  <?php
  if (!is_file ($mgmt_config['abs_path_data']."log/search.log")) $show = getescapedtext ($hcms_lang['search-expression-log-is-not-available'][$lang]);
  
  echo showmessage ($show, 560, 120, $lang, "position:absolute; left:15px; top:15px;");
  ?>
  
  <?php if ($regenerate && is_file ($mgmt_config['abs_path_data']."log/search.log")) { ?>
  <button class="hcmsButtonGreen" onclick="document.getElementById('hcmsLoadScreen').style.display='block'; location.href='?action=regenerate&token=<?php echo createtoken ($user); ?>';"><?php echo getescapedtext ($hcms_lang['refresh'][$lang]); ?></button>
  <?php } ?>

  <?php if (!empty ($show_rank)) { ?>
  <div style="float:left; margin-right:20px;">
  <p class=hcmsHeadline><?php echo getescapedtext ($hcms_lang['expression-frequency'][$lang]); ?></p>
  <span style="padding-left:4px;"><?php echo getescapedtext ($hcms_lang['please-click-the-links-below-to-access-the-files'][$lang]); ?></span>
  <table border="0" cellspacing="2" cellpadding="2">
    <?php
    echo $show_rank;
    ?>
  </table>
  </div>
  <?php } ?>
  
  <?php if (!empty ($show_sort)) { ?>
  <div style="float:left; margin-right:20px;">
  <p class=hcmsHeadline><?php echo getescapedtext ($hcms_lang['expression-sorted-alphabetically'][$lang]); ?></p>
  <span style="padding-left:4px;"><?php echo getescapedtext ($hcms_lang['please-click-the-links-below-to-access-the-files'][$lang]); ?></span>
  <table border="0" cellspacing="2" cellpadding="2">
    <?php
    echo $show_sort;
    ?>
  </table>
  </div>
  <?php } ?>
  
  </div>
</div>

</body>
</html>