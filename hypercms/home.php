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
// template engine
require ("function/hypercms_tplengine.inc.php");
// version info
require ("version.inc.php");



// input parameters
$action = getrequest ("action");
$homeboxes = getrequest ("homeboxes");
$token = getrequest ("token");

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// set home boxes for user
if ($action == "save" && checktoken ($token, $user))
{
  setuserboxes ($homeboxes, $user);
}

// wallpaper
$wallpaper = getwallpaper ();

// get home boxes for selection
$homebox_array = gethomeboxes ($siteaccess);

// get home boxes of user
$userbox_array = getuserboxes ($user);

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=0.9, maximum-scale=1.0, user-scalable=0" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />

<!-- main library -->
<script type="text/javascript" src="javascript/main.min.js"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>

<!-- Jquery and Jquery UI Autocomplete (used for search box) -->
<script type="text/javascript" src="javascript/jquery/jquery-3.5.1.min.js"></script>
<script type="text/javascript" src="javascript/jquery-ui/jquery-ui-1.12.1.min.js"></script>
<link rel="stylesheet" href="javascript/jquery-ui/jquery-ui-1.12.1.css" />

<style>
video#videoScreen
{ 
  position: fixed;
  top: 50%;
  left: 50%;
  min-width: 100%;
  min-height: 100%;
  width: auto;
  height: auto;
  z-index: -100;
  -ms-transform: translateX(-50%) translateY(-50%);
  -moz-transform: translateX(-50%) translateY(-50%);
  -webkit-transform: translateX(-50%) translateY(-50%);
  transform: translateX(-50%) translateY(-50%);
  background: url('<?php echo getthemelocation(); ?>/img/backgrd_start.png') no-repeat;
  background-size: cover; 
}

@media screen and (max-device-width: 800px)
{
  #videoScreen
  {
    display: none;
  }
}
</style>

<script type="text/javascript">

// default height for logo spacer
var spacerheight = 32;

function insertOption (newtext, newvalue)
{
  var selectbox = document.forms['box_form'].elements['box_array'];
  newentry = new Option (newtext, newvalue, false, true);
  var i;
  
  if (selectbox.length > 0)
  {  
    var position = -1;

    for (i=0; i<selectbox.length; i++)
    {
      if (selectbox.options[i].selected) position = i;
      // duplicate entry
      if (selectbox.options[i].value == newvalue) return false;
    }
    
    if (position != -1)
    {
      selectbox.options[selectbox.length] = new Option();
    
      for (i=selectbox.length-1; i>position; i--)
      {
        selectbox.options[i].text = selectbox.options[i-1].text;
        selectbox.options[i].value = selectbox.options[i-1].value;
      }
      
      selectbox.options[position+1] = newentry;
    }
    else selectbox.options[selectbox.length] = newentry;
  }
  else selectbox.options[selectbox.length] = newentry;
}

function moveSelected (select, down)
{
  if (select.selectedIndex != -1)
  {
    if (down)
    {
      if (select.selectedIndex != select.options.length - 1)
        var i = select.selectedIndex + 1;
      else
        return;
    }
    else
    {
      if (select.selectedIndex != 0)
        var i = select.selectedIndex - 1;
      else
        return;
    }

    var swapOption = new Object();

    swapOption.text = select.options[select.selectedIndex].text;
    swapOption.value = select.options[select.selectedIndex].value;
    swapOption.selected = select.options[select.selectedIndex].selected;

    for (var property in swapOption) select.options[select.selectedIndex][property] = select.options[i][property];
    for (var property in swapOption) select.options[i][property] = swapOption[property];
  }
}

function deleteSelected (select)
{
  if (select.length > 0)
  {
    for(var i=0; i<select.length; i++)
    {
      if (select.options[i].selected == true) select.remove(i);
    }
  }
}

function selectAllOptions (select)
{
  for (var i=0; i<select.options.length; i++)
  {
    select.options[i].selected = true;
  }
}

function submitHomeBoxes ()
{
  var form = document.forms['box_form'];

  if (form.elements['box_array'])
  {
    var select = form.elements['box_array'];
    var homeboxes = "|";

    if (select.options.length > 0)
    {
      for(var i=0; i<select.options.length; i++)
      {
        homeboxes = homeboxes + select.options[i].value + "|";
      }
    }

    form.elements['homeboxes'].value = homeboxes;
    form.submit();
  }
  else return false;
}

function html5support()
{
  if (hcms_html5file()) return 1;
  else return 0;
}

function setlogospacer ()
{
  // set logo spacer height
  var img = document.getElementById('logoimage');
  var spacerheight = img.clientHeight;
  if (spacerheight > 0) document.getElementById('homespacer').style.height = spacerheight + 10 + "px";
}

function setwallpaper ()
{
  // set background image
  <?php if (!empty ($wallpaper) && is_image ($wallpaper)) { ?>
  document.getElementById('startScreen').style.backgroundImage = "url('<?php echo $wallpaper; ?>')";
  return true;
  <?php } elseif (!empty ($wallpaper) && is_video ($wallpaper)) { ?>
  if (html5support())
  {
    document.getElementById('videoScreen').src = "<?php echo $wallpaper; ?>";
  }
  return true;
  <?php } else { ?>
  return false;
  <?php } ?>
}

function switchInfo (id)
{
  var layer_top = document.getElementById(id);
  var layer_back = document.getElementById('contentScreen');
  
  if (layer_top && layer_back)
  {
    if (layer_top.style.display == 'none')
    {
      // blur
      layer_back.classList.add('hcmsBlur');
      // show
      layer_top.style.display = 'inline';
      
    }
    else
    {
      // hide
      layer_top.style.display = 'none';
      // remove blur
      layer_back.classList.remove('hcmsBlur');
    }
  }
}

function openPopup (link, title)
{
  if (link != "")
  {
    document.getElementById('popupTitle').innerHTML = title;
    document.getElementById('popupViewer').src = link;
    hcms_minMaxLayer('popupLayer');
  }
}

function closePopup ()
{
  document.getElementById('popupTitle').innerHTML = '';
  document.getElementById('popupViewer').src = '<?php echo $mgmt_config['url_path_cms']; ?>loading.php';
  hcms_minMaxLayer('popupLayer');
}
</script>
</head>

<body onload="setlogospacer(); setwallpaper();">

<!-- popup (do not used nested fixed positioned div-layers due to MS IE and Edge issue) -->
<div id="popupLayer" class="hcmsHomeBox" style="position:fixed; left:50%; bottom:0px; z-index:-1; overflow:hidden; width:0px; height:0px; visibility:hidden;">
  <div style="display:block; padding-bottom:5px;">
    <div id="popupTitle" class="hcmsHeadline" style="float:left; margin:6px;"></div>
    <div style="float:right;"><img name="closedailystatsviewer" src="<?php echo getthemelocation(); ?>img/button_close.png" onClick="closePopup();" class="hcmsButtonBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('closedailystatsviewer','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" /></div>
  </div>
  <div style="width:100%; height:calc(100% - 42px);">
    <iframe id="popupViewer" src="<?php echo $mgmt_config['url_path_cms']; ?>loading.php" style="width:100%; height:100%; border:1px solid #000000;"></iframe>
  </div>
</div>

<!-- image background -->
<div id="startScreen" class="hcmsStartScreen" style="position:fixed; z-index:-200;"></div>

<?php if (!empty ($wallpaper) && is_video ($wallpaper)) { ?>
<!-- video background -->
<video id="videoScreen" playsinline="true" preload="auto" autoplay="true" loop="loop" muted="true" volume="0" poster="<?php echo getthemelocation(); ?>/img/backgrd_start.png">
  <source src="<?php echo $wallpaper; ?>" type="video/mp4">
</video>
<?php } ?>

<!-- logo -->
<div id="logo" style="position:fixed; top:10px; left:10px; z-index:0">
  <img id="logoimage" src="<?php echo getthemelocation(); ?>img/logo_server.png" style="max-width:<?php if ($is_mobile) echo "320px"; else echo "420px"; ?>; max-height:80px;" />
</div>

<?php if (!$is_mobile && checkrootpermission ('desktop') && checkrootpermission ('desktopsetting')) { ?>
  <!-- plus/minus button -->
  <div id="plusminus" style="position:fixed; top:12px; right:28px; z-index:200;">
    <img id="button_plusminus" onClick="switchInfo('menubox');" class="hcmsButtonTiny" style="width:43px; height:22px;" src="<?php echo getthemelocation(); ?>img/button_plusminus.png" alt="+/-" title="+/-" />
  </div>

  <!-- add / remove home boxes menu -->
  <div id="menubox" class="hcmsHomeBox" style="position:fixed; top:36px; right:25px; z-index:200; display:none;">
    <form id="box_form" name="box_form" action="" method="post">
      <input type="hidden" name="action" value="save" />
      <input type="hidden" name="homeboxes" value="" />
      <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
      
      <table class="hcmsTableStandard">
        <tr>
          <td style="vertical-align:top; text-align:left;">
            <span class="hcmsHeadline" style="padding:3px 0px 3px 0px; display:block;"><?php echo getescapedtext ($hcms_lang['select-object'][$lang]); ?></span>
            <?php
            // all available home boxes for selection
            if (is_array ($homebox_array) && sizeof ($homebox_array) > 0)
            {
              foreach ($homebox_array as $homebox_key => $homebox_name)
              {
                echo "
                <div onclick=\"insertOption('".$homebox_name."', '".$homebox_key."');\" style=\"display:block; cursor:pointer;\" title=\"".$homebox_name."\"><img src=\"".getthemelocation()."img/log_info.png\" class=\"hcmsIconList\" />&nbsp;".showshorttext($homebox_name, 30)."&nbsp;</div>";
              }
            }
            ?>
          </td>
          <td style="vertical-align:top; text-align:left;">
            <span class="hcmsHeadline" style="padding:3px 0px 3px 0px; display:block;"><?php echo getescapedtext ($hcms_lang['selected-object'][$lang]); ?></span>
            <select id="box_array" name="box_array" style="width:250px; min-height:280px;" size="18">
              <?php
              // user home boxes
              if (is_array ($userbox_array) && sizeof ($userbox_array) > 0)
              {
                foreach ($userbox_array as $userbox_key => $userbox_name)
                {
                  echo "
                  <option value=\"".$userbox_key."\">".showshorttext($userbox_name, 40)."</option>";
                }
              }
              ?>
            </select>
          </td>
          <td style="text-align:left; vertical-align:middle;">
            <img onClick="moveSelected(document.forms['box_form'].elements['box_array'], false)" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonUp" src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['move-up'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['move-up'][$lang]); ?>" /><br />                     
            <img onClick="deleteSelected(document.forms['box_form'].elements['box_array'])" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonDelete" src="<?php echo getthemelocation(); ?>img/button_delete.png" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" /><br />            
            <img onClick="moveSelected(document.forms['box_form'].elements['box_array'], true)" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonDown" src="<?php echo getthemelocation(); ?>img/button_movedown.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['move-down'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['move-down'][$lang]); ?>" /><br />
            <img onclick="submitHomeBoxes();" name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
            </td>
        </tr>
      </table>
    </form>
  </div>
  <?php } ?>

<!-- content -->
<div id="contentScreen" style="position:fixed; top:0; left:0; right:0; height:100%; overflow:auto; z-index:100; transition:all 0.3s linear;">

  <!-- spacer -->
  <div class="hcmsHomeSpacer" id="homespacer"></div>

  <!-- home boxes -->
  <?php
  if (is_array ($userbox_array))
  {
    $homeboxes_path = showhomeboxes ($userbox_array, $user, $lang);

    if (is_array ($homeboxes_path))
    {
      foreach ($homeboxes_path as $temp) include ($temp);
    }
  }
  ?>

  <!-- spacer -->
  <div style="clear:both; display:block; height:10px;"></div>

</div>

<?php includefooter(); ?>
</body>
</html>