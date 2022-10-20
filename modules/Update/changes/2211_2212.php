<?php
// crmv@261010_1

// change sender name of system emails
$configInc = file_get_contents('config.inc.php');
if (empty($configInc)) {
	Update::info("Unable to get config.inc.php contents, please modify it manually.");
} else {
	// backup it (only if it doesn't exist)
	$newConfigInc = 'config.inc.2211.php';
	if (!file_exists($newConfigInc)) {
		file_put_contents($newConfigInc, $configInc);
	}
	if (is_writable('config.inc.php')) {
    	$configInc = preg_replace('/^\$HELPDESK_SUPPORT_NAME.*$/m', "\$HELPDESK_SUPPORT_NAME = \$enterprise_mode.' Notification System';", $configInc);
    	$configInc = preg_replace('/^\$REMINDER_NAME.*$/m', "\$REMINDER_NAME = \$enterprise_mode.' Notification System';", $configInc);
	    file_put_contents('config.inc.php', $configInc);
	} else {
	    Update::info("Unable to update config.inc.php, please modify it manually.");
	}
}

global $enterprise_mode;
SDK::setLanguageEntries('Users', 'LBL_RECOVER_EMAIL_BODY2', array(
    'it_it'=>'per proseguire ed immettere la nuova password.<br />Hai a disposizione 15 minuti per terminare questo processo di recupero password. Passati i 15 minuti dovrai ripetere la procedura dall\'inizio cliccando nuovamente il link "Hai dimenticato la password?" nella pagina di login.',
    'en_us'=>'to continue and enter the new password.<br />You have 15 minutes to finish the password recovery process. Passed the 15 minutes you will have to start by clicking again on the link "Forgot your password?" at the login page.'
));
SDK::setLanguageEntries('Users', 'LBL_RECOVERY_EMAIL_PASSWORD_SAVED', array(
    'it_it'=>'La tua password è stata cambiata con successo dall\'indirizzo ip %s. Premere %s per il login.',
    'en_us'=>'Your password was successfully changed from the ip %s. Click %s for login.'
));
SDK::setLanguageEntries('Users', 'LBL_RECOVER_EMAIL_SUBJECT', array(
    'it_it'=>$enterprise_mode.' Recupero password',
    'en_us'=>$enterprise_mode.' Password recovery'
));
SDK::setLanguageEntries('Users', 'LBL_RECOVERY_SYSTEM4', array(
    'it_it'=>'altrimenti procedi per inserire la nuova password.',
    'en_us'=>'or proceed to enter the new password.'
));
SDK::setLanguageEntries('Users', 'LBL_RECOVERY_TOO_MANY_ATTEMPTS', array(
    'it_it'=>'Sei stato temporaneamente bloccato per troppi tentativi di recupero password. Riprova tra 1 ora.',
    'en_us'=>'You have been temporarily blocked for too many password recovery attempts. Try again in 1 hour.'
));
