/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/


function qcemptyCheck(fldName,fldLabel, fldType) {
	var currObj = window.document.QcEditView[fldName];
	
	if (fldType=="text") {
		if (currObj.value.replace(/^\s+/g, '').replace(/\s+$/g, '').length==0) {
			alert(sprintf(alert_arr.CANNOT_BE_EMPTY, fldLabel));
			currObj.focus();
	     	return false;
		} else{
       		return true;
		}
	//crmv@45428
	} else if (trim(currObj.value) == '' && isdefined('assigned_group_id') && fldName == 'assigned_user_id'){
		var currObj=getObj('assigned_group_id')
		if (trim(currObj.value) == '') {
			alert(sprintf(alert_arr.CANNOT_BE_NONE, fldLabel));
    		return false;
  		} else {
			return true;
		}
	//crmv@45428e
	} else {
		if (trim(currObj.value) == "" ) {
			alert(sprintf(alert_arr.CANNOT_BE_NONE, fldLabel));
	    	return false;
		} else {
			return true;
		}
	}
}

function qcpatternValidate(fldName,fldLabel,type) {
	var currObj=window.document.QcEditView[fldName];
	if (type.toUpperCase()=="EMAIL") //Email ID validation
	{
		var re=new RegExp(/^[a-zA-Z0-9]+([\_\-\.]*[a-zA-Z0-9]+[\_\-]?)*@[a-zA-Z0-9]+([\_\-]?[a-zA-Z0-9]+)*\.+([\_\-]?[a-zA-Z0-9])+(\.?[a-zA-Z0-9]+)*$/);
	}
	
	if (type.toUpperCase()=="DATE") {//DATE validation

		switch (userDateFormat) {
			case "yyyy-mm-dd" : 
								var re = /^\d{4}(\-|\/|\.)\d{1,2}\1\d{1,2}$/
								break;
			case "mm-dd-yyyy" : 
			case "dd-mm-yyyy" : 
								var re = /^\d{1,2}(\-|\/|\.)\d{1,2}\1\d{4}$/								
		}
	}
	
	if (type.toUpperCase()=="TIME") {//TIME validation
		var re = /^\d{1,2}\:\d{1,2}$/;
	}
	
	//Asha: Remove spaces on either side of a Email id before validating
	if (type.toUpperCase()=="EMAIL") currObj.value = trim(currObj.value);
	if (!re.test(currObj.value)) {
		alert(alert_arr.ENTER_VALID+fldLabel);
		currObj.focus();
		return false;
	} else {
		return true;
	}
}

function qcdateValidate(fldName,fldLabel,type) {
	if(qcpatternValidate(fldName,fldLabel,"DATE")==false)
		return false;
	
	var dateval=window.document.QcEditView[fldName].value.replace(/^\s+/g, '').replace(/\s+$/g, '');

	var dateelements=splitDateVal(dateval)
	
	var dd=dateelements[0];
	var mm=dateelements[1];
	var yyyy=dateelements[2];
	
	if (dd<1 || dd>31 || mm<1 || mm>12 || yyyy<1 || yyyy<1000) {
		alert(alert_arr.ENTER_VALID+fldLabel);
		window.document.QcEditView[fldName].focus();
		return false
	}
	
	if ((mm==2) && (dd>29)) {//checking of no. of days in february month
		alert(alert_arr.ENTER_VALID+fldLabel);
		window.document.QcEditView[fldName].focus();
		return false;
	}
	
	if ((mm==2) && (dd>28) && ((yyyy%4)!=0)) {//leap year checking
		alert(alert_arr.ENTER_VALID+fldLabel);
		window.document.QcEditView[fldName].focus();
		return false;
	}

	switch (parseInt(mm)) {
		case 2 : 
		case 4 : 
		case 6 : 
		case 9 : 
		case 11 :
			if (dd>30) {
				alert(alert_arr.ENTER_VALID+fldLabel);
				window.document.QcEditView[fldName].focus();
				return false;
			}	
	}
	
	var currdate=new Date();
	var chkdate=new Date();
	
	chkdate.setYear(yyyy);
	chkdate.setMonth(mm-1);
	chkdate.setDate(dd);
	
	if (type!="OTH") {
		if (!compareDates(chkdate,fldLabel,currdate,"current date",type)) {
			window.document.QcEditView[fldName].focus();
			return false;
		} else return true;
	} else {
		return true;
	}
}

function qcdateComparison(fldName1,fldLabel1,fldName2,fldLabel2,type) {
	var dateval1=window.document.QcEditView[fldName1].value.replace(/^\s+/g, '').replace(/\s+$/g, '');
	var dateval2=window.document.QcEditView[fldName2].value.replace(/^\s+/g, '').replace(/\s+$/g, '');

	var dateelements1=splitDateVal(dateval1);
	var dateelements2=splitDateVal(dateval2);
	
	var dd1=dateelements1[0];
	var mm1=dateelements1[1];
	var yyyy1=dateelements1[2];
	
	var dd2=dateelements2[0];
	var mm2=dateelements2[1];
	var yyyy2=dateelements2[2];
	
	var date1=new Date();
	var date2=new Date();		
	
	date1.setYear(yyyy1);
	date1.setMonth(mm1-1);
	date1.setDate(dd1);	
	
	date2.setYear(yyyy2);
	date2.setMonth(mm2-1);
	date2.setDate(dd2);
	
	if (type!="OTH") {
		if (!compareDates(date1,fldLabel1,date2,fldLabel2,type)) {
			window.document.QcEditView[fldName1].focus();
			return false;
		} else return true;
	} else {
		return true;
	}
}

function qcintValidate(fldName,fldLabel) {
	var val = window.document.QcEditView[fldName].value.replace(/^\s+/g, '').replace(/\s+$/g, '');
	if (isNaN(val) || (val.indexOf(".")!=-1 && fldName != 'potential_amount')) {
		alert(alert_arr.INVALID+fldLabel);
		window.document.QcEditView[fldName].focus();
		return false;
	}
	else if( (fldName != 'employees' || fldName != 'noofemployees') && (val < -2147483648 || val > 2147483647))	{
		alert(fldLabel +alert_arr.OUT_OF_RANGE);
		return false;
	}
	else if((fldName == 'employees' || fldName == 'noofemployees') && (val < 0 || val > 2147483647)) {
		alert(fldLabel +alert_arr.OUT_OF_RANGE);
		return false;
	} else {
		return true;
	}
}

function qcnumConstComp(fldName,fldLabel,type,constval) {
	var val = parseFloat(window.document.QcEditView[fldName].value.replace(/^\s+/g, '').replace(/\s+$/g, ''));
	constval=parseFloat(constval);

	var ret=true;
	switch (type) {
		case "L"  : if (val>=constval) {
						alert(fldLabel+alert_arr.SHOULDBE_LESS+constval);
						ret=false;
					}
					break;
		case "LE" :	if (val>constval) {
					alert(fldLabel+alert_arr.SHOULDBE_LESS_EQUAL+constval);
			        ret=false;
					}
					break;
		case "E"  :	if (val!=constval) {
						alert(fldLabel+alert_arr.SHOULDBE_EQUAL+constval);
                        ret=false;
					}
					break;
		case "NE" : if (val==constval) {
						alert(fldLabel+alert_arr.SHOULDNOTBE_EQUAL+constval);
						ret=false;
					}
					break;
		case "G"  :	if (val<=constval) {
						alert(fldLabel+alert_arr.SHOULDBE_GREATER+constval);
						ret=false;
					}
					break;
		case "GE" : if (val<constval) {
						alert(fldLabel+alert_arr.SHOULDBE_GREATER_EQUAL+constval);
						ret=false;
					}
					break;
	}
	
	if (ret==false) {
		window.document.QcEditView[fldName].focus();
		return false;
	} else {
		return true;
	}
}

function qcdateTimeValidate(dateFldName,timeFldName,fldLabel,type) {
	if(qcpatternValidate(dateFldName,fldLabel,"DATE")==false)
		return false;
	
	var dateval=window.document.QcEditView[dateFldName].value.replace(/^\s+/g, '').replace(/\s+$/g, '');
	var dateelements=splitDateVal(dateval);
	
	var dd=dateelements[0];
	var mm=dateelements[1];
	var yyyy=dateelements[2];
	
	if (dd<1 || dd>31 || mm<1 || mm>12 || yyyy<1 || yyyy<1000) {
		alert(alert_arr.ENTER_VALID+fldLabel);
		window.document.QcEditView[dateFldName].focus();
		return false;
	}
	
	if ((mm==2) && (dd>29)) {//checking of no. of days in february month
		alert(alert_arr.ENTER_VALID+fldLabel);
		window.document.QcEditView[dateFldName].focus();
		return false;
	}
	
	if ((mm==2) && (dd>28) && ((yyyy%4)!=0)) {//leap year checking
		alert(alert_arr.ENTER_VALID+fldLabel);
		window.document.QcEditView[dateFldName].focus();
		return false;
	}

	switch (parseInt(mm)) {
		case 2 : 
		case 4 : 
		case 6 : 
		case 9 : 
		case 11 :
			if (dd>30) {
				alert(alert_arr.ENTER_VALID+fldLabel);
				window.document.QcEditView[dateFldName].focus();
				return false;
			}	
	}
	
	if (qcpatternValidate(timeFldName,fldLabel,"TIME")==false)
		return false;
		
	var timeval=window.document.QcEditView[timeFldName].value.replace(/^\s+/g, '').replace(/\s+$/g, '');
	var hourval=parseInt(timeval.substring(0,timeval.indexOf(":")));
	var minval=parseInt(timeval.substring(timeval.indexOf(":")+1,timeval.length));
	var currObj=window.document.QcEditView[timeFldName];
	
	if (hourval>23 || minval>59) {
		alert(alert_arr.ENTER_VALID+fldLabel);
		currObj.focus();
		return false;
	}
	
	var currdate=new Date();
	var chkdate=new Date();
	
	chkdate.setYear(yyyy);
	chkdate.setMonth(mm-1);
	chkdate.setDate(dd);
	chkdate.setHours(hourval);
	chkdate.setMinutes(minval);
	
	if (type!="OTH") {
		if (!compareDates(chkdate,fldLabel,currdate,"current date & time",type)) {
			window.document.QcEditView[dateFldName].focus();
			return false;
		} else {
			return true;
		}
	} else {
		return true;
	}
}
