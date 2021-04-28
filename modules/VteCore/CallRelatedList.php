<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//crmv@203484 removed including file
global $mod_strings, $app_strings, $currentModule, $current_user, $theme;//crmv@203484 removed global singlepane

//crmv@203484
$VTEP = VTEProperties::getInstance();
$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
//crmv@203484e

$category = getParentTab();
$action = vtlib_purify($_REQUEST['action']);
$record = vtlib_purify($_REQUEST['record']);
$isduplicate = vtlib_purify($_REQUEST['isDuplicate']);

if($singlepane_view == true && $action == 'CallRelatedList') {//crmv@203484 changed to normal bool true, not string 'true'
    header("Location:index.php?action=DetailView&module=$currentModule&record=$record&parenttab=$category");
} else {
	
	$tool_buttons = Button_Check($currentModule);

	$focus = CRMEntity::getInstance($currentModule);
	if($record != '') {
	    $focus->retrieve_entity_info($record, $currentModule);
   		$focus->id = $record;
	}

	$smarty = new VteSmarty();

	if($isduplicate == 'true') $focus->id = '';
	if(isset($_REQUEST['mode']) && $_REQUEST['mode'] != ' ') $smarty->assign("OP_MODE",vtlib_purify($_REQUEST['mode']));
	if(!VteSession::getArray(array('rlvs', $currentModule))) {
		VteSession::remove('rlvs');
	}	

	// Identify this module as custom module.
	$smarty->assign('CUSTOM_MODULE', vtlib_isCustomModule($currentModule));

	$smarty->assign('APP', $app_strings);
	$smarty->assign('MOD', $mod_strings);
	$smarty->assign('MODULE', $currentModule);
	// TODO: Update Single Module Instance name here.
	$smarty->assign('SINGLE_MOD', getTranslatedString('SINGLE_'.$currentModule, $currentModule)); 
	$smarty->assign('CATEGORY', $category);
	$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
	$smarty->assign('THEME', $theme);
	$smarty->assign('ID', $focus->id);
	$smarty->assign('MODE', $focus->mode);
	$smarty->assign('CHECK', $tool_buttons);

	if ($focus->def_detailview_recname) {
		$smarty->assign('NAME', $focus->column_fields[$focus->def_detailview_recname]);
	} else {
		$smarty->assign("NAME", getEntityName($currentModule, $record, true));
	}
	
	if (isProductModule($currentModule)) {
		if ($record > 0) {
			$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024
			$product_base_currency = $InventoryUtils->getProductBaseCurrency($focus->id,$currentModule); // crmv@42024
		} else {
			$product_base_currency = fetchCurrency($current_user->id);
		}
		$smarty->assign("CURRENCY_ID",$product_base_currency);
	}
	
	if ($module == 'Contacts') {
		$parent_email = getEmailParentsList($currentModule,$record, $focus);
		$smarty->assign("HIDDEN_PARENTS_LIST",$parent_email);
		$smarty->assign("accountid",$focus->column_fields['accountid']);
	}
	
	$smarty->assign('UPDATEINFO',updateInfo($focus->id));
	
	// Module Sequence Numbering
	$mod_seq_field = getModuleSequenceField($currentModule);
	if ($mod_seq_field != null) {
		$mod_seq_id = $focus->column_fields[$mod_seq_field['name']];
	} else {
		$mod_seq_id = $focus->id;
	}
	$smarty->assign('MOD_SEQ_ID', $mod_seq_id);
	// END

	$related_array = getRelatedLists($currentModule, $focus);
	$smarty->assign('RELATEDLISTS', $related_array);
		
	require_once('include/ListView/RelatedListViewSession.php');
	if(!empty($_REQUEST['selected_header']) && !empty($_REQUEST['relation_id'])) {
		$relationId = vtlib_purify($_REQUEST['relation_id']);
		RelatedListViewSession::addRelatedModuleToSession($relationId,
				vtlib_purify($_REQUEST['selected_header']));
	}
	$open_related_modules = RelatedListViewSession::getRelatedModulesFromSession();
	$smarty->assign("SELECTEDHEADERS", $open_related_modules);
	
	if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] != '')
        $smarty->display("RelatedListContents.tpl");
	else
		$smarty->display('RelatedLists.tpl');
}