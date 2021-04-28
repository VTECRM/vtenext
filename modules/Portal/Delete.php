<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb, $table_prefix;

if(!isset($_REQUEST['record']))
	die($mod_strings['ERR_DELETE_RECORD']);

$del_query = 'DELETE FROM '.$table_prefix.'_portal WHERE portalid=?';
$adb->pquery($del_query, array($_REQUEST['record']));

 //code added for returning back to the current view after delete from list view
header("Location: index.php?action=PortalAjax&module=Portal&file=ListView&mode=ajax&datamode=manage");