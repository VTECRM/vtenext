<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@191067 */

global $adb, $table_prefix;
$local_log =& LoggerManager::getLogger('Transitions');
$ajaxaction = $_REQUEST["ajax"];
if($ajaxaction == "true")
{
    $module = $_REQUEST['modulename'];
    $roleid = $_REQUEST['roleid'];
    $field = $_REQUEST['field'];
    $status = $_REQUEST['status'];
    $query = "delete from tbl_s_transitions where module = ? and field = ? and roleid = ?";
    $params = Array($module, $field, $roleid);
    $adb->pquery($query,$params);

    $query2 = "delete from tbl_s_transitions_init_fields where module = ? and field = ? and roleid = ? and initial_value = ?";
    $params2 = Array($module, $field, $roleid, $status);
    $adb->pquery($query2,$params2);
    
}
die();