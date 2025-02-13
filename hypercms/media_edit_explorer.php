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
if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
{
  require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
}

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
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
<script type="text/javascript">

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
</script>
</head>

<body class="hcmsWorkplaceObjectlist">

<div id="NavFrameButtons" style="position:fixed; right:0; top:45%; margin:0; padding:0;">
  <img onclick="parent.minNavFrame();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_arrow_left.png" /><br />
  <img onclick="parent.maxNavFrame();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_arrow_right.png" />
</div>

<div id="Navigator">
<table class="hcmsTableStandard" style="width:100%;">
  <tr>
    <td>
      <div class="hcmsHeadline" style="margin-left:4px;"><?php echo getescapedtext ($hcms_lang['select-media-files'][$lang]); ?></div>
      <form name="imagesearch" method="post" action="">
        <input type="hidden" name="site" value="<?php echo $site; ?>" />
        <input type="hidden" name="mediacat" value="<?php echo $mediacat; ?>" />
        <input type="hidden" name="mediatype" value="<?php echo $mediatype; ?>" />
        <input type="hidden" name="sender" value="search" />
      
        <table class="hcmsTableStandard">
          <tr>
            <td class="hcmsTextSmall"><?php echo getescapedtext ($hcms_lang['select-media-category'][$lang]); ?> </td>
          </tr>
          <tr>
            <td>
            <select name="mediacat_name" style="width:220px;">
              <option value=""><?php echo getescapedtext ($hcms_lang['all-categories'][$lang]); ?></option>
              <?php
              if (is_array ($mediacat_array) && sizeof ($mediacat_array) > 0)
              {
                foreach ($mediacat_array as $mediacat_record)
                {
                  list ($mediacategory, $rest) = explode (":|", $mediacat_record);
                  echo "<option value=\"".$mediacategory."\" ".($mediacategory == $mediacat_name ? "selected" : "").">".$mediacategory."</option>\n";
                }
              }
              ?>
            </select>
            </td>
          </tr>
          <tr>
            <td class="hcmsTextSmall"><?php echo getescapedtext ($hcms_lang['select-file-format'][$lang]); ?> </td>
          </tr>
          <tr>
            <td>
            <select name="mediaformat" style="width:220px;">
              <option value=""><?php echo getescapedtext ($hcms_lang['all-formats'][$lang]); ?></option>
              <option value="audio" <?php if ($mediaformat == "audio") echo "selected=\"selected\""; ?>><?php echo getescapedtext ($hcms_lang['audio'][$lang]); ?></option>
              <option value="compressed" <?php if ($mediaformat == "compressed") echo "selected=\"selected\""; ?>><?php echo getescapedtext ($hcms_lang['compressed'][$lang]); ?></option>
              <option value="flash" <?php if ($mediaformat == "flash") echo "selected=\"selected\""; ?>><?php echo getescapedtext ($hcms_lang['flash'][$lang]); ?></option>
              <option value="image" <?php if ($mediaformat == "image") echo "selected=\"selected\""; ?>><?php echo getescapedtext ($hcms_lang['image'][$lang]); ?></option>
              <option value="text" <?php if ($mediaformat == "text") echo "selected=\"selected\""; ?>><?php echo getescapedtext ($hcms_lang['text'][$lang]); ?></option>
              <option value="video" <?php if ($mediaformat == "video") echo "selected=\"selected\""; ?>><?php echo getescapedtext ($hcms_lang['video'][$lang]); ?></option>
            </select>
            </td>
          </tr>
          <tr>
            <td class="hcmsTextSmall"><?php echo getescapedtext ($hcms_lang['search'][$lang]); ?> </td>
          </tr>
          <tr>
            <td>
            <input type="text" name="imagesearch" style="width:184px;" value="<?php echo $imagesearch; ?>"/>
            <img name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="document.forms['imagesearch'].submit();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" />
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
  $files = array();

  if ($mediacat_name == "")
  {
    // select all files in directory
    $scandir = scandir ($mediadir);

    if ($scandir)
    {
      foreach ($scandir as $entry)
      {
        if ($entry != "." && $entry != ".." && !is_dir ($mediadir.$entry) && ($imagesearch == "" || preg_match ("/".$imagesearch."/i", $entry)))
        {
          $files[] = $entry;
        }
      }
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
    elseif ($mediaformat == "video") $format_ext = strtolower ($hcms_ext['video'].$hcms_ext['rawvideo']);
    elseif ($mediaformat == "text") $format_ext = strtolower ($hcms_ext['cms'].$hcms_ext['bintxt'].$hcms_ext['cleartxt']);
    elseif ($mediaformat == "flash") $format_ext = strtolower ($hcms_ext['flash']);
    elseif ($mediaformat == "image") $format_ext = strtolower ($hcms_ext['image'].$hcms_ext['rawimage'].$hcms_ext['vectorimage'].$hcms_ext['cad']);
    elseif ($mediaformat == "compressed") $format_ext = strtolower ($hcms_ext['compressed']);
  }

  echo "<div class=\"hcmsHeadlineTiny\" style=\"margin-bottom:8px;\">".getescapedtext ($hcms_lang['found-media-files'][$lang])."</div>\n";

  // files in actual directory
  $c = 0;
  
  if (!empty ($files) && is_array ($files) && sizeof ($files) > 0)
  {
    natcasesort ($files);
    reset ($files);

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

          if ($file_info != false && $file != "Null_media.png")
          {
            echo "<img src=\"".getthemelocation()."img/".$file_info['icon']."\" class=\"hcmsIconList\" /><a href=\"javascript:void(0);\" onclick=\"sendInput('".$site."/".$file."'); goToURL('parent.frames[\'mainFrame2\']','media_view.php?site=".url_encode($site)."&mediacat=tpl&mediafile=".url_encode($site."/".$file)."'); return document.returnValue;\"> ".showshorttext($file_info['name'], 24, false)."</a><br />\n";
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
