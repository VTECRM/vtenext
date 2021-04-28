/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@160733 crmv@173271

window.VTEPortal = window.VTEPortal || {};

VTEPortal.HelpDesk = VTEPortal.HelpDesk || {
	
	formSubmitting: false,
	
	initFormValidation: function() {
		
		if (jQuery('form[name=Save]').length == 1) {
			return this.initCreateValidation();
		}
	},
	
	initCreateValidation: function() {
		var me = this,
			form = jQuery('form[name=Save]'),
			btn = form.find('button[type=submit]');
			
		if (btn.length > 0) {
			// crmv@117223
			btn.click(function() {
				var title = jQuery('input[name=ticket_title]').val();
				var description = jQuery('textarea[name=description]').val(); // crmv@188439
				if (me.formSubmitting) return false;
				if (trim(title) == '') {
					alert(alert_arr.LBL_TITLE_EMPTY_HELPDESK); // crmv@188439
					return false;
				}
				// crmv@188439
				if (trim(description) == '') {
					alert(alert_arr.LBL_DESCRIPTION_EMPTY_HELPDESK);
					return false;
				}
				// crmv@188439e
				me.formSubmitting = true;
				return true;
			});
			// crmv@117223e
		}
	}
	
};

VTEPortal.HelpDesk.ConfidentialInfo = VTEPortal.HelpDesk.ConfidentialInfo || {

	askData: function(module, crmid, commentid, status, morecomment) {
		if (status != 1) {
			alert(alert_arr.LBL_CONFIDENTIAL_INFO_ALREADY_PROVIDED);
			return false;
		}

		jQuery('#confinfo_more').closest('div')[morecomment ? 'show' : 'hide']();
		jQuery('#confinfo_more').val(morecomment);
		jQuery('#confinfo_data').val('');
		jQuery('#confinfo_data_comment').val('');
		jQuery('#confinfo_commentid').val(commentid);
		jQuery('#provideConfInfo').modal({
			keyboard: false
		});
	},
	
	provideInfo: function(module, crmid, fieldname, fieldlabel) {
		var data = jQuery('#confinfo_data').val().trim();
		
		if (data === '') {
			alert(alert_arr.LBL_TYPE_CONFIDENTIAL_INFO);
			return false;
		}
		
		jQuery('#confinfo_form').submit();
	}
}

// launch init functions!
jQuery(document).ready(function() {
	VTEPortal.HelpDesk.initFormValidation();
});