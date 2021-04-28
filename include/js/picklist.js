/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@114293 crmv@192033 */

/**
 * this function is used to get the picklist values using ajax
 * it does not accept any parameters but calculates the modulename and roleid from the document
 */
function changeModule(){
	
	var module = jQuery('#pickmodule').val();
	var role = jQuery('#pickid').val();
	
	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'action=PickListAjax&module=PickList&directmode=ajax&file=PickList&moduleName='+encodeURIComponent(module)+'&roleid='+role,
		success: function(result) {
			jQuery('#status').hide();
			jQuery("#picklist_datas").html(result);
		}
	});
	fnhide('actiondiv');
}

/**
 * this function is used to assign picklist values to role
 * @param string module - the module name
 * @param string fieldname - the name of the field
 * @param string fieldlabel - the label for the field
 */
function assignPicklistValues(module,fieldname,fieldlabel){
	var role = jQuery('#pickid').val();

	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'action=PickListAjax&module=PickList&file=AssignValues&moduleName='+encodeURIComponent(module)+'&fieldname='+encodeURIComponent(fieldname)+'&fieldlabel='+encodeURIComponent(fieldlabel)+'&roleid='+role,
		success: function(result) {
			jQuery('#status').hide();
			jQuery("#actiondiv").html(result).show();
			placeAtCenter(jQuery("#actiondiv").get(0));
		}
	});
}

/**
 * this function is used to select the value from select box of picklist to the edit box
 */
function selectForEdit(){
	var node = document.getElementById('edit_availPickList');
	if(node.selectedIndex >=0){
		var value = node.options[node.selectedIndex].text;
		jQuery('#replaceVal').val(value).focus();
	}
}

/**
 * this function checks if the edited value already exists; 
 * if not it pushes the edited value back to the picklist
 */
function pushEditedValue(e){
	var node = document.getElementById('edit_availPickList');
	if(typeof e.keyCode != 'undefined'){
		var keyCode = e.keyCode;
		//check if escape key is being pressed:: if yes then substitue the original value
		if(keyCode == 27){
			node.options[node.selectedIndex].text = node.options[node.selectedIndex].value;
			jQuery('#replaceVal').val(node.options[node.selectedIndex].value);
			return;
		}
	}
	
	var newVal = trim(document.getElementById('replaceVal').value);
	for(var i=0;i<node.length;i++){
		if(node[i].text.toLowerCase() == newVal.toLowerCase() && node.options[node.selectedIndex].text.toLowerCase() != newVal.toLowerCase()){
			alert(alert_arr.LBL_DUPLICATE_VALUE_EXISTS);
			return false;
		}
	}
	
	var nonEdit = document.getElementsByClassName('nonEditablePicklistValues');
	if(nonEdit){
		for(var i=0;i<nonEdit.length;i++){
			var val = trim(nonEdit[i].innerHTML);
			if(val.toLowerCase() == newVal.toLowerCase()){
				alert(alert_arr.LBL_DUPLICATE_VALUE_EXISTS);
				return false;
			}
		}
	}
	
	if(node.selectedIndex >=0){
		node.options[node.selectedIndex].text = newVal;
	}
}

/**
 * this function is used to show the delete div for a picklist
 */
function showDeleteDiv(){
	var module = jQuery('#pickmodule').val();
	
	var oModPick = document.getElementById('allpick');
	var fieldName=oModPick.options[oModPick.selectedIndex].value;
	var fieldLabel=oModPick.options[oModPick.selectedIndex].text;
	
	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'action=PickListAjax&module=PickList&mode=delete&file=ShowActionDivs&moduleName='+encodeURIComponent(module)+'&fieldname='+encodeURIComponent(fieldName)+'&fieldlabel='+encodeURIComponent(fieldLabel),
		success: function(result) {
			jQuery('#status').hide();
			jQuery("#actiondiv").html(result).show();
			placeAtCenter(jQuery("#actiondiv").get(0));
		}
	});
}

/**
 * this function is used to show the add div for a picklist
 */
function showAddDiv(){
	var module = jQuery('#pickmodule').val();
	
	var oModPick = document.getElementById('allpick');
	var fieldName=oModPick.options[oModPick.selectedIndex].value;
	var fieldLabel=oModPick.options[oModPick.selectedIndex].text;
	
	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'action=PickListAjax&module=PickList&mode=add&file=ShowActionDivs&moduleName='+encodeURIComponent(module)+'&fieldname='+encodeURIComponent(fieldName)+'&fieldlabel='+encodeURIComponent(fieldLabel),
		success: function(result) {
			jQuery('#status').hide();
			jQuery("#actiondiv").html(result).show();
			placeAtCenter(jQuery("#actiondiv").get(0));
		}	
	});
}

/**
 * this function is used to show the edit div for a picklist
 */
function showEditDiv(){
	var module = jQuery('#pickmodule').val();
	
	var oModPick = document.getElementById('allpick');
	var fieldName=oModPick.options[oModPick.selectedIndex].value;
	var fieldLabel=oModPick.options[oModPick.selectedIndex].text;
	
	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'action=PickListAjax&module=PickList&mode=edit&file=ShowActionDivs&moduleName='+encodeURIComponent(module)+'&fieldname='+encodeURIComponent(fieldName)+'&fieldlabel='+encodeURIComponent(fieldLabel),
		success: function(result) {
			jQuery('#status').hide();
			jQuery("#actiondiv").html(result).show();
			placeAtCenter(jQuery("#actiondiv").get(0));
		}
	});
}

/**
 * this function validates the add action
 * @param string fieldname - the name of the picklist field
 * @param string module - the name of the module
 */
function validateAdd(fieldname, module){
	var pickArr=new Array();
	var pick_options = document.getElementsByClassName('picklist_existing_options');
	for(var i=0;i<pick_options.length;i++){
		pickArr[i]=pick_options[i].innerHTML;
	}

	var new_vals = new Array();
	new_vals=document.getElementById("add_picklist_values").value.split('\n');		
	if(new_vals == '' || new_vals.length == 0) {
		alert(alert_arr.LBL_ADD_PICKLIST_VALUE);
		return false;
	}
	// clean duplicates of empty values
	if (new_vals.indexOf('') > -1) {
		var tmp_new_vals = [];
		var empty = false;
		jQuery.each(new_vals, function(k,v){
			if (v != '' || !empty) {
				tmp_new_vals.push(v);
				if (v == '') empty = true;
			}
		});
		new_vals = tmp_new_vals; 
	}
	
	// crmv@198652
	var bad_characters = [',','&','|','#'];
	var err = false;
	jQuery.each(new_vals, function(k,v){
		jQuery.each(bad_characters, function(b,c){
			if (v.indexOf(c) > -1) {
				err = true;
			}
		});
	});
	if (err) {
		alert(alert_arr.LBL_BAD_CHARACTER_PICKLIST_VALUE+' '+bad_characters.join(' '));
		return false;
	}
	// crmv@198652e

	var node = document.getElementsByClassName('picklist_noneditable_options');
	var nonEdit = new Array();
	for(var i=0;i<node.length;i++){
		nonEdit[i] = trim(node[i].innerHTML);
	}
	
	pickArr = pickArr.concat(new_vals);
	pickArr = pickArr.concat(nonEdit);
	if(checkDuplicatePicklistValues(pickArr) == true){
		pickAdd(module,fieldname);
	}
}

/**
 * this function is used to check duplicate values in a given picklist values arrays
 * @param array arr - the picklist values array
 * @return boolean - true if no duplicates :: false otherwise
 */
function checkDuplicatePicklistValues(arr){
	var len=arr.length;
	for(i=0;i<len;i++){
		for(j=i+1;j<len;j++){
			if(trim(arr[i]).toLowerCase() == trim(arr[j]).toLowerCase()){
				alert(alert_arr.LBL_DUPLICATE_FOUND+"'"+trim(arr[i])+"'");
				return false;
			}
		}
	}
	return true;
}

/**
 * this function adds a new value to the given picklist
 * @param string module - the module name
 * @param string fieldname - the picklist field name
 */
function pickAdd(module, fieldname){
	var arr = new Array();
	arr = document.getElementById("add_picklist_values").value.split('\n');
	// clean duplicates of empty values
	if (arr.indexOf('') > -1) {
		var tmp_arr = [];
		var empty = false;
		jQuery.each(arr, function(k,v){
			if (v != '' || !empty) {
				tmp_arr.push(v);
				if (v == '') empty = true;
			}
		});
		arr = tmp_arr; 
	}
	var trimmedArr = new Array();
	for(var i=0,j=0;i<arr.length;i++){
		trimmedArr[j++] = trim(arr[i]);
	}
	var newValues = JSON.stringify(trimmedArr);
	arr = new Array();
	
	var roles = document.getElementById("add_availRoles").options;
	var roleValues = '';
	if(roles.selectedIndex > -1){
		for (var i=0,j=0;i<roles.length;i++){
			if(roles[i].selected == true){
				arr[j++] = roles[i].value;
			}
		}
		roleValues = JSON.stringify(arr);
	}
	
	var addPicklist = function() {
		var node = document.getElementById('saveAddButton');
		node.disabled = true;
		jQuery('#status').show();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'action=PickListAjax&module=PickList&mode=add&file=PickListAction&fld_module='+encodeURIComponent(module)+'&fieldname='+encodeURIComponent(fieldname)+'&newValues='+encodeURIComponent(newValues)+'&selectedRoles='+encodeURIComponent(roleValues),
			success: function(result) {
				if(result=="SUCCESS"){
					changeModule();
					fnhide('actiondiv');
				}else{
					alert(result);
				}						
				jQuery('#status').hide();
			}
		});
	}
	
	if(trim(roleValues) == '') {
		vteconfirm(alert_arr.LBL_NO_ROLES_SELECTED, function(yes) {
			if (yes) {
				addPicklist();
			}
		});
	} else {
		addPicklist();
	}
}

/**
 * this function validates the edit action for a picklist
 * @param string fieldname - the fieldname of the picklist
 * @param string module - the module name
 */
function validateEdit(fieldname, module){
	var newVal = Array();
	var oldVal = Array();
	
	var node = document.getElementById('edit_availPickList');
	for(var i=0;i<node.length;i++){
		newVal[i] = node[i].text;
		if(trim(newVal[i]) == ''){
			alert(alert_arr.LBL_CANNOT_HAVE_EMPTY_VALUE);
			return false;
		}
		oldVal[i] = node[i].value;
	}
	pickReplace(module, fieldname, JSON.stringify(newVal), JSON.stringify(oldVal));
}

/**
 * this function is used to modify the picklist values
 * @param string module - the module name
 * @param string fieldname - the field name
 * @param array newVal - the new values for the picklist in json encoded string format
 * @param array oldVal - the old values for the picklist in json encoded string format
 */
function pickReplace(module, fieldname, newVal, oldVal){
	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'action=PickListAjax&module=PickList&mode=edit&file=PickListAction&fld_module='+encodeURIComponent(module)+'&fieldname='+encodeURIComponent(fieldname)+'&newValues='+encodeURIComponent(newVal)+'&oldValues='+encodeURIComponent(oldVal),
		success: function(result) {
			if(result == "SUCCESS"){
				changeModule();
				fnhide('actiondiv');
			}else{
				alert(result);
			}						
			jQuery('#status').hide();
		}
	});
}

/**
 * this function validates the delete action
 * @param string fieldname - the name of the picklist field
 * @param string module - the name of the module
 */
function validateDelete(fieldname, module){
	vteconfirm(alert_arr.LBL_WANT_TO_DELETE, function(yes) {
		if (yes) {
			var node = document.getElementById('replace_picklistval');
			var replaceVal = node.options[node.selectedIndex].value;
			node = document.getElementById('delete_availPickList');
			var arr = new Array();
			for(var i=0;i<node.length;i++){
				if(node.selectedIndex == -1){
					alert(alert_arr.LBL_NO_VALUES_TO_DELETE);
					return false;
				}else{
					for(var j=0, k=0; j<node.length; j++){
						if(node.options[j].selected == true){
							arr[k++] = encodeURIComponent(node.options[j].value.replace(/\\/g,'\\\\')); //crmv@76943 crmv@140302
						}
					}
				}
			}
		
			//check if replacement value is not equal to any deleted value
			for(var i=0; i<arr.length; i++){
				if(replaceVal == arr[i]){
					alert(alert_arr.LBL_PLEASE_CHANGE_REPLACEMENT);
					return false;
				}
			}
			
			// crmv@98693
			var nonEditableLength = 0;
			var nonEditable = jQuery('#nonEditablePicklistVal');
			if(nonEditable.length > 0){
				nonEditableLength = nonEditable.get(0).options.length;
			}
			// crmv@98693e
			
			if(arr.length == (node.length+nonEditableLength)){
				alert(alert_arr.LBL_DELETE_ALL_WARNING);
				return false;
			}
			pickDelete(module,fieldname, arr, replaceVal);
		}
	});
}

/**
 * this function deletes the given picklist values
 * @param string module - the module name
 * @param string fieldname - the field name of the picklist
 * @param array arr - the picklist values to delete
 * @param array replaceVal - the replacement value for the deleted value(s)
 */
function pickDelete(module, fieldname, arr, replaceVal){
	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'action=PickListAjax&module=PickList&mode=delete&file=PickListAction&fld_module='+encodeURIComponent(module)+'&fieldname='+encodeURIComponent(fieldname)+'&values='+JSON.stringify(arr)+'&replaceVal='+encodeURIComponent(replaceVal),
		success: function(result) {
			if(result == "SUCCESS"){
				changeModule();
				fnhide('actiondiv');
			}else{
				alert(result);
			}						
			jQuery('#status').hide();
		}
	});
}

/**
 * this function is used to assign the available picklist values to the assigend picklist values section
 */
function moveRight(){
	var rightElem = document.getElementById('selectedColumns');
	for (var i=0;i<rightElem.length;i++){
		rightElem.options[i].selected=false;
	}
	
	var leftElem = document.getElementById('availList');
	
	for (var i=0;i<leftElem.length;i++){
		if(leftElem.options[i].selected==true){            	
			var rowFound=false;
			//check if the value already exists
			for(var j=0;j<rightElem.length;j++){
				if(rightElem.options[j].value==leftElem.options[i].value){
					rowFound=true;
					rightElem.options[j].selected=true;
					break;
				}
			}
			
			//if the value does not exist then create it and set it as selected
			if(rowFound!=true){
				var newColObj=document.createElement("OPTION");
				newColObj.value=leftElem.options[i].value;
				newColObj.innerHTML=leftElem.options[i].innerHTML;
				
				rightElem.appendChild(newColObj);
				leftElem.options[i].selected=false;
				newColObj.selected=true;
			}
		}
	}
}

/**
 * this function is used to remove values from the assigned picklist values section
 */
function removeValue(){
	var elem = document.getElementById('selectedColumns');
	if(elem.options.selectedIndex>=0){
		for (var i=0;i<elem.options.length;i++){ 
			if(elem.options[i].selected == true){
				elem.removeChild(elem.options[i--]);
			}
		}
	}
}

/**
 * this function is used to move the selected option up in the assigned picklist
 */
function moveUp(){
	var elem = document.getElementById('selectedColumns');
	if(elem.options.selectedIndex>=0){
		for (var i=1;i<elem.options.length;i++){
			if(elem.options[i].selected == true){
				//swap with one up
				var first = elem.options[i-1];
				var second = elem.options[i];
				var temp = new Array();
				
				temp.value = first.value;
				temp.innerHTML = first.innerHTML;
				
				first.value = second.value;
				first.innerHTML = second.innerHTML;
				
				second.value = temp.value;
				second.innerHTML = temp.innerHTML;
				
				first.selected = true;
				second.selected = false;
			}
		}
	}
}

/**
 * this function is used to move the selected option down in the assigned picklist
 */
function moveDown(){
	var elem = document.getElementById('selectedColumns');
	if(elem.options.selectedIndex>=0){
		for (var i=elem.options.length-2;i>=0;i--){
			if(elem.options[i].selected == true){
				//swap with one down
				var first = elem.options[i+1];
				var second = elem.options[i];
				var temp = new Array();
				
				temp.value = first.value;
				temp.innerHTML = first.innerHTML;
				
				first.value = second.value;
				first.innerHTML = second.innerHTML;
				
				second.value = temp.value;
				second.innerHTML = temp.innerHTML;
				
				first.selected = true;
				second.selected = false;
			}
		}
	}
}

/**
 * this function is used to save the assigned picklist values for a given role
 * @param string moduleName - the name of the module
 * @param string fieldName - the name of the field
 * @param string roleid - the id of the given role
 */
function saveAssignedValues(moduleName, fieldName, roleid){
	var node = document.getElementById('selectedColumns');
	if(node.length == 0){
		alert(alert_arr.LBL_DELETE_ALL_WARNING);
		return false;
	}
	var arr = new Array();
	for(var i=0;i<node.length;i++){
		arr[i] = node[i].value;
	}
	
	node = document.getElementById('roleselect');
	var otherRoles = new Array();
	if(node != null){
		if(node.selectedIndex > -1){
			for(var i=0,j=0; i<node.options.length; i++){
				if(node.options[i].selected == true){
					otherRoles[j++] = node.options[i].value;
				}
			}
		}
	}
	otherRoles = JSON.stringify(otherRoles);
	
	var values = JSON.stringify(arr);
	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'action=PickListAjax&module=PickList&file=SaveAssignedValues&moduleName='+encodeURIComponent(moduleName)+'&fieldname='+encodeURIComponent(fieldName)+'&roleid='+roleid+'&values='+encodeURIComponent(values)+'&otherRoles='+encodeURIComponent(otherRoles),
		success: function(result) {
			if(result == "SUCCESS"){
				jQuery('#status').hide();
				jQuery("#actiondiv").hide();
				showPicklistEntries();
			}else{
				alert(result);
			}
		}
	});
}

/**
 * this function is used to display the picklist entries for a given module for a given field for a given roleid
 * it accepts the module name as parameter while retrieves other values from DOM
 * @param string module - the module name 
 */
function showPicklistEntries(){
	var moduleName = jQuery('#pickmodule').val();
	
	var roleid = jQuery('#pickid').val();
	
	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'action=PickListAjax&module=PickList&file=PickList&moduleName='+encodeURIComponent(moduleName)+'&roleid='+roleid+'&directmode=ajax',
		success: function(result) {
			if(result){
				jQuery('#status').hide();
				jQuery('#pickListContents').html(result);
			}
		}
	});
}

/**
 * this function is used to display the select role div
 * @param string roleid - the roleid of the current role
 */
function showRoleSelectDiv(roleid){
	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'action=PickListAjax&module=PickList&file=ShowRoleSelect&roleid='+roleid,
		success: function(result) {
			if(result){
				jQuery('#status').hide();
				var node = document.getElementById('assignPicklistTable');
				var tr = document.createElement('tr');
				var td = document.createElement('td');
				td.innerHTML = result;
				tr.appendChild(td);
				jQuery('#addRolesLink').hide();
				
				var tbody = getChildByTagName(node,'tbody');
				var sibling = getChildByTagName(tbody, "tr");
				sibling = getSiblingByTagName(sibling, "tr");
				tbody.insertBefore(tr,sibling);
				placeAtCenter(jQuery("#actiondiv").get(0));
			}
		}
	});
}

/**
 *
 */
function getSiblingByTagName(elem,tagName){
	var sibling = elem.nextSibling;
	while(sibling.nodeName.toLowerCase()!=tagName.toLowerCase()){
		sibling = sibling.nextSibling;
	}
	return sibling;
}

/**
 *
 */
function getChildByTagName(elem,tagName){
	for(var i=0;elem.childNodes.length;++i){
		if(elem.childNodes[i].nodeName.toLowerCase()==tagName.toLowerCase()){
			break;
		}
	}
	if(i >= elem.childNodes.length){
		return null;
	}
	return elem.childNodes[i];
}