<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
 // ===================================== PERMISSIONS =========================================

// ---------------------- rootpermission -----------------------------
// function: rootpermission()
// input: publication name, publication admin, permission string from group 
// output: global permission array/false

// description:
// deseralizes the permission string and and returns the root permission array.

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
    if (!isset ($rootpermission['desktop'])) $rootpermission['desktop'] = 0;
    if (!isset ($rootpermission['desktopsetting'])) $rootpermission['desktopsetting'] = 0;
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
    
    while (list ($group_name, $value) = each ($permission_str[$site_name]))
    {
      if ($group_name != "" && $value != "")
      {
        // get permissions from string
        parse_str ($value);
    
        // desktop permissions
        if ($rootpermission['desktop'] == 0 && $desktop[0] == 1) $rootpermission['desktop'] = 1;
        if ($rootpermission['desktopsetting'] == 0 && $desktop[1] == 1) $rootpermission['desktopsetting'] = 1;
        if ($rootpermission['desktoptaskmgmt'] == 0 && $desktop[2] == 1) $rootpermission['desktoptaskmgmt'] = 1;
        if ($rootpermission['desktopcheckedout'] == 0 && $desktop[3] == 1) $rootpermission['desktopcheckedout'] = 1;       
        if ($rootpermission['desktoptimetravel'] == 0 && $desktop[4] == 1) $rootpermission['desktoptimetravel'] = 1;
        if ($rootpermission['desktopfavorites'] == 0 && $desktop[5] == 1) $rootpermission['desktopfavorites'] = 1;    

        if ($site_admin == true)
        {
          // site permissions
          if ($rootpermission['site'] == 0 && $site[0] == 1) $rootpermission['site'] = 1;
          if ($rootpermission['sitecreate'] == 0 && $site[1] == 1) $rootpermission['sitecreate'] = 1;
          if ($rootpermission['sitedelete'] == 0 && $site[2] == 1) $rootpermission['sitedelete'] = 1;
          if ($rootpermission['siteedit'] == 0 && $site[3] == 1) $rootpermission['siteedit'] = 1;   
          // user permissions
          if ($rootpermission['user'] == 0 && $user[0] == 1) $rootpermission['user'] = 1;
          if ($rootpermission['usercreate'] == 0 && $user[1] == 1) $rootpermission['usercreate'] = 1;
          if ($rootpermission['userdelete'] == 0 && $user[2] == 1) $rootpermission['userdelete'] = 1;
          if ($rootpermission['useredit'] == 0 && $user[3] == 1) $rootpermission['useredit'] = 1;         
        }  
      }
    }
    
    if (is_array ($rootpermission)) 
    {
      return $rootpermission;
    }
    else return false;
  }
  else return false;
}
    
// ---------------------- globalpermission -----------------------------
// function: globalpermission()
// input: publication, permission string from group 
// output: global permission array/false

// description:
// deseralizes the permission string and returns the global permission array.

function globalpermission ($site_name, $permission_str)
{
  if (is_array ($permission_str) && valid_publicationname ($site_name))
  {  
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
   
    while (list ($group_name, $value) = each ($permission_str[$site_name]))
    {
      if ($group_name != "" && $value != "")
      {
        // get permissions from string
        parse_str ($value);

        // user permissions
        if ($globalpermission[$site_name]['user'] == 0 && $user[0] == 1) $globalpermission[$site_name]['user'] = 1;
        if ($globalpermission[$site_name]['usercreate'] == 0 && $user[1] == 1) $globalpermission[$site_name]['usercreate'] = 1;
        if ($globalpermission[$site_name]['userdelete'] == 0 && $user[2] == 1) $globalpermission[$site_name]['userdelete'] = 1;
        if ($globalpermission[$site_name]['useredit'] == 0 && $user[3] == 1) $globalpermission[$site_name]['useredit'] = 1;
        // group permissions
        if ($globalpermission[$site_name]['group'] == 0 && $group[0] == 1) $globalpermission[$site_name]['group'] = 1;
        if ($globalpermission[$site_name]['groupcreate'] == 0 && $group[1] == 1) $globalpermission[$site_name]['groupcreate'] = 1;
        if ($globalpermission[$site_name]['groupdelete'] == 0 && $group[2] == 1) $globalpermission[$site_name]['groupdelete'] = 1;
        if ($globalpermission[$site_name]['groupedit'] == 0 && $group[3] == 1) $globalpermission[$site_name]['groupedit'] = 1;
        // personalization permissions
        if ($globalpermission[$site_name]['pers'] == 0 && $pers[0] == 1) $globalpermission[$site_name]['pers'] = 1;
        if ($globalpermission[$site_name]['perstrack'] == 0 && $pers[1] == 1) $globalpermission[$site_name]['perstrack'] = 1;
        if ($globalpermission[$site_name]['perstrackcreate'] == 0 && $pers[2] == 1) $globalpermission[$site_name]['perstrackcreate'] = 1;
        if ($globalpermission[$site_name]['perstrackdelete'] == 0 && $pers[3] == 1) $globalpermission[$site_name]['perstrackdelete'] = 1;
        if ($globalpermission[$site_name]['perstrackedit'] == 0 && $pers[4] == 1) $globalpermission[$site_name]['perstrackedit'] = 1;
        if ($globalpermission[$site_name]['persprof'] == 0 && $pers[5] == 1) $globalpermission[$site_name]['persprof'] = 1;
        if ($globalpermission[$site_name]['persprofcreate'] == 0 && $pers[6] == 1) $globalpermission[$site_name]['persprofcreate'] = 1;
        if ($globalpermission[$site_name]['persprofdelete'] == 0 && $pers[7] == 1) $globalpermission[$site_name]['persprofdelete'] = 1;
        if ($globalpermission[$site_name]['persprofedit'] == 0 && $pers[8] == 1) $globalpermission[$site_name]['persprofedit'] = 1;
        // workflow permissions
        if ($globalpermission[$site_name]['workflow'] == 0 && $workflow[0] == 1) $globalpermission[$site_name]['workflow'] = 1;
        if ($globalpermission[$site_name]['workflowproc'] == 0 && $workflow[1] == 1) $globalpermission[$site_name]['workflowproc'] = 1;
        if ($globalpermission[$site_name]['workflowproccreate'] == 0 && $workflow[2] == 1) $globalpermission[$site_name]['workflowproccreate'] = 1;
        if ($globalpermission[$site_name]['workflowprocdelete'] == 0 && $workflow[3] == 1) $globalpermission[$site_name]['workflowprocdelete'] = 1;
        if ($globalpermission[$site_name]['workflowprocedit'] == 0 && $workflow[4] == 1) $globalpermission[$site_name]['workflowprocedit'] = 1;
        if ($globalpermission[$site_name]['workflowprocfolder'] == 0 && $workflow[5] == 1) $globalpermission[$site_name]['workflowprocfolder'] = 1;
        if ($globalpermission[$site_name]['workflowscript'] == 0 && $workflow[6] == 1) $globalpermission[$site_name]['workflowscript'] = 1;
        if ($globalpermission[$site_name]['workflowscriptcreate'] == 0 && $workflow[7] == 1) $globalpermission[$site_name]['workflowscriptcreate'] = 1;
        if ($globalpermission[$site_name]['workflowscriptdelete'] == 0 && $workflow[8] == 1) $globalpermission[$site_name]['workflowscriptdelete'] = 1;
        if ($globalpermission[$site_name]['workflowscriptedit'] == 0 && $workflow[9] == 1) $globalpermission[$site_name]['workflowscriptedit'] = 1;      
        // template permissions
        if ($globalpermission[$site_name]['template'] == 0 && $template[0] == 1) $globalpermission[$site_name]['template'] = 1;
        if ($globalpermission[$site_name]['tpl'] == 0 && $template[1] == 1) $globalpermission[$site_name]['tpl'] = 1;
        if ($globalpermission[$site_name]['tplcreate'] == 0 && $template[2] == 1) $globalpermission[$site_name]['tplcreate'] = 1;
        if (isset ($template[5]) && $template[5] != "") // older versions before 5.5.11 (template upload still exists)
        {
          if ($globalpermission[$site_name]['tpldelete'] == 0 && $template[4] == 1) $globalpermission[$site_name]['tpldelete'] = 1;
          if ($globalpermission[$site_name]['tpledit'] == 0 && $template[5] == 1) $globalpermission[$site_name]['tpledit'] = 1;
        }
        else
        {
          if ($globalpermission[$site_name]['tpldelete'] == 0 && $template[3] == 1) $globalpermission[$site_name]['tpldelete'] = 1;
          if ($globalpermission[$site_name]['tpledit'] == 0 && isset ($template[4]) && $template[4] == 1) $globalpermission[$site_name]['tpledit'] = 1;
        }
        // template media permissions
        if ($globalpermission[$site_name]['tplmedia'] == 0 && $media[0] == 1) $globalpermission[$site_name]['tplmedia'] = 1;
        if ($globalpermission[$site_name]['tplmediacatcreate'] == 0 && $media[1] == 1) $globalpermission[$site_name]['tplmediacatcreate'] = 1;
        if ($globalpermission[$site_name]['tplmediacatdelete'] == 0 && $media[2] == 1) $globalpermission[$site_name]['tplmediacatdelete'] = 1;
        if ($globalpermission[$site_name]['tplmediacatrename'] == 0 && $media[3] == 1) $globalpermission[$site_name]['tplmediacatrename'] = 1;
        if ($globalpermission[$site_name]['tplmediaupload'] == 0 && $media[4] == 1) $globalpermission[$site_name]['tplmediaupload'] = 1;
        if ($globalpermission[$site_name]['tplmediadelete'] == 0 && $media[5] == 1) $globalpermission[$site_name]['tplmediadelete'] = 1;
        // component permissions
        if ($globalpermission[$site_name]['component'] == 0 && $component[0] == 1) $globalpermission[$site_name]['component'] = 1;
        // content permissions
        if ($globalpermission[$site_name]['page'] == 0 && $page[0] == 1) $globalpermission[$site_name]['page'] = 1;     
      }  
    }
    
    if (is_array ($globalpermission[$site_name])) 
    {
      return $globalpermission;
    }
    else return false;
  }
  else return false;
}

// ---------------------- localpermission -----------------------------
// function: localpermission()
// input: publication, permission string from group 
// output: local permission array/false

// description:
// deseralizes the permission string and returns the local permission array.

function localpermission ($site_name, $permission_str)
{
  if (is_array ($permission_str) && valid_publicationname ($site_name))
  {  
    reset ($permission_str);
  
    while (list ($group_name, $value) = each ($permission_str[$site_name]))
    {
      if ($group_name != "" && $value != "")
      {
        // get permissions from string
        parse_str ($value);
  
        // component permissions
        $localpermission[$site_name][$group_name]['component'] = $component[0];
        $localpermission[$site_name][$group_name]['compupload'] = $component[1];
        $localpermission[$site_name][$group_name]['compdownload'] = $component[2];
        $localpermission[$site_name][$group_name]['compsendlink'] = $component[3]; 
        $localpermission[$site_name][$group_name]['compfoldercreate'] = $component[4];
        $localpermission[$site_name][$group_name]['compfolderdelete'] = $component[5];
        $localpermission[$site_name][$group_name]['compfolderrename'] = $component[6];
        $localpermission[$site_name][$group_name]['compcreate'] = $component[7];
        $localpermission[$site_name][$group_name]['compdelete'] = $component[8];
        $localpermission[$site_name][$group_name]['comprename'] = $component[8];
        $localpermission[$site_name][$group_name]['comppublish'] = $component[10];
        // content permissions
        $localpermission[$site_name][$group_name]['page'] = $page[0];
        $localpermission[$site_name][$group_name]['pagesendlink'] = $page[1]; 
        $localpermission[$site_name][$group_name]['pagefoldercreate'] = $page[2];
        $localpermission[$site_name][$group_name]['pagefolderdelete'] = $page[3];
        $localpermission[$site_name][$group_name]['pagefolderrename'] = $page[4];
        $localpermission[$site_name][$group_name]['pagecreate'] = $page[5];
        $localpermission[$site_name][$group_name]['pagedelete'] = $page[6];
        $localpermission[$site_name][$group_name]['pagerename'] = $page[7];
        $localpermission[$site_name][$group_name]['pagepublish'] = $page[8];
      }  
    }
    
    if (is_array ($localpermission[$site_name])) 
    {
      return $localpermission;
    }
    else return false;
  }
  else return false;
}

// ---------------------------------- accessgeneral -------------------------------------------
// function: accessgeneral()
// input: publication, location (path to folder), object category ['page, comp']
// output: true/false

// description: checks general access to certain system folders, publications and returns true if access is granted

function accessgeneral ($site, $location, $cat)
{
  global $mgmt_config, $hiddenfolder, $siteaccess;   

  if (valid_publicationname ($site) && valid_locationname ($location) && is_array ($mgmt_config))
  {
    // load config if not available
    if ((!isset ($mgmt_config[$site]) || !is_array ($mgmt_config[$site])) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }     
   
    // define category if undefined
    if ($cat == "") $cat = getcategory ($site, $location);     
    
    if (@substr_count ($location, "://") > 0)
    {
      if ($cat == "page") $location = str_replace ($mgmt_config[$site]['url_path_page'], $mgmt_config[$site]['abs_path_page'], $location);
      elseif ($cat == "comp") $location = str_replace ($mgmt_config['url_path_comp'], $mgmt_config['abs_path_comp'], $location);
    }
    elseif (substr_count ($location, "%page%") == 1 || substr_count ($location, "%comp%") == 1)
    {
      $location = deconvertpath ($location, "file");
    }
    
    // cut off file name 
    if (@is_file ($location) && $location[strlen ($location)-1] != "/")
    {
      $location = substr ($location, 0, strrpos ($location, "/") + 1);
    }
    elseif (@is_dir ($location) && $location[strlen ($location)-1] != "/")
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
    if (is_array ($siteaccess))
    {
      if (!checkpublicationpermission ($site)) return false;
    }

    // check excluded folders
    if (isset ($hiddenfolder[$site]) && is_array ($hiddenfolder[$site]))  
    {   
      foreach ($hiddenfolder[$site] as $exclude_folder)
      {
        if (@substr_count ($location_esc, $exclude_folder) > 0) return false;
      }
    }

    // check data root and cms root
    if (@substr_count ($location, $mgmt_config['abs_path_cms']) == 0 && @substr_count ($location, $mgmt_config['abs_path_data']) == 0)
    {
      // check page access
      if ($cat == "page" && @substr_count ($location, $mgmt_config[$site]['abs_path_page']) > 0 && @substr_count ($location, $mgmt_config['abs_path_rep']) == 0) 
      {   
        return true;
      }
      // check component access
      elseif ($cat == "comp" && @substr_count ($location, $mgmt_config['abs_path_comp']) > 0)
      { 
        return true;     
      }  
      else return false;
    }
    else return false;
  }
  else return false;
}

// ---------------------------------- accesspermission -------------------------------------------
// function: accesspermission()
// input: location (path to folder), object category ['page, comp']
// output: group with access permissions as array / false on error
// requires: accessgeneral

// description:
// evaluates page and component access permissions and returns group(s).

function accesspermission ($site, $location, $cat)
{
  global $pageaccess, $compaccess, $hiddenfolder, $hcms_linking, $mgmt_config;   

  if (valid_publicationname ($site) && valid_locationname ($location) && is_array ($mgmt_config))
  { 
    // load config if not available
    if ((!isset ($mgmt_config[$site]) || !is_array ($mgmt_config[$site])) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
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
    
    // cut off file name 
    if (@is_file ($location)) $location = getlocation ($location);
    
    // add slash if not present at the end of the location string
    if (substr ($location, -1) != "/") $location = $location."/";     
    
    // check general access permissions
    $access_passed = accessgeneral ($site, $location, $cat);

    // check if hcms linking is available and current location is inside linking location
    if ($access_passed == true && isset ($_SESSION['hcms_linking']) && is_array ($_SESSION['hcms_linking']))
    {
      // reset from session to be sure it is correct 
      $hcms_linking = $_SESSION['hcms_linking'];
      
      // check if inside linking location
      if (valid_locationname ($hcms_linking['location']) && substr_count ($location, $hcms_linking['location']) > 0) $access_passed = true;
      else $access_passed = false;
    }    

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
        while (list ($group, $value) = each ($thisaccess))
        {
          // split access-string into an array
          $value = substr ($value, 0, strlen ($value)-1);
          $value_array = explode ("|", $value);
          
          // access locations
          foreach ($value_array as $value)
          {
            if (@substr_count ($location, $value) >= 1)
            {
              $points[$i] = strlen ($value);
              $groups[$i] = $group;
              $i++;
            }              
          }
        }
      }
      // check component access (must hold absolute path values)
      elseif ($cat == "comp" && isset ($compaccess[$site]) && is_array ($compaccess[$site]))
      {
        reset ($compaccess);          
        $thisaccess = $compaccess[$site];        
        $i = 0;
        
        // groups
        while (list ($group, $value) = each ($thisaccess))
        {
          // split access-string into an array
          $value = substr ($value, 0, strlen ($value)-1);
          $value_array = explode ("|", $value);
          
          // access locations
          foreach ($value_array as $value)
          {
            if (@substr_count ($location, $value) >= 1)
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
        
        while (list ($id, $point) = each ($points))
        {
          if ($point == $max) $result[] = $groups[$id];
        }

        if (is_array ($result) && sizeof ($result) > 0) return $result;
      }
      // return deafult group as result if no group was located and hcms linking exists
      elseif (isset ($_SESSION['hcms_linking']) && is_array ($_SESSION['hcms_linking']))
      {
        $result[] = "default";
        return $result;
      }
      else return false;
    }
    else return false;
  }
  else return false;
}

// ---------------------- setlocalpermission -----------------------------
// function: setlocalpermission()
// input: publication, group name array, object category [page,comp]
// output: local permission array / false on error

// description:
// sets local permissions of a user group for a specific publication

function setlocalpermission ($site, $group_array, $cat)
{
  global $localpermission;

  // try to get localpermission from session
  if ((!isset ($localpermission) || !is_array ($localpermission)) && isset ($_SESSION['hcms_localpermission'])) $localpermission = $_SESSION['hcms_localpermission'];
  
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
      // component permissions
      if ($cat == "comp")
      {
        if ($setlocalpermission['root'] == 0 && $localpermission[$site][$group]['component'] == 1) $setlocalpermission['root'] = 1;
        if ($setlocalpermission['upload'] == 0 && $localpermission[$site][$group]['compupload'] == 1) $setlocalpermission['upload'] = 1;
        if ($setlocalpermission['download'] == 0 && $localpermission[$site][$group]['compdownload'] == 1) $setlocalpermission['download'] = 1;
        if ($setlocalpermission['sendlink'] == 0 && $localpermission[$site][$group]['compsendlink'] == 1) $setlocalpermission['sendlink'] = 1;  
        if ($setlocalpermission['foldercreate'] == 0 && $localpermission[$site][$group]['compfoldercreate'] == 1) $setlocalpermission['foldercreate'] = 1;
        if ($setlocalpermission['folderdelete'] == 0 && $localpermission[$site][$group]['compfolderdelete'] == 1) $setlocalpermission['folderdelete'] = 1;
        if ($setlocalpermission['folderrename'] == 0 && $localpermission[$site][$group]['compfolderrename'] == 1) $setlocalpermission['folderrename'] = 1;
        if ($setlocalpermission['create'] == 0 && $localpermission[$site][$group]['compcreate'] == 1) $setlocalpermission['create'] = 1; 
        if ($setlocalpermission['delete'] == 0 && $localpermission[$site][$group]['compdelete'] == 1) $setlocalpermission['delete'] = 1;
        if ($setlocalpermission['rename'] == 0 && $localpermission[$site][$group]['comprename'] == 1) $setlocalpermission['rename'] = 1;
        if ($setlocalpermission['publish'] == 0 && $localpermission[$site][$group]['comppublish'] == 1) $setlocalpermission['publish'] = 1;
      }      
      // content permissions
      elseif ($cat == "page")
      {      
        if ($setlocalpermission['root'] == 0 && $localpermission[$site][$group]['page'] == 1) $setlocalpermission['root'] = 1;
        if ($setlocalpermission['sendlink'] == 0 && $localpermission[$site][$group]['pagesendlink'] == 1) $setlocalpermission['sendlink'] = 1;
        if ($setlocalpermission['foldercreate'] == 0 && $localpermission[$site][$group]['pagefoldercreate'] == 1) $setlocalpermission['foldercreate'] = 1;
        if ($setlocalpermission['folderdelete'] == 0 && $localpermission[$site][$group]['pagefolderdelete'] == 1) $setlocalpermission['folderdelete'] = 1;
        if ($setlocalpermission['folderrename'] == 0 && $localpermission[$site][$group]['pagefolderrename'] == 1) $setlocalpermission['folderrename'] = 1;
        if ($setlocalpermission['create'] == 0 && $localpermission[$site][$group]['pagecreate'] == 1) $setlocalpermission['create'] = 1;
        if ($setlocalpermission['delete'] == 0 && $localpermission[$site][$group]['pagedelete'] == 1) $setlocalpermission['delete'] = 1;
        if ($setlocalpermission['rename'] == 0 && $localpermission[$site][$group]['pagerename'] == 1) $setlocalpermission['rename'] = 1;
        if ($setlocalpermission['publish'] == 0 && $localpermission[$site][$group]['pagepublish'] == 1) $setlocalpermission['publish'] = 1;
      }   
    }
  }
  
  return $setlocalpermission;
}

// ------------------------- checkpublicationpermission -----------------------------
// function: checkpublicationpermission()
// input: publication name, strictly limited to siteaccess only without inheritance [true/false] (optional)
// output: "direct" for direct access via group permission / "inherited" for access through inheritance / false

// description:
// checks access to a publication based on the site access and inheritance settings

function checkpublicationpermission ($site, $strict=true)
{
  global $mgmt_config, $siteaccess;
  
  if (!is_array ($siteaccess) && isset ($_SESSION['hcms_siteaccess'])) $siteaccess = $_SESSION['hcms_siteaccess'];
  
  if (valid_publicationname ($site) && is_array ($siteaccess))
  {
    // publication is in scope of user
    if (in_array ($site, $siteaccess)) return "direct";
    elseif ($strict == true) return false;
    
    // load publication inheritance setting
    $inherit_db = inherit_db_read ();
    $child_array = inherit_db_getchild ($inherit_db, $site);

    // check access to publication by inheritance
    if (is_array ($child_array))
    {
      foreach ($siteaccess as $child)
      {
        // load child publication settings
        if (valid_publicationname ($child)) @require ($mgmt_config['abs_path_data']."config/".$child.".conf.php");
        // check component access
        if (in_array ($child, $child_array) && $mgmt_config[$child]['inherit_comp'] == true) return "inherited";
      }
      
      return false;
    }
    else return false;
  }
  else return false;
}

// ---------------------- checkadminpermission -----------------------------
// function: checkadminpermission()
// input: %
// output: true/false

// description:
// checks super admin permission

function checkadminpermission ()
{
  global $adminpermission;
  
  // try to get localpermission from session
  if ((!isset ($adminpermission) || !is_array ($adminpermission)) && isset ($_SESSION['hcms_superadmin'])) $adminpermission = $_SESSION['hcms_superadmin'];
  
  // root permission
  if (isset ($adminpermission))
  {
    if ($adminpermission == 1) return true;
    else return false;
  }
  else return false;
}

// ---------------------- checkrootpermission -----------------------------
// function: checkrootpermission()
// input: permission name
// output: true/false

// description:
// checks root permission

function checkrootpermission ($name)
{
  global $rootpermission;
  
  if (valid_objectname ($name))
  {
    // try to get localpermission from session
    if ((!isset ($rootpermission) || !is_array ($rootpermission)) && isset ($_SESSION['hcms_rootpermission'])) $rootpermission = $_SESSION['hcms_rootpermission'];
    
    // root permission
    if (isset ($rootpermission[$name]))
    {
      if ($rootpermission[$name] == 1) return true;
      else return false;
    }
    else return false;
  }
  else return false;
}

// ---------------------- checkglobalpermission -----------------------------
// function: checkglobalpermission()
// input: publication name, permission name
// output: true/false

// description:
// checks global permission for a publication

function checkglobalpermission ($site, $name)
{
  global $globalpermission;
  
  if (valid_publicationname ($site) && valid_objectname ($name))
  {
    // try to get localpermission from session
    if ((!isset ($globalpermission) || !is_array ($globalpermission)) && isset ($_SESSION['hcms_globalpermission'])) $globalpermission = $_SESSION['hcms_globalpermission'];

    // global permission
    if (isset ($globalpermission[$site][$name]))
    {
      if ($globalpermission[$site][$name] == 1) return true;
      else return false;
    }
    else return false;
  }
  else return false;
}

// ---------------------- checklocalpermission -----------------------------
// function: checklocalpermission()
// input: publication name, user group name, permission name
// output: true/false

// description:
// checks local permissions of a user group for a specific publication

function checklocalpermission ($site, $group, $name)
{
  global $$localpermission;
  
  if (valid_publicationname ($site) && valid_objectname ($group) && valid_objectname ($name))
  {
    // try to get localpermission from session
    if ((!isset ($localpermission) || !is_array ($localpermission)) && isset ($_SESSION['hcms_localpermission'])) $localpermission = $_SESSION['hcms_localpermission'];

    // local permission
    if (isset ($localpermission[$site][$group][$name]))
    {
      if ($localpermission[$site][$group][$name] == 1) return true;
      else return false;
    }
    else return false;
  }
  else return false;
}

// =============================== USER LOGON/SESSION FUNCTIONS ================================

// --------------------------------------- userlogin -------------------------------------------
// function: userlogin()
// input: username, password, hash code of user, object reference for hcms linking (object ID), object code for hcms linking (crypted object ID), 
//        ignore passwordcheck needed for WebDAV or access link [true/false], lock IP after 10 failed attempts to login [true/false]
// output: result array
// requires: config.inc.php to be loaded before

// description:
// login of user by sending user and password using the variables: $sentuser, $sentpasswd
// this procedure will register the user in the hypercms session and in the php session.
// the procedure will return true or false using the variable $result.

function userlogin ($user, $passwd, $hash="", $objref="", $objcode="", $ignore_password=false, $locking=true)
{
  global $mgmt_config, $eventsystem, $hcms_lang_codepage, $hcms_lang, $lang;

  // include hypermailer class
  if (!class_exists ("HyperMailer")) require ($mgmt_config['abs_path_cms']."function/hypermailer.class.php");
  
  // set default language
  if (empty ($lang)) $lang = "en";
  
  // result array containing the following fields:
  $result = array(
      'hcms_linking'		=> array(),
      'globalpermission'	=> array(),
      'localpermission'	=> array(),
      'pageaccess'		=> array(),
      'siteaccess'		=> array(),
      'compaccess'		=> array(),
      'hiddenfolder'		=> array(),
      'auth'				=> false,
      'html'				=> '',
      'rootpermission'	=> array(),
      'lang'				=> '',
      'user'				=> '',
      'passwd'			=> '',
      'userhash'			=> '',
      'superadmin'			=> '',
      'instance'			=> false,
      'checksum'			=> '',
      'message'			=> '',
      'mobile'			=> '',
      'chatstae'			=> ''
      );
  
  $linking_auth = true;
  $ldap_auth = true;
  $auth = false;
  $site_collection = Null;
  $fileuser = Null;
  $filepasswd = Null;
  $superadmin = Null;
  $memberofnode = Null;
  
  if ($mgmt_config['db_connect_rdbms'] != "")
  {
    include_once ($mgmt_config['abs_path_cms']."database/db_connect/".$mgmt_config['db_connect_rdbms']);
  }
  
  // eventsystem
  if ($eventsystem['onlogon_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0))
  {
    onlogon_pre ($user);
  }

  // object linking
  if (!empty ($objref) && !empty ($objcode))
  {
    $passwd_crypted = urldecode ($passwd);
    $linking_auth = false;

    // check object reference (ID) and object code (token) in version before and after 5.5.8
    if ($mgmt_config['db_connect_rdbms'] != "" && ($objcode == hcms_crypt ($objref, 3, 12) || $objcode == hcms_crypt ($objref)))
    {
      $objectpath = rdbms_getobject ($objref);
      
      if (!empty ($objectpath))
      {
        $result['hcms_linking']['publication'] = getpublication ($objectpath);
        $result['hcms_linking']['cat'] = getcategory ($result['hcms_linking']['publication'], $objectpath);
        $objectpath = deconvertpath ($objectpath, "file");
        
        if (getobject ($objectpath) == ".folder")
        {
          $result['hcms_linking']['location'] = getlocation ($objectpath);
          $result['hcms_linking']['object'] = "";
          $result['hcms_linking']['type'] = "Folder";
        }
        else
        {
          $result['hcms_linking']['location'] = getlocation ($objectpath);
          $result['hcms_linking']['object'] = getobject ($objectpath);
          $result['hcms_linking']['type'] = "Object";
        }
        
        $linking_auth = true;
      }
    }
  }
  else
  {
    $passwd_crypted = crypt ($passwd, substr ($passwd, 1, 2));
  }

  // include LDAP connectivity
  if (isset ($ldap_connect) && $ldap_connect != "" && @is_file ($mgmt_config['abs_path_data']."ldap_connect/".$ldap_connect.".php"))
  {
    include ($mgmt_config['abs_path_data']."ldap_connect/".$ldap_connect.".php");
     
    $ldap_auth = ldap_connect ($sentuser, $sentpasswd);
  }
  
  if ($ldap_auth && $linking_auth)
  {
    // please note: each user login name and user group name is unique!
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
        $auth = false;
      }

      if (isset ($result_unlock) && $result_unlock == true)
      {
        // load user file
        $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");   
      }
      else $userdata = false;
    }    
     
    if ($userdata != false)
    {
      // update tasks
      update_tasks_v584 ();
    
      // get encoding (before version 5.5 encoding was empty and was saved as ISO 8859-1)
      $charset = getcharset ("", $userdata); 
      
      if ($charset == false || $charset == "")
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
          $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|error|$errcode|update (UTF-8 encoding) of user management file failed";      
          
          // save log
          savelog (@$error);
        } 
      }
      
      // count users
      $users = substr_count ($userdata, "</user>");
    
      // get user information
      if ($user != "") $usernode = selectcontent ($userdata, "<user>", "<login>", $user);
      elseif ($hash != "") $usernode = selectcontent ($userdata, "<user>", "<hashcode>", $hash);
      else $usernode = false;
      
      if (is_array ($usernode))
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
        
        // super admin
        $useradmin = getcontent ($usernode[0], "<admin>");
        if (!empty ($useradmin[0])) $result['superadmin'] = $superadmin = $useradmin[0];
        else $result['superadmin'] = "";
           
        // language
        $userlanguage = getcontent ($usernode[0], "<language>");
        if (!empty ($userlanguage[0])) $result['lang'] = $userlanguage[0];
        else $result['lang'] = "en";
        
        // set language of user and load language file
        $lang = $result['lang'];        
        require_once ($mgmt_config['abs_path_cms']."language/".getlanguagefile ($lang));
        
        // hyperCMS theme
        if (is_mobilebrowser ())
        {
          $result['themename'] = "mobile";
        }
        else
        {
          $usertheme = getcontent ($usernode[0], "<theme>");
          
          if (!empty ($usertheme[0])) $result['themename'] = $usertheme[0];
          else $result['themename'] = "standard";
        }

        $memberofnode = getcontent ($usernode[0], "<memberof>");
      }

      // check logon
      if ($hash == $result['userhash'] || ($user == $fileuser && ($filepasswd == $passwd_crypted || $ignore_password)))
      {
        $result['user'] = $fileuser;
        $result['passwd'] = $filepasswd;
                
        // super, download or system user
        if ($user == "admin" || $user == "sys" || $user == "hcms_download" || $superadmin == "1")
        {
          $inherit_db = inherit_db_read ();

          // set permissions and group name
          if ($user != "hcms_download") $permission_str_admin = "desktop=111111&site=1111&user=1111&group=1111&pers=111111111&workflow=1111111111&template=11111&media=111111&component=11111111111&page=111111111";
          else $permission_str_admin = "desktop=00000&site=0000&user=0000&group=0000&pers=000000000&workflow=0000000000&template=00000&media=000000&component=10100000000&page=000000000";
          
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
                $permission_str[$site_name][$group_name_admin] = $permission_str_admin;
                
                $result['siteaccess'][] = $site_name;
                $result['rootpermission'] = rootpermission ($site_name, $site_admin, $permission_str);
   
                break;
              }
              // include configuration of site
              elseif (valid_publicationname ($site_name) && @is_file ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php"))
              {
                @require_once ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php");
                
                $site_collection .= "|".$site_name; 
                
                // define array of excluded/hidden folders
                if (!empty ($mgmt_config[$site_name]['exclude_folders']))
                {
                  if (substr ($mgmt_config[$site_name]['exclude_folders'], strlen ($mgmt_config[$site_name]['exclude_folders']) - 1, 1) == ";")
                  {
                    $excludefolders = substr ($mgmt_config[$site_name]['exclude_folders'], 0, strlen ($mgmt_config[$site_name]['exclude_folders']) - 1);
                  }
                  else
                  {
                    $excludefolders = $mgmt_config[$site_name]['exclude_folders'];
                  }
                  
                  if (substr_count ($excludefolders, ";") >= 1)
                  {
                    $result['hiddenfolder'][$site_name] = explode (";", $excludefolders);
                  }
                  else
                  {
                    $result['hiddenfolder'][$site_name][0] = $excludefolders;
                  }
                  
                }
                else
                {
                  $result['hiddenfolder'][$site_name] = false;
                }
              }
               
              $result['siteaccess'][] = $site_name;
              $result['pageaccess'][$site_name][$group_name_admin] = deconvertpath ("%page%/".$site_name."/|", "file");
              $result['compaccess'][$site_name][$group_name_admin] = deconvertpath ("%comp%/".$site_name."/|", "file");
  
              // deseralize the permission string and define root, global and local permissions
              $permission_str[$site_name][$group_name_admin] = $permission_str_admin;

              if (isset ($permission_str[$site_name][$group_name_admin]))
              {
                $result['rootpermission'] = rootpermission ($site_name, $site_admin, $permission_str);
                $globalpermission_new = globalpermission ($site_name, $permission_str);
                $localpermission_new = localpermission ($site_name, $permission_str);
                
                if ($globalpermission_new != false)
                {
                  $result['globalpermission'] = array_merge ($result['globalpermission'], $globalpermission_new);
                }
                
                if ($localpermission_new != false)
                {
                  $result['localpermission'] = array_merge ($result['localpermission'], $localpermission_new);
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
              $result['siteaccess'][] = $site_name;
               
              $usergroup = getcontent ($memberof, "<usergroup>");
              $group_string = $usergroup[0];
              
              $site_collection .= "|".$site_name; 
              
              // load usergroup information
              $usergroupdata = loadfile ($mgmt_config['abs_path_data']."user/", $site_name.".usergroup.xml.php");

              // include configuration of site
              if (@is_file ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php"))
              {
                @require_once ($mgmt_config['abs_path_data']."config/".$site_name.".conf.php");
                
                // define array of excluded/hidden folders
                if (!empty($mgmt_config[$site_name]['exclude_folders']))
                {
                  if (substr ($mgmt_config[$site_name]['exclude_folders'], strlen ($mgmt_config[$site_name]['exclude_folders']) - 1, 1) == ";")
                  {
                    $excludefolders = substr ($mgmt_config[$site_name]['exclude_folders'], 0, strlen ($mgmt_config[$site_name]['exclude_folders']) - 1);
                  }
                  else
                  {
                    $excludefolders = $mgmt_config[$site_name]['exclude_folders'];
                  }
                  
                  if (substr_count ($excludefolders, ";") >= 1)
                  {
                    $result['hiddenfolder'][$site_name] = explode (";", $excludefolders);
                  }
                  else
                  {
                    $result['hiddenfolder'][$site_name][0] = $excludefolders;
                  }
                }
                else
                {
                  $result['hiddenfolder'][$site_name] = false;
                }
                
                if ($usergroupdata != false && strlen ($group_string) > 0)
                {
                  $group_array = explode ("|", substr ($group_string, 1, strlen ($group_string) - 2));
             
                  // if object linking is used assign group "default" if existing.
                  // user must have at least one group assigned to have access to the system!
                  if (isset ($result['hcms_linking']) && is_array ($result['hcms_linking']) && !empty ($result['hcms_linking']['location']))
                  {
                    $defaultgroup = selectcontent ($usergroupdata, "<usergroup>", "<groupname>", "default");
                    
                    if ($defaultgroup != false && $defaultgroup[0] != "" && !in_array ("default", $group_array))
                    {
                      $group_array[] = "default";
                    }
                  }
                  
                  if (is_array ($group_array) && sizeof ($group_array) > 0)
                  {
                    // get the permissions of the group
                    foreach ($group_array as $group_name)
                    {
                      // get usergroup information
                      $usergroupnode = selectcontent ($usergroupdata, "<usergroup>", "<groupname>", $group_name);
                      
                      if ($usergroupnode != false)
                      {
                        $userpermission = getcontent ($usergroupnode[0], "<permission>");
                        $userpageaccess = getcontent ($usergroupnode[0], "<pageaccess>");
                        $usercompaccess = getcontent ($usergroupnode[0], "<compaccess>");
    
                        if ($userpermission != false)
                        {
                          $permission_str[$site_name][$group_name] = trim ($userpermission[0]);
                        }
                        else
                        {
                          $permission_str = null;
                        }
                        
                        // page accsess
                        if ($userpageaccess != false && strlen ($userpageaccess[0]) >= 1)
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
                                  if ($temp_path != "") $folder_path .= getlocation($temp_path)."|";
                                }
                              }
                            }
                          }
                          else $folder_path = $userpageaccess[0];

                          $result['pageaccess'][$site_name][$group_name] = deconvertpath ($folder_path, "file");
                        }
                        else
                        {
                          $result['pageaccess'][$site_name][$group_name] = null;
                        }
                        
                        // component access
                        if ($usercompaccess != false && strlen ($usercompaccess[0]) >= 1)
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
                        else
                        {
                          $result['compaccess'][$site_name][$group_name] = null;
                        }
                        // deseralize the permission string and define root, global and local permissions
                        if (isset ($permission_str[$site_name][$group_name]))
                        {
                          $result['rootpermission'] = rootpermission ($site_name, $mgmt_config[$site_name]['site_admin'], $permission_str);
                          $globalpermission_new = globalpermission ($site_name, $permission_str);
                          $localpermission_new = localpermission ($site_name, $permission_str);

                          if ($globalpermission_new != false)
                          {
                            $result['globalpermission'] = array_merge ($result['globalpermission'], $globalpermission_new);
                          }
                          
                          if ($localpermission_new != false)
                          {
                            $result['localpermission'] = array_merge ($result['localpermission'], $localpermission_new);
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
  
  if ($auth)
  {
    // check disk key
    $result['keyserver'] = checkdiskkey ($users, $site_collection."|");

    // first time logon
    if (@is_file ($mgmt_config['abs_path_data']."check.dat"))
    {
      $containercounter = loadfile ($mgmt_config['abs_path_data'], "check.dat");
       
      if ($containercounter == 0)
      {
        // include disk key
        require ($mgmt_config['abs_path_cms']."include/diskkey.inc.php");
  
        $mailer = new HyperMailer();
        $mailer->AddAddress ("info@hypercms.net");
        $mailer->Subject = "hyperCMS Started First Time";
        $mailer->Body = "hyperCMS started first time by ".$mgmt_config['url_path_cms']." (".getuserip().")\r\nLicense key: ".$diskhash."\n";
        $mailer->Send();
        savefile ($mgmt_config['abs_path_data'], "check.dat", date ("Y-m-d", time()));
        
        // information
        $errcode = "00221";
        $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|information|$errcode|hyperCMS started first time by publication: ".$site_name;       
        
        $checkresult = true;       
      }
      else
      {
        $checkresult = true;
      }
  
      if (!$result['keyserver'])
      {
        $mailer = new HyperMailer();
        $mailer->AddAddress ("info@hypercms.net");
        $mailer->Subject = "hyperCMS License Alert";
        $mailer->Body = "License limit reached by ".$mgmt_config['url_path_cms']." (".getuserip().")\r\nPublications: ".$site_collection."|\n";
        $mailer->Send();
        
        // deletefile ($mgmt_config['abs_path_data'], "check.dat", 0);
        $result['message'] = $hcms_lang['your-action-is-not-confirm-to-the-licence-agreement'][$lang]." <a href=\"mailto:support@hypercms.net\">support@hypercms.net</a>";
        $checkresult = false;

        // warning
        $errcode = "00222";
        $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|warning|$errcode|license limits exceeded";            
      }
    }
    else
    {
      $mailer = new HyperMailer();
      $mailer->AddAddress ("info@hypercms.net");
      $mailer->Subject = "hyperCMS ALERT";
      $mailer->Body = "hyperCMS alert (check.dat is missing) for ".$mgmt_config['url_path_cms']."\n";
      $mailer->Send();
      $result['message'] = $hcms_lang['your-action-is-not-confirm-to-the-licence-agreement'][$lang]." <a href=\"mailto:support@hypercms.net\">support@hypercms.net</a>";
      $checkresult = false;
      
      // warning
      $errcode = "00223";
      $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|warning|$errcode|check.dat is missing for ".$mgmt_config['url_path_cms'];       
    }
  }
  
  // count failed login attempts of same client IP address
  if ($locking == true && $result['auth'] == false)
  {
    // reset session array
    if (!isset ($_SESSION['temp_ip_counter']) || !is_array ($_SESSION['temp_ip_counter'])) $_SESSION['temp_ip_counter'] = array();
    
    // get client IP address
    $client_ip = getuserip();

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
        $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|warning|$errcode|client IP $client_ip is banned due to 10 failed logon attempts";      
              
        // reset counter
        $_SESSION['temp_ip_counter'][$user] = 1;
      }      
    }
    else $result['message'] = $hcms_lang['you-have-been-banned'][$lang];
  }
  
  // auth. result
  $result['auth'] = ($ldap_auth && $linking_auth && $auth && $checkresult);
  
  // detect mobile browsers
  $result['mobile'] = is_mobilebrowser (); 

  // state of chat
  $result['chatstate'] = getchatstate (false);

  // message
  if (!$result['message'])
  {
    if (isset ($result['auth']) && $result['auth'] == true) $result['message'] = $hcms_lang['login-correct'][$lang];
    else $result['message'] = $hcms_lang['login-incorrect'][$lang];
  }
  
  // calculate checksum of permissions
  if (isset ($_SESSION['hcms_instance'])) $result['instance'] = $_SESSION['hcms_instance'];
  else $result['instance'] = false;

  $result['checksum'] = createchecksum (array ($result['instance'], $result['superadmin'], $result['siteaccess'], $result['pageaccess'], $result['compaccess'], $result['rootpermission'], $result['globalpermission'], $result['localpermission']));

  // eventsystem
  if ($eventsystem['onlogon_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0))
  {
    onlogon_post ($user, $result['auth']);
  }
  
  // save log
  savelog (@$error);    
  
  return $result;
}

// ---------------------- registerinstance -----------------------------
// function: registerinstance()
// input: instance name, load main config of instance [true/false] (optional)
// output: true/false
// requires: hypercms_api.inc.php

function registerinstance ($instance, $load_config=true)
{
  global $mgmt_config;

  if (!empty ($mgmt_config['instances']) && $instance != "")
  {
    if (valid_publicationname ($instance) && is_file ($mgmt_config['instances'].$instance.".inc.php"))
    {
      $_SESSION['hcms_instance'] = $instance;

      // load management configuration of instance
      if ($load_config) require ($mgmt_config['instances'].$instance.".inc.php");

      return true;
    }
    else return false;
  }
}

// ---------------------- createchecksum -----------------------------
// function: createchecksum()
// input: array or empty
// output: MD5 checksum
// requires: hypercms_api.inc.php

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
// input: user name, password, checksum
// output: true / false on error
// requires: hypercms_api.inc.php

// description:
// writes hyperCMS specific session data of a user

function writesession ($user, $passwd, $checksum)
{
  global $mgmt_config;

  if (valid_objectname ($user) && $passwd != "" && $checksum != "")
  {
    // write session data for load balancer if required
    writesessiondata ();
    
    // timestamp
    $sessiontime = time();
    
    // session string
    $sessiondata = session_id()."|".$sessiontime."|".md5 ($passwd)."|".$checksum."\n";
  
    // if user session file exists (user didn't log out or same user logged in a second time)
    if (@is_file ($mgmt_config['abs_path_data']."session/".$user.".dat"))
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
        $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|error|$errcode|appendfile failed for user $user on /data/session/".$user.".dat";      
        
        // save log
        savelog (@$error);
        
        return false;
      }
    }
    // if user session file is not available (user logged out correctly)
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
        $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|error|$errcode|savefile failed for user $user on /data/session/".$user.".dat";      
        
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
// serializes and writes all session data of a user

function writesessiondata ()
{
  global $mgmt_config;
  
  if (valid_objectname (session_id()))
  {
    // write session data for load balancer (third party load balancer or hyperCMS load balancer)
    if ((!empty ($mgmt_config['writesessiondata']) && $mgmt_config['writesessiondata'] == true) || (!empty ($mgmt_config['url_path_service']) && is_array ($mgmt_config['url_path_service']) && sizeof ($mgmt_config['url_path_service']) > 0))
    {
      // register current timestamp in session
      setsession ('hcms_temp_sessiontime', time());
     
      // serialize session data
      $session_data = session_encode ();
    
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
// input: user name (optional)
// output: true

// description:
// Checks if session data of a user is available. This function does access session variables directly!

function createsession ()
{
  global $mgmt_config;
  
  // check user session and set session ID if required
  if (!session_id() && !empty ($_REQUEST['PHPSESSID']))
  {
    session_id ($_REQUEST['PHPSESSID']);
  }

  // start session
  session_name ("hyperCMS");
  session_start ();

  // session is not valid or data directory is missing
  if (!valid_objectname (session_id()))
  {
    $errcode = "10401";
    $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|error|$errcode|session could not be created or session ID is invalid";      
    
    // save log
    savelog (@$error);
  }
  // load balancer is used: if a valid session ID is provided, evalute session and copy session data if required
  elseif (!empty ($mgmt_config['abs_path_data']) && (!empty ($mgmt_config['url_path_service']) || !empty ($mgmt_config['writesessiondata'])))
  {
    // define session file (a session file is only available if the load balancer is used)
    $session_file = $mgmt_config['abs_path_data']."session/".session_id().".dat";
    
    $session_time = (!empty ($_SESSION['hcms_temp_sessiontime']) ? $_SESSION['hcms_temp_sessiontime'] : 0);

    // check if session file exists and is newer than the existing session data
    if (is_file ($session_file) && filemtime ($session_file) > $session_time)
    {
      // load session information
      $data = file_get_contents ($session_file);

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
// input: user name  for hyperCMS session (optional), destroy php session [true,false] (optional)
// output: true
// requires: hypercms_api.inc.php

// description:
// destroys session data of user

function killsession ($user="", $destroy_php=true)
{
  global $mgmt_config;

  // if hypercms user session file exists
  if (valid_objectname ($user) && @is_file ($mgmt_config['abs_path_data']."session/".$user.".dat"))
  {
    $session_array = @file ($mgmt_config['abs_path_data']."session/".$user.".dat");
    
    if ($session_array != false && sizeof ($session_array) > 0)
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
          
    // delete session file
    if ($remove == true)
    {
      $test = deletefile ($mgmt_config['abs_path_data']."session/", $user.".dat", 0);
    }
    else
    {
      $test = savefile ($mgmt_config['abs_path_data']."session/", $user.".dat", $sessiondata);
    }
  }

  // delete session data and temporary files
  deletefile ($mgmt_config['abs_path_data']."session/", session_id().".dat", 0);
  deletefile ($mgmt_config['abs_path_temp'], session_id().".dat", 0);
  deletefile ($mgmt_config['abs_path_temp'], session_id().".js", 0);
  
  // kill PHP session
  if ($destroy_php == true) @session_destroy();
  
  return true;  
}

// ---------------------- checkdiskkey -----------------------------
// function: checkdiskkey()
// input: user count (optional), publication names (use | as seperator) (optional)
// output: true/false

// description:
// checks the disc key of the installation

function checkdiskkey ($users="", $site="")
{
  global $mgmt_config;
  // version info
  require ($mgmt_config['abs_path_cms']."version.inc.php");
  // include disk key
  require ($mgmt_config['abs_path_cms']."include/diskkey.inc.php"); 
  
  if ($diskhash != "")
  {
    $data = array();
    
    // disk hash code
    $data['key'] = $diskhash;
    
    // MD5 hash of hypercms_sec.inc.php 
    $md5 = md5_file ($mgmt_config['abs_path_cms']."function/hypercms_sec.inc.php");
    $data['md5'] = $md5;
    
    $data['site'] = $site;
    
    // count users
    if ($users == "")
    {
      $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php"); 
      $users = substr_count ($userdata, "</user>");
    }
    
    $data['users'] = $users;    

    // storage in MB
    $filesize = rdbms_getfilesize ("", "%hcms%");
    $storage = round (($filesize['filesize'] / 1024), 0);
    $data['storage'] = $storage;
    
    // cpu cores
    $serverload = getserverload ();
    $data['cpu'] = $serverload['cpu'];
    
    // version
    $data['version'] = $version;
    
    // client ip
    $data['userip'] = getuserip();
    
    // domain
    $data['domain'] = $mgmt_config['url_path_cms'];
    
    if ($mgmt_config['url_protocol'] != "https://" || $mgmt_config['url_protocol'] != "http://") $mgmt_config['url_protocol'] = "https://";
    
    $result_post = HTTP_Post ($mgmt_config['url_protocol']."cms.hypercms.net/keyserver/", $data);
    
    if ($result_post != "")
    {
      $result = getcontent ($result_post, "<result>");
      
      // result must be true or the default hash key is provided by the system (free open source installation)
      if ((is_array ($result) && $result[0] == "true") || $diskhash == "tg3234g234zg78ze8whf") return true;
      else return false; 
    }
    else return false;
  }
  else return false;
}

// ---------------------------------------- checkpassword --------------------------------------------
// function: checkpassword()
// input: password a string
// output: true if passed / error message as string

// description:
// this function checks the strength of a password and return the error messages or true.

function checkpassword ($password)
{
  global $mgmt_config, $lang;
  
  require ($mgmt_config['abs_path_cms']."language/".getlanguagefile ($lang)); 
  
  if ($password != "")
  {
    // must be at least 8 digits long
    if (strlen ($password) < 8) $error[] = $hcms_lang['the-passwords-has-less-than-8-digits'][$lang];
    // must not be longer than 20 gigits
    if (strlen ($password) > 20)	$error[] = $hcms_lang['the-password-has-more-than-20-digits'][$lang];
    // must contain at least one number
    if (!preg_match ("#[0-9]+#", $password)) $error[] = $hcms_lang['password-must-include-at-least-one-number'][$lang];
    // must contain at least one letter
    if (!preg_match ("#[a-z]+#", $password))	$error[] = $hcms_lang['password-must-include-at-least-one-letter'][$lang];
    // must contain at least one capital letter  
    if (!preg_match ("#[A-Z]+#", $password)) $error[] = $hcms_lang['password-must-include-at-least-one-capital-letter'][$lang];
    // must contain at least one symbol (optional but not used) 
    // if (!preg_match ("#\W+#", $password)) $error .= $hcms_lang['password-must-include-at-least-one-symbol'][$lang];    

    if ($error)
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
// input: client IP address, user logon name (optional)
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
    
    if (@is_file ($loglocation.$logfile))
    {
      // append data to log if IP is not already locked
      return appendfile ($loglocation, $logfile, $client_ip."|".$user."|".$now."\n");
    }
    else
    {
      // save log file initally
      return savefile ($loglocation, $logfile, $client_ip."|".$user."|".$now."\n");
    }
  }
  else return false;
}

// --------------------------------------- checkuserip -------------------------------------------
// function: checkuserip()
// input: client IP address, user logon name (optional), timeout in minutes (optional)
// output: true if IP is not locked / false if IP is locked or on error

function checkuserip ($client_ip, $user="", $timeout="") 
{
  global $mgmt_config;
  
  // set default logon timeout
  if ($timeout == "") $timeout = $mgmt_config['logon_timeout'];
  
  if ($client_ip != "" && $timeout > 0)
  {  
    // log file
    $loglocation = $mgmt_config['abs_path_data']."log/";
    $logfile = "locked_ip.log";
    
    // time stamp in seconds
    $now = time ();
    
    $valid = true;
    
    if (@is_file ($loglocation.$logfile))
    {
      // load log data
      $logdata = file ($loglocation.$logfile);

      foreach ($logdata as $record) 
      {
        list ($log_ip, $log_user, $log_time) = explode ("|", $record);

        // check if client ip is already in log and locked
        if ($client_ip == $log_ip && ($user == "" || $user == $log_user) && $now < ($log_time + 60 * $timeout)) 
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
// input: user name (optional)
// output: true / false if a certain amount of reguests per minute is exceeded

// description: provides security for Cross-Site Request Forgery

function checkuserrequests ($user="sys")
{
  global $mgmt_config;
  
  // set default value
  if (!isset($mgmt_config['requests_per_minute'])) $mgmt_config['requests_per_minute'] = 1000;
  
  if ($mgmt_config['requests_per_minute'] > 0)
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
    if ($_SESSION['hcms_temp_hit_counter'] > $mgmt_config['requests_per_minute'])
    {
      // more than given number of requests per minute, this might be a flood attack
      if (time() - $_SESSION['hcms_temp_hit_starttime'] <= 60)
      {
        // warning
        $client_ip = getuserip ();
        $errcode = "00109";
        $error[] = $mgmt_config['today']."|hypercms_sec.inc.php|warning|$errcode|user $user with client IP $client_ip is banned due to a possible CSRF attack";
        
        savelog ($error);        
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
  else return true;
}

// ------------------------- checkusersession -----------------------------
// function: checkusersession()
// input: user name (optional), include CSRF detection [true,false]
// output: true / html-output followed by termination

// description:
// checks if session data of user is correct. This function does access session variables directly!
// requires config.inc.php

function checkusersession ($user="sys", $CSRF_detection=true)
{
  global $mgmt_config;
  
  // add CSRF detection
  if ($CSRF_detection == true) checkuserrequests ($user); 
  
  $alarm = true;

  if (valid_objectname ($user) && @is_file ($mgmt_config['abs_path_data']."session/".$user.".dat") && is_array ($_SESSION['hcms_siteaccess']) && is_array ($_SESSION['hcms_rootpermission']))
  {
    $session_array = @file ($mgmt_config['abs_path_data']."session/".$user.".dat");
  
    if ($session_array != false && sizeof ($session_array) >= 1)
    {
      foreach ($session_array as $session)
      {
        if (trim ($session) != "")
        {
          list ($regsessionid, $regsessiontime, $regpasswd, $regchecksum) = explode ("|", trim ($session));
  
          // session is correct if session ID in session and hypercms session file are equal, MD5 crypted passwords are equal, permission checksums are equal
          if ($regsessionid == session_id() && $regpasswd == $_SESSION['hcms_passwd'] && $regchecksum == createchecksum ()) $alarm = false;
        }
      }
    }
  }

  // unauth. access
  if ($alarm == true)
  {
    echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
    <html>
    <head>
    <title>hyperCMS</title>
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
    <link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\">
    </head>
    <body class=\"hcmsWorkplaceGeneric\" leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 onLoad=\"top.location.href='".$mgmt_config['url_path_cms']."userlogout.php';\">
    <table width=100% border=0 height=100%>
    <tr>
      <td align=\"center\" valign=\"middle\" class=hcmsHeadline>
        <font size=4>Unauthorized Access!</font>
      </td>
    </tr>
    </table>
    </body>
    </html>";
  
    exit;
  }
  // auth. access
  else return true; 
}

// ------------------------- allowuserip  -----------------------------
// function: allowuserip ()
// input: publication name
// output: true / false

// description:
// checks if the client IP is in the range of valid IPs.
// requires config.inc.php

function allowuserip ($site)
{
  global $mgmt_config;
  
  // publication management config
  if (valid_publicationname ($site) && !isset ($mgmt_config[$site]['allow_ip'])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

  // check ip access
  if (valid_publicationname ($site) && isset ($mgmt_config[$site]['allow_ip']) && $mgmt_config[$site]['allow_ip'] != "")
  {
    $client_ip = getuserip ();
    $allow_ip = splitstring ($mgmt_config[$site]['allow_ip']);

    if ($client_ip && is_array ($allow_ip))
    {
      if (in_array ($client_ip, $allow_ip)) return true;
      else return false;
    }
    elseif (!$client_ip)
    {
      return false;
    }
    else return true;
  }
  else return true;
}

// ------------------------- valid_objectname -----------------------------
// function: valid_objectname()
// input: variable (string or array)
// output: variable / false on error

// description:
// test if an expression includes forbidden characters (true) or doesnt (false) to prevent directory browsing

function valid_objectname ($variable)
{
  if ($variable != "")
  {
    if (!is_array ($variable) && is_string ($variable))
    {
      if ($variable == ".") return false;
      if ($variable == "..") return false;
      if (substr_count ($variable, "<") > 0) return false;
      if (substr_count ($variable, ">") > 0) return false;
      if (substr_count ($variable, "../") > 0) return false;
      if (substr_count ($variable, "\\") > 0) return false;
      if (substr_count ($variable, "\"") > 0) return false;
      return $variable;
    }
    elseif (is_array ($variable))
    {
      $result = true;
      
      foreach ($variable as &$value)
      {
        $value = valid_objectname ($value);
        if ($value == false) $result = false;
      }
      
      if ($result == true) return $variable;
      else return false;
    } 
  }
  else return false;
}

// ------------------------- valid_locationname -----------------------------
// function: valid_locationname()
// input: variable (string or array)
// output: variable / false on error

// description:
// test if an expression includes forbidden characters (true) or doesnt (false) to prevent directory browsing

function valid_locationname ($variable)
{
  if ($variable != "")
  {
    if (!is_array ($variable) && is_string ($variable))
    {
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
      return $variable;
    }
    elseif (is_array ($variable))
    {
      $result = true;
      
      foreach ($variable as &$value)
      {
        $value = valid_locationname ($value);
        if ($value == false) $result = false;
      }
      
      if ($result == true) return $variable;
      else return false;
    }   
  }
  else return false;
}

// ------------------------- valid_publicationname -----------------------------
// function: valid_publicationname()
// input: variable (string or array)
// output: variable / false on error

// description:
// test if an expression includes forbidden characters (true) or doesnt (false) to prevent directory browsing

function valid_publicationname ($variable)
{
  if ($variable != "")
  {
    if (!is_array ($variable) && is_string ($variable))
    {
      if ($variable == "*Null*") return false;
      if (substr_count ($variable, "<") > 0) return false;
      if (substr_count ($variable, ">") > 0) return false;
      if (substr_count ($variable, "/") > 0) return false;
      if (substr_count ($variable, "\\") > 0) return false;
      if (substr_count ($variable, ":") > 0) return false;
      if (substr_count ($variable, "\"") > 0) return false;    
      return $variable;
    }
    elseif (is_array ($variable))
    {
      $result = true;
      
      foreach ($variable as &$value)
      {
        $value = valid_publicationname ($value);
        if ($value == false) $result = false;
      }
      
      if ($result == true) return $variable;
      else return false;
    }    
  }
  else return false;
}

// ------------------------- html_encode -----------------------------
// function: html_encode()
// input: variable as string or array, conversion of all special characters based on given character set or to ASCII (optional), 
//        remove characters to avoid JS injection [true,false] (optional)
// output: html encoded value as array or string / false on error

// description:
// this function encodes certain characters (&, <, >, ", ') into their 
// HTML character entity equivalents to protect against XSS.


// description:
// converts a string into the html equivalents (also used for XSS protection).
// supports multibyte character sets like UTF-8 as well based on the ASCII value of the character.

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
    else return false;      
  }
  else return false;
}

// ------------------------- html_decode -----------------------------
// function: html_decode()
// input: variable as string or array, conversion of all special characters based on given character set (optional)
// output: html decoded value as array or string / false on error

// description:
// this function decodes all characters which have been converted by html_encode.

function html_decode ($expression, $encoding="")
{
  if ($expression != "" )
  {    
    if (!is_array ($expression))
    {
      if ($encoding != "")
      {
        if (strtolower ($encoding) == "ascii") $encoding = "UTF-8";
         
        $expression = html_entity_decode ($expression, ENT_QUOTES, $encoding);
      }
      else
      {
        $expression = htmlspecialchars_decode ($expression, ENT_QUOTES);
      } 
    }
    elseif (is_array ($expression))
    {
      foreach ($expression as &$value)
      {
        $value = html_decode ($value, $encoding);
      }
    }
    
    if (!empty ($expression)) return $expression;
    else return false;      
  }
  else return false;
}

// ------------------------- scriptcode_encode -----------------------------
// function: scriptcode_encode()
// input: content as string  
// output: escaped content as string / false on error

// description:
// this function escapes all script tags.
// this function must be used to clean all user input in the CMS by removing all server side scripts tags.

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
  else return false;
}

// ------------------------- scriptcode_extract -----------------------------
// function: scriptcode_extract()
// input: content as string, identifier of script begin, and end 
// output: script code as array / false on error or if noting was found

// description:
// this function extracts the script code of a given content.

function scriptcode_extract ($content, $identifier_start="<?", $identifier_end="?>")
{
  if ($content != "" && $identifier_start != "" && $identifier_end != "")
  {
    $content_array = explode ($identifier_start, $content);
    
    if (is_array ($content_array))
    {
      $result = array();
      
      foreach ($content_array as $buffer)
      {
        if (strpos ($buffer, $identifier_end) > 0)
        {
          list ($content_script, $rest) = explode ($identifier_end, $buffer);
          
          if ($content_script != "")
          {
            // remove comments
            if (substr_count ($content_script, "//") > 0)
            {
              $comment_array = scriptcode_extract ("\n".$content_script, "// ", "\n");
              
              if (is_array ($comment_array))
              {
                foreach ($comment_array as $comment) $content_script = str_replace ($comment, "", $content_script);
              }
            }

            if (substr_count ($content_script, "/*") > 0)
            {
              $content_temp = scriptcode_extract ($content_script, "*/", "/*");
              if (is_array ($content_temp)) $content_script = implode ("", $content_temp);
            }

            $result[] = $identifier_start.$content_script.$identifier_end;
          }
        }
      }
      
      if (sizeof ($result) > 0) return $result;
      else return false;
    }
    else return false;
  }
  else return false;
}

// ------------------------- scriptcode_clean_functions -----------------------------
// function: scriptcode_clean_functions()
// input: content as string, cleaning level type from none = 0 to strong = 3 (no cleaning = 0, basic set of disabled functions = 1, 1 + file access functions = 2, 2 + include functions = 3) (optional), application [PHP,ASP,JSP] (optional)
// output: result array / false on error

// description:
// this function removes all dangerous PHP functions.

function scriptcode_clean_functions ($content, $type=3, $application="PHP")
{
  global $mgmt_config;
  
  if ($content != "" && $type > 0 && ($application == "ASP" || $application == "JSP" || $application == "PHP"))
  {
    if ($application == "PHP")
    {
      if ($type > 0) $disabled_functions = array("apache_child_terminate", "apache_setenv", "define_syslog_variables", "eval", "exec", "fp", "fput", "ftp_connect", "ftp_exec", "ftp_get", "ftp_login", "ftp_nb_fput", "ftp_put", "ftp_raw", "ftp_rawlist", "highlight_file", "ini_alter", "ini_get_all", "ini_restore", "inject_code", "mysql_pconnect", "openlog", "passthru", "php_uname", "phpinfo", "phpAds_remoteInfo", "phpAds_XmlRpc", "phpAds_xmlrpcDecode", "phpAds_xmlrpcEncode", "popen", "posix_getpwuid", "posix_kill", "posix_mkfifo", "posix_setpgid", "posix_setsid", "posix_setuid", "posix_setuid", "posix_uname", "proc_close", "proc_get_status", "proc_nice", "proc_open", "proc_terminate", "shell_exec", "syslog", "system", "xmlrpc_entity_decode");
      else $disabled_functions = array();
      
      if ($type > 1) $file_functions = array("basename", "chgrp", "chmod", "chown ", "clearstatcache", "copy", "delete", "dir", "dirname", "disk_free_space", "disk_total_space", "diskfreespace", "fclose", "feof", "fflush", "fgetc", "fgetcsv", "fgets", "fgetss", "file_exists", "file_get_contents", "file_put_contents ", "file", "fileatime", "filectime", "filegroup", "fileinode", "filemtime", "fileowner", "fileperms", "filesize", "filetype", "flock", "fnmatch", "fopen", "fpassthru", "fputcsv", "fputs", "fread", "fscanf", "fseek", "fstat", "ftell", "ftruncate", "fwrite", "glob", "is_dir", "is_executable ", "is_file", "is_link", "is_readable", "is_uploaded_file ", "is_writable", "is_writeable ", "lchgrp", "lchown", "link", "linkinfo", "lstat", "mkdir", "move_uploaded_file", "opendir", "parse_ini_file", "parse_ini_string", "pathinfo ", "pclose", "popen", "readfile", "readlink", "realpath_cache_get", "realpath_cache_size", "realpath", "rename", "rewind", "rmdir", "set_file_buffer", "stat", "symlink ", "tempnam", "tmpfile ", "touch ", "umask", "unlink");
      else $file_functions = array();
      
      if ($type > 2) $include_functions = array("include", "include_once", "require", "require_once");
      else  $include_functions = array();
      
      $identifier_start = "<?";
      $identifier_end = "?>";      
    }
    
    $all_functions = array_merge ($disabled_functions, $file_functions, $include_functions);
    
    $found = array();
    
    $scriptcode_array = scriptcode_extract ($content, $identifier_start, $identifier_end);
    if (is_array ($scriptcode_array)) $scriptcode = implode ("", $scriptcode_array); 

    // remove functions from content
    foreach ($all_functions as $name)
    {
      // find expression followed by (
      if ($name != "" && @preg_match ('/\b'.preg_quote ($name).'\b(.*?)\(/i', $scriptcode))
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
// input: variable as string or array
// output: urlencoded value as array or string / false on error

// description:
// this function encodes all characters.

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
// input: variable as string or array
// output: urldecoded value as array or string / false on error

// description:
// this function decodes all characters which have been converted by url_encode or urlencode (PHP).

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
// input: variable as string or array
// output: encoded value as array or string / false on error

// description:
// this function encodes/escapes characters to secure the shell comand.

function shellcmd_encode ($variable)
{
  if (!is_array ($variable))
  {
    return str_replace ("\~", "~", escapeshellcmd ($variable));
  }
  elseif (is_array ($variable))
  {
    foreach ($variable as &$value)
    {
      $value = str_replace ("\~", "~", escapeshellcmd ($value));
    }
    
    return $variable;
  }
  else return false;
}

// ======================================= CRYPTOGRAPHY =======================================

// ---------------------- hcms_crypt -----------------------------
// function: hcms_crypt()
// input: string to encode, start position, length for string extraction
// output: encoded string / false on error

// description:
// unidrectional encryption using crypt, MD5 and urlencode

function hcms_crypt ($string, $start=0, $length=0)
{
  global $mgmt_config;
  
  if ($string != "")
  {
    // crypt only uses the first 8 digits of a string!
    if (strlen ($string ) > 8)
    {
      if (strpos ($string, ".thumb.") > 0) $string = str_replace (".thumb.", ".", $string);
      else $string = substr ($string, -8);
    }
    // encoding algorithm
    $string_encoded = crypt ($string, substr ($string, 0, 1));
    $string_encoded = md5 ($string_encoded);
    // extract substring
    if ($length > 0) $string_encoded = substr ($string_encoded, $start, $length);
    else $string_encoded = substr ($string_encoded, $start);
    // urlencode string
    $string_encoded = urlencode ($string_encoded);

    if ($string_encoded != "") return $string_encoded;
    else return false;
  }
  else return false;
}

// ---------------------- hcms_encrypt -----------------------------
// function: hcms_encrypt()
// input: string to encode, key of length 16, 24 or 32 (optional), crypt strength level [weak,standard,strong] (optional), 
//        encoding [base64,url,none] (optional)
// output: encoded string / false on error

// description:
// encryption of a string. only strong encryption is binary-safe!

function hcms_encrypt ($string, $key="", $crypt_level="", $encoding="url")
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
    
    // weak
    // main purpose is to gain a short encrypted string, please don't use it for sensitive data or files!
    if ($crypt_level == "weak")
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
    }
    // strong (binary-safe)
    elseif ($crypt_level == "strong")
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
      // use PHP Mcrypt with AES 256 encryption (requires a key with 32 digits)
      else
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
    else
    {
      // MCRYPT_MODE_ECB (electronic codebook) 
      // is suitable for random data, such as encrypting other keys. Since data there is short and random, the disadvantages of ECB have a favorable negative effect.
      $ivsize = mcrypt_get_iv_size (MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
      $iv = mcrypt_create_iv ($ivsize, MCRYPT_RAND);
      $hash = mcrypt_encrypt (MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_ECB, $iv);
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
    else return false;
  }
  else return false;
}

// ---------------------- hcms_decrypt -----------------------------
// function: hcms_decrypt()
// input: hash-string to decode, key of length 16, 24 or 32 (optional), crypt strength level [weak,standard,strong] (optional), 
//        encoding [base64,url,none] (optional)
// output: decoded string / false on error

// description:
// decryption of a string. only strong encryption is binary-safe!

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

    // weak
    if ($crypt_level == "weak")
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
    // strong (binary-safe)
    elseif ($crypt_level == "strong")
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
      else
      {
        // MCRYPT_MODE_CBC (cipher block chaining) 
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
    else
    {
      // MCRYPT_MODE_ECB (electronic codebook) 
      // is suitable for random data, such as encrypting other keys. Since data there is short and random, the disadvantages of ECB have a favorable negative effect.
      $ivsize = mcrypt_get_iv_size (MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
      $iv = mcrypt_create_iv ($ivsize, MCRYPT_RAND);
      $hash_decrypted = mcrypt_decrypt (MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_ECB, $iv);
      $hash_decrypted = rtrim ($hash_decrypted, "\0");
    }

    if ($hash_decrypted != "") return $hash_decrypted;
    else return false;
  }
  else return false;
}

// ---------------------- createtimetoken -----------------------------
// function: createtimetoken()
// input: token lifetime in seconds (optional), secret value (optional)
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
  else return false;
}

// ---------------------- checktimetoken -----------------------------
// function: checktimetoken()
// input: token, secret value (optional)
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
    else return false;
  }
  else return false;
}

// ---------------------- createtoken -----------------------------
// function: createtoken()
// input: user name (optional), token lifetime in seconds (optional), secret value (optional)
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
      if ($mgmt_config['token_lifetime'] < 60) $lifetime = 86400;
      else $lifetime = intval ($mgmt_config['token_lifetime']);
    }    
    // create token
    $timetoken = createtimetoken ($lifetime, $secret);
    // create security token
    $token = hcms_encrypt ($timetoken."@".$user);
    
    return $token;
  }
  else return false;
}

// ---------------------- checktoken -----------------------------
// function: checktoken()
// input: token, user name (optional), secret value (optional)
// output: true / false

function checktoken ($token, $user="sys", $secret=4)
{
  global $mgmt_config;
  
  if ($token != "" && $user != "")
  {
    // decrypt token
    $token = hcms_decrypt ($token);
    // extract user name and timestamp
    if ($token != false) list ($timetoken, $token_user) = explode ("@", $token);
    // check if token is valid
    if ($timetoken != "" && $token_user != "")
    {
      if (checktimetoken ($timetoken, $secret) && $user == $token_user) return true;
      else return false;
    }
    else return false;
  }
  else return false;
}

// ---------------------- createuniquetoken -----------------------------
// function: createuniquetoken()
// input: token length (optional)
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
    else return false;
  }
  else return false;
}

// ---------------------- rand_secure -----------------------------
// function: rand_secure()
// input: min and max value as integer (optional)
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