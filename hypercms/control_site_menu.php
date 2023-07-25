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
// language file
require_once ("language/".getlanguagefile ($lang)); 


// input parameters
$action = getrequest_esc ("action");
$site_name = getrequest_esc ("site_name"); // site can include get parameters
$token = getrequest ("token"); 

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('site')) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// initialize
$show = "";
$add_onload = "";

// include scripts
if ($action == "site_create" && checkrootpermission ('site') && checkrootpermission ('sitecreate') && checktoken ($token, $user))
{
  $result = createpublication ($site_name, $user);

  $add_onload =  $result['add_onload'];
  $show = $result['message'];  
}
elseif ($action == "site_delete" && checkrootpermission ('site') && checkrootpermission ('sitedelete') && checktoken ($token, $user))
{
  $result = deletepublication ($site_name, $user);
  
  $add_onload =  $result['add_onload'];
  $show = $result['message'];  
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
<?php
// inverted main colors
if (!empty ($hcms_themeinvertcolors))
{
  if (!empty ($hcms_hoverinvertcolors)) $invertonhover = false;
  else $invertonhover = true;

  echo invertcolorCSS ($hcms_themeinvertcolors, ".hcmsInvertColor", true, $invertonhover);
  echo invertcolorCSS ($hcms_themeinvertcolors, ".hcmsInvertPrimaryColor", true, false);
}
// inverted hover colors
elseif (!empty ($hcms_hoverinvertcolors))
{
  echo invertcolorCSS ($hcms_hoverinvertcolors, ".hcmsInvertColor", false, true);
  echo invertcolorCSS ($hcms_hoverinvertcolors, ".hcmsInvertHoverColor", true, false);
}
?>
</style>
<script type="text/javascript">

function selectpublication (selObj)
{
  if (selObj.options[selObj.selectedIndex].value != "")
  {
    <?php if (checkrootpermission ('siteedit')) { ?>
    parent.frames['mainFrame'].location.href = 'frameset_site_edit.php?site_name=' + selObj.options[selObj.selectedIndex].value;
    <?php } else { ?>
    parent.frames['mainFrame'].location.href = 'site_edit_form.php?site_name=' + selObj.options[selObj.selectedIndex].value;
    <?php } ?>
  }
  else
  {
    parent.frames['mainFrame'].location.href = 'empty.php';
  }
}

function createpublication ()
{
  hcms_showHideLayers('createsiteLayer','','show');
  if (typeof parent.hcms_openSubMenu == "function") parent.hcms_openSubMenu(78);
}

function closepublication ()
{
  hcms_showHideLayers('createsiteLayer','','hide');
  if (typeof parent.hcms_closeSubMenu == "function") parent.hcms_closeSubMenu();
}

function deletepublication ()
{
  var form = document.forms['site_delete'];
  
  if (form.elements['site_name'].value == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-select-an-option'][$lang]); ?>"));
    return false;
  }
  else
  {
    check = confirm (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['warning'][$lang]); ?>\n <?php echo getescapedtext ($hcms_lang['the-selected-item-will-be-removed'][$lang]); ?>\n <?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-this-item'][$lang]); ?>"));
    if (check == true) form.submit();
    return check;
  }
}

function checkForm_chars (text, exclude_chars)
{
  exclude_chars = exclude_chars.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
  
	var expr = new RegExp ("[^a-zA-Z0-9" + exclude_chars + "]", "g");
	var separator = ', ';
	var found = text.match(expr); 
	
  if (found)
  {
		var addText = '';
    
		for (var i = 0; i < found.length; i++)
    {
			addText += found[i]+separator;
		}
    
		addText = addText.substr(0, addText.length-separator.length);
		alert ("<?php echo getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters'][$lang]); ?>\n " + addText);
		return false;
	}
  else
  {
		return true;
	}
}

function checkForm ()
{
  var form = document.forms['site_create'];
  
  if (form.elements['site_name'].value.trim() == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['a-name-is-required'][$lang]); ?>"));
    form.elements['site_name'].focus();
    return false;
  }
  
  if (!checkForm_chars (form.elements['site_name'].value, "-_"))
  {
    form.elements['site_name'].focus();
    return false;
  }
  
  form.submit();
  return true;
}

// init
parent.hcms_closeSubMenu();
</script>
</head>

<body class="hcmsWorkplaceControl" onLoad="<?php echo $add_onload; ?>">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:20px;", "hcms_infobox_mouseover"); ?>

<?php echo showmessage ($show, 660, 70, $lang, "position:fixed; left:10px; top:10px;"); ?>

<div class="hcmsLocationBar">
  <?php if (!$is_mobile) { ?>
  <table class="hcmsTableNarrow">
    <tr>
      <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['publication-management'][$lang]); ?></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>  
  </table>
  <?php } else { ?>
  <span style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo getescapedtext ($hcms_lang['publication-management'][$lang]); ?></span>
  <?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar" style="<?php if (!$is_mobile) echo "white-space:nowrap; min-width:580px;"; else echo "max-height:100px;"; ?>">
  <div class="hcmsToolbarBlock" style="padding:2px;">
    <form name="site_delete" action="" method="post">
      <input type="hidden" name="action" value="site_delete" />
      <input type="hidden" name="token" value="<?php echo $token_new; ?>" />

      <span class="hcmsInvertPrimaryColor">
        <span class=""><?php echo getescapedtext ($hcms_lang['publication'][$lang]); ?></span>
      </span>
      <select name="site_name" onChange="selectpublication(this);" style="width:<?php if ($is_mobile) echo "120px"; else echo "180px"; ?>;" title="<?php echo getescapedtext ($hcms_lang['publication-name'][$lang]); ?>">
        <option value=""><?php echo getescapedtext ($hcms_lang['select'][$lang]); ?></option>
        <?php
        $inherit_db = inherit_db_read ();
        $item_options = array();

        if (!empty ($inherit_db) && sizeof ($inherit_db) > 0)
        {
          foreach ($inherit_db as $inherit_db_record)
          {
            if (!empty ($inherit_db_record['parent']) && !empty ($siteaccess) && is_array ($siteaccess) && array_key_exists ($inherit_db_record['parent'], $siteaccess))
            {
              $inherit_db_record['parent'] = trim ($inherit_db_record['parent']);
              $item_options[] = $inherit_db_record['parent'];
            }              
          }
        }
        
        if (is_array ($item_options) && sizeof ($item_options) > 0)
        {
          natcasesort ($item_options);
          reset ($item_options);
          
          foreach ($item_options as $value)
          {
            echo "
            <option value=\"".url_encode($value)."\" ".($site_name == $value ? "selected=\"selected\"" : "")." title=\"".$value."\">".$siteaccess[$value]."</option>";
          }
        }
        ?>
      </select>
    </form>
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if (checkrootpermission ('site')  && checkrootpermission ('sitecreate'))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img class=\"hcmsButtonSizeSquare\" onClick=\"createpublication();\" id=\"media_new\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_site_new.png\" alt=\"".getescapedtext ($hcms_lang['create'][$lang])."\" title=\"".getescapedtext ($hcms_lang['create'][$lang])."\" />
      </div>";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_site_new.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    ?>
    <?php
    if (checkrootpermission ('site')  && checkrootpermission ('sitedelete'))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor hcmsButtonSizeSquare\">
        <img class=\"hcmsButtonSizeSquare\" onClick=\"deletepublication();\" id=\"media_delete\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_site_delete.png\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" />
      </div>";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_site_delete.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    ?>
  </div>
  <div class="hcmsToolbarBlock">
    <?php echo showhelpbutton ("adminguide", checkrootpermission ('site'), $lang, "", "hcmsHoverColor hcmsInvertColor"); ?>
  </div>
</div>

<!-- create publication -->
<div id="createsiteLayer" class="hcmsMessage" style="position:absolute; left:5px; top:5px; width:<?php if ($is_mobile) echo "95%"; else echo "650px"; ?>; visibility:hidden;">
  <form name="site_create" action="" method="post" onsubmit="return checkForm();">
    <input type="hidden" name="action" value="site_create" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
    
    <table class="hcmsTableNarrow" style="width:100%; min-height:40px;">
      <tr>
        <td>
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['create'][$lang]); ?></span><br />
          <span style="white-space:nowrap;">
            <input type="text" name="site_name" maxlength="100" style="width:<?php if ($is_mobile) echo "180px"; else echo "220px"; ?>;" placeholder="<?php echo getescapedtext ($hcms_lang['publication-name'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['publication-name'][$lang]); ?>" />
            <img name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
          </span>
        </td>
        <td style="width:38px; text-align:right; vertical-align:top;">
          <img name="hcms_mediaClose1" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose1','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="closepublication();" />
        </td>        
      </tr>
    </table>
  </form>
</div>

</body>
</html>