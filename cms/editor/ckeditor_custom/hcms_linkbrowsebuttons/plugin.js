/*
 * Plugin to browse the Components and Sites of a HCMS driven site
 * coded by Denis Neshyba
 */
CKEDITOR.plugins.add('hcms_linkbrowsebuttons',
	{
		requires :	[ 'dialog','filebrowser', 'link' ],
		lang:		[ 'en', 'de'],
		init: function( editor ) {
						
			CKEDITOR.on( 'dialogDefinition', function( event ) {
				var dialogName = event.data.name;
				var dialogDefinition = event.data.definition;
				
				if(dialogName == 'link') {
					
					var vbox = dialogDefinition.getContents( 'info' ).get("urlOptions");
					var add = true;
					var add2 = true;
					
					for( var i in vbox.children) {
						if(vbox.children[i].id == 'hcms_browse_page') {
							add = false;
						}
						if(vbox.children[i].id == 'hcms_browse_component') {
							add2 = false;
						}
					}
					
					if(add == true) {
						// Pages button
						vbox.children.push( 
							{
								type:	'button',
								id:		'hcms_browse_page',
								label:	editor.lang.hcms_linkbrowsebuttons.label.Page,
								filebrowser :
								{
									action : 'Browse',
									target: 'info:url',
									url: editor.config.filebrowserLinkBrowsePageUrl
								},
								hidden: false
							} 
						);
					}
					if(add2 == true) {
						// Component button
						vbox.children.push( 
							{
								type:	'button',
								id:		'hcms_browse_component',
								label:	editor.lang.hcms_linkbrowsebuttons.label.Component,
								filebrowser :
								{
									action : 'Browse',
									target: 'info:url',
									url: editor.config.filebrowserLinkBrowseComponentUrl
								},
								hidden: false
							} 
						);
					}
					// We need to refire the event, so the filebrowser does his magic!
					if(add2 == true || add == true) {
						CKEDITOR.fire('dialogDefinition', event.data, editor);
					}
				}
			});
		}
	}
);

CKEDITOR.plugins.setLang( 'hcms_linkbrowsebuttons', 'de',
{
	
	label: {
		Page : 'Seiten durchsuchen',
		Component: 'Assets durchsuchen'
		
	}
});

CKEDITOR.plugins.setLang( 'hcms_linkbrowsebuttons', 'en',
{
	label: {
		Page : 'Browse Pages',
		Component: 'Browse Assets'
		
	}
});