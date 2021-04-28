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

// crmv@166552 - removed code

if(!empty($forfield) && $focus->popup_type != 'detailview') {
	// value as it is in the DB
	$rawValue = ListViewUtils::decodeFromDb($value, true);
	
	// value ready for js inclusion
	$valueJs = ListViewUtils::encodeForJs($rawValue);
	
	// value with HTML enitities to be used inside HTML attributes
	$valueHtml = ListViewUtils::encodeForHtml($rawValue);
	
	$popupValues = array();
	
	$result = $adb->pquery('SELECT related_to FROM '.$table_prefix.'_potential WHERE potentialid = ?', array($entity_id));
	if ($result && $adb->num_rows($result)>0) {
		$related_to = (int)$adb->query_result_no_html($result,0,'related_to');
		if ($related_to > 0) {
			$related_mod = getSalesEntityType($related_to);
			if ($related_mod == 'Accounts') {
				$argsKeys = array(
					'accountname',
					'bill_street', 'ship_street', 'bill_city', 'ship_city', 'bill_state', 'ship_state', 
					'bill_code', 'ship_code', 'bill_country', 'ship_country', 'bill_pobox', 'ship_pobox',
				);
				$result = $adb->pquery(
					"SELECT ".implode(', ', $argsKeys)."
					FROM {$table_prefix}_account 
					INNER JOIN {$table_prefix}_accountbillads ON {$table_prefix}_account.accountid = {$table_prefix}_accountbillads.accountaddressid 
					INNER JOIN {$table_prefix}_accountshipads ON {$table_prefix}_account.accountid = {$table_prefix}_accountshipads.accountaddressid 
					WHERE {$table_prefix}_account.accountid = ?", 
					array($related_to)
				); 
				if ($result && $adb->num_rows($result)>0) {
					// get the row exactly as it is in the DB
					$popupValues = $adb->fetchByAssoc($result, -1, false);
				}
			
			} else {
				$argsKeys = array('contactname');
				$resc0 = $adb->pquery('SELECT lastname,firstname FROM '.$table_prefix.'_contactdetails WHERE contactid = ?', array($related_to));
				if ($resc0 && $adb->num_rows($resc0)>0) {
					$contact_lname = $adb->query_result_no_html($resc0,0,'lastname');
					$contact_fname = $adb->query_result_no_html($resc0,0,'firstname');
					$popupValues['contactname'] = trim($contact_lname.' '.$contact_fname);
				}
			}
		}
	}
	
	$value = "";
	$argList = array();
	
	if ($related_mod == 'Accounts') {
		$argList[] = $related_to;
		$fnName = 'set_return_address';
		$value = '<a href="index.php?module=Potentials&action=DetailView&record='.$entity_id.'&parenttab=Marketing" />';
	} elseif ($related_mod == 'Contacts') {
		$argList[] = $related_to;
		$fnName = 'set_return_contact';
		$value = '<a href="index.php?module=Potentials&action=DetailView&record='.$entity_id.'&parenttab=Marketing" />';
	} else {
	//crmv@100903
		$argList[] = ListViewUtils::encodeForJs($forfield, '"');
		$argsKeys = array();
		$fnName = 'vtlib_setvalue_from_popup';
	}
	//crmv@100903e
	
	// prepare the list of arguments
	foreach ($argsKeys as $key) {
		$argList[] = ListViewUtils::encodeForJs($popupValues[$key], '"');
	}
	$args = '"'.implode('", "', $argList).'"';

	// prepare the autocomplete function
	$autocomplete_return_function[$entity_id] = "$fnName($entity_id, \"$valueJs\", {$args});";
	$autocompleteHtml = ListViewUtils::encodeForHtmlAttr($autocomplete_return_function[$entity_id], "'");
	
	// html value
	$value .= "<a href='javascript:void(0);' onclick='{$autocompleteHtml}closePopup();'>$valueHtml</a>"; //crmv@21048m

}