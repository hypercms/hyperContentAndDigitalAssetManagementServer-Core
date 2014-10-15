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
require_once ("language/control_content_menu.inc.php");


// input parameters
$action = getrequest ("action");
$location = getrequest_esc ("location", "locationname");
$folder = getrequest_esc ("folder", "objectname");
$page = getrequest_esc ("page", "objectname");
$template = getrequest ("template");
$wf_token = getrequest_esc ("wf_token");
$token = getrequest ("token");
$convert_type = getrequest ("convert_type");
$convert_cfg = getrequest ("convert_cfg");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
// load publication configuration for live view
if (valid_publicationname ($site)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini");

// ------------------------------ permission section --------------------------------

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

// check local permissions for DAM usage only
if ($mgmt_config[$site]['dam'] == true && $setlocalpermission['root'] != 1) killsession ($user);
// check for general root element access since local permissions are checked later
// Attention! variable page can be empty when a new object will be created
elseif (
         !valid_publicationaccess ($site) || 
         (!valid_objectname ($page) && ($setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1)) || 
         !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($cat)
       ) killsession ($user);
       
// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";
$usedby = "";
$wf_id = "";
$wf_role = "";
$contentfile = "";
$application = "";

// get workflow id and release
if ($wf_token != "")
{
  $wf_string = hcms_decrypt ($wf_token);
  if ($wf_string != "" && strpos ($wf_string, ":") > 0) list ($wf_id, $wf_role) = explode (":", $wf_string);
}

// load object file and get container and media file
if ($action != "page_create")
{
  $objectdata = loadfile ($location, $page);
  $contentfile = getfilename ($objectdata, "content");
  $media = getfilename ($objectdata, "media");
  $template = getfilename ($objectdata, "template");

  if ($template != "")
  {
    $result = loadtemplate ($site, $template);
    
    if (is_array ($result))
    {
      $bufferdata = getcontent ($result['content'], "<application>");
      $application = $bufferdata[0];
    }
  }
}

// define message if object is checked out by another user
if (!empty ($contentfile))
{
  $usedby_array = getcontainername ($contentfile);
  
  if (is_array ($usedby_array) && !empty ($usedby_array['user'])) $usedby = $usedby_array['user'];
  else $usedby = "";

  if ($usedby != "" && $usedby != $user) $show = $text54[$lang]." '".$usedby."'";
  else $show = "";
}

// execute action
if ($action != "" && checktoken ($token, $user))
{
  // create object (token only holds location) 
  if ($setlocalpermission['create'] == 1 && $action == "page_create") 
  {
    $result = createobject ($site, $location, $page, $template, $user);
    
    $add_onload = $result['add_onload'];
    $show = $result['message'];  
    $page = $result['object'];
    $pagename = $result['name'];
  }
  // lock object
  elseif ($setlocalpermission['root'] == 1 && $action == "page_lock") 
  {
    $result = lockobject ($site, $location, $page, $user);

    $add_onload = $result['add_onload'];
    $show = "";  
    $page = $result['object'];
    $usedby = $result['usedby'];
    $pagename = $result['name'];
  }
  // unlock object
  elseif ($setlocalpermission['root'] == 1 && $action == "page_unlock") 
  {
    if ($usedby == $user)
    {
      $result = unlockobject ($site, $location, $page, $user);
    }
    elseif ($usedby != $user && $rootpermission['user'] == 1)
    {
      $result = unlockobject ($site, $location, $page, $usedby);
    }
    
    $add_onload = $result['add_onload'];
    $show = "";  
    $page = $result['object'];
    $usedby = $result['usedby'];
    $pagename = $result['name'];
  }
}

// get file info
if ($page != "") 
{
  // correct object file name
  $page = correctfile ($location, $page, $user);      
  // get file info
  $file_info = getfileinfo ($site, $location.$page, $cat);
  $filetype = $file_info['type'];
  $pagename = $file_info['name'];
}
else
{
  $file_info = Null;
  $filetype = "";
  $pagename = "";
}

// define object category name
if ($filetype == "Page")
{
  $pagecomp = $text0[$lang];
}
elseif ($filetype == "Component")
{
  $pagecomp = $text1[$lang];
}
elseif ($page == ".folder")
{
  $pagecomp = $text13[$lang];
}
else
{
  $pagecomp = $text2[$lang];
}

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<meta name="viewport" content="width=device-width; initial-scale=1.0; user-scalable=1;">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<style>
a {behavior: url(#default#AnchorClick);}
</style>
<script src="javascript/timeout.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
<!--
var locklayer = false;

function submitToWindow (url, action, windowname, features, width, height)
{
  if (features == undefined) features = 'scrollbars=no,resizable=no';
  if (width == undefined) width = 400;
  if (height == undefined) height = 120;
  if (windowname == '') windowname = Math.floor(Math.random()*9999999);
  
  hcms_openBrWindowItem('', windowname, features, width, height);
  
  var form = /*parent.frames['objframe'].*/document.forms['pagemenu_object'];
  
  form.attributes['action'].value = url;
  form.elements['action'].value = action;
  form.elements['site'].value = '<?php echo $site; ?>';
  form.elements['cat'].value = '<?php echo $cat; ?>';
  form.elements['location'].value = '<?php echo $location_esc; ?>';
  form.elements['page'].value = '<?php echo $page; ?>';
  form.elements['pagename'].value = '<?php echo $pagename; ?>';
  form.elements['folder'].value = '<?php echo $folder; ?>';
  form.elements['force'].value = 'start';
  form.elements['token'].value = '<?php echo $token_new; ?>';
  form.target = windowname;
  form.submit();
}

function submitToSelf (action)
{
  var form = document.forms['download'];
  
  form.elements['action'].value = action;
  form.elements['location'].value = '<?php echo $location_esc; ?>';
  form.elements['page'].value = '<?php echo $page; ?>';
  form.elements['wf_token'].value = '<?php echo $wf_token; ?>';
  form.submit();
}

function checkForm_chars(text, exclude_chars)
{
	<?php if ($mgmt_config[$site]['specialchr_disable']) { ?>
  exclude_chars = exclude_chars.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
  
  var expr = new RegExp ("[^a-zA-Z0-9" + exclude_chars + "]", "g");
  <?php } else { ?>
  var expr = new RegExp ('[,;/\\\\~`!@#$%^&:*?<>{}=|]', "g");
  <?php } ?>
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
		alert (hcms_entity_decode("<?php echo $text9[$lang]; ?>: ") + addText);
		return false;
	}
  else
  {
		return true;
	}
}

function checkForm_page_create()
{
  var form = document.forms['page_create'];
  
  if (form.elements['page'].value == "")
  {
    alert (hcms_entity_decode("<?php echo $text10[$lang]; ?>"));
    form.elements['page'].focus();
    return false;
  }
  
  if (form.elements['template'].value == "empty.php")
  {
    alert (hcms_entity_decode("<?php echo $text43[$lang]; ?>"));
    form.elements['template'].focus();
    return false;  
  }
  
  if (!checkForm_chars(form.elements['page'].value, ".-_"))
  {
    form.elements['page'].focus();
    return false;
  }
  
  form.submit();
  return true;
}

function docConvert (type)
{
  if (type != "")
  {
    var form = document.forms['download'];
      
    form.elements['convert_type'].value = type;
    
    submitToSelf ('download');
    
    return true;
  }
  else return false; 
}

function imgConvert (type, config)
{
  if (type != "")
  {
    var form = document.forms['download'];
      
    form.elements['convert_type'].value = type;
    form.elements['convert_cfg'].value = config;
    
    submitToSelf ('download');
    
    return true;
  }
  else return false; 
}

function switchselector (id)
{
  if (eval (id))
  {
    var selector = document.getElementById(id);
    
    if (selector.style.visibility == 'hidden') selector.style.visibility = 'visible';
    else selector.style.visibility = 'hidden';
    
    return true;
  }
  else return false;
}

function escapevalue (value)
{
  if (value != "")
  {
    return encodeURIComponent (value);
  }
  else return "";
}

<?php echo $add_onload; ?>
//-->
</script>
</head>

<body class="hcmsWorkplaceControlWallpaper">

<?php if (!$is_mobile) { ?>
<div style="position:absolute; right:0; top:0; margin:0; padding:0;">
  <img onclick="parent.document.getElementById('contentFrame').rows='44,*';" class="hcmsButtonTinyBlank" style="width:18px; height:18px;" alt="<?php echo $text65[$lang]; ?>" title="<?php echo $text65[$lang]; ?>" src="<?php echo getthemelocation(); ?>img/button_arrow_up.png" /><br />
  <img onclick="parent.document.getElementById('contentFrame').rows='100,*';" class="hcmsButtonTinyBlank" style="width:18px; height:18px;" alt="<?php echo $text66[$lang]; ?>" title="<?php echo $text66[$lang]; ?>" src="<?php echo getthemelocation(); ?>img/button_arrow_down.png" />
</div>
<?php } ?>

<?php
// define location name  
$location_name = getlocationname ($site, $location, $cat, "path");
  
if ($page == ".folder")
{
  $pagename = getobject ($location_name);      
  $location_name = getlocation ($location_name);
}

// define object name
if ($page != "" && $page != ".folder")
{
  $item = $pagecomp.":";
  $object_name = $pagename;
}
elseif ($page == ".folder")
{
  $item = $text13[$lang].":";
  $object_name = $pagename;
}    
// no object declared
else
{
  $item = "&nbsp;";
  $object_name = "&nbsp;";
}
?>

<div class="hcmsLocationBar">
  <?php if (!$is_mobile) { ?>
  <table border=0 cellspacing=0 cellpadding=0>
    <tr>
      <?php
      // location
      if ($cat == "page" || $cat == "comp")
      {
        echo "    <td class=\"hcmsHeadline\" nowrap=\"nowrap\">".$text12[$lang].": </td>
      <td class=\"hcmsHeadlineTiny\" nowrap=\"nowrap\">".$location_name."</td>\n";
      }
      else 
      {
        echo "    <td class=\"hcmsHeadline\" nowrap=\"nowrap\">".$text12[$lang].": </td>
      <td class=\"hcmsHeadlineTiny\" nowrap=\"nowrap\">".$pagecomp."</td>\n";    
      }
      ?>
    </tr>
    <tr>
      <?php
      // object
      echo "    <td class=\"hcmsHeadline\" nowrap=\"nowrap\">".$item."</td>
      <td class=\"hcmsHeadlineTiny\" nowrap=\"nowrap\">".$object_name."</td>\n";
      ?>
    </tr>
  </table>
  <?php } else { ?>
  <span class="hcmsHeadlineTiny" style="display:block;"><?php echo $location_name.$object_name; ?></span>
  <?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar">
  <div class="hcmsToolbarBlock">
    <?php if (empty ($media) && $page != ".folder") { ?>
    <img class="hcmsButton hcmsButtonSizeSquare" onClick="history.go(-2);" name="pic_obj_back" src="<?php echo getthemelocation(); ?>img/button_history_back.gif" alt="<?php echo $text47[$lang]; ?>" title="<?php echo $text47[$lang]; ?>" />
    <img class="hcmsButton hcmsButtonSizeSquare" onClick="history.go(2);" name="pic_obj_forward" src="<?php echo getthemelocation(); ?>img/button_history_forward.gif" alt="<?php echo $text48[$lang]; ?>" title="<?php echo $text48[$lang]; ?>" />
    <?php } else { ?>
    <img class="hcmsButtonOff hcmsButtonSizeSquare" name="pic_obj_back" src="<?php echo getthemelocation(); ?>img/button_history_back.gif" />
    <img class="hcmsButtonOff hcmsButtonSizeSquare" name="pic_obj_forward" src="<?php echo getthemelocation(); ?>img/button_history_forward.gif" />    
    <?php } ?>
    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    // Preview Button
    if ($page != "" && $page != ".folder" && $cat != "" && $setlocalpermission['root'] == 1)
    {echo "<img onClick=\"hcms_openBrWindowItem('page_preview.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."','preview','scrollbars=yes,resizable=yes','800','600');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_preview\" src=\"".getthemelocation()."img/button_file_preview.gif\" alt=\"".$text24[$lang]."\" title=\"".$text24[$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_file_preview.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}

    // define path variables
    if (empty ($media))
    {
      if ($cat == "page") $url_location = str_ireplace ($mgmt_config[$site]['abs_path_page'], $publ_config['url_publ_page'], $location).$page;
      elseif ($cat == "comp") $url_location = str_ireplace ($mgmt_config['abs_path_comp'], $publ_config['url_publ_comp'], $location).$page;
    }
    elseif (!empty ($media)) $url_location = $mgmt_config['url_path_cms']."explorer_wrapper.php?site=".url_encode($site)."&name=".url_encode($pagename)."&media=".url_encode($site."/".$media)."&token=".hcms_crypt($site."/".$media);
    
    // LiveView Button
    if ($file_info['published'] == true && $page != "" && $page != ".folder" && ($setlocalpermission['root'] == 1 && (($cat != "comp" && empty ($media)) || (!empty ($media) && $setlocalpermission['download'] == 1))))
    {echo "<img onClick=\"hcms_openBrWindowItem('".$url_location."','preview','location=yes,status=yes,menubar=yes,scrollbars=yes,resizable=yes','800','600');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_liveview\" src=\"".getthemelocation()."img/button_file_liveview.gif\" alt=\"".$text25[$lang]."\" title=\"".$text25[$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_file_liveview.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    
    // Download/Convert Button (also for folders) 
    // get media file extension
    if (!empty ($media))
    {
      $media_info = getfileinfo ($site, $media, $cat);
    }
    else $media_info = Null;
    
    $doc_rendering = false;
    $img_rendering = false;
    
    foreach ($mgmt_docpreview as $docpreview_ext => $docpreview)
    {
      // check file extension
      if (isset ($media_info['ext']) && substr_count ($docpreview_ext.".", $media_info['ext'].".") > 0 ) $doc_rendering = true;  
      else $doc_rendering = false;
    }
    
    $doc_rendering = $doc_rendering && is_array($mgmt_docconvert) && array_key_exists($media_info['ext'], $mgmt_docconvert);
    
    foreach ($mgmt_imagepreview as $imgpreview_ext => $imgpreview)
    {
      // check file extension
      if (substr_count ($imgpreview_ext.".", $media_info['ext'].".") > 0 )
  		{
  			// check if there are more options for providing the image in other formats
  			if (is_array ($mgmt_imageoptions) && !empty($mgmt_imageoptions))
  			{	
  				foreach ($mgmt_imageoptions as $config_fileext => $config_array) 
  				{
  					foreach ($config_array as $config_name => $config_parameter) 
  					{
  						if ($config_name != "thumbnail" && $config_name != "original") 
  						{
  							$img_rendering = true;
  							break 3;
  						}
  					}	
  				}
  			}
  		}      
    }
    
    // rendering options
    $perm_rendering = $setlocalpermission['root'] == 1 && $setlocalpermission['download'] == 1;
    $lock_rendering = ($usedby == "" || $usedby == $user);
    $dropbox_rendering = (is_array ($mgmt_config) && array_key_exists ("dropbox_appkey", $mgmt_config) && !empty ($mgmt_config['dropbox_appkey']));
    
    if (!$is_mobile) $left = "135px";
    else $left = "140px";
    
    if ($perm_rendering && $lock_rendering && $page != "" && !empty ($media) && ($doc_rendering || $img_rendering || $dropbox_rendering))
    {
      echo "<div class=\"hcmsButton hcmsButtonSizeWide\" onClick=\"switchselector ('select_obj_convert');\"><img src=\"".getthemelocation()."img/button_file_download.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" id=\"pic_obj_convert\" name=\"pic_obj_convert\" alt=\"".$text6[$lang]."\" title=\"".$text6[$lang]."\" /><img src=\"".getthemelocation()."img/pointer_select.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeNarrow\" alt=\"".$text6[$lang]."\" title=\"".$text6[$lang]."\" /></div>
        <div id=\"select_obj_convert\" class=\"hcmsSelector\" style=\"position:absolute; top:5px; left:".$left."; visibility:hidden; z-index:999; max-height:80px; overflow:auto; overflow-x:hidden; overflow-y:auto; white-space:nowrap;\">\n
        <div class=\"hcmsSelectorItem\" onclick=\"submitToSelf('download');\"><img src=\"".getthemelocation()."img/".$media_info['icon']."\" style=\"border:0; margin:0; padding:0;\" align=\"absmiddle\" />".$text89[$lang]."&nbsp;</div>\n";
        
      // document download options
      if ($doc_rendering)
      {
        if (!empty ($mgmt_docoptions['.pdf']) && in_array('.pdf', $mgmt_docconvert[$media_info['ext']])) echo "        <div class=\"hcmsSelectorItem\" onclick=\"docConvert ('pdf');\"><img src=\"".getthemelocation()."img/file_pdf.gif\" style=\"border:0; margin:0; padding:1px;\" align=\"absmiddle\" />".$text72[$lang]."&nbsp;</div>\n";
        if (!empty ($mgmt_docoptions['.doc']) && in_array('.doc', $mgmt_docconvert[$media_info['ext']])) echo "        <div class=\"hcmsSelectorItem\" onclick=\"docConvert ('doc');\"><img src=\"".getthemelocation()."img/file_doc.gif\" style=\"border:0; padding:1px;\" align=\"absmiddle\" />".$text73[$lang]."&nbsp;</div>\n";
        if (!empty ($mgmt_docoptions['.csv']) && in_array('.csv', $mgmt_docconvert[$media_info['ext']])) echo "        <div class=\"hcmsSelectorItem\" onclick=\"docConvert ('csv');\"><img src=\"".getthemelocation()."img/file_csv.gif\" style=\"border:0; padding:1px;\" align=\"absmiddle\" />".$text74[$lang]."&nbsp;</div>\n";
        if (!empty ($mgmt_docoptions['.xls']) && in_array('.xls', $mgmt_docconvert[$media_info['ext']])) echo "        <div class=\"hcmsSelectorItem\" onclick=\"docConvert ('xls');\"><img src=\"".getthemelocation()."img/file_xls.gif\" style=\"border:0; padding:1px;\" align=\"absmiddle\" />".$text75[$lang]."&nbsp;</div>\n";
        if (!empty ($mgmt_docoptions['.ppt']) && in_array('.ppt', $mgmt_docconvert[$media_info['ext']])) echo "        <div class=\"hcmsSelectorItem\" onclick=\"docConvert ('ppt');\"><img src=\"".getthemelocation()."img/file_ppt.gif\" style=\"border:0; padding:1px;\" align=\"absmiddle\" />".$text76[$lang]."&nbsp;</div>\n";
        if (!empty ($mgmt_docoptions['.html']) && in_array('.html', $mgmt_docconvert[$media_info['ext']])) echo "        <div class=\"hcmsSelectorItem\" onclick=\"docConvert ('html');\"><img src=\"".getthemelocation()."img/file_htm.gif\" style=\"border:0; padding:1px;\" align=\"absmiddle\" />".$text77[$lang]."&nbsp;</div>\n";
        if (!empty ($mgmt_docoptions['.odt']) && in_array('.odt', $mgmt_docconvert[$media_info['ext']])) echo "        <div class=\"hcmsSelectorItem\" onclick=\"docConvert ('odt');\"><img src=\"".getthemelocation()."img/file_odt.gif\" style=\"border:0; padding:1px;\" align=\"absmiddle\" />".$text78[$lang]."&nbsp;</div>\n";
        if (!empty ($mgmt_docoptions['.ods']) && in_array('.ods', $mgmt_docconvert[$media_info['ext']])) echo "        <div class=\"hcmsSelectorItem\" onclick=\"docConvert ('ods');\"><img src=\"".getthemelocation()."img/file_ods.gif\" style=\"border:0; padding:1px;\" align=\"absmiddle\" />".$text79[$lang]."&nbsp;</div>\n";
        if (!empty ($mgmt_docoptions['.odp']) && in_array('.odp', $mgmt_docconvert[$media_info['ext']])) echo "        <div class=\"hcmsSelectorItem\" onclick=\"docConvert ('odp');\"><img src=\"".getthemelocation()."img/file_odp.gif\" style=\"border:0; padding:1px;\" align=\"absmiddle\" />".$text80[$lang]."&nbsp;</div>\n";
      }
      
      // image download options
      if ($img_rendering)
      {
        foreach ($mgmt_imageoptions as $config_fileext => $config_array) 
        {
          if (is_array ($config_array)) 
          {
            $tmpname = explode (".", $config_fileext);
            $showname = strtoupper ($tmpname[1]);
            $img_info = getfileinfo ($site, $media_info['filename'].".".$tmpname[1], $cat);
            
            foreach ($config_array as $config_name => $config_parameter) 
            {
              if ($config_name != "thumbnail" && $config_name != "original") 
              {
                echo "        <div class=\"hcmsSelectorItem\" onclick=\"imgConvert ('".$tmpname[1]."', '".$config_name."');\"><img src=\"".getthemelocation()."img/".$img_info['icon']."\" style=\"border:0; margin:0; padding:1px;\" align=\"absmiddle\" />".$showname." ".$config_name."&nbsp;</div>\n";
              }
            }
          }
        }
      }
      
      //save to dropbox
			if ($dropbox_rendering)
			{
				echo "<div class=\"hcmsSelectorItem\" onclick=\"submitToWindow('popup_save_dropbox.php', 'Save to Dropbox', '', 'status=yes,scrollbars=yes,resizable=yes,width=600,height=400', '600', '400');\"><img src=\"".getthemelocation()."img/file_dropbox.png\" style=\"border:0; margin:0; padding:1px;\" align=\"absmiddle\" />".$text91[$lang]."&nbsp;</div>\n";
			}
      
      echo "    </div>\n";
    } 
    // folder/file download without options
    elseif ($perm_rendering && $lock_rendering && (!empty ($media) || $page == ".folder") && $page != "")
    {
      echo "<div class=\"hcmsButton hcmsButtonSizeWide\" onClick=\"submitToSelf('download');\">".
      "<img class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" name=\"pic_obj_liveview\" src=\"".getthemelocation()."img/button_file_download.gif\" alt=\"".$text6[$lang]."\" title=\"".$text6[$lang]."\" /><img src=\"".getthemelocation()."img/pointer_select.gif\" class=\"hcmsButtonTinyBlank hcmsButtonSizeNarrow\" alt=\"".$text71[$lang]."\" title=\"".$text71[$lang]."\" /></div>\n";
    }
    else
    {
      echo "<div class=\"hcmsButtonOff hcmsButtonSizeWide\"><img src=\"".getthemelocation()."img/button_file_download.gif\" class=\"hcmsButtonSizeSquare\" /><img src=\"".getthemelocation()."img/pointer_select.gif\" class=\"hcmsButtonSizeNarrow\" /></div>\n";
    }
    ?>
            
    <?php    
    // SendMail Button
    if ($page != "" && $mgmt_config[$site]['sendmail'] && $setlocalpermission['root'] == 1 && $setlocalpermission['sendlink'] == 1 && $mgmt_config['db_connect_rdbms'] != "")
    {
        echo "<img onClick=\"submitToWindow('user_sendlink.php', '', 'sendlink', 'scrollbars=yes,resizable=no','600','680');\" ".
                   "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_preview\" ".
                   "src=\"".getthemelocation()."img/button_user_sendlink.gif\" ".
                   "alt=\"".$text52[$lang]."\" title=\"".$text52[$lang]."\" />\n";
    }
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_user_sendlink.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    // Upload Button
    if ($html5file) $popup_upload = "popup_upload_html.php";
    else $popup_upload = "popup_upload_swf.php";
    
    if (($usedby == "" || $usedby == $user) && $cat != "page" && !empty ($media) && $application != "generator" && $page != ".folder" && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1)
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_openBrWindowItem('".$popup_upload."?uploadmode=single&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."','','status=yes,scrollbars=no,resizable=yes','600','400');\" name=\"pic_obj_upload\" src=\"".getthemelocation()."img/button_file_upload.gif\" alt=\"".$text17[$lang]."\" title=\"".$text17[$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_file_upload.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if ($usedby == "" && $page != "" && $wf_role == 5 && (($media == "" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1) || (!empty ($media) && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1)))
    {echo "<img onClick=\"if (locklayer == false) location.href='control_content_menu.php?action=page_lock&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."';\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_lock\" src=\"".getthemelocation()."img/button_file_lock.gif\" alt=\"".$text40[$lang]."\" title=\"".$text40[$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_file_lock.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\">\n";}   
    if ($usedby == $user && $page != "" && $wf_role == 5 && (($media == "" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1) || (!empty ($media) && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1)))
    {echo "<img onClick=\"if (locklayer == false) location.href='control_content_menu.php?action=page_unlock&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."';\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_unlock\" src=\"".getthemelocation()."img/button_file_unlock.gif\" alt=\"".$text41[$lang]."\" title=\"".$text41[$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_file_unlock.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}         
    ?>    
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if ($usedby == "" && ($wf_role >= 1 && $wf_role <= 4) && $page != "" && $setlocalpermission['root'] == 1)
    {echo "<img onClick=\"if (locklayer == false) hcms_openBrWindowItem('popup_message.php?action=accept&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."','popup_workflow','scrollbars=no,resizable=no','400','200');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_accept\" src=\"".getthemelocation()."img/button_workflow_accept.gif\" alt=\"".$text37[$lang]."\" title=\"".$text37[$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_workflow_accept.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}

    if ($usedby == "" && ($wf_role >= 1 && $wf_role <= 4 && $wf_id != "u.1") && $page != "" && $setlocalpermission['root'] == 1)
    {echo "<img onClick=\"if (locklayer == false) hcms_openBrWindowItem('popup_message.php?action=reject&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."','popup_workflow','scrollbars=no,resizable=no','400','200');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_reject\" src=\"".getthemelocation()."img/button_workflow_reject.gif\" alt=\"".$text38[$lang]."\" title=\"".$text38[$lang]."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation()."img/button_workflow_reject.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>    
  </div>
  <div class="hcmsToolbarBlock">  
    <?php
    // un/publish
	
    if ($page != "")
    {
      if (($usedby == "" || $usedby == $user) && $filetype != "" && $wf_role >= 3 && $setlocalpermission['root'] == 1 && $setlocalpermission['publish'] == 1)
      {
        echo "<img onClick=\"if (locklayer == false) hcms_openBrWindowItem('";
        if ($mgmt_config['db_connect_rdbms'] != "") echo "popup_publish.php";
        else echo "popup_action.php";
        echo "?action=publish&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."','','scrollbars=no,resizable=no','400','320');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_publish\" src=\"".getthemelocation()."img/button_file_publish.gif\" alt=\"".$text22[$lang]."\" title=\"".$text22[$lang]."\" />\n";
      }
      else
      {
        echo "<img src=\"".getthemelocation()."img/button_file_publish.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
      }
  
      if (($usedby == "" || $usedby == $user) && $filetype != "" && $wf_role >= 3 && $setlocalpermission['root'] == 1 && $setlocalpermission['publish'] == 1)
      {
        echo "<img onClick=\"if (locklayer == false) hcms_openBrWindowItem('";
        if ($mgmt_config['db_connect_rdbms'] != "") echo "popup_publish.php";
        else echo "popup_action.php";        
        echo "?action=unpublish&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."&token=".$token_new."','','scrollbars=no,resizable=no','400','320');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_unpublish\" src=\"".getthemelocation()."img/button_file_unpublish.gif\" alt=\"".$text23[$lang]."\" title=\"".$text23[$lang]."\" />\n";
      }
      else
      {
        echo "<img src=\"".getthemelocation()."img/button_file_unpublish.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
      }
    }
    // deactivate buttons
    else
    {
      echo "<img src=\"".getthemelocation()."img/button_file_publish.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
      echo "<img src=\"".getthemelocation()."img/button_file_unpublish.gif\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
    }
    ?>    
  </div>
  <div class="hcmsToolbarBlock">    
    <?php
    echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"if (locklayer == false) parent.frames['objFrame'].location.reload();\" name=\"pic_obj_refresh\" src=\"".getthemelocation()."img/button_view_refresh.gif\" alt=\"".$text46[$lang]."\" title=\"".$text46[$lang]."\" />\n";
    ?>    
  </div>
  <div class="hcmsToolbarBlock">    
    <?php
    if (!$is_mobile && file_exists ("help/usersguide_".$lang_shortcut[$lang].".pdf") && $setlocalpermission['root'] == 1)
    {echo "<a href=# onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('pic_obj_help','','".getthemelocation()."img/button_help_over.gif',1)\" onClick=\"hcms_openBrWindowItem('help/usersguide_".$lang_shortcut[$lang].".pdf','help','scrollbars=no,resizable=yes','800','600');\"><img name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".$text50[$lang]."\" title=\"".$text50[$lang]."\" /></a>\n";}
    ?>
  </div>
</div>

<?php
echo showmessage ($show, 650, 60, $lang, "position:absolute; left:15px; top:15px; ");
?>
<!-- form used by send_link function -->
<form target="_blank" method="post" action="" name="pagemenu_object">
<input type="hidden" name="action" value="">
<input type="hidden" name="force" value="start">
<input type="hidden" name="site" value="<?php echo $site; ?>">
<input type="hidden" name="cat" value="<?php echo $cat; ?>">
<input type="hidden" name="location" value="<?php echo $location_esc; ?>">
<input type="hidden" name="page" value="<?php echo $cat; ?>">
<input type="hidden" name="pagename" value="<?php echo $pagename; ?>">
<input type="hidden" name="folder" value="<?php echo $folder; ?>">
<input type="hidden" name="token" value="<?php echo $token_new; ?>" >
</form>

<div id="objcreateLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:<?php if ($page != "") echo "hidden"; else echo "visible"; ?>">
<form name="page_create" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return checkForm_page_create();">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="wf_token" value="<?php echo $wf_token; ?>" />
  <input type="hidden" name="wf_role" value="<?php echo $wf_role; ?>" />
  <input type="hidden" name="contentfile" value="<?php echo $contentfile; ?>" />
  <input type="hidden" name="action" value="page_create" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>">
  
  <table border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td>
        <?php echo $text16[$lang]." ".$text27[$lang]; ?>:
      </td>
      <td>
        <input type="text" name="page" maxlength="<?php if (!is_int ($mgmt_config['max_digits_filename'])) echo $mgmt_config['max_digits_filename']; else echo "200"; ?>" style="width:220px;" />
        <img name="Button3" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_page_create();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button3','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" title="OK" />
      </td>
    </tr>
    <tr>
      <td>
        <?php echo $text28[$lang]; ?>:
      </td>
      <td>
        <select name="template" onChange="hcms_jumpMenu('parent.frames[\'objFrame\']',this,0)" style="width:220px;">
          <option value="empty.php">--- <?php echo $text36[$lang]; ?> ---</option>
          <?php
          $template_array = gettemplates ($site, $cat);
          
          if (is_array ($template_array))
          {
            foreach ($template_array as $value)
            {
              if ($cat == "page" || strpos ($value, ".page.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".page.tpl"));
              elseif ($cat == "comp" || strpos ($value, ".comp.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".comp.tpl"));
              
              echo "<option value=\"template_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&template=".url_encode($value)."\">".$tpl_name."</option>\n";
            }
          }
          else 
          {
            echo "<option value=\"\"> ----------------- </option>\n";
          }          
          ?>
        </select>
      </td>
    </tr>
  </table>
</form>
</div>

<?php
if ($page != "")
{
  echo "<div id=\"Layer_tab\" class=\"hcmsTabContainer\" style=\"position:absolute; z-index:10; visibility:visible; left:0px; top:77px\">
    <table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" style=\"z-index:1;\">
      <tr>
        <td style=\"width:3px;\"><img src=\"".getthemelocation()."img/backgrd_tabs_spacer.gif\" style=\"width:3px; height:3px; border:0;\" /></td>
        <td align=\"left\" valign=\"top\" class=\"hcmsTab\">&nbsp;
          <a href=\"page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&ctrlreload=no\" target=\"objFrame\" onClick=\"hcms_showHideLayers('Layer_tab1','','show','Layer_tab2','','hide','Layer_tab3','','hide','Layer_tab4','','hide')\">".$pagecomp."</a>
        </td>
        <td style=\"width:3px;\"><img src=\"".getthemelocation()."img/backgrd_tabs_spacer.gif\" style=\"width:3px; height:3px; border:0;\" /></td>
        <td align=\"left\" valign=\"top\" class=\"hcmsTab\">&nbsp;\n";
          if (($usedby == "" || $usedby == $user) && ($wf_role >= 4 || $wf_role == 2) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1) 
            echo "<a href=\"frameset_template_change.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&template=".url_encode($template)."\" target=\"objFrame\" onClick=\"hcms_showHideLayers('Layer_tab1','','hide','Layer_tab2','','show','Layer_tab3','','hide','Layer_tab4','','hide')\">".$text31[$lang]."</a>";
          else echo "<b>".$text31[$lang]."</b>";
        echo "</td>
        <td style=\"width:3px;\"><img src=\"".getthemelocation()."img/backgrd_tabs_spacer.gif\" style=\"width:3px; height:3px; border:0;\" /></td>
        <td align=\"left\" valign=\"top\" class=\"hcmsTab\">&nbsp;\n";
          if (($usedby == "" || $usedby == $user) && ($wf_role >= 4 || $wf_role == 2) && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
            echo "<a href=\"version_content.php?site=".url_encode($site)."&location=".url_encode($location_esc)."&page=".url_encode($page)."\" target=\"objFrame\" onClick=\"hcms_showHideLayers('Layer_tab1','','hide','Layer_tab2','','hide','Layer_tab3','','show','Layer_tab4','','hide')\">".$text32[$lang]."</a>";
          else echo "<b>".$text32[$lang]."</b>";
        echo "</td>
        <td style=\"width:3px;\"><img src=\"".getthemelocation()."img/backgrd_tabs_spacer.gif\" style=\"width:3px; height:3px; border:0;\" /></td>
        <td align=\"left\" valign=\"top\" class=\"hcmsTab\">&nbsp;\n";
          if (($usedby == "" || $usedby == $user) && $wf_role >= 1 && $setlocalpermission['root'] == 1 && ($cat == "page" || $setlocalpermission['download'] == 1)) 
            echo "<a href=\"page_info.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."\" target=\"objFrame\" onClick=\"hcms_showHideLayers('Layer_tab1','','hide','Layer_tab2','','hide','Layer_tab3','','hide','Layer_tab4','','show')\">".$text33[$lang]."</a>";
          else echo "<b>".$text33[$lang]."</b>";
        echo "</td>
      </tr>
    </table>
  </div>\n";
   
  echo "<div id=\"Layer_tab1\" class=\"hcmsWorkplaceGeneric\" style=\"position:absolute; width:118px; height:2px; z-index:20; visibility:visible; left:4px; top:99px;\"> </div>\n";
  echo "<div id=\"Layer_tab2\" class=\"hcmsWorkplaceGeneric\" style=\"position:absolute; width:118px; height:2px; z-index:20; visibility:hidden; left:127px; top:99px;\"> </div>\n";
  echo "<div id=\"Layer_tab3\" class=\"hcmsWorkplaceGeneric\" style=\"position:absolute; width:118px; height:2px; z-index:20; visibility:hidden; left:250px; top:99px;\"> </div>\n";
  echo "<div id=\"Layer_tab4\" class=\"hcmsWorkplaceGeneric\" style=\"position:absolute; width:118px; height:2px; z-index:20; visibility:hidden; left:373px; top:99px;\"> </div>\n"; 
}
?>

<div id="downloadLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "80%"; else echo "650px"; ?>; height:60px; z-index:5; left:15px; top:10px; visibility:<?php echo ($action == 'download' ? 'visible' : 'hidden'); ?>;" >
<form name="download" action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
  <input type="hidden" name="action" value="download" />
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="page" value="<?php echo $page; ?>" />
  <input type="hidden" name="wf_token" value="<?php echo $wf_token; ?>" />
  <input type="hidden" name="convert_type" value="" />
  <input type="hidden" name="convert_cfg" value="" />

  <table width="100%" height="60" border=0 cellspacing=0 cellpadding=3 class="hcmsMessage">
    <tr>
      <td align="left" valign="middle">
        <div style="width:100%; height:100%; z-index:10; overflow:auto;">
          <?php
          // iPhone download
          if ($action == "download" && $is_iphone)
          { 
            $downloadlink = createmultidownloadlink ($site, $multiobject, $media, $location.$folder, $pagename, $user, $convert_type, $convert_cfg);
            
            echo "<a href=\"".$downloadlink."\" class=\"button hcmsButtonGreen\" target=\"_blank\">".$text92[$lang]."</a>";
          }
          else
          {
            echo $text61[$lang];
          }
          ?>
        </div>
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose5" src="<?php echo getthemelocation(); ?>img/button_close.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo $text34[$lang]; ?>" title="<?php echo $text34[$lang]; ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose5','','<?php echo getthemelocation(); ?>img/button_close_over.gif',1);" onClick="hcms_showHideLayers('downloadLayer','','hide');" />
      </td>        
    </tr>
  </table>
</form>
</div>
<?php 
if ($action == "download" && !$is_iphone)
{
  $downloadlink = createmultidownloadlink ($site, "", $media, $location, $pagename, $user, $convert_type, $convert_cfg);
  
  if ($downloadlink != "")
  {
?>
<script type="text/javascript">
<!--
function downloadFile()
{
  hcms_showHideLayers('downloadLayer','','hide');
  location.replace('<?php echo $downloadlink; ?>','popup_download');
}

setTimeout('downloadFile()', 1000);
-->
</script>  
<?php
  }
  // download failed (zip file could not be created)
  else
  {
    echo showmessage (str_replace ("%filesize%", $mgmt_config['maxzipsize'], $text81[$lang]), 650, 60, $lang, "position:absolute; left:15px; top:15px; ");
  }
}
?>

</body>
</html>
