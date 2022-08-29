<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ($mgmt_config['abs_path_cms']."function/hypercms_api.inc.php");

// input parameters
$site = getrequest_esc ("site", "publicationname");
$action = getrequest ("action");
$importtype = getrequest_esc ("importtype");
$importfile = getrequest_esc ("importfile");
$delimiter = getrequest_esc ("delimiter", "objectname", "");
$enclosure = getrequest_esc ("enclosure", "objectname", ""); 
$deletefiles = getrequest ("deletefiles", "bool", 0);
$job = getrequest ("job", "bool", 0);
$period = getrequest ("period");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (empty ($mgmt_config[$site]['taxonomy']) || !checkglobalpermission ($site, 'tpl') || !checkglobalpermission ($site, 'tpledit')) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// initialize
$show = "";

// target CSV file
$file_csv = $mgmt_config['abs_path_data']."include/".$site.".taxonomy.csv";

// import profile file
$import_profile = $mgmt_config['abs_path_data']."config/".$site.".taxonomy.import.dat";

// execute actions
if (checktoken ($token, $user) && valid_publicationname ($site))
{
  // import CSV file
  if ($action == "import" && checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
  {
    // upload file
    if (!empty ($_FILES["importfile"]["tmp_name"]))
    {
      $save = move_uploaded_file ($_FILES["importfile"]["tmp_name"], $file_csv);
    }
    // get file contents
    elseif (!empty ($importfile))
    {
      $filedata = file_get_contents ($importfile);

      // load or get file
      if (!empty ($filedata)) $save = savefile ($mgmt_config['abs_path_data']."include/", $site.".taxonomy.csv", $filedata);

      // remove source file
      if (!empty ($deletefiles) && is_file ($importfile)) unlink ($importfile);
    } 

    if (!empty ($save) && is_file ($file_csv))
    {
      // load imported CSV file and try to detect enclosure and character set
      $import = load_csv ($file_csv, $delimiter , "", "", "utf-8");

      // the index starts with 1
      if (is_array ($import) && !empty ($import[1]['level']))
      {
        $save = create_csv ($import, $site.".taxonomy.csv", $mgmt_config['abs_path_data']."include/", ";", '"', "utf-8", "utf-8", false);
      }
      
      // remove uploaded file on error
      // if (empty ($save)) unlink ($file_csv);
    }
    
    if (!empty ($save)) $show = getescapedtext ($hcms_lang['the-data-was-saved-successfully'][$lang]);
    else $show = getescapedtext ($hcms_lang['the-data-could-not-be-saved'][$lang]);
  }
  
  // save import profile
  if ($action == "save" && checkglobalpermission ($site, 'tpl') && checkglobalpermission ($site, 'tpledit'))
  {
    // if import job name has been provided
    if (trim ($importtype) != "")
    {
      // define new record
      $import_values = array (trim ($site), intval ($job), trim ($period), trim ($importtype), trim ($importfile), trim ($delimiter), trim ($enclosure), trim ($deletefiles));
      $import_record = createlogentry ($import_values);
      
      // save data
      $savefile = savefile ($mgmt_config['abs_path_data']."config/", $site.".taxonomy.import.dat", $import_record);
    }
    
    if (empty ($savefile))
    {  
      $show = "<p class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['the-data-could-not-be-saved'][$lang])."</p>\n".getescapedtext ($hcms_lang['write-permission-is-missing'][$lang]);
    }
    else $show = getescapedtext ($hcms_lang['the-data-was-saved-successfully'][$lang]);
  }
}

// load import jobs
if (is_file ($import_profile))
{
  $record_array = file ($import_profile);

  if (is_array ($record_array))
  {      
    reset ($record_array);
    
    foreach ($record_array as $record)
    {
      if (trim ($record) != "")
      {
        list ($site, $job, $period, $importtype, $importfile, $delimiter, $enclosure, $deletefiles) = explode ("|", trim ($record));
        break;
      }
    }
  }
}

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="<?php echo cleandomain ($mgmt_config['url_path_cms']); ?>javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript">

function switchimport (type)
{
  if (type == "upload")
  {
    document.getElementById("importupload").checked = true;
    document.getElementById("importfile_upload").disabled = false;
    document.getElementById("importpath").checked = false;
    document.getElementById("importfile_path").disabled = true;
    document.getElementById("deletefiles").disabled = true;
    document.getElementById("job").disabled = true;
    document.getElementById("period").disabled = true;
  }
  else if (type == "path")
  {
    document.getElementById("importupload").checked = false;
    document.getElementById("importfile_upload").disabled = true;
    document.getElementById("importpath").checked = true;
    document.getElementById("importfile_path").disabled = false;
    document.getElementById("deletefiles").disabled = false;
    document.getElementById("job").disabled = false;
    document.getElementById("period").disabled = false;
  }
}

function switchjob ()
{
  var job = document.getElementById("job");

  if (job.disabled == false && job.checked == true)
  {
    document.getElementById("period").disabled = false;
  }
  else
  {
    document.getElementById("period").disabled = true;
  }
}

function startimport ()
{
  var form = document.forms['importform'];

  if (form && (document.getElementById('importfile_upload').value.trim() != "" || document.getElementById('importfile_path').value.trim() != ""))
  {
    form.action = "";
    form.target = "";
    form.elements['action'].value = "import";
    hcms_showFormLayer ('savelayer', 0);
    form.submit();
  }
  else
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['import'][$lang]." ".$hcms_lang['csv'][$lang]." ".$hcms_lang['file'][$lang]." ".$hcms_lang['is-required'][$lang]); ?>"));
  }
}

function saveimport ()
{
  var form = document.forms['importform'];

  form.action = "";
  form.target = "";
  form.elements['action'].value = "save";
  form.submit();
}

function initialize ()
{
  switchimport ('<?php if (!empty ($importtype)) echo $importtype; else echo "upload"; ?>');
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" onLoad="initialize(); hcms_preloadImages('<?php echo getthemelocation(); ?>img/button_ok_over.png')">

<!-- saving --> 
<div id="savelayer" class="hcmsLoadScreen"></div>

<?php
echo showmessage ($show, 500, 70, $lang, "position:fixed; left:10px; top:10px;");
?>

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">

<p class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['taxonomy'][$lang]." ".$hcms_lang['import-list-comma-delimited'][$lang]); ?>&nbsp;
<img src="<?php echo getthemelocation(); ?>img/button_info.png" class="hcmsIconList" style="cursor:pointer;" title="level;de;en;it
1;Typ;Type;Tipo
2;Abenteuer;Adventure;Avventura" />
</p>

<form name="importform" action="" method="post" enctype="multipart/form-data">
  <input type="hidden" name="action" value="" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  <input type="hidden" id="site" name="site" value="<?php if (!empty ($site)) echo $site; ?>" />
    
  <table class="hcmsTableStandard" style="width:100%;">
    <tr>
      <td style="white-space:nowrap;">
        CSV Delimiter <input type="text" name="delimiter" style="width:22px;" value="<?php if (!empty ($delimiter)) echo $delimiter; else echo ";"; ?>" />
      </td>          
    </tr> 
    <tr>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td style="white-space:nowrap;">
        <label><input type="checkbox" id="importupload" name="importtype" value="upload" onclick="switchimport('upload')" <?php if ($importtype == "upload") echo "checked=\"checked\""; ?> /> <?php echo getescapedtext ($hcms_lang['upload-file'][$lang]." (".$hcms_lang['csv'][$lang]." ".$hcms_lang['file'][$lang].")"); ?></label><br/>
        <input id="importfile_upload" name="importfile" type="file" style="width:350px;" accept="text/*" value="<?php if ($importtype == "upload" && !empty ($importfile)) echo $importfile; ?>" />
      </td>          
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>
        <label><input type="checkbox" id="importpath" name="importtype" value="path" onclick="switchimport('path')" <?php if ($importtype == "path") echo "checked=\"checked\""; ?> /> <?php echo getescapedtext ($hcms_lang['import'][$lang]." ".$hcms_lang['location'][$lang]." / URL (".$hcms_lang['csv'][$lang]." ".$hcms_lang['file'][$lang].")"); ?></label><br/>
        <input id="importfile_path" name="importfile" type="text" style="width:350px;" value="<?php if ($importtype == "path" && !empty ($importfile)) echo $importfile; ?>" />
      </td>
    </tr>
    <tr>
      <td style="white-space:nowrap; padding-left:22px;"><label><input type="checkbox" id="deletefiles" name="deletefiles" value="true" <?php if (!empty ($deletefiles)) echo "checked=\"checked\""; ?> /> <?php echo getescapedtext ($hcms_lang['delete-imported-files'][$lang]); ?></label></td>
    </tr>
    <tr>
      <td style="white-space:nowrap; padding-left:22px;">
        <label><input type="checkbox" id="job" name="job" value="true" onclick="switchjob();" <?php if (!empty ($job)) echo "checked=\"checked\""; ?> /> <?php echo getescapedtext ($hcms_lang['enable-job'][$lang]); ?></label>
        <select id="period" name="period" style="max-width:180px;">
          <option value="monthly" <?php if ($period == "monthly") echo "selected"; ?> ><?php echo getescapedtext ($hcms_lang['monthly'][$lang]); ?></option>
          <option value="weekly" <?php if ($period == "weekly") echo "selected"; ?> ><?php echo getescapedtext ($hcms_lang['weekly'][$lang]); ?></option>
          <option value="daily" <?php if ($period == "daily") echo "selected"; ?> ><?php echo getescapedtext ($hcms_lang['daily'][$lang]); ?></option>
        </select>
      </td>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>
        <table class="hcmsTableStandard">
          <tr>
            <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['import'][$lang]); ?> </td>
            <td style="white-space:nowrap;"><img name="Button1" type="button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="startimport()" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" /></td>
          </tr>
          <tr>
            <td style="white-space:nowrap;"><?php echo getescapedtext ($hcms_lang['save-settings'][$lang]); ?> </td>
            <td style="white-space:nowrap;"><img name="Button2" type="button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="saveimport()" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button2','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" /></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</form>

</div>

<?php includefooter(); ?>

</body>
</html>