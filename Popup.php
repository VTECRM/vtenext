<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once("data/Tracker.php");
require_once('include/logging.php');
require_once('include/ListView/ListView.php');
require_once('include/database/PearDatabase.php');
require_once('include/ComboUtil.php');
require_once('include/utils/utils.php');
global $app_strings,$app_list_strings; //DS-CR VlMe 10.4.2008 - add $app_list_strings 
global $currentModule;
global $theme;
global $table_prefix;
$url_string = '';

if (VteSession::isEmpty('authenticated_user_id')) die('Unauthorized!'); // crmv@37463

$LVU = ListViewUtils::getInstance();

$smarty = new VteSmarty();
if (!isset($where)) $where = "";

if(isset($_REQUEST['parenttab']) && $_REQUEST['parenttab']){
$parent_tab=$_REQUEST['parenttab'];
$smarty->assign("CATEGORY",$parent_tab);}

$url = '';
$popuptype = '';
$popuptype = $_REQUEST["popuptype"];

//crmv@137077
require_once('include/utils/ModuleHomeView.php');
$MHW = ModuleHomeView::getInstance($currentModule, $current_user->id);
$MHW->setModHomeListId();
//crmv@137077e

//added to get relatedto field value for todo, while selecting from the popup list, after done the alphabet or basic search.
if(isset($_REQUEST['maintab']) && $_REQUEST['maintab'] != '')
{
        $act_tab = $_REQUEST['maintab'];
        $url = "&maintab=".$act_tab;
}
$smarty->assign("MAINTAB",$act_tab);

switch($currentModule)
{
	case 'Contacts':
		$focus = CRMEntity::getInstance($currentModule);
		$log = LoggerManager::getLogger('contact_list');
		$smarty->assign("SINGLE_MOD",'Contact');
		if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] !='')
			$smarty->assign("RETURN_MODULE",$_REQUEST['return_module']);
		else
			$smarty->assign("RETURN_MODULE",'Emails');
		if (isset($_REQUEST['select'])) $smarty->assign("SELECT",'enable');
		if (isset($_REQUEST['form'])) $smarty->assign("FORM",$_REQUEST['form']); //crmv@138218
		$alphabetical = $LVU->AlphabeticalSearch($currentModule,'Popup','lastname','true','basic',$popuptype,"","",$url);
		break;
	case 'Campaigns':
		$focus = CRMEntity::getInstance($currentModule);
		$log = LoggerManager::getLogger('campaign_list');
		$smarty->assign("SINGLE_MOD",'Campaign');
		if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] !='')
			$smarty->assign("RETURN_MODULE",$_REQUEST['return_module']);
		if (isset($_REQUEST['select'])) $smarty->assign("SELECT",'enable');
		$alphabetical = $LVU->AlphabeticalSearch($currentModule,'Popup','campaignname','true','basic',$popuptype,"","",$url);
		break;
	case 'Accounts':
		$focus = CRMEntity::getInstance($currentModule);
		$log = LoggerManager::getLogger('account_list');
		if (isset($_REQUEST['select'])) $smarty->assign("SELECT",'enable');
		$smarty->assign("SINGLE_MOD",'Account');
		if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] !='')
			$smarty->assign("RETURN_MODULE",$_REQUEST['return_module']);
		else
			$smarty->assign("RETURN_MODULE",'Emails');
		$alphabetical = $LVU->AlphabeticalSearch($currentModule,'Popup','accountname','true','basic',$popuptype,"","",$url);
		break;
	case 'Leads':
		$focus = CRMEntity::getInstance($currentModule);
		$log = LoggerManager::getLogger('contact_list');
		$smarty->assign("SINGLE_MOD",'Lead');
		if (isset($_REQUEST['select'])) $smarty->assign("SELECT",'enable');
		if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] !='')
			$smarty->assign("RETURN_MODULE",$_REQUEST['return_module']);
		else
			$smarty->assign("RETURN_MODULE",'Emails');
		$alphabetical = $LVU->AlphabeticalSearch($currentModule,'Popup','lastname','true','basic',$popuptype,"","",$url);
		break;
	case 'Potentials':
		$focus = CRMEntity::getInstance($currentModule);
		$log = LoggerManager::getLogger('potential_list');
		if (isset($_REQUEST['select'])) $smarty->assign("SELECT",'enable');
		$smarty->assign("SINGLE_MOD",'Opportunity');
		if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] !='')
			$smarty->assign("RETURN_MODULE",$_REQUEST['return_module']);
		$alphabetical = $LVU->AlphabeticalSearch($currentModule,'Popup','potentialname','true','basic',$popuptype,"","",$url);
		break;
	case 'Quotes':
		$focus = CRMEntity::getInstance($currentModule);
		$log = LoggerManager::getLogger('quotes_list');
		$smarty->assign("SINGLE_MOD",'Quote');
		//crmv@14492
		if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] !='')
			$smarty->assign("RETURN_MODULE",$_REQUEST['return_module']);	
		//crmv@14492 end			
		$alphabetical = $LVU->AlphabeticalSearch($currentModule,'Popup','subject','true','basic',$popuptype,"","",$url);
		break;
	case 'Invoice':
		$focus = CRMEntity::getInstance($currentModule);
		$smarty->assign("SINGLE_MOD",'Invoice');
		if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] !='')
			$smarty->assign("RETURN_MODULE",$_REQUEST['return_module']);
		$alphabetical = $LVU->AlphabeticalSearch($currentModule,'Popup','subject','true','basic',$popuptype,"","",$url);
		break;
	case 'Products':
		$focus = CRMEntity::getInstance($currentModule);
		$smarty->assign("SINGLE_MOD",'Product');
		if(isset($_REQUEST['curr_row']))
		{
			$curr_row = $_REQUEST['curr_row'];
			$smarty->assign("CURR_ROW", $curr_row);
			$url_string .="&curr_row=".$_REQUEST['curr_row'];
		}
		if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] !='')
			$smarty->assign("RETURN_MODULE",$_REQUEST['return_module']);
		if (isset($_REQUEST['select'])) $smarty->assign("SELECT",'enable');	
		$alphabetical = $LVU->AlphabeticalSearch($currentModule,'Popup','productname','true','basic',$popuptype,"","",$url);
		break;
	case 'Vendors':
		$focus = CRMEntity::getInstance($currentModule);
		$smarty->assign("SINGLE_MOD",'Vendor');
		//denis - senza le related dei fornitori non lasciano selezionare nulla
		if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] !='')
			$smarty->assign("RETURN_MODULE",$_REQUEST['return_module']);
		//denis e
		if (isset($_REQUEST['select'])) $smarty->assign("SELECT",'enable');//crmv@22366
		$alphabetical = $LVU->AlphabeticalSearch($currentModule,'Popup','vendorname','true','basic',$popuptype,"","",$url);
		break;
	case 'SalesOrder':
		$focus = CRMEntity::getInstance($currentModule);
		$smarty->assign("SINGLE_MOD",'SalesOrder');
		if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] !='')
			$smarty->assign("RETURN_MODULE",$_REQUEST['return_module']);
		$alphabetical = $LVU->AlphabeticalSearch($currentModule,'Popup','subject','true','basic',$popuptype,"","",$url);
		break;
	case 'PurchaseOrder':
		$focus = CRMEntity::getInstance($currentModule);
		$smarty->assign("SINGLE_MOD",'PurchaseOrder');
		if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] !='')
			$smarty->assign("RETURN_MODULE",$_REQUEST['return_module']);
		$alphabetical = $LVU->AlphabeticalSearch($currentModule,'Popup','subject','true','basic',$popuptype,"","",$url);
		break;
	case 'PriceBooks':
		$focus = CRMEntity::getInstance($currentModule);
		$smarty->assign("SINGLE_MOD",'PriceBook');
		if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] !='')
			$smarty->assign("RETURN_MODULE",$_REQUEST['return_module']);
		if(isset($_REQUEST['fldname']) && $_REQUEST['fldname'] !='')
		{
			$smarty->assign("FIELDNAME",$_REQUEST['fldname']);
			$url_string .="&fldname=".$_REQUEST['fldname'];
		}
		if(isset($_REQUEST['productid']) && $_REQUEST['productid'] !='')
		{
			$smarty->assign("PRODUCTID",$_REQUEST['productid']);
			$url_string .="&productid=".$_REQUEST['productid'];
		}
		$alphabetical = $LVU->AlphabeticalSearch($currentModule,'Popup','bookname','true','basic',$popuptype,"","",$url);
		break;
	case 'Users':
		require_once("modules/$currentModule/Users.php");
		$focus = CRMEntity::getInstance('Users');
		$smarty->assign("SINGLE_MOD",'Users');
		if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] !='')
			$smarty->assign("RETURN_MODULE",$_REQUEST['return_module']);
		$alphabetical = $LVU->AlphabeticalSearch($currentModule,'Popup','user_name','true','basic',$popuptype,"","",$url);
		if (isset($_REQUEST['select'])) $smarty->assign("SELECT",'enable');
		break;
	case 'HelpDesk':
		$focus = CRMEntity::getInstance($currentModule);
		$smarty->assign("SINGLE_MOD",'HelpDesk');
		if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] !='')
			$smarty->assign("RETURN_MODULE",$_REQUEST['return_module']);
		$alphabetical = $LVU->AlphabeticalSearch($currentModule,'Popup','ticket_title','true','basic',$popuptype,"","",$url);
		if (isset($_REQUEST['select'])) $smarty->assign("SELECT",'enable');
		break;
	case 'Documents':
		$focus = CRMEntity::getInstance($currentModule);
		$smarty->assign("SINGLE_MOD",'Note');
		if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] !='')
			$smarty->assign("RETURN_MODULE",vtlib_purify($_REQUEST['return_module']));
		else
			$smarty->assign("RETURN_MODULE",'Emails');
		if (isset($_REQUEST['select'])) $smarty->assign("SELECT",'enable');
		$alphabetical = $LVU->AlphabeticalSearch($currentModule,'Popup','notes_title','true','basic',$popuptype,"","",$url);
		break;		
	case 'Workflow':
		$focus = CRMEntity::getInstance($currentModule);
		$smarty->assign("SINGLE_MOD",'Workflow');
		if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] !='')
		$smarty->assign("RETURN_MODULE",$_REQUEST['return_module']);
		$alphabetical = $LVU->AlphabeticalSearch($currentModule,'Popup','phrase','true','basic',$popuptype,"","","");
		if (isset($_REQUEST['select'])) $smarty->assign("SELECT",'enable');
		break;
	//ds@26
	case 'Visitreport':
		$focus = CRMEntity::getInstance($currentModule);
		$smarty->assign("SINGLE_MOD",'Visitreport');
		if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] !='')
		$smarty->assign("RETURN_MODULE",$_REQUEST['return_module']);
		$alphabetical = $LVU->AlphabeticalSearch($currentModule,'Popup','crmid','true','basic',$popuptype,"","","");
		if (isset($_REQUEST['select'])) $smarty->assign("SELECT",'enable');
	break;
	//ds@26e
	// Special case handling (for curr_row value) for Services module
	case 'Services':
		if(isset($_REQUEST['curr_row']))
		{
			$curr_row = vtlib_purify($_REQUEST['curr_row']);
			$smarty->assign("CURR_ROW", $curr_row);
			$url_string .="&curr_row=".vtlib_purify($_REQUEST['curr_row']);
		}	
	//crmv@26265
	case 'Calendar';
		$result = $adb->query("SELECT cvid FROM ".$table_prefix."_customview WHERE entitytype = 'Calendar' AND viewname = 'Tasks' AND status = 0");
		if ($result && $adb->num_rows($result)) {
			$smarty->assign("CVID_TASKS",$adb->query_result($result,0,'cvid'));
		}
	//crmv@26265e
	// vtlib customization: Generic hook for Popup selection
	default:
		$focus = CRMEntity::getInstance($currentModule);
		$smarty->assign("SINGLE_MOD", $currentModule);		
		if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] !='')
			$smarty->assign("RETURN_MODULE",vtlib_purify($_REQUEST['return_module']));
		$alphabetical = $LVU->AlphabeticalSearch($currentModule,'Popup',$focus->def_basicsearch_col,'true','basic',$popuptype,"","",$url);	//crmv@21249
		if (isset($_REQUEST['select'])) $smarty->assign("SELECT",'enable');
		break;
	// END	
}
// vtlib customization: Initialize focus to get generic popup
if($_REQUEST['form'] == 'vtlibPopupView') {
	vtlib_setup_modulevars($currentModule, $focus);
}
// END
$smarty->assign("RETURN_ACTION",$_REQUEST['return_action']);


$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("APPLIST", $app_list_strings["moduleList"]); //DS-CR VlMe 10.4.2008
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("THEME_PATH",$theme_path);
$smarty->assign("MODULE",$currentModule);


//Retreive the list from Database
if($currentModule == 'PriceBooks')
{
	// crmv@37463
	$productid=intval($_REQUEST['productid']);
	$currency_id=intval($_REQUEST['currencyid']);
	// crmv@37463e
	if($currency_id == null) $currency_id = fetchCurrency($current_user->id);
	$query = 'select '.$table_prefix.'_pricebook.*, '.$table_prefix.'_pricebookproductrel.productid, '.$table_prefix.'_pricebookproductrel.listprice, ' .
					''.$table_prefix.'_crmentity.crmid, '.$table_prefix.'_crmentity.smownerid, '.$table_prefix.'_crmentity.modifiedtime ' .
					'from '.$table_prefix.'_pricebook inner join '.$table_prefix.'_pricebookproductrel on '.$table_prefix.'_pricebookproductrel.pricebookid = '.$table_prefix.'_pricebook.pricebookid ' .
					'inner join '.$table_prefix.'_crmentity on '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_pricebook.pricebookid ' .
					'where '.$table_prefix.'_pricebookproductrel.productid='.$adb->sql_escape_string($productid).' and '.$table_prefix.'_crmentity.deleted=0 ' .
							'and '.$table_prefix.'_pricebook.currency_id='.$adb->sql_escape_string($currency_id).' and '.$table_prefix.'_pricebook.active=1';
	//crmv@21387
	if($productid != '') {
		$construct_query=false;
	} else {
		$construct_query=true;
	}
	//crmv@21387e
}
else
{
	// crmv@37463
	$recordid = intval($_REQUEST['recordid']);
	$recordidAlt = intval($_REQUEST['record_id']);
	$construct_query=true;
	if($recordid > 0)
	{		
		$smarty->assign("RECORDID",$recordid);
		$url_string .='&recordid='.$recordid;
        $where_relquery = $LVU->getRelCheckquery($currentModule,$_REQUEST['return_module'],$recordid);
	}
	// crmv@37463e
	if ($_REQUEST['autocomplete'] != 'yes') {	//crmv@29190
		if(isset($_REQUEST['relmod_id']) || isset($_REQUEST['fromPotential']))
		{
			if($_REQUEST['relmod_id'] !='')
			{
				$mod = $_REQUEST['parent_module'];
				$id = $_REQUEST['relmod_id'];
			}
			else if($_REQUEST['fromPotential'] != '')
			{
				$mod = "Accounts";
				$id= $_REQUEST['acc_id'];
			}
	
			$smarty->assign("mod_var_name", "parent_module");
			$smarty->assign("mod_var_value", $mod);
			$smarty->assign("recid_var_name", "relmod_id");
			$smarty->assign("recid_var_value",$id);
			$where_relquery.= $LVU->getPopupCheckquery($currentModule,$mod,$id);
		}
		else if(isset($_REQUEST['task_relmod_id']))
		{
			$smarty->assign("mod_var_name", "task_parent_module");
			$smarty->assign("mod_var_value", $_REQUEST['task_parent_module']);
			$smarty->assign("recid_var_name", "task_relmod_id");
			$smarty->assign("recid_var_value",$_REQUEST['task_relmod_id']);
			$where_relquery.= $LVU->getPopupCheckquery($currentModule,$_REQUEST['task_parent_module'],$_REQUEST['task_relmod_id']);
		}
	}	//crmv@29190
    //sk@2
	if($currentModule == 'HelpDesk' && trim($_REQUEST['stopids'],":")!=''){
	    $where_relquery .= " and ".$table_prefix."_troubletickets.ticketid not in (".str_replace(":",",",trim($_REQUEST['stopids'],":")).")";
	}
	//sk@2e
	if($currentModule == 'Products' && !$_REQUEST['record_id'] && ($popuptype == 'inventory_prod' || $popuptype == 'inventory_prod_po'))
       		$where_relquery .=" and ".$table_prefix."_products.discontinued <> 0 AND (".$table_prefix."_products.productid NOT IN (SELECT crmid FROM ".$table_prefix."_seproductsrel WHERE setype='Products'))";
	elseif($currentModule == 'Products' && $_REQUEST['record_id'] && ($popuptype == 'inventory_prod' || $popuptype == 'inventory_prod_po'))
        	$where_relquery .=" and ".$table_prefix."_products.discontinued <> 0 AND (".$table_prefix."_products.productid IN (SELECT crmid FROM ".$table_prefix."_seproductsrel WHERE setype='Products' AND productid=".$adb->sql_escape_string($_REQUEST['record_id'])."))";
	elseif($currentModule == 'Products' && $_REQUEST['return_module'] != 'Products')
       		$where_relquery .=" and ".$table_prefix."_products.discontinued <> 0";
       		
	if($_REQUEST['return_module'] == 'Products' && $currentModule == 'Products' && $_REQUEST['recordid'])
       	$where_relquery .=" and ".$table_prefix."_products.discontinued <> 0 AND (".$table_prefix."_crmentity.crmid NOT IN (".$adb->sql_escape_string($_REQUEST['recordid']).") AND ".$table_prefix."_crmentity.crmid NOT IN (SELECT productid FROM ".$table_prefix."_seproductsrel WHERE setype='Products') AND ".$table_prefix."_crmentity.crmid NOT IN (SELECT crmid FROM ".$table_prefix."_seproductsrel WHERE setype='Products' AND productid=".$adb->sql_escape_string($_REQUEST['recordid'])."))";
	
	if($currentModule == 'Services' && $popuptype == 'inventory_service') {
		$where_relquery .=" and ".$table_prefix."_service.discontinued <> 0";
	}
	 
	//Avoiding Current Record to show up in the popups When editing.
	if($currentModule == 'Accounts' && $_REQUEST['recordid']!=''){
		$where_relquery .=" and ".$table_prefix."_account.accountid!=".$adb->sql_escape_string($_REQUEST['recordid']);
		$smarty->assign("RECORDID",vtlib_purify($_REQUEST['recordid']));
	}
	
	if($currentModule == 'Contacts' && $_REQUEST['recordid']!=''){
		$where_relquery .=" and ".$table_prefix."_contactdetails.contactid!=".$adb->sql_escape_string($_REQUEST['recordid']);
		$smarty->assign("RECORDID",vtlib_purify($_REQUEST['recordid']));
	}
	
	if($currentModule == 'Users' && $_REQUEST['recordid']!=''){
		$where_relquery .=" and ".$table_prefix."_users.id!=".$adb->sql_escape_string($_REQUEST['recordid']);
		$smarty->assign("RECORDID",vtlib_purify($_REQUEST['recordid']));
	}
}
if($currentModule == 'Products' && $_REQUEST['record_id'] && ($popuptype == 'inventory_prod' || $popuptype == 'inventory_prod_po'))
{
	$product_name = getProductName($_REQUEST['record_id']);
	$smarty->assign("PRODUCT_NAME", $product_name);
	$smarty->assign("RECORD_ID", vtlib_purify($_REQUEST['record_id']));
}			
//crmv@24577
if(isset($_REQUEST['query']) && $_REQUEST['reset_query'] != 'true'){
//crmv@24577e	
	list($where, $ustring) = explode("#@@#",getWhereCondition($currentModule));
	$url_string .="&query=true".$ustring;
}

if ($construct_query){
	if(isset($where) && $where != '')
	{
		$where_relquery .= ' and '.$where;
	}
	$query = $LVU->getListQuery($currentModule,$where_relquery);
	$query_count = $LVU->getListQuery($currentModule,$where_relquery);	
}
else {
	if(isset($where) && $where != '')
	{
		$where_relquery .= ' and '.$where;
	}
	$query .= $where_relquery;
	$query_count = $query.$where_relquery;
}

// vtlib customization: To override module specific popup query for a given field
$override_query = false;
if(method_exists($focus, 'getQueryByModuleField')) {
	$override_query = $focus->getQueryByModuleField(vtlib_purify($_REQUEST['srcmodule']), vtlib_purify($_REQUEST['forfield']), vtlib_purify($_REQUEST['forrecord']), $query);
	if($override_query) {
		$query = $override_query;
	}
}
// END
//crmv@sdk-24186	//crmv@26265
$sdk_show_all_button = false;
$sdk_view_all = false;
if ($_REQUEST['sdk_view_all'] != '') {
	$sdk_view_all = true;
}
if (!$sdk_view_all) {
	$sdk_file = SDK::getPopupQuery('field',vtlib_purify($_REQUEST['srcmodule']),vtlib_purify($_REQUEST['forfield']));
	if ($sdk_file != '' && Vtecrm_Utils::checkFileAccess($sdk_file)) {//crmv@198038
		include($sdk_file);
		//crmv@26920
		$sdk_params = SDK::getPopupHiddenElements(vtlib_purify($_REQUEST['srcmodule']), vtlib_purify($_REQUEST['forfield']), true);
		$smarty->assign('SDK_POPUP_PARAMS', $sdk_params);
		//crmv@26920e
	}
	$sdk_file = SDK::getPopupQuery('related',vtlib_purify($_REQUEST['return_module']),vtlib_purify($_REQUEST['module']));
	if ($sdk_file != '' && Vtecrm_Utils::checkFileAccess($sdk_file)) {//crmv@198038
		include($sdk_file);
	}
}
$smarty->assign('sdk_show_all_button', $sdk_show_all_button);
$smarty->assign('sdk_view_all', $sdk_view_all);
//crmv@sdk-24186e	//crmv@26265e
//crmv@11597
//------------------------------------------------------------------------------------------------------------------------------------------
//crmv@popup customview
// Enabling Module Search
$url_string = '';
//crmv@24577
if($_REQUEST['query'] == 'true' && $_REQUEST['reset_query']!= 'true') {
//crmv@24577e	
	list($where, $ustring, $add_join) = explode('#@@#', getWhereCondition($currentModule));	//crmv@131239
	$url_string .= "&query=true$ustring";
	$smarty->assign('SEARCH_URL', $url_string);
}
// Custom View
$customView = CRMEntity::getInstance('CustomView',$currentModule);
$viewid = $customView->getViewId($currentModule);
$customview_html = $customView->getCustomViewCombo($viewid);
$viewnamedesc = $customView->getCustomViewByCvid($viewid);

// Feature available from 5.1
if(method_exists($customView, 'isPermittedChangeStatus')) {
	// Approving or Denying status-public by the admin in CustomView
	$statusdetails = $customView->isPermittedChangeStatus($viewnamedesc['status']);
	
	// To check if a user is able to edit/delete a CustomView
	$edit_permit = $customView->isPermittedCustomView($viewid,'EditView',$currentModule);
	$delete_permit = $customView->isPermittedCustomView($viewid,'Delete',$currentModule);

	$smarty->assign("CUSTOMVIEW_PERMISSION",$statusdetails);
	$smarty->assign("CV_EDIT_PERMIT",$edit_permit);
	$smarty->assign("CV_DELETE_PERMIT",$delete_permit);
}
// END

$smarty->assign("VIEWID", $viewid);

if($viewnamedesc['viewname'] == 'All') $smarty->assign('ALL', 'All');

if($viewid != "0" && trim($viewid) != '')
{
	$stdfiltersql = $customView->getCVStdFilterSQL($viewid);
	$advfiltersql = $customView->getCVAdvFilterSQL($viewid);
	if(isset($stdfiltersql) && $stdfiltersql != '')
	{
		$list_where .= ' and '.$stdfiltersql;
	}
	if(isset($advfiltersql) && $advfiltersql != '')
	{
		$list_where .= ' and '.$advfiltersql;
	}
	$list_query = $customView->getModifiedCvListQuery($viewid,$query,$currentModule,$popuptype);
	//crmv@25193
	list($focus->customview_order_by,$focus->customview_sort_order) = $customView->getOrderByFilterSQL($viewid);
	$sorder = $focus->getSortOrder();
	$order_by = $focus->getOrderBy();
	if(!empty($order_by) && $order_by != '' && $order_by != null) {
		if($order_by == 'smownerid') $list_query .= ' ORDER BY user_name '.$sorder;
		else {
			$tablename = getTableNameForField($currentModule, $order_by);
			$tablename = ($tablename != '')? ($tablename . '.') : '';
			$list_query .= ' ORDER BY ' . $tablename . $order_by . ' ' . $sorder;
		}
	}
	//crmv@25193e
}
else {
	$list_query = $query;
}
if (!empty($add_join)) $list_query = str_ireplace(' where ',$add_join.' where ',$list_query);	//crmv@131239
$smarty->assign("CUSTOMVIEW_OPTION",$customview_html);
//crmv@popup customview end
//------------------------------------------------------------------------------------------------------------------------------------------
//crmv@sdk-18508
$sdk_files = SDK::getViews($module,'popup_query');
if (!empty($sdk_files)) {
	foreach($sdk_files as $sdk_file) {
		include($sdk_file['src']);
	}
}
//crmv@sdk-18508e
//crmv@29190 crmv@37463 crmv@146187
if ($_REQUEST['autocomplete'] == 'yes') {
	// crmv@131921
	$hasCategory = false; // crmv@198024
	// use the All filter for the autocomplete
	if ($module != 'Users' && !$override_query) { // crmv@157201
		$queryGenerator = QueryGenerator::getInstance($module, $current_user);
		$queryGenerator->initForAllCustomView();
		$queryGenerator->addFieldAlias('id', 'crmid');
		// add fields for popups
		// TODO: move this shit in the module class!
		if ($popuptype == 'inventory_prod' || $popuptype == 'inventory_prod_po' || $popuptype == 'inventory_service'){
			$queryGenerator->addField('description');
			if ($module == 'Products') {
				$queryGenerator->addCondition('discontinued', 1, 'e');
				// crmv@198024
				if (vtlib_isModuleActive('ConfProducts')) {
					$confprod = CRMEntity::getInstance('ConfProducts');
					$queryGenerator->addField('confprodid');
					$queryGenerator->addJoin($confprod->table_name, $confprod->table_index, $table_prefix.'_products', 'confproductid', 'LEFT', 'confprod');
					$queryGenerator->appendRawSelect('COALESCE(confprod.productname, \'\') as category, '.$table_prefix.'_products.confprodinfo');
					$hasCategory = true;
				}
				// crmv@198024e
			}
		}
		$query = $queryGenerator->getQuery();
	}
	// crmv@131921e
	$sdk_file = SDK::getPopupQuery('field',vtlib_purify($_REQUEST['srcmodule']),vtlib_purify($_REQUEST['forfield']));
	if ($sdk_file != '' && Vtecrm_Utils::checkFileAccess($sdk_file)) {//crmv@198038
		include($sdk_file);
	}
	$autoselect = vtlib_purify($_REQUEST['autocomplete_select']);
	$autoselect = str_ireplace(array('--', '#', ';', '*', ' from '), '', $autoselect);
	$query = 'select '.$autoselect.','.substr(trim($query),7);
	if(strripos($query,'ORDER BY') > 0) {
		$query = substr($query, 0, strripos($query,'ORDER BY'));
	}
	// create the query here, not in autocomplete.php
	$autowhere = vtlib_purify($_REQUEST['autocomplete_where']);
	$autowhereSql = ' and 0=1';
	if (is_array($autowhere) && count($autowhere) > 0) {
		$list = array();
		foreach ($autowhere as $cond) {
			$s = explode('###', $cond);
			if (count($s) == 2 && strlen($s[1]) > 2) {
				$colname = preg_replace('/[^a-z0-9_.]/i', '', $s[0]);
				//crmv@94308
				$s[1] = html_entity_decode($s[1]);  // crmv@175498
				if($adb->isMysql()){
					$list[] = "$colname like '".formatForSqlLike($s[1])."'"; // crmv@198024
				} else {
					$list[] = "$colname like '%".$s[1]."%'";
				}
				//crmv@94308e
				// crmv@198024
				if ($module == 'Products' && $hasCategory) {
					$list[] = "confprod.productname like '".formatForSqlLike($s[1])."'";
				}
				// crmv@198024e
			}
		}
		if (count($list) > 0) {
			$autowhereSql = ' and ('.implode(' or ', $list).')';
		}
	}
	$query .= $autowhereSql;
	$list_query = $query;
	
	// crmv@198024
	if ($module == 'Products' && $hasCategory) {
		// order by category, nulls last
		$list_query .= ' ORDER BY COALESCE(confprod.productname, \'ZZZ\') ASC';
	}
	// crmv@198024e
// crmv@198024
} else {
	if ($module == 'Products' && vtlib_isModuleActive('ConfProducts')) {
		$list_query = preg_replace("/ FROM {$table_prefix}_products /", ", {$table_prefix}_products.confprodinfo FROM {$table_prefix}_products ", $list_query);
	}
}
//crmv@29190e crmv@37463e crmv@146187e crmv@198024e

// crmv@184368
// an ugly patch for that terrible calendar (mysql only)!
if ($currentModule == 'Calendar' && $adb->isMysql()) {
	// limit to the 1st contact (it's N-N, but there's a field! WTF?!! )
	$list_query = preg_replace(
		"/JOIN {$table_prefix}_cntactivityrel.*?LEFT JOIN/i", 
		"JOIN {$table_prefix}_cntactivityrel ON {$table_prefix}_activity.activityid = (
			SELECT car.contactid FROM {$table_prefix}_cntactivityrel car
			WHERE car.activityid = {$table_prefix}_activity.activityid
			ORDER BY contactid
			LIMIT 1
		)
		LEFT JOIN",
		$list_query
	);
}
// crmv@184368e

$smarty->assign("CUSTOMCOUNTS_OPTION", $LVU->get_selection_options($currentModule, $noofrows));
$list_max_entries_per_page = $LVU->get_selection_options($currentModule, $noofrows, 'list');
if ($_REQUEST['calc_nav'] == 'true'){
	//Retreive the List View Table Header
	if($viewid !='')
	$url_string .= "&viewname=".$viewid;
	echo '&#&#&#';
	echo get_navigation_values($list_query,$url_string,$currentModule,'',false,$viewid);
	die();
}
if ($_REQUEST['ajax'] == 'true' 
	&& $_REQUEST['search']!= 'true' 
	&& $_REQUEST['changecount']!= 'true'
	&& $_REQUEST['changecustomview']!= 'true')
	{
	if ($_REQUEST['noofrows'] != '')
		$noofrows = $_REQUEST['noofrows'];
	else {
		$lvs_noofrows = getLVSDetails($currentModule,$viewid,'noofrows');
		if ($lvs_noofrows != '') $noofrows = $lvs_noofrows;
	}
	if ($noofrows > 0){ 
		$list_max_entries_per_page = $LVU->get_selection_options($currentModule, $noofrows, 'list');
		$queryMode = (isset($_REQUEST['query']) && $_REQUEST['query'] == 'true');
		$start = ListViewSession::getRequestCurrentPage($currentModule, $list_query, $viewid, $queryMode);
		$navigation_array = VT_getSimpleNavigationValues($start,$list_max_entries_per_page,$noofrows);
		$limit_start_rec = ($start-1) * $list_max_entries_per_page;
		$record_string = getRecordRangeMessage($list_max_entries_per_page, $limit_start_rec,$noofrows);
		$navigationOutput = $LVU->getTableHeaderSimpleNavigation($navigation_array, $url_string,$currentModule,$type,$viewid);
		$smarty->assign("RECORD_COUNTS", $record_string);
		$smarty->assign("NAVIGATION", $navigationOutput);
		$smarty->assign("AJAX", 'true');
	}
}
else {
	$queryMode = (isset($_REQUEST['query']) && $_REQUEST['query'] == 'true');
	//crmv@29190
	if ($_REQUEST['autocomplete'] == 'yes')
		$start = 2;
	else
		$start = ListViewSession::getRequestCurrentPage($currentModule, $list_query, $viewid, $queryMode);
	//crmv@29190e
	$limit_start_rec = ($start-1) * $list_max_entries_per_page;
	if ($limit_start_rec >= $list_max_entries_per_page){
		 $start -= 1;
		 $limit_start_rec = ($start-1) * $list_max_entries_per_page;
		 setLVSDetails($currentModule,$viewid,$start,'start');
	}		
	$navigation_array['current'] = $start;
	$navigation_array['start'] = $start;
}
$list_result = $adb->limitQuery($list_query,$limit_start_rec,$list_max_entries_per_page); //crmv@33997
//crmv@11597 e
//Retreive the List View Table Header

$focus->list_mode="search";
$focus->popup_type=$popuptype;
$url_string .='&popuptype='.$popuptype;
if(isset($_REQUEST['select']) && $_REQUEST['select'] == 'enable')
	$url_string .='&select=enable';
if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] != '')
	$url_string .='&return_module='.$_REQUEST['return_module'];
//crmv@29190
if ($_REQUEST['autocomplete'] != 'yes') {
//crmv@29190e
	$listview_header_search=getSearchListHeaderValues($focus,"$currentModule",$url_string,$sorder,$order_by,$relatedlist,$customView);
	$smarty->assign("SEARCHLISTHEADER", $listview_header_search);

	$listview_header = $LVU->getSearchListViewHeader($focus,"$currentModule",$url_string,$sorder,$order_by,$customView);
	$smarty->assign("LISTHEADER", $listview_header);
	$smarty->assign("HEADERCOUNT",count($listview_header)+1);
	$smarty->assign('FROM_CALENDAR', $_REQUEST['fromCalendar']);//crmv@26807
	$smarty->assign('MASS_LINK', $_REQUEST['mass_link']);//crmv@26510
//crmv@29190
}
//crmv@29190e

// crmv@126696
if (isset($_REQUEST['override_fn'])) {
	$LVU->overrideReturnFunction($_REQUEST['override_fn']);
}
// crmv@126696e

$listview_entries = $LVU->getSearchListViewEntries($focus,"$currentModule",$list_result,$navigation_array,'',$customView);
//crmv@33997
if ($_REQUEST['autocomplete'] == 'yes') {
	global $autocomplete_return_function;
	if ($list_result) {
		$list_result->MoveFirst(); //porto il resultset alla prima riga
		while($row=$adb->fetchByAssoc($list_result,-1,false)) {
			if($module == 'Calendar') {
				$entity_id = 'activityid';
			} elseif ($module == 'Users') {
				$entity_id = 'id';
			} else {
				$entity_id = 'crmid';
			}
			// crmv@198024
			$entry = array(
				'id'=>$row[$entity_id],
				'label'=>$row['displayname'],
				'return_function'=>html_entity_decode($autocomplete_return_function[$row[$entity_id]],null,'UTF-8'),	//crmv@34792
				'return_function_file'=>"modules/$module/$module.js",
			);
			
			if ($hasCategory) {
				$entry['category'] = $row['category'];
			}
			
			$autocomplete_return[] = $entry;
			// crmv@198024e
		}
	}
	die(Zend_Json::encode($autocomplete_return));
}
//crmv@33997e

$smarty->assign("LISTENTITY", $listview_entries);

$smarty->assign("DATEFORMAT",$current_user->date_format);
$smarty->assign("RECORD_COUNTS", $record_string);
$smarty->assign("POPUPTYPE", $popuptype);
//crmv@17997
$queryGenerator = QueryGenerator::getInstance($currentModule, $current_user);
$controller = ListViewController::getInstance($adb, $current_user, $queryGenerator);
$alphabetical = $LVU->AlphabeticalSearch($currentModule,'index',$focus->search_base_field,'true','basic',"","","","",$viewid);
$fieldnames = $controller->getAdvancedSearchOptionString(true);
//crmv@17997 end
$criteria = getcriteria_options();
$smarty->assign("CRITERIA", $criteria);
$smarty->assign("FIELDNAMES", $fieldnames);
$smarty->assign("ALPHABETICAL", $alphabetical);
if (isset($_REQUEST["selected_ids"]))
{
  $smarty->assign("SELECTED_IDS_ARRAY", explode(";",$_REQUEST["selected_ids"]));
  $smarty->assign("SELECTED_IDS", $_REQUEST["selected_ids"]);
} 
if (isset($_REQUEST["all_ids"]))
{
  $smarty->assign("ALL_IDS", $_REQUEST["all_ids"]);
}

// QuickCreatePopup
$qcreate_array = QuickCreate($currentModule);
if (!empty($qcreate_array['data'])) $smarty->assign('QUICKCREATE','permitted');

//crmv@sdk-18501
include_once('vtlib/Vtecrm/Link.php');
$hdrcustomlink_params = Array('MODULE'=>$currentModule);
$COMMONHDRLINKS = Vtecrm_Link::getAllByType(Vtecrm_Link::IGNORE_MODULE, Array('HEADERLINK','HEADERSCRIPT', 'HEADERCSS'), $hdrcustomlink_params);//crmv@198038
$smarty->assign('HEADERLINKS', $COMMONHDRLINKS['HEADERLINK']);
$smarty->assign('HEADERSCRIPTS', $COMMONHDRLINKS['HEADERSCRIPT']);
$smarty->assign('HEADERCSS', $COMMONHDRLINKS['HEADERCSS']);
//crmv@sdk-18501 e

// crmv@42024 - pass global JS vars to template
$JSGlobals = ( function_exists('getJSGlobalVars') ? getJSGlobalVars() : array() );
$smarty->assign('JS_GLOBAL_VARS',Zend_Json::encode($JSGlobals));
// crmv@42024e

// crmv@113400
$smarty_ajax_template = 'PopupContents.tpl';
$smarty_template = 'Popup.tpl';

$sdk_custom_file = 'PopupCustomisations';
if (isModuleInstalled('SDK')) {
	$tmp_sdk_custom_file = SDK::getFile($currentModule,$sdk_custom_file);
	if (!empty($tmp_sdk_custom_file)) {
		$sdk_custom_file = $tmp_sdk_custom_file;
	}
}
@include("modules/$currentModule/$sdk_custom_file.php");

if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] != '')
	$smarty->display($smarty_ajax_template);
else
	$smarty->display($smarty_template);
// crmv@113400e