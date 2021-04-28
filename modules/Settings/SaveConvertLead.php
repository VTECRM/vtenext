<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@29463
global $adb,$table_prefix;

$deletSQL = "DELETE FROM ".$table_prefix."_convertleadmapping WHERE editable=1";
$adb->pquery($deletSQL, array());
$insertSQL = "INSERT INTO ".$table_prefix."_convertleadmapping(cfmid, leadfid,accountfid,contactfid,potentialfid) VALUES(?,?,?,?,?)";
//$map = vtlib_purify($_REQUEST['map']);
$map = $_REQUEST['map'];

foreach ($map as $mapping) {
	if (!((empty($mapping['Accounts'])) && (empty($mapping['Contacts'])) && (empty($mapping['Potentials'])))) {
		$id = $adb->getUniqueID($table_prefix."_convertleadmapping");
		$adb->pquery($insertSQL, array($id, $mapping['Leads'], $mapping['Accounts'], $mapping['Contacts'], $mapping['Potentials']));
	}
}
header("Location: index.php?module=Settings&action=LeadCustomFieldMapping");	//crmv@29463
//crmv@29463e
?>