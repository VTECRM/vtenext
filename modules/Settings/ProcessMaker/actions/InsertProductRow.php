<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@195745 */

require_once(dirname(__FILE__).'/Base.php');

class PMActionInsertProductRow extends PMActionBase {
	
	function edit(&$smarty,$id,$elementid,$retrieve,$action_type,$action_id='') {
	
		require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
	
		$PMUtils = ProcessMakerUtils::getInstance();
		
		require_once(dirname(__FILE__).'/Create.php');
		$action = new PMActionCreate();
		$action->edit($smarty,$id,$elementid,$retrieve,$action_type,$action_id);

		list($metaid, $fieldid, $relatedmodule, $fieldname) = explode(':', $_REQUEST['insertproductrow_inventory_fields']);
		
		$FM = new FakeModules();
		$fieldlabel = $FM->getModuleLabel('ProductsBlock');
		$fields = $FM->getFields('ProductsBlock');
		// remove some fields
		unset($fields['id'], $fields['total_notaxes'], $fields['linetotal'], $fields['tax_total']); // crmv@206272

		// alter a bit the structure to be ready for the tpl
		foreach ($fields as &$field) {
			if ($field['wstype'] == 'reference') {
				$field['other'] = array("relatedmods" => implode(',', $field['relmodules']));
			}
		}
		unset($field); // crmv@206272
		
		// this should be before the getOutputHtml
		$_REQUEST['enable_editoptions'] = 'yes';
		
		$editoptionsfieldnames = array();
		$picklist_values = array();
		$reference_values = array();
		$reference_users_values = array();	// ex. uitypes 52
		$boolean_values = array();
		$date_values = array();

		$col_fields = array();
		$metadata = $PMUtils->getMetadata($id,$elementid);
		$col_fields = $metadata['actions'][$action_id];
		unset($col_fields['conditions']);
		
		if (empty($col_fields['productid']) && !empty($col_fields['other_productid'])) {
			$col_fields['productid'] = $col_fields['other_productid'];
		}

		$maxlength = 100;
		$generatedtype = 1;
		$module = 'ProductsBlock';
		$mode = 'edit';
		$readonly = 1;
		
		$custFldArray = array();
		$fieldid = 1;
		foreach($fields as $field) {
			$uitype = $field['uitype'];
			if (in_array($uitype,array(7,9,71,72))) $uitype = 1;	//crmv@96450
			if (substr($field['fieldname'], 0, 3) === 'tax') $uitype = 9;
			$custfld = getOutputHtml($uitype, $field['fieldname'], $field['label'], $maxlength, $col_fields, $generatedtype, $module, $mode, $readonly, $field['typeofdata'], $field['other']);
			$custfld[] = $fieldid;

			$custFldArray[] = $custfld;
			$fieldid++;
			
			$editoptionsfieldnames[] = $field['fieldname'];
			if ($field['wstype'] == 'picklist') {
				$picklist_values[$field['fieldname']] = $field;
			} elseif ($field['wstype'] == 'reference') {
				if (in_array('Users', $field['relmodules'])) {
					unset($field['relmodules']);
					$reference_users_values[$field['fieldname']] = $field;
				} else {
					unset($field['relmodules']);
					$reference_values[$field['fieldname']] = $field;
				}
			} elseif ($field['wstype'] == 'boolean') {
				$boolean_values[$field['fieldname']] = $field;
			} elseif (in_array($field['wstype'], ['date', 'datetime', 'time'])) {
				$date_values[$field['fieldname']] = $field;
			}
			
		}

		$smarty->assign('BLOCKS', array(
			array(
				'label'=>getTranslatedString('LBL_ITEM_DETAILS'),
				'blockid'=>1,
				'fields'=>array('LBL_ITEM_DETAILS'=>$custFldArray),
			)
		));
		$smarty->assign('HIDE_BUTTON_LIST',1);
		
		$smarty->assign('INSERT_PBLOCKROW_FIELD', $_REQUEST['insertproductrow_inventory_fields']);
 		$smarty->assign('INSERT_TABLEROW_LABEL', getTranslatedString('LBL_INSERT_ON_TABLE_FIELD','Settings').' '.$fieldlabel.' '.getTranslatedString('LBL_LIST_OF').' '.$PMUtils->getRecordsInvolvedLabel($id,$metaid,$row));
 		
 		$helpinfo = array(
			'discount' => getTranslatedString('LBL_PM_DISCOUNT_FIELD_INFO', 'Settings'),
 		);
 		$smarty->assign('FIELDHELPINFO', $helpinfo);
 		
		$_REQUEST['editoptionsfieldnames'] = implode('|',$editoptionsfieldnames);
		
		$dynaform_options = array();
		$processDynaFormObj = ProcessDynaForm::getInstance();
		$dynaform_options = $processDynaFormObj->getFieldsOptions($id,true);
		$PMUtils->getAllTableFieldsOptions($id, $dynaform_options);
		$PMUtils->getAllPBlockFieldsOptions($id, $dynaform_options);
 		
 		$smarty->assign('EDITOPTIONSPARAMS', addslashes(Zend_Json::encode(array(	//crmv@135190
			'processid'=>$id,
			'involved_records'=>$PMUtils->getRecordsInvolved($id,true),
			'form_data'=>[],
			'picklist_values'=>$picklist_values,
			'reference_values'=>$reference_values,
			'reference_users_values'=>$reference_users_values,
			'boolean_values'=>$boolean_values,
			'date_values'=>$date_values,
			'dynaform_options'=>$dynaform_options,
			'elements_actors'=>$PMUtils->getElementsActors($id)
			// TODO extws_options
		))));
	}
	
	function execute($engine,$actionid) {
		global $adb, $table_prefix;
		$action = $engine->vte_metadata['actions'][$actionid];
		
		(!empty($this->cycleRow['id'])) ? $cycleIndex = $this->cycleRow['id'] : $cycleIndex = $this->cycleIndex;
		
		list($metaid, $fieldid, $relatedmodule, $fieldname) = explode(':', $action['insertpblockrow_field']);
		
		$parentId = $engine->getCrmid($metaid, $engine->running_process); //crmv@182891
		$parentModule = getSalesEntityType($parentId);

		if (!isInventoryModule($parentModule)) {
			$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} FAILED module $parentModule is not inventory");
			return;
		}
		
		$newprod = array(
			'hdnProductId' => is_numeric($action['productid']) ? $action['productid'] : $action['other_productid'],
			'qty' => $action['quantity'],
			'listPrice' => $action['listprice'],
			'productDescription' => $action['description'],
			'comment' => $action['comment'],
			'discount_type' => '', // done later
			'discount' => $action['discount'], // to replace the tag
		);
		
		$referenceFields = array('hdnProductId');
		$ownerFields = array();

		foreach ($newprod as $fieldname=>&$value) {
			$value = $engine->replaceTags($fieldname,$value,$referenceFields,$ownerFields,$actionid,$cycleIndex);
		}
		
		// fill the product id type in case the productid was a tag
		if (is_numeric($newprod['hdnProductId'])) {
			$newprod['entityType'] = getSalesEntityType($newprod['hdnProductId']);
		}

		// handle discount
		if ($newprod['discount'] != '') {
			if (strpos($newprod['discount'], '%') === false) {
				$newprod['discount_type'] = 'amount';
				$newprod['discount_amount'] = trim($newprod['discount']);
			} else {
				$newprod['discount_type'] = 'percentage';
				$newprod['discount_percent'] = trim(str_replace('%', '', $newprod['discount']));
			}
		}
		unset($newprod['discount']);
		
		// handle taxes
		$taxes = array();
		foreach ($action as $k => $val) {
			if ($val !== '' && substr($k, 0, 3) === 'tax') {
				$taxes[] = array(
					'taxname' => $k,
					'percentage' => $val,
				);
			}
		}
		if (count($taxes) > 0) {
			$newprod['taxes'] = $taxes;
		}
		
		$IU = InventoryUtils::getInstance();
		$IU->addProductToRecord($parentModule, $parentId, $newprod);
		
		$engine->log("Action {$action['action_type']}","action $actionid - {$action['action_title']} completed");
	}

	
}