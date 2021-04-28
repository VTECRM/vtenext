<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $enterprise_mode;
$mod_strings = array (
	'LBL_UPDATE'=>'Update',
	'LBL_UPDATE_DESC'=>'Aggiorna la versione',
	'LBL_URL'=>'Indirizzo SVN',
	'LBL_USERNAME'=>'Nome utente',
	'LBL_PASWRD'=>'Password',
	'LBL_SIGN_IN_DETAILS'=>'Dettagli Login',
	'LBL_SIGN_IN_CHANGE'=>'Cambia Login',
	'LBL_CURRENT_VERSION'=>'Build corrente',
	'LBL_MAX_VERSION'=>'Ultima build disponibile',
	'LBL_UPDATE_DETAILS'=>'Dettagli Aggiornamento',
	'LBL_UPDATE_BUTTON'=>'Aggiorna',
	'LBL_UPDATE_TO'=>'Aggiorna a',
	'LBL_SPECIFIC_VERSION'=>'Specifica versione',
	'LBL_SPECIFICIED_VERSION'=>'Versione specificata',
	'LBL_UPDATE_PACK_INVALID'=>"Questo pacchetto di aggiornamento non è applicabile alla tua versione di $enterprise_mode.<br />Contatta CRMVillage.BIZ o il tuo Partner di riferimento per avere la versione corretta.",
	'LBL_POPUP_TITLE' => '<b>La versione {version} di vtenext è disponibile!</b>',
	'LBL_SCHEDULE' => 'Pianifica',
	'LBL_SCHEDULE_UPDATE' => 'Pianifica aggiornamento',
	'LBL_REMIND' => 'Ricordamelo',
	'LBL_REMIND_IN_4_HOURS' => 'Tra 4 ore',
	'LBL_REMIND_TOMORROW' => 'Domani',
	'LBL_REMIND_NEXT_WEEK' => 'La settimana prossima',
	'LBL_IGNORE_UPDATE' => 'Ignora aggiornamento',
	'LBL_WHEN_SCHEDULE_UPDATE' => 'Quando vuoi programmare l\'aggiornamento?',
	'LBL_ALERT_USER_OF_UPDATE' => 'Avvisa gli utenti dell\'aggiornamento',
	'LBL_SEND_THIS_MESSAGE' => 'Invia agli utenti questo messaggio di avviso',
	'LBL_UPDATE_SCHEDULED' => 'Aggiornamento pianificato con successo',
	'LBL_UPDATE_DEFAULT_MESSAGE' => "Gentile utente,
è stato pianificato un aggiornamento di vtenext per il giorno {date} alle {hour}.
Durante l'aggiornamento non sarà possibile utilizzare il sistema.

{cancel_text}",
	'LBL_UPDATE_MESSAGE_OK' => "Gentile {name},
l'aggiornamento di vtenext è stato completato correttamente.
Verifica i file allegati per i log dell'aggiornamento.",
	'LBL_UPDATE_MESSAGE_FAIL_RB' => "Gentile {name},
l'aggiornamento di vtenext non ha avuto successo, ed il sistema è stato ripristinato alla versione precedente.
E' necessario eseguire l'aggiornamento manualmente.
Verifica i file allegati per i log dell'aggiornamento.",
	'LBL_UPDATE_MESSAGE_FAIL' => "Gentile {name},
l'aggiornamento di vtenext non ha avuto successo, e non è stato possibile ripristinare il sistema alla versione precedente.
E' necessario ripristinare manualmente i backup e procedere con l'aggiornamento.
Verifica i file allegati per i log dell'aggiornamento.",
	'LBL_ALERT_CHANGES' => 'Sono state rilevate differenze nei files di vte. Procedere comunque con l\'aggiornamento (potresti perdere delle personalizzazioni)?',
	'LBL_VIEW_FILES_LIST' => 'Vedi lista di files',
	'LBL_MODIFIED_FILES' => 'Files con differenze',
	'LBL_NOTIFICATION_TPL_TEXT' => 'E\' disponibile un aggiornamento di vtenext. Clicca <a href="{url}">qui</a> per i dettagli.',
	'LBL_ALREADY_CHOSEN' => 'Un altro utente ha già pianificato o ignorato l\'aggiornamento',
	'LBL_OS_NOT_SUPPORTED' => 'L\'aggiornamento automatico non è supportato su questo sistema operativo.',
	'LBL_OS_NOT_SUPPORTED_UPDATE' => 'L\'aggiornamento automatico non è supportato su questo sistema operativo. E\' necessario aggiornare manualmente.',
	'LBL_MANUAL_INFO_1' => 'Eseguire un backup dei files e del database di vtenext, vedi <b><a href="%s" target="_blank">qui</a></b> per le istruzioni',
	'LBL_MANUAL_INFO_2' => 'Scaricare il pacchetto di aggiornamento da questo <b><a href="%s">indirizzo</a></b>',
	'LBL_MANUAL_INFO_3' => 'Decomprimere il file scaricato e sovrascrivere con il suo contenuto i file nella cartella <i>%s</i>',
	'LBL_MANUAL_INFO_4' => 'Andare a questa <b><a href="%s">pagina</a></b> e cliccare <i>update</i>',
	'LBL_ALL_USERS' => 'Tutti gli utenti',
	'LBL_UPDATE_DEFAULT_CANCEL_TEXT' => "Se vuoi annullare l'aggiornamento, clicca <b><a href=\"%s\">qui</a></b>.",
	'LBL_CANNOT_CANCEL' => 'Non è più possibile annullare l\'aggiornamento',
	'LBL_CANCEL_UPDATE_TITLE' => 'Annulla aggiornamento',
	'LBL_CANCEL_UPDATE_TEXT' => 'Un aggiornamento di vtenext è pianificato per il giorno {date} alle ore {hour}',
	'LBL_CANCEL_UPDATE_ASK' => 'Vuoi annullarlo?',
	'LBL_CANCEL_UPDATE_INFO' => 'Potrai riprogrammarlo cliccando sulla notifica originale',
	'LBL_CANCEL_BODY' => "Gentile utente,
l'aggiornamento pianificato per il giorno {date} alle {hour} è stato annullato.",
	'LBL_NEED_PHP_70' => 'La versione di PHP è troppo vecchia. La nuova versione di vtenext richiede almeno PHP 7.0',
	'LBL_PHP_OK_WAIT_CRON' => 'PHP è ora aggiornato. Aspetta qualche ora e verrai nuovamente notificato della disponibilità dell\'aggiornamento',
	'LBL_UPDATE_RUNNING_WAIT' => 'Aggiornamento in corso, attendere...',
	'LBL_UPDATE_FINISHED' => 'Aggiornamento completato',
	'LBL_UPDATE_FAILED' => 'Aggiornamento fallito. Verifica l\'errore, e riprova, ripristinando il backup.',
	'LBL_CONTINUE' => 'Prosegui',
	'LBL_DATE_IS_PAST' => 'La data non può essere nel passato',
	'LBL_DATE_TOO_CLOSE' => "La data deve essere almeno 10 minuti da ora",
	// crmv@199352
	'LBL_NO_UPDATES_AVAILABLE' => 'Al momento non ci sono aggiornamenti disponibili.',
	'LBL_NO_UPDATES_CRON' => 'Il controllo degli aggiornamenti non è attivo. Verifica la configurazione dei cron.',
	'LBL_LAST_CHECK' => 'Ultimo controllo: ',
	'LBL_CHECK_NOW' => 'Controlla ora',
	'LBL_CRON_FORCED' => 'Entro i prossimi minuti verrà controllata la disponibilità di aggiornamenti. Verrai notificato in caso ce e siano',
	'LBL_PROCESSING_UPDATE' => 'L\'aggiornamento è in fase di verifica, per favore attendi qualche minuto.',
	// crmv@199352e
);