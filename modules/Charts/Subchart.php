<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@83040 */
require_once('modules/Charts/Charts.php');

global $current_user, $app_strings, $mod_strings, $theme;

$action = $_REQUEST['subaction'];
$chartInst = CRMEntity::getInstance('Charts');


if ($action == 'getdata') {
	$chartid = intval($_REQUEST['chartid']);
	$level= intval($_REQUEST['level']);
	$dataids = Zend_Json::decode($_REQUEST['dataids']);

	$chartInst->retrieve_entity_info($chartid, 'Charts');
	$chartdata = $chartInst->generateChart(false, $level, $dataids);
	
	$result = array('success' => true, 'result' => array('data' => $chartdata));
	
	echo Zend_Json::encode($result);
	die();
}