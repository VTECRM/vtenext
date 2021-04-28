<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TouchGetAssociatedProducts extends TouchWSClass {

	public function process(&$request) {
		global $currentModule, $touchUtils;
	
		$module = $request['module'];
		$recordid = intval($request['record']);
		
		if (!isInventoryModule($module)) return $this->error('The requested module is not an inventory module');
		
		if (empty($recordid)) return $this->error('Invalid record requested');

		$currentModule = $module;

		$focus = $touchUtils->getModuleInstance($currentModule);
		$this->InventoryUtils = $InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

		$focus->id = $recordid;
		$focus->mode = 'edit';
		//$focus->retrieve_entity_info($recordid,$currentModule);	// this is only useful for totals, removed
		//$focus->name = $focus->column_fields['subject'];

		$associated_prod = $InventoryUtils->getAssociatedProducts($currentModule,$focus);
		//$finalinfo = $InventoryUtils->getFinalDetails($currentModule, $focus, $recordid);
		$prodn = count($associated_prod);

		// campi per i totali
		/*$totalFields = array('hdnSubTotal', 'hdnGrandTotal', 'hdnS_H_Amount', 'hdnDiscountPercent', 'hdnDiscountAmount', 'txtAdjustment');
		foreach ($totalFields as $fldname) {
			if (array_key_exists($fldname, $focus->column_fields)) {
				$totals[$fldname] = $focus->column_fields[$fldname];
			}
		}*/
		/*if (is_array($finalinfo[1]['final_details'])) {
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
		}*/

		// prodotti
		foreach ($associated_prod as $prodnum=>$prodinfo) {
			$prodlist[] = $this->processRow($prodnum, $prodinfo);
		}

		$output = array(
			'total' => count($associated_prod),
			'entries' => $prodlist,
			//'TOTALS' => $totals,
		);

		return $this->success($output);		
	}
	
	/**
	 * Adds some fields and clean up some others
	 */
	public function processRow($prodnum, $prodinfo) {
		
		if (empty($this->InventoryUtils)) {
			$this->InventoryUtils = InventoryUtils::getInstance();
		}
		
		$prod = array();
		foreach ($prodinfo as $prodfield => $fieldval) {
			// tolgo il numero finale dal nome del campo
			if (preg_match('/(.+?)'.$prodnum.'/', $prodfield, $matches) > 0) {
				$prodfield = $matches[1];
			}
			// crmv@48677
			if ($prodfield == 'discount_percent' && !empty($fieldval)) {
				$fieldval = $this->InventoryUtils->parseMultiDiscount($fieldval, 1, 0);
				$fieldval = $this->InventoryUtils->joinMultiDiscount($fieldval, 0, 0);
			}
			// crmv@48677e
			if (is_numeric($fieldval)) $fieldval = floatval($fieldval);
			$prod[$prodfield] = $fieldval;
		}
		// fix per alcuni campi
		$prod['entityname'] = $prod['productName'];
		$prod['lineid'] = $prod['lineItemId'];
		$prod['crmid'] = $prod['hdnProductId'];
		$prod['productDescription'] = html_entity_decode($prod['productDescription']); // crmv@130853
		// sistemo il campo productid
		$prod['hdnProductId'] = array('crmid'=>$prod['crmid'], 'display'=>$prod['productName']);
		unset($prod['style_discount_percent']);
		unset($prod['style_discount_amount']);
		unset($prod['checked_discount_zero']);
		
		return $prod;
	}
	
}
