<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@142955 change ticket status */
class HelpDeskStatusHandler extends VTEventHandler {

	function handleEvent($eventName, $data) {
		global $adb, $table_prefix, $current_user;

		// check if this is a helpdesk class (or subclassed with sdk)
		if (!is_a($data->focus, 'HelpDesk')) return; 
		
		$record = intval($data->getId());
		if ($record == 0) $record = intval($_REQUEST['record']);
		if ($record == 0) $record = intval($_REQUEST['recordid']);
		$column_fields = $data->getData();
		
		if($eventName == 'vte.entity.beforesave' && $record > 0 && ( ( $_REQUEST['fldName'] == 'comments' ) || ( $_REQUEST['mode'] == 'edit' && $_REQUEST['comments'] != '' ) )   ) {
			if ($data->focus->waitForResponseStatus != '') $data->set('ticketstatus',$data->focus->waitForResponseStatus);
		}
	}
}