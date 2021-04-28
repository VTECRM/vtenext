<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 */
/* crmv@49398 */
/* imposta i commenti come letti e ritorna il nuovo conteggio */
require_once('modules/SDK/src/Notifications/Notifications.php');
require_once('modules/ModComments/ModComments.php');

global $login, $userId, $current_user, $currentModule;

$module = 'ModComments';

if (!$login || !$userId) {
	echo 'Login Failed';
} elseif (in_array($module, $touchInst->excluded_modules)) {
	echo "Module not permitted";
} else {

	$currentModule = $module;
	$modObj = CRMEntity::getInstance($module);

	$tempid = intval($_REQUEST['temp_crmid']);

	// parametri
	$parentid = intval($_REQUEST['parent_comment']);
	$relatedto = intval($_REQUEST['related_to']);
	$visibility = vtlib_purify($_REQUEST['visibility']);
	//$userids = array_map('intval', explode('|', $_REQUEST['users_comm'])); // used also internally by ModCommentsCore->save_module
	$comment = vtlib_purify($_REQUEST['comment']);

	if (isPermitted($module, 'EditView', '') == 'yes') {
		$modObj->column_fields['commentcontent'] = $comment;
		$modObj->column_fields['related_to'] = $relatedto;
		$modObj->column_fields['assigned_user_id'] = $current_user->id;

		if ($parentid > 0) {
			$modObj->column_fields['parent_comments'] = $parentid;
		} else {
			$modObj->column_fields['visibility_comm'] = $visibility;
		}

		$modObj->save($currentModule);
		if (empty($modObj->id)) {
			die( Zend_Json::encode('ERROR::Not Saved') );
		}

		$focus = new Notifications($current_user->id,$module);

		// segno come letto tutto il thread
		$parent_comm = intval($modObj->column_fields['parent_comments']);
		if ($parent_comm > 0) {
			$ids = array($parent_comm);
			// recuper gli id da segnare come letti
			$table = $modObj->table_name;
			$tableid = $modObj->table_index;
			$res = $adb->pquery("select $tableid as id from $table where parent_comments = ?", array($parent_comm));
			if ($res) {
				while ($row = $adb->fetchByAssoc($res, -1, false)) {
					$ids[] = $row['id'];
				}
			}
			// elimino notifiche
			if (count($ids) > 0) {
				foreach ($ids as $id) {
					$focus->deleteNotification($id);
				}
			}
		}

		// conteggio
		$unseen =  intval($focus->getUserNotificationNo());
	} else {
		die( Zend_Json::encode('ERROR::Not Permitted') );
	}

	// return
	// TODO:return a proper json object
	echo Zend_Json::encode('SUCCESS::'.$unseen.'::'.$tempid.'::'.($modObj->id));
}
?>