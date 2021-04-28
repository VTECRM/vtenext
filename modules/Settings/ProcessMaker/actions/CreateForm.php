<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@42752 crmv@43050 crmv@43448 crmv@43864 crmv@92272 crmv@96450 crmv@108227 */

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
$tabid = getTabid($mod);
if (in_array($mod,array('Calendar','Events'))) {
	($mod == 'Calendar') ? $_REQUEST['activity_mode'] = 'Task' : $_REQUEST['activity_mode'] = '';
	$mod = 'Calendar';
	$_REQUEST['hide_invite_tab'] = 1;
	$_REQUEST['hide_reminder_time'] = 1;
	$_REQUEST['hide_recurringtype'] = 1;
	$_REQUEST['hide_reference_contact_field'] = 1;
}
$mod_strings = return_module_language($current_language, $mod);

$PMUtils = ProcessMakerUtils::getInstance();
//crmv@160843 codes removed
$involvedRecords = $PMUtils->getRecordsInvolved($processid,true);
if (!empty($involvedRecords)) {
	echo Zend_Json::encode($involvedRecords);
}
echo '|&|&|&|';

unset($_REQUEST['record']);
$_REQUEST['module'] = $currentModule = $module = $mod;
$_REQUEST['action'] = 'EditView';
$_REQUEST['hide_button_list'] = 1;
$_REQUEST['skip_sdk_view'] = 1;
$_REQUEST['disable_conditionals'] = 1;

$label_back = ($_REQUEST['popup_mode'] == 'onlycreate' ? getTranslatedString('LBL_CANCEL_BUTTON_LABEL') : getTranslatedString('LBL_BACK'));
($_REQUEST['show_create_note'] == 'yes') ? $notes = sprintf(getTranslatedString('LBL_POPUP_RECORDS_NOT_SELECTABLE'),getTranslatedString($currentModule,$currentModule)) : $notes = '';	//crmv@46678

$_REQUEST['enable_editoptions'] = 'yes';
$editoptionsfieldnames = array();
$result = $adb->pquery("select fieldname, uitype from {$table_prefix}_field where tabid = ?",array($tabid));
if ($result && $adb->num_rows($result) > 0) {
	while($row=$adb->fetchByASsoc($result,-1,false)) {
		if (in_array($row['uitype'],$PMUtils->editoptions_uitypes_not_supported)) continue;	// TODO fare il check per webservice type
		$editoptionsfieldnames[] = $row['fieldname'];
	}
}
$_REQUEST['editoptionsfieldnames'] = implode('|',$editoptionsfieldnames);

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
$reference_values = array();
$reference_users_values = array();	// ex. uitypes 52
$boolean_values = array();
$date_values = array();
$result = $adb->pquery("select * from {$table_prefix}_field where tabid=?",array($tabid));
while($row=$adb->fetchByASsoc($result,-1,false)) {
	$field = WebserviceField::fromQueryResult($adb,$result,$i);
	if ($field->getFieldDataType() == 'picklist') {
		$picklist_values[$row['fieldname']] = $metadata_form[$row['fieldname']];
	} elseif ($field->getFieldDataType() == 'reference' && in_array('Users',$field->getReferenceList())) {
		$reference_users_values[$row['fieldname']] = $metadata_form[$row['fieldname']];
	} elseif ($field->getFieldDataType() == 'reference') {
		$reference_values[$row['fieldname']] = $metadata_form[$row['fieldname']];
	} elseif ($field->getFieldDataType() == 'boolean') {
		$boolean_values[$row['fieldname']] = $metadata_form[$row['fieldname']];
	} elseif (in_array($field->getFieldDataType(),array('date','datetime','time')) || (in_array($tabid,array(9,16)) && in_array($row['fieldname'],array('time_start','time_end')))) {	//crmv@128159
		//crmv@120769
		if (is_array($tmp = Zend_Json::decode($metadata_form[$row['fieldname']])) && !empty($tmp['custom'])) {
			$tmp['custom'] = getDisplayDate(substr($tmp['custom'],0,10));
			$metadata_form[$row['fieldname']] = Zend_Json::encode($tmp);
		}
		$date_values[$row['fieldname']] = $metadata_form[$row['fieldname']];
		//crmv@120769e
	}
	$i++;
}
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

//crmv@sdk-18501
include_once('vtlib/Vtecrm/Link.php');
$hdrcustomlink_params = Array('MODULE'=>$currentModule);
$COMMONHDRLINKS = Vtecrm_Link::getAllByType($tabid, Array('HEADERSCRIPT'), $hdrcustomlink_params);
foreach ($COMMONHDRLINKS['HEADERSCRIPT'] as $HEADERSCRIPT) {
	echo  '<script type="text/javascript" src="'.$HEADERSCRIPT->linkurl.'"></script>';
}
//crmv@sdk-18501e

$smarty = new VteSmarty();
$smarty->assign("MODULE", $currentModule);
$smarty->display('modules/SDK/src/Reference/Autocomplete.tpl');

$sdk_custom_file = 'EditView';
if (isModuleInstalled('SDK')) {
    $tmp_sdk_custom_file = SDK::getFile($currentModule,$sdk_custom_file);
    if (!empty($tmp_sdk_custom_file)) {
    	$sdk_custom_file = $tmp_sdk_custom_file;
    }
}
// crmv@196940
if (file_exists("modules/$currentModule/$sdk_custom_file.php"))
	require("modules/$currentModule/$sdk_custom_file.php");
// crmv@196940e