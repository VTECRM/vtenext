<?php
/* the revision of the beast */

$UIUtils = UserInfoUtils::getInstance();
$UIUtils->dropHistoricizeVersionTables(); //crmv@162219
$UIUtils->initSystemVersions();

SDK::setLanguageEntries('ALERT_ARR', 'LBL_OLD_VERSION', array('it_it'=>'In uso','en_us'=>'Current'));
SDK::setLanguageEntries('ALERT_ARR', 'LBL_INCREMENT_VERSION_ERR_1', array(
	'it_it'=>'Sono stati rilevati dei processi in esecuzione. Vuoi che terminino con la configurazione in uso o con la nuova?',
	'en_us'=>'Some running processes were detected. Do you want it to end with the current configuration or the new one?'
));
SDK::setLanguageEntries('ALERT_ARR', 'LBL_INCREMENT_VERSION_ERR_2', array(
	'it_it'=>'Sono stati rilevati dei processi in esecuzione. Vuoi che terminino con la configurazione in uso o con la nuova? Inoltre sono state rilevate modifiche pendenti nelle seguenti configurazioni:%Scegliendo IN USO verranno salvate automaticamente tutte le modifiche pendenti.',
	'en_us'=>'Some running processes were detected. Do you want it to end with the current configuration or the new one? Furthermore, pending changes were detected in the following configurations:%Choosing CURRENT will automatically save all pending changes.'
));