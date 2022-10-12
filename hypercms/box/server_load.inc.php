<?php
// ---------------------- STATS ---------------------
if (isset ($siteaccess) && is_array ($siteaccess))
{ 
  // title
  $title = getescapedtext ("Server load and memory usage");

  // box width
  if (!empty ($is_mobile)) $width = "320px";
  else $width = "670px";
  
  echo "
  <div id=\"stats_serverload\" class=\"hcmsHomeBox\" style=\"text-align:left; overflow:auto; margin:10px; width:".$width."; height:400px; ".($is_iphone ? "-webkit-overflow-scrolling:touch;" : "")."\">
    <div class=\"hcmsHeadline\" style=\"margin:6px 2px;\"><img src=\"".getthemelocation("night")."img/button_script.png\" class=\"hcmsIconList\" /> ".$title."</div>
    <iframe src=\"service/serverload.php\" frameBorder=\"0\" style=\"width:100%; height:calc(100% - 44px); border:0; margin:0; padding:0; overflow:auto;\"></iframe>
  </div>\n";
}
?>