<?php
// ---------------------- STATS ---------------------
if (isset ($siteaccess) && is_array ($siteaccess))
{ 
  // title
  $title = getescapedtext ($hcms_lang['access-statistics-for'][$lang]);

  foreach ($siteaccess as $item_site)
  {
    // publication management config
    if (valid_publicationname ($item_site)) require ($mgmt_config['abs_path_data']."config/".$item_site.".conf.php");

    if (isset ($mgmt_config[$item_site]['dam']) && $mgmt_config[$item_site]['dam'] == true)
    {
      if (!empty ($is_mobile)) $width = "92%";
      else $width = "670px";
      
      echo "
      <div id=\"stats_".$item_site."\" class=\"hcmsHomeBox\" style=\"overflow:auto; margin:10px; width:".$width."; height:400px; float:left;\">
        <div class=\"hcmsHeadline\" style=\"margin:6px; white-space:nowrap;\"><img src=\"".getthemelocation("night")."img/site.png\" class=\"hcmsIconList\" /> ".$title." ".$item_site."</div>";
        
      $rootlocation_esc = "%comp%/".$item_site."/.folder";

      echo "
        <iframe src=\"service/accessstats.php?location=".url_encode($rootlocation_esc)."\" frameBorder=\"0\" scrolling=\"no\" style=\"width:100%; height:calc(100% - 44px); border:0; margin:0; padding:0; overflow:hidden;\"></iframe>";

      echo "
      </div>\n";
    }
  }
}
?>