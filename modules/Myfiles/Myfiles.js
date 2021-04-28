/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
jQuery('document').ready(function(){
	if (typeof gVTModule != 'undefined'){
		if (gVTModule == 'Myfiles'){
			if (jQuery('[name=record]').val() == '' && jQuery('[name="filelocationtype"]').val() != 'I'){
				jQuery('[name="filelocationtype"]').val('I').click();
				jQuery('[name="filestatus"]').prop('checked',true);
			}
		}
	}
});