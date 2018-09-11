<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>Upload</name>
<user>admin</user>
<category>comp</category>
<extension>php</extension>
<application>php</application>
<content><![CDATA[[hyperCMS:objectview name='inlineview']
[hyperCMS:scriptbegin

// INIT
$uniqid = uniqid();
$hash = "%objecthash%";

// USER ENTRIES for iframe
$uploadWidth = "[hyperCMS:textu id='uploadWidth' onEdit='hidden']";
$uploadHeight = "[hyperCMS:textu id='uploadHeight' onEdit='hidden']";

if (empty ($uploadWidth)) $uploadWidth = 800;
if (empty ($uploadHeight )) $uploadHeight = 600;

// CMS VIEW => get user entry and create iframe code
if ("%view%" == "cmsview")
{
scriptend]
<!DOCTYPE html>
<html>
  <head>
    <link rel="stylesheet" hypercms_href="[hyperCMS:scriptbegin echo getthemelocation(); scriptend]css/main.css" />
  </head>
  <body class="hcmsWorkplaceGeneric">
    <div class="hcmsWorkplaceFrame">
      <br />
      <table>
        <tr>
          <td>User Hash for Authentication</td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textu id='userHash' label='User Hash for Authentication' height='15' width='100']</div></td>
        </tr>
        <tr>
          <td>Design theme name</td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textu id='themeName' label='Design theme name' default='' height='15' width='100']</div></td>
        </tr>
        <tr>
          <td>Select Upload Folder</td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textu id='location' label='Location for file uploads' default='%publication%/' height='15' width='100']</div></td>
        </tr>
        <tr>
          <td>Width of Upload Frame</td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textu id='uploadWidth' label='Width of Upload Frame' constraint='isNum' default='800' height='15' width='100']</div> px</td>
        </tr>
        <tr>
          <td>Height of Upload Frame</td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textu id='uploadHeight' label='Height of Upload Frame' constraint='isNum' default='600' height='15' width='100']</div> px</td>
        </tr>
        <tr>
          <td>Enable UNZIP of files</td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textc id='enableUnzip' label=' Enable UNZIP of files' value='Yes']</div></td>
        </tr>
        <tr>
          <td>Enable search for duplicate files</td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textc id='enableDuplicates' label=' Enable search for duplicate files' value='Yes']</div></td>
        </tr>
        <tr>
        </tr>
        <tr>
          <td>&nbsp;</td><td><button class="hcmsButtonGreen" type="button" onClick="location.reload();" >generate code</button></td>
        </tr>
      </table>
      <p>Please do not forget to publish this page after changing the parameters!</p>
      <hr/>
[hyperCMS:scriptbegin
  // check if component is published
  $compinfo = getfileinfo ("%publication%", "%object%", "comp");

  if ($compinfo['published'])
  {
    $embed_code = "<iframe id='frame_".$uniqid."' src='".$mgmt_config['url_path_cms']."?wl=".$hash."' scrolling='no' frameborder=0 border=0 width='".$uploadWidth."' height='".$uploadHeight."'></iframe>";
  }
  else
  {
    $embed_code = "Component is not published yet!";
  }
scriptend]
      <strong>HTML body segment</strong>
      <br />
      Mark and copy the code from the text area box (keys ctrl + A and Ctrl + C for copy or right mouse button -> copy).  Insert this code into your HTML Body of the page, where the snippet will be integrated (keys Crtl + V or right mouse button -> insert).
      <br />
      <br />
      <textarea id="codesegment" wrap="VIRTUAL" style="height:80px; width:98%">[hyperCMS:scriptbegin echo html_encode($embed_code); scriptend]</textarea>
    </div>
  </body>
</html>
[hyperCMS:scriptbegin
}
elseif ("%view%" == "publish" || "%view%" == "preview")
{
  //published file should be a valid html
scriptend]
<?php

// session
define ("SESSION", "create");
// management configuration
require ("%abs_hypercms%/config.inc.php");
// hyperCMS API
require ("%abs_hypercms%/function/hypercms_api.inc.php");


// USER ENTRIES
$userhash = "[hyperCMS:textu id='userHash' onEdit='hidden']";
$themename = "[hyperCMS:textu id='themeName' onEdit='hidden']";
$location = "%comp%/[hyperCMS:textu id='location' onEdit='hidden']";
$site = "%publication%";
$enableUnzip = "[hyperCMS:textc id='enableUnzip' onEdit='hidden']";
$enableDuplicates = "[hyperCMS:textc id='enableDuplicates' onEdit='hidden']";

// input parameters (if provided via GET or POST)
if (empty ($location)) $location = getrequest_esc ("location", "locationname", "%abs_comp%/%publication%/");
if (empty ($userhash)) $userhash = getrequest ("userhash", "objectname", $userhash);
$uploadmode = getrequest ("uploadmode", "objectname", "multi");

// get category
$cat = getcategory ($site, $location);

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// logon
if (!empty ($userhash) && empty ($user))
{
  $login_result = userlogin ("", "", $userhash, "", "");

  if (!empty ($login_result['auth']))
  {
    // register user in session
    $login_result = registeruser ($instance, $login_result);

    // set session parameters as variables
    include ("%abs_hypercms%/include/session.inc.php");
  }
}

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['upload'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// max digits in file name
if (empty ($mgmt_config['max_digits_filename']) || intval ($mgmt_config['max_digits_filename']) < 1) $mgmt_config['max_digits_filename'] = 200;

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

// create new unique folder for each upload session
$newFolder = uniqid();
if (!empty ($site) && !empty ($location)) createfolder ($site, $location_esc, $newFolder, $user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=device-width, initial-scale=0.6, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation($themename); ?>css/main.css" type="text/css">
<link rel="stylesheet" href="<?php echo getthemelocation($themename); ?>css/jquery-fileupload.css" type="text/css">

<script src="%url_hypercms%/javascript/main.js" type="text/javascript"></script>

<!-- JQuery -->
<script src="%url_hypercms%/javascript/jquery/jquery-3.3.1.min.js" type="text/javascript"></script>

<!-- JQuery UI -->
<script src="%url_hypercms%/javascript/jquery-ui/jquery-ui-1.12.1.min.js" type="text/javascript"></script>
<link rel="stylesheet" href="javascript/jquery-ui/jquery-ui-1.12.1.css" type="text/css">

<!-- JQuery File Upload -->
<script src="%url_hypercms%/javascript/jquery/plugins/jquery.fileupload.js" type="text/javascript"></script>
<script src="%url_hypercms%/javascript/jquery/plugins/jquery.iframe-transport.js" type="text/javascript"></script>

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
    
  // Function that generate a jquery span field containing the name of the file
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
    url: '%url_hypercms%/service/uploadfile.php',
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
    
    // Remove the div after 10 seconds
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
  // add your own code here, executed after a successful file upload
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
    
    iframe.src='page_view.php?rlreload=yes&location=' + location + '&page=' + newpage;
    window.style.display='inline';
    
    // remove first array element
    editobjects.shift();
  }
}

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
    iframe.src='page_view.php?ctrlreload=yes&location=' + location + '&page=' + newpage;
    
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
window.onbeforeunload = function() {
  if (document.getElementById('editwindow') && document.getElementById('editwindow').style.display != "none")
  {
    return "<?php echo getescapedtext ($hcms_lang['please-enter-the-metadata-for-your-uploads'][$lang]); ?>";
  }
}
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<div id="content" class="hcmsWorkplaceFrame">
  <form name="upload" id="upload" enctype="multipart/form-data">
    <input type="hidden" name="PHPSESSID" value="<?php echo session_id(); ?>" />
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="location" value="<?php echo $location_esc.$newFolder."/"; ?>" />
    <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
    <input type="hidden" name="user" value="<?php echo $user; ?>" />
    <input type="hidden" name="token" value="<?php echo $token; ?>" />
    
    <div id="selectedFiles"></div>
    
    <div style="padding:5px;"><span id="status">0</span>&nbsp;<?php echo getescapedtext ($hcms_lang['files-uploaded'][$lang]); ?></div>
    
    <div>
      <div class="row">
        <input type="text" name="text[name]" value="" placeholder="<?php echo getescapedtext ($hcms_lang['name'][$lang]); ?>" style="width:400px; margin:1px 0px;" />
      </div>
      <div class="row">
        <input type="text" name="text[email]" value="" placeholder="<?php echo getescapedtext ($hcms_lang['e-mail'][$lang]); ?>" style="width:400px; margin:1px 0px;" />
      </div>
      <?php if (!empty ($enableUnzip) && $uploadmode == "multi" && is_array ($mgmt_uncompress) && sizeof ($mgmt_uncompress) > 0) { ?>
      <div class="row">
        <label><input type="checkbox" name="unzip" id="unzip" value="unzip" /> <?php echo getescapedtext ($hcms_lang['uncompress-files'][$lang]); ?> (<?php echo getescapedtext ($hcms_lang['existing-objects-will-be-replaced'][$lang]); ?>)</label>
      </div>
      <?php } ?>
      <?php if ($cat == "comp" && $uploadmode == "multi" && is_array ($mgmt_imagepreview) && sizeof ($mgmt_imagepreview) > 0) { ?>
      <div class="row">
        <label><input type="checkbox" name="imageresize" id="imageresize" value="percentage" /> <?php echo getescapedtext ($hcms_lang['resize-images-gif-jpeg-png-by-percentage-of-original-size-100'][$lang]); ?></label> <input name="imagepercentage" id="imagepercentage" type="text" size="3" maxlength="3" value="100" disabled="disabled" /> %
      </div>
      <?php } ?>
      <?php if (!empty ($enableDuplicates) && $cat == "comp") { ?>
        <input type="hidden" name="checkduplicates" id="checkduplicates" value="1" />
      <?php } ?>
      <div style="margin:10px 0px 10px 0px;">
        <img src="<?php echo getthemelocation($themename); ?>img/info.png" class="hcmsButtonSizeSquare" align="absmiddle" />
        <?php echo getescapedtext ($hcms_lang['you-can-drag-drop-files-into-the-window'][$lang]); ?>
      </div>
      <div style="margin:0px 0px 10px 0px;">
        <div for="inputSelectFile" id="btnSelectFile" class="button hcmsButtonGreen"><span id="txtSelectFile"><?php echo getescapedtext ($hcms_lang['select-files'][$lang]); ?></span><input id="inputSelectFile" type="file" name="Filedata" <?php if ($uploadmode == "multi") echo "multiple"; ?>/></div>
        <div id="btnUpload" class="button hcmsButtonBlue" ><?php echo getescapedtext ($hcms_lang['upload-files'][$lang]); ?></div>
        <div id="btnCancel" class="button hcmsButtonOrange" ><?php echo getescapedtext ($hcms_lang['cancel-all-uploads'][$lang]); ?></div>
      </div>
    </div>

  </form>
</div>

<?php
// iPad and iPhone requires special CSS settings
if ($is_iphone) $css_iphone = " overflow:scroll !important; -webkit-overflow-scrolling:touch !important;";
else $css_iphone = "";
?>
<!-- Edit Window -->
<div id="editwindow" style="display:none; position:fixed; top:0px; bottom:0px; left:0px; right:0px; margin:0; padding:0; z-index:1000;">
  <div class="hcmsPriorityHigh" style="width:100%; height:28px;">
    <div style="padding:4px;"><b><?php echo getescapedtext ($hcms_lang['please-enter-the-metadata-for-your-uploads'][$lang]); ?></b></div>
  </div>
  <div class="hcmsWorkplaceGeneric" style="position:fixed; top:28px; bottom:0px; left:0px; right:0px; margin:0; padding:0; z-index:1001; <?php echo $css_iphone; ?>">
    <iframe id="editiframe" scrolling="auto" src="" style="width:100%; height:95%; border-bottom:1px solid #000000; margin:0; padding:0;" frameborder="0"></iframe>
  </div>
</div>

</body>
</html>
[hyperCMS:scriptbegin
}
scriptend]]]></content>
</template>