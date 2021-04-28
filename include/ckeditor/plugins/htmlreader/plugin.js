//crmv@24011
CKEDITOR.plugins.add( 'htmlreader',
{
	init: function( editor )
	{
		editor.addCommand( 'selectHtmlFile',
			{
				exec : function( editor )
				{    
					openPopup('include/ckeditor/filemanager/index.html?langCode=it&HtmlReader=true','','','','','','','nospinner');
				}
			});
		editor.ui.addButton( 'HtmlReader',
		{
			label: 'Seleziona file html',
			command: 'selectHtmlFile',
			icon: this.path + 'images/htmlreader.png'
		} );
	}
} );
//crmv@24011e