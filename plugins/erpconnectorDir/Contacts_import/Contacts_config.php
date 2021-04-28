<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
include_once("Contacts_functions.php");
include("../config.php");
$log_active = false;
//modulo da importare:
$module = 'Contacts';
//array mappaggio campi: nome campo tabella di appoggio => fieldname di vte
$mapping = Array(
	'first_name'=>'firstname',					/* 	First Name - Nome													*/
	'last_name'=>'lastname',					/*	Last Name - Cognome													*/
	'external_code'=>'external_code',			/* 	External Code - Codice Esterno										*/
	'account_external_code'=>'account_id',		/*	Account Name - Nome Azienda	(Codice esterno dell'azienda collegata)	*/
	'phone'=>'phone',							/*	Office Phone - Telefono Ufficio										*/
	'mobile'=>'mobile',							/*	Mobile - Cellulare													*/
	'home_phone'=>'homephone',					/*	Home Phone - Telefono Casa											*/
	'other_phone'=>'otherphone',				/*	Other Phone - Altro Telefono										*/
	'title'=>'title',							/*	Title - Titolo														*/
	'fax'=>'fax',								/*	Fax																	*/
	'email'=>'email',							/*	Email																*/
	'birthday'=>'birthday',						/*	Birthdate - Compleanno												*/
	'address'=>'mailingstreet',					/*	Mailing Street - Via (spedizione)									*/
	'zip_code'=>'mailingzip',					/*	Mailing Zip - CAP (spedizione)										*/
	'city'=>'mailingcity',						/*	Mailing City - Citta` (spedizione)									*/
	'province'=>'mailingstate',					/*	Mailing State - Provincia (spedizione)								*/
	'nation'=>'mailingcountry',					/*	Mailing Country - Stato (spedizione)								*/
	'other_address'=>'otherstreet',				/*	Other Street - Altra Via											*/
	'other_zip_code'=>'otherzip',				/*	Other Zip - Altro CAP												*/
	'other_city'=>'othercity',					/*	Other City - Altra Citta`											*/
	'other_province'=>'otherstate',				/*	Other State - Altra Provincia										*/
	'other_nation'=>'othercountry',				/*	Other Country - Altro Stato											*/
//	'user_external_code'=>'assigned_user_id',	/*	Assigned To - Assegnato a (Codice esterno dell'utente)				*/
);
//campo nella tabella di appoggio per identificare il codice esterno (sul quale l'import effettuerà la creazione/aggiornamento dei dati)
global $table_prefix;
$external_code = 'external_code';
//tabella di appoggio
$table = "erp_contacts";
//condizioni sulla tabella di appoggio
$where = "";
//$order_by = "order by data_record asc";
// override query
if (!empty($contacts_query)) $override_query = $contacts_query;
//campi di default in creazione
$fields_auto_create[$table_prefix.'_crmentity']['smownerid'] = 1;
$fields_auto_create[$table_prefix.'_crmentity']['modifiedby'] = 1;
$fields_auto_create[$table_prefix.'_crmentity']['smcreatorid'] = 1;
//campi di default in aggiornamento
$fields_auto_update[$table_prefix.'_crmentity']['modifiedby'] = 1;
$fields_jump_update = Array();
?>