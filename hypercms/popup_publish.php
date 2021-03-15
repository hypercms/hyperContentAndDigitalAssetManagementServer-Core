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
$multiobject = getrequest_esc ("multiobject");
$location = getrequest_esc ("location", "locationname");
$folder = getrequest_esc ("folder", "objectname");
$page = getrequest_esc ("page", "objectname");
$filetype = getrequest_esc ("filetype", "objectname");
$media = getrequest_esc ("media", "objectname");
$action = getrequest_esc ("action");
$publish = getrequest ("publish");
$published_only = getrequest_esc ("published_only");
$publishdate = getrequest_esc ("publishdate");
$virtual = getrequest ("virtual");
$token = getrequest ("token");

// set current location
if ($action == "publish" || $action == "unpublish" || $mgmt_config[$site]['dam'] == true) $location_curr = $location.$folder."/";
else $location_curr = $location;

// get publication and category
$site = getpublication ($location_curr);
$cat = getcategory ($site, $location_curr); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location_curr, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

$access_allowed = true;
if ($virtual != 1 && ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['publish'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location))) $access_allowed = false;

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

$message = "";
$multiobject_array = array();

// show info if access is not allowed
if (!$access_allowed)
{
  echo showinfopage ($hcms_lang['you-do-not-have-access-permissions-to-this-object'][$lang], $lang);
  exit;
}

// check if location is converted (escaped)
if (substr_count ($location, "%comp%") > 0 || substr_count ($location, "%page%") > 0) $location_esc = $location;
else $location_esc = convertpath ($site, $location, $cat);

if ($action != "" && valid_publicationname ($site) && $cat != "" && valid_locationname ($location) && $publish != "")
{
  // prepare queue entries for later publishing
  if ($publish == "later")
  {
    // prepare multiobject array
    if ($multiobject != "")
    {
      $multiobject_array = link_db_getobject ($multiobject);
    }
    // or define multiobject array based on given location and object
    elseif ($site != "" && $location != "")
    {     
      if ($folder != "") $multiobject_array[] = convertpath ($site, $location.$folder."/.folder", $cat); 
      else $multiobject_array[] = convertpath ($site, $location.$page, $cat); 
    }
    
    // publish only already published objects
    if ($published_only == "") $published_only = "0";
    else $published_only = "1";    
    
    // save in publish queue
    if (is_array ($multiobject_array))
    {  
      $result = false;
         
      foreach ($multiobject_array as $multiobject)
      {
        if ($multiobject != "")
        {
          $result = createqueueentry ($action, $multiobject, $publishdate, $published_only, "", $user);
        }
      }
      
      if ($result == false) $message = getescapedtext ($hcms_lang['the-publishing-queue-could-not-be-saved'][$lang]);
      else $message = "<script language=\"JavaScript\" type=\"text/javascript\"> window.close(); </script>";
    }
    else $message = getescapedtext ($hcms_lang['no-objects-to-publish'][$lang]);
  }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script type="text/javascript" src="javascript/main.min.js"></script>

<link rel="stylesheet" type="text/css" href="javascript/rich_calendar/rich_calendar.css" />
<script type="text/javascript" src="javascript/rich_calendar/rich_calendar.min.js"></script>
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
function cal_on_change (cal, object_code)
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

function submitform ()
{
  if (document.forms['publish'].publish[0].checked == true)
  {
    document.forms['publish'].attributes['action'].value = "popup_status.php";
    document.forms['publish'].submit();
  }
  else if (document.forms['publish'].publish[1].checked == true)
  {
    if (document.forms['publish'].elements['publishdate'].value == "")
    {
      alert(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-set-a-date-for-publishing'][$lang]); ?>"));
    }
    else
    {
      document.forms['publish'].attributes['action'].value = "popup_publish.php";      
      document.forms['publish'].submit();
    }
  }
}
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php
if ($action == "publish") echo $headline = getescapedtext ($hcms_lang['publish-content'][$lang]);
elseif ($action == "unpublish") echo $headline = getescapedtext ($hcms_lang['unpublish-content'][$lang]);

echo showtopbar ($headline, $lang);

echo showmessage ($message, 360, 70, $lang, "position:fixed; left:10px; top:10px;");
?>

<div class="hcmsWorkplaceFrame">
  <form name="publish" method="post" action="">
    <input type="hidden" name="action" value="<?php echo $action; ?>" />
    <input type="hidden" name="force" value="start" />      
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="page" value="<?php echo correctfile ($location, $page, $user); ?>" />
    <input type="hidden" name="filetype" value="<?php echo $filetype; ?>" />          
    <input type="hidden" name="media" value="<?php echo $media; ?>" />
    <input type="hidden" name="folder" value="<?php echo $folder; ?>" />
    <input type="hidden" name="multiobject" value="<?php echo $multiobject; ?>" />
    <input type="hidden" name="token" value="<?php echo $token; ?>" /> 
    
    <table class="hcmsTableStandard" style="width:100%;">
      <tr> 
        <td>
          <label><input name="publish" type="radio" value="now" checked="checked" /> <?php echo getescapedtext ($hcms_lang['now'][$lang]); ?></label>
  	    </td>
      </tr>
      <tr> 
        <td>		
          <label>
            <input name="publish" type="radio" value="later" /> <?php echo getescapedtext ($hcms_lang['on-date'][$lang]); ?>
            <input type="text" name="publishdate" id="publishdate" readonly="readonly" value="<?php echo $publishdate; ?>" /><img name="datepicker" src="<?php echo getthemelocation(); ?>img/button_datepicker.png" onclick="show_cal(this, 'publishdate', '%Y-%m-%d %H:%i');" class="hcmsButtonTiny hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['select-date'][$lang]); ?>" />
          </label>
  	    </td>
      </tr>
      <?php if ($action == "publish") { ?>
      <tr> 
        <td>
          <label><input type="checkbox" name="published_only" value="1" /> <?php echo getescapedtext ($hcms_lang['only-already-published-content'][$lang]); ?></label>
  	    </td>
      </tr>
      <?php } ?>
      <tr>  
        <td>  
          <?php echo $headline; ?> <img name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onClick="submitform();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" title="OK" alt="OK" />
        </td>
      </tr>
    </table>
  </form>
</div>

<?php includefooter(); ?>
</body>
</html>