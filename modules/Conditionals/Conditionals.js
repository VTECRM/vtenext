/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@155145 */

window.VTE = window.VTE || {};

var ConditionalsUtils = ConditionalsUtils || {
	
	ajaxCall: function(service, params, options, callback) {
		var me = this;
			
		params = jQuery.extend({}, {
			displayVersion: false,
			versionContainer: 'conditionalsVersion',
		}, params || {});
		
		options = jQuery.extend({}, options || {});
		
		var url = 'index.php?module=Conditionals&action=ConditionalsAjax&file=ConditionalsUtilsAjax&ajax=true&parenttab=Settings&sub_mode='+service;
		
		jQuery('#status').show();
		jQuery.ajax({
			url: url,
			method: 'POST',
			data: params,
			success: function(response) {
				if (params.displayVersion && params.versionContainer) {
					jQuery("#"+params.versionContainer).html(response);
				}
				jQuery('#status').hide();
				if (typeof callback == 'function') callback(response);
			}
		});
	},
	
	closeVersion: function(callback) {
		var me = this;
		me.ajaxCall('closeVersion', {displayVersion: true}, {}, function(response){
			if (typeof callback == 'function') callback();
		});
	},
	
	exportVersion: function() {
		var me = this,
			module = jQuery('input[name=fld_module]').val(),
			url = 'index.php?module=Conditionals&action=ConditionalsAjax&file=ConditionalsUtilsAjax&sub_mode=exportVersion';
		
		me.ajaxCall('checkExportVersion', {}, {}, function(response){
			if (response != '') alert(response);
			else location.href = url;
		});
	},
	
	verify_data_conditionals: function(form, callback, checkFields) { // crmv@190416
		// crmv@42024
		var me = this,
			count = jQuery('#proTab tr:visible').length;	//crmv@45813
		getObj('total_conditions').value = count;
		if (typeof(checkFields) == 'undefined') var checkFields = false; // crmv@190416

		var isError = false;
		var errorMessage = "";
		if (trim(form.workflow_name.value) == "") {
			isError = true;
			errorMessage += alert_arr.MISSING_REQUIRED_FIELDS+"\n"+alert_arr.LBL_FPOFV_RULE_NAME;
			oField_miss = form.workflow_name;
		}
		if (count <= 0) {
			isError = true;
			errorMessage += "\n"+alert_arr.LBL_LEAST_ONE_CONDITION;
			oField_miss = form.workflow_name;
		}
		// crmv@190416
		if (checkFields) {
			var field_count = jQuery('[name="field_permissions_table"]').find(':checked').length
				- jQuery('[name="FpovManaged"]:checked').length
				- jQuery('[name="FpovReadPermission"]:checked').length
				- jQuery('[name="FpovWritePermission"]:checked').length
				- jQuery('[name="FpovMandatoryPermission"]:checked').length
			if (field_count <= 0) {
				isError = true;
				errorMessage += "\n"+alert_arr.LBL_LEAST_ONE_FIELD;
				oField_miss = form.workflow_name;
			}
		}
		// crmv@190416e
		// crmv@42024e
		if (isError == true) {
			me.set_fieldfocus(errorMessage,oField_miss);
			return;
		}
		me.ajaxCall('checkDuplicates', {'ruleid':form.ruleid.value,'rulename':form.workflow_name.value}, {}, function(response){
			if (response == 'duplicated') {
				me.set_fieldfocus(alert_arr.LBL_FPOFV_RULE_NAME_DUPLICATED,form.workflow_name);
			} else if (response == '') {
				//form.submit();
				if (typeof callback == 'function') callback();
			} else {
				alert('Error');
			}
		});
	},
	//crmv@17715
	set_fieldfocus: function(errorMessage,oMiss_field) {
		alert(trim(errorMessage));
		oMiss_field.focus();
	},
	//crmv@17715e
}

VTE.Settings = VTE.Settings || {};

VTE.Settings.Conditionals = VTE.Settings.Conditionals || {

	getListViewEntries_js: function(module, url) {
		jQuery("#status").show();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Conditionals&action=ConditionalsAjax&file=ListView&ajax=true&'+url,
			success: function(result) {
				jQuery("#status").hide();
				jQuery("#ListViewContents").html(result);
			}
		});
	}

};

// crmv@192033
function load_field_permissions_table(){

	var url = "";
	url += "&chk_module="+escape(getObj('module_name').value);
	
	var data = "file=EditViewAjax&module=Conditionals&action=ConditionalsAjax"+url;
	getObj("field_permissions_table").innerHTML = "loading...";
	
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: data,
		success: function(result) {
			getObj("field_permissions_table").innerHTML = result;
			getObj("field_permissions_table").style.display = "inline";
		}
	});
	getObj("field_permissions_table").style.display = "inline";
}

function fnAddProductRow(module,chk_fieldname,chk_criteria_id,chk_field_value){
	getObj('workflow_loading').style.display='block';
	getObj('add_rule').style.display='none';
	rowCnt++;
	var tableName = document.getElementById('proTab');
	var prev = tableName.rows.length;
	var count = eval(prev);//As the table has two headers, we should reduce the count
	var row = tableName.insertRow(prev);
	row.id = "row"+count;
	row.style.verticalAlign = "top";
	url = 'module=Conditionals&action=ConditionalsAjax&file=GetConditionalRow&conditional_module='+module+'&rowCnt='+count;
	if (chk_fieldname != undefined) url += '&chk_fieldname='+chk_fieldname;
	if (chk_criteria_id != undefined) url += '&chk_criteria_id='+chk_criteria_id;
	if (chk_field_value != undefined) url += '&chk_field_value='+chk_field_value;
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: url,
		success: function(result) {
			jQuery('#'+row.id).html(result);	//crmv@17715
	        getObj('workflow_loading').style.display='none';
	        getObj('add_rule').style.display='block';
		}
	});
	return count;
}
// crmv@192033e

function deleteRow(i) {
	rowCnt--;
	document.getElementById("row"+i).style.display = 'none';
	document.getElementById('deleted'+i).value = 1;
}
// crmv@77249
function resetConditions(module, field) {
	jQuery('#proTab').html('');	//crmv@18373
	rowCnt = 0;
	fnAddProductRow(module, field);
	getObj("field_permissions_table").innerHTML = '';
	getObj("field_permissions_table").style.display = 'none';
}
// crmv@77249e