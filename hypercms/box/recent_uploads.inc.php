<?php
// ---------------------- RECENT UPLOADS ---------------------

$maxcount = 20;

$object_array = array();

// get downloads stats of user (use higher limit since folders will not be displayed)
$stats_array = rdbms_getmediastat ("", "", "upload", "", "", $user, false, 60);

// prepare array
if (!empty ($stats_array) && is_array ($stats_array) && sizeof ($stats_array) > 0)
{
  $stats_array = array_reverse ($stats_array);
  reset ($stats_array);
  $i = 0;

  foreach ($stats_array as $key => $stats)
  {
    if (!empty ($stats['container_id']) && $i < ($maxcount * 5))
    {
      $result_array = rdbms_getobjects ($stats['container_id']);

      if (is_array ($result_array))
      {
        foreach ($result_array as $hash=>$result)
        {
          if (!empty ($result['objectpath']) && !in_array ($result['objectpath'], $object_array)) $object_array[$hash] = $result['objectpath'];
          $i++;
        }
      }
    }
  }
}

// output
if (is_array ($object_array) && sizeof ($object_array) > 0)
{
  $object_array = array_unique ($object_array);

  if (!empty ($is_mobile)) $width = "92%";
  else $width = "320px";
  
  echo "
  <div id=\"recent_downloads\" class=\"hcmsHomeBox\" style=\"margin:10px; width:".$width."; height:400px; float:left;\">
    <div class=\"hcmsHeadline\" style=\"margin:6px;\">".getescapedtext ($hcms_lang['my-recent-uploads'][$lang])."</div>";

  reset ($object_array);
  $i = 1;

  foreach ($object_array as $objectpath)
  {
    // show only object items
    if (getobject ($objectpath) != ".folder")
    {
      // max count reached
      if ($i > $maxcount) break;

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
          $openObject = "onClick=\"hcms_openWindow('frameset_content.php?ctrlreload=yes&site=".url_encode($item_site)."&cat=".url_encode($item_cat)."&location=".url_encode($item_location_esc)."&page=".url_encode($item_object)."', '".$item_objectinfo['container_id']."', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes', ".windowwidth ("object").", ".windowheight ("object").");\"";

          echo "
          <div ".$openObject." style=\"display:block; cursor:pointer;\" title=\"".$item_locationname.$item_fileinfo['name']."\"><img src=\"".getthemelocation()."img/".$item_fileinfo['icon']."\" class=\"hcmsIconList\" />&nbsp;".showshorttext($item_fileinfo['name'], 30)."&nbsp;</div>";
          $i++;
        }
      }
    }
  }

  echo "
  </div>\n";
}
?>