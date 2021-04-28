/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@191351

window.VTE = window.VTE || {};

VTE.Messages = VTE.Messages || {};

VTE.Messages.Settings = VTE.Messages.Settings || {};

VTE.Messages.Settings.FormOutOfOffice = VTE.Messages.Settings.FormOutOfOffice || {

	initialized: false,

	initialize: function() {
		var me = this;

		if (me.initialized) return;
		me.initialized = false;

		me.setupEvents();
		me.setupDatePickers();
		me.checkActiveForm();
		me.checkActiveEndDate();
		me.checkActiveAllDay();

		me.initialized = true;
	},

	setupEvents: function() {
		var me = this;

		jQuery('input[name="active"]').on('click', me.handleOutOfOfficeActivation);
		jQuery('input[name="end_date_active"]').on('click', me.handleEndDateActivation);
		jQuery('input[name="start_date_allday"]').on('click', me.handleAllDayActivation);
	},

	setupDatePickers: function() {
		var me = this;

		var currentDateFormat = 'yyyy-mm-dd'; // window.current_user.date_format;
		var currentLanguage = window.current_language;

		setupDatePicker('start_date', {
			trigger: 'start_date_trigger',
			date_format: currentDateFormat.toUpperCase(),
			language: currentLanguage,
		});

		setupDatePicker('end_date', {
			trigger: 'end_date_trigger',
			date_format: currentDateFormat.toUpperCase(),
			language: currentLanguage,
		});
	},

	handleOutOfOfficeActivation: function() {
		VTE.Messages.Settings.FormOutOfOffice.checkActiveForm();
	},

	handleEndDateActivation: function() {
		VTE.Messages.Settings.FormOutOfOffice.checkActiveEndDate();
	},

	handleAllDayActivation: function() {
		VTE.Messages.Settings.FormOutOfOffice.checkActiveAllDay();
	},

	checkActiveForm: function() {
		var activeForm = jQuery('input[name="active"]:checked').val();
		jQuery('.form-messages-outofoffice-data')[activeForm === '1' ? 'show' : 'hide']();
	},

	checkActiveEndDate: function() {
		var activeEndDate = jQuery('input[name="end_date_active"]').prop('checked');
		jQuery('.form-messages-outofoffice-enddate-data')[activeEndDate ? 'show' : 'hide']();
	},

	checkActiveAllDay: function() {
		if (jQuery('#start_date_allday:checked').length > 0) {
			jQuery('#start_time_container').hide();
			jQuery('#end_time_container').hide();
		} else {
			jQuery('#start_time_container').show();
			jQuery('#end_time_container').show();
		}
	},

	validateAndSave: function() {
		var me = this;
		me.prepareFieldForSave();
		if (!me.validate()) return false;

		jQuery('form[name="OutOfOffice"]').submit();
	},
	
	prepareFieldForSave: function() {
		var me = this;
		
		var messageBody = CKEDITOR.instances['message_body'].getData();
		jQuery('#message_body').val(messageBody);
	},

	validate: function() {
		var me = this,
			subject = jQuery('#message_subject').val(),
			body = jQuery('#message_body').val(),
			start_date = jQuery('#jscal_field_start_date').val(),
			end_date = jQuery('#jscal_field_end_date').val(),
			start_time = jQuery('#jscal_field_start_time').val(),
			end_time = jQuery('#jscal_field_end_time').val(),
			accounts = jQuery('#accounts').val();

		var subjectLabel = me.getFieldLabel('message_subject');
		var bodyLabel = me.getFieldLabel('message_body');
		var startDateLabel = me.getFieldLabel('start_date');
		var endDateLabel = me.getFieldLabel('end_date');
		var startTimeLabel = me.getFieldLabel('start_time');
		var endTimeLabel = me.getFieldLabel('end_time');
		var accountsLabel = me.getFieldLabel('accounts');

		// check emptyness
		// if (!subject || subject.length === 0) return me.displayError(alert_arr.CANNOT_BE_EMPTY.replace('%s', subjectLabel));
		if (!body || body.length === 0) return me.displayError(alert_arr.CANNOT_BE_EMPTY.replace('%s', bodyLabel));
		if (!start_date || start_date.length === 0) return me.displayError(alert_arr.CANNOT_BE_EMPTY.replace('%s', startDateLabel));
		if (jQuery('#end_date_active:checked').length && (!end_date || end_date.length === 0)) return me.displayError(alert_arr.CANNOT_BE_EMPTY.replace('%s', endDateLabel));
		if (jQuery('#start_date_allday:checked').length == 0) {
			if (!start_time || start_time.length === 0) return me.displayError(alert_arr.CANNOT_BE_EMPTY.replace('%s', startTimeLabel));
			if (jQuery('#end_date_active:checked').length && (!end_time || end_time.length === 0)) return me.displayError(alert_arr.CANNOT_BE_EMPTY.replace('%s', endTimeLabel));
		}
		if (!accounts || accounts.length === 0) return me.displayError(alert_arr.CANNOT_BE_EMPTY.replace('%s', accountsLabel));
		
		// check start date is in the future
		__checkStartDate = function() {
			var currdate = new Date();
		    var chkdate = new Date();
			var split_date = start_date.split('-');
			switch (top.userDateFormat) {
				case "yyyy-mm-dd" :
					chkdate.setYear(split_date[0]);
					chkdate.setMonth(split_date[1]-1);
				    chkdate.setDate(split_date[2]);
					break;
				case "mm-dd-yyyy" :
					chkdate.setYear(split_date[2]);
					chkdate.setMonth(split_date[0]-1);
				    chkdate.setDate(split_date[1]);
					break;
				case "dd-mm-yyyy" :
					chkdate.setYear(split_date[2]);
					chkdate.setMonth(split_date[1]-1);
				    chkdate.setDate(split_date[0]);
					break;
			}
			if (jQuery('#start_date_allday:checked').length == 0) {
				var split_time = start_time.split(':');
				chkdate.setHours(split_time[0]);
			    chkdate.setMinutes(split_time[1]);
			} else {
			    chkdate.setHours(currdate.getHours());
			    chkdate.setMinutes(currdate.getMinutes());
			}
			chkdate.setMilliseconds(0);
			currdate.setMilliseconds(0);
			
			return (chkdate.getTime() >= currdate.getTime());
		}
		if (!__checkStartDate()) return me.displayError(sprintf(alert_arr.DATE_SHOULDBE_GREATER_EQUAL, startTimeLabel, 'now'));

		return true;
	},

	getFieldLabel: function(fieldname) {
		if (fieldname == 'start_date' || fieldname == 'end_date' || fieldname == 'start_time' || fieldname == 'end_time') {
			return jQuery('input[name="'+fieldname+'"]').closest('.row').find('.col-xs-2').text().replace(':', '');
		} else {
			return jQuery('[name="'+fieldname+'"]').closest('.form-group').find('label').text().replace(':', '');
		}
	},

	displayError: function(message) {
		alert(message);
		return false;
	},

};