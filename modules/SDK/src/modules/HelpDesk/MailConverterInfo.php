<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@2043m crmv@60095 crmv@87556 crmv@181446 */
global $sdk_mode, $adb, $table_prefix;
if ($sdk_mode == "") {
	if ($fieldname == 'parent_id' && $_REQUEST['isDuplicate'] != 'true') { // crmv@157204
		if(isset($_REQUEST['parent_id']) && $_REQUEST['parent_id'] != '') {
			$value = $_REQUEST['parent_id'];
		}
		if($value != '') {
			$result181446 = $adb->pquery("select * from {$table_prefix}_field where tabid = ? and fieldname = ?", array(getTabid('HelpDesk'),'parent_id'));
			$wsField = WebserviceField::fromQueryResult($adb,$result181446,0);
			$referenceList = $wsField->getReferenceList();
			$parent_module = getSalesEntityType($value);
			if (!in_array($parent_module,$referenceList)) {
				$value = '';
			}
		}
		$col_fields['parent_id'] = $value;
	}
}
$success = true;
?>