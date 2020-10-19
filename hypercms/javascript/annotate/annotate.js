/*
djaodjin-annotate.js v0.0.4
Copyright (c) 2015, Djaodjin Inc.
MIT License

Modified by hyperCMS
- Modified toolbar
- Added support for undo/redo button in checkUndoRedo
- Added methode flatten in order to save the image
- Fixed bug for options.width and options.height
- Added support for clickevent to prevent face detection on click
- Added annoationStop as default event (no tool selected)
- Added zoomfactor for resizing 
- Added support for the display of the actived tool 
*/

/* global document jQuery Image window:true */

/* save the status of new annotations that have been made in order to save them */
var annotatestatus = false;


(function($) {
  'use strict';
  /**
   * Function to annotate the image
   * @param {[type]} el      [description]
   * @param {Object} options [description]
   */
   
  function Annotate(el, options) {
    this.options = options;
    this.$el = $(el);
    this.clicked = false;
    this.fromx = null;
    this.fromy = null;
    this.fromxText = null;
    this.fromyText = null;
    this.tox = null;
    this.toy = null;
    this.points = [];
    this.storedUndo = [];
    this.storedElement = [];
    this.images = [];
    this.img = null;
    this.selectedImage = null;
    this.currentWidth = null;
    this.currentHeight = null;
    this.selectImageSize = {};
    this.compensationWidthRate = 1;
    this.zoomfactor = 1;
    this.penmouse = 0;
    this.linewidth = 1;
    this.fontsize = 1;
    this.init();
  }
  
  Annotate.prototype = {
    init: function() {     
      var self = this;
      self.linewidth = self.options.linewidth;
      self.fontsize = self.options.fontsize;
      self.$el.addClass('annotate-container');
      self.$el.css({
        cursor: 'crosshair'
      });
      self.baseLayerId = 'baseLayer_' + self.$el.attr('id');
      self.drawingLayerId = 'drawingLayer_' + self.$el.attr('id');
      self.toolOptionId = 'tool_option_' + self.$el.attr('id');
      self.$el.append($('<canvas id="' + self.baseLayerId + '"></canvas>'));
      self.$el.append($('<canvas id="' + self.drawingLayerId +
        '"></canvas>'));
      self.baseCanvas = document.getElementById(self.baseLayerId);
      self.drawingCanvas = document.getElementById(self.drawingLayerId);
      self.baseContext = self.baseCanvas.getContext('2d');
      self.drawingContext = self.drawingCanvas.getContext('2d');
      self.baseContext.lineJoin = 'round';
      self.drawingContext.lineJoin = 'round';
      var classPosition1 = 'btn-group';
      var classPosition2 = '';
      
      if (self.options.position === 'left' || self.options.position ===
        'right') {
        classPosition1 = 'btn-group-vertical';
        classPosition2 = 'btn-block';
      }
      
      /* detect MS IE and EDGE  */
      var ua = window.navigator.userAgent;
      // MS IE 10
      var msie10 = ua.indexOf("MSIE ");
      // MS IE 11
      var msie11 = ua.indexOf("Trident");
      // MS Edge
      var msedge = ua.indexOf("Edge");
      // MS based browser
      if (msie10 > -1 || msie11 > -1 || msedge > -1) var msbrowser = true;
      else var msbrowser = false;
      
      if (self.options.bootstrap) {
        self.$tool = '<div id="" class="btn-group" role="group" >' +
          '<div class="' + classPosition1 + '" data-toggle="buttons">' +
          '<button id="undoaction" title="Undo the last annotation"' +
          ' class="btn btn-primary ' + classPosition2 +
          ' annotate-undo">' +
          ' <span class="glyphicon glyphicon-arrow-left"></span></button>';
        if (self.options.unselectTool) {
          self.$tool += '<label class="btn btn-danger active">' +
            '<input type="radio" name="' + self.toolOptionId +
            '" data-tool="null"' +
            ' data-toggle="tooltip" data-placement="top"' +
            ' title="No tool selected">' +
            '<span class="glyphicon glyphicon-ban-circle"></span>' +
            '</label>';
        }
        self.$tool += '<label class="btn btn-primary active">' +
          '<input type="radio" name="' + self.toolOptionId +
          '" data-tool="rectangle"' +
          ' data-toggle="tooltip" data-placement="top"' +
          ' title="Draw an rectangle">' +
          ' <span class="glyphicon glyphicon-unchecked"></span>' +
          '</label><label class="btn btn-primary">' +
          '<input type="radio" name="' + self.toolOptionId +
          '" data-tool="circle"' +
          ' data-toggle="tooltip"' +
          'data-placement="top" title="Write some text">' +
          ' <span class="glyphicon glyphicon-copyright-mark"></span>' +
          '</label><label class="btn btn-primary">' +
          '<input type="radio" name="' + self.toolOptionId +
          '" data-tool="text"' +
          ' data-toggle="tooltip"' +
          'data-placement="top" title="Write some text">' +
          ' <span class="glyphicon glyphicon-font"></span></label>' +
          '<label class="btn btn-primary">' +
          '<input type="radio" name="' + self.toolOptionId +
          '" data-tool="arrow"' +
          ' data-toggle="tooltip" data-placement="top" title="Draw an arrow">' +
          ' <span class="glyphicon glyphicon-arrow-up"></span></label>' +
          '<label class="btn btn-primary">' +
          '<input type="radio" name="' + self.toolOptionId +
          '" data-tool="pen"' +
          ' data-toggle="tooltip" data-placement="top" title="Pen Tool">' +
          ' <span class="glyphicon glyphicon-pencil"></span></label>' +
          '<button type="button" id="redoaction"' +
          ' title="Redo the last undone annotation"' +
          'class="btn btn-primary ' + classPosition2 + ' annotate-redo">' +
          ' <span class="glyphicon glyphicon-arrow-right"></span></button>' +
          '</div></div>';
      }else{
        self.$tool = "<div id=\"annotationToolbar\" style=\"display:inline-block; margin-top:-4px; margin-left:-13px; white-space:nowrap; min-width:500px;\">";

        if (self.options.unselectTool){
          self.$tool += "<div class=\"hcmsToolbarBlock\" style=\"white-space:nowrap;\">"
          + "<div class=\"hcmsButton hcmsButtonActive hcmsButtonSizeSquare\" id=\"tool0\"><label style=\"display:inline-block; float:left; margin-top:-16px; cursor:pointer;\"><input type=\"radio\" style=\"float:left; visibility:hidden\" name=\"" + self.toolOptionId + "\" data-tool=\"null\" checked /><img id=\"annotationStop\" src=\"\" class=\"hcmsButtonSizeSquare\" style=\"pointer-events:none; float:left;\" /></label></div>"
          + "</div>";
        }

        self.$tool += "<div class=\"hcmsToolbarBlock\" style=\"white-space:nowrap;\">"
          + "<div class=\"hcmsButton hcmsButtonSizeSquare\" id=\"tool1\"><label style=\"display:inline-block; float:left; margin-top:-16px; cursor:pointer;\"><input type=\"radio\" style=\"float:left; visibility:hidden\" name=\"" + self.toolOptionId + "\" data-tool=\"rectangle\" /><img id=\"annotationRectangle\" src=\"\" class=\"hcmsButtonSizeSquare\" style=\"pointer-events:none; float:left;\" /></label></div>"
          + "<div class=\"hcmsButton hcmsButtonSizeSquare\" id=\"tool2\"><label style=\"display:inline-block; float:left; margin-top:-16px; cursor:pointer;\"><input type=\"radio\" style=\"float:left; visibility:hidden\" name=\"" + self.toolOptionId + "\" data-tool=\"circle\" /><img id=\"annotationCircle\" src=\"\" class=\"hcmsButtonSizeSquare\" style=\"pointer-events:none; float:left;\" /></label></div>"
          + "<div class=\"hcmsButton hcmsButtonSizeSquare\" id=\"tool3\"><label style=\"display:inline-block; float:left; margin-top:-16px; cursor:pointer;\"><input type=\"radio\" style=\"float:left; visibility:hidden\" name=\"" + self.toolOptionId + "\" data-tool=\"text\" /><img id=\"annotationText\" src=\"\" class=\"hcmsButtonSizeSquare\" style=\"pointer-events:none; float:left;\" /></label></div>"
          + "<div class=\"hcmsButton hcmsButtonSizeSquare\" id=\"tool4\"><label style=\"display:inline-block; float:left; margin-top:-16px; cursor:pointer;\"><input type=\"radio\" style=\"float:left; visibility:hidden\" name=\"" + self.toolOptionId + "\" data-tool=\"arrow\" /><img id=\"annotationArrow\" src=\"\" class=\"hcmsButtonSizeSquare\" style=\"pointer-events:none; float:left;\" /></label></div>";

        if (msbrowser == false) {
          self.$tool += "<div class=\"hcmsButton hcmsButtonSizeSquare\" id=\"tool5\"><label style=\"display:inline-block; float:left; margin-top:-16px; cursor:pointer;\"><input type=\"radio\" style=\"float:left; visibility:hidden\" name=\"" + self.toolOptionId + "\" data-tool=\"pen\" /><img id=\"annotationPen\" src=\"\" class=\"hcmsButtonSizeSquare\" style=\"pointer-events:none; float:left;\" /></label></div>";
        }
        
        self.$tool += "</div>"
          + "<div class=\"hcmsToolbarBlock\" style=\"white-space:nowrap;\">";
          
        if (msbrowser == false) {
          self.$tool += "<div id=\"download\" class=\"hcmsButton hcmsButtonSizeSquare\"><img id=\"annotationDownload\" src=\"\" class=\"hcmsButtonSizeSquare\" /></div>";
        }
        
        self.$tool += "</div>"
          + "<div class=\"hcmsToolbarBlock\" style=\"white-space:nowrap;\">"
          + "<div id=\"undoaction\" class=\"annotate-undo hcmsButtonOff hcmsButtonSizeSquare\"><img id=\"annotationUndo\" src=\"\" class=\"hcmsButtonSizeSquare\" /></div>"
          + "<div id=\"redoaction\" class=\"annotate-redo hcmsButtonOff hcmsButtonSizeSquare\"><img id=\"annotationRedo\" src=\"\" class=\"hcmsButtonSizeSquare\" /></div>"
          + "</div>"
          + "<div id=\"zoomlayer\" class=\"hcmsToolbarBlock\" style=\"white-space:nowrap; display:none;\">"
          + "<div id=\"zoomaction\" style=\"vertical-align:center; line-height:36px;\"><select id=\"annotationZoom\" name=\"annotationZoom\"><option value='1' selected>100%</option><option value='0.75'>75%</option><option value='0.5'>50%</option></select></div>"
          + "</div>"
          + "<div class=\"hcmsToolbarBlock\" style=\"white-space:nowrap;\">"
          + "<div id=\"help\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\"><img id=\"annotationHelp\" src=\"\" class=\"hcmsButtonSizeSquare\" /></div>"
          + "</div>"
          + "</div>";
      }
      
      self.$tool = $(self.$tool);
      $('.annotate-container').append(self.$tool);
      var canvasPosition = self.$el.offset();
      
      if (self.options.position === 'top' || self.options.position !== 'top' && !self.options.bootstrap) {
        self.$tool.css({
          position: 'absolute',
          top: -35,
          left: canvasPosition.left
        });
      } else if (self.options.position === 'left' && self.options.bootstrap) {
        self.$tool.css({
          position: 'absolute',
          top: canvasPosition.top - 35,
          left: canvasPosition.left - 20
        });
      } else if (self.options.position === 'right' && self.options.bootstrap) {
        self.$tool.css({
          position: 'absolute',
          top: canvasPosition.top - 35,
          left: canvasPosition.left + self.baseCanvas.width + 20
        });
      } else if (self.options.position === 'bottom' && self.options.bootstrap) {
        self.$tool.css({
          position: 'absolute',
          top: canvasPosition.top + self.baseCanvas.height + 35,
          left: canvasPosition.left
        });
      }
      
      self.$textbox = $('<textarea id=""' +
        ' style="resize:none; position:absolute; z-index:100000; display:none; top:0; left:0; ' +
        'background:transparent; border:1px dotted; line-height:25px; ' +
        ';font-size:' + self.fontsize +
        ';font-family:sans-serif; color:' + self.options.color +
        ';word-wrap:break-word; outline-width:0; overflow:hidden; ' +
        'padding:0px;"></textarea>');
      $('body').append(self.$textbox);
      
      if (self.options.images) {
        self.initBackgroundImages();
      } else {
        if (!self.options.width && !self.options.height) {
          self.options.width = 640;
          self.options.height = 480;
        }
        self.baseCanvas.width = self.drawingCanvas.width = self.options.width;
        self.baseCanvas.height = self.drawingCanvas.height = self.options.height;
      }
      
      // radio button tool selector
      self.$tool.on('change', 'input[name^="tool_option"]', function() {
        // set click event
        clickevent = 'annotate';
        
        // dectivate toolbar buttons
        var toolbar = document.getElementById('annotationToolbar');      
        var div = toolbar.getElementsByClassName('hcmsButton');
        var classes;
        
        for (var i=0; i<div.length; i++)
        {
          classes = div[i].classList;
          if (classes.contains('hcmsButtonActive')) classes.remove('hcmsButtonActive');
        }
        
        // activate selected toolbar button
        $(this).parent().parent().addClass('hcmsButtonActive');
        
        self.selectTool($(this));
      });
      
      self.$tool.on('change', 'select[name^="annotationZoom"]', function() {
        self.zoomaction($(this).val());
      });
      
      $('[data-tool="' + self.options.type + '"]').trigger('click');
      
      self.$tool.on('click', '.annotate-redo', function(event) {
        self.redoaction(event);
      });
      
      self.$tool.on('click', '.annotate-undo', function(event) {
        self.undoaction(event);
      });
      
      $(document).on(self.options.selectEvent, '.annotate-image-select',
        function(event) {
          event.preventDefault();
          var image = self.selectBackgroundImage($(this).attr(self.options
            .idAttribute));
          self.setBackgroundImage(image);
        });
      $('#' + self.drawingLayerId).on('mousedown touchstart', function(
        event) {
        self.annotatestart(event);
      });
      
      $('#' + self.drawingLayerId).on('mouseup touchend', function(event) {
        self.annotatestop(event);
      });
      
      // https://developer.mozilla.org/en-US/docs/Web/Events/touchleave
      $('#' + self.drawingLayerId).on('mouseleave touchleave', function(
        event) {
        self.annotateleave(event);
      });
      
      $('#' + self.drawingLayerId).on('mousemove touchmove', function(
        event) {
        self.annotatemove(event);
      });
      
      $(window).on('resize', function() {
        self.annotateresize();
      });
      
      self.checkUndoRedo();
      clickevent = '';
    },
    
    generateId: function(length) {
      var chars =
        '0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz'.split(
          '');
      var charsLen = chars.length;
      if (!length) {
        length = Math.floor(Math.random() * charsLen);
      }
      var str = '';
      for (var i = 0; i < length; i++) {
        str += chars[Math.floor(Math.random() * charsLen)];
      }
      return str;
    },

    addElements: function(newStoredElements, set, callback)
    {
      var self = this; 
      this.storedElement = newStoredElements; 
      self.clear();
      self.redraw();
    },
      
    pushImage: function(newImage, set, callback) {
      var self = this;
      var id = null;
      var path = null;
      if (typeof newImage === 'object') {
        id = newImage.id;
        path = newImage.path;
      } else {
        id = newImage;
        path = newImage;
      }
      if (id === '' || typeof id === 'undefined' || self.selectBackgroundImage(
          id)) {
        id = self.generateId(10);
        while (self.selectBackgroundImage(id)) {
          id = self.generateId(10);
        }
      }
      var image = {
        id: id,
        path: path,
        storedUndo: [],
        storedElement: []
      };
      self.images.push(image);
      if (set) {
        self.setBackgroundImage(image);
      }
      if (callback) {
        callback({
          id: image.id,
          path: image.path
        });
      }
      self.$el.trigger('annotate-image-added', [
        image.id,
        image.path
      ]);
    },
    
    initBackgroundImages: function() {
      var self = this;
      $.each(self.options.images, function(index, image) {
        var set = false;
        if (index === 0) {
          set = true;
        }
        self.pushImage(image, set);
      });
    },
    
    selectBackgroundImage: function(id) {
      var self = this;
      var image = $.grep(self.images, function(element) {
        return element.id === id;
      })[0];
      return image;
    },
    
    setBackgroundImage: function(image) {
      var self = this;
      if (self.$textbox.is(':visible')) {
        self.pushText();
      }
      var currentImage = self.selectBackgroundImage(self.selectedImage);
      if (currentImage) {
        currentImage.storedElement = self.storedElement;
        currentImage.storedUndo = self.storedUndo;
      }
      self.img = new Image();
      self.img.src = image.path;
      self.img.onload = function() {
        if ((self.options.width && self.options.height) !== undefined || (self.options.width && self.options.height) !== 0) {
          self.currentWidth = self.options.width;
          self.currentHeight = self.options.height;
          self.selectImageSize.width = self.options.width;
          self.selectImageSize.height = self.options.height;
        } else {
          self.currentWidth = this.width;
          self.currentHeight = this.height;
          self.selectImageSize.width = this.width;
          self.selectImageSize.height = this.height;
        }
        self.baseCanvas.width = self.drawingCanvas.width = self.currentWidth;
        self.baseCanvas.height = self.drawingCanvas.height = self.currentHeight;
        self.baseContext.drawImage(self.img, 0, 0, self.currentWidth,
          self.currentHeight);
        self.$el.css({
          height: self.currentHeight,
          width: self.currentWidth
        });
        self.storedElement = image.storedElement;
        self.storedUndo = image.storedUndo;
        self.selectedImage = image.id;
        self.checkUndoRedo();
        self.clear();
        self.redraw();
        self.annotateresize();
      };
    },
    
    checkUndoRedo: function() {
      var self = this;
      if (self.storedUndo.length === 0){
        self.$tool.children(".annotate-redo").attr("disabled", true);
        $("#redoaction").addClass("hcmsButtonOff");
        $("#redoaction").removeClass("hcmsButton");
      }else{
        self.$tool.children(".annotate-redo").attr("disabled", false);
        $("#redoaction").addClass("hcmsButton");
        $("#redoaction").removeClass("hcmsButtonOff");
      }
      if (self.storedElement.length === 0){
        self.$tool.children(".annotate-undo").attr("disabled", true);
        $("#undoaction").addClass("hcmsButtonOff");
        $("#undoaction").removeClass("hcmsButton");
      }else{
        self.$tool.children(".annotate-undo").attr("disabled", false);
        $("#undoaction").addClass("hcmsButton");
        $("#undoaction").removeClass("hcmsButtonOff");
      }
    },
    
    undoaction: function(event) {
      event.preventDefault();
      var self = this;
      self.storedUndo.push(self.storedElement[self.storedElement.length -1]);
      self.storedElement.pop();
      self.checkUndoRedo();
      self.clear();
      self.redraw();
    },
    
    redoaction: function(event) {
      event.preventDefault();
      var self = this;
      self.storedElement.push(self.storedUndo[self.storedUndo.length - 1]);
      self.storedUndo.pop();
      self.checkUndoRedo();
      self.clear();
      self.redraw();
    },
    
    redraw: function() {
      var self = this;
      self.baseCanvas.width = self.baseCanvas.width;
      
      // hide and disable face markers if zoom is not 100%
      if (self.zoomfactor != 1) {
        hideFaceOnImage();
        clickevent = 'annotate';
      }
      else {
        showFaceOnImage();
      }
      
      // apply zoom factor
      var width = self.options.width * self.zoomfactor;
      var height = self.options.height * self.zoomfactor;
     
      self.currentWidth = width;
      self.currentHeight = height;
      self.baseCanvas.width = self.drawingCanvas.width = width;
      self.baseCanvas.height = self.drawingCanvas.height = height;
      $("#annotationFrame").width(width);
      $("#annotationFrame").height((height + 4));
      $("#annotation").width(width);
      $("#annotation").height(height);
      self.drawingContext.scale(self.zoomfactor, self.zoomfactor);
      
      // resize base image
      if (self.options.images) {
        self.baseContext.drawImage(self.img, 0, 0, self.currentWidth, self.currentHeight);
        self.baseContext.scale(self.zoomfactor, self.zoomfactor);
      }

      if (self.storedElement.length > 0) {
        // redraw each stored annotation
        for (var i = 0; i < self.storedElement.length; i++) {
          var element = self.storedElement[i];

          switch (element.type) {
            case 'rectangle':
              self.drawRectangle(self.baseContext, element.fromx, element.fromy, element.tox, element.toy);
              break;
            case 'arrow':
              self.drawArrow(self.baseContext, element.fromx, element.fromy, element.tox, element.toy);
              break;
            case 'pen':
              for (var b = 0; b < element.points.length - 1; b++) {
                var fromx = element.points[b][0];
                var fromy = element.points[b][1];
                var tox = element.points[b + 1][0];
                var toy = element.points[b + 1][1];
                self.drawPen(self.baseContext, fromx, fromy, tox, toy);
              }
              break;
            case 'text':
              self.drawText(self.baseContext, element.text, element.fromx, element.fromy, element.maxwidth);
              break;
            case 'circle':
              self.drawCircle(self.baseContext, element.fromx, element.fromy, element.tox, element.toy);
              break;
            default:
          }
        }
      }
    },
    
    clear: function() {
      var self = this;
      // Clear Canvas
      self.drawingCanvas.width = self.drawingCanvas.width;
    },
    
    drawRectangle: function(context, x, y, w, h) {
      var self = this;

      context.beginPath();
      context.rect(x, y, w, h);
      context.fillStyle = 'transparent';
      context.fill();
      context.lineWidth = self.linewidth;
      context.strokeStyle = self.options.color;
      context.stroke();
    },
    
    drawCircle: function(context, x1, y1, x2, y2) {
      var radiusX = (x2 - x1) * 0.5;
      var radiusY = (y2 - y1) * 0.5;
      var centerX = x1 + radiusX;
      var centerY = y1 + radiusY;
      var step = 0.05;
      var a = step;
      var pi2 = Math.PI * 2 - step;
      var self = this;

      context.beginPath();
      context.moveTo(centerX + radiusX * Math.cos(0), centerY + radiusY * Math.sin(0));
      for (; a < pi2; a += step) {
        context.lineTo(centerX + radiusX * Math.cos(a), centerY + radiusY * Math.sin(a));
      }
      context.lineWidth = self.linewidth;
      context.strokeStyle = self.options.color;
      context.closePath();
      context.stroke();
    },
    
    drawArrow: function(context, x, y, w, h) {
      var self = this;
      var angle = Math.atan2(h - y, w - x);

      context.beginPath();
      context.lineWidth = self.linewidth;
      context.moveTo(x, y);
      context.lineTo(w, h);
      context.moveTo(w - self.linewidth * 5 * Math.cos(angle + Math.PI / 6), h - self.linewidth * 5 * Math.sin(angle + Math.PI / 6));
      context.lineTo(w, h);
      context.lineTo(w - self.linewidth * 5 * Math.cos(angle - Math.PI / 6), h - self.linewidth * 5 * Math.sin(angle - Math.PI / 6));
      context.strokeStyle = self.options.color;
      context.stroke();
    },
    
    drawPen: function(context, fromx, fromy, tox, toy) {
      var self = this;

      context.lineWidth = self.linewidth;
      context.moveTo(fromx, fromy);
      context.lineTo(tox, toy);
      context.strokeStyle = self.options.color;
      context.stroke();
    },
    
    wrapText: function(drawingContext, text, x, y, maxWidth, lineHeight) {
      var lines = text.split('\n');
      for (var i = 0; i < lines.length; i++) {
        var words = lines[i].split(' ');
        var line = '';
        for (var n = 0; n < words.length; n++) {
          var testLine = line + words[n] + ' ';
          var metrics = drawingContext.measureText(testLine);
          var testWidth = metrics.width;
          if (testWidth > maxWidth && n > 0) {
            drawingContext.fillText(line, x, y);
            line = words[n] + ' ';
            y += lineHeight;
          } else {
            line = testLine;
          }
        }
        drawingContext.fillText(line, x, y + i * lineHeight);
      }
    },
    
    drawText: function(context, text, x, y, maxWidth) {
      var self = this;

      context.font = self.fontsize + ' sans-serif';
      context.textBaseline = 'top';
      context.fillStyle = self.options.color;
      self.wrapText(context, text, x + 3, y + 4, maxWidth, 25);
    },
    
    pushText: function() {
      var self = this;
      var text = self.$textbox.val();
      self.$textbox.val('').hide();
      if (text) {
        self.storedElement.push({
          type: 'text',
          text: text,
          fromx: self.fromx,
          fromy: self.fromy,
          maxwidth: self.tox
        });
        if (self.storedUndo.length > 0) {
          self.storedUndo = [];
        }
      }
      self.checkUndoRedo();
      self.redraw();
    },
  
    // Events
    selectTool: function(element) {
      if (element.data('tool') != null) clickevent = 'annotate';
      else clickevent = '';
      
      var self = this;
      self.options.type = element.data('tool');
      if (self.$textbox.is(':visible')) {
        self.pushText();
      }
    },
    
    annotatestart: function(event) {
      var self = this;
      self.clicked = true;
      var offset = self.$el.offset();
      if (self.$textbox.is(':visible')) {
        var text = self.$textbox.val();
        self.$textbox.val('').hide();
        if (text !== '') {
          if (!self.tox) {
            self.tox = 200;
          }
          self.storedElement.push({
            type: 'text',
            text: text,
            fromx: (self.fromxText - offset.left) * self.compensationWidthRate,
            fromy: (self.fromyText - offset.top) * self.compensationWidthRate,
            maxwidth: self.tox
          });
          if (self.storedUndo.length > 0) {
            self.storedUndo = [];
          }
        }
        self.checkUndoRedo();
        self.redraw();
        self.clear();
      }
      self.tox = null;
      self.toy = null;
      self.points = [];
      var pageX = event.pageX || event.originalEvent.touches[0].pageX;
      var pageY = event.pageY || event.originalEvent.touches[0].pageY;
      self.fromx = (pageX - offset.left) * self.compensationWidthRate;
      self.fromy = (pageY - offset.top) * self.compensationWidthRate;
      self.fromxText = pageX;
      self.fromyText = pageY;
      if (self.options.type === 'text') {
        self.$textbox.css({
          left: self.fromxText + 2,
          top: self.fromyText,
          width: 0,
          height: 0
        }).show();
      }
      if (self.options.type === 'pen') {
        self.points.push([
          self.fromx,
          self.fromy
        ]);
      }
    },
    
    annotatestop: function() {
      var self = this;
      self.clicked = false;
      annotatestatus = true;
       
      if (self.toy !== null && self.tox !== null) {
        switch (self.options.type) {
          case 'rectangle':
            self.storedElement.push({
              type: 'rectangle',
              fromx: self.fromx,
              fromy: self.fromy,
              tox: self.tox,
              toy: self.toy
            });
            break;
          case 'circle':
            self.storedElement.push({
              type: 'circle',
              fromx: self.fromx,
              fromy: self.fromy,
              tox: self.tox,
              toy: self.toy
            });
            break;
          case 'arrow':
            self.storedElement.push({
              type: 'arrow',
              fromx: self.fromx,
              fromy: self.fromy,
              tox: self.tox,
              toy: self.toy
            });
            break;
          case 'text':
            self.$textbox.css({
              left: self.fromxText + 2,
              top: self.fromyText,
              width: self.tox - 12,
              height: self.toy
            });
            break;
          case 'pen':
            self.storedElement.push({
              type: 'pen',
              points: self.points
            });
            for (var i = 0; i < self.points.length - 1; i++) {
              self.fromx = self.points[i][0];
              self.fromy = self.points[i][1];
              self.tox = self.points[i + 1][0];
              self.toy = self.points[i + 1][1];
              self.drawPen(self.baseContext, self.fromx, self.fromy, self.tox, self.toy);
            }
            self.points = [];
            self.penmouse = 1;
            break;
          default:
        }
        if (self.storedUndo.length > 0) {
          self.storedUndo = [];
        }
        self.checkUndoRedo();
        self.redraw();
      } else if (self.options.type === 'text') {
        self.$textbox.css({
          left: self.fromxText + 2,
          top: self.fromyText,
          width: 200,
          height: 25
        });
      }
    },
    
    annotateleave: function(event) {
      var self = this;
      if (self.clicked) {
        self.annotatestop(event);
      }
    },
    
    // display of annotations for mouse movement
    annotatemove: function(event) {
      var self = this;
      if (self.options.type) {
        event.preventDefault();
      }
      if (!self.clicked) {
        return;
      }
      var offset = self.$el.offset();
      var pageX = event.pageX || event.originalEvent.touches[0].pageX;
      var pageY = event.pageY || event.originalEvent.touches[0].pageY;
      switch (self.options.type) {
        case 'rectangle':
          self.clear();
          self.tox = (pageX - offset.left) * self.compensationWidthRate - self.fromx;
          self.toy = (pageY - offset.top) * self.compensationWidthRate - self.fromy;
          self.drawRectangle(self.drawingContext, self.fromx * self.zoomfactor, self.fromy * self.zoomfactor, self.tox * self.zoomfactor, self.toy * self.zoomfactor);
          break;
        case 'arrow':
          self.clear();
          self.tox = (pageX - offset.left) * self.compensationWidthRate;
          self.toy = (pageY - offset.top) * self.compensationWidthRate;
          self.drawArrow(self.drawingContext, self.fromx * self.zoomfactor, self.fromy * self.zoomfactor, self.tox * self.zoomfactor, self.toy * self.zoomfactor);
          break;
        case 'pen':
          self.tox = (pageX - offset.left) * self.compensationWidthRate;
          self.toy = (pageY - offset.top) * self.compensationWidthRate;
          self.fromx = self.points[self.points.length - 1][0];
          self.fromy = self.points[self.points.length - 1][1];
          self.points.push([
            self.tox,
            self.toy
          ]);
          var zoom = self.zoomfactor;
          if (self.penmouse > 0) zoom = 1;
          self.drawPen(self.drawingContext, self.fromx * zoom, self.fromy * zoom, self.tox * zoom, self.toy * zoom);
          break;
        case 'text':
          self.clear();
          self.tox = (pageX - self.fromxText) * self.compensationWidthRate;
          self.toy = (pageY - self.fromyText) * self.compensationWidthRate;
          self.$textbox.css({
            left: self.fromxText * self.zoomfactor + 2,
            top: self.fromyText * self.zoomfactor,
            width: self.tox * self.zoomfactor - 12,
            height: self.toy * self.zoomfactor
          });
          break;
        case 'circle':
          self.clear();
          self.tox = (pageX - offset.left) * self.compensationWidthRate;
          self.toy = (pageY - offset.top) * self.compensationWidthRate;
          self.drawCircle(self.drawingContext, self.fromx * self.zoomfactor, self.fromy * self.zoomfactor, self.tox * self.zoomfactor, self.toy * self.zoomfactor);
          break;
        default:
      }
    },
    
    annotateresize: function() {
      var self = this;
      var currentWidth = self.$el.width();
      var currentcompensationWidthRate = self.compensationWidthRate;
      self.compensationWidthRate = self.selectImageSize.width / currentWidth;
        
      if (self.compensationWidthRate < 1) {
        self.compensationWidthRate = 1;
      }
      
      self.linewidth = self.options.linewidth * self.compensationWidthRate * self.zoomfactor;
      self.fontsize = String(parseInt(self.options.fontsize.split('px')[0], 10) * self.compensationWidthRate * self.zoomfactor) + 'px';
      
      if (currentcompensationWidthRate !== self.compensationWidthRate) {
        self.redraw();
        self.clear();
      }
    },
    
    zoomaction: function(factor) {
      if (factor > 0) {
        var self = this;
        self.zoomfactor = factor;
        self.clear();
        self.redraw();
        self.annotateresize();
        self.penmouse = 0;
      }
    },
    
    flatten: function(){
      var self = this;
      var zoombefore = self.zoomfactor;
      if (zoombefore != 1) {
        self.zoomfactor = 1;
        self.redraw();
      }
      var canvases = self.$el.find("canvas");
      var canvas = document.createElement("canvas");
      var ctx = null;
      if (self.$textbox.is(':visible')) {
        self.pushText();
      }
      for (var idx = 0; idx < canvases.length; ++idx) {
        if (idx === 0) {
          canvas.width = canvases[idx].width;
          canvas.height = canvases[idx].height;
          ctx = canvas.getContext('2d');
        }
        ctx.drawImage(canvases[idx], 0, 0);
      }
      
      // create base64 encoded JPEG image data
      var image = canvas.toDataURL("image/jpeg", 0.95);

      // save image data to field mediadata
      $('#mediadata').val(image);
      
      // restore zoom
      if (zoombefore != self.zoomfactor) {
        self.zoomfactor = zoombefore;
        self.redraw();
      }
    },
    
    destroy: function() {
      var self = this;
      $(document).off(self.options.selectEvent, '.annotate-image-select');
      self.$tool.remove();
      self.$textbox.remove();
      self.$el.children('canvas').remove();
      self.$el.removeData('annotate');
    },
    
    exportImage: function(options, callback) {
      var self = this;
      if (self.$textbox.is(':visible')) {
        self.pushText();
      }
      // create base64 encoded JPEG image data
      var exportDefaults = {
        type: 'image/jpeg',
        quality: 0.95
      };
      options = $.extend({}, exportDefaults, options);
      var image = self.baseCanvas.toDataURL(options.type, options.quality);
      if (callback) {
        callback(image);
      }
    }
  };
  
  $.fn.annotate = function(options, cmdOption, callback) {
    var $annotate = $(this).data('annotate');
    if (options === 'destroy') {
      if ($annotate) {
        $annotate.destroy();
      } else {
        throw new Error('No annotate initialized for: #' + $(this).attr(
          'id'));
      }
    } else if (options === 'push') {
      if ($annotate) {
        $annotate.pushImage(cmdOption, true, callback);
      } else {
        throw new Error('No annotate initialized for: #' + $(this).attr(
          'id'));
      }
    }else if (options === 'fill') {
      if ($annotate) {
        $annotate.addElements(cmdOption, true, callback);
      } else {
        throw new Error('No annotate initialized for: #' + $(this).attr(
          'id'));
      }
    } else if (options === 'export') {
      if ($annotate) {
        $annotate.exportImage(cmdOption, callback);
      } else {
        throw new Error('No annotate initialized for: #' + $(this).attr(
          'id'));
      }
    } else if (options === "flatten") {
      if ($(this).data("annotate")){
        $(this).data("annotate").flatten();
      }else{
        throw "No annotate initialized for: #" + $(this).attr("id");
      }
    } else {
      var opts = $.extend({}, $.fn.annotate.defaults, options);
      var annotate = new Annotate($(this), opts);
      $(this).data('annotate', annotate);
    }
  };
  
  $.fn.annotate.defaults = {
    width: null,
    height: null,
    images: [],
    color: 'red',
    type: 'null',
    linewidth: 2,
    fontsize: '20px',
    bootstrap: false,
    position: 'top',
    idAttribute: 'id',
    selectEvent: 'change',
    unselectTool: false
  };
})(jQuery);
