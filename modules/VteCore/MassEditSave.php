<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@91571 */

global $current_user, $currentModule;

$MUtils = MassEditUtils::getInstance();

//crmv@27096
$idlist = getListViewCheck($currentModule);
$use_worklow = ($_REQUEST['use_workflow'] == 'true');
$enqueue = ($_REQUEST['enqueue'] == 'true');
//crmv@27096e

$return_module = vtlib_purify($_REQUEST['massedit_module']);
($currentModule == 'Documents' || $currentModule == 'Calendar') ? $return_action = 'ListView' : $return_action = 'index';	//crmv@56444 //crmv@60708

global $rstart;
if(isset($_REQUEST['start']) && $_REQUEST['start']!=''){
	$rstart = "&start=".vtlib_purify($_REQUEST['start']);
}

$savedRecords = array();
if(is_array($idlist) && count($idlist) > 0) {

	$massValues = $MUtils->extractValuesFromRequest($currentModule, $_REQUEST);
	
	$r = true;
	if ($enqueue) {
		$r = $MUtils->enqueue($current_user->id, $currentModule, $massValues, $idlist, $use_worklow);
	} else {
		// code for immediate massedit
		foreach ($idlist as $recordid) {
			if($recordid == '') continue;
			$r &= $MUtils->saveRecord($currentModule, $recordid, $massValues, $use_worklow);
		}
	}
	
}

$parenttab = getParentTab();
header("Location: index.php?module=$return_module&action=$return_action&parenttab=$parenttab$rstart");