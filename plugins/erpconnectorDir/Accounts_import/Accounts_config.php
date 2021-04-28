<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
include_once("Accounts_functions.php");
include("../config.php");
global $table_prefix;
$log_active = false;
//modulo da importare:
$module = 'Accounts';
//array mappaggio campi: nome campo tabella di appoggio => fieldname di vte
$mapping = Array(
	'name'=>'accountname',										/* 	Account Name - Nome Azienda								*/
	'external_code'=>'external_code',							/* 	External Code - Codice Esterno							*/
	'website'=>'website',										/* 	Website - Sito Web										*/
	'phone'=>'phone',											/* 	Phone - Telefono										*/
	'other_phone'=>'otherphone',								/* 	Other Phone - Altro Telefono							*/
	'fax'=>'fax',												/*	Fax														*/
	'email'=>'email1',											/*	Email 													*/
	'other_email'=>'email2',									/* 	Other Email - Altra Email 								*/
	'type'=>'accounttype',										/* 	Type - Tipo 											*/
	'bank_details'=>'crmv_bankdetails',							/* 	Bank Details - Coordinate Bancarie 						*/
	'vat_registration_number'=>'crmv_vat_registration_number',	/* 	VAT Registration Number - Partita IVA 					*/
	'social_security_number'=>'crmv_social_security_number',	/* 	Social Security number - Codice Fiscale					*/
	'bill_address'=>'bill_street',								/* 	Billing Address - Indirizzo (Fatturazione)				*/
	'bill_zip_code'=>'bill_code',								/* 	Billing Code - Codice (Fatturazione) 					*/
	'bill_city'=>'bill_city',									/* 	Billing City - Citta` (Fatturazione)					*/
	'bill_province'=>'bill_state',								/* 	Billing State - Provincia (Fatturazione)				*/
	'bill_nation'=>'bill_country',								/* 	Billing Country - Stato (Fatturazione)					*/
	'ship_address'=>'ship_street',								/* 	Shipping Address - Indirizzo (Spedizione)				*/
	'ship_zip_code'=>'ship_code',								/* 	Shipping Code - Codice (Spedizione)						*/
	'ship_city'=>'ship_city',									/* 	Shipping City - Citta` (Spedizione)						*/
	'ship_province'=>'ship_state',								/* 	Shipping State - Provincia (Spedizione)					*/
	'ship_nation'=>'ship_country',								/* 	Shipping Country - Stato (Spedizione)					*/
//	'user_external_code'=>'assigned_user_id',					/*	Assigned To - Assegnato a (Codice esterno dell'utente)	*/
);
//campo nella tabella di appoggio per identificare il codice esterno (sul quale l'import effettuerà la creazione/aggiornamento dei dati)
$external_code = 'external_code';
//tabella di appoggio
$table = "erp_accounts";
//condizioni sulla tabella di appoggio
$where = "";
//$order_by = "order by data_record asc";
// override query
if (!empty($accounts_query)) $override_query = $accounts_query;
//campi di default in creazione
$fields_auto_create[$table_prefix.'_crmentity']['smownerid'] = 1;		/*	Assigned To - Assegnato a					*/
$fields_auto_create[$table_prefix.'_crmentity']['modifiedby'] = 1;
$fields_auto_create[$table_prefix.'_crmentity']['smcreatorid'] = 1;	
$fields_auto_create[$table_prefix.'_account']['rating'] = 'Active';		/*	Rating										*/
//campi di default in aggiornamento
$fields_auto_update[$table_prefix.'_crmentity']['modifiedby'] = 1;
$fields_jump_update = Array();
?>