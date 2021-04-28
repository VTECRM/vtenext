<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// install demo graphs
global $current_user, $currentModule;
global $adb, $table_prefix;

require_once('include/utils/utils.php');

$folder = getEntityFoldersByName('Esempi', 'Charts');
if (empty($folder)) {
	addEntityFolder('Charts', 'Esempi', '', 1);
	$folder = getEntityFoldersByName('Esempi', 'Charts');
}

if (!empty($folder)) $folder = $folder[0];

$chartsinfo = array(
	array(
		'chartname' => 'Attività',
		'reportname' => 'Activities by users',//crmv@208568
		'chart_type' => 'BarVertical',
		'chart_legend' => 0,
		'chart_labels' => 1,
		'chart_exploded' => 0,
		'chart_values' => 'ChartValuesNone',
		'chart_order_data' => 'OrderNone',
		'chart_merge_small' => 1,
		'chart_palette' => 'default',
		'folderid' => $folder['folderid'],
		'chart_formula' => 'COUNT',
	),
	array(
		'chartname' => 'Attività - torta',
		'reportname' => 'Activities by users',//crmv@208568
		'chart_type' => 'Pie',
		'chart_legend' => 1,
		'chart_labels' => 0,
		'chart_exploded' => 1,
		'chart_values' => 'ChartValuesPercent',
		'chart_order_data' => 'OrderNone',
		'chart_merge_small' => 1,
		'chart_palette' => 'default',
		'folderid' => $folder['folderid'],
		'chart_formula' => 'COUNT',
	),
	array(
		'chartname' => 'Conteggio per stato',
		'reportname' => 'Quotes by status',//crmv@208568
		'chart_type' => 'BarVertical',
		'chart_legend' => 0,
		'chart_labels' => 1,
		'chart_exploded' => 0,
		'chart_values' => 'ChartValuesNone',
		'chart_order_data' => 'OrderNone',
		'chart_merge_small' => 1,
		'chart_palette' => 'default',
		'folderid' => $folder['folderid'],
		'chart_formula' => 'COUNT',
	),
	array(
		'chartname' => 'Totale per stato',
		'reportname' => 'Quotes by status',//crmv@208568
		'chart_type' => 'BarVertical',
		'chart_legend' => 0,
		'chart_labels' => 1,
		'chart_exploded' => 0,
		'chart_values' => 'ChartValuesNone',
		'chart_order_data' => 'OrderNone',
		'chart_merge_small' => 1,
		'chart_palette' => 'default',
		'folderid' => $folder['folderid'],
		'chart_formula' => 'SUM',
	),
	array(
		'chartname' => 'Preventivi Creati',
		'reportname' => 'Created quotes by users',//crmv@208568
		'chart_type' => 'BarVertical',
		'chart_legend' => 0,
		'chart_labels' => 1,
		'chart_exploded' => 0,
		'chart_values' => 'ChartValuesNone',
		'chart_order_data' => 'OrderNone',
		'chart_merge_small' => 1,
		'chart_palette' => 'default',
		'folderid' => $folder['folderid'],
		'chart_formula' => 'COUNT',
	),
	array(
		'chartname' => 'Totale Preventivi Creati',
		'reportname' => 'Created quotes by users',//crmv@208568
		'chart_type' => 'BarVertical',
		'chart_legend' => 0,
		'chart_labels' => 1,
		'chart_exploded' => 0,
		'chart_values' => 'ChartValuesNone',
		'chart_order_data' => 'OrderNone',
		'chart_merge_small' => 1,
		'chart_palette' => 'default',
		'folderid' => $folder['folderid'],
		'chart_formula' => 'SUM',
	),
	array(
		'chartname' => 'Preventivi Revisionati',
		'reportname' => 'Reviewed quotes by users',//crmv@208568
		'chart_type' => 'Pie',
		'chart_legend' => 0,
		'chart_labels' => 1,
		'chart_exploded' => 0,
		'chart_values' => 'ChartValuesNone',
		'chart_order_data' => 'OrderNone',
		'chart_merge_small' => 1,
		'chart_palette' => 'default',
		'folderid' => $folder['folderid'],
		'chart_formula' => 'COUNT',
	),
	array(
		'chartname' => 'Totale Preventivi Revisionati',
		'reportname' => 'Reviewed quotes by users',//crmv@208568
		'chart_type' => 'Pie',
		'chart_legend' => 0,
		'chart_labels' => 1,
		'chart_exploded' => 0,
		'chart_values' => 'ChartValuesNone',
		'chart_order_data' => 'OrderNone',
		'chart_merge_small' => 1,
		'chart_palette' => 'default',
		'folderid' => $folder['folderid'],
		'chart_formula' => 'SUM',
	),
	array(
		'chartname' => 'Conteggio aziende',
		'reportname' => 'Accounts by users',//crmv@208568
		'chart_type' => 'Pie',
		'chart_legend' => 0,
		'chart_labels' => 1,
		'chart_exploded' => 0,
		'chart_values' => 'ChartValuesNone',
		'chart_order_data' => 'OrderNone',
		'chart_merge_small' => 1,
		'chart_palette' => 'default',
		'folderid' => $folder['folderid'],
		'chart_formula' => 'COUNT',
	),
	array(
		'chartname' => 'Opportunità per stato',
		'reportname' => 'Potentials by status',//crmv@208568
		'chart_type' => 'BarVertical',
		'chart_legend' => 0,
		'chart_labels' => 1,
		'chart_exploded' => 0,
		'chart_values' => 'ChartValuesNone',
		'chart_order_data' => 'OrderNone',
		'chart_merge_small' => 1,
		'chart_palette' => 'default',
		'folderid' => $folder['folderid'],
		'chart_formula' => 'COUNT',
	),
	array(
		'chartname' => 'Totale Opportunità per stato',
		'reportname' => 'Potentials by status',//crmv@208568
		'chart_type' => 'BarVertical',
		'chart_legend' => 0,
		'chart_labels' => 1,
		'chart_exploded' => 0,
		'chart_values' => 'ChartValuesNone',
		'chart_order_data' => 'OrderNone',
		'chart_merge_small' => 1,
		'chart_palette' => 'default',
		'folderid' => $folder['folderid'],
		'chart_formula' => 'SUM',
	),
	array(
		'chartname' => 'Leads per utente',
		'reportname' => 'Leads count',//crmv@208568
		'chart_type' => 'Ring',
		'chart_legend' => 1,
		'chart_labels' => 0,
		'chart_exploded' => 0,
		'chart_values' => 'ChartValuesRaw',
		'chart_order_data' => 'OrderNone',
		'chart_merge_small' => 1,
		'chart_palette' => 'default',
		'folderid' => $folder['folderid'],
		'chart_formula' => 'COUNT',
	),
	array(
		'chartname' => 'Contatti per utente',
		'reportname' => 'Contacts by users',//crmv@208568
		'chart_type' => 'BarVertical',
		'chart_legend' => 0,
		'chart_labels' => 1,
		'chart_exploded' => 0,
		'chart_values' => 'ChartValuesNone',
		'chart_order_data' => 'OrderNone',
		'chart_merge_small' => 1,
		'chart_palette' => 'default',
		'folderid' => $folder['folderid'],
		'chart_formula' => 'COUNT',
	),
	array(
		'chartname' => 'Totale Fatture',
		'reportname' => 'Invoice total',//crmv@208568
		'chart_type' => 'Pie',
		'chart_legend' => 0,
		'chart_labels' => 1,
		'chart_exploded' => 0,
		'chart_values' => 'ChartValuesNone',
		'chart_order_data' => 'OrderNone',
		'chart_merge_small' => 1,
		'chart_palette' => 'default',
		'folderid' => $folder['folderid'],
		'chart_formula' => 'SUM',
	),
	array(
		'chartname' => 'Totale Fatture per stato',
		'reportname' => 'Invoices by status',//crmv@208568
		'chart_type' => 'BarHorizontal',
		'chart_legend' => 0,
		'chart_labels' => 1,
		'chart_exploded' => 0,
		'chart_values' => 'ChartValuesNone',
		'chart_order_data' => 'OrderNone',
		'chart_merge_small' => 1,
		'chart_palette' => 'default',
		'folderid' => $folder['folderid'],
		'chart_formula' => 'SUM',
	),
	array(
		'chartname' => 'Totale Ordini',
		'reportname' => 'SalesOrder by status',//crmv@208568
		'chart_type' => 'Pie',
		'chart_legend' => 0,
		'chart_labels' => 1,
		'chart_exploded' => 0,
		'chart_values' => 'ChartValuesNone',
		'chart_order_data' => 'OrderNone',
		'chart_merge_small' => 1,
		'chart_palette' => 'default',
		'folderid' => $folder['folderid'],
		'chart_formula' => 'SUM',
	),
);

foreach ($chartsinfo as $k => $chartinfo) {
	// get report id
	$reportid = 0;
	$res = $adb->pquery("select reportid from {$table_prefix}_report where reportname = ?", array($chartinfo['reportname']));
	if ($res) $reportid = $adb->query_result($res, 0, 'reportid');

	if (empty($reportid)) continue;

	unset($chartinfo['reportname']);
	$chartinfo['reportid'] = $reportid;

	// controlla esistenza
	$chartid = 0;
	$res = $adb->pquery("select chartid from {$table_prefix}_charts where chartname = ?", array($chartinfo['chartname']));
	if ($res && $adb->num_rows($res) > 0) $chartid = $adb->query_result($res, 0, 'chartid');
	//if ($chartid > 0) continue;

	$chartInst = CRMEntity::getInstance('Charts');

	if ($chartid > 0) {
		$chartInst->mode = 'edit';
		$chartInst->retrieve_entity_info($chartid, 'Charts');
		$chartInst->id = $chartid;
	} else {
		$chartInst->mode = '';
	}

	foreach ($chartinfo as $k=>$v) {
		$chartInst->column_fields[$k] = $v;
	}

	if ($chartInst->column_fields['assigned_user_id'] == '') {
		$chartInst->column_fields['assigned_user_id'] = 1;
	}
	$chartInst->save('Charts', false, false, false);
}
?>