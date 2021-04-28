<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@33448 crmv@55708 crmv@62394 */

global $adb, $table_prefix;

if(!Vtecrm_Utils::CheckTable($table_prefix.'_cal_tracker')) {
	$schema = '<?xml version="1.0"?>
		<schema version="0.3">
		  <table name="'.$table_prefix.'_cal_tracker">
		  <opt platform="mysql">ENGINE=InnoDB</opt>
		    <field name="userid" type="I" size="19">
		      <KEY/>
		    </field>
		    <field name="record" type="I" size="19">
		      <KEY/>
		    </field>
		    <field name="id" type="I" size="19"/>
		    <index name="CalTrackerIndex1">
		      <col>userid</col>
		    </index>
		    <index name="CalTrackerIndex2">
		      <col>record</col>
		    </index>
		    <index name="CalTrackerIndex3">
		      <col>id</col>
		    </index>
		    <index name="CalTrackerIndex4">
		      <col>userid</col>
		      <col>record</col>
		      <col>id</col>
		    </index>
		  </table>
		</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}
if(!Vtecrm_Utils::CheckTable($table_prefix.'_cal_tracker_log')) {
	$schema = '<?xml version="1.0"?>
		<schema version="0.3">
		  <table name="'.$table_prefix.'_cal_tracker_log">
		  <opt platform="mysql">ENGINE=InnoDB</opt>
		    <field name="id" type="I" size="19">
		      <KEY/>
		    </field>
		    <field name="userid" type="I" size="19"/>
		    <field name="record" type="I" size="19"/>
		    <field name="status" type="C" size="50"/>
		    <field name="date" type="DT"/>
		    <index name="CalTrackerLogIndex1">
		      <col>userid</col>
		    </index>
		    <index name="CalTrackerLogIndex2">
		      <col>record</col>
		    </index>
		  </table>
		</schema>';
	$schema_obj = new adoSchema($adb->database);
	$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
}

$sdkkInstance = Vtecrm_Module::getInstance('SDK');
Vtecrm_Link::addLink($sdkkInstance->id, 'HEADERSCRIPT', 'CalendarTrackingScript', 'modules/SDK/src/CalendarTracking/CalendarTracking.js');

SDK::setMenuButton('fixed','LBL_TRACK_MANAGER',"openPopup('index.php?module=SDK&action=SDKAjax&file=src/CalendarTracking/TrackerManager','','','auto','60','70','','nospinner');",'timer', '', '', 'isCalendarTrackingEnabled:modules/SDK/src/CalendarTracking/CalendarTrackingUtils.php'); // crmv@143804

$res = $adb->pquery("SELECT activitytypeid FROM {$table_prefix}_activitytype WHERE activitytype = ?", array("Tracked"));
if ($res && $adb->num_rows($res) == 0) {
	$fieldInstance = Vtecrm_Field::getInstance('activitytype',Vtecrm_Module::getInstance('Calendar'));
	$fieldInstance->setPicklistValues(array('Tracked'));
	
	$adb->pquery("UPDATE {$table_prefix}_activitytype SET presence = 0 WHERE activitytype= ?", array('Tracked')); // crmv@167704
}


// ------ LANGUAGES ------

$trans = array(
	'APP_STRINGS' => array(
		'it_it' => array(
			'LBL_TRACKER_MODULE' => 'Tracciamento Attivit�',
			'LBL_TRACK_NAME' => 'Tracciato',
			'LBL_DO_TRACK' => 'Traccia',
			'LBL_DO_TRACK_AND_TICKET' => 'Traccia e Ticket',
			'LBL_DO_TRACK_AND' => 'Traccia e ',
			'LBL_TRACK_AND_COMMENT' => 'Traccia e commenta',
			'LBL_TRACK_MANAGER' => 'Tracciamento',
			'LBL_TRACK_MANAGER_TITLE' => 'Tracciamenti attivi o in pausa',
			'LBL_CALENDAR_TRACKING' => 'Tracciamento',
			'LBL_TRACK_START_FOR' => 'Inizia tracciamento per questo ',
			'LBL_TRACK_PAUSE_FOR' => 'Metti in pausa il tracciamento per questo ',
			'LBL_TRACK_STOP_FOR' => 'Ferma tracciamento per questo ',
			'LBL_START' => 'Inizia',
			'LBL_PAUSE' => 'Pausa',
			'LBL_ABANDON' => 'Abbandona',
			'LBL_EJECT_TRACKING' => 'Rimuovi dalla lista',
			'LBL_TRACKING_NO_ENTRIES' => 'Nessun tracciamento attivo o in pausa',
			'LBL_TRACKING_MSG_START' => 'Gentile Cliente, la segnalazione � stata presa in carico dal reparto competente. La terremo aggiornata sullo stato di avanzamento.',
			'LBL_TRACKING_MSG_PAUSE' => 'Gentile Cliente, la risoluzione della sua segnalazione richiede pi� tempo del previsto, proseguiremo con l\'indagine e la terremo aggiornata sullo stato di avanzamento.',
			'LBL_TRACKING_MSG_STOP' => 'Gentile Cliente, abbiamo terminato l\'indagine relativa alla sua segnalazione, la invitiamo a verificare la risoluzione del problema.',
			'LBL_TRACKING_ALREADY_RUNNING' => 'Gi� in esecuzione per',
			'LBL_TRACKING_ALREADY_RUNNING_BY_USER' => 'Attualmente tracciato anche da',
			'Tracking' => 'Tracciato',
		),
		'en_us' => array(
			'LBL_TRACKER_MODULE' => 'Activity Tracker',
			'LBL_TRACK_NAME' => 'Tracking',
			'LBL_DO_TRACK' => 'Track',
			'LBL_DO_TRACK_AND_TICKET' => 'Track and Ticket',
			'LBL_DO_TRACK_AND' => 'Track and ',
			'LBL_TRACK_AND_COMMENT' => 'Track and comment',
			'LBL_TRACK_MANAGER' => 'Tracking',
			'LBL_TRACK_MANAGER_TITLE' => 'Active or paused trackings',
			'LBL_CALENDAR_TRACKING' => 'Tracking',
			'LBL_TRACK_START_FOR' => 'Start tracking for this ',
			'LBL_TRACK_PAUSE_FOR' => 'Pause tracking for this ',
			'LBL_TRACK_STOP_FOR' => 'Stop tracking for this ',
			'LBL_START' => 'Start',
			'LBL_PAUSE' => 'Pause',
			'LBL_ABANDON' => 'Abandon',
			'LBL_EJECT_TRACKING' => 'Eject from list',
			'LBL_TRACKING_NO_ENTRIES' => 'No active or paused trackings',
			'LBL_TRACKING_MSG_START' => 'Dear Customer, the issue has been taken care of by the involved department. We will inform you about the progress.',
			'LBL_TRACKING_MSG_PAUSE' => 'Dear Customer, the issue requires more time than expected, we will continue and we\'ll inform you about the progress.',
			'LBL_TRACKING_MSG_STOP' => 'Dear Customer, we finished the resolution of your issue, please verify that the problem is now solved.',
			'LBL_TRACKING_ALREADY_RUNNING' => 'Already running for',
			'LBL_TRACKING_ALREADY_RUNNING_BY_USER' => 'Currently also traced by',
			'Tracking' => 'Tracking',
		),
		'de_de' => array(
			'LBL_TRACKER_MODULE' => 'Activity Tracker',
			'LBL_TRACK_NAME' => 'Verfolgung',
			'LBL_DO_TRACK' => 'Spur',
			'LBL_DO_TRACK_AND_TICKET' => 'Spur und Ticket',
			'LBL_DO_TRACK_AND' => 'Spur und ',
			'LBL_TRACK_AND_COMMENT' => 'Spur und Kommentar',
			'LBL_TRACK_MANAGER' => 'Verfolgung',
			'LBL_TRACK_MANAGER_TITLE' => 'Aktive oder angehalten trackings',
			'LBL_CALENDAR_TRACKING' => 'Verfolgung',
			'LBL_TRACK_START_FOR' => 'Start Verfolgung daf�r ',
			'LBL_TRACK_PAUSE_FOR' => 'Pause Verfolgung daf�r ',
			'LBL_TRACK_STOP_FOR' => 'Stopp Verfolgung daf�r ',
			'LBL_START' => 'Start',
			'LBL_PAUSE' => 'Pause',
			'LBL_ABANDON' => 'Aufgeben',
			'LBL_EJECT_TRACKING' => 'Werfen Sie aus der Liste',
			'LBL_TRACKING_NO_ENTRIES' => 'Keine aktive oder pausierte trackings',
			'LBL_TRACKING_MSG_START' => 'Sehr geehrter Kunde, das Problem wurde betreut von der betroffenen Abteilung �bernommen. Wir werden Sie �ber den Fortschritt zu informieren.',
			'LBL_TRACKING_MSG_PAUSE' => 'Sehr geehrter Kunde, das Problem erfordert mehr Zeit als erwartet, werden wir auch weiterhin und wir werden Sie �ber den Fortschritt zu informieren.',
			'LBL_TRACKING_MSG_STOP' => 'Sehr geehrter Kunde, beendeten wir die Aufl�sung des Ausgabe, stellen Sie bitte sicher, dass das Problem ist nun behoben.',
			'LBL_TRACKING_ALREADY_RUNNING' => 'Bereits l�uft f�r',
			'LBL_TRACKING_ALREADY_RUNNING_BY_USER' => 'Zur Zeit auch zur�ck',
			'Tracking' => 'Verfolgung',
		),
		'nl_nl' => array(
			'LBL_TRACKER_MODULE' => 'Activity Tracker',
			'LBL_TRACK_NAME' => 'Tracking',
			'LBL_DO_TRACK' => 'Spoor',
			'LBL_DO_TRACK_AND_TICKET' => 'Spoor en Ticket',
			'LBL_DO_TRACK_AND' => 'Spoor en ',
			'LBL_TRACK_AND_COMMENT' => 'Spoor en commentaar',
			'LBL_TRACK_MANAGER' => 'Tracking',
			'LBL_TRACK_MANAGER_TITLE' => 'Active or paused trackings',
			'LBL_CALENDAR_TRACKING' => 'Tracking',
			'LBL_TRACK_START_FOR' => 'Start tracking for this ',
			'LBL_TRACK_PAUSE_FOR' => 'Pause tracking for this ',
			'LBL_TRACK_STOP_FOR' => 'Stop tracking for this ',
			'LBL_START' => 'Begin',
			'LBL_PAUSE' => 'Pauze',
			'LBL_ABANDON' => 'Verlaten',
			'LBL_EJECT_TRACKING' => 'Uitwerpen uit de lijst',
			'LBL_TRACKING_NO_ENTRIES' => 'Geen actieve of onderbroken trackings',
			'LBL_TRACKING_MSG_START' => 'Geachte klant, het probleem is opgevangen door de betrokken afdeling. Wij zullen u informeren over de voortgang.',
			'LBL_TRACKING_MSG_PAUSE' => 'Geachte klant, de kwestie meer tijd dan verwacht nodig, we zullen blijven en we zullen u informeren over de voortgang.',
			'LBL_TRACKING_MSG_STOP' => 'Geachte klant, we klaar met de oplossing van uw probleem, dan kunt u controleren of het probleem nu is opgelost.',
			'LBL_TRACKING_ALREADY_RUNNING' => 'Al actief voor',
			'LBL_TRACKING_ALREADY_RUNNING_BY_USER' => 'Momenteel ook opgespoord door',
			'Tracking' => 'Tracking',
		),
		'pt_br' => array(
			'LBL_TRACKER_MODULE' => 'Tracker atividade',
			'LBL_TRACK_NAME' => 'Rastreamento',
			'LBL_DO_TRACK' => 'Tra�o',
			'LBL_DO_TRACK_AND_TICKET' => 'Tra�o e Ticket',
			'LBL_DO_TRACK_AND' => 'Tra�o e ',
			'LBL_TRACK_AND_COMMENT' => 'Tra�o e comenta',
			'LBL_TRACK_MANAGER' => 'Rastreamento',
			'LBL_TRACK_MANAGER_TITLE' => 'Trackings ativos ou pausadas',
			'LBL_CALENDAR_TRACKING' => 'Rastreamento',
			'LBL_TRACK_START_FOR' => 'Iniciar rastreamento para este ',
			'LBL_TRACK_PAUSE_FOR' => 'Pausa de rastreamento para este ',
			'LBL_TRACK_STOP_FOR' => 'Pare de rastreamento para este',
			'LBL_START' => 'Iniciar',
			'LBL_PAUSE' => 'Pausa',
			'LBL_ABANDON' => 'Abandonar',
			'LBL_EJECT_TRACKING' => 'Ejetar da lista',
			'LBL_TRACKING_NO_ENTRIES' => 'Nenhum trackings ativas ou pausadas',
			'LBL_TRACKING_MSG_START' => 'Prezado Cliente, o problema foi cuidado pelo departamento envolvido. Vamos inform�-lo sobre o andamento.',
			'LBL_TRACKING_MSG_PAUSE' => 'Prezado Cliente, a quest�o requer mais tempo do que o esperado, vamos continuar e vamos inform�-lo sobre o andamento.',
			'LBL_TRACKING_MSG_STOP' => 'Prezado Cliente, n�s terminamos a resolu��o do seu problema, por favor, verifique se o problema j� est� resolvido.',
			'LBL_TRACKING_ALREADY_RUNNING' => 'J� em execu��o para',
			'LBL_TRACKING_ALREADY_RUNNING_BY_USER' => 'Atualmente tamb�m tra�ada por',
			'Tracking' => 'Rastreamento',
		),
	),

	'Calendar' => array(
		'it_it' => array(
			'Tracked' => 'Tracciato',
		),
		'en_us' => array(
			'Tracked' => 'Tracked',
		),		
		'de_de' => array(
			'Tracked' => 'Raupen',
		),
		'nl_nl' => array(
			'Tracked' => 'Bijgehouden',
		),		
		'pt_br' => array(
			'Tracked' => 'Acompanhou',
		),
	),

);

foreach ($trans as $module=>$modlang) {
	foreach ($modlang as $lang=>$translist) {
		foreach ($translist as $label=>$translabel) {
			SDK::setLanguageEntry($module, $lang, $label, $translabel);
		}
	}
}
