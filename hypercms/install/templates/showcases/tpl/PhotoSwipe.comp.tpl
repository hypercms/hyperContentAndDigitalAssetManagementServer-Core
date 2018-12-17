<?xml version="1.0" encoding="utf-8" ?>
<template>
<name>PhotoSwipe</name>
<user>admin</user>
<category>comp</category>
<extension>php</extension>
<application>php</application>
<content><![CDATA[[hyperCMS:objectview name='inlineview']
[hyperCMS:tplinclude file='ServiceCollectMedia.inc.tpl']
[hyperCMS:scriptbegin
global $mgmt_config;	

// INIT
$uniqid = uniqid();
$site = "%publication%";
$abs_comp = "%abs_comp%";
$container_id = "%container_id%";
$view = "%view%";
$hash = "%objecthash%";
$correctFile = correctfile("%abs_location%", "%object%");

// picture - file extensions
$picture_extensions = ".jpg.jpeg.png.gif.bmp";

// User entry - picture / folder
$picture = "[hyperCMS:mediafile id='picture' onEdit='hidden']";
$pictureTagId = "picture";

// Metadata IDs to display
$metaTitleId = "Title";
$metaDescriptionId = "Description";

// USER ENTRIES
$galleriaWidth = "[hyperCMS:textu id='galleriaWidth' onEdit='hidden']";
$galleriaHeight = "[hyperCMS:textu id='galleriaHeight' onEdit='hidden']";
$filtername = "[hyperCMS:textl id='filterName' onEdit='hidden']";
$filtervalue = "[hyperCMS:textu id='filterValue' onEdit='hidden']";

// SET FILTER
if ("[hyperCMS:textl id='filterName' onEdit='hidden']" != "")
{
  $filter = array ($filtername => $filtervalue);
}
else $filter = "";

// CMS VIEW => get user entry and create iframe code
if ($view == "cmsview")
{
scriptend]
<!DOCTYPE html>
<html>
  <head>
    <title>hyperCMS.com</title>
    <meta charset='utf-8'/>
    <link rel="stylesheet" hypercms_href="[hyperCMS:scriptbegin echo getthemelocation(); scriptend]css/main.css" />
  </head>
  <body class="hcmsWorkplaceGeneric">
    <div class="hcmsWorkplaceFrame">
      <br />
      <table>
        <tr>
          <td>Select Picture / Folder <!-- [hyperCMS:mediafile id='picture' label='Picture (folder)' mediatype='image' onPublish='hidden'] --></td><td>[hyperCMS:scriptbegin if (strpos ("[hyperCMS:mediafile id='picture' onEdit='hidden']", "Null_media") == false) echo "Done"; scriptend]</td>
        </tr>
        <tr>
          <td>Width of stage</td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textu id='galleriaWidth' label='Width of stage' constraint='isNum' default='800' height='15' width='100']</div> px</td>
        </tr>
        <tr>
          <td>Height of stage</td><td><div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textu id='galleriaHeight' label='Height of stage' constraint='isNum' default='600' height='15' width='100']</div> px</td>
        </tr>
        <tr>
          <td>Filter by</td><td>Field-ID:<div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textl id='filterName' label='Name' list='|Title|Description|Keywords|Copyright|Creator|License']</div> contains <div style="display:inline-block; padding:2px; border:1px solid #000;">[hyperCMS:textu id='filterValue' label='Value']</div></td>
        </tr>
        <tr>
          <td>&nbsp;</td><td><button class="hcmsButtonGreen" type="button" onClick="location.reload();" >generate code</button></td>
        </tr>
      </table>
      <p>Please do not forget to publish this page after changing the parameters!</p>
      <hr/>
[hyperCMS:scriptbegin
  //check if component is published
  $compinfo = getfileinfo ($site, $correctFile, "comp");

  if ($compinfo['published'])
  {
    $embed_code = "<iframe id='frame_$uniqid' src='".$mgmt_config['url_path_cms']."?wl=$hash' scrolling='no' frameborder=0 border=0 width='".$galleriaWidth."' height='".$galleriaHeight."'></iframe>";
  }
  else
  {
    $embed_code = "Component is not published yet!";
  }
scriptend]
      <strong>HTML body segment</strong>
      <br />
      Mark and copy the code from the text area box (keys ctrl + A and Ctrl + C for copy or right mouse button -> copy).  Insert this code into your HTML Body of the page, where the snippet will be integrated (keys Crtl + V or right mouse button -> insert).
      <br />
      <br />
      <textarea id="codesegment" wrap="VIRTUAL" style="height:80px; width:98%">[hyperCMS:scriptbegin echo html_encode($embed_code); scriptend]</textarea>
      <br />
      <hr/>
      <strong>Online view</strong>
      <br />
      [hyperCMS:scriptbegin if ($compinfo['published']) echo "<iframe id='frame_$uniqid' src='".$mgmt_config['url_path_cms']."?wl=$hash' scrolling='no' frameborder=0 border=0 width='".$galleriaWidth."' height='".$galleriaHeight."' style='border:1px solid grey; background-color:#000000;'></iframe>"; scriptend]
    </div>
  </body>
</html>
[hyperCMS:scriptbegin
}
elseif ($view == "publish" || $view == "preview")
{
  //published file should be a valid html
scriptend]
<!DOCTYPE html>
<html>
  <head>
    <title>Gallery</title>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" /> 

  <style>
    body {
      font-family: Arial;
      font-size:12px;
      margin: 0px;
      padding: 0px;
    }
    .thumbnail {
      margin: 10px;
      padding: 0px;
      float: left;
      width: 180px;
      height: 180px;
    }
  </style>

  <!-- Core CSS file -->
  <link rel="stylesheet" hypercms_href="[hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/iframe_photoswipe/dist/photoswipe.css?v=4.1.1-1.0.4"> 
  
  <!-- Skin CSS file (styling of UI - buttons, caption, etc.)
       In the folder of skin CSS file there are also:
       - .png and .svg icons sprite, 
       - preloader.gif (for browsers that do not support CSS animations) -->
  <link rel="stylesheet" hypercms_href="[hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/iframe_photoswipe/dist/default-skin/default-skin.css?v=4.1.1-1.0.4"> 
  
  <!-- Core JS file -->
  <script src="[hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/iframe_photoswipe/dist/photoswipe.min.js?v=4.1.1-1.0.4"></script> 
  
  <!-- UI JS file -->
  <script src="[hyperCMS:scriptbegin echo $mgmt_config['url_path_cms']; scriptend]javascript/iframe_photoswipe/dist/photoswipe-ui-default.min.js?v=4.1.1-1.0.4"></script> 

  </head>
  <body class="photoswipe-gallery">
[hyperCMS:scriptbegin
  // check if picture (folder) is choosen or if it exsists
  if (substr_count ($picture, "Null_media.gif") == 1)
  {
scriptend]
    <p>No media file selected!</p>
[hyperCMS:scriptbegin
  }
  else
  {
    $mediaFiles = collectMedia ($site, $container_id, $pictureTagId, $abs_comp, $picture_extensions, $metaTitleId, $metaDescriptionId, $filter);

    if (empty ($mediaFiles))
    {
scriptend]
 <p>Folder could not be read!</p>
[hyperCMS:scriptbegin		
    }
  }
scriptend]

  <div id="hypercms-gallery" class="hypercms-gallery">
        
  [hyperCMS:scriptbegin
      $i = 0;

      foreach ($mediaFiles as $media)
      {
  scriptend]
          <a class="thumbnail" href="[hyperCMS:scriptbegin echo $media['link']; scriptend]" data-size="[hyperCMS:scriptbegin echo $media['width']; scriptend]x[hyperCMS:scriptbegin echo $media['height']; scriptend]" data-author="[hyperCMS:scriptbegin echo (empty($media['title']) ? $media['name'] : $media['title']); scriptend]">
            <img src="[hyperCMS:scriptbegin echo $media['thumb_link']; scriptend]" style="max-height:180px;" alt="[hyperCMS:scriptbegin echo (empty($media['description']) ? $media['name'] : $media['description']); scriptend]" />
            <figure style="display:none;">[hyperCMS:scriptbegin echo (empty($media['description']) ? $media['name'] : $media['description']); scriptend]</figure>
          </a>
  [hyperCMS:scriptbegin

        $i++;
      }
  scriptend]
          
    </div>

    <!-- Root element of PhotoSwipe. Must have class pswp. -->
    <div class="pswp" tabindex="-1" role="dialog" aria-hidden="true">
        <!-- Background of PhotoSwipe. 
             It's a separate element as animating opacity is faster than rgba(). -->
        <div class="pswp__bg"></div>
        <!-- Slides wrapper with overflow:hidden. -->
        <div class="pswp__scroll-wrap">
            <!-- Container that holds slides. 
                PhotoSwipe keeps only 3 of them in the DOM to save memory.
                Don't modify these 3 pswp__item elements, data is added later on. -->
            <div class="pswp__container">
                <div class="pswp__item"></div>
                <div class="pswp__item"></div>
                <div class="pswp__item"></div>
            </div>    
            <!-- Default (PhotoSwipeUI_Default) interface on top of sliding area. Can be changed. -->
            <div class="pswp__ui pswp__ui--hidden">
                <div class="pswp__top-bar">
                    <!--  Controls are self-explanatory. Order can be changed. -->
                    <div class="pswp__counter"></div>
                    <button class="pswp__button pswp__button--close" title="Close (Esc)"></button>
                    <button class="pswp__button pswp__button--share" title="Share"></button>
                    <button class="pswp__button pswp__button--fs" title="Toggle fullscreen"></button>
                    <button class="pswp__button pswp__button--zoom" title="Zoom in/out"></button>    

                    <!-- element will get class pswp__preloader--active when preloader is running -->
                    <div class="pswp__preloader">
                        <div class="pswp__preloader__icn">
                          <div class="pswp__preloader__cut">
                            <div class="pswp__preloader__donut"></div>
                          </div>
                        </div>
                    </div>
                </div>    
                <div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap">
                    <div class="pswp__share-tooltip"></div> 
                </div>    
                <button class="pswp__button pswp__button--arrow--left" title="Previous (arrow left)"></button>
                <button class="pswp__button pswp__button--arrow--right" title="Next (arrow right)"></button>    
                <div class="pswp__caption">
                    <div class="pswp__caption__center"></div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
    (function() {

    var initPhotoSwipeFromDOM = function(gallerySelector) {

      var parseThumbnailElements = function(el) {
          var thumbElements = el.childNodes,
              numNodes = thumbElements.length,
              items = [],
              el,
              childElements,
              thumbnailEl,
              size,
              item;

          for(var i = 0; i < numNodes; i++) {
              el = thumbElements[i];

              // include only element nodes 
              if(el.nodeType !== 1) {
                continue;
              }

              childElements = el.children;

              size = el.getAttribute('data-size').split('x');

              // create slide object
              item = {
            src: el.getAttribute('href'),
            w: parseInt(size[0], 10),
            h: parseInt(size[1], 10),
            author: el.getAttribute('data-author')
              };

              item.el = el; // save link to element for getThumbBoundsFn

              if(childElements.length > 0) {
                item.msrc = childElements[0].getAttribute('src'); // thumbnail url
                if(childElements.length > 1) {
                    item.title = childElements[1].innerHTML; // caption (contents of figure)
                }
              }


          var mediumSrc = el.getAttribute('data-med');
                if(mediumSrc) {
                  size = el.getAttribute('data-med-size').split('x');
                  // "medium-sized" image
                  item.m = {
                      src: mediumSrc,
                      w: parseInt(size[0], 10),
                      h: parseInt(size[1], 10)
                  };
                }
                // original image
                item.o = {
                  src: item.src,
                  w: item.w,
                  h: item.h
                };

              items.push(item);
          }

          return items;
      };

      // find nearest parent element
      var closest = function closest(el, fn) {
          return el && ( fn(el) ? el : closest(el.parentNode, fn) );
      };

      var onThumbnailsClick = function(e) {
          e = e || window.event;
          e.preventDefault ? e.preventDefault() : e.returnValue = false;

          var eTarget = e.target || e.srcElement;

          var clickedListItem = closest(eTarget, function(el) {
              return el.tagName === 'A';
          });

          if(!clickedListItem) {
              return;
          }

          var clickedGallery = clickedListItem.parentNode;

          var childNodes = clickedListItem.parentNode.childNodes,
              numChildNodes = childNodes.length,
              nodeIndex = 0,
              index;

          for (var i = 0; i < numChildNodes; i++) {
              if(childNodes[i].nodeType !== 1) { 
                  continue; 
              }

              if(childNodes[i] === clickedListItem) {
                  index = nodeIndex;
                  break;
              }
              nodeIndex++;
          }

          if(index >= 0) {
              openPhotoSwipe( index, clickedGallery );
          }
          return false;
      };

      var photoswipeParseHash = function() {
        var hash = window.location.hash.substring(1),
          params = {};

          if(hash.length < 5) { // pid=1
              return params;
          }

          var vars = hash.split('&');
          for (var i = 0; i < vars.length; i++) {
              if(!vars[i]) {
                  continue;
              }
              var pair = vars[i].split('=');  
              if(pair.length < 2) {
                  continue;
              }           
              params[pair[0]] = pair[1];
          }

          if(params.gid) {
            params.gid = parseInt(params.gid, 10);
          }

          return params;
      };

      var openPhotoSwipe = function(index, galleryElement, disableAnimation, fromURL) {
          var pswpElement = document.querySelectorAll('.pswp')[0],
              gallery,
              options,
              items;

        items = parseThumbnailElements(galleryElement);

          // define options (if needed)
          options = {

              galleryUID: galleryElement.getAttribute('data-pswp-uid'),

              getThumbBoundsFn: function(index) {
                  // See Options->getThumbBoundsFn section of docs for more info
                  var thumbnail = items[index].el.children[0],
                      pageYScroll = window.pageYOffset || document.documentElement.scrollTop,
                      rect = thumbnail.getBoundingClientRect(); 

                  return {x:rect.left, y:rect.top + pageYScroll, w:rect.width};
              },

              addCaptionHTMLFn: function(item, captionEl, isFake) {
            if(!item.title) {
              captionEl.children[0].innerText = '';
              return false;
            }
            captionEl.children[0].innerHTML = item.title +  '<br/><small>Photo: ' + item.author + '</small>';
            return true;
              },
          
          };


          if(fromURL) {
            if(options.galleryPIDs) {
              // parse real index when custom PIDs are used 
              // http://photoswipe.com/documentation/faq.html#custom-pid-in-url
              for(var j = 0; j < items.length; j++) {
                if(items[j].pid == index) {
                  options.index = j;
                  break;
                }
              }
            } else {
              options.index = parseInt(index, 10) - 1;
            }
          } else {
            options.index = parseInt(index, 10);
          }

          // exit if index not found
          if( isNaN(options.index) ) {
            return;
          }



        var radios = document.getElementsByName('gallery-style');
        for (var i = 0, length = radios.length; i < length; i++) {
            if (radios[i].checked) {
                if(radios[i].id == 'radio-all-controls') {

                } else if(radios[i].id == 'radio-minimal-black') {
                  options.mainClass = 'pswp--minimal--dark';
                  options.barsSize = {top:0,bottom:0};
              options.captionEl = false;
              options.fullscreenEl = false;
              options.shareEl = false;
              options.bgOpacity = 0.85;
              options.tapToClose = true;
              options.tapToToggleControls = false;
                }
                break;
            }
        }

          if(disableAnimation) {
              options.showAnimationDuration = 0;
          }

          // Pass data to PhotoSwipe and initialize it
          gallery = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items, options);

          // see: http://photoswipe.com/documentation/responsive-images.html
        var realViewportWidth,
            useLargeImages = false,
            firstResize = true,
            imageSrcWillChange;

        gallery.listen('beforeResize', function() {

          var dpiRatio = window.devicePixelRatio ? window.devicePixelRatio : 1;
          dpiRatio = Math.min(dpiRatio, 2.5);
            realViewportWidth = gallery.viewportSize.x * dpiRatio;


            if(realViewportWidth >= 1200 || (!gallery.likelyTouchDevice && realViewportWidth > 800) || screen.width > 1200 ) {
              if(!useLargeImages) {
                useLargeImages = true;
                  imageSrcWillChange = true;
              }
                
            } else {
              if(useLargeImages) {
                useLargeImages = false;
                  imageSrcWillChange = true;
              }
            }

            if(imageSrcWillChange && !firstResize) {
                gallery.invalidateCurrItems();
            }

            if(firstResize) {
                firstResize = false;
            }

            imageSrcWillChange = false;

        });

        gallery.listen('gettingData', function(index, item) {
            if( useLargeImages ) {
                item.src = item.o.src;
                item.w = item.o.w;
                item.h = item.o.h;
            } else {
                item.src = item.m.src;
                item.w = item.m.w;
                item.h = item.m.h;
            }
        });

          gallery.init();
      };

      // select all gallery elements
      var galleryElements = document.querySelectorAll( gallerySelector );
      for(var i = 0, l = galleryElements.length; i < l; i++) {
        galleryElements[i].setAttribute('data-pswp-uid', i+1);
        galleryElements[i].onclick = onThumbnailsClick;
      }

      // Parse URL and open gallery if it contains #&pid=3&gid=1
      var hashData = photoswipeParseHash();
      if(hashData.pid && hashData.gid) {
        openPhotoSwipe( hashData.pid,  galleryElements[ hashData.gid - 1 ], true, true );
      }
    };

    initPhotoSwipeFromDOM('.hypercms-gallery');

  })();
  </script>
    
  </body>
</html>
[hyperCMS:scriptbegin 
}
scriptend]
]]></content>
</template>