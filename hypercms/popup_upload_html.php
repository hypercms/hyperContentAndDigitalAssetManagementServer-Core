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
require_once ("language/popup_upload.inc.php");


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

if (!$lang) $lang = 'de';

// load object file and get container and media file
$objectdata = loadfile ($location, $object);
$media = getfilename ($objectdata, "media");

// max digits in file name
if (!is_int ($mgmt_config['max_digits_filename'])) $mgmt_config['max_digits_filename'] = 200;

// get file info
$file_info = getfileinfo ($site, $location.$object, $cat);

// create secure token
$token = createtoken ($user);

// max files in queue
if ($uploadmode == "single") $maximumQueueItems = 1;
else $maximumQueueItems = -1;

// check storage limit (MB)
if (isset ($mgmt_config[$site]['storage']) && $mgmt_config[$site]['storage'] > 0)
{
  $filesize = rdbms_getfilesize ("", "%comp%/".$site."/");

  if ($filesize['filesize'] > ($mgmt_config[$site]['storage'] * 1024))
  {
    echo showinfopage ($text32[$lang], $lang);
    exit;
  }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
<title>hyperCMS</title>
<meta name="viewport" content="width=device-width; initial-scale=1.0; user-scalable=1;">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" type="text/css">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/jquery-fileupload.css" type="text/css">

<script src="javascript/main.js" type="text/javascript"></script>
<!-- <script src="javascript/click.js" type="text/javascript"></script> -->

<!-- JQuery -->
<script src="javascript/jquery/jquery-1.9.1.min.js" type="text/javascript"></script>

<!-- JQuery UI -->
<script src="javascript/jquery-ui/jquery-ui-1.10.2.min.js" type="text/javascript"></script>
<link rel="stylesheet" href="javascript/jquery-ui/jquery-ui-1.10.2.css" type="text/css">

<!-- JQuery File Upload -->
<script src="javascript/jquery/plugins/jquery.fileupload.js" type="text/javascript"></script>
<script src="javascript/jquery/plugins/jquery.iframe-transport.js" type="text/javascript"></script>
<!-- Dropbox dropin.js -->
<script type="text/javascript" src="https://www.dropbox.com/static/api/1/dropins.js" id="dropboxjs" data-app-key="<?php if (!empty ($mgmt_config['dropbox_appkey'])) echo $mgmt_config['dropbox_appkey']; ?>"></script>

<!-- File Upload Code -->
<script type="text/javascript">
  
$(function ()
{
  // Uploaded files count
  var filecount = 0;
  // Selected files count
  var selectcount = 0;
	// Time until an item is removed from the queue
	// After it is successfully transmitted
	// In Miliseconds
	var hcms_waitTillRemove = 5*1000;
	// Time after which the window is closed
	// Currently only applies to single uploads
	// In Miliseconds
	var hcms_waitTillClose =  5*1000;
	// Number of Items which can be in the queue at the same time
	// negative value or NaN values mean unlimited
	var hcms_maxItemInQueue = <?php echo $maximumQueueItems; ?>;
	//parameter indicating unzip
	var unzip = false;
	//parameter indicating resize
  var resize = false;
	// percentage of resize
  var percent = 100;
  // shall duplicates be checked
  var checkduplicates = false;
  // shall versioning be enabled
  var versioning = false;
  // delete objects on given date
  var deletedate = false;
  
  // Function fetched from the world wide web by 
  // http://codeaid.net/javascript/convert-size-in-bytes-to-human-readable-format-%28javascript%29
  // Ben Timby
  function bytesToSize (bytes)
  {
    var sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    if (bytes == 0) return 'n/a';
    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
    return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[[i]];
  };
    
  // Function that generate a jquery span field containing the name of the file
  function getFileNameSpan (name)
  {
    
    var maxLen = 49;
    var moreThanMaxLen = '...';
    
    var span = $('<div></div>');
    span.text((name.length > maxLen) ? name.substr(0, maxLen-moreThanMaxLen.length)+moreThanMaxLen : name)
        .addClass('inline file_name')
        .prop('title', name);
    return span;
  }
  
  // Builds the buttons needed for each element
  function buildButtons (data)
  {
    // Build the Submit Button
    // Is hidden from users view atm
    var submit = $('<div>&nbsp;</div>');
    submit.hide()
          .addClass('file_submit')
          .click( function() {
            // If we have already started the upload we don't do anything
            if(data.xhr && (ajax = data.xhr()) && ajax.readyState != ajax.DONE && ajax.readyState != ajax.UNSENT)
              return;
            // We unset data here, to guarantee that the file uploader does reload the form data before submitting
            data.data = undefined;
            
            data.submit();
          });
    
    // Button to cancel Download
    var cancel = $('<div>&nbsp;</div>');
    cancel.prop('title', hcms_entity_decode('<?php echo $text29[$lang]; ?>'))
          .prop('alt', hcms_entity_decode('<?php echo $text29[$lang]; ?>'))
          .addClass('hcmsButtonBlank hcmsButtonSizeSquare hcmsButtonClose file_cancel')
          .click( { }, function( event ) {
            // If we are sending data we stop it or else we remove the entry completely
            if(data.xhr && (ajax = data.xhr()) && ajax.readyState != ajax.DONE && ajax.readyState != ajax.UNSENT)
            {
              ajax.abort();
              buildFileMessage( data, '<?php echo $text31[$lang]; ?>', false);
            }
            else
            {
              data.context.remove();
              selectcount--;
            }
          });
          
    // Div containing from Buttons
    var buttons = $('<div></div>');
    buttons.addClass('inline file_buttons')
           .append(submit)
           .append(cancel);
           
    return buttons;
  }
    
  // Function that makes the div contain a message instead of file informations
  function buildFileMessage (data, text, success)
  {
    // Empty the div before
    data.context.empty();
    
    // apply the correct css for this div
    data.context.removeClass('file_normal')
    if(success)
      data.context.addClass('file_success');
    else
      data.context.addClass('file_error');
    
    // Build name field and buttons
    var name = getFileNameSpan(data.files[0].name);
    var buttons = buildButtons( data );
    
    // Build message field
    msg = $('<div></div>');
    msg.html(hcms_entity_decode(text))
       .addClass('inline file_message');
       
    // Add everything to the context
    data.context.append(name)
                .append(msg)
                .append(buttons);
  }
    
  // Function that make the div contain file informations
  function buildFileUpload (data)
  {
    var div = data.context;
    var file = data.files[0];
    
    // Empty the div before
    div.empty();
               
     // Name field
    var name = getFileNameSpan(file.name);
        
    // Size field
    var size = $('<div></div>');
    size.text(bytesToSize(file.size))
        .addClass('inline file_size');
    
    // Build the buttons
    var buttons = buildButtons(data);    
    
    // Build the progress bar
    var progress = $('<div></div>');
    var progressMeter = $('<div></div>');
    progressMeter.addClass('inline meter');
    
    progress.addClass('inline progress')
            .append(progressMeter);
    
    // Main Div                
    div.append(name)
       .append(size)
       .append(progress)
       .append(buttons)
       .removeClass('file_error file_success')
       .addClass('file file_normal');
  }
    
  // Reloads all needed frames
  function frameReload (newpage, timeout)
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
      else opener.parent.frames['objFrame'].location.href='page_view.php?ctrlreload=yes&site=<?php echo $site; ?>&cat=<?php echo $cat; ?>&location=<?php echo $location_esc; ?>&page='+newpage;
      
      setTimeout('window.close()', timeout);
    }
  }
   
  $('#inputSelectFile').fileupload({
    dataType: 'html',
    // Limits how much simultaneous request can be made
    limitConcurrentUpload: 3,
    url: 'upload_multi.php',
    cache: false,
    // Our script only works when singleFileUploads are true
    singleFileUploads: true,
    add: function (e, data) {
      
      var found = false;
      // Search if the file is already in our queue
      $('#selectedFiles .file_name').each(function( index, element) {
        element = $(element);
        // We use the title because there is always the full name stored
        if(element.prop('title') == data.files[0].name) {
          found = true;
        }
      });
      
      // File is already in queue we inform the user
      if(found) 
      {
        alert(hcms_entity_decode('<?php echo $text23[$lang]; ?>'));
        return false;
      }
      
      var elem = $(this);
      var that = elem.data('blueimp-fileupload') || elem.data('fileupload');
      
      data.context = $('<div></div>');
      data.context.options = that.options;
      var maxItems = hcms_maxItemInQueue;
      
      // Check if we reached the maximum number of items in the queue
      if(maxItems && !isNaN(maxItems) && maxItems > 0 && selectcount >= maxItems) {
        return false;
      }
      
      buildFileUpload(data);
      
      $('#selectedFiles').append(data.context);
      selectcount++;
    }
  })
  .bind('fileuploadsend', function(e, data) {        
    buildFileUpload(data);
  })
  .bind('fileuploaddone', function(e, data) {
    
    var file = "";
    
    // Put out message if possible
    if(data.xhr && (ajax = data.xhr()) && ajax.readyState != ajax.UNSENT)
    {
      text = ajax.responseText;
      
      if(text != "" && (filepos1 = text.indexOf("[")) > 0 && (filepos2 = text.indexOf("]")) == text.length -1) {
        file = text.substr (filepos1 + 1, filepos2 - filepos1 - 1);
        text = text.substr (0, filepos1);
      }
        
      buildFileMessage(data, text, true);
    }       
    
    // Update the total count of uploaded files
    filecount++;
    $('#status').text(filecount);
    
    frameReload(file, hcms_waitTillClose);
    
    // Remove the div after 10 seconds
    setTimeout( function() {
      data.context.remove();
      selectcount--;
    }, hcms_waitTillRemove);
  })
  .bind('fileuploadfail', function(e, data) {
    
    // Put out message if possible
    if(data.xhr && (ajax = data.xhr()) && ajax.readyState != ajax.UNSENT)
    {
      buildFileMessage( data, ajax.responseText, false);
    }       
  })
  .bind('fileuploadprogress', function( e, data) {
    var elem = data.context.find('.progress .meter');
    
    var progress = parseInt(data.loaded / data.total * 100, 10);
    
    elem.css('width', progress+'%')
        .html('&nbsp;');        
  });
	
	//-------------------------- DROPBOX CHOOSE BEGIN --------------------------
  
	//build buttons for dropbox elements
	function buildDropboxButtons (data)
  {
    // need ajax var for aborting process
		var ajax;
    // Build the Submit Button
    // Is hidden from users view atm
    var submit = $('<div>&nbsp;</div>');
					
      submit.hide()
            .addClass('file_submit')
            .click( function( event ) {
              if(ajax && ajax.readyState != ajax.DONE && ajax.readyState != ajax.UNSENT) 
                return;
						//start progress
						buildDropboxFileUpload(data);
						ajax = 	$.ajax({
											type: "POST",
											url: "<?php echo $mgmt_config['url_path_cms']?>upload_multi.php",
											"data": {
                            "location": "<?php echo $location_esc; ?>", 
                            "token": "<?php echo $token ?>", 
                            "user": "<?php echo $user ?>", 
                            "dropbox_file": data.files[0].file, 
                            "imageresize": resize, 
                            "imagepercentage": percent, 
                            "unzip": unzip,
                            "checkduplicates": checkduplicates,
                            "versioning": versioning,
                            "deletedate": deletedate,
													  "media_update": "<?php echo $media ?>",
													  "page": "<?php echo $object ?>"
                        },
											success: function(response)
											{
												var file = "";
												text = ajax.responseText;
												if(text != "" && (filepos1 = text.indexOf("[")) > 0 && (filepos2 = text.indexOf("]")) == text.length -1) {
													file = text.substr (filepos1 + 1, filepos2 - filepos1 - 1);
													text = text.substr (0, filepos1);
												}
												buildDropboxFileMessage( data, text, true);
												
												// Update the total count of uploaded files
												filecount++;
												$('#status').text(filecount);
												
												frameReload(file, hcms_waitTillClose);
												
												// Remove the div after 10 seconds
												setTimeout( function() {
													data.context.remove();
													selectcount--;
												}, hcms_waitTillRemove);
											},
											error: function(response) 
											{
												// Put out message if possible
												if(ajax && ajax.readyState != ajax.UNSENT)
												{
													buildDropboxFileMessage( data, ajax.responseText, false);
												}
											}
										});
            });
      
      // Button to cancel Download
      var cancel = $('<div>&nbsp;</div>');
      cancel.prop('title', hcms_entity_decode('<?php echo $text29[$lang]; ?>'))
            .prop('alt', hcms_entity_decode('<?php echo $text29[$lang]; ?>'))
            .addClass('hcmsButtonBlank hcmsButtonSizeSquare hcmsButtonClose file_cancel')
            .click( function( event ) {
              // If we are sending data we stop it or else we remove the entry completely
              if(ajax && ajax.readyState != ajax.DONE && ajax.readyState != ajax.UNSENT)
              {
                ajax.abort();
                buildDropboxFileMessage( data, '<?php echo $text31[$lang]; ?>', false);
              }
              else
              {
                data.context.remove();
                selectcount--;
              }
            });
            
      // Div containing from Buttons
      var buttons = $('<div></div>');
      buttons.addClass('inline file_buttons')
             .append(submit)
             .append(cancel);
             
      return buttons;
  }
	
  // Function that makes the div contain a message instead of file informations
  function buildDropboxFileMessage (data, text, success)
  {
    // Empty the div before
    data.context.empty();
    
    // apply the correct css for this div
    data.context.removeClass('file_normal')
    if(success)
      data.context.addClass('file_success');
    else
      data.context.addClass('file_error');
    
    // Build name field and buttons
    var name = getFileNameSpan(data.files[0].name);
    var buttons = buildDropboxButtons( data );
    
    // Build message field
    msg = $('<div></div>');
    msg.html(hcms_entity_decode(text))
       .addClass('inline file_message');
       
    // Add everything to the context
    data.context.append(name)
                .append(msg)
                .append(buttons);
  }
 
  // Function that make the div contain file informations
  function buildDropboxFileUpload (data)
  {
    var div = data.context;
    var file = data.files[0];
    
    // Empty the div before
    div.empty();
               
    // Name field
    var name = getFileNameSpan(file.name);
        
    // Size field
    var size = $('<div></div>');
    size.text(bytesToSize(file.size))
        .addClass('inline file_size');
    
    // Build the buttons
    var buttons = buildDropboxButtons(data);    
    
    // Build the progress bar
    var progress = $('<div><img src="<?php echo getthemelocation(); ?>img/loading.gif"/></div>');
    progress.addClass('inline progress');

    // Main Div                
    div.append(name)
       .append(size)
       .append(progress)
       .append(buttons)
       .removeClass('file_error file_success')
       .addClass('file file_normal');
  }
	
	// dropbox chooser options
	var dropboxOptions = {
		// Required. Called when a user selects an item in the Chooser.
		success: function(files) {
			var length = files.length,
					file = null;
			//iterate over chosen files
			for (var i = 0; i < length; i++) {
				file = files[i];
				var context = $('<div></div>');
				var data = {"files": [{"name": file.name, "size": file.bytes, "file": file}], "context": context};
				
				var found = false;
				// Search if the file is already in our queue
				$('#selectedFiles .file_name').each(function( index, element) {
					element = $(element);
					// We use the title because there is always the full name stored
					if(element.prop('title') == data.files[0].name) {
						found = true;
					}
				});
				
				// File is already in queue we inform the user
				if(found) 
				{
					alert(hcms_entity_decode('<?php echo $text23[$lang]; ?>'));
					break;
				}
				var maxItems = hcms_maxItemInQueue;
				// Check if we reached the maximum number of items in the queue
				if(maxItems && !isNaN(maxItems) && maxItems > 0 && selectcount >= maxItems) {
					break;
				}
				
				buildDropboxFileUpload(data);
        
				$('#selectedFiles').append(data.context);
				selectcount++;
			}
		},
		//fetch direct links
		linkType: "direct",
		//enable multi select
		multiselect: <?php if ($uploadmode == "multi") echo "true"; else echo "false"; ?>
	};
  
	//btnDropboxChoose click event to trigger choosing
	$('#btnDropboxChoose').click(function() {
      Dropbox.choose(dropboxOptions);
    });
    
	//-------------------------- DROPBOX CHOOSE END --------------------------
    
    $('#btnUpload').click(function() {
		//check if unzip is checked
		if($('#unzip').prop('checked'))
			unzip = $('#unzip').val();
      
		// check if resize is checked and validate percentage	
    percent = parseInt($('#imagepercentage').val(), 10);
    
    if($('#imageresize').prop('checked'))
    {
			if(isNaN(percent) || percent < 0 || percent > 200)
			{
				alert (hcms_entity_decode('<?php echo $text9[$lang]; ?>'));
				return false;
			}
      
			resize = $('#imageresize').val();
    }
    
    // check if delete is checked and date is defined
    if($('#deleteobject').prop('checked') && $('#deletedate').val() == "")
    {
      alert (hcms_entity_decode("<?php echo $text40[$lang]; ?>"));
      return false;
    }
    
    if($('#checkduplicates').prop('checked'))
    {
      checkduplicates = $('#checkduplicates').val();
    }
		else
		{
			checkduplicates = 0;
		}

    $('#selectedFiles').find('.file_submit').click();
  });
  
  $('#btnCancel').click(function() {
    $('#selectedFiles').find('.file_cancel').click();
  });
  
  $('#imageresize').click(function () {
    $('#imagepercentage').prop('disabled', !($(this).prop('checked')));
  });
  
  $('#deleteobject').click(function () {
    $('#deletedate').prop('disabled', !($(this).prop('checked')));
  });
});
</script>

<link rel="stylesheet" type="text/css" href="javascript/rich_calendar/rich_calendar.css">
<script language="JavaScript" type="text/javascript" src="javascript/rich_calendar/rich_calendar.js"></script>
<script language="JavaScript" type="text/javascript" src="javascript/rich_calendar/rc_lang_en.js"></script>
<script language="JavaScript" type="text/javascript" src="javascript/rich_calendar/rc_lang_de.js"></script>
<script language="Javascript" type="text/javascript" src="javascript/rich_calendar/domready.js"></script>
<script language="JavaScript" type="text/javascript">

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
	cal_obj.language = '<?php echo $lang; ?>';
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
if ($uploadmode == "multi") $title = $text0[$lang];
else $title = $text1[$lang];

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

<div id="content" class="hcmsWorkplaceFrame">
	<form name="upload" id="upload" enctype="multipart/form-data">
    <input type="hidden" name="PHPSESSID" value="<?php echo session_id(); ?>" />
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="page" value="<?php echo $object; ?>" />
    <input type="hidden" name="media_update" value="<?php echo $media; ?>"/>
    <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
    <input type="hidden" name="user" value="<?php echo $user; ?>" />
    <input type="hidden" name="token" value="<?php echo $token; ?>" />
  	<div id="selectedFiles"></div>
		<div style="padding:5px;"><span id="status">0</span>&nbsp;<?php echo $text6[$lang]; ?></div>
    <div>
      <?php if ($uploadmode == "multi" && is_array ($mgmt_uncompress) && sizeof ($mgmt_uncompress) > 0) { ?>
      <div class="inline">
        <input type="checkbox" name="unzip" id="unzip" value="1" /><?php echo $text2[$lang]; ?>
      </div>
      <br />
      <?php } elseif ($uploadmode == "single") { ?>
        <?php if (empty ($mgmt_config['contentversions']) || $mgmt_config['contentversions'] == true) { ?>
      <div class="inline">
        <input type="checkbox" name="versioning" id="versioning" value="1" /><?php echo $text37[$lang]; ?>
      </div>
        <?php } ?> 
      <br /> 
      <div class="inline">
        <input type="checkbox" name="createthumbnail" id="createthumbnail" value="1" /><?php echo $text3[$lang]; ?>
      </div>
      <br />
      <?php } 
      if ($uploadmode == "multi" && is_array ($mgmt_imagepreview) && sizeof ($mgmt_imagepreview) > 0) { ?>
      <div class="inline">
        <input type="checkbox" name="imageresize" id="imageresize" value="percentage" /><?php echo $text8[$lang]; ?>: <input name="imagepercentage" id="imagepercentage" type="text" size="3" maxlength="3" value="100" disabled="disabled" /> %
      </div>
      <br />
      <?php } ?> 
      <div class="inline">
        <input type="checkbox" name="checkduplicates" id="checkduplicates" value="1" <?php if ($mgmt_config['check_duplicates']) echo 'checked="checked"'; ?> /><?php echo $text36[$lang]; ?>
      </div>
      <br />
      <?php if ($uploadmode == "multi") { ?>
      <div class="inline">
        <input type="checkbox" name="deleteobject" id="deleteobject" value="1" /><?php echo $text38[$lang]; ?>
        <input type="hidden" name="deletedate" id="deletedate" value="<?php echo date ("Y-m-d", (time()+60*60*24)); ?> 00:00" />
        <input type="text" id="text_field" value="<?php echo date ("Y-m-d", (time()+60*60*24)); ?> 00:00" disabled="disabled" />
        <img id="datepicker" name="datepicker" src="<?php echo getthemelocation(); ?>img/button_datepicker.gif" onclick="show_cal(this);" align="absmiddle" class="hcmsButtonTiny hcmsButtonSizeSquare" alt="<?php echo $text39[$lang]; ?>" title="<?php echo $text39[$lang]; ?>" />
      </div>
      <br />
      <?php } ?>
      <div style="margin-top:10px;">
        <img src="<?php echo getthemelocation(); ?>img/info.gif" align="absmiddle" />
        <?php echo $text30[$lang]; ?>
      </div>
      <div>
			<?php if (is_array ($mgmt_config) && array_key_exists ("dropbox_appkey", $mgmt_config) && !empty ($mgmt_config['dropbox_appkey']) && array_key_exists ("publicdownload", $mgmt_config) && !empty ($mgmt_config['publicdownload'])) { ?>
				<div id="btnDropboxChoose" class="button hcmsButtonGreen"><span id="txtSelectFile" class="inline"><?php echo $text34[$lang]; ?></span></div>
      <?php } ?>
				<div for="inputSelectFile" id="btnSelectFile" class="button hcmsButtonGreen" ><span id="txtSelectFile" class="inline"><?php echo $text28[$lang]; ?></span><input id="inputSelectFile" type="file" name="Filedata"<?php if ($uploadmode == "multi") echo " multiple"?>/></div>
				<div id="btnUpload" class="button hcmsButtonBlue" ><?php echo $text4[$lang]; ?></div>
        <div id="btnCancel" class="button hcmsButtonOrange" ><?php echo $text5[$lang]; ?></div>
        <br /><br />
      </div>
    </div>

	</form>
</div>
</body>
</html>
