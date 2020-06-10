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
// extension definitions
include ("include/format_ext.inc.php");


// input parameters
$location = getrequest ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$type = getrequest ("type");
$title = getrequest ("title");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission( $site, $ownergroup, $cat );
if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1 || !valid_publicationname ($site)) killsession ($user);
// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// load object file and get container and media file
$objectdata = loadfile ($location, $page);
$mediafile = getfilename ($objectdata, "media");

// get file information of original component file
$pagefile_info = getfileinfo ($site, $page, $cat);

// get media file info
$file_info = getfileinfo ($site, $mediafile, "comp");

// location of media file (can be also outside repository if exported)
$media_root = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";
// thumbnail file is always in repository
$thumb_root = getmedialocation ($site, ".hcms.".$mediafile, "abs_path_media").$site."/";

// generate random id for div-tag
$frameid = rand_secure() + time();

// Path to PDF.JS
$pdfjs_path = $mgmt_config['url_path_cms']."javascript/pdfpreview/web/viewer.html?file=";

// check for document PDF preview
$mediafile_pdf = "";
$mediafile_thumb = $file_info['filename'].".thumb.pdf";

// if original file is a pdf
if (substr_count (".pdf", $file_info['ext']) == 1) $mediafile_pdf = $mediafile;
// document thumb file is a pdf
elseif (is_file ($thumb_root.$mediafile_thumb) || is_cloudobject ($thumb_root.$mediafile_thumb)) $mediafile_pdf = $mediafile_thumb;

// create wrapper link
$doc_link = "";

if (!empty ($mediafile_pdf)) $doc_link = createviewlink ($site, $mediafile_pdf, $pagefile_info['filename'], true)."&saveName=".$pagefile_info['filename'].".pdf";
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang);?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" type="text/css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
function updateCodeSegment()
{
  var title = document.getElementById("title").value;
  var width = document.getElementById("width").value;
  var height = document.getElementById("height").value;

  if (width != "") width = "width:" + width + "; ";
  if (height != "") height = "height:" + height + "; ";

  var code = "<iframe id=\"<?php echo $frameid; ?>\" src=\"<?php echo $pdfjs_path.urlencode($doc_link); ?>\" style=\"" + width + height + "border:none;\" title=\"" + title + "\"></iframe>";

  document.getElementById("codesegment").innerHTML = code;
  document.getElementById("codepreview").innerHTML = code;
}
</script>
<style>
#settings
{
  width: 24%;
  min-width: 280px;
}

#preview
{
  width: 72%;
  min-width: 640px;
  height: 700px; 
}

@media screen and (max-width: 1080px)
{
  #settings
  {
    width: 100%;
  }

  #preview
  {
    width: 100%;
  }
}
</style>
</head>
    
<body class="hcmsWorkplaceGeneric" onload="updateCodeSegment();">

<!-- top bar -->
<?php
echo showtopbar ($hcms_lang['media-player-configuration'][$lang], $lang, $mgmt_config['url_path_cms']."page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page));
?>

<!-- content -->
<div class="hcmsWorkplaceFrame">
  <!-- form  -->
  <div id="settings" style="padding:0px 20px 10px 0px; float:left;">
    <div>
      <label for="title"><?php echo getescapedtext ($hcms_lang['title'][$lang]);?> </label><br/>
      <input type="text" onchange="updateCodeSegment();" id="title" value="<?php echo $title; ?>" style="width:280px;" />
    </div>
    <div style="margin-top:10px;">
      <label for="width"><?php echo getescapedtext ($hcms_lang['width'][$lang]);?> </label> (px, cm, mm, in, pt, pc)<br/>
      <input type="text" onchange="updateCodeSegment();" id="width" value="560px" style="width:280px;" />
    </div>
    <div style="margin-top:10px;">
      <label for="height"><?php echo getescapedtext ($hcms_lang['height'][$lang]);?> </label> (px, cm, mm, in, pt, pc)<br/>
      <input type="text" onchange="updateCodeSegment();" id="height" value="800px" style="width:280px;" />
    </div>
    <hr />

    <strong><?php echo getescapedtext ($hcms_lang['html-body-segment'][$lang]); ?></strong> (<?php echo getescapedtext ($hcms_lang['character-set'][$lang])." ".strtoupper (getcodepage ($lang)); ?>)<br />
    <?php echo getescapedtext ($hcms_lang['mark-and-copy-the-code-from-the-text-area-box-keys-ctrl-a-and-ctrl-c-for-copy-or-right-mouse-button-copy'][$lang]); ?><br /><br />
    <textarea id="codesegment" style="height:140px; width:98%" wrap="VIRTUAL"></textarea>
    <hr />
  </div>

  <!-- preview -->
  <div id="preview" style="float:left; scrolling:auto;">
    <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['preview'][$lang]); ?></span><br/><br/>
    <div id="codepreview"></div>
  </div>

</div>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>