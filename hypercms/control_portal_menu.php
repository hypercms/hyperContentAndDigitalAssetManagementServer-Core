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
$action = getrequest_esc ("action");
$site = getrequest_esc ("site", "publicationname");
$template = getrequest ("template", "objectname");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
{
  require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
}

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'template') || !checkglobalpermission ($site, 'tpl') || !valid_publicationname ($site) || empty ($mgmt_config[$site]['portalaccesslink'])) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";

// check if template name is an attribute of a sent string
if (strpos ($template, ".php?") > 0)
{
  // extract template name
  $template = getattribute ($template, "template");
}

// execute actions
if (checktoken ($token, $user) && valid_publicationname ($site))
{
  // create template
  if ($action == "tpl_create" && checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tplcreate')) 
  {
    $result = createportal ($site, $template);
    
    $add_onload =  $result['add_onload'];
    $show = $result['message'];  
  }
  // delete template
  elseif ($action == "tpl_delete" && checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpldelete')) 
  {
    $result = deleteportal ($site, $template);
    
    $add_onload =  $result['add_onload'];
    $show = $result['message'];  
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

function selectportal (selObj)
{
  if (selObj.options[selObj.selectedIndex].value != "")
  {
    parent.frames['mainFrame'].location.href = 'portal_edit.php?site=<?php echo url_encode($site); ?>&save=no&template=' + selObj.options[selObj.selectedIndex].value;
  }
  else
  {
    parent.frames['mainFrame'].location.href = 'empty.php';
  }
}

function createportal ()
{
  hcms_showHideLayers('createtplLayer','','show', 'hcms_messageLayer','','hide');
  if (typeof parent.hcms_openSubMenu == "function") parent.hcms_openSubMenu(78);
}

function closeportal ()
{
  hcms_showHideLayers('createtplLayer','','hide');
  if (typeof parent.hcms_closeSubMenu == "function") parent.hcms_closeSubMenu();
}

function deleteportal ()
{
  var form = document.forms['tpl_delete'];

  if (form.elements['template'].value == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-select-an-option'][$lang]); ?>"));
    return false;
  }
  else
  {
    check = confirm(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['warning'][$lang]); ?>\n <?php echo getescapedtext ($hcms_lang['the-selected-item-will-be-removed'][$lang]); ?>\n <?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-the-template'][$lang]); ?>"));
    if (check == true) form.submit();
    return check;
  }
}

function checkForm_chars(text, exclude_chars)
{
  exclude_chars = exclude_chars.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
  
	var expr = new RegExp ("[^a-zA-Z0-9" + exclude_chars + "]", "g");
	var separator = ', ';
	var found = text.match(expr); 
	
  if (found)
  {
		var addText = '';
    
		for(var i = 0; i < found.length; i++)
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

function checkForm_tpl_create()
{
  var form = document.forms['tpl_create'];
   
  if (form.elements['template'].value.trim() == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['a-name-is-required'][$lang]); ?>"));
    form.elements['template'].focus();
    return false;
  }
  
  if (!checkForm_chars (form.elements['template'].value, "-_"))
  {
    form.elements['template'].focus();
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

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:10px;", "hcms_infobox_mouseover"); ?>

<?php echo showmessage ($show, 670, 65, $lang, "position:fixed; left:5px; top:5px; "); ?>

<div class="hcmsLocationBar">
  <?php if (!$is_mobile) { ?>
  <table class="hcmsTableNarrow">
    <tr>
      <td class="hcmsHeadline"><?php echo getescapedtext ($site." &gt; ".$hcms_lang['portal-templates'][$lang]); ?></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>  
  </table>
  <?php } else { ?>
  <span style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo getescapedtext ($site." &gt; ".$hcms_lang['portal-templates'][$lang]); ?></span>
  <?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar hcmsWorkplaceControl" style="<?php echo gettoolbarstyle ($is_mobile); ?>">
  <div class="hcmsToolbarBlock" style="padding:2px;">
    <form name="tpl_delete" action="" method="post">
      <input type="hidden" name="action" value="tpl_delete" />
      <input type="hidden" name="site" value="<?php echo $site; ?>" />
      <input type="hidden" name="token" value="<?php echo $token_new; ?>" />

      <span class="hcmsInvertPrimaryColor">
        <span class=""><?php echo $hcms_lang['portal-template'][$lang]; ?></span>
      </span>
      <select name="template" onchange="selectportal(this);" style="width:<?php if ($is_mobile) echo "130px"; else echo "200px"; ?>;" title="<?php echo getescapedtext ($hcms_lang['template'][$lang]); ?>">
        <option value=""><?php echo getescapedtext ($hcms_lang['select'][$lang]); ?></option>
      <?php
      $template_option_edit = array();  
      $template_files = getlocaltemplates ($site, "portal");

      if (is_array ($template_files) && sizeof ($template_files) > 0)
      {
        foreach ($template_files as $value)
        {
          $tpl_name = str_replace (".portal.tpl", "", $value);

          echo "
        <option value=\"".url_encode($value)."\" ".($template == $tpl_name ? "selected=\"selected\"" : "").">".$tpl_name."</option>";
        }
      }
      ?>
      </select>
    </form>
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if (checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tplcreate'))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"createportal();\">
        <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_tpl_new.png\" alt=\"".getescapedtext ($hcms_lang['create'][$lang])."\" title=\"".getescapedtext ($hcms_lang['create'][$lang])."\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['create'][$lang])."</span>
      </div>";
    }
    else
    {
      echo "
      <div class=\"hcmsButtonOff hcmsInvertColor\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_tpl_new.png\" class=\"hcmsButtonSizeSquare hcmsFloatLeft\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['create'][$lang])."</span>
      </div>";
      }
    ?>
    <?php
    if (checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpldelete'))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"deleteportal();\">
        <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_tpl_delete.png\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['delete'][$lang])."</span>
      </div>";
    }
    else
    {
      echo "
      <div class=\"hcmsButtonOff hcmsInvertColor\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_tpl_delete.png\" class=\"hcmsButtonSizeSquare hcmsFloatLeft\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['delete'][$lang])."</span>
      </div>";
    }
    ?>
  </div>
  <div class="hcmsToolbarBlock">
    <?php echo showhelpbutton ("templateguide", checkglobalpermission ($site, 'tpl'), $lang, "", "hcmsHoverColor hcmsInvertColor"); ?> 
  </div>
</div>

<!-- create portal -->
<div id="createtplLayer" class="hcmsMessage" style="position:absolute; left:5px; top:5px; width:<?php if ($is_mobile) echo "95%"; else echo "650px"; ?>; visibility:hidden;">
  <form name="tpl_create" action="" method="post" onsubmit="return checkForm_tpl_create();">
    <input type="hidden" name="action" value="tpl_create" />
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
    
    <table class="hcmsTableNarrow" style="width:100%; min-height:40px;">
      <tr>
        <td>
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['create'][$lang]); ?></span> <span class="hcmsTextSmall">(<?php echo getescapedtext ($hcms_lang['name-without-ext'][$lang]); ?>)</span><br />
          <span style="white-space:nowrap;">
            <input type="text" name="template" maxlength="100" style="width:<?php if ($is_mobile) echo "180px"; else echo "220px"; ?>;" placeholder="<?php echo getescapedtext ($hcms_lang['template'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['template'][$lang]); ?>"/>
            <img name="Button1" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_tpl_create();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
          </span>
        </td>
        <td style="width:38px; text-align:right; vertical-align:top;">
          <img name="hcms_mediaClose1" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose1','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="closeportal();" />
        </td>        
      </tr>  
    </table>
  </form>
</div>

</body>
</html>
