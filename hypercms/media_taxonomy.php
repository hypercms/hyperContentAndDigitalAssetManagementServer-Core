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
$action = getrequest ("action");
$site = getrequest_esc ("site", "publicationname");
$position = getrequest ("position", "numeric", 0);
$selectedlang = getrequest ("selectedlang", "array");
$start = getrequest ("start", "numeric", 1);
$perpage = getrequest ("perpage", "numeric", 20);
$saveindex_start = getrequest ("saveindex_start", "numeric");
$saveindex_stop = getrequest ("saveindex_stop", "numeric");
$taxonomy = getrequest ("taxonomy", "array");
$temp_sourcelang = getrequest ("temp_sourcelang");
$temp_targetlang = getrequest ("temp_targetlang");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'template') || !checkglobalpermission ($site, 'tpl') || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// initialize
$show = "";
$show_taxonomy = "";
$id = $start;
$loadtaxonomy = true;
$restoretaxonomy = false;
if (empty ($selectedlang)) $selectedlang = array();
$selectedlang_temp = $selectedlang;

// create secure token
$token_new = createtoken ($user);

if (!empty ($mgmt_config['abs_path_data']) && valid_publicationname ($site) && !empty ($taxonomy[$saveindex_start]) && checktoken ($token, $user))
{
  // create new row and save as CSV
  if ($action == "createrow")
  {
    // add new row after position
    $new_array = array();
    $taxonomy_new = array();
    
    // create columns for new row based on first row columns
    foreach ($taxonomy[$saveindex_start] as $langcode => $value)
    {
      $new_array[$langcode] = "";
    }
    
    $new_array['level'] = "1";

    foreach ($taxonomy as $row => $temp_array)
    {
      if ($row < $position)
      {
        // remain
        $taxonomy_new[$row] = $temp_array;
      }
      elseif ($row == $position)
      {
        // remain
        $taxonomy_new[$row] = $temp_array;

        // add new row
        $new_id = intval ($row) + 1;
        $taxonomy_new[$new_id] = $new_array;
      }
      elseif ($row > $position)
      {
        // shift row
        $new_id = intval ($row) + 1;
        $taxonomy_new[$new_id] = $temp_array;
      }
    }

    $savefile = savetaxonomy ($site, $taxonomy_new, $saveindex_start, $saveindex_stop);
    
    if ($savefile == false)   
    {  
      $show = "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['the-data-could-not-be-saved'][$lang])."</p>";
    }
  }
  // delete row and save as CSV
  elseif ($action == "deleterow")
  {
    // remove row
    unset ($taxonomy[$position]);

    if (sizeof ($taxonomy) > 0)
    {
      $savefile = savetaxonomy ($site, $taxonomy, $saveindex_start, $saveindex_stop);
    
      if ($savefile == false)   
      {  
        $show = "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['the-data-could-not-be-saved'][$lang])."</p>";
      }
    }
    else
    {
      $loadtaxonomy = false;
    }
  }
  // save as CSV
  elseif ($action == "save")
  {
    $savefile = savetaxonomy ($site, $taxonomy, $saveindex_start, $saveindex_stop);
    
    if ($savefile == false)   
    {  
      $show = "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['the-data-could-not-be-saved'][$lang])."</p>";
    }
  }
  // reindex content based on taxonomy
  elseif ($action == "reindex")
  {
    $create = createtaxonomy ($site, false);
   
    if ($create == false)   
    {  
      $show = "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['the-data-could-not-be-saved'][$lang])."</p>";
    }
    else $show = getescapedtext ($hcms_lang['the-data-was-saved-successfully'][$lang]);
  }
  // delete taxonomy
  elseif ($action == "deletetaxonomy")
  {
    $delete = deletetaxonomy ($site, true);
   
    if ($delete == false)   
    {  
      $show = "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['the-object-could-not-be-removed'][$lang])."</p>";
    }
    else $show = getescapedtext ($hcms_lang['the-object-was-deleted'][$lang]);
  }
  // restore taxonomy
  elseif ($action == "restoretaxonomy")
  {
    // copy default CSV taxonomy 
    if (is_file ($mgmt_config['abs_path_data']."include/default.taxonomy.csv"))
    {
      $restored = copy ($mgmt_config['abs_path_data']."include/default.taxonomy.csv", $mgmt_config['abs_path_data']."include/".$site.".taxonomy.csv");
    }

    // copy default PHP taxonomy 
    if (!empty ($restored) && is_file ($mgmt_config['abs_path_data']."include/default.taxonomy.inc.php"))
    {
      $restored = copy ($mgmt_config['abs_path_data']."include/default.taxonomy.inc.php", $mgmt_config['abs_path_data']."include/".$site.".taxonomy.inc.php");
    }

    if (empty ($restored))   
    {  
      $show = "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['the-data-could-not-be-saved'][$lang])."</p>";
    }
    else $show = getescapedtext ($hcms_lang['the-data-was-saved-successfully'][$lang]);
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script type="text/javascript" src="javascript/main.min.js"></script>
<script type="text/javascript" src="javascript/jquery/jquery-3.5.1.min.js"></script>

<style>
#taxonomy select
{
  padding-top:3px;
  padding-bottom:3px;
}
</style>

<script type="text/javascript">

var changed = false;
var text = "";

function startpoint (row)
{
  var form = document.forms['taxonomyform'];

  hcms_showFormLayer ('savelayer', 0);
  if (changed == true) form.elements['action'].value = "save";
  else form.elements['action'].value = "scroll";
  form.elements['start'].value = row;
  form.submit();
}

function createrow (position)
{
  if (position >= 0)
  {
    var form = document.forms['taxonomyform'];
  
    hcms_showFormLayer ('savelayer', 0);
    form.elements['action'].value = "createrow";
    form.elements['position'].value = position;
    form.submit();
  }
}

function deleterow (position)
{
  if (position >= 0)
  {
    var form = document.forms['taxonomyform'];
  
    hcms_showFormLayer ('savelayer', 0);
    form.elements['action'].value = "deleterow";
    form.elements['position'].value = position;
    form.submit();
  }
}

// deprecated since version 8.1.3
function deletelanguage (langcode)
{
  var form = document.forms['taxonomyform'];
  
  check = confirm ("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-remove-the-item'][$lang]); ?>");

  if (check == true)
  {
    var cols = document.getElementsByClassName(langcode);
    
    for (var i = 1; i < cols.length; i++)
    {
      if (cols[i].getElementsByTagName('input'))
      {
        var input = cols[i].getElementsByTagName('input');      
        if (input[0].value) input[0].value = '';
      }
    }
    
    document.getElementById(langcode).click();
  }
}

function switchlanguage (e)
{
  if (e.value != "" && document.getElementsByClassName(e.value))
  {
    var langcode = e.value;
    var cols = document.getElementsByClassName(langcode);
    
    for (var i = 0; i < cols.length; i++)
    {
      if (e.checked == true)
      {
        cols[i].style.display = "";
      }
      else
      {
        cols[i].style.display = "none";
      }
    }
  }
}

function translatelanguage (sourcelang_id, targetlang_id)
{
  if (sourcelang_id != "" && targetlang_id != "")
  {
    var sourceLang = "";
    var targetLang = "";
    var sourceCollection = "";
  
    if (document.getElementById(sourcelang_id)) sourceLang = document.getElementById(sourcelang_id).value;
    if (document.getElementById(targetlang_id)) targetLang = document.getElementById(targetlang_id).value;
    
    if (sourceLang != "" && targetLang != "")
    {
      var sourceCols = document.getElementsByClassName(sourceLang);
      var targetCols = document.getElementsByClassName(targetLang);
      
      // collect
      for (var i = 1; i < sourceCols.length; i++)
      {
        if (sourceCols[i].getElementsByTagName('input') && targetCols[i].getElementsByTagName('input'))
        {
          var sourceText = sourceCols[i].getElementsByTagName('input');

          // merge
          sourceCollection += sourceText[0].value.trim() + '\n';
        }
      }

      // translate
      var translated = hcms_translateText (sourceCollection, sourceLang, targetLang);

      if (translated != "")
      {
        // split
        translatedText = translated.split("\n");
        
        // items count is not the same
        if ((sourceCols.length - 1) != translatedText.length) alert (hcms_entity_decode('<?php echo $hcms_lang['error-occured'][$lang]; ?> (items mismatch)'));
      }
      // no translation available
      else alert (hcms_entity_decode('<?php echo $hcms_lang['error-occured'][$lang]; ?> (no translation)'));

      // insert translated text
      for (var i = 1; i < targetCols.length; i++)
      {
        if (sourceCols[i].getElementsByTagName('input') && targetCols[i].getElementsByTagName('input'))
        {
          var targetText = targetCols[i].getElementsByTagName('input');
    
          targetText[0].value = translatedText[i-1].trim();
          changed = true;
        }
      }
    }
  }
}

function setlevel (e)
{
 if (e)
 {
   e.style.marginLeft = ((e.value - 1) * 20) + "px";
   changed = true;
 }
}

function settext (e)
{
  if (e.value) changed = true;
}

function savetaxonomy ()
{
  var form = document.forms['taxonomyform'];

  hcms_showFormLayer ('savelayer', 0);
  form.elements['action'].value = "save";
  form.submit();
}

function reindex ()
{
  var form = document.forms['taxonomyform'];
  
  check = confirm ("<?php echo getescapedtext ($hcms_lang['apply-changes'][$lang]); ?>");

  if (check == true)
  {   
    hcms_showFormLayer ('savelayer', 0);
    form.elements['action'].value = "reindex";
    form.submit();
  }
}

function deletetaxonomy ()
{
  var form = document.forms['taxonomyform'];

  check = confirm (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['warning'][$lang]); ?>\n<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-delete-this-item'][$lang]); ?>"));

  if (check == true)
  {
    hcms_showFormLayer ('savelayer', 0);
    form.elements['action'].value = "deletetaxonomy";
    form.submit();
  }
}

function restoretaxonomy ()
{
  var form = document.forms['taxonomyform'];

  check = confirm ("<?php echo getescapedtext ($hcms_lang['apply-changes'][$lang]); ?>");

  if (check == true)
  {
    hcms_showFormLayer ('savelayer', 0);
    form.elements['action'].value = "restoretaxonomy";
    form.submit();
  }
}

function hcms_saveEvent ()
{
  savetaxonomy();
}

$(document).ready(function(){
  $(".up,.down").click(function(){
    var row = $(this).parents("tr:first");

    if ($(this).is(".up"))
    {
      row.insertBefore(row.prev());
      changed = true;
    }
    else
    {
      row.insertAfter(row.next());
      changed = true;
    }
  });
});
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- saving --> 
<div id="savelayer" class="hcmsLoadScreen"></div>

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<p class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['taxonomy'][$lang]); ?></p>
<hr />

<?php
echo showmessage ($show, 600, 70, $lang, "position:fixed; left:10px; top:50px;");

// load languages
$languages = getlanguageoptions ();

// evaluate activated language options and set default language if no language is set active for the publication 
$activelanguage = array();

if (is_array ($languages))
{
  foreach ($languages as $langcode => $langname)
  {
    if (is_activelanguage ($site, $langcode))
    {
      $activelanguage[$langcode] = $langname;
    }
  }
}

if (sizeof ($activelanguage) < 1) $activelanguage['en'] = "English";

// load taxonomy
if (!empty ($loadtaxonomy)) $result = loadtaxonomy ($site, $start, $perpage, true, $restoretaxonomy);

// no taxonomy available, set first taxonomy element
if (empty ($result))
{
  $result[1]['level'] = 1;
  
  foreach ($activelanguage as $langcode => $langname)
  {
    $result[1][$langcode] = "";
  }
}

// create table rows
if (is_array ($result))
{
  foreach ($result as $row => $temp_array)
  {
    if ($row != "count")
    {
      $show_taxonomy .= "
              <tr style=\"height:32px;\">";
      
      // create table cells for each row  
      // first column
      if (!empty ($temp_array['level']))
      {
        $level = $temp_array['level'];
        
        $show_taxonomy .= "
              <td class=\"hcmsRowHead2\" style=\"text-align:left; white-space:nowrap; width:160px; box-sizing:border-box;\">
                <select name=\"taxonomy[".$row."][level]\" class=\"hcmsRowHead1\" onchange=\"setlevel(this)\" style=\"margin-left:".(($level - 1) * 20)."px\">";

        for ($l=1; $l<=5; $l++)
        {
          $show_taxonomy .= "
                  <option ".($level == $l ? "selected=\"selected\"" : "").">".$l."</option>";
        }
        
        $show_taxonomy .= "
                </select><img src=\"".getthemelocation()."img/button_arrow_right.png\" align=\"absmiddle\" class=\"hcmsButtonSizeSquare\" />
              </td>
              <td class=\"hcmsRowHead2\" style=\"text-align:right; width:32px; box-sizing:border-box;\"><b>".$id."</b>&nbsp;</td>
              <td class=\"hcmsRowHead2\" style=\"text-align:right; width:92px !important; min-width:92px; box-sizing:border-box; white-space:nowrap;\">
                <img src=\"".getthemelocation()."img/button_moveup.png\" class=\"hcmsButton hcmsIconList up\" alt=\"".getescapedtext ($hcms_lang['move-up'][$lang])."\" title=\"".getescapedtext ($hcms_lang['move-up'][$lang])."\" />
                <img src=\"".getthemelocation()."img/button_movedown.png\" class=\"hcmsButton hcmsIconList down\" alt=\"".getescapedtext ($hcms_lang['move-down'][$lang])."\" title=\"".getescapedtext ($hcms_lang['move-down'][$lang])."\" />
                <img src=\"".getthemelocation()."img/button_file_new.png\" class=\"hcmsButton hcmsIconList\" onclick=\"createrow(".$row.");\" alt=\"".getescapedtext ($hcms_lang['create'][$lang])."\" title=\"".getescapedtext ($hcms_lang['create'][$lang])."\" />
                <img src=\"".getthemelocation()."img/button_delete.png\" class=\"hcmsButton hcmsIconList\" onclick=\"deleterow(".$row.");\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" />
              </td>";
      }
    
      // language columns / input fields
      if (!empty ($activelanguage) && is_array ($activelanguage))
      {
        reset ($activelanguage);
        
        foreach ($activelanguage as $langcode => $langname)
        {
          // memorize selected languages initially
          if (!empty ($langcode) && empty ($selectedlang_temp) || (is_array ($selectedlang_temp) && sizeof ($selectedlang_temp) < 1))
          {
            $selectedlang[$langcode] = $langcode;
          }

          // text
          if (!empty ($result[$row][$langcode])) $text = $result[$row][$langcode];
          else $text = "";
          
          // display or hide based on selected languages
          if (empty ($selectedlang[$langcode])) $style = "display:none;";
          else $style = "";

          $show_taxonomy .= "
              <td class=\"".$langcode."\" style=\"".$style." box-sizing:border-box;\">
                <input type=\"text\" name=\"taxonomy[".$row."][".$langcode."]\" value=\"".trim ($text)."\" onkeyup=\"settext(this);\" class=\"hcmsRowData1\" style=\"\" />
              </td>";
        }
      }

      $show_taxonomy .= "
      </tr>";

      $id++;
    }        
  }
}
?>
          
<form name="taxonomyform" action="" method="post">
  <input type="hidden" name="action" value="" />
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="position" value="" />
  <input type="hidden" name="start" value="<?php echo $start; ?>" />
  <input type="hidden" name="perpage" value="<?php echo $perpage; ?>" />
  <input type="hidden" name="saveindex_start" value="<?php echo $start; ?>" />
  <input type="hidden" name="saveindex_stop" value="<?php echo ($start + $perpage - 1); ?>" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />

  <?php
  // language options / display checkboxes only if more than 1 language option is active
  if (sizeof ($activelanguage) > 1)
  {
    echo "
  <!-- language options to display -->
  <p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['selected-languages'][$lang])."</p>
  <div style=\"width:94%; max-height:160px; overflow:auto;\">";
  
    foreach ($activelanguage as $langcode => $langname)
    {
      echo "
      <label style=\"display:inline-block; width:200px;\"><input type=\"checkbox\" id=\"".$langcode."\" name=\"selectedlang[".$langcode."]\" value=\"".$langcode."\" onclick=\"switchlanguage(this);\" ".(!empty ($selectedlang[$langcode]) ? "checked" : "")."> ".$languages[$langcode]."</label>";
    }
    
    echo "
  </div>
  <hr />";
  
    echo "
  <!-- translation -->
  <div style=\"width:94%;\">
    <span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['translate'][$lang])."</span>&nbsp;
    <select id=\"sourceLang\" name=\"temp_sourcelang\" style=\"width:165px;\">";

    if (!empty ($activelanguage) && is_array ($activelanguage))
    {
      foreach ($activelanguage as $langcode => $langname)
      {
        echo "
      <option value=\"".$langcode."\"".($temp_sourcelang == $langcode ? "selected=\"selected\"" : "").">".$langname."</option>";
      }
    }

    echo "
    </select>
    &#10095;
    <select id=\"targetLang\" name=\"temp_targetlang\" style=\"width:165px;\">";

    if (!empty ($activelanguage) && is_array ($activelanguage))
    {
      foreach ($activelanguage as $langcode => $langname)
      {
        echo "
      <option value=\"".$langcode."\"".($temp_targetlang == $langcode ? "selected=\"selected\"" : "").">".$langname."</option>";
      }
    }

    echo "
    </select>
    <img name=\"Button_translate\" onClick=\"translatelanguage('sourceLang', 'targetLang');\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" style=\"margin-right:2px;\" src=\"".getthemelocation()."img/button_ok.png\" onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('Button_translate','','".getthemelocation()."img/button_ok_over.png',1)\" align=\"absmiddle\" title=\"OK\" alt=\"OK\" />
  </div>
  <hr />";
  }
  ?>

  <!-- taxonomy -->
  <div style="width:94%; overflow:auto; padding-bottom:6px;">
    <table id="taxonomy" class="hcmsTableStandard">
      <thead>
        <tr style="height:28px;">
        <?php
        echo "
          <th class=\"hcmsHeadline hcmsRowHead1\" style=\"text-align:left; width:160px; height:20px; white-space:nowrap; box-sizing:border-box;\">&nbsp;".getescapedtext ($hcms_lang['level'][$lang])."</th>
          <th class=\"hcmsHeadline hcmsRowHead1\" style=\"text-align:right; width:38px; height:20px; white-space:nowrap; box-sizing:border-box;\">&nbsp;ID&nbsp;</th>
          <th class=\"hcmsHeadline hcmsRowHead1\" style=\"text-align:right; width:92px; height:20px; white-space:nowrap; box-sizing:border-box;\">&nbsp;&nbsp;</th>";

        if (!empty ($activelanguage) && is_array ($activelanguage))
        {
          foreach ($activelanguage as $langcode => $langname)
          {
            if (empty ($selectedlang[$langcode])) $style = "display:none;";
            else $style = "";
            
            echo "
            <th class=\"hcmsHeadline hcmsRowHead1 ".$langcode."\" style=\"width:140px; height:20px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; box-sizing:border-box; ".$style."\">
              <div style=\"margin-left:5px; float:left;\" title=\"".getescapedtext ($langname)."\">".showshorttext (getescapedtext ($langname), 16)."</div>
            </th>";
          }
        }
        ?>
        </tr>
      </thead>
      <tbody>
        <?php echo $show_taxonomy; ?>
      </tbody>
    </table>
  </div>
  
  <!-- paging -->
  <div class="hcmsHeadline" style="display:block; width:280px; margin:10px auto; text-align:center;">

    <?php if (($start - $perpage) >= 0) { ?>
    <img src="<?php echo getthemelocation(); ?>img/button_arrow_left.png" class="hcmsButton hcmsButtonSizeSquare" onclick="startpoint(<?php echo ($start - $perpage); ?>);" alt="<?php echo getescapedtext ($hcms_lang['back'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['back'][$lang]); ?>" />
    <?php } else { ?>
    <img src="<?php echo getthemelocation(); ?>img/button_arrow_left.png" class="hcmsButtonOff hcmsButtonSizeSquare" />
    <?php } ?>

    <?php
    if (!empty ($result['count']))
    {
      $pageselect = "<select onchange=\"startpoint(this.value);\">\n";

      $i = 1;

      while ($i <= $result['count'])
      {
        $pageselect .= "  <option value=\"".$i."\"".($i == $start ? " selected=\"selected\"" : "").">&nbsp;".$i." - ".($i + $perpage - 1)."&nbsp;</option>\n";
        $i = $i + $perpage;
      }

      $pageselect .= "</select>";
    }
    else $pageselect = $start." - ".($start +  sizeof ($result) - 1);
    ?>
    <div style="float:left; width:160px; padding:2px 10px;"><?php echo $pageselect; ?></div>

    <?php if (sizeof ($result) >= $perpage) { ?>
    <img src="<?php echo getthemelocation(); ?>img/button_arrow_right.png" class="hcmsButton hcmsButtonSizeSquare" onclick="startpoint(<?php echo ($start + $perpage); ?>);" alt="<?php echo getescapedtext ($hcms_lang['forward'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['forward'][$lang]); ?>"/>
    <?php } else { ?>
    <img src="<?php echo getthemelocation(); ?>img/button_arrow_right.png" class="hcmsButtonOff hcmsButtonSizeSquare" />
    <?php } ?>

  </div>
  <br/>

  <!-- save buttons -->
  <table class="hcmsTableStandard" style="margin-top:10px;">
    <tr>
      <td><?php echo getescapedtext ($hcms_lang['save-settings'][$lang]); ?> </td>
      <td><img name="Button1" type="button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="savetaxonomy()" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" /></td>
    </tr>
    <tr>
      <td><?php echo getescapedtext ($hcms_lang['apply-changes'][$lang]); ?> </div>
      <td><img name="Button2" type="button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="reindex()" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button2','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" /></td>
    </tr>
    <tr>
      <td><?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?> </div>
      <td><img name="Button3" type="button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="deletetaxonomy()" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button3','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" /></td>
    </tr>
    <tr>
      <td><?php echo getescapedtext ($hcms_lang['restore'][$lang]." (".$hcms_lang['standard'][$lang].")"); ?> </div>
      <td><img name="Button4" type="button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="restoretaxonomy()" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button4','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" /></td>
    </tr>
  </table>
  
</form>

</div>

<?php includefooter(); ?>

</body>
</html>