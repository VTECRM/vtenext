<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $table_prefix;
$projectplanid = $_REQUEST['projectplanid'];
if( $projectplanid != '' && $projectplanid != 'undefined'){
	$query .= " and {$table_prefix}_projecttask.projectid = '$projectplanid'"; //crmv@165062
}
?>