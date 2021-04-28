<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
include_once("Users_functions.php");
include("../config.php");
global $table_prefix;
$log_active = false;
//modulo da importare:
$module = 'Users';
//array mappaggio campi: nome campo tabella di appoggio => fieldname di vte
$mapping = Array(
	'user_name'=>'user_name',			/* 	User Name - Nome Utente			*/
	'external_code'=>'external_code',	/* 	External Code - Codice Esterno	*/
	'first_name'=>'first_name',			/* 	First Name - Nome				*/
	'last_name'=>'last_name',			/* 	Last Name - Cognome				*/
	'email'=>'email1',					/* 	Email							*/
	//'password' => 'user_password', 	/*  Password, cleartext				*/
);
//campo nella tabella di appoggio per identificare il codice esterno (sul quale l'import effettuerà la creazione/aggiornamento dei dati)
$external_code = 'external_code';
//tabella di appoggio
$table = "erp_users";
//condizioni sulla tabella di appoggio
$where = "";
//$order_by = "order by data_record asc";
// override query
if (!empty($users_query)) $override_query = $users_query;
//campi di default in creazione
$fields_auto_create[$table_prefix.'_users']['is_admin'] = 'off';
$fields_auto_create[$table_prefix.'_users']['status'] = 'Active';
$fields_auto_create[$table_prefix.'_users']['currency_id'] = 1;
$fields_auto_create[$table_prefix.'_users']['menu_view'] = 'Small Menu';
$fields_auto_create[$table_prefix.'_users']['internal_mailer'] = 1;
$fields_auto_create[$table_prefix.'_users']['activity_view'] = 'This Week';
$fields_auto_create[$table_prefix.'_users']['date_format'] = 'dd-mm-yyyy';
$fields_auto_create[$table_prefix.'_users']['reminder_interval'] = 'None';
$fields_auto_create[$table_prefix.'_users']['start_hour'] = '08:00';
$fields_auto_create[$table_prefix.'_users']['end_hour'] = '';
$fields_auto_create[$table_prefix.'_users']['hour_format'] = '24';
$fields_auto_create[$table_prefix.'_users']['no_week_sunday'] = 1;
$fields_auto_create[$table_prefix.'_users']['default_theme'] = 'next';
$fields_auto_create[$table_prefix.'_users']['default_language'] = 'it_it';
$fields_auto_create[$table_prefix.'_users']['default_module'] = 'Home';
// $fields_auto_create[$table_prefix.'_users']['allow_generic_talk'] = 1;
// $fields_auto_create[$table_prefix.'_users']['receive_public_talks'] = 1;
$fields_auto_create[$table_prefix.'_users']['notify_me_via'] = 'ModNotifications';
$fields_auto_create[$table_prefix.'_users']['user_timezone'] = 'Europe/Rome';
$fields_auto_create[$table_prefix.'_users']['notify_summary'] = 'Never';
// you can also read the password from the external table
$fields_auto_create[$table_prefix.'_users']['user_password'] = 'password';
$fields_auto_create[$table_prefix.'_user2role']['roleid'] = 'H8';

//campi di default in aggiornamento
$fields_jump_update = Array();
?>