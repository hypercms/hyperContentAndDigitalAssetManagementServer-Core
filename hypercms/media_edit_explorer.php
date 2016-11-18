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
// load formats/file extensions
require_once ("include/format_ext.inc.php");


// input parameters
$site = getrequest_esc ("site", "publicationname");
$sender = getrequest ("sender");
$mediatype = getrequest_esc ("mediatype", "objectname");
$mediacat = getrequest_esc ("mediacat", "objectname");
$mediacat_name = getrequest ("mediacat_name", "objectname");
$mediaformat = getrequest_esc ("mediaformat");
$imagesearch = getrequest_esc ("imagesearch");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check access permission
if (!valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// define media index file name
if (valid_publicationname ($site))
{
  $datafile = $site.".media.tpl.dat";
  $mediadir = $mgmt_config['abs_path_tplmedia'].$site."/";
  $mediaurl = $mgmt_config['url_path_tplmedia'].$site."/";
}

// load media database index
if (valid_objectname ($datafile)) $mediacat_data = loadfile ($mgmt_config['abs_path_data']."media/", $datafile);
else $mediacat_data = "";

$mediacat_array = array();

if (trim ($mediacat_data) != "")
{
  $mediacat_array = explode ("\n", $mediacat_data);
  natcasesort ($mediacat_array);
  reset ($mediacat_array);
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/navigator.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<script>
<!--
function sendInput(file)
{
  parent.frames['controlFrame2'].document.forms['media'].elements['mediafile'].value = file;
  parent.frames['controlFrame2'].document.forms['media'].elements['media_name'].value = file;
}

function goToURL()
{
  var i, args=goToURL.arguments;
  document.returnValue = false;
  for (i=0; i<(args.length-1); i+=2) eval(args[i]+".location='"+args[i+1]+"'");
}
//-->
</script>
</head>

<body class="hcmsWorkplaceObjectlist">

<div id="NavFrameButtons" style="position:fixed; right:0; top:45%; margin:0; padding:0;">
  <img onclick="parent.minNavFrame();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_arrow_left.png" /><br />
  <img onclick="parent.maxNavFrame();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_arrow_right.png" />
</div>

<div id="Navigator" class="hcmsWorkplaceFrame">
<table border="0" cellspacing="0" cellpadding="2" width="100%">
  <tr align="left">
    <td class=hcmsHeadline>
      <?php echo getescapedtext ($hcms_lang['select-media-files'][$lang]); ?>
      <form name="imagesearch" method="post" action="">
        <input type="hidden" name="site" value="<?php echo $site; ?>" />
        <input type="hidden" name="mediacat" value="<?php echo $mediacat; ?>" />
        <input type="hidden" name="mediatype" value="<?php echo $mediatype; ?>" />
        <input type="hidden" name="sender" value="search" />
      
        <table border="0" cellspacing="2" cellpadding="0">
          <tr>
            <td class="hcmsTextSmall"><?php echo getescapedtext ($hcms_lang['select-media-category'][$lang]); ?>:</td>
          </tr>
          <tr>
            <td>
            <select name="mediacat_name" style="width:200px;">
              <option value=""><?php echo getescapedtext ($hcms_lang['all-categories'][$lang]); ?></option>
              <?php
              if (is_array ($mediacat_array) && sizeof ($mediacat_array) > 0)
              {
                foreach ($mediacat_array as $mediacat_record)
                {
                  list ($mediacategory, $rest) = explode (":|", $mediacat_record);
                  echo "<option value=\"".$mediacategory."\">".$mediacategory."</option>\n";
                }
              }
              ?>
            </select>
            </td>
          </tr>
          <tr>
            <td class="hcmsTextSmall"><?php echo getescapedtext ($hcms_lang['select-file-format'][$lang]); ?>:</td>
          </tr>
          <tr>
            <td>
            <select name="mediaformat" style="width:200px;">
              <option value=""><?php echo getescapedtext ($hcms_lang['all-formats'][$lang]); ?></option>
              <option value="audio" <?php if ($mediatype == "audio") echo "selected=\"selected\""; ?>><?php echo getescapedtext ($hcms_lang['audio'][$lang]); ?></option>
              <option value="compressed" <?php if ($mediatype == "compressed") echo "selected=\"selected\""; ?>><?php echo getescapedtext ($hcms_lang['compressed'][$lang]); ?></option>
              <option value="flash" <?php if ($mediatype == "flash") echo "selected=\"selected\""; ?>><?php echo getescapedtext ($hcms_lang['flash'][$lang]); ?></option>
              <option value="image" <?php if ($mediatype == "image") echo "selected=\"selected\""; ?>><?php echo getescapedtext ($hcms_lang['media'][$lang]); ?></option>
              <option value="text" <?php if ($mediatype == "text") echo "selected=\"selected\""; ?>><?php echo getescapedtext ($hcms_lang['text'][$lang]); ?></option>
              <option value="video" <?php if ($mediatype == "video") echo "selected=\"selected\""; ?>><?php echo getescapedtext ($hcms_lang['video'][$lang]); ?></option>
            </select>
            </td>
          </tr>
          <tr>
            <td class="hcmsTextSmall"><?php echo getescapedtext ($hcms_lang['search'][$lang]); ?>:</td>
          </tr>
          <tr>
            <td>
            <input type="text" name="imagesearch" size="18" style="width:170px;" />
            <img name="Button" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="document.forms['imagesearch'].submit();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" alt="OK" />
            </td>
          </tr>
        </table>
      </form>
    </td>
  </tr>
  <tr>
    <td>
    <div id="searchLayer" style="position:relative; width:220px; height:94%; z-index:2; left:2px; top:2px; overflow:auto; visibility:visible;"> 
<?php
if ($sender == "search")
{   
  if ($mediacat_name == "")
  {
    // select all files in directory
    $dir_media = dir ($mediadir);

    if ($dir_media != false)
    {
      while ($entry = $dir_media->read())
      {
        if ($entry != "." && $entry != ".." && !is_dir ($entry) && ($imagesearch == "" || preg_match ("/".$imagesearch."/i", $entry)))
        {
          $files[] = $entry;
        }
      }
      
      $dir_media->close();
    }
  }
  elseif (sizeof ($mediacat_array) > 0)
  {
    // select all files in category from index
    foreach ($mediacat_array as $mediacat_record)
    {
      list ($mediacategory, $filelist) = explode (":|", $mediacat_record);

      if ($mediacategory == $mediacat_name)
      {
        if ($imagesearch == "")
        {
          $files = explode ("|", substr (chop ($filelist), 0, strlen ($filelist) - 1));
        }
        else
        {
          $files_1 = explode ("|", substr (chop ($filelist), 0, strlen ($filelist) - 1));

          foreach ($files_1 as $file_1)
          {
            if (preg_match ("/".$imagesearch."/i", $file_1)) $files[] = $file_1;
          }
        }

        break;
      }
    }
  }

  // media format
  if ($mediaformat != "")
  {
    if ($mediaformat == "audio") $format_ext = strtolower ($hcms_ext['audio']);
    elseif ($mediaformat == "video") $format_ext = strtolower ($hcms_ext['video']);
    elseif ($mediaformat == "text") $format_ext = strtolower ($hcms_ext['cms'].$hcms_ext['bintxt'].$hcms_ext['cleartxt']);
    elseif ($mediaformat == "flash") $format_ext = strtolower ($hcms_ext['flash']);
    elseif ($mediaformat == "image") $format_ext = strtolower ($hcms_ext['image']);
    elseif ($mediaformat == "compressed") $format_ext = strtolower ($hcms_ext['compressed']);
  }

  echo "<span class=\"hcmsHeadlineTiny\">".getescapedtext ($hcms_lang['found-media-files'][$lang])."</span><br />\n";

  // files in actual directory
  if (isset ($files) && $files != false && $files != "")
  {
    natcasesort ($files);
    reset ($files);

    $c = 0;

    foreach ($files as $file)
    {
      if ($file <> "")
      {
        // get the file extension of the file
        $fileext = strtolower (strrchr ($file, "."));

        if ($mediaformat == "" || substr_count ($format_ext, $fileext) == 1)
        {
          // set icon media and check
          $file_info = getfileinfo ($site, $file, "comp");           

          if ($file_info != false && $file != "Null_media.gif")
          {
            echo "<img src=\"".getthemelocation()."img/".$file_info['icon']."\" align=\"absmiddle\" width=\"16\" heigth=\"16\" /><a href=# onClick=\"sendInput('".$site."/".$file."'); goToURL('parent.frames[\'mainFrame2\']','media_view.php?site=".url_encode($site)."&mediacat=tpl&mediafile=".url_encode($site."/".$file)."'); return document.returnValue;\"> ".$file_info['name']."</a><br />\n";
          }

          $c++;
        }
      }
    }
  }

  if ($c == 0)
  {
    echo "<b>".getescapedtext ($hcms_lang['no-results-available'][$lang])."</b><br />\n";
  }
}
?>
    </div>
    </td>
  </tr>
</table>
</div>

</body>
</html>
