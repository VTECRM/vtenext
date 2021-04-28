<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@146671 crmv@147433 */

require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
require_once('modules/Settings/ExtWSConfig/ExtWSUtils.php');

$processid = $_REQUEST['id'];
$elementid = $_REQUEST['elementid'];
$action_id = $_REQUEST['action_id'];
$extwsid = intval($_REQUEST['extwsid']);

$PMUtils = ProcessMakerUtils::getInstance();
$EWSU = new ExtWSUtils();

$wsinfo = $EWSU->getWSInfo($extwsid);

$authFields = $wsinfo['authinfo'] ?: array(); //crmv@OPER10174
$paramFields = $wsinfo['params'];
$resultFields = $wsinfo['results'];

$smarty = new VteSmarty();

// start the output
//crmv@160843 codes removed
$involvedRecords = $PMUtils->getRecordsInvolved($processid,true);
if (!empty($involvedRecords)) {
	echo Zend_Json::encode($involvedRecords);
}
echo '|&|&|&|';

$wsinfoJs = $wsinfo;
unset($wsinfoJs['authinfo']);
echo Zend_Json::encode($wsinfoJs);
echo '|&|&|&|';

if ($action_id != '') {
	$metadata = $PMUtils->getMetadata($processid,$elementid);
	$metadata_action = $metadata['actions'][$action_id];
}
if (!empty($metadata_action)) echo Zend_Json::encode($metadata_action);
echo '|&|&|&|';

//crmv@106857
$processDynaFormObj = ProcessDynaForm::getInstance();
$otherOptions = $processDynaFormObj->getFieldsOptions($processid,true);
$PMUtils->getAllTableFieldsOptions($processid, $otherOptions);
$PMUtils->getAllPBlockFieldsOptions($processid, $otherOptions); // crmv@195745
if (!empty($otherOptions)) echo Zend_Json::encode($otherOptions);
echo '|&|&|&|';
//crmv@106857e

//crmv@100591
$elementsActors = $PMUtils->getElementsActors($processid);
if (!empty($elementsActors)) echo Zend_Json::encode($elementsActors);
echo '|&|&|&|';
//crmv@100591e

$extwsOptions = $PMUtils->getExtWSFields($processid, $elementid);
if (!empty($extwsOptions)) echo Zend_Json::encode($extwsOptions);
echo '|&|&|&|';

$wsfields = array(
	'auth' => array(),
	'has_auth' => (($authFields && $authFields['username']) || $metadata_action['auth_username']),
	'params' => array(),
	'rawbody' => $metadata_action['rawbody'] ?: $wsinfo['rawbody'], // crmv@190014
	'results' => array(),
);

// auth fields are always present
$wsfields['auth'][0][] = array(
	array(1),						// uitype
	array(getTranslatedString('LBL_USERNAME', 'Settings')),			// fieldlabel
	array('auth_username'),			// fieldname
	array($metadata_action['auth_username'] ?: $authFields['username']),	// fieldvalue
	$wsfields['has_auth'] ? 99 : 1,	// readonly
	'V~O',							// type of data
	null,							// is_admin
	1,								// fieldid
);
//crmv@182561
$wsfields['auth'][0][] = array(
	array(((empty($metadata_action['auth_password']) && $wsfields['has_auth']) || $metadata_action['auth_password'] == $authFields['password']) ? 99 : 1),	// uitype
	array(getTranslatedString('LBL_LIST_PASSWORD', 'Settings')),			// fieldlabel
	array('auth_password'),			// fieldname
	array(!empty($metadata_action['auth_password']) ? $metadata_action['auth_password'] : $authFields['password']), // fieldvalue
	1,								// readonly
	'V~O',							// type of data
	null,							// is_admin
	1,								// fieldid
);
//crmv@182561e

$editoptionsfieldnames = array('auth_username', 'auth_password', 'param_name', 'param_value', 'rawbody', 'result_value'); // crmv@190014
$wsinitfields = array(); //crmv@OPER10174

// add other fields
foreach ($paramFields as $k => $pinfo) {
	$wsinitfields[] = array('param_'.$k,$pinfo['name'],$metadata_action['params'][$pinfo['name']]); //crmv@OPER10174
	$wsfields['params'][0][] = array(
		array(1),						// uitype
		array($pinfo['name']),			// fieldlabel
		array('param_'.$k),				// fieldname
		array($pinfo['value']),			// fieldvalue
		99,								// readonly
		'V~O',							// type of data
		null,							// is_admin
		1,								// fieldid
	);
	$editoptionsfieldnames[] = 'param_'.$k;
	// hidden field with the real field name
	$wsfields['params'][0][] = array(
		array(1),						// uitype
		array($pinfo['name']),			// fieldlabel
		array('paramname_'.$k),			// fieldname
		array($pinfo['name']),			// fieldvalue
		100,							// readonly
		'V~O',							// type of data
		null,							// is_admin
		1,								// fieldid
	);
}

foreach ($resultFields as $k=>$pinfo) {
	$wsinitfields[] = array('result_'.$k,$pinfo['name'],$metadata_action['results'][$pinfo['name']]); //crmv@OPER10174
	$wsfields['results'][0][] = array(
		array(1),						// uitype
		array($pinfo['name']),			// fieldlabel
		array('result_'.$k),			// fieldname
		array($pinfo['value']),			// fieldvalue
		99,								// readonly
		'V~O',							// type of data
		null,							// is_admin
		1,								// fieldid
	);
	$editoptionsfieldnames[] = 'result_'.$k;
	// hidden field with the real field name
	$wsfields['params'][0][] = array(
		array(1),						// uitype
		array($pinfo['name']),			// fieldlabel
		array('resultname_'.$k),		// fieldname
		array($pinfo['name']),			// fieldvalue
		100,							// readonly
		'V~O',							// type of data
		null,							// is_admin
		1,								// fieldid
	);
}

//crmv@OPER10174
if (!empty($wsinitfields)) echo Zend_Json::encode($wsinitfields);
echo '|&|&|&|';
//crmv@OPER10174e

// drug the _REQUEST
$_REQUEST['enable_editoptions'] = 'yes';
$_REQUEST['editoptionsfieldnames'] = implode('|',$editoptionsfieldnames);

$smarty->assign('WSFIELDS', $wsfields);
$smarty->assign('MODE', 'create'); //crmv@182561

$smarty->display('Settings/ProcessMaker/actions/CallExtWSForm.tpl');