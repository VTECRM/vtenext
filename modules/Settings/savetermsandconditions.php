<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb, $table_prefix;

$inv_type = 'Inventory';
$inv_tandc = from_html($_REQUEST['inventory_tandc']);

$sql="SELECT id FROM {$table_prefix}_inventory_tandc WHERE type=?";
$result = $adb->pquery($sql, array($inv_type));
$inv_id = $adb->query_result($result,0,'id');
if($inv_id == '') {
	$inv_id = $adb->getUniqueID($table_prefix.'_inventory_tandc');
    $sql="insert into ".$table_prefix."_inventory_tandc (id, type, tandc) values (?,?,?)";
	$params = array($inv_id, $inv_type, $inv_tandc);
} else {
	$sql="update ".$table_prefix."_inventory_tandc set type = ?, tandc = ? where id = ?";
	$params = array($inv_type, $inv_tandc, $inv_id);
}
$adb->pquery($sql, $params);

header("Location: index.php?module=Settings&action=OrganizationTermsandConditions&parenttab=Settings");
