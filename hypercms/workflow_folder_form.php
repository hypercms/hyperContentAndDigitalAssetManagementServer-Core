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
require_once ("language/workflow_folder_form.inc.php");


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
if (!checkglobalpermission ($site, 'workflow') || !checkglobalpermission ($site, 'workflowproc') || !checkglobalpermission ($site, 'workflowprocfolder') || !valid_publicationname ($site)) killsession ($user);
// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";

// save file
if ($save == "yes" && valid_publicationname ($site) && in_array ($cat, array("page","comp")) && checktoken ($token, $user))
{
  $test = savefile ($mgmt_config['abs_path_data']."workflow_master/", $site.".".$cat.".folder.dat", trim ($result));
  
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
  var workflow = document.forms['workflow_area'].elements['workflow'].value;
  
  if (!object_id) object_id = sent_file;
  
  if (workflow != "")
  {
    if (document.forms['workflow_area'].elements['folder'].options.length > 0)
    {
      for (var i=0; i<document.forms['workflow_area'].elements['folder'].options.length; i++)
      {
        folder_file = document.forms['workflow_area'].elements['folder'].options[i].value;
        folder_file = folder_file.substring (folder_file.indexOf('|')+1, folder_file.length);
        folder_name = document.forms['workflow_area'].elements['folder'].options[i].text;
        folder_name = folder_name.substring (folder_name.indexOf(' : ')+1, folder_name.length);

        if (sent_file == folder_file)
        {
          message = message + "<?php echo $text1[$lang]; ?> " + "\n";
          insert = false;
        }
      }
    }
  
    if (insert == true)
    {
      newentry_value = new Option(workflow+': '+sent_name, workflow+'|'+object_id, false, true);
      newentry_pos = document.forms['workflow_area'].elements['folder'].length;
      document.forms['workflow_area'].elements['folder'].options[newentry_pos] = newentry_value;
      return true;
    }
    else
    {
      alert (hcms_entity_decode(message));
      return false;
    }
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
  else folderlist = "";

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

<form name="workflow_area" action="workflow_folder_form.php" method="post">
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="save" value="yes" />
  <input type="hidden" name="result" value="" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
    
  <table border="0" cellspacing="2" cellpadding="0">   
    <tr>
      <td  colspan="2" nowrap="nowrap"><?php echo $text7[$lang]; ?>:</td>
    </tr>
    <tr> 
      <td>
        <select name="workflow" style="width:350px;">
        <?php
        $wf_files = dir ($mgmt_config['abs_path_data']."workflow_master");
        $wf_names = array();
        
        while ($entry = $wf_files->read())
        {
          if ($entry != "" && substr ($entry, 0, strpos ($entry, ".")) == $site && strtolower (substr ($entry, strrpos ($entry, ".") + 1)) == "xml")
          {
            $start = strpos ($entry, ".") + 1;
            $end = strrpos ($entry, ".xml");
            $workflow = substr ($entry, $start, $end - $start);

            $wf_names[] = "<option value=\"".$workflow."\">".$workflow."</option>\n";
          }
        }
        
        natcasesort ($wf_names);
        reset ($wf_names);
        
        foreach ($wf_names as $option) echo $option;
        
        $wf_files->close();
        ?>
        </select><br /><br />
      </td>
    </tr>   
    <tr>
      <td  colspan="2" nowrap="nowrap"><?php echo $text4[$lang]; ?>:</td>          
    </tr>     
    <tr>
      <td colspan="2">
        <table border="0" width="100">
          <tr>
            <td>
              <select name="folder" style="width:350px;" size="10">
                <?php
                $wf_data = loadfile_fast ($mgmt_config['abs_path_data']."workflow_master/", $site.".".$cat.".folder.dat");
                
                if ($wf_data != false)
                {
                  $wf_array = explode ("\n", trim ($wf_data));                 
                
                  if (is_array ($wf_array) && sizeof ($wf_array) >= 1)
                  {
                    natcasesort ($wf_array);
                    reset ($wf_array);
                  
                    foreach ($wf_array as $wf_folder)
                    {
                      list ($workflow, $object_id) = explode ("|", $wf_folder);
                      
                      if ($workflow != "" && $object_id != "")
                      {
                        $object_id = trim ($object_id);
                        // versions before 5.6.3 used folder path instead of object id
                        if (substr_count ($object_id, "/") == 0) $folder_path = rdbms_getobject ($object_id);
                        else $folder_path = $object_id;

                        if ($folder_path != "")
                        {
                          $folder_name = getlocationname ($site, $folder_path, $cat, "path");
                          echo "<option value=\"".$workflow."|".$folder_path."\">".$workflow.": ".$folder_name."</option>\n";
                        }
                      }
                    }
                  }
                }
                else savefile ($mgmt_config['abs_path_data']."workflow_master/", $site.".".$cat.".folder.dat", "");
                ?>
              </select>
            </td>
            <td align="center" valign="middle">
              <img onClick="deleteSelected(document.forms['workflow_area'].elements['folder']);" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonDelete" src="<?php echo getthemelocation(); ?>img/button_delete.gif" title="<?php echo $text5[$lang]; ?>" alt="<?php echo $text5[$lang]; ?>" />
          </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td valign="top" colspan="2" nowrap="nowrap"><?php echo $text6[$lang]; ?>:
        <img name="Button" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="selectAll('workflow_area', 'folder', 'result');" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" title="OK" alt="OK" />
      </td>
    </tr>
  </table>
</form>

</div>
</body>
</html>

