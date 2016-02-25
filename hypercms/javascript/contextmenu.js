// temporary variables to hold mouse x-y position
var tempX = 0;
var tempY = 0;
var scrollX = 0;
var scrollY = 0;
var allow_tr_submit = true;

// sidebar
function hcms_loadSidebar()
{
  document.forms['contextmenu_object'].attributes['action'].value = 'explorer_preview.php';
  document.forms['contextmenu_object'].attributes['target'].value = 'sidebarFrame';
  document.forms['contextmenu_object'].elements['action'].value = '';
  
  if (allow_tr_submit)
  {
    document.forms['contextmenu_object'].submit();
  }
  else
  {
    allow_tr_submit = true;
  }
  
  return true;
}

// reset context menu  
function hcms_resetContext ()
{
  if (eval (document.forms['contextmenu_object']) && document.forms['contextmenu_object'].elements['contextmenustatus'].value == "hidden")
  {
    var contextmenu_form = document.forms['contextmenu_object'];
    
    contextmenu_form.elements['contexttype'].value = "none";
    contextmenu_form.elements['page'].value = "";
    contextmenu_form.elements['pagename'].value = "";
    contextmenu_form.elements['filetype'].value = "";
    contextmenu_form.elements['media'].value = "";
    contextmenu_form.elements['folder'].value = "";   
    if (eval (contextmenu_form.elements['folder_id'])) contextmenu_form.elements['folder_id'].value = "";
    if (eval (contextmenu_form.elements['memory'])) contextmenu_form.elements['token'].value = contextmenu_form.elements['memory'].value;
    else contextmenu_form.elements['token'].value = "";
  } 
  else if (eval (document.forms['contextmenu_user']) && document.forms['contextmenu_user'].elements['contextmenustatus'].value == "hidden")
  {
    var contextmenu_form = document.forms['contextmenu_user'];
    
    contextmenu_form .elements['login'].value = "";
    contextmenu_form .elements['token'].value = "";
  }
  else if (eval (document.forms['contextmenu_queue']) && document.forms['contextmenu_queue'].elements['contextmenustatus'].value == "hidden")
  {
    var contextmenu_form = document.forms['contextmenu_queue'];
    
    contextmenu_form.elements['site'].value = "";
    contextmenu_form.elements['cat'].value = "";
    contextmenu_form.elements['location'].value = "";
    contextmenu_form.elements['page'].value = "";
    contextmenu_form.elements['pagename'].value = "";
    contextmenu_form.elements['filetype'].value = "";
    contextmenu_form.elements['queueuser'].value = "";
    contextmenu_form.elements['queue_id'].value = ""; 
    contextmenu_form.elements['token'].value = "";
  }  
  
  return true;
} 

// lock/unlock context menu for writing  
function hcms_lockContext (status)
{
  if (status == "true" || status == true || status == "false" || status == false)
  {
    if (status == true) status = "true";
    if (status == false) status = "false";
    
    if (eval (document.forms['contextmenu_object'])) document.forms['contextmenu_object'].elements['contextmenulocked'].value = status;
    else if (eval (document.forms['contextmenu_user'])) document.forms['contextmenu_user'].elements['contextmenulocked'].value = status;
    else if (eval (document.forms['contextmenu_queue'])) document.forms['contextmenu_queue'].elements['contextmenulocked'].value = status;
  }

  return true;
}

// lock/unlock status of context menu  
function hcms_isLockedContext ()
{
  var status = "false";
  
  if (eval (document.forms['contextmenu_object'])) status = document.forms['contextmenu_object'].elements['contextmenulocked'].value;
  else if (eval (document.forms['contextmenu_user'])) status = document.forms['contextmenu_user'].elements['contextmenulocked'].value;
  else if (eval (document.forms['contextmenu_queue'])) status = document.forms['contextmenu_queue'].elements['contextmenulocked'].value;
  
  if (status == "true" || status == true) var result = true;
  else var result = false;

  return result;
}

// retrieve mouse x-y position
function hcms_getMouseXY (e) 
{
  if (!e) var e = window.event;
  
  if (e.clientX || e.clientY) 
  {
    // grab the x-y pos if browser is IE
    hcms_getScrollXY ();
    tempX = e.clientX + scrollX;
    tempY = e.clientY + scrollY;
  } 
  else if (e.pageX || e.pageY) 
  {
    // grab the x-y pos if browser is NS
    tempX = e.pageX;
    tempY = e.pageY;
  }  
  
  // catch possible negative values in NS4
  if (tempX < 0) tempX = 0;
  if (tempY < 0) tempY = 0; 
  
  return true;
}

// retrieve scrolling
function hcms_getScrollXY ()
{ 
  if (typeof(window.pageYOffset) == 'number')
  {
    // Netscape compliant
    scrollY = window.pageYOffset;
    scrollX = window.pageXOffset;
  }
  else if (document.body && (document.body.scrollLeft || document.body.scrollTop))
  {
    // DOM compliant
    scrollY = document.body.scrollTop;
    scrollX = document.body.scrollLeft;
  }
  else if (document.documentElement && (document.documentElement.scrollLeft || document.documentElement.scrollTop))
  {
    // IE6 standards compliant mode
    scrollY = document.documentElement.scrollTop;
    scrollX = document.documentElement.scrollLeft;
  }
  
  return true;
}

// retrieve browser window width
function hcms_getWindowWidth (win)
{ 
  if (win == undefined) win = window; 
  
  if (win.innerWidth)
  { 
    return win.innerWidth; 
  } 
  else
  { 
    if (win.document.documentElement && win.document.documentElement.clientWidth)
    { 
      return win.document.documentElement.clientWidth; 
    } 
    else return win.document.body.offsetWidth; 
  }
  
  return true;
} 

// retrieve browser window height 
function hcms_getWindowHeight (win)
{ 
  if (win == undefined) win = window; 
  
  if (win.innerHeight)
  { 
    return win.innerHeight; 
  } 
  else
  { 
    if (win.document.documentElement && win.document.documentElement.clientHeight)
    { 
      return win.document.documentElement.clientHeight; 
    } 
    else return win.document.body.offsetHeight; 
  }
  
  return true;
}

// position the contextmenu layer and make it visible
function hcms_positionContextmenu ()
{  
  if (document.all) 
  {   
    var contextelement = document.all['contextLayer'];
    
    if (tempY > hcms_getWindowHeight()/2) yoffset = parseInt(contextelement.style.height) + 0;
    else yoffset = 5;
    
    if (eval (contextxmove) && contextxmove == 1) contextelement.style.left = tempX - 5 + "px";
    if (eval (contextymove) && contextymove == 1) contextelement.style.top = tempY - yoffset + "px";   
    contextelement.style.visibility = 'visible';
  }
  else if (document.layers)
  {
    var contextelement = document.layers['contextLayer'];
    
    if (tempY > hcms_getWindowHeight()/2) yoffset = parseInt(contextelement.style.height) + 0;
    else yoffset = 5;
    
    if (eval (contextxmove) && contextxmove == 1) contextelement.left = tempX - 5 + "px";
    if (eval (contextymove) && contextymove == 1) contextelement.top = tempY - yoffset + "px";
    contextelement.visibility = 'visible';
  } 
  else 
  {
    var contextelement = document.getElementById('contextLayer'); 
    
    if (tempY > hcms_getWindowHeight()/2) yoffset = parseInt(contextelement.style.height) + 0;
    else yoffset = 5;
    
    if (eval (contextxmove) && contextxmove == 1) contextelement.style.left = tempX - 5 + "px";
    if (eval (contextymove) && contextymove == 1) contextelement.style.top = tempY - yoffset + "px";
    contextelement.style.visibility = 'visible';
  }    
  
  return true;
}
  
// set the icons of the contextmenu and call positioning
function hcms_showContextmenu ()
{
  if (eval (document.forms['contextmenu_object']))
  { 
    document.forms['contextmenu_object'].elements['contextmenustatus'].value = "visible";
    
    var contexttype = document.forms['contextmenu_object'].elements['contexttype'].value;
    var multiobject = document.forms['contextmenu_object'].elements['multiobject'].value;

    if (contextenable == 1)
    {
      if (contexttype == "object" || contexttype == "folder" || (multiobject != "" && contexttype == "media"))
      {
        if (eval (document.getElementById("img_preview"))) document.getElementById("img_preview").className = "hcmsIconOn";
        if (eval (document.getElementById("img_cmsview"))) document.getElementById("img_cmsview").className = "hcmsIconOn";
        if (eval (document.getElementById("img_notify"))) document.getElementById("img_notify").className = "hcmsIconOn";
        if (eval (document.getElementById("img_chat"))) document.getElementById("img_chat").className = "hcmsIconOn";
        if (eval (document.getElementById("img_delete"))) document.getElementById("img_delete").className = "hcmsIconOn";
        if (eval (document.getElementById("img_cut"))) document.getElementById("img_cut").className = "hcmsIconOn";
        if (eval (document.getElementById("img_copy"))) document.getElementById("img_copy").className = "hcmsIconOn";
        if (eval (document.getElementById("img_copylinked"))) document.getElementById("img_copylinked").className = "hcmsIconOn";
        if (eval (document.getElementById("img_publish"))) document.getElementById("img_publish").className = "hcmsIconOn";
        if (eval (document.getElementById("img_unpublish"))) document.getElementById("img_unpublish").className = "hcmsIconOn";
        if (eval (document.getElementById("img_unlock"))) document.getElementById("img_unlock").className = "hcmsIconOn";
        if (eval (document.getElementById("img_fav_delete"))) document.getElementById("img_fav_delete").className = "hcmsIconOn"; 
      }
      else if (multiobject == "" && contexttype == "media")
      {
        if (eval (document.getElementById("img_preview"))) document.getElementById("img_preview").className = "hcmsIconOn";
        if (eval (document.getElementById("img_cmsview"))) document.getElementById("img_cmsview").className = "hcmsIconOn";
        if (eval (document.getElementById("img_notify"))) document.getElementById("img_notify").className = "hcmsIconOn";
        if (eval (document.getElementById("img_chat"))) document.getElementById("img_chat").className = "hcmsIconOn";
        if (eval (document.getElementById("img_delete"))) document.getElementById("img_delete").className = "hcmsIconOn";   
        if (eval (document.getElementById("img_cut"))) document.getElementById("img_cut").className = "hcmsIconOn";
        if (eval (document.getElementById("img_copy"))) document.getElementById("img_copy").className = "hcmsIconOn";      
        if (eval (document.getElementById("img_copylinked"))) document.getElementById("img_copylinked").className = "hcmsIconOn";
        if (eval (document.getElementById("img_publish"))) document.getElementById("img_publish").className = "hcmsIconOn";
        if (eval (document.getElementById("img_unpublish"))) document.getElementById("img_unpublish").className = "hcmsIconOn";
        if (eval (document.getElementById("img_unlock"))) document.getElementById("img_unlock").className = "hcmsIconOn";
        if (eval (document.getElementById("img_fav_delete"))) document.getElementById("img_fav_delete").className = "hcmsIconOn";  
      }
      else
      {  
        if (eval (document.getElementById("img_preview"))) document.getElementById("img_preview").className = "hcmsIconOff";
        if (eval (document.getElementById("img_cmsview"))) document.getElementById("img_cmsview").className = "hcmsIconOff";
        if (eval (document.getElementById("img_notify"))) document.getElementById("img_notify").className = "hcmsIconOff";
        if (eval (document.getElementById("img_chat"))) document.getElementById("img_chat").className = "hcmsIconOff";
        if (eval (document.getElementById("img_delete"))) document.getElementById("img_delete").className = "hcmsIconOff";   
        if (eval (document.getElementById("img_cut"))) document.getElementById("img_cut").className = "hcmsIconOff";
        if (eval (document.getElementById("img_copy"))) document.getElementById("img_copy").className = "hcmsIconOff";      
        if (eval (document.getElementById("img_copylinked"))) document.getElementById("img_copylinked").className = "hcmsIconOff";
        if (eval (document.getElementById("img_publish"))) document.getElementById("img_publish").className = "hcmsIconOff";
        if (eval (document.getElementById("img_unpublish"))) document.getElementById("img_unpublish").className = "hcmsIconOff";
        if (eval (document.getElementById("img_unlock"))) document.getElementById("img_unlock").className = "hcmsIconOff";
        if (eval (document.getElementById("img_fav_delete"))) document.getElementById("img_fav_delete").className = "hcmsIconOff";
      }
    }
  }
  else if (eval (document.forms['contextmenu_user']))
  {
    document.forms['contextmenu_user'].elements['contextmenustatus'].value = "visible";
    
    var multiobject = document.forms['contextmenu_user'].elements['multiobject'].value;
    var login = document.forms['contextmenu_user'].elements['login'].value;

    if (login != "")
    {
      if (eval (document.getElementById("img_edit"))) document.getElementById("img_edit").className = "hcmsIconOn";
      if (eval (document.getElementById("img_delete"))) document.getElementById("img_delete").className = "hcmsIconOn";
    }    
    else
    {
      if (eval (document.getElementById("img_edit"))) document.getElementById("img_edit").className = "hcmsIconOff";
      if (eval (document.getElementById("img_delete"))) document.getElementById("img_delete").className = "hcmsIconOff";
    }  
  }
  else if (eval (document.forms['contextmenu_queue']))
  {
    document.forms['contextmenu_queue'].elements['contextmenustatus'].value = "visible";
    
    var multiobject = document.forms['contextmenu_queue'].elements['multiobject'].value;
    var queue_id = document.forms['contextmenu_queue'].elements['queue_id'].value;

    if (queue_id != "")
    {
      if (eval (document.getElementById("img_edit"))) document.getElementById("img_edit").className = "hcmsIconOn";
      if (eval (document.getElementById("img_delete"))) document.getElementById("img_delete").className = "hcmsIconOn";
    }    
    else
    {
      if (eval (document.getElementById("img_edit"))) document.getElementById("img_edit").className = "hcmsIconOff";
      if (eval (document.getElementById("img_delete"))) document.getElementById("img_delete").className = "hcmsIconOff";
    }
  }
  
  hcms_positionContextmenu ();    

  return true;
} 

function hcms_hideContextmenu ()
{
  if (eval (document.forms['contextmenu_object'])) document.forms['contextmenu_object'].elements['contextmenustatus'].value = "hidden";
  if (eval (document.forms['contextmenu_user'])) document.forms['contextmenu_user'].elements['contextmenustatus'].value = "hidden";
  if (eval (document.forms['contextmenu_queue'])) document.forms['contextmenu_queue'].elements['contextmenustatus'].value = "hidden";
  
  if (document.all) 
  {
    document.all['contextLayer'].style.visibility = 'hidden';
  }
  else if (document.layers)
  {
    document.layers['contextLayer'].visibility = 'hidden';  
  }
  else
  {
    contextelement = document.getElementById('contextLayer');
    contextelement.style.visibility = 'hidden';
  }  
  
  return true;
}

function hcms_submitWindow(formName, features, width, height)
{
  winName = 'popup' + Math.floor(Math.random()*1000);
  document.forms[formName].target = winName;
  hcms_openWindow('', winName, features, width, height);
  document.forms[formName].submit();
  
  return true;
}

function hcms_createContextmenuItem (action)
{
  // lock
  hcms_lockContext ('true');
  
  if (eval (document.forms['contextmenu_object']))
  {
    var contexttype = document.forms['contextmenu_object'].elements['contexttype'].value;
    var site = document.forms['contextmenu_object'].elements['site'].value;
    var cat = document.forms['contextmenu_object'].elements['cat'].value;
    var location = document.forms['contextmenu_object'].elements['location'].value;
    var page = document.forms['contextmenu_object'].elements['page'].value;
    var pagename = document.forms['contextmenu_object'].elements['pagename'].value;
    var filetype = document.forms['contextmenu_object'].elements['filetype'].value;
    var media = document.forms['contextmenu_object'].elements['media'].value;
    var folder = document.forms['contextmenu_object'].elements['folder'].value;
    var multiobject = document.forms['contextmenu_object'].elements['multiobject'].value;
    var token = document.forms['contextmenu_object'].elements['token'].value;
    
    if (contexttype == "object" || contexttype == "media" || contexttype == "folder" || contexttype == "none")
    {	
      if (action == "preview" || action == "cmsview" || action == "chat")
      {
        if (contexttype == "object" || contexttype == "media")
        {
          URLparaView = 'site=' + site + '&cat=' + cat + '&location=' + location + '&page=' + page + '&token=' + token;
        }
        else if (contexttype == "folder")
        {
          URLparaView = 'site=' + site + '&cat=' + cat + '&location=' + location + folder + '/&folder=' + folder + '&page=' + page + '&token=' + token;
        }
      }
      
      if (action == "preview")
      {
        hcms_openWindow('page_preview.php?' + URLparaView,'preview','scrollbars=yes,resizable=yes','800','600');
      }
      else if (action == "cmsview" && multiobject.split("|").length > 2 && parent && parent.frames && parent.frames['controlFrame'] && parent.frames['controlFrame'].submitToWindow)
      {
        parent.frames['controlFrame'].submitToWindow('page_multiedit.php','', 'multiedit','status=yes,scrollbars=yes,resizable=yes','800','600');
      }
      else if (action == "cmsview")
      {
        hcms_openWindow('frameset_content.php?ctrlreload=yes&' + URLparaView,'','status=yes,scrollbars=no,resizable=yes','800','600');
      }
      else if (action == "notify")
      {
        URLfile = "popup_notify.php";
          
        document.forms['contextmenu_object'].attributes['action'].value = URLfile;
        hcms_submitWindow('contextmenu_object', 'status=no,scrollbars=no,resizable=no','560','420');
      }
      else if (action == "chat")
      {
        var chatcontent = "hcms_openWindow('frameset_content.php?ctrlreload=yes&" + URLparaView + "','','status=yes,scrollbars=no,resizable=yes','800','600');";
        
        sendtochat (chatcontent);
      }
      else if (action == "delete")
      {
        check = confirm_delete ();
      
        if (check == true)
        {
          if (multiobject == "" && (contexttype == "object" || contexttype == "media")) URLfile = "popup_action.php";
          else URLfile = "popup_status.php";
            
          document.forms['contextmenu_object'].attributes['action'].value = URLfile;
          document.forms['contextmenu_object'].elements['action'].value = action;
          document.forms['contextmenu_object'].elements['force'].value = 'start';
          hcms_submitWindow('contextmenu_object', 'status=no,scrollbars=no,resizable=no,width=400,height=120','400','120');
        } 
      }  
      else if (action == "cut" || action == "copy" || action == "linkcopy")
      {
        document.forms['contextmenu_object'].attributes['action'].value = 'popup_action.php';
        document.forms['contextmenu_object'].elements['action'].value = action;
        hcms_submitWindow('contextmenu_object', 'status=no,scrollbars=no,resizable=no,width=400,height=120','400','120');
      }  
      else if (action == "paste")
      {
        if (site != "" && location != "") hcms_openWindow('popup_status.php?force=start&action=paste&site=' + site + '&cat=' + cat + '&location=' + location + '&token=' + token,'','status=no,scrollbars=no,resizable=no','400','120');    
      }  
      else if (action == "publish" || action == "unpublish")
      {
        URLfile = "popup_publish.php";
          
        document.forms['contextmenu_object'].attributes['action'].value = URLfile;
        document.forms['contextmenu_object'].elements['action'].value = action;
        document.forms['contextmenu_object'].elements['force'].value = 'start';
        hcms_submitWindow('contextmenu_object', 'status=no,scrollbars=no,resizable=no','400','370');
      }
      else if (action == "favorites_delete")
      {
        document.forms['contextmenu_object'].attributes['action'].value = "popup_action.php";
        document.forms['contextmenu_object'].elements['action'].value = "page_favorites_delete";
        hcms_submitWindow('contextmenu_object', 'status=no,scrollbars=no,resizable=no,width=400,height=120','400','120');
        allow_tr_submit = false;
      }
      else if (action == "checkin")
      {
        document.forms['contextmenu_object'].attributes['action'].value = "popup_action.php";
        document.forms['contextmenu_object'].elements['action'].value = "page_unlock";
        hcms_submitWindow('contextmenu_object', 'status=no,scrollbars=no,resizable=no,width=400,height=120','400','120');
        allow_tr_submit = false;
      }
    }  
  }
  else if (eval (document.forms['contextmenu_user']))
  {
    var site = document.forms['contextmenu_user'].elements['site'].value;
    var group = document.forms['contextmenu_user'].elements['group'].value;
    var login = document.forms['contextmenu_user'].elements['login'].value;
    var multiobject = document.forms['contextmenu_user'].multiobject.value;
    var token = document.forms['contextmenu_user'].elements['token'].value;
    
    if (action == "edit")
    {
      hcms_openWindow('user_edit.php?site=' + site + '&group=' + group + '&login=' + login + '&token=' + token,'edit','status=yes,scrollbars=no,resizable=yes','500','540');
    }
    else if (action == "delete")
    {
      check = confirm_delete ();
    
      if (check == true)
      {
        document.forms['contextmenu_user'].attributes['action'].value = "control_user_menu.php";
        document.forms['contextmenu_user'].attributes['target'].value = "controlFrame";
        document.forms['contextmenu_user'].elements['action'].value = action;
        document.forms['contextmenu_user'].submit();
        allow_tr_submit = false;
      } 
    }  
  }
  else if (eval (document.forms['contextmenu_queue']))
  {
    var site = document.forms['contextmenu_queue'].elements['site'].value;
    var cat = document.forms['contextmenu_queue'].elements['cat'].value;
    var location = document.forms['contextmenu_queue'].elements['location'].value;
    var page = document.forms['contextmenu_queue'].elements['page'].value;
    var pagename = document.forms['contextmenu_queue'].elements['pagename'].value;
    var filetype = document.forms['contextmenu_queue'].elements['filetype'].value;
    var queueuser = document.forms['contextmenu_queue'].elements['queueuser'].value;
    var queue_id = document.forms['contextmenu_queue'].elements['queue_id'].value;
    var multiobject = document.forms['contextmenu_queue'].elements['multiobject'].value;
    var token = document.forms['contextmenu_queue'].elements['token'].value;
    
    if (action == "edit")
    {
      hcms_openWindow('frameset_content.php?site=' + site + '&ctrlreload=yes&cat=' + cat + '&location=' + location + '&page=' + page + '&queueuser=' + queueuser + '&queue_id=' + queue_id + '&token=' + token, '', 'status=yes,scrollbars=no,resizable=yes', '800', '600');
    }
    else if (action == "delete")
    {
      check = confirm_delete ();
    
      if (check == true)
      {
        document.forms['contextmenu_queue'].attributes['action'].value = "control_queue_menu.php";
        document.forms['contextmenu_queue'].attributes['target'].value = "controlFrame";
        document.forms['contextmenu_queue'].elements['action'].value = action;
        document.forms['contextmenu_queue'].submit();
        allow_tr_submit = false;
      } 
    }  
  }
  
  // unlock
  hcms_lockContext ('false');
  
  return true;
}

function hcms_setObjectcontext(site, cat, location, page, pagename, filetype, media, folder, folder_id, token)
{
  if (eval (document.forms['contextmenu_object']) && hcms_isLockedContext() == false)
  {
    // hide and reset context menu
    hcms_hideContextmenu();
  
    // set values 
    var contexttype;
    
    if (folder != "") contexttype = "folder";
    else if (media != "") contexttype = "media";
    else if (page != "") contexttype = "object";
    else contexttype = "none";
    
    var contextmenu_form = document.forms['contextmenu_object'];

    contextmenu_form.elements['contexttype'].value = contexttype;
    contextmenu_form.elements['xpos'].value = tempX;
    contextmenu_form.elements['ypos'].value = tempY;
    contextmenu_form.elements['site'].value = site;
    contextmenu_form.elements['cat'].value = cat;
    contextmenu_form.elements['location'].value = location;
    contextmenu_form.elements['page'].value = page;
    contextmenu_form.elements['pagename'].value = pagename;
    contextmenu_form.elements['filetype'].value = filetype;
    contextmenu_form.elements['media'].value = media;
    contextmenu_form.elements['folder'].value = folder;   
    if (eval (contextmenu_form.elements['folder_id'])) contextmenu_form.elements['folder_id'].value = folder_id;
    contextmenu_form.elements['token'].value = token;
  }
  
  return true;
}

function hcms_setUsercontext(site, login, token)
{
  if (eval (document.forms['contextmenu_user']) && hcms_isLockedContext() == false)
  {
    // hide and reset context menu
    hcms_hideContextmenu();
    
    var contextmenu_form = document.forms['contextmenu_user'];
  
    // set values   
    contextmenu_form.elements['xpos'].value = tempX;
    contextmenu_form.elements['ypos'].value = tempY;
    contextmenu_form.elements['site'].value = site;
    contextmenu_form.elements['login'].value = login;
    contextmenu_form.elements['token'].value = token;
  }
  
  return true;
}

function hcms_setQueuecontext(site, cat, location, page, pagename, filetype, queueuser, queue_id, token)
{
  if (eval (document.forms['contextmenu_queue']) && hcms_isLockedContext() == false)
  {
    // hide and reset context menu
    hcms_hideContextmenu();
  
    var contextmenu_form = document.forms['contextmenu_queue'];
    
    // set values   
    contextmenu_form.elements['xpos'].value = tempX;
    contextmenu_form.elements['ypos'].value = tempY;
    contextmenu_form.elements['site'].value = site;
    contextmenu_form.elements['cat'].value = cat;
    contextmenu_form.elements['location'].value = location;
    contextmenu_form.elements['page'].value = page;
    contextmenu_form.elements['pagename'].value = pagename;
    contextmenu_form.elements['filetype'].value = filetype;
    contextmenu_form.elements['queueuser'].value = queueuser;   
    contextmenu_form.elements['queue_id'].value = queue_id;
    contextmenu_form.elements['token'].value = token;
  }
  
  return true;
}

// replace string
function hcms_replace (string, text, by) 
{
  // Replaces text with by in string
  var strLength = string.length, txtLength = text.length;
  if ((strLength == 0) || (txtLength == 0)) return string;

  var i = string.indexOf(text);
  if ((!i) && (text != string.substring(0,txtLength))) return string;
  if (i == -1) return string;

  var newstr = string.substring(0,i) + by;

  if (i+txtLength < strLength)
      newstr += hcms_replace(string.substring(i+txtLength,strLength),text,by);

  return newstr;
}

function hcms_endsWith(str, suffix) {
    return str.indexOf(suffix, str.length - suffix.length) !== -1;
}

// select multiple objects
function hcms_selectObject (row_id, event)
{
  var contextmenu_form = false;

  if (eval (document.forms['contextmenu_object']))
  {
    contextmenu_form = document.forms['contextmenu_object'];
  }
  else if (eval (document.forms['contextmenu_user']))
  {
    contextmenu_form = document.forms['contextmenu_user'];
  }
  else if (eval (document.forms['contextmenu_queue']))
  {
    contextmenu_form = document.forms['contextmenu_queue'];
  }
  
  // no contextmenu to use
  if (contextmenu_form == false)
  {
    return false;
  }   
 
  // reset object list if multiobject is empty
  if (contextmenu_form.elements['multiobject'].value == "")
  {
    if (eval (document.getElementById('objectlist')))
    {
      var table = document.getElementById('objectlist');   
      var tablerows = table.getElementsByTagName("tr");
      
      for (i = 0; i < tablerows.length; i++)
      {           
        tablerows[i].className = "hcmsObjectUnselected";
      }
    }
    
    if (eval (document.getElementById('objectgallery')))
    {
      var table = document.getElementById('objectgallery');   
      var tabledata = table.getElementsByTagName("td");
      
      for (i = 0; i < tabledata.length; i++)
      {           
        tabledata[i].className = "hcmsObjectUnselected";     
      }  
    }
  }
  
  var multiobject_str = contextmenu_form.elements['multiobject'].value;
  var multiobject_str2 = multiobject_str + '|';
  
  // if ctrl-key is pressed
  if (hcms_keyPressed('ctrl', event)==true)
  {
    var td = document.getElementById('h' + row_id + '_0');
    var inputs = td.getElementsByTagName('input'); 
    var object = inputs[0].value;

    if (multiobject_str == '' || multiobject_str2.indexOf ('|'+object+'|') == -1 )
    {
      contextmenu_form.elements['multiobject'].value = multiobject_str + '|' + object;
      document.getElementById('g' + row_id).className='hcmsObjectSelected';
      if (eval (document.getElementById('objectgallery'))) document.getElementById('t' + row_id).className='hcmsObjectSelected';
      return true;
    }
    else if (multiobject_str != '')
    {
      if (hcms_endsWith(multiobject_str, '|'+object))
      {
        contextmenu_form.elements['multiobject'].value = hcms_replace (multiobject_str, '|'+object, '');
      }
      else
      {
        contextmenu_form.elements['multiobject'].value = hcms_replace (multiobject_str, '|'+object+'|', '|');
      }
      
      document.getElementById('g' + row_id).className='hcmsObjectUnselected';
      if (eval (document.getElementById('objectgallery'))) document.getElementById('t' + row_id).className='hcmsObjectUnselected';
      return true;
    }
    else return false; 
  }
  // if shift-key is pressed
  else if (hcms_keyPressed('shift', event)==true)
  {
    var td = document.getElementById('h' + row_id + '_0');    
    var inputs = td.getElementsByTagName('input'); 
    var object = inputs[0].value;    
    
    if (multiobject_str == '')
    {
      contextmenu_form.elements['multiobject'].value = multiobject_str + '|' + object;
      document.getElementById('g' + row_id).className='hcmsObjectSelected';
      if (eval (document.getElementById('objectgallery'))) document.getElementById('t' + row_id).className='hcmsObjectSelected';
      return true;    
    }
    else if (multiobject_str != '')
    {
      var lastselection = multiobject_str.substring (multiobject_str.lastIndexOf ('|') + 1);
      var currentselection = object;        
      var table = document.getElementById('objectlist');   
      var rows = table.getElementsByTagName("tr");
      var row_id = 0;
      var startselect = false;
      var stopselect = false;
      var topdown = '';

      for (i = 0; i < rows.length; i++)
      {
        var input = rows[i].getElementsByTagName('input');  
        object = input[0].value;  
        
        if (topdown == '1' && startselect == true && stopselect == false && multiobject_str2.indexOf ('|'+object+'|') == -1)
        { 
          multiobject_str = multiobject_str + '|' + object;
          row_id = rows[i].id;
          row_id = row_id.substr(1);
          rows[i].className='hcmsObjectSelected';
          if (eval (document.getElementById('objectgallery'))) document.getElementById('t' + row_id).className='hcmsObjectSelected';
        }
              
        if (object == lastselection)
        {
          if (topdown == '') topdown = '1';
          if (startselect == false) startselect = true;
          else if (startselect == true) stopselect = true;
        }
        else if (object == currentselection)
        { 
          if (topdown == '') topdown = '0';
          if (startselect == false) startselect = true;
          else if (startselect == true) stopselect = true;  
        }
        
        if (topdown == '0' && startselect == true && stopselect == false && multiobject_str2.indexOf ('|'+object+'|') == -1)
        { 
          multiobject_str = multiobject_str + '|' + object;
          row_id = rows[i].id;
          row_id = row_id.substr(1);
          rows[i].className='hcmsObjectSelected';
          if (eval (document.getElementById('objectgallery'))) document.getElementById('t' + row_id).className='hcmsObjectSelected';
        }          
      }
      
      contextmenu_form.elements['multiobject'].value =  multiobject_str; 
      return true;
    }
  }
  // if no key is pressed
  else
  {
    var td = document.getElementById('h' + row_id + '_0');
    var inputs = td.getElementsByTagName('input'); 
    var object = inputs[0].value;

    if (multiobject_str == '' || multiobject_str2.indexOf ('|'+object+'|') == -1 )
    {
      contextmenu_form.elements['multiobject'].value = multiobject_str + '|' + object;
      document.getElementById('g' + row_id).className='hcmsObjectSelected';
      if (eval (document.getElementById('objectgallery'))) document.getElementById('t' + row_id).className='hcmsObjectSelected';
      return true;
    }
    else return false; 
  }
}

function hcms_updateControlObjectListMenu()
{
  document.forms['contextmenu_object'].attributes['action'].value = 'control_objectlist_menu.php';
  document.forms['contextmenu_object'].attributes['target'].value = 'controlFrame';
  document.forms['contextmenu_object'].elements['action'].value = '';
  
  if (allow_tr_submit)
  {
    document.forms['contextmenu_object'].submit();
  }
  else
  {
    allow_tr_submit = true;
  }
}

function hcms_updateControlUserMenu()
{	
  document.forms['contextmenu_user'].attributes['action'].value = 'control_user_menu.php';
  document.forms['contextmenu_user'].attributes['target'].value = 'controlFrame';
  document.forms['contextmenu_user'].elements['action'].value = '';
  
  if (allow_tr_submit)
  {
    document.forms['contextmenu_user'].submit();
  }
  else
  {
    allow_tr_submit = true;
  }
}

function hcms_updateControlQueueMenu()
{
  document.forms['contextmenu_queue'].attributes['action'].value = 'control_queue_menu.php';
  document.forms['contextmenu_queue'].attributes['target'].value = 'controlFrame';
  document.forms['contextmenu_queue'].elements['action'].value = '';
   
  if (allow_tr_submit)
  {
    document.forms['contextmenu_queue'].submit();
  }
  else
  {
    allow_tr_submit = true;
  }
}

// unselect all objects
function hcms_unselectAll()
{ 
 if (document.getElementsByTagName)
 {  
   if (eval (document.getElementById('objectlist')))
   {
     var table = document.getElementById('objectlist');   
     var tablerows = table.getElementsByTagName("tr");
      
     for (i = 0; i < tablerows.length; i++)
     {           
       tablerows[i].className = "hcmsObjectUnselected";
     }
   }
   
   if (eval (document.getElementById('objectgallery')))
   {
     var table = document.getElementById('objectgallery');   
     var tabledata = table.getElementsByTagName("td");
      
     for (i = 0; i < tabledata.length; i++)
     {           
       tabledata[i].className = "hcmsObjectUnselected";     
     }  
   } 
   
   if (eval (document.forms['contextmenu_object']))
   {
     if (document.forms['contextmenu_object'].elements['multiobject'].value)
     {
       document.forms['contextmenu_object'].elements['multiobject'].value = '';
       hcms_updateControlObjectListMenu();
     }
   }
   else if (eval (document.forms['contextmenu_user']))
   {
     if (document.forms['contextmenu_user'].elements['multiobject'].value)
     {
       document.forms['contextmenu_user'].elements['multiobject'].value = '';
       hcms_updateControlUserMenu();
     }
   }
   else if (eval (document.forms['contextmenu_queue']))
   {
     if (document.forms['contextmenu_queue'].elements['multiobject'].value)
     {
       document.forms['contextmenu_queue'].elements['multiobject'].value = '';
       hcms_updateControlQueueMenu();
     }
   }
   
   return true; 
 } 
} 

function hcms_Contextmenu(e) 
{
  if (!e) var e = window.event;

  // if alt-key is pressed
  if (activatelinks == true) return true;
  else return false;
}

function hcms_rightClick(e) 
{
  if (!e) var e = window.event;
  
  // if alt-key is not pressed
  if (activatelinks == false)
  {
    // right mouse click
    if (e.which == 2 || e.which == 3) 
    {
      hcms_showContextmenu();
    }
    else if (e.button == 2 || e.button == 3) 
    {
      hcms_showContextmenu();
    }
  }
  
  return true;
}

function hcms_leftClick(e) 
{
  if (!e) var e = window.event;
  var multiobject;
  var objectcount=0;

  if (eval (document.forms['contextmenu_object']))
  {
    multiobject = document.forms['contextmenu_object'].elements['multiobject'].value;
  }
  else if (eval (document.forms['contextmenu_user']))
  {
    multiobject = document.forms['contextmenu_user'].elements['multiobject'].value;
  }
  else if (eval (document.forms['contextmenu_queue']))
  {
    multiobject = document.forms['contextmenu_queue'].elements['multiobject'].value;
  }
  
  // count object stored in multiobject
  if (multiobject != "") objectcount = multiobject.split("|").length - 1;

  // left mouse click
  if (e.which == 0 || e.which == 1) 
  {
    hcms_hideContextmenu();
    
    // if no key is pressed and multiobject stores more than 1 object
    if (hcms_keyPressed('', e) == false && objectcount > 1)
    {
      hcms_unselectAll();
      hcms_resetContext();
    }
  }
  else if (e.button == 0 || e.button == 1) 
  {
    hcms_hideContextmenu();
    
    // if no key is pressed and multiobject stores more than 1 object
    if (hcms_keyPressed('', e) == false && objectcount > 1)
    {
      hcms_unselectAll();
      hcms_resetContext();
    }
  }

  return true;
} 

function hcms_keyPressed(key, e)
{
 var ctrlPressed=0;
 var altPressed=0;
 var shiftPressed=0;

 if (parseInt(navigator.appVersion)>3)
 {
  var evt = navigator.appName=="Netscape" ? e:event;

  if (navigator.appName=="Netscape" && parseInt(navigator.appVersion)==4)
  {
   // NETSCAPE 4 CODE
   var mString =(e.modifiers+32).toString(2).substring(3,6);
   shiftPressed=(mString.charAt(0)=="1");
   ctrlPressed =(mString.charAt(1)=="1");
   altPressed  =(mString.charAt(2)=="1");
  }
  else
  {
   // NEWER BROWSERS [CROSS-PLATFORM]
   shiftPressed=evt.shiftKey;
   altPressed  =evt.altKey;
   ctrlPressed =evt.ctrlKey;
  }
  
  if (key == 'ctrl' && ctrlPressed) return true;
  else if (key == 'shift' && shiftPressed) return true;
  else if (key == 'alt' && altPressed) return true;
  else if (key == '' && (altPressed || shiftPressed || ctrlPressed)) return true;
  else return false;
 }
 
 return true;
}

function hcms_activateLinks(e) 
{
  if (!e) var e = window.event;

  // activate or deactivate links
  if (hcms_keyPressed('alt', e))
  {
    var links = document.getElementsByTagName('a');
    var hashlink = false;

    for (var i = 0; i < links.length; i++)
    {
      var thisLink = links[i];
      var href = thisLink.getAttribute('data-href');

      if (thisLink.getAttribute('data-linktype') == 'hash')
      {
        // activate link
        if (activatelinks == false)
        {
          var href = thisLink.getAttribute('data-href');
          var href_attr = document.createAttribute('href');
          href_attr.nodeValue = href;
          thisLink.setAttributeNode(href_attr);
        }
        // deactivate link
        else
        {
          var hrefnode = thisLink.getAttributeNode('href');
          thisLink.removeAttributeNode(hrefnode);
        }
        
        // are download links present
        hashlink = true;
      }
    }

    // set activate state
    if (hashlink == true)
    {
      if (activatelinks == false)
      {
        document.getElementsByTagName('body')[0].className = 'hcmsWorkplaceObjectlistLinks';
        activatelinks = true;
      }
      // set deactivate state
      else
      {
        document.getElementsByTagName('body')[0].className = 'hcmsWorkplaceObjectlist';
        activatelinks = false;
      }
    }
  }
}

// initialize
var activatelinks = false;

document.onkeydown = hcms_activateLinks;
document.onmousemove = hcms_getMouseXY;
document.oncontextmenu = hcms_Contextmenu;
document.onmousedown = hcms_rightClick;
document.onclick = hcms_leftClick;
