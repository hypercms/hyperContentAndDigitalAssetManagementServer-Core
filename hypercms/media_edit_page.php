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
$mediacat = getrequest ("mediacat", "objectname");
$mediatype = getrequest_esc ("mediatype", "objectname", "", true);
$mediadir = getrequest_esc ("mediadir", "locationname");
$mediafile = getrequest_esc ("mediafile", "objectname");
$mediatype = getrequest_esc ("mediatype", "objectname", "", true); 
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
$container_id = getcontentcontainerid ($contentfile);

// read content using db_connect
if (!empty ($db_connect) && valid_objectname ($db_connect) && is_file ($mgmt_config['abs_path_data']."db_connect/".$db_connect)) 
{
  include ($mgmt_config['abs_path_data']."db_connect/".$db_connect);

  $db_connect_data = db_read_media ($site, $contentfile, "", $id, "", $user);

  if ($db_connect_data != false)
  {
    $mediafile = $db_connect_data['file'];
    $mediaobject = $db_connect_data['object'];
    if ($mediaalttext != "*Null*") $mediaalttext = $db_connect_data['alttext'];
    if ($mediaalign != "*Null*") $mediaalign = $db_connect_data['align'];
    if ($mediawidth != "*Null*") $mediawidth = $db_connect_data['width'];
    if ($mediaheight != "*Null*") $mediaheight = $db_connect_data['height'];
  }
}

// read content from content container when db_connect is not used
if (empty ($db_connect_data))
{
  $contentdata = loadcontainer ($contentfile, "work", "sys");

  if (!empty ($contentdata))
  {
    // get the whole media object information of the content container
    if (!empty ($id))
    {
      $medianode = selectcontent ($contentdata, "<media>", "<media_id>", $id);

      if (!empty ($medianode[0]))
      {
        $temp_array = getcontent ($medianode[0], "<mediafile>");
        if (!empty ($temp_array[0])) $mediafile = $temp_array[0];
        else $mediafile = "";
        
        $temp_array = getcontent ($medianode[0], "<mediaobject>");
        if (!empty ($temp_array[0])) $mediaobject = $temp_array[0];
        else $mediaobject = "";
        
        if ($mediaalttext != "*Null*")
        {
          $temp_array = getcontent ($medianode[0], "<mediaalttext>");
          if (!empty ($temp_array[0])) $mediaalttext = $temp_array[0];
          else $mediaalttext = "";
        }
        
        if ($mediaalign != "*Null*") 
        {
          $temp_array = getcontent ($medianode[0], "<mediaalign>");
          if (!empty ($temp_array[0])) $mediaalign = $temp_array[0];
          else $mediaalign = "";
        }

        if ($mediawidth != "*Null*")
        {
          $temp_array = getcontent ($medianode[0], "<mediawidth>");
          if (!empty ($temp_array[0])) $mediawidth = $temp_array[0];
          else $mediawidth = "";
        }

        if ($mediaheight != "*Null*")
        {
          $temp_array = getcontent ($medianode[0], "<mediaheight>");
          if (!empty ($temp_array[0])) $mediaheight = $temp_array[0];
          else $mediaheight = "";
        }
      }  
    }
  }
}

// escape special characters
$mediaalttext = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $mediaalttext);
$mediaalign = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $mediaalign);
$mediawidth = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $mediawidth);
$mediaheight = str_replace (array("\"", "'", "<", ">"), array("&quot;", "&#039;", "&lt;", "&gt;"), $mediaheight);

// remove &amp; from specific variables
$variables = array ('mediaalttext');

foreach ($variables as $variable)
{
  $$variable = str_replace ("&amp;", "&", $$variable);
}

// get file info
$file_info = getfileinfo ($site, $location.$page, $cat);

// clean Null_media
if (substr_count ($mediafile, "Null_media.png") == 1)
{
  $mediafile = "";
}
elseif ($mediaobject != "")
{
  $mediafile = $mediaobject;
}

// add %comp% if not provided
if (strpos ("_".$mediaobject, "%comp%/") == 0)
{
  if (is_file (deconvertpath ("%comp%".$mediaobject, "file"))) $mediaobject = "%comp%".$mediaobject;
  elseif (is_file (deconvertpath ("%comp%/".$mediaobject, "file"))) $mediaobject = "%comp%/".$mediaobject;
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
else $label = getlabel ($label, $lang);

// set character set in header
if (!empty ($charset)) header ('Content-Type: text/html; charset='.$charset);

// submit action
if ($mediawidth != "*Null*" || $mediaheight != "*Null*")
{
  $onsubmit = "submitMediaAll();";
}
else $onsubmit = "submitMediaType();";
?>
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo $charset; ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript" src="javascript/jquery/jquery.min.js"></script>
<script type="text/javascript">

function correctnames ()
{
  if (document.forms['media'].elements['mediafile']) document.forms['media'].elements['mediafile'].name = "<?php echo $art; ?>mediafile[<?php echo $id; ?>]";
  
  if (document.forms['media'].elements['mediaobject'])
  {
    document.forms['media'].elements['mediaobject'].name = "<?php echo $art; ?>mediaobject[<?php echo $id; ?>]";
  }
  
  if (document.forms['media'].elements['mediaalttext']) document.forms['media'].elements['mediaalttext'].name = "<?php echo $art; ?>mediaalttext[<?php echo $id; ?>]";
  if (document.forms['media'].elements['mediaalign']) document.forms['media'].elements['mediaalign'].name = "<?php echo $art; ?>mediaalign[<?php echo $id; ?>]";
  if (document.forms['media'].elements['mediawidth']) document.forms['media'].elements['mediawidth'].name = "<?php echo $art; ?>mediawidth[<?php echo $id; ?>]";
  if (document.forms['media'].elements['mediaheight']) document.forms['media'].elements['mediaheight'].name = "<?php echo $art; ?>mediaheight[<?php echo $id; ?>]";
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
      if (mediatype == "watermark") allowedext = ".jpg.jpeg.png.gif";
      else if (mediatype == "audio") allowedext = "<?php echo strtolower ($hcms_ext['audio']); ?>";
      else if (mediatype == "compressed") allowedext = "<?php echo strtolower ($hcms_ext['compressed']); ?>";
      else if (mediatype == "flash") allowedext = "<?php echo strtolower ($hcms_ext['flash']); ?>";
      else if (mediatype == "image") allowedext = "<?php echo strtolower ($hcms_ext['image'].$hcms_ext['vectorimage']); ?>";
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

  if (errors) alert(hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['the-following-errors-occurred'][$lang], $charset, $lang); ?>\n ' + errors));
  document.returnValue = (errors == '');
}

function openBrWindowMedia (winName, features, type)
{
  var url = document.forms['media'].elements['mediaobject'].value;
  var url_result = "";
  
  if (url != "")
  {
    if (type == "preview")
    {
      if (url.indexOf('://') == -1)
      {
        var position1 = url.indexOf("/");
        url_result = '<?php echo $mgmt_config['url_path_comp']; ?>' + url.substring (position1+1, url.length);
      }
    }
    else if (type == "cmsview")  
    {
      if (url.indexOf('://') == -1)
      {      
        var position1 = url.indexOf("/");
        position2 = url.lastIndexOf("/");
        
        var location_comp = "%comp%/" + url.substring (position1+1, position2+1);
        
        var location_site = url.substring (position1+1, url.length);              
        location_site = location_site.substring(0, location_site.indexOf('/'));
        
        var page_comp = url.substr (position2+1, url.length);
        
        url_result = '<?php echo $mgmt_config['url_path_cms']; ?>frameset_content.php?ctrlreload=yes&cat=comp&site=' + encodeURIComponent(location_site) + '&location=' + encodeURIComponent(location_comp) + '&page=' + encodeURIComponent(page_comp) + '&user=<?php echo url_encode($user); ?>';
      }
      else alert (hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['this-is-an-external-link'][$lang], $charset, $lang); ?>'));
    }
    
    if (url_result != "") hcms_openWindow (url_result, winName, features, <?php echo windowwidth ("object"); ?>, <?php echo windowheight ("object"); ?>);
  }
  else alert (hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['no-file-selected'][$lang], $charset, $lang); ?>'));  
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

function hcms_saveEvent ()
{
  <?php echo $onsubmit; ?>
}

// check for modified content
function checkUpdatedContent ()
{
  $.ajax({
    type: 'POST',
    url: "<?php echo cleandomain ($mgmt_config['url_path_cms'])."service/checkupdatedcontent.php"; ?>",
    data: {container_id:"<?php echo $container_id; ?>",tagname:"media",tagid:"<?php echo $id; ?>"},
    success: function (data)
    {
      if (data.message.length !== 0)
      {
        console.log('The same content has been modified by another user');
        var update = confirm (hcms_entity_decode(data.message));
        if (update == true) location.reload();
      }
    },
    dataType: "json",
    async: false
  });
}

setInterval (checkUpdatedContent, 3000);

// display media in main frame
if (parent.document.getElementById('mainFrame2'))
{
  parent.document.getElementById('mainFrame2').src = "<?php echo "media_view.php?site=".url_encode($site)."&mediacat=".url_encode($mediacat)."&mediafile=".url_encode($mediafile)."&mediaobject=".url_encode($mediaobject)."&mediatype=".url_encode($mediatype)."&scaling=".url_encode($scaling); ?>";
}
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php
// set character set in global variable of function showtopbar 
$hcms_charset = $charset;

echo showtopbar ($label, $lang, $mgmt_config['url_path_cms']."page_view.php?view=".url_encode($view)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame");
?>

<div class="hcmsWorkplaceFrame">
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
  <input type="hidden" name="mediatype" value="<?php echo $mediatype; ?>" />
  <input type="hidden" name="mediaobject" value="<?php echo $mediaobject; ?>" />
  <input type="hidden" name="token" value="<?php echo $token; ?>">

  <table class="hcmsTableStandard">
    <tr>
      <td colspan="2" class="hcmsHeadlineTiny" style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['media-file'][$lang], $charset, $lang); ?></td>
    </tr>
    <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['selected-media-file'][$lang], $charset, $lang); ?> </td>
      <td style="white-space:nowrap;">
        <input type="text" name="mediafile" value="<?php echo convertchars (getlocationname ($site, $mediafile, "comp"), $hcms_lang_codepage[$lang], $charset); ?>" style="width:220px;" />
        <img onClick="openBrWindowMedia('','location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes,status=no', 'cmsview');" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonEdit" src="<?php echo getthemelocation(); ?>img/button_edit.png" alt="<?php echo getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['edit'][$lang], $charset, $lang); ?>" />
        <img onClick="deleteEntry(document.forms['media'].elements['mediafile']); deleteEntry(document.forms['media'].elements['mediaobject']);" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonDelete" src="<?php echo getthemelocation(); ?>img/button_delete.png" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['delete'][$lang], $charset, $lang); ?>" />
        <img onClick="<?php echo $onsubmit; ?>" name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
      </td>
    </tr>

  <?php
  if ($mediaalttext != "*Null*")
  {
    echo "
    <tr>
      <td style=\"white-space:nowrap;\">".getescapedtext ($hcms_lang['alternative-text'][$lang], $charset, $lang)." </td>
      <td>
        <input type=\"text\" name=\"mediaalttext\" value=\"".$mediaalttext."\" style=\"width:220px;\" maxlength=\"255\"/>
      </td>
    </tr>";
  }

  if ($id == "Watermark")
  {
    echo "
    <tr>
      <td style=\"white-space:nowrap;\">".getescapedtext ($hcms_lang['alignment'][$lang], $charset, $lang)." </td>
      <td>
        <select name=\"mediaalign\" style=\"width:230px;\">
          <option value=\"topleft\"".($mediaalign == "topleft" ? "selected=\"selected\"" : "").">".getescapedtext ($hcms_lang['top'][$lang]." ".$hcms_lang['left'][$lang], $charset, $lang)."</option>
          <option value=\"topright\"".($mediaalign== "topright" ? "selected=\"selected\"" : "").">".getescapedtext ($hcms_lang['top'][$lang]." ".$hcms_lang['right'][$lang], $charset, $lang)."</option>
          <option value=\"bottomleft\"".($mediaalign == "bottomleft" ? "selected=\"selected\"" : "").">".getescapedtext ($hcms_lang['bottom'][$lang]." ".$hcms_lang['left'][$lang], $charset, $lang)."</option>
          <option value=\"bottomright\"".($mediaalign == "bottomright" ? "selected=\"selected\"" : "").">".getescapedtext ($hcms_lang['bottom'][$lang]." ".$hcms_lang['right'][$lang], $charset, $lang)."</option>
          <option value=\"center\"".($mediaalign == "center" ? "selected=\"selected\"" : "").">".getescapedtext ($hcms_lang['middle'][$lang], $charset, $lang)."</option>
        </select>
      </td>
    </tr>";
  }
  elseif ($mediaalign != "*Null*")
  {
    echo "
    <tr>
      <td style=\"white-space:nowrap;\">".getescapedtext ($hcms_lang['alignment'][$lang], $charset, $lang)." </td>
      <td style=\"vertical-align:top;\">
        <select name=\"mediaalign\" style=\"width:230px;\">
          <option value=\"\" ".($mediaalign == "" ? "selected=\"selected\"" : "").">".getescapedtext ($hcms_lang['standard'][$lang], $charset, $lang)."</option>
          <option value=\"top\" ".($mediaalign == "top" ? "selected=\"selected\"" : "").">".getescapedtext ($hcms_lang['top'][$lang], $charset, $lang)."</option>
          <option value=\"middle\" ".($mediaalign == "middle" ? "selected=\"selected\"" : "").">".getescapedtext ($hcms_lang['middle'][$lang], $charset, $lang)."</option>
          <option value=\"absmiddle\" ".($mediaalign == "absmiddle" ? "selected=\"selected\"" : "").">".getescapedtext ($hcms_lang['absolute-middle'][$lang], $charset, $lang)."</option>
          <option value=\"bottom\" ".($mediaalign == "bottom" ? "selected=\"selected\"" : "").">".getescapedtext ($hcms_lang['bottom'][$lang], $charset, $lang)."</option>
          <option value=\"left\" ".($mediaalign == "left" ? "selected=\"selected\"" : "").">".getescapedtext ($hcms_lang['left'][$lang], $charset, $lang)."</option>
          <option value=\"right\" ".($mediaalign == "right" ? "selected=\"selected\"" : "").">".getescapedtext ($hcms_lang['right'][$lang], $charset, $lang)."</option>
        </select>
      </td>
    </tr>";
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
    	$media_size = getmediasize ($media_path);
    
    	if (!empty ($media_size['width']) && !empty ($media_size['width']))
      {
    		// scaling images to reach given dpi 
    		$mediawidth = round ($media_size['width'] * $scaling);
    		$mediaheight = round ($media_size['height'] * $scaling);
    	}
    }
  
    echo "
    <tr>
      <td style=\"white-space:nowrap;\">".getescapedtext ($hcms_lang['media-size'][$lang], $charset, $lang)." </td>
      <td class=\"hcmsHeadlineTiny\" style=\"vertical-align:top;\">
    ";
      
    if ($mediawidth != "*Null*") echo getescapedtext ($hcms_lang['width'][$lang], $charset, $lang)." <input type=\"number\" name=\"mediawidth\" value=\"".$mediawidth."\" style=\"width:60px;\" min=\"0\" max=\"99999\"/>&nbsp;";
    else  echo getescapedtext ($hcms_lang['width'][$lang], $charset, $lang).": <input type=\"number\" name=\"mediawidth\" value=\"\" style=\"width:60px;\" disabled=\"disabled\" />&nbsp;";
    
    if ($mediaheight != "*Null*") echo getescapedtext ($hcms_lang['height'][$lang], $charset, $lang)." <input type=\"number\" name=\"mediaheight\" value=\"".$mediaheight."\" style=\"width:60px;\" min=\"0\" max=\"99999\"/>";
    else echo getescapedtext ($hcms_lang['height'][$lang], $charset, $lang).": <input type=\"number\" name=\"mediaheight\" value=\"\" style=\"width:60px;\" disabled=\"disabled\" />";
    
    echo "
      </td>
    </tr>";
  }
  ?>
  </table>
</form>
<hr/>
</div>

<?php includefooter(); ?>

</body>
</html>