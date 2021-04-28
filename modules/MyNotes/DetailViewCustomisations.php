<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@168573 removed widget code

if ($_REQUEST['mode'] == 'SimpleView') {
	VteSession::set('mynote_selected', $focus->id);
	$smarty->assign('SHOW_TURBOLIFT_BACK_BUTTON','no');
	$smarty->assign('TURBOLIFT_HREF_TARGET_LOCATION','window.top.location.href');
	$smarty_template = "modules/$currentModule/MyNotesDetailView.tpl";
} elseif ($_REQUEST['action'] == 'DetailView' || $_REQUEST['file'] == 'DetailView') {

	// crmv@146652
	if ($_REQUEST['action'] == 'DetailView' && $record > 0) {
		// try to open the linked detailview and the notes popup inside
		// search for the first linked visible record, otherwise open the home page
		$RM = RelationManager::getInstance();
		$excludeMods = array('ModComments', 'MyNotes'); // crmv@164120
		$links = $RM->getRelatedIds('MyNotes', $record, array(), $excludeMods, false, true);
		if (is_array($links)) {
			$linkid = null;
			foreach ($links as $linkmodule => $crmids) {
				foreach ($crmids as $crmid) {
					if (isPermitted($linkmodule, 'DetailView', $crmid) == 'yes') {
						$linkid = $crmid;
						break 2;
					}
				}
			}
			if ($linkid > 0) {
				header("Location: index.php?module=$linkmodule&action=DetailView&record=$linkid&openNote=$record");
				exit;
			}
		}
		header("Location: index.php?module=Home&action=index&openNote=$record");
		exit;
	}
	// crmv@146652e
	
	include("modules/$currentModule/SimpleView.php");
	exit;
}