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
require_once ("language/template_edit.inc.php");


// input parameters
$site = getrequest_esc ("site", "publicationname");
$template = getrequest_esc ("template", "objectname");
$cat = getrequest_esc ("cat", "objectname");
$save = getrequest ("save");
$preview = getrequest ("preview");
$constraints = getrequest ("constraints");
$application = getrequest ("application", "objectname");
$extension = getrequest ("extension", "objectname");
$contentfield = getrequest ("contentfield");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!valid_publicationname ($site) || !checkglobalpermission ($site, 'template') || !checkglobalpermission ($site, 'tpl')) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";

// define template name
if (strpos ($template, ".inc.tpl") > 0)
{
  $templatename = substr ($template, 0, strpos ($template, ".inc.tpl"));
  $pagecomp = $text44[$lang];
  $cat = "inc";
}
elseif (strpos ($template, ".page.tpl") > 0)
{
  $templatename = substr ($template, 0, strpos ($template, ".page.tpl"));
  $pagecomp = $text45[$lang];
  $cat = "page";
}
elseif (strpos ($template, ".comp.tpl") > 0)
{
  $templatename = substr ($template, 0, strpos ($template, ".comp.tpl"));
  $pagecomp = $text46[$lang];
  $cat = "comp";
}
elseif (strpos ($template, ".meta.tpl") > 0)
{
  $templatename = substr ($template, 0, strpos ($template, ".meta.tpl"));
  $pagecomp = $text64[$lang];
  $cat = "meta";
}

// save template file if save button was pressed
if (checkglobalpermission ($site, 'template') && checkglobalpermission ($site, 'tpledit') && $save == "yes" && checktoken ($token, $user))
{
  // set highest cleaning level is not provided
  if (!isset ($mgmt_config['template_clean_level'])) $mgmt_config['template_clean_level'] = 3;
  // check code
  $contentfield_check = scriptcode_clean_functions ($contentfield, $mgmt_config['template_clean_level']);

   // save pers file
  if ($contentfield_check['result'] == true)
  {    
    // save template file
    $result_save = edittemplate ($site, $template, $cat, $user, $contentfield, $extension, $application);
    
    if ($result_save['result'] == true && $preview == "yes")
    {
      $add_onload = " hcms_openWindow('template_view.php?site=".url_encode($site)."&cat=".$cat."&template=".url_encode($template)."','preview','scrollbars=yes,resizable=yes', '800', '600');";
    }
    else $add_onload = "";
  }
  else $show = "<span class=hcmsHeadline>".$text0[$lang]."</span><br />\n".$text67[$lang].": <span style=\"color:red;\">".$contentfield_check['found']."</span>";
}
// load template file
else
{
  $templatedata = loadfile ($mgmt_config['abs_path_template'].$site."/", $template);
  
  // extract information
  $bufferarray = getcontent ($templatedata, "<extension>"); 
  $extension = $bufferarray[0];
  $bufferarray = getcontent ($templatedata, "<application>"); 
  $application = $bufferarray[0];  
  $bufferarray = getcontent ($templatedata, "<content>"); 
  $contentfield = $bufferarray[0];
  
  // get charset before transformation of < and >
  $result_charset = getcharset ($site, $contentfield);  
}

// escape special characters (transform all special chararcters into their html/xml equivalents)
if ($contentfield != "")
{
  $contentfield = str_replace ("&", "&amp;", $contentfield);
  $contentfield = str_replace ("<", "&lt;", $contentfield);
  $contentfield = str_replace (">", "&gt;", $contentfield);
}  

// charset
if (isset ($result_charset['charset']) && $result_charset['charset'] != "") $charset = $result_charset['charset'];
else $charset = trim ($mgmt_config[$site]['default_codepage']);


// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
function openHelp()
{
  hcms_openWindow('template_help.php?site=<?php echo $site; ?>', 'help', 'resizable=yes,scrollbars=yes', '750', '680');
}

function openmetaInfo()
{  
  hcms_openWindow('template_edit_metainfo.php?site=<?php echo $site; ?>', 'constraint', 'scrollbars=no,resizable=no', '450', '150');
}

function openLanguageInfo()
{  
  hcms_openWindow('template_edit_language.php?site=<?php echo $site; ?>', 'language', 'scrollbars=no,resizable=no', '450', '150');
}

function openConstraints()
{  
  hcms_openWindow('template_edit_constraints.php?site=<?php echo $site; ?>', 'constraint', 'scrollbars=no,resizable=no', '450', '250');
}

function openmediaType()
{  
  hcms_openWindow('template_edit_mediatype.php?site=<?php echo $site; ?>', 'constraint', 'scrollbars=no,resizable=no', '350', '150');
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
		alert("<?php echo $text48[$lang]; ?>: "+addText);
		return false;
	}
  else
  {
		return true;
	}
}

function checkForm (expression)
{  
  if (!checkForm_chars(expression, "_"))
  {
    return false;
  }   
}

function replace (string, text, by) 
{
  // Replaces text with by in string
  var strLength = string.length, txtLength = text.length;
  if ((strLength == 0) || (txtLength == 0)) return string;

  var i = string.indexOf(text);
  if ((!i) && (text != string.substring(0,txtLength))) return string;
  if (i == -1) return string;

  var newstr = string.substring(0,i) + by;

  if (i+txtLength < strLength)
      newstr += replace(string.substring(i+txtLength,strLength),text,by);

  return newstr;
}

function insertAtCaret (aTag, eTag)
{
  var input = document.forms['template_edit'].elements['contentfield'];
  
  input.focus();
  
  /* für Internet Explorer */
  if (typeof document.selection != 'undefined')
  {
    /* Einfügen des Formatierungscodes */
    var range = document.selection.createRange();
    var insText = range.text;
    
    //range.text = aTag + insText + eTag;
    range.text = aTag + eTag;
    
    /* Anpassen der Cursorposition */
    range = document.selection.createRange();
    
    if (insText.length == 0)
    {
      range.move('character', -eTag.length);
    }
    else
    {
      //range.moveStart('character', aTag.length + insText.length + eTag.length);  
      range.moveStart('character', aTag.length + eTag.length);     
    }
    
    range.select();
  }
  /* für neuere auf Gecko basierende Browser */
  else if(typeof input.selectionStart != 'undefined')
  {
    /* Einfügen des Formatierungscodes */
    var start = input.selectionStart;
    var end = input.selectionEnd;
    var insText = input.value.substring(start, end);
    
    //input.value = input.value.substr(0, start) + aTag + insText + eTag + input.value.substr(end);
    input.value = input.value.substr(0, start) + aTag + eTag + input.value.substr(end);
    
    /* Anpassen der Cursorposition */
    var pos;
    
    if (insText.length == 0)
    {
      pos = start + aTag.length;
    }
    else
    {
      //pos = start + aTag.length + insText.length + eTag.length;
      pos = start + aTag.length + eTag.length;
    }
    
    input.selectionStart = pos;
    input.selectionEnd = pos;
  }
  /* für die übrigen Browser */
  else
  {
    /* Abfrage der Einfügeposition */
    var pos;
    var re = new RegExp('^[0-9]{0,3}$');
    
    while(!re.test(pos))
    {
      pos = prompt("Einfügen an Position (0.." + input.value.length + "):", "0");
    }
    
    if(pos > input.value.length)
    {
      pos = input.value.length;
    }
    
    /* Einfügen des Formatierungscodes */
    var insText = prompt("Bitte geben Sie den zu formatierenden Text ein:");
    input.value = input.value.substr(0, pos) + aTag + insText + eTag + input.value.substr(pos);
  }
}

function format_tag (format)
{
  if (eval (document.forms['template_edit'].elements['artid'])) artid = document.forms['template_edit'].elements['artid'].value;
  else artid = "";
  
  if (eval (document.forms['template_edit'].elements['tagid'])) tagid = document.forms['template_edit'].elements['tagid'].value;
  else tagid = "";
  
  if (eval (document.forms['template_edit'].elements['onpublish']) && document.forms['template_edit'].elements['onpublish'].checked) onpublish = " onPublish='hidden'";  
  else onpublish = ""; 
  
  if (eval (document.forms['template_edit'].elements['onedit']) && document.forms['template_edit'].elements['onedit'].checked) onedit = " onEdit='hidden'";  
  else onedit = "";
  
  if (eval (document.forms['template_edit'].elements['infotype']) && document.forms['template_edit'].elements['infotype'].checked) infotype = " infotype='meta'";
  else if (eval (document.forms['template_edit'].elements['infotype_media']) && document.forms['template_edit'].elements['infotype_media'].value == "meta") infotype = " infotype='meta'";  
  else infotype = "";
    
  constraint = "";
  
  if (format == "textu" && document.forms['template_edit'].elements['constraints'].value != "") 
  {
    constraint = " constraint='" + document.forms['template_edit'].elements['constraints'].value + "'";
  }
  
  if (format == "textc") constraint = " value='" + prompt("<?php echo $text11[$lang]; ?>", "") + "'";
  
  if (format == "mediafile" && document.forms['template_edit'].elements['constraints'].value != "") 
  {
    constraint = " mediatype='" + document.forms['template_edit'].elements['constraints'].value + "'";
  }

  if (artid == "")
  {
    idtest = checkForm (tagid);
   
    if (idtest != false)
    { 
      if (tagid != "" && tagid != null)
      {
        code = "[hyperCMS:"+format+" id='"+tagid+"'"+onpublish+onedit+constraint+infotype+"]";
        insertAtCaret (code, '');
      }
      else alert (hcms_entity_decode("<?php echo $text20[$lang]; ?>"));
    } 
  }
  else if (artid != "" && artid != null)
  {
    idtest = checkForm (tagid);
   
    if (idtest != false)
    {
      if (tagid != "" && tagid != null)
      {
        code = "[hyperCMS:art"+format+" id='"+artid+":"+tagid+"'"+onpublish+onedit+constraint+infotype+"]";
        insertAtCaret (code, '');
      }
      else alert (hcms_entity_decode("<?php echo $text20[$lang]; ?>"));
    } 
  }
}

function customertracking()
{
  code = "[hyperCMS:pagetracking infotype='meta']\r\n";
  document.forms['template_edit'].contentfield.value = code + document.forms['template_edit'].elements['contentfield'].value;
}

function db_connect()
{
  var filename = prompt("<?php echo $text52[$lang]; ?>", "");
  
  if (filename != null) 
  {
    code = "[hyperCMS:dbconnect file='"+filename+"']\r\n";
    document.forms['template_edit'].contentfield.value = code + document.forms['template_edit'].elements['contentfield'].value;
  }
}

function workflow()
{
  var filename = prompt("<?php echo $text54[$lang]; ?>", "");
  
  if (filename != null)
  {
    code = "[hyperCMS:workflow name='"+filename+"']\r\n";
    document.forms['template_edit'].contentfield.value = code + document.forms['template_edit'].elements['contentfield'].value;
  }
}

function metainfo()
{
  if (document.forms['template_edit'].elements['onpublish'].checked) onpublish = " onPublish='hidden'";  
  else onpublish = ""; 
  
  if (document.forms['template_edit'].elements['onedit'].checked) onedit = " onEdit='hidden'";  
  else onedit = "";
  
  metatag = document.forms['template_edit'].elements['constraints'].value;
  code = "[hyperCMS:"+metatag+" infotype='meta'"+onpublish+onedit+"]";
  insertAtCaret (code, '');
}

function language()
{ 
  var language_sessionvar = document.forms['template_edit'].elements['language_sessionvar'].value;
  var language_sessionvalues = document.forms['template_edit'].elements['language_sessionvalues'].value;
  
  list = replace (language_sessionvalues, ';', '|');
  
  code = "[hyperCMS:language name='"+language_sessionvar+"' list='"+list+"']";
  insertAtCaret (code, '');
}

function compcontenttype()
{
  var charset = prompt("<?php echo $text60[$lang]; ?>", "<?php echo $mgmt_config[$site]['default_codepage']; ?>");
  
  if (charset != null)
  {
    code = "[hyperCMS:compcontenttype content='text/html; charset="+charset+"']\r\n";
    document.forms['template_edit'].elements['contentfield'].value = code + document.forms['template_edit'].elements['contentfield'].value; 
    document.charset = charset;
  } 
}

function pagetitle()
{
  if (document.forms['template_edit'].elements['onpublish'].checked) onpublish = " onPublish='hidden'";  
  else onpublish = "";
  
  if (document.forms['template_edit'].elements['onedit'].checked) onedit = " onEdit='hidden'";  
  else onedit = "";     
  
  code = "[hyperCMS:pagetitle"+onpublish+onedit+" infotype='meta']";
  insertAtCaret (code, '');
}

function date()
{
  code = "%date%";
  insertAtCaret (code, '');
}

function tplmedia()
{
  code = "%tplmedia%/";
  insertAtCaret (code, '');
}

function script()
{
  code = "[hyperCMS:scriptbegin\r// insert your script here\rscriptend]";
  insertAtCaret (code, '');
}

function include(format)
{
  if (format == "php") {filename = prompt("<?php echo $text16[$lang]; ?>", "");}
  if (format == "") {filename = prompt("<?php echo $text17[$lang]; ?>", "");}

  if (filename != null && format == "php") {code = "[hyperCMS:fileinclude file='"+filename+"']";}
  if (filename != null && format == "") {code = "[hyperCMS:tplinclude file='"+filename+".inc.tpl']";}

  if (filename != null) insertAtCaret (code, '');
}

function format_tag_attr(format)
{
  if (eval (document.forms['template_edit'].artid)) artid = document.forms['template_edit'].elements['artid'].value;
  else artid = "";
  
  if (eval (document.forms['template_edit'].tagid)) tagid = document.forms['template_edit'].elements['tagid'].value;
  else tagid = "";
  
  if (eval (document.forms['template_edit'].onpublish) && document.forms['template_edit'].elements['onpublish'].checked) onpublish = " onPublish='hidden'";  
  else onpublish = ""; 
  
  if (eval (document.forms['template_edit'].onedit) && document.forms['template_edit'].elements['onedit'].checked) onedit = " onEdit='hidden'";  
  else onedit = "";
  
  if (eval (document.forms['template_edit'].infotype) && document.forms['template_edit'].elements['infotype'].checked) infotype = " infotype='meta'";
  else infotype = "";  
  
  if (tagid == "" || tagid == null)
  {
    alert (hcms_entity_decode("<?php echo $text20[$lang]; ?>"));
  }
  else
  {    
    if (format == "textl") list = prompt("<?php echo $text18[$lang]; ?>", "");
    else if (format == "linktarget") list = prompt("<?php echo $text19[$lang]; ?>", "");
  
    list = replace (list, ';', '|');
    list = replace (list, '&', '&amp;');
    list = replace (list, '<', '&lt;');
    list = replace (list, '>', '&gt;');    
  
    if (artid == "")
    {
      idtest = checkForm(tagid);
     
      if (idtest != false)
      {
        if (tagid != "" && tagid != null)
        {
          code = "[hyperCMS:"+format+" id='"+tagid+"' list='"+list+"'"+onpublish+onedit+infotype+"]";
          insertAtCaret (code, '');
        }
        else alert (hcms_entity_decode("<?php echo $text20[$lang]; ?>"));
      }  
    }
    else if (artid != "" && artid != null)
    {
      idtest = checkForm(tagid);
     
      if (idtest != false)
      {  
        if (tagid != "" && tagid != null)
        {
          code = "[hyperCMS:art"+format+" id='"+artid+":"+tagid+"' list='"+list+"'"+onpublish+onedit+infotype+"]";
          insertAtCaret (code, '');
        }
        else alert (hcms_entity_decode("<?php echo $text20[$lang]; ?>"));
      } 
    }
  }
}

function savetemplate(mode)
{
  if (eval (document.forms['template_edit'].elements['extension']))
  {
    if (document.forms['template_edit'].elements['extension'].value != "")
    {
      if (mode == "preview")
      {
        document.forms['template_edit'].elements['preview'].value = "yes";
      }
        
      document.forms['template_edit'].submit();
      return true;
    }
    else
    {
      alert (hcms_entity_decode("<?php echo $text62[$lang]; ?>"));
      return false;
    }
  }
  else
  {
    if (mode == "preview")
    {
      document.forms['template_edit'].elements['preview'].value = "yes";
    }
      
    document.forms['template_edit'].submit();
    return true;
  }
}
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric" onLoad="<?php echo $add_onload; ?>">

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<?php
echo showmessage ($show, 650, 70, $lang, "position:fixed; left:15px; top:100px;")
?>

<form name="template_edit" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="template" value="<?php echo $template; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="save" value="yes" />
  <input type="hidden" name="constraints" value="" />
  <input type="hidden" name="language_sessionvar" value="" />
  <input type="hidden" name="language_sessionvalues" value="" />
  <input type="hidden" name="preview" value="no" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>">
  
  <table border=0 cellpadding=0 cellspacing=2>
    <tr>
      <td width="250" class="hcmsHeadline"><?php echo $pagecomp; ?>:</td>
      <td><input name="template" type="text" value="<?php echo $templatename; ?>" style="width:220px;" disabled="disabled" /></td>
    </tr>
    <?php if ($cat == "page" || $cat == "comp") { ?>
    <tr>
      <td class="hcmsHeadline"><?php echo $text8[$lang]; ?>:</td>
      <td><input name="extension" maxlength="10" type="text" value="<?php echo $extension; ?>" style="width:50px;" /></td>
    </tr>
    <tr>
      <td class="hcmsHeadline"><?php echo $text9[$lang]; ?>:</td>
      <td>
      <select name="application">
        <option value="asp"<?php if ($application == "asp") echo "selected=\"selected\""; ?>>Active Server Pages (ASP)</option>
        <option value="xml"<?php if ($application == "xml") echo "selected=\"selected\""; ?>>Extensible Markup Language (XML) or Text</option>
        <option value="htm"<?php if ($application == "htm") echo "selected=\"selected\""; ?>>Hypertext Markup Language (HTML)</option>
        <option value="jsp"<?php if ($application == "jsp") echo "selected=\"selected\""; ?>>Java Server Pages (JSP)</option>
        <option value="php"<?php if ($application == "php") echo "selected=\"selected\""; ?>>PHP Hypertext Preprocessor (PHP)</option>
        <?php if( $cat == "comp") { ?>
          <option value="generator"<?php if ($application == "generator") echo "selected=\"selected\""; ?>>Document Generator</option>
        <?php } ?>
      </select>
     </td>
    </tr>
    <?php } ?>
  </table>
  <br />
  
  <table border="0" cellspacing="0" cellpadding="0" style="border: 1px solid #000000; width:820px;">
  <?php
  if ($cat == "page" || $cat == "comp" || $cat == "meta" || $cat == "inc")
  {
    if ($cat == "meta") $checkbox_metainfo = "&nbsp;&nbsp;&nbsp;<input type=\"hidden\" name=\"infotype_media\" value=\"meta\" />";
    else $checkbox_metainfo = "<font color=\"#000000\">&nbsp;&nbsp;&nbsp;".$text24[$lang].":</font><input type=\"checkbox\" name=\"infotype\" value=\"meta\" />";
    
    echo "<tr>
      <td>
        <table border=\"0\" cellspacing=\"2\" cellpadding=\"0\" width=\"100%\">\n";
    if ($cat != "meta") echo "<tr>
              <td width=\"248\" nowrap=\"nowrap\">".$text2[$lang].":</td>
              <td nowrap=\"nowrap\">
                <input type=\"text\" name=\"artid\" style=\"width:200px;\" />
                <input type=\"button\" class=\"hcmsButtonBlue\" name=\"art_clean\" value=\"".$text49[$lang]."\" onClick=\"document.forms['template_edit'].elements['artid'].value = '';\" />
              </td>
              <td align=\"right\" valign=\"top\">
                <a href=# onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('pic_obj_help','','".getthemelocation()."img/button_help_over.gif',1)\" onClick=\"openHelp();\"><img name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".$text59[$lang]."\" title=\"".$text59[$lang]."\" /></a>
              </td>
            </tr>\n";            
    echo "<tr>
              <td  width=\"248\" nowrap=\"nowrap\">".$text47[$lang].":</td>
              <td nowrap=\"nowrap\">
                <input type=\"text\" name=\"tagid\" style=\"width:200px;\" />
                <input type=\"button\" class=\"hcmsButtonBlue\" name=\"tag_clean\" value=\"".$text50[$lang]."\" onClick=\"document.forms['template_edit'].elements['tagid'].value = '';\" />".$checkbox_metainfo."
              </td>
              <td>&nbsp;</td>
            </tr>\n";
    if ($cat != "meta") echo "<tr>
              <td width=\"248\" nowrap=\"nowrap\">".$text55[$lang].":</td>
              <td nowrap=\"nowrap\">
                <input type=\"checkbox\" name=\"onpublish\" value=\"hidden\" />&nbsp;".$text56[$lang]."&nbsp;
                <input type=\"checkbox\" name=\"onedit\" value=\"hidden\" />&nbsp;".$text57[$lang]."
              </td>
              <td>&nbsp;</td>
            </tr>\n";
    echo "</table>
      </td>
    </tr>\n";
  }
  ?>
    <tr>
      <td>
        <div class="hcmsToolbar">
          <div class="hcmsToolbarBlock">
            <img onClick="savetemplate('');" src="<?php echo getthemelocation(); ?>img/button_save.gif" class="hcmsButton hcmsButtonSizeSquare" name="save" alt="<?php echo $text21[$lang]; ?>" title="<?php echo $text21[$lang]; ?>" />
            <?php if ($cat != "meta") { ?><img onClick="savetemplate('preview');" class="hcmsButton hcmsButtonSizeSquare" name="savepreview" src="<?php echo getthemelocation(); ?>img/button_preview.gif" alt="<?php echo $text22[$lang]; ?>" title="<?php echo $text22[$lang]; ?>" /><?php } ?>
            <?php
            if ($cat == "page")
            {
              echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";
              echo "<img onClick=\"pagetitle();\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media15\" src=\"".getthemelocation()."img/button_pagetitletag.gif\" alt=\"".$text23[$lang]."\" title=\"".$text23[$lang]."\" />\n";
              echo "<img onClick=\"openmetaInfo();\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media16\" src=\"".getthemelocation()."img/button_pagemetatag.gif\" alt=\"".$text24[$lang]."\" title=\"".$text24[$lang]."\" />\n";
            }
            elseif ($cat == "comp" || $cat == "inc")
            {
              echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";
              echo "<img onClick=\"compcontenttype();\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media18\" src=\"".getthemelocation()."img/button_pagemetatag.gif\" alt=\"".$text61[$lang]."\" title=\"".$text61[$lang]."\" />\n";
              echo "<img onClick=\"openmetaInfo();\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media19\" src=\"".getthemelocation()."img/button_pagemetatag.gif\" alt=\"".$text24[$lang]."\" title=\"".$text24[$lang]."\" />\n";
            }

            if ($cat == "page" || $cat == "comp" || $cat == "meta" || $cat == "inc")
            {
              if ($cat != "meta") echo "<img onClick=\"openLanguageInfo();\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media17\" border=0 src=\"".getthemelocation()."img/button_languagetag.gif\" alt=\"".$text10[$lang]."\" title=\"".$text10[$lang]."\" />\n";
              echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";
              echo "<img onClick=\"openConstraints ();\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media1\" src=\"".getthemelocation()."img/button_texttag.gif\" alt=\"".$text25[$lang]."\" title=\"".$text25[$lang]."\" />\n";
              echo "<img onClick=\"format_tag('textf');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media2\" src=\"".getthemelocation()."img/button_ftexttag.gif\" alt=\"".$text26[$lang]."\" title=\"".$text26[$lang]."\" />\n";
              echo "<img onClick=\"format_tag_attr('textl')\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media3_1\" src=\"".getthemelocation()."img/button_ltexttag.gif\" alt=\"".$text27[$lang]."\" title=\"".$text27[$lang]."\" />\n";
              echo "<img onClick=\"format_tag('textc')\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media3_2\" src=\"".getthemelocation()."img/button_ctexttag.gif\" alt=\"".$text12[$lang]."\" title=\"".$text12[$lang]."\" />\n";
              echo "<img onClick=\"format_tag('textd')\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media3_3\" src=\"".getthemelocation()."img/button_datepicker.gif\" alt=\"".$text66[$lang]."\" title=\"".$text66[$lang]."\" />\n";
              if ($cat == "meta") echo "<img onClick=\"format_tag('commentu');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media1\" src=\"".getthemelocation()."img/button_commentutag.gif\" alt=\"".$text68[$lang]."\" title=\"".$text68[$lang]."\" />\n";
              if ($cat == "meta") echo "<img onClick=\"format_tag('commentf');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media2\" src=\"".getthemelocation()."img/button_commentftag.gif\" alt=\"".$text69[$lang]."\" title=\"".$text69[$lang]."\" />\n";
              if ($cat != "meta") echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";
              if ($cat != "meta") echo "<img onClick=\"openmediaType();\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media4\" src=\"".getthemelocation()."img/button_mediatag.gif\" alt=\"".$text28[$lang]."\" title=\"".$text28[$lang]."\" />\n";
              if ($cat != "meta") echo "<img onClick=\"format_tag('mediaalign');\" class=\"hcmsButton hcmsButtonSizeSquare\"g name=\"media5_0\" src=\"".getthemelocation()."img/button_mediaaligntag.gif\" alt=\"".$text29[$lang]."\" title=\"".$text29[$lang]."\" />\n";
              if ($cat != "meta") echo "<img onClick=\"format_tag('mediawidth');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media5_1\" src=\"".getthemelocation()."img/button_mediawidthtag.gif\" alt=\"".$text30[$lang]."\" title=\"".$text30[$lang]."\" />\n";
              if ($cat != "meta") echo "<img onClick=\"format_tag('mediaheight');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media5_2\" src=\"".getthemelocation()."img/button_mediaheighttag.gif\" alt=\"".$text31[$lang]."\" title=\"".$text31[$lang]."\" />\n";
              if ($cat != "meta") echo "<img onClick=\"format_tag('mediaalttext');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media6\" src=\"".getthemelocation()."img/button_mediaalttexttag.gif\" alt=\"".$text32[$lang]."\" title=\"".$text32[$lang]."\" />\n";
              if ($cat != "meta") echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";
              if ($cat != "meta") echo "<img onClick=\"format_tag('linkhref');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media8\" src=\"".getthemelocation()."img/button_linktag.gif\" alt=\"".$text33[$lang]."\" title=\"".$text33[$lang]."\" />\n";
              if ($cat != "meta") echo "<img onClick=\"format_tag_attr('linktarget');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media9\" src=\"".getthemelocation()."img/button_linktargettag.gif\" alt=\"".$text34[$lang]."\" title=\"".$text34[$lang]."\" />\n";
              if ($cat != "meta") echo "<img onClick=\"format_tag('linktext');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media10\" border=0 src=\"".getthemelocation()."img/button_linktexttag.gif\" alt=\"".$text35[$lang]."\" title=\"".$text35[$lang]."\" />\n";
              echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";
            }
            
            if ($cat == "page" || $cat == "comp" || $cat == "inc")
            {
              echo "<img onClick=\"format_tag('components');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media12\" src=\"".getthemelocation()."img/button_compsingletag.gif\" alt=\"".$text36[$lang]."\" title=\"".$text36[$lang]."\" />\n";
              echo "<img onClick=\"format_tag('componentm');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media13\" src=\"".getthemelocation()."img/button_compmultitag.gif\" alt=\"".$text37[$lang]."\" title=\"".$text37[$lang]."\" />\n";
              echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";
            }  
            
            if ($cat == "page") 
            {
              echo "<img onClick=\"customertracking()\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media12-1\" src=\"".getthemelocation()."img/button_sessionreg.gif\" alt=\"".$text38[$lang]."\" title=\"".$text38[$lang]."\" />\n";
              echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";
            }  

            if ($cat == "page" || $cat == "comp" || $cat == "inc")
            {
              if ($cat == "page") 
              {
                echo "<img onClick=\"db_connect();\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media40\" src=\"".getthemelocation()."img/button_db_connect.gif\" alt=\"".$text51[$lang]."\" title=\"".$text51[$lang]."\" />\n";              
                echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";
              }  
              echo "<img onClick=\"workflow();\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media50\" src=\"".getthemelocation()."img/button_workflowinsert.gif\" alt=\"".$text53[$lang]."\" title=\"".$text53[$lang]."\">\n";                              
              echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";     
              echo "<img onClick=\"script();\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media60\" src=\"".getthemelocation()."img/button_script.gif\" alt=\"".$text58[$lang]."\" title=\"".$text58[$lang]."\" />\n";                              
              echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";                       
              echo "<img onClick=\"date();\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media30\" src=\"".getthemelocation()."img/button_tpldate.gif\" alt=\"".$text40[$lang]."\" title=\"".$text40[$lang]."\" />\n";
              echo "<img onClick=\"tplmedia();\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media31\" src=\"".getthemelocation()."img/button_tplmediapath.gif\" alt=\"".$text41[$lang]."\" title=\"".$text41[$lang]."\" />\n";
              echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";
              echo "<img onClick=\"include('');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media20\" src=\"".getthemelocation()."img/button_tplincludetag.gif\" alt=\"".$text42[$lang]."\" title=\"".$text42[$lang]."\" />\n";
              echo "<img onClick=\"include('php');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media21\" src=\"".getthemelocation()."img/button_phpincludetag.gif\" alt=\"".$text43[$lang]."\" title=\"".$text43[$lang]."\" />\n";
            }
            ?>
          </div>
        </div>
      </td>
    </tr>
    <tr>
      <td>
        <textarea name="contentfield" style="width:810px; height:400px;"><?php echo $contentfield; ?></textarea>
      </td>
    </tr>
  </table>
</form>

</div>

</body>
</html>
