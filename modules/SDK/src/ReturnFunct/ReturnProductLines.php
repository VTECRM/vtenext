<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@42247 crmv@107331 */

global $default_charset,$adb,$table_prefix,$autocomplete_return_function;

$forfield = $_REQUEST['forfield'];
$list_result_count = $i-1;

$value = $this->getValue($ui_col_array,$list_result,$fieldname,$focus,$module,$entity_id,$list_result_count,"search",$focus->popup_type);

if(isset($forfield) && $forfield != '' && $focus->popup_type != 'detailview') {
	// value as it is in the DB
	$rawValue = ListViewUtils::decodeFromDb($value, true);
	
	// value with HTML enitities to be used inside HTML attributes
	$valueHtml = ListViewUtils::encodeForHtml($rawValue);

	$value = "";
	$popupValues = array($rawValue);
	
	if ($module == 'SalesOrder') {
		$popupValues[] = 'EditView';
		$fnName = 'set_return_specific';
		$value = '<a href="index.php?module='.$module.'&action=DetailView&record='.$entity_id.'&parenttab=Sales" />';
	} elseif ($module == 'Quotes') {
		$fnName = 'set_return_specific';
		$value = '<a href="index.php?module='.$module.'&action=DetailView&record='.$entity_id.'&parenttab=Sales" />';
	} else {
	//crmv@100903
		$popupValues[] = $forfield;
		$fnName = 'vtlib_setvalue_from_popup';
	}
	//crmv@100903e
	
	// prepare the list of arguments
	foreach ($popupValues as &$val) {
		$val = ListViewUtils::encodeForJs($val, '"');
	}
	$args = '"'.implode('", "', $popupValues).'"';
	
	// prepare the autocomplete function
	$autocomplete_return_function[$entity_id] = "$fnName(\"$entity_id\", {$args});";
	$autocompleteHtml = ListViewUtils::encodeForHtmlAttr($autocomplete_return_function[$entity_id], "'");
	
	// html value
	$value .= "<a href='javascript:void(0);' onclick='{$autocompleteHtml}closePopup();'>$valueHtml</a>";
}