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
require_once ("language/control_workflow_menu.inc.php");


// input parameters
$action = getrequest_esc ("action");
$site = getrequest_esc ("site", "publicationname");
$cat = getrequest_esc ("cat", "objectname");
$wf_file = getrequest ("wf_file", "objectname");
$wf_name = getrequest_esc ("wf_name", "objectname");
$usermax = getrequest_esc ("usermax", "numeric");
$scriptmax = getrequest_esc ("scriptmax", "numeric");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'workflow') || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";

// define category name
if ($cat == "man")
{
  $item_type = $text6[$lang];
  $item_title = $text22[$lang];
}
elseif ($cat == "script")
{
  $item_type = $text7[$lang];
  $item_title = $text7[$lang];
}

// check if template name is an attribute of a sent string
if (strpos ($wf_file, ".php?") > 0)
{
  // extract template name
  $wf_file = getattribute ($wf_file, "wf_file");
}

// execute actions
if (checktoken ($token, $user))
{
  if (
       (
         ($cat == "script" && checkglobalpermission ($site, 'workflowscript') && checkglobalpermission ($site, 'workflowscriptcreate')) || 
         ($cat == "man" && checkglobalpermission ($site, 'workflowproc') && checkglobalpermission ($site, 'workflowproccreate'))
       ) && 
       $action == "item_create"
     )
  {
    $result = createworkflow ($site, $wf_name, $cat, $usermax, $scriptmax);
    
    $add_onload = $result['add_onload'];
    $show = $result['message'];  
  }
  elseif (
           (
             ($cat == "script" && checkglobalpermission ($site, 'workflowscript') && checkglobalpermission ($site, 'workflowscriptdelete')) || 
             ($cat == "man" && checkglobalpermission ($site, 'workflowproc') && checkglobalpermission ($site, 'workflowprocdelete'))
           ) && 
           $action == "item_delete"
         )
  {
    $result = deleteworkflow ($site, $wf_file, $cat);
    
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
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/timeout.js" type="text/javascript"></script>
<script src="javascript/fclick.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
<!--
function warning_delete()
{
  var form = document.forms['item_delete'];
  
  check = confirm(hcms_entity_decode("<?php echo $text0[$lang]; ?>:\r<?php echo $text1[$lang]; ?>\r<?php echo $text2[$lang]; ?>"));
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
		alert("<?php echo $text3[$lang]; ?>: "+addText);
		return false;
	}
  else
  {
		return true;
	}
}

function checkForm_item_create()
{
  var form = document.forms['item_create'];
  var workflowname = form.elements['wf_name'];
  
  if (workflowname.value == "")
  {
    alert (hcms_entity_decode("<?php echo $text4[$lang]; ?>"));
    workflowname.focus();
    return false;
  }
  
  if (!checkForm_chars (workflowname.value, "-_"))
  {
    workflowname.focus();
    return false;
  }

  if (eval (form.elements['usermax']) || eval (form.elements['scriptmax']))
  {
    var usermax = form.elements['usermax'];
    var scriptmax = form.elements['scriptmax'];
    var min1 = 1;
    var max1 = 30;
    
    val1 = usermax.value;   
         
    if (val1=="" || val1<min1 || max1<val1) 
    {
      alert (hcms_entity_decode ('<?php echo $text18[$lang]." ".$text20[$lang]; ?> '+min1+' <?php echo $text21[$lang]; ?> '+max1));
      usermax.focus();
      return false;
    }
    
    var min2 = 0;
    var max2 = 30;  
    
    val2 = scriptmax.value;
      
    if (val2<min2 || max2<val2) 
    {
      alert (hcms_entity_decode ('<?php echo $text19[$lang]." ".$text20[$lang]; ?> '+min2+' <?php echo $text21[$lang]; ?> '+max2));
      scriptmax.focus();
      return false;
    }  
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
      <td class="hcmsHeadline"><?php echo $item_title; ?></td>
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
    if (($cat == "script" && checkglobalpermission ($site, 'workflowscript') && checkglobalpermission ($site, 'workflowscriptcreate')) || ($cat == "man" && checkglobalpermission ($site, 'workflowproc') && checkglobalpermission ($site, 'workflowproccreate')))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createworkflowLayer','','show','deleteworkflowLayer','','hide','editworkflowLayer','','hide','hcms_messageLayer','','hide')\" name=\"media_new\" src=\"".getthemelocation()."img/button_workflow_new.gif\" alt=\"".$text9[$lang]."\" title=\"".$text9[$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_workflow_new.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
    <?php
    if (($cat == "script" && checkglobalpermission ($site, 'workflowscript') && checkglobalpermission ($site, 'workflowscriptdelete')) || ($cat == "man" && checkglobalpermission ($site, 'workflowproc') && checkglobalpermission ($site, 'workflowprocdelete')))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createworkflowLayer','','hide','deleteworkflowLayer','','show','editworkflowLayer','','hide','hcms_messageLayer','','hide')\" name=\"media_delete\" src=\"".getthemelocation()."img/button_workflow_reject.gif\" alt=\"".$text11[$lang]."\" title=\"".$text11[$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_workflow_reject.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
    <?php
    if (($cat == "script" && checkglobalpermission ($site, 'workflowscript') && checkglobalpermission ($site, 'workflowscriptedit')) || ($cat == "man" && checkglobalpermission ($site, 'workflowproc') && checkglobalpermission ($site, 'workflowprocedit')))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createworkflowLayer','','hide','deleteworkflowLayer','','hide','editworkflowLayer','','show','hcms_messageLayer','','hide')\" name=\"media_edit\" src=\"".getthemelocation()."img/button_workflow_edit.gif\" alt=\"".$text12[$lang]."\" title=\"".$text12[$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_workflow_edit.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
    <?php  
    if ($cat == "man")
    {
      echo "  </div>
      <div class=\"hcmsToolbarBlock\">\n";
      
      if (checkglobalpermission ($site, 'workflowproc') && checkglobalpermission ($site, 'workflowprocfolder'))
      {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"parent.frames['mainFrame'].location.href='frameset_workflow_folder.php?site=".url_encode($site)."&cat=comp'; hcms_showHideLayers('createworkflowLayer','','hide','deleteworkflowLayer','','hide','editworkflowLayer','','hide','hcms_messageLayer','','hide');\" name=\"media_foldercomp\" src=\"".getthemelocation()."img/button_workflow_foldercomp.gif\" salt=\"".$text23[$lang]."\" title=\"".$text23[$lang]."\" />\n";}
      else
      {echo "<img src=\"".getthemelocation()."img/button_workflow_foldercomp.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}

      if (checkglobalpermission ($site, 'workflowproc') && checkglobalpermission ($site, 'workflowprocfolder'))
      {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"parent.frames['mainFrame'].location.href='frameset_workflow_folder.php?site=".url_encode($site)."&cat=page'; hcms_showHideLayers('createworkflowLayer','','hide','deleteworkflowLayer','','hide','editworkflowLayer','','hide','hcms_messageLayer','','hide');\" name=\"media_folder\" src=\"".getthemelocation()."img/button_workflow_folder.gif\" alt=\"".$text24[$lang]."\" title=\"".$text24[$lang]."\" />\n";}
      else
      {echo "<img src=\"".getthemelocation()."img/button_workflow_folder.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    }
    ?>
    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if (!$is_mobile && file_exists ("help/workflowguide_".$lang_shortcut[$lang].".pdf"))
    {echo "<a href=# onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('pic_obj_help','','".getthemelocation()."img/button_help_over.gif',1)\" onClick=\"hcms_openBrWindowItem('help/workflowguide_".$lang_shortcut[$lang].".pdf','help','scrollbars=no,resizable=yes','800','600');\"><img name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".$text50[$lang]."\" title=\"".$text50[$lang]."\" /></a>\n";}
    ?>
  </tdiv>
</div>

<?php
echo showmessage ($show, 650, 60, $lang, "position:absolute; left:15px; top:15px; ");
?>

<div id="createworkflowLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:80px; z-index:4; left:15px; top:5px; visibility:hidden;">
<form name="item_create" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="action" value="item_create" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table width="100%" border="0" cellspacing="1" cellpadding="0">
    <tr>
      <td colspan="2"><span class=hcmsHeadline><?php echo $text9[$lang]; ?></span></td>
      <td rowspan="2" width="16" align="right" valign="top">
        <img name="hcms_mediaClose1" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $text16[$lang]; ?>" title="<?php echo $text16[$lang]; ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose1','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('createworkflowLayer','','hide');" />
      </td>        
    </tr>  
    <tr>
      <td width="100" nowrap="nowrap">
        <?php echo $item_type." ".$text13[$lang]; ?>:</td>
      <td>
        <input type="text" name="wf_name" maxlength="100" style="width:220px;" />
        <img name="Button1" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_item_create();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" />
      </td>
    </tr>
    <?php 
    if ($cat == "man") 
    echo "<tr>
      <td nowrap=\"nowrap\">".$text18[$lang].": </td>      
      <td nowrap=\"nowrap\"><input type=\"text\" name=\"usermax\" size=3 maxlength=3 /> &nbsp;&nbsp;&nbsp;".$text19[$lang].": <input type=\"text\" name=\"scriptmax\" size=3 maxlength=3 /></td>
    </tr>\n";
    ?>    
  </table>
</form>
</div>

<div id="deleteworkflowLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:hidden;">
<form name="item_delete" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="action" value="item_delete" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table width="100%" height="60" border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td nowrap="nowrap">
        <span class="hcmsHeadline"><?php echo $text11[$lang]; ?></span><br />
        <span style="float:left; margin:2px;"><?php echo $item_type; ?>:&nbsp;</span>
        <select name="wf_file" style="width:220px; float:left; margin:2px;" onChange="hcms_jumpMenu('parent.frames[\'mainFrame\']',this,0)">
          <option value="empty.php">--- <?php echo $text15[$lang]; ?> ---</option>
          <?php
          $temp_dir = $mgmt_config['abs_path_data']."workflow_master/";
          $dir_item = @dir ($temp_dir);

          $i = 0;
          $item_files = array();
          $item_option_edit = array();

          if ($dir_item != false)
          {
            while ($entry = $dir_item->read())
            {
              if ($entry != "." && $entry != ".." && is_file ($temp_dir.$entry) && substr ($entry, 0, strpos ($entry, ".")) == $site)
              {
                if ($cat == "man" && strpos ($entry, ".xml") > strlen ($site."."))
                {
                  $item_files[$i] = $entry;
                }
                elseif ($cat == "script" && strpos ($entry, ".inc.php") > strlen ($site."."))
                {
                  $item_files[$i] = $entry;
                }

                $i++;
              }
            }

            $dir_item->close();

            if (isset ($item_files) && is_array ($item_files) && sizeof ($item_files) > 0)
            {
              natcasesort ($item_files);
              reset ($item_files);

              foreach ($item_files as $value)
              {
                if ($cat == "man" && $value != "") 
                {
                  $item_name = substr ($value, strpos ($value, ".")+1);
                  $item_name = substr ($item_name, 0, strpos ($item_name, ".xml"));
                  
                  echo "<option value=\"empty.php?site=".url_encode($site)."&wf_file=".url_encode($value)."\">".$item_name."</option>\n";
                  $item_option_edit[] = "<option value=\"workflow_manager.php?site=".url_encode($site)."&wf_name=".url_encode($item_name)."\">".$item_name."</option>\n";                  
                }
                elseif ($cat == "script" && $value != "") 
                {
                  $item_name = substr ($value, strpos ($value, ".")+1);
                  $item_name = substr ($item_name, 0, strpos ($item_name, ".inc.php"));
                  
                  echo "<option value=\"workflow_script_form.php?site=".url_encode($site)."&cat=".url_encode($cat)."&save=no&preview=yes&wf_file=".url_encode($value)."\">".$item_name."</option>\n";
                  $item_option_edit[] = "<option value=\"workflow_script_form.php?site=".url_encode($site)."&cat=".url_encode($cat)."&save=no&preview=no&wf_file=".url_encode($value)."\">".$item_name."</option>\n";                  
                }
              }
            }
          }
          ?>
        </select>
        <img name="Button3" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="warning_delete();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button3','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" />
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose2" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $text16[$lang]; ?>" title="<?php echo $text16[$lang]; ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose2','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('deleteworkflowLayer','','hide');" />
      </td>        
    </tr>
  </table>
</form>
</div>

<div id="editworkflowLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:hidden;">
<form name="item_edit" action="" method="post" />
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  
  <table width="100%" height="60" border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td nowrap="nowrap">
        <span class=hcmsHeadline><?php echo $text12[$lang]; ?></span><br />
        <?php echo $item_type; ?>:
        <select name="wf_file" style="width:220px;" onChange="hcms_jumpMenu('parent.frames[\'mainFrame\']',this,0)">
          <option value="empty.php">--- <?php echo $text15[$lang]; ?> ---</option>
          <?php
          if (isset ($item_option_edit) && is_array ($item_option_edit) && sizeof ($item_option_edit) > 0)
          {
            foreach ($item_option_edit as $edit_option)
            {
              echo $edit_option;
            }
          }
          ?>
        </select>
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose3" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $text16[$lang]; ?>" title="<?php echo $text16[$lang]; ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose3','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('editworkflowLayer','','hide');" />
      </td>        
    </tr>
  </table>
</form>
</div>

</body>
</html>
