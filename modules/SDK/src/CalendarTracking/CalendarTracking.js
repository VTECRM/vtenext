/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@33448 crmv@55708 crmv@62394 */

var CalendarTracking = {
	
	baseAjaxUrl: 'index.php?module=SDK&action=SDKAjax&file=src/CalendarTracking/',
	
	showButtons: function() {
		jQuery("#track_buttons").css('visibility', 'visible');
	},
	
	hideButtons: function() {
		jQuery("#track_buttons").css('visibility', 'hidden');
	},
	
	showPopup: function(focusElement) {
		showFloatingDiv('trackerPopup');
	},
	
	setPopupType: function(type) {
		jQuery('#trackerPopup_type').val(type);
	},
	
	getPopupType: function() {
		return jQuery('#trackerPopup_type').val();
	},
	
	setPopupId: function(id) {
		jQuery('#trackerPopup_id').val(id);
	},
	
	getPopupId: function() {
		return jQuery('#trackerPopup_id').val();
	},
	
	hidePopup: function() {
		hideFloatingDiv('trackerPopup');
	},
	
	trackInCalendar: function(id, type, focusElement) {
		var me = this;

		jQuery('#track_message').val('');
		jQuery.ajax({
			url: me.baseAjaxUrl+'CheckTrackState&record='+id+'&type='+type,
			success: function(data){
				if (data == 'FAILED') {
					vtealert(alert_arr.ERROR);
				} else if (data.indexOf('FAILED::') > -1) {
					var message = data.split('FAILED::');
					vtealert(message[1], null, {html: true});
				} else if (data == 'SUCCESS') {
					me.changeTrackState(id,type);
				} else if (data.indexOf('SUCCESSMESSAGE::') > -1) {
					var message = data.split('SUCCESSMESSAGE::');
					message = message[1];
					me.setPopupType(type);
					me.setPopupId(id);
					me.showPopup(focusElement);
					jQuery('#track_message').val(message);
				}
			}
		});
	},
	
	changeTrackState: function(id, type, create_ticket, overrideMsg) {
		var me = this;
		
		id = parseInt(id);
		id = parseInt(id || me.getPopupId());
		
		if (!id) {
			console.log('ERROR: Empty ID');
			return;
		}

		type = type || me.getPopupType();
		if (!type) {
			console.log('ERROR: Missing type');
			return;
		}
		
		jQuery("#vtbusy_info").show();
		
		// defaults to no
		create_ticket = create_ticket || 'no';
		
		// if provided, use it!
		if (typeof overrideMsg == 'undefined' || overrideMsg === null) {
			var message = jQuery('#track_message').val();
		} else {
			var message = overrideMsg;
			jQuery('#track_message').val(message);
		}
		message = encodeURIComponent(message); // crmv@104437
		
		//crmv@69922
		var otherParams = {};		
		if (type == "stop") {
			if (window.gVTModule && window.gVTModule == 'Messages') {
				var relcrmid = jQuery('#buc_relcrmid_0').val();
				if (relcrmid) otherParams['relcrmid'] = relcrmid;
			}
		}
		var otherParamsSerialize = jQuery.param(otherParams);
		//crmv@69922e
		
		me.hideButtons();
		jQuery.ajax({
			url: me.baseAjaxUrl+'ChangeTrackState&record='+id+'&type='+type+'&create_ticket='+create_ticket+'&description='+message+'&'+otherParamsSerialize, //crmv@69922
			success: function(data){
				if (data == 'SUCCESS') {
					if (document.forms['DetailView'].module.value == 'HelpDesk' && message != '') {
						// crmv@199978
						var comments_div = getFile('index.php?file=DetailViewAjax&module=HelpDesk&action=HelpDeskAjax&record='+id+'&recordid='+id+'&fldName=comments&fieldValue='+message+'&ajxaction=DETAILVIEW');
						if (jQuery("#comments_div").length > 0) jQuery("#comments_div").html(comments_div.replace(":#:SUCCESS",""));
						// crmv@199978e
					}
					getFile(me.baseAjaxUrl+'ChangeTrackState&record='+id+'&type='+type+'&mode=save_state');
					me.hidePopup();
					me.reloadButtons(id);
					if (type != 'play' && window.gVTModule == 'Messages' && window.populateTurbolift) {
						populateTurbolift(id, true);
					}
				} else {
					console.log('Backend error', data);
				}
				jQuery("#vtbusy_info").hide();
			}
		});
	},
	
	trackInCompose: function(type) {
		var me = this;
		
		me.hideButtons();
		
		jQuery.ajax({
			url: 'index.php?module=Utilities&action=UtilitiesAjax&file=getServerTime&oformat=raw',
			success: function(data) {
				if (data && data > 0) {
					jQuery("#track_buttons").hide(); // hide totally
					jQuery('#track_buttons_active_lbl').show();
					jQuery('#tracking_compose_track').val('1');
					jQuery('#tracking_compose_start_ts').val(data);
				}
			}
		});
	},
	
	setComposeStopTime: function() {
		var me = this;
		
		var enabled = jQuery('#tracking_compose_track').val();
		
		jQuery('#tracking_compose_stop_ts').val('0');
		if (enabled == '1') {
			jQuery.ajax({
				url: 'index.php?module=Utilities&action=UtilitiesAjax&file=getServerTime&oformat=raw',
				success: function(data) {
					if (data && data > 0) {
						jQuery('#tracking_compose_stop_ts').val(data);
					}
				}
			});
		}
	},
	
	getCurrentShownId: function() {
		var id = jQuery('#track_buttons_current_id').val() || 0;
		return id;
	},
	
	getCurrentTrackedId: function() {
		var id = jQuery('#track_buttons_active_id').val() || 0;
		return id;
	},
	
	reloadButtons: function(id, success, failure) {
		var me = this;
		
		me.hideButtons();
		jQuery.ajax({
			url: me.baseAjaxUrl+'DetailButtons&record='+id,
			success: function(data) {
				if (data) {
					jQuery('#track_buttons').html(data);
					me.showButtons();
					if (typeof success == 'function') success();
				} else {
					if (typeof failure == 'function') failure();
				}
			}
		});
	},
	
	// ----- OLD FUNCTIONS -----
	
	trackInCalendarList: function(id,module,type) {
		var me = this;
		
		jQuery("#track_message").html("");
		jQuery.ajax({
			url: me.baseAjaxUrl+'CheckTrackState&record='+id+'&type='+type,
			success: function(data){
				if (data == 'FAILED') {
					location.reload();
				} else if (data == 'SUCCESS') {
					me.changeTrackStateList(id,module,type);
				} else if (data.indexOf('SUCCESSMESSAGE::') > -1) {
					var message = data.split('SUCCESSMESSAGE::');
					message = message[1];
					jQuery("#track_buttons").hide();
					
					jQuery('#track_message_id').val(id);
					jQuery('#track_message_module').val(module);
					jQuery('#track_message_type').val(type);
					
					jQuery("#track_message_tbl").show();
					jQuery("#track_message").html(message);
					if (module == 'HelpDesk') {
						jQuery('#track_message_btns_helpdesk').show();
						jQuery('#track_message_btns_standard').hide();
					} else {
						jQuery('#track_message_btns_helpdesk').hide();
						jQuery('#track_message_btns_standard').show();
					}
				}
			}
		});
	},
	
	changeTrackStateList: function(id,module,type,create_ticket, overrideMsg) { // crmv@79996
		var me = this;
		
		id = id || jQuery('#track_message_id').val();
		module = module || jQuery('#track_message_module').val();
		type = type || jQuery('#track_message_type').val();
		
		// defaults to no
		create_ticket = create_ticket || 'no';

		jQuery("#track_buttons").hide();
		jQuery("#track_message_tbl").hide();
		jQuery("#detailview_block_indicator").show();

		// crmv@79996
		if (typeof overrideMsg == 'undefined' || overrideMsg === null) {
			var message = jQuery('#track_message').val();
		} else {
			var message = overrideMsg;
			jQuery('#track_message').val(message);
		}
		// crmv@79996e
		
		jQuery.ajax({
			url: me.baseAjaxUrl+'ChangeTrackState&record='+id+'&type='+type+'&create_ticket='+create_ticket+'&description='+message,
			success: function(data){
				if (data == 'SUCCESS') {
					if (module == 'HelpDesk' && message != '') {
						getFile('index.php?file=DetailViewAjax&module=HelpDesk&action=HelpDeskAjax&record='+id+'&recordid='+id+'&fldName=comments&fieldValue='+message+'&ajxaction=DETAILVIEW');
					}
					getFile(me.baseAjaxUrl+'ChangeTrackState&record='+id+'&type='+type+'&mode=save_state');
					location.reload();
				}
			}
		});
	},
	
}
