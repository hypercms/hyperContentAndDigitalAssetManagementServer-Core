/**
 *  JQuery ZoomCrop plugin
 *
 *  2008 - 2014 SwampyFoot 
 *
 *  @author	Domas Labokas <domas@swampyfoot.com>
 *  @copyright  Copyright (c) 2014, SwampyFoot
 *  @version	1.0
 *  @license	End User License Agreement (EULA)
 *  @link	http://www.swampyfoot.com
 *   
 */

jQuery.fn.ZoomCrop = function(options) 
{
	// Default settings:
	var defaults = 
	{
		initialZoom : 0.0,
		image : null,
		realWidth : 0,
		updated : function(size, crop, position){}
	};
	
	var settings = $.extend( {}, defaults, options ); 
	var args = arguments;
	
	return this.each(function() 
	{
		var $this = $(this);
		var zoomCrop = $this.data('ZoomCrop');
		if(!zoomCrop)
		{
			zoomCrop = new ZoomCrop($this);
			$this.data('ZoomCrop', zoomCrop);    		
		}
		
		if(typeof zoomCrop[options] == 'function')
			return zoomCrop[options].apply(zoomCrop, Array.prototype.slice.call(args, 1));
		else
			zoomCrop.init(settings);
		    	
		return zoomCrop;
	});
};

function ZoomCrop(target)
{
	this.settings = null;
	
	this.target = target;
	this.slider = null;
	this.dragcontainer = null;
	this.image = null;

	this.viewportRatio = 1;
	this.imageRatio = 1;
	this.imageSize = { width:0, height:0 };

	this.init = function(settings)
	{
		//console.log('ZoomCrop', 'init', settings);
	
		if(!this.settings)
		{
			this.target.addClass('ZoomCrop-viewport');

			this.slider = $('<div class="ZoomCrop-slider"></div>');
			this.slider.appendTo(this.target);

			this.dragcontainer = $('<div class="ZoomCrop-dragcontainer"></div>');			
			this.dragcontainer.appendTo(this.target);

			this.slider.slider(
			{
				value: settings.initialZoom * 100,
				slide: $.proxy(function(event, ui){ this._updateZoom(ui.value); }, this),
				stop: $.proxy(this._updated, this)
	      		});	
		}	
		
		// Set default actual width
		if(settings.realWidth == 0)
			settings.realWidth = this.target.width();
		
		if(settings.image)
			this.loadImage(settings.image);
		
		this.settings = settings;		
	};
	
	this.loadImage = function(src)
	{
		//console.log('ZoomCrop', 'loadImage', src, this);

		this.image = $("<img/>");
		this.target.addClass('ZoomCrop-loading');
		this.dragcontainer.find('img').remove();
		
		// Load image & get size 
		var self = this;		
		this.image.attr("src", src).load(function(e) 
		{
			self.imageSize.width = this.width;
			self.imageSize.height = this.height;
			self.target.removeClass('ZoomCrop-loading');
			self.image.appendTo(self.dragcontainer);
			self.image.draggable(
			{
				containment: self.dragcontainer,
				stop: $.proxy(self._updated, self)
			});
			
			self.viewportRatio = parseFloat(self.target.width()) / parseFloat(self.settings.realWidth);
			self.imageRatio =  parseFloat(self.imageSize.height) / parseFloat(self.imageSize.width);
						
			self.slider.slider('value', self.settings.initialZoom * 100);
			self.ajustLayout(self.settings.initialZoom);
			
			self._updated(e, self.image);
		});
	}
	
	this._updateZoom = function(zoom)
	{
		console.log('ZoomCrop', '_updateZoom', zoom);
		this.ajustLayout(parseFloat(zoom) * 0.01);
	};
	
	this.getZoom = function()
	{
		return this.slider.slider('value') * 0.01;
	}
	
	this.setZoom = function(zoom)
	{
		this.slider.slider('value', zoom * 100);
		this.ajustLayout(zoom);		
	}
	
	this.setPosition = function(position)
	{
		this.setZoom(position.z);
		this.image.css({ 'left':position.x,  'top':position.y });		
	}
			
	this.ajustLayout = function(zoom_ratio)
	{
		// Calculate current image center ratio
		var prevImagePosition = this.image.position();
		var prevImageCenterRatio = 
		{ 
			x : (this.dragcontainer.width() * 0.5 - prevImagePosition.left) / this.image.width(), 
			y : (this.dragcontainer.height() * 0.5 - prevImagePosition.top) / this.image.height()
		};
	
		// Calculate image size
		var maxW = this.viewportRatio * this.imageSize.width;
		var minW =  Math.min((this.viewportRatio < 1) ? this.target.width() : this.target.width() * this.viewportRatio, maxW);				
		var diff = (maxW - minW) * zoom_ratio;
		var imgW = Math.round(minW + diff);
		var imgH = Math.round(imgW * this.imageRatio);
		
		// Set image size
		this.image.width(imgW);
		this.image.height(imgH);

		// Calculate Drag Container size
		var dw = Math.round(imgW * 2 - this.target.width());
		var dh = Math.round(imgH * 2 - this.target.height());
	
		// Validate min size
		if(dw < this.target.width())
			dw = this.target.width();

		if(dh < this.target.height())
			dh = this.target.height();

		// Set Drag Container size		
		this.dragcontainer.width(dw);
		this.dragcontainer.height(dh);
		
		// Center Drag container
		this.dragcontainer.css(
		{ 
			left: Math.round((dw - this.target.width()) * -0.5), 
			top: Math.round((dh - this.target.height()) * -0.5) 
		});
		

		// Keep image center in drag container
		var imagePosition = { width:0, height:0 };

		imagePosition.left = Math.round((dw * 0.5) - prevImageCenterRatio.x * imgW);
		imagePosition.top =  Math.round((dh * 0.5) - prevImageCenterRatio.y * imgH);

		// Fix image position(keep it in drag container)
		if(imagePosition.left < 0)
			imagePosition.left = 0;
		
		if(imagePosition.top < 0)
			imagePosition.top = 0;

		if(imagePosition.left + imgW > dw)
			imagePosition.left = dw - imgW;
		
		if(imagePosition.top + imgH > dh)
			imagePosition.top = dh - imgH;

		// Set new image position
		this.image.css({ 'left':imagePosition.left,  'top':imagePosition.top });	
	}
		
	this._updated = function(event, ui)
	{
		//console.log('ZoomCrop', 'updated', event, ui);
		var imagePosition = this.image.position();
		
		// Resized image size
		var size = 
		{
			width : Math.round(this.image.width() / this.viewportRatio),
			height : Math.round(this.image.height() / this.viewportRatio)			
		};

		// Crop position and size
		var crop = 
		{
			x1 :  Math.round(((this.dragcontainer.width() * 0.5 - this.target.width() * 0.5) - imagePosition.left) / this.viewportRatio),
			y1 :  Math.round(((this.dragcontainer.height() * 0.5 - this.target.height() * 0.5) - imagePosition.top) / this.viewportRatio),
			x2 : 0,
			y2 : 0,
			width : Math.round(this.target.width() / this.viewportRatio),
			height : Math.round(this.target.height() / this.viewportRatio)
		};

		crop.x2 = crop.x1 + crop.width;
		crop.y2 = crop.y1 + crop.height;
		
		// Current image position state
		var position = 
		{
			x : imagePosition.left,
			y : imagePosition.top,
			z : this.getZoom()
		}

		this.settings.updated.call(this, size, crop, position);
	}
	
	this.unload = function()
	{
		//console.log('ZoomCrop', 'unload');
		this.target.find('.ZoomCrop-dragcontainer, .ZoomCrop-slider').remove();
		this.target.removeClass('ZoomCrop-viewport');
		this.target.data('ZoomCrop', null);
	}
}