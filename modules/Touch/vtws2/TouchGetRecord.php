<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TouchGetRecord extends TouchWSClass {

	function process(&$request) {
		global $log, $adb, $table_prefix, $touchInst, $touchUtils, $current_user, $currentModule;

		$module = $request['module'];
		$recordid = intval($request['record']);
		$prodblock = intval($request['forproductblock']);
		$updaterecent = intval($request['set_recent']);
		$setSeen = intval($request['set_seen']);
		$folderList = intval($request['get_folders_list']);
		$filterList = intval($request['get_filters_list']);
		$fetchBody = $request['fetch_body']; // crmv@166575
		$app_data = $request['APP_DATA'];

		if (empty($module)) return $this->error('Module not specified');

		$currentModule = $module;

		// lettura record esistente
		if ($recordid > 0) {

			if ($module == 'ModNotifications') {
				$notifInst = $touchInst->getWSClassInstance('GetNotifications', $this->requestedVersion);
				$result = $notifInst->getOne($recordid);
				if (!$result['success']) return $result;
				$record = $result['notifications'][0];
			} else {
				// retrieve a record
				$record = $this->retrieveRecord($module, $recordid, $prodblock, $fetchBody); // crmv@166575
				if (isset($record['success']) && !$record['success']) return $record;

				if ($record['vtecrm_permissions']['perm_read'] === false) return $record;

				$options = array(
					'skip_pdflist' => ($request['skip_pdflist'] ? true : false),
					'get_folders_list' => $folderList,
					'get_filters_list' => $filterList,
					'get_relations' => !!$request['get_relations'], // crmv@177095
				);

				$record = $this->addInfoToRecord($record, $options);
			}

			$actions = array(
				'set_recent' => $updaterecent,
				'set_seen' => $setSeen,
			);

			$this->doRecordActions($record, $actions);



		// creazione nuovo record
		// carico i campi precompilati (per related e personalizzazioni varie)
		// funziona parzialmente, solo i campi visibili vengono considerati
		} else {

			if (!empty($app_data)) {
				$appdata = Zend_Json::decode($app_data);

				$parentid = intval($appdata['parent_id']);
				$parentmod = $appdata['parent_module'];

				if (!empty($parentid) && !empty($parentmod)) {

					// trovo il campo di collegamento e lo imposto
					$response = $touchUtils->wsRequest($current_user->id, 'describe', array('elementType'=>$module));
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
				$record['is_all_day_event'] = '0'; // crmv@149285
			}

		}
		
		// crmv@104567
		if ($record && $record['success'] && $recordid == 0 && $module == 'HelpDesk') {
			$record['signature'] = '';
		}
		// crmv@104567e

		// TODO: fix this (what if there's a field with this name??)
		$record['success'] = true;
		return $record;
	}

	// TODO: clean up more and divide retrieving from adding extra infos
	function retrieveRecord($module, $recordid, $prodblock = 0, $fetchBody = false) { // crmv@166575
		global $touchInst, $touchUtils;
		global $adb, $table_prefix, $current_user;

		// crmv@37794
		$permModule = $module;
		if ($module == 'Events') $permModule = 'Calendar'; // trick to allow events i am invited to

		$focus = $touchUtils->getModuleInstance($permModule);
		$ret = $focus->retrieve_entity_info_no_html($recordid, $module, false); // crmv@151349
		$focus->id = $recordid;

		// crmv@166575
		if ($module == 'Messages' && $fetchBody == true) {
			$focus->fetchBody();
		}
		// crmv@166575e
		
		if ($ret == 'LBL_RECORD_DELETE') {
			return $this->error('The record has been deleted');
		} elseif ($ret == 'LBL_RECORD_NOT_FOUND') {
			return $this->error('The record was not found');
		}
		
		// crmv@187823
		$origFields = $focus->column_fields;
		
		// read permission check
		if (isPermitted($permModule, 'DetailView', $recordid) != 'yes') {
			$record['vtecrm_permissions'] = array(
				'perm_read' => false,
				'perm_write' => (isPermitted($permModule, 'EditView', $recordid) == 'yes'),
				'perm_delete' => (isPermitted($permModule, 'Delete', $recordid) == 'yes'),
			);
			$exitNow = true;
			// might be an event with masked fields
			if ($module == 'Events') {
				if ($focus->hasMaskedFields($recordid, $focus->column_fields)) {
					$okFields = $focus->getNonMaskedFields();
					// add these 2 special fields
					$okFields[] = 'record_id';
					$okFields[] = 'record_module';
					$okFields[] = 'modifiedtime';
					// filterout masked fields
					$origFields = array_intersect_key($focus->column_fields, array_flip($okFields));
					$origFields['subject'] = getTranslatedString('Private Event', 'Calendar');
					$origFields['vtecrm_permissions'] = $record['vtecrm_permissions'];
					$origFields['vtecrm_permissions']['perm_read'] = true;
					$exitNow = false;
				}
			}
			if ($exitNow) {
				return $record;
			}
		}
		// crmv@37794e

		$record = $origFields;
		// crmv@187823e
		
		foreach ($record as $fldname=>$fldvalue) {
			$record[$fldname] = $touchInst->field2Touch($module, $fldname, $fldvalue, false, $focus);
		}


		if (!isset($record['record_module']) || $module == 'Events') $record['record_module'] = $module;
		if (!isset($record['record_id'])) $record['record_id'] = $recordid;

		if (empty($record['crmid'])) {
			$record['crmid'] = ($record['record_id'] ? $record['record_id'] : $recordid);
		}

		// crmv@73256
		if (!empty($record['createdtime'])) {
			$record['ctimestamp'] = strtotime($record['createdtime']);
		}
		// crmv@73256e
		
		if (!empty($record['modifiedtime'])) {
			$record['timestamp'] = strtotime($record['modifiedtime']);
		}
		
		if (empty($record['owner']) && !empty($record['assigned_user_id'])) {
			$record['owner'] = $record['assigned_user_id'];
		}
		
		if (empty($record['entityname'])) {
			$record['entityname'] = $touchUtils->getEntityNameFromFields($permModule, $recordid, $record);
		}
		
		if (isInventoryModule($module)) {
			$record['product_block'] = $this->getProductBlock($record, $focus);
		}

		// aggiungo i campi per il blocco prodotti
		// TODO: fare anche servizi
		// TODO: verificare se serve ancore
		if ($prodblock == 1 && $module == 'Products') {
			$record['entityType'] = $module;
			//$record['entityname'] = $record['productname'];
			$record['comment'] = '';
			$record['productDescription'] = $record['description'];
			$record['productName'] = $record['productname'];
			$record['hdnProductId'] = array('crmid' => $recordid, 'display'=>$record['productname']);
			$record['hdnProductcode'] = $record['productcode'];

			// calcolo id della riga
			$res = $adb->query("select max(lineitem_id) as maxid from {$table_prefix}_inventoryproductrel");
			if ($res && $adb->num_rows($res) > 0) {
				$lineitemid = intval($adb->query_result($res, 0, 'maxid'))+1;
			} else {
				$lineitemid = 1;
			}
			$record['lineItemId'] = $lineitemid;

			$record['qty'] = 1;
			$record['qtyInStock'] = $record['qtyinstock'];
			$record['discountTotal'] = 0;
			$record['discount_amount'] = 0;
			$record['discount_percent'] = 0;
			$record['discount_type'] = 0;
			$record['taxTotal'] = 0;
			$record['unitPrice'] = $record['unit_price'];
			$record['totalAfterdiscount'] = $record['unit_price'];
			$record['lineTotal'] = $record['unit_price'];
			$record['listPrice'] = $record['unit_price'];
			$record['netPrice'] = $record['unit_price'];
			$record['productTotal'] = $record['unit_price'];
		}

		// remove the cleaned_body, it can double the size of the result for nothing
		if ($module == 'Messages') {
			unset($record['cleaned_body']);
			unset($record['content_ids']);

			// fix a problem on iOS with illegal characters
			$record['description'] = str_replace(array("\xe2\x80\xa8", "\xe2\x80\xa9"), '', $record['description']);
		}

		// add some extra fields
		// TODO: remove this, use the same trick for notifications
		if ($module == 'ModComments') {

			$record['crmid'] = $record['record_id'];
			$record['smcreatorid'] = getSingleFieldValue($table_prefix.'_crmentity', 'smcreatorid', 'crmid', $record['record_id']);
			$record['related_to'] = $record['related_to']['crmid'];
			$record['parent_comments'] = $record['parent_comments']['crmid'];

			//$record['commentcontent'] = str_replace('&amp;', '&', $record['commentcontent']);

			$widgetInstance = $focus->getWidget('DetailViewBlockCommentWidget');
			$model = new ModComments_CommentsModel($record);
			$commentData = $model->content_no_html();

			unset($commentData['timestamp']);
			$record = array_merge($record, $commentData);
			if (empty($record['owner'])) {
				$record['owner'] = $record['assigned_user_id'] ?: $record['smownerid'];
			}

			$hasunseen = false;
			$forced = false;
			$replies = $commentData['replies'];

			if (is_array($replies) && count($replies) > 0) {
				foreach ($replies as $rk=>$rv) {
					$replies[$rk]['timestamp'] = strtotime($rv['modifiedtime']);
					$replies[$rk]['ctimestamp'] = strtotime($rv['createdtime']); // crmv@73256
					$replies[$rk]['owner'] = $rv['assigned_user_id'] ?: $rv['smownerid'];
					// fix per &
					$replies[$rk]['commentcontent'] = str_replace('&amp;', '&', $rv['commentcontent']);
					$hasunseen |= $replies[$rk]['unseen'];
					$forced |= $replies[$rk]['forced'];
				}
				$lastmessage = $replies[count($replies)-1];
			} else {
				$lastmessage = $record;
				$replies = array();
			}

			unset($record['replies']);
			unset($record['crmid']);
			array_unshift($replies, $record);

			$lastmessage['record_module'] = $record['record_module'];
			$lastmessage['record_id'] = $record['record_id'];
			$lastmessage['unseen'] |= $commentData['unseen'] | $hasunseen;
			$lastmessage['forced'] |= $commentData['forced'] | $forced;
			$lastmessage['related_to'] = $record['related_to'];
			$lastmessage['related_to_name'] = $record['related_to_name'];
			$lastmessage['related_to_module'] = ($record['related_to'] > 0 ? $touchUtils->getTouchModuleNameFromId($record['related_to']) : '');
			$lastmessage['entityname'] = $touchUtils->getEntityNameFromFields('ModComments', $lastmessage['record_id'], $lastmessage);
			$lastmessage['comments'] = $replies;
			
			// crmv@106521
			$tid = $touchInst->getTempId($current_user->id, null, $lastmessage['crmid']);
			if ($tid) {
				$lastmessage['temp_crmid'] = $tid;
			}
			// crmv@106521e

			// return the last message as the parent
			$record = $lastmessage;
		}
		// crmv@104567 crmv@105798
		if ($recordid > 0 && $module == 'HelpDesk') {
			$no_image_lbl = getTranslatedString('NO_SIGNATURE_IMAGE', $module);
			if (!empty($record['signature']) && $record['signature'] != $no_image_lbl) {
				$img_path = $record['signature'];
				$type = pathinfo($img_path, PATHINFO_EXTENSION);
				$data = file_get_contents($img_path);
				$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
				$record['signature'] = $base64;
			} else {
				$record['signature'] = '';
			}
		}
		// crmv@104567e crmv@105798e
		
		return $record;
	}
	
	protected function getProductBlock(&$record, &$focus) {
		
		$prodBlock = array();
		$module = $record['record_module'];
		$recordid = $record['record_id'];
		
		$focus->mode = 'edit';
			
		$InventoryUtils = InventoryUtils::getInstance();
		$finalinfo = $InventoryUtils->getFinalDetails($module, $focus, $recordid);
				
		$totals = array();
		if (is_array($finalinfo[1]['final_details'])) {
			foreach ($finalinfo[1]['final_details'] as $finalfield=>$finalvalue) {
				$totals[$finalfield] = $finalvalue;
			}
			if (!isset($totals['hdnGrandTotal'])) $totals['hdnGrandTotal'] = $totals['grandTotal'];
			if ($totals['discount_type_final'] == 'amount') {
				$totals['discount_value'] = $totals['discount_amount_final'];
			} else {
				$totals['discount_value'] = $totals['discount_percentage_final'];
				// crmv@48677
				if (!empty($totals['discount_value'])) {
					$fieldval = $InventoryUtils->parseMultiDiscount($totals['discount_value'], 1, 0);
					$fieldval = $InventoryUtils->joinMultiDiscount($fieldval, 0, 0);
					if (is_numeric($fieldval)) $fieldval = floatval($fieldval);
					$totals['discount_value'] = $totals['discount_percentage_final'] = $fieldval;
				}
				// crmv@48677e
			}
			// tasse
			if (is_array($totals['taxes'])) {
				foreach ($totals['taxes'] as $taxinfo) {
					$totals[$taxinfo['taxname']] = $taxinfo['percentage'];
				}
			}
			if (is_array($totals['sh_taxes'])) {
				foreach ($totals['sh_taxes'] as $taxinfo) {
					$totals[$taxinfo['taxname']] = $taxinfo['percentage'];
				}
			}
			unset($totals['style_discount_amount_final']);
			unset($totals['style_discount_percentage_final']);
			unset($totals['checked_discount_amount_final']);
		}
		
		$prodBlock['TOTALS'] = $totals;
		
		return $prodBlock;
	}

	function addInfoToRecord($record, $options = array()) {
		global $touchInst, $touchUtils, $current_user; // crmv@106521

		$recordid = $record['record_id'];
		$module = $record['record_module'];

		$trackMod = $module;
		if ($module == 'Events') $trackMod = 'Calendar';

		// aggiungo campo fittizio per preferiti
		if (!isset($record['vtecrm_favourite'])) {
			$favimg = getFavorite($recordid);
			$record['vtecrm_favourite'] = (preg_match('/_on/', $favimg) ? true : false);
		}

		// aggiungo campi fittizi per permessi
		if (!isset($record['vtecrm_permissions'])) {
			// crmv@133435
			$modPerm = $touchInst->force_module_premissions[$module];
			$record['vtecrm_permissions'] = array(
				'perm_read' => true,
				'perm_write' => (isset($modPerm['perm_write']) ? $modPerm['perm_write'] : (isPermitted($module, 'EditView', $recordid) == 'yes')),
				'perm_delete' => (isset($modPerm['perm_delete']) ? $modPerm['perm_delete'] : (isPermitted($module, 'Delete', $recordid) == 'yes')),
			);
			// crmv@133435e
		}

		// pdfmaker
		if ($touchUtils->hasPDFMaker($module)) {
			$pddet = $touchUtils->getPDFMakerDetails($module, $recordid);
			if (empty($pddet['templates'])) {
				$pddet['actions'] = array();
				// no templates -> no actions available
			}
			// clear everything if there are no actions available
			if (empty($pddet['actions'])) $pddet = array();
			$record['vtecrm_pdfmaker'] = $pddet;
		}

		if ($module == 'Messages') {
			// mail attachment
			$focus = $touchUtils->getModuleInstance($module);
			$focus->id = $recordid;
			$attach_info = $focus->getAttachments(null);
			if ($attach_info) $record['attachments'] = $this->processAttachments($attach_info, $recordid, $focus); // crmv@88981
			
			global $current_folder, $current_account;

			$current_folder = $record['folder'];
			$current_account = $record['account'];

			if ($current_account && $current_folder) {
				$focus->setAccount($current_account);
				$specialFolders = $focus->getSpecialFolders(false);

				if (is_array($specialFolders) && in_array($current_folder, array($specialFolders['Sent'], $specialFolders['Drafts']))) {
					$mto = $record['mto'];
					$mto_n = $record['mto_n'];
					$mto_f = $record['mto_f'];
				} else {
					$mto = $record['mfrom'];
					$mto_n = $record['mfrom_n'];
					$mto_f = $record['mfrom_f'];
				}
				$from_or_to = $focus->getAddressName($mto,$mto_n,$mto_f,false);
				$record['from_or_to'] = $from_or_to;
				// crmv@107199 crmv@107655
				$bcard = $focus->getBusinessCard('FROM');
				$bcard = $bcard[0];
				if ($bcard && $bcard['module_permitted'] && !in_array($bcard['module'], $touchInst->excluded_modules)) {
					// sanitize the array
					$bcard['crmid'] = intval($bcard['id']);
					unset($bcard['id'], $bcard['module_permitted']);
					$record['bcard_from'] = $bcard;
				}
				$bcards = $focus->getBusinessCard('TO');
				if (is_array($bcards)) {
					foreach ($bcards as &$bcard) {
						if ($bcard['module_permitted'] && !in_array($bcard['module'], $touchInst->excluded_modules)) {
							// sanitize the array
							$bcard['crmid'] = intval($bcard['id']);
							unset($bcard['id'], $bcard['module_permitted']);
							$record['bcards_to'][] = $bcard;
						}
					}
					unset($bcard);
				}
				$bcards = $focus->getBusinessCard('CC');
				if (is_array($bcards)) {
					foreach ($bcards as &$bcard) {
						if ($bcard['module_permitted'] && !in_array($bcard['module'], $touchInst->excluded_modules)) {
							// sanitize the array
							$bcard['crmid'] = intval($bcard['id']);
							unset($bcard['id'], $bcard['module_permitted']);
							$record['bcards_cc'][] = $bcard;
						}
					}
				}
				// crmv@107199e crmv@107655e
				
				// crmv@136430
				// fix mdate if empty
				if (empty($record['mdate']) || $record['mdate'] == '0000-00-00 00:00:00') {
					$record['mdate'] = $record['createdtime'];
				}
				// crmv@136430e
				
				// crmv@174249
				$icals = $focus->getIcals() ?: array();
				foreach ($icals as &$ical) {
					unset($ical['content']); // don't download all the ics, it's big
				}
				$record['icals'] = $icals;
				// crmv@174249e
				
				$record['has_relations'] = ($focus->haveRelations($recordid) ? '1' : '0'); // crmv@177095
			}
			
			// check if there are related records
			// removed, no longer used!
			/*$rm = RelationManager::getInstance();
			$excludedMods = array('ModComments');
			$relIds = $rm->getRelatedIds($module, $recordid, array(), $excludedMods);
			$record['has_related_ids'] = (count($relIds) > 0);
			*/

		} elseif ($module == 'Events') {
			// check if i am invited to this
			if (!$focus) $focus = $touchUtils->getModuleInstance($trackMod);
			$record['am_i_invited'] = ($focus->isUserInvited($recordid, $current_user->id) ? 1 : 0);
			if ($record['am_i_invited']) {
				$record['invitation_answer'] = $focus->getUserInvitationAnswer($recordid, $current_user->id);
			}

		} elseif ($module == 'HelpDesk') {
			// retrieve ticket comments also
			$subReq = array('recordid' => $recordid);
			$record['ticket_comments'] = $this->subcall('GetTicketComments', $subReq);
		} else if ($module == 'Processes') {
			// crmv@100158
			require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
			$processDynaFormObj = ProcessDynaForm::getInstance();
			
			$form = array();
			$values = array();
			$elementid = '';
			$focusInst = null;
			
			$processmaker = $record['processmaker'];
			$runningProcess = $record['running_process'];
			
			$processmakerFocus = $touchUtils->getModuleInstance('Processes');
			$processmakerFocus->id = $recordid;
			$processmakerFocus->retrieve_entity_info($recordid, 'Processes');
			
			$dynaform = $processDynaFormObj->getCurrentDynaForm($processmakerFocus, $elementid);
			
			if (!empty($dynaform)) {
				$meta = $processDynaFormObj->getMeta($processmaker, $elementid);
				$dynaFormValues = $processDynaFormObj->getValues($runningProcess, $meta['id']);
				$values = array_merge($record, $dynaFormValues);
				foreach ($dynaform as $key => &$blocks) {
					$fields = &$blocks['fields'] ?: array();
					foreach ($fields as $fieldkey => $field) {
						$newField = $this->formatDynaFormField($field);
						if (isset($values[$field['fieldname']])) {
							$field['value'] = $values[$field['fieldname']];
							if ($newField['type']['name'] === 'owner') {
								if (strpos($field['value'], 'x') !== false) {
									$components = vtws_getIdComponents($field['value']);
									$field['value'] = $components[1];
								}
							}
						}
						$newField['value'] = $touchInst->field2Touch($module, $newField['name'], $field['value'], false, $focusInst, $newField);
						$form['blocks'][$key]['fields'][] = $newField;
					}
					$form['blocks'][$key]['blockid'] = $touchUtils->related_blockids['dynablocks'] + $key;
					$form['blocks'][$key]['label'] = $blocks['blocklabel'];
					$form['blocks'][$key]['tlabel'] = $blocks['label'];
				}
			}
			
			$record['processmaker'] = $processmaker;
			$record['running_process'] = $runningProcess;
			$record['dynaform'] = Zend_Json::encode($form);
			// crmv@100158e
		}
		
		//crmv@106521
		$tid = $touchInst->getTempId($current_user->id, null, $record['crmid']); // crmv@107655
		if ($tid) {
			$record['temp_crmid'] = $tid;
		}
		//crmv@106521e
		
		// added filters and folder retrieval
		if (!$focus) $focus = $touchUtils->getModuleInstance($trackMod);
		if ($options['get_filters_list']) {
			$CustomView = CRMEntity::getInstance('CustomView'); // crmv@115329
			$filterList = $CustomView->getRecordViews($module, $recordid, true);
			$record['filters'] = $filterList;
		}
		if ($options['get_folders_list'] && $focus->hasFolders()) {
			if ($record['folderid']) {
				$folderid = $record['folderid'];
			} else {
				$folderid = getSingleFieldValue($focus->table_name, 'folderid', $focus->table_index, $recordid);
			}
			$record['folders'] = array(intval($folderid));
		}
		
		// crmv@177095
		if ($options['get_relations']) {
			$changesInst = $touchInst->getWSClassInstance('GetChanges', $this->requestedVersion);
			$rels = $changesInst->getAllRelated($module, array($recordid));
			$record['relations'] = $rels[$recordid];
		}
		// crmv@177095e
			
		return $record;
	}
	
	// crmv@100158
	protected function formatDynaFormField($field) {
		global $touchUtils, $current_user;
		
		$formattedField = array();
		
		if (!empty($field)) {
			$processDynaFormObj = ProcessDynaForm::getInstance();
			$fieldDetails = $processDynaFormObj->getFieldTypeDetails($field);
			
			$formattedField['type']['name'] = $fieldDetails['name'];
			$formattedField['name'] = $field['fieldname'];
			$formattedField['uitype'] = $field['uitype'];
			$formattedField['editable'] = $field['editable'];
			$formattedField['hidden'] = $field['hidden'] ? true : false;
			$formattedField['label'] = $field['fieldlabel'];
			$formattedField['mandatory'] = $field['mandatory'];
			
			$name = $formattedField['type']['name'];
			switch ($name) {
				case 'reference':
					if (empty($fieldDetails['refersTo'])) {
						if (!empty($field['relatedmods_selected'])) {
							$formattedField['type']['refersTo'] = explode(',', $field['relatedmods_selected']);
						}
					} else {
						$formattedField['type']['refersTo'] = $fieldDetails['refersTo'];
					}
					break;
				case 'multipicklist':
				case 'picklist':
					$formattedField['type']['picklistValues'] = $fieldDetails['picklistValues'];
					$formattedField['type']['defaultValue'] = $fieldDetails['defaultValue'];
					break;
				case 'currency':
					$formattedField['type']['symbol'] = $fieldDetails['symbol'];
					$formattedField['type']['symbol_name'] = $fieldDetails['symbol_name'];
					break;
				case 'date':
					$formattedField['type']['format'] = $fieldDetails['format'];
					break;
			}
		}
		
		return $formattedField;
	}
	// crmv@100158e

	// crmv@88981
	protected function processAttachments(&$atts, $recordid, $focus) {
		if (is_array($atts)) {
			foreach ($atts as &$att) {
				if ($att['action_view_JSfunction'] == 'ViewEML') {
					// eml, do now the parsing of the attachments to have the ids ready
					$focus->retrieve_entity_info($recordid,'Messages');
					$messagesid = 0;
					$error = '';
					// crmv@129062
					try {
						$_REQUEST['service'] = 'Messages';
						$_REQUEST['app_key'] = '12345';
						$success = $focus->parseEML($att['contentid'], $messagesid, $error);
					} catch (Exception $e) {
						// ignore exceptions for the attachments
						$success = false;
					}
					// crmv@129062e
					if ($success && !empty($messagesid)) {
						$att['eml_messagesid'] = intval($messagesid);
					}
				}
				unset($att['img']);
				unset($att['action_view_JSfunction']);
				unset($att['action_view_label']);
			}
		}
		return $atts;
	}
	// crmv@88981e

	function doRecordActions($record, $actions = array()) {
		global $log, $adb, $table_prefix, $current_user, $touchUtils;

		$recordid = $record['record_id'];
		$module = $record['record_module'];

		$updaterecent = $actions['set_recent'];
		$setSeen = $actions['set_seen'];

		// set seen (only for messages now)
		if ($module == 'Messages' && $setSeen) {
			$focus = $touchUtils->getModuleInstance($module);
			$focus->id = $recordid;
			$focus->retrieve_entity_info($recordid, $module);
			try {
				$focus->setFlag('seen',1);
			} catch (Exception $e) {
				// ignore server errors
			}
		}

		// aggiorno i recenti
		if ($updaterecent) {
			$trackMod = $module;
			if ($module == 'Events') $trackMod = 'Calendar';

			require_once('data/Tracker.php');

			$focus = $touchUtils->getModuleInstance($trackMod);
			$focus->track_view($current_user->id, $trackMod, $recordid);
		}

	}

}
