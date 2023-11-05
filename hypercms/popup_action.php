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

// verify the permissions of the user in the API functions
$mgmt_config['api_checkpermission'] = true;


// input parameters
$action = getrequest ("action");
$multiobject = getrequest ("multiobject");
$location = getrequest ("location", "locationname");
$folder = getrequest ("folder", "objectname");
$page = getrequest ("page", "objectname");
$wf_token = getrequest ("wf_token");
$from_page = getrequest ("from_page");
$token = getrequest ("token");

// no location provided
if ($location == "" && is_string ($multiobject) && strlen ($multiobject) > 6)
{
  $multiobject_array = link_db_getobject ($multiobject);
  $location = $multiobject_array[0];
}

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// publication management config
if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
{
  require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
}

// convert location
$location = deconvertpath ($location, "file");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

if (!valid_publicationname ($site) || !valid_locationname ($location)) killsession ($user);

// check session of user
checkusersession ($user, false);

?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/click.min.js"></script>
<style type="text/css">
.messageLayer
{
  padding: 3px;
  margin:3px 0px;
  box-sizing: border-box;
  vertical-align: middle;
  -moz-border-radius: 5px;
  -webkit-border-radius: 5px;
  border-radius: 5px;
  overflow: hidden;
  text-overflow: ellipsis;
  word-wrap: break-word;
  color: #222222;
  background-color: #F3F3F3;
}
</style>
</head>

<body class="hcmsWorkplaceGeneric" style="overflow:auto;">

<!-- load screen --> 
<div id="hcmsLoadScreen" class="hcmsLoadScreen" style="display:inline; filter:alpha(opacity=40); -moz-opacity:0.4; opacity:0.4; padding:42px 0px;"></div>

<!-- top bar -->
<?php
if ($action == "page_favorites_create") $headline = getescapedtext ($hcms_lang['add-to-favorites'][$lang]);
elseif ($action == "page_favorites_delete") $headline = getescapedtext ($hcms_lang['delete-favorite'][$lang]);
elseif ($action == "page_unlock") $headline = getescapedtext ($hcms_lang['check-in'][$lang]);
elseif ($action == "unzip") $headline = getescapedtext ($hcms_lang['uncompress-files'][$lang]);
elseif ($action == "cut") $headline = getescapedtext ($hcms_lang['cut'][$lang]);
elseif ($action == "copy") $headline = getescapedtext ($hcms_lang['copy'][$lang]);
elseif ($action == "linkcopy") $headline = getescapedtext ($hcms_lang['connected-copy'][$lang]);
elseif ($action == "paste") $headline = getescapedtext ($hcms_lang['paste'][$lang]);
elseif ($action == "delete" || $action == "deletemark") $headline = getescapedtext ($hcms_lang['delete'][$lang]);
elseif ($action == "emptybin") $headline = getescapedtext ($hcms_lang['empty-recycle-bin'][$lang]);
elseif ($action == "restore" || $action == "deleteunmark") $headline = getescapedtext ($hcms_lang['restore'][$lang]);
elseif ($action == "publish") $headline = getescapedtext ($hcms_lang['publish-content'][$lang]);
elseif ($action == "unpublish") $headline = getescapedtext ($hcms_lang['unpublish-content'][$lang]);

echo showtopbar ($headline, $lang);
?>

<div class="hcmsWorkplaceFrame">

<?php
// --------------------------------- logic section ----------------------------------

// flush in order to display load screen
// do not use it for action "publish" since the output will interfere with the session_start used in the template engine
if ($action != "publish")
{
  flushoutputbuffer ();
}

// initialize
$show = "";
$add_onload = "";
$multiobject_array = array();
$result = array();

// check authorization
$authorized = false;

if ($setlocalpermission['root'] == 1 && checktoken ($token, $user))
{
  if (($action == "delete" || $action == "deletemark" || $action == "restore") && ($setlocalpermission['delete'] == 1 || $setlocalpermission['folderdelete'] == 1)) $authorized = true;
  elseif (($action == "cut" || $action == "copy" || $action == "linkcopy") && ($setlocalpermission['rename'] == 1 || $setlocalpermission['folderrename'] == 1)) $authorized = true;
  elseif (($action == "page_favorites_create" || $action == "page_favorites_delete") && $setlocalpermission['create'] == 1) $authorized = true;
  elseif ($action == "page_unlock" && ($page != "" && $setlocalpermission['create'] == 1) || ($folder != "" && $setlocalpermission['foldercreate'] == 1)) $authorized = true;
  elseif ($action == "paste" && ($setlocalpermission['rename'] == 1 || $setlocalpermission['folderrename'] == 1)) $authorized = true;
  elseif (($action == "publish" || $action == "unpublish") && $setlocalpermission['publish'] == 1) $authorized = true;
  elseif ($action == "unzip") $authorized = true;
}

if ($authorized == true)
{
  // empty clipboard
  setsession ('hcms_temp_clipboard', "");

  // perform actions
  // priority for processing due to all variables (multiobject, folder, page) 
  // will be posted from the context menu:
  // 1. multiobject
  // 2. folder
  // 3. object

  // unzip
  if ($action == "unzip")
  {
    // action for unzip is below
  }
  // delete
  elseif ($action == "delete" || $action == "deletemark" || $action == "deleteunmark" || $action == "restore") 
  {
    // reset action
    if ($from_page != "recyclebin" && $action == "delete" && !empty ($mgmt_config['recyclebin'])) $action = "deletemark";

    if (is_string ($multiobject) && strlen ($multiobject) > 6) $multiobject_array = link_db_getobject ($multiobject);

    if (is_array ($multiobject_array) && sizeof ($multiobject_array) > 0)
    {
      $result['result'] = true;

      // delete objects
      foreach ($multiobject_array as $objectpath)
      {
        if ($objectpath != "" && !empty ($result['result']))
        {
          $site = getpublication ($objectpath);
          $location = getlocation ($objectpath);
          $page = getobject ($objectpath);

          if ($page != "")
          {
            // delete object
            if ($action == "delete") $result = deleteobject ($site, $location, $page, $user);
            // mark object as deleted
            elseif ($action == "deletemark") $result = deletemarkobject ($site, $location, $page, $user);
            // unmark object as deleted
            elseif ($action == "restore" || $action == "deleteunmark") $result = deleteunmarkobject ($site, $location, $page, $user);

            $add_onload = $result['add_onload'];
            $show = $result['message'];
          }
        }
      }
    }
    elseif ($page != "")
    {
      // delete object
      if ($action == "delete") $result = deleteobject ($site, $location, $page, $user);
      // mark object as deleted
      elseif ($action == "deletemark") $result = deletemarkobject ($site, $location, $page, $user);
      // unmark object as deleted
      elseif ($action == "restore" || $action == "deleteunmark") $result = deleteunmarkobject ($site, $location, $page, $user);

      $add_onload = $result['add_onload'];
      $show = $result['message'];       
    }    
  }
  // cut, copy, linkcopy
  elseif ($action == "cut" || $action == "copy" || $action == "linkcopy") 
  {
    if (is_string ($multiobject) && strlen ($multiobject) > 6) $multiobject_array = link_db_getobject ($multiobject);

    if (is_array ($multiobject_array) && sizeof ($multiobject_array) > 0)
    {
      $result['result'] = true;

      foreach ($multiobject_array as $objectpath)
      {
        if ($objectpath != "" && !empty ($result['result']))
        {
          $site = getpublication ($objectpath);
          $location = getlocation ($objectpath);
          $page = getobject ($objectpath);

          if ($site != "" && $location != "" && $page != "")
          {
            if ($action == "cut") $result = cutobject ($site, $location, $page, $user, true);
            elseif ($action == "copy") $result = copyobject ($site, $location, $page, $user, true);
            elseif ($action == "linkcopy") $result = copyconnectedobject ($site, $location, $page, $user, true);

            if (!empty ($result['add_onload'])) $add_onload = $result['add_onload'];
            if (!empty ($result['message'])) $show = $result['message'];   
          }
        }
      }
    }
    elseif ($folder != "")
    {
      if ($action == "cut") $result = cutobject ($site, $location, $folder, $user);
      elseif ($action == "copy") $result = copyobject ($site, $location, $folder, $user);
      elseif ($action == "linkcopy") $result = copyconnectedobject ($site, $location, $folder, $user);

      if (!empty ($result['add_onload'])) $add_onload = $result['add_onload'];
      if (!empty ($result['message'])) $show = $result['message'];    
    }     
    elseif ($page != "")
    {
      if ($action == "cut") $result = cutobject ($site, $location, $page, $user);
      elseif ($action == "copy") $result = copyobject ($site, $location, $page, $user);
      elseif ($action == "linkcopy") $result = copyconnectedobject ($site, $location, $page, $user);

      if (!empty ($result['add_onload'])) $add_onload = $result['add_onload'];
      if (!empty ($result['message'])) $show = $result['message'];   
    }
  }
  // remove objects from favorites
  elseif (($action == "page_favorites_create" || $action == "page_favorites_delete") && $setlocalpermission['root'] == 1)
  {
    if (is_string ($multiobject) && strlen ($multiobject) > 6) $multiobject_array = link_db_getobject ($multiobject);

    if (is_array ($multiobject_array) && sizeof ($multiobject_array) > 0)
    {
      $result['result'] = true;

      foreach ($multiobject_array as $multiobject_item)
      {
        if ($multiobject_item != "" && !empty ($result['result']))
        {
          $site = getpublication ($multiobject_item);
          $page = getobject ($multiobject_item);
          $location = getlocation ($multiobject_item);
          $location = deconvertpath ($location, "file");

          if ($action == "page_favorites_create") $result['result'] = createfavorite ($site, $location, $page, "", $user);
          elseif ($action == "page_favorites_delete") $result['result'] = deletefavorite ($site, $location, $page, "", $user);
        }
      }
    }
    elseif ($folder != "" && is_dir ($location.$folder))
    {
      if ($action == "page_favorites_create") $result['result'] = createfavorite ($site, $location.$folder."/", ".folder", "", $user);
      elseif ($action == "page_favorites_delete") $result['result'] = deletefavorite ($site, $location.$folder."/", ".folder", "", $user);
    }
    elseif ($page != "" && $page != ".folder" && is_file ($location.$page))
    {
      if ($action == "page_favorites_create") $result['result'] = createfavorite ($site, $location, $page, "", $user);
      elseif ($action == "page_favorites_delete") $result['result'] = deletefavorite ($site, $location, $page, "", $user);
    }

    // on error
    if (empty ($result['result'])) 
    {
      $show = "<span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['error-occured'][$lang])."</span>";
      $add_onload = "";
    }
    // on success
    else 
    {
      $show = "<span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['the-data-was-saved-successfully'][$lang])."</span>";
      $add_onload = "if (window.top.frames['workplFrame'] && window.top.frames['workplFrame'].frames['mainFrame']) window.top.frames['workplFrame'].frames['mainFrame'].location.reload();";
      $location = "";
      $page = "";
      $pagename = "";  
      $multiobject = "";
    }
  }  
  // check-in / unlock objects
  elseif ($action == "page_unlock" && checkrootpermission ("desktopcheckedout") && $setlocalpermission['root'] == 1)
  {
    if (is_string ($multiobject) && strlen ($multiobject) > 6) $multiobject_array = link_db_getobject ($multiobject);

    if (is_array ($multiobject_array) && sizeof ($multiobject_array) > 0)
    {
      $result['result'] = true;

      foreach ($multiobject_array as $multiobject_item)
      {
        if ($multiobject_item != "" && !empty ($result['result']))
        {
          $site = getpublication ($multiobject_item);
          $page = getobject ($multiobject_item);
          $location = getlocation ($multiobject_item);
          $location = deconvertpath ($location, "file");

          $result = unlockobject ($site, $location, $page, $user);
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

    // on error
    if (empty ($result['result'])) 
    {
      $show = $result['message'];
      $add_onload = "";
    }
    // on success
    else 
    {
      $show = $result['message'];
      $add_onload = "if (window.top.frames['workplFrame'] && window.top.frames['workplFrame'].frames['mainFrame']) window.top.frames['workplFrame'].frames['mainFrame'].location.reload();";
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
}
// permission not granted
else
{
  $result['result'] = true;
  $add_onload = "";
  $show = "<span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['you-do-not-have-permissions-to-execute-this-function'][$lang])."</span>";
}

// unzip
if ($action == "unzip" && $authorized == true)
{
  // load object file and get container and media file
  $objectdata = loadfile ($location, $page);
  $mediafile = getfilename ($objectdata, "media");    
  $mediapath = getmedialocation ($site, $mediafile, "abs_path_media");
  $media_info = getfileinfo ($site, $location.$page, $cat);

  // unzip file in assets
  if ($cat == "comp" && $mediapath != "" && $mediafile != "" && $location != "")
  {
    $result_unzip = unzipfile ($site, $mediapath.$site.'/'.$mediafile, $location, $media_info['name'], $cat, $user, true, true);
  }
  // unzip file in pages
  elseif ($cat == "page" && $location != "" && $page != "")
  {
    $result_unzip = unzipfile ($site, $location.$page, $location, $media_info['name'], $cat, $user, true, true);
  }
  else $result_unzip = false;
 
  // on success
  if (!empty ($result_unzip))
  {
    $result['result'] = true;
    $add_onload = "document.getElementById('hcmsLoadScreen').style.display='none'; if (window.top.frames['workplFrame'] && window.top.frames['workplFrame'].frames['mainFrame']) window.top.frames['workplFrame'].frames['mainFrame'].location.reload();";
    $show = "<span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['file-extracted-succesfully'][$lang])."</span><br />\n";
  }
  // on error
  else
  {
    $result['result'] = false;
    $add_onload = "document.getElementById('hcmsLoadScreen').style.display='none';\n";
    $show = "<span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['file-could-not-be-extracted'][$lang])."</span><br />\n";
  }
}

// no objects provided
if ($show == "")
{
  $result['result'] = true;
  $add_onload = "";
  $show = "<span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['no-file-selected'][$lang])."</span><br />\n";
}
?>

<!-- action -->

  <table class="hcmsTableNarrow" style="width:100%; height:140px;">
    <tr>
      <td style="text-align:center; vertical-align:middle;">
        <?php echo $show; ?><br/><br/>
        <?php echo showactionicon ($action, $lang); ?>
      </td>
    </tr>
  </table>

</div>

<script type="text/javascript">
// hide load screen
if (document.getElementById('hcmsLoadScreen')) document.getElementById('hcmsLoadScreen').style.display = 'none';

<?php
echo $add_onload;

if (!empty ($result['result']))
{
  echo "
// close popup frame
function popupclose ()
{
  var id = parent.document.getElementById(window.name).id;

  if (id.indexOf('Frame') > 0)
  {
    id = id.substring(0, id.length - 5);
    if (parent.document.getElementById(id) && typeof parent.closePopup == 'function') parent.closePopup(id);
  }
}

setTimeout('popupclose()', 1500);";
}
?>
</script>

</body>
</html>
