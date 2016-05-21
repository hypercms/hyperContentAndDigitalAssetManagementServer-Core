var instance = false;
var state;
var message;
var file;

function Chat ()
{
  this.update = updateChat;
  this.send = sendChat;
  this.getState = getStateOfChat;
  this.getInitState = getInitStateOfChat;
  this.inviteUser = inviteUserToChat;
  this.check = checkChat;
  this.open = openChat;
}

// open chat windows/layer
function openChat ()
{
  // standard browser
  if (document.getElementById('chatLayer'))
  {
    var chatsidebar = document.getElementById('chatLayer');            
    if (chatsidebar.style.display == "none") chatsidebar.style.display = "block";
  }
  else if (parent.document.getElementById('chatLayer'))
  {
    var chatsidebar = parent.document.getElementById('chatLayer');            
    if (chatsidebar.style.display == "none") chatsidebar.style.display = "block";
  }
  // mobile browser
  else if (document.getElementById('chat'))
  {
    $("#chat").panel("open");
  }
  else if (parent.document.getElementById('chat'))
  {
    parent.$("#chat").panel("open");
  }

  // scroll down
  if (document.getElementById('chat-area')) document.getElementById('chat-area').scrollTop = document.getElementById('chat-area').scrollHeight;
}

// get the inital state of the chat (number of lines in chat log)
function getInitStateOfChat ()
{
  if(!instance)
  {
    instance = true;
    $.ajax({
      type: "POST",
      url: "service/chat.php",
      data: {  
          'function': 'getInitState',
          'file': file
      },
      dataType: "json",
      success: function(data){
        state = data.state;
        instance = false;
      },
    });
  }	 
}

// get the state of the chat (number of lines in chat log)
function getStateOfChat ()
{
  if(!instance)
  {
    instance = true;
    $.ajax({
      type: "POST",
      url: "service/chat.php",
      data: {  
          'function': 'getState',
          'file': file
      },
      dataType: "json",
      success: function(data){
        state = data.state;
        instance = false;
      },
    });
  }	 
}

// update the chat
function updateChat ()
{
  if (!instance)
  {
    instance = true;
    $.ajax({
      type: "POST",
      url: "service/chat.php",
      data: {  
        'function': 'update',
        'state': state,
        'file': file
      },
      dataType: "json",
      success: function(data){
        if (data.text)
        {
          var update = false;
          
          for (var i = 0; i < data.text.length; i++)
          {
            $('#chat-area').append($("<p>"+ data.text[i] +"</p>"));
            update = true;
          }
          
          if (update == true) 
          {
            openChat();
            audio.play();
          }							  
        }
        instance = false;
        state = data.state;
      },
    });
  }
  else setTimeout (updateChat, 1500);
}

// send the message
function sendChat (message, nickname)
{       
  updateChat();
  $.ajax({
    type: "POST",
    url: "service/chat.php",
    data: {  
        'function': 'send',
        'message': message,
        'nickname': nickname,
        'file': file
    },
    dataType: "json",
    success: function(data){
      updateChat();
    },
  });
}

// invite user to chat
function inviteUserToChat (user, by)
{
  updateChat();
  $.ajax({
    type: "POST",
    url: "service/chat.php",
    data: {  
        'function': 'invite',
        'message': user,
        'nickname': by,
        'file': file
    },
    dataType: "json",
    success: function(data){
      updateChat();
    },
  });
}

// check the chat for event
function checkChat ()
{
  if (!instance)
  {
    instance = true;
    $.ajax({
      type: "POST",
      url: "service/chat.php",
      data: {  
        'function': 'check',
        'state': state,
        'nickname': name,
        'file': file
      },
      dataType: "json",
      success: function(data){
        if (data.text)
        {
          var update = false;

          for (var i = 0; i < data.text.length; i++)
          {
            $('#chat-area').append($("<p>"+ data.text[i] +"</p>"));
            update = true;
          }
          
          if (update == true)
          {
            openChat();
          }	  
        }
        instance = false;
        state = data.state;
      },
    });
  }
  else setTimeout (checkChat, 1500);
}