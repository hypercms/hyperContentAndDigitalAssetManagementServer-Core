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
  <div id=\"newsbox\" class=\"hcmsHomeBox\" style=\"text-align:left; width:".$width."; height:400px; margin:10px; overflow:hidden;\">
    <div style=\"display:block; padding:0; margin:0;\">
      <div class=\"hcmsHeadline\" style=\"float:left; margin:6px 2px;\"><img src=\"".getthemelocation("night")."img/info.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['information'][$lang])." </div>
      <div style=\"float:right;\"><img class=\"hcmsButtonTiny\" style=\"width:22px; height:22px; margin:6px;\" onclick=\"hcms_minMaxLayer('newsbox');\" src=\"".getthemelocation("night")."img/button_windowsize.png\" alt=\"".getescapedtext ($hcms_lang['view'][$lang])."\" title=\"".getescapedtext ($hcms_lang['view'][$lang])."\" /></div>
    </div>
    <div style=\"display:block; width:100%; height:calc(100% - 42px); padding:0; margin:0; ".($is_iphone ? "overflow:auto; -webkit-overflow-scrolling:touch;" : "overflow:hidden;")."\">
      <iframe src=\"".$mgmt_config['homebox_welcome']."\" style=\"width:100%; height:100%; border:0; overflow:scroll;\" frameborder=\"0\" seamless=\"seamless\"></iframe>
    </div>
  </div>";
}
?>