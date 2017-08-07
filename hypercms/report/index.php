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
require ($mgmt_config['abs_path_cms']."function/hypercms_api.inc.php");

// input parameters
$reportname = getrequest ("reportname", "objectname");
$export = getrequest ("export");
$search_textnode = getrequest ("search_textnode");


// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// define config file name and extract report name
if (strpos ($reportname, ".report.dat") > 0)
{
  $reportfile = $report_name;
  $reportname = substr ($reportfile, 0, strpos ($reportfile, ".report.dat"));
}
else
{
  $reportfile = $reportname.".report.dat";
}

// load report config file
if ($reportfile != "")
{
  $report_config = loadreport ($reportfile);
  
  // prepare variables 
  if (is_array ($report_config))
  {
    foreach ($report_config as $name=>$value)
    {
      // assign to variable
      if ($name != "") ${$name} = $value;
    }
  }
}

// process SQL statement
if (!empty ($sql))
{
  // build search form if requested by the report
  if (strpos (strtolower ($sql), "[hypercms:") > 0)
  {
    // replace tags with values
    if (!empty ($search_textnode) && is_array ($search_textnode))
    {
      // get all hyperCMS text tags
      $hypertag_array = gethypertag ($sql, "text", 0);    
    
      if ($hypertag_array != false) 
      {
        reset ($hypertag_array);
        
        // loop for each hyperCMS tag found in template
        foreach ($hypertag_array as $key => $hypertag)
        {
          // get tag id
          $id = getattribute ($hypertag, "id");
          
          // replace tag by search expression
          $sql = str_replace ($hypertag, $search_textnode[$id], $sql);
        }
      }
    }
    // show search form
    else
    {
      // template engine
      require ("../function/hypercms_tplengine.inc.php");
      
      echo buildsearchform ("", "", $reportname, "");
      exit;
    }
  }
      
  // execute SQL statement
  $queryresult = rdbms_externalquery ($sql, @$concat_by);

  if (is_array ($queryresult))
  {
    // create HTML output (table and chart)
    if (empty ($export) || $export != "true")
    {
      // title
      if (!empty ($title)) $table_title = "<div class=\"hcmsHeadline\" style=\"display:block; padding-bottom:3px;\">".$title."</div>\n";
      else $table_title = "";
    
      // create table
      $table = $table_title."<table id=\"table-data\">\n";
      
      foreach ($queryresult as $i=>$row)
      {
        if (is_array ($row))
        {
          // table header
          if (empty ($thead))
          {
            $thead = "  <thead>\n    <tr>\n";          
            foreach ($row as $key=>$value) $thead .= "      <th>".$key."</th>\n";
            $thead .= "    </tr>\n  </thead>\n";

            $table .= $thead;
          }
          
          // table data
          $table .= "  <tbody>\n    <tr>\n";        
          foreach ($row as $key=>$value) $table .= "      <td>".$value."</td>\n";
          $table .= "    </tr>\n  </tbody>\n";
        }
        else echo $row;
      }
      
      $table .= "</table>\n";
      
      // create chart
      if ($chart_type == "pie")
      {
        $chart = showpiechart ($title, $chart_pie_x_title, $chart_pie_x_value, $chart_pie_y_title, $chart_pie_y_value, $queryresult, "100%", "99%", "hcmsPieChart");
      }
      elseif ($chart_type == "column")
      {
        $chart = showcolumnchart ($title, $chart_col_x_title, $chart_col_x_value, $chart_col_y1_title, $chart_col_y1_value, $chart_col_y2_title, $chart_col_y2_value, $chart_col_y3_title, $chart_col_y3_value, $queryresult, "100%", "99%", "hcmsColumnChart");
      }
      elseif ($chart_type == "timeline")
      {
        $chart = showtimelinechart ($title, $chart_tl_y_title, $chart_tl_y_value, $chart_tl_x1_title, $chart_tl_x1_value, $chart_tl_x2_title, $chart_tl_x2_value, $queryresult, "100%", "99%", "hcmsTimelineChart");
      }
      elseif ($chart_type == "geolocation")
      {
        $chart = showgeolocationchart ($title, $chart_geo_name_value, $chart_geo_lat_value, $chart_geo_lng_value, $chart_geo_link_value, $queryresult, "100%", "99%", "hcmsGeolocationChart");
      }
    }
    // create CSV ouptut
    else
    {
      create_csv ($queryresult, "export.csv");
    }
  }
  else
  {
    $show = getescapedtext ($hcms_lang['no-results-available'][$lang]);
  }
}
else
{
  $show = getescapedtext ($hcms_lang['required-input-is-missing'][$lang]);
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<style>
table
{
  border-collapse: collapse;
  border:1px solid #000000;
}

th
{
  padding: 2px;
  border:1px solid #000000;
}

td
{
  padding: 2px;
  border:1px solid #000000;
  background-color: #FFFFFF;
}
</style>
<script src="../javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
function resizeChart ()
{
  hcms_drawChart();
}

function exportCSV ()
{
  document.location = "?export=true&reportname=<?php echo html_encode ($reportname); ?>";
}
</script>
</head>

<body style="width:100%; height:100%; margin:0; padding:0;" onresize="resizeChart()">

<?php if (empty ($show)) { ?>
  <div style="position:absolute; top:3px; left:3px; right:3px;">
    <?php if (!empty ($chart)) { ?>
    <button class="hcmsButtonBlue" onclick="hcms_hideInfo('table'); hcms_showInfo('chart', 0);"><?php echo getescapedtext ($hcms_lang['chart'][$lang]); ?></button> 
    <button class="hcmsButtonBlue" onclick="hcms_hideInfo('chart'); hcms_showInfo('table', 0);"><?php echo getescapedtext ($hcms_lang['table'][$lang]); ?></button> 
    <?php } ?>
    <button class="hcmsButtonGreen" onclick="exportCSV();"><?php echo getescapedtext ($hcms_lang['export'][$lang])." CSV"; ?></button>
  </div>
  
  <?php if (!empty ($chart)) { ?>
  <div id="chart" style="position:absolute; top:36px; bottom:3px; left:3px; right:3px; overflow:hidden;">
    <?php echo $chart; ?>
  </div>
  <?php } ?>
  
  <div id="table" style="position:absolute; top:36px; bottom:3px; left:3px; right:3px; overflow:auto; <?php if (!empty ($chart)) echo "display:none;"; ?>">
    <?php echo $table; ?>
  </div>
<?php } else echo $show; ?>

</body>
</html>