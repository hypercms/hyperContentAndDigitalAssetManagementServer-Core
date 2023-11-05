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
$multiobject = getrequest ("multiobject");
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$filetype = getrequest_esc ("filetype", "objectname");
$queueuser = getrequest ("queueuser", "objectname");
$queue_id = getrequest ("queue_id", "numeric");
$token = getrequest ("token");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);
if (empty ($cat)) $cat = "comp";

// publication management config
if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
{
  require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
}

// ------------------------------ permission section --------------------------------

// check permissions
if (($queueuser != "" && !checkrootpermission ('desktop')) || ($queueuser == "" && !checkrootpermission ('site') && !checkrootpermission ('user'))) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// initialize
$show = "";
$add_onload = "";
$multiobject_array = array();
$multiobject_count = Null;
$pagename = "";

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// file information
$file_info = getfileinfo ($site, $location.$page, $cat);
if (!empty ($file_info['name'])) $pagename = $file_info['name'];

// set local permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

// delete entries from queue
if ($action == "delete" && checktoken ($token, $user))
{
  if ($multiobject != "" || $queue_id != "")
  {
    if ($multiobject != "") $multiobject_array = link_db_getobject ($multiobject);
    elseif ($queue_id != "") $multiobject_array[0] = $queue_id;
 
    if (is_array ($multiobject_array) && sizeof ($multiobject_array) > 0)
    {
      $result = true;
      
      foreach ($multiobject_array as $queue_id)
      {
        if (valid_objectname ($queue_id) && $result == true)
        {
          $result = rdbms_deletequeueentry ($queue_id); 
        }
      }
      
      if ($result == true)
      {
        $show = "<span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['the-items-were-successfully-removed-from-the-queue'][$lang])."</span>";
        $add_onload = "parent.frames['mainFrame'].location.reload();";
        $page = "";
        $multiobject = "";
        $queue_id = "";
      }
      else
      {
        $show = "<span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['an-error-occured-removing-the-items-from-the-queue'][$lang])."</span>";
        $add_onload = "";
      }      
    }
  }
}

// security token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
<style type="text/css">
<?php echo showdynamicCSS ($hcms_themeinvertcolors, $hcms_hoverinvertcolors); ?>
</style>
<script type="text/javascript">

function warning_delete ()
{
  check = confirm (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-the-items-from-the-queue'][$lang]); ?>"));
  
  return check;
}

function submitTo (url, action, target, features, width, height)
{
  if (features == undefined)
  {
    features = 'scrollbars=no,resizable=no,width=400,height=120';
  }
  
  if (width == undefined)
  {
    width = 400;
  }
  
  if (height == undefined)
  {
    height = 120;
  }

  var form = parent.frames['mainFrame'].document.forms['contextmenu_queue'];
  
  form.attributes['action'].value = url;
  form.elements['action'].value = action;
  form.elements['queue_id'].value = '<?php echo $queue_id; ?>';
  form.elements['token'].value = '<?php echo $token_new; ?>';
  form.target = target;
  form.submit();
}

function jumpTo (target)
{
  site = document.forms['selectboxes'].elements['site'].options[document.selectboxes.site.selectedIndex].value;
  
  if (document.forms['selectboxes'] && document.forms['selectboxes'].elements['queueuser'])
  {
    queueuser = document.forms['selectboxes'].elements['queueuser'].options[document.selectboxes.queueuser.selectedIndex].value;
  }
  else queueuser = "";
  
  eval (target + ".location='queue_objectlist.php?site=" + encodeURIComponent(site) + "&queueuser=" + encodeURIComponent(queueuser) + "'");
}

// init
parent.hcms_closeSubMenu();
</script>
</head>

<body class="hcmsWorkplaceControl" onLoad="<?php echo $add_onload; ?>">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:10px;", "hcms_infobox_mouseover"); ?>

<?php echo showmessage ($show, 660, 65, $lang, "position:fixed; left:5px; top:5px;"); ?>

<div class="hcmsLocationBar">
  <?php if (!$is_mobile) { ?>
  <table class="hcmsTableNarrow">
    <tr>
      <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['publishing-queue-management'][$lang]); ?></td>
    </tr>
    <tr>
      <td>
        <b>
        <?php 
        if ($page != "" || $multiobject != "") 
        {
          // define object category name and check directory and component access rights of user
          if ($filetype == "Page")
          {
            echo getescapedtext ($hcms_lang['page'][$lang]);
            $media = "";
          }
          elseif ($filetype == "Component")
          {
            echo getescapedtext ($hcms_lang['component'][$lang]);
            $media = "";
          }
          elseif ($page == ".folder")
          {
            echo getescapedtext ($hcms_lang['folder'][$lang]);
            $media = "media";
          }
          else echo getescapedtext ($hcms_lang['file'][$lang]);
        }
        ?>&nbsp;</b>
        <span class="hcmsHeadlineTiny">
        <?php
        if ($page != "" || $multiobject != "") 
        {
          if ($multiobject != "")
          {
            $multiobject_count = sizeof (link_db_getobject ($multiobject));
          }
          
          if ($multiobject_count >= 1)
          {
            echo $multiobject_count." ".getescapedtext ($hcms_lang['items-selected'][$lang]);
          }
          elseif ($queue_id != "")
          {
            echo $pagename;
          }
        }
        ?>
        </span>
      </td>
    </tr>  
  </table>
  <?php } else { ?>
  <span style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo getescapedtext ($hcms_lang['publishing-queue'][$lang])." &gt; ".$pagename; ?></span>
  <?php } ?>

</div>

<!-- toolbar -->
<div class="hcmsToolbar hcmsWorkplaceControl" style="<?php echo gettoolbarstyle ($is_mobile); ?>">
<form name="selectboxes" action="">
  <div class="hcmsToolbarBlock">
  <?php
    // QUEUE EDIT BUTTON
    // object
    if ($multiobject_count <= 1 && $page != "" && 
      ((empty ($media) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1) || (empty ($media) && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1))
    )
    {
      if (!empty ($mgmt_config['object_newwindow']))
      {
        $openlink = "hcms_openWindow('frameset_content.php?ctrlreload=yes&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."', '', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");";
      }
      else
      {
        $openlink = "top.openMainView('frameset_content.php?ctrlreload=yes&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."');";
      }

      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"".$openlink."\">
        <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit-object'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit-object'][$lang])."\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['edit'][$lang])."</span>
      </div>";
    }
    // mail
    elseif ($multiobject_count <= 1 && $page != "" && !empty ($mgmt_config['db_connect_rdbms']))
    {
      if (!empty ($mgmt_config['object_newwindow']))
      {
        $openlink = "hcms_openWindow('user_sendlink.php?mailfile=".url_encode($page)."&queue_id=".url_encode($queue_id)."&token=".$token_new."', '', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes', 600, 800);";
      }
      else
      {
        $openlink = "parent.openPopup('user_sendlink.php?mailfile=".url_encode($page)."&queue_id=".url_encode($queue_id)."&token=".$token_new."');";
      }

      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"".$openlink."\">
        <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit-object'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit-object'][$lang])."\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['edit'][$lang])."</span>
      </div>";
    }  
    else
    {
      echo "
      <div class=\"hcmsButtonOff hcmsInvertColor\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_edit.png\" class=\"hcmsButtonSizeSquare hcmsFloatLeft\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['edit'][$lang])."</span>
      </div>";
    }
    ?>
    <?php
    // QUEUE DELETE BUTTON
    if (($queue_id != "" || $multiobject_count >= 1) && (($queueuser != "" && checkrootpermission ('desktop')) || ($queueuser == "" && (checkrootpermission ('site') || checkrootpermission ('user')))))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"if (warning_delete()==true) submitTo('control_queue_menu.php', 'delete', 'controlFrame');\">
        <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_delete.png\" alt=\"".getescapedtext ($hcms_lang['remove-items'][$lang])."\" title=\"".getescapedtext ($hcms_lang['remove-items'][$lang])."\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['delete'][$lang])."</span>
      </div>";
    }    
    else
    {
      echo "
      <div class=\"hcmsButtonOff hcmsInvertColor\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_delete.png\" class=\"hcmsButtonSizeSquare hcmsFloatLeft\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['delete'][$lang])."</span>
      </div>";
    }
    ?>
  </div>
  <div class="hcmsToolbarBlock">
    <div class="hcmsButton hcmsHoverColor hcmsInvertColor" onclick="parent.frames['mainFrame'].location.reload();">
      <?php echo "<img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_view_refresh.png\" alt=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" title=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" />"; ?>
      <span class="hcmsButtonLabel"><?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?></span>
    </div>
  </div>
  <div class="hcmsToolbarBlock">
    <div style="padding:3px; float:left;">
      <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_filter.png" class="hcmsIconList" style="vertical-align:middle;" />
      <select name="site" onchange="jumpTo('parent.frames[\'mainFrame\']')" style="width:<?php if ($is_mobile) echo "80px"; else echo "180px"; ?>;" title="<?php  echo getescapedtext ($hcms_lang['publication'][$lang]); ?> ">
        <option value=""><?php echo getescapedtext ($hcms_lang['all-publications'][$lang]); ?></option>
        <?php
          // select publication
          $inherit_db = inherit_db_read ();

          $site_array = array();

          if ($inherit_db != false && sizeof ($inherit_db) >= 1)
          {
            foreach ($inherit_db as $inherit_db_record)
            {
              if ($inherit_db_record['parent'] != "" && array_key_exists ($inherit_db_record['parent'], $siteaccess))
              {
                $site_array[] = trim ($inherit_db_record['parent']);
              }
            }
            
            if (is_array ($site_array))
            {
              natcasesort ($site_array);
              reset ($site_array);       
              
              foreach ($site_array as $site_item)
              {
                if ($site == $site_item) $selected = "selected=\"selected\"";
                else $selected = "";
                               
                echo "<option value=\"".$site_item."\" ".$selected.">".$site_item."</option>\n";            
              }
            }
          }
        ?>        
      </select>
    </div>
    <?php if (getsession ('hcms_temp_user') == "" && (checkrootpermission ('site') || checkrootpermission ('user'))) { ?>
  </div>

  <div class="hcmsToolbarBlock">
    <div style="padding:3px; float:left;">
      <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_filter.png" class="hcmsIconList" style="vertical-align:middle;" />
      <select name="queueuser" onchange="jumpTo('parent.frames[\'mainFrame\']');" style="width:<?php if ($is_mobile) echo "80px"; else echo "180px"; ?>;" title="<?php echo getescapedtext ($hcms_lang['user'][$lang]); ?>">
        <option value=""><?php echo getescapedtext ($hcms_lang['all-users'][$lang]); ?></option>
        <?php
        // select user
        $userdata = loadfile ($mgmt_config['abs_path_data']."user/", "user.xml.php");  
                          
        if ($userdata != false)
        {
          $user_array = getcontent ($userdata, "<login>");
        
          if ($user_array != false && is_array ($user_array))
          {
            natcasesort ($user_array);
            reset ($user_array);            
            
            foreach ($user_array as $user_item)
            {
              if ($queueuser == $user_item) $selected = "selected=\"selected\"";
              else $selected = "";
                             
              echo "<option value=\"".$user_item."\" ".$selected.">".$user_item."</option>\n";            
            }
          }
        }          
        ?>        
      </select>
    </div>
    <?php } ?>
    
  </div>
  <div class="hcmsToolbarBlock">
    <?php echo showhelpbutton ("usersguide", (checkrootpermission ('user') || checkglobalpermission ($site, 'user')), $lang, "", "hcmsHoverColor hcmsInvertColor"); ?>    
  </div>
</form>
</div>

</body>
</html>
