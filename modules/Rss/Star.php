<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb,$table_prefix;

if(isset($_REQUEST['record'])) {
	$recordid = intval($_REQUEST['record']);
	if(!isset($_REQUEST['starred'])) $starred = 0;
	$starred = $_REQUEST['starred'];
	
	$sSQL = "update ".$table_prefix."_rss set starred=? where ".$table_prefix."_rssid=?";
	$result = $adb->pquery($sSQL, array($starred, $recordid));
}

header("Location: index.php?module=Rss&action=index");
