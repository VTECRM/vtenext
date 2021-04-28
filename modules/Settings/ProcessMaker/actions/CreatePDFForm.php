<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@187729 */

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

$mode = $_REQUEST['mode'];

$PMUtils = ProcessMakerUtils::getInstance();
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
			if (isset($metadata_action['sdk_params'][$name])) {
				$_REQUEST['sdk_params_'.$name] = $metadata_action['sdk_params'][$name];
			}
		}
	}
}
if (!empty($metadata_form)) echo Zend_Json::encode($metadata_form);
echo '|&|&|&|';

$i = 0;
$picklist_values = array();
$reference_values = array(
	'related_to_entity' => $metadata_form['related_to_entity'],
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

$otherOptions = array();
$processDynaFormObj = ProcessDynaForm::getInstance();
$otherOptions = $processDynaFormObj->getFieldsOptions($processid,true);
$PMUtils->getAllTableFieldsOptions($processid, $otherOptions);
//crmv@203075 dont add prodblocks to action cycle related records
if($_REQUEST['cycle_fieldname'] == '')
//crmv@203075e
if (!empty($otherOptions)) echo Zend_Json::encode($otherOptions);
echo '|&|&|&|';

$elementsActors = $PMUtils->getElementsActors($processid);
if (!empty($elementsActors)) echo Zend_Json::encode($elementsActors);
echo '|&|&|&|';

$extwsOptions = $PMUtils->getExtWSFields($processid);
if (!empty($extwsOptions)) echo Zend_Json::encode($extwsOptions);
echo '|&|&|&|';