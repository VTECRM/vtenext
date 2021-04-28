<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@2539m
function getTotalDiscountPrice($record) {
	global $adb, $table_prefix, $PDFMaker_template_id, $current_user;
	$module = getSalesEntityType($record);
	$focus = CRMEntity::getInstance($module);
	$focus->id = $record;
	$focus->retrieve_entity_info($record,$module);
	$pdf = PDFContent::getInstance($PDFMaker_template_id, $module, $focus, $current_user->default_language); //crmv@34738
	$pdf->decimals = 2;
	$pdf->decimal_point = '.';
	$pdf->thousands_separator = '';
	$result = $pdf->getInventoryProducts();
	$total = 0;
	if (!empty($result['P'])) {
		foreach($result['P'] as $prod) {
			if ($prod['PRODUCTDISCOUNT'] > 0) {
				$total += $prod['PRODUCTTOTALSUM'];
			}
		}
	}
	return $total;
}
function getTotalNetPrice($record) {
	global $adb, $table_prefix, $PDFMaker_template_id, $current_user;
	$module = getSalesEntityType($record);
	$focus = CRMEntity::getInstance($module);
	$focus->id = $record;
	$focus->retrieve_entity_info($record,$module);
	$pdf = PDFContent::getInstance($PDFMaker_template_id, $module, $focus, $current_user->default_language); //crmv@34738
	$pdf->decimals = 2;
	$pdf->decimal_point = '.';
	$pdf->thousands_separator = '';
	$result = $pdf->getInventoryProducts();
	$total = 0;
	if (!empty($result['P'])) {
		foreach($result['P'] as $prod) {
			if ($prod['PRODUCTDISCOUNT'] == 0) {
				$total += $prod['PRODUCTTOTALSUM'];
			}
		}
	}
	return $total;
}
//crmv@2539me
?>