/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@98866
// crmv@103922

var CalendarPopup = CalendarPopup || {
	
	mode: '',
	record: null,
	busy: false,
	popupId: 'addEvent',
	progressId: 'calendarProgress',
	layerId: 'calendarPopupLayer',
	data: null,
	
	showBusy: function() {
		var me = this,
			popup = me.getPopup();
		
		if (!popup) return;
		
		me.busy = true;
		me.showProgress();
		
		me.recalcPopupHeight();
		popup.find('.closebutton').css('zIndex', findZMax()+1);
	},
	
	recalcPopupHeight: function() {
		var me = this,
			popup = me.getPopup();
		
		var popupHeight = popup.outerHeight(true);
		var height = popup.find('.level3Bg').first().outerHeight();
		
		popup.find('.main-content').css('height', (popupHeight-height-10) + 'px');
		popup.find('.main-content').css('max-height', (popupHeight-height-10) + 'px');
	},
	
	hideBusy: function() {
		var me = this;
		me.busy = false;
		me.hideProgress();
	},
	
	getPopup: function() {
		var me = this;
		var popup = jQuery('#' + me.popupId);
		return popup;
	},
	
	showButton: function(id) {
		var me = this,
			popup = me.getPopup();
		if (popup) popup.find('#' + id).show();
	},
	
	hideButton: function(id) {
		var me = this,
			popup = me.getPopup();
		if (popup) popup.find('#' + id).hide();
	},
	
	showTab: function(id) {
		var me = this,
			popup = me.getPopup();
		
		if (popup) jQuery('a[href="#' + id + '"]').show();
	},
	
	hideTab: function(id) {
		var me = this,
			popup = me.getPopup();
		
		if (popup) jQuery('a[href="#' + id + '"]').hide();
	},
	
	getActiveContainer: function() {
		var me = this,
			popup = me.getPopup();
		
		var container = null;
		if (popup) container = popup.find('div.tab-pane.active .tab-container');
		
		return container;
	},
	
	getActiveTab: function() {
		var me = this,
			popup = me.getPopup();
	
		var activeTab = null;
		var activeHref = null;
		
		if (popup) {
			var headerTab = popup.find('#header-tab');
			if (headerTab) activeHref = headerTab.find('li.active a');
			if (activeHref) activeTab = jQuery(activeHref.attr('href'));
		}
		
		return activeTab;
	},
	
	getCurrentTabName: function() {
		var me = this,
			activeTab = me.getActiveTab();
		
		var tabName = null;
		if (activeTab) {
			var tabId = activeTab.attr('id');
			tabName = tabId == 'event-tab' ? 'event' : 'task';
			return tabName;
		}
		
		return tabName;
	},
	
	getParentTab: function() {
		var me = this,
			popup = me.getPopup();
		
		var parentTab = null;
	
		if (popup) parentTab = popup.find('div.tab-pane.active .tab-container');
		
		return parentTab;
	},
	
	ajaxCall: function(action, params, options, callback) {
		var me = this;
		
		if (me.busy) return;
		
		options = jQuery.extend({}, {}, options || {});
		
		params = params || {};
		var url = "index.php?module=Calendar&action=CalendarAjax&file="+action;
		
		me.showBusy();
		jQuery.ajax({
			url: url,
			type: 'POST',
			async: true,
			data: params,
			success: function(data) {
				me.hideBusy();
				if (typeof callback == 'function') callback(data);
			},
			error: function() {
				me.hideBusy();
				if (options.callbackOnError) {
					if (typeof callback == 'function') callback();
				}
			}
		});
	},
	
	showProgress: function(imgurl) {
		var me = this,
			popup = me.getPopup();
		
		if (!popup) return;
		
		var progressObject = document.getElementById(me.progressId);
		
		if (!progressObject) {
			var progressObject = jQuery('<div>', {
				'id': me.progressId,
				'class': 'veil_light',
				'css': {
					'position': 'absolute',
					'width': '100%',
					'height': '100%',
					'top': '0',
					'left': '0',
					'display': 'block',
					'zIndex': findZMax() + 1
				},
			});
			
			var loaderTable = jQuery('<table>', {
				'align': 'center',
				'width': '100%',
				'height': '100%',
			});
			
			loaderTable.html('<tr><td class="big" align="center"><div class="vteLoader">Loading...</div></td></tr>');
			
			progressObject.append(loaderTable);
			
			popup.find('.main-content').css('overflow', 'hidden');
			popup.find('.main-content').append(progressObject);
		}
	},
	
	hideProgress: function() {
		var me = this,
			popup = me.getPopup();
		
		popup.find('.main-content').css('overflow-y', 'auto');
		popup.find('#' + me.progressId).remove();
	},
	
	showHideTab: function(params) {
		var me = this,
			popup = me.getPopup(),
			mode = params.mode || '';
		
		if (!popup) return;
		
		popup.find('#headerTitleCont').hide();
		popup.find('#headerTabCont').hide();
		
		if (!mode) {
			popup.find('#headerTitleCont').hide();
			popup.find('#headerTabCont').show();
			me[params.disableTodo ? 'hideTab' : 'showTab']('todo-tab');
		    me[params.disableEvent ? 'hideTab' : 'showTab']('event-tab');
		} else {
			if (params.showHeader) {
				var titleCont = popup.find('#headerTitleCont');
				titleCont.show();
				popup.find('#headerTabCont').hide();
				
				var title1 = titleCont.find('.recordTitle1');
				var title2 = titleCont.find('.recordTitle2');
				
				if (cPopTitle1) title1.html(cPopTitle1);
				if (params['mode'] == 'detail') title2.html(params['data'][1]); else if (cPopTitle2) title2.html(cPopTitle2); //crmv@178620
			} else {
				popup.find('#headerTabCont').hide();
			}
		}
		
		me.recalcPopupHeight();
	},
	
	showHideButtons: function(params) {
		var me = this,
			mode = params.mode || '';
		
		if (!mode) {
			me.showButton('btnSave');
			me.showButton('btnCancel');
			me.hideButton('btnEdit');
			me.hideButton('btnDetail');
			me.hideButton('btnCloseActivity');
			jQuery('#btnCancel').val(cancelString);
		} else {
			if (mode === 'edit') {
				me.showButton('btnSave');
				me.showButton('btnCancel');
				me.hideButton('btnEdit');
				me.showButton('btnDetail');
				me.hideButton('btnCloseActivity');
				jQuery('#btnCancel').val(cancelString);
			} else {
				me.hideButton('btnSave');
				me.showButton('btnDetail');
				
				var frame = window.frames['wdCalendar'];
				if (frame && frame.edit_permission == 'yes' && me.data[20] == 1) {
                	if ((me.data[17] == 'Held') || (me.data[17] == 'Completed')) {
                		me.hideButton('btnCloseActivity');
                	} else {
                		me.showButton('btnCloseActivity');
                	}
                } else {
                	me.hideButton('btnCloseActivity');
                }
				
				jQuery('#btnCancel').val(deleteString);
				if (frame && frame.delete_permission == 'yes' && me.data[21] == 1) { // crmv@120227
					me.showButton('btnCancel');
				} else {
					me.hideButton('btnCancel');
				}
				
				if (frame && frame.edit_permission == 'yes' && me.data[8] == 1) {
					me.showButton('btnEdit');
				} else {
					me.hideButton('btnEdit');
				}
			}
		}
	},
	
	init: function() {
		var me = this,
			popup = me.getPopup();
		
		if (!popup) return;
		
		popup.find('#header-tab').on('tabclick', function(e, params) {
			e.preventDefault();
			me.tabClicked(params);
		});
		
		popup.find('#btnSave').on('click', jQuery.proxy(me.saveClicked, me));
		popup.find('#btnCancel').on('click', jQuery.proxy(me.cancelClicked, me));
		popup.find('#btnEdit').on('click', jQuery.proxy(me.editClicked, me));
		popup.find('#btnDetail').on('click', jQuery.proxy(me.detailClicked, me));
		popup.find('#btnCloseActivity').on('click', jQuery.proxy(me.closeActivityClicked, me));
		popup.find('.closebutton').on('click', jQuery.proxy(me.closeButtonClick, me));
		
		jQuery(document).keyup(function(e) {
			e.preventDefault();
			if (e.keyCode === 27) popup.find('.closebutton').click(); // esc
		});
	},
	
	saveClicked: function() {
		var me = this;
		
		var form = jQuery('#header-tab-content>div.tab-pane.active .tab-container form');
		if (form.length > 0) form.submit();
	},
	
	cancelClicked: function() {
		var me = this,
			popup = me.getPopup();
		
		if (me.busy) return;
		if (!popup) return;
		
		if (!me.mode || me.mode == 'edit') {
			hideFloatingDiv('addEvent');
		} else {
			var frame = window.frames['wdCalendar'];
			if (!(frame && frame.delete_permission == 'yes' && me.data[21] == 1)) return; // crmv@120227
			vteconfirm(alert_arr.ARE_YOU_SURE, function(yes) {
				if (yes) {
					var url = "index.php?module=Calendar&action=CalendarAjax&file=wdCalendar&subfile=php/datafeed&method=remove";
					me.showBusy();
					jQuery.ajax({
						url: url,
						type: 'POST',
						async: true,
						data: {
							calendarId: me.record
						},
						success: function(data) {
							me.hideBusy();
							hideFloatingDiv('addEvent');
							window.frames['wdCalendar'].jQuery("#gridcontainer").reload();
						}
					});
				}
			});
		}
	},
	
	closeButtonClick: function() {
		var me = this,
			popup = me.getPopup();
		
		if (me.busy) return;
		if (!popup) return;
		
		hideFloatingDiv('addEvent');
	},
	
	editClicked: function() {
		var me = this;
		
		var frame = window.frames['wdCalendar'];
		if (!(frame && frame.edit_permission == 'yes' && me.data[8] == 1)) return;
		
		if (me.busy) return; // crmv@186401
		
		var params = {
			'mode': 'edit',
			'record': me.record
		};
		
		me.tabClicked(params);
	},
	
	detailClicked: function() {
		var me = this,
			record = me.record;
		
		if (!record) return;
		
		var action = 'DetailView';
		var module = 'Calendar';
		
		var url = "index.php?module="+module+"&action="+action+"&record="+record;
		location.href = url;
	},
	
	closeActivityClicked: function() {
		var me = this,
			popup = me.getPopup();
	
		if (!popup) return;
		if (me.data[8] != 1) return;
		
		var frame = window.frames['wdCalendar'];
		if (!(frame && frame.edit_permission == 'yes' && me.data[20] == 1)) return;
		
		if (me.busy) return; // crmv@186401
		
		me.showBusy();
		
		var activityMode = me.data[15];
		var evtStatus = null;
		if (activityMode == 'Task') {
			evtStatus = '&status=Completed';
    	} else {
        	evtStatus = '&eventstatus=Held';
        }
		
        // crmv@192033
        jQuery.ajax({
			url: 'index.php',
            method: 'POST',
        	data: "action=Save&module=Calendar&record="+me.record+"&change_status=true"+evtStatus,
            success: function(result) {
            	me.hideBusy();
            	hideFloatingDiv('addEvent');
        		window.frames['wdCalendar'].jQuery("#gridcontainer").reload();
            }
        });
		// crmv@192033e
	},
	
	tabClicked: function(params) {
		var me = this,
			popup = me.getPopup();
		
		if (!popup) return;
		
	    params = jQuery.extend({}, params || {});
	    me.data = params.data;
	    
	    var parentTab = me.getParentTab();
	    var tab = me.getActiveTab();
	    var tabName = me.getCurrentTabName();
	    var cont = me.getActiveContainer();
	    
	    me.showHideTab(params);
	    me.showHideButtons(params);
	    
	    var view = popup.find('input[name=pview]').val();
		var hour = popup.find('input[name=phour]').val();
		var day = popup.find('input[name=pday]').val();
		var month = popup.find('input[name=pmonth]').val();
		var year = popup.find('input[name=pyear]').val();
		var activityMode = tabName == 'event' ? 'Events' : 'Task';
		
		var record = params.record || '';
		me.record = record;
		
		var mode = params.mode || '';
		me.mode = mode;
		
		var ajaxparams = {};
		ajaxparams['view'] = view;
		ajaxparams['hour'] = hour;
		ajaxparams['day'] = day;
		ajaxparams['month'] = month;
		ajaxparams['year'] = year;
		ajaxparams['record'] = record;
		ajaxparams['ajaxCall'] = 'CalendarView';
		ajaxparams['activity_mode'] = activityMode;
		
		ajaxparams = jQuery.extend(ajaxparams, params.ajaxParams || {}); // crmv@194723
		
		cont.hide();
		popup.find('#event-tab .tab-container').empty();
		popup.find('#todo-tab .tab-container').empty();
	    
	    var action = mode === 'detail' ? 'DetailView' : 'EditView';
	    
		me.ajaxCall(action, ajaxparams, {}, function(response) {
		    cont.html(response);
		    cont.show();
		    
		    var showParams = {};
	    	showParams['showHeader'] = mode ? true : false;

		    me.showHideTab(jQuery.extend({}, showParams, params));
		    
		    if (mode !== 'detail') {
			    var activitytype = null;
			    
	    		if (tabName == 'event') {
	    			activitytype = 'event';
	    			var options = {
    					beforeSerialize: checkForm,
						success: params.successAddEvent || SuccessAddEvent, // crmv@194723
    				};
	    			jQuery('[name="EditView"]').ajaxForm(options);
	    		} else {
	    			activitytype = 'todo';
	    			var options_task = {
    					beforeSerialize: checkTaskForm,
						success: params.successAddTask || SuccessAddTask,
    				};
    				jQuery('[name="createTodo"]').ajaxForm(options_task);
	    		}
	    		
	    		var skipCreateAutoFill = params.skipCreateAutofill || false; // crmv@194723
	    		
	    		if (!mode && !skipCreateAutoFill) {
		    		var contents = jQuery("#wdCalendar").contents();
					var sd = new Date(contents.find("#bbit-cal-start").val());
					var sddate = params.startdate || wdCalendar.js2Php(sd, wdCalendar.crmv_date_format);
					var ed = new Date(contents.find("#bbit-cal-end").val());
					var eddate = params.enddate || wdCalendar.js2Php(ed, wdCalendar.crmv_date_format);
					var allday = params.is_all_day_event || contents.find("#bbit-cal-allday").val();
					var starthr = params.starthr || sd.getHours();
					var startmin = params.startmin || sd.getMinutes();
					var startfmt = params.startfmt || '';
					var endhr = params.endhr || ed.getHours();
					var endmin = params.endmin || ed.getMinutes();
					var endfmt = params.endfmt || '';
					var viewOption = params.viewOption || 'hourview';
					var subtab = params.subtab || 'event';
					var view_filter = params.view_filter || 'all';
					var calWhat = params.calWhat || '';
					var calDescription = params.calDescription || '';
					var calLocation = params.calLocation || '';

		    		activitytype = params.type || activitytype;
		    		gshow('addEvent', activitytype, sddate, eddate, starthr, startmin, startfmt, endhr, endmin, endfmt, viewOption, subtab, view_filter, allday, calWhat, calDescription, calLocation);
	    		}
	    	}
	    	jQuery(window).focus();
			
			me.registerPanelBlocker(); // crmv@171115
		});
	    
	},
	
	// crmv@171115
	registerPanelBlocker: function() {
		var me = this;
		
		var selectors = [
			'input[type="text"]',
			'textarea',
		];
		
		VTE.registerPanelBlocker('CalendarPopup', selectors);
	},
	// crmv@171115e
	
};

jQuery(document).ready(function() {
	CalendarPopup.init();
});

//crmv@17001
function checkForm(jqForm) {
	if(check_form() && formValidate(jqForm.context)) {
		VteJS_DialogBox.block();
	} else {
		return false;
	}
}

function SuccessAddEvent(response) {
	//crmv@158871
	if (response != '') response = jQuery.parseJSON(response);
	if (response['javascript'] != '' && response['javascript'] != null) eval(response['javascript']);
	//crmv@158871e
	VTE.releasePanelBlocker(); // crmv@171115
	hideFloatingDiv('addEvent');
	VteJS_DialogBox.unblock();
	window.frames['wdCalendar'].jQuery("#gridcontainer").reload();
}
//crmv@17001e

//crmv@20628 //crmv@sdk-18501 crmv@95751
function checkTaskForm(jqForm) {
	if(task_check_form() && formValidate(jqForm.context)) { 
		VteJS_DialogBox.block();
	} else { 
		return false; 
	}
}

function SuccessAddTask(response) {
	VTE.releasePanelBlocker(); // crmv@171115
	hideFloatingDiv('addEvent');
	VteJS_DialogBox.unblock();
	window.frames['wdCalendar'].jQuery("#gridcontainer").reload();
}
//crmv@20628e