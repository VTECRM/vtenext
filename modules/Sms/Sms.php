<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@152701 */

// Sms is used to store customer information.
class Sms extends CRMEntity {

	var $log;
	var $db;
	var $table_name;
	var $table_index = "smsid";	//crmv@16703

	var $rel_users_table;
	var $rel_contacts_table;
	var $rel_serel_table;

	var $tab_name = Array();
    var $tab_name_index = Array();

	// This is the list of vte_fields that are in the lists.
    var $list_fields = Array(
		'Description'=>Array('sms'=>'description'), // crmv@150773
		'Date Sent'=>Array('sms'=>'date_start'),
		'Assigned To'=>Array('crmentity','smownerid')
	);

	var $list_fields_name = Array(
		'Description'=>'description',
		'Date Sent'=>'date_start',
		'Assigned To'=>'assigned_user_id'
	);

	var $list_link_field= 'description';

	var $column_fields = Array();

	var $sortby_fields = Array('description','date_start','smownerid');

	//Added these variables which are used as default order by and sortorder in ListView
	var $default_order_by = 'date_start';
	var $default_sort_order = 'ASC';

	/** This function will set the columnfields for Email module
	*/

	function __construct() {
		global $table_prefix;
		parent::__construct(); // crmv@37004

		$this->table_name = $table_prefix."_sms";
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_sms');
		$this->tab_name_index = Array($table_prefix.'_crmentity'=>'crmid',$table_prefix.'_sms'=>'smsid');

		$this->log = LoggerManager::getLogger('sms');
		$this->db = new PearDatabase();
		$this->column_fields = getColumnFields('Sms');
	}

	function retrieve_entity_info($record, $module, $dieOnError=true, $onlyFields = array()) {
		global $adb, $table_prefix;
		$ret = parent::retrieve_entity_info($record, $module, $dieOnError, $onlyFields);
		
		// change some fields
		$sql = "SELECT sms_flag FROM {$table_prefix}_smsdetails WHERE smsid=?";
		$result = $adb->pquery($sql, array($record));
		if ($result) {
			$sms_flag = $adb->query_result_no_html($result,0,"sms_flag");
			if($sms_flag != 'SENT') {
				$this->column_fields['date_start'] = '';
			}
		}
		
		return $ret;
	}

	function save_module($module) {
		global $adb, $table_prefix, $current_user;

		// save relation manually
		if ($this->id && $this->parent_id && $this->parent_type) {
			$ids = array();
			$myids = explode("|", $this->parent_id);  //2@71|
			foreach ($myids as $fullid)	{
				$realid = explode("@", $fullid);
				if ($realid[1] > 0) {
					$ids[] = $realid[0];
				}
			}
			if (count($ids) > 0) {
				$this->save_related_module('Sms', $this->id, $this->parent_type, $ids, true);
			}
		
		
			//saving the sms details in vte_smsdetails vte_table
			$res = $adb->pquery('SELECT phone_mobile FROM '.$table_prefix.'_users WHERE id = ?', array($current_user->id));
			$user_sms = $adb->query_result_no_html($res,0,"phone_mobile");
			
			if(isset($_REQUEST["hidden_toid"]) && $_REQUEST["hidden_toid"]!='')
				$all_to_ids = str_replace(",","###",$_REQUEST["hidden_toid"]);
				
			if(isset($_REQUEST["saved_toid"]) && $_REQUEST["saved_toid"]!='')
				$all_to_ids .= str_replace(",","###",$_REQUEST["saved_toid"]);


			//added to save < as $lt; and > as &gt; in the database so as to retrive the smsID
			$all_to_ids = str_replace('<','&lt;',$all_to_ids);
			$all_to_ids = str_replace('>','&gt;',$all_to_ids);

			$query = 'SELECT smsid FROM '.$table_prefix.'_smsdetails WHERE smsid = ?';
			$result = $adb->pquery($query, array($this->id));
			if($adb->num_rows($result) > 0) {
				$query = 'UPDATE '.$table_prefix.'_smsdetails set to_number=?, idlists=?, sms_flag=\'SAVED\' where smsid = ?';
				$qparams = array($all_to_ids, $this->parent_id, $this->id);
			} else {
				$query = 'INSERT INTO '.$table_prefix.'_smsdetails values (?,?,?,\'\',?,\'SAVED\')';
				$qparams = array($this->id, $user_sms, $all_to_ids, $this->parent_id);
			}
			$adb->pquery($query, $qparams);
		
		}
		
	}


	/**
	* Invoked when special actions are performed on the module.
	* @param String Module name
	* @param String Event Type
	*/
	function vtlib_handler($moduleName, $eventType) {

		require_once('include/utils/utils.php');
		global $adb,$table_prefix;

 		if($eventType == 'module.postinstall') {
			require_once('vtlib/Vtecrm/Module.php');//crmv@207871

			$moduleInstance = Vtecrm_Module::getInstance($moduleName);

			$accModuleInstance = Vtecrm_Module::getInstance('Leads');
			$accModuleInstance->setRelatedList($moduleInstance,'Sms',array('add'),'get_sms');
			Vtecrm_Link::addLink($accModuleInstance->id, 'DETAILVIEWBASIC', 'TITLE_COMPOSE_SMS', "javascript:fnvshobj(this,'sendsms_cont');sendsms('\$MODULE\$','\$RECORD\$');", '', 1);

			$accModuleInstance = Vtecrm_Module::getInstance('Contacts');
			$accModuleInstance->setRelatedList($moduleInstance,'Sms',array('add'),'get_sms');
			Vtecrm_Link::addLink($accModuleInstance->id, 'DETAILVIEWBASIC', 'TITLE_COMPOSE_SMS', "javascript:fnvshobj(this,'sendsms_cont');sendsms('\$MODULE\$','\$RECORD\$');", '', 1);

			// Mark the module as Standard module
			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($moduleName));

			//set sms through mail
			$adb->pquery('insert into tbl_s_smsservertype (server_type,presence) values (?,?)', array('sms_mail',1));

			$moduleInstance->hide(array('hide_report'=>1)); // crmv@38798

 		} else if($eventType == 'module.disabled') {
			$adb->query("UPDATE ".$table_prefix."_settings_field SET active = 1 WHERE name = 'LBL_SMS_SERVER_SETTINGS'");	//crmv@16703
		} else if($eventType == 'module.enabled') {
			$adb->query("UPDATE ".$table_prefix."_settings_field SET active = 0 WHERE name = 'LBL_SMS_SERVER_SETTINGS'");	//crmv@16703
		} else if($eventType == 'module.preuninstall') {
		// TODO Handle actions when this module is about to be deleted.
		} else if($eventType == 'module.preupdate') {
		// TODO Handle actions before this module is updated.
		} else if($eventType == 'module.postupdate') {
		// TODO Handle actions after this module is updated.
		}
 	}

	function unlinkRelationship($id, $return_module, $return_id) {
		global $currentModule;
		$this->trash($currentModule, $id);
	}

}

/** Function to get the emailids for the given ids form the request parameters
 *  It returns an array which contains the mailids and the parentidlists
 */
function get_to_smsids($module)
{
	global $adb,$table_prefix;
	if(isset($_REQUEST["field_lists"]) && $_REQUEST["field_lists"] != "")
	{
		$field_lists = $_REQUEST["field_lists"];
		if (is_string($field_lists)) $field_lists = explode(":", $field_lists);
		$query = 'select columnname,fieldid from '.$table_prefix.'_field where fieldid in('. generateQuestionMarks($field_lists) .')';
		$result = $adb->pquery($query, array($field_lists));
		$columns = Array();
		$idlists = '';
		$smsids = '';
		while($row = $adb->fetch_array($result))
    		{
			$columns[]=$row['columnname'];
			$fieldid[]=$row['fieldid'];
		}
		$columnlists = implode(',',$columns);
		//crmv@27096 //crmv@27917
		$idarray = getListViewCheck($module);
		//crmv@32504
		if (empty($idarray) || isset($_REQUEST['idlist'])) {
			$idstring = $_REQUEST['idlist'];
		} else {
			$idstring = implode(':',$idarray);
		}
		//crmv@32504 e
		//crmv@27096e //crmv@27917e
		$single_record = false;
		if(!strpos($idstring,':'))
		{
			$single_record = true;
		}
		$crmids = str_replace(':',',',$idstring);
		$crmids = explode(",", $crmids);
		switch($module)
		{
			case 'Leads':
				$query = 'select crmid,'.$adb->sql_concat(Array('firstname',"' '",'lastname')).' as entityname,'.$columnlists.' from '.$table_prefix.'_leaddetails
				inner join '.$table_prefix.'_leadaddress on '.$table_prefix.'_leadaddress.leadaddressid='.$table_prefix.'_leaddetails.leadid
				inner join '.$table_prefix.'_crmentity on '.$table_prefix.'_crmentity.crmid='.$table_prefix.'_leaddetails.leadid left join '.$table_prefix.'_leadscf on '.$table_prefix.'_leadscf.leadid = '.$table_prefix.'_leaddetails.leadid where '.$table_prefix.'_crmentity.deleted=0 and '.$table_prefix.'_crmentity.crmid in ('. generateQuestionMarks($crmids) .')';
				break;
			case 'Contacts':
				$query = 'select crmid,'.$adb->sql_concat(Array('firstname',"' '",'lastname')).' as entityname,'.$columnlists.' from '.$table_prefix.'_contactdetails
							inner join '.$table_prefix.'_crmentity on '.$table_prefix.'_crmentity.crmid='.$table_prefix.'_contactdetails.contactid
							left join '.$table_prefix.'_contactscf on '.$table_prefix.'_contactscf.contactid = '.$table_prefix.'_contactdetails.contactid
							LEFT JOIN '.$table_prefix.'_contactsubdetails ON '.$table_prefix.'_contactsubdetails.contactsubscriptionid = '.$table_prefix.'_contactdetails.contactid
							where '.$table_prefix.'_crmentity.deleted=0 and '.$table_prefix.'_crmentity.crmid in ('. generateQuestionMarks($crmids) .')';
				break;
			case 'Accounts':
				$query = 'select crmid,accountname as entityname,'.$columnlists.' from '.$table_prefix.'_account inner join '.$table_prefix.'_crmentity on '.$table_prefix.'_crmentity.crmid='.$table_prefix.'_account.accountid left join '.$table_prefix.'_accountscf on '.$table_prefix.'_accountscf.accountid = '.$table_prefix.'_account.accountid where '.$table_prefix.'_crmentity.deleted=0 and '.$table_prefix.'_crmentity.crmid in ('. generateQuestionMarks($crmids) .')';
				break;
		}
		$result = $adb->pquery($query, array($crmids));
		while($row = $adb->fetch_array($result))
		{
			$name = $row['entityname'];
			for($i=0;$i<count($columns);$i++)
			{
				if($row[$columns[$i]] != NULL && $row[$columns[$i]] !='')
				{
					$idlists .= $row['crmid'].'@'.$fieldid[$i].'|';
					$smsids .= $name.'<'.$row[$columns[$i]].'>,';
				}
			}
		}

		$return_data = Array('idlists'=>$idlists,'smsids'=>$smsids);
	}else
	{
		$return_data = Array('idlists'=>"",'smsids'=>"");
	}
	return $return_data;

}