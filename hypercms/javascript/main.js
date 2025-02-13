// ------------------------ default values ----------------------------

// enable/disable specific functions for main and contextmenu library
var hcms_permission = new Array();
hcms_permission['rename'] = true;
hcms_permission['paste'] = true;
hcms_permission['delete'] = true;
hcms_permission['publish'] = true;
hcms_permission['shortcuts'] = true;
hcms_permission['minnavframe'] = true;

// client service
if (typeof hcms_service === 'undefined')
{
  var hcms_service = false;
}

// mobile browser
if (localStorage.getItem('is_mobile') !== null && localStorage.getItem('is_mobile') == 'false')
{
  var is_mobile = false;
  var hcms_transitioneffect = true;
}
else
{
  var is_mobile = true;
  var hcms_transitioneffect = false;
}

// ------------------------ random string ----------------------------

function hcms_uniqid ()
{
  var result = '';
  var length = 13;
  var chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  
  for (var i = length; i > 0; --i) result += chars[Math.floor(Math.random() * chars.length)];
  return result;
}

// ------------------------ Clipboard ----------------------------

function hcms_copyToClipboard (str)
{
  if (navigator && navigator.clipboard && navigator.clipboard.writeText)
  {
    // transform escaped < and >
    str = str.replace(/&lt;/g, '<');
    str = str.replace(/&gt;/g, '>');

    navigator.clipboard.writeText(str).then(function() {
      console.log('Copied text to clipboard');
    }, function(error) {
      console.error('Could not copy text with error: ', error);
    });

    return true;
  }
  else
  {
    console.log('The clipboard API is not available');
    return false;
  }
}

// ------------------------ MD5 hash ----------------------------

// JavaScript implementation of the RSA Data Security, Inc. MD5 Message
// Digest Algorithm, as defined in RFC 1321
// Copyright (C) Paul Johnston 1999 - 2000
// Updated by Greg Holt 2000 - 2001
// License to copy and use this software is granted provided that it is identified as the "RSA Data Security, Inc. MD4 Message-Digest Algorithm" in all material mentioning or referencing this software or this function.
// License is also granted to make and use derivative works provided that such works are identified as "derived from the RSA Data Security, Inc. MD4 Message-Digest Algorithm" in all material mentioning or referencing the derived work.
// RSA Data Security, Inc. makes no representations concerning either the merchantability of this software or the suitability of this software for any particular purpose. It is provided "as is" without express or implied warranty of any kind.
// These notices must be retained in any copies of any part of this documentation and/or software.

// Convert a 32-bit number to a hex string with ls-byte first
function hcms_rhex (num)
{
  var hex_chr = "0123456789abcdef";
  var str = "";

  for (j = 0; j <= 3; j++)
    str += hex_chr.charAt((num >> (j * 8 + 4)) & 0x0F) +
           hex_chr.charAt((num >> (j * 8)) & 0x0F);
  return str;
}

// Convert a string to a sequence of 16-word blocks, stored as an array.
// Append padding bits and the length, as described in the MD5 standard.
function hcms_str2blks_MD5 (str)
{
  nblk = ((str.length + 8) >> 6) + 1;
  blks = new Array(nblk * 16);
  for (i = 0; i < nblk * 16; i++) blks[i] = 0;
  for (i = 0; i < str.length; i++)
    blks[i >> 2] |= str.charCodeAt(i) << ((i % 4) * 8);
  blks[i >> 2] |= 0x80 << ((i % 4) * 8);
  blks[nblk * 16 - 2] = str.length * 8;
  return blks;
}

// Add integers, wrapping at 2^32. This uses 16-bit operations internally 
// to work around bugs in some JS interpreters.
function hcms_add (x, y)
{
  var lsw = (x & 0xFFFF) + (y & 0xFFFF);
  var msw = (x >> 16) + (y >> 16) + (lsw >> 16);
  return (msw << 16) | (lsw & 0xFFFF);
}

// Bitwise rotate a 32-bit number to the left
function hcms_rol (num, cnt)
{
  return (num << cnt) | (num >>> (32 - cnt));
}

// These functions implement the basic operation for each round of the algorithm.
function hcms_cmn (q, a, b, x, s, t)
{
  return hcms_add (hcms_rol (hcms_add (hcms_add (a, q), hcms_add (x, t)), s), b);
}
function hcms_ff (a, b, c, d, x, s, t)
{
  return hcms_cmn ((b & c) | ((~b) & d), a, b, x, s, t);
}
function hcms_gg (a, b, c, d, x, s, t)
{
  return hcms_cmn ((b & d) | (c & (~d)), a, b, x, s, t);
}
function hcms_hh (a, b, c, d, x, s, t)
{
  return hcms_cmn (b ^ c ^ d, a, b, x, s, t);
}
function hcms_ii (a, b, c, d, x, s, t)
{
  return hcms_cmn (c ^ (b | (~d)), a, b, x, s, t);
}

// Take a string and return the hex representation of its MD5
function hcms_md5 (str)
{
  x = hcms_str2blks_MD5 (str);
  a =  1732584193;
  b = -271733879;
  c = -1732584194;
  d =  271733878;

  for (i = 0; i < x.length; i += 16)
  {
    olda = a;
    oldb = b;
    oldc = c;
    oldd = d;

    a = hcms_ff (a, b, c, d, x[i+ 0], 7 , -680876936);
    d = hcms_ff (d, a, b, c, x[i+ 1], 12, -389564586);
    c = hcms_ff (c, d, a, b, x[i+ 2], 17,  606105819);
    b = hcms_ff (b, c, d, a, x[i+ 3], 22, -1044525330);
    a = hcms_ff (a, b, c, d, x[i+ 4], 7 , -176418897);
    d = hcms_ff (d, a, b, c, x[i+ 5], 12,  1200080426);
    c = hcms_ff (c, d, a, b, x[i+ 6], 17, -1473231341);
    b = hcms_ff (b, c, d, a, x[i+ 7], 22, -45705983);
    a = hcms_ff (a, b, c, d, x[i+ 8], 7 ,  1770035416);
    d = hcms_ff (d, a, b, c, x[i+ 9], 12, -1958414417);
    c = hcms_ff (c, d, a, b, x[i+10], 17, -42063);
    b = hcms_ff (b, c, d, a, x[i+11], 22, -1990404162);
    a = hcms_ff (a, b, c, d, x[i+12], 7 ,  1804603682);
    d = hcms_ff (d, a, b, c, x[i+13], 12, -40341101);
    c = hcms_ff (c, d, a, b, x[i+14], 17, -1502002290);
    b = hcms_ff (b, c, d, a, x[i+15], 22,  1236535329);    

    a = hcms_gg (a, b, c, d, x[i+ 1], 5 , -165796510);
    d = hcms_gg (d, a, b, c, x[i+ 6], 9 , -1069501632);
    c = hcms_gg (c, d, a, b, x[i+11], 14,  643717713);
    b = hcms_gg (b, c, d, a, x[i+ 0], 20, -373897302);
    a = hcms_gg (a, b, c, d, x[i+ 5], 5 , -701558691);
    d = hcms_gg (d, a, b, c, x[i+10], 9 ,  38016083);
    c = hcms_gg (c, d, a, b, x[i+15], 14, -660478335);
    b = hcms_gg (b, c, d, a, x[i+ 4], 20, -405537848);
    a = hcms_gg (a, b, c, d, x[i+ 9], 5 ,  568446438);
    d = hcms_gg (d, a, b, c, x[i+14], 9 , -1019803690);
    c = hcms_gg (c, d, a, b, x[i+ 3], 14, -187363961);
    b = hcms_gg (b, c, d, a, x[i+ 8], 20,  1163531501);
    a = hcms_gg (a, b, c, d, x[i+13], 5 , -1444681467);
    d = hcms_gg (d, a, b, c, x[i+ 2], 9 , -51403784);
    c = hcms_gg (c, d, a, b, x[i+ 7], 14,  1735328473);
    b = hcms_gg (b, c, d, a, x[i+12], 20, -1926607734);
    
    a = hcms_hh (a, b, c, d, x[i+ 5], 4 , -378558);
    d = hcms_hh (d, a, b, c, x[i+ 8], 11, -2022574463);
    c = hcms_hh (c, d, a, b, x[i+11], 16,  1839030562);
    b = hcms_hh (b, c, d, a, x[i+14], 23, -35309556);
    a = hcms_hh (a, b, c, d, x[i+ 1], 4 , -1530992060);
    d = hcms_hh (d, a, b, c, x[i+ 4], 11,  1272893353);
    c = hcms_hh (c, d, a, b, x[i+ 7], 16, -155497632);
    b = hcms_hh (b, c, d, a, x[i+10], 23, -1094730640);
    a = hcms_hh (a, b, c, d, x[i+13], 4 ,  681279174);
    d = hcms_hh (d, a, b, c, x[i+ 0], 11, -358537222);
    c = hcms_hh (c, d, a, b, x[i+ 3], 16, -722521979);
    b = hcms_hh (b, c, d, a, x[i+ 6], 23,  76029189);
    a = hcms_hh (a, b, c, d, x[i+ 9], 4 , -640364487);
    d = hcms_hh (d, a, b, c, x[i+12], 11, -421815835);
    c = hcms_hh (c, d, a, b, x[i+15], 16,  530742520);
    b = hcms_hh (b, c, d, a, x[i+ 2], 23, -995338651);

    a = hcms_ii (a, b, c, d, x[i+ 0], 6 , -198630844);
    d = hcms_ii (d, a, b, c, x[i+ 7], 10,  1126891415);
    c = hcms_ii (c, d, a, b, x[i+14], 15, -1416354905);
    b = hcms_ii (b, c, d, a, x[i+ 5], 21, -57434055);
    a = hcms_ii (a, b, c, d, x[i+12], 6 ,  1700485571);
    d = hcms_ii (d, a, b, c, x[i+ 3], 10, -1894986606);
    c = hcms_ii (c, d, a, b, x[i+10], 15, -1051523);
    b = hcms_ii (b, c, d, a, x[i+ 1], 21, -2054922799);
    a = hcms_ii (a, b, c, d, x[i+ 8], 6 ,  1873313359);
    d = hcms_ii (d, a, b, c, x[i+15], 10, -30611744);
    c = hcms_ii (c, d, a, b, x[i+ 6], 15, -1560198380);
    b = hcms_ii (b, c, d, a, x[i+13], 21,  1309151649);
    a = hcms_ii (a, b, c, d, x[i+ 4], 6 , -145523070);
    d = hcms_ii (d, a, b, c, x[i+11], 10, -1120210379);
    c = hcms_ii (c, d, a, b, x[i+ 2], 15,  718787259);
    b = hcms_ii (b, c, d, a, x[i+ 9], 21, -343485551);

    a = hcms_add (a, olda);
    b = hcms_add (b, oldb);
    c = hcms_add (c, oldc);
    d = hcms_add (d, oldd);
  }

  return hcms_rhex (a) + hcms_rhex (b) + hcms_rhex (c) + hcms_rhex (d);
}

// ------------------------ browser information ----------------------------

function hcms_getBrowserName()
{
  var name = "unknown";
  var isOpera = false;
  var isFirefox = false;
  var isSafari = false;
  var isIE = false;
  var isEdge = false;
  var isChrome = false;
  var isBlink = false;

  // Opera 8.0+
  if ((!!window.opr && !!opr.addons) || !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0)
  {
    name = "opera";
    isOpera = true;
  }

  // Firefox 1.0+
  if (typeof InstallTrigger !== 'undefined')
  {
    name = "firefox";
    isFirefox = true;
  }

  // Safari 3.0+ "[object HTMLElementConstructor]" 
  if (/constructor/i.test(window.HTMLElement) || (function (p) { return p.toString() === "[object SafariRemoteNotification]"; })(!window['safari'] || safari.pushNotification))
  {
    name = "safari";
    isSafari = true;
  }

  // Internet Explorer 6-11
  if (/*@cc_on!@*/false || !!document.documentMode)
  {
    name = "ie";
    isIE = true;
  }

  // Edge 20+
  if (!isIE && !!window.StyleMedia)
  {
    name = "edge";
    isEdge = true;
  }

  // Chrome 1+
  if (!!window.chrome && !!window.chrome.webstore)
  {
    name = "chrome";
    isChrome = true;
  }

  // Blink engine detection
  if ((isChrome || isOpera) && !!window.CSS)
  {
    name = "blink";
    isBlink =true;
  }

  return name;
}

// ------------------------ get URL parameter ----------------------------

function hcms_getURLparameter (name)
{
  return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g, '%20'))||null
}

function hcms_extractDomain (url)
{
  if (url != '')
  {
    var domain;
    
    // find and remove protocol
    if (url.indexOf("://") > -1)
    {
      domain = url.split('/')[2];
    }
    else
    {
      domain = url.split('/')[0];
    }

    // find and remove port number
    domain = domain.split(':')[0];

    return domain;
  }
  else return false;
}

// ------------------------ verify remote file ----------------------------

function hcms_remoteFileExists (url)
{
  var http = new XMLHttpRequest();

  http.open('HEAD', url, false);
  http.send();

  return http.status != 404;
}

// ------------------------ download base64 encoded data URI ----------------------------

function hcms_downloadURI (uri, filename)
{
  if (uri != '')
  {
    var link = document.createElement('a');
    
    if (typeof link.download === 'string')
    {
      link.href = uri;
      link.download = filename;
  
      // Firefox requires the link to be in the body
      document.body.appendChild(link);
      
      // simulate click
      link.click();
  
      // remove the link when done
      document.body.removeChild(link);
    }
    else if (typeof window.open !== 'undefined') 
    {
      var parts = uri.split(';');
      if (parts[1]) uri = 'data:application/octet-stream;' + parts[1];
      window.open(uri);
    }
    
    return false;
  }
  else return false;
}

// ------------------------ convert get to post request ----------------------------

function hcms_convertGet2Post (link)
{
  var parts = link.split('?');
  var action = parts[0];
  var params = parts[1].split('&');
  var form = $(document.createElement('form')).attr('action', action).attr('method','post');
  $('body').append(form);
  
  for (var i in params)
  {
    var tmp= params[i].split('=');
    var key = tmp[0], value = tmp[1];
    $(document.createElement('input')).attr('type', 'hidden').attr('name', key).attr('value', value).appendTo(form);
  }
  
  $(form).submit();
  return false;
}

// ------------------------ serialize form data ----------------------------

function hcms_serializeFormData (form_element)
{
  // Get all fields
  const fields = [].slice.call(form_element.elements, 0);

  return fields
    .map(function (ele) {
      const name = ele.name;
      const type = ele.type;

      // ignore:
      // - field that doesn't have a name
      // - disabled field
      // - file input
      // - unselected checkbox/radio
      if (!name || ele.disabled || type === 'file' || (/(checkbox|radio)/.test(type) && !ele.checked))
      {
        return '';
      }

      // multiple select
      if (type === 'select-multiple')
      {
        return ele.options
          .map(function (opt) {
              return opt.selected ? `${name}=${encodeURIComponent(opt.value)}` : '';
          })
          .filter(function (item) {
              return item;
          })
          .join('&');
      }

      return `${name}=${encodeURIComponent(ele.value)}`;
  })
  .filter(function (item) {
      return item;
  })
  .join('&');
};

// ---------------------------- post form data using AJAX ----------------------------

function hcms_postFormData (id_form, id_savelayer, id_messagelayer)
{
  if (id_form == "" || document.getElementById(id_form) == null) return false;

  return new Promise(function (resolve, reject) {

    // form element
    var form_element = document.getElementById(id_form);

    // serialize form data
    const params = hcms_serializeFormData (form_element);

    if (id_savelayer != "") hcms_showLayer (id_savelayer);

    // create new AJAX request
    const request= new XMLHttpRequest();
    request.open('POST', form_element.action, true);

    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

    // handle the events
    request.onload = function () {
      if (request.status >= 200 && request.status < 400)
      {
        var data = JSON.parse(request.responseText);

        // results
        if (data)
        {
          // show message on error
          if (id_messagelayer != "" && document.getElementById(id_messagelayer))
          {
            if (data['success'] == false && data['message'] != "")
            {
              hcms_showLayer (id_messagelayer);
              document.getElementById(id_messagelayer + '_text').innerHTML = data['message'];
            }
          }

          if (id_savelayer != "") hcms_hideLayer (id_savelayer);
        }
      }
    };

    request.onerror = function () {
      console.log('Internal Server Error');

      if (id_messagelayer != "" && document.getElementById(id_messagelayer))
      {
        hcms_showLayer (id_messagelayer);
        document.getElementById(id_messagelayer + '_text').innerHTML = "Internal Server Error";
      }
    };

    // send
    request.send(params);
  });
}

// ----------------------------- async AJAX request --------------------------------

function hcms_ajaxService (url, mimetype)
{
  mimetype = (typeof mimetype !== 'undefined') ? mimetype : '';
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

  // true makes the request asynchronous
  xmlhttp.open('GET', url, true);
  
  // set mime-type
  if (mimetype != null)
  {
    if (xmlhttp.overrideMimeType)
    {
      xmlhttp.overrideMimeType(mimetype);
    }
  }
  
  xmlhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200)
    {
      return xmlhttp.responseText;
    }
  }

  xmlhttp.onerror = function (e) {
    console.error (xmlhttp.statusText);
  };

  xmlhttp.send();
}

// ----------------------------- sync AJAX request --------------------------------

function hcms_syncajaxService (url, mimetype)
{
  mimetype = (typeof mimetype !== 'undefined') ? mimetype : '';
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

  // false makes the request synchronous
  xmlhttp.open('GET', url, false);

  // set mime-type
  if (mimetype != null)
  {
    if (xmlhttp.overrideMimeType)
    {
      xmlhttp.overrideMimeType(mimetype);
    }
  }

  xmlhttp.send(null);

  if (xmlhttp.status == 200 && xmlhttp.readyState == 4)
  {
    return xmlhttp.responseText;
  }
}

// ------------------------- loading content from iframe to div ---------------------------

function hcms_loadPage (id, url)
{
  if (id != "" && document.getElementById(id))
  {
    document.getElementById(id).src = url;
  }
}

function hcms_showPage (id_frame, id_layer)
{
  if (id_frame != "" && id_layer != "" && document.getElementById(id_layer))
  {
    if (window.frames[id_frame]) document.getElementById(id_layer).innerHTML = window.frames[id_frame].document.getElementById('hcms_htmlbody').innerHTML;
    else if (document.getElementById(id_frame)) document.getElementById(id_layer).innerHTML = document.getElementById(id_frame).document.getElementById('hcms_htmlbody').innerHTML;
  }
}

// -------------------------------- share link functions ---------------------------------

function hcms_sharelinkFacebook (url, title, description)
{
  if (url != "")
  {
    var sharelink = "https://www.facebook.com/sharer/sharer.php?u=" + encodeURIComponent(url) + "&title=" + encodeURIComponent(title) + "&quote=" + encodeURIComponent(description) + "&description=" + encodeURIComponent(description);
    console.log('Share with Facebook');
    hcms_openWindow (sharelink, "", "", 800, 800);
  }
  else return false;
}

function hcms_sharelinkTwitter (url, text)
{
  if (url != "")
  {
    var sharelink = "https://twitter.com/intent/tweet?text=" + encodeURIComponent(text) + "&source=hypercms&related=hypercms&url=" + encodeURIComponent(url);
    console.log('Share with Twitter/X');
    hcms_openWindow (sharelink, "", "", 800, 800);
  }
  else return false;
}

function hcms_sharelinkLinkedin (url, title, summary, source)
{
  if (url != "")
  {
    var sharelink = "https://www.linkedin.com/shareArticle?mini=true&url=" + encodeURIComponent(url) + "&title=" + encodeURIComponent(title) + "&summary=" + encodeURIComponent(summary) + "&source=" + encodeURIComponent(source);
    console.log('Share with Linkedin');
    hcms_openWindow (sharelink, "", "", 800, 800);
  }
  else return false;
}

function hcms_sharelinkPinterest (image_url, description)
{
  if (image_url != "")
  {
    var sharelink = "https://pinterest.com/pin/create/button/?url=" + encodeURIComponent(hcms_extractDomain (image_url)) + "&media=" + encodeURIComponent(image_url) + "&description=" + encodeURIComponent(description);
    console.log('Share with Pinterest');
    hcms_openWindow (sharelink, "", "", 800, 800);
  }
  else return false;
}

// ---------------------------------------- translate text function ---------------------------------------

function hcms_translateText (sourceText, sourceLang, targetLang)
{
  if (sourceText != "" && targetLang != "")
  {
    var translatedText = "";
    
    if (sourceLang == "") sourceLang = 'auto';

    // wait
    hcms_sleep (500);

    // remove html tags
    sourceText = hcms_stripTags (sourceText);

    var url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=" 
              + sourceLang + "&tl=" + targetLang + "&dt=t&q=" + encodeURIComponent(sourceText);

    var xmlhttp = new XMLHttpRequest();

    // synchronous request
    xmlhttp.open('POST', url, false);
    xmlhttp.send();

    var json = xmlhttp.responseText;

    // correct empty entries between commas (need to be used twice!)
    json = json.replace(/,,/g, ",\"\",");
    json = json.replace(/,,/g, ",\"\",");

    var result = JSON.parse(json);    
    var result = result[0];

    for (var i=0; i<result.length; i++)
    {
      if (result[i][0] != "")
      {
        if (translatedText != "" && translatedText.slice(-2) != "\n") translatedText = translatedText + " ";
        translatedText = translatedText + result[i][0];
      }
    }

    if (translatedText != "") return translatedText;
    else return false;
  }
  else return false;
}

function hcms_translateRichTextField (ckeditor_id, sourcelang_id, targetlang_id)
{
  var sourceText = "";
  var sourceLang = "";
  var targetLang = "";
  
  if (CKEDITOR.instances[ckeditor_id]) sourceText = CKEDITOR.instances[ckeditor_id].getData();
  if (document.getElementById(sourcelang_id)) sourceLang = document.getElementById(sourcelang_id).value;
  if (document.getElementById(targetlang_id)) targetLang = document.getElementById(targetlang_id).value;

  if (sourceText != "" && targetLang != "")
  {
    var translated = hcms_translateText (sourceText, sourceLang, targetLang);
  
    if (translated != "")
    {
      CKEDITOR.instances[ckeditor_id].setData(translated);
      return true;
    }
  }
  
  return false;
}

function hcms_translateTextField (textarea_id, sourcelang_id, targetlang_id)
{
  var sourceText = "";
  var sourceLang = "";
  var targetLang = "";
  
  if (document.getElementById(textarea_id)) sourceText = document.getElementById(textarea_id).value;
  if (document.getElementById(sourcelang_id)) sourceLang = document.getElementById(sourcelang_id).value;
  if (document.getElementById(targetlang_id)) targetLang = document.getElementById(targetlang_id).value;

  if (sourceText != "" && targetLang != "")
  {
    var translated = hcms_translateText (sourceText, sourceLang, targetLang);

    if (translated != "")
    {
      document.getElementById(textarea_id).value = translated;
      return true;
    }
  }
  
  return false;
}

// ---------------------------------------- table of content ---------------------------------------

function hcms_createTOC (content_id, toc_id, maxLevel)
{
  if (content_id != '' && toc_id != '' && maxLevel > 0)
  {
    var toc = "";
    var level = 0;

    document.getElementById(content_id).innerHTML =
      document.getElementById(content_id).innerHTML.replace(
        /<h([\d])>([^<]+)<\/h([\d])>/gi,
        function (str, openLevel, titleText, closeLevel)
        {
          if (openLevel > maxLevel) return "<h" + openLevel + ">" + titleText + "</h" + closeLevel + ">";

          if (openLevel != closeLevel)
          {
            return str + ' - ' + openLevel;
          }

          if (openLevel > level)
          {
            toc += (new Array(openLevel - level + 1)).join("<ul>");
          }
          else if (openLevel < level)
          {
            toc += (new Array(level - openLevel + 1)).join("</ul>");
          }

          level = parseInt(openLevel);

          var anchor = titleText.replace(/ /g, "_");
          toc += "<li><a href=\"#" + anchor + "\">" + titleText + "</a></li>";

          return "<h" + openLevel + "><a name=\"" + anchor + "\">" + titleText + "</a></h" + closeLevel + ">";
        }
      );

    if (level)
    {
      toc += (new Array(level + 1)).join("</ul>");
    }

    document.getElementById(toc_id).innerHTML += toc;
  }
  else return false;
}

// ---------------------------------------- color conversion ---------------------------------------

function hcms_Hex2Rgb (hex)
{
  // Expand shorthand form (e.g. "03F") to full form (e.g. "0033FF")
  var shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;

  hex = hex.replace(shorthandRegex, function(m, r, g, b) {
    return r + r + g + g + b + b;
  });

  var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);

  return result ? {
    r: parseInt(result[1], 16),
    g: parseInt(result[2], 16),
    b: parseInt(result[3], 16)
  } : null;
}

function hcms_Rgb2Cmyk (r, g, b)
{
  var computedC = 0;
  var computedM = 0;
  var computedY = 0;
  var computedK = 0;
 
  //remove spaces from input RGB values, convert to int
  var r = parseInt ((''+r).replace(/\s/g,''), 10); 
  var g = parseInt ((''+g).replace(/\s/g,''), 10); 
  var b = parseInt ((''+b).replace(/\s/g,''), 10); 
 
  if (r==null || g==null || b==null || isNaN(r) || isNaN(g)|| isNaN(b))
  {
    console.log ('Please enter numeric RGB values!');
    return;
  }

  if (r<0 || g<0 || b<0 || r>255 || g>255 || b>255)
  {
    console.log ('RGB values must be in the range 0 to 255.');
    return;
  }
 
  // BLACK
  if (r==0 && g==0 && b==0)
  {
   computedK = 1;
   return [0,0,0,1];
  }
 
  computedC = 1 - (r/255);
  computedM = 1 - (g/255);
  computedY = 1 - (b/255);
 
  var minCMY = Math.min(computedC, Math.min(computedM,computedY));
  computedC = Math.round((computedC - minCMY) / (1 - minCMY) * 100) ;
  computedM = Math.round((computedM - minCMY) / (1 - minCMY) * 100) ;
  computedY = Math.round((computedY - minCMY) / (1 - minCMY) * 100 );
  computedK = Math.round(minCMY * 100);
 
  return {c: computedC, m: computedM, y: computedY, k: computedK};
}

// ---------------------------------------- standard functions ---------------------------------------

function hcms_sleep (milliseconds)
{
  var start = new Date().getTime();

  for (var i = 0; i < 1e7; i++)
  {
    if ((new Date().getTime() - start) > milliseconds) break;
  }
}

function hcms_getImageSize (imgSrc)
{
  var newImg = new Image();

  // triggers afte image has been loaded
  newImg.onload = function() {
    var height = newImg.height;
    var width = newImg.width;
    return 'width:'+width+'px; height:'+height+'px;';
  }

  // set image src
  newImg.src = imgSrc;
}

// remove element form array
function hcms_arrayRemoveValue (array, value)
{
  var index = array.indexOf(value);

  if (index !== -1)
  {
    array.splice(index, 1);
  }

  return array;
}

// unique array
function hcms_arrayUnique_helper (value, index, self)
{ 
  return self.indexOf(value) === index;
}

function hcms_arrayUnique (array)
{ 
  return array.filter(hcms_arrayUnique_helper);
}

function hcms_enterKeyPressed (event)
{
  // Cross Browser
  if (!event) var event = e || window.event;
   
  if (event.which == 13 || event.keyCode == 13) return true;
  else return false;
}

function hcms_stripTags (html)
{    
   var tmp = document.createElement("DIV");
   tmp.innerHTML = html;
   return tmp.textContent || tmp.innerText || "";
}

function hcms_getcontentByName (name)
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
  // detection based on user agent
  var check = false;
  (function(a){if(/android|playbook|silk|(bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino|android|ipad|playbook|silk/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) check = true;})(navigator.userAgent||navigator.vendor||window.opera);
  return check;
}

function hcms_iOS ()
{
  var userAgent = window.navigator.userAgent;
  
  // iPod, iPad or iPhone
  if (userAgent.match(/iPad/i) || userAgent.match(/iPhone/i) || userAgent.match(/iPod/i)) return true;
  else return false;
}

function hcms_html5file ()
{
  if (window.File && window.FileList) return true;
  else return false;
}

function hcms_getViewportWidth ()
{
  // for mobile devices
  if (hcms_mobileBrowser())
  {
    var ratio = window.devicePixelRatio || 1;
    var screenwidth = screen.width;
  }
  // for desktop screens
  else
  {
    var screenwidth = window.innerWidth;
  }

  // return logical screen width
  if (screenwidth > 0) return screenwidth;
  else return false;
}

function hcms_setViewportScale ()
{
  var screenwidth = hcms_getViewportWidth ();

  if (screenwidth <= 480)
  { 
    document.querySelector("meta[name=viewport]").setAttribute('content', 'width=device-width, initial-scale=0.57, maximum-scale=1.0, user-scalable=1');
  }
  else if (screenwidth < 1024)
  { 
    document.querySelector("meta[name=viewport]").setAttribute('content', 'width=device-width, initial-scale=0.86, maximum-scale=1.0, user-scalable=1');
  }
  else if (screenwidth >= 1024)
  { 
    document.querySelector("meta[name=viewport]").setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0');
  }
}

function hcms_getBrightness (color)
{
  if (color.length >= 6)
  {
    if (color.length == 7) color = color.substring(1);

    var R = parseInt (color.substring(0,2), 16);
    var G = parseInt (color.substring(2,4), 16);
    var B = parseInt (color.substring(4,6), 16);

    // Background Brightness < 130 => Textcolor '#FFFFFF' else '#000000'
    return Math.sqrt (R * R * .241 + G * G * .691 + B * B * .068);
  }
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

function hcms_getLocation (path)
{
  if (path != "") return path.substring (0, path.lastIndexOf('/')+1);
  else return false;
}

function hcms_getObject (path)
{
  if (path != "") return path.substring (path.lastIndexOf('/')+1, path.length);
  else return false;
}

var hcms_style = "";

function hcms_minMaxLayer (id)
{
  var element = document.getElementById(id);

  if (element)
  {
    // minimize if max
    if (element.style.width == '90%')
    {
      element.style.cssText = hcms_style + ' transition:width 0.3s;';
    }
    // maximize if min
    else
    {
      hcms_style = element.style.cssText;
      element.style.cssText = 'position:fixed; z-index:9999; width:90%; height:90%; top:50%; left:50%; transform:translate(-50%, -50%); transition:width 0.3s;';
    }
  }
}

var hcms_windowcounter = 0;

function hcms_openWindow (theURL, winName, features, width, height)
{
  if (width < 1) width = 640;
  if (height < 1) height = 480;
  if (features == '') features = "location=0,menubar=0";

  // wait
  hcms_sleep (200);

  var popup = window.open(theURL, winName, features + ',width=' + width + ',height=' + height);

  // use different window positioning if width and height matches the size for object windows
  if (typeof popup.moveTo !== 'undefined')
  {
    if (localStorage.getItem('windowwidth') !== null && localStorage.getItem('windowwidth') == width && localStorage.getItem('windowheight') !== null && localStorage.getItem('windowheight') == height)
    {
      hcms_windowcounter++;
      var offsetX = 35 * hcms_windowcounter;
      var offsetY = 25 * hcms_windowcounter;
      if (screen.width > width * 1.8) offsetX = offsetX + 280;
      popup.moveTo(offsetX, offsetY);
    }
    // center window
    else if (screen.width > width && screen.height > height)
    {
      popup.moveTo(screen.width/2 - width/2, screen.height/2 - height/2);
    }
  }

  popup.focus();
}

function hcms_openChat ()
{
  // chat sidebar
  if (document.getElementById('chatLayer'))
  {
    var chatsidebar = document.getElementById('chatLayer');
  }
  else if (parent.document.getElementById('chatLayer'))
  {
    var chatsidebar = parent.document.getElementById('chatLayer');
  }
  else var chatsidebar = false;

  // toggle chat sidebar
  if (chatsidebar)
  {
    chatsidebar.style.transition = "0.3s";

    if (chatsidebar.style.right == "0px") chatsidebar.style.right = "-320px";
    else chatsidebar.style.right = "0px";
  }
}

function hcms_openSubMenu (height)
{
  if (document.getElementById('controlFrame'))
  {
    // set height
    if (is_mobile) height = (typeof height !== 'undefined') ? height : 172;
    else height = (typeof height !== 'undefined') ? height : 100;
    
    if (hcms_transitioneffect == true) document.getElementById('controlFrame').style.transition = "0.3s";
    document.getElementById('controlFrame').style.height = height + 'px';

    if (document.getElementById('mainLayer'))
    {
      if (hcms_transitioneffect == true) document.getElementById('mainLayer').style.transition = "0.3s";
      document.getElementById('mainLayer').style.top = height + 'px';
    }

    if (document.getElementById('sidebarLayer'))
    {
      if (hcms_transitioneffect == true) document.getElementById('sidebarLayer').style.transition = "0.3s";
      document.getElementById('sidebarLayer').style.top = height + 'px';
    }
  }
}

function hcms_closeSubMenu (height, minwidth)
{
  if ( document.getElementById('controlFrame'))
  {
    var width = screen.width;

    // set height
    if (is_mobile) height = (typeof height !== 'undefined') ? height : 62; 
    else height = (typeof height !== 'undefined') ? height : 78;

    // set min width
    minwidth = (typeof minwidth !== 'undefined') ? minwidth : 370;

    // force height on devices with small screens
    if (width < minwidth && height < 100) height = 100;
    
    if (hcms_transitioneffect == true) document.getElementById('controlFrame').style.transition = "0.3s";
    document.getElementById('controlFrame').style.height = height + 'px';

    if (document.getElementById('mainLayer'))
    {
      if (hcms_transitioneffect == true) document.getElementById('mainLayer').style.transition = "0.3s";
      document.getElementById('mainLayer').style.top = height + 'px';
    }

    if (document.getElementById('sidebarLayer'))
    {
      if (hcms_transitioneffect == true) document.getElementById('sidebarLayer').style.transition = "0.3s";
      document.getElementById('sidebarLayer').style.top = height + 'px';
    }
  }
}

function hcms_findObj (n, d) 
{
  var p, i, x;  

  if (!d) d = document; 

  if ((p=n.indexOf("?"))>0 && parent.frames.length) 
  {
    d = parent.frames[n.substring(p+1)].document;
    n = n.substring(0,p);
  }

  if (!(x = d[n]) && d.all) x = d.all[n]; 
  for (i=0; !x && i<d.forms.length; i++) x = d.forms[i][n];
  for (i=0; !x && d.layers && i<d.layers.length; i++) x = hcms_findObj(n,d.layers[i].document);
  if (!x && d.getElementById) x=d.getElementById(n);

  return x;
}

function hcms_swapImgRestore ()
{
  var i, x, a = document.sr;

  for (i=0; a && i<a.length && (x=a[i]) && x.oSrc; i++) x.src = x.oSrc;
}

function hcms_preloadImages ()
{
  var d = document;

  if (d.images)
  {
    if (!d.p) d.p = new Array();
    var i, j = d.p.length, a = hcms_preloadImages.arguments;

    for (i=0; i<a.length; i++)
    {
      if (a[i].indexOf("#") != 0)
      {
        d.p[j] = new Image;
        d.p[j++].src=a[i];
      }
    }
  }
}

function hcms_swapImage ()
{
  var i, j = 0, x, a = hcms_swapImage.arguments;

  document.sr = new Array;

  for (i=0; i<(a.length-2); i+=3)
  {
    if ((x=hcms_findObj(a[i]))!=null)
    {
      document.sr[j++] = x;
      if (!x.oSrc) x.oSrc = x.src;
      x.src = a[i+2];
    }
  }
}

function hcms_scanStyles (obj, prop)
{
  var inlineStyle = null;
  var ccProp = prop;
  var dash = ccProp.indexOf("-");
  
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

// ----------------------------------------  jump to link ---------------------------------------

function hcms_jumpMenu (target, selObj, restore)
{
  eval (target + ".location='" + selObj.options[selObj.selectedIndex].value + "'");
  if (restore) selObj.selectedIndex = 0;
}

function hcms_jumpMenuGo (selName, target, restore)
{
  var selObj = hcms_findObj (selName); 
  if (selObj) hcms_jumpMenu (target, selObj, restore);
}


// ---------------------------------------- select box ---------------------------------------

function hcms_moveFromToSelect (fbox, tbox, sort)
{
  sort = (typeof sort !== 'undefined') ? sort : true;
  var arrFbox = new Array();
  var arrTbox = new Array();
  var arrLookup = new Array();
  var i;

  if (tbox.options)
  {
    for (i = 0; i < tbox.options.length; i++)
    {
      arrLookup[tbox.options[i].text] = tbox.options[i].value;
      arrTbox[i] = tbox.options[i].text;
    }
  }

  var fLength = 0;
  var tLength = arrTbox.length;

  if (fbox.options)
  {
    for (i = 0; i < fbox.options.length; i++)
    {
      arrLookup[fbox.options[i].text] = fbox.options[i].value;

      if (fbox.options[i].selected && fbox.options[i].value != "")
      {
        arrTbox[tLength] = fbox.options[i].text;
        tLength++;
      }
      else
      {
        arrFbox[fLength] = fbox.options[i].text;
        fLength++;
      }
    }
  }

  if (sort == true)
  {
    arrFbox.sort();
    arrTbox.sort();
  }

  fbox.length = 0;
  tbox.length = 0;
  var c;

  for (c = 0; c < arrFbox.length; c++)
  {
    var no = new Option();
    no.value = arrLookup[arrFbox[c]];
    no.text = arrFbox[c];
    fbox[c] = no;
  }

  for (c = 0; c < arrTbox.length; c++)
  {
    var no = new Option();
    no.value = arrLookup[arrTbox[c]];
    no.text = arrTbox[c];
    tbox[c] = no;
  }

  if (sort == false && tbox.length > 0) tbox.options[tbox.options.length - 1].selected = true;
}

function hcms_insertOption (select, newtext, newvalue, allowduplicates)
{
  allowduplicates = (typeof allowduplicates !== 'undefined') ? allowduplicates : true;
  newentry = new Option (newtext, newvalue, false, true);
  var i;
  
  if (select.length > 0)
  {  
    var position = -1;

    for (i=0; i<select.length; i++)
    {
      if (select.options[i].selected) position = i;
      if (allowduplicates == false && select.options[i].value == newvalue) return false;
    }
    
    if (position != -1)
    {
      select.options[select.length] = new Option();
    
      for (i=select.length-1; i>position; i--)
      {
        select.options[i].text = select.options[i-1].text;
        select.options[i].value = select.options[i-1].value;
      }
      
      select.options[position+1] = newentry;
    }
    else select.options[select.length] = newentry;
  }
  else select.options[select.length] = newentry;
}

function hcms_moveSelected (select, down)
{
  if (select.selectedIndex != -1)
  {
    if (down)
    {
      if (select.selectedIndex != select.options.length - 1)
        var i = select.selectedIndex + 1;
      else
        return;
    }
    else
    {
      if (select.selectedIndex != 0)
        var i = select.selectedIndex - 1;
      else
        return;
    }

    var swapOption = new Object();

    swapOption.text = select.options[select.selectedIndex].text;
    swapOption.value = select.options[select.selectedIndex].value;
    swapOption.selected = select.options[select.selectedIndex].selected;

    for (var property in swapOption) select.options[select.selectedIndex][property] = select.options[i][property];
    for (var property in swapOption) select.options[i][property] = swapOption[property];
  }
}

function hcms_deleteSelected (select)
{
  if (select.length > 0)
  {
    for (var i=0; i<select.length; i++)
    {
      if (select.options[i].selected == true) select.remove(i);
    }
  }
}

function hcms_selectAllOptions (select)
{
  if (select.length > 0)
  {
    for (var i=0; i<select.options.length; i++)
    {
      select.options[i].selected = true;
    }
  }
}

// ----------------------------------------  drag layer ---------------------------------------

function hcms_dragLayer (elem, id_connection)
{
  id_connection = (typeof id_connection !== 'undefined') ? id_connection : '';
  var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;

  if (elem === null) return false;

  // define id of layer header
  if (elem && elem.id != '') var id_header = elem.id + "header";
  else var id_header = '';

  if (id_header != '' && document.getElementById(id_header))
  {
    // if present, the header is where you move the DIV from
    document.getElementById(id_header).onmousedown = dragMouseDown;
  }
  else
  {
    // otherwise, move the DIV from anywhere inside the DIV
    elem.onmousedown = dragMouseDown;
  }

  function dragMouseDown(e)
  {    
    e = e || window.event;
    e.preventDefault();

    // turn transition effects off due to slowdown
    elem.style.transition = 'none';

    // get the mouse cursor position at startup
    pos3 = e.clientX;
    pos4 = e.clientY;
    document.onmouseup = closeDragElement;

    // call a function whenever the cursor moves
    document.onmousemove = elementDrag;
  }

  function elementDrag(e)
  {
    e = e || window.event;
    e.preventDefault();

    // calculate the new cursor position
    pos1 = pos3 - e.clientX;
    pos2 = pos4 - e.clientY;
    pos3 = e.clientX;
    pos4 = e.clientY;

    // set the element's new position:
    elem.style.top = (elem.offsetTop - pos2) + "px";
    elem.style.left = (elem.offsetLeft - pos1) + "px";

    // redraw connections based on the affected connection id
    if (id_connection != '' && typeof hcms_connections_repaintConnections === 'function')
    {
      hcms_connections_repaintConnections (id_connection);
    }
  }

  function closeDragElement()
  {
    // stop moving when mouse button is released
    document.onmouseup = null;
    document.onmousemove = null;
  }
}

// Activates dragging of moveelem when elem is dragged and updates all connections that include the connection id (in from or to, connection id)
function hcms_dragLayers (elem, moveelem, id_connection)
{
  id_connection = (typeof id_connection !== 'undefined') ? id_connection : '';

  if (elem === null) return false;

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

    // Support left mouse click only
    if (hcms_isLeftClick(event))
    {
      // Setting the current moved element to the one for this element
      document.hcms_move.elem = this.hcms_move.elem;

      // turn transition effects off due to slowdown
      elem.style.transition = 'none';
      document.hcms_move.elem.style.transition = 'none';

      // Calculate the starting position of the move element
      var startx = parseInt(this.hcms_move.elem.style.left, 10);
      var starty = parseInt(this.hcms_move.elem.style.top, 10);

      if (isNaN(startx)) startx = 0;
      if (isNaN(starty)) starty = 0;

      // Calculcate the difference from current cursor to the moving element
      document.hcms_move.diffx = event.clientX - startx;
      document.hcms_move.diffy = event.clientY - starty;
    
      // on mousemove on the document (We need document here or else the user might be able to move out of the element before the element has moved)
      document.onmousemove = function(e) {

        // Cross Browser
        var event = e || window.event;

        // Moving the element to the correct position
        document.hcms_move.elem.style.left = (event.clientX - document.hcms_move.diffx) + 'px';
        document.hcms_move.elem.style.top = (event.clientY - document.hcms_move.diffy) + 'px';

        // redraw connections based on the affected connection id
        if (id_connection != '' && typeof hcms_connections_repaintConnections === 'function')
        {
          hcms_connections_repaintConnections (id_connection);
        }
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
}

// ---------------------------------- show/hide layer with transition effect ---------------------------------

function hcms_isHiddenLayer (id)
{
  var info = document.getElementById(id);
  
  if (info)
  {
    if (info.style.display == 'none') return true;
    else if (info.style.visibility == 'hidden') return true;
  }

  return false;
}

function hcms_slideDownLayer (id, offset) 
{
  // default value
  offset = typeof offset !== 'undefined' ? offset : "0px";

  var layer = document.getElementById(id);
  
  if (layer)
  {
    // transition
    if (hcms_transitioneffect) layer.style.transition = 'height 0.3s linear';
    layer.style.overflow = 'hidden';
    layer.style.visibility = 'visible';

    if (layer.style.height == offset + "px")
    {
      layer.style.height = 'auto';
    }
    else
    {
      layer.style.height = offset + "px";
    }
  }
}

function hcms_showHideLayers () 
{
  // uses visibilty (nested form data will be submitted)
  var i, p, z, v, o, obj;
  var args = hcms_showHideLayers.arguments;

  for (i=0; i<(args.length-2); i+=3)
  {
    // 1st argument
    if ((obj = hcms_findObj (args[i])) != null)
    {
      // z-index (2nd argument)
      z = args[i+1];
      // visibility (3rd argument)
      v = args[i+2];

      if (obj.style)
      {
        // transition
        if (hcms_transitioneffect)
        {
          obj.style.transition = 'all 0.3s linear';

          // opacity
          o = (v == 'show') ? '1' : (v == 'hide') ? '0' : '0';
          obj.style.opacity = o;
          // fix for MS IE
          obj.style.filter = 'alpha(opacity=' + (o * 100) + ')';
        }

        // z-index
        if (z != '')
        {
          z = (v == 'show') ? z : (v == 'hide') ? '-1' : '-1';
          obj.style.zIndex = z;
        }

        // visibility
        v = (v == 'show') ? 'visible' : (v == 'hide') ? 'hidden' : v;
        obj.style.visibility = v; 
      }
    }
  }
}

function hcms_displayLayers () 
{
  // uses display (included form data will not be submitted if display is 'none')
  var i, p, z, v, o, obj;
  var args = hcms_displayLayers.arguments;

  for (i=0; i<(args.length-2); i+=3)
  {
    // 1st argument
    if ((obj = hcms_findObj (args[i])) != null)
    {
      // z-index (2nd argument)
      z = args[i+1];
      // display (3rd argument)
      v = args[i+2];

      if (obj.style)
      {
        // transition
        if (hcms_transitioneffect)
        {
          obj.style.transition = 'all 0.3s linear';

          // opacity
          o = (v == 'show') ? '1' : (v == 'hide') ? '0' : '0';

          obj.style.opacity = o;
          // fix for MS IE
          obj.style.filter = 'alpha(opacity=' + o * 100 + ')';
        }

        // z-index
        if (z != '')
        {
          z = (v == 'show') ? z : (v == 'hide') ? '-1' : '-1';
          obj.style.zIndex = z;
        }

        // display
        v = (v == 'show') ? 'inline' : (v == 'hide') ? 'none' : v;
        obj.style.display = v; 
      }
    }
  }
}

// ---------------------------- simple show/hide hide layer ----------------------------

function hcms_showLayer (id_element)
{
  // overlay while saving
  if (document.getElementById(id_element))
  {
    document.getElementById(id_element).style.display="block";
  }
}

function hcms_hideLayer (id_element)
{
  // overlay while saving
  if (document.getElementById(id_element))
  {
    document.getElementById(id_element).style.display="none";
  }
}

// ----------------------------  show/hide form layer ----------------------------

function hcms_showFormLayer (id, sec)
{
  // default value
  sec = typeof sec !== 'undefined' ? sec : 0;

  var formlayer = document.getElementById(id);

  if (formlayer)
  {
    // do not apply effect on load screen
    // show based on display style
    formlayer.style.display = 'inline';

    // enable all form elements
    var nodes = formlayer.getElementsByTagName('*');

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
      var function_hide = "hcms_hideFormLayer('" + id + "')";
      setTimeout (function_hide, (sec * 1000));
    }

    return true;
  }
  else return false;
}

function hcms_hideFormLayer (id)
{
  var formlayer = document.getElementById(id);

  if (formlayer)
  {
    // hide based on display style
    formlayer.style.display = 'none';

    // disable all form elements
    var nodes = formlayer.getElementsByTagName('*');

    for (var i=0; i<nodes.length; i++)
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

function hcms_switchFormLayer (id)
{
  var formlayer = document.getElementById(id);
  
  if (formlayer)
  {
    if (formlayer.style.display == 'none')
    {
      // show
      formlayer.style.display = 'inline';
      
      // enable all form elements
      var nodes = formlayer.getElementsByTagName('*');

      for (var i=0; i<nodes.length; i++)
      {
        if (nodes[i].tagName == "INPUT" || nodes[i].tagName == "SELECT" || nodes[i].tagName == "TEXTAREA" || nodes[i].tagName == "BUTTON")
        {
          nodes[i].disabled = false;
        }
      }
    }
    else
    {
      // hide
      formlayer.style.display = 'none';

      // disable all form elements
      var nodes = formlayer.getElementsByTagName('*');

      for (var i=0; i<nodes.length; i++)
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

// ----------------------------  show/hide selector ----------------------------

function hcms_switchSelector (id)
{
  // uses visibilty
  var selector = document.getElementById(id);

  if (selector)
  {
    if (hcms_transitioneffect) selector.style.transition = 'all 0.3s linear';

    if (selector.style.visibility == 'hidden') 
    {
      if (hcms_transitioneffect) 
      {
        selector.style.opacity = '1';
        // for MS IE
        selector.style.filter = 'alpha(opacity=100)';
      }

      selector.style.visibility = 'visible';
    }
    else
    {
      if (hcms_transitioneffect) 
      {
        selector.style.opacity = '0';
        // fix for MS IE
        selector.style.filter = 'alpha(opacity=0)';
      }

      selector.style.visibility = 'hidden';
    }

    return true;
  }
  else return false;
}

function hcms_hideSelector (id)
{
  // uses visibilty
  var selector = document.getElementById(id);

  if (selector)
  {
    if (hcms_transitioneffect)
    {
      selector.style.transition = 'all 0.3s linear';
      selector.style.opacity = '0';
      // for MS IE
      selector.style.filter = 'alpha(opacity=0)';
    }

    if (selector.style.visibility == 'visible') selector.style.visibility = 'hidden';

    return true;
  }
  else return false;
}

// ------------------------------ element style functions -------------------------------

function hcms_elementStyle (element, class_element)
{
  if (element.className != class_element) element.className = class_element;
}

function hcms_elementbyIdStyle (id, class_element)
{
  var element = document.getElementById(id);

  if (element && element.className != class_element) element.className = class_element;
}

// ------------------------------- html entities ----------------------------------

// decodes the html entities in the str (e.x.: &auml; =>   but for the corresponding charset
// uses an html element to decode
function hcms_entity_decode (str)
{
  var ta = document.createElement("textarea");
  // html element to convert special characters
  ta.innerHTML = str;
  return ta.value;
}

// encodes the html entities in the str (e.x.:   => &auml; but for the corresponding charset
// uses an html element to encode
function hcms_entity_encode (str)
{
  var ta = document.createElement("textarea");
  // html element to convert special characters
  ta.innerHTML = str;
  return ta.innerHTML;
}

// ------------------------------ add table row --------------------------------

function hcms_addTableRow (id, position, values)
{
  if (document.getElementById(id) && position >= 0 && values instanceof Array)
  {
    var table = document.getElementById(id).getElementsByTagName('tbody')[0];
    var tr = table.insertRow(position);

    // create td then text, append
    for (var i = 0; i < values[i].length; i++)
    {
      // Insert a cell in the row
      var td  = newRow.insertCell(i);

      // Append a text node to the cell
      var content  = document.createTextNode(values[i]);
      td.appendChild(content);
    }
  }
  else return false;
}

// ------------------------------ sort table data --------------------------------

// define global arrays for the 2 tables (detailed and thumbnail view)
var hcms_detailview = new Array(); 
var hcms_galleryview = new Array(); 
var hcms_is_gallery = false;
var hcms_lastSort = null;
var hcms_objectpath = new Array();

function hcms_stripHTML (_str)
{
  if(!_str)return;

  // remove all 3 types of line breaks
  _str = _str.replace(/(\r\n|\n|\r)/gm, "");

  var _reg = /<.*?>/gi;

  while (_str.match(_reg) != null)
  {
    _str = _str.replace(_reg, "");
  }

  // replace non-breaking-space
  _str = _str.replace(/&nbps;/g, "");

  return _str;
}

// sort table array hcms_detailview by column number _c
function hcms_bubbleSort (c, _ud, _isNumber)
{
  for (var i=0; i < hcms_detailview.length; i++)
  {
    for (var j=i; j < hcms_detailview.length; j++)
    {
      var _left = hcms_stripHTML(hcms_detailview[i][c]);
      var _right = hcms_stripHTML(hcms_detailview[j][c]);
      var _sign = _ud ? ">" : "<";
      var _yes = false;

      // number
      if (_isNumber)
      {
        // replace all dots, commas and spaces in numbers
        _left = _left.replace(/\./g, "");
        _right = _right.replace(/\./g, "");
        _left = _left.replace(/,/g, "");
        _right = _right.replace(/,/g, "");
        _left = _left.replace(/\s/g, "");
        _right = _right.replace(/\s/g, "");
        _left = parseInt(_left) || 0;
        _right = parseInt(_right) || 0;

        if (_ud && (_left-_right > 0)) _yes = true;
        if (!_ud && (_left-_right < 0)) _yes = true;
      }
      // string
      else
      {
        if (_ud && _left.toLowerCase() > _right.toLowerCase()) _yes = true;
        if (!_ud && _left.toLowerCase() < _right.toLowerCase()) _yes = true;
      }

      if (_yes)
      {
        // swap rows for detailed view
        for (var x=0; x < hcms_detailview[i].length; x++)
        {
          var _t = hcms_detailview[i][x];
          hcms_detailview[i][x] = hcms_detailview[j][x];
          hcms_detailview[j][x] = _t;
        }

        // swap rows for thumbnail view  
        if (hcms_is_gallery) 
        {
          _t = hcms_galleryview[i];
          hcms_galleryview[i] = hcms_galleryview[j];
          hcms_galleryview[j] = _t;
        }
      }
    }
  }
  
  return true;
}

function hcms_sortTable (_c, _isNumber)
{
  if (typeof hcms_unselectAll === 'function') hcms_unselectAll();
  if (typeof hcms_resetContext === 'function') hcms_resetContext();

  hcms_is_gallery = eval (document.getElementById("t0"));  

  // detailed view table
  if (hcms_detailview.length <= 0)
  {
    var _o = null;
    var _i = 0;

    while (_o = document.getElementById("g"+_i))
    {
      hcms_detailview[_i] = new Array();
      var _j = 0;

      while (_p = document.getElementById("h"+_i+"_"+_j))
      {
        hcms_detailview[_i][_j] = _p.innerHTML;
        _j++;
      }

      _i++;
    }
  }
  
  // thumbnail view table
  if (hcms_galleryview.length <= 0 && hcms_is_gallery)
  {
    _o = null;
    _i = 0;
    
    while (_o = document.getElementById("t"+_i))
    {
      hcms_galleryview[_i] = _o.innerHTML;
      _i++;
    } 
  } 

  // sort both tables the same way
  hcms_bubbleSort (_c, hcms_lastSort != _c, _isNumber);

  // refill tables with sorted arrays
  for (var b = 0; b < hcms_detailview.length; b++)
  {
    for (var c = 0; c < hcms_detailview[b].length; c++)
    {
      document.getElementById("h"+b+"_"+c).innerHTML = hcms_detailview[b][c];
      if (hcms_is_gallery) document.getElementById("t"+b).innerHTML = hcms_galleryview[b];

      // save object path for viewer
      if (c == 0 && document.getElementById("h"+b+"_"+c).getElementsByTagName("A")) 
      {
        var link = document.getElementById("h"+b+"_"+c).getElementsByTagName("A");
        if (link[0] && link[0].getAttribute("data-objectpath")) hcms_objectpath[b] = link[0].getAttribute("data-objectpath");
      }
    }
  }

  // save object path array variable in parent frame
  if (hcms_objectpath) parent.hcms_objectpath = hcms_objectpath;

  if (hcms_lastSort != _c) hcms_lastSort = _c;
  else hcms_lastSort = null;
}

// ------------------------------ sort object --------------------------------

function hcms_sortObjectKey (object)
{
  if (typeof object === 'object' || typeof object === 'string')
  {
    if (typeof object === 'string') var object = JSON.parse (object);
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

function hcms_sortObjectValue (object, prop, asc)
{
  if ((typeof object === 'object' || typeof object === 'string') && prop != '')
  {
    if (typeof object === 'string') var object = JSON.parse (object);

    object = object.sort(function(a, b) {
      if (asc)
      {
        return (a[prop] > b[prop]) ? 1 : ((a[prop] < b[prop]) ? -1 : 0);
      }
      else
      {
        return (b[prop] > a[prop]) ? 1 : ((b[prop] < a[prop]) ? -1 : 0);
      }
    });
  }
  
  return object;
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
      autoSave();
      autosave_active.checked = false;
    }
    else
    {
      autoSave();
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

          div.innerHTML = '<input type="text" name="vtt_start" value="' + record.start + '" maxlength="12" style="float:left; margin:2px 2px 0px 0px; width:88px;" readonly="readonly" />\
              <input type="text" name="vtt_stop" value="' + record.stop + '" maxlength="12" style="float:left; margin:2px 2px 0px 0px; width:88px;" readonly="readonly" />\
              <input type="text" name="vtt_text" value="' + record.text + '" maxlength="400" style="float:left; margin:2px 2px 0px 0px; width:350px;" />\
              ' + vtt_buttons + '\
              <br />';
      
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

// ------------------------ iframe fix ----------------------------

// for alert, confirm, and prompt in iframe
if (hcms_service == false)
{
  window.alert = top.alert;
  window.confirm = top.confirm;
  window.prompt = top.prompt;
}

// ----------- event listener (cross-browser-support) -------------

function hcms_addEvent (event, element, func)
{
  // W3C DOM
  if (element.addEventListener)
  {
    element.addEventListener(event, func, false);
  }
  // IE DOM
  else if (elem.attachEvent)
  {
    element.attachEvent("on"+event, func);
  }
  else
  {
    element["on"+event] = func;
  }
}

// ------------------------ mouse events ----------------------------

// right mouse click
function hcms_isRightClick (e) 
{
  if (!e) var e = window.event;

  if (e.which && (e.which == 2 || e.which == 3)) return true;
  else if (e.button && (e.button == 2 || e.button == 3)) return true;
  else return false;
}

// left mouse click
function hcms_isLeftClick (e) 
{
  if (!e) var e = window.event;

  if (e.which && (e.which == 0 || e.which == 1)) return true;
  else if (e.button && (e.button == 0 || e.button == 1)) return true;
  else return false;
}

// left mouse click
function hcms_leftClickMain (e) 
{
  // left mouse click
  if (hcms_isLeftClick (e)) 
  {
    // minimize navigation for Mobile Edition
    if (is_mobile && hcms_permission['minnavframe'] == true && typeof top.minNavFrame === 'function') top.minNavFrame();
  }

  return true;
}

// ------------------------ key events ----------------------------

// verify if key is pressed
function hcms_keyPressed (key, e)
{
  var ctrlPressed = 0;
  var altPressed = 0;
  var shiftPressed = 0;

  if (e != null && e != 'selectarea')
  {
    // newer browsers (cross-platform)
    shiftPressed = e.shiftKey;
    altPressed = e.altKey;
    ctrlPressed = e.ctrlKey;
    // MacOS command key
    if (!ctrlPressed) ctrlPressed = e.metaKey;

    if (key == 'ctrl' && ctrlPressed) return true;
    else if (key == 'shift' && shiftPressed) return true;
    else if (key == 'alt' && altPressed) return true;
    else if (key == '' && (altPressed || shiftPressed || ctrlPressed)) return true;
    else return false;
  }

  return false;
}

hcms_addEvent ('keydown', document, function(e) {
  // save if Ctrl/Cmd+S key is pressed
  if (hcms_keyPressed('ctrl', e) && (e.key === 's' || e.key === 'S'))
  {
    // prevent the save dialog to open
    e.preventDefault();
    
    // call function
    if (typeof hcms_saveEvent === 'function') hcms_saveEvent();
  }

  // left arrow key
  if (e.keyCode == 37)
  {
    // call function
    if (typeof hcms_leftArrowEvent === 'function') hcms_leftArrowEvent();
  }

  // right arrow key
  if (e.keyCode == 39)
  {
    // call function
    if (typeof hcms_rightArrowEvent === 'function') hcms_rightArrowEvent();
  }

  // up arrow key
  if (e.keyCode == 38)
  {
    // call function
    if (typeof hcms_upArrowEvent === 'function') hcms_upArrowEvent();
  }

  // down arrow key
  if (e.keyCode == 40)
  {
    // call function
    if (typeof hcms_downArrowEvent === 'function') hcms_downArrowEvent();
  }
});

// ------------------------ mouse events ----------------------------

hcms_addEvent ('click', document, function(e) {
  // verify that the function hcms_leftClickContext of contextmenu.js is not included
  if (typeof hcms_leftClickContext != 'function') 
  {
    hcms_leftClickMain(e);
  }
});