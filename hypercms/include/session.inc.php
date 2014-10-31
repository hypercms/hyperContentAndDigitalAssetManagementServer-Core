<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// start session
session_name ("hyperCMS");
session_start ();

// ------------------------- getsession -----------------------------
// function: getsession()
// input: variable name, default value (optional)
// output: value

function getsession ($variable, $default="")
{
  if ($variable != "")
  {
    // get from session
    if (array_key_exists ($variable, $_SESSION)) $result = $_SESSION[$variable];
    else $result = $default;
    
    // return result
    return $result;    
  }
  else return $default;
}
 
// ============= SESSION Parameters ==============
// instance parameter
$instance = getsession ("hcms_instance");
// user parameter
$user = getsession ("hcms_user");
$passwd = getsession ("hcms_passwd");
$lang = getsession ("hcms_lang", "en");
// access parameter (array)
$siteaccess = getsession ("hcms_siteaccess");
$pageaccess = getsession ("hcms_pageaccess");
$compaccess = getsession ("hcms_compaccess");
// permission parameter (array)
$rootpermission = getsession ("hcms_rootpermission");
$globalpermission = getsession ("hcms_globalpermission"); 
$localpermission = getsession ("hcms_localpermission");
$adminpermission = getsession ("hcms_superadmin");
$hiddenfolder = getsession ("hcms_hiddenfolder");
// mobile browser
$is_mobile = getsession ("hcms_mobile");
$is_iphone = getsession ("hcms_iphone");
// HTML5 file support
$html5file = getsession ("hcms_html5file");
// mail linking parameter (array)
$hcms_linking = getsession ("hcms_linking");
// other temporary session parameters (accessed directly via SESSION)
$temp_clipboard = getsession ("hcms_temp_clipboard");
$temp_explorerview = getsession ("hcms_temp_explorerview");
$temp_objectview = getsession ("hcms_temp_objectview");
$temp_sidebar = getsession ("hcms_temp_sidebar");
$temp_site = getsession ("hcms_temp_site");
$temp_user = getsession ("hcms_temp_user");
$temp_pagelocation = getsession ("hcms_temp_pagelocation");
$temp_complocation = getsession ("hcms_temp_complocation");
$temp_latitude = getsession ("hcms_temp_latitude");
$temp_longitude = getsession ("hcms_temp_longitude");
$temp_chatstate = getsession ("hcms_temp_chatstate");
// security token
$temp_token = getsession ("hcms_temp_token");
// hyperCMS theme
$hcms_themename = getsession ("hcms_themename");
$hcms_themelocation = getsession ("hcms_themelocation");
// filter options for object list
$objectfilter = getsession ("hcms_objectfilter");
?>