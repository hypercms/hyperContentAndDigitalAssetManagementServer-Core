<?php
// ---------------------- NEWS / WELCOME BOX ---------------------
if (empty ($mgmt_config['homebox_welcome'])) $mgmt_config['homebox_welcome'] = $mgmt_config['welcome'];

if (!empty ($mgmt_config['homebox_welcome']))
{
  if (!empty ($is_mobile)) $width = "92%";
  else $width = "680px";

  echo "<iframe src=\"".$mgmt_config['homebox_welcome']."\" scrolling=\"yes\" class=\"hcmsHomeBox\" style=\"width:".$width."; height:410px; border:0; padding:0; margin:10px; float:left;\" seamless=\"seamless\" border=\"0\"></iframe>\n";
}
?>