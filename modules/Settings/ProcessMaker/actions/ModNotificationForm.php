<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@183346 */

require_once('modules/Popup/Popup.php');
require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');

global $adb, $table_prefix;
global $mod_strings, $app_strings, $theme;
global $currentModule, $current_user, $current_language;

$processid = $_REQUEST['id'];
$elementid = $_REQUEST['elementid'];
$action_id = $_REQUEST['action_id'];

$mod = str_replace('.', '', vtlib_purify($_REQUEST['mod']));
$mod_strings = return_module_language($current_language, $mod);

$PMUtils = ProcessMakerUtils::getInstance();
//crmv@160843 codes removed
$involvedRecords = $PMUtils->getRecordsInvolved($processid,true);
if (!empty($involvedRecords)) {
	echo Zend_Json::encode($involvedRecords);
}
echo '|&|&|&|';

if ($action_id != '') {
	$metadata = $PMUtils->getMetadata($processid,$elementid);
	$metadata_action = $metadata['actions'][$action_id];
	$metadata_form = $metadata_action['form'];
	if (!empty($metadata_form)) {
		foreach($metadata_form as $name => $value) {
			$_REQUEST[$name] = $value;
			//crmv@113527
			if (isset($metadata_action['sdk_params'][$name])) {
				$_REQUEST['sdk_params_'.$name] = $metadata_action['sdk_params'][$name];
			}
			//crmv@113527e
		}
	}
}
if (!empty($metadata_form)) echo Zend_Json::encode($metadata_form);
echo '|&|&|&|';

$i = 0;
$picklist_values = array();
$reference_values = array(
	'related_to' => $metadata_form['related_to'],
);
$reference_users_values = array();
$boolean_values = array();
$date_values = array();

if (!empty($picklist_values)) echo Zend_Json::encode($picklist_values);
echo '|&|&|&|';
if (!empty($reference_values)) echo Zend_Json::encode($reference_values);
echo '|&|&|&|';
if (!empty($reference_users_values)) echo Zend_Json::encode($reference_users_values);
echo '|&|&|&|';
if (!empty($boolean_values)) echo Zend_Json::encode($boolean_values);
echo '|&|&|&|';
if (!empty($date_values)) echo Zend_Json::encode($date_values);
echo '|&|&|&|';

// crmv@102879	crmv@106857
$otherOptions = array();
$processDynaFormObj = ProcessDynaForm::getInstance();
$otherOptions = $processDynaFormObj->getFieldsOptions($processid,true);
$PMUtils->getAllTableFieldsOptions($processid, $otherOptions);
//crmv@203075 dont add prodblocks to action cycle related records
if($_REQUEST['cycle_fieldname'] == '')
    $PMUtils->getAllPBlockFieldsOptions($processid, $otherOptions); // crmv@195745
//crmv@203075e
if (!empty($otherOptions)) echo Zend_Json::encode($otherOptions);
echo '|&|&|&|';
// crmv@102879e	crmv@106857e

//crmv@100591
$elementsActors = $PMUtils->getElementsActors($processid);
if (!empty($elementsActors)) echo Zend_Json::encode($elementsActors);
echo '|&|&|&|';
//crmv@100591e

// crmv@146671
$extwsOptions = $PMUtils->getExtWSFields($processid);
if (!empty($extwsOptions)) echo Zend_Json::encode($extwsOptions);
echo '|&|&|&|';
// crmv@146671e