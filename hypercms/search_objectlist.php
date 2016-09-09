<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// load formats/file extensions
require_once ("include/format_ext.inc.php");


// input parameters
$action = getrequest ("action");
$site = getrequest ("site"); // site can be %Null%
$login = getrequest ("login", "objectname");
$search_dir = getrequest ("search_dir", "locationname");
$search_textnode = getrequest ("search_textnode", "array");
$search_expression = getrequest ("search_expression");
$replace_expression = getrequest ("replace_expression");
$search_cat = getrequest ("search_cat", "objectname");
$search_format = getrequest ("search_format", "array");
$search_filesize = getrequest ("search_filesize", "numeric");
$search_filesize_operator = getrequest ("search_filesize_operator");
$date_from = getrequest ("date_from");
$date_to = getrequest ("date_to");
$template = getrequest ("template", "objectname");
$object_id = getrequest ("object_id");
$container_id = getrequest ("container_id");
$geo_border_sw = getrequest ("geo_border_sw");
$geo_border_ne = getrequest ("geo_border_ne");
$maxhits = getrequest ("maxhits", "numeric");
$search_save = getrequest ("search_save");
$search_execute = getrequest ("search_execute");

$cat = "";

// extract publication and template name
if (substr_count ($template, "/") == 1) list ($site, $template) = explode ("/", $template);

// for file size search
if ($search_filesize != "" && $search_filesize_operator != "")
{
  $search_filesize = $search_filesize_operator.$search_filesize;
}

// only for image search
$search_imagesize = getrequest ("search_imagesize");

if ($search_imagesize == "exact")
{
  $search_imagewidth = getrequest ("search_imagewidth", "numeric");
  $search_imageheight = getrequest ("search_imageheight", "numeric");
}
else
{
  $search_imagewidth = $search_imagesize;
  $search_imageheight = "";
}

if (getrequest ("search_imagecolor") != "") $search_imagecolor[] = getrequest ("search_imagecolor");
else $search_imagecolor = "";

$search_imagetype = getrequest ("search_imagetype");

// try to get publication and category
if ($site == "")
{
  if ($search_dir != "")
  {
    $site = getpublication ($search_dir);
    $cat = getcategory ($site, $search_dir);
  }
  elseif (strpos ("_".$search_expression, "%taxonomy%/") > 0)
  {
    list ($domain, $site, $rest) = explode ("/", $search_expression);
  }
  elseif (strpos ("_".$search_expression, "%hierarchy%/") > 0)
  {
    list ($domain, $site, $rest) = explode ("/", $search_expression);
  }
}

// define reduced array for labels and objectlistcols 
if ($site != "" && $cat != "")
{
  // reduce labels
  if (!empty ($labels[$site][$cat])) $labels_reduced = $labels[$site][$cat];
  
  // reduce objectlistcols
  if (!empty ($objectlistcols[$site][$cat])) $objectlistcols_reduced = $objectlistcols[$site][$cat];
}
elseif ($site != "")
{
  // reduce labels
  if (!empty ($labels[$site]['page']) && !empty ($labels[$site]['comp'])) $labels_reduced = array_merge ($labels[$site]['page'], $labels[$site]['comp']);
  elseif (!empty ($labels[$site]['page'])) $labels_reduced = $labels[$site]['page'];
  elseif (!empty ($labels[$site]['comp'])) $labels_reduced = $labels[$site]['comp'];
  
  // reduce objectlistcols
  if (!empty ($objectlistcols[$site]['page']) && !empty ($objectlistcols[$site]['comp'])) $objectlistcols_reduced = array_merge ($objectlistcols[$site]['page'], $objectlistcols[$site]['comp']);
  elseif (!empty ($objectlistcols[$site]['page'])) $objectlistcols_reduced = $objectlistcols[$site]['page'];
  elseif (!empty ($objectlistcols[$site]['comp'])) $objectlistcols_reduced = $objectlistcols[$site]['comp'];
}
else
{
  // reduce labels
  if (is_array ($labels) && sizeof ($labels) > 0)
  {
    foreach ($labels as $temp_array1)
    {
      foreach ($temp_array1 as $temp_array2)
      {
        foreach ($temp_array2 as $text_id => $label)
        {
          $labels_reduced[$text_id] = $label;
        }
      }
    }
  }
  
  // reduce objectlistcols
  if (is_array ($objectlistcols) && sizeof ($objectlistcols) > 0)
  {
    foreach ($objectlistcols as $temp_array1)
    {
      foreach ($temp_array1 as $temp_array2)
      {
        foreach ($temp_array2 as $text_id => $active)
        {
          $objectlistcols_reduced[$text_id] = $active;
        }
      }
    }
  }
}

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// save search parameters
if (!empty ($search_save))
{
  $search_record = $mgmt_config['today']."|".$action."|".$site."|".$search_dir."|".$date_from."|".$date_to."|".$template."|".json_encode($search_textnode)."|".$search_expression."|".$search_cat."|".json_encode($search_format)."|".$search_filesize."|".$search_imagewidth."|".$search_imageheight."|".json_encode($search_imagecolor)."|".$search_imagetype."|".$geo_border_sw."|".$geo_border_ne."|".$object_id."|".$container_id;

  savelog (array($search_record), $user.".search");
}
// execute a saved search again (date is used for search name/ID)
elseif (!empty ($search_execute))
{
  if (is_file ($mgmt_config['abs_path_data']."log/".$user.".search.log"))
  {
    $searchlog_array = file ($mgmt_config['abs_path_data']."log/".$user.".search.log");
  
    if ($searchlog_array != false && sizeof ($searchlog_array) > 0)
    {
      foreach ($searchlog_array as $searchlog)
      {
        if (strpos ($searchlog, "|") > 0)
        {
          list ($date, $rest) = explode ("|", trim ($searchlog));
          
          if ($date == $search_execute)
          {
            list ($date, $action, $site, $search_dir, $date_from, $date_to, $template, $search_textnode, $search_expression, $search_cat, $search_format, $search_filesize, $search_imagewidth, $search_imageheight, $search_imagecolor, $search_imagetype, $geo_border_sw, $geo_border_ne, $object_id, $container_id) = explode ("|", trim ($searchlog));
            
            // JSON decode
            $search_textnode = json_decode ($search_textnode, true);
            $search_format = json_decode ($search_format, true);
            $search_imagecolor = json_decode ($search_imagecolor, true);
          }
        }
      }
    }
  }
}

// plugin config
if (is_file ($mgmt_config['abs_path_data']."config/plugin.conf.php"))
{
  require ($mgmt_config['abs_path_data']."config/plugin.conf.php");
}

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $search_dir, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

if (
     ($action != "base_search") && 
     ($action != "favorites") && 
     ($action != "checkedout") && 
     ($action != "user_files" && $object_id == "" && $container_id == "") && 
     (!valid_publicationname ($site) || !valid_locationname ($search_dir))
   ) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$object_array = array();
$search_dir_esc = array ();
$exclude_dir_esc = array ();
$search_filename = "";

// create secure token
$token = createtoken ($user);

// collect all objects of given user 
if ($action == "user_files" && $login != "" && $site != "" && (($site == "*Null*" && checkrootpermission ('user')) || checkglobalpermission ($site, 'user')))
{
  $object_array = rdbms_searchuser ($site, $login, 500); 
}
// favorites of user
elseif ($action == "favorites" && $user != "")
{
  $object_array = getfavorites ($user);
}
// checked out objects of user
elseif ($action == "checkedout" && $user != "")
{
  $object_array = getlockedobjects ($user);
}
// search for object ID or link ID
elseif ($object_id != "")
{
  $object_array[0] = rdbms_getobject ($object_id);
}
// search for container ID
elseif ($container_id != "")
{
  $object_array = rdbms_getobjects ($container_id, "");
}
// search for expression in content
elseif ($action == "base_search" || $search_dir != "")
{
  // if linking is used
  if (is_array ($hcms_linking) && ($location == "" || deconvertpath ($location, "file") == deconvertpath ($hcms_linking['location'], "file"))) 
  {
    $site = $hcms_linking['publication'];
    $cat = $hcms_linking['cat'];
    $search_dir = $hcms_linking['location'];
  }

  // object name based search
  if ($search_cat == "file")
  {
    $template = "";
    $search_textnode = "";
    $search_filename = $search_expression;
  }
  // search for object name or object content 
  elseif ($search_expression != "")
  {
    $search_textnode[0] = $search_expression;
    
    if (strpos ("_".$search_expression, "%taxonomy%/") > 0 || strpos ("_".$search_expression, "%keyword%/") > 0 || strpos ("_".$search_expression, "%hierarchy%/") > 0)
    {
      $search_filename = "";
    }
    else $search_filename = $search_expression;
  }
  
  // search for certain object types/formats
  if (!is_array ($search_format)) $search_format = "";
  
  // check permissions
  if ($action == "base_search" || ($cat == "comp" && checkglobalpermission ($site, 'component')) || ($cat == "page" && checkglobalpermission ($site, 'page')))
  {
    // no location provided
    if ($action == "base_search" && !valid_locationname ($search_dir) && !valid_publicationname ($site)) 
    {
      // page access of user
      foreach ($pageaccess as $site_name => $value)
      {
        foreach ($value as $group_name => $pathes)
        {
          // split access-string into an array
          $pathes = substr ($pathes, 0, strlen ($pathes)-1);
          $path_array = explode ("|", $pathes);
          
          foreach ($path_array as $path)
          {
            // check access permission
            if ($localpermission[$site_name][$group_name]['page']) $search_dir_esc[] = convertpath ($site_name, $path, "page");
            else $exclude_dir_esc[] = convertpath ($site_name, $path, "page");
          }
        }
      }
      
      // component access of user
      foreach ($compaccess as $site_name => $value)
      {
        foreach ($value as $group_name => $pathes)
        {
          // split access-string into an array
          $pathes = substr ($pathes, 0, strlen ($pathes)-1);
          $path_array = explode ("|", $pathes);
          
          foreach ($path_array as $path)
          {
            // check access permission
            if ($localpermission[$site_name][$group_name]['component']) $search_dir_esc[] = convertpath ($site_name, $path, "comp");
            else $exclude_dir_esc[] = convertpath ($site_name, $path, "comp");
          }
        }
      }
    }
    // location provided
    elseif (valid_locationname ($search_dir))
    {
      $search_dir_esc = convertpath ($site, $search_dir, $cat);
    }
    // publication provided
    elseif (valid_publicationname ($site))
    {
      $search_dir_esc = array();
      
      if (checkglobalpermission ($site, 'component')) $search_dir_esc[] = "%comp%/".$site."/";
      if (checkglobalpermission ($site, 'page')) $search_dir_esc[] = "%page%/".$site."/";
    }
    
    // max. hits
    if ($maxhits > 1000) $maxhits = 1000;
    elseif (empty ($maxhits)) $maxhits = 100;

    // start search
    if ($replace_expression == "") $object_array = rdbms_searchcontent ($search_dir_esc, $exclude_dir_esc, $search_format, $date_from, $date_to, $template, $search_textnode, $search_filename, $search_filesize, $search_imagewidth, $search_imageheight, $search_imagecolor, $search_imagetype, $geo_border_sw, $geo_border_ne, $maxhits);
    // start search and replace
    elseif ($setlocalpermission['create'] == 1) $object_array = rdbms_replacecontent ($search_dir_esc, $search_format, $date_from, $date_to, $search_expression, $replace_expression, $user);
  }
}

// create view of items
// how many images/folders in each row
if ($is_mobile) $table_cells = 3;
else $table_cells = 5;

// define cell width of table
if ($table_cells == "1") $cell_width = "100%";
elseif ($table_cells == "2") $cell_width = "50%";  
elseif ($table_cells == "3") $cell_width = "33%";    
elseif ($table_cells == "4") $cell_width = "25%";      
elseif ($table_cells == "5") $cell_width = "20%";
elseif ($table_cells == "6") $cell_width = "16%";
else $cell_width = "10%";  

$galleryview = Null;
$listview = Null;
$items_row = 0;

if ($object_array != false && @sizeof ($object_array) > 0)
{
  foreach ($object_array as $hash => $objectpath)
  {
    // folder items
    if (getobject ($objectpath) == ".folder")
    {
      // check for location path inside folder variable
      if (substr_count ($objectpath, "/") > 0)
      {
        // remove .folder file from path
        $objectpath = getlocation ($objectpath);
        // get site
        $item_site = getpublication ($objectpath);
        // get category
        $item_cat = getcategory ($item_site, $objectpath);
        
        if (valid_publicationname ($item_site) && $item_cat != "")
        {         
          // get location (cut off folder name)
          $location_esc = getlocation ($objectpath);
          // get location in file system
          $location = deconvertpath ($location_esc, "file");         
          // get location name
          $item_location = getlocationname ($item_site, $location_esc, $item_cat, "path");                
          // get folder name
          $folder = getobject ($objectpath);
          // get folder name
          $file_info = getfileinfo ($item_site, $location.$folder."/.folder", $item_cat);
          $folder_name = $file_info['name'];
          
          // check access permission
          $ownergroup = accesspermission ($item_site, $location.$folder."/", $item_cat);
          $setlocalpermission = setlocalpermission ($item_site, $ownergroup, $item_cat);
          
          if ($ownergroup != false && $setlocalpermission['root'] == 1 && valid_locationname ($location) && valid_objectname ($folder) && is_dir ($location.$folder))
          {
            $metadata = "";
            $file_size = "";
            $file_created = "";
            $file_modified = "";
            $file_owner = "";
              
            // read file
            $objectdata = loadfile ($location.$folder."/", ".folder");
            
            if ($objectdata != false)
            {
              // get name of content file and load content container
              $contentfile = getfilename ($objectdata, "content");
              $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml"));  
                    
              // read meta data of media file
              if ($contentfile != false)
              {
                $result = getcontainername ($contentfile);
                
                if (!empty ($result['user'])) $usedby = $result['user'];
                else $usedby = "";          
              }
              
              // get metadata of container
              $container_info = getmetadata_container ($container_id, array_keys ($objectlistcols_reduced));
              
              if (is_array ($container_info))
              {  
                if (!empty ($container_info['createdate'])) $file_created = date ("Y-m-d H:i", strtotime ($container_info['createdate']));
                if (!empty ($container_info['date'])) $file_modified = date ("Y-m-d H:i", strtotime ($container_info['date']));
                if (!empty ($container_info['user'])) $file_owner = $container_info['user'];
              }
              
              // link for copy & paste of download links
              if ($mgmt_config[$item_site]['sendmail'] && $setlocalpermission['download'] == 1)
              {
                $dlink_start = "<a id=\"dlink_".$items_row."\" data-linktype=\"hash\" data-href=\"".createdownloadlink($item_site, $location.$folder."/", ".folder", $item_cat)."\">";
                $dlink_end = "</a>";
              }
              else
              {
                $dlink_start = "";
                $dlink_end = "";
              }
            }
            
            // fallback for date modified
            if (empty ($file_size))
            {
              // get file time
              $file_modified = date ("Y-m-d H:i", @filemtime ($location.$folder));
            }
            
            // listview - view option for locked multimedia objects
            if ($file_info['published'] == false) $class_image = "class=\"hcmsIconList hcmsIconOff\"";
            else $class_image = "class=\"hcmsIconList\"";            
            
            // refresh sidebar
            if (!$is_mobile) $sidebarclick = "if (sidebar) hcms_loadSidebar();";
            else $sidebarclick = "";
            // onclick for marking objects
            $selectclick = "onClick=\"hcms_selectObject('".$items_row."', event); hcms_updateControlObjectListMenu(); ".$sidebarclick."\" ";
            // open folder
            $openFolder = "onDblClick=\"parent.location='frameset_objectlist.php?site=".url_encode($item_site)."&cat=".url_encode($item_cat)."&location=".url_encode($location_esc.$folder)."/&token=".$token."';\" ";
            // set context
            $hcms_setObjectcontext = "onMouseOver=\"hcms_setObjectcontext('".$item_site."', '".$item_cat."', '".$location_esc."', '.folder', '".$folder_name."', 'Folder', '', '".$folder."', '', '".$token."');\" onMouseOut=\"hcms_resetContext();\" ";

            // listview - view option for locked folders
            if ($usedby != "")
            {
              $file_info['icon'] = "folderlock.gif";
              $file_info['icon_large'] = "folderlock.png";
            }
            
            // metadata
            $metadata = getescapedtext ($hcms_lang['name'][$lang]).": ".$folder_name." \r\n".getescapedtext ($hcms_lang['date-modified'][$lang]).": ".$file_modified." \r\n".$metadata;             

            $listview .= "
                         <tr id=g".$items_row." ".$selectclick." style=\"cursor:pointer\" align=\"left\">
                           <td id=\"h".$items_row."_0\" class=\"hcmsCol0 hcmsCell\" style=\"width:280px;\">
                             <input id=\"objectpath\" type=hidden value=\"".$location_esc.$folder."\">
                             <div ".$hcms_setObjectcontext." ".$openFolder." title=\"".$metadata."\" style=\"display:block; padding-left:5px; padding-right:5px;\">
                               <img src=\"".getthemelocation()."img/".$file_info['icon']."\" align=\"absmiddle\" class=\"hcmsIconList\" /> ".$dlink_start.$folder_name.$dlink_end."
                             </div>
                            </td>";

            if (!$is_mobile)
            {
              $listview .= "
                            <td id=\"h".$items_row."_1\" class=\"hcmsCol1 hcmsCell\" style=\"width:250px;\"><div ".$hcms_setObjectcontext." title=\"".$item_location."\" style=\"display:block; padding-left:5px; padding-right:5px;\">".$item_location."</div></td>";
                   
              if (is_array ($objectlistcols_reduced))
              {
                $i = 2;
                
                foreach ($objectlistcols_reduced as $key => $active)
                {
                  if ($i < (sizeof ($objectlistcols_reduced) + 1)) $style_td = "width:115px;";
                  else $style_td = "";
                  
                  $style_div = "";
                  
                  if ($active == 1)
                  {
                    if ($key == 'createdate')
                    {
                      $title = $file_created;
                    }
                    elseif ($key == 'modifieddate')
                    {
                      $title = $file_modified;
                    }
                    elseif ($key == 'filesize')
                    {
                      $title = "";
                      $style_div = "text-align:right;";
                    }
                    elseif ($key == 'type')
                    {
                      $title = getescapedtext ($hcms_lang['folder'][$lang]);
                    }
                    elseif ($key == 'owner')
                    {
                      $title = $file_owner;
                    }
                    else
                    {
                      if (!empty ($container_info[$key])) $title = $container_info[$key];
                      else $title = "";
                    }
                    
                    $listview .= "
                            <td id=\"h".$items_row."_".$i."\" class=\"hcmsCol".$i." hcmsCell\" style=\"".$style_td."\"><div ".$hcms_setObjectcontext." style=\"display:block; padding-left:5px; padding-right:5px; ".$style_div."\">".$title."</div></td>";
                    
                    $i++;
                  }
                }
              }
            }
            
            $listview .= "
                         </tr>";
        
            $galleryview .= "
                            <td id=t".$items_row." style=\"width:".$cell_width."; height:180px; text-align:center; vertical-align:bottom;\">
                              <div ".$selectclick." ".$hcms_setObjectcontext." ".$openFolder." title=\"".$folder_name."\" style=\"cursor:pointer; display:block;\">".
                                $dlink_start."
                                  <div id=\"w".$items_row."\" class=\"hcmsThumbnailWidth".$temp_explorerview."\"><img src=\"".getthemelocation()."img/".$file_info['icon_large']."\" style=\"border:0;\" /></div>
                                  ".showshorttext($folder_name, 18, true)."
                                ".$dlink_end."
                              </div>
                            </td>";
                            
            $items_row++;
          }
        }
      }        
    }
    // object items
    else
    { 
      // check for location path inside folder variable
      if (substr_count ($objectpath, "/") > 0)
      {
        // get site
        $item_site = getpublication ($objectpath);        
        // get category
        $item_cat = getcategory ($item_site, $objectpath); 
        
        if (valid_publicationname ($item_site) && $item_cat != "")
        {
          // get location
          $location_esc = getlocation ($objectpath);
          // get location in file system
          $location = deconvertpath ($location_esc, "file");           
          // get location name
          $item_location = getlocationname ($item_site, $location_esc, $item_cat, "path");        
          // get object name
          $object = getobject ($objectpath);  
          $object = correctfile ($location, $object, $user); 
          $file_info = getfileinfo ($item_site, $location.$object, $item_cat);
          $object_name = $file_info['name'];

          // check access permission
          $ownergroup = accesspermission ($item_site, $location, $item_cat);
          $setlocalpermission = setlocalpermission ($item_site, $ownergroup, $item_cat);

          if ($ownergroup != false && $setlocalpermission['root'] == 1 && valid_locationname ($location) && valid_objectname ($object) && is_file ($location.$object))
          {
            $metadata = "";
            $file_size = "";
            $file_created = "";
            $file_modified = "";
            $file_owner = "";
                    
            // page
            if ($file_info['type'] == "Page") $file_type = getescapedtext ($hcms_lang['object-page'][$lang]);
            // component
            elseif ($file_info['type'] == "Component") $file_type = getescapedtext ($hcms_lang['object-component'][$lang]);    
            // multimedia object 
            else $file_type = getescapedtext ($hcms_lang['file'][$lang])." (".$file_info['type'].")";

            // read file
            $objectdata = loadfile ($location, $object);
            
            if ($objectdata != false)
            {
              // get name of content file and load content container
              $contentfile = getfilename ($objectdata, "content");
              $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml"));  
                    
              // get user of locked container
              if ($contentfile != false)
              {
                $result = getcontainername ($contentfile);
                
                if (!empty ($result['user'])) $usedby = $result['user'];
                else $usedby = "";          
              } 
              
              // get metadata of container
              $container_info = getmetadata_container ($container_id, array_keys ($objectlistcols_reduced));
    
              if (is_array ($container_info))
              { 
                if (!empty ($container_info['filesize'])) $file_size = number_format ($container_info['filesize'], 0, "", ".");
                if (!empty ($container_info['createdate'])) $file_created = date ("Y-m-d H:i", strtotime ($container_info['createdate']));
                if (!empty ($container_info['date'])) $file_modified = date ("Y-m-d H:i", strtotime ($container_info['date']));
                if (!empty ($container_info['user'])) $file_owner = $container_info['user'];
              }

              // get name of media file
              $mediafile = getfilename ($objectdata, "media");
  
              if ($mediafile != false)
              {
                // location of file
                $mediadir = getmedialocation ($item_site, $mediafile, "abs_path_media");
                
                // fallback for file size and date modified
                if (empty ($file_size) && is_file ($mediadir.$item_site."/".$mediafile))
                {
                  $file_size = round (@filesize ($mediadir.$item_site."/".$mediafile) / 1024);
                  if ($file_size == 0) $file_size = 1;
                  $file_size = number_format ($file_size, 0, "", ".");
                  
                  $file_modified = date ("Y-m-d H:i", @filemtime ($mediadir.$item_site."/".$mediafile));               
                }
                
                // media file info
                $media_info = getfileinfo ($item_site, $mediafile, $item_cat);
                
                // get metadata for media file
                if (!empty ($mgmt_config['explorer_list_metadata']) && !$is_mobile && !$temp_sidebar) $metadata = getmetadata ("", "", $contentfile, " \r\n");
                
                // link for copy & paste of download links
                if ($mgmt_config[$item_site]['sendmail'] && $setlocalpermission['download'] == 1)
                {
                  $dlink_start = "<a id=\"dlink_".$items_row."\" data-linktype=\"hash\" data-href=\"".$mgmt_config['url_path_cms']."?dl=".$hash."\">";
                  $dlink_end = "</a>";
                }
                else
                {
                  $dlink_start = "";
                  $dlink_end = "";
                }
              }    
              // object without media file
              else
              {
                // get file size
                $file_size = round (@filesize ($location.$object) / 1024);
                if ($file_size == 0) $file_size = 1;
                $file_size = number_format ($file_size, 0, "", ".");
                
                // get file time
                $file_modified = date ("Y-m-d H:i", @filemtime ($location.$object));
                
                // link for copy & paste of download links
                if ($mgmt_config[$item_site]['sendmail'] && $setlocalpermission['download'] == 1)
                {
                  $dlink_start = "<a id=\"link_".$items_row."\" target=\"_blank\" data-linktype=\"hash\" data-href=\"".$mgmt_config['url_path_cms']."?wl=".$hash."\">";
                  $dlink_end = "</a>";
                }
                else
                {
                  $dlink_start = "";
                  $dlink_end = "";
                }
              }
            }
        
            // open on double click
            $openObject = "onDblClick=\"hcms_openWindow('frameset_content.php?ctrlreload=yes&site=".url_encode($item_site)."&cat=".url_encode($item_cat)."&location=".url_encode($location_esc)."&page=".url_encode($object)."&token=".$token."', '".$container_id."', 'status=yes,scrollbars=no,resizable=yes', 800, 600);\"";
            // refresh sidebar
            if (!$is_mobile) $sidebarclick = "if (sidebar) hcms_loadSidebar();";
            else $sidebarclick = "";
            // onclick for marking objects
            $selectclick = "onClick=\"hcms_selectObject('".$items_row."', event); hcms_updateControlObjectListMenu(); ".$sidebarclick."\" ";
            // set context
            $hcms_setObjectcontext = "onMouseOver=\"hcms_setObjectcontext('".$item_site."', '".$item_cat."', '".$location_esc."', '".$object."', '".$object_name."', '".$file_info['type']."', '".$mediafile."', '', '', '".$token."');\" onMouseOut=\"hcms_resetContext();\" ";

            // metadata
            $metadata = getescapedtext ($hcms_lang['name'][$lang]).": ".$object_name." \r\n".getescapedtext ($hcms_lang['date-modified'][$lang]).": ".$file_modified." \r\n".getescapedtext ($hcms_lang['size-in-kb'][$lang]).": ".$file_size." \r\n".$metadata;
            
            // listview - view option for un/published objects
            if ($file_info['published'] == false) $class_image = "class=\"hcmsIconList hcmsIconOff\"";
            else $class_image = "class=\"hcmsIconList\"";
            
            // listview - view option for locked objects
            if ($usedby != "")
            {
              $file_info['icon'] = "filelock.gif";
              $file_info['icon_large'] = "filelock.png";
            }         
            
            $listview .= "
                         <tr id=\"g".$items_row."\" style=\"text-align:left; cursor:pointer;\" ".$selectclick.">
                           <td id=\"h".$items_row."_0\"class=\"hcmsCol0 hcmsCell\" style=\"width:280px;\">
                             <input id=\"objectpath\" type=hidden value=\"".$location_esc.$object."\" />
                             <div ".$hcms_setObjectcontext." ".$openObject." title=\"".$metadata."\" style=\"display:block; padding-left:5px; padding-right:5px;\">
                               <img src=\"".getthemelocation()."img/".$file_info['icon']."\" align=\"absmiddle\" ".$class_image." /> ".$dlink_start.$object_name.$dlink_end."
                             </div>
                           </td>";
            
            if (!$is_mobile)
            {
              $listview .= "
                           <td id=\"h".$items_row."_1\" class=\"hcmsCol1 hcmsCell\" style=\"width:250px;\"><div ".$hcms_setObjectcontext." title=\"".$item_location."\" style=\"display:block; padding-left:5px; padding-right:5px;\">".$item_location."</div></td>";

              if (is_array ($objectlistcols_reduced))
              {
                $i = 2;
                
                foreach ($objectlistcols_reduced as $key => $active)
                {
                  if ($i < (sizeof ($objectlistcols_reduced) + 1)) $style_td = "width:115px;";
                  else $style_td = "";
                
                  if ($active == 1)
                  {
                    $style_div = "";
                    
                    if ($key == 'createdate')
                    {
                      $title = $file_created;
                    }
                    elseif ($key == 'modifieddate')
                    {
                      $title = $file_modified;
                    }
                    elseif ($key == 'filesize')
                    {
                      $title = $file_size;
                      $style_div = "text-align:right;";
                    }
                    elseif ($key == 'type')
                    {
                      $title = $file_type;
                    }
                    elseif ($key == 'owner')
                    {
                      $title = $file_owner;
                    }
                    else
                    {
                      if (!empty ($container_info[$key])) $title = $container_info[$key];
                      else $title = "";
                    }
                    
                    $listview .= "
                            <td id=\"h".$items_row."_".$i."\" class=\"hcmsCol".$i." hcmsCell\" style=\"".$style_td."\"><div ".$hcms_setObjectcontext." style=\"display:block; padding-left:5px; padding-right:5px; ".$style_div."\">".$title."</div></td>";
                  
                    $i++;
                  }
                }
              }
            }
               
            $listview .= "
                         </tr>";  

            // default value
            $ratio = "Width";
            
            // if there is a thumb file, display the thumb
            if ($mediafile != false)
            {
              // get thumbnail location
              $thumbdir = getmedialocation ($site, $media_info['filename'].".thumb.jpg", "abs_path_media");
          
              // prepare source media file
              preparemediafile ($item_site, $thumbdir.$item_site."/", $media_info['filename'].".thumb.jpg", $user);
               
              // try to create thumbnail if not available
              if ($mgmt_config['recreate_preview'] == true && (!is_file ($thumbdir.$item_site."/".$media_info['filename'].".thumb.jpg") || !is_cloudobject ($thumbdir.$item_site."/".$media_info['filename'].".thumb.jpg")))
              {
                createmedia ($item_site, $thumbdir.$item_site."/", $thumbdir.$item_site."/", $media_info['file'], "", "thumbnail");
              }          
              
              if (is_file ($thumbdir.$item_site."/".$media_info['filename'].".thumb.jpg") || is_cloudobject ($thumbdir.$item_site."/".$media_info['filename'].".thumb.jpg"))
              {
                $imgsize = getimagesize ($thumbdir.$item_site."/".$media_info['filename'].".thumb.jpg");
                
                // calculate image ratio to define CSS for image container div-tag
                if (is_array ($imgsize))
                {
                  $imgwidth = $imgsize[0];
                  $imgheight = $imgsize[1];
                  $imgratio = $imgwidth / $imgheight;   
                  
                  // image width >= height
                  if ($imgratio >= 1) $ratio = "Width";
                  // image width < height
                  else $ratio = "Height";
                }
                          
                // galleryview - view option for locked multimedia objects
                if ($file_info['published'] == false) $class_image = "class=\"hcmsIconOff\"";
                else $class_image = "class=\"hcmsImageItem\"";               
                
                // if thumbnail is smaller than defined thumbnail size
                if ($imgwidth < 180 && $imgheight < 180)
                {
                  $div_id = "id=\"x".$items_row."\"";
                  $class_size = "";
                }
                else
                {
                  $div_id = "id=\"".strtolower(substr($ratio, 0, 1)).$items_row."\"";
                  $class_size = "class=\"hcmsThumbnail".$ratio.$temp_explorerview."\"";
                }
                
                $thumbnail = "<div ".$div_id." ".$class_size."><img src=\"".createviewlink ($item_site, $media_info['filename'].".thumb.jpg", $object_name)."\" ".$class_image." /></div>";
              }
              // display file icon if thumbnail fails 
              else
              {
                // galleryview - view option for locked multimedia objects
                if ($file_info['published'] == false) $class_image = "class=\"hcmsIconOff\"";
                else $class_image = "";
                        
                $thumbnail = "<div id=\"w".$items_row."\" class=\"hcmsThumbnail".$ratio.$temp_explorerview."\"><img src=\"".getthemelocation()."img/".$file_info['icon_large']."\" style=\"border:0;\" ".$class_image." /></div>";
              }           
            }
            // display file icon for non multimedia objects 
            else
            {
              // galleryview - view option for locked multimedia objects
              if ($file_info['published'] == false) $class_image = "class=\"hcmsIconOff\"";
              else $class_image = "";
                      
              $thumbnail = "<div id=\"w".$items_row."\" class=\"hcmsThumbnail".$ratio.$temp_explorerview."\"><img src=\"".getthemelocation()."img/".$file_info['icon_large']."\" style=\"border:0;\" ".$class_image." /></div>";
            }
            
            // if linking is used display download buttons, display edit button for mobile edition
            $linking_buttons = "";
            
            if ($mediafile != false && is_array (getsession ('hcms_linking')) && $setlocalpermission['root'] == 1 && $setlocalpermission['download'] == 1)
            {
              // check download of original file
              if (empty ($downloadformats) || (is_document ($mediafile) && !empty ($downloadformats['document']['original'])) || (is_image ($mediafile) && !empty ($downloadformats['image']['original'])))
              {            
                $linking_buttons .= "
                <button class=\"hcmsButtonDownload\" onClick=\"openliveview('".url_encode($location_esc)."', '".url_encode($object)."');\">".getescapedtext ($hcms_lang['view'][$lang])."</button>
                <a href=\"".createviewlink ($item_site, $mediafile, $object_name, false, "download")."\" target=\"_blank\"><button class=\"hcmsButtonDownload\">".getescapedtext ($hcms_lang['download'][$lang])."</button></a>";
              }
            }
            
            // if mobile edition is used display edit button
            if ($is_mobile && (($mediafile == "" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1) || ($mediafile != "" && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1)))
            {   
              $linking_buttons .= "
              <button class=\"hcmsButtonDownload\" onClick=\"hcms_openWindow('frameset_content.php?ctrlreload=yes&site=".url_encode($item_site)."&cat=".url_encode($item_cat)."&location=".url_encode($location_esc)."&page=".url_encode($object)."&token=".$token."', '".$container_id."', 'status=yes,scrollbars=no,resizable=yes', 800, 600);\">".getescapedtext ($hcms_lang['edit'][$lang])."</button>";
            }
            
            // if assetbrowser is used display edit button
            if (!empty ($hcms_assetbrowser) && $mediafile != "" && $setlocalpermission['root'] == 1)
            {   
              $linking_buttons .= "
              <button class=\"hcmsButtonDownload\" style=\"width:154px;\" onClick=\"parent.parent.returnMedia('".url_encode($location_esc.$object)."', '".$object_name."', '".$imgwidth."', '".$imgheight."', '".$file_modified."', '".$file_size."');\">".getescapedtext ($hcms_lang['select'][$lang])."</button>";
            }
            
            if ($linking_buttons != "")
            {
              if (!$is_mobile) $width = "160px";
              else $width = "180px";
                
              $linking_buttons = "<div style=\"width:".$width."; margin-left:auto; margin-right:auto; padding:0; text-align:center;\">".$linking_buttons."</div>";
            }

            $galleryview .= "
                            <td id=\"t".$items_row."\" style=\"width:".$cell_width."; height:180px; text-align:center; vertical-align:bottom;\">
                              <div ".$selectclick." ".$hcms_setObjectcontext." ".$openObject." title=\"".$metadata."\" style=\"cursor:pointer; display:block; text-align:center;\">".
                                $dlink_start."
                                  ".$thumbnail."
                                  ".showshorttext($object_name, 18, true)."
                                ".$dlink_end."
                              </div>
                              ".$linking_buttons."
                            </td>";
                            
            $items_row++;
          }
        }      
      }
    }
    
    if (is_int ($items_row / $table_cells))
    {
      $galleryview .= "</tr>\n<tr>\n";
    }     
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/navigator.css" />
<script src="javascript/main.js" language="JavaScript" type="text/javascript"></script>
<script src="javascript/contextmenu.js" language="JavaScript" type="text/javascript"></script>
<script type="text/javascript" src="javascript/jquery/jquery-1.10.2.min.js"></script>
<script type="text/javascript" src="javascript/jquery/plugins/colResizable-1.5.min.js"></script>
<script type="text/javascript" src="javascript/chat.js"></script>
<script language="JavaScript">

// context menu
var contextenable = 1;

// set contect menu move options
var contextxmove = 1;
var contextymove = 1;

// explorer view option
var explorerview = "<?php echo $temp_explorerview; ?>";
var sidebar = <?php if ($temp_sidebar) echo "true"; else echo "false"; ?>;

// define global variable for popup window name used in contextmenu.js
var session_id = '<?php echo session_id(); ?>';

function checktype (type)
{
  var settype = document.forms['contextmenu_object'].elements['contexttype'].value;
  
  if (settype == type) return true;
  else return false;
}

function confirm_delete ()
{
  return confirm(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-remove-the-item'][$lang]); ?>"));
}

function toggleview (viewoption)
{
  if (viewoption == "detail") hcms_showHideLayers ('detailviewLayer','','show','objectLayer','','show','detailviewReset','','show','galleryviewLayer','','hide','galleryviewReset','','hide');
  else if (viewoption == "small" || viewoption == "medium" || viewoption == "large") hcms_showHideLayers ('detailviewLayer','','hide','objectLayer','','hide','detailviewReset','','show','galleryviewLayer','','show','galleryviewReset','');
  
  var id = '';
  
  for (var i = 0; i <= <?php echo $items_row; ?>; i++)
  {
    thumbnail = eval (document.getElementById('w' + i));
    
    if (thumbnail)
    {
      thumbnail.className = 'hcmsThumbnailWidth' + viewoption;
    }
    else
    {
      thumbnail = eval (document.getElementById('h' + i));
      if (thumbnail) thumbnail.className = 'hcmsThumbnailHeight' + viewoption;    
    }
  }
  
  return true;
}

function openliveview (location, object)
{
  var width = Math.max(document.documentElement.clientWidth, window.innerWidth || 0)
  var height = Math.max(document.documentElement.clientHeight, window.innerHeight || 0)
  
  document.getElementById('liveview').src = 'explorer_liveview.php?location=' + location + '&page=' + object + '&width=' + width + '&height=' + height;
  hcms_showInfo('liveviewLayer',0);
}

function closeliveview ()
{
  document.getElementById('liveview').src = '';
  hcms_hideInfo('liveviewLayer');
}

// start chat
var chat =  new Chat();

function sendtochat (text)
{
  if (text != "")
  {
    var username = '<?php echo $user; ?>';
    // strip tags
    username = username.replace(/(<([^>]+)>)/ig,"");
    chat.send(text, username);
  }
}

function resizecols ()
{
  var colwidth;

  for (i = 0; i < <?php if (is_array ($objectlistcols_reduced)) echo sizeof ($objectlistcols_reduced) + 1; else echo 1;  ?>; i++)
  {
    // get width of table header columns
    if ($('#c'+i)) colwidth = $('#c'+i).width();

    // set width for table columns
    $('.hcmsCol'+i).width(colwidth);
  }
}

// load control frame
parent.frames['controlFrame'].location = 'control_objectlist_menu.php?virtual=1&from_page=search';
</script>
<style>
.hcmsCell
{
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
</head>

<body id="hcmsWorkplaceObjectlist" class="hcmsWorkplaceObjectlist" onresize="resizecols();">

<!-- live view --> 
<div id="liveviewLayer" class="hcmsWorkplaceObjectlist" style="display:none; position:fixed; width:100%; height:100%; margin:0; padding:0; left:0; top:0; z-index:8;">
  <div style="position:fixed; right:5px; top:5px; z-index:9;">
    <img name="hcms_mediaClose" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="closeliveview();" />
  </div>
  <iframe id="liveview" src="" scrolling="auto" frameBorder="0" <?php if (!$is_iphone) echo 'style="width:100%; height:100%; border:0; margin:0; padding:0;"'; ?>></iframe>
</div>

<!-- contextual help --> 
<?php if (!$is_mobile) echo showinfobox ($hcms_lang['hold-ctrl-key-select-objects-by-click'][$lang]."<br/>".$hcms_lang['hold-shift-key-select-a-group-of-objects-by-2-clicks'][$lang]."<br/>".$hcms_lang['press-alt-key-switch-to-download-links-to-copy-paste-into-e-mails'][$lang], $lang, "position:fixed; top:30px; right:30px;", "hcms_infoboxKeys"); ?>

<!-- context menu --> 
<div id="contextLayer" style="position:absolute; width:150px; height:260px; z-index:10; left:20px; top:20px; visibility:hidden;"> 
  <form name="contextmenu_object" action="" method="post" target="_blank">
    <input type="hidden" name="contextmenustatus" value="" />
    <input type="hidden" name="contextmenulocked" value="false" />
    <input type="hidden" name="action" value="" />
    <input type="hidden" name="force" value="" />
    <input type="hidden" name="contexttype" value="none" />
    <input type="hidden" name="xpos" value="" />
    <input type="hidden" name="ypos" value="" />
    <input type="hidden" name="site" value="" />
    <input type="hidden" name="cat" value="" />
    <input type="hidden" name="location" value="" />
    <input type="hidden" name="page" value="" />
    <input type="hidden" name="pagename" value="" />
    <input type="hidden" name="filetype" value="" />
    <input type="hidden" name="media" value="" />
    <input type="hidden" name="folder" value="" />
    <input type="hidden" name="multiobject" value="" />
    <input type="hidden" name="from_page" value="search" />
    <input type="hidden" name="token" value="" />
    <input type="hidden" name="convert_type" value="" />
    <input type="hidden" name="convert_cfg" value="" />
    
    <table width="150px" cellspacing="0" cellpadding="3" class="hcmsContextMenu">
      <tr>
        <td>
          <?php if ($action == "favorites") { ?>
          <a href=# id="href_fav_delete" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('favorites_delete');"><img src="<?php echo getthemelocation(); ?>img/button_favorites_new.gif" id="img_fav_delete" align="absmiddle" border=0 />&nbsp;<?php echo getescapedtext ($hcms_lang['delete-favorite'][$lang]); ?></a><br />        
          <hr />
          <?php } ?>  
          <?php if ($action == "checkedout") { ?>
          <a href=# id="href_unlock" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('checkin');"><img src="<?php echo getthemelocation(); ?>img/button_file_unlock.gif" id="img_unlock" align="absmiddle" border=0 />&nbsp;<?php echo getescapedtext ($hcms_lang['check-in'][$lang]); ?></a><br />        
          <hr />
          <?php } ?>  
          <a href=# id="href_preview" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('preview');"><img src="<?php echo getthemelocation(); ?>img/button_file_preview.gif" id="img_preview" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo getescapedtext ($hcms_lang['preview'][$lang]); ?></a><br />  
          <a href=# id="href_cmsview" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('cmsview');"><img src="<?php echo getthemelocation(); ?>img/button_file_edit.gif" id="img_cmsview" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo getescapedtext ($hcms_lang['edit'][$lang]); ?></a><br />
          <a href=# id="href_notify" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('notify');"><img src="<?php echo getthemelocation(); ?>img/button_notify.gif" id="img_notify" align="absmiddle" border=0 class="hcmsIconOn">&nbsp;<?php echo getescapedtext ($hcms_lang['notify-me'][$lang]); ?></a><br />
          <?php if (isset ($mgmt_config['chat']) && $mgmt_config['chat'] == true) { ?> 
          <a href=# id="href_chat" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('chat');"><img src="<?php echo getthemelocation(); ?>img/button_chat.gif" id="img_chat" align="absmiddle" border=0 class="hcmsIconOn">&nbsp;<?php echo getescapedtext ($hcms_lang['send-to-chat'][$lang]); ?></a><br />
          <?php } ?>   
          <hr />
          <a href=# id="href_delete" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('delete');"><img src="<?php echo getthemelocation(); ?>img/button_delete.gif" id="img_delete" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?></a><br />     
          <hr />
          <a href=# id="href_cut" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('cut');"><img src="<?php echo getthemelocation(); ?>img/button_file_cut.gif" id="img_cut" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo getescapedtext ($hcms_lang['cut'][$lang]); ?></a><br />  
          <a href=# id="href_copy" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('copy');"><img src="<?php echo getthemelocation(); ?>img/button_file_copy.gif" id="img_copy" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo getescapedtext ($hcms_lang['copy'][$lang]); ?></a><br />  
          <a href=# id="href_copylinked" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('linkcopy');"><img src="<?php echo getthemelocation(); ?>img/button_file_copylinked.gif" id="img_copylinked" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo getescapedtext ($hcms_lang['connected-copy'][$lang]); ?></a><br />   
          <hr />
          <a href=# id="href_publish" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('publish');"><img src="<?php echo getthemelocation(); ?>img/button_file_publish.gif" id="img_publish" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo getescapedtext ($hcms_lang['publish'][$lang]); ?></a><br />  
          <a href=# id="href_unpublish" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('unpublish');"><img src="<?php echo getthemelocation(); ?>img/button_file_unpublish.gif" id="img_unpublish" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo getescapedtext ($hcms_lang['unpublish'][$lang]); ?></a><br />
          <hr />
          <?php
          // ----------------------------------------- plugins ----------------------------------------------
          if ($setlocalpermission['root'] == 1 && empty ($hcms_assetbrowser) && !isset ($hcms_linking['location']) && !empty ($mgmt_plugin))
          { 
            $plugin_items = "";
            
            foreach ($mgmt_plugin as $plugin_name => $data)
            {
              // Only active plugins which have the correct keys are used
              if (is_array ($data) && !empty ($data['active']) && array_key_exists ('menu', $data) && is_array ($data['menu']) && array_key_exists ('context', $data['menu']) && is_array ($data['menu']['context']))
              {
                foreach ($data['menu']['context'] as $key => $point)
                {
                  $plugin_items .= "
            <a href=# id=\"href_plugin_".$key."\" onClick=\"if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('".$mgmt_config['url_path_plugin'].$plugin_name."/".$point['page']."');\"><img src=\"".$point['icon']."\" name=\"img_plugin\" align=\"absmiddle\" style=\"border:0; width:16px; height:16px;\" class=\"hcmsIconOn\" />&nbsp;".getescapedtext ($point['name'])."</a><br />";
                }
              }
            }
            
            if ($plugin_items != "") echo $plugin_items."
          <hr />";
          }
          ?>
          <a href=# id="href_print" onClick="hcms_hideContextmenu(); window.print();"><img src="<?php echo getthemelocation(); ?>img/button_print.gif" id="img_print" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo getescapedtext ($hcms_lang['print'][$lang]); ?></a><br />     
          <a href=# id="href_refresh" onClick="document.location.reload();"><img src="<?php echo getthemelocation(); ?>img/button_view_refresh.gif" id="img_refresh" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo getescapedtext ($hcms_lang['refresh'][$lang]); ?></a>
        </td>
      </tr>    
    </table>
  </form>
</div>

<!-- Detail View -->
<div id="detailviewLayer" style="position:fixed; top:0; left:0; bottom:0; margin:0; padding:0; width:100%; z-index:1; visibility:visible;">
  <table id="objectlist_head" cellpadding="0" cellspacing="0" style="border:0; width:100%; height:20px; table-layout:fixed;">
    <tr>
      <td id="c0" onClick="hcms_sortTable(0);" class="hcmsTableHeader" style="width:278px; white-space:nowrap;">&nbsp;<?php echo getescapedtext ($hcms_lang['name'][$lang]); ?>&nbsp;</td>
    <?php
    if (!$is_mobile)
    {
    ?>
      <td id="c1" onClick="hcms_sortTable(1);" class="hcmsTableHeader" style="width:248px; white-space:nowrap;">&nbsp;<?php echo getescapedtext ($hcms_lang['location'][$lang]); ?>&nbsp;</td> 
    <?php
      if (is_array ($objectlistcols_reduced))
      {
        $i = 2;
        
        foreach ($objectlistcols_reduced as $key => $active)
        {
          if ($i < (sizeof ($objectlistcols_reduced) + 1)) $style_td = "width:113px; white-space:nowrap;";
          else $style_td = "white-space:nowrap;";
          
          $sortnumeric = "";
          
          if ($active == 1)
          {
            if ($key == 'createdate')
            {
              $title = getescapedtext ($hcms_lang['date-created'][$lang]);
            }
            elseif ($key == 'modifieddate')
            {
              $title = getescapedtext ($hcms_lang['date-modified'][$lang]);
            }
            elseif ($key == 'filesize')
            {
              $title = getescapedtext ($hcms_lang['size-in-kb'][$lang]);
              $sortnumeric = ", true";
            }
            elseif ($key == 'type')
            {
              $title = getescapedtext ($hcms_lang['type'][$lang]);
            }
            elseif ($key == 'owner')
            {
              $title = getescapedtext ($hcms_lang['owner'][$lang]);
            }
            else
            {
              // use label
              if (!empty ($labels_reduced[$key])) $title = $labels_reduced[$key];
              // use text ID
              else $title = ucfirst (str_replace ("_", " ", substr ($key, 5)));
            }
            
            echo "
      <td id=\"c".$i."\" onClick=\"hcms_sortTable(".$i.$sortnumeric.");\" class=\"hcmsTableHeader\" style=\"".$style_td."\">&nbsp;".$title."&nbsp;</td>";

            $i++;
          }
        }
      }
      ?>
      <td class="hcmsTableHeader" style="width:16px;">&nbsp;</td>
    <?php } ?>
    </tr>
  </table>

  <div id="objectLayer" style="position:fixed; top:20px; left:0; bottom:0; margin:0; padding:0; width:100%; z-index:2; visibility:visible; overflow-x:hidden; overflow-y:scroll;">
    <table id="objectlist" name="objectlist" cellpadding="0" cellspacing="0" style="border:0; width:100%; table-layout:fixed;">
    <?php 
    echo $listview;
    ?>
    </table>
    <br /><div id="detailviewReset" style="width:100%; height:2px; z-index:3; visibility:visible;" onMouseOver="hcms_hideContextmenu();"></div>
  </div>
</div>

<!-- Gallery View -->
<div id="galleryviewLayer" style="position:fixed; top:0; left:0; bottom:0; width:100%; z-index:1; visibility:hidden; overflow-y:scroll;">
<?php
if ($galleryview != "")
{
  echo "
  <table id=\"objectgallery\" name=\"objectgallery\" border=\"0\" cellpadding=\"5\" width=\"98%\" align=\"center\">
    <tr>"; 
  
  while (!is_int ($items_row / $table_cells))
  {
    $items_row++;
    $galleryview .= "
      <td onMouseOver=\"hcms_resetContext();\">&nbsp;</td>";
  }
  
  echo $galleryview;
  echo "
    </tr>
  </table>";
}
?>
  <br /><div style="width:100%; height:2px; z-index:0; visibility:visible;" onMouseOver="hcms_hideContextmenu();"></div>
</div>

<!-- initalize -->
<script language="JavaScript">
toggleview (explorerview);
$("#objectlist_head").colResizable({liveDrag:true, onDrag: resizecols});
</script>

</body>
</html>
