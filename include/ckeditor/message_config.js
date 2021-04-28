/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@2963m */
CKEDITOR.editorConfig = function( config )
{
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
	
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
	//crmv@31210
	config.toolbar_Basic =
	[
	    ['Font','FontSize','Bold','Italic','Underline','Strike','TextColor','BGColor','Link','Unlink','NumberedList','BulletedList','Outdent','Indent','Blockquote','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	    ['Image','Table']
	];
	//crmv@31210e
	config.extraPlugins = 'htmlreader,uploadimage,uploadwidget,filetools,widget,notificationaggregator,lineutils,notification';	// crmv@24011 crmv@81704
	
	config.removePlugins = 'elementspath';
	
	config.entities_processNumerical = true; //crmv@97184
	config.toolbarCanCollapse = false;
	config.sharedSpaces =
	{
	    bottom : 'hideBottom'
	};
	config.height = jQuery(document).height()							//document height 
					- jQuery('#emailHeader').outerHeight()				//emailHeader height
					- 4													//tr
					- 32												//subject height
					- 8													//td mailSubHeader padding
					- 10												//cke_description padding
					- 10												//cke_wrapper cke_ltr padding
					- 31												//ckeditor buttons
					- jQuery('#DETAILVIEWWIDGETBLOCK').outerHeight()	//ModComments height
					- 10												//...
					;
	if ('WebkitAppearance' in document.documentElement.style) { // crmv@82419
		config.height = config.height-2;
	}
	
	// crmv@56461
	config.skin = 'moonocolor';
	config.uiColor = '#ffffff';
	// crmv@56461e
	
	// enable all html code, due to dynamic change of signature
	config.allowedContent = true; // crmv@81852
};