<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");


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

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (($queueuser != "" && !checkrootpermission ('desktop')) || ($queueuser == "" && !checkrootpermission ('site'))) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";
$multiobject_count = Null;

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// file information
$file_info = getfileinfo ($site, $location.$page, $cat);
$pagename = $file_info['name'];

// set local permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

// delete entries from queue
if ($action == "delete" && checktoken ($token, $user) && 
     $queue_id != "" && 
     (($queueuser != "" && checkrootpermission ('desktop')) || 
     (checkrootpermission ('site') || checkrootpermission ('user')))
   )
{
  if ($multiobject != "" || $queue_id != "")
  {
    if ($multiobject != "") $multiobject_array = link_db_getobject ($multiobject);
    elseif ($queue_id != "") $multiobject_array[0] = $queue_id;
 
    if (is_array ($multiobject_array))
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
        $show = "<span class=hcmsHeadline>".$hcms_lang['the-items-were-successfully-removed-from-the-queue'][$lang]."</span>";
        $add_onload = "parent.frames['mainFrame'].location.reload();";
        $page = "";
        $multiobject = "";
        $queue_id = "";
      }
      else
      {
        $show = "<span class=hcmsHeadline>".$hcms_lang['an-error-occured-removing-the-items-from-the-queue'][$lang]."</span>";
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
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
<!--
function warning_delete()
{
  check = confirm (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-the-items-from-the-queue'][$lang]); ?>"));
  
  return check;
}

function submitTo(url, action, target, features, width, height)
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
  
  if (eval (document.forms['selectboxes'].elements['queueuser']))
  {
    queueuser = document.forms['selectboxes'].elements['queueuser'].options[document.selectboxes.queueuser.selectedIndex].value;
  }
  else queueuser = "";
  
  eval (target + ".location='queue_objectlist.php?site=" + site + "&queueuser=" + queueuser + "'");
}
//-->
</script>
</head>

<body class="hcmsWorkplaceControlWallpaper" onLoad="<?php echo $add_onload; ?>">

<?php
echo showmessage ($show, 650, 60, $lang, "position:fixed; left:15px; top:15px; ");
?>
<div class="hcmsLocationBar">
  <table border=0 cellspacing=0 cellpadding=0>
    <tr>
      <td class="hcmsHeadline"><?php echo $hcms_lang['publishing-queue-management'][$lang]; ?></td>
    </tr>
    <tr>
      <td>
        <span class="hcmsHeadline">
        <?php 
        if ($page != "") 
        {
          // define object category name and check directory and component access rights of user
          if ($filetype == "Page")
          {
            echo $hcms_lang['page'][$lang];
            $media = "";
          }
          elseif ($filetype == "Component")
          {
            echo $hcms_lang['component'][$lang];
            $media = "";
          }
          elseif ($page == ".folder")
          {
            echo $hcms_lang['folder'][$lang];
            $media = "media";
          }
          else echo $hcms_lang['file'][$lang];
          
          echo ":";
        }
        ?>&nbsp;</span>
        <span class="hcmsHeadlineTiny">
        <?php 
        if ($page != "") 
        {
          if ($multiobject)
          {
            $multiobject_count = sizeof (link_db_getobject ($multiobject));
          }
          
          if ($multiobject_count >= 1)
          {
            echo $multiobject_count." ".$hcms_lang['items-selected'][$lang];
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
</div>

<!-- toolbar -->
<div class="hcmsToolbar">
  <form name="selectboxes" action="">
  <div class="hcmsToolbarBlock">
    <?php
    // QUEUE DELETE BUTTON
    if ($queue_id != "" && (($queueuser != "" && checkrootpermission ('desktop')) || ($queueuser == "" && (checkrootpermission ('site') || checkrootpermission ('user')))))
    {
      echo 
      "<img ".
        "class=\"hcmsButton hcmsButtonSizeSquare\" ".
        "onClick=\"if (warning_delete()==true) ".
        "submitTo('control_queue_menu.php', 'delete', 'controlFrame'); \" ".
        "name=\"media_delete\" src=\"".getthemelocation()."img/button_file_delete.gif\" alt=\"".$hcms_lang['remove-items'][$lang]."\" title=\"".$hcms_lang['remove-items'][$lang]."\" />\n";
    }    
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_file_delete.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>
    <?php
    // QUEUE EDIT BUTTON
    if ($multiobject_count <= 1 && $page != "" && 
      ((empty ($media) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1) || (empty ($media) && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1))
    )
    {
      echo "<img ".
             "class=\"hcmsButton hcmsButtonSizeSquare\" ".
             "onClick=\"hcms_openWindow('frameset_content.php?ctrlreload=yes&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."','','status=yes,scrollbars=no,resizable=yes', '800', '600');\" ".
             "name=\"media_edit\" src=\"".getthemelocation()."img/button_file_edit.gif\" alt=\"".$hcms_lang['edit-object'][$lang]."\" title=\"".$hcms_lang['edit-object'][$lang]."\" />\n";
    }    
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_file_edit.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>
    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"parent.frames['mainFrame'].location.reload();\" name=\"pic_obj_refresh\" src=\"".getthemelocation()."img/button_view_refresh.gif\" alt=\"".$hcms_lang['refresh'][$lang]."\" title=\"".$hcms_lang['refresh'][$lang]."\" />\n";
    ?>
    
  </div>
  <div class="hcmsToolbarBlock">
    <div style="padding:3px; float:left;"> 
      <?php echo $hcms_lang['publication'][$lang]; ?>:
      <select name="site" onChange="jumpTo('parent.frames[\'mainFrame\']')">
        <option value=""><?php echo $hcms_lang['all-publications'][$lang]; ?></option>
        <?php
          // select publication
          $inherit_db = inherit_db_read ($user);

          if ($inherit_db != false && sizeof ($inherit_db) >= 1)
          {
            foreach ($inherit_db as $inherit_db_record)
            {
              if ($inherit_db_record['parent'] != "" && in_array ($inherit_db_record['parent'], $siteaccess))
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
      <?php echo $hcms_lang['user'][$lang]; ?>:
      <select name="queueuser" onChange="jumpTo('parent.frames[\'mainFrame\']')">
        <option value=""><?php echo $hcms_lang['all-users'][$lang]; ?></option>
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
    <?php
    if (!$is_mobile && file_exists ("help/adminguide_".$hcms_lang_shortcut[$lang].".pdf") && (checkrootpermission ('user') || checkglobalpermission ($site, 'user')))
    {echo "<a href=# onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('pic_obj_help','','".getthemelocation()."img/button_help_over.gif',1)\" onClick=\"hcms_openWindow('help/adminguide_".$hcms_lang_shortcut[$lang].".pdf','help','scrollbars=no,resizable=yes','800','600');\"><img name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".$hcms_lang['help'][$lang]."\" title=\"".$hcms_lang['help'][$lang]."\" /></a>\n";}
    elseif (!$is_mobile && file_exists ("help/adminguide_en.pdf") && (checkrootpermission ('user') || checkglobalpermission ($site, 'user')))
    {echo "<a href=# onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('pic_obj_help','','".getthemelocation()."img/button_help_over.gif',1)\" onClick=\"hcms_openWindow('help/adminguide_en.pdf','help','scrollbars=no,resizable=yes','800','600');\"><img name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".$hcms_lang['help'][$lang]."\" title=\"".$hcms_lang['help'][$lang]."\" /></a>\n";}
    ?>      
  </div>
  </form>
</div>

</body>
</html>
