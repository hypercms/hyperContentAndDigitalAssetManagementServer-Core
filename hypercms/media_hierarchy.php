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
$selectname = getrequest_esc ("selectname", "objectname");
$deletename = getrequest_esc ("deletename", "objectname");
$name = getrequest_esc ("name", "objectname");
$hierarchy_array = getrequest ("hierarchy", "array");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'template') || !checkglobalpermission ($site, 'tpl') || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";

// create secure token
$token_new = createtoken ($user);

if (!empty ($mgmt_config['abs_path_data']))
{
  // save hierarchy file
  if (valid_publicationname ($site) && checktoken ($token, $user) && (trim ($name) != "" || trim ($deletename) != ""))
  {
    // if hierarchy name has been provided
    if (trim ($name) != "")
    {
      // set selected name
      $selectname = $name;

      // define new record
      $record_new = "";
    
      // creating mapping from definition
      if (is_array ($hierarchy_array) && sizeof ($hierarchy_array) > 0)
      {
        foreach ($hierarchy_array as $key => $hierarchy)
        {
          if (!empty ($hierarchy['level']) && !empty ($hierarchy['text_id']))
          {
            if ($record_new != "") $record_new .= "|";
            
            $record_new .= trim ($hierarchy['level'])."->".trim ($hierarchy['text_id'])."->".trim ($hierarchy['label']);
          }
        }
        
        $record_new = trim ($name)."|".$record_new;
      }
    }

    // load hierarchy
    $data = "";

    if (is_file ($mgmt_config['abs_path_data']."config/".$site.".hierarchy.dat"))
    {
      $record_array = file ($mgmt_config['abs_path_data']."config/".$site.".hierarchy.dat");
      
      if (is_array ($record_array) && sizeof ($record_array) > 0)
      {
        foreach ($record_array as $record)
        {
          list ($hierarchyname, $rest) = explode ("|", trim ($record));

          // add new record
          if (!empty ($record_new) && $name == $hierarchyname)
          {
            $data .= trim ($record_new)."\n";
            $added = true;
          }
          // delete record
          elseif (!empty ($deletename) && $deletename == $hierarchyname)
          {
            $data .= "";
          }
          // add without changes
          elseif (!empty ($record))
          {
            $data .= trim ($record)."\n";
          }
        }
      }
      
      // no data available or hierarchy name does not exist
      if (!empty ($record_new) && ($data == "" || empty ($added))) $data .= trim ($record_new)."\n";
    }
    // no hierarchy file available
    else $data = trim ($record_new)."\n";

    // save data
    $savefile = savefile ($mgmt_config['abs_path_data']."config/", $site.".hierarchy.dat", $data);
    
    if ($savefile == false)   
    {  
      $show = "<p class=hcmsHeadline>".getescapedtext ($hcms_lang['the-data-could-not-be-saved'][$lang])."</p>\n".getescapedtext ($hcms_lang['you-do-not-have-write-permissions'][$lang]);
    }
    else $show = getescapedtext ($hcms_lang['the-data-was-saved-successfully'][$lang]);
  }
  
  // load hierarchy
  $hierarchy = gethierarchy_definition ($site);

  // get text IDs from templates
  $attributes_all = array();
  $templates = gettemplates ($site, "all");
  
  if (is_array ($templates) && sizeof ($templates) > 0)
  {
    foreach ($templates as $template)
    {
      $templatedata = loadtemplate ($site, $template);
      
      if (!empty ($templatedata['content']))
      {
        $hypertag_array = gethypertag ($templatedata['content'], "text");
        
        if (is_array ($hypertag_array) && sizeof ($hypertag_array) > 0)
        {
          foreach ($hypertag_array as $hypertag)
          {
            $text_id = getattribute ($hypertag, "id");
            $type = gethypertagname ($hypertag);
            
            // remove article prefix
            if (substr ($type, 0, 3) == "art") $type = substr ($type, 3);

            $attributes_all[$type.":".$text_id] = $text_id;
          }
        }
      }
    }
    
    $attributes_all = array_unique ($attributes_all);
    natcasesort ($attributes_all);
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
function checkForm_chars(text, exclude_chars)
{
  exclude_chars = exclude_chars.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
  
  var expr = new RegExp ("[^a-zA-Z0-9" + exclude_chars + "]", "g");
  var separator = ', ';
  var found = text.match(expr); 
  
  if (found)
  {
    var addText = '';
    
    for(var i = 0; i < found.length; i++)
    {
      addText += found[i]+separator;
    }
    
    addText = addText.substr(0, addText.length-separator.length);
    alert("<?php echo getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters'][$lang]); ?>\n " + addText);
    return false;
  }
  else
  {
    return true;
  }
}

function savehierarchy ()
{
  var form = document.forms['hierarchyform'];
  
  if (form.elements['name'].value.trim() == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['a-name-is-required'][$lang]); ?>"));
    form.elements['name'].focus();
    return false;
  }
  
  if (!checkForm_chars(form.elements['name'].value, "-_"))
  {
    form.elements['name'].focus();
    return false;
  }  
  
  form.submit();
}

function deletehierarchy ()
{
  var form = document.forms['hierarchyform'];
  
  check = confirm ("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-remove-the-item'][$lang]); ?>");

  if (check == true)
  {
    document.location='?site=<?php echo url_encode ($site); ?>&deletename=' + form.elements['hierarchyname'].options[form.elements['hierarchyname'].selectedIndex].value + '&token=<?php echo $token_new; ?>';
  }
}

function setlevel (e)
{
 if (e)
 {
   e.style.marginLeft = ((e.value - 1) * 20) + "px";
 }
}
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<p class=hcmsHeadline><?php echo getescapedtext ($hcms_lang['meta-data-hierarchy'][$lang]); ?></p>

<?php
echo showmessage ($show, 600, 70, $lang, "position:fixed; left:5px; top:50px;");
?>

<form name="hierarchyform" action="" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table class="hcmsTableStandard" style="width:100%;">
    <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['select-hierarchy'][$lang]); ?> </td>          
    </tr>    
    <tr>
      <td>
        <select name="hierarchyname" onchange="document.location='?site=<?php echo url_encode ($site); ?>&selectname=' + this.options[this.selectedIndex].value" style="width:350px;">
          <option value="">&nbsp;</option>
          <?php
          if (!empty ($hierarchy) && is_array ($hierarchy))
          {
            foreach ($hierarchy as $hierarchy_name => $array)
            {
              echo "
            <option value=\"".$hierarchy_name."\" ".($selectname == $hierarchy_name ? "selected=\"selected\"" : "").">".$hierarchy_name."</option>";
            }
          }
          ?>
        </select>
        <img onClick="deletehierarchy()" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonDelete" src="<?php echo getthemelocation(); ?>img/button_delete.png" title="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" />
        <hr />
      </td>
    </tr>
      <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['name'][$lang]); ?> </td>          
    </tr>     
    <tr>
      <td>
        <input type="text" name="name" style="width:350px;" value="<?php if (!empty ($selectname)) echo $selectname; ?>" />
      </td>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>
        <table class="hcmsTableStandard">
          <tr>
            <td class="hcmsHeadline" style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['level'][$lang]); ?></td>
            <td class="hcmsHeadline" style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['text-id'][$lang]); ?></td>
            <td class="hcmsHeadline" style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['label'][$lang]); ?> (en:Title; de:Titel; fr:Titre)</td>
          </tr>
          <?php
          if (is_array ($hierarchy) && sizeof ($hierarchy) > 0)
          {
            $i = 1;
            $record_array = array();
            
            foreach ($hierarchy as $name => $level_array)
            {
              if ($name == $selectname)
              {
                ksort ($level_array);
                
                foreach ($level_array as $level => $text_array)
                {
                  foreach ($text_array as $text_id => $label_array)
                  {
                    $labels = "";
                    
                    foreach ($label_array as $langcode => $label)
                    {
                      if ($langcode != "default")
                      {
                        if ($labels != "") $labels .= "; ";
                        $labels .= $langcode.":".$label;
                      }
                      else $labels = $label;
                    }
                    
                    $record_array[$i]['level'] = $level;
                    $record_array[$i]['text_id'] = $text_id;
                    $record_array[$i]['label'] = $labels;
                    
                    $i++;
                  }
                }
              }
            }
          }
          
          
          for ($i=1; $i<=10; $i++)
          {
            if ($i % 2 == 0) $rowcolor = "hcmsRowData2";
            else $rowcolor = "hcmsRowData1";
          ?>
          <tr>
            <td style="width:160px; text-align:left; white-space:nowrap;">
              <select name="hierarchy[<?php echo $i; ?>][level]" class="<?php echo $rowcolor; ?>" onchange="setlevel(this)" style="<?php if (!empty ($record_array[$i]['level'])) echo "margin-left:".(($record_array[$i]['level'] - 1) * 20)."px"; elseif ($i != 1) echo "margin-left:20px;"; ?>">
                <?php
                if ($i == 1)
                {
                  echo "
                  <option selected=\"selected\">1</option>";
                }
                else
                {
                  for ($l=2; $l<=5; $l++)
                  {
                    echo "
                  <option ".(@$record_array[$i]['level'] == $l ? "selected=\"selected\"" : "").">".$l."</option>";
                  }
                }
                ?>
              </select><img src="<?php echo getthemelocation(); ?>img/button_arrow_right.png" class="hcmsButtonSizeSquare" />
            </td>
            <td>
              <select name="hierarchy[<?php echo $i; ?>][text_id]" style="width:160px;" class="<?php echo $rowcolor; ?>">
                <option value="">&nbsp;</option>
              <?php
              if (!empty ($attributes_all) && is_array ($attributes_all))
              {
                foreach ($attributes_all as $text_id => $value)
                {
                  // remember selected text ID for label
                  if (!empty ($hierarchy[$selectname][$i][$text_id])) $text_id_selected = $text_id;
                
                  echo "
                <option value=\"".$text_id."\" ".(@$record_array[$i]['text_id'] == $text_id ? "selected" :  "").">".$value."</option>\n";
                }
              }
              ?>
              </select>
            </td>
            <td>
              <input type="text" name="hierarchy[<?php echo $i; ?>][label]" value="<?php echo @$record_array[$i]['label']; ?>" style="width:360px;" class="<?php echo $rowcolor; ?>"/>
            </td>
          </tr>
          <?php
          }
          ?>
        </table>
      </td>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>
        <?php echo getescapedtext ($hcms_lang['save-settings'][$lang]); ?> 
        <img name="Button" type="button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="savehierarchy()" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" />
      </td>
    </tr>
  </table>  
</form>

</div>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>
