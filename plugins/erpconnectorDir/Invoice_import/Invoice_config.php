<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
include_once("Invoice_functions.php");
include("../config.php");
global $table_prefix;
$log_active = false;
//modulo da importare:
$module = 'Invoice';
$module_row = 'Invoice_row';
//array mappaggio campi testata: nome campo tabella di appoggio => fieldname di vte
$mapping = Array(
	'name'=>'subject',								/*	Subject - Soggetto														*/
	'external_code'=>'external_code',				/* 	External Code - Codice Esterno											*/
	'status'=>'invoicestatus',						/*	Status - Stato															*/
	'date'=>'invoicedate',							/*	Invoice Date - Data Fattura												*/
	'due_date'=>'duedate',							/*	Due Date - Scadenza Pagamento 											*/
	'salesorder_external_code'=>'salesorder_id',	/*	Sales Order - Ordine di Vendita (Codice esterno dell'ordine collegato)	*/
	'contact_external_code'=>'contact_id',			/*	Contact Name - Nome Contatto (Codice esterno del contatto collegato)	*/
	'account_external_code'=>'account_id',			/*	Account Name - Nome Azienda (Codice esterno dell'azienda collegata)		*/
	'currency'=>'currency_id',						/*	Currency - Valuta (es. EUR)												*/
	'bill_address'=>'bill_street',					/* 	Billing Address - Indirizzo (Fatturazione)								*/
	'bill_zip_code'=>'bill_code',					/* 	Billing Code - Codice (Fatturazione) 									*/
	'bill_city'=>'bill_city',						/* 	Billing City - Citta` (Fatturazione)									*/
	'bill_province'=>'bill_state',					/* 	Billing State - Provincia (Fatturazione)								*/
	'bill_nation'=>'bill_country',					/* 	Billing Country - Stato (Fatturazione)									*/
	'ship_address'=>'ship_street',					/* 	Shipping Address - Indirizzo (Spedizione)								*/
	'ship_zip_code'=>'ship_code',					/* 	Shipping Code - Codice (Spedizione)										*/
	'ship_city'=>'ship_city',						/* 	Shipping City - Citta` (Spedizione)										*/
	'ship_province'=>'ship_state',					/* 	Shipping State - Provincia (Spedizione)									*/
	'ship_nation'=>'ship_country',					/* 	Shipping Country - Stato (Spedizione)									*/
	'terms_conditions'=>'terms_conditions',			/*	Terms & Conditions - Termini e Condizioni								*/
	'description'=>'description',					/*	Description - Descrizione												*/
//	'user_external_code'=>'assigned_user_id',		/*	Assigned To - Assegnato a (Codice esterno dell'utente)					*/
);
//array mappaggio capi righe prodotto: nome campo tabella di appoggio => colonna nella tabella vte_inventoryproductrel
$mapping_row = Array(
	'external_code'=>'id',							/* 	External Code - Codice Esterno	(del modulo corrente)					*/
	'product_external_code'=>'productid',			/* 	Codice esterno del prodotto da collegare								*/
	'sequence_no'=>'sequence_no',					/* 	Numero si sequenza del prodotto											*/
	'quantity'=>'quantity',							/*	Quantity - Quantita`													*/
	'price'=>'listprice',							/*	List Price - Prezzo di listino											*/
	'comment'=>'comment',							/*	Comment - Commento														*/
);
$additional_fields_row = Array();
//campo nella tabella di appoggio per identificare il codice esterno (sul quale l'import effettuer� la creazione/aggiornamento dei dati)
$external_code = 'external_code';
$external_code_product = Array('product_external_code'=>'external_code');	//campo tabella row che identifica il prodotto (non la riga prodotto nell'inventory, ma l'entit� prodotto) => fieldname del campo External code nel modulo Products
//tabella di appoggio
$table = "erp_invoice";
$table_row = "erp_invoice_row";
//condizioni sulla tabella di appoggio
$limit = false;
$limit_row = false;
$where = "";
$where_row = "";
$order_by = "";
$order_by_row = "";
// query override
if (!empty($invoice_query)) $override_query = $invoice_query;
if (!empty($invoice_row_query)) $override_query_row = $invoice_row_query;
//campi di default in creazione
$fields_auto_create[$table_prefix.'_crmentity']['smownerid'] = 1;
$fields_auto_create[$table_prefix.'_crmentity']['modifiedby'] = 1;
$fields_auto_create[$table_prefix.'_crmentity']['smcreatorid'] = 1;
$fields_auto_create[$table_prefix.'_invoice']['discount_percent'] = 0;
$fields_auto_create[$table_prefix.'_invoice']['discount_amount'] = 0;
$fields_auto_create[$table_prefix.'_invoice']['s_h_amount'] = 0;
$fields_auto_create[$table_prefix.'_invoice']['adjustment'] = 0;
$fields_auto_create[$table_prefix.'_invoice']['taxtype'] = 'group';
//campi di default in aggiornamento
$fields_auto_update[$table_prefix.'_crmentity']['modifiedby'] = 1;
$fields_jump_update = Array();
?>