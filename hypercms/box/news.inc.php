<?php
// ---------------------- NEWS / WELCOME BOX ---------------------
if (empty ($mgmt_config['homebox_welcome'])) $mgmt_config['homebox_welcome'] = $mgmt_config['welcome'];

if (!empty ($mgmt_config['homebox_welcome']))
{
  if (!empty ($is_mobile)) $width = "92%";
  else $width = "670px";

  echo "
  <div id=\"newsbox\" class=\"hcmsHomeBox\" style=\"width:".$width."; height:400px; margin:10px; overflow:hidden; float:left;\">
    <div style=\"display:block; padding:0; margin:0;\">
      <div class=\"hcmsHeadline\" style=\"float:left; margin:6px;\">".getescapedtext ($hcms_lang['information'][$lang])." </div>
      <div style=\"float:right;\"><img class=\"hcmsButtonTiny\" style=\"width:43px; height:22px; margin:6px;\" onClick=\"hcms_minMaxLayer('newsbox');\" src=\"".getthemelocation()."img/button_plusminus_light.png\" alt=\"+/-\" title=\"+/-\" /></div>
    </div>
    <div style=\"display:block; width:100%; height:calc(100% - 42px); padding:0; margin:0;\">
      <iframe src=\"".$mgmt_config['homebox_welcome']."\" style=\"width:100%; height:100%; border:0; overflow:scroll;\" frameborder=\"0\" seamless=\"seamless\"></iframe>
    </div>
  </div>";
}
?>