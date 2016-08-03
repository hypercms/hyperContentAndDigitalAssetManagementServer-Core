<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
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
  $pagecomp = getescapedtext ($hcms_lang['template-component'][$lang]);
  $cat = "inc";
}
elseif (strpos ($template, ".page.tpl") > 0)
{
  $templatename = substr ($template, 0, strpos ($template, ".page.tpl"));
  $pagecomp = getescapedtext ($hcms_lang['page-template'][$lang]);
  $cat = "page";
}
elseif (strpos ($template, ".comp.tpl") > 0)
{
  $templatename = substr ($template, 0, strpos ($template, ".comp.tpl"));
  $pagecomp = getescapedtext ($hcms_lang['component-template'][$lang]);
  $cat = "comp";
}
elseif (strpos ($template, ".meta.tpl") > 0)
{
  $templatename = substr ($template, 0, strpos ($template, ".meta.tpl"));
  $pagecomp = getescapedtext ($hcms_lang['meta-data-template'][$lang]);
  $cat = "meta";
}

// save template file if save button was pressed
if (checkglobalpermission ($site, 'template') && checkglobalpermission ($site, 'tpledit') && $save == "yes" && checktoken ($token, $user))
{
  // set highest cleaning level is not provided or meta data template
  if (!isset ($mgmt_config['template_clean_level']) || $cat == "meta") $mgmt_config['template_clean_level'] = 3;
  
  // check code
  $contentfield_check = scriptcode_clean_functions ($contentfield, $mgmt_config['template_clean_level']);

   // save pers file
  if ($contentfield_check['result'] == true)
  {    
    // save template file
    $result_save = edittemplate ($site, $template, $cat, $user, $contentfield, $extension, $application);
    
    if ($result_save['result'] == true && $preview == "yes")
    {
      $add_onload = " hcms_openWindow('template_view.php?site=".url_encode($site)."&cat=".$cat."&template=".url_encode($template)."', 'preview', 'scrollbars=yes,resizable=yes', 800, 600);";
    }
    else $add_onload = "";
  }
  else $show = "<span class=hcmsHeadline>".getescapedtext ($hcms_lang['template-could-not-be-saved'][$lang], $charset, $lang)."</span><br />\n".getescapedtext ($hcms_lang['there-are-forbidden-functions-in-the-code'][$lang], $charset, $lang).": <span style=\"color:red;\">".$contentfield_check['found']."</span>";
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
}

// get charset before transformation of < and >
$result_charset = getcharset ($site, $contentfield);  

if (isset ($result_charset['charset']) && $result_charset['charset'] != "") $charset = $result_charset['charset'];
else $charset = $mgmt_config[$site]['default_codepage'];

// escape special characters (transform all special chararcters into their html/xml equivalents)
if ($contentfield != "")
{
  $contentfield = str_replace ("&", "&amp;", $contentfield);
  $contentfield = str_replace ("<", "&lt;", $contentfield);
  $contentfield = str_replace (">", "&gt;", $contentfield);
}

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo $charset; ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
function openHelp()
{
  hcms_openWindow('template_help.php?site=<?php echo $site; ?>', 'help', 'resizable=yes,scrollbars=yes', 750, 680);
}

function openmetaInfo()
{  
  hcms_openWindow('template_edit_metainfo.php?site=<?php echo $site; ?>', 'constraint', 'scrollbars=no,resizable=no', 450, 150);
}

function openLanguageInfo()
{  
  hcms_openWindow('template_edit_language.php?site=<?php echo $site; ?>', 'language', 'scrollbars=no,resizable=no', 450, 150);
}

function openConstraints()
{  
  hcms_openWindow('template_edit_constraints.php?site=<?php echo $site; ?>', 'constraint', 'scrollbars=no,resizable=no', 450, 250);
}

function openmediaType()
{  
  hcms_openWindow('template_edit_mediatype.php?site=<?php echo $site; ?>', 'constraint', 'scrollbars=no,resizable=no', 350, 150);
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
		alert(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters-in-the-content-identification-name'][$lang], $charset, $lang); ?>: ")+addText);
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

function insertAtCaret (aTag, eTag)
{
  var input = document.forms['template_edit'].elements['contentfield'];
  
  input.focus();
  
  /* Internet Explorer */
  if (typeof document.selection != 'undefined')
  {
    /* insert code */
    var range = document.selection.createRange();
    var insText = range.text;
    
    //range.text = aTag + insText + eTag;
    range.text = aTag + eTag;
    
    /* set cursor position */
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
  /* new Gecko based browsers */
  else if(typeof input.selectionStart != 'undefined')
  {
    /* insert code */
    var start = input.selectionStart;
    var end = input.selectionEnd;
    var insText = input.value.substring(start, end);
    
    //input.value = input.value.substr(0, start) + aTag + insText + eTag + input.value.substr(end);
    input.value = input.value.substr(0, start) + aTag + eTag + input.value.substr(end);
    
    /* set cursor position */
    var pos;
    
    if (insText.length == 0)
    {
      pos = start + aTag.length;
    }
    else
    {
      pos = start + aTag.length + eTag.length;
    }
    
    input.selectionStart = pos;
    input.selectionEnd = pos;
  }
  /* other Browsers */
  else
  {
    /* get insert position */
    var pos;
    var re = new RegExp('^[0-9]{0,3}$');
    
    while(!re.test(pos))
    {
      pos = prompt("Insert at position (0.." + input.value.length + "):", "0");
    }
    
    if(pos > input.value.length)
    {
      pos = input.value.length;
    }
    
    /* insert code */
    var insText = prompt("Please input the text to be formatted:");
    input.value = input.value.substr(0, pos) + aTag + insText + eTag + input.value.substr(pos);
  }
}

function format_tag (format)
{
  if (eval (document.forms['template_edit'].elements['artid'])) var artid = document.forms['template_edit'].elements['artid'].value;
  else var artid = "";
  
  if (eval (document.forms['template_edit'].elements['tagid'])) var tagid = document.forms['template_edit'].elements['tagid'].value;
  else var tagid = "";
  
  if (eval (document.forms['template_edit'].elements['onpublish']) && document.forms['template_edit'].elements['onpublish'].checked) var onpublish = " onPublish='hidden'";  
  else var onpublish = ""; 
  
  if (eval (document.forms['template_edit'].elements['onedit']) && document.forms['template_edit'].elements['onedit'].checked) var onedit = " onEdit='hidden'";  
  else var onedit = "";
  
  if (eval (document.forms['template_edit'].elements['infotype']) && document.forms['template_edit'].elements['infotype'].checked) var infotype = " infotype='meta'";
  else var infotype = "";
    
  var constraint = "";
  
  if (format == "textu" && document.forms['template_edit'].elements['constraints'].value != "") 
  {
    constraint = " constraint='" + document.forms['template_edit'].elements['constraints'].value + "'";
  }
  
  if (format == "textc") constraint = " value='" + prompt(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['enter-value-for-checkbox'][$lang], $charset, $lang); ?>"), "") + "'";
  
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
      else alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-fill-in-a-content-identification-name'][$lang], $charset, $lang); ?>"));
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
      else alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-fill-in-a-content-identification-name'][$lang], $charset, $lang); ?>"));
    } 
  }
}

function format_tag_attr(format)
{
  if (eval (document.forms['template_edit'].artid)) var artid = document.forms['template_edit'].elements['artid'].value;
  else var artid = "";
  
  if (eval (document.forms['template_edit'].tagid)) var tagid = document.forms['template_edit'].elements['tagid'].value;
  else var tagid = "";
  
  if (eval (document.forms['template_edit'].onpublish) && document.forms['template_edit'].elements['onpublish'].checked) var onpublish = " onPublish='hidden'";  
  else var onpublish = ""; 
  
  if (eval (document.forms['template_edit'].onedit) && document.forms['template_edit'].elements['onedit'].checked) var onedit = " onEdit='hidden'";  
  else var onedit = "";
  
  if (eval (document.forms['template_edit'].infotype) && document.forms['template_edit'].elements['infotype'].checked) var infotype = " infotype='meta'";
  else var infotype = "";  
  
  if (tagid == "" || tagid == null)
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-fill-in-a-content-identification-name'][$lang], $charset, $lang); ?>"));
  }
  else
  {    
    if (format == "textl") var list = prompt(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-enter-the-text-options-eg'][$lang], $charset, $lang); ?>"), "");
    else if (format == "linktarget") var list = prompt(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['enter-frame-names-for-target-specification-eg'][$lang], $charset, $lang); ?>"), "");
    else var list = "";

    if (list != "")
    {
      list = list.replace (';', '|');
      list = list.replace ('&', '&amp;');
      list = list.replace ('<', '&lt;');
      list = list.replace ('>', '&gt;');
    }  
  
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
        else alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-fill-in-a-content-identification-name'][$lang], $charset, $lang); ?>"));
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
        else alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-fill-in-a-content-identification-name'][$lang], $charset, $lang); ?>"));
      } 
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
  var filename = prompt(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-enter-the-file-name-of-the-database-connectivity-eg'][$lang], $charset, $lang); ?>"), "");
  
  if (filename != null) 
  {
    code = "[hyperCMS:dbconnect file='"+filename+"']\r\n";
    document.forms['template_edit'].contentfield.value = code + document.forms['template_edit'].elements['contentfield'].value;
  }
}

function workflow()
{
  var filename = prompt(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-enter-the-name-of-the-master-workflow-eg'][$lang], $charset, $lang); ?>"), "");
  
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
  
  list = language_sessionvalues.replace (';', '|');
  
  code = "[hyperCMS:language name='"+language_sessionvar+"' list='"+list+"']";
  insertAtCaret (code, '');
}

function compcontenttype()
{
  var charset = prompt(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['define-character-set'][$lang], $charset, $lang); ?>"), "<?php echo $mgmt_config[$site]['default_codepage']; ?>");
  
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
  if (format == "php") {filename = prompt(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['path-to-component-to-be-included-with-ext'][$lang], $charset, $lang); ?>"), "");}
  if (format == "") {filename = prompt(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['name-of-template-component-to-be-included-without-ext-no-path-required'][$lang], $charset, $lang); ?>"), "");}

  if (filename != null && format == "php") {code = "[hyperCMS:fileinclude file='"+filename+"']";}
  if (filename != null && format == "") {code = "[hyperCMS:tplinclude file='"+filename+".inc.tpl']";}

  if (filename != null) insertAtCaret (code, '');
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
      alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-define-a-file-extension'][$lang], $charset, $lang); ?>"));
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
      <td width="250" class="hcmsHeadline"><?php echo getescapedtext ($pagecomp, $charset, $lang); ?>:</td>
      <td><input name="template" type="text" value="<?php echo getescapedtext ($templatename, $charset, $lang); ?>" style="width:220px;" disabled="disabled" /></td>
    </tr>
    <?php if ($cat == "page" || $cat == "comp") { ?>
    <tr>
      <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['file-extension-without-dot'][$lang], $charset, $lang); ?>:</td>
      <td><input name="extension" maxlength="10" type="text" value="<?php echo $extension; ?>" style="width:50px;" /></td>
    </tr>
    <tr>
      <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['application'][$lang], $charset, $lang); ?>:</td>
      <td>
      <select name="application">
        <option value="asp"<?php if ($application == "asp") echo " selected=\"selected\""; ?>>Active Server Pages (ASP)</option>
        <option value="xml"<?php if ($application == "xml") echo " selected=\"selected\""; ?>>Extensible Markup Language (XML) or Text</option>
        <option value="htm"<?php if ($application == "htm") echo " selected=\"selected\""; ?>>Hypertext Markup Language (HTML)</option>
        <option value="jsp"<?php if ($application == "jsp") echo " selected=\"selected\""; ?>>Java Server Pages (JSP)</option>
        <option value="php"<?php if ($application == "php") echo " selected=\"selected\""; ?>>PHP Hypertext Preprocessor (PHP)</option>
        <?php if ($cat == "comp") { ?>
        <option value="generator"<?php if ($application == "generator") echo " selected=\"selected\""; ?>>Document Generator</option>
        <?php } ?>
      </select>
     </td>
    </tr>
    <?php } elseif ($cat == "meta") { ?>
    <input type="hidden" name="application" value="media" />
    <?php } ?>
  </table>
  <br />
  
  <table border="0" cellspacing="0" cellpadding="0" style="border: 1px solid #000000; width:820px;">
  <?php
  if ($cat == "page" || $cat == "comp" || $cat == "meta" || $cat == "inc")
  {
    // meta-information checkbox
    if ($cat != "meta") $checkbox_metainfo = "<font color=\"#000000\">&nbsp;&nbsp;&nbsp;</font><input type=\"checkbox\" name=\"infotype\" value=\"meta\" /> ".getescapedtext ($hcms_lang['meta-information'][$lang], $charset, $lang);
    else $checkbox_metainfo = "";
    
    echo "<tr>
      <td>
        <table border=\"0\" cellspacing=\"2\" cellpadding=\"0\" width=\"100%\">\n";
    if ($cat != "meta") echo "<tr>
              <td width=\"248\" nowrap=\"nowrap\">".getescapedtext ($hcms_lang['article-identification-name'][$lang], $charset, $lang).":</td>
              <td nowrap=\"nowrap\">
                <input type=\"text\" name=\"artid\" style=\"width:200px;\" />
                <input type=\"button\" class=\"hcmsButtonBlue\" name=\"art_clean\" value=\"".getescapedtext ($hcms_lang['no-article'][$lang], $charset, $lang)."\" onClick=\"document.forms['template_edit'].elements['artid'].value = '';\" />
              </td>
            </tr>\n";            
    echo "<tr>
              <td nowrap=\"nowrap\">".getescapedtext ($hcms_lang['content-identification-name'][$lang], $charset, $lang).":</td>
              <td nowrap=\"nowrap\">
                <input type=\"text\" name=\"tagid\" style=\"width:200px;\" />
                <input type=\"button\" class=\"hcmsButtonBlue\" name=\"tag_clean\" value=\"".getescapedtext ($hcms_lang['reset'][$lang], $charset, $lang)."\" onClick=\"document.forms['template_edit'].elements['tagid'].value = '';\" />".$checkbox_metainfo."
              </td>
            </tr>\n";
    echo "<tr>
              <td wnowrap=\"nowrap\">".getescapedtext ($hcms_lang['hide-content'][$lang], $charset, $lang).":</td>
              <td nowrap=\"nowrap\">
                <input type=\"checkbox\" name=\"onpublish\" value=\"hidden\" />&nbsp;".getescapedtext ($hcms_lang['on-publish'][$lang], $charset, $lang)."&nbsp;
                <input type=\"checkbox\" name=\"onedit\" value=\"hidden\" />&nbsp;".getescapedtext ($hcms_lang['on-edit'][$lang], $charset, $lang)."
              </td>
            </tr>\n";
    echo "</table>
      </td>
    </tr>\n";
  }
  ?>
    <tr>
      <td>
        <div class="hcmsToolbar" style="width:816px;">
          <div class="hcmsToolbarBlock">
            <img onClick="savetemplate('');" src="<?php echo getthemelocation(); ?>img/button_save.gif" class="hcmsButton hcmsButtonSizeSquare" name="save" alt="<?php echo getescapedtext ($hcms_lang['save'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['save'][$lang], $charset, $lang); ?>" />
            <?php if ($cat != "meta") { ?><img onClick="savetemplate('preview');" class="hcmsButton hcmsButtonSizeSquare" name="savepreview" src="<?php echo getthemelocation(); ?>img/button_preview.gif" alt="<?php echo getescapedtext ($hcms_lang['save-and-preview'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['save-and-preview'][$lang], $charset, $lang); ?>" /><?php } ?>
            <?php
            if ($cat == "page")
            {
              echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";
              echo "<img onClick=\"pagetitle();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_pagetitletag.gif\" alt=\"".getescapedtext ($hcms_lang['page-title'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['page-title'][$lang], $charset, $lang)."\" />\n";
              echo "<img onClick=\"openmetaInfo();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_pagemetatag.gif\" alt=\"".getescapedtext ($hcms_lang['meta-information'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['meta-information'][$lang], $charset, $lang)."\" />\n";
            }
            elseif ($cat == "comp" || $cat == "inc")
            {
              echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";
              echo "<img onClick=\"compcontenttype();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_pagemetatag.gif\" alt=\"".getescapedtext ($hcms_lang['character-set'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['character-set'][$lang], $charset, $lang)."\" />\n";
              echo "<img onClick=\"openmetaInfo();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_pagemetatag.gif\" alt=\"".getescapedtext ($hcms_lang['meta-information'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['meta-information'][$lang], $charset, $lang)."\" />\n";
            }

            if ($cat == "page" || $cat == "comp" || $cat == "meta" || $cat == "inc")
            {
              if ($cat != "meta") echo "<img onClick=\"openLanguageInfo();\" class=\"hcmsButton hcmsButtonSizeSquare\" border=0 src=\"".getthemelocation()."img/button_languagetag.gif\" alt=\"".getescapedtext ($hcms_lang['language'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['language'][$lang], $charset, $lang)."\" />\n";
              echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";
              echo "<img onClick=\"openConstraints ();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_texttag.gif\" alt=\"".getescapedtext ($hcms_lang['unformatted-text'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['unformatted-text'][$lang], $charset, $lang)."\" />\n";
              echo "<img onClick=\"format_tag('textf');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_ftexttag.gif\" alt=\"".getescapedtext ($hcms_lang['formatted-text'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['formatted-text'][$lang], $charset, $lang)."\" />\n";
              echo "<img onClick=\"format_tag_attr('textl')\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_ltexttag.gif\" alt=\"".getescapedtext ($hcms_lang['text-options'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['text-options'][$lang], $charset, $lang)."\" />\n";
              echo "<img onClick=\"format_tag('textc')\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_ctexttag.gif\" alt=\"".getescapedtext ($hcms_lang['checkbox'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['checkbox'][$lang], $charset, $lang)."\" />\n";
              echo "<img onClick=\"format_tag('textd')\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_datepicker.gif\" alt=\"".getescapedtext ($hcms_lang['date'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['date'][$lang], $charset, $lang)."\" />\n";
              if ($cat == "meta") echo "<img onClick=\"format_tag('textk');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_keywordtag.gif\" alt=\"".getescapedtext ($hcms_lang['keywords'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['keywords'][$lang], $charset, $lang)."\" />\n";
              if ($cat == "meta") echo "<img onClick=\"format_tag('commentu');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_commentutag.gif\" alt=\"".getescapedtext ($hcms_lang['unformatted-comment'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['unformatted-comment'][$lang], $charset, $lang)."\" />\n";
              if ($cat == "meta") echo "<img onClick=\"format_tag('commentf');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_commentftag.gif\" alt=\"".getescapedtext ($hcms_lang['formatted-comment'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['formatted-comment'][$lang], $charset, $lang)."\" />\n";
              if ($cat != "meta") echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";
              if ($cat != "meta") echo "<img onClick=\"openmediaType();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_mediatag.gif\" alt=\"".getescapedtext ($hcms_lang['media-file'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['media-file'][$lang], $charset, $lang)."\" />\n";
              if ($cat != "meta") echo "<img onClick=\"format_tag('mediaalign');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_mediaaligntag.gif\" alt=\"".getescapedtext ($hcms_lang['media-alignment'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['media-alignment'][$lang], $charset, $lang)."\" />\n";
              if ($cat != "meta") echo "<img onClick=\"format_tag('mediawidth');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_mediawidthtag.gif\" alt=\"".getescapedtext ($hcms_lang['media-width'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['media-width'][$lang], $charset, $lang)."\" />\n";
              if ($cat != "meta") echo "<img onClick=\"format_tag('mediaheight');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_mediaheighttag.gif\" alt=\"".getescapedtext ($hcms_lang['media-height'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['media-height'][$lang], $charset, $lang)."\" />\n";
              if ($cat != "meta") echo "<img onClick=\"format_tag('mediaalttext');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_mediaalttexttag.gif\" alt=\"".getescapedtext ($hcms_lang['media-alternative-text'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['media-alternative-text'][$lang], $charset, $lang)."\" />\n";
              if ($cat != "meta") echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";
              if ($cat != "meta") echo "<img onClick=\"format_tag('linkhref');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_linktag.gif\" alt=\"".getescapedtext ($hcms_lang['link'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['link'][$lang], $charset, $lang)."\" />\n";
              if ($cat != "meta") echo "<img onClick=\"format_tag_attr('linktarget');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_linktargettag.gif\" alt=\"".getescapedtext ($hcms_lang['link-target'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['link-target'][$lang], $charset, $lang)."\" />\n";
              if ($cat != "meta") echo "<img onClick=\"format_tag('linktext');\" class=\"hcmsButton hcmsButtonSizeSquare\" border=0 src=\"".getthemelocation()."img/button_linktexttag.gif\" alt=\"".getescapedtext ($hcms_lang['link-text'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['link-text'][$lang], $charset, $lang)."\" />\n";
              echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";
            }
            
            if ($cat == "page" || $cat == "comp" || $cat == "meta" || $cat == "inc")
            {
              echo "<img onClick=\"format_tag('components');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_compsingletag.gif\" alt=\"".getescapedtext ($hcms_lang['single-component'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['single-component'][$lang], $charset, $lang)."\" />\n";
              echo "<img onClick=\"format_tag('componentm');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_compmultitag.gif\" alt=\"".getescapedtext ($hcms_lang['multiple-component'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['multiple-component'][$lang], $charset, $lang)."\" />\n";
              echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";
            }  
            
            if ($cat == "page") 
            {
              echo "<img onClick=\"customertracking()\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_sessionreg.gif\" alt=\"".getescapedtext ($hcms_lang['customer-tracking'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['customer-tracking'][$lang], $charset, $lang)."\" />\n";
              echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";
            }  

            if ($cat == "page" || $cat == "comp" || $cat == "meta" || $cat == "inc")
            {
              echo "<img onClick=\"db_connect();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_db_connect.gif\" alt=\"".getescapedtext ($hcms_lang['database-connectivity'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['database-connectivity'][$lang], $charset, $lang)."\" />\n";              
              echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";
              echo "<img onClick=\"workflow();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_workflowinsert.gif\" alt=\"".getescapedtext ($hcms_lang['workflow'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['workflow'][$lang], $charset, $lang)."\">\n";                              
              echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";     
              echo "<img onClick=\"script();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_script.gif\" alt=\"".getescapedtext ($hcms_lang['script'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['script'][$lang], $charset, $lang)."\" />\n";                              
              echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";                       
              echo "<img onClick=\"date();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_tpldate.gif\" alt=\"".getescapedtext ($hcms_lang['insert-date'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['insert-date'][$lang], $charset, $lang)."\" />\n";
              echo "<img onClick=\"tplmedia();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_tplmediapath.gif\" alt=\"".getescapedtext ($hcms_lang['insert-path-to-template-media'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['insert-path-to-template-media'][$lang], $charset, $lang)."\" />\n";
              echo "</div>\n<div class=\"hcmsToolbarBlock\">\n";
              echo "<img onClick=\"include('');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_tplincludetag.gif\" alt=\"".getescapedtext ($hcms_lang['include-template-component'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['include-template-component'][$lang], $charset, $lang)."\" />\n";
              echo "<img onClick=\"include('php');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_phpincludetag.gif\" alt=\"".getescapedtext ($hcms_lang['inclusion-of-a-file'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['inclusion-of-a-file'][$lang], $charset, $lang)."\" />\n";
            }
            ?>
          </div>
          <a href=# onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('pic_obj_help','','<?php echo getthemelocation(); ?>img/button_help_over.gif',1)" onClick="openHelp();"><img name="pic_obj_help" src="<?php echo getthemelocation(); ?>img/button_help.gif" class="hcmsButtonBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['help'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['help'][$lang], $charset, $lang); ?>" /></a>
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
