<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("../include/session.inc.php");
// management configuration
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");
// hyperCMS UI
require ("../function/hypercms_ui.inc.php");
// load language file
require_once ("../language/editorl.inc.php");


// input parameters
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$contenttype = getrequest_esc ("contenttype");
$contentbot = getrequest ("contentbot");
$db_connect = getrequest_esc ("db_connect", "objectname");
$id = getrequest_esc ("id", "objectname");
$label = getrequest_esc ("label");
$tagname = getrequest_esc ("tagname", "objectname");
$format = getrequest_esc ("format", false, "", true);
$default = getrequest_esc ("default");
$token = getrequest ("token");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);  
if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// load object file and get container
$objectdata = loadfile ($location, $page);
$contentfile = getfilename ($objectdata, "content");

// format
if (substr_count ($format, "%") == 0) $format = "%Y-%m-%d";

// define content-type if not set
if ($contenttype == "") 
{
  $contenttype = "text/html; charset=".$mgmt_config[$site]['default_codepage'];
  $charset = $mgmt_config[$site]['default_codepage'];
}
elseif (strpos ($contenttype, "charset") > 0) $charset = getattribute ($contenttype, "charset");

// create secure token
$token = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="<?php echo $contenttype; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="../javascript/main.js" type="text/javascript"></script>

<link rel="stylesheet" type="text/css" href="../javascript/rich_calendar/rich_calendar.css">
<script language="JavaScript" type="text/javascript" src="../javascript/rich_calendar/rich_calendar.js"></script>
<script language="JavaScript" type="text/javascript" src="../javascript/rich_calendar/rc_lang_en.js"></script>
<script language="JavaScript" type="text/javascript" src="../javascript/rich_calendar/rc_lang_de.js"></script>
<script language="Javascript" type="text/javascript" src="../javascript/rich_calendar/domready.js"></script>

<script language="JavaScript">
<!--
var cal_obj = null;
var format = '<?php echo $format; ?>';

// show calendar
function show_cal (el)
{
	if (cal_obj) return;
  var date_field = document.getElementById("date_field");

	cal_obj = new RichCalendar();
	cal_obj.start_week_day = 1;
	cal_obj.language = '<?php echo $lang; ?>';
	cal_obj.user_onchange_handler = cal_on_change;
	cal_obj.user_onautoclose_handler = cal_on_autoclose;
	cal_obj.parse_date(date_field.value, format);
	cal_obj.show_at_element(date_field, "adj_left-bottom");
}

// user defined onchange handler
function cal_on_change (cal, object_code)
{
	if (object_code == 'day')
	{
		document.getElementById("date_field").value = cal.get_formatted_date(format);
		cal.hide();
		cal_obj = null;
	}
}

// user defined onautoclose handler
function cal_on_autoclose (cal)
{
	cal_obj = null;
}

function setsavetype(type)
{
  document.forms['editor'].elements['savetype'].value = type;
  document.forms['editor'].submit();
}
//-->
</script>
</head>

<body class="hcmsWorkplaceGeneric" leftmargin=3 topmargin=3 marginwidth=0 marginheight=0>
<?php
// read content using db_connect
if ($contentbot == "")
{
  if (!empty ($db_connect) && $db_connect != false && file_exists ($mgmt_config['abs_path_data']."db_connect/".$db_connect)) 
  {
    include ($mgmt_config['abs_path_data']."db_connect/".$db_connect);
    
    $db_connect_data = db_read_text ($site, $contentfile, "", $id, "", $user);
    
    if ($db_connect_data != false) $contentbot = $db_connect_data['text'];
    else $contentbot = false;
  }  
  else $contentbot = false;
  
  // read content using db_connect_tamino
  if ($contentbot == false && !empty ($mgmt_config['db_connect_tamino']) && file_exists ($mgmt_config['abs_path_data']."db_connect/".$mgmt_config['db_connect_tamino']))
  {
    include ($mgmt_config['abs_path_data']."db_connect/".$mgmt_config['db_connect_tamino']);
    
    $db_connect_data = db_read_text ("work", $site, $contentfile, "", $id, "", $user);
    
    if ($db_connect_data != false) $contentbot = $db_connect_data['text'];
    else $contentbot = false;
  }
  
  // read content from content container
  if ($contentbot == false) 
  {
    $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml")); 
    
    $filedata = loadcontainer ($contentfile, "work", $user);
    
    if ($filedata != "")
    {
      $contentarray = selectcontent ($filedata, "<text>", "<text_id>", $id);
      $contentarray = getcontent ($contentarray[0], "<textcontent>");
      $contentbot = $contentarray[0];
    }
  }
}

// set default value given eventually by tag
if ($contentbot == "" && $default != "") $contentbot = $default;

// escape special characters
$contentbot = str_replace (array("\"", "<", ">"), array("&quot;", "&lt;", "&gt;"), $contentbot);  

if ($label == "") $label = $id;
?>

<!-- top bar -->
<?php echo showtopbar ($label, $lang, $mgmt_config['url_path_cms']."page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame"); ?>

<!-- form for content -->
<div style="padding:0; width:100%; z-index:1;">
  <form name="editor" method="post" action="<?php echo $mgmt_config['url_path_cms']; ?>page_save.php">
    <input type="hidden" name="contenttype" value="<?php echo $contenttype; ?>">
    <input type="hidden" name="site" value="<?php echo $site; ?>">
    <input type="hidden" name="cat" value="<?php echo $cat; ?>">
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>">
    <input type="hidden" name="page" value="<?php echo $page; ?>">
    <input type="hidden" name="db_connect" value="<?php echo $db_connect; ?>">
    <input type="hidden" name="tagname" value="<?php echo $tagname; ?>">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <input type="hidden" name="format" value="<?php echo $format; ?>">
    <input type="hidden" name="savetype" value="">
    <input type="hidden" name="<?php echo $tagname."[".$id."]"; ?>" value="">
    <input type="hidden" name="token" value="<?php echo $token; ?>">
    
    <table border="0" cellspacing="2">
      <tr>
        <td>
        <img border="0" name="Button_so" src="<?php echo getthemelocation(); ?>img/button_save.gif" class="hcmsButton hcmsButtonSizeSquare" onClick="setsavetype('editord_so');" alt="<?php echo $text1[$lang]; ?>" title="<?php echo $text1[$lang]; ?>">
        <img border="0" name="Button_sc" src="<?php echo getthemelocation(); ?>img/button_saveclose.gif" class="hcmsButton hcmsButtonSizeSquare" onClick="setsavetype('editord_sc');" alt="<?php echo $text2[$lang]; ?>" title="<?php echo $text2[$lang]; ?>">
        <br />
        <input type="text" id="date_field" name="<?php echo $tagname."[".$id."]"; ?>" value="<?php echo $contentbot; ?>" />
        <img name="datepicker" src="<?php echo getthemelocation(); ?>img/button_datepicker.gif" onclick="show_cal(this);" align="absmiddle" class="hcmsButtonTiny hcmsButtonSizeSquare" alt="<?php echo $text3[$lang]; ?>" title="<?php echo $text3[$lang]; ?>" />
        </td>
      </tr>
    </table>
    
  </form>
</div>

</body>
</html>