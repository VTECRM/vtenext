<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@5687 */

/* 
 * Example file which can use as a base to override specific Webservices behaviours.
 * Just rename this file to WSOverride.php and populate the variables/functions here
 *
 */


// Sostituisce l'estrazione dei moduli originale
$ws_replace_sql = "";	

// Filtro aggiuntivo alla query di etrazione dei moduli (OR per aggiungere altri moduli, AND per diminuire la visibilità a quelli già estratti)
// E' necessario fare logout e login da Outlook quando si modifica questa stringa perchè i moduli caricati vengono messi in cache
// $ws_additional_modules = " OR {$table_prefix}_field.tabid IN ('2','13') ";	
$ws_additional_modules = " ";	

// Filtri aggiuntivi per l'estrazione dei record ricercati nel collegamento di una mail
/*
$ws_filters = array('13'=>" AND {$table_prefix}_troubletickets.status = 'Open' ",
					'2'=>" AND {$table_prefix}_potential.sales_stage <> 'Closed Lost' "
);
*/
$ws_filters = array();