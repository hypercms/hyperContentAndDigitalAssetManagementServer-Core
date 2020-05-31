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
$action = getrequest ("action");
$contentdate = getrequest_esc ("contentdate");
$templatedate = getrequest_esc ("templatedate");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('desktop') || !checkrootpermission ('desktoptimetravel'))  killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$date_content = Null;
$date_template = Null;
$day_content = Null;
$month_content = Null;
$year_content = Null;
$day_template = Null;
$month_template = Null;
$year_template = Null;
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<script type="text/javascript">
function submitform ()
{
  var contentdate = document.forms['history'].elements['content_year'].value + "-" + document.forms['history'].elements['content_month'].value + "-" + document.forms['history'].elements['content_day'].value;
  var templatedate = document.forms['history'].elements['template_year'].value + "-" + document.forms['history'].elements['template_month'].value + "-" + document.forms['history'].elements['template_day'].value ;
  
  var contentdatecheck = document.forms['history'].elements['content_year'].value + document.forms['history'].elements['content_month'].value + document.forms['history'].elements['content_day'].value;
  var templatedatecheck = document.forms['history'].elements['template_year'].value + document.forms['history'].elements['template_month'].value + document.forms['history'].elements['template_day'].value;

  document.forms['history'].elements['action'].value = "set";  
  document.forms['history'].elements['contentdate'].value = contentdate;
  document.forms['history'].elements['templatedate'].value = templatedate;
  document.forms['history'].submit();
  
  return true;
}

function cleandate ()
{
  document.forms['history'].elements['action'].value = "clean";
  document.forms['history'].submit();
  
  return true;
}
</script>
</head>
<?php
if ($action == "set")
{
  if ($contentdate != "" && $templatedate != "")
  {
    $data = "<?php\n\$date_content = \"".$contentdate."\";\n\$date_template = \"".$templatedate."\";\n?>";
    savefile ($mgmt_config['abs_path_temp'], session_id().".dates.php", $data);
  }
}
elseif ($action == "clean")
{
  deletefile ($mgmt_config['abs_path_temp'], session_id().".dates.php", 0);
}

// load setup date of hypercms (yyyy-mm-dd)
$setupdate = loadfile ($mgmt_config['abs_path_data'], "check.dat");

if ($setupdate != "") list ($year_setup, $month_setup, $day_setup) = explode ("-", $setupdate);

// load set dates for history view
if (file_exists ($mgmt_config['abs_path_temp'].session_id().".dates.php"))
{
  include ($mgmt_config['abs_path_temp'].session_id().".dates.php");
}
  
if ($date_content != "") list ($year_content, $month_content, $day_content) = explode ("-", $date_content);
if ($date_template != "") list ($year_template, $month_template, $day_template) = explode ("-", $date_template);
?>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php echo showtopbar ($hcms_lang['travel-through-time'][$lang], $lang); ?>

<div style="background:url('<?php echo getthemelocation(); ?>img/backgrd_history.png') no-repeat left top; background-size:contain; max-width:420px; min-height:450px; padding:4px;">
<p><?php echo getescapedtext ($hcms_lang['here-you-can-start-your-journey-into-the-past'][$lang]); ?></p>
<form name="history" action="" method="post">
  <input type="hidden" name="action" value="">
  <input type="hidden" name="contentdate" value="">
  <input type="hidden" name="templatedate" value="">
  
  <table class="hcmsTableStandard">
    <tr> 
      <td>&nbsp;</td>
      <td style="white-space:nowrap; text-align:center;"><?php echo getescapedtext ($hcms_lang['year'][$lang]); ?></td>
      <td style="white-space:nowrap; text-align:center;"><?php echo getescapedtext ($hcms_lang['month'][$lang]); ?></td>
      <td style="white-space:nowrap; text-align:center;"><?php echo getescapedtext ($hcms_lang['day'][$lang]); ?></td>
    </tr>
    <tr> 
      <td><?php echo getescapedtext ($hcms_lang['show-the-content-online-on'][$lang]); ?> </td>
      <td>
        <select name="content_year">
          <?php                
          $startyear = $year_setup;
          $endyear = date ("Y", time());
          
          for ($y=$startyear; $y<=$endyear; $y++)
          {
            echo "<option value=\"".$y."\""; if ($year_content == $y) {echo "selected=\"selected\"";} echo ">".$y."</option>";
          }
          ?>
        </select>
      </td>
      <td>
        <select name="content_month">
          <?php 
          for ($m=1; $m<=12; $m++)
          {
            if (strlen ($m) == 1) $m = "0".$m;
            
            echo "<option value=\"".$m."\""; if ($month_content == $m) echo "selected=\"selected\""; echo ">".$m."</option>\n";
          }
          ?>
        </select>
      </td>
      <td>
        <select name="content_day">
          <?php 
          for ($d=1; $d<=31; $d++)
          {
            if (strlen ($d) == 1) $d = "0".$d;
            
            echo "<option value=\"".$d."\""; if ($day_content == $d) echo "selected=\"selected\""; echo ">".$d."</option>\n";
          }
          ?>
        </select>
       </td>
    </tr>
    <tr> 
      <td><?php echo getescapedtext ($hcms_lang['show-the-design-online-on'][$lang]); ?> </td>
      <td>
        <select name="template_year">
          <?php 
          $year = 0;
           
          for ($y=$startyear; $y<=$endyear; $y++)
          {
            echo "<option value=\"".$y."\""; if ($year_template == $y) {echo "selected=\"selected\"";} echo ">".$y."</option>\n";
            $year++;
          }
          ?>
        </select>
      </td>
      <td>
        <select name="template_month">
          <?php 
          for ($m=1; $m<=12; $m++)
          {
            if (strlen ($m) == 1) $m = "0".$m;
            
            echo "<option value=\"".$m."\""; if ($month_template == $m) echo "selected=\"selected\""; echo ">".$m."</option>\n";
          }
          ?>
        </select>
      </td>
      <td>
        <select name="template_day">
          <?php 
          for ($d=1; $d<=31; $d++)
          {
            if (strlen ($d) == 1) $d = "0".$d;
            
            echo "<option value=\"".$d."\""; if ($day_template == $d) echo "selected=\"selected\""; echo ">".$d."</option>\n";
          }
          ?>
        </select>
      </td>
    </tr>
  </table>
  
  <div style="margin-top:12px;">
    <button type="button" name="ButtonSet" class="hcmsButtonGreen" style="width:98%; margin:4px 0px;" onClick="submitform();"><?php echo getescapedtext ($hcms_lang['set-date-for-the-journey'][$lang]); ?></button>
    <button type="button" name="ButtonClean" class="hcmsButtonOrange" style="width:98%; margin:4px 0px;" onClick="cleandate();"><?php echo getescapedtext ($hcms_lang['clean-date-exit'][$lang]); ?></button>
  </style>

</form>
</div>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>
