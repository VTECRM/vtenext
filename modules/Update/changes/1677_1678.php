<?php

// crmv@160733

// fix createdtime column
if ($adb->isMysql()) {
	// this seems the only way to change the column
	$adb->query("ALTER TABLE {$table_prefix}_ticketcomments CHANGE createdtime createdtime TIMESTAMP NULL");
}


$adb->addColumnToTable($table_prefix.'_ticketcomments', 'conf_status', 'I(5)'); // 0 = none, 1 = requested, 2 = provided, 3 = confidential data
$adb->addColumnToTable($table_prefix.'_ticketcomments', 'conf_password', 'C(255)');
$adb->addColumnToTable($table_prefix.'_ticketcomments', 'conf_data', 'X');


$trans = array(
	'HelpDesk' => array(
		'it_it' => array(
			'LBL_CONFIDENTIAL_INFO' => 'Informazioni confidenziali',
			'LBL_SAVE_CONFIDENTIAL_COMMENT' => 'Richiedi informazioni confidenziali',
			'LBL_CONFIDENTIAL_REQUEST' => 'Richiesta dell\'utente',
			'LBL_CONFIDENTIAL_RESPONSE' => 'Risposta confidenziale',
			'LBL_REQUEST_CONFIDENTIAL_INFO_DESC' => 'Scegli una password che verrà usata per cifrare i dati confidenziali', 
			'LBL_REQUEST_CONFIDENTIAL_INFO_MORE' => 'Puoi anche inserire dei commenti (normale o cifrato) per specificare i dati richiesti.',
			'LBL_CONFIDENTIAL_INFO_REQUEST_TEXT' => 'Per inserire le informazioni richieste, clicca {HERELINK}',
			'LBL_CONFIDENTIAL_INFO_REQUEST_ETEXT' => 'Sono state richieste informazioni confidenziali, per inserirle, clicca {HERELINK}',
			'LBL_CONFIDENTIAL_INFO_REPLY_TEXT' => 'Sono state fornite le informazioni confidenziali richieste. Per visualizzarle, clicca {HERELINK}',
			'LBL_CONFIDENTIAL_INFO_SEE_DESC' => 'Immettere la password scelta in precedenza e cliccare su Mostra Informazioni',
			'LBL_CONFIDENTIAL_INFO_SHOW_BTN' => 'Mostra informazioni',
			'LBL_ADD_ENCRYPTED_COMMENT' => 'Aggiungi commento cifrato',
			'LBL_WONT_BE_ENCRYPTED' => 'Non verrà cifrato',
			'Comment' => 'Commento',
		),
		'en_us' => array(
			'LBL_CONFIDENTIAL_INFO' => 'Confidential information',
			'LBL_SAVE_CONFIDENTIAL_COMMENT' => 'Request confidential information',
			'LBL_CONFIDENTIAL_REQUEST' => 'User request',
			'LBL_CONFIDENTIAL_RESPONSE' => 'Confidential response',
			'LBL_REQUEST_CONFIDENTIAL_INFO_DESC' => 'Choose a password to encrypt confidential information',
			'LBL_REQUEST_CONFIDENTIAL_INFO_MORE' => 'You can also write a additional comments (standard or encrypted) to explain the needed information.',
			'LBL_CONFIDENTIAL_INFO_REQUEST_TEXT' => 'To provide the requested information, click {HERELINK}',
			'LBL_CONFIDENTIAL_INFO_REQUEST_ETEXT' => 'Confidential information has been requested. To provide it, click {HERELINK}',
			'LBL_CONFIDENTIAL_INFO_REPLY_TEXT' => 'The requested confidential information have been provided. To see them click {HERELINK}',
			'LBL_CONFIDENTIAL_INFO_SEE_DESC' => 'Type the password chosen before and click on Show Information',
			'LBL_CONFIDENTIAL_INFO_SHOW_BTN' => 'Show information',
			'LBL_ADD_ENCRYPTED_COMMENT' => 'Add encrypted comment',
			'LBL_WONT_BE_ENCRYPTED' => 'Won\'t be encrypted',
		),
	),
	'ALERT_ARR' => array(
		'it_it' => array(
			'LBL_TYPE_A_COMMENT' => 'Inserisci un commento prima',
			'LBL_CONFIDENTIAL_INFO_ALREADY_PROVIDED' => 'Le informazioni richieste sono già state fornite',
			'LBL_OPERATION_NOT_SUPPORTED_EDITVIEW' => 'Questa operazione non è supportata in modalità EditView',
		),
		'en_us' => array(
			'LBL_TYPE_A_COMMENT' => 'Please type a comment',
			'LBL_CONFIDENTIAL_INFO_ALREADY_PROVIDED' => 'The requested information have already been provided',
			'LBL_OPERATION_NOT_SUPPORTED_EDITVIEW' => 'This operation is not supported in EdiView mode',
		),
	),
);

$languages = vtlib_getToggleLanguageInfo();
foreach ($trans as $module => $modlang) {
	foreach ($modlang as $lang => $translist) {
		if (array_key_exists($lang, $languages)) {
			foreach ($translist as $label => $translabel) {
				SDK::setLanguageEntry($module, $lang, $label, $translabel);
			}
		}
	}
}
