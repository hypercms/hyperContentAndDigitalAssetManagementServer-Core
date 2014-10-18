<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("../../../include/session.inc.php");
// management configuration
require ("../../../config.inc.php");
// hyperCMS API
require ("../../../function/hypercms_api.inc.php");
// hyperCMS UI
require ("../../../function/hypercms_ui.inc.php");
// language file
require_once ("../lang/page.inc.php");

// input parameters
$site = getrequest_esc ("site", "publicationname");
$action = getrequest_esc ("action");
$token = getrequest_esc ("token");

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
      
      $show .= "<tr><td><button class=\"".$css."\" style=\"max-width:240px;\" onclick=\"location.href='frameset_objectlist.php?site=".urlencode($site)."&action=keyword_search".$search_dir."&search_textnode[".$text_id."]=".urlencode($value)."&maxhits=1000';\">".$value." ".$count."</button></td></tr>\n";
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

// split keyword string into array
function splitkeywords ($string)
{
  if ($string != "")
  {
    $string = str_replace ("\n", "", $string);
    $result_array = array();
  	$array1 = explode (",", $string);
        
  	foreach ($array1 as $entry1)
    {
      $result_array[] = trim ($entry1);
  	}
    
    if (is_array ($result_array)) return $result_array;
    else return false;
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
  // connect to MySQL
  $mysqli = new mysqli ($mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname']);      
  if ($mysqli->connect_errno) $show .= "DB error (".$mysqli->connect_errno."): ".$mysqli->connect_error."<br/>\n";
  
  if ($show == "")
  {
    $site = $mysqli->escape_string($site);
    $text_id = $mysqli->escape_string($text_id);
    
    // Select keywords from Assets
    $sql = "SELECT textnodes.textcontent FROM textnodes, object WHERE textnodes.text_id='".$text_id."' AND textnodes.textcontent!='' AND textnodes.id=object.id AND object.objectpath LIKE '*comp*/".$site."/%'";
  
    if ($result = $mysqli->query ($sql))
    {
      $keywords = array();
  
      while ($row = $result->fetch_assoc())
      {
        $keywords_add = splitkeywords ($row['textcontent']);

        $keywords = array_merge ($keywords, $keywords_add);
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
            $store .= "\$keywords_comp['".$count."-".$i."'] = \"".transformkeyword ($keyword)."\";\n";
            $i++;
          }
        }
        
        // save keywords
        if ($store != "") savefile ($mgmt_config['abs_path_data']."config/", $site.".keyword.php", "<?php\n".$store."?>\n");
      }
    } 
    else $show .= "DB error (".$mysqli->errno."): ".$mysqli->error."<br/>\n";
    
    // Select keywords from Pages
    $sql = "SELECT textnodes.textcontent FROM textnodes, object WHERE textnodes.text_id='".$text_id."' AND textnodes.textcontent!='' AND textnodes.id=object.id AND object.objectpath LIKE '*page*/".$site."/%'";
  
    if ($result = $mysqli->query ($sql))
    {
      $keywords = array();
      
      while ($row = $result->fetch_assoc())
      {
        $keywords_add = splitkeywords ($row['textcontent']);

        $keywords = array_merge ($keywords, $keywords_add);
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
            $store .= "\$keywords_page['".$count."-".$i."'] = \"".transformkeyword ($keyword)."\";\n";
            $i++;
          }
        }
        
        // save keywords
        if ($store != "") savefile ($mgmt_config['abs_path_data']."config/", $site.".keyword.php", "<?php\n".$store."?>\n");
      }
    }
    else $show .= "DB error (".$mysqli->errno."): ".$mysqli->error."<br/>\n";
    
    $result->close();
    $mysqli->close();
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
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="../../../javascript/main.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric" background="<?php echo getthemelocation(); ?>img/backgrd_empty.png">

<div id="hcmsLoadScreen" class="hcmsLoadScreen"></div>

<!-- top bar -->
<?php echo showtopbar ($text0[$lang], $lang); ?>

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
  <div id="scrollFrame" style="width:98%; height:700px; overflow:auto;">
  
  <?php echo showmessage ($show, 560, 120, $lang, "position:absolute; left:15px; top:15px;"); ?>
  
  <?php if ($regenerate) { ?>
  <button class="hcmsButtonGreen" onclick="document.getElementById('hcmsLoadScreen').style.display='block'; location.href='?action=regenerate&site=<?php echo html_encode ($site); ?>&token=<?php echo createtoken ($user); ?>';"><?php echo $text6[$lang]; ?></button>
  <?php } ?>

  <?php if (!empty ($show_comp_rank)) { ?>
  <div style="float:left; margin-right:20px;">
  <p class=hcmsHeadline><?php echo $text1[$lang]; ?></p>
  <p><?php echo $text2[$lang]; ?></p>
  <table border="0" cellspacing="2" cellpadding="2">
 	  <tr align="left" valign="top">
      <td class="hcmsHeadline"><?php echo $text3[$lang]; ?></td>
    </tr>
    <?php
    echo $show_comp_rank;
    ?>
  </table>
  </div>
  <?php } ?>
  
  <?php if (!empty ($show_page_rank)) { ?>
  <div style="float:left; margin-right:20px;">
  <p class=hcmsHeadline><?php echo $text1[$lang]; ?></p>
  <p><?php echo $text2[$lang]; ?></p>
  <table border="0" cellspacing="2" cellpadding="2">
 	  <tr align="left" valign="top">
      <td class="hcmsHeadline"><?php echo $text4[$lang]; ?></td>
    </tr>
    <?php
    echo $show_page_rank;
    ?>
  </table>
  </div>
  <?php } ?>
  
  <?php if (!empty ($show_comp_sort)) { ?>
  <div style="float:left; margin-right:20px;">
  <p class=hcmsHeadline><?php echo $text5[$lang]; ?></p>
  <p><?php echo $text2[$lang]; ?></p>
  <table border="0" cellspacing="2" cellpadding="2">
 	  <tr align="left" valign="top">
      <td class="hcmsHeadline"><?php echo $text3[$lang]; ?></td>
    </tr>
    <?php
    echo $show_comp_sort;
    ?>
  </table>
  </div>
  <?php } ?>
  
  <?php if (!empty ($show_page_sort)) { ?>
  <div style="float:left; margin-right:20px;">
  <p class=hcmsHeadline><?php echo $text5[$lang]; ?></p>
  <p><?php echo $text2[$lang]; ?></p>
  <table border="0" cellspacing="2" cellpadding="2">
 	  <tr align="left" valign="top">
      <td class="hcmsHeadline"><?php echo $text4[$lang]; ?></td>
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