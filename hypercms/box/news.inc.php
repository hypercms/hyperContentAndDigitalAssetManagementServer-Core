<?php
// ---------------------- NEWS / WELCOME BOX ---------------------
if ($mgmt_config['welcome'] != "") 
{
  if ($is_mobile) $width = "92%";
  else $width = "680px";
  
  echo "<iframe src=\"".$mgmt_config['welcome']."\" scrolling=\"yes\" class=\"hcmsHomeBox\" style=\"width:".$width."; height:410px; border:0; padding:0; margin:10px; float:left;\" seamless=\"seamless\" border=\"0\"></iframe>\n";
}
?>