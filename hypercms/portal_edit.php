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
$site = getrequest_esc ("site", "publicationname");
$template = getrequest_esc ("template", "objectname");
$save = getrequest ("save");
$delete_logo_top = getrequest ("delete_logo_top");
$delete_logo = getrequest ("delete_logo");
$delete_wallpaper = getrequest ("delete_wallpaper");
$portaluser = getrequest ("portaluser");
$navigation = getrequest ("navigation", "array");
$designtheme = getrequest ("designtheme");
$primarycolor = getrequest ("primarycolor");
$token = getrequest ("token");

// formats
$format_img = getrequest_esc ("format_img", "array");
$format_doc = getrequest_esc ("format_doc", "array");
$format_vid = getrequest_esc ("format_vid", "array");


// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!valid_publicationname ($site) || !checkglobalpermission ($site, 'template') || !checkglobalpermission ($site, 'tpl') || empty ($mgmt_config[$site]['portalaccesslink'])) killsession ($user);

// edit permission defines view mode
if (checkglobalpermission ($site, 'tpledit')) $preview = "no";
else $preview = "yes";

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$templatename = "";

// define template name
if (strpos ($template, ".portal.tpl") > 0)
{
  $templatename = substr ($template, 0, strpos ($template, ".portal.tpl"));
}

// save template file if save button was pressed
if (checkglobalpermission ($site, 'template') && checkglobalpermission ($site, 'tpledit') && $save == "yes" && checktoken ($token, $user))
{
  // --------------------------------- download formats ------------------------------
    
  $format_array = array();
  
  if (!empty ($format_img) && is_array ($format_img))
  {
    foreach ($format_img as $value)
    {
      // image format and config provided
      if (substr_count ($value, "|") > 0)
      {
        list ($ext, $config) = explode ("|", $value);
        $format_array['image'][$ext][$config] = 1;
      }
      // only image format
      else
      {
        $format_array['image'][$value] = 1;
      }
    }
  }

  if (!empty ($format_doc) && is_array ($format_doc))
  {
    foreach ($format_doc as $ext)
    {
      // document format
      $format_array['document'][$ext] = 1;
    }
  }

  if (!empty ($format_vid) && is_array ($format_vid))
  {
    foreach ($format_vid as $ext)
    {
      // document format
      $format_array['video'][$ext] = 1;
    }
  }

  // create JSON string
  if (sizeof ($format_array) > 0) $formats = json_encode ($format_array);
  else $formats = "";

  // set delete array
  if (!empty ($delete_logo_top)) $_FILES['logo_top']['delete'] = 1;
  if (!empty ($delete_logo)) $_FILES['logo']['delete'] = 1;
  if (!empty ($delete_wallpaper)) $_FILES['wallpaper']['delete'] = 1;

  // save template file
  $result_save = editportal ($site, $template, $portaluser, $designtheme, $primarycolor, $_FILES, $navigation, $formats, $user);
  
  if (empty ($result_save['result']))
  {
    $show = "<span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['template-could-not-be-saved'][$lang])."</span>";
  }
}
// load template file
else
{
  $templatedata = loadfile ($mgmt_config['abs_path_template'].$site."/", $template);
  
  // initialize
  $designtheme = "";
  $primarycolor = "";
  $hcms_themeinvertcolors = "";
  $designuser = "";
  $navigation = array();
  $format_img = array();
  $format_doc = array();
  $format_vid= array();

  // extract information
  $temp_array = getcontent ($templatedata, "<designtheme>");
  if (!empty ($temp_array[0])) list ($temp, $designtheme) = explode ("/", $temp_array[0]);

  $temp_array = getcontent ($templatedata, "<primarycolor>");
  if (!empty ($temp_array[0])) $primarycolor = $temp_array[0];

  $temp_array = getcontent ($templatedata, "<user>");
  if (!empty ($temp_array[0])) $designuser = $temp_array[0];

  $temp_array = getcontent ($templatedata, "<portaluser>");
  if (!empty ($temp_array[0])) $portaluser = $temp_array[0];

  $temp_array = getcontent ($templatedata, "<navigation>");
  if (!empty ($temp_array[0])) $navigation = explode ("|", $temp_array[0]);

  $temp_array = getcontent ($templatedata, "<downloadformats>");

  if (!empty ($temp_array[0]))
  {
    $downloadformats = $temp_array[0];
    $downloadformats = json_decode ($downloadformats, true);

    // prepare format arrays
    if (!empty ($downloadformats['image']) && is_array ($downloadformats['image']))
    {
      foreach ($downloadformats['image'] as $ext => $temp_config)
      {
        if (is_array ($temp_config))
        {
          foreach ($temp_config as $config => $active)
          {
            if ($active == 1) $format_img[] = $ext."|".$config;
          }
        }
        else $format_img[] = $ext;
      }
    }

    if (!empty ($downloadformats['document']) && is_array ($downloadformats['document']))
    {
      foreach ($downloadformats['document'] as $ext => $active)
      {
        if ($active == 1) $format_doc[] = $ext;
      }
    }

    if (!empty ($downloadformats['video']) && is_array ($downloadformats['video']))
    {
      foreach ($downloadformats['video'] as $ext => $active)
      {
        if ($active == 1) $format_vid[] = $ext;
      }
    }
  }
}

// wallpaper
$wallpaper = "";

if ($templatename != "")
{
  // design theme
  $portaltheme = $site."/".$templatename;

  // inverted theme default value
  $hcms_themeinvertcolors = $portaltheme;

  $wallpaper = getwallpaper ($portaltheme);
}

// invert colors
if (!empty ($designtheme) && !empty ($primarycolor))
{
  $brightness = getbrightness ($primarycolor);

  if ($designtheme == "day" && $brightness < 130) $hcms_themeinvertcolors = "night";
  elseif ($designtheme == "night" && $brightness >= 130) $hcms_themeinvertcolors = "day";
}

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation($portaltheme); ?>css/main.css?ts=<?php echo time(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script type="text/javascript" src="javascript/main.min.js"></script>
<script type="text/javascript" src="javascript/jscolor/jscolor.min.js"></script>

<?php
// invert colors
if (!empty ($hcms_themeinvertcolors))
{
  echo "<style>";
  echo invertcolorCSS ($hcms_themeinvertcolors);
  echo "</style>";
}
?>

<script type="text/javascript">

function toggleCheckboxes (name, source)
{
  var checkboxes = document.getElementsByName(name);
  
  for (var i=0; i<checkboxes.length; i++)
  {
    checkboxes[i].checked = source.checked;
  }
}

function switchLayer ()
{
  var form = document.forms['template_edit'];
  var layer = document.getElementById('additionalLayer');

  if (form.elements['portaluser'].value != '')
  {
    layer.style.height = 'auto';
    layer.style.overflow = 'auto';
  }
  else
  {
    layer.style.overflow = 'hidden';
    layer.style.height = '0px';
  }
}

function deleteSelected (name)
{
 var form = document.forms['template_edit'];

 if (name != "" && form.elements[name])
 {
  form.elements[name].value = "1";
  return true;
 }
 else return false;
}

function savetemplate ()
{
  if (document.forms['template_edit'])
  {
    hcms_showFormLayer ('savelayer', 0); 
    document.forms['template_edit'].submit();
    return true;
  }
  else return false;
}

function setwallpaper ()
{
  // set background image
  <?php if (!empty ($wallpaper) && is_image ($wallpaper)) { ?>
  document.getElementById('homeScreen').style.backgroundImage = "url('<?php echo $wallpaper; ?>?ts=<?php echo time(); ?>')";
  return true;
  <?php } else { ?>
  return false;
  <?php } ?>
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

table.TableNarrow
{
  width: 100%;
  height: 800px;
  display: table;
  table-layout: auto;
  margin: 0;
  padding: 0;
  border-collapse: separate;
  border-spacing: 0;
}

table.TableNarrow th, table.TableNarrow td
{
  margin: 0;
  padding: 0;
  text-align: left;
  vertical-align: top;
}
</style>
</head>

<body class="hcmsWorkplaceGeneric" onload="setwallpaper(); switchLayer();">

<!-- saving --> 
<div id="savelayer" class="hcmsLoadScreen"></div>

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

  <?php
  echo showmessage ($show, 650, 30, $lang, "position:fixed; left:15px; top:100px;")
  ?>

  <!-- form  -->
  <div id="settings" style="padding:0px 20px 10px 0px; float:left;">
    <form name="template_edit" action="" method="post" enctype="multipart/form-data">
      <input type="hidden" name="site" value="<?php echo $site; ?>" />
      <input type="hidden" name="template" value="<?php echo $template; ?>" />
      <input type="hidden" name="delete_logo_top" value="" />
      <input type="hidden" name="delete_logo" value="" />
      <input type="hidden" name="delete_wallpaper" value="" />
      <input type="hidden" name="save" value="yes" />
      <input type="hidden" name="token" value="<?php echo $token_new; ?>">
    
      <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['portal-template'][$lang]); ?></span> <?php echo getescapedtext ($templatename); ?><br/>
      <hr/><br/>

      <!-- Color schema -->
      <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['color-schema'][$lang]); ?></span><br/><br/>

      <?php echo getescapedtext ($hcms_lang['text-and-icons'][$lang]); ?> &nbsp;&nbsp; 
      <label><input type="radio" name="designtheme" value="night" <?php if ($designtheme == "night" || empty ($designtheme)) echo "checked=\"checked\""; ?> /> <?php echo getescapedtext ($hcms_lang['light'][$lang]); ?></label>&nbsp;&nbsp; 
      <label><input type="radio" name="designtheme" value="day" <?php if ($designtheme == "day") echo "checked=\"checked\""; ?> /> <?php echo getescapedtext ($hcms_lang['dark'][$lang]); ?></label><br/><br/>

      <?php echo getescapedtext ($hcms_lang['color'][$lang]); ?><br/>
      <input class="jscolor" name="primarycolor" value="<?php echo $primarycolor; ?>" style="width:280px;" /><br/>
      <hr/><br/>

      <!-- Uploads -->
      <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['upload-file'][$lang]); ?></span><br/><br/>
      <?php echo getescapedtext ($hcms_lang['logo'][$lang]." (PNG, WxH >= 114x114 px)"); ?><br/>
      <input type="file" name="logo_top" accept="image/png" style="width:280px; float:left;" />
      <?php if (is_file ($mgmt_config['abs_path_rep']."portal/".$portaltheme."/uploads/logo_top.png")) { ?>
      <img onclick="deleteSelected('delete_logo_top');" class="hcmsButtonTiny hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_delete.png" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" />
      <?php } else { ?>
      <img src="<?php echo getthemelocation(); ?>img/button_delete.png" class="hcmsButtonOff hcmsButtonSizeSquare" />
      <?php } ?>
      <div style="clear:both;"></div>
      <br/>
      <?php echo getescapedtext ($hcms_lang['logo'][$lang]." (PNG, H <= 100 px)"); ?><br/>
      <input type="file" name="logo" accept="image/png" style="width:280px; float:left;" />
      <?php if (is_file ($mgmt_config['abs_path_rep']."portal/".$portaltheme."/uploads/logo.png")) { ?>
      <img onclick="deleteSelected('delete_logo');" class="hcmsButtonTiny hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_delete.png" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" />
      <?php } else { ?>
      <img src="<?php echo getthemelocation(); ?>img/button_delete.png" class="hcmsButtonOff hcmsButtonSizeSquare" />
      <?php } ?>
      <div style="clear:both;"></div>
      <br/>
      <?php echo getescapedtext ($hcms_lang['wallpaper'][$lang]." (PNG, JPG, ~ 1920x1080 px)"); ?><br/>
      <input type="file" name="wallpaper" accept="image/png, image/jpeg" style="width:280px; float:left;" />
      <?php if (is_file ($mgmt_config['abs_path_rep']."portal/".$portaltheme."/uploads/wallpaper.png") || is_file ($mgmt_config['abs_path_rep']."portal/".$portaltheme."/uploads/wallpaper.jpg")) { ?>
      <img onclick="deleteSelected('delete_wallpaper');" class="hcmsButtonTiny hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_delete.png" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" />
      <?php } else { ?>
      <img src="<?php echo getthemelocation(); ?>img/button_delete.png" class="hcmsButtonOff hcmsButtonSizeSquare" />
      <?php } ?>
      <div style="clear:both;"></div>
      <hr/><br/>

      <!-- Portal user -->
      <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['user'][$lang]." (".$hcms_lang['permissions'][$lang].", ".$hcms_lang['access-to-folders'][$lang].")"); ?></span><br/><br/>
      <select name="portaluser" onchange="switchLayer();" style="width:280px;">
        <option value=""><?php echo $hcms_lang['please-select-a-user'][$lang]; ?></option>
      <?php
      $user_array = getuserinformation ();
      $user_option = array();
      
      if (is_array ($user_array) && sizeof ($user_array) > 0)
      {
        foreach ($user_array[$site] as $login => $value)
        {
          if ($login != "admin" && $login != "sys")
          {
            $text = $login;
            if ($value['realname'] != "") $text .= " (".$value['realname'].")";
  
            $user_option[$text] = "
            <option value=\"".$login."\" ".($portaluser == $login ? "selected=\"selected\"" : "").">".$text."</option>";
          }
        }

        ksort ($user_option, SORT_STRING | SORT_FLAG_CASE);
        echo implode ("", $user_option);
      }
      ?>
      </select>
      <hr/><br/>

      <div id="additionalLayer" style="height:0px; overflow:hidden;">
        <!-- Portal navigation -->
        <span class="hcmsHeadline"><label style="cursor:pointer;"><input type="checkbox" onclick="toggleCheckboxes('navigation[]', this);" style="display:none" /><?php echo getescapedtext ($hcms_lang['navigate'][$lang]); ?></label></span><br/><br/>
        <?php
        if (!empty ($mgmt_config[$site]['taxonomy'])) echo "
        <input name=\"navigation[]\" type=\"checkbox\" value=\"taxonomy\" ".(is_array($navigation) && in_array ("taxonomy", $navigation) ? "checked=\"checked\"" : "")." /> <img src=\"".getthemelocation()."img/folder_taxonomy.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['taxonomy'][$lang])."<br/>";
        
        $hierarchy = gethierarchy_definition ($site);

        if (is_array ($hierarchy) && sizeof ($hierarchy) > 0)
        {
          foreach ($hierarchy as $name => $level_array)
          {
            $name = getescapedtext ($name);
            echo "
            <input name=\"navigation[]\" type=\"checkbox\" value=\"".$name."\" ".(is_array($navigation) && in_array ($name, $navigation) ? "checked=\"checked\"" : "")." /> <img src=\"".getthemelocation()."img/folder.png\" class=\"hcmsIconList\" /> ".$name." (".getescapedtext ($hcms_lang['meta-data-hierarchy'][$lang]).")<br/>";
          }
        }

        echo "
        <input name=\"navigation[]\" type=\"checkbox\" value=\"assets\" ".(is_array($navigation) && in_array ("assets", $navigation) ? "checked=\"checked\"" : "")." /> <img src=\"".getthemelocation()."img/folder.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['assets'][$lang])."<br/>";
        ?>
        <hr/><br/>

        <!-- Formats -->
        <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['download-formats'][$lang]); ?></span><br/><br/>     
        <div id="formatsLayer" style="clear:right; scrolling:auto;">
          <div style="padding:0px 10px 10px 0px; float:left;">
            <span class="hcmsHeadline"><label style="cursor:pointer;"><input type="checkbox" onclick="toggleCheckboxes('format_img[]', this);" style="display:none" /><?php echo getescapedtext ($hcms_lang['image'][$lang]); ?></label></span><br/>
            <?php
            if (is_array ($mgmt_imageoptions) && sizeof ($mgmt_imageoptions) > 0)
            {
              $i = 1;

              if (!empty ($format_img) && is_array ($format_img) && in_array ("original", $format_img)) $checked = "checked=\"checked\"";
              else $checked = "";

              echo "<label><input name=\"format_img[]\" type=\"checkbox\" value=\"original\" ".$checked." /> <img src=\"".getthemelocation()."img/file_image.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['original'][$lang])."</label><br />\n";
              $i++;

              foreach ($mgmt_imageoptions as $ext => $imageconfig_array)
              {
                if (is_array ($imageconfig_array))
                {
                  $ext_array = explode (".", trim ($ext, "."));
                  $image_type = $ext_array[0];

                  foreach ($imageconfig_array as $image_config => $value)
                  {
                    if ($image_config != "original" && $image_config != "thumbnail")
                    {
                      $file_info = getfileinfo ($site, "file".$ext, "comp");

                      if (!empty ($format_img) && is_array ($format_img) && in_array ($image_type."|".$image_config, $format_img)) $checked = "checked=\"checked\"";
                      else $checked = "";

                      echo "<label><input name=\"format_img[]\" type=\"checkbox\" value=\"".$image_type."|".$image_config."\" ".$checked." /> <img src=\"".getthemelocation()."img/".$file_info['icon']."\" class=\"hcmsIconList\" /> ".strtoupper($image_type)." ".$file_info['type']." ".$image_config."</label><br />\n";

                      $i++;
                    }
                  }
                }
              }
            }
            ?>
          </div>
          <div style="padding:0px 10px 10px 0px; float:left;">
            <span class="hcmsHeadline"><label style="cursor:pointer;"><input type="checkbox" onclick="toggleCheckboxes('format_vid[]', this);" style="display:none" /><?php echo getescapedtext ($hcms_lang['video'][$lang]); ?></label></span><br/>
            <?php
            if (is_array ($mgmt_mediaoptions) && sizeof ($mgmt_mediaoptions) > 0)
            {
              $i = 1;

              if (!empty ($format_vid) && is_array ($format_vid) && in_array ("original", $format_vid)) $checked = "checked=\"checked\"";
              else $checked = "";

              echo "
              <label><input name=\"format_vid[]\" type=\"checkbox\" value=\"original\" ".$checked." /> <img src=\"".getthemelocation()."img/file_mpg.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['original'][$lang])."</label><br />";
              $i++;

              if (!empty ($format_vid) && is_array ($format_vid) && in_array ("origthumb", $format_vid)) $checked = "checked=\"checked\"";
              else $checked = "";

              echo "
              <label><input name=\"format_vid[]\" type=\"checkbox\" value=\"origthumb\" ".$checked." /> <img src=\"".getthemelocation()."img/file_mpg.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['preview'][$lang])."</label><br />";
              $i++;

              if (!empty ($format_vid) && is_array ($format_vid) && in_array ("jpg", $format_vid)) $checked = "checked=\"checked\"";
              else $checked = "";

              echo "
              <label><input name=\"format_vid[]\" type=\"checkbox\" value=\"jpg\" ".$checked." /> <img src=\"".getthemelocation()."img/file_image.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['images'][$lang])." (JPG)</label><br />";
              $i++;

              if (!empty ($format_vid) && is_array ($format_vid) && in_array ("png", $format_vid)) $checked = "checked=\"checked\"";
              else $checked = "";

              echo "
              <label><input name=\"format_vid[]\" type=\"checkbox\" value=\"png\" ".$checked." /> <img src=\"".getthemelocation()."img/file_image.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['images'][$lang])." (PNG)</label><br />";
              $i++;
            }
            ?>
          </div>
          <div style="padding:0px 10px 10px 0px; float:left;">
            <span class="hcmsHeadline"><label style="cursor:pointer;"><input type="checkbox" onclick="toggleCheckboxes('format_doc[]', this);" style="display:none" /><?php echo getescapedtext ($hcms_lang['document'][$lang]); ?></label></span><br/>
            <?php 
            if (is_array ($mgmt_docoptions) && sizeof ($mgmt_docoptions) > 0)
            {
              $print_first = "";
              $print_next = "";
              $i = 1;

              if (!empty ($format_doc) && is_array ($format_doc) && in_array ("original", $format_doc)) $checked = "checked=\"checked\"";
              else $checked = "";
                  
              echo "<label><input name=\"format_doc[]\" type=\"checkbox\" value=\"original\" ".$checked." /> <img src=\"".getthemelocation()."img/file_txt.png\" class=\"hcmsIconList\" /> ".getescapedtext ($hcms_lang['original'][$lang])."</label><br />\n";
              $i++;

              foreach ($mgmt_docoptions as $ext => $value)
              {
                if ($ext != "" && !is_image ("_".$ext))
                {
                  $ext_array = explode (".", trim ($ext, "."));
                  $doc_type = $ext_array[0];

                  $file_info = getfileinfo ($site, "file".$ext, "comp");

                  if (!empty ($format_doc) && is_array ($format_doc) && in_array ($doc_type, $format_doc)) $checked = "checked=\"checked\"";
                  else $checked = "";

                  $temp = "<label><input name=\"format_doc[]\" type=\"checkbox\" value=\"".$doc_type."\" ".$checked." /> <img src=\"".getthemelocation()."img/".$file_info['icon']."\" class=\"hcmsIconList\" /> ".$file_info['type']." (".strtoupper($doc_type).")</label><br />\n";

                  if (strtolower ($ext) == ".pdf") $print_first .= $temp;
                  else $print_next .= $temp;

                  $i++;
                }
              }

              echo $print_first.$print_next;
            }
            ?>
          </div>
          <div style="clear:both;"></div>
        </div>
        <hr/><br/>

        <!-- Portal access link -->
        <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['access-link'][$lang]); ?></span><br/><br/>
        <span class="hcmsHeadlineTiny"><?php echo createportallink ($site, $templatename); ?></span>
        <hr/><br/>
      </div>

      <?php if ($preview == "no") { ?>
      <?php echo getescapedtext ($hcms_lang['save-and-preview'][$lang]); ?>
      <img name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" onclick="savetemplate();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" /><br/>
      <?php } ?>
    </form>
  </div>

  <!-- preview -->
  <div id="preview" style="float:left; scrolling:auto;">
    <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['preview'][$lang]); ?></span><br/><br/>

    <table class="TableNarrow hcmsImageItem">
      <tr>
        <td class="hcmsWorkplaceTop" style="width:36px !important;">
          <!-- navigation items -->
          <img src="<?php echo getthemelocation($portaltheme); ?>img/logo_top.png?ts=<?php echo time(); ?>" class="hcmsLogoTop" />
          <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/home.png?ts=<?php echo time(); ?>" class="hcmsButtonTiny hcmsButtonSizeSquare" style="padding:2px;" />
          <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_explorer.png?ts=<?php echo time(); ?>" class="hcmsButtonTiny hcmsButtonSizeSquare" style="padding:2px;" />
          <img src="<?php echo getthemelocation($hcms_themeinvertcolors); ?>img/button_search.png?ts=<?php echo time(); ?>" class="hcmsButtonTiny hcmsButtonSizeSquare" style="padding:2px;" />
        </td>
        <td class="hcmsWorkplaceExplorer" style="width:260px !important;">
          <!-- explorer items -->
          <div style="display:block; width:260px;">
            <div style="padding:8px 0px 0px 10px; white-space:nowrap;"><img src="<?php echo getthemelocation($portaltheme); ?>img/site.png?ts=<?php echo time(); ?>" class="hcmsIconList" /> <a href="#"><?php echo getescapedtext ($hcms_lang['publication'][$lang]); ?></a></div>
            <div style="padding:4px 0px 0px 26px; white-space:nowrap;"><img src="<?php echo getthemelocation($portaltheme); ?>img/folder_comp.png?ts=<?php echo time(); ?>" class="hcmsIconList" /> <a href="#"><?php echo getescapedtext ($hcms_lang['assets'][$lang]); ?></a></div>
            <div style="padding:4px 0px 0px 42px; white-space:nowrap;"><img src="<?php echo getthemelocation($portaltheme); ?>img/folder_comp.png?ts=<?php echo time(); ?>" class="hcmsIconList" /> <a href="#"><?php echo getescapedtext ($hcms_lang['folder'][$lang]); ?> Marketing</a></div>
            <div style="padding:4px 0px 0px 42px; white-space:nowrap;"><img src="<?php echo getthemelocation($portaltheme); ?>img/folder_comp.png?ts=<?php echo time(); ?>" class="hcmsIconList" /> <a href="#"><?php echo getescapedtext ($hcms_lang['folder'][$lang]); ?> Product Management</a></div>
            <div style="padding:4px 0px 0px 42px; white-space:nowrap;"><img src="<?php echo getthemelocation($portaltheme); ?>img/folder_comp.png?ts=<?php echo time(); ?>" class="hcmsIconList" /> <a href="#"><?php echo getescapedtext ($hcms_lang['folder'][$lang]); ?> Public Relations</a></div>
          </div>
        </td>
        <td class="hcmsStartScreen" id="homeScreen" style="position:static; display:table-cell; background-attachment:scroll;">
          <!-- logo -->
          <div id="logo" style="margin:10px;">
            <img src="<?php echo getthemelocation($portaltheme); ?>img/logo_server.png?ts=<?php echo time(); ?>" style="max-width:420px; max-height:100px;" />
          </div>

          <!-- home boxes -->
          <?php
          if (is_file ($mgmt_config['abs_path_cms']."box/news.inc.php")) include ($mgmt_config['abs_path_cms']."box/news.inc.php");
          if (is_file ($mgmt_config['abs_path_cms']."box/recent_downloads.inc.php")) include ($mgmt_config['abs_path_cms']."box/recent_downloads.inc.php");
          ?>
        </td>
      </tr>
    </table>
  </div>

</div>

<?php includefooter(); ?>
</body>
</html>