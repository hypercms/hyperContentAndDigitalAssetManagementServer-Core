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
var is_selectarea = false;
var x1 = 0;
var y1 = 0;
var x2 = 0;
var y2 = 0;
var x3 = 0;
var y3 = 0;
var x4 = 0;
var y4 = 0;

// drag and drop
var is_dragndrop = false;

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
    var location = document.forms['contextmenu_object'].elements['location'].value;
    var folder = document.forms['contextmenu_object'].elements['folder'].value;
    var page = document.forms['contextmenu_object'].elements['page'].value;
    
    if (allow_tr_submit && location != '' && (folder != '' || page != ''))
    {
      // wait (due to issues with browsers like MS Edge, Chrome)
      setTimeout (function() { parent.document.getElementById('sidebarFrame').src='explorer_preview.php?location=' + encodeURIComponent(location) + '&folder=' +  encodeURIComponent(folder) + '&page=' + encodeURIComponent(page); }, 300);
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
    var multiobject_count = multiobject.split('|').length - 1;

    if (contextenable)
    {
      if (contexttype == "object" || contexttype == "folder" || (multiobject != "" && contexttype == "media"))
      {
        if (document.getElementById("img_preview")) document.getElementById("img_preview").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_cmsview")) document.getElementById("img_cmsview").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_notify")) document.getElementById("img_notify").className = "hcmsIconOn hcmsIconList";
        if (document.getElementById("img_chat") && multiobject_count <= 1) document.getElementById("img_chat").className = "hcmsIconOn hcmsIconList";
        else if (document.getElementById("img_chat")) document.getElementById("img_chat").className = "hcmsIconOff hcmsIconList";
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
        if (document.getElementById("img_chat") && multiobject_count <= 1) document.getElementById("img_chat").className = "hcmsIconOn hcmsIconList";
        else if (document.getElementById("img_chat")) document.getElementById("img_chat").className = "hcmsIconOff hcmsIconList";
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

function hcms_submitToWindow (formName, features, width, height)
{
  if (document.forms[formName])
  {
    winName = 'popup' + hcms_uniqid();
    document.forms[formName].target = winName;
    hcms_openWindow('', winName, features, width, height);
    document.forms[formName].submit();    
    return true;
  }
  else return false;
}

function hcms_submitToPopup (formName, id)
{
  if (document.forms[formName] && id != '')
  {
    var contextmenu_form = document.forms[formName];
    var result = window.top.openPopup('empty.php', id);

    if (result)
    {
      contextmenu_form.target = id  + "Frame"; 
      contextmenu_form.submit();
      return true;
    }
    else return false;
  }
  else return false;
}

function hcms_openPopup (url, id)
{
  if (url != '' && id != '')
  {
    window.top.openPopup(url, id);
    return true;
  }
  else return false;
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
      hcms_submitToPopup('contextmenu_object', "recyclebin");
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
    var contextmenu_form = document.forms['contextmenu_object'];
    var contexttype = contextmenu_form.elements['contexttype'].value;
    var site = contextmenu_form.elements['site'].value;
    var cat = contextmenu_form.elements['cat'].value;
    var location = contextmenu_form.elements['location'].value;
    var page = contextmenu_form.elements['page'].value;
    var pagename = contextmenu_form.elements['pagename'].value;
    var filetype = contextmenu_form.elements['filetype'].value;
    var media = contextmenu_form.elements['media'].value;
    var folder = contextmenu_form.elements['folder'].value;
    var multiobject = contextmenu_form.elements['multiobject'].value;
    var multiobject_count = multiobject.split('|').length - 1;
    var token = contextmenu_form.elements['token'].value;

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
          
        contextmenu_form.attributes['action'].value = URLfile;
        hcms_submitToPopup('contextmenu_object', "notify");
      }
      // chat
      else if (action == "chat")
      {
        if (multiobject_count <= 1)
        {
          // link to object as chat message
          var chatcontent = "hcms_openWindow('frameset_content.php?ctrlreload=yes&" + URLparaView + "', '', 'status=yes,scrollbars=no,resizable=yes', 800, 1000);";
          
          sendtochat (chatcontent);
        }
      }
      // restore from recycle bin
      else if (action == "restore")
      {
        contextmenu_form.attributes['action'].value = 'popup_status.php';
        contextmenu_form.elements['action'].value = action;
        contextmenu_form.elements['force'].value = 'start';
        hcms_submitToPopup('contextmenu_object', "restore" + hcms_uniqid());
      }
      // delete
      else if (action == "delete")
      {
        if ((multiobject != "" || page != "" || folder != "") && hcms_permission['delete'] == true) check = confirm_delete();

        if (check == true)
        {
          contextmenu_form.attributes['action'].value = 'popup_status.php';
          contextmenu_form.elements['action'].value = action;
          contextmenu_form.elements['force'].value = 'start';
          hcms_submitToPopup('contextmenu_object', "delete" + hcms_uniqid());
        }
      }
      // cut, copy, linked copy
      else if (action == "cut" || action == "copy" || action == "linkcopy")
      {
        if (hcms_permission['rename'] == true)
        {
          contextmenu_form.attributes['action'].value = 'popup_action.php';
          contextmenu_form.elements['action'].value = action;
          hcms_submitToPopup('contextmenu_object', action + hcms_uniqid());
        } 
      }
      // paste
      else if (action == "paste")
      {
        if (site != "" && location != "" && hcms_permission['paste'] == true)
        {
          hcms_openPopup('popup_status.php?force=start&action=' + encodeURIComponent(action) + '&site=' + encodeURIComponent(site) + '&cat=' + encodeURIComponent(cat) + '&location=' + encodeURIComponent(location) + '&token=' + token, 'paste' + hcms_uniqid());    
        }
      }
      // publish, unpublish
      else if (action == "publish" || action == "unpublish")
      {
        if (hcms_permission['publish'] == true)
        {
          URLfile = "popup_publish.php";

          contextmenu_form.attributes['action'].value = URLfile;
          contextmenu_form.elements['action'].value = action;
          contextmenu_form.elements['force'].value = 'start';
          hcms_submitToPopup('contextmenu_object', "publish" + hcms_uniqid());
        }
      }
      // create favorite
      else if (action == "favorites_create")
      {
        contextmenu_form.attributes['action'].value = "popup_action.php";
        contextmenu_form.elements['action'].value = "page_favorites_create";
        hcms_submitToPopup('contextmenu_object', "fav" + hcms_uniqid());
        allow_tr_submit = false;
      }
      // delete favorite
      else if (action == "favorites_delete")
      {
        contextmenu_form.attributes['action'].value = "popup_action.php";
        contextmenu_form.elements['action'].value = "page_favorites_delete";
        hcms_submitToPopup('contextmenu_object', "fav" + hcms_uniqid());
        allow_tr_submit = false;
      }
      // check-in
      else if (action == "checkin")
      {
        contextmenu_form.attributes['action'].value = "popup_action.php";
        contextmenu_form.elements['action'].value = "page_unlock";
        hcms_submitToPopup('contextmenu_object', "checkin" + hcms_uniqid());
        allow_tr_submit = false;
      }
      // other actions
      else if (action != "")
      {
        contextmenu_form.attributes['action'].value = action;
        contextmenu_form.attributes['target'].value = "workplFrame";
        contextmenu_form.elements['action'].value = "plugin";
        contextmenu_form.submit();
        allow_tr_submit = false;
      }
    }
  }
  // Users
  else if (document.forms['contextmenu_user'])
  {
    var check = false;
    var contextmenu_form = document.forms['contextmenu_user'];
    var site = contextmenu_form.elements['site'].value;
    var group = contextmenu_form.elements['group'].value;
    var login = contextmenu_form.elements['login'].value;
    var multiobject = contextmenu_form.multiobject.value;
    var token = contextmenu_form.elements['token'].value;

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
        contextmenu_form.attributes['action'].value = "control_user_menu.php";
        contextmenu_form.attributes['target'].value = "controlFrame";
        contextmenu_form.elements['action'].value = action;
        contextmenu_form.submit();
        allow_tr_submit = false;
      }
    }
  }
  // Queue
  else if (document.forms['contextmenu_queue'])
  {
    var check = false;
    var contextmenu_form = document.forms['contextmenu_queue'];
    var site = contextmenu_form.elements['site'].value;
    var cat = contextmenu_form.elements['cat'].value;
    var location = contextmenu_form.elements['location'].value;
    var page = contextmenu_form.elements['page'].value;
    var pagename = contextmenu_form.elements['pagename'].value;
    var filetype = contextmenu_form.elements['filetype'].value;
    var queueuser = contextmenu_form.elements['queueuser'].value;
    var queue_id = contextmenu_form.elements['queue_id'].value;
    var multiobject = contextmenu_form.elements['multiobject'].value;
    var token = contextmenu_form.elements['token'].value;
    
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
        contextmenu_form.attributes['action'].value = "control_queue_menu.php";
        contextmenu_form.attributes['target'].value = "controlFrame";
        contextmenu_form.elements['action'].value = action;
        contextmenu_form.submit();
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

    // stop propagation
    if (!e) var e = window.event;
    if (e.stopPropagation != undefined) e.stopPropagation();

    // set values 
    var contexttype;

    if (folder != "") contexttype = "folder";
    else if (media != "") contexttype = "media";
    else if (page != "") contexttype = "object";
    else contexttype = "none";

    // context form
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

    // stop propagation
    if (!e) var e = window.event;
    if (e.stopPropagation != undefined) e.stopPropagation();

    // context form
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

    // stop propagation
    if (!e) var e = window.event;
    if (e.stopPropagation != undefined) e.stopPropagation();

    // context form
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

    // stop propagation
    if (!e) var e = window.event;
    if (e.stopPropagation != undefined) e.stopPropagation();

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

    // stop propagation
    if (!e) var e = window.event;
    if (e.stopPropagation != undefined) e.stopPropagation();

    // context form
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
  var unselected = false;

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
    hcms_unselectAll(true);
    unselected = true;
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

        hcms_updateControlObjectListMenu();
        hcms_updateControlUserMenu();
        hcms_updateControlQueueMenu();
        hcms_updateControlMessageMenu();
 
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
        
        hcms_updateControlObjectListMenu();
        hcms_updateControlUserMenu();
        hcms_updateControlQueueMenu();
        hcms_updateControlMessageMenu();

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

        hcms_updateControlObjectListMenu();
        hcms_updateControlUserMenu();
        hcms_updateControlQueueMenu();
        hcms_updateControlMessageMenu();
        
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

        hcms_updateControlObjectListMenu();
        hcms_updateControlUserMenu();
        hcms_updateControlQueueMenu();
        hcms_updateControlMessageMenu();

        return true;
      }
    }
    else return false;
  }
  // if no key is pressed
  else
  {
    if (unselected == false) hcms_unselectAll(false);

    var td = document.getElementById('h' + row_id + '_0');
    var links = td.getElementsByTagName('A');

    if (links[0]) var object = links[0].getAttribute('data-objectpath');
    else var object = '';

    if (object != '')
    {
      contextmenu_form.elements['multiobject'].value = '|' + object;
      document.getElementById('g' + row_id).className = 'hcmsObjectSelected';
      if (document.getElementById('objectgallery')) document.getElementById('t' + row_id).className = 'hcmsObjectSelected';

      hcms_updateControlObjectListMenu();
      hcms_updateControlUserMenu();
      hcms_updateControlQueueMenu();
      hcms_updateControlMessageMenu();

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
    var contextmenu_form = document.forms['contextmenu_user'];
    contextmenu_form.attributes['action'].value = 'control_user_menu.php';
    contextmenu_form.attributes['target'].value = 'controlFrame';
    contextmenu_form.elements['action'].value = '';
    
    if (allow_tr_submit)
    {
      contextmenu_form.submit();
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
function hcms_updateControlQueueMenu ()
{
  if (document.forms['contextmenu_queue'])
  {
    var contextmenu_form = document.forms['contextmenu_queue']; 
    contextmenu_form.attributes['action'].value = 'control_queue_menu.php';
    contextmenu_form.attributes['target'].value = 'controlFrame';
    contextmenu_form.elements['action'].value = '';
    
    if (allow_tr_submit)
    {
      contextmenu_form.submit();
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
function hcms_selectAll (updatecontrol)
{
  updatecontrol = (typeof updatecontrol !== 'undefined') ?  updatecontrol : true;

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
    if (updatecontrol) hcms_updateControlObjectListMenu();
  }
  else if (document.forms['contextmenu_user'] && document.forms['contextmenu_user'].elements['multiobject'])
  {
    document.forms['contextmenu_user'].elements['multiobject'].value = multiobject;
    if (updatecontrol) hcms_updateControlUserMenu();
  }
  else if (document.forms['contextmenu_queue'] && document.forms['contextmenu_queue'].elements['multiobject'])
  {
    document.forms['contextmenu_queue'].elements['multiobject'].value = multiobject;
    if (updatecontrol) hcms_updateControlQueueMenu();
  }
  else if (document.forms['contextmenu_message'] && document.forms['contextmenu_message'].elements['multiobject'])
  {
    document.forms['contextmenu_message'].elements['multiobject'].value = multiobject;
    if (updatecontrol) hcms_updateControlMessageMenu();
  }

  return true;
}

// unselect all objects
function hcms_unselectAll (updatecontrol)
{
  updatecontrol = (typeof updatecontrol !== 'undefined') ?  updatecontrol : true;

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
    if (updatecontrol) hcms_updateControlObjectListMenu();
  }
  else if (document.forms['contextmenu_user'] && document.forms['contextmenu_user'].elements['multiobject'].value)
  {
    document.forms['contextmenu_user'].elements['multiobject'].value = '';
    if (updatecontrol) hcms_updateControlUserMenu();
  }
  else if (document.forms['contextmenu_queue'] && document.forms['contextmenu_queue'].elements['multiobject'].value)
  {
    document.forms['contextmenu_queue'].elements['multiobject'].value = '';
    if (updatecontrol) hcms_updateControlQueueMenu();
  }
  else if (document.forms['contextmenu_message'] && document.forms['contextmenu_message'].elements['multiobject'].value)
  {
    document.forms['contextmenu_message'].elements['multiobject'].value = '';
    if (updatecontrol) hcms_updateControlMessageMenu();
  }

  return true; 
}

// contextmenu
function hcms_Contextmenu (e) 
{
  if (!e) var e = window.event;

  // show standard context menu if the download links have been activated (alt-key)
  if (activatelinks == true)
  {
    return true;
  }
  // do not show the standard context menu
  else
  {
    if (e.preventDefault != undefined) e.preventDefault();
    if (e.stopPropagation != undefined) e.stopPropagation();
    return false;
  }
}

// right mouse click (triggered by mousedown event)
function hcms_rightClickContext (e) 
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

  return false;
}

// left mouse click (triggered by click event)
function hcms_leftClickContext (e) 
{
  if (!e) var e = window.event;

  var object;
  var multiobject;
  var objectcount = 0;

  // minimize navigation for Mobile Edition
  if (is_mobile && hcms_permission['minnavframe'] == true && typeof top.minNavFrame === 'function') top.minNavFrame();

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

    // if no key is pressed and multiobject stores at least 1 object
    if (hcms_keyPressed('', e) == false && object == "" && objectcount >= 1 && is_selectarea == false)
    {
      hcms_unselectAll(true);
      hcms_resetContext();
    }
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
  if (is_dragndrop == false && hcms_keyPressed('alt', e) && document.forms['contextmenu_object'])
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
    is_dragndrop = false;
    return true;
  }

  return false;
}

function hcms_drawSelectArea ()
{
  // if alt-key is not pressed
  if (is_dragndrop == false && activatelinks == false && selectarea && x1 > 0 && y1 > 0)
  {
    is_selectarea = true;
    x3 = Math.min(x1,x2);
    x4 = Math.max(x1,x2);
    y3 = Math.min(y1,y2);
    y4 = Math.max(y1,y2);

    // remove selection marks of browser
    hcms_clearSelection();

    // enable select area if width and height are larger than 5 pixels
    if ((selectarea.style.display == '' || selectarea.style.display == 'none') && (x4 - x3) > 5 && (y4 - y3) > 5)
    {
      selectarea.style.display = 'inline';
    }
    
    // resize select area
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
  if (is_dragndrop == false && activatelinks == false && selectarea && selectarea.style.display != 'none' && x1 > 0 && y1 > 0 && x3 != 0 && y3 != 0 && x4 != 0 && y4 != 0 && (x4-x3) > 5 && (y4-y3) > 5)
  {    
    // unselect all
    hcms_unselectAll (false);

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
  if (selectarea)
  {
    // required delay since click event (leftclick) will fire after the mousup event 
    setTimeout (function() { is_selectarea = false; }, 300);
    selectarea.style.display = 'none';
  }

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
  if (is_dragndrop == true && hcms_getBrowserName() != "ie" && e.dataTransfer && typeof e.dataTransfer.setDragImage === "function")
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
    is_dragndrop = true;

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
  // dropped objects
  if (!is_mobile && hcms_getBrowserName() != "ie" && e.target && e.dataTransfer && typeof e.dataTransfer.getData === "function" && e.dataTransfer.getData('site') && e.dataTransfer.getData('location') && document.forms['contextmenu_object'])
  {
    // prevent default event on drop
    e.preventDefault();

    // find link of dropped objects
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

      hcms_submitToPopup('memory', 'drop');
    }
  }
  // prevent default event on drop
  else if (!is_mobile) e.preventDefault();
}

// prevent default event on drop
function hcms_allowDrop (e)
{
  // open upload popup for dropped files
  if (!is_mobile && hcms_getBrowserName() != "ie" && e.dataTransfer.items && document.forms['contextmenu_object'])
  {
    // prevent default event on drop
    e.preventDefault();

    // use DataTransferItemList interface to access the files
    for (var i = 0; i < e.dataTransfer.items.length; i++)
    {
      // if dropped items are files and have a mime-type (important for Chrome and MS Edge due to issue with kind property)
      if (e.dataTransfer.items[i].kind === 'file' && e.dataTransfer.items[i].type != '')
      {
        // context menu
        var contextmenu = document.forms['contextmenu_object'];
        
        var site = contextmenu.elements['site'].value;
        var cat = contextmenu.elements['cat'].value;
        var location = contextmenu.elements['location'].value;
        
        if (site && cat && location) window.top.openPopup('popup_upload_html.php?uploadmode=multi&site=' + encodeURIComponent(site) + '&cat=' + encodeURIComponent(cat) + '&location=' + encodeURIComponent(location), 'upload' + hcms_md5(location).substr(0, 13));
      }
    }
  }
}

// key events
hcms_addEvent ('keydown', document, function(e) {
  // if shortcuts are enabled
  if (hcms_permission['shortcuts'] == true)
  {
    // activate download links on Alt key
    hcms_activateLinks(e);

    // select all objects on Ctrl/Cmd+A key
    if (hcms_keyPressed('ctrl', e) && (e.key === 'a' || e.key == 'A'))
    {
      // prevent the default
      e.preventDefault();
      hcms_selectAll();
    }

    // cut objects if Ctrl/Cmd+X key is pressed
    if (hcms_keyPressed('ctrl', e) && (e.key == 'x' || e.key == 'X'))
    {
      // prevent the default
      e.preventDefault();
      hcms_createContextmenuItem('cut');
    }

    // copy objects if Ctrl/Cmd+C key is pressed
    if (hcms_keyPressed('ctrl', e) && (e.key == 'c' || e.key == 'C'))
    {
      // prevent the default
      e.preventDefault();
      hcms_createContextmenuItem('copy');
    }

    // linked copy objects if Ctrl/Cmd+Y key is pressed
    if (hcms_keyPressed('ctrl', e) && (e.key == 'y' || e.key == 'Y'))
    {
      // prevent the default
      e.preventDefault();
      hcms_createContextmenuItem('linkcopy');
    }

    // paste objects if Ctrl/Cmd+V key is pressed
    if (hcms_keyPressed('ctrl', e) && (e.key == 'v' || e.key == 'V'))
    {
      // prevent the default
      e.preventDefault();
      hcms_createContextmenuItem('paste');
    }

    // delete objects if Delete or Backspace (MacOS) key is pressed
    if (e.key == "Delete" || e.keyCode == 8 || e.keyCode == 46)
    {
      // prevent the default
      e.preventDefault();
      hcms_createContextmenuItem('delete');
    }
  }
});

hcms_addEvent ('mousemove', document, function(e) {
  hcms_getMouseXY(e);
});

hcms_addEvent ('contextmenu', document, function(e) {
  hcms_Contextmenu(e);
});

hcms_addEvent ('mousedown', document, function(e) {
  hcms_rightClickContext(e);
});

hcms_addEvent ('mouseup', document, function(e) {
  hcms_endSelectArea(e);
});

hcms_addEvent ('click', document, function(e) {
  hcms_leftClickContext(e);
});

// for alert in iframe
window.alert = top.alert;