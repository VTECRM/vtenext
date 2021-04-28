/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
function trimfValues(value)
{
    var string_array;
    string_array = value.split(":");
    return string_array[4];
}

function updatefOptions(sel, opSelName, modulename, selectsequence) {	//crmv@29615

    var selObj = document.getElementById(opSelName);
    var fieldtype = null ;

    var currOption = selObj.options[selObj.selectedIndex];
    var currField = sel.options[sel.selectedIndex];
    var fld = currField.value.split(":");
    var tod = fld[4];

    if(currField.value != null && currField.value.length != 0)
    {
	fieldtype = trimfValues(currField.value);
	//crmv@7221
	if (typeof(typeofdata) == 'undefined'){
		var typeofdata = new Array();
		typeofdata['V'] = ['e','n','s','ew','c','k'];
		typeofdata['N'] = ['e','n','l','g','m','h'];
		typeofdata['T'] = ['e','n','l','g','m','h'];
		typeofdata['I'] = ['e','n','l','g','m','h'];
		typeofdata['C'] = ['e','n'];
		typeofdata['DT'] = ['e','n','l','g','m','h'];
		typeofdata['D'] = ['e','n','l','g','m','h'];
		typeofdata['NN'] = ['e','n','l','g','m','h'];
		typeofdata['E'] = ['e','n','s','ew','c','k'];
	}
	if (typeof(fLabels) == 'undefined'){
		var fLabels = new Array();
		fLabels['e'] = alert_arr.EQUALS;
		fLabels['n'] = alert_arr.NOT_EQUALS_TO;
		fLabels['s'] = alert_arr.STARTS_WITH;
		fLabels['ew'] = alert_arr.ENDS_WITH;
		fLabels['c'] = alert_arr.CONTAINS;
		fLabels['k'] = alert_arr.DOES_NOT_CONTAINS;
		fLabels['l'] = alert_arr.LESS_THAN;
		fLabels['g'] = alert_arr.GREATER_THAN;
		fLabels['m'] = alert_arr.LESS_OR_EQUALS;
		fLabels['h'] = alert_arr.GREATER_OR_EQUALS;	
	}
	//crmv@7221e
	ops = typeofdata[fieldtype];
	if (modulename == 'Processes' && fld[2] == 'process_actor') ops = ['e','c'];	//crmv@103450
	var off = 0;
	if(ops != null)
	{

		var nMaxVal = selObj.length;
		for(nLoop = 0; nLoop < nMaxVal; nLoop++)
		{
			selObj.remove(0);
		}
		selObj.options[0] = new Option (alert_arr.LBL_NONE, '');
		if (currField.value == '') {
			selObj.options[0].selected = true;
		}
		off = 1;
		for (var i = 0; i < ops.length; i++)
		{
			var label = fLabels[ops[i]];
			if (label == null) continue;
			var option = new Option (fLabels[ops[i]], ops[i]);
			selObj.options[i + off] = option;
			if (currOption != null && currOption.value == option.value)
			{
				option.selected = true;
			}
		}
	}
    }else
    {
		if (currField.value == '') {
			selObj.options[0].selected = true;
		}
    }
	//crmv@29615
    if(currField.value != '')
		changeColumnField(modulename, currField.value, selectsequence, currOption.value);
	else
		document.getElementById("Srch_adv_value"+selectsequence).innerHTML = '';
	//crmv@29615e
}
function verify_data() {
	var isError = false;
	var errorMessage = "";
	if (trim(document.EditView.viewName.value) == "") {	//crmv@29615
		isError = true;
		errorMessage += "\nView Name";
	}
	// Here we decide whether to submit the form.
	if (isError == true) {
		alert(alert_arr.MISSING_REQUIRED_FIELDS + errorMessage);
		return false;
	}
	//return true;
}


function CancelForm()
{
var cvmodule = document.templatecreate.cvmodule.value;
var viewid = document.templatecreate.cvid.value;
document.location.href = "index.php?module="+cvmodule+"&action=index&viewname="+viewid;
}


function check4null(form)
{
        var isError = false;
        var errorMessage = "";
        // Here we decide whether to submit the form.
        if (trim(form.subject.value) =='') {
                isError = true;
                errorMessage += "\n subject";
                form.subject.focus();
        }

        // Here we decide whether to submit the form.
        if (isError == true) {
                alert(alert_arr.MISSING_REQUIRED_FIELDS + errorMessage);
                return false;
        }
 return true;
}

// Added for Custom View Advance Filter validation
function checkval()
{
	var value,option,arr,dttime,sep;
	for(var i=1;i<=5;i++)
	{
		value=trim(getObj("fval"+i).value);
		option=getObj("fcol"+i).value;
		fopvalue=trim(getObj("fop"+i).value);
		if(option !="" && value !="")
		{
			if(getObj("fop"+i).selectedIndex == 0)
				{
					alert(alert_arr.LBL_SELECT_CRITERIA);
		        	        return false;	
				}
			arr=option.split(":");
			if(arr[4] == "N" || arr[4] == "I" || arr[4] == "NN")
			{
				sep=value.split(",");
				for(var j=0;j<sep.length;j++)
				{
					if(arr[3] == "Calendar_Start_Time" || arr[3] == "Calendar_End_Time")
					{
						if(!cv_patternValidate(sep[j],"Time","TIME"))
						{
							getObj("fval"+i).select();
							return false;
						}
					}
					else if(isNaN(sep[j]))
					{
						alert(alert_arr.LBL_ENTER_VALID_NO);
						getObj("fval"+i).select();
						return false;
					}
				
	
				}
			}
			if(arr[4] == "D")
			{

				sep=value.split(",");
                                for(var j=0;j<sep.length;j++)
                                {
					if(!cv_dateValidate(trim(sep[j]),"Date","OTH"))
					{
						getObj("fval"+i).select();
						return false;
					}
				}
			}	
			if(arr[4] == "T")
			{

				sep=value.split(",");
				for(var j=0;j<sep.length;j++)
				{
					var dttime=trim(sep[j]).split(" ");

					if(sep[j].length == 5 && sep[j].indexOf(':') > -1)	//crmv@122071
                    {
                        if(!cv_patternValidate(sep[j],"Time","TIME"))
                        {
                            getObj("fval"+i).select();
                            return false;
                        }
                    }
                    else if(!cv_dateValidate(dttime[0],"Date","OTH"))
					{
						getObj("fval"+i).select();
						return false;
					}
					if(dttime.length > 1)
					{
						if(!cv_patternValidate(dttime[1],"Time","TIMESECONDS"))
						{
							getObj("fval"+i).select();
							return false;
						}
					}
				}

			}	
			if(arr[4] == "C")
			{
					if(value == "1")
					{
						getObj("fval"+i).value= "yes";
						continue;
					}
					else if(value == "0")
					{
						getObj("fval"+i).value= "no";
						continue;						
					}
					if(value.toLowerCase() != "yes") if(value.toLowerCase() != "no") 
					{
						alert(alert_arr.LBL_PROVIDE_YES_NO);
						getObj("fval"+i).select();
						return false;
					}
			}	
		}
		else if (!(option =="" && fopvalue == "" && value == "")) 
		{
			if(option =="")
			{
				alert(alert_arr.LBL_SELECT_COLUMN);
				return false;
			}
			if(fopvalue == "")
			{
				alert(alert_arr.LBL_SELECT_CRITERIA);
				return false;
			}
		}
	}
return true;
}

//Added for Custom view validation
//Copied from general.js and altered some lines. becos we cant send vales to function present in general.js. it accept only field names.
function cv_dateValidate(fldval,fldLabel,type) {
	if(cv_patternValidate(fldval,fldLabel,"DATE")==false)
		return false;
	dateval=fldval.replace(/^\s+/g, '').replace(/\s+$/g, '') 

	var dateelements=splitDateVal(dateval)
	
	dd=dateelements[0]
	mm=dateelements[1]
	yyyy=dateelements[2]
	
	if (dd<1 || dd>31 || mm<1 || mm>12 || yyyy<1 || yyyy<1000) {
		alert(alert_arr.ENTER_VALID+fldLabel)
		return false
	}
	
	if ((mm==2) && (dd>29)) {//checking of no. of days in february month
		alert(alert_arr.ENTER_VALID+fldLabel)
		return false
	}
	
	if ((mm==2) && (dd>28) && ((yyyy%4)!=0)) {//leap year checking
		alert(alert_arr.ENTER_VALID+fldLabel)
		return false
	}

	switch (parseInt(mm)) {
		case 2 : 
		case 4 : 
		case 6 : 
		case 9 : 
		case 11 :	if (dd>30) {
						alert(alert_arr.ENTER_VALID+fldLabel)
						return false
					}	
	}
	
	var currdate=new Date()
	var chkdate=new Date()
	
	chkdate.setYear(yyyy)
	chkdate.setMonth(mm-1)
	chkdate.setDate(dd)
	
	if (type!="OTH") {
		if (!compareDates(chkdate,fldLabel,currdate,"current date",type)) {
			return false
		} else return true;
	} else return true;
}

//Added for Custom view validation
//Copied from general.js and altered some lines. becos we cant send vales to function present in general.js. it accept only field names.
function cv_patternValidate(fldval,fldLabel,type) {
	if (type.toUpperCase()=="DATE") {//DATE validation 

		switch (userDateFormat) {
			case "yyyy-mm-dd" : 
								var re = /^\d{4}(-)\d{1,2}\1\d{1,2}$/
								break;
			case "mm-dd-yyyy" : 
			case "dd-mm-yyyy" : 
								var re = /^\d{1,2}(-)\d{1,2}\1\d{4}$/								
		}
	}
	

	if (type.toUpperCase()=="TIMESECONDS") 
	{
		//TIME validation.optional hour, min and seconds
		//var re = new RegExp("^([0-1][0-9]|[2][0-3]):([0-5][0-9]):([0-5][0-9])$");
		var re = new RegExp("^(([0-1]?[0-9])|([2][0-3]))(:([0-5]?[0-9]))?(:([0-5]?[0-9]))?$");
	}
	else if (type.toUpperCase()=="TIME") 
	{
		//TIME validation. optional hours and minutes only. dont accept second. added for calendar start and end time field.
		var re = new RegExp("^(([0-1]?[0-9])|([2][0-3]))(:([0-5]?[0-9]))$");
	}
	if (!re.test(fldval)) {
		alert(alert_arr.ENTER_VALID + fldLabel)
		return false
	}
	else return true




}
//added to hide date selection option, if a user doesn't have permission for not permitter standard filter column
//added to fix the ticket #5117
function standardFilterDisplay()
{
	if(getObj("stdDateFilterField"))
	{
		if(document.EditView.stdDateFilterField.selectedIndex > -1 && document.EditView.stdDateFilterField.options[document.EditView.stdDateFilterField.selectedIndex].value == "not_accessible")	//crmv@29615
		{
			getObj('stdDateFilter').disabled = true;
			getObj('startdate').disabled = true;                                                                                         getObj('enddate').disabled = true;
			getObj('jscal_trigger_date_start').style.visibility="hidden";
			getObj('jscal_trigger_date_end').style.visibility="hidden";
		}
		else
		{
			getObj('stdDateFilter').disabled = false;
			getObj('startdate').disabled = false;
			getObj('enddate').disabled = false;
			getObj('jscal_trigger_date_start').style.visibility="visible";
			getObj('jscal_trigger_date_end').style.visibility="visible";
		}
	}
}

//crmv@29615
function changeColumnField(parent_module, value, selectsequence, option)
{
	var viewid = jQuery('#customview_form input[name=record]').val();
	content = document.getElementById("Srch_adv_value"+selectsequence);
	content.innerHTML = "<span id=\"vtbusy_info"+selectsequence+"\" valign=\"bottom\"><i class=\"dataloader\" data-loader=\"circle\" style=\"vertical-align:middle;\"></i></span>";
	response = getFile("index.php?module=CustomView&action=CustomViewAjax&file=ShowColumn&type=CustomView&parent_module="+parent_module+"&value="+value+"&selectsequence="+selectsequence+'&option='+option+'&viewid='+viewid);
	resp = response.split("$$$");
	content.innerHTML = resp[0];
	var scriptTags = content.getElementsByTagName("script");
	for(var i = 0; i< scriptTags.length; i++){
		var scriptTag = scriptTags[i];
		eval(scriptTag.innerHTML);
	}
	eval(content);
		
	params = resp[1].split("@@@");
	fieldname = params[0];
	//crmv@42329
	if (typeof searchAdvFields == 'undefined'){
		searchAdvFields = new Array();
	}
	//crmv@42329e	
	searchAdvFields[selectsequence] = fieldname;
		
	val = jQuery('#fval'+selectsequence).val();
	if (val != '') loadColumnField(val, parent_module, value, selectsequence);
	jQuery("#fval"+selectsequence).val('');
}
function loadColumnField(val, parent_module, value, selectsequence)
{
	var fieldname = searchAdvFields[selectsequence];
	var element = jQuery('div#Srch_adv_value'+selectsequence+' *[name="'+fieldname+'"],div#Srch_adv_value'+selectsequence+' *[name="'+fieldname+'[]"]');
	var type = jQuery(element).prop('type');	//crmv@106293
	if (type == 'checkbox' && val == 'Yes')
		element.prop('checked',true);
	else if (fieldname == 'assigned_user_id') {
		var assigntype = 'U'; //crmv@32334
		/*
		jQuery('div#Srch_adv_value'+selectsequence+' select[name="assigned_group_id"] option').each(function(){
			if (this.text == val) {
				assigntype = 'T';
				jQuery('div#Srch_adv_value'+selectsequence+' select[name="assigned_group_id"]').val(this.value);
	      		return false;
			}
		});
		jQuery('div#Srch_adv_value'+selectsequence+' select[name="assigned_user_id"] option').each(function(){
			if (this.text == val) {
				assigntype = 'U';
				element.val(this.value);
	      		return false;
			}
		});
		*/
		// crmv@95272
		//jQuery('div#Srch_adv_value'+selectsequence+' input:radio[name="assigntype"]').val(new Array(assigntype));
		//toggleAssignType(assigntype);
	}
	else if (type == 'select-multiple') {
		tmp_val = val.split(',');
		new_val = new Array();
		for(var i=0;i<tmp_val.length;i++) {
			new_val[i] = jQuery.trim(tmp_val[i]);
		}
		val = new_val;
		element.val(val);
	}
	else
		element.val(val);
}
function getDisplayFieldName(parent_module,value)
{
	return getFile("index.php?module=CustomView&action=CustomViewAjax&file=ShowColumn&type=CustomView&parent_module="+parent_module+"&value="+value+"&mode=DisplayFieldName");
}
function customViewSubmit() {
	updateAllColumnField();
	//crmv@OPER6288
	if (kanban_loaded == false) {
		alert(alert_arr.ERROR+': Kanban');
		return false;
	}
	jQuery('[name="kanban_json"]').val(GroupConditions.getJson(jQuery,'kanban_columns','#kanbanColumns'));
	//crmv@OPER6288e
	if (checkReportFilter()) {
		return checkDuplicate();
	} else {
		return false;
	}
}
function updateAllColumnField()
{
	for(var i=1;i<searchAdvFields.length;i++) {
		updateColumnField(searchAdvFields[i],i);
	}
	return false;
}
function updateColumnField(fieldname,selectsequence)
{
	//crmv@175849 crmv@176811
	var element = jQuery('div#Srch_adv_value'+selectsequence+' *[id="'+fieldname+'"]');
	if (jQuery(element).length == 0) var element = jQuery('div#Srch_adv_value'+selectsequence+' *[name="'+fieldname+'"],div#Srch_adv_value'+selectsequence+' *[name="'+fieldname+'[]"]');
	if (jQuery(element).length == 0) return; // crmv@186987
	//crmv@175849e crmv@176811e
	var type = jQuery(element).get(0).type;
 	if (type == 'checkbox') {
 		val = jQuery(element).prop('checked');
 		if (val == true) val = 'Yes';
 		else val = 'No';
 	}
 	else if (type == 'select-one') {
 		if (fieldname == 'assigned_user_id') {
	 		var fieldname1 = 'assigned_user_id';
	 		var fieldname2 = 'assigned_group_id';
	 		assigntype = jQuery('div#Srch_adv_value'+selectsequence+' input:radio[name="assigntype"]:checked').val();
	 		if (assigntype == 'U') fieldname = fieldname1;
	 		else if (assigntype == 'T') fieldname = fieldname2;
	 	//crmv@22220
	 		val = jQuery('div#Srch_adv_value'+selectsequence+' select[name="'+fieldname+'"] option:selected').text().trim();
 		} else {
 			val = jQuery('div#Srch_adv_value'+selectsequence+' select[name="'+fieldname+'"] option:selected').val().trim();
		}
		//crmv@22220e
 	}
 	else if (type == 'select-multiple') {
 		var list = "";
		jQuery('div#Srch_adv_value'+selectsequence+' *[name="'+fieldname+'[]"] option:selected').each(function(){
		  list += this.value + ", ";
		});
		val = list.substr(0, list.length - 2);
 	}
	//crmv@60177 crmv@166849
	else if (fieldname == 'assigned_user_id'+'_'+selectsequence) {
		var fieldname1 = 'assigned_user_id'+'_'+selectsequence;
		var fieldname2 = 'assigned_group_id'+'_'+selectsequence;
		var assigned_user_id_type = fieldname1 + '_type';
		assigntype = jQuery('div#Srch_adv_value'+selectsequence+' #'+assigned_user_id_type+' option:selected').val();
		if (assigntype == 'U') fieldname = fieldname1;
		else if (assigntype == 'T') fieldname = fieldname2;
		val = jQuery('div#Srch_adv_value'+selectsequence+' #'+fieldname).val();
	}
	//crmv@60177e crmv@166849e
 	else
 		val = jQuery(element).val();
 	jQuery("#fval"+selectsequence).val(val);
}
//crmv@29615e

//crmv@31775
function return_report_to_cv(id,name) {
	document.forms['EditView'].report.value=id;
	document.forms['EditView'].report_display.value=name;
	disableReferenceField(document.forms['EditView'].report_display);
}
function popupReport(mode,module,title) {
	var url = '';
	if (mode == 'edit') {
		var reportid = getObj('report').value;
		if (reportid == '') {
			return false;
		}
		var arg = 'index.php?module=Reports&action=ReportsAjax&file=NewReport1&return_module=CustomView&skipsecmodule=1&reportmodule='+module+'&reportname='+title+'&record='+reportid;
	} else {
		var arg = 'index.php?module=Reports&action=ReportsAjax&file=NewReport0&return_module=CustomView&reportmodule='+module+'&reportname='+title;
	}
	openPopup(arg);
}
function checkReportFilter() {
	var setStatus = getObj('setStatus').checked;
	if (setStatus == true) {
		var reportid = getObj('report').value;
		var res = getFile("index.php?module=Reports&action=ReportsAjax&file=CheckReportFilter&report="+reportid);
		if (res != 'SUCCESS') {
			alert(res);
			return false;
		}
	}
	return true;
}
//crmv@31775e

//crmv@34627
function reloadColumns(module,reportid) {
	var params = '';
	var columns = {};
	var column = 0;
	jQuery('select[id^="column"] option:selected').each(function(){
		columns[column] = jQuery(this).val();
		column++
	});
	jQuery("#cv_columns").css({ opacity: 0.5 });
	jQuery.ajax({
		url: 'index.php?module='+module+'&action='+module+'Ajax&file=CustomView&mode=report_columns&record='+getObj('record').value+'&reportid='+reportid+'&columns='+encodeURIComponent(JSON.stringify(columns)),
		type: 'POST',
		dataType: 'html',
		success: function(data){
			jQuery('#cv_columns').html(data);
			jQuery("#cv_columns").css({ opacity: 1 });
		}
	});
}
//crmv@34627e

// crmv@171507
function validateAdvRule(event){
	var title = jQuery('#title').val().trim();
	if(title == ""){
		event.preventDefault();
		vtealert(alert_arr.LBL_SET_ADVRULE_NAME,function(){
			jQuery('#title').focus();
		});
	}
	else{
		var check = 0, error = false;
		for(i=1;i<=5;i++){
			if(i>1 && jQuery('#fcol'+i).val() != "" && jQuery('#fcol'+(i-1)).val() == ""){
				event.preventDefault();
				vtealert(alert_arr.LBL_SET_ADVRULES_IN_ORDER);
				error = true;
				break;
			}
			else if(jQuery('#fcol'+i).val() != ""){
				if(jQuery('#fop'+i).val() != ""){
					check++;
				}
				else{
					event.preventDefault();
					vtealert(alert_arr.LBL_SET_ADVRULE_OPERATOR+i);
					error = true;
					break;
				}
			}
		}
		if(!error){
			if(check == 0){
				event.preventDefault();
				vtealert(alert_arr.LBL_SET_ONE_ADVRULE);
			}
			else{
				jQuery('form#customview_form input[name=record]').val('');
				updateAllColumnField();
			}
		}
	}
}
//crmv@171507e