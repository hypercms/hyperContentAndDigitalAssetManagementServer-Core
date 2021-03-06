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
if (empty ($mgmt_config['max_digits_filename']) || intval ($mgmt_config['max_digits_filename']) < 1) $mgmt_config['max_digits_filename'] = 236;

// get file info
$file_info = getfileinfo ($site, $location.$object, $cat);

// create secure token
$token = createtoken ($user);

// max files in queue
if ($uploadmode == "single") $maximumQueueItems = 1;
else $maximumQueueItems = -1;

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
  
  // factor to correct used storage due to annotation files, video previews, and so on
  if (!empty ($mgmt_config[$site]['storagefactor'])) $factor = $mgmt_config[$site]['storagefactor'];
  elseif  (!empty ($mgmt_config['storagefactor'])) $factor = $mgmt_config['storagefactor'];
  else $factor = 1.2;

  if (($filesize['filesize'] * $factor) > ($mgmt_config[$site]['storage_limit'] * 1024))
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
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=device-width, initial-scale=0.6, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<style type="text/css">
#contentLayer
{
  display: block;
}

@media screen and (max-width: 320px)
{
  #contentLayer
  {
    display: none;
  }
}
</style>

<script type="text/javascript" src="javascript/main.min.js"></script>

<!-- JQuery -->
<script type="text/javascript" src="javascript/jquery/jquery-3.5.1.min.js"></script>

<!-- JQuery UI -->
<script type="text/javascript" src="javascript/jquery-ui/jquery-ui-1.12.1.min.js"></script>
<link rel="stylesheet" href="javascript/jquery-ui/jquery-ui-1.12.1.css" type="text/css">

<!-- JQuery File Upload -->
<script type="text/javascript" src="javascript/jquery/plugins/jquery.fileupload.js"></script>
<script type="text/javascript" src="javascript/jquery/plugins/jquery.iframe-transport.js"></script>
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/jquery-fileupload.css" type="text/css">

<!-- Dropbox dropin.js -->
<script type="text/javascript" src="https://www.dropbox.com/static/api/1/dropins.js" id="dropboxjs" data-app-key="<?php if (!empty ($mgmt_config['dropbox_appkey'])) echo $mgmt_config['dropbox_appkey']; ?>"></script>

<!-- File Upload Code -->
<script type="text/javascript">

// memory for uploaded objects
var editobjects = [];
  
// when document is ready
$(document).ready(function ()
{
  // Uploaded files count
  var filecount = 0;
  // Selected files count
  var selectcount = 0;
  // Files in queue count
  var queuecount = 0;
  // Time until an item is removed from the queue
  // After it is successfully transmitted
  // In Miliseconds
  var hcms_waitTillRemove = 5*1000;
  // Time after which the window is closed in miliseconds
  // Only applies to single uploads
  var hcms_waitTillClose =  2000;
  // Number of Items which can be in the queue at the same time
  // negative value or NaN values mean unlimited
  var hcms_maxItemInQueue = <?php echo $maximumQueueItems; ?>;
  // parameter indicating unzip and zip
  var unzip = "";
  var zipname = "";
  var zipcount = 0;
  // parameter indicating resize
  var resize = "";
  // percentage of resize
  var percent = 100;
  // shall duplicates be checked
  var checkduplicates = "";
  // shall versioning be enabled
  var versioning = "";
  // delete objects on given date
  var deletedate = "";
  
  // Function to convert the file size in bytes
  function bytesToSize (bytes)
  {
    var sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    if (bytes == 0) return 'n/a';    
    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));    
    return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[[i]];
  };
    
  // Function that generates a jquery span field containing the name of the file
  function getFileNameSpan (name)
  {
    var maxLen = 39;
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
    cancel.prop('title', hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['cancel'][$lang]); ?>'))
          .prop('alt', hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['cancel'][$lang]); ?>'))
          .addClass('hcmsButtonClose hcmsButtonSizeSquare file_cancel')
          .click( { }, function( event ) {
            // If we are sending data we stop it or else we remove the entry completely
            if(data.xhr && (ajax = data.xhr()) && ajax.readyState != ajax.DONE && ajax.readyState != ajax.UNSENT)
            {
              ajax.abort();
              buildFileMessage( data, '<?php echo getescapedtext ($hcms_lang['upload-cancelled'][$lang]); ?>', false);
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
    if (success)
      data.context.addClass('file_success');
    else
      data.context.addClass('file_error');
    
    // Build name field and buttons
    var name = getFileNameSpan(data.files[0].name);
    var buttons = buildButtons( data );
    
    // Build message field
    msg = $('<div style="font-size:11px;"></div>');
    msg.html(hcms_entity_decode(text))
       .addClass('inline file_message');
       
    // Add everything to the context
    data.context.append(name)
                .append(msg)
                .append(buttons);
  }
    
  // Function that makes the div contain file information
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
   
  $('#inputSelectFile').fileupload({
    dataType: 'html',
    // Limits how much simultaneous request can be made
    limitConcurrentUpload: 3,
    url: 'service/uploadfile.php',
    cache: false,
    // Script only works when singleFileUploads is true
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
      if (found) 
      {
        alert(hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['the-file-you-are-trying-to-upload-already-exists'][$lang]); ?>'));
        return false;
      }
      
      var elem = $(this);
      var that = elem.data('blueimp-fileupload') || elem.data('fileupload');
      
      data.context = $('<div></div>');
      data.context.options = that.options;
      var maxItems = hcms_maxItemInQueue;
      
      // Check if we reached the maximum number of items in the queue
      if (maxItems && !isNaN(maxItems) && maxItems > 0 && selectcount >= maxItems)
      {
        return false;
      }
      
      buildFileUpload(data);
      
      $('#selectedFiles').append(data.context);
      queuecount = selectcount++;
    }
  })
  
  // callback on submit of each file
  .bind('fileuploadsubmit', function (e, data) {

    // set file count for zip file
    if (unzip == "zip") 
    {
      document.getElementById('zipcount').value = selectcount;
      
      // serialize form inputs
      var formData = $('form').serializeArray();
    }
  })
  
  // callback on file upload
  .bind('fileuploadsend', function(e, data) {

    buildFileUpload(data);
    
    // Update queue counter
    queuecount--;
  })
  
  // file upload is finished (for each file)
  .bind('fileuploaddone', function(e, data) {
    
    var file = "";
    
    // Put out message if possible
    if (data.xhr && (ajax = data.xhr()) && ajax.readyState != ajax.UNSENT)
    {
      var text = ajax.responseText.trim();

      if (text != "" && (filepos1 = text.indexOf("[")) > 0 && (filepos2 = text.indexOf("]")) == text.length -1)
      {
        file = text.substr (filepos1 + 1, filepos2 - filepos1 - 1);
        text = text.substr (0, filepos1);

        <?php if ($cat == "comp" && !empty ($mgmt_config[$site]['upload_userinput']) && $mgmt_config[$site]['upload_userinput'] == true && $uploadmode != "single") { ?> 
        if (file.indexOf("|") > 0)
        {
          var file_array = file.split("|");

          for (var i=0; i < file_array.length; ++i)
          {
            // open meta data edit window in iframe
            if (file_array[i] != "") openEditWindow(file_array[i]);
          }
        }
        else
        {
          // open meta data edit window in iframe
          openEditWindow(file);
        }
        <?php } ?>
      }
      
      buildFileMessage(data, text, true);
    }       
    
    // Update the total count of uploaded files
    filecount++;
    $('#status').text(filecount);

    if (queuecount <= 0) frameReload(file, hcms_waitTillClose);
    
    // Remove the div
    setTimeout( function() {
      data.context.remove();
      selectcount--;
    }, hcms_waitTillRemove);
  })
  
  // file upload failed
  .bind('fileuploadfail', function(e, data) {
    
    // Put out message if possible
    if(data.xhr && (ajax = data.xhr()) && ajax.readyState != ajax.UNSENT)
    {
      buildFileMessage(data, ajax.responseText, false);
    }       
  })
  
  // progress bar
  .bind('fileuploadprogress', function(e, data) {
    var elem = data.context.find('.progress .meter');
    
    var progress = parseInt(data.loaded / data.total * 100, 10);
    
    // message
    if (progress == 100)
    {
      var text = '<div style="margin-bottom:-2px; padding:0; width:160px; font-size:11px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['the-file-is-being-processed'][$lang]); ?></div>';
    }    else
    {
      var text = '&nbsp;';
    }
    
    elem.css('width', progress+'%').html(text);
  });
  
  //-------------------------- DROPBOX --------------------------
  
  // build buttons for dropbox elements
  function buildDropboxButtons (data)
  {
    // need ajax var for aborting process
    var ajax;
    // Build the Submit Button
    // Is hidden from users view atm
    var submit = $('<div>&nbsp;</div>');
          
    submit.hide()
      .addClass ('file_submit')
      .click (function (event)
      {
        if (ajax && ajax.readyState != ajax.DONE && ajax.readyState != ajax.UNSENT) return;
        
        // start progress
        buildDropboxFileUpload (data);
 
        // Update queue count
        queuecount--;
        
        ajax = 	$.ajax({
          type: "POST",
          url: "<?php echo $mgmt_config['url_path_cms']?>service/uploadfile.php",
          "data": {
            "location": "<?php echo $location_esc; ?>", 
            "token": "<?php echo $token ?>", 
            "user": "<?php echo $user ?>", 
            "dropbox_file": data.files[0].file, 
            "imageresize": resize, 
            "imagepercentage": percent, 
            "unzip": unzip,
            "zipname": zipname,
            "zipcount": selectcount,
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
                  
            if (text != "" && (filepos1 = text.indexOf("[")) > 0 && (filepos2 = text.indexOf("]")) == text.length -1)
            {
              file = text.substr (filepos1 + 1, filepos2 - filepos1 - 1);
              text = text.substr (0, filepos1);
            }
                  
            buildDropboxFileMessage (data, text, true);
            
            // Update the total count of uploaded files
            filecount++;
            $('#status').text(filecount);
            
            if (queuecount <= 0) frameReload(file, hcms_waitTillClose);
            
            // Remove the div
            setTimeout( function()
            {
              data.context.remove();
              selectcount--;
            }, hcms_waitTillRemove);
          },
          error: function(response) 
          {
            // Put out message if possible
            if (ajax && ajax.readyState != ajax.UNSENT)
            {
              buildDropboxFileMessage( data, ajax.responseText, false);
            }
          }
        });
      });
      
    // Button to cancel Download
    var cancel = $('<div>&nbsp;</div>');
    
    cancel.prop ('title', hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['cancel'][$lang]); ?>'))
          .prop ('alt', hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['cancel'][$lang]); ?>'))
          .addClass ('hcmsButtonClose hcmsButtonSizeSquare file_cancel')
          .click (function(event)
          {
            // If we are sending data we stop it or else we remove the entry completely
            if(ajax && ajax.readyState != ajax.DONE && ajax.readyState != ajax.UNSENT)
            {
              ajax.abort();
              buildDropboxFileMessage (data, '<?php echo getescapedtext ($hcms_lang['upload-cancelled'][$lang]); ?>', false);
            }
            else
            {
              data.context.remove();
              selectcount--;
              queuecount--;
            }
          });
          
    // Div containing from Buttons
    var buttons = $('<div></div>');
    buttons.addClass ('inline file_buttons')
           .append (submit)
           .append (cancel);
           
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
    msg = $('<div style="font-size:11px;"></div>');
    msg.html(hcms_entity_decode(text))
       .addClass('inline file_message');
       
    // Add everything to the context
    data.context.append(name)
                .append(msg)
                .append(buttons);
  }
 
  // Function that make the div contain file information
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
    var progress = $('<div></div>');
    progress.addClass('inline progress');

    // Main Div                
    div.append(name)
       .append(size)
       .append(progress)
       .append(buttons)
       .removeClass('file_error file_success')
       .addClass('file file_normal');
  }
  
  // Dropbox chooser options
  var dropboxOptions = {
    // Required: called when a user selects an item in the Chooser
    success: function(files)
    {
      var length = files.length,
          file = null;
          
      // iterate over chosen files
      for (var i = 0; i < length; i++)
      {
        file = files[i];
        var context = $('<div></div>');
        var data = {"files": [{"name": file.name, "size": file.bytes, "file": file}], "context": context};
        
        var found = false;
        
        // Search if the file is already in our queue
        $('#selectedFiles .file_name').each(function( index, element) {
          element = $(element);
          
          // We use the title because there is always the full name stored
          if (element.prop('title') == data.files[0].name)
          {
            found = true;
          }
        });
        
        // File is already in queue we inform the user
        if (found) 
        {
          alert(hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['the-file-you-are-trying-to-upload-already-exists'][$lang]); ?>'));
          break;
        }
        
        var maxItems = hcms_maxItemInQueue;
        
        // Check if we reached the maximum number of items in the queue
        if (maxItems && !isNaN(maxItems) && maxItems > 0 && selectcount >= maxItems)
        {
          break;
        }
        
        buildDropboxFileUpload(data);
        
        $('#selectedFiles').append(data.context);
        selectcount++;
        queuecount = selectcount;
      }
    },
    //fetch direct links
    linkType: "direct",
    //enable multi select
    multiselect: <?php if ($uploadmode == "multi") echo "true"; else echo "false"; ?>
  };
  
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
    msg = $('<div style="font-size:11px;"></div>');
    msg.html(hcms_entity_decode(text))
       .addClass('inline file_message');
       
    // Add everything to the context
    data.context.append(name)
                .append(msg)
                .append(buttons);
  }
 
  // Function that makes the div contain file information
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
    var progress = $('<div></div>');
    progress.addClass('inline progress');

    // Main Div                
    div.append(name)
       .append(size)
       .append(progress)
       .append(buttons)
       .removeClass('file_error file_success')
       .addClass('file file_normal');
  }

  //-------------------------- FTP --------------------------
  
  //build buttons for dropbox elements
  function buildFTPButtons (data)
  {
    // need ajax var for aborting process
    var ajax;
    // Build the Submit Button
    // Is hidden from users view atm
    var submit = $('<div>&nbsp;</div>');
          
    submit.hide()
      .addClass ('file_submit')
      .click (function (event)
      {
        if (ajax && ajax.readyState != ajax.DONE && ajax.readyState != ajax.UNSENT) return;
        
        //start progress
        buildFTPFileUpload (data);
        
        // Update queue count
        queuecount--;
  
        ajax = 	$.ajax({
          type: "POST",
          url: "<?php echo $mgmt_config['url_path_cms']?>service/uploadfile.php",
          "data": {
            "location": "<?php echo $location_esc; ?>", 
            "token": "<?php echo $token ?>", 
            "user": "<?php echo $user ?>", 
            "ftp_file": data.files[0].file, 
            "imageresize": resize, 
            "imagepercentage": percent, 
            "unzip": unzip,
            "zipname": zipname,
            "zipcount": selectcount,
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
                  
            if (text != "" && (filepos1 = text.indexOf("[")) > 0 && (filepos2 = text.indexOf("]")) == text.length -1)
            {
              file = text.substr (filepos1 + 1, filepos2 - filepos1 - 1);
              text = text.substr (0, filepos1);
            }
                  
            buildFTPFileMessage (data, text, true);
            
            // Update queue count
            queuecount--; 
            
            // Update the total count of uploaded files
            filecount++;
            $('#status').text(filecount);

            if (queuecount <= 0) frameReload(file, hcms_waitTillClose);
            
            // Remove the div
            setTimeout( function()
            {
              data.context.remove();
              selectcount--;
            }, hcms_waitTillRemove);
          },
          error: function(response) 
          {
            // Put out message if possible
            if (ajax && ajax.readyState != ajax.UNSENT)
            {
              buildFTPFileMessage( data, ajax.responseText, false);
            }
          }
        });
      });
      
    // Button to cancel Download
    var cancel = $('<div>&nbsp;</div>');
    
    cancel.prop ('title', hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['cancel'][$lang]); ?>'))
          .prop ('alt', hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['cancel'][$lang]); ?>'))
          .addClass ('hcmsButtonClose hcmsButtonSizeSquare file_cancel')
          .click (function(event)
          {
            // If we are sending data we stop it or else we remove the entry completely
            if (ajax && ajax.readyState != ajax.DONE && ajax.readyState != ajax.UNSENT)
            {
              ajax.abort();
              buildFTPFileMessage (data, '<?php echo getescapedtext ($hcms_lang['upload-cancelled'][$lang]); ?>', false);
            }
            else
            {
              data.context.remove();
              selectcount--;
              queuecount--;
            }
          });
          
    // Div containing from Buttons
    var buttons = $('<div></div>');
    buttons.addClass ('inline file_buttons')
           .append (submit)
           .append (cancel);
           
    return buttons;
  }
  
  // Function that makes the div contain a message instead of file informations
  function buildFTPFileMessage (data, text, success)
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
    var buttons = buildFTPButtons( data );
    
    // Build message field
    msg = $('<div style="font-size:11px;"></div>');
    msg.html(hcms_entity_decode(text))
       .addClass('inline file_message');
       
    // Add everything to the context
    data.context.append(name)
                .append(msg)
                .append(buttons);
  }
  
  // FTP global chooser function (called when a user submits the selected files from the FTP chooser window)
  window.insertFTPFile = function (name, size, link)
  {
    if (file != "" && size != "" && link != "")
    {
      // Check if we reached the maximum number of items in the queue
      if (maxItems && !isNaN(maxItems) && maxItems > 0 && (selectcount + 1) >= maxItems)
      {
        return;
      }
    
      var context = $('<div></div>');
      var file = { "name": name, "size": size, "link": link }
      var data = {"files": [{"name": name, "size": size, "file": file}], "context": context};
      
      var found = false;

      // Search if the file is already in our queue
      $('#selectedFiles .file_name').each(function( index, element) {
        element = $(element);

        // We use the title because there is always the full name stored
        if (element.prop('title') == data.files[0].name)
        {
          found = true;
        }
      });
      
      // File is already in queue
      if (found) 
      {
        alert(hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['the-file-you-are-trying-to-upload-already-exists'][$lang]); ?>'));
        return;
      }
      
      var maxItems = hcms_maxItemInQueue;

      buildFTPFileUpload(data);
      
      $('#selectedFiles').append(data.context);
      selectcount++;
      queuecount = selectcount;
    }
  }

  // Function that makes the div contain a message instead of file information
  function buildFTPFileMessage (data, text, success)
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
    var buttons = buildFTPButtons( data );
    
    // Build message field
    msg = $('<div style="font-size:11px;"></div>');
    msg.html(hcms_entity_decode(text))
       .addClass('inline file_message');
       
    // Add everything to the context
    data.context.append(name)
                .append(msg)
                .append(buttons);
  }
 
  // Function that make the div contain file informations
  function buildFTPFileUpload (data)
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
    var buttons = buildFTPButtons(data);    
    
    // Build the progress bar
    var progress = $('<div></div>');
    progress.addClass('inline progress');

    // Main Div                
    div.append(name)
       .append(size)
       .append(progress)
       .append(buttons)
       .removeClass('file_error file_success')
       .addClass('file file_normal');
  }

  //-------------------------- BUTTON ACTIONS --------------------------
  
  // Upload
  $('#btnUpload').click(function()
  {
    // check if unzip is checked
    if ($('#unzip').prop('checked')) unzip = $('#unzip').val();
    
    // check if zip is checked
    if ($('#zip').prop('checked'))
    {
      unzip = $('#zip').val();
      zipname = $('#zipname').val();
    }
    
    // check if resize is checked and validate percentage	
    percent = parseInt($('#imagepercentage').val(), 10);
    
    if ($('#imageresize').prop('checked'))
    {
      if (isNaN(percent) || percent < 0 || percent > 200)
      {
        alert (hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['the-resize-value-must-be-between-1-and-200-'][$lang]); ?>'));
        return false;
      }
      
      resize = $('#imageresize').val();
    }
    
    // check if delete is checked and date is defined
    if ($('#deleteobject').prop('checked') && $('#deletedate').val() == "")
    {
      alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-set-a-delete-date-for-the-files'][$lang]); ?>"));
      return false;
    }
    else if ($('#deleteobject').prop('checked'))
    {
      deletedate = $('#deletedate').val();
    }
    
    // versioning
    if ($('#versioning').prop('checked'))
    {
      versioning = $('#versioning').val();
    }
    
    // thumbnail
    if ($('#createthumbnail').prop('checked'))
    {
      createthumbnail = $('#createthumbnail').val();
    }

    // check for duplicates
    if ($('#checkduplicates').prop('checked'))
    {
      checkduplicates = $('#checkduplicates').val();
    }

    // submit
    $('#selectedFiles').find('.file_submit').click();
  });
  
  // btnDropboxChoose click event to trigger choosing
  $('#btnDropboxChoose').click(function() {
      Dropbox.choose(dropboxOptions);
  });
  
  // Cancel
  $('#btnCancel').click(function()
  {
    $('#selectedFiles').find('.file_cancel').click();
  });
  
  // Image resize
  $('#imageresize').click(function ()
  {
    $('#imagepercentage').prop('disabled', !($(this).prop('checked')));
  });
  
  // Delete object
  $('#deleteobject').click(function ()
  {
    $('#deletedate').prop('disabled', !($(this).prop('checked')));
  });
  
});

//-------------------------- GENERAL --------------------------

// Reloads all needed frames
function frameReload (objectpath, timeout)
{
  // reload main frame (upload by control objectlist)
  // if new upload window
  if (opener && opener.parent.frames['mainFrame'])
  {
    opener.parent.frames['mainFrame'].location.reload();
  }
  // if upload layer in main frame
  else if (window.top && window.top.frames['workplFrame'] && window.top.frames['workplFrame'].frames['mainFrame'])
  {
    window.top.frames['workplFrame'].frames['mainFrame'].location.reload();
  }
  
  // reload explorer frame (upload by component explorer in new upload window)
  if (opener && opener.parent.frames['navFrame2'])
  {
    opener.parent.frames['navFrame2'].location.reload();
  }
  // reload object frame (upload by control content)
  else if (parent.document.getElementById('objFrame'))
  {
    if (objectpath == "")
    {
      // reload same iframe source
      var iframe = parent.document.getElementById('objFrame');

      // start file conversion with async AJAX request
      // hcms_ajaxService (iframe.src);

      // reload object view
      iframe.src = iframe.src;
    }
    else
    {
      // get location and object
      var index = objectpath.lastIndexOf("/") + 1;
      var location = objectpath.substring(0, index);
      var newpage = objectpath.substr(index);

      // start file conversion with async AJAX request
      // hcms_ajaxService ('<?php echo $mgmt_config['url_path_cms']; ?>page_view.php?ctrlreload=yes&location=' +  encodeURIComponent(location) + '&page=' + encodeURIComponent(newpage));

      // reload object view
      parent.document.getElementById('objFrame').src='page_view.php?ctrlreload=yes&location=' +  encodeURIComponent(location) + '&page=' + encodeURIComponent(newpage);
    }

    setTimeout ('parent.closePopup()', timeout);
  }
}

function openEditWindow (objectpath)
{
  // add objectpath to array
  editobjects.push(objectpath);
  
  var window = document.getElementById('editwindow');
  var iframe = document.getElementById('editiframe');

  // open edit window for first object
  if (window.style.display == 'none')
  {
    // get location and object
    var index = objectpath.lastIndexOf("/") + 1;
    var location = objectpath.substring(0, index);
    var newpage = objectpath.substr(index);
    
    iframe.src='page_view.php?rlreload=yes&location=' + encodeURIComponent(location) + '&page=' + encodeURIComponent(newpage);
    window.style.display='inline';
    
    // remove first array element
    editobjects.shift();
  }
}
  
// function will be called from iframe and must be outside of document onload/ready function
function nextEditWindow ()
{
  var window = document.getElementById('editwindow');
  var iframe = document.getElementById('editiframe');

  if (editobjects.length > 0)
  {
    // get and remove first array element
    var objectpath = editobjects.shift();

    // get location and object
    var index = objectpath.lastIndexOf("/") + 1;
    var location = objectpath.substring(0, index);
    var newpage = objectpath.substr(index);

    // load next object
    iframe.src='page_view.php?ctrlreload=yes&location=' + encodeURIComponent(location) + '&page=' + encodeURIComponent(newpage);
    
    if (window.style.display == 'none')
    {
      window.style.display='inline';
    }
  }
  else
  {
    window.style.display='none';
    iframe.src='';
  }
}

// if user closes window while still in edit mode
function showwarning ()
{
  if (document.getElementById('editwindow') && document.getElementById('editwindow').style.display != "none")
  {
    return "<?php echo getescapedtext ($hcms_lang['please-enter-the-metadata-for-your-uploads'][$lang]); ?>";
  }
  else return "";
}
</script>

<link rel="stylesheet" type="text/css" href="javascript/rich_calendar/rich_calendar.css" />
<script type="text/javascript" src="javascript/rich_calendar/rich_calendar.min.js"></script>
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
    cal.hide();
    cal_obj = null;
  }
}

// user defined onautoclose handler
function cal_on_autoclose (cal)
{
  cal_obj = null;
}

// enable/disable checkboxes and buttons
function switchzip ()
{
  if (document.getElementById("zip").checked)
  {
    document.getElementById("unzip").checked = false;
    document.getElementById("unzip").disabled = true;
    document.getElementById("zipname").disabled = false;
    document.getElementById("imageresize").checked = false;
    document.getElementById("imageresize").disabled = true;
    document.getElementById("checkduplicates").checked = false;
    document.getElementById("checkduplicates").disabled = true;
  }
  else
  {
    document.getElementById("unzip").checked = false;
    document.getElementById("unzip").disabled = false;
    document.getElementById("zipname").disabled = true;
    document.getElementById("imageresize").checked = false;
    document.getElementById("imageresize").disabled = false;
    document.getElementById("checkduplicates").checked = false;
    document.getElementById("checkduplicates").disabled = false;
  }
}

function switchthumbnail ()
{
  if (document.getElementById("createthumbnail").checked)
  {
    document.getElementById("versioning").checked = false;
    document.getElementById("versioning").disabled = true;
    document.getElementById("checkduplicates").checked = false;
    document.getElementById("checkduplicates").disabled = true;
  }
  else
  {
    document.getElementById("versioning").disabled = false;
    document.getElementById("checkduplicates").disabled = false;
  }
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" onbeforeunload="return showwarning();">

<!-- top bar -->
<?php
if ($uploadmode == "multi")
{
  $title = "<span id=\"status\">0</span>&nbsp;".getescapedtext ($hcms_lang['files-uploaded'][$lang]);
  $object_name = getlocationname ($site, $location_esc, $cat, "path");
  $object_name = "<img src=\"".getthemelocation()."img/folder.png\" title=\"".getescapedtext ($hcms_lang['location'][$lang])."\" class=\"hcmsIconList\" />&nbsp;".str_replace ("/", " &gt; ", trim ($object_name, "/"));
}
else
{
  $title = getescapedtext ($hcms_lang['upload-new-file-in'][$lang])."<span id=\"status\" style=\"display:none;\">0</span>";
  $fileinfo = getfileinfo ($site, $object, $cat);
  $object_name = "<img src=\"".getthemelocation()."img/".$fileinfo['icon']."\" title=\"".getescapedtext ($hcms_lang['object'][$lang])."\" class=\"hcmsIconList\" />&nbsp;".$fileinfo['name'];
}

echo showtopbar ($title."<br/><span style=\"font-weight:normal;\">".$object_name."</span>", $lang);
?>

<div id="contentLayer" class="hcmsWorkplaceFrame" style="margin-top:12px;">
  <form name="upload" id="upload" enctype="multipart/form-data">
    <input type="hidden" name="PHPSESSID" value="<?php echo session_id(); ?>" />
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="page" value="<?php echo $object; ?>" />
    <input type="hidden" name="media_update" value="<?php echo $media; ?>"/>
    <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
    <input type="hidden" name="user" value="<?php echo $user; ?>" />
    <input type="hidden" name="token" value="<?php echo $token; ?>" />
    <input type="hidden" name="zipcount" id="zipcount" value="" />
    
    <!-- selected files -->
    <div id="selectedFiles"></div>
    
    <!-- controls -->
    <div <?php if ($is_mobile) echo "class=\"hcmsTextSmall\""; ?>>
      <?php if ($uploadmode == "multi" && is_array ($mgmt_uncompress) && sizeof ($mgmt_uncompress) > 0) { ?>
      <div class="row">
        <label><input type="checkbox" name="unzip" id="unzip" value="unzip" /> <?php echo getescapedtext ($hcms_lang['uncompress-files'][$lang]); ?><span class="">(<?php echo getescapedtext ($hcms_lang['existing-objects-will-be-replaced'][$lang]); ?>)</span></label>
      </div>
      <?php } ?> 
      <?php if ($uploadmode == "multi" && is_array ($mgmt_compress) && sizeof ($mgmt_compress) > 0) { ?>
      <div class="row">
        <label><input type="checkbox" name="unzip" id="zip" onclick="switchzip();" value="zip" /> <?php echo getescapedtext ($hcms_lang['compress-files'][$lang]); ?></label> <input name="zipname" id="zipname" type="text" placeholder="<?php echo getescapedtext ($hcms_lang['file-name'][$lang]); ?>" size="20" maxlength="120" value="" disabled="disabled" />
      </div>
      <?php } ?> 
      <?php if ($cat == "comp" && $uploadmode == "single") { ?>
        <?php if (empty ($mgmt_config['contentversions']) || $mgmt_config['contentversions'] == true) { ?>
      <div class="row">
        <label><input type="checkbox" name="versioning" id="versioning" value="1" checked="checked" /> <?php echo getescapedtext ($hcms_lang['keep-existing-file-as-old-version'][$lang]); ?></label>
      </div>
        <?php } ?> 
      <div class="row">
        <label><input type="checkbox" name="createthumbnail" id="createthumbnail" value="1" onclick="switchthumbnail() " /> <?php echo getescapedtext ($hcms_lang['thumbnail-image'][$lang]); ?></label>
      </div>
      <?php } ?>
      <?php if ($cat == "comp" && $uploadmode == "multi" && is_array ($mgmt_imagepreview) && sizeof ($mgmt_imagepreview) > 0) { ?>
      <div class="row">
        <label><input type="checkbox" name="imageresize" id="imageresize" value="percentage" /> <?php echo getescapedtext ($hcms_lang['resize-images-gif-jpeg-png-by-percentage-of-original-size-100'][$lang]); ?></label> <input name="imagepercentage" id="imagepercentage" type="text" size="4" maxlength="3" value="100" disabled="disabled" /> %
      </div>
      <?php } ?>
      <?php if ($cat == "comp") { ?>
      <div class="row">
        <label><input type="checkbox" name="checkduplicates" id="checkduplicates" value="1" <?php if ($mgmt_config['check_duplicates']) echo 'checked="checked"'; ?> /> <?php echo getescapedtext ($hcms_lang['check-for-duplicates'][$lang]); ?></label>
      </div>
      <?php } ?>
      <?php if ($cat == "comp" && $uploadmode == "multi") { ?>
      <div class="row">
        <label><input type="checkbox" name="deleteobject" id="deleteobject" value="1" /> <?php echo getescapedtext ($hcms_lang['remove-uploaded-files-on'][$lang]); ?></label>
        <input type="hidden" name="deletedate" id="deletedate" value="<?php echo date ("Y-m-d", (time()+60*60*24)); ?> 00:00" disabled="disabled" />
        <input type="text" id="text_field" value="<?php echo date ("Y-m-d", (time()+60*60*24)); ?> 00:00" disabled="disabled" /><img id="datepicker" name="datepicker" src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this);" class="hcmsButtonTiny hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" />
      </div>
      <?php } ?>
    </div>
    <?php if (!$is_mobile) { ?>
    <div style="margin-top:8px;">
      <img src="<?php echo getthemelocation(); ?>img/info.png" class="hcmsIconList" />
      <?php echo getescapedtext ($hcms_lang['you-can-drag-drop-files-into-the-window'][$lang]); ?>
    </div>
    <?php } ?>
    <div style="min-height:40px; margin:5px 0px; padding:0;">
      <div for="inputSelectFile" id="btnSelectFile" class="button hcmsButtonGreen"><span id="txtSelectFile"><?php echo getescapedtext ($hcms_lang['select-files'][$lang]); ?></span><input id="inputSelectFile" type="file" name="Filedata" <?php if ($uploadmode == "multi") echo "multiple"; ?> /></div>
      <?php if (!empty ($mgmt_config['dropbox_appkey']) && !empty ($mgmt_config['publicdownload'])) { ?>
      <div id="btnDropboxChoose" class="button hcmsButtonGreen"><span id="txtSelectFile"><?php echo getescapedtext ($hcms_lang['dropbox'][$lang]); ?></span></div>
      <?php } ?>
      <?php if (!empty ($mgmt_config['ftp_download'])) { ?>
      <div id="btnFTP" class="button hcmsButtonGreen" onclick="hcms_openWindow('popup_ftp.php?site=<?php echo url_encode($site); ?>&multi=<?php if ($uploadmode == "multi") echo "true"; else echo "false"; ?>', 'ftp', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes', 600, 400);"><?php echo getescapedtext ($hcms_lang['ftp'][$lang]); ?></div>
      <?php } ?>
      <div id="btnUpload" class="button hcmsButtonBlue" ><?php echo getescapedtext ($hcms_lang['upload-files'][$lang]); ?></div>
      <div id="btnCancel" class="button hcmsButtonOrange" ><?php echo getescapedtext ($hcms_lang['cancel-all-uploads'][$lang]); ?></div>
    </div>

  </form>
</div>

<?php
// iPad and iPhone requires special CSS settings
if ($is_iphone) $css_iphone = " overflow:scroll !important; -webkit-overflow-scrolling:touch !important;";
else $css_iphone = " overflow-x:hidden; overflow-y:hidden;";
?>
<!-- Edit Window -->
<div id="editwindow" style="display:none; position:fixed; top:0px; bottom:0px; left:0px; right:0px; margin:0; padding:0; z-index:1000;">
  <div class="hcmsPriorityHigh" style="width:100%; height:36px;">
    <div style="padding:4px;"><b><?php echo getescapedtext ($hcms_lang['please-enter-the-metadata-for-your-uploads'][$lang]); ?></b></div>
  </div>
  <div class="hcmsWorkplaceGeneric" style="position:fixed; top:36px; bottom:0px; left:0px; right:0px; margin:0; padding:0; z-index:1001; <?php echo $css_iphone; ?>">
    <iframe id="editiframe" src="" frameborder="0" style="width:100%; height:100%; border-bottom:1px solid #000000; margin:0; padding:0; overflow:auto;"></iframe>
  </div>
</div>

<?php includefooter(); ?>
</body>
</html>