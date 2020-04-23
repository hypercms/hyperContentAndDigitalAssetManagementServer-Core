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
$site = getrequest_esc ("site"); // site can be *Null*
$site_name = getrequest_esc ("site_name"); // site can include get parameters
$token = getrequest ("token"); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('site')) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";

// include scripts
if (checkrootpermission ('site') && checkrootpermission ('sitecreate') && $action == "site_create" && checktoken ($token, $user))
{
  $result = createpublication ($site_name, $user);
    
  $add_onload =  $result['add_onload'];
  $show = $result['message'];  
}
elseif (checkrootpermission ('site') && checkrootpermission ('sitedelete') && $action == "site_delete" && checktoken ($token, $user))
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
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
function warning_delete ()
{
  var form = document.forms['site_delete'];
  
  if (form.elements['site_name'].value == "empty.php")
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

function checkForm ()
{
  var form = document.forms['site_create'];
  
  if(form.elements['site_name'].value.trim() == "")
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
</script>
</head>

<body class="hcmsWorkplaceControlWallpaper" onLoad="<?php echo $add_onload; ?>">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:20px;"); ?>

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
  <span class="hcmsHeadlineTiny" style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo getescapedtext ($hcms_lang['publication-management'][$lang]); ?></span>
  <?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar">
  <div class="hcmsToolbarBlock">
    <?php
    if (checkrootpermission ('site')  && checkrootpermission ('sitecreate'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createsiteLayer','','show','deletesiteLayer','','hide','editsiteLayer','','hide','hcms_messageLayer','','hide')\" name=\"media_new\" src=\"".getthemelocation()."img/button_site_new.png\" alt=\"".getescapedtext ($hcms_lang['create'][$lang])."\" title=\"".getescapedtext ($hcms_lang['create'][$lang])."\" />";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_site_new.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";}
    ?>
    <?php
    if (checkrootpermission ('site')  && checkrootpermission ('sitedelete'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createsiteLayer','','hide','deletesiteLayer','','show','editsiteLayer','','hide','hcms_messageLayer','','hide')\" name=\"media_delete\" src=\"".getthemelocation()."img/button_site_delete.png\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" />";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_site_delete.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";}
    ?>
    <?php
    if (checkrootpermission ('site')  && checkrootpermission ('siteedit'))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createsiteLayer','','hide','deletesiteLayer','','hide','editsiteLayer','','show','hcms_messageLayer','','hide')\" name=\"media_edit\" src=\"".getthemelocation()."img/button_site_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" />";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_site_edit.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";}
    ?>
    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if (file_exists ($mgmt_config['abs_path_cms']."help/adminguide_".$hcms_lang_shortcut[$lang].".pdf") && checkrootpermission ('site'))
    {echo "<img  onClick=\"hcms_openWindow('help/adminguide_".$hcms_lang_shortcut[$lang].".pdf', 'help', 'scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");\" name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.png\" class=\"hcmsButton hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" />\n";}
    elseif (file_exists ($mgmt_config['abs_path_cms']."help/adminguide_en.pdf") && checkrootpermission ('site'))
    {echo "<img  onClick=\"hcms_openWindow('help/adminguide_en.pdf', 'help', 'scrollbars=no,resizable=yes', ".windowwidth("object").", ".windowheight("object").");\" name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.png\" class=\"hcmsButton hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" />\n";}
    ?>
  </div>
</div>

<?php
echo showmessage ($show, 650, 60, $lang, "position:fixed; left:15px; top:15px; ");
?>

<div id="createsiteLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:hidden;">
<form name="site_create" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="action" value="site_create" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table class="hcmsTableStandard" style="width:100%; height:60px;">
    <tr>
      <td>
        <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['create'][$lang])." ".getescapedtext ($hcms_lang['publication'][$lang]); ?></span><br />
        <span style="white-space:nowrap;">
          <input type="text" name="site_name" maxlength="100" style="width:160px;" title="<?php echo getescapedtext ($hcms_lang['publication-name'][$lang]); ?>" />
          <img name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
        </span>
      </td>
      <td style="width:38px; text-align:right; vertical-align:top;">
        <img name="hcms_mediaClose1" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose1','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('createsiteLayer','','hide');" />
      </td>        
    </tr>
  </table>
</form>
</div>

<div id="deletesiteLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:hidden;">
<form name="site_delete" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="action" value="site_delete" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table class="hcmsTableStandard" style="width:100%; height:60px;">
    <tr>
      <td style="white-space:nowrap;">
        <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['delete'][$lang])." ".getescapedtext ($hcms_lang['publication'][$lang]); ?></span><br />
        <span style="white-space:nowrap;">
          <select name="site_name" onChange="hcms_jumpMenu('parent.frames[\'mainFrame\']',this,0)" style="width:160px;" title="<?php echo getescapedtext ($hcms_lang['publication-name'][$lang]); ?>">
            <option value="empty.php"><?php echo getescapedtext ($hcms_lang['select'][$lang]); ?></option>
          <?php
            if (!isset ($inherit_db) || $inherit_db == false) $inherit_db = inherit_db_read ();
            
            $item_option_delete = array();
            $item_option_edit = array();
  
            if ($inherit_db != false && sizeof ($inherit_db) > 0)
            {
              foreach ($inherit_db as $inherit_db_record)
              {
                if (!empty ($inherit_db_record['parent']) && !empty ($siteaccess) && is_array ($siteaccess) && in_array ($inherit_db_record['parent'], $siteaccess))
                {
                  $inherit_db_record['parent'] = trim ($inherit_db_record['parent']);
                  if ($inherit_db_record['parent'] != $site) $item_option_delete[] = $inherit_db_record['parent'];
                  $item_option_edit[] = $inherit_db_record['parent'];
                }              
              }
            }
            
            if (is_array ($item_option_delete) && sizeof ($item_option_delete) > 0)
            {
              natcasesort ($item_option_delete);
              reset ($item_option_delete);
              
              foreach ($item_option_delete as $delete_option)
              {
                echo "<option value=\"site_edit_form.php?site=".url_encode($site)."&preview=yes&site_name=".url_encode($delete_option)."\">".$delete_option."</option>\n";
              }
            }
          ?>
          </select>
          <img name="Button3" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="warning_delete();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button3','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
        </span>
      </td>
      <td style="width:38px; text-align:right; vertical-align:top;">
        <img name="hcms_mediaClose2" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose2','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('deletesiteLayer','','hide');" />
      </td>        
    </tr>
  </table>
</form>
</div>

<div id="editsiteLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:hidden;">
<form name="site_edit" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  
  <table class="hcmsTableStandard" style="width:100%; height:60px;">
    <tr>
      <td style="white-space:nowrap;">
        <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['edit'][$lang])." ".getescapedtext ($hcms_lang['publication'][$lang]); ?></span><br />
        <select name="site_name" onChange="hcms_jumpMenu('parent.frames[\'mainFrame\']',this,0)" style="width:180px;" title="<?php echo getescapedtext ($hcms_lang['publication-name'][$lang]); ?>">
          <option value="empty.php"><?php echo getescapedtext ($hcms_lang['select'][$lang]); ?></option>
          <?php
          if (is_array ($item_option_edit) && sizeof ($item_option_edit) > 0)
          {
            natcasesort ($item_option_edit);
            reset ($item_option_edit);
            
            foreach ($item_option_edit as $edit_option)
            {
              echo "<option value=\"frameset_site_edit.php?site=".url_encode($site)."&preview=no&site_name=".url_encode($edit_option)."\">".$edit_option."</option>\n";
            }
          }
          ?>
        </select>
      </td>
      <td style="width:38px; text-align:right; vertical-align:top;">
        <img name="hcms_mediaClose3" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose3','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('editsiteLayer','','hide');" />
      </td>        
    </tr>
  </table>
</form>
</div>

</body>
</html>