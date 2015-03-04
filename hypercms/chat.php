<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("include/session.inc.php");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");


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
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
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
  border-bottom: 1px solid #ccc;
}
</style>
<script src="javascript/main.js" language="JavaScript" type="text/javascript"></script>
<script type="text/javascript" src="javascript/jquery/jquery-1.9.1.min.js"></script>
<script type="text/javascript" src="javascript/chat.js"></script>
<script type="text/javascript">
// user name    
var username = "<?php echo $user; ?>";
<?php if (!empty ($temp_chatstate)) echo "state = ".$temp_chatstate.";\n"; ?>

// strip tags
username = username.replace(/(<([^>]+)>)/ig,"");
	
// start chat
var chat =  new Chat();

$(function()
{
  //chat.getState();

  // watch textarea for key pressed
  $("#send-message-input").keydown(function(event)
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
</script>
</head>

<body class="hcmsWorkplaceExplorer" onload="setInterval('chat.update()', 1000); adjust_height();" onresize="adjust_height();">

<?php
$users_online = getusersonline ();

if (is_array ($users_online) && sizeof ($users_online) > 1)
{
  $users_online_button = "<button class=\"hcmsButtonOrange\" style=\"heigth:20px; margin:0; white-space:nowrap;\" onClick=\"hcms_switchSelector('select_user');\">".$hcms_lang['invite-online-user'][$lang]."</button>
  <div id=\"select_user\" class=\"hcmsSelector\" style=\"position:fixed; top:26px; right:8px; visibility:hidden; z-index:999; max-height:300px; overflow:auto; overflow-x:hidden; overflow-y:auto; white-space:nowrap;\">\n";

  foreach ($users_online as $user_online) if ($user_online != $user) $users_online_button .= "    <div class=\"hcmsSelectorItem\" style=\"text-align:left\" onclick=\"invite('".$user_online."');\"><img src=\"".getthemelocation()."img/user.gif\" style=\"border:0; margin:0; padding:0;\" align=\"absmiddle\" />".$user_online."&nbsp;</div>\n";

  $users_online_button .= "  </div>\n";
}
else $users_online_button = "<button class=\"hcmsButtonOrange\" style=\"heigth:20px; margin:0; white-space:nowrap;\" onClick=\"location.reload();\">".$hcms_lang['invite-online-user'][$lang]."</button>";
?>

<?php echo showtopbar ($hcms_lang['chat'][$lang], $lang); ?>

<div style="position:fixed; top:2px; right:8px; z-index:1000;"><?php echo $users_online_button; ?></div>

<div id="page-wrap" style="margin:0; padding:0;">
   
  <div id="chat-wrap" class="hcmsInfoBox" style="margin:8px;">
    <div id="chat-area" style="height:300px; overflow:auto; padding:5px;"></div>
  </div>
        
  <form id="send-message-area" style="margin:8px;">
    <textarea id="send-message-input" class="hcmsInfoBox" style="width:272px; height:60px; margin:0; padding:5px;" placeholder="<?php echo $hcms_lang['your-message'][$lang]; ?>" maxlength="600"></textarea>
  </form>
  
</div>

</body>
</html>