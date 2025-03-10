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

// load object file and get container and media file
$objectdata = loadfile ($location, $object);
$media = getfilename ($objectdata, "media");

// get file info
$file_info = getfileinfo ($site, $location.$object, $cat);

// verify browser
$user_client = getbrowserinfo ();

// create secure token
$token = createtoken ($user);

// max files in queue
if ($uploadmode == "single") $maximumQueueItems = 1;
// use -1 for no limit
else $maximumQueueItems = 500;

// check storage limit (MB)
if (isset ($mgmt_config[$site]['storage_limit']) && $mgmt_config[$site]['storage_limit'] > 0)
{
  // memory for file size (should be kept for 24 hours)
  $filesize_mem = $mgmt_config['abs_path_temp'].$site.".filesize.dat";
  
  if (!is_file ($filesize_mem) || (filemtime ($filesize_mem) + 86400) < time())
  {  
    // this function might require some time for the result in case of large databases
    $filesize = rdbms_getfilesize ("", "%comp%/".$site."/", true);
    savefile ($mgmt_config['abs_path_temp'], $site.".filesize.dat", $filesize['filesize']);
  }
  else $filesize['filesize'] = loadfile ($mgmt_config['abs_path_temp'], $site.".filesize.dat");
  
  // factor to correct used storage due to annotation files, video previews, and so on
  if (!empty ($mgmt_config[$site]['storagefactor'])) $factor = $mgmt_config[$site]['storagefactor'];
  elseif  (!empty ($mgmt_config['storagefactor'])) $factor = $mgmt_config['storagefactor'];
  else $factor = 1.2;

  if ((intval ($filesize['filesize']) * $factor) > ($mgmt_config[$site]['storage_limit'] * 1024))
  {
    echo showinfopage ($hcms_lang['storage-limit-exceeded'][$lang], $lang);

    $errcode = "00100";
    $error[] = $mgmt_config['today']."|hypercms_main.inc.php|warning|".$errcode."|Storage limit of ".$mgmt_config[$site]['storage_limit']." MB has been exceeded by publication '".$site."'";

    // save log
    savelog ($error);

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
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<style type="text/css">
#topbarLayer
{
  margin-right: 110px;
  overflow: hidden; 
  text-overflow: ellipsis;
}

#contentLayer
{
  display: block;
}

@media screen and (max-width: 320px)
{
  #topbarLayer
  {
    max-width: 190px;
  }

  #contentLayer
  {
    display: none;
  }
}
</style>

<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<!-- JQuery -->
<script type="text/javascript" src="javascript/jquery/jquery.min.js"></script>
<!-- JQuery UI -->
<script type="text/javascript" src="javascript/jquery-ui/jquery-ui.min.js"></script>
<link rel="stylesheet" href="javascript/jquery-ui/jquery-ui.css" type="text/css">
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
  // in Miliseconds
  var hcms_waitTillRemove = 3000;
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
  // shall existing files be overwritten
  var overwrite = "";
  // shall versioning be enabled
  var versioning = "";
  // delete objects on given date
  var deletedate = "";
  // XMLHttpRequest object used to store the XHR objects in order to abort file chunk uploads
  var jqXHR = {};

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
          .click(function() {
            // If we have already started the upload we don't do anything
            if (data.xhr && (ajax = data.xhr()) && ajax.readyState != ajax.DONE && ajax.readyState != ajax.UNSENT) return;

            var filename = data.files[0].name;

            // We unset data here, to guarantee that the file uploader does reload the form data before submitting
            data.data = undefined;

            console.log("Uploading file '" + filename + "'");

            // submit the files and store XHR object
            jqXHR[filename] = data.submit();
          });

    // Button to cancel Download
    var cancel = $('<div>&nbsp;</div>');
    cancel.prop('title', hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['cancel'][$lang]); ?>'))
          .prop('alt', hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['cancel'][$lang]); ?>'))
          .addClass('hcmsButtonClose hcmsButtonSizeSquare file_cancel')
          .click({ }, function(event) {

            console.log("Cancel upload of file '" + data.files[0].name + "'");

            // if we are sending data we stop it or else we remove the entry completely
            if (data.xhr && (ajax = data.xhr()) && ajax.readyState != ajax.DONE && ajax.readyState != ajax.UNSENT)
            {
              ajax.abort();
              buildFileMessage(data, '<?php echo getescapedtext ($hcms_lang['upload-cancelled'][$lang]); ?>', false);

              setTimeout(function() {
                data.context.remove(); 
                selectcount--;

                // set total count (for tracking on server)
                document.getElementById('totalcount').value = selectcount;
              }, 1000);
            }
            // for chunks files use the jqXHR object
            else if (jqXHR && data.files[0])
            {
              var filename = data.files[0].name;
              if (jqXHR[filename]) jqXHR[filename].abort();
              buildFileMessage(data, '<?php echo getescapedtext ($hcms_lang['upload-cancelled'][$lang]); ?>', false);
              
              setTimeout(function() {
                data.context.remove(); 
                selectcount--;

                // set total count (for tracking on server)
                document.getElementById('totalcount').value = selectcount;
              }, 1000);
            }
            // file upload has not been started 
            else
            {
              data.context.remove();
              selectcount--;

              // set total count (for tracking on server)
              document.getElementById('totalcount').value = selectcount;
            }
          });

    // Div containing Buttons
    var buttons = $('<div></div>');
    buttons.addClass('inline file_buttons')
           .append(submit)
           .append(cancel);
           
    return buttons;
  }
    
  // Function that makes the div contain a message instead of file informations
  function buildFileMessage (data, text, success)
  {
    // Empty the div before xxx
    data.context.empty();
    
    // apply the correct css for this div
    data.context.removeClass('file_normal');

    if (success)
      data.context.addClass('file_success');
    else
      data.context.addClass('file_error');
    
    // Build name field and buttons
    var file = data.files[0];
    var name = getFileNameSpan(data.files[0].name);
    var buttons = buildButtons(data);

    // Path field
    var path = $('<input type="hidden" name="filepath[' + file.name + ']" value="' + file.webkitRelativePath + '" />');
    
    // Build message field
    msg = $('<div style="width:227px; font-size:11px;"></div>');
    msg.html(hcms_entity_decode(text))
       .addClass('inline file_message');
       
    // Add everything to the context
    data.context.append(name)
                .append(path)
                .append(msg)
                .append(buttons);
  }

  // Function that gets the response text from service uploadfile and executes buildFileMessage
  function getResponseAndBuildFileMessage (data, success, openeditwindow)
  {
    var text = "";
    var file = "";

    // get response text
    if (data.jqXHR && data.jqXHR.responseText)
    {
      text = data.jqXHR.responseText;
    }
    else if (data.xhr && (ajax = data.xhr()) && ajax.readyState != ajax.UNSENT)
    {
      if (ajax && ajax.responseText) 
      {
        text = ajax.responseText;
      }
      else if (data.result)
      {
        text = data.result;
      }
    }

    // put out message if possible
    if (text != "")
    {
      // seperate message from command/objects
      if ((filepos1 = text.indexOf("[")) > 0 && (filepos2 = text.indexOf("]")) == text.length -1)
      {
        file = text.substr (filepos1 + 1, filepos2 - filepos1 - 1);
        text = text.substr (0, filepos1);

        <?php if ($cat == "comp" && !empty ($mgmt_config[$site]['upload_userinput']) && $uploadmode != "single") { ?>
        if (openeditwindow == true && file != "")
        {
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
        }
        <?php } ?>
      }

      buildFileMessage(data, text, success);
    }
  }
    
  // Function that makes the div contain file information
  function buildFileUpload (data)
  {
    var div = data.context;
    
    // Empty the div before
    div.empty();
               
    // Name field
    var file = data.files[0];
    var name = getFileNameSpan(file.name);

    // Path field
    var path = $('<input type="hidden" name="filepath[' + file.name + ']" value="' + file.webkitRelativePath + '" />');
        
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
       .append(path)
       .append(size)
       .append(progress)
       .append(buttons)
       .removeClass('file_error file_success')
       .addClass('file file_normal');
  }
   
  $('#inputSelectFile').fileupload({
    // upload service
    url: 'service/uploadfile.php',
    // data type to be returned in the servers response (use "html" for plain text or "json" for JSON string)
    dataType: 'html',
    // Limits how much simultaneous request can be made
    limitConcurrentUpload: 3,
    <?php if (!empty ($mgmt_config['resume_uploads'])) { ?>
    // split large files in chunks of 10 MB
    maxChunkSize: 10000000,
    <?php } ?>
    // cache
    cache: false,
    // script only works when singleFileUploads is true
    singleFileUploads: true,

    add: function (e, data) {

      // find existing file in queue      
      var found = false;

      // if the file is already in the queue
      $('#selectedFiles.file_name').each(function (index, element) {
        element = $(element);
        // use the title because there is always the full name stored
        if (element.prop('title') == data.files[0].name)
        {
          found = true;
        }
      });
      
      // file is already in queue, inform the user
      if (found) 
      {
        alert(hcms_entity_decode('<?php echo getescapedtext($hcms_lang['the-file-you-are-trying-to-upload-already-exists'][$lang]); ?>'));
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

      <?php if (!empty ($mgmt_config['resume_uploads'])) { ?>
      // resuming file uploads
      // using the uploadedBytes option, it is possible to resume aborted uploads
      var that = this;

      $.getJSON('service/uploadresume.php', {action: 'resume', location: '<?php echo $location_esc; ?>', file: data.files[0].name, user: '<?php echo $user; ?>'}, function (result) {

        if (result.Filedata[0])
        {
          var file = result.Filedata[0];
          console.log("Requesting upload info on file '" + file.name + "'");

          if (parseInt(file.size) > 0) 
          {
            data.uploadedBytes = file && file.size;
            console.log("Resuming upload of file '" + file.name + "' after " + file.size + " bytes");
            
            // auto resume upload
            // $.blueimp.fileupload.prototype.options.add.call(that, e, data);
          }
        }
      });
      <?php } ?>
    }
    <?php if (!empty ($mgmt_config['resume_uploads'])) { ?>,
    fail: function (e, data) {
      // put out message if possible
      getResponseAndBuildFileMessage(data, false, false);

      // delete aborted chunked uploads
      console.log("Deleting aborted chunked upload of file '" + data.files[0].name + "'");

      $.ajax({
        url: 'service/uploadresume.php?action=delete&location=<?php echo urlencode($location_esc); ?>&file=' + encodeURIComponent(data.files[0].name) + '&user=<?php echo urlencode($user); ?>',
        dataType: 'json',
        data: {location: '<?php echo $location_esc; ?>', file: data.files[0].name, user: '<?php echo $user; ?>'},
        type: 'DELETE'
      });
    }
    <?php } ?>
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

    // set total count (for tracking on server)
    document.getElementById('totalcount').value = selectcount;
  })
  
  // callback on file upload
  .bind('fileuploadsend', function(e, data) {

    buildFileUpload(data);
    
    // update queue counter
    queuecount--;
  })
  
  // file upload is finished (for each file)
  .bind('fileuploaddone', function(e, data) {
    
    var fileExt = data.files[0].name.split('.').pop();

    <?php
    if ($uploadmode == "multi") echo "var file = \"\"";
    elseif (!empty ($object)) echo "var file = \"".$location_esc.substr($object, 0, strrpos ($object, ".")).".\" + fileExt";
    ?>

    console.log("The file '" + data.files[0].name + "' has been uploaded");

    // put out message if possible
    getResponseAndBuildFileMessage(data, true, true);
    
    // update the total count of uploaded files
    filecount++;
    $('#status').text(filecount);

    if (queuecount <= 0) frameReload(file, hcms_waitTillClose);
    
    // remove the div
    setTimeout(function() {
      data.context.remove();
      selectcount--;

      // set total count (for tracking on server)
      document.getElementById('totalcount').value = selectcount;
    }, hcms_waitTillRemove);
  })
  
  // file upload failed
  .bind('fileuploadfail', function(e, data) {
    
    // put out message if possible
    getResponseAndBuildFileMessage(data, false, false);    
  })
  
  // progress bar
  .bind('fileuploadprogress', function(e, data) {
    var elem = data.context.find('.progress .meter');
    
    var progress = parseInt(data.loaded / data.total * 100, 10);
    
    // message
    if (progress == 100)
    {
      var text = '<div style="margin:0; padding:0; width:160px; font-size:11px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['the-file-is-being-processed'][$lang]); ?></div>';
    }
    else
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
    // build the Submit Button
    // is hidden from users view atm
    var submit = $('<div>&nbsp;</div>');
          
    submit.hide()
      .addClass ('file_submit')
      .click (function (event)
      {
        if (ajax && ajax.readyState != ajax.DONE && ajax.readyState != ajax.UNSENT) return;
        
        // start progress
        buildDropboxFileUpload (data);
 
        // update queue count
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
            "overwrite": overwrite,
            "versioning": versioning,
            "deletedate": deletedate,
            "media_update": "<?php echo $media ?>",
            "page": "<?php echo $object ?>"
          },
          success: function(response)
          {
            var file = "";

            if (ajax && ajax.responseText) 
            {
              var text = ajax.responseText;
            }
            else if (data.result)
            {
              var text = data.result;
            }

            // separate message from command/objects
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

              // set total count (for tracking on server)
              document.getElementById('totalcount').value = selectcount;
            }, hcms_waitTillRemove);
          },
          error: function(response) 
          {
            // Put out message if possible
            if (ajax && ajax.readyState != ajax.UNSENT)
            {
              if (ajax.responseText) 
              {
                var text = ajax.responseText;
              }
              else if (data.result)
              {
                var text = data.result;
              }

              // seperate message from command/objects
              if (text != "" && (filepos1 = text.indexOf("[")) > 0 && (filepos2 = text.indexOf("]")) == text.length -1)
              {
                file = text.substr (filepos1 + 1, filepos2 - filepos1 - 1);
                text = text.substr (0, filepos1);
              }

              buildDropboxFileMessage (data, text, false);
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
            // if we are sending data we stop it or else we remove the entry completely
            if (ajax && ajax.readyState != ajax.DONE && ajax.readyState != ajax.UNSENT)
            {
              ajax.abort();
              buildDropboxFileMessage (data, '<?php echo getescapedtext ($hcms_lang['upload-cancelled'][$lang]); ?>', false);
            }
            else
            {
              data.context.remove();
              selectcount--;
              queuecount--;

              // set total count (for tracking on server)
              document.getElementById('totalcount').value = selectcount;
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
    data.context.removeClass('file_normal');

    if (success) data.context.addClass('file_success');
    else data.context.addClass('file_error');
    
    // Build name field and buttons
    var file = data.files[0];
    var name = getFileNameSpan(data.files[0].name);
    var buttons = buildDropboxButtons(data);

    // Path field
    var path = $('<input type="hidden" name="filepath[' + file.name + ']" value="' + file.webkitRelativePath + '" />');

    // Build message field
    msg = $('<div style="width:227px; font-size:11px;"></div>');
    msg.html(hcms_entity_decode(text)).addClass('inline file_message');
       
    // Add everything to the context
    data.context.append(name)
                .append(path)
                .append(msg)
                .append(buttons);
  }
 
  // Function that make the div contain file information
  function buildDropboxFileUpload (data)
  {
    var div = data.context;
    
    // Empty the div before
    div.empty();

    // Name field
    var file = data.files[0];
    var name = getFileNameSpan(file.name);

    // Path field
    var path = $('<input type="hidden" name="filepath[' + file.name + ']" value="' + file.webkitRelativePath + '" />');
        
    // Size field
    var size = $('<div></div>');
    size.text(bytesToSize(file.size)).addClass('inline file_size');
    
    // Build the buttons
    var buttons = buildDropboxButtons(data);    
    
    // Build the progress bar
    var progress = $('<div></div>');
    progress.addClass('inline progress');

    // Main Div                
    div.append(name)
        .append(path)
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

    // Name field
    var file = data.files[0];
    var name = getFileNameSpan(file.name);

    // Path field
    var path = $('<input type="hidden" name="filepath[' + file.name + ']" value="' + file.webkitRelativePath + '" />');
    
    // apply the correct css for this div
    data.context.removeClass('file_normal')

    if (success) data.context.addClass('file_success');
    else data.context.addClass('file_error');
    
    // Build name field and buttons
    var name = getFileNameSpan(data.files[0].name);
    var buttons = buildDropboxButtons( data );
    
    // Build message field
    msg = $('<div style="font-size:11px;"></div>');
    msg.html(hcms_entity_decode(text))
       .addClass('inline file_message');
       
    // Add everything to the context
    data.context.append(name)
                .append(path)
                .append(msg)
                .append(buttons);
  }
 
  // Function that makes the div contain file information
  function buildDropboxFileUpload (data)
  {
    var div = data.context;

    // Empty the div before
    div.empty();
               
    // Name field
    var file = data.files[0];
    var name = getFileNameSpan(file.name);

    // Path field
    var path = $('<input type="hidden" name="filepath[' + file.name + ']" value="' + file.webkitRelativePath + '" />');

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
       .append(path)
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
            "overwrite": overwrite,
            "versioning": versioning,
            "deletedate": deletedate,
            "media_update": "<?php echo $media ?>",
            "page": "<?php echo $object ?>"
          },
          success: function(response)
          {
            var file = "";

            if (ajax && ajax.responseText) 
            {
              var text = ajax.responseText;
            }
            else if (data.result)
            {
              var text = data.result;
            }

            // separate message from command/objects
            if (text != "" && (filepos1 = text.indexOf("[")) > 0 && (filepos2 = text.indexOf("]")) == text.length -1)
            {
              file = text.substr (filepos1 + 1, filepos2 - filepos1 - 1);
              text = text.substr (0, filepos1);
            }
 
            buildFTPFileMessage(data, text, true);

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

              // set total count (for tracking on server)
              document.getElementById('totalcount').value = selectcount;
            }, hcms_waitTillRemove);
          },
          error: function(response) 
          {
            // Put out message if possible
            if (ajax && ajax.readyState != ajax.UNSENT)
            {
              if (ajax.responseText) 
              {
                var text = ajax.responseText;
              }
              else if (data.result)
              {
                var text = data.result;
              }

              // seperate message from command/objects
              if (text != "" && (filepos1 = text.indexOf("[")) > 0 && (filepos2 = text.indexOf("]")) == text.length -1)
              {
                file = text.substr (filepos1 + 1, filepos2 - filepos1 - 1);
                text = text.substr (0, filepos1);
              }

              buildFTPFileMessage(data, text, false);
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
            // if we are sending data we stop it or else we remove the entry completely
            if (ajax && ajax.readyState != ajax.DONE && ajax.readyState != ajax.UNSENT)
            {
              ajax.abort();
              buildFTPFileMessage(data, '<?php echo getescapedtext ($hcms_lang['upload-cancelled'][$lang]); ?>', false);
            }
            else
            {
              data.context.remove();
              selectcount--;
              queuecount--;

              // set total count (for tracking on server)
              document.getElementById('totalcount').value = selectcount;
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

    // Name field
    var file = data.files[0];
    var name = getFileNameSpan(file.name);

    // Path field
    var path = $('<input type="hidden" name="filepath[' + file.name + ']" value="' + file.webkitRelativePath + '" />');

    // apply the correct css for this div
    data.context.removeClass('file_normal');

    if (success) data.context.addClass('file_success');
    else data.context.addClass('file_error');

    // Build name field and buttons
    var name = getFileNameSpan(data.files[0].name);
    var buttons = buildFTPButtons(data);

    // Build message field
    msg = $('<div style="width:227px; font-size:11px;"></div>');
    msg.html(hcms_entity_decode(text))
       .addClass('inline file_message');
       
    // Add everything to the context
    data.context.append(name)
                .append(path)
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

    // Name field
    var file = data.files[0];
    var name = getFileNameSpan(file.name);

    // Path field
    var path = $('<input type="hidden" name="filepath[' + file.name + ']" value="' + file.webkitRelativePath + '" />');

    // apply the correct css for this div
    data.context.removeClass('file_normal');

    if (success) data.context.addClass('file_success');
    else data.context.addClass('file_error');

    // Build name field and buttons
    var name = getFileNameSpan(data.files[0].name);
    var buttons = buildFTPButtons(data);

    // Build message field
    msg = $('<div style="font-size:11px;"></div>');
    msg.html(hcms_entity_decode(text))
       .addClass('inline file_message');

    // Add everything to the context
    data.context.append(name)
                .append(path)
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
    var file = data.files[0];
    var name = getFileNameSpan(file.name);

    // Path field
    var path = $('<input type="hidden" name="filepath[' + file.name + ']" value="' + file.webkitRelativePath + '" />');

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
       .append(path)
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

    // overwrite existing files
    if ($('#overwrite').prop('checked'))
    {
      overwrite = $('#overwrite').val();
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
    $('#text_field').prop('disabled', !($(this).prop('checked')));
  });

});

//-------------------------- GENERAL --------------------------

// Reloads all needed frames
function frameReload (objectpath, timeout)
{
  // reload main frame (upload by control objectlist)
  // if upload layer in main frame
  if (window.top && window.top.frames['workplFrame'] && window.top.frames['workplFrame'].frames['mainFrame'])
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

      console.log("Reloading object '" + location + newpage + "'");
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

    // maximize upload window
    if (typeof parent.maxPopup == "function") parent.maxPopup('upload<?php echo md5($location_esc); ?>');

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

// if user closes the window while still in edit mode
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
    document.getElementById("overwrite").checked = false;
    document.getElementById("overwrite").disabled = true;
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
    document.getElementById("overwrite").checked = false;
    document.getElementById("overwrite").disabled = false;
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
    document.getElementById("overwrite").checked = false;
    document.getElementById("overwrite").disabled = true;
  }
  else
  {
    document.getElementById("versioning").disabled = false;
    document.getElementById("checkduplicates").disabled = false;
    document.getElementById("overwrite").checked = false;
    document.getElementById("overwrite").disabled = false;
  }
}

function activateOptions ()
{
  hcms_slideDownLayer('optionsLayer', '0');
}

function activateDirectoryUpload (active)
{
  var fileinput = document.getElementById('inputSelectFile');

  if (fileinput)
  {
    <?php if ($uploadmode == "multi") { ?>
    if (active == 0)
    {
      fileinput.removeAttribute('webkitdirectory');
    }
    else
    {
      fileinput.setAttribute('webkitdirectory', 'webkitdirectory');
    }
    <?php } ?>

    fileinput.click();
  }
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" <?php if ($uploadmode == "multi") echo "onbeforeunload=\"return showwarning();\""; ?>>

<!-- top bar -->
<?php
if ($uploadmode == "multi")
{
  $title = "<span id=\"status\">0</span>&nbsp;".getescapedtext ($hcms_lang['files-uploaded'][$lang]);
  $object_name = getlocationname ($site, $location_esc, $cat, "path");
  $object_name = str_replace ("/", " &gt; ", trim ($object_name, "/"));
  if ($is_mobile) $object_name = showshorttext ($object_name, 40, false);
  $object_name = "<img src=\"".getthemelocation()."img/folder.png\" title=\"".getescapedtext ($hcms_lang['location'][$lang])."\" class=\"hcmsIconList\" />&nbsp;".$object_name;
}
else
{
  $title = getescapedtext ($hcms_lang['upload-new-file-in'][$lang])."<span id=\"status\" style=\"display:none;\">0</span>";
  $fileinfo = getfileinfo ($site, $object, $cat);
  $object_name = "<img src=\"".getthemelocation()."img/".$fileinfo['icon']."\" title=\"".getescapedtext ($hcms_lang['object'][$lang])."\" class=\"hcmsIconList\" />&nbsp;".$fileinfo['name'];
}

echo showtopbar ("<div id=\"topbarLayer\">".$title."<br/><div style=\"width:90%; font-weight:normal; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\">".$object_name."</div></div>", $lang);
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
    <input type="hidden" name="totalcount" id="totalcount" value="" />

    <div class="hcmsWorkplaceGeneric" style="display:block; position:fixed; top:36px; left:6px; right:6px; z-index:1;">
      <!-- info -->
      <?php if (!$is_mobile) { ?>
      <div style="margin:8px 0px;">
        <img src="<?php echo getthemelocation(); ?>img/info.png" class="hcmsIconList" />
        <strong><?php echo getescapedtext ($hcms_lang['you-can-drag-drop-files-into-the-window'][$lang]); ?></strong>
      </div>
      <?php } ?>
      
      <!-- buttons -->
      <div style="display:block; margin-top:8px; padding:0; clear:both;">
        <input id="inputSelectFile" type="file" name="Filedata" <?php if ($uploadmode == "multi") echo "multiple "; ?> style="position:absolute; visibility:hidden;" />
        <button type="button" onclick="activateDirectoryUpload(0);" id="btnSelectFile" class="button hcmsButtonGreen"><span id="txtSelectFile"><?php echo getescapedtext ($hcms_lang['select-files'][$lang]); ?></span></button>
        <?php if ($uploadmode == "multi" && empty ($user_client['msie']) && empty ($user_client['opera'])) { ?><button type="button" onclick="activateDirectoryUpload(1);" id="btnSelectFolder" class="button hcmsButtonGreen"><span id="txtSelectFile"><?php echo getescapedtext ($hcms_lang['select-folder'][$lang]); ?></span></button><?php } ?>
        <?php if (!empty ($mgmt_config['dropbox_appkey']) && !empty ($mgmt_config['publicdownload'])) { ?>
        <button type="button" id="btnDropboxChoose" class="button hcmsButtonGreen"><span id="txtSelectFile"><?php echo getescapedtext ($hcms_lang['dropbox'][$lang]); ?></span></button>
        <?php } ?>
        <?php if (!empty ($mgmt_config['ftp_download'])) { ?>
        <button type="button" id="btnFTP" class="button hcmsButtonGreen" onclick="hcms_openWindow('popup_ftp.php?site=<?php echo url_encode($site); ?>&multi=<?php if ($uploadmode == "multi") echo "true"; else echo "false"; ?>', 'ftp', 'location=no,menubar=no,toolbar=no,titlebar=no,scrollbars=yes,resizable=yes', 600, 400);"><?php echo getescapedtext ($hcms_lang['ftp'][$lang]); ?></button>
        <?php } ?>
        <button type="button" id="btnUpload" class="button hcmsButtonBlue" ><?php echo getescapedtext ($hcms_lang['upload'][$lang]); ?></button>
        <button type="button" id="btnCancel" class="button hcmsButtonOrange" ><?php echo getescapedtext ($hcms_lang['cancel'][$lang]); ?></button>
      </div>

      <!-- options -->
      <hr />
      <div style="display:block; margin-bottom:3px;">
        <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['options'][$lang]); ?></span>
        <img onclick="activateOptions()" class="hcmsButtonTiny" src="<?php echo getthemelocation(); ?>img/button_plusminus.png" style="float:right; width:31px; height:16px;" alt="+/-" title="+/-" />
      </div>
      <div id="optionsLayer" <?php if ($is_mobile) echo "class=\"hcmsTextSmall\""; ?> style="position:relative; z-index:10; display:block; box-sizing:border-box; overflow:hidden; <?php if ($uploadmode != "single") echo "height:0px;"; else "height:80px;"; ?> clear:right;">
        <?php if ($uploadmode == "multi" && is_array ($mgmt_uncompress) && sizeof ($mgmt_uncompress) > 0) { ?>
        <div class="row">
          <label><input type="checkbox" name="unzip" id="unzip" value="unzip" /> <?php echo getescapedtext ($hcms_lang['uncompress-files'][$lang]); ?> (<?php echo getescapedtext ($hcms_lang['existing-objects-will-be-replaced'][$lang]); ?>)</label>
        </div>
        <?php } ?> 
        <?php if ($uploadmode == "multi" && is_array ($mgmt_compress) && sizeof ($mgmt_compress) > 0) { ?>
        <div class="row">
          <label><input type="checkbox" name="unzip" id="zip" onclick="switchzip();" value="zip" /> <?php echo getescapedtext ($hcms_lang['compress-files'][$lang]); ?></label> <input name="zipname" id="zipname" type="text" placeholder="<?php echo getescapedtext ($hcms_lang['file-name'][$lang]); ?>" size="20" maxlength="120" value="" disabled="disabled" />
        </div>
        <?php } ?> 
        <?php if ($cat == "comp" && $uploadmode == "multi" && !empty ($mgmt_config['overwrite_files'])) { ?>
        <div class="row">
          <label><input type="checkbox" name="overwrite" id="overwrite" value="1" /> <?php echo getescapedtext ($hcms_lang['overwrite-existing-files'][$lang]." (".$hcms_lang['keep-existing-file-as-old-version'][$lang].")"); ?></label>
          <input type="checkbox" name="versioning" id="versioning" value="1" checked="checked" style="visibility:hidden;" />
        </div>
        <?php } ?>
        <?php if ($cat == "comp" && $uploadmode == "single") { ?>
          <?php if (!empty ($mgmt_config['contentversions'])) { ?>
        <div class="row">
          <label><input type="checkbox" name="versioning" id="versioning" value="1" <?php if (!empty ($mgmt_config['contentversions_checked']) || !isset ($mgmt_config['contentversions_checked'])) echo "checked=\"checked\""; ?> /> <?php echo getescapedtext ($hcms_lang['keep-existing-file-as-old-version'][$lang]); ?></label>
        </div>
          <?php } ?> 
        <div class="row">
          <label><input type="checkbox" name="createthumbnail" id="createthumbnail" value="1" onclick="switchthumbnail() " /> <?php echo getescapedtext ($hcms_lang['thumbnail-image'][$lang]); ?></label>
        </div>
        <?php } ?>
        <?php if ($cat == "comp" && $uploadmode == "multi" && is_array ($mgmt_imagepreview) && sizeof ($mgmt_imagepreview) > 0) { ?>
        <div class="row">
          <label><input type="checkbox" name="imageresize" id="imageresize" value="percentage" /> <?php echo getescapedtext ($hcms_lang['resize-images-gif-jpeg-png-by-percentage-of-original-size-100'][$lang]); ?></label> <span style="white-space:nowrap;"><input name="imagepercentage" id="imagepercentage" type="text" size="4" maxlength="3" value="100" disabled="disabled" /> %</span>
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
          <input type="text" id="text_field" value="<?php echo date ("Y-m-d", (time()+60*60*24)); ?> 00:00" readonly disabled="disabled" /><img id="datepicker" name="datepicker" src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this);" class="hcmsButtonTiny hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" />
        </div>
        <?php } ?>
      </div>
      <hr />
    </div>

    <!-- selected files -->
    <div id="selectedFiles" style="<?php if ($uploadmode != "single") echo "margin-top:100px;"; else echo "margin-top:160px;"; ?> <?php if ($is_mobile) echo "max-width:100%;"; ?>"></div>

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
    <iframe id="editiframe" src="loading.php" frameborder="0" style="width:100%; height:100%; border-bottom:1px solid #000000; margin:0; padding:0; overflow:auto;"></iframe>
  </div>
</div>

<?php includefooter(); ?>

</body>
</html>