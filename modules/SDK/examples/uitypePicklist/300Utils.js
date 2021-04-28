/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/*
* Funzioni per la gestione delle picklist collegate
*/
/* crmv@27229 crmv@82831 crmv@131239 */

/*
* Aggiorna le varie picklist
*/
function linkedListUpdateLists(res) {
	if (!res) return;

	for (i=0; i<res.length; ++i) {
		name = res[i][0];
		list = res[i][1];
		list_trans = res[i][2];
		otherpl = document.getElementsByName(name);
		
		// take the first matching element
		if (otherpl.length > 0) {
			otherpl = otherpl[0];
		} else {
		// try a multiselect picklist
			otherpl = document.getElementsByName(name+"[]");
			if (otherpl.length > 0) otherpl = otherpl[0]; else continue;
		}

		// get original sel value
		if (typeof(otherpl.options) == 'undefined') continue;	//crmv@153321 if the field is hidden continue
		var opt = otherpl.options[otherpl.selectedIndex];
		var oldval = (opt ? opt.value : null);
		// delete inside (this cycle is much faster than using innerhtml)
		while (otherpl.firstChild) {
			otherpl.removeChild(otherpl.firstChild);
		}
		// re-populate
		for (j=0; j<list.length; ++j) {
			var option = document.createElement("option");
			option.text = list_trans[j];
			option.value = list[j];
			otherpl.add(option);
			if (option.value == oldval) option.selected = true;
		}
		
		// crmv@97692
		// if empty, remove all
		if (list.length == 0) {
			otherpl.innerHTML = '';
			otherpl.selectedIndex = null;
		}
		// crmv@97692e

		// change other lists
		if (otherpl.onchange) otherpl.onchange(otherpl);
	}
}

/*
* funzione da chiamare quando la picklist obj cambia
*/
function linkedListChainChange(obj, module) { // crmv@30528
	if (!obj) return;
	
	setTimeout(function(){	// TODO per farlo partire dopo i conditionals
		var pickname = obj.name;
	
		var opt = obj.options.item(obj.selectedIndex);
		var pickselection = (opt ? opt.value : null);
		var params = {'function':'linkedListGetChanges','modname':module,'name':pickname,'sel':pickselection};
		if (typeof(fieldname) != 'undefined') params['fieldname'] = JSON.stringify(fieldname);
		if (typeof(fielddatatype) != 'undefined') params['fielddatatype'] = JSON.stringify(fielddatatype);
	
		jQuery.ajax({
			url:"index.php?module=SDK&action=SDKAjax&file=examples/uitypePicklist/300Ajax",
			dataType:"json",
			type: "post",
			data: jQuery.param(params),
			async: true,
			cache: false,
			success: function(res) {
				linkedListUpdateLists(res);
			}
		});
	}, 100);
}

// crmv@143365
function linkedListChek(self) {
	if (jQuery('#enable_conditionals').val() == '1') {
		//check if it's one of the condition fields
		var fieldname = self.name;
		if (fieldname && window.ProcessScript && ProcessScript.condition_fields && ProcessScript.condition_fields.length > 0) {
			if (ProcessScript.condition_fields.indexOf(fieldname) >= 0) return false;
		}
	}
	return true;
}
// crmv@143365e