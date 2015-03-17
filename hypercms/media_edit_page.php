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
// load formats/file extensions
require_once ("include/format_ext.inc.php");


// input parameters
$action = getrequest ("action");
$view = getrequest_esc ("view");
$savetype = getrequest_esc ("savetype");
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$contenttype = getrequest_esc ("contenttype");
$db_connect = getrequest_esc ("db_connect", "objectname");
$tagname = getrequest_esc ("tagname", "objectname");
$id = getrequest_esc ("id", "objectname", "", true);
$label = getrequest_esc ("label");
$mediadir = getrequest_esc ("mediadir", "locationname");
$mediafile = getrequest_esc ("mediafile", "objectname");
$mediatype = getrequest_esc ("mediatype", "objectname", "", true); 
$mediaobject_curr = getrequest_esc ("mediaobject_curr", "locationname");
$mediaobject = getrequest_esc ("mediaobject", "locationname");
$mediaalttext = getrequest_esc ("mediaalttext");
$mediaalign = getrequest_esc ("mediaalign");
$mediawidth = getrequest_esc ("mediawidth");
$mediaheight = getrequest_esc ("mediaheight");
$scaling = getrequest ("scaling", "numeric");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);  
if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// load object file and get container and media file
$objectdata = loadfile ($location, $page);
$contentfile = getfilename ($objectdata, "content");

// remove &amp; from specific variables
$variables = array ('mediaalttext');

foreach ($variables as $variable)
{
  $$variable = str_replace ("&amp;", "&", $$variable);
}

// get file info
$file_info = getfileinfo ($site, $location.$page, $cat);

// clean Null_media
if (substr_count ($mediafile, "Null_media.gif") == 1)
{
  $mediafile = "";
}
elseif ($mediaobject != "")
{
  $mediafile = $mediaobject;
}

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
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="<?php echo $contenttype; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
function correctnames ()
{
  if (eval (document.forms['media'].elements['mediafile'])) document.forms['media'].elements['mediafile'].name = "<?php echo $art; ?>mediafile[<?php echo $id; ?>]";
  if (eval (document.forms['media'].elements['mediaobject']))
  {
    document.forms['media'].elements['mediaobject_curr'].name = "<?php echo $art; ?>mediaobject_curr[<?php echo $id; ?>]";
    document.forms['media'].elements['mediaobject'].name = "<?php echo $art; ?>mediaobject[<?php echo $id; ?>]";
  }
  if (eval (document.forms['media'].elements['mediaalttext'])) document.forms['media'].elements['mediaalttext'].name = "<?php echo $art; ?>mediaalttext[<?php echo $id; ?>]";
  if (eval (document.forms['media'].elements['mediaalign'])) document.forms['media'].elements['mediaalign'].name = "<?php echo $art; ?>mediaalign[<?php echo $id; ?>]";
  if (eval (document.forms['media'].elements['mediawidth'])) document.forms['media'].elements['mediawidth'].name = "<?php echo $art; ?>mediawidth[<?php echo $id; ?>]";
  if (eval (document.forms['media'].elements['mediaheight'])) document.forms['media'].elements['mediaheight'].name = "<?php echo $art; ?>mediaheight[<?php echo $id; ?>]";
  return true;
}

function deleteEntry(select)
{
  select.value = "";
}

function checkType()
{
  var mediafile = document.forms['media'].elements['mediafile'].value;
  var mediatype = document.forms['media'].elements['mediatype'].value;
  
  if (mediafile != "" && mediatype != "")
  {
    var mediaext = mediafile.substring (mediafile.lastIndexOf("."), mediafile.length);
    mediaext = mediaext.toLowerCase();
   
    if (mediaext.length > 2)
    {
      if (mediatype == "audio") allowedext = "<?php echo strtolower ($hcms_ext['audio']); ?>";
      else if (mediatype == "compressed") allowedext = "<?php echo strtolower ($hcms_ext['compressed']); ?>";
      else if (mediatype == "flash") allowedext = "<?php echo strtolower ($hcms_ext['flash']); ?>";
      else if (mediatype == "image") allowedext = "<?php echo strtolower ($hcms_ext['image']); ?>";
      else if (mediatype == "text") allowedext = "<?php echo strtolower ($hcms_ext['cms'].$hcms_ext['bintxt'].$hcms_ext['cleartxt']); ?>";
      else if (mediatype == "video") allowedext = "<?php echo strtolower ($hcms_ext['video']); ?>";
      
      if (allowedext.indexOf(mediaext) < 0) 
      {
        alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['file-is-of-wrong-media-type-required-type'][$lang], $charset, $lang)." ".$mediatype; ?>"));
        return false;
      }
      else return true;   
    }
    else return true;
  }
  else return true;
}

function validateForm()
{
  var i,p,q,nm,test,num,min,max,errors='',args=validateForm.arguments;

  for (i=0; i<(args.length-2); i+=3)
  {
    test=args[i+2];
    val=hcms_findObj(args[i]);

    if (val)
    {
      nm=val.name;

      if ((val=val.value)!="")
      {
        if (test.indexOf('isEmail')!=-1)
        {
          p=val.indexOf('@');

          if (p<1 || p==(val.length-1)) errors+='- '+nm+' <?php echo getescapedtext ($hcms_lang['must-contain-an-e-mail-address'][$lang], $charset, $lang); ?>.\n';
        }
        else if (test!='R')
        {
          if (isNaN(val)) errors+='- '+nm+' <?php echo getescapedtext ($hcms_lang['must-contain-a-number'][$lang], $charset, $lang); ?>.\n';

          if (test.indexOf('inRange') != -1)
          {
            p=test.indexOf(':');
            min=test.substring(8,p);
            max=test.substring(p+1);

            if (val<min || max<val) errors+='- '+nm+' <?php echo getescapedtext ($hcms_lang['must-contain-a-number-between'][$lang], $charset, $lang); ?> '+min+' <?php echo getescapedtext ($hcms_lang['and'][$lang], $charset, $lang); ?> '+max+'.\n';
          }
        }
      }
      else if (test.charAt(0) == 'R') errors += '- '+nm+' <?php echo getescapedtext ($hcms_lang['is-required'][$lang], $charset, $lang); ?>.\n';
    }
  }

  if (errors) alert(hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['the-following-errors-occurred'][$lang], $charset, $lang); ?>:\n'+errors));
  document.returnValue = (errors == '');
}

function submitMediaAll ()
{
  test = validateForm('mediawidth','','NisNum','mediaheight','','NisNum');

  if (test != false) 
  {
    test = checkType();
    
    if (test != false)
    {
      correctnames ();
      document.forms['media'].submit();
    }
    else return false;
  }
  else return false;
}

function submitMediaType ()
{
  test = checkType();
  
  if (test != false)
  {
    correctnames ();
    document.forms['media'].submit();
  }
  else return false;
}

function openBrWindowMedia(winName, features, type)
{
  theURL = document.forms['media'].elements['mediaobject'].value;
  
  if (theURL != "")
  {
    if (type == "preview")
    {
      if (theURL.indexOf('://') == -1)
      {
        position1 = theURL.indexOf("/");
        theURL = '<?php echo $mgmt_config['url_path_comp']; ?>' + theURL.substring (position1+1, theURL.length);
      }
  
      popup = window.open(theURL,winName,features);
      popup.moveTo(screen.width/2-800/2, screen.height/2-600/2);
      popup.focus();
    }
    else if (type == "cmsview")  
    {
      if (theURL.indexOf('://') == -1)
      {      
        position1 = theURL.indexOf("/");
        position2 = theURL.lastIndexOf("/");
        
        location_comp = "%comp%/" + theURL.substring (position1+1, position2+1);
        location_comp = escape (location_comp);
        
        location_site = theURL.substring (position1+1, theURL.length);              
        location_site = location_site.substring(0, location_site.indexOf('/'));
        location_site = escape (location_site);
        
        page_comp = theURL.substr (position2+1, theURL.length);
        page_comp = escape (page_comp);
        
        theURL = '<?php echo $mgmt_config['url_path_cms']; ?>frameset_content.php?ctrlreload=yes&cat=comp&site=' + location_site + '&location=' + location_comp + '&page=' + page_comp + '&user=<?php echo $user; ?>';

        popup = window.open(theURL,winName,features);
        popup.moveTo(screen.width/2-800/2, screen.height/2-600/2);
        popup.focus();
      }
      else alert (hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['this-is-an-external-link'][$lang], $charset, $lang); ?>'));
    }
  }
  else alert (hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['no-file-selected'][$lang], $charset, $lang); ?>'));  
}
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric" leftmargin=3 topmargin=3 marginwidth=0 marginheight=0>

<?php
if ($mediawidth != "*Null*" || $mediaheight != "*Null*")
{
  $onsubmit = "submitMediaAll();";
}
else $onsubmit = "submitMediaType();";
?>

<!-- top bar -->
<?php echo showtopbar ($label, $lang, $mgmt_config['url_path_cms']."page_view.php?view=".url_encode($view)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame"); ?>

<form name="media" action="service/savecontent.php" target="_parent" method="post">
  <input type="hidden" name="contenttype" value="<?php echo $contenttype; ?>" />
  <input type="hidden" name="view" value="<?php echo $view; ?>" />
  <input type="hidden" name="savetype" value="<?php echo $savetype; ?>" />
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="page" value="<?php echo $page; ?>" />
  <input type="hidden" name="db_connect" value="<?php echo $db_connect; ?>" />
  <input type="hidden" name="tagname" value="<?php echo $tagname; ?>" />
  <input type="hidden" name="id" value="<?php echo $id; ?>" />  
  <input type="hidden" name="mediadir" value="<?php echo $mediadir; ?>" />
  <input type="hidden" name="mediatype" value="<?php echo $mediatype; ?>" />
  <input type="hidden" name="mediaobject_curr" value="<?php echo $mediaobject_curr; ?>" />
  <input type="hidden" name="mediaobject" value="<?php echo $mediaobject; ?>" />
  <input type="hidden" name="token" value="<?php echo $token; ?>">

  <table border="0" cellspacing="3" cellpadding="0">
    <tr>
      <td colspan=2 class="hcmsHeadlineTiny" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['media-file'][$lang], $charset, $lang); ?></td>
    </tr>
    <tr>
      <td valign="top" nowrap="nowrap"><?php echo getescapedtext ($hcms_lang['selected-media-file'][$lang], $charset, $lang); ?>: </td>
      <td valign="top">
        <input type="text" name="mediafile" value="<?php echo convertchars (getlocationname ($site, $mediafile, "comp"), $hcms_lang_codepage[$lang], $charset); ?>" style="width:300px;" />
        <img onClick="openBrWindowMedia('','scrollbars=yes,resizable=yes,width=800,height=600,status=yes', 'cmsview');" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonEdit" src="<?php echo getthemelocation(); ?>img/button_file_edit.gif" align="absmiddle" alt="<?php echo getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang); ?>" />
        <img onClick="deleteEntry(document.forms['media'].elements['mediafile']); deleteEntry(document.forms['media'].elements['mediaobject']);" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonDelete" src="<?php echo getthemelocation(); ?>img/button_delete.gif" align="absmiddle" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang); ?>" />
        <img onClick="<?php echo $onsubmit; ?>" name="Button" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" />
      </td>
    </tr>

  <?php
  if ($mediaalttext != "*Null*")
  {
    echo "<tr>\n";
    echo "  <td nowrap=\"nowrap\">".getescapedtext ($hcms_lang['alternative-text'][$lang], $charset, $lang).": </td>\n";
    echo "  <td>\n";
    echo "    <input type=\"text\" name=\"mediaalttext\" value=\"".convertchars ($mediaalttext, $hcms_lang_codepage[$lang], $charset)."\" style=\"width:300px;\" />\n";
    echo "  </td>\n";
    echo "</tr>\n";
  }

  if ($mediaalign != "*Null*")
  {
    echo "<tr>\n";
    echo "  <td valign=\"top\" nowrap=\"nowrap\">".getescapedtext ($hcms_lang['alignment'][$lang], $charset, $lang).": </td>\n";
    echo "  <td valign=\"top\">\n";
    echo "    <select name=\"mediaalign\" style=\"width:300px;\">\n";
          $alignstandard = "";
          $aligntop = "";
          $alignmiddle = "";
          $alignabsmiddle = "";
          $alignbottom = "";
          $alignleft = "";
          $alignright = "";

          if ($mediaalign == "")
          {
            $alignstandard="selected=\"selected\"";
          }
          elseif ($mediaalign == "top")
          {
            $aligntop="selected=\"selected\"";
          }
          elseif ($mediaalign == "middle")
          {
            $alignmiddle="selected=\"selected\"";
          }
          elseif ($mediaalign == "absmiddle")
          {
            $alignabsmiddle="selected=\"selected\"";
          }
          elseif ($mediaalign == "bottom")
          {
            $alignbottom="selected=\"selected\"";
          }
          elseif ($mediaalign == "left")
          {
            $alignleft="selected=\"selected\"";
          }
          elseif ($mediaalign == "right")
          {
            $alignright="selected=\"selected\"";
          }

          echo "<option value=\"\" ".$alignstandard.">".getescapedtext ($hcms_lang['standard'][$lang], $charset, $lang)."</option>\n";
          echo "<option value=\"top\" ".$aligntop.">".getescapedtext ($hcms_lang['top'][$lang], $charset, $lang)."</option>\n";
          echo "<option value=\"middle\" ".$alignmiddle.">".getescapedtext ($hcms_lang['middle'][$lang], $charset, $lang)."</option>\n";
          echo "<option value=\"absmiddle\" ".$alignabsmiddle.">".getescapedtext ($hcms_lang['absolute-middle'][$lang], $charset, $lang)."</option>\n";
          echo "<option value=\"bottom\" ".$alignbottom.">".getescapedtext ($hcms_lang['bottom'][$lang], $charset, $lang)."</option>\n";
          echo "<option value=\"left\" ".$alignleft.">".getescapedtext ($hcms_lang['left'][$lang], $charset, $lang)."</option>\n";
          echo "<option value=\"right\" ".$alignright.">".getescapedtext ($hcms_lang['right'][$lang], $charset, $lang)."</option>\n";
    echo "    </select>\n";
    echo "  </td>\n";
    echo "</tr>\n";
  }

  if (($mediawidth != "*Null*" || $mediaheight != "*Null*") || $scaling != "")
  {
  	// check if scalingfactor is given
  	if (!empty ($scaling) && $scaling > 0)
    {
    	// initialize mediaheight and mediawidth
    	$mediawidth = "";
    	$mediaheight = "";
    	// get file information
    	$media_path = getmedialocation ($site, $mediafile, "abs_path_media").$mediafile;
    	$media_size = @getimagesize ($media_path);
    
    	if (!empty ($media_size[3]))
      {
    		// scaling images to reach given dpi 
    		$mediawidth = round ($media_size[0] * $scaling);
    		$mediaheight = round ($media_size[1] * $scaling);
    	}
    }
  
    echo "<tr>\n";
    echo "  <td valign=\"top\">\n";
    echo "    ".getescapedtext ($hcms_lang['media-size'][$lang], $charset, $lang).": </td>\n";
    echo "  <td valign=\"top\" class=\"hcmsHeadlineTiny\">\n";
    if ($mediawidth != "*Null*") echo "    ".getescapedtext ($hcms_lang['width'][$lang], $charset, $lang).": <input type=\"text\" name=\"mediawidth\" value=\"".$mediawidth."\" size=4 />\n";
    else  echo "    ".getescapedtext ($hcms_lang['width'][$lang], $charset, $lang).": <input type=\"text\" name=\"mediawidth\" value=\"\" size=4 disabled=\"disabled\" />\n";
    if ($mediaheight != "*Null*") echo "    ".getescapedtext ($hcms_lang['height'][$lang], $charset, $lang).": <input type=\"text\" name=\"mediaheight\" value=\"".$mediaheight."\" size=4 />\n";
    else echo "    ".getescapedtext ($hcms_lang['height'][$lang], $charset, $lang).": <input type=\"text\" name=\"mediaheight\" value=\"\" size=4 disabled=\"disabled\" />\n";
    echo "  </td>\n";
    echo "</tr>\n";
  }
  ?>
  </table>
</form>

</body>
</html>