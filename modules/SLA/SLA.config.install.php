<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@123658 */

$sla_holidays = HolidaysUtils::getHolidaysForYear(date('Y'), 'IT'); // crmv@201442

$sla_config = array();

$sla_config['default'] = Array( 	// default configuration
	'time_measure'=>'seconds',
	'status_field'=>'', 		//campo stato del modulo
	'status_idle_value'=>Array( //stati del modulo per i quali il conteggio dello SLA è in "pausa"
		'Wait For Response',
	),
	'status_close_value'=>Array( //stati del modulo per i quali considerare chiuso il ticket (si calcola il tempo effettivamente trascorso in base alla data e ora chiusura effettiva -> si può utilizzare il conditional per forzare la compilazione dei suddetti campi!)
		'Closed',
	),
	'auto_set_closing_datetime'=>true, // inserimento automatico data e ora chiusura una volta messo in stato chiuso
	'hours'=>Array( //orario giornaliero nel quale effettuare il conteggio
		0=>Array(Array("8:00","12:00"),Array("15:00","19:00")), //domenica
		1=>Array(Array("8:00","12:00"),Array("15:00","19:00")), //lunedi
		2=>Array(Array("8:00","12:00"),Array("15:00","19:00")), //martedi
		3=>Array(Array("8:00","12:00"),Array("15:00","19:00")), //mercoledi
		4=>Array(Array("8:00","12:00"),Array("15:00","19:00")), //giovedi
		5=>Array(Array("8:00","12:00"),Array("15:00","19:00")), //venerdi
		6=>Array(Array("8:00","12:00"),Array("15:00","19:00")), //sabato
	),
	'jump_days'=>Array( // giorni della settimana da saltare nel conteggio (0 = domenica 1= lunedi......6 = sabato)
		0
	), 
	'holidays'=> $sla_holidays,
	'force_days'=>Array( //giorni nell'anno da contare nonostante siano da saltare, oppure quelli con una finestra temporale diversa dal normale (in formato dd-mm => finestre temporali, come nell'array hours!)
		//esempio "12-12"=>Array(Array("8:00","12:00"),Array("15:00","19:00")) //crmv@46872
	), 
	'fields'=>Array( //campi calcolati
		'time_elapsed',
		'time_remaining',
		'start_sla',
		'end_sla',
		'time_refresh',
		'sla_time',
		'due_date',
		'due_time',
		'time_change_status',
		'time_elapsed_change_status',
		'reset_sla',
		'ended_sla',
		'time_elapsed_idle',
	),
);

/* --- UPDATE MARKER --- */
// please don't change the previous line, it's used to update the configuration */

// default config for HelpDesk module
$sla_config['HelpDesk'] = array(
	'status_field'=>'ticketstatus',
	'status_idle_value'=>Array(
		'Wait For Response',
	),
	'status_close_value'=>Array(
		'Closed',
	),
);

// here you can add other default modules during install
