<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/Calendar/Activity.php');
require_once('modules/Calendar/CalendarCommon.php');

/* crmv@95751 */
// crmv@98866

global $table_prefix;
$local_log =& LoggerManager::getLogger('index');

$focus = CRMEntity::getInstance('Activity');
$activity_mode = $_REQUEST['activity_mode'];

// first set the values from the request
foreach($focus->column_fields as $fieldname => $val) {
	if(isset($_REQUEST[$fieldname])) {
		if(is_array($_REQUEST[$fieldname]))
			$value = $_REQUEST[$fieldname];
		else
			$value = trim($_REQUEST[$fieldname]);
		$focus->column_fields[$fieldname] = $value;
	}
}

// then set the specific task values

if($activity_mode == 'Task') {
	$tab_type = 'Calendar';
	$focus->column_fields["activitytype"] = 'Task';
}

if(isset($_REQUEST['record'])) {
	$focus->id = intval($_REQUEST['record']);
}

if(isset($_REQUEST['mode'])) {
	$focus->mode = $_REQUEST['mode'];
}

//crmv@31171
if($_REQUEST['assigntype'] == 'U') {
	$focus->column_fields["assigned_user_id"] =  $_REQUEST["assigned_user_id"];
} elseif($_REQUEST['assigntype'] == 'T') {
	$focus->column_fields["assigned_user_id"] =  $_REQUEST["assigned_group_id"];
}
//crmv@31171e

// DS-CR VlMe 31.3.2008 ToDo is not able to save while visibility has no value
if((!isset($_REQUEST["visibility"]) || $_REQUEST["visibility"]=="") && (!isset($focus->column_fields["visibility"]) || $focus->column_fields["visibility"]=="" || is_null($focus->column_fields["visibility"]))){
	$focus->column_fields["visibility"]="all";
}
// DS-END

$focus->save($tab_type);

//crmv@20628
if ($_REQUEST['ajaxCalendar'] == 'detailedAdd') {
	exit;
} else {
//crmv@20628e
	//crmv@17001
	// header("Location: index.php?action=index&module=Calendar&view=".$_REQUEST['view']."&hour=".$_REQUEST['hour']."&day=".$_REQUEST['day']."&month=".$_REQUEST['month']."&year=".$_REQUEST['year']."&viewOption=".$_REQUEST['viewOption']."&subtab=todo&parenttab=".$_REQUEST['parenttab']);
	$res = $adb->query("SELECT cvid FROM ".$table_prefix."_customview WHERE entitytype = 'Calendar' AND viewname = 'Tasks' AND status = 0");
	if ($res && $adb->num_rows($res)>0) $viewname = $adb->query_result($res,0,'cvid');
	header("Location: index.php?action=ListView&module=Calendar&parenttab=".$_REQUEST['parenttab']."&viewname=$viewname");
	//crmv@17001e
}
?>