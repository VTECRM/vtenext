<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $mod_strings,$app_strings,$theme,$currentModule,$current_user;

//crmv@27096 crmv@91571 crmv@115378
if ($_REQUEST['check_count'] == 'true') {
	$count = count(getListViewCheck($currentModule));
	$wflimit = PerformancePrefs::getInteger('LISTVIEW_MASS_CHECK_WITH_WORKFLOW');
	if ($count > $wflimit) {
		echo 'enqueue';
	} else {
		echo '';
	}
	echo '###'.$wflimit;
	exit;
}
//crmv@27096e crmv@91571e crmv@115378e

$focus = CRMEntity::getInstance($currentModule);
$focus->mode = '';
$mode = 'mass_edit';

$disp_view = getView($focus->mode);
$idstring = vtlib_purify($_REQUEST['idstring']);

$smarty = new VteSmarty();
//crmv@27096	
//$smarty->assign("IDS",$_REQUEST['idstring']);
$smarty->assign("USE_WORKFLOW",$_REQUEST['use_worklow']);
$smarty->assign("ENQUEUE",$_REQUEST['enqueue']); // crmv@91571
//crmv@27096e
$smarty->assign('MODULE',$currentModule);
$smarty->assign('APP',$app_strings);
$smarty->assign('THEME', $theme);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign('MASS_EDIT','1');
$smarty->assign('BLOCKS',getBlocks($currentModule,$disp_view,$mode,$focus->column_fields));
$smarty->assign("CATEGORY",getParentTab());

// crmv@83877 crmv@112297
// Field Validation Information
$tabid = getTabid($currentModule);
$otherInfo = array();
$validationData = getDBValidationData($focus->tab_name,$tabid,$otherInfo,$focus);	//crmv@96450
$validationArray = split_validationdataArray($validationData, $otherInfo);
$smarty->assign("VALIDATION_DATA_FIELDNAME",$validationArray['fieldname']);
$smarty->assign("VALIDATION_DATA_FIELDDATATYPE",$validationArray['datatype']);
$smarty->assign("VALIDATION_DATA_FIELDLABEL",$validationArray['fieldlabel']);
$smarty->assign("VALIDATION_DATA_FIELDUITYPE",$validationArray['fielduitype']);
$smarty->assign("VALIDATION_DATA_FIELDWSTYPE",$validationArray['fieldwstype']);
// crmv@83877e crmv@112297e

// crmv@102790
if (isProductModule($currentModule) && array_key_exists('taxclass', $validationData)) {
	$InventoryUtils = InventoryUtils::getInstance();
	
	$tax_details = $InventoryUtils->getAllTaxes('available');
	
	$taxids = array_map(function($tax) {
		return $tax['taxid'];
	}, $tax_details);
	
	foreach ($tax_details as &$tax) {
		$tax['check_name'] = $tax['taxname'].'_check';
		$tax['check_value'] = 0;
		$tax['percentage'] = $InventoryUtils->getTaxPercentage($tax['taxname']);
	}
	
	$smarty->assign("TAX_DETAILS", $tax_details);
}
// crmv@102790e

$smarty->display('MassEditForm.tpl');