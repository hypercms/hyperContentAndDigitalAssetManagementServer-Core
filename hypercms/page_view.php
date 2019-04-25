<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// template engine
require ("function/hypercms_tplengine.inc.php");


// input parameters
$site = getrequest_esc ("site", "publicationname");
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$view = getrequest ("view");
$follow = getrequest ("follow", "url");
$ctrlreload = getrequest ("ctrlreload");
$hcms_session = getrequest ("hcms_session");

// prepare for EasyEdit mode
if ($follow != "")
{
  $follow = followlink ($site, $follow);
  $site_follow = getpublication ($follow);
  $location = getlocation ($follow);
  $page = getobject ($follow);
  $cat_follow = getcategory ($site, $follow);
  
  if ($site_follow != "") $site = $site_follow;
  if ($cat_follow != "") $cat = $cat_follow;
  else $cat = "page";
  
  // redirect to original files for JS and CSS files
  $follow_info = getfileinfo ($site, $follow, $cat);
  $dont_follow = array (".css", ".js");
  
  // get file extension of follow and redirect to original file if necessary
  if (!empty ($follow_info['ext']) && in_array ($follow_info['ext'], $dont_follow))
  {
    $follow = deconvertpath ($follow, "url");    
    if ($follow != "") header ("Location: ".$follow);
    exit;
  }
}
else
{
  // get publication and category
  $site = getpublication ($location);
  $cat = getcategory ($site, $location);
}

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

// check localpermissions for DAM usage only
if (!checkpublicationpermission ($site) || (!empty ($mgmt_config[$site]['dam']) && empty ($setlocalpermission['root']))) killsession ($user);
// check for general root element access since localpermissions are checked later
// Attention! variable page can be empty when a new object will be created
elseif (
         !checkpublicationpermission ($site) || 
         (!valid_objectname ($page) && ($setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1)) || 
         !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($cat)
       ) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

if ($ctrlreload == "") $ctrlreload = "yes"; 

// set view in session
if ($view != "")
{
  if ($view == "cmsview" || $view == "inlineview")
  {
    setsession ('hcms_temp_objectview', $view, true);
    
    // save GUI settings
    if (!empty ($_SESSION['hcms_temp_objectview']) && !empty ($_SESSION['hcms_temp_explorerview']) && isset ($_SESSION['hcms_temp_sidebar']) && !empty ($_SESSION['hcms_user']))
    {
      setguiview ($_SESSION['hcms_temp_objectview'], $_SESSION['hcms_temp_explorerview'], $_SESSION['hcms_temp_sidebar'], $_SESSION['hcms_user']);
    }
  }
}
// set default view
else
{
  if ($temp_objectview != "") $view = $temp_objectview;
  elseif ($mgmt_config['objectview'] != "") $view = $mgmt_config['objectview'];
  else $view = "cmsview";
}

// set hyperCMS session
if (is_array ($hcms_session))
{
  $update_session = false;
  
  foreach ($hcms_session as $key => $value)
  {
    // if session key is allowed (prefix hcms_ must not be used for the name)
    if ($key != "" && substr ($key, 0, 5) != "hcms_")
    { 
      $_SESSION[$key] = $value;
      $update_session = true;
    }
  }
  
  // write session data if load balancer is used and session data need to be updated
  if ($update_session) writesessiondata ();
}

// if link refers to other domain (external page) or to mail client (mailto)
if (@substr_count ($follow, "://") > 0 || @substr_count (strtolower ($follow), "mailto:") > 0)
{
  if (@substr_count ($follow, "://") > 0)
  {
    $add_code = "<script type=\"text/javascript\">
    function urlforward()
    {
      document.location='".$follow."';
    }

    setTimeout('urlforward()', 2000);
    </script>";
  }
  else $add_code = "<a href=\"".$follow."\" target=\"_blank\">".$follow."</a>";
  
  echo "<!DOCTYPE html>\n";
  echo "<html>\n";
  echo "<head>\n";
  echo "<title>hyperCMS</title>\n";
  echo "<meta charset=\"".getcodepage ($lang)."\" />\n";
  echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />\n";
  echo "<script src=\"javascript/click.js\" type=\"text/javascript\" />\n";
  echo "</script>\n";
  echo "</head>\n";
  echo "<body class=\"hcmsWorkplaceGeneric\">\n";
  echo "<span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['you-will-be-forwarded-to'][$lang])." </span>".$follow."<br \>\n";
  echo $add_code;
  echo "</body>\n</html>";
  exit;
}
// if link refers to a managed object (internal page)
else
{
  if (valid_publicationname ($site) && valid_locationname ($location) && valid_objectname ($page))
  {
    // ---------------------------- call template engine ---------------------------    
    $result = buildview ($site, $location, $page, $user, $view, $ctrlreload);

    $charset = $result['charset'];
    $viewstore = $result['view'];
    $contentfile = $result['container'];
    $contentdata = $result['containerdata'];
    $templatefile = $result['template'];
    $templatedata = $result['templatedata'];
    $filetype = $result['objecttype'];  
    // -----------------------------------------------------------------------------

    // object is managed by hyperCMS  
    if ($contentfile != false || $templatefile != false)
    {
      // if template is empty
      if ($templatefile == false || $templatefile == "")
      {
        echo "<!DOCTYPE html>\n";
        echo "<html lang=\"".getsession("hcms_lang", "en")."\">\n";
        echo "<head>\n";
        echo "<title>hyperCMS</title>\n";
        echo "<meta charset=\"".getcodepage ($lang)."\" />\n";
        echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />\n";
        echo "<script src=\"javascript/click.js\" type=\"text/javascript\">\n";
        echo "</script>\n";
        echo "</head>\n";
        echo "<body class=\"hcmsWorkplaceGeneric\" style=\"padding:3px;\">\n";
        echo "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['could-not-create-view-due-to-empty-template-'][$lang])."</p>\n";
        echo getescapedtext ($hcms_lang['the-template-holds-no-information'][$lang]).": '".$templatefile."'\n";
        echo "</body>\n</html>";
        exit;
      }
      
      // if content container is empty
      if ($contentfile == false || $contentfile == "")
      {
        echo "<!DOCTYPE html>\n";
        echo "<html lang=\"".getsession("hcms_lang", "en")."\">\n";
        echo "<head>\n";
        echo "<title>hyperCMS</title>\n";
        echo "<meta charset=\"".getcodepage ($lang)."\" />\n";
        echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />\n";
        echo "</script>\n";
        echo "</head>\n";    
        echo "</head>\n";
        echo "<body class=\"hcmsWorkplaceGeneric\" style=\"padding:3px;\">\n";
        echo "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['could-not-create-view-due-to-empty-content-container'][$lang])."</p>\n";
        echo getescapedtext ($hcms_lang['the-content-container-holds-no-information'][$lang]).": '".$contentfile."'\n";
        echo "</body>\n</html>";
        exit;
      }
    
      // check if an error occured during building view
      if ($viewstore == false)
      {
        echo "<!DOCTYPE html>\n";
        echo "<html lang=\"".getsession("hcms_lang", "en")."\">\n";
        echo "<head>\n";
        echo "<title>hyperCMS</title>\n";
        echo "<meta charset=\"".getcodepage ($lang)."\" />\n";
        echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />\n";
        echo "</head>\n";
        echo "<body class=\"hcmsWorkplaceGeneric\" style=\"padding:3px;\">\n";
        echo "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['could-not-create-view-of-the-object'][$lang])."</p>\n";
        echo getescapedtext ($hcms_lang['an-error-occured-while-creating-the-view'][$lang])."\n";
        echo "</body>\n";
        echo "</html>";
        exit;
      }
      // output view
      else
      {
        header ('Content-Type: text/html; charset='.$charset);
        echo $viewstore;
      }
    }
    // if object is not managed by hyperCMS
    else
    {
      // define forward URL (decprecated for security reasons)
      if ($cat == "page")
      {
        $forwardurl = str_replace ($mgmt_config[$site]['abs_path_page'], $mgmt_config[$site]['url_path_page'], $location).$page;
      }
      elseif ($cat == "comp")
      {
        $forwardurl = str_replace ($mgmt_config['abs_path_comp'], $mgmt_config['url_path_comp'], $location).$page;
      }
      else $forwardurl = "";
           
      // -------------------------------------- build view of live page ------------------------------------
      // if object is not managed by hyperCMS
      echo "<!DOCTYPE html>\n";
      echo "<html lang=\"".getsession("hcms_lang", "en")."\">\n";
      echo "<head>\n";
      echo "<title>hyperCMS</title>\n";
      echo "<meta charset=\"".getcodepage ($lang)."\" />\n";
      echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />\n"; 
      echo "</head>\n";
      echo "<body class=\"hcmsWorkplaceGeneric\" style=\"padding:3px;\">\n";
      echo "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['this-object-is-not-managed-by-hypercms-or-you-dont-have-access-to-it'][$lang])."</p><br /><br />\n";
      echo "</body>\n";
      echo "</html>\n";
      exit;    
    }
  }
  else
  {
    echo "<!DOCTYPE html>\n";
    echo "<html lang=\"".getsession("hcms_lang", "en")."\">\n";
    echo "<head>\n";
    echo "<title>hyperCMS</title>\n";
    echo "<meta charset=\"".getcodepage ($lang)."\" />\n";
    echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\" />\n";
    echo "</head>\n";
    echo "<body class=\"hcmsWorkplaceGeneric\" style=\"padding:3px;\">\n";
    echo "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['the-object-does-not-exist'][$lang])."</p>\n";
    echo "</body>\n";
    echo "</html>";
    exit;
  }
}
?>