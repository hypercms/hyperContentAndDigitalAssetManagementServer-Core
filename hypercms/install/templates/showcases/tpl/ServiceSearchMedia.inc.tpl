<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>ServiceSearchMedia</name>
<user>admin</user>
<category>inc</category>
<extension></extension>
<application></application>
<content><![CDATA[<?php
// function: searchMedia()
// input: publication name [string], containerid [string], mediaTagId [string], absolute component root path [string], allowedFileExtensions [.jpg.jpeg.gif.png] (optional), text ID of the image title [string] (optional), 
//          text Id of the image description [string] (optional), filter text-ID and filter value pairs [array] (optional)
// output: result array, each array contains name / link / thumb_link of a mediafile / false on error

// description:
// Performs a media search or retrieves the location of the given mediaTag container_id and collects all mediafiles of this location.

function searchMedia ($site, $container_id, $mediaTagId, $abs_comp, $allowedFileExtensions=".jpg.jpeg.gif.png", $metaTitleId="", $metaDescriptionId="", $filter=array())
{
  global $mgmt_config;

  // check if paramters are empty
  if (empty ($site) || empty ($container_id) || empty ($mediaTagId) || empty ($abs_comp)) return false;
  
  // retrieve folder of mediafile via container/mediaTagId
  $data = loadcontainer ($container_id, "work", "sys");

  if ($data)
  {
    $media = selectcontent ($data, "<media>", "<media_id>", $mediaTagId);
    
    if (!empty ($media[0]))
    {
      // Fully determine Folder
      $temp = getcontent ($media[0], "<mediaobject>");
      if (!empty ($temp[0])) $folder = getlocation ($temp[0]);
    }
    else return false;
  }
  else return false;
  
  // check if folder is empty
  if (empty ($folder)) return false;

  // collect mediafiles
  $location_esc = convertpath ($site, $folder, "comp");
  $files = array();
  $i = 0;

  // use image search
  if (!empty ($mgmt_config['publicsearch']) && is_array ($filter) && sizeof ($filter) > 0)
  {
    // include metadata in result
    $text_id_array = array("text:".$metaTitleId, "text:".$metaDescriptionId);

    // search
    $searchcontent  = rdbms_searchcontent ($location_esc, "", array("image"), "", "", "", $filter, "", "", "", "", "", "", "", "", 100, $text_id_array, true, false);

    if ($searchcontent ['count'] > 0)
    {
      foreach ($searchcontent as $hash=> $objectinfo)
      {
        if (!empty ($objectinfo['objectpath']))
        {
          $temp_site = getpublication ($objectinfo['objectpath']);
          $temp_location = getlocation ($objectinfo['objectpath']);
          $temp_object = getobject ($objectinfo['objectpath']);
          $temp_media = $objectinfo['media'];

          if ($site == $temp_site && $temp_media != "")
          {
            $fileinfo = getfileinfo ($temp_site, $temp_location.$temp_object, "comp");
            $mediafileinfo = getfileinfo ($temp_site, $temp_media, "comp");
            $medialocation = getmedialocation ($temp_site, $temp_media, "abs_path_media");
            $link = $mgmt_config['url_path_cms']."?wl=".$hash;
        
            // create thumbnail link
            if (is_file ($thumbnail_path=$medialocation.$temp_site."/".$mediafileinfo['filename'].".thumb.jpg"))
            {
              $thumb_link = createviewlink ($temp_site, $mediafileinfo['filename'].".thumb.jpg");
            }
            else
            {
              $thumb_link = createviewlink ($item_site, $temp_media);
            }

            // retrieve image size
            list ($width, $height, $rest) = getimagesize ($medialocation.$temp_site."/".$temp_media);

            $files[] = array("name" => $fileinfo['name'], "title"=>$objectinfo['text:'.$metaTitleId], "description"=>$objectinfo['text:'.$metaDescriptionId], "link" => $link, "thumb_link" => $thumb_link, "abspath"  => $medialocation.$temp_site."/", "filename" => $temp_media, "width" => $width, "height" => $height);
          }
        }
      }
    }
  }

  if (!empty ($files)) return $files;
  else return false;
}
?>]]></content>
</template>