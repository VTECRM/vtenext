<?php
// crmv@200243
$VTEP = VTEProperties::getInstance();
if ($VTEP->getProperty('modules.messages.messages_cleaned_by_schedule') == '-1 day') {
	$VTEP->setProperty('modules.messages.messages_cleaned_by_schedule', 500);
	$VTEP->setProperty('modules.messages.preserve_search_results_date', '-1 day');
}

// crmv@200330
SDK::setLanguageEntries('Messages', 'Bcc', array('it_it'=>'Ccn','en_us'=>'Bcc'));
SDK::setLanguageEntries('Messages', 'Bcc Name', array('it_it'=>'Ccn','en_us'=>'Bcc'));
SDK::setLanguageEntries('Messages', 'Bcc Full', array('it_it'=>'Ccn','en_us'=>'Bcc'));
SDK::setLanguageEntries('Messages', 'ReplyTo', array('it_it'=>'Rispondi a','en_us'=>'Reply to'));