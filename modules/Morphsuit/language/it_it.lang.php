<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $enterprise_website;
$mod_strings = array (
	'LBL_MORPHSUIT_ACTIVATION'=>'Attivazione',
	'LBL_MORPHSUIT_TIME_EXPIRED'=>'E` necessario attivare VTE.',
	'LBL_MORPHSUIT_EMAIL'=>'Email',
	'LBL_MORPHSUIT_INSTALLATION_TYPE'=>'Tipo Installazione',
	'LBL_MORPHSUIT_PROD'=>'Produzione',
	'LBL_MORPHSUIT_TEST'=>'Test',
	'LBL_MORPHSUIT_DEMO'=>'Demo',
	'LBL_MORPHSUIT_INSTALLATION_LENGTH'=>'Durata Installazione',
	'LBL_MORPHSUIT_1Y'=>'1 Anno',
	'LBL_MORPHSUIT_6M'=>'6 Mesi',
	'LBL_MORPHSUIT_30D'=>'30 Giorni',
	'LBL_MORPHSUIT_15D'=>'15 Giorni',
	'LBL_MORPHSUIT_1D'=>'1 Giorno',
	'LBL_MORPHSUIT_LATER'=>'Non Adesso',
	'LBL_MORPHSUIT_PREVIOUS'=>'Indietro',
	'LBL_MORPHSUIT_NEXT'=>'Avanti',
	'LBL_MORPHSUIT_ACTIVATE'=>'Attiva',
	'LBL_MORPHSUIT_SEND_REQUEST'=>'Invia Chiave',
	'LBL_MORPHSUIT_KEY'=>'Chiave di attivazione',
	'LBL_MORPHSUIT_DESCRIPTION'=>'Invia a %s la chiave generata. Riceverai il codice di attivazione da incollare sotto.',
	'LBL_MORPHSUIT_KEY_EMPTY'=>'La chiave e` vuota.',
	'LBL_MORPHSUIT_CODE'=>'Codice di attivazione',
	'LBL_MORPHSUIT_CODE_EMPTY'=>'Il codice di attivazione e` vuoto.',
	'LBL_MORPHSUIT_CODE_RIGHT'=>'Il codice di attivazione e` stato inserito correttamente!\nPremere OK per continuare.',
	'LBL_MORPHSUIT_CODE_WRONG'=>'Il codice di attivazione non e` stato inserito correttamente.',
	'LBL_MORPHSUIT_CODE_WRONG_TIME_EXPIRED'=>'Il codice di attivazione e` scaduto.',
	'LBL_MORPHSUIT_ACTIVATION_MAIL_ERROR'=>'In caso di problemi nell`invio automatico e` necessario spedire manualmente la mail con la chiave di attivazione.',
	'LBL_MORPHSUIT_ACTIVATION_MAIL_OK'=>'La mail e` stata inviata al nostro servizio di attivazione, attendere all`indirizzo indicato il Codice di attivazione.',
	'LBL_MORPHSUIT_USER_NUMBER'=>'Numero di utenti',
	'LBL_MORPHSUIT_USER_NUMBER_DESCR'=>'Lasciare il campo vuoto per avere utenti illimitati.',
	'LBL_MORPHSUIT_USER_NUMBER_EXCEEDED'=>'Limite numero utenti attivi superato. Contattare l\'Amministratore per richiedere la nuova licenza.',
	'LICENSE_ID' => 'Licenza numero',
	'LBL_MORPHSUIT_NEW_LICENSE_REGISTRATION'=>'Procedere adesso con la registrazione di una nuova chiave?',
	'LBL_VTE_FREE_OK'=>'Attivazione VTE avvenuta con successo.',
	'LBL_ERROR_VTE_FREE'=>'Errore VTE Free',
	'LBL_ERROR_VTE_FREE_CONNECTION'=>'Impossibile contattare il server. Se desideri inviare una segnalazione scrivi a info@crmvillage.biz.',
	'LBL_ERROR_VTE_FREE_CHECK'=>'Errore chiave. Verificare che il numero utenti non sia oltre il numero consentito.',
	'LBL_FREE' => 'Gratuita',
	'LBL_MORPHSUIT_USER_NUMBER_EXCEEDED_FREE' => 'Limite numero utenti attivi superato per la versione gratuita. Clicca OK per passare alla versione Standard di VTE.',
	'LBL_MORPHSUIT_USER_NUMBER_5' => '5',
	'LBL_MORPHSUIT_USER_NUMBER_10' => '10',
	'LBL_MORPHSUIT_USER_NUMBER_20' => '20',
	'LBL_MORPHSUIT_USER_NUMBER_50' => '50',
	'LBL_MORPHSUIT_USER_NUMBER_100' => '100',
	'LBL_MORPHSUIT_USER_NUMBER_200' => '200',
	'LBL_MORPHSUIT_USER_NUMBER_UNLIMITED' => 'Illimitati',
	'LBL_AVAILABLE_USERS' => 'Utenti disponibili: ',
	'LBL_AVAILABLE_VERSION_TITLE' => 'Aggiornamento VTE',
	'LBL_AVAILABLE_VERSION_TEXT' => 'E` disponibile una nuova versione di VTE.',
	'LBL_AVAILABLE_VERSION_UPDATE' => 'Aggiorna adesso',
	'LBL_ERROR_VTE_REGISTRATION' => 'E` necessario essere iscritti al sito www.crmvillage.biz',
	'LBL_ERROR_SMTP' => 'Non hai ancora impostato un Server di Posta in Uscita (SMTP). E\' indispensabile per la funzione di recupero password. Vuoi farlo ora?',
	'LBL_FUNCTION_BLOCKED'=>'Questa funzione e` disponibile soltanto su VTE BUSINESS ONSITE. Se desideri proseguire ed adeguare la tua posizione puoi contattare il servizio commerciale di CRMVILLAGE all\'indirizzo email %s specificando per quanti utenti desideri attivare il tuo VTE.',
	'LBL_MORPHSUIT_ROLE_NUMBER_EXCEEDED'=>'Hai superato il numero di ruoli previsto per la versione FREE.',
	'LBL_MORPHSUIT_PROFILE_NUMBER_EXCEEDED'=>'Hai superato il numero di profili previsto per la versione FREE.',
	'LBL_MORPHSUIT_PDF_NUMBER_EXCEEDED'=>'Hai superato il pdf previsto per la versione FREE.',
	'LBL_MORPHSUIT_ADV_SHARING_RULE_NUMBER_EXCEEDED'=>'Hai superato il numero di regole di condivisione avanzata previsto per la versione FREE.',
	'LBL_MORPHSUIT_SHARING_RULE_USER_NUMBER_EXCEEDED'=>'Hai superato il numero di regole di condivisione basate sul proprietario previsto per la versione FREE.',
	'LBL_MORPHSUIT_UPDATE'=>'Aggiornamento licenza',
	'LBL_ERROR_VTE_FREE_NOT_ACTIVABLE'=>'Questa versione non ?? pi?? attivabile. Scarica la 4.3 o successiva.',
	'LBL_ZOMBIE_MODE'=>'Continua a usare VTE in sola lettura',
	'LBL_CONNECT_TO_ENABLE_VTE'=>'Verifica la connessione a internet di VTE e rifare il login per sbloccare VTECRM',
	'LBL_MORPHSUIT_SITE_LOGIN'=>'Inserisci username e password di',
	'LBL_MORPHSUIT_SITE_REGISTRATION'=>'Se non sei ancora registrato clicca',
	'LBL_ERROR_VTE_FREE_NOT_ACTIVABLE'=>'Questa versione non ?? pi?? attivabile. Scarica/Aggiorna all\'ultima disponibile.',
	'LBL_OTHER_FREE_VERSION'=>'Ci risulta sia stata installato un altro VTE Free per questo utente del sito. Alcune funzionalit?? saranno quindi ridotte.',
	'LBL_MORPHSUIT_CODE'=>'Incolla qui il Codice di attivazione',
	'LBL_MORPHSUIT_ADMIN_CONFIG'=>"Configura l'utente amministratore",
	'LBL_MORPHSUIT_OVERWRITE_CREDENTIALS'=>'Le credenziali di accesso dell\'utente admin saranno sovrascritte con quelle di '.$enterprise_website[1],
	'LBL_MORPHSUIT_BUSINESS_ACTIVATION'=>'E` necessario attivare una versione Business On Site.',
	'LBL_MORPHSUIT_REGISTER'=>'Registra',
	'LBL_MORPH_NEWSLETTER_LANG'=>'Lingua newsletter',
	'ERR_REENTER_PASSWORDS'=>'Le password non coincidono',
	'LBL_ACTIVATED_USERS'=>'Utenti attivi',
	'LBL_EXPIRATION_DATE'=>'Data di scadenza',
	'LBL_UPDATE_YOUR_LICENSE'=>'Aggiorna la tua licenza',
);
?>