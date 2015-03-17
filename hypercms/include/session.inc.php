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

// ------------------------- initsession -----------------------------
// function: initsession()
// input: variable name, default value (optional)
// output: value

function initsession ($variable, $default="")
{
  if ($variable != "" && session_id() != "")
  {
    // get from session
    if (array_key_exists ("hcms_".$variable, $_SESSION)) $result = $_SESSION["hcms_".$variable];
    elseif (array_key_exists ($variable, $_SESSION)) $result = $_SESSION[$variable];
    else $result = $default;
    
    return $result;    
  }
  else return $default;
}
 
// ============= SESSION Parameters ==============
// instance parameter
$instance =  initsession ("hcms_instance");
// user parameter
$user =  initsession ("hcms_user");
$passwd =  initsession ("hcms_passwd");
$lang =  initsession ("hcms_lang", "en");
// access parameter (array)
$siteaccess =  initsession ("hcms_siteaccess");
$pageaccess =  initsession ("hcms_pageaccess");
$compaccess =  initsession ("hcms_compaccess");
// permission parameter (array)
$rootpermission =  initsession ("hcms_rootpermission");
$globalpermission =  initsession ("hcms_globalpermission"); 
$localpermission =  initsession ("hcms_localpermission");
$adminpermission =  initsession ("hcms_superadmin");
$hiddenfolder =  initsession ("hcms_hiddenfolder");
// download formats
$downloadformats =  initsession ("hcms_downloadformats");
// mobile browser
$is_mobile =  initsession ("hcms_mobile");
$is_iphone =  initsession ("hcms_iphone");
// HTML5 file support
$html5file =  initsession ("hcms_html5file");
// mail linking parameter (array)
$hcms_linking =  initsession ("hcms_linking");
// other temporary session parameters (accessed directly via SESSION)
$temp_clipboard =  initsession ("hcms_temp_clipboard");
$temp_explorerview =  initsession ("hcms_temp_explorerview");
$temp_objectview =  initsession ("hcms_temp_objectview");
$temp_sidebar =  initsession ("hcms_temp_sidebar");
$temp_site =  initsession ("hcms_temp_site");
$temp_user =  initsession ("hcms_temp_user");
$temp_pagelocation =  initsession ("hcms_temp_pagelocation");
$temp_complocation =  initsession ("hcms_temp_complocation");
$temp_latitude =  initsession ("hcms_temp_latitude");
$temp_longitude =  initsession ("hcms_temp_longitude");
$temp_chatstate =  initsession ("hcms_temp_chatstate");
// the temporary storage is used to pass container content of files between functions
// and to trigger saving of the file [yes/no]
$temp_cache =  initsession ("hcms_temp_cache", "");
$temp_save =  initsession ("hcms_temp_save", "yes");
// security token
$temp_token =  initsession ("hcms_temp_token");
// hyperCMS theme
$hcms_themename =  initsession ("hcms_themename");
$hcms_themelocation =  initsession ("hcms_themelocation");
// filter options for object list
$objectfilter =  initsession ("hcms_objectfilter");
?>