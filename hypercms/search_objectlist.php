<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("include/session.inc.php");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");
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
$date_modified = getrequest ("date_modified");
$year_from = getrequest ("year_from", "numeric");
$month_from = getrequest ("month_from", "numeric");
$day_from = getrequest ("day_from", "numeric");
$year_to = getrequest ("year_to", "numeric");
$month_to = getrequest ("month_to", "numeric");
$day_to = getrequest ("day_to", "numeric");
$template = getrequest ("template", "objectname");
$object_id = getrequest ("object_id");
$container_id = getrequest ("container_id");
$geo_border_sw = getrequest ("geo_border_sw");
$geo_border_ne = getrequest ("geo_border_ne");
$maxhits = getrequest ("maxhits", "numeric");
// just for image search
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

// get publication and category
if ($action != "user_files" && $action != "base_search") $site = getpublication ($search_dir);
$cat = getcategory ($site, $search_dir); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

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
$date_from = "";
$date_to = "";
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
  // modified date
  if ($date_modified == "yes")
  {
    $date_from = $year_from."-".$month_from."-".$day_from;
    $date_to = $year_to."-".$month_to."-".$day_to;
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
    $search_filename = $search_expression;
  }
  
  // search for certain object types/formats
  if (!is_array ($search_format)) $search_format = "";
  
  // check permissions
  if ($action == "base_search" || ($cat == "comp" && checkglobalpermission ($site, 'component')) || ($cat == "page" && checkglobalpermission ($site, 'page')))
  {
    if ($action == "base_search") 
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
    else $search_dir_esc = convertpath ($site, $search_dir, $cat);
    
    // max. hits
    if ($maxhits > 1000) $maxhits = 1000;
    elseif (empty ($maxhits)) $maxhits = 100;

    // start search
    if ($replace_expression == "") $object_array = rdbms_searchcontent ($search_dir_esc, $exclude_dir_esc, $search_format, $date_from, $date_to, $template, $search_textnode, $search_filename, "", $search_imagewidth, $search_imageheight, $search_imagecolor, $search_imagetype, $geo_border_sw, $geo_border_ne, $maxhits);
    // start search and replace
    elseif ($setlocalpermission['create'] == 1) $object_array = rdbms_replacecontent ($search_dir_esc, $search_format, $date_from, $date_to, $search_expression, $replace_expression, $user);
  }
}

// create view of items
$table_cells = "5"; //How many images/folders in each row do you want? // Looks best with 3  

// Makes the tables look nice
if ($table_cells == "1") $cell_width = "100%";
elseif ($table_cells == "2") $cell_width = "50%";  
elseif ($table_cells == "3") $cell_width = "33%";    
elseif ($table_cells == "4") $cell_width = "25%";      
elseif ($table_cells == "5") $cell_width = "20%";
elseif ($table_cells == "6") $cell_width = "16%";
else $cell_width="10%";  

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
          $item_info = getfileinfo ($item_site, $location.$folder."/.folder", $item_cat);
          $folder_name = $item_info['name'];
          
          // check access permission
          $ownergroup = accesspermission ($item_site, $location.$folder."/", $item_cat);
          $setlocalpermission = setlocalpermission ($item_site, $ownergroup, $item_cat);
          
          if ($ownergroup != false && $setlocalpermission['root'] == 1 && valid_locationname ($location) && valid_objectname ($folder) && is_dir ($location.$folder))
          {                  
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
                
                if ($result['user'] != "") $usedby = $result['user'];
                else $usedby = "";          
              }
              
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
            
            // listview - view option for locked multimedia objects
            if ($item_info['published'] == false) $class_image = "class=\"hcmsIconList hcmsIconOff\"";
            else $class_image = "class=\"hcmsIconList\"";            
            
            // refresh sidebar
            if (!$is_mobile) $sidebarclick = "if (sidebar) hcms_loadSidebar();";
            else $sidebarclick = "";
            // onclick for marking objects
            $selectclick = "onClick=\"hcms_selectObject('".$items_row."', event); hcms_updateControlObjectListMenu(); ".$sidebarclick."\" ";
            // open folder
            $openFolder = "onDblClick=\"parent.location.href='frameset_objectlist.php?site=".url_encode($item_site)."&cat=".url_encode($item_cat)."&location=".url_encode($location_esc.$folder)."/&token=".$token."';\" ";
            // set context
            $hcms_setObjectcontext = "onMouseOver=\"hcms_setObjectcontext('".$item_site."', '".$item_cat."', '".$location_esc."', '.folder', '".$folder_name."', 'Folder', '', '".$folder."', '', '".$token."');\" onMouseOut=\"hcms_resetContext();\" ";
            
            $style = "style=\"display:block;\" ";
            
            // listview - view option for locked folders
            if ($usedby != "")
            {
              $item_info['icon'] = "folderlock.gif";
              $item_info['icon_large'] = "folderlock.png";
            }             
            
            $listview .= "<tr id=g".$items_row." ".$selectclick." style=\"cursor:pointer\" align=\"left\">
                           <td id=h".$items_row."_0 width=\"280\" nowrap=\"nowrap\">
                             <input id=\"objectpath\" type=hidden value=\"".$location_esc.$folder."\">
                             <div ".$hcms_setObjectcontext." ".$style." ".$openFolder." title=\"".$folder_name."\">
                               <img src=\"".getthemelocation()."img/".$item_info['icon']."\" align=\"absmiddle\" class=\"hcmsIconList\" />&nbsp;".$dlink_start.showshorttext($folder_name, 30).$dlink_end."&nbsp;
                             </div>
                            </td>\n";
            if (!$is_mobile) $listview .= "<td id=h".$items_row."_1 width=\"250\" nowrap=\"nowrap\"><span ".$hcms_setObjectcontext." ".$style." title=\"".$item_location."\">&nbsp;".showshorttext($item_location, -32)."</span></td>
                            <td id=h".$items_row."_2 width=\"120\" nowrap=\"nowrap\"><span ".$hcms_setObjectcontext." ".$style.">&nbsp;&nbsp;".@date ("Y-m-d H:i", @filemtime ($location.$folder))."</span></td>
                            <td id=h".$items_row."_3 nowrap=\"nowrap\"><span ".$hcms_setObjectcontext." ".$style.">&nbsp;&nbsp;".$item_site."</span></td>\n";
            $listview .= "</tr>\n";
        
            $galleryview .= "<td id=t".$items_row." ".$selectclick." width=\"".$cell_width."\" align=\"center\" valign=\"bottom\">
                              <div ".$hcms_setObjectcontext." ".$openFolder." title=\"".$folder_name."\" style=\"cursor:pointer; display:block;\">".
                                $dlink_start."
                                  <div id=\"w".$items_row."\" class=\"hcmsThumbnailWidth".$temp_explorerview."\"><img src=\"".getthemelocation()."img/".$item_info['icon_large']."\" style=\"border:0;\" /></div>
                                  ".showshorttext($folder_name, 18, true)."
                                ".$dlink_end."
                              </div>
                            </td>\n";
            $items_row++;
          }
        }
        // clean from db
        else
        {
          //rdbms_deleteobject ($location_esc.$folder);
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
          $item_info = getfileinfo ($item_site, $location.$object, $item_cat);
          $object_name = $item_info['name'];
          
          // read file
          $objectdata = loadfile ($location, $object);
          
          if ($objectdata != false)
          {
            // get name of content file and load content container
            $contentfile = getfilename ($objectdata, "content");
            $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml"));  
                  
            // read meta data of media file
            if ($contentfile != false)
            {
              $result = getcontainername ($contentfile);
              
              if ($result['user'] != "") $usedby = $result['user'];
              else $usedby = "";          
            }
          }            

          // check access permission
          $ownergroup = accesspermission ($item_site, $location, $item_cat);
          $setlocalpermission = setlocalpermission ($item_site, $ownergroup, $item_cat);

          if ($ownergroup != false && $setlocalpermission['root'] == 1 && valid_locationname ($location) && valid_objectname ($object) && is_file ($location.$object))
          {
            $metadata = "";
                    
            // page
            if ($item_info['type'] == "Page") $item_type = $hcms_lang['object-page'][$lang];
            // component
            elseif ($item_info['type'] == "Component") $item_type = $hcms_lang['object-component'][$lang];    
            // multimedia object 
            else $item_type = $hcms_lang['file'][$lang]." (".$item_info['type'].")";

            // get name of media file
            $mediafile = getfilename ($objectdata, "media");

            if ($mediafile != false)
            {
              // location of file
              $mediadir = getmedialocation ($item_site, $mediafile, "abs_path_media");
                          
              // get file time
              if (is_file ($mediadir.$item_site."/".$mediafile)) $file_time = date ("Y-m-d H:i", @filemtime ($mediadir.$item_site."/".$mediafile));
              else $file_time = "-";
              
              // get file size
              if ($mgmt_config['db_connect_rdbms'] != "")
              {
                $media_info = rdbms_getmedia ($container_id);
                $file_size = $media_info['filesize'];
                $file_size = number_format ($file_size, 0, "", "."); 
              }
              elseif (is_file ($mediadir.$item_site."/".$mediafile))
              {
                $file_size = round (@filesize ($mediadir.$item_site."/".$mediafile) / 1024);
                if ($file_size == 0) $file_size = 1;
                $file_size = number_format ($file_size, 0, "", ".");                 
              }
              else
              {
                $file_size = "-";
              }
              
              // media file info
              $media_info = getfileinfo ($item_site, $mediafile, $item_cat);
              
              // read meta data of media file
              if (!$is_mobile && !$temp_sidebar) $metadata = getmetadata ("", "", $contentfile, " \r\n");
              
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
              $file_time = date ("Y-m-d H:i", @filemtime ($location.$object));
              
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
        
            // open on double click
            $openObject = "onDblClick=\"hcms_openWindow('frameset_content.php?ctrlreload=yes&site=".url_encode($item_site)."&cat=".url_encode($item_cat)."&location=".url_encode($location_esc)."&page=".url_encode($object)."&token=".$token."','".$container_id."','status=yes,scrollbars=no,resizable=yes', '800', '600');\"";
            // refresh sidebar
            if (!$is_mobile) $sidebarclick = "if (sidebar) hcms_loadSidebar();";
            else $sidebarclick = "";
            // onclick for marking objects
            $selectclick = "onClick=\"hcms_selectObject('".$items_row."', event); hcms_updateControlObjectListMenu(); ".$sidebarclick."\" ";
            // set context
            $hcms_setObjectcontext = "onMouseOver=\"hcms_setObjectcontext('".$item_site."', '".$item_cat."', '".$location_esc."', '".$object."', '".$object_name."', '".$item_info['type']."', '".$mediafile."', '', '', '".$token."');\" onMouseOut=\"hcms_resetContext();\" ";
            
            $style = "style=\"display:block;\" ";

            // metadata
            $metadata = $hcms_lang['name'][$lang].": ".$object_name." \r\n".$hcms_lang['date-modified'][$lang].": ".$file_time." \r\n".$hcms_lang['size-in-kb'][$lang].": ".$file_size." \r\n".$metadata;
            
            // listview - view option for un/published objects
            if ($item_info['published'] == false) $class_image = "class=\"hcmsIconList hcmsIconOff\"";
            else $class_image = "class=\"hcmsIconList\"";
            
            // listview - view option for locked objects
            if ($usedby != "")
            {
              $item_info['icon'] = "filelock.gif";
              $item_info['icon_large'] = "filelock.png";
            }         
            
            $listview .= "<tr id=g".$items_row." ".$selectclick." align=\"left\" style=\"cursor:pointer;\">
                           <td id=h".$items_row."_0 width=\"280\" nowrap=\"nowrap\">
                             <input id=\"objectpath\" type=hidden value=\"".$location_esc.$object."\" />
                             <div ".$hcms_setObjectcontext." ".$style." ".$openObject." title=\"".$metadata."\">
                               <img src=\"".getthemelocation()."img/".$item_info['icon']."\" align=\"absmiddle\" ".$class_image." />&nbsp;".$dlink_start.showshorttext($object_name, 30).$dlink_end."&nbsp;
                             </div>
                           </td>\n";
            if (!$is_mobile) $listview .= "<td id=h".$items_row."_1 width=\"250\" nowrap=\"nowrap\"><span ".$hcms_setObjectcontext." ".$style." title=\"".$item_location."\">&nbsp;&nbsp;".showshorttext($item_location, -32)."</span></td>
                           <td id=h".$items_row."_2 width=\"120\" nowrap=\"nowrap\"><span ".$hcms_setObjectcontext." ".$style.">&nbsp;&nbsp;".$file_time."</span></td>
                           <td id=h".$items_row."_3 nowrap=\"nowrap\"><span ".$hcms_setObjectcontext." ".$style.">&nbsp;&nbsp;".$item_site."</span></td>\n";
            $listview .= "</tr>\n";  
                           
        	  // if there is a thumb file, display the thumb
          	if ($mediafile != false && $mediadir != "")
            {              
              // try to create thumbnail if not available
              if ($mgmt_config['recreate_preview'] == true && !file_exists ($mediadir.$item_site."/".$media_info['filename'].".thumb.jpg") && !file_exists ($mediadir.$item_site."/".$media_info['filename'].".thumb.flv"))
              {
                createmedia ($item_site, $mediadir.$item_site."/", $mediadir.$item_site."/", $media_info['file'], "", "thumbnail");
              }            
              
              if (@is_file ($mediadir.$item_site."/".$media_info['filename'].".thumb.jpg") && @filesize ($mediadir.$item_site."/".$media_info['filename'].".thumb.jpg") > 400)
              {
                $imgsize = getimagesize ($mediadir.$item_site."/".$media_info['filename'].".thumb.jpg");
                
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
                // default value
                else
                {
                  $ratio = "Width";
                }
                          
                // galleryview - view option for locked multimedia objects
                if ($item_info['published'] == false) $class_image = "class=\"hcmsIconOff\"";
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
                
                $thumbnail = "<div ".$div_id." ".$class_size."><img src=\"".$mgmt_config['url_path_cms']."explorer_wrapper.php?site=".url_encode($item_site)."&media=".url_encode($item_site."/".$media_info['filename'].".thumb.jpg")."&token=".hcms_crypt($item_site."/".$media_info['filename'].".thumb.jpg")."\" ".$class_image." /></div>";
              }
              // display file icon if thumbnail fails 
              else
              {
                // galleryview - view option for locked multimedia objects
                if ($item_info['published'] == false) $class_image = "class=\"hcmsIconOff\"";
                else $class_image = "";
                        
          		  $thumbnail = "<div id=\"w".$items_row."\" class=\"hcmsThumbnail".$ratio.$temp_explorerview."\"><img src=\"".getthemelocation()."img/".$item_info['icon_large']."\" style=\"border:0;\" ".$class_image." /></div>";
            	}           
          	}
            // display file icon for non multimedia objects 
            else
            {
              // galleryview - view option for locked multimedia objects
              if ($item_info['published'] == false) $class_image = "class=\"hcmsIconOff\"";
              else $class_image = "";
                      
        		  $thumbnail = "<div id=\"w".$items_row."\" class=\"hcmsThumbnail".$ratio.$temp_explorerview."\"><img src=\"".getthemelocation()."img/".$item_info['icon_large']."\" style=\"border:0;\" ".$class_image." /></div>";
          	}
            
            // if linking is used, display download buttons
            if ($mediafile != false && is_array (getsession ('hcms_linking')) && $setlocalpermission['root'] == 1 && $setlocalpermission['download'] == 1)
            {
              if (!$is_mobile) $width = "160px";
              else $width = "180px";
          
              $linking_buttons = "<div style=\"width:".$width."; margin-left:auto; margin-right:auto; padding:0; text-align:center;\"><a href=\"".$mgmt_config['url_path_cms']."explorer_wrapper.php?name=".$object_name."&media=".$item_site."/".$mediafile."&token=".hcms_crypt($item_site."/".$mediafile)."\" target=\"_blank\"><button class=\"hcmsButtonDownload\">".$hcms_lang['view'][$lang]."</button></a><a href=\"".$mgmt_config['url_path_cms']."explorer_download.php?name=".$object_name."&media=".$item_site."/".$mediafile."&token=".hcms_crypt($item_site."/".$mediafile)."\"><button class=\"hcmsButtonDownload\">".$hcms_lang['download'][$lang]."</button></a></div>";
            }
            else $linking_buttons = "";            
  
            $galleryview .= "<td id=t".$items_row." ".$selectclick." width=\"".$cell_width."\" align=\"center\" valign=\"bottom\">
                              <div ".$openObject." ".$hcms_setObjectcontext." title=\"".$metadata."\" style=\"cursor:pointer; display:block;\">".
                                $dlink_start."
                                 ".$thumbnail."
                                 ".showshorttext($object_name, 18, true)."
                                ".$dlink_end."
                              </div>
                              ".$linking_buttons."
                            </td>\n";
                            
            $items_row++;
          }
        }
        // clean from db
        else
        {
          //rdbms_deleteobject ($objectpath);
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
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/navigator.css">
<script src="javascript/main.js" language="JavaScript" type="text/javascript"></script>
<script src="javascript/contextmenu.js" language="JavaScript" type="text/javascript"></script>
<script type="text/javascript" src="javascript/jquery/jquery-1.9.1.min.js"></script>
<script type="text/javascript" src="javascript/chat.js"></script>
<script language="JavaScript">
<!--
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
  return confirm(hcms_entity_decode("<?php echo $hcms_lang['are-you-sure-you-want-to-remove-the-item'][$lang]; ?>"));
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

// load control frame
parent.frames['controlFrame'].location.href='control_objectlist_menu.php?virtual=1&from_page=search';
//-->
</script>
</head>

<body id="hcmsWorkplaceObjectlist" style="overflow:hidden;" class="hcmsWorkplaceObjectlist"">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['hold-ctrl-key-select-objects-by-click'][$lang]."<br/>".$hcms_lang['hold-shift-key-select-a-group-of-objects-by-2-clicks'][$lang]."<br/>".$hcms_lang['press-alt-key-switch-to-download-links-to-copy-paste-into-e-mails'][$lang], $lang, 3, "position:fixed; top:30px; right:30px;"); ?>

<div id="contextLayer" style="position:absolute; width:150px; height:260px; z-index:10; left:20px; top:20px; visibility:hidden;"> 
  <form name="contextmenu_object" action="" method="post" target="_blank">
    <input type="hidden" name="contextmenustatus" value="" />
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
          <a href=# id="href_fav_delete" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('favorites_delete');"><img src="<?php echo getthemelocation(); ?>img/button_favorites_new.gif" id="img_fav_delete" align="absmiddle" border=0 />&nbsp;<?php echo $hcms_lang['delete-favorite'][$lang]; ?></a><br />        
          <hr />
          <?php } ?>  
          <?php if ($action == "checkedout") { ?>
          <a href=# id="href_unlock" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('checkin');"><img src="<?php echo getthemelocation(); ?>img/button_file_unlock.gif" id="img_unlock" align="absmiddle" border=0 />&nbsp;<?php echo $hcms_lang['check-in'][$lang]; ?></a><br />        
          <hr />
          <?php } ?>  
          <a href=# id="href_preview" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('preview');"><img src="<?php echo getthemelocation(); ?>img/button_file_preview.gif" id="img_preview" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['preview'][$lang]; ?></a><br />  
          <a href=# id="href_cmsview" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('cmsview');"><img src="<?php echo getthemelocation(); ?>img/button_file_edit.gif" id="img_cmsview" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['edit'][$lang]; ?></a><br />
          <a href=# id="href_notify" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('notify');"><img src="<?php echo getthemelocation(); ?>img/button_notify.gif" id="img_notify" align="absmiddle" border=0 class="hcmsIconOn">&nbsp;<?php echo $hcms_lang['notify-me'][$lang]; ?></a><br />
          <?php if (isset ($mgmt_config['chat']) && $mgmt_config['chat'] == true) { ?> 
          <a href=# id="href_chat" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('chat');"><img src="<?php echo getthemelocation(); ?>img/button_chat.gif" id="img_chat" align="absmiddle" border=0 class="hcmsIconOn">&nbsp;<?php echo $hcms_lang['send-to-chat'][$lang]; ?></a><br />
          <?php } ?>   
          <hr />
          <a href=# id="href_delete" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('delete');"><img src="<?php echo getthemelocation(); ?>img/button_delete.gif" id="img_delete" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['delete'][$lang]; ?></a><br />     
          <hr />
          <a href=# id="href_cut" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('cut');"><img src="<?php echo getthemelocation(); ?>img/button_file_cut.gif" id="img_cut" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['cut'][$lang]; ?></a><br />  
          <a href=# id="href_copy" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('copy');"><img src="<?php echo getthemelocation(); ?>img/button_file_copy.gif" id="img_copy" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['copy'][$lang]; ?></a><br />  
          <a href=# id="href_copylinked" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('linkcopy');"><img src="<?php echo getthemelocation(); ?>img/button_file_copylinked.gif" id="img_copylinked" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['connected-copy'][$lang]; ?></a><br />   
          <hr />
          <a href=# id="href_publish" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('publish');"><img src="<?php echo getthemelocation(); ?>img/button_file_publish.gif" id="img_publish" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['publish'][$lang]; ?></a><br />  
          <a href=# id="href_unpublish" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('unpublish');"><img src="<?php echo getthemelocation(); ?>img/button_file_unpublish.gif" id="img_unpublish" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['unpublish'][$lang]; ?></a><br />
          <hr />
          <a href=# id="href_print" onClick="hcms_hideContextmenu(); window.print();"><img src="<?php echo getthemelocation(); ?>img/button_print.gif" id="img_print" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['access-to-link-management-failed-record-or-database-is-locked-or-missing'][$lang]; ?></a><br />     
          <a href=# id="href_refresh" onClick="document.location.reload();"><img src="<?php echo getthemelocation(); ?>img/button_view_refresh.gif" id="img_refresh" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['refresh'][$lang]; ?></a>
        </td>
      </tr>    
    </table>
  </form>
</div>

<div id="detailviewLayer" style="position:absolute; top:0px; left:0px; width:100%; height:100%; z-index:1; visibility:visible;">
  <table cellpadding="0" cellspacing="0" cols="4" style="border:0; width:100%; height:20px; table-layout:fixed;">
    <tr>
      <td width="280" onClick="hcms_sortTable(0);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo $hcms_lang['name'][$lang]; ?>
      </td>
      <?php if (!$is_mobile) { ?>
      <td width="250" onClick="hcms_sortTable(1);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo $hcms_lang['location'][$lang]; ?>
      </td>          
      <td width="120" onClick="hcms_sortTable(2);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo $hcms_lang['date-modified'][$lang]; ?>
      </td>
      <td onClick="hcms_sortTable(3);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo $hcms_lang['publication'][$lang]; ?>
      </td>
      <td width="16" class="hcmsTableHeader">
        &nbsp;
      </td>
       <?php } ?>
    </tr>
  </table>

  <div id="objectLayer" style="position:absolute; Top:20px; Left:0px; width:100%; height:100%; z-index:2; visibility:visible; overflow:scroll;">
    <table id="objectlist" name="objectlist" cellpadding="0" cellspacing="0" cols="4" style="border:0; width:100%; table-layout:fixed;">
<?php 
echo $listview;
?>
    </table>
    <br /><div style="width:100%; height:2px; z-index:3; visibility:visible;" onMouseOver="hcms_hideContextmenu();"></div>
  </div>
</div>

<div id="galleryviewLayer" style="position:absolute; Top:0px; Left:0px; width:100%; height:100%; z-index:1; visibility:hidden; overflow:scroll;">
<?php
if ($galleryview != "")
{
  echo "<table id=\"objectgallery\" name=\"objectgallery\" border=\"0\" cellpadding=\"5\" width=\"98%\" align=\"center\">\n";
  echo "<tr>\n"; 
  
  while (!is_int ($items_row / $table_cells))
  {
    $items_row++;
    $galleryview .= "<td>&nbsp;</td>\n";
  }
  
  echo $galleryview;
  echo "</tr>\n";
  echo "</table>\n";
}
?>
  <br /><div style="width:100%; height:2px; z-index:0; visibility:visible;" onMouseOver="hcms_hideContextmenu();"></div>
</div>

<!-- toggle view -->
<script language="JavaScript">
<!--
toggleview (explorerview);
//-->
</script>

</body>
</html>
