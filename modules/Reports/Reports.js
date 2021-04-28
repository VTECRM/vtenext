/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@98500 crmv@192033 */

function trimfValues(value)
{
    var string_array;
    string_array = value.split(":");
    return string_array[4];
}

function verify_data(form) {
	var isError = false;
	var errorMessage = "";
	if (trim(form.folderName.value) == "") {
		isError = true;
		errorMessage += "\nFolder Name";
	}
	// Here we decide whether to submit the form.
	if (isError == true) {
		alert(alert_arr.MISSING_FIELDS + errorMessage);
		return false;
	}
	return true;
}

function re_dateValidate(fldval,fldLabel,type) {
	if(re_patternValidate(fldval,fldLabel,"DATE")==false)
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
		case 11 :if (dd>30) {
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

//Copied from general.js and altered some lines. becos we cant send vales to function present in general.js. it accept only field names.
function re_patternValidate(fldval,fldLabel,type) {
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

	if (type.toUpperCase()=="TIMESECONDS") {//TIME validation
		var re = new RegExp("^([0-1][0-9]|[2][0-3]):([0-5][0-9]):([0-5][0-9])$");
	}
	if (!re.test(fldval)) {
		alert(alert_arr.ENTER_VALID + fldLabel)
		return false
	}
	else return true
}

//added to fix the ticket #5117
function standardFilterDisplay()
{
	if(document.NewReport.stdDateFilterField.options.length <= 0 || (document.NewReport.stdDateFilterField.selectedIndex > -1 && document.NewReport.stdDateFilterField.options[document.NewReport.stdDateFilterField.selectedIndex].value == "Not Accessible"))
	{
		getObj('stdDateFilter').disabled = true;
		getObj('startdate').disabled = true;getObj('enddate').disabled = true;
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

function showReportTab(tabname, tdtab) {
	var tablist = ['trReportCount', 'trReportTotal', 'trReportMain', 'trReportCharts']; // crmv@30014
	var tabtdlist = ['tdTabReportCount', 'tdTabReportTotal', 'tdTabReportMain', 'tdTabReportCharts']; // crmv@30014
	var tdid = tdtab.id;

	for (var i=0; i<tablist.length; ++i) {
		if (tabname == tablist[i]) {
			jQuery('#'+tablist[i]).show();
		} else {
			jQuery('#'+tablist[i]).hide();
		}
	}

	for (var i=0; i<tabtdlist.length; ++i) {
		if (tdid == tabtdlist[i]) {
			jQuery('#'+tabtdlist[i]).removeClass('dvtUnSelectedCell').addClass('dvtSelectedCell');
		} else {
			jQuery('#'+tabtdlist[i]).removeClass('dvtSelectedCell').addClass('dvtUnSelectedCell');
		}
	}
	
	// crmv@96742
	
	// recalc the fixed header, since might be hidden
	var tables = jQuery.fn.dataTable.tables({visible: false, api: true});
	if (tables && tables.fixedHeader) {
		tables.fixedHeader.adjust();
	}
	
	// crmv@82770
	if (tabname == 'trReportCharts' && window.VTECharts) {
		VTECharts.refreshAll();
	}
	// crmv@82770e
}

// crmv@31209
function getActiveTab() {
	var tablist = ['tdTabReportCount', 'tdTabReportTotal', 'tdTabReportMain', 'tdTabReportCharts']; // crmv@30014

	for (var i=0; i<tablist.length; ++i) {
		if (jQuery('#'+tablist[i]).hasClass('dvtSelectedCell')) return tablist[i].replace('tdTabReport', '');
	}
	return '';
}
// crmv@31209

//crmv@44323
function budgetParams(id) {
	var bpu_check = jQuery('#budgetPerUser').is(':checked') ? 'true' : 'false';
	return '&budgetPeriod='+encodeURIComponent(jQuery('#budgetPeriod').val())+
			'&budgetSubperiod='+encodeURIComponent(jQuery('#budgetSubperiod').val())+
			'&budgetPerUser='+encodeURIComponent(bpu_check);
}

function reloadBudget(clearsp) {
	var url = location.href,
		bpu_check = jQuery('#budgetPerUser').is(':checked') ? 'true' : 'false';
	location.href = url.replace(/&budgetPeriod=.+&?/, '').replace(/&budgetSubperiod=.+&?/, '') +
		'&budgetPeriod=' + jQuery('#budgetPeriod').val() +
		'&budgetSubperiod=' + (clearsp ? '' : jQuery('#budgetSubperiod').val())+
		'&budgetPerUser='+encodeURIComponent(bpu_check);
}
//crmv@44323e

function createrepFolder(oLoc,divid)
{
	jQuery('#editfolder_info').html(' '+ReportLabels.LBL_ADD_NEW_GROUP+' ');
	getObj('fldrsave_mode').value = 'save';
	jQuery('#folder_id').val('');
	jQuery('#folder_name').val('');
	jQuery('#folder_desc').val('');
	fnvshobj(oLoc,divid);
}

function DeleteFolder(id)
{
	var title = 'folder'+id;
	var fldr_name = getObj(title).innerHTML;
		if(confirm(ReportLabels.DELETE_FOLDER_CONFIRMATION+fldr_name +"' ?"))
	{
		jQuery.ajax({
			url: 'index.php',
			method: 'post',
			data: 'action=ReportsAjax&mode=ajax&file=DeleteReportFolder&module=Reports&record='+id,
			success: function(result) {
				var item = trim(result);
				if(item.charAt(0)=='<')
					getObj('customizedrep').innerHTML = item;
				else
					alert(item);
			}
		});
	}
	else
	{
		return false;
	}
}

function AddFolder()
{
	if(getObj('folder_name').value.replace(/^\s+/g, '').replace(/\s+$/g, '').length==0)
	{
                alert(ReportLabels.FOLDERNAME_CANNOT_BE_EMPTY);
                return false;
	}
	else if(getObj('folder_name').value.replace(/^\s+/g, '').replace(/\s+$/g, '').length > 20 )
	{
                alert(ReportLabels.FOLDER_NAME_ALLOW_20CHARS);
                return false;
	}
	else if((getObj('folder_name').value).match(/['"<>/\+]/) || (getObj('folder_desc').value).match(/['"<>/\+]/))
    {
            alert(alert_arr.SPECIAL_CHARS+' '+alert_arr.NOT_ALLOWED+alert_arr.NAME_DESC);
            return false;
    }
	/*else if((!CharValidation(getObj('folder_name').value,'namespace')) || (!CharValidation(getObj('folder_desc').value,'namespace')))
	{
			alert(alert_arr.NO_SPECIAL +alert_arr.NAME_DESC);
			return false;
	}*/
	else
	{
		var foldername = encodeURIComponent(getObj('folder_name').value);
		jQuery.ajax({
			url: 'index.php',
			method: 'post',
			data: 'action=ReportsAjax&mode=ajax&file=CheckReport&module=Reports&check=folderCheck&folderName='+foldername,
			success: function(result) {
				var folderid = getObj('folder_id').value;
				var resresult =result.split("::");
				var mode = getObj('fldrsave_mode').value;
				if(resresult[0] != 0 &&  mode =='save' && resresult[0] != 999)
				{
					alert(ReportLabels.FOLDER_NAME_ALREADY_EXISTS);
					return false;
				}
				else if(((resresult[0] != 1 && resresult[0] != 0) || (resresult[0] == 1 && resresult[0] != 0 && resresult[1] != folderid )) &&  mode =='Edit' && resresult[0] != 999)
					{
												alert(ReportLabels.FOLDER_NAME_ALREADY_EXISTS);
												return false;
					}
				else if(result == 999) // 999 check for special chars
					{
												alert(ReportLabels.SPECIAL_CHARS_NOT_ALLOWED);
												return false;
					}
				else
					{
						fninvsh('orgLay');
						var folderdesc = encodeURIComponent(getObj('folder_desc').value);
						getObj('folder_name').value = '';
						getObj('folder_desc').value = '';
						foldername = foldername.replace(/^\s+/g, '').replace(/\s+$/g, '');
						foldername = foldername.replace(/&/gi,'*amp*');
						folderdesc = folderdesc.replace(/^\s+/g, '').replace(/\s+$/g, '');
						folderdesc = folderdesc.replace(/&/gi,'*amp*');
						if(mode == 'save')
						{
							url ='&savemode=Save&foldername='+foldername+'&folderdesc='+folderdesc;
						}
						else
						{
							var folderid = getObj('folder_id').value;
							url ='&savemode=Edit&foldername='+foldername+'&folderdesc='+folderdesc+'&record='+folderid;
						}
						getObj('fldrsave_mode').value = 'save';
						jQuery.ajax({
							url: 'index.php',
							method: 'post',
							data: 'action=ReportsAjax&mode=ajax&file=SaveReportFolder&module=Reports'+url,
							success: function(result) {
								getObj('customizedrep').innerHTML = result;
							}
						});
					}
				}
			}
		);
	}
}


function EditFolder(id,name,desc)
{
	jQuery('#editfolder_info').html(' '+ReportLabels.LBL_RENAME_FOLDER+' ');
	getObj('folder_name').value = name;
	getObj('folder_desc').value = desc;
	getObj('folder_id').value = id;
	getObj('fldrsave_mode').value = 'Edit';
}

// crmv@30967
function massDeleteReport() {
	var repids = [];
	jQuery('.report_form input[id^=check_report]:checked').each(function(idx, el) {
		repids.push(el.id.replace('check_report_', ''));
	});

	var idstring = '';
	var count = repids.length;
	idstring = repids.join(':');

	if(idstring != '')
	{
		if(confirm(ReportLabels.DELETE_CONFIRMATION+" "+count+alert_arr.RECORDS))
		{
			jQuery.ajax({
				url: 'index.php',
				method: 'post',
				data: 'action=ReportsAjax&mode=ajax&file=Delete&module=Reports&idlist='+idstring,
				success: function(result) {
					if (result.match(/ERROR::.*/)) {
						window.alert('Access Denied');
					} else {
						location.reload();
					}
				}
			});
		}else {
			return false;
		}

	}else {
			alert(ReportLabels.SELECT_ATLEAST_ONE_REPORT);
			return false;
	}
}

function DeleteReport(id) {
	if (confirm(ReportLabels.DELETE_REPORT_CONFIRMATION))
	{
		jQuery.ajax({
			url: 'index.php',
			method: 'post',
			data: 'action=ReportsAjax&file=Delete&module=Reports&record='+id,
			success: function(result) {
				if (result.match(/ERROR::.*/)) {
					window.alert('Access Denied');
				} else {
					location.reload();
				}
			}
		});
	} else {
		return false;
	}
}

function showMoveReport(elem) {

	var selcount = jQuery('.report_form input[id^=check_report]:checked').length;
	if (selcount == 0) {
		return window.alert(ReportLabels.SELECT_ATLEAST_ONE_REPORT);
	} else {
		return showFloatingDiv('ReportMove', elem);
	}
}

function MoveReport(id,foldername) {
	var repids = [];
	jQuery('.report_form input[id^=check_report]:checked').each(function(idx, el) {
		repids.push(el.id.replace('check_report_', ''));
	});

	var idstring = repids.join(':');

	if (idstring != '') {
		var destid = jQuery('#select_move_report').val();
		if (destid > 0) {
			jQuery.ajax({
				url: 'index.php',
				method: 'post',
				data: 'action=ReportsAjax&mode=ajax&file=ChangeFolder&module=Reports&folderid='+destid+'&idlist='+idstring,
				success: function(result) {
					location.reload();
				}
			});
		}
	}else {
			alert(ReportLabels.SELECT_ATLEAST_ONE_REPORT);
			return false;
	}

}
// crmv@30967e

function CrearEnlace(tipo,id, ajaxfile, useFilters) { // crmv@177381
	var stdDateFilterFieldvalue = '';
	if(document.NewReport.stdDateFilterField.selectedIndex != -1)
		stdDateFilterFieldvalue = document.NewReport.stdDateFilterField.options  [document.NewReport.stdDateFilterField.selectedIndex].value;

	var stdDateFiltervalue = '';
	if(document.NewReport.stdDateFilter.selectedIndex != -1)
		stdDateFiltervalue = document.NewReport.stdDateFilter.options[document.NewReport.stdDateFilter.selectedIndex].value;

	var ajaxparam = '';
	if (tipo == 'ReportsAjax' && ajaxfile != undefined && ajaxfile != '') ajaxparam = '&file='+ajaxfile;

	// crmv@177381
	var url = "index.php?module=Reports&action="+tipo+ajaxparam+"&record="+id+
		"&stdDateFilterField="+stdDateFilterFieldvalue+"&stdDateFilter="+stdDateFiltervalue+
		"&startdate="+document.NewReport.startdate.value+"&enddate="+document.NewReport.enddate.value+
		//"&columns="+encodeURIComponent(ReportTable.table.search())+
		"&folderid="+getObj('folderid').value+
		getSdkParams(id);	//crmv@sdk-25785
	
	if (useFilters) {
		var aparams = ReportTable.table.ajax.params();
		if (aparams) {
			url += 
				"&"+jQuery.param({search:aparams['search']})+
				"&"+jQuery.param({columns:aparams['columns']})+
				"&"+jQuery.param({order:aparams['order']});
		}
	}
	
	return url;
	// crmv@177381e
}

/**
 * @deprecated
 * Please use Report.refreshTable
 */
function generateReport(id) {
	return Reports.refreshTables(id);
}

function ReportInfor() {
	// crmv@49622
	if (typeof window.report_info_override != 'undefined' && window.report_info_override !== null) {
		getObj('report_info').innerHTML = window.report_info_override;
		return;
	}
	// crmv@49622e
	var stdDateFilterFieldvalue = '';
	if(document.NewReport.stdDateFilterField.selectedIndex != -1)
		stdDateFilterFieldvalue = document.NewReport.stdDateFilterField.options  [document.NewReport.stdDateFilterField.selectedIndex].text;

	var stdDateFiltervalue = '';
	if(document.NewReport.stdDateFilter.selectedIndex != -1)
		stdDateFiltervalue = document.NewReport.stdDateFilter.options[document.NewReport.stdDateFilter.selectedIndex].text;

	var startdatevalue = document.NewReport.startdate.value;
	var enddatevalue = document.NewReport.enddate.value;

	if(startdatevalue != '' && enddatevalue=='')
	{
		var reportinfr = 'Reporting  "'+stdDateFilterFieldvalue+'"   (from  '+startdatevalue+' )';
	}else if(startdatevalue == '' && enddatevalue !='')
	{
		var reportinfr = 'Reporting  "'+stdDateFilterFieldvalue+'"   (  till  '+enddatevalue+')';
	}else if(startdatevalue == '' && enddatevalue =='')
	{
        var reportinfr = ReportLabels.NO_FILTER_SELECTED;
	}else if(startdatevalue != '' && enddatevalue !='')
	{
		var reportinfr = ReportLabels.LBL_REPORTING+' "'+stdDateFilterFieldvalue+'" '+alert_arr.LBL_WITH+' "'+stdDateFiltervalue+'"  ( '+startdatevalue+'  to  '+enddatevalue+' )'; // crmv@29686
	}
	getObj('report_info').innerHTML = reportinfr;
}

/**
 * New interface for reports methods
 * TODO: slowly, move all the functions here
 */
var Reports = {
	
	initialize: function() {

		var filter = getObj('stdDateFilter').options[document.NewReport.stdDateFilter.selectedIndex].value;
		if (filter != "custom") {
			showDateRange( filter );
		}
		
		// If current user has no access to date fields, we should disable selection
		// Fix for: #4670
		standardFilterDisplay();
		ReportInfor();	
	},
	
	getReportid: function() {
		return document.NewReport.record.value;
	},
	
	openPopup: function(url) {
		openPopup(url, "ReportWindow","width=790px,height=630px,scrollbars=yes", 'auto'); //crmv@21048m
	},
	
	createNew: function(folderid, duplicateFrom, formodule) {
		var arg ='index.php?module=Reports&action=ReportsAjax&file=EditReport';
		if (folderid) arg += '&folder='+folderid;
		if (duplicateFrom) arg += '&duplicate='+duplicateFrom;
		if (formodule) arg += '&formodule='+formodule;
		this.openPopup(arg);
	},
	
	editReport: function(reportid) {
		if (!reportid) return;
		var arg ='index.php?module=Reports&action=ReportsAjax&file=EditReport&record='+reportid;
		this.openPopup(arg);
	},
	
	// crmv@177381
	/**
	 * True if the table is filtered/ordered
	 */
	hasFilters: function() {
		var me = this,
			aparams = ReportTable.table && ReportTable.table.ajax.params();
			
		if (!aparams) return;
		
		// global search
		if (aparams.search.value != '') return true;
		
		// column search
		if (aparams.columns.length > 0) {
			for (var i=0; i<aparams.columns.length; ++i) {
				var col = aparams.columns[i];
				if (col.search && col.search.value != '') return true;
			}
		}
		
		// column ordering
		if (aparams.order.length > 0) return true;
		
		return false;
	},
	
	onExportMainChange: function(self) {
		var on = jQuery(self).is(':checked');
		
		if (on) {
			jQuery('#export_with_filters').attr('disabled', false);
		} else {
			jQuery('#export_with_filters').prop('checked', false).attr('disabled', true);
		}
	},
	// crmv@177381
	
	showExportOptions: function(reportid, type) {
		var me = this;
		
		var types = ['print', 'pdf', 'xls'];

		var hasSummary = parseInt(jQuery('#reportHasSummary').val());
		var hasTotals = parseInt(jQuery('#reportHasTotals').val());
		var hasFilters = me.hasFilters(); // crmv@177381

		if (!hasSummary && !hasTotals && !hasFilters) { // crmv@177381
			// call directly export function
			return me.startExport(reportid, type);
		}

		showFloatingDiv('ReportExport');

		for (var i=0; i<types.length; ++i) {
			if (types[i] == type) {
				jQuery('#report_export_button_'+types[i]).show();
				jQuery('#report_choose_button_'+types[i]).show();
			} else {
				jQuery('#report_export_button_'+types[i]).hide();
				jQuery('#report_choose_button_'+types[i]).hide();
			}
		}
		
		jQuery('#export_with_filters').prop('checked', hasFilters); // crmv@177381
	},
	
	startExport: function(reportid, type) {
		var extraopts = jQuery('#export_report_list').serialize();
		var useFilters = jQuery('#export_with_filters').is(':checked'); // crmv@177381
		
		if (extraopts == '') {
			return window.alert(ReportLabels.LBL_CHOOSE_EMPTY);
		} else {
			extraopts = '&'+extraopts;
		}

		// crmv@177381
		switch (type) {
			case 'pdf': 
				document.location.href = CrearEnlace('CreatePDF',reportid, '', useFilters) + extraopts; 
				break;
			case 'xls': 
				document.location.href = CrearEnlace('CreateXL',reportid, '', useFilters) + extraopts;
				break;
			case 'print':
				window.open(
					CrearEnlace('ReportsAjax',reportid,'PrintReport', useFilters) + extraopts, 
					ReportLabels.LBL_Print_REPORT,
					"width=800,height=650,resizable=1,scrollbars=1,left=100"
				);
				break;
		}
		// crmv@177381e

		hideFloatingDiv('ReportExport');
	},
	
	validateStdFilter: function() {
		var me = this;
		
		var stdDateFilterFieldvalue = jQuery('#stdDateFilterField').val();
		var stdDateFiltervalue = jQuery('#stdDateFilter').val();
		var startdatevalue = document.NewReport.startdate.value;
		var enddatevalue = document.NewReport.enddate.value;
		
		if ((startdatevalue != '') || (enddatevalue != '')) {

			if(!dateValidate("startdate","Start Date","D"))
				return false

			if(!dateValidate("enddate","End Date","D"))
				return false

			if(!dateComparison("startdate",'Start Date',"enddate",'End Date','LE'))
				return false;
		}
		
		return true;
	},
	
	refreshTables: function(reportid) {
		var me = this;
		
		if (!me.validateStdFilter()) return false;
		
		jQuery("#status").show();
		jQuery.ajax({
			method: 'POST',
			url: 'index.php?action=ReportsAjax&file=SaveAndRun&mode=ajax&module=Reports&record='+reportid+"&folderid="+getObj('folderid').value,
			data: me.getExtraAjaxParams(),
			success: function(response) {
				jQuery("#status").hide();
				jQuery('#Generate').html(response);
				setTimeout("ReportInfor()",1);
			},
			error: function() {
				jQuery("#status").hide();
			}
		});

	},
	
	getExtraAjaxParams: function() {
		var me = this,
			reportid = me.getReportid();
			
		var stdDateFilterFieldvalue = jQuery('#stdDateFilterField').val();
		var stdDateFiltervalue = jQuery('#stdDateFilter').val();
		var startdatevalue = jQuery('#jscal_field_date_start').val();
		var enddatevalue = jQuery('#jscal_field_date_end').val();
		
		var params = {
			stdDateFilterField: stdDateFilterFieldvalue,
			stdDateFilter: stdDateFiltervalue,
			startdate: startdatevalue,
			enddate: enddatevalue,
			tab: getActiveTab(),
		}
		
		var sdkparams = getSdkParams(reportid); //crmv@sdk-25785 crmv@31209
		if (sdkparams) {
			// transform to object
			sdkparams = sdkparams.replace(/^&/, '');
			var sdkobj = JSON.parse('{"' + decodeURI(sdkparams).replace(/"/g, '\\"').replace(/&/g, '","').replace(/=/g,'":"') + '"}');
			jQuery.extend(params, sdkobj || {});
		}
		return params;
	}
	
}

var ReportTable = {
	
	reportid: null,
	folderid: null,
	
	lengthMenu: [20, 50, 100, 200],
	baseUrl: 'index.php?module=Reports&action=ReportsAjax&file=SaveAndRun&mode=ajax&format=json',
	
	table: null,
	summaryTable: null,
	
	initialize: function(reportid, folderid, params, options) {
		var me = this;
		
		me.reportid = reportid;
		me.folderid = folderid;
		
		options = options || {};
		
		me.initMainTable(params, options);
		me.initSummaryTable();
	},
	
	initMainTable: function(params, options) {
		var me = this;
		
		var pageSize = params.pageSize;
		var totalRecords = params.totalRecords;
		var columns = params.columns;
		var order = []; // crmv@132151
		
		// check if column definition is present
		if (!columns || columns.length == 0) return;  // crmv@172034

		if (me.lengthMenu.indexOf(pageSize) == -1) me.lengthMenu.push(pageSize);
		me.lengthMenu.sort(function(a,b) {return a - b;});
		
		// prepare the columns
		jQuery.each(columns, function(idx, col) {
			col.name = col.column;
			col.data = col.column+'.value';
			if (col.sortorder) order.push([idx, col.sortorder.toLowerCase()]); // crmv@132151
		});
		
		// Setup - add a text input to each footer cell
		jQuery('#tableContentMain thead th').each(function(idx) {
			var title = jQuery(this).text();
			if (columns[idx] && columns[idx].searchable === true) { // crmv@172034
				// crmv@118320
				var data = 'data-uitype="'+columns[idx].uitype+'" data-wstype="'+columns[idx].wstype+'"';
				jQuery(this).append('<br><div class="dvtCellInfo"><input class="detailedViewTextBox" type="text" placeholder="'+alert_arr.LBL_SEARCH+' '+title+'" '+data+' /></div>');
				// crmv@118320e
			}
		});

		me.table = jQuery('#tableContentMain').DataTable({
			
			// crmv@201332 - remove inline-form class to have input fields to shrink more
			oClasses: {
				sWrapper: 'dataTables_wrapper dt-bootstrap',
			},
			// crmv@201332e
			
			// paging
			paging: true,
			lengthMenu: me.lengthMenu,
			pageLength: pageSize,
			
			// ordering
			ordering: true,
			order: order, // crmv@132151
			orderMulti: false,	// disabled for the moment
			
			// searching
			searching: true,
			search: {
				caseInsensitive: true,
				smart: false,	// disabled for the moment
			},
			
			fixedHeader: {
				headerOffset: jQuery('#vte_menu').outerHeight(),
			},
			
			// server side processing
			deferLoading: totalRecords,
			processing: true,
			serverSide: true,
			ajax: {
				url: me.baseUrl + '&folderid='+me.folderid+'&record='+me.reportid,
				data: me.appendAjaxData,
				type: 'POST',
				// crmv@106004
				beforeSend: function(){
					//crmv@91082
					if(!SessionValidator.check()) {
						SessionValidator.showLogin();
						jQuery('#modalProcessingDiv').hide();
						jQuery('#status').hide();
						return false;
					}
					//crmv@91082e
				}
				//crmv@106004e
			},
			
			// column definitions
			columns: columns,
			
			// internationalization
			language: {
				url: "include/js/dataTables/i18n/"+(window.current_language || "en_us")+".lang.json"
			},
			
			// apply some transformations after ajax calls
			fnRowCallback: function(tr, aData, iRow, iDisplayIndexFull) {
				for (colname in aData) {
					var cdata = aData[colname];
					if (cdata && cdata.class) {
						var cell = this.api().cell(iRow, colname+':name');
						var node = cell.node();
						// apply the class
						jQuery(node).addClass(cdata.class);
					}
				}
			},

		});
		
		// wait for the table to be initialized
		me.table.on('init.dt', function() {
		
			// handle the generic search on return key, not keypress
			jQuery('.dataTables_filter input').off();
			jQuery('.dataTables_filter input').on('keyup', function(e) {
				if(e.keyCode == 13) {
					me.table.search(this.value).draw();
				}
			});
			
		}).on('preXhr.dt', function() {
			var zindex = jQuery(this).zIndex();
			jQuery('#modalProcessingDiv').css('z-index', zindex+1).show();
			jQuery('#tableContentMain_processing').css('z-index', zindex+2);
			jQuery('#status').show();
		}).on('xhr.dt', function() {
			jQuery('#modalProcessingDiv').hide();
			jQuery('#status').hide();
		});
		
		// Apply the search per column
		me.table.columns().every(function (idx) {
			var that = this,
				header = this.header();
				
			// prevent propagation
			jQuery('input', header).on('click focus', function (event) {
				return false;
			});
 
			// use keypress, since the th has a listener on it and fires the redraw
			jQuery('input', header).on('keypress', function (event) {
				// crmv@118320
				var uitype = parseInt(jQuery(this).data('uitype'));
				var wstype = jQuery(this).data('wstype');
				if (event.type == 'keypress' && event.keyCode == 13 && that.search() !== this.value) {
					if (me.validateColSearch(this.value, uitype, wstype)) {
						var searchvalue = me.alterColSearchValue(this.value, uitype, wstype);
						that.search(searchvalue).draw();
					}
					return false;
				}
				// crmv@118320e
			});
			
		});
	},
	
	// crmv@118320
	validateColSearch: function(value, uitype, wstype) {
	
		if (uitype == 7 || uitype == 9 || uitype == 71 || uitype == 72) {
			// validate as user number
			if (!validateUserNumber(value)) {
				alert(alert_arr.LBL_ENTER_VALID_NO);
				return false;
			}
		}
		
		return true;
	},
	
	alterColSearchValue: function(value, uitype, wstype) {
	
		if (uitype == 7 || uitype == 9 || uitype == 71 || uitype == 72) {
			// convert a user number to a standard float number,but keep it as a string!
			if (thousands_separator != '') value = value.replace(thousands_separator, '');
			if (decimal_separator != '') value = value.replace(decimal_separator, '.');
		}
		
		return value;
	},
	// crmv@118320e
	
	// crmv@177381
	initSummaryTable: function(params, options) {
		var me = this;

		jQuery('#tableContentCount thead th').each(function(idx) {
			var title = jQuery(this).text();
			var data = "";
			jQuery(this).append('<br><div class="dvtCellInfo"><input class="detailedViewTextBox" type="text" placeholder="'+alert_arr.LBL_SEARCH+' '+title+'" '+data+' /></div>');
		});
		
		me.summaryTable = jQuery('#tableContentCount').DataTable({
			paging: true,
			pageLength: 50,
			searching: true,
			ordering: true,
			order: [], // no initial ordering
			processing: false,
			dom: "ltip",
			fixedHeader: {
				headerOffset: jQuery('#vte_menu').outerHeight(),
			},
			language: {
				url: "include/js/dataTables/i18n/"+(window.current_language || "en_us")+".lang.json"
			},
		});
		
		// Apply the search per column
		me.summaryTable.columns().every(function (idx) {
			var that = this,
				header = this.header();
				
			// prevent propagation
			jQuery('input', header).on('click focus', function (event) {
				return false;
			});
 
			// use keypress, since the th has a listener on it and fires the redraw
			jQuery('input', header).on('keypress', function (event) {
				if (event.type == 'keypress' && event.keyCode == 13 && that.search() !== this.value) {
					var searchvalue = this.value;
					that.search(searchvalue).draw();
					return false;
				}
			});
			
		});
		
		me.summaryTable.on('draw.dt', function () {
			// fix values in empty cells
			var prevRow = [];
			var cnt = me.summaryTable.rows({page: 'current'}).every(function(idx, tloop, rLoop) {
				var row = this,
					node = row.node();
				jQuery(node).find('td').each(function(cellidx) {
					var cell = jQuery(this),
						tdEmpty = cell.hasClass('rptEmptyGrp') || cell.hasClass('rptWasEmptyGrp'),
						value = cell.data('search');
						
					if (tdEmpty) {
						if (cell.html() == '' && value !== prevRow[cellidx]) {
							cell.html(cell.data('fullvalue')).removeClass('rptEmptyGrp').addClass('rptWasEmptyGrp');
						} else if (value === prevRow[cellidx]) {
							cell.html('').removeClass('rptWasEmptyGrp').addClass('rptEmptyGrp');
						}
					}
					prevRow[cellidx] = value;
				});
			});
		});
		
	},
	// crmv@177381e
	
	appendAjaxData: function(data) {
		var me = ReportTable;
		
		// alter the data sent to server appending more informations
		var params = Reports.getExtraAjaxParams();
		if (params) {
			jQuery.extend(data, params);
		}
		
	},
	
	/*refreshAll: function(callback) {
		
	},
	
	refreshTable: function(tab, callback) {
		
	}*/
}