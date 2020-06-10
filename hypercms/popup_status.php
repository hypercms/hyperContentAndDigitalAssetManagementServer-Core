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


// input parameters
$action = getrequest ("action");
$action_esc = getrequest_esc ("action");
$multiobject = getrequest ("multiobject");
$targetlocation = getrequest_esc ("targetlocation", "locationname");
$location_orig = getrequest_esc ("location", "locationname");
$folder = getrequest_esc ("folder", "objectname");
$page = getrequest_esc ("page", "objectname");
$force = getrequest_esc ("force");
$method = getrequest_esc ("method");
$maxcount = getrequest_esc ("maxcount", "numeric");
$published_only = getrequest_esc ("published_only");
$tempfile = getrequest_esc ("tempfile", "locationname");
$from_page = getrequest_esc ("from_page");
$token= getrequest_esc ("token");
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script src="javascript/click.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- load screen --> 
<div id="hcmsLoadScreen" class="hcmsLoadScreen" style="display:inline;"></div>

<?php
// flush in order to display load screen
// do not use it for action "publish" since the output will interfere with the session_start used in the template engine
if ($force == "start" && $action != "publish")
{
  ob_implicit_flush (true);
  ob_end_flush ();
  //sleep (1);
}

// ==================================== stage 1 (only for cut, copy, linkcopy) ====================================

// if action includes 2 stages (cut or copy and paste)
if ($force == "start" && substr_count ($action, "->") == 1)
{
  list ($method, $action) = explode ("->", $action);

  // correct location for access permission
  $location = $location_orig;

  // get publication and category
  $site = getpublication ($location);
  $cat = getcategory ($site, $location);

  // publication management config
  if (valid_publicationname ($site)) require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

  // convert location
  $location = deconvertpath ($location, "file");
  $location_esc = convertpath ($site, $location, $cat);

  // ------------------------------ permission section --------------------------------

  // check authorization
  $authorized = false;

  // check access permissions
  $ownergroup = accesspermission ($site, $location, $cat);
  $setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

  if ($setlocalpermission['root'] == 1 && checktoken ($token, $user))
  {
    if (($method == "cut" || $method == "copy" || $method == "linkcopy") && ($setlocalpermission['rename'] == 1 && $setlocalpermission['folderrename'] == 1)) $authorized = true;
  }

  // --------------------------------- logic section ----------------------------------

  // initalize
  $result = array();

  // execute action
  if ($authorized == true)
  {
    // empty clipboard
    setsession ('hcms_temp_clipboard', "");

    if ($multiobject != "")
    {
      $multiobject_array = link_db_getobject ($multiobject);
      $result['result'] = true;

      foreach ($multiobject_array as $objectpath)
      {
        if ($objectpath != "" && $result['result'] == true)
        {
          $temp_site = getpublication ($objectpath);
          $temp_location = getlocation ($objectpath);
          $temp_page = getobject ($objectpath);

          if ($temp_site != "" && $temp_location != "" && $temp_page != "")
          {
            if ($method == "cut") $result = cutobject ($temp_site, $temp_location, $temp_page, $user, true);
            elseif ($method == "copy") $result = copyobject ($temp_site, $temp_location, $temp_page, $user, true);
            elseif ($method == "linkcopy") $result = copyconnectedobject ($temp_site, $temp_location, $temp_page, $user, true);
          }
        }
      }
    }
    elseif ($folder != "")
    {
      if ($method == "cut") $result = cutobject ($site, $location, $folder, $user);
      elseif ($method == "copy") $result = copyobject ($site, $location, $folder, $user);
      elseif ($method == "linkcopy") $result = copyconnectedobject ($site, $location, $folder, $user);
    }     
    elseif ($page != "")
    {
      if ($method == "cut") $result = cutobject ($site, $location, $page, $user);
      elseif ($method == "copy") copyobject ($site, $location, $page, $user);
      elseif ($method == "linkcopy") $result = copyconnectedobject ($site, $location, $page, $user);
    } 

    if (!empty ($result['message'])) $show = $result['message']; 

    // use target location for paste
    $location_orig = $targetlocation;
    $folder = "";
    $page = "";
  }
}

// ==================================== stage 2 ====================================

// set current location (for action "paste" the folder is not part of the location to paste)
if ($folder != "" && $action != "paste")
{
  $location = $location_orig.$folder."/";
}
else $location = $location_orig;

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// publication management config
if (valid_publicationname ($site)) require_once ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

if ($action != "emptybin" && ($ownergroup == false || $setlocalpermission['root'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location))) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// initalize
$add_javascript = "window.focus();";
$count = 0;

// check authorization of requested action
$authorized = false;

if (($setlocalpermission['root'] == 1 || $action == "emptybin") && checktoken ($token, $user))
{
  // recycle bin should be emptied
  if ($action == "emptybin")
  {
    $authorized = true;
  }
  // other actions
  else
  {
    if (($action == "delete" || $action == "deletemark" || $action == "restore") && $setlocalpermission['folderdelete'] == 1 && $setlocalpermission['delete'] == 1) $authorized = true;
    elseif ($action == "paste" && $setlocalpermission['folderrename'] == 1 && $setlocalpermission['rename'] == 1) $authorized = true;
    elseif (($action == "publish" || $action == "unpublish") && $setlocalpermission['publish'] == 1) $authorized = true;
    
    // check if folder or object exists
    if ($force == "start" && $action != "paste")
    {
      if ($location != "" && $page != "" && !is_file ($location.correctfile ($location, $page, $user))) $authorized = false;
      elseif ($location != "" && $folder != "" && !is_dir ($location)) $authorized = false;
    }
  }
}

// execute action
if ($authorized == true || $force == "stop")
{
  // start/continue process
  if ($force == "start" || $force == "continue")
  {
    $multiobject_temp = array();
    $multiobject_array = array();

    // define multiobject array as input
    if ($force == "start")
    {
      // multiobjects are not possible if action = paste
      if (is_string ($multiobject) && strlen ($multiobject) > 6) $multiobject_temp = link_db_getobject ($multiobject);

      if (is_array ($multiobject_temp) && sizeof ($multiobject_temp) > 1 && $action != "paste")
      {
        $multiobject_array = $multiobject_temp;
      }
      elseif ($site != "" && $location != "" && strlen ($location) > 6)
      {
        if ($folder != "") $multiobject_array[0] = convertpath ($site, $location.".folder", $cat); 
        $multiobject_array[0] = convertpath ($site, $location.$page, $cat);
      }
    }

    // for publish and unpublish
    if ($published_only != "1") $published_only = "0";
    else $published_only = "1";

    // process objects:
    // method is used for action = paste -> methods: cut, copy, linkcopy
    // $source_root and $source_folder are passed as global variables to the functions and are needed if action = paste.
    if ($from_page != "recyclebin" && $action == "delete" && !empty ($mgmt_config['recyclebin'])) $action = "deletemark"; 

    $result = manipulateallobjects ($action, $multiobject_array, "$method", $force, $published_only, $user, $tempfile);
    
    // if manipulation was successful
    if ($result['result'] != false) 
    {
      if ($maxcount == "") $maxcount = $result['maxcount'];

      if (isset ($result['count'])) $count = $result['count']; 
      else $count =  0;
      
      if (!empty ($maxcount) && !empty ($count)) $status = ($maxcount - $count)." / ".$maxcount." ".getescapedtext ($hcms_lang['items'][$lang]);
      else $status = "";
      
      if (isset ($result['working'])) $working = $result['working'];
      else $working = false;
      
      if (isset ($result['method'])) $method = $result['method'];
      else $method = "";
      
      if (isset ($result['tempfile'])) $tempfile = $result['tempfile'];   
      else $tempfile = "";
     
      // define next process
      if ($working == true)
      {
        $add_javascript = "document.location='".$mgmt_config['url_path_cms']."popup_status.php?force=continue&action=".url_encode($action)."&tempfile=".url_encode($tempfile)."&method=".url_encode($method)."&maxcount=".url_encode($maxcount)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_orig)."&folder=".url_encode($folder)."&page=".url_encode($page)."&from_page=".url_encode($from_page)."&token=".url_encode($token)."';\n";
      }
      elseif ($working == false)
      {
        $add_javascript = "document.location='".$mgmt_config['url_path_cms']."popup_status.php?force=finish&action=".url_encode($action)."&tempfile=".url_encode($tempfile)."&method=".url_encode($method)."&maxcount=".url_encode($maxcount)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_orig)."&folder=".url_encode($folder)."&page=".url_encode($page)."&from_page=".url_encode($from_page)."&token=".url_encode($token)."';\n"; 
      }    
    }
    // if an error occured
    else 
    {
      if (!isset ($maxcount)) $maxcount = $result['maxcount'];
      
      if (isset ($result['count'])) $count = $result['count']; 
      else $count =  0;
      
      if (!empty ($result['message'])) $status = strip_tags ($result['message']);    
      else $status = getescapedtext ($hcms_lang['error-occured'][$lang]);
      
      $working = "error";
      
      $meta_refresh = "";
    } 
  }
  // finish process
  elseif ($force == "finish")
  {
    if ($action == "paste" && $method != "cut")
    {
      // not suitable for EasyEdit
      $add_javascript = "
    if (opener && opener.parent.frames['mainFrame']) opener.parent.frames['mainFrame'].location.reload();

    self.close();
    ";
    }
    elseif ($action == "paste" && $method == "cut")
    {
      // not suitable for EasyEdit
      $add_javascript = "
    // reload root node in explorer
    if (opener && opener.document.getElementById('a_".$cat."_".$site."')) opener.document.getElementById('a_".$cat."_".$site."').click();

    // reload objectlist from explorer
    if (opener && opener.parent.frames['workplFrame'] && opener.parent.frames['workplFrame'].frames['mainFrame']) opener.parent.frames['workplFrame'].frames['mainFrame'].location.reload();
    // reload objectlist from itself
    else if (opener && opener.parent.frames['mainFrame']) opener.parent.frames['mainFrame'].location.reload();

    self.close();
    ";
    }  
    elseif ($action == "delete" || $action == "deletemark" || $action == "deleteunmark" || $action == "restore" || $action == "emptybin")
    {
      // for recycle bin
      if ($from_page == "recyclebin")
      {
        // not suitable for EasyEdit
        $add_javascript = "
    // reload control
    if (opener && opener.parent.frames['controlFrame']) opener.parent.frames['controlFrame'].location='control_objectlist_menu.php?virtual=1&action=recyclebin';

    // reload objectlist
    if (opener && opener.parent.frames['mainFrame']) opener.parent.frames['mainFrame'].location.reload();

    self.close();
    ";
      }
      else
      {
        // not suitable for EasyEdit
        $add_javascript = "
    // reload control
    if (opener && opener.parent.frames['controlFrame']) opener.parent.frames['controlFrame'].location='control_objectlist_menu.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_orig)."';

    // reload objectlist
    if (opener && opener.parent.frames['mainFrame']) opener.parent.frames['mainFrame'].location.reload();

    self.close();
    ";
      }
    }
    elseif ($action == "publish" || $action == "unpublish")
    {
      // not suitable for EasyEdit
      $add_javascript = "
    // called from control content, reload object view
    if (opener && opener.parent.frames['objFrame']) opener.parent.frames['objFrame'].location.reload();

    // called from objectlist or control, reload objectlist
    if (opener && opener.parent.frames['mainFrame'])
    {
      if (opener.parent.frames['mainFrame'].location.pathname.indexOf('explorer_objectlist.php') > -1) opener.parent.frames['mainFrame'].location='explorer_objectlist.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_orig)."';
    }
    // called from explorer, reload workplace (control + objectlist)
    else if (opener && opener.parent.frames['workplFrame'] && opener.parent.frames['workplFrame'].frames['mainFrame']) opener.parent.frames['workplFrame'].frames['mainFrame'].location.reload();

    self.close();
    ";
    }       
    else
    {
      $add_javascript = "
    self.close();
    ";  
    }
  
    $status = $maxcount." / ".$maxcount." ".getescapedtext ($hcms_lang['items'][$lang]);
  }
  // cancel process
  elseif ($force == "stop")
  {
    deletefile ($mgmt_config['abs_path_temp'], session_id().".coll.dat", 1);
  
    $add_javascript = "
    self.close();
    ";
  
    $status = ($maxcount - $count)." / ".$maxcount." ".getescapedtext ($hcms_lang['items'][$lang]);
  }
}
else
{
  $status = getescapedtext ($hcms_lang['you-do-not-have-permissions-to-execute-this-function'][$lang]);
}

// define progress bar
if ($maxcount > 0)
{
  $progress = (($maxcount - $count) / $maxcount) * 100;
  if ($progress == 0) $progress = 1;
}
?>

<div style="display:block; width:100%; text-align:center; vertical-align:middle;">
  <p><span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['status'][$lang]); ?></span> <span class="hcmsHeadlineTiny"><?php echo $status; ?></span></p>
  
  <?php if (!empty ($progress) && $progress >= 0) { ?>
  <div style="display:block; width:80%; height:16px; margin:10px auto; border:1px solid #000000;">
    <div class="hcmsRowHead1" style="width:<?php echo $progress; ?>%; height:100%;"></div>
  </div>
  <?php } ?>
  
  <form name="stop" action="" method="post">
    <input type="hidden" name="force" value="stop" />
    <input type="hidden" name="action" value="<?php echo $action_esc; ?>" />
    <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
    <input type="hidden" name="location" value="<?php echo $location_orig; ?>" />
    <input type="hidden" name="folder" value="<?php echo $folder; ?>" />
    <input type="hidden" name="page" value="<?php echo $page; ?>" />
    <input type="hidden" name="from_page" value="<?php echo $from_page; ?>" />
    <input type="hidden" name="count" value="<?php echo $count; ?>" />
    <input type="hidden" name="maxcount" value="<?php echo $maxcount; ?>" />
    <input type="hidden" name="tempfile" value="<?php echo $tempfile; ?>" />
    <input type="hidden" name="token" value="<?php echo $token; ?>" />
    <button class="hcmsButtonBlue" type="submit" /><?php echo getescapedtext ($hcms_lang['cancel'][$lang]); ?></button>
  </form>
</div>

<script type="text/javascript">
// load screen
if (document.getElementById('hcmsLoadScreen')) document.getElementById('hcmsLoadScreen').style.display = 'none';

// set window height
window.innerHeight = 120;

// reload status window
function refreshpopup ()
{
  <?php echo $add_javascript; ?>
}

setTimeout ('refreshpopup()', 1000);

// focus on start and finish
<?php if ($force == "start" || $force == "finish") echo "window.focus();\n"; ?>
</script>

</body>
</html>
