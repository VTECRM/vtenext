/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@194723

window.CalendarResources = window.CalendarResources || {
	
	initWindowListener: false,
	calendarResourcesTable: null,
	columns: null,
	
	loadCalendarResources: function() {
    	var me = this;
    	
    	// Force week view
    	me.forceWeekView();

		jQuery('#calendarResources').hide();
		
		jQuery.ajax({
			url: 'index.php?module=Calendar&action=CalendarAjax&file=CalendarResources',
			type: 'GET',
			dataType: 'json',
			success: function(data) {
				if (data && data.html) {
					jQuery('#calendarResources').html(data.html);
					jQuery('#calendarResources').show();
					
					me.adjustViewSize();
					
					me.columns = data.columns;
					me.initResourcesDataTable();
				}
			}
		});
    },

	forceWeekView: function() {
		var options = jQuery('#gridcontainer').BcalGetOp();

    	if (options.view !== 'week') {
    		jQuery('#gridcontainer').BcalSetOp({ 'view': 'res' });
    		jQuery('#showweekbtn').click();
    	}
	},
	
	adjustViewSize: function() {
		var me = this;
		
		jQuery('#calendarResources').width((jQuery(window).width()) + 'px');

		if (me.initWindowListener) return;
		
		jQuery(window).on('resize', function() {
			jQuery('#calendarResources').width((jQuery(window).width()) + 'px');
		});
		
		me.initWindowListener = true;
	},
	
	onLoadRolesModal: function() {
		var bsModal = jQuery('#rolesModal').data('bs.modal');
		var isShown = bsModal && bsModal.isShown || false;
		
		if (isShown) return;
		
		VteJS_DialogBox.progress();
		
		var height = parseInt(jQuery(window).height()) - 200;
		
		jQuery.ajax({
			url: 'index.php?module=Calendar&action=CalendarAjax&file=CalendarResources&subaction=fetch_roles',
			type: 'GET',
			dataType: 'json',
			success: function(data) {
				if (data && data.html) {
					VTE.showModal('rolesModal', data.title, data.html, {
						height: height + 'px',
						maxHeight: height,
						showFooter: true,
						buttons: [
							{
								id: 'cancelBtn', 
								cls: 'crmbutton edit', 
								dismissable: true, 
								value: data.labels.LBL_CLOSE,
							},
							{
								id: 'unselectAllBtn', 
								cls: 'crmbutton edit', 
								dismissable: false, 
								value: data.labels.LBL_UNSELECT_ALL,
								handler: function() {
									CalendarResources.uncheckAllRoleUsers();
								},
							},
							{
								id: 'saveRolesBtn', 
								cls: 'crmbutton save', 
								dismissable: false, 
								value: data.labels.LBL_SAVE_LABEL,
								handler: function() {
									CalendarResources.saveResourceList();
								},
							},
						],
						events: {
							'hidden.bs.modal': function() {
								CalendarResources.destroyCalendarResources();
								CalendarResources.initResourcesDataTable();
							},
						},
					});
					VteJS_DialogBox.hideprogress();
				}
			}
		});
	},
	
	checkAllRoleUsers: function(el, roleId) {
		var checkAll = jQuery(el).data('isAllChecked') || false;
		
		checkAll = !checkAll;
		jQuery('.uil .usercheck_' + roleId).not(':disabled').prop('checked', checkAll);
		
		jQuery(el).data('isAllChecked', checkAll);
	},
	
	uncheckAllRoleUsers: function() {
		jQuery('.uil .usercheck').not(':disabled').prop('checked', false);
		jQuery('.uil .x').data('isAllChecked', false);
	},
	
	toggleRoleVisibility: function(subRoles, roleId) {
		var subRoleList = subRoles.split(',');

		for (var i = 0; i < subRoleList.length; i++) {
			var x = jQuery('#' + subRoleList[i]);
			if (!x.is(':visible')) {
				x.show();
				jQuery('.uil .useritem_' + roleId).show();
				jQuery('#img_' + roleId).text('indeterminate_check_box');
			} else {
				x.hide();
				jQuery('.uil .useritem_' + roleId).hide();
				jQuery('#img_' + roleId).text('add_box');
			}
		}
	},
	
	saveResourceList: function() {
		var selectedResources = [];
		
		jQuery('.uil .usercheck:checked').not(':disabled').each(function() {
			var userId = parseInt(jQuery(this).val());
			selectedResources.push(userId);
		});
		
		var data = {
			'selected_resources': selectedResources
		};
		
		VteJS_DialogBox.progress('rolesModal');
		
		jQuery.ajax({
			url: 'index.php?module=Calendar&action=CalendarAjax&file=CalendarResources&subaction=save_resources',
			type: 'POST',
			data: data,
			dataType: 'json',
			success: function(data) {
				VteJS_DialogBox.hideprogress('rolesModal');
				VTE.hideModal('rolesModal');
			}
		});
	},
	
	initResourcesDataTable: function() {
		var me = this;
		
		jQuery('#calendarResourcesTable').on('draw.dt', me.onDataTableDraw);
		jQuery('#calendarResourcesTable').on('preXhr.dt', me.onDataTableDataLoad);
		jQuery('#calendarResourcesTable').on('xhr.dt', me.onDataTableDataLoadEnd);
		
		var options = me.getDataTableOptions();
		me.calendarResourcesTable = jQuery('#calendarResourcesTable').DataTable(options);	
		
		jQuery('#calendarResourcesTable tbody').on('click', '.event-chip', me.onEventClick);
		//jQuery('#calendarResourcesTable tbody').on('mouseover', '.user-cell', me.onUserCellOver);
		//jQuery('#calendarResourcesTable tbody').on('mouseout', '.user-cell', me.onUserCellOut);
		jQuery('#calendarResourcesTable tbody').on('click', '.day-cell', me.onDayCellClick);
	},
	
	getDataTableOptions: function() {
		var me = this;
		
		var height = parseInt(jQuery(window).height()) - 70;
		
		var options = {
			scrollY: height,
			scrollX: true,
			scrollCollapse: true,
			paging: false,
			info: false,
			searching: false,
			ordering: false,
	        serverSide: true,
			fixedColumns: {
				leftColumns: 1,
			},
			ajax: {
				url: 'index.php?module=Calendar&action=CalendarAjax&file=CalendarResources&subaction=fetch_data',
				data: me.appendAjaxData,
				type: 'GET',
			},
			columns: me.columns,
			columnDefs: [
				{
					targets: me.getColumnIdx('user'),
					width: 220,
					render: me.renderUser,
				},
				{
					targets: [
						me.getColumnIdx('all_events.mon'),
						me.getColumnIdx('all_events.tue'),
						me.getColumnIdx('all_events.wed'),
						me.getColumnIdx('all_events.thu'),
						me.getColumnIdx('all_events.fri'),
						me.getColumnIdx('all_events.sat'),
						me.getColumnIdx('all_events.sun'),
					],
					render: me.renderSlot,
				},
			],
			headerCallback: me.headerCallback
		};
		
		return options;
	},
	
	getColumnIdx: function(columnName) {
		var index = 'indexnotfound';
		for (var i = 0; i < this.columns.length; i++) {
		    if (this.columns[i].data == columnName) {
		        index = i;
		        break;
		    }
		}
		return index;
	},
	
	renderUser: function(data, type, row) {
    	var renderHtml = CalendarResources.getUserTemplate(data);
		return renderHtml;
    },
    
    getUserTemplate: function(values) {
    	values = values || {};
    	
		var userTemplate = '';
		
		userTemplate += '<div class="user-info" data-user-id="{{USER_ID}}">' +
			'<div class="user-color" style="background-color:{{USER_CALENDAR_COLOR}};"></div>' +
			'<img src="{{USER_AVATAR}}" class="avatar user-avatar" />' +
			'<div class="user-text">' +
				'<span class="user-username" title="{{USER_USERNAME}}">{{USER_USERNAME}}</span>' +
				'<div><span class="user-completename">{{USER_COMPLETENAME}}</span></div>' +
			'</div>' +
			'<div class="user-actions" style="display:none;">' +
				'<div class="user-action"><i class="vteicon user-trash-icon">close</i></div>' +
			'</div>' +
		'</div>';

		if (jQuery.isPlainObject(values)) {
			userTemplate = userTemplate.replace(/{{USER_ID}}/g, values['id'])
				.replace(/{{USER_CALENDAR_COLOR}}/g, values['cal_color'])
				.replace(/{{USER_AVATAR}}/g, values['avatar'])
				.replace(/{{USER_USERNAME}}/g, values['user_name'])
				.replace(/{{USER_COMPLETENAME}}/g, values['complete_name']);
		}

		return userTemplate;
	},
    
    renderSlot(data, type, row) {
    	var events = data.events || [];
    	
		var eventsHtml = '';
		
		for (var i = 0; i < events.length; i++) {
			eventsHtml += CalendarResources.getEventTemplate(events[i]);
		}

		var renderHtml = CalendarResources.getSlotTemplate({
			'slot_date': data.slot_date,
			'slot_date_display': data.slot_date_display,
			'events': eventsHtml
		});

		return renderHtml;
    },
    
    headerCallback: function(thead, data, start, end, display) {
    	var me = CalendarResources;
		
    	// Add the week inside the first column
    	var calOp = jQuery('#gridcontainer').BcalGetOp();
    	
    	var columnIdx = me.getColumnIdx('user');
    	var title = me.columns[columnIdx]['title'];
		jQuery(thead).find('th').eq(0).html('<div class="week-num">' + title + ' ' + calOp.vstart.getWeek() + ' - ' + calOp.vstart.getWeekYear() + '</div>');
		
		// Add the date inside the other columns
		if (data && data[0]) {
			var keys = Object.keys(data[0]['all_events']);
			for (var i = 0; i < keys.length; i++) {
				var dayName = keys[i];
				var slotDate = data[0]['all_events'][dayName]['slot_date'] || "";
				var slotDateDisplay = data[0]['all_events'][dayName]['slot_date_display'] || "";
				me.populateHeaderColumn(dayName, slotDate, slotDateDisplay);
			}
		}
		
		CalendarResources.relayoutFixedColumns();
	},
	
	populateHeaderColumn: function(dayName, slotDate, slotDateDisplay) {
		var me = this;
		
		var columnIdx = me.getColumnIdx('all_events.' + dayName);
		var column = jQuery(me.calendarResourcesTable.columns(columnIdx).header());
		var title = me.columns[columnIdx]['title'];
		
		column.html(title + '<div class="event-slot-date" data-slot-date="' + slotDate + '">' + slotDateDisplay + '</div>');
		
		setResourcesTodayColumn(); //crmv@201468
	},
	
    getSlotTemplate(values) {
    	values = values || {};
    	
    	var slotTemplate = '';

    	slotTemplate += '<div class="event-slot-date" data-slot-date="{{SLOT_DATE}}" style="display:none;">{{SLOT_DATE_DISPLAY}}</div>';
    	slotTemplate += '<div class="event-containers">{{EVENTS}}</div>';

    	if (jQuery.isPlainObject(values)) {
    		slotTemplate = slotTemplate.replace(/{{SLOT_DATE}}/g, values['slot_date'])
				.replace(/{{SLOT_DATE_DISPLAY}}/g, values['slot_date_display'])
				.replace(/{{EVENTS}}/g, values['events']);
    	}
    	
		return slotTemplate;
    },
    
	getEventTemplate: function(values) {
		values = values || {};
		
		var eventTemplate = '';

		var eventData = {
			'data-activity-id': '{{ACTIVITY_ID}}',
			'data-activity-owner': '{{ACTIVITY_OWNER}}',
			'data-activity-user': '{{ACTIVITY_USER}}',
			'data-activity-sequence': '{{ACTIVITY_SEQUENCE}}',
			'data-movable': '{{MOVABLE}}',
		};

		var htmlAttributes = [];

		for (var key in eventData) {
			if (eventData.hasOwnProperty(key)) {
				htmlAttributes.push(key + "=" + eventData[key]);
			}
		}

		htmlAttributes = htmlAttributes.join(" ");
		
		eventTemplate += '<div class="event-chip" ' + htmlAttributes + '>' +
			'<div class="event-chip-color" style="background-color:{{USER_CALENDAR_COLOR}};">' +
				'<img class="event-chip-invited-icon" src="{{INVITED_ICON}}" />' +
				'<i class="vteicon event-chip-type nohover">{{ACTIVITY_TYPE_ICON}}</i>' +
			'</div>' +
			'<div class="event-chip-text">' +
				'<div class="event-chip-hour">' +
					'<span>{{TIME_START}}</span><span>{{TIME_END}}</span>' +
				'</div>' +
				'<div class="event-chip-title">{{SUBJECT}}</div>' +
			'</div>' +
		'</div>';

		if (jQuery.isPlainObject(values)) {
			eventTemplate = eventTemplate.replace(/{{ACTIVITY_ID}}/g, values['crmid'])
				.replace(/{{ACTIVITY_OWNER}}/g, values['owner_id'])
				.replace(/{{ACTIVITY_USER}}/g, values['user_id'])
				.replace(/{{ACTIVITY_SEQUENCE}}/g, values['sequence'])
				.replace(/{{USER_CALENDAR_COLOR}}/g, values['cal_color'])
				.replace(/{{INVITED_ICON}}/g, values['invited_icon'])
				.replace(/{{ACTIVITY_TYPE_ICON}}/g, values['activitytype_icon'])
				.replace(/{{TIME_START}}/g, values['time_start'])
				.replace(/{{TIME_END}}/g, values['time_end'])
				.replace(/{{SUBJECT}}/g, values['subject'])
				.replace(/{{MOVABLE}}/g, values['movable']);
		}
		
		return eventTemplate;
	},
	
	onEventClick: function(e) {
		e.stopPropagation();
		var activityId = parseInt(jQuery(this).data('activityId'));
		CalendarResources.loadEventPopup(activityId);
	},
	
	onDayCellClick: function() {
		var startDate = jQuery(this).closest('td').find('.event-slot-date').data('slotDate');
		var modifiedUser = parseInt(jQuery(this).closest('tr').find('.user-info').data('userId'));
		CalendarResources.loadEventPopup(0, {
			ajaxParams: {
				subaction: 'CalendarResourcesAdd',
				date_start: startDate,
				due_date: startDate,
				assigned_user_id: modifiedUser,
    		},
			skipCreateAutofill: true,
			disableTodo: true,
		});
	},
	
	onUserCellOver: function() {
		var userActions = jQuery(this).find('.user-actions');
		if (userActions.length > 0) {
			userActions.stop(true).fadeIn();
		}
	},
	
	onUserCellOut: function() {
		var userActions = jQuery(this).find('.user-actions');
		if (userActions.length > 0) {
			userActions.stop(true).fadeOut();
		}
	},
	
	onDataTableDraw: function() {
		if (jQuery('.event-containers').length > 0) {
			var options = CalendarResources.getSortableOptions();
			jQuery('.event-containers').sortable(options);
		}
	},
	
	onDataTableDataLoad: function() {
		VteJS_DialogBox.progress();
	},
	
	onDataTableDataLoadEnd: function() {
		VteJS_DialogBox.hideprogress();
	},
	
	getSortableOptions: function() {
		var me = this;
		
		var options = {
			connectWith: '.event-containers',
			items: '.event-chip',
			opacity: 0.75,
			revert: 200,
			helper: 'clone',
			tolerance: 'pointer',
			placeholder: 'ui-state-highlight',
			forcePlaceholderSize: true,
			start: me.onEventSortableStart,
			stop: me.onEventSortableStop,
			update: me.onEventSortableUpdate
		};
		
		return options;
	},
	
	onEventSortableStart: function(event, ui) {
		var activityId = jQuery(ui.item).data('activityId');
		var activityUser = jQuery(ui.item).data('activityUser');
		var sequence = jQuery(ui.item).data('activitySequence');
		var movable = jQuery(ui.item).data('movable');
		
		var elementsToHide = jQuery('.event-chip[data-activity-id="' + activityId + '"][data-activity-user="' + activityUser +  '"]').not('.event-chip[data-activity-sequence="' + sequence + '"]');
		
		if (elementsToHide.length > 0) {
			elementsToHide.fadeOut('fast');
			
			var originalWidth = parseInt(jQuery(ui.item).width());
			var animateWidth = originalWidth * (elementsToHide.length+1);
			jQuery(ui.helper).animate({
				'width': animateWidth + 'px'
			});
		}
		
		if (movable === 0) {
			jQuery(ui.placeholder).removeClass('ui-state-highlight').addClass('ui-state-highlight-disabled');
		}
		
		// Prevent event create popup
		jQuery(ui.item).closest('.day-cell').one('click', function(e) {
			e.stopImmediatePropagation(); 
		});
	},
	
	onEventSortableStop: function(event, ui) {
		var activityId = jQuery(ui.item).data('activityId');
		jQuery('.event-chip[data-activity-id="' + activityId + '"]').fadeIn();
	},
	
	onEventSortableUpdate: function(event, ui) {
		if (this === ui.item.parent()[0]) {
			var activityId = jQuery(ui.item).data('activityId');
			var movable = jQuery(ui.item).data('movable');
			
			if (movable === 0) {
				ui.sender.sortable('cancel');
				return;
			}

			setTimeout(function() {
				var modifiedUser = parseInt(jQuery(ui.item).closest('tr').find('.user-info').data('userId'));
				var modifiedDate = jQuery(ui.item).closest('td').find('.event-slot-date').data('slotDate');
				
				CalendarResources.loadEventPopup(activityId, {
					ajaxParams: {
						subaction: 'CalendarResourcesEdit',
						modified_user: modifiedUser,
						modified_date: modifiedDate,
	        		}
				});

	    		ui.sender.sortable('cancel');
			}, 200);
		}
	},
	
    loadEventPopup: function(activityId, params) {
    	activityId = parseInt(activityId);
    	params = params || {};
    	
		parent.showFloatingDiv('addEvent', null, { modal: true });
		
		var mode = (activityId > 0) ? 'edit' : '';
		
		var params = jQuery.extend({}, {
    		forceLoad: true,
    		mode: mode,
    		record: activityId,
    		data: {},
    		successAddEvent: CalendarResources.successAddEvent,
    	}, params || {});
		
		var hrefTab = 'a[href="#event-tab"]';
		parent.jQuery(hrefTab).trigger('click.tab.data-api', params);
    },
    
    successAddEvent: function(response) {
		parent.SuccessAddEvent(response);
		CalendarResources.reloadView();
	},
	
	reloadView: function() {
		if (CalendarResources.calendarResourcesTable) {
			CalendarResources.calendarResourcesTable.ajax.reload();
		}
	},
	
	relayoutFixedColumns: function() {
		jQuery.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().fixedColumns().relayout();
	},
	
	clearCalendarResources: function() {
    	var me = this;
    	
    	jQuery('#calendarResources').hide();
    	jQuery('#gridcontainer').BcalSetOp({ 'view': 'res' });
    	me.destroyCalendarResources();
    	
    	var bsModal = jQuery('#rolesModal').data('bs.modal');
		var isShown = bsModal && bsModal.isShown || false;
		if (isShown) {
			jQuery('#rolesModal').off('hidden.bs.modal');
			VTE.hideModal('rolesModal');
		}
    },
    
    destroyCalendarResources: function() {
    	if (CalendarResources.calendarResourcesTable) {
    		CalendarResources.calendarResourcesTable.destroy();
    		delete CalendarResources.calendarResourcesTable;
        	jQuery('#calendarResourcesTable').empty();
    	}
    },
    
    appendAjaxData: function() {
    	var calOp = jQuery('#gridcontainer').BcalGetOp();

		function formatDate(date) {
		    var d = new Date(date),
		        month = '' + (d.getMonth() + 1),
		        day = '' + d.getDate(),
		        year = d.getFullYear();

		    if (month.length < 2) {
		        month = '0' + month;
		    }
		    if (day.length < 2) {
		        day = '0' + day;
		    }

		    return [year, month, day].join('-');
		}

		var startDate = formatDate(calOp.vstart);
		var endDate = formatDate(calOp.vend);

		return {
			'start_date': startDate,
			'end_date': endDate,
		};
    }
	
};