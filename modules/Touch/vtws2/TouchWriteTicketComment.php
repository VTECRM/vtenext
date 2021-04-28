<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@34559 */

class TouchWriteTicketComment extends TouchWSClass {

	function process(&$request) {
		global $touchInst, $touchUtils, $current_user, $current_language;

		$module = 'HelpDesk';
		$recordid = intval($request['recordid']);
		$comment = vtlib_purify($request['comment']);
		$tempId = intval($request['temp_commentid']);

		if (in_array($module, $touchInst->excluded_modules)) return $this->error('Module not permitted');

		$focus = $touchUtils->getModuleInstance($module);
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
			$output = ob_get_clean();
			ob_end_clean();

			if (substr($output, 0, 10) == ':#:SUCCESS') {
				if ($modObj) {
					$commentid = $modObj->lastInsertedCommentId;
					$result = array('ticketid'=>$recordid, 'temp_commentid' => $tempId, 'commentid' => $commentid);
				} else {
					return $this->error('Error saving the comment');
				}

			} else {
				return $this->error('Error saving the comment: '.$output);
			}

		} else {
			return $this->error('The ticket was not found');
		}

		return $this->success(array('result'=>$result));
	}
}
