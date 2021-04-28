<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@98866

$longFields = array('subject', 'assigned_user_id');
$editSkipFields = array('parent_id', 'contact_id', 'exp_duration', 'date_start', 'due_date');

$smarty->assign("LONG_FIELDS", $longFields);
$smarty->assign("EDIT_SKIP_FIELDS", $editSkipFields);
$smarty->assign("CALENDAR_POPUP", true);

$smarty_template = "modules/Calendar/AddTodoForm.tpl";

?>