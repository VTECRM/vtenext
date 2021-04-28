/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@28295 crmv@30009	crmv@67817
/* crmv@192033 */

function getEventList(obj) {
	initlializeCalendar();
}
function closeEvent(id,checked) {
	if (checked) {
		var status = 'Held';
	} else {
		var status = 'Not Held';
	}
	jQuery('#event_'+id).attr("disabled", true);
	jQuery('#event2_'+id).attr("disabled", true); // crmv@36871
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: "action=Save&module=Calendar&record="+id+"&change_status=true&eventstatus="+status+'&ajaxCalendar=closeEvent',
		success: function(result) {
				NotificationsCommon.drawChanges('EventCheckChangesDiv','EventCheckChangesImg',result,'Event');
				// crmv@36871
				jQuery('#events_list_row_'+id).fadeOut('fast', function() {this.hide();} );
				var container_id = jQuery('#events_list_row_'+id).parent().attr('id');
				if (jQuery('#'+container_id+' tr').length <= 2) {
					jQuery('#'+container_id+'_toggle').fadeOut('fast', function() {this.hide();} );
				}
				jQuery('#events2_list_row_'+id).fadeOut('fast', function() {this.hide();} );
				container_id = jQuery('#events2_list_row_'+id).parent().attr('id');
				if (jQuery('#'+container_id+' tr').length <= 1) {
					jQuery('#'+container_id+'_toggle').fadeOut('fast', function() {this.hide();} );
				}
				// crmv@36871e
			}
	});
}
function get_more_events() {
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: "module=SDK&action=SDKAjax&file=src/Events/GetEventList&mode=all",
		success: function(result) {
			jQuery('#events_button').hide();
	        jQuery('#events_div').height(jQuery('#events_list').height());
	        jQuery('#events_div').css('overflow-y','auto');
	        jQuery('#events_div').css('overflow-x','hidden');
	        jQuery('#events_div').html(result); // crmv@36871
		}
	});
}
function toggleEventPeriod(id) {
	var div = id;
	var img = '#'+id+'_img';
	if(getObj(div).style.display != "block"){
		getObj(div).style.display = "block";
        jQuery(img).attr("src", 'themes/images/close_details.png');
	}else{
		getObj(div).style.display = "none";
        jQuery(img).attr("src", 'themes/images/open_details.png');
	}
}
//crmv@28295e	//crmv@30009e
//crmv@36871
function eventShowByDate() {
	jQuery('#teventbtn_date').addClass('eventbtn_active');
	jQuery('#event_btn_duration').removeClass('eventbtn_active');
	jQuery('#divEvent_bydate').show();
	jQuery('#divEvent_byduration').hide();
	jQuery('#events_list').show();
	jQuery('#events_list_duration').hide();
}

function eventShowByDuration() {
	jQuery('#event_btn_duration').addClass('eventbtn_active');
	jQuery('#event_btn_date').removeClass('eventbtn_active');
	jQuery('#divEvent_bydate').hide();
	jQuery('#divEvent_byduration').show();
	jQuery('#events_list').hide();
	jQuery('#events_list_duration').show();
}
//crmv@36871e
function eventGetOccupation(year,month,day){
	occupation = getFile('index.php?module=SDK&action=SDKAjax&file=src/Events/GetOccupation&startdate='+year+"-"+month+"-"+day);
	occupation = eval('('+occupation+')');
	if (occupation['success'] == true){
		return occupation['result'];
	}
	return new Array();
}
function initlializeCalendar(){
	var days_count = new Object();
	jQuery('div#events #events_calendar').datepicker({
		dateFormat: 'dd/mm/yy',
		beforeShowDay: function(date) {
			if (date.getMonth()+1 == jQuery(this).datepicker("getDate").getMonth()+1){
				if (typeof days_count[jQuery(this).datepicker("getDate").getMonth()+1] == 'undefined'){
					jQuery('div#events #indicatorevents').show();
					days_count[jQuery(this).datepicker("getDate").getMonth()+1] = eventGetOccupation(jQuery(this).datepicker("getDate").getFullYear(),jQuery(this).datepicker("getDate").getMonth()+1,jQuery(this).datepicker("getDate").getDate());
					getEvents();
				}
				if (days_count[jQuery(this).datepicker("getDate").getMonth()+1][date.getDate()] == 1){
					return [true, 'redCircle', ''];
				}
				else{
		            return [true, '', ''];
		        }
	        }
	        else{
	        	return [true, '', ''];
			}
	    },
	    onChangeMonthYear:function(year,month,inst){
	    	today= new Date();
	    	var day = 1;
	    	if (today.getMonth()+1 == month){
	    		day = today.getDate();
			}
	    	jQuery(this).datepicker("setDate",day+'/'+month+'/'+year);
	    	jQuery('div#events #indicatorevents').show();
	    	eventGetOccupation(year, month,day);
	    	getEvents();
		},
		onSelect:function(date,inst){
	    	jQuery(this).datepicker("setDate",date);
	    	jQuery('div#events #indicatorevents').show();
	    	eventGetOccupation(jQuery(this).datepicker("getDate").getFullYear(),jQuery(this).datepicker("getDate").getMonth()+1,jQuery(this).datepicker("getDate").getDate());
	    	getEvents();		
		}
	});
}
function getEvents(){
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: "module=SDK&action=SDKAjax&file=src/Events/GetEventList&year="+jQuery('div#events #events_calendar').datepicker("getDate").getFullYear()+'&month='+(jQuery('div#events #events_calendar').datepicker("getDate").getMonth()+1)+'&day='+jQuery('div#events #events_calendar').datepicker("getDate").getDate(),
		success: function(result) {
			jQuery('div#events #indicatorevents').hide();	//crmv@32429
	        jQuery('#events_button').show();
	        jQuery('#events_list').html(result);
	        UpdateTitle();
	        jQuery('div#events #indicatorevents').hide();
		}
	});		
}
function UpdateTitle(){
	var selday = jQuery('div#events #events_calendar').datepicker("getDate");
	jQuery('div#events span#Events_Range_Title_from').html(selday.getDate());
	var lastday = new Date(selday.getFullYear(),selday.getMonth()+1,0);
	jQuery('div#events span#Events_Range_Title_to').html(lastday.getDate());
	jQuery('div#events div#Events_Range_Title').show();
}