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
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
function warning_mediacat_delete()
{
  var form = document.forms['mediacat_delete'];
  
  if (form.elements['mediacat_name'].options[0].selected)
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-select-a-media-category'][$lang]); ?>"));
    form.elements['mediacat_name'].focus();
    return false;
  }
  else
  {
    check = confirm (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['warning'][$lang]); ?>\n <?php echo getescapedtext ($hcms_lang['the-selected-item-will-be-removed'][$lang]); ?>\n <?php echo getescapedtext ($hcms_lang['before-you-can-delete-the-category-it-must-not-hold-any-files'][$lang]); ?>\n <?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-this-item'][$lang]); ?>"));
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
		alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters'][$lang]); ?>\n ") + addText);
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
  
  if (form.elements['mediacat_name'].value.trim() == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['a-category-name-is-required'][$lang]); ?>"));
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
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['a-category-name-is-required'][$lang]); ?>"));
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
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-select-a-destination-media-category'][$lang]); ?>"));
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
      alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters'][$lang]); ?>"));
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
</script>
</head>

<body class="hcmsWorkplaceControlWallpaper" onLoad="<?php echo $add_onload; ?>">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:20px;"); ?>

<div class="hcmsLocationBar">
  <?php if (!$is_mobile) { ?>
  <table class="hcmsTableNarrow">
    <tr>
      <?php
      // define title
      echo "<td><b>".getescapedtext ($hcms_lang['template-media'][$lang])."</b></td>\n";
      ?>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>
  </table>
  <?php } else { ?>
  <span class="hcmsHeadlineTiny" style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo getescapedtext ($hcms_lang['template-media'][$lang]); ?></span>
  <?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar">
  <div class="hcmsToolbarBlock">
    <?php
    if (checkglobalpermission ($site, 'tplmedia') && checkglobalpermission ($site, 'tplmediacatcreate'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createmediacatLayer','','show','deletemediacatLayer','','hide','renamemediacatLayer','','hide','uploadmediaLayer','','hide','Layer5','','hide','hcms_messageLayer','','hide')\" name=\"media1\" src=\"".getthemelocation()."img/button_folder_new.png\" alt=\"".getescapedtext ($hcms_lang['create-media-category'][$lang])."\" title=\"".getescapedtext ($hcms_lang['create-media-category'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_folder_new.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
    <?php
    if (checkglobalpermission ($site, 'tplmedia') && checkglobalpermission ($site, 'tplmediacatdelete'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createmediacatLayer','','hide','deletemediacatLayer','','show','renamemediacatLayer','','hide','uploadmediaLayer','','hide','hcms_messageLayer','','hide')\" name=\"media2\" src=\"".getthemelocation()."img/button_folder_delete.png\" alt=\"".getescapedtext ($hcms_lang['delete-media-category'][$lang])."\" title=\"".getescapedtext ($hcms_lang['delete-media-category'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_folder_delete.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
    <?php
    if (checkglobalpermission ($site, 'tplmedia') && checkglobalpermission ($site, 'tplmediacatrename'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createmediacatLayer','','hide','deletemediacatLayer','','hide','renamemediacatLayer','','show','uploadmediaLayer','','hide','hcms_messageLayer','','hide')\" name=\"media3\" src=\"".getthemelocation()."img/button_folder_edit.png\" alt=\"".getescapedtext ($hcms_lang['rename-media-category'][$lang])."\" title=\"".getescapedtext ($hcms_lang['rename-media-category'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_folder_edit.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
  </div>
  
  <div class="hcmsToolbarBlock">
    <?php
    if (checkglobalpermission ($site, 'tplmedia') && checkglobalpermission ($site, 'tplmediaupload'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createmediacatLayer','','hide','deletemediacatLayer','','hide','renamemediacatLayer','','hide','uploadmediaLayer','','show','hcms_messageLayer','','hide')\" name=\"media_upload\" src=\"".getthemelocation()."img/button_media_new.png\" alt=\"".getescapedtext ($hcms_lang['upload-media-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['upload-media-file'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_media_new.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
    <?php
    if (checkglobalpermission ($site, 'tplmedia') && checkglobalpermission ($site, 'tplmediadelete'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"parent.frames['mainFrame'].location='frameset_edit_media.php?site=".url_encode($site)."&action=mediafile_delete&mediacat=".url_encode($mediacat)."'; hcms_showHideLayers('createmediacatLayer','','hide','deletemediacatLayer','','hide','renamemediacatLayer','','hide','uploadmediaLayer','','hide','hcms_messageLayer','','hide');\" name=\"media_delete\" src=\"".getthemelocation()."img/button_media_delete.png\" alt=\"".getescapedtext ($hcms_lang['delete-media-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['delete-media-file'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_media_delete.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";}
    ?>    
  </div>
  
  <div class="hcmsToolbarBlock">
    <?php
    if (checkglobalpermission ($site, 'tplmedia'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"parent.frames['mainFrame'].location='frameset_edit_media.php?site=".url_encode($site)."&action=mediafile_preview&mediacat=".url_encode($mediacat)."'; hcms_showHideLayers('createmediacatLayer','','hide','deletemediacatLayer','','hide','renamemediacatLayer','','hide','uploadmediaLayer','','hide','hcms_messageLayer','','hide');\" name=\"media_view\" src=\"".getthemelocation()."img/button_media_view.png\" alt=\"".getescapedtext ($hcms_lang['view-media-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['view-media-file'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_media_view.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
  </div>
  
  <div class="hcmsToolbarBlock">
    <?php
    if (file_exists ($mgmt_config['abs_path_cms']."help/usersguide_".$hcms_lang_shortcut[$lang].".pdf"))
    {echo "<img  onClick=\"hcms_openWindow('help/usersguide_".$hcms_lang_shortcut[$lang].".pdf', 'help', 'scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");\" name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.png\" class=\"hcmsButton hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" />\n";}
    elseif (file_exists ($mgmt_config['abs_path_cms']."help/usersguide_en.pdf"))
    {echo "<img  onClick=\"hcms_openWindow('help/usersguide_en.pdf', 'help', 'scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");\" name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.png\" class=\"hcmsButton hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" />\n";}
    ?>
  </div>
</div>

<?php
echo showmessage ($show, 650, 80, $lang, "position:fixed; left:15px; top:5px; ");
?>

<div id="createmediacatLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:70px; z-index:1; left:15px; top:15px; visibility:hidden;">
<form name="mediacat_create" action="control_media_menu.php" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="action" value="mediacat_create" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table class="hcmsTableStandard" style="width:100%; height:70px;">
    <tr>
      <td style="overflow:auto;">
        <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['create-media-category'][$lang]); ?></span><br />
        <span style="white-space:nowrap;">
          <input type="text" name="mediacat_name" maxlength="100" style="width:150px;" title="<?php echo getescapedtext ($hcms_lang['media-category'][$lang]); ?>" />
          <img name="Button1" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" value="Submit" onclick="checkForm_mediacat_create();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
        </span>
      </td>
      <td style="width:38px; text-align:right; vertical-align:top;">
        <img name="hcms_mediaClose1" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose1','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('createmediacatLayer','','hide');" />
      </td>         
    </tr>
  </table>
</form>
</div>

<div id="deletemediacatLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:70px; z-index:2; left:15px; top:15px; visibility:hidden;">
<form name="mediacat_delete" action="control_media_menu.php" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="action" value="mediacat_delete" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table class="hcmsTableStandard" style="width:100%; height:70px;">
    <tr>
      <td style="overflow:auto;">
        <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['delete-media-category'][$lang]); ?></span><br />
        <span style="white-space:nowrap;">
          <select name="mediacat_name" style="width:150px;" title="<?php echo getescapedtext ($hcms_lang['media-category'][$lang]); ?>">
            <option value=""><?php echo getescapedtext ($hcms_lang['select'][$lang]); ?></option>
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
          <img name="Button2" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="warning_mediacat_delete();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button2','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
        </span>
      </td>
      <td style="width:38px; text-align:right; vertical-align:top;">
        <img name="hcms_mediaClose2" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose2','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('deletemediacatLayer','','hide');" />
      </td>         
    </tr>
  </table>
</form>
</div>

<div id="renamemediacatLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:70px; z-index:2; left:15px; top:15px; visibility:hidden;">
<form name="mediacat_rename" action="control_media_menu.php" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="action" value="mediacat_rename" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table class="hcmsTableStandard" style="width:100%; height:70px;">
    <tr>
      <td style="overflow:auto;">
        <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['rename-media-category'][$lang]); ?></span><br />
        <span style="white-space:nowrap;">
          <select name="mediacat_name_curr" onChange="insertCat()" style="width:150px;" title="<?php echo getescapedtext ($hcms_lang['media-category'][$lang]); ?>">
            <option value=""><?php echo getescapedtext ($hcms_lang['select'][$lang]); ?></option>
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
          <input type="text" name="mediacat_name" maxlength="100" style="width:150px;" placeholder="<?php echo getescapedtext ($hcms_lang['rename-selected-category'][$lang]); ?> " />
          <img name="Button3" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_mediacat_rename();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button3','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
        </span>
      </td>
      <td style="width:38px; text-align:right; vertical-align:top;">
        <img name="hcms_mediaClose3" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose3','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('renamemediacatLayer','','hide');" />
      </td>        
    </tr>
  </table>
</form>
</div>

<div id="uploadmediaLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:70px; z-index:3; left:15px; top:15px; visibility:hidden;">
<form name="mediafile_upload" method="post" action="control_media_menu.php" enctype="multipart/form-data">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="action" value="mediafile_upload" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table class="hcmsTableStandard" style="width:100%; height:70px;">
    <tr>
      <td style="overflow:auto;">
        <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['upload-media-file'][$lang]); ?></span><br />
        <?php echo getescapedtext ($hcms_lang['media-category'][$lang]); ?>
        <span style="white-space:nowrap;">
          <select name="mediacat_name" style="width:150px;" title="<?php echo getescapedtext ($hcms_lang['media-category'][$lang]); ?>">
            <option value=""><?php echo getescapedtext ($hcms_lang['select'][$lang]); ?></option>
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
          <input type="file" name="file" style="width:150px;" />
          <img name="Button4" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_mediafile_upload();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button4','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
        </span>
      </td>
      <td style="width:38px; text-align:right; vertical-align:top;">
        <img name="hcms_mediaClose4" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose4','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('uploadmediaLayer','','hide');" />
      </td>        
    </tr>
  </table>
</form>
</div>

</body>
</html>
