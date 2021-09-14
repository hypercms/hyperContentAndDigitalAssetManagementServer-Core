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

// load JSON image data
if (is_file ($media_root.$mediafile_info['filename'].".json") || is_cloudobject ($media_root.$mediafile_info['filename'].".json"))
{
  // reset media file
  $mediafile_json = $mediafile_info['filename'].".json";

  // prepare media file
  $temp = preparemediafile ($site, $media_root, $mediafile_json, $user);

  // if encrypted
  if (!empty ($temp['result']) && !empty ($temp['crypted']) && is_file ($temp['templocation'].$temp['tempfile']))
  {
    $media_root_json = $temp['templocation'];
    $mediafile_json = $temp['tempfile'];
  }
  // if restored
  elseif (!empty ($temp['result']) && !empty ($temp['restored']) && is_file ($temp['location'].$temp['file']))
  {
    $media_root_json = $temp['location'];
    $mediafile_json = $temp['file'];
  }
  // reset media file
  else
  {
    $media_root_json = $media_root;
    $mediafile_json = $mediafile_json;
  }
  
  // load JSON image data
  $jsondata = loadfile ($media_root_json, $mediafile_json);
}

// if no JSON image data file is available
if (empty ($jsondata))
{
  // if RAW image, use equivalent JPEG image
  if (is_rawimage ($mediafile))
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

  // if no RAW image or no equivalent JPEG image is available
  if (!is_rawimage ($mediafile) || !empty ($mediafile_failed))
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

  // convert image file to JPG in its original size if not supported by the browser
  $media_config = "";

  if (!in_array ($mediafile_info['ext'], array(".bmp", ".gif", ".jpg", ".jpeg", ".png", ".webp")))
  {
    // suppress watermarking
    $media_config = "&type=jpg&options=".url_encode("-wm none");
  }

  // create image link and add type parameter
  $imagelink = createviewlink ($site, $mediafile, $pagefile_info['name'], true, "wrapper").$media_config;
}

// security token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html dir="ltr">
<head>
  <title>hyperCMS - miniPaint</title>
  <meta charset="utf-8" />
  <meta http-equiv="x-ua-compatible" content="IE=edge" />
  <link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" type="text/css" />
  <link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
  <script type="text/javascript" src="<?php echo $mgmt_config['url_path_cms']; ?>javascript/main.min.js"></script>
  <script type="text/javascript" src="javascript/jquery/jquery-3.5.1.min.js"></script>
  <!-- miniPaint -->
  <base href="<?php echo $mgmt_config['url_path_cms']; ?>/javascript/minipaint/" />
  <script src="<?php echo $mgmt_config['url_path_cms']; ?>javascript/minipaint/dist/bundle.min.js"></script>
</head>

<body>

<!-- saving screen --> 
<div id="saveLayer" class="hcmsLoadScreen" style="display:none;"></div>

<!-- top bar close button -->
<?php
echo "<div style=\"position:fixed; top:0px; right:0px; width:32px; padding:0; margin:0; z-index:1000;\"><a href=\"javascript:closeminipaint();\" onMouseOut=\"hcms_swapImgRestore();\" onMouseOver=\"hcms_swapImage('close_button','','".getthemelocation("night")."img/button_close_over.png',1);\"><img name=\"close_button\" src=\"".getthemelocation("night")."img/button_close.png\" class=\"hcmsButtonTiny hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['close'][$lang])."\" title=\"".getescapedtext ($hcms_lang['close'][$lang])."\" /></a></div>\n";
?>

<nav aria-label="Main Menu" class="main_menu" id="main_menu"></nav>

<div class="wrapper">

  <div class="submenu">
    <div class="block attributes" id="action_attributes"></div>
    <button class="undo_button" id="undo_button" type="button">
      <span class="sr_only">Undo</span>
    </button>
  </div>

  <div class="sidebar_left" id="tools_container"></div>

  <div class="middle_area" id="middle_area">

    <canvas class="ruler_left" id="ruler_left"></canvas>
    <canvas class="ruler_top" id="ruler_top"></canvas>

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
  </div>

  <div class="sidebar_right">
    <div class="preview block">
      <h2 class="trn toggle" data-target="toggle_preview">Preview</h2>
      <div id="toggle_preview"></div>
    </div>
    
    <div class="colors block">
      <h2 class="trn toggle" data-target="toggle_colors">Colors</h2>
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
  <button class="left_mobile_menu" id="left_mobile_menu_button" type="button">
    <span class="sr_only">Toggle Menu</span>
  </button>
  <button class="right_mobile_menu" id="mobile_menu_button" type="button">
    <span class="sr_only">Toggle Menu</span>
  </button>
</div>

<div class="hidden" id="tmp"></div>

<div id="popups"></div>

<!-- image source -->
<img style="visibility:hidden;" id="image" src="<?php if (!empty ($imagelink)) echo $imagelink; ?>" />

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
  <input type="hidden" name="jsondata" value="<?php if (!empty ($jsondata)) echo htmlspecialchars ($jsondata); ?>" />
  <input type="hidden" name="wf_token" value="<?php echo $wf_token; ?>" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
</form>

<script type="text/javascript">

function closeminipaint ()
{
  var page = document.forms['mediaconfig'].elements['page'].value;
  
  if (page!='') document.location.href = '<?php echo $mgmt_config['url_path_cms']; ?>page_view.php?site=<?php echo url_encode($site); ?>&cat=<?php echo url_encode($cat); ?>&location=<?php echo url_encode($location_esc); ?>&page=' + encodeURIComponent(page);
}

// Quick save JSON data
window.savejsoncontent = function (jsondata, filename)
{
  if (jsondata != '')
  {
    hcms_showFormLayer ('saveLayer', 0);

    // write JSON data to field
    document.forms['mediaconfig'].elements['jsondata'].value = jsondata;
    document.forms['mediaconfig'].elements['mediadata'].value = "";

    // transfer to server
    $.ajax({
      type: 'POST',
      url: '<?php echo $mgmt_config['url_path_cms']; ?>service/savemedia.php',
      data: $("#mediaconfig").serialize(),
      success: function (data)
      {
        // close saving layer
        setTimeout ("hcms_hideFormLayer ('saveLayer')", 300);
      },
      dataType: 'json',
      async: false
    });
  }
}

// Quick save image
window.quicksavemedia = function (format)
{
  var tempCanvas = document.createElement("canvas");
  var tempCtx = tempCanvas.getContext("2d");
  var dim = window.Layers.get_dimensions();
  tempCanvas.width = dim.width;
  tempCanvas.height = dim.height;
  Layers.convert_layers_to_canvas(tempCtx);

  if (format == "jpeg" || format == "jpg") var dataUri = tempCanvas.toDataURL('image/jpeg', 0.95);
  else var dataUri = tempCanvas.toDataURL('image/png');

  // click save button to close the menu (deprecated)
  // if (document.getElementById('save_button')) document.getElementById('save_button').click();

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
      else if (ext == "tiff") mimetype = 'image/tiff';
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
    hcms_showFormLayer ('saveLayer', 0);

    // write file content to field
    document.forms['mediaconfig'].elements['mediadata'].value = filecontent;
    document.forms['mediaconfig'].elements['jsondata'].value = "";

    // transfer to server
    $.ajax({
      type: 'POST',
      url: '<?php echo $mgmt_config['url_path_cms']; ?>service/savemedia.php',
      data: $("#mediaconfig").serialize(),
      success: function (data)
      {
        // close saving layer
        setTimeout ("hcms_hideFormLayer ('saveLayer')", 300);

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

  // set alternative css class for minipaint (theme-dark, theme-light)
  // document.body.className = "theme-light";

  // if JSON image data is available
  if (document.forms['mediaconfig'].elements['jsondata'].value != "")
  {
    var jsondata = JSON.parse(document.forms['mediaconfig'].elements['jsondata'].value);

    if (jsondata)
    {
      var image = window.FileOpen.load_json(jsondata);
      if (image == false) alert ('The image could not be created from the JSON data source');
    }
  }
  // if image data is available
  else if (document.getElementById('image').value != "")
  {
    // define image
    var image = document.getElementById('image');

    // insert image
    window.Layers.insert({
        name: "<?php echo $pagefile_info['name']; ?>",
        type: 'image',
        data: image,
        width: image.naturalWidth || image.width,
        height: image.naturalHeight || image.height,
        width_original: image.naturalWidth || image.width,
        height_original: image.naturalHeight || image.height
    });
  }

}, false);
</script>

</body>
</html>
