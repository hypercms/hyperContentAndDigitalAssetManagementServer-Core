<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
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
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'template') || !checkglobalpermission ($site, 'tpl') || !valid_publicationname ($site) || (!empty ($mgmt_config[$site]['dam']) && $cat != "meta" && $cat != "inc" && $cat != "comp")) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

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
   
  // import from CSV file
  if ($action == "import" && checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
  {
    if (!empty ($_FILES["importfile"]) && move_uploaded_file ($_FILES["importfile"]["tmp_name"], $file_csv))
    {
      $import = load_csv ($file_csv, "", "", "utf-8");

      // the index starts with 1
      if (is_array ($import) && !empty ($import[1]['level']))
      {
        $save = create_csv ($import, $site.".taxonomy.csv", $mgmt_config['abs_path_data']."include/", ";", '"', "utf-8");
      }
      
      // remove uploaded file on error
      if (empty ($save)) unlink ($file_csv);
    }
    
    if (!empty ($save)) $show = getescapedtext ($hcms_lang['the-data-was-saved-successfully'][$lang]);
    else $show = getescapedtext ($hcms_lang['the-data-could-not-be-saved'][$lang]);
  }
  // export as CSV
  elseif ($action == "export" && checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
  {
    if (is_file ($file_csv)) $export = load_csv ($file_csv, "", "", "utf-8");
    else $export = loadtaxonomy ($site);

    // CSV export
    if (is_array ($export)) create_csv ($export, "taxonomy.csv");
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
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
function warning_delete()
{
  var form = document.forms['tpl_delete'];
  
  check = confirm(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['warning'][$lang]); ?>\n <?php echo getescapedtext ($hcms_lang['the-selected-item-will-be-removed'][$lang]); ?>\n <?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-the-template'][$lang]); ?>"));
  if (check == true) form.submit();
  return check;
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

function checkForm_import()
{
  var form = document.forms['import'];
  var filename = form.elements['importfile'].value;
  
  if (filename.trim() == "" || filename.substr((filename.lastIndexOf('.') + 1)).toLowerCase() != "csv")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-select-a-file-to-upload'][$lang]); ?>"));
    form.elements['foldernew'].focus();
    return false;
  }

  form.submit();
  return true;
}
</script>
</head>

<body class="hcmsWorkplaceControlWallpaper" onLoad="<?php echo $add_onload; ?>">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:20px;"); ?>

<div class="hcmsLocationBar">
  <?php if (!$is_mobile) { ?>
  <table class="hcmsTableNarrow">
    <tr>
      <td><b><?php echo $pagecomp; ?></b></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>  
  </table>
  <?php } else { ?>
  <span class="hcmsHeadlineTiny" style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo $pagecomp; ?></span>
  <?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar">
  <div class="hcmsToolbarBlock">
    <?php
    if (checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tplcreate'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createtplLayer','','show','importLayer','','hide','deletetplLayer','','hide','edittplLayer','','hide','hcms_messageLayer','','hide')\" name=\"media_new\" src=\"".getthemelocation()."img/button_tpl_new.png\" alt=\"".getescapedtext ($hcms_lang['create'][$lang])."\" title=\"".getescapedtext ($hcms_lang['create'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_tpl_new.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
    <?php
    if (checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpldelete'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createtplLayer','','hide','importLayer','','hide','deletetplLayer','','show','edittplLayer','','hide','hcms_messageLayer','','hide')\" name=\"media_delete\" src=\"".getthemelocation()."img/button_tpl_delete.png\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_tpl_delete.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
    <?php
    if (checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createtplLayer','','hide','importLayer','','hide','deletetplLayer','','hide','edittplLayer','','show','hcms_messageLayer','','hide')\" name=\"media_edit\" src=\"".getthemelocation()."img/button_tpl_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_tpl_edit.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if ($cat == "meta" && $mgmt_config['db_connect_rdbms'] != "" && checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"parent.frames['mainFrame'].location='frameset_licensenotification.php?site=".url_encode($site)."&cat=comp';\" src=\"".getthemelocation()."img/button_user_sendlink.png\" alt=\"".getescapedtext ($hcms_lang['license-notification'][$lang])."\" title=\"".getescapedtext ($hcms_lang['license-notification'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_user_sendlink.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?> 
    <?php
    if ($cat == "meta" && checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"parent.frames['mainFrame'].location='media_mapping.php?site=".url_encode($site)."';\" src=\"".getthemelocation()."img/button_mapping.png\" alt=\"".getescapedtext ($hcms_lang['meta-data-mapping'][$lang])."\" title=\"".getescapedtext ($hcms_lang['meta-data-mapping'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_mapping.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
    <?php
    if ($cat == "meta" && checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"parent.frames['mainFrame'].location='media_hierarchy.php?site=".url_encode($site)."';\" src=\"".getthemelocation()."img/button_hierarchy.png\" alt=\"".getescapedtext ($hcms_lang['meta-data-hierarchy'][$lang])."\" title=\"".getescapedtext ($hcms_lang['meta-data-hierarchy'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_hierarchy.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if ($cat == "meta" && !empty ($mgmt_config[$site]['taxonomy']) && checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"parent.frames['mainFrame'].location='media_taxonomy.php?site=".url_encode($site)."';\" src=\"".getthemelocation()."img/button_taxonomy.png\" alt=\"".getescapedtext ($hcms_lang['taxonomy'][$lang])."\" title=\"".getescapedtext ($hcms_lang['taxonomy'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_hierarchy.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
    <?php
    if ($cat == "meta" && !empty ($mgmt_config[$site]['taxonomy']) && checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createtplLayer','','hide','importLayer','','show','deletetplLayer','','hide','edittplLayer','','hide','hcms_messageLayer','','hide')\" src=\"".getthemelocation()."img/button_import.png\" alt=\"".getescapedtext ($hcms_lang['taxonomy'][$lang]." ".$hcms_lang['import-list-comma-delimited'][$lang])."\" title=\"".getescapedtext ($hcms_lang['taxonomy'][$lang]." ".$hcms_lang['import-list-comma-delimited'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_import.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
    <?php
    if ($cat == "meta" && !empty ($mgmt_config[$site]['taxonomy']) && checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"location.href='?action=export&site=".url_encode($site)."&cat=".url_encode($cat)."&token=".$token_new."';\" src=\"".getthemelocation()."img/button_export_page.png\" alt=\"".getescapedtext ($hcms_lang['taxonomy'][$lang]." ".$hcms_lang['export-list-comma-delimited'][$lang])."\" title=\"".getescapedtext ($hcms_lang['taxonomy'][$lang]." ".$hcms_lang['export-list-comma-delimited'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_export_page.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if (file_exists ($mgmt_config['abs_path_cms']."help/templateguide_".$hcms_lang_shortcut[$lang].".pdf") && checkglobalpermission ($site, 'tpl'))
    {echo "<img  onClick=\"hcms_openWindow('help/templateguide_".$hcms_lang_shortcut[$lang].".pdf', 'help', 'scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");\" name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.png\" class=\"hcmsButton hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" />\n";}
    elseif (file_exists ($mgmt_config['abs_path_cms']."help/templateguide_en.pdf") && checkglobalpermission ($site, 'tpl'))
    {echo "<img  onClick=\"hcms_openWindow('help/templateguide_en.pdf', 'help', 'scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");\" name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.png\" class=\"hcmsButton hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" />\n";}
    ?>      
  </div>
</div>

<?php
echo showmessage ($show, 650, 60, $lang, "position:fixed; left:15px; top:15px; ");
?>

<div id="createtplLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:hidden;">
  <form name="tpl_create" action="" method="post">
    <input type="hidden" name="action" value="tpl_create" />
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
    
    <table class="hcmsTableStandard" style="width:100%; height:60px;">
      <tr>
        <td>
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['create'][$lang])." ".$pagecomp; ?></span> <span class="hcmsTextSmall">(<?php echo getescapedtext ($hcms_lang['name-without-ext'][$lang]); ?>)</span><br />
          <span style="white-space:nowrap;">
            <input type="text" name="template" maxlength="100" style="width:160px;" title="<?php echo getescapedtext ($hcms_lang['template'][$lang]); ?>"/>
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

<div id="importLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:1; left:15px; top:15px; visibility:hidden">
  <form name="import" action="" method="post" enctype="multipart/form-data" onsubmit="return checkForm_import();">
    <input type="hidden" name="action" value="import" />
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>">
    
    <table class="hcmsTableStandard" style="width:100%; height:60px;">
      <tr>
        <td>
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['import-list-comma-delimited'][$lang]); ?></span><br />
          <span style="white-space:nowrap;">
            <input name="importfile" type="file" style="width:160px;" accept="text/*" />
            <img src="<?php echo getthemelocation(); ?>img/button_info.png" class="hcmsButtonSizeSquare" style="cursor:pointer;" title="Example:
    level;de;en;it
    1;Typ;Type;Tipo
    2;Abenteuer;Adventure;Avventura" />
            <img name="Button2" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_import();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button2','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
          </span>
        </td>
        <td style="width:38px; text-align:right; vertical-align:top;">
          <img name="hcms_mediaClose" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('importLayer','','hide');" />
        </td>      
      </tr>
    </table>
  </form>
</div>

<div id="deletetplLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:hidden;">
  <form name="tpl_delete" action="" method="post">
    <input type="hidden" name="action" value="tpl_delete" />
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
    
    <table class="hcmsTableStandard" style="width:100%; height:60px;">
      <tr>
        <td style="white-space:nowrap;">
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['delete'][$lang])." ".$pagecomp; ?></span><br />
          <span style="white-space:nowrap;">
            <select name="template" onChange="hcms_jumpMenu('parent.frames[\'mainFrame\']',this,0)" style="width:160px;" title="<?php echo getescapedtext ($hcms_lang['template'][$lang]); ?>">
              <option value="empty.php"><?php echo getescapedtext ($hcms_lang['select'][$lang]); ?></option>
            <?php
            $template_option_edit = array();
  
            $template_files = getlocaltemplates ($site, $cat);
  
            if (is_array ($template_files) && sizeof ($template_files) > 0)
            {
              foreach ($template_files as $value)
              {
                if ($cat == "inc" || strpos ($value, ".inc.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".inc.tpl"));
                elseif ($cat == "page" || strpos ($value, ".page.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".page.tpl"));
                elseif ($cat == "comp" || strpos ($value, ".comp.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".comp.tpl"));
                elseif ($cat == "meta" || strpos ($value, ".meta.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".meta.tpl"));
    
                if ($value != "default.meta.tpl") echo "<option value=\"template_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&template=".url_encode($value)."\">".$tpl_name."</option>\n";
    
                $template_option_edit[] = "<option value=\"frameset_template_edit.php?site=".url_encode($site)."&cat=".url_encode($cat)."&save=no&template=".url_encode($value)."\">".$tpl_name."</option>\n";
              }
            }
            ?>
            </select>
            <img name="Button3" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="warning_delete();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button3','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
          </span>
        </td>
        <td style="width:38px; text-align:right; vertical-align:top;">
          <img name="hcms_mediaClose3" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose3','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('deletetplLayer','','hide');" />
        </td>        
      </tr>
    </table>
  </form>
</div>

<div id="edittplLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:hidden;">
<form name="tpl_edit" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  
  <table class="hcmsTableStandard" style="width:100%; height:60px;">
    <tr>
      <td style="white-space:nowrap;">
        <span class=hcmsHeadline><?php echo getescapedtext ($hcms_lang['edit'][$lang])." ".$pagecomp; ?></span><br />
        <select name="template" onChange="hcms_jumpMenu('parent.frames[\'mainFrame\']',this,0)" style="width:180px;" title="<?php echo getescapedtext ($hcms_lang['template'][$lang]); ?>">
          <option value="empty.php"><?php echo getescapedtext ($hcms_lang['select'][$lang]); ?></option>
          <?php
          if (sizeof ($template_option_edit) > 0)
          {
            foreach ($template_option_edit as $edit_option)
            {
              echo $edit_option;
            }
          }
          ?>
        </select>
      </td>
      <td style="width:38px; text-align:right; vertical-align:top;">
        <img name="hcms_mediaClose4" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose4','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('edittplLayer','','hide');" />
      </td>       
    </tr>
  </table>
</form>
</div>

</body>
</html>
