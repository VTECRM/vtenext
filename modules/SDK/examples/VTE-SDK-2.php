<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
die('Remove die!');

/* Let's include some handy stuff */
include_once('../../../config.inc.php');
chdir($root_directory);
require_once('include/utils/utils.php');
include_once('vtlib/Vtecrm/Module.php');
$vtlib_Utils_Log = true;

/* Start the session, in order to allow SDK to update values stored in 
 * $_SESSION array. SDK uses the session to store values when updating 
 * to speed up queries.
 * If session has not started, you need to log out and login every time a 
 * SDK method is called. */
VteSession::start();

/* Retrieve instance of SDK module */
$SDKdir = 'modules/SDK/';
$moduleInstance = Vtecrm_Module::getInstance('SDK');
if (empty($moduleInstance)) {
	die('Modulo SDK non inizializzato');
}

/* Clears previous SDK values in the session array */
SDK::clearSessionValues();

/* create hooks for various examples */
$exdir = 'modules/SDK/examples/';
$module = "Accounts";
$accountsdir = $exdir.'Accounts/';

SDK::setClass('Accounts', 'Accounts2', $accountsdir.'ClassAccounts.php');
SDK::setClass('Accounts2', 'Accounts3', $accountsdir.'ClassAccounts.php');
SDK::setExtraSrc($module, $accountsdir."t3.png");
SDK::setLanguageEntry($module, 'en_us', 'Description', 'Write here your opinion');
SDK::setLanguageEntry($module, 'it_it', 'Description', 'Scrivi qui cosa pensi');
SDK::setPreSave($module, $accountsdir."PresaveAccounts.php");
SDK::addView($module, $accountsdir.'ViewAccounts.php', 'constrain', 'continue');

/* Businness Unit - start */
SDK::setAdvancedQuery($module, "queryAccounts", $accountsdir.'QueryAccounts.php');
SDK::setAdvancedPermissionFunction($module, "permAccounts", $accountsdir.'PermissionAccounts.php');
$fields = array();
$fields[] = array('module'=>'Accounts','block'=>'LBL_ACCOUNT_INFORMATION','name'=>'business_unit','label'=>'Business Unit','uitype'=>'33','picklist'=>array('BU 1','BU 2','BU 3'));
$fields[] = array('module'=>'Users','block'=>'LBL_USERLOGIN_ROLE','name'=>'business_unit','label'=>'Business Unit','uitype'=>'33','picklist'=>array('BU 1','BU 2','BU 3'));
include('modules/SDK/examples/fieldCreate.php');
/* Businness Unit - end */

/* Popup - start */
$campaignInstance = Vtecrm_Module::getInstance('Campaigns');
$block = new Vtecrm_Block();
$block->label = 'LBL_COURSE_INFORMATION';
$block->save($campaignInstance);

$fields = array();
$fields[] = array('module'=>'Campaigns','block'=>'LBL_COURSE_INFORMATION','name'=>'teacher','label'=>'Teacher','uitype'=>'10','columntype'=>'INT(19)','typeofdata'=>'I~O','relatedModules'=>array('Contacts'));
$fields[] = array('module'=>'Campaigns','block'=>'LBL_COURSE_INFORMATION','name'=>'spokesman','label'=>'Spokesman','uitype'=>'10','columntype'=>'INT(19)','typeofdata'=>'I~O','relatedModules'=>array('Contacts'));
$fields[] = array('module'=>'Campaigns','block'=>'LBL_COURSE_INFORMATION','name'=>'seat','label'=>'Seat','uitype'=>'10','columntype'=>'INT(19)','typeofdata'=>'I~O','relatedModules'=>array('Accounts'));
$fields[] = array('module'=>'Campaigns','block'=>'LBL_COURSE_INFORMATION','name'=>'course_address','label'=>'Course Address','uitype'=>'1');
$fields[] = array('module'=>'Contacts','block'=>'LBL_CUSTOM_INFORMATION','name'=>'function','label'=>'Function','uitype'=>'15','picklist'=>array('--None--','Teacher','Spokesman'));
include('modules/SDK/examples/fieldCreate.php');

SDK::setLanguageEntries('Campaigns', 'Teacher', array('it_it'=>'Docente','en_us'=>'Teacher'));
SDK::setLanguageEntries('Campaigns', 'Spokesman', array('it_it'=>'Relatore','en_us'=>'Spokesman'));
SDK::setLanguageEntries('Campaigns', 'LBL_COURSE_INFORMATION', array('it_it'=>'Informazioni Corso','en_us'=>'Course Informations'));
SDK::setLanguageEntries('Campaigns', 'Seat', array('it_it'=>'Sede','en_us'=>'Seat'));
SDK::setLanguageEntries('Campaigns', 'Course Address', array('it_it'=>'Indirizzo Corso','en_us'=>'Course Address'));
SDK::setLanguageEntries('Contacts', 'Function', array('it_it'=>'Mansione','en_us'=>'Function'));
SDK::setLanguageEntries('Contacts', 'Teacher', array('it_it'=>'Docente','en_us'=>'Teacher'));
SDK::setLanguageEntries('Contacts', 'Spokesman', array('it_it'=>'Relatore','en_us'=>'Spokesman'));

SDK::setPopupQuery('field', 'Campaigns', 'teacher', 'modules/SDK/examples/Campaigns/TeacherQuery.php');
SDK::setPopupQuery('field', 'Campaigns', 'spokesman', 'modules/SDK/examples/Campaigns/SpokesmanQuery.php');

SDK::setPopupQuery('related', 'Contacts', 'Products', 'modules/SDK/examples/Contacts/ProductsQuery.php');

Vtecrm_Link::addLink($moduleInstance->id, 'HEADERSCRIPT', 'SeatToCampaign', 'modules/SDK/examples/Campaigns/SeatToCampaign.js');
SDK::setExtraSrc('Campaigns', 'modules/SDK/examples/Campaigns/SeatToCampaign.js');
SDK::setPopupReturnFunction('Campaigns', 'seat', 'modules/SDK/examples/Campaigns/SeatToCampaign.php');
/* Popup - end */

/* Smarty - start */
// If industry is equal to 'Hospitaity', shows an extra block with hospital description
// in detail, create and edit view
$healthInstance = new Vtecrm_Block();
$healthInstance->label = 'LBL_HEALTH_INFORMATION';
$accountsInstance = Vtecrm_Module::getInstance($module);
$accountsInstance->addBlock($healthInstance);
$fields = array();
$fields[] = array(
	'module'=>$module,
	'block'=>'LBL_HEALTH_INFORMATION',
	'name'=>'hdescription',
	'label'=>'hdescription',
	'uitype'=>'19',
	'columntype'=>'C(200)',
	'typeofdata'=>'V~O'
);
include('modules/SDK/examples/fieldCreate.php');
SDK::setLanguageEntry($module, 'it_it', 'LBL_HEALTH_INFORMATION', 'Informazioni ospedale');
SDK::setLanguageEntry($module, 'en_us', 'LBL_HEALTH_INFORMATION', 'Hospital Informations');
SDK::setLanguageEntry($module, 'it_it', 'hdescription', 'Descrizione ospedale');
SDK::setLanguageEntry($module, 'en_us', 'hdescription', 'Hospital description');
SDK::setSmartyTemplate(array('module'=>$module,'action'=>'DetailView','record'=>'$NOTNULL$'),$accountsdir.'DetailView.tpl');
SDK::setSmartyTemplate(array('module'=>$module,'action'=>'EditView','record'=>'$NOTNULL$'),$accountsdir.'EditView.tpl');
SDK::setSmartyTemplate(array('module'=>$module,'action'=>'EditView'),$accountsdir.'CreateView.tpl');
SDK::setSmartyTemplate(array('module'=>$module,'action'=>'EditView','record'=>'$NOTNULL$','isDuplicate'=>'true'),$accountsdir.'CreateView.tpl');
/* Smarty - end */

/* set up intellisense */
/* create the new field */
$fields = array();
$fields[] = array(
	'module'=>$module,
	'block'=>'LBL_CUSTOM_INFORMATION',
	'name'=>'voto',
	'label'=>'Voto',
	'uitype'=>'15',
	'sdk_uitype'=>1115,
	'columntype'=>'C(100)',
	'typeofdata'=>'V~O',
	'picklist'=>array('Eccellente', 'Ottimo', 'Buono', 'Discreto', 'Pessimo')
);
include('fieldCreate.php');
/* register it */
SDK::setUitype(1115,$exdir.'intellisense/1115.php',$exdir.'intellisense/1115.tpl','','picklist');
Vtecrm_Link::addLink($moduleInstance->id,'HEADERSCRIPT','IntellisenseScript',$exdir.'intellisense/search_engine_script/jquery.bgiframe.pack.js');
Vtecrm_Link::addLink($moduleInstance->id,'HEADERSCRIPT','IntellisenseScript',$exdir.'intellisense/search_engine_script/jquery.watermarkinput.js');
Vtecrm_Link::addLink($moduleInstance->id,'HEADERSCRIPT','IntellisenseScript',$exdir.'intellisense/search_engine_script/autosuggest/bsn.AutoSuggest_2.1.3_comp.js');
Vtecrm_Link::addLink($moduleInstance->id,'HEADERCSS','IntellisenseCss',$exdir.'intellisense/search_engine_script/autosuggest/autosuggest_inquisitor.css');
SDK::setExtraSrc($module, $exdir.'intellisense/');

/* setup basic uitypes */
$uidir = $exdir.'uitypeSocial/';
/* all the uitypes social */ 
/* uitypes: 170, 171, 172, 173, 174, 175, 176, 177 */
$uitypes = array(170=>'Facebook', 171=>'LinkedIn');
$fields = array();
foreach ($uitypes as $uit=>$uitname) {
	$fields[] = array(
		'module'=>$module,
		'block'=>'LBL_CUSTOM_INFORMATION',
		'name'=>$uitname,
		'label'=>$uitname,
		'uitype'=>'1',
		'sdk_uitype'=>$uit,
		'columntype'=>'C(100)',
		'typeofdata'=>'V~O',
	);
}
include('fieldCreate.php');
foreach ($uitypes as $uit=>$uitname) {
	SDK::setUitype($uit,$uidir.strval($uit).'.php',$uidir.strval($uit).'.tpl',$uidir.strval($uit).'.js');
}

/* setup linked picklists */
$typeid = 300;
$pldir = $exdir.'uitypePicklist/';
require_once($pldir.'300Utils.php');
$fields = array();
$fields[] = array(
	'module'=>$module,
	'block'=>'LBL_CUSTOM_INFORMATION',
	'name'=>'nazione',
	'label'=>'Nazione',
	'uitype'=>'15',
	'sdk_uitype'=>$typeid,
	'columntype'=>'C(100)',
	'typeofdata'=>'V~O',
	'picklist'=>array('Italia', 'Francia', 'Germania')
);
$fields[] = array(
	'module'=>$module,
	'block'=>'LBL_CUSTOM_INFORMATION',
	'name'=>'regione',
	'label'=>'Regione',
	'uitype'=>'15',
	'sdk_uitype'=>$typeid,
	'columntype'=>'C(100)',
	'typeofdata'=>'V~O',
	'picklist'=>array('Veneto', 'Lazio', 'Bretagna', 'Alsazia', 'Baviera', 'Sassonia')
);
$fields[] = array(
	'module'=>$module,
	'block'=>'LBL_CUSTOM_INFORMATION',
	'name'=>'citta',
	'label'=>'Citta',
	'uitype'=>'15',
	'sdk_uitype'=>$typeid,
	'columntype'=>'C(100)',
	'typeofdata'=>'V~O',
	'picklist'=>array('Verona', 'Padova', 'Venezia', 'Roma', 'Rennes', 'Brest', 'Strasburgo', 'Monaco', 'Norimberga', 'Dresda')
);
include('fieldCreate.php');
SDK::setUitype($typeid,$pldir.strval($typeid).'.php',$pldir.strval($typeid).'.tpl',$pldir.strval($typeid).'.js');
Vtecrm_Link::addLink($moduleInstance->id,'HEADERSCRIPT','SDKUitype',$pldir.'300Utils.js');
linkedListAddLink('nazione', 'regione', $module, 'Italia', array('Veneto', 'Lazio'));
linkedListAddLink('nazione', 'regione', $module, 'Francia', array('Bretagna', 'Alsazia'));
linkedListAddLink('nazione', 'regione', $module, 'Germania', array('Baviera', 'Sassonia'));
linkedListAddLink('regione', 'citta', $module, 'Veneto', array('Verona', 'Padova', 'Venezia'));
linkedListAddLink('regione', 'citta', $module, 'Lazio', array('Roma'));
linkedListAddLink('regione', 'citta', $module, 'Bretagna', array('Rennes', 'Brest'));
linkedListAddLink('regione', 'citta', $module, 'Alsazia', array('Strasburgo'));
linkedListAddLink('regione', 'citta', $module, 'Baviera', array('Monaco', 'Norimberga'));
linkedListAddLink('regione', 'citta', $module, 'Sassonia', array('Dresda'));
SDK::setExtraSrc($module, $pldir);

//Report Custom
SDK::setReportFolder('ReportsSDK', 'Reports SDK');

// basic report with hand-written html (no column search, column ordering, pagination...)
SDK::setReport('Aziende con sito', 'Aziende con sito', 'ReportsSDK', 'modules/SDK/examples/Reports/ReportRunAccounts.php', 'ReportRunAccounts');

// advanced report with custom query but all the standard functionalities
SDK::setReport('Aziende e preventivi', 'Aziende e preventivi', 'ReportsSDK', 'modules/SDK/examples/Reports/ReportRunAccQuotes.php', 'ReportRunAccQuotes'); // crmv@172034


$hidden = array('accountid'=>'getObj("accountid").value');
SDK::setPopupQuery('field', 'Ddt', 'salesorderid', 'modules/SDK/examples/Ddt/QuerySalesOrder.php', $hidden);

SDK::setPreSave('Ddt', 'modules/SDK/examples/Ddt/PreSaveDdt.php');

SDK::setHomeIframe(2, 'http://www.ilmeteo.it/portale/meteo/previsioni.php?citta=San+Bonifacio&c=6132&g=7', 'Meteo San Bonifacio (VR)', null, true);

//Process Manager
// popolamento/aggiornamento campi
SDK::setProcessMakerFieldAction('vte_sum','modules/SDK/src/ProcessMaker/Utils.php','Sum (number1,number2,...)');
SDK::setProcessMakerFieldAction('vte_calculate_percentage','modules/SDK/src/ProcessMaker/Utils.php','Calculate percentage (percentage,total)');
SDK::setProcessMakerFieldAction('vte_calculate_table_total','modules/SDK/src/ProcessMaker/Utils.php','Calculate table total (table_fieldname,price_fieldname,quantity_fieldname[,metaid])');

// condizione (BPMN-Task)
// es. verifico nel modulo Accounts se l'indirizzo di fatturazione è uguale a quello di spedizione
SDK::setProcessMakerTaskCondition('vte_compare_account_bill_ship_street', 'modules/SDK/src/ProcessMaker/Utils.php', 'Indirizzi di spedizione e fatturazione uguali [e/n]');

// nuova azione sdk
// la funzione php che scrivo nel file indicato avrà i parametri: $engine, $actionid. Il primo è il solito oggetto che contiene le informazioni relative al processo in corso mentre il secondo è l'id dell'azione in quel ScriptTask.
// come descritto nella funzione vte_calculate_percentage puoi usare $engine->getCrmid() per recuperare l'id dei record del processo e quindi usarli nelle tue query di lettura / scrittura
// es. chiude tutti i ticket collegati alla prima entità del processo
SDK::setProcessMakerAction('close_tickets', 'modules/SDK/src/ProcessMaker/Utils.php', 'Chiudi Ticket relazionati');

// nuovo metodo ws rest che verifica l'esistenza di un record
SDK::setRestOperation('check_exists', 'modules/SDK/examples/RestApi/CustomRestApi.php', 'vtws_check_exists', array('id'=>'string'));