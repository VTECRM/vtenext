/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@202301 */

window.VTE = window.VTE || {};

VTE.Settings = VTE.Settings || {};

VTE.Settings.AuditTrail = VTE.Settings.AuditTrail || {

	auditenabled: function(ochkbox) {
		var status = ochkbox.checked ? 'enabled' : 'dsabled';
		jQuery("#status").show();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Settings&action=SettingsAjax&ajax=true&file=AuditTrail/Save&audit_trail='+status,
			success: function(result) {
				jQuery("#status").hide();
				try {
					var data = JSON.parse(result);
				} catch (e) {
					var data = null;
				}
				if (data && data.success) {
					vtealert(status == 'enabled' ? alert_arr.LBL_AUDIT_TRAIL_ENABLED : alert_arr.LBL_AUDIT_TRAIL_DISABLED);
				} else if (data && data.error) {
					vtealert('Error: '+data.error);
				} else {
					vtealert('Error: Wrong answer from server');
				}
			}
		});
	},
	
	getAuditTrailUrlParams: function() {
		var userid = parseInt(jQuery('#user_list').val());
		
		var url = 'userid='+userid+
			'&interval='+encodeURIComponent(jQuery('#interval_selector').val())+
			'&date_start='+encodeURIComponent(jQuery('#jscal_field_date_start').val())+
			'&date_end='+encodeURIComponent(jQuery('#jscal_field_date_end').val());
		return url;
	},

	//crmv@203590
	showAuditTrail: function() {
		var userid = jQuery('#user_list').val();
		if (!userid) return;
		let dateStart = jQuery('#jscal_field_date_start').val();
		let startDate = this.parseDate(dateStart);

		if (this.calculateLimitDate(audit_log_interval).getTime() > startDate.getTime()) {
			this.openLogFileChooser();
			return;
		}

		this.getListViewEntries_js('Settings', '');
	},

	openLogFileChooser: function() {
		var me = this;
		let userId = jQuery('#user_list').val();
		me.showFloatingDiv('logs_div_filechooser');
		jQuery('#fileChooserTree').fileTree({
			script: 'include/js/jquery_plugins/fileTree/connectors/jqueryFileTreeAuditTrailLogs.php?userId='+userId
		}, function(file) {
			if (file && file.indexOf('/') === 0) file = file.substr(1);
			me.hideFloatingDiv('logs_div_filechooser');
			VteJS_DialogBox.progress();
			jQuery.ajax({
				url: 'modules/Settings/AuditTrail/DownloadLog.php?filename=' + file,
				success: function(data) {
					VteJS_DialogBox.hideprogress();
					me.createAndDownloadFile(file, data);
				}
			});
		});
	},

	createAndDownloadFile: function(fileName, fileData) {
		let a = document.createElement("a");
		document.body.appendChild(a);
		a.style = "display: none";
		let trimmedFileName = fileName.slice(0, -3);
		let	blob = new Blob([fileData], {
					type: "text/csv"
				}),
				url = window.URL.createObjectURL(blob);
		a.href = url;
		a.download = trimmedFileName;
		a.click();
		window.URL.revokeObjectURL(url);
	},

	showFloatingDiv: function(div) {
		this.fadeInPopupBackground();
		var me = this;

		if (me.activeFloatingDiv) return;

		var divobj = me.getJQueryObject(div);

		divobj.show();
		placeAtCenter(divobj.get(0));
	},

	hideFloatingDiv: function(div) {
		this.fadeOutPopupBackground();
		var me = this;
		var divobj = me.getJQueryObject(div);

		divobj.hide();
		me.activeFloatingDiv = null;
	},

	getJQueryObject: function(ref) {
		if (typeof ref == 'string') {
			ref = '#'+ref;
		}
		return jQuery(ref);
	},

	calculateLimitDate(monthsInterval) {
		let date = new Date();
		date.setMonth(date.getMonth() - monthsInterval);
		date.setDate(1);
		date.setHours(0,0,0,0);
		return date;
	},

	parseDate(date) {
		let dateArray = date.split("-",3);
		let returnDate = new Date();
		returnDate.setDate(parseInt(dateArray[0]));
		returnDate.setMonth(parseInt(dateArray[1]) - 1);
		returnDate.setFullYear(parseInt(dateArray[2]));
		returnDate.setHours(0,0,0,0);
		return returnDate;
	},

	fadeInPopupBackground() {
		jQuery('body').prepend('<div id="filechooser_background" class="modal fade in" tabindex="-1" ' +
			'style="z-index: 100008; display: block;background-color: rgba(0,0,0,0.1);"></div>');
	},

	fadeOutPopupBackground() {
		jQuery('#filechooser_background').remove();
	},
	//crmv@203590e

	// crmv@164355
	exportAuditTrail: function() {
		var userid = jQuery('#user_list').val();
		if (!userid) return;
		location.href = "index.php?module=Settings&action=SettingsAjax&file=AuditTrail/Export&"+this.getAuditTrailUrlParams();
	},
	// crmv@164355e

	getListViewEntries_js(module, url) {
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Settings&action=SettingsAjax&file=AuditTrail/Show&ajax=true&'+url+'&'+this.getAuditTrailUrlParams(),
			success: function(result) {
				jQuery("#AuditTrailContents").html(result);
			}
		});
	},
	
	initTimeIntervals: function() {
		var target = jQuery('#interval_selector'),
			html = '';
		for (var int in window.time_intervals) {
			var interval = time_intervals[int];
			var selected = '';
			if (int === 'thismonth') selected = 'selected';
			html += '<option '+selected+' value="'+int+'">'+interval.label+'</option>';
			
		}
		target.html(html);
		changeDateRangePicklist(target.val()); //crmv@203590
	}

};

/**
 * @deprecated
 * This function has been moved to VTE.Settings.AuditTrail class.
 */

function auditenabled(ochkbox) {
	return VTE.callDeprecated('auditenabled', VTE.Settings.AuditTrail.auditenabled, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Settings.AuditTrail class.
 */

function showAuditTrail() {
	return VTE.callDeprecated('showAuditTrail', VTE.Settings.AuditTrail.showAuditTrail, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Settings.AuditTrail class.
 */

function exportAuditTrail() {
	return VTE.callDeprecated('exportAuditTrail', VTE.Settings.AuditTrail.exportAuditTrail, arguments);
}