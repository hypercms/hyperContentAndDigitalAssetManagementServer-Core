<?php
// ---------------------- ANALOG CLOCK ---------------------

// box width
$width = "320px";
?>

<div id="analog_clock" class="hcmsHomeBox" style="margin:10px; width:<?php echo $width; ?>; height:400px;">
  <div class="hcmsHeadline" style="margin:6px 2px;"><img src="<?php echo getthemelocation("night"); ?>img/button_time.png" class="hcmsIconList" /> <?php echo getescapedtext ($hcms_lang['server-time'][$lang]); ?></div>
  <div class="Clock">
    <div class="hour">
      <div class="hr" id="hr"></div>
    </div>
    <div class="minute">
      <div class="min" id="min"></div>
    </div>
    <div class="second">
      <div class="sec" id="sec"></div>
    </div>
  </div>
  <div id="analog_clock_date" style="padding-top:0px; text-align:center; font-size:20px; line-height:20px; overflow:hidden;"></div>
</div>

<script>
// clock pointers
const deg = 6;
const hr = document.querySelector('#hr');
const min = document.querySelector('#min');
const sec = document.querySelector('#sec');

function showAnalogClock ()
{
  var analog_hh = serverdate.getHours() * 30;
  var analog_mm = serverdate.getMinutes() * deg;
  var analog_s = serverdate.getSeconds();
  var analog_ss = analog_s * deg;

  // display time in clock
  hr.style.transform = `rotateZ(${(analog_hh)+(analog_mm/12)}deg)`;
  min.style.transform = `rotateZ(${analog_mm}deg)`;
  sec.style.transform = `rotateZ(${analog_ss}deg)`;

  var analog_year = serverdate.getFullYear();
  var analog_month = serverdate.getMonth() + 1;
  if (analog_month < 10) month = '0' + analog_month;
  var analog_day = serverdate.getDate();
  if (analog_day < 10) analog_day = '0' + analog_day;

  // display date
  document.getElementById("analog_clock_date").innerHTML = "<small><small><?php echo $hcms_lang['day'][$lang]; ?></small></small> " + analog_day + " <small><small><?php echo $hcms_lang['month'][$lang]; ?></small></small> " + analog_month + " <small><small><?php echo $hcms_lang['year'][$lang]; ?></small></small> " + analog_year;

  setTimeout (showAnalogClock, 1000);
}

// init
showAnalogClock();
</script>
