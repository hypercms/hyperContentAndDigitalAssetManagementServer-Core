<?php
// ---------------------- NEWS / WELCOME BOX ---------------------
if (empty ($mgmt_config['homebox_welcome'])) $mgmt_config['homebox_welcome'] = $mgmt_config['welcome'];

if (!empty ($mgmt_config['homebox_welcome']))
{
  if (!empty ($is_mobile)) $width = "92%";
  else $width = "680px";

  echo "
  <div id=\"newsbox\" class=\"hcmsHomeBox\" style=\"width:".$width."; height:410px; margin:10px; padding:0; float:left; overflow:auto;\">
    <div style=\"position:relative; right:20px; top:6px; z-index:300; float:right;\"><img class=\"hcmsButton\" style=\"width:43px; height:22px;\" onClick=\"hcms_minMaxLayer('newsbox');\" src=\"".getthemelocation()."img/button_plusminus_light.png\" alt=\"+/-\" title=\"+/-\" /></div>
    <iframe src=\"".$mgmt_config['homebox_welcome']."\" scrolling=\"yes\" style=\"position:relative; left:0; top:4px; width:100%; height:calc(100% - 32px); border:0; padding:0; margin:0; z-index:200;\" frameborder=\"0\" seamless=\"seamless\"></iframe>
  </div>";
}
?>