<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

global $current_user;
global $adb, $log;
global $rstart;
//Added to fix 4600
global $table_prefix;

$idlist = $_REQUEST['idlist'];
$leadstatusval = $_REQUEST['leadval'];
$viewid = vtlib_purify($_REQUEST['viewname']);
$return_module = vtlib_purify($_REQUEST['return_module']);
$return_action = vtlib_purify($_REQUEST['return_action']);

$url = getBasic_Advance_SearchURL();

if (isset($_REQUEST['start']) && $_REQUEST['start'] != '') {
    $rstart = "&start=" . vtlib_purify($_REQUEST['start']);
}

$storearray = explode(";", trim($idlist, ';'));

$ids_list = array();

$date_var = date('Y-m-d H:i:s');

if (isset($_REQUEST['owner_id']) && $_REQUEST['owner_id'] != '') {
    foreach ($storearray as $id) {
        if (isPermitted($return_module, 'EditView', $id) == 'yes') {
            $idval = $_REQUEST['owner_id'];
            //Inserting changed owner information to salesmanactivityrel table
            if ($return_module == "Calendar") {
                $del_act = "delete from " . $table_prefix . "_salesmanactivityrel where smid=(select smownerid from " . $table_prefix . "_crmentity where crmid=?) and activityid=?";
                $adb->pquery($del_act, array($id, $id));
                if ($_REQUEST['owner_type'] == 'User') {
                    $count_r = $adb->pquery("select * from " . $table_prefix . "_salesmanactivityrel where smid=? and activityid=?", array($idval, $id));
                    if ($adb->num_rows($count_r) == 0) {
                        $insert = "insert into " . $table_prefix . "_salesmanactivityrel values(?,?)";
                        $result = $adb->pquery($insert, array($idval, $id));
                    }
                }
            }
            //Now we have to update the smownerid
            $sql = "update " . $table_prefix . "_crmentity set modifiedby=?, smownerid=?, modifiedtime=? where crmid=?";
            $result = $adb->pquery($sql, array($current_user->id, $idval, $adb->formatDate($date_var, true), $id));
        } else {
            $ids_list[] = $id;
        }
    }
} elseif (isset($_REQUEST['leadval']) && $_REQUEST['leadval'] != '') {

    foreach ($storearray as $id) {
        if (isPermitted($return_module, 'EditView', $id) == 'yes') {
            if ($id != '') {
                $sql = "update " . $table_prefix . "_leaddetails set leadstatus=? where leadid=?";
                $result = $adb->pquery($sql, array($leadstatusval, $id));
                $query = "update " . $table_prefix . "_crmentity set modifiedby=?, modifiedtime=? where crmid=?";
                $result1 = $adb->pquery($query, array($current_user->id, $adb->formatDate($date_var, true), $id));
            }
        } else {
            $ids_list[] = $id;
        }

    }
}
if (count($ids_list) > 0) {
    $ret_owner = getEntityName($return_module, $ids_list);
    $errormsg = implode(',', $ret_owner);
} else {
    $errormsg = '';
}
if ($return_action == 'ActivityAjax') {
    $view = vtlib_purify($_REQUEST['view']);
    $day = vtlib_purify($_REQUEST['day']);
    $month = vtlib_purify($_REQUEST['month']);
    $year = vtlib_purify($_REQUEST['year']);
    $type = vtlib_purify($_REQUEST['type']);
    $viewOption = vtlib_purify($_REQUEST['viewOption']);
    $subtab = vtlib_purify($_REQUEST['subtab']);

    header("Location: index.php?module=$return_module&action=" . $return_action . "&type=" . $type . $rstart . "&view=" . $view . "&day=" . $day . "&month=" . $month . "&year=" . $year . "&viewOption=" . $viewOption . "&subtab=" . $subtab . $url);
} else {
    header("Location: index.php?module=$return_module&action=" . $return_module . "Ajax&file=ListView&ajax=changestate" . $rstart . "&viewname=" . $viewid . "&errormsg=" . $errormsg . $url);
}

