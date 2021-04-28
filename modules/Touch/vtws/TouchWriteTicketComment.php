<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@34559 */
global $login, $userId, $current_user;

$module = 'HelpDesk';
$recordid = intval($_REQUEST['recordid']);
$comment = vtlib_purify($_REQUEST['comment']);

if (!$login || !$userId) {
	echo 'Login Failed';
} elseif (in_array($module, $touchInst->excluded_modules)) {
	echo "Module not permitted";
} else {


	$focus = CRMEntity::getInstance($module);
	$r = $focus->retrieve_entity_info($recordid, $module, false);
	if ($r != 'LBL_RECORD_DELETE') {

		$comment = str_replace(array('&amp;', '&lt;', '&gt;'), array('&', '<', '>'), $comment);

		// OLD CODE, doesn't send emails
		/*$focus->id = $recordid;
		$focus->column_fields['comments'] = $comment;
		$focus->insertIntoTicketCommentTable('ticketcomments', $module);
		*/

		// fake detailviewajax, so email are sent
		global $currentModule, $app_strings, $mod_strings;
		$currentModule = $module;

		$app_strings = return_application_language($current_language);
		$mod_strings = return_module_language($current_language, $currentModule);

		unset($_REQUEST);
		$_REQUEST["module"] = 'HelpDesk';
		$_REQUEST["action"] = 'HelpDeskAjax';
		$_REQUEST["file"] = 'DetailViewAjax';
		$_REQUEST["ajxaction"] = 'DETAILVIEW';
		$_REQUEST["recordid"] = $recordid;
		//$_REQUEST["tableName"];
		$_REQUEST["fldName"] = 'comments';
		$_REQUEST["fieldValue"] = $comment;

		// prevent output
		ob_start();
		require('modules/HelpDesk/DetailViewAjax.php');
		ob_end_clean();
	}

	echo 'SUCCESS';
}
?>