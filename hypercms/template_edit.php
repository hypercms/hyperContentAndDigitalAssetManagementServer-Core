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
  // get charset before transformation of < and >
  $result_charset = getcharset ($site, $contentfield);  
  
  if (isset ($result_charset['charset']) && $result_charset['charset'] != "") $charset = $result_charset['charset'];
  else $charset = $mgmt_config[$site]['default_codepage'];
  
  // set highest cleaning level if not provided and not a metadata template
  if ($site != "" && isset ($mgmt_config[$site]['template_clean_level']) && $cat != "meta") $cleanlevel = $mgmt_config[$site]['template_clean_level'];
  elseif (isset ($mgmt_config['template_clean_level']) && $cat != "meta") $cleanlevel = $mgmt_config['template_clean_level'];
  else $cleanlevel = 4;

  // check template code
  $contentfield_check = scriptcode_clean_functions ($contentfield, $cleanlevel);

   // if passed
  if ($contentfield_check['result'] == true)
  {    
    // save template file
    $result_save = edittemplate ($site, $template, $cat, $user, $contentfield, $extension, $application);
    
    if ($result_save['result'] == true && $preview == "yes")
    {
      $add_onload = " hcms_openWindow('".$mgmt_config['url_path_cms']."template_view.php?site=".url_encode($site)."&cat=".$cat."&template=".url_encode($template)."', 'preview', 'scrollbars=yes,resizable=yes', ".windowwidth("object").", ".windowheight("object").");";
    }
    elseif ($result_save['result'] == false)
    {
      $show = "<span class=hcmsHeadline>".getescapedtext ($hcms_lang['template-could-not-be-saved'][$lang], $charset, $lang)."</span>";
    }
    else $add_onload = "";
  }
  // failed
  else $show = "<span class=hcmsHeadline>".getescapedtext ($hcms_lang['template-could-not-be-saved'][$lang], $charset, $lang)."</span><br />\n".getescapedtext ($hcms_lang['there-are-forbidden-functions-in-the-code'][$lang], $charset, $lang).": <span style=\"color:red;\">".$contentfield_check['found']."</span>";
}
// load template file
else
{
  $templatedata = loadfile ($mgmt_config['abs_path_template'].$site."/", $template);
  
  $extension = "";
  $application = "";
  $contentfield = "";

  // extract information
  $temp_array = getcontent ($templatedata, "<extension>");
  if (!empty ($temp_array[0])) $extension = $temp_array[0];

  $temp_array = getcontent ($templatedata, "<application>");
  if (!empty ($temp_array[0])) $application = $temp_array[0];

  $temp_array = getcontent ($templatedata, "<content>");
  if (!empty ($temp_array[0])) $contentfield = $temp_array[0];
  
  // get charset before transformation of < and >
  $result_charset = getcharset ($site, $contentfield);  
  
  if (isset ($result_charset['charset']) && $result_charset['charset'] != "") $charset = $result_charset['charset'];
  else $charset = $mgmt_config[$site]['default_codepage'];
}

// escape special characters (transform all special chararcters into their html/xml equivalents)
if ($contentfield != "")
{
  $contentfield = str_replace ("&", "&amp;", $contentfield);
  $contentfield = str_replace ("<", "&lt;", $contentfield);
  $contentfield = str_replace (">", "&gt;", $contentfield);
}

// create secure token
$token_new = createtoken ($user);

// set character set in header
if (!empty ($charset)) ini_set ('default_charset', $charset);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo $charset; ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
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
    alert(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters-in-the-content-identification-name'][$lang], $charset, $lang); ?>\n ") + addText);
    return false;
  }
  else
  {
    return true;
  }
}

function checkForm (expression)
{  
  if (!checkForm_chars(expression, "_-"))
  {
    return false;
  }   
}

function insertAtCaret (aTag, eTag)
{
  var input = document.forms['template_edit'].elements['contentfield'];
  
  input.focus();
  
  // Internet Explorer
  if (typeof document.selection != 'undefined')
  {
    // insert code
    var range = document.selection.createRange();
    var insText = range.text;
    
    // range.text = aTag + insText + eTag;
    range.text = aTag + eTag;
    
    // set cursor position
    range = document.selection.createRange();
    
    if (insText.length == 0)
    {
      range.move('character', -eTag.length);
    }
    else
    {
      // range.moveStart('character', aTag.length + insText.length + eTag.length);  
      range.moveStart('character', aTag.length + eTag.length);     
    }
    
    range.select();
  }
  // new Gecko based browsers
  else if(typeof input.selectionStart != 'undefined')
  {
    // insert code
    var start = input.selectionStart;
    var end = input.selectionEnd;
    var insText = input.value.substring(start, end);
    
    // input.value = input.value.substr(0, start) + aTag + insText + eTag + input.value.substr(end);
    input.value = input.value.substr(0, start) + aTag + eTag + input.value.substr(end);
    
    // set cursor position
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
  // other Browsers
  else
  {
    // get insert position
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
    
    // insert code
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
  
  if (format == "watermark")
  {
    format = "mediafile";
    constraint = " mediatype='watermark'";
    tagid = "Watermark";
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

function geolocation()
{
  if (eval (document.forms['template_edit'].onpublish) && document.forms['template_edit'].elements['onpublish'].checked) var onpublish = " onPublish='hidden'";  
  else var onpublish = ""; 
  
  if (eval (document.forms['template_edit'].onedit) && document.forms['template_edit'].elements['onedit'].checked) var onedit = " onEdit='hidden'";  
  else var onedit = "";
  
  code = "[hyperCMS:geolocation infotype='meta'"+onpublish+onedit+"]";
  insertAtCaret (code, '');
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

function phpscript()
{
  code = "[hyperCMS:scriptbegin\r// insert your script here\rscriptend]";
  insertAtCaret (code, '');
}

function javascript()
{
  code = "[JavaScript:scriptbegin\r// insert your script here\rscriptend]";
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
       
      hcms_showInfo ('savelayer', 0); 
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
    
    hcms_showInfo ('savelayer', 0);
    document.forms['template_edit'].submit();
    return true;
  }
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" onLoad="<?php echo $add_onload; ?>">

<!-- saving --> 
<div id="savelayer" class="hcmsLoadScreen"></div>

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
  
  <table class="hcmsTableStandard">
    <tr>
      <td class="hcmsHeadline" style="width:250px;"><?php echo getescapedtext ($pagecomp, $charset, $lang); ?> </td>
      <td><input name="template" type="text" value="<?php echo getescapedtext ($templatename, $charset, $lang); ?>" style="width:220px;" disabled="disabled" /></td>
    </tr>
    <?php if ($cat == "page" || $cat == "comp") { ?>
    <tr>
      <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['file-extension-without-dot'][$lang], $charset, $lang); ?> </td>
      <td><input name="extension" maxlength="10" type="text" value="<?php echo $extension; ?>" style="width:50px;" /></td>
    </tr>
    <tr>
      <td class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['application'][$lang], $charset, $lang); ?> </td>
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
  
  <table class="hcmsTableNarrow" style="border:1px solid #000000; width:100%; height:100%;">
  <?php
  if ($cat == "page" || $cat == "comp" || $cat == "meta" || $cat == "inc")
  {
    // meta-information checkbox
    if ($cat != "meta") $checkbox_metainfo = "&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" name=\"infotype\" value=\"meta\" /> ".getescapedtext ($hcms_lang['meta-information'][$lang], $charset, $lang);
    else $checkbox_metainfo = "";
    
    echo "
    <tr>
      <td>
        <table class=\"hcmsTableStandard\" style=\"width:100%;\">";
    if ($cat != "meta") echo "
            <tr>
              <td style=\"white-space:nowrap; width:248px; padding:2px;\">".getescapedtext ($hcms_lang['article-identification-name'][$lang], $charset, $lang)." </td>
              <td style=\"white-space:nowrap; padding:2px;\">
                <input type=\"text\" name=\"artid\" style=\"width:200px;\" />
                <input type=\"button\" class=\"hcmsButtonBlue\" name=\"art_clean\" value=\"".getescapedtext ($hcms_lang['no-article'][$lang], $charset, $lang)."\" onClick=\"document.forms['template_edit'].elements['artid'].value = '';\" />
              </td>
            </tr>";            
    echo "
            <tr>
              <td style=\"white-space:nowrap; width:248px; padding:2px;\">".getescapedtext ($hcms_lang['content-identification-name'][$lang], $charset, $lang)." </td>
              <td style=\"white-space:nowrap; padding:2px;\">
                <input type=\"text\" name=\"tagid\" style=\"width:200px;\" />
                <input type=\"button\" class=\"hcmsButtonBlue\" name=\"tag_clean\" value=\"".getescapedtext ($hcms_lang['reset'][$lang], $charset, $lang)."\" onClick=\"document.forms['template_edit'].elements['tagid'].value = '';\" />".$checkbox_metainfo."
              </td>
            </tr>";
    echo "
            <tr>
              <td style=\"white-space:nowrap; width:248px; padding:2px;\">".getescapedtext ($hcms_lang['hide-content'][$lang], $charset, $lang)." </td>
              <td style=\"white-space:nowrap; padding:2px;\">
                <input type=\"checkbox\" name=\"onpublish\" value=\"hidden\" />&nbsp;".getescapedtext ($hcms_lang['on-publish'][$lang], $charset, $lang)."&nbsp;
                <input type=\"checkbox\" name=\"onedit\" value=\"hidden\" />&nbsp;".getescapedtext ($hcms_lang['on-edit'][$lang], $charset, $lang)."
              </td>
            </tr>";
    echo "
        </table>
      </td>
    </tr>\n";
  }
  ?>
    <tr>
      <td>
        <div class="hcmsToolbar" style="width:100%;">
        
          <div class="hcmsToolbarBlock">
            <img onClick="savetemplate('');" src="<?php echo getthemelocation(); ?>img/button_save.png" class="hcmsButton hcmsButtonSizeSquare" name="save" alt="<?php echo getescapedtext ($hcms_lang['save'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['save'][$lang], $charset, $lang); ?>" />
            <?php if ($cat != "meta") { ?><img onClick="savetemplate('preview');" class="hcmsButton hcmsButtonSizeSquare" name="savepreview" src="<?php echo getthemelocation(); ?>img/button_file_preview.png" alt="<?php echo getescapedtext ($hcms_lang['save-and-preview'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['save-and-preview'][$lang], $charset, $lang); ?>" /><?php } ?>
          </div>
          
            <?php
            if ($cat == "page")
            {
              echo "
            <div class=\"hcmsToolbarBlock\">
              <img onClick=\"pagetitle();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_pagetitle.png\" alt=\"".getescapedtext ($hcms_lang['page-title'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['page-title'][$lang], $charset, $lang)."\" />
              <img onClick=\"openmetaInfo();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_pagemeta.png\" alt=\"".getescapedtext ($hcms_lang['meta-information'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['meta-information'][$lang], $charset, $lang)."\" />
            </div>";
            }
            elseif ($cat == "comp" || $cat == "inc")
            {
              echo "
            <div class=\"hcmsToolbarBlock\">
              <img onClick=\"compcontenttype();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_pagemeta.png\" alt=\"".getescapedtext ($hcms_lang['character-set'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['character-set'][$lang], $charset, $lang)."\" />
              <img onClick=\"openmetaInfo();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_pagemeta.png\" alt=\"".getescapedtext ($hcms_lang['meta-information'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['meta-information'][$lang], $charset, $lang)."\" />
            </div>";
            }

            if ($cat == "page" || $cat == "comp" || $cat == "meta" || $cat == "inc")
            {
              echo "
            <div class=\"hcmsToolbarBlock\">
                <img onClick=\"geolocation();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_marker.png\" alt=\"".getescapedtext ($hcms_lang['geo-location'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['geo-location'][$lang], $charset, $lang)."\" />";
              if ($cat != "meta") echo "
                <img onClick=\"openLanguageInfo();\" class=\"hcmsButton hcmsButtonSizeSquare\" border=0 src=\"".getthemelocation()."img/button_language.png\" alt=\"".getescapedtext ($hcms_lang['language'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['language'][$lang], $charset, $lang)."\" />";
              echo "
            </div>
            
            <div class=\"hcmsToolbarBlock\">
              <img onClick=\"openConstraints ();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_textu.png\" alt=\"".getescapedtext ($hcms_lang['unformatted-text'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['unformatted-text'][$lang], $charset, $lang)."\" />
              <img onClick=\"format_tag('textf');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_textf.png\" alt=\"".getescapedtext ($hcms_lang['formatted-text'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['formatted-text'][$lang], $charset, $lang)."\" />
              <img onClick=\"format_tag_attr('textl')\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_textl.png\" alt=\"".getescapedtext ($hcms_lang['text-options'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['text-options'][$lang], $charset, $lang)."\" />
              <img onClick=\"format_tag('textc')\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_textc.png\" alt=\"".getescapedtext ($hcms_lang['checkbox'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['checkbox'][$lang], $charset, $lang)."\" />
              <img onClick=\"format_tag('textd')\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_datepicker.png\" alt=\"".getescapedtext ($hcms_lang['date'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['date'][$lang], $charset, $lang)."\" />
              <img onClick=\"format_tag('textk');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_keyword.png\" alt=\"".getescapedtext ($hcms_lang['keywords'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['keywords'][$lang], $charset, $lang)."\" />";
              if ($cat == "meta") echo "
              <img onClick=\"format_tag('commentu');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_commentu.png\" alt=\"".getescapedtext ($hcms_lang['unformatted-comment'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['unformatted-comment'][$lang], $charset, $lang)."\" />
              <img onClick=\"format_tag('commentf');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_commentf.png\" alt=\"".getescapedtext ($hcms_lang['formatted-comment'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['formatted-comment'][$lang], $charset, $lang)."\" />";
             echo "
           </div>";
           
              if ($cat == "meta") echo "
              <div class=\"hcmsToolbarBlock\">
                <img onClick=\"format_tag('watermark');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_media.png\" alt=\"".getescapedtext ($hcms_lang['watermark-options-for-images'][$lang].", ".$hcms_lang['watermark-options-for-vidoes'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['watermark-options-for-images'][$lang].", ".$hcms_lang['watermark-options-for-vidoes'][$lang], $charset, $lang)."\" />
              </div>";
              else echo "
            <div class=\"hcmsToolbarBlock\">
              <img onClick=\"openmediaType();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_media.png\" alt=\"".getescapedtext ($hcms_lang['media-file'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['media-file'][$lang], $charset, $lang)."\" />
              <img onClick=\"format_tag('mediaalign');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_mediaalign.png\" alt=\"".getescapedtext ($hcms_lang['media-alignment'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['media-alignment'][$lang], $charset, $lang)."\" />
              <img onClick=\"format_tag('mediawidth');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_mediawidth.png\" alt=\"".getescapedtext ($hcms_lang['media-width'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['media-width'][$lang], $charset, $lang)."\" />
              <img onClick=\"format_tag('mediaheight');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_mediaheight.png\" alt=\"".getescapedtext ($hcms_lang['media-height'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['media-height'][$lang], $charset, $lang)."\" />
             <img onClick=\"format_tag('mediaalttext');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_mediaalttext.png\" alt=\"".getescapedtext ($hcms_lang['media-alternative-text'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['media-alternative-text'][$lang], $charset, $lang)."\" />
            </div>
            <div class=\"hcmsToolbarBlock\">
              <img onClick=\"format_tag('linkhref');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_link.png\" alt=\"".getescapedtext ($hcms_lang['link'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['link'][$lang], $charset, $lang)."\" />
              <img onClick=\"format_tag_attr('linktarget');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_linktarget.png\" alt=\"".getescapedtext ($hcms_lang['link-target'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['link-target'][$lang], $charset, $lang)."\" />
              <img onClick=\"format_tag('linktext');\" class=\"hcmsButton hcmsButtonSizeSquare\" border=0 src=\"".getthemelocation()."img/button_linktext.png\" alt=\"".getescapedtext ($hcms_lang['link-text'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['link-text'][$lang], $charset, $lang)."\" />
            </div>";
            }
            
            if ($cat == "page" || $cat == "comp" || $cat == "meta" || $cat == "inc")
            {
              echo "
            <div class=\"hcmsToolbarBlock\">
              <img onClick=\"format_tag('components');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_compsingle.png\" alt=\"".getescapedtext ($hcms_lang['single-component'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['single-component'][$lang], $charset, $lang)."\" />
              <img onClick=\"format_tag('componentm');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_compmulti.png\" alt=\"".getescapedtext ($hcms_lang['multiple-component'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['multiple-component'][$lang], $charset, $lang)."\" />
            </div>";
            }  
            
            if ($cat == "page") 
            {
              echo "
            <div class=\"hcmsToolbarBlock\">
              <img onClick=\"customertracking()\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_sessionreg.png\" alt=\"".getescapedtext ($hcms_lang['customer-tracking'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['customer-tracking'][$lang], $charset, $lang)."\" />
            </div>";
            }  

            if ($cat == "page" || $cat == "comp" || $cat == "meta" || $cat == "inc")
            {
              echo "
            <div class=\"hcmsToolbarBlock\">
              <img onClick=\"db_connect();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_db_connect.png\" alt=\"".getescapedtext ($hcms_lang['database-connectivity'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['database-connectivity'][$lang], $charset, $lang)."\" />             
            </div>
            <div class=\"hcmsToolbarBlock\">
             <img onClick=\"workflow();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_workflowinsert.png\" alt=\"".getescapedtext ($hcms_lang['workflow'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['workflow'][$lang], $charset, $lang)."\">                           
            </div>
            <div class=\"hcmsToolbarBlock\">    
              <img onClick=\"phpscript();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_script.png\" alt=\"".getescapedtext ("hyperCMS-Script", $charset, $lang)."\" title=\"".getescapedtext ("hyperCMS-Script", $charset, $lang)."\" />
              <img onClick=\"javascript();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/admin.png\" alt=\"".getescapedtext ("JavaScript (form-views)", $charset, $lang)."\" title=\"".getescapedtext ("JavaScript (form-views)", $charset, $lang)."\" />                          
            </div>
            <div class=\"hcmsToolbarBlock\">                     
              <img onClick=\"date();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_tpldate.png\" alt=\"".getescapedtext ($hcms_lang['insert-date'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['insert-date'][$lang], $charset, $lang)."\" />
              <img onClick=\"tplmedia();\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_tplmediapath.png\" alt=\"".getescapedtext ($hcms_lang['insert-path-to-template-media'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['insert-path-to-template-media'][$lang], $charset, $lang)."\" />
            </div>
            <div class=\"hcmsToolbarBlock\">
              <img onClick=\"include('');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_tplinclude.png\" alt=\"".getescapedtext ($hcms_lang['include-template-component'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['include-template-component'][$lang], $charset, $lang)."\" />
              <img onClick=\"include('php');\" class=\"hcmsButton hcmsButtonSizeSquare\" src=\"".getthemelocation()."img/button_phpinclude.png\" alt=\"".getescapedtext ($hcms_lang['inclusion-of-a-file'][$lang], $charset, $lang)."\" title=\"".getescapedtext ($hcms_lang['inclusion-of-a-file'][$lang], $charset, $lang)."\" />
            </div>";
            }
            ?>
          
          <img onClick="openHelp();" name="pic_obj_help" src="<?php echo getthemelocation(); ?>img/button_help.png" class="hcmsButton hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['help'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['help'][$lang], $charset, $lang); ?>" />
        </div>
      </td>
    </tr>
    <tr>
      <td style="text-align:left;">
        <textarea name="contentfield" style="width:100%; min-height:500px; -webkit-box-sizing:border-box; -moz-box-sizing:border-box; box-sizing:border-box;"><?php echo $contentfield; ?></textarea>
      </td>
    </tr>
  </table>
</form>

</div>

</body>
</html>
