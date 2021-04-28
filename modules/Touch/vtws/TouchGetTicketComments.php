<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@34559 */
global $login, $userId, $current_user;

$module = 'HelpDesk';
$recordid = intval($_REQUEST['recordid']);

if (!$login || !$userId) {
	echo 'Login Failed';
} elseif (in_array($module, $touchInst->excluded_modules)) {
	echo "Module not permitted";
} else {

	$comm_out = array();
	$focus = CRMEntity::getInstance($module);
	$r = $focus->retrieve_entity_info($recordid, $module, false);
	if ($r != 'LBL_RECORD_DELETE') {
		$comm = $focus->get_ticket_comments_list($recordid);
		if (is_array($comm)) {
			foreach ($comm as $k => $c) {
				$comm_out[] = array(
					'crmid' => 1500010 + $recordid + $k,
					'commentcontent' => $c['comments'],
					'author' => $c['owner'],
					'timestamp' => $c['createdtime'],
					'timestampago' => getFriendlyDate($c['createdtime']),
				);
			}
			$comm_out = array_reverse($comm_out);
		}
	}

	echo Zend_Json::encode($comm_out);
}
?>