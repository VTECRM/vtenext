<?php
global $adb, $table_prefix;

// crmv@197575
$adb->query("DELETE FROM {$table_prefix}_links WHERE linklabel = 'OpenNewsletterWizard' AND linktype = 'LISTVIEWBASIC' ");

SDK::setLanguageEntries('Campaigns', 'OpenNewsletterWizard', array('it_it'=>'Wizard','en_us'=>'Wizard') );
SDK::setLanguageEntries('Newsletter', 'OpenNewsletterWizard', array('it_it'=>'Wizard','en_us'=>'Wizard') );

$VTEP = VTEProperties::getInstance();
$VTEP->setProperty('layout.template_editor', 'grapesjs');   //ckeditor