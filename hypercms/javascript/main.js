var nn4= (document.layers);
var nn6= (document.getElementById && !document.all);
var ie4= (document.all && !document.getElementById);
var ie5= (document.all && document.getElementById);

// ------------------------ get URL paramater ----------------------------

function hcms_getURLparameter (name)
{
  return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g, '%20'))||null
}

// ---------------------- standard AJAX request --------------------------

function hcms_ajaxService (url)
{
  var xmlhttp;

  // code for IE7+, Firefox, Chrome, Opera, Safari
  if (window.XMLHttpRequest)
  {
    xmlhttp = new XMLHttpRequest();
  }
  // code for IE6, IE5
  else
  {
    xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
  }
  
  xmlhttp.onreadystatechange = function()
  {
    if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
    {
      return xmlhttp.responseText;
    }
  }

  xmlhttp.open('GET', url, true);
  xmlhttp.send();
}

// ----------------- loading content from iframe to div ---------------------

function hcms_loadPage (id, nestref, url)
{
  // layer width
  content_width = 460;
  
  if (nn4)
  {
    var lyr = (nestref)? eval('document.'+nestref+'.document.'+id) : document.layers[id]
    lyr.load(url,content_width)
  }
  else if (ie4) parent.contentFRM.location = url;
  else if (ie5 || nn6) document.getElementById('contentFRM').src = url;
}

function hcms_showPage (id)
{
  if (ie4)
  {
    document.all[id].innerHTML = parent.contentFRM.document.body.innerHTML;
  }  
  else if (nn6 || ie5)
  { 
    document.getElementById(id).innerHTML = window.frames['contentFRM'].document.getElementById('hcms_htmlbody').innerHTML;
  }
}

// -------------------------- share link functions ---------------------------

function hcms_sharelinkFacebook (url)
{
  if (url != "")
  {
    var sharelink = "https://www.facebook.com/sharer/sharer.php?u=" + encodeURIComponent(url);
    hcms_openWindow (sharelink, "", "", 800, 800);
  }
  else return false;
}

function hcms_sharelinkTwitter (url, text)
{
  if (url != "" && text != "")
  {
    var sharelink = "https://twitter.com/intent/tweet?text=" + encodeURIComponent(text) + "&source=hypercms&related=hypercms&url=" + encodeURIComponent(url);
    hcms_openWindow (sharelink, "", "", 800, 800);
  }
  else return false;
}

function hcms_sharelinkGooglePlus (url)
{
  if (url != "")
  {
    var sharelink = "https://plus.google.com/share?url=" + encodeURIComponent(url);
    hcms_openWindow (sharelink, "", "", 800, 800);
  }
  else return false;
}

function hcms_sharelinkLinkedin (url, title, summary, source)
{
  if (url != "" && title != "")
  {
    var sharelink = "https://www.linkedin.com/shareArticle?mini=true&url=" + encodeURIComponent(url) + "&title=" + encodeURIComponent(title) + "&summary=" + encodeURIComponent(summary) + "&source=" + encodeURIComponent(source);
    hcms_openWindow (sharelink, "", "", 800, 800);
  }
  else return false;
}

function hcms_sharelinkPinterest (image_url, title, description)
{
  if (image_url != "" && title != "")
  {
    var sharelink = "https://pinterest.com/pin/create/button/?url=" + encodeURIComponent(image_url) + "&media=" + encodeURIComponent(title) + "&description=" + encodeURIComponent(description);
    hcms_openWindow (sharelink, "", "", 800, 800);
  }
  else return false;
}

// --------------------------- standard functions ----------------------------

function hcms_getcontentByName(name)
{
  if (name != "" && document.getElementsByName(name))
  {
    var e = document.getElementsByName(name);
    var i;

    for (i = 0; i < e.length; i++)
    {
      if (e[i].type == "textarea" || e[i].type == "input") return e[i].value;
    }
  }
  else return false;
}

function hcms_setGlobalVar (name, value)
{
  if (name != '')
  {
    window[name] = value;
    return true;
  }
  else return false;
}

function hcms_mobileBrowser ()
{
  if (/Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent)) return true;
  else return false;
}

function hcms_iPhonePad ()
{
  var userAgent = window.navigator.userAgent;
  
  // iPad or iPhone
  if (userAgent.match(/iPad/i) || userAgent.match(/iPhone/i)) return true;
  else return false;
}

function hcms_html5file ()
{
  if (window.File && window.FileList) return true;
  else return false;
}

function hcms_geolocation ()
{
  if (navigator.geolocation)
  {
    navigator.geolocation.getCurrentPosition(hcms_geoposition);
  }
  else return false;
}

function hcms_getDocWidth ()
{
  if (self.innerHeight) // all except Explorer
  {
    return self.innerWidth;
  }
  else if (document.documentElement && document.documentElement.clientWidth) // Explorer 6 Strict Mode	
  {
    return document.documentElement.clientWidth;
  }
  else if (document.body) // other Explorers
  {
    return document.body.clientWidth;
  }
  else return false;
}

function hcms_getDocHeight ()
{
  if (self.innerHeight) // all except Explorer
  {
    return self.innerHeight;
  }
  else if (document.documentElement && document.documentElement.clientHeight) // Explorer 6 Strict Mode	
  {
    return document.documentElement.clientHeight;
  }
  else if (document.body) // other Explorers
  {
    return document.body.clientHeight;
  }
  else return false;
}

function hcms_resizeFrameWidth (leftframe, leftwidth, rightframe, rightwidth, unit)
{
  if (unit == '') unit = 'px';
  
  if (unit == 'px') var docwidth = parent.hcms_getDocWidth();
  else if (unit == '%') var docwidth = 100;
  else return false;

  if (leftwidth > 0)
  {
    rightwidth = docwidth - leftwidth;
    parent.document.getElementById(leftframe).style.width = leftwidth + unit;
    parent.document.getElementById(rightframe).style.width = rightwidth + unit;
  }
  else if (rightwidth > 0)
  {
    leftwidth = docwidth - rightwidth;
    parent.document.getElementById(leftframe).style.width = leftwidth + unit;
    parent.document.getElementById(rightframe).style.width = rightwidth + unit;
  }
}

function hcms_openWindow (theURL, winName, features, width, height)
{
  popup = window.open(theURL,winName,features + ',width=' + width + ',height=' + height);    
  popup.moveTo(screen.width/2-width/2, screen.height/2-height/2);
  popup.focus();
}

function hcms_openChat ()
{
  // standard browser (open/close chat)
  if (document.getElementById('chatLayer'))
  {
    var chatsidebar = document.getElementById('chatLayer');
            
    if (chatsidebar.style.display == "none") chatsidebar.style.display = "block";
    else chatsidebar.style.display = "none";
  }
  else if (parent.document.getElementById('chatLayer'))
  {
    var chatsidebar = parent.document.getElementById('chatLayer');
            
    if (chatsidebar.style.display == "none") chatsidebar.style.display = "block";
    else chatsidebar.style.display = "none";
  }
  // mobile browser (only open chat)
  else if (document.getElementById('chat'))
  {
    $("#chat").panel("open");
  }
  else if (parent.document.getElementById('chat'))
  {
    parent.$("#chat").panel("open");
  }
}

function hcms_findObj (n, d) 
{
  var p,i,x;  
  
  if (!d) d=document; 
  
  if ((p=n.indexOf("?"))>0&&parent.frames.length) 
  {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);
  }
  
  if (!(x=d[n])&&d.all) x=d.all[n]; 
  for (i=0; !x&&i<d.forms.length; i++) x=d.forms[i][n];
  for (i=0; !x&&d.layers&&i<d.layers.length; i++) x=hcms_findObj(n,d.layers[i].document);
  if (!x && d.getElementById) x=d.getElementById(n);
  
  return x;
}

function hcms_swapImgRestore ()
{
  var i,x,a=document.sr;
  
  for (i=0; a&&i<a.length&&(x=a[i])&&x.oSrc; i++) x.src=x.oSrc;
}

function hcms_preloadImages ()
{
  var d=document;

  if (d.images)
  {
    if (!d.p) d.p=new Array();
    var i,j=d.p.length,a=hcms_preloadImages.arguments;
    
    for (i=0; i<a.length; i++)
    {
      if (a[i].indexOf("#")!=0)
      {
        d.p[j]=new Image;
        d.p[j++].src=a[i];
      }
    }
  }
}

function hcms_swapImage ()
{
  var i,j=0,x,a=hcms_swapImage.arguments;
  
  document.sr=new Array;
  
  for(i=0;i<(a.length-2);i+=3)
  {
    if ((x=hcms_findObj(a[i]))!=null)
    {
      document.sr[j++]=x;
      if(!x.oSrc) x.oSrc=x.src;
      x.src=a[i+2];
    }
  }
}

function hcms_scanStyles (obj, prop)
{
  var inlineStyle = null; var ccProp = prop; var dash = ccProp.indexOf("-");
  
  while (dash != -1)
  {
    ccProp = ccProp.substring(0, dash) + ccProp.substring(dash+1,dash+2).toUpperCase() + ccProp.substring(dash+2);
    dash = ccProp.indexOf("-");
  }
  
  inlineStyle = eval("obj.style." + ccProp);
  if (inlineStyle) return inlineStyle;
  
  var ss = document.styleSheets;
  
  for (var x = 0; x < ss.length; x++)
  {
    var rules = ss[x].cssRules;
    
    for (var y = 0; y < rules.length; y++)
    {
      var z = rules[y].style;
    
      if (z[prop] && (rules[y].selectorText == '*[ID"' + obj.id + '"]' || rules[y].selectorText == '#' + obj.id))
      {
        return z[prop];
      }
    }
  }
  
  return "";
}

function hcms_getProp (obj, prop)
{
  if (!obj) return ("");
  
  if (prop == "L") return obj.offsetLeft;
  else if (prop == "T") return obj.offsetTop;
  else if (prop == "W") return obj.offsetWidth;
  else if (prop == "H") return obj.offsetHeight;
  else
  {
    if (typeof(window.getComputedStyle) == "undefined")
    {
      if (typeof(obj.currentStyle) == "undefined")
      {
        if (prop == "P") return hcms_scanStyles(obj,"position");
        else if (prop == "Z") return hcms_scanStyles(obj,"z-index");
        else if (prop == "V") return hcms_scanStyles(obj,"visibility");
      }
      else
      {
        if (prop == "P") return obj.currentStyle.position;
        else if (prop == "Z") return obj.currentStyle.zIndex;
        else if (prop == "V") return obj.currentStyle.visibility;
      }
    }
    else
    {
      if (prop == "P") return window.getComputedStyle(obj,null).getPropertyValue("position");
      else if (prop == "Z") return window.getComputedStyle(obj,null).getPropertyValue("z-index");
      else if (prop == "V") return window.getComputedStyle(obj,null).getPropertyValue("visibility");
    }
  }
}

// Activates dragging of moveelem when elem is dragged. Also disables any default behaviour on elem.
function hcms_drag (elem, moveelem)
{
  // Setting up needed variables
  document.hcms_move = {};
  elem.hcms_move = {}
  elem.hcms_move.elem = moveelem;
  
  // On mouse down we start dragging
  elem.onmousedown = function(e) {
  
    // Cross Browser
    var event = e || window.event;
  
    // Prevent default action
    event.preventDefault();
    
    // Setting the current moved element to the one for this element
    document.hcms_move.elem = this.hcms_move.elem;
    
    // Calculate the starting position of the move element
    var startx = parseInt(this.hcms_move.elem.style.left, 10);
    var starty = parseInt(this.hcms_move.elem.style.top, 10);
    
    if (isNaN(startx)) startx = 0;
    if (isNaN(starty)) starty = 0;

    // Calculcate the difference from current cursor to the moving element
    document.hcms_move.diffx = event.clientX - startx;
    document.hcms_move.diffy = event.clientY - starty;
    
    // Do the magic on mousemove on the document (We need document here or else the user might be able to move out of the element before the element has moved
    document.onmousemove = function(e) {
    
      // Cross Browser
      var event = e || window.event;
      
      // Moving the element to the correct position
      document.hcms_move.elem.style.left = (event.clientX - document.hcms_move.diffx)+'px';
      document.hcms_move.elem.style.top = (event.clientY - document.hcms_move.diffy)+'px';
    }
    
    // Clear everything when mouse is released
    this.onmouseup = function(e) {
      
      // Cross Browser
      var event = e || window.event;
      
      document.onmousemove = function() {}
      document.hcms_move.diffx = 0;
      document.hcms_move.diffy = 0;
      document.hcms_move.elem = 'undefined';
    }
    
  }
}

function hcms_showHideLayers () 
{
  // uses visibilty
  var i,p,v,obj;
  var args=hcms_showHideLayers.arguments;
  
  for (i=0; i<(args.length-2); i+=3)
  {
    if ((obj=hcms_findObj(args[i]))!=null)
    {
      v=args[i+2];
      
      if (obj.style)
      {
        obj=obj.style;
        v=(v=='show')?'visible':(v=='hide')?'hidden':v;
      }
      
      obj.visibility=v;
    }
  }
}

function hcms_jumpMenu (targ,selObj,restore)
{
  eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
  if (restore) selObj.selectedIndex=0;
}

function hcms_jumpMenuGo (selName,targ,restore)
{
  var selObj = hcms_findObj(selName); 
  if (selObj) hcms_jumpMenu(targ,selObj,restore);
}

function hcms_showInfo (id, sec)
{
  // uses display
  var info = document.getElementById(id);
  
  if (info)
  {
    info.style.display="inline";
    
    // enable all form elements
    var nodes = info.getElementsByTagName('*');
    
    for (var i = 0; i < nodes.length; i++)
    {
      if (nodes[i].tagName == "INPUT" || nodes[i].tagName == "SELECT" || nodes[i].tagName == "TEXTAREA" || nodes[i].tagName == "BUTTON")
      {
        nodes[i].disabled = false;
      }
    }
    
    // hide element
    if (sec > 0)
    {
      var function_hide = "hcms_hideInfo('" + id + "')";
      setTimeout (function_hide, sec);
    }
    
    return true;
  }
  else return false;
}

function hcms_hideInfo (id)
{
  // uses display
  var info = document.getElementById(id);
  
  if (info)
  {
    info.style.display="none";
    
    // disable all form elements
    var nodes = info.getElementsByTagName('*');
    
    for (var i = 0; i < nodes.length; i++)
    {
      if (nodes[i].tagName == "INPUT" || nodes[i].tagName == "SELECT" || nodes[i].tagName == "TEXTAREA" || nodes[i].tagName == "BUTTON")
      {
        nodes[i].disabled = true;
      }
    }
    
    return true;
  }
  else return false;
}

function hcms_switchInfo (id)
{
  // uses display
  var info = document.getElementById(id);
  
  if (info)
  {
    if (info.style.display == 'none')
    {
      info.style.display = 'inline';
      
      // enable all form elements
      var nodes = info.getElementsByTagName('*');
      
      for (var i = 0; i < nodes.length; i++)
      {
        if (nodes[i].tagName == "INPUT" || nodes[i].tagName == "SELECT" || nodes[i].tagName == "TEXTAREA" || nodes[i].tagName == "BUTTON")
        {
          nodes[i].disabled = false;
        }
      }
    }
    else
    {
      info.style.display = 'none';
      
      // disable all form elements
      var nodes = info.getElementsByTagName('*');
      
      for (var i = 0; i < nodes.length; i++)
      {
        if (nodes[i].tagName == "INPUT" || nodes[i].tagName == "SELECT" || nodes[i].tagName == "TEXTAREA" || nodes[i].tagName == "BUTTON")
        {
          nodes[i].disabled = true;
        }
      }
    }
    
    return true;
  }
  else return false;
}

function hcms_switchSelector (id)
{
  // uses visibilty
  var selector = document.getElementById(id);
  
  if (selector)
  {
    if (selector.style.visibility == 'hidden') selector.style.visibility = 'visible';
    else selector.style.visibility = 'hidden';
    
    return true;
  }
  else return false;
}

// ------------------------------ element style functions -------------------------------

function hcms_ElementStyle (Element, ElementClass)
{
  if (Element.className != ElementClass) Element.className = ElementClass;
}

// ------------------------------- html entities ----------------------------------

// decodes the html entities in the str (e.x.: &auml; => ä but for the corresponding charset
// uses an html element to decode
function hcms_entity_decode(str)
{
  var ta = document.createElement("textarea");
  // html element to convert special characters
  ta.innerHTML = str;
  return ta.value;
}

// encodes the html entities in the str (e.x.: ä => &auml; but for the corresponding charset
// uses an html element to encode
function hcms_entity_encode(str)
{
  var ta = document.createElement("textarea");
  // html element to convert special characters
  ta.innerHTML = str;
  return ta.innerHTML;
}

// ------------------------------ sort table data --------------------------------

// define global arrays for the 2 tables (detailed and thumbnail view)
var hcms_detailview=new Array(); 
var hcms_galleryview=new Array(); 
var is_gallery = false;

function hcms_stripHTML (_str)
{
  if(!_str)return;

  // remove all 3 types of line breaks
  _str = _str.replace(/(\r\n|\n|\r)/gm, "");

  var _reg=/<.*?>/gi;
  
  while (_str.match(_reg)!=null)
  {
    _str=_str.replace(_reg, "");
  }
  
  // replace non-breaking-space
  _str = _str.replace("&nbps;", "");
  
  return _str;
}

// sort table array hcms_detailview by column number _c
function hcms_bubbleSort (c, _ud, _isNumber)
{
  for (var i=0; i<hcms_detailview.length; i++)
  {
    for (var j=i; j<hcms_detailview.length; j++)
    {
      var _left=hcms_stripHTML(hcms_detailview[i][c]);
      var _right=hcms_stripHTML(hcms_detailview[j][c]);

      var _sign=_ud?">":"<";
      var _yes=false;
      
      if (_isNumber)
      {
         _left = _left.replace(".", "");
         _right = _right.replace(".", "");
         if(_ud && (parseInt(_left)-parseInt(_right)>0))_yes=true;
         if(!_ud && (parseInt(_left)-parseInt(_right)<0))_yes=true;
      }
      else
      {
        if(_ud && _left.toLowerCase() > _right.toLowerCase())_yes=true;
        if(!_ud && _left.toLowerCase() < _right.toLowerCase())_yes=true;
      }
      
      if (_yes)
      {
        // swap rows for detailed view
        for(var x=0; x<hcms_detailview[i].length; x++)
        {
          var _t=hcms_detailview[i][x];
          hcms_detailview[i][x]=hcms_detailview[j][x];
          hcms_detailview[j][x]=_t;
        }
        
        // swap rows for thumbnail view  
        if (is_gallery) 
        {
          _t=hcms_galleryview[i];
          hcms_galleryview[i]=hcms_galleryview[j];
          hcms_galleryview[j]=_t;
        }
      }
    }
  }
  
  return true;
}

var lastSort = null;

function hcms_sortTable (_c, _isNumber)
{
  if (typeof hcms_unselectAll == 'function') hcms_unselectAll();
  if (typeof hcms_resetContext == 'function') hcms_resetContext();
      
  is_gallery = eval (document.getElementById("t0"));  
  
  // detailed view table
  if (hcms_detailview.length <= 0)
  {
    var _o=null;
    var _i=0;
    
    while (_o=document.getElementById("g"+_i))
    {
      hcms_detailview[_i]=new Array();
      var _j=0;
      
      while (_p=document.getElementById("h"+_i+"_"+_j))
      {
        hcms_detailview[_i][_j]=_p.innerHTML;
        _j++;
      }
      _i++;
    }
  }
  
  // thumbnail view table
  if (hcms_galleryview.length <= 0 && is_gallery)
  {
    _o=null;
    _i = 0;
    
    while (_o=document.getElementById("t"+_i))
    {
      hcms_galleryview[_i]=_o.innerHTML;
      _i++;
    } 
  } 
  
  // sort both tables the same way
  hcms_bubbleSort (_c, lastSort!=_c, _isNumber);
  
  // refill tables with sorted arrays
  for (var b = 0; b < hcms_detailview.length; b++)
  {
    for (var c = 0; c < hcms_detailview[b].length; c++)
    {
      document.getElementById("h"+b+"_"+c).innerHTML=hcms_detailview[b][c];
      if (is_gallery) document.getElementById("t"+b).innerHTML=hcms_galleryview[b]; 
    }
  }
  
  if (lastSort!=_c) lastSort=_c;
  else lastSort=null;
}

// ------------------------------ sort object --------------------------------

function hcms_sortObject (object)
{
  if (typeof object === 'object')
  {
    var sorted = {},
    key, a = [];
  
    for (key in object)
    {
      if (object.hasOwnProperty(key))
      {
        a.push(key);
      }
    }
  
    a.sort();
  
    for (key = 0; key < a.length; key++)
    {
      sorted[a[key]] = object[a[key]];
    }
    
    return sorted;
  }
  else return false;
}

// ------------------------------ Video Text Track (VTT) --------------------------------

// global object for VTT records
if (typeof vtt_object !== 'object') var vtt_object = {};

// global variable for VTT delete buttons (delete button for VTT record)
if (typeof vtt_buttons == 'undefined') var vtt_buttons = '';

// global variable for VTT confirm to copy text tracks from previous language
if (typeof vtt_confirm == 'undefined') var vtt_confirm = '';

function hcms_markVTTlanguages ()
{
  // mark available languages in selectbox
  if (typeof vtt_object === 'object' && document.getElementById('vtt_language') !== null)
  {
    var selectbox = document.getElementById('vtt_language');
    
    // reset options
    for (var i = 0; i < selectbox.options.length; i++)
    {
      selectbox.options[i].setAttribute('class', '');
    }
    
    for (var langcode in vtt_object)
    {
      if (vtt_object.hasOwnProperty(langcode))
      {
        for (var i = 0; i < selectbox.options.length; i++)
        {
          if (selectbox.options[i].value == langcode)
          {
            selectbox.options[i].setAttribute('class', 'hcmsRowHead1');
          }
        }
      }
    }
    
    return true;
  }
  else return false;
}

function hcms_openVTTeditor (id)
{
  if (id != "" && document.getElementById(id) !== null)
  {
    var vtt_editor = document.getElementById(id);
  
    if (vtt_editor.style.display == 'none') vtt_editor.style.display = 'block';
    else vtt_editor.style.display = 'none';
    
    hcms_markVTTlanguages();
    return true;
  }
  else return false;
}

function hcms_changeVTTlanguage ()
{
  // language define by select
  var e = document.getElementById('vtt_language');
  var vtt_langcode_new = e.options[e.selectedIndex].value;
  
  // current language of VTT editor
  var vtt_langcode = document.getElementById('vtt_langcode').value;
  
  // save language using autosave
  if (vtt_langcode_new != "" && vtt_langcode != "" && vtt_langcode_new != vtt_langcode)
  {
    // if autosave checkbox is used (autosave feature is used)
    if (document.getElementById('autosave') !== null && document.getElementById('autosave').checked == false)
    {
      var autosave_active = document.getElementById('autosave');
      
      autosave_active.checked = true;
      autosave();
      autosave_active.checked = false;
    }
    else
    {
      autosave();
    }
  }
  
  if (vtt_langcode_new != "")
  {
    // reset language code of editor
    document.getElementById('vtt_langcode').value = vtt_langcode_new;
    
    // mark languages
    hcms_markVTTlanguages();
    
    // tracks for language exists
    if (vtt_object.hasOwnProperty(vtt_langcode_new)) 
    {
      // remove all records from editor if language records exist
      hcms_removeVTTrecords();

      // load VTT records of selected language into editor
      hcms_createVTTrecords (vtt_object[vtt_langcode_new]);
    }
    // language tracks are empty
    else if (vtt_object.hasOwnProperty(vtt_langcode) && confirm (vtt_confirm) == false)
    {
      // remove all records from editor if language records exist
      hcms_removeVTTrecords();
    }
    
    return true;
  }
  else return false;
}

function hcms_createVTTrecords (records)
{
  if (typeof records === 'object' && document.getElementById('vtt_records') !== null)
  {
    for (var time in records)
    {
      if (records.hasOwnProperty(time))
      {
        var record = records[time];

        var timestamp = time;
        timestamp = timestamp.replace(":", "");
        timestamp = timestamp.replace(":", "");
        timestamp = timestamp.replace(".", "");
          
        if (document.getElementById(timestamp) === null)
        {
          var div = document.createElement('div');
          
          div.id = timestamp;
      
          div.innerHTML = '<input type="text" name="vtt_start" value="' + record.start + '" maxlength="12" style="float:left; margin:2px 2px 0px 0px; width:58px;" readonly="readonly" />\
              <input type="text" name="vtt_stop" value="' + record.stop + '" maxlength="12" style="float:left; margin:2px 2px 0px 0px; width:58px;" readonly="readonly" />\
              <input type="text" name="vtt_text" value="' + record.text + '" maxlength="400" style="float:left; margin:2px 2px 0px 0px; width:400px;" />\
              ' + vtt_buttons + '\
              <div style="clear:both;"></div>';
      
          document.getElementById('vtt_records').appendChild(div);
        }
      }
    }
    
    // sort VTT records
    hcms_sortVTTrecords();
    
    return true;
  }
  else return false;
}

function hcms_createVTTrecord ()
{
  if (document.getElementById('vtt_create'))
  {
    var e = document.getElementById('vtt_language');
    var langcode = e.options[e.selectedIndex].value;
    var start = document.getElementById('vtt_start').value;
    var stop = document.getElementById('vtt_stop').value;
    var text = document.getElementById('vtt_text').value;
    var vtt_record = {};

    if (langcode != "" && start != "" && stop != "" && text != "")
    {
      vtt_record[start] = { start:start, stop:stop, text:text };
      return hcms_createVTTrecords (vtt_record);
    }
  }
  
  return false;
}

function hcms_sortVTTrecords ()
{
  if (document.getElementById('vtt_records') !== null)
  {
    var main = document.getElementById('vtt_records');
  
    [].map.call(main.children, Object).sort( function (a, b) {
      return +a.id.match( /\d+/ ) - +b.id.match( /\d+/ );
    }).forEach (function (elem) {
      main.appendChild(elem);
    });
  }
  else return false;
}

function hcms_removeVTTrecords ()
{
  if (document.getElementById('vtt_records') !== null)
  {
    document.getElementById('vtt_records').innerHTML = '';
    return true;
  }
  else return false;
}

function hcms_removeVTTrecord (input)
{
  if (input && document.getElementById('vtt_records'))
  {
    document.getElementById('vtt_records').removeChild(input.parentNode);
    return true;
  }
  else return false;
}

function hcms_stringifyVTTrecords ()
{
  if (document.getElementById('vtt_records') !== null)
  {
    var vtt_langcode = document.getElementById('vtt_langcode').value;
    var vtt_start = document.getElementsByName('vtt_start');
    var vtt_stop = document.getElementsByName('vtt_stop');
    var vtt_text = document.getElementsByName('vtt_text');
    var vtt_records = {};
    var vtt_string = "";

    // create VTT string
    if (vtt_start !== null && vtt_start.length > 0)
    {
      var i;
      
      for (i = 0; i < vtt_start.length; i++)
      {
        var start = vtt_start[i].value;
        var stop = vtt_stop[i].value;
        var text = vtt_text[i].value;
        
        // create VVT record as string
        vtt_string += start + " --> " + stop + "\n" + text + "\n\n";
        
        // create VTT record as object
        vtt_records[start] =  { start:start, stop:stop, text:text };
      }

      if (vtt_string != "") vtt_string = "WEBVTT\n\n" + vtt_string;
      else vtt_string = "";
    }
    
    // write VTT string to object and hidden input field
    if (vtt_langcode != "")
    {
      // create JS object for VTT (language -> starttime -> attributes)
      if (typeof vtt_records === 'object')
      {
        // remove old record
        delete vtt_object[vtt_langcode];
        // add new record
        vtt_object[vtt_langcode] = vtt_records;
      }

      // write to hidden input field
      if (document.getElementById('VTT') !== null)
      {
        // set name according to selected language
        document.getElementById('VTT').name = 'textu[VTT-' + vtt_langcode + ']';
        document.getElementById('VTT').value = vtt_string;
      }
      
      // mark languages
      hcms_markVTTlanguages();
    }
    
    return vtt_string;
  }
  else return false;
}