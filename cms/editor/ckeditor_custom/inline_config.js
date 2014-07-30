CKEDITOR.editorConfig = function( config )
{
	// Customized for Hypercms
	config.uiColor = '#C8DCFF';
	
	// Remove plugin.
	config.removePlugins = 'codemirror';

	// allow all tags/content
	config.allowedContent = true;

	config.toolbar_Complete = 
		[
			{ name: 'document',    items : [ 'Source','-','Preview','Print','-','Templates' ] },
			{ name: 'clipboard',   items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
			{ name: 'editing',     items : [ 'Find','Replace','-','SelectAll','-','SpellChecker', 'Scayt' ] },
			{ name: 'tools',       items : [ 'Maximize', 'ShowBlocks' ] },
			'/',
			{ name: 'basicstyles', items : [ 'TextColor','BGColor','-','Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
			{ name: 'paragraph',   items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','CreateDiv','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','BidiLtr','BidiRtl' ] },
			'/',
			{ name: 'insert',      items : [ 'Image','Flash','hcms_video','Table','HorizontalRule','Smiley','SpecialChar','Iframe','PageBreak','-','Form','Checkbox','Radio','TextField','Textarea','Select','Button','ImageButton','HiddenField' ] },
			{ name: 'links',       items : [ 'Link','Unlink','Anchor' ] },
			'/',
			{ name: 'styles',      items : [ 'Styles','Format','Font','FontSize' ] }
		];

	config.toolbar_Default = 
		[
			{ name: 'document',    items : [ 'Source','-','Print','-','Templates' ] },
			{ name: 'clipboard',   items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
			{ name: 'editing',     items : [ 'Find','Replace','-','SelectAll' ] },
			{ name: 'tools',       items : [ 'ShowBlocks' ] },
			'/',
			{ name: 'basicstyles', items : [ 'TextColor','BGColor','-','Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
			{ name: 'paragraph',   items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','CreateDiv','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','BidiLtr','BidiRtl' ] },
			'/',
			{ name: 'insert',      items : [ 'Image','Flash','hcms_video','Table','HorizontalRule','SpecialChar','Iframe','PageBreak' ] },
			{ name: 'links',       items : [ 'Link','Unlink','Anchor' ] },
			'/',
			{ name: 'styles',      items : [ 'Styles','Format','Font','FontSize' ] }
		];
	// Wir brauchen das da tplengine.inc DefaultForm als toolbar wert enthält
	config.toolbar_DefaultForm = config.toolbar_Default;

	config.toolbar_DefaultExFormat = 
		[
			{ name: 'document',    items : [ 'Source','-','Print','-','Templates' ] },
			{ name: 'clipboard',   items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
			{ name: 'editing',     items : [ 'Find','Replace','-','SelectAll' ] },
			{ name: 'tools',       items : [ 'ShowBlocks' ] },
			'/',
			{ name: 'basicstyles', items : [ 'TextColor','BGColor','-','Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
			{ name: 'paragraph',   items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','CreateDiv','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','BidiLtr','BidiRtl' ] },
			'/',
			{ name: 'insert',      items : [ 'Image','Flash','hcms_video','Table','HorizontalRule','SpecialChar','Iframe','PageBreak' ] },
			{ name: 'links',       items : [ 'Link','Unlink','Anchor' ] },
		];
    
	config.toolbar_DAM = 
		[
			{ name: 'clipboard',   items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
			{ name: 'editing',     items : [ 'Find','Replace','-','SelectAll' ] },
			{ name: 'basicstyles', items : [ 'TextColor','BGColor','-','Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
			{ name: 'paragraph',   items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','BidiLtr','BidiRtl' ] },
			{ name: 'insert',      items : [ 'Table','HorizontalRule','SpecialChar' ] },
		];
	
	config.toolbar_PDF = 
		[
			{ name: 'clipboard',   items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
			{ name: 'editing',     items : [ 'Find','Replace','-','SelectAll' ] },
			{ name: 'basicstyles', items : [ 'TextColor','BGColor','-','Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
			{ name: 'paragraph',   items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','BidiLtr','BidiRtl' ] },
			{ name: 'insert',      items : [ 'Image','Table','HorizontalRule','SpecialChar' ] },
			{ name: 'styles',      items : [ 'Styles','Format','Font','FontSize' ] },
		];

	config.toolbar = 'Default';
	config.skin = 'hypercms';
	config.filebrowserWindowWidth = 500;
	
	// Here we load the hcms plugins for CKEDitor
	var customPath = CKEDITOR.basePath.split("/");
	// If we have one / at the end we get an empty entry at the end of the array, that needs to be removed too
	if(customPath[customPath.length-1] == "") {
		customPath.pop();
	}
	// Removing the last directory
	customPath.pop();
	var customPath = customPath.join("/");
	CKEDITOR.plugins.addExternal( 'hcms_linkbrowsebuttons', customPath + '/ckeditor_custom/hcms_linkbrowsebuttons/plugin.js' );
	CKEDITOR.plugins.addExternal( 'hcms_video', customPath + '/ckeditor_custom/hcms_video/plugin.js' );
	config.extraPlugins = "hcms_linkbrowsebuttons,hcms_video";
};

// We need this or else all our elements would've an automatic \n after each save
/*CKEDITOR.on('instanceReady', function( ev ) {
	var blockTags = ['div','h1','h2','h3','h4','h5','h6','p','pre','li','blockquote','ul','ol','table','thead','tbody','tfoot','td','th'];

	for (var i = 0; i < blockTags.length; i++) {
		ev.editor.dataProcessor.writer.setRules( '*', 
			{
				indent : true,
				breakBeforeOpen : false,
				breakAfterOpen : false,
				breakBeforeClose : false,
				breakAfterClose : false
			}
		);
	}
});*/
// We need this or our dialogs would all be fixed and on small windows you couldn't click on the 
// bottom Ok, Cancel buttons.
/*CKEDITOR.on( 'dialogDefinition', function( event ) {
    //svar dialogName = event.data.name;
    var dialogDefinition = event.data.definition;
    dialogDefinition.dialog.parts.dialog.setStyles(
	    {
	        position : 'absolute'
	    });
});*/