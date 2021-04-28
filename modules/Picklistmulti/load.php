<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 /* crmv@89261 */
require_once 'include/utils/CommonUtils.php';
require_once('modules/Picklistmulti/Picklistmulti_class.php');
require_once('modules/Picklistmulti/Picklistmulti_utils.php');
$page = $_GET['page']; // get the requested page
$limit = $_GET['rows']; // get how many rows we want to have into the grid
$sidx = $_GET['sidx']; // get index row - i.e. user click to sort
$sord = $_GET['sord']; // get the direction
$start = $limit*$page - $limit; // do not put $limit*($page - 1)
if(!$sidx) $sidx =1;
if ($_REQUEST['field'] == "" || $_REQUEST['field_module'] == ""){
	echo Zend_Json::encode(Array());
	die;
}
else {
	$field = $_REQUEST['field'];
	$module = $_REQUEST['field_module'];
}
if (!is_numeric($limit)){
	$pick_obj = new Picklistmulti(true,$module,$field);
	$limit = $pick_obj->total;
	$page = 1;
}	
else 
	$pick_obj = new Picklistmulti(true,$module,$field,Array($start,$limit));	
$i=0;
$response = array();
if (is_array($pick_obj->field['value'])){
	foreach ($pick_obj->field['value'] as $arr){
		$response['rows'][$i]['id']=$arr['code_system'];
	    $response['rows'][$i]['cell']=array_values($arr);
	    $i++;
	}
}
else {
	$response['success'] = false;
	$response['message'] = 'LBL_EMPTY';
}
if ($pick_obj->total > 0)
	$total_pages = ceil($pick_obj->total/$limit);
else
	$total_pages = 0; 	 
if ($page > $total_pages) 
	$page=$total_pages; 
	
$response['page'] = $page;	
$response['total'] = $total_pages;	
$response['records'] = $pick_obj->total;
//die('fatto');
echo Zend_Json::encode($response);
die;
?>