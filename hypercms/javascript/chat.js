var chat_instance = false;
var chat_service = "service/chat.php";
var chat_audio = "javascript/ding.mp3";
var chat_state;
var chat_file;

function Chat ()
{
  this.update = updateChat;
  this.send = sendChat;
  this.getState = getStateOfChat;
  this.getInitState = getInitStateOfChat;
  this.inviteUser = inviteUserToChat;
  this.uninviteUsers = uninviteUsersOf;
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
  if (!chat_instance)
  {
    chat_instance = true;
    $.ajax({
      type: "POST",
      url: chat_service,
      data: {  
          'function': 'getInitState',
          'file': chat_file
      },
      dataType: "json",
      success: function(data){
        chat_state = data.state;
        chat_instance = false;
      },
    });
  }	 
}

// get the state of the chat (number of lines in chat log)
function getStateOfChat ()
{
  if (!chat_instance)
  {
    chat_instance = true;
    $.ajax({
      type: "POST",
      url: chat_service,
      data: {  
          'function': 'getState',
          'file': chat_file
      },
      dataType: "json",
      success: function(data){
        chat_state = data.state;
        chat_instance = false;
      },
    });
  }	 
}

// update the chat
function updateChat ()
{
  if (!chat_instance)
  {
    chat_instance = true;
    $.ajax({
      type: "POST",
      url: chat_service,
      data: {  
        'function': 'update',
        'state': chat_state,
        'file': chat_file
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
            chat_audio.play();
          }							  
        }
        chat_instance = false;
        chat_state = data.state;
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
    url: chat_service,
    data: {  
        'function': 'send',
        'message': message,
        'nickname': nickname,
        'file': chat_file
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
    url: chat_service,
    data: {  
        'function': 'invite',
        'message': user,
        'nickname': by,
        'file': chat_file
    },
    dataType: "json",
    success: function(data){
      updateChat();
    },
  });
}

// uninvite users
function uninviteUsersOf (user)
{
  updateChat();
  $.ajax({
    type: "POST",
    url: chat_service,
    data: {  
        'function': 'uninvite',
        'message': user,
        'nickname': user,
        'file': chat_file
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
  if (!chat_instance)
  {
    chat_instance = true;
    $.ajax({
      type: "POST",
      url: chat_service,
      data: {  
        'function': 'check',
        'state': chat_state,
        'nickname': name,
        'file': chat_file
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
        chat_instance = false;
        chat_state = data.state;
      },
    });
  }
  else setTimeout (checkChat, 1500);
}