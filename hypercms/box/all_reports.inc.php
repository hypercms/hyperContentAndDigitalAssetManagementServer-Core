<?php
// ---------------------- REPORTS ---------------------
if (!$is_mobile && isset ($siteaccess) && is_array ($siteaccess) && is_file ($mgmt_config['abs_path_cms']."report/index.php"))
{
  // language file
  require_once ("language/".getlanguagefile ($lang));
  
  if (!empty ($is_mobile)) $width = "92%";
  else $width = "670px";
  
  echo "
  <div id=\"reportviewer\" class=\"hcmsHomeBox\" style=\"overflow:auto; margin:10px; width:".$width."; height:400px; float:left;\">
    <div style=\"display:block; padding-bottom:5px;\">
      <div class=\"hcmsHeadline\" style=\"float:left; margin:6px;\">".getescapedtext ($hcms_lang['report'][$lang])." </div>
      <div style=\"float:left; margin:0px 10px 0px 2px;\">
        <select id=\"reportfile\" style=\"width:240px;\">
          <option value=\"".$mgmt_config['url_path_cms']."empty.php\">".getescapedtext ($hcms_lang['select'][$lang])."</option>
  ";
  
  $report_files = getdirectoryfiles ($mgmt_config['abs_path_data']."report/", ".report.dat");
  
  if (is_array ($report_files))
  {
    foreach ($report_files as $report_file)
    {
      if (strpos ($report_file, ".report.dat") > 0)
      {
        $report_name = substr ($report_file, 0, strpos ($report_file, ".report.dat"));

        // load report config file
        $report_config = loadreport ($report_file);

        if (trim ($report_config['title']) == "") $report_config['title'] = $report_name;

        echo "
          <option value=\"".$mgmt_config['url_path_cms']."report/?reportname=".url_encode($report_name)."\">".$report_config['title']."</option>";
      }
    }
  }
    
  echo"
        </select>
        <img name=\"Button\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_ok.png\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button','','".getthemelocation()."img/button_ok_over.png',1)\" title=\"OK\" alt=\"OK\" onClick=\"document.getElementById('report').src=document.getElementById('reportfile').value;\" />   
      </div>
      <div style=\"float:right;\"><img class=\"hcmsButton\" style=\"width:43px; height:22px; margin:6px;\" onClick=\"hcms_minMaxLayer('reportviewer');\" src=\"".getthemelocation()."img/button_plusminus_light.png\" alt=\"+/-\" title=\"+/-\" /></div>
    </div>
    <div style=\"width:100%; height:calc(100% - 42px);\">
      <iframe id=\"report\" src=\"".$mgmt_config['url_path_cms']."empty.php\" style=\"width:100%; height:100%; border:1px solid #000000;\"></iframe>
    </div>
  </div>";
}
?>