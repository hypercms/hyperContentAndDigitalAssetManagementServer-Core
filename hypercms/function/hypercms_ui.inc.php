<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
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
// Sets explorer objectlist view parameter

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
// Enables or disables the sidebar

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
// input: set of filters as array with keys [comp,image,document,video,audio] and value [0,1]
// output: true / false

// description:
// Set filter settings for object view in session

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
// If an object name is passing the filter-test. One or more filters need to be set in the session "hcms_objectfilter".

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
      if ($filter == "comp" && $value == 1) $ext .= strtolower ($hcms_ext['cms']);
      elseif ($filter == "image" && $value == 1) $ext .= strtolower ($hcms_ext['image']);
      elseif ($filter == "document" && $value == 1) $ext .= strtolower ($hcms_ext['bintxt'].$hcms_ext['cleartxt']);
      elseif ($filter == "video" && $value == 1) $ext .= strtolower ($hcms_ext['video']);
      elseif ($filter == "audio" && $value == 1) $ext .= strtolower ($hcms_ext['audio']);
      elseif ($filter == "flash" && $value == 1) $ext .= strtolower ($hcms_ext['flash']);
      elseif ($filter == "compressed" && $value == 1) $ext .= strtolower ($hcms_ext['compressed']);
      elseif ($filter == "binary" && $value == 1) $ext .= strtolower ($hcms_ext['binary']);
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
// function: showshorttext ()
// input: text as string, max. length of text (minus length starting from the end) (optional),
//        line break instead of cut [true,false] only if length is positive (optional), character set for encoding (optional)
// output: shortened text if possible, or orignal text

// description:
// Reduce the length of a string and add "..." at the end

function showshorttext ($text, $length=0, $linebreak=false, $charset="UTF-8")
{
  if ($text != "" && $length > 0)
  {
    if (!$linebreak)
    {
      if (mb_strlen ($text, $charset) > $length)
      {
        return mb_substr ($text, 0, $length, $charset)."...";
      }
      else return $text;
    }
    else
    {
      // max. 3 lines
      if (mb_strlen ($text, $charset) > ($length * 3)) $text = mb_substr ($text, 0, $length, $charset)."<br />\n".mb_substr ($text, $length, $length, $charset)."<br />\n".mb_substr ($text, ($length*2), ($length-2), $charset)."...";
      elseif (mb_strlen ($text, $charset) > ($length * 2)) $text = mb_substr ($text, 0, $length, $charset)."<br />\n".mb_substr ($text, $length, $length, $charset)."<br />\n".mb_substr ($text, ($length*2), NULL, $charset);
      elseif (mb_strlen ($text,$charset) > $length) $text = mb_substr ($text, 0, $length, $charset)."<br />\n".mb_substr ($text, $length, NULL, $charset);
      
      // keep 
      return "<div style=\"vertical-align:top; height:50px; display:block;\">".$text."</div>";
    }
  }
  elseif ($text != "" && $length < 0)
  {
    if (mb_strlen ($text, $charset) > ($length * -1)) return "...".mb_substr ($text, $length, $charset);
    else return $text;
  }
  else return $text;
}

// --------------------------------------- showtopbar -------------------------------------------
// function: showtopbar ()
// input: message, language code (optional), close button link (optional), link target (optional), individual button (optional), ID of div-layer (optional)
// output: top bar box / false on error

// description:
// Returns the standard top bar with or without close button

function showtopbar ($show, $lang="en", $close_link="", $close_target="", $individual_button="", $id="bar")
{
  global $mgmt_config, $hcms_charset, $hcms_lang;
        
  if ($show != "" && strlen ($show) < 600 && $lang != "" && $id != "")
  {
    $close_button_code = "";
    $individual_button_code = "";
    
    // define close button
    if (trim ($close_link) != "")
    {
      $close_id = uniqid();
      $close_button_code = "<td style=\"width:26px; text-align:right; vertical-align:middle;\"><a href=\"".$close_link."\" target=\"".$close_target."\" onMouseOut=\"hcms_swapImgRestore();\" onMouseOver=\"hcms_swapImage('close_".$close_id."','','".getthemelocation()."img/button_close_over.gif',1);\"><img name=\"close_".$close_id."\" src=\"".getthemelocation()."img/button_close.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['close'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['close'][$lang], $hcms_charset, $lang)."\" /></a></td>\n";
    }
    
    if (trim ($individual_button) != "")
    {
      $individual_button_code = "<td style=\"width:26px; text-align:right; vertical-align:middle;\">".$individual_button."</td>";
    }
    
    return "
  <div id=\"".$id."\" class=\"hcmsWorkplaceBar\">
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
// Returns the menu top bar with or without close button

function showtopmenubar ($show, $menu_array, $lang="en", $close_link="", $close_target="", $id="bar")
{
  global $mgmt_config, $hcms_charset, $hcms_lang;
        
  if ($show != "" && is_array ($menu_array) && strlen ($show) < 600 && $lang != "" && $id != "")
  {
    // define close button
    if ($close_link != "")
    {
      $close_id = uniqid();
      $close_button = "<td style=\"width:26px; text-align:right; vertical-align:middle;\"><a href=\"".$close_link."\" target=\"".$close_target."\" onMouseOut=\"hcms_swapImgRestore();\" onMouseOver=\"hcms_swapImage('close_".$close_id."','','".getthemelocation()."img/button_close_over.gif',1);\"><img name=\"close_".$close_id."\" src=\"".getthemelocation()."img/button_close.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['close'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['close'][$lang], $hcms_charset, $lang)."\" /></a></td>\n";
    }
    else $close_button = "";
    
    // define text or button
    $menu_button = "";
    $id = 0;
    
    foreach ($menu_array as $name => $events)
    {
      $menu_button .= "<div id=\"barbutton_".$id."\" class=\"hcmsButtonMenu\" ".$events.">".$name."</div>";
      $id++;
    }
    
    return "
  <div id=\"".$id."\" class=\"hcmsWorkplaceBar\">
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
// Returns the standard message box with close button

function showmessage ($show, $width="580px", $height="70px", $lang="en", $style="", $id="hcms_messageLayer")
{
  global $mgmt_config, $hcms_charset, $hcms_lang;

  if ($show != "" && strlen ($show) < 2400 && $lang != "")
  {
    // check mobile setting
    if (!empty ($_SESSION['hcms_mobile'])) $width = "90%";
    
    // add unit if not set
    if (is_int ($width)) $width = $width."px";
    if (is_int ($height)) $height = $height."px";
    
    // define unique name for close button
    $close_id = uniqid();
    
    return "
  <div id=\"".$id."\" class=\"hcmsMessage\" style=\"".$style." width:".$width."; height:".$height."; z-index:9999; padding:0; margin:5px; visibility:visible;\">
    <table style=\"width:100%; height:100%; padding:0; border:0; border-spacing:0; border-collapse:collapse;\">
      <tr>
        <td style=\"text-align:left; vertical-align:top; padding:3px; margin:0;\">
          <div id=\"message_text\">".$show."</div>
        </td>
        <td style=\"width:22px; text-align:right; vertical-align:top; padding:3px; margin:0;\">
          <img name=\"close_".$close_id."\" src=\"".getthemelocation()."img/button_close.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['close'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['close'][$lang], $hcms_charset, $lang)."\" onMouseOut=\"hcms_swapImgRestore();\" onMouseOver=\"hcms_swapImage('close_".$close_id."','','".getthemelocation()."img/button_close_over.gif',1);\" onClick=\"hcms_showHideLayers('".$id."','','hide');\" />
        </td>
      <tr>
    </table>
  </div>\n";
  }
  else return false;
}

// --------------------------------------- showinfopage -------------------------------------------
// function: showinfopage ()
// input: message, language code (optional), on load JS events (optional)
// output: message on html info page / false on error

// description:
// Returns a full html info page

function showinfopage ($show, $lang="en", $onload="")
{
  global $mgmt_config, $hcms_charset, $hcms_lang_codepage, $hcms_lang;
      
  if ($show != "" && strlen ($show) < 2400 && $lang != "")
  {    
    return "<!DOCTYPE html>
<html>
  <head>
    <title>hyperCMS</title>
    <meta charset=\"".getcodepage ($lang)."\" />
    <link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />
  </head>  
  <body class=\"hcmsWorkplaceGeneric\" onload=\"".$onload."\">
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
// input: message, language code (optional), additional style definitions  of div-layer (optional), ID of div-layer (optional)
// output: message in div layer / false on error

// description:
// Returns the infobox as long as it has not been closed. Saves the close event in localstorage of browser.

function showinfobox ($show, $lang="en", $style="", $id="hcms_infoboxLayer")
{
  global $mgmt_config, $hcms_charset, $hcms_lang_codepage, $hcms_lang;

  if (!empty ($mgmt_config['showinfobox']) && $show != "" && strlen ($show) < 2400 && $lang != "" && $id != "")
  {
    // define unique name for close button
    $close_id = uniqid();
    
    return "
  <div id=\"".$id."\" class=\"hcmsInfoBox\" style=\"display:none; ".$style."\">
    <table style=\"width:100%; padding:0; border:0; border-spacing:0; border-collapse:collapse;\">
      <tr>
        <td style=\"text-align:left; vertical-align:top; padding:1px; margin:0;\">
          ".$show."
        </td>
        <td style=\"width:22px; text-align:right; vertical-align:top; padding:0; margin:0;\">
          <img name=\"close_".$close_id."\" src=\"".getthemelocation()."img/button_close.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['close'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['close'][$lang], $hcms_charset, $lang)."\" onMouseOut=\"hcms_swapImgRestore();\" onMouseOver=\"hcms_swapImage('close_".$close_id."','','".getthemelocation()."img/button_close_over.gif',1);\" onClick=\"localStorage.setItem('".$id."','no'); hcms_showHideLayers('".$id."','','hide');\" />
        </td>
      <tr>
    </table>
  </div>
  <script type=\"text/javascript\">
  var hcms_showinfobox = localStorage.getItem('".$id."') || 'yes';
  if (hcms_showinfobox=='yes') document.getElementById('".$id."').style.display='inline';
  else document.getElementById('".$id."').style.display='none';
  </script>\n";
  }
  else return false;
}

// --------------------------------------- showsharelinks -------------------------------------------
// function: showsharelinks ()
// input: link to share, language code (optional), additional style definitions of div-layer (optional), ID of div-layer (optional)
// output: message in div layer / false on error

// description:
// Returns the presenation of share links of social media platforms

function showsharelinks ($link, $lang="en", $style="", $id="hcms_shareLayer")
{
  global $mgmt_config, $hcms_charset, $hcms_lang_codepage, $hcms_lang;
     
  if (is_dir ($mgmt_config['abs_path_cms']."connector/") && $link != "" && $lang != "" && $id != "")
  {
    return "
  <div id=\"".$id."\" class=\"hcmsInfoBox\" style=\"".$style."\">".
    getescapedtext ($hcms_lang['share'][$lang])."<br />
    <img src=\"".getthemelocation()."img/icon_facebook.png\" title=\"Facebook\" class=\"hcmsButton\" onclick=\"if (hcms_sharelinkFacebook('".$link."') == false) alert('".getescapedtext ($hcms_lang['required-input-is-missing'][$lang])."');\" /><br />
    <img src=\"".getthemelocation()."img/icon_twitter.png\" title=\"Twitter\" class=\"hcmsButton\" onclick=\"if (hcms_sharelinkTwitter('".$link."', hcms_getcontentByName('textu_Description')) == false) alert('".getescapedtext ($hcms_lang['required-input-is-missing'][$lang])."');\" /><br />
    <img src=\"".getthemelocation()."img/icon_linkedin.png\" title=\"LinkedIn\" class=\"hcmsButton\" onclick=\"if (hcms_sharelinkLinkedin('".$link."', hcms_getcontentByName('textu_Title'), hcms_getcontentByName('textu_Description'), hcms_getcontentByName('Creator')) == false) alert('".getescapedtext ($hcms_lang['required-input-is-missing'][$lang])."');\" /><br />
    <img src=\"".getthemelocation()."img/icon_pinterest.png\" title=\"Pinterest\" class=\"hcmsButton\" onclick=\"if (hcms_sharelinkPinterest('".$link."', hcms_getcontentByName('textu_Title'), hcms_getcontentByName('textu_Description')) == false) alert('".getescapedtext ($hcms_lang['required-input-is-missing'][$lang])."');\" /><br />
    <img src=\"".getthemelocation()."img/icon_googleplus.png\" title=\"Google+\" class=\"hcmsButton\" onclick=\"if (hcms_sharelinkGooglePlus('".$link."') == false) alert('".getescapedtext ($hcms_lang['required-input-is-missing'][$lang])."');\" /><br />
  </div>\n";
  }
  else return false;
}

// --------------------------------------- showmetadata -------------------------------------------
// function: showmetadata ()
// input: meta data as array, hierarchy level, CSS-class with background-color for headlines (optional)
// output: result as HTML unordered list / false on error

function showmetadata ($data, $lang="en", $class_headline="hcmsRowData2")
{
  global $mgmt_config, $hcms_charset, $hcms_lang_codepage, $hcms_lang;
  
  if (is_array ($data))
  {
    // XMP always using UTF-8 so should any other XML-based format
    $hcms_charset_source = "UTF-8";
    $hcms_charset_dest = getcodepage ($lang);

    $result = "<ul class=\"hcmsStructuredList\">\n";
  
    foreach ($data as $key => $value)
    {
      if (is_array ($value))
      {        
        $subresult = showmetadata ($value);

        // html encode string
        $key = html_encode (strip_tags ($key), $hcms_charset_source);

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
        $key = html_encode (strip_tags ($key), $hcms_charset_source);
        // html encode string
        $value = html_encode (strip_tags ($value), $hcms_charset_source);
        
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
  global $mgmt_config, $hcms_charset, $hcms_lang, $lang;

  $location = deconvertpath ($location, "file");

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && is_file ($location.$page))
  {
    $filetime = "";
    $filecount = 0;
    $filesize = 0;
    
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
    <tr><td>".getescapedtext ($hcms_lang['modified'][$lang], $hcms_charset, $lang).": </td><td class=\"hcmsHeadlineTiny\">".$filetime."</td></tr>\n";
    if (!empty ($filesize) && $filesize > 0) $mediaview .= "<tr><td valign=\"top\">".getescapedtext ($hcms_lang['file-size'][$lang], $hcms_charset, $lang).": </td><td class=\"hcmsHeadlineTiny\" valign=\"top\">".$filesize."</td></tr>\n";
    if (!empty ($filecount) && $filecount > 1) $mediaview .= "<tr><td valign=\"top\">".getescapedtext ($hcms_lang['number-of-files'][$lang], $hcms_charset, $lang).": </td><td class=\"hcmsHeadlineTiny\" valign=\"top\">".$filecount."</td></tr>\n";
    $mediaview .= "</table>\n";
    
    return $mediaview;
  }
  else return false;
}

// --------------------------------------- showmedia -----------------------------------------------
// function: showmedia ()
// input: mediafile (publication/filename), name of mediafile for display, view type [template,media_only,preview,preview_download,preview_no_rendering], ID of the HTML media tag,
//        width in px (optional), height in px (optional), CSS class (optional)
// output: html presentation of any media asset / false

// description:
// This function requires site, location and cat to be set as global variable in order to validate the access permission of the user

function showmedia ($mediafile, $medianame, $viewtype, $id="", $width="", $height="", $class="hcmsImageItem")
{
  // $mgmt_imageoptions is used for image rendering (in case the format requires the rename of the object file extension)	 
  global $mgmt_config, $mgmt_mediapreview, $mgmt_mediaoptions, $mgmt_imagepreview, $mgmt_docconvert, $hcms_charset, $hcms_lang_codepage, $hcms_lang, $lang,
         $site, $location, $cat, $page, $user, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $setlocalpermission, $mgmt_imageoptions, $is_mobile;
  
  // Path to PDF.JS and Google Docs
  $pdfjs_path = $mgmt_config['url_path_cms']."javascript/pdfpreview/web/viewer.html?file=";
  $gdocs_path = "https://docs.google.com/viewer?url=";

  require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

  // define flash media type (only executables)
  $swf_ext = ".swf.dcr";

  // define document type extensions that are convertable into pdf or which the document viewer (google doc viewer) can display
  $doc_ext = ".pages.pdf.doc.docx.ppt.pptx.xls.xlsx.odt.ods.odp";
  
  // set html media tag ID if not set
  if (empty ($id)) $id = "media_".uniqid(); 
  
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

  // no media file
  if (@substr_count (strtolower ($mediafile), "null_media.gif") > 0 || $mediafile == "")
  {
    return "";
  }
  // continue with media file
  elseif ($mediafile != "" && valid_publicationname ($site))
  {
    $is_config = false;
    $is_version = false;

    // display name
    $medianame = getobject ($medianame);
    if ($medianame == "") $medianame = getobject ($mediafile);

    // get file info
    // if config file
    if (strpos ($mediafile, ".config.") > 0)
    {
      $is_config = true;
      $file_info = getfileinfo ($site, str_replace ("config.", "", $mediafile), "comp");
    }
    // if version file
    elseif (strpos ("_".substr ($mediafile, strrpos ($mediafile, ".")), ".v_") == 1)
    {
      $is_version = true;
      $file_info = getfileinfo ($site, substr ($mediafile, 0, strrpos ($mediafile, ".")), "comp");
    }
    // if media file
    else $file_info = getfileinfo ($site, $mediafile, "comp");

    // file extension of original file
    $file_info['orig_ext'] = $file_info['ext'];

    // define media file root directory for template media files
    if ($viewtype == "template" && valid_publicationname ($site))
    {
      $media_root = $mgmt_config['abs_path_tplmedia'].$site."/";
      
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
    // define media file root directory for assets
    elseif (valid_publicationname ($site))
    {
      // location of media file (can be also outside repository if exported)
      $media_root = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";
      // thumbnail file is always in repository
      $thumb_root = getmedialocation ($site, "dummy.".$mediafile, "abs_path_media").$site."/";

      // get container ID
      $container_id = getmediacontainerid ($mediafile);

      if ($viewtype != "media_only")
      {
        // locked by user
        $usedby_array = getcontainername ($container_id.".xml");
  
        if (!empty ($usedby_array['user'])) $usedby = $usedby_array['user'];
        else $usedby = "";
  
        // load container
        if (!empty ($usedby) && $usedby != $user)
        {
          $contentdata = loadcontainer ($container_id.".xml.wrk.@".$usedby, "version", $usedby);
        }
        else $contentdata = loadcontainer ($container_id, "work", $user);
      
        // extract information from container
        $owner = getcontent ($contentdata, "<contentuser>");
        $date_published = getcontent ($contentdata, "<contentpublished>");
      }

      // get media file information from database
      if ($mgmt_config['db_connect_rdbms'] != "" && !$is_version)
      {
        $media_info = rdbms_getmedia ($container_id, true);
        
        $mediafiletime = date ("Y-m-d H:i", strtotime ($media_info['date']));
        $mediafilesize = $media_info['filesize'];
        $width_orig = $media_info['width'];
        $height_orig = $media_info['height'];
      }
      // get media file information from media file
      elseif (is_file ($media_root.$mediafile))
      {
        $mediafiletime = date ("Y-m-d H:i", filemtime ($thumb_root.$mediafile));
        
        // prepare media file
        $temp = preparemediafile ($site, $media_root, $mediafile, $user);
        
        if ($temp['result'] && $temp['crypted'])
        {
          $media_root = $temp['templocation'];
          $mediafile = $temp['tempfile'];
        }
        elseif ($temp['restored'])
        {
          $media_root = $temp['location'];
          $mediafile = $temp['file'];
        }
        
        if (filesize ($media_root.$mediafile) > 0) $mediafilesize = round (filesize ($media_root.$mediafile) / 1024, 0);
        else $mediafilesize = 0;
      
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
    
    $mediaview = "";
    $style = "";

    // only show details if user has permissions to edit the file or for template media files
    if ($viewtype == "template" || ($setlocalpermission['root'] == 1 && $setlocalpermission['download'] == 1))
    {
      // --------------------------------------- if version ----------------------------------------
      if ($is_version && $viewtype != "template")
      {
        // define version thumbnail file (append version file extension)
        $mediafile_thumb = $file_info['filename'].".thumb.jpg".strtolower (strrchr ($mediafile_orig, "."));
        
        // prepare media file
        $temp = preparemediafile ($site, $thumb_root, $mediafile_thumb, $user);
        
        if ($temp['result'] && $temp['crypted'])
        {
          $thumb_root = $temp['templocation'];
          $mediafile_thumb = $temp['tempfile'];
        }
        elseif ($temp['restored'])
        {
          $thumb_root = $temp['location'];
          $mediafile_thumb = $temp['file'];
        }
        
        $style = "";
        if ($width > 0) $style .= "width:".intval($width)."px;";
        if ($height > 0) $style .= "height:".intval($height)."px;";
        if ($style != "") $style = "style=\"".$style."\"";

        // use thumbnail if it is valid (larger than 10 bytes)
        if (is_file ($thumb_root.$mediafile_thumb))
        {
          $mediaview .= "
        <table style=\"margin:0; border-spacing:0; border-collapse:collapse;\">
          <tr><td align=\"left\"><img src=\"".createviewlink ($site, $mediafile_thumb)."\" id=\"".$id."\" alt=\"".$medianame."\" title=\"".$medianame."\" class=\"".$class."\" ".$style." /></td></tr>";
          if ($viewtype != "media_only") $mediaview .= "
          <tr><td align=\"middle\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td></tr>";           
        }
        // if no thumbnail/preview exists
        else
        {
          $mediaview .= "
        <table style=\"margin:0; border-spacing:0; border-collapse:collapse;\">
          <tr><td align=\"left\"><img src=\"".getthemelocation()."img/".$file_info['icon_large']."\" id=\"".$id."\" alt=\"".$medianame."\" title=\"".$medianame."\" ".$style." /></td></tr>";
          if ($viewtype != "media_only") $mediaview .= "
          <tr><td align=\"middle\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td></tr>";
        }

        // define html code for download of older file version
        $mediaview .= "
          <tr><td align=\"middle\"><button class=\"hcmsButtonGreen\" onclick=\"location.href='".createviewlink ($site, $mediafile_orig, $medianame, false, "download")."';\">
            ".getescapedtext ($hcms_lang['download-file'][$lang], $hcms_charset, $lang)."
          </button></td></tr>";
          
        $mediaview .= "</table>\n";
      }
      // ---------------------------------------- if document --------------------------------------
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
          // if original file is a pdf
          if (substr_count (".pdf", $file_info['orig_ext']) == 1) 
          {
            // using pdfjs with orig. file via iframe
            $doc_link = cleandomain (createviewlink ($site, $mediafile_orig, $medianame, true));
            $mediaview .= "<iframe src=\"".$pdfjs_path.urlencode($doc_link)."\" ".$style." id=\"".$id."\" style=\"border:none;\"></iframe><br />\n";
          }
          else
          {
            $mediafile_thumb = $medianame_thumb = $file_info['filename'].".thumb.pdf";
            
            // document thumb file exists in media repository
            if (is_file ($thumb_root.$mediafile_thumb) || is_cloudobject ($thumb_root.$mediafile_thumb)) 
            {
              $thumb_pdf_exists = true;
            }
            // sometimes libre office (UNOCONV) takes long time to convert to PDF and function createdocument is not able to rename the file from .pdf to .thumb.pdf  
            elseif (is_file ($thumb_root.$file_info['filename'].".pdf") || is_cloudobject ($thumb_root.$file_info['filename'].".pdf"))
            {
              rename ($thumb_root.$file_info['filename'].".pdf", $thumb_root.$file_info['filename'].".thumb.pdf");
              if (function_exists ("renamecloudobject")) renamecloudobject ($site, $thumb_root, $file_info['filename'].".pdf", $file_info['filename'].".thumb.pdf", $user);
              $thumb_pdf_exists = true;
            }
            else $thumb_pdf_exists = false;
            
            // thumb pdf exsists
            if ($thumb_pdf_exists != false)
            {
              // using pdfjs with thumbnail file via iframe
              $doc_link = cleandomain (createviewlink ($site, $mediafile_thumb, $medianame_thumb, true));
              $mediaview .= "<iframe src=\"".$pdfjs_path.urlencode($doc_link)."\" ".$style." id=\"".$id."\" style=\"border:none;\"></iframe><br />\n";
            }
            // thumb pdf does not exsist
            elseif ($thumb_pdf_exists == false)
            {
              // using original file and wrapper to start conversion in the background
              $doc_link = createviewlink ($site, $mediafile, $medianame_thumb)."&type=pdf&ts=".time();

              // show standard file icon
              if (!empty ($file_info['icon_large'])) $mediaview .= "<div style=\"width:".$width."px; text-align:center;\"><img src=\"".getthemelocation()."img/".$file_info['icon_large']."\" ".$id." alt=\"".$medianame."\" title=\"".$medianame."\" /></div>\n";
              
              // use AJAX service to start conversion of media file to pdf format for preview
              $mediaview .= "<script type=\"text/javascript\">hcms_ajaxService('".$doc_link."')</script>\n";
            }
            // using Google Docs if UNOCONV was not able to convert into pdf and no standard file-icon exists
            else
            {
              $doc_link = createviewlink ($site, $mediafile_orig, $medianame, true);
              $mediaview .= "<iframe src=\"".$gdocs_path.urlencode($doc_link)."&embedded=true\" ".$style." id=\"".$id."\" style=\"border:none;\"></iframe><br />\n";
            }
          }
        }
        else
        {
          // Not compatible Browser - using google docs
          $doc_link = createviewlink ($site, $mediafile_orig, $medianame, true);
          $mediaview .= "<iframe src=\"".$gdocs_path.urlencode($doc_link)."&embedded=true\" ".$style." id=\"".$id."\" style=\"border:none;\"></iframe><br />\n";
        }
     
        if ($viewtype != "media_only") $mediaview .= "<div style=\"padding:5px 0px 8px 0px; width:".$width."px; text-align:center;\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</div>";
      }
      // ----------------------------------- if image ------------------------------------- 
      elseif ($file_info['ext'] != "" && is_image ($file_info['ext']))
      {
        // media size
        $style = "";
      
        if ($viewtype != "template")
        {
          $thumbfile = $file_info['filename'].".thumb.jpg";
          
          // prepare media file
          $temp = preparemediafile ($site, $thumb_root, $thumbfile, $user);
          
          if ($temp['result'] && $temp['crypted'])
          {
            $thumb_root = $temp['templocation'];
            $thumbfile = $temp['tempfile'];
          }
          elseif ($temp['restored'])
          {
            $thumb_root = $temp['location'];
            $thumbfile = $temp['file'];
          }
          
          // use thumbnail if it is valid
          if (is_file ($thumb_root.$thumbfile))
          {
            // get thumbnail image information
            $thumb_size = @getimagesize ($thumb_root.$thumbfile);
            if (!empty ($thumb_size[0]) && !empty ($thumb_size[1])) $mediaratio = $thumb_size[0] / $thumb_size[1];

            // set width or height if not provided as input
            if (empty ($width) && empty ($height) && !empty ($thumb_size[0]))
            {
              $width = round (($thumb_size[0] * 1.5), 0);
            }
            
            // calculate width or height
            if ($width > 0 && empty ($height))
            {
              $height = round (($width / $mediaratio), 0);
            }
            elseif (empty ($width) && $height > 0)
            {
              $width = round (($height / $mediaratio), 0);
            }

            // new image size cant exceed the original image size
            $newsize = mediasize2frame ($width_orig, $height_orig, $width, $height, true);
            
            if (is_array ($newsize))
            {
              $width = $newsize['width'];
              $height = $newsize['height'];
            }
            // if no media size is available
            else 
            {
              $widht = "";
              $height = "";
            }

            // create new image for annotations (only if annotations are enabled and image conversion software and permissions are given)
            $annotationname = $file_info['filename'].'.annotation.jpg';
            
            if (
                 $viewtype == "preview" &&
                 !empty ($mediaratio) && ($thumb_size[0] >= 180 || $thumb_size[1] >= 180) && 
                 !empty ($mgmt_config['annotation']) && 
                 (!is_file ($thumb_root.$annotationname) || filemtime ($thumb_root.$annotationname) < filemtime ($thumb_root.$thumbfile)) && 
                 is_supported ($mgmt_imagepreview, $file_info['orig_ext']) && 
                 $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1
               )
            {
              $maxmediasize = 540;
              
              // set width and height for annotation image
              if ($mediaratio >= 1)
              {
                $width = $maxmediasize;
                $height = round (($width / $mediaratio), 0);
              }
              elseif ($mediaratio < 1)
              {
                $height = $maxmediasize;
                $width = round (($height * $mediaratio), 0);
              }
              
              if (is_array ($mgmt_imageoptions))
              {
                // define image format
                $mgmt_imageoptions['.jpg.jpeg']['annotation'] = '-s '.$width.'x'.$height.' -f jpg';

                // create new file for annotations
                $result = createmedia ($site, $thumb_root, $thumb_root, $mediafile_orig, 'jpg', 'annotation', true);

                if ($result) $mediafile = $result;
              }
            }

            // if thumbnail file is smaller than the defined size of a thumbnail due to a smaller original image
            if (!empty ($mediaratio) && $thumb_size[0] < 180 && $thumb_size[1] < 180)
            {
              $width = $thumb_size[0];
              $height = $thumb_size[1];
              $mediafile = $thumbfile;
            }
            // generate a new image file if the new image size is greater than 150% of the width or height of the thumbnail
            elseif (!empty ($mediaratio) && ($width > 0 && $thumb_size[0] * 1.5 < $width) && ($height > 0 && $thumb_size[1] * 1.5 < $height) && is_supported ($mgmt_imagepreview, $file_info['orig_ext']))
            {
              // define parameters for view-images
              $viewfolder = $mgmt_config['abs_path_temp'];
              $newext = 'jpg';
              $typename = 'view.'.$width.'x'.$height;
                        
              // predict the name to check if the file does exist and maybe is actual
              $newname = $file_info['filename'].".".$typename.'.'.$newext;

              // generate new file only when another one wasn't already created or is outdated (use thumbnail since the date of the decrypted temporary file is not representative)
              if (!is_file ($viewfolder.$newname) || @filemtime ($thumb_root.$thumbfile) > @filemtime ($viewfolder.$newname)) 
              {
                if (!empty ($mgmt_imagepreview) && is_array ($mgmt_imagepreview))
                {
                  $mgmt_imageoptions['.jpg.jpeg'][$typename] = '-s '.$width.'x'.$height.' -f '.$newext;
                  
                  // create new temporary thumbnail
                  $result = createmedia ($site, $thumb_root, $viewfolder, $mediafile_orig, $newext, $typename, true);

                  if ($result) $mediafile = $result;
                }
              }
              // we use the existing file
              else $mediafile = $newname;
            }
            // use thumbnail image
            else $mediafile = $thumbfile;
            
            if ($width > 0 && $height > 0) $style = "style=\"width:".intval($width)."px; height:".intval($height)."px;\"";
            
            // set width and height of media file as file-parameter
            $mediaview .= "
            <!-- hyperCMS:width file=\"".$width_orig."\" -->
            <!-- hyperCMS:height file=\"".$height_orig."\" -->";
            
            // get file extension of new file (preview file)
            $file_info['ext'] = strtolower (strrchr ($mediafile, "."));

            $mediaview .= "
          <table style=\"margin:0; border-spacing:0; border-collapse:collapse;\">
            <tr><td align=\"left\">";
            
            if (($thumb_size[0] >= 180 || $thumb_size[1] >= 180) && !empty ($mgmt_config['annotation']) && is_file ($thumb_root.$annotationname) && $viewtype == "preview" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
            {
              $mediaview .= "<div style=\"margin-top:30px\"><div id=\"annotation\" style=\"position:relative\" class=\"".$class."\"></div></div>";
            }
            else
            {
              $mediaview .= "<img src=\"".createviewlink ($site, $mediafile, $medianame, true)."\" id=\"".$id."\" alt=\"".$medianame."\" title=\"".$medianame."\" class=\"".$class."\" ".$style."/>";
            }
            
            $mediaview .= "</td></tr>";
            
            if ($viewtype != "media_only") $mediaview .= "
            <tr><td align=\"middle\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td></tr>";           
          }
          // if no thumbnail/preview exists
          else
          {
            $mediaview .= "
          <table style=\"margin:0; border-spacing:0; border-collapse:collapse;\">
            <tr><td align=\"left\"><img src=\"".getthemelocation()."img/".$file_info['icon_large']."\" id=\"".$id."\" alt=\"".$medianame."\" title=\"".$medianame."\" /></td></tr>";
            
            if ($viewtype != "media_only") $mediaview .= "
            <tr><td align=\"middle\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td></tr>";
          }
        }      
        // if template media view
        elseif (is_file ($media_root.$mediafile))
        {
          $mediaview .= "
        <table style=\"margin:0; border-spacing:0; border-collapse:collapse;\">
          <tr><td align=\"left\"><img src=\"".$mgmt_config['url_path_tplmedia'].$site."/".$mediafile."\" id=\"".$id."\" alt=\"".$mediafile."\" title=\"".$mediafile."\" class=\"".$class."\" ".$style."/></td></tr>";
          
          if ($viewtype != "media_only") $mediaview .= "
          <tr><td align=\"middle\" class=\"hcmsHeadlineTiny\">".showshorttext($mediafile, 40, false)."</td></tr>";
        }      
  
        // image rendering (only if image conversion software and permissions are given)
        if ($viewtype == "preview" && is_supported ($mgmt_imagepreview, $file_info['orig_ext']) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1) 
        {
          // add image rendering button
          $mediaview .= "<tr><td align=middle><input name=\"media_rendering\" class=\"hcmsButtonGreen\" type=\"button\" value=\"".getescapedtext ($hcms_lang['edit-image'][$lang], $hcms_charset, $lang)."\" onclick=\"if (typeof setSaveType == 'function') setSaveType('imagerendering_so', '', 'post');\" /></td></tr>\n";
        }
        
        $mediaview .= "</table>\n";

        // embed annotation script
        if (!empty ($mediaratio) && ($thumb_size[0] >= 180 || $thumb_size[1] >= 180) && !empty ($mgmt_config['annotation']) && is_file ($thumb_root.$annotationname) && $viewtype == "preview" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
        {
          $mediaview .= "
  <script type=\"text/javascript\" src=\"".$mgmt_config['url_path_cms']."javascript/annotate/annotate.js\"></script>
	<script>
    function setAnnoationButtons ()
    {
      document.getElementById('annotationRectangle').src = '".getthemelocation()."img/button_rectangle.gif';
      document.getElementById('annotationRectangle').title = hcms_entity_decode('".getescapedtext ($hcms_lang['rectangle'][$lang], $hcms_charset, $lang)."');
      document.getElementById('annotationRectangle').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['rectangle'][$lang], $hcms_charset, $lang)."');
      
      document.getElementById('annotationCircle').src = '".getthemelocation()."img/button_circle.gif';
      document.getElementById('annotationCircle').title = hcms_entity_decode('".getescapedtext ($hcms_lang['circle'][$lang], $hcms_charset, $lang)."');
      document.getElementById('annotationCircle').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['circle'][$lang], $hcms_charset, $lang)."');
      
      document.getElementById('annotationText').src = '".getthemelocation()."img/button_texttag.gif';
      document.getElementById('annotationText').title = hcms_entity_decode('".getescapedtext ($hcms_lang['text'][$lang], $hcms_charset, $lang)."');
      document.getElementById('annotationText').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['text'][$lang], $hcms_charset, $lang)."');
      
      document.getElementById('annotationArrow').src = '".getthemelocation()."img/button_arrow.gif';
      document.getElementById('annotationArrow').title = hcms_entity_decode('".getescapedtext ($hcms_lang['arrow'][$lang], $hcms_charset, $lang)."');
      document.getElementById('annotationArrow').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['arrow'][$lang], $hcms_charset, $lang)."');
      
      document.getElementById('annotationPen').src = '".getthemelocation()."img/button_pen.gif';
      document.getElementById('annotationPen').title = hcms_entity_decode('".getescapedtext ($hcms_lang['pen'][$lang], $hcms_charset, $lang)."');
      document.getElementById('annotationPen').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['pen'][$lang], $hcms_charset, $lang)."');
      
      document.getElementById('annotationUndo').src = '".getthemelocation()."img/button_history_back.gif';
      document.getElementById('annotationUndo').title = hcms_entity_decode('".getescapedtext ($hcms_lang['undo'][$lang], $hcms_charset, $lang)."');
      document.getElementById('annotationUndo').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['undo'][$lang], $hcms_charset, $lang)."');
      
      document.getElementById('annotationRedo').src = '".getthemelocation()."img/button_history_forward.gif';
      document.getElementById('annotationRedo').title = hcms_entity_decode('".getescapedtext ($hcms_lang['redo'][$lang], $hcms_charset, $lang)."');
      document.getElementById('annotationRedo').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['redo'][$lang], $hcms_charset, $lang)."');
      
      document.getElementById('annotationHelp').src = '".getthemelocation()."img/button_help.gif';
      document.getElementById('annotationHelp').title = hcms_entity_decode('".getescapedtext ($hcms_lang['select-a-tool-in-order-to-add-an-annotation'][$lang], $hcms_charset, $lang)."');
      document.getElementById('annotationHelp').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['select-a-tool-in-order-to-add-an-annotation'][$lang], $hcms_charset, $lang)."');
    }
  
		$(document).ready(function(){
      // set annotaion image file name
      $('#medianame').val('".$annotationname."');
      
      // create annotation image
			$('#annotation').annotate({
				color: 'red',
				bootstrap: false,
				images: ['".createviewlink ($site, $annotationname, $annotationname)."']
      });
      
      // set images for buttons
      if (!hcms_iOS()) setAnnoationButtons();
      else document.getElementById('annotationToolbar').disabled = true;
		});
	</script>
  ";
        }
      }
      // -------------------------------------- if flash --------------------------------------- 
      elseif ($file_info['ext'] != "" && substr_count ($swf_ext.".", $file_info['ext'].".") > 0)
      {
        // new size may exceed the original image size
        $newsize = mediasize2frame ($width_orig, $height_orig, $width, $height, true);
        
        if (is_array ($newsize))
        {
          $width = $newsize['width'];
          $height = $newsize['height'];
        }
        else
        {
          $width = "";
          $heigth = "";
        }
      
        // use provided dimensions
        $style = "";
        if ($width > 0) $style .= " width=\"".$width."\"";
        if ($height > 0) $style .= " height=\"".$height."\"";
        
        $mediaview .= "
      <table style=\"margin:0; border-spacing:0; border-collapse:collapse;\">
        <tr><td align=\"left\">
          <object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0\"".$style.">
            <param name=\"movie\" value=\"".createviewlink ($site, $mediafile_orig, $medianame)."\" />
            <param name=\"quality\" value=\"high\" />
            <embed src=\"".createviewlink ($site, $mediafile_orig, $medianame, true)."\" id=\"".$id."\" quality=\"high\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\" type=\"application/x-shockwave-flash\" ".$style." />
          </object>
        </td></tr>";
        if ($viewtype != "media_only") $mediaview .= "
        <tr><td align=\"middle\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td></tr>
      </table>";
      }
      // --------------------------------------- if audio ----------------------------------------- 
      elseif ($file_info['ext'] != "" && is_audio ($file_info['ext']))
      {
        // media player config file is given
        if (strpos ($mediafile_orig, ".config.") > 0 && (is_file ($thumb_root.$mediafile_orig) || is_cloudobject ($thumb_root.$mediafile_orig)))
        {
          $config = readmediaplayer_config ($thumb_root, $mediafile_orig);
        }
        // new since version 5.6.3 (config of original-preview file)
        elseif (is_file ($thumb_root.$file_info['filename'].".config.orig") || is_cloudobject ($thumb_root.$file_info['filename'].".config.orig"))
        {
          $config = readmediaplayer_config ($thumb_root, $file_info['filename'].".config.orig");
        }
        // new since version 5.6.3 (config of audioplayer)
        elseif (is_file ($thumb_root.$file_info['filename'].".config.audio") || is_cloudobject ($thumb_root.$file_info['filename'].".config.audio"))
        {
          $config = readmediaplayer_config ($thumb_root, $file_info['filename'].".config.audio");
        }
        // no media config file is available, try to create video thumbnail file
        elseif (is_file ($thumb_root.$mediafile_orig) || is_cloudobject ($thumb_root.$mediafile_orig))
        {
          // create thumbnail audio of original file
          $create_media = createmedia ($site, $thumb_root, $thumb_root, $mediafile_orig, "", "origthumb");
          
          if ($create_media) $config = readmediaplayer_config ($thumb_root, $file_info['filename'].".config.orig");
        }

        // use config values
        if (!empty ($config['width']) && $config['width'] > 0 && !empty ($config['height']) && $config['height'] > 0)
        {
          $mediawidth = $config['width'];
          $mediaheight = $config['height'];
          $mediaratio = $config['width'] / $config['height'];
        }
        // use default values
        else
        {
          $mediawidth = 320;
          $mediaheight = 320;
          $mediaratio = $mediawidth / $mediaheight;
        }
        
        // set width and height of media file as file-parameter
        $mediaview .= "
        <!-- hyperCMS:width file=\"".$mediawidth."\" -->
        <!-- hyperCMS:height file=\"".$mediaheight."\" -->";
        
        // new size may exceed the original image size
        $newsize = mediasize2frame ($mediawidth, $mediaheight, $width, $height, true);
        
        if (is_array ($newsize))
        {
          $mediawidth = $newsize['width'];
          $mediaheight = $newsize['height'];
        }
        
        // add original file if config is empty
        if (empty ($config['mediafiles']) && strpos ($mediafile_orig, ".config.") == 0 && (is_file ($thumb_root.$mediafile_orig) || is_cloudobject ($thumb_root.$mediafile_orig)))
        {
          $config['mediafiles'] = array ($site."/".$mediafile_orig.";".getmimetype ($mediafile_orig));
        }

        // generate player code
        $playercode = showaudioplayer ($site, $config['mediafiles'], $mediawidth, $mediaheight, "", $id, false, false, true, true);
      
        $mediaview .= "
        <table style=\"margin:0; border-spacing:0; border-collapse:collapse;\">
          <tr><td align=\"left\">
            <!-- audio player begin -->
            <div id=\"videoplayer_container\" style=\"display:inline-block; text-align:center;\">
              ".$playercode."
              <div id=\"mediaplayer_segmentbar\" style=\"display:none; width:100%; height:22px; background-color:#808080; text-align:left; margin-bottom:8px;\"></div>";
              
              if ($viewtype != "media_only") $mediaview .= "
              <div style=\"display:block; margin:3px;\">".showshorttext($medianame, 40, false)."</div>\n";
              
        // audio rendering and embedding (requires the JS function 'setSaveType' provided by the template engine)
        if (is_supported ($mgmt_mediapreview, $file_info['orig_ext']) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
        {  
          // edit, embed button
          if ($viewtype == "preview")
          {
            $mediaview .= "
                <input type=\"hidden\" id=\"VTT\" name=\"\" value=\"\" />
                <button type=\"button\" class=\"hcmsButtonGreen\" onclick=\"if (typeof setSaveType == 'function') setSaveType('mediarendering_so', '', 'post');\">".getescapedtext ($hcms_lang['edit-audio-file'][$lang], $hcms_charset, $lang)."</button>&nbsp;
                <button type=\"button\" class=\"hcmsButtonBlue\" onclick=\"if (typeof setSaveType == 'function') setSaveType('mediaplayerconfig_so', '', 'post');\">".getescapedtext ($hcms_lang['embed-audio-file'][$lang], $hcms_charset, $lang)."</button>";
          }
          // cut, embed, options button
          elseif ($viewtype == "preview_download" && valid_locationname ($location) && valid_objectname ($page))
          {
            $mediaview .= "
                <button type=\"button\" id=\"mediaplayer_cut\" class=\"hcmsButtonOrange\" onclick=\"setbreakpoint()\" style=\"display:none;\"><img src=\"".getthemelocation()."img/button_cut.png\" style=\"height:12px;\" /> ".getescapedtext ($hcms_lang['audio-montage'][$lang], $hcms_charset, $lang)."</button>&nbsp;
                <button type=\"button\" id=\"mediaplayer_options\" class=\"hcmsButtonBlue\" onclick=\"document.getElementById('barbutton_0').click();\" style=\"display:none;\">".getescapedtext ($hcms_lang['options'][$lang], $hcms_charset, $lang)."</button>&nbsp;
                <button type=\"button\" id=\"mediaplayer_embed\" class=\"hcmsButtonBlue\" onclick=\"document.location.href='media_playerconfig.php?location=".url_encode($location_esc)."&page=".url_encode($page)."';\">".getescapedtext ($hcms_lang['embed-audio-file'][$lang], $hcms_charset, $lang)."</button>";
          }
        }
        
        $mediaview .= "
            </div>
            <!-- audio player end -->
          </td></tr>
        </table>\n";
      }
      // ---------------------------- if video ---------------------------- 
      elseif ($file_info['ext'] != "" && is_video ($file_info['ext']))
      {
        $config = array();
        
        // media player config file is given
        if (strpos ($mediafile_orig, ".config.") > 0 && (is_file ($thumb_root.$mediafile_orig) || is_cloudobject ($thumb_root.$mediafile_orig)))
        {
          $config = readmediaplayer_config ($thumb_root, $mediafile_orig);
        }
        // get media player config file
        // new since version 5.6.3 (config/preview of original file)
        elseif (is_file ($thumb_root.$file_info['filename'].".config.orig") || is_cloudobject ($thumb_root.$file_info['filename'].".config.orig"))
        {
          $config = readmediaplayer_config ($thumb_root, $file_info['filename'].".config.orig");
          
          // set default width for preview of original video file if no width has been provided
          if (!is_numeric ($width) || $width == 0) $width = 320;
        }
        // new since version 5.5.7 (config of videoplayer)
        elseif (is_file ($thumb_root.$file_info['filename'].".config.video") || is_cloudobject ($thumb_root.$file_info['filename'].".config.video"))
        {
          $config = readmediaplayer_config ($thumb_root, $file_info['filename'].".config.video");
        }
        // old version (only FLV support)
        elseif (is_file ($thumb_root.$file_info['filename'].".config.flv") || is_cloudobject ($thumb_root.$file_info['filename'].".config.flv"))
        {
          $config = readmediaplayer_config ($thumb_root, $file_info['filename'].".config.flv");
        }
        // no media config file is available, try to create video thumbnail file
        elseif (is_file ($thumb_root.$mediafile_orig) || is_cloudobject ($thumb_root.$mediafile_orig))
        {
          // create thumbnail video of original file
          $create_media = createmedia ($site, $thumb_root, $thumb_root, $mediafile_orig, "", "origthumb");
          
          if ($create_media) $config = readmediaplayer_config ($thumb_root, $file_info['filename'].".config.orig");
        }

        // add original file as well if it is an MP4, WebM or OGG/OGV (supported formats by most of the browsers)
        if (!is_array ($config['mediafiles']) || sizeof ($config['mediafiles']) <= 1)
        {
          if (strpos ($mediafile_orig, ".config.") == 0 && substr_count (".mp4.ogg.ogv.webm.flv.", $file_info['orig_ext'].".") > 0 && (is_file ($thumb_root.$mediafile_orig) || is_cloudobject ($thumb_root.$mediafile_orig)))
          {
            $config['mediafiles'][] = $site."/".$mediafile_orig.";".getmimetype ($mediafile_orig);
          }
        }

        // use config values
        if (!empty ($config['width']) && $config['width'] > 0 && !empty ($config['height']) && $config['height'] > 0)
        {
          $mediawidth = $config['width'];
          $mediaheight = $config['height'];
        }
        // use default values
        else
        {
          $mediawidth = 320;
          $mediaheight = 240;
        }

        // set width and height of media file as file-parameter
        $mediaview .= "
        <!-- hyperCMS:width file=\"".$mediawidth."\" -->
        <!-- hyperCMS:height file=\"".$mediaheight."\" -->";
        
        // new size may exceed the original image size
        $newsize = mediasize2frame ($mediawidth, $mediaheight, $width, $height, true);
        
        if (is_array ($newsize))
        {
          $mediawidth = $newsize['width'];
          $mediaheight = $newsize['height'];
        }

        // generate player code
        $playercode = showvideoplayer ($site, @$config['mediafiles'], $mediawidth, $mediaheight, "", $id, "", false, true, false, false, true, false, true);

        $mediaview .= "
        <table style=\"margin:0; border-spacing:0; border-collapse:collapse;\">
          <tr><td align=\"left\">
            <!-- video player begin -->
            <div id=\"videoplayer_container\" style=\"display:inline-block; text-align:center;\">
              ".$playercode."
              <div id=\"mediaplayer_segmentbar\" style=\"display:none; width:100%; height:22px; background-color:#808080; text-align:left; margin-bottom:8px;\"></div>";
              if ($viewtype != "media_only") $mediaview .= "
              <div style=\"display:block; margin:3px;\">".showshorttext($medianame, 40, false)."</div>\n";
              
        // video rendering and embedding (requires the JS function 'setSaveType' provided by the template engine)
        if (is_supported ($mgmt_mediapreview, $file_info['orig_ext']) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
        {  
          // VTT, edit, embed button
          if ($viewtype == "preview")
          {
            $mediaview .= "
                <input type=\"hidden\" id=\"VTT\" name=\"\" value=\"\" />
                <button type=\"button\" class=\"hcmsButtonGreen\" onclick=\"hcms_openVTTeditor('vtt_container');\" />".getescapedtext ($hcms_lang['video-text-track'][$lang], $hcms_charset, $lang)."</button>&nbsp;
                <button type=\"button\" class=\"hcmsButtonGreen\" onclick=\"if (typeof setSaveType == 'function') setSaveType('mediarendering_so', '', 'post');\">".getescapedtext ($hcms_lang['edit-video'][$lang], $hcms_charset, $lang)."</button>&nbsp;
                <button type=\"button\" class=\"hcmsButtonBlue\" onclick=\"if (typeof setSaveType == 'function') setSaveType('mediaplayerconfig_so', '', 'post');\">".getescapedtext ($hcms_lang['embed-video'][$lang], $hcms_charset, $lang)."</button>";
          }
          // embed button
          elseif ($viewtype == "preview_download" && valid_locationname ($location) && valid_objectname ($page))
          {
            $mediaview .= "
                <button type=\"button\" id=\"mediaplayer_cut\" class=\"hcmsButtonOrange\" onclick=\"setbreakpoint()\" style=\"display:none;\"><img src=\"".getthemelocation()."img/button_cut.png\" style=\"height:12px;\" /> ".getescapedtext ($hcms_lang['video-montage'][$lang], $hcms_charset, $lang)."</button>&nbsp;
                <button type=\"button\" id=\"mediaplayer_options\" class=\"hcmsButtonBlue\" onclick=\"document.getElementById('barbutton_0').click();\" style=\"display:none;\">".getescapedtext ($hcms_lang['options'][$lang], $hcms_charset, $lang)."</button>&nbsp;
                <button type=\"button\" id=\"mediaplayer_embed\" class=\"hcmsButtonBlue\" onclick=\"document.location.href='media_playerconfig.php?location=".url_encode($location_esc)."&page=".url_encode($page)."';\">".getescapedtext ($hcms_lang['embed-video'][$lang], $hcms_charset, $lang)."</button>";
          }
        }
        
        $mediaview .= "
            </div>
            <!-- video player end -->";

        // VTT editor
        if (is_supported ($mgmt_mediapreview, $file_info['orig_ext']) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
        {
          if ($viewtype == "preview")
          {
            // load language code index file
            $langcode_array = getlanguageoptions ();
    
            if ($langcode_array != false)
            {
              $lang_select = "
                 <select id=\"vtt_language\" name=\"vtt_language\" style=\"width:318px;\" onchange=\"hcms_changeVTTlanguage()\">
                   <option value=\"\">".getescapedtext ($hcms_lang['language'][$lang], $hcms_charset, $lang)."</option>";
              
              foreach ($langcode_array as $code => $language)
              {
                $lang_select .= "
                    <option value=\"".$code."\">".$language."</option>";
              }
              
              $lang_select .= "
                </select>\n";
            }
          
            $mediaview .= "
          </td></tr>
          <tr><td align=\"left\">
          
            <div id=\"vtt_container\" style=\"display:none; width:592px;\">
              <!-- VTT editor -->
              <div id=\"vtt_create\" class=\"hcmsInfoBox\" style=\"width:100%;\">
                <b>".getescapedtext ($hcms_lang['video-text-track'][$lang], $hcms_charset, $lang)."</b><br />
                <div style=\"float:left; margin:2px 2px 0px 0px;\">".$lang_select."</div>
                <input type=\"hidden\" id=\"vtt_langcode\" name=\"vtt_langcode\" value=\"\" />
                <input type=\"text\" id=\"vtt_start\" name=\"start\" value=\"\" placeholder=\"".getescapedtext ($hcms_lang['start'][$lang], $hcms_charset, $lang)."\" maxlength=\"12\" style=\"float:left; margin:2px 0px 0px 0px; width:90px;\" readonly=\"readonly\" />
                <img src=\"".getthemelocation()."img/button_tpldate.gif\" onclick=\"setVTTtime('vtt_start');\" class=\"hcmsButton hcmsButtonSizeSquare\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['set'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['set'][$lang], $hcms_charset, $lang)."\" />
                <input type=\"text\" id=\"vtt_stop\" name=\"stop\" value=\"\" placeholder=\"".getescapedtext ($hcms_lang['end'][$lang], $hcms_charset, $lang)."\" maxlength=\"12\" style=\"float:left; margin:2px 0px 0px 0px; width:90px;\" readonly=\"readonly\" />
                <img src=\"".getthemelocation()."img/button_tpldate.gif\" onclick=\"setVTTtime('vtt_stop');\" class=\"hcmsButton hcmsButtonSizeSquare\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['set'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['set'][$lang], $hcms_charset, $lang)."\" />
                <input type=\"text\" id=\"vtt_text\" name=\"text\" value=\"\" placeholder=\"".getescapedtext ($hcms_lang['text'][$lang], $hcms_charset, $lang)."\" maxlength=\"400\" style=\"float:left; margin:2px 0px 0px 0px; width:532px;\" />
                <img src=\"".getthemelocation()."img/button_save.gif\" onclick=\"createVTTrecord()\" class=\"hcmsButton hcmsButtonSizeSquare\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['save'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['save'][$lang], $hcms_charset, $lang)."\" />
                <div style=\"clear:both;\"></div>
              </div>
              <div id=\"vtt_records_container\" class=\"hcmsInfoBox\" style=\"margin-top:10px; width:100%; height:200px; overflow:auto;\">
                <div id=\"vtt_header\">
                  <div style=\"float:left; margin:2px 2px 0px 0px; width:64px;\"><b>".getescapedtext ($hcms_lang['start'][$lang], $hcms_charset, $lang)."</b></div>
                  <div style=\"float:left; margin:2px 2px 0px 0px; width:64px;\"><b>".getescapedtext ($hcms_lang['end'][$lang], $hcms_charset, $lang)."</b></div>
                  <div style=\"float:left; margin:2px 2px 0px 0px; width:400px;\"><b>".getescapedtext ($hcms_lang['text'][$lang], $hcms_charset, $lang)."</b></div>
                  <div style=\"clear:both;\"></div>
                </div>
                <div id=\"vtt_records\">
                </div>
              </div>
            </div>

            <script language=\"JavaScript\" type=\"text/javascript\">
            // define delete button for VTT record
            var vtt_buttons = '<img src=\"".getthemelocation()."img/button_delete.gif\" onclick=\"hcms_removeVTTrecord(this)\" class=\"hcmsButton hcmsButtonSizeSquare\" align=\"absmiddle\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $hcms_charset, $lang)."\" />';
            var vtt_confirm = '".getescapedtext ($hcms_lang['copy-tracks-from-previously-selected-language'][$lang], $hcms_charset, $lang)."';

            function createVTTrecord ()
            {
              var result = hcms_createVTTrecord();
            
              if (!result) alert (hcms_entity_decode ('".getescapedtext ($hcms_lang['the-input-is-not-valid'][$lang], $hcms_charset, $lang)."'));
            }
            
            function setVTTtime (id)
            {
              ";
              // if projekktor is used, we need to check for the state beforehand
              if (strtolower ($mgmt_config['videoplayer']) ==  "projekktor") $mediaview .= "
              var player = projekktor('".$id."');
              
              if (player.getState('PLAYING') || player.getState('PAUSED'))
              {
                var time = player.getPosition();
              }
              else
              {
                alert (hcms_entity_decode('".getescapedtext ($hcms_lang['videoplayer-must-be-playing-or-paused-to-set-start-and-end-positions'][$lang], $hcms_charset, $lang)."'));
                return 0;
              }
              ";
              // if VIDEO-JS
              else $mediaview .= "
              var player = videojs(\"".$id."\");
              var time = player.currentTime();
              ";
              $mediaview .= "
              var seconds = Math.floor(time) % 60;
              
              if (seconds > 0)
              {
                var milliseconds = Math.floor((time % seconds) * 1000);
                
                if (milliseconds < 10) milliseconds = \"00\" + milliseconds;
                else if (milliseconds < 100) milliseconds = \"0\" + milliseconds;
                else if (milliseconds > 999) milliseconds = milliseconds.toString().substring(0,3);
              }
              else var milliseconds = \"000\";
              
              var minutes = Math.floor(time / 60) % 60;
              var hours = Math.floor(time / 3600) % 24;
              
              if (hours   < 10) hours = \"0\" + hours;
              if (minutes < 10) minutes = \"0\" + minutes;
              if (seconds < 10) seconds = \"0\" + seconds;
            
              document.getElementById(id).value = hours + ':' + minutes + ':' + seconds + '.' + milliseconds;
            }
            </script>
            
          </td></tr>";
          }
        }     

        $mediaview .= "
      </table>\n";
      }
      // ---------------------------------- if plain/clear text ---------------------------------- 
      elseif ($file_info['ext'] != "" && substr_count (strtolower ($hcms_ext['cleartxt'].$hcms_ext['cms']).".", $file_info['ext'].".") > 0)
      {
        // prepare media file
        $temp = preparemediafile ($site, $media_root, $mediafile, $user);
        
        if ($temp['result'] && $temp['crypted'])
        {
          $media_root = $temp['templocation'];
          $mediafile = $temp['tempfile'];
        }
        elseif ($temp['restored'])
        {
          $media_root = $temp['location'];
          $mediafile = $temp['file'];
        }
      
        if (is_file ($media_root.$mediafile))
        {
          $content = loadfile ($media_root, $mediafile);

          if (!is_utf8 ($content))
          {
            if (is_latin1 ($content)) $content = iconv ("ISO-8859-1", "UTF-8//TRANSLIT", $content);
            else $content = utf8_encode ($content);
          }
          
          // escape special characters
          if ($hcms_lang_codepage[$lang] == "") $hcms_lang_codepage[$lang] = "UTF-8";
          $content = html_encode ($content, "ASCII");
          
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
          
          if ($viewtype == "template") $mediaview .= "<form name=\"editor\" method=\"post\">
          <input type=\"hidden\" name=\"site\" value=\"".$site."\" />
          <input type=\"hidden\" name=\"mediacat\" value=\"tpl\" />
          <input type=\"hidden\" name=\"mediafile\" value=\"".$mediafile."\" />
          <input type=\"hidden\" name=\"save\" value=\"yes\" />
          <input type=\"hidden\" name=\"token\" value=\"".createtoken ($user)."\" />\n";
          
          if ($viewtype == "template") $mediaview .= "<table cellspacing=\"0\" cellpadding=\"0\" style=\"border:1px solid #000000; margin:2px;\">\n";
          else $mediaview .= "<table>\n";
          
          // save button
          if ($viewtype == "template") $mediaview .= "<td align=\"left\"><img onclick=\"document.forms['editor'].submit();\" name=\"save\" src=\"".getthemelocation()."img/button_save.gif\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['save'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['save'][$lang], $hcms_charset, $lang)."\" /></td>\n";
          
          // disable text area
          if ($viewtype == "template") $disabled = "";
          else $disabled = "disabled=\"disabled\"";
          
          $mediaview .= "<tr><td align=\"left\"><textarea name=\"content\" id=\"".$id."\" ".$style." ".$disabled.">".$content."</textarea></td></tr>";
          if ($viewtype != "media_only") $mediaview .= "
          <tr><td align=\"middle\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td></tr>
          </table>\n";
          
          if ($viewtype == "template") $mediaview .= "</form>\n";  
        }
      }
    }

    // ----------------------------- show standard file icon ------------------------------- 
    if ($mediaview == "")
    {
      // use thumbnail if it is valid (larger than 10 bytes)
      if (is_file ($thumb_root.$file_info['filename'].".thumb.jpg") || is_cloudobject ($thumb_root.$file_info['filename'].".thumb.jpg"))
      {
        // thumbnail file
        $mediafile = $file_info['filename'].".thumb.jpg";
        // get file extension of new file (preview file)
        $file_info['ext'] = strtolower (strrchr ($mediafile, "."));
        
        $mediaview .= "
      <table style=\"margin:0; border-spacing:0; border-collapse:collapse;\">
        <tr><td align=left><img src=\"".createviewlink ($site, $mediafile, $medianame, true)."\" id=\"".$id."\" alt=\"".$medianame."\" title=\"".$medianame."\" class=\"".$class."\" /></td></tr>
        <tr><td align=\"middle\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td></tr>\n";           
      }
      // if no thumbnail/preview exists
      else
      {
        $mediaview .= "
      <table style=\"margin:0; border-spacing:0; border-collapse:collapse;\">
        <tr><td align=\"left\"><img src=\"".getthemelocation()."img/".$file_info['icon_large']."\" id=\"".$id."\" alt=\"".$medianame."\" title=\"".$medianame."\" /></td></tr>";
        if ($viewtype != "media_only") $mediaview .= "
        <tr><td align=\"middle\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td></tr>\n";
      }
      
      $mediaview .= "</table>\n";
    }  

    // --------------------- properties of video and audio files (original and thumbnail files) --------------------------
    if ($viewtype != "media_only")
    {
      if (is_array ($mgmt_mediapreview) && $file_info['ext'] != "" && substr_count (strtolower ($hcms_ext['video'].$hcms_ext['audio']).".", $file_info['ext'].".") > 0)
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
        if (substr_count (strtolower ($hcms_ext['video']), $file_info['ext']) > 0) $is_video = true;
        else $is_video = false;
        
        // get video info from config file
        $mediafile_config = substr ($mediafile_orig, 0, strrpos ($mediafile_orig, ".")).".config.orig";
        $videoinfo = readmediaplayer_config ($thumb_root, $mediafile_config);
        
        // get video info from video file for older versions
        if (empty ($videoinfo['version']) || floatval ($videoinfo['version']) < 2.3)
        {
          // prepare media file
          $temp = preparemediafile ($site, $media_root, $mediafile, $user);
          
          if ($temp['result'] && $temp['crypted'])
          {
            $media_root = $temp['templocation'];
            $mediafile = $temp['tempfile'];
          }
          elseif ($temp['restored'])
          {
            $media_root = $temp['location'];
            $mediafile = $temp['file'];
          }
        
          $videoinfo = getvideoinfo ($media_root.$mediafile);
        }
        
        // show the values
        if (is_array ($videoinfo))
        {
          // save duration of original media file in hidden field so it can be accessed for video editing
          $mediaview .= "<input type=\"hidden\" id=\"mediaplayer_duration\" name=\"mediaplayer_duration\" value=\"".$videoinfo['duration']."\" />";
        
          $filesizes['original'] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['filesize'].'&nbsp;&nbsp;&nbsp;</td>';
  
          $dimensions['original'] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['dimension'].'&nbsp;&nbsp;&nbsp;</td>';
  
          $durations['original'] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['duration_no_ms'].'&nbsp;&nbsp;&nbsp;</td>';
  
          $bitrates['original'] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['videobitrate'].'&nbsp;&nbsp;&nbsp;</td>';
  
          $audio_bitrates['original'] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['audiobitrate'].'&nbsp;&nbsp;&nbsp;</td>';
  
          $audio_frequenzies['original'] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['audiofrequenzy'].'&nbsp;&nbsp;&nbsp;</td>';
  
          $audio_channels['original'] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['audiochannels'].'&nbsp;&nbsp;&nbsp;</td>';
          
          $download_link = "top.location.href='".createviewlink ($site, $mediafile_orig, $medianame, false, "download")."'; return false;";
         
          // download button
          if ($viewtype == "preview" || $viewtype == "preview_download") 
          {
            $downloads['original'] = '<td class="hcmsHeadlineTiny" style="text-align:left;"><button class="hcmsButtonBlue" onclick="'.$download_link.'">'.getescapedtext ($hcms_lang['download'][$lang], $hcms_charset, $lang).'</button></td>';
            
            // Youtube upload
            if (!empty ($mgmt_config[$site]['youtube']) && $mgmt_config[$site]['youtube'] == true && is_file ($mgmt_config['abs_path_cms']."connector/youtube/index.php"))
            {		
              $youtube_uploads['original'] = '<td class="hcmsHeadlineTiny" style="text-align:left;"> 
              <button type="button" name="media_youtube" class="hcmsButtonGreen" onclick=\'hcms_openWindow("'.$mgmt_config['url_path_cms'].'connector/youtube/index.php?site='.url_encode($site).'&page='.url_encode($page).'&path='.url_encode($site."/".$mediafile_orig).'&location='.url_encode(getrequest_esc('location')).'","","scrollbars=no,resizable=yes","640","400")\'><img src="'.getthemelocation().'img/button_upload.png" style="height:12px;" /> '.getescapedtext ($hcms_lang['youtube'][$lang], $hcms_charset, $lang).'</button> </td>';
            }
          }
        }
  
        $videos = array ('<th style="text-align:left;">'.getescapedtext ($hcms_lang['original'][$lang], $hcms_charset, $lang).'</th>');
  
        if (!$is_version && ($viewtype == "preview" || $viewtype == "preview_download"))
        {
          // look for available audio and video files 
          foreach ($mgmt_mediaoptions as $media_extension => $media_options)
          {
            if ($media_extension != "" && $media_extension != "thumbnail-video" && $media_extension != "thumbnail-audio")
            {
              // remove dot
              $media_extension = str_replace (".", "", $media_extension);
              
              // get video info from config file
              $mediafile_config = substr ($mediafile_orig, 0, strrpos ($mediafile_orig, ".")).".config.".$media_extension;
      
              // verify if video config file exists
              if (is_file ($thumb_root.$mediafile_config) || is_cloudobject ($thumb_root.$mediafile_config))
              {
                $videoinfo = readmediaplayer_config ($thumb_root, $mediafile_config);
      
                // verify and define video thumbnail file
                // try individual video
                $test_file = $file_info['filename'].".media.".$media_extension;
                
                if (is_file ($thumb_root.$test_file) || is_cloudobject ($thumb_root.$test_file))
                {
                  $video_thumbfile = $test_file;
                }
                else
                {
                  // try use thumbnail video file
                  $test_file = $file_info['filename'].".thumb.".$media_extension;
                  
                  if (is_file ($thumb_root.$test_file) || is_cloudobject ($thumb_root.$test_file))
                  {
                    $video_thumbfile = $test_file;
                  }
                }
      
                // get video info from video file for older versions
                if (!empty ($video_thumbfile) && (empty ($videoinfo['version']) || floatval ($videoinfo['version']) < 2.3))
                {
                  // prepare media file
                  $temp = preparemediafile ($site, $thumb_root, $video_thumbfile, $user);
      
                  if ($temp['result'] && $temp['crypted'])
                  {
                    $thumb_root = $temp['templocation'];
                    $video_thumbfile = $temp['tempfile'];
                  }
                  elseif ($temp['restored'])
                  {
                    $thumb_root = $temp['location'];
                    $video_thumbfile = $temp['file'];
                  }
                
                  $videoinfo = getvideoinfo ($thumb_root.$video_thumbfile);
                }
    
                // show the values
                if (!empty ($video_thumbfile) && is_array ($videoinfo))
                {
                  $videos[$media_extension] = '<th style="text-align:left;">'.$media_extension.'</th>';
                 
                  // define video file name          
                  $video_filename = substr ($medianame, 0, strrpos ($medianame, ".")).".".$media_extension;
                  
                  $filesizes[$media_extension] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['filesize'].'&nbsp;&nbsp;&nbsp;</td>';
      
                  $dimensions[$media_extension] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['dimension'].'&nbsp;&nbsp;&nbsp;</td>';
      
                  $durations[$media_extension] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['duration_no_ms'].'&nbsp;&nbsp;&nbsp;</td>';
      
                  $bitrates[$media_extension] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['videobitrate'].'&nbsp;&nbsp;&nbsp;</td>';
      
                  $audio_bitrates[$media_extension] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['audiobitrate'].'&nbsp;&nbsp;&nbsp;</td>';
      
                  $audio_frequenzies[$media_extension] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['audiofrequenzy'].'&nbsp;&nbsp;&nbsp;</td>';
      
                  $audio_channels[$media_extension] = '<td class="hcmsHeadlineTiny" style="text-align:left; white-space:nowrap;">'.$videoinfo['audiochannels'].'&nbsp;&nbsp;&nbsp;</td>';
      
                  $download_link = "top.location.href='".createviewlink ($site, $video_thumbfile, $video_filename, false, "download")."'; return false;";
                 
                  // download button
                  if ($viewtype == "preview" || $viewtype == "preview_download")
                  {
                    $downloads[$media_extension] = '<td class="hcmsHeadlineTiny" style="text-align:left;"><button class="hcmsButtonBlue" onclick="'.$download_link.'">'.getescapedtext ($hcms_lang['download'][$lang], $hcms_charset, $lang).'</button></td>'; 
                    
                    // Youtube upload
                    if ($mgmt_config[$site]['youtube'] == true && is_file ($mgmt_config['abs_path_cms']."connector/youtube/index.php"))
                    {	
                      $youtube_uploads[$media_extension] = '<td class="hcmsHeadlineTiny" style="text-align:left;"> 
                    <button type="button" name="media_youtube" class="hcmsButtonGreen" onclick=\'hcms_openWindow("'.$mgmt_config['url_path_cms'].'connector/youtube/index.php?site='.url_encode($site).'&page='.url_encode($page).'&path='.url_encode($site."/".$video_thumbfile).'&location='.url_encode(getrequest_esc('location')).'","","scrollbars=no,resizable=yes","640","400")\'><img src="'.getthemelocation().'img/button_upload.png" style="height:12px;" /> '.getescapedtext ($hcms_lang['youtube'][$lang], $hcms_charset, $lang).'</button> </td>';
                    }
                  }
                }
              }
            }
          }
        }
  
        // generate output	    
        if (is_array ($videos)) $mediaview .= '<table style="cellspacing:2px;"><tr><th>&nbsp;</th>'.implode("", $videos).'</tr>';		
        // Filesize
        if (is_array ($filesizes)) $mediaview .= '<tr><td style="text-align:left; white-space:nowrap;">'.getescapedtext ($hcms_lang['file-size'][$lang], $hcms_charset, $lang).'&nbsp;</td>'.implode ("", $filesizes).'</tr>';	    
        // Dimension
        if ($is_video && is_array ($dimensions)) $mediaview .= '<tr><td style="text-align:left; white-space:nowrap;">'.getescapedtext ($hcms_lang['width-x-height'][$lang], $hcms_charset, $lang).'&nbsp;</td>'.implode ("", $dimensions).'</tr>';			    
        // Durations
        if (is_array ($durations)) $mediaview .= '<tr><td style="text-align:left; white-space:nowrap;">'.getescapedtext ($hcms_lang['duration-hhmmss'][$lang], $hcms_charset, $lang).'&nbsp;</td>'.implode ("", $durations).'</tr>';		
        // Bitrate
        if ($is_video && is_array ($bitrates)) $mediaview .= '<tr><td style="text-align:left; white-space:nowrap;">'.getescapedtext ($hcms_lang['video-bitrate'][$lang], $hcms_charset, $lang).'&nbsp;</td>'.implode ("", $bitrates).'</tr>';
        // Audio bitrate
        if (is_array ($audio_bitrates)) $mediaview .= '<tr><td style="text-align:left; white-space:nowrap;">'.getescapedtext ($hcms_lang['audio-bitrate'][$lang], $hcms_charset, $lang).'&nbsp;</td>'.implode ("", $audio_bitrates).'</tr>';
        // Audio frequenzy
        if (is_array ($audio_frequenzies)) $mediaview .= '<tr><td style="text-align:left; white-space:nowrap;">'.getescapedtext ($hcms_lang['audio-frequenzy'][$lang], $hcms_charset, $lang).'&nbsp;</td>'.implode ("", $audio_frequenzies).'</tr>';
        // Audio frequenzy
        if (is_array ($audio_channels)) $mediaview .= '<tr><td style="text-align:left; white-space:nowrap;">'.getescapedtext ($hcms_lang['audio-channels'][$lang], $hcms_charset, $lang).'&nbsp;</td>'.implode ("", $audio_channels).'</tr>';
        // Download
        if (is_array ($downloads) && sizeof ($downloads) > 0) $mediaview .= '<tr><td>&nbsp;</td>'.implode ("", $downloads).'</tr>';
        // Youtube
        if (!empty ($mgmt_config[$site]['youtube']) && $mgmt_config[$site]['youtube'] == true && is_file ($mgmt_config['abs_path_cms']."connector/youtube/index.php"))
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
        $col_width = "width:120px; ";
        
        $mediaview .= "    <table>";
        if (!empty ($owner[0])) $mediaview .= "
        <tr>
        <td style=\"".$col_width."text-align:left; white-space:nowrap;\">".getescapedtext ($hcms_lang['owner'][$lang], $hcms_charset, $lang).": </td>
        <td class=\"hcmsHeadlineTiny\" style=\"text-align:left; white-space:nowrap;\">".$owner[0]."</td>
        </tr>";
        $mediaview .= "
        <tr>
        <td style=\"".$col_width."text-align:left; white-space:nowrap;\">".getescapedtext ($hcms_lang['modified'][$lang], $hcms_charset, $lang).": </td>
        <td class=\"hcmsHeadlineTiny\" style=\"text-align:left; white-space:nowrap;\">".$mediafiletime."</td>
        </tr>";
        if (!empty ($date_published[0])) $mediaview .= "
        <tr>
        <td style=\"".$col_width."text-align:left; white-space:nowrap;\">".getescapedtext ($hcms_lang['published'][$lang], $hcms_charset, $lang).": </td>
        <td class=\"hcmsHeadlineTiny\" style=\"text-align:left; white-space:nowrap;\">".$date_published[0]."</td>
        </tr>";
        if (!empty ($date_delete)) $mediaview .= "
        <tr>
        <td style=\"".$col_width."text-align:left; white-space:nowrap;\">".getescapedtext ($hcms_lang['will-be-removed'][$lang], $hcms_charset, $lang).": </td>
        <td class=\"hcmsHeadlineTiny\" style=\"text-align:left; white-space:nowrap;\">".$date_delete."</td>
        </tr>";
        $mediaview .= "
        <tr>
        <td style=\"".$col_width."text-align:left; white-space:nowrap;\">".getescapedtext ($hcms_lang['file-size'][$lang], $hcms_charset, $lang).": </td>
        <td class=\"hcmsHeadlineTiny\" style=\"text-align:left; white-space:nowrap;\">".$mediafilesize."</td>
        </tr>\n";
  
        if (!empty ($width_orig) && !empty ($height_orig))
        {
          // size in pixel of media file
          $mediaview .= "
        <tr>
        <td style=\"".$col_width."text-align:left; white-space:nowrap;\">".getescapedtext ($hcms_lang['size'][$lang], $hcms_charset, $lang).": </td>
        <td class=\"hcmsHeadlineTiny\" style=\"text-align:left; white-space:nowrap;\">".$width_orig."x".$height_orig." px</td>
        </tr>\n";
        
          // size in cm
          $mediaview .= "
        <tr>
        <td style=\"".$col_width."text-align:left; white-space:nowrap;\">".getescapedtext ($hcms_lang['size'][$lang], $hcms_charset, $lang)." (72 dpi): </td>
        <td class=\"hcmsHeadlineTiny\">".round(($width_orig / 72 * 2.54), 1)."x".round(($height_orig / 72 * 2.54), 1)." cm, ".round(($width_orig / 72), 1)."x".round(($height_orig / 72), 1)." inch</td>
        </tr>\n";
        
          // size in inch
          $mediaview .= "
        <tr>
        <td style=\"".$col_width."text-align:left; white-space:nowrap;\">".getescapedtext ($hcms_lang['size'][$lang], $hcms_charset, $lang)." (300 dpi): </td>
        <td class=\"hcmsHeadlineTiny\">".round(($width_orig / 300 * 2.54), 1)."x".round(($height_orig / 300 * 2.54), 1)." cm, ".round(($width_orig / 300), 1)."x".round(($height_orig / 300), 1)." inch</td>
        </tr>\n";
        }
        
        $mediaview .= "    </table>";
      }
    }

    return $mediaview;
  }
  else return false;
}

// --------------------------------------- showcompexplorer -------------------------------------------
// function: showcompexplorer ()
// input: publication name, current explorer location, object location (optional), object name (optional), 
//        component category [single,multi,media] (optional), search expression (optional), search format [object,document,image,video,audio] (optional), 
//        media-type [audio,video,text,flash,image,compressed,binary] (optional), 
//        callback of CKEditor (optional), saclingfactor for images (optional)
// output: explorer with search / false on error

// description:
// Creates component explorer including the search form

function showcompexplorer ($site, $dir, $location_esc="", $page="", $compcat="multi", $search_expression="", $search_format="", $mediatype="", $lang="en", $callback="", $scalingfactor="1")
{
  global $user, $mgmt_config, $siteaccess, $pageaccess, $compaccess, $rootpermission, $globalpermission, $localpermission, $hiddenfolder, $html5file, $temp_complocation, $hcms_charset, $hcms_lang;
  
  if (valid_publicationname ($site) && (valid_locationname ($dir) || $dir == ""))
  {
    // load file extension defintions
    require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

    // convert location
    $dir = deconvertpath ($dir, "file");
    $location = deconvertpath ($location_esc, "file");
    
    // local access permissions
    $ownergroup = accesspermission ($site, $dir, "comp");
    $setlocalpermission = setlocalpermission ($site, $ownergroup, "comp");
 
    // load publication inheritance setting
    $inherit_db = inherit_db_read ();
    $parent_array = inherit_db_getparent ($inherit_db, $site);
    
    // get location in component structure from session
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
    
    // if not configured as DAM, define root location if no dir was provided
    if (!$mgmt_config[$site]['dam'] && $dir == "")
    {
      if ($mgmt_config[$site]['inherit_comp'] == false || $parent_array == false)
      {
        $dir = $mgmt_config['abs_path_comp'].$site."/";
      }
      elseif ($mgmt_config[$site]['inherit_comp'] == true)
      {
        $dir = $mgmt_config['abs_path_comp'];
      }
    }
    // if DAM use compaccess
    elseif ($mgmt_config[$site]['dam'] && ($setlocalpermission['root'] != 1 || $dir == ""))
    {
      $comp_entry_dir = array();
      
      if (!empty ($compaccess[$site]))
      {
        foreach ($compaccess[$site] as $group => $value)
        {  
          if ($localpermission[$site][$group]['component'] == 1 && $value != "")
          { 
            // create path array
            $path_array = link_db_getobject ($value);
            
            foreach ($path_array as $value)
            {
              // path must be inside the location, avoid double entries
              if ($value != "" && substr ($value, 0, strlen ($dir)) == $dir)
              {
                if (substr ($value, 0, -7) != ".folder") $value = $value.".folder";
                
                $comp_entry_dir[] = convertpath ($site, $value, "comp");
              }               
            }  
          }
        }
        
        // set dir if user has access to component root folder
        if (sizeof ($comp_entry_dir) == 1 && $comp_entry_dir[0] == "%comp%/".$site."/.folder")
        {
          $comp_entry_dir = array();
          $dir = $mgmt_config['abs_path_comp'].$site."/";
        }
        // remove double entries 
        else
        {
          if (sizeof ($comp_entry_dir) > 1) $comp_entry_dir = array_unique ($comp_entry_dir);
          $dir = "";
        }
      }
      // user has no component access
      else $dir = "";
    }

    // convert path
    $dir_esc = convertpath ($site, $dir, "comp");
    
    // set location in component structure in session
    if (valid_locationname ($dir))
    {
      $temp_complocation[$site] = $dir;
      $_SESSION['hcms_temp_complocation'] = $temp_complocation;
    }
    
    // media format
    if ($mediatype == "audio") $format_ext = strtolower ($hcms_ext['audio']);
    elseif ($mediatype == "video") $format_ext = strtolower ($hcms_ext['video']);
    elseif ($mediatype == "text") $format_ext = strtolower ($hcms_ext['cms'].$hcms_ext['bintxt'].$hcms_ext['cleartxt']);
    elseif ($mediatype == "flash") $format_ext = strtolower ($hcms_ext['flash']);
    elseif ($mediatype == "image") $format_ext = strtolower ($hcms_ext['image']);
    elseif ($mediatype == "compressed") $format_ext = strtolower ($hcms_ext['compressed']);
    elseif ($mediatype == "binary") $format_ext = strtolower ($hcms_ext['binary']);
    else $format_ext = "";
    
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

function showOptions()
{
  if (document.getElementById('searchOptions'))
  {
    if (document.getElementById('searchOptions').style.display == 'none')
    {
      document.getElementById('searchOptions').style.display = 'block';
    }
    else if (document.getElementById('searchOptions').style.display == 'block')
    {
      document.getElementById('searchOptions').style.display = 'none';
    }
  }
}
//-->
</script>";
    
    // current location
    $location_name = getlocationname ($site, $dir, "comp", "path");
    
    $result .= "
    <span class=\"hcmsHeadline\" style=\"padding:3px 0px 3px 0px; display:block;\">".getescapedtext ($hcms_lang['select-object'][$lang], $hcms_charset, $lang)."</span>
    <span class=\"hcmsHeadlineTiny\" style=\"padding:3px 0px 3px 0px; display:block;\">".$location_name."</span>\n";
    
    // file upload    
    if ($compcat == "media" && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1 && $search_expression == "")
    {
      // Upload Button
      if ($html5file) $popup_upload = "popup_upload_html.php";
      else $popup_upload = "popup_upload_swf.php";
      
      $result .= "
      <div style=\"align:center; padding:2px; width:100%;\">
        <input name=\"UploadButton\" class=\"hcmsButtonGreen\" style=\"width:198px; float:left;\" type=\"button\" onClick=\"hcms_openWindow('".$mgmt_config['url_path_cms'].$popup_upload."?uploadmode=multi&site=".url_encode($site)."&cat=comp&location=".url_encode($dir_esc)."','','status=yes,scrollbars=no,resizable=yes,width=800,height=600','800','600');\" value=\"".getescapedtext ($hcms_lang['upload-file'][$lang], $hcms_charset, $lang)."\" />
        <img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"document.location.reload();\" src=\"".getthemelocation()."img/button_view_refresh.gif\" alt=\"".getescapedtext ($hcms_lang['refresh'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['refresh'][$lang], $hcms_charset, $lang)."\" />
      </div>
      <div style=\"clear:both;\"></div>\n";
    }
    elseif (($compcat == "single" || $compcat == "multi") && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1 && $search_expression == "")
    {
      $result .= "
      <div style=\"align:center; padding:2px; width:100%;\">
        <input name=\"UploadButton\" class=\"hcmsButtonGreen\" style=\"width:198px; float:left;\" type=\"button\" onClick=\"hcms_openWindow('".$mgmt_config['url_path_cms']."frameset_content.php?site=".url_encode($site)."&cat=comp&location=".url_encode($dir_esc)."','','status=yes,scrollbars=no,resizable=yes,width=800,height=600','800','600');\" value=\"".getescapedtext ($hcms_lang['new-component'][$lang], $hcms_charset, $lang)."\" />
        <img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"document.location.reload();\" src=\"".getthemelocation()."img/button_view_refresh.gif\" alt=\"".getescapedtext ($hcms_lang['refresh'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['refresh'][$lang], $hcms_charset, $lang)."\" />
      </div>
      <div style=\"clear:both;\"></div>\n";
    }
    
    // search form
    if ($mgmt_config['db_connect_rdbms'] != "")
    {
      $result .= "
    <div id=\"searchForm\" style=\"padding:2px; width:100%;\">
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
        else $result .= getescapedtext ($hcms_lang['search-expression'][$lang], $hcms_charset, $lang);
        $result .= "\" onblur=\"if (this.value=='') this.value='".getescapedtext ($hcms_lang['search-expression'][$lang], $hcms_charset, $lang)."';\" onclick=\"showOptions();\" onfocus=\"if (this.value=='".getescapedtext ($hcms_lang['search-expression'][$lang], $hcms_charset, $lang)."') this.value='';\" style=\"width:190px;\" maxlength=\"60\" />
        <img name=\"SearchButton\" src=\"".getthemelocation()."img/button_OK.gif\" onClick=\"if (document.forms['searchform_general'].elements['search_expression'].value=='".getescapedtext ($hcms_lang['search-expression'][$lang], $hcms_charset, $lang)."') document.forms['searchform_general'].elements['search_expression'].value=''; document.forms['searchform_general'].submit();\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('SearchButton','','".getthemelocation()."img/button_OK_over.gif',1)\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" align=\"top\" alt=\"OK\" title=\"OK\" />";
    
      // search options
      if (($compcat == "media" && $mediatype == "") || $mgmt_config[$site]['dam']) $result .= "
        <div id=\"searchOptions\" class=\"hcmsInfoBox\" style=\"width:210px; margin:2px 0px 8px 0px; display:none;\">
          &nbsp;<b>".$hcms_lang['search-for-file-type'][$lang].":</b><br />
          <input type=\"checkbox\" name=\"search_format[object]\" value=\"comp\" checked=\"checked\" />".$hcms_lang['components'][$lang]."<br />
          <input type=\"checkbox\" name=\"search_format[image]\" value=\"image\" checked=\"checked\" />".$hcms_lang['image'][$lang]."<br />
          <input type=\"checkbox\" name=\"search_format[document]\" value=\"document\" checked=\"checked\" />".$hcms_lang['document'][$lang]."<br />
          <input type=\"checkbox\" name=\"search_format[video]\" value=\"video\" checked=\"checked\" />".$hcms_lang['video'][$lang]."<br />
          <input type=\"checkbox\" name=\"search_format[audio]\" value=\"audio\" checked=\"checked\" />".$hcms_lang['audio'][$lang]."<br />
        </div>";
    
      $result .= "
      </form>
    </div>\n";
    }
    
    $result .= "
    <table width=\"98%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
  
    // parent directory
    if (
         (!$mgmt_config[$site]['dam'] && substr_count ($dir, $mgmt_config['abs_path_comp']) > 0 && $dir != $mgmt_config['abs_path_comp']) || 
         ($mgmt_config[$site]['dam'] && $setlocalpermission['root'] == 1)
       )
    {
      //get parent directory
      $updir_esc = getlocation ($dir_esc);
    
      if ($callback == "") $result .= "<tr><td align=\"left\" colspan=\"2\" nowrap=\"nowrap\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode($updir_esc)."&compcat=".url_encode($compcat)."&mediatype=".url_encode($mediatype)."&site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&scaling=".url_encode($scalingfactor)."\"><img src=\"".getthemelocation()."img/back.gif\" class=\"hcmsIconList\" />&nbsp;".getescapedtext ($hcms_lang['back'][$lang], $hcms_charset, $lang)."</a></td></tr>\n";
      else $result .= "<tr><td align=\"left\" colspan=\"2\" nowrap=\"nowrap\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode($updir_esc)."&site=".url_encode($site)."&compcat=".url_encode($compcat)."&mediatype=".url_encode($mediatype)."&lang=".url_encode($lang)."&callback=".url_encode($callback)."&scaling=".url_encode($scalingfactor)."\"><img src=\"".getthemelocation()."img/back.gif\" class=\"hcmsIconList\" />&nbsp;".getescapedtext ($hcms_lang['back'][$lang], $hcms_charset, $lang)."</a></td></tr>\n";
    }
    elseif ($search_expression != "")
    {
      if ($callback == "") $result .= "<tr><td align=\"left\" colspan=\"2\" nowrap=\"nowrap\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode("%comp%/")."&compcat=".url_encode($compcat)."&mediatype=".url_encode($mediatype)."&site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&scaling=".url_encode($scalingfactor)."\"><img src=\"".getthemelocation()."img/back.gif\" class=\"hcmsIconList\" />&nbsp;".getescapedtext ($hcms_lang['back'][$lang], $hcms_charset, $lang)."</a></td></tr>\n";
      else $result .= "<tr><td align=\"left\" colspan=\"2\" nowrap=\"nowrap\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode("%comp%/")."&site=".url_encode($site)."&compcat=".url_encode($compcat)."&mediatype=".url_encode($mediatype)."&lang=".url_encode($lang)."&callback=".url_encode($callback)."&scaling=".url_encode($scalingfactor)."\"><img src=\"".getthemelocation()."img/back.gif\" class=\"hcmsIconList\" />&nbsp;".getescapedtext ($hcms_lang['back'][$lang], $hcms_charset, $lang)."</a></td></tr>\n";
    }
    
    // -------------------------------- search results ------------------------------------
    if ($search_expression != "")
    {
      if ($mediatype != "") $search_format = array ($mediatype);
          
      $object_array = rdbms_searchcontent ($dir_esc, "", $search_format, "", "", "", array($search_expression), $search_expression, "", "", "", "", "", "", "", 100);

      if (is_array ($object_array))
      {
        foreach ($object_array as $entry)
        {
          if ($entry != "")
          {
            $authorized = false;
            
            // if DAM
            if ($mgmt_config[$site]['dam'])
            {
              // local access permissions
              $ownergroup_entry = accesspermission ($site, $entry, "comp");
              $setlocalpermission_entry = setlocalpermission ($site, $ownergroup_entry, "comp");
              
              if (isset ($setlocalpermission_entry['root']) && $setlocalpermission_entry['root'] == 1) $authorized = true;
            }
            // if CMS
            else $authorized = accessgeneral ($site, $entry, "comp");

            if ($authorized)
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
    }
    // -------------------------------- file explorer -------------------------------- 
    elseif ($dir != "")
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

    // -------------------------------- prepare output  -------------------------------- 
    if (isset ($comp_entry_dir) || isset ($comp_entry_file))
    {      
      // folder
      if (isset ($comp_entry_dir) && is_array ($comp_entry_dir) && sizeof ($comp_entry_dir) > 0)
      {
        natcasesort ($comp_entry_dir);
        reset ($comp_entry_dir);
        
        foreach ($comp_entry_dir as $dirname)
        {
          if ($dirname != "")
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
      }
      
      // component/asset
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
            
            // correct extension if object is unpublished
            if (substr ($object, -4) == ".off") $object = substr ($object, 0, strlen ($object) - 4);

            // get name
            $comp_name = getlocationname ($site, $object, "comp", "path");
            
            if ($compcat != "media" && strlen ($comp_name) > 50) $comp_name = "...".substr (substr ($comp_name, -50), strpos (substr ($comp_name, -50), "/")); 

            if (
                 $dir.$object != $location.$page && 
                 (
                   ($compcat != "media" && !$mgmt_config[$site]['dam'] && $comp_info['type'] == "Component") || // standard components if not DAM for component tag
                   ($compcat != "media" && $mgmt_config[$site]['dam']) || // any type if is DAM for component tag
                   ($compcat == "media" && ($mediatype == "" || $mediatype == "comp" || substr_count ($format_ext.".", $comp_info['ext'].".") > 0)) // media assets for media tag
                 )
               )
            {
              $comp_path = $object;
              
              // listview - view option for un/published objects
              if ($comp_info['published'] == false) $class_image = "class=\"hcmsIconList hcmsIconOff\"";
              else $class_image = "class=\"hcmsIconList\"";

              // warning if file extensions don't match and HTTP include is off and it is not a DAM
              if ($compcat != "media" && !$mgmt_config[$site]['dam'] && $mgmt_config[$site]['http_incl'] == false && ($comp_info['ext'] != $page_info['ext'] && $comp_info['ext'] != ".page")) $alert = "test = confirm(hcms_entity_decode('".getescapedtext ($hcms_lang['the-object-types-do-not-match'][$lang], $hcms_charset, $lang)."'));";    
              else $alert = "test = true;";
              
              if ($compcat == "single")
              {
                $result .= "<tr><td width=\"85%\" align=\"left\" nowrap=\"nowrap\"><a href=# onClick=\"".$alert." if (test == true) sendCompInput('".$comp_name."','".$comp_path."');\" title=\"".$comp_name."\"><img src=\"".getthemelocation()."img/".$comp_info['icon']."\" ".$class_image." />&nbsp;".showshorttext($comp_info['name'], 24)."</a></td><td align=\"left\" nowrap=\"nowrap\"><a href=# onClick=\"".$alert." if (test == true) sendCompInput('".$comp_name."','".$comp_path."')\"><img src=\"".getthemelocation()."img/button_OK_small.gif\" class=\"hcmsIconList\" alt=\"OK\" title=\"OK\" /></a></td></tr>\n";
              }
              elseif ($compcat == "multi")
              {
                $result .= "<tr><td width=\"85%\" align=\"left\" nowrap=\"nowrap\"><a href=# onClick=\"".$alert." if (test == true) sendCompOption('".$comp_name."','".$comp_path."');\" title=\"".$comp_name."\"><img src=\"".getthemelocation()."img/".$comp_info['icon']."\" ".$class_image." />&nbsp;".showshorttext($comp_info['name'], 24)."</a></td><td align=\"left\" nowrap=\"nowrap\"><a href=# onClick=\"".$alert." if (test == true) sendCompOption('".$comp_name."','".$comp_path."')\"><img src=\"".getthemelocation()."img/button_OK_small.gif\" class=\"hcmsIconList\" alt=\"OK\" title=\"OK\" /></a></td></tr>\n";
              }
              elseif ($compcat == "media")
              {
                if ($callback == "") $result .= "<tr><td width=\"85%\" align=\"left\" nowrap=\"nowrap\"><a href=# onClick=\"".$alert." if (test == true) sendMediaInput('".$comp_name."','".$comp_path."'); parent.frames['mainFrame2'].location.href='media_view.php?site=".url_encode($site)."&mediacat=cnt&mediatype=".url_encode($mediatype)."&mediaobject=".url_encode($comp_path)."&scaling=".url_encode($scalingfactor)."';\" title=\"".$comp_name."\"><img src=\"".getthemelocation()."img/".$comp_info['icon']."\" ".$class_image." />&nbsp;".showshorttext($comp_info['name'], 24)."</a></td><td align=\"left\" nowrap=\"nowrap\"><a href=# onClick=\"".$alert." if (test == true) sendMediaInput('".$comp_name."','".$comp_path."'); parent.frames['mainFrame2'].location.href='media_view.php?site=".url_encode($site)."&mediacat=cnt&mediatype=".url_encode($mediatype)."&mediaobject=".url_encode($comp_path)."&scaling=".url_encode($scalingfactor)."';\"><img src=\"".getthemelocation()."img/button_OK_small.gif\" class=\"hcmsIconList\" alt=\"OK\" title=\"OK\" /></a></td></tr>\n";
                else $result .= "<tr><td width=\"85%\" align=\"left\" nowrap><a href=# onClick=\"parent.frames['mainFrame2'].location.href='media_select.php?site=".url_encode($site)."&mediacat=cnt&mediatype=".url_encode($mediatype)."&mediaobject=".url_encode($comp_path)."&lang=".url_encode($lang)."&callback=".url_encode($callback)."&scaling=".url_encode($scalingfactor)."';\" title=\"".$comp_name."\"><img src=\"".getthemelocation()."img/".$comp_info['icon']."\" ".$class_image." />&nbsp;".showshorttext($comp_info['name'], 24)."</a></td><td align=\"left\" nowrap><a href=# onClick=\"parent.frames['mainFrame2'].location.href='media_select.php?site=".url_encode($site)."&mediacat=cnt&mediatype=".url_encode($mediatype)."&mediaobject=".url_encode($comp_path)."&lang=".url_encode($lang)."&callback=".url_encode($callback)."&scaling=".url_encode($scalingfactor)."';\"><img src=\"".getthemelocation()."img/button_OK_small.gif\" class=\"hcmsIconList\" alt=\"OK\" title=\"OK\" /></a></td></tr>\n";
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
// Returns the rich text editor code

function showeditor ($site, $hypertagname, $id, $contentbot="", $sizewidth=600, $sizeheight=300, $toolbar="Default", $lang="en", $dpi=72)
{
  global $mgmt_config, $publ_config;
  
  if (is_array ($mgmt_config) && valid_publicationname ($site) && $hypertagname != "" && $id != "" && $lang != "")
  {
    if (valid_publicationname ($site) && !is_array ($publ_config)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"); 
    
    // transform language code for editor
    if ($lang == "zh-s") $lang = "zh";
    
    //initialize scaling factor 
    $scalingfactor = 1;
    
    //check if $dpi is valid and than calculate scalingfactor
    if ($dpi > 0 && $dpi < 1000) 
    {
      $scalingfactor = 72 / $dpi; 
    }
    
    // define class-name for comment tags
    if (strpos ("_".$hypertagname, "comment") == 1) $classname = "class=\"is_comment\"";
    else $classname = "";
    
    return "<textarea id=\"".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id."]\" ".$classname.">".$contentbot."</textarea>
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
// Returns the rich text editor code (JS, CSS) for include into the html head section

function showinlineeditor_head ($lang)
{
  global $mgmt_config, $hcms_charset, $hcms_lang;

  if (is_array ($mgmt_config) && $lang != "")
  {
    return "
    <script src=\"".$mgmt_config['url_path_cms']."javascript/jquery/jquery-1.10.2.min.js\" type=\"text/javascript\"></script>
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
                if (p<1 || p==(val.length-1)) errors += '".getescapedtext ($hcms_lang['value-must-contain-an-e-mail-address'][$lang], $hcms_charset, $lang).".\\n';
              } 
              else if (test!='R') 
              { 
                num = parseFloat(val);
                if (isNaN(val)) errors += '".getescapedtext ($hcms_lang['value-must-contain-a-number'][$lang], $hcms_charset, $lang).".\\n';
                if (test.indexOf('inRange') != -1) 
                { 
                  p=test.indexOf(':');
                  if(test.substring(0,1) == 'R') {
                    min=test.substring(8,p); 
                  } else {
                    min=test.substring(7,p); 
                  }
                  max=test.substring(p+1);
                  if (num<min || max<num) errors += '".getescapedtext ($hcms_lang['value-must-contain-a-number-between'][$lang], $hcms_charset, $lang)." '+min+' - '+max+'.\\n';
                } 
              } 
            } 
            else if (test.charAt(0) == 'R') errors += '".getescapedtext ($hcms_lang['a-value-is-required'][$lang], $hcms_charset, $lang).".\\n'; 
          }
        } 
        
        if (errors) 
        {
          alert(hcms_entity_decode('".getescapedtext ($hcms_lang['the-input-is-not-valid'][$lang], $hcms_charset, $lang).":\\n'+errors));
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
// Returns the date picker code (JS, CSS) for include into the html head section

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
// output: rich text editor code / false on error

// description:
// shows the rich text inline editor

function showinlineeditor ($site, $hypertag, $id, $contentbot="", $sizewidth=600, $sizeheight=300, $toolbar="Default", $lang="en", $contenttype="", $cat="", $location_esc="", $page="", $contentfile="", $db_connect=0, $token="")
{
  global $mgmt_config, $publ_config, $hcms_charset, $hcms_lang;

  // add confirm save on changes in inline editor or leave empty string
  // $confirm_save = " && confirm(hcms_entity_decode(\"".getescapedtext ($hcms_lang['do-you-want-to-save-the-changes'][$lang]."\"));";
  $confirm_save = "";
  
  // get hypertagname
  $hypertagname = gethypertagname ($hypertag);
  
  // get constraint
  $constraint = getattribute ($hypertag, "constraint");
  
  // get tag id
  $id = getattribute ($hypertag, "id");
  
  // correct Ids of article
  if ($id != "" && strpos ("_".$id, ":") > 0) $id = str_replace (":", "_", $id);
  
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
        $title .= getescapedtext ($hcms_lang['edit-unformatted-text'][$lang], $hcms_charset, $lang);
        break;
      case 'arttextf':
      case 'textf':
        $title .= getescapedtext ($hcms_lang['edit-formatted-text'][$lang], $hcms_charset, $lang);
        $tag = "div";
        // disable contenteditable for inline editing
        $contenteditable = true;
        break;
      case 'arttextl':
      case 'textl':
        $title .= getescapedtext ($hcms_lang['edit-text-options'][$lang], $hcms_charset, $lang);
        break;
      case 'arttextc':
      case 'textc':
        $title .= getescapedtext ($hcms_lang['set-checkbox'][$lang], $hcms_charset, $lang);
        break;
      case 'arttextd':
      case 'textd':
        $title .= getescapedtext ($hcms_lang['pick-a-date'][$lang], $hcms_charset, $lang);
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
            
            jq_inline('#hcms_checkbox_".$hypertagname."_".$id."').click(function(event) {
              event.stopPropagation();
            }).blur(function() {
              checkbox = jq_inline(this);
              form = jq_inline('#hcms_form_".$hypertagname."_".$id."');
              elem = jq_inline('#".$hypertagname."_".$id."');
              
              newcheck = (checkbox.prop('checked') ? jq_inline.trim(checkbox.val()) : '".$defaultText."');
              
              if (oldcheck_".$hypertagname."_".$id." != newcheck".$confirm_save.")
              {
                oldcheck_".$hypertagname."_".$id." = newcheck;
                jq_inline.post(
                  \"".$mgmt_config['url_path_cms']."service/savecontent.php\", 
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
              }
              else
              {
                checkbox.prop('checked', (oldcheck_".$hypertagname."_".$id." == \"\" ? false : true));
              }
              
              form.hide();
              elem.show();
            });
          });
        </script>
        ";
        $element = "<input type=\"hidden\" name=\"".$hypertagname."[".$id."]\" value=\"\"/><input title=\"".$labelname.": ".getescapedtext ($hcms_lang['set-checkbox'][$lang], $hcms_charset, $lang)."\" type=\"checkbox\" id=\"hcms_checkbox_".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id."]\" value=\"".$value."\"".($value == $contentbot ? ' checked ' : '').">".$labelname;
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
            // onclose handler
            var cal_on_close_".$hypertagname."_".$id." = function (cal) {
              form = jq_inline('#hcms_form_".$hypertagname."_".$id."');
              form.hide();
              cal_obj_".$hypertagname."_".$id.".hide();
              elem = jq_inline('#".$hypertagname."_".$id."');
              elem.show();
            }
           
            // handling the saving
            var date_save_".$hypertagname."_".$id." = function () {
              if(preventSave_".$hypertagname."_".$id." == false)
              {
                datefield = jq_inline('#hcms_datefield_".$hypertagname."_".$id."');
                form = jq_inline('#hcms_form_".$hypertagname."_".$id."');
                elem = jq_inline('#".$hypertagname."_".$id."');
                
                newdate = jq_inline.trim(datefield.val());
                var check = true;
                
                // confirm the changes
                if (olddate_".$hypertagname."_".$id." != newdate".$confirm_save.") 
                {  
                  olddate_".$hypertagname."_".$id." = newdate;
                  jq_inline.post(
                    \"".$mgmt_config['url_path_cms']."service/savecontent.php\", 
                    jq_inline('#hcms_form_".$hypertagname."_".$id."').serialize(), 
                    function(data)
                    {
                      if (data.message.length !== 0)
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
                  
                  // jump out without changing anything
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
              
              if (cal_obj_".$hypertagname."_".$id.")
              {
                cal_obj_".$hypertagname."_".$id.".parse_date(datefield_".$hypertagname."_".$id.".value, format_".$hypertagname."_".$id.");
                cal_obj_".$hypertagname."_".$id.".show_at_element(form, 'child');
              }
              else
              {
                cal_obj_".$hypertagname."_".$id." = new RichCalendar();
                cal_obj_".$hypertagname."_".$id.".start_week_day = 1;
                cal_obj_".$hypertagname."_".$id.".show_time = false;
                cal_obj_".$hypertagname."_".$id.".language = '".getcalendarlang ($lang)."';
                cal_obj_".$hypertagname."_".$id.".auto_close = false;
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
            
            jq_inline('#hcms_datefield_".$hypertagname."_".$id."').click(function(event) {
              event.stopPropagation();
            }).blur(date_save_".$hypertagname."_".$id.");
          });
          </script>
        ";
        $element = "<input title=\"".$labelname.": ".getescapedtext ($hcms_lang['pick-a-date'][$lang], $hcms_charset, $lang)."\" type=\"text\" id=\"hcms_datefield_".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id."]\" value=\"".$contentbot."\" style=\"color:#000; background:#FFF; font-family:Verdana,Arial,Helvetica,sans-serif; font-size:12px; font-weight:normal;\" /><br>";
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
              
              if (oldtext_".$hypertagname."_".$id." == hcms_entity_decode('".$defaultText."')) oldtext_".$hypertagname."_".$id." = '';
              
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
                  \"".$mgmt_config['url_path_cms']."service/savecontent.php\", 
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
        $element = "<textarea title=\"".$labelname.": ".$title."\" id=\"hcms_txtarea_".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id."]\" onkeyup=\"hcms_adjustTextarea(this);\" class=\"hcms_editable_textarea\">".$contentbot."</textarea>";
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
                  \"".$mgmt_config['url_path_cms']."service/savecontent.php\", 
                  jq_inline('#hcms_form_".$hypertagname."_".$id."').serialize(), 
                  function(data)
                  {
                    if (data.message.length !== 0)
                    {
                      alert(hcms_entity_decode(data.message));
                    }				
                  }, 
                  \"json\"
                );
                elem.text(newselect);
              }
              else
              {
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
        $element = "<select title=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-text-options'][$lang], $hcms_charset, $lang)."\" id=\"hcms_selectbox_".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id."]\" style=\"color:#000; background:#FFF; font-family:Verdana,Arial,Helvetica,sans-serif; font-size:12px; font-weight:normal;\">\n";
        
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

                    if (hcms_stripTags(oldtext_".$hypertagname."_".$id.") == hcms_stripTags('".$defaultText."'))
                    {
                      oldtext_".$hypertagname."_".$id." = '';
                    }
                    
                    event.editor.setData(oldtext_".$hypertagname."_".$id.");
                  },
                  blur: function (event)
                  {
                    var newtext = jq_inline.trim(event.editor.getData());
                    
                    if (oldtext_".$hypertagname."_".$id." != newtext".$confirm_save.")
                    {
                      oldtext_".$hypertagname."_".$id." = newtext;
                      jq_inline('#hcms_txtarea_".$hypertagname."_".$id."').val(event.editor.getData());
                      jq_inline.post(
                        \"".$mgmt_config['url_path_cms']."service/savecontent.php\", 
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
                    }
                    else
                    {
                      event.editor.setData(oldtext_".$hypertagname."_".$id.");
                    }
                    
                    if (event.editor.getData() == '')
                    {
                      event.editor.setData('<p>".$defaultText."</p>');
                    }
                  }
                }
              });
            });
          </script>
        ";
        
        $element = "<textarea title=\"".$labelname.": ".$title."\" id=\"hcms_txtarea_".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id."]\">".$contentbot."</textarea>";
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
// videoArray (Array) containing the different html sources,
// width (Integer) Width of the video in pixel,
// height (Integer) Height of the video in pixel,
// logo_url (String) Link to the logo which is displayed before you click on play (If the value is null the default logo will be used),
// id (String) The ID of the video (will be generated when empty),
// title (String) The title for this video,
// autoplay (Boolean) Should the video be played on load (true), default is false,
// enableFullScreen (Boolean) Is it possible to view the video in fullScreen (true),
// play loop (optional) [true,false],
// muted/no sound (optional) [true,false],
// player controls (optional) [true,false],
// use video in iframe (optional) [true,false],
// reload video sources to prevent the browser cache to show the same video even if it has been changed [true,false] (optional),

// output: HTML code of the video player / false on error

// description:
// Generates a html segment for the video player code

function showvideoplayer ($site, $video_array, $width=320, $height=240, $logo_url="", $id="", $title="", $autoplay=true, $fullscreen=true, $loop=false, $muted=false, $controls=true, $iframe=false, $force_reload=false)
{
  global $mgmt_config;
  
  if ($width == "") $width = 320;
  if ($height == "") $height = 240;

  if (valid_publicationname ($site) && is_array ($video_array) && $width != "" && $height != "")
  {
    // prepare video array
    $sources = "";
    
    // add timestamp to force reload of files
    if ($force_reload) $ts = "&ts=".time();
    else $ts = "";
    
    foreach ($video_array as $value)
    {
      // only partial URL
      if (strpos ("_".trim($value), "http") != 1)
      {
        // before version 2.0 (only media reference incl. the wrapper is given)
        if (strpos ("_".$value, "explorer_wrapper.php") > 0)
        {
          $media = getattribute ($value, "media");
          
          if ($media != "") $type = "type=\"".getmimetype ($media)."\" ";
          else $type = "";

          // use new media streaming service
          if (substr_count ($media, "/") == 1) $url = $mgmt_config['url_path_cms']."?wm=".hcms_encrypt ($media).$ts;
          // is not supported anymore since version 6.0.6
          else $url = $mgmt_config['url_path_cms'].$value.$ts;
        }
        // version 2.0 (only media reference is given, no ; as seperator is used)
        elseif (strpos ($value, ";") < 1)
        {
          if ($value != "") $type = "type=\"".getmimetype ($value)."\" ";
          else $type = "";
            
          $url = $mgmt_config['url_path_cms']."?wm=".hcms_encrypt ($value).$ts;
        }
        // version 2.1 (media reference and mimetype is given)
        elseif (strpos ($value, ";") > 0)
        {
          list ($media, $type) = explode (";", $value);
           
          $type = "type=\"".$type."\" ";
          $url = $mgmt_config['url_path_cms']."?wm=".hcms_encrypt ($media).$ts;
        }
        else $url = "";
      }
      // absolute URL is given (deprecated)
      else
      {
        $media = getattribute ($value, "media");
        
        if ($media != "") $type = "type=\"".getmimetype ($media)."\" ";
        else $type = "";
        
        $url = $value.$ts;
      }
      
      // define VTT file name
      if ($media != "")
      {
        $container_id = getmediacontainerid ($media);
        
        if ($container_id > 0) $vtt_filename = $container_id;
      }

      if ($url != "") $sources .= "    <source src=\"".$url."\" ".$type."/>\n";
    }
    
    // logo from video thumb image
    $logo_file = getobject ($media);
    $media_dir = getmedialocation ($site, $media, "abs_path_media");

    // define logo if undefined
    if ($logo_url == "" && $media_dir != "")
    {
      // if original video preview file (FLV)
      if (strpos ($logo_file, ".orig.") > 0)
      {
        $logo_name = substr ($logo_file, 0, strrpos ($logo_file, ".orig."));
      }
      // if video thumbnail file
      elseif (strpos ($logo_file, ".thumb.") > 0)
      {
        $logo_name = substr ($logo_file, 0, strrpos ($logo_file, ".thumb."));
      }
      // if individual video file
      elseif (strpos ($logo_file, ".media.") > 0)
      {
        $logo_name = substr ($logo_file, 0, strrpos ($logo_file, ".media."));
      }
      // if original video file
      elseif (strpos ($logo_file, ".") > 0)
      {
        $logo_name = substr ($logo_file, 0, strrpos ($logo_file, "."));
      }
      
      // if logo is in media repository
      // IMPORTANT: Do not use a wrapperlink for the video poster image!
      if ($logo_name != "" && (is_file ($media_dir.$site."/".$logo_name.".thumb.jpg") || is_cloudobject ($media_dir.$site."/".$logo_name.".thumb.jpg")))
      {
        $logo_url = createviewlink ($site, $logo_name.".thumb.jpg").$ts;
      }
    }

    $flashplayer = $mgmt_config['url_path_cms']."javascript/video/jarisplayer.swf";
    
    // if no logo is defined set default logo
    if (empty ($logo_url)) $logo_url = getthemelocation()."img/logo_player.jpg";
    
    if (empty ($id)) $id = "media_".uniqid(); 
  
    // PROJEKKTOR Player
    if (isset ($mgmt_config['videoplayer']) && strtolower ($mgmt_config['videoplayer']) == "projekktor")
    {
      $return = '
  <video id="'.$id.'" class="projekktor"'.(($loop) ? ' loop ' : ' ').(($muted) ? ' muted ' : ' ').((!empty($logo_url)) ? ' poster="'.$logo_url.'" ' : ' ').((!empty($title)) ? ' title="'.$title.'" ' : ' ').'width="'.$width.'" height="'.$height.'" '.($fullscreen ? 'allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true" ' : '').(($controls) ? ' controls' : '').'>'."\n";

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
      enableFullscreen: '.(($fullscreen) ? 'true' : 'false').',
      enableKeyboard: true,
      disablePause: false,
      disallowSkip: false,
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
    
      $return = "  <video id=\"".$id."\" class=\"video-js vjs-default-skin\" ".(($controls) ? " controls" : "").(($loop) ? " loop" : "").(($muted) ? " muted" : "").(($autoplay) ? " autoplay" : "")." preload=\"auto\" 
    width=\"".intval($width)."\" height=\"".intval($height)."\"".(($logo_url != "") ? " poster=\"".$logo_url."\"" : "")."
    data-setup='{\"loop\":".(($loop) ? "true" : "false").$fallback."}' title=\"".$title."\"".($fullscreen ? " allowFullScreen=\"true\" webkitallowfullscreen=\"true\" mozallowfullscreen=\"true\"" : "").">\n";
    
      $return .= $sources;
      
      // create VTT track sources
      if (!empty ($vtt_filename))
      {
        // load language code index file
        $langcode_array = getlanguageoptions ();

        if ($langcode_array != false)
        {
          foreach ($langcode_array as $code => $language)
          {
            if (is_file ($mgmt_config['abs_path_temp']."view/".$vtt_filename."_".trim($code).".vtt"))
            {
              $return .= "    <track kind=\"captions\" src=\"".$mgmt_config['url_path_temp']."view/".$vtt_filename."_".trim($code).".vtt\" srclang=\"".trim($code)."\" label=\"".trim($language)."\" />\n";
            }
          }
        }
      }
      
      $return .= "  </video>\n";
    }

    return $return;
  }
  else return false;
}

// ------------------------- showvideoplayer_head -----------------------------
// function: showvideoplayer_head()
// input: secure hyperreferences by adding 'hypercms_', is it possible to view the video in fullScreen [true,false]
// output: head for video player / false on error

function showvideoplayer_head ($secureHref=true, $fullscreen=true)
{
  global $mgmt_config;

  // PROJEKKTOR Player
  if (isset ($mgmt_config['videoplayer']) && strtolower ($mgmt_config['videoplayer']) == "projekktor")
  {
    $jquerylib = $mgmt_config['url_path_cms']."javascript/jquery/jquery-1.10.2.min.js";
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
    if ($fullscreen == false) $return .= "  <style> .vjs-fullscreen-control { display: none; } .vjs-default-skin .vjs-volume-control { margin-right: 20px; } </style>";
  }
  
  return $return;
}

// ------------------------- showaudioplayer -----------------------------
// function: showaudioplayer()
// input: publication name, audio files as array (Array), ID of the tag (optional),
//        autoplay (optional) [true,false], play loop (optional) [true,false], player controls (optional) [true,false]
// output: code of the HTML5 player / false

// description:
// Generates the html segment for the video player code

function showaudioplayer ($site, $audioArray, $width=320, $height=320, $logo_url="", $id="", $autoplay=false, $loop=false, $controls=true, $force_reload=false)
{
  global $mgmt_config;
  
  if ($width == "") $width = 320;
  if ($height == "") $height = 320;

  if (valid_publicationname ($site) && is_array ($audioArray) && $width != "" && $height != "")
  {
    // add timestamp to force reload of files
    if ($force_reload) $ts = "&ts=".time();
    else $ts = "";
     
    // prepare video array
    $sources = "";
    
    foreach ($audioArray as $value)
    {
      // only partial URL
      if (strpos ("_".trim($value), "http") != 1)
      {
        // before version 2.0 (only media reference incl. the wrapper is given)
        if (strpos ("_".$value, "explorer_wrapper.php") > 0)
        {
          $media = getattribute ($value, "media");
          
          if ($media != "") $type = "type=\"".getmimetype ($media)."\" ";
          else $type = "";
          
          // use new media streaming service
          if (substr_count ($media, "/") == 1) $url = $mgmt_config['url_path_cms']."?wm=".hcms_encrypt ($media).$ts;
          // is not supported anymore since version 6.0.6
          else $url = $mgmt_config['url_path_cms'].$value.$ts;
        }
        // version 2.0 (only media reference is given, no ; as seperator is used)
        elseif (strpos ($value, ";") < 1)
        {
          if ($value != "") $type = "type=\"".getmimetype ($value)."\" ";
          else $type = "";

          $url = $mgmt_config['url_path_cms']."?wm=".hcms_encrypt($value).$ts;
        }
        // version 2.1 (media reference and mimetype is given)
        elseif (strpos ($value, ";") > 0)
        {
          list ($media, $type) = explode (";", $value);
          
          $type = "type=\"".$type."\" ";
          $url = $mgmt_config['url_path_cms']."?wm=".hcms_encrypt($media).$ts;
        }
        else $url = "";
      }
      // absolute URL is given (deprecated)
      else
      {
        $media = getattribute ($value, "media");
        
        if ($media != "") $type = "type=\"".getmimetype ($media)."\" ";
        else $type = "";
        
        $url = $value;
      }
      
      if ($url != "") $sources .= "    <source src=\"".$url."\" ".$type."/>\n";
    }
    
    // logo from audio thumb image
    $logo_file = getobject ($media);
    $media_dir = getmedialocation ($site, $media, "abs_path_media");
    
    // define logo if undefined
    if ($logo_url == "" && $media_dir != "")
    {
      // if original video preview file (FLV)
      if (strpos ($logo_file, ".orig.") > 0)
      {
        $logo_name = substr ($logo_file, 0, strrpos ($logo_file, ".orig."));
      }
      // if video thumbnail file
      elseif (strpos ($logo_file, ".thumb.") > 0)
      {
        $logo_name = substr ($logo_file, 0, strrpos ($logo_file, ".thumb."));
      }
      // if individual video file
      elseif (strpos ($logo_file, ".media.") > 0)
      {
        $logo_name = substr ($logo_file, 0, strrpos ($logo_file, ".media."));
      }
      // if original video file
      elseif (strpos ($logo_file, ".") > 0)
      {
        $logo_name = substr ($logo_file, 0, strrpos ($logo_file, "."));
      }
      
      // if logo is in media repository
      // IMPORTANT: Do not use a wrapperlink for the video poster image!
      if ($logo_name != "" && (is_file ($media_dir.$site."/".$logo_name.".thumb.jpg") || is_cloudobject ($media_dir.$site."/".$logo_name.".thumb.jpg")))
      {
        $logo_url = createviewlink ($site, $logo_name.".thumb.jpg").$ts;
      }
    }
        
    if (empty ($id)) $id = "media_".uniqid(); 
    
    // get browser info
    $user_client = getbrowserinfo ();
      
    if (isset ($user_client['msie']) && $user_client['msie'] > 0) $fallback = ", \"playerFallbackOrder\":[\"flash\", \"html5\", \"links\"]";
    else $fallback = "";
    
    // reset height if poster is not available
    if ($logo_url == "") $height = 60;
    
    // loop audio 
    if ($loop == true || $loop == 1 || strtolower ($loop) == "true") $loop = "true";
    else $loop = "false";
    
    $return = "  <audio id=\"".$id."\" class=\"video-js vjs-default-skin\"".(($controls) ? " controls" : "").(($autoplay) ? " autoplay" : "")." preload=\"auto\" 
    width=\"".$width."\" height=\"".$height."\"".(($logo_url != "") ? " poster=\"".$logo_url."\"" : "").(($loop) ? " loop" : "")." data-setup='{\"loop\":".(($loop) ? "true" : "false").$fallback."}'>\n";

    $return .= $sources;
    
    $return .= "  </audio>\n";

    return $return;
  }
  else return false;
}

// ------------------------- showaudioplayer_head -----------------------------
// function: showaudioplayer_head()
// input: secure hyperreferences by adding 'hypercms_'
// output: head for audio player

function showaudioplayer_head ($secureHref=true)
{
  global $mgmt_config;
  
  //return "<script src=\"".$mgmt_config['url_path_cms']."javascript/audio-js/audio.js\"></script>\n";
  return "  <link ".(($secureHref) ? "hypercms_" : "")."href=\"".$mgmt_config['url_path_cms']."javascript/video-js/video-js.css\" rel=\"stylesheet\" />
  <script src=\"".$mgmt_config['url_path_cms']."javascript/video-js/video.js\"></script>
  <script>
    videojs.options.flash.swf = \"".$mgmt_config['url_path_cms']."javascript/video-js/video-js.swf\";
  </script>
  <style> .vjs-fullscreen-control { display: none; } .vjs-default-skin .vjs-volume-control { margin-right: 20px; } </style>";
}

// ------------------------- debug_getbacktracestring -----------------------------
// function: debug_getbacktracestring()
// input: separator for arguments, separator for a Row on screen/file, functionnames to be ignored
// output: debug message

// description:
// Returns the current backtrace as a good readable string.
// Ignores debug and debug_getbacktracestring.

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

// ------------------------- showAPIdocs -----------------------------
// function: showAPIdocs()
// input: path to API file, return result as HTML or array [html,array] (optional)
// output: HTML output of documentation / false on error

// description:
// Generates the documentation of an API file

function showAPIdocs ($file, $return="html")
{
  if (is_file ($file))
  {
    $data = file ($file);
    
    if (is_array ($data))
    {
      $name = "";
      $open = "";
      $input = array();
      $output = array();
      $description = array();
      $requires = array();
      $function = array();
      $global = array();
      
      // collect
      foreach ($data as $line)
      {
        if ($line != "")
        {
          if (strpos ("_".$line, "// function:") > 0)
          {
            $temp = substr ($line, strlen ("// function:"));
            $temp = htmlentities ($temp);
            $name = trim (str_replace (array("(", ")"), "", $temp));
          }
          elseif (strpos ("_".$line, "// input:") > 0 && !empty ($name))
          {
            $temp = substr ($line, strlen ("// input:"));
            $input[$name] = trim ($temp)." ";
            $open = "input";
          }
          elseif (strpos ("_".$line, "// output:") > 0 && !empty ($name))
          {
            $temp = substr ($line, strlen ("// output:"));
            $temp = trim (htmlentities ($temp))." ";
            $output[$name] = $temp;
            $open = "output";
          }
          elseif (strpos ("_".$line, "// description:") > 0 && !empty ($name))
          {
            $temp = substr ($line, strlen ("// description:"));
            $temp = trim (htmlentities ($temp))." ";
            $description[$name] = $temp;
            $open = "description";
          }
          elseif (strpos ("_".$line, "// requires:") > 0 && !empty ($name))
          {
            $temp = substr ($line, strlen ("// requires:"));
            $temp = trim (htmlentities ($temp))." ";
            $requires[$name] = $temp;
            $open = "requires";
          }
          elseif (strpos ("_".$line, "// requirements:") > 0 && !empty ($name))
          {
            $temp = substr ($line, strlen ("// requirements:"));
            $temp = trim (htmlentities ($temp));
            $requires[$name] = $temp;
            $open = "requires";
          }
          // next commented lines
          elseif (strpos ("_".$line, "//") > 0 && !empty ($name))
          {
            $temp = trim (substr (trim ($line), strlen ("//")));
            $temp = htmlentities ($temp)." ";

            // comma separated list, may have multiple lines
            if ($open == "input") $input[$name] .= $temp;
            // one liner, may use commas
            elseif ($open == "output") $output[$name] .= $temp;
            // may have multiple lines
            elseif ($open == "description")
            {
              if (trim ($temp) != "") $temp = "\n".$temp;
              $description[$name] .= $temp;
            }
            // may have multiple lines
            elseif ($open == "requires")
            {
              if (trim ($temp) != "") $temp = "\n".$temp;
              $requires[$name] .= $temp;
            }
          }
          // function
          elseif (strpos ("_".$line, "function ".$name) > 0 && !empty ($name))
          {
            $temp = trim (substr (trim ($line), strlen ("function ")));
            $temp = htmlentities ($temp);
            $function[$name] = $temp;
            $open = "none";
          }
          // global variables
          elseif (strpos ("_".$line, "global ") > 0 && !empty ($name))
          {
            $temp = trim (substr (trim ($line), strlen ("global ")));
            $temp = trim (htmlentities ($temp), ";")." ";
            $global[$name] = $temp;
            
            if (strpos ("_".$line, ";") > 0) $open = "none";
            else $open = "global";
          }
          // next line of global variables
          elseif ($open == "global" && !empty ($name))
          {
            $temp = trim ($line);
            $temp = trim (htmlentities ($temp), ";")." ";
            $global[$name] .= $temp;

            if (strpos ("_".$line, ";") > 0) $open = "none";
            else $open = "global";
          }
          else
          {
            $open = "none";
          }
        }
      }

      // return as HTML code segment
      if ($return == "html" && !empty ($function) && sizeof ($function) > 0)
      {
        $result = "";
        
        foreach ($function as $name=>$value)
        {
          $result .= "<h3>".$name."</h3><br/>\n";
          $result .= "<b>Syntax:</b><br/>\n";          
          $function[$name] = str_replace (",", ", ", $function[$name]);
          $result .= $function[$name]."<br/><br/>\n";
          
          $temp = trim (substr ($value, strpos ($value, "(") + 1), ")");
          $input_vars = explode (", ", trim ($temp));
          
          if (is_array ($input_vars) && sizeof ($input_vars) > 0)
          {
            $result .= "<b>Input parameters:</b><br/>\n";
            
            $var_text = explode (", ", $input[$name]);
            
            for ($i=0; $i<count($input_vars); $i++)
            {
              if (!empty ($input_vars[$i]))
              {
                if (strpos ($input_vars[$i], "=") > 0) list ($var, $default) = explode ("=", $input_vars[$i]);
                else $var = $input_vars[$i];
                
                if (trim ($var) == "") $var = "%";
                
                if (!empty ($var_text[$i]))
                {
                  $var_text[$i] = str_replace (",", ", ", $var_text[$i]);
                  $text = " ... ".trim ($var_text[$i]);
                }
                else $text = "";
              
                $result .= trim ($var).$text."<br/>\n";
              }
            }
            
            $result .= "<br/>\n";
          }
          
          if (!empty ($global[$name]))
          {
            $result .= "<b>global input parameters:</b><br/>\n";
            $result .= str_replace (", ", "<br/>\n", $global[$name]);
            $result .= "<br/><br/>\n";
          }
          
          $result .= "<b>Output:</b><br/>\n";
          $result .= str_replace (", ", "<br/>\n", $output[$name]);
          $result .= "<br/><br/>\n";
          
          if (!empty ($description[$name]))
          {
            $description[$name] = str_replace (",", ", ", $description[$name]);
            $result .= "<b>Description:</b><br/>\n";
            $result .= nl2br (trim ($description[$name]))."<br/><br/>\n";
          }
        }
      }
      // return as array
      else
      {
        $result = array();
        
        $result['function'] = $function;
        $result['input'] = $input;
        $result['global'] = $global;
        $result['output'] = $output;
        $result['description'] = $description;
      }
    }
    
    return $result;
  }
  else return false;
}

// ------------------------- readnavigation -----------------------------
// function: readnavigation()
// input: publication name, location, object name, view [see view parameters of function buildview], user name
// output: navigation item array / false

// description:
// Reads the content from the container and collects information about a single navigation item

function readnavigation ($site, $docroot, $object, $view="publish", $user="sys")
{
  global $mgmt_config, $navi_config;

  if (valid_publicationname ($site) && valid_locationname ($docroot) && valid_objectname ($object) && valid_objectname ($user))
  {
    $xmldata = getobjectcontainer ($site, $docroot, $object, $user);
  
    if ($xmldata != false)
    {
      // if show/hide navigation text_id has been defined
      if (!empty ($navi_config['hide_text_id']))
      {
        $hidenode = selectcontent ($xmldata, "<text>", "<text_id>", $navi_config['hide_text_id']);

        if ($hidenode != false)
        {
          $hide = getcontent ($hidenode[0], "<textcontent>");

          if (!empty ($hide[0])) $hideitem = true;
          else $hideitem = false;
        }
        else $hideitem = false;
      }
      else $hideitem = false;

      // if sort order text_id has been defined
      if (!empty ($navi_config['sort_text_id']))
      {
        $sortordernode = selectcontent ($xmldata, "<text>", "<text_id>", $navi_config['sort_text_id']);

        if ($sortordernode != false)
        {
          $sortorder = getcontent ($sortordernode[0], "<textcontent>");
          if (!empty ($sortorder[0])) $sortorder_no = $sortorder[0];
          else $sortorder_no = "X";
        }
        else $sortorder_no = "X";
      }
      else $sortorder_no = "X";

      // if text_id has been defined for the navigation title
      if (!empty ($navi_config['lang_text_id']) && is_array ($navi_config['lang_text_id']))
      {
        // collect navigation titles for each language
        reset ($navi_config['lang_text_id']);
        $navtitles = "";
        $links = "";

        while (list ($key, $value) = each ($navi_config['lang_text_id']))
        {
          // get title
          $textnode = selectcontent ($xmldata, "<text>", "<text_id>", $value);

          if ($textnode != false)
          {
            $title = getcontent ($textnode[0], "<textcontent>");

            if (!empty ($title[0])) $navtitle = $title[0];
            else $navtitle = $object;
          }
          // use object name
          else $navtitle = $object;
 
          // if language session variable has been defined
          if (!empty ($navi_config['lang_session']))
          {
            // for publishing
            if ($navtitle != "" && $view == "publish") $navtitles .= "<?php if (\$_SESSION['".$navi_config['lang_session']."'] == \"".$key."\") { ?>".$navtitle."<?php } ?>";
            // for all other views
            elseif ($navtitle != "" && $key == $_SESSION[$navi_config['lang_session']]) $navtitles .= $navtitle;
          }
          // only single language support
          else $navtitles = $navtitle;

          // get PermaLinks
          if (!empty ($navi_config['permalink_text_id'][$key]))
          {
            $textnode = selectcontent ($xmldata, "<text>", "<text_id>", $navi_config['permalink_text_id'][$key]);

            if ($textnode != false)
            {
              $permalink = getcontent ($textnode[0], "<textcontent>");

              if (!empty ($permalink[0])) $navlink = $permalink[0];
              else $navlink = cleandomain (str_replace ($navi_config['root_path'], $navi_config['root_url'], $docroot.$object));
            }
            else $navlink = cleandomain (str_replace ($navi_config['root_path'], $navi_config['root_url'], $docroot.$object));

            // if language session variable has been defined
            if (!empty ($navi_config['lang_session']))
            {
              // for publishing
              if ($navlink != "" && $view == "publish") $links .= "<?php if (\$_SESSION['".$navi_config['lang_session']."'] == \"".$key."\") { ?>".$navlink."<?php } ?>";
              // only single language support
              elseif ($navlink != "" && $key == $_SESSION[$navi_config['lang_session']]) $links .= str_replace ($navi_config['root_path'], $navi_config['root_url'], $docroot.$object);
            }
            else $links = str_replace ($navi_config['root_path'], $navi_config['root_url'], $docroot.$object);
          }
          // use standard link
          else $links = str_replace ($navi_config['root_path'], $navi_config['root_url'], $docroot.$object);
        }
      }
      // use standard link
      else
      {
        $navtitles = $object;
        $links = str_replace ($navi_config['root_path'], $navi_config['root_url'], $docroot.$object);
      }

      // result
      $result = array();
      $result['title'] = $navtitles;
      $result['order'] = $sortorder_no;
      $result['hide'] = $hideitem;
      $result['link'] = $links;

      return $result;
    }
    else return false;
  }
  else return false;
}

// ------------------------- createnavigation -----------------------------
// function: createnavigation()
// input: publication name, document root for navigation, URL root for navigation, view [see view parameters of function buildview], path to current object (optional), recursive [true,false] (optional)
// output: navigation array / false

// description:
// Generates an associative array (item => nav-item, sub => array with sub-items)

function createnavigation ($site, $docroot, $urlroot, $view="publish", $currentobject="", $recursive=true)
{
  global $mgmt_config, $navi_config;

  if (valid_publicationname ($site) && valid_locationname ($docroot) && $urlroot != "")
  {
    // deconvert path
    if (substr_count ($docroot, "%page%") == 1 || substr_count ($docroot, "%comp%") == 1)
    {
      $docroot = deconvertpath ($docroot, "file");
    }
    
    if (substr_count ($currentobject, "%page%") == 1 || substr_count ($currentobject, "%comp%") == 1)
    {
      $currentobject = deconvertpath ($currentobject, "file");
    }
    
    // add slash if not present at the end of the location string
    if (substr ($docroot, -1) != "/") $docroot = $docroot."/";
    if (substr ($currentobject, -1) != "/") $currentobject = $currentobject."/";
  
    // collect navigation data
    $handler = opendir ($docroot);
    
    if ($handler != false)
    {
      $i = 0;
      $fileitem = array(); 
      $navitem = array();  
  
      while ($file = readdir ($handler))
      {
        if ($file != "." && $file != ".." && substr ($file, -4) != ".off") $fileitem[] = $file;
      }
  
      if (sizeof ($fileitem) > 0)
      {
        natcasesort ($fileitem);
        reset ($fileitem);
    
        foreach ($fileitem as $object)
        {
          // PAGE OBJECT -> standard navigation item
          if (is_file ($docroot.$object) && $object != ".folder")
          {
            $navi = readnavigation ($site, $docroot, $object, $view, "sys");
    
            if ($navi != false && $navi['hide'] == false)
            {
              // navigation display
              if (substr_count ($currentobject, $docroot.$object) == 1) $add_css = $navi_config['attr_li_active'];
              else $add_css = ""; 
    
              $navitem[$navi['order'].'.'.$i]['item'] = $add_css."|".$navi['link']."|".$navi['title'];
              $navitem[$navi['order'].'.'.$i]['sub'] = "";
    
              $i++;
            }
          }
          // FOLDER -> next navigation level
          elseif ($recursive && is_dir ($docroot.$object) && !is_emptyfolder ($docroot.$object))
          {
            $navi = readnavigation ($site, $docroot, $object, $view, "sys");
    
            if (is_array ($navi) && $navi['hide'] == false)
            {
              if ($navi['order'] == "X") $navi['order'] = $i;
    
              // create main item for sub navigation
              $navitem[$navi['order'].'.'.$i]['item'] = $navi_config['attr_li_dropdown']."|#|".$navi['title'];
              $navitem[$navi['order'].'.'.$i]['sub'] = "";
    
              // create sub navigation
              $subnav = createnavigation ($site, $docroot.$object."/", $urlroot.$object."/", $view, $currentobject);
    
              if (is_array ($subnav))
              {
                ksort ($subnav, SORT_NUMERIC);
                reset ($subnav);
                $j = 1;
    
                foreach ($subnav as $key => $value)
                {
                  if (!empty ($navi_config['use_1st_folderitem']) && $j == 1)
                  {
                    $navitem[$navi['order'].'.'.$i]['item'] = $value['item'];
                    $navitem[$navi['order'].'.'.$i]['sub'] = "";
                  }
                  else
                  {
                    $navitem[$navi['order'].'.'.$i]['sub'][$key] = $value;
                  }
                  
                  $j++;
                }
              }
    
              $i++;
            }
          }
        }
      }
  
      closedir ($handler);
    }
    
    if (isset ($navitem) && is_array ($navitem)) return $navitem;
    else return false;
  }
  else return false;
}

// ------------------------- shownavigation -----------------------------
// function: shownavigation()
// input: navigation array (created by function readnavigation), level as integer (optional)
// output: navigation HTML presentation / false

// description:
// display navigation as HTML code.
// 
// The following example configures the navigation:
// $navi_config = array();
// 
// document root definitions:
// $navi_config['root_path'] = "%abs_page%/";
// $navi_config['root_url'] = "%url_page%/";
// 
// HTML / CSS class defintions (names between percentage signs are placeholders):
// $navi_config['attr_ul_top'] = "class=\"nav navbar-nav\"";
// $navi_config['attr_ul_dropdown'] = "class=\"dropdown-menu\"";
// $navi_config['attr_li_active'] = "class=\"active\"";
// $navi_config['attr_li_dropdown'] = "class=\"dropdown\"";
// $navi_config['attr_href_dropdown'] = "class=\"dropdown-toggle\" data-toggle=\"dropdown\"";
// $navi_config['tag_li'] = "<li %attr_li%><a href=\"%link%\" %attr_href%>%title%</a>%sub%</li>\n";
// $navi_config['tag_ul'] = "<ul %attr_ul%>%list%</ul>\n";
// 
// language definitions
// Session variable name that holds the language setting
// $navi_config['lang_session'] = "langcode";
// note: key = langcode & value = text_id of textnode
// $navi_config['lang_text_id']['DE'] = "Titel_DE";
// $navi_config['lang_text_id']['EN'] = "Titel_EN";
//
// PermaLink defintions
// note: key = langcode & value = text_id of textnode
// $navi_config['permalink_text_id']['DE'] = "PermaLink_DE";
// $navi_config['permalink_text_id']['EN'] = "PermaLink_EN";
// 
// Navigation hide and sort order defintions
// $navi_config['hide_text_id'] = "NavigationHide";
// $navi_config['sort_text_id'] = "NavigationSortOrder";
// 
// Use the first item in a folder for the main navigation item and display all following as sub navigation items [true,false]
// $navi_config['use_1st_folderitem'] = false;

function shownavigation ($navigation, $level=1)
{
  global $mgmt_config, $navi_config;
  
  if (is_array ($navigation))
  {
    $out = "";
    $sub = "";
  
    ksort ($navigation, SORT_NUMERIC);
    reset ($navigation);
  
    list ($tag_ul_start, $tag_ul_end) = explode ("%list%", $navi_config['tag_ul']);
    
    if ($level == 1) $out .= str_repeat ("  ", $level) . str_replace ("%attr_ul%", $navi_config['attr_ul_top'], $tag_ul_start."\n");
    else $out .= str_repeat ("  ", $level) . str_replace ("%attr_ul%", $navi_config['attr_ul_dropdown'], $tag_ul_start."\n");
  
    foreach ($navigation as $key => $value)
    {
      list ($css, $link, $title) = explode ("|", $value['item']);

      if (is_array ($value['sub']))
      {
        $out .= str_repeat ("  ", $level) . str_replace (array("%attr_li%", "%attr_href%", "%link%", "%title%"), array($css, $navi_config['attr_href_dropdown'], $link, $title), $navi_config['tag_li']);
        $sub = shownavigation ($value['sub'], ($level+1));

        if ($sub != "") $out = str_replace ("%sub%", $sub, $out);
        else $out = str_replace ("%sub%", "", $out);
      }
      else
      {
        $out .= str_repeat ("  ", $level) . str_replace (array("%attr_li%", "%attr_href%", "%link%", "%title%"), array($css, "", $link, $title), $navi_config['tag_li']);
        $out = str_replace ("%sub%", "", $out);
      }
    }
  
    $out .= $tag_ul_end;
  
    return $out;
  }
  else return false;
}

// ------------------------- showselect -----------------------------
// function: showselect()
// input: values array (array-key = value, array-value = text), use values of array as option value and text [true,false] (optional), selected value (optional), attributes of select tags like name or id or events (optional)
// output: HTML select box presentation / false

function showselect ($value_array, $only_text=false, $selected_value="", $id="", $attributes="")
{
  if (is_array ($value_array) && ($attributes == "" || (strpos ("_".$attributes, "<") < 1 && strpos ("_".$attributes, ">") < 1)))
  {
    if ($id != "" && strpos ("_".$id, "<") < 1 && strpos ("_".$id, ">") < 1) $id = " id=\"".$id."\"";
  
    $result = "  <select".$id." ".$attributes.">\n";
    
    foreach ($value_array as $value=>$text)
    {
      // use option values and text
      if (!$only_text)
      {
        $value = " value=\"".$value."\"";
        
        if ($value == $selected_value) $selected = " selected";
        else $selected = "";
      }
      // use only option text
      else
      {
        $value = "";
        
        if ($text == $selected_value) $selected = " selected";
        else $selected = "";
      }
      
      $result .= "    <option".$value.$selected.">".getescapedtext($text)."</option>\n";
    }
    
    $result .= "  </select>\n";
    
    return $result;
  }
  else return false;
}

// ------------------------- showtranslator -----------------------------
// function: showtranslator()
// input: publication name, editor/text-tag ID, unformatted or formatted texttag-type [u,f], character set (optional), language code (optional), style of div tag (optional)
// output: HTML translator box presentation / false

function showtranslator ($site, $id, $type, $charset="UTF-8", $lang="en", $style="")
{
  global $mgmt_config, $hcms_lang;

  if (valid_publicationname ($site) && $id != "" && ($type == "u" || $type == "f") && !empty ($mgmt_config[$site]['translate']))
  {
    // JS function to be used
    if ($type == "u") $JSfunction = "hcms_translateTextField";
    else $JSfunction = "hcms_translateRichTextField";
    
    // define unique name for button
    $button_id = uniqid();
  
    $result = "
  <div style=\"".$style."\">
    ".getescapedtext ($hcms_lang['translate'][$lang], $charset, $lang)."&nbsp;
    <select id=\"sourceLang_".$id."\" style=\"width:55px;\">
      <option value=\"\">Automatic</option>";

    $langcode_array = getlanguageoptions();

    if ($langcode_array != false)
    {
      foreach ($langcode_array as $code => $lang_short)
      {
        if (substr_count  (",".$mgmt_config[$site]['translate'].",", ",".$code.",") > 0) $result .= "
      <option value=\"".$code."\">".$lang_short."</option>";
      }
    }

    $result .= "
    </select>
    &#10095;
    <select id=\"targetLang_".$id."\" style=\"width:55px;\">";

    $langcode_array = getlanguageoptions();
    
    if ($langcode_array != false)
    {
      foreach ($langcode_array as $code => $lang_short)
      {
        if (substr_count  (",".$mgmt_config[$site]['translate'].",", ",".$code.",") > 0) $result .= "
      <option value=\"".$code."\">".$lang_short."</option>";
      }
    }

    $result .= "
    </select>
    <img name=\"Button_".$button_id."\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" style=\"margin-right:2px;\" src=\"".getthemelocation()."img/button_OK.gif\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button_".$button_id."','','".getthemelocation()."img/button_OK_over.gif',1)\" align=\"absmiddle\" title=\"OK\" alt=\"OK\" onClick=\"".$JSfunction."('".$id."', 'sourceLang_".$id."', 'targetLang_".$id."');\" />
  </div>";
    
    return $result;
  }
  else return false;
}
?>