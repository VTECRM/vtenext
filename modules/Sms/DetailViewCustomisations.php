<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@152701 */

global $currentModule;

// readonly record
$tool_buttons = Button_Check($currentModule);
$tool_buttons['EditView'] = 'no';	//crmv@16834

$smarty->assign('EDIT_PERMISSION', 'notpermitted');
$smarty->assign('EDIT_DUPLICATE', 'notpermitted');
$smarty->assign('CHECK', $tool_buttons);