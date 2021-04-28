<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@96450 crmv@104180 */

require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');

function dynaform_describe($processmakerid, $metaId, $options, $user){
	global $current_user;
	$current_user = vtws_preserveGlobal('current_user',$user);
	$app_strings = VTWS_PreserveGlobal::getGlobal('app_strings');

	$fields = array();
	$processDynaFormObj = ProcessDynaForm::getInstance();
	$blocks = $processDynaFormObj->getStructure($processmakerid, false, $metaId);
	if (!empty($blocks)) {
		foreach($blocks as $block) {
			if(is_array($block['fields']) && !empty($block['fields'])){ // crmv@179124
				foreach($block['fields'] as $field) {
					$newfield = array(
						"name"=>$field['fieldname'],	//TODO add $DFX-
						"label"=>$field['label'],
						"mandatory"=>($field['mandatory'] == 1)?true:false,
						"type"=>$processDynaFormObj->getFieldTypeDetails($field),
						"nullable"=>true,
						"editable"=>($field['mandatory'] == 1)?true:false,
						"fieldid"=>$field['fieldname'],
						"uitype"=>$field['uitype'],
						"blockid"=>$block['blocklabel'],
						"sequence"=>false
					);
					if ($newfield['type']['name'] == 'file') continue; //crmv@185786
					if ($newfield['type']['name'] == 'table') {
						// add the columns
						$newfield['columns'] = Zend_Json::decode($field['columns']) ?: array();
						foreach ($newfield['columns'] as &$col) {
							$col['name'] = $col['fieldname'];
							//$col['mandatory'] = ($col['mandatory'] == 1)?true:false;
							$col['nullable'] = true;
							$col['editable'] = ($col['mandatory'] == 1)?true:false;
							$col['type'] = $processDynaFormObj->getFieldTypeDetails($col);
							$col['fieldid'] = $col['fieldname'];
							$col['blockid'] = $field['fieldname'];
							$col['sequence'] = false;
						}
					}
					$fields[] = $newfield;
				}
			}
		}
	}
	$return = array(
		"label"=>(isset($app_strings['DynaForm'])) ? $app_strings['DynaForm'] : 'DynaForm',
		"name"=>'DynaForm',
		"createable"=>false,
		"updateable"=>false,
		"deleteable"=>false,
		"retrieveable"=>false,
		"fields"=>$fields,
		"idPrefix"=>false,
		'isEntity'=>false,
		'labelFields'=>false
	);
	return $return;
}