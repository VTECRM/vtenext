<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/*
 * Generates preview for the chart
 */
 
/* crmv@82770 */
require_once('modules/Charts/Charts.php');

global $current_user, $app_strings, $mod_strings, $theme;


$chartInst = CRMEntity::getInstance('Charts');

$chartInst->thumbnail_size = 300; // override default thumb size

// set default empty options
$chartInst->column_fields['chartname'] = 'test';
$chartInst->column_fields['assigned_user_id'] = $current_user->id;
$chartInst->column_fields['chart_type'] = 'Pie';
$chartInst->column_fields['chart_legend'] = 0;
$chartInst->column_fields['chart_labels'] = 0;
$chartInst->column_fields['chart_values'] = 0;
$chartInst->column_fields['chart_merge_small'] = 0;
$chartInst->column_fields['chart_exploded'] = 0;
$chartInst->column_fields['chart_report_lev'] = 'Level1';
$chartInst->column_fields['chart_order_data'] = 'OrderNone';
$chartInst->column_fields['chart_values'] = 'ChartValuesNone';


// get options from request
foreach ($_REQUEST as $var=>$val) {
	if (array_key_exists($var, $chartInst->column_fields)) {
		$val = vtlib_purify($val);
		if ($val == 'on') $val = 1;
		$chartInst->column_fields[$var] = $val;
	}
}

// set the vtetheme palette if nothing else is defined and the chart is bar or line
$type = $chartInst->column_fields['chart_type'];
if (empty($chartInst->column_fields['chart_palette']) && in_array($type, array('BarHorizontal', 'BarVertical', 'Line')))  {
	$chartInst->column_fields['chart_palette'] = 'vtetheme';
}

if ($chartInst->chartLibrary == 'pChart') {
	
	// old pChart code
	$fname = $chartInst->generateChart(true);
	$thumbfile = $chartInst->createThumbnail($fname);
	$chartdata = null;
	if (empty($fname)) die("Error");
	
} elseif ($chartInst->chartLibrary == 'ChartJS') {

	// inverted, to have the right sizes
	$thumbfile = $chartInst->createThumbnail(null);
	$chartdata = $chartInst->generateChart(true);

}

$smarty = new VteSmarty();

$smarty->assign('APP', $app_strings);
$smarty->assign('MOD', return_module_language($current_language,'Charts'));
$smarty->assign('THEME', $theme);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");

$smarty->assign('CHART_TITLE', $chartInst->getChartTitle());
$smarty->assign('CHART_PATH', $thumbfile);
$smarty->assign('CHART_DATA', $chartdata);

if ($chartInst->chartLibrary == 'pChart') {
	$smarty->display('modules/Charts/RenderChart.tpl');
} elseif ($chartInst->chartLibrary == 'ChartJS') {
	$smarty->display('modules/Charts/RenderChartJS.tpl');
} else {
	throw new Exception("Chart library '{$chartInst->chartLibrary}' is not supported");
}