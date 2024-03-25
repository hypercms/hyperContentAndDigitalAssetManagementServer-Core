<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */
 
 // ===================================== PERMISSIONS =========================================

// ---------------------- resolvepermission -----------------------------
// function: resolvepermission()
// input: permission array [array], permission segment name [string], permission value position [integer]
// output: 1 / 0

// description:
// Returns the permission value (true or false) of a permission position of a permission segment 

function resolvepermission ($permission_array, $segment, $position)
{
 if (is_array ($permission_array) && sizeof ($permission_array) > 0 && is_string ($segment) && $segment != "" && intval ($position) >= 0)
 {
  if (!empty ($permission_array[$segment]))
  {
    $value = substr ($permission_array[$segment], $position, 1);

    if (!empty ($value)) return 1;
  }
 }

 return 0;
}

// ---------------------- rootpermission -----------------------------
// function: rootpermission()
// input: publication name [string], publication admin [boolean], permission string from group [string]
// output: global permission array/false

// description:
// Deserializes the permission string and and returns the root permission array

function rootpermission ($site_name, $site_admin, $permission_str)
{
  global $rootpermission, $mgmt_config;

  // load config if site_admin is not set
  if (valid_publicationname ($site_name) && $site_admin != true)
  {
    require ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php");
  }

  if (is_array ($permission_str) && valid_publicationname ($site_name))
  {
    // initialize
    if (!isset ($rootpermission) || !is_array ($rootpermission)) $rootpermission = array();

    if (!isset ($rootpermission['desktop'])) $rootpermission['desktop'] = 0;
    if (!isset ($rootpermission['desktopsetting'])) $rootpermission['desktopsetting'] = 0;
    if (!isset ($rootpermission['desktopprojectmgmt'])) $rootpermission['desktopprojectmgmt'] = 0; 
    if (!isset ($rootpermission['desktoptaskmgmt'])) $rootpermission['desktoptaskmgmt'] = 0;
    if (!isset ($rootpermission['desktopcheckedout'])) $rootpermission['desktopcheckedout'] = 0; 
    if (!isset ($rootpermission['desktoptimetravel'])) $rootpermission['desktoptimetravel'] = 0;
    if (!isset ($rootpermission['desktopfavorites'])) $rootpermission['desktopfavorites'] = 0; 

    if (!isset ($rootpermission['site'])) $rootpermission['site'] = 0;
    if (!isset ($rootpermission['sitecreate'])) $rootpermission['sitecreate'] = 0;
    if (!isset ($rootpermission['sitedelete'])) $rootpermission['sitedelete'] = 0;
    if (!isset ($rootpermission['siteedit'])) $rootpermission['siteedit'] = 0;

    if (!isset ($rootpermission['user'])) $rootpermission['user'] = 0;
    if (!isset ($rootpermission['usercreate'])) $rootpermission['usercreate'] = 0;
    if (!isset ($rootpermission['userdelete'])) $rootpermission['userdelete'] = 0;
    if (!isset ($rootpermission['useredit'])) $rootpermission['useredit'] = 0;

    reset ($permission_str);

    foreach ($permission_str[$site_name] as $group_name => $value)
    {
      if ($group_name != "" && $value != "")
      {
        // get permissions from string
        parse_str ($value, $permission_array);

        // desktop permissions
        if ($rootpermission['desktop'] == 0 && resolvepermission ($permission_array, "desktop", 0)) $rootpermission['desktop'] = 1;
        if ($rootpermission['desktopsetting'] == 0 && resolvepermission ($permission_array, "desktop", 1)) $rootpermission['desktopsetting'] = 1;
        if ($rootpermission['desktoptaskmgmt'] == 0 && resolvepermission ($permission_array, "desktop", 2)) $rootpermission['desktoptaskmgmt'] = 1;
        if ($rootpermission['desktopcheckedout'] == 0 && resolvepermission ($permission_array, "desktop", 3)) $rootpermission['desktopcheckedout'] = 1; 
        if ($rootpermission['desktoptimetravel'] == 0 && resolvepermission ($permission_array, "desktop", 4)) $rootpermission['desktoptimetravel'] = 1;
        if ($rootpermission['desktopfavorites'] == 0 && resolvepermission ($permission_array, "desktop", 5)) $rootpermission['desktopfavorites'] = 1;
        if ($rootpermission['desktopprojectmgmt'] == 0 && resolvepermission ($permission_array, "desktop", 6)) $rootpermission['desktopprojectmgmt'] = 1; // new in version 6.0.1

        if ($site_admin == true)
        {
          // site permissions
          if ($rootpermission['site'] == 0 && resolvepermission ($permission_array, "site", 0)) $rootpermission['site'] = 1;
          if ($rootpermission['sitecreate'] == 0 && resolvepermission ($permission_array, "site", 1)) $rootpermission['sitecreate'] = 1;
          if ($rootpermission['sitedelete'] == 0 && resolvepermission ($permission_array, "site", 2)) $rootpermission['sitedelete'] = 1;
          if ($rootpermission['siteedit'] == 0 && resolvepermission ($permission_array, "site", 3)) $rootpermission['siteedit'] = 1; 
          // user permissions
          if ($rootpermission['user'] == 0 && resolvepermission ($permission_array, "user", 0)) $rootpermission['user'] = 1;
          if ($rootpermission['usercreate'] == 0 && resolvepermission ($permission_array, "user", 1)) $rootpermission['usercreate'] = 1;
          if ($rootpermission['userdelete'] == 0 && resolvepermission ($permission_array, "user", 2)) $rootpermission['userdelete'] = 1;
          if ($rootpermission['useredit'] == 0 && resolvepermission ($permission_array, "user", 3)) $rootpermission['useredit'] = 1; 
        }
      }
    }

    if (is_array ($rootpermission)) 
    {
      return $rootpermission;
    }
  }

  return false;
}

// ---------------------- globalpermission -----------------------------
// function: globalpermission()
// input: publication name [string], permission string from group [string]
// output: global permission array/false

// description:
// Deserializes the permission string and returns the global permission array

function globalpermission ($site_name, $permission_str)
{
  if (is_array ($permission_str) && valid_publicationname ($site_name))
  {
    // initialize
    $globalpermission = array();
    $globalpermission[$site_name] = array();

    $globalpermission[$site_name]['user'] = 0;
    $globalpermission[$site_name]['usercreate'] = 0;
    $globalpermission[$site_name]['userdelete'] = 0;
    $globalpermission[$site_name]['useredit'] = 0;

    $globalpermission[$site_name]['group'] = 0;
    $globalpermission[$site_name]['groupcreate'] = 0;
    $globalpermission[$site_name]['groupdelete'] = 0;
    $globalpermission[$site_name]['groupedit'] = 0;

    $globalpermission[$site_name]['pers'] = 0;
    $globalpermission[$site_name]['perstrack'] = 0;
    $globalpermission[$site_name]['perstrackcreate'] = 0;
    $globalpermission[$site_name]['perstrackdelete'] = 0;
    $globalpermission[$site_name]['perstrackedit'] = 0;
    $globalpermission[$site_name]['persprof'] = 0;
    $globalpermission[$site_name]['persprofcreate'] = 0;
    $globalpermission[$site_name]['persprofdelete'] = 0;
    $globalpermission[$site_name]['persprofedit'] = 0;

    $globalpermission[$site_name]['workflow'] = 0;
    $globalpermission[$site_name]['workflowproc'] = 0;
    $globalpermission[$site_name]['workflowproccreate'] = 0;
    $globalpermission[$site_name]['workflowprocdelete'] = 0;
    $globalpermission[$site_name]['workflowprocedit'] = 0;
    $globalpermission[$site_name]['workflowprocfolder'] = 0;
    $globalpermission[$site_name]['workflowscript'] = 0;
    $globalpermission[$site_name]['workflowscriptcreate'] = 0;
    $globalpermission[$site_name]['workflowscriptdelete'] = 0;
    $globalpermission[$site_name]['workflowscriptedit'] = 0;

    $globalpermission[$site_name]['template'] = 0;
    $globalpermission[$site_name]['tpl'] = 0;
    $globalpermission[$site_name]['tplcreate'] = 0;
    $globalpermission[$site_name]['tpldelete'] = 0;
    $globalpermission[$site_name]['tpledit'] = 0; 
    $globalpermission[$site_name]['tplmedia'] = 0;
    $globalpermission[$site_name]['tplmediacatcreate'] = 0;
    $globalpermission[$site_name]['tplmediacatdelete'] = 0;
    $globalpermission[$site_name]['tplmediacatrename'] = 0;
    $globalpermission[$site_name]['tplmediaupload'] = 0;
    $globalpermission[$site_name]['tplmediadelete'] = 0;

    $globalpermission[$site_name]['component'] = 0;
    $globalpermission[$site_name]['page'] = 0;

    reset ($permission_str);
 
    foreach ($permission_str[$site_name] as $group_name => $value)
    {
      if ($group_name != "" && $value != "")
      {
        // get permissions from string
        parse_str ($value, $permission_array);

        // user permissions
        if ($globalpermission[$site_name]['user'] == 0 && resolvepermission ($permission_array, "user", 0)) $globalpermission[$site_name]['user'] = 1;
        if ($globalpermission[$site_name]['usercreate'] == 0 && resolvepermission ($permission_array, "user", 1)) $globalpermission[$site_name]['usercreate'] = 1;
        if ($globalpermission[$site_name]['userdelete'] == 0 && resolvepermission ($permission_array, "user", 2)) $globalpermission[$site_name]['userdelete'] = 1;
        if ($globalpermission[$site_name]['useredit'] == 0 && resolvepermission ($permission_array, "user", 3)) $globalpermission[$site_name]['useredit'] = 1;
        // group permissions
        if ($globalpermission[$site_name]['group'] == 0 && resolvepermission ($permission_array, "group", 0)) $globalpermission[$site_name]['group'] = 1;
        if ($globalpermission[$site_name]['groupcreate'] == 0 && resolvepermission ($permission_array, "group", 1)) $globalpermission[$site_name]['groupcreate'] = 1;
        if ($globalpermission[$site_name]['groupdelete'] == 0 && resolvepermission ($permission_array, "group", 2)) $globalpermission[$site_name]['groupdelete'] = 1;
        if ($globalpermission[$site_name]['groupedit'] == 0 && resolvepermission ($permission_array, "group", 3)) $globalpermission[$site_name]['groupedit'] = 1;
        // personalization permissions
        if ($globalpermission[$site_name]['pers'] == 0 && resolvepermission ($permission_array, "pers", 0)) $globalpermission[$site_name]['pers'] = 1;
        if ($globalpermission[$site_name]['perstrack'] == 0 && resolvepermission ($permission_array, "pers", 1)) $globalpermission[$site_name]['perstrack'] = 1;
        if ($globalpermission[$site_name]['perstrackcreate'] == 0 && resolvepermission ($permission_array, "pers", 2)) $globalpermission[$site_name]['perstrackcreate'] = 1;
        if ($globalpermission[$site_name]['perstrackdelete'] == 0 && resolvepermission ($permission_array, "pers", 3)) $globalpermission[$site_name]['perstrackdelete'] = 1;
        if ($globalpermission[$site_name]['perstrackedit'] == 0 && resolvepermission ($permission_array, "pers", 4)) $globalpermission[$site_name]['perstrackedit'] = 1;
        if ($globalpermission[$site_name]['persprof'] == 0 && resolvepermission ($permission_array, "pers", 5)) $globalpermission[$site_name]['persprof'] = 1;
        if ($globalpermission[$site_name]['persprofcreate'] == 0 && resolvepermission ($permission_array, "pers", 6)) $globalpermission[$site_name]['persprofcreate'] = 1;
        if ($globalpermission[$site_name]['persprofdelete'] == 0 && resolvepermission ($permission_array, "pers", 7)) $globalpermission[$site_name]['persprofdelete'] = 1;
        if ($globalpermission[$site_name]['persprofedit'] == 0 && resolvepermission ($permission_array, "pers", 8)) $globalpermission[$site_name]['persprofedit'] = 1;
        // workflow permissions
        if ($globalpermission[$site_name]['workflow'] == 0 && resolvepermission ($permission_array, "workflow", 0)) $globalpermission[$site_name]['workflow'] = 1;
        if ($globalpermission[$site_name]['workflowproc'] == 0 && resolvepermission ($permission_array, "workflow", 1)) $globalpermission[$site_name]['workflowproc'] = 1;
        if ($globalpermission[$site_name]['workflowproccreate'] == 0 && resolvepermission ($permission_array, "workflow", 2)) $globalpermission[$site_name]['workflowproccreate'] = 1;
        if ($globalpermission[$site_name]['workflowprocdelete'] == 0 && resolvepermission ($permission_array, "workflow", 3)) $globalpermission[$site_name]['workflowprocdelete'] = 1;
        if ($globalpermission[$site_name]['workflowprocedit'] == 0 && resolvepermission ($permission_array, "workflow", 4)) $globalpermission[$site_name]['workflowprocedit'] = 1;
        if ($globalpermission[$site_name]['workflowprocfolder'] == 0 && resolvepermission ($permission_array, "workflow", 5)) $globalpermission[$site_name]['workflowprocfolder'] = 1;
        if ($globalpermission[$site_name]['workflowscript'] == 0 && resolvepermission ($permission_array, "workflow", 6)) $globalpermission[$site_name]['workflowscript'] = 1;
        if ($globalpermission[$site_name]['workflowscriptcreate'] == 0 && resolvepermission ($permission_array, "workflow", 7)) $globalpermission[$site_name]['workflowscriptcreate'] = 1;
        if ($globalpermission[$site_name]['workflowscriptdelete'] == 0 && resolvepermission ($permission_array, "workflow", 8)) $globalpermission[$site_name]['workflowscriptdelete'] = 1;
        if ($globalpermission[$site_name]['workflowscriptedit'] == 0 && resolvepermission ($permission_array, "workflow", 9)) $globalpermission[$site_name]['workflowscriptedit'] = 1;
        // template permissions
        if ($globalpermission[$site_name]['template'] == 0 && resolvepermission ($permission_array, "template", 0)) $globalpermission[$site_name]['template'] = 1;
        if ($globalpermission[$site_name]['tpl'] == 0 && resolvepermission ($permission_array, "template", 1)) $globalpermission[$site_name]['tpl'] = 1;
        if ($globalpermission[$site_name]['tplcreate'] == 0 && resolvepermission ($permission_array, "template", 2)) $globalpermission[$site_name]['tplcreate'] = 1;
        if (resolvepermission ($permission_array, "template", 5)) // older versions before 5.5.11 (template upload still exists)
        {
          if ($globalpermission[$site_name]['tpldelete'] == 0 && resolvepermission ($permission_array, "template", 4)) $globalpermission[$site_name]['tpldelete'] = 1;
          if ($globalpermission[$site_name]['tpledit'] == 0 && resolvepermission ($permission_array, "template", 5)) $globalpermission[$site_name]['tpledit'] = 1;
        }
        else
        {
          if ($globalpermission[$site_name]['tpldelete'] == 0 && resolvepermission ($permission_array, "template", 3)) $globalpermission[$site_name]['tpldelete'] = 1;
          if ($globalpermission[$site_name]['tpledit'] == 0 && resolvepermission ($permission_array, "template", 4)) $globalpermission[$site_name]['tpledit'] = 1;
        }
        // template media permissions
        if ($globalpermission[$site_name]['tplmedia'] == 0 && resolvepermission ($permission_array, "media", 0)) $globalpermission[$site_name]['tplmedia'] = 1;
        if ($globalpermission[$site_name]['tplmediacatcreate'] == 0 && resolvepermission ($permission_array, "media", 1)) $globalpermission[$site_name]['tplmediacatcreate'] = 1;
        if ($globalpermission[$site_name]['tplmediacatdelete'] == 0 && resolvepermission ($permission_array, "media", 2)) $globalpermission[$site_name]['tplmediacatdelete'] = 1;
        if ($globalpermission[$site_name]['tplmediacatrename'] == 0 && resolvepermission ($permission_array, "media", 3)) $globalpermission[$site_name]['tplmediacatrename'] = 1;
        if ($globalpermission[$site_name]['tplmediaupload'] == 0 && resolvepermission ($permission_array, "media", 4)) $globalpermission[$site_name]['tplmediaupload'] = 1;
        if ($globalpermission[$site_name]['tplmediadelete'] == 0 && resolvepermission ($permission_array, "media", 5)) $globalpermission[$site_name]['tplmediadelete'] = 1;
        // component permissions
        if ($globalpermission[$site_name]['component'] == 0 && resolvepermission ($permission_array, "component", 0)) $globalpermission[$site_name]['component'] = 1;
        // content permissions
        if ($globalpermission[$site_name]['page'] == 0 && resolvepermission ($permission_array, "page", 0)) $globalpermission[$site_name]['page'] = 1; 
      }
    }

    if (is_array ($globalpermission[$site_name])) 
    {
      return $globalpermission;
    }
  }

  return false;
}

// ---------------------- localpermission -----------------------------
// function: localpermission()
// input: publication name [string], permission string from group [string]
// output: local permission array/false

// description:
// Deserializes the permission string and returns the local permission array

function localpermission ($site_name, $permission_str)
{
  if (is_array ($permission_str) && valid_publicationname ($site_name))
  {
    reset ($permission_str);

    foreach ($permission_str[$site_name] as $group_name => $value)
    {
      if ($group_name != "" && $value != "")
      {
        // get permissions from string
        parse_str ($value, $permission_array);

        // initialize
        $localpermission = array();
        $localpermission[$site_name] = array();
        $localpermission[$site_name][$group_name] = array();

        // component permissions
        $localpermission[$site_name][$group_name]['component'] = resolvepermission ($permission_array, "component", 0);
        $localpermission[$site_name][$group_name]['compupload'] = resolvepermission ($permission_array, "component", 1);
        $localpermission[$site_name][$group_name]['compdownload'] = resolvepermission ($permission_array, "component", 2);
        $localpermission[$site_name][$group_name]['compsendlink'] = resolvepermission ($permission_array, "component", 3); 
        $localpermission[$site_name][$group_name]['compfoldercreate'] = resolvepermission ($permission_array, "component", 4);
        $localpermission[$site_name][$group_name]['compfolderdelete'] = resolvepermission ($permission_array, "component", 5);
        $localpermission[$site_name][$group_name]['compfolderrename'] = resolvepermission ($permission_array, "component", 6);
        $localpermission[$site_name][$group_name]['compcreate'] = resolvepermission ($permission_array, "component", 7);
        $localpermission[$site_name][$group_name]['compdelete'] = resolvepermission ($permission_array, "component", 8);
        $localpermission[$site_name][$group_name]['comprename'] = resolvepermission ($permission_array, "component", 9);
        $localpermission[$site_name][$group_name]['comppublish'] = resolvepermission ($permission_array, "component", 10);
        // content permissions
        $localpermission[$site_name][$group_name]['page'] = resolvepermission ($permission_array, "page", 0);
        $localpermission[$site_name][$group_name]['pageupload'] = resolvepermission ($permission_array, "component", 1); // the upload permission of components is reused here
        $localpermission[$site_name][$group_name]['pagesendlink'] = resolvepermission ($permission_array, "page", 1); 
        $localpermission[$site_name][$group_name]['pagefoldercreate'] = resolvepermission ($permission_array, "page", 2);
        $localpermission[$site_name][$group_name]['pagefolderdelete'] = resolvepermission ($permission_array, "page", 3);
        $localpermission[$site_name][$group_name]['pagefolderrename'] = resolvepermission ($permission_array, "page", 4);
        $localpermission[$site_name][$group_name]['pagecreate'] = resolvepermission ($permission_array, "page", 5);
        $localpermission[$site_name][$group_name]['pagedelete'] = resolvepermission ($permission_array, "page", 6);
        $localpermission[$site_name][$group_name]['pagerename'] = resolvepermission ($permission_array, "page", 7);
        $localpermission[$site_name][$group_name]['pagepublish'] = resolvepermission ($permission_array, "page", 8);
      }
    }

    if (is_array ($localpermission[$site_name])) 
    {
      return $localpermission;
    }
  }

  return false;
}

// ---------------------------------- accessgeneral -------------------------------------------
// function: accessgeneral()
// input: publication name [string], location (path to folder) [string], object category [page,comp]
// output: true/false

// description:
// Checks general access to certain system folders, publications and returns true if access is granted

function accessgeneral ($site, $location, $cat)
{
  global $mgmt_config, $hiddenfolder, $siteaccess;

  $location = correctpath ($location);

  if (valid_publicationname ($site) && valid_locationname ($location) && is_array ($mgmt_config))
  {
    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }
 
    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location);

    if (substr_count ($location, "://") > 0)
    {
      if ($cat == "page") $location = str_replace ($mgmt_config[$site]['url_path_page'], $mgmt_config[$site]['abs_path_page'], $location);
      elseif ($cat == "comp") $location = str_replace ($mgmt_config['url_path_comp'], $mgmt_config['abs_path_comp'], $location);
    }
    elseif (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1)
    {
      $location = deconvertpath ($location, "file");
    }

    // resolve symbolic links
    if (!empty ($mgmt_config[$site]['abs_path_page'])) $mgmt_config[$site]['abs_path_page'] = correctpath (realpath ($mgmt_config[$site]['abs_path_page']));
    if (!empty ($mgmt_config['abs_path_comp'])) $mgmt_config['abs_path_comp'] = correctpath (realpath ($mgmt_config['abs_path_comp']));
    if (!empty ($mgmt_config['abs_path_rep'])) $mgmt_config['abs_path_rep'] = correctpath (realpath ($mgmt_config['abs_path_rep']));

    // cut off file name 
    if (is_file ($location) && $location[strlen ($location)-1] != "/")
    {
      $location = substr ($location, 0, strrpos ($location, "/") + 1);
    }
    elseif (is_dir ($location) && $location[strlen ($location)-1] != "/")
    {
      $location = $location."/";
    }

    // convert location
    $location_esc = convertpath ($site, $location, $cat);

    // check publication and location
    if ($location_esc != "")
    {
      $site_location = getpublication ($location_esc);
      if ($site_location != $site) return false;
    }
    else return false;

    // check publication access
    if (!empty ($siteaccess) && is_array ($siteaccess))
    {
      if (!checkpublicationpermission ($site)) return false;
    }

    // check excluded folders
    if (isset ($hiddenfolder[$site]) && is_array ($hiddenfolder[$site]))
    {
      foreach ($hiddenfolder[$site] as $exclude_folder)
      {
        if (!empty ($location_esc) && !empty ($exclude_folder) && substr_count ($location_esc, $exclude_folder) > 0) return false;
      }
    }

    // check data root and cms root
    if (@substr_count ($location, $mgmt_config['abs_path_cms']) == 0 && @substr_count ($location, $mgmt_config['abs_path_data']) == 0)
    {
      // check page access
      if ($cat == "page" && @substr_count ($location, $mgmt_config[$site]['abs_path_page']) > 0 && @substr_count ($location, $mgmt_config['abs_path_rep']) == 0 && @substr_count ($mgmt_config['abs_path_rep'], $location) == 0) 
      {
        return true;
      }
      // check component access
      elseif ($cat == "comp" && @substr_count ($location, $mgmt_config['abs_path_comp']) > 0)
      {
        return true;
      }
    }
  }

  return false;
}

// ---------------------------------- accesspermission -------------------------------------------
// function: accesspermission()
// input: location (path to folder) [string], object category [page,comp]
// output: group with access permissions as array / false on error
// requires: accessgeneral

// description:
// Evaluates page and asset/component access permissions and returns the group(s). Since version 8.0.0 this function does not evaluate the access based on access links anymore since explorer_objectlist verifies the access linking.

function accesspermission ($site, $location, $cat)
{
  global $user, $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $mgmt_config; 

  $location = correctpath ($location);

  if (valid_publicationname ($site) && valid_locationname ($location) && is_array ($mgmt_config))
  {
    // initialize
    $points = array();
    $groups = array();
    $result = array();

    // publication management config
    if (!isset ($mgmt_config[$site]['abs_path_page']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    } 

    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location); 

    // deconvert path to absolute file path
    if (@substr_count ($location, "://") > 0)
    {
      if ($cat == "page") $location = str_replace ($mgmt_config[$site]['url_path_page'], $mgmt_config[$site]['abs_path_page'], $location);
      elseif ($cat == "comp") $location = str_replace ($mgmt_config['url_path_comp'], $mgmt_config['abs_path_comp'], $location);
    }
    elseif (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1)
    {
      $location = deconvertpath ($location, "file");
    }

    // remove slash if present at the end of the location string
    if (substr ($location, -1) == "/") $location = substr ($location, 0 , -1);

    // cut off file name 
    if (is_file ($location)) $location = getlocation ($location);

    // add slash if not present at the end of the location string
    $location = correctpath ($location); 

    // check general access permissions
    $access_passed = accessgeneral ($site, $location, $cat);

    // check access
    if ($access_passed == true)
    {
      // check page access (must hold absolute path values)
      if ($cat == "page" && isset ($pageaccess[$site]) && is_array ($pageaccess[$site]) && @substr_count ($location, $mgmt_config['abs_path_rep']) == 0) 
      {
        reset ($pageaccess);
        $thisaccess = $pageaccess[$site];        
        $i = 0;

        // groups
        foreach ($thisaccess as $group => $value)
        {
          // split access-string into an array
          $value = trim ($value, "|");
          $value_array = explode ("|", $value);

          // access locations
          foreach ($value_array as $value)
          {
            if ($value != "" && substr_count ($location, $value) > 0)
            {
              $points[$i] = strlen ($value);
              $groups[$i] = $group;
              $i++;
            }
          }
        }
      }
      // check component access (must provide absolute path values)
      elseif ($cat == "comp" && isset ($compaccess[$site]) && is_array ($compaccess[$site]))
      {
        reset ($compaccess);
        $thisaccess = $compaccess[$site];
        $i = 0;

        // groups
        foreach ($thisaccess as $group => $value)
        {
          // split access-string into an array
          $value = trim ($value, "|");
          $value_array = explode ("|", $value);

          // access locations
          foreach ($value_array as $value)
          {
            if ($value != "" && substr_count ($location, $value) > 0)
            {
              $points[$i] = strlen ($value);
              $groups[$i] = $group;
              $i++;
            }
          }      
        }
      }
      else return false;

      // return result if group was located
      if (isset ($groups) && is_array ($groups) && sizeof ($groups) > 0 && isset ($points) && is_array ($points) && sizeof ($points) > 0)
      {
        // get longest location (this is the closest to the current location and will therefore apply)
        $max = max ($points);

        foreach ($points as $id => $point)
        {
          if ($point == $max) $result[] = $groups[$id];
        }

        if (is_array ($result) && sizeof ($result) > 0) return $result;
      }
      // return default group as result if no group was located and hcms linking exists
      elseif (linking_valid())
      {
        $result[] = "default";
        return $result;
      }
    }
  }

  return false;
}

// ---------------------- setlocalpermission -----------------------------
// function: setlocalpermission()
// input: publication name [string], group name [array], object category [page,comp]
// output: local permission array / false on error

// description:
// Sets local permissions of a user group for a specific publication

function setlocalpermission ($site, $group_array, $cat)
{
  global $localpermission, $user;

  // try to get localpermission from session
  if ((!isset ($localpermission) || !is_array ($localpermission)) && isset ($_SESSION['hcms_localpermission']) && is_array ($_SESSION['hcms_localpermission']))
  {
    $localpermission = $_SESSION['hcms_localpermission'];
  }

  // set all permissions to zero
  $setlocalpermission = array();
  $setlocalpermission['root'] = 0;
  $setlocalpermission['upload'] = 0;
  $setlocalpermission['download'] = 0;
  $setlocalpermission['sendlink'] = 0;
  $setlocalpermission['foldercreate'] = 0;
  $setlocalpermission['folderdelete'] = 0;
  $setlocalpermission['folderrename'] = 0;
  $setlocalpermission['create'] = 0;
  $setlocalpermission['delete'] = 0;
  $setlocalpermission['rename'] = 0;
  $setlocalpermission['publish'] = 0;

  if (valid_publicationname ($site) && is_array ($group_array) && ($cat == "page" || $cat == "comp"))
  {
    $cat = strtolower ($cat);

    reset ($group_array);

    foreach ($group_array as $group)
    {
      // asset/component permissions
      if ($cat == "comp")
      {
        if ($setlocalpermission['root'] == 0 && @$localpermission[$site][$group]['component'] == 1) $setlocalpermission['root'] = 1;
        if ($setlocalpermission['upload'] == 0 && @$localpermission[$site][$group]['compupload'] == 1) $setlocalpermission['upload'] = 1;
        if ($setlocalpermission['download'] == 0 && @$localpermission[$site][$group]['compdownload'] == 1) $setlocalpermission['download'] = 1;
        if ($setlocalpermission['sendlink'] == 0 && @$localpermission[$site][$group]['compsendlink'] == 1) $setlocalpermission['sendlink'] = 1;
        if ($setlocalpermission['foldercreate'] == 0 && @$localpermission[$site][$group]['compfoldercreate'] == 1) $setlocalpermission['foldercreate'] = 1;
        if ($setlocalpermission['folderdelete'] == 0 && @$localpermission[$site][$group]['compfolderdelete'] == 1) $setlocalpermission['folderdelete'] = 1;
        if ($setlocalpermission['folderrename'] == 0 && @$localpermission[$site][$group]['compfolderrename'] == 1) $setlocalpermission['folderrename'] = 1;
        if ($setlocalpermission['create'] == 0 && @$localpermission[$site][$group]['compcreate'] == 1) $setlocalpermission['create'] = 1; 
        if ($setlocalpermission['delete'] == 0 && @$localpermission[$site][$group]['compdelete'] == 1) $setlocalpermission['delete'] = 1;
        if ($setlocalpermission['rename'] == 0 && @$localpermission[$site][$group]['comprename'] == 1) $setlocalpermission['rename'] = 1;
        if ($setlocalpermission['publish'] == 0 && @$localpermission[$site][$group]['comppublish'] == 1) $setlocalpermission['publish'] = 1;
      }
      // page permissions
      elseif ($cat == "page")
      {
        if ($setlocalpermission['root'] == 0 && @$localpermission[$site][$group]['page'] == 1) $setlocalpermission['root'] = 1;
        if ($setlocalpermission['upload'] == 0 && @$localpermission[$site][$group]['pageupload'] == 1) $setlocalpermission['upload'] = 1;
        if ($setlocalpermission['sendlink'] == 0 && @$localpermission[$site][$group]['pagesendlink'] == 1) $setlocalpermission['sendlink'] = 1;
        if ($setlocalpermission['foldercreate'] == 0 && @$localpermission[$site][$group]['pagefoldercreate'] == 1) $setlocalpermission['foldercreate'] = 1;
        if ($setlocalpermission['folderdelete'] == 0 && @$localpermission[$site][$group]['pagefolderdelete'] == 1) $setlocalpermission['folderdelete'] = 1;
        if ($setlocalpermission['folderrename'] == 0 && @$localpermission[$site][$group]['pagefolderrename'] == 1) $setlocalpermission['folderrename'] = 1;
        if ($setlocalpermission['create'] == 0 && @$localpermission[$site][$group]['pagecreate'] == 1) $setlocalpermission['create'] = 1;
        if ($setlocalpermission['delete'] == 0 && @$localpermission[$site][$group]['pagedelete'] == 1) $setlocalpermission['delete'] = 1;
        if ($setlocalpermission['rename'] == 0 && @$localpermission[$site][$group]['pagerename'] == 1) $setlocalpermission['rename'] = 1;
        if ($setlocalpermission['publish'] == 0 && @$localpermission[$site][$group]['pagepublish'] == 1) $setlocalpermission['publish'] = 1;
      }
    }
  }

  return $setlocalpermission;
}

// ------------------------- checkpublicationpermission -----------------------------
// function: checkpublicationpermission()
// input: publication name [string], strictly limited to publication access without inheritance [boolean] (optional)
// output: "direct" for direct access via group permission / "inherited" for access through inheritance / false

// description:
// Checks the access to a publication based on the publication access and inheritance settings

function checkpublicationpermission ($site, $strict=true)
{
  global $mgmt_config, $siteaccess;

  // try to get siteaccess from session
  if (!is_array ($siteaccess) && isset ($_SESSION['hcms_siteaccess']) && is_array ($_SESSION['hcms_siteaccess']))
  {
    $siteaccess = $_SESSION['hcms_siteaccess'];
  }

  if (valid_publicationname ($site) && !empty ($siteaccess) && is_array ($siteaccess))
  {
    // publication is in scope of user
    if (array_key_exists ($site, $siteaccess)) return "direct";
    elseif ($strict == true) return false;

    // load publication inheritance setting
    $inherit_db = inherit_db_read ();
    $child_array = inherit_db_getchild ($inherit_db, $site);

    // check access to publication by inheritance
    if (is_array ($child_array))
    {
      foreach ($siteaccess as $child => $displayname)
      {
        // load child publication settings
        if (valid_publicationname ($child)) @require ($mgmt_config['abs_path_data']."config/".$child.".conf.php");
        // check component access
        if (in_array ($child, $child_array) && $mgmt_config[$child]['inherit_comp'] == true) return "inherited";
      }
    }
  }

  return false;
}

// ---------------------- checkadminpermission -----------------------------
// function: checkadminpermission()
// input: %
// output: true/false

// description:
// Checks the super admin permission

function checkadminpermission ()
{
  global $adminpermission;

  // try to get localpermission from session
  if ((!isset ($adminpermission) || !is_array ($adminpermission)) && isset ($_SESSION['hcms_superadmin']))
  {
    $adminpermission = $_SESSION['hcms_superadmin'];
  }

  // root permission
  if (isset ($adminpermission) && $adminpermission == 1) return true;

  return false;
}

// ---------------------- checkrootpermission -----------------------------
// function: checkrootpermission()
// input: permission name [string]
// output: true/false

// description:
// Checks the root permissions

function checkrootpermission ($name)
{
  global $rootpermission;

  if (valid_objectname ($name))
  {
    // try to get localpermission from session
    if ((!isset ($rootpermission) || !is_array ($rootpermission)) && isset ($_SESSION['hcms_rootpermission']) && is_array ($_SESSION['hcms_rootpermission']))
    {
      $rootpermission = $_SESSION['hcms_rootpermission'];
    }

    // root permission
    if (isset ($rootpermission[$name]))
    {
      if ($rootpermission[$name] == 1) return true;
    }
  }

  return false;
}

// ---------------------- checkglobalpermission -----------------------------
// function: checkglobalpermission()
// input: publication name [string], permission name [string]
// output: true/false

// description:
// Checks global permission for a publication

function checkglobalpermission ($site, $name)
{
  global $globalpermission;

  if (valid_publicationname ($site) && valid_objectname ($name))
  {
    // try to get localpermission from session
    if ((!isset ($globalpermission) || !is_array ($globalpermission)) && isset ($_SESSION['hcms_globalpermission']) && is_array ($_SESSION['hcms_globalpermission']))
    {
      $globalpermission = $_SESSION['hcms_globalpermission'];
    }

    // global permission
    if (isset ($globalpermission[$site][$name]))
    {
      if ($globalpermission[$site][$name] == 1) return true;
    }
  }

  return false;
}

// ---------------------- checklocalpermission -----------------------------
// function: checklocalpermission()
// input: publication name [string], user group name [string], permission name [string]
// output: true/false

// description:
// Checks local permissions of a user group for a specific publication

function checklocalpermission ($site, $group, $name)
{
  global $localpermission;

  if (valid_publicationname ($site) && valid_objectname ($group) && valid_objectname ($name))
  {
    // try to get localpermission from session
    if ((!isset ($localpermission) || !is_array ($localpermission)) && isset ($_SESSION['hcms_localpermission']) && is_array ($_SESSION['hcms_localpermission']))
    {
      $localpermission = $_SESSION['hcms_localpermission'];
    }

    // local permission
    if (isset ($localpermission[$site][$group][$name]))
    {
      if ($localpermission[$site][$group][$name] == 1) return true;
    }
  }

  return false;
}

// ---------------------- checkpluginpermission -----------------------------
// function: checkpluginpermission()
// input: publication name [string], plugin name [string]
// output: true/false

// description:
// Checks the plugin access permissions of a user for a specific plugin

function checkpluginpermission ($site, $pluginname)
{
  global $mgmt_config, $mgmt_plugin, $pluginaccess;

  if (valid_objectname ($pluginname))
  {
    // try to get pluginaccess from session
    if ((!isset ($pluginaccess) || !is_array ($pluginaccess)) && isset ($_SESSION['hcms_pluginaccess']) && is_array ($_SESSION['hcms_pluginaccess']))
    {
      $pluginaccess = $_SESSION['hcms_pluginaccess'];
    }

    // load plugin definitions
    if ((empty ($mgmt_plugin) || !is_array ($mgmt_plugin)) && is_file ($mgmt_config['abs_path_data']."config/plugin.global.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/plugin.global.php");
    }

    // access permissions to plugins
    if (!empty ($mgmt_plugin) && is_array ($mgmt_plugin))
    {
      foreach ($mgmt_plugin as $key => $data)
      {
        // only active plugins can be used
        if ($key == $pluginname && is_array ($data) && !empty ($data['active']))
        {
          // publication specific plugin access
          if (valid_publicationname ($site) && !empty ($pluginaccess[$site]) && is_array ($pluginaccess[$site]) && in_array ($pluginname, $pluginaccess[$site]))
          {
            return true;
          }
          // plugin access for all publications (no publication name provided) 
          elseif (!valid_publicationname ($site) && !empty ($pluginaccess) && is_array ($pluginaccess))
          {
            foreach ($pluginaccess as $temp_site => $temp_pluginnames)
            {
              if (in_array ($pluginname, $temp_pluginnames)) return true;
            }
          }
        }
      }
    }
  }
  
  return false;
}

// --------------------------------------- checklanguage -----------------------------------------------
// function: checklanguage()
// input: language array with all valid values [array], language value of attribute in template tag [string}
// output: true if language array holds the given language value / false if not found

function checklanguage ($language_array, $language_value)
{
  if (is_array ($language_array) && $language_value != "")
  {
    if (in_array ($language_value, $language_array)) return true;
  }

  return true;
}

// --------------------------------------- checkgroupaccess -----------------------------------------------
// function: checkgroupaccess()
// input: group access from template group-tag attribute [string], user group membership names [array]
// output: true if the current user group has access / false if not

// description:
// Verifies if a user has access to the tags content based on the group membership.

function checkgroupaccess ($groupaccess, $usergroup_array)
{
  // no group access defined
  if (trim ($groupaccess) == "" || !is_string ($groupaccess))
  {
    return true;
  }
  // no user groups defined
  elseif (!is_array ($usergroup_array) || sizeof ($usergroup_array) < 1)
  {
    return false;
  }
  // continue
  else
  {
    $groupaccess_array = array ();

    // replace ; with |
    if (substr_count ($groupaccess, ";") > 0) $groupaccess = str_replace (";", "|", $groupaccess);

    // split into array
    if (substr_count ($groupaccess, "|") > 0) $groupaccess_array = explode ("|", $groupaccess);
    else $groupaccess_array[] = $groupaccess;

    if (is_array ($groupaccess_array) && sizeof ($groupaccess_array) > 0)
    {
      // verify group access
      foreach ($usergroup_array as $usergroup)
      {
        if (in_array ($usergroup, $groupaccess_array)) return true;
      }

      return false;
    }
    else return true;
  }
}


// =============================== USER LOGON/SESSION FUNCTIONS ================================

// --------------------------------------- userlogin -------------------------------------------
// function: userlogin()
// input: user name [string] (optional if hash code is used for logon), password [string] (optional if hash code is used for logon), hash code of user [string] (optional), object reference for hcms linking (object ID) [string] (optional), 
//        object code for hcms linking (crypted object ID) [string] (optional), ignore passwordcheck needed for WebDAV or access link [boolean] (optional), lock IP after 10 failed attempts to login [boolean] (optional), portal name in the form of publication.portal or publication/portal [string] (optional)
// output: result array
// requires: config.inc.php to be loaded before

// description:
// Login of a user by his credentials (user and password, or user hash code).
// The function reads and provides all permissions of the user and authenticated against other user directories, e.g. LDAP/AD if defined in the main configuration, see $mgmt_config['authconnect'].
// The function provides a result array but does not register the user in the session.

function userlogin ($user="", $passwd="", $hash="", $objref="", $objcode="", $ignore_password=false, $locking=true, $portal="")
{
  global $mgmt_config, $eventsystem, $hcms_lang_codepage, $hcms_lang, $lang, $is_webdav;

  // initialize
  $error = array();

  // set default language
  if (empty ($lang)) $lang = "en";

  // include hypermailer class
  if (!class_exists ("HyperMailer")) require ($mgmt_config['abs_path_cms']."function/hypermailer.class.php");

  if (!empty ($mgmt_config['db_connect_rdbms']))
  {
    include_once ($mgmt_config['abs_path_cms']."database/db_connect/".$mgmt_config['db_connect_rdbms']);
  }

  // result array definition
  $result = array(
      'hcms_linking' => array(),
      'globalpermission' => array(),
      'localpermission' => array(),
      'siteaccess' => array(),
      'pageaccess' => array(),
      'compaccess' => array(),
      'pluginaccess' => array(),
      'hiddenfolder' => array(),
      'auth' => false,
      'html' => '',
      'rootpermission' => array(),
      'lang' => '',
      'timezone' => '',
      'user' => '',
      'passwd' => '',
      'userhash' => '',
      'superadmin' => '',
      'instance' => false,
      'checksum' => '',
      'message' => '',
      'mobile' => '',
      'chatstate' => '',
      'resetpassword' => false,
      'userexpired' => false,
      'portal' => false,
      'themename' => '',
      'themeinvertcolors' => false,
      'hoverinvertcolors' => false,
      'mainnavigation' => "left",
      'downloadformats' => array(),
      'objectlistcols' => array(),
      'labels' => array(),
      'toolbarfunctions' => array()
      );

  // initialize
  $linking_auth = true;
  $ldap_auth = true;
  $validdate = true;
  $checkresult = false;
  $auth = false;
  $site_collection = "";
  $fileuser = NULL;
  $filepasswd = NULL;
  $superadmin = NULL;
  $memberofnode = NULL;
  $permission_str = array();
  $usergroups = array();

  // eventsystem
  if (!empty ($eventsystem['onlogon_pre']) && empty ($eventsystem['hide']) && function_exists ("onlogon_pre"))
  {
    onlogon_pre ($user);
  }

  // --------------------- user data --------------------- 
  // please note: each user login name and user group name is unique
  // load user file
  $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");

  // user file could not be loaded (might be locked by a user)
  if ($userdata == false)
  {
    // get locked file info
    $result_locked = getlockedfileinfo ($mgmt_config['abs_path_data']."user/", "user.xml.php");

    if (is_array ($result_locked) && $result_locked['user'] != "")
    {
      // unlock file
      $result_unlock = unlockfile ($result_locked['user'], $mgmt_config['abs_path_data']."user/", "user.xml.php");
    }
    else
    {
      // send mail
      $mailer = new HyperMailer();
      $mailer->AddAddress ("info@hypercms.net");
      $mailer->Subject = "hyperCMS logon failed on server: ".$_SERVER['SERVER_NAME'];
      $mailer->Body = "User directory is locked!\nhyperCMS Host: ".$_SERVER['SERVER_NAME']."\n";
      $mailer->Send();

      $result['message'] = $hcms_lang['the-user-index-is-locked'][$lang];
    }

    if (isset ($result_unlock) && $result_unlock == true)
    {
      // load user file
      $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php"); 
    }
    else $userdata = false;
  }

  // --------------------- object linking --------------------- 
  if (!empty ($objref) && !empty ($objcode))
  {
    $passwd_crypted = urldecode ($passwd);
    $linking_auth = false;

    // check object reference (ID) and object code (token) in version before and after version 5.5.8
    if (!empty ($mgmt_config['db_connect_rdbms']) && ($objcode == hcms_crypt ($objref, 3, 12) || $objcode == hcms_crypt ($objref)))
    {
      if (strpos ($objref, "|") > 0)
      {
        $multiobject = link_db_getobject ($objref); 
      }
      else $multiobject = array ($objref);

      if (is_array ($multiobject))
      {
        foreach ($multiobject as $temp)
        {
          $objectpath = rdbms_getobject ($temp);
          $objecthash = rdbms_getobject_hash ($temp);

          if (!empty ($objectpath))
          {
            $result['hcms_linking'][$objecthash] = $objectpath;
            $linking_auth = true;
          }
        }
      }
    }
  }
  else
  {
    // old password hash before version 7.0.6 (deprecated)
    // deprecated salt might cause PHP Deprecated: Supplied salt is not valid for DES.
    $passwd_crypted = @crypt ($passwd, substr ($passwd, 1, 2));
  }

  if ($linking_auth)
  {
    // change of the password after reset request
    if (is_file ($mgmt_config['abs_path_temp'].$user.".resetpassword.dat"))
    {
      $result['resetpassword'] = true;
    }
    // request to change the password
    elseif (!empty ($mgmt_config['passwordexpires']))
    {
      $log_array = loadlog ($user.".password", "array");

      if (is_array ($log_array) && end ($log_array) != "")
      {
        list ($log_date, $log_password) = explode ("|", end ($log_array));

        // expiration UNIX timestamp
        $pw_expires = strtotime ($log_date) + intval ($mgmt_config['passwordexpires']) * 24 * 60 * 60;

        // if password is expired
        if (time() > $pw_expires) $result['resetpassword'] = true;
      }
    }

    // ---------------------  user expiration due to inactivity --------------------- 
    if (!empty ($mgmt_config['userexpires']))
    {
      $log_array = loadlog ($user.".user", "array");

      if (is_array ($log_array) && end ($log_array) != "")
      {
        list ($log_date, $rest) = explode ("|", end ($log_array));

        // expiration UNIX timestamp
        $user_expires = strtotime ($log_date) + intval ($mgmt_config['userexpires']) * 24 * 60 * 60;

        // if user is expired due to inactivity
        if (time() > $user_expires)
        {
          $result['userexpired'] = true;
          $result['message'] = $hcms_lang['the-user-information-cant-be-accessed'][$lang]." (inactivity limit of ".intval ($mgmt_config['userexpires'])." days)";
          $auth = false;
        }
      }
    }

    if ($userdata != false)
    {
      // --------------------- updates ---------------------

      updates_all ();

      // get encoding (before version 5.5 encoding was empty and was saved as ISO 8859-1)
      $charset = getcharset ("", $userdata); 

      if (empty ($charset))
      {
        // set encoding
        $charset = "utf-8";

        // UTF-8 encode ISO-8859-1 special characters
        $userdata = utf8_encode ($userdata);

        // write XML declaration parameter for text encoding
        if ($charset != "") $userdata = setxmlparameter ($userdata, "encoding", $charset);

        // save user file in unlocked mode
        if ($userdata != "") $update_result = savefile ($mgmt_config['abs_path_data']."user/", "user.xml.php", $userdata);

        // error log
        if ($update_result == false)
        {
          $errcode = "10318";
          $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|error|".$errcode."|".($is_webdav ? "WebDAV: " : "")."Update (UTF-8 encoding) of user management file failed";

          // save log
          savelog (@$error);
        } 
      }

      // --------------------- include authentification connectivity (LDAP, AD, or others) for sync of users ---------------------

      // new main configuration parameter $mgmt_config['authconnect_all'] has been introduced in version 9.0.2
      if (!empty ($mgmt_config['authconnect']))
      {
        // use data/connect/
        if (is_file ($mgmt_config['abs_path_data']."connect/".$mgmt_config['authconnect'].".inc.php")) include_once ($mgmt_config['abs_path_data']."connect/".$mgmt_config['authconnect'].".inc.php");
        // use connector/authconnect/
        elseif (is_file ($mgmt_config['abs_path_cms']."connector/authconnect/".$mgmt_config['authconnect'].".inc.php")) include_once ($mgmt_config['abs_path_cms']."connector/authconnect/".$mgmt_config['authconnect'].".inc.php");

        if (function_exists ("authconnect"))
        {
          // if LDAP/AD has been defined for all publications
          if (!empty ($mgmt_config['authconnect_all']))
          {
            $ldap_auth = authconnect ($user, $passwd);

            if ($ldap_auth == false)
            {
              // warning
              $errcode = "00721";
              $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|warning|".$errcode."|".($is_webdav ? "WebDAV: " : "")."LDAP/AD authentication failed for user '".$user."' using LDAP servers: ".$mgmt_config['ldap_servers'].", base DN: ".$mgmt_config['ldap_base_dn'].", user domain: ".$mgmt_config['ldap_userdomain'];
            }
          }
          // if LDAP/AD has been defined per publication
          else
          {
            $usernode = selectcontent ($userdata, "<user>", "<login>", $user);

            // get publications if user exists
            if (!empty ($usernode[0]))
            {
              $temp_user_sites = getcontent ($usernode[0], "<publication>");
            }
            // create user information array for all publications if user does not exist
            else
            {
              $inherit_db = inherit_db_read ();
              $temp_user_sites = array();

              if (!empty ($inherit_db) && sizeof ($inherit_db) > 0)
              {
                foreach ($inherit_db as $inherit_db_record)
                {
                  if (!empty ($inherit_db_record['parent']))
                  {
                    $temp_user_sites[] = trim ($inherit_db_record['parent']);
                  }
                }
              }
            }

            // verify user
            if (!empty ($temp_user_sites) && is_array ($temp_user_sites))
            {
              foreach ($temp_user_sites as $temp_site)
              {
                // publication management config
                if (valid_publicationname ($temp_site) && empty ($mgmt_config[$temp_site]['ldap_servers'])) require_once ($mgmt_config['abs_path_data']."config/".$temp_site.".conf.php");

                // verify mandatory settings
                if (!empty ($mgmt_config[$temp_site]['ldap_servers']) && !empty ($mgmt_config[$temp_site]['ldap_base_dn']))
                {
                  $ldap_auth = authconnect ($user, $passwd, $temp_site);

                  if ($ldap_auth != true)
                  {
                    // warning
                    $errcode = "00722";
                    $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|warning|".$errcode."|".($is_webdav ? "WebDAV: " : "")."LDAP/AD authentication failed for user '".$user."' using LDAP servers: ".$mgmt_config[$temp_site]['ldap_servers'].", base DN: ".$mgmt_config[$temp_site]['ldap_base_dn'].", user domain: ".$mgmt_config[$temp_site]['ldap_userdomain'];
                  }
                }
              }
            }
          }

          // SSO with provided user name
          // only grant user access if it has been verified by a provided LDAP/AD admin user and the function verifyoauthclient exists (requires Connector module)
          if (!empty ($mgmt_config['sso']) && $ldap_auth == true && !empty ($mgmt_config['ldap_admin_username']) && !empty ($mgmt_config['ldap_admin_password']) && function_exists ("verifyoauthclient"))
          {
            // get user hash for authentication since no password has been provided
            if (!empty ($userdata))
            {
              $usernode = selectcontent ($userdata, "<user>", "<login>", $user);

              if (!empty ($usernode[0]))
              {
                $temp = getcontent ($usernode[0], "<hashcode>");

                if (!empty ($temp[0]))
                {
                  $hash = $temp[0];
                  $user_ip = getuserip();

                  // log IP of user
                  if (!empty ($user_ip))
                  {
                    // load log
                    if (is_file ($mgmt_config['abs_path_data']."log/".$user.".ip.log")) $log_ip = file_get_contents ($mgmt_config['abs_path_data']."log/".$user.".ip.log");

                    // save log 
                    if (!empty ($log_ip) && strpos ("_".$log_ip, $user_ip) < 1) savelog (array ($user_ip), $user.".ip");
                  }
                }
                else
                {
                  // warning
                  $errcode = "00723";
                  $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|warning|".$errcode."|".($is_webdav ? "WebDAV: " : "")."The user hash of user '".$user."' is empty";
                }
              }
            }
          }
        }

        // reload the user file on success since the user might have been edited by function authconnect
        if (!empty ($ldap_auth))
        {
          $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");
        }
      }

      // --------------------- get user data --------------------- 

      // count users
      $users = substr_count ($userdata, "</user>");

      // get user information
      if ($user != "") $usernode = selectcontent ($userdata, "<user>", "<login>", $user);
      elseif ($hash != "") $usernode = selectcontent ($userdata, "<user>", "<hashcode>", $hash);
      else $usernode = false;

      if (!empty ($usernode[0]))
      {
        // user name
        $userlogin = getcontent ($usernode[0], "<login>");
        if (!empty ($userlogin[0])) $fileuser = $userlogin[0];
        else $fileuser = "";

        // password hash
        $userpasswd = getcontent ($usernode[0], "<password>");
        if (!empty ($userpasswd[0])) $filepasswd = $userpasswd[0];
        else $filepasswd = "";

        // user hash for WebDAV
        $userhash = getcontent ($usernode[0], "<hashcode>");
        if (!empty ($userhash[0])) $result['userhash'] = $userhash[0];
        else $result['userhash'] = "";

        // user real name
        $userrealname = getcontent ($usernode[0], "<realname>");
        if (!empty ($userrealname[0])) $result['realname'] = $userrealname[0];
        else $result['realname'] = "";

        // user e-mail
        $useremail = getcontent ($usernode[0], "<email>");
        if (!empty ($useremail[0])) $result['email'] = $useremail[0];
        else $result['email'] = "";

        // super admin
        $useradmin = getcontent ($usernode[0], "<admin>");
        if (!empty ($useradmin[0])) $result['superadmin'] = $superadmin = $useradmin[0];
        else $result['superadmin'] = "";

        // language
        $userlanguage = getcontent ($usernode[0], "<language>");
        if (!empty ($userlanguage[0])) $result['lang'] = $userlanguage[0];
        else $result['lang'] = "en";

        // time zone
        $usertimezone = getcontent ($usernode[0], "<timezone>");
        if (!empty ($usertimezone[0])) $result['timezone'] = $usertimezone[0];
        else $result['timezone'] = "";

        // set language of user and load language file
        $lang = $result['lang'];
        require_once ($mgmt_config['abs_path_cms']."language/".getlanguagefile ($lang));

        // valid dates
        $uservaliddatefrom = getcontent ($usernode[0], "<validdatefrom>");
        if (!empty ($uservaliddatefrom[0])) $result['validdatefrom'] = $uservaliddatefrom[0];
        else $result['validdatefrom'] = "";

        $uservaliddateto = getcontent ($usernode[0], "<validdateto>");
        if (!empty ($uservaliddateto[0])) $result['validdateto'] = $uservaliddateto[0];
        else $result['validdateto'] = "";

        // lo logon allowed
        $usernologon = getcontent ($usernode[0], "<nologon>");
        if (!empty ($usernologon[0])) $result['nologon'] = $usernologon[0];
        else $result['nologon'] = 0;

        // --------------------- portal --------------------- 

        if (!empty ($portal))
        {
          // set design theme of portal for the user
          if (strpos ($portal, ".") > 0)
          {
            list ($portal_site, $portal_theme) = explode (".", $portal);
            $result['portal'] = $result['themename'] = $portal_site."/".$portal_theme;
          }
          elseif (strpos ($portal, "/") > 0)
          {
            $result['portal'] = $result['themename'] = $portal;
          }

          // extract download formats if a portal theme is used
          if (!empty ($result['themename']) && strpos ($result['themename'], "/") > 0)
          {
            list ($portal_site, $portal_theme) = explode ("/", $result['themename']);

            if (valid_objectname ($portal_theme))
            {
              $portal_template = $portal_theme.".portal.tpl";
              $portal_template = loadtemplate ($portal_site, $portal_template);

              // get download formats and main navigation position
              if (!empty ($portal_template['content']))
              {
                $temp_array = getcontent ($portal_template['content'], "<downloadformats>");

                if (!empty ($temp_array[0]))
                {
                  $result['downloadformats'] = json_decode ($temp_array[0], true);
                }

                $temp_array = getcontent ($portal_template['content'], "<mainnavigation>");

                if (!empty ($temp_array[0]))
                {
                  $result['mainnavigation'] = $temp_array[0];
                }
              }
            }
          }
        }

        // --------------------- design theme --------------------- 

        // if design theme has not been set so far (no portal used or portal name is not valid)
        if (empty ($result['themename']))
        {
          // mandatory design theme has been defined in main configuration
          if (!empty ($mgmt_config['theme']))
          {
            $result['themename'] = $mgmt_config['theme'];
          }
          // get design theme of the user
          else
          {
            $usertheme = getcontent ($usernode[0], "<theme>");

            if (!empty ($usertheme[0])) $result['themename'] = $usertheme[0];
            else $result['themename'] = "standard";
          }

          // if portal design theme
          if (strpos ($result['themename'], "/") > 0)
          {
            list ($portal_site, $portal_theme) = explode ("/", $result['themename']);

            if (valid_objectname ($portal_theme))
            {
              $portal_template = $portal_theme.".portal.tpl";
              $portal_template = loadtemplate ($portal_site, $portal_template);

              // get download formats and main navigation position
              if (!empty ($portal_template['content']))
              {
                $temp_array = getcontent ($portal_template['content'], "<mainnavigation>");

                if (!empty ($temp_array[0]))
                {
                  $result['mainnavigation'] = $temp_array[0];
                }
              }
            }
          }
        }

        // get design theme and primary color if a portal theme is used
        $result_inverted_themes = getinvertcolortheme ($result['themename']);
        $result = array_merge ($result, $result_inverted_themes);

        // -------------- publication and group memberships --------------
        
        $memberofnode = getcontent ($usernode[0], "<memberof>");
      }

      // --------------------- permissions --------------------- 

      // check valid dates
      if (!empty ($result['validdatefrom']) && strtotime ($result['validdatefrom']) > time()) $validdate = false;
      if (!empty ($result['validdateto']) && strtotime ($result['validdateto']) < time()) $validdate = false;

      // check logon
      if ($validdate == true && ((!empty ($hash) && $hash == $result['userhash']) || ($user == $fileuser && ((empty ($result['nologon']) && (password_verify ($passwd, $filepasswd) || $filepasswd == $passwd_crypted)) || $ignore_password))))
      {
        $result['user'] = $fileuser;
        $result['passwd'] = $filepasswd;

        // super, download or system user
        if ($user == "admin" || $user == "sys" || $user == "hcms_download" || $superadmin == "1")
        {
          $inherit_db = inherit_db_read ();

          // set permissions and group name
          if ($user != "hcms_download") $permission_str_admin = "desktop=1111111&site=1111&user=1111&group=1111&pers=111111111&workflow=1111111111&template=11111&media=111111&component=11111111111&page=111111111";
          else $permission_str_admin = "desktop=0000000&site=0000&user=0000&group=0000&pers=000000000&workflow=0000000000&template=00000&media=000000&component=10100000000&page=000000000";

          if ($user != "hcms_download")
          {
            $site_admin = true;
            $group_name_admin = "admin";
          }
          else
          {
            $site_admin = false;
            $group_name_admin = "download";
          }

          if (is_array ($inherit_db))
          {
            foreach ($inherit_db as $key => $inherit_db_record)
            {
              $site_name = $inherit_db_record['parent'];

              // if no publication has been created so far
              if ($key == "hcms_empty" && !valid_publicationname ($site_name))
              {
                $site_name = "hcms_empty";
                $site_admin = true;

                // deseralize the permission string and define root, global and local permissions
                if (!isset ($permission_str)) $permission_str = array();

                if (valid_publicationname ($site_name) && !empty ($group_name_admin))
                {
                  if (!isset ($permission_str[$site_name])) $permission_str[$site_name] = array();
                  $permission_str[$site_name][$group_name_admin] = $permission_str_admin;
                }

                $result['siteaccess'][$site_name] = $site_name;
                $result['rootpermission'] = rootpermission ($site_name, $site_admin, $permission_str);
 
                break;
              }
              // include configuration of site
              elseif (valid_publicationname ($site_name) && is_file ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php"))
              {
                @require_once ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php");

                $site_collection .= "|".$site_name;

                if (!isset ($result['hiddenfolder'][$site_name])) $result['hiddenfolder'][$site_name] = array();

                // define array of excluded/hidden folders
                if (!empty ($mgmt_config[$site_name]['exclude_folders']))
                {
                  if (substr_count ($mgmt_config[$site_name]['exclude_folders'], ";") > 0)
                  {
                    $result['hiddenfolder'][$site_name] = splitstring ($mgmt_config[$site_name]['exclude_folders']);
                  }
                  else
                  {
                    $result['hiddenfolder'][$site_name] = array ($mgmt_config[$site_name]['exclude_folders']);
                  }
                }
              }

              // access permissions to publications and display name of publication
              $result['siteaccess'][$site_name] = !empty ($mgmt_config[$site_name]['displayname']) ? $mgmt_config[$site_name]['displayname'] : $site_name;

              // access permissions to asset/component folders
              if (valid_publicationname ($site_name) && !empty ($group_name_admin))
              {
                // initialize compaccess array
                if (!isset ($result['compaccess'][$site_name])) $result['compaccess'][$site_name] = array();

                $result['compaccess'][$site_name][$group_name_admin] = deconvertpath ("%comp%/".$site_name."/|", "file");
              }

              // access permissions to page folders
              if (valid_publicationname ($site_name) && !empty ($group_name_admin) && empty ($mgmt_config[$site_name]['dam']) && !empty ($mgmt_config[$site_name]['abs_path_page']))
              {
                // initialize pageaccess array
                if (!isset ($result['pageaccess'][$site_name])) $result['pageaccess'][$site_name] = array();

                $result['pageaccess'][$site_name][$group_name_admin] = deconvertpath ("%page%/".$site_name."/|", "file");
              }

              // access permissions to plugins
              if ($user != "hcms_download" && file_exists ($mgmt_config['abs_path_data']."config/plugin.global.php"))
              {
                require_once ($mgmt_config['abs_path_data']."config/plugin.global.php");

                if (!empty ($mgmt_plugin) && is_array ($mgmt_plugin))
                {
                  foreach ($mgmt_plugin as $key => $data)
                  {
                    if ($key != "") $result['pluginaccess'][$site_name][] = $key;
                  }
                }
              }

              // deseralize the permission string and define root, global and local permissions
              if (valid_publicationname ($site_name) && !empty ($group_name_admin))
              {
                if (empty ($permission_str[$site_name])) $permission_str[$site_name] = array();
                $permission_str[$site_name][$group_name_admin] = $permission_str_admin;
              }

              // set group names for each publication
              $usergroups[$site_name] = array ($group_name_admin);

              if (isset ($permission_str[$site_name][$group_name_admin]))
              {
                $result['rootpermission'] = rootpermission ($site_name, $site_admin, $permission_str);
                $globalpermission_new = globalpermission ($site_name, $permission_str);
                $localpermission_new = localpermission ($site_name, $permission_str);

                if ($globalpermission_new != false)
                {
                  $result['globalpermission'] = array_replace_recursive ($result['globalpermission'], $globalpermission_new);
                }

                if ($localpermission_new != false)
                {
                  $result['localpermission'] = array_replace_recursive ($result['localpermission'], $localpermission_new);
                }
              } 
            }
          }

          $auth = true;
        }
        // other users
        else
        {
          if (isset ($memberofnode) && is_array ($memberofnode))
          {
            $site_collection = "";

            foreach ($memberofnode as $memberof)
            {
              $site_node = getcontent ($memberof, "<publication>");
              $site_name = $site_node[0];
              $usergroup = getcontent ($memberof, "<usergroup>");
              $group_string = $usergroup[0];

              $site_collection .= "|".$site_name; 

              // load usergroup information
              $usergroupdata = loadfile ($mgmt_config['abs_path_data']."user/", $site_name.".usergroup.xml.php");

              // include configuration of site
              if (valid_publicationname ($site_name) && is_file ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php"))
              {
                require_once ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php");

                // access permissions to publications and display name of publication
                $result['siteaccess'][$site_name] = !empty ($mgmt_config[$site_name]['displayname']) ? $mgmt_config[$site_name]['displayname'] : $site_name;

                // hidden folders
                if (!isset ($result['hiddenfolder'][$site_name])) $result['hiddenfolder'][$site_name] = array();

                // define array of excluded/hidden folders
                if (!empty ($mgmt_config[$site_name]['exclude_folders']))
                {
                  if (substr_count ($mgmt_config[$site_name]['exclude_folders'], ";") > 0)
                  {
                    $result['hiddenfolder'][$site_name] = explode (";", $mgmt_config[$site_name]['exclude_folders']);
                  }
                  else
                  {
                    $result['hiddenfolder'][$site_name] = array ($mgmt_config[$site_name]['exclude_folders']);
                  }
                }

                if (!empty ($usergroupdata) && strlen ($group_string) > 0)
                {
                  // user group names as array
                  if (strpos ("_".$group_string, "|") > 0) $group_array = explode ("|", trim ($group_string, "|"));
                  else $group_array = array ($group_string);

                  // if object linking is used assign group "default" if it exists
                  // user must have at least one group assigned to have access to the system!
                  if (isset ($result['hcms_linking']) && is_array ($result['hcms_linking']) && sizeof ($result['hcms_linking']) > 0)
                  {
                    $defaultgroup = selectcontent ($usergroupdata, "<usergroup>", "<groupname>", "default");

                    if (!empty ($defaultgroup[0]) && !in_array ("default", $group_array))
                    {
                      $group_array[] = "default";
                    }
                  }

                  // set group names for each publication
                  $usergroups[$site_name] = $group_array;

                  if (is_array ($group_array) && sizeof ($group_array) > 0)
                  {
                    // get the permissions of the group
                    foreach ($group_array as $group_name)
                    {
                      if ($group_name != "")
                      {
                        // get usergroup information
                        $usergroupnode = selectcontent ($usergroupdata, "<usergroup>", "<groupname>", $group_name);

                        if (!empty ($usergroupnode[0]))
                        {
                          $userpermission = getcontent ($usergroupnode[0], "<permission>");
                          $userpageaccess = getcontent ($usergroupnode[0], "<pageaccess>");
                          $usercompaccess = getcontent ($usergroupnode[0], "<compaccess>");
                          $userpluginccess = getcontent ($usergroupnode[0], "<plugins>");

                          if (!empty ($userpermission[0]))
                          {
                            if (!isset ($permission_str)) $permission_str = array();
                            if (!isset ($permission_str[$site_name])) $permission_str[$site_name] = array();
                            $permission_str[$site_name][$group_name] = trim ($userpermission[0]);
                          }
                          else
                          {
                            $permission_str = NULL;
                          }

                          // page accsess
                          if (!isset ($result['pageaccess'][$site_name])) $result['pageaccess'][$site_name] = array();
                          $result['pageaccess'][$site_name][$group_name] = NULL;

                          if (!empty ($userpageaccess[0]))
                          {
                            // versions before 5.6.3 used folder path instead of object id
                            if (substr_count ($userpageaccess[0], "/") == 0)
                            {
                              $temp_array = explode ("|", $userpageaccess[0]);
        
                              if (is_array ($temp_array))
                              {
                                $folder_path = "";
          
                                foreach ($temp_array as $temp)
                                {
                                  if ($temp != "")
                                  {
                                    $temp_path = rdbms_getobject ($temp);
                                    if ($temp_path != "") $folder_path .= getlocation ($temp_path)."|";
                                  }
                                }
                              }
                            }
                            else $folder_path = $userpageaccess[0];

                            $result['pageaccess'][$site_name][$group_name] = deconvertpath ($folder_path, "file");
                          }

                          // component access
                          if (!isset ($result['compaccess'][$site_name])) $result['compaccess'][$site_name] = array();
                          $result['compaccess'][$site_name][$group_name] = NULL;

                          if (!empty ($usercompaccess[0]))
                          {
                            // versions before 5.6.3 used folder path instead of object id
                            if (substr_count ($usercompaccess[0], "/") == 0)
                            {
                              $temp_array = explode ("|", $usercompaccess[0]);
        
                              if (is_array ($temp_array))
                              {
                                $folder_path = "";

                                foreach ($temp_array as $temp)
                                {
                                  if ($temp != "")
                                  {
                                    $temp_path = rdbms_getobject ($temp);
                                    if ($temp_path != "") $folder_path .= getlocation ($temp_path)."|";
                                  }
                                }
                              }
                            }
                            else $folder_path = $usercompaccess[0];

                            $result['compaccess'][$site_name][$group_name] = deconvertpath ($folder_path, "file");
                          }

                          // plugin access
                          if (!isset ($result['pluginaccess'][$site_name])) $result['pluginaccess'][$site_name] = array();

                          if (!empty ($userpluginccess[0]))
                          {
                            $temp_array = link_db_getobject ($userpluginccess[0]);

                            // merge
                            $result['pluginaccess'][$site_name] = array_merge ($result['pluginaccess'][$site_name], $temp_array);
                          }

                          // deseralize the permission string and define root, global and local permissions
                          if (isset ($permission_str[$site_name][$group_name]))
                          {
                            $result['rootpermission'] = rootpermission ($site_name, $mgmt_config[$site_name]['site_admin'], $permission_str);
                            $globalpermission_new = globalpermission ($site_name, $permission_str);
                            $localpermission_new = localpermission ($site_name, $permission_str);

                            if ($globalpermission_new != false)
                            {
                              $result['globalpermission'] = array_replace_recursive ($result['globalpermission'], $globalpermission_new);
                            }
      
                            if ($localpermission_new != false)
                            {
                              $result['localpermission'] = array_replace_recursive ($result['localpermission'], $localpermission_new);
                            }
                          }
                        }
                      }
                    }
                  }

                  $auth = true;
                }
              }
            }
          }
        }
      }
    }
  }

  // in case a user hash code has been provided
  if (empty ($user)) $user = $fileuser;

  // --------------------- verify key ---------------------

  if (!empty ($auth))
  {
    // check disk key
    $result['keyserver'] = checkdiskkey ();

    // first time logon
    if (is_file ($mgmt_config['abs_path_data']."check.dat"))
    {
      // load check.dat
      $check = loadfile ($mgmt_config['abs_path_data'], "check.dat");

      // initial load
      if (trim ($check) == "")
      {
        // save installation date
        $checkresult = savefile ($mgmt_config['abs_path_data'], "check.dat", date ("Y-m-d", time()));

        // log information
        if ($checkresult == true)
        {
          $errcode = "00221";
          $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|information|".$errcode."|hyperCMS started first time by publication: ".$site_name;
        }
        // on error
        else
        {
          $errcode = "10221";
          $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|error|".$errcode."|check.dat could not be saved";
        }

        $mailer = new HyperMailer();
        $mailer->AddAddress ("info@hypercms.net");
        $mailer->Subject = "hyperCMS Started First Time";
        $mailer->Body = "hyperCMS started first time by ".$mgmt_config['url_path_cms']." (".(!empty ($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : "unknown IP")."), License key: ".$mgmt_config['diskkey']."\n";
        $mailer->Send();

        mail ("info@hypercms.net", "hyperCMS Started First Time", "hyperCMS started first time by ".$mgmt_config['url_path_cms']." (".(!empty ($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : "unknown IP")."), License key: ".$mgmt_config['diskkey']."\n");
      }
      // installation date has been set
      else
      {
        $checkresult = true;
      }

      if (empty ($result['keyserver']) && (is_dir ($mgmt_config['abs_path_cms']."connector") || is_dir ($mgmt_config['abs_path_cms']."project") || is_dir ($mgmt_config['abs_path_cms']."report") || is_dir ($mgmt_config['abs_path_cms']."task") || is_dir ($mgmt_config['abs_path_cms']."webdav") || is_dir ($mgmt_config['abs_path_cms']."workflow")))
      {
        $mailer = new HyperMailer();
        $mailer->AddAddress ("info@hypercms.net");
        $mailer->Subject = "hyperCMS License Alert";
        $mailer->Body = "License limit exceeded by ".$mgmt_config['url_path_cms']." (".(!empty ($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : "unknown IP")."), Publications: ".str_replace ("|", ", ", trim ($site_collection, "|"))."\n";
        $mailer->Send();

        // deletefile ($mgmt_config['abs_path_data'], "check.dat", 0);
        $result['message'] = $hcms_lang['your-action-is-not-confirm-to-the-license-agreement'][$lang]." <a href=\"mailto:support@hypercms.net\">support@hypercms.net</a>";
        $checkresult = false;

        // warning
        $errcode = "00222";
        $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|warning|".$errcode."|License limits exceeded";
      }
    }
    // file is missing
    else
    {
      $mailer = new HyperMailer();
      $mailer->AddAddress ("info@hypercms.net");
      $mailer->Subject = "hyperCMS ALERT";
      $mailer->Body = "hyperCMS alert (check.dat is missing) for ".$mgmt_config['url_path_cms']."\n";
      $mailer->Send();
      $result['message'] = $hcms_lang['your-action-is-not-confirm-to-the-license-agreement'][$lang]." <a href=\"mailto:support@hypercms.net\">support@hypercms.net</a>";
      $checkresult = false;

      // warning
      $errcode = "00223";
      $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|warning|".$errcode."|check.dat is missing for ".$mgmt_config['url_path_cms']; 
    }
  }

  // --------------------- authentication result --------------------- 

  // combined results
  // $ldap_auth result has been removed in version 9.0.6 in order to allow access for external users
  $result['auth'] = ($linking_auth && $auth && $checkresult);

  if ($result['auth'] == false)
  {
    // warning
    $errcode = "00333";
    $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|warning|".$errcode."|".($is_webdav ? "WebDAV: " : "")."Authorization failed with results for user '".$user."': user-access-link=".$linking_auth.", user-credentials=".$auth.", valid-user-dates=".$validdate.", check.dat=".$checkresult." (all must have a value of 1) and user-nologon=".(!empty ($result['nologon']) ? $result['nologon'] : 0)." (must be 0)";
  }

  // --------------------------- security ----------------------------

  // get client IP address
  $client_ip = getuserip();

  // count failed login attempts of same client IP address
  if ($locking == true && $result['auth'] == false)
  {
    // reset session array
    if (!isset ($_SESSION['temp_ip_counter']) || !is_array ($_SESSION['temp_ip_counter'])) $_SESSION['temp_ip_counter'] = array();

    // if ip/user is not already locked
    if (checkuserip ($client_ip, $user))
    {
      // access counter
      if (isset ($_SESSION['temp_ip_counter'][$user]) && $_SESSION['temp_ip_counter'][$user] > 0) $_SESSION['temp_ip_counter'][$user] = $_SESSION['temp_ip_counter'][$user] + 1;
      else $_SESSION['temp_ip_counter'][$user] = 1;

      // log client ip after 10 failed attempts
      if ($_SESSION['temp_ip_counter'][$user] > 9)
      {
        loguserip ($client_ip, $user);

        // warning
        $errcode = "00101";
        $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|warning|".$errcode."|".($is_webdav ? "WebDAV: " : "")."Client IP ".$client_ip." is banned due to 10 failed logon attempts";
 
        // reset counter
        $_SESSION['temp_ip_counter'][$user] = 1;
      }
    }
    else $result['message'] = $hcms_lang['you-have-been-banned'][$lang];
  }

  // --------------------- GUI definitions (chat, columns, views) ---------------------

  if ($result['auth'] == true)
  {
    // read colum defintions of user
    $columns = array();

    // read labels of templates
    $labels = array();

    if (is_file ($mgmt_config['abs_path_data']."checkout/".$user.".objectlistcols.json"))
    {
      // load objectlist definition file
      $temp_json = loadfile_fast ($mgmt_config['abs_path_data']."checkout/", $user.".objectlistcols.json");

      // JSON decode the string
      if ($temp_json != "") $columns = json_decode ($temp_json, true);
    }

    if (is_array ($result['siteaccess']) && sizeof ($result['siteaccess']) > 0)
    {
      $result['siteaccess'] = array_unique ($result['siteaccess']);
      natcasesort ($result['siteaccess']);

      foreach ($result['siteaccess'] as $temp_site)
      {
        // set default values
        if (!isset ($columns[$temp_site]))
        {
          $columns[$temp_site]['page'] = array('modifieddate'=>1, 'filesize'=>1, 'type'=>1);
          $columns[$temp_site]['comp'] = array('modifieddate'=>1, 'filesize'=>1, 'type'=>1);
          $update_columns = true;
        }

        // read and set labels in templates for objectlist and metadata views
        $labels[$temp_site] = array();

        // get all templates excluding template includes
        $template_array = gettemplates ($temp_site, "all");

        if (is_array ($template_array) && sizeof ($template_array) > 0)
        {
          foreach ($template_array as $template)
          {
            // load template and define labels
            if ($template != "")
            {
              if ($temp_site != "" && $template != "")
              {
                // get category
                if (strpos ($template, ".page.tpl") > 0) $temp_cat = "page";
                else $temp_cat = "comp";

                $temp_template = loadtemplate ($temp_site, $template);

                if ($temp_template['content'] != "")
                {
                  $hypertag_array = gethypertag ($temp_template['content'], "text", 0);

                  if (is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
                  {
                    foreach ($hypertag_array as $tag)
                    {
                      $temp_id = getattribute ($tag, "id");
                      $temp_label = getattribute ($tag, "label");
                      $temp_groups = getattribute ($tag, "groups");

                      // user has access to the content
                      if (empty ($temp_groups) || checkgroupaccess ($temp_groups, $usergroups[$temp_site]))
                      {
                        // define label name based on label of tag
                        if ($temp_id != "" && trim ($temp_label) != "") $labels[$temp_site][$temp_cat]['text:'.$temp_id] = $temp_label;
                        // or use text ID
                        elseif ($temp_id != "") $labels[$temp_site][$temp_cat]['text:'.$temp_id] = ucfirst (str_replace ("_", " ", $temp_id));
                      }
                      // user has no access to the content
                      else
                      {
                        // remove column
                        if (!empty ($columns[$temp_site][$temp_cat]['text:'.$temp_id]))
                        {
                          unset ($columns[$temp_site][$temp_cat]['text:'.$temp_id]);
                          $update_columns = true;
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }

      // save objectlist definition file with new default values
      if (is_array ($columns) && !empty ($update_columns))
      {
        savefile ($mgmt_config['abs_path_data']."checkout/", $user.".objectlistcols.json", json_encode ($columns));
      }
    }

    // set columns definitions for result
    $result['objectlistcols'] = $columns;

    // set labels definitions for result
    $result['labels'] = $labels;

    // get default GUI settings
    $result['objectview'] = $mgmt_config['objectview'];
    $result['explorerview'] = $mgmt_config['explorerview'];
    $result['sidebar'] = $mgmt_config['sidebar'];

    // get users GUI settings
    if (is_file ($mgmt_config['abs_path_data']."checkout/".$user.".gui.dat"))
    {
      // load gui definition file
      $temp_dat = loadfile ($mgmt_config['abs_path_data']."checkout/", $user.".gui.dat");

      if (!empty ($temp_dat)) list ($result['objectview'], $result['explorerview'], $result['sidebar']) = explode ("|", $temp_dat);
    }

    // get users toolbar settings
    if (!empty ($mgmt_config['toolbar_functions']) && is_file ($mgmt_config['abs_path_data']."checkout/".$user.".toolbar.json"))
    {
      // load gui definition file
      $temp_dat = loadfile ($mgmt_config['abs_path_data']."checkout/", $user.".toolbar.json");

      if (!empty ($temp_dat)) $result['toolbarfunctions'] = json_decode ($temp_dat, true);
    }
  }

  // detect mobile browsers
  $result['mobile'] = is_mobilebrowser ();

  // message
  if (empty ($result['message']))
  {
    if (!empty ($result['auth'])) $result['message'] = $hcms_lang['login-correct'][$lang];
    else $result['message'] = $hcms_lang['login-incorrect'][$lang];
  }

  // log
  if ($result['auth'] == true)
  { 
    // information
    $errcode = "00102";
    $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|information|".$errcode."|".($is_webdav ? "WebDAV: " : "")."User '".$user."' with client IP ".$client_ip." is logged in";
  }
  else
  {
    // information
    $errcode = "00103";
    $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|information|".$errcode."|".($is_webdav ? "WebDAV: " : "")."User '".$user."' with client IP ".$client_ip." failed to login";
  }

  // --------------------- chat --------------------- 

  // chat relations file
  $chat_relations_log = $mgmt_config['abs_path_temp']."/chat_relations.php";

  if ($result['auth'] == true)
  {
    // state of chat
    $result['chatstate'] = getchatstate (false);

    // clear user as host in chat relationships
    if (is_file ($chat_relations_log))
    {
      // load chat relations
      $chat_relations = file_get_contents ($chat_relations_log);

      if (!empty ($chat_relations))
      {
        $chat_relations_array = unserialize ($chat_relations);

        if (!empty ($chat_relations_array[$user]))
        {
          unset ($chat_relations_array[$user]);
          $chat_relations = serialize ($chat_relations_array);
          if (!empty ($chat_relations)) file_put_contents ($chat_relations_log, $chat_relations);
        }
      }
    }
  }

  // --------------------- reset permissions for portal --------------------- 

  // if a portal access link is used we withdraw all permissions except for favorites, assets, and pages
  if (!empty ($result['portal']))
  {
    $result = setportalpermissions ($result);
  }

  // --------------------- permission checksum --------------------- 

  // calculate checksum of permissions
  if (isset ($_SESSION['hcms_instance'])) $result['instance'] = $_SESSION['hcms_instance'];
  else $result['instance'] = false;

  $result['checksum'] = createchecksum (array ($result['instance'], $result['superadmin'], $result['siteaccess'], $result['pageaccess'], $result['compaccess'], $result['rootpermission'], $result['globalpermission'], $result['localpermission']));

  // eventsystem
  if (!empty ($eventsystem['onlogon_post']) && empty ($eventsystem['hide']) && function_exists ("onlogon_post"))
  {
    $temp = onlogon_post ($user, $result);
    if (is_array ($temp)) $result = $temp;
  }

  // save log
  savelog (@$error);

  // replace placeholder with value
  if (!empty ($result['message'])) $result['message'] = str_replace ("%timeout%", $mgmt_config['logon_timeout'], $result['message']);

  return $result;
}

// ---------------------- setportalpermissions -----------------------------
// function: setportalpermissions()
// input: result from function userlogin [array]
// output: result array / false
// requires: hypercms_api.inc.php

// description:
// Sets the permissions for a portal user by reducing the standard permissions.

function setportalpermissions ($login_result)
{
  global $mgmt_config;

  if (is_array ($login_result))
  {
    // reset root permissions
    foreach ($login_result['rootpermission'] as $temp_name => $temp_value)
    {
      if ($temp_name != "desktopfavorites") $login_result['rootpermission'][$temp_name] = 0;
    }

    // reset global permissions
    foreach ($login_result['globalpermission'] as $temp_site => $temp_permission)
    {
      foreach ($temp_permission as $temp_name => $temp_value)
      {
        if (($temp_name != "component" && $temp_name != "page") || empty ($mgmt_config[$temp_site]['portalaccesslink'])) $login_result['globalpermission'][$temp_site][$temp_name] = 0;
      }
    }

    return $login_result;
  }
  else return false;
}

// ---------------------- registerinstance -----------------------------
// function: registerinstance()
// input: instance name [string], load main config of instance [boolean] (optional)
// output: true/false
// requires: hypercms_api.inc.php

// description:
// Registers the instance in the users session.

function registerinstance ($instance, $load_config=true)
{
  global $mgmt_config;

  if (!empty ($mgmt_config['instances']) && $instance != "")
  {
    // create session if it does not exist
    createsession ();

    if (valid_publicationname ($instance) && is_file ($mgmt_config['instances'].$instance.".inc.php"))
    {
      // load management configuration of instance
      if ($load_config) require ($mgmt_config['instances'].$instance.".inc.php");

      return setsession ('hcms_instance', $instance);
    }
  }

  return false;
}

// ---------------------- registerservice -----------------------------
// function: registerservice()
// input: service name [string], user name [string]
// output: service hash as string / false on error
// requires: hypercms_api.inc.php

// description:
// Registers the service of a standard user and returns the service hash.

function registerservice ($servicename, $user)
{
  global $mgmt_config;

  if (valid_objectname ($servicename) && valid_objectname ($user))
  {
    // create unique hash
    $servicehash = createuniquetoken (16);

    // save service file
    $data = $mgmt_config['today']."|".$servicename."|".$user;
    
    if (savefile ($mgmt_config['abs_path_data']."session/", $servicename.".".$servicehash.".dat", $data))
    {
      // create session if it does not exist
      createsession ();

      // save service in users session
      setsession ('hcms_services', array($servicename => array($servicehash)));

      return $servicehash;
    }
  }

  return false;
}

// ------------------------- registerserviceuser -----------------------------
// function: registerserviceuser()
// input: service name [string], 16 digits service hash [string]
// output: system service user name / false

// description:
// Registers a system service user "sys:service-name:16-digit-servicehash" in the session. 

function registerserviceuser ($servicename, $servicehash)
{
  global $mgmt_config;

  if ($servicename != "" && $servicehash != "")
  {
    // create session if it does not exist
    createsession ();

    // create system service user name "sys:service-name:16-digit-servicehash"
    $user = "sys:".$servicename.":".$servicehash;

    // register user name and the IP address
    setsession ('hcms_user', $user);
    setsession ('hcms_user_ip', getuserip());

    return $user;
  }
  else return false;
}

// ---------------------- registeruser -----------------------------
// function: registeruser()
// input: instance name [string] (optional), result array of function userlogin [array], access link [array] (optional), download formats of access link provided by function rdbms_getaccessinfo [array] (optional), mobile browser result of client [0,1] (optional), is iOS browser result of client [0,1] (optional), HTML5 file support result of client [0,1] (optional)
// output: result array / false on error
// requires: hypercms_api.inc.php

// description:
// Registers all user related paramaters in the session. Access links can be provided with the login result or alternatively as the seperate accesslink parameter.

function registeruser ($instance="", $login_result=array(), $accesslink=false, $hcms_objformats=false, $is_mobile=0, $is_iphone=0, $html5support=1)
{
  global $mgmt_config, $hcms_lang, $lang;

  if (!empty ($mgmt_config) && !empty ($login_result['auth']))
  {	
    // create session if it doesn not exist
    createsession ();

    // regenerate session id after successful logon
    session_regenerate_id ();

    // register instance in session without loading main config
    registerinstance ($instance, false);

    // register root, global and local pemissions
    if (!empty ($login_result['rootpermission']))
    {
      setsession ('hcms_rootpermission', $login_result['rootpermission']);
    }

    if (!empty ($login_result['globalpermission']))
    {
      setsession ('hcms_globalpermission', $login_result['globalpermission']);
    }

    if (!empty ($login_result['localpermission']))
    {
      setsession ('hcms_localpermission', $login_result['localpermission']);
    }

    // register values in the session
    setsession ('hcms_user', $login_result['user']);
    setsession ('hcms_passwd', md5 ($login_result['passwd']));
    setsession ('hcms_realname', $login_result['realname']);
    setsession ('hcms_email', $login_result['email']);
    setsession ('hcms_siteaccess', $login_result['siteaccess']);
    setsession ('hcms_pageaccess', $login_result['pageaccess']);
    setsession ('hcms_compaccess', $login_result['compaccess']);
    setsession ('hcms_pluginaccess', $login_result['pluginaccess']);
    setsession ('hcms_superadmin', $login_result['superadmin']);
    setsession ('hcms_lang', $login_result['lang']);
    setsession ('hcms_timezone', $login_result['timezone']);
    setsession ('hcms_hiddenfolder', $login_result['hiddenfolder']);

    // register download formats in case of an access link (1st priority) or portal access link (2nd priority)
    if (!empty ($hcms_objformats)) setsession ('hcms_downloadformats', $hcms_objformats);
    elseif (!empty ($login_result['downloadformats'])) setsession ('hcms_downloadformats', $login_result['downloadformats']);

    // reset mobile settings by values of client side browser detection (JavaScript)
    if (is_mobilebrowser () || $is_mobile == 1 || $is_mobile == "yes")
    {
      $login_result['mobile'] = true;
    }
    else $login_result['mobile'] = false;

    // iphone setting
    if (is_iOS() || $is_iphone == 1 || $is_iphone == "yes")
    {
      $login_result['iphone'] = true;
    }
    else $login_result['iphone'] = false;

    // portal
    setsession ('hcms_portal', $login_result['portal']);

    // register design theme settings
    setsession ('hcms_themename', $login_result['themename']);
    setsession ('hcms_themelocation', getthemelocation ($login_result['themename']));
    setsession ('hcms_themeinvertcolors', $login_result['themeinvertcolors']);
    setsession ('hcms_hoverinvertcolors', $login_result['hoverinvertcolors']);
    setsession ('hcms_mainnavigation', $login_result['mainnavigation']);

    // register permanent view settings
    setsession ('hcms_mobile', $login_result['mobile']);
    setsession ('hcms_iphone', $login_result['iphone']);

    // register temporary GUI view settings
    setsession ('hcms_temp_explorerview', $login_result['explorerview']);
    setsession ('hcms_temp_objectview', $login_result['objectview']);
    setsession ('hcms_temp_sidebar', $login_result['sidebar']);

    // register chat state after logon
    setsession ('hcms_temp_chatstate', $login_result['chatstate']);

    // register HTML5 file support in session
    setsession ('hcms_html5file', $html5support);

    // register server feedback
    setsession ('hcms_keyserver', $login_result['keyserver']);

    // register current timestamp in session
    setsession ('hcms_temp_sessiontime', time());

    // register objectlist column defintions
    setsession ('hcms_objectlistcols', $login_result['objectlistcols']);

    // register template label defintions
    setsession ('hcms_labels', $login_result['labels']);
    
    // register toolbar function defintions
    setsession ('hcms_toolbarfunctions', $login_result['toolbarfunctions']);

    // set object linking information in session provided as element in login_result
    if (!empty ($login_result['hcms_linking']) && is_array ($login_result['hcms_linking']) && sizeof ($login_result['hcms_linking']) > 0)
    {
      setsession ('hcms_linking', $login_result['hcms_linking']);
      setsession ('hcms_temp_explorerview', "medium");
    }
    // provided accesslink
    elseif (!empty ($accesslink['hcms_linking']) && is_array ($accesslink['hcms_linking']) && sizeof ($accesslink['hcms_linking']) > 0)
    {
      setsession ('hcms_linking', $accesslink['hcms_linking']);
      setsession ('hcms_temp_explorerview', "medium");
    }
    else
    {
      setsession ('hcms_linking', NULL);
    }

    // write hypercms session file (as a backup for the recreation)
    $login_result['writesession'] = writesession ($login_result['user'], $login_result['passwd'], $login_result['checksum'], $login_result['siteaccess']);

    // session info could not be saved
    if ($login_result['writesession'] == false)
    {
      $login_result['message'] = getescapedtext ($hcms_lang['session-information-could-not-be-saved'][$lang]);
    }

    return $login_result;
  }
  else return false;
}

// ---------------------- registerassetbrowser -----------------------------
// function: registerassetbrowser()
// input: user hash [string], object hash [string] (optional)
// output: true/false
// requires: hypercms_api.inc.php

function registerassetbrowser ($userhash, $objecthash="")
{
  global $mgmt_config;

  // user hash is provided for the assetbrowser or object access links
  if (!empty ($userhash))
  {
    // create session if it doesn not exist
    createsession ();

    // set assetbrowser mode information in session
    setsession ('hcms_assetbrowser', true);

    // set assetbrowser location and object in session
    if (!empty ($objecthash))
    {
      $objectpath = rdbms_getobject ($objecthash);

      if (!empty ($objectpath))
      {
        setsession ('hcms_assetbrowser_location', getlocation ($objectpath));
        setsession ('hcms_assetbrowser_object', getobject ($objectpath));
      }
    }

    // reset temporary view settings
    setsession ('hcms_temp_explorerview', "small");
    setsession ('hcms_temp_sidebar', true, true);

    return true;
  }
  else return false;
}

// ---------------------- createchecksum -----------------------------
// function: createchecksum()
// input: array or empty [array]
// output: MD5 checksum
// requires: hypercms_api.inc.php

// description:
// Creates the checksum of the user permissions.

function createchecksum ($permissions="")
{
  if (is_array ($permissions))
  {
    return $checksum = md5 (makestring ($permissions));
  }
  else
  {
    if (!isset ($_SESSION['hcms_instance'])) $_SESSION['hcms_instance'] = false;
    if (!isset ($_SESSION['hcms_superadmin'])) $_SESSION['hcms_superadmin'] = false;
    if (!isset ($_SESSION['hcms_siteaccess'])) $_SESSION['hcms_siteaccess'] = false;
    if (!isset ($_SESSION['hcms_pageaccess'])) $_SESSION['hcms_pageaccess'] = false;
    if (!isset ($_SESSION['hcms_compaccess'])) $_SESSION['hcms_compaccess'] = false;
    if (!isset ($_SESSION['hcms_rootpermission'])) $_SESSION['hcms_rootpermission'] = false;
    if (!isset ($_SESSION['hcms_globalpermission'])) $_SESSION['hcms_globalpermission'] = false;
    if (!isset ($_SESSION['hcms_localpermission'])) $_SESSION['hcms_localpermission'] = false;

    $permissions = array ($_SESSION['hcms_instance'], $_SESSION['hcms_superadmin'], $_SESSION['hcms_siteaccess'], $_SESSION['hcms_pageaccess'], $_SESSION['hcms_compaccess'], $_SESSION['hcms_rootpermission'], $_SESSION['hcms_globalpermission'], $_SESSION['hcms_localpermission']);

    return $checksum = md5 (makestring ($permissions));
  }
}

// ---------------------- writesession -----------------------------
// function: writesession()
// input: user name [string], password [string], checksum [string], publicaion access [array]
// output: true / false on error
// requires: hypercms_api.inc.php

// description:
// Writes hyperCMS specific session data of a user.

function writesession ($user, $passwd, $checksum, $siteaccess=array())
{
  global $mgmt_config;

  // initialize
  $error = array();

  if ((session_id() != "" || !isset ($_SESSION)) && valid_objectname ($user) && $passwd != "" && $checksum != "")
  {
    // write session data for the load balancing
    writesessiondata ();

    // timestamp
    $sessiontime = time();

    // session string
    $sessiondata = session_id()."|".$sessiontime."|".md5 ($passwd)."|".$checksum."|".implode(":", array_keys($siteaccess))."\n";

    // if user session file exists (user didn't log out or same user logged in a second time)
    if (is_file ($mgmt_config['abs_path_data']."session/".$user.".dat") && !empty ($mgmt_config['userconcurrent']))
    {
     // write session file
      $test = appendfile ($mgmt_config['abs_path_data']."session/", $user.".dat", $sessiondata);

      if ($test != false)
      {
        return true;
      }
      else 
      {
        $errcode = "10108";
        $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|error|".$errcode."|appendfile failed for user $user on /data/session/".$user.".dat";

        // save log
        savelog (@$error);

        return false;
      }
    }
    // if user session file does not exist (user logged out correctly) or no concurrent use of a user account enabled
    else
    {
      // write session file
      $test = savefile ($mgmt_config['abs_path_data']."session/", $user.".dat", $sessiondata);

      if ($test != false)
      {
        return true;
      }
      else 
      {
        $errcode = "10109";
        $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|error|".$errcode."|savefile failed for user $user on /data/session/".$user.".dat";

        // save log
        savelog (@$error); 

        return false;
      }
    }
  }
  else return false;
}


// ---------------------- writesessiondata -----------------------------
// function: writesessiondata()
// input: %
// output: true / false on error
// requires: hypercms_api.inc.php

// description:
// Serializes and writes all session data of a user.

function writesessiondata ()
{
  global $mgmt_config;

  // session Id and user name must exist in session
  if (valid_objectname (session_id()) && getsession ('hcms_user') != "")
  {
    // write session data for load balancer (third party load balancer or hyperCMS load balancer)
    if (!empty ($mgmt_config['writesessiondata']) || (!empty ($mgmt_config['url_path_service']) && is_array ($mgmt_config['url_path_service']) && sizeof ($mgmt_config['url_path_service']) > 0))
    {
      // register current timestamp in session
      setsession ('hcms_temp_sessiontime', time());
 
      // serialize session data
      $session_data = session_encode ();
      savefile ($mgmt_config['abs_path_data']."session/", session_id().".dat", $session_data);

      // save session data
      if ($session_data != "") return savefile ($mgmt_config['abs_path_data']."session/", session_id().".dat", $session_data);
      else return false;
    }
    else return true;
  }
  else return false;
}

// ------------------------- createsession -----------------------------
// function: createsession()
// input: session name [string] (optional), session ID [string] (optional)
// output: true

// description:
// Creates a session for the user. This function accesses session variables directly.

function createsession ($name="hyperCMS", $session_id="")
{
  global $mgmt_config;

  // initialize
  $error = array();

  // check user session and set session ID if required
  if (!empty ($_REQUEST['PHPSESSID']))
  {
    session_id ($_REQUEST['PHPSESSID']);
  }

  // start session
  if ((session_id() == "" || !isset ($_SESSION)) && !headers_sent())
  {
    if (!empty ($name) || session_name() != $name) session_name ($name);
    if (!empty ($session_id)) session_id ($session_id);
    session_start ();
  }

  // session is not valid or data directory is missing
  if (session_id() == "" || !isset ($_SESSION))
  {
    $errcode = "10401";
    $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|error|".$errcode."|Session could not be created or session ID is invalid";

    // save log
    savelog (@$error);
  }
  // load balancer is used: if a valid session ID is provided, evalute session and copy session data if required
  elseif (!empty ($mgmt_config['abs_path_data']) && (!empty ($mgmt_config['url_path_service']) || !empty ($mgmt_config['writesessiondata'])))
  {
    // define session file (a session file is required if the load balancer is used)
    $session_file = $mgmt_config['abs_path_data']."session/".session_id().".dat";

    $session_time = (!empty ($_SESSION['hcms_temp_sessiontime']) ? $_SESSION['hcms_temp_sessiontime'] : 0);

    // check if session file exists and is newer than the existing session data
    if (is_file ($session_file) && filemtime ($session_file) > $session_time)
    {
      // load session information
      $data = @file_get_contents ($session_file);

      // decode session data and register variables in session
      if ($data != "") session_decode ($data);

      // check hyperCMS user session file
      if (!empty ($_SESSION['hcms_user']) && !empty ($_SESSION['hcms_passwd']) && is_file ($mgmt_config['abs_path_data']."session/".$_SESSION['hcms_user'].".dat"))
      {
        // check session of user
        checkusersession ($_SESSION['hcms_user']);
      }
      // kill users session since there is no hyperCMS session file
      elseif (!empty ($_SESSION['hcms_user']))
      {
        killsession ($_SESSION['hcms_user']);
      }
      // kill session
      else
      {
        killsession ();
      }
    }
  }

  return true;
}

// ---------------------- killsession -----------------------------
// function: killsession()
// input: user name for hyperCMS session [string] (optional), destroy php session [boolean] (optional), remove session file of the user [boolean] (optional)
// output: true
// requires: hypercms_api.inc.php

// description:
// Destroys the session data of a user.

function killsession ($user="", $destroy_php=true, $remove=false)
{
  global $mgmt_config;

  // initialize
  $error = array();

  // if hypercms user session file exists
  if (valid_objectname ($user) && is_file ($mgmt_config['abs_path_data']."session/".$user.".dat"))
  {
    if ($remove == false)
    {
      $session_array = file ($mgmt_config['abs_path_data']."session/".$user.".dat");

      if (is_array ($session_array) && sizeof ($session_array) > 0)
      {
        $sessiondata = "";
        $remove = true;

        foreach ($session_array as $session)
        {
          $session = trim ($session);

          list ($regsessionid, $regsessiontime, $regpasswd, $regchecksum) = explode ("|", $session);

          // remove session entry if it is older than 12 hours (43200 sec.)
          if ($regsessionid == session_id() || $regsessiontime + 43200 <= time())
          {
            // session entry can be killed
          }
          else 
          {
            $sessiondata .= $session."\n";
            $remove = false;
          }
        }
      }
    }
 
    // delete session file
    if ($remove == true)
    {
      if (is_file ($mgmt_config['abs_path_data']."session/".$user.".dat"))
      {
        $deletesession = deletefile ($mgmt_config['abs_path_data']."session/", $user.".dat", 0);
      }
    }
    else
    {
      $deletesession = savefile ($mgmt_config['abs_path_data']."session/", $user.".dat", $sessiondata);
    }
  }

  // delete session data and temporary files
  if (is_file ($mgmt_config['abs_path_data']."session/".session_id().".dat")) deletefile ($mgmt_config['abs_path_data']."session/", session_id().".dat", 0);
  if (is_file ($mgmt_config['abs_path_temp'].session_id().".dat")) deletefile ($mgmt_config['abs_path_temp'], session_id().".dat", 0);
  if (is_file ($mgmt_config['abs_path_temp'].session_id().".js")) deletefile ($mgmt_config['abs_path_temp'], session_id().".js", 0);

  // delete service files
  $services = getsession ("hcms_services");

  if (is_array ($services) && sizeof ($services) > 0)
  {
    foreach ($services as $servicename => $hash_array)
    {
      if (is_array ($hash_array) && sizeof ($hash_array) > 0)
      {
        foreach ($hash_array as $hash)
        {
          if (is_file ($mgmt_config['abs_path_data']."session/".$servicename.".".$hash.".dat"))
          {
            deletefile ($mgmt_config['abs_path_data']."session/", $servicename.".".$hash.".dat", 0);
          }
        }
      }
    }
  }

  // get client IP address
  $client_ip = getuserip();

  // log information
  if (!empty ($deletesession))
  {
    $errcode = "00112";
    $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|information|".$errcode."|User '".$user."' with client IP ".$client_ip." is logged out";
    savelog ($error);
  }

  // kill PHP session
  if ($destroy_php == true) @session_destroy();

  return true;
}

// ---------------------- suspendsession -----------------------------
// function: suspendsession()
// input: process name [string] (optional), user name [string] (optional)
// output: session ID / false on error
// requires: hypercms_api.inc.php

// description:
// Suspends the session by writing the session data and closing it.

function suspendsession ($name="", $user="")
{
  global $mgmt_config;

  // create process directory if missing
  if (!is_dir ($mgmt_config['abs_path_data']."process/")) mkdir ($mgmt_config['abs_path_data']."process/");

  // get session ID
  if (session_id() != "") $session_id = session_id();
  else $session_id = "";

  // create process file if process name and user name have been provided
  if (valid_objectname ($name) && valid_objectname ($user) && is_dir ($mgmt_config['abs_path_data']."process/"))
  {
    $data = date ("Y-m-d H:i:s")."|".$session_id;

    savefile ($mgmt_config['abs_path_data']."process/", $name.".".$user.".dat", $data);
  }

  // write and close session (important for non-blocking: any page that needs to access a session now has to wait for the long running script to finish execution before it can begin)
  if ($session_id != "")
  {
    session_write_close();

    return $session_id;
  }

  return false;
}

// ---------------------- revokesession -----------------------------
// function: revokesession()
// input: process name [string] (optional), user name [string] (optional), session ID [string] (optional)
// output: session ID / false on error
// requires: hypercms_api.inc.php

// description:
// Revokes the session.

function revokesession ($name="", $user="", $session_id="")
{
  global $mgmt_config;

  // delete process file if process name and user name have been provided
  if (valid_objectname ($name) && valid_objectname ($user) && is_file ($mgmt_config['abs_path_data']."process/".$name.".".$user.".dat"))
  {
    deletefile ($mgmt_config['abs_path_data']."process/", $name.".".$user.".dat", 0);
  }

  // restart session (that has been previously closed for non-blocking procedure)
  if (empty (session_id()))
  {
    return createsession ("hyperCMS", $session_id);
  }

  return false;
}

// ---------------------- is_suspendedsession -----------------------------
// function: is_suspendedsession()
// input: process name [string] (optional), user name [string] (optional)
// output: true / false
// requires: hypercms_api.inc.php

// description:
// Verifies if a process file of a suspended session exists.

function is_suspendedsession ($name="", $user="")
{
  global $mgmt_config;

  $process_file = $mgmt_config['abs_path_data']."process/".$name.".".$user.".dat";

  // verify process file if process name and user name have been provided
  if (valid_objectname ($name) && valid_objectname ($user) && is_file ($process_file))
  {
    // if process file is not older than 2 hour
    if (filemtime ($process_file) > (time() - 60*60*2))
    {
      return true;
    }
  }

  return false;
}

// ---------------------- checkdiskkey -----------------------------
// function: checkdiskkey()
// input: %
// output: true / false

// description:
// Checks the disc key of the installation.

function checkdiskkey ()
{
  global $mgmt_config;

  // initialize
  $error = array();

  // version info
  require ($mgmt_config['abs_path_cms']."version.inc.php");

  // disk key
  require ($mgmt_config['abs_path_cms'].strrev("php.cni.yekksid/edulcni"));

  // mapping
  $key = strrev ("yekksid");
  $hash = strrev ("hsahksid");

  if (!empty ($mgmt_config[$key]))
  {
    $data = array();

    // disk key
    $data['key'] = $mgmt_config[$key];

    // MD5 hash of hypercms_sec.inc.php 
    $md5 = md5_file ($mgmt_config['abs_path_cms']."function/hypercms_sec.inc.php");
    $data['md5'] = $md5;

    // publications
    $data['site'] = "";
    $site_array = array();
    $inherit_db = inherit_db_read ();
    
    if ($inherit_db != false && sizeof ($inherit_db) > 0)
    {
      foreach ($inherit_db as $inherit_db_record)
      {
        if ($inherit_db_record['parent'] != "")
        {
          $site_array[] = trim ($inherit_db_record['parent']);
        }
      }

      if (is_array ($site_array))
      {
        natcasesort ($site_array);
        $data['site'] = implode ("|", $site_array);
      }
    }

    // count users
    $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php"); 
    $data['users'] = substr_count ($userdata, "</user>");

    // storage in MB
    $filesize = rdbms_getfilesize ("", "%hcms%");
    $storage = round (($filesize['filesize'] / 1024), 0);
    $data['storage'] = $storage;

    // cpu cores
    $serverload = getserverload ();
    $data['cpu'] = $serverload['cpu'];

    // version
    $data['version'] = $mgmt_config['version'];

    // client ip
    $data['userip'] = getuserip();

    // domain
    $data['domain'] = $mgmt_config['url_path_cms'];

    // server IP address
    $data['server_ip'] = (!empty ($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : "unknown IP");

    // non-free modules
    $data['modules'] = "";

    if (is_dir ($mgmt_config['abs_path_cms']."connector")) $data['modules'] .= "Connector,";
    if (is_dir ($mgmt_config['abs_path_cms']."encryption")) $data['modules'] .= "Encryption,";
    if (is_dir ($mgmt_config['abs_path_cms']."project")) $data['modules'] .= "Project,";
    if (is_dir ($mgmt_config['abs_path_cms']."report")) $data['modules'] .= "Report,";
    if (is_dir ($mgmt_config['abs_path_cms']."task")) $data['modules'] .= "Task,";
    if (is_dir ($mgmt_config['abs_path_cms']."webdav")) $data['modules'] .= "WebDAV,";
    if (is_dir ($mgmt_config['abs_path_cms']."workflow")) $data['modules'] .= "Workflow,";

    $data['modules'] = trim ($data['modules'], ",");

    if ($mgmt_config['url_protocol'] != "https://" || $mgmt_config['url_protocol'] != "http://") $mgmt_config['url_protocol'] = "https://";

    $result_post = HTTP_Post ($mgmt_config['url_protocol']."cloud.hypercms.net/keyserver/", $data, "application/x-www-form-urlencoded", "UTF-8", "", "body");

    if (!empty ($result_post) && strpos ("_".$result_post, "<result>") > 0)
    {
      $result = getcontent ($result_post, "<result>");

      // result must be true or the default hash key is provided by the system (free edition)
      if ((!empty ($result[0]) && $result[0] == "true") || ($mgmt_config[$key] == "tg3234g234zg78ze8whf" && !empty ($data['modules']))) return true;
      else return false;
    }
    elseif (!empty ($mgmt_config[$hash]))
    {
      $result = hcms_decrypt ($mgmt_config[$hash], $mgmt_config[$key], "strong", "base64");

      if ($result != "")
      {
        list ($server_ips, $modules, $cpu, $users, $storage, $timestamp) = explode (";", $result);

        // check limits
        if (strpos (" ".$server_ips." ", $data['server_ip']) > 0 && $modules == $data['modules'] && (intval ($cpu) <= 0 || intval ($data['cpu']) <= intval ($cpu)) && (intval ($users) <= 0 || intval ($data['users']) <= intval ($users)) && (intval ($storage) <= 0 || intval ($data['storage']) <= intval ($storage)) && ($timestamp <= 0 || time() <= $timestamp))
        {
          return true;
        }
        else
        {
          // warning
          $errcode = "00130";
          $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|warning|".$errcode."|IP=".$data['server_ip']." (".$server_ip."), Modules=".$data['modules']." (".$modules."), CPU=".$data['cpu']." (".$cpu."), Users=".$data['users']." (".$users."), Storage=".$data['storage']." (".$storage."), Time=".time()." (".$timestamp.")";

          savelog (@$error, "license");
          
          return false;
        }
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// ---------------------------------------- checkpassword --------------------------------------------
// function: checkpassword()
// input: password [string], user name [string] (optional for password history)
// output: true if passed / error message as string

// description:
// This function checks the strength of a password and return the error messages or true.

function checkpassword ($password, $user="")
{
  global $mgmt_config, $lang;

  // initialize
  $error = array();

  require ($mgmt_config['abs_path_cms']."language/".getlanguagefile ($lang));

  // set default
  if (empty ($mgmt_config['passwordminlength']) || intval ($mgmt_config['passwordminlength']) < 4 || intval ($mgmt_config['passwordminlength']) > 100)
  {
    $mgmt_config['passwordminlength'] = 10;
  }

  if ($password != "")
  {
    // must be at least X digits long
    if (strlen ($password) < intval ($mgmt_config['passwordminlength'])) $error[] = $hcms_lang['the-passwords-has-less-than-digits'][$lang];
    // must not be longer than 100 digits
    if (strlen ($password) > 100)	$error[] = $hcms_lang['the-password-has-more-than-digits'][$lang];
    // must contain at least one number
    if (!preg_match ("#[0-9]+#", $password)) $error[] = $hcms_lang['password-must-include-at-least-one-number'][$lang];
    // must contain at least one letter
    if (!preg_match ("#[a-z]+#", $password))	$error[] = $hcms_lang['password-must-include-at-least-one-letter'][$lang];
    // must contain at least one capital letter
    if (!preg_match ("#[A-Z]+#", $password)) $error[] = $hcms_lang['password-must-include-at-least-one-capital-letter'][$lang];
    // must contain at least one symbol (optional but not used) 
    // if (!preg_match ("#\W+#", $password)) $error .= $hcms_lang['password-must-include-at-least-one-symbol'][$lang];

    // password blacklist
    if (!empty ($mgmt_config['passwordblacklist']))
    {
      $passwordblacklist = splitstring ($mgmt_config['passwordblacklist']);

      if (is_array ($passwordblacklist) && in_array ($password, $passwordblacklist)) $error[] = $hcms_lang['password-insufficient'][$lang];
    }

    // password history
    if (!empty ($mgmt_config['passwordhistory']))
    {
      $log_array = loadlog ($user.".password", "array");

      if ($log_array != false && sizeof ($log_array) > 0)
      {
        // reverse array
        $log_array = array_reverse ($log_array);

        // check history
        $i = 1;

        foreach ($log_array as $log)
        {
          if (strpos ($log, "|") > 0)
          {
            list ($date, $hash) = explode ("|", $log);

            if (password_verify ($password, $hash))
            {
              $error[] = $hcms_lang['password-must-not-be-used'][$lang]." (Password history: ".intval ($mgmt_config['passwordhistory']).")";
            }

            if ($i <= intval ($mgmt_config['passwordhistory'])) break;
            $i++;
          }
        }
      }
    }

    if (is_array ($error) && sizeof ($error) > 0)
    {
      return $hcms_lang['password-validation-failure'][$lang].": ".implode (", ", $error);
    }
    else return true;
  }
  else return $hcms_lang['password-is-not-set'][$lang];
}

// ===================================== SECURITY FUNCTIONS =====================================

// --------------------------------------- loguserip -------------------------------------------
// function: loguserip()
// input: client IP address [string], user logon name [string] (optional)
// output: true / false on error

function loguserip ($client_ip, $user="sys") 
{
  global $mgmt_config;

  if ($client_ip != "" && $user != "")
  {
    // log file
    $loglocation = $mgmt_config['abs_path_data']."log/";
    $logfile = "locked_ip.log";

    // time stamp in seconds
    $now = time ();

    if (is_file ($loglocation.$logfile))
    {
      // append data to log if IP is not already locked
      return appendfile ($loglocation, $logfile, $client_ip."|".$user."|".$now."\n");
    }
    else
    {
      // save log file initially
      return savefile ($loglocation, $logfile, $client_ip."|".$user."|".$now."\n");
    }
  }
  else return false;
}

// --------------------------------------- checkuserip -------------------------------------------
// function: checkuserip()
// input: client IP address [string], user logon name [string] (optional), timeout in minutes [integer] (optional)
// output: true if IP is not locked / false if IP is locked or on error

function checkuserip ($client_ip, $user="", $timeout=0) 
{
  global $mgmt_config;

  // set default logon timeout
  if (empty ($timeout)) $timeout = $mgmt_config['logon_timeout'];

  if ($client_ip != "" && $timeout > 0)
  {
    // log file
    $loglocation = $mgmt_config['abs_path_data']."log/";
    $logfile = "locked_ip.log";

    // time stamp in seconds
    $now = time ();

    $valid = true;

    if (is_file ($loglocation.$logfile))
    {
      // load log data
      $logdata = file ($loglocation.$logfile);

      foreach ($logdata as $record) 
      {
        list ($log_ip, $log_user, $log_time) = explode ("|", $record);

        // check if client ip is already in log and locked
        if ($client_ip == $log_ip && ($user == "" || $user == $log_user) && $now < (intval ($log_time) + 60 * intval ($timeout)))
        {
          // no access
          $valid = false;
          break;
        }
      }

      return $valid;
    }
    else return $valid;
  }
  // timeout is set to 0, means there is no timeout
  elseif ($timeout == 0) return true;
  // invalid arguments
  else return false;
}

// --------------------------------------- checkuserrequests -------------------------------------------
// function: checkuserrequests()
// input: user name [string] (optional)
// output: true / false if a certain amount of reguests per minute is exceeded

// description:
// Provides security for Cross-Site Request Forgery.

function checkuserrequests ($user="sys")
{
  global $mgmt_config;

  // initialize
  $error = array();

  // set default value
  if (!isset ($mgmt_config['requests_per_minute'])) $mgmt_config['requests_per_minute'] = 1000;

  if (intval ($mgmt_config['requests_per_minute']) > 0)
  {
    // hit counter
    if (isset ($_SESSION['hcms_temp_hit_counter']) && $_SESSION['hcms_temp_hit_counter'] > 0)
    {
      $_SESSION['hcms_temp_hit_counter']++;
    }
    // set hit counter and time stamp
    else
    {
      $_SESSION['hcms_temp_hit_counter'] = 1;
      $_SESSION['hcms_temp_hit_starttime'] = time();
    }

    // check time after given number of requests
    if (intval ($_SESSION['hcms_temp_hit_counter']) > intval ($mgmt_config['requests_per_minute']))
    {
      // more than given number of requests per minute, this might be a flood attack
      if (time() - $_SESSION['hcms_temp_hit_starttime'] <= 60)
      {
        // get client IP
        $client_ip = getuserip ();

        // log client ip
        loguserip ($client_ip, $user);

        // warning
        $errcode = "00109";
        $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|warning|".$errcode."|User '".$user."' with client IP ".$client_ip." is banned due to a possible CSRF attack";

        savelog (@$error);
        killsession ($user);

        return false;
      }
      else
      {
        // reset hit counter and time stamp
        $_SESSION['hcms_temp_hit_counter'] = 1;
        $_SESSION['hcms_temp_hit_starttime'] = time();

        return true;
      }
    }
  }

  return true;
}

// ------------------------- recreateusersession -----------------------------
// function: recreateusersession()
// input: %
// output: true / false
// requires config.inc.php

// description:
// Recreates the users session data in case it is missing (due to issues with Android Chrome and the Mobile Edition).
// Recreates the session data only if the session ID is still available.

function recreateusersession ()
{
  global $mgmt_config;

  // read and set session data if not available
  if (getsession ('hcms_user') == "" && is_file ($mgmt_config['abs_path_data']."session/".session_id().".dat"))
  {
    session_decode (file_get_contents ($mgmt_config['abs_path_data']."session/".session_id().".dat"));
  }
}

// ------------------------- checkusersession -----------------------------
// function: checkusersession()
// input: user name or service identifier [string] (optional), include CSRF detection [boolean]
// output: true / html-output followed by termination
// requires config.inc.php

// description:
// Checks if the session data of a user is valid. This function does access session variables directly.
// If a system service is used the service identifier in the form of "sys:service-name:service-hash" can be provided. 

function checkusersession ($user="sys", $CSRF_detection=true)
{
  global $mgmt_config;

  // initialize
  $alarm = true;
  $error = array();

  // add CSRF detection
  if ($CSRF_detection == true) checkuserrequests ($user); 

  // check system service "sys:service-name:16-digit-servicehash"
  if (substr ($user, 0, 4) == "sys:" && substr_count ($user, ":") == 2)
  {
    list ($sys, $servicename, $servicehash) = explode (":", $user);

    // get users client IP address
    $client_ip = getuserip ();

    // service session file must exist
    if (is_file ($mgmt_config['abs_path_data']."session/".$servicename.".".$servicehash.".dat"))
    {
      // verify IP address of  system service user
      if (getsession ('hcms_user_ip') == $client_ip) $alarm = false;
    }
  }
  // check standard user
  elseif (valid_objectname ($user) && is_file ($mgmt_config['abs_path_data']."session/".$user.".dat") && !empty ($_SESSION['hcms_siteaccess']) && is_array ($_SESSION['hcms_siteaccess']) && !empty ($_SESSION['hcms_rootpermission']) && is_array ($_SESSION['hcms_rootpermission']))
  {
    $session_array = @file ($mgmt_config['abs_path_data']."session/".$user.".dat");

    if ($session_array != false && sizeof ($session_array) > 0)
    {
      foreach ($session_array as $session)
      {
        if (trim ($session) != "")
        {
          list ($regsessionid, $regsessiontime, $regpasswd, $regchecksum) = explode ("|", trim ($session));

          // session is correct if session ID in session and hypercms session file are equal, MD5 crypted passwords are equal, permission checksums are equal
          if ($regsessionid == session_id() && $regpasswd == $_SESSION['hcms_passwd'] && $regchecksum == createchecksum ())
          {
            $alarm = false;
          }
        }
      }
    }
  }

  // save log
  savelog (@$error);

  // unauthorized access
  if ($alarm == true)
  {
    echo showinfopage ("Unauthorized Access!", "en", "top.location='".cleandomain ($mgmt_config['url_path_cms'])."userlogout.php';");
    exit;
  }
  // authorized access
  else return true; 
}

// ------------------------- allowuserip  -----------------------------
// function: allowuserip ()
// input: publication name [string]
// output: true / false
// requires config.inc.php

// description:
// Verifies if the client IP is in the range of valid IPs and logs IP addresses with no access.

function allowuserip ($site)
{
  global $mgmt_config;

  // initialize
  $error = array();

  // publication management config
  if (valid_publicationname ($site) && !isset ($mgmt_config[$site]['allow_ip'])) require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

  // check ip access
  if (valid_publicationname ($site) && isset ($mgmt_config[$site]['allow_ip']) && $mgmt_config[$site]['allow_ip'] != "")
  {
    $client_ip = getuserip ();
    $allow_ip = splitstring ($mgmt_config[$site]['allow_ip']);

    if ($client_ip && is_array ($allow_ip))
    {
      if (in_array ($client_ip, $allow_ip)) $result = true;
      else $result = false;
    }
    elseif (!$client_ip)
    {
      $result = false;
    }
    else $result = true;

    if ($result == false)
    {
      $errcode = "00401";
      $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|warning|".$errcode."|Client IP (".$client_ip.") tried to access an object outside of the allowed IP range of publication ".$site; 
    }
 
    // save log
    savelog (@$error);

    return $result;
  }

  return true;
}

// ------------------------- valid_objectname -----------------------------
// function: valid_objectname()
// input: variable [string or array]
// output: true / false

// description:
// Checks if an object name includes forbidden characters in order to prevent directory browsing.

function valid_objectname ($variable)
{
  if ($variable != "")
  {
    if (is_string ($variable))
    {
      if ($variable == ".") return false;
      if ($variable == "..") return false;
      if (substr_count ($variable, "<") > 0) return false;
      if (substr_count ($variable, ">") > 0) return false;
      if (substr_count ($variable, "./") > 0) return false;
      if (substr_count ($variable, "../") > 0) return false;
      if (substr_count ($variable, "\\") > 0) return false;
      if (substr_count ($variable, "\"") > 0) return false;

      return true;
    }
    elseif (is_array ($variable))
    {
      $result = true;

      foreach ($variable as &$value)
      {
        $value = valid_objectname ($value);
        if ($value == false) $result = false;
      }

      if ($result == true) return true;
    } 
  }

  return false;
}

// ------------------------- valid_locationname -----------------------------
// function: valid_locationname()
// input: variable [string or array]
// output: true / false

// description:
// Checks if an location is valid and does not include forbidden characters in order to prevent directory browsing.

function valid_locationname ($variable)
{
  global $mgmt_config;

  // initialize
  $error = array();
  
  if ($variable != "")
  {
    if (!is_array ($variable) && is_string ($variable))
    {
      // default values
      if (empty ($mgmt_config['max_digits_filename']) || intval ($mgmt_config['max_digits_filename']) < 1) $mgmt_config['max_digits_filename'] = 200;
      if (empty ($mgmt_config['max_digits_location']) || intval ($mgmt_config['max_digits_location']) < 1) $mgmt_config['max_digits_location'] = 4096;

      $variable = trim ($variable);

      // decode special characters in location (decoded path might be shorter than the encoded path)
      $variable_name = specialchr_decode ($variable);
      
      // encode special characters in location (string might be longer after encoding)
      $variable = specialchr_encode ($variable, false);

      // remove component path since it is not used for display (in browser and WebDAV client)
      if (!empty ($mgmt_config['abs_path_comp']) && strpos ("_".$variable_name, $mgmt_config['abs_path_comp']) == 1) $variable_name = str_replace ($mgmt_config['abs_path_comp'], "/", $variable_name);

      if (strpos ("_".$variable_name, "%comp%") == 1) $variable_name = str_replace ("%comp%", "", $variable_name);
      elseif (strpos ("_".$variable_name, "%page%") == 1) $variable_name = str_replace ("%page%", "", $variable_name);

      // if location path is too long according to the main configuration settings
      // Windows Explorer (MAX_PATH) only supports only up to 260 characters for the file path incl the drive letter which could be an issue when using WebDAV
      // database column objectpath supports up to 4096 characters for the file path, same as the standard Linux FS
      // new main configuration setting $mgmt_config['max_digits_location'] since version 10.1.1 
      // the max length for file names only will be subtracted if the max path lenth is longer than 260 characters since there could be a lot of warning otherwise with $mgmt_config['max_digits_location'] = 260 and $mgmt_config['max_digits_filename'] = 200
      if (
           (intval ($mgmt_config['max_digits_location']) <= 260 && (mb_strlen ($variable_name) + 2) > intval ($mgmt_config['max_digits_location'])) ||
           (intval ($mgmt_config['max_digits_location']) > 260 && (mb_strlen ($variable_name) + 2) > (intval ($mgmt_config['max_digits_location']) - intval ($mgmt_config['max_digits_filename']))) ||
           (mb_strlen ($variable) + intval ($mgmt_config['max_digits_filename'])) > 4096
         )
      {
        // warning
        $errcode = "00900";
        $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|warning|".$errcode."|Location path is too long '".$variable_name."'";

        savelog (@$error);

        return false;
      }
      
      // invalid location path
      if ($variable == ".") return false;
      if ($variable == "..") return false;
      if (substr_count ($variable, "<") > 0) return false;
      if (substr_count ($variable, ">") > 0) return false;
      if (substr_count ($variable, "\"") > 0) return false;
      if (strpos ("_".$variable, "../") == 1 || substr_count ($variable, "/../") > 0) return false;
      if (strpos ("_".$variable, "..\\") == 1 || substr_count ($variable, "\\..\\") > 0) return false;
      if (strpos ("_".$variable, "./") == 1 || substr_count ($variable, "/./") > 0) return false;
      if (strpos ("_".$variable, ".\\") == 1 || substr_count ($variable, "\\.\\") > 0) return false;
      if (substr_count ($variable, "\\0") > 0) return false;

      return true;
    }
    elseif (is_array ($variable))
    {
      $result = true;

      foreach ($variable as &$value)
      {
        $value = valid_locationname ($value);
        if ($value == false) $result = false;
      }

      if ($result == true) return true;
    } 
  }

  return false;
}

// ------------------------- valid_publicationname -----------------------------
// function: valid_publicationname()
// input: variable [string or array]
// output: true / false

// description:
// Checks if a publication name includes forbidden characters in order to prevent directory browsing.
// Optionally verifies if the publication name is included in the siteaccess variable.

function valid_publicationname ($variable)
{
  global $siteaccess;

  if ($variable != "")
  {
    if (!is_array ($variable) && is_string ($variable))
    {
      if (!empty ($siteaccess) && is_array ($siteaccess) && !array_key_exists ($variable, $siteaccess)) return false;
      if ($variable == "*Null*" || $variable == "*no_memberof*") return false;
      if ($variable == "..") return false;
      if (substr_count ($variable, "<") > 0) return false;
      if (substr_count ($variable, ">") > 0) return false;
      if (substr_count ($variable, "/") > 0) return false;
      if (substr_count ($variable, "\\") > 0) return false;
      if (substr_count ($variable, ":") > 0) return false;
      if (substr_count ($variable, "\"") > 0) return false;
      return true;
    }
    elseif (is_array ($variable))
    {
      $result = true;

      foreach ($variable as &$value)
      {
        $value = valid_publicationname ($value);
        if ($value == false) $result = false;
      }

      if ($result == true) return true;
    }
  }

  return false;
}

// ------------------------- html_encode -----------------------------
// function: html_encode()
// input: variable [string or array], conversion of all special characters based on given character set or to ASCII [string] (optional), 
//        remove characters to avoid JS injection [boolean] (optional)
// output: html encoded value as array or string / false on error

// description:
// This function encodes certain characters (&, <, >, ", ') into their HTML character entity equivalents to protect against XSS.
// Converts a string into the html equivalents (also used for XSS protection).
// Supports multibyte character sets like UTF-8 as well based on the ASCII value of the character.

function html_encode ($expression, $encoding="", $js_protection=false)
{
  if ($expression != "")
  {
    // input is string
    if (!is_array ($expression) && strlen ($expression) > 0)
    { 
      // encode all characters with support multibyte character sets like UTF-8 (htmlentities is not supporting all languages)
      if (strtolower ($encoding) == "ascii")
      {
        $result = "";
        $offset = 0;

        // to prevent double encoding decode first
        $expression = html_decode ($expression, "UTF-8");

        if (substr_count ($expression, "&#") == 0 && strlen ($expression) > 0)
        {
          while ($offset < strlen ($expression))
          {
            // get ord of each single character
            $code = ord (substr ($expression, $offset, 1));

            // if ord is greater than 128 it must be a multibyte character (otherwise 0xxxxxxx)
            if ($code >= 128)
            {
              // 110xxxxx
              if ($code < 224) $bytesnumber = 2; 
              // 1110xxxx 
              else if ($code < 240) $bytesnumber = 3;
              // 11110xxx
              else if ($code < 248) $bytesnumber = 4;

              $codetemp = $code - 192 - ($bytesnumber > 2 ? 32 : 0) - ($bytesnumber > 3 ? 16 : 0);

              for ($i = 2; $i <= $bytesnumber; $i++)
              {
                $offset++;
                // 10xxxxxx
                $code2 = ord (substr ($expression, $offset, 1)) - 128;
                $codetemp = $codetemp * 64 + $code2;
              }

              $code = $codetemp;
            }

            $offset += 1;

            // html escape character
            $result .= "&#".$code.";";

            // if end of string -> end loop
            if ($offset >= strlen ($expression)) break;
          }
        }
      }
      // enocode based on character set
      elseif ($encoding != "")
      {
        // to prevent double encoding decode first
        $result = htmlentities (html_decode ($expression, $encoding), ENT_QUOTES, $encoding);
      }
      // enocde only a small set of special characters
      else 
      {
        // to prevent double encoding decode first
        $result = str_replace (array ("&", "\"", "'", "<", ">"), array ("&amp;", "&quot;", "&#039;", "&lt;", "&gt;"), html_decode ($expression));
      } 
    }
    // input is array
    elseif (is_array ($expression))
    {
      $result = $expression;

      foreach ($result as &$value)
      {
        // convert
        $value = html_encode ($value, $encoding, $js_protection);
      }
    }

    // replace special harmful characters for JS (XSS protection)
    if ($js_protection == true && !empty ($result))
    {
      $result = str_replace (array ("{", "}", "(", ")", ";", "\\n"), array ("", "", "", "", "", ""), html_decode ($result));
    }

    if (!empty ($result)) return $result;
    else return $expression;
  }

  return false;
}

// ------------------------- html_decode -----------------------------
// function: html_decode()
// input: epxression [string or array], conversion of all special characters based on character set [string] (optional)
// output: html decoded value as array or string / false on error

// description:
// This function decodes all characters which have been converted by html_encode.

function html_decode ($expression, $encoding="")
{
  if ($expression != "" )
  {
    if (!is_array ($expression))
    {
      if ($encoding != "")
      {
        if (strtolower ($encoding) == "ascii") $encoding = "UTF-8";
 
        $expression_esc = html_entity_decode ($expression, ENT_QUOTES, $encoding);
      }
      else
      {
        $expression_esc = htmlspecialchars_decode ($expression, ENT_QUOTES);
      } 
    }
    elseif (is_array ($expression))
    {
      foreach ($expression as &$value)
      {
        $value = html_decode ($value, $encoding);
      }
    }

    if (!empty ($expression_esc)) return $expression_esc;
    else return $expression;
  }

  return false;
}

// ------------------------- scriptcode_encode -----------------------------
// function: scriptcode_encode()
// input: content [string] 
// output: escaped content as string / false on error

// description:
// This function escapes all script tags.
// This function must be used to clean all user input in the CMS by removing all server side scripts tags.

function scriptcode_encode ($content)
{
  global $mgmt_config;

  if ($content != "" && !is_array ($content))
  {
    $content = str_replace ("<?", "&lt;?", $content);
    $content = str_replace ("?>", "?&gt;", $content);
    $content = str_replace ("<%", "&lt;%", $content);
    $content = str_replace ("%>", "%&gt;", $content);
    $content = str_replace ("<script>", "&lt;script&gt;", $content);
    $content = str_replace ("</script>", "&lt;/script&gt;", $content); 
    $content = str_replace ("<script", "&lt;script", $content); 

    return $content;
  }

  return false;
}

// ------------------------- scriptcode_extract -----------------------------
// function: scriptcode_extract()
// input: content [string], identifier of script begin [string], identifier of script end [string]
// output: script code as array / false on error or if noting was found

// description:
// This function extracts the script code of a given content.

function scriptcode_extract ($content, $identifier_start="<?", $identifier_end="?>")
{
  if ($content != "" && $identifier_start != "" && $identifier_end != "" && strpos ("_".$content, $identifier_start) > 0)
  {
    $content_array = explode ($identifier_start, $content);

    if (is_array ($content_array))
    {
      $result = array();
      $i = 0;

      foreach ($content_array as $buffer)
      {
        if ($i > 0)
        {
          // search for end tag
          if (strpos ($buffer, $identifier_end) > 0)
          {
            list ($content_script, $rest) = explode ($identifier_end, $buffer);
          }
          // no end tag defined
          else
          {
            $content_script = $buffer;
          }
 
          if ($content_script != "")
          {
            // remove // comments
            if (substr_count ($content_script, "//") > 0)
            {
              $comment_array = scriptcode_extract ($content_script."\n", "// ", "\n");

              if (is_array ($comment_array))
              {
                foreach ($comment_array as $comment) $content_script = str_replace ($comment, "", $content_script);
              }
            }

            // remove /* */ comments
            if (substr_count ($content_script, "/*") > 0)
            {
              $comment_array = scriptcode_extract ($content_script, "/*", "*/");

              if (is_array ($comment_array))
              {
                foreach ($comment_array as $comment) $content_script = str_replace ($comment, "", $content_script);
              }
            }

            $result[] = $identifier_start.$content_script.$identifier_end;
          }
        }

        $i++;
      }

      if (sizeof ($result) > 0) return $result;
      else return false;
    }
    else return false;
  }
  else return $content;
}

// ------------------------- scriptcode_clean_functions -----------------------------
// function: scriptcode_clean_functions()
// input: content [string], cleaning level type: no cleaning = 0; basic set of disabled functions = 1; 1 + file access functions = 2; 2 + include functions = 3; 3 + hyperCMS API file functions = 4; No server side script allowed = 5 [0,1,2,3,4,5] (optional), application [PHP,ASP,JSP] (optional)
// output: result array / false on error

// description:
// This function removes all dangerous PHP functions.

function scriptcode_clean_functions ($content, $type=4, $application="PHP")
{
  global $mgmt_config;

  // initialize
  $disabled_functions = array();
  $file_functions = array();
  $include_functions = array();
  $api_file_functions = array();
  $identifier_start = "<?";
  $identifier_end = "?>";

  // validate application input
  $application = strtoupper ($application);
  if ($application != "ASP" && $application != "JSP" && $application != "PHP") $application = "PHP";

  // PHP defintions
  if ($application == "PHP")
  {
    if ($type > 0) $disabled_functions = array("apache_child_terminate", "apache_setenv", "define_syslog_variables", "eval", "exec", "fp", "fput", "ftp_connect", "ftp_exec", "ftp_get", "ftp_login", "ftp_nb_fput", "ftp_put", "ftp_raw", "ftp_rawlist", "highlight_file", "ini_alter", "ini_get_all", "ini_restore", "inject_code", "mysql_pconnect", "openlog", "passthru", "php_uname", "phpinfo", "phpAds_remoteInfo", "phpAds_XmlRpc", "phpAds_xmlrpcDecode", "phpAds_xmlrpcEncode", "popen", "posix_getpwuid", "posix_kill", "posix_mkfifo", "posix_setpgid", "posix_setsid", "posix_setuid", "posix_setuid", "posix_uname", "proc_close", "proc_get_status", "proc_nice", "proc_open", "proc_terminate", "shell_exec", "syslog", "system", "xmlrpc_entity_decode");
    if ($type > 1) $file_functions = array("basename", "chgrp", "chmod", "chown ", "clearstatcache", "copy", "delete", "dir", "dirname", "disk_free_space", "disk_total_space", "diskfreespace", "fclose", "feof", "fflush", "fgetc", "fgetcsv", "fgets", "fgetss", "file_exists", "file_get_contents", "file_put_contents ", "file", "fileatime", "filectime", "filegroup", "fileinode", "filemtime", "fileowner", "fileperms", "filesize", "filetype", "flock", "fnmatch", "fopen", "fpassthru", "fputcsv", "fputs", "fread", "fscanf", "fseek", "fstat", "ftell", "ftruncate", "fwrite", "glob", "is_dir", "is_executable ", "is_file", "is_link", "is_readable", "is_uploaded_file ", "is_writable", "is_writeable ", "lchgrp", "lchown", "link", "linkinfo", "lstat", "mkdir", "move_uploaded_file", "opendir", "parse_ini_file", "parse_ini_string", "pathinfo ", "pclose", "popen", "readfile", "readlink", "realpath_cache_get", "realpath_cache_size", "realpath", "rename", "rewind", "rmdir", "set_file_buffer", "stat", "symlink ", "tempnam", "tmpfile ", "touch ", "umask", "unlink");
    if ($type > 2) $include_functions = array("include", "include_once", "require", "require_once");
    $identifier_start = "<?";
    $identifier_end = "?>";
  }
  // ASP defintions
  elseif ($application == "ASP")
  {
    $identifier_start = "<?";
    $identifier_end = "?>"; 
  }
  // JSP defintions
  elseif ($application == "JSP")
  {
    $identifier_start = "<%";
    $identifier_end = "%>"; 
  }

  // scan code for server side functions
  if ($content != "" && $type > 0 && $type < 5)
  {
    // hyperCMS API functions
    if ($type > 3) $api_file_functions = array("loadfile_header", "loadfile_fast", "loadfile", "loadlockfile", "savefile", "savelockfile", "appendfile", "lockfile", "unlockfile", "deletefile", "deletemediafiles", "copyrecursive", "deleteversion", "deleteversions", "loadcontainer", "savecontainer", "loadtemplate", "savetemplate", "loadlog", "savelog", "indexcontent", "unindexcontent", "reindexcontent", "convertimage", "convertmedia", "rotateimage", "readmediaplayer_config", "savemediaplyer_config", "unzipfile", "clonefolder", "zipfiles_helper", "createthumbnail_indesign", "createthumbnail_video", "createimages_video", "splitmedia", "createmedia", "createdocument"); 

    $all_functions = array_merge ($disabled_functions, $file_functions, $include_functions, $api_file_functions);

    $found = array();
    $scriptcode = "";

    // hyperCMS Script
    $scriptcode_array = scriptcode_extract (strtolower ($content), "[hypercms:scriptbegin", "scriptend]");
    if (is_array ($scriptcode_array)) $scriptcode = implode ("", $scriptcode_array);

    // Application Code
    $scriptcode_array = scriptcode_extract ($content, $identifier_start, $identifier_end);
    if (is_array ($scriptcode_array)) $scriptcode = $scriptcode.implode ("", $scriptcode_array);

    // remove functions from content
    if (!empty ($scriptcode))
    {
      foreach ($all_functions as $name)
      {
        // convert all multispaces to space
        $scriptcode = preg_replace ("/ +/", " ", $scriptcode);

        // find expression followed by (
        if ($name != "" && (substr_count ($scriptcode, $name." (") > 0 || substr_count ($scriptcode, $name."(") > 0))
        {
          // found expression
          $found[] = $name;
        }
      }
    }

    if (!empty ($found) && is_array ($found) && sizeof ($found) > 0)
    {
      $found_list = implode (", ", $found);
      $passed = false;
    }
    else
    {
      $found_list = "";
      $passed = true;
    }

    $result = array();
    $result['result'] = $passed;
    $result['content'] = $content;
    $result['found'] = $found_list;

    return $result;
  }
  // no server side script code allowed
  elseif ($type == 5)
  {
    $scriptcode = "";

    // hyperCMS Script
    $scriptcode_array = scriptcode_extract (strtolower ($content), "[hypercms:scriptbegin", "scriptend]");

    if (is_array ($scriptcode_array))
    {
      $scriptcode = implode ("", $scriptcode_array);
      $content = str_ireplace ($scriptcode_array, "", $content);
    }

    // Application Code
    $scriptcode_array = scriptcode_extract ($content, $identifier_start, $identifier_end);

    if (is_array ($scriptcode_array))
    {
      $scriptcode = $scriptcode.implode ("", $scriptcode_array);
      $content = str_ireplace ($scriptcode_array, "", $content);
    }

    if ($scriptcode != "")
    {
      $passed = false;
      $content = "";
      $found_list = "Server side code included";
    }
    else
    {
      $passed = true;
      $found_list = "";
    }

    $result = array();
    $result['result'] = $passed;
    $result['content'] = $content;
    $result['found'] =  $found_list;

    return $result;
  }
  // no check
  else
  {
    $result = array();
    $result['result'] = true;
    $result['content'] = "";
    $result['found'] = "";

    return $result;
  }
}

// ------------------------- sql_clean_functions -----------------------------
// function: sql_clean_functions()
// input: SQL statement [string]
// output: result array / false on error

// description:
// This function checks SQL statements for write operations.

function sql_clean_functions ($content)
{
  global $mgmt_config;

  if ($content != "")
  {
    $write_functions = array("insert", "update", "create", "delete", "replace", "set", "drop"); 

    $found = array();

    // remove functions from content
    foreach ($write_functions as $name)
    {
      // find expression followed by (
      if ($name != "" && @preg_match ('/\b'.preg_quote ($name).'\b(.*?)\(/i', $content))
      {
        // found expression
        $found[] = $name;
      }
    }

    if (sizeof ($found) > 0)
    {
      $found_list = implode (", ", $found);
      $passed = false;
    }
    else
    {
      $found_list = "";
      $passed = true;
    }

    $result = array();
    $result['result'] = $passed;
    $result['content'] = $content;
    $result['found'] = $found_list;

    return $result;
  }
  // no check
  else
  {
    $result = array();
    $result['result'] = true;
    $result['content'] = "";
    $result['found'] = "";

    return $result;
  }
}

// ------------------------- url_encode -----------------------------
// function: url_encode()
// input: variable [string or array]
// output: urlencoded value as array or string / false on error

// description:
// This function encodes all characters.

function url_encode ($variable)
{
  global $mgmt_config;

  if (!is_array ($variable))
  {
    return urlencode ($variable);
  }
  elseif (is_array ($variable))
  {
    foreach ($variable as &$value)
    {
      $value = urlencode ($value);
    }

    return $variable;
  }
  else return false;
}

// ------------------------- url_decode -----------------------------
// function: url_decode()
// input: variable [string or array]
// output: urldecoded value as array or string / false on error

// description:
// This function decodes all characters which have been converted by url_encode or urlencode (PHP).

function url_decode ($variable)
{
  global $mgmt_config;

  if (!is_array ($variable))
  {
    return urldecode ($variable);
  }
  elseif (is_array ($variable))
  {
    foreach ($variable as &$value)
    {
      $value = urldecode ($value);
    }

    return $variable;
  }
  else return false;
}

// ------------------------- shellcmd_encode -----------------------------
// function: shellcmd_encode()
// input: variable [string or array], type [%,strict] (optional)
// output: encoded value as array or string / false on error

// description:
// This function encodes/escapes characters to secure the shell comand.

function shellcmd_encode ($variable, $type="")
{
  if (!is_array ($variable))
  {
    if ($type == "strict") $variable = escapeshellcmd ($variable);

    // remove multiple commands connectors
    $variable = str_replace (array(";", "&&", "||", "&", "|"), array("", "", "", "", ""), $variable);

    // restore escaped value in file path
    return str_replace ("\~", "~", $variable);
  }
  elseif (is_array ($variable))
  {
    foreach ($variable as &$value)
    {
      if ($type == "strict") $value = escapeshellcmd ($value);

      // remove multiple commands connectors
      $value = str_replace (array(";", "&&", "||", "&", "|"), array("", "", "", "", ""), $value);

      // restore escaped value in file path
      $value = str_replace ("\~", "~", $value);
    }

    return $variable;
  }
  else return false;
}

// ======================================= CRYPTOGRAPHY =======================================

// ---------------------- hcms_crypt -----------------------------
// function: hcms_crypt()
// input: string to encode [string], start position [integer], length for string extraction [integer]
// output: encoded string / false on error

// description:
// Unidrectional encryption using sha1 and urlencode. Used to create tokens for simple view links in the system.
// The tokens can be verified by calculating the hash of the media file name and comparing the hash values.
// Don't use this function to secure any string or for password hashing.

function hcms_crypt ($string, $start=0, $length=0)
{
  global $mgmt_config;

  if ($string != "")
  {
    // set default private key for hashing
    if (empty ($mgmt_config['crypt_key'])) $mgmt_config['crypt_key'] = "h1y2p3e4r5c6m7s8";

    // reduce string for faster encryption
    if ($start == 0 && $length == 0 && strlen ($string) > 32) $string = substr ($string, -32);

    // encoding algorithm
    $string_encoded = hash_hmac ("sha1", $string, $mgmt_config['crypt_key']);

    // extract substring
    if ($length != 0) $string_encoded = substr ($string_encoded, $start, $length);
    elseif ($start != 0) $string_encoded = substr ($string_encoded, $start);

    // urlencode string
    $string_encoded = urlencode ($string_encoded);

    if ($string_encoded != "") return $string_encoded;
  }

  return false;
}

// ---------------------- hcms_encrypt -----------------------------
// function: hcms_encrypt()
// input: string to encode [string], key of length 16 or 24 or 32 [string] (optional), crypt strength level [weak,standard,strong] (optional), 
//        encoding [base64,url,none] (optional)
// output: encoded string / false on error

// description:
// Encryption of a string. Only strong encryption is binary-safe.

function hcms_encrypt ($string, $key="", $crypt_level="", $encoding="url")
{
  global $mgmt_config;

  // initialize
  $error = array();

  if ($string != "")
  {
    // define crypt level
    if ($crypt_level == "" && !empty ($mgmt_config['crypt_level'])) $crypt_level = strtolower ($mgmt_config['crypt_level']);
    else $crypt_level = strtolower ($crypt_level);

    // define key
    if ($crypt_level == "strong")
    {
      if ($key == "" && !empty ($mgmt_config['aes256_key'])) $key = $mgmt_config['aes256_key'];
      else $key = "h1y2p3e4r5c6m7s8s9m0c1r2e3p4y5h6";

      // key of length 32 is required for AES 256
      if (mb_strlen ($key, '8bit') !== 32) return false;
    }
    else
    {
      if ($key == "" && !empty ($mgmt_config['crypt_key'])) $key = $mgmt_config['crypt_key'];
      else $key = "h1y2p3e4r5c6m7s8";
    }

    // PHP7 does not support mcrypt anymore

    // strong (binary-safe)
    if ($crypt_level == "strong" && (function_exists ("openssl_decrypt") || function_exists ("mcrypt_get_iv_size")))
    {
      // use OpenSSL if available with AES 256 encryption (requires a key with 32 digits)
      if (function_exists ("openssl_encrypt"))
      {
        $method = "aes-256-cbc";
        $ivsize = openssl_cipher_iv_length ($method);
        $iv = openssl_random_pseudo_bytes ($ivsize);

        // Encrypt $data using aes-256-cbc cipher with the given encryption key and 
        // initialization vector. The 0 gives us the default options, but can
        // be changed to OPENSSL_RAW_DATA or OPENSSL_ZERO_PADDING
        $hash = openssl_encrypt ($string, $method, $key, OPENSSL_RAW_DATA, $iv);
        $hash = $iv.$hash;
      }
      // deprecated since PHP 7: use PHP Mcrypt with AES 256 encryption (requires a key with 32 digits)
      elseif (function_exists ("mcrypt_get_iv_size"))
      {
        // base 64 encode binary data to be binary-safe
        $string = base64_encode ($string);

        // MCRYPT_MODE_CBC (cipher block chaining) 
        // is especially suitable for encrypting files where the security is increased over ECB significantly.
        $ivSize = mcrypt_get_iv_size (MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $iv = mcrypt_create_iv ($ivSize, MCRYPT_RAND);
        $hash = mcrypt_encrypt (MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_CBC, $iv);
        $hash = $iv.$hash;
      }
    }
    // standard
    elseif ($crypt_level == "standard" && (function_exists ("openssl_decrypt") || function_exists ("mcrypt_get_iv_size")))
    {
      // use OpenSSL if available with AES 128 encryption (requires a key with 32 digits)
      if (function_exists ("openssl_encrypt"))
      {
        $method = "aes-128-cbc";
        $ivsize = openssl_cipher_iv_length ($method);
        $iv = openssl_random_pseudo_bytes ($ivsize);

        // Encrypt $data using aes-256-cbc cipher with the given encryption key and 
        // initialization vector. The 0 gives us the default options, but can
        // be changed to OPENSSL_RAW_DATA or OPENSSL_ZERO_PADDING
        $hash = openssl_encrypt ($string, $method, $key, OPENSSL_RAW_DATA, $iv);
        $hash = $iv.$hash;
      }
      // deprecated since PHP 7: MCRYPT_MODE_ECB (electronic codebook) 
      // is suitable for random data, such as encrypting other keys. Since data there is short and random, the disadvantages of ECB have a favorable negative effect.
      // use PHP Mcrypt with AES 256 encryption (requires a key with 32 digits)
      elseif (function_exists ("mcrypt_get_iv_size"))
      {
        $ivsize = mcrypt_get_iv_size (MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv ($ivsize, MCRYPT_RAND);
        $hash = mcrypt_encrypt (MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_ECB, $iv);
      }
    }
    // weak
    // main purpose is to gain a short encrypted string, please don't use it for sensitive data or files!
    else
    {
      $key = sha1 ($key);
      $strLen = strlen ($string);
      $keyLen = strlen ($key);
      $j = 0;
      $hash = "";

      for ($i = 0; $i < $strLen; $i++)
      {
        $ordStr = ord (substr ($string, $i, 1));
        if ($j == $keyLen) $j = 0;
        $ordKey = ord (substr ($key, $j, 1));
        $j++;
        $hash .= strrev (base_convert (dechex ($ordStr + $ordKey), 16, 36));
      }

      if ($crypt_level != "weak")
      {
        // warning
        $errcode = "00110";
        $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|warning|".$errcode."|Fallback to crypt level 'weak' due to missing support of stronger encryption technologies";

        savelog (@$error);
      }
    }

    if ($hash != "")
    {
      // base64 encoding to be used to encode binary files (stronlgy recommended due to issues with OpenSSL encryption and decryption)
      if (strtolower($encoding) == "base64") return base64_encode ($hash);
      // to be used for strings passed via GET (base64 encoding will be applied as well in order to be binary safe)
      // base64 uses A-z, a-z. 0-9, /, +, = as characters and need to be url encoded.
      // since we don't want to decode and encode the string again when passing from one to another script, we escape the % character used for url encoding to avoid 
      // the en- and decoding
      elseif (strtolower($encoding) == "url") return str_replace ("%", ".", urlencode (base64_encode ($hash)));
      // no encoding
      else return $hash;
    }
  }

  return false;
}

// ---------------------- hcms_decrypt -----------------------------
// function: hcms_decrypt()
// input: hash-string to decode [string], key of length 16 or 24 or 32 [string] (optional), crypt strength level [weak,standard,strong] (optional), 
//        encoding [base64,url,none] (optional)
// output: decoded string / false on error

// description:
// Decryption of a string. Only strong encryption is binary-safe.

function hcms_decrypt ($string, $key="", $crypt_level="", $encoding="url")
{
  global $mgmt_config;

  if ($string != "")
  {
    // define crypt level
    if ($crypt_level == "" && !empty ($mgmt_config['crypt_level'])) $crypt_level = strtolower ($mgmt_config['crypt_level']);
    else $crypt_level = strtolower ($crypt_level);

    // define key
    if ($crypt_level == "strong")
    {
      if ($key == "" && !empty ($mgmt_config['aes256_key'])) $key = $mgmt_config['aes256_key'];
      else $key = "h1y2p3e4r5c6m7s8s9m0c1r2e3p4y5h6";

      // key of length 32 is required for AES 256
      if (mb_strlen ($key, '8bit') !== 32) return false;
    }
    else
    {
      if ($key == "" && !empty ($mgmt_config['crypt_key'])) $key = $mgmt_config['crypt_key'];
      else $key = "h1y2p3e4r5c6m7s8";
    }

    // to be used to decode files
    if (strtolower ($encoding) == "base64") $string = base64_decode ($string);
    // to be used for strings passed via GET 
    elseif (strtolower($encoding) == "url") $string = base64_decode (urldecode (str_replace (".", "%", $string)));

    // strong (binary-safe)
    if ($crypt_level == "strong" && (function_exists ("openssl_decrypt") || function_exists ("mcrypt_get_iv_size")))
    {
      // use OpenSSL if available with AES 256 decryption (requires a key with 32 digits)
      if (function_exists ("openssl_decrypt"))
      {
        $method = "aes-256-cbc";
        $ivsize = openssl_cipher_iv_length ($method);
        $iv = mb_substr ($string, 0, $ivsize, '8bit');
        $string = mb_substr ($string, $ivsize, null, '8bit');

        $hash_decrypted = openssl_decrypt ($string, $method, $key, OPENSSL_RAW_DATA, $iv);
      }
      elseif (function_exists ("mcrypt_get_iv_size"))
      {
        // deprecated since PHP 7: MCRYPT_MODE_CBC (cipher block chaining) 
        // is especially suitable for encrypting files where the security is increased over ECB significantly.
        $ivsize = mcrypt_get_iv_size (MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        if (strlen ($string) < $ivsize) return false;
        $iv = substr ($string, 0, $ivsize);
        $string = substr ($string, $ivsize);
        $hash_decrypted = mcrypt_decrypt (MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_CBC, $iv);
        $hash_decrypted = rtrim ($hash_decrypted, "\0");

        // base 64 decode (binary-safe)
        $hash_decrypted = base64_decode ($hash_decrypted);
      }
    }
    // standard
    elseif ($crypt_level == "standard" && function_exists ("mcrypt_get_iv_size"))
    {
      // use OpenSSL if available with AES 256 decryption (requires a key with 32 digits)
      if (function_exists ("openssl_decrypt"))
      {
        $method = "aes-128-cbc";
        $ivsize = openssl_cipher_iv_length ($method);
        $iv = mb_substr ($string, 0, $ivsize, '8bit');
        $string = mb_substr ($string, $ivsize, NULL, '8bit');

        $hash_decrypted = openssl_decrypt ($string, $method, $key, OPENSSL_RAW_DATA, $iv);
      }
      // deprecated since PHP 7: MCRYPT_MODE_ECB (electronic codebook) 
      // is suitable for random data, such as encrypting other keys. Since data there is short and random, the disadvantages of ECB have a favorable negative effect.
      elseif (function_exists ("mcrypt_get_iv_size"))
      {
        $ivsize = mcrypt_get_iv_size (MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv ($ivsize, MCRYPT_RAND);
        $hash_decrypted = mcrypt_decrypt (MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_ECB, $iv);
        $hash_decrypted = rtrim ($hash_decrypted, "\0");
      }
    }
    // weak
    else
    {
      $key = sha1 ($key);
      $strLen = strlen ($string);
      $keyLen = strlen ($key);
      $j = 0;
      $hash_decrypted = "";

      for ($i = 0; $i < $strLen; $i+=2)
      {
        $ordStr = hexdec (base_convert (strrev (substr ($string, $i, 2)), 36, 16));
        if ($j == $keyLen) $j = 0;
        $ordKey = ord (substr ($key, $j, 1));
        $j++;
        $hash_decrypted .= chr ($ordStr - $ordKey);
      }
    }

    if ($hash_decrypted != "") return $hash_decrypted;
  }

  return false;
}

// ---------------------- createtimetoken -----------------------------
// function: createtimetoken()
// input: token lifetime in seconds [integer] (optional), secret value [integer] (optional)
// output: token / false on error

function createtimetoken ($lifetime=0, $secret=4)
{
  global $mgmt_config;

  if ($lifetime != "" && $secret > 0)
  {
    // create timestamp
    $timestamp = time() + intval ($lifetime);

    // create token
    $timetoken = round ($timestamp / intval ($secret), 0, PHP_ROUND_HALF_UP);

    // shift mode
    $shiftmode = rand (0, 5);

    // apply shift mode
    $timetoken = substr ($timetoken, $shiftmode).substr ($timetoken, 0, $shiftmode);

    return $shiftmode.$timetoken;
  }

  return false;
}

// ---------------------- checktimetoken -----------------------------
// function: checktimetoken()
// input: token [string], secret value [integer] (optional)
// output: true / false

function checktimetoken ($token, $secret=4)
{
  global $mgmt_config;

  if ($token != "" && $secret > 0)
  {
    // get shift mode
    $shiftmode = strlen ($token) - 1 - substr ($token, 0, 1);

    // reverse shift mode
    $timetoken = substr ($token, 1);
    $timetoken = substr ($timetoken, $shiftmode).substr ($timetoken, 0, $shiftmode);

    // get time stamp
    $timestamp = intval ($timetoken) * $secret;

    // check if token is valid
    if ($timestamp >= time() || $timestamp == 0) return true;
  }

  return false;
}

// ---------------------- createtoken -----------------------------
// function: createtoken()
// input: user name [string] (optional), token lifetime in seconds [integer] (optional), secret value [integer] (optional)
// output: token / false on error

function createtoken ($user="sys", $lifetime=0, $secret=4)
{
  global $mgmt_config;

  if ($user != "")
  {
    // token lifetime
    if ($lifetime == 0)
    {
      // default lifetime of token (valid for one day from now)
      if (!empty ($mgmt_config['token_lifetime']))
      {
        if ($mgmt_config['token_lifetime'] < 60) $lifetime = 86400;
        else $lifetime = intval ($mgmt_config['token_lifetime']);
      }
      else $lifetime = 86400;
    }

    // create token
    $timetoken = createtimetoken ($lifetime, $secret);

    // create security token
    $token = hcms_encrypt ($timetoken."@".$user);

    return $token;
  }

  return false;
}

// ---------------------- checktoken -----------------------------
// function: checktoken()
// input: token [string], user name [string] (optional), secret value [integer] (optional)
// output: true / false

function checktoken ($token, $user="sys", $secret=4)
{
  global $mgmt_config;

  if ($token != "" && $user != "")
  {
    // decrypt token
    $token = hcms_decrypt ($token);

    // extract user name and timestamp (only first @ is the seperator)
    if (!empty ($token)) list ($timetoken, $token_user) = explode ("@", $token, 2);

    // check if token is valid
    if (!empty ($timetoken) && !empty ($token_user))
    {
      if (checktimetoken ($timetoken, $secret) && $user == $token_user) return true;
    }
  }

  return false;
}

// ---------------------- createuniquetoken -----------------------------
// function: createuniquetoken()
// input: token length [integer] (optional)
// output: token as string / false

function createuniquetoken ($length=16)
{
  global $mgmt_config;

  if ($length > 0 && $length <= 32)
  {
    $characters = "abcdefghijklmnopqrstuvwxyz0123456789";
    $string = "";

    for ($i = 0; $i < $length; $i++)
    {
      $string .= substr ($characters, rand_secure(0, strlen($characters) - 1), 1);
    }

    if ($string != "") return $string;
  }

  return false;
}

// ---------------------- createpassword -----------------------------
// function: createpassword()
// input: password length [integer] (optional)
// output: password as string / false

function createpassword ($length=10)
{
  global $mgmt_config;

  if ($length > 0 && $length <= 16)
  {
    $characters = "ABCDEFGHIJKLMNMOPQRSTUVWZYZabcdefghijklmnopqrstuvwxyz0123456789";
    $string = "";

    for ($i = 0; $i < $length; $i++)
    {
      $string .= substr ($characters, rand_secure(0, strlen($characters) - 1), 1);
    }

    if ($string != "") return $string;
  }

  return false;
}

// ---------------------- rand_secure -----------------------------
// function: rand_secure()
// input: min and max value [integer] (optional)
// output: secure random number / false

function rand_secure ($min=1000, $max=999999999999)
{
  if ($min < $max)
  {
    if (function_exists ("openssl_random_pseudo_bytes"))
    {
      $range = $max - $min;
      $log = log ($range, 2);

      // length in bytes
      $bytes = (int) ($log / 8) + 1;

      // length in bits
      $bits = (int) $log + 1;

      // set all lower bits to 1
      $filter = (int) (1 << $bits) - 1;

      do
      {
        $rnd = hexdec (bin2hex (openssl_random_pseudo_bytes($bytes)));
        
        // discard irrelevant bits
        $rnd = $rnd & $filter;
      }
      while ($rnd >= $range);

      return $min + $rnd;
    }
    else return mt_rand ($min, $max);
  }
  else return false;
}
?>