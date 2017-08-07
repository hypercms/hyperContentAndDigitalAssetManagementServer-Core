<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");


// input parameters
$view = getrequest_esc ("view");
$contenttype = getrequest_esc ("contenttype");
$site = getrequest_esc ("site", "publicationname");
$cat = getrequest_esc ("cat", "objectname");
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$db_connect = getrequest_esc ("db_connect", "objectname");
$tagname = getrequest_esc ("tagname", "objectname");
$artid = getrequest_esc ("artid", "objectname");
$label = getrequest_esc ("label");
$arttitle = getrequest_esc ("arttitle");
$artstatus = getrequest_esc ("artstatus");
$artdatefrom = getrequest_esc ("artdatefrom");
$artdateto = getrequest_esc ("artdateto");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($cat)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// load object file and get container
$objectdata = loadfile ($location, $page);
$contentfile = getfilename ($objectdata, "content");

// get file info
$file_info = getfileinfo ($site, $location.$page, $cat);

// define content-type if not set
if ($contenttype == "") 
{
  $contenttype = "text/html; charset=".$mgmt_config[$site]['default_codepage'];
  $charset = $mgmt_config[$site]['default_codepage'];
}
else
{
  // get character set 
  $charset_array = getcharset ($site, $contenttype);
  
  // set character set if not set
  if (!empty ($charset_array['charset'])) $charset = $charset_array['charset'];
  else $charset = $mgmt_config[$site]['default_codepage'];
}

// create secure token
$token_new = createtoken ($user);

if ($label == "") $label = $artid;
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo $charset; ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>

<link rel="stylesheet" type="text/css" href="javascript/rich_calendar/rich_calendar.css" />
<script type="text/javascript" src="javascript/rich_calendar/rich_calendar.js"></script>
<script type="text/javascript" src="javascript/rich_calendar/rc_lang_en.js"></script>
<script type="text/javascript" src="javascript/rich_calendar/rc_lang_de.js"></script>
<script type="text/javascript" src="javascript/rich_calendar/domready.js"></script>
<script type="text/javascript">
var cal_obj = null;
var cal_format = '%Y-%m-%d %H:%i';
var cal_field = null;

// show calendar
function show_cal (el, field_id, format)
{
  if (cal_obj) return;
  
  cal_field = field_id;
  cal_format = format;
  var datefield = document.getElementById(field_id);

	cal_obj = new RichCalendar();
	cal_obj.start_week_day = 1;
	cal_obj.show_time = true;
	cal_obj.language = '<?php echo getcalendarlang ($lang); ?>';
  cal_obj.user_onchange_handler = cal_on_change;
  cal_obj.user_onclose_handler = cal_on_close;
  cal_obj.user_onautoclose_handler = cal_on_autoclose;
  cal_obj.parse_date(datefield.value, cal_format);
	cal_obj.show_at_element(datefield, "adj_left-bottom");
}

// user defined onchange handler
function cal_on_change(cal, object_code)
{
	if (object_code == 'day')
	{
		document.getElementById(cal_field).value = cal.get_formatted_date(cal_format);
		cal.hide();
		cal_obj = null;
	}
}

// user defined onclose handler (used in pop-up mode - when auto_close is true)
function cal_on_close(cal)
{
	cal.hide();
	cal_obj = null;
}

// user defined onautoclose handler
function cal_on_autoclose(cal)
{
	cal_obj = null;
}

function validateForm(select, min, max) 
{
  var errors = '';
  
  val = select.value;

  if (val<min || max<val) errors += '<?php echo getescapedtext ($hcms_lang['time-must-contain-a-number-between'][$lang], $charset, $lang); ?> ' + min + ' <?php echo getescapedtext ($hcms_lang['and'][$lang], $charset, $lang); ?> ' + max + ' <?php echo getescapedtext ($hcms_lang['be'][$lang], $charset, $lang); ?>.\n';
  
  if (errors) 
  {
    select.focus();    
    alert(hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['the-following-error-occurred'][$lang], $charset, $lang); ?>\n' + errors));
  }
  else
  {
    if (val.length == 1) select.value = '0' + val;
    else if (val.length < 1) select.value = '00';
  }
  
  return false;
}

function submitform ()
{
  var artdatefromcheck = document.getElementById("artdatefrom").value;
  artdatefromcheck = artdatefromcheck.replace ("-", "");
  artdatefromcheck = artdatefromcheck.replace (" ", "");
  artdatefromcheck = artdatefromcheck.replace (":", "");
  
  var artdatetocheck = document.getElementById("artdateto").value;
  artdatetocheck = artdatetocheck.replace ("-", "");
  artdatetocheck = artdatetocheck.replace (" ", "");
  artdatetocheck = artdatetocheck.replace (":", "");
  
  if (artdatetocheck < artdatefromcheck)
  {
    alert(hcms_entity_decode('<?php echo getescapedtext ($hcms_lang['the-end-date-is-before-the-start-date-of-the-article'][$lang], $charset, $lang); ?>'));
    return false;
  }
  else
  {
    document.forms['article'].elements['artdatefrom'].name = "artdatefrom[<?php echo $artid; ?>]";
    document.forms['article'].elements['artdateto'].name = "artdateto[<?php echo $artid; ?>]";
    document.forms['article'].submit();
    return true;
  }
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" leftmargin=3 topmargin=3 marginwidth=0 marginheight=0>

<!-- top bar -->
<?php 
echo showtopbar ($label, $lang, $mgmt_config['url_path_cms']."page_view.php?view=".url_encode($view)."&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame");
?>

<form name="article" action="service/savecontent.php" method="post">
  <input type="hidden" name="contenttype" value="<?php echo $contenttype; ?>" />
  <input type="hidden" name="view" value="<?php echo $view; ?>" />
  <input type="hidden" name="site" value="<?php echo $site; ?>" />
  <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="page" value="<?php echo $page; ?>">
  <input type="hidden" name="contentfile" value="<?php echo $contentfile; ?>" />
  <input type="hidden" name="db_connect" value="<?php echo $db_connect; ?>" />
  <input type="hidden" name="artid" value="<?php echo $artid; ?>" />
  <input type="hidden" name="tagname" value="<?php echo $tagname; ?>" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>">
  
  <table border="0" cellspacing="5" cellpadding="0">
    <tr>
      <td colspan="2">
        <p class="hcmsHeadlineTiny"><?php echo getescapedtext ($hcms_lang['publication-settings-for-article'][$lang], $charset, $lang); ?></p>
      </td>
    </tr>        
    <tr>
      <td><?php echo getescapedtext ($hcms_lang['title-will-not-be-shown'][$lang], $charset, $lang); ?></td>
      <td>
        <input type="text" name="arttitle[<?php echo $artid; ?>]" value="<?php echo $arttitle; ?>" size="40" />
      </td>
    </tr>
    <tr>
      <td>
        <input type="radio" name="artstatus[<?php echo $artid; ?>]" value="active" <?php if ($artstatus == "active" || $artstatus == "") {echo "checked=\"checked\"";} ?>>
        <?php echo getescapedtext ($hcms_lang['set-active'][$lang], $charset, $lang); ?></td>
      <td>

      </td>
    </tr>
    <tr>
      <td><input type="radio" name="artstatus[<?php echo $artid; ?>]" value="inactive" <?php if ($artstatus == "inactive") {echo "checked=\"checked\"";} ?> />
      <?php echo getescapedtext ($hcms_lang['set-inactive'][$lang], $charset, $lang); ?></td>
      <td>
        
      </td>
    </tr>
    <tr>
      <td>
        <input type="radio" name="artstatus[<?php echo $artid; ?>]" value="timeswitched" <?php if ($artstatus == "timeswitched") {echo "checked=\"checked\"";} ?> />
        <?php echo getescapedtext ($hcms_lang['active-from'][$lang], $charset, $lang); ?></td>
      <td>
        <input type="text" name="artdatefrom" id="artdatefrom" readonly="readonly" value="<?php echo $artdatefrom; ?>" /><img src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'artdatefrom', '%Y-%m-%d %H:%i');" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang], $charset, $lang); ?>" align="top" class="hcmsButtonTiny hcmsButtonSizeSquare" />
        <?php echo getescapedtext ($hcms_lang['to'][$lang], $charset, $lang); ?>
        <input type="text" name="artdateto" id="artdateto" readonly="readonly" value="<?php echo $artdateto; ?>" /><img src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'artdateto', '%Y-%m-%d %H:%i');" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang], $charset, $lang); ?>" align="top" class="hcmsButtonTiny hcmsButtonSizeSquare" />
      </td>
    </tr>
    <tr>
      <td><?php echo getescapedtext ($hcms_lang['save-release-settings'][$lang], $charset, $lang); ?></td>
      <td>
        &nbsp;<img name="Button" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" src="<?php echo getthemelocation(); ?>img/button_ok.png" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" align="absmiddle" title="OK" alt="OK" onClick="submitform();" />        
      </td>
    </tr>
  </table>
  
</form>

</body>
</html>
