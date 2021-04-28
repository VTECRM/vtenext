<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 - restituisce la struttura (blocchi, campi, related) di un modulo*/
/* crmv@33097 - gestione multi-modulo per offline */
/* crmv@33311 - conversazioni nei moduli, altri fix */
/* crmv@71985 - maps on address blocks */

global $login, $userId, $current_user, $currentModule;
global $adb, $table_prefix;

// parametri
$module = $_REQUEST['module'];
$recordid = intval($_REQUEST['recordid']);

if (!$login || empty($userId)) {
	echo 'Login Failed';
} elseif ($module != 'ALL' && in_array($module, $touchInst->excluded_modules)) {
	echo "Module not permitted";
} else {

	$moduleslist = array();
	if ($module == 'ALL') {
		$moduleslist = touchModulesList();
	} else {
		$moduleslist[] = array('view'=>$module);
	}

	$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

	$allblocks = array();
	foreach ($moduleslist as $moduleinfo) {
		$module = $currentModule = $moduleinfo['view'];

		$response = wsRequest($userId,'describe', array('elementType'=> $module));

		if (!is_array($response) || !$response['success'] || !is_array($response['result']['fields'])) {
			continue;
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

		// riordino la risposta
		$blocks = array();
		foreach ($response['fields'] as $fld) {

			// nascondo alcuni campi
			if (isInventoryModule($module)) {
				if (in_array($fld['name'], array('txtAdjustment', 'subtotal', 'total', 'conversion_rate'))) continue;
				if (substr($fld['name'], 0, 3) == 'hdn') continue;
			}

			if ($module == 'Calendar') {
				// rimozione campi degli eventi
				if (in_array($fld['name'], array('reminder_time', 'recurringtype', 'eventstatus', 'time_end', 'activitytype', 'duration_hours', 'duration_minutes', 'visibility', 'location', 'notime', 'id'))) continue;
				// sposto tutti i campi non custom nel primo blocco
				if (!in_array($fld['blockid'], $cal_customblocks)) $fld['blockid'] = $cal_taskblock;
				// il soggetto è deve essere il primo campo
				if ($fld['name'] == 'subject') $fld['sequence'] = 0;
			} elseif ($module == 'Events') {
				// rimozione campi degli eventi
				if (in_array($fld['name'], array('reminder_time', 'recurringtype', 'taskstatus', 'notime', 'id', 'duration_hours', 'duration_minutes', 'contact_id'))) continue;
				// sposto tutti i campi non custom nel primo blocco
				if (!in_array($fld['blockid'], $cal_customblocks)) $fld['blockid'] = $cal_taskblock;
				// il soggetto è deve essere il primo campo
				if ($fld['name'] == 'subject') $fld['sequence'] = 0;
			}

			if ($module == 'HelpDesk') {
				if (in_array($fld['name'], array('update_log'))) continue;
			}

			// crmv@71388
			if ($module == 'MyNotes') {
				if (in_array($fld['name'], array('createdtime', 'modifiedtime'))) continue;
			}

			// Documenti and MyFiles
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
					$currpicklist[] = array('value' => $c['curid'], 'label' => $c['currencylabel']);
				}
				$fld['type'] = array(
					'name' => 'picklist',
					'defaultValue' => '',
					'picklistValues' => $currpicklist,
				);
				$fld['uitype'] = 15;
			}
			
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


		// PRODOTTI - simulo una related
		if (isInventoryModule($module)) {

			$blockid = 1000000; // 1 milione
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

		// crmv@34559
		// COMMENTI TICKET
		if ($module == 'HelpDesk') {
			$relmod = 'ModComments';
			$rblock = array(
				'blockid' => intval($linkid)+1500000, // id che parte da 1.5 milioni
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
		if (!in_array('ModComments', $touchInst->excluded_modules) && ($linkid = hasCommentsBlock($module))) {
			$relmod = 'ModComments';
			$rblock = array(
				'blockid' => intval($linkid)+2000000, // id che parte da 2 milioni
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
		if (!in_array('PDFMaker', $touchInst->excluded_modules) && ($linkid = hasPDFMaker($module))) {
			$relmod = 'PDFMaker';
			$rblock = array(
				'blockid' => 2500000,
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
					'blockid' => 2700000,
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
				'blockid' => 2700000+1,
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


		// RELATED
		$related_array = getRelatedLists(($module == 'Events' ? 'Calendar' : $module),'');

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

			$relatedFields = getRelatedFields($userId, $module, $relmod, $value['relationId'], $recordid);

			$rblock = array(
				'blockid' => intval($value['relationId'])+3000000, // id che parte da 3 milioni
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

			// calendar fix
			/*if ($relmod == 'Calendar') {
				if (preg_match('/history/i', $key)) {
					$rblock['label'] = getTranslatedString('Tasks', 'APP_STRINGS');
				} else {
					$rblock['label'] = getTranslatedString('Tasks', 'APP_STRINGS');
				}

			}*/
			$blocks[] = $rblock;
		}

		$allblocks = array_merge($allblocks, $blocks);
	}

	// output
	echo Zend_Json::encode($allblocks);
}
?>