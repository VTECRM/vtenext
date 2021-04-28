/*
*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************
*/
window.VTE = window.VTE || {};

// crmv@194723

VTE.CalendarView = VTE.CalendarView || {
	
	switchView: function(viewname) {
		var me = this;
		
		if (viewname === 'showresbtn') {
			me.showCalendarResources();
		} else {
			me.hideCalendarResources();
			jClickCalendar(viewname);
		}
		
		me.updateViewOptions(viewname);
	},
	
	updateViewOptions: function(viewname) {
		var selectedOption = jQuery('#' + viewname);
		var calendarViewButton = jQuery('.calendarViewButton');
		var calendarViewToggle = calendarViewButton.find('.dropdown-toggle');
		
		if (calendarViewToggle.length > 0) {
			calendarViewToggle.html(selectedOption.text() + ' <span class="caret"></span>');
		}
	},
	
	showCalendarResources: function() {
		jQuery("#wdCalendar").contents().find('#dvCalMain').hide();
		jQuery("#wdCalendar").contents().find('#td-calendar-users').hide();
		jQuery("#wdCalendar").get(0).contentWindow.loadCalendarResources();
		jQuery("#loadRolesModalContainer").show();
		jQuery("#geoCalendarContainer").hide();
	},
	
	hideCalendarResources: function() {
		jQuery("#wdCalendar").contents().find('#dvCalMain').show();
		jQuery("#wdCalendar").contents().find('#td-calendar-users').show();
		jQuery("#wdCalendar").get(0).contentWindow.clearCalendarResources();
		jQuery("#loadRolesModalContainer").hide();
		jQuery("#geoCalendarContainer").show();
	},
	
	calendarToList: function() {
		top.window.location.href = 'index.php?action=ListView&module=Calendar';
	},
	
	listToCalendar: function(activity_view) {
		top.window.location.href = 'index.php?action=index&module=Calendar&activity_view=' + activity_view;
	},
	
};

// crmv@194723e

function set_values(form) {

	if (form.duedate_flag.checked) {

		form.duedate_flag.value='on';

		form.duedate.value="";

		form.duetime.value="";

		form.duedate.readOnly=true;

		form.duetime.readOnly=true;

		document.images.jscal_trigger.width = 0;

		document.images.jscal_trigger.height = 0;

	}

	else {

		form.duedate_flag.value='off';

		form.duedate.readOnly=false;

		form.duetime.readOnly=false;

		if (form.duetime.readonly) alert (alert_arr.READONLY);

		document.images.jscal_trigger.width = 16;

		document.images.jscal_trigger.height = 16;

	}

}
function toggleTime()
{
	if(getObj("notime").checked)
	{
		getObj("notime").value = 'on';
		getObj("duration_hours").disabled = true;
		getObj("duration_minutes").disabled = true;
	}
	else
	{
		getObj("notime").value = 'off';
        getObj("duration_minutes").disabled = false;
		getObj("duration_hours").disabled = false;
	}
}

function showActivityView(selectactivity_view)
{
	//script to reload the page with the view type when the combo values are changed
	View_name = selectactivity_view.options[selectactivity_view.options.selectedIndex].value;
	document.frmOpenLstView.action = "index.php?module=Home&action=index&activity_view="+View_name;
	document.frmOpenLstView.submit();
}	

function exportCalendar(){
	var filename = jQuery('#ics_filename').val();
	if (!filename) return;
	
	VteJS_DialogBox.block();
    location.href = "index.php?module=Calendar&action=iCalExport&filename="+filename;
    VteJS_DialogBox.unblock();
	
	hideFloatingDiv('CalExport');
}

function importCalendar(){
	var file = document.getElementById('ics_file').value;
	if (file != '') {
		if (file.indexOf('.ics') != (file.length - 4)) {
			alert(alert_arr.PLS_SELECT_VALID_FILE+".ics")
		}
		else {
			document.ical_import.action.value='iCalImport';
			document.ical_import.module.value='Calendar';
			document.ical_import.submit();
		}
	}
}

//crmv@vte10usersFix
function listToCalendar(activity_view) {
	location.href = 'index.php?action=index&module=Calendar&activity_view=' + activity_view;
}

function jClickCalendar(showButton) {
	jQuery("#wdCalendar").contents().find('#jClickCalendar_'+showButton).click();
	//jQuery("#wdCalendar").contents().find('#' + showButton).click();
	//jQuery("#txtdatetimeshow_new").html(jQuery("#wdCalendar").contents().find('#txtdatetimeshow').html());
}

function reloadShownList(val) {
	if (val == 'selected') {
		response = getFile("index.php?module=Calendar&action=CalendarAjax&file=wdCalendar&subfile=ajaxRequests/ReloadShownList");
		jQuery("#wdCalendar").contents().find('#filterDivCalendar').html(response);
	}
}

function filterAssignedUser(val) {
	jQuery("#wdCalendar").contents().find("select#filter_view_Option").children().each(function () {
		if (jQuery(this).val() == val) {
			jQuery("#wdCalendar").contents().find("#filterClick_" + val).click();
		}
	});
}
//crmv@vte10usersFix e