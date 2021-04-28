<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@23687	crmv@25483	crmv@24378	crmv@11691	crmv@55317 */

require_once('include/database/PearDatabase.php');
require_once('include/ComboUtil.php'); //new
require_once('include/utils/utils.php'); //new
require_once('include/utils/CommonUtils.php'); //new
global $table_prefix;
$column_array = array('accountid','contact_id','product_id','campaignid','quoteid','vendorid','potentialid','salesorderid','vendor_id','contactid','handler');
$table_col_array = array($table_prefix.'_account.accountname',$table_prefix.'_contactdetails.firstname',$table_prefix.'_contactdetails.lastname',$table_prefix.'_products.productname',$table_prefix.'_campaign.campaignname',$table_prefix.'_quotes.subject',$table_prefix.'_vendor.vendorname',$table_prefix.'_potential.potentialname',$table_prefix.'_salesorder.subject',$table_prefix.'_vendor.vendorname',$table_prefix.'_contactdetails.firstname', $table_prefix.'_contactdetails.lastname',$table_prefix.'_users.user_name');

class SearchUtils extends SDKExtendableUniqueClass {
	
	static public function callMethodByName($name, $arguments = array()) {
		$SearchUtils = SearchUtils::getInstance();
		return call_user_func_array(array($SearchUtils, $name), $arguments);
	}

	// ----- SEARCH UTILS FUNCTIONS -----
	
	function getUnifiedWhereWSFields($module,$types2skip=array()) {
		global $adb, $current_user, $table_prefix;
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		
		if (empty($types2skip)) $types2skip = array('reference','datetime','date','time');
	
		//crmv@26369 crmv@69572
		$focusCV = CRMEntity::getInstance('CustomView');
		$ssql = $focusCV->getCustomViewQuery($module, $sparams);
		$cv_columns = array();
		$cv_result = $adb->pquery("SELECT columnname FROM {$table_prefix}_customview
			INNER JOIN {$table_prefix}_cvcolumnlist ON {$table_prefix}_customview.cvid = {$table_prefix}_cvcolumnlist.cvid
			INNER JOIN ($ssql) p ON p.cvid = {$table_prefix}_customview.cvid
			GROUP BY columnname", array($sparams));
		while($cv_row=$adb->fetchByAssoc($cv_result)) {
		$tmp = explode(':',$cv_row['columnname']);
		$cv_columns[] = $tmp[1]; //crmv@27686
		}
		//crmv@26369e crmv@69572e
	
		$search_cond = "";
		if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] ==0){
			$query = "SELECT ".$table_prefix."_field.* FROM ".$table_prefix."_field WHERE tabid = ? and ".$table_prefix."_field.presence in (0,2) $search_cond";
			$qparams = array(getTabid($module));
		}else{
			$profileList = getCurrentUserProfileList();
			$query = "SELECT ".$table_prefix."_field.* FROM ".$table_prefix."_field INNER JOIN ".$table_prefix."_def_org_field ON ".$table_prefix."_def_org_field.fieldid = ".$table_prefix."_field.fieldid WHERE ".$table_prefix."_field.tabid = ? AND ".$table_prefix."_def_org_field.visible = 0 and ".$table_prefix."_field.presence in (0,2) ";
		    $qparams = array(getTabid($module));
	 	    $query.=" AND EXISTS(SELECT * FROM ".$table_prefix."_profile2field WHERE ".$table_prefix."_profile2field.fieldid = ".$table_prefix."_field.fieldid ";
	            if (count($profileList) > 0) {
		  	  		$query.=" AND ".$table_prefix."_profile2field.profileid IN (". generateQuestionMarks($profileList) .") ";
		  	 		array_push($qparams, $profileList);
		    }
	 	    $query.=" AND ".$table_prefix."_profile2field.visible = 0) ";
			$query.=" $search_cond ";
		}
		//crmv@26369
		if (in_array($module,array('Emails','Fax','Sms'))) {
			$query .= " and columnname = 'subject'";
		} else {
			$query .= ' and columnname in ('.generateQuestionMarks($cv_columns).')';
			array_push($qparams, $cv_columns);
		}
		//crmv@26369e
	
		$result = $adb->pquery($query, $qparams);
		$noofrows = $adb->num_rows($result);
	
		$fields = array();
		for($i=0;$i<$noofrows;$i++){
			$field = WebserviceField::fromQueryResult($adb,$result,$i);
			$type = $field->getFieldDataType();
			$fieldname = $field->getFieldName();
			$tablename = $field->getTableName();
			//crmv@26369
			if (in_array($type,$types2skip)) {
				continue;
			}
			//crmv@26369e
			if (!is_numeric($search_val) && in_array($type,array('boolean','double','integer','int','decimal','currency'))) {
				continue;
			}
			if (in_array($fieldname,array('birthday','currency_id'))) {
				continue;
			}
			if ($module == 'Calendar' && $fieldname == 'contact_id') {	// disabilito ricerca per contact nel Calendar
				continue;
			}
			if ($module == 'Emails' && in_array($tablename,array($table_prefix.'_attachments',$table_prefix.'_email_track'))) {
				continue;
			}
			$fields[] = $field;
		}
		return $fields;
	}
	
	function getUnifiedSearchFieldInfoList($module) {
		$info = array();
		$fields = $this->getUnifiedWhereWSFields($module,array('datetime','date','time'));
		if (!empty($fields)) {
			foreach($fields as $field) {
				$info[$field->getFieldName()] = getTranslatedString($field->getFieldLabelKey(),$module);
			}
		}
		return $info;
	}
	
	function getUnifiedWhereConditions($module,$search_val) {
		global $adb, $table_prefix;
	
		$where = array();
		$fields = $this->getUnifiedWhereWSFields($module);
		if (!empty($fields)) {
			$j = 0;
			foreach($fields as $field) {
				$type = $field->getFieldDataType();
				$fieldname = $field->getFieldName();
				$tablename = $field->getTableName();
				$typeofdata = explode('~',$field->getTypeOfData());
				
				if ($module == 'Documents' && $fieldname == 'backend_name') continue;
				
				if ($module == 'Documents' && $fieldname == 'folderid') {
					$fieldname = 'foldername';
					$tablename = $table_prefix.'_crmentityfolder'; // crmv@30967
				}
				$where['Fields'.$j] = $fieldname.'::::'.$typeofdata[0];
				if ($typeofdata[0] == 'V' || $typeofdata[0] == 'E' || $type == 'reference') {
		    		$where['Condition'.$j] = 'c';
				} else {
		    		$where['Condition'.$j] = 'e';
				}
		    	$where['Srch_value'.$j] = $search_val;
		    	$j++;
			}
			$where['search_cnt'] = $j;
		    $where['searchtype'] = 'advance';
		    $where['matchtype'] = 'any';
		}
		return $where;
	}
	
	//crmv@159559
	function getAdvancedSearchParams($input) {
		$advanced_search_params = array(
			'searchtype'=>vtlib_purify($input['searchtype']),
			'search_cnt'=>vtlib_purify($input['search_cnt']),
			'matchtype'=>vtlib_purify($input['matchtype']),
			'rows' => [],
		);
		for($i=0;$i<$input['search_cnt'];$i++) {
			$advanced_search_params['rows'][$i] = array(
				'Fields'=>stripslashes(str_replace("'","",vtlib_purify($input['Fields'.$i]))),
				'Condition'=>vtlib_purify($input['Condition'.$i]),
				'Srch_value'=>to_html(vtlib_purify($input['Srch_value'.$i])),
			);
		}
		return $advanced_search_params;
	}
	//crmv@159559e
}

function getUnifiedWhereConditions() { return SearchUtils::callMethodByName(__FUNCTION__, func_get_args()); }

/**
 * To get the modules allowed for global search this function returns all the
 * modules which supports global search as an array in the following structure
 * array($module_name1=>$object_name1,$module_name2=>$object_name2,$module_name3=>$object_name3,$module_name4=>$object_name4,-----);
 */
function getSearchModules($filter = array(), $check_permission=false){ //crmv@187493
	global $adb,$table_prefix;

	if (empty($filter)) return array(); // crmv@26485

	// vtlib customization: Ignore disabled modules.
	//$sql = 'select distinct vte_field.tabid,name from vte_field inner join vte_tab on vte_tab.tabid=vte_field.tabid where vte_tab.tabid not in (16,29)';
	$sql = 'select distinct '.$table_prefix.'_field.tabid,name from '.$table_prefix.'_field inner join '.$table_prefix.'_tab on '.$table_prefix.'_tab.tabid='.$table_prefix.'_field.tabid where '.$table_prefix.'_tab.tabid not in (16,29) and '.$table_prefix.'_tab.presence != 1 and isentitytype = 1 and '.$table_prefix.'_field.presence in (0,2) and '.$table_prefix.'_tab.name not in (\'Fax\',\'Sms\')';	//crmv@27911
	// END
	$result = $adb->pquery($sql, array());
	while($module_result = $adb->fetch_array($result)){
		$modulename = $module_result['name'];
		// Do we need to filter the module selection?
		if(!empty($filter) && is_array($filter) && !in_array($modulename, $filter)) {
			continue;
		}
		// END
		if (isPermitted($modulename,"index") == "no") continue; //crmv@187493
		if($modulename != 'Calendar'){
			$return_arr[$modulename] = $modulename;
		}else{
			$return_arr[$modulename] = 'Activity';
		}
	}
	return $return_arr;
}

//crmv@29907
function getAllModulesForTag(){
	global $adb,$current_user,$table_prefix;

	$modulelist = array();

	$sql = 'select distinct '.$table_prefix.'_field.tabid,name from '.$table_prefix.'_field inner join '.$table_prefix.'_tab on '.$table_prefix.'_tab.tabid='.$table_prefix.'_field.tabid where '.$table_prefix.'_tab.tabid not in (16,29) and '.$table_prefix.'_tab.presence != 1 and isentitytype = 1 and '.$table_prefix.'_field.presence in (0,2)';	//crmv@27911
	$moduleres = $adb->query($sql);
	while($modulerow = $adb->fetch_array($moduleres)) {
		if((is_admin($current_user) || isPermitted($modulerow['name'], 'DetailView') == 'yes') && !in_array($modulerow['name'],array('Messages','Emails','ModComments','MyNotes','Processes'))) { //crmv@130836 crmv@164120 crmv@164122
			$modulelist[] = $modulerow['name'];
		}
	}

	return $modulelist;
}
//crmv@29907e

/**This function is used to get the list view header values in a list view during search
*Param $focus - module object
*Param $module - module name
*Param $sort_qry - sort by value
*Param $sorder - sorting order (asc/desc)
*Param $order_by - order by
*Param $relatedlist - flag to check whether the header is for listvie or related list
*Param $oCv - Custom view object
*Returns the listview header values in an array
*/

function getSearchListHeaderValues($focus, $module,$sort_qry='',$sorder='',$order_by='',$relatedlist='',$oCv='')
{
	global $log;
	$log->debug("Entering getSearchListHeaderValues(".(is_object($focus)? get_class($focus):'').",". $module.",".$sort_qry.",".$sorder.",".$order_by.",".$relatedlist.",".(is_object($oCV)? get_class($oCV):'').") method ..."); //crmv@31429
        global $adb, $table_prefix;
        global $theme;
        global $app_strings;
        global $mod_strings,$current_user;

        $arrow='';
        $qry = getURLstring($focus);
        $theme_path="themes/".$theme."/";
        $image_path=$theme_path."images/";
        $search_header = Array();

        //Get the vte_tabid of the module
        //require_once('include/utils/UserInfoUtil.php')
        $tabid = getTabid($module);
        //added for vte_customview 27/5
        if($oCv && isset($oCv->list_fields))
           	$focus->list_fields = $oCv->list_fields;
	//Added to reduce the no. of queries logging for non-admin vte_users -- by Minnie-start
	$field_list = array();
	$j=0;
	require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	foreach($focus->list_fields as $name=>$tableinfo)
	{
		$fieldname = $focus->list_fields_name[$name];
		if($oCv)
		{
			if(isset($oCv->list_fields_name))
			{
				$fieldname = $oCv->list_fields_name[$name];
			}
		}
		if($fieldname == "accountname" && $module !="Accounts")
			$fieldname = "account_id";

		if($fieldname == "productname" && $module =="Campaigns")
			$fieldname = "product_id";

		if($fieldname == "lastname" && $module !="Leads" && $module !="Contacts")
		{
			$fieldname = "contact_id";
		}
		if($fieldname == 'folderid' && $module == 'Documents'){
			$fieldname = 'foldername';
		}
		array_push($field_list, $fieldname);
		$j++;
	}
	//Getting the Entries from Profile2 vte_field vte_table
	if($is_admin == false)
	{
		$profileList = getCurrentUserProfileList();
		//changed to get vte_field.fieldname
		$params = array();
		$query  = "SELECT ".$table_prefix."_field.fieldname
					FROM ".$table_prefix."_field
					  INNER JOIN ".$table_prefix."_profile2field
					    ON ".$table_prefix."_profile2field.fieldid = ".$table_prefix."_field.fieldid
					  INNER JOIN ".$table_prefix."_def_org_field
					    ON ".$table_prefix."_def_org_field.fieldid = ".$table_prefix."_field.fieldid
					WHERE ".$table_prefix."_field.tabid = ?
					    AND ".$table_prefix."_def_org_field.visible = 0
					    AND ".$table_prefix."_field.fieldname IN(". generateQuestionMarks($field_list) .")";
		array_push($params, $tabid,$field_list);
		$query.=" AND EXISTS(SELECT * FROM ".$table_prefix."_profile2field WHERE ".$table_prefix."_profile2field.fieldid = ".$table_prefix."_field.fieldid AND ".$table_prefix."_profile2field.visible = 0";
		if (count($profileList) > 0) {
			 $query.=" AND ".$table_prefix."_profile2field.profileid IN (". generateQuestionMarks($profileList) .") ";
			 array_push($params, $profileList);
		}
		$query.=")";
		$result = $adb->pquery($query,$params);
		$field=Array();
		for($k=0;$k < $adb->num_rows($result);$k++)
		{
			$field[]=$adb->query_result($result,$k,"fieldname");
		}

		//if this field array is empty and the user don't have any one of the admin, view all, edit all permissions then the search picklist options will be empty and we cannot navigate the users list - js error will thrown in function getListViewEntries_js in Smarty\templates\Popup.tpl
		if($module == 'Users' && empty($field))
			$field = Array("last_name","email1");
	}

	// Remove fields which are made inactive
	$focus->filterInactiveFields($module);
        //modified for vte_customview 27/5 - $app_strings change to $mod_strings
        foreach($focus->list_fields as $name=>$tableinfo)
        {
                //added for vte_customview 27/5
                if($oCv)
                {
                        if(isset($oCv->list_fields_name))
			{
				if( $oCv->list_fields_name[$name] == '')
					$fieldname = 'crmid';
				else
					$fieldname = $oCv->list_fields_name[$name];

                        }else
                        {
				if( $focus->list_fields_name[$name] == '')
					$fieldname = 'crmid';
				else
					$fieldname = $focus->list_fields_name[$name];

                        }
			if($fieldname == "lastname" && $module !="Leads" && $module !="Contacts")
				$fieldname = "contact_id";
			if($fieldname == "accountname" && $module !="Accounts")
				$fieldname = "account_id";
			if($fieldname == "productname" && $module =="Campaigns")
				$fieldname = "product_id";


                }
		else
                {
			if( $focus->list_fields_name[$name] == '')
				$fieldname = 'crmid';
			else
				$fieldname = $focus->list_fields_name[$name];

			if($fieldname == "lastname" && $module !="Leads" && $module !="Contacts")
                                $fieldname = "contact_id";
		}
                if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] ==0 || in_array($fieldname,$field))
		{
			//crmv@19401
			if($fieldname == 'parent_id' && $module == "HelpDesk") {
				$search_header[$fieldname] = getTranslatedString($name);
			} elseif($fieldname!='parent_id') {
			//crmv@19401e
				$fld_name=$fieldname;
				if($fieldname == 'contact_id' && $module !="Contacts")
				$name = $app_strings['LBL_CONTACT_LAST_NAME'];
				elseif($fieldname == 'contact_id' && $module =="Contacts")
					$name = $mod_strings['Reports To']." - ".$mod_strings['LBL_LIST_LAST_NAME'];
				//assign the translated string
				//added to fix #5205
				//Added condition to hide the close column in calendar search header
				if($name != $app_strings['Close'])
					$search_header[$fld_name] = getTranslatedString($name);
			//crmv@19401
			}
			//crmv@19401e
		}
		if($module == 'HelpDesk' && $fieldname == 'crmid')
		{
                        $fld_name=$fieldname;
                        $search_header[$fld_name] = getTranslatedString($name);
                }
	}
	$log->debug("Exiting getSearchListHeaderValues method ...");
        return $search_header;

}

/**This function is used to get the where condition for search listview query along with url_string
*Param $module - module name
*Returns the where conditions and url_string values in string format
*/

function Search($module)
{
	global $log,$default_charset;
        $log->debug("Entering Search(".$module.") method ...");
	$url_string='';
	if(isset($_REQUEST['search_field']) && $_REQUEST['search_field'] !="") {
		$search_column=vtlib_purify($_REQUEST['search_field']);
	}
	if(isset($_REQUEST['search_text']) && $_REQUEST['search_text']!="") {
		// search other characters like "|, ?, ?" by jagi
		$search_string = $_REQUEST['search_text'];
		$stringConvert = function_exists('iconv') ? @iconv("UTF-8",$default_charset,$search_string) : $search_string; // crmv@167702
		$search_string=trim($stringConvert);
	}
	if(isset($_REQUEST['searchtype']) && $_REQUEST['searchtype']!="") {
        $search_type=vtlib_purify($_REQUEST['searchtype']);
    	if($search_type == "BasicSearch") {
    		//crmv@131239
    		$search_result = BasicSearch($module,$search_column,$search_string);
    		$where = $search_result['where'];
    		$join = $search_result['join'];
    		//crmv@131239e
    	} else if ($search_type == "AdvanceSearch") {
    	} else { //Global Search
		}
		$url_string = "&search_field=".$search_column."&search_text=".urlencode($search_string)."&searchtype=BasicSearch";
		if(isset($_REQUEST['type']) && $_REQUEST['type'] != '')
			$url_string .= "&type=".vtlib_purify($_REQUEST['type']);
		$log->debug("Exiting Search method ...");
		return $where.'#@@#'.$url_string.'#@@#'.$join;	//crmv@131239
	}
}

/**This function is used to get user_id's for a given user_name during search
*Param $table_name - vte_tablename
*Param $column_name - columnname
*Param $search_string - searchstring value (username)
*Returns the where conditions for list query in string format
*/

function get_usersid($table_name,$column_name,$search_string)
{

	global $log;
	$log->debug("Entering get_usersid(".$table_name.",".$column_name.",".$search_string.") method ...");
	global $adb, $table_prefix;
	$where.="(".$table_prefix."_users.user_name like '". formatForSqlLike($search_string) .
			"' or ".$table_prefix."_groups.groupname like '". formatForSqlLike($search_string) ."')";
	$log->debug("Exiting get_usersid method ...");
	return $where;
}

/**This function is used to get where conditions for a given vte_accountid or contactid during search for their respective names
*Param $column_name - columnname
*Param $search_string - searchstring value (username)
*Returns the where conditions for list query in string format
*/


function getValuesforColumns($column_name,$search_string,$criteria='cts')
{
	global $log, $current_user, $table_prefix;
	$log->debug("Entering getValuesforColumns(".$column_name.",".$search_string.") method ...");
	global $column_array,$table_col_array;

	if($_REQUEST['type'] == "entchar")
		$criteria = "is";

	for($i=0; $i<count($column_array);$i++)
	{
		if($column_name == $column_array[$i])
		{
			$val=$table_col_array[$i];
			$explode_column=explode(",",$val);
			$x=count($explode_column);
			if($x == 1 )
			{
				$where=getSearch_criteria($criteria,$search_string,$val);
			}
			else
			{
				if($column_name == "contact_id" && $_REQUEST['type'] == "entchar") {
					if (getFieldVisibilityPermission('Contacts', $current_user->id,'firstname') == '0') {
						$where = $adb->sql_concat(Array($table_prefix.'_contactdetails.lastname',"' '",$table_prefix.'_contactdetails.firstname'))." = '$search_string'";
					} else {
						$where = $table_prefix."_contactdetails.lastname = '$search_string'";
					}
				}
				else {
					$where="(";
					for($j=0;$j<count($explode_column);$j++)
					{
						$where .=getSearch_criteria($criteria,$search_string,$explode_column[$j]);
						if($j != $x-1)
						{
							if($criteria == 'dcts' || $criteria == 'isn')
								$where .= " and ";
							else
								$where .= " or ";
						}
					}
					$where.=")";
				}
			}
			break 1;
		}
	}
	$log->debug("Exiting getValuesforColumns method ...");
	return $where;
}

/**This function is used to get where conditions in Basic Search
*Param $module - module name
*Param $search_field - columnname/field name in which the string has be searched
*Param $search_string - searchstring value (username)
*Returns the where conditions for list query in string format
*/

function BasicSearch($module,$search_field,$search_string){
	global $log,$mod_strings,$current_user;
	$log->debug("Entering BasicSearch(".$module.",".$search_field.",".$search_string.") method ...");
	global $adb, $table_prefix;
	$search_string = ltrim(rtrim($adb->sql_escape_string($search_string)));
	global $column_array,$table_col_array;
	$join = '';	//crmv@131239
	if($search_field =='crmid'){
		$column_name='crmid';
		$table_name=$table_prefix.'_crmentity';
		$where="$table_name.$column_name like '". formatForSqlLike($search_string) ."'";
	}elseif($search_field =='currency_id' && ($module == 'PriceBooks' || $module == 'PurchaseOrder' || $module == 'SalesOrder' || $module == 'Invoice' || $module == 'Quotes')){
		$column_name='currency_name';
		$table_name=$table_prefix.'_currency_info';
		$where="$table_name.$column_name like '". formatForSqlLike($search_string) ."'";
	}elseif($search_field == 'folderid' && $module == 'Documents'){
		$column_name='foldername';
		$table_name=$table_prefix.'_crmentityfolder'; // crmv@30967
		$where="$table_name.$column_name like '". formatForSqlLike($search_string) ."'";
	}else{
		//Check added for tickets by accounts/contacts in dashboard
		$search_field_first = $search_field;
		if($module=='HelpDesk'){
			//crmv@19401
			if($search_field == 'parent_id'){
				$where = "(".$table_prefix."_account.accountname like '". formatForSqlLike($search_string) ."' or ".$table_prefix."_contactdetails.firstname like '". formatForSqlLike($search_string) ."' or ".$table_prefix."_contactdetails.lastname like '". formatForSqlLike($search_string) ."')";
				return array('where'=>$where,'join'=>$join); // crmv@167662
			}elseif($search_field == 'contactid'){
			//crmv@19401e
				$where = "(".$table_prefix."_contactdetails.contact_no like '". formatForSqlLike($search_string) ."')";
				return array('where'=>$where,'join'=>$join); // crmv@167662
			}elseif($search_field == 'account_id'){
				$search_field = "parent_id";
			}
		}
		//Check ends

		//Added to search contact name by lastname
		if(($module == "Calendar" || $module == "Invoice" || $module == "Documents" || $module == "SalesOrder" || $module== "PurchaseOrder") && ($search_field == "contact_id")){
			$module = 'Contacts';
			$search_field = 'lastname';
		}
		if($search_field == "accountname" && $module != "Accounts"){
			$search_field = "account_id";
		}
		if($search_field == 'productname' && $module == 'Campaigns'){
			$search_field = "product_id";
		}

		$qry="select ".$table_prefix."_field.columnname,tablename from ".$table_prefix."_tab inner join ".$table_prefix."_field on ".$table_prefix."_field.tabid=".$table_prefix."_tab.tabid where ".$table_prefix."_tab.name=? and (fieldname=? or columnname=?)";
		$result = $adb->pquery($qry, array($module, $search_field, $search_field));
		$noofrows = $adb->num_rows($result);
		if($noofrows!=0)
		{
			$column_name=$adb->query_result($result,0,'columnname');

			//Check added for tickets by accounts/contacts in dashboard
			if ($column_name == 'parent_id')
			{
				if ($search_field_first	== 'account_id') $search_field_first = 'accountid';
				if ($search_field_first	== 'contactid') $search_field_first = 'contact_id';
				$column_name = $search_field_first;
			}

			//Check ends
			$table_name=$adb->query_result($result,0,'tablename');
			$uitype=getUItype($module,$column_name);

			//Added for Member of search in Accounts
			if($column_name == "parentid" && $module == "Accounts")
			{
				$table_name = $table_prefix."_account2";
				$column_name = "accountname";
			}
			if($column_name == "parentid" && $module == "Products")
			{
				$table_name = $table_prefix."_products2";
				$column_name = "productname";
			}
			if($column_name == "reportsto" && $module == "Contacts")
			{
				$table_name = $table_prefix."_contactdetails2";
				$column_name = "lastname";
			}
			if($column_name == "inventorymanager" && $module = "Quotes")
			{
				$table_name = $table_prefix."_usersQuotes";
				$column_name = "user_name";
			}

			if($column_name == "reports_to_id" && $table_name == $table_prefix."_projects") {
				$column_name = "user_name";
				$table_name = "users_projectleader";
			}

			//Added to support user date format in basic search
			if($uitype == 5 || $uitype == 6 || $uitype == 23 || $uitype == 70)
			{
				list($sdate,$stime) = explode(" ",$search_string);
				if($stime !='')
					$search_string = getDBInsertDateValue($sdate)." ".$stime;
				else
					$search_string = getDBInsertDateValue($sdate);
			}
			//crmv@128159
			if (SDK::isUitype($uitype)) {
				$sdk_file = SDK::getUitypeFile('php','popupbasicsearch',$uitype);
				if ($sdk_file != '') {
					include($sdk_file);
				}
			}
			//crmv@128159e
			// Added to fix errors while searching check box type fields(like product active. ie. they store 0 or 1. we search them as yes or no) in basic search.
			elseif ($uitype == 56)
			{
				if(strtolower($search_string) == 'yes')
					$where="$table_name.$column_name = '1'";
				elseif(strtolower($search_string) == 'no')
					$where="$table_name.$column_name = '0'";
				else
					$where="$table_name.$column_name = '-1'";
			}
			elseif ($uitype == 111 || $uitype == 15 || $uitype == 16){
				//crmv@17997
				if(is_uitype($uitype, '_picklist_')) {
					$qgen_obj = QueryGenerator::getInstance($module,$current_user);
					$value_trans = $qgen_obj->getReverseTranslate($search_string,'bwt');
					if ($value_trans != $search_string){
						if(getFieldVisibilityPermission("Calendar", $current_user->id,'taskstatus') == '0' && ($column_name == "status" || $column_name == "eventstatus")){
								$where="(".$table_prefix."_activity.status like '". formatForSqlLike($search_string) ."' or ".$table_prefix."_activity.status like '". formatForSqlLike($value_trans) ."'";
								$where=" or ".$table_prefix."_activity.eventstatus like '". formatForSqlLike($search_string) ."' or ".$table_prefix."_activity.eventstatus like '". formatForSqlLike($value_trans) ."')";
						}
						else
							$where="($table_name.$column_name like '". formatForSqlLike($search_string) ."' or $table_name.$column_name like '". formatForSqlLike($value_trans) ."')";
					}
					else {
						if(getFieldVisibilityPermission("Calendar", $current_user->id,'taskstatus') == '0' && ($column_name == "status" || $column_name == "eventstatus"))
							$where="(".$table_prefix."_activity.status like '". formatForSqlLike($search_string) ."' or ".$table_prefix."_activity.eventstatus like '". formatForSqlLike($search_string) ."')";
						else
							$where="$table_name.$column_name like '". formatForSqlLike($search_string) ."'";
					}
				}
				//crmv@17997 end
			}
			//crmv@8982 campi picklist multilingua
			elseif($uitype == 1015)
			{
				//crmv@17997
				if (!in_array($search_string,Array('is','isn'))){
				//crmv@17997 end
					if($_REQUEST['type'] == 'alpbt')
					{
				        $qry_val = "$search_string%";
					}
					else $qry_val = "%$search_string%";
					$cond_val = "like '$qry_val'";
				}
				else {
					$cond_val = " = '$search_string'";
				}
				global $current_language;
				$ssql = "select code from tbl_s_picklist_language where value $cond_val and field = '$column_name' and language = '$current_language'";

				$res=$adb->query($ssql);
				if ($res){
					$cnt == 0;
					while ($row = $adb->fetchByAssoc($res,-1,false)){
						if ($cnt == 0)
							$search_string = "'".$row['code']."'";
						else
							$search_string .= ",'".$row['code']."'";
							$cnt++;
					}
					if ($cnt == 0)
						$where = "$table_name.$column_name = '$search_string'";
					else
						$where = "$table_name.$column_name in ($search_string)";
				}
				else $where = "$table_name.$column_name = '$search_string'";
			}
			//crmv@8982 e
			elseif($table_name == $table_prefix."_crmentity" && $column_name == "smownerid")
			{
				$where = get_usersid($table_name,$column_name,$search_string);
			}
			else if(in_array($column_name,$column_array))
			{
				$where = getValuesforColumns($column_name,$search_string);
			}
			else if($_REQUEST['type'] == 'entchar')
			{
				$where="$table_name.$column_name = '". $search_string ."'";
			}
			else
			{
				$where="$table_name.$column_name like '". formatForSqlLike($search_string) ."'";
			}
		}
	}
	if(stristr($where,"like '%%'"))
	{
		$where_cond0=str_replace("like '%%'","like ''",$where);
		$where_cond1=str_replace("like '%%'","is NULL",$where);
		if($module == "Calendar")
			$where = "(".$where_cond0." and ".$where_cond1.")";
		else
			$where = "(".$where_cond0." or ".$where_cond1.")";
	}
	// commented to support searching "%" with the search string.
	/*if($_REQUEST['type'] == 'entchar')
	{
		$search = array('Un Assigned','%','like');
		$replace = array('','','=');
		$where= str_replace($search,$replace,$where);
	}*/
	if($_REQUEST['type'] == 'alpbt' && $uitype != 1015)
	{
	        $where = str_replace_once("%", "", $where);
	}

	//uitype 10 handling
	if($uitype == 10){
		$where = array();
		$sql = "select fieldid from ".$table_prefix."_field where tabid=? and fieldname=?";
		$result = $adb->pquery($sql, array(getTabid($module), $search_field));

		if($adb->num_rows($result)>0){
			$fieldid = $adb->query_result($result, 0, "fieldid");
			$sql = "select * from ".$table_prefix."_fieldmodulerel where fieldid=?";
			$result = $adb->pquery($sql, array($fieldid));
			$count = $adb->num_rows($result);
			$searchString = formatForSqlLike($search_string);
			
			$ENU = EntityNameUtils::getInstance(); // crmv@144125

			for($i=0;$i<$count;$i++){
				$relModule = $adb->query_result($result, $i, "relmodule");
				$relInfo = $ENU->getEntityField($relModule); // crmv@144125
				$relTable = $relInfo["tablename"];
				$relField = $relInfo["fieldname"];

				if(stripos($relField, $relTable) !== false){	//crmv@22861
					$where[] = "$relField like '$searchString'";
				}else{
					$where[] = "$relTable.$relField like '$searchString'";
				}

			}
			$where = implode(" or ", $where);
		}
		$where = "($where) ";
	}

	$log->debug("Exiting BasicSearch method ...");
	return array('where'=>$where,'join'=>$join);	//crmv@131239
}

/**This function is used to get where conditions in Advance Search
*Param $module - module name
*Returns the where conditions for list query in string format
*/

function getAdvSearchfields($module)
{
	global $log;
        $log->debug("Entering getAdvSearchfields(".$module.") method ...");
	global $adb, $table_prefix;
	global $current_user;
	global $mod_strings,$app_strings;
	require('user_privileges/requireUserPrivileges.php'); // crmv@39110

	$tabid = getTabid($module);
        if($tabid==9)
                $tabid="9,16";

	if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] == 0)
	{
		$marks = ($tabid == 9)?generateQuestionMarks(array(9,16)):'?';
		$sql = "select * from ".$table_prefix."_field ";
		$sql.= " where ".$table_prefix."_field.tabid in ($marks) and ";
		$sql.= $table_prefix."_field.displaytype in (1,2,3)";
		if($tabid == 13 || $tabid == 15)
		{
			$sql.= " and ".$table_prefix."_field.fieldlabel != 'Add Comment'";
		}
		if($tabid == 14)
		{
			$sql.= " and ".$table_prefix."_field.fieldlabel != 'Product Image'";
		}
		if($tabid == 9 || $tabid==16)
		{
			$sql.= " and ".$table_prefix."_field.fieldname not in('notime','duration_minutes','duration_hours')";
		}
		if($tabid == 4)
		{
			$sql.= " and ".$table_prefix."_field.fieldlabel != 'Contact Image'";
		}
		if($tabid == 13 || $tabid == 10)
		{
			$sql.= " and ".$table_prefix."_field.fieldlabel != 'Attachment'";
		}
		$sql.= " order by block,sequence";
		$params[] = $tabid;
		if ($tabid == 9){
			$params[] = 16;
		}
	}
	else
	{
		$profileList = getCurrentUserProfileList();
		$marks = ($tabid == 9)?generateQuestionMarks(array(9,16)):'?';
		$sql = "select * from ".$table_prefix."_field inner join ".$table_prefix."_def_org_field on ".$table_prefix."_def_org_field.fieldid=".$table_prefix."_field.fieldid ";
		$sql.= " where ".$table_prefix."_field.tabid in ($marks) and ";
		$sql.= $table_prefix."_field.displaytype in (1,2,3) and ".$table_prefix."_def_org_field.visible=0";

		$params[] = $tabid;
		if ($tabid == 9){
			$params[] = 16;
		}
 	    $sql.=" AND EXISTS(SELECT * FROM ".$table_prefix."_profile2field WHERE ".$table_prefix."_profile2field.fieldid = ".$table_prefix."_field.fieldid ";
        if (count($profileList) > 0) {
		  	 $sql.=" AND ".$table_prefix."_profile2field.profileid IN (". generateQuestionMarks($profileList) .") ";
		  	 array_push($params, $profileList);
		}
	    $sql.=" AND ".$table_prefix."_profile2field.visible = 0) ";

		if($tabid == 13 || $tabid == 15)
		{
			$sql.= " and ".$table_prefix."_field.fieldlabel != 'Add Comment'";
		}
		if($tabid == 14)
		{
			$sql.= " and ".$table_prefix."_field.fieldlabel != 'Product Image'";
		}
		if($tabid == 9 || $tabid==16)
		{
			$sql.= " and ".$table_prefix."_field.fieldname not in('notime','duration_minutes','duration_hours')";
		}
		if($tabid == 4)
		{
			$sql.= " and ".$table_prefix."_field.fieldlabel != 'Contact Image'";
		}
		if($tabid == 13 || $tabid == 10)
		{
			$sql.= " and ".$table_prefix."_field.fieldlabel != 'Attachment'";
		}
		$sql .= " order by block,sequence";
	}

	$result = $adb->pquery($sql, $params);
	$noofrows = $adb->num_rows($result);
	$block = '';
	$select_flag = '';

	for($i=0; $i<$noofrows; $i++)
	{
		$fieldtablename = $adb->query_result($result,$i,"tablename");
		$fieldcolname = $adb->query_result($result,$i,"columnname");
		$block = $adb->query_result($result,$i,"block");
		$fieldtype = $adb->query_result($result,$i,"typeofdata");
		$fieldtype = explode("~",$fieldtype);
		$fieldtypeofdata = $fieldtype[0];
		if($fieldcolname == 'account_id' || $fieldcolname == 'accountid' || $fieldcolname == 'product_id' || $fieldcolname == 'vendor_id' || $fieldcolname == 'contact_id' || $fieldcolname == 'contactid' || $fieldcolname == 'vendorid' || $fieldcolname == 'potentialid' || $fieldcolname == 'salesorderid' || $fieldcolname == 'quoteid' || $fieldcolname == 'parentid' || $fieldcolname == "recurringtype" || $fieldcolname == "campaignid" || $fieldcolname == "inventorymanager" ||  $fieldcolname == "handler" ||  $fieldcolname == "currency_id")
			$fieldtypeofdata = "V";
		if($fieldcolname == "discontinued" || $fieldcolname == "active")
			$fieldtypeofdata = "C";
		$fieldlabel = $mod_strings[$adb->query_result($result,$i,"fieldlabel")];

		// Added to display customfield label in search options
		if($fieldlabel == "")
			$fieldlabel = $adb->query_result($result,$i,"fieldlabel");

		if($fieldlabel == "Related To")
		{
			$fieldlabel = "Related to";
		}
		if($fieldlabel == "Start Date & Time")
		{
			$fieldlabel = "Start Date";
			if($module == 'Activities' && $block == 19)
				$module_columnlist[$table_prefix.'_activity:time_start::Activities_Start Time:I'] = 'Start Time';

		}
		//$fieldlabel1 = str_replace(" ","_",$fieldlabel); // Is not used anywhere
		//Check added to search the lists by Inventory manager
                if($fieldtablename == $table_prefix.'_quotes' && $fieldcolname == 'inventorymanager')
                {
                        $fieldtablename = $table_prefix.'_usersQuotes';
                        $fieldcolname = 'user_name';
                }
		if($fieldtablename == $table_prefix.'_contactdetails' && $fieldcolname == 'reportsto')
                {
                        $fieldtablename = $table_prefix.'_contactdetails2';
                        $fieldcolname = 'lastname';
                }
                if($fieldtablename == $table_prefix.'_notes' && $fieldcolname == 'folderid'){
                	$fieldtablename = $table_prefix.'_crmentityfolder'; // crmv@30967
                	$fieldcolname = 'foldername';
                }
		if($fieldlabel != 'Related to')
		{
			if ($i==0)
				$select_flag = "selected";

			$mod_fieldlabel = $mod_strings[$fieldlabel];
			if($mod_fieldlabel =="") $mod_fieldlabel = $fieldlabel;

			if($fieldlabel == "Product Code")
				$OPTION_SET .= "<option value=\'".$fieldtablename.".".$fieldcolname."::::".$fieldtypeofdata."\'".$select_flag.">".$mod_fieldlabel."</option>";
			if($fieldlabel == "Reports To")
				$OPTION_SET .= "<option value=\'".$fieldtablename.".".$fieldcolname."::::".$fieldtypeofdata."\'".$select_flag.">".$mod_fieldlabel." - ".$mod_strings['LBL_LIST_LAST_NAME']."</option>";
			elseif($fieldcolname == "contactid" || $fieldcolname == "contact_id")
			{
				$OPTION_SET .= "<option value=\'".$table_prefix."_contactdetails.lastname::::".$fieldtypeofdata."\' ".$select_flag.">".$app_strings['LBL_CONTACT_LAST_NAME']."</option>";
				$OPTION_SET .= "<option value=\'".$table_prefix."_contactdetails.firstname::::".$fieldtypeofdata."\'>".$app_strings['LBL_CONTACT_FIRST_NAME']."</option>";
			}
			elseif($fieldcolname == "campaignid")
				$OPTION_SET .= "<option value=\'".$table_prefix."_campaign.campaignname::::".$fieldtypeofdata."\' ".$select_flag.">".$mod_fieldlabel."</option>";
			else
				$OPTION_SET .= "<option value=\'".$fieldtablename.".".$fieldcolname."::::".$fieldtypeofdata."\' ".$select_flag.">".str_replace("'","`",$fieldlabel)."</option>";
		}
	}
	//Added to include Ticket ID in HelpDesk advance search
	if($module == 'HelpDesk')
	{
		$mod_fieldlabel = $mod_strings['Ticket ID'];
                if($mod_fieldlabel =="") $mod_fieldlabel = 'Ticket ID';

		$OPTION_SET .= "<option value=\'".$table_prefix."_crmentity.crmid::::".$fieldtypeofdata."\'>".$mod_fieldlabel."</option>";
	}
	//Added to include activity type in activity advance search
	if($module == 'Activities')
	{
		$mod_fieldlabel = $mod_strings['Activity Type'];
                if($mod_fieldlabel =="") $mod_fieldlabel = 'Activity Type';

		$OPTION_SET .= "<option value=\'".$table_prefix."_activity.activity$table_prefix.':".$fieldtypeofdata."\'>".$mod_fieldlabel."</option>";
	}
	$log->debug("Exiting getAdvSearchfields method ...");
	return $OPTION_SET;
}

/**This function is returns the search criteria options for Advance Search
*takes no parameter
*Returns the criteria option in html format
*/

function getcriteria_options()
{
	global $log,$app_strings;
	$log->debug("Entering getcriteria_options() method ...");
	//crmv@159559
	$CRIT_OPT = "<option value='c'>".str_replace("'","`",$app_strings['contains']).
			"</option><option value='k'>".str_replace("'","`",$app_strings['does_not_contains']).
			"</option><option value='e'>".str_replace("'","`",$app_strings['is']).
			"</option><option value='n'>".str_replace("'","`",$app_strings['is_not']).
			"</option><option value='s'>".str_replace("'","`",$app_strings['begins_with']).
			"</option><option value='ew'>".str_replace("'","`",$app_strings['ends_with']).
			"</option><option value='g'>".str_replace("'","`",$app_strings['greater_than']).
			"</option><option value='l'>".str_replace("'","`",$app_strings['less_than']).
			"</option><option value='h'>".str_replace("'","`",$app_strings['greater_or_equal']).
			"</option><option value='m'>".str_replace("'","`",$app_strings['less_or_equal']).
			"</option>";
	//crmv@159559e
	$log->debug("Exiting getcriteria_options method ...");
	return $CRIT_OPT;
}

/**This function is returns the where conditions for each search criteria option in Advance Search
*Param $criteria - search criteria option
*Param $searchstring - search string
*Param $searchfield - vte_fieldname to be search for
*Returns the search criteria option (where condition) to be added in list query
*/

function getSearch_criteria($criteria,$searchstring,$searchfield)
{
	global $log, $table_prefix;
	$log->debug("Entering getSearch_criteria(".$criteria.",".$searchstring.",".$searchfield.") method ...");
	$searchstring = ltrim(rtrim($searchstring));
	//crmv fix advanced search
	if($searchfield == $table_prefix."_account.parentid")
		$searchfield = $table_prefix."_account2.accountname";
	if($searchfield == $table_prefix."_pricebook.currency_id" || $searchfield == $table_prefix."_quotes.currency_id" || $searchfield == $table_prefix."_invoice.currency_id"
			|| $searchfield == $table_prefix."_purchaseorder.currency_id" || $searchfield == $table_prefix."_salesorder.currency_id")
		$searchfield = $table_prefix."_currency_info.currency_name";
	$where_string = '';
	switch($criteria)
	{
		case 'cts':
			$where_string = $searchfield." like '". formatForSqlLike($searchstring) ."' ";
			if($searchstring == NULL)
			{
					$where_string = "(".$searchfield." like '' or ".$searchfield." is NULL)";
			}
			break;
//crmv@8982
		case 'dcts':
			if($searchfield == $table_prefix."_users.user_name" || $searchfield ==$table_prefix."_groups.groupname")
				$where_string = "(".$searchfield." not like '". formatForSqlLike($searchstring) ."')";
			else
				$where_string = "(".$searchfield." not like '". formatForSqlLike($searchstring) ."' or ".$searchfield." is null)";
			if($searchstring == NULL)
			$where_string = "(".$searchfield." not like '' or ".$searchfield." is not NULL)";
			break;

		case 'is':
			$where_string = $searchfield." = '".$searchstring."' ";
			if($searchstring == NULL)
			$where_string = "(".$searchfield." is NULL or ".$searchfield." = '')";
			break;

		case 'isn':
			if($searchfield == $table_prefix."_users.user_name" || $searchfield ==$table_prefix."_groups.groupname")
				$where_string = "(".$searchfield." <> '".$searchstring."')";
			else
				$where_string = "(".$searchfield." <> '".$searchstring."' or ".$searchfield." is null)";
			if($searchstring == NULL)
			$where_string = "(".$searchfield." not like '' and ".$searchfield." is not NULL)";
			break;

		case 'bwt':
			$where_string = $searchfield." like '". formatForSqlLike($searchstring, 2) ."' ";
			break;

		case 'ewt':
			$where_string = $searchfield." like '". formatForSqlLike($searchstring, 1) ."' ";
			break;

		case 'grt':
			$where_string = $searchfield." > '".$searchstring."' ";
			break;

		case 'lst':
			$where_string = $searchfield." < '".$searchstring."' ";
			break;

		case 'grteq':
			$where_string = $searchfield." >= '".$searchstring."' ";
			break;

		case 'lsteq':
			$where_string = $searchfield." <= '".$searchstring."' ";
			break;
		case (in_array($criteria,Array('is_multiple','cts_multiple'))) :
			$where_string = $searchfield." in (".$searchstring.") ";
			break;
		case (in_array($criteria,Array('isn_multiple','dcts_multiple'))):
			$where_string = $searchfield." not in (".$searchstring.") ";
			break;
		case 'bwt_multiple':
			$where_string = $searchfield." like ";
			$string_split = explode(",",$searchstring);
			$cnt == 0;
			foreach ($string_split as $str){
				if ($cnt == 0){
					$where_string.="'".formatForSqlLike($str,2) ."'";
				}
				else $where_string.=" or ".$searchfield." like '".formatForSqlLike($str,2) ."'";
				$cnt++;
			}
			break;
		case 'ewt_multiple':
			$where_string = $searchfield." like ";
			$string_split = explode(",",$searchstring);
			$cnt == 0;
			foreach ($string_split as $str){
				if ($cnt == 0){
					$where_string.="'".formatForSqlLike($str,1) ."'";
				}
				else $where_string.=" or ".$searchfield." like '".formatForSqlLike($str,1) ."'";
				$cnt++;
			}
			break;
		case 'grt_multiple':
			$where_string = $searchfield." > ";
			$string_split = explode(",",$searchstring);
			$cnt == 0;
			foreach ($string_split as $str){
				if ($cnt == 0){
					$where_string.="'".$str."'";
				}
				else $where_string.=" or ".$searchfield." > '".$str."'";
				$cnt++;
			}
			break;
		case 'lst_multiple':
			$where_string = $searchfield." < ";
			$string_split = explode(",",$searchstring);
			$cnt == 0;
			foreach ($string_split as $str){
				if ($cnt == 0){
					$where_string.="'".$str."'";
				}
				else $where_string.=" or ".$searchfield." < '".$str."'";
				$cnt++;
			}
			break;
		case 'grteq_multiple':
			$where_string = $searchfield." >= ";
			$string_split = explode(",",$searchstring);
			$cnt == 0;
			foreach ($string_split as $str){
				if ($cnt == 0){
					$where_string.="'".$str."'";
				}
				else $where_string.=" or ".$searchfield." >= '".$str."'";
				$cnt++;
			}
			break;
		case 'lsteq_multiple':
			$where_string = $searchfield." <= ";
			$string_split = explode(",",$searchstring);
			$cnt == 0;
			foreach ($string_split as $str){
				if ($cnt == 0){
					$where_string.="'".$str."'";
				}
				else $where_string.=" or ".$searchfield." <= '".$str."'";
				$cnt++;
			}
			break;
//crmv@8982 e

	}
	$log->debug("Exiting getSearch_criteria method ...");
	return $where_string;
}

/**This function is returns the where conditions for search
*Param $currentModule - module name
*Returns the where condition to be added in list query in string format
*/

function getWhereCondition($currentModule)
{
	global $log,$default_charset,$adb,$table_prefix;
	global $column_array,$table_col_array,$mod_strings,$current_user;

	$log->debug("Entering getWhereCondition(".$currentModule.") method ...");

	if($_REQUEST['searchtype']=='advance')
	{
		$adv_string='';
		$url_string='';
		if(isset($_REQUEST['search_cnt']))
		$tot_no_criteria = vtlib_purify($_REQUEST['search_cnt']);
		if($_REQUEST['matchtype'] == 'all')
			$matchtype = "and";
		else
			$matchtype = "or";
		for($i=0; $i<$tot_no_criteria; $i++)
		{
			if($i == $tot_no_criteria-1)
			$matchtype= "";

			$table_colname = 'Fields'.$i;
			$search_condition = 'Condition'.$i;
			$search_value = 'Srch_value'.$i;

			list($tab_col_val,$typeofdata) = explode("::::",$_REQUEST[$table_colname]);
			$tab_col = str_replace('\'','',stripslashes($tab_col_val));
			//crmv@31979
			if (strpos($tab_col,"[.]") !== false) {
				list($table_name,$col_name) = explode("[.]",$tab_col);
			} elseif (strpos($tab_col,'.') !== false) {
				list($table_name,$table_name) = explode('.',$tab_col);
			}
			//crmv@31979 e	
			if($col_name == "reports_to_id" && $table_name == $table_prefix."_projects") {
				$col_name = "user_name";
				$table_name = "users_projectleader";
				$tab_col = $table_name.'.'.$col_name;
			}

			$srch_cond = str_replace('\'','',stripslashes($_REQUEST[$search_condition]));
			$srch_cond = convert_to_old_condition($srch_cond);
			$srch_val = $_REQUEST[$search_value];
			//crmv@fix advanced search
			if (isset($_REQUEST['fields_to_convert']) && $_REQUEST['fields_to_convert'] != ''){
				$split = explode(";",$_REQUEST['fields_to_convert']);
				if (in_array($i,$split)){
					list($temp_date,$temp_time) = explode(" ",$srch_val);
					$temp_date = getDBInsertDateValue(trim($temp_date));
					if(trim($temp_time) != '')
						$temp_date .= ' '.$temp_time;
					$srch_val = $temp_date;
				}
			}
			//crmv@fix advanced search e
			$srch_val = function_exists('iconv') ? @iconv("UTF-8",$default_charset,$srch_val) : $srch_val; // crmv@167702
			$url_string .="&Fields".$i."=".$tab_col."&Condition".$i."=".$srch_cond."&Srch_value".$i."=".urlencode($srch_val);
			$srch_val = $adb->sql_escape_string($srch_val);
			//crmv@31979
			if (strpos($tab_col,"[.]") !== false) {
				list($tab_name,$column_name) = explode("[.]",$tab_col);
			} elseif (strpos($tab_col,'.') !== false) {
				list($tab_name,$column_name) = explode('.',$tab_col);
			}
			//crmv@31979e
			$uitype=getUItype($currentModule,$column_name);
			//added to allow  search in check box type fields(ex: product active. it will contain 0 or 1) using yes or no instead of 0 or 1
			//crmv@128159
			if (SDK::isUitype($uitype)) {
				$sdk_file = SDK::getUitypeFile('php','popupadvancedsearch',$uitype);
				if ($sdk_file != '') {
					include($sdk_file);
					$adv_string .= " ".getSearch_criteria($srch_cond,$srch_val,$tab_col)." ".$matchtype;
				}
			}
			//crmv@128159e
			elseif ($uitype == 56)
			{
				if(strtolower($srch_val) == 'yes')
                	$adv_string .= " ".getSearch_criteria($srch_cond,"1",$tab_name.'.'.$column_name)." ".$matchtype;
				elseif(strtolower($srch_val) == 'no')
                	$adv_string .= " ".getSearch_criteria($srch_cond,"0",$tab_name.'.'.$column_name)." ".$matchtype;
				else
					$adv_string .= " ".getSearch_criteria($srch_cond,"-1",$tab_name.'.'.$column_name)." ".$matchtype;
			}
			elseif ($uitype == 111 || $uitype == 15 || $uitype == 16)
			{
				//crmv@17997
				if(is_uitype($uitype, '_picklist_')) {
					$qgen_obj = QueryGenerator::getInstance($currentModule,$current_user);
					$value_trans = $qgen_obj->getReverseTranslate($srch_val,$srch_cond);
					if ($value_trans != $srch_val){
						$cond_ = $srch_cond.'_multiple';
						$srch_val = "'$srch_val'";
						$srch_val .= ",'".$value_trans."'";
					}
					else
						$cond_ = $srch_cond;
					if(getFieldVisibilityPermission("Calendar", $current_user->id,'taskstatus') == '0' && ($tab_col == $table_prefix."_activity.status" || $tab_col == $table_prefix."_activity.eventstatus")){
						if($srch_cond == 'dcts' || $srch_cond == 'isn' || $srch_cond == 'is')
							$re_cond = "and";
						else
							$re_cond = "or";
						if($srch_cond == 'is' && $srch_val !='')
							$re_cond = "or";

						$adv_string .= " (".getSearch_criteria($cond_,$srch_val,$table_prefix.'_activity.status')." ".$re_cond;
						$adv_string .= " ".getSearch_criteria($cond_,$srch_val,$table_prefix.'_activity.eventstatus')." )".$matchtype;
					}
					else
						$adv_string .= " ".getSearch_criteria($cond_,$srch_val,$table_name.".".$column_name);

					$adv_string.=" ".$matchtype;
				}
				//crmv@17997 end
			}
			//crmv@8982 campi picklist multilingua
			elseif($uitype == 1015)
			{
				$cond_ = $srch_cond.'_multiple';
				global $adb;
				if (!in_array($srch_cond,Array('is','isn'))){
					if($_REQUEST['type'] == 'alpbt')
					{
				        $qry_val = "$srch_val%";
					}
					else $qry_val = "%$srch_val%";
					$cond_val = "like '$qry_val'";
				}
				else {
					$cond_val = " = '$srch_val'";
				}
				global $current_language;
				$ssql = "select code from tbl_s_picklist_language where value $cond_val and field = '$column_name' and language = '$current_language'";
				$res=$adb->query($ssql);
				if ($res){
					$cnt == 0;
					while ($row = $adb->fetchByAssoc($res,-1,false)){
						if ($cnt == 0){
							if (!in_array($srch_cond,Array('is','isn','cts','dcts')))
								$srch_val = $row['code'];
							else
								$srch_val = "'".$row['code']."'";
						}
						else{
							if (!in_array($srch_cond,Array('is','isn','cts','dcts')))
								$srch_val .= ",".$row['code'];
							else
								$srch_val .= ",'".$row['code']."'";
						}
						$cnt++;
					}
					if ($cnt == 0){
						$adv_string .= getSearch_criteria($srch_cond,$srch_val,$table_name.".".$column_name);
					}
					else {
						$adv_string .= getSearch_criteria($cond_,$srch_val,$table_name.".".$column_name);
					}
				}
				else $adv_string .= getSearch_criteria($srch_cond,$srch_val,$table_name.".".$column_name);
				$adv_string.=" ".$matchtype;
			}
			//crmv@8982 e
			//crmv@31979
			elseif($uitype == 26)
			{
				$adv_string .= " ".getSearch_criteria($srch_cond,$srch_val,$table_prefix.'_crmentityfolder.foldername')." ".$matchtype;
			}
			//crmv@31979e
			elseif($tab_col == $table_prefix."_crmentity.smownerid")
			{
				$adv_string .= " (".getSearch_criteria($srch_cond,$srch_val,$table_prefix.'_users.user_name')." or";
				$adv_string .= " ".getSearch_criteria($srch_cond,$srch_val,$table_prefix.'_groups.groupname')." )".$matchtype;
			}
			elseif($tab_col == $table_prefix."_cntactivityrel.contactid")
			{
				$adv_string .= " (".getSearch_criteria($srch_cond,$srch_val,$table_prefix.'_contactdetails.firstname')." or";
				$adv_string .= " ".getSearch_criteria($srch_cond,$srch_val,$table_prefix.'_contactdetails.lastname')." )".$matchtype;
			}
			elseif(in_array($column_name,$column_array))
			{
				$adv_string .= " ".getValuesforColumns($column_name,$srch_val,$srch_cond)." ".$matchtype;
			}
			else
			{
				$adv_string .= " ".getSearch_criteria($srch_cond,$srch_val,$tab_col)." ".$matchtype;
			}
		}
		$where="(".$adv_string.")#@@#".$url_string."&searchtype=advance&search_cnt=".$tot_no_criteria."&matchtype=".$_REQUEST['matchtype'];
	}
	else//crmv@208472
	{
 		$where=Search($currentModule);
	}
	$log->debug("Exiting getWhereCondition method ...");
	return $where;

}

function getSearchURL($input) {
	global $log,$default_charset;
	$urlString='';
	if ($input['searchtype']=='advance') {
		if(empty($input['search_cnt'])) {
			return $urlString;
		}
		$noOfConditions = vtlib_purify($input['search_cnt']);
		for($i=0; $i<$noOfConditions; $i++) {
			$fieldInfo = 'Fields'.$i;
			$condition = 'Condition'.$i;
			$value = 'Srch_value'.$i;

			list($fieldName,$typeOfData) = explode("::::",str_replace('\'','',
					stripslashes($input[$fieldInfo])));
			$operator = str_replace('\'','',stripslashes($input[$condition]));
			$searchValue = $input[$value];
			$searchValue = function_exists('iconv') ? @iconv("UTF-8",$default_charset, // crmv@167702
					$searchValue) : $searchValue;
			$urlString .="&Fields$i=$fieldName&Condition$i=$operator&Srch_value$i=".
					urlencode($searchValue);
		}
		$urlString .= "&searchtype=".$input['searchtype']."&search_cnt=$noOfConditions&matchtype=".
				vtlib_purify($input['matchtype']);
	} elseif($input['type']=='dbrd') {
		if(isset($input['leadsource'])) {
			$leadSource = vtlib_purify($input['leadsource']); // crmv@26907
			$urlString .= "&leadsource=".$leadSource;
		}
		if(isset($input['date_closed'])) {
			$dateClosed = vtlib_purify($input['date_closed']); // crmv@26907
			$urlString .= "&date_closed=".$dateClosed;
		}
		if(isset($input['sales_stage'])) {
			$salesStage = vtlib_purify($input['sales_stage']); // crmv@26907
			$urlString .= "&sales_stage=".$salesStage;
		}
		if(!empty($input['closingdate_start']) && !empty($input['closingdate_end'])) {
			$dateClosedStart = vtlib_purify($input['closingdate_start']);  // crmv@26907
			$dateClosedEnd = vtlib_purify($input['closingdate_end']);  // crmv@26907
			$urlString .= "&closingdate_start=$dateClosedStart&closingdate_end=".$dateClosedEnd;
		}
		if(isset($input['owner'])) {
			$owner = vtlib_purify($input['owner']);
			$urlString .= "&owner=".$owner;
		}
		if(isset($input['campaignid'])) {
			$campaignId = vtlib_purify($input['campaignid']);
			$urlString .= "&campaignid=".$campaignId;
		}
		if(isset($input['quoteid'])) {
			$quoteId = vtlib_purify($input['quoteid']);
			$urlString .= "&quoteid=".$quoteId;
		}
		if(isset($input['invoiceid'])) {
			$invoiceId = vtlib_purify($input['invoiceid']);
			$urlString .= "&invoiceid=".$invoiceId;
		}
		if(isset($input['purchaseorderid'])) {
			$purchaseOrderId = vtlib_purify($input['purchaseorderid']);
			$urlString .= "&purchaseorderid=".$purchaseOrderId;
		}

		if(isset($input['from_homepagedb']) && $input['from_homepagedb'] != '') {
			$url_string .= "&from_homepagedb=".vtlib_purify($input['from_homepagedb']);
		}
		if(isset($input['type']) && $input['type'] != '') {
			$url_string .= "&type=".vtlib_purify($input['type']);
		}
	} else {
		$value = $input['search_text'];
		$stringConvert = function_exists('iconv') ? @iconv("UTF-8",$default_charset,$value) : //crmv@167234
				$value;
		$value=trim($stringConvert);
		$field=vtlib_purify($input['search_field']);
 		$urlString = "&search_field=$field&search_text=".urlencode($value)."&searchtype=BasicSearch";
		if(!empty($input['type'])) {
			$urlString .= "&type=".vtlib_purify($input['type']);
		}
		if(!empty($input['operator'])) {
			$urlString .= "&operator=".$input['operator'];
		}
	}
	//crmv@3084m
	if(!empty($input['GridSearchCnt'])) {
		$noOfConditions = vtlib_purify($input['GridSearchCnt']);
		for($i=0; $i<$noOfConditions; $i++) {
			$fieldInfo = 'GridFields'.$i;
			$condition = 'GridCondition'.$i;
			$value = 'GridSrch_value'.$i;

			list($fieldName,$typeOfData) = explode("::::",str_replace('\'','',
					stripslashes($input[$fieldInfo])));
			$operator = str_replace('\'','',stripslashes($input[$condition]));
			$searchValue = urldecode($input[$value]);
			$searchValue = function_exists('iconv') ? @iconv("UTF-8",$default_charset,$searchValue) : $searchValue; // crmv@167702
			$urlString .="&GridFields$i=$fieldName&GridCondition$i=$operator&GridSrch_value$i=".urlencode($searchValue);
		}
		$urlString .= "&GridSearchCnt=$noOfConditions";
	}
	//crmv@3084me
	return $urlString;
}

//crmv@208472

/**This function is used to replace only the first occurence of a given string
Param $needle - string to be replaced
Param $replace - string to be replaced with
Param $replace - given string
Return type is string
*/
function str_replace_once($needle, $replace, $haystack)
{
	// Looks for the first occurence of $needle in $haystack
	// and replaces it with $replace.
	$pos = strpos($haystack, $needle);
	if ($pos === false) {
		// Nothing found
		return $haystack;
	}
	return substr_replace($haystack, $replace, $pos, strlen($needle));
}

/**
 * Function to get the where condition for a module based on the field table entries
 * @param  string $listquery  -- ListView query for the module
 * @param  string $module     -- module name
 * @param  string $search_val -- entered search string value
 * @return string $where      -- where condition for the module based on field table entries
 */
function getUnifiedWhere($listquery,$module,$search_val){
	global $adb, $current_user,$table_prefix;
	require('user_privileges/requireUserPrivileges.php'); // crmv@39110

	$search_val = $adb->sql_escape_string($search_val);
	//crmv@11691
	$search_cond = "";
	if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] ==0){
		$query = "SELECT columnname, tablename FROM ".$table_prefix."_field WHERE tabid = ? and ".$table_prefix."_field.presence in (0,2) $search_cond";
		$qparams = array(getTabid($module));
	}else{
		$profileList = getCurrentUserProfileList();
		$query = "SELECT columnname, tablename FROM ".$table_prefix."_field INNER JOIN ".$table_prefix."_def_org_field ON ".$table_prefix."_def_org_field.fieldid = ".$table_prefix."_field.fieldid WHERE ".$table_prefix."_field.tabid = ? AND ".$table_prefix."_def_org_field.visible = 0 and ".$table_prefix."_field.presence in (0,2) ";
	    $qparams = array(getTabid($module));
 	    $query.=" AND EXISTS(SELECT * FROM ".$table_prefix."_profile2field WHERE ".$table_prefix."_profile2field.fieldid = ".$table_prefix."_field.fieldid ";
            if (count($profileList) > 0) {
	  	  		$query.=" AND ".$table_prefix."_profile2field.profileid IN (". generateQuestionMarks($profileList) .") ";
	  	 		array_push($qparams, $profileList);
	    }
 	    $query.=" AND ".$table_prefix."_profile2field.visible = 0) ";
		$query.=" $search_cond ";
	}
	//crmv@11691 e
	$result = $adb->pquery($query, $qparams);
	$noofrows = $adb->num_rows($result);

	$where = '';
	for($i=0;$i<$noofrows;$i++){
		$columnname = $adb->query_result($result,$i,'columnname');
		$tablename = $adb->query_result($result,$i,'tablename');

		// Search / Lookup customization
		if($module == 'Contacts' && $columnname == 'accountid') {
			$columnname = "accountname";
			$tablename = $table_prefix."_account";
		}
		// END

		//Before form the where condition, check whether the table for the field has been added in the listview query
		if(strstr($listquery,$tablename)){
			if($where != ''){
				$where .= " OR ";
			}
			$where .= $tablename.".".$columnname." LIKE '". formatForSqlLike($search_val) ."'";
		}
	}
	return $where;
}