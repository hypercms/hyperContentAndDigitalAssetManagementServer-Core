// ------------------------ default values ----------------------------

// mobile browser
if (localStorage.getItem('is_mobile') !== null && localStorage.getItem('is_mobile') == 'false')
{
  var is_mobile = false;
}
else
{
  var is_mobile = true;
}

// general context menu options
var contextenable = true;

// contect menu move options
var contextxmove = true;
var contextymove = true;

// temporary variables to hold mouse x-y position
var tempX = 0;
var tempY = 0;
var scrollX = 0;
var scrollY = 0;
var allow_tr_submit = true;

// activate links
var activatelinks = false;

// select area
var selectarea = null;
var x1 = 0;
var y1 = 0;
var x2 = 0;
var y2 = 0;
var x3 = 0;
var y3 = 0;
var x4 = 0;
var y4 = 0;

// drag and drop
var dragndrop = false;

// enable/disable specific functions
var permission = new Array();
permission['rename'] = true;
permission['paste'] = true;
permission['delete'] = true;
permission['publish'] = true;

// design theme
var themelocation = '';

// ------------------------ contextmenu ----------------------------

// remove selection marks of browser
function hcms_clearSelection ()
{
  // only used if a selectarea element exists (otherwise focus on input fields will be lost)
  if (selectarea && activatelinks == false)
  {
    if (window.getSelection)
    {
      window.getSelection().removeAllRanges();
    }
    else if (document.selection)
    {
      document.selection.empty();
    }
  }
}

// sidebar
function hcms_loadSidebar ()
{
  // if sidebar is not hidden
  if (parent.document.getElementById('sidebarLayer') && parent.document.getElementById('sidebarLayer').style.width != "0px" && document.forms['contextmenu_object'])
  {
    // wait (due to issues with browsers like MS Edge, Chrome)
    hcms_sleep (400);

    var location = document.forms['contextmenu_object'].elements['location'].value;
    var folder = document.forms['contextmenu_object'].elements['folder'].value;
    var page = document.forms['contextmenu_object'].elements['page'].value;
    
    if (allow_tr_submit && location != '' && (folder != '' || page != ''))
    {
      parent.document.getElementById('sidebarFrame').src='explorer_preview.php?location=' + encodeURIComponent(location) + '&folder=' +  encodeURIComponent(folder) + '&page=' + encodeURIComponent(page);
    }
    else
    {
      allow_tr_submit = true;
    }
    
    return true;
  }
  else return false;
}

// reset context menu  
function hcms_resetContext ()
{
  // Object
  if (document.forms['contextmenu_object'] && document.forms['contextmenu_object'].elements['contextmenustatus'].value == "hidden")
  {
    var contextmenu_form = document.forms['contextmenu_object'];
    
    contextmenu_form.elements['contexttype'].value = "none";
    contextmenu_form.elements['page'].value = "";
    contextmenu_form.elements['pagename'].value = "";
    contextmenu_form.elements['filetype'].value = "";
    contextmenu_form.elements['media'].value = "";
    contextmenu_form.elements['folder'].value = "";   
    if (contextmenu_form.elements['folder_id']) contextmenu_form.elements['folder_id'].value = "";
  }
  // User
  else if (document.forms['contextmenu_user'] && document.forms['contextmenu_user'].elements['contextmenustatus'].value == "hidden")
  {
    var contextmenu_form = document.forms['contextmenu_user'];
    
    contextmenu_form .elements['login'].value = "";
  }
  // Queue
  else if (document.forms['contextmenu_queue'] && document.forms['contextmenu_queue'].elements['contextmenustatus'].value == "hidden")
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
    
    if (document.forms['contextmenu_object']) document.forms['contextmenu_object'].elements['contextmenulocked'].value = status;
    else if (document.forms['contextmenu_user']) document.forms['contextmenu_user'].elements['contextmenulocked'].value = status;
    else if (document.forms['contextmenu_queue']) document.forms['contextmenu_queue'].elements['contextmenulocked'].value = status;
  }

  return true;
}

// lock/unlock status of context menu  
function hcms_isLockedContext ()
{
  var status = "false";
  
  if (document.forms['contextmenu_object']) status = document.forms['contextmenu_object'].elements['contextmenulocked'].value;
  else if (document.forms['contextmenu_user']) status = document.forms['contextmenu_user'].elements['contextmenulocked'].value;
  else if (document.forms['contextmenu_queue']) status = document.forms['contextmenu_queue'].elements['contextmenulocked'].value;
  
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
    
    // select area
    x2 = e.clientX;
    y2 = e.clientY;
  } 
  else if (e.pageX || e.pageY) 
  {
    // grab the x-y pos if browser is NS
    tempX = e.pageX;
    tempY = e.pageY;
    
    // select area
    x2 = e.pageX;
    y2 = e.pageY;
  }  
  
  // catch possible negative values in NS4
  if (tempX < 0) tempX = 0;
  if (tempY < 0) tempY = 0;
  
  hcms_drawSelectArea();

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
    
    if (contextxmove) contextelement.style.left = tempX - 5 + "px";
    if (contextymove) contextelement.style.top = tempY - yoffset + "px";   
    contextelement.style.visibility = 'visible';
  }
  else if (document.layers)
  {
    var contextelement = document.layers['contextLayer'];
    
    if (tempY > hcms_getWindowHeight()/2) yoffset = parseInt(contextelement.style.height) + 0;
    else yoffset = 5;
    
    if (contextxmove) contextelement.left = tempX - 5 + "px";
    if (contextymove) contextelement.top = tempY - yoffset + "px";
    contextelement.visibility = 'visible';
  } 
  else 
  {
    var contextelement = document.getElementById('contextLayer'); 
    
    if (tempY > hcms_getWindowHeight()/2) yoffset = parseInt(contextelement.style.height) + 0;
    else yoffset = 5;
    
    if (contextxmove) contextelement.style.left = tempX - 5 + "px";
    if (contextymove) contextelement.style.top = tempY - yoffset + "px";
    contextelement.style.visibility = 'visible';
  }    
  
  return true;
}
  
// set the icons of the contextmenu and call positioning
function hcms_showContextmenu ()
{
  if (document.forms['contextmenu_object'])
  { 
    document.forms['contextmenu_object'].elements['contextmenustatus'].value = "visible";
    
    var contexttype = document.forms['contextmenu_object'].elements['contexttype'].value;
    var multiobject = document.forms['contextmenu_object'].elements['multiobject'].value;

    if (contextenable)
    {
      if (contexttype == "object" || contexttype == "folder" || (multiobject != "" && contexttype == "media"))
      {
        if (document.getElementById("img_preview")) document.getElementById("img_preview").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_cmsview")) document.getElementById("img_cmsview").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_notify")) document.getElementById("img_notify").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_chat")) document.getElementById("img_chat").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_restore")) document.getElementById("img_restore").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_delete")) document.getElementById("img_delete").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_cut")) document.getElementById("img_cut").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_copy")) document.getElementById("img_copy").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_copylinked")) document.getElementById("img_copylinked").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_publish")) document.getElementById("img_publish").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_unpublish")) document.getElementById("img_unpublish").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_unlock")) document.getElementById("img_unlock").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_fav_create")) document.getElementById("img_fav_create").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_fav_delete")) document.getElementById("img_fav_delete").className = "hcmsIconOn hcmsIconList";
        if (document.getElementsByName("img_plugin"))
        {
          var plugin_items = document.getElementsByName("img_plugin");
          for (var i=0; i<plugin_items.length; i++) plugin_items[i].className = "hcmsIconOn hcmsIconList";
        }
      }
      else if (multiobject == "" && contexttype == "media")
      {
        if (document.getElementById("img_preview")) document.getElementById("img_preview").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_cmsview")) document.getElementById("img_cmsview").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_notify")) document.getElementById("img_notify").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_chat")) document.getElementById("img_chat").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_restore")) document.getElementById("img_restore").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_delete")) document.getElementById("img_delete").className = "hcmsIconOn hcmsIconList";   
        if (document.getElementById("img_cut")) document.getElementById("img_cut").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_copy")) document.getElementById("img_copy").className = "hcmsIconOn hcmsIconList";      
        if (document.getElementById("img_copylinked")) document.getElementById("img_copylinked").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_publish")) document.getElementById("img_publish").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_unpublish")) document.getElementById("img_unpublish").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_unlock")) document.getElementById("img_unlock").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_fav_create")) document.getElementById("img_fav_create").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_fav_delete")) document.getElementById("img_fav_delete").className = "hcmsIconOn hcmsIconList";
        if (document.getElementsByName("img_plugin"))
        {
          var plugin_items = document.getElementsByName("img_plugin");
          for (var i=0; i<plugin_items.length; i++) plugin_items[i].className = "hcmsIconOn hcmsIconList";
        }
      }
      else
      {  
        if (document.getElementById("img_preview")) document.getElementById("img_preview").className = "hcmsIconOff hcmsIconList";
        if (document.getElementById("img_cmsview")) document.getElementById("img_cmsview").className = "hcmsIconOff hcmsIconList";
        if (document.getElementById("img_notify")) document.getElementById("img_notify").className = "hcmsIconOff hcmsIconList";
        if (document.getElementById("img_chat")) document.getElementById("img_chat").className = "hcmsIconOff hcmsIconList";
        if (document.getElementById("img_restore")) document.getElementById("img_restore").className = "hcmsIconOff hcmsIconList";
        if (document.getElementById("img_delete")) document.getElementById("img_delete").className = "hcmsIconOff hcmsIconList";   
        if (document.getElementById("img_cut")) document.getElementById("img_cut").className = "hcmsIconOff hcmsIconList";
        if (document.getElementById("img_copy")) document.getElementById("img_copy").className = "hcmsIconOff hcmsIconList";      
        if (document.getElementById("img_copylinked")) document.getElementById("img_copylinked").className = "hcmsIconOff hcmsIconList";
        if (document.getElementById("img_publish")) document.getElementById("img_publish").className = "hcmsIconOff hcmsIconList";
        if (document.getElementById("img_unpublish")) document.getElementById("img_unpublish").className = "hcmsIconOff hcmsIconList";
        if (document.getElementById("img_unlock")) document.getElementById("img_unlock").className = "hcmsIconOff hcmsIconList";
        if (document.getElementById("img_fav_create")) document.getElementById("img_fav_create").className = "hcmsIconOff hcmsIconList";
        if (document.getElementById("img_fav_delete")) document.getElementById("img_fav_delete").className = "hcmsIconOff hcmsIconList";
        if (document.getElementsByName("img_plugin"))
        {
          var plugin_items = document.getElementsByName("img_plugin");
          for (var i=0; i<plugin_items.length; i++) plugin_items[i].className = "hcmsIconOff hcmsIconList";
        }
      }
    }
  }
  else if (document.forms['contextmenu_user'])
  {
    document.forms['contextmenu_user'].elements['contextmenustatus'].value = "visible";
    
    var multiobject = document.forms['contextmenu_user'].elements['multiobject'].value;
    var login = document.forms['contextmenu_user'].elements['login'].value;

    if (login != "")
    {
      if (document.getElementById("img_edit")) document.getElementById("img_edit").className = "hcmsIconOn hcmsIconList";
      if (document.getElementById("img_delete")) document.getElementById("img_delete").className = "hcmsIconOn hcmsIconList";
    }    
    else
    {
      if (document.getElementById("img_edit")) document.getElementById("img_edit").className = "hcmsIconOff hcmsIconList";
      if (document.getElementById("img_delete")) document.getElementById("img_delete").className = "hcmsIconOff hcmsIconList";
    }  
  }
  else if (document.forms['contextmenu_queue'])
  {
    document.forms['contextmenu_queue'].elements['contextmenustatus'].value = "visible";
    
    var multiobject = document.forms['contextmenu_queue'].elements['multiobject'].value;
    var queue_id = document.forms['contextmenu_queue'].elements['queue_id'].value;

    if (queue_id != "")
    {
      if (document.getElementById("img_edit")) document.getElementById("img_edit").className = "hcmsIconOn hcmsIconList";
      if (document.getElementById("img_delete")) document.getElementById("img_delete").className = "hcmsIconOn hcmsIconList";
    }    
    else
    {
      if (document.getElementById("img_edit")) document.getElementById("img_edit").className = "hcmsIconOff hcmsIconList";
      if (document.getElementById("img_delete")) document.getElementById("img_delete").className = "hcmsIconOff hcmsIconList";
    }
  }
  
  hcms_positionContextmenu ();    

  return true;
} 

function hcms_hideContextmenu ()
{
  if (document.forms['contextmenu_object']) document.forms['contextmenu_object'].elements['contextmenustatus'].value = "hidden";
  if (document.forms['contextmenu_user']) document.forms['contextmenu_user'].elements['contextmenustatus'].value = "hidden";
  if (document.forms['contextmenu_queue']) document.forms['contextmenu_queue'].elements['contextmenustatus'].value = "hidden";
  
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

function hcms_submitWindow (formName, features, width, height)
{
  winName = 'popup' + Math.floor(Math.random()*1000);
  document.forms[formName].target = winName;
  hcms_openWindow('', winName, features, width, height);
  document.forms[formName].submit();
  
  return true;
}

function hcms_emptyRecycleBin (token)
{
  // lock
  hcms_lockContext ('true');
  
  if (document.forms['contextmenu_object'])
  {
    check = confirm_delete ();
  
    if (check == true)
    { 
      document.forms['contextmenu_object'].attributes['action'].value = 'popup_status.php';
      document.forms['contextmenu_object'].elements['action'].value = 'emptybin';
      document.forms['contextmenu_object'].elements['force'].value = 'start';
      document.forms['contextmenu_object'].elements['token'].value = token;
      hcms_submitWindow('contextmenu_object', 'status=no,scrollbars=no,resizable=no', 400, 300);
    }
  }
  
  // unlock
  hcms_lockContext ('false');
  
  return true;
}

function hcms_createContextmenuItem (action)
{
  // lock
  hcms_lockContext ('true');

  // get width and height for object window
  if (localStorage.getItem('windowwidth') !== null || localStorage.getItem('windowwidth') > 0) var windowwidth = localStorage.getItem('windowwidth');
  else var windowwidth = 800;

  if (localStorage.getItem('windowheight') !== null || localStorage.getItem('windowheight') > 0) var windowheight = localStorage.getItem('windowheight');
  else var windowheight = 1000;

  // get object new window
  if (localStorage.getItem('object_newwindow') !== null && localStorage.getItem('object_newwindow') == "true") var object_newwindow = true;
  else var object_newwindow = false;

  // get message new window
  if (localStorage.getItem('message_newwindow') !== null && localStorage.getItem('message_newwindow') == "true") var message_newwindow = true;
  else var message_newwindow = false;

  // get user new window
  if (localStorage.getItem('user_newwindow') !== null && localStorage.getItem('user_newwindow') == "true") var user_newwindow = true;
  else var user_newwindow = false;

  // Objects (folders, pages, assets)
  if (document.forms['contextmenu_object'])
  {
    var check = false;
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
          URLparaView = 'site=' + encodeURIComponent(site) + '&cat=' + encodeURIComponent(cat) + '&location=' + encodeURIComponent(location) + '&page=' + encodeURIComponent(page) + '&token=' + token;
        }
        else if (contexttype == "folder")
        {
          URLparaView = 'site=' + encodeURIComponent(site) + '&cat=' + encodeURIComponent(cat) + '&location=' + encodeURIComponent(location + folder) + '/&folder=' + encodeURIComponent(folder) + '&page=' + encodeURIComponent(page) + '&token=' + token;
        }
      }
      
      // preview
      if (action == "preview")
      {
        if (folder != "") location = location + folder + '/';
        openObjectView(location, page, 'preview');
      }
      // edit view
      else if (action == "cmsview")
      {
        // multiedit for multiple selected objects
        if (multiobject.split("|").length > 2 && parent && parent.frames && parent.frames['controlFrame'] && parent.frames['controlFrame'].submitToWindow)
        {
          if (object_newwindow == true) parent.frames['controlFrame'].submitToWindow('page_multiedit.php', '', 'multiedit', 'status=yes,scrollbars=yes,resizable=yes', windowwidth, windowheight);
          else parent.frames['controlFrame'].submitToMainFrame('page_multiedit.php', '');
        }
        // single object
        else
        {
          if (object_newwindow == true) hcms_openWindow('frameset_content.php?ctrlreload=yes&' + URLparaView, '', 'status=yes,scrollbars=no,resizable=yes', windowwidth, windowheight);
          else top.openMainView('frameset_content.php?ctrlreload=yes&' + URLparaView);
        }
      }
      // notify me
      else if (action == "notify")
      {
        URLfile = "popup_notify.php";
          
        document.forms['contextmenu_object'].attributes['action'].value = URLfile;
        hcms_submitWindow('contextmenu_object', 'status=no,scrollbars=no,resizable=no', 620, 520);
      }
      // chat
      else if (action == "chat")
      {
        var chatcontent = "hcms_openWindow('frameset_content.php?ctrlreload=yes&" + URLparaView + "', '', 'status=yes,scrollbars=no,resizable=yes', 800, 1000);";
        
        sendtochat (chatcontent);
      }
      // restore from recycle bin
      else if (action == "restore")
      {
        if (multiobject == "" && (contexttype == "object" || contexttype == "media")) URLfile = "popup_action.php";
        else URLfile = "popup_status.php";
          
        document.forms['contextmenu_object'].attributes['action'].value = URLfile;
        document.forms['contextmenu_object'].elements['action'].value = action;
        document.forms['contextmenu_object'].elements['force'].value = 'start';
        hcms_submitWindow('contextmenu_object', 'location=no,menubar=no,toolbar=no,titlebar=no,status=no,scrollbars=no,resizable=no', 400, 300);
      }
      // delete
      else if (action == "delete")
      {
        if ((multiobject != "" || page != "" || folder != "") && permission['delete'] == true) check = confirm_delete();

        if (check == true)
        {
          if (multiobject == "" && (contexttype == "object" || contexttype == "media")) URLfile = "popup_action.php";
          else URLfile = "popup_status.php";
            
          document.forms['contextmenu_object'].attributes['action'].value = URLfile;
          document.forms['contextmenu_object'].elements['action'].value = action;
          document.forms['contextmenu_object'].elements['force'].value = 'start';
          hcms_submitWindow('contextmenu_object', 'location=no,menubar=no,status=no,scrollbars=no,resizable=no', 400, 300);
        }
      }
      // cut, copy, linked copy
      else if (action == "cut" || action == "copy" || action == "linkcopy")
      {
        if (permission['rename'] == true)
        {
          document.forms['contextmenu_object'].attributes['action'].value = 'popup_action.php';
          document.forms['contextmenu_object'].elements['action'].value = action;
          hcms_submitWindow('contextmenu_object', 'location=no,menubar=no,toolbar=no,titlebar=no,status=no,scrollbars=no,resizable=no', 400, 300);
        } 
      }
      // paste
      else if (action == "paste")
      {
        if (site != "" && location != "" && permission['paste'] == true)
        {
          hcms_openWindow('popup_status.php?force=start&action=' + encodeURIComponent(action) + '&site=' + encodeURIComponent(site) + '&cat=' + encodeURIComponent(cat) + '&location=' + encodeURIComponent(location) + '&token=' + token, '', 'status=no,scrollbars=no,resizable=no', 400, 300);    
        }
      }
      // publish, unpublish
      else if (action == "publish" || action == "unpublish")
      {
        if (permission['publish'] == true)
        {
          URLfile = "popup_publish.php";
            
          document.forms['contextmenu_object'].attributes['action'].value = URLfile;
          document.forms['contextmenu_object'].elements['action'].value = action;
          document.forms['contextmenu_object'].elements['force'].value = 'start';
          hcms_submitWindow('contextmenu_object', 'location=no,menubar=no,toolbar=no,titlebar=no,status=no,scrollbars=no,resizable=no', 400, 300);
        }
      }
      // create favorite
      else if (action == "favorites_create")
      {
        document.forms['contextmenu_object'].attributes['action'].value = "popup_action.php";
        document.forms['contextmenu_object'].elements['action'].value = "page_favorites_create";
        hcms_submitWindow('contextmenu_object', 'location=no,menubar=no,toolbar=no,titlebar=no,status=no,scrollbars=no,resizable=no' ,400, 300);
        allow_tr_submit = false;
      }
      // delete favorite
      else if (action == "favorites_delete")
      {
        document.forms['contextmenu_object'].attributes['action'].value = "popup_action.php";
        document.forms['contextmenu_object'].elements['action'].value = "page_favorites_delete";
        hcms_submitWindow('contextmenu_object', 'location=no,menubar=no,toolbar=no,titlebar=no,status=no,scrollbars=no,resizable=no' ,400, 300);
        allow_tr_submit = false;
      }
      // check-in
      else if (action == "checkin")
      {
        document.forms['contextmenu_object'].attributes['action'].value = "popup_action.php";
        document.forms['contextmenu_object'].elements['action'].value = "page_unlock";
        hcms_submitWindow('contextmenu_object', 'location=no,menubar=no,toolbar=no,titlebar=no,status=no,scrollbars=no,resizable=no', 400, 300);
        allow_tr_submit = false;
      }
      // other actions
      else if (action != "")
      {
        document.forms['contextmenu_object'].attributes['action'].value = action;
        document.forms['contextmenu_object'].attributes['target'].value = "workplFrame";
        document.forms['contextmenu_object'].elements['action'].value = "plugin";
        document.forms['contextmenu_object'].submit();
        allow_tr_submit = false;
      }
    }
  }
  // Users
  else if (document.forms['contextmenu_user'])
  {
    var check = false;
    var site = document.forms['contextmenu_user'].elements['site'].value;
    var group = document.forms['contextmenu_user'].elements['group'].value;
    var login = document.forms['contextmenu_user'].elements['login'].value;
    var multiobject = document.forms['contextmenu_user'].multiobject.value;
    var token = document.forms['contextmenu_user'].elements['token'].value;
    
    if (action == "edit")
    {
      if (user_newwindow == true) hcms_openWindow('user_edit.php?site=' + encodeURIComponent(site) + '&group=' + encodeURIComponent(group) + '&login=' + encodeURIComponent(login) + '&token=' + token, 'edit', 'status=yes,scrollbars=yes,resizable=yes', 560, 800);
      else parent.openPopup('user_edit.php?site=' + encodeURIComponent(site) + '&group=' + encodeURIComponent(group) + '&login=' + encodeURIComponent(login) + '&token=' + token);
    }
    else if (action == "delete")
    {
      if (multiobject != "" || login != "") check = confirm_delete();
    
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
  // Queue
  else if (document.forms['contextmenu_queue'])
  {
    var check = false;
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
      // object
      if (site != "" && location != "")
      {
        if (object_newwindow == true) hcms_openWindow('frameset_content.php?site=' + encodeURIComponent(site) + '&ctrlreload=yes&cat=' + encodeURIComponent(cat) + '&location=' + encodeURIComponent(location) + '&page=' + encodeURIComponent(page) + '&queueuser=' + encodeURIComponent(queueuser) + '&queue_id=' + encodeURIComponent(queue_id) + '&token=' + token, '', 'status=yes,scrollbars=no,resizable=yes', 800, 1000);
        else top.openMainView('frameset_content.php?site=' + encodeURIComponent(site) + '&ctrlreload=yes&cat=' + encodeURIComponent(cat) + '&location=' + encodeURIComponent(location) + '&page=' + encodeURIComponent(page) + '&queueuser=' + encodeURIComponent(queueuser) + '&queue_id=' + encodeURIComponent(queue_id) + '&token=' + token);
      }
      // message
      else if (page != "")
      {
        if (message_newwindow == true) hcms_openWindow('user_sendlink.php?mailfile=' + encodeURIComponent(page) + '&cat=' + encodeURIComponent(cat) + '&queueuser=' + encodeURIComponent(queueuser) + '&queue_id=' + encodeURIComponent(queue_id) + '&token=' + token, '', 'status=yes,scrollbars=no,resizable=yes', 600, 800);
        else if (page != "") parent.openPopup('user_sendlink.php?mailfile=' + encodeURIComponent(page) + '&cat=' + encodeURIComponent(cat) + '&queueuser=' + encodeURIComponent(queueuser) + '&queue_id=' + encodeURIComponent(queue_id) + '&token=' + token);
      }
    }
    else if (action == "delete")
    {
      if (multiobject != "" || page != "") check = confirm_delete();
    
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
  // Messages
  else if (document.forms['contextmenu_message'])
  {
    var check = false;
    var messageuser = document.forms['contextmenu_message'].elements['messageuser'].value;
    var message_id = document.forms['contextmenu_message'].elements['message_id'].value;
    var multiobject = document.forms['contextmenu_message'].elements['multiobject'].value;
    var token = document.forms['contextmenu_message'].elements['token'].value;
    
    if (action == "edit")
    {
      if (message_newwindow == true) hcms_openWindow('user_sendlink.php?mailfile=' + encodeURIComponent(message_id) + '&cat=comp&messageuser=' + encodeURIComponent(messageuser) + '&token=' + token, '', 'status=yes,scrollbars=no,resizable=yes', 600, 800);
      else parent.openPopup('user_sendlink.php?mailfile=' + encodeURIComponent(message_id) + '&cat=comp&messageuser=' + encodeURIComponent(messageuser) + '&token=' + token);
    }
    else if (action == "delete")
    {
      if ((multiobject != "" || message_id != "")) check = confirm_delete();
    
      if (check == true)
      {
        document.forms['contextmenu_message'].attributes['action'].value = "control_message_menu.php";
        document.forms['contextmenu_message'].attributes['target'].value = "controlFrame";
        document.forms['contextmenu_message'].elements['action'].value = action;
        document.forms['contextmenu_message'].submit();
        allow_tr_submit = false;
      }
    }
  }
  
  // unlock
  hcms_lockContext ('false');
  
  return true;
}

function hcms_setObjectcontext (site, cat, location, page, pagename, filetype, media, folder, folder_id, token)
{
  if (document.forms['contextmenu_object'] && hcms_isLockedContext() == false)
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
    
    // enable/disable display of context menus
    contextmenu_form.style.display = 'block';    
    if (document.forms['contextmenu_column']) document.forms['contextmenu_column'].style.display = 'none';

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
    if (contextmenu_form.elements['folder_id']) contextmenu_form.elements['folder_id'].value = folder_id;
    contextmenu_form.elements['token'].value = token;
  }
  
  return true;
}

function hcms_setColumncontext ()
{
  if (document.forms['contextmenu_column'])
  {
    // hide and reset context menu
    hcms_hideContextmenu();

    var contextmenu_form = document.forms['contextmenu_column'];
    
    // enable/disable display of context menus
    contextmenu_form.style.display = 'block';    
    if (document.forms['contextmenu_object']) document.forms['contextmenu_object'].style.display = 'none';
  }
  
  return true;
}

function hcms_setUsercontext (site, login, token)
{
  if (document.forms['contextmenu_user'] && hcms_isLockedContext() == false)
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

function hcms_setQueuecontext (site, cat, location, page, pagename, filetype, queueuser, queue_id, token)
{
  if (document.forms['contextmenu_queue'] && hcms_isLockedContext() == false)
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

function hcms_setMessagecontext (messageuser, message_id, token)
{
  if (document.forms['contextmenu_message'] && hcms_isLockedContext() == false)
  {
    // hide and reset context menu
    hcms_hideContextmenu();
  
    var contextmenu_form = document.forms['contextmenu_message'];
    
    // set values   
    contextmenu_form.elements['xpos'].value = tempX;
    contextmenu_form.elements['ypos'].value = tempY;
    contextmenu_form.elements['messageuser'].value = messageuser;   
    contextmenu_form.elements['message_id'].value = message_id;
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

// if string ends with suffix
function hcms_endsWith (str, suffix)
{
  return str.indexOf(suffix, str.length - suffix.length) !== -1;
}

// select multiple objects
function hcms_selectObject (row_id, event)
{
  var contextmenu_form = false;
  
  // extract number from td ID
  if (row_id != '')
  {
    // for tr and td in list view
    row_id = row_id.replace ('g', '');
    row_id = row_id.replace ('h', '');
    row_id = row_id.replace ('_0', '');
    
    // for td in gallery view
    row_id = row_id.replace ('t', '');
  }
  else return false;

  if (document.forms['contextmenu_object'])
  {
    contextmenu_form = document.forms['contextmenu_object'];
  }
  else if (document.forms['contextmenu_user'])
  {
    contextmenu_form = document.forms['contextmenu_user'];
  }
  else if (document.forms['contextmenu_queue'])
  {
    contextmenu_form = document.forms['contextmenu_queue'];
  }
  else if (document.forms['contextmenu_message'])
  {
    contextmenu_form = document.forms['contextmenu_message'];
  }
  
  // no contextmenu to use
  if (contextmenu_form == false)
  {
    return false;
  }   
 
  // reset object list if multiobject is empty
  if (contextmenu_form.elements['multiobject'].value == "")
  {
    hcms_unselectAll();
  }

  // if ctrl-key is pressed or select area is used
  if (hcms_keyPressed('ctrl', event) == true || event == 'selectarea' || is_mobile)
  {
    var multiobject_str = contextmenu_form.elements['multiobject'].value;
    var multiobject_str2 = multiobject_str + '|';
  
    var td = document.getElementById('h' + row_id + '_0');
    var links = td.getElementsByTagName('A');

    if (links) var object = links[0].getAttribute('data-objectpath');
    else var object = '';

    if (object != '')
    {
      if (multiobject_str == '|' || multiobject_str2.indexOf ('|'+object+'|') == -1)
      {
        contextmenu_form.elements['multiobject'].value = multiobject_str + '|' + object;
        document.getElementById('g' + row_id).className='hcmsObjectSelected';
        if (document.getElementById('objectgallery')) document.getElementById('t' + row_id).className='hcmsObjectSelected';
        return true;
      }
      else if (multiobject_str != '')
      {
        if (multiobject_str == object)
        {
          contextmenu_form.elements['multiobject'].value = hcms_replace (multiobject_str, object, '');
        }
        else if (hcms_endsWith(multiobject_str, '|'+object))
        {
          contextmenu_form.elements['multiobject'].value = hcms_replace (multiobject_str, '|'+object, '');
        }
        else
        {
          contextmenu_form.elements['multiobject'].value = hcms_replace (multiobject_str, '|'+object+'|', '|');
        }
        
        document.getElementById('g' + row_id).className='hcmsObjectUnselected';
        if (document.getElementById('objectgallery')) document.getElementById('t' + row_id).className='hcmsObjectUnselected';
        return true;
      }
      else return false; 
    }
    else return false; 
  }
  // if shift-key is pressed
  else if (hcms_keyPressed('shift', event) == true)
  {
    var multiobject_str = contextmenu_form.elements['multiobject'].value;
    var multiobject_str2 = multiobject_str + '|';
  
    var td = document.getElementById('h' + row_id + '_0');    
    var links = td.getElementsByTagName('A');

    if (links) var object = links[0].getAttribute('data-objectpath');
    else var object = '';
    
    if (object != '')
    {
      if (multiobject_str == '')
      {
        contextmenu_form.elements['multiobject'].value = multiobject_str + '|' + object;
        document.getElementById('g' + row_id).className='hcmsObjectSelected';
        if (document.getElementById('objectgallery')) document.getElementById('t' + row_id).className='hcmsObjectSelected';
        return true;    
      }
      else if (multiobject_str != '')
      {
        var lastselection = multiobject_str.substring (multiobject_str.lastIndexOf ('|') + 1);
        var currentselection = object;        
        var table = document.getElementById('objectlist');   
        var rows = table.getElementsByTagName("TR");
        var row_id = 0;
        var startselect = false;
        var stopselect = false;
        var topdown = '';

        for (i = 0; i < rows.length; i++)
        {
          var links = rows[i].getElementsByTagName('A');  

          if (links) object = links[0].getAttribute('data-objectpath');
          else object = '';
          
          if (topdown == '1' && startselect == true && stopselect == false && multiobject_str2.indexOf ('|'+object+'|') == -1)
          { 
            multiobject_str = multiobject_str + '|' + object;
            row_id = rows[i].id;
            row_id = row_id.substr(1);
            rows[i].className='hcmsObjectSelected';
            if (document.getElementById('objectgallery')) document.getElementById('t' + row_id).className='hcmsObjectSelected';
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
            rows[i].className = 'hcmsObjectSelected';
            if (document.getElementById('objectgallery')) document.getElementById('t' + row_id).className = 'hcmsObjectSelected';
          }          
        }
        
        contextmenu_form.elements['multiobject'].value =  multiobject_str; 
        return true;
      }
    }
    else return false;
  }
  // if no key is pressed
  else
  {
    hcms_unselectAll();

    var td = document.getElementById('h' + row_id + '_0');
    var links = td.getElementsByTagName('A');

    if (links[0]) var object = links[0].getAttribute('data-objectpath');
    else var object = '';

    if (object != '')
    {
      contextmenu_form.elements['multiobject'].value = '|' + object;
      document.getElementById('g' + row_id).className = 'hcmsObjectSelected';
      if (document.getElementById('objectgallery')) document.getElementById('t' + row_id).className = 'hcmsObjectSelected';
      return true;
    }
    else return false; 
  }
}

// update control object list menu
function hcms_updateControlObjectListMenu ()
{
  if (document.forms['contextmenu_object'])
  {
    document.forms['contextmenu_object'].attributes['action'].value = 'control_objectlist_menu.php';
    document.forms['contextmenu_object'].attributes['target'].value = 'controlFrame';
    document.forms['contextmenu_object'].elements['action'].value = '';

    if (allow_tr_submit && document.forms['contextmenu_object'].elements['location'].value != '')
    {
      document.forms['contextmenu_object'].submit();
    }
    else
    {
      allow_tr_submit = true;
    }

    // sidebar
    if (!is_mobile) hcms_loadSidebar();

    return true;
  }
  else return false;
}

// update control user menu
function hcms_updateControlUserMenu ()
{
  if (document.forms['contextmenu_user'])
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

    return true;
  }
  else return false;
}

// update control queue menu
function hcms_updateControlQueueMenu()
{
  if (document.forms['contextmenu_queue'])
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

    return true;
  }
  else return false;
}

// update control message menu
function hcms_updateControlMessageMenu ()
{
  if (document.forms['contextmenu_message'])
  {
    document.forms['contextmenu_message'].attributes['action'].value = 'control_message_menu.php';
    document.forms['contextmenu_message'].attributes['target'].value = 'controlFrame';
    document.forms['contextmenu_message'].elements['action'].value = '';
    
    if (allow_tr_submit)
    {
      document.forms['contextmenu_message'].submit();
    }
    else
    {
      allow_tr_submit = true;
    }

    return true;
  }
  else return false;
}

// select all objects
function hcms_selectAll (e)
{
  if (!e) var e = window.event;

  // if Ctrl+A key is pressed
  if (hcms_keyPressed('ctrl', e) && e.key === 'a')
  {
    // prevent the default select
    e.preventDefault();
    
    // select all
    if (document.getElementById('objectlist'))
    {
      var links = '';
      var multiobject = '';
      var table = document.getElementById('objectlist');
      var tablerows = table.getElementsByTagName("TR");

      for (i = 0; i < tablerows.length; i++)
      {
        links = tablerows[i].getElementsByTagName('A');  

        if (links)
        {
          object = links[0].getAttribute('data-objectpath');
          if (object != '') multiobject = multiobject + '|' + object;
        }

        tablerows[i].className = "hcmsObjectSelected";
      }
    }
    
    if (document.getElementById('objectgallery'))
    {
      var table = document.getElementById('objectgallery');   
      var tabledata = table.children;

      for (i = 0; i < tabledata.length; i++)
      {           
        tabledata[i].className = "hcmsObjectSelected";     
      }  
    } 

    if (document.forms['contextmenu_object'] && document.forms['contextmenu_object'].elements['multiobject'])
    {
      document.forms['contextmenu_object'].elements['multiobject'].value = multiobject;
      hcms_updateControlObjectListMenu();
    }
    else if (document.forms['contextmenu_user'] && document.forms['contextmenu_user'].elements['multiobject'])
    {
      document.forms['contextmenu_user'].elements['multiobject'].value = multiobject;
      hcms_updateControlUserMenu();
    }
    else if (document.forms['contextmenu_queue'] && document.forms['contextmenu_queue'].elements['multiobject'])
    {
      document.forms['contextmenu_queue'].elements['multiobject'].value = multiobject;
      hcms_updateControlQueueMenu();
    }
    else if (document.forms['contextmenu_message'] && document.forms['contextmenu_message'].elements['multiobject'])
    {
      document.forms['contextmenu_message'].elements['multiobject'].value = multiobject;
      hcms_updateControlMessageMenu();
    }

    return true;
  }
}

// unselect all objects
function hcms_unselectAll ()
{
  if (document.getElementById('objectlist'))
  {
    var table = document.getElementById('objectlist');   
    var tablerows = table.getElementsByTagName("TR");

    for (i = 0; i < tablerows.length; i++)
    {           
      tablerows[i].className = "hcmsObjectUnselected";
    }
  }

  if (document.getElementById('objectgallery'))
  {
    var table = document.getElementById('objectgallery');   
    var tabledata = table.children;

    for (i = 0; i < tabledata.length; i++)
    {           
      tabledata[i].className = "hcmsObjectUnselected";     
    }  
  } 

  if (document.forms['contextmenu_object'] && document.forms['contextmenu_object'].elements['multiobject'].value)
  {
    document.forms['contextmenu_object'].elements['multiobject'].value = '';
    hcms_updateControlObjectListMenu();
  }
  else if (document.forms['contextmenu_user'] && document.forms['contextmenu_user'].elements['multiobject'].value)
  {
    document.forms['contextmenu_user'].elements['multiobject'].value = '';
    hcms_updateControlUserMenu();
  }
  else if (document.forms['contextmenu_queue'] && document.forms['contextmenu_queue'].elements['multiobject'].value)
  {
    document.forms['contextmenu_queue'].elements['multiobject'].value = '';
    hcms_updateControlQueueMenu();
  }
  else if (document.forms['contextmenu_message'] && document.forms['contextmenu_message'].elements['multiobject'].value)
  {
    document.forms['contextmenu_message'].elements['multiobject'].value = '';
    hcms_updateControlMessageMenu();
  }

  return true; 
}

// contextmenu
function hcms_Contextmenu (e) 
{
  if (!e) var e = window.event;

  // if alt-key is pressed
  if (activatelinks == true) return true;
  else return false;
}

// right mouse click
function hcms_rightClick (e) 
{
  if (!e) var e = window.event;

  // if alt-key is not pressed
  if (activatelinks == false)
  {
    // right mouse click
    if (e.which && (e.which == 2 || e.which == 3)) 
    {
      hcms_showContextmenu();
    }
    else if (e.button && (e.button == 2 || e.button == 3)) 
    {
      hcms_showContextmenu();
    }

    hcms_startSelectArea(e);
  }

  return true;
}

// left mouse clicks
function hcms_leftClick (e) 
{
  if (!e) var e = window.event;

  var object;
  var multiobject;
  var objectcount = 0;

  // remove selection marks of browser
  hcms_clearSelection();

  if (document.forms['contextmenu_object'])
  {
    object = document.forms['contextmenu_object'].elements['page'].value;
    multiobject = document.forms['contextmenu_object'].elements['multiobject'].value;
  }
  else if (document.forms['contextmenu_user'])
  {
    object = document.forms['contextmenu_user'].elements['login'].value;
    multiobject = document.forms['contextmenu_user'].elements['multiobject'].value;
  }
  else if (document.forms['contextmenu_queue'])
  {
    object = document.forms['contextmenu_queue'].elements['page'].value;
    multiobject = document.forms['contextmenu_queue'].elements['multiobject'].value;
  }
  else if (document.forms['contextmenu_message'])
  {
    object = document.forms['contextmenu_message'].elements['message_id'].value;
    multiobject = document.forms['contextmenu_message'].elements['multiobject'].value;
  }

  // count objects stored in multiobject
  if (multiobject != "") objectcount = multiobject.split("|").length - 1;

  // left mouse click
  if (e.which == 0 || e.which == 1 || e.button == 0 || e.button == 1) 
  {
    hcms_hideContextmenu();

    // if no key is pressed and multiobject stores more than 1 object
    if (hcms_keyPressed('', e) == false && object == "" && objectcount <= 1)
    {
      hcms_unselectAll();
      hcms_resetContext();
    }
  }

  return true;
} 

// verify if key is pressed
function hcms_keyPressed (key, e)
{
  var ctrlPressed = 0;
  var altPressed = 0;
  var shiftPressed = 0;

  if (parseInt(navigator.appVersion) > 3 && e != null && e != 'selectarea')
  {
    var evt = navigator.appName == "Netscape" ? e:event;

    if (navigator.appName == "Netscape" && parseInt(navigator.appVersion) == 4)
    {
      // NETSCAPE 4 CODE
      var mString = (e.modifiers+32).toString(2).substring(3,6);
      shiftPressed = (mString.charAt(0) == "1");
      ctrlPressed = (mString.charAt(1) == "1");
      altPressed = (mString.charAt(2) == "1");
    }
    else
    {
      // NEWER BROWSERS [CROSS-PLATFORM]
      shiftPressed = evt.shiftKey;
      altPressed = evt.altKey;
      ctrlPressed = evt.ctrlKey;
    }

    if (key == 'ctrl' && ctrlPressed) return true;
    else if (key == 'shift' && shiftPressed) return true;
    else if (key == 'alt' && altPressed) return true;
    else if (key == '' && (altPressed || shiftPressed || ctrlPressed)) return true;
    else return false;
  }

  return false;
}

// get download or wrapper link from service
function hcms_getlink (location, type)
{
  if (location != '')
  {
    var object_id;
    var downloadlink = '';
    var wrapperlink = '';

  	$.ajax({
  		async: false,
  		type: 'POST',
  		url: 'service/getlink.php',
  		data: {'location': location},
  		dataType: 'json',
  		success: function(data){ if(data.success) {downloadlink = data.downloadlink; wrapperlink = data.downloadlink;} }
  	});

    if (type == 'download' && downloadlink != '') return downloadlink;
    else if (type == 'wrapper' && wrapperlink != '') return wrapperlink;
    else return false;
  }
  else return false;
}

// activate the download links
function hcms_activateLinks (e)
{
  if (!e) var e = window.event;

  // if Alt keyx is pressed
  // activate or deactivate links for all objects (only supports contextmenu_object)
  if (dragndrop == false && hcms_keyPressed('alt', e) && document.forms['contextmenu_object'])
  {
    var links = document.getElementsByTagName('A');
    var hashlink = false;
    var loadscreen = false;

    for (var i = 0; i < links.length; i++)
    {
      var thisLink = links[i];
      var href = thisLink.getAttribute('data-href');
      var location = thisLink.getAttribute('data-objectpath');
      var linktype = thisLink.getAttribute('data-linktype');

      if (linktype == 'download' || linktype == 'wrapper')
      {
        // activate link
        if (activatelinks == false)
        {
          // download link is available
          if (href != '')
          {
            // create href attribute
            var href_attr = document.createAttribute('href');
            href_attr.nodeValue = href;
            thisLink.setAttributeNode(href_attr);
          }
          // download link has not been requested
          else
          {
            // load screen
            if (loadscreen == false && document.getElementById('hcmsLoadScreen'))
            {
              document.getElementById('hcmsLoadScreen').style.display = 'inline';
              loadscreen = true;
            }

            // request link
            var href = hcms_getlink (location, linktype);
            // set data-href attribute value
            thisLink.setAttribute('data-href', href);
            // create href attribute
            var href_attr = document.createAttribute('href');
            href_attr.nodeValue = href;
            thisLink.setAttributeNode(href_attr);
          }
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

    if (hashlink == true)
    {
      // set activate state
      if (activatelinks == false)
      {
        document.getElementsByTagName('body')[0].className = 'hcmsWorkplaceObjectlistLinks';
        activatelinks = true;
        if (document.getElementById('hcmsLoadScreen')) document.getElementById('hcmsLoadScreen').style.display = 'none';
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

// select area
function hcms_startSelectArea (e)
{
  if (!e) var e = window.event;

  // if alt-key is not pressed
  // start select area on left mouse button down
  if (activatelinks == false && selectarea && (e.which == 0 || e.which == 1 || e.button == 0 || e.button == 1))
  {
    x1 = e.clientX;
    y1 = e.clientY;
    dragndrop = false;
    return true;
  }

  return false;
}

function hcms_drawSelectArea ()
{
  // if alt-key is not pressed
  if (dragndrop == false && activatelinks == false && selectarea && x1 > 0 && y1 > 0)
  {
    x3 = Math.min(x1,x2);
    x4 = Math.max(x1,x2);
    y3 = Math.min(y1,y2);
    y4 = Math.max(y1,y2);

    // remove selection marks of browser
    hcms_clearSelection();

    // enable select area if width and height are larger than 5 pixels
    if (selectarea.style.display == 'none' && (x4 - x3) > 5 && (y4 - y3) > 5)
    {
      selectarea.style.display = 'inline';
    }
    
    // size select area
    if (selectarea.style.display != 'none')
    {
      selectarea.style.left = x3 + 'px';
      selectarea.style.top = y3 + 'px';
      selectarea.style.width = x4 - x3 + 'px';
      selectarea.style.height = y4 - y3 + 'px';
      return true;
    }
  }

  return false;
}

function hcms_endSelectArea ()
{
  var selected = false;

  // if alt-key is not pressed and
  // if select area is used
  if (dragndrop == false && activatelinks == false && selectarea && selectarea.style.display != 'none' && x1 > 0 && y1 > 0 && x3 != 0 && y3 != 0 && x4 != 0 && y4 != 0 && (x4-x3) > 5 && (y4-y3) > 5)
  {    
    // unselect all
    hcms_unselectAll ();

    // select objects in the given area
    if (document.getElementById('objectLayer') && document.getElementById('objectLayer').style.visibility == "visible")
    {
      // list view
      var objects = document.getElementsByClassName('hcmsObjectListMarker');
      var x_diff = 80;
      var y_diff = 10;
    }
    else if (document.getElementById('galleryviewLayer') && document.getElementById('galleryviewLayer').style.visibility == "visible")
    {
      // gallery view
      var objects = document.getElementsByClassName('hcmsObjectGalleryMarker');
      var x_diff = 80;
      var y_diff = 80;
    }

    if (objects && objects.length > 0)
    {
      var div_id, row_id, pos, x, y;

      for (var i = 0; i < objects.length; i++)
      {
        pos = objects[i].getBoundingClientRect();
        x = pos.left;
        y = pos.top;
  
        if (x >= (x3-x_diff) && y >= (y3-y_diff) && x <= (x4-x_diff) && y <= (y4-y_diff))
        {
          row_id = objects[i].parentElement.id;
          hcms_selectObject (row_id, 'selectarea');
          selected = true;
        }
      }

      // update control
      if (selected)
      {
        if (document.forms['contextmenu_object']) setTimeout (hcms_updateControlObjectListMenu, 300);
        else if (document.forms['contextmenu_user']) setTimeout (hcms_updateControlUserMenu, 300);
        else if (document.forms['contextmenu_queue']) setTimeout (hcms_updateControlQueueMenu, 300);
        else if (document.forms['contextmenu_message']) setTimeout (hcms_updateControlMessageMenu, 300);
      }
    }
  }

  // reset select area
  x1 = 0;
  y1 = 0;
  x2 = 0;
  y2 = 0;
  x3 = 0;
  y3 = 0;
  x4 = 0;
  y4 = 0;

  // hide select area
  if (selectarea) selectarea.style.display = 'none';

  if (selected) return true;
  else return false;
}

function hcms_collectObjectpath ()
{
  // table with td element id=h0_0 must exist
  if (document.getElementById("h0_0"))
  {
    var td = null;
    var i = 0;
    
    // collect object path from the link tag
    while (td = document.getElementById("h"+i+"_0"))
    {
      var links = td.getElementsByTagName("A");
      if (links[0].getAttribute("data-objectpath")) hcms_objectpath[i] = links[0].getAttribute("data-objectpath");
      i++;
    }
  }

  // save object path array variable in parent frame
  if (hcms_objectpath) parent.hcms_objectpath = hcms_objectpath;
}

// find element by tag name by looking into the parent nodes
function hcms_findElementByTagName (element, tag)
{
  if (tag != "")
  {
    // tagName returns upper case
    tag = tag.toUpperCase();
    
    if (element.tagName == tag) return element;

    while (element.parentNode)
    {
      element = element.parentNode;
      if (element.tagName == tag) return element;
    }
  }

  return null;
}

// set the image for drag and drop
function hcms_setDragImage (e)
{
  if (dragndrop == true && hcms_getBrowserName() != "ie" && e.dataTransfer && typeof e.dataTransfer.setDragImage === "function")
  {
    // set custom drag image
    var dragimage = document.createElement('img');
    dragimage.src = themelocation + '/img/button_file_copy.png';
    e.dataTransfer.setDragImage(dragimage, 32, 32);
  }
}

// drag objects
function hcms_drag (e)
{
  if (!is_mobile && hcms_getBrowserName() != "ie" && e.dataTransfer && typeof e.dataTransfer.setData === "function" && document.forms['contextmenu_object'])
  {
    // to hide the select area
    dragndrop = true;

    // set custom drag image
    hcms_setDragImage(e)

    // context menu
    var contextmenu = document.forms['contextmenu_object'];

    e.dataTransfer.setData('action', contextmenu.elements['action'].value);
    e.dataTransfer.setData('force', contextmenu.elements['force'].value);
    e.dataTransfer.setData('contexttype', contextmenu.elements['contexttype'].value);
    e.dataTransfer.setData('site', contextmenu.elements['site'].value);
    e.dataTransfer.setData('cat', contextmenu.elements['cat'].value);
    e.dataTransfer.setData('location', contextmenu.elements['location'].value);
    e.dataTransfer.setData('page', contextmenu.elements['page'].value);
    e.dataTransfer.setData('pagename', contextmenu.elements['pagename'].value);
    e.dataTransfer.setData('filetype', contextmenu.elements['filetype'].value);
    e.dataTransfer.setData('media', contextmenu.elements['media'].value);
    e.dataTransfer.setData('folder', contextmenu.elements['folder'].value);
    e.dataTransfer.setData('multiobject', contextmenu.elements['multiobject'].value);
    e.dataTransfer.setData('token', contextmenu.elements['token'].value);
    e.dataTransfer.setData('convert_type', contextmenu.elements['convert_type'].value);
    e.dataTransfer.setData('convert_cfg', contextmenu.elements['convert_cfg'].value);
  }
}

// drop objects
function hcms_drop (e)
{
  if (!is_mobile && hcms_getBrowserName() != "ie" && e.target && e.dataTransfer && typeof e.dataTransfer.getData === "function" && e.dataTransfer.getData('site') && e.dataTransfer.getData('location') && document.forms['contextmenu_object'])
  {
    // prevent default event on drop
    e.preventDefault();

    // find link
    var link = hcms_findElementByTagName(e.target, 'A');

    if (link)
    {
      // context menu
      var memory = document.forms['memory'];
      var targetlocation = link.getAttribute('data-objectpath');
  
      memory.attributes['action'].value = 'popup_status.php';

      if (hcms_keyPressed('alt', e)) memory.elements['action'].value = 'linkcopy->paste';
      else if (hcms_keyPressed('ctrl', e)) memory.elements['action'].value = 'copy->paste';
      else memory.elements['action'].value = 'cut->paste';
      
      memory.elements['force'].value = 'start';
      memory.elements['contexttype'].value = e.dataTransfer.getData('contexttype');
      memory.elements['site'].value = e.dataTransfer.getData('site');
      memory.elements['cat'].value = e.dataTransfer.getData('cat');
      memory.elements['location'].value = e.dataTransfer.getData('location');
      memory.elements['targetlocation'].value = targetlocation;
      memory.elements['page'].value = e.dataTransfer.getData('page');
      memory.elements['pagename'].value = e.dataTransfer.getData('pagename');
      memory.elements['filetype'].value = e.dataTransfer.getData('filetype');
      memory.elements['media'].value = e.dataTransfer.getData('media');
      memory.elements['folder'].value = e.dataTransfer.getData('folder');
      memory.elements['multiobject'].value = e.dataTransfer.getData('multiobject');
      memory.elements['token'].value = e.dataTransfer.getData('token');
      memory.elements['convert_type'].value = e.dataTransfer.getData('convert_type');
      memory.elements['convert_cfg'].value = e.dataTransfer.getData('convert_cfg');

      hcms_submitWindow('memory', 'status=no,scrollbars=no,resizable=no', 400, 180);
    }
  }
  // prevent default event on drop
  else if (!is_mobile) e.preventDefault();
}

// prevent default event on drop
function hcms_allowDrop (e)
{
  if (!is_mobile) e.preventDefault();
}

// key events
document.addEventListener('keydown', e => {
  // activate download links on Alt key
  hcms_activateLinks(e);

  // select all objects on Ctrl+A key
  hcms_selectAll(e);

  // cut objects if Ctrl+X key is pressed
  if (hcms_keyPressed('ctrl', e) && (e.key == 'x' || e.key == 'X'))
  {
    // prevent the default
    e.preventDefault();
    hcms_createContextmenuItem('cut');
  }

  // copy objects if Ctrl+C key is pressed
  if (hcms_keyPressed('ctrl', e) && (e.key == 'c' || e.key == 'C'))
  {
    // prevent the default
    e.preventDefault();
    hcms_createContextmenuItem('copy');
  }

  // linked copy objects if Ctrl+Y key is pressed
  if (hcms_keyPressed('ctrl', e) && (e.key == 'y' || e.key == 'Y'))
  {
    // prevent the default
    e.preventDefault();
    hcms_createContextmenuItem('linkcopy');
  }

  // paste objects if Ctrl+V key is pressed
  if (hcms_keyPressed('ctrl', e) && (e.key == 'v' || e.key == 'V'))
  {
    // prevent the default
    e.preventDefault();
    hcms_createContextmenuItem('paste');
  }

  // delete objects if Ctrl+V key is pressed
  if (e.key == "Delete") hcms_createContextmenuItem('delete');
});

// other events
document.onmousemove = hcms_getMouseXY;
document.oncontextmenu = hcms_Contextmenu;
document.onmousedown = hcms_rightClick;
document.onmouseup = hcms_endSelectArea;
document.onclick = hcms_leftClick;

// for alert in iframe
window.alert = top.alert;