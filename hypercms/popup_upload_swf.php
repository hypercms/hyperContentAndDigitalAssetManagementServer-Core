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
$location = getrequest_esc ("location", "locationname");
$object = getrequest_esc ("page", "objectname");
$filetype = getrequest_esc ("filetype", "objectname");
$media = getrequest_esc ("media", "objectname");
$uploadmode = getrequest ("uploadmode");

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
if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['upload'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// load object file and get container and media file
$objectdata = loadfile ($location, $object);
$media = getfilename ($objectdata, "media");

// max digits in file name
if (!is_int ($mgmt_config['max_digits_filename'])) $mgmt_config['max_digits_filename'] = 200;

// get file info
$file_info = getfileinfo ($site, $location.$object, $cat);

// create secure token
$token = createtoken ($user);

// check storage limit (MB)
if (isset ($mgmt_config[$site]['storage_limit']) && $mgmt_config[$site]['storage_limit'] > 0)
{
  // memory for file size (should be kept for 24 hours)
  $filesize_mem = $mgmt_config['abs_path_temp'].$site.".filesize.dat";
  
  if (!is_file ($filesize_mem) || (filemtime ($filesize_mem) + 86400) < time())
  {  
    // this function might require some time for the result in case of large databases
    $filesize = rdbms_getfilesize ("", "%comp%/".$site."/");
    savefile ($mgmt_config['abs_path_temp'], $site.".filesize.dat", $filesize['filesize']);
  }
  else $filesize['filesize'] = loadfile ($mgmt_config['abs_path_temp'], $site.".filesize.dat");

  if ($filesize['filesize'] > ($mgmt_config[$site]['storage_limit'] * 1024))
  {
    echo showinfopage ($hcms_lang['storage-limit-exceeded'][$lang], $lang);
    exit;
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#464646" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" type="text/css">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/file_upload.css" type="text/css" />
<script type="text/javascript" src="javascript/main.js"></script>
<script type="text/javascript" src="javascript/click.js"></script>
<script type="text/javascript" src="javascript/swfupload/swfupload.js"></script>
<script type="text/javascript" src="javascript/swfupload/swfupload.queue.js"></script>
<script type="text/javascript" src="javascript/swfupload/fileprogress.js"></script>
<script type="text/javascript" src="javascript/swfupload/handlers.js"></script>
<script type="text/javascript">

var swfu;

window.onload = function()
{
  var settings = {
				flash_url : "<?php echo $mgmt_config['url_path_cms']; ?>javascript/swfupload/swfupload_f10.swf",
				upload_url: "<?php echo $mgmt_config['url_path_cms']; ?>service/uploadfile.php",
				post_params: {"PHPSESSID" : "<?php echo session_id(); ?>",
          "site" : "<?php echo $site; ?>",
          "location" : "<?php echo $location_esc; ?>",
          "page" : "<?php echo $object; ?>",
          "media_update" : "<?php echo $media; ?>",
          "cat" : "<?php echo $cat; ?>",
          "user" : "<?php echo $user; ?>",
          "unzip" : "",
          "createthumbnail" : "",
          "imageresize" : "",
          "imagepercentage" : "",
          "checkduplicates" : "<?php if ($mgmt_config['check_duplicates']) echo '1'; ?>",
          "versioning" : "",
          "deletedate" : "",
          "token" : "<?php echo $token; ?>"},
				file_size_limit : "",
				file_types : "*.*",
				file_types_description : "All Files",
				file_upload_limit : 500,
				file_queue_limit : <?php if ($uploadmode == "multi") echo "500"; else echo "1"; ?>,
				custom_settings : {
  				progressTarget : "fsUploadProgress",
  				cancelButtonId : "btnCancel"
				},
        
				// Button settings
				button_image_url: "<?php echo getthemelocation(); ?>img/button_upload.gif",
				button_width: "160",
				button_height: "22",
				button_placeholder_id: "spanButtonPlaceHolder",
				button_text: '<span class="button_upload"><?php echo getescapedtext ($hcms_lang['upload-files'][$lang]); ?></span>',
        button_text_style: ".button_upload { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 9pt; font-weight: normal; color: #FFFFFF; }",
				button_text_left_padding: 12,
				button_text_top_padding: 3, 
        button_action: <?php if ($uploadmode == "multi") echo "SWFUpload.BUTTON_ACTION.SELECT_FILES"; elseif ($uploadmode == "single") echo "SWFUpload.BUTTON_ACTION.SELECT_FILE"; ?>,       

				// The event handler functions are defined in handlers.js
				file_queued_handler : fileQueued,
				file_queue_error_handler : fileQueueError,
				file_dialog_complete_handler : fileDialogComplete,
				upload_start_handler : uploadStart,
				upload_progress_handler : uploadProgress,
				upload_error_handler : uploadError,
				upload_success_handler : uploadSuccess,
				upload_complete_handler : uploadComplete,
				queue_complete_handler : queueComplete,	// Queue plugin event
        
        // Debug settings
        debug: false        
	};

  swfu = new SWFUpload(settings);
};

function setpost_multi ()
{
  var percentage = document.forms['upload'].elements['imagepercentage'];
  var resize = document.forms['upload'].elements['imageresize'];
  var unzip = document.forms['upload'].elements['unzip'];
  var checkduplicates = document.forms['upload'].elements['checkduplicates'];
  var deleteobject = document.forms['upload'].elements['deleteobject'];
  var deletedate = document.forms['upload'].elements['deletedate'];
  
  var imageresize = '';
  var imagepercentage = '';
  var fileunzip = '';
  var filecheckduplicates = '';
  var filedeletedate = '';
  
  if (resize.checked == true && (percentage.value > 0 && percentage.value <= 200))
  {
    percentage.disabled = false;
    imageresize = 'percentage';
    imagepercentage = percentage.value;
  }
  else
  {
    if (resize.checked == true)
    {
      alert (hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['the-resize-value-must-be-between-1-and-200-'][$lang]); ?>'));
      percentage.disabled = false;
    }
    else percentage.disabled = true;
  }
  
  if (unzip.checked == true)
  {
    fileunzip = '1';
  }
  
  if (checkduplicates.checked == true)
  {
    filecheckduplicates = '1';
  }
  
  if (deleteobject.checked == true)
  {
    if (deletedate.value == "")
    {
      alert (hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['please-set-a-delete-date-for-the-files'][$lang]); ?>'));
    }
    else filedeletedate = deletedate.value;
  }
  
  swfu.setPostParams({'PHPSESSID' : '<?php echo session_id(); ?>', 'site' : '<?php echo $site; ?>', 'location' : '<?php echo $location_esc; ?>', 'cat' : '<?php echo $cat; ?>', 'user' : '<?php echo $user; ?>', 'unzip' : fileunzip, 'imageresize' : imageresize, 'imagepercentage' : imagepercentage, 'checkduplicates' : filecheckduplicates, 'deletedate' : filedeletedate, 'token' : '<?php echo $token; ?>'});
  return true;
}

function setpost_single ()
{
  var versioning = '';
  var createthumbnail = ''; 
  
  if (eval(document.forms['upload'].elements['versioning']))
  {  
    if (document.forms['upload'].elements['versioning'].checked == true) versioning = '1';
  }
  
  if (eval(document.forms['upload'].elements['createthumbnail']))
  {
    if (document.forms['upload'].elements['createthumbnail'].checked == true) createthumbnail = '1';
  }

  swfu.setPostParams({'PHPSESSID' : '<?php echo session_id(); ?>', 'site' : '<?php echo $site; ?>', 'location' : '<?php echo $location_esc; ?>', 'page' : '<?php echo $object; ?>', 'media_update' : '<?php echo $media; ?>', 'cat' : '<?php echo $cat; ?>', 'user' : '<?php echo $user; ?>', 'versioning' : versioning, 'createthumbnail' : createthumbnail, 'token' : '<?php echo $token; ?>'});
    
  if (createthumbnail.checked == true) swfu.setFileTypes('*.jpg; *.jpeg', 'JPEG-Datei');
  else swfu.setFileTypes('*.*', 'All Files');
  
  return true;
}

function getMessage (msgstring)
{
  if (msgstring != "" && msgstring.indexOf("[") > 0 && msgstring.indexOf("]") > 0)
  {
    filepos = msgstring.indexOf("[");
    message = msgstring.substr (0, filepos);
    return message;
  }
  else return msgstring;
}

function getPage (msgstring)
{
  if (msgstring != "" && msgstring.indexOf("[") > 0 && msgstring.indexOf("]") > 0)
  {
    filepos1 = msgstring.indexOf("[");
    filepos2 = msgstring.indexOf("]");
    page = msgstring.substr (filepos1 + 1, filepos2 - filepos1 - 1);
    return page;
  }
  else return "";
}

function translatemessage (errorno)
{
  if (errorno != "")
  {
    if (errorno == 500) return "<?php echo getescapedtext ($hcms_lang['you-dont-have-permissions-to-use-this-function'][$lang]); ?>";
    else if (errorno == 501) return "<?php echo getescapedtext ($hcms_lang['file-could-not-be-saved-or-only-partialy-saved'][$lang]); ?>";
    else if (errorno == 502) return "<?php echo getescapedtext ($hcms_lang['no-file-selected-to-upload'][$lang]); ?>";
    else if (errorno == 503) return "<?php echo getescapedtext (str_replace ("%maxdigits%", $mgmt_config['max_digits_filename'], $hcms_lang['the-file-name-has-more-than-maxdigits-digits'][$lang])); ?>";
    else if (errorno == 504) return "<?php echo getescapedtext ($hcms_lang['the-file-you-are-trying-to-upload-already-exists'][$lang]); ?>";
    else if (errorno == 505) return "<?php echo getescapedtext ($hcms_lang['the-file-you-are-trying-to-upload-is-too-big'][$lang]); ?>";
    else if (errorno == 506) return "<?php echo getescapedtext ($hcms_lang['the-file-you-are-trying-to-upload-is-of-wrong-type'][$lang]); ?>";
    else if (errorno == 507) return "<?php echo getescapedtext ($hcms_lang['file-could-not-be-extracted'][$lang]); ?>";
    else if (errorno == 508) return "<?php echo getescapedtext ($hcms_lang['the-request-holds-invalid-parameters'][$lang]); ?>";
    else if (errorno == 509) return "<?php echo getescapedtext ($hcms_lang['invalid-input-parameters'][$lang]); ?>";
    else if (errorno == 510) return "<?php echo getescapedtext (str_replace ("%files%", "<b>No HTML5 File Support!</b>", $hcms_lang['there-are-files-with-the-same-content-files'][$lang])); ?>";
  }
}
    
function frameReload (newpage)
{
  // reload main frame (upload by control objectlist)
  if (eval (opener.parent.frames['mainFrame']))
  {
    opener.parent.frames['mainFrame'].location.reload();
  }

  // reload explorer frame (upload by component explorer)
  if (eval (opener.parent.frames['navFrame2']))
  {
    opener.parent.frames['navFrame2'].location.reload();
  }
  // reload object frame (upload by control content)
  else if (eval (opener.parent.frames['objFrame']))
  {
    if (newpage == "") opener.parent.frames['objFrame'].location.reload();
    else opener.parent.frames['objFrame'].location='page_view.php?ctrlreload=yes&site=<?php echo $site; ?>&cat=<?php echo $cat; ?>&location=<?php echo $location_esc; ?>&page='+newpage;
    
    setTimeout('window.close()', 1000);
  }
  
  return true;
}
</script>

<link rel="stylesheet" type="text/css" href="javascript/rich_calendar/rich_calendar.css">
<script type="text/javascript" src="javascript/rich_calendar/rich_calendar.js"></script>
<script type="text/javascript" src="javascript/rich_calendar/rc_lang_en.js"></script>
<script type="text/javascript" src="javascript/rich_calendar/rc_lang_de.js"></script>
<script type="text/javascript" src="javascript/rich_calendar/domready.js"></script>
<script type="text/javascript">

var cal_obj = null;
var format = '%Y-%m-%d %H:%i';

// show calendar
function show_cal (el)
{
	if (cal_obj) return;

  var text_field = document.getElementById("text_field");

	cal_obj = new RichCalendar();
	cal_obj.start_week_day = 1;
	cal_obj.show_time = true;
	cal_obj.language = '<?php echo getcalendarlang ($lang); ?>';
	cal_obj.user_onchange_handler = cal_on_change;
	cal_obj.user_onautoclose_handler = cal_on_autoclose;
	cal_obj.parse_date(text_field.value, format);
	cal_obj.show_at_element(text_field, "adj_left-bottom");
}

// user defined onchange handler
function cal_on_change (cal, object_code)
{
	if (object_code == 'day')
	{
		document.getElementById("text_field").value = cal.get_formatted_date(format);
		document.getElementById("deletedate").value = cal.get_formatted_date(format);
    setpost_multi();
		cal.hide();
		cal_obj = null;
	}
}

// user defined onautoclose handler
function cal_on_autoclose (cal)
{
	cal_obj = null;
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" leftmargin=3 topmargin=3 marginwidth=0 marginheight=0>

<!-- top bar -->
<?php
if ($uploadmode == "multi") $title = getescapedtext ($hcms_lang['upload-files-to-location'][$lang]);
else $title = getescapedtext ($hcms_lang['upload-new-file-in'][$lang]);

if ($uploadmode == "multi")
{
  $object_name = getlocationname ($site, $location, $cat, "path");
}
else
{
  $fileinfo = getfileinfo ($site, $object, $cat);
  $object_name = $fileinfo['name'];
}

echo showtopbar ($title.": ".$object_name, $lang);
?>

<div id="content">
    <form name="upload" id="upload" action="" method="post" enctype="multipart/form-data">
  	<fieldset class="flash" id="fsUploadProgress">
	</fieldset>
	<div><div id="divStatus" style="float:left;">0</div>&nbsp;<?php echo getescapedtext ($hcms_lang['files-uploaded'][$lang]); ?><br /></div>
        <br />
	<div>
        <?php if ($uploadmode == "multi" && is_array ($mgmt_uncompress) && sizeof ($mgmt_uncompress) > 0) { ?>
        <input type="checkbox" name="unzip" id="unzip" value="1" onclick="setpost_multi();" />&nbsp;<?php echo getescapedtext ($hcms_lang['uncompress-files'][$lang]); ?><br />
        <?php } elseif ($uploadmode == "single") { ?> 
          <?php if (empty ($mgmt_config['contentversions']) || $mgmt_config['contentversions'] == true) { ?>
          <input type="checkbox" name="versioning" id="versioning" value="1" onchange="setpost_single();" />&nbsp;<?php echo getescapedtext ($hcms_lang['keep-existing-file-as-old-version'][$lang]); ?><br />
          <?php } ?> 
          <input type="checkbox" name="createthumbnail" id="createthumbnail" value="1" onchange="setpost_single();" />&nbsp;<?php echo getescapedtext ($hcms_lang['thumbnail-image-jpeg-file'][$lang]); ?><br />
        <?php } ?> 
        <?php if ($uploadmode == "multi" && is_array ($mgmt_imagepreview) && sizeof ($mgmt_imagepreview) > 0) {  ?>
          <input type="checkbox" name="imageresize" id="imageresize" value="percentage" onchange="setpost_multi();" />
          <?php echo getescapedtext ($hcms_lang['resize-images-gif-jpeg-png-by-percentage-of-original-size-100'][$lang]); ?>: <input name="imagepercentage" id="imagepercentage" type="text" onkeyup="setpost_multi();" size="3" maxlength="3" value="100" disabled="disabled" /> %<br />
        <?php } ?>
          <input type="checkbox" name="checkduplicates" id="checkduplicates" value="1" onchange="setpost_multi();" <?php if ($mgmt_config['check_duplicates']) echo 'checked="checked"'; ?> />&nbsp;<?php echo getescapedtext ($hcms_lang['check-for-duplicates'][$lang]); ?><br />
        <?php if ($uploadmode == "multi") { ?>
          <input type="checkbox" name="deleteobject" id="deleteobject" value="1" onchange="setpost_multi();" />&nbsp;<?php echo getescapedtext ($hcms_lang['remove-uploaded-files-on'][$lang]); ?>
          <input type="hidden" name="deletedate" id="deletedate" value="<?php echo date ("Y-m-d", (time()+60*60*24)); ?> 00:00" />
          <input type="text" id="text_field" value="<?php echo date ("Y-m-d", (time()+60*60*24)); ?> 00:00" disabled="disabled" />
          <img id="datepicker" name="datepicker" src="<?php echo getthemelocation(); ?>img/button_datepicker.gif" onclick="show_cal(this);" align="absmiddle" class="hcmsButtonTiny hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" /><br />
        <?php } ?>
        <br />
        <table cellspacing="3">
          <tr>
            <td><span id="spanButtonPlaceHolder"></span></td>    
            <td><input id="btnCancel" type="button" class="hcmsButtonOrange" value="<?php echo getescapedtext ($hcms_lang['cancel-all-uploads'][$lang]); ?>" onclick="swfu.cancelQueue();" disabled="disabled" style="height:22px;" /></td>
          </tr>
        </table>
      </div>
    </form>
</div>

</body>
</html>
