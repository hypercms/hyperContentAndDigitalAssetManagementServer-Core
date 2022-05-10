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
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
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
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>" ></script>
<script type="text/javascript" src="javascript/jquery/jquery.min.js"></script>
<script type="text/javascript" src="javascript/chat.min.js"></script>
<script type="text/javascript">
// user name    
var username = "<?php echo $user; ?>";
<?php if (!empty ($temp_chatstate)) echo "chat_state = ".$temp_chatstate.";\n"; ?>

// strip tags
username = username.replace(/(<([^>]+)>)/ig,"");

// audio file
var chat_audio = new Audio('javascript/ding.mp3');
	
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

function uninvite ()
{
  if (username != "")
  {
    chat.uninviteUsers(username);
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

function initialize ()
{
  setInterval('chat.update()', <?php if (!empty ($mgmt_config['chat_update_interval']) && intval ($mgmt_config['chat_update_interval']) > 99) echo intval ($mgmt_config['chat_update_interval']); else echo "1600"; ?>);
  setInterval('getusersonline()', 12300);
  adjust_height();
}
</script>
</head>

<body class="hcmsWorkplaceGeneric" onload="initialize();" onresize="adjust_height();">

<!-- top bar -->
<div class="hcmsWorkplaceBar" style="width:100%;">
  <table class="hcmsTableNarrow" style="width:100%;">
    <tr>
      <td style="width:38px; height:38px; text-align:left; white-space:nowrap;">
        <img src="<?php echo getthemelocation(); ?>img/button_user_new.png" class="hcmsButtonTiny hcmsButtonSizeSquare" style="padding:3px;" onClick="hcms_switchSelector('select_user');" alt="<?php echo getescapedtext ($hcms_lang['invite-online-user'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['invite-online-user'][$lang]); ?>" />
        <div id="select_user" class="hcmsSelector" style="position:absolute; top:32px; left:5px; visibility:hidden; z-index:999; max-height:300px; overflow:auto; overflow-x:hidden; overflow-y:auto; white-space:nowrap;"></div>
        <?php if (!empty ($mgmt_config['chat_type']) && strtolower ($mgmt_config['chat_type']) == "private") { ?>
        <img src="<?php echo getthemelocation(); ?>img/button_user_delete.png" class="hcmsButtonTiny hcmsButtonSizeSquare" style="padding:3px;" onClick="uninvite();" alt="<?php echo getescapedtext ($hcms_lang['remove-user'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['remove-user'][$lang]); ?>" />
        <?php } ?>
      </td>
      <td class="hcmsHeadline" style="text-align:center;">
        <?php echo getescapedtext ($hcms_lang['chat'][$lang]); ?>
      </td>
      <td style="width:38px; text-align:center;">
        <?php if (!$is_mobile) { ?>
        <img name="closechat" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTiny hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('closechat','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="parent.hcms_openChat();" />
        <?php } ?>
      </td>
    </tr>
  </table>
</div>
<div style="width:100%; height:38px;">&nbsp;</div>

<!-- chat area -->
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