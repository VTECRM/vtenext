<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@152701 */

global $log;
global $mod_strings, $app_strings;
global $currentModule;
global $adb, $table_prefix;
$focus = CRMEntity::getInstance($currentModule);
$smarty = new VteSmarty();
if(isset($_REQUEST['record'])) 
{
	$focus->retrieve_entity_info($_REQUEST['record'],"Fax");
	$log->info("Entity info successfully retrieved for DetailView.");
	$focus->id = $_REQUEST['record'];
	$query = 'select fax_flag,from_number,to_number from '.$table_prefix.'_faxdetails where faxid = ?';
	$result = $adb->pquery($query, array($focus->id));
    	$smarty->assign('FROM_FAX',$adb->query_result($result,0,'from_number'));	
	$to_fax = str_replace('###',', ',$adb->query_result($result,0,'to_number'));
	if (strlen($to_fax) > 100) $to_fax=substr($to_fax,0,100).".....";
	$smarty->assign('TO_FAX',to_html($to_fax));	
    	$smarty->assign('FAX_FLAG',$adb->query_result($result,0,'fax_flag'));	
	if($focus->column_fields['name'] != '')
	        $focus->name = $focus->column_fields['name'];
	else
		$focus->name = $focus->column_fields['subject'];
}
if(isset($_REQUEST['isDuplicate']) && $_REQUEST['isDuplicate'] == 'true') 
{
	$focus->id = "";
} 

//needed when creating a new fax with default values passed in 
if (isset($_REQUEST['contact_name']) && is_null($focus->contact_name)) 
{
	$focus->contact_name = $_REQUEST['contact_name'];
}
if (isset($_REQUEST['contact_id']) && is_null($focus->contact_id)) 
{
	$focus->contact_id = $_REQUEST['contact_id'];
}
if (isset($_REQUEST['opportunity_name']) && is_null($focus->parent_name)) 
{
	$focus->parent_name = $_REQUEST['opportunity_name'];
}
if (isset($_REQUEST['opportunity_id']) && is_null($focus->parent_id)) 
{
	$focus->parent_id = $_REQUEST['opportunity_id'];
}
if (isset($_REQUEST['account_name']) && is_null($focus->parent_name)) 
{
	$focus->parent_name = $_REQUEST['account_name'];
}
if (isset($_REQUEST['account_id']) && is_null($focus->parent_id)) 
{
	$focus->parent_id = $_REQUEST['account_id'];
}
if (isset($_REQUEST['parent_name'])) 
{
        $focus->parent_name = $_REQUEST['parent_name'];
}
if (isset($_REQUEST['parent_id'])) 
{
        $focus->parent_id = $_REQUEST['parent_id'];
}
if (isset($_REQUEST['parent_type'])) 
{
        $focus->parent_type = $_REQUEST['parent_type'];
}
if (isset($_REQUEST['filename']) && is_null($focus->filename)) 
{
        $focus->filename = $_REQUEST['filename'];
}
elseif (is_null($focus->parent_type)) 
{
        $focus->parent_type = $app_list_strings['record_type_default_key'];
}

global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$log->info("Fax detail view");

$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);

$smarty->assign("UPDATEINFO",updateInfo($focus->id));
if (isset($_REQUEST['return_module'])) $smarty->assign("RETURN_MODULE", $_REQUEST['return_module']);
if (isset($_REQUEST['return_action'])) $smarty->assign("RETURN_ACTION", $_REQUEST['return_action']);
if (isset($_REQUEST['return_id'])) $smarty->assign("RETURN_ID", $_REQUEST['return_id']);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);
$category = getParentTab();
$smarty->assign("CATEGORY",$category);

if (isset($focus->name)) $smarty->assign("NAME", $focus->name);
	else $smarty->assign("NAME", "");

$entries = getBlocks($currentModule,"detail_view",'',$focus->column_fields);
//print_r($entries[$mod_strings['LBL_FAX_INFORMATION']]['3']);die;
$entries[$mod_strings['LBL_FAX_INFORMATION']]['5'][$mod_strings['Description']]['value'] = from_html($entries[$mod_strings['LBL_FAX_INFORMATION']]['5'][$mod_strings['Description']]['value']);
//changed this to view description in all langauge - bharath
$smarty->assign("BLOCKS",$entries['LBL_FAX_INFORMATION']); 
$smarty->assign("SINGLE_MOD", 'Fax');

if(isPermitted("Fax","EditView",$_REQUEST['record']) == 'yes')
	$smarty->assign("EDIT_DUPLICATE","permitted");

if(isPermitted("Fax","Delete",$_REQUEST['record']) == 'yes')
	$smarty->assign("DELETE","permitted");

$smarty->assign("EDIT_PERMISSION",isPermitted("Fax","EditView",$_REQUEST['record']));

$smarty->assign("ID",$focus->id);

$check_button = Button_Check($module);
$check_button['EditView'] = 'no'; // crmv@152701
$smarty->assign("CHECK", $check_button);
	
//Constructing the Related Lists from here
$smarty->assign("MODULE",$currentModule);
$smarty->assign("SENDER",$fax_id);

$smarty->display("FaxDetailView.tpl");