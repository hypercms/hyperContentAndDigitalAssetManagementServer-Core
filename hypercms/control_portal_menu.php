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
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

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
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script type="text/javascript" src="javascript/main.min.js"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
<?php
// invert colors
if (!empty ($hcms_themeinvertcolors))
{
  echo "<style>";
  echo invertcolorCSS ($hcms_themeinvertcolors);
  echo "</style>";
}
?>
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
</script>
</head>

<body class="hcmsWorkplaceControlWallpaper" onLoad="<?php echo $add_onload; ?>">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:20px;"); ?>

<?php echo showmessage ($show, 650, 60, $lang, "position:fixed; left:15px; top:15px; "); ?>

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
  <span class="hcmsHeadlineTiny" style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo getescapedtext ($site." &gt; ".$hcms_lang['portal-templates'][$lang]); ?></span>
  <?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar" style="width:<?php if ($is_mobile) echo "380px;"; else echo "620px;"; ?>">
  <div class="hcmsToolbarBlock" style="padding:2px;">
    <form name="tpl_delete" action="" method="post">
      <input type="hidden" name="action" value="tpl_delete" />
      <input type="hidden" name="site" value="<?php echo $site; ?>" />
      <input type="hidden" name="token" value="<?php echo $token_new; ?>" />

      <span class="hcmsInvertColor"><?php echo $hcms_lang['portal-template'][$lang]; ?></span>
      <select name="template" onChange="selectportal(this);" style="width:<?php if ($is_mobile) echo "130px"; else echo "200px"; ?>;" title="<?php echo getescapedtext ($hcms_lang['template'][$lang]); ?>">
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
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createtplLayer','','show', 'hcms_messageLayer','','hide');\" name=\"media_new\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_tpl_new.png\" alt=\"".getescapedtext ($hcms_lang['create'][$lang])."\" title=\"".getescapedtext ($hcms_lang['create'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_tpl_new.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
    <?php
    if (checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpldelete'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"deleteportal();\" name=\"media_delete\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_tpl_delete.png\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_tpl_delete.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if (file_exists ($mgmt_config['abs_path_cms']."help/templateguide_".$hcms_lang_shortcut[$lang].".pdf") && checkglobalpermission ($site, 'tpl'))
    {echo "<img  onClick=\"hcms_openWindow('help/templateguide_".$hcms_lang_shortcut[$lang].".pdf', 'help', 'scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");\" name=\"pic_obj_help\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_help.png\" class=\"hcmsButton hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" />\n";}
    elseif (file_exists ($mgmt_config['abs_path_cms']."help/templateguide_en.pdf") && checkglobalpermission ($site, 'tpl'))
    {echo "<img  onClick=\"hcms_openWindow('help/templateguide_en.pdf', 'help', 'scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");\" name=\"pic_obj_help\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_help.png\" class=\"hcmsButton hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" />\n";}
    ?>      
  </div>
</div>

<div id="createtplLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:hidden;">
  <form name="tpl_create" action="" method="post">
    <input type="hidden" name="action" value="tpl_create" />
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
    
    <table class="hcmsTableStandard" style="width:100%; height:60px;">
      <tr>
        <td>
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['create'][$lang]); ?></span> <span class="hcmsTextSmall">(<?php echo getescapedtext ($hcms_lang['name-without-ext'][$lang]); ?>)</span><br />
          <span style="white-space:nowrap;">
            <input type="text" name="template" maxlength="100" style="width:160px;" placeholder="<?php echo getescapedtext ($hcms_lang['template'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['template'][$lang]); ?>"/>
            <img name="Button1" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_tpl_create();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
          </span>
        </td>
        <td style="width:38px; text-align:right; vertical-align:top;">
          <img name="hcms_mediaClose1" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose1','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('createtplLayer','','hide');" />
        </td>        
      </tr>  
    </table>
  </form>
</div>

</body>
</html>
