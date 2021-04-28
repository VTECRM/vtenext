<?php
// crmv@198518
SDK::setLanguageEntries('Settings', 'LBL_PM_ACTION_EMAIL_SELECT_ATTACH_FROM', array('it_it'=>'Allega documenti da','en_us'=>'Attach documents from'));

// crmv@202172
$VTEP = VTEProperties::getInstance();
$VTEP->setProperty('modules.emails.auto_append_servers', array('gmail','office365'));