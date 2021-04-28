<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@184240 */

global $table_prefix;

$record = intval($_REQUEST['record']);
$activityid = intval($_REQUEST['return_id']);

if (isPermitted('Calendar', 'EditView', $record) != 'yes') {
	// redirect to settings, where an error will be shown
	header("Location: index.php?module=Settings&action=index&parenttab=Settings");
	die();
}

$sql= 'delete from '.$table_prefix.'_salesmanactivityrel where smid=? and activityid = ?';
$adb->pquery($sql, array($record, $activityid));

if($_REQUEST['return_module'] == 'Calendar')
	$mode ='&activity_mode=Events';

header("Location: index.php?module=".vtlib_purify($_REQUEST['return_module'])."&action=".vtlib_purify($_REQUEST['return_action']).$mode."&record=".$activityid."&relmodule=".vtlib_purify($_REQUEST['module']));