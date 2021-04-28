<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $table_prefix;
$accountid = $_REQUEST['accountid'];
if( $accountid != '' && $accountid != 'undefined'){
	$query .= " and ".$table_prefix."_salesorder.accountid = '".$accountid."' ";
}
?>