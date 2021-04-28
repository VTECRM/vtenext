<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $current_user;

$module_list = vtlib_purify($_REQUEST['modules']);
$module_list = explode(',', $module_list);

VteSession::set('__UnifiedSearch_SelectedModules__', $module_list);
$current_user->saveSearchModules($module_list);

?>