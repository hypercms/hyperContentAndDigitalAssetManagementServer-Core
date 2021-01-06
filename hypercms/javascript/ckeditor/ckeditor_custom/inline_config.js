CKEDITOR.editorConfig = function( config )
{
  // Customized for Hypercms
  config.uiColor = '#F7F7F7';
 
  // Remove plugin
  config.removePlugins = 'codemirror';

  // Allow all tags/content
  config.allowedContent = true;

  config.toolbar_Complete = 
    [
      { name: 'document',    items : [ 'Source','-','Preview','Print','-','Templates' ] },
      { name: 'clipboard',   items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
      { name: 'editing',     items : [ 'Find','Replace','-','SelectAll','-','SpellChecker','Scayt' ] },
      { name: 'tools',       items : [ 'Maximize', 'ShowBlocks' ] },
      '/',
      { name: 'basicstyles', items : [ 'TextColor','BGColor','-','Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
      { name: 'paragraph',   items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','CreateDiv','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','BidiLtr','BidiRtl' ] },
      '/',
      { name: 'insert',      items : [ 'Image','Flash','hcms_video','Youtube','Table','HorizontalRule','Smiley','SpecialChar','Iframe','PageBreak','-','Form','Checkbox','Radio','TextField','Textarea','Select','Button','ImageButton','HiddenField' ] },
      { name: 'links',       items : [ 'Link','Unlink','Anchor' ] },
      '/',
      { name: 'styles',      items : [ 'Styles','Format','Font','FontSize' ] }
    ];

  config.toolbar_Default = 
    [
      { name: 'document',    items : [ 'Source','-','Print','-','Templates' ] },
      { name: 'clipboard',   items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
      { name: 'editing',     items : [ 'Find','Replace','-','SelectAll','-','Scayt' ] },
      { name: 'tools',       items : [ 'ShowBlocks' ] },
      '/',
      { name: 'basicstyles', items : [ 'TextColor','BGColor','-','Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
      { name: 'paragraph',   items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','CreateDiv','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','BidiLtr','BidiRtl' ] },
      '/',
      { name: 'insert',      items : [ 'Image','Flash','hcms_video','Youtube','Table','HorizontalRule','SpecialChar','Iframe','PageBreak' ] },
      { name: 'links',       items : [ 'Link','Unlink','Anchor' ] },
      '/',
      { name: 'styles',      items : [ 'Styles','Format','Font','FontSize' ] }
    ];
    
  // tplengine.inc.php uses DefaultForm as default toolbar
  config.toolbar_DefaultForm = config.toolbar_Default;

  config.toolbar_DefaultExFormat = 
    [
      { name: 'document',    items : [ 'Source','-','Print','-','Templates' ] },
      { name: 'clipboard',   items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
      { name: 'editing',     items : [ 'Find','Replace','-','SelectAll','-','Scayt' ] },
      { name: 'tools',       items : [ 'ShowBlocks' ] },
      '/',
      { name: 'basicstyles', items : [ 'TextColor','BGColor','-','Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
      { name: 'paragraph',   items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','CreateDiv','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','BidiLtr','BidiRtl' ] },
      '/',
      { name: 'insert',      items : [ 'Image','Flash','hcms_video','Youtube','Table','HorizontalRule','SpecialChar','Iframe','PageBreak' ] },
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
      { name: 'editing',     items : [ 'Find','Replace','-','SelectAll','-','Scayt' ] },
      { name: 'basicstyles', items : [ 'TextColor','BGColor','-','Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
      { name: 'paragraph',   items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','BidiLtr','BidiRtl' ] },
      { name: 'insert',      items : [ 'Image','Table','HorizontalRule','SpecialChar' ] },
      { name: 'styles',      items : [ 'Styles','Format','Font','FontSize' ] },
    ];

  config.toolbar = 'Default';
  config.skin = 'moono-lisa';
  config.filebrowserWindowWidth = 600;
  config.filebrowserWindowHeight = 600;
  
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
  
  // Enable plugin
  config.extraPlugins = "hcms_linkbrowsebuttons,hcms_video,youtube";
};