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


// input parameter
$contenttype = getrequest_esc ("contenttype");
$view = getrequest_esc ("view");
$savetype = getrequest_esc ("savetype");
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$contenttype = getrequest_esc ("contenttype");
$db_connect = getrequest_esc ("db_connect", "objectname");
$tagname = getrequest_esc ("tagname", "objectname");
$id = getrequest_esc ("id", "objectname", "", true);
$label = getrequest_esc ("label");   
$linkhref = getrequest_esc ("linkhref");
$linktext = getrequest_esc ("linktext");
$linktarget = getrequest_esc ("linktarget");
$targetlist = getrequest_esc ("targetlist");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
// load publication configuration for live view
if (valid_publicationname ($site)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini"); 

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);  
if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// correct linkhref
if (strpos ("_".$linkhref, "%page%") < 1)
{
  if (file_exists (deconvertpath ("%page%".$linkhref, "file"))) $linkhref = "%page%".$linkhref;
}

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// load object file and get container
$objectdata = loadfile ($location, $page);
$contentfile = getfilename ($objectdata, "content");

// read content from content container if DB Connect is not used
if (empty ($db_connect))
{
  // load container
  $contentdata = loadcontainer ($contentfile, "work", "sys");

  if (!empty ($contentdata))
  {
    // get the media object information of the content container
    if (!empty ($id))
    {
      $linknode = selectcontent ($contentdata, "<link>", "<link_id>", $id);

      if (!empty ($linknode[0]))
      {
        $temp_array = getcontent ($linknode[0], "<linkhref>");
        if (!empty ($temp_array[0])) $linkhref = $temp_array[0];
        
        $temp_array = getcontent ($linknode[0], "<linktarget>");
        if (!empty ($temp_array[0])) $linktarget = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $temp_array[0]);
        
        $temp_array = getcontent ($linknode[0], "<linktext>");
        if (!empty ($temp_array[0])) $linktext = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $temp_array[0]);
      }  
    }
  }
}

// remove &amp; from specific variables
$variables = array ('linkhref', 'linktext');

foreach ($variables as $variable)
{
  $$variable = str_replace ("&amp;", "&", $$variable);
}

// add %page% if not provided
if (strpos ("_".$linkhref, "%page%/") == 0)
{
  if (is_file (deconvertpath ("%page%".$linkhref, "file"))) $linkhref = "%page%".$linkhref;
  elseif (is_file (deconvertpath ("%page%/".$linkhref, "file"))) $linkhref = "%page%/".$linkhref;
}

// get file info
$file_info = getfileinfo ($site, $location.$page, $cat);

// article prefix
if (substr_count ($tagname, "art") == 1) $art = "art";
else $art = "";

// set content-type if not set
if (empty ($contenttype))
{
  $contenttype = "text/html; charset=".$mgmt_config[$site]['default_codepage'];
  $charset = $mgmt_config[$site]['default_codepage'];
}
else
{
  // get character set from content-type
  $charset_array = getcharset ($site, $contenttype); 

  // set character set if not set
  if (!empty ($charset_array['charset'])) $charset = $charset_array['charset'];
  else $charset = $mgmt_config[$site]['default_codepage'];
}

// create secure token
$token = createtoken ($user);

if ($label == "") $label = $id;

// set character set in header
if (!empty ($charset)) header ('Content-Type: text/html; charset='.$charset);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo $charset; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
function replace (string,text,by)
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

function correctnames ()
{
  if (document.forms['link'].elements['linkhref']) document.forms['link'].elements['linkhref'].name = "<?php echo $art; ?>linkhref[<?php echo $id; ?>]";
  if (document.forms['link'].elements['linktarget']) document.forms['link'].elements['linktarget'].name = "<?php echo $art; ?>linktarget[<?php echo $id; ?>]";
  if (document.forms['link'].elements['linktext']) document.forms['link'].elements['linktext'].name = "<?php echo $art; ?>linktext[<?php echo $id; ?>]";
  return true;
}

function geturl (type)
{
  if (document.forms['link'].elements['link_name'] && document.forms['link'].elements['linkhref'] && document.forms['link'].elements['link_name'].value != "")
  {
    var theURL = '';
    
    if (type == 'preview')
    {
      if (document.link.elements['link_name'].value.indexOf('://') > 0)
      {
        return theURL = document.forms['link'].elements['link_name'].value;
      }
      else if (document.link.elements['linkhref'].value.indexOf('%page%') != -1)
      {
        return theURL = replace (document.forms['link'].elements['linkhref'].value, '<?php echo "%page%/".$site."/"; ?>', '<?php echo $mgmt_config[$site]['url_path_page']; ?>');
      }
      else
      {
        return "";
      }
    }
    else if (type == 'cmsview')  
    {
      theURL = document.link.elements['linkhref'].value;

      if (theURL.indexOf('://') == -1)
      {      
        position1 = theURL.indexOf("/");
        position2 = theURL.lastIndexOf("/");
        
        location_page = theURL.substring (position1, position2+1);
        location_page = replace (location_page, '<?php echo "/".$site."/"; ?>', '<?php echo "%page%/".$site."/"; ?>');
        
        location_site = theURL.substring (position1+1, theURL.length);              
        location_site = location_site.substring(0, location_site.indexOf('/'));
        
        page = theURL.substring (position2 + 1, theURL.length);
        if (page.indexOf('?') > 0) page = page.substring (0, page.indexOf('?'));
        if (page.indexOf('#') > 0) page = page.substring (0, page.indexOf('#'));
 
        return theURL = '<?php echo $mgmt_config['url_path_cms']; ?>frameset_content.php?ctrlreload=yes&cat=page&site=' + encodeURIComponent(location_site) + '&location=' + encodeURIComponent(location_page) + '&page=' + encodeURIComponent(page) + '&user=<?php echo url_encode($user); ?>';
      }
      else
      {
        alert (hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['this-is-an-external-page-link'][$lang], $charset, $lang); ?>'));
        return "";
      }
    }
    else return "";
  }
  else return "";
}

function openBrWindowLink (winName, features, type)
{
  if (document.forms['link'].elements['link_name'] && document.forms['link'].elements['linkhref'] && document.forms['link'].elements['link_name'].value != "")
  {
    var theURL = geturl (type);

    if (theURL != "") hcms_openWindow (theURL, winName, features, <?php echo windowwidth ("object"); ?>, <?php echo windowheight ("object"); ?>);
  }
  else alert(hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['no-link-selected'][$lang], $charset, $lang); ?>'));
}

function checkForm()
{ 
  if (document.forms['link'] && document.forms['link'].elements['link_name'] && document.forms['link'].elements['linkhref'])
  {    
    if (document.forms['link'].elements['link_name'].value != "")
    {
      // manually entered hyperlink (http://)
      if (document.forms['link'].elements['link_name'].value.indexOf('://') > 0)
      {
        document.forms['link'].elements['linkhref'].value = document.forms['link'].elements['link_name'].value;
      }
      // manually entered relative hyperlink
      else if (document.forms['link'].elements['link_name'].value.indexOf('://') == -1 && document.forms['link'].elements['link_name'].value.indexOf('/') > 0)
      {
  	    document.forms['link'].elements['linkhref'].value = document.forms['link'].elements['link_name'].value;
      }
      // or set via navigation tree
      else if (document.forms['link'].elements['link_name'].value.indexOf('/') == 0 && document.forms['link'].elements['link_name'].value.indexOf('%page%') == -1 && document.forms['link'].link_name.value.indexOf('%comp%') == -1)
      {
        var link_name = document.forms['link'].elements['link_name'];
        var link_href = document.forms['link'].elements['linkhref'];
        var link_add = '';
               
        //  manually added anchor (#anchor)
        if (link_name.value.indexOf('#') > 0)
        {
          link_add = link_name.value.substring(link_name.value.indexOf('#'), link_name.value.length);

          if (link_href.value.indexOf('#') > 0) link_href.value = link_href.value.substring(0, link_href.value.indexOf('#')) + link_add;
          else link_href.value = link_href.value + link_add;            
        }
        // manually added parameters (?variable=name)
        else if (link_name.value.indexOf('?') > 0)
        {
          link_add = link_name.value.substring(link_name.value.indexOf('?'), link_name.value.length);
          
          if (link_href.value.indexOf('?') > 0) link_href.value = link_href.value.substring(0, link_href.value.indexOf('?')) + link_add;
          else link_href.value = link_href.value + link_add;
        }
        // selected link by explorer
        else link_href.value = link_href.value + link_add;        
      }
      else
      {
        // use link_name
        document.forms['link'].elements['linkhref'].value = document.forms['link'].elements['link_name'].value;
      }
    }
    // all other cases
    else
    { 
      // use link name
      document.forms['link'].elements['linkhref'].value = '';
    }
  }
  
  correctnames ();
  document.forms['link'].submit();
  return true;
}

function deleteEntry(select)
{
  select.value = "";
}

function refreshPreview ()
{
  var theURL = geturl ('preview');

  if (theURL != "") document.getElementById("preview").src = theURL;
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" onload="refreshPreview();">

<!-- top bar -->
<?php
// set character set in global variable of function showtopbar 
$hcms_charset = $charset;

echo showtopbar ($label, $lang, $mgmt_config['url_path_cms']."page_view.php?view=".url_encode($view)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame");
?>

<div class="hcmsWorkplaceFrame">
<form name="link" action="service/savecontent.php" target="_parent" method="post">
  <input type="hidden" name="contenttype" value="<?php echo $contenttype; ?>">
  <input type="hidden" name="view" value="<?php echo $view; ?>">
  <input type="hidden" name="savetype" value="<?php echo $savetype; ?>" />
  <input type="hidden" name="site" value="<?php echo $site; ?>">
  <input type="hidden" name="cat" value="<?php echo $cat; ?>">
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>">
  <input type="hidden" name="page" value="<?php echo $page; ?>">
  <input type="hidden" name="contentfile" value="<?php echo $contentfile; ?>">
  <input type="hidden" name="db_connect" value="<?php echo $db_connect; ?>">
  <input type="hidden" name="tagname" value="<?php echo $tagname; ?>">
  <input type="hidden" name="id" value="<?php echo $id; ?>">      
  <input type="hidden" name="linkhref" value="<?php echo $linkhref; ?>">
  <input type="hidden" name="token" value="<?php echo $token; ?>">
  
  <table class="hcmsTableStandard">
    <tr>
      <td colspan="2" class="hcmsHeadlineTiny"><?php echo getescapedtext ($hcms_lang['link'][$lang], $charset, $lang); ?></td>
    </tr>      
    <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['selected-linkurl'][$lang], $charset, $lang); ?> </td>
      <td style="white-space:nowrap;">
        <input type="text" name="link_name" value="<?php echo convertchars (getlocationname ($site, $linkhref, "page", "path"), $hcms_lang_codepage[$lang], $charset); ?>" style="width:220px;" />
        <img onClick="openBrWindowLink('preview','scrollbars=yes,resizable=yes', 'preview')" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonView" src="<?php echo getthemelocation(); ?>img/button_file_liveview.png" alt="<?php echo getescapedtext ($hcms_lang['preview'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['preview'][$lang], $charset, $lang); ?>" />
        <img onClick="openBrWindowLink('','scrollbars=yes,resizable=yes,status=yes', 'cmsview');" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonEdit" src="<?php echo getthemelocation(); ?>img/button_edit.png" alt="<?php echo getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang); ?>" />
        <img onClick="deleteEntry(document.link.linkhref); deleteEntry(document.link.link_name);" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonDelete" src="<?php echo getthemelocation(); ?>img/button_delete.png" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang); ?>" />
        <img onClick="checkForm();" name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
      </td>
    </tr>
  <?php
  if ($linktarget != "*Null*")
  {
    echo "
    <tr>
     <td>".getescapedtext ($hcms_lang['open-link'][$lang], $charset, $lang)." </td>
      <td>
       <select name=\"linktarget\" style=\"width:220px;\">";

    $list_array = array();
    
    if (substr_count ($targetlist, "|") >= 1) $list_array = explode ("|", $targetlist);
    elseif ($targetlist != "") $list_array[] = $targetlist;
    
    if (sizeof ($list_array) > 0)
    {
      foreach ($list_array as $target)
      {
        echo "
          <option value=\"".$target."\""; if ($linktarget == $target) echo " selected=\"selected\""; echo ">".$target."</option>";
      }
    }
  
    echo "
          <option value=\"_self\""; if ($linktarget == "_self") echo " selected=\"selected\""; echo ">".getescapedtext ($hcms_lang['in-same-frame'][$lang], $charset, $lang)."</option>
          <option value=\"_parent\""; if ($linktarget == "_parent") echo " selected=\"selected\""; echo ">".getescapedtext ($hcms_lang['in-parent-frame'][$lang], $charset, $lang)."</option>
          <option value=\"_top\""; if ($linktarget == "_top") echo " selected=\"selected\""; echo ">".getescapedtext ($hcms_lang['in-same-browser-window'][$lang], $charset, $lang)."</option>
          <option value=\"_blank\""; if ($linktarget == "_blank") echo " selected=\"selected\""; echo ">".getescapedtext ($hcms_lang['in-new-browser-window'][$lang], $charset, $lang)."</option>
        </select>
      </td>
    </tr>";
  }

  if ($linktext != "*Null*")
  {
    echo "
    <tr>
      <td>".getescapedtext ($hcms_lang['link-text'][$lang], $charset, $lang)." </td>
      <td>
        <input type=\"text\" name=\"linktext\" value=\"".$linktext."\" style=\"width:220px;\" />
      </td>
    </tr>";
  }
  ?>
  </table>
  
  <iframe id="preview" src="" style="width:98%; height:500px; border:1px solid #000000; margin:5px;" sandbox=""></iframe>
</form>
</div>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>
