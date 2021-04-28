<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 - fix vari */
/* crmv@33097 - fix vari */
/* crmv@71388 - file upload */

global $login, $userId, $current_user, $currentModule;

$module = $_REQUEST['module'];
$recordid = intval($_REQUEST['record']);
$values = vtlib_purify($_REQUEST['values']);

if (!$login || empty($userId)) {
	echo 'Login Failed';
} elseif (in_array($module, $touchInst->excluded_modules)) {
	echo "Module not permitted";
} else {

	$currentModule = $module;

	$returnok = true;
	$returndata = null;
	$errormsg = '';

	$newRecord = Zend_Json::decode($values);

	// fix for calendar
	if ($module == 'Events') {
		$start = substr($newRecord['date_start'], 0, 10).'T'.substr($newRecord['time_start'], 0, 5).':00';
		$end = substr($newRecord['due_date'], 0, 10).'T'.substr($newRecord['time_end'], 0, 5).':00';
		$tsStart = DateTime::createFromFormat('Y-m-d\TH:i:s', $start);
		$tsEnd = DateTime::createFromFormat('Y-m-d\TH:i:s', $end);
		$diff = $tsEnd->getTimestamp() - $tsStart->getTimestamp();
		$delta_hours = floor($diff / 3600);
		$delta_min = floor($diff / 60) % 60;
		$newRecord['duration_hours'] = $delta_hours;
		$newRecord['duration_minutes'] = $delta_min;
	}
	
	require_once('modules/Touch/vtws/classes/UploadFile.class.php');
	$wsclass = new TouchUploadFile();

	if ($recordid > 0) {
		// UPDATE

		$wsrecordid = vtws_getWebserviceEntityId($module, $recordid);

		$wsname = 'retrieve';
		if (isInventoryModule($module)) $wsname = 'retrieveInventory';
		$response = wsRequest($userId,$wsname, array('id'=>$wsrecordid)	);

		if (!empty($response['error'])) {
			$returnok = false;
			$errormsg = $response['error']->message;
			//die();
		}

		$record = $response['result'];

		// other fix for stupid calendar
		if ($module == 'Events') {
			unset($record['contact_id']);
		}

		if (is_array($newRecord) && is_array($record)) {
			// aggiungo blocco prodotti se manca
			if (!array_key_exists('product_block', $record) && !empty($newRecord['product_block'])) $record['product_block'] = array();
			foreach ($newRecord as $fldname => $fldval) {
				if (array_key_exists($fldname, $record)) {
					// to prevent notes html content from being stripped out
					if ($module == 'Documents' && $fldname == 'notecontent') {
						$newRecord2 = Zend_Json::decode($_REQUEST['values']);
						if (is_array($newRecord2)) {
							$fldval = $newRecord2[$fldname];
						}
						unset($newRecord2);
					}
					$fldval = $touchInst->touch2Field($module, $fldname, $fldval);
					$record[$fldname] = $fldval;
				}
			}
		}

		if ($module == 'MyNotes' && !array_key_exists('assigned_user_id', $record)) {
			$record['assigned_user_id'] = $current_user->id;
		}
		
		$uploads = checkUploadedFile($newRecord, $record, $wsclass);

		$response = wsRequest($userId,'update', array('element'=>$record) );
		$returnok = ($response['success'] == 1);
		
		if ($returnok) {
			// and delete the uploads
			if ($uploads && count($uploads) > 0) {
				$wsclass->removeUploads($uploads);
			}
		}

	} else {
		// CREATE

		$updateRecord = array();
		if (is_array($newRecord)) {
			foreach ($newRecord as $fldname => $fldval) {
				$fldval = $touchInst->touch2Field($module, $fldname, $fldval);
				if ($fldval == '') continue;
				$updateRecord[$fldname] = $fldval;
			}
			// aggiunta campi per calendario
			if ($module == 'Calendar') {
				$updateRecord['activitytype'] = 'Task';
				$updateRecord['visibility'] = 'Standard';
			}

			if ($module == 'MyNotes' && !array_key_exists('assigned_user_id', $updateRecord)) {
				$updateRecord['assigned_user_id'] = $current_user->id;
			}
		}
		
		// add attachment uploaded from the app
		$uploads = checkUploadedFile($newRecord, $updateRecord, $wsclass);

		$response = wsRequest($userId,'create',
			array(
				'elementType'=>$module,
				'element'=>$updateRecord,
			)
		);
		//$record = $response['result'];

		// in caso di creazione ritorno il record appena creato
		if ($response['success'] === true) {
			list($modid, $recordid) = explode('x', $response['result']['id'], 2);
			
			// and delete the uploads
			if ($uploads && count($uploads) > 0) {
				$wsclass->removeUploads($uploads);
			}
			
			$focus = CRMEntity::getInstance($module);
			$focus->retrieve_entity_info($recordid, $module);
			$record = $focus->column_fields;
			foreach ($record as $fldname=>$fldvalue) {
				$record[$fldname] = $touchInst->field2Touch($module, $fldname, $fldvalue);
			}

			$record['crmid'] = $recordid;
			$returndata = $record;
		}

	}

	if ($response['success'] !== true) {
		$returnok = false;
		$errormsg = $response['error']->message;
	}

	echo Zend_Json::encode(array('success' => $returnok, 'result' => $returndata, 'error' => $errormsg));
}

function checkUploadedFile($inputValues, &$outputValues, $wsclass) {

	$uploads = array_filter(array_unique(explode(',', $inputValues['upload_ids'])), function($v) {
		return $v !== "" && $v >= 0;
	});
	
	if (count($uploads) > 0) {
		
		// add other uploaded files
		// retrieve the file information
		$list = $wsclass->getTouchUploadList($uploads);
		$base = 'storage/touch_uploads/';
		$_FILES = array();
		foreach ($list as $uinfo) {
			if (is_readable($base.$uinfo['path'])) {
				$_FILES['filename'] = array(
					'name' => $uinfo['realname'] ?: $uinfo['path'],
					'tmp_name' => $base.$uinfo['path'],
					'type' => $uinfo['filetype'],
					'size' => filesize($base.$uinfo['path'])
				);
				$_POST['copy_not_move'] = 'true';
				$outputValues['filelocationtype'] = 'I';
				unset($outputValues['upload_ids']);
				return $uploads;
				break;
			}
		}
	}
	return false;
}