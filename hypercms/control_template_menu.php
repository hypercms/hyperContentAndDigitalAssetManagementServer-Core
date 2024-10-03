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
$cat = getrequest_esc ("cat", "objectname");
$template = getrequest ("template", "objectname");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
{
  require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
}

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'template') || !checkglobalpermission ($site, 'tpl') || !valid_publicationname ($site) || (!empty ($mgmt_config[$site]['dam']) && $cat != "meta" && $cat != "inc" && $cat != "comp")) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// initialize
$show = "";
$add_onload = "";

// define template category name
if ($cat == "page")
{
  $pagecomp = getescapedtext ($hcms_lang['page-template'][$lang]);
}
elseif ($cat == "comp")
{
  $pagecomp = getescapedtext ($hcms_lang['component-template'][$lang]);
}
elseif ($cat == "inc")
{
  $pagecomp = getescapedtext ($hcms_lang['template-includes'][$lang]);
}
elseif ($cat == "meta")
{
  $pagecomp = getescapedtext ($hcms_lang['meta-data-template'][$lang]);
}

// check if template name is an attribute of a sent string
if (strpos ($template, ".php?") > 0)
{
  // extract template name
  $template = getattribute ($template, "template");
}

// execute actions
if (checktoken ($token, $user) && valid_publicationname ($site))
{
  $file_csv = $mgmt_config['abs_path_data']."include/".$site.".taxonomy.csv";
   
  // export as CSV
  if ($action == "export" && checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
  {
    if (is_file ($file_csv)) $export = load_csv ($file_csv, ";", '"', "utf-8", "utf-8");
    else $export = loadtaxonomy ($site);

    // CSV export
    if (is_array ($export)) create_csv ($export, "taxonomy.csv", "php://output", ";", '"', "utf-8", "utf-8", true);
    else $show = getescapedtext ($hcms_lang['configuration-not-available'][$lang]);
  }
  // create template
  elseif ($action == "tpl_create" && checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tplcreate')) 
  {
    $result = createtemplate ($site, $template, $cat);
    
    $add_onload =  $result['add_onload'];
    $show = $result['message'];  
  }
  // delete template
  elseif ($action == "tpl_delete" && checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpldelete')) 
  {
    $result = deletetemplate ($site, $template, $cat);
    
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

function resettemplate ()
{
  document.forms['tpl_delete'].elements['template'].selectedIndex = 0;
}

function selecttemplate (selObj)
{
  if (selObj.options[selObj.selectedIndex].value != "")
  {
    <?php if (checkglobalpermission ($site, 'tpledit')) { ?>
    parent.frames['mainFrame'].location.href = 'frameset_template_edit.php?site=<?php echo url_encode($site); ?>&cat=<?php echo url_encode($cat); ?>&save=no&template=' + selObj.options[selObj.selectedIndex].value;
    <?php } else { ?>
    parent.frames['mainFrame'].location.href = 'template_view.php?site=<?php echo url_encode($site); ?>&cat=<?php echo url_encode($cat); ?>&save=no&template=' + selObj.options[selObj.selectedIndex].value;
    <?php } ?>
  }
  else
  {
    parent.frames['mainFrame'].location.href = 'empty.php';
  }
}

function createtemplate ()
{
  hcms_showHideLayers('createtplLayer','','show', 'hcms_messageLayer','','hide');
  if (typeof parent.hcms_openSubMenu == "function") parent.hcms_openSubMenu(78);
}

function closetemplate ()
{
  hcms_showHideLayers('createtplLayer','','hide');
  if (typeof parent.hcms_closeSubMenu == "function") parent.hcms_closeSubMenu();
}

function deletetemplate ()
{
  var form = document.forms['tpl_delete'];

  if (form.elements['template'].value == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-select-an-option'][$lang]); ?>"));
    return false;
  }
  else if (form.elements['template'].value == "default.meta.tpl")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['the-input-is-not-valid'][$lang]); ?> (default)"));
    return false;
  }
  else
  {
    check = confirm(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['warning'][$lang]); ?>\n <?php echo getescapedtext ($hcms_lang['the-selected-item-will-be-removed'][$lang]); ?>\n <?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-the-template'][$lang]); ?>"));
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

function checkForm_tpl_create ()
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

function checkForm_import ()
{
  var form = document.forms['import'];
  var filename = form.elements['importfile'].value;
  
  if (filename.trim() == "" || filename.substr((filename.lastIndexOf('.') + 1)).toLowerCase() != "csv")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-select-a-file-to-upload'][$lang]); ?>"));
    form.elements['importfile'].focus();
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

<?php echo showmessage ($show, 660, 65, $lang, "position:fixed; left:5px; top:5px;"); ?>

<div class="hcmsLocationBar">
  <?php if (!$is_mobile) { ?>
  <table class="hcmsTableNarrow">
    <tr>
      <td class="hcmsHeadline"><?php echo getescapedtext ($site." &gt; ".$pagecomp); ?></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>  
  </table>
  <?php } else { ?>
  <span style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo getescapedtext ($site." &gt; ".$pagecomp); ?></span>
  <?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar hcmsWorkplaceControl" style="<?php echo gettoolbarstyle ($is_mobile); ?>">
  <div class="hcmsToolbarBlock" style="padding:2px;">
    <form name="tpl_delete" action="" method="post">
      <input type="hidden" name="action" value="tpl_delete" />
      <input type="hidden" name="site" value="<?php echo $site; ?>" />
      <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
      <input type="hidden" name="token" value="<?php echo $token_new; ?>" />

      <span class="hcmsInvertPrimaryColor">
        <span class=""><?php echo getescapedtext ($pagecomp); ?></span>
      </span>
      <select name="template" onChange="selecttemplate(this);" style="width:<?php if ($is_mobile) echo "120px"; else echo "180px"; ?>;" title="<?php echo getescapedtext ($hcms_lang['template'][$lang]); ?>">
        <option value=""><?php echo getescapedtext ($hcms_lang['select'][$lang]); ?></option>
        <?php
        $template_files = getlocaltemplates ($site, $cat);

        if (is_array ($template_files) && sizeof ($template_files) > 0)
        {
          foreach ($template_files as $value)
          {
            if ($cat == "inc" || strpos ($value, ".inc.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".inc.tpl"));
            elseif ($cat == "page" || strpos ($value, ".page.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".page.tpl"));
            elseif ($cat == "comp" || strpos ($value, ".comp.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".comp.tpl"));
            elseif ($cat == "meta" || strpos ($value, ".meta.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".meta.tpl"));

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
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"createtemplate();\">
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
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"deletetemplate();\">
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
  <?php if ($cat == "meta") { ?>
  <div class="hcmsToolbarBlock">
    <?php
    if (!empty ($mgmt_config['db_connect_rdbms']) && checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"parent.frames['mainFrame'].location='frameset_licensenotification.php?site=".url_encode($site)."&cat=comp'; resettemplate();\">
        <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_user_sendlink.png\" alt=\"".getescapedtext ($hcms_lang['license-notification'][$lang])."\" title=\"".getescapedtext ($hcms_lang['license-notification'][$lang])."\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['license-notification'][$lang])."</span>
      </div>";
    }
    else
    {
      echo "
      <div class=\"hcmsButtonOff hcmsInvertColor\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_user_sendlink.png\" class=\"hcmsButtonSizeSquare hcmsFloatLeft\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['license-notification'][$lang])."</span>
      </div>";
    }
    ?> 
    <?php
    if (checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"parent.frames['mainFrame'].location='media_mapping.php?site=".url_encode($site)."'; resettemplate();\">
        <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_mapping.png\" alt=\"".getescapedtext ($hcms_lang['meta-data-mapping'][$lang])."\" title=\"".getescapedtext ($hcms_lang['meta-data-mapping'][$lang])."\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['meta-data-mapping'][$lang])."</span>
      </div>";
    }
    else
    {
      echo "
      <div class=\"hcmsButtonOff hcmsInvertColor\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_mapping.png\" class=\"hcmsButtonSizeSquare hcmsFloatLeft\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['meta-data-mapping'][$lang])."</span>
      </div>";
    }
    ?>
    <?php
    if (checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"parent.frames['mainFrame'].location='media_hierarchy.php?site=".url_encode($site)."'; resettemplate();\">
        <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_hierarchy.png\" alt=\"".getescapedtext ($hcms_lang['meta-data-hierarchy'][$lang])."\" title=\"".getescapedtext ($hcms_lang['meta-data-hierarchy'][$lang])."\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['meta-data-hierarchy'][$lang])."</span>
      </div>";
    }
    else
    {
      echo "
      <div class=\"hcmsButtonOff hcmsInvertColor\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_hierarchy.png\" class=\"hcmsButtonSizeSquare hcmsFloatLeft\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['meta-data-hierarchy'][$lang])."</span>
      </div>";
    }
    ?>
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if (checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"parent.frames['mainFrame'].location='media_taxonomy.php?site=".url_encode($site)."'; resettemplate();\">
        <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_taxonomy.png\" alt=\"".getescapedtext ($hcms_lang['taxonomy'][$lang])."\" title=\"".getescapedtext ($hcms_lang['taxonomy'][$lang])."\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['taxonomy'][$lang])."</span>
      </div>";
    }
    else
    {
      echo "
      <div class=\"hcmsButtonOff hcmsInvertColor\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_hierarchy.png\" class=\"hcmsButtonSizeSquare hcmsFloatLeft\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['taxonomy'][$lang])."</span>      
      </div>";
    }
    ?>
    <?php
    if (checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"parent.frames['mainFrame'].location='media_taxonomy_import.php?site=".url_encode($site)."'; resettemplate();\">
        <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_import.png\" alt=\"".getescapedtext ($hcms_lang['taxonomy'][$lang]." ".$hcms_lang['import-list-comma-delimited'][$lang])."\" title=\"".getescapedtext ($hcms_lang['taxonomy'][$lang]." ".$hcms_lang['import-list-comma-delimited'][$lang])."\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['import'][$lang])."</span>
      </div>";
    }
    else
    {
      echo "
      <div class=\"hcmsButtonOff hcmsInvertColor\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_import.png\" class=\"hcmsButtonSizeSquare hcmsFloatLeft\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['import'][$lang])."</span>
      </div>";
    }
    ?>
    <?php
    if (checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
    {
      echo "
      <div class=\"hcmsButton hcmsHoverColor hcmsInvertColor\" onclick=\"location.href='?action=export&site=".url_encode($site)."&cat=".url_encode($cat)."&token=".$token_new."'; resettemplate();\">
        <img class=\"hcmsButtonSizeSquare hcmsFloatLeft\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_export_page.png\" alt=\"".getescapedtext ($hcms_lang['taxonomy'][$lang]." ".$hcms_lang['export-list-comma-delimited'][$lang])."\" title=\"".getescapedtext ($hcms_lang['taxonomy'][$lang]." ".$hcms_lang['export-list-comma-delimited'][$lang])."\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['export'][$lang])."</span>
      </div>";
    }
    else
    {
      echo "
      <div class=\"hcmsButtonOff hcmsInvertColor\">
        <img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_export_page.png\" class=\"hcmsButtonSizeSquare hcmsFloatLeft\" />
        <span class=\"hcmsButtonLabel\">".getescapedtext ($hcms_lang['export'][$lang])."</span>
      </div>";
    }
    ?>
  </div>
  <?php } ?>
  <div class="hcmsToolbarBlock">
    <?php echo showhelpbutton ("templateguide", checkglobalpermission ($site, 'tpl'), $lang, "", "hcmsHoverColor hcmsInvertColor"); ?>      
  </div>
</div>

<!-- create template -->
<div id="createtplLayer" class="hcmsMessage" style="position:absolute; left:5px; top:5px; width:<?php if ($is_mobile) echo "95%"; else echo "650px"; ?>; visibility:hidden;">
  <form name="tpl_create" action="" method="post" onsubmit="return checkForm_tpl_create();">
    <input type="hidden" name="action" value="tpl_create" />
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
    
    <table class="hcmsTableNarrow" style="width:100%; min-height:40px;">
      <tr>
        <td>
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['create'][$lang]); ?></span> <span class="hcmsTextSmall">(<?php echo getescapedtext ($hcms_lang['name-without-ext'][$lang]); ?>)</span><br />
          <span style="white-space:nowrap;">
            <input type="text" name="template" maxlength="100" style="width:<?php if ($is_mobile) echo "180px"; else echo "220px;"; ?>;" placeholder="<?php echo getescapedtext ($hcms_lang['template'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['template'][$lang]); ?>"/>
            <img name="Button1" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_tpl_create();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
          </span>
        </td>
        <td style="width:38px; text-align:right; vertical-align:top;">
          <img name="hcms_mediaClose1" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose1','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onclick="closetemplate();" />
        </td>        
      </tr>  
    </table>
  </form>
</div>

</body>
</html>
