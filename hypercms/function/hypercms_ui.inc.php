<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */
 
// ================================ USER INTERFACE / LAYOUT ITEMS ===============================

// --------------------------------------- windowwidth -------------------------------------------
// function: windowwidth ()
// input: type [string] (optional)
// output: window width in pixels

// description:
// Returns the width of the object window when editing/opening an object

function windowwidth ($type="object")
{
  global $mgmt_config;

  if ($type == "object" && !empty ($mgmt_config['window_object_width']) && $mgmt_config['window_object_width'] > 0) return $mgmt_config['window_object_width'] ;
  else return 800;
}

// --------------------------------------- windowheight -------------------------------------------
// function: windowheight ()
// input: type [string] (optional)
// output: window height in pixels

// description:
// Returns the height of the object window when editing/opening an object

function windowheight ($type="object")
{
  global $mgmt_config;

  if ($type == "object" && !empty ($mgmt_config['window_object_height']) && $mgmt_config['window_object_height'] > 0) return $mgmt_config['window_object_height'] ;
  else return 1000;
}

// --------------------------------------- toggleexplorerview -------------------------------------------
// function: toggleexplorerview ()
// input: view [detail,small,medium,large]
// output: true / false

// description:
// Set explorer objectlist view parameter

function toggleexplorerview ($view)
{
  global $mgmt_config;

  // register explorer view type
  // if set manually
  if (!empty ($view) && ($view == "detail" || $view == "small" || $view == "medium" || $view == "large"))
  {
    setsession ('hcms_temp_explorerview', $view, true);
  }
  // if not set and object linking is used, use medium gallery view
  elseif (empty ($_SESSION['hcms_temp_explorerview']) && is_array ($_SESSION['hcms_linking']))
  {
    setsession ('hcms_temp_explorerview', "small", true);
  }
  // if not set at all
  elseif (
    empty ($_SESSION['hcms_temp_explorerview']) && $mgmt_config['explorerview'] != "" && 
    ($mgmt_config['explorerview'] == "detail" || $mgmt_config['explorerview'] == "small" || $mgmt_config['explorerview'] == "medium" || $mgmt_config['explorerview'] == "large")
  )
  {
    setsession ('hcms_temp_explorerview', $mgmt_config['explorerview'], true);
  }
  else return false;


  // save GUI settings
  if (!empty ($_SESSION['hcms_temp_objectview']) && !empty ($_SESSION['hcms_temp_explorerview']) && isset ($_SESSION['hcms_temp_sidebar']) && !empty ($_SESSION['hcms_user']))
  {
    setguiview ($_SESSION['hcms_temp_objectview'], $_SESSION['hcms_temp_explorerview'], $_SESSION['hcms_temp_sidebar'], $_SESSION['hcms_user']);
  }

  return true;
}

// --------------------------------------- toggletaskview -------------------------------------------
// function: toggletaskview ()
// input: view [boolean]
// output: true / false

// description:
// Set task view parameter

function toggletaskview ($view)
{
  global $mgmt_config;

  // register task view
  if (!empty ($view) && $view != "false")
  {
    setsession ('hcms_temp_taskview', $view, true);
  }

  return true;
}

// --------------------------------------- togglesidebar -------------------------------------------
// function: togglesidebar ()
// input: view [boolean]
// output: true / false

// description:
// Enables or disables the sidebar

function togglesidebar ($view)
{
  global $mgmt_config;

  // register sidebar
  if (!empty ($view) && $view != "false")
  {
    setsession ('hcms_temp_sidebar', true, true);
  }
  else
  {
    setsession ('hcms_temp_sidebar', false, true);
  }

  // save GUI settings
  if (!empty ($_SESSION['hcms_temp_objectview']) && !empty ($_SESSION['hcms_temp_explorerview']) && isset ($_SESSION['hcms_temp_sidebar']) && !empty ($_SESSION['hcms_user']))
  {
    setguiview ($_SESSION['hcms_temp_objectview'], $_SESSION['hcms_temp_explorerview'], $_SESSION['hcms_temp_sidebar'], $_SESSION['hcms_user']);
  }

  return true;
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
    if (isset ($_SESSION['hcms_objectfilter'])) unset ($_SESSION['hcms_objectfilter']);

    $_SESSION['hcms_objectfilter'] = array();

    $filters_active = false;

    foreach ($filter_names as $filter)
    {
      // register only active filters in session
      if (!empty ($filter_set[$filter]) && ($filter_set[$filter] == 1 || $filter_set[$filter] == "1" || strtolower($filter_set[$filter]) == "yes"))
      {
        $_SESSION['hcms_objectfilter'][$filter] = 1;
        
        $filters_active = true;
      }
    }

    // if no filter has been set
    if ($filters_active == false)
    {
      unset ($_SESSION['hcms_objectfilter']);
    }

    return true;
  }
  else return false;
}

// --------------------------------------- objectfilter -------------------------------------------
// function: objectfilter ()
// input: file name [string]
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
      elseif ($filter == "image" && $value == 1) $ext .= strtolower ($hcms_ext['image'].$hcms_ext['rawimage'].$hcms_ext['vectorimage'].$hcms_ext['cad']);
      elseif ($filter == "document" && $value == 1) $ext .= strtolower ($hcms_ext['bintxt'].$hcms_ext['cleartxt']);
      elseif ($filter == "video" && $value == 1) $ext .= strtolower ($hcms_ext['video'].$hcms_ext['rawvideo']);
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

// --------------------------------------- getinvertcolortheme -------------------------------------------
// function: getinvertcolortheme ()
// input: design theme name for CSS class hcmsToolbarBlock [string]
// output: result array with inverted themes names or empty names if an inversion is not required based on the brightness of the background color.

// description:
// Used for portals in order to get the inverted theme names of the primary and hover colors based on the brightness of the primary and hover background color.

function getinvertcolortheme ($themename)
{
  global $mgmt_config;

  // initialize
  $result = array();
  $result['themeinvertcolors'] = "";
  $result['hoverinvertcolors'] = "";

  // get design theme and primary color if a portal theme is used
  if (!empty ($themename) && strpos ($themename, "/") > 0)
  {
    list ($portal_site, $portal_theme) = explode ("/", $themename);

    if (valid_objectname ($portal_theme))
    {
      $portal_template = $portal_theme.".portal.tpl";
      $portal_template = loadtemplate ($portal_site, $portal_template);
    }
    
    // get design theme and primary color
    if (!empty ($portal_template['content']))
    {
      $temp_portaltheme = getcontent ($portal_template['content'], "<designtheme>");
      $temp_portalcolor = getcontent ($portal_template['content'], "<primarycolor>");
      $temp_hovercolor = getcontent ($portal_template['content'], "<hovercolor>");

      if (!empty ($temp_portaltheme[0]) && !empty ($temp_portalcolor[0]))
      {
        list ($portalsite, $portaltheme) = explode ("/", $temp_portaltheme[0]);
        $brightness_portalcolor = getbrightness ($temp_portalcolor[0]);
        $brightness_hovercolor = getbrightness ($temp_hovercolor[0]);

        if ($portaltheme == "day" && $brightness_portalcolor < 130) $result['themeinvertcolors'] = "night";
        elseif ($portaltheme == "night" && $brightness_portalcolor >= 130) $result['themeinvertcolors'] = "day";

        if ($portaltheme == "day" && $brightness_hovercolor < 130) $result['hoverinvertcolors'] = "night";
        elseif ($portaltheme == "night" && $brightness_hovercolor >= 130) $result['hoverinvertcolors'] = "day";
      }
    }
  }

  return $result;
}

// --------------------------------------- invertcolorCSS -------------------------------------------
// function: invertcolorCSS ()
// input: design theme name for CSS class hcmsToolbarBlock [string] (optional), CSS selector for elements [string] (optional), 
//        use class when no event is triggered [boolean] (optional), use class for hover event [boolean] (optional), invert percentage value [integer] (optional)
// output: CSS style code / false on error

// description:
// Used for portals in order to invert the color of elements.
// MS IE does not support invert, MS Edge does.

function invertcolorCSS ($theme="", $css_selector=".hcmsInvertColor", $default=true, $hover=false, $percentage=100)
{
  global $mgmt_config;

  $result = "";

  // invert colors
  if ($css_selector != "" && intval ($percentage) >= 0)
  {
    if ($default == true) $invertpercentage = 100 - intval ($percentage);
    else $invertpercentage = $percentage;

    // invert
    if ($default == true) $result .= "
  ".$css_selector." > span
  {
    -webkit-filter: invert(".intval ($percentage)."%);
    -o-filter: invert(".intval ($percentage)."%);
    -moz-filter: invert(".intval ($percentage)."%);
    -ms-filter: invert(".intval ($percentage)."%);
    filter: invert(".intval ($percentage)."%);
  }";
  
  // invert on hover
  if ($hover == true) $result .= "

  ".$css_selector.":hover > img
  {
    -webkit-filter: invert(".intval ($percentage)."%);
    -o-filter: invert(".intval ($percentage)."%);
    -moz-filter: invert(".intval ($percentage)."%);
    -ms-filter: invert(".intval ($percentage)."%);
    filter: invert(".intval ($percentage)."%);
  }
  
  ".$css_selector.":hover > span
  {
    -webkit-filter: invert(".intval ($invertpercentage)."%);
    -o-filter: invert(".intval ($invertpercentage)."%);
    -moz-filter: invert(".intval ($invertpercentage)."%);
    -ms-filter: invert(".intval ($invertpercentage)."%);
    filter: invert(".intval ($invertpercentage)."%);
  }";
  }

  // set color for border for standard CSS class
  if ($css_selector == ".hcmsInvertColor" && $default == true)
  {
    if ($theme == "day") $color = "#000000";
    elseif ($theme == "night") $color = "#FFFFFF";

    if (!empty ($color)) $result .= "

  .hcmsToolbarBlock
  {
    border-color: ".$color."; 
  }";
  }

  return $result;
}

// --------------------------------------- showdate -------------------------------------------
// function: showdate ()
// input: date and time [string, date input format [string], date output format [string], correct time zone [boolean] (optional)
// output: date and time

// description:
// Prepares the date and time for the display in the users time zone and format.

function showdate ($date, $sourceformat="Y-m-d H:i", $targetformat="Y-m-d H:i", $timezone=true)
{
  global $mgmt_config;

  if ($date != "" && $date != "0000-00-00 00:00:00" && is_date ($date, $sourceformat))
  {
    // remove time from target if not present in source format
    if (stripos ("_".$sourceformat, "H:i") < 1 && stripos ("_".$sourceformat, "G:i") < 1)
    {
      $targetformat = trim (str_replace ("H:i:s", "", $targetformat));
      $targetformat = trim (str_replace ("G:i:s", "", $targetformat));
      $targetformat = trim (str_replace ("h:i:s", "", $targetformat));
      $targetformat = trim (str_replace ("g:i:s", "", $targetformat));
    }
    // remove seconds from target if not present in source format
    elseif (stripos ("_".$sourceformat, ":s") < 1)
    {
      $targetformat = trim (str_replace ("H:i:s", "H:i", $targetformat));
      $targetformat = trim (str_replace ("G:i:s", "G:i", $targetformat));
      $targetformat = trim (str_replace ("h:i:s", "h:i", $targetformat));
      $targetformat = trim (str_replace ("g:i:s", "g:i", $targetformat));
    }

    // convert date and time based on users time zone
    if ($timezone == true && !empty ($_SESSION['hcms_timezone']) && ini_get ('date.timezone') && $_SESSION['hcms_timezone'] != ini_get ('date.timezone'))
    {
      $datenew = convertdate ($date, ini_get ('date.timezone'), $sourceformat, $_SESSION['hcms_timezone'], $targetformat);
    }
    // convert date format
    elseif ($targetformat != "")
    {
      $datenew = date ($targetformat, strtotime ($date));
    }

    if (!empty ($datenew)) return $datenew;
    else return $date;
  }
  // null date from database
  elseif ($date == "0000-00-00 00:00:00")
  {
    return "";
  }
  else
  {
    return $date;
  }
}

// --------------------------------------- showshorttext -------------------------------------------
// function: showshorttext ()
// input: text [string], max. positive length of text or minus value for the length starting from the end [integer] (optional),
//        use max. 3 line breaks instead of cut only if length is positive [boolean] (optional), character set for encoding [string] (optional)
// output: shortened text if possible, or orignal text

// description:
// Reduce the length of a string and add "..." at the end

function showshorttext ($text, $length=0, $linebreak=false, $charset="UTF-8")
{
  $text = trim ($text);

  // cut or break after certain length
  if ($text != "" && $length > 0)
  {
    // no line breaks
    if (!$linebreak)
    {
      if (mb_strlen ($text, $charset) > $length)
      {
        return mb_substr ($text, 0, $length, $charset)."...";
      }
      else return $text;
    }
    // use line breaks
    elseif (intval ($linebreak) > 0)
    {
      if (intval ($linebreak) > 3) $linebreak = 3;

      if ($linebreak = 3)
      {
        if (mb_strlen ($text, $charset) > ($length * 3)) $text = trim (mb_substr ($text, 0, $length, $charset))."<br />\n".trim (mb_substr ($text, $length, $length, $charset))."<br />\n".trim (mb_substr ($text, ($length*2), $length, $charset))."...";
        elseif (mb_strlen ($text, $charset) > ($length * 2)) $text = trim (mb_substr ($text, 0, $length, $charset))."<br />\n".trim (mb_substr ($text, $length, $length, $charset))."<br />\n".trim (mb_substr ($text, ($length*2), NULL, $charset));
        elseif (mb_strlen ($text, $charset) > ($length * 1)) $text = trim (mb_substr ($text, 0, $length, $charset))."<br />\n".trim (mb_substr ($text, $length, NULL, $charset));
      }
      elseif ($linebreak = 2)
      {
        if (mb_strlen ($text, $charset) > ($length * 2)) $text = trim (mb_substr ($text, 0, $length, $charset))."<br />\n".trim (mb_substr ($text, $length, $length, $charset))."...";
        elseif (mb_strlen ($text, $charset) > ($length * 1)) $text = trim (mb_substr ($text, 0, $length, $charset))."<br />\n".trim (mb_substr ($text, $length, NULL, $charset));
      }
      elseif ($linebreak = 1)
      {
        if (mb_strlen ($text, $charset) > $length) $text = trim (mb_substr ($text, 0, $length, $charset))."...";
      }

      return $text;
    }
  }
  // replace white space by line break
  elseif ($text != "" && $length == 0 && intval ($linebreak) > 0)
  {
    $text = wordwrap ($text, 8, "\n", false);
    $temp_array = explode ("\n", $text);

    if (sizeof ($temp_array) > 3) 
    {
      $text = $temp_array[0];

      for ($i = 1; $i <= intval ($linebreak); $i++)
      {
        $text = "<br />\n".$temp_array[$i];
      }
    }

    return $text;
  }
  // cut from end
  elseif ($text != "" && $length < 0)
  {
    if (mb_strlen ($text, $charset) > ($length * -1)) return "...".trim (mb_substr ($text, $length, $charset));
    else return $text;
  }
  else return $text;
}

// --------------------------------------- showtopbar -------------------------------------------
// function: showtopbar ()
// input: message [string], language code [string] (optional), close button link [string] (optional), link target [string] (optional), individual button [string] (optional), ID of div-layer [string] (optional)
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
      $close_button_code = "<td style=\"width:36px; text-align:center; vertical-align:right; padding:2px 2px 2px 0px;\"><a href=\"".$close_link."\" target=\"".$close_target."\" onMouseOut=\"hcms_swapImgRestore();\" onMouseOver=\"hcms_swapImage('close_".$close_id."','','".getthemelocation()."img/button_close_over.png',1);\"><img name=\"close_".$close_id."\" src=\"".getthemelocation()."img/button_close.png\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['close'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['close'][$lang], $hcms_charset, $lang)."\" /></a></td>\n";
    }

    if (trim ($individual_button) != "")
    {
      $individual_button_code = "<td style=\"width:36px; text-align:right; vertical-align:middle; padding:2px 2px 2px 0px;\">".$individual_button."</td>";
    }

    return "
  <div id=\"".$id."\" class=\"hcmsWorkplaceBar\">
    <table style=\"box-sizing:border-box; width:100%; min-height:36px; padding:0; border-spacing:0; border-collapse:collapse;\">
      <tr>
        <td class=\"hcmsHeadline\" style=\"text-align:left; vertical-align:middle; padding:0px 6px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\">".$show."</td>".
        $individual_button_code.$close_button_code.
      "</tr>
    </table>
  </div>
  <div style=\"width:100%; height:40px;\">&nbsp;</div>";
  }
  else return false;
}

// --------------------------------------- showtopmenubar -------------------------------------------
// function: showtopmenubar ()
// input: message [string], menu [array:name => properties/events], language code [string] (optional), close button link [string] (optional), link target [string] (optional), ID of div-layer [string] (optional)
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
      $close_button = "<td style=\"width:36px; text-align:right; vertical-align:middle; padding-right:2px;\"><a href=\"".$close_link."\" target=\"".$close_target."\" onMouseOut=\"hcms_swapImgRestore();\" onMouseOver=\"hcms_swapImage('close_".$close_id."','','".getthemelocation()."img/button_close_over.png',1);\"><img name=\"close_".$close_id."\" src=\"".getthemelocation()."img/button_close.png\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['close'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['close'][$lang], $hcms_charset, $lang)."\" /></a></td>\n";
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
    <table style=\"box-sizing:border-box; width:100%; min-height:36px; padding:0; border-spacing:0; border-collapse:collapse;\">
      <tr>
        <td class=\"hcmsHeadline\" style=\"width:80px; text-align:left; vertical-align:middle; padding:0px 6px; white-space:nowrap;\">".$show."</td>
        <td style=\"text-align:left; vertical-align:middle; padding:0;\">".$menu_button."</td>".
        $close_button.
      "</tr>
    </table>
  </div>
  <div style=\"width:100%; height:40px;\">&nbsp;</div>";
  }
  else return false;
}

// --------------------------------------- showhomeboxes -------------------------------------------
// function: showhomeboxes ()
// input: home box names [array:file name => readable name]
// output: path to home boxes [array] / false on error

// description:
// Returns the file path of the home boxes as array. This function does not directly return the rendered HTML code. Therefore the home boxes need to be included in order to display them.

function showhomeboxes ($homebox_array)
{
  global $mgmt_config;

  if (!empty ($homebox_array) && is_array ($homebox_array))
  {
    // initialize
    $result = array();

    // remove duplicates
    $homebox_array = array_unique ($homebox_array);

    // remove trailing slashes
    if (!empty ($mgmt_config['homeboxes_directory'])) $mgmt_config['homeboxes_directory'] = trim ($mgmt_config['homeboxes_directory'], "/");

    // show boxes
    foreach ($homebox_array as $homebox_key => $homebox_name)
    {
      if ($homebox_name != "" && valid_locationname ($homebox_key))
      {
        // individual home boxes of a publication (publication/name)
        if (!empty ($mgmt_config['homeboxes_directory']) && valid_locationname ($homebox_key) && strpos ($homebox_key, "/") > 0)
        {
          list ($site, $name) = explode ("/", $homebox_key);

          if (is_file ($mgmt_config['abs_path_comp'].$site."/".$mgmt_config['homeboxes_directory']."/".$name.".php"))
          {
            $result[] = $mgmt_config['abs_path_comp'].$site."/".$mgmt_config['homeboxes_directory']."/".$name.".php";
          }
        }
        // system home boxes (name)
        elseif (valid_objectname ($homebox_key) && is_file ($mgmt_config['abs_path_cms']."box/".$homebox_key.".inc.php"))
        {
          $result[] = $mgmt_config['abs_path_cms']."box/".$homebox_key.".inc.php";
        }
      }
    }

    // return result
    if (sizeof ($result) > 0) return $result;
    else return false;
  }
  else return false;
}

// --------------------------------------- showmessage -------------------------------------------
// function: showmessage ()
// input: message [string], width in pixel [integer] (optional), height in pixel [integer] (optional), language code [string] (optional), additional style definitions of div-layer [string] (optional), ID of div-layer [string] (optional)
// output: message box / false on error

// description:
// Returns the standard message box icluding a close button.
// The message box has a specific size.

function showmessage ($show, $width="580px", $height="80px", $lang="en", $style="", $id="hcms_messageLayer")
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

    // define unique id if default id is used
    if ($id == "hcms_messageLayer") $id .= "_".$close_id;

    return "
  <div id=\"".$id."\" class=\"hcmsMessage\" style=\"".$style." width:".$width."; height:".$height.";\">
    <table style=\"table-layout:fixed; width:100% !important; min-height:calc(".$height." -  10px); padding:0; margin:0; border-spacing:0; border-collapse:collapse;\">
      <tr>
        <td style=\"text-align:left; vertical-align:top; padding:0;\">
          <div id=\"".$id."_text\" style=\"display:block; width:100%; height:".(intval ($height) - 10)."px; overflow:auto;\">".$show."</div>
        </td>
        <td style=\"width:36px; text-align:right; vertical-align:top; padding:0;\">
          <img name=\"close_".$close_id."\" src=\"".getthemelocation()."img/button_close.png\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['close'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['close'][$lang], $hcms_charset, $lang)."\" onMouseOut=\"hcms_swapImgRestore();\" onMouseOver=\"hcms_swapImage('close_".$close_id."','','".getthemelocation()."img/button_close_over.png',1);\" onClick=\"hcms_switchFormLayer('".$id."');\" />
        </td>
      <tr>
    </table>
  </div>";
  }
  else return false;
}

// --------------------------------------- showinfopage -------------------------------------------
// function: showinfopage ()
// input: message [string], language code [string] (optional), on load JS events [string] (optional)
// output: message on html info page / false on error

// description:
// Returns a full html info page.

function showinfopage ($show, $lang="en", $onload="")
{
  global $mgmt_config, $hcms_charset, $hcms_lang_codepage, $hcms_lang, $is_mobile;

  if ($show != "" && strlen ($show) < 2400 && $lang != "")
  {
    return "<!DOCTYPE html>
<html>
  <head>
    <title>hyperCMS</title>
    <meta charset=\"".getcodepage ($lang)."\" />
    <link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css?v=".getbuildnumber()."\" />
    <link rel=\"stylesheet\" href=\"".getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css")."?v=".getbuildnumber()."\" />
  </head>
  <body class=\"hcmsWorkplaceGeneric\" onload=\"".$onload."\">
    <div style=\"padding:20px;\">
      <img src=\"".getthemelocation()."img/info.png\" class=\"hcmsButtonSizeSquare\" /> <span class=\"hcmsHeadline\">Info</span><br \>
      <div style=\"display:block; padding:0px 0px 0px 32px;\">".$show."</div>
    </div>
  </body>
</html>";
  }
  else return false;
}

// --------------------------------------- showinfobox -------------------------------------------
// function: showinfobox ()
// input: message [string], language code [string] (optional), additional style definitions of div-layer [string] (optional), ID of div-layer [string] (optional)
// output: message in div layer / false on error

// description:
// Returns the infobox as long as it has not been closed. Saves the close event in localstorage of browser.
// The infobox does not have a specific size by default compared to the message box.

function showinfobox ($show, $lang="en", $style="", $id="")
{
  global $mgmt_config, $hcms_charset, $hcms_lang_codepage, $hcms_lang;

  if (!empty ($mgmt_config['showinfobox']) && $show != "" && strlen ($show) < 2400 && $lang != "")
  {
    // define unique name for close button
    $close_id = uniqid();

    // define unique id for the infobox
    if (trim ($id) == "") $id = "hcms_infoboxLayer_".$close_id;

    // do not use the CSS setting table-layout:fixed since the div does not provide a size
    return "
  <div id=\"".$id."\" class=\"hcmsInfoBox\" style=\"display:none; ".$style."\">
    <table style=\"width:100%; height:100%; padding:0; margin:0; border-spacing:0; border-collapse:collapse;\">
      <tr>
        <td style=\"text-align:left; vertical-align:top; padding:0;\">
          ".$show."
        </td>
        <td style=\"width:36px; text-align:right; vertical-align:top; padding:0;\">
          <img name=\"close_".$close_id."\" src=\"".getthemelocation()."img/button_close.png\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['close'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['close'][$lang], $hcms_charset, $lang)."\" onMouseOut=\"hcms_swapImgRestore();\" onMouseOver=\"hcms_swapImage('close_".$close_id."','','".getthemelocation()."img/button_close_over.png',1);\" onClick=\"localStorage.setItem('".$id."','no'); hcms_switchFormLayer('".$id."');\" />
        </td>
      <tr>
    </table>
  </div>
  <script type=\"text/javascript\">
  var hcms_showFormLayerbox = localStorage.getItem('".$id."') || 'yes';
  if (hcms_showFormLayerbox=='yes') document.getElementById('".$id."').style.display='inline';
  else document.getElementById('".$id."').style.display='none';
  </script>";
  }
  else return false;
}

// --------------------------------------- showhelpbutton -------------------------------------------
// function: showhelpbutton ()
// input: PDF file name without '_langcode' and file extension [$string], enabled [boolean] (optional), language code [string] (optional), ID of div-layer [string] (optional), add CSS class name [string] (optional)
// output: button as img tag

// description:
// Returns the help button including the help document functionality.

function showhelpbutton ($pdf_name, $enabled=true, $lang="en", $id="hcms_helpButton", $css_class="")
{
  global $mgmt_config, $hcms_themeinvertcolors, $hcms_lang_codepage, $hcms_lang_shortcut, $hcms_lang;

  // Path to PDF.JS
  $pdfjs_path = $mgmt_config['url_path_cms']."javascript/pdfpreview/web/viewer.html?file=";

  // verify PDF file
  if (!empty ($enabled) && valid_objectname ($pdf_name) && $lang != "")
  {
    if (is_file ($mgmt_config['abs_path_cms']."help/".$pdf_name."_".$hcms_lang_shortcut[$lang].".pdf")) $help = cleandomain ($mgmt_config['url_path_cms'])."help/".$pdf_name."_".$hcms_lang_shortcut[$lang].".pdf";
    elseif (is_file ($mgmt_config['abs_path_cms']."help/".$pdf_name."_en.pdf")) $help = cleandomain ($mgmt_config['url_path_cms'])."help/".$pdf_name."_en.pdf";
  }

  // enabled button
  if (!empty ($help))
  {
    $viewer = $pdfjs_path.urlencode($help);

    return "
    <div class=\"hcmsButton hcmsHoverColor ".$css_class." hcmsButtonSizeSquare\">
      <img id=\"".$id."\" onClick=\"hcms_openWindow('".$viewer."', 'help', 'location=no,menubar=no,toolbar=no,titlebar=no,location=no,scrollbars=no,resizable=yes,status=no', ".windowwidth("object").", ".windowheight("object").");\" ".
      "src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_help.png\" class=\"hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" />
    </div>";
  }
  // disabled button
  else
  {
    return "<img id=\"".$id."\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_help.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
  }
}

// --------------------------------------- showactionicon -------------------------------------------
// function: showactionicon ()
// input: action name [$string], language code [string] (optional), CSS style [string] (optional), ID of image-layer [string] (optional)
// output: icon as img tag / empty string on error

// description:
// Returns the icon image for an action

function showactionicon ($action, $lang="en", $style="width:64px; height:64px;", $id="hcms_icon")
{
  global $mgmt_config, $hcms_charset, $hcms_lang_codepage, $hcms_lang;

  if (!empty ($action) && !empty ($lang))
  {
    if ($action == "page_favorites_create") $icon = "<img src=\"".getthemelocation()."img/button_favorites_new.png\" id=\"".$id."\" style=\"".$style."\" alt=\"".getescapedtext ($hcms_lang['add-to-favorites'][$lang])."\" />";
    elseif ($action == "page_favorites_delete") $icon = "<img src=\"".getthemelocation()."img/button_favorites_delete.png\" id=\"".$id."\" style=\"".$style."\" alt=\"".getescapedtext ($hcms_lang['delete-favorite'][$lang])."\" />";
    elseif ($action == "page_lock") $icon = "<img src=\"".getthemelocation()."img/button_file_lock.png\" id=\"".$id."\" style=\"".$style."\" alt=\"".getescapedtext ($hcms_lang['check-out'][$lang])."\" />";
    elseif ($action == "page_unlock") $icon = "<img src=\"".getthemelocation()."img/button_file_unlock.png\" id=\"".$id."\" style=\"".$style."\" alt=\"".getescapedtext ($hcms_lang['check-in'][$lang])."\" />";
    elseif ($action == "zip") $icon = "<img src=\"".getthemelocation()."img/button_zip.png\" id=\"".$id."\" style=\"".$style."\" alt=\"".getescapedtext ($hcms_lang['compress-files'][$lang])."\" />";
    elseif ($action == "unzip") $icon = "<img src=\"".getthemelocation()."img/button_unzip.png\" id=\"".$id."\" style=\"".$style."\" alt=\"".getescapedtext ($hcms_lang['uncompress-files'][$lang])."\" />";
    elseif ($action == "cut") $icon = "<img src=\"".getthemelocation()."img/button_file_cut.png\" id=\"".$id."\" style=\"".$style."\" alt=\"".getescapedtext ($hcms_lang['cut'][$lang])."\" />";
    elseif ($action == "copy") $icon = "<img src=\"".getthemelocation()."img/button_file_copy.png\" id=\"".$id."\" style=\"".$style."\" alt=\"".getescapedtext ($hcms_lang['copy'][$lang])."\" />";
    elseif ($action == "linkcopy") $icon = "<img src=\"".getthemelocation()."img/button_file_copylinked.png\" id=\"".$id."\" style=\"".$style."\" alt=\"".getescapedtext ($hcms_lang['connected-copy'][$lang])."\" />";
    elseif ($action == "paste") $icon = "<img src=\"".getthemelocation()."img/button_file_paste.png\" id=\"".$id."\" style=\"".$style."\" alt=\"".getescapedtext ($hcms_lang['paste'][$lang])."\" />";
    elseif ($action == "delete" || $action == "deletemark") $icon = "<img src=\"".getthemelocation()."img/button_delete.png\" id=\"".$id."\" style=\"".$style."\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" />";
    elseif ($action == "emptybin") $icon = "<img src=\"".getthemelocation()."img/button_recycle_bin.png\" id=\"".$id."\" style=\"".$style."\" alt=\"".getescapedtext ($hcms_lang['empty-recycle-bin'][$lang])."\" />";
    elseif ($action == "restore" || $action == "deleteunmark") $icon = "<img src=\"".getthemelocation()."img/button_recycle_bin.png\" id=\"".$id."\" style=\"".$style."\" alt=\"".getescapedtext ($hcms_lang['restore'][$lang])."\" />";
    elseif ($action == "publish") $icon = "<img src=\"".getthemelocation()."img/button_file_publish.png\" id=\"".$id."\" style=\"".$style."\" alt=\"".getescapedtext ($hcms_lang['publish-content'][$lang])."\" />";
    elseif ($action == "unpublish") $icon = "<img src=\"".getthemelocation()."img/button_file_unpublish.png\" id=\"".$id."\" style=\"".$style."\" alt=\"".getescapedtext ($hcms_lang['unpublish-content'][$lang])."\" />";

    return $icon;
  }
  else return "";
}

// --------------------------------------- showsharelinks -------------------------------------------
// function: showsharelinks ()
// input: link to share [string], media file name [string], language code [string] (optional), additional style definitions of div-layer [string] (optional), ID of div-layer [string] (optional)
// output: message in div layer / false on error

// description:
// Returns the presenation of share links of social media platforms

function showsharelinks ($link, $mediafile, $lang="en", $style="", $id="hcms_shareLayer")
{
  global $mgmt_config, $hcms_charset, $hcms_lang_codepage, $hcms_lang;
 
  if (is_dir ($mgmt_config['abs_path_cms']."connector/") && $link != "" && $lang != "" && $id != "")
  {
    if ($style == "") $style = "width:46px;";

    $result = "
  <div id=\"".$id."\" class=\"hcmsInfoBox\" style=\"".$style."\">".
    getescapedtext ($hcms_lang['share'][$lang])."<br />";

    // Facebook (only images are supported by GET API)
    if (is_image ($mediafile)) $result .= "
    <img src=\"".getthemelocation()."img/icon_facebook.png\" title=\"Facebook\" class=\"hcmsButton\" onclick=\"if (hcms_sharelinkFacebook('".$link."') == false) alert('".getescapedtext ($hcms_lang['required-input-is-missing'][$lang])."');\" /><br />";

    // Twitter (support also videos and audio files as external links via GET API)
    $result .= "
    <img src=\"".getthemelocation()."img/icon_twitter.png\" title=\"Twitter\" class=\"hcmsButton\" onclick=\"if (hcms_sharelinkTwitter('".$link."', hcms_getcontentByName('textu_Description')) == false) alert('".getescapedtext ($hcms_lang['required-input-is-missing'][$lang])."');\" /><br />";

    // LinkedIn (only images are supported by GET API)
    if (is_image ($mediafile)) $result .= "
    <img src=\"".getthemelocation()."img/icon_linkedin.png\" title=\"LinkedIn\" class=\"hcmsButton\" onclick=\"if (hcms_sharelinkLinkedin('".$link."', hcms_getcontentByName('textu_Title'), hcms_getcontentByName('textu_Description'), hcms_getcontentByName('Creator')) == false) alert('".getescapedtext ($hcms_lang['required-input-is-missing'][$lang])."');\" /><br />";

    // Pinterest (only images are supported by GET API)
    if (is_image ($mediafile)) $result .= "
    <img src=\"".getthemelocation()."img/icon_pinterest.png\" title=\"Pinterest\" class=\"hcmsButton\" onclick=\"if (hcms_sharelinkPinterest('".$link."', hcms_getcontentByName('textu_Description')) == false) alert('".getescapedtext ($hcms_lang['required-input-is-missing'][$lang])."');\" /><br />";

    // Google+ (support also videos and audio files as external links via GET API)
    // $result .= "
    // <img src=\"".getthemelocation()."img/icon_googleplus.png\" title=\"Google+\" class=\"hcmsButton\" onclick=\"if (hcms_sharelinkGooglePlus('".$link."') == false) alert('".getescapedtext ($hcms_lang['required-input-is-missing'][$lang])."');\" /><br />
    $result .= "
  </div>";

    return $result;
  }
  else return false;
}

// --------------------------------------- showmetadata -------------------------------------------
// function: showmetadata ()
// input: metadata [array], 2 digits language code [string], CSS-class with background-color for headlines [string] (optional)
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

        // base64 encoded signature image
        if (strpos ("_".$value, "image/gif;base64") > 0 || strpos ("_".$value, "image/png;base64") > 0 || strpos ("_".$value, "image/jpeg;base64") > 0 || strpos ("_".$value, "image/svg;base64") > 0 || strpos ("_".$value, "image/webp;base64") > 0)
        {
          $value = "<img src=\"data:".strip_tags ($value)."\" />";
        }

        // html encode string
        $key = html_encode (strip_tags ($key), $hcms_charset_source);
        // html encode string
        $value = html_encode (strip_tags ($value), $hcms_charset_source);

        $result .= "  <li><div style=\"width:200px; float:left;\"><strong>".$key."</strong></div><div style=\"float:left;\">".$value."</div><div style=\"clear:both;\"></div></li>\n";
      }
    }

    $result .= "</ul>";

    if (strlen ($result) > 9) return $result;
    else return false;
  }
  else return false;
}

// --------------------------------------- showobject -----------------------------------------------
// function: showobject ()
// input: publication name [string], location [string], object name [string], category [page,comp] (optional), object name [string] (optional)
// output: html presentation / false

function showobject ($site, $location, $page, $cat="", $name="")
{
  global $mgmt_config, $hcms_charset, $hcms_lang, $hcms_lang_date, $lang, $user;

  $location = deconvertpath ($location, "file");

  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page) && is_file ($location.$page))
  {
    $owner = "";
    $date_created = "";
    $date_modified = "";
    $date_published = "";
    $filecount = 0;
    $filesize = 0;

    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location.$page);

    $location_esc = convertpath ($site, $location.$page, $cat);

    // get object info
    $object_info = getobjectinfo ($site, $location, $page, "sys");

    // container ID
    if (!empty ($object_info['container_id'])) $container_id = $object_info['container_id'];

    // define object name
    if ($name == "" && !empty ($object_info['name'])) $name = $object_info['name'];

    if (!empty ($container_id) && intval ($container_id) > 0)
    {
      // get dates and owner
      $container_info = getmetadata_container ($container_id, array("date", "createdate", "publishdate", "user"));
      
      if (!empty ($container_info['user']))
      {
        $owner = $container_info['user'];
        $date_created = $container_info['createdate'];
        $date_modified = $container_info['date'];
        $date_published = $container_info['publishdate'];
      }
      // use content container
      else
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
        else $contentdata = loadcontainer ($container_id, "work", "sys");

        $temp = getcontent ($contentdata, "<contentuser>");
        if (!empty ($temp[0])) $owner = $temp[0];
        $temp = getcontent ($contentdata, "<contentcreated>");
        if (!empty ($temp[0])) $date_created = $temp[0];
        $temp = getcontent ($contentdata, "<contentdate>");
        if (!empty ($temp[0])) $date_modified = $temp[0];
        $temp = getcontent ($contentdata, "<contentpublished>");
        if (!empty ($temp[0])) $date_published = $temp[0];
      }
    }

    // get file size
    // if folder
    if ($page == ".folder")
    {
      if (!empty ($mgmt_config['db_connect_rdbms']))
      { 
        $filesize_array = rdbms_getfilesize ("", $location_esc, false);

        if (!empty ($filesize_array['filesize'])) $filesize = $filesize_array['filesize'];
        if (!empty ($filesize_array['count'])) $filecount = $filesize_array['count'];
      }
    }
    // if page or component
    else
    {
      $filesize = round (@filesize ($location.$page) / 1024, 0);
    }

    // format file size
    if ($filesize > 1000)
    {
      $filesize = $filesize / 1024;
      $unit = "MB";
    }
    else $unit = "KB";

    $filesize = number_format ($filesize, 0, ".", " ")." ".$unit;

    // output information
    $col_width = "min-width:120px; ";

    $mediaview = "
    <table style=\"width:100%; margin:0; border-spacing:0; border-collapse:collapse;\">
      <tr>
        <td style=\"text-align:left;\"><img src=\"".getthemelocation()."img/".$object_info['icon']."\" alt=\"".$object_info['name']."\" title=\"".$object_info['name']."\" /></td>
      </tr>
      <tr>
        <td style=\"text-align:left;\" class=\"hcmsHeadlineTiny\">".$name."</td>
      </tr>
      <tr>
        <td><hr/></td>
      </tr>
    </table>
    <table style=\"margin:0; border-spacing:0; border-collapse:collapse;\">";

    if (!empty ($owner)) $mediaview .= "
      <tr>
        <td style=\"".$col_width."text-align:left;\">".getescapedtext ($hcms_lang['owner'][$lang], $hcms_charset, $lang)." </td>
        <td class=\"hcmsHeadlineTiny\">".$owner."</td>
      </tr>";

    if (!empty ($date_created)) $mediaview .= "
      <tr>
        <td style=\"".$col_width."text-align:left;\">".getescapedtext ($hcms_lang['date-created'][$lang], $hcms_charset, $lang)." </td>
        <td class=\"hcmsHeadlineTiny\">".showdate ($date_created, "Y-m-d H:i", $hcms_lang_date[$lang])."</td>
      </tr>";

    if (!empty ($date_modified)) $mediaview .= "
      <tr>
        <td style=\"".$col_width."text-align:left;\">".getescapedtext ($hcms_lang['date-modified'][$lang], $hcms_charset, $lang)." </td>
        <td class=\"hcmsHeadlineTiny\">".showdate ($date_modified, "Y-m-d H:i", $hcms_lang_date[$lang])."</td>
      </tr>";

    if (!empty ($date_published)) $mediaview .= "
      <tr>
        <td style=\"".$col_width."text-align:left;\">".getescapedtext ($hcms_lang['published'][$lang], $hcms_charset, $lang)." </td>
        <td class=\"hcmsHeadlineTiny\">".showdate ($date_published, "Y-m-d H:i", $hcms_lang_date[$lang])."</td>
      </tr>";

    if (!empty ($filesize) && $filesize > 0) $mediaview .= "
      <tr>
        <td style=\"".$col_width."text-align:left; vertical-align:top;\">".getescapedtext ($hcms_lang['file-size'][$lang], $hcms_charset, $lang)." </td>
        <td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top;\">".$filesize."</td>
      </tr>";

    if (!empty ($filecount) && $filecount > 0) $mediaview .= "
      <tr>
        <td style=\"".$col_width."text-align:left; vertical-align:top;\">".getescapedtext ($hcms_lang['number-of-files'][$lang], $hcms_charset, $lang)." </td>
        <td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top;\">".$filecount."</td>
      </tr>";

    $mediaview .= "
    </table>";

    return $mediaview;
  }
  else return false;
}

// --------------------------------------- showmedia -----------------------------------------------
// function: showmedia ()
// input: mediafile (publication/filename) [string], name of mediafile for display [string], view type [template,media_only,preview,preview_download,preview_no_rendering], ID of the HTML media tag [string],
//        width in px [integer] (optional), height in px [integer] (optional), CSS class [string] (optional), recognize faces service in use [boolean] (optional)
// output: html presentation of any media asset / false

// description:
// This function requires site, location and cat to be set as global variable in order to validate the access permission of the user

function showmedia ($mediafile, $medianame, $viewtype, $id="", $width="", $height="", $class="hcmsImageItem", $recognizefaces_service=false)
{
  // $mgmt_imageoptions is used for image rendering (in case the format requires the rename of the object file extension)	 
  global $site, $mgmt_config, $mgmt_mediapreview, $mgmt_mediaoptions, $mgmt_imagepreview, $mgmt_docpreview, $mgmt_docoptions, $mgmt_docconvert, $mgmt_maxsizepreview, $hcms_charset, $hcms_lang_codepage, $hcms_lang_date, $hcms_lang, $lang,
         $site, $location, $cat, $page, $user, $pageaccess, $compaccess, $downloadformats, $hiddenfolder, $hcms_linking, $setlocalpermission, $mgmt_imageoptions, $is_mobile, $is_iphone;

  // Path to PDF.JS
  $pdfjs_path = $mgmt_config['url_path_cms']."javascript/pdfpreview/web/viewer.html?file=";

  // Path to Google Docs (disabled since veersion 7.0.6)
  // $gdocs_path = "https://docs.google.com/viewer?url=";

  require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

  // define flash media type (only executables)
  $swf_ext = ".swf.dcr";

  // define media ratio (W/H > ratio) to switch from 360 degree to horizontal panoramic view (wider image => use horizontal degree view)
  $switch_panoview = 3.5;

  // initialize
  $media_root = "";
  $mediafilesize = "";
  $mediafiletime = "";
  $width_orig = "";
  $height_orig = "";
  $mediaview = "";
  $mediaratio = 0;
  $owner = "";
  $date_created = "";
  $date_modified = "";
  $date_published = "";
  $style = "";
  $session_id = "";
  $is_config = false;
  $is_version = false;

  // define document type extensions that are convertable into pdf or which the document viewer (google doc viewer) can display
  // use main config setting $mgmt_docconvert since version 8.0.5
  $temp_docconvert = array();

  if (is_array ($mgmt_docconvert)) $doc_keys = array_keys ($mgmt_docconvert);

  $doc_keys = array_unique ($doc_keys);

  if (is_array ($doc_keys) && sizeof ($doc_keys) > 0) $doc_ext = ".pages.pdf".implode ("", $doc_keys).".";
  else $doc_ext = ".pages.pdf.doc.docx.ppt.pptx.xls.xlsx.odt.ods.odp.rtf.txt.";

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

  // set required permissions for the service user (required for service savecontent, function buildview and function showmedia)
  if (!empty ($recognizefaces_service) && !empty ($user) && is_facerecognition ($user))
  {
    if (!isset ($setlocalpermission)) $setlocalpermission = array();
    $setlocalpermission['root'] = 1;
    $setlocalpermission['create'] = 1;
  }

  // get browser information/version
  $user_client = getbrowserinfo ();

  // if valid media file
  if ($mediafile != "" && valid_publicationname ($site) && strpos (strtolower ("_".$mediafile), "null_media.png") < 1)
  {
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
      $media_root = $thumb_root = $mgmt_config['abs_path_tplmedia'].$site."/";

      if (is_file ($media_root.$mediafile))
      {
        // get file size in kB
        $mediafilesize = round (@filesize ($media_root.$mediafile) / 1024, 0);

        // get modified date
        $mediafiletime = date ("Y-m-d H:i", filemtime ($media_root.$mediafile));

        // get dimensions of original media file
        $temp = getmediasize ($media_root.$mediafile);

        if (!empty ($temp['width']) && !empty ($temp['height']))
        {
          $width_orig = $temp['width'];
          $height_orig = $temp['height'];
        }
      }
    }
    // define media file root directory for assets
    elseif (valid_publicationname ($site))
    {
      // location of media file (can be also outside the repository if exported)
      $media_root = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";

      // thumbnail file is always in the repository
      $thumb_root = getmedialocation ($site, ".hcms.".$mediafile, "abs_path_media").$site."/";

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

        // get dates and owner
        $container_info = getmetadata_container ($container_id, array("date", "createdate", "publishdate", "user"));
        
        if (!empty ($container_info['user']))
        {
          $owner = $container_info['user'];
          $date_created = $container_info['createdate'];
          $date_modified = $container_info['date'];
          $date_published = $container_info['publishdate'];
        }
        // use content container
        else
        {
          $temp = getcontent ($contentdata, "<contentuser>");
          if (!empty ($temp[0])) $owner = $temp[0];
          $temp = getcontent ($contentdata, "<contentcreated>");
          if (!empty ($temp[0])) $date_created = $temp[0];
          $temp = getcontent ($contentdata, "<contentdate>");
          if (!empty ($temp[0])) $date_modified = $temp[0];
          $temp = getcontent ($contentdata, "<contentpublished>");
          if (!empty ($temp[0])) $date_published = $temp[0];
        }
      }

      // get media file information from database
      if (!$is_version)
      {
        $media_info = rdbms_getmedia ($container_id);

        $mediafilesize = $media_info['filesize'];
        $width_orig = $media_info['width'];
        $height_orig = $media_info['height'];
      }

      // get media file information from media file (fallback)
      if (empty ($mediafilesize) && is_file ($media_root.$mediafile))
      {
        // initialize
        $imageinfo['filesize'] = NULL;
        $imageinfo['filetype'] = "";
        $imageinfo['width'] = NULL;
        $imageinfo['height'] = NULL;
        $imageinfo['red'] = NULL;
        $imageinfo['green'] = NULL;
        $imageinfo['blue'] = NULL;
        $imageinfo['colorkey'] = "";
        $imageinfo['imagetype'] = "";
        $imageinfo['md5_hash'] = "";

        // prepare media file
        $temp = preparemediafile ($site, $media_root, $mediafile, $user);

        // if encrypted
        if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
        {
          $media_root = $temp['templocation'];
          $mediafile = $temp['tempfile'];
        }
        // if restored
        elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
        {
          $media_root = $temp['location'];
          $mediafile = $temp['file'];
        }

        // get information of original media file
        $imageinfo = getimageinfo ($media_root.$mediafile);

        // file size
        $mediafilesize = $imageinfo['filesize'];

        if (!empty ($imageinfo['width']) && !empty ($imageinfo['height']))
        {
          $width_orig = $imageinfo['width'];
          $height_orig = $imageinfo['height'];
        }

        // set media info
        if (is_array ($imageinfo)) rdbms_setmedia ($container_id, $imageinfo['filesize'], $imageinfo['filetype'], $imageinfo['width'], $imageinfo['height'], $imageinfo['red'], $imageinfo['green'], $imageinfo['blue'], @$imageinfo['colorkey'], $imageinfo['imagetype'], $imageinfo['md5_hash']);
      }

      // if object will be deleted automatically
      if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page))
      {
        $location_esc = convertpath ($site, $location, "comp"); 
        $queue = rdbms_getqueueentries ("delete", "", "", "", $location_esc.$page);
        if (is_array ($queue) && !empty ($queue[0]['date'])) $date_delete = substr ($queue[0]['date'], 0, -3);
      }
    }

    // if watermark is used
    if (is_image ($mediafile))
    {
      // get container ID
      $container_id = getmediacontainerid ($mediafile);

      // get individual watermark
      if ($mgmt_config['publicdownload'] == true) $containerdata = loadcontainer ($container_id, "work", "sys");
      else $containerdata = loadcontainer ($container_id, "published", "sys");

      if (!empty ($containerdata))
      {
        $wmlocation = getmedialocation ($site, $mediafile, "abs_path_media");
        $wmnode = selectcontent ($containerdata, "<media>", "<media_id>", "Watermark");

        if (!empty ($wmnode[0]))
        {
          $temp = getcontent ($wmnode[0], "<mediafile>");
          if (!empty ($temp[0])) $wmfile = $temp[0];

          $temp = getcontent ($wmnode[0], "<mediaalign>");
          if (!empty ($temp[0])) $wmalign = $temp[0];
          else $wmalign = "center";

          // disabled since version 8.0.4
          // if (!empty ($wmfile)) $force_recreate = true;
        }
      }
    }

    // only show details if user has permissions to edit the file or for template media files
    if ($viewtype == "template" || (!empty ($setlocalpermission['root']) && !empty ($setlocalpermission['create']) || !empty ($setlocalpermission['download'])))
    {
      // --------------------------------------- if version ----------------------------------------
      if ($is_version && $viewtype != "template")
      {
        // define version thumbnail file (append version file extension)
        $mediafile_thumb = $file_info['filename'].".thumb.jpg".strtolower (strrchr ($mediafile_orig, "."));

        // prepare media file
        $temp = preparemediafile ($site, $thumb_root, $mediafile_thumb, $user);

        // if encrypted
        if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
        {
          $thumb_root = $temp['templocation'];
          $mediafile_thumb = $temp['tempfile'];
        }
        // if restored
        elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
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
        <table style=\"width:100%; margin:0; border-spacing:0; border-collapse:collapse;\">
          <tr>
            <td style=\"text-align:left;\"><img src=\"".cleandomain (createviewlink ($site, $mediafile_thumb)).((!empty ($recognizefaces_service) && substr ($user, 0, 4) == "sys:") ? "&PHPSESSID=".session_id() : "")."\" id=\"".$id."\" alt=\"".$medianame."\" title=\"".$medianame."\" class=\"".$class."\" ".$style." /></td>
          </tr>";

          $mediaview .= "
          <tr>
            <td style=\"text-align:left;\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."<br /><hr /></td>
          </tr>"; 
        }
        // if no thumbnail/preview exists
        else
        {
          $mediaview .= "
        <table style=\"width:100%; margin:0; border-spacing:0; border-collapse:collapse;\">
          <tr>
            <td style=\"text-align:left;\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" id=\"".$id."\" alt=\"".$medianame."\" title=\"".$medianame."\" ".$style." /></td>
          </tr>";

          $mediaview .= "
          <tr>
            <td style=\"text-align:left;\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."<br /><hr /></td>
          </tr>";
        }

        // define html code for download of older file version
        $mediaview .= "
          <tr>
            <td style=\"text-align:left; padding-top:10px;\">
              <button class=\"hcmsButtonGreen\" onclick=\"location.href='".cleandomain (createviewlink ($site, $mediafile_orig, $medianame, false, "download")).((!empty ($recognizefaces_service) && substr ($user, 0, 4) == "sys:") ? "&PHPSESSID=".session_id() : "")."';\">".getescapedtext ($hcms_lang['download-file'][$lang], $hcms_charset, $lang)."</button>
            </td>
          </tr>";

        $mediaview .= "
      </table>";
      }
      // ---------------------------------------- if document (only document formats that can be converted to PDF)  --------------------------------------
      elseif (!empty ($file_info['orig_ext']) && substr_count ($doc_ext, $file_info['orig_ext'].".") > 0 && !empty ($mgmt_config['docviewer']))
      {
        $mediaview_doc = "";

        // check for document PDF preview
        $mediafile_thumb = $file_info['filename'].".thumb.pdf";

        // if original file is a pdf
        if (substr_count (".pdf", $file_info['orig_ext']) == 1) $mediafile_pdf = $mediafile_orig;
        // document thumb file is a pdf
        elseif (is_file ($thumb_root.$mediafile_thumb) || is_cloudobject ($thumb_root.$mediafile_thumb)) $mediafile_pdf = $mediafile_thumb;

        // calculate the ratio based ont the PDF file
        if (!empty ($mediafile_pdf))
        {
          // get PDF width and height which should be available for documents depeding on the used extensions
          $pdf_info = getpdfinfo ($thumb_root.$mediafile_pdf);

          // define ratio
          if (!empty ($pdf_info['width']) || !empty ($pdf_info['height']))
          {
            $mediaratio = $pdf_info['width'] / $pdf_info['height'];
          }
        }

        // define media ratio based on file extension
        if ($mediaratio == 0)
        {
          // presentation and spreadsheet formats, older versions use 4:3
          if (strpos ("_.ppt.pot.pps.otp.xls.xlsx.ods", $file_info['orig_ext']) > 0) 
          {
            $mediaratio = 4 / 3;
          }
          // presentation formats, newer versions use 16:9
          elseif (strpos ("_.pptx.potx.ppsx", $file_info['orig_ext']) > 0) 
          {
            $mediaratio = 16 / 9;
          }
          // use A4 format
          else $mediaratio = 0.707;
        }

        // media size
        if (is_numeric ($width) && $width > 0 && is_numeric ($height) && $height > 0)
        {
          $style .= "width=\"".$width."\" height=\"".$height."\"";
        }
        elseif (is_numeric ($width) && $width > 0)
        {
          // min. width is required for document viewer
          if ($width < 320) $width = 320;
 
          $height = round (($width / $mediaratio), 0);
          $style .= "width=\"".$width."\" height=\"".$height."\"";
        }
        elseif (is_numeric ($height) && $height > 0)
        {
          $width = round (($height * $mediaratio), 0);

          // min. width is required for document viewer
          if ($width < 320)
          {
            $width = 320;
            $height = round (($width / $mediaratio), 0);
          }

          $style .= "width=\"".$width."\" height=\"".$height."\"";
        }
        else
        {
          $width = 576;
          $height = $height = round (($width / $mediaratio), 0);

          $style .= "width=\"".$width."\" height=\"".$height."\"";
        }

        // check user browser for compatibility with pdf render javascript - PDF.JS and if original file is a pdf or is convertable to a pdf
        if (
            substr_count (".pdf", $file_info['orig_ext']) == 1 || 
            (!empty ($mgmt_docconvert[$file_info['orig_ext']]) && is_array ($mgmt_docconvert[$file_info['orig_ext']]) && in_array (".pdf", $mgmt_docconvert[$file_info['orig_ext']]))
           )
        {
          // if original file is a pdf
          if (substr_count (".pdf", $file_info['orig_ext']) == 1) 
          {
            // using PDF.JS with orig. file via iframe
            $doc_link = cleandomain (createviewlink ($site, $mediafile_orig, $medianame, true)).((!empty ($recognizefaces_service) && substr ($user, 0, 4) == "sys:") ? "&PHPSESSID=".session_id() : "")."&saveName=".$medianame.".pdf";
            $mediaview_doc = "<iframe src=\"".$pdfjs_path.urlencode($doc_link)."\" ".$style." id=\"".$id."\" style=\"border:0;\"></iframe><br />\n";
          }
          else
          {
            $mediafile_thumb = $medianame_thumb = $file_info['filename'].".thumb.pdf";

            // prepare media file
            $temp = preparemediafile ($site, $thumb_root, $mediafile_thumb, $user);

            // if encrypted
            if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
            {
              $thumb_root = $temp['templocation'];
              $mediafile_thumb = $temp['tempfile'];
            }
            // if restored
            elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
            {
              $thumb_root = $temp['location'];
              $mediafile_thumb = $temp['file'];
            }

            // document thumb file exists in media repository
            if ((is_file ($thumb_root.$mediafile_thumb) || is_cloudobject ($thumb_root.$mediafile_thumb)) && (filemtime ($thumb_root.$mediafile_thumb) >= filemtime ($thumb_root.$mediafile_orig))) 
            {
              $thumb_pdf_exists = true;
            }
            // sometimes libre office (UNOCONV) takes long time to convert to PDF and function createdocument is not able to rename the file from .pdf to .thumb.pdf
            elseif ((is_file ($thumb_root.$file_info['filename'].".pdf") || is_cloudobject ($thumb_root.$file_info['filename'].".pdf")) && (filemtime ($thumb_root.$file_info['filename'].".pdf") >= filemtime ($thumb_root.$mediafile_orig)))
            {
              rename ($thumb_root.$file_info['filename'].".pdf", $thumb_root.$file_info['filename'].".thumb.pdf");
              if (function_exists ("renamecloudobject")) renamecloudobject ($site, $thumb_root, $file_info['filename'].".pdf", $file_info['filename'].".thumb.pdf", $user);
              $thumb_pdf_exists = true;
            }
            else $thumb_pdf_exists = false;

            // thumb pdf exsists
            if (!empty ($thumb_pdf_exists))
            {
              // using pdfjs with thumbnail file via iframe
              $doc_link = cleandomain (createviewlink ($site, $mediafile_thumb, $medianame_thumb, true)).((!empty ($recognizefaces_service) && substr ($user, 0, 4) == "sys:") ? "&PHPSESSID=".session_id() : "")."&saveName=".$medianame.".pdf";
              $mediaview_doc = "<iframe src=\"".$pdfjs_path.urlencode($doc_link)."\" ".$style." id=\"".$id."\" style=\"border:0;\"></iframe><br />\n";
            }
            // thumb pdf does not exist but can be created
            elseif (empty ($thumb_pdf_exists) && is_supported ($mgmt_docpreview, $file_info['orig_ext']) && (empty ($mgmt_maxsizepreview[$file_info['orig_ext']]) || ($mediafilesize/1024) <= $mgmt_maxsizepreview[$file_info['orig_ext']]))
            {
              // try to remove outdated pdf thumbnail file
              if (is_file ($thumb_root.$mediafile_thumb))
              {
                deletefile ($thumb_root, $mediafile_thumb, 0);

                // remove old annotation image files
                $annotation_filename = $file_info['filename'].".annotation";

                if ((is_file ($thumb_root.$annotation_filename."-0.jpg") || is_cloudobject ($thumb_root.$annotation_filename."-0.jpg")))
                { 
                  for ($p=0; $p<=10000; $p++)
                  {
                    $temp = $annotation_filename."-".$p.".jpg";
                    
                    // local media file
                    if (is_file ($thumb_root.$temp)) $delete_1 = deletefile ($thumb_root, $temp, 0);
                    // cloud storage
                    if (function_exists ("deletecloudobject")) $delete_2 = deletecloudobject ($site, $thumb_root, $temp, $user);
                    // remote client
                    remoteclient ("delete", "abs_path_media", $site, $thumb_root, "", $temp, "");
                    // break if no more page is available
                    if (empty ($delete_1) && empty ($delete_2)) break;
                  }
                }
              }

              // using original file and wrapper to start conversion in the background
              $doc_link = createviewlink ($site, $mediafile, $medianame_thumb)."&type=pdf&ts=".time();

              // show standard file icon
              if (!empty ($file_info['icon'])) $mediaview_doc .= "
              <div style=\"width:100%; text-align:left;\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" ".$id." alt=\"".$medianame."\" title=\"".$medianame."\" /><br/><img src=\"".getthemelocation()."img/loading.gif\" /></div>";

              // use AJAX service to start conversion of media file to pdf format for preview
              $mediaview_doc .= "
            <script type=\"text/javascript\">
              hcms_ajaxService('".$doc_link."');
              setTimeout(function() { location.reload(); }, 5000);
            </script>\n ";
            }
            // using Google Docs if UNOCONV was not able to convert into pdf and no standard file-icon exists
            elseif (!empty ($gdocs_path))
            {
              $doc_link = createviewlink ($site, $mediafile_orig, $medianame, true);

              $mediaview_doc .= "
              <iframe src=\"".$gdocs_path.urlencode($doc_link)."&embedded=true\" ".$style." id=\"".$id."\" style=\"border:0;\"></iframe><br />\n";
            }
            // show standard file icon
            else
            {
              // show standard file icon
              if (!empty ($file_info['icon'])) $mediaview_doc .= "
              <div style=\"width:100%; text-align:left;\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" ".$id." alt=\"".$medianame."\" title=\"".$medianame."\" /></div>";
            }
          }
        }
        // using Google Docs
        elseif (!empty ($gdocs_path))
        {
          // no compatible browser - using Google Docs
          $doc_link = createviewlink ($site, $mediafile_orig, $medianame, true);

          $mediaview_doc .= "
          <iframe src=\"".$gdocs_path.urlencode($doc_link)."&embedded=true\" ".$style." id=\"".$id."\" style=\"border:0;\"></iframe><br />";
        }
        // show standard file icon
        else
        {
          // show standard file icon
          if (!empty ($file_info['icon'])) $mediaview_doc .= "
          <div style=\"width:100%; text-align:left;\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" ".$id." alt=\"".$medianame."\" title=\"".$medianame."\" /></div>";
        }

        // document annotations
        if (is_annotation () && $viewtype == "preview" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
        {
          // check for document annotation image (1st page)
          $annotation_page = $file_info['filename'].".annotation-0.jpg";

          // create pages if the first page does not exist or is older than the original media file
          if (
               !empty ($mediafile_pdf) &&
               (
                 (!is_file ($thumb_root.$annotation_page) && !is_cloudobject ($thumb_root.$annotation_page)) || 
                 (is_file ($thumb_root.$annotation_page) && filemtime ($thumb_root.$annotation_page) < filemtime ($thumb_root.$mediafile_pdf))
               )
             )
          {
            // get PDF width and height
            $pdf_info = getpdfinfo ($thumb_root.$mediafile_pdf);

            // use default values
            if (empty ($pdf_info['width']) || empty ($pdf_info['height']))
            {
              $pdf_info = array();
              $pdf_info['width'] = 623;
              $pdf_info['height'] = 806;
            }

            // create pages as images from PDF document
            $mgmt_imageoptions['.jpg.jpeg']['annotation'] = "-s ".round($pdf_info['width'], 0)."x".round($pdf_info['height'], 0)." -q 100 -f jpg";
            createmedia ($site, $thumb_root, $thumb_root, $mediafile_pdf, 'jpg', 'annotation', true, false);
          }

          // embed annotation script
          if (is_file ($thumb_root.$annotation_page))
          {
            // reset
            $mediaview_doc = "";

            // count pages 
            for ($page_count = 0; $page_count <= 10000; $page_count++)
            {
              if (!is_file ($thumb_root.$file_info['filename'].".annotation-".$page_count.".jpg") && !is_cloudobject ($thumb_root.$file_info['filename'].".annotation-".$page_count.".jpg")) break;
            }

            if ($page_count > 1)
            {
              $mediaview_doc .= "
  <div style=\"position:absolute; width:".($width+15)."px; height:28px; text-align:right;".($width < 420 ? "margin-top:-38px;" : "margin-top:-8px;")."\">
    <img src=\"".getthemelocation()."img/button_arrow_left.png\" onclick=\"gotoPage('previous');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" style=\"display:inline-block; vertical-align:middle;\" alt=\"".getescapedtext ($hcms_lang['back'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['back'][$lang], $hcms_charset, $lang)."\" />
    <select id=\"pagenumber\" onchange=\"gotoPage('none');\" title=\"".getescapedtext ($hcms_lang['page'][$lang], $hcms_charset, $lang)."\" style=\"display:inline-block; width:70px;\">";

              for ($i = 0; $i < $page_count; $i++)
              {
                $mediaview_doc .= "
        <option value=\"".$i."\">".($i+1)."</option>";
              }

              $mediaview_doc .= "
    </select> / ".$page_count."
    <img src=\"".getthemelocation()."img/button_arrow_right.png\" onclick=\"gotoPage('next');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" style=\"display:inline-block; vertical-align:middle;\" alt=\"".getescapedtext ($hcms_lang['forward'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['forward'][$lang], $hcms_charset, $lang)."\" />
  </div>";
            }

           $mediaview_doc .= "
  <div style=\"margin-top:30px;\">
    <div id=\"annotation\" style=\"position:relative;\" class=\"".$class."\"></div>
  </div>
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/annotate/annotate.min.js\"></script>
	<script type=\"text/javascript\">
    // set annotation buttons
    function setAnnotationButtons ()
    {
      if (document.getElementById('annotationStop'))
      {
        document.getElementById('annotationStop').src = '".getthemelocation()."img/button_file_lock.png';
        document.getElementById('tool0').title = hcms_entity_decode('".getescapedtext ($hcms_lang['none'][$lang], $hcms_charset, $lang)."');
        document.getElementById('annotationStop').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['none'][$lang], $hcms_charset, $lang)."');
      }

      if (document.getElementById('annotationRectangle'))
      {
        document.getElementById('annotationRectangle').src = '".getthemelocation()."img/button_rectangle.png';
        document.getElementById('tool1').title = hcms_entity_decode('".getescapedtext ($hcms_lang['rectangle'][$lang], $hcms_charset, $lang)."');
        document.getElementById('annotationRectangle').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['rectangle'][$lang], $hcms_charset, $lang)."');
      }

      if (document.getElementById('annotationCircle'))
      {
        document.getElementById('annotationCircle').src = '".getthemelocation()."img/button_circle.png';
        document.getElementById('tool2').title = hcms_entity_decode('".getescapedtext ($hcms_lang['circle'][$lang], $hcms_charset, $lang)."');
        document.getElementById('annotationCircle').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['circle'][$lang], $hcms_charset, $lang)."');
      }

      if (document.getElementById('annotationText'))
      {
        document.getElementById('annotationText').src = '".getthemelocation()."img/button_textu.png';
        document.getElementById('tool3').title = hcms_entity_decode('".getescapedtext ($hcms_lang['text'][$lang], $hcms_charset, $lang)."');
        document.getElementById('annotationText').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['text'][$lang], $hcms_charset, $lang)."');
      }

      if (document.getElementById('annotationArrow'))
      {
        document.getElementById('annotationArrow').src = '".getthemelocation()."img/button_arrow.png';
        document.getElementById('tool4').title = hcms_entity_decode('".getescapedtext ($hcms_lang['arrow'][$lang], $hcms_charset, $lang)."');
        document.getElementById('annotationArrow').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['arrow'][$lang], $hcms_charset, $lang)."');
      }

      if (document.getElementById('annotationPen'))
      {
        document.getElementById('annotationPen').src = '".getthemelocation()."img/button_pen.png';
        document.getElementById('tool5').title = hcms_entity_decode('".getescapedtext ($hcms_lang['pen'][$lang], $hcms_charset, $lang)."');
        document.getElementById('annotationPen').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['pen'][$lang], $hcms_charset, $lang)."');
      }

      if (document.getElementById('annotationDownload'))
      {
        document.getElementById('annotationDownload').src = '".getthemelocation()."img/button_file_download.png';
        document.getElementById('download').title = hcms_entity_decode('".getescapedtext ($hcms_lang['download'][$lang], $hcms_charset, $lang)."');
        document.getElementById('annotationDownload').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['download'][$lang], $hcms_charset, $lang)."');
      }

      if (document.getElementById('annotationUndo'))
      {
        document.getElementById('annotationUndo').src = '".getthemelocation()."img/button_history_back.png';
        document.getElementById('undoaction').title = hcms_entity_decode('".getescapedtext ($hcms_lang['undo'][$lang], $hcms_charset, $lang)."');
        document.getElementById('annotationUndo').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['undo'][$lang], $hcms_charset, $lang)."');
      }

      if (document.getElementById('annotationRedo'))
      {
        document.getElementById('annotationRedo').src = '".getthemelocation()."img/button_history_forward.png';
        document.getElementById('redoaction').title = hcms_entity_decode('".getescapedtext ($hcms_lang['redo'][$lang], $hcms_charset, $lang)."');
        document.getElementById('annotationRedo').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['redo'][$lang], $hcms_charset, $lang)."');
      }

      if (document.getElementById('annotationHelp'))
      {
        document.getElementById('annotationHelp').src = '".getthemelocation()."img/button_info.png';
        document.getElementById('annotationHelp').title = hcms_entity_decode('".getescapedtext ($hcms_lang['select-a-tool-in-order-to-add-an-annotation'][$lang], $hcms_charset, $lang)."');
        document.getElementById('annotationHelp').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['select-a-tool-in-order-to-add-an-annotation'][$lang], $hcms_charset, $lang)."');
      }
    }

    // annotation paging
    function gotoPage (action)
    {
      var number = $('#pagenumber').val();

      if (action == 'previous') number--;
      else if (action == 'next') number++;

      pages_name = [];
      pages_link = [];";

      for ($i = 0; $i < $page_count; $i++)
      {
        $mediaview_doc .= "
      pages_name[".$i."] = \"".$file_info['filename'].".annotation-".$i.".jpg\";
      pages_link[".$i."] = \"".cleandomain (createviewlink ($site, $file_info['filename'].".annotation-".$i.".jpg", $file_info['filename'].".annotation-".$i.".jpg")).((!empty ($recognizefaces_service) && substr ($user, 0, 4) == "sys:") ? "&PHPSESSID=".session_id() : "")."&ts=\" + Date.now()";
      }

      $mediaview_doc .= "

      if (number >= 0 && number < ".$page_count.")
      {
        if (annotatestatus == true) autoSave(true);
        $('#pagenumber').val(number);
        $('#medianame').val(pages_name[number]);

        $('#annotation').annotate('push', pages_link[number]);
        annotatestatus = false;
      }
    }

    $(document).ready(function(){
      // set annotation image file name
      $('#medianame').val('".$annotation_page."');

      // create annotation image
			$('#annotation').annotate({
        width: '".$width."',
        height: '".$height."',
				color: 'red',
				bootstrap: false,
        unselectTool: true,
				images: ['".cleandomain (createviewlink ($site, $annotation_page, $annotation_page)).((!empty ($recognizefaces_service) && substr ($user, 0, 4) == "sys:") ? "&PHPSESSID=".session_id() : "")."?ts=".time()."']
      });

      // set images for buttons
      setAnnotationButtons();

      // annotations download event
      $('#annotationDownload').click(function(event) {
        $('#annotation').annotate('export', {type: 'image/jpeg', quality: 1}, function(data){
          hcms_downloadURI (data, 'annotation.jpg');
        });
      });
    });
  </script>
  ";
          }
        }

        // display name
        if ($mediaview_doc != "")
        {
          $mediaview_doc .= "
          <div style=\"padding:5px 0px 8px 0px; width:".$width."px; text-align:left;\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."<br /></div>";
        }

        // image rendering (only if image conversion software and permissions are given)
        if ($viewtype == "preview" && !empty ($mediafile_pdf) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1 && $mediaview_doc != "") 
        {
          // add image rendering button
          $mediaview_doc .= "
          <div style=\"padding:5px 0px 8px 0px; width:".$width."px; text-align:left;\">
            <button type=\"button\" class=\"hcmsButtonBlue\" onclick=\"if (typeof setSaveType === 'function') setSaveType('documentviewerconfig_so', '', 'post');\"><img src=\"".getthemelocation()."img/button_phpinclude.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['embed'][$lang], $hcms_charset, $lang)."</button>
          </div>";
        }
        // add doc viewer
        $mediaview .= "<table style=\"width:100%; margin:0; border-spacing:0; border-collapse:collapse;\">
      <tr>
        <td style=\"text-align:left;\" class=\"hcmsHeadlineTiny\">".$mediaview_doc."</td>
      </tr>
      <tr>
        <td><hr/></td>
      </tr>
    </table>";
      }
      // ----------------------------------- if image (including encapsulated post script and svg files that are treated as multi-page files by function createmedia) ------------------------------------- 
      elseif (!empty ($file_info['ext']) && is_image ($file_info['ext']))
      {
        // media size
        $style = "";

        if ($viewtype != "template")
        {
          $thumbfile = $file_info['filename'].".thumb.jpg";

          // prepare media file
          $temp = preparemediafile ($site, $thumb_root, $thumbfile, $user);

          // if encrypted
          if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
          {
            $thumb_root = $temp['templocation'];
            $thumbfile = $temp['tempfile'];
          }
          // if restored
          elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
          {
            $thumb_root = $temp['location'];
            $thumbfile = $temp['file'];
          }

          // use thumbnail if it is valid
          if (is_file ($thumb_root.$thumbfile))
          {
            // get thumbnail image information
            $thumb_size = getmediasize ($thumb_root.$thumbfile);

            if (!empty ($thumb_size['width']) && !empty ($thumb_size['height']))
            {
              $mediaratio = $thumb_size['width'] / $thumb_size['height'];
            }

            // set width or height if not provided as input (assume 150% of thumbnail image size)
            if (empty ($width) && empty ($height) && !empty ($thumb_size['width']))
            {
              $width = round (($thumb_size['width'] * 1.5), 0);
            }

            // calculate width or height if one dimension is missing
            if ($width > 0 && empty ($height) && !empty ($mediaratio))
            {
              $height = round (($width / $mediaratio), 0);
            }
            elseif (empty ($width) && $height > 0 && !empty ($mediaratio))
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

            // create new image for annotations (only if annotations are enabled and image conversion software and permissions are given)
            if (substr_count ($doc_ext.$hcms_ext['vectorimage'], $file_info['orig_ext'].".") > 0) $annotation_file = $file_info['filename'].".annotation-0.jpg";
            else $annotation_file = $file_info['filename'].'.annotation.jpg';

            if (
                 $viewtype == "preview" &&
                 !empty ($mediaratio) && ($thumb_size['width'] >= 180 || $thumb_size['height'] >= 180) && 
                 is_annotation () && 
                 (!is_file ($thumb_root.$annotation_file) || filemtime ($thumb_root.$annotation_file) < filemtime ($thumb_root.$thumbfile)) && 
                 (is_supported ($mgmt_imagepreview, $file_info['orig_ext'])  || is_kritaimage ($file_info['orig_ext'])) && 
                 $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1
               )
            {
              // render image using the default image width
              $width_render = getpreviewwidth ($site, $mediafile, $width_orig);
              $height_render = round (($width_render / $mediaratio), 0);

              // set max width and height for annotation image
              if ($width > $width_render)
              {
                $width = $width_render;
                $height = round (($width / $mediaratio), 0);
              }
              else 
              {
                $height = round (($width / $mediaratio), 0);
              }

              if (is_array ($mgmt_imageoptions))
              {
                // define image format
                $mgmt_imageoptions['.jpg.jpeg']['annotation'] = '-s '.$width_render.'x'.$height_render.' -q 100 -f jpg';

                // use existing converted image file in case of RAW or KRITA image
                if (is_rawimage ($file_info['orig_ext']) || is_kritaimage ($file_info['orig_ext']))
                {
                  // prepare media file
                  $temp_file = preparemediafile ($site, $thumb_root, $file_info['filename'].".jpg", $user);

                  // if encrypted
                  if (!empty ($temp_file['result']) && !empty ($temp_file['crypted']) && !empty ($temp_file['templocation']) && !empty ($temp_file['tempfile']))
                  {
                    $temp_location = $temp_file['templocation'];
                    $temp_mediafile = $temp_file['tempfile'];
                  }
                  // if restored
                  elseif (!empty ($temp_file['result']) && !empty ($temp_file['restored']) && !empty ($temp_file['location']) && !empty ($temp_file['file']))
                  {
                    $temp_location = $temp_file['location'];
                    $temp_mediafile = $temp_file['file'];
                  }
                  // use existing file
                  else
                  {
                    $temp_location = $thumb_root;
                    $temp_mediafile = $file_info['filename'].".jpg";
                  }

                  // create new image for annotations
                  if (!empty ($temp_location) && !empty ($temp_mediafile))
                  {
                    $result = createmedia ($site, $temp_location, $thumb_root, $temp_mediafile, 'jpg', 'annotation', true, false);
                  }
                }

                // create new image for annotations using the original image file
                if (empty ($result) && is_supported ($mgmt_imagepreview, $file_info['orig_ext']))
                {
                  $result = createmedia ($site, $thumb_root, $thumb_root, $mediafile_orig, 'jpg', 'annotation', true, false);
                }

                if (!empty ($result))
                {
                  $mediafile = $result;
                  $previewimage_path = $thumb_root.$result;
                }
              }
            }

            // generate a new image file if the new image size is greater than 150% of the width or height of the thumbnail
            if (!empty ($mediaratio) && ($width > 0 && $thumb_size['width'] * 1.5 < $width) && ($height > 0 && $thumb_size['height'] * 1.5 < $height) && is_supported ($mgmt_imagepreview, $file_info['orig_ext']))
            {
              // define parameters for view-images
              $viewfolder = $mgmt_config['abs_path_temp'];
              $newext = 'jpg';
              $typename = 'preview.'.$width.'x'.$height;

              // predict the name to check if the file does exist and maybe is actual
              $newname = $file_info['filename'].".".$typename.'.'.$newext;

              // generate new file only when another one wasn't already created or is outdated (use thumbnail since the date of the decrypted temporary file is not representative)
              if (!is_file ($viewfolder.$newname) || (is_file ($thumb_root.$thumbfile) && @filemtime ($thumb_root.$thumbfile) > @filemtime ($viewfolder.$newname)) || !empty ($force_recreate)) 
              {
                if (!empty ($mgmt_imagepreview) && is_array ($mgmt_imagepreview))
                {
                  $mgmt_imageoptions['.jpg.jpeg'][$typename] = '-s '.$width.'x'.$height.' -q 100 -f '.$newext;

                  // create new temporary thumbnail
                  $result = createmedia ($site, $thumb_root, $viewfolder, $mediafile_orig, $newext, $typename, true, false);

                  if ($result)
                  {
                    $mediafile = $result;
                    $previewimage_path = $viewfolder.$result;
                  }
                }
              }
              // we use the existing file
              else $mediafile = $newname;

              if (!empty ($viewfolder.$mediafile)) 
              {
                // get image size
                if (is_file ($viewfolder.$mediafile))
                {
                  $temp_size = getmediasize ($viewfolder.$mediafile);

                  if (!empty ($temp_size['width']) && !empty ($temp_size['height']))
                  {
                    $width = $temp_size['width'];
                    $height = $temp_size['height'];
                  }
                }
              }
            }
            // stretch view of thumbnail image
            elseif (!empty ($mediaratio) && ($width > 0 && $thumb_size['width'] * 1.5 < $width) && ($height > 0 && $thumb_size['height'] * 1.5 < $height))
            {
              $width = ceil ($thumb_size['width'] * 1.5);
              $height = ceil ($thumb_size['height'] * 1.5);
              $mediafile = $thumbfile;
            }
            // if thumbnail file is smaller than the defined size of a thumbnail due to a smaller original image
            elseif (!empty ($mediaratio) && $thumb_size['width'] < 180 && $thumb_size['height'] < 180)
            {
              $width = $thumb_size['width'];
              $height = $thumb_size['height'];
              $mediafile = $thumbfile;
            }
            // use thumbnail with requested dimension
            else
            {
              $mediafile = $thumbfile;
            }

            if ($width > 0 && $height > 0) $style = "width:".intval($width)."px; height:".intval($height)."px;";

            // set width and height of media file as file-parameter
            $mediaview .= "
          <!-- hyperCMS:width file=\"".$width_orig."\" -->
          <!-- hyperCMS:height file=\"".$height_orig."\" -->";

            // get file extension of new file (preview file)
            $file_info['ext'] = strtolower (strrchr ($mediafile, "."));

            $mediaview .= "
          <table style=\"width:100%; margin:0; border-spacing:0; border-collapse:collapse;\">
            <tr>
              <td style=\"text-align:left;\">";

            // image annotation
            if (($thumb_size['width'] >= 180 || $thumb_size['height'] >= 180) && is_annotation () && is_file ($thumb_root.$annotation_file) && $viewtype == "preview" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
            {
              // get annotation image size
              $temp = getmediasize ($thumb_root.$annotation_file);

              if (!$is_mobile && !empty ($temp['width']) && !empty ($temp['height']))
              {
                $width_annotation = $temp['width'];
                $height_annotation = $temp['height'];
              }
              else
              {
                $width_annotation = $width;
                $height_annotation = $height;
              }

              $previewimage_link = createviewlink ($site, $annotation_file, $annotation_file, true).((!empty ($recognizefaces_service) && substr ($user, 0, 4) == "sys:") ? "&PHPSESSID=".session_id() : "");
              $previewimage_path = $thumb_root.$annotation_file;

              $width_diff = 500 - intval($width_annotation);
              if ($width_diff <= 0) $width_diff = 0;

              if (!$is_mobile && empty ($recognizefaces_service)) $mediaview .= "
              <div id=\"hcms360View\" class=\"".$class."\" style=\"position:absolute; z-index:9910; display:none; margin-left:-2px; width:".intval($width_annotation + $width_diff + 4)."px; height:".intval($height_annotation + 42)."px;\">
                <div style=\"position:absolute; right:4px; top:4px;\">
                  <img name=\"hcms_mediaClose\" onClick=\"if (typeof showFaceOnImage === 'function') showFaceOnImage(); hcms_switchFormLayer('hcms360View');\" src=\"".getthemelocation()."img/button_close.png\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['close'][$lang])."\" title=\"".getescapedtext ($hcms_lang['close'][$lang])."\" onMouseOut=\"hcms_swapImgRestore();\" onMouseOver=\"hcms_swapImage('hcms_mediaClose','','".getthemelocation()."img/button_close_over.png',1);\" />
                </div>
                <!-- 360 view -->
                <iframe src=\"".cleandomain ($mgmt_config['url_path_cms'])."media_360view.php?type=image&link=".url_encode($previewimage_link).($mediaratio > $switch_panoview ? "&view=horizontal" : "")."\" frameborder=\"0\" style=\"width:100%; height:100%; border:0;\" allowFullScreen=\"true\" webkitallowfullscreen=\"true\" mozallowfullscreen=\"true\"></iframe>
              </div>";
              $mediaview .= "
              <div id=\"annotationFrame\" style=\"margin-top:40px; width:".intval($width_annotation + $width_diff)."px; height:".intval($height_annotation + 8)."px;\">
                <div style=\"position:relative; left:0; top:0; width:0; height:0;\">
                  <!-- annotation image --> 
                  <img src=\"".cleandomain ($previewimage_link)."\" id=\"".$id."\" style=\"position:absolute; left:0; top:0; z-index:-10; visibility:hidden;\" />
                </div>
                <div id=\"annotation\" style=\"position:relative;\" ".(((is_facerecognition ("sys") || is_annotation ()) && $viewtype == "preview") ? "onclick=\"if (typeof createFaceOnImage === 'function') createFaceOnImage (event, 'annotation');\" onmousedown=\"$('.hcmsFace').hide(); $('.hcmsFaceName').hide();\" onmouseup=\"$('.hcmsFace').show(); $('.hcmsFaceName').show();\"" : "")." class=\"".$class."\"></div>
              </div>";
            }
            // image with face/object markers only (no annotations) 
            else
            {
              $previewimage_link = createviewlink ($site, $mediafile, $medianame, true).((!empty ($recognizefaces_service) && substr ($user, 0, 4) == "sys:") ? "&PHPSESSID=".session_id() : "");

              if (!empty ($viewfolder)) $previewimage_path = $viewfolder.$mediafile;
              elseif (!empty ($thumb_root)) $previewimage_path = $thumb_root.$mediafile;

              if (!$is_mobile && empty ($recognizefaces_service)) $mediaview .= "
              <div id=\"hcms360View\" class=\"".$class."\" style=\"position:absolute; z-index:9910; display:none; ".$style."\">
                <div style=\"position:absolute; right:4px; top:4px;\">
                  <img name=\"hcms_mediaClose\" onClick=\"if (typeof showFaceOnImage === 'function') showFaceOnImage(); hcms_switchFormLayer('hcms360View');\" src=\"".getthemelocation()."img/button_close.png\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['close'][$lang])."\" title=\"".getescapedtext ($hcms_lang['close'][$lang])."\" onMouseOut=\"hcms_swapImgRestore();\" onMouseOver=\"hcms_swapImage('hcms_mediaClose','','".getthemelocation()."img/button_close_over.png',1);\" />
                </div>
                <!-- 360 view --> 
                <iframe src=\"".cleandomain ($mgmt_config['url_path_cms'])."media_360view.php?type=image&link=".url_encode($previewimage_link).($mediaratio > $switch_panoview ? "&view=horizontal" : "")."\" frameborder=\"0\" style=\"width:100%; height:100%; border:0;\" allowfullscreen ></iframe>
              </div>";
              $mediaview .= "
              <div id=\"facemarker\" style=\"position:relative; width:auto; height:auto;\" ".(((is_facerecognition ("sys") || is_annotation ()) && $viewtype == "preview") ? "onclick=\"if (typeof createFaceOnImage === 'function') createFaceOnImage (event, '".$id."');\"" : "").">
                <!-- face marker -->  
                <img src=\"".cleandomain ($previewimage_link)."\" id=\"".$id."\" alt=\"".$medianame."\" title=\"".$medianame."\" class=\"".$class."\" style=\"".$style."\" />
              </div>";
            }

            $mediaview .= "
              </td>
            </tr>";

            $mediaview .= "
            <tr>
              <td style=\"text-align:left;\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td>
            </tr>"; 
          }
          // if no thumbnail/preview exists
          else
          {
            $mediaview .= "
          <table style=\"width:100%; margin:0; border-spacing:0; border-collapse:collapse;\">
            <tr>
              <td style=\"text-align:left;\">
                <!-- file icon -->
                <img src=\"".getthemelocation()."img/".$file_info['icon']."\" id=\"".$id."\" alt=\"".$medianame."\" title=\"".$medianame."\" />
              </td>
            </tr>";

            $mediaview .= "
            <tr>
              <td style=\"text-align:left;\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td>
            </tr>";
          }
        }
        // if template media view
        elseif (is_file ($media_root.$mediafile))
        {
          $mediaview .= "
        <table style=\"width:100%; margin:0; border-spacing:0; border-collapse:collapse;\">
          <tr>
            <td style=\"text-align:left;\"><img src=\"".$mgmt_config['url_path_tplmedia'].$site."/".$mediafile."\" id=\"".$id."\" alt=\"".$mediafile."\" title=\"".$mediafile."\" class=\"".$class."\" style=\"".$style."\" /></td>
          </tr>";

          $mediaview .= "
          <tr>
            <td style=\"text-align:left;\" class=\"hcmsHeadlineTiny\">".showshorttext($mediafile, 40, false)."</td>
          </tr>";
        }

        // image rendering (only if image conversion software and permissions are given)
        if ($viewtype == "preview" && is_supported ($mgmt_imagepreview, $file_info['orig_ext']) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1) 
        {
          // add image rendering button
          $mediaview .= "
          <tr>
            <td style=\"text-align:left; padding-top:10px;\">
              <button type=\"button\" class=\"hcmsButtonGreen\" onclick=\"if (typeof setSaveType === 'function') setSaveType('imagerendering_so', '', 'post');\"><img src=\"".getthemelocation()."img/button_edit.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['edit'][$lang], $hcms_charset, $lang)."</button>";
          if (!$is_mobile) $mediaview .= "
              <button type=\"button\" class=\"hcmsButtonBlue\" onclick=\"if (typeof hideFaceOnImage === 'function') hideFaceOnImage(); hcms_switchFormLayer ('hcms360View');\"><img src=\"".getthemelocation()."img/icon_rotate.png\" class=\"hcmsIconList\" /> 360° ".getescapedtext ($hcms_lang['preview'][$lang], $hcms_charset, $lang)."</button>";
          $mediaview .= "
              <button type=\"button\" class=\"hcmsButtonBlue\" onclick=\"if (typeof setSaveType === 'function') setSaveType('imageviewerconfig_so', '', 'post');\"><img src=\"".getthemelocation()."img/button_phpinclude.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['embed'][$lang], $hcms_charset, $lang)."</button>
            </td>
          </tr>";
        }
        // options button for image editing
        elseif ($viewtype == "preview_download")
        {
          $mediaview .= "
          <tr>
            <td style=\"text-align:left; padding-top:10px;\">
              <button type=\"button\" id=\"mediaplayer_options\" class=\"hcmsButtonBlue\" onclick=\"document.getElementById('barbutton_0').click();\"><img src=\"".getthemelocation()."img/button_edit.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['options'][$lang], $hcms_charset, $lang)."</button>
            </td>
          </tr>";
        }
        // 360 view button
        elseif ($viewtype == "media_only" && !empty ($previewimage_link) && !$is_mobile)
        {
          $mediaview .= "
          <tr>
            <td style=\"text-align:left; padding-top:10px;\">
              <button type=\"button\" class=\"hcmsButtonBlue\" onclick=\"if (typeof hideFaceOnImage === 'function') hideFaceOnImage(); hcms_switchFormLayer ('hcms360View');\"><img src=\"".getthemelocation()."img/icon_rotate.png\" class=\"hcmsIconList\" /> 360° ".getescapedtext ($hcms_lang['preview'][$lang], $hcms_charset, $lang)."</button>
            </td>
          </tr>";
        }

        $mediaview .= "
        <tr>
          <td><hr/></td>
        </tr>
      </table>";

        // embed annotation script
        if (!empty ($mediaratio) && ($thumb_size['width'] >= 180 || $thumb_size['height'] >= 180) && is_annotation () && is_file ($thumb_root.$annotation_file) && $viewtype == "preview" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
        {
          // count pages 
          for ($page_count = 0; $page_count <= 10000; $page_count++)
          {
            if (!is_file ($thumb_root.$file_info['filename'].".annotation-".$page_count.".jpg") && !is_cloudobject ($thumb_root.$file_info['filename'].".annotation-".$page_count.".jpg")) break;
          }

          if ($page_count > 1)
          {
            $mediaview = "
  <div style=\"position:absolute; width:".($width+15)."px; height:28px; text-align:right; ".($width < 420 ? "margin-top:-38px;" : "margin-top:-8px;")."\">
    <img src=\"".getthemelocation()."img/button_arrow_left.png\" onclick=\"gotoPage('previous');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" style=\"display:inline-block; vertical-align:middle;\" alt=\"".getescapedtext ($hcms_lang['back'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['back'][$lang], $hcms_charset, $lang)."\" />
    <select id=\"pagenumber\" onchange=\"gotoPage('none');\" title=\"".getescapedtext ($hcms_lang['page'][$lang], $hcms_charset, $lang)."\" style=\"display:inline-block; width:70px;\">";

            for ($i = 0; $i < $page_count; $i++)
            {
              $mediaview .= "
        <option value=\"".$i."\">".($i+1)."</option>";
            }

            $mediaview .= "
    </select> / ".$page_count."
    <img src=\"".getthemelocation()."img/button_arrow_right.png\" onclick=\"gotoPage('next');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\"  style=\"display:inline-block; vertical-align:middle;\" alt=\"".getescapedtext ($hcms_lang['forward'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['forward'][$lang], $hcms_charset, $lang)."\" />
  </div>";
          }

          $mediaview .= "
  <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/annotate/annotate.min.js\"></script>
	<script type=\"text/javascript\">
    // set annotation buttons
    function setAnnotationButtons ()
    {
      if (document.getElementById('annotationStop'))
      {
        document.getElementById('annotationStop').src = '".getthemelocation()."img/button_file_lock.png';
        document.getElementById('tool0').title = hcms_entity_decode('".getescapedtext ($hcms_lang['none'][$lang], $hcms_charset, $lang)."');
        document.getElementById('annotationStop').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['none'][$lang], $hcms_charset, $lang)."');
      }

      if (document.getElementById('annotationRectangle'))
      {
        document.getElementById('annotationRectangle').src = '".getthemelocation()."img/button_rectangle.png';
        document.getElementById('tool1').title = hcms_entity_decode('".getescapedtext ($hcms_lang['rectangle'][$lang], $hcms_charset, $lang)."');
        document.getElementById('annotationRectangle').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['rectangle'][$lang], $hcms_charset, $lang)."');
      }

      if (document.getElementById('annotationCircle'))
      {
        document.getElementById('annotationCircle').src = '".getthemelocation()."img/button_circle.png';
        document.getElementById('tool2').title = hcms_entity_decode('".getescapedtext ($hcms_lang['circle'][$lang], $hcms_charset, $lang)."');
        document.getElementById('annotationCircle').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['circle'][$lang], $hcms_charset, $lang)."');
      }

      if (document.getElementById('annotationText'))
      {
        document.getElementById('annotationText').src = '".getthemelocation()."img/button_textu.png';
        document.getElementById('tool3').title = hcms_entity_decode('".getescapedtext ($hcms_lang['text'][$lang], $hcms_charset, $lang)."');
        document.getElementById('annotationText').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['text'][$lang], $hcms_charset, $lang)."');
      }

      if (document.getElementById('annotationArrow'))
      {
        document.getElementById('annotationArrow').src = '".getthemelocation()."img/button_arrow.png';
        document.getElementById('tool4').title = hcms_entity_decode('".getescapedtext ($hcms_lang['arrow'][$lang], $hcms_charset, $lang)."');
        document.getElementById('annotationArrow').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['arrow'][$lang], $hcms_charset, $lang)."');
      }

      if (document.getElementById('annotationPen'))
      {
        document.getElementById('annotationPen').src = '".getthemelocation()."img/button_pen.png';
        document.getElementById('tool5').title = hcms_entity_decode('".getescapedtext ($hcms_lang['pen'][$lang], $hcms_charset, $lang)."');
        document.getElementById('annotationPen').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['pen'][$lang], $hcms_charset, $lang)."');
      }

      if (document.getElementById('annotationDownload'))
      {
        document.getElementById('annotationDownload').src = '".getthemelocation()."img/button_file_download.png';
        document.getElementById('download').title = hcms_entity_decode('".getescapedtext ($hcms_lang['download'][$lang], $hcms_charset, $lang)."');
        document.getElementById('annotationDownload').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['download'][$lang], $hcms_charset, $lang)."');
      }

      if (document.getElementById('annotationUndo'))
      {
        document.getElementById('annotationUndo').src = '".getthemelocation()."img/button_history_back.png';
        document.getElementById('undoaction').title = hcms_entity_decode('".getescapedtext ($hcms_lang['undo'][$lang], $hcms_charset, $lang)."');
        document.getElementById('annotationUndo').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['undo'][$lang], $hcms_charset, $lang)."');
      }

      if (document.getElementById('annotationRedo'))
      {
        document.getElementById('annotationRedo').src = '".getthemelocation()."img/button_history_forward.png';
        document.getElementById('redoaction').title = hcms_entity_decode('".getescapedtext ($hcms_lang['redo'][$lang], $hcms_charset, $lang)."');
        document.getElementById('annotationRedo').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['redo'][$lang], $hcms_charset, $lang)."');
      }

      if (document.getElementById('annotationHelp'))
      {
        document.getElementById('annotationHelp').src = '".getthemelocation()."img/button_info.png';
        document.getElementById('annotationHelp').title = hcms_entity_decode('".getescapedtext ($hcms_lang['select-a-tool-in-order-to-add-an-annotation'][$lang], $hcms_charset, $lang)."');
        document.getElementById('annotationHelp').alt = hcms_entity_decode('".getescapedtext ($hcms_lang['select-a-tool-in-order-to-add-an-annotation'][$lang], $hcms_charset, $lang)."');
      }

      // display zoom feature (disabled)
      document.getElementById('zoomlayer').style.display='inline';
    }

    $(document).ready(function(){

      // set annotation image file name
      $('#medianame').val('".$annotation_file."');

      // create annotation image
			$('#annotation').annotate({
        width: '".$width_annotation."',
        height: '".$height_annotation."',
				color: 'red',
				bootstrap: false,
        unselectTool: true,
				images: ['".cleandomain ($previewimage_link)."']
      });

      // set images for buttons
      setAnnotationButtons();

      // annotations download event
      $('#annotationDownload').click(function(event) {
        $('#annotation').annotate('export', {type: 'image/jpeg', quality: 1}, function(data){
          hcms_downloadURI (data, 'annotation.jpg');
        });
      });
    });
  </script>
  ";
        }
      }
      // -------------------------------------- if flash --------------------------------------- 
      elseif (!empty ($file_info['ext']) && substr_count ($swf_ext.".", $file_info['ext'].".") > 0)
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
      <table style=\"width:100%; margin:0; border-spacing:0; border-collapse:collapse;\">
        <tr>
          <td style=\"text-align:left;\">
            <object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0\" ".$style.">
              <param name=\"movie\" value=\"".createviewlink ($site, $mediafile_orig, $medianame)."\" />
              <param name=\"quality\" value=\"high\" />
              <embed src=\"".createviewlink ($site, $mediafile_orig, $medianame, true)."\" id=\"".$id."\" quality=\"high\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\" type=\"application/x-shockwave-flash\" ".$style." />
            </object>
        </td>
        </tr>";

        $mediaview .= "
        <tr>
          <td style=\"text-align:left;\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."<br /><hr /></td>
        </tr>
      </table>";
      }
      // --------------------------------------- if audio ----------------------------------------- 
      elseif (!empty ($file_info['ext']) && is_audio ($file_info['ext']))
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
          // create audio thumbnail from original file
          $create_media = createmedia ($site, $thumb_root, $thumb_root, $mediafile_orig, "", "origthumb", false, true);

          if ($create_media) $config = readmediaplayer_config ($thumb_root, $file_info['filename'].".config.orig");
        }

        // verify that the media files exist
        if (!empty ($config['mediafiles']) && is_array ($config['mediafiles']))
        {
          $temp_array = $config['mediafiles'];
          $config['mediafiles'] = array();

          foreach ($temp_array as $temp)
          {
            // full media record
            $temp_media = $temp;

            // remove type
            if (strpos ($temp, ";") > 0) list ($temp, $rest) = explode (";", $temp);

            if (is_file ($thumb_root.getobject ($temp))) $config['mediafiles'][] = $temp_media;
          }
        }
        // no media files
        else $config['mediafiles'] = array();

        // add original file as well if it is an AAC, FLAC, MP3, OGG, or WAV (supported formats by most of the browsers)
        if (empty ($config['mediafiles']) || !is_array ($config['mediafiles']) || sizeof ($config['mediafiles']) < 1)
        {
          if (strpos ($mediafile_orig, ".config.") == 0 && substr_count (".aac.flac.mp3.ogg.wav.", $file_info['ext'].".") > 0 && (is_file ($thumb_root.$mediafile_orig) || is_cloudobject ($thumb_root.$mediafile_orig)))
          {
            $config['mediafiles'] = array ($site."/".$mediafile_orig.";".getmimetype ($mediafile_orig));
          }
        }

        // use config values
        if (!empty ($config['width']) && $config['width'] > 0 && !empty ($config['height']) && $config['height'] > 0)
        {
          $mediawidth = $config['width'];
          $mediaheight = $config['height'];
          $mediaratio = $config['width'] / $config['height'];
        }

        // use default values
        if (empty ($mediawidth) || $mediawidth < 300 || empty ($mediaheight) || $mediaheight < 60)
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

        // generate player code
        $playercode = showaudioplayer ($site, $config['mediafiles'], $mediawidth, $mediaheight, "", $id, false, false, true, true);

        $mediaview .= "
        <table style=\"width:100%; margin:0; border-spacing:0; border-collapse:collapse;\">
          <tr>
            <td style=\"text-align:left;\">
            <!-- audio player begin -->
            <div id=\"videoplayer_container\" style=\"display:inline-block; text-align:left;\">
              ".$playercode."
              <div id=\"mediaplayer_segmentbar\" style=\"display:none; width:100%; height:22px; background-color:#808080; text-align:left; margin-bottom:8px;\"></div>";

              $mediaview .= "
              <div style=\"display:block; margin:3px;\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</div>";

        // audio rendering and embedding (requires the JS function 'setSaveType' provided by the template engine)
        if (is_supported ($mgmt_mediapreview, $file_info['orig_ext']) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
        {
          // edit, embed button
          if ($viewtype == "preview")
          {
            $mediaview .= "
              <div style=\"padding-top:10px;\">
                <input type=\"hidden\" id=\"VTT\" name=\"\" value=\"\" />
                <button type=\"button\" class=\"hcmsButtonGreen\" onclick=\"if (typeof setSaveType === 'function') setSaveType('mediarendering_so', '', 'post');\"><img src=\"".getthemelocation()."img/button_edit.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['edit'][$lang], $hcms_charset, $lang)."</button>
                <button type=\"button\" class=\"hcmsButtonBlue\" onclick=\"if (typeof setSaveType === 'function') setSaveType('mediaplayerconfig_so', '', 'post');\"><img src=\"".getthemelocation()."img/button_phpinclude.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['embed'][$lang], $hcms_charset, $lang)."</button>
              </div>";
          }
          // cut, embed, options button
          elseif ($viewtype == "preview_download" && valid_locationname ($location) && valid_objectname ($page))
          {
            $mediaview .= "
              <div style=\"padding-top:10px;\">
                <button type=\"button\" id=\"mediaplayer_cut\" class=\"hcmsButtonOrange\" onclick=\"setbreakpoint()\" style=\"display:none;\"><img src=\"".getthemelocation()."img/button_cut.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['audio-montage'][$lang], $hcms_charset, $lang)."</button>
                <button type=\"button\" id=\"mediaplayer_options\" class=\"hcmsButtonBlue\" onclick=\"document.getElementById('barbutton_0').click();\" style=\"display:none;\"><img src=\"".getthemelocation()."img/button_edit.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['options'][$lang], $hcms_charset, $lang)."</button>
                <button type=\"button\" id=\"mediaplayer_embed\" class=\"hcmsButtonBlue\" onclick=\"document.location.href='media_playerconfig.php?location=".url_encode($location_esc)."&page=".url_encode($page)."';\"><img src=\"".getthemelocation()."img/button_phpinclude.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['embed'][$lang], $hcms_charset, $lang)."</button>
              </div>";
          }
        }

        $mediaview .= "
            </div>
            <!-- audio player end -->
            <hr />
          </td>
        </tr>
      </table>";
      }
      // ---------------------------- if video ---------------------------- 
      elseif (!empty ($file_info['ext']) && is_video ($file_info['ext']))
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
          if (!is_numeric ($width) || $width <= 0) $width = 854;
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
        elseif ((is_file ($thumb_root.$mediafile_orig) || is_cloudobject ($thumb_root.$mediafile_orig)) && (empty ($mgmt_maxsizepreview[$file_info['orig_ext']]) || ($mediafilesize/1024) <= $mgmt_maxsizepreview[$file_info['orig_ext']]))
        {
          // create video thumbnail from original file
          $create_media = createmedia ($site, $thumb_root, $thumb_root, $mediafile_orig, "", "origthumb", false, true);

          if ($create_media) $config = readmediaplayer_config ($thumb_root, $file_info['filename'].".config.orig");
        }

        // verify that the media files exist
        if (!empty ($config['mediafiles']) && is_array ($config['mediafiles']))
        {
          $temp_array = $config['mediafiles'];
          $config['mediafiles'] = array();

          foreach ($temp_array as $temp)
          {
            // full media record
            $temp_media = $temp;

            // remove type
            if (strpos ($temp, ";") > 0) list ($temp, $type) = explode (";", $temp);

            // video preview file exists
            if (is_file ($thumb_root.getobject ($temp))) $config['mediafiles'][] = $temp_media;
          }
        }
        // no media files
        else $config['mediafiles'] = array();

        // no valid video file has been found
        if (empty ($config['mediafiles']) || !is_array ($config['mediafiles']))
        {
          // add original file as well if it is an MP4, WebM or OGG/OGV (supported formats by most of the browsers)
          if ($width > 854 || (sizeof ($config['mediafiles']) < 1 && $width <= 854))
          {
            if (strpos ($mediafile_orig, ".config.") == 0 && substr_count (".mp4.ogg.ogv.webm.", $file_info['orig_ext'].".") > 0 && (is_file ($thumb_root.$mediafile_orig) || is_cloudobject ($thumb_root.$mediafile_orig)))
            {
              if (!is_array ($config['mediafiles'])) $config['mediafiles'] = array();
              $temp = $site."/".$mediafile_orig.";".getmimetype ($mediafile_orig);
              array_unshift ($config['mediafiles'], $temp);
            }
            // try to create video thumbnail from original file
            elseif (!empty ($mgmt_config['recreate_preview']))
            {
              $create_media = createmedia ($site, $thumb_root, $thumb_root, $mediafile_orig, "", "origthumb", false, true);

              if ($create_media)
              {
                $config = readmediaplayer_config ($thumb_root, $file_info['filename'].".config.orig");
              }
            }
          }
        }

        // calculate ratio and reset original width and height
        if (!empty ($config['width']) && !empty ($config['height']))
        {
          $mediaratio = $config['width'] / $config['height'];
          $mediawidth = $width_orig = $config['width'];
          $mediaheight = $height_orig = $config['height'];

          // correct height for portrait videos
          if (empty ($height) && $height_orig > $width_orig && $height_orig > 1080)
          {
            $height = 1080;
          }
        }

        // set width and height of media file as file-parameter
        $mediaview .= "
        <!-- hyperCMS:width file=\"".$width_orig."\" -->
        <!-- hyperCMS:height file=\"".$height_orig."\" -->";

        // use default values
        if (empty ($mediawidth) || empty ($mediaheight))
        {
          $mediawidth = 854;
          $mediaheight = 480;
        }

        // new size may exceed the original image size
        $newsize = mediasize2frame ($mediawidth, $mediaheight, $width, $height, true);

        if (is_array ($newsize))
        {
          $mediawidth = $newsize['width'];
          $mediaheight = $newsize['height'];
        }

        // generate player code
        $playercode = showvideoplayer ($site, @$config['mediafiles'], $mediawidth, $mediaheight, "", $id, "", false, true, false, false, true, false, true, true);

        // create view link
        $preview_video = "";

        if (is_array ($config['mediafiles']))
        {
          foreach ($config['mediafiles'] as $temp)
          {
            if (strpos ($temp, "video/mp4") > 0 || substr ($temp, -4) == ".mp4") 
            {
              if (strpos ($temp, ";") > 0) list ($video_file, $rest) = explode (";", $temp);
              else $video_file = $temp;
              break;
            }
          }
        }

        if (!empty ($video_file)) $preview_video = createviewlink ($site, $video_file, $video_file, true);

        // style for 360 view
        $width_diff = 500 - intval ($mediawidth);
        if ($width_diff <= 0) $width_diff = 0;

        if (is_facerecognition ("sys") && $viewtype == "preview") $style360 = "left:0px; top:0px; width:".intval($mediawidth + $width_diff + 4)."px; height:".intval($mediaheight)."px;";
        else $style360 = "left:0px; top:0px; width:".intval($mediawidth)."px; height:".intval($mediaheight)."px;";

        // link for 360 view
        $link360 = $mgmt_config['url_path_cms']."media_360view.php?type=video&link=".url_encode($preview_video).($mediaratio > $switch_panoview ? "&view=horizontal" : "");

        // 360 video player code
        $playercode360 = "";

        if ((!empty ($video_file)) && !$is_mobile) $playercode360 = "
        <div id=\"hcms360View\" class=\"".$class."\" style=\"position:absolute; z-index:9910; display:none; ".$style360."\">
          <div style=\"position:absolute; right:4px; top:4px;\">
            <img name=\"hcms_mediaClose\" onClick=\"hcms_switchFormLayer ('hcms360View'); document.getElementById('hcms360videoplayer').src='';\" src=\"".getthemelocation()."img/button_close.png\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['close'][$lang])."\" title=\"".getescapedtext ($hcms_lang['close'][$lang])."\" onMouseOut=\"hcms_swapImgRestore();\" onMouseOver=\"hcms_swapImage('hcms_mediaClose','','".getthemelocation()."img/button_close_over.png',1);\" />
          </div>
          <iframe id=\"hcms360videoplayer\" src=\"\" frameborder=\"0\" style=\"width:100%; height:100%; border:0;\" allowFullScreen=\"true\" webkitallowfullscreen=\"true\" mozallowfullscreen=\"true\"></iframe>
        </div>";

        $mediaview .= "
        <table style=\"width:100%; margin:0; border-spacing:0; border-collapse:collapse;\">
          <tr>
            <td style=\"text-align:left;\">

            <!-- video player begin -->
            <div id=\"videoplayer_container\" style=\"display:inline-block; text-align:left;\">";

         if (((is_facerecognition ("sys") || is_annotation ()) && $viewtype == "preview")) $mediaview .= "
              <div id=\"annotationToolbar\" style=\"width:auto; height:36px; margin:4px 0px;\">
                <script type=\"text/javascript\">
                var annotationmode = false;

                function annotateButtonActive (id)
                {
                  var toolbar = document.getElementById('annotationToolbar');
                  var div = toolbar.getElementsByClassName('hcmsButton');
                  var classes;

                  for (var i=0; i<div.length; i++)
                  {
                    classes = div[i].classList;

                    // dectivate toolbar buttons
                    if (classes.contains('hcmsButtonActive')) classes.remove('hcmsButtonActive');

                    // activate selected toolbar button
                    if (div[i].id == id) classes.add('hcmsButtonActive');
                  }
                }
                </script>
                <div class=\"hcmsToolbarBlock\">
                  <img id=\"annotationStop\" onclick=\"annotateButtonActive(this.id); annotationmode=false;\" src=\"".getthemelocation()."img/button_file_lock.png\" class=\"hcmsButton hcmsButtonActive hcmsButtonSizeSquare\" title=\"".getescapedtext ($hcms_lang['none'][$lang], $hcms_charset, $lang)."\" alt=\"".getescapedtext ($hcms_lang['none'][$lang], $hcms_charset, $lang)."\" />
                  <img id=\"annotationRectangle\" onclick=\"annotateButtonActive(this.id); annotationmode=true;\" src=\"".getthemelocation()."img/button_rectangle.png\" class=\"hcmsButton hcmsButtonSizeSquare\" title=\"".getescapedtext ($hcms_lang['rectangle'][$lang], $hcms_charset, $lang)."\" alt=\"".getescapedtext ($hcms_lang['rectangle'][$lang], $hcms_charset, $lang)."\" />
                </div>
                <div class=\"hcmsToolbarBlock\">
                  <img id=\"annotationHelp\" src=\"".getthemelocation()."img/button_info.png\" class=\"hcmsButton hcmsButtonSizeSquare\" title=\"".getescapedtext ($hcms_lang['select-a-tool-in-order-to-add-an-annotation'][$lang], $hcms_charset, $lang)."\" alt=\"".getescapedtext ($hcms_lang['select-a-tool-in-order-to-add-an-annotation'][$lang], $hcms_charset, $lang)."\" />
                </div>
              </div>";

        $mediaview .= "
              <div style=\"position:relative; width:auto; height:auto;\" ".(((is_facerecognition ("sys") || is_annotation ()) && $viewtype == "preview") ? "onclick=\"if (annotationmode) createFaceOnVideo (event);\"" : "").">".$playercode360.$playercode."</div>
              <div id=\"mediaplayer_segmentbar\" style=\"display:none; width:100%; height:22px; background-color:#808080; text-align:left; margin-bottom:8px;\"></div>";
        
        $mediaview .= "
              <div style=\"display:block; margin:3px;\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</div>";

        // video rendering and embedding (requires the JS function 'setSaveType' provided by the template engine)
        if (is_supported ($mgmt_mediapreview, $file_info['orig_ext']) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
        {
          // VTT, detect faces, edit, embed button
          if ($viewtype == "preview")
          {
            $mediaview .= "
              <div style=\"padding-top:10px;\">
                <input type=\"hidden\" id=\"VTT\" name=\"\" value=\"\" />
                <button type=\"button\" class=\"hcmsButtonGreen\" onclick=\"hcms_openVTTeditor('vtt_container');\"><img src=\"".getthemelocation()."img/button_textl.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['video-text-track'][$lang], $hcms_charset, $lang)."</button>";
            if (is_facerecognition ("sys")) $mediaview .= "
                <button type=\"button\" class=\"hcmsButtonGreen\" onclick=\"detectFaceOnVideo();\"><img src=\"".getthemelocation()."img/pers_registration.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['detect-faces'][$lang], $hcms_charset, $lang)."</button>";
            $mediaview .= "
                <button type=\"button\" class=\"hcmsButtonGreen\" onclick=\"if (typeof setSaveType === 'function') setSaveType('mediarendering_so', '', 'post');\"><img src=\"".getthemelocation()."img/button_edit.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['edit'][$lang], $hcms_charset, $lang)."</button>";
            // 360 view button
            if (!$is_mobile && empty ($recognizefaces_service)) $mediaview .= "
                <button type=\"button\" class=\"hcmsButtonBlue\" onclick=\"if (document.getElementById('hcms360videoplayer').src!='".$link360."') document.getElementById('hcms360videoplayer').src='".$link360."'; else document.getElementById('hcms360videoplayer').src=''; hcms_switchFormLayer ('hcms360View');\"><img src=\"".getthemelocation()."img/icon_rotate.png\" class=\"hcmsIconList\" /> 360° ".getescapedtext ($hcms_lang['preview'][$lang], $hcms_charset, $lang)."</button>";
            $mediaview .= "
                <button type=\"button\" class=\"hcmsButtonBlue\" onclick=\"if (typeof setSaveType === 'function') setSaveType('mediaplayerconfig_so', '', 'post');\"><img src=\"".getthemelocation()."img/button_phpinclude.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['embed'][$lang], $hcms_charset, $lang)."</button>
              </div>";
          }
          // cut, options, embed button
          elseif ($viewtype == "preview_download" && valid_locationname ($location) && valid_objectname ($page))
          {
            $mediaview .= "
              <div style=\"padding-top:10px;\">
                <button type=\"button\" id=\"mediaplayer_cut\" class=\"hcmsButtonOrange\" onclick=\"setbreakpoint()\" style=\"display:none;\"><img src=\"".getthemelocation()."img/button_cut.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['video-montage'][$lang], $hcms_charset, $lang)."</button>
                <button type=\"button\" id=\"mediaplayer_options\" class=\"hcmsButtonBlue\" onclick=\"document.getElementById('barbutton_0').click();\" style=\"display:none;\"><img src=\"".getthemelocation()."img/button_edit.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['options'][$lang], $hcms_charset, $lang)."</button>
                <button type=\"button\" id=\"mediaplayer_embed\" class=\"hcmsButtonBlue\" onclick=\"document.location.href='media_playerconfig.php?location=".url_encode($location_esc)."&page=".url_encode($page)."';\"><img src=\"".getthemelocation()."img/button_phpinclude.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['embed'][$lang], $hcms_charset, $lang)."</button>
              </div>";
          }
          // 360 view button
          elseif ($viewtype == "media_only" && !empty ($video_file) && !$is_mobile && empty ($recognizefaces_service))
          {
            $mediaview .= "
              <div style=\"padding-top:10px;\">
                <button type=\"button\" class=\"hcmsButtonBlue\" onclick=\"if (typeof hideFaceOnVideo === 'function') hideFaceOnVideo(); if (document.getElementById('hcms360videoplayer').src!='".$link360."') document.getElementById('hcms360videoplayer').src='".$link360."'; else document.getElementById('hcms360videoplayer').src=''; hcms_switchFormLayer ('hcms360View');\"><img src=\"".getthemelocation()."img/icon_rotate.png\" class=\"hcmsIconList\" /> 360° ".getescapedtext ($hcms_lang['preview'][$lang], $hcms_charset, $lang)."</button>
              </div>";
          }
        }

        $mediaview .= "
            </div>
            <!-- video player end -->
            <hr />";

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
                 <select id=\"vtt_language\" name=\"vtt_language\" style=\"margin:2px 2px 0px 0px; width:298px;\" onchange=\"hcms_changeVTTlanguage()\">
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
          </td>
        </tr>
        <tr>
          <td style=\"text-align:left;\">

            <div id=\"vtt_container\" style=\"display:none; width:600px;\">
              <!-- VTT editor -->
              <div id=\"vtt_create\" class=\"hcmsInfoBox\" style=\"width:100%;\">
                <b>".getescapedtext ($hcms_lang['video-text-track'][$lang], $hcms_charset, $lang)."</b><br />
                <div style=\"float:left; padding:0; margin:0;\">".$lang_select."</div>
                <input type=\"hidden\" id=\"vtt_langcode\" name=\"vtt_langcode\" value=\"\" />
                <input type=\"text\" id=\"vtt_start\" name=\"start\" value=\"\" placeholder=\"".getescapedtext ($hcms_lang['start'][$lang], $hcms_charset, $lang)."\" maxlength=\"12\" style=\"float:left; margin:2px 2px 0px 0px; width:100px;\" readonly=\"readonly\" />
                <img src=\"".getthemelocation()."img/button_time.png\" onclick=\"setPlayerTime('vtt_start');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" style=\"float:left;\" alt=\"".getescapedtext ($hcms_lang['set'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['set'][$lang], $hcms_charset, $lang)."\" />
                <input type=\"text\" id=\"vtt_stop\" name=\"stop\" value=\"\" placeholder=\"".getescapedtext ($hcms_lang['end'][$lang], $hcms_charset, $lang)."\" maxlength=\"12\" style=\"float:left; margin:2px 2px 0px 4px; width:100px;\" readonly=\"readonly\" />
                <img src=\"".getthemelocation()."img/button_time.png\" onclick=\"setPlayerTime('vtt_stop');\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" style=\"float:left;\" alt=\"".getescapedtext ($hcms_lang['set'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['set'][$lang], $hcms_charset, $lang)."\" />
                <input type=\"text\" id=\"vtt_text\" name=\"text\" value=\"\" placeholder=\"".getescapedtext ($hcms_lang['text'][$lang], $hcms_charset, $lang)."\" maxlength=\"400\" style=\"float:left; margin:2px 2px 0px 0px; width:540px;\" />
                <img src=\"".getthemelocation()."img/button_save.png\" onclick=\"createVTTrecord()\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" style=\"float:left;\" alt=\"".getescapedtext ($hcms_lang['save'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['save'][$lang], $hcms_charset, $lang)."\" />
                <div style=\"clear:both;\"></div>
              </div>
              <div id=\"vtt_records_container\" class=\"hcmsInfoBox\" style=\"margin-top:10px; width:100%; height:200px; overflow:auto; white-space:nowrap;\">
                <div id=\"vtt_header\">
                  <div style=\"float:left; margin:2px 2px 0px 0px; width:98px;\"><b>".getescapedtext ($hcms_lang['start'][$lang], $hcms_charset, $lang)."</b></div>
                  <div style=\"float:left; margin:2px 2px 0px 0px; width:98px;\"><b>".getescapedtext ($hcms_lang['end'][$lang], $hcms_charset, $lang)."</b></div>
                  <div style=\"float:left; margin:2px 2px 0px 0px; width:340px;\"><b>".getescapedtext ($hcms_lang['text'][$lang], $hcms_charset, $lang)."</b></div>
                  <div style=\"clear:both;\"></div>
                </div>
                <div id=\"vtt_records\">
                </div>
              </div>
            </div>

            <script type=\"text/javascript\">
            // define delete button for VTT record
            var vtt_buttons = '<img src=\"".getthemelocation()."img/button_delete.png\" onclick=\"hcms_removeVTTrecord(this)\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" style=\"float:left;\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $hcms_charset, $lang)."\" />';
            var vtt_confirm = hcms_entity_decode ('".getescapedtext ($hcms_lang['copy-tracks-from-previously-selected-language'][$lang], $hcms_charset, $lang)."');

            function createVTTrecord ()
            {
              var result = hcms_createVTTrecord();

              if (!result) alert (hcms_entity_decode ('".getescapedtext ($hcms_lang['the-input-is-not-valid'][$lang], $hcms_charset, $lang)."'));
            }

            function setPlayerTime (id)
            {
              var player = videojs(\"".$id."\");
              var time = player.currentTime();
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

              if (id != '') document.getElementById(id).value = hours + ':' + minutes + ':' + seconds + '.' + milliseconds;

              return time;
            }
            </script>

            </td>
          </tr>";
          }
        } 

        $mediaview .= "
      </table>";
      }
      // ---------------------------------- if plain/clear text ---------------------------------- 
      elseif (!empty ($file_info['ext']) && substr_count (strtolower ($hcms_ext['cleartxt'].$hcms_ext['cms']).".", $file_info['ext'].".") > 0)
      {
        // prepare media file
        $temp = preparemediafile ($site, $media_root, $mediafile, $user);

        // if encrypted
        if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
        {
          $media_root = $temp['templocation'];
          $mediafile = $temp['tempfile'];
        }
        // if restored
        elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
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
          else $style = "style=\"width:100%; min-height:500px; -webkit-box-sizing:border-box; -moz-box-sizing:border-box; box-sizing:border-box;\""; 

          if ($viewtype == "template") $mediaview .= "
        <form name=\"editor\" method=\"post\">
          <input type=\"hidden\" name=\"site\" value=\"".$site."\" />
          <input type=\"hidden\" name=\"mediacat\" value=\"tpl\" />
          <input type=\"hidden\" name=\"mediafile\" value=\"".$mediafile."\" />
          <input type=\"hidden\" name=\"save\" value=\"yes\" />
          <input type=\"hidden\" name=\"token\" value=\"".createtoken ($user)."\" />";

          if ($viewtype == "template") $mediaview .= "
        <table class=\"hcmsTableNarrow\" style=\"width:100%; margin:2px;\">";
          else $mediaview .= "
        <table style=\"width:100%; margin:0; border-spacing:0; border-collapse:collapse;\">";

          // save button
          if ($viewtype == "template") $mediaview .= "
          <tr>
            <td style=\"text-align:left;\"><img onclick=\"document.forms['editor'].submit();\" name=\"save\" src=\"".getthemelocation()."img/button_save.png\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['save'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['save'][$lang], $hcms_charset, $lang)."\" /></td>
          </tr>";

          // disable text area
          if ($viewtype == "template") $disabled = "";
          else $disabled = "readonly=\"readonly\"";

          $mediaview .= "
          <tr>
            <td style=\"text-align:left;\"><textarea name=\"content\" id=\"".$id."\" ".$style." ".$disabled.">".$content."</textarea></td>
          </tr>
          <tr>
            <td style=\"text-align:left;\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td>
          </tr>
          <tr>
            <td><hr/></td>
          </tr>
        </table>";

          if ($viewtype == "template") $mediaview .= "
        </form>";
        }
      }
    }

    // ----------------------------- show standard file icon ------------------------------- 
    if (empty ($mediaview))
    {
      // use thumbnail if it is valid (larger than 10 bytes)
      if (is_file ($thumb_root.$file_info['filename'].".thumb.jpg") || is_cloudobject ($thumb_root.$file_info['filename'].".thumb.jpg"))
      {
        // thumbnail file
        $mediafile = $file_info['filename'].".thumb.jpg";

        // get file extension of new file (preview file)
        $file_info['ext'] = strtolower (strrchr ($mediafile, "."));

        $mediaview .= "
      <table style=\"width:100%; margin:0; border-spacing:0; border-collapse:collapse;\">
        <tr>
          <td style=\"text-align:left;\"><img src=\"".createviewlink ($site, $mediafile, $medianame, true).((!empty ($recognizefaces_service) && substr ($user, 0, 4) == "sys:") ? "&PHPSESSID=".session_id() : "")."\" id=\"".$id."\" alt=\"".$medianame."\" title=\"".$medianame."\" class=\"".$class."\" /></td>
        </tr>
        <tr>
          <td style=\"text-align:left;\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td>
        </tr>
        <tr>
          <td><hr/></td>
        </tr>"; 
      }
      // if no thumbnail/preview exists
      else
      {
        $mediaview .= "
      <table style=\"width:100%; margin:0; border-spacing:0; border-collapse:collapse;\">
        <tr>
          <td style=\"text-align:left;\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" id=\"".$id."\" alt=\"".$medianame."\" title=\"".$medianame."\" /></td>
        </tr>";

        $mediaview .= "
        <tr>
          <td style=\"text-align:left;\" class=\"hcmsHeadlineTiny\">".showshorttext($medianame, 40, false)."</td>
        </tr>
        <tr>
          <td><hr/></td>
        </tr>";
      }

      $mediaview .= "
      </table>";
    }

    if ($viewtype != "media_only")
    {
      // ------------------------------- width and height of other media files ------------------------------------
      // format file size
      if ($mediafilesize > 1000)
      {
        $mediafilesize = $mediafilesize / 1024;
        $unit = "MB";
      }
      else $unit = "KB";

      $mediafilesize = number_format ($mediafilesize, 0, ".", " ")." ".$unit;

      // output information
      $col_width = "min-width:120px; ";

      $mediaview .= "
    <input id=\"previewimage\" type=\"hidden\" name=\"previewimage\" value=\"".(!empty ($previewimage_path) ? hcms_encrypt ($previewimage_path) : "")."\" />
    <table class=\"hcmsTableNarrow\">";

      $mediaview .= "
      <tr>
        <td style=\"".$col_width."text-align:left; white-space:nowrap;\">".getescapedtext ($hcms_lang['owner'][$lang], $hcms_charset, $lang)." </td>
        <td class=\"hcmsHeadlineTiny\" style=\"text-align:left; white-space:nowrap;\">".(!empty ($owner) ? $owner : "")."</td>
      </tr>";

      $mediaview .= "
      <tr>
        <td style=\"".$col_width."text-align:left; white-space:nowrap;\">".getescapedtext ($hcms_lang['date-created'][$lang], $hcms_charset, $lang)." </td>
        <td class=\"hcmsHeadlineTiny\" style=\"text-align:left; white-space:nowrap;\">".(!empty ($date_created) ? showdate ($date_created, "Y-m-d H:i", $hcms_lang_date[$lang]) : "")."</td>
      </tr>";

      $mediaview .= "
      <tr>
        <td style=\"".$col_width."text-align:left; white-space:nowrap;\">".getescapedtext ($hcms_lang['date-modified'][$lang], $hcms_charset, $lang)." </td>
        <td class=\"hcmsHeadlineTiny\" style=\"text-align:left; white-space:nowrap;\">".(!empty ($date_modified) ? showdate ($date_modified, "Y-m-d H:i", $hcms_lang_date[$lang]) : "")."</td>
      </tr>";

      $mediaview .= "
      <tr>
        <td style=\"".$col_width."text-align:left; white-space:nowrap;\">".getescapedtext ($hcms_lang['published'][$lang], $hcms_charset, $lang)." </td>
        <td class=\"hcmsHeadlineTiny\" style=\"text-align:left; white-space:nowrap;\">".(!empty ($date_published) ? showdate ($date_published, "Y-m-d H:i", $hcms_lang_date[$lang]) : "")."</td>
      </tr>";

      $mediaview .= "
      <tr>
        <td style=\"".$col_width."text-align:left; white-space:nowrap;\">".getescapedtext ($hcms_lang['will-be-removed'][$lang], $hcms_charset, $lang)." </td>
        <td class=\"hcmsHeadlineTiny\" style=\"text-align:left; white-space:nowrap;\">".(!empty ($date_delete) ? showdate ($date_delete, "Y-m-d H:i", $hcms_lang_date[$lang]) : "")."</td>
      </tr>";

      // not for audio and video files
      if (!is_audio ($mediafile) && !is_video ($mediafile)) $mediaview .= "
      <tr>
        <td style=\"".$col_width."text-align:left; white-space:nowrap;\">".getescapedtext ($hcms_lang['file-size'][$lang], $hcms_charset, $lang)." </td>
        <td class=\"hcmsHeadlineTiny\" style=\"text-align:left; white-space:nowrap;\">".$mediafilesize."</td>
      </tr>";

      // only for images
      if (is_image ($mediafile) && !empty ($width_orig) && !empty ($height_orig))
      {
        // size in pixel of media file
        $mediaview .= "
      <tr>
        <td style=\"".$col_width."text-align:left; white-space:nowrap;\">".getescapedtext ($hcms_lang['size'][$lang], $hcms_charset, $lang)." </td>
        <td class=\"hcmsHeadlineTiny\" style=\"text-align:left; white-space:nowrap;\">".$width_orig."x".$height_orig." px</td>
      </tr>";

        // size in cm
        $mediaview .= "
      <tr>
        <td style=\"".$col_width."text-align:left; white-space:nowrap;\">".getescapedtext ($hcms_lang['size'][$lang], $hcms_charset, $lang)." (72 dpi) </td>
        <td class=\"hcmsHeadlineTiny\">".round(($width_orig / 72 * 2.54), 1)."x".round(($height_orig / 72 * 2.54), 1)." cm, ".round(($width_orig / 72), 1)."x".round(($height_orig / 72), 1)." inch</td>
      </tr>";

        // size in inch
        $mediaview .= "
      <tr>
        <td style=\"".$col_width."text-align:left; white-space:nowrap;\">".getescapedtext ($hcms_lang['size'][$lang], $hcms_charset, $lang)." (300 dpi) </td>
        <td class=\"hcmsHeadlineTiny\">".round(($width_orig / 300 * 2.54), 1)."x".round(($height_orig / 300 * 2.54), 1)." cm, ".round(($width_orig / 300), 1)."x".round(($height_orig / 300), 1)." inch</td>
      </tr>";
      }

      $mediaview .= "
      </table>";

      // --------------------- properties of video and audio files (original and thumbnail files) --------------------------
      if (is_array ($mgmt_mediapreview) && !empty ($file_info['ext']) && substr_count (strtolower ($hcms_ext['video'].$hcms_ext['audio']).".", $file_info['ext'].".") > 0)
      {
        $dimensions = array();
        $rotations = array();
        $durations = array();
        $bitrates = array();
        $video_codecs = array();
        $audio_codecs = array();
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

          // if encrypted
          if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
          {
            $media_root = $temp['templocation'];
            $mediafile = $temp['tempfile'];
          }
          // if restored
          elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
          {
            $media_root = $temp['location'];
            $mediafile = $temp['file'];
          }

          $videoinfo = getvideoinfo ($media_root.$mediafile);
        }

        $colcolor = "";

        // show the values
        if (is_array ($videoinfo))
        {
          // define row color
          if ($viewtype != "preview" && $viewtype != "preview_download") $colcolor = "hcmsHeadlineTiny";
          elseif ($colcolor == "hcmsRowData1") $colcolor = "hcmsRowData2";
          else $colcolor = "hcmsRowData1";

          $filesizes['original'] = '<td class="'.$colcolor.'" style="text-align:left; white-space:nowrap; padding:1px 3px;">'.$videoinfo['filesize'].'</td>';
          $dimensions['original'] = '<td class="'.$colcolor.'" style="text-align:left; white-space:nowrap; padding:1px 3px;">'.$videoinfo['dimension'].'</td>';
          $rotations['original'] = '<td class="'.$colcolor.'" style="text-align:left; white-space:nowrap; padding:1px 3px;">'.@$videoinfo['rotate'].' '.getescapedtext ($hcms_lang['degree'][$lang], $hcms_charset, $lang).'</td>';
          $durations['original'] = '<td class="'.$colcolor.'" style="text-align:left; white-space:nowrap; padding:1px 3px;">'.$videoinfo['duration_no_ms'].'</td>'; 
          $video_codecs['original'] = '<td class="'.$colcolor.'" style="text-align:left; white-space:nowrap; padding:1px 3px;">'.@$videoinfo['videocodec'].'</td>';
          $audio_codecs['original'] = '<td class="'.$colcolor.'" style="text-align:left; white-space:nowrap; padding:1px 3px;">'.@$videoinfo['audiocodec'].'</td>';
          $bitrates['original'] = '<td class="'.$colcolor.'" style="text-align:left; white-space:nowrap; padding:1px 3px;">'.@$videoinfo['videobitrate'].'</td>';
          $audio_bitrates['original'] = '<td class="'.$colcolor.'" style="text-align:left; white-space:nowrap; padding:1px 3px;">'.$videoinfo['audiobitrate'].'</td>';
          $audio_frequenzies['original'] = '<td class="'.$colcolor.'" style="text-align:left; white-space:nowrap; padding:1px 3px;">'.$videoinfo['audiofrequenzy'].'</td>';
          $audio_channels['original'] = '<td class="'.$colcolor.'" style="text-align:left; white-space:nowrap; padding:1px 3px;">'.$videoinfo['audiochannels'].'</td>';

          $download_link = "top.location.href='".cleandomain (createviewlink ($site, $mediafile_orig, $medianame, false, "download"))."'; return false;";
 
          // download button
          if (($viewtype == "preview" || $viewtype == "preview_download") && (empty ($downloadformats) || !empty ($downloadformats['video']['original'])))
          {
            $downloads['original'] = '
              <td class="hcmsHeadlineTiny" style="text-align:left;">
                <button class="hcmsButtonBlue" style="width:100%; margin:0;" onclick="'.$download_link.'">
                  <img src="'.getthemelocation().'img/button_file_download.png" class="hcmsIconList" /> '.getescapedtext ($hcms_lang['download'][$lang], $hcms_charset, $lang).'
                </button>
              </td>';

            // Youtube upload (not for portals)
            $portal = getsession ("hcms_portal");

            if (!empty ($mgmt_config['youtube_oauth2_client_id']) && !empty ($mgmt_config[$site]['youtube']) && $mgmt_config[$site]['youtube'] == true && is_file ($mgmt_config['abs_path_cms']."connector/youtube/index.php") && empty ($portal))
            {		
              $youtube_uploads['original'] = '
              <td class="hcmsHeadlineTiny" style="text-align:left;"> 
                <button type="button" name="media_youtube" class="hcmsButtonGreen" style="width:100%; margin:0;" onclick=\'parent.openPopup("'.$mgmt_config['url_path_cms'].'connector/youtube/index.php?site='.url_encode($site).'&page='.url_encode($page).'&location='.url_encode(getrequest_esc('location')).'");\'>
                  <img src="'.getthemelocation().'img/button_file_upload.png" class="hcmsIconList" /> '.getescapedtext ($hcms_lang['youtube'][$lang], $hcms_charset, $lang).'
                </button>
              </td>';
            }
          }
        }

        $videos = array ('<th style="'.$col_width.' text-align:left; padding:1px 3px;">'.getescapedtext ($hcms_lang['original'][$lang], $hcms_charset, $lang).'</th>');

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

                  // if encrypted
                  if (!empty ($temp['result']) && !empty ($temp['crypted']) && !empty ($temp['templocation']) && !empty ($temp['tempfile']))
                  {
                    $thumb_root = $temp['templocation'];
                    $video_thumbfile = $temp['tempfile'];
                  }
                  // if restored
                  elseif (!empty ($temp['result']) && !empty ($temp['restored']) && !empty ($temp['location']) && !empty ($temp['file']))
                  {
                    $thumb_root = $temp['location'];
                    $video_thumbfile = $temp['file'];
                  }

                  $videoinfo = getvideoinfo ($thumb_root.$video_thumbfile);
                }

                // show the values
                if (!empty ($video_thumbfile) && is_array ($videoinfo))
                {
                  // define row color
                  if ($colcolor == "hcmsRowData1") $colcolor = "hcmsRowData2";
                  else $colcolor = "hcmsRowData1";

                  // delete video button
                  $deletebutton = '';

                  if (($viewtype == "preview" || $viewtype == "preview_download") && $setlocalpermission['root'] == 1 && $setlocalpermission['delete'] == 1)
                  {
                    $deletebutton = '<img onClick="deleteMedia(\''.$video_thumbfile.'\', \'hcms'.strtoupper($media_extension).'\')" class="hcmsButtonTiny hcmsIconList" style="float:right;" src="'.getthemelocation().'img/button_delete.png" alt="'.getescapedtext ($hcms_lang['delete'][$lang]).'" alt="'.getescapedtext ($hcms_lang['delete'][$lang]).'" title="'.getescapedtext ($hcms_lang['delete'][$lang]).'" />';
                  }

                  $videos[$media_extension] = '<th class="hcms'.strtoupper($media_extension).'" style="'.$col_width.' text-align:left; padding:1px 3px;">'.strtoupper($media_extension).$deletebutton.'</th>';
 
                  // define video file name
                  $video_filename = substr ($medianame, 0, strrpos ($medianame, ".")).".".$media_extension;

                  $filesizes[$media_extension] = '<td class="'.$colcolor.' hcms'.strtoupper($media_extension).'" style="text-align:left; white-space:nowrap; padding:1px 3px;">'.$videoinfo['filesize'].'</td>';
                  $dimensions[$media_extension] = '<td class="'.$colcolor.' hcms'.strtoupper($media_extension).'" style="text-align:left; white-space:nowrap; padding:1px 3px;">'.@$videoinfo['dimension'].'</td>';
                  $rotations[$media_extension] = '<td class="'.$colcolor.' hcms'.strtoupper($media_extension).'" style="text-align:left; white-space:nowrap; padding:1px 3px;">'.@$videoinfo['rotate'].' '.getescapedtext ($hcms_lang['degree'][$lang], $hcms_charset, $lang).'</td>';
                  $durations[$media_extension] = '<td class="'.$colcolor.' hcms'.strtoupper($media_extension).'" style="text-align:left; white-space:nowrap; padding:1px 3px;">'.@$videoinfo['duration_no_ms'].'</td>';
                  $video_codecs[$media_extension] = '<td class="'.$colcolor.' hcms'.strtoupper($media_extension).'" style="text-align:left; white-space:nowrap; padding:1px 3px;">'.@$videoinfo['videocodec'].'</td>';
                  $audio_codecs[$media_extension] = '<td class="'.$colcolor.' hcms'.strtoupper($media_extension).'" style="text-align:left; white-space:nowrap; padding:1px 3px;">'.@$videoinfo['audiocodec'].'</td>';
                  $bitrates[$media_extension] = '<td class="'.$colcolor.' hcms'.strtoupper($media_extension).'" style="text-align:left; white-space:nowrap; padding:1px 3px;">'.@$videoinfo['videobitrate'].'</td>';
                  $audio_bitrates[$media_extension] = '<td class="'.$colcolor.' hcms'.strtoupper($media_extension).'" style="text-align:left; white-space:nowrap; padding:1px 3px;">'.@$videoinfo['audiobitrate'].'</td>';
                  $audio_frequenzies[$media_extension] = '<td class="'.$colcolor.' hcms'.strtoupper($media_extension).'" style="text-align:left; white-space:nowrap; padding:1px 3px;">'.@$videoinfo['audiofrequenzy'].'</td>';
                  $audio_channels[$media_extension] = '<td class="'.$colcolor.' hcms'.strtoupper($media_extension).'" style="text-align:left; white-space:nowrap; padding:1px 3px;">'.@$videoinfo['audiochannels'].'</td>';

                  $download_link = "top.location.href='".cleandomain (createviewlink ($site, $video_thumbfile, $video_filename, false, "download"))."'; return false;";
 
                  // download button
                  if (($viewtype == "preview" || $viewtype == "preview_download") && (empty ($downloadformats) || !empty ($downloadformats['video']['original'])))
                  {
                    $downloads[$media_extension] = '
                      <td class="hcmsHeadlineTiny hcms'.strtoupper($media_extension).'" style="text-align:left;">
                        <button class="hcmsButtonBlue" style="width:100%; margin:0;" onclick="'.$download_link.'">
                          <img src="'.getthemelocation().'img/button_file_download.png" class="hcmsIconList" /> '.getescapedtext ($hcms_lang['download'][$lang], $hcms_charset, $lang).'
                        </button>
                      </td>'; 

                    // Youtube upload (not for portals)
                    $portal = getsession ("hcms_portal");

                    if (!empty ($mgmt_config['youtube_oauth2_client_id']) && $mgmt_config[$site]['youtube'] == true && is_file ($mgmt_config['abs_path_cms']."connector/youtube/index.php") && empty ($portal))
                    {	
                      $youtube_uploads[$media_extension] = '
                      <td class="hcmsHeadlineTiny hcms'.strtoupper($media_extension).'" style="text-align:left;"> 
                        <button type="button" name="media_youtube" class="hcmsButtonGreen" style="width:100%; margin:0;" onclick=\'parent.openPopup("'.$mgmt_config['url_path_cms'].'connector/youtube/index.php?site='.url_encode($site).'&page='.url_encode($page).'&path='.url_encode($site."/".$video_thumbfile).'&location='.url_encode(getrequest_esc('location')).'");\'>
                          <img src="'.getthemelocation().'img/button_file_upload.png" class="hcmsIconList" /> '.getescapedtext ($hcms_lang['youtube'][$lang], $hcms_charset, $lang).'
                        </button>
                      </td>';
                    }
                  }
                }
              }
            }
          }
        }

        // delete media
        if (($viewtype == "preview" || $viewtype == "preview_download") && $setlocalpermission['root'] == 1 && $setlocalpermission['delete'] == 1)
        {
          $mediaview .= "
        <script type=\"text/javascript\">
        function deleteMedia (mediafile, classname)
        {
          var result = false;

          if (mediafile != '')
          {
            // AJAX request
            $.ajax({
              async: false,
              type: 'POST',
              url: '".cleandomain ($mgmt_config['url_path_cms'])."service/deletemedia.php',
              data: {'location': '".$location_esc."', 'media': mediafile},
              dataType: 'json',
              success: function(data){ if(data.success) {result = true;} }
            });
          }

          if (classname != '' && result == true)
          {
            var cols = document.getElementsByClassName(classname);

            // hide table cols
            for (var i=0; i<cols.length; i++)
            {
              cols[i].style.display = 'none';
            }
          }
          else
          {
            alert ('".getescapedtext ($hcms_lang['error-occured'][$lang], $hcms_charset, $lang)."');
          }
        }
        </script>";
        }

        $mediaview .= "
        <table class=\"hcmsTableNarrow\" style=\"margin-top:10px;\">";

        // generate output	
        if (is_array ($videos) && !$is_version && ($viewtype == "preview" || $viewtype == "preview_download")) $mediaview .= '
        <tr><th>&nbsp;</th>'.implode ("", $videos).'</tr>';		
        // Filesize
        if (is_array ($filesizes)) $mediaview .= '
        <tr><td style="'.$col_width.'text-align:left; white-space:nowrap;">'.getescapedtext ($hcms_lang['file-size'][$lang], $hcms_charset, $lang).'&nbsp;</td>'.implode ("", $filesizes).'</tr>';
        // Dimension
        if ($is_video && is_array ($dimensions)) $mediaview .= '
        <tr><td style="'.$col_width.'text-align:left; white-space:nowrap;">'.getescapedtext ($hcms_lang['width-x-height'][$lang], $hcms_charset, $lang).'&nbsp;</td>'.implode ("", $dimensions).'</tr>';
        // Rotations
        if (is_array ($rotations)) $mediaview .= '
        <tr><td style="'.$col_width.'text-align:left; white-space:nowrap;">'.getescapedtext ($hcms_lang['rotate'][$lang], $hcms_charset, $lang).'&nbsp;</td>'.implode ("", $rotations).'</tr>';		
        // Durations
        if (is_array ($durations)) $mediaview .= '
        <tr><td style="'.$col_width.'text-align:left; white-space:nowrap;">'.getescapedtext (substr ($hcms_lang['duration-hhmmss'][$lang], 0, strpos ($hcms_lang['duration-hhmmss'][$lang], "(")), $hcms_charset, $lang).'&nbsp;</td>'.implode ("", $durations).'</tr>';		
        // Video codec
        if ($is_video && !empty ($video_codecs) && is_array ($video_codecs)) $mediaview .= '
        <tr><td style="'.$col_width.'text-align:left; white-space:nowrap;">'.getescapedtext ($hcms_lang['video-codec'][$lang], $hcms_charset, $lang).'&nbsp;</td>'.implode ("", $video_codecs).'</tr>';
        // Audio codec
        if (!empty ($audio_codecs) && is_array ($audio_codecs)) $mediaview .= '
        <tr><td style="'.$col_width.'text-align:left; white-space:nowrap;">'.getescapedtext ($hcms_lang['audio-codec'][$lang], $hcms_charset, $lang).'&nbsp;</td>'.implode ("", $audio_codecs).'</tr>';
        // Bitrate
        if ($is_video && is_array ($bitrates)) $mediaview .= '
        <tr><td style="'.$col_width.'text-align:left; white-space:nowrap;">'.getescapedtext ($hcms_lang['video-bitrate'][$lang], $hcms_charset, $lang).'&nbsp;</td>'.implode ("", $bitrates).'</tr>';
        // Audio bitrate
        if (is_array ($audio_bitrates)) $mediaview .= '
        <tr><td style="'.$col_width.'text-align:left; white-space:nowrap;">'.getescapedtext ($hcms_lang['audio-bitrate'][$lang], $hcms_charset, $lang).'&nbsp;</td>'.implode ("", $audio_bitrates).'</tr>';
        // Audio frequenzy
        if (is_array ($audio_frequenzies)) $mediaview .= '
        <tr><td style="'.$col_width.'text-align:left; white-space:nowrap;">'.getescapedtext ($hcms_lang['audio-frequenzy'][$lang], $hcms_charset, $lang).'&nbsp;</td>'.implode ("", $audio_frequenzies).'</tr>';
        // Audio frequenzy
        if (is_array ($audio_channels)) $mediaview .= '
        <tr><td style="'.$col_width.'text-align:left; white-space:nowrap;">'.getescapedtext ($hcms_lang['audio-channels'][$lang], $hcms_charset, $lang).'&nbsp;</td>'.implode ("", $audio_channels).'</tr>';
        // Download
        if (is_array ($downloads) && sizeof ($downloads) > 0) $mediaview .= '
        <tr><td>&nbsp;</td>'.implode ("", $downloads).'</tr>';
        // Youtube
        if (!empty ($mgmt_config[$site]['youtube']) && $mgmt_config[$site]['youtube'] == true && is_file ($mgmt_config['abs_path_cms']."connector/youtube/index.php"))
        {
          if (is_array ($youtube_uploads) && sizeof ($youtube_uploads) > 0) $mediaview .= '
        <tr><td>&nbsp;</td>'.implode ("", $youtube_uploads).'</tr>';
        }

        $mediaview .= "
        </table>";
      }

      // save duration of original media file in hidden field so it can be accessed for video editing
      if (!empty ($videoinfo['duration'])) $mediaview .= "
      <input type=\"hidden\" id=\"mediaplayer_duration\" name=\"mediaplayer_duration\" value=\"".$videoinfo['duration']."\" />";
    }

    return $mediaview;
  }
  // no vald media file
  else return false;
}

// --------------------------------------- showcompexplorer -------------------------------------------
// function: showcompexplorer ()
// input: publication name [string], current explorer location [string], object location [string] (optional), object name [string] (optional), 
//        component category [single,multi,media] (optional), search expression [string] (optional), search format [object,document,image,video,audio,watermark] (optional), 
//        media-type [audio,binary,component,compressed,flash,image,text,video,watermark] (optional), view tpye [list,gallery] (optional), thumbnail size in pixel [integer]
//        callback of CKEditor [string] (optional), saclingfactor for images [integer] (optional)
// output: explorer with search / false on error

// description:
// Creates the component explorer including the search form and upload function

function showcompexplorer ($site, $dir, $location_esc="", $page="", $compcat="multi", $search_expression="", $search_format="", $mediatype="", $lang="en", $callback="", $scalingfactor="1", $view="list", $thumbsize=100)
{
  global $mgmt_config, $siteaccess, $pageaccess, $compaccess, $rootpermission, $globalpermission, $localpermission, $hiddenfolder, $html5file, $temp_complocation, $hcms_charset, $hcms_lang, $user;

  if (valid_publicationname ($site) && (valid_locationname ($dir) || $dir == ""))
  {
    // load file extension defintions
    require ($mgmt_config['abs_path_cms']."include/format_ext.inc.php");

    // initialize
    if (empty ($temp_complocation) || !is_array ($temp_complocation)) $temp_complocation = array();

    // get location in component structure from session
    if (!valid_locationname ($dir) && !empty ($temp_complocation[$site])) 
    {
      $dir = $temp_complocation[$site];

      if (!is_dir ($dir))
      {
        $dir = "";

        unset ($temp_complocation[$site]);

        setsession ('hcms_temp_complocation', $temp_complocation, true);
      }
    }

    // load publication inheritance setting
    $inherit_db = inherit_db_read ();
    $parent_array = inherit_db_getparent ($inherit_db, $site);

    // if not configured as DAM, define root location if no dir was provided
    if (empty ($mgmt_config[$site]['dam']) && $dir == "")
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
    elseif (!empty ($mgmt_config[$site]['dam']) && $dir == "")
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

    // convert location
    $dir = deconvertpath ($dir, "file");
    $location = deconvertpath ($location_esc, "file");

    // local access permissions
    $ownergroup = accesspermission ($site, $dir, "comp");
    $setlocalpermission = setlocalpermission ($site, $ownergroup, "comp");

    // set location in component structure in session
    if ($site != "" && valid_locationname ($dir))
    {
      $temp_complocation[$site] = $dir;

      setsession ('hcms_temp_complocation', $temp_complocation, true);
    }

    // media format
    // only for watermark images
    if ($mediatype == "watermark") $format_ext = ".jpg.jpeg.png.gif";
    elseif ($mediatype == "audio") $format_ext = strtolower ($hcms_ext['audio']);
    elseif ($mediatype == "binary") $format_ext = strtolower ($hcms_ext['binary']);
    elseif ($mediatype == "component") $format_ext = strtolower ($hcms_ext['cms']);
    elseif ($mediatype == "compressed") $format_ext = strtolower ($hcms_ext['compressed']);
    elseif ($mediatype == "flash") $format_ext = strtolower ($hcms_ext['flash']);
    elseif ($mediatype == "image") $format_ext = strtolower ($hcms_ext['image'].$hcms_ext['rawimage']);
    elseif ($mediatype == "text") $format_ext = strtolower ($hcms_ext['cms'].$hcms_ext['bintxt'].$hcms_ext['cleartxt']);
    elseif ($mediatype == "video") $format_ext = strtolower ($hcms_ext['video'].$hcms_ext['rawvideo']);
    else $format_ext = "";

    // javascript code
    $result = "<!-- Jquery and Jquery UI Autocomplete -->
<script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/jquery/jquery.min.js\"></script>
<script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/jquery-ui/jquery-ui.min.js\"></script>
<script type=\"text/javascript\" async=\"\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/lazysizes/lazysizes.min.js\"></script>
<script type=\"text/javascript\">
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

function submitForm ()
{
  if (document.forms['searchform_general'])
  {
    var form = document.forms['searchform_general'];
    form.submit();
  }
}

$(document).ready(function()
{
  var available_expressions = [".(is_array (getsearchhistory ($user)) ? implode (",\n", getsearchhistory ($user)) : "")."];

  $(\"#search_expression\").autocomplete({
    source: available_expressions
  });
});
</script>";

    // current location
    $location_name = getlocationname ($site, $dir, "comp", "path");

    $result .= "
    <span class=\"hcmsHeadline\" style=\"padding:3px 0px 3px 0px; display:block;\">".getescapedtext ($hcms_lang['select-object'][$lang], $hcms_charset, $lang)."</span>
    <span class=\"hcmsHeadlineTiny\" style=\"padding:3px 0px 3px 0px; display:block;\">".$location_name."</span>";

    // file upload
    if ($compcat == "media" && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1 && $search_expression == "")
    {
      // Upload Button
      $result .= "
      <div style=\"text-align:left; padding:2px; width:100%;\">
        <button name=\"UploadButton\" class=\"hcmsButtonGreen hcmsButtonSizeHeight\" style=\"width:184px; margin-right:4px; float:left;\" type=\"button\" onClick=\"";
        // only new upload window supported
        $result .= "hcms_openWindow('".cleandomain ($mgmt_config['url_path_cms'])."popup_upload_html.php?uploadmode=multi&site=".url_encode($site)."&cat=comp&location=".url_encode($dir_esc)."', '', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=yes,resizable=yes', 800, 600);";
        $result .= "\">".getescapedtext ($hcms_lang['upload-file'][$lang], $hcms_charset, $lang)."</button>
        <img class=\"hcmsButtonTiny hcmsButtonSizeSquare\" onClick=\"document.location.reload();\" src=\"".getthemelocation()."img/button_view_refresh.png\" alt=\"".getescapedtext ($hcms_lang['refresh'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['refresh'][$lang], $hcms_charset, $lang)."\" />
      </div>
      <div style=\"clear:both;\"></div>";
    }
    // create new component
    elseif (($compcat == "single" || $compcat == "multi") && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1 && $search_expression == "")
    {
      $result .= "
      <div style=\"text-align:left; padding:2px; width:100%;\">
        <button name=\"UploadButton\" class=\"hcmsButtonGreen hcmsButtonSizeHeight\" style=\"width:184px; margin-right:4px; float:left;\" type=\"button\" onClick=\"hcms_openWindow('".cleandomain ($mgmt_config['url_path_cms'])."frameset_content.php?site=".url_encode($site)."&cat=comp&location=".url_encode($dir_esc)."', '', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes', ".windowwidth ("object").", ".windowheight ("object").");\">".getescapedtext ($hcms_lang['new-component'][$lang], $hcms_charset, $lang)."</button>
        <img class=\"hcmsButtonTiny hcmsButtonSizeSquare\" onClick=\"document.location.reload();\" src=\"".getthemelocation()."img/button_view_refresh.png\" alt=\"".getescapedtext ($hcms_lang['refresh'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['refresh'][$lang], $hcms_charset, $lang)."\" />
      </div>
      <div style=\"clear:both;\"></div>";
    }

    // search form
    if (!empty ($mgmt_config['db_connect_rdbms']))
    {
      $result .= "
    <div id=\"searchForm\" style=\"padding:2px; width:100%;\">
      <form name=\"searchform_general\" method=\"post\" action=\"\">
        <input type=\"hidden\" name=\"dir\" value=\"".$dir_esc."\" />
        <input type=\"hidden\" name=\"view\" value=\"".$view."\" />
        <input type=\"hidden\" name=\"site\" value=\"".$site."\" />
        <input type=\"hidden\" name=\"location\" value=\"".$location_esc."\" />
        <input type=\"hidden\" name=\"page\" value=\"".$page."\" />
        <input type=\"hidden\" name=\"compcat\" value=\"".$compcat."\" />
        <input type=\"hidden\" name=\"mediatype\" value=\"".$mediatype."\" />
        <input type=\"hidden\" name=\"lang\" value=\"".$lang."\" />
        <input type=\"hidden\" name=\"callback\" value=\"".$callback."\" />

        <input type=\"text\" name=\"search_expression\" id=\"search_expression\" placeholder=\"".getescapedtext ($hcms_lang['search'][$lang], $hcms_charset, $lang)."\" value=\"".(!empty ($search_expression) ? html_encode ($search_expression) : "")."\" ".
        "style=\"width:184px;\" maxlength=\"200\" onclick=\"showOptions();\" /><img name=\"SearchButton\" src=\"".getthemelocation()."img/button_ok.png\" onClick=\"submitForm();\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('SearchButton','','".getthemelocation()."img/button_ok_over.png',1)\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" alt=\"OK\" title=\"OK\" />";

      // search options
      if (($compcat == "media" && $mediatype == "") || !empty ($mgmt_config[$site]['dam'])) $result .= "
        <div id=\"searchOptions\" class=\"hcmsInfoBox\" style=\"width:184px; margin:2px 0px 8px 0px; display:none;\">
          &nbsp;<b>".$hcms_lang['search-for-file-type'][$lang]."</b><br />
          <input type=\"checkbox\" name=\"search_format[object]\" value=\"comp\" checked=\"checked\" />".$hcms_lang['components'][$lang]."<br />
          <input type=\"checkbox\" name=\"search_format[image]\" value=\"image\" checked=\"checked\" />".$hcms_lang['image'][$lang]."<br />
          <input type=\"checkbox\" name=\"search_format[document]\" value=\"document\" checked=\"checked\" />".$hcms_lang['document'][$lang]."<br />
          <input type=\"checkbox\" name=\"search_format[video]\" value=\"video\" checked=\"checked\" />".$hcms_lang['video'][$lang]."<br />
          <input type=\"checkbox\" name=\"search_format[audio]\" value=\"audio\" checked=\"checked\" />".$hcms_lang['audio'][$lang]."<br />
        </div>";

      $result .= "
      </form>
    </div>";
    }

    $result .= "
    <table class=\"hcmsTableNarrow\" style=\"min-width:218px;\">";

    // parent directory
    if (
         (empty ($mgmt_config[$site]['dam']) && substr_count ($dir, $mgmt_config['abs_path_comp']) > 0 && $dir != $mgmt_config['abs_path_comp']) || 
         (!empty ($mgmt_config[$site]['dam']) && $setlocalpermission['root'] == 1)
       )
    {
      //get parent directory
      $updir_esc = getlocation ($dir_esc);

      if ($callback == "") $result .= "
      <tr>
        <td style=\"text-align:left; white-space:nowrap;\" colspan=\"2\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode($updir_esc)."&compcat=".url_encode($compcat)."&mediatype=".url_encode($mediatype)."&site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&scaling=".url_encode($scalingfactor)."&view=".url_encode($view)."\"><img src=\"".getthemelocation()."img/back.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['back'][$lang], $hcms_charset, $lang)."</a></td>
      </tr>";
      else $result .= "
      <tr>
        <td style=\"text-align:left; white-space:nowrap;\" colspan=\"2\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode($updir_esc)."&site=".url_encode($site)."&compcat=".url_encode($compcat)."&mediatype=".url_encode($mediatype)."&lang=".url_encode($lang)."&callback=".url_encode($callback)."&scaling=".url_encode($scalingfactor)."&view=".url_encode($view)."\"><img src=\"".getthemelocation()."img/back.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['back'][$lang], $hcms_charset, $lang)."</a></td>
      </tr>";
    }
    elseif ($search_expression != "")
    {
      if ($callback == "") $result .= "
      <tr>
        <td style=\"text-align:left; white-space:nowrap;\" colspan=\"2\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode("%comp%/")."&compcat=".url_encode($compcat)."&mediatype=".url_encode($mediatype)."&site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&scaling=".url_encode($scalingfactor)."&view=".url_encode($view)."\"><img src=\"".getthemelocation()."img/back.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['back'][$lang], $hcms_charset, $lang)."</a></td>
      </tr>";
      else $result .= "
      <tr>
        <td style=\"text-align:left; white-space:nowrap;\" colspan=\"2\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode("%comp%/")."&site=".url_encode($site)."&compcat=".url_encode($compcat)."&mediatype=".url_encode($mediatype)."&lang=".url_encode($lang)."&callback=".url_encode($callback)."&scaling=".url_encode($scalingfactor)."&view=".url_encode($view)."\"><img src=\"".getthemelocation()."img/back.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['back'][$lang], $hcms_charset, $lang)."</a></td>
      </tr>";
    }

    $result .= "
    </table>";

    // -------------------------------- search results ------------------------------------
    if (trim ($search_expression) != "")
    {
      if ($mediatype != "") $search_format = array ($mediatype);

      $object_array = rdbms_searchcontent ($dir_esc, "", $search_format, "", "", "", array($search_expression), $search_expression, "", "", "", "", "", "", "", "", 100);

      if (is_array ($object_array))
      {
        foreach ($object_array as $hash=>$object_item)
        {
          $entry = $object_item['objectpath'];

          if ($hash != "count" && $entry != "")
          {
            $authorized = false;

            // if DAM
            if (!empty ($mgmt_config[$site]['dam']))
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

              if ($entry_object !== false)
              {
                // folders
                if ($entry_object == ".folder")
                {
                  // remove _gsdata_ directory created by Cyberduck
                  if (getobject ($entry_location) == "_gsdata_")
                  {
                    deletefolder ($site, getlocation ($entry_location), getobject ($entry_location), $user);
                  }
                  else
                  {
                    $comp_entry_dir[] = $entry_location.$entry_object;
                  }
                }
                // files
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
    elseif (!empty ($dir) && is_dir ($dir))
    {
      // get all files in dir
      $scandir = scandir ($dir);

      // get all comp_outdir entries in folder and file array
      if ($scandir)
      {
        foreach ($scandir as $comp_entry)
        {
          if ($comp_entry != "" && $comp_entry != "." && $comp_entry != ".." && !is_hiddenfile ($comp_entry))
          {
            if ($dir != $mgmt_config['abs_path_comp'] || ($dir == $mgmt_config['abs_path_comp'] && ($mgmt_config[$site]['inherit_comp'] == true && is_array ($parent_array) && in_array ($comp_entry, $parent_array)) || $comp_entry == $site))
            {
              // folders
              if (is_dir ($dir.$comp_entry))
              {
                // remove _gsdata_ directory created by Cyberduck
                if ($comp_entry == "_gsdata_")
                {
                  deletefolder ($site, $dir_esc, $comp_entry, $user);
                }
                else
                {
                  $comp_entry_dir[] = $dir_esc.$comp_entry."/.folder";
                }
              }
              // files
              elseif (is_file ($dir.$comp_entry) && $comp_entry != ".folder")
              {
                $comp_entry_file[] = $dir_esc.$comp_entry;
              }
            }
          }
        }
      }
    }

    // -------------------------------- prepare output  -------------------------------- 

    $result .= "
    <table class=\"hcmsTableNarrow\" style=\"min-width:218px;\">";

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

          if ($dirname != "" && $folder_info['deleted'] == false)
          {
            $folder_path = getlocation ($dirname);
            $location_name = getlocationname ($site, $folder_path, "comp", "path");

            // define icon
            if ($dir == $mgmt_config['abs_path_comp']) $icon = getthemelocation()."img/site.png";
            else $icon = getthemelocation()."img/".$folder_info['icon'];

            if ($view == "gallery")
            {
              $thumbnail = "<img src=\"".$icon."\" style=\"margin-top:4px; height:".$thumbsize."px;\" /><br/>";
            }
            else
            {
              $thumbnail = "<img src=\"".$icon."\" class=\"hcmsIconList\" /> ";
            }

            if ($folder_info != false && $folder_info['deleted'] == false)
            {
              if ($callback == "") $result .= "
            <tr>
              <td style=\"".($view == "gallery" ? "height:".$thumbsize."px; text-align:center;" : "text-align:left;")." vertical-align:bottom; white-space:nowrap;\" colspan=\"2\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode($folder_path)."&site=".url_encode($site)."&compcat=".url_encode($compcat)."&mediatype=".url_encode($mediatype)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&scaling=".url_encode($scalingfactor)."&view=".url_encode($view)."\" title=\"".$location_name."\">".$thumbnail.showshorttext($folder_info['name'], 24, false)."</a></td>
            </tr>";
              else $result .= "
            <tr>
              <td style=\"width:28px; text-align:left; vertical-align:bottom; white-space:nowrap;\" colspan=\"2\"><a href=\"".$_SERVER['PHP_SELF']."?dir=".url_encode($folder_path)."&site=".url_encode($site)."&compcat=".url_encode($compcat)."&mediatype=".url_encode($mediatype)."&lang=".url_encode($lang)."&callback=".url_encode($callback)."&scaling=".url_encode($scalingfactor)."&view=".url_encode($view)."\" title=\"".$location_name."\">".$thumbnail.showshorttext($folder_info['name'], 20, false)."</a></td>
            </tr>";
            }
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
          // object info
          $comp_info = getfileinfo ($site, $object, "comp");

          if ($object != "" && $comp_info['deleted'] == false)
          {
            // correct extension if object is unpublished
            if (substr ($object, -4) == ".off") $object = substr ($object, 0, strlen ($object) - 4);

            // get name
            $comp_name = getlocationname ($site, $object, "comp", "path");

            // shorten name
            // if ($compcat != "media" && strlen ($comp_name) > 50) $comp_name = "...".substr (substr ($comp_name, -50), strpos (substr ($comp_name, -50), "/")); 

            if (
                 !empty ($comp_info) && empty ($comp_info['deleted']) && 
                 $dir.$object != $location.$page && 
                 (
                   ($compcat != "media" && empty ($mgmt_config[$site]['dam']) && $comp_info['type'] == "Component") || // standard components if not DAM for component tag
                   ($compcat != "media" && !empty ($mgmt_config[$site]['dam']) && (empty ($format_ext) || substr_count ($format_ext.".", $comp_info['ext'].".") > 0)) || // any type if no mediatype is requested and if DAM for component tag
                   ($compcat == "media" && ($comp_info['type'] != "Component" || !empty ($mgmt_config[$site]['dam'])) && ($mediatype == "" || $mediatype == "component" || substr_count ($format_ext.".", $comp_info['ext'].".") > 0)) // media assets for media tag
                 )
               )
            {
              $comp_path = $object;

              // warning if file extensions don't match and HTTP include is off and it is not a DAM
              if ($compcat != "media" && !$mgmt_config[$site]['dam'] && $mgmt_config[$site]['http_incl'] == false && ($comp_info['ext'] != $page_info['ext'] && $comp_info['ext'] != ".page")) $alert = "test = confirm(hcms_entity_decode('".getescapedtext ($hcms_lang['the-object-types-do-not-match'][$lang], $hcms_charset, $lang)."'));";
              else $alert = "test = true;";

              // thumbnail of media asset 
              $objectinfo = getobjectinfo ($site, getlocation ($object), getobject ($object));

              // gallery view
              if ($view == "gallery")
              {
                // use thumbnail file
                if (!empty ($objectinfo['media']))
                {
                  $mediafile = $objectinfo['media'];
                  $mediainfo = getfileinfo ($site, $mediafile, "comp");
                  $thumbnailfile = $mediainfo['filename'].".thumb.jpg";
                  $thumbnail = "";
                  $mediadir = getmedialocation ($site, $objectinfo['media'], "abs_path_media").$site."/";

                  $container_info = getmetadata_container ($objectinfo['container_id']);

                  // use original image size from RDBMS
                  $style_size = "";

                  if (!empty ($container_info['width']) && !empty ($container_info['height']))
                  {
                    // if thumbnail is smaller than defined thumbnail size
                    if ($container_info['width'] < $thumbsize && $container_info['height'] < $thumbsize)
                    {
                      $style_size = "width:".$container_info['width']."px; height:".$container_info['height']."px;";
                    }
                  }

                  // listview - view option for un/published objects
                  if ($comp_info['published'] == false) $class_image = "class=\"lazyload hcmsImageItem hcmsIconOff\"";
                  else $class_image = "class=\"lazyload hcmsImageItem\"";

                  $thumbnail = "<img data-src=\"".cleandomain (createviewlink ($site, $thumbnailfile, $objectinfo['name'], false, "wrapper", $comp_info['icon']))."\" ".$class_image." style=\"".$style_size." margin-top:10px;\" /><br/>";
                }
                // use standard file icon if it is not a media object 
                else
                {
                  // gallery view - view option for un/published objects
                  if ($comp_info['published'] == false) $class_image = "class=\"hcmsIconOff\"";
                  else $class_image = "";

                  $thumbnail = "<img src=\"".getthemelocation()."img/".$comp_info['icon']."\" ".$class_image." style=\"margin-top:10px; width:".$thumbsize."px; height:".$thumbsize."px\" /><br/>";
                }
              }
              // list view - view option for un/published objects
              else
              {
                if ($comp_info['published'] == false) $class_image = "class=\"hcmsIconList hcmsIconOff\"";
                else $class_image = "class=\"hcmsIconList\"";

                $thumbnail = "<img src=\"".getthemelocation()."img/".$comp_info['icon']."\" ".$class_image." /> ";
              }

              if ($compcat == "single")
              {
                $result .= "
              <tr>
                <td style=\"".($view == "gallery" ? "height:".$thumbsize."px; text-align:center;" : "text-align:left;")." vertical-align:bottom; white-space:nowrap;\"><a href=\"javascript:void(0);\" onClick=\"".$alert." if (test == true) sendCompInput('".$comp_name."','".$comp_path."');\" title=\"".$comp_name."\">".$thumbnail.showshorttext($comp_info['name'], 24, false)."</a></td>
                <td style=\"width:28px; text-align:right; vertical-align:bottom; white-space:nowrap;\"><a href=\"javascript:void(0);\" onClick=\"".$alert." if (test == true) sendCompInput('".$comp_name."','".$comp_path."');\"><img src=\"".getthemelocation()."img/button_ok.png\" class=\"hcmsIconList\" alt=\"OK\" title=\"OK\" /></a></td>
              </tr>";
              }
              elseif ($compcat == "multi")
              {
                $result .= "
              <tr>
                <td style=\"".($view == "gallery" ? "height:".$thumbsize."px; text-align:center;" : "text-align:left;")." vertical-align:bottom; white-space:nowrap;\"><a href=\"javascript:void(0);\" onClick=\"".$alert." if (test == true) sendCompOption('".$comp_name."','".$comp_path."');\" title=\"".$comp_name."\">".$thumbnail.showshorttext($comp_info['name'], 24, false)."</a></td>
                <td style=\"width:28px; text-align:right; vertical-align:bottom; white-space:nowrap;\"><a href=\"javascript:void(0);\" onClick=\"".$alert." if (test == true) sendCompOption('".$comp_name."','".$comp_path."');\"><img src=\"".getthemelocation()."img/button_ok.png\" class=\"hcmsIconList\" alt=\"OK\" title=\"OK\" /></a></td>
              </tr>";
              }
              elseif ($compcat == "media")
              {
                if ($callback == "") $result .= "
              <tr>
                <td style=\"".($view == "gallery" ? "height:".$thumbsize."px; text-align:center;" : "text-align:left;")." vertical-align:bottom; white-space:nowrap;\"><a href=\"javascript:void(0);\" onClick=\"".$alert." if (test == true) sendMediaInput('".$comp_name."','".$comp_path."'); parent.frames['mainFrame2'].location.href='media_view.php?site=".url_encode($site)."&mediacat=cnt&mediatype=".url_encode($mediatype)."&mediaobject=".url_encode($comp_path)."&scaling=".url_encode($scalingfactor)."';\" title=\"".$comp_name."\">".$thumbnail.showshorttext($comp_info['name'], 24)."</a></td>
                <td style=\"width:28px; text-align:right; vertical-align:bottom; white-space:nowrap;\"><a href=\"javascript:void(0);\" onClick=\"".$alert." if (test == true) sendMediaInput('".$comp_name."','".$comp_path."'); parent.frames['mainFrame2'].location.href='media_view.php?site=".url_encode($site)."&mediacat=cnt&mediatype=".url_encode($mediatype)."&mediaobject=".url_encode($comp_path)."&scaling=".url_encode($scalingfactor)."';\"><img src=\"".getthemelocation()."img/button_ok.png\" class=\"hcmsIconList\" alt=\"OK\" title=\"OK\" /></a></td>
              </tr>";
                else $result .= "
              <tr>
                <td style=\"".($view == "gallery" ? "height:".$thumbsize."px; text-align:center;" : "text-align:left;")." vertical-align:bottom; white-space:nowrap;\"><a href=\"javascript:void(0);\" onClick=\"parent.frames['mainFrame2'].location.href='text_media_select.php?site=".url_encode($site)."&mediacat=cnt&mediatype=".url_encode($mediatype)."&mediaobject=".url_encode($comp_path)."&lang=".url_encode($lang)."&callback=".url_encode($callback)."&scaling=".url_encode($scalingfactor)."';\" title=\"".$comp_name."\">".$thumbnail.showshorttext($comp_info['name'], 24)."</a></td>
                <td style=\"width:28px; text-align:right; vertical-align:bottom; white-space:nowrap;\"><a href=\"javascript:void(0);\" onClick=\"parent.frames['mainFrame2'].location.href='text_media_select.php?site=".url_encode($site)."&mediacat=cnt&mediatype=".url_encode($mediatype)."&mediaobject=".url_encode($comp_path)."&lang=".url_encode($lang)."&callback=".url_encode($callback)."&scaling=".url_encode($scalingfactor)."';\"><img src=\"".getthemelocation()."img/button_ok.png\" class=\"hcmsIconList\" alt=\"OK\" title=\"OK\" /></a></td>
              </tr>";
              }
            }
          }
        }
      }
    }

    $result .= "
    </table>";

    // result
    return $result;
  }
  else return false;
}

// --------------------------------------- showeditor -------------------------------------------
// function: showeditor ()
// input: publication name [string], hypertag name [string], hypertag id [string], content [string], width of the editor [integer], height of the editor [integer], toolbar set [string], 2 digit language code [string], dpi for scaling images [integer]
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

    // transform escaped < > due to issue with CKEditor unescaping these characters
    $contentbot = str_replace('&lt;', '&amp;lt;', $contentbot);
    $contentbot = str_replace('&gt;', '&amp;gt;', $contentbot);

    // define class-name for comment tags
    if (strpos ("_".$hypertagname, "comment") == 1) $classname = "class=\"is_comment\"";
    else $classname = "";

    return "
      <textarea id=\"".$hypertagname."_".str_replace (":", "_", $id)."\" name=\"".$hypertagname."[".$id."]\" ".$classname.">".$contentbot."</textarea>
      <script type=\"text/javascript\">
        CKEDITOR.replace( '".$hypertagname."_".$id."',
        {
          baseHref:					               		'".$publ_config['url_publ_page']."',
          customConfig:             			  	'".cleandomain ($mgmt_config['url_path_cms'])."javascript/ckeditor/ckeditor_custom/editorf_config.js',
          language:	              						'".$lang."',
          scayt_sLang:		              			'".getscaytlang ($lang)."',
          height:					              			'".$sizeheight."',
          width:							              	'".$sizewidth."',
          filebrowserImageBrowseUrl:	    		'".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_text.php?site=".url_encode($site)."&mediacat=cnt&mediatype=image&scaling=".url_encode($scalingfactor)."',
          filebrowserFlashBrowseUrl:	    		'".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_text.php?site=".url_encode($site)."&mediacat=cnt&mediatype=flash',
          filebrowserVideoBrowseUrl:	    		'".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_text.php?site=".url_encode($site)."&mediacat=cnt&mediatype=video',
          filebrowserLinkBrowsePageUrl:	    	'".cleandomain ($mgmt_config['url_path_cms'])."text_link_explorer.php?site=".url_encode($site)."',
          filebrowserLinkBrowseComponentUrl:	'".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_text.php?site=".url_encode($site)."&mediacat=cnt&mediatype=',
          toolbar:	              						'".$toolbar."',
          cmsLink:	              						'".cleandomain ($mgmt_config['url_path_cms'])."'
        });
      </script>";
  }
  else return false;
}

// --------------------------------------- showinlineeditor_head -------------------------------------------
// function: showinlineeditor_head ()
// input: 2 digit language code [string]
// output: rich text editor code for html head section / false on error

// description:
// Returns the rich text editor code (JS, CSS) for include into the html head section

function showinlineeditor_head ($lang)
{
  global $mgmt_config, $hcms_charset, $hcms_lang;

  if (is_array ($mgmt_config) && $lang != "")
  {
    return "
    <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/jquery/jquery.min.js\"></script>
    <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/signature/jSignature.min.noconflict.js\"></script>
    <!--[if lt IE 9]>
    <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/signature/flashcanvas.js\"></script>
    <![endif]-->
    <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."/javascript/ckeditor/ckeditor/ckeditor.js\"></script>
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

            if ((val=val.value) != '') 
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

      function hcms_resetSignature (id)
      {
        // clears the canvas and rerenders the decor on it
        jq_inline('#signature_'+id).jSignature('reset');
        // empty hidden field
        jq_inline('#signature_'+id).val('');

        return false;
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
        outline: 1px dotted auto;
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
        outline: 1px dotted auto;
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

    <link rel=\"stylesheet\" hypercms_href=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/rich_calendar.css\">
    <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/rich_calendar.min.js\"></script>
    <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/rc_lang_en.js\"></script>
    <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/rc_lang_de.js\"></script>
    <script type=\"text/javascript\" src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/rich_calendar/domready.js\"></script>
    ";
  }
  else return false;
}

// --------------------------------------- showinlineeditor -------------------------------------------
// function: showinlineeditor ()
// input: publication name [string], hypertag [string], hypertag id [string], content [string], width of the editor [integer], height of the editor [integer], toolbar set [string], 2 digit language code [string], 
//        content-type [string], category [page,comp], converted location [string], object name [string], container name [string], DB-connect file name [string], security token [string]
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
  $id = $id_orig = getattribute ($hypertag, "id");

  // get label text
  $label = getattribute ($hypertag, "label");

  // get format (if date)
  $format = getattribute ($hypertag, "format"); 
  if ($format == "") $format = "%Y-%m-%d";

  if (substr ($hypertagname, 0, strlen ("arttext")) == "arttext")
  {
    // get article id
    $artid = getartid ($id);

    // element id
    $elementid = getelementid ($id); 

    // define label
    if ($label == "") $labelname = $artid." - ".$elementid;
    else $labelname = $artid." - ".getlabel ($label, $lang);;
  }
  else
  {
    // define label
    if ($label == "") $labelname = $id;
    else $labelname = getlabel ($label, $lang);;
  }

  // correct IDs of article
  if ($id != "" && strpos ("_".$id, ":") > 0) $id = str_replace (":", "_", $id);

  if (is_array ($mgmt_config) && valid_publicationname ($site) && $hypertagname != "" && $id != "" && $lang != "")
  {
    if (valid_publicationname ($site) && !is_array ($publ_config)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"); 

    // building the title for the element
    $title = $labelname.": ";

    // the tag of the element containing the content
    $tag = "span";

    // the CSS class of the element containing the content
    $css_class = "hcms_editable";

    // is the contenteditable attribute set
    $contenteditable = false;

    switch ($hypertagname)
    {
      case 'arttexts':
      case 'texts':
        $title .= getescapedtext ($hcms_lang['signature'][$lang], $hcms_charset, $lang);
        break;
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
      case 'arttextk':
      case 'textk':
        $title .= getescapedtext ($hcms_lang['keywords'][$lang], $hcms_charset, $lang);
        $css_class = "";
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

    // building the display of the content
    $return = "<".$tag." id=\"".$hypertagname."_".$id."\" title=\"".$title."\" class=\"".$css_class."\" ".($contenteditable ? 'contenteditable="true" ' : '').">".(empty($contentbot) ? $defaultText : $contentbot)."</".$tag.">";

    // building of the specific editor
    $element = "";

    switch ($hypertagname)
    {
      // signature
      case 'arttexts':
      case 'texts':

        // do not add the content display for signatures to the form that is submitted including the element that is sent
        $return = "
        <script type=\"text/javascript\">
        jq_inline().ready(function() {
          // display the form for signatures
          form = jq_inline('#hcms_form_".$hypertagname."_".$id."');
          form.css('display', 'inline');

          // initialize the jSignature widget with options
          jq_inline('#signature_".$hypertagname."_".$id."').jSignature({ 'lineWidth': 2, 'decor-color': 'transparent' });

          jq_inline('#signature_".$hypertagname."_".$id."').bind('change', function(e) {
            var constraint = \"".$constraint."\";

            // create image (image = PNG, svgbase64 = SVG)
            if (jq_inline('#signature_".$hypertagname."_".$id."').jSignature('getData', 'native').length > 0) 
            {
              var imagedata = jq_inline('#signature_".$hypertagname."_".$id."').jSignature('getData', 'image');
              // set image data string
              jq_inline('#".$hypertagname."_".$id."').val(imagedata);
            }
            else jq_inline('#".$hypertagname."_".$id."').val('');

            if (constraint == '' || (check = hcms_validateForm('".$hypertagname."_".$id."','', constraint)))
            {
              jq_inline.post(
                \"".cleandomain ($mgmt_config['url_path_cms'])."service/savecontent.php\", 
                jq_inline('#hcms_form_".$hypertagname."_".$id."').serialize(), 
                function(data)
                {
                  if (data.message.length !== 0)
                  {
                    alert (hcms_entity_decode(data.message));
                  }				
                }, 
                \"json\"
              );
            }
          });
          
          // show existing signature image and hide signature field
          if (jq_inline('#signatureimage_".$hypertagname."_".$id."').length)
          {
            jq_inline('#signatureimage_".$hypertagname."_".$id."').show();
            jq_inline('#signaturefield_".$hypertagname."_".$id."').hide();
          }
          else
          {
            jq_inline('#signaturefield_".$hypertagname."_".$id."').show();
          }
        });
        </script>
        ";

        // size of the element
        $style = "";
        if ($sizewidth > 0) $style .= "width:".$sizewidth."px;";
        // no height will be used since the signature canvas will define the height
        // if ($sizeheight > 0) $style .= "height:".$sizeheight."px; ";

        // existing signature
        if (!empty ($contentbot) && strlen ($contentbot) > 333) $signature_image = "<img id=\"signatureimage_".$hypertagname."_".$id."\" onclick=\"jq_inline('#signatureimage_".$hypertagname."_".$id."').hide(); jq_inline('#signaturefield_".$hypertagname."_".$id."').show();\" src=\"data:".$contentbot."\" style=\"".$style." display:none; padding:0 !important; max-width:100%; max-height:100%;\" />";
        else $signature_image = "";
        
        $element = "
        <div class=\"hcms_editable\" title=\"".$title."\">
          ".$signature_image."
          <div id=\"signaturefield_".$hypertagname."_".$id."\" style=\"".$style."\">
            <div id=\"signature_".$hypertagname."_".$id."\" style=\"outline:2px dotted #FF9000; background-color:#FFFFFF; color:darkblue;\"></div>
            <div style=\"all:unset; position:relative; float:right; margin:-25px 5px 0px 0px;\">
              <img src=\"".getthemelocation("day")."img/button_delete.png\" onclick=\"hcms_resetSignature('".$hypertagname."_".$id."');\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang], $hcms_charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang], $hcms_charset, $lang)."\" style=\"all:unset; display:inline !important; width:20px; height:20px; border:0; cursor:pointer; z-index:9999999;\"/>
            </div>
            <input id=\"".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id."]\" type=\"hidden\" value=\"".$contentbot."\" />
          </div>
        </div>";

        break;

      // checkbox
      case 'arttextc':
      case 'textc':

        // extract text value of checkbox
        $value = getattribute ($hypertag, "value");

        $return .= "
          <script type=\"text/javascript\">
          jq_inline().ready(function() 
          {
            var oldcheck_".$hypertagname."_".$id." = '';

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
                  \"".cleandomain ($mgmt_config['url_path_cms'])."service/savecontent.php\", 
                  jq_inline('#hcms_form_".$hypertagname."_".$id."').serialize(), 
                  function(data)
                  {
                    if (data.message.length !== 0)
                    {
                      alert (hcms_entity_decode(data.message));
                    }				
                  }, 
                  \"json\"
                );

                elem.text((newcheck == \"&nbsp;\" ? '' : newcheck));
              }
              else
              {
                checkbox.prop('checked', (oldcheck_".$hypertagname."_".$id." == '' ? false : true));
              }

              form.hide();
              elem.show();
            });
          });
        </script>
        ";

        $element = "<input type=\"hidden\" name=\"".$hypertagname."[".$id_orig."]\" value=\"\" /><input title=\"".$labelname.": ".getescapedtext ($hcms_lang['set-checkbox'][$lang], $hcms_charset, $lang)."\" type=\"checkbox\" id=\"hcms_checkbox_".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id_orig."]\" value=\"".$value."\"".($value == $contentbot ? ' checked ' : '')." />".$labelname;
        
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
                    \"".cleandomain ($mgmt_config['url_path_cms'])."service/savecontent.php\", 
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

                  elem.html(newdate == '' ? '".$defaultText."' : newdate);
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

        $element = "<input title=\"".$labelname.": ".getescapedtext ($hcms_lang['pick-a-date'][$lang], $hcms_charset, $lang)."\" type=\"text\" id=\"hcms_datefield_".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id_orig."]\" value=\"".$contentbot."\" style=\"color:#000; background:#FFF; font-family:Verdana,Arial,Helvetica,sans-serif; font-size:12px; font-weight:normal;\" /><br>";
        
        break;

      // unformatted text
      case 'arttextu':
      case 'textu':

        $return .= "
          <script type=\"text/javascript\">
          jq_inline().ready(function() 
          {
            var oldtext_".$hypertagname."_".$id." = '';

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
                  \"".cleandomain ($mgmt_config['url_path_cms'])."service/savecontent.php\", 
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

          // initialize size
          setTimeout(function(){ hcms_initTextarea('hcms_txtarea_".$hypertagname."_".$id."', document.getElementById('".$hypertagname."_".$id."').offsetWidth, document.getElementById('".$hypertagname."_".$id."').offsetHeight); }, 800);
          </script>
        ";

        // textarea
        $element = "<textarea title=\"".$title."\" id=\"hcms_txtarea_".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id_orig."]\" onkeyup=\"hcms_adjustTextarea(this);\" class=\"hcms_editable_textarea\">".$contentbot."</textarea>";
        
        break;

      // keywords
      case 'arttextk':
      case 'textk':

        // no editor since a tag link is used
        $element = "";
      
        break;
        
      // text options/list
      case 'arttextl':
      case 'textl':

        $return .= "
          <script type=\"text/javascript\">
          jq_inline().ready(function() 
          {
            var oldselect_".$hypertagname."_".$id." = '';

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
                  \"".cleandomain ($mgmt_config['url_path_cms'])."service/savecontent.php\", 
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

        // get list entries
        $list = "";

        // extract source file (file path or URL) for text list
        $list_sourcefile = getattribute ($hypertag, "file");

        if ($list_sourcefile != "")
        {
          $list .= getlistelements ($list_sourcefile);
          // replace commas and vertical bars
          $list = str_replace (",", "|", str_replace("|", "&#124;", $list));
        }

        // extract text list
        $list_add = getattribute ($hypertag, "list");

        // add seperator
        if ($list_add != "") $list = $list_add."|".$list;

        // get list entries
        if ($list != "")
        {
          $list_array = explode ("|", trim ($list, "|"));

          $element = "<select title=\"".$labelname.": ".getescapedtext ($hcms_lang['edit-text-options'][$lang], $hcms_charset, $lang)."\" id=\"hcms_selectbox_".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id_orig."]\" style=\"color:#000; background:#FFF; font-family:Verdana,Arial,Helvetica,sans-serif; font-size:12px; font-weight:normal;\">\n";

          foreach ($list_array as $elem)
          {
            $element .= "  <option value=\"".$elem."\"".($elem == $contentbot ? ' selected ' : '').">".$elem."</option>\n";
          }

          $element .= "</select>\n";
        }
          
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

        // transform escaped < > due to issue with CKEditor unescaping these characters
        $contentbot = str_replace('&lt;', '&amp;lt;', $contentbot);
        $contentbot = str_replace('&gt;', '&amp;gt;', $contentbot);

        $return .= "
          <script type=\"text/javascript\">
            jq_inline().ready(function() 
            {
              var oldtext_".$hypertagname."_".$id." = '';

              jq_inline('#".$hypertagname."_".$id."').click(function(event) 
              { // Prevent propagation so that only ckeditor is shown and no operations from a parent onClick is performed
                event.stopPropagation();
              }).mouseover(function()
              { // Overwriting the title everytime the mouse moves over the element, because CKEditor does overwrite it sometimes
                jq_inline(this).attr('title', decodeURIComponent('".$title."'));
              });

              CKEDITOR.inline( '".$hypertagname."_".$id."',
              {
                baseHref:					               		'".$publ_config['url_publ_page']."',
                customConfig:             			  	'".cleandomain ($mgmt_config['url_path_cms'])."javascript/ckeditor/ckeditor_custom/inline_config.js',
                language:	              						'".$lang."',
                scayt_sLang:		              			'".getscaytlang ($lang)."',
                height:					              			'".$sizeheight."',
                width:							              	'".$sizewidth."',
                filebrowserImageBrowseUrl:	    		'".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_text.php?site=".url_encode($site)."&mediacat=cnt&mediatype=image&scaling=".url_encode($scalingfactor)."',
                filebrowserFlashBrowseUrl:	    		'".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_text.php?site=".url_encode($site)."&mediacat=cnt&mediatype=flash',
                filebrowserVideoBrowseUrl:	    		'".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_text.php?site=".url_encode($site)."&mediacat=cnt&mediatype=video',
                filebrowserLinkBrowsePageUrl:	    	'".cleandomain ($mgmt_config['url_path_cms'])."text_link_explorer.php?site=".url_encode($site)."',
                filebrowserLinkBrowseComponentUrl:	'".cleandomain ($mgmt_config['url_path_cms'])."frameset_edit_text.php?site=".url_encode($site)."&mediacat=cnt&mediatype=',
                toolbar:	              						'".$toolbar."',
                cmsLink:	              						'".cleandomain ($mgmt_config['url_path_cms'])."',
                on: {
                  focus: function( event ) {
                    oldtext_".$hypertagname."_".$id." = jq_inline.trim(event.editor.getData());

                    if (hcms_stripTags(oldtext_".$hypertagname."_".$id.") == hcms_stripTags(decodeURIComponent('".$defaultText."')))
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
                        \"".cleandomain ($mgmt_config['url_path_cms'])."service/savecontent.php\", 
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

        $element = "<textarea title=\"".$title."\" id=\"hcms_txtarea_".$hypertagname."_".$id."\" name=\"".$hypertagname."[".$id_orig."]\">".$contentbot."</textarea>";
        
        break;

      default:
        break;
    }

    // Adding the form that is submitted including the element that is sent
    $return .= "
      <form style=\"display:none;\" method=\"post\" id=\"hcms_form_".$hypertagname."_".$id."\">
        <input type=\"hidden\" name=\"contenttype\" value=\"".$contenttype."\" /> 
        <input type=\"hidden\" name=\"site\" value=\"".$site."\" /> 
        <input type=\"hidden\" name=\"cat\" value=\"".$cat."\" /> 
        <input type=\"hidden\" name=\"location\" value=\"".$location_esc."\" />
        <input type=\"hidden\" name=\"page\" value=\"".$page."\" /> 
        <input type=\"hidden\" name=\"contentfile\" value=\"".$contentfile."\" />
        <input type=\"hidden\" name=\"db_connect\" value=\"".$db_connect."\" />
        <input type=\"hidden\" name=\"tagname\" value=\"".$hypertagname."\" /> 
        <input type=\"hidden\" name=\"id\" value=\"".$id."\" /> 
        <input type=\"hidden\" name=\"width\" value=\"".$sizewidth."\" /> 
        <input type=\"hidden\" name=\"height\" value=\"".$sizeheight."\" />
        <input type=\"hidden\" name=\"toolbar\" value=\"".$toolbar."\" /> 
        <input type=\"hidden\" id=\"savetype\" name=\"savetype\" value=\"auto\" />
        <input type=\"hidden\" name=\"token\" value=\"".$token."\" />
        ".$element."
      </form>\n";
  }

  return $return;
}

// ------------------------- showvideoplayer -----------------------------
// function: showvideoplayer()
// input:
// video array containing the different video sources [array], 
// width of the video in pixel [integer], 
// height of the video in pixel [integer], 
// link to the logo which is displayed before you click on play (If the value is null the default logo will be used) [string], 
// ID of the video (will be generated when empty) [string], 
// title for this video [string], 
// autoplay video on load (true), default is false [boolean], 
// view the video in full screen [boolean], 
// play loop [boolean] (optional), 
// muted/no sound [boolean] (optional), 
// player controls and selectable marker/faces gallery [boolean] (optional), 
// use video in iframe [boolean] (optional), 
// reload video sources to prevent the browser cache to show the same video even if it has been changed [boolean] (optional)
// remove domain name from the URL of the video sources and poster [boolean] (optional)

// output: HTML code of the video player / false on error

// description:
// Generates a html segment for the video player code

function showvideoplayer ($site, $video_array, $width=854, $height=480, $logo_url="", $id="", $title="", $autoplay=true, $fullscreen=true, $loop=false, $muted=false, $controls=true, $iframe=false, $force_reload=false, $cleandomain=false)
{
  global $mgmt_config;

  // default size
  if (intval ($width) < 0) $width = 854;
  if (intval ($height) < 0) $height = 480;

  // min video width in pixel for the display of the gallery
  $gallery_min_width = 420;
  
  // link to flash player (fallback player)
  $flashplayer = $mgmt_config['url_path_cms']."javascript/video/jarisplayer.swf";

  // if no ID has been provided
  if (empty ($id)) $id = "media_".uniqid(); 

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
      if (!empty ($media))
      {
        $container_id = getmediacontainerid ($media);

        if ($container_id > 0) $vtt_filename = $container_id;
      }

      // remove domain name
      if (!empty ($cleandomain)) $url = cleandomain ($url);
      
      if ($url != "") $sources .= "    <source src=\"".$url."\" ".$type."/>\n";
    }

    // logo from video thumb image
    if (!empty ($media)) 
    {
      $logo_file = getobject ($media);
      $media_dir = getmedialocation ($site, $media, "abs_path_media");
    }

    // define logo if undefined
    if (empty ($logo_url) && !empty ($media_dir))
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
        if (!empty ($cleandomain)) $logo_url = cleandomain ($logo_url);
      }
    }

    // video thumbnails
    $thumb_bar = "";
    $thumb_items = array();

    if (!empty ($container_id) && intval ($container_id) > 0 && is_dir ($media_dir.$site."/".$container_id))
    {
      $scandir = scandir ($media_dir.$site."/".$container_id);

      if (is_array ($scandir) && sizeof ($scandir) > 0)
      {
        sort ($scandir);
        $sec = -10;

        foreach ($scandir as $temp)
        {
          if ($temp != "." && $temp != ".." && substr ($temp, 0, 9) == "thumbnail" && is_file ($media_dir.$site."/".$container_id."/".$temp))
          {
            if (!empty ($cleandomain)) $source_url = cleandomain ($mgmt_config['url_path_cms']);
            else $source_url = $mgmt_config['url_path_cms'];

            if ($sec >= 0) $thumb_items[] = "    ".$sec.": { src: '".$source_url."?wm=".hcms_encrypt ($site."/".$container_id."/".$temp)."', width: '120px' }";
            $sec = $sec + 5;
          }
        }

        if (sizeof ($thumb_items) > 0) $thumb_bar .= "
      <script type=\"text/javascript\">
      // initialize video.js
      var video = videojs('".$id."');        
      video.thumbnails({\n".implode (",\n", $thumb_items)."});
      </script>\n";
      }
    }

    // video overlay for gallery (requires controls and a min width)
    $overlay_height = 144;
    $overlay = "";
    $thumb_items = array();

    if (!empty ($controls) && intval ($width) >= $gallery_min_width && !empty ($media_dir) && !empty ($container_id) && is_file ($media_dir.$site."/".$container_id."/faces.json"))
    {
      // load JSON file with face definitions
      $faces_json = loadfile ($media_dir.$site."/".$container_id."/", "faces.json");

      if (!empty ($faces_json))
      {
        // decode JSON string
        $faces = json_decode ($faces_json, true);

        // collect face images
        if (is_array ($faces))
        {
          $i = 0;

          foreach ($faces as $face)
          {
            if (!empty ($face['time']) && is_file ($media_dir.$site."/".$container_id."/face-".$face['time'].".jpg"))
            {
              $time = $face['time'];
              $name = $face['name'];
              if (!empty ($face['link'])) $name = "<a href=\"".$face['link']."\">".$name."</a>";

              // focal point
              if (intval ($face['x']) > 0 && intval ($face['y']) > 0)
              {
                // width and height position of the face [%]
                $background_x = ($face['x'] + $face['width'] / 2) / $face['videowidth'] * 100;
                $background_y = ($face['y'] + $face['height'] / 2) / $face['videoheight'] * 100;

                // the background-image is z times as wide as its bounding box
                $z = 240 / 120;

                // focus point [%] = (c − 50%) × z/(z − 1) + 50%
                // c ... expressed as a percentage of the width of the image
                // z ... the background-image is z times as wide as its bounding box
                $background_x = ($background_x - 50) * $z / ($z - 1) + 50;
                $background_y = ($background_y - 50) * $z / ($z - 1) + 50;

                if ($background_x < 0) $background_x = 0;
                if ($background_y < 0) $background_y = 0;

                $background_pos = round ($background_x)."% ".round ($background_y)."%";
              }
              else $background_pos = "50% 50%";

              if (!empty ($cleandomain)) $source_url = cleandomain ($mgmt_config['url_path_cms']);
              else $source_url = $mgmt_config['url_path_cms'];

              $thumb_items[round($time).".".$i] = "<div onclick=\"hcms_jumpToVideoTime(".$time.");\" class=\"hcmsVideoThumbFrame\" style=\"background-position:".$background_pos."; background-image:url('".$source_url."?wm=".hcms_encrypt ($site."/".$container_id."/face-".$time.".jpg")."');\"><div class=\"hcmsVideoThumbnail\">".$name."</div></div>";
              $i++;
            }
          }

          ksort ($thumb_items, SORT_NUMERIC);
        }
      }

      $overlay = "
      <script type=\"text/javascript\">
      function hcms_minOverlay ()
      {
        if (document.getElementById('hcms_overlay_".$id."'))
        {
          document.getElementById('hcms_overlay_".$id."').style.transition = '0.3s';
          document.getElementById('hcms_overlay_".$id."').style.height = '22px';
          document.getElementById('hcms_gallery_".$id."').style.display = 'none';
          document.getElementById('hcms_collapse_".$id."').style.display = 'none';
          document.getElementById('hcms_expand_".$id."').style.display = 'inline';
        }
      }
      
      function hcms_maxOverlay ()
      {
        if (document.getElementById('hcms_overlay_".$id."'))
        {
          document.getElementById('hcms_overlay_".$id."').style.transition = '0.3s';
          document.getElementById('hcms_overlay_".$id."').style.height = '".$overlay_height."px';
          document.getElementById('hcms_gallery_".$id."').style.display = 'inline';
          document.getElementById('hcms_collapse_".$id."').style.display = 'inline';
          document.getElementById('hcms_expand_".$id."').style.display = 'none';
        }
      }

      function hcms_jumpToVideoTime (time)
      {
        // find video tag ID (add suffix _html5_api for HTML5 or _flash_api for Flash)
        if (document.getElementById('".$id."_html5_api')) var videoobject = document.getElementById('".$id."_html5_api');
        else if (document.getElementById('".$id."_flash_api')) var videoobject = document.getElementById('".$id."_flash_api');
        else var videoobject = false;

        if (videoobject)
        {
          // play video
          videoobject.play();

          // set video time
          setTimeout(function() { videoobject.currentTime = time; }, 300);
        }
        else alert ('video object missing');

        // play video using the existing video object created by video.js API (not working)
        // video.play();
        // video.on('timeupdate', function() { video.currentTime(time); });
      }
      </script>
      <style>
      .hcmsVideoOverlay
      {
        position: absolute; 
        top: 0;
        left: 0;
        text-align: center;
        background-color: rgba(7, 20, 30, 0.5);
        width: ".intval ($width)."px;
        height: 22px;
        padding: 0;
        z-index: 10;
      }

      .hcmsVideoThumbFrame
      {
        display: inline-block;
        cursor: pointer;
        width: 212px;
        height: 119px;
        margin: 1px 0.5px 1px 0.5px;
      }

      .hcmsVideoThumbnail
      {
        box-sizing: border-box;
        color: transparent;
        font-family: Arial, sans-serif;
        font-size: 14px;
        font-weight: 300;
        text-align: left;
        white-space: normal;
        background-color: transparent;
        width: 212px;
        height: 119px;
        padding: 5px;
        overflow: hidden;
      }

      .hcmsVideoThumbnail:hover
      {
        color: #FFF;
        background-color: rgba(7, 20, 30, 0.8);
      }

      .hcmsOverlayIcon
      {
        width: 22px;
        height: 22px;
        cursor: pointer;
      }
      </style>
      <div id=\"hcms_overlay_".$id."\" class=\"hcmsVideoOverlay\">
        <div id=\"hcms_gallery_".$id."\" style=\"display:none; position:absolute; left:0; top:0; width:".intval ($width)."px; height:".($overlay_height - 22)."px; overflow-x:auto; overflow-y:hidden; white-space:nowrap;\">
          ".implode ("", $thumb_items)."
        </div>
        <div style=\"position:absolute; left:0; bottom:0; display:block; width:".intval ($width)."px; height:22px; text-align:center;\">
          <img id=\"hcms_collapse_".$id."\" onclick=\"hcms_minOverlay();\" class=\"hcmsOverlayIcon\" style=\"display:none;\" src=\"".getthemelocation ("night")."img/button_arrow_up.png\" />
          <img id=\"hcms_expand_".$id."\" onclick=\"hcms_maxOverlay();\" class=\"hcmsOverlayIcon\" src=\"".getthemelocation ("night")."img/button_arrow_down.png\" /> 
        </div>
      </div>
      ";
    }


    // if no logo is defined set default logo
    if (empty ($logo_url)) $logo_url = getthemelocation()."img/logo_player.jpg";

    // VIDEO.JS Player (Standard)
    // get browser info
    $user_client = getbrowserinfo ();

    if (isset ($user_client['msie']) && $user_client['msie'] > 0) $fallback = ", \"playerFallbackOrder\":[\"flash\", \"html5\", \"links\"]";
    else $fallback = "";

    $return = $overlay."  <video id=\"".$id."\" class=\"video-js vjs-default-skin\" ".(($controls) ? " controls" : "").(($loop) ? " loop" : "").(($muted) ? " muted" : "").(($autoplay) ? " autoplay" : "")." preload=\"auto\" width=\"".intval ($width)."\" height=\"".intval ($height)."\"".(($logo_url != "") ? " poster=\"".$logo_url."\"" : "")." data-setup='{\"loop\":".(($loop) ? "true" : "false").$fallback."}' title=\"".$title."\"".($fullscreen ? " allowFullScreen=\"true\" webkitallowfullscreen=\"true\" mozallowfullscreen=\"true\"" : "")." onplay=\"if (typeof hideFaceOnVideo === 'function') hideFaceOnVideo();\">\n";

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
          if (is_file ($mgmt_config['abs_path_view'].$vtt_filename."_".trim ($code).".vtt"))
          {
            if (!empty ($cleandomain)) $source_url = cleandomain ($mgmt_config['url_path_temp']);
            else $source_url = $mgmt_config['url_path_temp'];

            $return .= "    <track kind=\"captions\" src=\"".$source_url."view/".$vtt_filename."_".trim ($code).".vtt\" srclang=\"".trim ($code)."\" label=\"".trim ($language)."\" />\n";
          }
        }
      }
    }

    $return .= "  </video>\n".$thumb_bar;

    return $return;
  }
  else return false;
}

// ------------------------- showvideoplayer_head -----------------------------
// function: showvideoplayer_head()
// input: secure hyperreferences by adding 'hypercms_' [boolean] (optional), is it possible to view the video in fullScreen [boolean] (optional), remove domain name from URLs [bollean] (optional)
// output: head for video player / false on error

function showvideoplayer_head ($secureHref=true, $fullscreen=true, $cleandomain=false)
{
  global $mgmt_config;

  // VIDEO.JS Player (Standard)
  if (is_dir ($mgmt_config['abs_path_cms']."javascript/video-js/") && !empty ($mgmt_config['url_path_cms']))
  {
    // remove domain name
    if (!empty ($cleandomain)) $source_url = cleandomain ($mgmt_config['url_path_cms']);
    else $source_url = $mgmt_config['url_path_cms'];

    $return = "  <link ".(($secureHref) ? "hypercms_" : "")."href=\"".$source_url."javascript/video-js/video-js.css\" rel=\"stylesheet\" />
  <link ".(($secureHref) ? "hypercms_" : "")."href=\"".$source_url."javascript/video-js/videojs.thumbnails.css\" rel=\"stylesheet\">
  <script src=\"".$source_url."javascript/video-js/video.min.js\"></script>
  <script type=\"text/javascript\">
    videojs.options.flash.swf = \"".$source_url."javascript/video-js/video-js.swf\";
  </script>
  <script src=\"".$source_url."javascript/video-js/videojs.thumbnails.js\"></script>\n";
    if ($fullscreen == false) $return .= "  <style> .vjs-fullscreen-control { display:none; } .vjs-default-skin .vjs-volume-control { margin-right:20px; } </style>";
  }

  return $return;
}

// ------------------------- showaudioplayer -----------------------------
// function: showaudioplayer()
// input: publication name [string], audio files [array], ID of the tag [string] (optional), width of the video in pixel [integer], height of the video in pixel [integer],
//        link to the logo which is displayed before you click on play (If the value is null the default logo will be used) [string], ID of the video (will be generated when empty) [string],
//        autoplay (optional) [boolean], play loop (optional) [boolean], player controls (optional) [boolean], remove domain name from source and poster URLs [bollean] (optional)
// output: code of the HTML5 player / false

// description:
// Generates the html segment for the video player code

function showaudioplayer ($site, $audioArray, $width=320, $height=320, $logo_url="", $id="", $autoplay=false, $loop=false, $controls=true, $force_reload=false, $cleandomain=false)
{
  global $mgmt_config;

  if (intval ($width) < 0) $width = 320;
  if (intval ($height) < 0) $height = 320;

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

        $url = $value;

        // remove domain name
        if (!empty ($cleandomain)) $url = cleandomain ($url);
      }


      // remove domain name
      if (!empty ($cleandomain)) $url = cleandomain ($url);

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

        // remove domain name
        if (!empty ($cleandomain)) $logo_url = cleandomain ($logo_url);
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
// input: secure hyperreferences by adding 'hypercms_' [boolean] (optional), remove domain name from source and poster URLs [bollean] (optional)
// output: head for audio player

function showaudioplayer_head ($secureHref=true, $cleandomain=false)
{
  global $mgmt_config;

  // VIDEO.JS Player (Standard)
  if (is_dir ($mgmt_config['abs_path_cms']."javascript/video-js/") && !empty ($mgmt_config['url_path_cms']))
  {
    // remove domain name
    if (!empty ($cleandomain)) $source_url = cleandomain ($mgmt_config['url_path_cms']);
    else $source_url = $mgmt_config['url_path_cms'];

    return "  <link ".(($secureHref) ? "hypercms_" : "")."href=\"".$source_url."javascript/video-js/video-js.css\" rel=\"stylesheet\" />
  <script src=\"".$source_url."javascript/video-js/video.min.js\"></script>
  <script type=\"text/javascript\">
    videojs.options.flash.swf = \"".$source_url."javascript/video-js/video-js.swf\";
  </script>
  <style> .vjs-fullscreen-control { display:none; } .vjs-default-skin .vjs-volume-control { margin-right:20px; } </style>";
  }
}

// ------------------------- debug_getbacktracestring -----------------------------
// function: debug_getbacktracestring()
// input: separator for arguments [string], separator for a row on screen/file [string], functionnames to be ignored [array]
// output: debug message

// description:
// Returns the current backtrace as a good readable string.
// Ignores debug and debug_getbacktracestring.

function debug_getbacktracestring ($valueSeparator, $rowSeparator, $ignoreFunctions=array())
{
  // initialize
  if (!is_array ($ignoreFunctions)) $ignoreFunctions = array();
  $ignoreFunctions[] = 'debug_getbacktracestring';
  $trace = debug_backtrace();
  $msg = array();

  if (is_array ($trace))
  {
    // running through the Stack
    foreach ($trace as $stack)
    {
      // no need to export the debug functions
      if (!is_array ($stack) || in_array ($stack['function'], $ignoreFunctions ))
      {
        continue;
      }

      $specialcount = 1;

      // building String for Function Variables
      $arguments = array();
      $add = array();

      if (!empty ($stack['args']) && is_array ($stack['args']))
      {
        foreach ($stack['args'] as $arg)
        {
          if (is_array ($arg) || is_object ($arg))
          {
            $arguments[] = 'Arg#'.$specialcount;
            $add[] = 'Arg#'.($specialcount++).var_export ($arg, true);
          }
          else $arguments[] = var_export ($arg, true);
        }
      } 

      // when $stack['class'] exists we can use $stack['type'] else it was not a class function and we don't ouput a class
      if (!isset ($stack['class'])) $stack['class'] = "";
      else $stack['class'] .= $stack['type'];

      // building the arguments and the additional information
      $arguments = implode ($valueSeparator, $arguments);

      // only add information when there is something to output
      if (!empty ($add)) $add = $rowSeparator."Objects/Arrays:".$rowSeparator.implode ($rowSeparator, $add);
      else $add = "";

      $msg[] = 'In '.@$stack['file'].' at Line '.@$stack['line'].'. Function called: '.@$stack['class'].@$stack['function'].'('.$arguments.')'.$add;
    }
  }
  else
  {
    $msg[] = 'Trace was not an Array! ('.var_export ($trace, true).')';
  }

  // only return something when we have anything
  if (empty ($msg)) return "";
  else return implode ($rowSeparator, $msg).$rowSeparator;
}

// ------------------------- showAPIdocs -----------------------------
// function: showAPIdocs()
// input: path to API file [string], return result as HTML or array [html,array] (optional), use horizontal rule as separator in HTML output [boolean] (optional)
//        display description [boolean] (optional), display input parameters [boolean] (optional), display global variables [boolean] (optional), display output [boolean] (optional), display only defined function names [array] (optional)
// output: HTML output of documentation / false on error

// description:
// Generates the documentation of an API file.
// If you only want to display the main API functions that you would normally be interested in, please use this defintion:
// $display_functions = array ("is_folder", "is_emptyfolder", "is_supported", "is_date", "is_document", "is_image", "is_rawimage", "is_video", "is_rawvideo", "is_audio", "is_keyword", "is_mobilebrowser", "is_iOS", "createviewlink", "createportallink", "createaccesslink", "createobjectaccesslink", "createwrapperlink", "createdownloadlink", "createmultiaccesslink", "createmultidownloadlink", "restoremediafile", "downloadobject", "downloadfile", "createpublication", "editpublication", "editpublicationsetting", "deletepublication", "createtemplate", "edittemplate", "deletetemplate, "createportal", "editportal", "deleteportal", "createuser", "edituser", "deleteuser", "creategroup", "editgroup", "deletegroup", "createfolder", "renamefolder", "deletefolder",  "createobject", "uploadfile", "createmediaobject", "createmediaobjects", "editmediaobject", "editobject", "renameobject", "deleteobject", "cutobject", "copyobject", "copyconnectedobject", "pasteobject", "lockobject", "unlockobject", "publishobject", "unpublishobject", "createqueueentry", "remoteclient", "savelog", "loadlog", "deletelog", "debuglog", "sendlicensenotification", "sendresetpassword", "createfavorite", "deletefavorite", "load_csv", "create_csv", "sendmessage", "savecontent", "hmtl2pdf", "mergepdf");

function showAPIdocs ($file, $return="html", $html_hr=true, $html_description=true, $html_input=true, $html_globals=true, $html_output=true, $display_functions=array())
{
  if (is_file ($file))
  {
    $data = file ($file);

    if (is_array ($data))
    {
      // initialize
      $name = "";
      $open = "";
      $function = array();
      $input = array();
      $output = array();
      $requires = array();
      $global = array();

      $description = array();
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
          // next commented lines (must be inside the first 10 digits of the line since //: is used in input parameters)
          elseif (strpos ("_".$line, "//") > 0 && strpos ("_".$line, "//") < 10 && !empty ($name))
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
            $open = "none";
            
            // only display functions that have been defined in $display_functions array
            if (empty ($display_function) || sizeof ($display_functions) < 1 || in_array ($name, $display_functions))
            {
              $function[$name] = $temp;
            }
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
      if ($return == "html" && !empty ($function) && is_array ($function) && sizeof ($function) > 0)
      {
        $result = "";

        foreach ($function as $name => $value)
        {
          $result .= "<h3><a id=".$name."></a>".$name."</h3><br/>\n";

          if (!empty ($description[$name]) && !empty ($html_description))
          {
            $description[$name] = str_replace (",", ", ", $description[$name]);
            $result .= "<b>Description</b><br/>\n";
            $result .= nl2br (trim ($description[$name]))."<br/><br/>\n";
          }
          
          $result .= "<b>Syntax</b><br/>\n";
          $function[$name] = str_replace (",", ", ", $function[$name]);
          $result .= $function[$name]."<br/><br/>\n";

          $temp = trim (substr ($value, strpos ($value, "(") + 1), ")");
          $input_vars = explode (", ", trim ($temp));

          if (is_array ($input_vars) && sizeof ($input_vars) > 0 && !empty ($html_input))
          {
            $result .= "<b>Input parameters</b><br/>\n<ul>\n";

            if (!empty ($input[$name])) $var_text = explode (", ", $input[$name]);

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

                $result .= "<li>".trim ($var).$text."</li>\n";
              }
            }

            $result .= "</ul>\n<br/>\n";
          }

          if (!empty ($global[$name]) && !empty ($html_globals))
          {
            $result .= "<b>global input parameters</b><br/>\n";
            $result .= "<ul><li>".str_replace (", ", "</li>\n<li/>", $global[$name])."</li></ul>\n";
            $result .= "<br/>\n";
          }

          if (!empty ($html_output)) 
          {
            $result .= "<b>Output</b><br/>\n";
            $result .= "<ul><li>".str_replace (", ", "</li>\n<li/>", $output[$name])."</li></ul>\n";
            $result .= "<br/>\n";
          }

          if (!empty ($html_hr)) $result .= "<hr/>\n";
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
// input: publication name [string], location [string], object name [string], view name (see view parameters of function buildview) [string] (optional), user name [string] (optional)
// output: navigation item array / false

// description:
// Reads the content from the container and collects information about a single navigation item

function readnavigation ($site, $docroot, $object, $view="publish", $user="sys")
{
  global $mgmt_config, $navi_config;

  if (valid_publicationname ($site) && valid_locationname ($docroot) && valid_objectname ($object) && valid_objectname ($user))
  {
    $xmldata = getobjectcontainer ($site, $docroot, $object, $user);

    if (!empty ($xmldata))
    {
      // if show/hide navigation text_id has been defined
      if (!empty ($navi_config['hide_text_id']))
      {
        $hidenode = selectcontent ($xmldata, "<text>", "<text_id>", $navi_config['hide_text_id']);

        if (!empty ($hidenode[0]))
        {
          $hide = getcontent ($hidenode[0], "<textcontent>", true);

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

        if (!empty ($sortordernode[0]))
        {
          $sortorder = getcontent ($sortordernode[0], "<textcontent>", true);

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

        foreach ($navi_config['lang_text_id'] as $key => $value)
        {
          // get title
          $textnode = selectcontent ($xmldata, "<text>", "<text_id>", $value);

          if (!empty ($textnode[0]))
          {
            $title = getcontent ($textnode[0], "<textcontent>", true);

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

            if (!empty ($textnode[0]))
            {
              $permalink = getcontent ($textnode[0], "<textcontent>", true);

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
// input: publication name [string], document root for navigation [string], URL root for navigation [string], view name (see view parameters of function buildview) [string], path to current object [string] (optional), recursive [boolean] (optional)
// output: navigation array / false

// description:
// Generates an associative array (item => nav-item, sub => array with sub-items).
// 
// Example
// $navi_config = array();
// 
// document root definitions
// $navi_config['root_path'] = "%abs_page%/";
// $navi_config['root_url'] = "%url_page%/";
// 
// HTML / CSS class defintions
// $navi_config['attr_ul_top'] = "class=\"nav navbar-nav\"";
// $navi_config['attr_ul_dropdown'] = "class=\"dropdown-menu\"";
// $navi_config['attr_li_active'] = "class=\"active\"";
// $navi_config['attr_li_dropdown'] = "class=\"dropdown\"";
// $navi_config['attr_href_dropdown'] = "class=\"dropdown-toggle\" data-toggle=\"dropdown\"";
// $navi_config['tag_li'] = "<li %attr_li%><a href=\"%link%\" %attr_href%>%title%</a>%sub%</li>\n";
// $navi_config['tag_ul'] = "<ul %attr_ul%>%list%</ul>\n";
// 
// Language definitions
// Session variable name that holds the language setting
// $navi_config['lang_session'] = "langcode";
// 2nd key = langcode & value = text_id of textnode
// $navi_config['lang_text_id']['DE'] = "Titel_DE";
// $navi_config['lang_text_id']['EN'] = "Titel_EN";
// 
// PermaLink definitions
// 2nd key = langcode & value = text_id of textnode
// $navi_config['permalink_text_id']['DE'] = "PermaLink_DE";
// $navi_config['permalink_text_id']['EN'] = "PermaLink_EN";
// 
// Hide navigation item (any value or empty) and use sort order (number or empty)
// $navi_config['hide_text_id'] = "NavigationHide";
// $navi_config['sort_text_id'] = "NavigationSortOrder";
// 
// Use only index file of directory as navigation item, e.g. index.html or index.php (Keep empty if all objects of a folder should be included)
// $navi_config['index_file'] = "";
// 
// $navigation = createnavigation ("%publication%", $navi_config['root_path'], $navi_config['root_url'], "%view%", "%abs_location%/%object%");
// echo shownavigation ($navigation);

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
    $docroot = correctpath ($docroot);
    $currentobject = correctpath ($currentobject);

    // collect navigation data
    if (is_dir ($docroot))
    {
      $scandir = scandir ($docroot);

      if ($scandir)
      {
        $i = 0;
        $fileitem = array(); 
        $navitem = array();

        foreach ($scandir as $file)
        {
          if ($file != "." && $file != ".." && substr ($file, -4) != ".off" && substr ($file, -8) != ".recycle") $fileitem[] = $file;
        }

        if (sizeof ($fileitem) > 0)
        {
          natcasesort ($fileitem);
          reset ($fileitem);

          foreach ($fileitem as $object)
          {
            // PAGE OBJECT -> standard navigation item
            if (is_file ($docroot.$object) && $object != ".folder" && (empty ($navi_config['index_file']) || $object == $navi_config['index_file']))
            {
              $navi = readnavigation ($site, $docroot, $object, $view, "sys");

              if ($navi != false && $navi['hide'] == false)
              {
                // navigation display
                if (substr_count ($currentobject, $docroot.$object) == 1) $add_css = $navi_config['attr_li_active'];
                else $add_css = ""; 

                if (empty ($navitem[$navi['order'].'.'.$i])) $navitem[$navi['order'].'.'.$i] = array();
                $navitem[$navi['order'].'.'.$i]['item'] = $add_css."|".$navi['link']."|".str_replace("|", "&#124;", $navi['title']);
                $navitem[$navi['order'].'.'.$i]['sub'] = "";

                $i++;
              }
            }
            // FOLDER -> next navigation level
            elseif ($recursive && is_dir ($docroot.$object) && !is_emptyfolder ($docroot.$object))
            {
              $navi = readnavigation ($site, $docroot, $object, $view, "sys");

              if (is_array ($navi) && empty ($navi['hide']))
              {
                // use folder object data
                if (empty ($navi_config['use_1st_folderitem']))
                {
                  // "X" means undefined sort order
                  if ($navi['order'] == "X") $navi['order'] = $i;

                  // create main item for sub navigation
                  if (empty ($navitem[$navi['order'].'.'.$i])) $navitem[$navi['order'].'.'.$i] = array();
                  $navitem[$navi['order'].'.'.$i]['item'] = $navi_config['attr_li_dropdown']."|#|".$navi['title'];
                  $navitem[$navi['order'].'.'.$i]['sub'] = "";
                }

                // create sub navigation
                $subnav = createnavigation ($site, $docroot.$object."/", $urlroot.$object."/", $view, $currentobject);

                if (is_array ($subnav))
                {
                  ksort ($subnav, SORT_NUMERIC);
                  reset ($subnav);
                  $j = 1;

                  foreach ($subnav as $key => $value)
                  {
                    // use page object data
                    if (!empty ($navi_config['use_1st_folderitem']) && $j == 1)
                    {
                      // "X" means undefined sort order
                      if ($navi['order'] == "X") $navi['order'] = $key;

                      // create main item for sub navigation
                      if (empty ($navitem[$navi['order'].'.'.$i])) $navitem[$navi['order'].'.'.$i] = array();
                      $navitem[$navi['order'].'.'.$i]['item'] = $value['item'];
                      $navitem[$navi['order'].'.'.$i]['sub'] = "";
                    }
                    else
                    {
                      // sub navigation
                      if (empty ($navitem[$navi['order'].'.'.$i])) $navitem[$navi['order'].'.'.$i] = array();
                      if (empty ($navitem[$navi['order'].'.'.$i]['sub'])) $navitem[$navi['order'].'.'.$i]['sub'] = array();
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
      }
    }

    if (isset ($navitem) && is_array ($navitem) && sizeof ($navitem) > 0) return $navitem;
  }

  return false;
}

// ------------------------- shownavigation -----------------------------
// function: shownavigation()
// input: navigation (created by function readnavigation) [array], level [integer] (optional)
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
// Use the first object of a folder for the main navigation item and display all following objects as sub navigation items [boolean]
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
// input: values array (array-key = value, array-value = text) [array], use values of array as option value and text [boolean] (optional), selected value [string] (optional), attributes of select tags like name or id or events [string] (optional)
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
// input: publication name [string], editor/text-tag ID [string], unformatted or formatted texttag-type [u,f], character set [string] (optional), 2 digit language code [string] (optional), style of div tag [string] (optional)
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
    <select id=\"sourceLang_".$id."\" style=\"width:70px; padding-left:2px; padding-right:16px;\">
      <option value=\"\">Automatic</option>";

    $langcode_array = getlanguageoptions();

    if ($langcode_array != false)
    {
      reset ($langcode_array);

      foreach ($langcode_array as $code => $lang_short)
      {
        if (is_activelanguage ($site, $code)) $result .= "
      <option value=\"".$code."\">".$lang_short."</option>";
      }
    }

    $result .= "
    </select>
    &#10095;
    <select id=\"targetLang_".$id."\" style=\"width:70px; padding-left:2px; padding-right:16px;\">";

    if ($langcode_array != false)
    {
      reset ($langcode_array);

      foreach ($langcode_array as $code => $lang_short)
      {
        if (is_activelanguage ($site, $code)) $result .= "
      <option value=\"".$code."\">".$lang_short."</option>";
      }
    }

    $result .= "
    </select>
    <img name=\"Button_".$button_id."\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" style=\"margin-right:2px;\" src=\"".getthemelocation()."img/button_ok.png\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button_".$button_id."','','".getthemelocation()."img/button_ok_over.png',1)\" title=\"OK\" alt=\"OK\" onClick=\"".$JSfunction."('".$id."', 'sourceLang_".$id."', 'targetLang_".$id."');\" />
  </div>";

    return $result;
  }
  else return false;
}

// ------------------------- showmapping -----------------------------
// function: showmapping()
// input: publication name [string], 2 digit language code [string] (optional)
// output: table with form fields for display / false

// description:
// Present the mapping form of the provided publication.

function showmapping ($site, $lang="en")
{
  global $mgmt_config, $hcms_charset, $hcms_lang;

  // load mapping
  if (valid_publicationname ($site))
  {
    $mapping_data = getmapping ($site);

    if (!empty ($mapping_data)) $mapping_array = explode (PHP_EOL, $mapping_data);
  }

  $i = 0;
  $marker = "empty";

  if (!empty ($mapping_array) && is_array ($mapping_array) && sizeof ($mapping_array) > 0)
  {
    $content = "
  <input name=\"mapping_data\" type=\"hidden\" value=\"".str_replace (array("<", ">"), array("&lt;", "&gt;"), addslashes ($mapping_data))."\" />
  <table class=\"hcmsTableStandard\" style=\"width:640px;\">";

    foreach ($mapping_array as $mapping_data)
    {
      // row colors
      if ($i % 2 == 0) $rowcolor = "hcmsRowData2";
      else $rowcolor = "hcmsRowData1";

      // title
      if (strpos ("_".$mapping_data, "//") == 1)
      {
        $content .= "
      <tr>
        <td colspan=\"2\" ".($marker != "comment" ? "class=\"hcmsHeadline\"": "").">".trim (str_replace (array("//", "<", ">"), array("", "&lt;", "&gt;"), $mapping_data))."</td>
      </tr>";

        // marker
        $marker = "comment";
      }
      // mapping data
      elseif (strpos ($mapping_data, "=>") > 0)
      {
        list ($metatag, $hypertag) = explode ("=>", $mapping_data);

        // clean text (remove double and single quotes)
        $metatag = trim (str_replace (array("'", '"', "<", ">"), array("", "", "&lt;", "&gt;"), $metatag));
        $hypertag = trim (str_replace (array("'", '"', "<", ">"), array("", "", "&lt;", "&gt;"), $hypertag));
        $texttype = "";

        if (strpos ($hypertag, ":") > 0) list ($texttype, $textid) = explode (":", $hypertag);
        else $textid = trim ($hypertag);

        $content .= "
      <tr class=\"".$rowcolor."\">
        <td style=\"white-space:nowrap;\">".trim (str_replace ("'", "", $metatag))." </td>
        <td style=\"white-space:nowrap; text-align:right;\">
        <select name=\"mapping_texttype[".$metatag."]\" class=\"".$rowcolor."\">
          <option value=\"textu\" ".(strtolower ($texttype) == "textu" ? "selected" : "").">".getescapedtext ($hcms_lang['text'][$lang], $hcms_charset, $lang)."</option>
          <option value=\"textk\" ".(strtolower ($texttype) == "textk" ? "selected" : "").">".getescapedtext ($hcms_lang['keywords'][$lang], $hcms_charset, $lang)."</option>
          <option value=\"textl\" ".(strtolower ($texttype) == "textl" ? "selected" : "").">".getescapedtext ($hcms_lang['text-options'][$lang], $hcms_charset, $lang)."</option>
          <option value=\"textd\" ".(strtolower ($texttype) == "textd" ? "selected" : "").">".getescapedtext ($hcms_lang['date'][$lang], $hcms_charset, $lang)."</option>
        </select>
        <input name=\"mapping_textid[".$metatag."]\" type=\"text\" class=\"".$rowcolor."\" value=\"".getescapedtext ($textid, $hcms_charset, $lang)."\" />
        </td>
      </tr>";

        // marker
        $marker = "mapping";
      }
      // new segment
      elseif (trim ($mapping_data) == "")
      {
        $content .= "
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>";

        // marker
        $marker = "empty";
      }
      // end
      elseif (strpos ("_".$mapping_data, "/* hcms_mapping */") > 0)
      {
        break;
      }

      $i++;
    }

    $content .= "
  </table>";
  }

  if (!empty ($content)) return $content;
  else return false;
}

// ------------------------- showgallery -----------------------------
// function: showgallery()
// input: multiobjects represented by their path or object ID [array], thumbnail size in pixels [integer] (optional), open object on click [boolean] (optional), user name [string] (optional)
// output: gallery view / false

// description:
// Presents all objects in a gallery with their thumbnails.

function showgallery ($multiobject, $thumbsize=100, $openlink=false, $user="sys")
{
  global $mgmt_config, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $globalpermission, $setlocalpermission, $hcms_lang, $lang;

  if (is_array ($multiobject) && $thumbsize > 0 && valid_objectname ($user))
  {
    $count = 0;
    $galleryview = "
    <script src=\"".cleandomain ($mgmt_config['url_path_cms'])."javascript/lazysizes/lazysizes.min.js\" type=\"text/javascript\" async=\"\"></script>";

    // create secure token
    $token = createtoken ($user);

    // run through each object
    foreach ($multiobject as $object) 
    {
      $object = trim ($object);

      // convert ID to path
      if (is_numeric ($object)) $object = rdbms_getobject ($object);

      // ignore empty entries 
      if (empty ($object)) continue;

      // initialize
      $openobject = "";

      $site = getpublication ($object);
      $location_esc = getlocation ($object);
      $location = deconvertpath ($location_esc, "file");
      $cat = getcategory ($site, $object);
      $page = getobject ($object);
      $location_name = getlocationname ($site, $location_esc, $cat);
      $objectinfo = getobjectinfo ($site, $location, $page);

      // check access permissions
      $ownergroup = accesspermission ($site, $location, $cat);
      $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

      // do not display objects without the users general access permission
      if ($setlocalpermission['root'] != 1) continue;
      
      $count++;

      // open object
      if (!empty ($openlink) && $setlocalpermission['root'] == 1)
      {
        $functioncall = "hcms_openWindow('frameset_content.php?ctrlreload=yes&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&token=".$token."', '".$objectinfo['container_id']."', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").")";

        // open on click (parent must be used if function is called from iframe!)
        $openobject = "onclick=\"if (window.parent) parent.".$functioncall."; else ".$functioncall.";\"";
      }

      $fileinfo = getfileinfo ($site, $location_esc.$page, $cat);

      // media asset
      if (!empty ($objectinfo['media']))
      {
        $mediainfo = getfileinfo ($site, $objectinfo['media'], "comp");
        $thumbnail = $mediainfo['filename'].".thumb.jpg";

        // thumbnail preview
        $galleryview .= "
        <div id=\"image".$count."\" style=\"margin:5px; width:".$thumbsize."px; height:".$thumbsize."px; float:left; cursor:pointer; display:block; text-align:center; vertical-align:bottom;\" ".$openobject." title=\"".$location_name.$objectinfo['name']."\"><img data-src=\"".cleandomain (createviewlink ($site, $thumbnail, $objectinfo['name'], false, "wrapper", $fileinfo['icon']))."\" class=\"lazyload hcmsImageItem\" style=\"border:0; max-width:".$thumbsize."px; max-height:".$thumbsize."px;\" /></div>";
      }
      // object or folder
      else
      {
        $galleryview .= "
        <div id=\"image".$count."\" style=\"margin:5px; width:".$thumbsize."px; height:".$thumbsize."px; float:left; cursor:pointer; display:block; text-align:center; vertical-align:bottom;\" ".$openobject." title=\"".$location_name.$objectinfo['name']."\"><img src=\"".cleandomain (getthemelocation()."img/".$fileinfo['icon'])."\" style=\"border:0; width:".$thumbsize."px; height:".$thumbsize."px;\" /></div>";
      }
    }

    return $galleryview;
  }
  else return false;
}

// ------------------------- showthumbnail -----------------------------
// function: showthumbnail()
// input: publication name [string], media file name [string], display name [string] (optional), thumbnail size in pixels [integer] (optional), base64 encoding [boolean] (optional), CSS style for image [string] (optional)
//        design theme name for icons [string] (optional)
// output: thumbnail view / false

// description:
// Presents the thumbnail of a single media file that is optionally base64 encoded an can be embedded in HTML pages or e-mails.

function showthumbnail ($site, $mediafile, $name="", $thumbsize=120, $base64=false, $style="", $theme="standard")
{
  global $mgmt_config;

  if (valid_publicationname ($site) && $mediafile != "" && $thumbsize > 0)
  {
    // thumbnail file is always in repository
    $mediadir = getmedialocation ($site, ".hcms.".$mediafile, "abs_path_media").$site."/";

    // get thumbnail file name
    $mediainfo = getfileinfo ($site, $mediafile, "comp");
    $thumbnail = $mediainfo['filename'].".thumb.jpg";

    // thumbnails preview
    if (is_file ($mediadir.$thumbnail))
    {
      $imgsize = getmediasize ($mediadir.$thumbnail);

      // calculate image ratio to define CSS for image container div-tag
      if (!empty ($thumb_size['width']) && !empty ($thumb_size['height']))
      {
        $imgwidth = $imgsize['width'];
        $imgheight = $imgsize['height'];
        $imgratio = $imgwidth / $imgheight;

        // if thumbnail is smaller than defined thumbnail size
        if ($imgwidth < $thumbsize && $imgheight < $thumbsize)
        {
          $style_size = "width:".$imgwidth."px; height:".$imgheight."px;";
        }
        else
        {
          // image width >= height
          if ($imgratio >= 1) $style_size = "width:".$thumbsize."px; height:".round(($thumbsize / $imgratio), 0)."px;";
          // image width < height
          else $style_size = "width:".round(($thumbsize * $imgratio), 0)."px; height:".$thumbsize."px;";
        }
      }
      // default value
      else
      {
        $style_size = "max-width:".$thumbsize."px; max-height:".$thumbsize."px;";
      }

      // base64 encode
      if ($base64 == true)
      {
        $image = "data:image/jpeg;base64,".base64_encode (file_get_contents ($mediadir.$thumbnail));
      }
      // view link
      else
      {
        $image = createviewlink ($site, $thumbnail, $name);
      }

      $view = "<img src=\"".$image."\" style=\"".$style_size." ".$style."\" alt=\"".$name."\" title=\"".$name."\" />";
    }
    // no thumbnail available
    else
    {
      // get theme location for portal themes or system themes
      if (valid_locationname ($theme) && strpos ($theme, "/") > 0 && is_dir ($mgmt_config['abs_path_rep']."portal/".$theme."/css"))
      {
        $mediadir = $mgmt_config['abs_path_rep']."portal/".$theme."/img/";
        $mediaurl = $mgmt_config['url_path_rep']."portal/".$theme."/img/";
      }
      else
      {
        $mediadir = $mgmt_config['abs_path_cms']."theme/".$theme."/img/";
        $mediaurl = $mgmt_config['url_path_cms']."theme/".$theme."/img/";
      }

      // base64 encode
      if ($base64 == true)
      {
        $image = "data:image/png;base64,".base64_encode (file_get_contents ($mediadir.$mediainfo['icon']));
      }
      // view link
      else
      {
        $image = $mediaurl.$mediainfo['icon'];
      }

      $view = "<img src=\"".$image."\" style=\"max-width:".$thumbsize."px; max-height:".$thumbsize."px; ".$style."\" alt=\"".$name."\" title=\"".$name."\" />";
    }

    return $view;
  }
  else return false;
}

// ------------------------- showtaxonomytree -----------------------------
// function: showtaxonomytree()
// input: publication name [string] (optional), container ID [integer][array], text ID [string], language code [string] (optional), 
//        taxonomy ID or expression or taxonomy path in the form %taxonomy%/publication-name or %taxonomy%/default/language-code/taxonomy-ID/taxonomy-child-levels [string], width in pixel [integer] (optional), height in pixel [integer] (optional),
//        character set [string] (optional)
// output: taxonomy tree view / false

// description:
// Displays the requested taxonomy tree structure or sub branch with checkboxes for the keywords.

function showtaxonomytree ($site="", $container_id=array(), $text_id="", $tagname="textk", $taxonomy_lang="en", $expression="", $width=600, $height=500, $charset="UTF-8")
{
  global $mgmt_config, $hcms_lang, $lang, $taxonomy;

  if (valid_publicationname ($site) && (is_numeric ($container_id) || is_array ($container_id)) && $lang != "" && is_array ($mgmt_config) && !empty ($mgmt_config[$site]['taxonomy']))
  {
    $view = "";
    $tax_id_selected_array = array();
    $childlevels = 10;

    // extract taxonomy language from taxonomy path
    if (strpos ("_".$expression, "%taxonomy%/") > 0)
    {
      $slice = explode ("/", $expression);

      if (!empty ($slice[0])) $domain = $slice[0];
      if (!empty ($slice[1])) $site = $slice[1];
      if (!empty ($slice[2])) $taxonomy_lang = $slice[2];
      if (isset ($slice[3])) $tax_id = $slice[3];
      if (isset ($slice[4])) $childlevels = $slice[4];

      if (empty ($taxonomy_lang) || strtolower ($taxonomy_lang) == "all") $taxonomy_lang = "";
    }

    // get the taxonomy tree with the full path as key
    $taxonomy_array = gettaxonomy_childs ($site, $lang, $expression, $childlevels, true, true);
 
    if (is_array ($taxonomy_array))
    {
      // get taxonomy data for the container
      if (is_array ($container_id))
      {
        $container_id = array_unique ($container_id);

        foreach ($container_id as $temp)
        {
          $taxdata = rdbms_gettaxonomy ($temp, $text_id);

          // collect taxonomy IDs in array
          if (is_array ($taxdata))
          {
            foreach ($taxdata as $temp2) $tax_id_selected_array[] = $temp2['taxonomy_id'];
          }

          if (is_array ($tax_id_selected_array)) $tax_id_selected_array = array_unique ($tax_id_selected_array);
        }  
      }
      else
      {
        $taxdata = rdbms_gettaxonomy ($container_id, $text_id);

        // collect taxonomy IDs in array
        if (is_array ($taxdata))
        {
          foreach ($taxdata as $temp) $tax_id_selected_array[] = $temp['taxonomy_id'];
        }
      }

      // check/uncheck all
      $toogleid = uniqid();

      $view .= "
    <script type=\"text/javascript\">
      var searchResults".$toogleid." = [];
      var searchExpression".$toogleid." = '';
      searchCurrent".$toogleid." = 0;

      // mark or unmark all taxonomy checkboxes
      function toggle".$toogleid." (source)
      {
        var checkboxes = document.querySelectorAll(\"input[name^='".getescapedtext ($tagname)."[".getescapedtext ($text_id)."][']\");

        for (var i=0; i<checkboxes.length; i++)
        {
          if (checkboxes[i].style.visibility != 'hidden') checkboxes[i].checked = source.checked;
        }
      }

      // search taxonomy
      function search".$toogleid." (expression)
      {
        var checkboxes = document.querySelectorAll(\"input[name^='".getescapedtext ($tagname)."[".getescapedtext ($text_id)."][']\");
        var layers = document.getElementsByClassName('hcmsLayer".$toogleid."');

        // search
        if (expression != '' && searchExpression".$toogleid." != expression)
        {
          searchResults".$toogleid." = [];
          searchExpression".$toogleid." = expression;

          expression = expression.toLowerCase();
          
          // search
          for (var i=0; i<checkboxes.length; i++)
          {
            if (checkboxes[i].value.toLowerCase().indexOf (expression) > -1)
            {
              checkboxes[i].parentNode.className = 'hcmsPriorityHigh';
              checkboxes[i].style.visibility = 'visible';
              searchResults".$toogleid.".push (checkboxes[i]);
            }
            else
            {
              checkboxes[i].parentNode.className = '';
              checkboxes[i].style.visibility = 'hidden';
            }
          }

          // open layers
          if (searchResults".$toogleid.".length > 0)
          {
            for (var i=0; i<layers.length; i++)
            {
              layers[i].style.height = 'auto';
            }
          }

          // scroll to search result
          if (searchResults".$toogleid.".length > 0) searchResults".$toogleid."[0].scrollIntoView ({ block:'center', behavior:'smooth' });

          // fix for MS IE and Edge
          if (hcms_getBrowserName() == 'ie' || hcms_getBrowserName() == 'edge') window.scrollBy(0, -100);
        }
        // scroll to next search result
        else if (expression != '' && searchResults".$toogleid.".length > 1)
        {
          if ((searchCurrent".$toogleid." + 1) < searchResults".$toogleid.".length)
          {
            searchCurrent".$toogleid."++;
          }
          else searchCurrent".$toogleid." = 0;

          // scroll
          searchResults".$toogleid."[searchCurrent".$toogleid."].scrollIntoView ({ block:'center', behavior:'smooth' });

          // fix for MS IE and Edge
          if (hcms_getBrowserName() == 'ie' || hcms_getBrowserName() == 'edge') window.scrollBy(0, -100);
        }
        // display all
        else
        {
          searchResults".$toogleid." = [];

          // reset checkboxes
          for (var i=0; i<checkboxes.length; i++)
          {
            checkboxes[i].parentNode.className = '';
            checkboxes[i].style.visibility = 'visible';
          }

          // close layers
          for (var i=0; i<layers.length; i++)
          {
            layers[i].style.height = '18px';
          }
        }

        // do not submit form
        event.preventDefault(); 
        return false;
      }
    </script>
    <!-- Fix for font-size issue in Chrome for Android -->
    <div style=\"max-height:999999px;\">
      <div>
        <input id=\"searchexpression".$toogleid."\" type=\"text\" onkeydown=\"if (hcms_enterKeyPressed(event)) search".$toogleid."(document.getElementById('searchexpression".$toogleid."').value);\" placeholder=\"".getescapedtext ($hcms_lang['search'][$lang], $charset, $lang)."\" style=\"width:280px; padding-right:30px;\" maxlength=\"100\" />
        <img src=\"".getthemelocation()."img/button_search_dark.png\" style=\"cursor:pointer; width:22px; height:22px; margin-left:-30px;\" onClick=\"search".$toogleid."(document.getElementById('searchexpression".$toogleid."').value);\" title=\"".getescapedtext ($hcms_lang['search'][$lang])."\" alt=\"".getescapedtext ($hcms_lang['search'][$lang], $charset, $lang)."\" />
        <label style=\"margin-left:14px;\"><input type=\"checkbox\" style=\"font-size:13px !important;\" onclick=\"toggle".$toogleid."(this);\"> ".getescapedtext ($hcms_lang['select-all'][$lang], $charset, $lang)."</label>
      </div>
      <!-- needed if no checkbox is checked -->
      <input type=\"hidden\" name=\"".getescapedtext ($tagname)."[".getescapedtext ($text_id)."]\" value=\"\" />
      <!-- applied taxonomy language -->
      <input type=\"hidden\" name=\"".getescapedtext ($tagname)."[".getescapedtext ($text_id)."][language]\" value=\"".$taxonomy_lang."\" />

      <div style=\"width:".$width.(strpos ($width, "%") > 0 ? "" : "px")."; height:".($height - 40)."px; overflow:auto;\">\n";

      $pre_level = 1;

      foreach ($taxonomy_array as $path => $keyword)
      {
        $level = substr_count ($path, "/") - 1;

        // close layers
        if ($level < $pre_level)
        {
          $diff = $pre_level - $level;
          $view .= str_repeat ("  </div>\n", $diff);
        }

        // create new layer
        if ($level > $pre_level)
        {
          $layer_id = "hcmsLayer".uniqid();
          $view .= str_repeat ("  ", $level)."<div id=\"".$layer_id."\" class=\"hcmsLayer".$toogleid."\" style=\"position:relative; z-index:10; display:block; margin:-18px 0px 0px 10px; padding:2px; height:18px; box-sizing:border-box; overflow:hidden;\">\n";
          $view .= str_repeat ("  ", $level)."  <a style=\"margin:0; cursor:pointer; font-size:13px !important;\" onclick=\"hcms_slideDownLayer('".$layer_id."', '18');\">+</a><br/>\n";
        }

        // get taxonomy ID
        $path_temp = substr ($path, 0, -1);
        $tax_id = substr ($path_temp, strrpos ($path_temp, "/") + 1);

        // escape commas
        $keyword = str_replace (",", "¸", $keyword);

        // entry
        if (sizeof ($tax_id_selected_array) > 0 && in_array ($tax_id, $tax_id_selected_array)) $checked = "checked";
        else $checked = "";

        $view .= str_repeat ("  ", $level)."  <label style=\"position:relative; z-index:20; margin-left:28px; padding:2px; font-size:13px !important;\"><input type=\"checkbox\" name=\"".getescapedtext ($tagname)."[".getescapedtext ($text_id)."][".$path."]\" value=\"".getescapedtext ($keyword, $charset, $lang)."\" ".$checked."> ".getescapedtext ($keyword, $charset, $lang)."</label><br/>\n";

        $pre_level = $level;
      }

      $diff = $pre_level - 1;
      $view .= str_repeat ("  </div>\n", $diff);
      $view .= "
      </div>
    </div>\n";

      return $view;
    }
    else return false;
  }
  else return false;
}

// ------------------------- showworkflowstatus -----------------------------
// function: showworkflowstatus()
// input: publication name [string], location path [string], object name [string]
// output: workflow status view / false

// description:
// Displays the workflow status information table.

function showworkflowstatus ($site, $location, $page)
{
  global $mgmt_config, $publ_config, $hcms_charset, $hcms_lang, $lang, $user;

  if (is_file ($mgmt_config['abs_path_cms']."workflow/frameset_workflow.php") && valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page))
  {
    // convert location
    $cat = getcategory ($site, $location);
    $location = deconvertpath ($location, "file");
    $location_esc = convertpath ($site, $location, $cat);

    // check and correct file
    $page = correctfile ($location, $page, $user);

    // load page and read actual file info (to get associated template and content)
    $pagestore = loadfile ($location, $page);

    if ($pagestore != false)
    {
      // get template
      $template = getfilename ($pagestore, "template");

      // get container
      $contentfile = getfilename ($pagestore, "content");
    }

    if (!empty ($template))
    {
      // read associated template file
      $result = loadtemplate ($site, $template);
      
      $templatedata = $result['content'];

      // get workflow from template
      $hypertag_array = gethypertag ($templatedata, "workflow", 0);

      // check if workflow is definded in template or workflow on folder must be applied
      if (empty ($hypertag_array))
      {
        if (file_exists ($mgmt_config['abs_path_data']."workflow_master/".$site.".".$cat.".folder.dat")) $wf_exists = true;
        else $wf_exists = false;
      }
      // worklfow defined in template 
      else $wf_exists = true;

      // collect workflow status information
      if ($wf_exists == true && is_file ($mgmt_config['abs_path_data']."workflow/".$site."/".$contentfile))
      {
        // load workflow
        $workflow_data = loadfile ($mgmt_config['abs_path_data']."workflow/".$site."/", $contentfile);

        // get workflow name
        $wf_name = getcontent ($workflow_data, "<name>");
        
        // build workflow stages
        $item_array = buildworkflow ($workflow_data);

        // count stages (1st dimension)
        $stage_max = sizeof ($item_array); 

        // set start stage (stage 0 can only exist if passive items exist)
        if (isset ($item_array[0]) && is_array ($item_array[0]) && sizeof ($item_array[0]) > 0) 
        {
          $stage_start = 1;
          $stage_max = $stage_max - 1;
        }
        else 
        {
          $stage_start = 1;
        }       

        echo "
    <div style=\"width:95%; padding:10px 4px;\"><span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['workflow-status'][$lang])."</span><br/>".(!empty ($wf_name[0]) ? getescapedtext ($hcms_lang['name'][$lang]).": ".$wf_name[0] : "")."</div>
    <table class=\"hcmsTableStandard\" style=\"width:95%;\">
      <tr>
        <td class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['member-type'][$lang])."</td>
        <td class=\"hcmsHeadline\" style=\"width:25%;\">".getescapedtext ($hcms_lang['task'][$lang])."</td>
        <td class=\"hcmsHeadline\" style=\"width:15%;\">".getescapedtext ($hcms_lang['member'][$lang])."</td>
        <td class=\"hcmsHeadline\" style=\"width:15%;\">".getescapedtext ($hcms_lang['status'][$lang])."</td>
        <td class=\"hcmsHeadline\" style=\"width:15%;\">".getescapedtext ($hcms_lang['date'][$lang])."</td>
      </tr>"; 

        for ($stage=$stage_start; $stage<=$stage_max; $stage++)
        {
          if (is_array ($item_array[$stage]))
          {
            echo "
      <tr class=\"hcmsRowHead2\">
        <td colspan=\"5\" class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['members-on-workflow-stage'][$lang])." ".$stage."</td>
      </tr>"; 
            
            foreach ($item_array[$stage] as $item)
            {
              $task_array = getcontent ($item, "<task>");
              if (!empty ($task_array[0])) $task = $task_array[0];
              else $task = "";

              $member_array = array();
              $type_array = getcontent ($item, "<type>");       

              if ($type_array[0] == "user")
              {
                $type = getescapedtext ($hcms_lang['user'][$lang]);
                $member_array = getcontent ($item, "<user>");
              }
              elseif ($type_array[0] == "usergroup")
              {
                $type = getescapedtext ($hcms_lang['user-group'][$lang]);
                $member_array = getcontent ($item, "<group>");
              }
              elseif ($type_array[0] == "script") 
              {
                $type = getescapedtext ($hcms_lang['robot-script'][$lang]);
                $member_array[0] = "-";
              }

              if (empty ($member_array[0])) $member_array[0] = "-";

              $passed_array = getcontent ($item, "<passed>");

              if (!empty ($passed_array[0]))
              {
                $passed = getescapedtext ($hcms_lang['accepted'][$lang]);
                $class = "hcmsFinished";
              }
              else
              {
                $passed = getescapedtext ($hcms_lang['pendingrejected'][$lang]);
                $class = "hcmsToDo";
              }

              $date_array = getcontent ($item, "<date>");

              echo "
        <tr class=\"".$class."\">
          <td>".$type."</td>
          <td>".$task."</td>
          <td>".$member_array[0]."</td>
          <td>".$passed."</td>
          <td>".$date_array[0]."</td>
        </tr>"; 
            }
          }
        }

        echo "
      </table>";
      }
    }
    else return false;
  }
  else return false;
}
?>