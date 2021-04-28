/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/**
 * this function is used to update the page with the new module selected
 */
 
 /* crmv@192033 */
 
function changeModule() {
	
	jQuery("#status").show();
	
	var module= getObj('pickmodule').value;
	var result = getFile("index.php?module=Picklistmulti&action=PicklistmultiAjax&file=LoadField&module_name="+encodeURIComponent(module))
	result = eval('(' + result + ')');
	if (result == null){
		 rm_all_opt('picklist_field');
		 add_opt('picklist_field',"","{$APP.LBL_NONE}");
	}
	else {
		var field_obj = getObj('picklist_field');
		 resetpicklist('picklist_field');
		 for (var key in result){
	    	add_opt('picklist_field',values[key],key);
		 }
		fieldname = field_obj.value;
		fieldmodule = module_obj.value;
		fieldlabel = field_obj.options[field_obj.selectedIndex].text;	
		jQuery("#table_picklist").jqGrid('setGridParam',{editurl:"index.php?module=Picklistmulti&action=PicklistmultiAjax&file=edit&field="+fieldname+"&field_module="+fieldmodule,url:'index.php?module=Picklistmulti&action=PicklistmultiAjax&file=load&field='+fieldname+'&field_module='+fieldmodule}).jqGrid('setCaption',fieldlabel).trigger('reloadGrid');
	}
	jQuery("#status").hide;
}
/**
 * this function is used to update the page with the new field selected
 */
function changeField(){
	var module_obj=getObj('pickmodule');
	var field_obj = getObj('picklist_field');
	fieldname = field_obj.value;
	fieldmodule = module_obj.value;
	fieldlabel = field_obj.options[field_obj.selectedIndex].text;
	jQuery("#table_picklist").jqGrid('setGridParam',{editurl:"index.php?module=Picklistmulti&action=PicklistmultiAjax&file=edit&field="+fieldname+"&field_module="+fieldmodule,url:'index.php?module=Picklistmulti&action=PicklistmultiAjax&file=load&field='+fieldname+'&field_module='+fieldmodule}).jqGrid('setCaption',fieldlabel).trigger('reloadGrid');
}