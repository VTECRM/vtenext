<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/


global $default_charset,$adb,$table_prefix,$autocomplete_return_function;
$forfield = htmlspecialchars($_REQUEST['forfield'], ENT_QUOTES, $default_charset);
$list_result_count = $i-1;
$value = $this->getValue($ui_col_array,$list_result,$fieldname,$focus,$module,$entity_id,$list_result_count,"search",$focus->popup_type);
if(isset($forfield) && $forfield != '' && $focus->popup_type != 'detailview') {
	$value1 = strip_tags($value);
	$value = htmlspecialchars(addslashes(html_entity_decode(strip_tags($value), ENT_QUOTES,$default_charset)), ENT_QUOTES,$default_charset); // Remove any previous html conversion
	$course_address = '';
	$result = $adb->pquery('SELECT * FROM '.$table_prefix.'_accountbillads WHERE accountaddressid = ?', array($entity_id));// crmv@208173
    if ($result && $adb->num_rows($result)>0) {
		$course_address .= $adb->query_result($result,0,'bill_street');
		$course_address .= ' '.$adb->query_result($result,0,'bill_pobox');
		$course_address .= ' '.$adb->query_result($result,0,'bill_city');
		$course_address .= ' '.$adb->query_result($result,0,'bill_code');
		$course_address .= ' '.$adb->query_result($result,0,'bill_country');
		$course_address .= ' '.$adb->query_result($result,0,'bill_state');
	}
	$autocomplete_return_function[$entity_id] = "return_seat_to_campaign($entity_id, \"$value\", \"$forfield\", \"$course_address\");";
	$value = "<a href='javascript:void(0);' onclick='{$autocomplete_return_function[$entity_id]}closePopup();'>$value1</a>"; //crmv@21048m
}
?>