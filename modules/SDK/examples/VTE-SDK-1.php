<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
die('Remove die!');

include_once('../../../config.inc.php');
chdir($root_directory);
require_once('include/utils/utils.php');
global $adb, $table_prefix;
VteSession::start();

// Turn on debugging level
include_once('vtlib/Vtecrm/Menu.php');
include_once('vtlib/Vtecrm/Module.php');
$vtlib_Utils_Log = true;

// Create module instance and save it first
$module = new Vtecrm_Module();
$module->name = 'InstalledBase';
$module->save();

// Initialize all the tables required
$module->initTables();
/**
* Creates the following table:
* vte_installedbase (installedbaseid INTEGER)
* vte_installedbasecf(installedbaseid INTEGER PRIMARY KEY)
*/

// Add the module to the Menu (entry point from UI)
$menu = Vtecrm_Menu::getInstance('Inventory');
$menu->addModule($module);

// Add the basic module block
$block1 = new Vtecrm_Block();
$block1->label = 'LBL_INSTALLEDBASE_INFORMATION';
$module->addBlock($block1);

// Add custom block (required to support Custom Fields)
$block2 = new Vtecrm_Block();
$block2->label = 'LBL_CUSTOM_INFORMATION';
$module->addBlock($block2);

// Add description block (required to support Description)
$block3 = new Vtecrm_Block();
$block3->label = 'LBL_DESCRIPTION_INFORMATION';
$module->addBlock($block3);

/** Create required fields and add to the block */
$field1 = new Vtecrm_Field();
$field1->name = 'installedbase_name';
$field1->table = $module->basetable;
$field1->label= 'InstalledBase Name';
$field1->columntype = 'C(255)';
$field1->uitype = 1;
$field1->typeofdata = 'V~M';
$field1->quickcreate = 0;
$block1->addField($field1); 

// Set at-least one field to identifier of module record
$module->setEntityIdentifier($field1);

$field2 = new Vtecrm_Field();
$field2->name = 'description';
$field2->table = $table_prefix.'_crmentity';
$field2->label = 'Description';
$field2->uitype = 19;
$field2->typeofdata = 'V~O';// Varchar~Optional
$block3->addField($field2); /** table and column are automatically set */
//$field2->setPicklistValues( Array ('Employee', 'Trainee') );

$field3 = new Vtecrm_Field();
$field3->name = 'installation_period';
$field3->table = $module->basetable;
$field3->label = 'Installation Period';
$field3->uitype = 5;
$field3->columntype = 'D';
$field3->typeofdata = 'D~O'; // Date~Optional
$block1->addField($field3); 

$field4 = new Vtecrm_Field();
$field4->name = 'edit_period';
$field4->table = $module->basetable;
$field4->label = 'Edit Period';
$field4->uitype = 5;
$field4->columntype = 'D';
$field4->typeofdata = 'D~O'; // Date~Optional
$block1->addField($field4); 

/** table, column, label, set to default values */

$field5 = new Vtecrm_Field();
$field5->name = 'active';
$field5->label= 'Active';
$field5->table = $module->basetable;
$field5->column = 'active';
$field5->columntype = 'I(1)';
$field5->uitype = 56;
$field5->typeofdata = 'C~O';
$field5->quickcreate = 0;
$block1->addField($field5);
//$field4->setRelatedModules(Array('Contacts'));


$field6 = new Vtecrm_Field();
$field6->name = 'installation_state';//vte_installation_state
$field6->table = $module->basetable;
$field6->label = 'Installation State';
$field6->uitype = 15;
$field6->columntype = 'C(255)';
$field6->typeofdata = 'V~O';// Varchar~Optional
$block1->addField($field6); /** table and column are automatically set */
$field6->setPicklistValues( Array ('-- Nessuno -- ', 'In corso di evasione', 'Evasa', 'Fatturata') );

$field7 = new Vtecrm_Field();
$field7->name = 'account_id';
$field7->table = $module->basetable;
$field7->label= 'Account Id';
$field7->column = 'accountid';
$field7->uitype = 10;
$field7->columntype = 'I(19)';
$field7->typeofdata = 'I~O';
$field7->displaytype= 1;
$field7->helpinfo = 'Relate to an existing account';
$field7->quickcreate = 0;
$block1->addField($field7);
$field7->setRelatedModules(Array('Accounts'));

$field11 = new Vtecrm_Field();
$field11->name = 'responsabile';
$field11->table = $module->basetable;
$field11->label= 'Responsabile';
$field11->column = 'responsabile';
$field11->uitype = 77;
$field11->columntype = 'I(19)';
$field11->typeofdata = 'V~O';
$block1->addField($field11);

/** Common fields that should be in every module, linked to VTECRM core table */

$field8 = new Vtecrm_Field();
$field8->name = 'assigned_user_id';
$field8->label = 'Assigned To';
$field8->table = $table_prefix.'_crmentity';
$field8->column = 'smownerid';
$field8->uitype = 53;
$field8->typeofdata = 'V~M';
$field8->quickcreate = 0;
$block1->addField($field8);

$field9 = new Vtecrm_Field();
$field9->name = 'createdtime';
$field9->label= 'Created Time';
$field9->table = $table_prefix.'_crmentity';
$field9->column = 'createdtime';
$field9->uitype = 70;
$field9->typeofdata = 'T~O';
$field9->displaytype= 2;
$block1->addField($field9);

$field10 = new Vtecrm_Field();
$field10->name = 'modifiedtime';
$field10->label= 'Modified Time';
$field10->table = $table_prefix.'_crmentity';
$field10->column = 'modifiedtime';
$field10->uitype = 70;
$field10->typeofdata = 'T~O';
$field10->displaytype= 2;
$block1->addField($field10);

/** END */

// Create default custom filter (mandatory)
$filter1 = new Vtecrm_Filter();
$filter1->name = 'All';
$filter1->isdefault = true;
$module->addFilter($filter1);

// Add fields to the filter created
$filter1->addField($field1,1)->addField($field3,2)->addField($field5,3)->addField($field6,4)->addField($field7,5)->addField($field8,6);

// Create one more filter
$filter2 = new Vtecrm_Filter();
$filter2->name = 'Active Installation';
$module->addFilter($filter2);

// Add fields to the filter
$filter2->addField($field1);
$filter2->addField($field1,1)->addField($field3,2)->addField($field5,3)->addField($field6,4)->addField($field7,5)->addField($field8,6);

// Add rule to the filter field
$filter2->addRule($field5, 'EQUALS', '1');

/** Associate other modules to this module */
//$module->setRelatedList(Vtecrm_Module::getInstance('SalesOrder'), 'SalesOrder', Array('ADD'));
//$module->setRelatedList(Vtecrm_Module::getInstance('Potentials'), 'Potentials', Array('ADD'));

//get_dependents_list -> 1 -> N
//get_related_list -> N -> N

//relazione n a n
$module->setRelatedList(Vtecrm_Module::getInstance('Products'), 'Products', Array('SELECT'), 'get_related_list');
$module->setRelatedList(Vtecrm_Module::getInstance('ServiceContracts'), 'Service Contracts', Array('ADD','SELECT'));
$module->setRelatedList(Vtecrm_Module::getInstance('PurchaseOrder'), 'PurchaseOrder', Array('ADD'));

$products = Vtecrm_Module::getInstance('Products');
$products->setRelatedList(Vtecrm_Module::getInstance('InstalledBase'), 'Installed Base', Array('SELECT'));

$servicec = Vtecrm_Module::getInstance('ServiceContracts');
$servicec->setRelatedList(Vtecrm_Module::getInstance('InstalledBase'), 'Installed Base', Array('SELECT'));

$porder = Vtecrm_Module::getInstance('PurchaseOrder');
$porder->setRelatedList(Vtecrm_Module::getInstance('InstalledBase'), 'Installed Base', Array('SELECT'));

//relazione 1 a n
$accounts = Vtecrm_Module::getInstance('Accounts');
$accounts->setRelatedList(Vtecrm_Module::getInstance('InstalledBase'), 'Installed Base', Array('SELECT'), 'get_dependents_list');

/** Set sharing access of this module */
$module->setDefaultSharing('Private');

/** Enable and Disable available tools */
$module->enableTools(Array('Import', 'Export'));
$module->disableTools('Merge'); 

// per aggiungere il supporto ai webservices
$module->initWebservice();

Vtecrm_Module::fireEvent($module->name, Vtecrm_Module::EVENT_MODULE_POSTINSTALL);
?>