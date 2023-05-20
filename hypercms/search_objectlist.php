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
$action = getrequest ("action");
$start = getrequest ("start", "numeric", 0);
$site = getrequest ("site"); // site can be %Null%
$login = getrequest ("login", "objectname");
$search_dir = getrequest ("search_dir", "locationname");
$search_textnode = getrequest ("search_textnode", "array");
$search_expression = getrequest ("search_expression");
$search_fileextension = ""; // not supported by search UI
$search_cat = getrequest ("search_cat", "objectname");
$search_format = getrequest ("search_format", "array");
$search_filesize = getrequest ("search_filesize", "numeric");
$search_filesize_operator = getrequest ("search_filesize_operator");
$search_imagecolor = getrequest ("search_imagecolor", "array");
$search_operator = getrequest ("search_operator", "objectname");
$find_expression = getrequest ("find_expression");
$replace_expression = getrequest ("replace_expression");
$date_from = getrequest ("date_from");
$date_to = getrequest ("date_to");
$from_user = getrequest ("from_user");
$to_user = getrequest ("to_user");
$template = getrequest ("template", "objectname");
$object_id = getrequest ("object_id");
$container_id = getrequest ("container_id");
$geo_border_sw = getrequest ("geo_border_sw");
$geo_border_ne = getrequest ("geo_border_ne");
$search_save = getrequest ("search_save");
$search_execute = getrequest ("search_execute");

// --------------------------------- logic section ----------------------------------

// initialize
$error = array();
$cat = "";
$object_array = array();
$search_dir_esc = array ();
$exclude_dir_esc = array ();
$search_filename = "";
$galleryview = "";
$listview = "";
$items_row = -1;
$items_id = -1;
$objects_total = 0;
$thumbnailsize_small = 120;
$thumbnailsize_medium = 160;
$thumbnailsize_large = 180;
$objects_counted = 0;

// SQL limit (result does not contain unique objetpaths if content is returned)
$limit_standard = 1000;
$limit_large = 2000;

// search parameters as URL coded query string (exluding the limit parameter)
unset ($_REQUEST['start']);
$search_url = http_build_query ($_REQUEST);

// default value for inital max items in search result
if (empty ($mgmt_config['search_max_results'])) $mgmt_config['search_max_results'] = 100;

// define next max number of items
if (is_numeric ($start)) $end = $start + $mgmt_config['search_max_results'];
else $end = $mgmt_config['search_max_results'];

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

// image color keys
if (is_array ($search_imagecolor))
{
  // array holds no values
  if (!array_filter ($search_imagecolor)) $search_imagecolor = "";
}
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
  if (!empty ($labels) && is_array ($labels) && sizeof ($labels) > 0)
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
  if (!empty ($objectlistcols) && is_array ($objectlistcols) && sizeof ($objectlistcols) > 0)
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

// set default columns
if (empty ($objectlistcols_reduced))
{
  $objectlistcols_reduced = array();
  $objectlistcols_reduced['modifieddate'] = 1;
  $objectlistcols_reduced['owner'] = 1;
}
// remove text IDs from search in order to improve performance and due to limited displayof text IDs in the search result
elseif (is_array ($objectlistcols_reduced))
{
  foreach ($objectlistcols_reduced as $text_id => $active)
  {
    // exclude text from search result
    if (strpos ("_".$text_id, "text:") > 0) unset ($objectlistcols_reduced[$text_id]);
  }
}

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// save search parameters
if (!empty ($search_save))
{
  $search_values = array (uniqid(), $mgmt_config['today'], $action, $site, $search_dir, $date_from, $date_to, $template, json_encode($search_textnode), $search_expression, $search_cat, json_encode($search_format), $search_filesize, $search_imagewidth, $search_imageheight, json_encode($search_imagecolor), $search_imagetype, $geo_border_sw, $geo_border_ne, $object_id, $container_id);

  $search_record = createlogentry ($search_values);

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
          list ($search_id, $rest) = explode ("|", trim ($searchlog));

          if ($search_id == $search_execute)
          {
            // update to version 8.0.2
            if (substr_count ($searchlog, "|") == 19)
            {
              $searchlog = "|".$searchlog;
            }

            list ($uniqid, $date, $action, $site, $search_dir, $date_from, $date_to, $template, $search_textnode, $search_expression, $search_cat, $search_format, $search_filesize, $search_imagewidth, $search_imageheight, $search_imagecolor, $search_imagetype, $geo_border_sw, $geo_border_ne, $object_id, $container_id) = explode ("|", trim ($searchlog));

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
if (is_file ($mgmt_config['abs_path_data']."config/plugin.global.php"))
{
  require ($mgmt_config['abs_path_data']."config/plugin.global.php");
}

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $search_dir, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

if (
     $action != "linking" && 
     $action != "base_search" && 
     $action != "recyclebin" &&
     $action != "favorites" && 
     $action != "checkedout" && 
     $action != "clipboard" && 
     ($action != "user_files" && $object_id == "" && $container_id == "") && 
     $action != "recipient" && 
     (!valid_publicationname ($site) || !valid_locationname ($search_dir))
   ) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// create secure token
$token = createtoken ($user);

// write and close session (non-blocking other frames)
suspendsession ();

// search operator for multiple fields in advanced search
if ($search_operator == "AND" || $search_operator == "OR")
{
  $mgmt_config['search_operator'] = $search_operator;
}

// access linking
if ($action == "linking")
{
  $object_array = linking_objects();
}
// deleted objects of a user
elseif ($action == "recyclebin" && $user != "")
{
  if (!empty ($adminpermission)) $object_array = rdbms_getdeletedobjects ("", "", $limit_large, @array_keys ($objectlistcols_reduced), false, false);
  else $object_array = rdbms_getdeletedobjects ($user, "", $limit_large, @array_keys ($objectlistcols_reduced), false, false);
}
// collect all objects of given user 
elseif ($action == "user_files" && $login != "" && $site != "" && (($site == "*Null*" && checkrootpermission ('user')) || checkglobalpermission ($site, 'user')))
{
  $object_array = rdbms_searchuser ($site, $login, $limit_large, @array_keys ($objectlistcols_reduced)); 
}
// search for sender, recipient or date sent
elseif ($action == "recipient")
{
  $object_array = rdbms_searchrecipient ($site, $from_user, $to_user, $date_from, $date_to, $limit_large, @array_keys ($objectlistcols_reduced));
}
// favorites of user
elseif ($action == "favorites" && $user != "")
{
  $object_array = getfavorites ($user, "path", @array_keys ($objectlistcols_reduced));
}
// clipboard of users session
elseif ($action == "clipboard" && $user != "")
{
  $object_array = getclipboard ("path", @array_keys ($objectlistcols_reduced));
}
// checked out objects of user
elseif ($action == "checkedout" && $user != "")
{
  $object_array = getlockedobjects ($user, @array_keys ($objectlistcols_reduced));
}
// search for specific object ID or link ID
elseif ($object_id != "")
{
  $object_info = rdbms_getobject_info ($object_id, @array_keys ($objectlistcols_reduced));
  
  if (is_array ($object_info) && !empty ($object_info['hash']))
  {
    $hash = $object_info['hash'];
    $object_array[$hash] = $object_info;
  }
}
// search for specific container ID
elseif ($container_id != "")
{
  $object_array = rdbms_getobjects ($container_id, "", @array_keys ($objectlistcols_reduced));
}
// search for expression in content
elseif ($action == "base_search" || $search_dir != "")
{
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
    $search_textnode = array();
    $search_textnode[0] = $search_expression;

    // do not search in the location and object name if a text based search is requested
    if ($search_cat == "text")
    {
      $search_filename = "*Null*";
    }
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
          $path_array = link_db_getobject ($pathes);
          
          if (is_array ($path_array))
          {
            foreach ($path_array as $path)
            {
              // add slash if missing
              $path = correctpath ($path);

              // check access permission
              if (!empty ($localpermission[$site_name][$group_name]['page'])) $search_dir_esc[] = convertpath ($site_name, $path, "page");
              else $exclude_dir_esc[] = convertpath ($site_name, $path, "page");
            }
          }
        }
      }

      // component access of user
      foreach ($compaccess as $site_name => $value)
      {
        foreach ($value as $group_name => $pathes)
        {
          // split access-string into an array
          $path_array = link_db_getobject ($pathes);
          
          if (is_array ($path_array))
          {
            foreach ($path_array as $path)
            {
              // add slash if missing
              $path = correctpath ($path);

              // check access permission
              if (!empty ($localpermission[$site_name][$group_name]['component'])) $search_dir_esc[] = convertpath ($site_name, $path, "comp");
              else $exclude_dir_esc[] = convertpath ($site_name, $path, "comp");
            }
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
    
    // start search and replace
    if ($setlocalpermission['create'] == 1 && $find_expression != "")
    {
      $object_array = rdbms_replacecontent ($search_dir_esc, $search_format, $date_from, $date_to, $find_expression, $replace_expression, $user);
    }
    // start search
    else
    {
      $object_array = rdbms_searchcontent ($search_dir_esc, $exclude_dir_esc, $search_format, $date_from, $date_to, $template, $search_textnode, $search_filename, $search_fileextension, $search_filesize, $search_imagewidth, $search_imageheight, $search_imagecolor, $search_imagetype, $geo_border_sw, $geo_border_ne, $limit_standard, @array_keys ($objectlistcols_reduced));
    }
  }
}

if (!empty ($object_array) && is_array ($object_array) && sizeof ($object_array) > 0)
{
  // total results count
  $objects_total = sizeof ($object_array);

  // the hash is used for download and wrapper links
  foreach ($object_array as $hash => $object_item)
  {
    // break loop if maximum has been reached
    if (($items_row + 1) >= $end) break;

    // set object path
    if (!empty ($object_item['objectpath'])) $objectpath = $object_item['objectpath'];
    else $objectpath = $object_item;

    // hashcode and path must be provided
    if ($hash != "count" && substr_count ($objectpath, "/") > 0)
    {
      $container_id = "";
      $contentfile = "";
      $mediafile = "";
      $file_size = "";
      $file_width = "";
      $file_height = "";
      $file_created = "";
      $file_modified = "";
      $file_published = "";
      $file_owner = "";
      $file_connected_copy = "";
      $usedby = "";
      $metadata = "";
      $workflow_status = "";
      $workflow_icon = "";
      $workflow_class = "";
      $container_info = array();

      // media information and content
      if (is_array ($object_item))
      {
        if (!empty ($object_item['container_id'])) { $container_id = $object_item['container_id']; $contentfile = $container_id.".xml"; }
        if (!empty ($object_item['media'])) $mediafile = $object_item['media'];
        if (!empty ($object_item['createdate']) && is_date ($object_item['createdate'])) $file_created = date ("Y-m-d H:i", strtotime ($object_item['createdate']));
        if (!empty ($object_item['date']) && is_date ($object_item['date'])) $file_modified = date ("Y-m-d H:i", strtotime ($object_item['date']));
        if (!empty ($object_item['publishdate']) && is_date ($object_item['publishdate'])) $file_published = date ("Y-m-d H:i", strtotime ($object_item['publishdate']));
        if (!empty ($object_item['user'])) $file_owner = $object_item['user'];
        if (!empty ($object_item['filesize'])) $file_size = number_format ($object_item['filesize'], 0, ".", " ");
        if (!empty ($object_item['width'])) $file_width = $object_item['width'];
        if (!empty ($object_item['height'])) $file_height = $object_item['height'];

        // workflow status
        if (!empty ($object_item['workflowstatus']) && strpos ($object_item['workflowstatus'], "/") > 0)
        {
          list ($workflow_stage, $workflow_maxstage) = explode ("/", $object_item['workflowstatus']);

          if (intval ($workflow_stage) == intval ($workflow_maxstage)) $workflow_status = "passed";
          elseif (intval ($workflow_stage) < intval ($workflow_maxstage)) $workflow_status = "inprogress";

          // workflow icon image
          if ($workflow_status == "passed") $workflow_icon = "<img src=\"".getthemelocation()."img/workflow_accept.png\" class=\"hcmsIconList\" alt=\"".getescapedtext ($hcms_lang['accepted'][$lang])."\" />";
          elseif ($workflow_status == "inprogress") $workflow_icon = "<img src=\"".getthemelocation()."img/workflow_inprogress.png\" class=\"hcmsIconList\" alt=\"".getescapedtext ($hcms_lang['in-progress'][$lang])."\" />";

          // workflow CSS class
          if ($workflow_status == "passed") $workflow_class = "hcmsWorkflowPassed";
          elseif ($workflow_status == "inprogress") $workflow_class = "hcmsWorkflowInprogress";
        }

        // text content
        foreach ($object_item as $text_id=>$content)
        {
          if (substr ($text_id, 0, 5) == "text:") $container_info[$text_id] = $content;
        }
      }

      // ---------------------------------------------------- folder items ----------------------------------------------------
      if ($objectpath != "" && getobject ($objectpath) == ".folder")
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
            // remove _gsdata_ directory created by Cyberduck
            if ($folder == "_gsdata_")
            {
              deletefolder ($site, $location, $folder, $user);
            }
            else
            {
              // count valid objects
              $items_row++;

              // skip rows for paging
              if (!empty ($mgmt_config['explorer_paging']) && $items_row < $start) continue;

              // required for JS table sort
              $items_id++;

              // read file
              if (empty ($container_id))
              {
                $objectdata = loadfile_fast ($location.$folder."/", ".folder");

                if (!empty ($objectdata))
                {
                  // get name of content file and load content container
                  $contentfile = getfilename ($objectdata, "content");
                  $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml")); 
                }
              }

              // create folder file if it does not exist
              if (!is_file ($location.$folder."/.folder"))
              {
                createobject ($site, $location.$folder."/", ".folder", "default.meta.tpl", "sys");
              }

              if (!empty ($container_id))
              {
                // read meta data of media file
                $result = getcontainername ($container_id);             
                if (!empty ($result['user'])) $usedby = $result['user'];
                
                // get metadata of container
                if (!is_array ($object_item) && !empty ($objectlistcols_reduced) && is_array ($objectlistcols_reduced) && sizeof ($objectlistcols_reduced) > 0)
                {
                  $container_info = getmetadata_container ($container_id, @array_keys ($objectlistcols_reduced));
                  
                  if (!empty ($container_info) && is_array ($container_info))
                  {  
                    if (!empty ($container_info['createdate']) && is_date ($container_info['createdate'])) $file_created = date ("Y-m-d H:i", strtotime ($container_info['createdate']));
                    if (!empty ($container_info['date']) && is_date ($container_info['date'])) $file_modified = date ("Y-m-d H:i", strtotime ($container_info['date']));
                    if (!empty ($container_info['publishdate']) && is_date ($container_info['publishdate'])) $file_published = date ("Y-m-d H:i", strtotime ($container_info['publishdate']));
                    if (!empty ($container_info['user'])) $file_owner = $container_info['user'];
                  }
                }
              }

              // connected copies
              if (!empty ($objectlistcols_reduced['connectedcopy']))
              {
                $temp_array = rdbms_getobjects ($container_id);

                if (is_array ($temp_array) && sizeof ($temp_array) > 1) 
                {
                  $file_connected_copy = "<a href=\"javascript:void(0);\" onclick=\"parent.openPopup('page_info_container.php?site=".url_encode($item_site)."&cat=".url_encode($item_cat)."&location=".url_encode($location_esc.$folder)."&page=.folder&from_page=objectlist');\">".getescapedtext ($hcms_lang['show-where-used'][$lang])."</a>";
                }
              }

              // link for copy & paste of download links (not if an access link is used)
              if (!empty ($mgmt_config[$item_site]['sendmail']) && $setlocalpermission['download'] == 1 && linking_valid() == false)
              {
                $dlink_start = "<a id=\"dlink_".$items_id."\" data-linktype=\"download\" data-objectpath=\"".$location_esc.$folder."\" data-href=\"".cleandomain ($mgmt_config['url_path_cms'])."?dl=".$hash."\">";
                $dlink_end = "</a>";
              }
              else
              {
                $dlink_start = "<a id=\"link_".$items_id."\" data-linktype=\"none\" data-objectpath=\"".$location_esc.$folder."\" data-href=\"javascript:void(0);\">";
                $dlink_end = "</a>";
              }

              // fallback for modified date
              if (empty ($file_modified))
              {
                // get file time
                $file_modified = date ("Y-m-d H:i", @filemtime ($location.$folder));
              }

              // listview - view option for locked multimedia objects
              if ($file_info['published'] == false && $action != "recyclebin") $class_image = "class=\"hcmsIconList hcmsIconOff\"";
              else $class_image = "class=\"hcmsIconList\"";            

              // onclick for marking objects
              $selectclick = "onClick=\"hcms_selectObject(this.id, event);\" ";

              // open folder
              if ($action != "recyclebin") $openFolder = "onDblClick=\"parent.location='frameset_objectlist.php?site=".url_encode($item_site)."&cat=".url_encode($item_cat)."&location=".url_encode($location_esc.$folder)."/&token=".$token."';\" ";
              else $openFolder = "";

              // set context
              $hcms_setObjectcontext = "onMouseOver=\"hcms_setObjectcontext('".$item_site."', '".$item_cat."', '".$location_esc."', '.folder', '".$folder_name."', 'Folder', '', '".$folder."', '', '".$token."');\" onMouseOut=\"hcms_resetContext();\" ";

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
              if ($usedby != "")
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
                          <tr id=\"g".$items_id."\" style=\"cursor:pointer\" ".$selectclick.">
                            <td id=\"h".$items_id."_0\" class=\"hcmsCol0 hcmsCell\" style=\"width:280px;\">
                              <div class=\"hcmsObjectListMarker\" ".$hcms_setObjectcontext." ".$openFolder." title=\"".$metadata."\" ondrop=\"hcms_drop(event)\" ondragover=\"hcms_allowDrop(event)\" ".$dragevent.">
                                ".$dlink_start."<img src=\"".getthemelocation()."img/".$file_info['icon']."\" class=\"hcmsIconList\" /> ".$folder_name.$dlink_end." ".$workflow_icon."
                              </div>
                            </td>";
    
              if (!$is_mobile)
              {
                $listview .= "
                              <td id=\"h".$items_id."_1\" class=\"hcmsCol1 hcmsCell\" style=\"width:250px;\"><div ".$hcms_setObjectcontext." title=\"".$item_location."\" style=\"display:block; \">".$item_location."</div></td>";
  
                if (!empty ($objectlistcols_reduced) && is_array ($objectlistcols_reduced))
                {
                  $i = 2;

                  foreach ($objectlistcols_reduced as $key => $active)
                  {
                    if ($i < (sizeof ($objectlistcols_reduced) + 1)) $style_td = "width:125px;";
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
                      elseif ($key == 'connectedcopy')
                      {
                        $title = $file_connected_copy;
                      }
                      else
                      {
                        if (!empty ($container_info[$key])) $title = $container_info[$key];
                        else $title = "";
                      }
                      
                      $listview .= "
                              <td id=\"h".$items_id."_".$i."\" class=\"hcmsCol".$i." hcmsCell\" style=\"".$style_td."\"><div ".$hcms_setObjectcontext." style=\"display:block; ".$style_div."\">".$title."</div></td>";
                      
                      $i++;
                    }
                  }
                }
              }
              
              $listview .= "
                          </tr>";

              $galleryview .= "
                              <div id=\"t".$items_id."\" ".$selectclick." class=\"hcmsObjectUnselected\">
                                <div class=\"hcmsObjectGalleryMarker ".$workflow_class."\" ".$hcms_setObjectcontext." ".$openFolder." title=\"".$folder_name."\" ondrop=\"hcms_drop(event)\" ondragover=\"hcms_allowDrop(event)\" ".$dragevent.">".
                                  $dlink_start."
                                    <div id=\"i".$items_id."\" class=\"hcmsThumbnailFrame hcmsThumbnail".$temp_explorerview."\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" /></div>
                                    <div class=\"hcmsItemName\">".showshorttext($folder_name, 18, 3)."</div>
                                  ".$dlink_end."
                                </div>
                                ".$linking_buttons."
                              </div>";
            }
          }
          // folder does not exist or user has no access permission 
          else $objects_total--;
        }     
      }
      // --------------------------------------------------------- object items ----------------------------------------------------
      elseif ($objectpath != "")
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
            // count valid objects
            $items_row++;

            // skip rows for paging
            if (!empty ($mgmt_config['explorer_paging']) && $items_row < $start) continue;

            // required for JS table sort
            $items_id++;

            // page
            if ($file_info['type'] == "Page") $file_type = getescapedtext ($hcms_lang['object-page'][$lang]);
            // component
            elseif ($file_info['type'] == "Component") $file_type = getescapedtext ($hcms_lang['object-component'][$lang]);    
            // multimedia object 
            else $file_type = getescapedtext ($hcms_lang['file'][$lang])." (".$file_info['type'].")";
  
            // read file
            if (empty ($container_id) || (empty ($mediafile)  && (is_supported ($mgmt_imagepreview, $object) || is_supported ($mgmt_mediapreview, $object) || is_supported ($mgmt_docpreview, $object))))
            {
              $objectdata = loadfile_fast ($location, $object);
  
              if (!empty ($objectdata))
              {
                // get name of content file and load content container
                $contentfile = getfilename ($objectdata, "content");
                $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml"));
                
                // get name of media file
                $mediafile = getfilename ($objectdata, "media");
              }
            }

            if (!empty ($container_id))
            {
              // read meta data of media file
              $result = getcontainername ($container_id);             
              if (!empty ($result['user'])) $usedby = $result['user'];
              
              // get metadata of container
              if (!is_array ($object_item) && !empty ($objectlistcols_reduced) && is_array ($objectlistcols_reduced) && sizeof ($objectlistcols_reduced) > 0)
              {
                $container_info = getmetadata_container ($container_id, @array_keys ($objectlistcols_reduced));
      
                if (!empty ($container_info) && is_array ($container_info))
                { 
                  if (!empty ($container_info['filesize'])) $file_size = number_format ($container_info['filesize'], 0, ".", " ");
                  if (is_date ($container_info['createdate'])) $file_created = date ("Y-m-d H:i", strtotime ($container_info['createdate']));
                  if (is_date ($container_info['date'])) $file_modified = date ("Y-m-d H:i", strtotime ($container_info['date']));
                  if (is_date ($container_info['publishdate'])) $file_published = date ("Y-m-d H:i", strtotime ($container_info['publishdate']));
                  if (!empty ($container_info['user'])) $file_owner = $container_info['user'];
                  if (!empty ($container_info['width'])) $file_width = $container_info['width'];
                  if (!empty ($container_info['height'])) $file_height = $container_info['height'];
                }
              }

              // connected copies
              if (!empty ($objectlistcols_reduced['connectedcopy']))
              {
                $temp_array = rdbms_getobjects ($container_id);

                if (is_array ($temp_array) && sizeof ($temp_array) > 1) 
                {
                  $file_connected_copy = "<a href=\"javascript:void(0);\" onclick=\"parent.openPopup('page_info_container.php?site=".url_encode($item_site)."&cat=".url_encode($item_cat)."&location=".url_encode($location_esc)."&page=".url_encode($object)."&from_page=objectlist');\">".getescapedtext ($hcms_lang['show-where-used'][$lang])."</a>";
                }
              }
  
              if ($mediafile != false)
              {
                // location of file
                $mediadir = getmedialocation ($item_site, $mediafile, "abs_path_media");
                
                // fallback for file size and date modified
                if (empty ($file_size) && is_file ($mediadir.$item_site."/".$mediafile))
                {
                  $file_size = round (@filesize ($mediadir.$item_site."/".$mediafile) / 1024);
                  $file_size = number_format ($file_size, 0, ".", " ");
                  
                  $file_modified = date ("Y-m-d H:i", @filemtime ($mediadir.$item_site."/".$mediafile));
                }
                
                // media file info
                $media_info = getfileinfo ($item_site, $mediafile, $item_cat);
                
                // get metadata for media file
                if (!empty ($mgmt_config['explorer_list_metadata']) && !$is_mobile && !$temp_sidebar) $metadata = getmetadata ("", "", $contentfile, " \r\n");
                
                // link for copy & paste of download links (not if an access link is used)
                if (!empty ($mgmt_config[$item_site]['sendmail']) && $setlocalpermission['download'] == 1 && linking_valid() == false)
                {
                  $dlink_start = "<a id=\"dlink_".$items_id."\" data-linktype=\"download\" data-objectpath=\"".$location_esc.$object."\" data-href=\"".cleandomain ($mgmt_config['url_path_cms'])."?dl=".$hash."\">";
                  $dlink_end = "</a>";
                }
                else
                {
                  $dlink_start = "<a id=\"link_".$items_id."\" data-linktype=\"none\" data-objectpath=\"".$location_esc.$object."\" data-href=\"javascript:void(0);\">";
                  $dlink_end = "</a>";
                }
              }    
              // object without media file
              else
              {
                // get file size
                $file_size = round (@filesize ($location.$object) / 1024);
                if ($file_size == 0) $file_size = 1;
                $file_size = number_format ($file_size, 0, ".", " ");
                
                // get file time
                $file_modified = date ("Y-m-d H:i", @filemtime ($location.$object));
                
                // link for copy & paste of download links (not if an access link is used)
                if (!empty ($mgmt_config[$item_site]['sendmail']) && $setlocalpermission['download'] == 1 && linking_valid() == false)
                {
                  $dlink_start = "<a id=\"link_".$items_id."\" target=\"_blank\" data-linktype=\"wrapper\" data-objectpath=\"".$location_esc.$object."\" data-href=\"".cleandomain ($mgmt_config['url_path_cms'])."?wl=".$hash."\">";
                  $dlink_end = "</a>";
                }
                else
                {
                  $dlink_start = "<a id=\"link_".$items_id."\" data-linktype=\"none\" data-objectpath=\"".$location_esc.$object."\" data-href=\"javascript:void(0);\">";
                  $dlink_end = "</a>";
                }
              }
            }
  
            // open on double click
            if ($action != "recyclebin") $openObject = "onDblClick=\"hcms_openWindow('frameset_content.php?ctrlreload=yes&site=".url_encode($item_site)."&cat=".url_encode($item_cat)."&location=".url_encode($location_esc)."&page=".url_encode($object)."&token=".$token."', '".$container_id."', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");\"";
            else $openObject = "";

            // onclick for marking objects
            $selectclick = "onClick=\"hcms_selectObject(this.id, event);\" ";
            
            // set context
            $hcms_setObjectcontext = "onMouseOver=\"hcms_setObjectcontext('".$item_site."', '".$item_cat."', '".$location_esc."', '".$object."', '".$object_name."', '".$file_info['type']."', '".$mediafile."', '', '', '".$token."');\" onMouseOut=\"hcms_resetContext();\" ";
  
            // metadata
            $metadata = getescapedtext ($hcms_lang['name'][$lang]).": ".$object_name." \r\n".getescapedtext ($hcms_lang['date-modified'][$lang]).": ".showdate ($file_modified, "Y-m-d H:i", $hcms_lang_date[$lang])." \r\n".getescapedtext ($hcms_lang['size-in-kb'][$lang]).": ".$file_size." \r\n".$metadata;
            
            // listview - view option for un/published objects
            if ($file_info['published'] == false && $action != "recyclebin") $class_image = "class=\"hcmsIconList hcmsIconOff\"";
            else $class_image = "class=\"hcmsIconList\"";
            
            // listview - view option for locked objects
            if ($usedby != "")
            {
              $file_info['icon'] = "file_lock.png";
            }
            
            // drag events
            if ($setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
            {
              $dragevent = "draggable=\"true\" ondragstart=\"hcms_drag(event)\"";
            }
            else $dragevent = "";
            
            $listview .= "
                         <tr id=\"g".$items_id."\" style=\"cursor:pointer;\" ".$selectclick.">
                           <td id=\"h".$items_id."_0\"class=\"hcmsCol0 hcmsCell\" style=\"width:280px;\">
                             <div class=\"hcmsObjectListMarker\" ".$hcms_setObjectcontext." ".$openObject." title=\"".$metadata."\" ".$dragevent.">
                               ".$dlink_start."<img src=\"".getthemelocation()."img/".$file_info['icon']."\" ".$class_image." /> ".$object_name.$dlink_end." ".$workflow_icon."
                             </div>
                           </td>";
            
            if (!$is_mobile)
            {
              $listview .= "
                           <td id=\"h".$items_id."_1\" class=\"hcmsCol1 hcmsCell\" style=\"width:250px;\"><div ".$hcms_setObjectcontext." title=\"".$item_location."\" style=\"display:block;\">".$item_location."</div></td>";
  
              if (!empty ($objectlistcols_reduced) && is_array ($objectlistcols_reduced))
              {
                $i = 2;
                
                foreach ($objectlistcols_reduced as $key => $active)
                {
                  if ($i < (sizeof ($objectlistcols_reduced) + 1)) $style_td = "width:125px;";
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
                    elseif ($key == 'connectedcopy')
                    {
                      $title = $file_connected_copy;
                    }
                    else
                    {
                      if (!empty ($container_info[$key])) $title = $container_info[$key];
                      else $title = "";
                    }

                    $listview .= "
                            <td id=\"h".$items_id."_".$i."\" class=\"hcmsCol".$i." hcmsCell\" style=\"".$style_td."\"><div ".$hcms_setObjectcontext." style=\"display:block; ".$style_div."\">".$title."</div></td>";
                  
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
              preparemediafile ($item_site, $thumbdir.$item_site."/", $media_info['filename'].".thumb.jpg", $user);
  
              // try to create thumbnail if not available
              if (!empty ($mgmt_config['recreate_preview']) && (!is_file ($thumbdir.$item_site."/".$media_info['filename'].".thumb.jpg") || !is_cloudobject ($thumbdir.$item_site."/".$media_info['filename'].".thumb.jpg")))
              {
                createmedia ($item_site, $thumbdir.$item_site."/", $thumbdir.$item_site."/", $media_info['file'], "", "thumbnail", true, true);
              }          

              // thumbnail image
              if (is_file ($thumbdir.$item_site."/".$media_info['filename'].".thumb.jpg") || is_cloudobject ($thumbdir.$item_site."/".$media_info['filename'].".thumb.jpg"))
              {
                // galleryview - view option for locked multimedia objects
                if ($file_info['published'] == false && $action != "recyclebin") $class_image = "class=\"lazyload hcmsImageItem hcmsIconOff\"";
                else $class_image = "class=\"lazyload hcmsImageItem\"";

                $thumbnail = "<div id=\"m".$items_id."\" class=\"hcmsThumbnailFrame hcmsThumbnail".$temp_explorerview."\"><img data-src=\"".cleandomain (createviewlink ($item_site, $media_info['filename'].".thumb.jpg", $object_name))."\" ".$class_image." /></div>";
              }
              // display file icon if thumbnail fails 
              else
              {
                // galleryview - view option for locked multimedia objects
                if ($file_info['published'] == false && $action != "recyclebin") $class_image = "class=\"hcmsIconOff\"";
                else $class_image = "";
                        
                $thumbnail = "<div id=\"i".$items_id."\" class=\"hcmsThumbnailFrame hcmsThumbnail".$temp_explorerview."\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" ".$class_image." style=\"max-width:186px; max-height:186px;\" /></div>";
              }           
            }
            // display file icon for non multimedia objects 
            else
            {
              // galleryview - view option for locked multimedia objects
              if ($file_info['published'] == false && $action != "recyclebin") $class_image = "class=\"hcmsIconOff\"";
              else $class_image = "";
                      
              $thumbnail = "<div id=\"i".$items_id."\" class=\"hcmsThumbnailFrame hcmsThumbnail".$temp_explorerview."\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" ".$class_image." style=\"max-width:186px; max-height:186px;\" /></div>";
            }

            // if linking is used display download buttons, display edit button for mobile edition
            $linking_buttons = "";

            if ($mediafile != false && linking_valid() == true && $setlocalpermission['root'] == 1 && $setlocalpermission['download'] == 1)
            {
              // check download of original file
              if (empty ($downloadformats) || (is_document ($mediafile) && !empty ($downloadformats['document']['original'])) || (is_image ($mediafile) && !empty ($downloadformats['image']['original'])))
              {            
                $linking_buttons .= "
                <button class=\"hcmsButtonDownload\" style=\"width:94%;\" onClick=\"openObjectView('".$location_esc."', '".$object."', 'preview');\">".getescapedtext ($hcms_lang['view'][$lang])."</button>
                <a href=\"".cleandomain (createviewlink ($item_site, $mediafile, $object_name, false, "download"))."\" target=\"_blank\"><button class=\"hcmsButtonDownload\" style=\"width:94%;\">".getescapedtext ($hcms_lang['download'][$lang])."</button></a>";
              }
            }

            // if mobile edition is used display edit button
            if ($is_mobile && (($mediafile == "" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1) || ($mediafile != "" && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1)))
            {   
              $linking_buttons .= "
              <button class=\"hcmsButtonDownload\" style=\"width:94%;\" onClick=\"hcms_openWindow('frameset_content.php?ctrlreload=yes&site=".url_encode($item_site)."&cat=".url_encode($item_cat)."&location=".url_encode($location_esc)."&page=".url_encode($object)."&token=".$token."', '".$container_id."', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");\">".getescapedtext ($hcms_lang['edit'][$lang])."</button>";
            }
            
            // if assetbrowser is used display edit button
            if (!empty ($hcms_assetbrowser) && $mediafile != "" && $setlocalpermission['root'] == 1)
            {   
              $linking_buttons .= "
              <button class=\"hcmsButtonDownload\" style=\"width:94%;\" onClick=\"parent.parent.returnMedia('".$location_esc.$object."', '".$object_name."', '".$imgwidth."', '".$imgheight."', '".$file_modified."', '".$file_size."');\">".getescapedtext ($hcms_lang['select'][$lang])."</button>";
            }
            
            if ($linking_buttons != "")
            {
              $linking_buttons = "<div style=\"width:100%; margin:0 auto; padding:0; text-align:center;\">".$linking_buttons."</div>";
            }
  
            $galleryview .= "
                            <div id=\"t".$items_id."\" ".$selectclick." class=\"hcmsObjectUnselected\">
                              <div class=\"hcmsObjectGalleryMarker ".$workflow_class."\" ".$hcms_setObjectcontext." ".$openObject." title=\"".$metadata."\" ".$dragevent.">".
                                $dlink_start."
                                  ".$thumbnail."
                                  <div class=\"hcmsItemName\">".showshorttext($object_name, 18, 3)."</div>
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
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript" src="javascript/contextmenu.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript" src="javascript/jquery/jquery.min.js"></script>
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

.hcmsWorkflowPassed
{
  margin: 2px;
  background-image: url("<?php echo getthemelocation(); ?>img/workflow_accept.png");
  background-repeat: no-repeat !important;
  background-position: 98% 98% !important;
  background-size: 22px 22px !important;
}

.hcmsWorkflowInprogress
{
  margin: 2px;
  background-image: url("<?php echo getthemelocation(); ?>img/workflow_inprogress.png");
  background-repeat: no-repeat !important;
  background-position: 98% 98% !important;
  background-size: 22px 22px !important;
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

// select area
var selectarea;

// design theme
themelocation = '<?php echo getthemelocation(); ?>';

// context menu
contextenable = true;
is_mobile = <?php if (!empty ($is_mobile)) echo "true"; else echo "false"; ?>;
if (is_mobile) hcms_transitioneffect = false;
else hcms_transitioneffect = true;
contextxmove = true;
contextymove = true;

// overwrite permissions from contextmenu.js
<?php if ($action == "recyclebin") { ?>
hcms_permission['rename'] = false;
<?php } ?>
hcms_permission['paste'] = false;

// explorer view option
var explorerview = "<?php echo getescapedtext ($temp_explorerview); ?>";

// verify sidebar
if (parent.document.getElementById('sidebarLayer') && parent.document.getElementById('sidebarLayer').style.width > 0) var sidebar = true;
else var sidebar = false;

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
  else if (viewoption == "medium") style = "width:<?php echo ($thumbnailsize_medium + 16); ?>px; height:<?php echo ($thumbnailsize_medium + 56); ?>px;";
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
  for (var i = 0; i <= <?php echo $items_id; ?>; i++)
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

function openObjectView (location, object, view)
{
  if (location != "" && object != "" && parent.document.getElementById('objectview'))
  {
    parent.openObjectView(location, object, view);
  }
  else return false;
}

// start chat
var chat =  new Chat();

function sendtochat (text)
{
  if (text != "")
  {
    var username = '<?php echo getescapedtext ($user); ?>';
    // strip tags
    username = username.replace(/(<([^>]+)>)/ig,"");
    chat.send(text, username);
  }
}

function resizecols ()
{
  var colwidth;

  for (i = 0; i < <?php if (!empty ($objectlistcols_reduced) && is_array ($objectlistcols_reduced)) echo sizeof ($objectlistcols_reduced) + 1; else echo 1;  ?>; i++)
  {
    // get width of table header columns
    if ($('#c'+i)) colwidth = $('#c'+i).width();

    // set width for table columns
    $('.hcmsCol'+i).width(colwidth);
  }
}

function initialize ()
{
  // set view
  toggleview (explorerview);

  // resize columns
  $("#objectlist_head").colResizable({liveDrag:true, onDrag:resizecols});

  // select area
  selectarea = document.getElementById('selectarea');

  // load screen
  if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display = 'none';

  // collect objects and set objects array
  hcms_collectObjectpath ();

  // focus
  window.focus();
}

// load control frame
parent.frames['controlFrame'].location = 'control_objectlist_menu.php?virtual=1&from_page=<?php echo getescapedtext ($action); ?>';
</script>
</head>

<body id="hcmsWorkplaceObjectlist" class="hcmsWorkplaceObjectlist" onresize="resizecols();">

<!-- load screen --> 
<div id="hcmsLoadScreen" class="hcmsLoadScreen" style="display:none;"></div>

<!-- select area --> 
<div id="selectarea" class="hcmsSelectArea"></div>

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
<div id="contextLayer" style="position:absolute; min-width:160px; max-width:280px; height:320px; z-index:10; left:20px; top:20px; visibility:hidden;">
  <form name="contextmenu_object" action="" method="post" target="_blank" style="display:block;">
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
    <input type="hidden" name="targetlocation" value="" />
    <input type="hidden" name="page" value="" />
    <input type="hidden" name="pagename" value="" />
    <input type="hidden" name="filetype" value="" />
    <input type="hidden" name="media" value="" />
    <input type="hidden" name="folder" value="" />
    <input type="hidden" name="multiobject" value="" />
    <input type="hidden" name="from_page" value="<?php echo $action; ?>" />
    <input type="hidden" name="token" value="<?php echo $token; ?>" />
    <input type="hidden" name="convert_type" value="" />
    <input type="hidden" name="convert_cfg" value="" />

    <div class="hcmsContextMenu">
      <table class="hcmsTableStandard" style="width:100%;">
        <tr>
          <td style="white-space:nowrap;">
            <?php if ($action == "favorites" && linking_valid() == false) { ?>
            <a href="javascript:void(0);" id="href_fav_delete" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('favorites_delete');"><img src="<?php echo getthemelocation(); ?>img/button_favorites_new.png" id="img_fav_delete" class="hcmsIconOn" />&nbsp;<?php echo getescapedtext ($hcms_lang['delete-favorite'][$lang]); ?></a><br />        
            <hr />
            <?php } elseif ($action != "recyclebin" && checkrootpermission ('desktopfavorites') && $setlocalpermission['root'] == 1 && linking_valid() == false) { ?>
            <a href="javascript:void(0);" id="href_fav_create" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('favorites_create');"><img src="<?php echo getthemelocation(); ?>img/button_favorites_delete.png" id="img_fav_create" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['add-to-favorites'][$lang]); ?></a><br />
            <hr />        
            <?php } ?>
            <?php if ($action == "checkedout" && linking_valid() == false) { ?>
            <a href="javascript:void(0);" id="href_unlock" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('checkin');"><img src="<?php echo getthemelocation(); ?>img/button_file_unlock.png" id="img_unlock" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['check-in'][$lang]); ?></a><br />        
            <hr />
            <?php } ?>  
            <a href="javascript:void(0);" id="href_preview" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('preview');"><img src="<?php echo getthemelocation(); ?>img/button_file_preview.png" id="img_preview" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['preview'][$lang]); ?></a><br />  
            <?php if ($action != "recyclebin") { ?>
            <a href="javascript:void(0);" id="href_cmsview" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('cmsview');"><img src="<?php echo getthemelocation(); ?>img/button_edit.png" id="img_cmsview" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['edit'][$lang]); ?></a><br />
            <a href="javascript:void(0);" id="href_notify" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('notify');"><img src="<?php echo getthemelocation(); ?>img/button_notify.png" id="img_notify" class="hcmsIconOn hcmsIconList">&nbsp;<?php echo getescapedtext ($hcms_lang['notify-me'][$lang]); ?></a><br />
            <?php } ?>
            <?php if ($action != "recyclebin" && !empty ($mgmt_config['chat'])) { ?>
            <a href="javascript:void(0);" id="href_chat" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('chat');"><img src="<?php echo getthemelocation(); ?>img/button_chat.png" id="img_chat" class="hcmsIconOn hcmsIconList">&nbsp;<?php echo getescapedtext ($hcms_lang['send-to-chat'][$lang]); ?></a><br />
            <?php } ?>   
            <hr />
            <?php if ($action == "recyclebin") { ?>
            <a href="javascript:void(0);" id="href_emptybin" onClick="hcms_emptyRecycleBin ('<?php echo $token; ?>');"><img src="<?php echo getthemelocation(); ?>img/button_recycle_bin.png" id="img_emptybin" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['empty-recycle-bin'][$lang]); ?></a><br />
            <hr />
            <a href="javascript:void(0);" id="href_restore" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('restore');"><img src="<?php echo getthemelocation(); ?>img/button_import.png" id="img_restore" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['restore'][$lang]); ?></a><br />        
            <?php } ?>  
            <a href="javascript:void(0);" id="href_delete" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('delete');"><img src="<?php echo getthemelocation(); ?>img/button_delete.png" id="img_delete" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?></a><br />     
            <hr />
            <?php if ($action != "recyclebin") { ?>
            <a href="javascript:void(0);" id="href_cut" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('cut');"><img src="<?php echo getthemelocation(); ?>img/button_file_cut.png" id="img_cut" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['cut'][$lang]); ?></a><br />  
            <a href="javascript:void(0);" id="href_copy" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('copy');"><img src="<?php echo getthemelocation(); ?>img/button_file_copy.png" id="img_copy" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['copy'][$lang]); ?></a><br />  
            <a href="javascript:void(0);" id="href_copylinked" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('linkcopy');"><img src="<?php echo getthemelocation(); ?>img/button_file_copylinked.png" id="img_copylinked" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['connected-copy'][$lang]); ?></a><br />   
            <hr />
            <a href="javascript:void(0);" id="href_publish" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('publish');"><img src="<?php echo getthemelocation(); ?>img/button_file_publish.png" id="img_publish" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['publish'][$lang]); ?></a><br />  
            <a href="javascript:void(0);" id="href_unpublish" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('unpublish');"><img src="<?php echo getthemelocation(); ?>img/button_file_unpublish.png" id="img_unpublish" class="hcmsIconOn hcmsIconList" />&nbsp;<?php echo getescapedtext ($hcms_lang['unpublish'][$lang]); ?></a><br />
            <hr />
            <?php } ?> 
            <?php
            // ----------------------------------------- plugins ----------------------------------------------
            if ($action != "recyclebin" && $setlocalpermission['root'] == 1 && empty ($hcms_assetbrowser) && linking_valid() == false && !empty ($mgmt_plugin))
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
    </div>
  </form>
</div>

<!-- Table Header -->
<div id="tableHeadLayer" style="position:fixed; top:0; left:0; margin:0; padding:0; width:100%; z-index:2; visibility:visible;">
  <table id="objectlist_head" style="border-collapse:collapse; border:0; border-spacing:0; padding:0; width:100%; height:20px;">
    <tr>
      <td id="c0" onClick="hcms_sortTable(0); toggleview('');" class="hcmsTableHeader hcmsHead" style="width:280px;">&nbsp;<?php echo getescapedtext ($hcms_lang['name'][$lang]); ?>&nbsp;</td>
    <?php
    if (!$is_mobile)
    {
    ?>
      <td id="c1" onClick="hcms_sortTable(1); toggleview('');" class="hcmsTableHeader hcmsHead" style="width:250px;">&nbsp;<?php echo getescapedtext ($hcms_lang['location'][$lang]); ?>&nbsp;</td> 
    <?php
      if (!empty ($objectlistcols_reduced) && is_array ($objectlistcols_reduced))
      {
        $i = 2;
        
        foreach ($objectlistcols_reduced as $key => $active)
        {
          if ($i < (sizeof ($objectlistcols_reduced) + 1)) $style_td = "width:125px;";
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
            elseif ($key == 'connectedcopy')
            {
              $title = getescapedtext ($hcms_lang['connected-copy'][$lang]);
            }
            else
            {
              // use label
              if (!empty ($labels_reduced[$key])) $title = getlabel ($labels_reduced[$key], $lang);
              // use text ID
              else $title = ucfirst (str_replace ("_", " ", substr ($key, 5)));
              
              if (!is_utf8 ($title)) $title = utf8_encode ($title);
            }
            
            echo "
      <td id=\"c".$i."\" onClick=\"hcms_sortTable(".$i.$sortnumeric.");\" class=\"hcmsTableHeader hcmsHead\" style=\"".$style_td."\">&nbsp;".$title."&nbsp;</td>";

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
<div id="objectLayer" style="position:fixed; top:20px; left:0; bottom:32px; margin:0; padding:0; width:100%; z-index:1; visibility:visible; overflow-x:hidden; overflow-y:scroll;">
  <table id="objectlist" name="objectlist" style="table-layout:fixed; border-collapse:collapse; border:0; border-spacing:0; padding:0; width:100%;">
  <?php 
  echo $listview;
  ?>
  </table>
  <br /><div id="detailviewReset" style="width:100%; height:3px; z-index:3; visibility:visible;" onMouseOver="hcms_hideContextmenu();"></div>
</div>

<!-- Gallery View -->
<div id="galleryviewLayer" style="position:fixed; top:20px; left:0; bottom:32px; margin:0; padding:0; width:100%; z-index:1; visibility:hidden; overflow-y:scroll;">
<?php
if ($galleryview != "")
{
  echo "
  <div id=\"objectgallery\" name=\"objectgallery\">  
    ".$galleryview."
  </div>";
}
?>
  <br /><div style="width:100%; height:3px; z-index:0; visibility:visible;" onMouseOver="hcms_hideContextmenu();"></div>
</div>

<?php
// objects counted (counter starts at 0)
if ($items_row >= 0) $objects_counted = $items_row + 1;

// expanding
if (empty ($mgmt_config['explorer_paging']) && $objects_total > $end)
{
  $next_start = $objects_counted;
?>
<!-- status bar incl. more button -->
<div id="ButtonMore" class="hcmsMore" style="position:fixed; bottom:0; width:100%; height:30px; z-index:4; visibility:visible; text-align:left;" onclick="if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display='inline'; window.location='<?php echo "?".$search_url."&start=".url_encode($next_start); ?>';" onMouseOver="hcms_hideContextmenu();" title="<?php echo getescapedtext ($hcms_lang['more'][$lang]); ?>">
  <div style="padding:8px; float:left;"><?php echo $next_start." / ".number_format ($objects_total, 0, ".", " ")." ".(!$is_mobile ? getescapedtext ($hcms_lang['objects'][$lang]) : ""); ?></div>
  <div style="margin:0 auto; text-align:center;"><img src="<?php echo getthemelocation(); ?>img/button_arrow_down.png" class="hcmsButtonSizeSquare" style="border:0;" /></div>
</div>
<?php
}
// paging
elseif (!empty ($mgmt_config['explorer_paging']) && ($start > 0 || $objects_total >= $end))
{
  // start positions (inital start is 0 and not 1)
  $previous_start = $start - intval ($mgmt_config['search_max_results']);
  $next_start = $objects_counted;
?>
<!-- status bar incl. previous and next buttons -->
<div id="ButtonPrevious" class="hcmsMore" style="position:fixed; bottom:0; left:0; right:50%; height:30px; z-index:4; visibility:visible; text-align:left;" <?php if ($start > 0) { ?>onclick="if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display='inline'; window.location='<?php echo "?".$search_url."&start=".url_encode($previous_start); ?>';"<?php } ?> onMouseOver="hcms_hideContextmenu();" title="<?php echo getescapedtext ($hcms_lang['back'][$lang]); ?>">
  <div style="padding:8px; float:left;"><?php echo ($start + 1)."-".$next_start." / ".number_format ($objects_total, 0, ".", " ")." ".(!$is_mobile ? getescapedtext ($hcms_lang['objects'][$lang]) : ""); ?></div>
  <div style="margin:0 auto; text-align:center;"><img src="<?php echo getthemelocation(); ?>img/button_arrow_up.png" class="hcmsButtonSizeSquare" style="border:0;" /></div>
</div>
<div id="ButtonNext" class="hcmsMore" style="position:fixed; bottom:0; left:50%; right:0; height:30px; z-index:4; visibility:visible; text-align:left;" <?php if ($objects_total > $end) { ?>onclick="if (parent.document.getElementById('hcmsLoadScreen')) parent.document.getElementById('hcmsLoadScreen').style.display='inline'; window.location='<?php echo "?".$search_url."&start=".url_encode($next_start); ?>';"<?php } ?> onMouseOver="hcms_hideContextmenu();" title="<?php echo getescapedtext ($hcms_lang['forward'][$lang]); ?>">
  <div style="margin:0 auto; text-align:center;"><img src="<?php echo getthemelocation(); ?>img/button_arrow_down.png" class="hcmsButtonSizeSquare" style="border:0;" /></div>
</div>
<?php
}
// status bar without buttons
else
{
  if ($objects_counted >= 0) $next_start = $objects_counted;
  else $next_start = 0;
?>
<!-- status bar -->
<div id="StatusBar" class="hcmsStatusbar" style="position:fixed; bottom:0; width:100%; height:30px; z-index:4; visibility:visible; text-align:left;" onMouseOver="hcms_hideContextmenu();">
  <div style="margin:auto; padding:8px; float:left;"><?php echo $next_start." ".(!$is_mobile ? getescapedtext ($hcms_lang['objects'][$lang]) : ""); ?></div>
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