<?php
// ---------------------- REPORTS ---------------------
if (!$is_mobile && isset ($siteaccess) && is_array ($siteaccess) && is_file ($mgmt_config['abs_path_cms']."report/index.php"))
{
  // box width
  if (!empty ($is_mobile)) $width = "320px";
  else $width = "670px";

  $button = uniqid();

  echo "
  <div id=\"reportviewer\" class=\"hcmsHomeBox\" style=\"text-align:left; width:".$width."; height:400px; margin:10px; overflow:hidden;\">
    <div style=\"display:block; padding:0; margin:0;\">
      <div class=\"hcmsHeadline\" style=\"float:left; margin:6px 2px; white-space:nowrap;\"><img src=\"".getthemelocation("night")."img/template.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['report'][$lang])." </div>
      <div style=\"float:left; margin:0px 10px 0px 2px;\">
        <select id=\"reportfile\" style=\"width:240px;\">
          <option value=\"".cleandomain ($mgmt_config['url_path_cms'])."empty.php\">".getescapedtext ($hcms_lang['select'][$lang])."</option>
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
          <option value=\"".cleandomain ($mgmt_config['url_path_cms'])."report/?reportname=".url_encode($report_name)."\">".$report_config['title']."</option>";
      }
    }
  }

  echo"
        </select>
        <img name=\"".$button."\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_ok.png\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('".$button."','','".getthemelocation()."img/button_ok_over.png',1)\" title=\"OK\" alt=\"OK\" onClick=\"document.getElementById('report').src=document.getElementById('reportfile').value;\" />   
      </div>
      <div style=\"float:right;\"><img class=\"hcmsButtonTiny\" style=\"width:43px; height:22px; margin:6px;\" onClick=\"hcms_minMaxLayer('reportviewer');\" src=\"".getthemelocation()."img/button_plusminus_light.png\" alt=\"+/-\" title=\"+/-\" /></div>
    </div>
    <div style=\"display:block; width:100%; height:calc(100% - 42px); padding:0; margin:0;\">
      <iframe id=\"report\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."empty.php\" style=\"width:100%; height:100%; border:0;\"frameborder=\"0\" seamless=\"seamless\"></iframe>
    </div>
  </div>";
}
?>