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
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />

<!-- main library -->
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>

<!-- Jquery and Jquery UI Autocomplete (used for search box) -->
<script type="text/javascript" src="javascript/jquery/jquery.min.js"></script>
<script type="text/javascript" src="javascript/jquery-ui/jquery-ui.min.js"></script>
<link rel="stylesheet" href="javascript/jquery-ui/jquery-ui.css" />

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
  background: url('<?php echo getthemelocation(); ?>img/backgrd_start.jpg') no-repeat;
  background-size: cover;
}

@media screen and (max-device-width: 800px)
{
  #videoScreen
  {
    display: none;
  }
}

.Clock
{
  width: 320px;
  height: 320px;
  background-image: url('<?php echo getthemelocation(); ?>img/backgrd_clock.png');
  background-size: cover;
  display: flex;
  justify-content: center;
  align-items: center;
}
.Clock:before
{
  content: '';
  position: absolute;
  width: 15px;
  height: 15px;
  background: #FFF;
  border-radius: 50%;
  z-index: 10000;
}
.Clock .hour,
.Clock .minute,
.Clock .second
{
  position: absolute;
}
.Clock .hour, .hr
{
  width: 160px;
  height: 160px;
}
.Clock .minute, .min
{
  width: 190px;
  height: 190px;
}
.Clock .second, .sec
{
  width: 230px;
  height: 230px;
}
.hr, .min, .sec
{
  display: flex;
  justify-content: center;
  /*align-items: center;*/
  position: absolute;
  border-radius: 50%;
}
.hr:before
{
  content: '';
  position: absolute;
  width: 8px;
  height: 80px;
  background: #FFF;
  z-index: 10;
  border-radius: 6px 6px 0 0;
}
.min:before
{
  content: '';
  position: absolute;
  width: 4px;
  height: 90px;
  background: #FFF;
  z-index: 11;
  border-radius: 6px 6px 0 0;
}
.sec:before
{
  content: '';
  position: absolute;
  width: 2px;
  height: 150px;
  background: #FFF;
  z-index: 12;
  border-radius: 6px 6px 0 0;
}
</style>

<script type="text/javascript">

// default height for logo spacer
var spacerheight = 32;

function moveBoxEntry (fbox, tbox, tsort=true)
{
  var arrFbox = new Array();
  var arrTbox = new Array();
  var arrLookup = new Array();
  var i;

  for (i = 0; i < tbox.options.length; i++)
  {
    arrLookup[tbox.options[i].text] = tbox.options[i].value;
    arrTbox[i] = tbox.options[i].text;
  }

  var fLength = 0;
  var tLength = arrTbox.length;

  for (i = 0; i < fbox.options.length; i++)
  {
    arrLookup[fbox.options[i].text] = fbox.options[i].value;
    if (fbox.options[i].selected && fbox.options[i].value != '')
    {
      arrTbox[tLength] = fbox.options[i].text;
      tLength++;
    }
    else
    {
      arrFbox[fLength] = fbox.options[i].text;
      fLength++;
    }
  }

  if (tsort == true) arrTbox.sort();
  fbox.length = 0;
  tbox.length = 0;
  var c;

  for (c = 0; c < arrFbox.length; c++)
  {
    var no = new Option();
    no.value = arrLookup[arrFbox[c]];
    no.text = arrFbox[c];
    fbox[c] = no;
  }

  for (c = 0; c < arrTbox.length; c++)
  {
    var no = new Option();
    no.value = arrLookup[arrTbox[c]];
    no.text = arrTbox[c];
    tbox[c] = no;
  }
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

function selectAllOptions (select)
{
  for (var i=0; i<select.options.length; i++)
  {
    select.options[i].selected = true;
  }
}

function submitHomeBoxes ()
{
  var form = document.forms['homebox_form'];

  if (form.elements['homebox_selected'])
  {
    var select = form.elements['homebox_selected'];
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

function switchFullscreen ()
{
  var layer_back = document.getElementById('contentScreen');
  
  if (layer_back)
  {
    if (layer_back.style.display == 'none')
    {
      // show
      layer_back.style.display = 'inline';
    }
    else
    {
      // hide
      layer_back.style.display = 'none';
      parent.minNavFrame();
    }
  }
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

<body onload="setlogospacer(); <?php if ($hcms_themename != "transparent") echo "setwallpaper();"; ?>">

<!-- popup (do not use nested fixed positioned div-layers due to MS IE and Edge issue) -->
<div id="popupLayer" class="hcmsHomeBox" style="position:fixed; left:50%; bottom:0px; z-index:-1; overflow:hidden; width:0px; height:0px; visibility:hidden;">
  <div style="display:block; padding-bottom:5px;">
    <div id="popupTitle" class="hcmsHeadline" style="float:left; margin:6px;"></div>
    <div style="float:right;"><img name="closedailystatsviewer" src="<?php echo getthemelocation("night"); ?>img/button_close.png" onclick="closePopup();" class="hcmsButtonTiny hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('closedailystatsviewer','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" /></div>
  </div>
  <div style="width:100%; height:calc(100% - 42px);">
    <iframe id="popupViewer" src="<?php echo $mgmt_config['url_path_cms']; ?>loading.php" style="width:100%; height:100%; border:1px solid #000000;"></iframe>
  </div>
</div>

<!-- image background -->
<div id="startScreen" class="<?php if ($hcms_themename != "transparent") echo "hcmsStartScreen"; ?>" style="position:fixed; z-index:-200;"></div>

<?php if (!empty ($wallpaper) && is_video ($wallpaper)) { ?>
<!-- video background -->
<video id="videoScreen" playsinline="true" preload="auto" autoplay="true" loop="loop" muted="true" volume="0" poster="<?php echo getthemelocation(); ?>/img/backgrd_start.jpg">
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
    <img id="button_plusminus" onclick="switchInfo('menubox');" class="hcmsButtonTiny" style="width:43px; height:22px;" src="<?php echo getthemelocation(); ?>img/button_plusminus.png" alt="+/-" title="+/-" />
    <img id="button_fullscreen" onclick="switchFullscreen();" class="hcmsButtonTiny" style="width:22px; height:22px;" src="<?php echo getthemelocation(); ?>img/edit_drag.png" alt="<?php echo getescapedtext ($hcms_lang['enable-fullscreen'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['enable-fullscreen'][$lang]); ?>" />
  </div>

  <!-- add / remove home boxes menu -->
  <div id="menubox" class="hcmsHomeBox" style="position:fixed; top:36px; right:25px; z-index:200; display:none;">
    <form id="homebox_form" name="homebox_form" action="" method="post">
      <input type="hidden" name="action" value="save" />
      <input type="hidden" name="homeboxes" value="" />
      <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
      
      <table class="hcmsTableStandard">
        <tr>
          <td style="vertical-align:top; text-align:left;">
            <span class="hcmsHeadline" style="padding:3px 0px 3px 0px; display:block;"><?php echo getescapedtext ($hcms_lang['select-object'][$lang]); ?></span>
            <select multiple name="homebox_select" style="width:250px; height:280px;">
              <?php
              // all available home boxes for selection
              if (is_array ($homebox_array) && sizeof ($homebox_array) > 0)
              {
                natcasesort ($homebox_array);

                foreach ($homebox_array as $homebox_key => $homebox_name)
                {
                  if (!in_array ($homebox_name, $userbox_array))
                  {
                    echo "
                  <option value=\"".$homebox_key."\">".showshorttext($homebox_name, 30, false)."</option>";
                  }
                }
              }
              ?>
              </select>
          </td>
          <td class="text-align:center; vertical-align:middle;">
            <br />
            <button type="button" class="hcmsButtonBlue" style="width:40px; margin:5px; display:block;" onclick="moveBoxEntry(this.form.elements['homebox_select'], this.form.elements['homebox_selected'], false);">&gt;&gt;</button>
            <button type="button" class="hcmsButtonBlue" style="width:40px; margin:5px; display:block;" onclick="moveBoxEntry(this.form.elements['homebox_selected'], this.form.elements['homebox_select'], true);">&lt;&lt;</button>
          </td>
          <td style="vertical-align:top; text-align:left;">
            <span class="hcmsHeadline" style="padding:3px 0px 3px 0px; display:block;"><?php echo getescapedtext ($hcms_lang['selected-object'][$lang]); ?></span>
            <select id="homebox_selected" name="homebox_selected" style="width:250px; height:280px;" size="18">
              <?php
              // selected user home boxes
              if (is_array ($userbox_array) && sizeof ($userbox_array) > 0)
              {
                foreach ($userbox_array as $userbox_key => $userbox_name)
                {
                  echo "
                <option value=\"".$userbox_key."\">".showshorttext($userbox_name, 30, false)."</option>";
                }
              }
              ?>
            </select>
          </td>
          <td style="text-align:left; vertical-align:middle;">
            <img onclick="moveSelected(document.forms['homebox_form'].elements['homebox_selected'], false)" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonUp" src="<?php echo getthemelocation("night"); ?>img/button_moveup.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['move-up'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['move-up'][$lang]); ?>" /><br />                     
            <img onclick="moveBoxEntry(document.forms['homebox_form'].elements['homebox_selected'], document.forms['homebox_form'].elements['homebox_select'], true)" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonDelete" src="<?php echo getthemelocation("night"); ?>img/button_delete.png" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" /><br />            
            <img onclick="moveSelected(document.forms['homebox_form'].elements['homebox_selected'], true)" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonDown" src="<?php echo getthemelocation("night"); ?>img/button_movedown.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['move-down'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['move-down'][$lang]); ?>" /><br />
            <img onclick="submitHomeBoxes();" name="Button" src="<?php echo getthemelocation("night"); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
            </td>
        </tr>
      </table>
    </form>
  </div>
  <?php } ?>

<!-- content -->
<div id="contentScreen" style="position:fixed; top:0; left:0; right:0; height:100%; overflow:auto; z-index:100; transition:all 0.3s linear; <?php if ($is_mobile) echo "text-align:center;"; ?>">

  <!-- spacer -->
  <div class="hcmsHomeSpacer" id="homespacer"></div>

  <!-- update info -->
  <?php
  if (!$is_mobile && !empty ($mgmt_config['update_info']) && update_software ("check"))
  {
    echo "
  <div id=\"updateinfo\" class=\"hcmsHomeBox hcmsPriorityAlarm\" style=\"position:fixed; top:8px; right:110px; text-align:center;\">
    <div class=\"hcmsHeadline\" style=\"padding:0px 4px; white-space:nowrap;\"><img src=\"".getthemelocation()."img/info.png\" class=\"hcmsIconList\" /> A software update is available</div>
  </div>";
  }
  ?>

  <!-- home boxes -->
  <?php
  if (is_array ($userbox_array))
  {
    // function showhomeboxes returns a verified array of existing home box pathes
    $homeboxes_path = showhomeboxes ($userbox_array);

    if (is_array ($homeboxes_path))
    {
      foreach ($homeboxes_path as $temp)
      {
        include ($temp);
      }
    }
  }
  ?>

  <!-- spacer -->
  <div style="clear:both; display:block; height:10px;"></div>

</div>

<?php includefooter(); ?>

</body>
</html>