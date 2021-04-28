<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@89261 */
require_once('include/utils/utils.php');
require_once('modules/Picklistmulti/Picklistmulti_class.php');
require_once('modules/Picklistmulti/Picklistmulti_utils.php');
$success = false;
$message = '';
$response = array();
if ($_REQUEST['field'] == "" || $_REQUEST['field_module'] == ""){
	check_error('LBL_ERROR_GENERIC');
}
else {
	$field = $_REQUEST['field'];
	$module = $_REQUEST['field_module'];
}
switch($_REQUEST['oper']){
	case 'checkcode':{
		$pick_obj = new Picklistmulti(false,$module,$field);
		$code = $_REQUEST['value'];
		$code_system = $_REQUEST['code_system'];
		$mode = $_REQUEST['mode'];
		check_error($pick_obj->control_code_unique($code,$code_system,$mode));
	break;
	}	
	case 'edit':{
		$pick_obj = new Picklistmulti(false,$module,$field);
		$edit_arr['code_system'] = $_REQUEST['id'];
		$edit_arr['code'] = $_REQUEST['code'];
		foreach ($pick_obj->languages as $language){
			$edit_arr[$language['prefix']] = $_REQUEST[$language['prefix']];
		} 
		check_error($pick_obj->editline($edit_arr));
	break;
	}
	case 'add':{
		$pick_obj = new Picklistmulti(false,$module,$field);
		$edit_arr['code_system'] = $_REQUEST['id'];
		$edit_arr['code'] = $_REQUEST['code'];
		foreach ($pick_obj->languages as $language){
			$edit_arr[$language['prefix']] = $_REQUEST[$language['prefix']];
		} 
		check_error($pick_obj->addline($edit_arr));	
	break;
	}
	case 'del':{
		$pick_obj = new Picklistmulti(false,$module,$field);
		$edit_arr['code_system'] = $_REQUEST['id'];
		if (strpos($edit_arr['code_system'],",") !== false){
			$edit_arr['code_system'] = explode(",",$edit_arr['code_system']);
		}
		else $edit_arr['code_system'] = Array($edit_arr['code_system']);
		check_error($pick_obj->removeline($edit_arr));	
	break;
	}
	default:{
		$pick_obj = new Picklistmulti(false,$module,$field);
		$i=0;
		if (is_array($pick_obj->field['value'])){
			foreach ($pick_obj->field['value'] as $arr){
				$response['rows'][$i]['id']=$arr['code_system'];
			    $response['rows'][$i]['cell']=array_values($arr);
			    $i++;
			}
		}
		else {
			check_error('LBL_EMPTY');
		}		
	break;
	}
}
$response['status'] = true;
echo Zend_Json::encode($response);
die;

function check_error($val){
	if ($val !== true){
		$response = array('success'=>false,'message'=>getTranslatedString($val,'Picklistmulti'));
		echo Zend_Json::encode($response);
		die;
	}
}			
?>