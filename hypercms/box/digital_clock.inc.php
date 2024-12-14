<?php
// ---------------------- ANALOG CLOCK ---------------------

// box width
$width = "320px";
?>

<div id="digital_clock" class="hcmsHomeBox" style="margin:10px; width:<?php echo $width; ?>; height:400px;">
  <div class="hcmsHeadline" style="margin:6px 2px;"><img src="<?php echo getthemelocation("night"); ?>img/button_time.png" class="hcmsIconList" /> <?php echo getescapedtext ($hcms_lang['server-time'][$lang]); ?></div>
  <div id="digital_clock_time" style="text-align:center; font-size:50px; letter-spacing:2px; padding-top:130px;" onload="showTime();"></div>
  <div id="digital_clock_date" style="padding-top:130px; text-align:center; font-size:20px; line-height:20px; overflow:hidden;"></div>
</div>

<script>
function showDigitalClock ()
{
  var digital_hr = serverdate.getHours(); // 0 - 23
  var digital_min = serverdate.getMinutes(); // 0 - 59
  var digital_s = serverdate.getSeconds(); // 0 - 59
  var digital_sec = digital_s;
  var session = "AM";

  if (digital_hr == 0)
  {
    digital_hr = 12;
  }

  if (digital_hr > 12)
  {
    digital_hr = digital_hr - 12;
    session = "PM";
  }

  digital_hr = (digital_hr < 10) ? "0" + digital_hr : digital_hr;
  digital_min = (digital_min < 10) ? "0" + digital_min : digital_min;
  digital_sec = (digital_sec < 10) ? "0" + digital_sec : digital_sec;

  // display time
  document.getElementById("digital_clock_time").innerHTML = digital_hr + ":" + digital_min + ":" + digital_sec + "<small><small><small>" + session + "</small></small></small>";

  var digital_year = serverdate.getFullYear();
  var digital_month = serverdate.getMonth() + 1;
  if (digital_month < 10) digital_month = '0' + digital_month;
  var digital_day = serverdate.getDate();
  if (digital_day < 10) digital_day = '0' + digital_day;

  // display date
  document.getElementById("digital_clock_date").innerHTML = "<small><small><?php echo $hcms_lang['day'][$lang]; ?></small></small> " + digital_day + " <small><small><?php echo $hcms_lang['month'][$lang]; ?></small></small> " + digital_month + " <small><small><?php echo $hcms_lang['year'][$lang]; ?></small></small> " + digital_year;

  setTimeout (showDigitalClock, 1000);
}

// init
showDigitalClock();
</script>
