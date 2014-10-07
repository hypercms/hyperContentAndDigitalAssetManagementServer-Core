<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
 // ========================================= LOAD CONTENT ============================================

// ---------------------------------------------- getobjectcontainer ----------------------------------------------
// function: getobjectcontainer()
// input: publication [string], location [string], object [string], user [string]
// output: Content Container [XML]/false
// requires: config.inc.php

// description:
// loads the content container of a given object (page, component, folder)

function getobjectcontainer ($site, $location, $object, $user)
{
  global $mgmt_config;
  
  if (valid_publicationname ($site) && valid_locationname ($location) && $object != "" && valid_objectname ($user))
  {
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";
    
    // deconvert location
    if (@substr_count ($path, "%page%") > 0 || @substr_count ($path, "%comp%") > 0)
    {
      $cat = getcategory ($site, $location);
      $location = deconvertpath ($location, $cat);
    }
    
    // evaluate if object is a file or a folder
    if (@is_file ($location.$object))
    {   
      $object = correctfile ($location, $object, $user);   
    }
    elseif (@is_dir ($location.$object))
    {
      $location = $location.$object."/";
      $object = ".folder";
    }
    
    // load object file
    $data = loadfile ($location, $object);
    
    if ($data != "") $container = getfilename ($data, "content");
    else $container = false;
    
    // load container
    if ($container != false)
    {
      $container_id = substr ($container, 0, strpos ($container, ".xml"));
      $data = loadcontainer ($container, "work", $user);
      if ($data != false && $data != "") return $data;
      else return false;
    }    
  }
  else return false;
}

// ---------------------------------------------- getcontainer ----------------------------------------------
// function: getcontainer()
// input: container name or container ID, container type [published, work]
// output: Contant Container [XML]/false
// requires: config.inc.php

// description:
// obsolete function used as shell for loadcontainer function without loading locked containers 

function getcontainer ($containerid, $type)
{
  global $mgmt_config;
  
  return loadcontainer ($containerid, $type, "");
}
 
// ========================================= GET FUNCTIONS ============================================

// --------------------------------------- getcontainername -------------------------------------------
// function: getcontainername()
// input: container name (e.g. 0000112.xml.wrk)
// output: Array with file name of the working content container (locked or unlocked!) and username if locked
// requires: config.inc.php to be loaded

function getcontainername ($container)
{
  global $mgmt_config;
  
  if ($container != "" && strpos ($container, ".xml") > 0)
  {
    $container_id = substr ($container, 0, strpos ($container, ".xml"));
    
    if (strpos ($container, ".wrk") > 0)
    {
      $container = substr ($container, 0, strpos ($container, ".wrk"));
      $containerwrk = $container.".wrk";
    }
    else $containerwrk = $container.".wrk";
    
    $user = "";
    $location = getcontentlocation ($container_id, 'abs_path_content');

    // container exists and is not locked
    if (@is_file ($location.$containerwrk))
    {
      // return result
      $result['result'] = true;
      $result['container'] = $containerwrk;
      $result['user'] = $user;    
      return $result;
    }
    // container exists and is locked by current user
    elseif (@is_file ($location.$containerwrk.".@".$_SESSION['hcms_user']))
    {
      // return result
      $result['result'] = true;
      $result['container'] = $containerwrk.".@".$_SESSION['hcms_user'];
      $result['user'] = $_SESSION['hcms_user'];    
      return $result;
    }
    // container is locked or does not exist
    else
    {
      $dir = dir ($location);

      if ($dir)
      {
        while ($entry = $dir->read())
        {
          // if locked working container was found
          if (preg_match ("/$container.wrk.@/", $entry))
          { 
            $containerwrk = $entry;
            $user = substr ($entry, strpos ($entry, "wrk.@") + 5);
            
            $result['result'] = true;
            $result['container'] = $containerwrk;
            $result['user'] = $user;
            
            $dir->close();
            
            // return result 
            return $result;
            break;
          }   
        }
        
        $dir->close();
        
        // return result
        $result['result'] = false;
      }
      else 
      {
        // return result
        $result['result'] = false;    
      }
      
      return $result;
    }
  }
  else
  {
    $result['result'] = false;
    return $result;
  }
}

// ------------------------------------- getlocationname ------------------------------------------

// function: getlocationname()
// input: publication name, location path (as absolute path or converted path), category [page,comp], source for name [path,name]
// output: location with readable names instead of file names / false on error

function getlocationname ($site, $location, $cat, $source="path")
{
  global $mgmt_config, $lang, $lang_codepage;
  
  if (valid_locationname ($location))
  {
    // load config is not available
    if (valid_publicationname ($site) && (!isset ($mgmt_config[$site]) || !is_array ($mgmt_config[$site])) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }
    
    // check for .folder and remove it
    if (getobject ($location) == ".folder") $location = getlocation ($location);

    // input is converted location
    if (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1 && is_array ($mgmt_config[$site]))
    {
      if ($site == "") $site = getpublication ($location);      
      if ($cat == "") $cat = getcategory ($site, $location);
      
      $location_esc = $location;
      $location_abs = deconvertpath ($location, "file");
    }
    // input is not a converted location and publication name is valid
    elseif (valid_publicationname ($site) && is_array($mgmt_config[$site]))
    {
      if ($cat == "") $cat = getcategory ($site, $location);
      
      $location_esc = convertpath ($site, $location, $cat);
      $location_abs = $location;
    }
    else return false;
   
    if (valid_publicationname ($site) && $location_esc != "" && $location_abs != "")
    {
      // get names from name file pointer
      if ($source == "name")
      {
        if ($cat == "page") $root_abs = $mgmt_config[$site]['abs_path_page'];
        elseif ($cat == "comp") $root_abs = $mgmt_config['abs_path_comp'];
            
        // loop while operating in home folder
        $location_folder = $location_abs;
        $location_name = "";
  
        while (substr_count ($root_abs, $location_folder) < 1)
        {
          // read file and define folder name
          $objectdata = loadfile ($location_folder, ".folder");
          if ($objectdata != false) $foldername = getfilename ($objectdata, "name");
          if ($foldername == false || $foldername == "") $foldername = getobject ($location_folder); 
          // define location name
          $location_name = $foldername."/".$location_name;
          // reset name
          $foldername = "";
          // get parent location   
          $location_folder = getlocation ($location_folder);
        }
        
        if ($cat == "page") $location_name = "/".$site."/".$location_name;
        elseif ($cat == "comp") $location_name = "/".$location_name;        
      }
      // get names from decoding the file path
      else
      {
        if ($cat == "page") $root_abs = "%page%";
        elseif ($cat == "comp") $root_abs = "%comp%";
        else $root_abs = "";
        
        if ($root_abs != "") $location_name = str_replace ($root_abs, "", $location_esc);
        $location_name = specialchr_decode ($location_name);
      }
      
      if ($location_name != "") return $location_name;
      else return false;
    }
    else return false;
  }
  else return false;
}

// --------------------------------------- getthemelocation -------------------------------------------
// function: getthemelocation ()
// input: theme name (optional)
// output: path to theme / false

// description:
// returns the absolute path (URL) to the theme (css and images).

function getthemelocation ($theme="")
{
  global $mgmt_config;

  // input parameter
  if (valid_objectname ($theme))
  {
    return cleandomain ($mgmt_config['url_path_cms']."theme/".$theme."/");
  }
  // theme path from session
  elseif (!empty ($_SESSION['hcms_themepath']))
  {
    return $_SESSION['hcms_themepath'];
  }
  // theme name from session
  elseif (!empty ($_SESSION['hcms_themename']))
  {
    return cleandomain ($mgmt_config['url_path_cms']."theme/".$_SESSION['hcms_themename']."/");
  }    
  // from config 
  elseif (valid_objectname ($mgmt_config['theme']))
  {
    return cleandomain ($mgmt_config['url_path_cms']."theme/".$mgmt_config['theme']."/");
  }
  // default theme
  else
  {
    return cleandomain ($mgmt_config['url_path_cms']."theme/standard/");
  }
}

// ---------------------- getcategory -----------------------------
// function: getcategory()
// input: location path
// output: category ['page, comp'] / false on error
// requires: config.inc.php

// description:
// evaluates the category ['page, comp'] of a location.

function getcategory ($site, $location)
{
  global $mgmt_config, $publ_config;

  if ($location != "" && is_array ($mgmt_config))
  {
    // define category
    if (@substr_count ($location, "%page%") >= 1)
    {
      $cat = "page";
    }
    elseif (@substr_count ($location, "%comp%") >= 1)
    {
      $cat = "comp";
    }  
    elseif (@substr_count ($location, "://") == 0 && valid_publicationname ($site))
    {
      if (!empty ($mgmt_config['abs_path_comp']) && !empty ($mgmt_config[$site]['abs_path_page']) && strlen ($mgmt_config['abs_path_comp']) > strlen ($mgmt_config[$site]['abs_path_page']))
      {
        if (@substr_count ($location, $mgmt_config['abs_path_comp']) == 1) $cat = "comp";
        elseif (@substr_count ($location, $mgmt_config[$site]['abs_path_page']) == 1) $cat = "page";
      }  
      else
      {
        if (@substr_count ($location, $mgmt_config[$site]['abs_path_page']) == 1) $cat = "page";            
        elseif (@substr_count ($location, $mgmt_config['abs_path_comp']) == 1) $cat = "comp";
      }
    }
    elseif (@substr_count ($location, "://") > 0 && valid_publicationname ($site))
    {
      if (!empty ($mgmt_config['abs_path_comp']) && !empty ($mgmt_config[$site]['abs_path_page']) && strlen ($mgmt_config['url_path_comp']) > strlen ($mgmt_config[$site]['url_path_page']))
      {
        if (@substr_count ($location, $mgmt_config['url_path_comp']) == 1) $cat = "comp";
        elseif (@substr_count ($location, $mgmt_config[$site]['url_path_page']) == 1) $cat = "page";
      }  
      else
      {
        if (@substr_count ($location, $mgmt_config[$site]['url_path_page']) == 1) $cat = "page";            
        elseif (@substr_count ($location, $mgmt_config['url_path_comp']) == 1) $cat = "comp";
      }
      
      if (!isset ($cat))
      {
        if (!is_array ($publ_config) && valid_publicationname ($site) && @is_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"))
        {
          // load ini
          $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini");
        }
         
        if (!empty ($url_publ_comp) && !empty ($mgmt_config[$site]['url_publ_page']) && strlen ($url_publ_comp) > strlen ($publ_config['url_publ_page']))
        {
          if (@substr_count ($location, $publ_config['url_publ_comp']) == 1) $cat = "comp";
          elseif (@substr_count ($location, $publ_config['url_publ_page']) == 1) $cat = "page";
        }  
        else
        {
          if (@substr_count ($location, $publ_config['url_publ_page']) == 1) $cat = "page";            
          elseif (@substr_count ($location, $publ_config['url_publ_comp']) == 1) $cat = "comp";
        }
      } 
    }
    else return false;
  }
  else return false;
  
  if (!empty ($cat) && ($cat == "page" || $cat == "comp")) return $cat;
  else return false;
}

// ---------------------- getpublication -----------------------------
// function: getpublication()
// input: converted location path
// output: publication name

// description:
// extract the publication name of a location path.

function getpublication ($path)
{
  if ($path != "")
  {
    // extract publication from the converted path (first found path entry only!)
    if (@substr_count ($path, "%page%") > 0) $root_var = "%page%/";
    elseif (@substr_count ($path, "%comp%") > 0) $root_var = "%comp%/";
    elseif (@substr_count ($path, "%media%") > 0) $root_var = "%media%/";
    else $root_var = false;
  
    if ($root_var != false)
    {
      $pos1 = @strpos ($path, $root_var) + strlen ($root_var);
      
      if ($pos1 != false) $pos2 = @strpos ($path, "/", $pos1);
      else $pos2 = false;
      
      if ($pos1 != false && $pos2 != false) $site = @substr ($path, $pos1, $pos2-$pos1);
      else $site = false;
      
      return $site;
    }
    else return false;
  }
  else return false;
}

// ------------------------- getlocation ---------------------------------
// function: getlocation()
// input: location path
// output: location (without object or folder)

// description:
// extract the location excluding object or folder of a location path.

function getlocation ($path)
{
  if ($path != "")
  {
    // if object has no slash at the end
    if (substr ($path, -1) != "/")
    {
      $location = substr ($path, 0, strrpos ($path, "/")+1);
    }
    // else remove slash
    else
    {
      // remove slash at the end of the objectpath string
      $location = substr ($path, 0, strlen ($path)-1);
      $location = substr ($location, 0, strrpos ($location, "/")+1);          
    }
    
    return $location;
  }
  else return false;
}

// ------------------------- getobject ---------------------------------
// function: getobject()
// input: location path
// output: object or folder name

// description:
// extract the object or folder of a location path.

function getobject ($path)
{
  if ($path != "")
  {
    // if given input is a path
    if (substr_count ($path, "/") > 0)
    {
      // if path has no slash at the end
      if (substr ($path, -1) != "/")
      {
        $object = substr ($path, strrpos ($path, "/") + 1);
      }
      // else remove slash
      else
      {
        // remove slash at the end of the objectpath string
        $path = substr ($path, 0, strlen ($path) - 1);
        $object = substr ($path, strrpos ($path, "/") + 1);          
      }
      
      // if path holds parameters (URL)
      if (strpos ($object, "?") > 0)
      {
        $object = substr ($object, 0, strrpos ($object, "?"));
      }
    }
    else $object = $path;
    
    return $object;
  }
  else return false;
}

// ---------------------- getmediacontainername -----------------------------
// function: getmediacontainername()
// input: file name
// output: container name / false on error

// description:
// extract the container name out of a multimedia file name by using the hcm-ID

function getmediacontainername ($file)
{
  if (valid_objectname ($file))
  {
    $startpos = strrpos ($file, "_hcm") + 4;
    
    if (strpos ($file, ".", $startpos) > 0) $endpos = strpos ($file, ".", $startpos);
    else $endpos = strlen ($file);
    
    $length = $endpos - $startpos;
    $id = substr ($file, $startpos, $length);
    
    if (is_int (intval ($id))) return $id.".xml";
    else return false;
  }
  else return false;
}

// ---------------------- getmediacontainerid -----------------------------
// function: getmediacontainernid()
// input: media file name
// output: container ID / false on error

// description:
// extract the container ID out of a multimedia file name by using the hcms-ID

function getmediacontainerid ($file)
{
  if (valid_objectname ($file) && strpos ("_".$file, "_hcm") > 0)
  {
    $startpos = strrpos ($file, "_hcm") + 4;
    
    if (strpos ($file, ".", $startpos) > 0) $endpos = strpos ($file, ".", $startpos);
    else $endpos = strlen ($file);
    
    $length = $endpos - $startpos;
    $id = substr ($file, $startpos, $length);
    
    if (is_int (intval ($id))) return $id;
    else return false;
  }
  else return false;
}
  
// ---------------------- getfileinfo -----------------------------
// function: getfileinfo()
// input: publication, file name incl. extension, category [page,comp]
// output: array/false

// description:
// defines file properties based on the file extension and returns file info in an array:
//    $result['file']: file name without hypercms management extension
//    $result['name']: readable file name without hypercms management extension
//    $result['filename']: file name without file extensions
//    $result['icon']: file name of the file icon
//    $result['icon_large']: file name of the large file icon
//    $result['type']: file type
//    $result['ext']: file extension incl. dot in lower case
//    $result['published']: if file published = true else = false

function getfileinfo ($site, $file, $cat)
{
  global $mgmt_config;
  
  if ($file != "" && (valid_publicationname ($site) || ($cat == "page" || $cat == "comp")))
  {
    include ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
    
    // if file has an extension or holds a path
    if (@substr_count ($file, ".") > 0 || substr_count ($file, "/") > 0)
    {            
      // get the file extension of the file
      $file_ext_orig = strrchr ($file, ".");
      $file_ext = strtolower ($file_ext_orig);

      // CASE: folder 
      if ($file_ext == ".folder" || (substr_count ($file, "/") > 0 && is_dir (deconvertpath ($file, "file"))))
      {
        // get folder name from location (location required!) 
        if (substr_count ($file, "/") > 0)
        {
          // add / if missing
          if ($file_ext != ".folder" && substr ($file, -1) != "/") $file = $file."/";
          $location_name = substr ($file, 0, strrpos ($file, "/"));
          $folder_name = substr ($location_name, strrpos ($location_name, "/") + 1);
        }
        else $folder_name = $file;
        
        if ($cat == "") $cat = getcategory ($site, $file);  
      
        $file_name = $folder_name;
        $file_nameonly = $folder_name;
        
        if ($cat == "page") $file_icon = "folder_page.gif";
        elseif ($cat == "comp") $file_icon = "folder_comp.gif";
        else $file_icon = "folder.gif";
        
        if ($cat == "page") $file_icon_large = "folder_page.png";
        elseif ($cat == "comp") $file_icon_large = "folder_comp.png";
        else $file_icon_large = "folder.png";
        
        $file_type = "Folder";
        $file_published = true;
      }
      // CASE: file
      else
      {
        // if file holds a path
        if (@substr_count ($file, "/") > 0) $file = getobject ($file);
        
        // unpublished objects 
        if ($file_ext == ".off")
        {
          $file_name = substr ($file, 0, strlen ($file)-4);
          // get file name without extensions
          $file_nameonly = strrev (substr (strstr (strrev ($file_name), "."), 1));
          // get file extension of file name minus .off
          $file_ext = strtolower (strrchr ($file_name, "."));
          $file_published = false;
        }
        // published objects
        else
        {
          $file_name = $file; 
          // get file name without extension
          $file_nameonly = strrev (substr (strstr (strrev ($file), "."), 1));
          $file_published = true;
        }
        
        // MS Word
        if ($file_ext == ".doc" || $file_ext == ".docx" || $file_ext == ".docm" || $file_ext == ".dot" || $file_ext == ".dotx")
        {
          $file_icon = "file_doc.gif";
          $file_icon_large = "file_doc.png";
          $file_type = "MS Word";
        }
        // MS Powerpoint
        elseif ($file_ext == ".ppt" || $file_ext == ".pptx" || $file_ext == ".pps" || $file_ext == ".ppsx" || $file_ext == ".pot" || $file_ext == ".potm" || $file_ext == ".potx")
        {
          $file_icon = "file_ppt.gif";
          $file_icon_large = "file_ppt.png";
          $file_type = "MS Powerpoint";
        }
        // MS Excel
        elseif ($file_ext == ".xls" || $file_ext == ".xlsx" || $file_ext == ".xlst" || $file_ext == ".xlsm" ||$file_ext == ".csv")
        {
          $file_icon = "file_xls.gif";
          $file_icon_large = "file_xls.png";
          $file_type = "MS Excel";
        }
        // Adobe PDF
        elseif ($file_ext == ".pdf")
        {
          $file_icon = "file_pdf.gif";
          $file_icon_large = "file_pdf.png";
          $file_type = "Adobe Acrobat";
        }
        // Open Office Text
        elseif ($file_ext == ".odt" || $file_ext == ".fodt")
        {
          $file_icon = "file_odt.gif";
          $file_icon_large = "file_odt.png";
          $file_type = "OO Text";
        }
        // Open Office Spreadsheet
        elseif ($file_ext == ".ods" || $file_ext == ".fods")
        {
          $file_icon = "file_ods.gif";
          $file_icon_large = "file_ods.png";
          $file_type = "OO Spreadsheet";
        }
        // Open Office Presentation
        elseif ($file_ext == ".odp" || $file_ext == ".fodp")
        {
          $file_icon = "file_odp.gif";
          $file_icon_large = "file_odp.png";
          $file_type = "OO Presentation";
        }                      
        // text based documents in proprietary format    
        elseif (@substr_count ($hcms_ext['bintxt'].".", $file_ext.".") > 0)
        {
          $file_icon = "file_txt.gif";
          $file_icon_large = "file_txt.png";
          $file_type = "Text";
        }
        // text based documents in clear text  
        elseif (@substr_count ($hcms_ext['cleartxt'].".", $file_ext.".") > 0)
        {
          $file_icon = "file_txt.gif";
          $file_icon_large = "file_txt.png";
          $file_type = "Text";
        }        
        // image files 
        elseif (@substr_count ($hcms_ext['image'].".", $file_ext.".") > 0)
        {
          $file_icon = "file_image.gif";
          $file_icon_large = "file_image.png";
          $file_type = "Image";
        }
        // Adobe Flash
        elseif (@substr_count ($hcms_ext['flash'].".", $file_ext.".") > 0)
        {
          $file_icon = "file_flash.gif";
          $file_icon_large = "file_flash.png";
          $file_type = "Macromedia Flash";
        }
        // Audio files
        elseif (@substr_count ($hcms_ext['audio'].".", $file_ext.".") > 0)
        {
          $file_icon = "file_audio.gif";
          $file_icon_large = "file_audio.png";
          $file_type = "Audio";
        }
        // Apple Quicktime files
        elseif ($file_ext == ".qt" || $file_ext == ".qtl" || $file_ext == ".mov")
        {
          $file_icon = "file_qt.gif";
          $file_icon_large = "file_qt.png";
          $file_type = "Quicktime Video";
        }
        // Video files  
        elseif (@substr_count ($hcms_ext['video'].".", $file_ext.".") > 0)
        {
          $file_icon = "file_mpg.gif";
          $file_icon_large = "file_mpg.png";
          $file_type = "Video";
        }
        // Compressed files
        elseif (@substr_count ($hcms_ext['compressed'].".", $file_ext.".") > 0)
        {
          $file_icon = "file_zip.gif";
          $file_icon_large = "file_zip.png";
          $file_type = "compressed";
        }
        // CMS template files
        elseif (@substr_count ($hcms_ext['template'].".", $file_ext.".") > 0)
        {
          if (@substr_count ($file, ".page.tpl"))
          {
            $file_icon = "template_page.gif";
            $file_icon_large = "template_page.gif";
            $file_type = "Page Template";
          }
          elseif (@substr_count ($file, ".comp.tpl"))
          {
            $file_icon = "template_comp.gif";
            $file_icon_large = "template_comp.gif";
            $file_type = "Component Template";
          }
          elseif (@substr_count ($file, ".meta.tpl"))
          {
            $file_icon = "template_media.gif";
            $file_icon_large = "template_media.gif";
            $file_type = "Meta Data Template";
          }        
          elseif (@substr_count ($file, ".inc.tpl"))
          {
            $file_icon = "template_comp.gif";
            $file_icon_large = "template_comp.gif";
            $file_type = "Template Component";
          }          
              
          $file_type = "Template";
        }
        // CMS files
        elseif (@substr_count ($hcms_ext['cms'].".", $file_ext.".") > 0)
        {
          if ($cat == "page")
          {
            $file_icon = "file_page.gif";
            $file_icon_large = "file_page.png";
            $file_type = "Page";
          }
          elseif ($cat == "comp")
          {
            $file_icon = "file_comp.gif";
            $file_icon_large = "file_comp.png";
            $file_type = "Component";      
          }
          else
          {
            $file_icon = "file_page.gif";
            $file_icon_large = "file_page.png";
            $file_type = "Page";        
          }
        }  
        // all other files    
        else
        {
          $file_icon = "file_binary.gif";
          $file_icon_large = "file_binary.png";
          $file_type = substr ($file_ext, 1);
        }
      } 
    }
    // no extension available
    else 
    {
      // if file holds a path
      if (@substr_count ($file, "/") > 0) $file = getobject ($file);
          
      $file_name = $file;
      $file_nameonly = $file;
      $file_icon = "file_binary.gif";
      $file_icon_large = "file_binary.png";
      $file_type = "unknown";
      $file_ext = "";
      $file_published = true;
    }
    
    // set result array
    $result['file'] = $file_name;
    $result['name'] = specialchr_decode ($file_name);
    $result['filename'] = $file_nameonly;
    $result['icon'] = $file_icon;
    $result['icon_large'] = $file_icon_large;
    $result['type'] = $file_type;
    $result['ext'] = $file_ext;
    $result['published'] = $file_published;
  }
  else $result = false;
      
  return $result;
}

// ---------------------------------------------- getobjectinfo ----------------------------------------------
// function: getobjectinfo()
// input: publication [string], location [string], object [string], user [string] (optional)
// output: result array / false on error
// requires: config.inc.php

// description:
// get's all file pointers(container, media) and object name from object file

function getobjectinfo ($site, $location, $object, $user="sys")
{
  global $mgmt_config;
  
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($object))
  {
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";
    
    // deconvert location
    if (@substr_count ($location, "%page%") > 0 || @substr_count ($location, "%comp%") > 0)
      $location = deconvertpath ($location, "file");
    
    // evaluate if object is a file or a folder
    if (is_dir ($location.$object))
    {
      $location = $location.$object."/";
      $object = ".folder";
    }
    elseif (!is_file ($location.$object))
    {   
      $object = correctfile ($location, $object, $user);   
    }
    
    // load object file
    $data = loadfile ($location, $object);
    
    if ($data != "")
    {
      $result['template'] = getfilename ($data, "template");
      $result['content'] = getfilename ($data, "content");
      $result['media'] = getfilename ($data, "media");
      $result['name'] = getfilename ($data, "name");      
      $result['container_id'] = substr ($result['content'], 0, strpos ($result['content'], ".xml"));
      
      if (is_array ($result)) return $result;
      else return false;
    }
    else return false;
  }
  else return false;
}

// ---------------------- getfilesize -----------------------------
// function: getfilesize()
// input: path to file or directory, max. file count in file system
// output: result array with file size in kB and file count / false on error

// Attention!
// this function won't give you a proper result of the file size of multimedia components, if there is no DB in use.

function getfilesize ($file, $maxcount=100)
{
  global $mgmt_config, $site;
  
  if (valid_locationname ($file))
  {
    // get file size from DB
    if ($mgmt_config['db_connect_rdbms'] != "")
    {
      $file = convertpath ($site, $file, "comp");          
      // get file size
      return rdbms_getfilesize ("", $file);
    }
    // get file size from file system (won't work on multimedia components!)
    else
    {
      // deconvert path 
      if (substr_count ($file, "%page%") == 1 || substr_count ($file, "%comp%") == 1)
      {
        $file = deconvertpath ($file, "file");
      }
      
      // cut off .folder
      $object = getobject ($file);
      if ($object == ".folder") $file = getlocation ($file);
      
      if (is_file ($file))
      {
        // get file size in kB
        $size = filesize ($file) / 1024;
        return array('filesize'=>$size, 'count'=>0);
      }
      
      if (is_dir ($file) && $dir = opendir ($file))
      {
        $size = 0;
        $n = 0;
        
        // add slash if not present at the end
        if (substr ($file, -1) != "/") $file = $file."/";           

        while (($item = readdir ($dir)) !== false && $n <= $maxcount)
        {
          if ($item == "." || $item == ".." || $item == ".folder") continue;
          $n++;
          $data = getfilesize ($file.$item);
          $size += $data['filesize'];
          $n += $data['count'];
        }
        
        closedir ($dir);
        
        return array('filesize'=>$size,'count'=>$n);
      }
      
      return array('filesize'=>0,'count'=>0);
    }
  }
  else return false;
}

// ---------------------- getmimetype -----------------------------
// function: getmimetype()
// input: file name incl. extension  
// output: mime_type

// description:
// gets the mime-type of the file of its extension.
// if file has a version file extension the next file extension will be used.

function getmimetype ($file)
{
  global $mgmt_config;
  
  if (valid_objectname ($file))
  {
    include ($mgmt_config['abs_path_cms']."include/format_mime.inc.php");
    
    // get the file extension of the file
    $file_ext = strtolower (strrchr ($file, "."));
    
    // avoid version file extension
    if (substr_count ($file_ext, "v_") == 1)
    {
      $file = substr ($file, 0, strrchr ($file, "."));
      $file_ext = strtolower (strrchr ($file, "."));
    }
    
    // check if mime-type for the given extension exists
    if ($mimetype[$file_ext] != "") return $mimetype[$file_ext];
    else return "application/octetstream";
  }
  else return "";
}

// ---------------------- getfiletype -----------------------------
// function: getfiletype()
// input: file extension
// output: file type to be saved in database

function getfiletype ($file_ext)
{
  global $mgmt_config, $hcms_ext; 
  
  if (!is_array ($hcms_ext)) require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
  
  if ($file_ext != "" && is_array ($hcms_ext))
  {
    $file_ext = strtolower ($file_ext);
    
    if (substr_count ($hcms_ext['audio'], $file_ext) > 0) $filetype = "audio";
    elseif (substr_count ($hcms_ext['bintxt'].$hcms_ext['cleartxt'], $file_ext) > 0) $filetype = "document";
    elseif (substr_count ($hcms_ext['cms'].$hcms_ext['cleartxt'], $file_ext) > 0) $filetype = "text";
    elseif (substr_count ($hcms_ext['image'], $file_ext) > 0) $filetype = "image";
    elseif (substr_count ($hcms_ext['video'], $file_ext) > 0) $filetype = "video";
    elseif (substr_count ($hcms_ext['flash'], $file_ext) > 0) $filetype = "flash";
    elseif (substr_count ($hcms_ext['compressed'], $file_ext) > 0) $filetype = "compressed";
    elseif (substr_count ($hcms_ext['binary'], $file_ext) > 0) $filetype = "binary";
    else $filetype = "unknown";
    
    return $filetype;
  }
  elseif ($file_ext == "")
  {
    return "unknown";
  }
  else return false;
}

// ---------------------- getvideoinfo -----------------------------
// function: getvideoinfo()
// input: path to video file
// output: video file information as result array / false on error

function getvideoinfo ($mediafile)
{
  global $mgmt_config, $mgmt_mediapreview;
  
  if ($mediafile != "" && @is_file ($mediafile))
  {
  	$dimensionRegExp = "/, ([0-9]+x[0-9]+)/";
  	$durationRegExp = "/Duration: ([0-9\:\.]+)/i";
  	$bitRateRegExp = "/bitrate: ([0-9]+ [a-z]+\/s)/i";
    
    $dimension = "";
    $width = "";
    $height = ""; 
    $duration = "";   
    $bitrate = "";
    $imagetype = "";
    
    // get video file size in MB
    $filesize = (int)round (@filesize ($mediafile) / 1024 / 1024, 0);
    
    // file extension
    $file_ext = strtolower (strrchr ($mediafile, ".")); 
  
    foreach ($mgmt_mediapreview as $mediapreview_ext => $mediapreview)
    {
      // check file extension
      if ($file_ext != "" && substr_count ($mediapreview_ext.".", $file_ext.".") > 0)
      {  
        $return = 1;
        $metadata = array();
          
        // get info from video file using FFMPEG
      	$cmd = $mgmt_mediapreview[$mediapreview_ext]." -i \"".$mediafile."\" -y -f rawvideo -vframes 1 /dev/null 2>&1";
        exec ($cmd, $metadata, $return); 
        
        // parsing the values
        if ($return == 0)
        {
          // video dimension in pixels
    			$matches = array();
          
    			if (preg_match ($dimensionRegExp, implode ("\n", $metadata), $matches))
          {
    				$dimension = $matches[1];
            list ($width, $height) = explode ("x", $dimension);
                  
          	// set 'portrait', 'landscape' or 'square' for the image type
          	if ($width > $height) $imagetype = "landscape";
          	elseif ($height > $width) $imagetype = "portrait";
          	elseif ($height == $width) $imagetype = "square";      
    			}
          
          // video duration in hours:minutes:seconds
    			$matches = array();
          
    			if (preg_match ($durationRegExp, implode ("\n", $metadata), $matches))
          {
            if (strpos ($matches[1], ".") > 6) $matches[1] = substr ($matches[1], 0, -3);
    				$duration = $matches[1];
    			}
          
          // video bitrate in kB/s
    			$matches = array();
          
    			if (preg_match ($bitRateRegExp, implode ("\n", $metadata), $matches))
          {
    				$bitrate = $matches[1];
    			}
        }
      }
    }
    
    // return result 
    $result = array();
    $result['filesize'] = $filesize;
    $result['dimension'] = $dimension;
    $result['width'] = $width;
    $result['height'] = $height;
    if ($height > 0) $result['ratio'] = $width / $height;
    else $result['ratio'] = 0;      
    $result['duration'] = $duration;
    $result['bitrate'] = $bitrate;
    $result['imagetype'] = $imagetype;
    
    return $result;
  }
  else return false;
}

// --------------------------------------- getbrowserinfo -----------------------------------------------
// function: getbrowserinfo ()
// input: browser agent string (optional)
// output: client browser + version as array

function getbrowserinfo ($agent=null) 
{
  // declare known browsers to look for
  $known = array('msie', 'firefox', 'safari', 'webkit', 'opera', 'netscape', 'konqueror', 'gecko');

  // clean up agent and build regex that matches phrases for known browsers
  // (e.g. "Firefox/2.0" or "MSIE 6.0" (This only matches the major and minor
  // version numbers.  E.g. "2.0.0.6" is parsed as simply "2.0"
  $agent = strtolower ($agent ? $agent : $_SERVER['HTTP_USER_AGENT']);
  $pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9]+(?:\.[0-9]+)?)#';

  // find all phrases (or return empty array if none found)
  if (!preg_match_all ($pattern, $agent, $matches)) return array();

  // since some UAs have more than one phrase (e.g Firefox has a Gecko phrase,
  // Opera 7,8 have a MSIE phrase), use the last one found (the right-most one
  // in the UA).  That's usually the most correct.
  $i = count ($matches['browser'])-1;
  return array($matches['browser'][$i] => $matches['version'][$i]);
}

// ---------------------- getcontentlocation -----------------------------
// function: getcontentlocation()
// input: container id, type [url_path_content, abs_path_content]
// output: location of the container file / false on error

// description:
// gets the content location due to the given container id
// a split up of folders is necessary since the number of directories are limited by
// the filesystem, e.g. Linux ext3 is limited to 32000.

function getcontentlocation ($container_id, $type="abs_path_content")
{
  global $mgmt_config;
  
  if ($container_id != "" && ($type == "url_path_content" || $type == "abs_path_content") && is_array ($mgmt_config))
  {
    // directory block size of 10.000
    $limitbase = 10000;
    // max. 32000 subdirectories
    for ($i=0; $i<32000; $i++)
    {
      $limit = $limitbase * (1 + $i);

      if (intval ($container_id) < $limit)
      {
        if (!is_dir ($mgmt_config['abs_path_content'].$i)) @mkdir ($mgmt_config['abs_path_content'].$i, $mgmt_config['fspermission']);
        return $mgmt_config[$type].$i."/".$container_id."/";
      }
    }
  }
  else return false;
} 

// ---------------------- getmedialocation -----------------------------
// function: getmedialocation()
// input: publication, multimedia file name (including hcm-ID), type [url_path_media, abs_path_media, url_publ_media, abs_publ_media]
// output: location of the multimedia file / false on error

// description:
// gets the media repsitory location from $mgtm_config Array.
// the function supports up to 10 media repositories.
// any other rules for splitting the media files on multiple devices could be 
// implemented as well.

// include rule from external file (must return a value)   
if (@is_file ($mgmt_config['abs_path_data']."media/getmedialocation.inc.php"))
{
  include ($mgmt_config['abs_path_data']."media/getmedialocation.inc.php");
}

function getmedialocation ($site, $file, $type)
{
  global $mgmt_config, $publ_config;
  
  if (valid_objectname ($file) && $type != "" && is_array ($mgmt_config))
  {
    // management configuration
    if ($type == "url_path_media" || $type == "abs_path_media")
    {
      // multiple media harddisk support
      if (is_array ($mgmt_config[$type]))
      {
        // encoding algorithm
        $container_id = intval (getmediacontainerid ($file));
        $no = substr ($container_id, -1);
      
        if (function_exists ("getmedialocation_rule"))
        {
          $result = getmedialocation_rule ($site, $file, $type, $container_id);
          if ($result != "") return $result;
        }
        
        $hdarray_size = sizeof ($mgmt_config[$type]);
        
        if ($hdarray_size == 1) return $mgmt_config[$type][1];
        elseif ($hdarray_size  > 1) 
        {
          $j = 1;
          
          for ($i=1; $i<=10; $i++)
          {
            if (substr ($i, -1) == $no) return $mgmt_config[$type][$j];
            if ($j == $hdarray_size) $j = 1;
            else $j++;
          }
        }
      }
      // single media harddsik
      else return $mgmt_config[$type];
    }
    // publication configuration
    elseif ($type == "url_publ_media" || $type == "abs_publ_media")
    {
      if (valid_publicationname ($site) && !is_array ($publ_config))
      {
        $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"); 
        return $publ_config[$type];
      }
      elseif (is_array ($publ_config))
      {
        return $publ_config[$type];
      }
      else return false;
    }
    else return false;
  }
  else return false;
} 

// ---------------------- getlockedfileinfo -----------------------------
// function: getlockedfileinfo()
// input: location to file, file name
// output: Array holding file name incl. lock extension and user name / false on error

// description:
// finds the locked file and returns the name and user as array

function getlockedfileinfo ($location, $file)
{
  global $mgmt_config;
  
  if (valid_locationname ($location) && valid_objectname ($file) && is_dir ($location))
  {
    // file is locked
    if (!is_file ($location.$file))
    {
      $dir = dir ($location);
  
      if ($dir)
      {
        $result = array();
        
        while ($entry = $dir->read())
        {
          if (preg_match ("/".preg_quote ($file.".@")."/", $entry))
          {
            $result['file'] = $entry;
            $result['user'] = substr ($entry, strrpos ($entry, ".@") + 2);
            
            return $result;
          }   
        }
        
        $dir->close();
      }
      else return false;
    }
    // file exists (is not locked)
    else
    {
      $result['file'] = $file;
      $result['user'] = "";
      
      return $result;
    }
  }
  else return false; 
}

// ======================================== GET FILEPOINTER =====================================

// ------------------------------------------ getfilename ---------------------------------------
// function: getfilename()
// input: file content, hyperCMS tag name in page or component 
// output: file name

// description:
// extracts the file name of the hyperCMS content and template pointer tags 

function getfilename ($filedata, $tagname)
{
  if ($filedata != "" && $tagname != "")
  {
    // define comment tag for file pointers (changed since version 4.1 to <!-- pointer -->)
    if (strpos (strtolower ($filedata), "!-- hypercms:") > 0) 
    {
      $ctagbegin = "<!-- ";
      $ctagend = " -->";
    }
    else 
    {
      $ctagbegin = "<!";
      $ctagend = ">";
    }
      
    // find first positions of hyperCMS tag
    if (strpos (strtolower ($filedata), "hypercms:".strtolower ($tagname)." file=\"") > 0)
    {
      $len = strlen ($ctagbegin."hypercms:".$tagname." file=\"");
      $namestart = strpos (strtolower ($filedata), $ctagbegin."hypercms:".strtolower ($tagname)." file=\"") + $len;
      $nameend = strpos ($filedata, "\"".$ctagend, $namestart);
    }
    elseif (strpos (strtolower ($filedata), "hypercms:".strtolower ($tagname)." file = \"") > 0)
    {
      $len = strlen ($ctagbegin."hypercms:".$tagname." file = \"");
      $namestart = strpos (strtolower ($filedata), $ctagbegin."hypercms:".strtolower ($tagname)." file = \"") + $len;
      $nameend = strpos ($filedata, "\"".$ctagend, $namestart);
    }
    else $namestart = 0;
  
    if ($namestart > 0)
    {
      // get file name
      $filename = trim (substr ($filedata, $namestart, $nameend - $namestart));
      
      return $filename;
    }
    else return false;
  }
  else return false;
}

// ======================================== TAG HANDLING ============================================

// ----------------------------------------- gethypertag --------------------------------------------
// function: gethypertag()
// input: file content [string], full/partly hyperCMS tag name (with or without hyperCMS:) [string], offset position [integer] 
// output: full hyperCMS tag array [array]/false on error

// description:
// finds the hyperCMS tag start and end position 
// and returns an array of the whole tags including all information.
// offset value must be integer value and is used to skip search
// for hyperCMS tag till offset position of filedata.

function gethypertag ($filedata, $tagname, $offset=0)
{
  if ($filedata != "" && $tagname != "")
  {
    // add freespace at the beginning of filedata
    $filedata = " ".$filedata;
    
    // define full hyperCMS tag name, if not done by request
    if (@substr_count (strtolower ($tagname), "hypercms:") == 0) $tagname = "[hyperCMS:".$tagname;
    elseif (@substr_count (strtolower ($tagname), "hypercms:") == 1 && @substr_count (strtolower ($tagname), "[hypercms:") == 0) $tagname = "[".$tagname;
    
    // define offset if not set
    if ($offset == "") $offset = 0;
  
    if (@substr_count (strtolower ($filedata), strtolower ($tagname)) >= 1)
    {
      $endpos = 1;
    
      while ($endpos > 0 && $endpos != false)
      {
        // find start and end position of hyperCMS tag
        $startpos = strpos ($filedata, $tagname, $offset);
         
        if ($startpos != false) $endpos = strpos ($filedata, "]", $startpos);
        else $endpos = false;
  
        // get hyperCMS tag into array
        if ($startpos != false && $endpos != false && $endpos > $startpos)
        {
          $buffer = substr ($filedata, $startpos, $endpos + 1 - $startpos);
          if ($buffer != "") $hypertag[$startpos] = $buffer;
        }

        // define new offset
        $offset = $endpos;
      }
      
      // return hyperCMS tag array (the array key is the start position of the tag)
      if (is_array ($hypertag)) return $hypertag;
      else return false;    
    }
    else return false;
  }
  else return false;
}

// ------------------------- gethypertagname ---------------------------
// function: gethypertagname()
// input: full hyperCMS tag
// output: full hyperCMS tag name/false on error

// description:
// reads the name of the hyperCMS tag.

function gethypertagname ($tagdata)
{
  if ($tagdata != "")
  {
    $namestart = strpos ($tagdata, "hyperCMS:") + strlen ("hyperCMS:");
   
    if ($namestart != false) 
    {
      $nameend = strpos ($tagdata, " ");
      if ($nameend == false) $nameend = strpos ($tagdata, "]");
    }
    else $nameend = false;
   
    if ($namestart != false && $nameend != false && ($nameend > $namestart)) 
    $hypertagname = substr ($tagdata, $namestart, $nameend - $namestart);
   
    if ($hypertagname != false && $hypertagname != "") return $hypertagname;
    else return false;
  }
  else return false;
}

// ------------------------ gethtmltag ------------------------------
// function: gethtmltag()
// input: file content, full hyperCMS tag (or other identifier)
// output: full html tag/false on error

// description:
// finds the first html tag start and end position of a nested hyperCMS tag
// and returns the whole tag including all information.
// works also if other script tags are nested in the HTML-tag.
// this function is not case sensitive!

function gethtmltag ($filedata, $tag)
{
  if ($filedata != "" && $tag != "")
  {
    $filedata = "__".$filedata."__";
    $filedata_lower = strtolower ($filedata);
    $tag_lower = strtolower ($tag);
    $abslen = strlen ($filedata_lower);
  
    if (@substr_count ($filedata_lower, $tag_lower) > 0)
    {
      // find positions of hyperCMS tag
      $cmstagstart = strpos ($filedata_lower, $tag_lower);
      
      if ($cmstagstart > 0)
      {
        // find first position of HTML tag, direction: <-
        $pos = $cmstagstart + 1; // go one digit -> to include < in $tag
    
        if ($pos > 0)
        {
          $found = 0; // position of the start html tag
          $intag = 0; // is there a nested script tag in the html tag
          $tags = 0; // we allow max. 2 nested script tags to appear before we break the routine
           
          while ($pos > 0 && $tags < 3)
          {
            // found HTML tag
            if ($filedata_lower[$pos] == "<" && $intag == 0)
            {
              $found = $pos;
              break;
            }
            // if some script tag <...> is nested in an HTML tag
            elseif ($filedata_lower[$pos] == ">" && $intag == 0)
            {
              $intag++;
              $tags++;
            }
            elseif ($filedata_lower[$pos] == "<" && $intag > 0)
            {
              $intag--;
            }
      
            $pos--;
          }
          
          if ($found > 0) $tagstart = $found;
          else $tagstart = $cmstagstart;
        }
        else $tagstart = $cmstagstart;
        
        // define end positions of hyperCMS tag
        $cmstagend = $cmstagstart + strlen ($tag_lower);  
        
        // find last positions of HTML tag, direction: ->
        $pos = $cmstagend - 1; // go one digit <- to include > in $tag
    
        if ($pos > 0 && $pos < $abslen)
        {
          $found = 0; // position of the start html tag
          $intag = 0; // is there a nested script tag in the html tag
          $tags = 0; // we allow max. 2 nested script tags to appear before we break the routine
          
          while ($pos <= $abslen && $tags < 3)
          {
            // found HTML tag
            if ($filedata_lower[$pos] == ">" && $intag == 0)
            {
              $found = $pos;
              break;
            }
            // if some script tag <...> is nested in an HTML tag
            elseif ($filedata_lower[$pos] == "<" && $intag == 0)
            {
              $intag++;
              $tags++;
            }
            elseif ($filedata_lower[$pos] == ">" && $intag > 0)
            {
              $intag--;
            }
  
            $pos++;
          }
          
          if ($found > 0 && $found < $abslen) $tagend = $found;
          else $tagend = $cmstagend;   
        }
        else $tagend = $cmstagend;
       
        if ($tagstart > 0 && $tagend < $abslen)
        {
          // get full HTML tag
          $htmltag = substr ($filedata, $tagstart, $tagend + 1 - $tagstart);      
    
          if (strlen ($htmltag) < strlen ($tag)) return $tag;
          else return $htmltag;
        }
        else return false;
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// ------------------------ gethtmltags ------------------------------
// function: gethtmltags()
// input: file content, full hyperCMS tag or other identifier in html tag
// output: string from html tag start to end tag/false on error

// description:
// finds the nearest html tag start and end position of a nested hyperCMS tag
// and returns the whole tag including all information.
// this functions works also for html-tag pairs like <a href></a>, <div></div> and so on.

function gethtmltags ($filedata, $tag)
{
  if ($filedata != "" && $tag != "")
  {
    $filedata = "__".$filedata."__";
    $filedata_lower = strtolower ($filedata);
    $tag_lower = strtolower ($tag);
    $abslen = strlen ($filedata_lower);
  
    if (@substr_count ($filedata_lower, $tag_lower) > 0)
    {
      // find positions of hyperCMS tag
      $cmstagstart = strpos ($filedata_lower, $tag_lower);
      
      if ($cmstagstart > 0)
      {
        // find first position of HTML tag, direction: <-
        $pos = $cmstagstart + 1; // go one digit -> to include < in $tag
    
        if ($pos > 0)
        {
          $found = 0; // position of the start html tag
          $intag = 0; // is there a nested script tag in the html tag
          $tags = 0; // we allow max. 1 nested script tags to appear before we break the routine
           
          while ($pos > 0 && $tags < 2)
          {
            // found HTML tag
            if ($filedata_lower[$pos] == "<" && $intag == 0)
            {
              $found = $pos;
              break;
            }
            // if some script tag <...> is nested in an HTML tag
            elseif ($filedata_lower[$pos] == ">" && $intag == 0)
            {
              $intag++;
              $tags++;
            }
            elseif ($filedata_lower[$pos] == "<" && $intag > 0)
            {
              $found = $pos;
              $intag--;
              break;
            }
      
            $pos--;
          }
          
          if ($found > 0) $tagstart = $found;
          else $tagstart = $cmstagstart;
        }
        else $tagstart = $cmstagstart;
        
        // define end positions of hyperCMS tag
        $cmstagend = $cmstagstart + strlen ($tag_lower); 
        
        // find last positions of HTML tag, direction: ->
        $pos = $cmstagend - 1; // go one digit <- to include > in $tag
    
        if ($pos > 0 && $pos < $abslen)
        {
          $found = 0; // position of the start html tag
          $intag = 0; // is there a nested script tag in the html tag
          $tags = 0; // we allow max. 2 nested script tags to appear before we break the routine
          
          while ($pos < $abslen && $tags < 2)
          {
            // found HTML tag
            if ($filedata_lower[$pos] == ">" && $intag == 0)
            {
              $found = $pos;
              break;
            }
            // if some script tag <...> is nested in an HTML tag
            elseif ($filedata_lower[$pos] == "<" && $intag == 0)
            {
              $intag++;
              $tags++;
            }
            elseif ($filedata_lower[$pos] == ">" && $intag > 0)
            {
              $found = $pos;
              $intag--;
              break;
            }
  
            $pos++;
          }
          
          if ($found > 0 && $found < $abslen) $tagend = $found;
          else $tagend = $cmstagend;
        }
       
        if ($tagstart > 0 && $tagend < $abslen)
        {
          // get full HTML tag
          $htmltag = substr ($filedata, $tagstart, $tagend + 1 - $tagstart);      
    
          // extract html tag name
          $htmltagname = substr ($htmltag, $tagstart + 1, strpos ($htmltag, " ") - $tagstart - 1);
          $htmlendtag = "</".$htmltagname.">";
          
          // search for end tag 
          $tagend_new = strpos ($filedata, $htmlendtag, $tagend) + strlen ($htmlendtag);     
          
          if ($tagend_new > 0 && $tagend_new > $tagend) $htmltag = substr ($filedata, $tagstart, $tagend_new - $tagstart);   
    
          if (strlen ($htmltag) < strlen ($tag)) return $tag;
          else return $htmltag;
        }
        else return false;
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// ------------------------- getattribute --------------------------------
// function: getattribute()
// input: string including attributes, attribute name
// output: attribute value/false on error

// description:
// get the value of a certain attribute out of a string (...attributname=value....)

function getattribute ($string, $attribute)
{
  if ($string != "" && $attribute != "")
  {
    // URL based query string (GET)
    if (substr_count ("_".strip_tags ($string), ".php?") > 0)
    {
      $string = substr ($string, strpos ($string, ".php?") + 5);
      $string = html_decode ($string);
      parse_str ($string, $result);
      
      if (!empty ($result[$attribute]))
      {
        $value = $result[$attribute];
        // secure value
        $value = strip_tags ($value);
        $value = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $value);     
      }
      else $value = ""; 

      return $value;
    }
    // html/xml based attribute
    else
    {
      $string = html_decode ($string);

      // remove freespaces
      $freespace_array[0] = "   ";
      $freespace_array[1] = "  ";
      $freespace_array[2] = " ";
      
      foreach ($freespace_array as $freespace)
      {
        if (@substr_count (strtolower ($string), strtolower ($attribute).$freespace."=") > 0)
        {
          $string = str_replace ($attribute.$freespace."=", $attribute."=", $string);
        }
      }
    
      if (@substr_count (strtolower ($string), strtolower ($attribute)."=") > 0)
      {
        // get length of attribute name and add 1 for '='
        $attrlen = strlen ($attribute) + 1;
    
        while (@substr_count (strtolower ($string), strtolower ($attribute)."=") > 0)
        {
          // check if found attribute is not part of the name of another attribute
          $attr_seperator = substr ($string, strpos (strtolower ($string), strtolower ($attribute)."=") - 1, 1);
   
          if (strpos (strtolower ($string), strtolower ($attribute)."=") == 0 || $attr_seperator == "?" || $attr_seperator == "&" || $attr_seperator == " ")
          {
            // leave string as it is and exit loop
            $checkedstring = $string;
            break;
          }
          else
          {
            // cut off the first wrong occurence of the attribute name
            $string = substr (strstr ($string, $attribute."="), $attrlen);
          }
        }
  
        if (!empty ($checkedstring))
        {
          // cut off first part of the string till attribute value begins
          $checkedstring = substr (strstr ($checkedstring, $attribute."="), $attrlen);
    
          // " indicates end of attribute value
          if ($checkedstring[0] == "\"" && strpos (substr ($checkedstring, 1), "\"") >= 0)
          {
            // get the length of the value
            $vallen = strpos (substr ($checkedstring, 1), "\"") + 2;
          }  
          // ' indicates end of attribute value
          elseif ($checkedstring[0] == "'" && strpos (substr ($checkedstring, 1), "'") >= 0)
          {
            // get the length of the value
            $vallen = strpos (substr ($checkedstring, 1), "'") + 2;
          }  
          //  '&' indicates end of attribute value
          elseif (strpos ($checkedstring, "&") > 0)
          {
            // get the length of the value
            $vallen = strpos ($checkedstring, "&");
          }
          // in case ot hyperCMS tags an end parenthesis indicates also end of attribute value
          elseif (strpos ($checkedstring, "]") > 0)
          {
            // get the length of the value
            $vallen = strpos ($checkedstring, "]");
          }    
          // freespace indicates also end of attribute value
          elseif (strpos ($checkedstring, " ") > 0)
          {
            // get the length of the value
            $vallen = strpos ($checkedstring, " ");
          }    
          // or the value itself is the end of the string    
          else
          {
            $vallen = strlen ($checkedstring);
          }
      
          // get the value of attribute
          $value = trim (substr ($checkedstring, 0, $vallen));

          // cut off double quotes from value
          if ((substr ($value, 0, 1) == "'" && substr ($value, strlen ($value)-1, 1) == "'") || (substr ($value, 0, 1) == "\"" && substr ($value, strlen ($value)-1, 1) == "\""))
          {
            $value = substr ($value, 1, strlen ($value)-2);
          }
          
          // secure value
          $value = strip_tags ($value);
          $value = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $value);
          
          return $value;
        }
        else return false;
      }
      else return false;
    }
  }
  else return false;
}

// ----------------------------- getoption --------------------------------
// function: getoption()
// input: string including options, option name
// output: option value/false on error

// description:
// get the value of a certain option out of a string (-c:v value -ar 44100)

function getoption ($string, $option)
{
  if ($string != "" && $option != "")
  {
    $option = trim ($option)." ";
    
    if (strpos ("_".$string, $option) > 0)
    {
      // extract value of option
      $temp = substr ($string, strpos ($string, $option) + strlen ($option));
      $value = substr ($temp, 0, strpos ($temp, " "));      
      if ($value == "" || $value == false) $value = substr ($temp, 0);
      
      // remove " and '
      $value = str_replace (array ("\"", "'"), array ("", ""), $value);
      
      return $value;
    }
    else return false;                  
  }
  else return false;
}
                      
// ------------------------------ getcharset ----------------------------------
// function: getcharset()
// input: publication, data from template or content container [string]
// output: array with content-type and charset / false on error
// requires: config.inc.php

// description:
// extract the content-type definition and the character set from the template (1st priority), content container (2nd priority) or publication settings (3rd priority)

function getcharset ($site, $data)
{
  global $mgmt_config;

  if ($data != "")
  {  
    $charset = false;
    $contenttype = false;
  
    // if HTML page and no pagecontentype can be defined by the editor
    if (@substr_count (strtolower ($data), " http-equiv=") > 0 && @substr_count (strtolower ($data), "content-type") > 0 && @substr_count (strtolower ($data), "pagecontenttype") == 0)
    {
      // get tag defined by the value of attribute http-equiv="content-type"
      $contenttypetag = gethtmltag (strtolower ($data), "content-type");
  
      if ($contenttypetag != false)
      {
        $start = strpos ($contenttypetag, "content=") + strlen ("content=");
    
        if (substr_count (substr ($contenttypetag, $start), "\"") > 0) $quotes = "\"";
        elseif (substr_count (substr ($contenttypetag, $start), "'") > 0) $quotes = "'";
        
        $end = strrpos ($contenttypetag, $quotes);
        $length = $end - $start;
        $contenttype = trim (substr ($contenttypetag, $start, $length));
        $contenttype = str_replace ($quotes, "", $contenttype);
        
        if (strpos ($contenttype, "charset") > 0) $charset = getattribute ($contenttype, "charset");
        else $charset = trim ($contenttype);            
      }    
    }   
 
    // if hypertag is used to set the character set (e.g. components)
    if ($contenttype == false && @substr_count (strtolower ($data), "compcontenttype") > 0)
    {
      // get content-type from component template, if set
      $hypertag_array = gethypertag ($data, "compcontenttype", 0);

      if ($hypertag_array != false)
      {
        foreach ($hypertag_array as $hypertag)
        {
          $contenttype = getattribute ($hypertag, "content");
          if ($contenttype != false) break;
        }
      }
      else $contenttype = false;
      
      if ($contenttype != false && $contenttype != "")
      {
        $contenttype = trim ($contenttype);
        
        if (strpos ($contenttype, "charset") > 0) $charset = getattribute ($contenttype, "charset");
        else $charset = trim ($contenttype);
      }
      else 
      {
        $charset = false;
        $contenttype = false;
      }
    }    
    
    // if XML page of from content container encoding
    if ($contenttype == false && @substr_count (strtolower ($data), " encoding=") > 0)
    {
      $xml_encoding = gethtmltag ($data, "?xml");
      
      if ($xml_encoding != false) 
      {
        $charset = getattribute ($xml_encoding, "encoding");
        
        if ($charset != "" && @substr_count (strtolower ($charset), "[hypercms:") == 0)
        {
          $contenttype = "text/html; charset=".$charset;
        }
        else 
        {
          $charset = false;
          $contenttype = false;
        }
      }
      else 
      {
        $charset = false;
        $contenttype = false;
      }      
    }
    
    // if head information in container is set
    if ($contenttype == false && @substr_count (strtolower ($data), "<pagecontenttype>") > 0)
    {
      $contenttype_array = getcontent ($data, "<pagecontenttype>");
      
      if ($contenttype_array != false)
      {
        $contenttype = $contenttype_array[0];
        $charset = getattribute ($contenttype, "charset");
        
        if ($charset == false || $charset == "")
        {
          $charset = false;
          $contenttype = false;
        }
      }
      else 
      {
        $charset = false;
        $contenttype = false;
      }
    }  
    
    // if content-type is given
    if ($contenttype == false && @substr_count (strtolower ($data), "charset") > 0)
    {
      $charset = getattribute ($data, "charset");

      if ($charset != "" && strlen ($charset) < 20)
      {
        if (strlen ($data) < 40 && @substr_count (strtolower ($data), "text/") > 0) $contenttype = $data;
        else $contenttype = "text/html; charset=".$charset;
      }
      else
      {
        $charset = false;
        $contenttype = false;
      }
    }
    
    // no character set could be found in $data
    if (($contenttype == "" || $contenttype == false) && valid_publicationname ($site))
    {
      $contenttype = "text/html; charset=".$mgmt_config[$site]['default_codepage'];
      $charset = $mgmt_config[$site]['default_codepage'];
    }  

    // return result
    if ($contenttype != "") $result['contenttype'] = $contenttype;
    if ($charset != "") $result['charset'] = $charset;

    if (is_array ($result)) return $result;
    else return false;
  }
  elseif (valid_publicationname ($site))
  {
    $result['contenttype'] = "text/html; charset=".$mgmt_config[$site]['default_codepage'];
    $result['charset'] = $mgmt_config[$site]['default_codepage'];
    
    if (!empty ($result) && is_array ($result)) return $result;
    else return false;    
  }
  else return false;
}  

// ------------------------------ getartid ----------------------------------
// function: getartid()
// input: string including id
// output: article id/false on error

// description:
// extract article id out of the id.

function getartid ($id)
{
  if ($id != "")
  {
    if (@substr_count ($id, ":") == 1)
    {
      $artid = substr ($id, 0, strpos ($id, ":"));
      
      // cut off the quote if there is any at the beginning
      if ($artid[0] == "'") $artid = substr ($artid, 1);
    }
    else $artid = false;
  
    return $artid;
  }
  else return false;
}

// ------------------------------ getelementid ----------------------------------
// function: getelementid()
// input: string including id
// output: element id/false on error

// description:
// extract element id out of the id

function getelementid ($id)
{
  if ($id != "")
  {
    if (@substr_count ($id, ":") == 1)
    {
      $elementid = substr ($id, strpos ($id, ":") + 1);
      
      // cut off the quote if there is any at the beginning
      if ($elementid != "" && $elementid[strlen($elementid) - 1] == "'") $elementid = substr ($elementid, 0, strlen($elementid) - 1);
    }
    else $elementid = false;
  
    return $elementid;
  }
  else return false;
}
?>