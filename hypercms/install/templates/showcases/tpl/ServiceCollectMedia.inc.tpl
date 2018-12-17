<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>ServiceCollectMedia</name>
<user>admin</user>
<category>inc</category>
<extension></extension>
<application></application>
<content><![CDATA[[hyperCMS:scriptbegin
// function: sortByName()
// description:
// helper function for sorting outcome

function sortByName ($a, $b)
{
  return strnatcmp ($a['name'], $b['name']);
}

// function: collectMedia()
// input: publication name [string], containerid [string], mediaTagId [string], absolute component root path [string], allowedFileExtensions [.jpg.jpeg.gif.png] (optional), text ID of the image title [string] (optional), 
//          text Id of the image description [string] (optional), filter text-ID and filter value pairs [array] (optional), search expressions [array]
// output: result array, each array contains name / link / thumb_link of a mediafile / false on error

// description:
// Performs a media search or retrieves the location of the given mediaTag container_id tuple and collects all mediafiles of this location.

function collectMedia ($site, $container_id, $mediaTagId, $abs_comp, $allowedFileExtensions=".jpg.jpeg.gif.png", $metaTitleId="", $metaDescriptionId="", $filter=array())
{
  global $mgmt_config;
  
  // check if paramters are empty
  if (empty ($site) || empty ($container_id) || empty ($mediaTagId) || empty ($abs_comp)) return false;
  
  // retrieve folder of mediafile via container/mediaTagId
  $data = loadcontainer ($container_id, "work", "sys");
  $folder = "";
  
  if ($data)
  {
    $media = selectcontent ($data, "<media>", "<media_id>", $mediaTagId);
    
    // Fully determine Folder
    if ($media) $folder = str_replace ("%comp%", $abs_comp, dirname (current (getcontent ($media[0], "<mediaobject>"))))."/";
    else return false;
  }
  else return false;

  // check if folder is empty
  if (empty ($folder) && !is_dir ($folder))
  {
    return false;
  }

  // collect mediafiles
  $location_esc = convertpath ($site, $folder, "comp");
  $item_site = getpublication ($location_esc);
  $files = array();
  $i = 0;
  
  $scandir = scandir ($folder);
  
  foreach ($scandir as $file)
  {
    // check if file exists
    if ($file != "." && $file != ".." && $file != ".folder" && is_file ($folder.$file))
    {
      $fileinfo = getfileinfo ($item_site, $file, "comp");
      $objectinfo = getobjectinfo ($item_site, $folder, $file, "sys");	
      $mediafileinfo = getfileinfo ($item_site, $objectinfo['media'], "comp");

      if (substr_count ($allowedFileExtensions, $mediafileinfo['ext']) > 0)
      {
        $i++;

        $medialocation = getmedialocation ($item_site, $objectinfo['media'], "abs_path_media");
        $link = createwrapperlink ($item_site, $folder, $file, "comp");        
        $abspath = $medialocation.$item_site."/";
        
        // create thumbnail link
        if (is_file ($thumbnail_path=$medialocation.$item_site."/".$mediafileinfo['filename'].".thumb.jpg"))
        {	
          $thumb_link = createviewlink ($item_site, $mediafileinfo['filename'].".thumb.jpg");
        }
        else
        {
          $thumb_link = createviewlink ($item_site, $objectinfo['media']);
        }
        
        // retrieve image size
        list ($width, $height, $rest) = getimagesize ($medialocation.$item_site."/".$objectinfo['media']);

        // retrieve additional
        $contentdata = loadcontainer ($objectinfo['container_id'], "work", "sys");

        if (!empty ($contentdata))
        {
          if (!empty ($metaTitleId))
          {
            $titletext = selectcontent ($contentdata, "<text>", "<text_id>", $metaTitleId);
            $title = ($titletext) ? current(getcontent($titletext[0], "<textcontent>")) : '';
          }
          else $title = "";
          
          if (!empty ($metaDescriptionId))
          {
            $desctext = selectcontent ($contentdata, "<text>", "<text_id>", $metaDescriptionId);          
            $desc = ($desctext) ? current(getcontent($desctext[0], "<textcontent>")) : '';
          }
          else $desc = "";
          
          if (!empty ($filter['name']))
          {
            $filtertext = selectcontent ($contentdata, "<text>", "<text_id>", $filter['name']);
            $filtervalue = ($filtertext) ? current(getcontent($filtertext[0], "<textcontent>")) : '';
          }
          else $filtervalue = "";
        }

        // return array
        if (!is_array ($filter) || sizeof ($filter) < 1 || (!empty ($filter['name']) && strpos ("_".$filtervalue, $filter['value']) > 0))
        {
          $files[] = array("name" => $fileinfo['name'], "title"=>$title, "description"=>$desc, "link" => $link, "thumb_link" => $thumb_link, "abspath"  => $abspath, "filename" => $objectinfo['media'], "width" => $width, "height" => $height);
        }

        // sort result via usort and helperfunction
        usort ($files, "sortByName");
      }
    }
  }

  return $files;
}
scriptend]]]></content>
</template>