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
$save = getrequest ("save");
$openpreview = getrequest ("openpreview");
$preview = getrequest ("preview");
$reportfile = getrequest ("reportfile", "objectname");

$report_config = array();
$title = $report_config['title'] = getrequest_esc ("title");
$sql = $report_config['sql'] = getrequest ("sql");
$concat_by = $report_config['concat_by'] = getrequest ("concat_by");
$chart_type = $report_config['chart_type'] = getrequest ("chart_type");

$chart_pie_x_title = $report_config['chart_pie_x_title'] = getrequest_esc ("chart_pie_x_title");
$chart_pie_x_value = $report_config['chart_pie_x_value'] = getrequest ("chart_pie_x_value");
$chart_pie_y_title = $report_config['chart_pie_y_title'] = getrequest ("chart_pie_y_title");
$chart_pie_y_value = $report_config['chart_pie_y_value'] = getrequest ("chart_pie_y_value");

$chart_col_x_title = $report_config['chart_col_x_title'] = getrequest_esc ("chart_col_x_title");
$chart_col_x_value = $report_config['chart_col_x_value'] = getrequest ("chart_col_x_value");
$chart_col_y1_title = $report_config['chart_col_y1_title'] = getrequest_esc ("chart_col_y1_title");
$chart_col_y1_value = $report_config['chart_col_y1_value'] = getrequest ("chart_col_y1_value");
$chart_col_y2_title = $report_config['chart_col_y2_title'] = getrequest_esc ("chart_col_y2_title");
$chart_col_y2_value = $report_config['chart_col_y2_value'] = getrequest ("chart_col_y2_value");
$chart_col_y3_title = $report_config['chart_col_y3_title'] = getrequest_esc ("chart_col_y3_title");
$chart_col_y3_value = $report_config['chart_col_y3_value'] = getrequest ("chart_col_y3_value");

$chart_tl_y_title = $report_config['chart_tl_y_title'] = getrequest_esc ("chart_tl_y_title");
$chart_tl_y_value = $report_config['chart_tl_y_value'] = getrequest ("chart_tl_y_value");
$chart_tl_x1_title = $report_config['chart_tl_x1_title'] = getrequest_esc ("chart_tl_x1_title");
$chart_tl_x1_value = $report_config['chart_tl_x1_value'] = getrequest ("chart_tl_x1_value");
$chart_tl_x2_title = $report_config['chart_tl_x2_title'] = getrequest_esc ("chart_tl_x2_title");
$chart_tl_x2_value = $report_config['chart_tl_x2_value'] = getrequest ("chart_tl_x2_value");

$chart_geo_name_value = $report_config['chart_geo_name_value'] = getrequest ("chart_geo_name_value");
$chart_geo_lat_value = $report_config['chart_geo_lat_value'] = getrequest ("chart_geo_lat_value");
$chart_geo_lng_value = $report_config['chart_geo_lng_value'] = getrequest ("chart_geo_lng_value");
$chart_geo_link_value = $report_config['chart_geo_link_value'] = getrequest ("chart_geo_link_value");

$token = getrequest ("token");

// ------------------------------ permission section --------------------------------

if (!checkrootpermission ('site')) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$attributes = array();
$conditions = "";
$group_by = "";
$order_by = "";
$limit = "";

// check if file name is an attribute of a sent string
if (strpos ($reportfile, ".php") > 0)
{
  // extract file name
  $reportfile = getattribute ($reportfile, "reportfile");
}

// define category name and extract pers name
if ($reportfile != "") $reportname = substr ($reportfile, 0, strpos ($reportfile, ".report.dat"));
else $reportname = "";

// check file
if (valid_objectname ($reportfile))
{
  // check if file exists
  if (!is_file ($mgmt_config['abs_path_data']."report/".$reportfile)) $reportfile = "";
}
else $reportfile = "";

// save report config file
if ($save == "yes" && $reportfile != "" && checktoken ($token, $user))
{
  $editreport = editreport ($reportfile, $report_config);
  $show = $editreport['message'];
}
// load report config file
else
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

// prepare SQL statement
if (!empty ($sql))
{
  // analyze SQL statement
  $result = analyzeSQLselect ($sql);
  
  // add line breaks to SQL statement
  $sql = str_replace (array(" FROM ", " WHERE ", " GROUP BY ", " ORDER BY ", " LIMIT "), array(" \nFROM ", " \nWHERE ", " \nGROUP BY ", " \nORDER BY ", " \nLIMIT "), $result['sql']);

  // select attributes
  $attributes = $result['select_name'];
  
  // where conditions
  if (!empty ($result['where'])) 
  {
    $conditions = $result['where'];
    
    if (strpos ($conditions, ")") > 0) $conditions = trim (substr ($conditions, strpos ($conditions, ")") + 1));
    
    $conditions = str_replace (" AND ", " \nAND ", $conditions);
    $conditions = str_replace (" OR ", " \nOR ", $conditions);
  }
  
  // group-by statement
  if (!empty ($result['group_by'])) $group_by = $result['group_by'];
  
  // order-by statement
  if (!empty ($result['order_by'])) $order_by = $result['order_by'];
  
  // limit statement
  if (!empty ($result['limit'])) $limit = $result['limit'];
}

// define script for form action
if ($preview == "no") $action = "report_form.php";
else $action = "";
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="../javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
<!--
function createSQL()
{
  // define tables with container id as primary or foreign key
  var tables_id = ["object", "container", "textnodes", "dailystat", "media"];
    
  var inputs = document.getElementsByTagName('input');
  var attributes = document.getElementById('attributes').value;
  var checked = [];
  var tables = [];
  var condition = [];

  // checkboxes for fields
  for (var i = 0; i < inputs.length; i++)
  {
    var field = inputs[i].value;
        
    // checked
    if (inputs[i].type == "checkbox")
    {
      if (inputs[i].checked == true)
      {
        // extract table name
        if (field.indexOf("(") < 0) var table = field.substr (0, field.indexOf("."));
        else var table = field.substring (field.lastIndexOf("(")+1, field.indexOf("."));
        
        // add field
        checked.push(field);
        
        // check for existing attribute and add new attribute
        if (attributes != "" && attributes.indexOf(field) < 0) attributes = attributes + ", " + field;
        else if (attributes == "") attributes = field;
        
        // add table name if not in tables array
        if (tables.indexOf(table) < 0) tables.push(table);
      }
      // unchecked
      else if (inputs[i].checked == false)
      {
        // check for existing attribute and remove attribute
        if (attributes != "")
        {
          if (attributes.indexOf(", SUM("+field+")") >= 0) attributes = attributes.replace(", SUM("+field+")", "");
          else if (attributes.indexOf("SUM("+field+"), ") >= 0) attributes = attributes.replace("SUM("+field+"), ", "");
          else if (attributes.indexOf(", "+field) >= 0) attributes = attributes.replace(", "+field, "");
          else if (attributes.indexOf(field+", ") >= 0) attributes = attributes.replace(field+", ", "");
          else if (attributes.indexOf("SUM("+field+")") >= 0) attributes = attributes.replace("SUM("+field+")", "");
          else if (attributes.trim() == field) attributes = attributes.replace(field, "");
        }
      }
    }
  }
  
  // set attributes
  document.getElementById('attributes').value = attributes;
  
  // inner joins
  for (var i = 0; i < tables.length; i++)
  {    
    if (tables_id.indexOf(tables[i]) < 0 && tables.indexOf("object") >= 0 && tables[i] != "object")
    {
      condition.push(tables[i] + ".object_id=object.object_id");
    }
    else if (tables.indexOf("container") >= 0 && tables[i] != "container" && condition.indexOf("container.id="+tables[i]+".id") < 0)
    {
      condition.push(tables[i] + ".id=container.id");
    }
    else if (tables.indexOf("object") >= 0 && tables[i] != "object" && condition.indexOf("object.id="+tables[i]+".id") < 0)
    {
      condition.push(tables[i] + ".id=object.id");
    }
    else if (tables.indexOf("textnodes") >= 0 && tables[i] != "textnodes" && condition.indexOf("textnodes.id="+tables[i]+".id") < 0)
    {
      condition.push(tables[i] + ".id=textnodes.id");
    }
    else if (tables.indexOf("dailystat") >= 0 && tables[i] != "dailystat" && condition.indexOf("dailystat.id="+tables[i]+".id") < 0)
    {
      condition.push(tables[i] + ".id=dailystat.id");
    }
    else if (tables.indexOf("media") >= 0 && tables[i] != "media" && condition.indexOf("media.id="+tables[i]+".id") < 0)
    {
      condition.push(tables[i] + ".id=media.id");
    }
    else if (tables.indexOf("project") >= 0 && tables[i] == "task" && condition.indexOf("project.project_id="+tables[i]+".project_id") < 0)
    {
      condition.push(tables[i] + ".project_id=project.project_id");
    }
  }
  
  // build SQL statement
  if (checked.length > 0)
  {
    var sql = "SELECT " + attributes + " \nFROM " + tables.join(", ");
    
    // inner joins
    if (condition.length > 0) sql = sql + " \nWHERE (" + condition.join(" AND ") + ")";
    
    // conditions
    var condition_extra = document.getElementById('conditions').value.trim();
    var first = condition_extra.substr (0, condition_extra.indexOf(" "));
    
    if (first == "AND" || first == "OR") condition_extra = condition_extra.substr (condition_extra.indexOf(" "));
    if (condition_extra.trim() != "" && condition.length > 0) sql = sql + " \nAND " + condition_extra;
    else if (condition_extra.trim() != "") sql = sql + " \nWHERE " + condition_extra;
    
    // group by
    var group_by_extra = document.getElementById('group_by').value;
    if (group_by_extra.trim() != "") sql = sql + " \nGROUP BY " + group_by_extra;
    
    // order by
    var order_by_extra = document.getElementById('order_by').value;
    if (order_by_extra.trim() != "") sql = sql + " \nORDER BY " + order_by_extra;
    
    // limit
    var limit_extra = document.getElementById('limit').value;
    if (limit_extra.trim() != "") sql = sql + " \nLIMIT " + limit_extra;
  }
  else var sql = "";
  
  document.getElementById('sql').value = sql;
}

function addcondition()
{
  var operator = document.getElementById('condition_operator').value;
  var field = document.getElementById('condition_attribute').value;
  var operand = document.getElementById('condition_operand').value;
  var value = document.getElementById('condition_value').value;

  // get existing conditions
  var conditions = document.getElementById('conditions').value;
  
  // prepare new condition
  var condition_new = operator + " " + field + " " + operand + ' "' + value + '"';
  
  // add newlines
  if (conditions.trim() != "") conditions = conditions + " \n";
  
  // set new condition
  document.getElementById('conditions').value = conditions + condition_new;
  
  // update SQL statement
  createSQL();
}

function addgroupby ()
{
  var group_by_new = document.getElementById('groupby_attribute').value;
  
  // get existing group by statement
  var group_by = document.getElementById('group_by').value;
  
  if (group_by.indexOf(group_by_new) < 0)
  {
    // add newlines
    if (group_by.trim() != "") group_by = group_by + ", ";
  
    // set new group by statement
    document.getElementById('group_by').value = group_by + group_by_new;
    
    // update SQL statement
    createSQL();
  }
}

function addorderby ()
{
  var order_by_new = document.getElementById('orderby_attribute').value;
  var order_by_sort = document.getElementById('orderby_sort').value;
  
  // get existing group by statement
  var order_by = document.getElementById('order_by').value;
 
  if (order_by.indexOf(order_by_new) < 0)
  {
    // add newlines
    if (order_by.trim() != "") order_by = order_by + ", ";
    
    // add sort type
    if (order_by_sort != "") order_by_new = order_by_new + " " + order_by_sort;
  
    // set new group by statement
    document.getElementById('order_by').value = order_by + order_by_new;
    
    // update SQL statement
    createSQL();
  }
}

function format_tag (format)
{
  var tagid = "";
  var value = "";
  var list = "";
  var dateformat = "";

  tagid = prompt(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-fill-in-a-content-identification-name'][$lang]); ?>"), "");
  if (format == "textc") value = " value='" + prompt(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['enter-value-for-checkbox'][$lang]); ?>"), "") + "'";
  if (format == "textl") list = " list='" + prompt(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-enter-the-text-options-eg'][$lang]); ?>"), "") + "'";
  if (format == "textd") dateformat = " format='%Y-%m-%d'";
  
  if (list != "")
  {
    list = list.replace (';', '|');
    list = list.replace ('&', '&amp;');
    list = list.replace ('<', '&lt;');
    list = list.replace ('>', '&gt;');
  }
  
  if (tagid != "" && tagid != null)
  {
    var code = "[hyperCMS:" + format + " id='" + tagid + "' label='" + tagid + "' " + value + list + dateformat + "]";
    document.getElementById('condition_value').value = code;
  }
  else alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-fill-in-a-content-identification-name'][$lang]); ?>"));
}

function savereport (mode)
{
  if (mode == "preview") document.forms['editor'].elements['openpreview'].value = "yes";
  else document.forms['editor'].elements['openpreview'].value = "no";
    
  document.forms['editor'].submit();
}
//-->
</script>

</head>

<body class="hcmsWorkplaceGeneric" onload="<?php if ($openpreview == "yes") echo "hcms_showHideLayers('previewLayer','','show');"; ?>">
<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<?php
if ($openpreview == "yes")
{
  $show = "<iframe src=\"index.php?reportname=".html_encode($reportname)."\" frameBorder=\"0\" style=\"width:100%; height:600px; border:0; margin:0; padding:0;\"></iframe>";
  echo showmessage ($show, "95%", "95%", $lang, "position:fixed; left:15px; top:15px;");
}
else echo showmessage ($show, 600, 70, $lang, "position:fixed; left:15px; top:100px;");
?>

<p class=hcmsHeadline><?php echo getescapedtext ($hcms_lang['report'][$lang]); ?>: <?php echo $reportname; ?></p>

<form id="editor" name="editor" method="post" action="<?php echo $action; ?>">
  <input type="hidden" name="reportfile" value="<?php echo html_encode ($reportfile); ?>" />
  <input type="hidden" name="save" value="yes" />
  <input type="hidden" name="openpreview" value="no" />
  <input type="hidden" name="preview" value="no" />
  <input type="hidden" name="token" value="<?php echo createtoken ($user); ?>" />
  
  <table cellspacing="0" cellpadding="3" style="border:1px solid #000000; margin:2px;">
    <?php if ($preview == "no") { ?>
    <tr>
      <td align="left">
        <img onclick="savereport('');" name="save" src="<?php echo getthemelocation(); ?>img/button_save.png" class="hcmsButton hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['save'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['save'][$lang]); ?>" />
        <img onClick="savereport('preview');" name="savepreview" src="<?php echo getthemelocation(); ?>img/button_file_preview.png" class="hcmsButton hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['save-and-preview'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['save-and-preview'][$lang]); ?>" />
      </td>
    </tr>
    <?php } ?>
    <tr>
      <td valign="top">
        <hr />
        <b><?php echo getescapedtext ($hcms_lang['title'][$lang]); ?></b><br />
        <input type="text" name="title" style="width:99%;" value="<?php echo $title; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
      </td>
    </tr>
    <tr>
      <td valign="top">
        <hr />
        <?php
        echo "<b>".getescapedtext ($hcms_lang['select-fields-of-entities'][$lang])."</b><br />\n";
        
        if (!empty ($mgmt_config['report_tables'])) $tables = splitstring ($mgmt_config['report_tables']);
        else $tables = array ("object", "container", "textnodes", "dailystat", "media", "recipient", "accesslink");

        $attributes_all = array();
        
        if (!empty ($result['select_alias']) && is_array ($result['select_alias']))
        {
          foreach ($result['select_alias'] as $value)
          {
            if (trim ($value) != "") $attributes_all[] = $value;
          }
        }
        
        foreach ($tables as $table)
        {
          echo "<div class=\"hcmsInfoBox\" style=\"float:left; margin:4px; height:280px;\">\n";
          echo "  <span class=\"hcmsHeadlineTiny\">".$table."</span><br/>\n";
          
          $info = rdbms_gettableinfo ($table);
          
          // special cases for attribute names in order tom sumarize values
          if ($table == "dailystat")
          {
            $info[100]['special'] = "SUM(".$table.".count)";
            $info[100]['name'] = "SUM(count)";
          }
          elseif ($table == "media")
          {
            $info[100]['special'] = "SUM(".$table.".filesize)";
            $info[100]['name'] = "SUM(filesize)";
          }
          elseif ($table == "task")
          {
            $info[100]['special'] = "SEC_TO_TIME(SUM(TIME_TO_SEC(".$table.".actual)))";
            $info[100]['name'] = "SUM(actual)";
          }
          
          if (is_array ($info))
          {
            foreach ($info as $attr)
            {
              // regular fields
              if (empty ($attr['special']) && !empty ($attr['name']))
              {
                // collect attributes
                $attributes_all[] = $table.".".$attr['name'];

                // if checked
                if (!empty ($result['select_field']) && in_array ($table.".".$attr['name'], $result['select_field'])) $checked = "checked";
                else $checked = "";

                // foreign keys
                if (($attr['name'] == "object_id" && $table != "object" && $table != "textnodes") || ($attr['name'] == "id" && $table != "container") || ($attr['name'] == "project_id" && $table != "project"))
                {
                  $name = "<b style=\"color:red;\">".$attr['name']."</b> [Foreign key]";
                }
                else $name = "<b>".$attr['name']."</b> [".$attr['type']."]";
                
                echo "  <input type=\"checkbox\" onclick=\"createSQL()\" id=\"".$table."_".$attr['name']."\" value=\"".$table.".".$attr['name']."\" ".$checked." ".(($preview == "yes") ? "disabled=\"disabled\"" : "")." /> <label for=\"".$table."_".$attr['name']."\">".$name."</label><br/>\n";
              }
              // function fields
              elseif (!empty ($attr['special']))
              {
                // if checked
                if (!empty ($result['select_field']) && in_array ($attr['special'], $result['select_field'])) $checked = "checked";
                else $checked = "";
               
                echo "  <input type=\"checkbox\" onclick=\"createSQL()\" id=\"".$table."_special\" value=\"".$attr['special']."\" ".$checked." ".(($preview == "yes") ? "disabled=\"disabled\"" : "")." /> <label for=\"".$table."_special\"><b>".$attr['name']."</b></label><br/>\n";
              }
            }
          }
          
          echo "</div>\n";
        }
        ?>
        <textarea id="attributes" onblur="createSQL()" wrap="VIRTUAL" style="width:99%;" rows=4 <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?>><?php if (!empty ($result['select'])) echo $result['select']; ?></textarea>
      </td>
    </tr>
    <tr>
      <td valign="top">
        <hr />
        <?php
        echo "<b>".getescapedtext ($hcms_lang['add-new-condition'][$lang])."</b><br />\n";
        
        // add new condition
        if (!empty ($attributes_all) && is_array ($attributes_all))
        {
          $condition_box = "<div class=\"hcmsInfoBox\" style=\"display:block; margin:4px; width:98%;\">\n";
          $condition_box .= "  <div class=\"hcmsToolbarBlock\">\n";
          $condition_box .= "  <select id=\"condition_operator\" ".(($preview == "yes") ? "disabled=\"disabled\"" : "").">\n    <option>AND</option>\n    <option>OR</option>\n  </select>\n";
          $condition_box .= "  <select id=\"condition_attribute\" ".(($preview == "yes") ? "disabled=\"disabled\"" : "").">\n";
          
          foreach ($attributes_all as $value)
          {
            $condition_box .= "    <option>".$value."</option>\n";
          }
          
          $condition_box .= "  </select>\n";          
          $condition_box .= "  <select id=\"condition_operand\" ".(($preview == "yes") ? "disabled=\"disabled\"" : "").">\n    <option>=</option>\n    <option>!=</option>\n    <option>&gt;</option>\n    <option>&gt;=</option>\n    <option>&lt;</option>\n    <option>=&lt;</option>\n    <option>LIKE</option>\n  </select>\n";
          $condition_box .= "  <input type=\"text\" id=\"condition_value\" value=\"\" style=\"width:400px;\" />\n";
          $condition_box .= "  </div>\n";
          $condition_box .= "  <div class=\"hcmsToolbarBlock\">\n";
          $condition_box .= "  <img onClick=\"format_tag('textu')\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_textu.png\" alt=\"".getescapedtext ($hcms_lang['text'][$lang])."\" title=\"".getescapedtext ($hcms_lang['text'][$lang])."\" />\n";
          $condition_box .= "  <img onClick=\"format_tag('textl')\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_textl.png\" alt=\"".getescapedtext ($hcms_lang['text-options'][$lang])."\" title=\"".getescapedtext ($hcms_lang['text-options'][$lang])."\" />\n";
          $condition_box .= "  <img onClick=\"format_tag('textc')\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_textc.png\" alt=\"".getescapedtext ($hcms_lang['checkbox'][$lang])."\" title=\"".getescapedtext ($hcms_lang['checkbox'][$lang])."\" />\n";
          $condition_box .= "  <img onClick=\"format_tag('textd')\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_datepicker.png\" alt=\"".getescapedtext ($hcms_lang['date'][$lang])."\" title=\"".getescapedtext ($hcms_lang['date'][$lang])."\" />\n";
          $condition_box .= "  </div>\n";
          $condition_box .= "  <div class=\"hcmsToolbarBlock\">\n";
          $condition_box .= "  <img name=\"Button\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_ok.png\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button','','".getthemelocation()."img/button_ok_over.png',1)\" align=\"absmiddle\" title=\"OK\" alt=\"OK\" onClick=\"addcondition();\" ".(($preview == "yes") ? "disabled=\"disabled\"" : "")." />\n";
          $condition_box .= "  </div>\n";
          $condition_box .= "</div>\n";
        }
        
        echo $condition_box;
        ?>

        <b><?php echo getescapedtext ($hcms_lang['conditions'][$lang]); ?></b><br />
        <textarea id="conditions" onblur="createSQL()" wrap="VIRTUAL" style="width:99%;" rows=2 <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?>><?php echo $conditions; ?></textarea>
      </td>
    </tr>
    <tr>
      <td valign="top">
        <hr />
        <b>Group by</b><br />
        <?php
        // add group by statement
        if (!empty ($attributes_all) && is_array ($attributes_all))
        {
          $field_box = "<div class=\"hcmsInfoBox\" style=\"display:block; margin:4px; width:98%;\">\n";
          $field_box .= "  <select id=\"groupby_attribute\" ".(($preview == "yes") ? "disabled=\"disabled\"" : "").">\n";
          
          foreach ($attributes_all as $value)
          {
            $field_box .= "    <option>".$value."</option>\n";
          }
          
          $field_box .= "  </select>\n";     
          $field_box .= "  <img name=\"Button2\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_ok.png\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button2','','".getthemelocation()."img/button_ok_over.png',1)\" align=\"absmiddle\" title=\"OK\" alt=\"OK\" onClick=\"addgroupby();\" ".(($preview == "yes") ? "disabled=\"disabled\"" : "")." />\n";
          $field_box .= "</div>\n";
        }
        
        echo $field_box;
        ?>
        <input id="group_by" onblur="createSQL()" type="text" style="width:99%;" value="<?php echo $group_by; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
      </td>
    </tr>
    <tr>
      <td valign="top">
        <hr />
        <b>Order by</b><br />
        <?php
        // add group by statement
        if (!empty ($attributes_all) && is_array ($attributes_all))
        {
          $field_box = "<div class=\"hcmsInfoBox\" style=\"display:block; margin:4px; width:98%;\">\n";
          $field_box .= "  <select id=\"orderby_attribute\" ".(($preview == "yes") ? "disabled=\"disabled\"" : "").">\n";
          
          foreach ($attributes_all as $value)
          {
            $field_box .= "    <option>".$value."</option>\n";
          }
          
          $field_box .= "  </select>\n";
          $field_box .= "  <select id=\"orderby_sort\" ".(($preview == "yes") ? "disabled=\"disabled\"" : "").">\n    <option></option>\n    <option>ASC</option>\n    <option>DESC</option>\n  </select>\n";
          $field_box .= "  <img name=\"Button3\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_ok.png\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button3','','".getthemelocation()."img/button_ok_over.png',1)\" align=\"absmiddle\" title=\"OK\" alt=\"OK\" onClick=\"addorderby();\" ".(($preview == "yes") ? "disabled=\"disabled\"" : "")." />\n";
          $field_box .= "</div>\n";
        }
        
        echo $field_box;
        ?>
        <input id="order_by" onblur="createSQL()" type="text" style="width:99%;" value="<?php echo $order_by; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
      </td>
    </tr>
    <tr>
      <td valign="top">
        <hr />
        <b>Concat by</b><br />
        <?php
        // add concat by
        if (!empty ($attributes) && is_array ($attributes))
        {
          $field_box = "  <select name=\"concat_by\" ".(($preview == "yes") ? "disabled=\"disabled\"" : "").">\n";
          $field_box .= "    <option> </option>\n";

          foreach ($attributes as $value)
          {
            if (strpos ($value, ".") > 0 && strpos ($value, ")") < 1) list ($table, $value) = explode (".", $value);
            if ($value != "") $field_box .= "    <option ".(($concat_by == $value) ? "selected" : "").">".$value."</option>\n";
          }
          
          $field_box .= "  </select>\n";
        }
        
        echo $field_box;
        ?>
      </td>
    </tr>
    <tr>
      <td valign="top">
        <hr />
        <b>Limit (#start, #next)</b><br />
        <input id="limit" onblur="createSQL()" type="text" style="width:99%;" value="<?php echo $limit; ?>" <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?> />
      </td>
    </tr>
    <tr>
      <td valign="top">
        <hr />
        <b><?php echo getescapedtext ($hcms_lang['sql-statement'][$lang]); ?></b><br />
        <textarea id="sql" name="sql" wrap="VIRTUAL" style="width:99%;" rows=4 <?php if ($preview == "yes") echo "disabled=\"disabled\""; ?>><?php echo $sql; ?></textarea>
      </td>
    </tr>
    <tr>
      <td valign="top">
        <hr />
        <b><?php echo getescapedtext ($hcms_lang['chart'][$lang]); ?></b><br />
        
        <?php
        // add empty first element to attributes array
        if (is_array ($attributes)) array_unshift ($attributes, " ");
        else $attributes = array (" ");
        ?>
        
        <!-- no chart -->
        <div class="hcmsInfoBox" style="float:left; margin:4px; width:300px; height:520px;">
          <input type="radio" id="chart_type" name="chart_type" value="" <?php if ($chart_type == "") echo "checked"; ?> /> <label for="chart_type"><b><?php echo getescapedtext ($hcms_lang['none'][$lang]); ?></b></label><br/>
          <img src="<?php echo getthemelocation(); ?>/img/chart_none.png" alt="<?php echo getescapedtext ($hcms_lang['none'][$lang]); ?>" />
        </div>
        
        <!-- pie chart -->
        <div class="hcmsInfoBox" style="float:left; margin:4px; width:300px; height:520px;">
          <input type="radio" id="chart_type" name="chart_type" value="pie" <?php if ($chart_type == "pie") echo "checked"; ?> /> <label for="chart_type"><b><?php echo getescapedtext ($hcms_lang['pie-chart'][$lang]); ?></b></label><br/>
          <img src="<?php echo getthemelocation(); ?>/img/chart_pie.png" alt="<?php echo getescapedtext ($hcms_lang['pie-chart'][$lang]); ?>" /><br/>
          <?php
          echo "X - ".getescapedtext ($hcms_lang['name'][$lang])."<br/>\n";
          echo "<input name=\"chart_pie_x_title\" type=\"text\" style=\"width:292px;\" value=\"".$chart_pie_x_title."\" ".(($preview == "yes") ? "disabled=\"disabled\"" : "")." />\n";
          echo "X - ".getescapedtext ($hcms_lang['value'][$lang])."<br/>\n";
          echo showselect ($attributes, true, $chart_pie_x_value, "", "name=\"chart_pie_x_value\" style=\"width:292px;\"")."<br/>\n";

          echo "Y - ".getescapedtext ($hcms_lang['name'][$lang])."<br/>\n";
          echo "<input name=\"chart_pie_y_title\" type=\"text\" style=\"width:292px;\" value=\"".$chart_pie_y_title."\" ".(($preview == "yes") ? "disabled=\"disabled\"" : "")." />\n";
          echo "Y - ".getescapedtext ($hcms_lang['value'][$lang])." (number-type)<br/>\n";
          echo showselect ($attributes, true, $chart_pie_y_value, "", "name=\"chart_pie_y_value\" style=\"width:292px;\"")."<br/>\n";
          ?> 
        </div>
        
        <!-- column chart -->
        <div class="hcmsInfoBox" style="float:left; margin:4px; width:300px; height:520px;">
          <input type="radio" id="chart_type" name="chart_type" value="column" <?php if ($chart_type == "column") echo "checked"; ?> /> <label for="chart_type"><b><?php echo getescapedtext ($hcms_lang['column-chart'][$lang]); ?></b></label><br/>
          <img src="<?php echo getthemelocation(); ?>/img/chart_column.png" alt="<?php echo getescapedtext ($hcms_lang['column-chart'][$lang]); ?>" /><br/>
          <?php
          echo "X - ".getescapedtext ($hcms_lang['name'][$lang])."<br/>\n";
          echo "<input name=\"chart_col_x_title\" type=\"text\" style=\"width:292px;\" value=\"".$chart_col_x_title."\" ".(($preview == "yes") ? "disabled=\"disabled\"" : "")." />\n";
          echo "X - ".getescapedtext ($hcms_lang['value'][$lang])."<br/>\n";
          echo showselect ($attributes, true, $chart_col_x_value, "", "name=\"chart_col_x_value\" style=\"width:292px;\"")."<br/>\n";

          echo "Y1 - ".getescapedtext ($hcms_lang['name'][$lang])."<br/>\n";
          echo "<input name=\"chart_col_y1_title\" type=\"text\" style=\"width:292px;\" value=\"".$chart_col_y1_title."\" ".(($preview == "yes") ? "disabled=\"disabled\"" : "")." />\n";
          echo "Y1 - ".getescapedtext ($hcms_lang['value'][$lang])." (number-type)<br/>\n";
          echo showselect ($attributes, true, $chart_col_y1_value, "", "name=\"chart_col_y1_value\" style=\"width:292px;\"")."<br/>\n";
          
          echo "Y2 - ".getescapedtext ($hcms_lang['name'][$lang])."<br/>\n";
          echo "<input name=\"chart_col_y2_title\" type=\"text\" style=\"width:292px;\" value=\"".$chart_col_y2_title."\" ".(($preview == "yes") ? "disabled=\"disabled\"" : "")." />\n";
          echo "Y2 - ".getescapedtext ($hcms_lang['value'][$lang])." (number-type)<br/>\n";
          echo showselect ($attributes, true, $chart_col_y2_value, "", "name=\"chart_col_y2_value\" style=\"width:292px;\"")."<br/>\n";
          
          echo "Y3 - ".getescapedtext ($hcms_lang['name'][$lang])."<br/>\n";
          echo "<input name=\"chart_col_y3_title\" type=\"text\" style=\"width:292px;\" value=\"".$chart_col_y3_title."\" ".(($preview == "yes") ? "disabled=\"disabled\"" : "")." />\n";
          echo "Y3 - ".getescapedtext ($hcms_lang['value'][$lang])." (number-type)<br/>\n";
          echo showselect ($attributes, true, $chart_col_y3_value, "", "name=\"chart_col_y3_value\" style=\"width:292px;\"")."<br/>\n";
          ?> 
        </div>
        
        <!-- timeline chart -->
        <div class="hcmsInfoBox" style="float:left; margin:4px; width:300px; height:520px;">
          <input type="radio" id="chart_type" name="chart_type" value="timeline" <?php if ($chart_type == "timeline") echo "checked"; ?> /> <label for="chart_type"><b><?php echo getescapedtext ($hcms_lang['timeline-chart'][$lang]); ?></b></label><br/>
          <img src="<?php echo getthemelocation(); ?>/img/chart_timeline.png" alt="<?php echo getescapedtext ($hcms_lang['timeline-chart'][$lang]); ?>" /><br/>
          <?php
          echo "Y - ".getescapedtext ($hcms_lang['name'][$lang])."<br/>\n";
          echo "<input name=\"chart_tl_y_title\" type=\"text\" style=\"width:292px;\" value=\"".$chart_tl_y_title."\" ".(($preview == "yes") ? "disabled=\"disabled\"" : "")." />\n";
          echo "Y - ".getescapedtext ($hcms_lang['value'][$lang])."<br/>\n";
          echo showselect ($attributes, true, $chart_tl_y_value, "", "name=\"chart_tl_y_value\" style=\"width:292px;\"")."<br/>\n";

          echo "X1 - ".getescapedtext ($hcms_lang['name'][$lang])."<br/>\n";
          echo "<input name=\"chart_tl_x1_title\" type=\"text\" style=\"width:292px;\" value=\"".$chart_tl_x1_title."\" ".(($preview == "yes") ? "disabled=\"disabled\"" : "")." />\n";
          echo "X1 - ".getescapedtext ($hcms_lang['value'][$lang])." (date-type)<br/>\n";
          echo showselect ($attributes, true, $chart_tl_x1_value, "", "name=\"chart_tl_x1_value\" style=\"width:292px;\"")."<br/>\n";
          
          echo "X2 - ".getescapedtext ($hcms_lang['name'][$lang])."<br/>\n";
          echo "<input name=\"chart_tl_x2_title\" type=\"text\" style=\"width:292px;\" value=\"".$chart_tl_x2_title."\" ".(($preview == "yes") ? "disabled=\"disabled\"" : "")." />\n";
          echo "X2 - ".getescapedtext ($hcms_lang['value'][$lang])." (date-type)<br/>\n";
          echo showselect ($attributes, true, $chart_tl_x2_value, "", "name=\"chart_tl_x2_value\" style=\"width:292px;\"")."<br/>\n";
          ?> 
        </div>
        
        <!-- geo chart -->
        <div class="hcmsInfoBox" style="float:left; margin:4px; width:300px; height:520px;">
          <input type="radio" id="chart_type" name="chart_type" value="geolocation" <?php if ($chart_type == "geolocation") echo "checked"; ?> /> <label for="chart_type"><b><?php echo getescapedtext ($hcms_lang['geo-chart'][$lang]); ?></b></label><br/>
          <img src="<?php echo getthemelocation(); ?>/img/chart_geolocation.png" alt="<?php echo getescapedtext ($hcms_lang['geo-chart'][$lang]); ?>" /><br/>
          <?php
          echo "Marker - ".getescapedtext ($hcms_lang['name'][$lang])."<br/>\n";
          echo showselect ($attributes, true, $chart_geo_name_value, "", "name=\"chart_geo_name_value\" style=\"width:292px;\"")."<br/>\n";

          echo "Marker - Latitude (number-type)<br/>\n";
          echo showselect ($attributes, true, $chart_geo_lat_value, "", "name=\"chart_geo_lat_value\" style=\"width:292px;\"")."<br/>\n";
          
          echo "Marker - Longitude (number-type)<br/>\n";
          echo showselect ($attributes, true, $chart_geo_lng_value, "", "name=\"chart_geo_lng_value\" style=\"width:292px;\"")."<br/>\n";
          
          echo "Marker - ".getescapedtext ($hcms_lang['link'][$lang])."<br/>\n";
          echo showselect ($attributes, true, $chart_geo_link_value, "", "name=\"chart_geo_link_value\" style=\"width:292px;\"")."<br/>\n";
          ?> 
        </div>
      </td>
    </tr>
  </table>  
</form>

</div>
</body>
</html>
