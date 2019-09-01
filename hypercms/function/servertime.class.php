<?php
/*
 * Changes:
 * 2003-09-06: Made a class out of the script, made it easier to implement
 *             (only 2 installing points instead of three) 
 *             made it work with Mozilla 1.4
 */

/*
The Server-Date-Time script
Purpose: Install a digital clock showing server date and time using php and javascript

the original javascript-code comes from 
http://javascript.internet.com/clocks/basic-clock.html

copyright (c) 2001 by knito@knito.de
http://www.ingoknito.de

License: FREE
*/

class servertime
{
  var $divid = 'ServerTime'; // default id name
  var $divstyle = 'position:absolute;'; // default for NS 4.7
  var $divtag = 'span'; // other possibility: 'span'
  var $divclass = 'hcmsHeadlineTiny hcmsTextWhite'; // default empty
  var $title = '';

  # Internal use only: $ok_head and $ok_clock
  #
  var $ok_head  = false; // InstallClockBody() and InstallClock() will check for this.
  var $ok_clock = false; // InstallClockBody() will check for this, too.

  # This function is to be used in the <head> section of the page.
  function InstallClockHead ()
  {
    echo "\n<script type=\"text/javascript\">\n<!--\n";

    # Here is where the server time comes into the script:
    # date() is a php function which runs on the server, giving exactly
    # the time the server has.
    #
    echo 'var digital = new Date( "'.date('M, d Y H:i:s').'");'; // <-- this is the trick!

    echo "\n\nfunction writeLayer(layerID,txt)".
    "\n{\n  if(document.getElementById)\n  {\n".
    "    document.getElementById(layerID).innerHTML=txt;\n".
    "  }\n  else if(document.all)\n  {\n    document.all[layerID].innerHTML=txt;\n".
    "  }\n  else if(document.layers)\n  {\n".
    "    document.layers[layerID].document.open();\n".
    "    document.layers[layerID].document.write(txt);\n".
    "    document.layers[layerID].document.close();\n  }\n}\n"; 
 
   echo "\n//-->\n</script>\n";
 
   $this->ok_head = true; // Check later
 
  } // eof InstallClockHead();

  # This is to be used where you want the clock to appear on your page.
  function InstallClock ()
  {
    # To have it work with NS 4.7 the style "position:absolute" MUST be given (knito)
    $klasse = strlen( trim( $this->divclass ) ) > 0 ? " class='".$this->divclass."'" : '';
    $style  = strlen( trim( $this->divstyle ) ) > 0 ? " style='".$this->divstyle."'" : '';

    echo "<".$this->divtag." id='".$this->divid."'".$style.$klasse.">".$this->title."</".$this->divtag.">";

    if( $this->ok_head == false )
    {
      die("InstallClockHead() is missing");
    }

    $this->ok_clock = true;
  } // eof Clock() 


  # This function is to be used at the end of the <body> section of the page.
  function InstallClockBody ()
  {
    echo "\n<script language='JavaScript' type='text/javascript'>\n<!--\n".
    "function clock()\n{\n".
    "  var year = digital.getFullYear();\n".
    "  var month = digital.getMonth() + 1;\n".
    "  if (month < 10) month = '0' + month;\n".
    "  var day = digital.getDate();\n".
    "  if (day < 10) day = '0' + day;\n".
    "  var hours = digital.getHours();\n".
    "  var minutes = digital.getMinutes();\n".
    "  var seconds = digital.getSeconds();\n".
    "  var dispTime;\n\n  digital.setMinutes( minutes+1 );\n\n".
    "  if (minutes < 10) minutes = '0' + minutes;\n".
    "  if (seconds < 10) seconds = '0' + seconds;\n";

    echo "  dispTime = year + \"-\" + month + \"-\" + day + \" \" + \" \" + hours + \":\" + minutes\n";

    echo "  writeLayer( '".$this->divid."', dispTime );\n".
    "  setTimeout(\"clock()\", 60000);\n}\n\n".
    "clock();\n//-->\n</script>\n";

    if( $this->ok_head == false )
    {
      die("InstallClockHead() is missing");
    }
    if( $this->ok_clock == false )
    {
      die("InstallClock() is missing");
    }
  } // eof InstallClockBody
} // eoc ServerTime
?>