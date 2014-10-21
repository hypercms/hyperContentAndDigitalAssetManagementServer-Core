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
// template engine
require ("function/hypercms_tplengine.inc.php");
// language file
require_once ("language/page_view.inc.php");


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
if (!checkpublicationpermission ($site) || ($mgmt_config[$site]['dam'] == true && $setlocalpermission['root'] != 1)) killsession ($user);
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
  if ($view == "cmsview" || $view == "inlineview") $_SESSION['hcms_temp_objectview'] = $view;
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
  foreach ($hcms_session as $key => $value)
  {
    // if session key is allowed
    if ($key != "" && substr_count ("|user|passwd|lang|siteaccess|pageaccess|compaccess|rootpermission|globalpermission|localpermission|hiddenfolder|hcms_linking|explorerview|temp_site|temp_user|temp_pagelocation|temp_complocation|temp_token|", "|".$key."|") == 0)
    { 
      $_SESSION[$key] = $value;
    }
  }
}


// if link refers to other domain (external page) or to mail client (mailto)
if (@substr_count ($follow, "://") > 0 || @substr_count (strtolower ($follow), "mailto:") > 0)
{
  if (@substr_count ($follow, "://") > 0)
  {
    $add_code = "<script type=\"text/javascript\">
    <!--
    function urlforward()
    {
      document.location.href='".$follow."';
    }

    setTimeout('urlforward()', 2000);
    -->
    </script>";
  }
  else $add_code = "<a href=\"".$follow."\" target=\"_blank\">".$follow."</a>";
  
  echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";
  echo "<html>\n";
  echo "<head>\n";
  echo "<title>hyperCMS</title>\n";
  echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$lang_codepage[$lang]."\">\n";
  echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\">\n";
  echo "<script src=\"javascript/click.js\" type=\"text/javascript\">\n";
  echo "</script>\n";
  echo "</head>\n";
  echo "<body class=\"hcmsWorkplaceGeneric\">\n";
  echo "<span class=hcmsHeadline>".$text13[$lang].":</span>".$follow."<br \>\n";
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
        echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";
        echo "<html>\n";
        echo "<head>\n";
        echo "<title>hyperCMS</title>\n";
        echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$lang_codepage[$lang]."\">\n";
        echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\">\n";
        echo "<script src=\"javascript/click.js\" type=\"text/javascript\">\n";
        echo "</script>\n";
        echo "</head>\n";
        echo "<body class=\"hcmsWorkplaceGeneric\" style=\"padding:3px;\">\n";
        echo "<p class=hcmsHeadline>".$text1[$lang]."</p>\n";
        echo $text2[$lang].": '".$templatefile."'\n";
        echo "</body>\n</html>";
        exit;
      }
      
      // if content container is empty
      if ($contentfile == false || $contentfile == "")
      {
        echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";
        echo "<html>\n";
        echo "<head>\n";
        echo "<title>hyperCMS</title>\n";
        echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$lang_codepage[$lang]."\">\n";
        echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\">\n";
        echo "<script src=\"javascript/click.js\" type=\"text/javascript\">\n";
        echo "</script>\n";
        echo "</head>\n";    
        echo "</head>\n";
        echo "<body class=\"hcmsWorkplaceGeneric\" style=\"padding:3px;\">\n";
        echo "<p class=hcmsHeadline>".$text3[$lang]."</p>\n";
        echo $text4[$lang].": '".$contentfile."'\n";
        echo "</body>\n</html>";
        exit;
      }
    
      // check if an error occured during building view
      if ($viewstore == false)
      {
        echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";
        echo "<html>\n";
        echo "<head>\n";
        echo "<title>hyperCMS</title>\n";
        echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$lang_codepage[$lang]."\">\n";
        echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\">\n";
        echo "</head>\n";
        echo "<body class=\"hcmsWorkplaceGeneric\" style=\"padding:3px;\">\n";
        echo "<p class=hcmsHeadline>".$text5[$lang]."</p>\n";
        echo $text6[$lang]."\n";
        echo "</body>\n";
        echo "</html>";
        exit;
      }
      // output view
      else
      {
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
      echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";
      echo "<html>\n";
      echo "<head>\n";
      echo "<title>hyperCMS</title>\n";
      echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$lang_codepage[$lang]."\">\n";
      echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\">\n"; 
      echo "</head>\n";
      echo "<body class=\"hcmsWorkplaceGeneric\" style=\"padding:3px;\">\n";
      echo "<p class=hcmsHeadline>".$text7[$lang]."</p><br /><br />\n";
      echo "</body>\n";
      echo "</html>\n";
      exit;    
    }
  }
  else
  {
    echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "<title>hyperCMS</title>\n";
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$lang_codepage[$lang]."\">\n";
    echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\">\n";
    echo "</head>\n";
    echo "<body class=\"hcmsWorkplaceGeneric\" style=\"padding:3px;\">\n";
    echo "<p class=hcmsHeadline>".$text10[$lang]."</p>\n";
    echo "</body>\n";
    echo "</html>";
    exit;
  }
}
?>