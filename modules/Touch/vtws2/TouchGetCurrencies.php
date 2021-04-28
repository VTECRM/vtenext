<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@134732 */

class TouchGetCurrencies extends TouchWSClass {

	function process(&$request) {
		global $touchInst, $touchCache;
		
		$all = ($request['getall'] == 'true');

		$currencies = array();
		
		$IUtils = InventoryUtils::getInstance();
		$curr = $IUtils->getAllCurrencies($all ? 'all' : 'available');
		
		// transform the array
		if (is_array($curr)) {
			foreach ($curr as $c) {
				$currencies[] = array(
					'currencyid' => intval($c['curid']),
					'name' => $c['currencylabel'],
					'code' => $c['currencycode'],
					'symbol' => html_entity_decode($c['currencysymbol'], ENT_COMPAT, 'UTF-8'),
					'symbol_html' => $c['currencysymbol'],
					'rate' => floatval($c['conversionrate']),
				);
			}
		}

		return $this->success(array('currencies' => $currencies, 'total' => count($currencies)));
	}

}
