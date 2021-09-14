<?php
// ---------------------- NEWS / WELCOME BOX ---------------------
// migration to version 8.0.4
if (empty ($mgmt_config['homebox_welcome']) && !empty ($mgmt_config['welcome'])) $mgmt_config['homebox_welcome'] = $mgmt_config['welcome'];

if (!empty ($mgmt_config['homebox_welcome']))
{
  // box width
  if (!empty ($is_mobile)) $width = "320px";
  else $width = "670px";

  echo "
  <div id=\"newsbox\" class=\"hcmsHomeBox\" style=\"width:".$width."; height:400px; margin:10px; overflow:hidden; float:left;\">
    <div style=\"display:block; padding:0; margin:0;\">
      <div class=\"hcmsHeadline\" style=\"float:left; margin:6px;\">".getescapedtext ($hcms_lang['information'][$lang])." </div>
      <div style=\"float:right;\"><img class=\"hcmsButtonTiny\" style=\"width:43px; height:22px; margin:6px;\" onClick=\"hcms_minMaxLayer('newsbox');\" src=\"".getthemelocation()."img/button_plusminus_light.png\" alt=\"+/-\" title=\"+/-\" /></div>
    </div>
    <div style=\"display:block; width:100%; height:calc(100% - 42px); padding:0; margin:0; ".($is_iphone ? "overflow:auto; -webkit-overflow-scrolling:touch;" : "overflow:hidden;")."\">
      <iframe src=\"".$mgmt_config['homebox_welcome']."\" style=\"width:100%; height:100%; border:0; overflow:scroll;\" frameborder=\"0\" seamless=\"seamless\"></iframe>
    </div>
  </div>";
}
?>