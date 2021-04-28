<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

if ($_REQUEST['mode'] == 'SimpleView') {
	exit;
} elseif ($_REQUEST['mode'] == 'DetailViewMyNotesWidget') {
	$parent = vtlib_purify($_REQUEST['parent']);
	if (!empty($parent)) {
		$rel_notes = $focus->getRelNotes($parent,1);
		if (!empty($rel_notes[0])) {
			$rel_note = $rel_notes[0];
		}
		echo Zend_Json::encode(array('success'=>'true','record'=>$rel_note,'parent'=>$parent));
	}
	exit;
}
?>