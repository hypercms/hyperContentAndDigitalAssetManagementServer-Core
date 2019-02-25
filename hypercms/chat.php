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


// ------------------------------ permission section --------------------------------

// check access to chat
if (!isset ($mgmt_config['chat']) || $mgmt_config['chat'] != true) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS Chat</title>
<meta charset="utf-8" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<style type="text/css">
#chat-area span
{
  font-size: 11px;
  color: #FFFFFF;
  background: #333333;
  padding: 2px 4px;
  -moz-border-radius: 3px;
  -webkit-border-radius: 3px;
  border-radius: 3px;
  margin: 0px 5px 0px 0px;
}
#chat-area p
{
  padding: 4px 0px;
  border-bottom: 1px solid #333333;
}
</style>
<script type="text/javascript" src="javascript/main.js" ></script>
<script type="text/javascript" src="javascript/jquery/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="javascript/chat.js"></script>
<script type="text/javascript">
// user name    
var username = "<?php echo $user; ?>";
<?php if (!empty ($temp_chatstate)) echo "state = ".$temp_chatstate.";\n"; ?>

// strip tags
username = username.replace(/(<([^>]+)>)/ig,"");

// audio file
var audio = new Audio('javascript/ding.mp3');
	
// start chat
var chat =  new Chat();

$(function()
{
  //chat.getState();
  getusersonline();

  // watch textarea for key pressed
  $('#send-message-input').keydown(function(event)
  {
    var key = event.which;  
 
    // all keys including return.  
    if (key >= 33)
    {
      var maxLength = $(this).attr("maxlength");  
      var length = this.value.length;  
     
      // don't allow new content if length is maxed out
      if (length >= maxLength) event.preventDefault();
    }  
  });

	// watch textarea for release of key press
	$('#send-message-input').keyup(function(e)
  {	 
	  if (e.keyCode == 13)
    {
      var text = $(this).val();
		  var maxLength = $(this).attr("maxlength");  
      var length = text.length; 
       
      // send 
      if (length <= maxLength + 1)
      { 
        chat.send(text, username);	
        $(this).val("");
      }
      else
      {
			  $(this).val(text.substring(0, maxLength));
      }
    }
  });
});

function invite (inviteuser)
{
  if (inviteuser != "")
  {
    chat.inviteUser(inviteuser, username); 
  }
}

function adjust_height ()
{
  var height = hcms_getDocHeight();  
  
  setheight = height - 160;
  document.getElementById('chat-area').style.height = setheight + "px";
}

function getusersonline ()
{
  var usersonline;
  var result = '';

	$.ajax({
		async: false,
		type: 'POST',
		url: '<?php echo $mgmt_config['url_path_cms']; ?>service/getusersonline.php',
		data: {},
		dataType: 'json',
		success: function(data){ if(data.success) {usersonline = data.usersonline;}}
	});
  
  if (usersonline)
  {
    for (var i in usersonline)
    {
      if (usersonline.hasOwnProperty(i) && usersonline[i] != "<?php echo $user; ?>")
      {
        result = result + "    <div class=\"hcmsSelectorItem\" style=\"text-align:left;\" onclick=\"invite('" + usersonline[i] + "');\"><img src=\"<?php echo getthemelocation()."img/user.png"; ?>\" class=\"hcmsIconList\" />" + usersonline[i] + "&nbsp;</div>\n";
      }
    }
  }
  
  if (result != "") document.getElementById('select_user').innerHTML = result;
  else document.getElementById('select_user').innerHTML = "   <div style=\"text-align:left;\">&nbsp; . . . . . . &nbsp;</div>\n";
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" onload="setInterval('chat.update()', 1000); setInterval('getusersonline()', 10000); adjust_height();" onresize="adjust_height();">

<?php echo showtopbar ($hcms_lang['chat'][$lang], $lang); ?>

<div style="position:fixed; top:2px; right:8px; z-index:1000;">
  <button class="hcmsButtonOrange hcmsButtonSizeHeight" style="white-space:nowrap;" onClick="hcms_switchSelector('select_user');"><?php echo getescapedtext ($hcms_lang['invite-online-user'][$lang]); ?></button>
  <div id="select_user" class="hcmsSelector" style="position:absolute; top:32px; right:0px; visibility:hidden; z-index:999; max-height:300px; overflow:auto; overflow-x:hidden; overflow-y:auto; white-space:nowrap;">
  </div>
</div>

<div id="page-wrap" style="margin:0; padding:0;">
   
  <div id="chat-wrap" style="margin:8px;">
    <div id="chat-area" style="height:300px; overflow:auto; padding:5px;"></div>
  </div>
        
  <form id="send-message-area" style="margin:8px;">
    <textarea id="send-message-input" class="hcmsInfoBox" style="width:272px; height:60px; margin:0; padding:5px;" placeholder="<?php echo getescapedtext ($hcms_lang['your-message'][$lang]); ?>" maxlength="800"></textarea>
  </form>
  
</div>

</body>
</html>