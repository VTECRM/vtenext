<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
include_once("Products_functions.php");
include("../config.php");
global $table_prefix;
$log_active = false;
//modulo da importare:
$module = 'Products';
//array mappaggio campi: nome campo tabella di appoggio => fieldname di vte
$mapping = Array(
	'name'=>'productname',						/*	Product Name - Nome Prodotto							*/
	'external_code'=>'external_code',			/* 	External Code - Codice Esterno							*/
	'code'=>'productcode',						/*	Part Number - Codice Prodotto							*/
	'category'=>'productcategory',				/*	Product Category - Categoria Prodotto					*/
	'start_date'=>'start_date',					/*	Support Start Date - Data Inizio Supporto				*/
	'expiry_date'=>'expiry_date',				/*	Support Expiry Date - Data Termine Supporto				*/
	'sales_start_date'=>'sales_start_date',		/*	Sales Start Date - Data Inizio Vendite					*/
	'sales_end_date'=>'sales_end_date',			/*	Sales End Date - Data Fine Vendite						*/
	'website'=>'website',						/*	Website													*/
	'serial_no'=>'serial_no',					/*	Serial No - Numero di Serie								*/
	'glacct'=>'glacct',							/*	GL Account - Codice Contabile							*/
	'price'=>'unit_price',						/*	Unit Price - Prezzo Unitario							*/
	'description'=>'description',				/*	Description - Descrizione								*/
//	'user_external_code'=>'assigned_user_id',	/*	Assigned To - Assegnato a (Codice esterno dell'utente)	*/
);
//campo nella tabella di appoggio per identificare il codice esterno (sul quale l'import effettuerà la creazione/aggiornamento dei dati)
$external_code = 'external_code';
//tabella di appoggio
$table = "erp_products";
//condizioni sulla tabella di appoggio
$where = "";
//$order_by = "order by data_record asc";
// override query
if (!empty($products_query)) $override_query = $products_query;
//campi di default in creazione
$fields_auto_create[$table_prefix.'_crmentity']['smownerid'] = 1;
$fields_auto_create[$table_prefix.'_crmentity']['modifiedby'] = 1;
$fields_auto_create[$table_prefix.'_crmentity']['smcreatorid'] = 1;
$fields_auto_create[$table_prefix.'_products']['discontinued'] = 1;		/*	Product Active - Prodotto Attivo	*/
//campi di default in aggiornamento
$fields_auto_update[$table_prefix.'_crmentity']['modifiedby'] = 1;
$fields_jump_update = Array();
?>