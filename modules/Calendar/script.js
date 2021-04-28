/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@192033 */

function DisableSharing()
{

        x = document.SharedList.selected_id.length;
        idstring = "";
        xx = 0;
        if ( x == undefined)
        {

                if (document.SharedList.selected_id.checked)
                {
                        document.SharedList.idlist.value=document.SharedList.selected_id.value;
                }
                else
                {
                        alert(alert_arr.SELECT_ATLEAST_ONE_USER);
                        return false;
                }
        }
        else
        {
                for(i = 0; i < x ; i++)
                {
                        if(document.SharedList.selected_id[i].checked)
                        {
                                idstring = document.SharedList.selected_id[i].value +";"+idstring
                        xx++
                        }
                }
                if (xx != 0)
                {
                        document.SharedList.idlist.value=idstring;
                }
                else
                {
                        alert(alert_arr.SELECT_ATLEAST_ONE_USER);
                        return false;
                }
        }
        if(confirm(alert_arr.DISABLE_SHARING_CONFIRMATION+xx+alert_arr.USERS))
        {
                document.SharedList.action="index.php?module=Calendar&action=disable_sharing&return_module=Calendar&return_action=calendar_share";
        }
        else
        {
                return false;
        }
}

//crmv@69922
function showhideCalendar(argg)
{
	var x = getObj(argg).style;
	if (x.display == "none") {
		x.display = "block";
	}
	else {
		x.display = "none";
	}
}
//crmv@69922e

function showhideRepeat(argg1,argg2)
{
	var x=document.getElementById(argg2).style;
	var y=document.getElementById(argg1).checked;

	if (y)
	{
		x.display="block";
	}
	else {
		x.display="none";
	}

}

function selectedValue(field,label){
	if (field.options != undefined) {
		for(i=0;i<field.options.length;i++){
			if (field.options[i].value == label){
				field.selectedIndex = i;
				break;
			}
		}
	}
}

//crmv@17001
function savePartecipation(activityid,userid,partecipation) {
	var url = "module=Calendar&action=CalendarAjax&file=SavePartecipation&activityid="+activityid+"&userid="+userid+"&partecipation="+partecipation;
	jQuery("#loadingpannel").fadeIn(); //crmv@20324
	jQuery('#indicatorModNotifications').show(); // crmv@43194
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: url,
		success: function(result) {
			//crmv@20324
			jQuery("#loadingpannel").hide();
			jQuery('#indicatorModNotifications').hide(); // crmv@43194
			if (result.indexOf("Query Failed") >= 0) {
				jQuery("#errorpannel").fadeIn();
				jQuery("#errorpannel").delay(500).fadeOut();
			}
			else {
				jQuery("#savedpannel").fadeIn();
				jQuery("#savedpannel").delay(500).fadeOut();
			}
			//crmv@20324e
		}
	});
}

function setAllDayEvent(is_all_day_event) {
	if (is_all_day_event == 1) {
		document.EditView.is_all_day_event.checked = "checked";
		getObj('time_event_start').style.display = "none";
		getObj('time_event_end').style.display = "none";
	}
	else {
		document.EditView.is_all_day_event.checked = "";
		getObj('time_event_start').style.display = "block";
		getObj('time_event_end').style.display = "block";
	}
}

function presetAllDayEvent(val) {
	if (val == true) setAllDayEvent(1);
	else setAllDayEvent(0);
}
//crmv@17001e

function gshow(argg1,type,startdate,enddate,starthr,startmin,startfmt,endhr,endmin,endfmt,viewOption,subtab,view_filter,is_all_day_event,calWhat,calDescription,calLocation)	//crmv@17001 //crmv@20156
{
	smin = parseInt(startmin,10);
	smin = smin - (smin%5);
	//crmv@97607
	emin = parseInt(endmin,10);
	emin = emin - (emin%5);
	//crmv@97607e
	
	var y=document.getElementById(argg1).style;

	//crmv@26030m
	if (calWhat == undefined) {
		calWhat = '';
	}
	if (calDescription == undefined) {
		calDescription = '';
	}
	//crmv@26030m e

	//crmv@8398
	if(type != 'todo' && type!='') {
		//crmv@20602
		var calAss = '<input type="hidden" name="time_start" id="time_start"><input type="hidden" name="time_end" id="time_end">';
		document.getElementById('calAddEventPopup').innerHTML = calAss;
		//crmv@20602e

		//crmv@17001
		if (is_all_day_event && is_all_day_event != '')
			setAllDayEvent(is_all_day_event);
		//crmv@17001e

		selectedValue(document.EditView.activitytype,type);
		smin = _2digit(smin);
		emin = _2digit(emin); //crmv@97607
		//crmv@20156
		if (calLocation == undefined) {
			calLocation = '';
		}
		document.EditView.subject.value = calWhat;
		document.EditView.description.value = calDescription;
		document.EditView.location.value = calLocation;
		//crmv@20156e
		document.EditView.date_start.value = startdate;
		document.EditView.starthr.value = starthr;
		selectedValue(document.EditView.starthr,starthr);
		document.EditView.startmin.value = smin;
		selectedValue(document.EditView.startmin,smin);
		document.EditView.startfmt.value = startfmt;
		selectedValue(document.EditView.startfmt,startfmt);
		document.EditView.due_date.value = enddate;
		document.EditView.endhr.value = endhr;
		selectedValue(document.EditView.endhr,endhr);
		document.EditView.endmin.value = emin; //crmv@97607
		selectedValue(document.EditView.endmin,emin); //crmv@97607
		document.EditView.endfmt.value = endfmt;
		selectedValue(document.EditView.endfmt,endfmt);
		document.EditView.viewOption.value = viewOption;
		document.EditView.view_filter.value = view_filter;
        document.EditView.subtab.value = subtab;
		document.EditView.parentid.innerHTML = '';	//crmv@25614
		//crmv@26807
		//document.EditView.selectedusers.innerHTML = ''; //crmv@26171

		//crmv@26921
		jQuery('form[name="EditView"]').find('[name="parent_id"]').val(jQuery('#wdCalendar').contents().find('#parent_id_link').val());
		//crmv@29190
		if (jQuery('#wdCalendar').contents().find('#parent_id_link').val() != undefined && jQuery('#wdCalendar').contents().find('#parent_id_link').val() != 'undefined' && jQuery('#wdCalendar').contents().find('#parent_id_link').val() != '') {
			document.EditView.parent_name.value = jQuery('#wdCalendar').contents().find('#selectparent_link').val();
			disableReferenceField(document.EditView.parent_name);
		} else {
			resetReferenceField(document.EditView.parent_name);
		}
		//crmv@29190e
		jQuery('form[name="EditView"]').find('#selectedTable').html(jQuery('#wdCalendar').contents().find('#selectedTable').html());

		jQuery('form[name="EditView"]').find('[name="contactidlist"]').val(jQuery('#wdCalendar').contents().find('#parent_id_link_contacts').val());
		var optId = '';
		var optHtml = '';
		jQuery('#wdCalendar').contents().find('#contacts_div table tr td').each(function() {
			optId = jQuery(this).attr('id');
			optHtml = jQuery(this).html();
			jQuery('form[name="EditView"]').find('select[name="contactlist"]').append('<option value="' + optId + '">' + optHtml + '</option>');	//crmv@OPER6317
		});
		getObj('multi_contact_autocomplete').value = empty_search_str;	//crmv@29190

		jQuery('#wdCalendar').contents().find('#parent_id_link').val('');
		jQuery('#wdCalendar').contents().find('#selectparent_link').val('');	//crmv@29190
		jQuery('#wdCalendar').contents().find('#selectedTable').html('');
		jQuery('#wdCalendar').contents().find('#availableTable').html('');
		jQuery('#wdCalendar').contents().find('#bbit-cal-txtSearch').val(frames.wdCalendar.empty_search_str);	//crmv@29190
		jQuery('#wdCalendar').contents().find('#parent_id_link_contacts').val('');
		jQuery('#wdCalendar').contents().find('#contacts_div table').html('');

		//jQuery('#wdCalendar').contents().find("#bbit-cal-buddle").css("visibility", "hidden");
		//crmv@26921e

		jQuery('form[name="EditView"]').find('#availableTable tr').css('display','block');
		jQuery('form[name="EditView"]').find('#availableTable tr').find('input:checkbox').prop('checked', false);
		jQuery('form[name="EditView"]').find('#availableTable tr').css('background-color','');
		//crmv@26807e
		//calDuedatetime(type);
	}
//crmv@8398e
	if(type == 'todo') {
		// crmv@26030m
		document.createTodo.subject.value = calWhat;
		document.createTodo.description.value = calDescription;
		// crmv@26030me
		smin = _2digit(smin);
		starthr = _2digit(starthr);

		document.createTodo.date_start.value = startdate;
		document.createTodo.due_date.value = enddate;
		document.createTodo.starthr.value = starthr;
		document.createTodo.startmin.value = smin;
		document.createTodo.startfmt.value = startfmt;
		document.createTodo.viewOption.value = viewOption;
		document.createTodo.subtab.value = subtab;
	}
	if (y.display=="none")
    {
		y.display="block";
	}
}

function rptoptDisp(Opt){
	var currOpt = Opt.options[Opt.selectedIndex].value;
	if(currOpt == "Daily")
	{
		ghide('repeatWeekUI');
		ghide('repeatMonthUI');
	}
	else if(currOpt == "Weekly")
	{
		if(document.getElementById('repeatWeekUI').style.display == "none");
			document.getElementById('repeatWeekUI').style.display = "block";
		ghide('repeatMonthUI');
	}
	else if(currOpt == "Monthly")
	{
		ghide('repeatWeekUI');
		if(document.getElementById('repeatMonthUI').style.display == "none");
                        document.getElementById('repeatMonthUI').style.display = "block";
	}
	else if(currOpt == "Yearly")
	{
		ghide('repeatWeekUI');
                ghide('repeatMonthUI');
	}
}

function Taskshow(argg1,type,startdate,starthr,startmin,startfmt)
{
	var y=document.getElementById(argg1).style;
	if (y.display=="none")
        {
                document.EditView.date_start.value = startdate;
                document.EditView.starthr.value = starthr;
                document.EditView.startmin.value = startmin;
                document.EditView.startfmt.value = startfmt;
		y.display="block";
	}
}

function ghide(argg2) {
	//crmv@20628
	if (argg2 == 'addEvent' || argg2 == 'createTodo') {
		//crmv@31707 crmv@95751
		if (argg2 == 'addEvent' && document.EditView) {
			var assigned_user_id = document.EditView.assigned_user_id.value;
			var assigned_user_id_display = document.EditView.assigned_user_id_display.value;
			var assigned_user_id_display_css = document.EditView.assigned_user_id_display.className;
			if(typeof(document.EditView.assigned_group_id) != 'undefined'){ //crmv@32118
				var assigned_group_id = document.EditView.assigned_group_id.value;
				var assigned_group_id_display = document.EditView.assigned_group_id_display.value;
				var assigned_group_id_display_css = document.EditView.assigned_group_id_display.className;
			} //crmv@32118
		} else if (argg2 == 'createTodo' && document.createTodo) {
			var task_assigned_user_id = document.createTodo.task_assigned_user_id.value;
			var task_assigned_user_id_display = document.createTodo.task_assigned_user_id_display.value;
			var task_assigned_user_id_display_css = document.createTodo.task_assigned_user_id_display.className;
			if(typeof(document.createTodo.task_assigned_group_id) != 'undefined'){ //crmv@32118
				var task_assigned_group_id = document.createTodo.task_assigned_group_id.value;
				var task_assigned_group_id_display = document.createTodo.task_assigned_group_id_display.value;
				var task_assigned_group_id_display_css = document.createTodo.task_assigned_group_id_display.className;
			} //crmv@32118
		}
		jQuery("#"+argg2).clearFormCrmv();
		if (argg2 == 'addEvent' && document.EditView) {
			document.EditView.assigned_user_id.value = assigned_user_id;
			document.EditView.assigned_user_id_display.value = assigned_user_id_display;
			document.EditView.assigned_user_id_display.className = assigned_user_id_display_css;
			if(typeof(document.EditView.assigned_group_id) != 'undefined'){ //crmv@32118
				document.EditView.assigned_group_id.value = assigned_group_id;
				document.EditView.assigned_group_id_display.value = assigned_group_id_display;
				document.EditView.assigned_group_id_display.className = assigned_group_id_display_css;
			} //crmv@32118
		} else if (argg2 == 'createTodo' && document.createTodo) {
			document.createTodo.task_assigned_user_id.value = task_assigned_user_id;
			document.createTodo.task_assigned_user_id_display.value = task_assigned_user_id_display;
			document.createTodo.task_assigned_user_id_display.className = task_assigned_user_id_display_css;
			if(typeof(document.createTodo.task_assigned_group_id) != 'undefined'){ //crmv@32118
				document.createTodo.task_assigned_group_id.value = task_assigned_group_id;
				document.createTodo.task_assigned_group_id_display.value = task_assigned_group_id_display;
				document.createTodo.task_assigned_group_id_display.className = task_assigned_group_id_display_css;
			} //crmv@32118
		}
		//crmv@31707e crmv@95751e
	}
	//crmv@20628e
	//crmv@42752 crmv@20602
	jQuery('#'+argg2).hide();
	jQuery('#messageCalendarCont').hide();
	if (jQuery('#addEvent').css('display') != 'block') {
		jQuery('#calAddEventPopup').html('');
	}
	//crmv@42752e crmv@20602e
}

 function moveMe(arg1) {
	var posx = 0;
	var posy = 0;
	var e=document.getElementById(arg1);

	if (!e) var e = window.event;

	if (e.pageX || e.pageY)
	{
		posx = e.pageX;
		posy = e.pageY;
	}
	else if (e.clientX || e.clientY)
	{
		posx = e.clientX + document.body.scrollLeft;
		posy = e.clientY + document.body.scrollTop;
	}
 }

function switchClass(myModule,toStatus) {
	var x = document.getElementById(myModule);
	if (x) {
		if (toStatus=="on") {
			x.className="dvtSelectedCell";
		} else if (toStatus=="off") {
			x.className="dvtUnSelectedCell";
		}
	}
}

function enableCalstarttime()
{
	if(document.SharingForm.sttime_check.checked == true)
		document.SharingForm.start_hour.disabled = false;
	else
		document.SharingForm.start_hour.disabled = true;
}
/* crmv@26807 crmv@26961 crmv@59091 crmv@95751 */
function check_form()
{
	// crmv@103922
	var inviteesid_users = new Array();
	var inviteesid_contacts = new Array();
	
	function cleanArray(actual) {
	  var newArray = new Array();
	  for (var i = 0; i < actual.length; i++) {
	    if (actual[i]) {
	      newArray.push(actual[i]);
	    }
	  }
	  return newArray;
	}
	inviteesid_users = cleanArray(jQuery('#CalendarUsers_idlist').val().split('|'));
	inviteesid_contacts = cleanArray(jQuery('#CalendarContacts_idlist').val().split('|'));
	// crmv@103922e
	
	inviteesid_users = inviteesid_users.join(';');
	inviteesid_contacts = inviteesid_contacts.join(';');
	if (inviteesid_users == '') {
		inviteesid_users = '--none--';
	}
	if (inviteesid_contacts == '') {
		inviteesid_contacts = '--none--';
	}
	jQuery('#inviteesid').val(inviteesid_users);
	jQuery('#inviteesid_con').val(inviteesid_contacts);
	
	// crmv@105416 TODO: pass the validation to the standard functions, this script must disappear!!!
	var assignedToObj = getObj('assigned_user_id');
	var assignedTo = null;
	
	if (assignedToObj) {
		assignedTo = assignedToObj.value;
	}
	
	if (assignedTo == 0) {
		assignedToObj = getObj('assigned_group_id');
		if (assignedToObj) {
			assignedTo = assignedToObj.value;
		}
	}
	
	if (assignedTo == 0) {
		var label = null;
		for (var i = 0; i < fieldname.length; i++) {
			if (fieldname[i] == 'assigned_user_id') {
				label = fieldlabel[i];
				break;
			}
		}
		alert(sprintf(alert_arr.CANNOT_BE_NONE, label));
		return false;
	}
	// crmv@105416e
	
	if(trim(document.EditView.subject.value) == "")
	{
		alert(alert_arr.MISSING_EVENT_NAME);
		document.EditView.subject.focus()
		return false;
	}
	else
	{
		
		if(document.EditView.record.value != '')
		{
			document.EditView.mode.value = 'edit';
		}
		else
		{
			document.EditView.mode.value = 'create';
		}
		starthour = parseInt(document.EditView.starthr.value,10);
			startmin  = parseInt(document.EditView.startmin.value,10);
			startformat = document.EditView.startfmt.value;
			endhour = parseInt(document.EditView.endhr.value,10);
			endmin  = parseInt(document.EditView.endmin.value,10);
			endformat = document.EditView.endfmt.value;
		followupformat = document.EditView.followup_startfmt.value;
			followuphour = parseInt(document.EditView.followup_starthr.value,10);
			followupmin = parseInt(document.EditView.followup_startmin.value,10);
		if(startformat != '')
		{
			if(startformat == 'pm')
			{
				if(starthour == 12)
					starthour = 12;
				else
					starthour = starthour + 12;
			}
			else
			{
				if(starthour == 12)
									starthour = 0;
				else
					starthour = starthour;
			}
		}
		if(endformat != '')
		{
			if(endformat == 'pm')
						{
				if(endhour == 12)
										endhour = 12;
								else
										endhour = endhour + 12;
						}
			else
			{
				if(endhour == 12)
					endhour = 0;
				else
					endhour = endhour;
			}
		}
		//crmv@32334
		if (document.EditView.is_all_day_event.checked == true){
			starthour = 0;
			startmin = 0;
			endhour = 23;
			endmin = 59;
		}
		//crmv@32334 e
		var fieldnameDSI = fieldname.indexOf('date_start');
		if (fieldnameDSI != -1) var fieldnameDSL = fieldlabel[fieldnameDSI]; else var fieldnameDSL = 'Start date'; //crmv@63771
		var fieldnameEDI = fieldname.indexOf('due_date');
		if (fieldnameEDI != -1) var fieldnameEDL = fieldlabel[fieldnameEDI]; else var fieldnameEDL = 'End date'; //crmv@63771

		if(!dateValidate('date_start',fieldnameDSL,'OTH'))
		{
			return false;
		}
		if(!dateValidate('due_date',fieldnameEDL,'OTH'))
		{
			return false;
		}
		//crmv@32334
		/*
		if (document.EditView.is_all_day_event.checked == true)
			return true;
		*/
		//crmv@32334 e
		if(dateComparison('due_date',fieldnameEDL,'date_start',fieldnameDSL,'GE'))
		{
			var dateval1=getObj('date_start').value.replace(/^\s+/g, '').replace(/\s+$/g, '');
					var dateval2=getObj('due_date').value.replace(/^\s+/g, '').replace(/\s+$/g, '');
			var dateval3=getObj('followup_date').value.replace(/^\s+/g, '').replace(/\s+$/g, '');

					var dateelements1=splitDateVal(dateval1)
					var dateelements2=splitDateVal(dateval2)
			var dateelements3=splitDateVal(dateval3)

					dd1=dateelements1[0]
					mm1=dateelements1[1]
					yyyy1=dateelements1[2]

					dd2=dateelements2[0]
					mm2=dateelements2[1]
					yyyy2=dateelements2[2]

			dd3=dateelements3[0]
						mm3=dateelements3[1]
						yyyy3=dateelements3[2]

					var date1=new Date()
					var date2=new Date()
			var date3=new Date()

					date1.setYear(yyyy1)
					date1.setMonth(mm1-1)
					date1.setDate(dd1)

					date2.setYear(yyyy2)
					date2.setMonth(mm2-1)
					date2.setDate(dd2)

			date3.setYear(yyyy3)
						date3.setMonth(mm3-1)
						date3.setDate(dd3)

					if (date2<=date1)
					{
							if((endhour*60+endmin) <= (starthour*60+startmin))
						{
									alert(alert_arr.ENDTIME_GREATER_THAN_STARTTIME);
									document.EditView.endhr.focus();
									return false;
							}
				durationinmin = (endhour*60+endmin) - (starthour*60+startmin);
							if(durationinmin >= 60)
							{
									hour = Math.floor(durationinmin/60); // crmv@124729
									minute = durationinmin%60;
							}
							else
							{
									hour = 0;
									minute = durationinmin;
							}
				document.EditView.duration_hours.value = hour;
							document.EditView.duration_minutes.value = minute;

				}

						event_starthour = _2digit(starthour);
						event_startmin = _2digit(startmin);
						event_endhour = _2digit(endhour);
						event_endmin = _2digit(endmin);
						document.EditView.time_start.value = event_starthour+':'+event_startmin;
						document.EditView.time_end.value = event_endhour+':'+event_endmin;
			// Added for Aydin Kurt-Elli requirement START -by Minnie
						if (document.EditView.followup.checked == true && document.getElementById('date_table_thirdtd').style.display == 'block')
						{
								if(!dateValidate('followup_date','Followup Date','OTH'))
								{
										return false;
								}
								if(followupformat != '')
								{
										if(followupformat == 'pm')
										{
												if(followuphour == 12)
														followuphour = 12;
												else
														followuphour = followuphour + 12;
										}
										else
										{
												if(followuphour == 12)
														followuphour = 0;
												else
														followuphour = followuphour;
										}
								}

				if ( compareDates(date3,'Followup Date',date2,fieldnameEDL,'GE'))
								{
										if (date3 <= date2)
										{
												if((followuphour*60+followupmin) <= (endhour*60+endmin))
												{
														alert(alert_arr.FOLLOWUPTIME_GREATER_THAN_STARTTIME);
														document.EditView.followup_starthr.focus();
														return false;
												}
										}
								}
								else return false;
							//modified to set followup end date depends on the event or todo. If it is Event, the difference between followup start date and end date is 1hr. If it is todo then difference is 5mins.
								date3.setMinutes(followupmin);
								date3.setHours(followuphour);
								if(document.EditView.activitytype[0].checked == true)
								{
										date3.setMinutes(parseInt(date3.getMinutes(),10)+5);
								}
								if(document.EditView.activitytype[1].checked == true)
								{
										date3.setMinutes(parseInt(date3.getMinutes(),10)+60);
								}
				var tempdate = getdispDate(date3);

				followuphour = _2digit(followuphour);
					followupmin = _2digit(followupmin);
				followupendhour = _2digit(date3.getHours());
					followupendmin = _2digit(date3.getMinutes());
					document.EditView.followup_due_date.value = tempdate;
								document.EditView.followup_time_start.value = followuphour+':'+followupmin;
								document.EditView.followup_time_end.value = followupendhour+':'+followupendmin;
				//end
						}
						// Added for Aydin Kurt-Elli requirement END -by Minnie -->

			//added to avoid db error while giving characters in the repeat "every n no of day in month" text box
						if((getObj("recurringcheck")) && (document.EditView.recurringcheck.checked == true) && (document.EditView.recurringtype.value =="Monthly"))
						{
				if((document.EditView.repeatMonth[0].checked == true) && ((parseInt(parseFloat(document.EditView.repeatMonth_date.value))!=document.EditView.repeatMonth_date.value) || document.EditView.repeatMonth_date.value=='' || parseInt(document.EditView.repeatMonth_date.value)>'31' || document.EditView.repeatMonth_date.value<='0'))
								{
										alert(alert_arr.INVALID +' "'+document.EditView.repeatMonth_date.value+'" ');
										document.EditView.repeatMonth_date.focus();
										return false;
								}
						}
						//end


						//added to check Start Date & Time,if Activity Status is Planned.//start
						if(document.EditView.eventstatus.value == "Planned")
						{
								var chkdate=new Date()
				chkdate.setMinutes(event_startmin)
				chkdate.setHours(event_starthour)
								chkdate.setYear(yyyy1)
								chkdate.setMonth(mm1-1)
								chkdate.setDate(dd1)
						}

		} else {
			return false;
		}
//		if(getObj("recurringcheck") && document.EditView.recurringcheck.checked == false)
//                {
//                        document.EditView.recurringtype.value = '--None--';
//                }
			//crmv@sdk-18501	//crmv@sdk-26260
		sdkValidate = SDKValidate();
		if (sdkValidate) {
			sdkValidateResponse = eval('('+sdkValidate.responseText+')');
			// crmv@160581
			if (sdkValidateResponse) {
				if (!sdkValidateResponse['status']) {
					return false;
				}
			} else {
				vtealert(alert_arr.LBL_REQ_FAILED_NO_CONNECTION);
				return false;
			}
			// crmv@160581e
		}
		//crmv@sdk-18501e	crmv@sdk-26260e
	}
	
	// check custom fields
	if (window.fieldnameCustom && fieldnameCustom.length > 0) {
		for (var i=0; i<fieldnameCustom.length; ++i) {
			var fname = fieldnameCustom[i],
				flabel = fieldlabelCustom[i],
				datatype = fielddatatypeCustom[i],
				fuitype = fielduitypeCustom[i];
				
			var fieldobj = getObj(fname);
			if (!checkCustomField(fieldobj, fname, flabel, datatype, fuitype)) {
				return false;
			}
		}
	}
	
	return true;
}

function task_check_form() {
	var starthour = parseInt(document.createTodo.starthr.value,10);
	var startmin  = parseInt(document.createTodo.startmin.value,10);
	var startformat = document.createTodo.startfmt.value;
	
	if(startformat != '') {
		if(startformat == 'pm') {
			if(starthour == 12)
				starthour = 12;
			else
				starthour = starthour + 12;
		} else {
			if(starthour == 12)
				starthour = 0;
			else
				starthour = starthour;
		}
	}
	starthour = _2digit(starthour);
	startmin = _2digit(startmin);
	document.createTodo.time_start.value = starthour+':'+startmin;
	
	if(document.createTodo.record.value != '') {
		document.createTodo.mode.value = 'edit';
	} else {
		document.createTodo.mode.value = 'create';
	}

	// check custom fields
	if (window.fieldnameTaskCustom && fieldnameTaskCustom.length > 0) {
		for (var i=0; i<fieldnameTaskCustom.length; ++i) {
			var fname = fieldnameTaskCustom[i],
				flabel = fieldlabelTaskCustom[i],
				datatype = fielddatatypeTaskCustom[i],
				fuitype = fielduitypeTaskCustom[i];
				
			var fieldobj = getObj(fname);
			if (!checkCustomField(fieldobj, fname, flabel, datatype, fuitype)) {
				return false;
			}
		}
	}
	
	return true;
}


function maintask_check_form() {
	var starthour = parseInt(document.EditView.starthr.value,10);
	var startmin  = parseInt(document.EditView.startmin.value,10);
	var startformat = document.EditView.startfmt.value;
	
	if(startformat != '') {
		if(startformat == 'pm') {
			if(starthour == 12)
				starthour = 12;
			else
				starthour = starthour + 12;
		} else {
			if(starthour == 12)
				starthour = 0;
			else
				starthour = starthour;
		}
	}
	
	starthour = _2digit(starthour);
	startmin = _2digit(startmin);
	document.EditView.time_start.value = starthour+':'+startmin;
	
	// crmv@105416 TODO: pass the validation to the standard functions, this script must disappear!!!
	var assignedToObj = getObj('assigned_user_id');
	var assignedTo = null;
	
	if (assignedToObj) {
		assignedTo = assignedToObj.value;
	}
	
	if (assignedTo == 0) {
		assignedToObj = getObj('assigned_group_id');
		if (assignedToObj) {
			assignedTo = assignedToObj.value;
		}
	}
	
	if (assignedTo == 0) {
		var label = null;
		for (var i = 0; i < fieldname.length; i++) {
			if (fieldname[i] == 'assigned_user_id') {
				label = fieldlabel[i];
				break;
			}
		}
		alert(sprintf(alert_arr.CANNOT_BE_NONE, label));
		return false;
	}
	// crmv@105416e
		
	// check custom fields
	if (window.fieldnameTaskCustom && fieldnameTaskCustom.length > 0) {
		for (var i=0; i<fieldnameTaskCustom.length; ++i) {
			var fname = fieldnameTaskCustom[i],
				flabel = fieldlabelTaskCustom[i],
				datatype = fielddatatypeTaskCustom[i],
				fuitype = fielduitypeTaskCustom[i];
				
			var fieldobj = getObj(fname);
			if (!checkCustomField(fieldobj, fname, flabel, datatype, fuitype)) {
				return false;
			}
		}
	}
	
	return true;
}

function checkCustomField(fieldobj, fname, flabel, datatype, fuitype) {
	var type = datatype.split("~");
	
	// field not found, don't check
	if (!fieldobj) return true;
			
	if (type[1]=="M") {
		if (!emptyCheck(fname,flabel,fieldobj.type)) {
			return false;
		}
	}
	
    switch (type[0]) {
        case "O"  : break;
        case "V"  :
        	//crmv@add textlength check
        	if (type[2] && type[3]){
				if (!lengthComparison(fname,flabel,type[2],type[3])) {
	    			return false;
				}
        	};
        	//crmv@add textlength check end
        	break;
        case "C"  : break;
        case "DT" :
			if (fieldobj.value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0) {
				if (type[1]=="M")
					if (!emptyCheck(fname,flabel,fieldobj.type)) // crmv@77878
						return false;

					if (typeof(type[3])=="undefined") 
						var currdatechk="OTH";
					else 
						var currdatechk=type[3];

					if (!dateTimeValidate(fname,type[2],flabel,currdatechk))
						return false;
					
					if (type[4]) {
						if (!dateTimeComparison(fname,type[2],flabel,type[5],type[6],type[4]))
							return false;
					}
			}
			break;
		case "D"  :
			if (fieldobj.value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0) {
				if(typeof(type[2])=="undefined")
					var currdatechk="OTH";
				else
					var currdatechk=type[2];
				if (!dateValidate(fname,flabel,currdatechk))
					return false;
				if (type[3]) {
					var otherFieldIdx = fieldnameCustom.indexOf(type[4]);
					if (otherFieldIdx != -1) var otherFieldLabel = fieldlabelCustom[otherFieldIdx]; else var otherFieldLabel = type[5];
					if (!dateComparison(fname,flabel,type[4],otherFieldLabel,type[3]))
						return false;
				}
			}
			break;
		case "T"  :
			if (fieldobj.value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0) {
				if(typeof(type[2])=="undefined") var currtimechk="OTH";
				else var currtimechk=type[2];

				if (!timeValidate(fname,flabel,currtimechk))
					return false;
				if (type[3]) {
					var otherFieldIdx = fieldnameCustom.indexOf(type[4]);
					if (otherFieldIdx != -1) var otherFieldLabel = fieldlabelCustom[otherFieldIdx]; else var otherFieldLabel = type[5];
					if (!timeComparison(fname,flabel,type[4],otherFieldLabel,type[3]))
						return false;
				}
			}
			break;
		case "I"  :
			if (fieldobj.value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
			{
				if (fieldobj.value.length!=0)
				{
					if (!intValidate(fname,flabel,fuitype)) // crmv@83877
						return false;
					if (type[2]) {
						if (!numConstComp(fname,flabel,type[2],type[3]))
							return false;
					}
				}
			}
			break;
		case "N"  :
            case "NN" :
			if (fieldobj.value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
            {
				// crmv@83877
				if (fieldobj.value.length!=0) {
                    if (typeof(type[2])=="undefined")
						var numformat="any";
                    else
						var numformat=type[2];
                    if (type[0]=="NN") {
						if (!numValidate(fname,flabel,numformat,true, fuitype)) {
							return false;
						}
					} else if (!numValidate(fname,flabel,numformat,false, fuitype)) {
                        return false;
					}
                    if (type[3]) {
						if (!numConstComp(fname,flabel,type[3],type[4]))
                            return false;
                    }
                }
                // crmv@83877e
            }
			break;
        case "E"  :
			if (fieldobj.value.replace(/^\s+/g, '').replace(/\s+$/g, '').length!=0)
            {
				if (fieldobj.value.length!=0)
                {
                    var etype = "EMAIL"
					if (!patternValidate(fname,flabel,etype))
                        return false;
                }
            }
			break;
	}
	
	return true;
}
// crmv@95751

var moveupLinkObj,moveupDisabledObj,movedownLinkObj,movedownDisabledObj;

function userEventSharing(selectedusrid,selcolid)
{
        formSelectColumnString(selectedusrid,selcolid);
}

// crmv@187823
function alignCalSharing() {
	var shareList = jQuery('#available_users_sharing');
	var shareOccList = jQuery('#available_users_sharing_occ');
	var shareSelected = jQuery('#selected_users_sharing>option').map(function() { return this.value; });
	var shareOccSelected = jQuery('#selected_users_sharing_occ>option').map(function() { return this.value; });
	
	// disable users in the other lists
	for (var i=0; i<shareSelected.length; ++i) {
		var uid = shareSelected[i];
		shareOccList.find('option[value='+uid+']').prop('disabled', true);
	}
	for (var i=0; i<shareOccSelected.length; ++i) {
		var uid = shareOccSelected[i];
		shareList.find('option[value='+uid+']').prop('disabled', true);
	}
}

function incUserAndAlign(avail_users,sel_users) {
	var added = jQuery('#'+avail_users).val() || [];
	
	incUser(avail_users,sel_users);
	
	var target = jQuery('#' + (avail_users == 'available_users_sharing' ? 'available_users_sharing_occ' : 'available_users_sharing'));
	for (var i=0; i<added.length; ++i) {
		var uid = added[i];
		target.find('option[value='+uid+']').prop('disabled', true).prop('selected', false);
	}
}

function rmvUserAndAlign(sel_users) {
	var removed = jQuery('#'+sel_users).val() || [];
	rmvUser(sel_users);
	
	var target = jQuery('#' + (sel_users == 'selected_users_sharing' ? 'available_users_sharing_occ' : 'available_users_sharing'));
	for (var i=0; i<removed.length; ++i) {
		var uid = removed[i];
		target.find('option[value='+uid+']').prop('disabled', false);
	}
}
// crmv@187823e

function incUser(avail_users,sel_users)
{
	availListObj=getObj(avail_users)
        selectedColumnsObj=getObj(sel_users)
        var selectlength=selectedColumnsObj.length
        var availlength=availListObj.length

	for (i=0;i<selectedColumnsObj.length;i++)
	{
		selectedColumnsObj.options[i].selected=false
	}
	for (i=0;i<availListObj.length;i++)
	{
		if (availListObj.options[i].selected==true && !availListObj.options[i].disabled) // crmv@187823
		{
			var rowFound = false;
			var existingObj = null;
			for (j=0;j<selectedColumnsObj.length;j++)
			{
				if (selectedColumnsObj.options[j].value==availListObj.options[i].value)
				{
					rowFound=true;
					existingObj=selectedColumnsObj.options[j]
					break
				}
			}
			if (rowFound!=true)
			{
				var newColObj=document.createElement("OPTION")
					newColObj.value=availListObj.options[i].value
					if (browser_ie) newColObj.innerText=availListObj.options[i].innerText
					else if (browser_nn4 || browser_nn6) newColObj.text=availListObj.options[i].text
						selectedColumnsObj.appendChild(newColObj)
							availListObj.options[i].selected=false
							newColObj.selected=true
							rowFound=false
			}
			else
			{
				if (existingObj != null) existingObj.selected=true
			}
		}
	}
}

function rmvUser(sel_users)
{
	selectedColumnsObj=getObj(sel_users)
        var selectlength=selectedColumnsObj.options.length
	for(i = 0; i <= selectlength; i++)
	{
		if(selectedColumnsObj.options.selectedIndex >= 0)
		selectedColumnsObj.remove(selectedColumnsObj.options.selectedIndex)
	}

}


// function to delete activity related contact in calendar
var del_ids = new Array();
function removeActContacts()
{
	var avail_contacts = getObj('parentid');
	// this block is to remove contacts and get deleted contact ids
	if(avail_contacts.options.selectedIndex > -1)
	{
		for(m = 0; m < avail_contacts.options.length; m++)
		{
			if(avail_contacts.options[m].selected == true)
			{
				del_ids.push(avail_contacts.options[m].value);
				avail_contacts.options[m] = null;
				removeActContacts();
			}
		}
	}
	document.EditView.deletecntlist.value = del_ids.join(";");

	// this block is to get available id list
	var avail_ids = new Array();
	for(n=0; n<avail_contacts.options.length;n++)
	{
		avail_ids.push(avail_contacts.options[n].value);
	}
	document.EditView.contactidlist.value = avail_ids.join(";");

}
//end
function formSelectColumnString(usr,col)
{

	var selectedColumnsObj=getObj(col)
	usr_id = document.getElementById(usr);
	var selectedColStr = "";
        for (i=0;i<selectedColumnsObj.options.length;i++)
        {
        	selectedColStr += selectedColumnsObj.options[i].value + ";";
        }
	usr_id.value = selectedColStr;
}

function fnRedirect() {
 	var OptionData = jQuery('#view_Option').val();
	if(OptionData == 'listview')
	{
		document.EventViewOption.action.value = "index";
		window.document.EventViewOption.submit();
	}
	if(OptionData == 'hourview')
	{
		document.EventViewOption.action.value = "index";
		window.document.EventViewOption.submit();
	}
}

//crmv@vte10usersFix
function monthToNum(str)
{
	switch(str)
	{
		case 'Gen': str = 1; break;
		case 'Feb': str = 2; break;
		case 'Mar': str = 3; break;
		case 'Apr': str = 4; break;
		case 'Mag': str = 5; break;
		case 'Giu': str = 6; break;
		case 'Lug': str = 7; break;
		case 'Ago': str = 8; break;
		case 'Set': str = 9; break;
		case 'Ott': str = 10; break;
		case 'Nov': str = 11; break;
		case 'Dic': str = 12; break;

		case 'Jan': str = 1; break;
		case 'Feb': str = 2; break;
		case 'Mar': str = 3; break;
		case 'Apr': str = 4; break;
		case 'May': str = 5; break;
		case 'Jun': str = 6; break;
		case 'Jul': str = 7; break;
		case 'Aug': str = 8; break;
		case 'Sep': str = 9; break;
		case 'Oct': str = 10; break;
		case 'Nov': str = 11; break;
		case 'Dec': str = 12; break;
	}
	return str;
}
//crmv@vte10usersFix e

//crmv@8398
function fnAddEvent(obj,CurrObj,start_date,end_date,start_hr,start_min,start_fmt,end_hr,end_min,end_fmt,viewOption,subtab,eventlist,view_filter,offsetTop,date_format){//crmv@23696 //crmv@vte10usersFix
	var str = jQuery("#wdCalendar").contents().find("#txtdatetimeshow_hidden").val(); // crmv@98866
	if (!str) return; // crmv@98866
	
	//crmv@20480
	var tagName = document.getElementById(CurrObj);
	var left_Side = findPosX(obj);
	var top_Side = findPosY(obj);
	tagName.style.left= left_Side  + 'px';
	//crmv@22622
	if (typeof offsetTop == 'undefined') {
		var offsetTop = 0;
	}
	top_Side = top_Side + 28 + offsetTop;
	tagName.style.top= top_Side + 'px'; //crmv@20253 //crmv@22259
	//crmv@22622
	//crmv@20480e
	tagName.style.display = 'block';
	eventlist = eventlist.split(";");
	//ds@47
	//for(var i=0;i<(eventlist.length-1);i++){ why calc -1 ????

	//crmv@vte10usersFix
	var currentDate = new Date();

	str = str.split('-'); // crmv@98866
  	var start = str[0].split(' ');
  	start[1] = monthToNum(start[1]).toString();//crmv@25413
  	var startDate = new Date(start[2],start[1]-1,start[0]);

	date_format = date_format.replace('yyyy','Y');
	date_format = date_format.replace('mm','m');
	date_format = date_format.replace('dd','d');

	if (currentDate < startDate) {
		//crmv@25413
		if (start[0].length < 2) {
			start[0] = '0' + start[0];
		}
		if (start[1].length < 2) {
			start[1] = '0' + start[1];
		}
		//crmv@25413e
		if (date_format == 'd-m-Y') {
			start_date = start[0] + '-' + start[1] + '-' + start[2];
		}
		else if (date_format == 'm-d-Y') {
			start_date = start[1] + '-' + start[0] + '-' + start[2];
		}
		else if (date_format == 'Y-m-d') {
			start_date = start[2] + '-' + start[1] + '-' + start[0];
		}
		end_date = start_date;
	}
	//crmv@vte10usersFix e

	// crmv@98866
	var params = {
		startdate : start_date,
		enddate : end_date,
		starthr : start_hr,
		startmin : start_min,
		startfmt : start_fmt,
		endhr : end_hr,
		endmin : end_min,
		endfmt : end_fmt,
		viewOption : viewOption,
		subtab : subtab,
		view_filter : view_filter,
		is_all_day_event : '0',
		calWhat : '',
		calDescription : '',
		calLocation : '',
		forceLoad : true,
	};

	for (var i = 0; i < (eventlist.length); i++) {
		// ds@47
		var eventname = eventlist[i];
		var eventnamel = eventname.toLowerCase().replace('/', '\\/').replace(/ /g, "_"); // crmv@106578 crmv@123806

		jQuery('#add' + eventnamel).attr('href', 'javascript:void(0)');
		jQuery('#add' + eventnamel).off('click');
		
		var paramsE = jQuery.extend(true, {}, params);
		paramsE['argg1'] = 'addEvent';
		paramsE['type'] = eventname;
		paramsE['disableTodo'] = true;
		paramsE['disableEvent'] = false;
		
		jQuery('#add' + eventnamel).click(quickEventClickCallback(paramsE));
	}

	jQuery('#addtodo').attr('href', 'javascript:void(0)');
	jQuery('#addtodo').off('click');
	
	var paramsT = jQuery.extend(true, {}, params);
	paramsT['argg1'] = 'createTodo';
	paramsT['type'] = 'todo';
	paramsT['disableTodo'] = false;
	paramsT['disableEvent'] = true;
	
	jQuery('#addtodo').click(quickEventClickCallback(paramsT));
	// crmv@98866 end
}
//crmv@8398e

// crmv@98866
function quickEventClickCallback(params) {
	return function(e) {
		e.preventDefault();
		var tabType = params['type'] == 'todo' ? 'todo-tab' : 'event-tab';
		parent.jQuery('#addEvent').show();
		parent.jQuery('#addEvent').css('visibility', 'visible'); // crmv@167543
		parent.jQuery('a[href="#' + tabType + '"]').trigger('click.tab.data-api', params);
		fnRemoveEvent();
	}
}
// crmv@98866 e

//crmv@8398e
function fnRemoveEvent(){
	var tagName = document.getElementById('addEventDropDown').style.display= 'none';
}

function fnShowEvent(){
		var tagName = document.getElementById('addEventDropDown').style.display= 'block';
}

// crmv@180714 - removed code

function getCalSettings(url){
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'module=Calendar&action=ActivityAjax&'+url+'&type=settings&ajax=true',
		success: function(result) {
			jQuery("#calSettings").html(result);
		}
	});
}

function updateStatus(record,status,view,hour,day,month,year,type){
	if (type == 'event') {
		
		var OptionData = jQuery('#view_Option').val();
		var view_filter = jQuery('#filter_view_Option').val();
		
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Calendar&action=ActivityAjax&record='+record+'&'+status+'&view='+view+'&hour='+hour+'&day='+day+'&month='+month+'&year='+year+'&type=change_status&viewOption='+OptionData+'&subtab=event&ajax=true&view_filter='+view_filter,
			success: function(result) {
				if(OptionData == 'listview')
				{
					result = result.split('####');
					jQuery("#total_activities").html(result[1]);
					jQuery("#listView").html(result[0]);
				}
				if(OptionData == 'hourview')
				{
					result = result.split('####');
					jQuery("#total_activities").html(result[1]);
					jQuery("#hrView").html(result[0]);
				}
			}
		});
		
	} else if (type == 'todo') {
		
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Calendar&action=ActivityAjax&record='+record+'&'+status+'&view='+view+'&hour='+hour+'&day='+day+'&month='+month+'&year='+year+'&type=change_status&subtab=todo&ajax=true',
			success: function(result) {
				result = result.split('####');
				jQuery("#total_activities").html(result[1]);
				jQuery("#mnuTab2").html(result[0]);
			}
		});
	}
}

function cal_navigation(type,urlstring,start)
{
	var url = urlstring;
	jQuery('#status').show();
	
	if (type == 'event') {
		
		var OptionData = jQuery('#view_Option').val();
		var view_filter = jQuery('#filter_view_Option').val();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Calendar&action=CalendarAjax&file=ActivityAjax&ajax=true&n_type=nav&viewOption='+OptionData+url+start+'&subtab='+type+'&view_filter='+view_filter,
			success: function(result) {
				if(OptionData == 'listview') {
					result = result.split('####');
					jQuery("#total_activities").html(result[1]);
					jQuery("#listView").html(result[0]);
					jQuery('#status').hide();
				}
				if(OptionData == 'hourview') {
					result = result.split('####');
					jQuery("#total_activities").html(result[1]);
					jQuery("#hrView").html(result[0]);
					jQuery('#status').hide();
				}
			}
		});
		
	} else if (type == 'todo') {
		
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Calendar&action=CalendarAjax&file=ActivityAjax&ajax=true&n_type=nav'+url+start+'&subtab=todo',
			success: function(result) {
				result = result.split('####');
				jQuery("#total_activities").html(result[1]);
				jQuery("#mnuTab2").html(result[0]);
				jQuery('#status').hide();
			}
		});
	}
}
//crmv@7381

function getcalAction(obj,Lay,id,view,hour,dateVal,type,del){
    var tagName = document.getElementById(Lay);
    var leftSide = findPosX(obj);
    var topSide = findPosY(obj);
    var maxW = tagName.style.width;
    var widthM = maxW.substring(0,maxW.length-2);
    var getVal = eval(leftSide) + eval(widthM);
    var vtDate = dateVal.split("-");
    var day = parseInt(vtDate[2],10);
    var month = parseInt(vtDate[1],10);
    var year = parseInt(vtDate[0],10);
    if(getVal  > window.innerWidth ){
        leftSide = eval(leftSide) - eval(widthM);
        tagName.style.left = leftSide + 'px';
    }
    else
        tagName.style.left= leftSide + 'px';
    tagName.style.top= topSide + 'px';
    tagName.style.display = 'block';
    tagName.style.visibility = "visible";
    if(type == 'event')
    {
	var heldstatus = "eventstatus=Held";
	var notheldstatus = "eventstatus=Not Held";
        var activity_mode = "Events";
	var complete = document.getElementById("complete");
	var pending = document.getElementById("pending");
	var postpone = document.getElementById("postpone");
	var actdelete =	document.getElementById("actdelete");
	var changeowner = document.getElementById("changeowner");
	var OptionData = document.getElementById('view_Option').options[document.getElementById('view_Option').selectedIndex].value;
	var view_filter = document.getElementById('filter_view_Option').options[document.getElementById('filter_view_Option').selectedIndex].value;

    }
    if(type == 'todo')
    {
	var heldstatus = "status=Completed";
    var notheldstatus = "status=Deferred";
	//ds@45
	var notstartedstatus ="status=Not Started";
	var inprogressstatus ="status=In Progress";
	var pendinginputstatus ="status=Pending Input";
	var plannedstatus = "status=Planned";
	//ds@45e

	var activity_mode = "Task";
	var complete = document.getElementById("taskcomplete");
    var pending = document.getElementById("taskpending");
	//ds@45
	var notstarted = document.getElementById("tasknotstarted");
	var inprogress = document.getElementById("taskinprogress");
	var pendinginput = document.getElementById("taskpendinginput");
	var planned = document.getElementById("taskplanned");
	//ds@45e
        var postpone = document.getElementById("taskpostpone");
        var actdelete = document.getElementById("taskactdelete");
        var changeowner = document.getElementById("taskchangeowner");
	var OptionData = '';
	var view_filter = '';
    }
    document.getElementById("idlist").value = id;
    document.change_owner.hour.value = hour;
    document.change_owner.day.value = day;
    document.change_owner.view.value = view;
    document.change_owner.month.value = month;
    document.change_owner.year.value = year;
    document.change_owner.subtab.value = type;
    if(complete) complete.href="javascript:updateStatus("+id+",'"+heldstatus+"','"+view+"',"+hour+","+day+","+month+","+year+",'"+type+"')";
    if(pending) pending.href="javascript:updateStatus("+id+",'"+notheldstatus+"','"+view+"',"+hour+","+day+","+month+","+year+",'"+type+"')";
	//ds@45
	if(notstarted) notstarted.href="javascript:updateStatus("+id+",'"+notstartedstatus+"','"+view+"',"+hour+","+day+","+month+","+year+",'"+type+"')";
	if(inprogress) inprogress.href="javascript:updateStatus("+id+",'"+inprogressstatus+"','"+view+"',"+hour+","+day+","+month+","+year+",'"+type+"')";
	if(pendinginput) pendinginput.href="javascript:updateStatus("+id+",'"+pendinginputstatus+"','"+view+"',"+hour+","+day+","+month+","+year+",'"+type+"')";
	if(planned) planned.href="javascript:updateStatus("+id+",'"+plannedstatus+"','"+view+"',"+hour+","+day+","+month+","+year+",'"+type+"')";
	//ds@45e
    if(postpone) postpone.href="index.php?module=Calendar&action=EditView&record="+id+"&return_action=index&activity_mode="+activity_mode+"&view="+view+"&hour="+hour+"&day="+day+"&month="+month+"&year="+year+"&viewOption="+OptionData+"&subtab="+type+"&maintab=Calendar&view_filter="+view_filter;

    if(actdelete) actdelete.href="javascript:delActivity("+id+",'"+view+"',"+hour+","+day+","+month+","+year+",'"+type+"')";

    if(changeowner) changeowner.href="javascript:dispLayer('act_changeowner');";

 if (del == "no") hideAction(actdelete);
 else displayAction(actdelete);
}

function hideAction(Lay){
        Lay.style.visibility = 'hidden';
        Lay.style.display = 'none';
}
function displayAction(Lay){
        Lay.style.visibility = 'visible';
        Lay.style.display = 'block';
}
function dispLayer(lay)
{
	var tagName = document.getElementById(lay);
        tagName.style.visibility = 'visible';
        tagName.style.display = 'block';
}
//check whether user form selected or group form selected
function checkgroup()
{
	if(jQuery("#group_checkbox").is(':checked'))
	{
		document.change_owner.lead_group_owner.style.display = "block";
		document.change_owner.lead_owner.style.display = "none";
	}
	else
	{
		document.change_owner.lead_group_owner.style.display = "none";
		document.change_owner.lead_owner.style.display = "block";
	}
}

function calendarChangeOwner()
{
	var idlist = document.change_owner.idlist.value;
		var view   = document.change_owner.view.value;
		var day    = document.change_owner.day.value;
		var month  = document.change_owner.month.value;
		var year   = document.change_owner.year.value;
		var hour   = document.change_owner.hour.value;
		var subtab = document.change_owner.subtab.value;

	//var checked = document.change_owner.user_lead_owner[0].checked;
	if (jQuery("#user_checkbox").is(':checked'))
	{
		var user_id = document.getElementById('lead_owner').options[document.getElementById('lead_owner').options.selectedIndex].value;
		var url = 'module=Users&action=updateLeadDBStatus&return_module=Calendar&return_action=ActivityAjax&owner_id='+user_id+'&idlist='+idlist+'&view='+view+'&hour='+hour+'&day='+day+'&month='+month+'&year='+year+'&type=change_owner';
	}
	else
	{
		var group_id = document.getElementById('lead_group_owner').options[document.getElementById('lead_group_owner').options.selectedIndex].value;
		var url = 'module=Users&action=updateLeadDBStatus&return_module=Calendar&return_action=ActivityAjax&owner_id='+group_id+'&idlist='+idlist+'&view='+view+'&hour='+hour+'&day='+day+'&month='+month+'&year='+year+'&type=change_owner';
	}

	if(subtab == 'event')
	{
		var OptionData = jQuery('#view_Option').val();
		var view_filter = jQuery('#filter_view_Option').val();
		var eventurl = url+'&viewOption='+OptionData+'&subtab=event&ajax=true&view_filter='+view_filter;

		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: eventurl,
			success: function(result) {
				if(OptionData == 'listview') {
					result = result.split('####');
					jQuery("#total_activities").html(result[1]);
					jQuery("#listView").html(result[0]);
				}
				if(OptionData == 'hourview') {
					result = result.split('####');
					jQuery("#total_activities").html(result[1]);
					jQuery("#hrView").html(result[0]);
				}
			}
		});
	}
	if(subtab == 'todo')
	{
		var todourl = url+'&subtab=todo&ajax=true';
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: todourl,
			success: function(result) {
				result = result.split('####');
				jQuery("#total_activities").html(result[1]);
				jQuery("#mnuTab2").html(result[0]);
			}
		});
	}

}

function delActivity(id,view,hour,day,month,year,subtab)
{
	if(subtab == 'event')
	{
		var OptionData = jQuery('#view_Option').val();
		var view_filter = jQuery('#filter_view_Option').val();
		
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Users&action=massdelete&return_module=Calendar&return_action=ActivityAjax&idlist='+id+'&view='+view+'&hour='+hour+'&day='+day+'&month='+month+'&year='+year+'&type=activity_delete&viewOption='+OptionData+'&subtab=event&ajax=true&view_filter='+view_filter,
			success: function(result) {
				if(OptionData == 'listview') {
					result = result.split('####');
					jQuery("#total_activities").html(result[1]);
					jQuery("#listView").html(result[0]);
				}
				if(OptionData == 'hourview') {
					result = result.split('####');
					jQuery("#total_activities").html(result[1]);
					jQuery("#hrView").html(result[0]);
				}
			}
		});
	}
	if(subtab == 'todo')
	{
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Users&action=massdelete&return_module=Calendar&return_action=ActivityAjax&idlist='+id+'&view='+view+'&hour='+hour+'&day='+day+'&month='+month+'&year='+year+'&type=activity_delete&subtab=todo&ajax=true',
			success: function(result) {
				result = result.split('####');
				jQuery("#total_activities").html(result[1]);
				jQuery("#mnuTab2").html(result[0]);
			}
		});
	}
}


/*
* javascript function to display the div tag
* @param divId :: div tag ID
*/
function cal_show(divId)

{

    var id = document.getElementById(divId);

    id.style.visibility = 'visible';

}

function fnAssignTo(){
		var option_Box = document.getElementById('parent_type');
		var option_select = option_Box.options[option_Box.selectedIndex].value;
		if(option_select == "Leads" || option_select == "Leads&action=Popup")
		{
			document.getElementById('leadLay').style.visibility = 'visible';
		}
		else if(option_select == "Accounts" || option_select == "Accounts&action=Popup")
		{
			document.getElementById('leadLay').style.visibility = 'visible';
		}
		else if(option_select == "Potentials" || option_select == "Potentials&action=Popup")
		{
			document.getElementById('leadLay').style.visibility = 'visible';
		}
		else if(option_select == "Quotes&action=Popup" || option_select == "Quotes&action=Popup")
                {
                        document.getElementById('leadLay').style.visibility = 'visible';
                }
		else if(option_select == "PurchaseOrder" || option_select == "PurchaseOrder&action=Popup")
                {
                        document.getElementById('leadLay').style.visibility = 'visible';
                }
		else if(option_select == "SalesOrder" || option_select == "SalesOrder&action=Popup")
                {
                        document.getElementById('leadLay').style.visibility = 'visible';
                }
		else if(option_select == "Invoice" || option_select == "Invoice&action=Popup")
                {
                        document.getElementById('leadLay').style.visibility = 'visible';
                }
		else if(option_select == "Campaigns" || option_select == "Campaigns&action=Popup")
                {
                        document.getElementById('leadLay').style.visibility = 'visible';
                }
		else{
			document.getElementById('leadLay').style.visibility = 'hidden';
		}
	}

function fnShowPopup(){
	document.getElementById('popupLay').style.display = 'block';
}

function fnHidePopup(){
	document.getElementById('popupLay').style.display = 'none';
}

function getValidationarr(id,activity_mode,opmode,subtab,viewOption){
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'module=Calendar&action=ActivityAjax&record='+id+'&activity_mode='+activity_mode+'&ajax=true&type=view&file=DetailView',
		success: function(result) {
			jQuery("#dataArray").html(result);
			setFieldvalues(opmode,subtab,viewOption);
		}
	});
}

function setFieldvalues(opmode,subtab,viewOption)
{
	var st = document.getElementById('activity_cont');
	eval(st.innerHTML);
	if(activity_type == 'Events')
	{
		document.EditView.viewOption.value = viewOption;
                document.EditView.subtab.value = subtab;
		for(x=0;x<key.length;x++)
		{
			if(document.EditView[key[x]] != undefined)
			{
				selectedValue(document.EditView.visibility,data[x]); //crmv@17001
				if(key[x] == 'activitytype' && data[x] == 'Call')
				{
					document.EditView.activitytype[0].checked = true;
				}
				else
				{
					document.EditView.activitytype[1].checked = true;
				}
				if(key[x] == 'set_reminder' && data[x] == 'Yes')
				{
					document.EditView.remindercheck.checked = true;
					document.getElementById('reminderOptions').style.display = 'block';
				}
				if(key[x] == 'recurringcheck' && data[x] == 'on')
				{
					document.EditView.recurringcheck.checked = true;
					document.getElementById('repeatOptions').style.display = 'block';
				}
				if(key[x] == 'recurringtype')
				{
					if(data[x] == 'Weekly')
						document.getElementById('repeatWeekUI').style.display = 'block';
					else
						document.getElementById('repeatWeekUI').style.display = 'none';
					if(data[x] == 'Monthly')
						document.getElementById('repeatMonthUI').style.display = 'block';
					else
						document.getElementById('repeatMonthUI').style.display = 'none';
				}
				if(key[x] == 'parent_name')
				{
					if(data[x] != '')
						document.getElementById('leadLay').style.visibility = 'visible';
					else
						document.getElementById('leadLay').style.display = 'hidden';
				}
				document.EditView[key[x]].value = data[x];
			//}
			}
		}
		document.getElementById('addEvent').style.display = 'block';
	}
	else
	{
		document.createTodo.viewOption.value = viewOption;
                document.createTodo.subtab.value = subtab;
		for(x=0;x<key.length;x++)
                {
			if(document.createTodo[key[x]] != undefined)
			{
                                document.createTodo[key[x]].value = data[x];
			}
		}
		document.getElementById('createTodo').style.display = 'block';
	}
}

function doNothing()
{
}

/** This is Javascript Function which is used to toogle between
  * assigntype user and group/team select options while assigning owner to Task.
  */
function toggleTaskAssignType(currType)
{
        if (currType=="U")
        {
                getObj("task_assign_user").style.display="block"
                getObj("task_assign_team").style.display="none"
        }
        else
        {
                getObj("task_assign_user").style.display="none"
                getObj("task_assign_team").style.display="block"
        }
}
function dochange(start,end)
{
        var startdate = document.getElementById(start);
        document.getElementById(end).value = startdate.value;
}

function getSelectedStatus()
{
        var chosen = document.EditView.eventstatus.value;
        if(chosen == "Held")
        {
                document.getElementById('date_table_firsttd').style.width = "33%";
                document.getElementById('date_table_secondtd').style.width = "33%";
                document.getElementById('date_table_thirdtd').style.display = 'block';
        }
        else
        {
                document.getElementById('date_table_firsttd').style.width = "50%";
                document.getElementById('date_table_secondtd').style.width = "50%";
                document.getElementById('date_table_thirdtd').style.display = 'none';
        }

}

function changeEndtime_StartTime(type)
{
//crmv@8398
	calDuedatetime(type);
	return true;
//crmv@8398e
}

function calDuedatetime(type)
{
	var dateval1=getObj('date_start').value.replace(/^\s+/g, '').replace(/\s+$/g, '');
	var dateelements1=splitDateVal(dateval1);
	dd1=parseInt(dateelements1[0],10);
	mm1=dateelements1[1];
	yyyy1=dateelements1[2];
	var date1=new Date();
	//date1.setDate(dd1+1);
	date1.setYear(yyyy1);
	date1.setMonth(mm1-1,dd1+1);
	var tempdate = getdispDate(date1);
	var date = document.EditView.date_start.value;
	var hour = parseInt(document.EditView.starthr.value,10);
	var min = parseInt(document.EditView.startmin.value,10);
	var fmt = document.EditView.startfmt.value;
	//crmv@8398
	if(type != 'Call')
	//crmv@8398e
	{
		if(fmt == 'pm')
		{
			if(hour == 11)
			{
				date = tempdate;
				hour = 12;
				min = min;
				fmt = 'am';

			}else if(hour == 12)
			{
				hour = 1;
				min = min;
				fmt = 'pm';
			}
			else hour = hour + 1;
			hour = _2digit(hour);
			min = _2digit(min);
			document.EditView.due_date.value = date;
			document.EditView.endhr.value = hour;
			document.EditView.endmin.value = min;
			document.EditView.endfmt.value = fmt;
			document.EditView.followup_date.value = date;
			document.EditView.followup_starthr.value = hour;
			document.EditView.followup_startmin.value = min;
			document.EditView.followup_startfmt.value = fmt;
		}else if(fmt == 'am')
		{
			if(hour == 11)
			{
				hour = 12; min = min; fmt = 'pm';
			}else if(hour == 12)
			{
				hour = 1; min = min; fmt = 'am';
			}
			else hour = hour + 1;
			hour = _2digit(hour);
			min = _2digit(min);
			document.EditView.due_date.value = date;
			document.EditView.endhr.value = hour;
			document.EditView.endmin.value = min;
			document.EditView.endfmt.value = fmt;
			document.EditView.followup_date.value = date;
			document.EditView.followup_starthr.value = hour;
			document.EditView.followup_startmin.value = min;
			document.EditView.followup_startfmt.value = fmt;
		}else
		{
			hour = hour + 1;
			if(hour == 24)
			{
				hour = 0;
				date =  tempdate;
			}
			hour = _2digit(hour);
			min = _2digit(min);
			document.EditView.due_date.value = date;
			document.EditView.endhr.value = hour;
			document.EditView.endmin.value = min;
			document.EditView.followup_date.value = date;
			document.EditView.followup_starthr.value = hour;
			document.EditView.followup_startmin.value = min;
		}
	}
	if(type == 'Call')
	{
		if(fmt == 'pm')
		{
			if(hour == 11 && min == 55)
			{
				hour = 12; min = 0; fmt = 'am';
				date = tempdate;
			}
			else if(hour == 12 && min == 55)
			{
				hour = 1; min = 0; fmt = 'pm';
			}
			else
			{
				if(min == 55)
				{
					min = 0;
					hour = hour + 1;
				}
				else    min = min + 5;
			}
			hour = _2digit(hour);
			min = _2digit(min);
			document.EditView.due_date.value = date;
			document.EditView.endhr.value = hour;
			document.EditView.endmin.value = min;
			document.EditView.endfmt.value = fmt;
			document.EditView.followup_date.value = date;
			document.EditView.followup_starthr.value = hour;
			document.EditView.followup_startmin.value = min;
			document.EditView.followup_startfmt.value = fmt;
		}else if(fmt == 'am')
		{
			if(hour == 11 && min == 55)
			{
				hour = 12;
				min = 0;
				fmt = 'pm';
			}
			else if(hour == 12 && min == 55)
			{
				hour = 1;
				min = 0;
				fmt = 'am';
			}
			else
			{
				if(min == 55)
				{
					min = 0;
					hour = hour + 1;
				}
				else    min = min + 5;
			}
			hour = _2digit(hour);
			min = _2digit(min);
			document.EditView.due_date.value = date;
			document.EditView.endhr.value = hour;
			document.EditView.endmin.value = min;
			document.EditView.endfmt.value = fmt;
			document.EditView.followup_date.value = date;
			document.EditView.followup_starthr.value = hour;
			document.EditView.followup_startmin.value = min;
			document.EditView.followup_startfmt.value = fmt;
		}
		else
		{
			if(min == 55)
			{
				min = 0;
				hour = hour + 1;
			}else min = min + 5;
			if(hour == 24)
			{
				hour = 0;
				date =  tempdate;
			}
			hour = _2digit(hour);
			min = _2digit(min);
			document.EditView.due_date.value = date;
			document.EditView.endhr.value = hour;
			document.EditView.endmin.value = min;
			document.EditView.followup_date.value = date;
			document.EditView.followup_starthr.value = hour;
			document.EditView.followup_startmin.value = min;
		}
	}
}

function cal_fnvshobj(obj,Lay){
    var tagName = document.getElementById(Lay);
    var leftSide = findPosX(obj);
    var topSide = findPosY(obj);
    tagName.style.left = 550 + 'px';
    tagName.style.top= (topSide - 100) + 'px';
    tagName.style.display = 'block';
    tagName.style.visibility = "visible";
}

/**this is for to add a option element while selecting contact in add event page
   lvalue ==> is a contact id
   ltext ==> is a contact name
**/
function addOption(lvalue,ltext)
{
	var optObj = document.createElement('OPTION')
	if (browser_ie) optObj.innerText = ltext;
        else if(browser_nn4 || browser_nn6) optObj.text = ltext;
	else optObj.text = ltext;
	optObj.value = lvalue;
	document.getElementById('parentid').appendChild(optObj);
}

function getdispDate(tempDate)	//crmv@31315
{
	//crmv@29190	//crmv@31315
	var formName = getReturnFormName();
	var form = getReturnForm(formName);
	//crmv@29190e

	var datefmt = form.dateformat.value;
    var dd = _2digit(parseInt(tempDate.getDate(),10));
    var mm = _2digit(parseInt(tempDate.getMonth(),10)+1);
	var yy = tempDate.getFullYear();
	if(datefmt == '%d-%m-%Y') return dd+'-'+mm+'-'+yy;
	else if(datefmt == '%m-%d-%Y') return mm+'-'+dd+'-'+yy;
	else return yy+'-'+mm+'-'+dd;
}

//crmv@26807

//crmv@26921
function addEventLinkUI(label) {
	var addEventLinkUI_div = '';
	addEventLinkUI_div += '<th class="cb-key">'+ label + ':' + '</th><td class="cb-value" id="LinkDetails"></td>';
	LinkDetails('LinkDetails');
	return addEventLinkUI_div;
}

function addEventSingleContactLinkUI() {
	var addEventSingleContactLinkUI_div = '';
	addEventSingleContactLinkUI_div += '<th class="cb-key"></th><td class="cb-value" id="SingleContactDetails"></td>';
	LinkDetails('SingleContactDetails');
	return addEventSingleContactLinkUI_div;
}

function addEventContactsLinkUI() {
	var addEventContactsLinkUI_div = '';
	addEventContactsLinkUI_div += '<th class="cb-key"></th><td class="cb-value" id="ContactsDetails"></td>';
	LinkDetails('ContactsDetails');
	return addEventContactsLinkUI_div;
}
//crmv@26921e

function getInviteesDetail() {
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: "module=Calendar&action=CalendarAjax&file=wdCalendar&subfile=ajaxRequests/getInviteesDetail",
		success: function(result) {
			var link = result;
			link = link.split('-|@##@|-');
			jQuery('#optionUserDetails_search').html(link[0]);
			jQuery('#optionUserDetails_add').html(link[1]);
			jQuery('#bbit-cal-txtSearch').click(function() {
				jQuery(this).focus();
				focusSearchInput(this, link[2]);	//crmv@29190
			});
			// crmv@101475
			jQuery('#bbit-cal-txtSearch').keyup(jQuery.debounce(300, function() {
				searchFunction('bbit-cal-txtSearch');
			}));
			// crmv@101475e
		}
	});
}

function searchFunction(txtSearchId) {
	if (jQuery('#' + txtSearchId).val().length > 2) {	// > 0
		jQuery('#' + txtSearchId).addClass('ui-autocomplete-loading');
		autocompleteCall(txtSearchId);
  }
  /*
  else if (jQuery('#' + txtSearchId).val().length == 0) {
      resetSearch(txtSearchId);
  }
  */
}

function autocompleteCall(txtSearchId) {
	var term = '&term=' + jQuery('#' + txtSearchId).val();
	var referenceModule = '&referenceModule=' + jQuery('#quick_parent_type').val(); // crmv@98866
	var urlAutocomplete = 'index.php?module=Calendar&action=CalendarAjax&file=Autocomplete' + term + referenceModule;

	jQuery.getJSON(urlAutocomplete, function(data) {
		var items = [];
		var dataStr = '';
		jQuery.each(data, function(key, val) {
			dataStr += '<tr id="' + val.id + '" onclick="checkTr(this.id)">' +
							'<td align="center" style="display:none;"><input type="checkbox" value="' + val.id + '"></td>' +
							'<td nowrap align="left" class="parent_name" style="width:100%">' + val.parent_name + '</td>' +
						'</tr>';
		});
		jQuery('#availableTable').html(dataStr);
		jQuery('#' + txtSearchId).removeClass('ui-autocomplete-loading');
	});
}

function incUser_quick(objId,className) {
	if (typeof className == 'undefined'){
  	var className = 'invited';
	}
  if (jQuery('#' + objId).hasClass(className)) {
      jQuery('#' + objId).removeClass(className);
      jQuery('#' + objId).css('background-color','');
  }
  else {
      jQuery('#' + objId).addClass(className);
      jQuery('#' + objId).css('background-color','#C8DEFB');
  }
}

//crmv@29190
function LinkDetails(linktype) {
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: "module=Calendar&action=CalendarAjax&file=wdCalendar&subfile=ajaxRequests/getLinkDetail&linktype=" + linktype,
		success: function(result) {
			var link = result;
			var empty_str = jQuery('#selectparent_link').val();
			jQuery('#' + linktype).html(link);
			jQuery('#parent_type_link').click(function(){
				//jQuery('#link_tab').css('top',jQuery('#parent_type_link').position().top);
				jQuery('#link_tab').css('display','block');
			});
			if (linktype == 'LinkDetails') {
				jQuery('#selectparent_link').click(function() {
					jQuery(this).focus();
					focusSearchInput(this, empty_str);
				});
				initAutocomplete('parent_id_link','selectparent_link',encodeURIComponent(linkClick('parent_type_link','yes')));
			} else if (linktype == 'SingleContactDetails') {
				jQuery('#selectparent_link_singleContact').click(function() {
					jQuery(this).focus();
					focusSearchInput(this, empty_str);
				});
				initAutocomplete('parent_id_link_singleContact','selectparent_link_singleContact',encodeURIComponent(linkClick('parent_type_link_singleContact','yes')));
			} else if (linktype == 'ContactsDetails') {
				jQuery('#multi_contact_autocomplete_wd').click(function() {
					jQuery(this).focus();
					focusSearchInput(this, empty_str);
				});
				initMultiContactAutocomplete('multi_contact_autocomplete_wd','wdCalendar',encodeURIComponent(linkClickContacts('contacts_div','yes')));
			}
			jQuery('#activitytype_show_button').click(function(){
				jQuery('#activitytype_tab').css('top', '20px'); //crmv@OPER5104
				jQuery('#activitytype_tab').css('display','block');
			});
			//crmv@26965
			closePicklist('activitytype_tab');
			closePicklist('quick_parent_type_tab');
			closePicklist('link_tab');
			//crmv@26965e
		}
	});
}
//crmv@29190e

//crmv@26921	//crmv@29190
function linkClick(parentId,autocomplete) {
	if (parentId == 'parent_type_link_singleContact' ) {
		if(jQuery('#parent_type_link').attr('name') == 'Leads' ) {
			alert(top.alert_arr.CANT_SELECT_CONTACTS);
		}
		else {
			if(jQuery('#parent_id_link').val() != '') {
				var search_string = "&parent_module=" + jQuery('#parent_type_link').attr('name') +"&relmod_id=" + jQuery('#parent_id_link').val();
				if (autocomplete == 'yes') {
					return "module=Contacts&action=Popup&fromCalendar=fromCalendar&parentId=" + parentId;
				} else {
					openPopup("index.php?module=Contacts&action=Popup&fromCalendar=fromCalendar&parentId=" + parentId + search_string,'','','','','','top');
				}
			}
			else {
				if (autocomplete == 'yes') {
					return "module=Contacts&action=Popup&fromCalendar=fromCalendar&parentId=" + parentId;
				} else {
					openPopup("index.php?module=Contacts&action=Popup&fromCalendar=fromCalendar&parentId=" + parentId,'','','','','','top');
				}
			}
		}
	}
	else {
		if (autocomplete == 'yes') {
			return 'module=' + jQuery('#' + parentId).attr('name') + '&action=Popup&fromCalendar=fromCalendar&parentId=' + parentId;
		} else {
			openPopup('index.php?module=' + jQuery('#' + parentId).attr('name') + '&action=Popup&fromCalendar=fromCalendar&parentId=' + parentId,'','','','','','top');
		}
	}
}

function linkClickContacts(parentId,autocomplete) {
	if(jQuery('#parent_type_link').attr('name') == 'Leads' ) {
		alert(top.alert_arr.CANT_SELECT_CONTACTS);
	}
	else {
		if(jQuery('#parent_id_link').val() != '') {
			var search_string = "&parent_module=" + jQuery('#parent_type_link').attr('name') +"&relmod_id=" + jQuery('#parent_id_link').val();
			if (autocomplete == 'yes') {
				return "module=Contacts&action=Popup&select=enable&fromCalendar=fromCalendar&parentId=" + parentId + search_string;
			} else {
				openPopup("index.php?module=Contacts&action=Popup&select=enable&fromCalendar=fromCalendar&parentId=" + parentId + search_string,'','','','','','top');
			}
		}
		else {
			if (autocomplete == 'yes') {
				return "module=Contacts&action=Popup&select=enable&fromCalendar=fromCalendar&parentId=" + parentId;
			} else {
				openPopup("index.php?module=Contacts&action=Popup&select=enable&fromCalendar=fromCalendar&parentId=" + parentId,'','','','','','top');
			}
		}
	}
	//openPopup("index.php?module=Contacts&action=Popup&select=enable&fromCalendar=fromCalendar&parentId=" + parentId,'','','','','','top');
}
//crmv@26921e	//crmv@29190e

//crmv@26961
function inviteesPopup(parentId,objId,edit) {
	var fromCalendar = '';
	if (typeof(edit) != undefined && edit == 'edit') {
		fromCalendar = 'fromEditViewCalendar';
	}
	else {
		fromCalendar = 'fromCalendar';
	}
	//crmv@104061 crmv@138379
	var url = 'index.php?module=' + jQuery('#' + objId).val() + '&action=Popup&fromCalendar=' + fromCalendar + '&select=enable&parentId=' + parentId;
	openPopup(url);
	//crmv@104061e crmv@138379e
}


function linkContactsTable(entity_id,strVal,parentId,linkedMod) {
	if (top.jQuery('#addEvent').css('display') != 'block') {
		if (parentId == 'contacts_div') {
			if (jQuery('#contacts_div table').contents().find('#'+entity_id).length < 1) {
				strHtlm = '<tr style="cursor:hand;cursor:pointer;"><td onclick="javascript:incUser_quick(this.id,\'contactSelected\')" id="' + entity_id + '">' + strVal + '</td></tr>';
				jQuery('#contacts_div table').append(strHtlm);
				jQuery('#parent_id_link_contacts').val(jQuery('#parent_id_link_contacts').val() + ';' + entity_id); //crmv@26921
			}
		}
		else if (parentId == 'selectedTable') {
			if (top.wdCalendar.jQuery('#bbit-cal-buddle').contents().find('#' + entity_id + '_' + linkedMod + '_dest').length < 1) {
				strHtlm = '<tr id="' + entity_id + '_' + linkedMod + '_dest' + '" onclick="checkTr(this.id)">' +
								'<td align="center" style="display:none;"><input type="checkbox" value="' + entity_id + '_' + linkedMod + '"></td>' +
								'<td nowrap align="left" class="parent_name" style="width:100%">' + strVal + '</td>' +
							'</tr>';
				jQuery('#selectedTable').append(strHtlm);
				jQuery('#parent_id_link_contacts').val(jQuery('#parent_id_link_contacts').val() + ';' + entity_id);
			}
		}
	}
	else if (parentId == 'selectedTable') {
		if (top.jQuery('#addEvent').contents().find('#' + entity_id + '_' + linkedMod + '_dest').length < 1) {
			strHtlm = '<tr id="' + entity_id + '_' + linkedMod + '_dest' + '" onclick="checkTr(this.id)">' +
			'<td align="center" style="display:none;"><input type="checkbox" value="' + entity_id + '_' + linkedMod + '"></td>' +
			'<td nowrap align="left" class="parent_name" style="width:100%">' + strVal + '</td>' +
			'</tr>';
			top.jQuery('#selectedTable').append(strHtlm);
			jQuery('#parent_id_link_contacts').val(jQuery('#parent_id_link_contacts').val() + ';' + entity_id);
		}
	}
}
//crmv@26961e

function clearLink(parentId,selectId) {
	jQuery('#' + selectId).val('');	//crmv@29190
	jQuery('#' + parentId).val('');
}

function clearTrLinks() {
  jQuery('#contacts_div table').find('.contactSelected').each(function() {
  	jQuery(this).parent().remove();
  });
}

function linkTabClick(module,strModule) {
	jQuery('#parent_type_link').attr('name',module);
	jQuery('#parent_type_link').val(strModule); //crmv@OPER5104
	clearLink('parent_id_link','selectparent_link');
	jQuery('#link_tab').css('display','none');
	//crmv@29190
	initAutocomplete('parent_id_link','selectparent_link',encodeURIComponent(linkClick('parent_type_link','yes')));
	enableReferenceField(jQuery('#selectparent_link'));
	jQuery('#selectparent_link').click();
	//crmv@29190e
}

function activitytypeTabClick(act,strAct) {
	if(act === 'Free for appointment') jQuery('#bbit-cal-what').val(strAct);	//crmv@56580
	jQuery('#activitytype_show_button').attr('name',act);
	jQuery('#activitytype_show_button').val(strAct); //crmv@OPER5104
	jQuery('#activitytype_tab').css('display','none');
}

function incDest(availDest,selDest,buttonValue) {
	var trId = '';
	jQuery('#' + availDest).find('tr').find('input:checked').each(function(){
		trId = jQuery(this).val();
		if (jQuery('#' + selDest).contents().find('#' + trId + '_dest').length < 1) {
			jQuery('#' + trId).clone().attr('id',trId + '_dest').appendTo('#' + selDest);
		}
	});
	jQuery('#selectedTable').find('input:checked').each(function(){
		jQuery(this).removeAttr('checked');
		jQuery(this).parent().parent().css('background-color','')
	});
}

function rmvDest(selDest) {
	jQuery('#' + selDest).find('input:checked').each(function(){
		trId = jQuery(this).parent().parent().attr('id');
		jQuery('#' + selDest).find('#' + trId).remove();
	});
}

//crmv@98866
function checkTr(objId) {
	if (jQuery('#' + objId).find('input:checkbox').prop('checked') == true) {
		jQuery('#' + objId).css('background-color', '');
		jQuery('#' + objId).find('input:checkbox').prop('checked', false);
	} else {
		jQuery('#' + objId).css('background-color', '#C8DEFB');
		jQuery('#' + objId).find('input:checkbox').prop('checked', true);
	}
}
//crmv@98866e

function resetSearch(txtSearchId) {
	jQuery('#' + txtSearchId).val(empty_search_str);	//crmv@29190
	jQuery('#availableTable tr').css('display','block');
	jQuery('#availableTable tr').find('input:checkbox').prop('checked', false);
	jQuery('#availableTable tr').css('background-color','')
	jQuery('#selectedTable tr').find('input:checkbox').prop('checked', false);
	jQuery('#selectedTable tr').css('background-color','')
	jQuery('#' + txtSearchId).focus();
	jQuery('#availableTable').html('');
}

function showInv(objId,clickedId) {
	jQuery('#' + objId).css('top',jQuery('#' + clickedId).position().top);
	jQuery('#' + objId).css('display','block');
}

function searchTabClick(module,strModule) {
	jQuery('#quick_parent_type').attr('name',module);
	jQuery('#quick_parent_type').val(strModule);
	jQuery('#quick_parent_type_tab').css('display','none');
	resetSearch('bbit-cal-txtSearch');
}

function onMouseOverButton(objId) {
    jQuery('#' + objId).css('background-color','#C8DEFB');
}
function onMouseOutButton(objId) {
    jQuery('#' + objId).css('background-color','');
}
//crmv@26807e

//crmv@26965
function closePicklist(objId) {
	jQuery('#' + objId).hover(
		function () {
		},
		function () {
			jQuery(this).css('display','none');
		}
	);
}
//crmv@26965e

//crmv@29190
function focusSearchInput(field, empty_str) {
	if (field.value == empty_str) {
		field.value = '';
	}
}

function blurSearchInput(field, empty_str) {
	if (field.value == '') {
		field.value = empty_str;
	}
}

// crmv@170658 - removed duplicated function

function enableReferenceField(field) {
	jQuery(field).attr('readonly',false);
	if (jQuery(field).parent('div').length > 0) {
		var div = jQuery(field).parent('div');
		div.attr('class','dvtCellInfoOn');
		div.focusin(function(){
			div.attr('class','dvtCellInfoOn');
		}).focusout(function(){
			div.attr('class','dvtCellInfo');
		});
	}
	jQuery(field).focus();
}
function disableReferenceField(field) {
	jQuery(field).attr('readonly','readonly');
	if (jQuery(field).parent('div').length > 0) {
		var div = jQuery(field).parent('div');
		div.attr('class','dvtCellInfoOff');
		div.focusin(function(){
			div.attr('class','dvtCellInfoOff');
		}).focusout(function(){
			div.attr('class','dvtCellInfoOff');
		});
	}
	jQuery(field).blur();
}
function resetReferenceField(field) {
	jQuery(field).attr('readonly',false);
	if (jQuery(field).parent('div').length > 0) {
		var div = jQuery(field).parent('div');
		div.attr('class','dvtCellInfo');
		div.focusin(function(){
			div.attr('class','dvtCellInfoOn');
		}).focusout(function(){
			div.attr('class','dvtCellInfo');
		});
	}
	jQuery(field).val(empty_search_str);
}
function initMultiContactAutocomplete(field,mode,params) {
	
	// crmv@140887
	var appendTo = null;
	if (mode == 'addEventUI') appendTo = '#addEvent';
	// crmv@140887e

	jQuery('#'+field)
		.focus(function(){
			var term = this.value;
			if ( term.length == 0 || this.value == empty_search_str) {
				this.value = '';
			}
		})
		.blur(function(){
			var term = this.value;
			if ( term.length == 0 ) {
				this.value = empty_search_str;
			}
		})
		.autocomplete({
			appendTo: appendTo, // crmv@140887
			source: function( request, response ) {
				jQuery.getJSON( "index.php?module=SDK&action=SDKAjax&file=src/Reference/Autocomplete", {
					term: request.term,
					field: field,
					params: params
				}, function(data) {
					var url = "index.php?"+decodeURIComponent(params);
					jQuery.getJSON(url, {
						autocomplete: 'yes',
						autocomplete_select: data[0],
						autocomplete_where: data[1]	//crmv@42329
					}, response );
				});
			},
			search: function() {
				// custom minLength
				var term = this.value;
				if ( term.length < 3 ) {
					return false;
				}
			},
			focus: function() {
				// prevent value inserted on focus
				return false;
			},
			select: function( event, ui, ret_funct ) {
				jQuery(this).click();
				if (ui.item.return_function_file != '') {
					autocomplete_include_script = 'yes';
					jQuery.getScript(ui.item.return_function_file, function(data){
						eval(data);
						eval(ui.item.return_function);
						jQuery.getScript('modules/Calendar/script.js', function(data){eval(data);});
						autocomplete_include_script = 'no';
						jQuery('#'+field).val('');
					});
				}
				return false;
			}
		}
	);
}
//crmv@29190e

//crmv@31315
function calDuedatetimeQC(form, type)
{
	//crmv@29190
	var formName = 'QcEditView';
	//crmv@29190e

	var dateval1 = form.date_start.value.replace(/^\s+/g, '').replace(/\s+$/g, '');
	//setting due_date like date_start
	if(form.due_date != null){	//not task
		form.due_date.value = dateval1;
		if(type == 'hour'){

			var start_hour = form.starthr.value;
			var start_minutes = form.startmin.value;
			var end_hour = form.endhr.value;
			var end_minutes =form.endmin.value;

			end_hour = eval(start_hour)+1;
			if(start_hour == '23'){
				end_hour = '0';
			}else{
				end_minutes = eval(start_minutes); //+5
			}

			start_hour = _2digit(eval(start_hour));
			end_hour = _2digit(eval(end_hour));
			start_minutes = _2digit(eval(start_minutes));
			end_minutes = _2digit(eval(end_minutes));

			form.starthr.value = start_hour;
			form.startmin.value = start_minutes;
			form.endhr.value = end_hour;
			form.endmin.value = end_minutes;

			form.time_start.value = start_hour+':'+start_minutes;
            form.time_end.value = end_hour+':'+end_minutes;
		}
	}

}