<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once 'modules/VteCore/EditView.php';	//crmv@30447

// crmv@69743
$changedFields = array();

global $table_prefix;
if(!empty($_REQUEST['service_id'])) {
	$serviceObj = CRMEntity::getInstance('Services');
	if(isRecordExists($_REQUEST['service_id'])) {
		$serviceObj->retrieve_entity_info($_REQUEST['service_id'], 'Services');
		$changedFields['tracking_unit'] = $serviceObj->column_fields['service_usageunit'];
		//crmv@16644
		if(!empty($_REQUEST['return_id']) && $_REQUEST['return_module'] == 'SalesOrder') {
			$result = $adb->pquery("SELECT productid,quantity FROM ".$table_prefix."_inventoryproductrel WHERE id = ? AND productid = ?",array($_REQUEST['return_id'],$_REQUEST['service_id']));
			if ($result) {
				$order_quantity = $adb->query_result($result,0,'quantity');
				$service_quantity = $serviceObj->column_fields['qty_per_unit'];
				$result = $adb->query("SELECT SUM(total_units) as total_units FROM ".$table_prefix."_servicecontracts
										INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_servicecontracts.servicecontractsid
										WHERE deleted = 0 AND sorder_id = ".$_REQUEST['return_id']." AND service_id = ".$_REQUEST['service_id']."
										GROUP BY sorder_id");
				if ($result) $sum = $adb->query_result($result,0,'total_units');
				$changedFields['total_units'] = ($service_quantity*$order_quantity)-$sum;
			}
		}
		//crmv@16644e
	}
}
if(!empty($_REQUEST['return_id']) && !empty($_REQUEST['return_module'])) {
	$invModule = $_REQUEST['return_module'];
	$inventoryObj = CRMEntity::getInstance($invModule);
	$inventoryObj->retrieve_entity_info($_REQUEST['return_id'], $invModule);
	if(empty($_REQUEST['sc_related_to'])) {
		if(!empty($inventoryObj->column_fields['account_id'])) {
			$changedFields['sc_related_to_type'] = 'Accounts';
			$changedFields['sc_related_to'] = $inventoryObj->column_fields['account_id'];
		} else if(!empty($inventoryObj->column_fields['contact_id'])) {
			$changedFields['sc_related_to_type'] = 'Contacts';
			$changedFields['sc_related_to'] = $inventoryObj->column_fields['contact_id'];		
		}
	}
	//crmv@16644
	if ($invModule == 'SalesOrder') $changedFields['sorder_id'] = $_REQUEST['return_id'];
	//crmv@16644e
}

// now reload the blocks
if (!empty($changedFields)) {
	$focus->column_fields = array_merge($focus->column_fields, $changedFields);
	$smarty->assign("BLOCKS",getBlocks($currentModule,$disp_view,$mode,$focus->column_fields)); // crmv@104568
}
// crmv@69743e

$smarty->display('salesEditView.tpl');