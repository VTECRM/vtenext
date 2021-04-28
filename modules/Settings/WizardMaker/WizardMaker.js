/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@96233 */

var WizardMaker = {
	
	busy: false,
	
	showBusy: function() {
		var me = this;
		me.busy = true;
		jQuery('#wmaker_busy').show();
	},
	
	hideBusy: function() {
		var me = this;
		me.busy = false;
		jQuery('#wmaker_busy').hide();
	},
	
	createNew: function() {
		if (this.busy) return;
		this.showBusy();
		location.href = 'index.php?module=Settings&action=WizardMaker&parentTab=Settings&mode=create&wizard_maker_step=1';
	},
	
	editWizard: function(wizid) {
		if (this.busy) return;
		this.showBusy();
		location.href = 'index.php?module=Settings&action=WizardMaker&parentTab=Settings&mode=edit&wizard_maker_step=1&wizardid='+wizid;
	},
	
	deleteWizard: function(wizid) {
		var me = this;
		if (me.busy) return;
		if (confirm(alert_arr.SURE_TO_DELETE)) {
			me.ajaxCall('delete_wizard', {wizardid:wizid}, function(data) {
				me.gotoList();
			});
		}
	},
	
	disableWizard: function(wizid) {
		var me = this;
		if (me.busy) return;
		me.ajaxCall('disable_wizard', {wizardid:wizid}, function(data) {
			me.gotoList();
		});
	},
	
	enableWizard: function(wizid) {
		var me = this;
		if (me.busy) return;
		me.ajaxCall('enable_wizard', {wizardid:wizid}, function(data) {
			me.gotoList();
		});
	},
	
	ajaxCall: function(action, params, callback, options) {
		var me = this;
		
		// return if busy
		if (me.busy) return;
		
		options = options || {
			includeForm: false,
			jsonData: true,
			callbackOnError: false,
		};
		params = params || {};
		var url = "index.php?module=Settings&action=SettingsAjax&file=WizardMaker&ajax=1&subaction="+action;
		
		if (options.includeForm) {
			var form = jQuery('#wizard_maker_form').serialize();
			params = jQuery.param(params) + '&' + form;
		}
		
		me.showBusy();
		jQuery.ajax({
			url: url,
			type: 'POST',
			async: true,
			data: params,
			success: function(data) {
				me.hideBusy();
				if (options.jsonData) {
					// data should be json with a success property
					try {
						data = JSON.parse(data);
					} catch (e) {
						data = null;
					}
					if (data && data.success) {
						if (typeof callback == 'function') callback(data);
					} else if (data && data.error) {
						alert(data.error);
					} else {
						console.log('Unknown error');
						console.log(data);
					}
				} else {
					if (typeof callback == 'function') callback(data);
				}
			},
			error: function() {
				me.hideBusy();
				if (options.callbackOnError) {
					if (typeof callback == 'function') callback();
				}
			}
		});
		
	},
	
	gotoList: function() {
		var me = this;
		if (me.busy) return;
		me.showBusy();
		location.href = 'index.php?module=Settings&action=WizardMaker&parentTab=Settings';
	},
	
	gotoStep: function(step) {
		// set the inputs
		jQuery('#wizard_maker_step').val(step);
		
		// get the form and submit
		var form = document.getElementById('wizard_maker_form');
		if (form) form.submit();
	},
	
	getCurrentStep: function() {
		var step = parseInt(jQuery('#wizard_maker_prev_step').val());
		return step;
	},
	
	gotoNextStep: function() {
		var me = this,
			step = me.getCurrentStep(),
			nstep = step+1;
			
		if (!me.validateStep(step)) return false;
		
		me.hideError();
		me.gotoStep(nstep);
	},
	
	gotoPrevStep: function() {
		var me = this,
			step = me.getCurrentStep(),
			pstep = step-1;
		
		if (!me.validateStep(step)) return false;
		
		me.hideError();
		me.gotoStep(pstep);
	},
	
	hideNavigationButtons: function() {
		jQuery('#wmaker_div_navigation').hide();
	},
	
	showNavigationButtons: function() {
		jQuery('#wmaker_div_navigation').show();
	},
	
	validateStep: function(step) {
		var me = this,
			fname = 'validateStep'+step;
		
		if (typeof me[fname] == 'function') {
			return me[fname]();
		}
		return true;
	},
	
	saveWizard: function() {
		var me = this;
		
		jQuery('#wizard_maker_savedata').val('1');
		me.gotoNextStep();
	},
	
	displayError: function(text) {
		var me = this;
		
		var div = jQuery('#wmaker_error_box');
		var oldbg = div.css('background-color') || '#ffffff';
		
		// set the text and show it
		div.text(text).show();
		
		// don't animate if already doing it
		if (me.displayErrorAnimation) return false;
		
		// animate the background
		me.displayErrorAnimation = true;
		div.css({
			'background-color': '#ff7070',
		});
		jQuery('#wmaker_error_box').animate({
			'background-color': oldbg,
		}, {
			duration: 600,
			complete: function() {
				me.displayErrorAnimation = false;
			}
		});
		return false;
	},
	
	hideError: function() {
		jQuery('#wmaker_error_box').hide().text('');
	},
	
	step1_getFieldLabel: function(fname) {
		var obj = jQuery('#'+fname).closest('tr').children('td').first().find('span').text() || fname;
		return obj
	},
	
	validateStep1: function() {
		var me = this;
		
		var wlabel = jQuery('#wmaker_name').val();
		
		if (!wlabel) return me.displayError(alert_arr.CANNOT_BE_EMPTY.replace('%s', me.step1_getFieldLabel('wmaker_name')));
		
		return true;
	},
	
	step3_changeMand: function(self) {
		var me = this,
			name = self.name
			fieldid = name.replace('field_mand_', '');
		
		if (fieldid && self.checked) {
			jQuery('#field_'+fieldid).prop('checked', true);
		}
		
	},
	
	step3_changeVisible: function(self) {
		var me = this,
			name = self.name
			fieldid = name.replace('field_', '');
		
		if (fieldid && !self.checked) {
			jQuery('#field_mand_'+fieldid).prop('checked', false);
		}
		
	}

};