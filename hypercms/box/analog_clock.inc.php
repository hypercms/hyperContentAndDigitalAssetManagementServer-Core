<?php
// ---------------------- ANALOG CLOCK ---------------------

// box width
$width = "320px";

// date
if (!empty ($hcms_lang_date[$lang]))
{
  list ($date_format, $time_format) = explode (" ", $hcms_lang_date[$lang]);
  $date = date ($date_format);
}
else $date = date ("Y-m-d");
?>

<div id="recent" class="hcmsHomeBox" style="margin:10px; width:<?php echo $width; ?>; height:400px; float:left;">
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
  <div style="padding-top:20px; text-align:center; font-size:26px; line-height:32px; overflow:hidden;">
    <?php echo $date; ?>
</div>
</div>

<script>
const deg = 6;
const hr = document.querySelector('#hr');
const min = document.querySelector('#min');
const sec = document.querySelector('#sec');

setInterval(() => {
    let day = new Date();
    let hh = day.getHours() * 30;
    let mm = day.getMinutes() * deg;
    let ss = day.getSeconds() * deg;

    hr.style.transform = `rotateZ(${(hh)+(mm/12)}deg)`;
    min.style.transform = `rotateZ(${mm}deg)`;
    sec.style.transform = `rotateZ(${ss}deg)`;
})
</script>
