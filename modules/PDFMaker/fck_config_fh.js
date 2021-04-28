/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
CKEDITOR.editorConfig = function( config )
{
    config.toolbar = 'HeaderToolbar';

    config.toolbar_HeaderToolbar =
    [
        ['Source','-','NewPage','Preview','-','Templates'],
        ['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print', 'SpellChecker', 'Scayt'],
        ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
        ['BidiLtr', 'BidiRtl'],
        '/',
        ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
        ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote','CreateDiv'],
        ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
        ['Image','Table','HorizontalRule','Smiley','SpecialChar'],
        '/',
        ['Styles','Format','Font','FontSize'],
        ['TextColor','BGColor'],
        ['Maximize', 'ShowBlocks','-','About']
    ];
    config.height = '200';
    //config.width = '1100';
    
    // crmv@56461
	config.skin = 'moonocolor';
	config.uiColor = '#ffffff';
	// crmv@56461e
	
	// enable all html code, due to dynamic change of signature
	config.allowedContent = true; // crmv@81852
};