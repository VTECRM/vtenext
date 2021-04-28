<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@98866

global $current_user; // crmv@183418

$longFields = array('subject', 'location', 'assigned_user_id');
$editSkipFields = array('date_start', 'due_date', 'recurringtype', 'parent_id', 'reminder_time', 'is_all_day_event');

$smarty->assign("LONG_FIELDS", $longFields);
$smarty->assign("EDIT_SKIP_FIELDS", $editSkipFields);
$smarty->assign("CALENDAR_POPUP", true);
$smarty->assign("WEEKSTART", $current_user->weekstart); // crmv@183418

$smarty_template = "modules/Calendar/AddEventForm.tpl";