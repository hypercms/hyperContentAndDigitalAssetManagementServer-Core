<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>Breadcrumb</name>
<user>hypercms</user>
<category>inc</category>
<extension></extension>
<application></application>
<content><![CDATA[[hyperCMS:scriptbegin

global $mgmt_config;

$startpos = 5;
$location = "%abs_page%/";
$dir_array = explode ("/", "%abs_location%" );
$showbreadcrumb = false;

if(sizeof($dir_array) > 6) {
  function getCurrentTitle()
  {
    $xmldata = getobjectcontainer ("%publication%", "%abs_location%", "%object%", "sys");

    if ($xmldata != false)
    {
      $textnode = selectcontent ($xmldata, "<text>", "<text_id>", "Title");
      if ($textnode != false) $title = getcontent ($textnode[0], "<textcontent>");
      else $title = false;
      if ($title != false && $title != "") return $title[0];
      else return "%object%";
    }
    else return "%object%";
  }
 
 function getBreadcrumbElement ($path = "")
  { 
    if (empty($path)) return false;
    $elements = array();
    $files = array();
    $scandir = scandir ($path);

    if ($scandir)
    {
      foreach ($scandir as $file)
      {
        if (pathinfo($file, PATHINFO_EXTENSION) == "php" && pathinfo($file, PATHINFO_EXTENSION) != "off") $files[] = $file;
      }

      natcasesort ($files);
      $files = array_values ($files);
    }
    
    if (is_array ($files))
    {
      for ($i = 0; $i < sizeof($files); $i++)
      {
        $e = readnavigation ("%publication%", $path, $files[$i], "sys");
  
        if (!$e['hide'])
        {
          if (!empty($e['order']))
          {
            if ($e['order'] == "X") $key = "X-".$i;  
            else $key = $e['order']."-".$i;;
          }
          else $key = "X-".$i;
  
          $elements[$key]['title'] = $e['title'] ;
          $elements[$key]['href'] = str_replace ("%abs_page%", "%url_page%", $path.$files[$i]);
        }
      }
  
      if (is_array ($elements))
      {
        ksort ($elements, SORT_STRING);
        reset ($elements);
    
        if (!empty ($elements))
        {
          $result = array_slice ($elements, 0, 1);
          return array_shift ($result);
        }
        else return false;
      }
      else return false;
    }
    else return false;
  }

scriptend]
<!-- Breadcrumb -->
<div class="breadcrumb">
[hyperCMS:scriptbegin
  for ($i = $startpos; $i < sizeof ($dir_array) ; $i++)
  {
     if ($i == (sizeof ($dir_array) - 1) && $showbreadcrumb)
    {
      echo getCurrentTitle ();
      break;
    }
    else
    {
      $e = getBreadcrumbElement ($location);

      if (!empty($e))
      {
scriptend]
	<a href="[hyperCMS:scriptbegin echo $e["href"]; scriptend]" title="[hyperCMS:scriptbegin echo $e["title"]; scriptend]">[hyperCMS:scriptbegin echo $e["title"]; scriptend]</a>
	 > 
[hyperCMS:scriptbegin
        $showbreadcrumb = true;
      }
    }

    if (array_key_exists (($i+1), $dir_array)) $location .= $dir_array[$i+1].'/';
  }
scriptend]
</div>
<!-- Breadcrumb -->
[hyperCMS:scriptbegin
}
scriptend]]]></content>
</template>