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
if (!checkglobalpermission ($site, 'template') || !checkglobalpermission ($site, 'tpl') || !valid_publicationname ($site) || ($mgmt_config[$site]['dam'] == true && $cat != "meta")) killsession ($user);

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
if (checktoken ($token, $user))
{
  if (checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tplcreate') && $action == "tpl_create") 
  {
    $result = createtemplate ($site, $template, $cat);
    
    $add_onload =  $result['add_onload'];
    $show = $result['message'];  
  }
  elseif (checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpldelete') && $action == "tpl_delete") 
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
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<script language="JavaScript" type="text/javascript">
<!--
function warning_delete()
{
  var form = document.forms['tpl_delete'];
  
  check = confirm(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['warning'][$lang]); ?>:\r<?php echo getescapedtext ($hcms_lang['the-selected-item-will-be-removed'][$lang]); ?>\r<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-the-template'][$lang]); ?>"));
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
		alert ("<?php echo getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters'][$lang]); ?>: "+addText);
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
   
  if (form.elements['template'].value == "")
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

function checkForm_file_upload()
{  
  var form = document.forms['file_upload'];
  var uploadfile = form.elements['file'];
  
  if (uploadfile.value == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-select-a-file-to-upload'][$lang]); ?>"));
    uploadfile.focus();
    return false;
  }  

  // Windows OS with backslash
  filepos = uploadfile.lastIndexOf("\\");
  // UNIX and others using slash
  if (filepos < 1) filepos = uploadfile.lastIndexOf("/");
  
  uploadfile = uploadfile.substr(filepos+1, uploadfile.length); 
  
  if (!checkForm_chars (uploadfile, "-_"))
  {
    uploadfile.focus();
    return false;
  }
  
  form.submit();
  return true;
}
//-->
</script>
</head>

<body class="hcmsWorkplaceControlWallpaper" onLoad="<?php echo $add_onload; ?>">

<div class="hcmsLocationBar">
  <table border=0 cellspacing=0 cellpadding=0>
    <tr>
      <td class="hcmsHeadline"><?php echo $pagecomp; ?></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>  
  </table>
</div>

<!-- toolbar -->
<div class="hcmsToolbar">
  <div class="hcmsToolbarBlock">
    <?php
    if (checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tplcreate'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createtplLayer','','show','uploadtplLayer','','hide','deletetplLayer','','hide','edittplLayer','','hide','hcms_messageLayer','','hide')\" name=\"media_new\" src=\"".getthemelocation()."img/button_tpl_new.gif\" alt=\"".getescapedtext ($hcms_lang['create'][$lang])."\" title=\"".getescapedtext ($hcms_lang['create'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_tpl_new.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
    <?php
    if (checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpldelete'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createtplLayer','','hide','uploadtplLayer','','hide','deletetplLayer','','show','edittplLayer','','hide','hcms_messageLayer','','hide')\" name=\"media_delete\" src=\"".getthemelocation()."img/button_tpl_delete.gif\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_tpl_delete.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
    <?php
    if (checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createtplLayer','','hide','uploadtplLayer','','hide','deletetplLayer','','hide','edittplLayer','','show','hcms_messageLayer','','hide')\" name=\"media_edit\" src=\"".getthemelocation()."img/button_tpl_edit.gif\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_tpl_edit.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if ($cat == "meta" && $mgmt_config['db_connect_rdbms'] != "" && checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"parent.frames['mainFrame'].location.href='frameset_licensenotification.php?site=".url_encode($site)."&cat=comp';\" name=\"media_edit\" src=\"".getthemelocation()."img/button_user_sendlink.gif\" alt=\"".getescapedtext ($hcms_lang['license-notification'][$lang])."\" title=\"".getescapedtext ($hcms_lang['license-notification'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_user_sendlink.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?> 
    <?php
    if ($cat == "meta" && checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"parent.frames['mainFrame'].location.href='media_mapping.php?site=".url_encode($site)."';\" name=\"media_edit\" src=\"".getthemelocation()."img/button_mapping.gif\" alt=\"".getescapedtext ($hcms_lang['meta-data-mapping'][$lang])."\" title=\"".getescapedtext ($hcms_lang['meta-data-mapping'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_mapping.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>   
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if (!$is_mobile && file_exists ($mgmt_config['abs_path_cms']."help/templateguide_".$hcms_lang_shortcut[$lang].".pdf") && checkglobalpermission ($site, 'tpl'))
    {echo "<a href=# onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('pic_obj_help','','".getthemelocation()."img/button_help_over.gif',1)\" onClick=\"hcms_openWindow('help/templateguide_".$hcms_lang_shortcut[$lang].".pdf','help','scrollbars=no,resizable=yes','800','600');\"><img name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" /></a>\n";}
    elseif (!$is_mobile && file_exists ($mgmt_config['abs_path_cms']."help/templateguide_en.pdf") && checkglobalpermission ($site, 'tpl'))
    {echo "<a href=# onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('pic_obj_help','','".getthemelocation()."img/button_help_over.gif',1)\" onClick=\"hcms_openWindow('help/templateguide_en.pdf','help','scrollbars=no,resizable=yes','800','600');\"><img name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" /></a>\n";}
    ?>      
  </div>
</div>

<?php
echo showmessage ($show, 650, 60, $lang, "position:fixed; left:15px; top:15px; ");
?>

<div id="createtplLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:80px; z-index:4; left:15px; top:5px; visibility:hidden;">
<form name="tpl_create" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="action" value="tpl_create" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table width="100%" border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td colspan="2"><span class=hcmsHeadline><?php echo getescapedtext ($hcms_lang['create'][$lang]); ?></span></td>
      <td rowspan="2" width="16" align="right" valign="top">
        <img name="hcms_mediaClose1" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose1','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('createtplLayer','','hide');" />
      </td>        
    </tr>  
    <tr>
      <td nowrap="nowrap"><?php echo $pagecomp; ?><font size="1">(<?php echo getescapedtext ($hcms_lang['name-without-ext'][$lang]); ?>)</font>: </td>
      <td>
        <input type="text" name="template" maxlength="60" style="width:220px;" />
        <img name="Button1" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_tpl_create();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" />
      </td>
    </tr>
  </table>
</form>
</div>

<div id="uploadtplLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:3; left:15px; top:15px; visibility:hidden;">
<form name="file_upload" method="post" action="" enctype="multipart/form-data">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="action" value="file_upload" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table width="100%" height="60" border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td valign="middle" nowrap="nowrap">
        <span class=hcmsHeadline><?php echo getescapedtext ($hcms_lang['upload'][$lang]); ?></span><br />
        <?php echo $pagecomp; ?>:
        <input type="file" name="file" size="30" />
        <img name="Button2" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_file_upload();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button2','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" />
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose2" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose2','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('uploadtplLayer','','hide');" />
      </td>       
    </tr>
  </table>
</form>
</div>

<div id="deletetplLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:hidden;">
<form name="tpl_delete" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="action" value="tpl_delete" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table width="100%" height="60" border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td valign="middle" nowrap="nowrap">
        <span class=hcmsHeadline><?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?></span><br />
        <?php echo $pagecomp; ?>:
        <select name="template" onChange="hcms_jumpMenu('parent.frames[\'mainFrame\']',this,0)">
          <option value="empty.php">--- <?php echo getescapedtext ($hcms_lang['select'][$lang]); ?> ---</option>
          <?php
          $dir_template = @dir ($mgmt_config['abs_path_template'].$site."/");

          $i = 0;
          $template_files = array();
          $template_option_edit = array();

          if ($dir_template != false)
          {
            while ($entry = $dir_template->read())
            {
              if ($entry != "." && $entry != ".." && !is_dir ($entry) && substr_count ($entry, ".tpl.v_") == 0 && substr_count ($entry, ".bak") == 0)
              {
                if ($cat == "page" && strpos ($entry, ".page.tpl") > 0)
                {
                  $template_files[$i] = $entry;
                }
                elseif ($cat == "comp" && strpos ($entry, ".comp.tpl") > 0)
                {
                  $template_files[$i] = $entry;
                }
                elseif ($cat == "meta" && strpos ($entry, ".meta.tpl") > 0)
                {
                  $template_files[$i] = $entry;
                }                
                elseif ($cat == "inc" && strpos ($entry, ".inc.tpl") > 0)
                {
                  $template_files[$i] = $entry;
                }

                $i++;
              }
            }

            $dir_template->close();

            if (sizeof ($template_files) >= 1)
            {
               sort ($template_files);
               reset ($template_files);

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
          }
          ?>
        </select>
        <img name="Button3" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="warning_delete();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button3','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" />
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose3" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose3','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('deletetplLayer','','hide');" />
      </td>        
    </tr>
  </table>
</form>
</div>

<div id="edittplLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:hidden;">
<form name="tpl_edit" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  
  <table width="100%" height="60" border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td valign="middle" nowrap="nowrap">
        <span class=hcmsHeadline><?php echo getescapedtext ($hcms_lang['edit'][$lang]); ?></span><br />
        <?php echo $pagecomp; ?>:
        <select name="template" onChange="hcms_jumpMenu('parent.frames[\'mainFrame\']',this,0)">
          <option value="empty.php">--- <?php echo getescapedtext ($hcms_lang['select'][$lang]); ?> ---</option>
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
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose4" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose4','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('edittplLayer','','hide');" />
      </td>       
    </tr>
  </table>
</form>
</div>

</body>
</html>
