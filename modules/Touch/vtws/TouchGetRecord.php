<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 - fix vari */
/* crmv@33097 */
global $login, $userId, $current_user, $currentModule;

$module = $_REQUEST['module'];
$recordid = intval($_REQUEST['record']);
$prodblock = intval($_REQUEST['forproductblock']);
$updaterecent = intval($_REQUEST['set_recent']);
$setSeen = intval($_REQUEST['set_seen']);

if (!$login || empty($userId)) {
	echo 'Login Failed';
} elseif (in_array($module, $touchInst->excluded_modules)) {
	echo "Module not permitted";
} else {

	$currentModule = $module;

	// lettura record esistente
	if ($recordid > 0) {
		$record = touchGetRecord($module, $recordid, $prodblock);

		// aggiorno i recenti
		if ($updaterecent) {
			require_once('data/Tracker.php');

			$trackMod = $module;
			if ($module == 'Events') $trackMod = 'Calendar';

			$focus = CRMEntity::getInstance($trackMod);
			$focus->track_view($current_user->id, $trackMod, $recordid);
		}

		if ($module == 'Messages') {
			if ($setSeen) {
				$focus = CRMEntity::getInstance($currentModule);
				$focus->id = $recordid;
				$focus->retrieve_entity_info($recordid, $currentModule);
				try {
					$focus->setFlag('seen',1);
				} catch (Exception $e) {
					// ignore server errors
				}
			}
			// check if there are related records
			$rm = RelationManager::getInstance();
			$excludedMods = array('ModComments');
			$relIds = $rm->getRelatedIds($module, $recordid, array(), $excludedMods);
			$record['has_related_ids'] = (count($relIds) > 0);

		} elseif ($module == 'Events') {
			// check if i am invited to this
			if (!$focus) $focus = CRMEntity::getInstance($trackMod);
			$record['am_i_invited'] = $focus->isUserInvited($recordid, $current_user->id);
			if ($record['am_i_invited']) {
				$record['invitation_answer'] = $focus->getUserInvitationAnswer($recordid, $current_user->id);
			}
		}

	// creazione nuovo record
	// carico i campi precompilati (per related e personalizzazioni varie)
	// funziona parzialmente, solo i campi visibili vengono considerati
	} else {

		if (!empty($_REQUEST['APP_DATA'])) {
			$appdata = Zend_Json::decode($_REQUEST['APP_DATA']);

			$parentid = intval($appdata['parent_id']);
			$parentmod = $appdata['parent_module'];

			if (!empty($parentid) && !empty($parentmod)) {

				// trovo il campo di collegamento e lo imposto
				$response = wsRequest($current_user->id, 'describe', array('elementType'=>$module));
				$fields = $response['result']['fields'];
				if (is_array($fields)) {
					foreach ($fields as $field) {
						$type = $field['type']['name'];
						if ($type == 'reference') {
							if (in_array($parentmod, $field['type']['refersTo'])) {
								$fieldname = $field['name'];
								// metto nella request
								$_REQUEST[$fieldname] = $parentid;
							}
						}
					}
				}

				// altri campi parent
				$_REQUEST['return_id'] = $parentid;
				$_REQUEST['parent_id'] = $parentid;
				$_REQUEST['RLparent_id'] = $parentid;
				$_REQUEST['return_module'] = $parentmod;
				$_REQUEST['RLreturn_module'] = $parentmod;
			}
			unset($_REQUEST['APP_DATA']);
		}

		unset($_REQUEST['password'], $_REQUEST['username']);

		// simulo una editview in creazione
		$_REQUEST['record'] = '';
		$_REQUEST['module'] = $module;
		$_REQUEST['mode'] = '';
		$_REQUEST['action'] = 'EditView';

		try {
			ob_start();
			$moduleFile = $module;
			if ($module == 'Events') $moduleFile = 'Calendar';
			include("modules/$moduleFile/EditView.php");
			$html = ob_get_clean();
			ob_end_clean();
		} catch (Exception $e) {
			$html = '';
		}

		// recupero i campi (INPUT)
		// TODO: fare anche picklist
		$lastp = -1;
		$record = array();
		while (($lastp = stripos($html, '<input ', $lastp+1)) !== false) {
			$endp = stripos($html, '>', $lastp+1);
			// crmv@71388 - checkbox support
			if ($endp !== false) {
				$input = substr($html, $lastp, $endp-$lastp+1);
				$fieldname = '';
				$fieldvalue = '';
				$inputtype = '';
				if (preg_match('/type=["\']([^"\']+)["\']/', $input, $matches)) {
					$inputtype = strtolower($matches[1]);
				}
				
				if (preg_match('/name=["\']([^"\']+)["\']/', $input, $matches)) {
					$fieldname = $matches[1];
				}
				if ($inputtype == 'checkbox') {
					if (stripos($input, 'checked') !== false) $fieldvalue = '1';
				} elseif (preg_match('/value=["\']([^"\']+)["\']/', $input, $matches)) {
					$fieldvalue = $matches[1];
				}
				if (!empty($fieldname) && !empty($fieldvalue)) {
					$record[$fieldname] = $touchInst->field2Touch($module, $fieldname, $fieldvalue);
				}
			} else {
				break;
			}
			// crmv@71388e
		}
		
		// crmv@71388
		// this should be done automatically, when the picklist will be retrieved automatically, for now, let's hardcode it here
		if ($module == 'Documents' || $module == 'Myfiles') {
			if (!isset($record['filelocationtype'])) {
				$record['filelocationtype'] = 'I';
			}
			if ($module == 'Myfiles') {
				$record['filestatus'] = '1';
			}
		}
		// crmv@71388e

		// recupero i campi (TEXTAREA)
		$lastp = -1;
		while (($lastp = stripos($html, '<textarea ', $lastp+1)) !== false) {
			$endp = stripos($html, '>', $lastp+1);
			if ($endp !== false) {
				$input = substr($html, $lastp, $endp-$lastp+1);
				$fieldname = '';
				$fieldvalue = '';
				if (preg_match('/ name=["\']([^"\']+)["\']/', $input, $matches)) {
					$fieldname = $matches[1];
				}

				// trovo il contenuto
				$endp2 = stripos($html, '</textarea>', $endp+1);
				if ($endp2 !== false) {
					$fieldvalue = substr($html, $endp+1, $endp2-$endp-1);
					if (!empty($fieldname) && !empty($fieldvalue)) {
						$record[$fieldname] = $touchInst->field2Touch($module, $fieldname, $fieldvalue);
					}
					$lastp = $endp2 + 5;
				}
			} else {
				break;
			}
		}

		// fix per todo e calendario
		if ($module == 'Calendar' || $module == 'Events') {
			// round to 5 min
			$min = floor(intval(date('i')) / 5) * 5;
			$record['time_start'] = date('H').':'.str_pad($min, 2, '0', STR_PAD_LEFT);
			// 30 min later
			$timestampEnd = time()+60*30;
			$min = floor(date('i', $timestampEnd) / 5) * 5;
			$record['time_end'] = date('H', $timestampEnd).':'.str_pad($min, 2, '0', STR_PAD_LEFT);
		}

		// crmv@68320
		if (empty($record['assigned_user_id'])) {
			$record['assigned_user_id'] = strval($current_user->id);
			$record['assigned_user_id_display'] = $current_user->user_name;
		}
		// crmv@68320e


		/*
		// questa strada, sebbene elegante, non è praticabile, le pagine sono piene di errori html
		if (class_exists('DOMDocument')) {
			$pageDom = new DOMDocument();
			$pageDom->recover = true;
			$pageDom->strictErrorChecking = false;
			$pageDom->loadHTML($html);
			$xpath = new DOMXpath($pageDom);

			// find inputs
			$nodelist = $xpath->query("//input");
			if ($nodelist->length > 0) {
				$input = $nodelist->item(0);
				$name = $input->nodeName;
				$value = $input->attributes->getNamedItem('value')->value;
				$record[$name] = $value;
			}
		}
		*/
	}

	echo Zend_Json::encode($record);
}
?>