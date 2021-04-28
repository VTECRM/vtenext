<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $current_user, $currentModule;
if(isset($_REQUEST['dup_check']) && $_REQUEST['dup_check'] != ''){
	
	check_duplicate(vtlib_purify($_REQUEST['module']),
	vtlib_purify($_REQUEST['colnames']),vtlib_purify($_REQUEST['fieldnames']),
	vtlib_purify($_REQUEST['fieldvalues']));
	die;
}

$focus = CRMEntity::getInstance($currentModule);
setObjectValuesFromRequest($focus);

$mode = $_REQUEST['mode'];
$record=$_REQUEST['record'];
if($mode) $focus->mode = $mode;
if($record)$focus->id  = $record;

$currencyid=fetchCurrency($current_user->id);
$rate_symbol = getCurrencySymbolandCRate($currencyid);
$rate = $rate_symbol['rate'];

if($_REQUEST['imagelist'] != '')
{
	$del_images = array();
	$del_images = explode('###',$_REQUEST['imagelist']);
	$del_image_array = array_slice($del_images,0,count($del_images)-1);
}

//Checking If image is given or not 
$image_lists=array();
$count=0;

$saveimage = "true";
$image_error = "false";
//end of code to retain the pictures from db
	
if($_REQUEST['activity_mode'] != '') $activity_mode = vtlib_purify($_REQUEST['activity_mode']);
if($_REQUEST['return_module'] != '') { 
	$return_module = vtlib_purify($_REQUEST['return_module']);
} else {
	$return_module = $currentModule;
}
if($_REQUEST['return_action'] != '') {
	$return_action = vtlib_purify($_REQUEST['return_action']);
} else {
	$return_action = 'DetailView';
}
if($_REQUEST['return_id'] != '') $return_id = vtlib_purify($_REQUEST['return_id']);

if($image_error=="true") { //If there is any error in the file upload then moving all the data to EditView.

	//re diverting the page and reassigning the same values as image error occurs
	$log->debug("There is an error during the upload of product image.");
	$field_values_passed.="";
	foreach($focus->column_fields as $fieldname => $val) {
		if(isset($_REQUEST[$fieldname])) {
			$log->debug("Assigning the previous values given for the product to respective vte_fields ");
			$field_values_passed.="&";
			$value = $_REQUEST[$fieldname];
			$focus->column_fields[$fieldname] = $value;
			$field_values_passed.=$fieldname."=".$value;
		}
	}
	$values_pass=$field_values_passed;
	$encode_field_values=base64_encode($values_pass);

	$error_module = "Products";
	$error_action = "EditView";

	if($mode=="edit")
	{
		$return_id=$_REQUEST['record'];
	}
	header("location: index.php?action=$error_action&module=$error_module&record=$return_id&return_id=$return_id&return_action=$return_action&return_module=$return_module&activity_mode=$activity_mode&return_viewname=$return_viewname&saveimage=$saveimage&error_msg=$errormessage&image_error=$image_error&encode_val=$encode_field_values.$search");
}
if($saveimage=="true")
{
	$image_lists_db=implode("###",$image_lists);
	$focus->column_fields['imagename']=$image_lists_db;
	$log->debug("Assign the Image name to the vte_field name ");
}

if(isset($_REQUEST['return_id']) && $_REQUEST['return_id'] != '' && $_REQUEST['return_id'] != $focus->id)
	$focus->parentid = $_REQUEST['return_id'];
if(isset($_REQUEST['return_module']) && $_REQUEST['return_module']!='')
	$focus->return_module = $_REQUEST['return_module'];
if($_REQUEST['assigntype'] == 'U') {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_user_id'];
} elseif($_REQUEST['assigntype'] == 'T') {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_group_id'];
}

$focus->save($currentModule);
$return_id = $focus->id;

$search=vtlib_purify($_REQUEST['search_url']);

if($_REQUEST['return_id'] != '') $return_id = vtlib_purify($_REQUEST['return_id']);

$parenttab = getParentTab();

//crmv@54375
if($_REQUEST['return2detail'] == 'yes') {
	$return_module = $currentModule;
	$return_action = 'DetailView';
	$return_id = $focus->id;
}
//crmv@54375e

if(isset($_request['activity_mode']))
	$return_action .= '&activity_mode='.$activity_mode;

$url = "index.php?action=$return_action&module=$return_module&record=$return_id&parenttab=$parenttab&start=".vtlib_purify($_REQUEST['pagenumber']).$search;

$from_module = vtlib_purify($_REQUEST['module']);
if (!empty($from_module)) $url .= "&from_module=$from_module";

header("Location: $url");
?>