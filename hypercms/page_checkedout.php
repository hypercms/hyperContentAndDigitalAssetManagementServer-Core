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
// format file extensions
require ("include/format_ext.inc.php");  
// language file
require_once ("language/explorer_objectlist.inc.php");


// input parameters
$action = getrequest ("action");
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$multiobject = getrequest ("multiobject");
$next = getrequest ("next");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// ------------------------------ permission section --------------------------------

// check permissions
if ($rootpermission['desktop'] != 1) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// create secure token
$token = createtoken ($user);

// default value for inital max items in list
if ($mgmt_config['explorer_list_maxitems'] == "") $mgmt_config['explorer_list_maxitems'] = 100; 

// define next max number of items on the list 
if ($next != "" && is_numeric ($next)) $next_max = $next + $mgmt_config['explorer_list_maxitems'];
else $next_max = $mgmt_config['explorer_list_maxitems'];

// get checked out objects of user
$checkedout_data = loadfile ($mgmt_config['abs_path_data']."checkout/", $user.".dat");

// delete entries of user without a corresponding siteaccess
if ($checkedout_data != false)
{
  $savecheckedout = false;
  
  $checkedout_array = explode ("\n", $checkedout_data);
  
  if (is_array ($checkedout_array))
  {
    foreach ($checkedout_array as $checkedout_rec)
    {    
      list ($site, $cat, $container) = explode ("|", $checkedout_rec); 
   
      // if no corresponding siteaccess for this user
      if (!in_array ($site, $siteaccess))
      {
        // get container id
        $container_id = substr ($container, 0, strpos ($container, ".xml"));

        // check-in content container
        $test = unlockfile ($user, getcontentlocation ($container_id, 'abs_path_content'), $container.".wrk");
      
        // remove entry from list
        if ($test == true) $checkedout_data = str_replace ($checkedout_rec."\n", "", $checkedout_data);
        
        $savecheckedout = true;
      }
    }
    
    // save list of checked-out working containers
    if ($savecheckedout == true && ($checkedout_data != false || $checkedout_data == "")) 
    {
      $test = savefile ($mgmt_config['abs_path_data']."checkout/", $user.".dat", $checkedout_data);
    }
  }
}

// get objects from the updated checked-out list
if ($checkedout_data != false) $checkedout_array = explode ("\n", trim ($checkedout_data));
else $checkedout_array = false;

$object_array = array();
$objects_total = 0; 

if ($checkedout_array != false && sizeof ($checkedout_array) > 0)
{      
  $color = false;
  
  reset ($checkedout_array);
 
  foreach ($checkedout_array as $checkedout_rec)
  {
    if (trim ($checkedout_rec) != "")
    {               
      // get container name            
      list ($site, $cat, $container) = explode ("|", trim ($checkedout_rec));
      
      // find corresponding objects in link management database
      $result_array = getconnectedobject ($container);

      if ($result_array != false)
      {  
        foreach ($result_array as $result)
        {
          $location = $result['convertedlocation'];
          $page = $result['object'];
          $page = correctfile ($location, $page, $user);
          
          // check if file exists
          if ($page != false)
          {
            $object_array[] = $location.$page;
            $objects_total++;
          }
        }
      }
    }
  }
}

// create view of items
$table_cells = "5"; //How many images/folders in each row do you want? 

// Makes the tables look nice
if ($table_cells == "1") $cell_width = "100%";
elseif ($table_cells == "2") $cell_width = "50%";	
elseif ($table_cells == "3") $cell_width = "33%";		
elseif ($table_cells == "4") $cell_width = "25%";			
elseif ($table_cells == "5") $cell_width = "20%";
elseif ($table_cells == "6") $cell_width = "16%";
else $cell_width="10%";  

$galleryview = "";
$listview = "";
$items_row = 0;

if (!empty ($object_array) && @sizeof ($object_array) > 0)
{
  natcasesort ($object_array);
  reset ($object_array);
  
  foreach ($object_array as $objectpath)
  {
    // folder items
    // check for location path inside folder variable
    if (getobject ($objectpath) == ".folder" && substr_count ($objectpath, "/") > 0 && $items_row < $next_max)
    {
      // remove .folder file from path
      $objectpath = getlocation ($objectpath);        
      // get site
      $site = getpublication ($objectpath);
      // get category
      $cat = getcategory ($site, $objectpath);
      // get location
      $location_esc = getlocation ($objectpath);
      // get location in file system
      $location = deconvertpath ($location_esc, "file");
      // get location name
      $location_name = getlocationname ($site, $location_esc, $cat, "path");        
      // get folder name
      $folder = getobject ($objectpath);
      $file_info = getfileinfo ($site, $location.$folder, $cat);
      $folder_name = $file_info['name'];              
      
      if (valid_locationname ($location) && valid_objectname ($folder) && is_dir ($location.$folder))
      {
        // check access permissions
        $ownergroup = accesspermission ($site, $location.$folder."/", $cat);
        $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);      
          
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
        
        // refresh sidebar
        if (!$is_mobile) $sidebarclick = "if (sidebar) hcms_loadSidebar();";
        else $sidebarclick = "";
        // onclick for marking objects
        $selectclick = "onClick=\"hcms_selectObject('".$items_row."', event); hcms_updateControlObjectListMenu(); ".$sidebarclick."\" ";        
        // open on double click     
        $openFolder = "onDblClick=\"parent.location.href='frameset_objectlist.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc.$folder)."/&token=".$token."';\" ";
        // set context
        $hcms_setObjectcontext = "onMouseOver=\"hcms_setObjectcontext('".$site."', '".$cat."', '".$location_esc."', '.folder', '".$folder_name."', 'Folder', '', '".$folder."', '');\" onMouseOut=\"hcms_resetContext();\" ";
        
        $style = "style=\"display:block; height:16px;\" ";
        
        // listview - view option for locked folders
        if ($usedby != "")
        {
          $file_info['icon'] = "folderlock.gif";
          $file_info['icon_large'] = "folderlock.png";
        }         
        
        $listview .= "<tr id=g".$items_row." ".$selectclick." align=\"left\" style=\"cursor:pointer\">
                       <td id=h".$items_row."_0 width=\"280\" nowrap=\"nowrap\">
                         <input id=\"objectpath\" type=hidden value=\"".$location_esc.$folder."\" />    
                         <div ".$hcms_setObjectcontext." ".$style." ".$openFolder." title=\"".$folder_name."\">
                           <img src=\"".getthemelocation()."img/".$file_info['icon']."\" align=\"absmiddle\" class=\"hcmsIconList\" />&nbsp;".$dlink_start.showshorttext($folder_name, 30).$dlink_end."&nbsp;
                         </div>
                        </td>\n";
        if (!$is_mobile) $listview .= "<td id=h".$items_row."_1 width=\"250\" nowrap=\"nowrap\"><span ".$hcms_setObjectcontext." ".$style." title=\"".$location_short."\">&nbsp;&nbsp;".showshorttext($location_name, -43)."</span></td>
                        <td id=h".$items_row."_2 width=\"120\" nowrap=\"nowrap\"><span ".$hcms_setObjectcontext." ".$style.">&nbsp;&nbsp;".date ("Y-m-d H:i", filemtime ($location.$folder))."</span></td>
                        <td id=h".$items_row."_3 nowrap=\"nowrap\"><span ".$hcms_setObjectcontext." ".$style.">&nbsp;&nbsp;".$site."</span></td>\n";
        $listview .= "</tr>\n";
        
        $galleryview .= "<td id=t".$items_row." ".$selectclick." width=\"".$cell_width."\" align=\"center\" valign=\"bottom\">
                          <div ".$hcms_setObjectcontext." ".$openFolder." title=\"".$folder_name."\" style=\"cursor:pointer;\">".
                            $dlink_start."
                            <div id=\"w".$items_row."\" class=\"hcmsThumbnailWidth".$temp_explorerview."\"><img src=\"".getthemelocation()."img/".$file_info['icon_large']."\" style=\"border:0;\" /></div><br />
                            ".showshorttext($folder_name, 40)."
                            ".$dlink_end."
                          </div>
                        </td>\n";
      }      
    }
    // object items
    else
    {
      // check for location path inside folder variable
      if (substr_count ($objectpath, "/") > 0 && $items_row < $next_max)
      {
        // get site
        $site = getpublication ($objectpath);
        // get category
        $cat = getcategory ($site, $objectpath);
        // get location
        $location_esc = getlocation ($objectpath);
        // get location in file system
        $location = deconvertpath ($location_esc, "file");
        // get location name
        $location_name = getlocationname ($site, $location_esc, $cat, "path");      

        // get object
        $object = getobject ($objectpath);
        $object = correctfile ($location, $object, $user);     
        $file_info = getfileinfo ($site, $location.$object, $cat);
        $object_name = $file_info['name'];
       
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

        if (valid_locationname ($location) && valid_objectname ($object) && is_file ($location.$object))
        {
          $mediafile = false;
          $metadata = "";
          
          // check access permissions
          $ownergroup = accesspermission ($site, $location, $cat);
          $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);      
      
          // page
          if ($file_info['type'] == "Page") $file_type = $text4[$lang];
          // component
          elseif ($file_info['type'] == "Component") $file_type = $text5[$lang];   
          // multimedia object 
          else $file_type = $text6[$lang]." (".$file_info['type'].")";

          // if object is of any multimedia type
          if ($file_info['ext'] == "" || substr_count ($hcms_ext['cms'], $file_info['ext']) == 0)
          {   
            // read file
            $objectdata = loadfile ($location, $object);
            
            // get name of media file
            $mediafile = getfilename ($objectdata, "media");                 
            
            if ($mediafile != false)
            {
              // location of file
              $mediadir = getmedialocation ($site, $mediafile, "abs_path_media");
             
              // media file info
              $media_info = getfileinfo ($site, $mediafile, $cat);
              
              // get file size
              if (is_file ($mediadir.$site."/".$mediafile))
              {              
                $file_size = round (@filesize ($mediadir.$site."/".$mediafile) / 1024);
                if ($file_size == 0) $file_size = 1;
                $file_size = number_format ($file_size, 0, "", ".");
                  
                // get file time
                $file_time = date ("Y-m-d H:i", @filemtime (getmedialocation ($site, $mediafile, "abs_path_media").$site."/".$mediafile));                  
              }
              else
              {
                $file_size = "-";
                $file_time = "-";
              }
                      
              // get name of content file and load content container
              $contentfile = getfilename ($objectdata, "content");
              
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
          }

          // object without media file
          if (!$mediafile)
          {
            // get file size
            $file_size = round (@filesize ($location.$object) / 1024);
            if ($file_size == 0) $file_size = 1;
            $file_size = number_format ($file_size, 0, "", ".");
            
            // get file time
            $file_time = date ("Y-m-d H:i", @filemtime ($location.$object));
            
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
          
          // open on double click
          $openObject = "onDblClick=\"window.open('frameset_content.php?ctrlreload=yes&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($object)."&token=".$token."','".$container_id."','status=yes,scrollbars=no,resizable=yes,width=800,height=600');\"";
          // refresh sidebar
          if (!$is_mobile) $sidebarclick = "if (sidebar) hcms_loadSidebar();";
          else $sidebarclick = ""; 
          // onclick for marking objects
          $selectclick = "onClick=\"hcms_selectObject('".$items_row."', event); hcms_updateControlObjectListMenu(); ".$sidebarclick."\" ";
          // set context
          $hcms_setObjectcontext = "onMouseOver=\"hcms_setObjectcontext('".$site."', '".$cat."', '".$location_esc."', '".$object."', '".$object_name."', '".$file_info['type']."', '".$mediafile."', '', '', '".$token."');\" onMouseOut=\"hcms_resetContext();\" ";
          $style = "style=\"display:block; height:16px;\" ";
          
          // metadata
          $metadata = $text0[$lang].": ".$object_name." \r\n".$text2[$lang].": ".$file_time." \r\n".$text7[$lang].": ".$file_size." \r\n".$metadata;
          
          // listview - view option for un/published objects
          if ($file_info['published'] == false) $class_image = "class=\"hcmsIconList hcmsIconOff\"";
          else $class_image = "class=\"hcmsIconList\"";
          
          // listview - view option for locked objects
          if ($usedby != "")
          {
            $file_info['icon'] = "filelock.gif";
            $file_info['icon_large'] = "filelock.png";
          }                 
          
          $listview .= "<tr id=g".$items_row." align=\"left\" style=\"cursor:pointer;\" ".$selectclick.">
                          <td id=h".$items_row."_0 width=\"280\" nowrap=\"nowrap\">
                           <input id=\"objectpath\" type=hidden value=\"".$location_esc.$object."\" />
                           <div ".$hcms_setObjectcontext." ".$style." ".$openObject." title=\"".$metadata."\">
                             <img src=\"".getthemelocation()."img/".$file_info['icon']."\" align=\"absmiddle\" ".$class_image." />&nbsp;".$dlink_start.showshorttext($object_name, 39).$dlink_end."&nbsp;
                           </div>
                          </td>\n";
          if (!$is_mobile) $listview .= "<td id=h".$items_row."_1 width=\"250\" nowrap=\"nowrap\"><span ".$hcms_setObjectcontext." ".$style." title=\"".$location_name."\">&nbsp;&nbsp;".showshorttext($location_name, -43)."</span></td>
                          <td id=h".$items_row."_2 width=\"120\" nowrap=\"nowrap\"><span ".$hcms_setObjectcontext." ".$style.">&nbsp;&nbsp;".$file_time."</span></td>
                          <td id=h".$items_row."_3 nowrap=\"nowrap\"><span ".$hcms_setObjectcontext." ".$style.">&nbsp;&nbsp;".$site."</span></td>\n";
          $listview .= "</tr>\n";     
                                
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
              // default value
              else
              {
                $ratio = "Width";
              }
                        
              // galleryview - view option for locked multimedia objects
              if ($file_info['published'] == false) $class_image = "class=\"hcmsIconOff\"";
              else $class_image = "class=\"hcmsImageItem\"";               
          		
              $thumbnail = "<div id=\"".strtolower(substr($ratio, 0, 1)).$items_row."\" class=\"hcmsThumbnail".$ratio.$temp_explorerview."\"><img src=\"".$mgmt_config['url_path_cms']."explorer_wrapper.php?site=".url_encode($site)."&media=".url_encode($site."/".$media_info['filename'].".thumb.jpg")."&token=".hcms_crypt($site."/".$media_info['filename'].".thumb.jpg")."\" ".$class_image." /></div>";
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
          if ($mediafile != false && is_array ($_SESSION['hcms_linking']) && $setlocalpermission['root'] == 1 && $setlocalpermission['download'] == 1)
          {
            if (!$is_mobile) $width = "160px";
            else $width = "180px";
          
            $linking_buttons = "<div style=\"width:".$width."; margin-left:auto; margin-right:auto; padding:0; text-align:center;\"><a href=\"".$mgmt_config['url_path_cms']."explorer_wrapper.php?name=".$object_name."&media=".$site."/".$mediafile."&token=".hcms_crypt($site."/".$mediafile)."\" target=\"_blank\"><button class=\"hcmsButtonDownload\">".$text23[$lang]."</buttons></a><a href=\"".$mgmt_config['url_path_cms']."explorer_download.php?name=".$object_name."&media=".$site."/".$mediafile."&token=".hcms_crypt($site."/".$mediafile)."\"><button class=\"hcmsButtonDownload\">".$text24[$lang]."</button></a></div>";
          }
          else $linking_buttons = "";               
          
          $galleryview .= "<td id=t".$items_row." ".$selectclick." width=\"".$cell_width."\" align=\"center\" valign=\"bottom\">
                            <div ".$hcms_setObjectcontext." ".$openObject." title=\"".$metadata."\" style=\"cursor:pointer; display:block;\">".
                              $dlink_start."
                                ".$thumbnail."
                                ".showshorttext($object_name, 18, true)."
                              ".$dlink_end."
                            </div>
                            ".$linking_buttons."
                         </td>\n";
        }
      }
    }
    
    $items_row++;
    
  	if (is_int ($items_row / $table_cells))
    {
  		$galleryview .= "</tr>\n<tr>\n";
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
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/navigator.css">
<script src="javascript/main.js" language="JavaScript" type="text/javascript"></script>
<script src="javascript/contextmenu.js" language="JavaScript" type="text/javascript"></script>
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
  return confirm (hcms_entity_decode("<?php echo $text18[$lang]; ?>"));
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
//-->
</script>
</head>

<body id="hcmsWorkplaceObjectlist" class="hcmsWorkplaceObjectlist">

<?php if (!$is_mobile) echo showinfobox ($text27[$lang]."<br/>".$text28[$lang]."<br/>".$text29[$lang], $lang, 3, "position:fixed; top:30px; right:30px;"); ?>

<div id="contextLayer" style="position:absolute; width:150px; height:285px; z-index:10; left: 20px; top: 20px; visibility: hidden;"> 
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
    <input type="hidden" name="from_page" value="page_checkout" />
    <input type="hidden" name="token" value="" />
    
    <table width="150px" cellspacing="0" cellpadding="3" class="hcmsContextMenu" />
      <tr>
        <td>  
          <a href=# id="href_preview" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('preview');"><img src="<?php echo getthemelocation(); ?>img/button_file_preview.gif" id="img_preview" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $text8[$lang]; ?></a><br />  
          <a href=# id="href_cmsview" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('cmsview');"><img src="<?php echo getthemelocation(); ?>img/button_file_edit.gif" id="img_cmsview" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $text9[$lang]; ?></a><br />     
          <hr />
          <a href=# id="href_delete" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('delete');"><img src="<?php echo getthemelocation(); ?>img/button_delete.gif" id="img_delete" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $text17[$lang]; ?></a><br />     
          <hr />
          <a href=# id="href_cut" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('cut');"><img src="<?php echo getthemelocation(); ?>img/button_file_cut.gif" id="img_cut" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $text10[$lang]; ?></a><br />  
          <a href=# id="href_copy" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('copy');"><img src="<?php echo getthemelocation(); ?>img/button_file_copy.gif" id="img_copy" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $text11[$lang]; ?></a><br />  
          <a href=# id="href_copylinked" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('linkcopy');"><img src="<?php echo getthemelocation(); ?>img/button_file_copylinked.gif" id="img_copylinked" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $text12[$lang]; ?></a><br />   
          <hr />
          <a href=# id="href_publish" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('publish');"><img src="<?php echo getthemelocation(); ?>img/button_file_publish.gif" id="img_publish" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $text14[$lang]; ?></a><br />  
          <a href=# id="href_unpublish" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('unpublish');"><img src="<?php echo getthemelocation(); ?>img/button_file_unpublish.gif" id="img_unpublish" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $text15[$lang]; ?></a><br />
          <hr />     
          <a href=# id="href_unlock" onClick="if (checktype('object')==true || checktype('media')==true || checktype('folder')==true) hcms_createContextmenuItem ('checkin');"><img src="<?php echo getthemelocation(); ?>img/button_file_unlock.gif" id="img_unlock" align="absmiddle" border=0 />&nbsp;<?php echo $text21[$lang]; ?></a><br />        
          <hr />   
          <a href=# id="href_print" onClick="hcms_hideContextmenu(); window.print();"><img src="<?php echo getthemelocation(); ?>img/button_print.gif" id="img_print" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $text22[$lang]; ?></a><br />        
          <a href=# id="href_refresh" onClick="document.location.reload();"><img src="<?php echo getthemelocation(); ?>img/button_view_refresh.gif" id="img_refresh" align="absmiddle" border=0 class="hcmsIconOn" />&nbsp;<?php echo $text16[$lang]; ?></a>
        </td>
      </tr>    
    </table>
  </form>
</div>

<div id="detailviewLayer" style="position:absolute; top:0px; left:0px; width:100%; height:100%; z-index:1; visibility:visible;">
  <table cellpadding="0" cellspacing="0" cols="4" style="border:0; width:100%; height:20px; table-layout:fixed;"> 
    <tr>
      <td width="280" onClick="hcms_sortTable(0);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo $text0[$lang]; ?>
      </td>
      <?php if (!$is_mobile) { ?>
      <td width="250" onClick="hcms_sortTable(1);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo $text19[$lang]; ?>
      </td>          
      <td width="120" onClick="hcms_sortTable(2);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo $text2[$lang]; ?>
      </td>
      <td onClick="hcms_sortTable(3);" class="hcmsTableHeader" nowrap="nowrap">
        &nbsp; <?php echo $text20[$lang]; ?>
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
    <br /><div style="width:100%; height:2px; z-index:0; visibility:visible;" onMouseOver="hcms_hideContextmenu();"></div>
  </div>
</div>

<div id="galleryviewLayer" style="position:absolute; Top:0px; Left:0px; width:100%; height:100%; z-index:1; visibility:hidden; overflow:scroll;">
<?php
if ($galleryview != "")
{
  echo "<table id=\"objectgallery\" border=\"0\" cellpadding=\"5\" width=\"98%\" align=\"center\">\n";
  echo "<tr>\n"; 
  
  // add table cells till tabel row adds up to defined tabel cells in a row
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

<?php
if ($objects_counted >= $next_max)
{
?>
<!-- status bar incl. more button -->
<div id="ButtonMore" class="hcmsMore" style="position:fixed; bottom:0; width:100%; height:30px; z-index:4; visibility:visible; text-align:left;" onclick="window.location.href='<?php echo $_SERVER['PHP_SELF']."?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&next=".url_encode($objects_counted); ?>';" onMouseOver="hcms_hideContextmenu();" title="<?php echo $text25[$lang]; ?>">
  <div style="padding:8px; float:left;"><?php echo $objects_counted." / ".$objects_total." ".$text26[$lang]; ?></div>
  <div style="margin-left:auto; margin-right:auto; text-align:center; padding-top:3px;"><img src="<?php echo getthemelocation(); ?>img/button_explorer_more.gif" style="border:0;" alt="<?php echo $text25[$lang]; ?>" title="<?php echo $text25[$lang]; ?>" /></div>
</div>
<?php
}
else
{
?>
<!-- status bar -->
<div id="StatusBar" class="hcmsStatusbar" style="position:fixed; bottom:0; width:100%; height:30px; z-index:3; visibility:visible; text-align:left;" onMouseOver="hcms_hideContextmenu();">
    <div style="margin:auto; padding:8px; float:left;"><?php echo $objects_counted." / ".$objects_total." ".$text26[$lang]; ?></div>
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

