<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("include/session.inc.php");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");
// language file
require_once ("language/control_media_menu.inc.php");


// input parameters
$action = getrequest_esc ("action");
$site = getrequest_esc ("site", "publicationname");
$mediacat = getrequest_esc ("mediacat", "objectname");
$mediacat_name_curr = getrequest ("mediacat_name_curr", "objectname");
$mediacat_name = getrequest ("mediacat_name", "objectname");
$token = getrequest ("token"); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check group permissions
if (!checkglobalpermission ($site, 'tplmedia') || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";

// template media category index file
if (valid_publicationname ($site)) $datafile = $site.".media.tpl.dat";
else $datafile = "";

// execute actions
if (checktoken ($token, $user))
{
  if (checkglobalpermission ($site, 'tplmedia') && checkglobalpermission ($site, 'tplmediacatcreate') && $action == "mediacat_create")
  {
    $result = createmediacat ($site, $mediacat_name);
    $add_onload = $result['add_onload'];
    $show = $result['message'];
  }
  elseif (checkglobalpermission ($site, 'tplmedia') && checkglobalpermission ($site, 'tplmediacatdelete') && $action == "mediacat_delete")
  {
    $result = deletemediacat ($site, $mediacat_name);
    $add_onload = $result['add_onload'];
    $show = $result['message'];
  }
  elseif (checkglobalpermission ($site, 'tplmedia') && checkglobalpermission ($site, 'tplmediacatrename') && $action == "mediacat_rename")
  {
    $result = renamemediacat ($site, $mediacat_name_curr, $mediacat_name);
    $add_onload = $result['add_onload'];
    $show = $result['message'];
  }
  elseif (checkglobalpermission ($site, 'tplmedia') && checkglobalpermission ($site, 'tplmediaupload') && $action == "mediafile_upload")
  {
    $result = uploadtomediacat ($site, $mediacat_name, $_FILES);
    $add_onload = $result['add_onload'];
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
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
<!--
function warning_mediacat_delete()
{
  var form = document.forms['mediacat_delete'];
  
  if (form.elements['mediacat_name'].options[0].selected)
  {
    alert (hcms_entity_decode("<?php echo $text0[$lang]; ?>"));
    form.elements['mediacat_name'].focus();
    return false;
  }
  else
  {
    check = confirm (hcms_entity_decode("<?php echo $text1[$lang]; ?>:\r<?php echo $text2[$lang]; ?>\r<?php echo $text3[$lang],"."; ?>\r<?php echo $text4[$lang]; ?>"));
    if (check == true) form.submit();
    return true;
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
		alert (hcms_entity_decode("<?php echo $text5[$lang]; ?>:\r") + addText);
		return false;
	}
  else
  {
		return true;
	}
}

function checkForm_mediacat_create()
{
  var form = document.forms['mediacat_create'];
  
  if (form.elements['mediacat_name'].value == "")
  {
    alert (hcms_entity_decode("<?php echo $text6[$lang]; ?>"));
    form.elements['mediacat_name'].focus();
    return false;
  }
  
  if (!checkForm_chars(form.elements['mediacat_name'].value, "-_"))
  {
    form.elements['mediacat_name'].focus();
    return false;
  }
  
  form.submit();
  return true; 
}

function checkForm_mediacat_rename()
{
  var form = document.forms['mediacat_rename'];
  
  if (form.elements['mediacat_name'].value == "")
  {
    alert (hcms_entity_decode("<?php echo $text6[$lang]; ?>"));
    form.elements['mediacat_name'].focus();
    return false;
  }
  
  if (!checkForm_chars(form.elements['mediacat_name'].value, "-_"))
  {
    form.elements['mediacat_name'].focus();
    return false;
  }

  form.submit();
  return true; 
}

function checkForm_mediafile_upload()
{
  var form = document.forms['mediafile_upload'];
  
  if (form.elements['mediacat_name'].options[0].selected)
  {
    alert (hcms_entity_decode("<?php echo $text7[$lang]; ?>"));
    form.elements['mediacat_name'].focus();
    return false;
  }
  else
  {
    mediafile = form.elements['file'].value;
  
    // Windows OS with backslash
    filepos = mediafile.lastIndexOf("\\");
    // UNIX and others using slash
    if (filepos < 1) filepos = mediafile.lastIndexOf("/");
    
    mediafile = mediafile.substr (filepos+1, mediafile.length);
    
    if (mediafile == "")
    {
      alert (hcms_entity_decode("<?php echo $text5[$lang]; ?>"));
      form.elements['file'].focus();
      return false;
    }
    
    if (!checkForm_chars (form.elements['file'].value, "\\ /:.-_[](){}"))
    {
      form.elements['file'].focus();
      return false;
    }
    
    form.submit();
    return true;    
  }
}

function insertCat()
{
  for(var i=0; i<document.forms['mediacat_rename'].elements['mediacat_name_curr'].options.length; i++)
  {
    if (document.forms['mediacat_rename'].elements['mediacat_name_curr'].options[i].selected)
    {
      if (document.forms['mediacat_rename'].elements['mediacat_name_curr'].options[i].value != "")
      {
        document.forms['mediacat_rename'].elements['mediacat_name'].value = document.forms['mediacat_rename'].elements['mediacat_name_curr'].options[i].text;
      }
      else
      {
        document.forms['mediacat_rename'].elements['mediacat_name'].value = "";
      }
    }
  }
}

function goToURL()
{ 
  var i, args=goToURL.arguments; document.returnValue = false;
  for (i=0; i<(args.length-1); i+=2) eval(args[i]+".location='"+args[i+1]+"'");
}
//-->
</script>
</head>

<body class="hcmsWorkplaceControlWallpaper" onLoad="<?php echo $add_onload; ?>">

<div class="hcmsLocationBar">
  <table border=0 cellspacing=0 cellpadding=0>
    <tr>
      <?php
      // define title
      echo "<td class=\"hcmsHeadline\">".$text10[$lang]."</td>\n";
      ?>
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
    if (checkglobalpermission ($site, 'tplmedia') && checkglobalpermission ($site, 'tplmediacatcreate'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createmediacatLayer','','show','deletemediacatLayer','','hide','renamemediacatLayer','','hide','uploadmediaLayer','','hide','Layer5','','hide','hcms_messageLayer','','hide')\" name=\"media1\" src=\"".getthemelocation()."img/button_folder_new.gif\" alt=\"".$text11[$lang]."\" title=\"".$text11[$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_folder_new.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
    <?php
    if (checkglobalpermission ($site, 'tplmedia') && checkglobalpermission ($site, 'tplmediacatdelete'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createmediacatLayer','','hide','deletemediacatLayer','','show','renamemediacatLayer','','hide','uploadmediaLayer','','hide','hcms_messageLayer','','hide')\" name=\"media2\" src=\"".getthemelocation()."img/button_folder_delete.gif\" alt=\"".$text12[$lang]."\" title=\"".$text12[$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_folder_delete.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
    <?php
    if (checkglobalpermission ($site, 'tplmedia') && checkglobalpermission ($site, 'tplmediacatrename'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createmediacatLayer','','hide','deletemediacatLayer','','hide','renamemediacatLayer','','show','uploadmediaLayer','','hide','hcms_messageLayer','','hide')\" name=\"media3\" src=\"".getthemelocation()."img/button_folder_rename.gif\" alt=\"".$text13[$lang]."\" title=\"".$text13[$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_folder_rename.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if (checkglobalpermission ($site, 'tplmedia') && checkglobalpermission ($site, 'tplmediaupload'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createmediacatLayer','','hide','deletemediacatLayer','','hide','renamemediacatLayer','','hide','uploadmediaLayer','','show','hcms_messageLayer','','hide')\" name=\"media_upload\" src=\"".getthemelocation()."img/button_media_new.gif\" alt=\"".$text14[$lang]."\" title=\"".$text14[$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_media_new.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
    <?php
    if (checkglobalpermission ($site, 'tplmedia') && checkglobalpermission ($site, 'tplmediadelete'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"parent.frames['mainFrame'].location.href='media_edit_frameset.php?site=".url_encode($site)."&action=mediafile_delete&mediacat=".url_encode($mediacat)."'; hcms_showHideLayers('createmediacatLayer','','hide','deletemediacatLayer','','hide','renamemediacatLayer','','hide','uploadmediaLayer','','hide','hcms_messageLayer','','hide');\" name=\"media_delete\" src=\"".getthemelocation()."img/button_media_delete.gif\" alt=\"".$text15[$lang]."\" title=\"".$text15[$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_media_delete.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";}
    ?>
    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if (checkglobalpermission ($site, 'tplmedia'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"parent.frames['mainFrame'].location.href='media_edit_frameset.php?site=".url_encode($site)."&action=mediafile_preview&mediacat=".url_encode($mediacat)."'; hcms_showHideLayers('createmediacatLayer','','hide','deletemediacatLayer','','hide','renamemediacatLayer','','hide','uploadmediaLayer','','hide','hcms_messageLayer','','hide');\" name=\"media_view\" src=\"".getthemelocation()."img/button_media_view.gif\" alt=\"".$text16[$lang]."\" title=\"".$text16[$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_media_view.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if (!$is_mobile && file_exists ("help/usersguide_".$lang_shortcut[$lang].".pdf"))
    {echo "<a href=# onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('pic_obj_help','','".getthemelocation()."img/button_help_over.gif',1)\" onClick=\"hcms_openWindow('help/usersguide_".$lang_shortcut[$lang].".pdf','help','scrollbars=no,resizable=yes','800','600');\"><img name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".$text50[$lang]."\" title=\"".$text50[$lang]."\" /></a>\n";}
    ?>
  </div>
</div>

<?php
if ($show != "") echo showmessage ($show, 650, 80, $lang, "position:absolute; left:15px; top:5px; ");
?>

<div id="createmediacatLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:80px; z-index:1; left:15px; top:5px; visibility:hidden;">
<form name="mediacat_create" action="control_media_menu.php" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="action" value="mediacat_create" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table width="100%" height="80" border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td valign="middle" nowrap="nowrap">
        <span class=hcmsHeadline><?php echo $text11[$lang]; ?></span><br />
        <?php echo $text18[$lang]; ?>:
        <input type="text" name="mediacat_name" maxlength="100" style="width:150px;">
        <img name="Button1" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" value="Submit" onclick="checkForm_mediacat_create();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" />
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose1" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $text20[$lang]; ?>" title="<?php echo $text20[$lang]; ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose1','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('createmediacatLayer','','hide');" />
      </td>         
    </tr>
  </table>
</form>
</div>

<div id="deletemediacatLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:2; left:15px; top:15px; visibility:hidden;">
<form name="mediacat_delete" action="control_media_menu.php" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="action" value="mediacat_delete" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table width="100%" height="60" border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td valign="middle" nowrap="nowrap">
        <span class=hcmsHeadline><?php echo $text12[$lang]; ?></span><br />
        <?php echo $text18[$lang]; ?>:
        <select name="mediacat_name" style="width:150px;">
          <option value="">--- <?php echo $text17[$lang]; ?> ---</option>
          <?php
          $mediacat_data = loadfile ($mgmt_config['abs_path_data']."media/", $datafile);
          
          if ($mediacat_data != false) $mediacat_array = explode ("\n", trim ($mediacat_data));
          else $mediacat_array = false;

          if (is_array ($mediacat_array) && sizeof ($mediacat_array) > 0)
          {
            sort ($mediacat_array);
            reset ($mediacat_array);

            foreach ($mediacat_array as $mediacat_record)
            {
              list ($mediacategory, $files) = explode (":|", $mediacat_record);
              echo "<option value=\"".$mediacategory."\">".$mediacategory."</option>\n";
            }
          }
          ?>
        </select>
        <img name="Button2" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="warning_mediacat_delete();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button2','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" />
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose2" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $text20[$lang]; ?>" title="<?php echo $text20[$lang]; ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose2','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('deletemediacatLayer','','hide');" />
      </td>         
    </tr>
  </table>
</form>
</div>

<div id="renamemediacatLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:80px; z-index:2; left:15px; top:5px; visibility:hidden;">
<form name="mediacat_rename" action="control_media_menu.php" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="action" value="mediacat_rename" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table width="100%" height="80" border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td colspan="2"><span class=hcmsHeadline><?php echo $text13[$lang]; ?></span></td>
      <td rowspan="2" width="16" align="right" valign="top">
        <img name="hcms_mediaClose3" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $text20[$lang]; ?>" title="<?php echo $text20[$lang]; ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose3','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('renamemediacatLayer','','hide');" />
      </td>        
    </tr>
    <tr>
      <td width="100" nowrap="nowrap">
        <?php echo $text18[$lang]; ?>: 
      </td>
      <td>
        <select name="mediacat_name_curr" style="width:150px;" onChange="insertCat()">
          <option value="">--- <?php echo $text17[$lang]; ?> ---</option>
          <?php
          if (is_array ($mediacat_array) && sizeof ($mediacat_array) > 0)
          {
            foreach ($mediacat_array as $mediacat_record)
            {
              list ($mediacategory, $files) = explode (":|", $mediacat_record);
              echo "<option value=\"".$mediacategory."\">".$mediacategory."</option>\n";
            }
          }
          ?>
        </select>
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">
        <?php echo $text19[$lang]; ?>: 
      </td>
      <td>
        <input type="text" name="mediacat_name" maxlength="100" style="width:150px;" />
        <img name="Button3" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_mediacat_rename();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button3','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" />
      </td>       
    </tr>
  </table>
</form>
</div>

<div id="uploadmediaLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:80px; z-index:3; left:15px; top:5px; visibility:hidden;">
<form name="mediafile_upload" method="post" action="control_media_menu.php" enctype="multipart/form-data">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="action" value="mediafile_upload" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table width="100%" height="80" border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td colspan="2"><span class=hcmsHeadline><?php echo $text14[$lang]; ?></span></td>
      <td rowspan="2" width="16" align="right" valign="top">
        <img name="hcms_mediaClose4" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonBlank hcmsButtonSizeSquare" alt="<?php echo $text20[$lang]; ?>" title="<?php echo $text20[$lang]; ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose4','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('uploadmediaLayer','','hide');" />
      </td>        
    </tr>  
    <tr>
      <td width="100" nowrap="nowrap">
        <?php echo $text18[$lang]; ?>: 
      </td>
      <td>
        <select name="mediacat_name" style="width:150px;">
          <option value="">--- <?php echo $text17[$lang]; ?> ---</option>
          <?php
          if (is_array ($mediacat_array) && sizeof ($mediacat_array) > 0)
          {
            foreach ($mediacat_array as $mediacat_record)
            {
              list ($mediacategory, $files) = explode (":|", $mediacat_record);
              echo "<option value=\"".$mediacategory."\">".$mediacategory."</option>\n";
            }
          }
          ?>
        </select>
      </td>
    </tr>
    <tr>
      <td nowrap="nowrap">
        <?php echo $text14[$lang]; ?>:
      </td>
      <td>
        <input type="file" name="file" size="25" />
        <img name="Button4" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_mediafile_upload();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button4','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" />
      </td>       
    </tr>
  </table>
</form>
</div>

</body>
</html>
