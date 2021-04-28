/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@199421 */

function role_selection_change() {
	var obj = getObj("st_table_content");
	obj.style.display = "none";
	getStTable(true);
}

function module_selection_change() {
	var obj = getObj("st_table_content");
	obj.style.display = "none";
	var module_name_obj = getObj("module_name");
	var role_check_obj = getObj("role_check");
	var field_check_obj = getObj("status_field");
	if (module_name_obj.value == "-1") {
		hide_all();
	} else {
		getObj("field_line").style.visibility = "visible";
		getStField(module_name_obj.value, "");
	}
}

function hide_all() {
	getObj("field_line").style.visibility = "collapse";
	getObj("roles_line").style.visibility = "collapse";
}

function status_field_selection_change() {
	var status_field_obj = getObj("status_field");
	var module_name_obj = getObj("module_name");
	getStField(module_name_obj.value, status_field_obj.value);
}

function getStField(module, field) {
	var url = "&module_name=" + module + "&field=" + field;

	jQuery("#status").show();

	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: "module=Transitions&action=TransitionsAjax&file=LoadField&ajax=true" + url,
		dataType: 'json',
		success: function(result) {
			jQuery("#status").hide();

			jQuery("#field_select").html(result["picklist_fields"]);
			document.getElementById("unmake_field_transition").style.visibility = "collapse";
			document.getElementById("make_field_transition").style.visibility = "collapse";
			var status_field_obj = getObj("status_field");
			if (status_field_obj.value == "-1") {
				getObj("roles_line").style.visibility = "collapse";
				jQuery("#st_table_content").hide();
			} else {
				if (result["is_managed"]) {
					getObj("roles_line").style.visibility = "visible";
					document.getElementById("unmake_field_transition").style.visibility = "visible";
					getStTable(false);
				} else {
					getObj("roles_line").style.visibility = "collapse";
					jQuery("#st_table_content").hide();
					document.getElementById("unmake_field_transition").style.visibility = "collapse";
					document.getElementById("make_field_transition").style.visibility = "visible";
				}
			}
		}
	});
}

function dotransition(module, field) {
	var url = "&module_name=" + module + "&field=" + field;
	
	jQuery("#status").show();

	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: "module=Transitions&action=TransitionsAjax&file=doTransition&ajax=true" + url,
		dataType: 'json',
		success: function(result) {
			jQuery("#status").hide();

			if (result["success"]) {
				var status_field_obj = getObj("status_field");
				if (status_field_obj.value == "-1") {
					getObj("roles_line").style.visibility = "collapse";
					jQuery("#st_table_content").hide();
					document.getElementById("unmake_field_transition").style.visibility = "collapse";
					document.getElementById("make_field_transition").style.visibility = "collapse";
				} else {
					document.getElementById("make_field_transition").style.visibility = "collapse";
					document.getElementById("unmake_field_transition").style.visibility = "visible";
					getObj("roles_line").style.visibility = "visible";
					getStTable(true);
				}
			} else {
				alert(result["msg"]);
			}
		}
	});
}

function makefieldTransition() {
	var status_field_obj = getObj("status_field");
	var module_name_obj = getObj("module_name");
	dotransition(module_name_obj.value, status_field_obj.value);
}

function unmakefieldTransition() {
	var status_field_obj = getObj("status_field");
	status_field_obj.value = "-1";
	var module_name_obj = getObj("module_name");
	dotransition(module_name_obj.value, status_field_obj.value);
}

function getStTable(alert_flag) {
	var module_name_obj = getObj("module_name");
	var field_name_obj = getObj("status_field");
	var role_check_obj = getObj("role_check");

	if (role_check_obj.value == "-1") {
		if (alert_flag) alert(alert_arr.LBL_STATUS_PLEASE_SELECT_A_ROLE);
		return;
	}

	jQuery("#st_table_content").hide();

	var url = "&module_name=" + module_name_obj.value + "&roleid=" + role_check_obj.value + "&field=" + field_name_obj.value;
	
	jQuery("#status").show();

	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: "module=Transitions&action=TransitionsAjax&file=ListView&ajax=true&" + url,
		success: function(result) {
			jQuery("#status").hide();
			jQuery("#st_table_content").html(result);
			jQuery("#st_table_content").show();
			var width = eval(jQuery(".settingsSelectedUI").width());
			jQuery("#rule_table").width(width - 17);
			//crmv@16604
			jQuery(".dvtCellLabel")
				.attr("class", "")
				.css("font-weight", "bold")
				.css("white-space", "nowrap");
			jQuery(".dvtCellInfo").attr("class", "");
			//crmv@16604e
		}
	});
}

function sttSetAll(boolset) {
	var table = document.getElementById("rule_table");
	var checks = table.getElementsByTagName("input");
	for (var i = 0; i < checks.length; i++) {
		if (checks[i].id.indexOf("st_ruleid_") > -1) {
			if (boolset) checks[i].checked = true;
			else checks[i].checked = false;
		}
	}
}

function sttUpdate() {
	var ruleid_sequence = "";
	var table = document.getElementById("rule_table");
	var checks = table.getElementsByTagName("input");
	for (var i = 0; i < checks.length; i++) {
		if (checks[i].id.indexOf("st_ruleid_") > -1) {
			if (checks[i].checked) ruleid_sequence += "&" + checks[i].id + "=1";
			else ruleid_sequence += "&" + checks[i].id + "=0";
		}
	}
	var role_check_obj = getObj("role_check");
	var module_name_obj = getObj("module_name");
	var status_field_obj = getObj("status_field");
	var source_module = module_name_obj.value;
	var source_roleid = role_check_obj.value;
	var status_field = status_field_obj.value;
	var status_field_value = getObj(status_field).value;

	jQuery("#status").show();

	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: "module=Transitions&action=TransitionsAjax&file=Update&ajax=true&" + ruleid_sequence + "&source_module=" + source_module + "&source_roleid=" + source_roleid + "&status_field=" + status_field + "&status_field_value=" + status_field_value,
		success: function(result) {
			jQuery("#status").hide();
			vtealert(alert_arr.LBL_TRANS_SETTINGS_SAVED); // crmv@191067
		}
	});
}

function sttCopy() {
	if (!confirm(alert_arr.ARE_YOU_SURE)) return;

	var role_check_obj = getObj("role_check");
	var module_name_obj = getObj("module_name");
	var src_role_check_obj = getObj("src_role_check");

	if (module_name_obj.value == "-1") {
		alert(alert_arr.LBL_STATUS_PLEASE_SELECT_A_MODULE);
		return;
	}

	if (role_check_obj.value == "-1") {
		alert(alert_arr.LBL_STATUS_PLEASE_SELECT_A_ROLE);
		return;
	}

	if (src_role_check_obj.value == "-1") {
		alert(alert_arr.LBL_STATUS_PLEASE_SELECT_A_ROLE);
		return;
	}

	var source_module = module_name_obj.value;
	var source_roleid = src_role_check_obj.value;
	var destination_roleid = role_check_obj.value;

	jQuery("#status").show();

	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: "module=Transitions&action=TransitionsAjax&file=Update&ajax=true&subaction=copy&source_module=" + source_module + "&source_roleid=" + source_roleid + "&destination_roleid=" + destination_roleid,
		success: function(result) {
			jQuery("#status").hide();
			getStTable(false);
		}
	});
}
/* crmv@191067 */
function deleteTransition(modulename, roleid, field, status, trtag)
{
	vteconfirm(alert_arr.ARE_YOU_SURE, function(yes) {
		if (yes)
		{
			jQuery.ajax({
				url: 'index.php',
				method: 'POST',
				data: "module=Transitions&action=TransitionsAjax&file=Delete&ajax=true&subaction=copy&modulename=" + modulename + "&roleid=" + roleid + "&field=" + field + "&status=" + status,
				success: function(result) {
					vtealert(alert_arr.LBL_TRANS_DELETED);
					jQuery('#' + trtag).hide();
				}
			});
		}
	});
}

function loadTransByData(modulename, roleid, field)
{
	jQuery('#moduleName').val(modulename).change();
	jQuery('#role_check').val(roleid).change();
	jQuery('#status_field').val(field).change();
};
/* crmv@191067e */