<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $adb,$log,$current_user,$table_prefix;

$cfmid=vtlib_purify($_REQUEST['cfmid']);

$deleteSql="DELETE FROM ".$table_prefix."_convertleadmapping WHERE cfmid=?";
$result=$adb->pquery($deleteSql,array($cfmid));

$listURL='index.php?module=Settings&action=LeadCustomFieldMapping';	//crmv@29463
header(sprintf("Location: %s",$listURL));

?>