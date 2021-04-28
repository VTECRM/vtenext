<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 - restituisce la struttura (blocchi, campi, related) di un modulo*/
/* crmv@33097 - gestione multi-modulo per offline */
/* crmv@33311 - conversazioni nei moduli, altri fix */
/* crmv@71985 - maps on address blocks */

class TouchGetBlocks extends TouchWSClass {

	public $longOperation = true;
	
	protected $cacheLife = 86400;	// 1 day
	
	public function clearCache($module = null) {
		global $touchCache;
		
		if ($module) {
			$touchCache->delete('blocks_std_'.$module);
			$touchCache->delete('blocks_invrel'.$module);
			$touchCache->delete('blocks_extrarel_'.$module);
			$touchCache->delete('blocks_stdrel_'.$module);
		} else {
			$touchCache->deleteMatching('/^blocks_/');
		}
	}

	function process(&$request) {
		global $adb, $table_prefix, $currentModule, $current_user;
		global $touchInst, $touchUtils, $touchCache;

		// parametri
		$module = $request['module'];
		$modules = Zend_Json::decode($request['modules']);
		//$recordid = intval($request['recordid']); // DEPRECATED -  this parameter is no longer used
		$type = $request['type'];	// get only one type of "blocks", empty means all

		if ($module != 'ALL' && in_array($module, $touchInst->excluded_modules)) return $this->error('Module not permitted');

		$moduleslist = array();
		if ($module == 'ALL') {
			$req = array('onlynames'=>true);
			$moduleslist = $this->subcall('ModulesList', $req);
		} elseif (is_array($modules)) {
			$moduleslist = array_filter($modules);
		} else {
			$moduleslist[] = $module;
		}

		$allblocks = array();
		foreach ($moduleslist as $modulename) {
			$module = $currentModule = $modulename;

			$blocks = array();

			// Blocks
			if (empty($type) || $type == 'BLOCK') {
				$blocks = $this->getBlocks($module);
				if (empty($blocks)) continue;
			}

			// Product related
			if (empty($type) || $type == 'PRODUCTS') {
				$prodrel = $this->getInventoryRelated($module);
				if (!empty($prodrel)) $blocks = array_merge($blocks, $prodrel);
			}

			// Other related
			$extrarel = $this->getExtraRelated($module);
			if (!empty($extrarel)) {
				// remove extra related
				if (!empty($type)) {
					foreach ($extrarel as $k=>$erel) {
						if ($erel['type'] != $type) unset($extrarel[$k]);
					}
				}
				$blocks = array_merge($blocks, array_values($extrarel));
			}

			// Standard Related
			if (empty($type) || $type == 'RELATED') {
				$relblocks = $this->getStandardRelated($module, '');
				$blocks = array_merge($blocks, $relblocks);
			}

			// Merge all
			$allblocks = array_merge($allblocks, $blocks);
		}

		// output
		return $this->success(array('blocks'=> $allblocks, 'total'=>count($allblocks)));
	}

	protected function getBlocks($module) {
		global $current_user, $touchInst, $touchUtils, $touchCache;
		global $adb, $table_prefix;

		// check cache
		$cachedBlocks = $touchCache->get('blocks_std_'.$module);
		if ($cachedBlocks) return $cachedBlocks;
		
		// crmv@164122
		if ($module == 'ModNotifications' && vtlib_isModuleActive($module) && !in_array($module, $touchInst->excluded_modules)) {
			return $this->getModNotificationsBlocks();
		}
		// crmv@164122e
		
		$blocks = array();
		$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

		$response = $touchUtils->wsRequest($current_user->id,'describe', array('elementType'=> $module));

		if (!is_array($response) || !$response['success'] || !is_array($response['result']['fields'])) {
			return null;
		}
		$response = $response['result'];

		// CALENDARIO --- prendo gli id dei blocchi custom e il blocco task
		$cal_customblocks = array();
		$res = $adb->pquery("select blockid from {$table_prefix}_blocks where tabid in (9,16) and blocklabel like ?", array('LBL_CUSTOM_INFORMATION'));
		if ($res) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$cal_customblocks[] = $row['blockid'];
			}
		}
		$cal_taskblock = 19;
		if ($module == 'Calendar') {
			$res = $adb->pquery("select blockid from {$table_prefix}_blocks where tabid = '9' and blocklabel like ?", array('LBL_TASK_INFORMATION'));
		} elseif ($module == 'Events') {
			$res = $adb->pquery("select blockid from {$table_prefix}_blocks where tabid = '16' and blocklabel like ?", array('LBL_EVENT_INFORMATION'));
		}
		if ($res && $adb->num_rows($res) > 0) {
			$cal_taskblock = $adb->query_result_no_html($res, 0, 'blockid');
		}
		// CALENDARIO END ---------

		// crmv@164122 - removed code

		// MODCOMMENTS (fake fields)
		if ($module == 'ModComments') {
			$response['fields'][] = array(
				'name' => 'author',
				'blockid' => $response['fields'][0]['blockid'],
				'type' => array(
					'name' => 'string',
				),
				'uitype' => 1,
				'editable' => false,
			);
			$response['fields'][] = array(
				'name' => 'authorPhoto',
				'blockid' => $response['fields'][0]['blockid'],
				'type' => array(
					'name' => 'string',
				),
				'uitype' => 1,
				'editable' => false,
			);
			$response['fields'][] = array(
				'name' => 'recipients',
				'blockid' => $response['fields'][0]['blockid'],
				'type' => array(
					'name' => 'string',
				),
				'uitype' => 1,
				'editable' => false,
			);
			$response['fields'][] = array(
				'name' => 'related_to_module',
				'blockid' => $response['fields'][0]['blockid'],
				'type' => array(
					'name' => 'string',
				),
				'uitype' => 1,
				'editable' => false,
			);
			$response['fields'][] = array(
				'name' => 'related_to_name',
				'blockid' => $response['fields'][0]['blockid'],
				'type' => array(
					'name' => 'string',
				),
				'uitype' => 1,
				'editable' => false,
			);
			$response['fields'][] = array(
				'name' => 'unseen',
				'blockid' => $response['fields'][0]['blockid'],
				'type' => array(
					'name' => 'boolean',
				),
				'uitype' => 1,
				'editable' => false,
			);
			$response['fields'][] = array(
				'name' => 'forced',
				'blockid' => $response['fields'][0]['blockid'],
				'type' => array(
					'name' => 'boolean',
				),
				'uitype' => 1,
				'editable' => false,
			);
			$response['fields'][] = array(
				'name' => 'comments',
				'blockid' => $response['fields'][0]['blockid'],
				'type' => array(
					'name' => 'string',
				),
				'uitype' => 1,
				'editable' => false,
			);

		} elseif ($module == 'Messages') {
			$response['fields'][] = array(
				'name' => 'attachments',
				'blockid' => $response['fields'][0]['blockid'],
				'type' => array(
					'name' => 'string',
				),
				'uitype' => 1,
				'editable' => false,
			);
			$response['fields'][] = array(
				'name' => 'from_or_to',
				'blockid' => $response['fields'][0]['blockid'],
				'type' => array(
					'name' => 'string',
				),
				'uitype' => 1,
				'editable' => false,
			);
			// crmv@107199 crmv@107655
			$response['fields'][] = array(
				'name' => 'bcard_from',
				'blockid' => $response['fields'][0]['blockid'],
				'type' => array(
					'name' => 'string',
				),
				'uitype' => 1,
				'editable' => false,
			);
			$response['fields'][] = array(
				'name' => 'bcards_to',
				'blockid' => $response['fields'][0]['blockid'],
				'type' => array(
					'name' => 'string',
				),
				'uitype' => 1,
				'editable' => false,
			);
			$response['fields'][] = array(
				'name' => 'bcards_cc',
				'blockid' => $response['fields'][0]['blockid'],
				'type' => array(
					'name' => 'string',
				),
				'uitype' => 1,
				'editable' => false,
			);
			// crmv@107199e crmv@107655e
			// crmv@174249
			$response['fields'][] = array(
				'name' => 'icals',
				'blockid' => $response['fields'][0]['blockid'],
				'type' => array(
					'name' => 'string',
				),
				'uitype' => 1,
				'editable' => false,
			);
			// crmv@174249e
			// crmv@177095
			$response['fields'][] = array(
				'name' => 'has_relations',
				'blockid' => $response['fields'][0]['blockid'],
				'type' => array(
					'name' => 'boolean',
				),
				'uitype' => 1,
				'editable' => false,
			);
			// crmv@177095e
			
		} elseif ($module == 'Events') {
			// add some fields for the calendar

			$response['fields'][] = array(
				'name' => 'am_i_invited',
				'blockid' => $response['fields'][0]['blockid'],
				'type' => array(
					'name' => 'boolean',
				),
				'uitype' => 1,
				'editable' => false,
				'hidden' => true,	// not shown in the standard edit view
			);

			$response['fields'][] = array(
				'name' => 'invitation_answer',
				'blockid' => $response['fields'][0]['blockid'],
				'type' => array(
					'name' => 'integer',
				),
				'uitype' => 1,
				'editable' => false,
				'hidden' => true,
			);

		} elseif ($module == 'Processes') {
			// crmv@100158
			// aggiungo campo fittizio per caricare il form dinamico dei processi
			$response['fields'][] = array(
				'name' => 'dynaform',
				'blockid' => getBlockId(getTabId('Processes'), 'LBL_DESCRIPTION_INFORMATION'),
				'type' => array(
					'name' => 'text',
				),
				'uitype' => 1,
				'editable' => false,
				'hidden' => true,
			);
			
			// id nascosti per il retrieve del processo nell'update
			$response['fields'][] = array(
				'name' => 'processmaker',
				'blockid' => $response['fields'][0]['blockid'],
				'type' => array(
					'name' => 'integer',
				),
				'uitype' => 1,
				'editable' => false,
				'hidden' => true,
			);
			
			$response['fields'][] = array(
				'name' => 'running_process',
				'blockid' => $response['fields'][0]['blockid'],
				'type' => array(
					'name' => 'integer',
				),
				'uitype' => 1,
				'editable' => false,
				'hidden' => true,
			);
			// crmv@100158e
		}

		// riordino la risposta
		$blocks = array();
		foreach ($response['fields'] as $fld) {

			// nascondo alcuni campi
			if (isInventoryModule($module)) {
				if (in_array($fld['name'], array('txtAdjustment', 'subtotal', 'total', 'conversion_rate'))) continue;
				if (substr($fld['name'], 0, 3) == 'hdn') continue;
			}
			
			if ($module == 'Messages') {
				if (in_array($fld['name'], array('cleaned_body', 'content_ids'))) continue;
			}
			
			// table fields are not supported
			if ($fld['uitype'] == 220) continue;

			if ($module == 'Calendar') {
				// rimozione campi degli eventi
				if (in_array($fld['name'], array('reminder_time', 'recurringtype', 'eventstatus', 'time_end', 'activitytype', 'duration_hours', 'duration_minutes', 'visibility', 'location', 'notime', 'id', 'ical_uuid', 'recurr_idx'))) continue;
				// sposto tutti i campi non custom nel primo blocco
				if (!in_array($fld['blockid'], $cal_customblocks)) $fld['blockid'] = $cal_taskblock;
				// il soggetto è deve essere il primo campo
				if ($fld['name'] == 'subject') $fld['sequence'] = 0;
			} elseif ($module == 'Events') {
				// rimozione campi degli eventi
				if (in_array($fld['name'], array('reminder_time', 'recurringtype', 'taskstatus', 'notime', 'id', 'duration_hours', 'duration_minutes', 'contact_id', 'ical_uuid', 'recurr_idx'))) continue;
				// sposto tutti i campi non custom nel primo blocco
				if (!in_array($fld['blockid'], $cal_customblocks)) $fld['blockid'] = $cal_taskblock;
				// il soggetto è deve essere il primo campo
				if ($fld['name'] == 'subject') $fld['sequence'] = 0;
			}

			// crmv@99131
			if (in_array($fld['name'], array('creator'))) continue;
			
			if ($module == 'Charts') {
				// change the type of the reportid field
				if ($fld['name'] == 'reportid') {
					$fld['type']['name'] = 'string';
				}
			}
			// crmv@99131e

			if ($module == 'HelpDesk') {
				if (in_array($fld['name'], array('update_log'))) continue;
			}
			
			// crmv@71388
			if ($module == 'MyNotes') {
				if (in_array($fld['name'], array('createdtime', 'modifiedtime'))) continue;
			}

			// Documenti
			if ($module == 'Documents' || $module == 'Myfiles') {
				if ($fld['name'] == 'folderid') {
					$folders = getEntityFoldersByName(null, $module);
					if (is_array($folders)) {
						foreach ($folders as $k=>$f) {
							$folders[$k] = array('value'=>$f['folderid'], 'label'=>$f['foldername']);
						}
					} else {
						$folders = array();
					}
					$fld['type'] = array(
						'name' => 'picklist',
						'defaultValue' => '',
						'picklistValues' => $folders,
					);
					$fld['uitype'] = 15;
				} elseif ($fld['name'] == 'filelocationtype') {
					//$fld['editable'] = false;
					$fld['type'] = array(
						'name' => 'picklist',
						'defaultValue' => '',
						'picklistValues' => array(
							0 => array('value'=>'E', 'label'=>getTranslatedString('LBL_EXTERNAL', $module)),
							1 => array('value'=>'I', 'label'=>getTranslatedString('LBL_INTERNAL', $module)),
						),
					);
					$fld['uitype'] = 15;
				} elseif (in_array($fld['name'], array('filesize', 'filetype'))) {
					$fld['editable'] = false;
				} elseif (in_array($fld['name'], array('filedownloadcount'))) {
					continue;
				}
			}
			// crmv@71388e

			// campi cifrati
			if ($fld['uitype'] == 208) {
				$fld['editable'] = false;
			}

			// campi testo html
			if ($fld['uitype'] == 210) {
				$fld['editable'] = false;
			}
			
			// crmv@187823
			// campo organizzatore
			if ($fld['uitype'] == 49) {
				$fld['editable'] = false;
			}
			// crmv@187823e

			// campi currency
			if ($fld['type']['name'] == 'reference' && $fld['type']['refersTo'][0] == 'Currency') {
				$currinfo = $InventoryUtils->getAllCurrencies();
				$currpicklist = array();
				foreach ($currinfo as $c) {
					$currpicklist[] = array('value' => intval($c['curid']), 'label' => $c['currencylabel']); // crmv@134732
				}
				$fld['type'] = array(
					'name' => 'picklist',
					'defaultValue' => '',
					'picklistValues' => $currpicklist,
				);
				$fld['uitype'] = 15;
			}

			// crmv@114632 - exclude fields to restricted modules
			if ($fld['type']['name'] == 'reference' && $fld['uitype'] == '10' && is_array($fld['type']['refersTo'])) {
				$fld['type']['refersTo'] = array_values(array_diff($fld['type']['refersTo'], $touchInst->excluded_modules));
				if (count($fld['type']['refersTo']) == 0) continue;
			}
			// crmv@114632e

			$blockid = $fld['blockid'];
			if ($blockid > 0) {
				if (!is_array($blocks[$blockid])) {
					$res = $adb->pquery("select * from {$table_prefix}_blocks where blockid = ?", array($blockid));
					if ($res && $adb->num_rows($res) > 0) {
						$row = $adb->fetchByAssoc($res, -1, false);
						if ($module == 'Calendar' && in_array($blockid, array(20,40))) $row['blocklabel'] = 'Description';
						
						$isAddressBlock = false;
						if (in_array($row['blocklabel'], array('LBL_ADDRESS_INFORMATION', 'LBL_VENDOR_ADDRESS_INFORMATION'))) {
							$isAddressBlock = true;
						}
						
						$block = array(
							'blockid' => $blockid,
							'type' => 'BLOCK',
							'module' => $module,
							'tabid' => getTabid($module),
							//'name' => $row['blocklabel'],
							'label' => getTranslatedString($row['blocklabel'], $module),
							'sequence' => $row['sequence'],
							'addressblock' => intval($isAddressBlock),
						);
						$blocks[$blockid] = $block;
					}
				}
				
				// geolocation info for fields
				if ($blocks[$blockid]['addressblock'] == 1) {
					$addr = getTranslatedString('LBL_ADDRESS', 'APP_STRINGS');
					$priAddr = str_replace(':', '', getTranslatedString('LBL_PRIMARY_ADDRESS', 'Contacts'));
					$secAddr = str_replace(':', '', getTranslatedString('LBL_ALTERNATE_ADDRESS', 'Contacts'));
					$billAddr = getTranslatedString('LBL_BILLING_ADDRESS', 'APP_STRINGS');
					$shipAddr = getTranslatedString('LBL_SHIPPING_ADDRESS', 'APP_STRINGS');
					$fieldGeoTypeMap = array(
						'lane' 			=> array('type'=>'street', 'name'=>'addr', 'label' => $addr),			// lead
						'street' 		=> array('type'=>'street', 'name'=>'addr', 'label' => $addr),			// vendor
						'mailingstreet' => array('type'=>'street', 'name'=>'addr1', 'label' => $priAddr),		// contact #1
						'otherstreet'	=> array('type'=>'street', 'name'=>'addr2', 'label' => $secAddr),		// contact #2
						'bill_street'	=> array('type'=>'street', 'name'=>'addr1', 'label' => $billAddr),		// other mods #1
						'ship_street'	=> array('type'=>'street', 'name'=>'addr2', 'label' => $shipAddr),		// other mods #2
						
						'city' 			=> array('type'=>'city', 'name'=>'addr', 'label' => $addr),				// lead and vendor
						'mailingcity' 	=> array('type'=>'city', 'name'=>'addr1', 'label' => $priAddr),			// contact #1
						'othercity'		=> array('type'=>'city', 'name'=>'addr2', 'label' => $secAddr),			// contact #2
						'bill_city'		=> array('type'=>'city', 'name'=>'addr1', 'label' => $billAddr),	
						'ship_city'		=> array('type'=>'city', 'name'=>'addr2', 'label' => $shipAddr),

						'code' 			=> array('type'=>'code', 'name'=>'addr', 'label' => $addr),				// lead
						'postalcode' 	=> array('type'=>'code', 'name'=>'addr', 'label' => $addr),				// vendor
						'mailingzip' 	=> array('type'=>'code', 'name'=>'addr1', 'label' => $priAddr),			// contact #1
						'otherzip'		=> array('type'=>'code', 'name'=>'addr2', 'label' => $secAddr),			// contact #2
						'bill_code'		=> array('type'=>'code', 'name'=>'addr1', 'label' => $billAddr),	
						'ship_code'		=> array('type'=>'code', 'name'=>'addr2', 'label' => $shipAddr),
						
						'state' 		=> array('type'=>'state', 'name'=>'addr', 'label' => $addr),			// lead and vendor
						'mailingstate' 	=> array('type'=>'state', 'name'=>'addr1', 'label' => $priAddr),		// contact #1
						'otherstate'	=> array('type'=>'state', 'name'=>'addr2', 'label' => $secAddr),		// contact #2
						'bill_state'	=> array('type'=>'state', 'name'=>'addr1', 'label' => $billAddr),	
						'ship_state'	=> array('type'=>'state', 'name'=>'addr2', 'label' => $shipAddr),

						'country' 		=> array('type'=>'country', 'name'=>'addr', 'label' => $addr),			// lead and vendor
						'mailingcountry'=> array('type'=>'country', 'name'=>'addr1', 'label' => $priAddr),		// contact #1
						'othercountry'	=> array('type'=>'country', 'name'=>'addr2', 'label' => $secAddr),		// contact #2
						'bill_country'	=> array('type'=>'country', 'name'=>'addr1', 'label' => $billAddr),	
						'ship_country'	=> array('type'=>'country', 'name'=>'addr2', 'label' => $shipAddr),
					);
					if (array_key_exists($fld['name'], $fieldGeoTypeMap)) {
						$fld['geoinfo'] = $fieldGeoTypeMap[$fld['name']];
					}
				}
				
				$blocks[$blockid]['fields'][] = $fld;
			}
		}

		// ordino i campi nei blocchi
		foreach ($blocks as $blockid => $binfo) {
			usort($blocks[$blockid]['fields'], function($v1,$v2) {
				return ($v1["sequence"] > $v2["sequence"] ? +1 : ($v1["sequence"] < $v2["sequence"] ? -1 : 0));
			});
			$blocks[$blockid]['fields'] = Zend_Json::encode($blocks[$blockid]['fields']); // double encode
		}

		// riordina indici
		$blocks = array_values($blocks);

		// ordino i blocchi
		usort($blocks, function($v1,$v2) {
			return ($v1["sequence"] > $v2["sequence"] ? +1 : ($v1["sequence"] < $v2["sequence"] ? -1 : 0));
		});

		$touchCache->set('blocks_std_'.$module, $blocks, $this->cacheLife);
		return $blocks;
	}
	
	// crmv@164122
	protected function getModNotificationsBlocks() {
		global $current_user, $touchInst, $touchUtils, $touchCache;
		global $adb, $table_prefix;
		
		$module = 'ModNotifications';
		$blocks = array();
		$fields = array();
		$blockid = 10000; // crmv@179603
		
		$fields[] = array(
			'name' => 'related_to',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'reference',
				'refersTo' => array()
			),
			'uitype' => 10,
			'editable' => false,
		);
		$fields[] = array(
			'name' => 'mod_not_type',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'picklist',
				'picklistValues' => array()
			),
			'uitype' => 15,
			'editable' => false,
		);
		$fields[] = array(
			'name' => 'assigned_user_id',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'owner',
			),
			'uitype' => 53,
			'editable' => false,
		);
		$fields[] = array(
			'name' => 'createdtime',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'datetime',
			),
			'uitype' => 70,
			'editable' => false,
		);
		$fields[] = array(
			'name' => 'modifiedtime',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'datetime',
			),
			'uitype' => 70,
			'editable' => false,
		);
		$fields[] = array(
			'name' => 'seen',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'boolean',
			),
			'uitype' => 56,
			'editable' => false,
		);
		$fields[] = array(
			'name' => 'description',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'text',
			),
			'uitype' => 19,
			'editable' => false,
		);
		
		// and add some extra fields
		$fields[] = array(
			'name' => 'action',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'string',
			),
			'uitype' => 1,
			'editable' => false,
		);
		$fields[] = array(
			'name' => 'author',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'string',
			),
			'uitype' => 1,
			'editable' => false,
		);
		$fields[] = array(
			'name' => 'rawhtml',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'string',
			),
			'uitype' => 1,
			'editable' => false,
		);
		$fields[] = array(
			'name' => 'hasdetails',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'boolean',
			),
			'uitype' => 1,
			'editable' => false,
		);
		$fields[] = array(
			'name' => 'haslist',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'boolean',
			),
			'uitype' => 1,
			'editable' => false,
		);
		$fields[] = array(
			'name' => 'details',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'string',
			),
			'uitype' => 1,
			'editable' => false,
		);
		$fields[] = array(
			'name' => 'list',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'string',
			),
			'uitype' => 1,
			'editable' => false,
		);
		$fields[] = array(
			'name' => 'item_module',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'string',
			),
			'uitype' => 1,
			'editable' => false,
		);
		$fields[] = array(
			'name' => 'item_record',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'integer',
			),
			'uitype' => 1,
			'editable' => false,
		);
		// crmv@142034
		$fields[] = array(
			'name' => 'item_viewname',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'integer',
			),
			'uitype' => 7,
			'editable' => false,
		);
		// crmv@142034e
		$fields[] = array(
			'name' => 'item_value',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'string',
			),
			'uitype' => 1,
			'editable' => false,
		);
		$fields[] = array(
			'name' => 'item_type',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'string',
			),
			'uitype' => 1,
			'editable' => false,
		);
		$fields[] = array(
			'name' => 'related_module',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'string',
			),
			'uitype' => 1,
			'editable' => false,
		);
		$fields[] = array(
			'name' => 'related_record',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'integer',
			),
			'uitype' => 1,
			'editable' => false,
		);
		$fields[] = array(
			'name' => 'related_value',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'string',
			),
			'uitype' => 1,
			'editable' => false,
		);
		$fields[] = array(
			'name' => 'related_type',
			'blockid' => $blockid,
			'type' => array(
				'name' => 'string',
			),
			'uitype' => 1,
			'editable' => false,
		);
		
		$blocks[] = array(
			'blockid' => $blockid,
			'type' => 'BLOCK',
			'module' => $module,
			'tabid' => getTabid($module),
			//'name' => $row['blocklabel'],
			'label' => "LBL_INFORMATION",
			'sequence' => 1,
			'fields' => Zend_Json::encode($fields),
		);

		$touchCache->set('blocks_std_'.$module, $blocks, $this->cacheLife);
		
		return $blocks;
	}
	// crmv@164122e

	protected function getInventoryRelated($module) {
		global $adb, $table_prefix, $current_user;
		global $touchInst, $touchUtils, $touchCache;

		// check cache
		$cachedBlocks = $touchCache->get('blocks_invrel_'.$module);
		if ($cachedBlocks) return $cachedBlocks;
		
		$blocks = array();
		$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

		// PRODOTTI - simulo una related
		if (isInventoryModule($module)) {

			$blockid = $touchUtils->related_blockids['products'] + getTabid($module); // 1 milione + tabid
			$totalFields = array(
				array(
					'name' => 'hdnSubTotal',
					'label' => getTranslatedString('LBL_NET_TOTAL', 'APP_STRINGS'),
					'mandatory' => true,
					'type' => array(
						'name' => 'double',
					),
					'nullable' => false,
					'editable' => false,
					'uitype' => 7,
				),
				array(
					'name' => 'discount_type_final',
					'label' => getTranslatedString('Discount', 'APP_STRINGS'),
					'mandatory' => false,
					'type' => array(
						'name' => 'picklist',
						'picklistValues' => array(
							array('value' => 'percentage', 'label' => '% '.getTranslatedString('LBL_OF_PRICE', 'APP_STRINGS')),
							array('value' => 'amount', 'label' => getTranslatedString('LBL_DIRECT_PRICE_REDUCTION', 'APP_STRINGS')),
						),
					),
					'nullable' => true,
					'editable' => true,
					'uitype' => 15,
				),
				array(
					'name' => 'discount_value',
					'label' => getTranslatedString('Discount Amount', 'Quotes'),
					'mandatory' => false,
					'type' => array(
						'name' => 'string',	// crmv@48677 to handle multiple discounts
					),
					'nullable' => true,
					'editable' => true,
					'uitype' => 7,
						),
						array(
						'name' => 'shipping_handling_charge',
						'label' => getTranslatedString('LBL_SHIPPING_AND_HANDLING_CHARGES', 'APP_STRINGS'),
						'mandatory' => false,
						'type' => array(
						'name' => 'double',
						),
						'nullable' => true,
						'editable' => true,
						'uitype' => 7,
						),
			);
			// tasse per la spedizione
			$taxes = $InventoryUtils->getAllTaxes('available','sh');
			foreach ($taxes as $taxinfo) {
				if ($taxinfo['deleted'] != 0) continue;
				$field = array(
					'name' => $taxinfo['taxname'],
					'label' => " - ".$taxinfo['taxlabel']." %",
					'mandatory' => false,
					'type' => array(
						'name' => 'double',
					),
					'nullable' => true,
					'editable' => true,
					'uitype' => 7,
				);
				$totalFields[] = $field;
			}
			$totalFields[] = array(
				'name' => 'shtax_totalamount',
				'label' => getTranslatedString('LBL_TAX_FOR_SHIPPING_AND_HANDLING', 'APP_STRINGS'),
				'mandatory' => false,
				'type' => array(
					'name' => 'double',
				),
				'nullable' => true,
				'editable' => false,
				'uitype' => 7,
			);
			$totalFields[] = array(
				'name' => 'adjustmentType',
				'label' => getTranslatedString('LBL_ADJUSTMENT', 'APP_STRINGS'),
				'mandatory' => false,
				'type' => array(
					'name' => 'picklist',
					'picklistValues' => array(
						array('value' => '+', 'label' => getTranslatedString('LBL_ADD_ITEM', 'APP_STRINGS')),
						array('value' => '-', 'label' => getTranslatedString('LBL_DEDUCT', 'APP_STRINGS')),
					),
				),
				'nullable' => true,
				'editable' => true,
				'uitype' => 7,
			);
			$totalFields[] = array(
				'name' => 'adjustment',
				'label' => ' ', // getTranslatedString('LBL_ADJUSTMENT', 'APP_STRINGS'),
				'mandatory' => false,
				'type' => array(
					'name' => 'double',
				),
				'nullable' => true,
				'editable' => true,
				'uitype' => 7,
			);
			$totalFields[] = array(
				'name' => 'grandTotal',
				'label' => getTranslatedString('LBL_TOTAL', 'APP_STRINGS'),
				'mandatory' => false,
				'type' => array(
					'name' => 'double',
				),
				'nullable' => false,
				'editable' => false,
				'uitype' => 7,
			);

			$prodFields = array(
				// campi per il totale - dentro ad un campo fittizio
				array(
					'name' => 'TOTALS',
					'fields' => $totalFields,
				),
				// campi per singoli prodotti
				array(
					'name' => 'hdnProductId',
					'label' => getTranslatedString('Product', 'Products'),
					'mandatory' => true,
					'type' => array(
						'name' => 'reference',
						'refersTo' => array('Products'),
					),
					'nullable' => false,
					'editable' => false,
					'uitype' => 10,
				),
				array(
					'name' => 'productDescription',
					'label' => getTranslatedString('Description', 'Products'),
					'mandatory' => false,
					'type' => array(
						'name' => 'text',
					),
					'nullable' => true,
					'editable' => true,
					'uitype' => 21,
				),
				array(
					'name' => 'comment',
					'label' => getTranslatedString('Comment', 'Products'), // TODO: aggiungi a prodotti
					'mandatory' => false,
					'type' => array(
						'name' => 'text',
					),
					'nullable' => true,
					'editable' => true,
					'uitype' => 21,
				),
				array(
					'name' => 'qtyInStock',
					'label' => getTranslatedString('Qty In Stock', 'Products'),
					'mandatory' => false,
					'type' => array(
						'name' => 'double',
					),
					'nullable' => true,
					'editable' => false,
					'uitype' => 7,
				),
				array(
					'name' => 'qty',
					'label' => getTranslatedString('Quantity', 'Products'),
					'mandatory' => true,
					'type' => array(
						'name' => 'double',
					),
					'nullable' => false,
					'editable' => true,
					'uitype' => 7,
				),
				array(
					'name' => 'listPrice',
					'label' => getTranslatedString('LBL_LIST_PRICE', 'APP_STRINGS'),
					'mandatory' => true,
					'type' => array(
						'name' => 'double',
					),
					'nullable' => false,
					'editable' => true,
					'uitype' => 7,
				),
				array(
					'name' => 'productTotal',
					'label' => getTranslatedString('Total', 'APP_STRINGS'),
					'mandatory' => false,
					'type' => array(
						'name' => 'double',
					),
					'nullable' => false,
					'editable' => false,
					'uitype' => 7,
				),
				array(
					'name' => 'discount_type',
					'label' => getTranslatedString('Discount', 'APP_STRINGS'),
					'mandatory' => false,
					'type' => array(
						'name' => 'picklist',
						'picklistValues' => array(
							array('value' => 'zero', 'label' => getTranslatedString('LBL_ZERO_DISCOUNT', 'APP_STRINGS')),
							array('value' => 'percentage', 'label' => '% '.getTranslatedString('LBL_OF_PRICE', 'APP_STRINGS')),
							array('value' => 'amount', 'label' => getTranslatedString('LBL_DIRECT_PRICE_REDUCTION', 'APP_STRINGS')),
						),
					),
					'nullable' => true,
					'editable' => true,
					'uitype' => 15,
				),
				array(
					'name' => 'discount_amount',
					'label' => getTranslatedString('Discount Amount', 'Quotes'),
					'mandatory' => false,
					'type' => array(
						'name' => 'string',	// crmv@48677 to handle multiple discounts
					),
					'nullable' => true,
					'editable' => true,
					'uitype' => 7,
				),
				array(
					'name' => 'totalAfterDiscount',
					'label' => getTranslatedString('LBL_TOTAL_AFTER_DISCOUNT', 'APP_STRINGS'),
					'mandatory' => false,
					'type' => array(
						'name' => 'double',
					),
					'nullable' => false,
					'editable' => false,
					'uitype' => 7,
				),
			);
			// campi per le tasse (li prendo tutti per ora)
			// TODO: segnare in qualche modo
			$taxes = $InventoryUtils->getAllTaxes();
			foreach ($taxes as $taxinfo) {
				if ($taxinfo['deleted'] != 0) continue;
				$field = array(
					'name' => $taxinfo['taxname'],
					'label' => " - ".$taxinfo['taxlabel']." %",
					'mandatory' => false,
					'type' => array(
						'name' => 'double',
						'istax' => true, // non standard
					),
					'nullable' => true,
					'editable' => true,
					'uitype' => 7,
				);
				$prodFields[] = $field;
			}

			$prodFields[] = array(
				'name' => 'taxTotal',
				'label' => getTranslatedString('LBL_TAX', 'APP_STRINGS'),
				'mandatory' => false,
				'type' => array(
					'name' => 'double',
				),
				'nullable' => false,
				'editable' => false,
				'uitype' => 7,
			);
			$prodFields[] = array(
				'name' => 'netPrice',
				'label' => getTranslatedString('LBL_NET_PRICE', 'APP_STRINGS'),
				'mandatory' => false,
				'type' => array(
					'name' => 'double',
				),
				'nullable' => false,
				'editable' => false,
				'uitype' => 7,
			);

			$i = $blockid + 1;
			foreach ($prodFields as $k => $v) {
				$prodFields[$k]['blockid'] = $blockid;
				$prodFields[$k]['fieldid'] = $i;
				$prodFields[$k]['sequence'] = $i++;
			}
			$blocks[] = array(
				'blockid' => $blockid, // 1 milione
				'type' => 'PRODUCTS',
				'module' => $module,
				'related_module' => 'Products',
				'tabid' => getTabId($module),
				'label' => getTranslatedString('Products', 'APP_STRINGS'), // uso traduzione da modulo collegato
				'fields' => Zend_Json::encode($prodFields),
				'actions' => '',
			);
		}

		$touchCache->set('blocks_invrel_'.$module, $blocks, $this->cacheLife);
		return $blocks;
	}

	protected function getExtraRelated($module) {
		global $current_user, $touchInst, $touchUtils, $touchCache;

		// check cache
		$cachedBlocks = $touchCache->get('blocks_extrarel_'.$module);
		if ($cachedBlocks) return $cachedBlocks;
		
		$blocks = array();

		// crmv@34559
		// COMMENTI TICKET
		if ($module == 'HelpDesk') {
			$relmod = 'ModComments';
			$rblock = array(
				'blockid' => $touchUtils->related_blockids['ticketcomments'] + getTabid($module), // id che parte da 1.5 milioni
				'type' => 'TICKETCOMMENTS',
				'module' => $module,
				'related_module' => $relmod ,
				'tabid' => getTabId($module),
				'label' => getTranslatedString('LBL_COMMENT_INFORMATION', $module),
			);
			$blocks[] = $rblock;
		}
		// crmv@34559e

		// COMMENTI
		if (!in_array('ModComments', $touchInst->excluded_modules) && ($linkid = $touchUtils->hasCommentsBlock($module))) {
			$relmod = 'ModComments';
			$rblock = array(
				'blockid' => $touchUtils->related_blockids['comments'] + getTabid($module), // id che parte da 2 milioni
				'type' => 'COMMENTS',
				'module' => $module,
				'related_module' => $relmod ,
				'tabid' => getTabId($module),
				'label' => getTranslatedString('Comments', $relmod), // uso traduzione da modulo collegato
			);
			$blocks[] = $rblock;
		}
		
		// MYNOTES
		if (!in_array('MyNotes', $touchInst->excluded_modules) && ($touchUtils->hasNotes($module))) {
			$relmod = 'MyNotes';
			$notesFields = array();
			
			$notesFields[] = array(
				'name' => 'subject',
				'label' => getTranslatedString('Subject', 'MyNotes'),
				'mandatory' => false,
				'type' => array(
					'name' => 'string',
				),
				'nullable' => true,
				'editable' => false,
				'uitype' => 1,
			);
			
			$notesFields[] = array(
				'name' => 'modifiedtime',
				'label' => getTranslatedString('Modified Time'),
				'mandatory' => true,
				'type' => array(
					'name' => 'datetime',
				),
				'nullable' => false,
				'editable' => false,
				'uitype' => 70,
			);

			$notesFields[] = array(
				'name' => 'description',
				'label' => getTranslatedString('Description', 'MyNotes'),
				'mandatory' => true,
				'type' => array(
					'name' => 'text',
				),
				'nullable' => false,
				'editable' => false,
				'uitype' => 21,
			);
			
			$rblock = array(
				'blockid' => $touchUtils->related_blockids['notes'] + getTabid($module),
				'type' => 'NOTES',
				'module' => $module,
				'related_module' => $relmod ,
				'tabid' => getTabId($module),
				'label' => getTranslatedString('MyNotes', $relmod),
				'fields' => Zend_Json::encode($notesFields),
				'actions' => array('ADD'),
			);
			$blocks[] = $rblock;
		}

		// PDFMaker
		if (!in_array('PDFMaker', $touchInst->excluded_modules) && ($linkid = $touchUtils->hasPDFMaker($module))) {
			$relmod = 'PDFMaker';
			$rblock = array(
				'blockid' => $touchUtils->related_blockids['pdfmaker'] + getTabid($module),
				'type' => 'PDFMAKER',
				'module' => $module,
				'related_module' => $relmod ,
				'tabid' => getTabId($module),
				'label' => getTranslatedString('PDFMaker', $relmod), // uso traduzione da modulo collegato
			);
			$blocks[] = $rblock;
		}

		// RELATED INVITEES (for Calendar only, before other related)
		if ($module == 'Events') {
			$lastrelid = intval($value['relationId']);
			$inviteesFields = array();

			$inviteesFields[] = array(
				'name' => 'participation',
				'label' => getTranslatedString('LBL_CAL_INVITATION', 'Calendar'),
				'mandatory' => false,
				'type' => array(
					'name' => 'string',		// boolean can only hold 2 values, but i need 3!
				),
				'nullable' => true,
				'editable' => false,
				'fieldid' => 10000,
				'uitype' => 1,
				'blockid' => 0,
				'sequence' => 1,
			);

			if (!in_array('Contacts', $touchInst->excluded_modules)) {
				$rblock = array(
					'blockid' => $touchUtils->related_blockids['invitees'],
					'type' => 'INVITEES',
					'module' => $module,
					'related_module' => 'Contacts',
					'tabid' => getTabId($module),
					'label' => getTranslatedString('LBL_INVITED_CONTACTS', 'Calendar'),
					'fields' => Zend_Json::encode($inviteesFields),
					'actions' => array('SELECT'),
					'related_tabid' => getTabid('Contacts'),
					'perm_delete' => true,
				);
				$blocks[] = $rblock;
			}

			$rblock = array(
				'blockid' => $touchUtils->related_blockids['invitees'] + 1,
				'type' => 'INVITEES',
				'module' => $module,
				'related_module' => 'Users',
				'tabid' => getTabId($module),
				'label' => getTranslatedString('LBL_INVITED_USERS', 'Calendar'),
				'fields' => Zend_Json::encode($inviteesFields),
				'actions' => array('SELECT'),
				'related_tabid' => getTabid('Users'),
				'perm_delete' => true,
			);
			$blocks[] = $rblock;
		}

		$touchCache->set('blocks_extrarel_'.$module, $blocks, $this->cacheLife);
		return $blocks;
	}

	protected function getStandardRelated($module, $recordid) {
		global $current_user, $touchInst, $touchUtils, $touchCache;

		// check cache
		$cachedBlocks = $touchCache->get('blocks_stdrel_'.$module);
		if ($cachedBlocks) return $cachedBlocks;
		
		$blocks = array();

		// RELATED
		//$related_array = getRelatedLists(($module == 'Events' ? 'Calendar' : $module),'');
		$related_array = getRelatedLists($module,'');
		if (empty($related_array)) $related_array = array();
		
		// crmv@177095
		// add fake related messages -> modules, otherwise related_ids won't be found
		if ($module == 'Messages') {
			$RM = RelationManager::getInstance();
			$rels = $RM->getRelations($module, ModuleRelation::$TYPE_NTON, array(), $touchInst->excluded_modules);
			foreach ($rels as $rel) {
				$relmod = $rel->getSecondModule();
				$related_array[$relmod] = array(
					'related_tabid' => getTabid($relmod),
					'name' => $rel->relationfn,
					'actions' => '',
					'relationId' => $rel->relationid
				);
			}
		}
		// crmv@177095e

		foreach ($related_array as $key => $value) {
			if (empty($value['related_tabid'])) continue;

			$relmod = vtlib_getModuleNameById($value['related_tabid']);
			if (empty($relmod) || in_array($relmod, $touchInst->excluded_modules)) continue;

			// ignoro azioni per get_history
			if ($value['name'] == 'get_history') {
				$actions = array();
			} else {
				$actions = array_filter(explode(',',strtoupper($value['actions'])));
			}

			// rimuovo select per get_dependents_list
			if ($value['name'] == 'get_dependents_list' && in_array('SELECT', $actions)) {
				$k = array_search('SELECT', $actions);
				unset($actions[$k]);
			}

			$actions = array_values($actions);

			// fix per calendar
			if ($module == 'Events' && $relmod == 'Contacts' && !in_array('SELECT', $actions)) {
				$actions[] = 'SELECT';
			}

			$relatedFields = $touchUtils->getRelatedFields($current_user->id, $module, $relmod, $value['relationId'], $recordid);

			$rblock = array(
				'blockid' => intval($value['relationId']) + $touchUtils->related_blockids['related'], // id che parte da 3 milioni
				'type' => 'RELATED',
				'module' => $module,
				'related_module' => $relmod,
				'tabid' => getTabId($module),
				'label' => getTranslatedString($key, $relmod), // uso traduzione da modulo collegato
				'fields' => Zend_Json::encode($relatedFields),
				'actions' => $actions,
				'related_tabid' => $value['related_tabid'],
				'perm_delete' => true,
			);

			// crmv@54449
			// add calendar for activities
			if ($module != 'Calendar' && $module != 'Events' && $relmod == 'Calendar') {
				$rblock2 = array(
					'blockid' => intval($value['relationId']) + $touchUtils->related_blockids['related_events'], // id che parte da 3.5 milioni
					'type' => 'RELATED',
					'module' => $module,
					'related_module' => 'Events',
					'tabid' => getTabId($module),
					'label' => getTranslatedString('Activities', $relmod),
					'fields' => Zend_Json::encode($relatedFields),
					'actions' => $actions,
					'related_tabid' => getTabId('Events'),
					'perm_delete' => true,
				);
				$blocks[] = $rblock2;
			
				$rblock['label'] = getTranslatedString('Tasks', 'APP_STRINGS');
			}
			// crmv@54449e
			
			$blocks[] = $rblock;
		}

		$touchCache->set('blocks_stdrel_'.$module, $blocks, $this->cacheLife);
		return $blocks;
	}


}