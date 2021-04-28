<?php
SDK::setLanguageEntries('ALERT_ARR', 'ARE_YOU_SURE_INCREMENT_VERSION', array('it_it'=>'Sei sicuro di voler salvare una nuova versione?','en_us'=>'Are you sure you want to save a new version?'));
SDK::setLanguageEntries('ALERT_ARR', 'LBL_OLD_VERSION', array('it_it'=>'Congela','en_us'=>'Freeze'));
SDK::setLanguageEntries('ALERT_ARR', 'LBL_NEW_VERSION', array('it_it'=>'Usa recente','en_us'=>'Use last'));
SDK::setLanguageEntries('ALERT_ARR', 'LBL_INCREMENT_VERSION_ERR_1', array(
	'it_it'=>'Sono stati rilevati dei processi in esecuzione. Vuoi congelare l\'esecuzione alla versione %1 o utilizzare la versione più recente del diagramma?',
	'en_us'=>'Some running processes were detected. Do you want to freeze the execution to version %1 or use the latest version of the diagram?'
));
SDK::setLanguageEntries('ALERT_ARR', 'LBL_INCREMENT_VERSION_ERR_2', array(
	'it_it'=>'Sono stati rilevati dei processi in esecuzione. Vuoi congelare l\'esecuzione alla versione %1 o utilizzare la versione più recente del diagramma?<br>Inoltre sono state rilevate modifiche pendenti nelle seguenti configurazioni:%2Scegliendo CONGELA verranno anche salvate automaticamente tutte queste modifiche pendenti.',
	'en_us'=>'Some running processes were detected. Do you want to freeze the execution to version %1 or use the latest version of the diagram?<br>Furthermore, pending changes were detected in the following configurations:%2Choosing FREEZE will automatically save all these pending changes.'
));
SDK::setLanguageEntries('Settings', 'LBL_INCREMENT_VERSION', array(
	'it_it'=>'Salva versione',
	'en_us'=>'Save version'
));