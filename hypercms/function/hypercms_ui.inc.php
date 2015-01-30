<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// ================================ USER INTERFACE / LAYOUT ITEMS ===============================

// --------------------------------------- toggleview -------------------------------------------
// function: toggleview ()
// input: view [detail,small,medium,large]
// output: true / false

// description:
// sets explorer objectlist view.

function toggleview ($view)
{
  global $mgmt_config;
  
  // register explorer view type
  // if set manually
  if (!empty ($view) && ($view == "detail" || $view == "small" || $view == "medium" || $view == "large"))
  {
    $_SESSION['hcms_temp_explorerview'] = $view;
    return true;
  }
  // if not set and object linking is used, use medium gallery view
  elseif (empty ($_SESSION['hcms_temp_explorerview']) && is_array ($_SESSION['hcms_linking']))
  {
    $_SESSION['hcms_temp_explorerview'] = "small";
    return true;
  }
  // if not set at all
  elseif (
    empty ($_SESSION['hcms_temp_explorerview']) && $mgmt_config['explorerview'] != "" && 
    ($mgmt_config['explorerview'] == "detail" || $mgmt_config['explorerview'] == "small" || $mgmt_config['explorerview'] == "medium" || $mgmt_config['explorerview'] == "large")
  )
  {
    $_SESSION['hcms_temp_explorerview'] = $mgmt_config['explorerview'];
    return true;
  }
  else return false;
}

// --------------------------------------- togglesidebar -------------------------------------------
// function: togglesidebar ()
// input: view [true,false]
// output: true / false

// description:
// enables or disables sidebar

function togglesidebar ($view)
{
  global $mgmt_config;
  
  // register sidebar
  if (!empty ($view) && $view != false && $view != "false")
  {
    $_SESSION['hcms_temp_sidebar'] = true;
    return true;
  }
  else
  {
    $_SESSION['hcms_temp_sidebar'] = false;
    return true;
  }
}

// --------------------------------------- setfilter -------------------------------------------
// function: setfilter ()
// input: set of filtera as array with keys [comp,image,document,video,audio] and value [0,1]
// output: true / false

// description:
// set filter settings for object view in session.

function setfilter ($filter_set)
{
  global $mgmt_config;
  
  if (is_array ($filter_set))
  {
    // define filter names
    $filter_names = array ("comp", "image", "document", "video", "audio", "flash", "compressed", "binary");
    
    // unset session variable
    unset ($_SESSION['hcms_objectfilter']);
    
    foreach ($filter_names as $filter)
    {
      // register only active filters in session
      if (!empty ($filter_set[$filter]) && ($filter_set[$filter] == 1 || $filter_set[$filter] == "1" || strtolower($filter_set[$filter]) == "yes"))
      {
        $_SESSION['hcms_objectfilter'][$filter] = 1;
      }
    }
    
    return true;
  }
  else return false;
}

// --------------------------------------- objectfilter -------------------------------------------
// function: objectfilter ()
// input: file name
// output: true / false

// description:
// if an object / file name is passing the filter-test.

function objectfilter ($file)
{
  global $mgmt_config, $hcms_ext;
  
  // load formats/file extensions
  if (!is_array ($hcms_ext)) require_once ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
  
  // get object filter from session
  $objectfilter = getsession ("hcms_objectfilter");
  
  // check 
  if (!empty ($file) && is_array ($hcms_ext) && is_array ($objectfilter))
  {
    $file_ext = strtolower (strrchr ($file, "."));
    $ext = "";
    
    foreach ($objectfilter as $filter => $value)
    {
      if ($filter == "comp" && $value == 1) $ext .= $hcms_ext['cms'];
      elseif ($filter == "image" && $value == 1) $ext .= $hcms_ext['image'];
      elseif ($filter == "document" && $value == 1) $ext .= $hcms_ext['bintxt'].$hcms_ext['cleartxt'];
      elseif ($filter == "video" && $value == 1) $ext .= $hcms_ext['video'];
      elseif ($filter == "audio" && $value == 1) $ext .= $hcms_ext['audio'];
      elseif ($filter == "flash" && $value == 1) $ext .= $hcms_ext['flash'];
      elseif ($filter == "compressed" && $value == 1) $ext .= $hcms_ext['compressed'];
      elseif ($filter == "binary" && $value == 1) $ext .= $hcms_ext['binary'];
    }
    
    if (@substr_count (strtolower ($ext."."), $file_ext.".") > 0) return true;
    else return false;
  }
  elseif (!is_array ($objectfilter))
  {
    return true;
  }
  else return false;
}

// --------------------------------------- showshorttext -------------------------------------------
// function: showmessage ()
// input: text as string, max. length of text (minus length starting from the end) (optional),
//        line break instead of cut [true,false] only if length is positive (optional)
// output: shorten text if possible

// description:
// reduce the length of a string and adding "..."

function showshorttext ($text, $length=0, $linebreak=false)
{
  if ($text != "" && $length > 0)
  {
    if (!$linebreak)
    {
      if (strlen ($text) > $length) return substr ($text, 0, $length)."...";
      else return $text;
    }
    else
    {
      // max. 3 lines
      if (strlen ($text) > ($length * 3)) $text = substr ($text, 0, $length)."<br />\n".substr ($text, $length, $length)."<br />\n".substr ($text, ($length*2), ($length-2))."...";
      elseif (strlen ($text) > ($length * 2)) $text = substr ($text, 0, $length)."<br />\n".substr ($text, $length, $length)."<br />\n".substr ($text, ($length*2));
      elseif (strlen ($text) > $length) $text = substr ($text, 0, $length)."<br />\n".substr ($text, $length);
      
      // keep 
      return "<div style=\"vertical-align:top; height:50px; display:block;\">".$text."</div>";
    }
  }
  elseif ($text != "" && $length < 0)
  {
    if (strlen ($text) > ($length * -1)) return "...".substr ($text, $length);
    else return $text;
  }
  else return $text;
}

// --------------------------------------- showtopbar -------------------------------------------
// function: showtopbar ()
// input: message, language code (optional), close button link (optional), link target (optional), individual button (optional), ID of div-layer (optional)
// output: top bar box / false on error

// description:
// shows the standard top bar with or without close button

function showtopbar ($show, $lang="en", $close_link="", $close_target="", $individual_button="", $id="bar")
{
  global $mgmt_config;
  
  require ($mgmt_config['abs_path_cms']."language/buildview.inc.php");
         
  if ($show != "" && strlen ($show) < 600 && $lang != "")
  {
    $close_button_code = "";
    $individual_button_code = "";
    
    // define close button
    if (trim ($close_link) != "")
    {
      $close_id = uniqid();
      $close_button_code = "<td style=\"width:26px; text-align:right; vertical-align:middle;\"><a href=\"".$close_link."\" target=\"".$close_target."\" onMouseOut=\"hcms_swapImgRestore();\" onMouseOver=\"hcms_swapImage('close_".$close_id."','','".getthemelocation()."img/button_close_over.gif',1);\"><img name=\"close_".$close_id."\" src=\"".getthemelocation()."img/button_close.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".$text33[$lang]."\" title=\"".$text33[$lang]."\" /></a></td>\n";
    }
    
    if (trim ($individual_button) != "")
    {
      $individual_button_code = "<td style=\"width:26px; text-align:right; vertical-align:middle;\">".$individual_button."</td>";
    }
    
    return "  <div id=\"".$id."\" class=\"hcmsWorkplaceBar\">
    <table style=\"width:100%; height:100%; padding:0; border-spacing:0; border-collapse:collapse;\">
      <tr>
        <td class=\"hcmsHeadline\" style=\"text-align:left; vertical-align:middle; padding:0; margin:0; white-space:nowrap;\">&nbsp;".$show."&nbsp;</td>".
        $individual_button_code.$close_button_code.
      "</tr>
    </table>
  </div>
  <div style=\"width:100%; height:34px;\">&nbsp;</div>\n";
  }
  else return false;
}

// --------------------------------------- showtopmenubar -------------------------------------------
// function: showtopmenubar ()
// input: message, menu as array [key=name, value=properties/events], language code (optional), close button link (optional), link target (optional), ID of div-layer (optional)
// output: top bar box / false on error

// description:
// shows the menu top bar with or without close button

function showtopmenubar ($show, $menu_array, $lang="en", $close_link="", $close_target="", $id="bar")
{
  global $mgmt_config;
  
  require ($mgmt_config['abs_path_cms']."language/buildview.inc.php");
         
  if ($show != "" && is_array ($menu_array) && strlen ($show) < 600 && $lang != "")
  {
    // define close button
    if ($close_link != "")
    {
      $close_id = uniqid();
      $close_button = "<td style=\"width:26px; text-align:right; vertical-align:middle;\"><a href=\"".$close_link."\" target=\"".$close_target."\" onMouseOut=\"hcms_swapImgRestore();\" onMouseOver=\"hcms_swapImage('close_".$close_id."','','".getthemelocation()."img/button_close_over.gif',1);\"><img name=\"close_".$close_id."\" src=\"".getthemelocation()."img/button_close.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".$text33[$lang]."\" title=\"".$text33[$lang]."\" /></a></td>\n";
    }
    else $close_button = "";
    
    // define text or button
    $menu_button = "";
    
    foreach ($menu_array as $name => $events)
    {
      $menu_button .= "<div class=\"hcmsButtonMenu\" ".$events.">".$name."</div>";
    }
    
    return "  <div id=\"".$id."\" class=\"hcmsWorkplaceBar\">
    <table style=\"width:100%; height:100%; padding:0; border-spacing:0; border-collapse:collapse;\">
      <tr>
        <td class=\"hcmsHeadline\" style=\"width:80px; text-align:left; vertical-align:middle; padding:0; margin:0; white-space:nowrap;\">&nbsp;".$show."&nbsp;</td>
        <td style=\"text-align:left; vertical-align:middle; padding:0; margin:0;\">".$menu_button."</td>".
        $close_button.
      "</tr>
    </table>
  </div>
  <div style=\"width:100%; height:34px;\">&nbsp;</div>\n";
  }
  else return false;
}

// --------------------------------------- showmessage -------------------------------------------
// function: showmessage ()
// input: message, width in pixel (optional), height in pixel (optional), language code (optional), additional style definitions  of div-layer (optional), ID of div-layer (optional)
// output: message box / false on error

// description:
// shows the standard message box with close button

function showmessage ($show, $width=580, $height=70, $lang="en", $style="", $id="hcms_messageLayer")
{
  global $mgmt_config;
  
  require ($mgmt_config['abs_path_cms']."language/buildview.inc.php");
         
  if ($show != "" && strlen ($show) < 2400 && $lang != "")
  {
    // check mobile setting
    if ($_SESSION['hcms_mobile']) $width = "90%";
    
    // given width - icon width - paddings
    if (is_int ($width)) $width_message = $width - 22 - 12; 
    
    return "  <div id=\"".$id."\" class=\"hcmsMessage\" style=\"".$style." width:".$width."px; height:".$height."px; z-index:999; padding:0; margin:5px; visibility:visible;\">
    <div style=\"width:".$width_message."px; height:100%; margin:0; padding:3px; z-index:99999; overflow:auto; float:left;\">
      ".$show."
    </div>
    <div style=\"margin:0; padding:3px; z-index:91; overflow:auto; float:left;\">
      <img name=\"hcms_mediaClose\" src=\"".getthemelocation()."img/button_close.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" alt=\"".$text33[$lang]."\" title=\"".$text33[$lang]."\" onMouseOut=\"hcms_swapImgRestore();\" onMouseOver=\"hcms_swapImage('hcms_mediaClose','','".getthemelocation()."img/button_close_over.gif',1);\" onClick=\"hcms_showHideLayers('".$id."','','hide');\" />
    </div>
  </div>\n";
  }
  else return false;
}

// --------------------------------------- showinfopage -------------------------------------------
// function: showinfopage ()
// input: message, language code (optional)
// output: message on html info page / false on error

// description:
// shows a full html info page

function showinfopage ($show, $lang="en")
{
  global $mgmt_config, $lang_codepage;
  
  require ($mgmt_config['abs_path_cms']."language/buildview.inc.php");
         
  if ($show != "" && strlen ($show) < 2400 && $lang != "")
  {    
    return "<!DOCTYPE html>
  <html>
  <head>
  <title>hyperCMS</title>
  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$lang_codepage[$lang]."\">
  <link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\">
  <script src=\"".$mgmt_config['url_path_cms']."javascript/click.js\" type=\"text/javascript\">
  </script>
  </head>
  
  <body class=\"hcmsWorkplaceGeneric\">
    <div style=\"padding:20px;\">
    <img src=\"".getthemelocation()."img/info.gif\" align=\"absmiddle\" /><span class=\"hcmsHeadline\">Info</span><br \>
    <div style=\"display:block; padding:0px 0px 0px 28px;\">".$show."</div>
    </div>
  </body>
  </html>";
  }
  else return false;
}

// --------------------------------------- showinfobox -------------------------------------------
// function: showinfobox ()
// input: message, language code (optional), display for seconds (optional), additional style definitions  of div-layer (optional), ID of div-layer (optional)
// output: message in div layer / false on error

// description:
// shows infobox for a few seconds

function showinfobox ($show, $lang="en", $sec=4, $style="", $id="hcms_infoLayer")
{
  global $mgmt_config, $lang_codepage;
  
  require ($mgmt_config['abs_path_cms']."language/buildview.inc.php");
         
  if ($mgmt_config['showinfobox'] && $show != "" && strlen ($show) < 2400 && $lang != "")
  {
    return "  <script language=\"JavaScript\">window.onload = function(){ hcms_showInfo('".$id."', ".($sec*1000).") };</script>
  <div id=\"".$id."\" class=\"hcmsInfoBox\" style=\"display:none; ".$style."\">".
    $show."
  </div>";
  }
  else return false;
}

// --------------------------------------- showmetadata -------------------------------------------
// function: showmetadata ()
// input: meta data as array, hierarchy level, CSS-class with background-color for headlines (optional)
// output: result as HTML unordered list / false on error

function showmetadata ($data, $lang="en", $class_headline="hcmsRowData2")
{
  global $mgmt_config, $lang_codepage;
  
  if (is_array ($data))
  {
    // XMP always using UTF-8 so should any other XML-based format
    $charset_source = "UTF-8";
    $charset_dest = $lang_codepage[$lang];

    $result = "<ul class=\"hcmsStructuredList\">\n";
  
    foreach ($data as $key => $value)
    {
      if (is_array ($value))
      {        
        $subresult = showmetadata ($value);

        // html encode string
        $key = html_encode (strip_tags ($key), $charset_source);

        $result .= "  <li><div class=\"".$class_headline."\"><div class=\"hcmsHeadline\">".$key."</div></div></li>
  <li>".$subresult."</li>\n";
      }
      elseif ($value != "")
      {
        // if image
        if (strpos ($key, ":image") > 0 || strpos ("_".$key, "image") == 1)
        {
          $value = "<img src=\"data:image/jpeg;base64,".strip_tags ($value)."\" />";
        }
        
        // html encode string
        $key = html_encode (strip_tags ($key), $charset_source);
        // html encode string
        $value = html_encode (strip_tags ($value), $charset_source);
        
        $result .= "  <li><div style=\"width:200px; float:left;\"><strong>".$key."</strong></div><div style=\"float:left;\">".$value."</div><div style=\"clear:both;\"></div></li>\n";
      }
    }
    
    $result .= "</ul>\n";
    
    if (strlen ($result) > 9) return $result;
    else return false;
  }
  else return false;
}

// --------------------------------------- showobject -----------------------------------------------
// function: showobject ()
// input: publication name, location, object name, category [page,comp] (optional), object name (optional)
// output: html presentation / false

function showobject ($site, $location, $page, $cat="", $name="")
{
  global $mgmt_config, $lang;

  require ($mgmt_config['abs_path_cms']."language/buildview.inc.php");
  
  $location = deconvertpath ($location, "file");

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && is_file ($location.$page))
  {
    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location.$page);
      
    $location_esc = convertpath ($site, $location.$page, $cat);
  
    // get file info
    $file_info = getfileinfo ($site, $location.$page, $cat);
    
    // define object name
    if ($name == "") $name = $object_info['name'];
    
    // if folder
    if ($page == ".folder")
    {
      // get file time
      $filetime = date ("Y-m-d H:i", @filemtime ($location.$page));
      // get file size
      if ($mgmt_config['db_connect_rdbms'] != "")
      { 
        $filesize_array = rdbms_getfilesize ("", $location_esc);
      
        if (is_array ($filesize_array))
        {
          $filesize = $filesize_array['filesize'];
          $filecount = $filesize_array['count'];
        }
      }
    }
    // if page or component
    else
    {
      // get file time
      $filetime = date ("Y-m-d H:i", @filemtime ($location.$page));
      // get file size
      $filesize = round (@filesize ($location.$page) / 1024, 0);
    }
  
    // format file size
    if ($filesize > 1000)
    {
      $filesize = $filesize / 1024;
      $unit = "MB";
    }
    else $unit = "KB";
    
    $filesize = number_format ($filesize, 0, "", ".")." ".$unit;

    $mediaview = "<table>
    <tr><td align=\"left\"><img src=\"".getthemelocation()."img/".$file_info['icon_large']."\" alt=\"".$file_info['name']."\" title=\"".$file_info['name']."\" /></td></tr>
    <tr><td align=\"middle\" class=\"hcmsHeadlineTiny\">".$name."</td></tr>
    <tr><td>".$text104[$lang].": </td><td class=\"hcmsHeadlineTiny\">".$filetime."</td></tr>\n";
    if (!empty ($filesize) && $filesize > 0) $mediaview .= "<tr><td valign=\"top\">".$text80[$lang].": </td><td class=\"hcmsHeadlineTiny\" valign=\"top\">".$filesize."</td></tr>\n";
    if (!empty ($filecount) && $filecount > 1) $mediaview .= "<tr><td valign=\"top\">".$text105[$lang].": </td><td class=\"hcmsHeadlineTiny\" valign=\"top\">".$filecount."</td></tr>\n";
    $mediaview .= "</table>\n";
    
    return $mediaview;
  }
  else return false;
}

// --------------------------------------- showmedia -----------------------------------------------
// function: showmedia ()
// input: mediafile (publication/filename), name of mediafile for display, view type [template, preview, preview_download, preview_no_rendering], ID of the media tag,
//        width in px (optional), height in px (optional), CSS class (optional)
// output: html presentation / false

// description:
// this function requires site, location and cat to be set as global variable in order to validate the access permission of the user

function showmedia ($mediafile, $medianame, $viewtype, $id="", $width="", $height="", $class="hcmsImageItem")
{
  global $mgmt_config, $mgmt_mediapreview, $mgmt_mediaoptions, $mgmt_imagepreview, $mgmt_docconvert, $lang_codepage, $lang,
         $site, $location, $cat, $page, $user, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $setlocalpermission, $mgmt_imageoptions; // used for image rendering (in case the format requires rename of the object file extension)	 
     
  $pdfjs_path = $mgmt_config['url_path_cms']."javascript/pdfpreview/build/generic/web/viewer.html?file=";
  $gdocs_path = "https://docs.google.com/viewer?url=";

  require ($mgmt_config['abs_path_cms']."language/buildview.inc.php");
  require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

  // define flash media type (only executables)
  $swf_ext = ".swf.dcr";

  // define document type extensions that are convertable into pdf or which the document viewer (google doc viewer) can display
  $doc_ext = ".pages.pdf.doc.docx.ppt.pptx.xls.xlsx.odt.ods.odp";
  
  // get publication 
  $site = substr ($mediafile, 0, strpos ($mediafile, "/"));
  
  // get filename and save original media file name (required as reference)
  $mediafile_orig = $mediafile = getobject ($mediafile);
  
  // check access permissions
  if (!is_array ($setlocalpermission) && valid_publicationname ($site) && valid_locationname ($location) && $cat == "comp")
  {
    $ownergroup = accesspermission ($site, $location, "comp");
    $setlocalpermission = setlocalpermission ($site, $ownergroup, "comp");
  }

  if (@substr_count (strtolower ($mediafile), "null_media.gif") > 0 || $mediafile == "")
  {
    return "";
  }
  elseif ($mediafile != "" && valid_publicationname ($site))
  {
    // display name
    $medianame = getobject ($medianame);
    if ($medianame == "") $medianame = getobject ($mediafile);
    
    // get file info
    if (strpos ($mediafile, ".config.") > 0) $file_info = getfileinfo ($site, str_replace ("config.", "", $mediafile), "comp");
    elseif (strpos ("_".substr ($mediafile, strrpos ($mediafile, ".")), ".v_") == 1) $file_info = getfileinfo ($site, substr ($mediafile, 0, strrpos ($mediafile, ".")), "comp");
    else $file_info = getfileinfo ($site, $mediafile, "comp");

    // file extension of original file
    $file_info['orig_ext'] = $file_info['ext'];

    // define media file root directory for template media files
    if ($viewtype == "template" && valid_publicationname ($site))
    {
      $media_root = $mgmt_config['abs_path_tplmedia'].$site."/";
    }
    // define media file root directory for assets
    elseif (valid_publicationname ($site))
    {
      $thumb_root = $media_root = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";;
      
      // get file time
      $mediafiletime = date ("Y-m-d H:i", @filemtime ($thumb_root.$mediafile_orig));

      // get container ID
      $container_id = getmediacontainerid ($mediafile);
      
      // locked by user
      $usedby_array = getcontainername ($container_id.".xml");

      if (!empty ($usedby_array['user'])) $usedby = $usedby_array['user'];
      else $usedby = "";

      // create temp file if file is encrypted
      $temp = createtempfile ($media_root, $mediafile);
      
      if ($temp['result'] && $temp['crypted'])
      {
        $media_root = $temp['templocation'];
        $mediafile = $temp['tempfile'];
      }

      // load container
      if (!empty ($usedby) && $usedby != $user)
      {
        $contentdata = loadcontainer ($container_id.".xml.wrk.@".$usedby, "version", $usedby);
      }
      else $contentdata = loadcontainer ($container_id, "work", $user);
    
      // extract information from container
      $owner = getcontent ($contentdata, "<contentuser>");
      $date_published = getcontent ($contentdata, "<contentpublished>");

      // get media information
      if ($mgmt_config['db_connect_rdbms'] != "")
      {
        // get media file information from database
        $media_info = rdbms_getmedia ($container_id);
        
        $mediafilesize = $media_info['filesize'];
        $width_orig = $media_info['width'];
        $height_orig = $media_info['height'];
      }
      else
      {
        // get file size
        $mediafilesize = round (@filesize ($media_root.$mediafile) / 1024, 0);
      
        // get dimensions of original media file
        $media_size = @getimagesize ($media_root.$mediafile);
        
        if (!empty ($media_size[0]) && !empty ($media_size[1]))
        {
          $width_orig = $media_size[0];
          $height_orig = $media_size[1];
        }
      }

      // if object will be deleted automatically
      if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page))
      {
        $location_esc = convertpath ($site, $location, "comp"); 
        $queue = rdbms_getqueueentries ("delete", "", "", "", $location_esc.$page);
        if (is_array ($queue) && !empty ($queue[0]['date'])) $date_delete = substr ($queue[0]['date'], 0, -3);
      }
    }

    // tag id
    if ($id != "") $id = "id=\"".$id."\"";
    else $id = "";
    
    $mediaview = "";
    $style = "";

    // only show details if user has permissions to edit the file or the configuration of the system is not a DAM
    if ($viewtype == "template" || ($setlocalpermission['root'] == 1 && $setlocalpermission['download'] == 1))
    {    
      // define html code for media file embedding

      // ----------- if Version -----------
      if (strpos ("_".substr ($mediafile, strrpos ($mediafile, ".")), ".v_") == 1)
      {
        $mediaview = "
      <table>
        <tr><td align=\"middle\" class=\"hcmsHeadlineTiny\">
          <img src=\"".getthemelocation()."img/".$file_info['icon']."\" ".$id." /> ".$text78[$lang].": 
          <a href=\"".$mgmt_config['url_path_cms']."explorer_download.php?site=".url_encode($site)."&name=".$medianame."&media=".url_encode($site."/".$mediafile_orig)."&token=".hcms_crypt($site."/".$mediafile_orig)."\">
            ".showshorttext($medianame, 40, false)."
          </a>
        </td></tr>\n";
      }
      // ----------- if Document -----------
      elseif ($file_info['orig_ext'] != "" && substr_count ($doc_ext.".", $file_info['orig_ext'].".") > 0 && $mgmt_config['docviewer'] == true)
      {
        // media size
        if (is_numeric ($width) && $width > 0 && is_numeric ($height) && $height > 0)
        {
          $style .= "width=\"".$width."\" height=\"".$height."\"";
        }
        elseif (is_numeric ($width) && $width > 0)
        {
          // min. width is required for document viewer
          if ($width < 320) $width = 320;
          
          $height = round (($width / 0.75), 0);
          $style .= "width=\"".$width."\" height=\"".$height."\"";
        }
        elseif (is_numeric ($height) && $height > 0)
        {
          $width = round (($height * 0.75), 0);
          
          // min. width is required for document viewer
          if ($width < 320)
          {
            $width = 320;
            $height = round (($width / 0.68), 0);
          }
          
          $style .= "width=\"".$width."\" height=\"".$height."\"";
        }
        else
        {
          $style = "width=\"540\" height=\"740\"";
        }

        // get browser information/version
        $user_client = getbrowserinfo ();

        // check user browser for compatibility with pdf render javascript - pdfjs and if orig. file is a pdf or is convertable to a pdf
        if (
             (
               (isset ($user_client['firefox']) && $user_client['firefox'] >= 6) || 
               (isset ($user_client['msie']) && $user_client['msie'] >= 9) || 
               (isset ($user_client['chrome']) && $user_client['chrome'] >= 24) || 
               (isset ($user_client['unknown']))
             ) && 
             (
               substr_count (".pdf", $file_info['orig_ext']) == 1 || 
               (is_array ($mgmt_docconvert[$file_info['orig_ext']]) && in_array (".pdf", $mgmt_docconvert[$file_info['orig_ext']]))
             )
           )
        {    			
          // check if original file is a pdf
          if (substr_count (".pdf", $file_info['orig_ext']) == 1) 
          {
            // using pdfjs with orig. file via iframe
            $doc_link = cleandomain ($mgmt_config['url_path_cms'])."explorer_wrapper.php?site=".$site."&name=".$medianame."&media=".$site."/".$mediafile_orig."&token=".hcms_crypt ($site."/".$mediafile_orig);
            $mediaview = "<iframe src=\"".$pdfjs_path.urlencode($doc_link)."\" ".$style." ".$id." style=\"border:none;\"></iframe><br />\n";
          }
          else
          {
            $mediafile_thumb = $medianame_thumb = $file_info['filename'].".thumb.pdf";
            
            // if no thumb.pdf exists => create one 
            if (is_file ($thumb_root.$mediafile_thumb)) 
            {
              $thumb_pdf_exsists = true;
            }
            else
            {
              $thumb_pdf_exsists = createdocument ($site, $thumb_root, $thumb_root, $mediafile_orig, "pdf");
            }
            
            // thumb pdf exsists or creation was successful
            if ($thumb_pdf_exsists != false) 
            {
              // using pdfjs with orig. file via iframe
              $doc_link = cleandomain ($mgmt_config['url_path_cms'])."explorer_wrapper.php?site=".$site."&name=".$medianame_thumb."&media=".$site."/".$mediafile_thumb."&token=".hcms_crypt ($site."/".$mediafile_thumb);
              $mediaview = "<iframe src=\"".$pdfjs_path.urlencode($doc_link)."\" ".$style." ".$id." style=\"border:none;\"></iframe><br />\n";
            }
            else
            {
              // was not able to convert into pdf - using Google Docs
              $doc_link = $mgmt_config['url_path_cms']."explorer_wrapper.php?site=".$site."&name=".$medianame."&media=".$site."/".$mediafile_orig."&token=".hcms_crypt ($site."/".$mediafile_orig);
              $mediaview = "<iframe src=\"".$gdocs_path.urlencode($doc_link)."&embedded=true\" ".$style." ".$id." style=\"border:none;\"></iframe><br />\n";
            }
          }
        }
        else
        {
          // Not compatible Browser - using google docs
          $doc_link = $mgmt_config['url_path_cms']."explorer_wrapper.php?site=".url_encode($site)."&name=".url_encode($medianame)."&media=".url_encode($site."/".$mediafile_orig)."&token=".hcms_crypt ($site."/".$mediafile_orig);
          $mediaview = "<iframe src=\"".$gdocs_path.urlencode($doc_link)."&embedded=true\" ".$style." ".$id." style=\"border:none;\"></iframe><br />\n";
        }
     
        $mediaview .= "<div style=\"padding:5px 0px 8px 0px; width:".$width."px; text-align:center;\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</div>";
      }
      // ----------- if Image ----------- 
      elseif ($file_info['ext'] != "" && substr_count ($hcms_ext['image'].".", $file_info['ext'].".") > 0)
      {
        // media size
        $style = "";
      
        if ($viewtype != "template")
        {
          // use thumbnail if it is valid (larger than 400 bytes)
          if (@is_file ($thumb_root.$file_info['filename'].".thumb.jpg") && @filesize ($thumb_root.$file_info['filename'].".thumb.jpg") > 400)
          {
            $viewfolder = $mgmt_config['abs_path_cms'].'temp/';
            $newext = 'png';
            $typename = 'view';
            
            // get thumbnail image information
            $thumbfile = $file_info['filename'].".thumb.jpg";
            $thumb_size = @getimagesize ($thumb_root.$thumbfile);
            $mediaratio = $thumb_size[0] / $thumb_size[1];
            
            // predict the name to check if the file does exist and maybe is actual
            $newname = $file_info['filename'].'.'.$typename.'.'.$width.'x'.$height.'.'.$newext;

            // if thumbnail file is smaller than the defined size of a thumbnail due to a smaller original image
            if (is_array ($thumb_size) && $thumb_size[0] < 180 && $thumb_size[1] < 180)
            {
              $width = $thumb_size[0];
              $height = $thumb_size[1];
              $mediafile = $thumbfile;
            }
            // generate a new image file if the new image size is greater than 150% of the width or height of the thumbnail
            elseif (is_array ($thumb_size) && ($width > 0 && $thumb_size[0] * 1.5 < $width) && ($height > 0 && $thumb_size[1] * 1.5 < $height) && is_array ($mgmt_imageoptions))
            {
              // generate new file only when another one wasn't already created or is outdated (use thumbnail since the date of the decrypted temporary file is not representative)
              if (!is_file ($viewfolder.$newname) || @filemtime ($thumb_root.$thumbfile) > @filemtime ($viewfolder.$newname)) 
              {
                // searching for the configuration we need
                foreach ($mgmt_imageoptions as $formatstring => $settingArray)
                {
                  if (substr_count ($formatstring.".", '.'.$newext.'.') > 0)
                  {
                    $formats = $formatstring;
                  }
                }
              
                if ($formats != "")
                {
                  $mgmt_imageoptions[$formats][$typename.'.'.$width.'x'.$height] = '-s '.$width.'x'.$height.' -f '.$newext;
                  
                  // create new temporary thumbnail
                  $result = createmedia ($site, $thumb_root, $viewfolder, $mediafile_orig, $newext, $typename.'.'.$width.'x'.$height, true);

                  if ($result) $mediafile = $result;
                }
              }
              // we use the existing file
              else $mediafile = $newname;
            }
            elseif ($width > 0 && $height > 0)
            {
              $mediafile = $thumbfile;
            }
            elseif ($width > 0)
            {
              $height = round (($width / $mediaratio), 0);
              $mediafile = $thumbfile;
            }
            elseif ($height > 0)
            {
              $width = round (($height / $mediaratio), 0);
              $mediafile = $thumbfile;
            }
            else $mediafile = $thumbfile;
            
            if ($width > 0 && $height > 0) $style = "style=\"width:".intval($width)."px; height:".intval($height)."px;\"";
            
            // set width and height of media file as file-parameter
            $mediaview = "
            <!-- hyperCMS:width file=\"".$width."\" -->
            <!-- hyperCMS:height file=\"".$height."\" -->";
            
            // get file extension of new file (preview file)
            $file_info['ext'] = strtolower (strrchr ($mediafile, "."));

            $mediaview .= "
          <table>
            <tr><td align=left><img src=\"".$mgmt_config['url_path_cms']."explorer_wrapper.php?site=".url_encode($site)."&media=".url_encode($site."/".$mediafile)."&token=".hcms_crypt($site."/".$mediafile)."\" ".$id." alt=\"".$medianame."\" title=\"".$medianame."\" class=\"".$class."\" ".$style."/></td></tr>
            <tr><td align=\"middle\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td></tr>";           
          }
          // if no thumbnail/preview exists
          else
          {
            $mediaview = "
          <table>
            <tr><td align=\"left\"><img src=\"".getthemelocation()."img/".$file_info['icon_large']."\" ".$id." alt=\"".$medianame."\" title=\"".$medianame."\" /></td></tr>
            <tr><td align=\"middle\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td></tr>";
          }
        }      
        // if template media view
        elseif (file_exists ($media_root.$mediafile))
        {
          $mediaview = "
        <table>
          <tr><td align=\"left\"><img src=\"".$mgmt_config['url_path_tplmedia'].$mediafile."\" ".$id." alt=\"".$mediafile."\" title=\"".$mediafile."\" class=\"".$class."\" ".$style."/></td></tr>
          <tr><td align=\"middle\" class=\"hcmsHeadlineTiny\">".showshorttext($mediafile, 40, false)."</td></tr>";
        }      
  
        // image rendering (only if image conversion software and permissions are given)
        if ($viewtype == "preview" && is_supported ($mgmt_imagepreview, $file_info['orig_ext']) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1) 
        {
          // add image rendering button
          $mediaview .= "<tr><td align=middle><input name=\"media_rendering\" class=\"hcmsButtonGreen\" type=\"button\" value=\"".$text83[$lang]."\" onclick=\"if (typeof setSaveType == 'function') setSaveType('imagerendering_so', '');\" /></td></tr>\n";
        }
        
        $mediaview .= "</table>\n";
      }
      // ----------- if Flash ----------- 
      elseif ($file_info['ext'] != "" && substr_count ($swf_ext.".", $file_info['ext'].".") > 0)
      {
        // media size
        $style = "";
        
        // use provided dimensions
        if (is_numeric ($width) && $width > 0) $style .= "width=\"".$width."\"";
        if (is_numeric ($height) && $height > 0) $style .= " height=\"".$height."\"";
        
        // use original dimensions
        if ($style == "")
        {
          if (!empty ($width_orig) && !empty ($height_orig))
          {
            $style .= "width=\"".$width_orig."\" height=\"".$height_orig."\"";
          }
          // try to get the dimensions from media file
          else
          {
            $media_size = @getimagesize ($media_root.$mediafile);
            if (!empty ($media_size[3])) $style .= $media_size[3];
          }
        }
        
        $mediaview = "
      <table>
        <tr><td align=\"left\">
          <object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0\" ".$style.">
            <param name=\"movie\" value=\"".$mgmt_config['url_path_cms']."explorer_wrapper.php?site=".url_encode($site)."&media=".url_encode($site."/".$mediafile_orig)."&token=".hcms_crypt($site."/".$mediafile_orig)."\" />
            <param name=\"quality\" value=\"high\" />
            <embed src=\"".$mgmt_config['url_path_cms']."explorer_wrapper.php?site=".url_encode($site)."&media=".url_encode($site."/".$mediafile_orig)."&token=".hcms_crypt($site."/".$mediafile_orig)."\" ".$id." quality=\"high\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\" type=\"application/x-shockwave-flash\" ".$style." />
          </object>
        </td></tr>
        <tr><td align=\"middle\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td></tr>
      </table>";
      }
      // ----------- if Audio ----------- 
      elseif ($file_info['ext'] != "" && substr_count ($hcms_ext['audio'].".", $file_info['ext'].".") > 0)
      {
        // media player config file is given
        if (strpos ($mediafile_orig, ".config.") > 0 && is_file ($thumb_root.$mediafile_orig))
        {
          $config = readmediaplayer_config ($thumb_root, $mediafile_orig);
        }
        // new since version 5.6.3 (config of original-preview file)
        elseif (file_exists ($thumb_root.$file_info['filename'].".config.orig"))
        {
          $config = readmediaplayer_config ($thumb_root, $file_info['filename'].".config.orig");
        }
        // new since version 5.6.3 (config of audioplayer)
        elseif (file_exists ($thumb_root.$file_info['filename'].".config.audio"))
        {
          $config = readmediaplayer_config ($thumb_root, $file_info['filename'].".config.audio");
        }
        // no media config file is available, try to create video thumbnail file
        elseif (is_file ($thumb_root.$mediafile_orig))
        {
          // create thumbnail video of original file
          $create_media = createmedia ($site, $thumb_root, $thumb_root, $mediafile_orig, "", "origthumb");
          
          if ($create_media) $config = readmediaplayer_config ($thumb_root, $file_info['filename'].".config.orig");
        }

        // use default values
        $mediawidth = 320;
        $mediaheight = 240;
        
        // set width and height of media file as file-parameter
        $mediaview .= "
        <!-- hyperCMS:width file=\"".$mediawidth."\" -->
        <!-- hyperCMS:height file=\"".$mediaheight."\" -->";
        
        // generate player code
        if (empty ($config['mediafiles'])) $config['mediafiles'] = array ($site."/".$mediafile);
        
        $playercode = showaudioplayer ($site, $config['mediafiles'], "preview", "cut_audio");
      
        $mediaview .= "
      <table>
        <tr><td align=left>".$playercode."</td></tr>
        <tr><td align=\"middle\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td></tr>";
        
        // video rendering and embedding (requires the JS function 'setSaveType' provided by the template engine)
        if (is_supported ($mgmt_mediapreview, $file_info['orig_ext']) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
        {
          if ($viewtype == "preview")
          {
            $mediaview .= "
        <tr><td style=\"text-align:center;\">
          <button type=\"button\" name=\"media_rendering\" class=\"hcmsButtonGreen\" onclick=\"if (typeof setSaveType == 'function') setSaveType('mediarendering_so', '');\" />".$text106[$lang]."</button>&nbsp;
          <button type=\"button\" name=\"media_embedding\" class=\"hcmsButtonBlue\" onclick=\"if (typeof setSaveType == 'function') setSaveType('mediaplayerconfig_so', '');\">".$text107[$lang]."</button>&nbsp;
        </td></tr>";
          }
          elseif ($viewtype == "preview_download")
          {
            $mediaview .= "
        <tr><td style=\"text-align:center;\">
          <button type=\"button\" name=\"media_embedding\" class=\"hcmsButtonBlue\" onclick=\"document.location.href='media_playerconfig.php?location=".url_encode($location_esc)."&page=".url_encode($page)."';\">".$text107[$lang]."</button>&nbsp;
        </td></tr>";
          }
        }    
        
        $mediaview .= "</table>\n";
      }
      // ----------- if Video ----------- 
      elseif ($file_info['ext'] != "" && substr_count ($hcms_ext['video'].".", $file_info['ext'].".") > 0)
      {
        // media player config file is given
        if (strpos ($mediafile_orig, ".config.") > 0 && is_file ($thumb_root.$mediafile_orig))
        {
          $config = readmediaplayer_config ($thumb_root, $mediafile_orig);
        }
        // get media player config file
        // new since version 5.6.3 (config/preview of original file)
        elseif (is_file ($thumb_root.$file_info['filename'].".config.orig"))
        {
          $config = readmediaplayer_config ($thumb_root, $file_info['filename'].".config.orig");
        }
        // new since version 5.5.7 (config of videoplayer)
        elseif (is_file ($thumb_root.$file_info['filename'].".config.video"))
        {
          $config = readmediaplayer_config ($thumb_root, $file_info['filename'].".config.video");
        }
        // old version (only FLV support)
        elseif (is_file ($thumb_root.$file_info['filename'].".config.flv"))
        {
          $config = readmediaplayer_config ($thumb_root, $file_info['filename'].".config.flv");
        }
        // no media config file is available, try to create video thumbnail file
        elseif (is_file ($thumb_root.$mediafile_orig))
        {
          // create thumbnail video of original file
          $create_media = createmedia ($site, $thumb_root, $thumb_root, $mediafile_orig, "", "origthumb");
          
          if ($create_media) $config = readmediaplayer_config ($thumb_root, $file_info['filename'].".config.orig");
        }

        // use config values
        if (is_array ($config) && $config['width'] > 0 && $config['height'] > 0)
        {
          $mediawidth = $config['width'];
          $mediaheight = $config['height'];
          $mediaratio = $config['width'] / $config['height'];
        }
        // use default values
        else
        {
          $mediawidth = 320;
          $mediaheight = 240;
          $mediaratio = $mediawidth / $mediaheight;
        }
        
        // set width and height of media file as file-parameter
        $mediaview .= "
        <!-- hyperCMS:width file=\"".$mediawidth."\" -->
        <!-- hyperCMS:height file=\"".$mediaheight."\" -->";
        
        // if media size input is given (overwrite values)
        // width and height is provided
        if ((is_numeric ($width) && $width > 0) && (is_numeric ($height) && $height > 0))
        {
          $mediawidth = $width;
          $mediaheight = $height;
        }
        // only width is provided
        elseif (is_numeric ($width) && $width > 0 && $mediaratio > 0)
        {
          $mediawidth = $width;
          $mediaheight = round (($mediawidth / $mediaratio), 0);
        }
        // only height is provided
        elseif (is_numeric ($height) && $height > 0 && $mediaratio > 0)
        {
          $mediaheight = $height;
          $mediawidth = round (($mediaheight * $mediaratio), 0);
        }

        // generate player code
        $playercode = showvideoplayer ($site, $config['mediafiles'], $mediawidth, $mediaheight, "preview", Null, "cut_video", "", false);
      
        $mediaview .= "
        <table>
        <tr><td align=left>".$playercode."</td></tr>
        <tr><td align=\"middle\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td></tr>\n";
        
        // video rendering and embedding
        if (is_supported ($mgmt_mediapreview, $file_info['orig_ext']) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
        {
          if ($viewtype == "preview")
          {
            $mediaview .= "
        <tr><td style=\"text-align:center;\">
          <button type=\"button\" name=\"media_rendering\" class=\"hcmsButtonGreen\" onclick=\"if (typeof setSaveType == 'function') setSaveType('mediarendering_so', '');\" />".$text82[$lang]."</button>&nbsp;
          <button type=\"button\" name=\"media_embedding\" class=\"hcmsButtonBlue\" onclick=\"if (typeof setSaveType == 'function') setSaveType('mediaplayerconfig_so', '');\">".$text92[$lang]."</button>&nbsp;
        </td></tr>";
          }
          elseif ($viewtype == "preview_download" && valid_locationname ($location) && valid_objectname ($page))
          {
            $mediaview .= "
        <tr><td style=\"text-align:center;\">
          <button type=\"button\" name=\"media_embedding\" class=\"hcmsButtonBlue\" onclick=\"document.location.href='media_playerconfig.php?location=".url_encode($location_esc)."&page=".url_encode($page)."';\">".$text92[$lang]."</button>&nbsp;
        </td></tr>";
          }
        }     
        
        $mediaview .= "</table>\n";
      }
      // ----------- show clear text based doc ----------- 
      elseif ($file_info['ext'] != "" && substr_count ($hcms_ext['cleartxt'].$hcms_ext['cms'].".", $file_info['ext'].".") > 0)
      {
        if (file_exists ($media_root.$mediafile))
        {
          $content = loadfile ($media_root, $mediafile);
          
          if (!is_utf8 ($content))
          {
            if (is_latin1 ($content)) $content = iconv ("ISO-8859-1", "UTF-8//TRANSLIT", $content);
            else $content = iconv ("GBK", "UTF-8//TRANSLIT", $content);
          }
          
          // escape special characters
          if ($lang_codepage[$lang] == "") $lang_codepage[$lang] = "UTF-8";
          $content = html_encode ($content, $lang_codepage[$lang]);
          
          // media size
          $style = "";
          
          if (is_numeric ($width) && $width > 0 && is_numeric ($height) && $height > 0)
          {
            $style = "style=\"width:".$width."px; height:".$height."px;\"";
          }
          elseif (is_numeric ($width) && $width > 0)
          {
            $height = round (($width / 0.73), 0);
            $style = "style=\"width:".$width."px; height:".$height."px;\"";
          }
          elseif (is_numeric ($height) && $height > 0)
          {
            $width = round (($height * 0.73), 0);
            $style = "style=\"width:".$width."px; height:".$height."px;\"";
          }
          else $style = "style=\"width:580px; height:620px;\"";   
          
          if ($viewtype == "template") $mediaview = "<form name=\"editor\" method=\"post\">
          <input type=\"hidden\" name=\"site\" value=\"".$site."\" />
          <input type=\"hidden\" name=\"mediacat\" value=\"tpl\" />
          <input type=\"hidden\" name=\"mediafile\" value=\"".$mediafile."\" />
          <input type=\"hidden\" name=\"save\" value=\"yes\" />
          <input type=\"hidden\" name=\"token\" value=\"".createtoken ($user)."\" />\n";
          
          if ($viewtype == "template") $mediaview .= "<table cellspacing=\"0\" cellpadding=\"0\" style=\"border:1px solid #000000; margin:2px;\">\n";
          else $mediaview .= "<table>\n";
          
          // save button
          if ($viewtype == "template") $mediaview .= "<td align=\"left\"><img onclick=\"document.forms['editor'].submit();\" name=\"save\" src=\"".getthemelocation()."img/button_save.gif\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" alt=\"".$text31[$lang]."\" title=\"".$text31[$lang]."\" /></td>\n";
          
          // disable text area
          if ($viewtype == "template") $disabled = "";
          else $disabled = "disabled=\"disabled\"";
          
          $mediaview .= "<tr><td align=\"left\"><textarea name=\"content\" ".$id." ".$style." ".$disabled.">".$content."</textarea></td></tr>
          <tr><td align=\"middle\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td></tr>
          </table>\n";
          
          if ($viewtype == "template") $mediaview .= "</form>\n";  
        }
      }
    }

    // ----------- show standard file icon ----------- 
    if ($mediaview == "")
    {
      // use thumbnail if it is valid (larger than 400 bytes)
      if (file_exists ($thumb_root.$file_info['filename'].".thumb.jpg") && @filesize ($thumb_root.$file_info['filename'].".thumb.jpg") > 400)
      {
        // thumbnail file
        $mediafile = $file_info['filename'].".thumb.jpg";
        // get file extension of new file (preview file)
        $file_info['ext'] = strtolower (strrchr ($mediafile, "."));
        
        $mediaview = "<table>
        <tr><td align=left><img src=\"".$mgmt_config['url_path_cms']."explorer_wrapper.php?site=".url_encode($site)."&media=".url_encode($site."/".$mediafile)."&token=".hcms_crypt($site."/".$mediafile)."\" ".$id." alt=\"".$medianame."\" title=\"".$medianame."\" class=\"".$class."\" /></td></tr>
        <tr><td align=\"middle\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td></tr>\n";           
      }
      // if no thumbnail/preview exists
      else
      {
        $mediaview = "<table>
        <tr><td align=\"left\"><img src=\"".getthemelocation()."img/".$file_info['icon_large']."\" ".$id." alt=\"".$medianame."\" title=\"".$medianame."\" /></td></tr>
        <tr><td align=\"middle\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td></tr>\n";
      }
    }

    // properties of video files (original and thumbnail files)
    if (is_array ($mgmt_mediapreview) && $file_info['ext'] != "" && substr_count ($hcms_ext['video'].$hcms_ext['audio'], $file_info['ext']) > 0)
    {
      $dimensions = array();
      $durations = array();
      $bitrates = array();
      $audio_bitrates = array();
      $audio_frequenzies = array();
      $audio_channels = array();
      $filesizes = array();
      $downloads = array();
      $youtube_uploads = array();
      
      // is file a video
      if (substr_count ($hcms_ext['video'], $file_info['ext']) > 0) $is_video = true;
      else $is_video = false;
    
      // original video file
      $mediafile_config = substr ($mediafile_orig, 0, strrpos ($mediafile_orig, ".")).".config.orig";
      $videoinfo = readmediaplayer_config ($thumb_root, $mediafile_config);
      
      if (empty ($videoinfo['version']) || floatval ($videoinfo['version']) < 2.2) $videoinfo = getvideoinfo ($media_root.$mediafile);

      // show the values
      if (is_array ($videoinfo)) //media file
      {       
        $filesizes['original'] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['filesize'].' &nbsp;&nbsp;&nbsp;</td>';

        $dimensions['original'] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['dimension'].' &nbsp;&nbsp;&nbsp;</td>';

        $durations['original'] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['duration'].'&nbsp;&nbsp;&nbsp;</td>';

        $bitrates['original'] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['videobitrate'].'&nbsp;&nbsp;&nbsp;</td>';

        $audio_bitrates['original'] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['audiobitrate'].'&nbsp;&nbsp;&nbsp;</td>';

        $audio_frequenzies['original'] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['audiofrequenzy'].'&nbsp;&nbsp;&nbsp;</td>';

        $audio_channels['original'] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['audiochannels'].'&nbsp;&nbsp;&nbsp;</td>';
        
        $download_link = "top.location.href='".$mgmt_config['url_path_cms']."explorer_download.php?media=".url_encode($site."/".$mediafile_orig)."&name=".url_encode($medianame)."&token=".hcms_crypt($site."/".$mediafile_orig)."'; return false;";
       
        // download button
        if ($viewtype == "preview" || $viewtype == "preview_download") 
        {
          $downloads['original'] = '<td class="hcmsHeadlineTiny" style="text-align:left;"><button class="hcmsButtonBlue" onclick="'.$download_link.'">'.$text78[$lang].'</button></td>';
          
          if ($mgmt_config[$site]['youtube'] == true && is_file ($mgmt_config['abs_path_cms']."connector/youtube/index.php"))
          {		
            $youtube_uploads['original'] = '<td class="hcmsHeadlineTiny" style="text-align:left;"> 
            <button type="button" name="media_youtube" class="hcmsButtonGreen" onclick=\'hcms_openWindow("'.$mgmt_config['url_path_cms'].'connector/youtube/index.php?site='.url_encode($site).'&page='.url_encode($page).'&path='.url_encode($site."/".$mediafile_orig).'&location='.url_encode(getrequest_esc('location')).'","","scrollbars=no,resizable=yes","420","300")\'>'.$text108[$lang].'</button> </td>';
          }
        }
      }
      
      $videos = array ('<th style="text-align:left;">'.$text89[$lang].'</th>');
      
      if ($viewtype == "preview" || $viewtype == "preview_download")
      {
        // video thumbnail files
        if ($is_video) $media_extension_array = explode (".", substr ($hcms_ext['video'], 1));
        // audio thumbnail files
        else $media_extension_array = explode (".", substr ($hcms_ext['audio'], 1));
        
        foreach ($media_extension_array as $media_extension)
        {
          // try individual video
          $video_thumbfile = $file_info['filename'].".media.".$media_extension;
          
          // or use thumbnail video for videoplayer (only if file is not encrypted!)
          if (!is_file ($thumb_root.$video_thumbfile) && !is_encryptedfile ($thumb_root, $file_info['filename'].".thumb.".$media_extension))
          {
            $video_thumbfile = $file_info['filename'].".thumb.".$media_extension;
          }
          
          $video_filename = substr ($medianame, 0, strrpos ($medianame, ".")).".".$media_extension;
  
          if (is_file ($thumb_root.$video_thumbfile))
          {
            $videos[$media_extension] = '<th style="text-align:left;">'.$media_extension.'</th>';
            
            // get video information from thumbnail file
            $mediafile_config = substr ($mediafile_orig, 0, strrpos ($mediafile_orig, ".")).".config.".$media_extension;
            $videoinfo = readmediaplayer_config ($thumb_root, $mediafile_config);
  
            if (empty ($videoinfo['version']) || floatval ($videoinfo['version']) < 2.2) $videoinfo = getvideoinfo ($thumb_root.$video_thumbfile);
  
            // show the values
            if (is_array ($videoinfo))
            {          
              $filesizes[$media_extension] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['filesize'].' &nbsp;&nbsp;&nbsp;</td>';
  
              $dimensions[$media_extension] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['dimension'].' &nbsp;&nbsp;&nbsp;</td>';
  
              $durations[$media_extension] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['duration'].'&nbsp;&nbsp;&nbsp;</td>';
  
              $bitrates[$media_extension] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['videobitrate'].'&nbsp;&nbsp;&nbsp;</td>';
  
              $audio_bitrates[$media_extension] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['audiobitrate'].'&nbsp;&nbsp;&nbsp;</td>';
  
              $audio_frequenzies[$media_extension] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['audiofrequenzy'].'&nbsp;&nbsp;&nbsp;</td>';
  
              $audio_channels[$media_extension] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['audiochannels'].'&nbsp;&nbsp;&nbsp;</td>';
  
              $download_link = "top.location.href='".$mgmt_config['url_path_cms']."explorer_download.php?media=".url_encode($site."/".$video_thumbfile)."&name=".url_encode($video_filename)."&token=".hcms_crypt($site."/".$video_thumbfile)."'; return false;";
             
              // download button
              if ($viewtype == "preview" || $viewtype == "preview_download")
              {
                $downloads[$media_extension] = '<td class="hcmsHeadlineTiny" style="text-align:left;"><button class="hcmsButtonBlue" onclick="'.$download_link.'">'.$text78[$lang].'</button></td>'; 
                
                if ($mgmt_config[$site]['youtube'] == true && is_file ($mgmt_config['abs_path_cms']."connector/youtube/index.php"))
                {	
                  $youtube_uploads[$media_extension] = '<td class="hcmsHeadlineTiny" style="text-align:left;"> 
                <button type="button" name="media_youtube" class="hcmsButtonGreen" onclick=\'hcms_openWindow("'.$mgmt_config['url_path_cms'].'connector/youtube/index.php?site='.url_encode($site).'&page='.url_encode($page).'&path='.url_encode($site."/".$video_thumbfile).'&location='.url_encode(getrequest_esc('location')).'","","scrollbars=no,resizable=yes","420","300")\'>'.$text108[$lang].'</button> </td>';
                }
              }
            }
          }
        }
      }

      // generate output	    
      if (is_array ($videos)) $mediaview .= '<table style="cellspacing:2px;"><tr><th>&nbsp;</th>'.implode("", $videos).'</tr>';		
      // Filesize
      if (is_array ($filesizes)) $mediaview .= '<tr><td style="text-align:left; white-space:nowrap;">'.$text80[$lang].'&nbsp;</td>'.implode ("", $filesizes).'</tr>';	    
      // Dimension
      if ($is_video && is_array ($dimensions)) $mediaview .= '<tr><td style="text-align:left; white-space:nowrap;">'.$text88[$lang].'&nbsp;</td>'.implode ("", $dimensions).'</tr>';			    
      // Durations
      if (is_array ($durations)) $mediaview .= '<tr><td style="text-align:left; white-space:nowrap;">'.$text86[$lang].'&nbsp;</td>'.implode ("", $durations).'</tr>';		
      // Bitrate
      if ($is_video && is_array ($bitrates)) $mediaview .= '<tr><td style="text-align:left; white-space:nowrap;">'.$text87[$lang].'&nbsp;</td>'.implode ("", $bitrates).'</tr>';
      // Audio bitrate
      if (is_array ($audio_bitrates)) $mediaview .= '<tr><td style="text-align:left; white-space:nowrap;">'.$text117[$lang].'&nbsp;</td>'.implode ("", $audio_bitrates).'</tr>';
      // Audio frequenzy
      if (is_array ($audio_frequenzies)) $mediaview .= '<tr><td style="text-align:left; white-space:nowrap;">'.$text118[$lang].'&nbsp;</td>'.implode ("", $audio_frequenzies).'</tr>';
      // Audio frequenzy
      if (is_array ($audio_channels)) $mediaview .= '<tr><td style="text-align:left; white-space:nowrap;">'.$text119[$lang].'&nbsp;</td>'.implode ("", $audio_channels).'</tr>';
      // Download
      if (is_array ($downloads) && sizeof ($downloads) > 0) $mediaview .= '<tr><td>&nbsp;</td>'.implode ("", $downloads).'</tr>';
      // Youtube
      if ($mgmt_config[$site]['youtube'] == true && is_file ($mgmt_config['abs_path_cms']."connector/youtube/index.php"))
      {
        if (is_array ($youtube_uploads) && sizeof ($youtube_uploads) > 0) $mediaview .= '<tr><td>&nbsp;</td>'.implode ("", $youtube_uploads).'</tr>';
      }
      if (is_array ($videos)) $mediaview .= '</table>';
    }
    // width and height of other media files
    else
    {
      // format file size
      if ($mediafilesize > 1000)
      {
        $mediafilesize = $mediafilesize / 1024;
        $unit = "MB";
      }
      else $unit = "KB";
      
      $mediafilesize = number_format ($mediafilesize, 0, "", ".")." ".$unit;
      
      // output information
      $mediaview .= "    <table>";
      if (!empty ($owner[0])) $mediaview .= "
      <tr>
      <td style=\"text-align:left; white-space:nowrap;\">".$text116[$lang].": </td>
      <td class=\"hcmsHeadlineTiny\" style=\"text-align:left; white-space:nowrap;\">".$owner[0]."</td>
      </tr>";
      $mediaview .= "
      <tr>
      <td style=\"text-align:left; white-space:nowrap;\">".$text104[$lang].": </td>
      <td class=\"hcmsHeadlineTiny\" style=\"text-align:left; white-space:nowrap;\">".$mediafiletime."</td>
      </tr>";
      if (!empty ($date_published[0])) $mediaview .= "
      <tr>
      <td style=\"text-align:left; white-space:nowrap;\">".$text111[$lang].": </td>
      <td class=\"hcmsHeadlineTiny\" style=\"text-align:left; white-space:nowrap;\">".$date_published[0]."</td>
      </tr>";
      if (!empty ($date_delete)) $mediaview .= "
      <tr>
      <td style=\"text-align:left; white-space:nowrap;\">".$text115[$lang].": </td>
      <td class=\"hcmsHeadlineTiny\" style=\"text-align:left; white-space:nowrap;\">".$date_delete."</td>
      </tr>";
      $mediaview .= "
      <tr>
      <td style=\"text-align:left; white-space:nowrap;\">".$text80[$lang].": </td>
      <td class=\"hcmsHeadlineTiny\" style=\"text-align:left; white-space:nowrap;\">".$mediafilesize."</td>
      </tr>\n";

      if (!empty ($width_orig) && !empty ($height_orig))
      {
        // size in pixel of media file
        $mediaview .= "
      <tr>
      <td style=\"text-align:left; white-space:nowrap;\">".$text79[$lang].": </td>
      <td class=\"hcmsHeadlineTiny\" style=\"text-align:left; white-space:nowrap;\">".$width_orig."x".$height_orig." px</td>
      </tr>\n";
      
        // size in cm
        $mediaview .= "
      <tr>
      <td style=\"text-align:left; white-space:nowrap;\">".$text79[$lang]." (72 dpi): </td>
      <td class=\"hcmsHeadlineTiny\">".round(($width_orig / 72 * 2.54), 1)."x".round(($height_orig / 72 * 2.54), 1)." cm, ".round(($width_orig / 72), 1)."x".round(($height_orig / 72), 1)." inch</td>
      </tr>\n";
      
        // size in inch
        $mediaview .= "
      <tr>
      <td style=\"text-align:left; white-space:nowrap;\">".$text79[$lang]." (300 dpi): </td>
      <td class=\"hcmsHeadlineTiny\">".round(($width_orig / 300 * 2.54), 1)."x".round(($height_orig / 300 * 2.54), 1)." cm, ".round(($width_orig / 300), 1)."x".round(($height_orig / 300), 1)." inch</td>
      </tr>\n";
      }
      
      $mediaview .= "    </table>";
    }
    
    return $mediaview;
  }
  else return false;
}

// --------------------------------------- showcompexplorer -------------------------------------------
// function: showcompexplorer ()
// input: publication name, current explorer location, object location (optional), object name (optional), 
//        component category [single,multi,media] (optional), search expression (optional), media-type [audio,video,text,flash,image,compressed,binary] (optional), 
//        callback of CKEditor (optional), saclingfactor for images (optional)
// output: explorer with search / false on error

// description:
// creates component explorer incl. search.

function showcompexplorer ($site, $dir, $location_esc="", $page="", $compcat="multi", $search_expression="", $mediatype="", $lang="en", $callback="", $scalingfactor="1")
{
  global $mgmt_config, $user, $siteaccess, $pageaccess, $compaccess, $rootpermission, $globalpermission, $localpermission, $hiddenfolder, $html5file, $temp_complocation;
  
  if (valid_publicationname ($site) && (valid_locationname ($dir) || $dir == ""))
  {
    // load file extension defintions
    require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");
    // language file
    require ($mgmt_config['abs_path_cms']."language/component_edit_explorer.inc.php");

    // convert location
    $dir = deconvertpath ($dir, "file");
    $location = deconvertpath ($location_esc, "file");
    
    // load publication inheritance setting
    $inherit_db = inherit_db_read ();
    $parent_array = inherit_db_getparent ($inherit_db, $site);
    
    // get last location in component structure
    if (!valid_locationname ($dir) && isset ($temp_complocation[$site])) 
    {
      $dir = $temp_complocation[$site];
      
      if (!is_dir ($dir))
      {
        $dir = "";
        $temp_complocation[$site] = null;
        $_SESSION['hcms_temp_complocation'] = $temp_complocation;
      }  
    }
    elseif (valid_locationname ($dir))
    {
      $temp_complocation[$site] = $dir;
      $_SESSION['hcms_temp_complocation'] = $temp_complocation;
    }
    
    // define root location if no location data is available
    if ($dir == "" && ($mgmt_config[$site]['inherit_comp'] == false || $parent_array == false))
    {
      $dir = $mgmt_config['abs_path_comp'].$site."/";
    }
    elseif ($dir == "" && $mgmt_config[$site]['inherit_comp'] == true)
    {
      $dir = $mgmt_config['abs_path_comp'];
    }
    
    // convert path
    $dir_esc = convertpath ($site, $dir, "comp");
    
    // media format
    if ($mediatype != "")
    {
      if ($mediatype == "audio") $format_ext = strtolower ($hcms_ext['audio']);
      elseif ($mediatype == "video") $format_ext = strtolower ($hcms_ext['video']);
      elseif ($mediatype == "text") $format_ext = strtolower ($hcms_ext['cms'].$hcms_ext['bintxt'].$hcms_ext['cleartxt']);
      elseif ($mediatype == "flash") $format_ext = strtolower ($hcms_ext['flash']);
      elseif ($mediatype == "image") $format_ext = strtolower ($hcms_ext['image']);
      elseif ($mediatype == "compressed") $format_ext = strtolower ($hcms_ext['compressed']);
      elseif ($mediatype == "binary") $format_ext = strtolower ($hcms_ext['binary']);
      else $format_ext = "";
    }  
    
    // javascript code
    $result = "<script language=\"JavaScript\">
<!--
function sendCompOption(newtext, newvalue)
{
  parent.mainFrame2.insertOption(newtext, newvalue);
}

function sendCompInput(newtext, newvalue)
{
  parent.frames['mainFrame2'].document.forms['component'].elements['component'].value = newvalue;
  parent.frames['mainFrame2'].document.forms['component'].elements['comp_name'].value = newtext;
}

function sendMediaInput(newtext, newvalue)
{
  parent.frames['controlFrame2'].document.forms['media'].elements['mediafile'].value = newtext;
  parent.frames['controlFrame2'].document.forms['media'].elements['mediaobject'].value = newvalue;
}
//-->
</script>";
    
    // current location
    $location_name = getlocationname ($site, $dir, "comp", "path");
    
    $result .= "<span class=\"hcmsHeadline\" style=\"padding:3px 0px 3px 0px; display:block;\">".$text0[$lang]."</span>
    <span class=\"hcmsHeadlineTiny\" style=\"padding:3px 0px 3px 0px; display:block;\">".$location_name."</span>\n";
    
    // file upload
    $ownergroup = accesspermission ($site, $dir, "comp");
    $setlocalpermission = setlocalpermission ($site, $ownergroup, "comp");
    
    if ($compcat == "media" && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1 && $search_expression == "")
    {
      // Upload Button
      if ($html5file) $popup_upload = "popup_upload_html.php";
      else $popup_upload = "popup_upload_swf.php";
      
      $result .= "<div style=\"align:center; padding:2px; width:100%;\">
        <input name=\"UploadButton\" class=\"hcmsButtonGreen\" style=\"width:198px; float:left;\" type=\"button\" onClick=\"hcms_openWindow('".$mgmt_config['url_path_cms'].$popup_upload."?uploadmode=multi&site=".url_encode($site)."&cat=comp&location=".url_encode($dir_esc)."','','status=yes,scrollbars=no,resizable=yes,width=600,height=400','600','400');\" value=\"".$text5[$lang]."\" />
        <img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"document.location.reload();\" src=\"".getthemelocation()."img/button_view_refresh.gif\" alt=\"".$text7[$lang]."\" title=\"".$text7[$lang]."\" />
      </div>
      <div style=\"clear:both;\"></div>\n";
    }
    elseif (($compcat == "single" || $compcat == "multi") && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1 && $search_expression == "")
    {
      $result .= "<div style=\"align:center; padding:2px; width:100%;\">
        <input name=\"UploadButton\" class=\"hcmsButtonGreen\" style=\"width:198px; float:left;\" type=\"button\" onClick=\"hcms_openWindow('".$mgmt_config['url_path_cms']."frameset_content.php?site=".url_encode($site)."&cat=comp&location=".url_encode($dir_esc)."','','status=yes,scrollbars=no,resizable=yes,width=800,height=600','800','600');\" value=\"".$text6[$lang]."\" />
        <img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"document.location.reload();\" src=\"".getthemelocation()."img/button_view_refresh.gif\" alt=\"".$text7[$lang]."\" title=\"".$text7[$lang]."\" />
      </div>
      <div style=\"clear:both;\"></div>\n";
    }
    
    // search form
    if ($mgmt_config['db_connect_rdbms'] != "") $result .= "
    <div style=\"padding:2px; width:100%;\">
      <form name=\"searchform_general\" method=\"post\" action=\"\">
        <input type=\"hidden\" name=\"dir\" value=\"".$dir_esc."\" />
        <input type=\"hidden\" name=\"site\" value=\"".$site."\" />
        <input type=\"hidden\" name=\"location\" value=\"".$location_esc."\" />
        <input type=\"hidden\" name=\"page\" value=\"".$page."\" />
        <input type=\"hidden\" name=\"compcat\" value=\"".$compcat."\" />
        <input type=\"hidden\" name=\"mediatype\" value=\"".$mediatype."\" />
        <input type=\"hidden\" name=\"lang\" value=\"".$lang."\" />
        <input type=\"hidden\" name=\"callback\" value=\"".$callback."\" />
        
        <input type=\"text\" name=\"search_expression\" value=\"";
        if ($search_expression != "") $result .= html_encode ($search_expression);
        else $result .= $text4[$lang];
        $result .= "\" onblur=\"if (this.value=='') this.value='".$text4[$lang]."';\" onfocus=\"if (this.value=='".$text4[$lang]."') this.value='';\" style=\"width:190px;\" maxlength=\"60\" />
        <img name=\"SearchButton\" src=\"".getthemelocation()."img/button_OK.gif\" onClick=\"if (document.forms['searchform_general'].elements['search_expression'].value=='".$text4[$lang]."') document.forms['searchform_general'].elements['search_expression'].value=''; document.forms['searchform_general'].submit();\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('SearchButton','','".getthemelocation()."img/button_OK_over.gif',1)\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" align=\"top\" alt=\"OK\" title=\"OK\" />
      </form>
    </div>\n";
    
    $result .= "<table width=\"98%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
  
    // parent directory
    if (substr_count ($dir, $mgmt_config['abs_path_comp']) > 0 && $dir != $mgmt_config['abs_path_comp'])
    {
      //get parent directory
      $updir_esc = getlocation ($dir_esc);
    
      if ($callback == "") $result .= "<tr><td align=\"left\" colspan=\"2\" nowrap=\"nowrap\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode($updir_esc)."&compcat=".url_encode($compcat)."&mediatype=".url_encode($mediatype)."&site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&scaling=".url_encode($scalingfactor)."\"><img src=\"".getthemelocation()."img/back.gif\" class=\"hcmsIconList\" />&nbsp;".$text1[$lang]."</a></td></tr>\n";
      else $result .= "<tr><td align=\"left\" colspan=\"2\" nowrap=\"nowrap\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode($updir_esc)."&site=".url_encode($site)."&compcat=".url_encode($compcat)."&mediatype=".url_encode($mediatype)."&lang=".url_encode($lang)."&callback=".url_encode($callback)."&scaling=".url_encode($scalingfactor)."\"><img src=\"".getthemelocation()."img/back.gif\" class=\"hcmsIconList\" />&nbsp;".$text1[$lang]."</a></td></tr>\n";
    }
    elseif ($search_expression != "")
    {
      if ($callback == "") $result .= "<tr><td align=\"left\" colspan=\"2\" nowrap=\"nowrap\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode("%comp%/")."&compcat=".url_encode($compcat)."&mediatype=".url_encode($mediatype)."&site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&scaling=".url_encode($scalingfactor)."\"><img src=\"".getthemelocation()."img/back.gif\" class=\"hcmsIconList\" />&nbsp;".$text1[$lang]."</a></td></tr>\n";
      else $result .= "<tr><td align=\"left\" colspan=\"2\" nowrap=\"nowrap\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode("%comp%/")."&site=".url_encode($site)."&compcat=".url_encode($compcat)."&mediatype=".url_encode($mediatype)."&lang=".url_encode($lang)."&callback=".url_encode($callback)."&scaling=".url_encode($scalingfactor)."\"><img src=\"".getthemelocation()."img/back.gif\" class=\"hcmsIconList\" />&nbsp;".$text1[$lang]."</a></td></tr>\n";
    }
    
    // search results
    if ($search_expression != "")
    {
      if ($mediatype != "") $object_type = array ($mediatype);
      else $object_type = "";
       
      $object_array = rdbms_searchcontent ($dir_esc, "", $object_type, "", "", "", array($search_expression), $search_expression, "", "", "", "", "", "", "", 100);
      
      if (is_array ($object_array))
      {
        foreach ($object_array as $entry)
        {
          if ($entry != "" && accessgeneral ($site, $entry, "comp"))
          {
            $entry_location = getlocation ($entry);
            $entry_object = getobject ($entry);
            $entry_object = correctfile ($entry_location, $entry_object, $user);

            if ($entry_object != false)
            {
              if ($entry_object == ".folder")
              {
                $comp_entry_dir[] = $entry_location.$entry_object;
              }
              else
              {
                $comp_entry_file[] = $entry_location.$entry_object;
              }
            }
          }
        }
      }
    }
    // file explorer
    else
    {
      // get all files in dir
      $outdir = @dir ($dir);

      // get all comp_outdir entries in folder and file array
      if ($outdir != false)
      {
        while ($comp_entry = $outdir->read())
        { 
          if ($comp_entry != "" && $comp_entry != "." && $comp_entry != ".." && $comp_entry != ".folder")
          {
            if ($dir != $mgmt_config['abs_path_comp'] || ($dir == $mgmt_config['abs_path_comp'] && ($mgmt_config[$site]['inherit_comp'] == true && is_array ($parent_array) && in_array ($comp_entry, $parent_array)) || $comp_entry == $site))
            {
              if (is_dir ($dir.$comp_entry))
              {
                $comp_entry_dir[] = $dir_esc.$comp_entry."/.folder";
              }
              elseif (is_file ($dir.$comp_entry))
              {
                $comp_entry_file[] = $dir_esc.$comp_entry;
              }
            }
          }
        }
        
        $outdir->close();
      }
    }

    if (isset ($comp_entry_dir) || isset ($comp_entry_file))
    {      
      // folder
      if (isset ($comp_entry_dir) && is_array ($comp_entry_dir) && sizeof ($comp_entry_dir) > 0)
      {
        natcasesort ($comp_entry_dir);
        reset ($comp_entry_dir);
        
        foreach ($comp_entry_dir as $dirname)
        {
          // folder info
          $folder_info = getfileinfo ($site, $dirname, "comp");
          $folder_path = getlocation ($dirname);
          $location_name = getlocationname ($site, $folder_path, "comp", "path");
          
          // define icon
          if ($dir == $mgmt_config['abs_path_comp']) $icon = getthemelocation()."img/site.gif";
          else $icon = getthemelocation()."img/".$folder_info['icon'];
            
          if ($callback == "") $result .= "<tr><td align=\"left\" colspan=\"2\" nowrap=\"nowrap\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode($folder_path)."&site=".url_encode($site)."&compcat=".url_encode($compcat)."&mediatype=".url_encode($mediatype)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&scaling=".url_encode($scalingfactor)."\" title=\"".$location_name."\"><img src=\"".$icon."\" class=\"hcmsIconList\" />&nbsp;".showshorttext($folder_info['name'], 24)."</a></td></tr>\n";
          else $result .= "<tr><td align=\"left\" colspan=\"2\" nowrap><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode($folder_path)."&site=".url_encode($site)."&compcat=".url_encode($compcat)."&mediatype=".url_encode($mediatype)."&lang=".url_encode($lang)."&callback=".url_encode($callback)."&scaling=".url_encode($scalingfactor)."\" title=\"".$location_name."\"><img src=\"".$icon."\" class=\"hcmsIconList\" />&nbsp;".showshorttext($folder_info['name'], 24)."</a></td></tr>\n";
        }
      }
      
      // component
      $page_info = getfileinfo ($site, $page, "comp");
      
      if (isset ($comp_entry_file) && is_array ($comp_entry_file) && sizeof ($comp_entry_file) > 0)
      {
        natcasesort ($comp_entry_file);
        reset ($comp_entry_file);
      
        foreach ($comp_entry_file as $object)
        {
          if ($object != "")
          {
            // object info
            $comp_info = getfileinfo ($site, $object, "comp");    
            
            // get name
            $comp_name = getlocationname ($site, $object, "comp", "path");
            
            if ($compcat != "media" && strlen ($comp_name) > 50) $comp_name = "...".substr (substr ($comp_name, -50), strpos (substr ($comp_name, -50), "/")); 
      
            if ($dir.$object != $location.$page && (($compcat != "media" && $comp_info['published'] == true && $comp_info['type'] == "Component") || ($compcat == "media" && ($mediatype == "" || $mediatype == "comp" || substr_count ($format_ext, $comp_info['ext']) > 0))))
            {
              $comp_path = $object;
      
              // warning if file extensions don't match and HTTP include is off
              if ($compcat != "media" && $mgmt_config[$site]['http_incl'] == false && ($comp_info['ext'] != $page_info['ext'] && $comp_info['ext'] != ".page")) $alert = "test = confirm(hcms_entity_decode('".$text3[$lang]."'));";    
              else $alert = "test = true;";
              
              if ($compcat == "single")
              {
                $result .= "<tr><td width=\"85%\" align=\"left\" nowrap=\"nowrap\"><a href=# onClick=\"".$alert." if (test == true) sendCompInput('".$comp_name."','".$comp_path."');\" title=\"".$comp_name."\"><img src=\"".getthemelocation()."img/".$comp_info['icon']."\" class=\"hcmsIconList\" />&nbsp;".showshorttext($comp_info['name'], 24)."</a></td><td align=\"left\" nowrap=\"nowrap\"><a href=# onClick=\"".$alert." if (test == true) sendCompInput('".$comp_name."','".$comp_path."')\"><img src=\"".getthemelocation()."img/button_OK_small.gif\" class=\"hcmsIconList\" alt=\"OK\" title=\"OK\" /></a></td></tr>\n";
              }
              elseif ($compcat == "multi")
              {
                $result .= "<tr><td width=\"85%\" align=\"left\" nowrap=\"nowrap\"><a href=# onClick=\"".$alert." if (test == true) sendCompOption('".$comp_name."','".$comp_path."');\" title=\"".$comp_name."\"><img src=\"".getthemelocation()."img/".$comp_info['icon']."\" class=\"hcmsIconList\" />&nbsp;".showshorttext($comp_info['name'], 24)."</a></td><td align=\"left\" nowrap=\"nowrap\"><a href=# onClick=\"".$alert." if (test == true) sendCompOption('".$comp_name."','".$comp_path."')\"><img src=\"".getthemelocation()."img/button_OK_small.gif\" class=\"hcmsIconList\" alt=\"OK\" title=\"OK\" /></a></td></tr>\n";
              }
              elseif ($compcat == "media")
              {
                if ($callback == "") $result .= "<tr><td width=\"85%\" align=\"left\" nowrap=\"nowrap\"><a href=# onClick=\"".$alert." if (test == true) sendMediaInput('".$comp_name."','".$comp_path."'); parent.frames['mainFrame2'].location.href='media_view.php?site=".url_encode($site)."&mediacat=cnt&mediatype=".url_encode($mediatype)."&mediaobject=".url_encode($comp_path)."&scaling=".url_encode($scalingfactor)."';\" title=\"".$comp_name."\"><img src=\"".getthemelocation()."img/".$comp_info['icon']."\" class=\"hcmsIconList\" />&nbsp;".showshorttext($comp_info['name'], 24)."</a></td><td align=\"left\" nowrap=\"nowrap\"><a href=# onClick=\"".$alert." if (test == true) sendMediaInput('".$comp_name."','".$comp_path."'); parent.frames['mainFrame2'].location.href='media_view.php?site=".url_encode($site)."&mediacat=cnt&mediatype=".url_encode($mediatype)."&mediaobject=".url_encode($comp_path)."&scaling=".url_encode($scalingfactor)."';\"><img src=\"".getthemelocation()."img/button_OK_small.gif\" class=\"hcmsIconList\" alt=\"OK\" title=\"OK\" /></a></td></tr>\n";
                else $result .= "<tr><td width=\"85%\" align=\"left\" nowrap><a href=# onClick=\"parent.frames['mainFrame2'].location.href='media_select.php?site=".url_encode($site)."&mediacat=cnt&mediatype=".url_encode($mediatype)."&mediaobject=".url_encode($comp_path)."&lang=".url_encode($lang)."&callback=".url_encode($callback)."&scaling=".url_encode($scalingfactor)."';\" title=\"".$comp_name."\"><img src=\"".getthemelocation()."img/".$comp_info['icon']."\" class=\"hcmsIconList\" />&nbsp;".showshorttext($comp_info['name'], 24)."</a></td><td align=\"left\" nowrap><a href=# onClick=\"parent.frames['mainFrame2'].location.href='media_select.php?site=".url_encode($site)."&mediacat=cnt&mediatype=".url_encode($mediatype)."&mediaobject=".url_encode($comp_path)."&lang=".url_encode($lang)."&callback=".url_encode($callback)."&scaling=".url_encode($scalingfactor)."';\"><img src=\"".getthemelocation()."img/button_OK_small.gif\" class=\"hcmsIconList\" alt=\"OK\" title=\"OK\" /></a></td></tr>\n";
              }
            }
          }
        }
      }
    }
    
    $result .= "</table>\n";
    
    // result
    return $result;    
  }
  else return false;
}

// --------------------------------------- showeditor -------------------------------------------
// function: showeditor ()
// input: publication name, hypertag name, hypertag id, content, width, height of the editor, toolbar set, language, dpi for scaling images
// output: rich text editor code / false on error

// description:
// shows the rich text editor

function showeditor ($site, $hypertagname, $id, $contentbot="", $sizewidth=600, $sizeheight=300, $toolbar="Default", $lang="en", $dpi='72')
{
  global $mgmt_config, $publ_config;
  
  if (is_array ($mgmt_config) && valid_publicationname ($site) && $hypertagname != "" && $id != "" && $lang != "")
  {
    if (valid_publicationname ($site) && !is_array ($publ_config)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"); 
    
    //initialize scaling factor 
    $scalingfactor = 1;
    
    //check if $dpi is valid and than calculate scalingfactor
    if ($dpi > 0 && $dpi < 1000) 
    {
      $scalingfactor = 72 / $dpi; 
    }
    
    return "<textarea id=\"".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id."]\">".$contentbot."</textarea>
      <script type=\"text/javascript\">
      <!--
        CKEDITOR.replace( '".$hypertagname."_".$id."',
        {
          baseHref:					               		'".$publ_config['url_publ_page']."',
          customConfig:             			  	'".$mgmt_config['url_path_cms']."editor/ckeditor_custom/editorf_config.js',
          language:	              						'".$lang."',
          scayt_sLang:		              			'".$lang."',
          height:					              			'".$sizeheight."',
          width:							              	'".$sizewidth."',
          filebrowserImageBrowseUrl:	    		'".$mgmt_config['url_path_cms']."editor/media_frameset.php?site=".url_encode($site)."&mediacat=cnt&mediatype=image&scaling=".url_encode($scalingfactor)."',
          filebrowserFlashBrowseUrl:	    		'".$mgmt_config['url_path_cms']."editor/media_frameset.php?site=".url_encode($site)."&mediacat=cnt&mediatype=flash',
          filebrowserVideoBrowseUrl:	    		'".$mgmt_config['url_path_cms']."editor/media_frameset.php?site=".url_encode($site)."&mediacat=cnt&mediatype=video',
          filebrowserLinkBrowsePageUrl:	    	'".$mgmt_config['url_path_cms']."editor/link_explorer.php?site=".url_encode($site)."',
          filebrowserLinkBrowseComponentUrl:	'".$mgmt_config['url_path_cms']."editor/media_frameset.php?site=".url_encode($site)."&mediacat=cnt&mediatype=',
          toolbar:	              						'".$toolbar."',
          cmsLink:	              						'".$mgmt_config['url_path_cms']."'
        });
      //-->
      </script>\n";
  }
  else return false;
}

// --------------------------------------- showinlineeditor_head -------------------------------------------
// function: showinlineeditor_head ()
// input: language
// output: rich text editor code for html head section / false on error

// description:
// shows the rich text editor code (JS, CSS) for include into the html head section

function showinlineeditor_head ($lang)
{
  global $mgmt_config;
  
  // load language
  require ($mgmt_config['abs_path_cms']."language/buildview.inc.php");
  
  if (is_array ($mgmt_config) && $lang != "")
  {
    return "
    <script src=\"".$mgmt_config['url_path_cms']."javascript/jquery/jquery-1.9.1.min.js\" type=\"text/javascript\"></script>
    <script type=\"text/javascript\" src=\"".$mgmt_config['url_path_cms']."/editor/ckeditor/ckeditor.js\"></script>
    <script type=\"text/javascript\">
      CKEDITOR.disableAutoInline = true;
      var jq_inline = $.noConflict();
      
      function hcms_validateForm() 
      {
        var i,p,q,nm,test,num,min,max,errors='',args=hcms_validateForm.arguments;
        
        for (i=0; i<(args.length-2); i+=3) 
        { 
          test=args[i+2]; val=hcms_findObj(args[i]);
          
          if (val) 
          { 
            nm=val.name;
            nm=nm.substring(nm.indexOf('_')+1, nm.length);
            
            if ((val=val.value)!='') 
            {
              if (test.indexOf('isEmail')!=-1) 
              { 
                p=val.indexOf('@');
                if (p<1 || p==(val.length-1)) errors += '".$text34[$lang].".\\n';
              } 
              else if (test!='R') 
              { 
                num = parseFloat(val);
                if (isNaN(val)) errors += '".$text35[$lang].".\\n';
                if (test.indexOf('inRange') != -1) 
                { 
                  p=test.indexOf(':');
                  if(test.substring(0,1) == 'R') {
                    min=test.substring(8,p); 
                  } else {
                    min=test.substring(7,p); 
                  }
                  max=test.substring(p+1);
                  if (num<min || max<num) errors += '".$text36[$lang]." '+min+' - '+max+'.\\n';
                } 
              } 
            } 
            else if (test.charAt(0) == 'R') errors += '".$text37[$lang].".\\n'; 
          }
        } 
        
        if (errors) 
        {
          alert(hcms_entity_decode('".$text38[$lang].":\\n'+errors));
          return false;
        }  
        else return true;
      }
      
      function hcms_initTextarea(ta_id, width, height)
      {
        if (parseInt(width) < 5) width = '100%';
        else width = (parseInt(width)+25)+'px';
        
        if (parseInt(height) < 5) height = '25px';
        else height = parseInt(height)+'px';
        
        document.getElementById(ta_id).style.width = '100%';
        document.getElementById(ta_id).style.height = height;
      }
      
      function hcms_adjustTextarea(ta)
      {
        if (ta.clientHeight < ta.scrollHeight)
        {
          ta.style.height = ta.scrollHeight + 'px';
          
          if (ta.clientHeight < ta.scrollHeight)
          {
            ta.style.height = (ta.scrollHeight * 2 - ta.clientHeight) + 'px';
          }
        }
      }
    </script>
    <style>
      .hcms_editable {
        overflow: visible;
        min-height: 10px;
        min-width: 10px;
        width: auto;
        height: auto;
        margin: 0px !important;
        padding: 0px !important;
        border: 0px !important;
        color: inherit;
        font-size: inherit;
        font-family: inherit;
        font-weight: inherit;
        font-style: inherit;
        line-height: inherit;
        display: inline-block;
        box-sizing: border-box;
        outline: 0 auto;
        box-shadow: none !important;
      }
      .hcms_editable:hover {
        outline: 2px solid #FF9000;
      }
      .hcms_editable:focus {
        outline: 0 auto;
      }
      .hcms_editable_textarea {
        overflow: visible;
        resize: both;
        margin: 0px !important;
        padding: 0px !important;
        border: 0px !important;
        color: inherit;
        font-family: inherit;
        font-size: inherit;
        font-weight: inherit;
        font-style: inherit;
        line-height: inherit;
        box-sizing: border-box;
        white-space: pre;
        outline: 0 auto;
        box-shadow: none !important;
        background-color: transparent;
      }
      .hcms_editable_textarea:hover {
        outline: 2px solid #FF9000;
      }
      .hcms_editable_textarea:focus {
        outline: 0 auto;
      }
    </style>
    ";
  }
  else return false;
}

// --------------------------------------- showinlinedatepicker_head -------------------------------------------
// function: showinlinedatepicker_head ()
// input: %
// output: date picker code for html head section / false on error

// description:
// shows the date picker code (JS, CSS) for include into the html head section

function showinlinedatepicker_head ()
{
  global $mgmt_config;
  
  if (is_array ($mgmt_config))
  {
    return " 

    <link rel=\"stylesheet\" hypercms_href=\"".$mgmt_config['url_path_cms']."javascript/rich_calendar/rich_calendar.css\">
    <script language=\"JavaScript\" type=\"text/javascript\" src=\"".$mgmt_config['url_path_cms']."javascript/rich_calendar/rich_calendar.js\"></script>
    <script language=\"JavaScript\" type=\"text/javascript\" src=\"".$mgmt_config['url_path_cms']."javascript/rich_calendar/rc_lang_en.js\"></script>
    <script language=\"JavaScript\" type=\"text/javascript\" src=\"".$mgmt_config['url_path_cms']."javascript/rich_calendar/rc_lang_de.js\"></script>
    <script language=\"Javascript\" type=\"text/javascript\" src=\"".$mgmt_config['url_path_cms']."javascript/rich_calendar/domready.js\"></script>
    ";
  }
  else return false;
}

// --------------------------------------- showinlineeditor -------------------------------------------
// function: showinlineeditor ()
// input: publication name, hypertag, hypertag id, content, width, height of the editor, toolbar set, language, 
//        content-type, category[page,comp], converted location, object name, container name, DB-connect file name, security token
// output: message box/false on error

// description:
// shows the rich text inline editor

function showinlineeditor ($site, $hypertag, $id, $contentbot="", $sizewidth=600, $sizeheight=300, $toolbar="Default", $lang="en", $contenttype="", $cat="", $location_esc="", $page="", $contentfile="", $db_connect=0, $token="")
{
  global $mgmt_config, $publ_config;
  
  require ($mgmt_config['abs_path_cms']."language/buildview.inc.php");
  
  // add confirm save on changes in inline editor or leave empty string
  // $confirm_save = " && confirm(hcms_entity_decode(\"".$text94[$lang]."\"));";
  $confirm_save = "";
  
  // get hypertagname
  $hypertagname = gethypertagname ($hypertag);
  
  // get constraint
  $constraint = getattribute ($hypertag, "constraint");
  
  // get tag id
  $id = getattribute ($hypertag, "id"); 
  
  // get label text
  $label = getattribute ($hypertag, "label");  
  
  // get format (if date)
  $format = getattribute ($hypertag, "format"); 
  if ($format == "") $format = "%Y-%m-%d";    
  
  if (substr($hypertagname, 0, strlen("arttext")) == "arttext")
  {
    // get article id
    $artid = getartid ($id);
    
    // element id
    $elementid = getelementid ($id);           

    // define label
    if ($label == "") $labelname = $artid." - ".$elementid;
    else $labelname = $artid." - ".$label;
  }
  else
  {
    // define label
    if ($label == "") $labelname = $id;
    else $labelname = $label;
  }
  
  $return = false;
  
  if (is_array ($mgmt_config) && valid_publicationname ($site) && $hypertagname != "" && $id != "" && $lang != "")
  {
    if (valid_publicationname ($site) && !is_array ($publ_config)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"); 
    
    // Building the title for the element
    $title = $labelname.": ";
    // And the tag of the element containing the content
    $tag = "span";
    // is the contenteditable attribute set
    $contenteditable = false;
    
    switch ($hypertagname)
    {
      case 'arttextu':
      case 'textu':
        $title .= $text0[$lang];
        break;
      case 'arttextf':
      case 'textf':
        $title .= $text1[$lang];
        $tag = "div";
        // disable contenteditable for inline editing
        $contenteditable = true;
        break;
      case 'arttextl':
      case 'textl':
        $title .= $text2[$lang];
        break;
      case 'arttextc':
      case 'textc':
        $title .= $text76[$lang];
        break;
      case 'arttextd':
      case 'textd':
        $title .= $text97[$lang];
        break;        
    }
    
    $defaultText = $title;
    
    // Building the display of the content
    $return = "<".$tag." id=\"".$hypertagname."_".$id."\" title=\"".$title."\" class=\"hcms_editable\" ".($contenteditable ? 'contenteditable="true" ' : '').">".(empty($contentbot) ? $defaultText : $contentbot)."</".$tag.">";
    
    // Building of the specific editor
    $element = "";
    
    switch ($hypertagname)
    {
      // checkbox
      case 'arttextc':
      case 'textc':
      
        // extract text value of checkbox
        $value = getattribute ($hypertag, "value");  
        
        $return .= "
          <script type=\"text/javascript\">
          jq_inline().ready(function() 
          {
            var oldcheck_".$hypertagname."_".$id." = \"\";
            jq_inline('#".$hypertagname."_".$id."').click(function(event)
            {
              event.stopPropagation();
            
              elem = jq_inline(this);
              checkbox = jq_inline('#hcms_checkbox_".$hypertagname."_".$id."');
              form = jq_inline('#hcms_form_".$hypertagname."_".$id."');
              
              oldcheck_".$hypertagname."_".$id." = jq_inline.trim(elem.text());
              
              elem.hide();
              form.css('display', 'inline');
              
              checkbox.focus();
            
            });
            jq_inline('#hcms_checkbox_".$hypertagname."_".$id."').click(function(event)
            {
              event.stopPropagation();
            }).blur(function()
            {
              checkbox = jq_inline(this);
              form = jq_inline('#hcms_form_".$hypertagname."_".$id."');
              elem = jq_inline('#".$hypertagname."_".$id."');
              
              newcheck = (checkbox.prop('checked') ? jq_inline.trim(checkbox.val()) : '".$defaultText."');
              
              if (oldcheck_".$hypertagname."_".$id." != newcheck".$confirm_save.")
              {
                  oldcheck_".$hypertagname."_".$id." = newcheck;
                  jq_inline.post(
                    \"".$mgmt_config['url_path_cms']."page_save.php\", 
                    jq_inline('#hcms_form_".$hypertagname."_".$id."').serialize(), 
                    function(data)
                      {
                        if(data.message.length !== 0)
                        {
                          alert(hcms_entity_decode(data.message));
                        }				
                      }, 
                    \"json\"
                    );
                  elem.text((newcheck == \"&nbsp;\" ? '' : newcheck));
                
              } else {
                checkbox.prop('checked', (oldcheck_".$hypertagname."_".$id." == \"\" ? false : true));
              }
              form.hide();
              elem.show();
            });
          });
        </script>
        ";
        $element = "<input type=\"hidden\" name=\"".$hypertagname."[".$id."]\" value=\"\"/><input title=\"".$labelname.": ".$text76[$lang]."\" type=\"checkbox\" id=\"hcms_checkbox_".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id."]\" value=\"".$value."\"".($value == $contentbot ? ' checked ' : '').">".$labelname;
        break;
        
      // date picker
      case 'arttextd':
      case 'textd':
        $return .= "
          <script type=\"text/javascript\">
          var cal_obj_".$hypertagname."_".$id." = null;
          var format_".$hypertagname."_".$id." = '".$format."';
          // Variable where we check if the user is in the datepicker and therefor do not save/close everything on blur
          var preventSave_".$hypertagname."_".$id." = false;
          
          jq_inline().ready(function() 
          {
            // onclose Handler (do nothing)
            var cal_on_close_".$hypertagname."_".$id." = function (cal) { }
           
            // handling the saving
            var date_save_".$hypertagname."_".$id." = function () {
              if(preventSave_".$hypertagname."_".$id." == false) {
                datefield = jq_inline('#hcms_datefield_".$hypertagname."_".$id."');
                form = jq_inline('#hcms_form_".$hypertagname."_".$id."');
                elem = jq_inline('#".$hypertagname."_".$id."');
                
                newdate = jq_inline.trim(datefield.val());
                var check = true;
                
                // Confirm the changes
                if (olddate_".$hypertagname."_".$id." != newdate".$confirm_save.") 
                {  
                    olddate_".$hypertagname."_".$id." = newdate;
                    jq_inline.post(
                      \"".$mgmt_config['url_path_cms']."page_save.php\", 
                      jq_inline('#hcms_form_".$hypertagname."_".$id."').serialize(), 
                      function(data)
                        {
                          if(data.message.length !== 0)
                          {
                            alert(hcms_entity_decode(data.message));
                          }				
                        }, 
                      \"json\"
                      );
                    elem.html(newdate == \"\" ? '".$defaultText."' : newdate);
                }
                else if (!check)
                {
                  datefield.focus();
                  // We jump out without changing anything
                  return false;
                }
                form.hide();
                cal_obj_".$hypertagname."_".$id.".hide();
                elem.show();
              }
            }
            
            // onchange handler
            var cal_on_change_".$hypertagname."_".$id." = function (cal, object_code)
            {
              if (object_code == 'day')
              {
                document.getElementById('hcms_datefield_".$hypertagname."_".$id."').value = cal.get_formatted_date(format_".$hypertagname."_".$id.");
                preventSave_".$hypertagname."_".$id." = false;
                date_save_".$hypertagname."_".$id."();
              }
            }
            
            function show_cal_".$hypertagname."_".$id." ()
            {
              
              var datefield_".$hypertagname."_".$id." = document.getElementById('hcms_datefield_".$hypertagname."_".$id."');  
              var form = document.getElementById('hcms_form_".$hypertagname."_".$id."');  
              if (cal_obj_".$hypertagname."_".$id.") {
                cal_obj_".$hypertagname."_".$id.".parse_date(datefield_".$hypertagname."_".$id.".value, format_".$hypertagname."_".$id.");
                cal_obj_".$hypertagname."_".$id.".show_at_element(form, 'child');
              } else {
                cal_obj_".$hypertagname."_".$id." = new RichCalendar();
                cal_obj_".$hypertagname."_".$id.".start_week_day = 1;
                cal_obj_".$hypertagname."_".$id.".show_time = false;
                cal_obj_".$hypertagname."_".$id.".language = '".$lang."';
                cal_obj_".$hypertagname."_".$id.".auto_close = false
                cal_obj_".$hypertagname."_".$id.".user_onchange_handler = cal_on_change_".$hypertagname."_".$id.";
                cal_obj_".$hypertagname."_".$id.".user_onclose_handler = cal_on_close_".$hypertagname."_".$id.";
                cal_obj_".$hypertagname."_".$id.".parse_date(datefield_".$hypertagname."_".$id.".value, format_".$hypertagname."_".$id.");
                cal_obj_".$hypertagname."_".$id.".show_at_element(form, 'child');
              }
              jq_inline('form#hcms_form_".$hypertagname."_".$id." iframe.rc_calendar').mouseover( function() {
                preventSave_".$hypertagname."_".$id." = true;
              }).mouseout( function() {
                preventSave_".$hypertagname."_".$id." = false;
                // We need to focus again on the field so that blur get's correctly called
                jq_inline('#hcms_datefield_".$hypertagname."_".$id."').focus();
              });
            }            
            
            var olddate_".$hypertagname."_".$id." = \"\";
            
            jq_inline('#".$hypertagname."_".$id."').click(function(event) {
            event.stopPropagation();
          
            elem = jq_inline(this);
            datefield = jq_inline('#hcms_datefield_".$hypertagname."_".$id."');
            form = jq_inline('#hcms_form_".$hypertagname."_".$id."');
            
            olddate_".$hypertagname."_".$id." = datefield.val();
            
            if (olddate_".$hypertagname."_".$id." == '".$defaultText."') olddate_".$hypertagname."_".$id." = '';
            
            elem.hide();
            form.css('display', 'inline');
            
            datefield.val('');
            datefield.focus();
            datefield.val(olddate_".$hypertagname."_".$id.");
            show_cal_".$hypertagname."_".$id."();
            });
            jq_inline('#hcms_datefield_".$hypertagname."_".$id."').click(function(event)
            {
              event.stopPropagation();
            }).blur(date_save_".$hypertagname."_".$id.");
          });
          </script>
        ";
        $element = "<input title=\"".$labelname.": ".$text97[$lang]."\"type=\"text\" id=\"hcms_datefield_".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id."]\" value=\"".$contentbot."\" /><br>";
        break;
        
      // unformatted text
      case 'arttextu':
      case 'textu':
        $return .= "
          <script type=\"text/javascript\">
          jq_inline().ready(function() 
          {
            var oldtext_".$hypertagname."_".$id." = \"\";
            jq_inline('#".$hypertagname."_".$id."').click(function(event) {
            event.stopPropagation();
          
            elem = jq_inline(this);
            txtarea = jq_inline('#hcms_txtarea_".$hypertagname."_".$id."');
            form = jq_inline('#hcms_form_".$hypertagname."_".$id."');
            
            oldtext_".$hypertagname."_".$id." = hcms_entity_decode(elem.html().replace(/\<br\>/g, '\\n'));
            
            if (oldtext_".$hypertagname."_".$id." == '".$defaultText."') oldtext_".$hypertagname."_".$id." = '';
            
            elem.hide();
            form.css('display', 'inline');
            
            txtarea.val('');
            txtarea.focus();
            txtarea.val(oldtext_".$hypertagname."_".$id.");
            });
            jq_inline('#hcms_txtarea_".$hypertagname."_".$id."').click(function(event)
            {
              event.stopPropagation();
            }).blur(function()
            {
              txtarea = jq_inline(this);
              form = jq_inline('#hcms_form_".$hypertagname."_".$id."');
              elem = jq_inline('#".$hypertagname."_".$id."');
              
              newtext = jq_inline.trim(txtarea.val());
              var constraint = \"".$constraint."\";
              var check = true;
              
              if (oldtext_".$hypertagname."_".$id." != newtext && (constraint == \"\" || (check = hcms_validateForm('hcms_txtarea_".$hypertagname."_".$id."','', constraint)))".$confirm_save.") 
              {  
                  oldtext_".$hypertagname."_".$id." = newtext;
                  jq_inline.post(
                    \"".$mgmt_config['url_path_cms']."page_save.php\", 
                    jq_inline('#hcms_form_".$hypertagname."_".$id."').serialize(), 
                    function(data)
                      {
                        if(data.message.length !== 0)
                        {
                          alert(hcms_entity_decode(data.message));
                        }				
                      }, 
                    \"json\"
                    );
                  elem.html(newtext.replace(/\\r?\\n|\\r/g, '<br>'));
              }
              else if (!check)
              {
                txtarea.focus();
                // jump out without changing anything
                return false;
              }
              
              form.hide();
              elem.show();
            });
          });
          
          // initalize size
          setTimeout(function(){ hcms_initTextarea('hcms_txtarea_".$hypertagname."_".$id."', document.getElementById('".$hypertagname."_".$id."').offsetWidth, document.getElementById('".$hypertagname."_".$id."').offsetHeight); }, 800);
          </script>
        ";
        // textarea
        $element = "<textarea title=\"".$labelname.": ".$text0[$lang]."\" id=\"hcms_txtarea_".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id."]\" onkeyup=\"hcms_adjustTextarea(this);\" class=\"hcms_editable_textarea\">".$contentbot."</textarea>";
        break;
        
      // text options/list
      case 'arttextl':
      case 'textl':      
        $return .= "
          <script type=\"text/javascript\">
          jq_inline().ready(function() 
          {
            var oldselect_".$hypertagname."_".$id." = \"\";
            jq_inline('#".$hypertagname."_".$id."').click(function(event) {
            event.stopPropagation();
          
            elem = jq_inline(this);
            selectbox = jq_inline('#hcms_selectbox_".$hypertagname."_".$id."');
            form = jq_inline('#hcms_form_".$hypertagname."_".$id."');
            
            oldselect_".$hypertagname."_".$id." = selectbox.val();
            
            elem.hide();
            form.css('display', 'inline');
            
            selectbox.focus();
            });
            jq_inline('#hcms_selectbox_".$hypertagname."_".$id."').click(function(event)
            {
              event.stopPropagation();
            }).blur(function() {
              selectbox = jq_inline(this);
              form = jq_inline('#hcms_form_".$hypertagname."_".$id."');
              elem = jq_inline('#".$hypertagname."_".$id."');
              
              newselect = selectbox.val();
              
              if (oldselect_".$hypertagname."_".$id." != newselect".$confirm_save.")
              {
                oldselect_".$hypertagname."_".$id." = newselect;
                jq_inline.post(
                  \"".$mgmt_config['url_path_cms']."page_save.php\", 
                  jq_inline('#hcms_form_".$hypertagname."_".$id."').serialize(), 
                  function(data)
                    {
                      if(data.message.length !== 0)
                      {
                        alert(hcms_entity_decode(data.message));
                      }				
                    }, 
                  \"json\"
                  );
                elem.text(newselect);
              } else {
                selectbox.val(oldselect_".$hypertagname."_".$id.");
              }
              form.hide();
              elem.show();
            });
          });
        </script>
        ";
        // Building the select box
        $list = explode ("|", getattribute ($hypertag, "list"));
        $element = "<select title=\"".$labelname.": ".$text2[$lang]."\" id=\"hcms_selectbox_".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id."]\">\n";
        
        foreach ($list as $elem)
        {
          $element .= "  <option value=\"".$elem."\"".($elem == $contentbot ? ' selected ' : '').">".$elem."</option>\n";
        }
        
        $element .= "</select>\n";
        break;
        
      // formatted text
      case 'arttextf':
      case 'textf':
        //get attribute dpi for right scaling of the images 72/dpi
        $dpi = getattribute ($hypertag, "dpi");
        
        //initialize scaling factor 
        $scalingfactor = 1;
        
        //check if $dpi is valid and than calculate scalingfactor
        if ($dpi > 0 && $dpi < 1000) 
        {
          $scalingfactor = 72 / $dpi; 
        }
        
        $return .= "
          <script type=\"text/javascript\">
            jq_inline().ready(function() 
            {
              var oldtext_".$hypertagname."_".$id." = \"\";
              jq_inline('#".$hypertagname."_".$id."').click(function(event) 
              { // Prevent propagation so that only ckeditor is shown and no operations from a parent onClick is performed
                event.stopPropagation();
              }).mouseover(function()
              { // Overwriting the title everytime the mouse moves over the element, because CKEditor does overwrite it sometimes
                jq_inline(this).attr('title', '".$title."' );
              });
              CKEDITOR.inline( '".$hypertagname."_".$id."',
              {
                baseHref:					               		'".$publ_config['url_publ_page']."',
                customConfig:             			  	'".$mgmt_config['url_path_cms']."editor/ckeditor_custom/inline_config.js',
                language:	              						'".$lang."',
                scayt_sLang:		              			'".$lang."',
                height:					              			'".$sizeheight."',
                width:							              	'".$sizewidth."',
                filebrowserImageBrowseUrl:	    		'".$mgmt_config['url_path_cms']."editor/media_frameset.php?site=".url_encode($site)."&mediacat=cnt&mediatype=image&scaling=".url_encode($scalingfactor)."',
                filebrowserFlashBrowseUrl:	    		'".$mgmt_config['url_path_cms']."editor/media_frameset.php?site=".url_encode($site)."&mediacat=cnt&mediatype=flash',
                filebrowserVideoBrowseUrl:	    		'".$mgmt_config['url_path_cms']."editor/media_frameset.php?site=".url_encode($site)."&mediacat=cnt&mediatype=video',
                filebrowserLinkBrowsePageUrl:	    	'".$mgmt_config['url_path_cms']."editor/link_explorer.php?site=".url_encode($site)."',
                filebrowserLinkBrowseComponentUrl:	'".$mgmt_config['url_path_cms']."editor/media_frameset.php?site=".url_encode($site)."&mediacat=cnt&mediatype=',
                toolbar:	              						'".$toolbar."',
                cmsLink:	              						'".$mgmt_config['url_path_cms']."',
                on: {
                  focus: function( event ) {
                    oldtext_".$hypertagname."_".$id." = jq_inline.trim(event.editor.getData());
                    if(oldtext_".$hypertagname."_".$id." == '<p>".$defaultText."</p>') {
                      oldtext_".$hypertagname."_".$id." = '';
                    }
                    event.editor.setData(oldtext_".$hypertagname."_".$id.");
                  },
                  blur: function (event)
                  {
                    var newtext = jq_inline.trim(event.editor.getData());
                    
                    if(oldtext_".$hypertagname."_".$id." != newtext".$confirm_save.")
                    {
                      oldtext_".$hypertagname."_".$id." = newtext;
                      jq_inline('#hcms_txtarea_".$hypertagname."_".$id."').val(event.editor.getData());
                      jq_inline.post(
                        \"".$mgmt_config['url_path_cms']."page_save.php\", 
                        jq_inline('#hcms_form_".$hypertagname."_".$id."').serialize(), 
                        function(data)
                          {
                            if(data.message.length !== 0)
                            {
                              alert(hcms_entity_decode(data.message));
                            }
                            
                          }, 
                        \"json\"
                        );
                    } else {
                      event.editor.setData(oldtext_".$hypertagname."_".$id.");
                    }
                    if(event.editor.getData() == '')
                    {
                      event.editor.setData('<p>".$defaultText."</p>');
                    }
                  }
                }
              });
            });
          </script>
        ";
        $element = "<textarea title=\"".$labelname.": ".$text1[$lang]."\" id=\"hcms_txtarea_".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id."]\">".$contentbot."</textarea>";
        break;
      default:
        break;
    }
    
    // Adding the form that is submitted including the element that is sent
    $return .= "
      <form style=\"display:none;\" method=\"post\" id=\"hcms_form_".$hypertagname."_".$id."\">
        <input type=\"hidden\" name=\"contenttype\" value=\"".$contenttype."\"> 
        <input type=\"hidden\" name=\"site\" value=\"".$site."\"> 
        <input type=\"hidden\" name=\"cat\" value=\"".$cat."\"> 
        <input type=\"hidden\" name=\"location\" value=\"".$location_esc."\">
        <input type=\"hidden\" name=\"page\" value=\"".$page."\"> 
        <input type=\"hidden\" name=\"contentfile\" value=\"".$contentfile."\">
        <input type=\"hidden\" name=\"db_connect\" value=\"".$db_connect."\">
        <input type=\"hidden\" name=\"tagname\" value=\"".$hypertagname."\"> 
        <input type=\"hidden\" name=\"id\" value=\"".$id."\"> 
        <input type=\"hidden\" name=\"width\" value=\"".$sizewidth."\"> 
        <input type=\"hidden\" name=\"height\" value=\"".$sizeheight."\">
        <input type=\"hidden\" name=\"toolbar\" value=\"".$toolbar."\"> 
        <input type=\"hidden\" id=\"savetype\" name=\"savetype\" value=\"auto\">
        <input type=\"hidden\" name=\"token\" value=\"".$token."\">
        ".$element."
      </form>\n";
  }
  
  return $return;
}

// ------------------------- showvideoplayer -----------------------------
// function: showvideoplayer()
// input:
// videoArray (Array) containing the different html sources
// width (Integer) Width of the video in pixel
// height (Integer) Height of the video in pixel
// view [publish/preview]
// logo_url (String) Link to the logo which is displayed before you click on play (If the value is null the default logo will be used)
// id (String) The ID of the video (will be generated when empty)
// title (String) The title for this video
// autoplay (Boolean) Should the video be played on load (true), default is false
// enableFullScreen (Boolean) Is it possible to view the video in fullScreen (true)
// enableKeyBoard (Boolean) Is it possible to use the Keyboard (true)
// enablePause (Boolean) Is it possible to pause the video (true)
// enableSeek (Boolean) Is it possible to seek or to skip the video (true)
// output: String Code for the HTML

// description:
// Generates a html segment for the video code we use. False on error.

function showvideoplayer ($site, $video_array, $width=320, $height=240, $view="publish", $logo_url=Null, $id="", $title="", $autoplay=true, $enableFullScreen=true, $enableKeyBoard=true, $enablePause=true, $enableSeek=true, $iframe=false)
{
  global $mgmt_config;

  if (valid_publicationname ($site) && is_array ($video_array) && $width != "" && $height != "" && $view != "")
  {
    // prepare video array
    $sources = "";
    
    foreach ($video_array as $value)
    {
      // only partial URL
      if (strpos ("_".trim($value), "http") != 1)
      {
        // version 2.0 (only media reference incl. the wrapper is given)
        if (strpos ("_".$value, "explorer_wrapper.php") > 0)
        {
          $media = getattribute ($value, "media");
          
          if ($media != "") $type = "type=\"".getmimetype ($media)."\" ";
          else $type = "";
          
          $url = $mgmt_config['url_path_cms'].$value;
        }
        // version 2.0 (only media reference is given, no ; as seperator is used)
        elseif (strpos ($value, ";") < 1)
        {
          $media = getattribute ($value, "media");
          
          if ($media != "") $type = "type=\"".getmimetype ($media)."\" ";
          else $type = "";
          
          $url = $mgmt_config['url_path_cms']."explorer_wrapper.php?media=".$value."&token=".hcms_crypt($value);
        }
        // version 2.1 (media reference and mimetype is given)
        elseif (strpos ($value, ";") > 0)
        {
          list ($media, $type) = explode (";", $value);
           
          $type = "type=\"".$type."\" ";
          $url = $mgmt_config['url_path_cms']."?wm=".hcms_encrypt($media);
        }
        else $url = "";
      }
      // absulute URL
      else
      {
        $media = getattribute ($value, "media");
        
        if ($media != "") $type = "type=\"".getmimetype ($media)."\" ";
        else $type = "";
        
        $url = $value;
      }

      if ($url != "") $sources .= "    <source src=\"".$url."\" ".$type."/>\n";
    }
    
    // logo from video thumb image
    $logo_file = getobject ($media);
    $media_dir = getmedialocation ($site, $media, "abs_path_media");
    $media_url = getmedialocation ($site, $logo_file, "url_path_media");

    // define logo if undefined
    if ($logo_url == NULL && $media_dir != "")
    {
      if (strpos ($logo_file, ".orig.") > 0)
      {
        $logo_name = substr ($logo_file, 0, strrpos ($logo_file, ".orig."));
        
        if (is_file ($media_dir.$site."/".$logo_name.".thumb.jpg")) $logo_url = $media_url.$site."/".$logo_name.".thumb.jpg";
      }
      elseif (strpos ($logo_file, ".thumb.") > 0)
      {
        $logo_name = substr ($logo_file, 0, strrpos ($logo_file, ".thumb."));
        
        if (is_file ($media_dir.$site."/".$logo_name.".thumb.jpg")) $logo_url = $media_url.$site."/".$logo_name.".thumb.jpg";
      }
    }

    // if logo is in media repository
    if (substr_count ($logo_url, $mgmt_config['url_path_media'])) 
    {
      $logo_new = str_replace ($mgmt_config['url_path_media'], "", $logo_url);
      $logo_url = $mgmt_config['url_path_cms'].'explorer_wrapper.php?media='.$logo_new.'&token='.hcms_crypt($logo_new);
    }

    $flashplayer = $mgmt_config['url_path_cms']."javascript/video/jarisplayer.swf";
    
    // if no logo is defined set default logo
    if (is_null ($logo_url)) $logo_url = getthemelocation()."img/logo_player.jpg";
    
    if (empty ($id)) $id = uniqid();    
  
    // PROJEKKTOR Player
    if (isset ($mgmt_config['videoplayer']) && strtolower ($mgmt_config['videoplayer']) == "projekktor")
    {
      $return = '
  <video id="hcms_mediaplayer_'.$id.'" class="projekktor"'.((!empty($logo_url)) ? ' poster="'.$logo_url.'" ' : ' ').((!empty($title)) ? ' title="'.$title.'" ' : ' ').'width="'.$width.'" height="'.$height.'" '.($enableFullScreen ? 'allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true" ' : '').'controls>'."\n";

      $return .= $sources;
    
      $return .= '  </video>
  <script type="text/javascript">
  <!--
  jQuery(document).ready(function() 
  {
    projekktor("#hcms_projekktor_'.$id.'", 
    {
      useYTIframeAPI: false,
      height: '.intval($height).',
      width: '.intval($width).',';
      
      if (!empty ($logo)) $return .= '
      poster: "'.$logo.'",';
      
      $return .= '
      autoplay: '.(($autoplay) ? 'true' : 'false').',
      enableFullscreen: '.(($enableFullScreen) ? 'true' : 'false').',
      enableKeyboard: '.(($enableKeyBoard) ? 'true' : 'false').',
      disablePause: '.(($enablePause) ? 'false' : 'true').',
      disallowSkip: '.(($enableSeek) ? 'false' : 'true').',
      playerFlashMP4: "'.$flashplayer.'"';
      
      if ($iframe) $return .= ',
      iframe: true';
      
      $return .= '
    });
  });
  //-->
  </script>'."\n";
    }
    // VIDEO.JS Player (Standard)
    else
    {
      // get browser info
      $user_client = getbrowserinfo ();
      
      if (isset ($user_client['msie']) && $user_client['msie'] > 0) $fallback = ", \"playerFallbackOrder\":[\"flash\", \"html5\", \"links\"]";
      else $fallback = "";
    
      $return = "  <video id=\"hcms_mediaplayer_".$id."\" class=\"video-js vjs-default-skin\" controls ".(($autoplay) ? "autoplay" : "")." preload=\"auto\" 
    width=\"".intval($width)."\" height=\"".intval($height)."\" ".(($logo_url != "") ? "poster=\"".$logo_url."\"" : "")."
    data-setup='{\"loop\":false".$fallback."}' title=\"".$title."\" ".($enableFullScreen ? "allowFullScreen=\"true\" webkitallowfullscreen=\"true\" mozallowfullscreen=\"true\"" : "").">\n";
    
      $return .= $sources;
      
      $return .= "  </video>\n";
    }

    return $return;
  }
  else return false;
}

// ------------------------- showvideoplayer_head -----------------------------
// function: showvideoplayer_head()
// input: publication name, secure hyperreferences by adding 'hypercms_', view [publish,%] 
// output: head for video player / false on error

function showvideoplayer_head ($site, $secureHref=true, $view="publish")
{
  global $mgmt_config;
  
  if (valid_publicationname ($site))
  {
    // PROJEKKTOR Player
    if (isset ($mgmt_config['videoplayer']) && strtolower ($mgmt_config['videoplayer']) == "projekktor")
    {
      $jquerylib = $mgmt_config['url_path_cms']."javascript/jquery/jquery-1.9.1.min.js";
      $css = $mgmt_config['url_path_cms']."javascript/video/theme/style.css";
      $projekktor = $mgmt_config['url_path_cms']."javascript/video/projekktor.min.js";

      $return = '
  <script type="text/javascript">
    var noConflict = (typeof $ !== "undefined");
  </script>
  <script type="text/javascript" src="'.$jquerylib.'"></script>
  <link rel="stylesheet" '.(($secureHref) ? 'hypercms_' : '').'href="'.$css.'" type="text/css" media="screen" />
  <!-- older version before 5.3 needs jq_vid -->
  <!-- we use $.noConflict() when there is already something loaded into $ -->
  <script type="text/javascript">
  if (noConflict) var jq_vid = $.noConflict();
  else var jq_vid = $;
  </script>
  <script type="text/javascript" src="'.$projekktor.'"></script>'."\n";
    }
    // VIDEO.JS Player (Standard)
    else
    {
      $return = "  <link ".(($secureHref) ? "hypercms_" : "")."href=\"".$mgmt_config['url_path_cms']."javascript/video-js/video-js.css\" rel=\"stylesheet\" />
  <script src=\"".$mgmt_config['url_path_cms']."javascript/video-js/video.js\"></script>
  <script>
    videojs.options.flash.swf = \"".$mgmt_config['url_path_cms']."javascript/video-js/video-js.swf\";
  </script>\n";
    }
    
    return $return;
  }
  else return false;
}

// ------------------------- showaudioplayer -----------------------------
// function: showaudioplayer()
// input: publication name, audio files as array (Array), view [publish/preview], ID of the tag (optional),
//        autoplay (optional), play loop (optional)
// output: String Code for the HTML

// description:
// Generates a html segment for the video code we use. False on error.

function showaudioplayer ($site, $audioArray, $view="publish", $id="", $autoplay=false, $loop=false)
{
  global $mgmt_config;

  if (valid_publicationname ($site) && is_array ($audioArray) && $view != "")
  {
    // prepare video array
    $sources = "";
    
    foreach ($audioArray as $value)
    {
      // only partial URL
      if (strpos ("_".trim($value), "http") != 1)
      {
        // version 2.0 (only media reference incl. the wrapper is given)
        if (strpos ("_".$value, "explorer_wrapper.php") > 0)
        {
          $media = getattribute ($value, "media");
          
          if ($media != "") $type = "type=\"".getmimetype ($media)."\" ";
          else $type = "";
          
          $url = $mgmt_config['url_path_cms'].$value;
        }
        // version 2.0 (only media reference is given, no ; as seperator is used)
        elseif (strpos ($value, ";") < 1)
        {
          $media = getattribute ($value, "media");
          
          if ($media != "") $type = "type=\"".getmimetype ($media)."\" ";
          else $type = "";
          
          $url = $mgmt_config['url_path_cms']."explorer_wrapper.php?media=".$value."&token=".hcms_crypt($value);
        }
        // version 2.1 (media reference and mimetype is given)
        elseif (strpos ($value, ";") > 0)
        {
          list ($media, $type) = explode (";", $value);
          
          $type = "type=\"".$type."\" ";
          $url = $mgmt_config['url_path_cms']."?wm=".hcms_encrypt($media);
        }
        else $url = "";
      }
      // absulute URL
      else
      {
        $media = getattribute ($value, "media");
        
        if ($media != "") $type = "type=\"".getmimetype ($media)."\" ";
        else $type = "";
        
        $url = $value;
      }
      
      if ($url != "") $sources .= "    <source src=\"".$url."\" ".$type."/>\n";
    }
        
    if (empty($id)) $id = uniqid();    
  
    $return = '
  <audio id="hcms_audioplayer_'.$id.'" preload="auto">'."\n";

      $return .= $sources;
    
      $return .= '  </audio>
  <script type="text/javascript">
  <!--
    audiojs.events.ready(function() {
      var element = document.getElementById("hcms_audioplayer_'.$id.'");
      var config = {
        "autoplay": '.($autoplay == true ? 'true' : 'false').',
        "loop": '.($loop == true ? 'true' : 'false').'
      };
      audiojs.create(element, config);
    });
  //-->
  </script>'."\n";
  
    return $return;
  }
  else return false;
}

// ------------------------- showaudioplayer_head -----------------------------
// function: showaudioplayer_head()
// input: 
// output: head for video player

function showaudioplayer_head ()
{
  global $mgmt_config;
  
  return "<script src=\"".$mgmt_config['url_path_cms']."javascript/audio-js/audio.js\"></script>\n";
}

// ------------------------- debug_getbacktracestring -----------------------------
// function: debug_getbacktracestring()
// input: separator for arguments, separator for a Row on screen/file, functionnames to be ignored
// output: debug message

// description:
// Returns the current backtrace as a good readable string
// ignores debug and debug_getbacktracestring

function debug_getbacktracestring ($valueSeparator, $rowSeparator, $ignoreFunctions=array())
{
  if(!is_array($ignoreFunctions)) $ignoreFunctions = array();
  
  $ignoreFunctions[] = 'debug_getbacktracestring';  
  $trace = debug_backtrace();  
  $msg = array();
  
  if (is_array ($trace))
  {
    //Running through the Stack
    foreach ($trace as $stack)
    {
      // We don't need to export the debug functions
      if (!is_array ($stack) || in_array ($stack['function'], $ignoreFunctions ))
      {
        continue;
      }
      
      $specialcount = 1;
      //Building String for Function Variables
      $arguments = array();
      $add = array();
      
      if (!empty ($stack['args']) && is_array($stack['args']))
      {
        foreach ($stack['args'] as $arg)
        {
          if (is_array($arg) || is_object($arg))
          {
            $arguments[] = 'Arg#'.$specialcount;
            $add[] = 'Arg#'.($specialcount++).var_export($arg, true);
          }
          else $arguments[] = var_export($arg, true);
        }
      } 
      
      // When $stack['class'] exists we can use $stack['type'] else it was not a class function and we don't ouput a class
      if (!isset($stack['class'])) $stack['class'] = "";
      else $stack['class'] .= $stack['type'];

      // Building the arguments and the additional information      
      $arguments = implode($valueSeparator, $arguments);
      
      // We only put out add information when there is something to output
      if (!empty($add)) $add = $rowSeparator."Objects/Arrays:".$rowSeparator.implode($rowSeparator, $add);
      else $add = "";

      $msg[] = 'In '.@$stack['file'].' at Line '.@$stack['line'].'. Function called: '.@$stack['class'].@$stack['function'].'('.$arguments.')'.$add;
    }
  }
  else
  {
    $msg[] = 'Trace was not an Array! ('.var_export($trace, true).')';
  }
  
  // We only return something when we have anything
  if (empty($msg)) return "";
  else return implode ($rowSeparator, $msg).$rowSeparator;
}
?>