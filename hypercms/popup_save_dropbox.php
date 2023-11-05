<?php
/*
 convert bytes to most appropriate units (B,KB,MB,GB or TB)
 */
 function bytesToUnits($bytes)
 {
	if ($bytes <= 0) return 'n/a';
	
	$units = array("B", "KB", "MB", "GB", "TB");
	$i = intval(floor(log($bytes) / log(1024))); 
	return round($bytes / pow(1023, $i), 2)." ".$units[$i];
 }
 /*
  prepare file name for displaying, cut out text if exceed maxlength
 */
 function prepareFilename($filename)
 {
	$maxLen = 49;
  $moreThanMaxLen = '...';
	return ((strlen($filename) > $maxLen) ? substr(0, $maxLen-strlen($moreThanMaxLen)).$moreThanMaxLen : $filename);
 }

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
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$multiobject = getrequest ("multiobject", "objectname");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// publication management config
if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
{
  require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
}

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['upload'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// extract and prepare information for uploading to dropbox
$saveObjects = array();
$displayObjects = array();
$multiObjectArray = array();

if ((!empty ($multiobject) || !empty ($page)) && !empty ($location))
{
	if (!empty ($multiobject))
	{
		if (strpos ($multiobject, "|") === 0 && strlen ($multiobject) > 2) $multiobject = substr ($multiobject, 1);
		$multiObjectArray = explode ("|", $multiobject);
	}
	else if (!empty ($page) && !empty ($location)) $multiObjectArray[] = convertpath ($site, $location.$page, $cat);
	
	if (!empty ($multiObjectArray))
	{
		foreach ($multiObjectArray as $object)
    {
			$objectLocation = getlocation ($object);
			$objectFile = getobject ($object);
      $objectInfo = getobjectinfo ($site, $objectLocation, $objectFile);
      
      if (empty ($objectInfo['media'])) continue;
      
			$downlaodlink = createdownloadlink ($site, $objectLocation, $objectFile, $cat, "", "", "", "", true);
			$objectFileInfo = getfileinfo ($site, $object, $cat);
			$objectMediaPath = getmedialocation ($site, $objectInfo['media'], "abs_path_media").$site."/".$objectInfo['media'];
			$objectMediaSize = filesize ($objectMediaPath);
			$saveObjects[] = "{'filename': '".$objectFileInfo['name']."', 'url': '".$downlaodlink."'}";
			$displayObjects[] = array ("name" => $objectFileInfo['name'], "size" => $objectMediaSize);
		}
	}
}

//prepare info for topbar
$title = getescapedtext ($hcms_lang['save-files-to-dropbox-from'][$lang]);
$object_name = getlocationname ($site, $location, $cat, "path");
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" type="text/css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/jquery-fileupload.css" type="text/css" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<!-- JQuery -->
<script src="javascript/jquery/jquery.min.js" type="text/javascript"></script>
<!-- JQuery UI -->
<script src="javascript/jquery-ui/jquery-ui.min.js" type="text/javascript"></script>
<link rel="stylesheet" href="javascript/jquery-ui/jquery-ui.css" type="text/css" />
<!-- Dropbox dropin.js -->
<script type="text/javascript" src="https://www.dropbox.com/static/api/1/dropins.js" id="dropboxjs" data-app-key="<?php echo $mgmt_config['dropbox_appkey']; ?>"></script>
<script type="text/javascript">

// override window open function
var childwindows = [];

// saving original function
window._open = window.open;

window.open = function(url,name,params) {
    var windowRef = window._open(url, name, params);
		childwindows.push(windowRef);
		return windowRef;
}

$(function() {
 <?php
	if (!empty ($saveObjects))
	{	
 ?> 
	var options = {
    files: [ <?php echo implode (",",  $saveObjects); ?> ],
    success: function() { 
			$("#progress").hide();
			$('#success').show();
			$('#btnCancel').hide();
			$('.file').removeClass('file_normal').addClass('file_success');
		},
		cancel: function() { 
			$("#progress").hide();
			$('#success').hide();
			$('#btnCancel').hide();
			$('#btnUpload').show();			
		},
		error: function(err) { alert(err); },
	}
	
	$('#btnCancel').hide();
	$('#progress').hide();
	$('#success').hide();
	
	$("#btnUpload").click( function() {
		$('#btnCancel').show();
		$('#progress').show();
		$('#btnUpload').hide();
		Dropbox.save(options);
	});
<?php 
}
?>
	$("#btnCancel").click( function() {
		for (var i=0; i<childwindows.length; i++)
    {
			 childwindows[i].close();
		}
		window.close();
	});
	
});
</script>
</head>

<body class="hcmsWorkplaceGeneric">

  <!-- top bar -->
  <?php
  echo showtopbar ($title.": ".$object_name, $lang);
  ?>
    
  <div class="hcmsWorkplaceBar" id="progress">
    <div style="padding:6px;">
      <div class="hcmsHeadline" style="float:left;"><?php echo getescapedtext ($hcms_lang['saving-files-to-dropbox'][$lang]); ?></div>
      <div style="float: left; margin-left:20px; margin-top:2px;"><img src="/cms_dev/theme/standard/img/loading.gif" /></div>
    </div>
  </div>
  
  <div class="hcmsWorkplaceBar" id="success">
    <div style="padding:6px;">
      <div class="hcmsHeadline" style="float:left;"><?php echo getescapedtext ($hcms_lang['successfully-saved-files-to-dropbox-'][$lang]); ?></div>
    </div>
  </div>
  
  <div id="content" class="hcmsWorkplaceFrame">
  
    <div id="selectedFiles">
    <?php 
    if (!empty ($displayObjects)) 
    { 
      foreach ($displayObjects as $displayObject) 
      {
    ?>
      <div class="file file_normal">
        <div class="inline file_name"><?php echo prepareFilename ($displayObject['name']); ?></div>
        <div class="inline file_size" style="float:right; width:80px;"><?php echo bytesToUnits ($displayObject['size']); ?></div>
      </div>
    <?php 	
      }
    }
    else
    {
    ?>
      <div class="file file_error">
        <div class="inline file_name"><?php echo getescapedtext ($hcms_lang['no-files-selected'][$lang]);; ?></div>
        <div class="inline file_size" style="float:right; width:80px;">&nbsp;</div>
      </div>	
    <?php 	
    }
    ?>
    </div>
    
    <div>
    <?php 
    if (!empty ($displayObjects)) 
    {
    ?>
      <div id="btnUpload" class="button hcmsButtonBlue" ><?php echo getescapedtext ($hcms_lang['save'][$lang]); ?></div>
    <?php 
    }
    ?>
      <div id="btnCancel" class="button hcmsButtonOrange" ><?php echo getescapedtext ($hcms_lang['cancel'][$lang]); ?></div>
    </div>
    <br />
    <br />
    
  </div>

<?php includefooter(); ?>
</body>
</html>