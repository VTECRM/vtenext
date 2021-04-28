/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@161554 crmv@163697

var GDPRConfig = GDPRConfig || {
	
	busy: false,
		
	showBusy: function() {
		var me = this;
		me.busy = true;
		jQuery('#gdpr_busy').show();
	},
	
	hideBusy: function() {
		var me = this;
		me.busy = false;
		jQuery('#gdpr_busy').hide();
	},
	
	editGDPR: function(id) {
		if (this.busy) return;
		this.showBusy();
		location.href = 'index.php?module=Settings&action=GDPRConfig&parentTab=Settings&mode=edit&business_id='+id;
	},
	
	loadBusiness: function(elem) {
		if (this.busy) return;
		this.showBusy();
		var business = jQuery(elem).val();
		location.href = 'index.php?module=Settings&action=GDPRConfig&parentTab=Settings&business_id='+business;
	},
	
	initEditView: function() {
		var me = this;
		
		me.showBusy();
		
		CKEDITOR.replace('privacy_policy', {
			on: {
				instanceReady: function(evt) {
					me.hideBusy();
				}
			},
			height: '450px',
		});
	},
	
	saveGeneralSettings: function() {
		var me = this;
		
		if (me.busy) return false;
		me.showBusy();
		
		var form = {};
		jQuery.each(jQuery('#general-settings').serializeArray(), function() {
			form[this.name] = this.value;
		});
		
		var action = 'save_general_settings';
		
		jQuery.ajax({
			url: 'index.php?module=Settings&action=SettingsAjax&file=GDPRConfig&ajax=1&subaction='+action,
			method: 'GET',
			data: form,
			success: function(res) {
				me.hideBusy();
				if (res && res.error) {
					vtalert(res.error);
				}
				window.location.reload();
			},
			error: function() {
				me.hideBusy();
				console.log('Ajax error');
			}
		});
	},
	
}