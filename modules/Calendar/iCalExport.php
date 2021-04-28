<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@68357: main rewrite, move methods in external class

define('_BENNU_VERSION', '0.1');

require_once('modules/Calendar/CalendarCommon.php');
require_once('modules/Calendar/iCal/includeAll.php');

global $current_user, $currentModule;
global $adb, $table_prefix; //crmv@62716

// crmv@27651
$filename = vtlib_purify($_REQUEST['filename']);

// generate the query 
$customView = CRMEntity::getInstance('CustomView', $currentModule); // crmv@115329
$viewid = $customView->getViewId($currentModule);

$queryGenerator = new QueryGenerator($currentModule, $current_user);
if ($viewid != "0") {
	$queryGenerator->initForCustomViewById($viewid);
} else {
	$queryGenerator->initForDefaultCustomView();
}

$ical_query = $queryGenerator->getQuery();
//crmv@29407
if(VteSession::hasKey('export_where')){
	$where =VteSession::get('export_where');
	$where = ltrim($where,' and');	//crmv@21448
	$ical_query.=" and ".$where;
}
//crmv@29407e
//$where = $queryGenerator->getConditionalWhere();

// aggiungo join con reminder
if (!preg_match('/join\s+'.$table_prefix.'_activity_reminder/i', $ical_query)) { //crmv@62716
	$ical_query = preg_replace('/inner join '.$table_prefix.'_crmentity/i', 'LEFT JOIN '.$table_prefix.'_activity_reminder ON '.$table_prefix.'_activity_reminder.activity_id = '.$table_prefix.'_activity.activityid AND '.$table_prefix.'_activity_reminder.recurringid=0 INNER JOIN '.$table_prefix.'_crmentity', $ical_query); //crmv@62716
}

// change columns (note, the ?: non-greedy search)
$ical_query = preg_replace('/^(select) .*? (from)/i', '\1 '.$table_prefix.'_activity.*, smownerid, createdtime, modifiedtime, '.$table_prefix.'_activity_reminder.reminder_time \2', $ical_query); //crmv@62716 crmv@68357 crmv@150773

// init helper class
$config = array( "unique_id" => "VTECRM");
$vcalendar = new VTEvcalendar( $config );

$myical = $vcalendar->generateFromSql($ical_query);

// Send the right content type and filename
header('Content-type: text/calendar');
header("Content-Disposition: attachment; filename={$filename}.ics");

// Print the actual calendar
echo $myical->serialize();