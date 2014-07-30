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
// language file
require_once ("language/popup_action.inc.php");


// input parameters
$action = getrequest ("action");
$multiobject = getrequest ("multiobject");
$location = getrequest ("location", "locationname");
$folder = getrequest ("folder", "objectname");
$page = getrequest ("page", "objectname");
$wf_token = getrequest ("wf_token");
$token = getrequest ("token");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// convert location
$location = deconvertpath ($location, "file");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);  
if ($ownergroup == false || $setlocalpermission['root'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// correct location for access permission
if ($folder != "")
{
  $location_ACCESS = $location.$folder."/";
}
else
{
  $location_ACCESS = $location;
}

// check authorization
$authorized = false;

if ($setlocalpermission['root'] == 1 && checktoken ($token, $user))
{
  if ($action == "delete" && (($page != "" && $setlocalpermission['delete'] == 1) || ($folder != "" && $setlocalpermission['folderdelete'] == 1))) $authorized = true;
  elseif (($action == "cut" || $action == "copy") && (($page != "" && $setlocalpermission['rename'] == 1) || ($folder != "" && $setlocalpermission['folderrename'] == 1))) $authorized = true;
  elseif ($action == "linkcopy" && (($page != "" && $setlocalpermission['rename'] == 1 && $setlocalpermission['create'] == 1) || ($folder != "" && $setlocalpermission['folderrename'] == 1 && $setlocalpermission['foldercreate'] == 1))) $authorized = true;
  elseif ($action == "page_unlock" && $setlocalpermission['create'] == 1) $authorized = true;
  elseif ($action == "paste" && ($setlocalpermission['rename'] == 1 || $setlocalpermission['folderrename'] == 1)) $authorized = true;
  elseif (($action == "publish" || $action == "unpublish") && $setlocalpermission['publish'] == 1) $authorized = true;
  elseif ($action == "unzip") $authorized = true;
}

if ($authorized == true)
{
  // empty clipboard
  $_SESSION['hcms_temp_clipboard'] = "";
  $temp_clipboard = "";
      
  // perform actions
  // priority for processing due to all variables (multiobject, folder, page) 
  // will be posted from the context menu:
  // 1. multiobject
  // 2. folder
  // 3. object
  
  // unzip file
  if ($action == "unzip") 
  {
    // load object file and get container and media file
    $objectdata = loadfile ($location, $page);
    $mediafile = getfilename ($objectdata, "media");    
    $mediapath = getmedialocation ($site, $mediafile, "abs_path_media");
    $media_info = getfileinfo ($site, $location.$page, $cat);
    
    if ($mediapath != "" && $mediafile != "" && $location != "") $result_unzip =  unzipfile ($site, $mediapath.$site.'/'.$mediafile, $location, $media_info['name'], $user);
    else $result_unzip = false;
    
    if ($result_unzip == true)
    {
      $result['result'] = true;
      $add_onload = "if (eval (opener.parent.frames['mainFrame'])) {opener.parent.frames['mainFrame'].location.reload();}";
      $show = "<span class=\"hcmsHeadline\">".$text1[$lang]."</span><br />\n";
    }
    else
    {
      $show = "<span class=\"hcmsHeadline\">".$text2[$lang]."</span><br />\n";
    }
  }
  // delete
  elseif ($action == "delete") 
  {
    if ($multiobject != "")
    {
      $multiobject_array = link_db_getobject ($multiobject);
      $result['result'] = true;
      
      foreach ($multiobject_array as $objectpath)
      {
        if ($objectpath != "" && $result['result'] == true)
        {
          $site = getpublication ($objectpath);
          $location = getlocation ($objectpath);
          $page = getobject ($objectpath);
          
          if ($page != "")
          { 
            $result = deleteobject ($site, $location, $page, $user);
        
            $add_onload = $result['add_onload'];
            $show = $result['message'];
          }
        }
      }
    }
    elseif ($page != "")
    { 
      $result = deleteobject ($site, $location, $page, $user);
  
      $add_onload = $result['add_onload'];
      $show = $result['message'];       
    }    
  }
  // cut
  elseif ($action == "cut") 
  {
    if ($multiobject != "")
    {
      $multiobject_array = link_db_getobject ($multiobject);
      $result['result'] = true;
      $i = 1;
      
      foreach ($multiobject_array as $objectpath)
      {
        if ($objectpath != "" && $result['result'] == true)
        {
          $site = getpublication ($objectpath);
          $location = getlocation ($objectpath);
          $page = getobject ($objectpath);
          
          if ($site != "" && $location != "" && $page != "")
          {
            $result = cutobject ($site, $location, $page, $user, true);
        
            $add_onload = $result['add_onload'];
            $show = $result['message'];  
          }
        }
        
        $i++;
      }
    }
    elseif ($folder != "")
    {
      $result = cutobject ($site, $location, $folder, $user);
      
      $add_onload = $result['add_onload'];
      $show = $result['message'];    
    }     
    elseif ($page != "")
    {
      $result = cutobject ($site, $location, $page, $user);
      
      $add_onload = $result['add_onload'];
      $show = $result['message'];   
    } 
  }
  // copy
  elseif ($action == "copy") 
  {
    if ($multiobject != "")
    {
      $multiobject_array = link_db_getobject ($multiobject);
      $result['result'] = true;
      $i = 1;
      
      foreach ($multiobject_array as $objectpath)
      {
        if ($objectpath != "" && $result['result'] == true)
        {
          $site = getpublication ($objectpath);
          $location = getlocation ($objectpath);
          $page = getobject ($objectpath);

          if ($page != "")
          { 
            $result = copyobject ($site, $location, $page, $user, true);
            
            $add_onload = $result['add_onload'];
            $show = $result['message'];     
          }
        }
        
        $i++;
      }
    }  
    elseif ($folder != "")
    {
      $result = copyobject ($site, $location, $folder, $user);
      
      $add_onload = $result['add_onload'];
      $show = $result['message'];  
    }
    elseif ($page != "")
    {
      $result = copyobject ($site, $location, $page, $user);
      
      $add_onload = $result['add_onload'];
      $show = $result['message'];         
    }    
  }
  // linked copy
  elseif ($action == "linkcopy") 
  {
    if ($multiobject != "")
    {
      $multiobject_array = link_db_getobject ($multiobject);
      $result['result'] = true;
      $i = 1;
      
      foreach ($multiobject_array as $objectpath)
      {
        if ($objectpath != "" && $result['result'] == true)
        {
          $site = getpublication ($objectpath);
          $location = getlocation ($objectpath);
          $page = getobject ($objectpath);
          
          if ($page != "")
          {
            $result = copyconnectedobject ($site, $location, $page, $user, true);
        
            $add_onload = $result['add_onload'];
            $show = $result['message'];    
          }
        }
        
        $i++;
      }
    }
    elseif ($folder != "")
    {
      $result = copyconnectedobject ($site, $location, $folder, $user);
      
      $add_onload = $result['add_onload'];
      $show = $result['message'];  
    }    
    elseif ($page != "")
    {
      $result = copyconnectedobject ($site, $location, $page, $user);
      
      $add_onload = $result['add_onload'];
      $show = $result['message'];      
    }
  }
  // check-in / unlock objects
  elseif ($action == "page_unlock" && $setlocalpermission['root'] == 1)
  {
    if ($multiobject != "")
    {
      $multiobject_array = link_db_getobject ($multiobject);
      
      if (is_array ($multiobject_array))
      {
        $result['result'] = true;
        
        foreach ($multiobject_array as $multiobject_item)
        {
          if ($multiobject_item != "" && $result['result'] == true)
          {
            $site = getpublication ($multiobject_item);
            $page = getobject ($multiobject_item);
            $location = getlocation ($multiobject_item);
            $location = deconvertpath ($location, "file");
  
            // check-in content container
            $result = unlockobject ($site, $location, $page, $user);
          }
        }   
      } 
    }
    elseif ($folder != "" && is_dir ($location.$folder))
    {
      $result = unlockobject ($site, $location.$folder."/", ".folder", $user);
    }
    elseif ($page != "" && $page != ".folder" && is_file ($location.$page))
    {
      $result = unlockobject ($site, $location, $page, $user);
    }
  
    // check result
    if ($result['result'] == false) 
    {
      $show = $result['message'];
      $add_onload = "";
    }
    else 
    {
      $show = $result['message'];
      $add_onload = $result['add_onload'];
      $location = "";
      $page = "";
      $pagename = "";  
      $multiobject = "";
    }
  }  
  // paste
  elseif ($action == "paste") 
  {
    $result = pasteobject ($site, $location, $user);
    
    $add_onload = $result['add_onload'];
    $show = $result['message'];      
  }
  // publish
  elseif ($action == "publish") 
  {
    $result = publishobject ($site, $location, $page, $user);
    $add_onload = $result['add_onload'];
    $show = $result['message'];  
  }
  // unpublish
  elseif ($action == "unpublish") 
  {
    $result = unpublishobject ($site, $location, $page, $user);
    
    $add_onload = $result['add_onload'];
    $show = $result['message'];  
  }
  
  if ($result['result'] != false)
  {
    $add_javascript = $add_onload."
    
  function popupfocus ()
  {
    self.focus();
    setTimeout('popupfocus()', 100);
  }
  
  popupfocus ();
  
  function popupclose ()
  {
    self.close();
  }
  
  setTimeout('popupclose()', 1000);\n";
  
  }
}
else
{
  $show = "<span class=\"hcmsHeadline\">".$text0[$lang]."</span>";
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta name="viewport" content="width=device-width; initial-scale=1.0; user-scalable=1;">
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/click.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<table width="100%" height="120" border=0 cellpadding="3" cellspacing="0">
  <tr>
    <td class="hcmsWorkplaceControlWallpaper" align="left" valign="top" width="20"><img src="<?php echo getthemelocation(); ?>img/info.gif" align="absmiddle"/></td>
    <td align="left" valign="middle"><?php echo $show; ?></td>
  </tr>
</table>

<script language="JavaScript">
<!--
<?php echo $add_javascript; ?>
//-->
</script>

</body>
</html>
