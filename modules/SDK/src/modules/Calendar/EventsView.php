<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $sdk_mode, $current_user;
//crmv@158871
if (in_array($sdk_mode,array('edit','detail')) && $fieldname == 'visibility' && $col_fields['visibility'] == 'Private' && !is_admin($current_user) && $col_fields['assigned_user_id'] != $current_user->id/* && isCalendarInvited($current_user->id,$col_fields['record_id'],true) == 'no'*/) {
	$readonly = 99;
	$success = true;
}
//crmv@158871e

// crmv@194723 crmv@202029
if (in_array($fieldname, array('date_start', 'due_date', 'assigned_user_id'))) {
	$subaction = $_REQUEST['subaction'] ?? '';

	if ($subaction === 'CalendarResourcesEdit' && $mode === 'edit') {
		$modifiedUser = (int) $_REQUEST['modified_user'] ?? '';
		$modifiedDate = $_REQUEST['modified_date'] ?? '';
		
		if (in_array($fieldname, array('date_start', 'due_date'))) {
			$areDatesChanged = $areDatesChanged ?? false;
			if (!empty($modifiedDate) && !$areDatesChanged) {
				$fromDate = new DateTime(date('Y-m-d', strtotime($col_fields['date_start'])));
				$toDate = new DateTime(date('Y-m-d', strtotime($col_fields['due_date'])));
				$daysDiff = (int) $fromDate->diff($toDate)->format('%r%a');
				
				$modifiedDate = new DateTime(date('Y-m-d', strtotime($modifiedDate)));
				$col_fields['date_start'] = $modifiedDate->format('Y-m-d');
				
				$modifiedDate->modify(($daysDiff > 0 ? '+'.$daysDiff : $daysDiff) . ' days');
				$col_fields['due_date'] = $modifiedDate->format('Y-m-d');

				$areDatesChanged = true;
			}
		}
		if ($fieldname === 'assigned_user_id') {
			if ($modifiedUser > 0) {
				$col_fields['assigned_user_id'] = $modifiedUser;
			}
		}
	}
}
// crmv@194723e crmv@202029e