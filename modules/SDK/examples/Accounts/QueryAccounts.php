<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
function queryAccounts($module = '') {
	global $current_user,$table_prefix;
	
	if($module == 'Accounts')
		$table = $table_prefix.'_account';
	elseif($module == 'Vendors')
		$table = $table_prefix.'_vendor';
	elseif ($module = 'Leads')
		$table = $table_prefix.'_leaddetails';
	else
		return '';

	$filter = '';
	if ($current_user->business_unit != ''){
		
		$business_unit = explode(' |##| ',$current_user->business_unit);
		if (is_array($business_unit)){
			$sec_query_tmp = array();
			foreach($business_unit as $bu)
				$sec_query_tmp[] = "$table.business_unit like '%$bu%'";
			$filter = ' or ('.implode(' or ',$sec_query_tmp).')';
		}
	}
	return $filter;
}
?>