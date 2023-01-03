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
$action = getrequest_esc ("action");
$site = getrequest_esc ("site", "publicationname");
$cat = getrequest_esc ("cat", "objectname");
$persname = getrequest_esc ("persname", "objectname");
$persfile = getrequest ("persfile", "objectname");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'persprof') || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";

// define category name
if ($cat == "tracking")
{
  $item_type = $hcms_lang['customer-tracking'][$lang];
}
elseif ($cat == "profile")
{
  $item_type = $hcms_lang['customer-profile'][$lang];
}

// include scripts
if ($action == "item_create")
{
  if (
       valid_objectname ($persname) &&
       ($cat == "profile" && checkglobalpermission ($site, 'persprof') && checkglobalpermission ($site, 'persprofcreate')) || 
       ($cat == "tracking" && checkglobalpermission ($site, 'perstrack') && checkglobalpermission ($site, 'perstrackcreate'))
     )
  {
    $result = createpersonalization ($site, $persname, $cat);
    
    $add_onload =  $result['add_onload'];
    $show = $result['message'];
  }
}
elseif ($action == "item_delete")
{
  if (
       valid_objectname ($persfile) &&
       ($cat == "profile" && checkglobalpermission ($site, 'persprof') && checkglobalpermission ($site, 'persprofdelete')) || 
       ($cat == "tracking" && checkglobalpermission ($site, 'perstrack')  && checkglobalpermission ($site, 'perstrackdelete'))
     )
  {
    $result = deletepersonalization ($site, $persfile, $cat);
    
    $add_onload =  $result['add_onload'];
    $show = $result['message'];
  }
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

function selectpers (selObj)
{
  if (selObj.options[selObj.selectedIndex].value != "")
  {
    parent.frames['mainFrame'].location.href = 'pers_form.php?site=<?php echo url_encode($site); ?>&cat=<?php echo url_encode($cat); ?>&save=no&persfile=' + selObj.options[selObj.selectedIndex].value;
  }
  else
  {
    parent.frames['mainFrame'].location.href = 'empty.php';
  }
}

function deletepers ()
{
  check = confirm (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['warning'][$lang]); ?>\n<?php echo getescapedtext ($hcms_lang['the-selected-item-will-be-removed'][$lang]); ?>\n<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-the-template'][$lang]); ?>"));

  if (check == true) document.forms['item_delete'].submit();
  return check;
}

function checkForm_chars (text, exclude_chars)
{
  exclude_chars = exclude_chars.replace (/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
  
	var expr = new RegExp ("[^a-zA-Z0-9" + exclude_chars + "]", "g");
	var separator = ', ';
	var found = text.match(expr); 
	
  if (found)
  {
		var addText = '';
    
		for (var i = 0; i < found.length; i++)
    {
			addText += found[i]+separator;
		}
    
		addText = addText.substr (0, addText.length-separator.length);
		alert ("<?php echo getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters'][$lang]); ?>\n " + addText);
		return false;
	}
  else
  {
		return true;
	}
}

function checkForm_item_create ()
{  
  var form = document.forms['item_create'];
  
  if (form.elements['persname'].value.trim() == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['a-name-is-required'][$lang]); ?>"));
    form.elements['persname'].focus();
    return false;
  }
  
  if (!checkForm_chars (form.elements['persname'].value, "-_"))
  {
    form.elements['persname'].focus();
    return false;
  }
  
  form.submit();
  return true;    
}
</script>
</head>

<body class="hcmsWorkplaceControl" onLoad="<?php echo $add_onload; ?>">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:10px;"); ?>

<?php echo showmessage ($show, 660, 70, $lang, "position:fixed; left:10px; top:10px;"); ?>

<div class="hcmsLocationBar">
  <?php if (!$is_mobile) { ?>
  <table class="hcmsTableNarrow">
    <tr>
      <td class="hcmsHeadline"><?php echo getescapedtext ($site." &gt; ".$item_type); ?></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>  
  </table>
  <?php } else { ?>
  <span style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo getescapedtext ($site." &gt; ".$item_type); ?></span>
  <?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar" style="<?php if ($is_mobile) echo "380px;"; else echo "620px;"; ?>;">
  <div class="hcmsToolbarBlock" style="padding:2px;">
    <form name="item_delete" action="" method="post">
      <input type="hidden" name="site" value="<?php echo $site; ?>" />
      <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
      <input type="hidden" name="action" value="item_delete" />

      <span class="hcmsInvertColor"><?php echo getescapedtext ($item_type); ?></span>
      <select name="persfile" onChange="selectpers(this);" style="width:<?php if ($is_mobile) echo "130px"; else echo "200px"; ?>;" title="<?php echo getescapedtext ($item_type); ?>">
        <option value=""><?php echo getescapedtext ($hcms_lang['select'][$lang]); ?></option>
        <?php
        $temp_dir = $mgmt_config['abs_path_data']."customer/".$site."/";
        $dir_item = @dir ($temp_dir);

        $i = 0;
        $item_files = array();
        $item_option_edit = array();

        if ($dir_item != false)
        {
          while ($entry = $dir_item->read())
          {
            if ($entry != "." && $entry != ".." && is_file ($temp_dir.$entry))
            {
              if ($cat == "tracking" && strpos ($entry, ".track.dat") > 0)
              {
                $item_files[$i] = $entry;
              }
              elseif ($cat == "profile" && strpos ($entry, ".prof.dat") > 0)
              {
                $item_files[$i] = $entry;
              }

              $i++;
            }
          }

          $dir_item->close();

          if (sizeof ($item_files) > 0)
          {
            natcasesort ($item_files);
            reset ($item_files);

            foreach ($item_files as $value)
            {
              if ($cat == "tracking" || strpos ($value, ".track.dat") > 0) $item_name = substr ($value, 0, strpos ($value, ".track.dat"));
              elseif ($cat == "profile" || strpos ($value, ".prof.dat") > 0) $item_name = substr ($value, 0, strpos ($value, ".prof.dat"));

              echo "
              <option value=\"".url_encode($value)."\" ".($persname == $item_name ? "selected=\"selected\"" : "").">".$item_name."</option>";
            }
          }
        }
        ?>
      </select>
    </form>
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if (($cat == "profile" && checkglobalpermission ($site, 'persprof') && checkglobalpermission ($site, 'persprofcreate')) || ($cat == "tracking" && checkglobalpermission ($site, 'perstrack') && checkglobalpermission ($site, 'perstrackcreate')))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers('createpersLayer','','show', 'hcms_messageLayer','','hide')\" name=\"media_new\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_tpl_new.png\" alt=\"".getescapedtext ($hcms_lang['create'][$lang])."\" title=\"".getescapedtext ($hcms_lang['create'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_tpl_new.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
    <?php
    if (($cat == "profile" && checkglobalpermission ($site, 'persprof') && checkglobalpermission ($site, 'persprofdelete')) || ($cat == "tracking" && checkglobalpermission ($site, 'perstrack') && checkglobalpermission ($site, 'perstrackdelete')))
    {echo "<img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"deletepers();\" name=\"media_delete\" src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_tpl_delete.png\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" />\n";}
    else
    {echo "<img src=\"".getthemelocation($hcms_themeinvertcolors)."img/button_tpl_delete.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";}
    ?>
  </div>
  <div class="hcmsToolbarBlock">
    <?php echo showhelpbutton ("personalizationguide", true, $lang, ""); ?>
  </div>
</div>

<!-- create personalization -->
<div id="createpersLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:70px; left:10px; top:10px; visibility:hidden;">
  <form name="item_create" action="" method="post" onsubmit="return checkForm_item_create();">
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="action" value="item_create" />
    <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
    
    <table class="hcmsTableNarrow" style="width:100%; height:60px;">
      <tr>
        <td style="overflow:auto;">
          <span class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['create'][$lang]); ?></span><br />
          <span style="white-space:nowrap;">
            <input type="text" name="persname" maxlength="100" style="width:<?php if ($is_mobile) echo "200px"; else echo "80%"; ?>;" placeholder="<?php echo getescapedtext ($item_type); ?>" title="<?php echo getescapedtext ($item_type); ?>" />
            <img name="Button1" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_item_create();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
          </span>
        </td>
        <td style="width:38px; text-align:right; vertical-align:top;">
          <img name="hcms_mediaClose1" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose1','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('createpersLayer','','hide');" />
        </td>        
      </tr>
    </table>
  </form>
</div>

</body>
</html>
