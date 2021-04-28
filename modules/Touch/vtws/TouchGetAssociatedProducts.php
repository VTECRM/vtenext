<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 */

global $login, $userId, $currentModule;

$module = $_REQUEST['module'];
$recordid = intval($_REQUEST['record']);

if(!$login || empty($userId)) {
	echo 'Login Failed';
} else {

	if ($recordid > 0) {

		$currentModule = $module;

		$focus = CRMEntity::getInstance($currentModule);
		$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

		$focus->id = $recordid;
		$focus->mode = 'edit';
		$focus->retrieve_entity_info($recordid,$currentModule);
		//$focus->name = $focus->column_fields['subject'];

		$associated_prod = $InventoryUtils->getAssociatedProducts($currentModule,$focus);
		$finalinfo = $InventoryUtils->getFinalDetails($currentModule, $focus, $recordid);
		$prodn = count($associated_prod);


		// campi per i totali
		/*$totalFields = array('hdnSubTotal', 'hdnGrandTotal', 'hdnS_H_Amount', 'hdnDiscountPercent', 'hdnDiscountAmount', 'txtAdjustment');
		foreach ($totalFields as $fldname) {
			if (array_key_exists($fldname, $focus->column_fields)) {
				$totals[$fldname] = $focus->column_fields[$fldname];
			}
		}*/
		if (is_array($finalinfo[1]['final_details'])) {
			foreach ($finalinfo[1]['final_details'] as $finalfield=>$finalvalue) {
				$totals[$finalfield] = $finalvalue;
			}
			if (!isset($totals['hdnGrandTotal'])) $totals['hdnGrandTotal'] = $totals['grandTotal'];
			if ($totals['discount_type_final'] == 'amount') {
				$totals['discount_value'] = $totals['discount_amount_final'];
			} else {
				$totals['discount_value'] = $totals['discount_percentage_final'];
				// crmv@48677
				if (!empty($totals['discount_value'])) {
					$fieldval = $InventoryUtils->parseMultiDiscount($totals['discount_value'], 1, 0);
					$fieldval = $InventoryUtils->joinMultiDiscount($fieldval, 0, 0);
					if (is_numeric($fieldval)) $fieldval = floatval($fieldval);
					$totals['discount_value'] = $totals['discount_percentage_final'] = $fieldval;
				}
				// crmv@48677e
			}
			// tasse
			if (is_array($totals['sh_taxes'])) {
				foreach ($totals['sh_taxes'] as $taxinfo) {
					$totals[$taxinfo['taxname']] = $taxinfo['percentage'];
				}
			}
		}

		// prodotti
		foreach ($associated_prod as $prodnum=>$prodinfo) {
			$prod = array();
			foreach ($prodinfo as $prodfield => $fieldval) {
				// tolgo il numero finale dal nome del campo
				if (preg_match('/(.+?)'.$prodnum.'/', $prodfield, $matches) > 0) {
					$prodfield = $matches[1];
				}
				// crmv@48677
				if ($prodfield == 'discount_percent' && !empty($fieldval)) {
					$fieldval = $InventoryUtils->parseMultiDiscount($fieldval, 1, 0);
					$fieldval = $InventoryUtils->joinMultiDiscount($fieldval, 0, 0);
				}
				// crmv@48677e
				if (is_numeric($fieldval)) $fieldval = floatval($fieldval);
				$prod[$prodfield] = $fieldval;
			}
			// fix per alcuni campi
			$prod['entityname'] = $prod['productName'];
			$prod['crmid'] = $prod['hdnProductId'];
			// sistemo il campo productid
			$prod['hdnProductId'] = array('crmid'=>$prod['crmid'], 'display'=>$prod['productName']);
			$prodlist[] = $prod;
		}

		$output = array(
			'total' => count($associated_prod),
			'entries' => $prodlist,
			'TOTALS' => $totals,
		);
	}
	echo Zend_Json::encode($output);
}
?>