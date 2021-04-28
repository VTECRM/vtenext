<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $current_user, $currentModule,$adb,$log;
global $table_prefix;
checkFileAccess("modules/$currentModule/$currentModule.php");
require_once("modules/$currentModule/$currentModule.php");

$focus = CRMEntity::getInstance($currentModule);
setObjectValuesFromRequest($focus);

// TODO: validate time format
// First we fix the time input as we can accept "#h #m" format
$wt = $_REQUEST['worktime'];
if (stripos($wt,'h') > 0) {
    $hr = substr($wt,0,stripos($wt,'h'));
    $rt = substr($wt,stripos($wt,'h')+1);
    if (stripos($rt,'m') > 0) {
        $min = intval(substr($rt,0,stripos($rt,'m')));
        if ($min > 59) {
            $hr  = $hr + intval($min / 60);
            $min = $min % 60;
            $wt  = $hr.':'.$min;
        } else {
            $wt = $hr.':'.$min;
        }
    } else {
        $wt = $hr.':00';
    }
}
if (stripos($wt,'m') > 0) {
    $min = intval(substr($wt,0,stripos($wt,'m')));
    if ($min > 59) {
        $hr  = intval($min / 60);
        $min = $min % 60;
        $wt  = $hr.':'.$min;
    } else {
        $wt = '0:'.$min;
    }
}
// From here on we have correct time format in variable $wt
$focus->column_fields['worktime']=$wt;

$mode = $_REQUEST['mode'];
$record=$_REQUEST['record'];
if($mode) $focus->mode = $mode;
if($record)$focus->id  = $record;

if($_REQUEST['assigntype'] == 'U') {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_user_id'];
} elseif($_REQUEST['assigntype'] == 'T') {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_group_id'];
}

$focus->save($currentModule);
$return_id = $focus->id;

$search = vtlib_purify($_REQUEST['search_url']);

$parenttab = getParentTab();
if($_REQUEST['return_module'] != '') {
	$return_module = vtlib_purify($_REQUEST['return_module']);
} else {
	$return_module = $currentModule;
}

if($_REQUEST['return_id'] != '') {
	$return_id = vtlib_purify($_REQUEST['return_id']);
}

//crmv@53056
if (isset($_REQUEST['newtc']) && $_REQUEST['am_I_in_popup'] != 'yes') {
    $return_module = $currentModule;
    $return_action = "EditView";  // Create new TC
	$return_id = '';
	$ticketid='&ticket_id='.$focus->column_fields['ticket_id'].'&newtcdone=yes';
}
//crmv@53056e
//crmv@54375
elseif($_REQUEST['return2detail'] == 'yes') {
	$return_module = $currentModule;
	$return_action = 'DetailView';
	$return_id = $focus->id;
}
//crmv@54375e
elseif($_REQUEST['return_action'] != 'DetailView' && $_REQUEST['return_action'] != '') {
	$return_action = vtlib_purify($_REQUEST['return_action']);
} else {
	$return_action = "DetailView";
}

$url = "index.php?action=$return_action&module=$return_module&record=$return_id$ticketid&parenttab=$parenttab&start=".vtlib_purify($_REQUEST['pagenumber']).$search;

$from_module = vtlib_purify($_REQUEST['module']);
if (!empty($from_module)) $url .= "&from_module=$from_module";

header("Location: $url");