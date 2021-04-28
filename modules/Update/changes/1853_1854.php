<?php
$VTEP = VTEProperties::getInstance();
$VTEP->setProperty('calendar_tracking.enabled', true);
$VTEP->setProperty('calendar_tracking.detailview_modules', array('Accounts', 'Contacts', 'HelpDesk', 'ProjectTask'));
$VTEP->setProperty('calendar_tracking.turbolift_modules', array('Messages', 'Emails'));

Update::info('Calendar tracking properties have been moved in vteprop.');
Update::info('If you have customizations using old functions or syntax, please review them.');
Update::info('');
