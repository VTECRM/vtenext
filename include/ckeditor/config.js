/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
	//crmv@104558
	/*
	config.toolbar =
	[
	    ['Source','-','HtmlReader','-','NewPage','Preview','-','Templates'],	//crmv@24011
	    ['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print', 'SpellChecker', 'Scayt'],
	    ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
	    ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'],
	    '/',
	    ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
	    ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote','CreateDiv'],
	    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	    ['Link','Unlink','Anchor'],
	    ['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak'],
	    '/',
	    ['Styles','Format','Font','FontSize'],
	    ['TextColor','BGColor'],
	    ['Maximize', 'ShowBlocks','-','About']
	    
	];
	*/
	config.toolbar =
		[
		 	['Source','-','HtmlReader'],
		 	['Styles','Format','Font','FontSize'],
		 	['TextColor','BGColor'],
		 	['Undo','Redo','Find','Replace','SelectAll','RemoveFormat'],
		 	['Maximize', 'ShowBlocks'],
		 	 '/',
		 	['Cut','Copy','Paste'],
		 	['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
		 	['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
		 	['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
		 	['Link','Unlink','Anchor'],
		 	['Image','Table','HorizontalRule','SpecialChar','PageBreak'],
		];
	//crmv@104558e
	//crmv@31210
	config.toolbar_Basic =
	[
	    ['Font','FontSize','Bold','Italic','Underline','Strike','TextColor','BGColor','Link','Unlink','NumberedList','BulletedList','Outdent','Indent','Blockquote','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	    ['Image','Table']
	];
	//crmv@31210e
	config.extraPlugins = 'htmlreader';	//crmv@24011
	config.removePlugins = 'elementspath';
	
	config.entities_processNumerical = true; //crmv@97184
	config.startupFocus = true;
	config.toolbarCanCollapse = false;
	config.sharedSpaces =
	{
	    bottom : 'hideBottom'
	};
	config.enterMode = CKEDITOR.ENTER_BR; //crmv@54228
	
	// crmv@56461
	config.skin = 'minimalist'; //crmv@104558
	config.uiColor = '#ffffff';
	// crmv@56461e
	
	config.startupFocus = false;	//crmv@56883
	
	// enable all html code, due to dynamic change of signature
	config.allowedContent = true; // crmv@81852
};
