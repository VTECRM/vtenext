/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

var ModNotificationsCommon = ModNotificationsCommon || {

	divId: "ModNotifications",
	default_number_of_news: 20,

	follow: function(record) {
		jQuery("#vtbusy_info").show();
		jQuery.ajax({
			url: "index.php?module=ModNotifications&action=ModNotificationsAjax&file=SetFollowFlag&record=" + record,
			success: function(data) {
				if (data.indexOf(":#:SUCCESS") > -1) {
					var response = data.split(":#:SUCCESS");
					response = response[1];
					if (response != "") {
						jQuery("#followImg").text(response);
					}
				}
				jQuery("#vtbusy_info").hide();
			}
		});
	},

	displayDetailNotificationModuleSettings: function(record) {
		jQuery("#notification_module_settings").fadeToggle(); // crmv@168103
	},

	getLastNotifications: function(obj) {
		showFloatingDiv(ModNotificationsCommon.divId, obj);
		ModNotificationsCommon.loadModNotifications(ModNotificationsCommon.default_number_of_news);
	},

	loadModNotifications: function(num, target, indicator) {
		if (target == undefined || target == "") {
			target = ModNotificationsCommon.divId + "_div";
		}
		if (indicator == undefined || indicator == "") {
			indicator = "indicator" + ModNotificationsCommon.divId;
		}
		ModNotificationsCommon.reloadContentWithFiltering("DetailViewBlockCommentWidget", "", num, target, indicator);
	},

	reloadContentWithFiltering: function(widget, parentid, criteria, targetdomid, indicator) {
		jQuery('#' + indicator).show();

		var url = "module=ModNotifications&action=ModNotificationsAjax&file=ModNotificationsWidgetHandler&ajax=true";
		url += "&widget=" + encodeURIComponent(widget) + "&parentid=" + encodeURIComponent(parentid);
		url += "&criteria=" + encodeURIComponent(criteria) + "&target_frame=" + encodeURIComponent(targetdomid) + "&indicator=" + encodeURIComponent(indicator); // crmv@174098

		jQuery.ajax({
			url: "index.php?" + url,
			type: "POST",
			dataType: "html",
			success: function(result) {
				jQuery('#' + indicator).hide();

				if (jQuery('#' + targetdomid).length > 0) {
					jQuery('#' + targetdomid).html(result);
					jQuery('#' + targetdomid).show();
				}

				// crmv@30850 crmv@43194 crmv@59626
				jQuery("#" + targetdomid).on("click", ".ModCommUnseen", function() {
					// crmv@82419
					var container = jQuery(this).closest("table[id^=tbl]"),
						id = container.find(".dataId").html(),
						imgSeen = container.find(".seenIcon"),
						imgUnseen = container.find(".unseenIcon");

					NotificationsCommon.removeChange("ModNotifications", id, function() {
						container.find(".ModCommUnseen").removeClass("ModCommUnseen");
						imgUnseen.hide();
						imgSeen.show();
					});
				});
				// crmv@30850e crmv@43194e crmv@59626e
			}
		});
	},

	// crmv@43194
	acceptInvitation: function(record, user, answer) {
		if (answer === undefined || answer === null || answer === "") answer = true;
		savePartecipation(record, user, answer ? 2 : 1);
	},

	declineInvitation: function(record, user) {
		return this.acceptInvitation(record, user, false);
	},

	markAllAsRead: function() {
		NotificationsCommon.removeChange("ModNotifications", "all", function() {
			ModNotificationsCommon.loadModNotifications(jQuery("#ModNotificationsDetailViewBlockCommentWidget_max_number_of_news").val());
		});
	},

	markAsRead: function(notificationid, domid, seen) {
		return this.markAsUnread(notificationid, domid, 1);
	},

	markAsUnread: function(notificationid, domid, seen) {
		var rowContainer = jQuery("#tbl" + domid + "_" + notificationid);

		if (seen === undefined || seen === null || seen === "") seen = 0;

		jQuery("#indicatorModNotifications").show();
		jQuery.ajax({
			url: "index.php?module=ModNotifications&action=ModNotificationsAjax&file=DetailViewAjax&ajxaction=GETNOTIFICATION&seen=" + seen + "&record=" + notificationid,
			success: function(data) {
				if (data.indexOf(":#:SUCCESS") > -1) {
					jQuery("#indicatorModNotifications").hide();
					var response = data.split(":#:SUCCESS"),
						counter = response[0];
					response = response[1];
					if (response != "") {
						rowContainer.html(response);
						NotificationsCommon.drawChangesAndStorage(
							"ModNotificationsCheckChangesDiv",
							"ModNotificationsCheckChangesImg",
							counter,
							"ModNotifications"
						); // crmv@OPER5904
					}
				}
			}
		});
	},
	// crmv@43194e

	followCV: function() {
		var record = jQuery("#viewname").val();
		jQuery("#status").show();
		jQuery.ajax({
			url: "index.php?module=ModNotifications&action=ModNotificationsAjax&file=SetFollowFlag&type=customview&record=" + record,
			success: function(data) {
				if (data.indexOf(":#:SUCCESS") > -1) {
					var response = data.split(":#:SUCCESS");
					response = response[1];
					if (response != "") {
						jQuery("#followImgCV").text(response);
					}
				}
				jQuery("#status").hide();
			}
		});
	},

	setFollowImgCV: function(record) {
		jQuery("#status").show();
		jQuery.ajax({
			url: "index.php?module=ModNotifications&action=ModNotificationsAjax&file=SetFollowFlag&type=customview&record=" + record + "&mode=get_image",
			success: function(data) {
				if (data.indexOf(":#:SUCCESS") > -1) {
					var response = data.split(":#:SUCCESS");
					response = response[1];
					if (response != "") {
						jQuery("#followImgCV").text(response);
					}
				}
				jQuery("#status").hide();
			}
		});
	},

	toggleChangeLog: function(id) {
		var div = "div_" + id;
		var img = "#img_" + id;

		if (getObj(div).style.display != "block") {
			getObj(div).style.display = "block";
			jQuery(img).html("keyboard_arrow_down"); // crmv@104566
		} else {
			getObj(div).style.display = "none";
			jQuery(img).html("keyboard_arrow_right"); // crmv@104566
		}
	}

};