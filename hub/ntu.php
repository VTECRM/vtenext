<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@191669 */

require_once('../config.inc.php');
chdir($root_directory);
require_once('include/utils/utils.php');
global $adb;
global $table_prefix;
$crmid = $_REQUEST["c"];
$newsletterid = $_REQUEST["n"];
if ($crmid && $newsletterid) {
	$result = $adb->pquery('select first_view,num_views from tbl_s_newsletter_queue where newsletterid = ? and crmid = ?',array($newsletterid,$crmid));
	if ($result && $adb->num_rows($result)>0) {
		$first_view = $adb->query_result($result,0,'first_view');
		$num_views = $adb->query_result($result,0,'num_views');
		if (strtotime($first_view) == '' || $first_view == '0000-00-00 00:00:00') { //crmv@59488 - refer to http://stackoverflow.com/questions/17805751/strtotime0000-00-00-0000-return-negative-value
			$adb->pquery('update tbl_s_newsletter_queue set first_view = ? where newsletterid = ? and crmid = ?',array($adb->formatDate(date('Y-m-d H:i:s'),true),$newsletterid,$crmid));
		}
		$adb->pquery('update tbl_s_newsletter_queue set last_view = ? where newsletterid = ? and crmid = ?',array($adb->formatDate(date('Y-m-d H:i:s'),true),$newsletterid,$crmid));
		$adb->pquery('update tbl_s_newsletter_queue set num_views = ? where newsletterid = ? and crmid = ?',array(($num_views+1),$newsletterid,$crmid));
	}
	// crmv@207152 - removed obsolete code
}
header("Content-Type: image/png");
print base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAABGdBTUEAALGPC/xhBQAAAAZQTFRF////AAAAVcLTfgAAAAF0Uk5TAEDm2GYAAAABYktHRACIBR1IAAAACXBIWXMAAAsSAAALEgHS3X78AAAAB3RJTUUH0gQCEx05cqKA8gAAAApJREFUeJxjYAAAAAIAAUivpHEAAAAASUVORK5CYII=');