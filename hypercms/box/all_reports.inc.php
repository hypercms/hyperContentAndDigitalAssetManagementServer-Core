<?php
// ---------------------- REPORTS ---------------------
if (!$is_mobile && isset ($siteaccess) && is_array ($siteaccess) && is_file ($mgmt_config['abs_path_cms']."report/index.php"))
{
  // language file
  require_once ("language/".getlanguagefile ($lang));
  
  if ($is_mobile) $width = "92%";
  else $width = "670px";
  
  echo "
  <div id=\"reportviewer\" class=\"hcmsHomeBox\" style=\"overflow:auto; margin:10px; width:".$width."; height:400px; float:left;\">
    <div style=\"display:block; padding-bottom:5px;\">
      <span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['report'][$lang])." </span>
      <select name=\"reportfile\" onChange=\"document.getElementById('report').src=this.value\">
        <option value=\"".$mgmt_config['url_path_cms']."empty.php\">".getescapedtext ($hcms_lang['select'][$lang])."</option>
  ";
  
  $report_files = getdirectoryfiles ($mgmt_config['abs_path_data']."report/", ".report.dat");
  
  if (is_array ($report_files))
  {
    foreach ($report_files as $value)
    {
      if (strpos ($value, ".report.dat") > 0)
      {
        $item_name = substr ($value, 0, strpos ($value, ".report.dat"));
      
        echo "
        <option value=\"".$mgmt_config['url_path_cms']."report/?reportname=".url_encode($item_name)."\">".$item_name."</option>";
      }
    }
  }
    
  echo"
      </select>
    </div>
    <iframe id=\"report\" src=\"".$mgmt_config['url_path_cms']."empty.php\" style=\"width:100%; height:360px; border:1px solid #000000;\"></iframe>
  </div>\n";
}
?>