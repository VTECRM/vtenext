<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@34559 */

class TouchGetTicketComments extends TouchWSClass {

	function process(&$request) {
		global $current_user, $touchInst, $touchUtils;

		$module = 'HelpDesk';
		$recordid = intval($request['recordid']);

		if (in_array($module, $touchInst->excluded_modules)) return $this->error('Module not permitted');


		$comm_out = array();
		$focus = $touchUtils->getModuleInstance($module);
		$r = $focus->retrieve_entity_info($recordid, $module, false);
		if ($r != 'LBL_RECORD_DELETE') {
			$comm = $focus->get_ticket_comments_list($recordid);
			if (is_array($comm)) {
				foreach ($comm as $k => $c) {
					$comm_out[] = array(
						'commentid' => intval($c['commentid']),
						'ticketid' => intval($c['ticketid']),
						'comment' => $c['comments'],
						'author' => $c['owner'],
						'assigned_user_id' => intval($c['ownerid']),
						'ownertype' => $c['ownertype'],
						'timestamp' => strtotime($c['createdtime']),
					);
				}
				$comm_out = array_reverse($comm_out);
			}
		}

		return $this->success(array('comments'=>$comm_out, 'total'=>count($comm_out)));
	}
}
