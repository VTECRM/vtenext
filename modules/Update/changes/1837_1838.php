<?php
SDK::setLanguageEntries('Settings', 'LBL_PM_ACTION_ModNotification', array('it_it'=>'Invia notifica','en_us'=>'Send notification'));
SDK::setLanguageEntries('ModNotifications', 'LBL_EMAIL_INFORMATION', array('it_it'=>'Informazioni mail','en_us'=>'Mail information'));
SDK::setLanguageEntries('ModNotifications', 'LBL_EMAIL_INFORMATION_NOTE', array('it_it'=>'la mail viene inviata agli utenti che hanno scelto di essere notificati via Email, puoi indicare qui i parametri di invio','en_us'=>'la mail viene inviata agli utenti che hanno scelto di essere notificati via Email'));

require_once('modules/ModNotifications/ModNotifications.php');
$focusModNotifications = ModNotifications::getInstance();
$focusModNotifications->addNotificationType('Generic','',0);