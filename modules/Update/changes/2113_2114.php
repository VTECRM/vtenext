<?php
/* crmv@207852 */

global $adb, $table_prefix;

$defaultHandlersPaths = [
    'modules/Update/UpdatePopupHandler.php',
    'data/VTEntityDelta.php',
    'modules/SalesOrder/RecurringInvoiceHandler.php',
    'modules/com_vtiger_workflow/VTEventHandler.inc',
    'modules/Users/MenuViewHandler.php',
    'modules/Calendar/CalendarHandler.php',
    'modules/HelpDesk/HelpDeskStatusHandler.php',
    'modules/SLA/SLAHandler.php',
    'modules/ModNotifications/ModNotificationsHandler.php',
    'modules/FieldFormulas/VTFieldFormulasEventHandler.inc',
    'modules/Timecards/TimecardsHandler.php',
    'modules/WSAPP/WorkFlowHandlers/WSAPPAssignToTracker.php',
    'modules/ProjectTask/ProjectTaskHandler.php',
    'modules/MyNotes/MyNotesHandler.php',
    'modules/ServiceContracts/ServiceContractsHandler.php',
    'modules/Newsletter/NewsletterHandler.php',
    'modules/Geolocalization/GeolocalizationHandler.php',
    'modules/ChangeLog/ChangeLogHandler.php',
    'modules/Settings/ProcessMaker/ProcessMakerHandler.php',
    'modules/Transitions/TransitionHandler.php',
    'include/utils/GDPRWS/handlers/GDPRHandler.php',
];

$update = $adb->query("UPDATE {$table_prefix}_eventhandlers SET event_name = REPLACE(event_name, 'vtiger.', 'vte.')");

$tabs = $adb->query("SELECT handler_path FROM {$table_prefix}_eventhandlers");

if (!!$tabs && $adb->num_rows($tabs) > 0) {
    while ($row = $adb->fetchByAssoc($tabs, -1, false)) {
        if(!in_array($row['handler_path'], $defaultHandlersPaths)){
            $data = '';
            $handle = fopen($row['handler_path'], "r");
            if ($handle !== false) {//if file was opened correctly
                while(($line = fgets($handle)) !== false) {
                    $line = str_replace('vtiger.entity.', 'vte.entity.', $line);
                    $data .= $line;
                }
                fclose($handle);

                //check if data is not empty
                if($data !== ''){
                    $handleToWrite = fopen($row['handler_path'], "w");

                    fwrite($handleToWrite, $data);
                    fclose($handleToWrite);
                }
            }
        }
    }
}




SDK::setLanguageEntries('ALERT_ARR', 'LBL_UTF8', array(
    'it_it'=>'Prego cambiare il file di configurazione (situato nella root di VTE CRM, con il nome config-inc.php) per il supporto al set di caratteri UTF-8 e poi aggiorna la pagina',
    'en_us'=>'Please change the configuration file (located in the root of VTE CRM, with the name config-inc.php) for UTF-8 character set support and then refresh the page',
    'pl_pl'=>'Aby uzsykać pomoc w konfiguracji znaków UTF-8, zmień plik (który znajduje się w katalogu głównym VTE CRM pod nazwą config-inc.php) i przeładuj stronę',
    'de_de'=>'Bitte ändern Sie die Konfigurationsdatei (in der Wurzel des VTE CRM befindet, mit dem Namen config-inc.php), um die UTF-8-Zeichensatz unterstützen und aktualisieren Sie die Seite',
    'pt_br'=> 'Por favor modificar o arquivo de configuração (situado na root de VTE CRM, com o nome config-inc.php) para o suporte ao set de caracteres UTF-8 e ebntão atualize a página'
));


