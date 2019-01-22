<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
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


// input parameters
$action = getrequest ("action");
$multiobject = getrequest ("multiobject");
$messageuser = getrequest ("messageuser", "objectname");
$message_id = getrequest ("message_id");
$token = getrequest ("token");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('desktop')) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";
$multiobject_count = Null;

// delete entries
if ($action == "delete" && checktoken ($token, $user) && $message_id != "" && $messageuser != "" && checkrootpermission ('desktop'))
{
  if ($multiobject != "" || $message_id != "")
  {
    if ($multiobject != "") $multiobject_array = link_db_getobject ($multiobject);
    elseif ($message_id != "") $multiobject_array[0] = $message_id;
 
    if (is_array ($multiobject_array))
    {
      $result = true;
      
      foreach ($multiobject_array as $message_id)
      {
        if (valid_objectname ($message_id) && $result == true)
        {
          $result = deletefile ($mgmt_config['abs_path_data']."message/", $message_id.".php", 0);
        }
      }
      
      if ($result == true)
      {
        $show = "<span class=hcmsHeadline>".getescapedtext ($hcms_lang['the-data-was-saved-successfully'][$lang])."</span>";
        $add_onload = "parent.frames['mainFrame'].location.reload();";
        $multiobject = "";
        $message_id = "";
      }
      else
      {
        $show = "<span class=hcmsHeadline>".getescapedtext ($hcms_lang['the-data-could-not-be-saved'][$lang])."</span>";
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
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
function warning_delete()
{
  check = confirm (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-the-selected-entries'][$lang]); ?>"));
  
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

  var form = parent.frames['mainFrame'].document.forms['contextmenu_message'];
  
  form.attributes['action'].value = url;
  form.elements['action'].value = action;
  form.elements['message_id'].value = '<?php echo $message_id; ?>';
  form.elements['token'].value = '<?php echo $token_new; ?>';
  form.target = target;
  form.submit();
}
</script>
</head>

<body class="hcmsWorkplaceControlWallpaper" onLoad="<?php echo $add_onload; ?>">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:20px;"); ?>

<?php echo showmessage ($show, 650, 60, $lang, "position:fixed; left:15px; top:15px; "); ?>

<div class="hcmsLocationBar">
  <table class="hcmsTableNarrow">
    <tr>
      <td><b><?php echo getescapedtext ($hcms_lang['messages'][$lang]); ?></b></td>
    </tr>
    <tr>
      <td>
        <b>
        <?php
        if ($message_id != "" || $multiobject != "") 
        {
          echo getescapedtext ($hcms_lang['object'][$lang]);
        }
        ?>&nbsp;</b>
        <span class="hcmsHeadlineTiny">
        <?php
        if ($message_id != "" || $multiobject != "") 
        {
          if ($multiobject != "")
          {
            $multiobject_count = sizeof (link_db_getobject ($multiobject));
          }
          elseif ($message_id != "")
          {
            $multiobject_count = 1;
          }

          if ($multiobject_count >= 1)
          {
            echo $multiobject_count." ".getescapedtext ($hcms_lang['items-selected'][$lang]);
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
  <div class="hcmsToolbarBlock">
    <?php
    // DELETE BUTTON
    if ($message_id != "" && $messageuser != "" && checkrootpermission ('desktop'))
    {
      echo 
      "<img ".
        "class=\"hcmsButton hcmsButtonSizeSquare\" ".
        "onClick=\"if (warning_delete()==true) ".
        "submitTo('control_message_menu.php', 'delete', 'controlFrame'); \" ".
        "name=\"media_delete\" src=\"".getthemelocation()."img/button_delete.png\" alt=\"".getescapedtext ($hcms_lang['remove-items'][$lang])."\" title=\"".getescapedtext ($hcms_lang['remove-items'][$lang])."\" />\n";
    }    
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_delete.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>
    <?php
    // EDIT BUTTON
    // mail
    if ($multiobject_count <= 1 && $message_id != "" && !empty ($mgmt_config['db_connect_rdbms']))
    {
      echo "<img ".
             "class=\"hcmsButton hcmsButtonSizeSquare\" ".
             "onClick=\"hcms_openWindow('user_sendlink.php?mailfile=".url_encode($message_id)."&token=".$token_new."', '', 'status=yes,scrollbars=no,resizable=yes', 600, 800);\" ".
             "name=\"media_edit\" src=\"".getthemelocation()."img/button_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit-object'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit-object'][$lang])."\" />\n";
    }  
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_edit.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>
  </div>
  
  <div class="hcmsToolbarBlock">
    <?php
    echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"parent.frames['mainFrame'].location.reload();\" name=\"pic_obj_refresh\" src=\"".getthemelocation()."img/button_view_refresh.png\" alt=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" title=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" />\n";
    ?>
  </div>
  
  <div class="hcmsToolbarBlock">
    <div style="padding:3px; float:left;">
      <form name="searchform" method="post" action="message_objectlist.php" target="mainFrame" style="margin:0; padding:0; border:0;">
        <input type="hidden" name="maxhits" value="300" />
        <input type="text" name="search" style="float:left; width:200px; height:20px; padding:2px;" maxlength="200" placeholder="<?php echo getescapedtext ($hcms_lang['search-expression'][$lang]); ?>" value="" />
        <img src="<?php echo getthemelocation(); ?>img/button_search.png" onclick="document.searchform.submit();" style="float:left; cursor:pointer; margin:2px 0px 2px -24px; width:22px; height:22px;" title="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" />
      </form>
    </div>
  </div>

  <div class="hcmsToolbarBlock">  
    <?php
    if (file_exists ($mgmt_config['abs_path_cms']."help/usersguide_".$hcms_lang_shortcut[$lang].".pdf") && (checkrootpermission ('user') || checkglobalpermission ($site, 'user')))
    {echo "<img  onClick=\"hcms_openWindow('help/usersguide_".$hcms_lang_shortcut[$lang].".pdf', 'help', 'scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");\" name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.png\" class=\"hcmsButton hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" />\n";}
    elseif (file_exists ($mgmt_config['abs_path_cms']."help/usersguide_en.pdf") && (checkrootpermission ('user') || checkglobalpermission ($site, 'user')))
    {echo "<img  onClick=\"hcms_openWindow('help/usersguide_en.pdf', 'help', 'scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");\" name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.png\" class=\"hcmsButton hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" />\n";}
    ?>      
  </div>
</div>

</body>
</html>
