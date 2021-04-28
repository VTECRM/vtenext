<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

if (!function_exists('getModuleList201')) {
	function getModuleList201() {
		global $adb,$table_prefix;
		$query = "select name from ".$table_prefix."_tab where presence = 0 and name not in ('Emails','Events','Fax') and (isentitytype = 1 or name in ('Home','Rss','Reports','RecycleBin')) order by name";//crmv@208472
		return $adb->query($query);
	}
}