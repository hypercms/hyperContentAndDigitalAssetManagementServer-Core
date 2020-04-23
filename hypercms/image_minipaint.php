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
// file formats extensions
require ("include/format_ext.inc.php");


// input parameters
$action = getrequest_esc ("action");
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$wf_token = getrequest_esc ("wf_token");
$token = getrequest ("token");

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
$mediafile = $mediafile_orig = getfilename ($objectdata, "media");

// get file information of original media file
$mediafile_info = getfileinfo ($site, $mediafile, "");
$media_root = getmedialocation ($site, $mediafile_info['name'], "abs_path_media").$site."/";

// get file information of original component file
$pagefile_info = getfileinfo ($site, $location.$page, $cat);

// if RAW image, use equivalent JPEG image
if (is_rawimage ($mediafile_info['ext']))
{
  // reset media file
  $mediafile_raw = $mediafile_info['filename'].".jpg";
  
  // prepare media file
  $temp = preparemediafile ($site, $media_root, $mediafile_raw, $user);
  
  // if encrypted
  if (!empty ($temp['result']) && !empty ($temp['crypted']) && is_file ($temp['templocation'].$temp['tempfile']))
  {
    $mediafile = $temp['tempfile'];
  }
  // if restored
  elseif (!empty ($temp['result']) && !empty ($temp['restored']) && is_file ($temp['location'].$temp['file']))
  {
    $mediafile = $temp['file'];
  }
  // if JPEG of RAW file exists
  elseif (is_file ($media_root.$mediafile_raw))
  {
    // reset media file
    $mediafile = $mediafile_raw;
  }
  // use RAW file
  else
  {
    // reset media file
    $mediafile = $mediafile_orig;
    $mediafile_failed = true;
  }
}

// if not a RAW image or no equivalent JPEG image is available
if (!is_rawimage ($mediafile_info['ext']) || !empty ($mediafile_failed))
{
  // prepare media file
  $temp = preparemediafile ($site, $media_root, $mediafile, $user);

  // if encrypted
  if (!empty ($temp['result']) && !empty ($temp['crypted']) && is_file ($temp['templocation'].$temp['tempfile']))
  {
    $mediafile = $temp['tempfile'];
  }
  // if restored
  elseif (!empty ($temp['result']) && !empty ($temp['restored']) && is_file ($temp['location'].$temp['file']))
  {
    $mediafile = $temp['file'];
  }
  // reset media file
  else
  {
    $mediafile = $mediafile_orig;
  }
}

// create image link
$imagelink = createviewlink ($site, $mediafile, $pagefile_info['name'], false, "wrapper");

// security token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html dir="ltr">
<head>
  <title>hyperCMS - miniPaint</title>
  <meta charset="utf-8" />
  <meta http-equiv="x-ua-compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" type="text/css" />
  <script src="<?php echo $mgmt_config['url_path_cms']; ?>javascript/main.js" type="text/javascript"></script>
  <script type="text/javascript" src="javascript/jquery/jquery-3.3.1.min.js"></script>
  <!-- miniPaint -->
  <base href="<?php echo $mgmt_config['url_path_cms']; ?>/javascript/minipaint/" />
  <script src="<?php echo $mgmt_config['url_path_cms']; ?>javascript/minipaint/dist/bundle.js"></script>
</head>

<body>

<!-- saving screen --> 
<div id="saveLayer" class="hcmsLoadScreen" style="display:none;"></div>

<!-- top bar close button -->
<?php
echo "<div style=\"position:fixed; top:0px; right:0px; width:36px; padding:0; margin:0; z-index:1000;\"><a href=\"javascript:closeminipaint();\" onMouseOut=\"hcms_swapImgRestore();\" onMouseOver=\"hcms_swapImage('close_button','','".getthemelocation()."img/button_close_over.png',1);\"><img name=\"close_button\" src=\"".getthemelocation()."img/button_close.png\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['close'][$lang])."\" title=\"".getescapedtext ($hcms_lang['close'][$lang])."\" /></a></div>\n";
?>

<div class="wrapper">
  
  <div class="submenu">
    <div class="block attributes" id="action_attributes"></div>
    <div class="clear"></div>
  </div>
  
  <div class="sidebar_left" id="tools_container"></div>
  
  <div class="main_wrapper" id="main_wrapper">
    <div class="canvas_wrapper" id="canvas_wrapper">
      <div id="mouse"></div>
      <div class="transparent-grid" id="canvas_minipaint_background"></div>
      <canvas id="canvas_minipaint">
        <div class="trn error">
          Your browser does not support canvas or JavaScript is not enabled.
        </div>
      </canvas>
    </div>
  </div>

  <div class="sidebar_right">
    <div class="preview block">
      <h2 class="trn toggle" data-target="toggle_preview">Preview</h2>
      <div id="toggle_preview"></div>
    </div>
    
    <div class="colors block">
      <h2 class="trn toggle" data-target="toggle_colors">Colors</h2>
      <input
        title="Click to change color" 
        type="color" 
        class="color_area" 
        id="main_color" 
        value="#0000ff"	/>
      <div class="content" id="toggle_colors"></div>
    </div>
    
    <div class="block" id="info_base">
      <h2 class="trn toggle toggle-full" data-target="toggle_info">Information</h2>
      <div class="content" id="toggle_info"></div>
    </div>
    
    <div class="details block" id="details_base">
      <h2 class="trn toggle toggle-full" data-target="toggle_details">Layer details</h2>
      <div class="content" id="toggle_details"></div>
    </div>
    
    <div class="layers block">
      <h2 class="trn">Layers</h2>
      <div class="content" id="layers_base"></div>
    </div>
  </div>
</div>
<div class="mobile_menu">
  <button class="right_mobile_menu" id="mobile_menu_button" type="button"></button>
</div>
<div class="ddsmoothmenu" id="main_menu"></div>
<div class="hidden" id="tmp"></div>
<div id="popup"></div>

<!-- image source -->
<img style="visibility:hidden;" id="image" src="<?php echo $imagelink; ?>" />

<!-- data form -->
<form name="mediaconfig" id="mediaconfig" action="service/renderimage.php" method="post">
  <input type="hidden" id="action" name="action" value="rendermedia" />
  <input type="hidden" name="savetype" value="auto" />
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="page" value="<?php echo $page; ?>" />
  <input type="hidden" name="media" value="<?php echo $mediafile; ?>" />
  <input type="hidden" name="mediadata" value="" />
  <input type="hidden" name="wf_token" value="<?php echo $wf_token; ?>" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
</form>

<script type="text/javascript">
function closeminipaint ()
{
  var page = document.forms['mediaconfig'].elements['page'].value;
  
  if (page!='') document.location.href = '<?php echo $mgmt_config['url_path_cms']; ?>page_view.php?site=<?php echo url_encode($site); ?>&cat=<?php echo url_encode($cat); ?>&location=<?php echo url_encode($location_esc); ?>&page=' + encodeURIComponent(page);
}

// Quick save function
function quicksavemedia (format)
{
  var tempCanvas = document.createElement("canvas");
  var tempCtx = tempCanvas.getContext("2d");
  var dim = window.Layers.get_dimensions();
  tempCanvas.width = dim.width;
  tempCanvas.height = dim.height;
  Layers.convert_layers_to_canvas(tempCtx);

  if (format == "jpg") var dataUri = tempCanvas.toDataURL('image/jpeg', 0.95);
  else var dataUri = tempCanvas.toDataURL('image/png');

  // click save button to close the menu
  if (document.getElementById('save_button')) document.getElementById('save_button').click();

  // send
  transfermedia (dataUri);
}

// global function to save file content via the minpaint "Save as" dialog
  // In order to support the hyperCMS save function savemediacontent:
  // Replace navigator.msSaveOrOpenBlob with window.savemediacontent in minipaint/dist/bundle.js for IE10+.
  // Replace u.default.saveAs with window.savemediacontent in minipaint/dist/bundle.js for other browsers.
window.savemediacontent = function (fileblob, filename)
{
  // get mime-type
  var mimetype = '';

  if (filename != '' && filename.indexOf('.') > 0)
  {
    
    var ext = filename.split('.').pop();

    if (ext != "")
    {
      ext = ext.toLowerCase();

      if (ext == "jpg") mimetype = 'image/jpeg';
      else if (ext == "png") mimetype = 'image/png';
      else if (ext == "gif") mimetype = 'image/gif';
      else if (ext == "bmp") mimetype = 'image/bmp';
      else if (ext == "webp") mimetype = 'image/webp';
    }
  }
  else if (filename != '') mimetype = 'image/gif';
  
  if (mimetype == '')
  {
    alert ('The file extension and mime-type has not been provided by the API');
    return false;
  }

  // check for the File API support
  if (window.File && window.FileReader && window.FileList && window.Blob)
  {
    // convert blob to string
    var dataUri = '';
    var filecontent = '';
    var reader = new FileReader();

    // this fires after the blob has been loaded
    // not supported by IE < 10: reader.addEventListener('loadend', (e) => { filecontent = e.srcElement.result; });
    reader.onloadend=function(){
      filecontent = this.result;

      // converting binary data to base64
      dataUri = btoa (filecontent);

      // define base64 encoded string
      dataUri = 'data:' + mimetype + ';base64,' + dataUri;
      
      // log base64 encoded image
      // console.log(filename+": "+dataUri);

      // send
      transfermedia (dataUri);
    }

    // start reading the blob as binary data
    reader.readAsBinaryString (fileblob);
  }
  else
  {
    alert ('The File APIs are not fully supported by the browser');
  }
}

// save image
function transfermedia (filecontent)
{
  if (filecontent != '')
  {
    hcms_showInfo ('saveLayer', 0);

    // write file content to field
    document.forms['mediaconfig'].elements['mediadata'].value = filecontent;

    // transfer to server
    $.ajax({
      type: 'POST',
      url: '<?php echo $mgmt_config['url_path_cms']; ?>service/savemedia.php',
      data: $("#mediaconfig").serialize(),
      success: function (data)
      {
        // close saving layer
        setTimeout ("hcms_hideInfo ('saveLayer')", 500);

        // update form values due to change of the file extension
        if (data.object.length !== 0)	document.forms['mediaconfig'].elements['page'].value = data.object;
        if (data.mediafile.length !== 0)	document.forms['mediaconfig'].elements['media'].value = data.mediafile;
        if (data.message.length !== 0) alert (hcms_entity_decode(data.message));

        // update control frame
        if (parent && parent.frames['controlFrame'] && data.add_onload.length !== 0)
        {
          eval (data.add_onload);
        }
      },
      dataType: 'json',
      async: false
    });
  }
}

window.addEventListener('load', function (e) {
  var image = document.getElementById('image');

  // set alternative css class for minipaint (theme-dark, theme-green, theme-light)
  // document.body.className = "theme-green";

  // define image
  window.Layers.insert({
      name: "<?php echo $pagefile_info['name']; ?>",
      type: 'image',
      data: image,
      width: image.naturalWidth || image.width,
      height: image.naturalHeight || image.height,
      width_original: image.naturalWidth || image.width,
      height_original: image.naturalHeight || image.height
  });

  // disable minipaint "Save as" dialog file name field
  if (document.getElementById('save_as'))
  {
    document.getElementById('save_as').addEventListener('click', function () {
      setTimeout (function() { document.getElementById('pop_data_name').readOnly = true; }, 300);
      return false;
    });
  }

  // disable minipaint "Save as -> Seperated" dialog radio button
  if (document.getElementById('save_as'))
  {
    document.getElementById('save_as').addEventListener('click', function () {
      setTimeout (function() { document.getElementById('pop_data_layers_poptmp2').disabled = true; }, 300);
      return false;
    });
  }

  // save image as PNG (Quick save)
  if (document.getElementById('save_png'))
  {
    document.getElementById('save_png').addEventListener('click', function () {
      quicksavemedia ('png');
      return false;
    });
  }

  // save image as JPEG (Quick save)
  if (document.getElementById('save_jpg'))
  {
    document.getElementById('save_jpg').addEventListener('click', function () {
      quicksavemedia ('jpg');
      return false;
    });
  }
}, false);
</script>

</body>
</html>
