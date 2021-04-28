<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@38798
//crmv@203484 removed including file

global $adb, $currentModule, $table_prefix;//crmv@203484 removed global singlepane
global $current_user;
//crmv@203484
$VTEP = VTEProperties::getInstance();
$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
//crmv@203484e
$idlist = vtlib_purify($_REQUEST['idlist']);
$dest_mod = vtlib_purify($_REQUEST['destination_module']);
$parenttab = getParentTab();

$forCRMRecord = vtlib_purify($_REQUEST['parentid']);
$mode = $_REQUEST['mode'];

// crmv@200009
require_once('include/utils/VTEProperties.php');
$VTEP = VTEProperties::getInstance();
$limit = $VTEP->getProperty('loadrelations.limit');
$URtils = LoadRelationsUtils::getInstance();
// crmv@200009e

if($singlepane_view == true)//crmv@203484 changed to normal bool true, not string 'true'
	$action = "DetailView";
else
	$action = "CallRelatedList";

$focus = CRMEntity::getInstance($currentModule);

if($mode == 'delete') {

	// Split the string of ids
	$ids = array_filter(explode (";",$idlist));
	if (!empty($ids)) {
		$focus->delete_related_module($currentModule, $forCRMRecord, $dest_mod, $ids);
	}

} else {

	$ids = array();
	if (!empty($idlist)) {
		// Split the string of ids
		$ids = array_filter(explode(";", trim($idlist,";")));
	} elseif (!empty($_REQUEST['entityid'])){
		$ids = array(intval($_REQUEST['entityid']));
	}

	// crmv@200009
	$howMuch = count($ids);
	if ($howMuch > 0) {
	    if($howMuch < $limit)
		    $focus->save_related_module($currentModule, $forCRMRecord, $dest_mod, $ids);
	    else
            $URtils->enqueue($current_user->id, $currentModule, $forCRMRecord, $dest_mod, $ids);
	}
	// crmv@200009e
}

// crmv@37004
if ($_REQUEST['no_redirect'] == true) {

} else {
	header("Location: index.php?module=$currentModule&record=$forCRMRecord&action=$action&parenttab=$parenttab");
}
// crmv@37004e
?>