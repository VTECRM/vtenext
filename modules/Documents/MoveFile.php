<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb, $current_user, $table_prefix;

if ($current_user->is_admin != 'on') {
	echo 'NOT_PERMITTED';
	die;
} else {
	$new_folderid = $_REQUEST['folderid'];

	if (isset($_REQUEST['idlist']) && $_REQUEST['idlist'] != '') {
		$id_array = array();
		$id_array = implode(',', $_REQUEST['idlist']);

		$sql = "UPDATE {$table_prefix}_notes SET folderid={$new_folderid} WHERE notesid IN ({$id_array})";
		$res = $adb->pquery($sql, array());

		header("Location: index.php?action=DocumentsAjax&file=ListView&mode=ajax&module=Documents");
	}
}
