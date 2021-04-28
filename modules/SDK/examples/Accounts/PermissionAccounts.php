<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
function permAccounts($module, $actionname, $record_id='') {
	global $current_user;
	if (in_array($module,array('Accounts','Vendors','Leads')) && $current_user->business_unit != '' && (in_array(getSalesEntityType($record_id),array('Accounts','Vendors','Leads')))){
		$business_unit = explode(' |##| ',$current_user->business_unit);
		if (is_array($business_unit)){		
			$acc = CRMEntity::getInstance(getSalesEntityType($record_id));
		    $acc->retrieve_entity_info($record_id,getSalesEntityType($record_id));
		    
		    $business_unit_acc = explode(' |##| ',$acc->column_fields['business_unit']);
		    foreach($business_unit as $bu) {
		    	if (in_array($bu,$business_unit_acc)) {
		    		return 'yes';
		    	}
		    }
		}
	}
	return '';
}
?>