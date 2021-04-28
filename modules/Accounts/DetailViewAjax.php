<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@67410

global $currentModule, $current_user;

$modObj = CRMEntity::getInstance($currentModule);

$ajaxaction = $_REQUEST["ajxaction"];
if($ajaxaction == 'DETAILVIEW')
{
	$crmid = $_REQUEST['recordid'];
	$tablename = $_REQUEST['tableName'];
	$fieldname = $_REQUEST['fldName'];
	$fieldvalue = utf8RawUrlDecode($_REQUEST['fieldValue']);
	if($crmid != '')
	{
		$permEdit = isPermitted($currentModule, 'DetailViewAjax', $crmid);
		$permField = getFieldVisibilityPermission($currentModule, $current_user->id, $fieldname);
		
		if ($permEdit == 'yes' && $permField == 0) {
			
			$modObj->retrieve_entity_info($crmid, $currentModule);
			// crmv@83877
			if ($fieldname == 'annual_revenue') {
				//annual revenue converted to dollar value while saving
				$fieldvalue = formatUserNumber(getConvertedPrice(parseUserNumber($fieldvalue)));
			}
			$modObj->column_fields[$fieldname] = $fieldvalue;
			// crmv@83877e
			$modObj->id = $crmid;
			$modObj->mode = 'edit';
			$modObj->save($currentModule);
			
			// crmv@193226
			if ($_REQUEST['address_change'] == 'yes') {
				$modObj->updateContactsAddress();
			}
			// crmv@193226e
			
			if($modObj->id != '') {
				echo ':#:SUCCESS';
			} else {
				echo ':#:FAILURE';
			}
		} else {
			echo ":#:FAILURE";
		}
	} else {
		echo ':#:FAILURE';
	}
} elseif($ajaxaction == "LOADRELATEDLIST" || $ajaxaction == "DISABLEMODULE"){
	require_once 'include/ListView/RelatedListViewContents.php';
}
?>