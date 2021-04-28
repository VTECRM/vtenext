<?php
global $adb, $table_prefix;

$nlModule = Vtiger_Module::getInstance('Newsletter');
$adb->pquery("update {$table_prefix}_links set linkicon = ? where tabid = ? and linklabel = ?", array('vteicon:send',$nlModule->id,'LBL_SEND_MAIL_BUTTON'));
$adb->pquery("update {$table_prefix}_links set linkicon = ? where tabid = ? and linklabel = ?", array('vteicon:mail_outline',$nlModule->id,'LBL_SEND_TEST_MAIL_BUTTON'));
$adb->pquery("update {$table_prefix}_links set linkicon = ? where tabid = ? and linklabel = ?", array('vteicon:remove_red_eye',$nlModule->id,'LBL_PREVIEW_MAIL_BUTTON'));
$nlModule->addLink('DETAILVIEWBASIC', 'LBL_STOP_NEWSLETTER_BUTTON', "javascript:stopNewsletter(\$RECORD\$);", 'vteicon:cancel');

$trans = array(
	'Newsletter' => array(
		'it_it' => array(
			'LBL_STOP_NEWSLETTER_BUTTON' => 'Interrompi invio',
		),
		'en_us' => array(
			'LBL_STOP_NEWSLETTER_BUTTON' => 'Stop sending',
		),
	),
);
foreach ($trans as $module=>$modlang) {
	foreach ($modlang as $lang=>$translist) {
		foreach ($translist as $label=>$translabel) {
			SDK::setLanguageEntry($module, $lang, $label, $translabel);
		}
	}
}