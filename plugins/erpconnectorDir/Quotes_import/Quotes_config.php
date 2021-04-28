<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
include_once("Quotes_functions.php");
include("../config.php");
global $table_prefix;
$log_active = false;
//modulo da importare:
$module = 'Quotes';
$module_row = 'Quotes_row';
//array mappaggio campi testata: nome campo tabella di appoggio => fieldname di vte
$mapping = Array(
	'name'=>'subject',							/*	Subject - Soggetto														*/
	'external_code'=>'external_code',			/* 	External Code - Codice Esterno											*/
	'status'=>'quotestage',						/*	Quote Stage - Stadio Preventivo											*/
	'valid_till'=>'validtill',					/*	Valid Till - Valido fino a												*/
	'contact_external_code'=>'contact_id',		/*	Contact Name - Nome Contatto (Codice esterno del contatto collegato)	*/
	'account_external_code'=>'account_id',		/*	Account Name - Nome Azienda (Codice esterno dell'azienda collegata)		*/
	'currency'=>'currency_id',					/*	Currency - Valuta (es. EUR)												*/
	'shipping'=>'shipping',						/*	Shipping - Spedizione													*/
	'bill_address'=>'bill_street',				/* 	Billing Address - Indirizzo (Fatturazione)								*/
	'bill_zip_code'=>'bill_code',				/* 	Billing Code - Codice (Fatturazione) 									*/
	'bill_city'=>'bill_city',					/* 	Billing City - Citta` (Fatturazione)									*/
	'bill_province'=>'bill_state',				/* 	Billing State - Provincia (Fatturazione)								*/
	'bill_nation'=>'bill_country',				/* 	Billing Country - Stato (Fatturazione)									*/
	'ship_address'=>'ship_street',				/* 	Shipping Address - Indirizzo (Spedizione)								*/
	'ship_zip_code'=>'ship_code',				/* 	Shipping Code - Codice (Spedizione)										*/
	'ship_city'=>'ship_city',					/* 	Shipping City - Citta` (Spedizione)										*/
	'ship_province'=>'ship_state',				/* 	Shipping State - Provincia (Spedizione)									*/
	'ship_nation'=>'ship_country',				/* 	Shipping Country - Stato (Spedizione)									*/
	'terms_conditions'=>'terms_conditions',		/*	Terms & Conditions - Termini e Condizioni								*/
	'description'=>'description',				/*	Description - Descrizione												*/
//	'user_external_code'=>'assigned_user_id',	/*	Assigned To - Assegnato a (Codice esterno dell'utente)					*/
);
//array mappaggio capi righe prodotto: nome campo tabella di appoggio => colonna nella tabella vte_inventoryproductrel
$mapping_row = Array(
	'external_code'=>'id',						/* 	External Code - Codice Esterno	(del modulo corrente)					*/
	'product_external_code'=>'productid',		/* 	Codice esterno del prodotto da collegare								*/
	'sequence_no'=>'sequence_no',				/* 	Numero si sequenza del prodotto											*/
	'quantity'=>'quantity',						/*	Quantity - Quantita`													*/
	'price'=>'listprice',						/*	List Price - Prezzo di listino											*/
	'comment'=>'comment',						/*	Comment - Commento														*/
);
$additional_fields_row = Array();
//campo nella tabella di appoggio per identificare il codice esterno (sul quale l'import effettuer� la creazione/aggiornamento dei dati)
$external_code = 'external_code';
$external_code_product = Array('product_external_code'=>'external_code');	//campo tabella row che identifica il prodotto (non la riga prodotto nell'inventory, ma l'entit� prodotto) => fieldname del campo External code nel modulo Products
//tabella di appoggio
$table = "erp_quotes";
$table_row = "erp_quotes_row";
//condizioni sulla tabella di appoggio
$limit = false;
$limit_row = false;
$where = "";
$where_row = "";
$order_by = "";
$order_by_row = "";
// query override
if (!empty($quotes_query)) $override_query = $quotes_query;
if (!empty($quotes_row_query)) $override_query_row = $quotes_row_query;
//campi di default in creazione
$fields_auto_create[$table_prefix.'_crmentity']['smownerid'] = 1;
$fields_auto_create[$table_prefix.'_crmentity']['modifiedby'] = 1;
$fields_auto_create[$table_prefix.'_crmentity']['smcreatorid'] = 1;
$fields_auto_create[$table_prefix.'_quotes']['discount_percent'] = 0;
$fields_auto_create[$table_prefix.'_quotes']['discount_amount'] = 0;
$fields_auto_create[$table_prefix.'_quotes']['s_h_amount'] = 0;
$fields_auto_create[$table_prefix.'_quotes']['adjustment'] = 0;
$fields_auto_create[$table_prefix.'_quotes']['taxtype'] = 'group';
//campi di default in aggiornamento
$fields_auto_update[$table_prefix.'_crmentity']['modifiedby'] = 1;
$fields_jump_update = Array();
?>