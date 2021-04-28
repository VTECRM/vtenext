<?php
global $adb, $table_prefix;

SDK::setLanguageEntries('Reports', 'Activities by users', array('it_it'=>'Attività per utente','en_us'=>'Activities by users'));
SDK::setLanguageEntries('Reports', 'Quotes by status', array('it_it'=>'Preventivi per stato','en_us'=>'Quotes by status'));
SDK::setLanguageEntries('Reports', 'Created quotes by users', array('it_it'=>'Preventivi creati per utente','en_us'=>'Created quotes by users'));
SDK::setLanguageEntries('Reports', 'Reviewed quotes by users', array('it_it'=>'Preventivi revisionati per utente','en_us'=>'Reviewed quotes by users'));
SDK::setLanguageEntries('Reports', 'Accounts by users', array('it_it'=>'Aziende per utente','en_us'=>'Accounts by users'));
SDK::setLanguageEntries('Reports', 'Potentials by status', array('it_it'=>'Opportunita per stato','en_us'=>'Potentials by status'));
SDK::setLanguageEntries('Reports', 'Leads count', array('it_it'=>'Conteggio lead','en_us'=>'Lead counts'));
SDK::setLanguageEntries('Reports', 'Contacts by users', array('it_it'=>'Contatti per utenti','en_us'=>'Contacts by users'));
SDK::setLanguageEntries('Reports', 'Invoice total', array('it_it'=>'Totale fatture','en_us'=>'Invoice total'));
SDK::setLanguageEntries('Reports', 'Invoices by status', array('it_it'=>'Fatture per stato','en_us'=>'Invoices by status'));
SDK::setLanguageEntries('Reports', 'SalesOrder by status', array('it_it'=>'Ordini per stato','en_us'=>'SalesOrder by status'));

$adb->pquery("update {$table_prefix}_report set reportname = ? where reportname = ?", array('Activities by users','Attività  per utente'));
$adb->pquery("update {$table_prefix}_report set reportname = ? where reportname = ?", array('Quotes by status','Preventivi per Stato'));
$adb->pquery("update {$table_prefix}_report set reportname = ? where reportname = ?", array('Created quotes by users','Preventivi Creati per utente'));
$adb->pquery("update {$table_prefix}_report set reportname = ? where reportname = ?", array('Reviewed quotes by users','Preventivi Revisionati per utente'));
$adb->pquery("update {$table_prefix}_report set reportname = ? where reportname = ?", array('Accounts by users','Aziende per utente'));
$adb->pquery("update {$table_prefix}_report set reportname = ? where reportname = ?", array('Potentials by status','Opportunita per stato'));
$adb->pquery("update {$table_prefix}_report set reportname = ? where reportname = ?", array('Leads count','Numero Leads'));
$adb->pquery("update {$table_prefix}_report set reportname = ? where reportname = ?", array('Contacts by users','Contatti per utenti'));
$adb->pquery("update {$table_prefix}_report set reportname = ? where reportname = ?", array('Invoice total','Totale Fatture'));
$adb->pquery("update {$table_prefix}_report set reportname = ? where reportname = ?", array('Invoices by status','Fatture per stato'));
$adb->pquery("update {$table_prefix}_report set reportname = ? where reportname = ?", array('SalesOrder by status','Ordini per Stato'));