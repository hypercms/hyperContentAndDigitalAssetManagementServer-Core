<?php
// ---------------------- NEWS / WELCOME BOX ---------------------
if ($mgmt_config['welcome'] != "") 
{
  if ($is_mobile) $width = "92%";
  else $width = "670";
  
  echo "<iframe width=\"".$width."\" height=\"400\" src=\"".$mgmt_config['welcome']."\" scrolling=\"yes\" class=\"hcmsInfoBox\" style=\"margin:10px; float:left;\" seamless=\"seamless\"></iframe>\n";
}
?>