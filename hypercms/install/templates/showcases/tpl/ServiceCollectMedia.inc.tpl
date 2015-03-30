<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>ServiceCollectMedia</name>
<user>hypercms</user>
<category>inc</category>
<extension></extension>
<application></application>
<content><![CDATA[[hyperCMS:scriptbegin

/*
helper function for sorting outcome
*/
function sortByName($a, $b)
{
   return strnatcmp($a['name'], $b['name']);
}

/*
Retrieves the location of the given mediaTag container_id tuple and collects
than all mediafiles of this location.
@param string site/publication 
@param string containerid 
@param string mediaTagId
@param string allowedFileExtensions e.g. ".jpg.gif.png"
@param string abs_comp
@return array array of array where each array contains name / link / thumb_link of a mediafile
*/	
function collectMedia($site, $container_id, $mediaTagId, $abs_comp, $allowedFileExtensions = "", $metaTitleId = "", $metaDescriptionId = "")
{
  global $mgmt_config;
  
  //check if paramters are empty
  if(empty($site) || empty($container_id) || empty($mediaTagId) || empty($abs_comp))
  {
    return false;
  }
  
  //retrieve folder of mediafile via container/mediaTagId
  $data = loadcontainer($container_id, "work", "sys");
  $folder = "";
  
  if($data)
  {
    $media = selectcontent($data, "<media>", "<media_id>", $mediaTagId);
    
    if($media)
    {
      // Fully determine Folder
      $folder = str_replace("%comp%", $abs_comp, dirname(current(getcontent($media[0], "<mediaobject>"))))."/";
    }
    else
    {
      return false;
    }
  }
  else
  {
    return false;
  }
  
  //check if folder is empty
  if(empty($folder) && !is_dir($folder))
  {
    return false;
  }

  //collect mediafiles
  $location_esc = convertpath($site, $folder, "comp");
  $item_site = getpublication($location_esc);
  $files = array();
  $dir_handle = opendir($folder);
  
  while($file = readdir($dir_handle))
  {
    //check if file exists
    if($file != "." && $file != ".." && $file != ".folder" && is_file($folder.$file))
    {
      $fileinfo = getfileinfo ($item_site, $file, "comp");
      $objectinfo = getobjectinfo ($item_site, $folder, $file, "sys");	
      $mediafileinfo = getfileinfo ($item_site, $objectinfo['media'], "comp");

      if (substr_count ($allowedFileExtensions, $mediafileinfo['ext']) > 0)
      {
        $medialocation = getmedialocation ($item_site, $objectinfo['media'], "abs_path_media");
        $link = createwrapperlink($item_site, $folder, $file, "comp");        
        $abspath = $medialocation.$item_site."/";
        
        //create thumbnail link
        if (@is_file ($thumbnail_path=$medialocation.$item_site."/".$mediafileinfo['filename'].".thumb.jpg") && @filesize ($medialocation.$item_site."/".$mediafileinfo['filename'].".thumb.jpg") > 400)
        {	
          $thumb_link = $mgmt_config['url_path_cms']."explorer_wrapper.php?site=".url_encode($item_site)."&media=".url_encode($item_site."/".$mediafileinfo['filename'].".thumb.jpg")."&token=".hcms_crypt($item_site."/".$mediafileinfo['filename'].".thumb.jpg");
        }
        else
        {
          $thumb_link = $picture_link;
        }

        //retrieve additional
        $contentdata = loadcontainer($objectinfo['container_id'], "work", "sys");

        if (!empty($contentdata))
        {
          $titletext = selectcontent($contentdata, "<text>", "<text_id>", "Title");
          $desctext = selectcontent($contentdata, "<text>", "<text_id>", "Description");
          $title = ($titletext) ? current(getcontent($titletext[0], "<textcontent>")) : ''; 
          $desc = ($desctext) ? current(getcontent($desctext[0], "<textcontent>")) : '';
        }

        //return array
        $files[] = array("name" => $fileinfo['name'], "title"=>$title, "description"=>$desc, "link" => $link, "thumb_link" => $thumb_link, "abspath"  => $abspath, "filename" => $objectinfo['media']);
        //sort result via usort and helperfunction
        usort($files, "sortByName");
      }
    }
  }

  return $files;
}

scriptend]]]></content>
</template>