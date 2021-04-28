/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@181161 crmv@182073 */

window.VTE = window.VTE || {};

VTE.Update = VTE.Update|| {
	
	showPopup: function() {
		var rpw = jQuery('#rightPanel').width();
		var margin = rpw ? rpw + 20 : 0;
		var width = 450 + margin;
		
		Snackbar.show({ 
			text: jQuery('#updatePopupContent').html(),
			width: width+'px', 
			duration: 0,
			pos: 'bottom-right',
			customClass: 'systemUpdateDialog',
			showAction: false,
		});
		
		// adjust margin to take into account the right bar (if present)
		if (margin > 0) {
			jQuery('.systemUpdateDialog').css('margin-right', margin+'px');
		}
	},
	
	showQuickPopup(text, callback) {
		var rpw = jQuery('#rightPanel').width();
		var margin = rpw ? rpw + 20 : 0;
		var width = 400 + margin;
		
		if (window.Snackbar) {
		
			Snackbar.show({
				text: text,
				width: width+'px', 
				duration: 2000,
				pos: 'bottom-right',
				showAction: false,
				onClose: function() {
					if (typeof callback == 'function') callback();
				}
			});
			
			if (margin > 0) {
				jQuery('div.snackbar-container').css('margin-right', margin+'px');
			}
			
		} else {
			// fallback on simple message
			vtealert(text, callback);
		}
	},
	
	hidePopup: function() {
		if (window.Snackbar) Snackbar.close();
	},
	
	ajaxCall: function(action, params, options, callback) {
		var me = this;
		
		jQuery('#status').show();
		jQuery.ajax({
			url: 'index.php?module=Update&action=UpdateAjax&subaction='+action,
			type: 'POST',
			data: params,
			success: function(data) {
				jQuery('#status').hide();
				
				try {
					var parsed = JSON.parse(data);
					if (!parsed.success) {
						vtealert('Error: '+parsed.error);
						return;
					}
				} catch (e) {
					vtealert('Malformed server response');
					return;
				}
				
				if (typeof callback === 'function') callback(parsed);
			}
		});
	},
	
	setPopupShown: function() {
		var me = this;
		me.ajaxCall('popup_seen', null, null);
	},
	
	// crmv@199352
	forceCheck: function() {
		var me = this;
		me.ajaxCall('force_check', null, null, function(data) {
			vtealert(data.message);
		});
	},
	// crmv@199352e
	
	scheduleUpdate: function() {
		location.href = 'index.php?module=Update&action=ScheduleUpdate&parenttab=Settings';
	},
	
	remindUpdate: function(when, redirect) {
		var me = this;
		me.ajaxCall('remind_update', {when: when}, null, function() {
			me.hidePopup();
			me.showQuickPopup(alert_arr.update_postponed, function() {
				if (redirect) location.href= "index.php";
			});
		});
	},
	
	ignoreUpdate: function(redirect) {
		var me = this;
		me.ajaxCall('ignore_update', null, null, function() {
			me.hidePopup();
			me.showQuickPopup(alert_arr.update_ignored, function() {
				if (redirect) location.href= "index.php";
			});
		});
	},
	
	// crmv@183486
	cancelUpdate: function(redirect) {
		var me = this;
		me.ajaxCall('cancel_update', null, null, function() {
			me.hidePopup();
			me.showQuickPopup(alert_arr.update_canceled, function() {
				if (redirect) location.href= "index.php";
			});
		});
	},
	// crmv@183486e
	
	onchangeAlertUsers: function(self) {
		var checked = jQuery(self).is(':checked');
		
		if (checked) {
			jQuery('#message_box').show();
			jQuery('#users_box').show();
		} else {
			jQuery('#message_box').hide();
			jQuery('#users_box').hide();
		}
	},
	
	changeMemberType: function() {
		var me = this,
			type = jQuery('#shareMemberType').val(),
			opts = "";
			
		jQuery('#availmembers').html('');
		
		if (type == 'groups' && window.reportGroups) {
			jQuery.each(reportGroups, function(groupid, grp) {
				opts += '<option value="'+grp.value+'">'+grp.label+'</option>';
			});
		} else if (type == 'users' && window.reportUsers) {
			jQuery.each(reportUsers, function(userid, usr) {
				opts += '<option value="'+usr.value+'">'+usr.label+'</option>';
			});
		}
		
		if (opts) {
			jQuery('#availmembers').html(opts);
		}
	},
	
	// crmv@183486
	addMembers: function() {
		var me = this,
			src = jQuery('#availmembers'),
			target = jQuery('#sharedmembers');
		
		var wantsAll = src.find('option[value="users::all"]:selected').length > 0,
			hasAll = me.isMemberChosen('users::all');
			
		if (hasAll) return; // no sense in adding other things
		
		// remove existing non-all values
		if (wantsAll) {
			target.find('option').each(function() {
				if (this.value != 'users::all') {
					jQuery(this).remove();
				}
			});
		}
		
		src.find('option:selected').each(function() {
			// add the field
			if ((!wantsAll || this.value == 'users::all') && !me.isMemberChosen(this.value)) {
				jQuery(this).prop('selected', false);
				jQuery(this).clone().appendTo(target);
			}
		});
		me.alignMemberField();
	},
	// crmv@183486e
	
	isMemberChosen: function(value) {
		var me = this,
			exists = false,
			target = jQuery('#sharedmembers');
		
		target.find('option').each(function() {
			if (value == this.value) {
				exists = true;
				return false;
			}
		});
		
		return exists;
	},
	
	removeMembers: function() {
		var me = this,
			target = jQuery('#sharedmembers');
			
		target.find('option:selected').remove();
		me.alignMemberField();
	},
	
	alignMemberField: function() {
		var me = this,
			list = [],
			target = jQuery('#sharedmembers');
			
		target.find('option').each(function(idx, el) {
			list.push(el.value);
		});
		
		jQuery('#schedule_users').val(JSON.stringify(list));
	},
	
	populateUsers: function(list) {
		var users = {},
			groups = {},
			opts = '';
			
		// create object by key
		jQuery.each(reportUsers, function(userid, usr) {
			users[usr.value] = usr.label;
		});
		jQuery.each(reportGroups, function(groupid, grp) {
			groups[grp.value] = grp.label;
		});
		
		for (var i=0; i<list.length; ++i) {
			var us = list[i];
			
			if (us.substr(0,5) === 'users') {
				opts += '<option value="'+us+'">'+users[us]+'</option>';
			} else if (us.substr(0,6) === 'groups') {
				opts += '<option value="'+us+'">'+groups[us]+'</option>';
			}
		}
		
		jQuery('#sharedmembers').html(opts);
	},
	
	onScheduleUpdate: function() {
		var me = this;
		
		// validate
		var dateLabel = jQuery('label[for=jscal_field_schedule_date]').text().trim();
		if (!patternValidate('jscal_field_schedule_date', dateLabel, 'date'))  return false;
		
		var hourLabel = jQuery('label[for=schedule_hour]').text().trim();
		var hour = jQuery('#jscal_field_schedule_hour').val();
		if (!hour.match(/^[0-2][0-9]:[0-5][0-9]$/)) {
			vtealert(alert_arr.ENTER_VALID + hourLabel, function() {
				jQuery('#jscal_field_schedule_hour').focus();
			});
			return false;
		}
		
		var doalert = jQuery('#schedule_alert').is(':checked');
		if (doalert) {
			// check if I have users and a message
			var users = jQuery('#sharedmembers option').length;
			if (users == 0) {
				vtealert(alert_arr.LBL_YOU_MUST_SELECT_USERS);
				return false;
			}
			
			var msg = jQuery('#schedule_message').val().trim();
			
			if (msg == '') {
				vtealert(alert_arr.LBL_YOU_MUST_TYPE_A_MESSAGE);
				return false;
			}
		}
		
		return true;
	},
	
	// crmv@182073
	onChangeDiffAlert: function(self) {
		var checked = jQuery(self).is(':checked');
		if (checked) {
			jQuery('#allfields').show();
			jQuery('#submitBtn').prop('disabled', false).css({opacity:1});
		} else {
			jQuery('#allfields').hide();
			jQuery('#submitBtn').prop('disabled', true).css({opacity:0.5});
		}
	},
	
	viewDiffFiles: function() {
		var me = this;
		
		me.ajaxCall('show_diff', null, null, function(result) {
			jQuery('#diff_content').text(result.data);
			showFloatingDiv('DiffDetails');
		});
		
	}
	// crmv@182073e
	
}