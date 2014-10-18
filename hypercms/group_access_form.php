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
require_once ("language/group_access_form.inc.php");


// input parameters
$site = getrequest_esc ("site", "publicationname");
$cat = getrequest_esc ("cat", "objectname");
$dir = getrequest_esc ("dir", "locationname");
$group_name = getrequest_esc ("group_name", "objectname");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'group') || (!checkglobalpermission ($site, 'groupcreate') && !checkglobalpermission ($site, 'groupedit')) || !valid_publicationname ($site))  killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// define variables depending on content category
if ($cat == "page")
{
  $access_tag = "<pageaccess>";
  $pagecomp = $text7[$lang];
}
elseif ($cat == "comp")
{
  $access_tag = "<compaccess>";
  $pagecomp = $text8[$lang];
}

// check if login is an attribute of a sent string
if (@strpos ($group_name, ".php") > 0)
{
  // extract login
  $group_name = getattribute ($group_name, "group_name");
}

if ($group_name != false && $group_name != "")
{
  // load user file
  $groupdata = loadfile ($mgmt_config['abs_path_data']."user/", $site.".usergroup.xml.php");

  if ($groupdata != false)
  {
    $grouprecord = selectcontent ($groupdata, "<usergroup>", "<groupname>", $group_name);
    $temp_access = getcontent ($grouprecord[0], $access_tag);
    $dirstring = $temp_access[0];

    // split the pageaccess string and get each pageaccess value into an array
    if ($dirstring != "")
    {
      // cut off last |
      $dirstringshort = substr ($dirstring, 0, strlen ($dirstring)-1);

      // split folder string
      $folderaccesslist = explode ("|", $dirstringshort);

      sort ($folderaccesslist);
      reset ($folderaccesslist);
    }
  }
}
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
  
  if (!object_id) object_id = sent_file;

  if (document.forms['group_access'].elements['folder'].options.length > 0)
  {
    for (var i = 0; i < document.forms['group_access'].elements['folder'].options.length; i++)
    {
      folder_file = document.forms['group_access'].elements['folder'].options[i].value
      folder_name = document.forms['group_access'].elements['folder'].options[i].text

      if (sent_file == folder_file)
      {
        message = message + "<?php echo $text1[$lang]; ?> " + "\r";
        insert = false;
      }
      else if (sent_file.indexOf (folder_file) != -1)
      {
        message = message + "<?php echo $text2[$lang]; ?> " + folder_name + "\r";
        insert = false;
      }
      else if (folder_file.indexOf (sent_file) != -1)
      {
        message = message + "<?php echo $text3[$lang]; ?> " + folder_name + "\r";
        insert = false;
      }
    }
  }

  if (insert == true)
  {
    newentry_value = new Option(sent_name, object_id, false, true);
    newentry_pos = document.forms['group_access'].elements['folder'].length;
    document.forms['group_access'].elements['folder'].options[newentry_pos] = newentry_value;

    return true;
  }
  else
  {
    alert (hcms_entity_decode(message));

    return false;
  }
}

function deleteSelected ()
{
  var select = document.forms['group_access'].elements['folder'];
  
  if (select.length > 0)
  {
    for (var i=0; i<select.length; i++)
    {
      if (select.options[i].selected == true) select.remove(i);
    }
  }
}

function selectAll ()
{
  var folderlist = "";
  var form = document.forms['group_access'];
  var select = form.elements['folder'];

  if (select.options.length > 0)
  {
    for (var i=0; i<select.options.length; i++)
    {
      if (select.options[i].value != "")
      {
        folderlist = folderlist + select.options[i].value + "|" ;
      }  
    }
  }
  else
  {
    folderlist = "";
  }

  form.elements['access_new'].value = folderlist;
  form.submit();
  return true;
}
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric" onLoad="hcms_preloadImages('<?php echo getthemelocation(); ?>img/button_OK_over.gif');">

<!-- top bar -->
<?php echo showtopbar ($pagecomp." ".$text0[$lang].": ".$group_name, $lang, $mgmt_config['url_path_cms']."group_edit_form.php?site=".url_encode($site)."&group_name=".url_encode($group_name)."&preview=no", "_parent"); ?>

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
  <form name="group_access" action="group_edit_script.php" method="post">
    <input type="hidden" name="sender" value="access" />
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="group_name" value="<?php echo $group_name; ?>" />    
    <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
    <input type="hidden" name="access_new" value="" />
    <input type="hidden" name="token" value="<?php echo createtoken ($user); ?>" />
      
    <table border="0" cellspacing="2" cellpadding="0">
      <tr>
        <td colspan="2" nowrap="nowrap">
          <?php echo $text4[$lang]; ?>:
        </td>
      </tr>
      <tr>
        <td colspan="2">
          <table border="0">
            <tr>
              <td>
                &nbsp;&nbsp;&nbsp;
                <select name="folder" size="10">
                  <?php
                  if (isset ($folderaccesslist) && is_array ($folderaccesslist) && sizeof ($folderaccesslist) > 0)
                  {
                    foreach ($folderaccesslist as $object_id)
                    {
                      $object_id = trim ($object_id);
                      // versions before 5.6.3 used folder path instead of object id
                      if (substr_count ($object_id, "/") == 0) $folder_path = rdbms_getobject ($object_id);
                      else $folder_path = $object_id;
                      
                      if ($folder_path != "")
                      {
                        // get location name
                        $folder_name = getlocationname ($site, $folder_path, $cat, "path");
                        
                        echo "<option value=\"".$object_id."\">".$folder_name."</option>\n";
                      }
                    }
                  }
                  ?>
                </select>
              </td>
              <td align="center" valign="middle">
                <img onClick="deleteSelected();" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonDelete" src="<?php echo getthemelocation(); ?>img/button_delete.gif" alt="<?php $text5[$lang]; ?>" />
            </td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td valign="top" nowrap="nowrap">
          <?php echo $text6[$lang]; ?>:
        </td>
        <td>
          <img name="Button" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="selectAll();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" title="OK" alt="OK" />
        </td>
      </tr>
    </table>
  </form>
</div>

</body>
</html>

