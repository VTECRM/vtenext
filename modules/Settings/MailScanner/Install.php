<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@104782 - install the needed fields for MailScanner

global $adb, $table_prefix;

require_once('modules/Update/Update.php');

if (isModuleInstalled('HelpDesk')) {
	$HelpDeskInstance = Vtecrm_Module::getInstance('HelpDesk');
	
	// update old labels
	$adb->pquery("UPDATE {$table_prefix}_links SET linklabel = ? WHERE tabid = ? AND linklabel = ?", array('Answer by mail', $HelpDeskInstance->id, 'Rispondi via mail'));
	$adb->pquery("UPDATE {$table_prefix}_links SET linklabel = ? WHERE tabid = ? AND linklabel = ?", array('Answer by mail (info)', $HelpDeskInstance->id, 'Rispondi via mail (info)'));
	
	// update old icon and condition
	$cond = 'checkMailScannerInfoRule:include/utils/crmv_utils.php';
	$adb->pquery("UPDATE {$table_prefix}_links SET linkicon = ?, cond = ? WHERE linktype = ? AND tabid = ? AND linklabel LIKE 'Answer by mail%'",
		array('vteicon:reply',$cond,'DETAILVIEWBASIC',$HelpDeskInstance->id)
	);
	
	// add links
	Vtecrm_Link::addLink($HelpDeskInstance->id, 'DETAILVIEWBASIC', 'Answer by mail', 'javascript:ReplyMailConverter($RECORD$,\'current\');', 'vteicon:reply', 0, $cond);
	Vtecrm_Link::addLink($HelpDeskInstance->id, 'DETAILVIEWBASIC', 'Answer by mail (info)', 'javascript:ReplyMailConverter($RECORD$,\'mailconverter\');', 'vteicon:reply', 0, $cond);
	Vtecrm_Link::addLink($HelpDeskInstance->id, 'HEADERSCRIPT', 'ReplyMailConverter', 'modules/SDK/src/modules/HelpDesk/ReplyMailConverter.js');
	//campo parent_id del modulo helpdesk uitype 68 -> 10
	SDK::addView('HelpDesk', 'modules/SDK/src/modules/HelpDesk/MailConverterInfo.php', 'restrict', 'continue');
	
}

// add blocks and fields
$blocks = array(
	'LBL_MAIL_INFO' => array('module' => 'HelpDesk', 'label' => 'LBL_MAIL_INFO'),
);
Update::create_blocks($blocks);

$fields = array(
	'email_from' 		=> array('module'=>'HelpDesk','block'=>'LBL_MAIL_INFO','name'=>'email_from',		'label'=>'Mail From',	'uitype'=>'13',		'columntype'=>'C(100)','typeofdata'=>'V~O'),
	'email_to' 			=> array('module'=>'HelpDesk','block'=>'LBL_MAIL_INFO','name'=>'email_to',		'label'=>'Mail To',		'uitype'=>'13',		'columntype'=>'C(100)','typeofdata'=>'V~O'),
	'email_cc' 			=> array('module'=>'HelpDesk','block'=>'LBL_MAIL_INFO','name'=>'email_cc',		'label'=>'Mail Cc',		'uitype'=>'1',		'columntype'=>'C(255)','typeofdata'=>'V~O'),
	'email_bcc' 		=> array('module'=>'HelpDesk','block'=>'LBL_MAIL_INFO','name'=>'email_bcc',		'label'=>'Mail Bcc',	'uitype'=>'1',		'columntype'=>'C(255)','typeofdata'=>'V~O'),
	'helpdesk_from_name'	=> array('module'=>'HelpDesk','block'=>'LBL_MAIL_INFO','name'=>'helpdesk_from_name',	'label'=>'HelpDesk From Name','uitype'=>'1','columntype'=>'C(100)','typeofdata'=>'V~O'),
	'helpdesk_from' 	=> array('module'=>'HelpDesk','block'=>'LBL_MAIL_INFO','name'=>'helpdesk_from',	'label'=>'HelpDesk From','uitype'=>'13',	'columntype'=>'C(100)','typeofdata'=>'V~O'),
	'email_date' 		=> array('module'=>'HelpDesk','block'=>'LBL_MAIL_INFO','name'=>'email_date',		'label'=>'Mail Date',	'uitype'=>'70',		'columntype'=>'T','typeofdata'=>'T~O','displaytype'=>'2'),
	'mailscanner_action'	=> array('module'=>'HelpDesk','block'=>'LBL_MAIL_INFO','name'=>'mailscanner_action',	'label'=>'Mail Converter Action','uitype'=>'204','columntype'=>'C(50)','typeofdata'=>'V~O','readonly'=>'100'),
);

Update::create_fields($fields);

$trans = array(
	'HelpDesk' => array(
		'it_it' => array(
			'LBL_MAIL_INFO' => 'Informazioni Mail',
			'Answer by mail' => 'Rispondi via mail',
			'Answer by mail (info)' => 'Rispondi via mail (info)',
			'Mail From' => 'Mittente Mail',
			'Mail To' => 'Destinatario Mail',
			'Mail Cc' => 'Cc Mail',
			'Mail Bcc' => 'Bcc Mail',
			'HelpDesk From Name' => 'Nome Mittente VTE',
			'HelpDesk From' => 'Mail Mittente VTE',
			'Mail Date' => 'Data Mail',
			'Mail Converter Action' => 'Regola Mail Converter',
		),
		'en_us' => array(
			'LBL_MAIL_INFO' => 'Mail Information',
			'Answer by mail' => 'Answer by mail',
			'Answer by mail (info)' => 'Answer by mail (info)',
			'Mail From' => 'Mail From',
			'Mail To' => 'Mail To',
			'Mail Cc' => 'Mail Cc',
			'Mail Bcc' => 'Mail Bcc',
			'HelpDesk From Name' => 'VTE From Name',
			'HelpDesk From' => 'VTE From Mail',
			'Mail Date' => 'Mail Date',
			'Mail Converter Action' => 'Mail Converter Action',
		),
		'pt_br' => array(
			'LBL_MAIL_INFO' => 'Informação Mail',
			'Answer by mail' => 'Responder por mail',
			'Answer by mail (info)' => 'Responder por mail (info)',
			'Mail From' => 'Remetente Mail',
			'Mail To' => 'Destinatário Mail',
			'Mail Cc' => 'Cc Mail',
			'Mail Bcc' => 'Bcc Mail',
			'HelpDesk From Name' => 'Nome Remetente VTE',
			'HelpDesk From' => 'Mail Remetente VTE',
			'Mail Date' => 'Data Mail',
			'Mail Converter Action' => 'Mail Converter Action',
		),
	),
	'Settings' => array(
		'it_it' => array(
			'LBL_DO_NOTHING' => 'Non fare nulla',
			'LBL_DO_NOT_IMPORT_ANYMORE' => 'Non importare più',
		),
		'en_us' => array(
			'LBL_DO_NOTHING' => 'Do nothing',
			'LBL_DO_NOT_IMPORT_ANYMORE' => 'Do not import anymore',
		),
		'pt_br' => array(
			'LBL_DO_NOTHING' => 'Não fazer nada',
			'LBL_DO_NOT_IMPORT_ANYMORE' => 'Não importar mais',
		),
	)
);
$languages = vtlib_getToggleLanguageInfo();
foreach ($trans as $module=>$modlang) {
	foreach ($modlang as $lang=>$translist) {
		if (array_key_exists($lang,$languages)) {
			foreach ($translist as $label=>$translabel) {
				SDK::setLanguageEntry($module, $lang, $label, $translabel);
			}
			if ($module == 'ALERT_ARR') {
				$recalculateJsLanguage[$lang] = $lang;
			}
		}
	}
}

if (!SDK::isUitype(204)) {
	SDK::setUitype(204,'modules/SDK/src/204/204.php','modules/SDK/src/204/204.tpl','');
}