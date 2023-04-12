<?php
// ---------------------- FAVORITES ---------------------
// get favorites of user
if (!empty ($user)) $objectinfo_array = getfavorites ($user, "path");

if (!empty ($objectinfo_array) && is_array ($objectinfo_array) && sizeof ($objectinfo_array) > 0)
{
  // box width
  if (!empty ($is_mobile)) $width = "320px";
  else $width = "320px";

  // prepare array
  $object_array = array();

  foreach ($objectinfo_array as $hash => $objectinfo)
  {
    if (!empty ($objectinfo['objectpath'])) $object_array[$hash] = $objectinfo['objectpath'];
  }

  // output
  if (is_array ($object_array) && sizeof ($object_array) > 0)
  {
    echo "
    <div id=\"favorites\" class=\"hcmsHomeBox\" style=\"text-align:left; margin:10px; width:".$width."; height:400px;\">
      <div class=\"hcmsHeadline\" style=\"margin:6px 2px; white-space:nowrap;\"><img src=\"".getthemelocation("night")."img/favorites.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['favorites'][$lang])."</div>";

    array_reverse ($object_array);
    reset ($object_array);
    $i = 0;
    
    foreach ($object_array as $hash => $objectpath)
    {
      // show only object items
      if (getobject ($objectpath) != ".folder" && $i < 20)
      {
        // get site
        $item_site = getpublication ($objectpath);        
        // get category
        $item_cat = getcategory ($item_site, $objectpath); 
        
        if (valid_publicationname ($item_site) && $item_cat != "")
        {
          // get location
          $item_location_esc = getlocation ($objectpath);
          // get location in file system
          $item_location = deconvertpath ($item_location_esc, "file");           
          // get location name
          $item_locationname = getlocationname ($item_site, $item_location_esc, $item_cat, "path");        
          // get object name
          $item_object = getobject ($objectpath);  
          $item_object = correctfile ($item_location, $item_object, $user); 
          $item_fileinfo = getfileinfo ($item_site, $item_location.$item_object, $item_cat);
          $item_objectinfo = getobjectinfo ($item_site, $item_location, $item_object, $user);
          
          // check access permission
          $ownergroup = accesspermission ($item_site, $item_location, $item_cat);
          $setlocalpermission = setlocalpermission ($item_site, $ownergroup, $item_cat);
          
          if ($ownergroup != false && $setlocalpermission['root'] == 1 && valid_locationname ($item_location) && valid_objectname ($item_object))
          {
            // open on double click
            $openObject = "onclick=\"hcms_openWindow('frameset_content.php?ctrlreload=yes&site=".url_encode($item_site)."&cat=".url_encode($item_cat)."&location=".url_encode($item_location_esc)."&page=".url_encode($item_object)."', '".$item_objectinfo['container_id']."', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes', ".windowwidth ("object").", ".windowheight ("object").");\"";
          
            echo "
            <div ".$openObject." style=\"display:block; cursor:pointer;\" title=\"".$item_locationname.$item_fileinfo['name']."\"><img src=\"".getthemelocation()."img/".$item_fileinfo['icon']."\" class=\"hcmsIconList\" />&nbsp;<a href=\"javascript:void(0);\">".showshorttext($item_fileinfo['name'], 30, false)."</a>&nbsp;</div>";
            $i++;
          }
        }
      }
    }
    
    echo "
    </div>";
  }
}
?>