<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@80155 */

global $adb, $table_prefix, $theme, $currentModule, $current_user, $default_charset; // crmv@119012

$record = intval($_REQUEST['record']);

$focus = CRMEntity::getInstance($currentModule);
$focus->id = $record;
$focus->retrieve_entity_info($record, $currentModule);

$templatename = '';
if (!in_array($focus->column_fields['templateemailid'], array('', 0))) {
	$edit_perm = true;
	$result = $adb->pquery("select * from {$table_prefix}_emailtemplates where templateid=?", array($focus->column_fields['templateemailid']));
	$templatename = $adb->query_result($result, 0, 'templatename');
	$templatename = htmlspecialchars($templatename, ENT_QUOTES, $default_charset); // crmv@119012
	
	$res = $adb->query("select * from {$table_prefix}_field where fieldname = 'bu_mc'");
	if ($res && $adb->num_rows($res) > 0) {
		$saved_bu_mc = explode(' |##| ', $adb->query_result($result, 0, 'bu_mc'));
		$bu_mc = explode(' |##| ', $current_user->column_fields['bu_mc']);
		$edit_perm = false;
		if (!empty($bu_mc)) {
			foreach ($bu_mc as $b) {
				if (in_array($b, $saved_bu_mc)) {
					$edit_perm = true;
					break;
				}
			}
		}
	}
} else {
	$edit_perm = false;
}

$smarty = new VteSmarty();
$smarty->assign('RECORD', $record);
$smarty->assign('TEMPLATE_NAME', $templatename);
$smarty->assign('EDIT_PERMISSION', $edit_perm);
$smarty->display("modules/Newsletter/widgets/TemplateEmailWidget.tpl");