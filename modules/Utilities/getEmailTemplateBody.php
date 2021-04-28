<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@153002 */

global $current_user, $theme;

$templateid = intval($_REQUEST['templateid']);
$module = $_REQUEST['formodule'];
$record = $_REQUEST['forrecord'];

if ($templateid > 0) {
	$body = '';
	
	if (is_admin($current_user) || isPermitted($module,'DetailView', $record) == 'yes') {

		$res = $adb->pquery("select body from ".$table_prefix."_emailtemplates where templateid=?", array($templateid));
		if ($res && $adb->num_rows($res) > 0) {
			$body = $adb->query_result_no_html($res, 0, 'body');
		}
		
	}
	
	echo "<html class=\"popup-newsletter-preview\"><head><link rel=\"stylesheet\" type=\"text/css\" href=\"themes/{$theme}/style.css\" /><base target=\"_blank\"></html>\n<body>\n";
	echo "<div class=\"template-preview-body\">";
	echo $body."\n";
	echo "</div>";
	echo "</body></html>";
	die();
}