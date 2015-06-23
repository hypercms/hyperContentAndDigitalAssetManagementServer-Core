<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// =================================== META DATA FUNCTIONS =======================================

// --------------------------------------- getkeywords -------------------------------------------
// function: getkeywords ()
// input: text as string, supported language [de,en]
// output: keywords sperated by , /false on error

// description:
// generates a keyword list for meta information. supports german and english stop words lists.

function getkeywords ($text, $language="en", $charset="UTF-8")
{
  global $mgmt_config;
	
  if ($text != "")
  {
    // include stopword lists
    include ($mgmt_config['abs_path_cms']."/include/stopwords.inc.php");
    
    $language = strtolower ($language);

    // remove hmtl tags
    $text = trim (strip_tags ($text));
    
    // decode html special characters
    $text = html_entity_decode ($text, ENT_COMPAT | ENT_HTML401, $charset);
    
    // replace characters
    $text = str_replace (array("_", ",", "&nbsp;", ";"), array(" "), $text);
    
    // remove newlines
    $text = preg_replace ("/[\n\r]/", " ", $text);
    
    // remove stopwords
    if (is_array ($stopwords[$language])) $text = str_ireplace ($stopwords[$language]." ", "", $text);
    
    // extract the keywords
    $pattern1 = '/\b[A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+ [A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+ [A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+|[A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+ [A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+|[A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+\b/';
    $pattern2 = '/\b[A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+ [A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+ [A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+\b/';
    $pattern3 = '/\b[A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+ [A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+\b/';
    $pattern4 = '/\b[A-ZÄÖÜ]{1}+[A-Za-zäüöéèáàúùß]{2,}+\b/';
    
    preg_match_all ($pattern1, $text, $array1);
    $tags2 = implode (', ', $array1[0]);
    preg_match_all ($pattern2, $tags2, $array2);
    $tags3 = implode (', ', $array1[0]);
    preg_match_all ($pattern3, $tags3, $array3);
    $tags4 = implode (', ', $array1[0]);
    preg_match_all ($pattern4, $tags4, $array4);
    
    $array1[0] = array_map ('ucwords', array_map ('strtolower', $array1[0]));
    $array2[0] = array_map ('ucwords', array_map ('strtolower', $array2[0]));
    $array3[0] = array_map ('ucwords', array_map ('strtolower', $array3[0]));
    $array4[0] = array_map ('ucwords', array_map ('strtolower', $array4[0]));
    
    $ausgabe1 = array_count_values ($array1[0]);
    $ausgabe2 = array_count_values ($array2[0]);
    $ausgabe3 = array_count_values ($array3[0]);
    $ausgabe4 = array_count_values ($array4[0]);
    
    $new_keys = array_merge ($ausgabe1, $ausgabe2, $ausgabe3, $ausgabe4);
    
    // sort array
    array_multisort ($new_keys, SORT_DESC);
    
    $new_keys = array_slice ($new_keys, 0, 39);
    $keywords = array_keys ($new_keys);

    // sperate entries by comma
    $keywords = implode(', ',$keywords);

    return $keywords;
  }
  else return false;
}

// --------------------------------------- getdescription -------------------------------------------
// function: getdescription ()
// input: text as string
// output: cleanded description of provided text /false on error

// description:
// generates a keyword list for meta information. supports german and english stop words lists.

function getdescription ($text, $charset="UTF-8")
{
  if ($text != "")
  {
    // remove hmtl tags
    $text = trim (strip_tags ($text));
    
    // remove multiple white spaces
    $text = preg_replace ('/\s+/', ' ', $text);
    
    // decode html special characters
    $text = html_entity_decode ($text, ENT_COMPAT, $charset);
    
    // remove newlines
    $text = preg_replace ("/[\n\r]/", "", $text); 
    
    // shorten text
    if (strlen ($text) > 1000) $text = substr ($text, 0, 1000);
    
    return $text;
  }
  else return false;
}

// --------------------------------------- getgooglesitemap -------------------------------------------
// function: getgooglesitemap ()
// input: directory path, URL to directory, GET parameters to use for new versions of the URL as array, frequency of google scrawler [never,weekly,daily], priority [1 or less], 
//        ignore file names as array (optional), allowed file types as array (optional), include frequenzy tag [true,false] (optional), include priority tag [true,false] (optional)
// output: xml sitemap / false on error

// description:
// generates a google sitemap xml-output.

// help function
function collecturlnodes ($dir, $url, $getpara, $chfreq, $prio, $ignore_array, $filetypes_array, $show_freq, $show_prio)
{
  if ($dir != "" && is_dir ($dir) && $url != "")
  {
    // add slash if not present at the end of the location string
    if (substr ($dir, -1) != "/") $dir = $dir."/";
    if (substr ($url, -1) != "/") $url = $url."/";

    // ignore these files
    $ignore_default = array('.', '..', 'sitemap.xml', '.folder', '.htaccess');

    if (is_array ($ignore_array) && sizeof ($ignore_array) > 0) $ignore = array_merge ($ignore_array, $ignore_default);
    else $ignore = $ignore_default;

    // the replace array, this works as file => replacement, so 'index.php' => '', would make the index.php be listed as just /
    $replace = array('index.php' => '', 'index.htm' => '', 'index.html' => '', 'index.xhtml' => '', 'index.jsp' => '', 'default.asp' => '', 'default.aspx' => '');
      
    // crawl dir
    $handle = opendir ($dir);
    $result_array = array();

    if ($handle != false)
    {
      while ($file = readdir ($handle))
      {
        // check if this file needs to be ignored, if so, skip it
        if (in_array ($file, $ignore)) continue;
        
        if (is_dir ($dir.$file))
        {
          $resultnew_array = collecturlnodes ($dir.$file.'/', $url.$file.'/', $getpara, $chfreq, $prio, $ignore_array, $filetypes_array, $show_freq, $show_prio);
          if (is_array ($resultnew_array)) $result_array = array_merge ($result_array, $resultnew_array);
        }

        // check whether the file has one of the extensions allowed for this XML sitemap
        $fileinfo = pathinfo ($dir.$file);
        
        if (isset ($fileinfo['extension']) && in_array ($fileinfo['extension'], $filetypes_array))
        {
          // create a W3C valid date for use in the XML sitemap based on the file modification time
          $modified = date ('c', filemtime ($dir.$file));
          
          // replace the file with it's replacement from the settings, if needed
          if (in_array ($file, $replace)) $file = $replace[$file];
          
          // define priority if not given
          if ($prio == "" && $url != "")
          {
            $dirlevel = substr_count ($url, "/");
            if ($dirlevel <= 4) $setprio = 1;
            else $setprio = round ((4 / $dirlevel), 2);
            
            if ($setprio > 1) $setprio = 1;
          }
          else $setprio = 1;

          // creating the url nodes
          $result_string = "  <url>
    <loc>".$url.$file."</loc>
    <lastmod>".$modified."</lastmod>";
          if ($show_freq) $result_string .= "
    <changefreq>".$chfreq."</changefreq>";
          if ($show_prio) $result_string .= "
    <priority>".$setprio."</priority>";
          $result_string .= "
  </url>";
  
          $result_array[] = $result_string;
          
          // if GET parameters should be added to create new versions of the URL
          if (sizeof ($getpara) > 0)
          {
            foreach ($getpara as $add)
            {
              if ($add != "") 
              {
                $result_string = "  <url>
      <loc>".$url.$file."?".$add."</loc>
      <lastmod>".$modified."</lastmod>";
                if ($show_freq) $result_string .= "
      <changefreq>".$chfreq."</changefreq>";
                if ($show_prio) $result_string .= "
      <priority>".$setprio."</priority>";
                $result_string .= "
    </url>";
    
                $result_array[] = $result_string;
              }
            }
          }
        }
      }
      
      closedir ($handle);
    }
    
    if (sizeof ($result_array) > 0) return $result_array;
    else return false;
  }
  else return false;
}

function getgooglesitemap ($dir, $url, $getpara=array(), $chfreq="weekly", $prio="", $ignore=array(), $filetypes=array('cfm', 'htm', 'html', 'xhtml', 'asp', 'aspx', 'jsp', 'php', 'pdf'), $show_freq=true, $show_prio=true)
{
  if ($dir != "" && is_dir ($dir) && $url != "")
  {
    // cget url nodes
    $result_array = collecturlnodes ($dir, $url, $getpara, $chfreq, $prio, $ignore, $filetypes, $show_freq, $show_prio);

    if (sizeof ($result_array) > 0)
    {
      $result = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<urlset
  xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"
  xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
  xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9
      http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n";
      
      $result .= implode ("\n", $result_array)."\n";
      
      $result .= "</urlset>";
      
      return $result;
    }
    else return false;
  }
  else return false;
}

// ---------------------- getmetadata -----------------------------
// function: getmetadata()
// input: location, object (both optional if container is given), container name or container content (optional), 
//        seperator of meta data fields [any string,array] (optional), publication name/template name to extract label names (optional)
// output: string with all meta data from given object based on container

function getmetadata ($location, $object, $container="", $seperator="\n", $template="")
{
	global $mgmt_config;
  
	if ((valid_locationname ($location) && valid_objectname ($object)) || $container != "")
  {
  	// check if object is folder or page/component
    if ($container == "")
    {
      // if object is folder
    	if (@is_dir ($location.$object))
      {
    		$location = $location.$object."/";
    		$object = ".folder";
    	}
      
      if (@is_file ($location.$object))
      {
    		// read file
    		$objectdata = loadfile ($location, $object);
        
    		// get name of container
    		if ($objectdata != "")
        {
          $container = getfilename ($objectdata, "content");
          $template = getfilename ($objectdata, "template");
        }
        else $container = false;
      }
      else $container = false;
    }
    
		// read meta data of media file
		if ($container != false)
    {
      // container need to be loaded
      if (valid_objectname ($container) && strpos ($container, "<container>") < 1)
      {
  			$container_id = substr ($container, 0, strpos ($container, ".xml")); 
  			$result = getcontainername ($container);
  			$container = $result['container'];
  			$contentdata = loadcontainer ($container, "version", "sys");
      }
      // container content is available
      else $contentdata = $container;
      
      $labels = Null;

      // load template and define labels
      if ($template != "" && strpos ($template, "/") > 0)
      {
        list ($site, $template) = explode ("/", $template);
        
        if ($site != "" && $template != "")
        {
          $result = loadtemplate ($site, $template);
          
          if ($result['content'] != "")
          {
            $hypertag_array = gethypertag ($result['content'], "text", 0);
            
            if (is_array ($hypertag_array))
            {
              $labels = array();
              
              foreach ($hypertag_array as $tag)
              {
                $id = getattribute ($tag, "id");
                $label = getattribute ($tag, "label");
                
                if ($id != "" && $label != "") $labels[$id] = $label;
                elseif ($id != "") $labels[$id] = str_replace ("_", " ", $id);
              }
            }
          }
        }
      }
      
			if ($contentdata != false)
      {
        $metadata = Null;
      
        // if no template and no lables are given
        if (!is_array ($labels))
        {
  				$textnode = getcontent ($contentdata, "<text>");
          
  				if ($textnode != false)
          {
  					if (strtolower ($seperator) != "array") $metadata = "";
            else $metadata = array();
            
  					foreach ($textnode as $buffer)
            {
              // get info from container
  						$text_id = getcontent ($buffer, "<text_id>");
              
              // dont include comments or articles (they use :)
              if (strpos ($text_id[0], ":") == 0)
              {
                $label = str_replace ("_", " ", $text_id[0]);
              
    						$text_content = getcontent ($buffer, "<textcontent>");
    						$text_content[0] = str_replace ("\"", "'", strip_tags ($text_content[0]));
                
    						if (strtolower ($seperator) != "array") $metadata .= $label.": ".$text_content[0].$seperator;
                else $metadata[$label] = $text_content[0];
              }
  					}
  				}
        }
        // if labels were defined
        elseif (is_array ($labels) && sizeof ($labels) > 0)
        {
          foreach ($labels as $id => $label)
          {
            $text_str = "";
            
            // dont include comments or articles (they use :)
            if (strpos ($id, ":") == 0)
            {
              $textnode = selectcontent ($contentdata, "<text>", "<text_id>", $id);
              
              if ($textnode != false)
              {
                $text_content = getcontent ($textnode[0], "<textcontent>");
                $text_content[0] = str_replace ("\"", "'", strip_tags ($text_content[0]));
                
    						$text_str = $text_content[0];
              }
            }
        
						if (strtolower ($seperator) != "array") $metadata .= $label.": ".$text_str.$seperator;
            else $metadata[$label] = $text_str;
          }
        }
        
				return $metadata;
			}
      else return false;
		}
    else return false;
	}
  else return false;
}

// ---------------------- copymetadata -----------------------------
// function: copymetadata()
// input: path to source file, path to destination file
// output: true / false

// description: copies all meta data from source to destination file using EXIFTOOL

function copymetadata ($file_source, $file_dest)
{
	global $user, $mgmt_config, $mgmt_mediametadata;
  
	if (is_file ($file_source) && is_file ($file_dest) && is_array ($mgmt_mediametadata))
  {
    // get source file extension
    $file_source_ext = strtolower (strrchr ($file_source, "."));
    
    // copy metadata from original file using EXIFTOOL
    foreach ($mgmt_mediametadata as $extensions => $executable)
    {
      if ($executable != "" && substr_count ($extensions.".", $file_source_ext.".") > 0)
      {
        // get location and media file name
        $location_source = getlocation ($file_source);
        $media_source = getobject ($file_source);
        
        // create temp file if file is encrypted
        $temp_source = createtempfile ($location_source, $media_source);
        
        if ($temp_source['crypted']) $file_source = $temp_source['templocation'].$temp_source['tempfile'];
        
        // get location and media file name
        $location_dest = getlocation ($file_dest);
        $media_dest = getobject ($file_dest);
        
        // create temp file if file is encrypted
        $temp_dest = createtempfile ($location_dest, $media_dest);
        
        if ($temp_dest['crypted']) $file_dest = $temp_dest['templocation'].$temp_dest['tempfile'];
        
        // copy meta data
        $cmd = $executable." -overwrite_original -TagsFromFile \"".shellcmd_encode ($file_source)."\" \"-all:all>all:all\" \"".shellcmd_encode ($file_dest)."\"";    
        @exec ($cmd, $buffer, $errorCode);
        
        // delete temp files
        if ($temp_source['crypted']) deletefile ($temp_source['templocation'], $temp_source['tempfile'], 0);
        if ($temp_dest['crypted']) deletefile ($temp_dest['templocation'], $temp_dest['tempfile'], 0);
        
        // on error
        if ($errorCode)
        {
          $errcode = "20241";
          $error[] = $mgmt_config['today']."|explorer_download.php|error|$errcode|exec of EXIFTOOL (code:$errorCode) failed in copy metadata to file: ".getobject($file_dest);
          
          // save log
          savelog (@$error);
        }
        else
        {
          return true;
        }
      }
    }
	}
  else return false;
}

// ------------------------- extractmetadata -----------------------------
// function: extractmetadata()
// input: path to image file
// output: result array / false on error

// description: extracts all meta data from a file using EXIFTOOL

function extractmetadata ($file)
{
  global $user, $mgmt_config, $mgmt_mediametadata;
  
	if (is_file ($file) && is_array ($mgmt_mediametadata))
  {
    $result = array();
    $hide_properties = array ("version number", "file name", "directory", "file permissions", "app14 flags", "thumbnail", "xmp toolkit");
    
    // get file info
    $file_info = getfileinfo ("", $file, "comp");

    // define executable
    foreach ($mgmt_mediametadata as $extensions => $executable)
    {
      if (substr_count ($extensions.".", $file_info['ext'].".") > 0)
      {
        // get location and media file name
        $location = getlocation ($file);
        $media = getobject ($file);
    
        // create temp file if file is encrypted
        $temp = createtempfile ($location, $media);
        
        if ($temp['result'] && $temp['crypted']) $file = $temp['templocation'].$temp['tempfile'];
        
        // get image information using EXIFTOOL
        $cmd = $executable." -G \"".shellcmd_encode ($file)."\"";          
        @exec ($cmd, $output, $errorCode);
        
        // delete temp file
        if ($temp['result'] && $temp['created']) deletefile ($temp['templocation'], $temp['tempfile'], 0);
            
        // on error
        if ($errorCode)
        {
          $errcode = "20247";
          $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|$errcode|exec of EXIFTOOL (code:$errorCode) failed for file: ".getobject($file);
        }
        elseif (is_array ($output))
        {
          foreach ($output as $line)
          {
            if (strpos ($line, " : ") > 0)
            {
              list ($property, $value) = explode (" : ", $line);

              if (trim ($property) != "" && trim ($value) != "")
              {
                $property = trim ($property);
                
                // extract group and property name
                if (strpos ($property, "[") == 0 && strpos ($property, "] ") > 0)
                {
                  list ($group, $property) = explode ("] ", substr ($property, 1));
                  $group = trim ($group);
                  $property = trim ($property);
                }
                else $group = "File";
                
                $hide = false;
                
                foreach ($hide_properties as $hide_property)
                {
                  if (substr_count (strtolower($property), strtolower($hide_property)) > 0)
                  {
                    $hide = true;
                    break;
                  }
                }
                
                if ($hide == false && $group != "" && $property != "") $result[$group][$property] = trim ($value);
              }
            }
          }
          
          if (sizeof ($result) > 0) return $result;
          else return false;
        }
        else return false;
      }
    }
    
    return false;
  }
  else return false;
}

// ------------------------- xmlobject2array -----------------------------
// function: xmlobject2array()
// input: XML as object, namespace as array (optional)
// output: result array / false

// description:
// function to convert an xmlobject to an array, provided by xaviered at gmail dot com

function xmlobject2array ($obj, $namespace="")
{
  if (is_object ($obj))
  {
    // get namespace
    if (!is_array ($namespace))
    {
      $namespace = $obj->getDocNamespaces (true);
      $namespace[NULL] = NULL;
    }
    
    $children = array();
    $attributes = array();
    $result = array();
    
    // XML tag name and text-content
    $name = (string)$obj->getName(); 
    $text = trim ((string)$obj);
    if (strlen ($text) <= 0) $text = NULL;
 
    // get info for all namespaces
    foreach ($namespace as $ns => $nsUrl)
    {
      /*
      // atributes
      $objAttributes = $obj->attributes ($ns, true);
      
      foreach ($objAttributes as $attributeName => $attributeValue)
      {
        $attribName = trim ((string)$attributeName);
        $attribVal = trim ((string)$attributeValue);
        if (!empty ($ns)) $attribName = $ns.':'.$attribName;
        $attributes[$attribName] = $attribVal;
      }
      */
      
      if (!empty ($ns)) $fullname = $ns.':'.$name;  
     
      // children
      $objChildren = $obj->children ($ns, true);
      
      $result_sub = array();
      
      if (sizeof ($objChildren) > 0)
      {
        foreach ($objChildren as $childname => $child)
        {
          $childname = (string)$childname;
          if (!empty ($ns)) $fullchildname = $ns.':'.$childname;
          $childnamespace[$ns] = $nsUrl;
          
          $children = xmlobject2array ($child);
          if (is_array ($children)) $result_sub[$childname] = $children;
        }
      }
      elseif ($text != "")
      {
        $result_sub[$name] = $text;
      }
      
      if (sizeof ($result_sub) > 0) $result = array_merge ($result, $result_sub);
    }
    
    if (sizeof ($result) > 0) return $result;
    else return false;
  }
  else return false;
} 

// ------------------------- id3_getdata -----------------------------
// function: id3_getdata()
// input: path to audio file
// output: result array / false on error

// description: requires getID3 library since EXIFTOOL cannot write ID3 tags so far

function id3_getdata ($file)
{
  global $mgmt_config, $hcms_ext;

	if (is_file ($file) && is_file ($mgmt_config['abs_path_cms']."library/getID3/getid3/getid3.php"))
  {
    if (!is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
    // load getID3 library
    require_once ($mgmt_config['abs_path_cms']."library/getID3/getid3/getid3.php");
    
    // get file info
    $file_info = getfileinfo ("", $file, "comp");
    
    // check file extension
    if (substr_count (strtolower ($hcms_ext['audio']).".", $file_info['ext'].".") == 0) return false;
    
    set_time_limit (30);
    $file = realpath ($file);
    
    // initialize getID3 engine
    $getID3 = new getID3;
    // analyze file
		$file_info = $getID3->analyze ($file);
    // combine all/any available tag formats in 'comments'
		getid3_lib::CopyTagsToComments ($file_info);

    // Example outputs can be:
    // $file_info['comments_html']['artist'][0] ... artist from any/all available tag formats
    // $file_info['tags']['id3v2']['title'][0]  ... title from ID3v2
    // $file_info['audio']['bitrate']           ... audio bitrate
    // $file_info['playtime_string']            ... playtime in minutes:seconds, formatted string
    // $file_info['comments']['picture'][0]['data'] ... album art
    
    $result = array();
    
    if (is_array ($file_info))
    {
      // extract album art image
      if (isset ($file_info['comments']['picture'][0]) && !empty ($file_info['comments']['picture'][0]['data']))
      {
        // binary data
        $result['imagedata'] = $file_info['comments']['picture'][0]['data'];
        // mime-type
        if (!empty ($file_info['comments']['picture'][0]['image_mime']))
          $result['imagemimetype'] = trim (strtolower ($file_info['comments']['picture'][0]['image_mime']));
        else $result['imagemimetype'] = "image/jpeg";
        // width and height
        if (!empty ($file_info['comments']['picture'][0]['image_width'])) $result['imagewidth'] = $file_info['comments']['picture'][0]['image_width'];
        else $result['imagewidth'] = "";
        if (!empty ($file_info['comments']['picture'][0]['image_height'])) $result['imageheight'] = $file_info['comments']['picture'][0]['image_height'];
        else $result['imageheight'] = "";
        // image type (e.g. cover)
        if (!empty ($file_info['comments']['picture'][0]['picturetype'])) $result['imagetype'] = $file_info['comments']['picture'][0]['picturetype'];
        else $result['imagetype'] = "";
        // image description
        if (!empty ($file_info['comments']['picture'][0]['description'])) $result['description'] = $file_info['comments']['picture'][0]['description'];
        else $result['description'] = "";
        // image data length
        if (!empty ($file_info['comments']['picture'][0]['datalength'])) $result['datalength'] = $file_info['comments']['picture'][0]['datalength'];
        else $result['datalength'] = "";
      }
      
      // encoding
      if (!empty ($file_info['encoding'])) $result['encoding'] = $file_info['encoding'];
      else $result['encoding'] = "UTF-8";
        
      // extract text based data from 'comments'
      if (!empty ($file_info['comments']) && is_array ($file_info['comments']))
      {
        // title
        if (!empty ($file_info['comments']['title'][0])) $result['title'] = $file_info['comments']['title'][0];
        else $result['title'] = "";
        // artist
        if (!empty ($file_info['comments']['artist'][0])) $result['artist'] = $file_info['comments']['artist'][0];
        else $result['artist'] = "";
        // album
        if (!empty ($file_info['comments']['album'][0])) $result['album'] = $file_info['comments']['album'][0];
        else $result['album'] = "";
        // year
        if (!empty ($file_info['comments']['year'][0])) $result['year'] = $file_info['comments']['year'][0];
        else $result['year'] = "";
        // comment
        if (!empty ($file_info['comments']['comment'][0])) $result['comment'] = $file_info['comments']['comment'][0];
        else $result['comment'] = "";
        // track
        if (!empty ($file_info['comments']['track'][0])) $result['track'] = $file_info['comments']['track'][0];
        else $result['track'] = "";
        // genre (multiple entries possible)
        if (!empty ($file_info['comments']['genre'][0])) $result['genre'] = implode (", ", $file_info['comments']['genre']);
        else $result['genre'] = "";
        // track number
        if (!empty ($file_info['comments']['track_number'][0])) $result['tracknumber'] = $file_info['comments']['track_number'][0];
        else $result['tracknumber'] = "";
        // band
        if (!empty ($file_info['comments']['band'][0])) $result['band'] = $file_info['comments']['band'][0];
        else $result['band'] = "";
      }
      
      // return result
      if (sizeof ($result) > 0) return $result;
      else return false;
    }
    else return false;
  }
  else return false;
} 

// ------------------------- id3_writefile -----------------------------
// function: id3_writefile()
// input: abs. path to audio file, ID3 tag array, keep existing ID3 data of file [true,false] (optional)
// output: true / false on error

// description:
// writes ID3 tags into audio file for supported file types and keeps the existing ID3 tags

function id3_writefile ($file, $id3, $keep_data=true)
{
  global $user, $mgmt_config, $mgmt_mediametadata, $hcms_ext;

  if (is_file ($file) && is_array ($id3) && is_array ($hcms_ext) && is_file ($mgmt_config['abs_path_cms']."library/getID3/getid3/getid3.php"))
  {
    if (!is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
    
    $result = false;
    
    // get file info
    $file_info = getfileinfo ("", $file, "comp");
    
    // check file extension
    if (substr_count (strtolower ($hcms_ext['audio']).".", $file_info['ext'].".") == 0) return false;
    
    // get location and media file name
    $location = getlocation ($file);
    $media = getobject ($file);
    
    // create temp file if file is encrypted
    $temp = createtempfile ($location, $media);
    
    if ($temp['result'] && $temp['crypted']) $file = $temp['templocation'].$temp['tempfile'];
    
    if (is_file ($file))
    {
      $encoding = "UTF-8";

      // load getID3 library
      require_once ($mgmt_config['abs_path_cms']."library/getID3/getid3/getid3.php");
      require_once ($mgmt_config['abs_path_cms']."library/getID3/getid3/write.php");
    
      set_time_limit (30);
      $file = realpath ($file);
      
      // initialize getID3 engine
      $getID3 = new getID3;
      $getID3->setOption(array('encoding'=>$encoding));

      // initialize getID3 tag-writing module
      $tagwriter = new getid3_writetags;

      $tagwriter->filename = $file;

      // set tagformat version 'id3v1', 'id3v2.3';
      $tagwriter->tagformats = array('id3v2.3');

      // set options
      // if true will erase existing tag data and write only passed data; if false will merge passed data with existing tag data (experimental)
      $tagwriter->overwrite_tags = true;
      // if true removes other tag formats (e.g. ID3v1, ID3v2, APE, Lyrics3, etc) that may be present in the file and only write the specified tag format(s).
      // if false leaves any unspecified tag formats as-is.
      if ($keep_data == true || $keep_data == 1) $tagwriter->remove_other_tags = false;
      else $tagwriter->remove_other_tags = true;
      
      $tagwriter->tag_encoding = $encoding;
  
      $tagdata = array();
      
      // populate ID3 data array
      foreach ($id3 as $tag => $value)
      {
        if (strpos ($tag, ":") > 0) list ($namespace, $tag) = explode (":", $tag);
        
        if ($tag != "" && $namespace == "id3")
        {
          // correct tag for track number
          if ($tag == "tracknumber") $tag = "track_number";

          $tagdata[$tag] = array(html_decode ($value, $encoding));
        }
      }
      
      if (sizeof ($tagdata) > 0)
      {
        // set tags
        $tagwriter->tag_data = $tagdata;
        
        // write tags into file
        if ($tagwriter->WriteTags())
        {
          $result = true;
                
          // on warning
        	if (!empty ($tagwriter->warnings))
          {
            $errcode = "20280";
            $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|warning|$errcode|There were warnings when writing ID3 tags to file: ".getobject($file)."<br />".implode("<br />", $tagwriter->warnings);
        	}
        }
        // on error
        else
        {
          $errcode = "20281";
          $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|$errcode|Failed to write ID3 tags to file: ".getobject($file);
        }
      }
      
      // encrypt and save file if required
      if ($temp['result'] && $temp['created']) movetempfile ($location, $media, true);
      
      // save log
      savelog (@$error);
    }

    return $result;
  }
  else return false;
}

// ------------------------- id3_create -----------------------------
// function: id3_create()
// input: publication name, text array (from content container)
// output: ID3 tag array / false on error

// description:
// defines ID3 tag array based on the media mapping of a publication.

function id3_create ($site, $text)
{
  global $mgmt_config;
  
  if (valid_publicationname ($site) && is_array ($text) && !empty ($mgmt_config['abs_path_data']))
  {
    // try to load mapping configuration file of publication
    if (is_file ($mgmt_config['abs_path_data']."config/".$site.".media.map.php"))
    {
      @include ($mgmt_config['abs_path_data']."config/".$site.".media.map.php");
    }
    
    $result = array();
    
    // look in mapping definition (name => id)
    if (isset ($mapping) && is_array ($mapping))
    {
      foreach ($mapping as $tag => $id)
      {
        // set ID3 tag (tag => value)
        if ($tag != "" && $id != "" && isset ($text[$id]) && substr ($tag, 0, 4) == "id3:")
        {
          $result[$tag] = $text[$id];
        }
      }
      
      return $result;
    }
    else
    {
      $errcode = "10109";
      $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|$errcode|media mapping of publication '".$site."' could not be loaded";
      savelog ($error);
          
      return false;
    }
  }
  else return false;
}

// ------------------------- xmp_getdata -----------------------------
// function: xmp_getdata()
// input: path to image file
// output: result array / false on error

function xmp_getdata ($file)
{
  global $user, $mgmt_config, $hcms_ext;

	if (is_file ($file))
  {
    if (!is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
    
    // get file info
    $file_info = getfileinfo ("", $file, "comp");
    
    // check file extension
    if (substr_count (strtolower ($hcms_ext['image']).".", $file_info['ext'].".") == 0) return false;
  
    // get location and media file name
    $location = getlocation ($file);
    $media = getobject ($file);
    
    // create temp file if file is encrypted
    $temp = createtempfile ($location, $media);
    
    if ($temp['result'] && $temp['crypted']) $file = $temp['templocation'].$temp['tempfile'];
    
    // load file
    $content = file_get_contents ($file);
    
    // delete temp file
    if ($temp['result'] && $temp['created']) deletefile ($temp['templocation'], $temp['tempfile'], 0);
    
    $result = array();
    
    if ($content != "" && strpos ($content, "</x:xmpmeta>") > 0)
    {    
      $xmp_data_start = strpos ($content, '<x:xmpmeta');
      $xmp_data_end = strpos ($content, '</x:xmpmeta>');
      $xmp_length = $xmp_data_end - $xmp_data_start;
      $xmp_data = substr ($content, $xmp_data_start, $xmp_length + 12);
      
      if ($xmp_data != "")
      {
        $xmp = simplexml_load_string ($xmp_data);
        $result = xmlobject2array ($xmp);

        if (sizeof ($result) > 0) return $result;
        else return false;
      }
      else return false;
      
      /*
      // XMP -> Dublin Core namespace tags
      $mapping['dc:title'] = "Title";
      $mapping['dc:subject'] = "Subject";
      $mapping['dc:description'] = "Description";
      $mapping['dc:creator'] = "Creator";
      $mapping['dc:rights'] = "Copyright";
      $mapping['dc:contributor'] = "Contributor";
      $mapping['dc:coverage'] = "Coverage";
      $mapping['dc:date'] = "Date";
      $mapping['dc:format'] = "Format";
      $mapping['dc:identifier'] = "Identifier";
      $mapping['dc:language'] = "Language";
      $mapping['dc:publisher'] = "Publisher";
      $mapping['dc:relation'] = "Relation";
      $mapping['dc:source'] = "Source";
      $mapping['dc:type'] = "Type";

      // XMP -> Adobe PhotoShop namespace tags
      $mapping['photoshop:Source'] = "Source";
      $mapping['photoshop:Credit'] = "Credit";
      $mapping['photoshop:DateCreated'] = "Creation date";
      $mapping['photoshop:AuthorsPosition'] = "Authors position";
      $mapping['photoshop:CaptionWriter'] = "Caption writer";
      $mapping['photoshop:Category'] = "Category";
      $mapping['photoshop:SupplementalCategories'] = "Supplemental categories";
      $mapping['photoshop:City'] = "City";
      $mapping['photoshop:State'] = "State";
      $mapping['photoshop:Country'] = "Country";
      $mapping['photoshop:DocumentAncestors'] = "Document ancestors";
      $mapping['photoshop:DocumentAncestorID'] = "Document ancestor ID";
      $mapping['photoshop:Headline'] = "Headline";
      $mapping['photoshop:Instructions'] = "Instructions";
      $mapping['photoshop:History'] = "History";
      $mapping['photoshop:Urgency'] = "Urgency";
      // ColorMode:
      // 0 = Bitmap
      // 1 = Grayscale
      // 2 = Indexed
      // 3 = RGB
      // 4 = CMYK
      // 7 = Multichannel
      // 8 = Duotone
      // 9 = Lab	
      $mapping['photoshop:ColorMode'] = "Color mode";
      $mapping['photoshop:ICCProfileName'] = "ICC Profile name";
      $mapping['photoshop:LegacyIPTCDigest'] = "Legacy IPTC digest";
      $mapping['photoshop:SidecarForExtension'] = "Sidecar for extension";
      $mapping['photoshop:TextLayers'] = "Text layers";
      $mapping['photoshop:TextLayerName'] = "Text layer name";
      $mapping['photoshop:TextLayerText'] = "Text layer text";
      $mapping['photoshop:TransmissionReference'] = "Transmission reference";
      
      // get XMP meta data
      $xmp = getcontent ($content, "<x:xmpmeta *>");
    
      if (is_array ($xmp))
      {
        reset ($mapping);
        
        foreach ($mapping as $tag => $name)
        {
          // only for XMP (XML-based) tags (DC, PhotoShop ...)
          if ($tag != "")
          {          
            $xmpstr = "";
            
            // namespace
            if (strpos ($tag, ":") > 0) $namespace = strtoupper (substr ($tag, 0, strpos ($tag, ":")));
            else $namespace = Null;
            
            // get content
            $xmp_node = getcontent ($xmp[0], "<".$tag." *>");
            if ($xmp_node != false) $xmp_li = getcontent ($xmp_node[0], "<rdf:li *>");
            if ($xmp_li != false) $xmpstr = implode (", ", $xmp_li);
        
            if (trim ($xmpstr) != "")
            {
              $result[$namespace][$name] = trim ($xmpstr);
            }
          }
        }
        
        if (sizeof ($result) > 0) return $result;
        else return false;
      }
      else return false;
      */
    }
    else return false;
	}
  else return false;
}

// ------------------------- xmp_writefile -----------------------------
// function: xmp_writefile()
// input: abs. path to image file, XMP tag array, keep existing XMP data of file [true,false] (optional)
// output: true / false on error

// description:
// writes XMP tags into image file for supported file types and keeps the existing XMP tags

function xmp_writefile ($file, $xmp, $keep_data=true)
{
  global $user, $mgmt_config, $mgmt_mediametadata, $hcms_ext;

  if (is_file ($file) && is_array ($xmp) && is_array ($mgmt_mediametadata))
  {
    if (!is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
    
    // get file info
    $file_info = getfileinfo ("", $file, "comp");
    
    // check file extension
    if (substr_count ($hcms_ext['image'].".", $file_info['ext'].".") == 0) return false;
    
    // get location and media file name
    $location = getlocation ($file);
    $media = getobject ($file);
    
    // create temp file if file is encrypted
    $temp = createtempfile ($location, $media);
    
    if ($temp['result'] && $temp['crypted']) $file = $temp['templocation'].$temp['tempfile'];
    
    if (is_file ($file))
    {
      // define executable
      foreach ($mgmt_mediametadata as $extensions => $executable)
      {
        if (substr_count ($extensions.".", $file_info['ext'].".") > 0)
        {
          // remove all XMP tags from file
          if ($keep_data == false || $keep_data == 0)
          {
            $cmd = $executable." -overwrite_original -r -XMP-crss:all= \"".shellcmd_encode ($file)."\"";          
            @exec ($cmd, $buffer, $errorCode);
            
            // on error
            if ($errorCode)
            {
              $errcode = "20242";
              $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|$errcode|exec of EXIFTOOL (code:$errorCode) failed for file: ".getobject($file);
            }
          }
  
          // inject XMP tags into file
          foreach ($xmp as $tag => $value)
          {
            if (strpos ($tag, ":") > 0) list ($namespace, $tag) = explode (":", $tag);
            
            if ($tag != "" && ($namespace == "dc" || $namespace == "photoshop"))
            {
              $cmd = $executable." -overwrite_original -xmp:".$tag."=\"".shellcmd_encode (html_decode ($value, "UTF-8"))."\" \"".shellcmd_encode ($file)."\"";
              @exec ($cmd, $buffer, $errorCode);
    
              // on error
              if ($errorCode)
              {
                $errcode = "20243";
                $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|$errcode|exec of EXIFTOOL (code:$errorCode) failed for file: ".getobject($file);
              }
            }
          }
          
          // encrypt and save file if required
          if ($temp['result'] && $temp['created']) movetempfile ($location, $media, true);
          
          // save log
          savelog (@$error);
          
          return true;
        }
      }
    }

    return false;
  }
  else return false;
}

// ------------------------- xmp_create -----------------------------
// function: xmp_create()
// input: publication name, text array (from content container)
// output: XMP tag array / false on error

// description:
// defines XMP tag array based on the media mapping of a publication.

function xmp_create ($site, $text)
{
  global $mgmt_config;
  
  if (valid_publicationname ($site) && is_array ($text) && !empty ($mgmt_config['abs_path_data']))
  {
    // try to load mapping configuration file of publication
    if (is_file ($mgmt_config['abs_path_data']."config/".$site.".media.map.php"))
    {
      @include ($mgmt_config['abs_path_data']."config/".$site.".media.map.php");
    }
    
    $result = array();
    
    // look in mapping definition (name => id)
    if (isset ($mapping) && is_array ($mapping))
    {
      foreach ($mapping as $tag => $id)
      {
        // set XMP tag (tag => value)
        if ($tag != "" && $id != "" && isset ($text[$id]) && (substr ($tag, 0, 3) == "dc:" || substr ($tag, 0, 10) == "photoshop:"))
        {
          $result[$tag] = $text[$id];
        }
      }
      
      return $result;
    }
    else
    {
      $errcode = "10101";
      $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|$errcode|media mapping of publication '".$site."' could not be loaded";
      savelog ($error);
          
      return false;
    }
  }
  else return false;
}

// ------------------------- geo2decimal -----------------------------
// function: geo2decimal()
// input: geo location in degree, minutes, seconds, hemisphere [N,O,S,W]
// output: decimal result / false

function geo2decimal ($deg, $min, $sec, $hemi) 
{
  $d = $deg + ((($min/60) + ($sec/3600))/100);
  return ($hemi == 'S' || $hemi == 'W') ? $d*=-1 : $d;
}

// ------------------------- exif_getdata -----------------------------
// function: exif_getdata()
// input: path to image file
// output: result array / false

function exif_getdata ($file)
{
  global $user, $mgmt_config, $hcms_ext;

	if (is_file ($file))
  {
    if (!is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
    
    $result = array();
    
    // get file info
    $file_info = getfileinfo ("", $file, "comp");
    
    // check file extension
    if (substr_count (strtolower ($hcms_ext['image']).".", $file_info['ext'].".") == 0) return false;
    
    // get location and media file name
    $location = getlocation ($file);
    $media = getobject ($file);
    
    // create temp file if file is encrypted
    $temp = createtempfile ($location, $media);
    
    if ($temp['result'] && $temp['crypted']) $file = $temp['templocation'].$temp['tempfile'];
		
    // set encoding for EXIF to UTF-8
    ini_set ('exif.encode_unicode', 'UTF-8');  
    error_reporting (0);
    
    // read exif data
		$exif = exif_read_data ($file, 0, true);
    
    // delete temp file
    if ($temp['result'] && $temp['created']) deletefile ($temp['templocation'], $temp['tempfile'], 0);
		
    // date and time
		if (isset ($exif["EXIF"]["DateTimeOriginal"]))
    {
			$dateoriginal = str_replace (":", "-", substr ($exif["EXIF"]["DateTimeOriginal"], 0, 10));
			$timeoriginal = substr ($exif["EXIF"]["DateTimeOriginal"], 10);
			if (trim ($dateoriginal." ".$timeoriginal) != "") $result['Camera']['Date and time'] = $dateoriginal." ".$timeoriginal;
		}
    
    // geo location
    if (is_array ($exif['GPSLongitude']) && is_array ($exif['GPSLatitude']))
    {
      $egeoLong = $exif['GPSLongitude'];
      $egeoLat = $exif['GPSLatitude'];
      $egeoLongR = $exif['GPSLongitudeRef'];
      $egeoLatR = $exif['GPSLatitudeRef'];
      
      $result['GPS']['longitude'] = geo2decimal ($egeoLong[0], $egeoLong[1], $egeoLong[2], $egeoLongR);
      $result['GPS']['latitude'] = geo2decimal ($egeoLat[0], $egeoLat[1], $egeoLat[2], $egeoLatR);
    }

    // shutter
		if (isset ($exif["EXIF"]["FNumber"]))
    {
			list ($num, $den) = explode ("/", $exif["EXIF"]["FNumber"]);
			$aperture  = "F/".($num/$den);
			if (trim ($aperture) != "") $result['Camera']['Shutter dissolve'] = trim ($aperture);
		}
		
    // exposure time
		if (isset ($exif["EXIF"]["ExposureTime"]))
    {
			list ($num, $den) = explode ("/", $exif["EXIF"]["ExposureTime"]);
      
			if ($num > $den)
      {
				$exposure = $num." s";
			}
      else
      {
				$den = round ($den/$num);
				$exposure = "1/".$den." s";
			}
      
      if (trim ($exposure) != "") $result['Camera']['Exposure time'] = trim ($exposure);
		}
		
    // focal length
		if (isset ($exif["EXIF"]["FocalLength"]))
    {
			list ($num, $den) = explode ("/", $exif["EXIF"]["FocalLength"]);
			$focallength  = ($num/$den)." mm";
			if (trim ($focallength) != "") $result['Camera']['Focal length'] = trim ($focallength);
		}
		
		if (isset ($exif["EXIF"]["FocalLengthIn35mmFilm"]))
    {
			$focallength35 = $exif["EXIF"]["FocalLengthIn35mmFilm"];
			if (trim ($focallength35) != "") $result['Camera']['Focal length in 35mm film'] = trim ($focallength35)." mm";
		}
		
    // ISO
		if (isset ($exif["EXIF"]["ISOSpeedRatings"]))
    {
      $iso = $exif["EXIF"]["ISOSpeedRatings"];
			if (trim ($iso) != "") $result['Camera']['ISO'] = trim ($iso);
		}

    // 
		if (isset ($exif["EXIF"]["WhiteBalance"]))
    {
			switch ($exif["EXIF"]["WhiteBalance"])
      {
				case 0:
					$whitebalance = "Auto";
					break;
				case 1:
					$whitebalance = "Daylight";
					break;
				case 2:
					$whitebalance = "Fluorescent";
					break;
				case 3:
					$whitebalance = "Incandescent";
					break;
				case 4:
					$whitebalance = "Flash";
					break;
				case 9:
					$whitebalance = "Fine Weather";
					break;
				case 10:
					$whitebalance = "Cloudy";
					break;
				case 11:
					$whitebalance = "Shade";
					break;
				default:
					$whitebalance = "";
					break;
			}
      
			if (trim ($whitebalance) != "") $result['Camera']['White balance'] = $whitebalance;
		}
		
		if (isset ($exif["EXIF"]["Flash"]))
    {
			switch ($exif["EXIF"]["Flash"])
      {
				case 0:
					$flash = 'Flash did not fire';
					break;
				case 1:
					$flash = 'Flash fired';
					break;
				case 5:
					$flash = 'Strobe return light not detected';
					break;
				case 7:
					$flash = 'Strobe return light detected';
					break;
				case 9:
					$flash = 'Flash fired, compulsory flash mode';
					break;
				case 13:
					$flash = 'Flash fired, compulsory flash mode, return light not detected';
					break;
				case 15:
					$flash = 'Flash fired, compulsory flash mode, return light detected';
					break;
				case 16:
					$flash = 'Flash did not fire, compulsory flash mode';
					break;
				case 24:
					$flash = 'Flash did not fire, auto mode';
					break;
				case 25:
					$flash = 'Flash fired, auto mode';
					break;
				case 29:
					$flash = 'Flash fired, auto mode, return light not detected';
					break;
				case 31:
					$flash = 'Flash fired, auto mode, return light detected';
					break;
				case 32:
					$flash = 'No flash function';
					break;
				case 65:
					$flash = 'Flash fired, red-eye reduction mode';
					break;
				case 69:
					$flash = 'Flash fired, red-eye reduction mode, return light not detected';
					break;
				case 71:
					$flash = 'Flash fired, red-eye reduction mode, return light detected';
					break;
				case 73:
					$flash = 'Flash fired, compulsory flash mode, red-eye reduction mode';
					break;
				case 77:
					$flash = 'Flash fired, compulsory flash mode, red-eye reduction mode, return light not detected';
					break;
				case 79:
					$flash = 'Flash fired, compulsory flash mode, red-eye reduction mode, return light detected';
					break;
				case 89:
					$flash = 'Flash fired, auto mode, red-eye reduction mode';
					break;
				case 93:
					$flash = 'Flash fired, auto mode, return light not detected, red-eye reduction mode';
					break;
				case 95:
					$flash = 'Flash fired, auto mode, return light detected, red-eye reduction mode';
					break;
				default:
					$flash = '';
					break;
			}
        
			if (trim ($flash) != "") $result['Camera']['Flash'] = trim ($flash);
		}
		
		if (isset ($exif["IFD0"]["Make"]) && isset ($exif["IFD0"]["Model"]))
    {
			$make = ucwords (strtolower ($exif["IFD0"]["Make"]));
			$model = ucwords ($exif["IFD0"]["Model"]);
			if (trim ($make) != "") $result['Camera']['Camera/Scanner make'] = trim ($make);
			if (trim ($model) != "") $result['Camera']['Camera/Scanner model'] = trim ($model);
		}
    
		if (isset ($exif["COMMENT"]))
    {
      if (is_array ($exif["COMMENT"]))
      {
        foreach ($exif["COMMENT"] as $key => $value)
        {
          if (trim ($value) != "") $result['Comment'][$key] = trim ($value);
        }
      }
    }
    
		if (isset ($exif["COMPUTED"]))
    {
      if (is_array ($exif["COMPUTED"]))
      {
        foreach ($exif["COMPUTED"] as $key => $value)
        {
          if (trim ($value) != "") $result['Computed'][$key] = trim ($value);
        }
      }
    }
    
    if (sizeof ($result) > 0) return $result;
    else return false;
	}
  else return false;
}

// ------------------------- iptc_getdata -----------------------------
// function: iptc_getdata()
// input: path to image file
// output: result array / false

function iptc_getdata ($file)
{
  global $user, $mgmt_config, $hcms_ext;

  if (is_file ($file))
  {
    if (!is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
    
    // get file info
    $file_info = getfileinfo ("", $file, "comp");
    
    // check file extension
    if (substr_count (strtolower ($hcms_ext['image']).".", $file_info['ext'].".") == 0) return false;
  
    // get location and media file name
    $location = getlocation ($file);
    $media = getobject ($file);
    
    // create temp file if file is encrypted
    $temp = createtempfile ($location, $media);
    
    if ($temp['result'] && $temp['crypted']) $file = $temp['templocation'].$temp['tempfile'];
  
    $size = getimagesize ($file, $info);
    
    if ($size) $iptc = iptcparse ($info['APP13']);
    else $iptc = false;
    
    // delete temp file
    if ($temp['result'] && $temp['created']) deletefile ($temp['templocation'], $temp['tempfile'], 0);
    
    $result = array();
    
    if (is_array ($iptc))
    {
      if (trim ($iptc['2#003'][0]) != "") $result['Object type'] = trim ($iptc['2#003'][0]);
      if (trim ($iptc['2#005'][0]) != "") $result['Title'] = trim ($iptc['2#005'][0]);
      if (trim ($iptc['2#007'][0]) != "") $result['Edit status'] = trim ($iptc['2#007'][0]);
      if (trim ($iptc['2#010'][0]) != "") $result['Urgency'] = trim ($iptc['2#010'][0]);
      if (trim ($iptc['2#015'][0]) != "") $result['Category'] = trim ($iptc['2#015'][0]);
      if (trim ($iptc['2#020'][0]) != "") $result['Supplemental category'] = trim ($iptc['2#020'][0]);
      if (is_array ($iptc["2#025"]) && trim ($iptc["2#025"][0]) != "") $result['Keywords'] = implode (", ", $iptc["2#025"]);
      if (trim ($iptc['2#030'][0]) != "") $result['Release date'] = trim ($iptc['2#030'][0]);
      if (trim ($iptc['2#035'][0]) != "") $result['Release time'] = trim ($iptc['2#035'][0]);
      if (trim ($iptc['2#037'][0]) != "") $result['Expiriation date'] = trim ($iptc['2#037'][0]);
      if (trim ($iptc['2#038'][0]) != "") $result['Expiriation time'] = trim ($iptc['2#038'][0]);
      if (trim ($iptc['2#040'][0]) != "") $result['Special instructions'] = trim ($iptc['2#040'][0]);
      if (trim ($iptc['2#055'][0]) != "") $result['Creation date'] = trim ($iptc['2#055'][0]);
      if (trim ($iptc['2#060'][0]) != "") $result['Creation time'] = trim ($iptc['2#060'][0]);
      if (trim ($iptc['2#063'][0]) != "") $result['Digital creation date'] = trim ($iptc['2#063'][0]);
      if (trim ($iptc['2#065'][0]) != "") $result['Digital creation time'] = trim ($iptc['2#065'][0]);
      if (trim ($iptc['2#080'][0]) != "") $result['Photographer'] = trim ($iptc['2#080'][0]);
      if (trim ($iptc['2#085'][0]) != "") $result['Photographer title'] = trim ($iptc['2#085'][0]);
      if (trim ($iptc['2#090'][0]) != "") $result['City'] = trim ($iptc['2#090'][0]);
      if (trim ($iptc['2#095'][0]) != "") $result['State'] = trim ($iptc['2#095'][0]);
      if (trim ($iptc['2#101'][0]) != "") $result['Country'] = trim ($iptc['2#101'][0]);
      if (trim ($iptc['2#103'][0]) != "") $result['OTR'] = trim ($iptc['2#103'][0]);
      if (trim ($iptc['2#105'][0]) != "") $result['Headline'] = trim ($iptc['2#105'][0]);
      if (trim ($iptc['2#110'][0]) != "") $result['Credit'] = trim ($iptc['2#110'][0]);
      if (trim ($iptc['2#115'][0]) != "") $result['Source'] = trim ($iptc['2#115'][0]);
      if (trim ($iptc['2#116'][0]) != "") $result['Copyright'] = trim ($iptc['2#116'][0]);
      if (trim ($iptc['2#118'][0]) != "") $result['Contact'] = trim ($iptc['2#118'][0]);
      if (trim ($iptc['2#120'][0]) != "") $result['Description'] = trim ($iptc['2#120'][0]);
      if (trim ($iptc['2#122'][0]) != "") $result['Description author'] = trim ($iptc['2#122'][0]);
      if (trim ($iptc['2#130'][0]) != "") $result['Image type'] = trim ($iptc['2#130'][0]);
      if (trim ($iptc['2#131'][0]) != "") $result['Image orientation'] = trim ($iptc['2#131'][0]);
      if (trim ($iptc['2#135'][0]) != "") $result['Language'] = trim ($iptc['2#135'][0]);

      if (sizeof ($result) > 0) return $result;
      else return false;
    }
    else return false;
  }
  else return false;
}

// ------------------------- iptc_getcharset -----------------------------
// function: iptc_getcharset()
// input: iptc tag that holds character set information 
// output: charset as string / false on error

// description:
// Copied from MediaWiki!
// Warning, this function does not (and is not intended to) detect all iso 2022 escape codes.
// In practise, the code for utf-8 is the only code that seems to have wide use. It does detect that code.
// According to iim standard, charset is defined by the tag 1:90.
// in which there are iso 2022 escape sequences to specify the character set.
// the iim standard seems to encourage that all necessary escape sequences are
// in the 1:90 tag, but says it doesn't have to be.
// This is in need of more testing probably. This is definitely not complete.
// however reading the docs of some other iptc software, it appears that most iptc software
// only recognizes utf-8. If 1:90 tag is not present content is
// usually ascii or iso-8859-1 (and sometimes utf-8), but no guarantee.
// This also won't work if there are more than one escape sequence in the 1:90 tag
// or if something is put in the G2, or G3 charsets, etc. It will only reliably recognize utf-8.
// This is just going through the charsets mentioned in appendix C of the iim standard.

function iptc_getcharset ($tag)
{
  // \x1b = ESC.
  switch ($tag)
  {
  	case "\x1b%G": //utf-8
  	//Also call things that are compatible with utf-8, utf-8 (e.g. ascii)
  	case "\x1b(B": // ascii
  	case "\x1b(@": // iso-646-IRV (ascii in latest version, $ different in older version)
  		$c = 'UTF-8';
  		break;
  	case "\x1b(A": //like ascii, but british.
  		$c = 'ISO646-GB';
  		break;
  	case "\x1b(C": //some obscure sweedish/finland encoding
  		$c = 'ISO-IR-8-1';
  		break;
  	case "\x1b(D":
  		$c = 'ISO-IR-8-2';
  		break;
  	case "\x1b(E": //some obscure danish/norway encoding
  		$c = 'ISO-IR-9-1';
  		break;
  	case "\x1b(F":
  		$c = 'ISO-IR-9-2';
  		break;
  	case "\x1b(G":
  		$c = 'SEN_850200_B'; // aka iso 646-SE; ascii-like
  		break;
  	case "\x1b(I":
  		$c = "ISO646-IT";
  		break;
  	case "\x1b(L":
  		$c = "ISO646-PT";
  		break;
  	case "\x1b(Z":
  		$c = "ISO646-ES";
  		break;
  	case "\x1b([":
  		$c = "GREEK7-OLD";
  		break;
  	case "\x1b(K":
  		$c = "ISO646-DE";
  		break;
  	case "\x1b(N":  //crylic
  		$c = "ISO_5427";
  		break;
  	case "\x1b(`": //iso646-NO
  		$c = "NS_4551-1";
  		break;
  	case "\x1b(f": //iso646-FR
  		$c = "NF_Z_62-010";
  		break;
  	case "\x1b(g":
  		$c = "PT2"; //iso646-PT2
  		break;
  	case "\x1b(h":
  		$c = "ES2";
  		break;
  	case "\x1b(i": //iso646-HU
  		$c = "MSZ_7795.3";
  		break;
  	case "\x1b(w":
  		$c = "CSA_Z243.4-1985-1";
  		break;
  	case "\x1b(x":
  		$c = "CSA_Z243.4-1985-2";
  		break;
  	case "\x1b\$(B":
  	case "\x1b\$B":
  	case "\x1b&@\x1b\$B":
  	case "\x1b&@\x1b\$(B":
  		$c = "JIS_C6226-1983";
  		break;
  	case "\x1b-A": // iso-8859-1. at least for the high code characters.
  	case "\x1b(@\x1b-A":
  	case "\x1b(B\x1b-A":
  		$c = 'ISO-8859-1';
  		break;
  	case "\x1b-B": // iso-8859-2. at least for the high code characters.
  		$c = 'ISO-8859-2';
  		break;
  	case "\x1b-C": // iso-8859-3. at least for the high code characters.
  		$c = 'ISO-8859-3';
  		break;
  	case "\x1b-D": // iso-8859-4. at least for the high code characters.
  		$c = 'ISO-8859-4';
  		break;
  	case "\x1b-E": // iso-8859-5. at least for the high code characters.
  		$c = 'ISO-8859-5';
  		break;
  	case "\x1b-F": // iso-8859-6. at least for the high code characters.
  		$c = 'ISO-8859-6';
  		break;
  	case "\x1b-G": // iso-8859-7. at least for the high code characters.
  		$c = 'ISO-8859-7';
  		break;
  	case "\x1b-H": // iso-8859-8. at least for the high code characters.
  		$c = 'ISO-8859-8';
  		break;
  	case "\x1b-I": // CSN_369103. at least for the high code characters.
  		$c = 'CSN_369103';
  		break;
  	default:
  		//at this point just give up and refuse to parse iptc?
  		$c = false;
  }
    
  return $c;
}

// ------------------------- iptc_maketag -----------------------------
// function: iptc_maketag()
// input: type of tag (e.g. 2), code of tag (e.g. 025), value of tag
// output: binary IPTC tag / false on error

// description:
// convert the IPTC tag into binary code.

function iptc_maketag ($record=2, $tag, $value)
{
  if ($record >= 0 && $tag != "")
  {
    $length = strlen ($value);
    $retval = chr(0x1C).chr($record).chr($tag);

    if ($length < 0x8000)
    {
      $retval .= chr($length >> 8).chr($length & 0xFF);
    }
    else
    {
      $retval .= chr(0x80) . 
                 chr(0x04) . 
                 chr(($length >> 24) & 0xFF) . 
                 chr(($length >> 16) & 0xFF) . 
                 chr(($length >> 8) & 0xFF) . 
                 chr($length & 0xFF);
    }

    return $retval.$value;
  }
  else return false;
}

// ------------------------- iptc_writefile -----------------------------
// function: iptc_writefile()
// input: abs. path to image file, IPTC tag array, keep existing IPTC data of file [true,false] (optional)
// output: true / false on error

// description:
// writes IPTC tags into image file for supported file types and keeps the existing IPTC tags

function iptc_writefile ($file, $iptc, $keep_data=true)
{
  global $user, $mgmt_config, $mgmt_mediametadata;

  // write meta data only for the following file extensions
  $allowed_ext = array (".jpg", ".jpeg", ".pjpeg");
  
  if (is_file ($file) && is_array ($iptc))
  {
    $iptc = array();
    $encoding = "UTF-8";

    // get file info
    $file_info = getfileinfo ("", $file, "comp");
    
    // check file extension
    if (!in_array ($file_info['ext'], $allowed_ext)) return false;
    
    // get location and media file name
    $location = getlocation ($file);
    $media = getobject ($file);
    
    // create temp file if file is encrypted
    $temp = createtempfile ($location, $media);
    
    if ($temp['result'] && $temp['crypted']) $file = $temp['templocation'].$temp['tempfile'];
    
    if (is_file ($file))
    {
      // get IPTC info stored in file
      $imagesize = getimagesize ($file, $info);
      
      if (isset ($info['APP13']))
      {
        // parse binary IPTC block
        $iptc_old = iptcparse ($info['APP13']);
        
        // get charset info contained in tag 1:90.
        if (isset ($iptc_old["1#090"][0])) $encoding = iptc_getcharset (trim ($iptc_old["1#090"][0]));
        
        // compare old and new information to keep existing data
        if ($keep_data == true || $keep_data == 1)
        {
          if (is_array ($iptc_old))
          {
            // compare old and new tags
            foreach ($iptc_old as $tag => $value)
            {
              if (!isset ($iptc[$tag]))
              {
                // add old tags to iptc array
                if ($tag == "2#025" && is_array ($iptc_old[$tag])) $iptc[$tag] = implode (", ", $iptc_old[$tag]);
                else $iptc[$tag] = trim ($iptc_old[$tag][0]);
              }
            }
          }
        }
      }
  
      // remove IPTC data from file before embedding IPTC
      if (is_array ($mgmt_mediametadata))
      {
        // get file info
        $file_info = getfileinfo ("", $file, "comp");
      
        // define executable
        foreach ($mgmt_mediametadata as $extensions => $executable)
        {
          if (substr_count ($extensions.".", $file_info['ext'].".") > 0)
          {
            // remove all IPTC tags from file
            $cmd = $executable." -overwrite_original -r -IPTC:all= \"".shellcmd_encode ($file)."\"";          
            @exec ($cmd, $buffer, $errorCode);

            // on error
            if ($errorCode)
            {
              $errcode = "20242";
              $error[] = $mgmt_config['today']."|hypercms_meta.inc.php|error|$errcode|exec of EXIFTOOL (code:$errorCode) failed for file: ".getobject($file);
            }
          }
        }
      }

      // convert the IPTC tags into binary code
      $data = "";
      
      if (sizeof ($iptc) > 0)
      {
        foreach ($iptc as $tag => $value)
        {
          $record = substr ($tag, 0, 1);
          $tag = substr ($tag, 2);  
          $data .= iptc_maketag ($record, $tag, html_decode ($value, $encoding)); 
        }
      }
  
      // embed the IPTC data (only JPEG files)
      $content = iptcembed ($data, $file);
       
      // write the new image data to the file
      $fp = fopen ($file, "wb");
      
      if ($fp)
      {
        fwrite ($fp, $content);
        fclose ($fp);
        
        // encrypt and save file if required
        if ($temp['result'] && $temp['created']) movetempfile ($location, $media, true);
        
        return true;
      }
    }
    
    return false;
  }
  else return false;
}


// ------------------------- iptc_create -----------------------------
// function: iptc_create()
// input: publication name, text array (from content container)
// output: IPTC tag array / false on error

// description:
// defines IPTC tag array based on the medai mapping of a publication.

function iptc_create ($site, $text)
{
  global $mgmt_config;
  
  if (valid_publicationname ($site) && is_array ($text) && !empty ($mgmt_config['abs_path_data']))
  {
    // try to load mapping configuration file of publication
    if (is_file ($mgmt_config['abs_path_data']."config/".$site.".media.map.php"))
    {
      @include ($mgmt_config['abs_path_data']."config/".$site.".media.map.php");
    }
    
    $iptc_info = array();
    
    // list of most common IPTC tags (name => tag)
    $iptc_info['iptc:object_type'] = "2#003";
    $iptc_info['iptc:title'] = "2#005";
    $iptc_info['iptc:edit_status'] = "2#007";
    $iptc_info['iptc:urgency'] = "2#010";
    $iptc_info['iptc:category'] = "2#015";
    $iptc_info['iptc:supplemental_categories'] = "2#020";
    $iptc_info['iptc:keywords'] = "2#025";
    $iptc_info['iptc:release_date'] = "2#030";
    $iptc_info['iptc:release_time'] = "2#035";
    $iptc_info['iptc:expiriation_date'] = "2#037";
    $iptc_info['iptc:expiriation_time'] = "2#038";
    $iptc_info['iptc:special_instructions'] = "2#040";
    $iptc_info['iptc:creation_date'] = "2#055";
    $iptc_info['iptc:creation_time'] = "2#060";
    $iptc_info['iptc:digital_creation_date'] = "2#063";
    $iptc_info['iptc:digital_creation_time'] = "2#065";
    $iptc_info['iptc:photographer'] = "2#080";
    $iptc_info['iptc:photographer_title'] = "2#085";
    $iptc_info['iptc:city'] = "2#090";
    $iptc_info['iptc:state'] = "2#095";
    $iptc_info['iptc:country'] = "2#101";
    $iptc_info['iptc:otr'] = "2#103";
    $iptc_info['iptc:headline'] = "2#105";
    $iptc_info['iptc:credit'] = "2#110";
    $iptc_info['iptc:source'] = "2#115";
    $iptc_info['iptc:copyright'] = "2#116";
    $iptc_info['iptc:contact'] = "2#118";
    $iptc_info['iptc:description'] = "2#120";
    $iptc_info['iptc:description_author'] = "2#122";
    $iptc_info['iptc:image_type'] = "2#130";
    $iptc_info['iptc:image_orientation'] = "2#131";
    $iptc_info['iptc:language'] = "2#135";
    
    $result = array();
    
    // look in mapping definition (name => id)
    if (isset ($mapping) && is_array ($mapping))
    {
      foreach ($mapping as $name => $id)
      {  
        $name = strtolower ($name);
        
        // set IPTC tag (tag => value)
        if ($id != "" && isset ($text[$id]) && !empty ($iptc_info[$name]))
        {
          $tag = $iptc_info[$name];
          if ($tag != "") $result[$tag] = $text[$id];
        }
      }
      
      return $result;
    }
    else return false;
  }
  else return false;
}
   
// ------------------------- createmapping -----------------------------
// function: createmapping()
// input: publication name, mapping definition
// output: true / false on error

// description:
// prepares the PHP mapping array from the provided mapping definition and saves media mapping file

function createmapping ($site, $mapping)
{
  global $mgmt_config;
  
  if ($mapping != "")
  {
    $mapping_result = "";
    // unescape >
    $mapping = str_replace ("&gt;", ">", $mapping);
    // create array
    $lines = explode ("\n", $mapping);   
    
    if (is_array ($lines))
    {
      foreach ($lines as $line)
      {
        if ($line != "" && substr_count ($line, "=>") == 1 && substr_count ($line, "//") == 0)
        {
          list ($key, $value) = explode ("=>", $line);
          
          if ($key != "" && $value != "")
          {
            if (substr_count ($key, "\"") > 0) $key_array = explode ("\"", $key);
            elseif (substr_count ($key, "'") > 0) $key_array = explode ("'", $key);
            else $key_array[1] = $key;
            
            if (is_array ($key_array)) $map_tag = "\$mapping['".trim(addslashes(strip_tags ($key_array[1])))."']";
            else $map_tag = false;
            
            if (substr_count ($value, "\"") > 0) $value_array = explode ("\"", $value);
            elseif (substr_count ($value, "'") > 0) $value_array = explode ("'", $value);
            else $value_array[1] = $value;
            
            if (is_array ($value_array) && $value_array[1] != "") $map_id = "\"".trim(addslashes(strip_tags ($value_array[1])))."\";\n";
            else $map_id = false;
            
            if ($map_tag != false && $map_id != false) $mapping_result .= $map_tag." = ".$map_id;
          }
        }
      }
    }
    
    if ($mapping != "")
    {
      // remove all tags
      $mapping_data_info = strip_tags ($mapping);
      $mapping_data_save = "/*\n".$mapping_data_info."\n*/\n/* hcms_mapping */\n".trim ($mapping_result);
    
      // save mapping file
      return savefile ($mgmt_config['abs_path_data']."config/", $site.".media.map.php", "<?php\n".$mapping_data_save."\n?>");
    }
    else return false;
  }
  else return false;
}

// ------------------------- getmapping -----------------------------
// function: getmapping()
// input: publication name
// output: mapping code for display / false

// description:
// loads the mapping file of the provided publication.

function getmapping ($site)
{
  global $mgmt_config;
  
  $mapping_data = "";
  
  if (valid_publicationname ($site) && @is_file ($mgmt_config['abs_path_data']."config/".$site.".media.map.php"))
  {
    // load pers file
    $mapping_data = loadfile ($mgmt_config['abs_path_data']."config/", $site.".media.map.php");

    if ($mapping_data != "")
    {
      list ($mapping_data, $empty) = explode ("/* hcms_mapping */", $mapping_data);
      
      // convert from older version to 5.5.8
      if (substr_count ($mapping_data, "\$mapping[") > 0)
      {
        $mapping_data = str_replace (array("\$mapping['", "']", "=", "\"", ";"), array("", "", "=>", "", ""), $mapping_data);
      }
      
      // remove comment tags
      $mapping_data = str_replace (array("/*", "*/"), array("", ""), $mapping_data);  
      
      // remove php tags
      $mapping_data = str_replace (array("<?php", "?>"), array("", ""), $mapping_data);
      
      // escape & < >
      $mapping_data = str_replace (array("<", ">"), array("&lt;", "&gt;"), $mapping_data);
      
      // trim
      return trim ($mapping_data);
    }
  }
  
  // default mapping
  if ($mapping_data == "")
  {
    return '// Mapping definition: Meta data tag => "hyperCMS tag-ID"

// IPTC tags
iptc:title => "Title"
iptc:keywords => "Keywords"
iptc:description => "Description"
iptc:photographer => "Creator"
iptc:source => "Copyright"
iptc:urgency => ""
iptc:category => ""
iptc:supp_categories => ""
iptc:spec_instr => ""
iptc:creation_date => ""
iptc:credit_byline_title => ""
iptc:city => ""
iptc:state => ""
iptc:country => ""
iptc:otr => ""
iptc:headline => ""
iptc:source => ""
iptc:photo_number => ""
iptc:photo_source => ""
iptc:charset => ""

// XMP -> Dublin Core namespace tags
dc:title => "Title"
dc:subject => "Keywords"
dc:description => "Description"
dc:creator => "Creator"
dc:rights => "Copyright"
dc:contributor => ""
dc:coverage => ""
dc:date => ""
dc:format =>""
dc:identifier => ""
dc:language => ""
dc:publisher => ""
dc:relation => ""
dc:rights => ""
dc:source => ""
dc:type => ""

// XMP -> Adobe PhotoShop namespace tags
photoshop:AuthorsPosition => ""
photoshop:CaptionWriter => ""
photoshop:Category => ""
photoshop:City => ""
// ColorMode:
// 0 = Bitmap
// 1 = Grayscale
// 2 = Indexed
// 3 = RGB
// 4 = CMYK
// 7 = Multichannel
// 8 = Duotone
// 9 = Lab
photoshop:ColorMode => ""
photoshop:Country => ""
photoshop:Credit => ""
photoshop:DateCreated => ""
photoshop:DocumentAncestors => ""
photoshop:DocumentAncestorID => ""
photoshop:Headline => ""
photoshop:History => ""
photoshop:ICCProfileName => ""
photoshop:Instructions => ""
photoshop:LegacyIPTCDigest => ""
photoshop:SidecarForExtension => ""
photoshop:Source => ""
photoshop:State => ""
photoshop:SupplementalCategories => ""
photoshop:TextLayers => ""
photoshop:TextLayerName => ""
photoshop:TextLayerText => ""
photoshop:TransmissionReference => ""
photoshop:Urgency => ""

// EXIF tags
// EXIF-Sections:
// FILE ...	FileName, FileSize, FileDateTime, SectionsFound
// COMPUTED ... html, Width, Height, IsColor, and more if available. Height and Width are computed the same way getimagesize() does so their values must not be part of any header returned. Also, html is a height/width text string to be used inside normal HTML.
// ANY_TAG ...	Any information that has a Tag e.g. IFD0, EXIF, ...
// IFD0 ... All tagged data of IFD0. In normal imagefiles this contains image size and so forth.
// THUMBNAIL ...	A file is supposed to contain a thumbnail if it has a second IFD. All tagged information about the embedded thumbnail is stored in this section.
// COMMENT ...	Comment headers of JPEG images.
// EXIF ... The EXIF section is a sub section of IFD0. It contains more detailed information about an image. Most of these entries are digital camera related.
// exif:SECTION.Tag-Name
exif:FILE.FileName => ""
exif:FILE.FileDateTime => ""
exif:FILE.FileSize => ""
exif:FILE.FileType => ""
exif:FILE.MimeType => ""
exif:FILE.SectionsFound => ""
exif:COMPUTED.html => ""
exif:COMPUTED.Height => ""
exif:COMPUTED.Width => ""
exif:COMPUTED.IsColor => ""
exif:COMPUTED.ByteOrderMotorola => ""
exif:COMPUTED.Thumbnail.FileType => ""
exif:COMPUTED.Thumbnail.MimeType => ""
exif:IFD0.DateTime => ""
exif:IFD0.Artist => ""
exif:IFD0.Exif_IFD_Pointer => ""
exif:IFD0.Title => ""
exif:THUMBNAIL.Compression => ""
exif:THUMBNAIL.XResolution => ""
exif:THUMBNAIL.YResolution => ""
exif:THUMBNAIL.ResolutionUnit => ""
exif:THUMBNAIL.JPEGInterchangeFormat => ""
exif:THUMBNAIL.JPEGInterchangeFormatLength => ""
exif:EXIF.DateTimeOriginal => ""
exif:EXIF.DateTimeDigitized => ""

// ID3 -> ID3 namespace tags
id3:title => "Title"
id3:artist => "Creator"
id3:album => ""
id3:year => ""
id3:comment => "Description"
id3:track => ""
id3:genre => ""
id3:tracknumber => ""
id3:band => ""

// Image Resolution defines Quality [Print, Web]
hcms:quality => "Quality"';
  }
}

// ------------------------- setmetadata -----------------------------
// function: setmetadata()
// input: publication name, location path (optional), object name (optional), media file name (optional), mapping array (meta data tag name -> text-id, optional), user name 
// output: true/false

// description:
// saves meta data of a multimedia file using a provided mapping in the proper fields of the content container. 
// if no mapping is given a default mapping will be used.

function setmetadata ($site, $location="", $object="", $mediafile="", $mapping="", $user)
{
  global $eventsystem, $mgmt_config, $hcms_ext;
  
  if (!is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

  // IPTC-tag and xml-tag name from multimedia file is mapped with text-id of the content container.
  // text-ids need to be defined in the meta data defintion. 
  if ((!is_array ($mapping) || sizeof ($mapping) == 0) && !empty ($mgmt_config['abs_path_data']))
  {
    // try to load mapping configuration file of publication
    if (valid_publicationname ($site) && @is_file ($mgmt_config['abs_path_data']."config/".$site.".media.map.php"))
    {
      @include ($mgmt_config['abs_path_data']."config/".$site.".media.map.php");
    }
    // define mapping if undefined
    else
    {
      $mapping = array();
      
      // IPTC tags
      $mapping['iptc:charset'] = "";
      $mapping['iptc:object_type'] = "";
      $mapping['iptc:title'] = "Title";
      $mapping['iptc:edit_status'] = "";
      $mapping['iptc:urgency'] = "";
      $mapping['iptc:category'] = "";
      $mapping['iptc:supplemental_categories'] = "";
      $mapping['iptc:keywords'] = "Keywords";
      $mapping['iptc:photographer'] = "Creator";
      $mapping['iptc:source'] = "";  
      $mapping['iptc:expiriation_date'] = "";
      $mapping['iptc:expiriation_time'] = "";
      $mapping['iptc:special_instructions'] = "";
      $mapping['iptc:creation_date'] = "";
      $mapping['iptc:creation_time'] = "";
      $mapping['iptc:digital_creation_date'] = "";
      $mapping['iptc:digital_creation_time'] = "";
      $mapping['iptc:photographer'] = "Creator";
      $mapping['iptc:photographer_title'] = "";
      $mapping['iptc:city'] = "";
      $mapping['iptc:state'] = "";
      $mapping['iptc:country'] = "";
      $mapping['iptc:otr'] = "";
      $mapping['iptc:headline'] = "";
      $mapping['iptc:credit'] = "";
      $mapping['iptc:source'] = "Copyright";
      $mapping['iptc:copyright'] = "";
      $mapping['iptc:contact'] = "";
      $mapping['iptc:description'] = "Description";
      $mapping['iptc:description_author'] = "";
      $mapping['iptc:image_type'] = "";
      $mapping['iptc:image_orientation'] = "";
      $mapping['iptc:language'] = "";

      // XMP -> Dublin Core namespace tags
      $mapping['dc:title'] = "Title";
      $mapping['dc:subject'] = "Keywords";
      $mapping['dc:description'] = "Description";
      $mapping['dc:creator'] = "Creator";
      $mapping['dc:rights'] = "Copyright";
      $mapping['dc:contributor'] = "";
      $mapping['dc:coverage'] = "";
      $mapping['dc:date'] = "";
      $mapping['dc:format'] = "";
      $mapping['dc:identifier'] = "";
      $mapping['dc:language'] = "";
      $mapping['dc:publisher'] = "";
      $mapping['dc:relation'] = "";
      $mapping['dc:source'] = "";
      $mapping['dc:type'] = "";

      // XMP -> Adobe PhotoShop namespace tags
      $mapping['photoshop:Source'] = "";
      $mapping['photoshop:Credit'] = "";
      $mapping['photoshop:DateCreated'] = "";
      $mapping['photoshop:AuthorsPosition'] = "";
      $mapping['photoshop:CaptionWriter'] = "";
      $mapping['photoshop:Category'] = "";
      $mapping['photoshop:SupplementalCategories'] = "";
      $mapping['photoshop:City'] = "";
      $mapping['photoshop:State'] = "";
      $mapping['photoshop:Country'] = "";
      $mapping['photoshop:DocumentAncestors'] = "";
      $mapping['photoshop:DocumentAncestorID'] = "";
      $mapping['photoshop:Headline'] = "";
      $mapping['photoshop:Instructions'] = "";
      $mapping['photoshop:History'] = "";
      $mapping['photoshop:Urgency'] = "";
      // ColorMode:
      // 0 = Bitmap
      // 1 = Grayscale
      // 2 = Indexed
      // 3 = RGB
      // 4 = CMYK
      // 7 = Multichannel
      // 8 = Duotone
      // 9 = Lab	
      $mapping['photoshop:ColorMode'] = "";
      $mapping['photoshop:ICCProfileName'] = "";
      $mapping['photoshop:LegacyIPTCDigest'] = "";
      $mapping['photoshop:SidecarForExtension'] = "";
      $mapping['photoshop:TextLayers'] = "";
      $mapping['photoshop:TextLayerName'] = "";
      $mapping['photoshop:TextLayerText'] = "";
      $mapping['photoshop:TransmissionReference'] = "";
      
      // EXIF tags
      // EXIF-Sections:
      // FILE ...	FileName, FileSize, FileDateTime, SectionsFound
      // COMPUTED ... html, Width, Height, IsColor, and more if available. Height and Width are computed the same way getimagesize() does so their values must not be part of any header returned. Also, html is a height/width text string to be used inside normal HTML.
      // ANY_TAG ...	Any information that has a Tag e.g. IFD0, EXIF, ...
      // IFD0 ... All tagged data of IFD0. In normal imagefiles this contains image size and so forth.
      // THUMBNAIL ...	A file is supposed to contain a thumbnail if it has a second IFD. All tagged information about the embedded thumbnail is stored in this section.
      // COMMENT ...	Comment headers of JPEG images.
      // EXIF ... The EXIF section is a sub section of IFD0. It contains more detailed information about an image. Most of these entries are digital camera related.
      // exif:SECTION.Tag-Name
      $mapping['exif:FILE.FileName'] = "";
      $mapping['exif:FILE.FileDateTime'] = "";
      $mapping['exif:FILE.FileSize'] = "";
      $mapping['exif:FILE.FileType'] = "";
      $mapping['exif:FILE.MimeType'] = "";
      $mapping['exif:FILE.SectionsFound'] = "";
      $mapping['exif:COMPUTED.html'] = "";
      $mapping['exif:COMPUTED.Height'] = "";
      $mapping['exif:COMPUTED.Width'] = "";
      $mapping['exif:COMPUTED.IsColor'] = "";
      $mapping['exif:COMPUTED.ByteOrderMotorola'] = "";
      $mapping['exif:COMPUTED.Thumbnail.FileType'] = "";
      $mapping['exif:COMPUTED.Thumbnail.MimeType'] = "";
      $mapping['exif:IFD0.DateTime'] = "";
      $mapping['exif:IFD0.Artist'] = "";
      $mapping['exif:IFD0.Exif_IFD_Pointer'] = "";
      $mapping['exif:IFD0.Title'] = "";
      $mapping['exif:THUMBNAIL.Compression'] = "";
      $mapping['exif:THUMBNAIL.XResolution'] = "";
      $mapping['exif:THUMBNAIL.YResolution'] = "";
      $mapping['exif:THUMBNAIL.ResolutionUnit'] = "";
      $mapping['exif:THUMBNAIL.JPEGInterchangeFormat'] = "";
      $mapping['exif:THUMBNAIL.JPEGInterchangeFormatLength'] = "";
      $mapping['exif:EXIF.DateTimeOriginal'] = "";
      $mapping['exif:EXIF.DateTimeDigitized'] = "";
      
      // ID3 -> ID3 namespace tags
      $mapping['id3:title'] = "Title";
      $mapping['id3:artist'] = "Creator";
      $mapping['id3:album'] = "";
      $mapping['id3:year'] = "";
      $mapping['id3:comment'] = "Description";
      $mapping['id3:track'] = "";
      $mapping['id3:genre'] = "";
      $mapping['id3:tracknumber'] = "";
      $mapping['id3:band'] = "";
      
      // Image Resolution defines Quality [Print, Web]
      $mapping['hcms:quality'] = "Quality";
    }
  }

  if (valid_publicationname ($site) && ((valid_locationname ($location) && valid_objectname ($object)) || valid_objectname ($mediafile)) && is_array ($mapping) && valid_objectname ($user))
  {
    // get media file if not given
    if ($mediafile == "" || !valid_objectname ($mediafile))
    {
      // deconvert path
      $location = deconvertpath ($location, "file");
    
      $objectdata = loadfile ($location, $object);
    
      if ($objectdata != "") $mediafile = getfilename ($objectdata, "media");
      else return false;
    }
       
    // get the file extension
    $mediafile_ext = strtolower (@strrchr ($mediafile, "."));

    // check file extension
    if (substr_count (strtolower ($hcms_ext['audio'].$hcms_ext['image'].$hcms_ext['video']).".", $mediafile_ext.".") > 0)
    {
      // media location
      $medialocation = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";

      // load multimedia file   
      $mediadata = decryptfile ($medialocation, $mediafile);

      // get container information
      $container_id = getmediacontainerid ($mediafile);
      $container = getmediacontainername ($mediafile);
      $containerdata = loadcontainer ($container, "work", $user);
      
      // get destination character set
      $charset_array = getcharset ($site, $containerdata);

      // set to UTF-8 if not available
      if (is_array ($charset_array)) $charset_dest = $charset_array['charset'];
      else $charset_dest = "UTF-8";

      // read metadata based on EXIF, XMP, IPTC in this order (so IPTC will overwrite EXIF and XMP if not empty)
      // XMP should be UTF-8 encoded but Adobe does not provide proper encoding of special characters 
      if ($mediadata != false && $containerdata != false)
      {        
        // load text XML-schema
        $text_schema_xml = chop (loadfile ($mgmt_config['abs_path_cms']."xmlsubschema/", "text.schema.xml.php"));
        
        // create temp file if file is encrypted
        $temp = createtempfile ($medialocation, $mediafile);
        
        if ($temp['result'] && $temp['crypted'])
        {
          $medialocation = $temp['templocation'];
          $mediafile = $temp['tempfile'];
        }
        
        // ------------------- ID3 -------------------

        // get ID3 data from file
        $id3_data = id3_getdata ($medialocation.$mediafile);

        // inject meta data based on mapping
        if (is_array ($id3_data))
        {
          // get source charset
          if (!empty ($id3_data['encoding'])) $charset_source = $id3_data['encoding'];
          else $charset_source = "UTF-8"; 
        
          reset ($mapping);
          
          foreach ($mapping as $key => $text_id)
          {
            // only for ID3 tags (audio files)
            if (substr_count ($key, "id3:") == 1 && $text_id != "")
            {          
              // get ID3 tag name
              if (strpos ($key, ":") > 0) list ($namespace, $key) = explode (":", $key);
              
              // get data
              if (!empty ($id3_data[$key])) $id3str = $id3_data[$key];
              else $id3str = "";
          
              if ($id3str != "")
              {
                // remove tags
                $id3str = strip_tags ($id3str);           

                // convert string for container
                if ($charset_source != "" && $charset_dest != "" && $charset_source != $charset_dest)
                {
                  $id3str = convertchars ($id3str, $charset_source, $charset_dest);
                }
                elseif ($charset_dest == "UTF-8")
                {
                  // encode to UTF-8
                  if (!is_utf8 ($id3str)) $id3str = utf8_encode ($id3str);
                }
  
                // html encode string
                $id3str = html_encode ($id3str, $charset_dest);

                // textnodes search index in database
                $text_array[$text_id] = $id3str;
                                                
                $containerdata_new = setcontent ($containerdata, "<text>", "<textcontent>", "<![CDATA[".$id3str."]]>", "<text_id>", $text_id);
          
                if ($containerdata_new == false)
                {
                  $containerdata_new = addcontent ($containerdata, $text_schema_xml, "", "", "", "<textcollection>", "<text_id>", $text_id);
                  $containerdata_new = setcontent ($containerdata_new, "<text>", "<textcontent>", "<![CDATA[".$id3str."]]>", "<text_id>", $text_id);
                  $containerdata_new = setcontent ($containerdata_new, "<text>", "<textuser>", $user, "<text_id>", $text_id);
                }
          
                if ($containerdata_new != false) $containerdata = $containerdata_new;
                else return false;
              }
            }
          }
        }
        
        // ------------------- EXIF -------------------

        // set encoding for EXIF to UTF-8
        ini_set ('exif.encode_unicode', 'UTF-8');
        error_reporting(0);
        
        if (exif_imagetype ($medialocation.$mediafile))
        {
          // read EXIF data
          $exif_data = exif_read_data ($medialocation.$mediafile, 0, true);
         
          if (is_array ($exif_data))
          {
            $exif_info = array();
            
            foreach ($exif_data as $key => $section)
            {
              foreach ($section as $name => $value)
              {
                $exif_info['iptc:'.$key.'.'.$name] = $value;
              }
            }
          }            
          
          // inject meta data based on mapping
          reset ($mapping);
          
          foreach ($mapping as $key => $text_id)
          {
            // only for EXIF tags
            if (substr_count ($key, "exif:") > 0 && $text_id != "")
            {           
              if ($exif_info[$key] != "")
              {
                // remove tags
                $exif_info[$key] = strip_tags ($exif_info[$key]);           
                
                // we set encoding for EXIF to UTF-8
                $charset_source = "UTF-8";              
                
                // convert string for container
                if ($charset_source != "" && $charset_dest != "" && $charset_source != $charset_dest)
                {
                  $exif_info[$key] = convertchars ($exif_info[$key], $charset_source, $charset_dest);
                }
  
                // html encode string
                $exif_info[$key] = html_encode ($exif_info[$key], $charset_source);
  
                // textnodes search index in database
                $text_array[$text_id] = $exif_info[$key];
                                                
                $containerdata_new = setcontent ($containerdata, "<text>", "<textcontent>", "<![CDATA[".$exif_info[$key]."]]>", "<text_id>", $text_id);
          
                if ($containerdata_new == false)
                {
                  $containerdata_new = addcontent ($containerdata, $text_schema_xml, "", "", "", "<textcollection>", "<text_id>", $text_id);
                  $containerdata_new = setcontent ($containerdata_new, "<text>", "<textcontent>", "<![CDATA[".$exif_info[$key]."]]>", "<text_id>", $text_id);
                  $containerdata_new = setcontent ($containerdata_new, "<text>", "<textuser>", $user, "<text_id>", $text_id);
                }
          
                if ($containerdata_new != false) $containerdata = $containerdata_new;
                else return false;
              }
            }
          }
        }
    
        // ------------------- XMP-based -------------------
        
        // inject meta data based on mapping
        reset ($mapping);
        
        foreach ($mapping as $key => $text_id)
        {
          // only for XMP (XML-based) tags (DC, PhotoShop ...)
          if (substr_count ($key, "iptc:") == 0 && substr_count ($key, "hcms:") == 0 && substr_count ($key, "exif:") == 0 && $text_id != "")
          {          
            $dcstr = "";
            
            // extract XMP information
            $dc = getcontent ($mediadata, "<".$key.">");
            if ($dc != false) $dc = getcontent ($dc[0], "<rdf:li *>");
            if ($dc != false) $dcstr = implode (", ", $dc);
        
            if ($dcstr != "")
            {
              // remove tags
              $dcstr = strip_tags ($dcstr);           
              
              // XMP always using UTF-8 so should any other XML-based format
              $charset_source = "UTF-8";              
              
              // convert string for container
              if ($charset_source != "" && $charset_dest != "" && $charset_source != $charset_dest)
              {
                $dcstr = convertchars ($dcstr, $charset_source, $charset_dest);
              }
              elseif ($charset_dest == "UTF-8")
              {
                // encode to UTF-8
                if (!is_utf8 ($dcstr)) $dcstr = utf8_encode ($dcstr);
              }

              // html encode string
              $dcstr = html_encode ($dcstr, $charset_dest);

              // textnodes search index in database
              $text_array[$text_id] = $dcstr;
                                              
              $containerdata_new = setcontent ($containerdata, "<text>", "<textcontent>", "<![CDATA[".$dcstr."]]>", "<text_id>", $text_id);
        
              if ($containerdata_new == false)
              {
                $containerdata_new = addcontent ($containerdata, $text_schema_xml, "", "", "", "<textcollection>", "<text_id>", $text_id);
                $containerdata_new = setcontent ($containerdata_new, "<text>", "<textcontent>", "<![CDATA[".$dcstr."]]>", "<text_id>", $text_id);
                $containerdata_new = setcontent ($containerdata_new, "<text>", "<textuser>", $user, "<text_id>", $text_id);
              }
        
              if ($containerdata_new != false) $containerdata = $containerdata_new;
              else return false;
            }
          }
        }
        
        // ------------------- binary IPTC block -------------------
        
        $size = getimagesize ($medialocation.$mediafile, $info);

        if (isset ($info['APP13']))
        {
          // parse binary IPTC block
          $iptc = iptcparse ($info['APP13']);

          if (is_array ($iptc))
          {
            $iptc_info = array();
            
            // list of most common IPTC tags
            // charset info contained in tag 1:90.
            if (isset ($iptc["1#090"][0])) $iptc_info['iptc:charset'] = trim ($iptc["1#090"][0]);
            else $iptc_info['iptc:charset'] = "";
            if (isset ($iptc["2#005"][0])) $iptc_info['iptc:title'] = trim ($iptc["2#005"][0]);
            else $iptc_info['iptc:title'] = "";
            if (isset ($iptc["2#010"][0])) $iptc_info['iptc:urgency'] = trim ($iptc["2#010"][0]);
            else $iptc_info['iptc:urgency'] = "";
            if (isset ($iptc["2#015"][0])) $iptc_info['iptc:category'] = trim ($iptc["2#015"][0]);
            else $iptc_info['iptc:category'] = "";
            // note that sometimes supplemental_categories contains multiple entries
            if (isset ($iptc["2#020"][0])) $iptc_info['iptc:supplemental_categories'] = trim ($iptc["2#020"][0]);
            else $iptc_info['iptc:supplemental_categories'] = "";
            // get keywords saved in tag 2:25 and generate keyword list
            if (isset ($iptc["2#025"]) && is_array ($iptc["2#025"])) $iptc_info['iptc:keywords'] = implode (", ", $iptc["2#025"]);
            else $iptc_info['iptc:keywords'] = "";
            if (isset ($iptc["2#037"][0])) $iptc_info['iptc:expiriation_date'] = trim ($iptc["2#037"][0]);
            else $iptc_info['iptc:expiriation_date'] = "";
            if (isset ($iptc["2#038"][0])) $iptc_info['iptc:expiriation_time'] = trim ($iptc["2#038"][0]);
            else $iptc_info['iptc:expiriation_time'] = "";
            if (isset ($iptc["2#040"][0])) $iptc_info['iptc:special_instructions'] = trim ($iptc["2#040"][0]);
            else $iptc_info['iptc:special_instructions'] = "";
            if (isset ($iptc["2#055"][0])) $iptc_info['iptc:creation_date'] = trim ($iptc["2#055"][0]);
            else $iptc_info['iptc:creation_date'] = "";
            if (isset ($iptc["2#060"][0])) $iptc_info['iptc:creation_time'] = trim ($iptc["2#060"][0]);
            else $iptc_info['iptc:creation_time'] = "";
            if (isset ($iptc["2#063"][0])) $iptc_info['iptc:digital_creation_date'] = trim ($iptc["2#063"][0]);
            else $iptc_info['iptc:digital_creation_date'] = "";
            if (isset ($iptc["2#065"][0])) $iptc_info['iptc:digital_creation_time'] = trim ($iptc["2#065"][0]);
            else $iptc_info['iptc:digital_creation_time'] = "";
            if (isset ($iptc["2#080"][0])) $iptc_info['iptc:photographer'] = trim ($iptc["2#080"][0]);
            else $iptc_info['iptc:photographer'] = "";
            if (isset ($iptc["2#085"][0])) $iptc_info['iptc:photographer_title'] = trim ($iptc["2#085"][0]);
            else $iptc_info['iptc:photographer_title'] = "";
            if (isset ($iptc["2#090"][0])) $iptc_info['iptc:city'] = trim ($iptc["2#090"][0]);
            else $iptc_info['iptc:city'] = "";
            if (isset ($iptc["2#095"][0])) $iptc_info['iptc:state'] = trim ($iptc["2#095"][0]);
            else $iptc_info['iptc:state'] = "";
            if (isset ($iptc["2#101"][0])) $iptc_info['iptc:country'] = trim ($iptc["2#101"][0]);
            else $iptc_info['iptc:country'] = "";
            if (isset ($iptc["2#103"][0])) $iptc_info['iptc:otr'] = trim ($iptc["2#103"][0]);
            else $iptc_info['iptc:otr'] = "";
            if (isset ($iptc["2#105"][0])) $iptc_info['iptc:headline'] = trim ($iptc["2#105"][0]);
            else $iptc_info['iptc:headline'] = "";
            if (isset ($iptc["2#110"][0])) $iptc_info['iptc:credit'] = trim ($iptc["2#110"][0]);
            else $iptc_info['iptc:credit'] = "";
            if (isset ($iptc["2#115"][0])) $iptc_info['iptc:source'] = trim ($iptc["2#115"][0]);
            else $iptc_info['iptc:source'] = "";
            if (isset ($iptc["2#116"][0])) $iptc_info['iptc:copyright'] = trim ($iptc["2#116"][0]);
            else $iptc_info['iptc:copyright'] = "";
            if (isset ($iptc["2#118"][0])) $iptc_info['iptc:contact'] = trim ($iptc["2#118"][0]);
            else $iptc_info['iptc:contact'] = "";
            if (isset ($iptc["2#120"][0])) $iptc_info['iptc:description'] = trim ($iptc["2#120"][0]);
            else $iptc_info['iptc:description'] = "";
            if (isset ($iptc["2#122"][0])) $iptc_info['iptc:description_author'] = trim ($iptc["2#122"][0]);
            else $iptc_info['iptc:description_author'] = "";
            if (isset ($iptc["2#130"][0])) $iptc_info['iptc:image_type'] = trim ($iptc["2#130"][0]);
            else $iptc_info['iptc:image_type'] = "";
            if (isset ($iptc["2#131"][0])) $iptc_info['iptc:image_orientation'] = trim ($iptc["2#131"][0]);
            else $iptc_info['iptc:image_orientation'] = "";
            if (isset ($iptc["2#135"][0])) $iptc_info['iptc:language'] = trim ($iptc["2#135"][0]);
            else $iptc_info['iptc:language'] = "";
            
            if (!empty ($iptc_info['iptc:charset']))
            {
              $charset_source = iptc_getcharset ($iptc_info['iptc:charset']);
            }
            else $charset_source = "";        

            // inject meta data based on mapping
            reset ($mapping);
            
            foreach ($mapping as $key => $text_id)
            {
              // only for IPTC tags
              if (substr_count ($key, "iptc:") > 0 && $text_id != "")
              {           
                if ($iptc_info[$key] != "")
                {
                  // remove tags
                  $iptc_info[$key] = strip_tags ($iptc_info[$key]);
                  
                  // importing data from some Mac applications, they may put chr(213) into strings to access a closing quote character.
                  // this prints as a captial O with a tilde above it in a web browser or on Windows. 
                  $iptc_info[$key] = str_replace (chr(213), "'",  $iptc_info[$key]);      
                  
                  // convert string since IPTC supports different charsets      
                  if ($charset_source != "" && $charset_dest != "" && $charset_source != $charset_dest)
                  {
                    $iptc_info[$key] = convertchars ($iptc_info[$key], $charset_source, $charset_dest);
                  }
                  elseif ($charset_source == false && $charset_dest == "UTF-8")
                  {
                    // encode to UTF-8
                    if (!is_utf8 ($iptc_info[$key])) $iptc_info[$key] = utf8_encode ($iptc_info[$key]);              
                  }

                  // html encode string
                  $iptc_info[$key] = html_encode ($iptc_info[$key], $charset_dest);                      

                  // textnodes for search index in database
                  $text_array[$text_id] = $iptc_info[$key];

                  $containerdata_new = setcontent ($containerdata, "<text>", "<textcontent>", "<![CDATA[".$iptc_info[$key]."]]>", "<text_id>", $text_id);
            
                  if ($containerdata_new == false)
                  {
                    $containerdata_new = addcontent ($containerdata, $text_schema_xml, "", "", "", "<textcollection>", "<text_id>", $text_id);
                    $containerdata_new = setcontent ($containerdata_new, "<text>", "<textcontent>", "<![CDATA[".$iptc_info[$key]."]]>", "<text_id>", $text_id);
                    $containerdata_new = setcontent ($containerdata_new, "<text>", "<textuser>", $user, "<text_id>", $text_id);
                  }
            
                  if ($containerdata_new != false) $containerdata = $containerdata_new;
                  else return false;
                }
              }
            }
          }     
        }
        
        // ------------------- define and set image quality -------------------
        if ($mapping['hcms:quality'] != "")
        {      
          // get dpi from XMP tag
          $xres_array = getcontent ($mediadata, "<tiff:XResolution>");
      
          if ($xres_array != false)
          {
            if (substr_count ($xres_array[0], "/") == 1)
            {
              list ($x1, $x2) = explode ("/", $xres_array[0]);
              $xres = $x1 / $x2;
            }
            else $xres = $xres_array[0];
          }
          // get dpi from EXIF (only for JPEG and TIFF)
          elseif (exif_imagetype ($medialocation.$mediafile))
          {
            if (is_array ($exif_data))
            {
              reset ($exif_data);
              
              foreach ($exif_data as $key => $section)
              {
                if (is_array ($section))
                {
        	        foreach ($section as $name => $val)
                  {
                    if (strtolower($key) != "thumbnail" && strtolower($name) == "xresolution")
                    {
                      $xres = $val;
        
                      if (substr_count ($xres, "/") == 1)
                      {
                        list ($x1, $x2) = explode ("/", $xres);
                        $xres = $x1 / $x2;
                      }
                    }
                  }
            	  }
              }
            }
          }
      
          if ($xres != "")
          {
            if ($xres >= 300) $quality = "Print";
            else $quality = "Web";
      
            $text_array['Quality'] = $quality;
            $containerdata_new = setcontent ($containerdata, "<text>", "<textcontent>", "<![CDATA[".$quality."]]>", "<text_id>", $mapping['hcms:quality']);
      
            if ($containerdata_new == false)
            {
              $containerdata_new = addcontent ($containerdata, $text_schema_xml, "", "", "", "<textcollection>", "<text_id>", $mapping['hcms:quality']);
              $containerdata_new = setcontent ($containerdata_new, "<text>", "<textcontent>", "<![CDATA[".$quality."]]>", "<text_id>", $mapping['hcms:quality']);
              $containerdata_new = setcontent ($containerdata_new, "<text>", "<textuser>", $user, "<text_id>", $mapping['hcms:quality']);
            }
      
            if ($containerdata_new != false) $containerdata = $containerdata_new;
            else return false;
          }
        }

        // delete temp file
        if ($temp['result'] && $temp['created']) deletefile ($temp['templocation'], $temp['tempfile'], 0);

        // save container
        if ($containerdata != false)
        {
          if ($mgmt_config['db_connect_rdbms'] != "")
          {
            include_once ($mgmt_config['abs_path_cms']."database/db_connect/".$mgmt_config['db_connect_rdbms']);
            rdbms_setcontent ($container_id, $text_array, $user);
          }

          return savecontainer ($container, "work", $containerdata, $user);
        }
        else return false;
      }
      else return false;
    }
    else return false;
  }
  else return false;
}
?>