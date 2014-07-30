/*
 * Plugin to add a Video to the text.
 *
 */

(function() {
  
  // Span is old configuration
  var fakeName_span = 'hcms_video_span';
  // iframe is new one
  var fakeName_iframe = 'hcms_video_iframe';
  var fakeClass = 'cke_hcms_video';
  var videoIdPreFix = 'hcms_projekktor_';
  var divIdPreFix_old = 'hcms_div_projekktor_';
  var iframePreFix = 'hcms_mediaplayer_';
  
  // decodes the html entities in the str (e.x.: &auml; => ä but for the corresponding charset
  // uses an html element to decode  
  function html_entity_decode(str) {
    var ta = document.createElement("textarea");  // We need a html element to convert special characters
    ta.innerHTML=str.replace(/</g,"<").replace(/>/g,">");
    return ta.value;
  }
  
  function decode(str) {
     return unescape(str.replace(/\+/g, " "));
}
  
  function generateID(seed) {
    return Math.floor(Math.random()*(Math.random()*seed));
  };
  
  function isEmpty(variable) {
    return (variable == null || variable == 0 || variable == '' || variable == 'undefined' || variable == '0');
  }
  
  function generate_videoplayer_iframe(cmsLink, video, width, height, logo, id, title, autoplay, enableFullScreen, enableKeyBoard) {
   
    if (isEmpty(id)) id = generateID(100000);

    return '<iframe scrolling="no" frameBorder="0" style="border:0" '+((!isEmpty(title)) ? 'title="'+title+' "' : '')+'id="'+iframePreFix+id+'" width="'+width+'" height="'+height+'" src="'+cmsLink+'videoplayer.php?media='+video+'&width='+width+'&height='+height+'&autoplay='+((autoplay) ? "true" : "false")+'&fullscreen='+((enableFullScreen) ? "true" : "false")+'&keyboard='+((enableKeyBoard) ? "true" : "false")+((!isEmpty(title)) ? '&title='+encodeURIComponent(title) : '')+'&logo='+(!isEmpty(logo) ? encodeURIComponent(logo) : '')+'" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true">';
  }
  
  function generate_videoplayer_span(cmsLink, video, width, height, logo, id, title, autoplay, enableFullScreen, enableKeyBoard) {
    
    if (isEmpty(id)) id = generateID(100000);

    ret = '<span id="'+divIdPreFix_old+id+'"><video id="'+videoIdPreFix+id+'" class="projekktor"'+((!isEmpty(logo)) ? ' poster="'+logo+'" ' : ' ')+((!isEmpty(title)) ? ' title="'+title+'" ' : ' ')+'width="'+width+'" height="'+height+'" controls>';

    ret += '</video>'+
       '<hcms_script type="text/javascript">'+
      'jq_vid(document).ready(function()'+ 
      '{'+
        'projekktor("#'+videoIdPreFix+id+'",'+ 
        '{'+
          'useYTIframeAPI: false,'+
          'height: '+height+','+
          'width: '+width+',';

    if (!isEmpty(logo)) ret += 'poster: "'+logo+'",';

    ret +=  'autoplay: '+((autoplay) ? 'true' : 'false')+','+
          'enableFullscreen: '+((enableFullScreen) ? 'true' : 'false')+','+
          'enableKeyboard: '+((enableKeyBoard) ? 'true' : 'false')+','+
          'playerFlashMP4: "'+cmsLink+'video/js/jarisplayer.swf"'+
        '});'+
      '});'+
    '</hcms_script></span>';

    return ret;
  }
  
  function parseVideoParameters_iframe(link, editor) {
    // We need to decode because CKEditor encodes script elements
    if(link.replace) {
      link = link.replace("%hcms%", editor.config.cmsLink);
    }
    link = decodeURIComponent(link);
    var regLink = /\?media\=([a-zA-Z\/\_\.\~0-9\-]+)/i;
    var regPoster = /\&logo\=([a-zA-Z\/\.\:\_\~\%0-9\-]+)/i;
    var regAutoplay = /\&autoplay\=(true|false)/i;
    var regFullscreen = /\&fullscreen\=(true|false)/i;
    var regKeyboard = /\&keyboard\=(true|false)/i;
      
    var data = {};
    
    // Reading Link
    regLink.exec(link);
    data.link = RegExp.$1;
    
    // Reading Autoplay
    regAutoplay.exec(link);
    data.autoplay = RegExp.$1;
    
    // Reading EnableFullscreen
    regFullscreen.exec(link);
    data.enablefullscreen = RegExp.$1;
    
    // Reading EnableKeyboard
    regKeyboard.exec(link);
    data.enablekeyboard = RegExp.$1;
    
    // Reading poster
    if(regPoster.exec(link)) {
      data.poster = RegExp.$1;
    } else {
      data.poster = '';
    }
    
    return data;
    
  }
  
  function parseVideoParameters_span(html) {
    // We need to decode because CKEditor encodes script elements
    html = decodeURIComponent(html);
    var regHeight = /height\: ([0-9]+)/i;
    var regWidth = /width\: ([0-9]+)/i;
    var regPoster = /poster\: \"([a-zA-Z\/\.\:\_\-\%0-9]+)\"/i;
    var regAutoplay = /autoplay\: (true|false)/i;
    var regFullscreen = /enableFullscreen\: (true|false)/i;
    var regKeyboard = /EnableKeyboard\: (true|false)/i;
      
    var data = {};
    
    // Reading video urls
    var div = document.createElement("div");
    div.innerHTML = html;
    
    // Either we have the direct repository link
    data.link = div.children[0].children[0].src;
    var find = "repository/media_cnt/";
    var cut_start = data.link.indexOf(find);
    if(cut_start > -1) 
    {
      cut_start += find.length;
      data.link = data.link.substr(cut_start);
    }
    // Or the wrapper link
    find = "media=";
    cut_start = data.link.indexOf(find);
    if(cut_start > -1)
    {
      cut_start += find.length;
      data.link = data.link.substr(cut_start);
    }
    
    // Reading Height
    regHeight.exec(html);
    data.height = RegExp.$1;
    
    // Reading Width
    regWidth.exec(html);
    data.width = RegExp.$1;
    
    // Reading Autoplay
    regAutoplay.exec(html);
    data.autoplay = RegExp.$1;
    
    // Reading EnableFullscreen
    regFullscreen.exec(html);
    data.enablefullscreen = RegExp.$1;
    
    // Reading EnableKeyboard
    regKeyboard.exec(html);
    data.enablekeyboard = RegExp.$1;
       
    // Reading poster
    if(regPoster.exec(html)) {
      data.poster = RegExp.$1;
    } else {
      data.poster = '';
    }
    
    return data;
    
  }
    
  CKEDITOR.plugins.add( 'hcms_video',  {
      requires :  [ 'dialog', 'fakeobjects' ],
      lang:    [ 'en', 'de'],
      init: function ( editor ) {
        
        // Defining our main path (this.path has some the js file and some ? parameter in it sometimes
        var customPath = this.path.split("/");
        // Removing the last bit if it's not empty
        if(customPath[customPath.length-1] != "") {
          customPath.pop();
        }
        var customPath = customPath.join("/");
        
        // Generating link where the image for the video button resided
        var imageLink = customPath+'/images/icon.png';
        var placeHolderLink = customPath+'/images/placeholder.png';
        
        // Adding the command to open the dialog
        editor.addCommand( 'hcms_video_dialog', new CKEDITOR.dialogCommand( 'hcms_video_dialog'));
        
        // Adding the button which calls the command
        editor.ui.addButton( 'hcms_video', {
            label: editor.lang.hcms_video.menu.main,
            command: 'hcms_video_dialog',
            icon: imageLink
          }
          
        );
        
        // We are using the standard way with our own regex
        CKEDITOR.dialog.validate.positiveNumber = function( msg ) {
          // this regex allows any number greater than 0 (0,000000001 to endless)
          var numberRegex = /^([0-9]+\.[0-9]+|[1-9][0-9]*)$/;
          return CKEDITOR.dialog.validate.regex( numberRegex , msg );
          
        };
        
        // Definition of the dialog
        // Could be separated into another file
        CKEDITOR.dialog.add( 'hcms_video_dialog', function ( editor ) {
          return {
            title : editor.lang.hcms_video.title,
            minWidth : 420,
            minHeight : 310,
            contents :
            [{
              // General Tab
              id : 'gen',
              label : editor.lang.hcms_video.tab.gen.title,
              elements :
              [{
                // Videolink and Select Button
                type : 'hbox',
                align : 'left',
                children : 
                [{
                  // Video input field
                  type : 'text',
                  id : 'link',
                  label : editor.lang.hcms_video.tab.gen.video.title,
                  required : true, 
                  validate : CKEDITOR.dialog.validate.notEmpty( editor.lang.hcms_video.tab.gen.video.empty ),
                  setup: function( config ) {
                    this.setValue( config.link );
                  },
                  commit: function( config ) {
                    config.link = this.getValue();
                  }
                },
                {
                  // Select Button
                  type : 'button',
                  id : 'videobrowser',
                  label : editor.lang.hcms_video.tab.gen.video.browser,
                  filebrowser : 
                  {
                    action : 'Browse',
                    url: editor.config.filebrowserVideoBrowseUrl,
                    onSelect: function( fileURL, info) {
                      if(info.split)
                      {
                        data = info.split("x");
                        data = data.filter(function(v) { return v.replace(/^\s+|\s+$/g, "") != '';});
                        
                        var width = parseInt(data[0], 10);
                        var height = parseInt(data[1], 10);
                        var dialog = this.getDialog();
                        
                        dialog.getContentElement( 'gen', 'width' ).setValue( width );
                        dialog.getContentElement( 'gen', 'height' ).setValue( height );
                      }
                      
                      dialog.getContentElement( 'gen', 'link' ).setValue( fileURL );


                      return false;
                    }
                  },
                  // v-align with the 'src' field.
                  // TODO: We need something better than a fixed size here.
                  style : 'display:inline-block;margin-top:12px;'
                }]
              }, {
                // Height and Width
                type : 'hbox',
                align : 'left',
                children : 
                [{
                  // Width
                  type : 'text',
                  id : 'width',
                  label: editor.lang.hcms_video.tab.gen.width.title,
                  required : true,
                  validate : CKEDITOR.dialog.validate.positiveNumber( editor.lang.hcms_video.tab.gen.width.incorrectNumber ),
                  setup: function( config ) {
                    this.setValue( config.width );
                  },
                  commit: function( config ) {
                    config.width = this.getValue();
                  }
                  },
                  {
                    // Height
                  type : 'text',
                  id : 'height',
                  label: editor.lang.hcms_video.tab.gen.height.title,
                  required : true,
                  validate : CKEDITOR.dialog.validate.positiveNumber( editor.lang.hcms_video.tab.gen.height.incorrectNumber ),
                  setup: function( config ) {
                    this.setValue( config.height );
                  },
                  commit: function( config ) {
                    config.height = this.getValue();
                  }
                }]
              }, {
                // Posterlink and Select Button
                type : 'hbox',
                align : 'left',
                children : 
                [{
                  // Poster input field
                  type : 'text',
                  id : 'poster',
                  label : editor.lang.hcms_video.tab.gen.poster.title,
                  setup: function( config ) {
                    this.setValue( config.poster );
                  },
                  commit: function( config ) {
                    config.poster = this.getValue();
                  }
                },
                {
                  // Select Button
                  type : 'button',
                  id : 'posterbrowser',
                  label : editor.lang.hcms_video.tab.gen.poster.browser,
                  filebrowser : 
                  {
                    action : 'Browse',
                    url: editor.config.filebrowserImageBrowseUrl,
                    onSelect: function( fileURL, data) {
                      var dialog = this.getDialog();
                      
                      dialog.getContentElement( 'gen', 'poster' ).setValue( fileURL );

                      return false;
                    }
                  },
                  // v-align with the 'src' field.
                  // TODO: We need something better than a fixed size here.
                  style : 'display:inline-block;margin-top:12px;'
                }]
              }]
            }, {
              // Advanded Tab
              id : 'adv',
              label : editor.lang.hcms_video.tab.adv.title,
              elements :
              [{
                // Id Input
                type : 'text',
                id : 'id',
                label : editor.lang.hcms_video.tab.adv.id,
                setup: function( config ) {
                    this.setValue( config.id );
                },
                commit: function( config ) {
                  config.id = this.getValue();
                }
              }, {
                // Title input field
                type : 'text',
                id : 'title',
                label : editor.lang.hcms_video.tab.adv.titlefield,
                setup: function( config ) {
                    this.setValue( config.title );
                },
                commit: function( config ) {
                  config.title = this.getValue();
                }
              }, {
                type : 'fieldset',
                label : editor.lang.hcms_video.tab.adv.enable.title,
                children :
                [{
                  type : 'vbox',
                  padding : 0,
                  children :
                  [{
                    type : 'checkbox',
                    id : 'autoplay',
                    label : editor.lang.hcms_video.tab.adv.enable.autoplay,
                    'default' : false,
                    setup: function( config ) {
                      this.setValue( (config.autoplay == 'true') );
                    },
                    commit: function( config ) {
                      config.autoplay = this.getValue();
                    }
                  }, {
                    type : 'checkbox',
                    id : 'fullScreen',
                    label : editor.lang.hcms_video.tab.adv.enable.fullScreen,
                    'default' : true,
                    setup: function( config ) {
                      this.setValue( (config.enablefullscreen == 'true') );
                    },
                    commit: function( config ) {
                      config.enablefullscreen = this.getValue();
                    }
                  }, {
                    type : 'checkbox',
                    id : 'keyBoard',
                    label : editor.lang.hcms_video.tab.adv.enable.keyBoard,
                    'default' : true,
                    setup: function( config ) {
                      this.setValue( (config.enablekeyboard == 'true') );
                    },
                    commit: function( config ) {
                      config.enablekeyboard = this.getValue();
                    }
                  }]
                }]
              }]
            }],
            onOk : function() {
              
              config = { };
              this.commitContent( config );
              
              var html = generate_videoplayer_iframe(editor.config.cmsLink, config.link, config.width, config.height, config.poster, config.id, config.title, config.autoplay, config.enablefullscreen, config.enablekeyboard);
              var fakeName = fakeName_iframe;
              videoNode = CKEDITOR.dom.element.createFromHtml(html);
              var extraStyles = { width: config.width+'px', height: config.height+'px' };
              var extraAttributes = { title: editor.lang.hcms_video.fakeTitle, alt: editor.lang.hcms_video.fakeTitle };
              
              var newFakeImage = editor.createFakeElement( videoNode, fakeClass, fakeName, true );
              newFakeImage.setStyles( extraStyles );
              newFakeImage.setAttributes( extraAttributes );

              // Insert the fake image
              if(this.fakeImage) {
                newFakeImage.replace( this.fakeImage );
                editor.getSelection().selectElement( newFakeImage );
              } else {
                editor.insertElement( newFakeImage );
              }
            },
            onShow : function() {
              var fakeImage = this.getSelectedElement();
              // old Configuration
              if(fakeImage && fakeImage.data( 'cke-real-element-type' ) && fakeImage.data( 'cke-real-element-type' ) == fakeName_span) {
                this.fakeImage = fakeImage;
                var divNode = editor.restoreRealElement( this.fakeImage );
                var videoNode = divNode.getChild(0);
                var sourceNode = videoNode.getChild(0);
                
                var parsedData = parseVideoParameters_span(divNode.getHtml());
                parsedData.title = videoNode.getAttribute( 'title' );
                
                // We cut out the id we need for this interface
                var id = videoNode.getAttribute( 'id' );
                if(id.substring)
                  parsedData.id = id.substring(videoIdPreFix.length);
                else
                  parsedData.id = generateID(100000);
                  
              // new Configuration  
              } else if(fakeImage && fakeImage.data( 'cke-real-element-type' ) && fakeImage.data( 'cke-real-element-type' ) == fakeName_iframe) {
                this.fakeImage = fakeImage;
                var iframe = editor.restoreRealElement( this.fakeImage );
                
                var parsedData = parseVideoParameters_iframe(iframe.getAttribute( 'src' ), editor);
                parsedData.title = iframe.getAttribute( 'title' );
                
                // Parsing width and height
                parsedData.width = iframe.getAttribute( 'width' );
                parsedData.height = iframe.getAttribute( 'height' );
                if(parsedData.width.substring)
                  parsedData.width = parseInt(parsedData.width, 10);
                if(parsedData.height.substring)
                  parsedData.height = parseInt(parsedData.height, 10);
                
                // We cut out the id we need for this interface
                var id = iframe.getAttribute( 'id' );
                if(id.substring)
                  parsedData.id = id.substring(divIdPreFix_old.length);
                else
                  parsedData.id = generateID(100000);
                  
              } else {
                var parsedData = {};
                parsedData.id = generateID(100000);
                parsedData.poster = editor.config.cmsLink+'theme/standard/img/logo_player.jpg';
                parsedData.autoplay = 'false';
                parsedData.enablefullscreen = 'true';
                parsedData.enablekeyboard = 'true';
              }
              this.setupContent( parsedData );
              
            },
            onLoad : function() {
              this.getContentElement("gen", "link").disable();
              //this.getContentElement("gen", "width").disable();
              //this.getContentElement("gen", "height").disable();
            }
          };
        });
        CKEDITOR.addCss(
          'img.'+fakeClass+
          '{' +
            'background-image: url(' + CKEDITOR.getUrl( placeHolderLink ) + ');' +
            'background-position: center center;' +
            'background-repeat: no-repeat;' +
            'border: 1px solid #a9a9a9;' +
          '}'
        );
        if ( editor.contextMenu ) {
          editor.addMenuGroup( 'Video' );
          editor.addMenuItem( 'edit_hcms_video',
          {
            label : editor.lang.hcms_video.menu.context,
            icon : imageLink,
            command : 'hcms_video_dialog',
            group : 'Video'
          });
          editor.contextMenu.addListener( function( element )  {
            if ( element && element.data( 'cke-real-element-type' ) && (element.data( 'cke-real-element-type' ) == fakeName_span || element.data( 'cke-real-element-type' ) == fakeName_iframe)) {
              return { 'edit_hcms_video' : CKEDITOR.TRISTATE_OFF };
            }
            return null;
          });
        }
        CKEDITOR.dtd.$empty['source']=1;
        CKEDITOR.dtd.span.video = 1;
        CKEDITOR.dtd.span.source = 1;
        CKEDITOR.dtd.span.audio = 1;
        // Hack for our own script tag
        CKEDITOR.dtd.$cdata['hcms_script'] = 1;
        CKEDITOR.dtd.$inline['hcms_script'] = 1;
        CKEDITOR.dtd.$nonBodyContent['hcms_script'] = 1;
        CKEDITOR.dtd.$nonEditable['hcms_script'] = 1;
        CKEDITOR.dtd.span.hcms_script = 1;
        CKEDITOR.dtd.hcms_script = { '#': 1 };
        
      },
      afterInit : function( editor ) {
        var dataProcessor = editor.dataProcessor;
        var dataFilter = dataProcessor && dataProcessor.dataFilter;

        if ( dataFilter ) {
          dataFilter.addRules({
            elements : {
              'span' : function( element ) {
                // We only apply it to our video elements
                if(element.attributes.id) {
                   var found = element.attributes.id.indexOf(divIdPreFix_old);
             	   if(element.attributes.id && found >= 0) {
                     for(var i = 0;i<element.children.length;i++) {
                       if(element.children[i].value) {
                         element.children[i].value = element.children[i].value.replace(/script>/g, "hcms_script>");
                       }
                     }
                   }

                  var ed = editor.createFakeParserElement( element, fakeClass, fakeName_span, true );
                  ed.attributes.title = editor.lang.hcms_video.fakeTitle;
                  ed.attributes.alt = editor.lang.hcms_video.fakeTitle;
                  return ed;
                }
                return element;
              },
              'iframe' : function ( element ) {
                // We only apply it to our video elements
                if(element.attributes.id) {
                  var found = element.attributes.id.indexOf(iframePreFix);
                  var found2 = element.attributes.id.indexOf(divIdPreFix_old);
                  if(element.attributes.id && (found >= 0 || found2 >= 0)) {
                    var ed = editor.createFakeParserElement( element, fakeClass, fakeName_iframe, true );
                    ed.attributes.title = editor.lang.hcms_video.fakeTitle;
                    ed.attributes.alt = editor.lang.hcms_video.fakeTitle;
                    return ed;
                  }
                }
              }
            }
          },
          // Maximum priority
          1
          );
        }
      }
    }
    
  );
  
  // German translations
  CKEDITOR.plugins.setLang( 'hcms_video', 'de', {
    title : html_entity_decode('Video'),
    menu : {
      main : html_entity_decode('Video'),
      context : html_entity_decode('Video bearbeiten')
    },
    tab : {
      gen : {
        title : html_entity_decode('Allgemein'),
        poster : {
          title : html_entity_decode('Vorschaubild'),
          browser : html_entity_decode('Bilder durchsuchen')
        },
        video : {
          title : html_entity_decode('Video'),
          empty : html_entity_decode('Video ausw&auml;hlen'),
          browser: html_entity_decode('Videos durchsuchen')
        },
        height : {
          title : html_entity_decode('H&ouml;he'),
          incorrectNumber : html_entity_decode('H&ouml;he muss eine Zahl sein und gr&ouml;&szlig;er als 0') 
        },
        width : {
          title : html_entity_decode('Breite'),
          incorrectNumber : html_entity_decode('Breite muss eine Zahl sein und gr&ouml;&szlig;er als 0')
        },
        preview : html_entity_decode('Vorschau')
      },
      adv : {
        title : html_entity_decode('Erweitert'),
        titlefield : html_entity_decode('Titel'),
        id : html_entity_decode('Id'),
        enable : {
          title : html_entity_decode('Aktivieren/Deaktivieren'),
          autoplay : html_entity_decode('Automatisches Abspielen'),
          fullScreen : html_entity_decode('Vollbild'),
          keyBoard : html_entity_decode('Keyboard Befehle')
        }
      }
    },
    fakeTitle : 'Integriertes Video'
  });
  // English translations
  CKEDITOR.plugins.setLang( 'hcms_video', 'en', {
    title : html_entity_decode('Video'),
    menu : {
      main : html_entity_decode('Video'),
      context : html_entity_decode('edit video')
    },
    tab : {
      gen : {
        title : html_entity_decode('General'),
        poster : {
          title : html_entity_decode('Preview Image'),
          browser : html_entity_decode('Browse Images')
        },
        video : {
          title : html_entity_decode('Video'),
          empty : html_entity_decode('Select videos'),
          browser: html_entity_decode('Browse videos')
        },
        height : {
          title : html_entity_decode('Height'),
          incorrectNumber : html_entity_decode('Height must be a number and greater than 0') 
        },
        width : {
          title : html_entity_decode('Width'),
          incorrectNumber : html_entity_decode('Width must be a number and greater than 0')
        },
        preview : html_entity_decode('Preview')
      },
      adv : {
        title : html_entity_decode('Advanced'),
        titlefield : html_entity_decode('Title'),
        id : html_entity_decode('Id'),
        enable : {
          title : html_entity_decode('Active/Deactivate'),
          autoplay : html_entity_decode('Autoplay'),
          fullScreen : html_entity_decode('Fullscreen'),
          keyBoard : html_entity_decode('Keyboard Commands')
        }
      }
    },
    fakeTitle : 'Embedded Video'
  });
  
})();
