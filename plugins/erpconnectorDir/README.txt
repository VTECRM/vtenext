1 -	Rinominare la cartella plugins/erpconnectorDir ad esempio in plugins/erpconnector per non perdere eventuali sviluppi con un aggiornamento di versione

2 -	Impostare il percorso della cartella nella variabile $erpconnector_dir nei file config.php e InstallErpconnector.php

3 -	Le cartelle Accounts_import, Contacts_import, ... contengono lo script di importazione per il relativo modulo (Accounts, Contacts, ...)

4 -	Vanno configurati i file plugins/erpconnector/MODULO_import/MODULO_config.php per definire i campi da importare e poi eseguito il file 
	InstallErpconnector.php per creare le tabelle di log e le tabelle specifiche di ogni modulo da leggere e importare in VTE.
	Le tabelle di ogni modulo vengono create dinamicamente in base ai campi definiti nell'array $mapping dentro ad ogni file .../MODULO_config.php.
	Per chi avesse problemi ad eseguire il file � presente anche il file InstallErpconnector.sql contenente il codice sql delle tabelle standard.

NB.	In ogni file .../MODULO_config.php � commentato il valore 'user_external_code' nell'array $mapping ed � presente la riga:
	$fields_auto_create['vte_crmentity']['smownerid'] = 1;
	Questo implica che le entit� importate saranno asegnate sempre all'utente di VTE con id = 1 (admin).

	Se si vogliono importare da un sistema esterno gli utenti e assegnargli le entit� aziende, contatti, ecc. vanno prima importati
	gli utenti e poi nel file .../MODULO_config.php delle altre entit� va commentata la riga:
	$fields_auto_create['vte_crmentity']['smownerid'] = 1;
	e decommentato il valore 'user_external_code' nell'array $mapping.
	A questo punto (dopo aver lanciato il file InstallErpconnector.php) nella tabella di importazione si pu� inserire il codice esterno dell'utente
	a cui assegnare ogni entit�.
	
	Per ognuno dei moduli Quotes, SalesOrder e Invoice sono presenti 2 tabelle di importazioni che contengono una le testate e l'altra le rige prodotto.
	Per associare testata con righe deve coincidere il campo external_code.
	
5 - Una volta popolate le tabelle di importazione vanno eseguiti i file plugins/erpconnector/MODULO_import/MODULO.php
	Utilizzando i campi 'external_code' le entit� gi� importate vengono aggiornate a una seconda esecuzione del file.
	
6 - Trovate i log nelle tabelle erp_log_script_state e erp_log_script_content

NB.
Per importazioni in MySQL, nel blocco [mysqld] del file di configurazione my.cnf aggiungere:
local-infile=1