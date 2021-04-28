/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@161554

jQuery('#support-request').click(function() {
   jQuery('#support-request-modal').modal('show'); 
});

var VTGDPR = VTGDPR || {
    
    showBusy: function() {
        var me = this;
        jQuery('#loader').show();
        me.busy = true;
    },
    
    hideBusy: function() {
        var me = this;
        jQuery('#loader').hide();
        me.busy = false;
    },
    
    process: function(action) {
    	var me = this;
    	
    	if (action && action.length < 1) return;
    	
    	var actionFn = action + 'Step';
    	var validateFn = 'validateStep' + (action.charAt(0).toUpperCase() + action.slice(1));
    	
		if (typeof me[actionFn] === 'function') {
			if (typeof me[validateFn] === 'function') {
				if (!me[validateFn].call(me)) return false;
			}
			return me[actionFn].call(me);
		} else {
			console.error('Invalid action name');
		}
    },
    
    verifyStep: function() {
    	var me = this;
		
		var params = {
			cid: jQuery('input[name="cid"]').val(),
			authtoken: jQuery('input[name="authtoken"]').val(),
		};
		
		me.doRequest('SendVerify', params, null, function(data) {
			if (data.success) {
				me.redirectTo('verify-sent', {
					cid: params.cid,
				});
			}
		});
    },
    
    checkPrivacyPolicy: function(self) {
		var me = this,
			checked = jQuery(self).is(':checked');
		
		if (checked) {
			jQuery('#save-settings-button').removeClass('d-none');
			jQuery('#cancel-settings-button').removeClass('d-none');
		} else {
			jQuery('#save-settings-button').addClass('d-none');
			jQuery('#cancel-settings-button').addClass('d-none');
		}
	},
	
	saveSettingsStep: function() {
		var me = this,
			form = jQuery('#settings-form');
		
		var params = {};
		
		jQuery.each(form.serializeArray(), function() {
			params[this.name] = this.value;
		});
		
		form.find('input[type="checkbox"]').each(function() {
			params[this.name] = jQuery(this).prop('checked');
	    });
		
		me.doRequest('SaveSettings', params, null, function(data) {
			if (data.success) {
				me.redirectTo('confirm-sent', {
					'accesstoken': params.accesstoken,
				});
			}
		});
	},
	
	cancelSettingsStep: function() {
		window.location.reload();
	},
    
    checkMainContact: function(self) {
		var me = this;
		
		if (jQuery(self).is('tr')) {
			self = jQuery(self).find('input[type="radio"]').get(0);
			jQuery(self).prop('checked', true);
		}
		
		var checked = jQuery(self).is(':checked');
		
		if (checked) {
			// deselect other radios
			jQuery('#merge-contacts input[type="radio"]').each(function(idx, el) {
				if (el !== self) {
					jQuery(el).prop('checked', false);
					jQuery(el).closest('.merge-row').removeClass('table-success');
				}
			});
			
			var contact = jQuery(self).data('contactid');
			me.mainContact = contact;
			
			jQuery(self).closest('.merge-row').addClass('table-success');
			jQuery('#merge-button').removeClass('d-none');
		} else {
			me.mainContact = null;
			jQuery('#merge-button').addClass('d-none');
		}
	},
	
	mergeContactStep: function() {
		var me = this,
			form = jQuery('#merge-form');
		
		var params = {};
		
		jQuery.each(form.serializeArray(), function() {
			params[this.name] = this.value;
		});
		
		params['maincontact'] = me.mainContact;
		
		var otherids = [];
		jQuery('#merge-contacts input[type="radio"]').each(function(idx, el) {
			var contactid = jQuery(el).data('contactid');
			if (contactid !== me.mainContact) {
				otherids.push(contactid);
			}
		});
		
		params['otherids'] = otherids;
		
		me.doRequest('MergeContact', params, null, function(data) {
			if (data.success) {
				me.redirectTo('confirm-sent', {
					'accesstoken': params.accesstoken,
				});
			}
		});
	},
	
	validateStepMergeContact: function() {
		var me = this;

		if (!me.mainContact) {
			alert(LANG['please_select_contact']);
			return false;
		}
		
		return true;
	},
	
	deleteContactStep: function() {
		var me = this,
			form = jQuery('#delete-form');
		
		var params = {};
		
		jQuery.each(form.serializeArray(), function() {
			params[this.name] = this.value;
		});
		
		me.doRequest('DeleteContact', params, null, function(data) {
			if (data.success) {
				me.redirectTo('confirm-sent', {
					'accesstoken': params.accesstoken,
				});
			}
		});
	},
	
	validateStepDeleteContact: function() {
		var me = this;
		
		if (confirm(LANG['are_you_sure_delete_contact'])) {
			return true;
		}
		
		return false;
	},
	
	supportRequestStep: function() {
		var me = this,
			form = jQuery('#support-request-form');
		
		var params = {};
		
		jQuery.each(form.serializeArray(), function() {
			params[this.name] = this.value;
		});
		
		me.doRequest('SendSupportRequest', params, null, function(data) {
			if (data.success) {
				alert(LANG['request_sent']);
				jQuery('#support-request-subject').val('');
				jQuery('#support-request-description').val('');
				jQuery('#support-request-modal').modal('hide'); 
			}
		});
	},
	
	validateStepSupportRequest: function() {
		var me = this;

		var subject = jQuery('#support-request-subject').val() || '';
		var description = jQuery('#support-request-description').val() || '';
		
		if (subject.length < 1) {
			alert(LANG['please_insert_title']);
			return false;
		}
		
		if (description.length < 1) {
			alert(LANG['please_insert_description']);
			return false;
		}
		
		return true;
	},
	
	sendPrivacyPolicyStep: function() {
		var me = this,
			form = jQuery('#privacy-policy-form');
		
		var params = {};
		
		jQuery.each(form.serializeArray(), function() {
			params[this.name] = this.value;
		});
		
		me.doRequest('SendPrivacyPolicy', params, null, function(data) {
			if (data.success) {
				alert(LANG['email_sent']);
			}
		});
	},
	
	loadDetailBlock: function() {
		var me = this,
			form = jQuery('#detailview-form');
		
		var params = {};
		
		jQuery.each(form.serializeArray(), function() {
			params[this.name] = this.value;
		});
		
		me.doRequest('LoadDetailBlock', params, { raw: true }, function(data) {
			if (data.success) {
				form.html(data.html);
				me.populateContactData();
			}
		});
	},
	
	loadEditBlock: function() {
		var me = this,
			form = jQuery('#editview-form');
		
		var params = {};
		
		jQuery.each(form.serializeArray(), function() {
			params[this.name] = this.value;
		});
		
		me.doRequest('LoadEditBlock', params, { raw: true }, function(data) {
			if (data.success) {
				form.html(data.html);
				me.populateContactData();
			}
		});
	},
	
	populateContactData: function() {
		var me = this,
			contactData = window.contactData;
		
		if (!contactData) return;
		
		jQuery.each(contactData, function(field, value) {
			jQuery('#'+field).val(value);
		});
	},
	
	editContactStep: function() {
		var me = this,
			form = jQuery('#editview-form');
		
		var params = {};
		
		jQuery.each(form.serializeArray(), function() {
			params[this.name] = this.value;
		});
		
		me.doRequest('EditContact', params, null, function(data) {
			if (data.success) {
				me.redirectTo('confirm-sent', {
					'accesstoken': params.accesstoken,
				});
			}
		});
	},
	
	redirectTo: function(action, params) {
    	var me = this;
    	
    	if (action && action.length < 1) return;
    	
    	var urlParams = jQuery.param(jQuery.extend({
    		action: action,
    	}, params || {}));
    	
    	window.location.href = 'index.php?' + urlParams;
    },
    
    doRequest: function(action, params, options, callback) {
        var me = this;
        
        if (me.busy) return;

        options = jQuery.extend({}, {
            rawData: false,
        }, options || {});

        me.showBusy();
        
        jQuery.ajax({
            url: 'action.php?ajax_action='+action,
            method: 'POST',
            data: params,
            success: function(result) {
                me.hideBusy();
                if (result) {
                    if (options.rawData) {
                        if (typeof callback == 'function') callback(result);
                        return;
                    }
                    try {
						var parsed = JSON.parse(result);
					} catch (e) {
						var parsed = {success: false, error: 'Invalid response'};
					}
					
					if (parsed && parsed.success) {
						if (typeof callback === 'function') callback(parsed);
					} else if (parsed && parsed.error) {
						if (parsed.error === 'SESSION_EXPIRED') {
							window.location.reload();
						} else {
							alert(parsed.error);
						}
					} else {
						alert('Request failed');
					}
                } else {
                    console.log('Invalid data returned from server: ' + result);
                }
            },
            error: function() {
                me.hideBusy();
                console.log('Ajax error');
            },
        });
    },
    
};