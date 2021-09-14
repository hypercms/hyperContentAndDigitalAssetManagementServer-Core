<?php
// ---------------------- STATS ---------------------
if (isset ($siteaccess) && is_array ($siteaccess))
{
  // box width
  if (!empty ($is_mobile)) $width = "320px";
  else $width = "670px";
  
  // get favorites of user
  $object_array = getfavorites ($user);
  
  if (!empty ($object_array) && is_array ($object_array) && sizeof ($object_array) > 0)
  {
    foreach ($object_array as $item_objectinfo)
    {
      if (!empty ($item_objectinfo['objectpath'])) $item_objectpath = $item_objectinfo['objectpath'];
      else $item_objectpath = $item_objectinfo;
      
      if ($item_objectpath != "")
      {
        $item_site = getpublication ($item_objectpath);
        $item_cat = getcategory ($item_site, $item_objectpath);
        $item_location = getlocation ($item_objectpath);
        $item_object = getobject ($item_objectpath);
        $item_fileinfo = getfileinfo ($item_site, $item_location.$item_object, $item_cat);

        echo "
        <div id=\"stats_".$item_object."\" onclick=\"hcms_openWindow('frameset_content.php?site=".url_encode($item_site)."&ctrlreload=yes&cat=".url_encode($item_cat)."&location=".url_encode($item_location)."&page=".url_encode($item_object)."', '".$item_object."', 'location=no,menubar=no,toolbar=no,titlebar=no,location=no,status=yes,scrollbars=no,resizable=yes,titlebar=no', ".windowwidth ("object").", ".windowheight ("object").");\" class=\"hcmsHomeBox\" style=\"cursor:pointer; margin:10px; width:".$width."; height:400px; float:left; overflow:auto; ".($is_iphone ? "-webkit-overflow-scrolling:touch;" : "")."\">
          <div class=\"hcmsHeadline\" style=\"margin:6px; white-space:nowrap;\"><img src=\"".getthemelocation("night")."img/favorites.png\" class=\"hcmsIconList\" /> ".(empty ($is_mobile) ? getescapedtext ($hcms_lang['access-statistics-for'][$lang]) : "")." ".showshorttext($item_fileinfo['name'], 40)."</div>
          <iframe src=\"service/accessstats.php?location=".url_encode($item_objectpath)."\" frameBorder=\"0\" style=\"width:100%; height:calc(100% - 44px); border:0; margin:0; padding:0; overflow:auto;\"></iframe>
        </div>\n";
      }
    }
  }
}
?>