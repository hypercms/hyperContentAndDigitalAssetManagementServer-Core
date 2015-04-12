<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
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
// hyperCMS UI
require ("function/hypercms_ui.inc.php");
// load formats/file extensions
require_once ("include/format_ext.inc.php");


// input parameters
$location = getrequest_esc ("location", "locationname");
$folder = getrequest ("folder", "objectname");
$virtual = getrequest ("virtual", "numeric");
$next = getrequest ("next");
$filter = getrequest ("filter", "array");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// initalize object linking
$objects_total = 0;
$folder_array = array();
$object_array = array();

if (is_array ($hcms_linking) && ($location == "" || deconvertpath ($location, "file") == deconvertpath ($hcms_linking['location'], "file"))) 
{
  $site = $hcms_linking['publication'];
  $cat = $hcms_linking['cat'];
  $location = $hcms_linking['location'];
  if (!empty ($hcms_linking['object'])) $object_array[] = $hcms_linking['object'];

  $objects_total = sizeof ($object_array);
}

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

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

// display setting for mobile
if ($is_mobile) $display = "display:none;";
else $display = "";

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// create secure token
$token = createtoken ($user);

// default value for inital max items in list
if ($mgmt_config['explorer_list_maxitems'] == "") $mgmt_config['explorer_list_maxitems'] = 100; 

// define next max number of items on the list 
if ($next != "" && is_numeric ($next)) $next_max = $next + $mgmt_config['explorer_list_maxitems'];
else $next_max = $mgmt_config['explorer_list_maxitems'];

// define variables depending on category
if (strtolower ($cat) == "page") 
{
  $icon = 'folder.gif';
  $access = $pageaccess;
  $itemname = "page";
}  
elseif (strtolower ($cat) == "comp") 
{
  $icon = 'component.gif';
  $access = $compaccess;
  $itemname = "component";
}

// collect all objects for list
if (
     (valid_locationname ($location) && $hcms_linking['location'] == "") || 
     ($hcms_linking['location'] != "" && $hcms_linking['object'] == "" && substr_count ($location, $hcms_linking['location']) > 0)
   )
{  
  // generate page or component list using access permission data
  if (accesspermission ($site, $location, $cat) == false)
  {
    if (@is_array ($access) && @sizeof ($access) > 0 && is_array ($access[$site]))
    {
      reset ($access);

      while (list ($group, $value) = each ($access[$site]))
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
    $dir = opendir ($location);
    
    if ($dir != false)
    {
      while ($file = @readdir ($dir)) 
      {
        if ($location.$file != "" && $file != '.' && $file != '..') 
        {
          if (!$is_mobile && @is_dir ($location.$file))
          {
            $group_array = accesspermission ($site, $location.$file."/", "$cat");
            $setlocalpermission = setlocalpermission ($site, $group_array, "$cat");
               
            if ($setlocalpermission['root'] == 1) $folder_array[] = $file;
            
            $objects_total++;
          }
          elseif (@is_file ($location.$file) && $file != ".folder")
          {
            $object_array[] = $file;
            
            $objects_total++;     
          }
        }
      }
    }
  
    @closedir ($dir);  
  }
}

// create view of items
$table_cells = 5; //How many images/folders in each row do you want?

// define cell width of table
if ($table_cells == 1) $cell_width = "100%";
elseif ($table_cells == 2) $cell_width = "50%";	
elseif ($table_cells == 3) $cell_width = "33%";		
elseif ($table_cells == 4) $cell_width = "25%";			
elseif ($table_cells == 5) $cell_width = "20%";
elseif ($table_cells == 6) $cell_width = "16%";
else $cell_width = "10%";  

$galleryview = "";
$listview = "";
$items_row = 0;
  
// write folder entries
if (!$is_mobile && is_array ($folder_array) && @sizeof ($folder_array) > 0)
{
  natcasesort ($folder_array);
  reset ($folder_array);

  foreach ($folder_array as $folder)
  {
    if ($folder != "" && $items_row < $next_max)
    {
      // check for location path inside folder variable
      if (substr_count ($folder, "/") >= 1)
      {
        $location = getlocation ($folder);
        $folder = getobject ($folder);
      }
      
      // eventsystem
      if ($eventsystem['onobjectlist_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
        onobjectlist_pre ($site, $cat, $location, $folder, $user);        
      
      // if folder exists
      if (valid_locationname ($location) && valid_objectname ($folder) && is_dir ($location.$folder))
      {     
        // convert location
        $location_esc = convertpath ($site, $location, $cat);
        // get folder name
        $file_info = getfileinfo ($site, $location.$folder."/.folder", $cat);
        $folder_name = $file_info['name'];   
    
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
          
          // link for copy & paste of download links
          if ($mgmt_config[$site]['sendmail'] && $setlocalpermission['download'] == 1)
          {
            $dlink_start = "<a id=\"link_".$items_row."\" data-linktype=\"hash\" data-href=\"".createdownloadlink($site, $location.$folder."/", ".folder", $cat)."\">";
            $dlink_end = "</a>";
          }
          else
          {
            $dlink_start = "";
            $dlink_end = "";
          }
        }
        
        // get file time
        $file_time = date ("Y-m-d H:i", @filemtime ($location.$folder));
        
        // refresh sidebar
        if (!$is_mobile) $sidebarclick = "if (sidebar) hcms_loadSidebar();";
        else $sidebarclick = "";
        // onclick for marking objects  
        $selectclick = "onClick=\"hcms_selectObject('".$items_row."', event); hcms_updateControlObjectListMenu(); ".$sidebarclick."\" ";
        // open folder
        $openFolder = "onDblClick=\"parent.location.href='frameset_objectlist.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc.$folder)."/';\" ";
        // set context
        $hcms_setObjectcontext = "onMouseOver=\"hcms_setObjectcontext('".$site."', '".$cat."', '".$location_esc."', '.folder', '".$folder_name."', 'Folder', '', '".$folder."', '', '".$token."');\" onMouseOut=\"hcms_resetContext();\" ";
        
        $style = "style=\"display:block;\" ";
        
        // listview - view option for locked folders
        if ($usedby != "")
        {
          $file_info['icon'] = "folderlock.gif";
          $file_info['icon_large'] = "folderlock.png";
        } 
        
        $listview .= "<tr id=g".$items_row." ".$selectclick." align=\"left\" style=\"cursor:pointer\">
                       <td id=h".$items_row."_0 width=\"360\" nowrap=\"nowrap\">
                         <input id=\"objectpath\" type=hidden value=\"".$location_esc.$folder."\" />                  
                         <div ".$hcms_setObjectcontext." ".$style." ".$openFolder." title=\"".$folder_name."\">
                           <img src=\"".getthemelocation()."img/".$file_info['icon']."\" align=\"absmiddle\" class=\"hcmsIconList\" />&nbsp;".$dlink_start.showshorttext($folder_name, 40).$dlink_end."&nbsp;
                         </div>
                       </td>\n";
        if (!$is_mobile) $listview .= "<td id=h".$items_row."_1 width=\"120\" nowrap=\"nowrap\"><span ".$hcms_setObjectcontext." ".$style.">&nbsp;&nbsp;".$file_time."</span></td>                   
                       <td id=h".$items_row."_2 width=\"120\" nowrap=\"nowrap\"><span ".$hcms_setObjectcontext." ".$style.">&nbsp;&nbsp;</span></td>
                       <td id=h".$items_row."_3 nowrap=\"nowrap\"><span ".$hcms_setObjectcontext." ".$style.">&nbsp;&nbsp;".$hcms_lang['folder'][$lang]."</span></td>\n";
        $listview .= "</tr>\n";                       
    
        $galleryview .= "<td id=t".$items_row." ".$selectclick." width=\"".$cell_width."\" align=\"center\" valign=\"bottom\">
                          <div ".$hcms_setObjectcontext." ".$openFolder." title=\"".$folder_name."\" style=\"cursor:pointer; display:block;\">".
                            $dlink_start."
                              <div id=\"w".$items_row."\" class=\"hcmsThumbnailWidth".$temp_explorerview."\"><img src=\"".getthemelocation()."img/".$file_info['icon_large']."\" style=\"border:0;\" /></div>
                              ".showshorttext($folder_name, 18, true)."
                            ".$dlink_end."
                          </div>
                       </td>\n";
       
        $items_row++;
      
    		if (is_int ($items_row / $table_cells))
        {
    			$galleryview .= "</tr>\n<tr>\n";
    		}
      }
    }
  }
}

// write object entries
if (is_array ($object_array) && @sizeof ($object_array) > 0)
{
  natcasesort ($object_array);
  reset ($object_array);

  foreach ($object_array as $objectpath)
  {
    // check for location path inside object variable
    if ($objectpath != "" && $items_row < $next_max)
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
  
      // eventsystem
      if ($eventsystem['onobjectlist_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
        onobjectlist_pre ($site, $cat, $location, $object, $user);  
        
      // if object exists
      if (valid_locationname ($location) && valid_objectname ($object) && is_file ($location.$object) && ($cat == "page" || objectfilter ($object)))       
      {       
        // page
        if ($file_info['type'] == "Page") $file_type = $hcms_lang['object-page'][$lang];
        // component
        elseif ($file_info['type'] == "Component") $file_type = $hcms_lang['object-component'][$lang];
        // multimedia object 
        else $file_type = $hcms_lang['file'][$lang]." (".$file_info['type'].")";
  
        // read file
        $objectdata = loadfile ($location, $object);
        
        // get name of media file
        if ($objectdata != false)
        {
          // get name of content file and load content container
          $contentfile = getfilename ($objectdata, "content");
          $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml"));  
          
          // get container info
          if ($contentfile != false)
          {
            $result = getcontainername ($contentfile);
            
            if (!empty ($result['user'])) $usedby = $result['user'];
            else $usedby = "";          
          }
                           
          // get media file      
          $mediafile = getfilename ($objectdata, "media");
        
          if ($mediafile != false)
          {
            // location of file
            $mediadir = getmedialocation ($site, $mediafile, "abs_path_media");
            
            // get file time
            if (is_file ($mediadir.$site."/".$mediafile)) $file_time = date ("Y-m-d H:i", @filemtime ($mediadir.$site."/".$mediafile));
            else $file_time = "-";
            
            // get file size
            if ($mgmt_config['db_connect_rdbms'] != "")
            {
              $media_info = rdbms_getmedia ($container_id);
              $file_size = $media_info['filesize'];
              $file_size = number_format ($file_size, 0, "", "."); 
            }
            elseif (is_file ($mediadir.$site."/".$mediafile))
            {
              $file_size = round (@filesize ($mediadir.$site."/".$mediafile) / 1024);
              if ($file_size == 0) $file_size = 1;
              $file_size = number_format ($file_size, 0, "", ".");                 
            }
            else
            {
              $file_size = "-";
            }
            
            // media file info
            $media_info = getfileinfo ($site, $mediafile, $cat);
            
            // read meta data of media file
            if (!$is_mobile && !$temp_sidebar) $metadata = getmetadata ("", "", $contentfile, " \r\n");
            else $metadata = "";
            
            // link for copy & paste of download links
            if ($mgmt_config[$site]['sendmail'] && $setlocalpermission['download'] == 1)
            {
              $dlink_start = "<a id=\"link_".$items_row."\" data-linktype=\"hash\" data-href=\"".createdownloadlink($site, $location, $object, $cat)."\">";
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
            // get file time
            $file_time = date ("Y-m-d H:i", @filemtime ($location.$object)); 
            
            // get file size
            $file_size = round (@filesize ($location.$object) / 1024);
            if ($file_size == 0) $file_size = 1;
            $file_size = number_format ($file_size, 0, "", ".");

            // link for copy & paste of download links
            if ($mgmt_config[$site]['sendmail'] && $setlocalpermission['download'] == 1)
            {
              $dlink_start = "<a id=\"link_".$items_row."\" target=\"_blank\" data-linktype=\"hash\" data-href=\"".createwrapperlink($site, $location, $object, $cat)."\">";
              $dlink_end = "</a>";
            }
            else
            {
              $dlink_start = "";
              $dlink_end = "";
            }
          }      
        }
        
        // eventsystem
        if ($eventsystem['onobjectlist_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
          onobjectlist_post ($site, $cat, $location, $object, $contentfile, $contentdata, $usedby, $user);     

        // open on double click
        $openObject = "onDblClick=\"hcms_openWindow('frameset_content.php?ctrlreload=yes&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($object)."&token=".$token."', '".$container_id."', 'status=yes,scrollbars=no,resizable=yes', '800', '600');\"";
        // refresh sidebar
        if (!$is_mobile) $sidebarclick = "if (sidebar) hcms_loadSidebar();";
        else $sidebarclick = "";
        // onclick for marking objects      
        $selectclick = "onClick=\"hcms_selectObject('".$items_row."', event); hcms_updateControlObjectListMenu(); ".$sidebarclick."\" ";
        // set context
        $hcms_setObjectcontext = "onMouseOver=\"hcms_setObjectcontext('".$site."', '".$cat."', '".$location_esc."', '".$object."', '".$file_info['name']."', '".$file_info['type']."', '".$mediafile."', '', '', '".$token."');\" onMouseOut=\"hcms_resetContext();\" ";
        $style = "style=\"display:block;\" ";
        
        // metadata
        $metadata = $hcms_lang['name'][$lang].": ".$object_name." \r\n".$hcms_lang['date-modified'][$lang].": ".$file_time." \r\n".$hcms_lang['size-in-kb'][$lang].": ".$file_size." \r\n".$metadata;             
        
        // listview - view option for un/published objects
        if ($file_info['published'] == false) $class_image = "class=\"hcmsIconList hcmsIconOff\"";
        else $class_image = "class=\"hcmsIconList\"";
        
        // listview - view option for locked objects
        if ($usedby != "")
        {
          $file_info['icon'] = "filelock.gif";
          $file_info['icon_large'] = "filelock.png";
        } 

        $listview .= "<tr id=\"g".$items_row."\" align=\"left\" style=\"cursor:pointer;\" ".$selectclick.">
                        <td id=\"h".$items_row."_0\" width=\"360\" nowrap=\"nowrap\">
                          <input id=\"objectpath\" type=\"hidden\" value=\"".$location_esc.$object."\" />
                          <div ".$hcms_setObjectcontext." ".$style." ".$openObject." title=\"".$metadata."\">
                            <img src=\"".getthemelocation()."img/".$file_info['icon']."\" align=\"absmiddle\" ".$class_image." />&nbsp;".$dlink_start.showshorttext($object_name, 40).$dlink_end."&nbsp;   
                          </div>
                        </td>\n";
        if (!$is_mobile) $listview .= "<td id=h".$items_row."_1 width=\"120\" nowrap=\"nowrap\"><span ".$hcms_setObjectcontext." ".$style.">&nbsp;&nbsp;".$file_time."</span></td>
                        <td id=h".$items_row."_2 width=\"120\" nowrap=\"nowrap\"><div align=\"right\" ".$hcms_setObjectcontext." ".$style.">".$file_size."&nbsp;</div></td>
                        <td id=h".$items_row."_3 nowrap=\"nowrap\"><span ".$hcms_setObjectcontext." ".$style.">&nbsp;&nbsp;".$file_type."</span></td>\n";
        $listview .= "</tr>\n"; 
        
        // default value
        $ratio = "Width";
            
    	  // if there is a thumb file, display the thumb
      	if ($mediafile != false && $mediadir != "")
        {              
          // try to create thumbnail if not available
          if ($mgmt_config['recreate_preview'] == true && !file_exists ($mediadir.$site."/".$media_info['filename'].".thumb.jpg") && !file_exists ($mediadir.$site."/".$media_info['filename'].".thumb.flv"))
          {
            createmedia ($site, $mediadir.$site."/", $mediadir.$site."/", $media_info['file'], "", "thumbnail");
          }            
          
          if (@is_file ($mediadir.$site."/".$media_info['filename'].".thumb.jpg") && @filesize ($mediadir.$site."/".$media_info['filename'].".thumb.jpg") > 400)
          {
            $imgsize = getimagesize ($mediadir.$site."/".$media_info['filename'].".thumb.jpg");
            
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
            else $class_image  = "class=\"hcmsImageItem\"";
            
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
            
            $thumbnail = "<div ".$div_id." ".$class_size."><img src=\"".$mgmt_config['url_path_cms']."explorer_wrapper.php?site=".url_encode($site)."&media=".url_encode($site."/".$media_info['filename'].".thumb.jpg")."&token=".hcms_crypt($site."/".$media_info['filename'].".thumb.jpg")."\" ".$class_image." /></div>";
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

        // if linking is used display download buttons
        $linking_buttons = "";
        
        if ($mediafile != false && is_array (getsession ('hcms_linking')) && $setlocalpermission['root'] == 1 && $setlocalpermission['download'] == 1)
        {
          // check download of original file
          if (empty ($downloadformats) || (is_document ($mediafile) && !empty ($downloadformats['document']['original'])) || (is_image ($mediafile) && !empty ($downloadformats['image']['original'])))
          {
            if (!$is_mobile) $width = "160px";
            else $width = "180px";
            
            $linking_buttons = "<div style=\"width:".$width."; margin-left:auto; margin-right:auto; padding:0; text-align:center;\"><a href=\"".$mgmt_config['url_path_cms']."explorer_wrapper.php?name=".$object_name."&media=".$site."/".$mediafile."&token=".hcms_crypt ($site."/".$mediafile)."\" target=\"_blank\"><button class=\"hcmsButtonDownload\">".$hcms_lang['view'][$lang]."</button></a><a href=\"".$mgmt_config['url_path_cms']."explorer_download.php?name=".$object_name."&media=".$site."/".$mediafile."&token=".hcms_crypt ($site."/".$mediafile)."\" target=\"_blank\"><button class=\"hcmsButtonDownload\">".$hcms_lang['download'][$lang]."</button></a></div>";
          }
        }       
  
    		$galleryview .= "<td id=\"t".$items_row."\" ".$selectclick." width=\"".$cell_width."\" align=\"center\" valign=\"bottom\">
                          <div ".$hcms_setObjectcontext." ".$openObject." title=\"".$metadata."\" style=\"cursor:pointer; display:block;\">".
                            $dlink_start."
                              ".$thumbnail."
                              ".showshorttext($object_name, 18, true).
                            $dlink_end."
                          </div>
                          ".$linking_buttons."
                        </td>\n";
        
        $items_row++;
    	
      	if (is_int ($items_row / $table_cells))
        {
      		$galleryview .= "</tr>\n<tr>\n";
      	}
      }
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
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<meta name="viewport" content="width=device-width; initial-scale=1.0; user-scalable=1;">
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
  return confirm (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-remove-the-item'][$lang]); ?>"));
}

function adjust_height ()
{
  height = hcms_getDocHeight();  
  
  setheight = height - 20 - 30;
  document.getElementById('objectLayer').style.height = setheight + "px";
  setheight = height - 30;
  document.getElementById('galleryviewLayer').style.height = setheight + "px";
}

window.onresize = adjust_height;

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
//-->
</script>
</head>

<body id="hcmsWorkplaceObjectlist" style="overflow:hidden;" class="hcmsWorkplaceObjectlist">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['hold-ctrl-key-select-objects-by-click'][$lang]."<br/>".$hcms_lang['hold-shift-key-select-a-group-of-objects-by-2-clicks'][$lang]."<br/>".$hcms_lang['press-alt-key-switch-to-download-links-to-copy-paste-into-e-mails'][$lang], $lang, 3, "position:fixed; top:30px; right:30px;"); ?>

<div id="contextLayer" style="position:absolute; width:150px; height:300px; z-index:10; left:20px; top:20px; visibility:hidden;"> 
  <form name="contextmenu_object" action="" method="post" target="_blank">
    <input type="hidden" name="contextmenustatus" value="" />
    <input type="hidden" name="action" value="" />
    <input type="hidden" name="force" value="" />
    <input type="hidden" name="contexttype" value="none" />
    <input type="hidden" name="xpos" value="" />
    <input type="hidden" name="ypos" value="" />
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="page" value="" />
    <input type="hidden" name="pagename" value="" />
    <input type="hidden" name="filetype" value="" />
    <input type="hidden" name="media" value="" />
    <input type="hidden" name="folder" value="" />
    <input type="hidden" name="multiobject" value="" />
    <input type="hidden" name="memory" value="<?php echo $token; ?>" />
    <input type="hidden" name="token" value="<?php echo $token; ?>" />
    <input type="hidden" name="convert_type" value="" />
    <input type="hidden" name="convert_cfg" value="" />
    
    <table width="150" cellspacing="0" cellpadding="3" class="hcmsContextMenu">
      <tr>
        <td>  
          <a href=# id="href_preview" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('preview');"><img src="<?php echo getthemelocation(); ?>img/button_file_preview.gif" id="img_preview" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['preview'][$lang]; ?></a><br />  
          <?php if ($setlocalpermission['root'] == 1 && ($setlocalpermission['create'] == 1 || $setlocalpermission['upload'] == 1)) { ?>
          <a href=# id="href_cmsview" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('cmsview');"><img src="<?php echo getthemelocation(); ?>img/button_file_edit.gif" id="img_cmsview" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['edit'][$lang]; ?></a><br />     
          <?php } else { ?>
          <a href=# id="_href_cmsview" disabled="disabled"><img src="<?php echo getthemelocation(); ?>img/button_file_edit.gif" id="_img_cmsview" align="absmiddle" border=0 class="hcmsIconOff">&nbsp;<?php echo $hcms_lang['edit'][$lang]; ?></a><br /> 
          <?php } ?>
          <?php if ($setlocalpermission['root'] == 1) { ?>
          <a href=# id="href_notify" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('notify');"><img src="<?php echo getthemelocation(); ?>img/button_notify.gif" id="img_notify" align="absmiddle" border=0 class="hcmsIconOn">&nbsp;<?php echo $hcms_lang['notify-me'][$lang]; ?></a><br />
          <?php } else { ?>
          <a href=# id="_href_notify" disabled="disabled"><img src="<?php echo getthemelocation(); ?>img/button_notify.gif" id="_img_notify" align="absmiddle" border=0 class="hcmsIconOff">&nbsp;<?php echo $hcms_lang['notify-me'][$lang]; ?></a><br /> 
          <?php } ?>
          <?php if ($setlocalpermission['root'] == 1 && isset ($mgmt_config['chat']) && $mgmt_config['chat'] == true) { ?>
          <a href=# id="href_chat" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('chat');"><img src="<?php echo getthemelocation(); ?>img/button_chat.gif" id="img_chat" align="absmiddle" border=0 class="hcmsIconOn">&nbsp;<?php echo $hcms_lang['send-to-chat'][$lang]; ?></a><br />
          <?php } ?>
          <hr />
          <?php if ($setlocalpermission['root'] == 1 && $setlocalpermission['delete'] == 1 && $setlocalpermission['folderdelete'] == 1) { ?>
          <a href=# id="href_delete" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('delete');"><img src="<?php echo getthemelocation(); ?>img/button_delete.gif" id="img_delete" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['delete'][$lang]; ?></a><br />
          <hr />
          <?php } elseif ($setlocalpermission['root'] == 1 && $setlocalpermission['delete'] == 1) { ?>
          <a href=# id="href_delete" onClick="if (checktype('object')==true || checktype('media')==true) hcms_createContextmenuItem ('delete');"><img src="<?php echo getthemelocation(); ?>img/button_delete.gif" id="img_delete" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['delete'][$lang]; ?></a><br />
          <hr />        
          <?php } elseif ($setlocalpermission['root'] == 1 && $setlocalpermission['folderdelete'] == 1) { ?>     
          <a href=# id="href_delete" onClick="if (checktype('folder')==true) hcms_createContextmenuItem ('delete');"><img src="<?php echo getthemelocation(); ?>img/button_delete.gif" id="img_delete" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['delete'][$lang]; ?></a><br />
          <hr />
          <?php } else { ?>
          <a href=# id="_href_delete" disabled="disabled"><img src="<?php echo getthemelocation(); ?>img/button_delete.gif" id="_img_delete" align="absmiddle" border=0 class="hcmsIconOff">&nbsp;<?php echo $hcms_lang['delete'][$lang]; ?></a><br />
          <hr />
          <?php } ?>     
          <?php if ($setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1 && $setlocalpermission['folderrename'] == 1) { ?>
          <a href=# id="href_cut" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('cut');"><img src="<?php echo getthemelocation(); ?>img/button_file_cut.gif" id="img_cut" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['cut'][$lang]; ?></a><br />  
          <a href=# id="href_copy" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('copy');"><img src="<?php echo getthemelocation(); ?>img/button_file_copy.gif" id="img_copy" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['copy'][$lang]; ?></a><br />  
          <a href=# id="href_copylinked" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('linkcopy');"><img src="<?php echo getthemelocation(); ?>img/button_file_copylinked.gif" id="img_copylinked" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['connected-copy'][$lang]; ?></a><br />
          <?php } elseif ($setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1) { ?>
          <a href=# id="href_cut" onClick="if (checktype('object')==true || checktype('media')==true) hcms_createContextmenuItem ('cut');"><img src="<?php echo getthemelocation(); ?>img/button_file_cut.gif" id="img_cut" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['cut'][$lang]; ?></a><br />  
          <a href=# id="href_copy" onClick="if (checktype('object')==true || checktype('media')==true) hcms_createContextmenuItem ('copy');"><img src="<?php echo getthemelocation(); ?>img/button_file_copy.gif" id="img_copy" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['copy'][$lang]; ?></a><br />  
          <a href=# id="href_copylinked" onClick="if (checktype('object')==true || checktype('media')==true) hcms_createContextmenuItem ('linkcopy');"><img src="<?php echo getthemelocation(); ?>img/button_file_copylinked.gif" id="img_copylinked" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['connected-copy'][$lang]; ?></a><br />
          <?php } elseif ($setlocalpermission['root'] == 1 && $setlocalpermission['folderrename'] == 1) { ?> 
          <a href=# id="href_cut" onClick="if (checktype('folder')==true) hcms_createContextmenuItem ('cut');"><img src="<?php echo getthemelocation(); ?>img/button_file_cut.gif" id="img_cut" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['cut'][$lang]; ?></a><br />  
          <a href=# id="href_copy" onClick="if (checktype('folder')==true) hcms_createContextmenuItem ('copy');"><img src="<?php echo getthemelocation(); ?>img/button_file_copy.gif" id="img_copy" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['copy'][$lang]; ?></a><br />  
          <a href=# id="href_copylinked" onClick="if (checktype('folder')==true) hcms_createContextmenuItem ('linkcopy');"><img src="<?php echo getthemelocation(); ?>img/button_file_copylinked.gif" id="img_copylinked" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['connected-copy'][$lang]; ?></a><br />        
          <?php } else { ?>
          <a href=# id="_href_cut" disabled="disabled"><img src="<?php echo getthemelocation(); ?>img/button_file_cut.gif" id="_img_cut" align="absmiddle" border=0 class="hcmsIconOff" />&nbsp;<?php echo $hcms_lang['cut'][$lang]; ?></a><br />  
          <a href=# id="_href_copy" disabled="disabled"><img src="<?php echo getthemelocation(); ?>img/button_file_copy.gif" id="_img_copy" align="absmiddle" border=0 class="hcmsIconOff" />&nbsp;<?php echo $hcms_lang['copy'][$lang]; ?></a><br />  
          <a href=# id="_href_copylinked" disabled="disabled"><img src="<?php echo getthemelocation(); ?>img/button_file_copylinked.gif" id="_img_copylinked" align="absmiddle" border=0 class="hcmsIconOff" />&nbsp;<?php echo $hcms_lang['connected-copy'][$lang]; ?></a><br />  
          <?php } ?>
          <?php if ($setlocalpermission['root'] == 1 && ($setlocalpermission['folderrename'] == 1 || $setlocalpermission['rename'] == 1)) { ?> 
          <a href=# id="href_paste" onClick="hcms_createContextmenuItem ('paste');"><img src="<?php echo getthemelocation(); ?>img/button_file_paste.gif" id="img_paste" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['paste'][$lang]; ?></a><br />  
          <hr />
          <?php } else { ?>
          <a href=# id="href_paste" disabled><img src="<?php echo getthemelocation(); ?>img/button_file_paste.gif" id="img_paste" align="absmiddle" border=0 class="hcmsIconOff">&nbsp;<?php echo $hcms_lang['paste'][$lang]; ?></a><br />  
          <hr />
          <?php } ?>
          <?php if ($virtual == 1 || ($setlocalpermission['root'] == 1 && $setlocalpermission['publish'] == 1)) { ?>
          <a href=# id="href_publish" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('publish');"><img src="<?php echo getthemelocation(); ?>img/button_file_publish.gif" id="img_publish" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['publish'][$lang]; ?></a><br />  
          <a href=# id="href_unpublish" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('unpublish');"><img src="<?php echo getthemelocation(); ?>img/button_file_unpublish.gif" id="img_unpublish" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['unpublish'][$lang]; ?></a><br />
          <hr /> 
          <?php } else { ?>
          <a href=# id="_href_publish" disabled="disabled"><img src="<?php echo getthemelocation(); ?>img/button_file_publish.gif" id="_img_publish" align="absmiddle" border=0 class="hcmsIconOff" />&nbsp;<?php echo $hcms_lang['publish'][$lang]; ?></a><br />  
          <a href=# id="_href_unpublish" disabled="disabled"><img src="<?php echo getthemelocation(); ?>img/button_file_unpublish.gif" id="_img_unpublish" align="absmiddle" border=0 class="hcmsIconOff" />&nbsp;<?php echo $hcms_lang['unpublish'][$lang]; ?></a><br />
          <hr />         
          <?php } ?>  
          <a href=# id="href_print" onClick="hcms_hideContextmenu(); window.print();"><img src="<?php echo getthemelocation(); ?>img/button_print.gif" id="img_print" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['print'][$lang]; ?></a><br />     
          <a href=# id="href_refresh" onClick="document.location.reload();"><img src="<?php echo getthemelocation(); ?>img/button_view_refresh.gif" id="img_refresh" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $hcms_lang['refresh'][$lang]; ?></a>
        </td>
      </tr>    
    </table>
  </form>
</div>

<!-- Detail View -->
<div id="detailviewLayer" style="position:absolute; top:0px; left:0px; margin:0; padding:0; width:100%; height:100%; z-index:1; visibility:visible;">
  <table cellpadding="0" cellspacing="0" cols="4" style="border:0; width:100%; height:20px; table-layout:fixed;"> 
    <tr>
      <td width="360" onClick="hcms_sortTable(0);" class="hcmsTableHeader" nowrap="nowrap">
      &nbsp; <?php echo $hcms_lang['name'][$lang]; ?>
      </td>
      <?php if (!$is_mobile) { ?>
      <td width="120" onClick="hcms_sortTable(1);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo $hcms_lang['date-modified'][$lang]; ?>
      </td>
      <td width="120" onClick="hcms_sortTable(2, true);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo $hcms_lang['size-in-kb'][$lang]; ?>&nbsp;&nbsp;
      </td>    
      <td onClick="hcms_sortTable(3);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo $hcms_lang['type'][$lang]; ?>
      </td>
      <td width="16" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp;
      </td>
      <?php } ?> 
    </tr>
  </table>

  <div id="objectLayer" style="position:absolute; top:20px; left:0px; margin:0; padding:0; width:100%; height:100%; z-index:2; visibility:visible; overflow:scroll;">
    <table id="objectlist" name="objectlist" cellpadding="0" cellspacing="0" cols="4" style="border:0; width:100%; table-layout:fixed;">
    <?php 
    echo $listview;
    ?>
    </table>
    <br /><div id="detailviewReset" style="width:100%; height:3px; z-index:3; visibility:visible;" onMouseOver="hcms_hideContextmenu();"></div>
  </div>
</div>

<!-- Gallery View -->
<div id="galleryviewLayer" style="position:absolute; top:0px; left:0px; margin:0; padding:0; width:100%; height:100%; z-index:1; visibility:hidden; overflow:scroll;">
<?php
if ($galleryview != "")
{
  echo "<table id=\"objectgallery\" name=\"objectgallery\" border=\"0\" cellpadding=\"5\" width=\"98%\" align=\"center\">\n";
  echo "<tr>\n";
  
  // add table cells till tabel row adds up to defined tabel cells in a row
	while (!is_int ($items_row / $table_cells))
  {
    $items_row++;
		$galleryview .= "<td onMouseOver=\"hcms_resetContext();\">&nbsp;</td>\n";
	}
  
  echo $galleryview;
  echo "</tr>\n";
  echo "</table>\n";
}
?>
  <br /><div id="galleryviewReset" style="width:100%; height:3px; z-index:0; visibility:visible;" onMouseOver="hcms_hideContextmenu();"></div>
</div>

<?php
if ($objects_counted >= $next_max)
{
?>
<!-- status bar incl. more button -->
<div id="ButtonMore" class="hcmsMore" style="position:fixed; bottom:0; width:100%; height:30px; z-index:4; visibility:visible; text-align:left;" onclick="window.location.href='<?php echo $_SERVER['PHP_SELF']."?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&next=".url_encode($objects_counted); ?>';" onMouseOver="hcms_hideContextmenu();" title="<?php echo $hcms_lang['more'][$lang]; ?>">
  <div style="padding:8px; float:left;"><?php echo $objects_counted." / ".$objects_total." ".$hcms_lang['objects'][$lang]; ?></div>
  <div style="margin-left:auto; margin-right:auto; text-align:center; padding-top:3px;"><img src="<?php echo getthemelocation(); ?>img/button_explorer_more.gif" style="border:0;" alt="<?php echo $hcms_lang['more'][$lang]; ?>" title="<?php echo $hcms_lang['more'][$lang]; ?>" /></div>
</div>
<?php
}
else
{
?>
<!-- status bar -->
<div id="StatusBar" class="hcmsStatusbar" style="position:fixed; bottom:0; width:100%; height:30px; z-index:3; visibility:visible; text-align:left;" onMouseOver="hcms_hideContextmenu();">
    <div style="margin:auto; padding:8px; float:left;"><?php echo $objects_counted." / ".$objects_total." ".$hcms_lang['objects'][$lang]; ?></div>
</div>
<?php
}
?>

<!-- toggle view -->
<script language="JavaScript">
<!--
toggleview (explorerview);
adjust_height();
//-->
</script>

</body>
</html>
