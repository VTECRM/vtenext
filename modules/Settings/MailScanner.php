<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

$mode = $_REQUEST['mode'];

if($mode == 'Ajax' && !empty($_REQUEST['xmode'])) {
	$mode = $_REQUEST['xmode'];
}

/** Based on the mode include the MailScanner file. */
if($mode == 'scannow') {
	include_once('cron/modules/MailScanner/MailScanner.service.php');	//crmv@50401
} else if($mode == 'edit') {
	include('modules/Settings/MailScanner/MailScannerEdit.php');
} else if($mode == 'save') {
	include('modules/Settings/MailScanner/MailScannerSave.php');
} else if($mode == 'remove') {
	include('modules/Settings/MailScanner/MailScannerRemove.php');
} else if($mode == 'rule') {
	include('modules/Settings/MailScanner/MailScannerRule.php');
} else if($mode == 'ruleedit') {
	include('modules/Settings/MailScanner/MailScannerRuleEdit.php');
} else if($mode == 'rulesave') {
	include('modules/Settings/MailScanner/MailScannerRuleSave.php');
} else if($mode == 'rulemove_up' || $mode == 'rulemove_down') {
	include('modules/Settings/MailScanner/MailScannerRuleMove.php');
} else if($mode == 'ruledelete') {
	include('modules/Settings/MailScanner/MailScannerRuleDelete.php');
} else if($mode == 'folder') {
	include('modules/Settings/MailScanner/MailScannerFolder.php');
} else if($mode == 'foldersave') {
	include('modules/Settings/MailScanner/MailScannerFolderSave.php');
} else if($mode == 'folderupdate') {
	include('modules/Settings/MailScanner/MailScannerFolderUpdate.php');
} else {
	include('modules/Settings/MailScanner/MailScannerInfo.php');
}
?>