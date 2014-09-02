<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("include/session.inc.php");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");
// template engine
require ("function/hypercms_tplengine.inc.php");
// language file
require_once ("language/buildview.inc.php");

// input parameters
$site = getrequest ("site", "publicationname");
$db_connect = getrequest ("db_connect");
$multiobject = getrequest ("multiobject");

// function to collect tag data
function gettagdata ($tag_array) 
{
  global $mgmt_config, $site;
  
  $return = array();

  foreach ($tag_array as $tagDefinition)
  {
    // get tag id
    $id = getattribute ($tagDefinition, "id");
    // get visibility on edit
    $onedit = getattribute (strtolower ($tagDefinition), "onedit");
    
    // We only use the first occurence of each element
    if (array_key_exists($id, $return) && isset($return[$id]->onedit))
    {
      // combine group access when needed
      $groups = getattribute ($tagDefinition, "groups");

      $return[$id]->groupaccess = trim ($return[$id]->groupaccess."|".$groups, "|"); 
      continue;
    }
    // We completely ignore values which are onEdit hidden
    elseif ($onedit == "hidden") continue;
    
    $return[$id] = new stdClass();
    $return[$id]->onedit = $onedit;
    
    // get tag name
    $hypertagname = gethypertagname ($tagDefinition);
    $return[$id]->hypertagname = $hypertagname;
    
    $return[$id]->type = substr ($hypertagname, strlen($hypertagname)-1);
        
    $label = getattribute ($tagDefinition, "label");
    
    if (substr ($return[$id]->hypertagname, 0, strlen ("arttext")) == "arttext")
    {
      $return[$id]->article = true;
      // get article id
      $artid = getartid ($id);

      // get element id
      $elementid = getelementid ($id);           

      // define label
      if ($label == "") $labelname = $artid." - ".$elementid;
      else $labelname = $artid." - ".$label;
      
      $return[$id]->labelname = $labelname;
    }
    else
    {
      $return[$id]->article = false;
      
      // define label
      if ($label == "") $labelname = $id;
      else $labelname = $label;
      
      $return[$id]->labelname = $labelname;
    }

    // get DPI
    $return[$id]->dpi = getattribute ($tagDefinition, "dpi");

    // get constraint
    $constraint = getattribute ($tagDefinition, "constraint");
    
    if ($constraint != "") $constraint = "'".$hypertagname."[".$id."]','".$labelname."','".$constraint."'";

    $return[$id]->constraint = $constraint;

    // extract text value of checkbox
    $return[$id]->value = getattribute ($tagDefinition, "value");  

    // get value of tag
    $return[$id]->defaultvalue = getattribute ($tagDefinition, "default");

    // get format (if date)
    $return[$id]->format = getattribute ($tagDefinition, "format");  

    // get toolbar
    if ($mgmt_config[$site]['dam'] == false) $toolbar = getattribute ($tagDefinition, "toolbar");
    else $toolbar = "DAM";

    if ($toolbar == false) $toolbar = "DefaultForm";
    
    $return[$id]->toolbar = $toolbar;
    
    // get height in pixel of text field
    $sizeheight = getattribute ($tagDefinition, "height");

    if ($sizeheight == false || $sizeheight <= 0) $sizeheight = "300";

    $return[$id]->height = $sizeheight;
    
    // get width in pixel of text field
    $sizewidth = getattribute ($tagDefinition, "width");

    if ($sizewidth == false || $sizewidth <= 0) $sizewidth = "600";

    $return[$id]->width = $sizewidth;
    
    // get language attribute
    $return[$id]->language_info = getattribute ($tagDefinition, "language");

    // get group access
    $return[$id]->groupaccess = getattribute ($tagDefinition, "groups"); 
    if ($return[$id]->groupaccess == "") $return[$id]->groupaccess = "";
    
    $return[$id]->list = getattribute ($tagDefinition, "list");
  }
  
  return $return;
}

// get multiple objects
$multiobject_array = explode ("|", $multiobject);

$template = "";
$templatedata = "";
$error = false;
$groups = array();
$site = "";
$count = 0;
$allTexts = array();

// run through each object
foreach ($multiobject_array as $object) 
{
  // ignore empty entries
  $object = trim ($object);
  if (empty ($object)) continue;
  
  $count++;
  $osite = getpublication ($object);
  $olocation = getlocation ($object);
  $ocat = getcategory ($osite, $object);
  $ofile = getobject ($object);
  
  if (empty ($site))
  {
    $site = $osite;
  }
  elseif ($site != $osite)
  {
    $error = $text110[$lang];
    break;
  }
  
  // load publication configuration
  if (valid_publicationname ($site) && empty ($mgmt_config[$site])) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
  
  $clocation = deconvertpath ($olocation, "file");
  
  // check access permissions
  $ownergroup = accesspermission ($site, $clocation, $ocat);
  $setlocalpermission = setlocalpermission ($site, $ownergroup, $ocat);
  
  // check localpermissions for DAM usage only
  if ($mgmt_config[$site]['dam'] == true && $setlocalpermission['root'] != 1)
  {
    killsession ($user);
    break;
  }
  // check for general root element access since localpermissions are checked later
  // Attention! variable page can be empty when a new object will be created
  elseif (
           !valid_publicationaccess ($site) || 
           (!valid_objectname ($ofile) && ($setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1)) || 
           !valid_publicationname ($site) || !valid_locationname ($olocation) || !valid_objectname ($ocat)
         ) 
  {
    killsession ($user);
    break;
  }

  $groups[] = $ownergroup;
  
  $oinfo = getobjectinfo ($osite, $olocation, $ofile);
  $content = loadcontainer ($oinfo['container_id'], "work", $user);
  
  if (empty ($template))
  {
    $template = $oinfo['template'];
  }
  elseif ($template != $oinfo['template'])
  {
    $error = $text109[$lang];
    break;
  }
  
  if (empty ($templatedata))
  {
    // load template
    $tcontent = loadtemplate ($osite, $template);
    $templatedata = $tcontent['content'];
    
    // try to get DB connectivity
    $db_connect = "";
    $dbconnect_array = gethypertag ($templatedata, "dbconnect", 0);
    
    if ($dbconnect_array != false)
    {
      foreach ($dbconnect_array as $hypertag)
      {
        $db_connect = getattribute ($hypertag, "file");
        
        if (!empty ($db_connect) && is_file ($mgmt_config['abs_path_data']."db_connect/".$db_connect)) 
        { 
          // include db_connect function
          @include_once ($mgmt_config['abs_path_data']."db_connect/".$db_connect);
          break;
        }
      }
    }
  }
  
  $texts = getcontent ($content, "<text>");
  
  // Means that there where no entries found so we make an empty array
  if (!is_array ($texts)) $texts = array();
  
  $newtext = array();
  
  foreach ($texts as $text)
  {
    $id = getcontent ($text, "<text_id>");
    
    // read content using db_connect
    $db_connect_data = false; 
    
    if (isset ($db_connect) && $db_connect != "") 
    {
      $db_connect_data = db_read_text ($site, $oinfo['container_id'], $content, $id, "", $user);
      
      if ($db_connect_data != false) 
      {
        $textcontent = $db_connect_data['text'];      
        // set true
        $db_connect_data = true;                    
      }
    }
    
    // read content from content container         
    if ($db_connect_data == false) $textcontent = getcontent ($text, "<textcontent>");
    
    // If we didn't find anything we stop
    if (!is_array ($id) || !is_array ($textcontent)) continue;
    
    $id = $id[0];
    $textcontent = trim ($textcontent[0]);
    
    // We ignore comments
    if (substr ($id, 0, strlen ('comment')) == "comment") continue;
    
    $newtext[$id] = $textcontent;
  }
  
  $allTexts[] = $newtext;
}

// fetch all texts
$text_array = gethypertag ($templatedata, "text", 0);
if (!is_array ($text_array)) $text_array = array();

// fetch all articles
$art_array = gethypertag ($templatedata, "arttext", 0);
if (!is_array ($art_array)) $art_array = array();

$all_array = array_merge ($text_array, $art_array);
$tagdata_array = gettagdata ($all_array);

// we don't want to use the default character set here, so we don't provide the site
$result = getcharset ($site, $templatedata);

if (is_array ($result)) $contenttype = $result['contenttype'];
else $contenttype = "text/html; charset=utf-8";

// loop through each tagdata array
foreach ($tagdata_array as $id => $tagdata) 
{
  // We kick the fields out if there should be groups checked and the user isn't allowed to view/edit it
  if ($tagdata->groupaccess) 
  {
    // If we don't have access through groups we will remove the field completely
    foreach ($groups as $group)
    {
      
      if (!checkgroupaccess ($tagdata->groupaccess, $group))
      {
        unset ($tagdata_array[$id]);
        continue 2;
      }
    }
  }
  
  foreach ($allTexts as $object) 
  {
    // If the current element isn't ignored we continue
    if (isset($tagdata->ignore) && $tagdata->ignore == true) continue;
    
    // Calculate the value we use
    $value = (array_key_exists($id, $object) ? $object[$id] : $tagdata->defaultvalue);
    
    if (!isset($tagdata->fieldvalue)) 
    {
      $tagdata->fieldvalue = $value;
      $tagdata->ignore = false;
    }
    else
    {
      if ($tagdata->fieldvalue != $value)
      {
        $tagdata->ignore = true;
        $tagdata->constraint = "";
      }
      else
      {
        $tagdata->ignore = false;
      }
    }
  }
}

// define form function call for unformated text constraints
$add_constraint = "";

foreach ($tagdata_array as $key => $tagdata)
{
  if (trim ($tagdata->constraint) != "")
  {
    if ($add_constraint == "") $add_constraint = $tagdata->constraint;
    else $add_constraint = $add_constraint.",".$tagdata->constraint;
  }
}

if ($add_constraint != "") $add_constraint = "checkcontent = validateForm(".$add_constraint.");";

// check session of user
checkusersession ($user);
$token = createtoken ($user);
?>
<!DOCTYPE html>
<html>
	<head>
		<title>hyperCMS</title>
    <meta http-equiv="Content-Type" content="<?php echo $contenttype; ?>" />
    <meta name="viewport" content="width=580; initial-scale=0.9; maximum-scale=1.0; user-scalable=1;" />
    <script src="<?php echo $mgmt_config['url_path_cms']; ?>javascript/jquery/jquery-1.9.1.min.js"></script>
    <script src="<?php echo $mgmt_config['url_path_cms']; ?>editor/ckeditor/ckeditor.js"></script>
    <script> CKEDITOR.disableAutoInline = true;</script>
    <link rel="stylesheet" href="<?php echo $mgmt_config['url_path_cms']; ?>javascript/rich_calendar/rich_calendar.css" />
    <script src="<?php echo $mgmt_config['url_path_cms']; ?>javascript/rich_calendar/rich_calendar.js"></script>
    <script src="<?php echo $mgmt_config['url_path_cms']; ?>javascript/rich_calendar/rc_lang_en.js"></script>
    <script src="<?php echo $mgmt_config['url_path_cms']; ?>javascript/rich_calendar/rc_lang_de.js"></script>
    <script src="<?php echo $mgmt_config['url_path_cms']; ?>javascript/rich_calendar/domready.js"></script>
		<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css"/>
    <style>
      .fieldrow > div.cke {
        display: inline-block !important;
      }
    </style>
		<script src="javascript/main.js"></script>
    <script>
    function save (reload)
    {
      var checkcontent = true;
        
      <?php echo $add_constraint; ?>
    
      if (checkcontent == true)
      {
        // update all CKEDitor instances
        for (var instanceName in CKEDITOR.instances)
          CKEDITOR.instances[instanceName].updateElement();
        
        var obj = $('#objs').val().split("|");
        var fields = $('#fields').val().split("|");
        
        var postdata = {
          'savetype' : 'auto',
          'db_connect': '<?php echo $db_connect; ?>',
          'contenttype': '<?php echo $contenttype; ?>',
          'token': '<?php echo $token; ?>'
        };
        
        for (var nr in fields)
        {
          var field = $('[id="'+fields[nr]+'"]');
          
          if (!field.prop) 
          {
            alert ('<?php echo $text113[$lang]; ?>');
          }
          
          var name = field.prop('name');
          var value = '';
          // for input we get the type
          if (field.prop('tagName').toUpperCase() == 'INPUT' && field.prop('type').toUpperCase() == 'CHECKBOX') 
            value = (field.prop('checked') ? field.prop('value') : '');
          // formatted fields doesn't need to be encoded as this is already done by CKEDitor
          // use direct value for selectboxes
          else if ( field.attr('id').slice(0, 'textf_'.length) == 'textf_' || field.prop('tagName').toUpperCase() == 'SELECT')
            value = field.prop('value');
          else if (field.prop('value') == "")
            value = "";
          else
            value = field.prop('value');
          
          postdata[name] = value;
        }
        
        // show savelayer across the whole page
        $('#savelayer').show();
        
        for (nr in obj) 
        {
          file = obj[nr];
          // we ignore empty values
          if($.trim(file) == "") continue;
          
          // for each selected files the following information must be changed
          // location
          // page = name of the object
          var len = file.lastIndexOf('/')+1;
          postdata['page'] = file.slice(len);
          postdata['location'] = file.slice(0, len);
          
          $.ajax({
            'type': "POST",
            'url': "<?php echo $mgmt_config['url_path_cms']; ?>page_save.php",
            'data': postdata,
            'async': false,
            'dataType': 'json'
          }).error(function(data) {
            // We need to refine this maybe
            if (data.message.length !== 0)
            {
              alert(hcms_entity_decode(data.message));
            }
            else
            {
              alert('An Internal Error happened');
            }
          }).success(function(data) {
            // We need to refine this maybe
            if (data.message.length !== 0)
            {
              alert(hcms_entity_decode(data.message));
            }
            
          });
        }
        
        if (reload == true) $('#reloadform').submit();
        return true;
      }
      else return false;
    }
      
    function saveClose()
    {
      var result = save(false);
      if (result == true) window.close();
    }
      
    function validateForm() 
    {
      var i,p,q,nm,test,num,min,max,errors='',args=validateForm.arguments;
      
      for (i=0; i<(args.length-2); i+=3) 
      { 
        test = args[i+2];
        contentname = args[i+1];
        val = hcms_findObj(args[i]);
        
        if (val) 
        { 
          if (contentname != '')
          {
            nm = contentname;
          }
          else
          {
            nm = val.name;
            nm = nm.substring(nm.indexOf('_')+1, nm.length);
          }
          
          if ((val=val.value) != '' && test != '') 
          {
            if (test == 'audio' || test == 'compressed' || test == 'flash' || test == 'image' || test == 'text' || test == 'video') 
            { 
              errors += checkMediaType(val, contentname, test);
            } 
            else if (test.indexOf('isEmail')!=-1) 
            { 
              p=val.indexOf('@');
              if (p<1 || p==(val.length-1)) errors += nm+' - <?php echo $text34[$lang]; ?>\n';
            } 
            else if (test!='R') 
            { 
              num = parseFloat(val);
              if (isNaN(val)) errors += nm+' - ".$text35[$lang].".\n';
              if (test.indexOf('inRange') != -1) 
              { 
                p=test.indexOf(':');
                if(test.substring(0,1) == 'R')
                {
                  min=test.substring(8,p); 
                } else {
                  min=test.substring(7,p); 
                }
                max=test.substring(p+1);
                if (num<min || max<num) errors += nm+' - <?php echo $text36[$lang]; ?> '+min+' - '+max+'.\n';
              } 
            } 
          } 
          else if (test.charAt(0) == 'R') errors += nm+' - <?php echo $text37[$lang]; ?>\n'; 
        }
      } 
      
      if (errors) 
      {
        alert (hcms_entity_decode ('<?php echo $text38[$lang]; ?>:\n'+errors));
        return false;
      }  
      else return true;
    }
    </script>
  </head>
  
  <body class="hcmsWorkplaceFrame hcmsWorkplaceGeneric" style="overflow:auto">
  
    <!-- Save Layer --> 
    <div id="savelayer" class="hcmsWorkplaceGeneric" style="position:fixed; width:100%; height: 100%; margin:0; padding:0; left:0px; top:0px; display: none; z-index:100;">
      <span style="position:absolute; top:50%; height:150px; margin-top:-75px; width:200px; left:50%; margin-left:-100px;">
        <b><?php echo $text114[$lang];?></b>
        <br />
        <br />
        <img src="<?php echo getthemelocation(); ?>img/loading.gif" />
      </span>
    </div>
    
    <!-- top bar -->
    <div id="bar" class="hcmsWorkplaceBar">
      <table style="width:100%; height:100%; padding:0; border-spacing:0; border-collapse:collapse;">
        <tr>
          <td class="hcmsHeadline" style="text-align:left; vertical-align:middle; padding:0px 1px 0px 2px">
            <img name="Button_so" src="<?php echo getthemelocation(); ?>img/button_save.gif" class="hcmsButton hcmsButtonSizeSquare" onClick="save(true);" alt="<?php echo $text31[$lang]; ?>" title="<?php echo $text31[$lang] ?>" align="absmiddle" />
            <img name="Button_sc" src="<?php echo getthemelocation()?>img/button_saveclose.gif" class="hcmsButton" onClick="saveClose()" alt="<?php echo $text32[$lang]; ?>" title="<?php echo $text32[$lang]; ?>" align="absmiddle" />
          </td>
          <td style="width:26px; text-align:right; vertical-align:middle;">
            &nbsp;
          </td>
        </tr>
      </table>
    </div>
    
    <div style="width:100%; height:32px;">&nbsp;</div>
    <?php
    if ($error !== false)
    {
      echo showmessage ($error);
    }
    else
    {
    ?>
    <form id="reloadform" style="display:none" method="POST" action="<?php echo $mgmt_config['url_path_cms']; ?>page_multiedit.php">
      <?php
      foreach ($_POST as $pkey => $pvalue)
      {
        ?>
        <input type="hidden" name="<?php echo $pkey; ?>" value="<?php echo $pvalue; ?>" />
        <?php
      }
      ?>
    </form>
    <form id="sendform">
      <div>
        <span class="hcmsHeadlineTiny">
          <?php echo $text112[$lang];?>
        </span>
        <?php
        $ids = array();
        
        foreach ($tagdata_array as $key => $tagdata)
        {
          $disabled = ($tagdata->ignore == true ? ' DISABLED="DISABLED" READONLY="READONLY"' : "");
          $id = $tagdata->hypertagname.'_'.$key;
          $label = $tagdata->labelname;
          
          if ($tagdata->ignore == false) $ids[] = $id;
          ?>
          <div style="margin-top: 10px;" class="fieldrow">
            <label for="<?php echo $id ?>" style="display:inline-block; width:130px; vertical-align:top;"><b><?php if (trim ($label) != "") { echo $label.":"; if ($tagdata->ignore == false) echo " *"; } ?></b></label>
          <?php 
          if ($tagdata->type == "u") 
          {
          ?>
            <textarea id="<?php echo $id ?>" name="<?php echo $tagdata->hypertagname; ?>[<?php echo $key; ?>]" style="width: <?php echo $tagdata->width; ?>px; height: <?php echo $tagdata->height; ?>px;"<?php echo $disabled; ?>><?php if ($tagdata->ignore == false) echo $tagdata->fieldvalue; ?></textarea>
          <?php 
          } 
          elseif ($tagdata->type == "f")
          {
           if ($tagdata->ignore == false)
            echo showeditor ($site, $tagdata->hypertagname, $key, $tagdata->fieldvalue, $tagdata->width, $tagdata->height, $tagdata->toolbar, $lang, $tagdata->dpi);
          }
          elseif ($tagdata->type == "d")
          {
            $onclick = "show_cal_{$id}(this);";
            ?>
            <input type="text" id="<?php echo $id ?>" name="<?php echo $tagdata->hypertagname; ?>[<?php echo $key; ?>]" value="<?php if ($tagdata->ignore == false) echo $tagdata->fieldvalue; ?>"<?php echo $disabled; ?>/>
            <?php
            if ($tagdata->ignore == false) 
            {
            ?>
            <img name="datepicker" src="<?php echo getthemelocation(); ?>img/button_datepicker.gif" onclick="<?php echo $onclick; ?>" align="absmiddle" style="width:22px; height:22px; border:0; cursor:pointer;" alt="<?php echo $text97[$lang]; ?>" title="<?php echo $text97[$lang]; ?>"/>
            <script type="text/javascript">
            <!--
            var cal_obj_<?php echo $id; ?> = null;
            var format_<?php echo $id; ?> = '<?php echo $tagdata->format; ?>';

            function show_cal_<?php echo $id; ?> (el)
            {
              if (cal_obj_<?php echo $id; ?>) return;
              var datefield_<?php echo $id; ?> = document.getElementById('<?php echo $id; ?>');  

              cal_obj_<?php echo $id; ?> = new RichCalendar();
              cal_obj_<?php echo $id; ?>.start_week_day = 1;
              cal_obj_<?php echo $id; ?>.show_time = false;
              cal_obj_<?php echo $id; ?>.language = '<?php echo $lang; ?>';
              cal_obj_<?php echo $id; ?>.user_onchange_handler = cal_on_change_<?php echo $id; ?>;
              cal_obj_<?php echo $id; ?>.user_onautoclose_handler = cal_on_autoclose_<?php echo $id; ?>;
              cal_obj_<?php echo $id; ?>.parse_date(datefield_<?php echo $id; ?>.value, format_<?php echo $id; ?>);
              cal_obj_<?php echo $id; ?>.show_at_element(datefield_<?php echo $id; ?>, 'adj_left-bottom');
            }

            // onchange handler
            function cal_on_change_<?php echo $id; ?> (cal, object_code)
            {
              if (object_code == 'day')
              {
                document.getElementById('<?php echo $id ?>').value = cal.get_formatted_date(format_<?php echo $id; ?>);
                cal.hide();
                cal_obj_<?php echo $id; ?> = null;
              }
            }

            // onautoclose handler
            function cal_on_autoclose_<?php echo $id; ?> (cal)
            {
              cal_obj_<?php echo $id; ?> = null;
            }
            -->
            </script>
          <?php
            }
          } 
          elseif ($tagdata->type == "l")
          {
            // get list entries
            $list_array = explode ("|", $tagdata->list);
            ?>
            <select name="<?php echo $tagdata->hypertagname."[".$key."]"; ?>" id="<?php echo $id ?>"<?php echo $disabled; ?>>
            <?php
            foreach ($list_array as $list_entry)
            {
              $end_val = strlen($list_entry)-1;
              
              if (($start_val = strpos($list_entry, "{")) > 0 && strpos($list_entry, "}") == $end_val)
              {
                $diff_val = $end_val-$start_val-1;
                $list_value = substr($list_entry, $start_val+1, $diff_val);
                $list_text = substr($list_entry, 0, $start_val);
              } 
              else $list_value = $list_text = $list_entry;
              ?>
              <option value="<?php echo $list_value; ?>"
              <?php if ($tagdata->ignore == false && $list_value == $tagdata->fieldvalue) echo " selected";?>>
              <?php echo $list_text; ?>
              </option>
              <?php
            }

            ?>
            </select>
          <?php
          } 
          elseif ($tagdata->type == "c")
          {
            ?>
            <input type="checkbox" name="<?php echo $tagdata->hypertagname."[".$key."]"; ?>" id="<?php echo $id ?>" value="<?php echo $tagdata->value; ?>"<?php if( $tagdata->ignore == false && $tagdata->value == $tagdata->fieldvalue) echo " checked"; echo $disabled; ?>><?php echo $tagdata->value; ?>
            <?php
          }
          else
          {
            echo "UNKNOWN TYPE: ".var_export($tagdata->type, true)." for ".var_export($tagdata->hypertagname, true)."<br>\n";
          }
          ?>
        </div>
      <?php
      }
      ?>
      </div>
      <input type="hidden" id="objs" value="<?php echo $multiobject ?>" />
      <input type="hidden" id="fields" value="<?php echo implode('|', $ids) ?>" />
    </form>
    <?php
    } 
    ?>
  </body>
</html>