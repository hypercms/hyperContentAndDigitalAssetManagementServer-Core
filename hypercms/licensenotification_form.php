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
$cat = getrequest_esc ("cat", "objectname");
$folder = getrequest ("folder", "objectname");
$save = getrequest ("save");
$result = getrequest ("result");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'template') || !checkglobalpermission ($site, 'tpl') || !checkglobalpermission ($site, 'tpledit') || !valid_publicationname ($site)) killsession ($user);
// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";

// save file
if ($save == "yes" && valid_publicationname ($site) && in_array ($cat, array("page","comp")) && checktoken ($token, $user))
{
  $test = savefile ($mgmt_config['abs_path_data']."config/", $site.".".$cat.".msg.dat", trim ($result));
  
  if ($test == false)   
  {  
    $show = "<p class=hcmsHeadline>".getescapedtext ($hcms_lang['notification-settings-could-not-be-saved'][$lang])."</p>\n".getescapedtext ($hcms_lang['write-permission-is-missing'][$lang]);
  }
  else $show = getescapedtext ($hcms_lang['notification-settings-were-saved-successfully'][$lang]);
}

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script type="text/javascript" src="javascript/main.min.js"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
<script type="text/javascript" src="javascript/jquery/jquery-3.5.1.min.js"></script>
<script type="text/javascript">
function replace (string, text, by) 
{
  // Replaces text with by in string
  var strLength = string.length, txtLength = text.length;
  if ((strLength == 0) || (txtLength == 0)) return string;

  var i = string.indexOf(text);
  if ((!i) && (text != string.substring(0,txtLength))) return string;
  if (i == -1) return string;

  var newstr = string.substring(0,i) + by;

  if (i+txtLength < strLength)
    newstr += replace(string.substring(i+txtLength,strLength),text,by);

  return newstr;
}

function getobject_id (location)
{
  if (location != "")
  {
    var object_id;
  
  	$.ajax({
  		async: false,
  		type: 'POST',
  		url: '<?php echo $mgmt_config['url_path_cms']; ?>/service/getobject_id.php',
  		data: {'location': location},
  		dataType: 'json',
  		success: function(data){ if(data.success) {object_id = data.object_id;} }
  	});
    
    if (object_id > 0) return object_id;
    else return false;
  }
  else return false;
}

function insertOption (sent_name, sent_file)
{
  var insert = true;
  var message = ""; 
  var object_id = getobject_id (sent_file);
  var period = document.forms['notification_area'].elements['period'].value;
  var users = document.forms['notification_area'].elements['users'].value;
  var text_id = document.forms['notification_area'].elements['text_id'].value;
  var format = document.forms['notification_area'].elements['format'].value;
  
  if (!object_id) object_id = sent_file;

  if (object_id != "" && period != "" && users != "" && text_id != "")
  {
    newentry_value = new Option(text_id + '@' + sent_name + '(' + period+'): ' + users, object_id + '|' + text_id + '|' + format + '|' + period + '|' + users, false, true);
    newentry_pos = document.forms['notification_area'].elements['folder'].length;
    document.forms['notification_area'].elements['folder'].options[newentry_pos] = newentry_value;
    return true;
  }
  else 
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['user-and-text-id-are-mandatory'][$lang]); ?>"));
    return false;
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

function selectAll (form_name, select_name, input_name)
{
  var folderlist = "";
  var form = document.forms[form_name];
  var select = form.elements[select_name];
  
  if (select.options.length > 0)
  {
    for (var i=0; i<select.options.length; i++)
    {
      if (select.options[i].value != "")
      {
        folderlist = folderlist + select.options[i].value + "\n";
      }  
    }
  }
  else
  {
    folderlist = "";
  }

  form.elements[input_name].value = folderlist;
  form.submit();
  return true;
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" onLoad="hcms_preloadImages('<?php echo getthemelocation(); ?>img/button_ok_over.png')">

<?php
echo showmessage ($show, 500, 70, $lang, "position:fixed; left:20px; top:100px;");
?> 

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<p class=hcmsHeadline><?php echo getescapedtext ($hcms_lang['apply-license-notification-on-selected-folder'][$lang]); ?></p>

<form name="notification_area" action="licensenotification_form.php" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="save" value="yes" />
  <input type="hidden" name="result" value="" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
    
  <table class="hcmsTableStandard">
    <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['text-id-expiration-date-to-monitor'][$lang]); ?> </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;"><input type="text" name="text_id" value="" style="width:360px;" /></td>
    </tr> 
    <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['date-format-eg'][$lang]); ?> </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;"><input type="text" name="format" value="%Y-%m-%d" style="width:360px;" /></td>
    </tr> 
    <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['notify-users'][$lang]." ".$hcms_lang['comma-seperated'][$lang]); ?> </td>
    </tr>
    <tr>
      <td style="white-space:nowrap;"><input type="text" name="users" value="" style="width:360px;" /></td>
    </tr>    
    <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['select-period'][$lang]); ?> </td>
    </tr>
    <tr> 
      <td>
        <select name="period" style="width:360px;">
          <option value="monthly"><?php echo getescapedtext ($hcms_lang['monthly'][$lang]); ?></option>
          <option value="weekly"><?php echo getescapedtext ($hcms_lang['weekly'][$lang]); ?></option>
          <option value="daily"><?php echo getescapedtext ($hcms_lang['daily'][$lang]); ?></option>
        </select>
      </td>
    </tr>   
    <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['applied-settings-on-selected-folders'][$lang]); ?> </td>          
    </tr>     
    <tr>
      <td>
        <table class="hcmsTableNarrow">
          <tr>
            <td>
              <select name="folder" style="width:360px;" size="10">
                <?php
                $config_data = loadfile_fast ($mgmt_config['abs_path_data']."config/", $site.".".$cat.".msg.dat");
                
                if ($config_data != false)
                {
                  $config_array = explode ("\n", trim ($config_data));                 
                
                  if (is_array ($config_array) && sizeof ($config_array) >= 1)
                  {
                    sort ($config_array);
                  
                    foreach ($config_array as $config_folder)
                    {
                      if (trim ($config_folder) != "")
                      {
                        list ($object_id, $text_id, $format, $period, $users) = explode ("|", $config_folder);
                        
                        // since version 5.6.3 object id is used instead of path
                        if (substr_count ($object_id, "/") == 0)
                        {
                          $folder_path = rdbms_getobject ($object_id);
                        }
                        // versions before 5.6.3 used folder path instead of object id
                        else
                        {
                          $folder_path = $object_id;
                          $object_id = rdbms_getobject_id ($folder_path);
                          if (!$object_id) $object_id = $folder_path;
                        }                      
                        
                        if ($period != "" && $users != "" && $folder_path != "")
                        {
                          $folder_path = trim ($folder_path);
                          $folder_name = getlocationname ($site, $folder_path, $cat, "path");
                        
                          echo "<option value=\"".$object_id."|".$text_id."|".$format."|".$period."|".$users."\">".$text_id."@".$folder_name."(".$period."): ".$users."</option>\n";
                        }
                      }
                    }
                  }
                }
                else savefile ($mgmt_config['abs_path_data']."config/", $site.".".$cat.".msg.dat", "");
                ?>
              </select>
            </td>
            <td style="text-align:center; vertical-align:middle; padding:2px;">
              <img onClick="deleteSelected(document.forms['notification_area'].elements['folder']);" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonDelete" src="<?php echo getthemelocation(); ?>img/button_delete.png" title="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" />
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['save-settings'][$lang]); ?> 
        <img name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="selectAll('notification_area', 'folder', 'result');" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" />
      </td>
    </tr>
  </table>
</form>

</div>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>

