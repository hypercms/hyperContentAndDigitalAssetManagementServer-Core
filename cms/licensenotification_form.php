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
// language file
require_once ("language/licensenotification_form.inc.php");


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
if ($globalpermission[$site]['template'] != 1 || $globalpermission[$site]['tpl'] != 1 || $globalpermission[$site]['tpledit'] != 1 || !valid_publicationname ($site)) killsession ($user);
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
    $show = "<p class=hcmsHeadline>".$text2[$lang]."</p>\n".$text3[$lang];
  }
  else $show = $text9[$lang];
}

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/jquery/jquery-1.9.1.min.js" type="text/javascript"></script>
<script language="JavaScript">
<!--
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
    alert (hcms_entity_decode("<?php echo $text8[$lang]; ?>"));
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
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric" onLoad="hcms_preloadImages('<?php echo getthemelocation(); ?>img/button_OK_over.gif')">

<?php
echo showmessage ($show, 500, 70, $lang, "position:absolute; left:20px; top:100px;");
?> 

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<p class=hcmsHeadline><?php echo $text0[$lang]; ?></p>

<form name="notification_area" action="licensenotification_form.php" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="save" value="yes" />
  <input type="hidden" name="result" value="" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
    
  <table border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td nowrap="nowrap"><?php echo $text14[$lang]; ?>:</td>
    </tr>
    <tr>
      <td nowrap="nowrap"><input type="text" name="text_id" value="" style="width:350px;" /></td>
    </tr> 
    <tr>
      <td nowrap="nowrap"><?php echo $text15[$lang]; ?>:</td>
    </tr>
    <tr>
      <td nowrap="nowrap"><input type="text" name="format" value="%Y-%m-%d" style="width:350px;" /></td>
    </tr> 
    <tr>
      <td nowrap="nowrap"><?php echo $text13[$lang]; ?>:</td>
    </tr>
    <tr>
      <td nowrap="nowrap"><input type="text" name="users" value="" style="width:350px;" /></td>
    </tr>    
    <tr>
      <td nowrap="nowrap"><?php echo $text7[$lang]; ?>:</td>
    </tr>
    <tr> 
      <td>
        <select name="period" style="width:350px;">
          <option value="monthly"><?php echo $text10[$lang]; ?></option>
          <option value="weekly"><?php echo $text11[$lang]; ?></option>
          <option value="daily"><?php echo $text12[$lang]; ?></option>
        </select>
      </td>
    </tr>   
    <tr>
      <td  colspan="2" nowrap="nowrap"><?php echo $text4[$lang]; ?>:</td>          
    </tr>     
    <tr>
      <td>
        <table border="0" width="100">
          <tr>
            <td>
              <select name="folder" style="width:350px;" size="10">
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
            <td align="center" valign="middle">
              <img onClick="deleteSelected(document.forms['notification_area'].elements['folder']);" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonDelete" src="<?php echo getthemelocation(); ?>img/button_delete.gif" title="<?php echo $text5[$lang]; ?>" alt="<?php echo $text5[$lang]; ?>" />
          </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td valign="top" nowrap="nowrap"><?php echo $text6[$lang]; ?>:
        <img name="Button" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="selectAll('notification_area', 'folder', 'result');" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" title="OK" alt="OK" />
      </td>
    </tr>
  </table>
</form>

</div>
</body>
</html>

