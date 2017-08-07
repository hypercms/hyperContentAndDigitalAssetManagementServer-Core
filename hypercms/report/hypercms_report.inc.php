<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// ======================================= REPORT OPERATIONS ==========================================

// ----------------------------------------- createreport ---------------------------------------------
// function: createreport()
// input: report name
// output: result array
// requires: config.inc.php to be loaded before

// description:
// This function creates a new report

function createreport ($report_name)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  $result_ok = false;
  
  // set default language
  if ($lang == "") $lang = "en";
  
  if (valid_objectname ($report_name) && strlen ($report_name) <= 100)
  {
    // check if file is customer registration (reg.dat), customer profile (.prof.dat) and define extension
    $ext = ".report.dat";
    
    // create pers file name
    $report_name = trim ($report_name);
    $reportfile = $report_name.$ext;
    
    // upload template file
    if (@is_file ($mgmt_config['abs_path_data']."report/".$reportfile))
    {
      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-object-exists-already'][$lang]."</span>
      ".$hcms_lang['please-try-another-template-name'][$lang]."\n";
    }
    else
    {
      // save template file
      $test = savefile ($mgmt_config['abs_path_data']."report/", $reportfile, "");
    
      if ($test == false)
      {
        $add_onload = "";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-object-could-not-be-created'][$lang]."</span>
        ".$hcms_lang['you-do-not-have-write-permissions'][$lang]."\n";
      }
      else
      {
        $add_onload = "parent.frames['mainFrame'].location='report_form.php?save=no&preview=no&reportfile=".url_encode($reportfile)."'; ";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-object-was-created-successfully'][$lang]."</span>\n";
        
        // success
        $result_ok = true;
      }
    }
  }
  else 
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['a-name-is-required'][$lang]."</span>";
  }    

  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;  
  
  return $result;  
}

// ----------------------------------------- editreport ---------------------------------------------
// function: editreport()
// input: report name, report configuration as array
// output: result array
// requires: config.inc.php to be loaded before

// description:
// This function saves the configuration of a report.

function editreport ($report_name, $config)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  $result_ok = false;
  $add_onload = "";
  
  // set default language
  if ($lang == "") $lang = "en";
  
  if (valid_objectname ($report_name) && strlen ($report_name) <= 100 && is_array ($config))
  {
    // check if file name is an attribute of a sent string
    if (strpos ($report_name, ".php?") > 0)
    {
      // extract file name
      $report_name = getattribute ($report_name, "reportfile");
    }
    
    // define config file name and extract report name
    if (strpos ($report_name, ".report.dat") > 0)
    {
      $reportfile = $report_name;
      $report_name = substr ($reportfile, 0, strpos ($reportfile, ".report.dat"));
    }
    else
    {
      $reportfile = $report_name.".report.dat";
    }

    // clean and check SQL statement
    if (!empty ($config['sql']))
    {
      $config['sql'] = str_replace (array ("&amp;", "&lt;", "&gt;"), array("&", "<", ">"), $config['sql']);
      $config['sql'] = str_replace (array("\r\n", "\n", "\r"), " ", $config['sql']);
      $config['sql'] = str_replace (array("  ", "  ", "  "), " ", $config['sql']);
      
      // check SQL statement
      $sql_check = sql_clean_functions ($config['sql']);
    }
    else $sql_check['result'] = true;

    if ($sql_check['result'] == true)
    {      
      // define report data
      $reportdata = "title=".@$config['title']."\n";
      $reportdata .= "sql=".@$config['sql']."\n";
      $reportdata .= "concat_by=".@$config['concat_by']."\n";
      $reportdata .= "chart_type=".@$config['chart_type']."\n";
      
      $reportdata .= "chart_pie_x_title=".@$config['chart_pie_x_title']."\n";
      $reportdata .= "chart_pie_x_value=".@$config['chart_pie_x_value']."\n";
      $reportdata .= "chart_pie_y_title=".@$config['chart_pie_y_title']."\n";
      $reportdata .= "chart_pie_y_value=".@$config['chart_pie_y_value']."\n";
  
      $reportdata .= "chart_col_x_title=".@$config['chart_col_x_title']."\n";
      $reportdata .= "chart_col_x_value=".@$config['chart_col_x_value']."\n";
      $reportdata .= "chart_col_y1_title=".@$config['chart_col_y1_title']."\n";
      $reportdata .= "chart_col_y1_value=".@$config['chart_col_y1_value']."\n";
      $reportdata .= "chart_col_y2_title=".@$config['chart_col_y2_title']."\n";
      $reportdata .= "chart_col_y2_value=".@$config['chart_col_y2_value']."\n";
      $reportdata .= "chart_col_y3_title=".@$config['chart_col_y3_title']."\n";
      $reportdata .= "chart_col_y3_value=".@$config['chart_col_y3_value']."\n";
  
      $reportdata .= "chart_tl_y_title=".@$config['chart_tl_y_title']."\n";
      $reportdata .= "chart_tl_y_value=".@$config['chart_tl_y_value']."\n";
      $reportdata .= "chart_tl_x1_title=".@$config['chart_tl_x1_title']."\n";
      $reportdata .= "chart_tl_x1_value=".@$config['chart_tl_x1_value']."\n";
      $reportdata .= "chart_tl_x2_title=".@$config['chart_tl_x2_title']."\n";
      $reportdata .= "chart_tl_x2_value=".@$config['chart_tl_x2_value']."\n";
  
      $reportdata .= "chart_geo_name_value=".@$config['chart_geo_name_value']."\n";
      $reportdata .= "chart_geo_lat_value=".@$config['chart_geo_lat_value']."\n";
      $reportdata .= "chart_geo_lng_value=".@$config['chart_geo_lng_value']."\n";
      $reportdata .= "chart_geo_link_value=".@$config['chart_geo_link_value']."\n";
     
      $result_ok = savefile ($mgmt_config['abs_path_data']."report/", $reportfile, $reportdata);
  
      if ($result_ok == false) $show = "<span class=hcmsHeadline>".getescapedtext ($hcms_lang['the-data-could-not-be-saved'][$lang])."</span><br />\n".getescapedtext ($hcms_lang['you-do-not-have-write-permissions'][$lang]);
      else $show = "<span class=hcmsHeadline>".getescapedtext ($hcms_lang['the-data-was-saved-successfully'][$lang])."</span>";
    }
    else $show = "<span class=hcmsHeadline>".getescapedtext ($hcms_lang['the-data-could-not-be-saved'][$lang])."</span><br />\n".getescapedtext ($hcms_lang['there-are-unsecure-functions-in-the-code'][$lang]).": <span style=\"color:red;\">".$sql_check['found']."</span>";#
  }
  else $show = "<span class=hcmsHeadline>".$hcms_lang['a-name-is-required'][$lang]."</span>";
  
  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;
  
  return $result; 
}

// ----------------------------------------- loadreport ---------------------------------------------
// function: loadreport()
// input: report name
// output: result array / false
// requires: config.inc.php to be loaded before

// description:
// This function loads the report configuration file and provides the data as array.

function loadreport ($report_name)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;

  if (valid_objectname ($report_name))
  {
    $result = array();
    
    // check if file name is an attribute of a sent string
    if (strpos ($report_name, ".php?") > 0)
    {
      // extract file name
      $report_name = getattribute ($report_name, "reportfile");
    }
    
    // define config file name and extract report name
    if (strpos ($report_name, ".report.dat") > 0)
    {
      $reportfile = $report_name;
      $report_name = substr ($reportfile, 0, strpos ($reportfile, ".report.dat"));
    }
    else
    {
      $reportfile = $report_name.".report.dat";
    }
    
    // load report config file
    $reportdata = loadfile ($mgmt_config['abs_path_data']."report/", $reportfile);
    
    // trim and split into lines
    $reportdata_array = explode ("\n", trim ($reportdata));
    
    // prepare result array
    if (is_array ($reportdata_array))
    {
      foreach ($reportdata_array as $line)
      {
        if ($line != "")
        {
          // extract name and value
          $name = substr ($line, 0, strpos ($line, "="));
          $value = substr ($line, strpos ($line, "=") + 1);
          
          // escape & < > for SQL statement
          if ($name == "sql") $value = str_replace (array("&", "<", ">"), array("&amp;", "&lt;", "&gt;"), $value);
          
          // assign to variable
          $result[$name] = $value;
        }
      }
      
      if (sizeof ($result) > 0) return $result;
    } 
  }
  else return false;
}

// ----------------------------------------- deletereport ---------------------------------------------
// function: deletereport()
// input: report name
// output: result array
// requires: config.inc.php to be loaded before

// description:
// This function deletes a report

function deletereport ($report_name)
{
  global $user, $eventsystem, $mgmt_config, $hcms_lang, $lang;
 
  $result_ok = false;
  
  // set default language
  if ($lang == "") $lang = "en";
  
  if (valid_objectname ($report_name))
  {
    // check if file name is an attribute of a sent string
    if (strpos ($report_name, ".php?") > 0)
    {
      // extract file name
      $report_name = getattribute ($report_name, "reportfile");
    }
    
    // define config file name and extract report name
    if (strpos ($report_name, ".report.dat") > 0)
    {
      $reportfile = $report_name;
      $report_name = substr ($reportfile, 0, strpos ($reportfile, ".report.dat"));
    }
    else
    {
      $reportfile = $report_name.".report.dat";
    }
    
    if (@is_file ($mgmt_config['abs_path_data']."report/".$reportfile))
    {
      $test = deletefile ($mgmt_config['abs_path_data']."report/", $reportfile, 0);
    
      if ($test == true)
      {
        $add_onload = "parent.frames['mainFrame'].location='".$mgmt_config['url_path_cms']."empty.php'; ";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-object-was-removed'][$lang]."</span>\n";
        
        // success
        $result_ok = true;
      }
      else
      {
        $add_onload = "";
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-object-could-not-be-removed'][$lang]."</span><br />".$hcms_lang['the-object-does-not-exist-or-you-do-not-have-permissions'][$lang]."\n";
      }  
    }
    else
    {
      $add_onload = "";
      $show = "<span class=hcmsHeadline>".$hcms_lang['the-object-could-not-be-removed'][$lang]."</span><br />\n".$hcms_lang['the-object-does-not-exist-or-you-do-not-have-permissions'][$lang]."\n";
    }
  }
  else 
  {
    $add_onload = "";
    $show = "<span class=hcmsHeadline>".$hcms_lang['a-name-is-required'][$lang]."</span>";
  }    
  
  $result = array();
  $result['result'] = $result_ok;
  $result['add_onload'] = $add_onload;
  $result['message'] = $show;  
  
  return $result;  
}

// ----------------------------------------- analyzeSQLselect ---------------------------------------------
// function: analyzeSQLselect()
// input: SQL statement as string
// output: result array

// description:
// This function analyzes an SQL Select statement and return its parts in an array

function analyzeSQLselect ($sql)
{
  if ($sql != "")
  {
    $result = array();
    
    // clean SQL
    $sql = str_replace ("\r\n", " ", $sql);
    $sql = str_replace ("\n", " ", $sql);
    $sql = str_replace ("\r", " ", $sql);
    $sql = str_replace ("  ", " ", $sql);
    $sql = str_replace ("  ", " ", $sql);
    $sql = str_ireplace ("select all ", "SELECT ALL ", $sql);
    $sql = str_ireplace ("select distinctrow ", "SELECT DISTINCTROW ", $sql);
    $sql = str_ireplace ("select distinct ", "SELECT DISTINCT ", $sql);
    $sql = str_ireplace ("select ", "SELECT ", $sql);
    $sql = str_ireplace (" as ", " AS ", $sql);
    $sql = str_ireplace (" from ", " FROM ", $sql);
    $sql = str_ireplace (" where ", " WHERE ", $sql);
    $sql = str_ireplace (" order by ", " ORDER BY ", $sql);
    $sql = str_ireplace (" group by ", " GROUP BY ", $sql);
    $sql = str_ireplace (" limit ", " LIMIT ", $sql);
    
    $result['sql'] = $sql;
    
    // get select attributes
    if (stripos ("_".$sql, "SELECT ALL ") > 0) list ($select, $rest) = explode (" FROM ", str_ireplace ("SELECT ALL ", "", $sql));
    if (stripos ("_".$sql, "SELECT DISTINCTROW ") > 0) list ($select, $rest) = explode (" FROM ", str_ireplace ("SELECT DISTINCTROW ", "", $sql));
    if (stripos ("_".$sql, "SELECT DISTINCT ") > 0) list ($select, $rest) = explode (" FROM ", str_ireplace ("SELECT DISTINCT ", "", $sql));
    elseif (stripos ("_".$sql, "SELECT ") > 0) list ($select, $rest) = explode (" FROM ", str_ireplace ("SELECT ", "", $sql));
    
    if (!empty ($select))
    {
      $result['select'] = $select;
      // split into array
      $attributes = explode (",", $select);
      $result['select_array'] = array_map ('trim', $attributes);
      
      $result['select_field'] = array();
      $result['select_name'] = array();
      
      foreach ($attributes as $value)
      {
        if (trim ($value) != "")
        {
          if (strpos ($value, " AS ") > 0)
          {
            list ($attribute_field, $attribute_name) = explode (" AS ", $value);
            $attribute_alias = $attribute_name;
          }
          else
          {
            $attribute_field = $value;
            $attribute_name = $value;
            $attribute_alias = "";
          }
          
          // only field names
          $result['select_field'][] = trim ($attribute_field);
          // name can be field name or alias if it exists
          $result['select_name'][] = trim ($attribute_name);
          // only alias
          $result['select_alias'][] = trim ($attribute_alias);
        }
      }
    }
      
    // get where conditions
    if (stripos ($sql, " WHERE ") > 0)
    {
      list ($rest, $where) = explode (" WHERE ", $sql);
     
      if (!empty ($where))
      {
        if (stripos ($where, " GROUP BY ") > 0) list ($where, $group_by) = explode (" GROUP BY ", $where);
        elseif (stripos ($where, " ORDER BY ") > 0) list ($where, $order_by) = explode (" ORDER BY ", $where);
        elseif (stripos ($where, " LIMIT ") > 0) list ($where, $limit) = explode (" LIMIT ", $where);
        
        $result['where'] = $where;
      }
    }
    
    // get group-by statement
    if (stripos ($sql, " GROUP BY ") > 0)
    {
      list ($rest, $group_by) = explode (" GROUP BY ", $sql);
      
      if (!empty ($group_by))
      {
        if (stripos ($group_by, " ORDER BY ") > 0) list ($group_by, $order_by) = explode (" ORDER BY ", $group_by);
        elseif (stripos ($group_by, " LIMIT ") > 0) list ($group_by, $limit) = explode (" LIMIT ", $group_by);
        
        $result['group_by'] = $group_by;
        // split into array
        $attributes = explode (",", $group_by);
        $result['group_by_array'] = array_map ('trim', $attributes);
      }
    }
    
    // get order-by statement
    if (stripos ($sql, " ORDER BY ") > 0)
    {
      list ($rest, $order_by) = explode (" ORDER BY ", $sql);
      
      if (!empty ($order_by))
      {
        if (stripos ($order_by, " LIMIT ") > 0) list ($order_by, $limit) = explode (" LIMIT ", $order_by);
        
        $result['order_by'] = $order_by;
        // split into array
        $attributes = explode (",", $order_by);
        $result['order_by_array'] = array_map ('trim', $attributes);
      }
    }
    
    // get limit statement
    if (stripos ($sql, " LIMIT ") > 0)
    {
      list ($rest, $limit) = explode (" LIMIT ", $sql);
      
      if (!empty ($limit)) $result['limit'] = $limit;
    }
    
    // return result
    return $result;
  }
  else return false;
}

// ----------------------------------------- showpiechart ---------------------------------------------
// function: showpiechart()
// input: chart title, chart x-axis title, chart x-axis array key name holding the values, chart y-axis title, chart y-axis array key name holding the values, 
//        assoz. data array, chart width in pixels (optional), chart height in pixels (optional), ID of chart (optional), function name suffix (optional), 
//        load Google Chart API [true,false] (optional)
// output: Google Chart code / false

// description:
// This function creates the Google Chart Code for a pie chart. The Google Chart API need to be loaded first!

function showpiechart ($title="", $x_title, $x_value, $y_title, $y_value, $data_array, $width="100%", $height="100%", $id="hcmsPieChart", $suffix="", $loadAPI=true)
{
  global $mgmt_config, $hcms_lang, $lang;

  if ($x_title != "" && $x_value != "" && $y_title != "" && $y_value != "" && is_array ($data_array) && sizeof ($data_array) > 0 && $width != "" && $height != "")
  {
    if ($loadAPI) $result = "
    <script type=\"text/javascript\" src=\"https://www.google.com/jsapi\"></script>";
    else $result = "";
    
    $result .= "
    <script type=\"text/javascript\">
    google.load('visualization', '1', {packages:['corechart']});
    google.setOnLoadCallback(hcms_drawChart".$suffix.");
    function hcms_drawChart".$suffix." ()
    {
      var data = google.visualization.arrayToDataTable([
        ['".$x_title."', '".$y_title."'], ";
    
    // prepare name of values
    if (strpos ($x_value, ".") > 0 && strpos ($x_value, ")") < 1) list ($table, $x_value) = explode (".", $x_value);
    if (strpos ($y_value, ".") > 0 && strpos ($y_value, ")") < 1) list ($table, $y_value) = explode (".", $y_value);
    
    // loop through rows
    $result_array = array();
      
    foreach ($data_array as $i=>$row)
    {
      if (!empty ($row[$x_value]) && !empty ($row[$y_value]))
      {
        $result_array[] = "
        ['".$row[$x_value]."', ".floatval($row[$y_value])."]";
      }
    }
    
    $result .= implode (",", $result_array);
    
    $result .= "    
      ]);

      var options = {
        title: '".$title."',
        pieHole: 0.4,
      };

      var chart = new google.visualization.PieChart(document.getElementById('".$id."'));
      chart.draw(data, options);
    }
    </script>
    <div id=\"".$id."\" style=\"width:".$width."; height:".$height.";\"></div>
    ";
    
    return $result;
  }
  else return false;
}

// ----------------------------------------- showcolumnchart ---------------------------------------------
// function: showcolumnchart()
// input: chart title, chart x-axis title, chart x-axis array key name holding the values, chart y1-axis title, chart y1-axis array key name holding the values, 
//        chart y2-axis title, chart y2-axis array key name holding the values, chart y3-axis title, chart y3-axis array key name holding the values,
//        assoz. data array, chart width in pixels (optional), chart height in pixels (optional), ID of chart (optional), function name suffix (optional)
// output: Google Chart code / false

// description:
// This function creates the Google Chart Code for a column chart. The Google Chart API need to be loaded first!

function showcolumnchart ($title="", $x_title, $x_value, $y1_title, $y1_value, $y2_title="", $y2_value="", $y3_title="", $y3_value="", $data_array, $width="100%", $height="100%", $id="hcmsColumnChart", $suffix="", $loadAPI=true)
{
  global $mgmt_config, $hcms_lang, $lang;

  if ($x_title != "" && $x_value != "" && $y1_title != "" && $y1_value != "" && is_array ($data_array) && sizeof ($data_array) > 0 && $width != "" && $height != "")
  {
    if ($loadAPI) $result = "
    <script type=\"text/javascript\" src=\"https://www.google.com/jsapi\"></script>";
    else $result = "";
    
    $result .= "
    <script type=\"text/javascript\">
    google.load('visualization', '1', {packages:['corechart']});
    google.setOnLoadCallback(hcms_drawChart".$suffix.");
    
    function hcms_drawChart".$suffix." ()
    {
      var data = google.visualization.arrayToDataTable([
        ['".$x_title."', '".$y1_title."'".(($y2_value != "") ? ", '".$y2_title."'" : "").(($y3_value != "") ? ", '".$y3_title."'" : "")."], ";
    
    // prepare name of values
    if (strpos ($x_value, ".") > 0 && strpos ($x_value, ")") < 1) list ($table, $x_value) = explode (".", $x_value);
    if (strpos ($y1_value, ".") > 0 && strpos ($y1_value, ")") < 1) list ($table, $y1_value) = explode (".", $y1_value);
    if (strpos ($y2_value, ".") > 0 && strpos ($y2_value, ")") < 1) list ($table, $y2_value) = explode (".", $y2_value);
    if (strpos ($y3_value, ".") > 0 && strpos ($y3_value, ")") < 1) list ($table, $y3_value) = explode (".", $y3_value);
    
    // loop through rows
    $result_array = array();

    foreach ($data_array as $i=>$row)
    {
      if (!empty ($row[$x_value]) && !empty ($row[$y1_value]))
      {
        // prepare values
        $row[$y1_value] = floatval ($row[$y1_value]);
        if ($y2_value != "") $row[$y2_value] = floatval ($row[$y2_value]);
        if ($y3_value != "") $row[$y3_value] = floatval ($row[$y3_value]);
        
        // default values (0)
        if (empty ($row[$y1_value])) $row[$y1_value] = 0;
        if ($y2_value != "" && empty ($row[$y2_value])) $row[$y2_value] = 0;
        if ($y3_value != "" && empty ($row[$y3_value])) $row[$y3_value] = 0;
      
        $result_array[] = "
        ['".$row[$x_value]."', ".$row[$y1_value].(($y2_value != "") ? ", ".$row[$y2_value] : "").(($y3_value != "") ? ", ".$row[$y3_value] : "")."]";
      }
    }
    
    $result .= implode (",", $result_array);
    
    $result .= "    
      ]);

      var options = {
        title: '".$title."'
      };

      var chart = new google.visualization.ColumnChart(document.getElementById('".$id."'));
      chart.draw(data, options);
    }
    </script>
    <div id=\"".$id."\" style=\"width:".$width."; height:".$height.";\"></div>
    ";
    
    return $result;
  }
  else return false;
}

// ----------------------------------------- showtimelinechart ---------------------------------------------
// function: showtimelinechart()
// input: chart title, chart y-axis title, chart y-axis array key name holding the values, chart x1-axis title, chart x1-axis array key name holding the values, 
//        chart x2-axis title, chart x2-axis array key name holding the values, assoz. data array, chart width in pixels (optional), 
//        chart height in pixels (optional), ID of chart (optional), function name suffix (optional), load Google Chart API [true,false] (optional)
// output: Google Chart code / false

// description:
// This function creates the Google Chart Code for a timeline chart. The Google Chart API need to be loaded first!

function showtimelinechart ($title="", $y_title, $y_value, $x1_title, $x1_value, $x2_title="", $x2_value="", $data_array, $width="100%", $height="100%", $id="hcmsTimelineChart", $suffix="", $loadAPI=true)
{
  global $mgmt_config, $hcms_lang, $lang;

  if ($y_title != "" && $y_value != "" && $x1_title != "" && $x1_value != "" && $x2_title != "" && $x2_value != "" && is_array ($data_array) && sizeof ($data_array) > 0 && $width != "" && $height != "")
  {
    if ($loadAPI) $result = "
    <script type=\"text/javascript\" src=\"https://www.google.com/jsapi\"></script>";
    else $result = "";
    
    $result .= "
    <script type=\"text/javascript\">
    google.load('visualization', '1', {packages:['timeline']})
    google.setOnLoadCallback(hcms_drawChart".$suffix.");
    
    
    function hcms_drawChart".$suffix." ()
    {
      var container = document.getElementById('".$id."');
      var chart = new google.visualization.Timeline(container);
      var dataTable = new google.visualization.DataTable();

      dataTable.addColumn({ type: 'string', id: '".$y_title."' });
      dataTable.addColumn({ type: 'date', id: '".$x1_title."' });
      dataTable.addColumn({ type: 'date', id: '".$x2_title."' });
      
      dataTable.addRows([
    ";
    
    // prepare name of values
    if (strpos ($y_value, ".") > 0 && strpos ($y_value, ")") < 1) list ($table, $y_value) = explode (".", $y_value);
    if (strpos ($x1_value, ".") > 0 && strpos ($x1_value, ")") < 1) list ($table, $x1_value) = explode (".", $x1_value);
    if (strpos ($x2_value, ".") > 0 && strpos ($x2_value, ")") < 1) list ($table, $x2_value) = explode (".", $x2_value);
    
    // loop through rows
    $result_array = array();
      
    foreach ($data_array as $i=>$row)
    {
      if (!empty ($row[$y_value]) && !empty ($row[$x1_value]) && !empty ($row[$x2_value]))
      {
        // prepare values
        $startdate = strtotime ($row[$x1_value]) * 1000;
        $enddate = strtotime ($row[$x2_value]) * 1000;
        
        // default values (today)
        if ($startdate < 0) $startdate = time() * 1000;
        if ($enddate < 0) $enddate = (time() + 86400) * 1000;
      
        $result_array[] = "
        ['".$row[$y_value]."', new Date(".$startdate."), new Date(".$enddate.")]";
      }
    }
    
    $result .= implode (",", $result_array);
    
    $result .= "    
      ]);

      var options = {
        title: '".$title."'
      };

      chart.draw(dataTable, options);
    }
    </script>
    <div id=\"".$id."\" style=\"width:".$width."; height:".$height.";\"></div>
    ";
    
    return $result;
  }
  else return false;
}

// ----------------------------------------- showgeolocationchart ---------------------------------------------
// function: showgeolocationchart()
// input: chart title, array key name holding the value for the marker title, array key name holding the value for the latitude of the marker, 
//        array key name holding the value for the longitude of the marker, array key name holding the value for the marker link, 
//        assoz. data array, chart width in pixels (optional), chart height in pixels (optional), ID of chart (optional), function name suffix (optional),
//        load Google Maps API [true,false] (optional)
// output: Google Chart code / false

// description:
// This function creates the Google Maps Code for a geolocation chart. The Google Maps API need to be loaded first!

function showgeolocationchart ($title="", $marker_value, $lat_value, $lng_value, $link_value, $data_array, $width="100%", $height="100%", $id="hcmsGeolocationChart", $suffix="", $loadAPI=true)
{
  global $mgmt_config, $hcms_lang, $lang;

  if ($marker_value != "" && $lat_value != "" && $lng_value != "" && is_array ($data_array) && sizeof ($data_array) > 0 && $width != "" && $height != "")
  {
    // prepare name of values
    if (strpos ($marker_value, ".") > 0 && strpos ($marker_value, ")") < 1) list ($table, $marker_value) = explode (".", $marker_value);
    if (strpos ($lat_value, ".") > 0 && strpos ($lat_value, ")") < 1) list ($table, $lat_value) = explode (".", $lat_value);
    if (strpos ($lng_value, ".") > 0 && strpos ($lng_value, ")") < 1) list ($table, $lng_value) = explode (".", $lng_value);
    if (strpos ($link_value, ".") > 0 && strpos ($link_value, ")") < 1) list ($table, $link_value) = explode (".", $link_value);
    
    // loop through rows
    $result_array = array();
      
    foreach ($data_array as $i=>$row)
    {
      if (!empty ($row[$marker_value]) && !empty ($row[$lat_value]) && !empty ($row[$lng_value]))
      {
        $lat = floatval ($row[$lat_value]);
        $lng = floatval ($row[$lng_value]);
      
        if ($lat != false && $lng != false)
        {
          $result_array[] = "
        ['".$row[$marker_value]."', ".$lat.", ".$lng." ".(!empty ($row[$link_value]) ? ", '".$row[$link_value]."'" : "")."]";
        
          // remember first valid marker geo position for init of map
          if (empty ($map_lat) && empty ($map_lng))
          {
            $map_lat = $lat;
            $map_lng = $lng;
          }
        }
      }
    }
  
    $result = "
    <script type=\"text/javascript\">
    function initMap".$suffix."()
    {
      var map = new google.maps.Map(document.getElementById('".$id."'), {
        zoom: 4,
        center: {lat: ".$map_lat.", lng: ".$map_lng."},
        title: '".$title."'
      });
    
      setMarkers".$suffix."(map);
    }
    
    // Data for the markers consisting of a name, a LatLng and a link
    var markers = [
    ";
    
    $result .= implode (",", $result_array);
      
    $result .= " 
    ];
    
    function setMarkers".$suffix."(map)
    {
      // Adds markers to the map    
      for (var i = 0; i < markers.length; i++)
      {
        var data = markers[i];
        var marker = new google.maps.Marker({
          position: {lat: data[1], lng: data[2]},
          map: map,
          title: data[0]
        });
      }
    }
    </script>
    <div id=\"".$id."\" style=\"width:".$width."; height:".$height.";\"></div>
    ";
    
    if ($loadAPI) $result .= "
    <script src=\"https://maps.googleapis.com/maps/api/js?v=3&key=".$mgmt_config['googlemaps_appkey']."&callback=initMap".$suffix."\" type=\"text/javascript\" async defer></script>
    ";
    
    return $result;
  }
  else return false;
}
?>