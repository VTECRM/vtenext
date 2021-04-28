/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
CKEDITOR.editorConfig = function( config )
{
    config.toolbar = 'BodyToolbar';
    
    config.toolbar_BodyToolbar =
    [
        ['Source','-','NewPage','Preview','-','Templates'],
        ['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print', 'SpellChecker', 'Scayt'],
        ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
        ['BidiLtr', 'BidiRtl'],
        '/',
        ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
        ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote','CreateDiv'],
        ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
        ['Image','Table','HorizontalRule','Smiley','SpecialChar','PageBreak'],
        '/',
        ['Styles','Format','Font','FontSize'],
        ['TextColor','BGColor'],
        ['Maximize', 'ShowBlocks','-','About']
    ];
    config.height = '510';
    //config.width = '860';	//crmv
	/* crmv@63433
    config.font_names =
      	'Arial/Arial, Helvetica, sans-serif;' +
       	'Comic Sans MS;' +
         //'Comic Sans MS/Comic Sans MS, cursive;' +
       	'Courier New/Courier New, Courier, monospace;' +
       	'DejaVu Sans;' +
       	'DejaVu Sans Condensed;' +
       	'DejaVu Sans Mono;' +
       	'DejaVu Serif;' +
       	'DejaVu Serif Condensed;' +
       	'Georgia;' +
       	'Lucida Sans Unicode;' +
       	//'Lucida Sans Unicode/Lucida Sans Unicode, Lucida Grande, sans-serif;' +
       	'Tahoma;' +
       	//'Tahoma/Tahoma, Geneva, sans-serif;' +
       	 'Times New Roman/Times New Roman, Times, serif;' +
         'Trebuchet MS;' +
       	// 'Trebuchet MS/Trebuchet MS, Helvetica, sans-serif;' +
       	'Verdana';
       	//'Verdana/Verdana, Geneva, sans-serif';
	*/
	config.filebrowserBrowseUrl = 'include/ckeditor/filemanager/index.html'; //crmv@35765
	
	// crmv@56461
	config.skin = 'moonocolor';
	config.uiColor = '#ffffff';
	// crmv@56461e
	
	// enable all html code, due to dynamic change of signature
	config.allowedContent = true; // crmv@81852
	config.fillEmptyBlocks = false; // crmv@180720
};