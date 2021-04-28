<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb, $table_prefix;
$idlist = $_POST['idlist'];
$returnmodule=$_REQUEST['return_module'];
$returnaction=$_REQUEST['return_action'];
//split the string and store in an array
$storearray = explode(";",$idlist);
foreach($storearray as $id)
{
        $sql="delete from ".$table_prefix."_sharedcalendar where sharedid=?";
        $result = $adb->pquery($sql, array($id));
}
header("Location:index.php?module=".$returnmodule."&action=".$returnaction);
?>