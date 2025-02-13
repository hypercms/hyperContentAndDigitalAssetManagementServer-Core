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
$multiobject_array = array();
$multiobject_count = Null;

// delete entries
if ($action == "delete" && checktoken ($token, $user) && $message_id != "" && $messageuser != "" && checkrootpermission ('desktop'))
{
  if ($multiobject != "" || $message_id != "")
  {
    if ($multiobject != "") $multiobject_array = link_db_getobject ($multiobject);
    elseif ($message_id != "") $multiobject_array[0] = $message_id;
 
    if (is_array ($multiobject_array) && sizeof ($multiobject_array) > 0)
    {
      $result = true;
      
      foreach ($multiobject_array as $message_id)
      {
        if (valid_objectname ($message_id) && $result == true)
        {
          if (is_file ($mgmt_config['abs_path_data']."message/".$message_id.".php"))
          {
            $result = deletefile ($mgmt_config['abs_path_data']."message/", $message_id.".php", 0);
          }
        }
      }
      
      if ($result == true)
      {
        $show = "";
        $add_onload = "parent.frames['mainFrame'].location.reload();";
        $multiobject = "";
        $message_id = "";
      }
      else
      {
        $show = "<span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['the-data-could-not-be-saved'][$lang])."</span>";
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

function startSearch ()
{
  if (document.forms['searchform'])
  {
    var form = document.forms['searchform'];

    // load screen
    if (parent.frames['mainFrame'].document.getElementById('hcmsLoadScreen')) parent.frames['mainFrame'].document.getElementById('hcmsLoadScreen').style.display='inline';
    
    // submit form
    form.submit();
  }
}

function warning_delete ()
{
  check = confirm (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-the-selected-entries'][$lang]); ?>"));
  
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

  var form = parent.frames['mainFrame'].document.forms['contextmenu_message'];
  
  form.attributes['action'].value = url;
  form.elements['action'].value = action;
  form.elements['message_id'].value = '<?php echo $message_id; ?>';
  form.elements['token'].value = '<?php echo $token_new; ?>';
  form.target = target;
  form.submit();
}

// init
parent.hcms_closeSubMenu();
</script>
</head>

<body class="hcmsWorkplaceControl" onLoad="<?php echo $add_onload; ?>">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:15px;", "hcms_infobox_mouseover"); ?>

<?php echo showmessage ($show, 660, 65, $lang, "position:fixed; left:5px; top:5px; "); ?>

<div class="hcmsLocationBar">
  <?php if (!$is_mobile) { ?>
  <table class="hcmsTableNarrow">
    <tr>
      <td class="hcmsHeadline"> <?php echo getescapedtext ($hcms_lang['messages'][$lang]); ?> </td>
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
  <?php } else { ?>
  <span style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo getescapedtext ($hcms_lang['messages'][$lang]); ?></span>
  <?php } ?>

</div>

<!-- toolbar -->
<div class="hcmsToolbar hcmsWorkplaceControl" style="<?php echo gettoolbarstyle ($is_mobile); ?>">
  <div class="hcmsToolbarBlock">
    <?php
    // EDIT BUTTON
    // mail
    if ($multiobject_count <= 1 && $message_id != "" && !empty ($mgmt_config['db_connect_rdbms']))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" ";
      if (!empty ($mgmt_config['message_newwindow'])) echo "onclick=\"hcms_openWindow('user_sendlink.php?mailfile=".url_encode($message_id)."&token=".$token_new."', '', 'location=no,menubar=no,toolbar=no,titlebar=no,status=yes,scrollbars=no,resizable=yes', 600, 900);\" ";
      else echo "onclick=\"parent.openPopup('user_sendlink.php?mailfile=".url_encode($message_id)."&token=".$token_new."');\" ";
      echo ">
        <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" id=\"media_edit\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit-object'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit-object'][$lang])."\" />
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
    // DELETE BUTTON
    if ($message_id != "" && $messageuser != "" && checkrootpermission ('desktop'))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"if (warning_delete()==true) submitTo('control_message_menu.php', 'delete', 'controlFrame');\">
        <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" id=\"media_delete\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_delete.png\" alt=\"".getescapedtext ($hcms_lang['remove-items'][$lang])."\" title=\"".getescapedtext ($hcms_lang['remove-items'][$lang])."\" />
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
    <?php
    echo "
    <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"parent.frames['mainFrame'].location.reload();\">
      <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" id=\"pic_obj_refresh\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_view_refresh.png\" alt=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" title=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" />
      <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['refresh'][$lang])."</span>
    </div>";
    ?>
  </div>
  
  <div class="hcmsToolbarBlock">
    <div style="padding:3px; float:left;">
      <form name="searchform" method="post" action="message_objectlist.php" target="mainFrame" style="margin:0; padding:0; border:0;">
        <input type="text" name="search" onkeydown="if (hcms_enterKeyPressed(event)) startSearch();" style="float:left; width:<?php if ($is_mobile) echo "130px"; else echo "200px"; ?>;" maxlength="400" placeholder="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" value="" />
        <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_search_dark.png" onclick="startSearch();" style="float:left; cursor:pointer; width:22px; height:22px; margin:5px 0px 3px -26px; " title="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>" />
      </form>
    </div>
  </div>

  <div class="hcmsToolbarBlock">
    <?php echo showhelpbutton ("usersguide", checkrootpermission ('user'), $lang, "", "hcmsHoverColor hcmsInvertColor"); ?>   
  </div>
</div>

</body>
</html>
