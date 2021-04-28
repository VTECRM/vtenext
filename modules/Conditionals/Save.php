<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@155145 crmv@173271 */

global $adb,$table_prefix;

$ruleid = intval($_REQUEST['ruleid']);
$total_conditions = intval($_REQUEST['total_conditions']);
$module_name = $_REQUEST['module_name'];
$condName = $_REQUEST['workflow_name'];
$condRoles = $_REQUEST['role_grp_check'];

$conditionals = CRMEntity::getInstance('Conditionals');

// build conditional object
$cond = array(
	'name' => $condName,
	'module' => $module_name,
	'roles' => $condRoles,
	'conditions' => array(),
	'fields' => array(),
);

// get conditions
if ($total_conditions > 0) {
	for ($j=0; $j<$total_conditions; $j++) {
		if ($_REQUEST["deleted".$j] == 1) continue;
		$cond['conditions'][] = array(
			'fieldname' => $_REQUEST['field'.$j],
			'criteria' => $_REQUEST['criteria_id'.$j],
			'value' => $_REQUEST['field_value'.$j],
		);
	}
}

// get fields
$query = 
	"SELECT fieldname
	FROM {$table_prefix}_field f
	INNER JOIN {$table_prefix}_tab t ON t.tabid = f.tabid 
	WHERE t.name = ?";
$result = $adb->pquery($query, array($module_name));
if ($result && $adb->num_rows($result) > 0) {
	while ($row = $adb->fetchByAssoc($result, -1, false)) {
		$fieldname = $row['fieldname'];
		if (array_key_exists("FpovManaged".$fieldname,$_REQUEST)) {
			$cond['fields'][$fieldname] = array(
				'read' => ($_REQUEST['FpovReadPermission'.$fieldname] == "1"),
				'write' => ($_REQUEST['FpovWritePermission'.$fieldname] == "1"),
				'mandatory' => ($_REQUEST['FpovMandatoryPermission'.$fieldname] == "1"),
			);
		} 
	}
}

$ruleid = $conditionals->saveConditional($ruleid, $cond);

// crmv@77249
if ($_REQUEST['included'] == true) {
	$params = array(
		'included' => 'true',
		'skip_vte_header' => 'true',
		'skip_footer' => 'true',
		'formodule' => $_REQUEST['formodule'],
		'statusfield' => $_REQUEST['statusfield'],
	);
	$otherParams = "&".http_build_query($params);
}
// crmv@77249e

header("Location: index.php?module=Conditionals&action=index&parenttab=Settings".$otherParams);