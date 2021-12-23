<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
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
$location = getrequest_esc ("location", "locationname");
$folder = getrequest ("folder", "objectname");
$virtual = getrequest ("virtual", "numeric");
$start = getrequest ("start", "numeric", 0);
$filter = getrequest ("filter", "array");
$column = getrequest ("column", "array");
$token = getrequest ("token");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// initialize
$resetcols = false;
$objects_total = 0;
$folder_array = array();
$object_array = array();
$galleryview = "";
$listview = "";
$items_row = -1;
$thumbnailsize_small = 120;
$thumbnailsize_medium = 160;
$thumbnailsize_large = 180;

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// plugin config
if (is_file ($mgmt_config['abs_path_data']."config/plugin.global.php"))
{
  require ($mgmt_config['abs_path_data']."config/plugin.global.php");
}

// ------------------------------ permission section --------------------------------

// set local permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

// we check for general root element access since localpermissions are checked later
if (!valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($cat) || ($cat == "comp" && !checkglobalpermission ($site, 'component')) || ($cat == "page" && !checkglobalpermission ($site, 'page'))) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------
  
// set filter
setfilter ($filter);

// set columns
if ((is_array ($column) || empty ($column)) && checktoken ($token, $user))
{
  if (empty ($column)) $column = array();
  
  // replace defintion of publication
  if (empty ($objectlistcols)) $objectlistcols = array();
  if (empty ($objectlistcols[$site])) $objectlistcols[$site] = array();
  
  $objectlistcols[$site][$cat] = $column;
  
  // save column definition
  savefile ($mgmt_config['abs_path_data']."checkout/", $user.".objectlistcols.json", json_encode ($objectlistcols));
  
  // set in session
  setsession ("hcms_objectlistcols", $objectlistcols);

  // reset width of all columns
  $resetcols = true;
}

// write and close session (important for non-blocking of other frames)
if (session_id() != "") session_write_close();

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// create secure token
$token = createtoken ($user);

// default value for inital max items in list
if (empty ($mgmt_config['explorer_list_maxitems'])) $mgmt_config['explorer_list_maxitems'] = 100; 

// define next max number of items on the list 
if (is_numeric ($start)) $end = $start + $mgmt_config['explorer_list_maxitems'];
else $end = $mgmt_config['explorer_list_maxitems'];

// define variables depending on category
if (strtolower ($cat) == "page") 
{
  $access = $pageaccess;
  $itemname = "page";
}  
elseif (strtolower ($cat) == "comp") 
{
  $access = $compaccess;
  $itemname = "component";
}

// collect all objects for list
if (valid_locationname ($location))
{  
  // generate page or component list using access permission data
  if (accesspermission ($site, $location, $cat) == false && linking_valid() == false)
  {
    if (!empty ($access) && is_array ($access) && sizeof ($access) > 0 && is_array ($access[$site]))
    {
      reset ($access);

      foreach ($access[$site] as $group=>$value)
      {
        if ($localpermission[$site][$group][$itemname] == 1 && $value != "")
        {    
          // cut of last '|'
          $access_str = substr ($value, 0, strlen ($value) - 1);
      
          // create folder array
          $folder_array_new = explode ("|", $access_str);
          
          // merge folders of different groups
          $folder_array = array_merge ($folder_array, $folder_array_new);
        }
      }
       
      // remove double entries 
      $folder_array = array_unique ($folder_array);
      
      $objects_total = sizeof ($folder_array);
    }
  }    
  // show requested location
  elseif (valid_locationname ($location) && is_dir ($location))
  {
    $scandir = scandir ($location);
    
    if ($scandir)
    {
      foreach ($scandir as $file) 
      {
        if ($location.$file != "" && $file != "." && $file != ".." && substr ($file, -8) != ".recycle") 
        {
          // if linking is not used or object is in linking scope
          if (linking_inscope ($site, $location, $file, $cat) == true)
          {
            if (is_dir ($location.$file))
            {
              $group_array = accesspermission ($site, $location.$file."/", $cat);
              $setlocalpermission = setlocalpermission ($site, $group_array, $cat);

              if ($setlocalpermission['root'] == 1)
              {
                // remove _gsdata_ directory created by Cyberduck
                if ($file == "_gsdata_")
                {
                  deletefolder ($site, $location, $file, $user);
                }
                else
                {
                  $folder_array[] = $file;            
                  $objects_total++;
                }
              }
            }
            elseif (is_file ($location.$file) && !is_hiddenfile ($file))
            {
              $object_array[] = $file;            
              $objects_total++;     
            }
          }
          // forward if access linking is used and object is out of linking scope
          elseif (linking_valid () == true) 
          {
            header ("Location: search_objectlist.php?action=linking");
          }
        }
      }
    }
  }
}

// ---------------------------------------------------- folder items ----------------------------------------------------
if (is_array ($folder_array) && sizeof ($folder_array) > 0)
{
  natcasesort ($folder_array);
  reset ($folder_array);

  foreach ($folder_array as $folder)
  {
    // break loop if maximum has been reached
    if (($items_row + 1) >= $end) break;

    if ($folder != "")
    {
      // check for location path inside folder variable
      if (substr_count ($folder, "/") >= 1)
      {
        $location = getlocation ($folder);
        $folder = getobject ($folder);
      }
      
      // folder information
      $file_info = getfileinfo ($site, $location.$folder."/.folder", $cat);
      
      // eventsystem
      if (!empty ($eventsystem['onobjectlist_pre']) && empty ($eventsystem['hide'])) 
        onobjectlist_pre ($site, $cat, $location, $folder, $user);        
      
      // if folder exists
      if (valid_locationname ($location) && valid_objectname ($folder) && is_dir ($location.$folder) && !$file_info['deleted'])
      {
        // count valid objects
        $items_row++;

        // skip rows for paging
        if (!empty ($mgmt_config['explorer_paging']) && $items_row < $start) continue;

        // convert location
        $location_esc = convertpath ($site, $location, $cat);

        // get folder name
        $folder_name = $file_info['name'];
        
        $metadata = "";
        $file_size = "";
        $file_created = "";
        $file_modified = "";
        $file_published = "";
        $file_owner = "";
        $usedby = "";
    
        // read file
        $objectdata = loadfile ($location.$folder."/", ".folder");
        
        // folder file exists and can be loaded
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
          }
          
          // get metadata of container
          if (!empty ($objectlistcols[$site][$cat]) && is_array ($objectlistcols[$site][$cat]) && sizeof ($objectlistcols[$site][$cat]) > 0)
          {
            $container_info = getmetadata_container ($container_id, array_keys ($objectlistcols[$site][$cat]));
            
            if (!empty ($container_info) && is_array ($container_info))
            {  
              if (!empty ($container_info['createdate']) && is_date ($container_info['createdate'])) $file_created = date ("Y-m-d H:i", strtotime ($container_info['createdate']));
              if (!empty ($container_info['date']) && is_date ($container_info['date'])) $file_modified = date ("Y-m-d H:i", strtotime ($container_info['date']));
              if (!empty ($container_info['publishdate']) && is_date ($container_info['publishdate'])) $file_published = date ("Y-m-d H:i", strtotime ($container_info['publishdate']));
              if (!empty ($container_info['user'])) $file_owner = $container_info['user'];
            }
          }
        }
        // create folder file if it does not exist
        else
        {
          createobject ($site, $location.$folder."/", ".folder", "default.meta.tpl", "sys");
        }

        // link for copy & paste of download links (not if an access link is used)
        if (!empty ($mgmt_config[$site]['sendmail']) && $setlocalpermission['download'] == 1 && linking_valid() == false)
        {
          $dlink_start = "<a id=\"link_".$items_row."\" data-linktype=\"download\" data-objectpath=\"".$location_esc.$folder."\" data-href=\"\">";
          $dlink_end = "</a>";
        }
        else
        {
          $dlink_start = "<a id=\"link_".$items_row."\" data-linktype=\"none\" data-objectpath=\"".$location_esc.$folder."\" data-href=\"javascript:void(0);\">";
          $dlink_end = "</a>";
        }
        
        // fallback for date modified
        if (empty ($file_modified))
        {
          // get file time
          $file_modified = date ("Y-m-d H:i", @filemtime ($location.$folder));
        }

        // onclick for marking objects  
        $selectclick = "onClick=\"hcms_selectObject(this.id, event); hcms_updateControlObjectListMenu();\" ";

        // open folder
        $openFolder = "onDblClick=\"parent.location='frameset_objectlist.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc.$folder)."/';\" ";
        
        // set context
        $hcms_setObjectcontext = "onMouseOver=\"hcms_setObjectcontext('".$site."', '".$cat."', '".$location_esc."', '.folder', '".$folder_name."', 'Folder', '', '".$folder."', '', '".$token."');\" onMouseOut=\"hcms_resetContext();\" ";

        // if linking is used display download buttons
        $linking_buttons = "";

        // if mobile edition is used display navigate button
        if ($is_mobile && $setlocalpermission['root'] == 1)
        {   
          $linking_buttons .= "
          <button class=\"hcmsButtonDownload\" style=\"width:94%;\" onClick=\"parent.location='frameset_objectlist.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc.$folder)."/';\">".getescapedtext ($hcms_lang['navigate'][$lang])."</button>";
        }

        if ($linking_buttons != "")
        {   
          $linking_buttons = "
          <div style=\"width:100%; margin:0 auto; padding:0; text-align:center;\">".$linking_buttons."</div>";
        }

        // listview - view option for locked folders
        if (!empty ($usedby))
        {
          $file_info['icon'] = "folder_lock.png";
        }

        // drag events
        if ($setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
        {
          $dragevent = "draggable=\"true\" ondragstart=\"hcms_drag(event)\"";
        }
        else $dragevent = "";
        
        // metadata
        $metadata = getescapedtext ($hcms_lang['name'][$lang]).": ".$folder_name." \r\n".getescapedtext ($hcms_lang['date-modified'][$lang]).": ".showdate ($file_modified, "Y-m-d H:i", $hcms_lang_date[$lang])." \r\n".$metadata;             

        $listview .= "
                      <tr id=\"g".$items_row."\" style=\"cursor:pointer\" ".$selectclick.">
                       <td id=\"h".$items_row."_0\" class=\"hcmsCol0 hcmsCell\" style=\"width:280px;\">                
                         <div class=\"hcmsObjectListMarker\" ".$hcms_setObjectcontext." ".$openFolder." title=\"".$metadata."\" ondrop=\"hcms_drop(event)\" ondragover=\"hcms_allowDrop(event)\" ".$dragevent.">
                           ".$dlink_start."<img src=\"".getthemelocation()."img/".$file_info['icon']."\" class=\"hcmsIconList\" /> ".$folder_name.$dlink_end."
                         </div>
                       </td>";
                       
        if (!$is_mobile)
        {            
          if (!empty ($objectlistcols[$site][$cat]) && is_array ($objectlistcols[$site][$cat]))
          {
            $i = 1;
            
            foreach ($objectlistcols[$site][$cat] as $key => $active)
            {
              if ($i < sizeof ($objectlistcols[$site][$cat])) $style_td = "width:125px;";
              else $style_td = "";
              
              $style_div = "";
              
              if ($active == 1)
              {
                if ($key == 'createdate')
                {
                  $title = "<span style=\"display:none;\">".date ("YmdHi", strtotime ($file_created))."</span>".showdate ($file_created, "Y-m-d H:i", $hcms_lang_date[$lang]);
                }
                elseif ($key == 'modifieddate' || $key == 'date')
                {
                  $title = "<span style=\"display:none;\">".date ("YmdHi", strtotime ($file_modified))."</span>".showdate ($file_modified, "Y-m-d H:i", $hcms_lang_date[$lang]);
                }
                elseif ($key == 'publishdate')
                {
                  $title = "<span style=\"display:none;\">".date ("YmdHi", strtotime ($file_published))."</span>".showdate ($file_published, "Y-m-d H:i", $hcms_lang_date[$lang]);
                }
                elseif ($key == 'filesize')
                {
                  $title = "";
                  $style_div = "text-align:right; padding-right:5px;";
                }
                elseif ($key == 'type')
                {
                  $title = getescapedtext ($hcms_lang['folder'][$lang]);
                }
                elseif ($key == 'owner' || $key == 'user')
                {
                  $title = $file_owner;
                }
                else
                {
                  if (!empty ($container_info[$key])) $title = $container_info[$key];
                  else $title = "";
                }
                
                $listview .= "
                        <td id=\"h".$items_row."_".$i."\" class=\"hcmsCol".$i." hcmsCell\" style=\"".$style_td."\"><div ".$hcms_setObjectcontext." style=\"display:block; ".$style_div."\">".$title."</div></td>";
                
                $i++;
              }
            }
          }
        }
        
        $listview .= "</tr>";                       
    
        $galleryview .= "
                       <div id=\"t".$items_row."\" ".$selectclick." class=\"hcmsObjectUnselected\">
                          <div class=\"hcmsObjectGalleryMarker\" ".$hcms_setObjectcontext." ".$openFolder." title=\"".$folder_name."\" ondrop=\"hcms_drop(event)\" ondragover=\"hcms_allowDrop(event)\" ".$dragevent.">".
                            $dlink_start."
                              <div id=\"i".$items_row."\" class=\"hcmsThumbnailFrame hcmsThumbnail".$temp_explorerview."\" data-objectpath=\"".$location_esc.$folder."/\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" /></div>
                              <div class=\"hcmsItemName\">".showshorttext($folder_name, 18, true)."</div>
                            ".$dlink_end."
                          </div>
                          ".$linking_buttons."
                       </div>";
      }
      // object does not exist or user has no access permission 
      else $objects_total--;
    }
  }
}

// --------------------------------------------------------- object items ----------------------------------------------------
if (is_array ($object_array) && sizeof ($object_array) > 0)
{
  natcasesort ($object_array);
  reset ($object_array);

  foreach ($object_array as $objectpath)
  {
    // break loop if maximum has been reached
    if (($items_row + 1) >= $end) break;

    if ($objectpath != "")
    {
      // check for location path inside folder variable
      if (substr_count ($objectpath, "/") > 0)
      {
        $object = getobject ($objectpath);      
        $location = getlocation ($objectpath);
      }
      else $object = $objectpath;

      // convert location
      $location_esc = convertpath ($site, $location, $cat);  
      
      // correct file name
      $object = correctfile ($location, $object, $user);  
      $file_info = getfileinfo ($site, $location.$object, $cat);
      $object_name = $file_info['name'];

      $mediafile = false;
      $metadata = "";
      $file_size = "";
      $file_created = "";
      $file_modified = "";
      $file_published = "";
      $file_owner = "";
      $usedby = "";
    
      // eventsystem
      if (!empty ($eventsystem['onobjectlist_pre']) && empty ($eventsystem['hide'])) 
        onobjectlist_pre ($site, $cat, $location, $object, $user);  

      // if object exists
      if (valid_locationname ($location) && valid_objectname ($object) && is_file ($location.$object) && !$file_info['deleted'] && ($cat == "page" || objectfilter ($object)))       
      {
        // count valid objects
        $items_row++;

        // skip rows for paging
        if (!empty ($mgmt_config['explorer_paging']) && $items_row < $start) continue;

        // page
        if ($file_info['type'] == "Page") $file_type = getescapedtext ($hcms_lang['object-page'][$lang]);
        // component
        elseif ($file_info['type'] == "Component") $file_type = getescapedtext ($hcms_lang['object-component'][$lang]);
        // multimedia object 
        else $file_type = getescapedtext ($hcms_lang['file'][$lang])." (".$file_info['type'].")";

        // read file
        $objectdata = loadfile ($location, $object);

        // get name of media file
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
          }

          // get metadata of container
          if (is_array ($objectlistcols[$site][$cat]) && sizeof ($objectlistcols[$site][$cat]) > 0)
          {
            $container_info = getmetadata_container ($container_id, array_keys ($objectlistcols[$site][$cat]));
  
            if (!empty ($container_info) && is_array ($container_info))
            {
              if (!empty ($container_info['filesize'])) $file_size = number_format ($container_info['filesize'], 0, ".", " ");
              if (!empty ($container_info['createdate']) && is_date ($container_info['createdate'])) $file_created = date ("Y-m-d H:i", strtotime ($container_info['createdate']));
              if (!empty ($container_info['publishdate']) && is_date ($container_info['publishdate'])) $file_published = date ("Y-m-d H:i", strtotime ($container_info['publishdate']));
              if (!empty ($container_info['date']) && is_date ($container_info['date'])) $file_modified = date ("Y-m-d H:i", strtotime ($container_info['date']));
              if (!empty ($container_info['user'])) $file_owner = $container_info['user'];
            }
          }

          // get media file
          $mediafile = getfilename ($objectdata, "media");

          if ($mediafile != false)
          {
            // location of media file
            $mediadir = getmedialocation ($site, $mediafile, "abs_path_media");

            // fallback for file size and date modified
            if (empty ($file_size) && is_file ($mediadir.$site."/".$mediafile))
            {
              $file_size = round (@filesize ($mediadir.$site."/".$mediafile) / 1024);
              $file_size = number_format ($file_size, 0, ".", " ");              
              $file_modified = date ("Y-m-d H:i", @filemtime ($mediadir.$site."/".$mediafile));               
            }

            // media file info
            $media_info = getfileinfo ($site, $mediafile, $cat);

            // get metadata for media file
            if (!empty ($mgmt_config['explorer_list_metadata']) && !$is_mobile && !$temp_sidebar) $metadata = getmetadata ("", "", $contentfile, " \r\n");
            else $metadata = "";

            // link for copy & paste of download links (not if an access link is used)
            if (!empty ($mgmt_config[$site]['sendmail']) && $setlocalpermission['download'] == 1 && linking_valid() == false)
            {
              $dlink_start = "<a id=\"link_".$items_row."\" data-linktype=\"download\" data-objectpath=\"".$location_esc.$object."\" data-href=\"\">";
              $dlink_end = "</a>";
            }
            else
            {
              $dlink_start = "<a id=\"link_".$items_row."\" data-linktype=\"none\" data-objectpath=\"".$location_esc.$object."\" data-href=\"javascript:void(0);\">";
              $dlink_end = "</a>";
            }
          }
          // object without media file
          else
          {
            // get file time
            $file_modified = date ("Y-m-d H:i", @filemtime ($location.$object)); 
            
            // get file size
            $file_size = round (@filesize ($location.$object) / 1024);
            if ($file_size == 0) $file_size = 1;
            $file_size = number_format ($file_size, 0, ".", " ");

            // link for copy & paste of download links (not if an access link is used)
            if (!empty ($mgmt_config[$site]['sendmail']) && $setlocalpermission['download'] == 1 && linking_valid() == false)
            {
              $dlink_start = "<a id=\"link_".$items_row."\" target=\"_blank\" data-linktype=\"wrapper\" data-objectpath=\"".$location_esc.$object."\" data-href=\"\">";
              $dlink_end = "</a>";
            }
            else
            {
              $dlink_start = "<a id=\"link_".$items_row."\" data-linktype=\"none\" data-objectpath=\"".$location_esc.$object."\" data-href=\"javascript:void(0);\">";
              $dlink_end = "</a>";
            }
          }      
        }

        // eventsystem
        if (!empty ($eventsystem['onobjectlist_post']) && empty ($eventsystem['hide'])) 
          onobjectlist_post ($site, $cat, $location, $object, $contentfile, $contentdata, $usedby, $user);     

        // open on double click
        $openObject = "onDblClick=\"hcms_openWindow('frameset_content.php?ctrlreload=yes&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($object)."&token=".$token."', '".$container_id."', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");\"";

        // onclick for marking objects      
        $selectclick = "onClick=\"hcms_selectObject(this.id, event); hcms_updateControlObjectListMenu();\" ";

        // set context
        $hcms_setObjectcontext = "onMouseOver=\"hcms_setObjectcontext('".$site."', '".$cat."', '".$location_esc."', '".$object."', '".$file_info['name']."', '".$file_info['type']."', '".$mediafile."', '', '', '".$token."');\" onMouseOut=\"hcms_resetContext();\" ";

        // metadata
        $metadata = getescapedtext ($hcms_lang['name'][$lang]).": ".$object_name." \r\n".getescapedtext ($hcms_lang['date-modified'][$lang]).": ".showdate ($file_modified, "Y-m-d H:i", $hcms_lang_date[$lang])." \r\n".getescapedtext ($hcms_lang['size-in-kb'][$lang]).": ".$file_size." \r\n".$metadata;             

        // listview - view option for un/published objects
        if ($file_info['published'] == false) $class_image = "class=\"hcmsIconList hcmsIconOff\"";
        else $class_image = "class=\"hcmsIconList\"";

        // drag events
        if ($setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
        {
          $dragevent = "draggable=\"true\" ondragstart=\"hcms_drag(event)\"";
        }
        else $dragevent = "";

        // listview - view option for locked objects
        if (!empty ($usedby))
        {
          $file_info['icon'] = "file_lock.png";
        }

        $listview .= "
                      <tr id=\"g".$items_row."\" style=\"cursor:pointer;\" ".$selectclick.">
                        <td id=\"h".$items_row."_0\" class=\"hcmsCol0 hcmsCell\" style=\"width:280px;\">
                          <div class=\"hcmsObjectListMarker\" ".$hcms_setObjectcontext." ".$openObject." title=\"".$metadata."\" ".$dragevent.">
                            ".$dlink_start."<img src=\"".getthemelocation()."img/".$file_info['icon']."\" ".$class_image." /> ".$object_name.$dlink_end."  
                          </div>
                        </td>";

        if (!$is_mobile)
        {
          if (is_array ($objectlistcols[$site][$cat]))
          {
            $i = 1;

            foreach ($objectlistcols[$site][$cat] as $key => $active)
            {
              if ($i < sizeof ($objectlistcols[$site][$cat])) $style_td = "width:125px;";
              else $style_td = "";

              if ($active == 1)
              {
                $style_div = "";
                
                if ($key == 'createdate')
                {
                  $title = "<span style=\"display:none;\">".date ("YmdHi", strtotime ($file_created))."</span>".showdate ($file_created, "Y-m-d H:i", $hcms_lang_date[$lang]);
                }
                elseif ($key == 'modifieddate' || $key == 'date')
                {
                  $title = "<span style=\"display:none;\">".date ("YmdHi", strtotime ($file_modified))."</span>".showdate ($file_modified, "Y-m-d H:i", $hcms_lang_date[$lang]);
                }
                elseif ($key == 'publishdate')
                {
                  $title = "<span style=\"display:none;\">".date ("YmdHi", strtotime ($file_published))."</span>".showdate ($file_published, "Y-m-d H:i", $hcms_lang_date[$lang]);
                }
                elseif ($key == 'filesize')
                {
                  $title = $file_size;
                  $style_div = "text-align:right; padding-right:5px;";
                }
                elseif ($key == 'type')
                {
                  $title = $file_type;
                }
                elseif ($key == 'owner' || $key == 'user')
                {
                  $title = $file_owner;
                }
                else
                {
                  if (!empty ($container_info[$key])) $title = $container_info[$key];
                  else $title = "";
                }

                $listview .= "
                        <td id=\"h".$items_row."_".$i."\" class=\"hcmsCol".$i." hcmsCell\" style=\"".$style_td."\"><div ".$hcms_setObjectcontext." style=\"display:block; ".$style_div."\">".$title."</div></td>";
              
                $i++;
              }
            }
          }
        }

        $listview .= "
                      </tr>"; 

        // if there is a thumb file, display the thumb
        if ($mediafile != false && empty ($usedby))
        {
          // get thumbnail location
          $thumbdir = getmedialocation ($site, $media_info['filename'].".thumb.jpg", "abs_path_media");

          // prepare source media file
          preparemediafile ($site, $thumbdir.$site."/", $media_info['filename'].".thumb.jpg", $user);

          // try to create thumbnail if not available
          if (!empty ($mgmt_config['recreate_preview']) && (!is_file ($thumbdir.$site."/".$media_info['filename'].".thumb.jpg") || !is_cloudobject ($thumbdir.$site."/".$media_info['filename'].".thumb.jpg")))
          {
            createmedia ($site, $thumbdir.$site."/", $thumbdir.$site."/", $media_info['file'], "", "thumbnail", true, true);
          }

          if (is_file ($thumbdir.$site."/".$media_info['filename'].".thumb.jpg") || is_cloudobject ($thumbdir.$site."/".$media_info['filename'].".thumb.jpg"))
          {
            // galleryview - view option for locked multimedia objects
            if ($file_info['published'] == false) $class_image = "class=\"lazyload hcmsImageItem hcmsIconOff\"";
            else $class_image  = "class=\"lazyload hcmsImageItem\"";

            $thumbnail = "<div id=\"m".$items_row."\" class=\"hcmsThumbnailFrame hcmsThumbnail".$temp_explorerview."\"><img data-src=\"".cleandomain (createviewlink ($site, $media_info['filename'].".thumb.jpg"))."\" ".$class_image." /></div>";
          }
          // display file icon if thumbnail fails 
          else
          {
            // galleryview - view option for locked multimedia objects
            if ($file_info['published'] == false) $class_image = "class=\"hcmsIconOff\"";
            else $class_image = "";
                    
            $thumbnail = "<div id=\"i".$items_row."\" class=\"hcmsThumbnailFrame hcmsThumbnail".$temp_explorerview."\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" ".$class_image." /></div>";
          }           
        }
        // display file icon for non multimedia objects 
        else
        {
          // galleryview - view option for locked multimedia objects
          if ($file_info['published'] == false) $class_image = "class=\"hcmsIconOff\"";
          else $class_image = "";
                  
          $thumbnail = "<div id=\"i".$items_row."\" class=\"hcmsThumbnailFrame hcmsThumbnail".$temp_explorerview."\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" ".$class_image." /></div>";
        }

        // if linking is used display download buttons, display edit button for mobile edition
        $linking_buttons = "";

        // if mobile edition is used display download button
        if ($mediafile != false && linking_valid() == true && $setlocalpermission['root'] == 1 && $setlocalpermission['download'] == 1)
        {
          // check download of original file
          if (empty ($downloadformats) || (is_document ($mediafile) && !empty ($downloadformats['document']['original'])) || (is_image ($mediafile) && !empty ($downloadformats['image']['original'])))
          {            
            $linking_buttons .= "
            <button class=\"hcmsButtonDownload\" style=\"width:94%;\" onClick=\"openObjectView('".$location_esc."', '".$object."', 'preview');\">".getescapedtext ($hcms_lang['view'][$lang])."</button>
            <a href=\"".cleandomain (createviewlink ($site, $mediafile, $object_name, false, "download"))."\" target=\"_blank\"><button class=\"hcmsButtonDownload\" style=\"width:94%;\">".getescapedtext ($hcms_lang['download'][$lang])."</button></a>";
          }
        }

        // if mobile edition is used display edit button if the download button is not used (only one button must be displayed in order to keep the div height for all folders and objects)
        if ($is_mobile && $linking_buttons == "" && (($mediafile == "" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1) || ($mediafile != "" && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1)))
        {   
          $linking_buttons .= "
          <button class=\"hcmsButtonDownload\" style=\"width:94%;\" onClick=\"hcms_openWindow('frameset_content.php?ctrlreload=yes&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($object)."&token=".$token."', '".$container_id."', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");\">".getescapedtext ($hcms_lang['edit'][$lang])."</button>";
        }

        // if assetbrowser is used display edit button
        if (!empty ($hcms_assetbrowser) && $mediafile != "" && $setlocalpermission['root'] == 1)
        {   
          $linking_buttons .= "
          <button class=\"hcmsButtonDownload\" style=\"width:94%;\" onClick=\"parent.parent.returnMedia('".$location_esc.$object."', '".$object_name."', '".$imgwidth."', '".$imgheight."', '".$file_modified."', '".$file_size."');\">".getescapedtext ($hcms_lang['select'][$lang])."</button>";
        }

        if ($linking_buttons != "")
        {
          $linking_buttons = "<div style=\"width:100%; margin:0px auto; padding:0; text-align:center;\">".$linking_buttons."</div>";
        }

        $galleryview .= "
                        <div id=\"t".$items_row."\" ".$selectclick." class=\"hcmsObjectUnselected\">
                          <div class=\"hcmsObjectGalleryMarker\" ".$hcms_setObjectcontext." ".$openObject." title=\"".$metadata."\" ".$dragevent.">".
                            $dlink_start."
                              ".$thumbnail."
                              <div class=\"hcmsItemName\">".showshorttext($object_name, 18, true)."</div>
                            ".$dlink_end."
                          </div>
                          ".$linking_buttons."
                        </div>";
      }
      // object does not exist or user has no access permission 
      else $objects_total--;
    }
  } 
}

// objects counted
if ($items_row > 0) $objects_counted = $items_row;
else $objects_counted = 0;
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript" src="javascript/contextmenu.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript" src="javascript/jquery/jquery-3.5.1.min.js"></script>
<script type="text/javascript" src="javascript/jquery/plugins/colResizable.min.js"></script>
<script type="text/javascript" src="javascript/chat.min.js"></script>
<script type="text/javascript" src="javascript/lazysizes/lazysizes.min.js" async=""></script>
<style type="text/css">
#objectlist
{
  table-layout: fixed;
  border-collapse: collapse;
  border: 0;
  border-spacing: 0;
  padding: 0;
  width: 100%;
}

#objectgallery
{
  border: 0;
  padding: 0;
  width: 100%;
}

#objectgallery > div
{
  display: block;
  float: left;
  padding: 4px;
}

.hcmsObjectListMarker
{
  display: block;
  padding: 0px 5px;
}

.hcmsObjectGalleryMarker
{
  display: table-cell;
  cursor: pointer;
  text-align: center;
  vertical-align: bottom;
  overflow: hidden;
  text-overflow: ellipsis;
}

.hcmsThumbnailFrame
{
  display: block;
  text-align: center;
  vertical-align: bottom;
}

.hcmsItemName
{
  display: block;
  height: 50px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.hcmsHead
{
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.hcmsCell
{
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  padding-left: 3px; 
}

.hcmsIconlarge img
{
  width: <?php echo $thumbnailsize_large; ?>px;
  height: <?php echo $thumbnailsize_large; ?>px;
}

.hcmsIconmedium img
{
  width: <?php echo $thumbnailsize_medium; ?>px;
  height: <?php echo $thumbnailsize_medium; ?>px;
}

.hcmsIconsmall img
{
  width: <?php echo $thumbnailsize_small; ?>px;
  height: <?php echo $thumbnailsize_small; ?>px;
}

.hcmsThumbnaillarge img
{
  max-width: <?php echo ($thumbnailsize_large * 4); ?>px;
  max-height: <?php echo $thumbnailsize_large; ?>px;
}

.hcmsThumbnailmedium img
{
  max-width: <?php echo $thumbnailsize_medium; ?>px;
  max-height: <?php echo $thumbnailsize_medium; ?>px;
}

.hcmsThumbnailsmall img
{
  max-width: <?php echo $thumbnailsize_small; ?>px;
  max-height: <?php echo $thumbnailsize_small; ?>px;
}

@media screen and (max-width: 360px)
{
  #objectgallery
  {
    width: 260px;
    margin: 0px auto;
  }
}
</style>
<script type="text/javascript">

// design theme
themelocation = '<?php echo getthemelocation(); ?>';

// explorer view option
var explorerview = "<?php echo $temp_explorerview; ?>";

// verify sidebar
if (parent.document.getElementById('sidebarLayer') && parent.document.getElementById('sidebarLayer').style.width > 0) var sidebar = true;
else var sidebar = false;

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
  return confirm (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-remove-the-item'][$lang]); ?>"));
}

function toggleview (viewoption)
{
  var style = "";
  var frames;
  var icon;
  var thumbnail;

  // reset explorer view
  if (typeof viewoption !== 'undefined' && viewoption != "") explorerview = viewoption;
  else viewoption = explorerview;
  
  // control layers
  if (viewoption == "detail") hcms_showHideLayers ('objectLayer','','show','detailviewReset','','show','galleryviewLayer','','hide','galleryviewReset','','hide');
  else if (viewoption == "small" || viewoption == "medium" || viewoption == "large") hcms_showHideLayers ('objectLayer','','hide','detailviewReset','','show','galleryviewLayer','','show','galleryviewReset','');
  else return false;

  // gallery marker frame size definitions
  if (viewoption == "large") style = "max-width:<?php echo ceil ($thumbnailsize_large * 4); ?>px; height:<?php echo ($thumbnailsize_large + 56); ?>px;";
  else if (viewoption == "medium") style = "width:<?php echo ($thumbnailsize_medium + 12); ?>px; height:<?php echo ($thumbnailsize_medium + 56); ?>px;";
  else if (viewoption == "small") style = "width:<?php echo ($thumbnailsize_small + 28); ?>px; height:<?php echo ($thumbnailsize_small + 58); ?>px;";

  frames = document.getElementsByClassName('hcmsObjectGalleryMarker');

  if (frames && style != "")
  {
    for (i = 0; i < frames.length; i++)
    {           
      frames[i].style.cssText = style;     
    }
  }
  
  // thumbnails and icons
  for (var i = 0; i <= <?php echo $items_row; ?>; i++)
  {
    // media thumbnail
    thumbnail = document.getElementById('m' + i);
      
    if (thumbnail)
    {
      thumbnail.className = 'hcmsThumbnailFrame hcmsThumbnail' + viewoption;
    }
    // standard icon
    else
    {
      icon = document.getElementById('i' + i);
    
      if (icon)
      {
        icon.className = 'hcmsThumbnailFrame hcmsIcon' + viewoption;
      }
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

function initsizecols ()
{
  var colwidth;

  for (i = 0; i < <?php if (!empty ($objectlistcols[$site][$cat]) && is_array ($objectlistcols[$site][$cat])) echo sizeof ($objectlistcols[$site][$cat]) + 1; else echo 1;  ?>; i++)
  {
    // get column width
    colwidth = localStorage.getItem('Col<?php echo $site.$cat; ?>'+i);

    // set width of table header columns
    $('#c'+i).width(colwidth);

    // set width for table columns
    $('.hcmsCol'+i).width(colwidth);
  }
}

function resizecols ()
{
  var colwidth;

  for (i = 0; i < <?php if (!empty ($objectlistcols[$site][$cat]) && is_array ($objectlistcols[$site][$cat])) echo sizeof ($objectlistcols[$site][$cat]) + 1; else echo 1;  ?>; i++)
  {
    // get width of table header columns
    if ($('#c'+i)) colwidth = $('#c'+i).width();

    // set width for table columns
    $('.hcmsCol'+i).width(colwidth);

    // save column width
    localStorage.setItem('Col<?php echo $site.$cat; ?>'+i, colwidth);
  }
}

function resetcols ()
{
  for (i = 0; i < <?php if (!empty ($objectlistcols[$site][$cat]) && is_array ($objectlistcols[$site][$cat])) echo sizeof ($objectlistcols[$site][$cat]) + 1; else echo 1;  ?>; i++)
  {
    // save column width
    localStorage.removeItem('Col<?php echo $site.$cat; ?>'+i);
  }
}

function setcolumns ()
{
  if (document.forms['contextmenu_column'])
  {
    // local load screen
    if (document.getElementById('hcmsLoadScreen')) document.getElementById('hcmsLoadScreen').style.display = 'inline';

    document.forms['contextmenu_column'].submit();
  }
}

function openObjectView (location, object, view)
{
  if (location != "" && object != "" && parent.document.getElementById('objectview'))
  {
    parent.openObjectView(location, object, view);
  }
  else return false;
}

function initialize ()
{
  // set view
  toggleview (explorerview);

  // select area
  selectarea = document.getElementById('selectarea');

  // parent load screen
  if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display = 'none';

  // collect objects and set objects array
  hcms_collectObjectpath ();

  // reset column width on request
  <?php if (!empty ($resetcols)) echo "resetcols();"; ?>

  // set columns width
  initsizecols ();
  
  // resize columns
  $("#objectlist_head").colResizable({liveDrag:true, onDrag:resizecols});
}
</script>
</head>

<body id="hcmsWorkplaceObjectlist" class="hcmsWorkplaceObjectlist" onresize="resizecols();" ondrop="hcms_drop(event);" ondragover="hcms_allowDrop(event);">

<!-- load screen --> 
<div id="hcmsLoadScreen" class="hcmsLoadScreen" style="display:none;"></div>

<!-- select area --> 
<div id="selectarea" class="hcmsSelectArea" hidden></div>

<!-- contextual help --> 
<?php if (!$is_mobile) echo showinfobox ($hcms_lang['hold-ctrl-key-select-objects-by-click'][$lang]."<br/>".$hcms_lang['hold-shift-key-select-a-group-of-objects-by-2-clicks'][$lang]."<br/>".$hcms_lang['press-alt-key-switch-to-download-links-to-copy-paste-into-e-mails'][$lang]."<br/>".$hcms_lang['drag-and-drop-press-ctrl-key-for-copy-and-alt-key-for-connected-copy'][$lang], $lang, "position:fixed; top:30px; right:30px;", "hcms_infoboxKeys"); ?>

<!-- memory (for drop event) -->
<form name="memory" action="" method="post" target="popup_explorer" style="position:absolute; width:0; height:0; z-index:-1; left:0; top:0; visibility:hidden;">
  <input type="hidden" name="action" value="" />
  <input type="hidden" name="force" value="" />
  <input type="hidden" name="contexttype" value="" />
  <input type="hidden" name="site" value="" />
  <input type="hidden" name="cat" value="" />
  <input type="hidden" name="location" value="" />
  <input type="hidden" name="targetlocation" value="" />
  <input type="hidden" name="page" value="" />
  <input type="hidden" name="pagename" value="" />
  <input type="hidden" name="filetype" value="" />
  <input type="hidden" name="media" value="" />
  <input type="hidden" name="folder" value="" /> 
  <input type="hidden" name="multiobject" value="" />
  <input type="hidden" name="token" value="<?php echo $token; ?>" />
  <input type="hidden" name="convert_type" value="" />
  <input type="hidden" name="convert_cfg" value="" />
</form>

<!-- context menu --> 
<div id="contextLayer" style="position:absolute; min-width:180px; max-width:280px; height:320px; z-index:10; left:20px; top:20px; visibility:hidden;">
  <form name="contextmenu_object" action="" method="post" target="_blank" style="display:block;">
    <input type="hidden" name="contextmenustatus" value="" />
    <input type="hidden" name="contextmenulocked" value="false" />
    <input type="hidden" name="action" value="" />
    <input type="hidden" name="force" value="" />
    <input type="hidden" name="contexttype" value="none" />
    <input type="hidden" name="xpos" value="" />
    <input type="hidden" name="ypos" value="" />
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="targetlocation" value="" />
    <input type="hidden" name="page" value="" />
    <input type="hidden" name="pagename" value="" />
    <input type="hidden" name="filetype" value="" />
    <input type="hidden" name="media" value="" />
    <input type="hidden" name="folder" value="" />
    <input type="hidden" name="multiobject" value="" />
    <input type="hidden" name="token" value="<?php echo $token; ?>" />
    <input type="hidden" name="convert_type" value="" />
    <input type="hidden" name="convert_cfg" value="" />
    
    <table class="hcmsContextMenu hcmsTableStandard" style="width:100%;">
      <tr>
        <td style="white-space:nowrap;">
          <?php if (checkrootpermission ('desktopfavorites') && $setlocalpermission['root'] == 1 && linking_valid() == false) { ?>
          <a href="javascript:void(0);" id="href_fav_create" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('favorites_create');"><img src="<?php echo getthemelocation(); ?>img/button_favorites_delete.png" id="img_fav_create" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['add-to-favorites'][$lang]); ?></a><br />
          <hr />        
          <?php } ?>
          <a href="javascript:void(0);" id="href_preview" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('preview');"><img src="<?php echo getthemelocation(); ?>img/button_file_preview.png" id="img_preview" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['preview'][$lang]); ?></a><br />  
          <?php if ($setlocalpermission['root'] == 1 && ($setlocalpermission['create'] == 1 || $setlocalpermission['upload'] == 1)) { ?>
          <a href="javascript:void(0);" id="href_cmsview" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('cmsview');"><img src="<?php echo getthemelocation(); ?>img/button_edit.png" id="img_cmsview" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['edit'][$lang]); ?></a><br />     
          <?php } else { ?>
          <a href="javascript:void(0);" id="_href_cmsview" disabled="disabled"><img src="<?php echo getthemelocation(); ?>img/button_edit.png" id="_img_cmsview" class="hcmsIconOff hcmsIconList">&nbsp;<?php echo getescapedtext ($hcms_lang['edit'][$lang]); ?></a><br /> 
          <?php } ?>
          <?php if ($setlocalpermission['root'] == 1) { ?>
          <a href="javascript:void(0);" id="href_notify" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('notify');"><img src="<?php echo getthemelocation(); ?>img/button_notify.png" id="img_notify" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['notify-me'][$lang]); ?></a><br />
          <?php } else { ?>
          <a href="javascript:void(0);" id="_href_notify" disabled="disabled"><img src="<?php echo getthemelocation(); ?>img/button_notify.png" id="_img_notify" class="hcmsIconOff hcmsIconList">&nbsp;<?php echo getescapedtext ($hcms_lang['notify-me'][$lang]); ?></a><br /> 
          <?php } ?>
          <?php if ($setlocalpermission['root'] == 1 && !empty ($mgmt_config['chat'])) { ?>
          <a href="javascript:void(0);" id="href_chat" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('chat');"><img src="<?php echo getthemelocation(); ?>img/button_chat.png" id="img_chat" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['send-to-chat'][$lang]); ?></a><br />
          <?php } ?>
          <hr />
          <?php if ($setlocalpermission['root'] == 1 && $setlocalpermission['delete'] == 1 && $setlocalpermission['folderdelete'] == 1) { ?>
          <a href="javascript:void(0);" id="href_delete" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('delete');"><img src="<?php echo getthemelocation(); ?>img/button_delete.png" id="img_delete" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?></a><br />
          <hr />
          <?php } elseif ($setlocalpermission['root'] == 1 && $setlocalpermission['delete'] == 1) { ?>
          <a href="javascript:void(0);" id="href_delete" onClick="if (checktype('object')==true || checktype('media')==true) hcms_createContextmenuItem ('delete');"><img src="<?php echo getthemelocation(); ?>img/button_delete.png" id="img_delete" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?></a><br />
          <hr />        
          <?php } elseif ($setlocalpermission['root'] == 1 && $setlocalpermission['folderdelete'] == 1) { ?>     
          <a href="javascript:void(0);" id="href_delete" onClick="if (checktype('folder')==true) hcms_createContextmenuItem ('delete');"><img src="<?php echo getthemelocation(); ?>img/button_delete.png" id="img_delete" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?></a><br />
          <hr />
          <?php } else { ?>
          <a href="javascript:void(0);" id="_href_delete" disabled="disabled"><img src="<?php echo getthemelocation(); ?>img/button_delete.png" id="_img_delete" class="hcmsIconOff hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?></a><br />
          <hr />
          <?php } ?>     
          <?php if ($setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1 && $setlocalpermission['folderrename'] == 1) { ?>
          <a href="javascript:void(0);" id="href_cut" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('cut');"><img src="<?php echo getthemelocation(); ?>img/button_file_cut.png" id="img_cut" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['cut'][$lang]); ?></a><br />  
          <a href="javascript:void(0);" id="href_copy" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('copy');"><img src="<?php echo getthemelocation(); ?>img/button_file_copy.png" id="img_copy" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['copy'][$lang]); ?></a><br />  
          <a href="javascript:void(0);" id="href_copylinked" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('linkcopy');"><img src="<?php echo getthemelocation(); ?>img/button_file_copylinked.png" id="img_copylinked" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['connected-copy'][$lang]); ?></a><br />
          <?php } elseif ($setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1) { ?>
          <a href="javascript:void(0);" id="href_cut" onClick="if (checktype('object')==true || checktype('media')==true) hcms_createContextmenuItem ('cut');"><img src="<?php echo getthemelocation(); ?>img/button_file_cut.png" id="img_cut" border=0 class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['cut'][$lang]); ?></a><br />  
          <a href="javascript:void(0);" id="href_copy" onClick="if (checktype('object')==true || checktype('media')==true) hcms_createContextmenuItem ('copy');"><img src="<?php echo getthemelocation(); ?>img/button_file_copy.png" id="img_copy" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['copy'][$lang]); ?></a><br />  
          <a href="javascript:void(0);" id="href_copylinked" onClick="if (checktype('object')==true || checktype('media')==true) hcms_createContextmenuItem ('linkcopy');"><img src="<?php echo getthemelocation(); ?>img/button_file_copylinked.png" id="img_copylinked" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['connected-copy'][$lang]); ?></a><br />
          <?php } elseif ($setlocalpermission['root'] == 1 && $setlocalpermission['folderrename'] == 1) { ?> 
          <a href="javascript:void(0);" id="href_cut" onClick="if (checktype('folder')==true) hcms_createContextmenuItem ('cut');"><img src="<?php echo getthemelocation(); ?>img/button_file_cut.png" id="img_cut" border=0 class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['cut'][$lang]); ?></a><br />  
          <a href="javascript:void(0);" id="href_copy" onClick="if (checktype('folder')==true) hcms_createContextmenuItem ('copy');"><img src="<?php echo getthemelocation(); ?>img/button_file_copy.png" id="img_copy" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['copy'][$lang]); ?></a><br />  
          <a href="javascript:void(0);" id="href_copylinked" onClick="if (checktype('folder')==true) hcms_createContextmenuItem ('linkcopy');"><img src="<?php echo getthemelocation(); ?>img/button_file_copylinked.png" id="img_copylinked" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['connected-copy'][$lang]); ?></a><br />        
          <?php } else { ?>
          <a href="javascript:void(0);" id="_href_cut" disabled="disabled"><img src="<?php echo getthemelocation(); ?>img/button_file_cut.png" id="_img_cut" class="hcmsIconOff hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['cut'][$lang]); ?></a><br />  
          <a href="javascript:void(0);" id="_href_copy" disabled="disabled"><img src="<?php echo getthemelocation(); ?>img/button_file_copy.png" id="_img_copy" class="hcmsIconOff hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['copy'][$lang]); ?></a><br />  
          <a href="javascript:void(0);" id="_href_copylinked" disabled="disabled"><img src="<?php echo getthemelocation(); ?>img/button_file_copylinked.png" id="_img_copylinked" class="hcmsIconOff hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['connected-copy'][$lang]); ?></a><br />  
          <?php } ?>
          <?php if ($setlocalpermission['root'] == 1 && ($setlocalpermission['folderrename'] == 1 || $setlocalpermission['rename'] == 1)) { ?> 
          <a href="javascript:void(0);" id="href_paste" onClick="hcms_createContextmenuItem ('paste');"><img src="<?php echo getthemelocation(); ?>img/button_file_paste.png" id="img_paste" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['paste'][$lang]); ?></a><br />  
          <hr />
          <?php } else { ?>
          <a href="javascript:void(0);" id="href_paste" disabled><img src="<?php echo getthemelocation(); ?>img/button_file_paste.png" id="img_paste" class="hcmsIconOff hcmsIconList">&nbsp;<?php echo getescapedtext ($hcms_lang['paste'][$lang]); ?></a><br />  
          <hr />
          <?php } ?>
          <?php if ($virtual == 1 || ($setlocalpermission['root'] == 1 && $setlocalpermission['publish'] == 1)) { ?>
          <a href="javascript:void(0);" id="href_publish" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('publish');"><img src="<?php echo getthemelocation(); ?>img/button_file_publish.png" id="img_publish" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['publish'][$lang]); ?></a><br />  
          <a href="javascript:void(0);" id="href_unpublish" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('unpublish');"><img src="<?php echo getthemelocation(); ?>img/button_file_unpublish.png" id="img_unpublish" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['unpublish'][$lang]); ?></a><br />
          <hr /> 
          <?php } else { ?>
          <a href="javascript:void(0);" id="_href_publish" disabled="disabled"><img src="<?php echo getthemelocation(); ?>img/button_file_publish.png" id="_img_publish" class="hcmsIconOff hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['publish'][$lang]); ?></a><br />  
          <a href="javascript:void(0);" id="_href_unpublish" disabled="disabled"><img src="<?php echo getthemelocation(); ?>img/button_file_unpublish.png" id="_img_unpublish" class="hcmsIconOff hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['unpublish'][$lang]); ?></a><br />
          <hr />         
          <?php } ?>
          <?php
          // ----------------------------------------- plugins ----------------------------------------------
          if ($setlocalpermission['root'] == 1 && empty ($hcms_assetbrowser) && linking_valid() == false && !empty ($mgmt_plugin))
          { 
            $plugin_items = "";
            
            foreach ($mgmt_plugin as $plugin_name => $data)
            {
              // Only active plugins which have the correct keys are used
              if (is_array ($data) && !empty ($data['active']) && array_key_exists ('menu', $data) && is_array ($data['menu']) && array_key_exists ('context', $data['menu']) && is_array ($data['menu']['context']))
              {
                foreach ($data['menu']['context'] as $key => $point)
                {
                  if (!empty ($point['page']) && !empty ($point['name'])) $plugin_items .= "
            <a href=\"javascript:void(0);\" id=\"href_plugin_".$key."\" onClick=\"if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('".$mgmt_config['url_path_plugin'].$plugin_name."/".$point['page']."');\"><img src=\"".$point['icon']."\" name=\"img_plugin\" class=\"hcmsIconOn hcmsIconList\" />&nbsp;".getescapedtext ($point['name'])."</a><br />";
                }
              }
            }
            
            if ($plugin_items != "") echo $plugin_items."
          <hr />";
          }
          ?>
          <a href="javascript:void(0);" id="href_print" onClick="hcms_hideContextmenu(); window.print();"><img src="<?php echo getthemelocation(); ?>img/button_print.png" id="img_print" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['print'][$lang]); ?></a><br />     
          <a href="javascript:void(0);" id="href_refresh" onClick="document.location.reload();"><img src="<?php echo getthemelocation(); ?>img/button_view_refresh.png" id="img_refresh" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['refresh'][$lang]); ?></a>
        </td>
      </tr>    
    </table>
  </form>
  
  <!-- context menu for colum attributes -->
  <form name="contextmenu_column" action="" method="post" style="display:none;">
    <input type="hidden" name="token" value="<?php echo $token; ?>" />
    <div style="min-width:180px; max-width:280px; max-height:640px; overflow:auto;">
      <table class="hcmsContextMenu hcmsTableStandard" style="width:100%;">
        <tr>
          <td style="white-space:nowrap;">
            <label><input onclick="setcolumns()" type="checkbox" name="column[createdate]" value="1" <?php if (!empty ($objectlistcols[$site][$cat]['createdate'])) echo "checked=\"checked\""; ?>/>&nbsp;<?php echo getescapedtext ($hcms_lang['date-created'][$lang]); ?></label><br />
            <label><input onclick="setcolumns()" type="checkbox" name="column[modifieddate]" value="1" <?php if (!empty ($objectlistcols[$site][$cat]['modifieddate'])) echo "checked=\"checked\""; ?>/>&nbsp;<?php echo getescapedtext ($hcms_lang['date-modified'][$lang]); ?></label><br />
            <label><input onclick="setcolumns()" type="checkbox" name="column[publishdate]" value="1" <?php if (!empty ($objectlistcols[$site][$cat]['publishdate'])) echo "checked=\"checked\""; ?>/>&nbsp;<?php echo getescapedtext ($hcms_lang['published'][$lang]); ?></label><br />
            <label><input onclick="setcolumns()" type="checkbox" name="column[filesize]" value="1" <?php if (!empty ($objectlistcols[$site][$cat]['filesize'])) echo "checked=\"checked\""; ?>/>&nbsp;<?php echo getescapedtext ($hcms_lang['file-size'][$lang]); ?></label><br />
            <label><input onclick="setcolumns()" type="checkbox" name="column[type]" value="1" <?php if (!empty ($objectlistcols[$site][$cat]['type'])) echo "checked=\"checked\""; ?>/>&nbsp;<?php echo getescapedtext ($hcms_lang['type'][$lang]); ?></label><br />
            <label><input onclick="setcolumns()" type="checkbox" name="column[owner]" value="1" <?php if (!empty ($objectlistcols[$site][$cat]['owner'])) echo "checked=\"checked\""; ?>/>&nbsp;<?php echo getescapedtext ($hcms_lang['owner'][$lang]); ?></label><br />
            <?php
            if (!empty ($labels[$site][$cat]) && is_array ($labels[$site][$cat]) && sizeof ($labels[$site][$cat]) > 0)
            {
              foreach ($labels[$site][$cat] as $text_id => $label)
              {
                if (!empty ($text_id)) 
                {                
                  echo "
            <label><input onclick=\"setcolumns()\" type=\"checkbox\" name=\"column[".$text_id."]\" value=\"1\" ".(!empty ($objectlistcols[$site][$cat][$text_id]) ? "checked=\"checked\"" : "")."/>&nbsp;".getescapedtext ($label)."</label><br />";
                }
              }
            }
            ?>
          </td>
        </tr>    
      </table>
    </div>
  </form>
</div>

<!-- Table Header -->
<div id="tableHeadLayer" style="position:fixed; top:0; left:0; margin:0; padding:0; width:100%; z-index:2; visibility:visible; overflow-x:hidden; overflow-y:hidden;">
  <table id="objectlist_head" style="border-collapse:collapse; border:0; border-spacing:0; padding:0; width:100%; height:20px;"> 
    <tr onmouseover="hcms_setColumncontext();">
      <td id="c0" onClick="hcms_sortTable(0); toggleview('');" class="hcmsTableHeader hcmsHead" style="width:280px;">&nbsp;<?php echo getescapedtext ($hcms_lang['name'][$lang]); ?>&nbsp;</td>
    <?php
    if (!$is_mobile)
    {
      if (!empty ($objectlistcols[$site][$cat]) && is_array ($objectlistcols[$site][$cat]))
      {
        $i = 1;
        
        foreach ($objectlistcols[$site][$cat] as $key => $active)
        {
          if ($i < sizeof ($objectlistcols[$site][$cat])) $style_td = "width:125px;";
          else $style_td = "";

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
            elseif ($key == 'publishdate')
            {
              $title = getescapedtext ($hcms_lang['published'][$lang]);
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
              if (!empty ($labels[$site][$cat][$key])) $title = $labels[$site][$cat][$key];
              // use text ID
              else $title = ucfirst (str_replace ("_", " ", substr ($key, 5)));
              
              if (!is_utf8 ($title)) $title = utf8_encode ($title);
            }
            
            echo "
      <td id=\"c".$i."\" onClick=\"hcms_sortTable(".$i.$sortnumeric."); toggleview('');\" class=\"hcmsTableHeader hcmsHead\" style=\"".$style_td."\">&nbsp;".$title."&nbsp;</td>";

            $i++;
          }
        }
      }
      ?>
    <?php } ?> 
    </tr>
  </table>
</div>

<!-- Detail View -->
<div id="objectLayer" onmouseover="hcms_setObjectcontext('<?php echo $site; ?>', '<?php echo $cat; ?>', '<?php echo $location_esc; ?>', '', '', '', '', '', '', '<?php echo $token; ?>');" style="position:fixed; top:20px; left:0; bottom:32px; margin:0; padding:0; width:100%; z-index:1; visibility:visible; overflow-x:hidden; overflow-y:scroll;">
  <table id="objectlist" name="objectlist" style="table-layout:fixed; border-collapse:collapse; border:0; border-spacing:0; padding:0; width:100%;">
  <?php 
  echo $listview;
  ?>
  </table>
  <br /><div id="detailviewReset" style="width:100%; height:3px; z-index:3; visibility:visible;" onMouseOver="hcms_hideContextmenu();"></div>
</div>

<!-- Gallery View -->
<div id="galleryviewLayer" onmouseover="hcms_setObjectcontext('<?php echo $site; ?>', '<?php echo $cat; ?>', '<?php echo $location_esc; ?>', '', '', '', '', '', '', '<?php echo $token; ?>');" style="position:fixed; top:20px; left:0; bottom:32px; margin:0; padding:0; width:100%; z-index:1; visibility:hidden; overflow-y:scroll;">
<?php
if ($galleryview != "")
{
  echo "
  <div id=\"objectgallery\" name=\"objectgallery\">
    ".$galleryview."
  </div>";
}
?>
  <br /><div id="galleryviewReset" style="width:100%; height:3px; z-index:0; visibility:visible;" onMouseOver="hcms_hideContextmenu();"></div>
</div>

<?php
// expanding
if (empty ($mgmt_config['explorer_paging']) && $objects_total >= $end)
{
?>
<!-- status bar incl. more button -->
<div id="ButtonMore" class="hcmsMore" style="position:fixed; bottom:0; width:100%; height:30px; z-index:4; visibility:visible; text-align:left;" onclick="if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display='inline'; window.location='<?php echo "?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&start=".url_encode($next_start); ?>';" onMouseOver="hcms_hideContextmenu();" title="<?php echo getescapedtext ($hcms_lang['more'][$lang]); ?>">
  <div style="padding:8px; float:left;"><?php echo $next_start." / ".number_format ($objects_total, 0, ".", " ")." ".(!$is_mobile ? getescapedtext ($hcms_lang['objects'][$lang]) : ""); ?></div>
  <div style="margin:0 auto; text-align:center;"><img src="<?php echo getthemelocation(); ?>img/button_arrow_down.png" class="hcmsButtonSizeSquare" style="border:0;" /></div>
</div>
<?php
}
// paging
elseif (!empty ($mgmt_config['explorer_paging']) && ($start > 0 || $objects_total > $end))
{
  // start positions (inital start is 0 and not 1)
  $previous_start = $start - intval ($mgmt_config['explorer_list_maxitems']);
  $next_start = $objects_counted + 1;
?>
<!-- status bar incl. previous and next buttons -->
<div id="ButtonPrevious" class="hcmsMore" style="position:fixed; bottom:0; left:0; right:50%; height:30px; z-index:4; visibility:visible; text-align:left;" <?php if ($start > 0) { ?>onclick="if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display='inline'; window.location='<?php echo "?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&start=".url_encode($previous_start); ?>';"<?php } ?> onMouseOver="hcms_hideContextmenu();" title="<?php echo getescapedtext ($hcms_lang['back'][$lang]); ?>">
  <div style="padding:8px; float:left;"><?php echo ($start + 1)."-".$next_start." / ".number_format ($objects_total, 0, ".", " ")." ".(!$is_mobile ? getescapedtext ($hcms_lang['objects'][$lang]) : ""); ?></div>
  <div style="margin:0 auto; text-align:center;"><img src="<?php echo getthemelocation(); ?>img/button_arrow_up.png" class="hcmsButtonSizeSquare" style="border:0;" /></div>
</div>
<div id="ButtonNext" class="hcmsMore" style="position:fixed; bottom:0; left:50%; right:0; height:30px; z-index:4; visibility:visible; text-align:left;" <?php if ($objects_total > $end) { ?>onclick="if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display='inline'; window.location='<?php echo "?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&start=".url_encode($next_start); ?>';"<?php } ?> onMouseOver="hcms_hideContextmenu();" title="<?php echo getescapedtext ($hcms_lang['forward'][$lang]); ?>">
  <div style="margin:0 auto; text-align:center;"><img src="<?php echo getthemelocation(); ?>img/button_arrow_down.png" class="hcmsButtonSizeSquare" style="border:0;" /></div>
</div>
<?php
}
// status bar without buttons
else
{
  if ($objects_counted > 0) $next_start = $objects_counted + 1;
  else $next_start = 0;
?>
<!-- status bar -->
<div id="StatusBar" class="hcmsStatusbar" style="position:fixed; bottom:0; width:100%; height:30px; z-index:4; visibility:visible; text-align:left;" onMouseOver="hcms_hideContextmenu();">
  <div style="margin:auto; padding:8px; float:left;"><?php echo $next_start." / ".number_format ($objects_total, 0, ".", " ")." ".(!$is_mobile ? getescapedtext ($hcms_lang['objects'][$lang]) : ""); ?></div>
</div>
<?php
}
?>

<!-- initialize -->
<script type="text/javascript">
initialize();
</script>

<?php includefooter(); ?>
</body>
</html>