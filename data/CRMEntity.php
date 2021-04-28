<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

include_once('config.php');
require_once('include/logging.php');
require_once('data/Tracker.php');
require_once('include/utils/utils.php');
require_once('include/utils/UserInfoUtil.php');
require_once('include/utils/FileStorage.php'); // crmv@95157
require_once("include/Zend/Json.php");

//crmv@33801
if (file_exists('modules/SDK/src/VTEEntity.php')) {
	require_once('modules/SDK/src/VTEEntity.php');
}

if (!class_exists('VTEEntity')) {
	class VTEEntity extends SDKExtendableClass { }
}

class CRMEntityBase extends VTEEntity
//crmv@33801e
{
	public $ownedby;
	public $modulename; // crmv@38798
  
  	//crmv@61173
	public $relatedEditButton = true;
	public $relatedDeleteButton = true;
	//crmv@61173e

	// crmv@83228
	public $has_detail_charts = false;
	public $has_list_charts = false;
	// crmv@83228e
	
	//crmv@176621 removed showProcessGraphTab crmv@101506

	protected $partialData = false; // crmv@113417
	
	public $formatFieldsForSave = true;	//crmv@130458
	
	protected $userTimezone = null; // crmv@163361

	/**
	 * Detect if we are in bulk save mode, where some features can be turned-off
	 * to improve performance.
	 */
	static function isBulkSaveMode() {
		global $BULK_SAVE_MODE;
		if (isset($BULK_SAVE_MODE) && $BULK_SAVE_MODE) {
			return true;
		}
		return false;
	}

	//crmv@37004
	function __construct() {
		global $table_prefix;
		// N-N relations info
		$this->relation_table = "{$table_prefix}_crmentityrel";
		$this->relation_table_id = 'crmid';
		$this->relation_table_otherid = 'relcrmid';
		$this->relation_table_module = 'module';
		$this->relation_table_othermodule = 'relmodule';
		$this->relation_table_ord = "{$table_prefix}_crmentityrel_ord"; // crmv@125816
		// call parent (if exists)
		if (method_exists(get_parent_class(), '__construct')) parent::__construct();
	}
	//crmv@37004e

	//crmv@31355 crmv@42024
	public static function getInstance($module = null) {
		$arguments = func_get_args();
		
		// crmv@164120 crmv@164122
		// compatibility fix, in case someone instances the changelog the old way
		if ($module == 'ChangeLog') {
			require_once('modules/ChangeLog/ChangeLog.php');
			$focus = ChangeLog::getInstance();
			return $focus;
		} elseif ($module == 'ModNotifications') {
			require_once('modules/ModNotifications/ModNotifications.php');
			$focus = ModNotifications::getInstance();
			return $focus;
		}
		// crmv@164120e crmv@164122e

		// fix for calendar, to be sure the class exists
		if ($module == 'Calendar' || $module == 'Events' || $module == 'Activity') {
			require_once("modules/Calendar/Activity.php");
			$module = $arguments[0] = 'Activity';
		}

		// call in this way, to pass all the arguments
		$focus = call_user_func_array(array(get_parent_class(), 'getInstanceByName'), $arguments);
		$focus->modulename = $module; //crmv@38798
		return $focus;
	}
	//crmv@31355e crmv@42024e
	
	// crmv@163361
	public function overrideTimezone($tz) {
		global $default_timezone;
		if ($tz == 'default') $tz = $default_timezone;
		$this->userTimezone = $tz;
	}
	
	public function clearOverrideTimezone() {
		$this->userTimezone = null;
	}
	// crmv@163361e

	function saveentity($module,$fileid='',$longdesc=false)	//crmv@16877
	{
		global $current_user, $adb, $table_prefix;//$adb added by raju for mass mailing
		//crmv@106857
		global $table_fields;
		//$table_fields = array();
		//crmv@106857e
		$insertion_mode = $this->mode;
		
		//crmv@171832
		$VTEP = VTEProperties::getInstance();
		if ($VTEP->getProperty('performance.editview_changelog') == 1 && $insertion_mode == 'edit' && !in_array($module,EditViewChangeLog::$skip_modules)) $editview_tag = $this->calculateWritableFields();
		//crmv@171832e

		/* crmv@182434
		$this->db->println("TRANS saveentity starts $module");
		$this->db->startTransaction();
		*/

		// crmv@80298 - fields for email cache
		$this->hasAddressFields = false;
		$this->emailAddresses = array();
		
		$this->saveWithLongDesc = $longdesc; // crmv@150773
		foreach($this->tab_name as $table_name) {

			if($table_name == $table_prefix."_crmentity")
			{
				$this->insertIntoCrmEntity($module,$fileid,$longdesc);
			}
			else
			{
				$this->insertIntoEntityTable($table_name, $module,$fileid);
			}
		}
		$this->saveWithLongDesc = false; // crmv@150773
		
		if ($this->hasAddressFields) {
			require_once('include/utils/EmailDirectory.php');
			$emailDirectory = new EmailDirectory();
			$emailDirectory->deleteById($this->id);			
			if (count($this->emailAddresses) > 0 && $emailDirectory->isModuleEnabled($module)) {	//crmv@143630
				foreach ($this->emailAddresses as $address) {
					$emailDirectory->save($address, $this->id, $module);
				}
			}
		}
		//crmv@80298e
		
		//crmv@106857
		if (!empty($table_fields[$this->id])) {
			require_once('include/utils/ModLightUtils.php');
			$MLUtils = ModLightUtils::getInstance();
			$MLUtils->saveTableFields($this);
		}
		//crmv@106857e
		
		//crmv@144125
		// update the entityname
		$ENU = EntityNameUtils::getInstance();
		$ENU->saveCachedName($module, $this->id);
		//crmv@144125e

		//Calling the Module specific save code
		$this->save_module($module);
		
		//crmv@171832
		if ($VTEP->getProperty('performance.editview_changelog') == 1 && $insertion_mode == 'edit' && !in_array($module,EditViewChangeLog::$skip_modules)){
			unset($this->writable_fields);
			if (!empty($editview_tag)){
				if (method_exists('EditViewChangeLog','markdelete_editview')){
					EditViewChangeLog::markdelete_editview($editview_tag);
				}
				unset($editview_tag);
			}
		}
		//crmv@171832e

		/* crmv@182434
		$this->db->completeTransaction();
		$this->db->println("TRANS saveentity ends");
		*/
		// vtlib customization: Hook provide to enable generic module relation.

		// Ticket 6386 fix
		
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view'); //crmv@203484
		if (
			(
				$_REQUEST['return_action'] == 'CallRelatedList' ||
				(
					isset($singlepane_view) && $singlepane_view == true &&
					$_REQUEST['return_action'] == 'DetailView' &&
					!empty($_REQUEST['return_module']) &&
					!empty($_REQUEST['return_id']) //crmv@fix oracle
				)
			) && 
			!isset($_REQUEST['save_related_module_done_'.vtlib_purify($_REQUEST['return_module'])]) && //crmv@47905
			($module != 'Documents' || $_REQUEST['return_module'] == $_REQUEST['module']) // crmv@177529
		)
		{
			$for_module = vtlib_purify($_REQUEST['return_module']);
			$for_crmid  = vtlib_purify($_REQUEST['return_id']);
			$_REQUEST['save_related_module_done_'.$for_module] = 1; //crmv@47905
			$on_focus = CRMEntity::getInstance($for_module);
			// Do conditional check && call only for Custom Module at present
			// TODO: $on_focus->IsCustomModule is not required if save_related_module function
			// is used for core modules as well.
			//crmv@22700
			//if($on_focus->IsCustomModule && method_exists($on_focus, 'save_related_module')) {
			if(method_exists($on_focus, 'save_related_module')) {
				//crmv@22700e
				$with_module = $module;
				$with_crmid = $this->id;
				$on_focus->save_related_module($for_module, $for_crmid, $with_module, $with_crmid);
			}
		}
	}

	// crmv@95157 - removed and moved code
	
	// this method is kept for compatibility only, please don't use it in new projects
	function insertIntoAttachment($id,$module) {
		$FS = FileStorage::getInstance();
		return $FS->insertIntoAttachment($id, $module, $this);
	}

	// this method is kept for compatibility only, please don't use it in new projects
	function uploadAndSaveFile($id,$module,$file_details,$copy=false) {
		$FS = FileStorage::getInstance();
		return $FS->uploadAndSaveFile($id, $module, $file_details, $copy, $this);
	}
	// crmv@95157e


	/** Function to insert values in the vte_crmentity for the specified module
  	  * @param $module -- module:: Type varchar
 	 */

  function insertIntoCrmEntity($module,$fileid='',$longdesc=false)	//crmv@16877
  {
	global $adb,$table_prefix;
	global $current_user;
	global $log;
	global $iAmAProcess;	//crmv@105685

	if($fileid != '')
	{
		$this->id = $fileid;
		$this->mode = 'edit';
	}

	$date_var = date('Y-m-d H:i:s');

	$ownerid = $this->column_fields['assigned_user_id'];
	$this->ownedby = getTabOwnedBy($module); // crmv@129138

	if($this->ownedby == 1)
	{
		$log->info("module is =".$module);
		$ownerid = $current_user->id;
	}
	// Asha - Change ownerid from '' to null since its an integer field.
	// It is empty for modules like Invoice/Quotes/SO/PO which do not have Assigned to field
	if($ownerid === '') $ownerid = 0;

	if($module == 'Events')
	{
		$module = 'Calendar';
	}
	if($this->mode == 'edit')
	{
		// crmv@150773 crmv@171832
		$sql = "update ".$table_prefix."_crmentity set modifiedby=?,modifiedtime=?";
		$params = array($current_user->id, $adb->formatDate($date_var, true));
		if (!$this->enable_partial_write || ($this->enable_partial_write && isset($this->writable_fields['assigned_user_id']))) {
			$sql .= ",smownerid=?";
			$params[] = $ownerid;
		}
		//crmv@183494
		if (!empty($this->column_fields['creator']) && (!$this->enable_partial_write || ($this->enable_partial_write && isset($this->writable_fields['creator'])))) {
			$sql .= ",smcreatorid=?";
			$params[] = $this->column_fields['creator'];
		}
		//crmv@183494e
		$sql.=" where crmid=?";
		$params[] = $this->id;
		// crmv@150773e crmv@171832e
		$adb->pquery($sql, $params);
		
		$sql1 ="delete from ".$table_prefix."_ownernotify where crmid=?";
		$params1 = array($this->id);
		$adb->pquery($sql1, $params1);
		if($ownerid != $current_user->id)
		{
			$sql1 = "insert into ".$table_prefix."_ownernotify values(?,?,?)";
			$params1 = array($this->id, $ownerid, null);
			$adb->pquery($sql1, $params1);
		}
	}
	else
	{
		//if this is the create mode and the group allocation is chosen, then do the following
		//crmv@offline
		if (vtlib_isModuleActive('Offline') !== false && $this->force_id){
			$current_id = $this->force_id;
		}
		else{
			$current_id = $adb->getUniqueID($table_prefix."_crmentity");
		}
		//crmv@offline end
		$_REQUEST['currentid']=$current_id;
		if (!isset($current_user)) $current_user = CRMEntity::getInstance('Users');
		if($current_user->id == '')
			$current_user->id = 0;
		
		// crmv@150773 crmv@183494
		$creator = $current_user->id;
		if (!empty($this->column_fields['creator'])) $creator = $this->column_fields['creator'];

		$sql = "insert into ".$table_prefix."_crmentity (crmid,smcreatorid,smownerid,setype,createdtime,modifiedtime) values(?,?,?,?,?,?)";
		$params = array($current_id, $creator, $ownerid, $module, $adb->formatDate($date_var, true), $adb->formatDate($date_var, true));
		$adb->pquery($sql, $params);
		// crmv@150773e crmv@183494e

		$this->id = $current_id;
	}
   }


	/** Function to insert values in the specifed table for the specified module
  	  * @param $table_name -- table name:: Type varchar
  	  * @param $module -- module:: Type varchar
 	 */
  function insertIntoEntityTable($table_name, $module, $fileid='')
  {
	  global $log,$table_prefix;
  	  global $current_user,$app_strings;
	   $log->info("function insertIntoEntityTable ".$module.$table_prefix.'_table name ' .$table_name);
	  global $adb;
	  global $iAmAProcess;	//crmv@105685
	  $insertion_mode = $this->mode;
	  
	  $VTEP = VTEProperties::getInstance(); //crmv@171832

	  //Checkin whether an entry is already is present in the vte_table to update
	  if($insertion_mode == 'edit')
	  {
	  	  $tablekey = $this->tab_name_index[$table_name];
	  	  // Make selection on the primary key of the module table to check.
		  $check_query = "select $tablekey from $table_name where $tablekey=?";
		  $check_result=$adb->pquery($check_query, array($this->id));

		  $num_rows = $adb->num_rows($check_result);

		  if($num_rows <= 0)
		  {
			  $insertion_mode = '';
		  }
	  }

	$tabid= getTabid($module);
  	if($module == 'Calendar' && $this->column_fields["activitytype"] != null && $this->column_fields["activitytype"] != 'Task') {
    	$tabid = getTabid('Events');
  	}
  	
  	$table_index_column = $this->tab_name_index[$table_name]; // crmv@167148
	  if($insertion_mode == 'edit')
	  {
		  $update = array();
		  $update_params = array();
		  require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		  if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] ==0 || $iAmAProcess === true)	//crmv@105685
		  {
				$sql = "select * from ".$table_prefix."_field where tabid in (". generateQuestionMarks($tabid) .") and tablename=? and displaytype in (1,3) and presence in (0,2)";
				$params = array($tabid, $table_name);
		  }
		  else
		  {
			  $profileList = getCurrentUserProfileList();

			  // crmv@37679
			  if (count($profileList) > 0) {
			  	$sql = "SELECT ".$table_prefix."_field.*, ".$table_prefix."_def_org_field.visible
			  			FROM ".$table_prefix."_field
			  			INNER JOIN ".$table_prefix."_def_org_field
			  			ON ".$table_prefix."_def_org_field.fieldid = ".$table_prefix."_field.fieldid
			  			WHERE ".$table_prefix."_field.tabid = ?
			  			AND ".$table_prefix."_def_org_field.visible = 0 and ".$table_prefix."_field.tablename=? and ".$table_prefix."_field.displaytype in (1,3) and ".$table_prefix."_field.presence in (0,2)";
 				//crmv@60969
 				$sql.=" AND EXISTS(SELECT * FROM ".$table_prefix."_profile2field 
 				inner join {$table_prefix}_profile2tab on {$table_prefix}_profile2tab.tabid = {$table_prefix}_profile2field.tabid and {$table_prefix}_profile2tab.profileid = {$table_prefix}_profile2field.profileid and {$table_prefix}_profile2tab.permissions = 0
 				WHERE ".$table_prefix."_profile2field.fieldid = ".$table_prefix."_field.fieldid AND ".$table_prefix."_profile2field.profileid IN (". generateQuestionMarks($profileList) .") AND ".$table_prefix."_profile2field.visible = 0) ";
 				//crmv@60969e

			  	$params = array($tabid,$table_name,$profileList);
			  } else {
			  	$sql = "SELECT ".$table_prefix."_field.*, ".$table_prefix."_def_org_field.visible
			  			FROM ".$table_prefix."_field
			  			INNER JOIN ".$table_prefix."_def_org_field
			  			ON ".$table_prefix."_def_org_field.fieldid = ".$table_prefix."_field.fieldid
			  			WHERE ".$table_prefix."_field.tabid = ?
			  			AND ".$table_prefix."_def_org_field.visible = 0 and ".$table_prefix."_field.tablename=? and ".$table_prefix."_field.displaytype in (1,3) and ".$table_prefix."_field.presence in (0,2)";
 				$sql.=" AND EXISTS(SELECT * FROM ".$table_prefix."_profile2field WHERE ".$table_prefix."_profile2field.fieldid = ".$table_prefix."_field.fieldid AND ".$table_prefix."_profile2field.visible = 0) ";
				$params = array($tabid, $table_name);
			  }
			  // crmv@37679e

		  }

	  }
	  else
	  {
		  if($table_index_column == 'id' && $table_name == $table_prefix.'_users')
		  {
		 	$currentuser_id = $adb->getUniqueID($table_prefix."_users");
			$this->id = $currentuser_id;
		  }
		  if (empty($this->id)) $this->id = $adb->getUniqueID($table_prefix."_crmentity"); //crmv@171021
		  $column = array($table_index_column);
		  $value = array($this->id);
		  $sql = "select * from ".$table_prefix."_field where tabid=? and tablename=? and displaytype in (1,3,4) and ".$table_prefix."_field.presence in (0,2)";
		  $params = array($tabid, $table_name);
	  }

	  $result = $adb->pquery($sql, $params);
	  $noofrows = $adb->num_rows($result);
	  for($i=0; $i<$noofrows; $i++) {
		$fieldname=$adb->query_result($result,$i,"fieldname");
		$columname=$adb->query_result($result,$i,"columnname");
		$tablename=$adb->query_result($result,$i,"tablename");
		$uitype=$adb->query_result($result,$i,"uitype");
		$readonly=$adb->query_result($result,$i,"readonly"); // crmv@37679
		$generatedtype=$adb->query_result($result,$i,"generatedtype");
		$typeofdata=$adb->query_result($result,$i,"typeofdata");
		$typeofdata_array = explode("~",$typeofdata);
		$datatype = $typeofdata_array[0];
		$sdk_skipfield = false; // crmv@37679
		
		//crmv@171832
		$force_writable_uitypes = $VTEP->getProperty('performance.editview_changelog_force_writable_uitypes');
		if ($this->enable_partial_write && !isset($this->writable_fields[$fieldname]) && !in_array($uitype,$force_writable_uitypes)) continue;
		//crmv@171832e

		if($uitype == 4 && $insertion_mode != 'edit' && $_REQUEST['convert_from'] != 'reviewquote') { // crmv@109886
			//crmv@151614
			if (isset($this->column_fields['bu_mc'])) {
				$this->column_fields[$fieldname] = $this->setModuleSeqNumber("increment",$module,'','',$this->column_fields['bu_mc']);
			} else {
				$this->column_fields[$fieldname] = $this->setModuleSeqNumber("increment",$module);
			}
			//crmv@151614e
			$fldvalue = $this->column_fields[$fieldname];
		}
		if(isset($this->column_fields[$fieldname]))
		{
			//crmv@130458
		  	if (!$this->formatFieldsForSave) {
		  		$fldvalue = $this->column_fields[$fieldname];
		  	} else {
		  	//crmv@130458e
		  
				//crmv@sdk-18509	//crmv@25963
				if(SDK::isUitype($uitype))
				{
					$fldvalue = $this->column_fields[$fieldname];
					$sdk_file = SDK::getUitypeFile('php','insert',$uitype);
					if ($sdk_file != '') {
						include($sdk_file);
						if ($sdk_skipfield) continue; // crmv@37679
					}
				}
				//crmv@sdk-18509 e	//crmv@25963e
				elseif($uitype == 56)
				{
					if($this->column_fields[$fieldname] == 'on' || $this->column_fields[$fieldname] == 1)
					{
						$fldvalue = '1';
					}
					else
					{
						$fldvalue = '0';
					}

				}
				elseif($uitype == 15 || $uitype == 16)
				{
					if($this->column_fields[$fieldname] == $app_strings['LBL_NOT_ACCESSIBLE'])
					{
						//If the value in the request is Not Accessible for a picklist, the existing value will be replaced instead of Not Accessible value.
						$sql="select $columname from  $table_name where ".$this->tab_name_index[$table_name]."=?";
						$res = $adb->pquery($sql,array($this->id));
						$pick_val = $adb->query_result($res,0,$columname);
						$fldvalue = $pick_val;
					}
					else
					{
						$fldvalue = $this->column_fields[$fieldname];
					}
				}
				elseif($uitype == 33)
				{
					if(is_array($this->column_fields[$fieldname]))
					{
					$field_list = implode(' |##| ',$this->column_fields[$fieldname]);
					}else
					{
					$field_list = $this->column_fields[$fieldname];
					}
					$fldvalue = $field_list;
				}
				elseif($uitype == 5 || $uitype == 6 || $uitype ==23)
				{
					if($_REQUEST['action'] == 'Import')
					{
						$fldvalue = $this->column_fields[$fieldname];
					}
					else
					{
						//Added to avoid function call getDBInsertDateValue in ajax save
						if (isset($current_user->date_format)) {
								$fldvalue = getValidDBInsertDateValue($this->column_fields[$fieldname]);
						} else {
								$fldvalue = $this->column_fields[$fieldname];
						}
						// crmv@25610 crmv@50039
						if (($module == 'Calendar' || $module == 'Events') && $fieldname == 'date_start') { // crmv@190264
							$dtstart = $this->column_fields['date_start'];
							if (isset($current_user->date_format)) {
								$dtstart = getValidDBInsertDateValue($dtstart);
							}
							$newval = $dtstart.' '.$this->column_fields['time_start'];
							$newval = adjustTimezone($newval, 0, $this->userTimezone, true); // crmv@163361
							$fldvalue = substr($newval, 0, 10);
						} elseif (($module == 'Calendar' || $module == 'Events') &&  $fieldname == 'due_date') { // crmv@190264
							$dtend = $this->column_fields['due_date'];
							if (isset($current_user->date_format)) {
								$dtend = getValidDBInsertDateValue($dtend);
							}
							$newval = $dtend.' '.$this->column_fields['time_end'];
							$newval = adjustTimezone($newval, 0, $this->userTimezone, true); // crmv@163361
							$fldvalue = substr($newval, 0, 10);
						} else {
							$fldvalue = adjustTimezone($fldvalue, 0, $this->userTimezone, true); // crmv@163361
						}
						// crmv@25610e crmv@50039e
					}
				}
				// crmv@25610
				elseif($uitype == 70)
				{
					$fldvalue = adjustTimezone($this->column_fields[$fieldname], 0, $this->userTimezone, true); // crmv@163361
				}
				// crmv@25610e
				elseif($uitype == 7)
				{
					//strip out the spaces and commas in numbers if given ie., in amounts there may be ,
					$fldvalue = str_replace(",","",$this->column_fields[$fieldname]);//trim($this->column_fields[$fieldname],",");

				}
					elseif($uitype == 26) {
						if(empty($this->column_fields[$fieldname])) {
							$fldvalue = 1; //the documents will stored in default folder
						}else {
							$fldvalue = $this->column_fields[$fieldname];
						}
				}
				elseif($uitype == 28){
						if($this->column_fields[$fieldname] == null){
							$fileQuery = $adb->pquery("SELECT filename from ".$table_prefix."_notes WHERE notesid = ?",array($this->id));
							$fldvalue = null;
							if(isset($fileQuery)){
								$rowCount = $adb->num_rows($fileQuery);
								if($rowCount > 0){
									$fldvalue = $adb->query_result($fileQuery,0,'filename');
								}
							}
						}else {
							$fldvalue = $this->column_fields[$fieldname];
						}
				}elseif($uitype == 8) {
					$this->column_fields[$fieldname] = rtrim($this->column_fields[$fieldname],',');
					$ids = explode(',',$this->column_fields[$fieldname]);
					$json = new Zend_Json();
					$fldvalue = $json->encode($ids);
				}elseif($uitype == 12) {
					//crmv@22700
					if ($fieldname == 'from_email' && $this->column_fields[$fieldname] != '') {
						$fldvalue = $this->column_fields[$fieldname];
					} else {
					//crmv@22700
						$query = "SELECT email1 FROM ".$table_prefix."_users WHERE id = ?";
						$res = $adb->pquery($query,array($current_user->id));
						$rows = $adb->num_rows($res);
						if($rows > 0) {
							$fldvalue = $adb->query_result($res,0,'email1');
						}
					}	//crmv@22700
				}elseif($uitype == 71 && $generatedtype == 2) { // Convert currency to base currency value before saving for custom fields of type currency
					$currency_id = $current_user->currency_id;
					$curSymCrate = getCurrencySymbolandCRate($currency_id);
					$fldvalue = convertToDollar($this->column_fields[$fieldname], $curSymCrate['rate']);
				//crmv@16265	crmv@43764
				} elseif($uitype == 199) {
					if (!empty($this->id) && $insertion_mode == 'edit' && $this->column_fields[$fieldname] == '') {
						$fldvalue = getSingleFieldValue($tablename, $fieldname, $this->tab_name_index[$tablename], $this->id);
					} else {
						$fldvalue = Users::changepassword($this->column_fields[$fieldname]);
					}
				//crmv@16265e	crmv@43764e
				} else {
					$fldvalue = $this->column_fields[$fieldname];
				}

				//crmv@50039 crmv@53679
				if (in_array($module, array('Calendar', 'Events')) && $fieldname == 'time_start' && !empty($fldvalue)) {
					$dtstart = $this->column_fields['date_start'];
					if (isset($current_user->date_format)) {
						$dtstart = getValidDBInsertDateValue($dtstart);
					}
					$newval = $dtstart.' '.$fldvalue;
					$newval = adjustTimezone($newval, 0, $this->userTimezone, true); // crmv@163361
					if (strlen($newval) > 5) $fldvalue = substr($newval, strpos($newval,' ')+1, 5);
				} elseif (in_array($module, array('Calendar', 'Events')) &&  $fieldname == 'time_end' && !empty($fldvalue)) {
					$dtend = $this->column_fields['due_date'];
					if (isset($current_user->date_format)) {
						$dtend = getValidDBInsertDateValue($dtend);
					}
					$newval = $dtend.' '.$fldvalue;
					$newval = adjustTimezone($newval, 0, $this->userTimezone, true); // crmv@163361
					if (strlen($newval) > 5) $fldvalue = substr($newval, strpos($newval,' ')+1, 5);
				}
				//crmv@50039e crmv@53679e

				//crmv@41883 crmv@80298
				if($uitype == 13 || $uitype == 104) {
					$fldvalue = trim($fldvalue);
					$this->hasAddressFields = true;
					if (!empty($fldvalue)) $this->emailAddresses[] = $fldvalue;
				}
				//crmv@41883e crmv@80298e

				if($uitype != 33 && $uitype !=8)
					$fldvalue = from_html($fldvalue,($insertion_mode == 'edit')?true:false);
					
			} //crmv@130458
		}
		else
		{
			$fldvalue = '';
		}
		
		if($fldvalue == '') {
		  	$fldvalue = $this->get_column_value($columname, $fldvalue, $fieldname, $uitype, $datatype);
		}
		
		// crmv@150773
		// save description later
		if ($columname == 'description' && $this->saveWithLongDesc) {
			if (!$this->enable_partial_write || ($this->enable_partial_write && isset($this->writable_fields['description']))) $saveDescription = true; //crmv@171832
			$description_val = from_html($this->column_fields['description'],($insertion_mode == 'edit')?true:false);
			continue;
		}
		// crmv@150773e

		if($insertion_mode == 'edit') {
			if($table_name != $table_prefix.'_ticketcomments' && $uitype != 4) {
				array_push($update, $columname."=?");
				array_push($update_params, $fldvalue);
			}
		} else {
			array_push($column, $columname);
			array_push($value, $fldvalue);
		}

	  }
	  
	//crmv@171021
  	$result2 = $adb->pquery("select columnname, fieldname from {$table_prefix}_field where tabid=? and tablename=? and fieldname in (?,?)", array($tabid, $table_name, 'createdtime', 'modifiedtime'));
  	if ($result2 && $adb->num_rows($result2) > 0) {
  		$date_var = date('Y-m-d H:i:s');
  		while($row=$adb->fetchByAssoc($result2)) {
  			$columname = $row['columnname'];
  			if ($row['fieldname'] == 'createdtime') {
  				if($insertion_mode == 'edit') {}
  				else {
  					array_push($column, $columname);
  					array_push($value, $adb->formatDate($date_var, true));
  				}
  			} elseif ($row['fieldname'] == 'modifiedtime') {
  				if($insertion_mode == 'edit') {
  					array_push($update, $columname."=?");
  					array_push($update_params, $adb->formatDate($date_var, true));
  				} else {
  					array_push($column, $columname);
  					array_push($value, $adb->formatDate($date_var, true));
  				}
  			}
  		}
  	}
	//crmv@171021e

	  if($insertion_mode == 'edit')
	  {
		  //Check done by Don. If update is empty the the query fails
		  if(count($update) > 0) {
		  	//crmv@fix column
		  	foreach ($update as $key=>$upd){
		  		$vals = explode('=',$upd);
		  		$adb->format_columns($vals[0]);
		  		$update[$key] = $vals[0].'='.$vals[1];
		  	}
		  	//crmv@fix column end
		  	//crmv@26938 crmv@32409 - fix for fields exceeding maximum length
		  	if ($adb->isOracle()) {
		  		// get fields types and length
		  		$col_defs = array();
		  		$rr = $adb->pquery("select column_name,data_type, data_length from user_tab_columns where table_name = ?", array(strtoupper($table_name)));
		  		if ($rr && $adb->num_rows($rr) > 0)  {
		  			while ($row = $adb->FetchByAssoc($rr, -1, false)) {
		  				$col_defs[$row['column_name']] = array('data_type'=>$row['data_type'], 'data_length'=>$row['data_length']);
		  			}
		  		}
		  		if (count($col_defs) > 0) {
		  			$count_par = count($update);		//crmv@56837
       				for ($i=0; $i<$count_par; ++$i) {	//crmv@56837
		  				$v = explode('=',$update[$i]);
		  				$column_key = strtoupper($v[0]);

		  				$coltype = $col_defs[$column_key]['data_type'];
		  				$colsize = $col_defs[$column_key]['data_length'];
		  				if ($coltype == 'VARCHAR2' && is_string($update_params[$i]) && $colsize > 0 && strlen($update_params[$i]) > 0) {
		  					$update_params[$i] = substr($update_params[$i], 0, $colsize);
		  				} elseif ($coltype == 'CLOB') {
		  					// aggiorno
		  					$adb->updateClob($table_name,str_replace('=?','', $update[$i]), $this->tab_name_index[$table_name].'='.$this->id, $update_params[$i]);
		  					// rimuovo da lista
		  					unset($update_params[$i]);
		  					unset($update[$i]);
		  				}
		  			}
		  		}

		  	}
		  	//crmv@26938e	//crmv@32409e
		  	$sql1 = "update $table_name set ". implode(",",$update) ." where ". $this->tab_name_index[$table_name] ."=?";
			array_push($update_params, $this->id);
			$res = $adb->pquery($sql1, $update_params);
		  }
	  }
	  else
	  {
	  	  //crmv@fix column
	  	  $adb->format_columns($column);
	  	  //crmv@fix column end
	  	  //crmv@26938	//crmv@32409
	  	  if ($adb->isOracle()) {
	  	  	// get fields types and length
	  	  	$col_defs = array();
	  	  	$rr = $adb->pquery("select column_name,data_type, data_length from user_tab_columns where table_name = ?", array(strtoupper($table_name)));
	  	  	if ($rr && $adb->num_rows($rr) > 0)  {
	  	  		while ($row = $adb->FetchByAssoc($rr, -1, false)) {
	  	  			$col_defs[$row['column_name']] = array('data_type'=>$row['data_type'], 'data_length'=>$row['data_length']);
	  	  		}
	  	  	}
	  	  	if (count($col_defs) > 0) {
	  	  		for ($i=0; $i<count($column); ++$i) {
	  	  			$column_key = strtoupper($column[$i]);
	  	  			$coltype = $col_defs[$column_key]['data_type'];
	  	  			$colsize = $col_defs[$column_key]['data_length'];
	  	  			if ($coltype == 'VARCHAR2' && is_string($value[$i]) && $colsize > 0 && strlen($value[$i]) > 0) {
	  	  				$value[$i] = substr($value[$i], 0, $colsize);
	  	  			}
	  	  		}
	  	  	}
	  	  }
	  	  //crmv@26938e	//crmv@32409e
	  	  $sql1 = "insert into $table_name(". implode(",",$column) .") values(". generateQuestionMarks($value) .")";
		  $adb->pquery($sql1, $value);
	  }
	  
	  // crmv@150773
	  if ($saveDescription) {
		$adb->updateClob($table_name,'description',$this->tab_name_index[$table_name]."=".$this->id,$description_val);
	  }
	  // crmv@150773e
	  
	  //crmv@sdk-18509
	  for($i=0; $i<$noofrows; $i++) {
		$fieldname=$adb->query_result($result,$i,"fieldname");
		$uitype=$adb->query_result($result,$i,"uitype");
		$columname=$adb->query_result($result,$i,"columnname"); // crmv@171890
		if(SDK::isUitype($uitype))
		{
			$fldvalue = $this->column_fields[$fieldname];
			$sdk_file = SDK::getUitypeFile('php','insert.after',$uitype);
			if ($sdk_file != '') {
				include($sdk_file);
			}
		}
	  }
	  //crmv@sdk-18509e
  }

  /** Function to delete a record in the specifed table
   * @param $table_name -- table name:: Type varchar
   * The function will delete a record .The id is obtained from the class variable $this->id and the columnname got from $this->tab_name_index[$table_name]
 	 */
  function deleteRelation($table_name)
  {
  	global $adb,$table_prefix;
  	$check_query = "select * from $table_name where ". $this->tab_name_index[$table_name] ."=?";
  	$check_result=$adb->pquery($check_query, array($this->id));
  	$num_rows = $adb->num_rows($check_result);

  	if($num_rows == 1)
  	{
  		$del_query = "DELETE from $table_name where ". $this->tab_name_index[$table_name] ."=?";
  		$adb->pquery($del_query, array($this->id));
  	}

  }

	// crmv@95157 - removed function

	// Code included by Jaguar - Ends

	/** Function to retrive the information of the given recordid ,module
  	  * @param $record -- Id:: Type Integer
  	  * @param $module -- module:: Type varchar
	  * This function retrives the information from the database and sets the value in the class columnfields array
 	 */
	//crmv@25872 crmv@113417
	function retrieve_entity_info($record, $module, $dieOnError=true, $onlyFields = array()) {
		global $adb, $table_prefix;

		$r = $this->checkRetrieve($record, $module, $dieOnError);
		if ($r !== null) return $r;
		
		// get the sql list of columns or null if everything should be extracted
		$sqlList = $this->getRetrieveSelect($record, $module, $onlyFields);
		
		// get the data from the DB
		$result = $this->getRetrieveResult($record, $module, $sqlList);

		// process the raw data and save it in the column_fields
		$this->processRetrieveResult($record, $module, $result, true, $onlyFields);
		
		$this->partialData = (count($onlyFields) > 0);

		// set the final values
		$this->column_fields["record_id"] = $record;
		$this->column_fields["record_module"] = $module;
		$this->id = $record;
		
		//crmv@181281
		$focusNewsletter = CRMEntity::getInstance('Newsletter');
		if (array_key_exists($module,$focusNewsletter->email_fields)) {
			$email = $this->column_fields[$focusNewsletter->email_fields[$module]['fieldname']];
			$this->column_fields['newsletter_unsubscrpt'] = intval($focusNewsletter->receivingNewsletter($email));
		}
		//crmv@181281e
	}

	/** Function to retrive the information of the given recordid ,module
  	  * @param $record -- Id:: Type Integer
  	  * @param $module -- module:: Type varchar
	  * This function retrives the information from the database and sets the value in the class columnfields array
 	 */
	function retrieve_entity_info_no_html($record, $module, $dieOnError=true, $onlyFields = array()) {
		global $adb, $table_prefix;

		$r = $this->checkRetrieve($record, $module, $dieOnError);
		if ($r !== null) return $r;
		
		// get the sql list of columns or null if everything should be extracted
		$sqlList = $this->getRetrieveSelect($record, $module, $onlyFields);
		
		// get the data from the DB
		$result = $this->getRetrieveResult($record, $module, $sqlList);

		// process the raw data and save it in the column_fields
		$this->processRetrieveResult($record, $module, $result, false, $onlyFields);
		
		// set this flag to prevent the save!
		$this->partialData = (count($onlyFields) > 0);

		// set the final values
		$this->column_fields["record_id"] = $record;
		$this->column_fields["record_module"] = $module;
		$this->id = $record;
		
		//crmv@181281
		$focusNewsletter = CRMEntity::getInstance('Newsletter');
		if (array_key_exists($module,$focusNewsletter->email_fields)) {
			$email = $this->column_fields[$focusNewsletter->email_fields[$module]['fieldname']];
			$this->column_fields['newsletter_unsubscrpt'] = intval($focusNewsletter->receivingNewsletter($email));
		}
		//crmv@181281e
	}
	
	/**
	 * Check if the record exists and is valid before retrieving all the fields
	 */
	public function checkRetrieve($record, $module, $dieOnError = true) {
		global $adb, $table_prefix;
		global $app_strings;
		
		// check if deleted
		$crmTable = $table_prefix.'_crmentity';
		if (array_key_exists($crmTable, $this->tab_name_index)) {
			$index = $this->tab_name_index[$crmTable];
			$res = $adb->pquery("SELECT deleted FROM $crmTable WHERE $index = ?", array($record));
			if ($res && $adb->query_result_no_html($res,0,"deleted") == 1) {
				if ($dieOnError) {
					echo "<div id='debug_backtrace' style='display:none'>\n";debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);echo '</div>';
					die("<br><br><center>".$app_strings['LBL_RECORD_DELETE']." <a href='javascript:window.history.back()' error='RECORDDELETED_{$record}_{$module}'>".$app_strings['LBL_GO_BACK'].".</a></center>");
				} else {
					return 'LBL_RECORD_DELETE';
				}
			}
		}

		// check if lead converted
		$leadTable = $table_prefix.'_leaddetails';
		if ($module == 'Leads' && array_key_exists($leadTable, $this->tab_name_index)) {
			$index = $this->tab_name_index[$leadTable];
			$res = $adb->pquery("SELECT converted FROM $leadTable WHERE $index = ?", array($record));
			if ($res && $adb->query_result_no_html($res,0,"converted") == 1) {
				if ($dieOnError) {
					echo "<div id='debug_backtrace' style='display:none'>\n";debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);echo '</div>';
					die("<br><br><center>".$app_strings['LBL_RECORD_DELETE']." <a href='javascript:window.history.back()' error='LEADCONVERTED_{$record}'>".$app_strings['LBL_GO_BACK'].".</a></center>");
				} else {
					return 'LBL_RECORD_DELETE';
				}
			}
		}

		// check if row exists in main table
		if (isset($this->table_name) && array_key_exists($this->table_name, $this->tab_name_index)) {
			$mod_index_col = $this->tab_name_index[$this->table_name];
			$res = $adb->pquery("SELECT $mod_index_col FROM {$this->table_name} WHERE $mod_index_col = ?", array($record));
			if($res && ($adb->num_rows($res) == 0 || $adb->query_result_no_html($res,0,$mod_index_col) == '')) {
				if ($dieOnError) {
					echo "<div id='debug_backtrace' style='display:none'>\n";debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);echo '</div>';
					die("<br><br><center>".$app_strings['LBL_RECORD_NOT_FOUND'].". <a href='javascript:window.history.back()' error='RECORDNOTFOUND_{$record}_{$module}'>".$app_strings['LBL_GO_BACK'].".</a></center>");
				} else {
					return 'LBL_RECORD_NOT_FOUND';
				}
			}
		}
		
		return null;
	}
	
	/**
	 * Prepare the content of the select for the retrieve_entity_info functions
	 */
	protected function getRetrieveSelect($record, $module, $onlyFields = array()) {
		global $adb, $table_prefix;
		
		$sqlList = null;
		// Lookup in cache for information when a list of fields is specified 
		if (count($onlyFields) > 0) {
			$cachedModuleFields = $this->getFieldDefinition($module);
			// prepare the list of fields to extract
			$sqlList = array();
			foreach ($onlyFields as $showField) {
				foreach ($cachedModuleFields as $fieldinfo) {
					$fieldname = $fieldinfo['fieldname'];
					if ($showField == $fieldname) {
						$tablename = $fieldinfo['tablename'];
						$sqlList[$tablename][] = $fieldinfo['columnname'];
						break;
					}
				}
			}
			foreach ($sqlList as $table=>&$list) {
				$adb->format_columns($list);
				$list = implode(', ', $list);
			}
		}
		
		return $sqlList;
	}
	
	protected function getRetrieveResult($record, $module, $sqlList = null) {
		global $adb, $table_prefix;
		$result = array();
		foreach($this->tab_name_index as $table_name=>$index) {
			//crmv@55682
			$orderby = (($table_name == $table_prefix.'_ticketcomments') ? ' ORDER BY commentid DESC' : '');
			if (is_null($sqlList)) {
				// no list of fields, extract everything
				$result[$table_name] = $adb->pquery("SELECT * FROM $table_name WHERE $index = ?".$orderby, array($record));
			} elseif ($sqlList[$table_name]) {
				// extract only passed fields
				$result[$table_name] = $adb->pquery("SELECT {$sqlList[$table_name]} FROM $table_name WHERE $index = ?".$orderby, array($record));
			}
			//crmv@55682e
		}
		
		return $result;
	}
	
	protected function getFieldDefinition($module) {
		global $adb, $table_prefix;
		
		$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module);
		if($cachedModuleFields === false) {
			$tabid = getTabid($module);

			// Let us pick up all the fields first so that we can cache information
			$sql1 =  "SELECT fieldname, fieldid, fieldlabel, columnname, tablename, uitype, typeofdata, presence
			FROM ".$table_prefix."_field WHERE tabid = ?";

			// NOTE: Need to skip in-active fields which we will be done later.
			$result1 = $adb->pquery($sql1, array($tabid));

			if ($adb->num_rows($result1) > 0) {
				while($resultrow = $adb->fetch_array_no_html($result1)) {
					// Update information to cache for re-use
						VTCacheUtils::updateFieldInfo(
							$tabid, $resultrow['fieldname'], $resultrow['fieldid'],
							$resultrow['fieldlabel'], $resultrow['columnname'], $resultrow['tablename'],
							$resultrow['uitype'], $resultrow['typeofdata'], $resultrow['presence']
						);
				}
			}

			// Get only active field information
			$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module);
		}
		
		return $cachedModuleFields;
	}
	
	protected function processRetrieveResult($record, $module, $result, $html = true, $onlyFields = array()) {
		global $adb, $table_prefix, $current_user;

		$cachedModuleFields = $this->getFieldDefinition($module);
		if($cachedModuleFields) {
			foreach($cachedModuleFields as $fieldname=>$fieldinfo) {
				$fieldcolname = $fieldinfo['columnname'];
				$tablename    = $fieldinfo['tablename'];
				$fieldname    = $fieldinfo['fieldname'];
				
				if (count($onlyFields) > 0 && !in_array($fieldname, $onlyFields)) continue;

				// To avoid ADODB execption pick the entries that are in $tablename
				// (ex. when we don't have attachment for troubletickets, $result[vte_attachments]
				// will not be set so here we should not retrieve)
				if(isset($result[$tablename])) {
					if ($html) {
						$fld_value = $adb->query_result($result[$tablename],0,$fieldcolname);
					} else {
						$fld_value = $adb->query_result_no_html($result[$tablename],0,$fieldcolname);
					}
				} else {
					$adb->println("There is no entry for this entity $record ($module) in the table $tablename");
					$fld_value = "";
				}
				//crmv@16265
				if ($fieldinfo['uitype'] == 199)
					$this->column_fields[$fieldname] = Users::de_cryption($fld_value);
				// crmv@25610 crmv@50039
				elseif (in_array($fieldinfo['uitype'], array(5,6,23,70))) {
					if ($current_user) {
						if (in_array($fieldinfo['uitype'], array(5,6,23))) {
							$fld_value = substr($fld_value, 0, 10);
						}
						$fld_value = adjustTimezone($fld_value, 0, $this->userTimezone, false); // crmv@163361
					}
					$this->column_fields[$fieldname] = $fld_value;
				}
				// crmv@25610e crmv@50039e
				else {
				//crmv@16265e
					$this->column_fields[$fieldname] = $fld_value;
				}
				
				// for the Users module, set also the parameters of the class (ignoring the html var)
				if($module == 'Users') {
					$this->$fieldname = $fld_value;
				}
			}
			// crmv@25610 crmv@50039
			// correggo le date per quel maledetto calendario
			if (in_array($module, array('Calendar','Events'))) {
				if (!empty($this->column_fields['date_start'])) {
					$newval = substr($this->column_fields['date_start'], 0, 10).' '.$this->column_fields['time_start'];
					$newval = adjustTimezone($newval, 0, $this->userTimezone, false); // crmv@163361
					$this->column_fields['date_start'] = substr($newval, 0, 10);
				}
				if (!empty($this->column_fields['due_date'])) {
					$newval = substr($this->column_fields['due_date'], 0, 10).' '.$this->column_fields['time_end'];
					$newval = adjustTimezone($newval, 0, $this->userTimezone, false); // crmv@163361
					$this->column_fields['due_date'] = substr($newval, 0, 10);
				}
				if (!empty($this->column_fields['time_start'])) {
					$newval = $this->column_fields['date_start'].' '.$this->column_fields['time_start'];	//crmv@57976
					$newval = adjustTimezone($newval, 0, $this->userTimezone, false); // crmv@163361
					$this->column_fields['time_start'] = substr($newval, 11, 5);
				}
				if (!empty($this->column_fields['time_end'])) {
					$newval = $this->column_fields['due_date'].' '.$this->column_fields['time_end'];	//crmv@57976
					$newval = adjustTimezone($newval, 0, $this->userTimezone, false); // crmv@163361
					$this->column_fields['time_end'] = substr($newval, 11, 5);
				}
			}
			// crmv@25610e crmv@50039e
		}
	}
	//crmv@25872e crmv@113417e

	/** Function to saves the values in all the tables mentioned in the class variable $tab_name for the specified module
  	  * @param $module -- module:: Type varchar
 	 */
	 //ds@28 workflow
	 //crmv@8716	//crmv@27096
	 //crmv@offline
	function save($module_name,$longdesc=false,$offline_update=false,$triggerEvent=true)
	{
		global $log,$adb;
        $log->debug("module name is ".$module_name);

		// crmv@113417
        // check if partialdata was read
        if ($this->partialData) {
			throw new Exception("Saving the record is no allowed because the retrieve was partial.");
        }
        // crmv@113417e
        
        //crmv@171832
        $VTEP = VTEProperties::getInstance();
        if ($VTEP->getProperty('performance.editview_changelog') == 1 && $this->mode == 'edit' && !in_array($module_name,EditViewChangeLog::$skip_modules) && isset($this->editview_etag) && !empty($this->editview_presavedata)){
        	$this->column_fields_presave = $this->column_fields;
        }
        //crmv@171832e
        
        // crmv@171524
        $triggerQueueManager = TriggerQueueManager::getInstance();
        if ($triggerEvent && $triggerQueueManager->isEnabled($module_name,$this->id) && !$triggerQueueManager->isConsumerActive()) { // crmv@199641
        	if (!empty($this->id)) {
        		$old_obj = CRMEntity::getInstance($module_name);
        		$old_obj->retrieve_entity_info_no_html($this->id,$module_name);
        		$old_column_fields = $old_obj->column_fields;
        	} else {
        		$old_column_fields = array();
        	}
        }
        // crmv@171524e
        
        $this->presaveAlterValues(); // crmv@83877
        
		if ($triggerEvent) {
			//Event triggering code
			require_once("include/events/include.inc");
			//crmv@8716
			if ($offline_update){
				$em = new VTEventTrigger_offline($adb);
			}
			else{
				$em = new VTEventsManager($adb);
			}
			// Initialize Event trigger cache
			$em->initTriggerCache();

			$entityData  = VTEntityData::fromCRMEntity($this);
			$em->triggerEvent("history_first", $entityData);
			$em->triggerEvent("vte.entity.beforesave.modifiable", $entityData);//crmv@207852
			$em->triggerEvent("vte.entity.beforesave", $entityData);//crmv@207852
			$em->triggerEvent("vte.entity.beforesave.final", $entityData);//crmv@207852
			//Event triggering code ends
		}
		//GS Save entity being called with the modulename as parameter
		$this->saveentity($module_name,'',$longdesc);
		//crmv@OPER10174
		require_once('modules/com_workflow/VTEntityCache.inc');//crmv@207901
		VTEntityCache::setResetCache(vtws_getWebserviceEntityId($module_name,$this->id));
		//crmv@OPER10174e
		if ($triggerEvent) {
			//Event triggering code
			// crmv@171524
			if ($triggerQueueManager->isEnabled($module_name,$this->id) && !$triggerQueueManager->isConsumerActive()) { // crmv@199641
				$new_column_fields = $this->column_fields;
				$enqueued = $triggerQueueManager->enqueue($this, 'processhandler', array(
					'old_column_fields'=>$old_column_fields,
					'new_column_fields'=>$new_column_fields,
				), true);
				if (!empty($_FILES) || !$enqueued) {
					// simulate the consumer
					$triggerQueueManager->activateConsumer(); // crmv@199641
				}
				if ($enqueued) {
					require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
					$PMUtils = ProcessMakerUtils::getInstance();
					
					$rabbitmqConnection = $triggerQueueManager->getConnectionParams('rabbitmq');
					$triggerQueueManager->sendMessageToTopic($rabbitmqConnection['freeze_topic_name'], Zend_Json::encode(array('record' => $this->id)));
					
					$brothers = $PMUtils->getRecordsBrothers($this->id, 'running');
					foreach ($brothers as $bid) {
						$triggerQueueManager->sendMessageToTopic($rabbitmqConnection['freeze_topic_name'], Zend_Json::encode(array('record' => $bid)));
					}
				}
			}
			// crmv@171524e
			//crmv@18338
			$em->triggerEvent("vte.entity.aftersave.first", $entityData);//crmv@207852
			$em->triggerEvent("vte.entity.aftersave", $entityData);//crmv@207852
			$em->triggerEvent("vte.entity.aftersave.last", $entityData);//crmv@207852
			//crmv@18338 end
			$em->triggerEvent("history_last", $entityData);
			$em->triggerEvent("vte.entity.aftersave.notifications", $entityData); // crmv@198950 crmv@207852
			$em->triggerEvent("vte.entity.aftersave.processes", $entityData); // crmv@177677 crmv@207852
			//Event triggering code ends
		}
	}
	//crmv@8716e	//crmv@27096e
	//ds@28e
	
	// crmv@83877
	// alter the values in $this->columns_fields before the save and only in some cases
	function presaveAlterValues() {
		global $adb, $table_prefix;

		// crmv@95751
		$module = $this->modulename;
		if ($module == 'Events' || $module == 'Activity') $module = 'Calendar';
		
		// alter the number according to the user format, only if coming from AjaxSave or regular web-interface Save
		$webSave = ($_REQUEST['module'] == $module && ($_REQUEST['action'] == 'Save' || $_REQUEST['action'] == 'TodoSave'));
		$ajaxSave = ($_REQUEST['module'] == $module && $_REQUEST['action'] == $module.'Ajax' && $_REQUEST['file'] == 'DetailViewAjax' && $_REQUEST['ajxaction'] == 'DETAILVIEW');
		$massEditSave = ($_REQUEST['module'] == $module && $_REQUEST['action'] == 'MassEditSave');
		// crmv@95751e

		if ($webSave || $ajaxSave || $massEditSave) {
			// uitypes to be checked and altered
			$checkUitypes = array(7,9,71,72); // crmv@92112
			
			// get correct tabid (also for that stupid calendar module)
			$tabid = getTabid($module);
			if ($module == 'Calendar' && $this->column_fields["activitytype"] != null && $this->column_fields["activitytype"] != 'Task') {
				$tabid = getTabid('Events');
			}
			
			// prepare the query
			$sql = "select fieldid, fieldname, uitype from {$table_prefix}_field where tabid = ? and presence in (0,2) AND uitype in (".generateQuestionMarks($checkUitypes).")";
			$params = array($tabid, $checkUitypes);
			
			// for ajax or massedit save, check only the changed fields
			$changedFields = array();
			if ($ajaxSave) {
				$fieldname = $_REQUEST["fldName"];
				if (!empty($fieldname)) {
					$changedFields[] = $fieldname;
				}
			} elseif ($massEditSave) {
				// get the changed fields
				foreach($this->column_fields as $fieldname => $val) {
					if(isset($_REQUEST[$fieldname."_mass_edit_check"]) && isset($_REQUEST[$fieldname])) {
						$changedFields[] = $fieldname;
					}
				}
			}
			if ($ajaxSave || $massEditSave) {
				if (count($changedFields) > 0) {
					$sql .= ' AND fieldname IN ('.generateQuestionMarks($changedFields).')';
					$params[] = $changedFields;
				} else {
					// no fields changed, no transformation done
					return;
				}
			}
			
			// get the fields
			$res = $adb->pquery($sql, $params);

			// alter the values
			if ($res && $adb->num_rows($res) > 0) {
				while ($row = $adb->FetchByAssoc($res, -1, false)) {
					$uitype = $row['uitype'];
					$fname = $row['fieldname'];
					if (isset($this->column_fields[$fname]) && $this->column_fields[$fname] != '') {
						if ($uitype == 71 || $uitype == 72 || $uitype == 7 || $uitype == 9) { // crmv@92112
							$this->column_fields[$fname] = parseUserNumber($this->column_fields[$fname]);
						}
					}
				}
			}
		}
		
	}
	// crmv@83877e
	
	//crmv@offline
	function process_list_query($query, $row_offset, $limit= -1, $max_per_page = -1)
	{
		global $list_max_entries_per_page;
		$this->log->debug("process_list_query: ".$query);
		if(!empty($limit) && $limit != -1){
			$result =& $this->db->limitQuery($query, $row_offset + 0, $limit,true,"Error retrieving $this->object_name list: ");
		}else{
			$result =& $this->db->query($query,true,"Error retrieving $this->object_name list: ");
		}

		$list = Array();
		if($max_per_page == -1){
			$max_per_page 	= $list_max_entries_per_page;
		}
		$rows_found =  $this->db->getRowCount($result);

		$this->log->debug("Found $rows_found ".$this->object_name."s");

		$previous_offset = $row_offset - $max_per_page;
		$next_offset = $row_offset + $max_per_page;

		if($rows_found != 0)
		{

			// We have some data.

			for($index = $row_offset , $row = $this->db->fetchByAssoc($result, $index); $row && ($index < $row_offset + $max_per_page || $max_per_page == -99) ;$index++, $row = $this->db->fetchByAssoc($result, $index)){


				foreach($this->list_fields as $entry)
				{

					foreach($entry as $key=>$field) // this will be cycled only once
					{
						if (isset($row[$field])) {
							$this->column_fields[$this->list_fields_names[$key]] = $row[$field];


							$this->log->debug("$this->object_name({$row['id']}): ".$field." = ".$this->$field);
						}
						else
						{
							$this->column_fields[$this->list_fields_names[$key]] = "";
						}
					}
				}


				//$this->db->println("here is the bug");


				$list[] = clone($this);//added by Richie to support PHP5
			}
		}

		$response = Array();
		$response['list'] = $list;
		$response['row_count'] = $rows_found;
		$response['next_offset'] = $next_offset;
		$response['previous_offset'] = $previous_offset;

		return $response;
	}

	//crmv@44323 crmv@102007
	/**
	 * Function to get sort order
	 * return string  $sorder    - sortorder string either 'ASC' or 'DESC'
	 */
	function getSortOrder($module = '', $useSession = true)	{
		global $log,$currentModule;
		$log->debug("Entering getSortOrder() method ...");
		if (empty($module)) $module = $currentModule;
		$use_default_order_by = '';
		//default listview sorting
		if(PerformancePrefs::getBoolean('LISTVIEW_DEFAULT_SORTING', true)) {
			$use_default_sort_order = $this->default_sort_order;
		}
		//crmv default listview customview sorting
		if ($this->customview_sort_order != '' && $use_default_sort_order != $this->customview_sort_order)
			$use_default_sort_order = $this->customview_sort_order;
		if(isset($_REQUEST['sorder']))
			$sorder = $this->db->sql_escape_string($_REQUEST['sorder']);
		elseif ($_REQUEST['override_orderby'] == 'true')
			$sorder = $use_default_sort_order;
		else
			$sorder = (($useSession && VteSession::get($module.'_SORT_ORDER') != '')?(VteSession::get($module.'_SORT_ORDER')):($use_default_sort_order));

		$log->debug("Exiting getSortOrder method ...");
		return $sorder;
	}

	/**
	 * Function to get order by
	 * return string  $order_by    - fieldname(eg: 'campaignname')
	 */
	function getOrderBy($module = '', $useSession = true) {
		global $log,$currentModule;
		$log->debug("Entering getOrderBy() method ...");
		if (empty($module)) $module = $currentModule;
		$use_default_order_by = '';
		//default listview sorting
		if(PerformancePrefs::getBoolean('LISTVIEW_DEFAULT_SORTING', true)) {
			$use_default_order_by = $this->default_order_by;
		}
		//crmv default listview customview sorting
		if ($this->customview_order_by != '' && $use_default_order_by != $this->customview_order_by)
			$use_default_order_by = $this->customview_order_by;
		if (isset($_REQUEST['order_by']))
			$order_by = $this->db->sql_escape_string($_REQUEST['order_by']);
		elseif ($_REQUEST['override_orderby'] == 'true')
		$order_by = $use_default_order_by;
		else
			$order_by = (($useSession && VteSession::get($module.'_ORDER_BY') != '')?(VteSession::get($module.'_ORDER_BY')):($use_default_order_by));

		$log->debug("Exiting getOrderBy method ...");
		return $order_by;
	}
	//crmv@44323e crmv@102007e


	/* This function should be overridden in each module.  It marks an item as deleted.
	 * If it is not overridden, then marking this type of item is not allowed
	 */
	//crmv@2390m crmv@171021 crmv@179044
	function mark_deleted($id)
	{
		global $adb, $table_prefix, $current_user;
		$date_var = date('Y-m-d H:i:s');
		$columns = array('deleted=?','modifiedtime=?');
		$params = array('1',$this->db->formatDate($date_var, true));
		
		if (!in_array($table_prefix.'_crmentity',$this->tab_name)) {
			$table = $this->table_name;
			$table_index = $this->table_index;
			
			$cols = $adb->getColumnNames($table);
			if (in_array('modifiedby', $cols)) {
				$columns[] = 'modifiedby=?';
				$params[] = isset($current_user) ? $current_user->id : 0;
			}
		} else {
			$table = $table_prefix.'_crmentity';
			$table_index = 'crmid';
			
			$columns[] = 'modifiedby=?';
			$params[] = isset($current_user) ? $current_user->id : 0;
		}
		$params[] = $id;
		$query = "UPDATE {$table} set ".implode(',',$columns)." where {$table_index}=?";
		$this->db->pquery($query, $params, true,"Error marking record deleted: ");
	}
	//crmv@2390me crmv@171021e crmv@179044e

	function retrieve_by_string_fields($fields_array, $encode=true)
	{
		$where_clause = $this->get_where($fields_array);

		$query = "SELECT * FROM $this->table_name $where_clause";
		$this->log->debug("Retrieve $this->object_name: ".$query);
		$result =& $this->db->requireSingleResult($query, true, "Retrieving record $where_clause:");
		if( empty($result))
		{
		 	return null;
		}

		 $row = $this->db->fetchByAssoc($result,-1, $encode);

		foreach($this->column_fields as $field)
		{
			if(isset($row[$field]))
			{
				$this->$field = $row[$field];
			}
		}
		return $this;
	}

	/**
         * Function to check if the custom vte_field vte_table exists
         * return true or false
         */
        function checkIfCustomTableExists($tablename)
        {
        		global $adb;
                $query = "select * from ". $adb->sql_escape_string($tablename);
                $result = $this->db->pquery($query, array());
                $testrow = $this->db->num_fields($result);
                if($testrow > 1)
                {
                        $exists=true;
                }
                else
                {
                        $exists=false;
                }
                return $exists;
        }

	/**
	 * function to construct the query to fetch the custom vte_fields
	 * return the query to fetch the custom vte_fields
     */
	function constructCustomQueryAddendum($tablename,$module) {
		global $adb,$table_prefix;

		$tabid=getTabid($module);
		$sql1 = "select columnname,fieldlabel from ".$table_prefix."_field where generatedtype=2 and tabid=?";
		$result = $adb->pquery($sql1, array($tabid));
		$numRows = $adb->num_rows($result);
		$sql3 = "select ";
		for ($i=0; $i < $numRows;$i++) {
			$columnName = $adb->query_result($result,$i,"columnname");
			$fieldlabel = $adb->query_result($result,$i,"fieldlabel");
			//construct query as below
			if ($i == 0) {
				$sql3 .= $tablename.".".$columnName. " '" .$fieldlabel."'";
			} else {
				$sql3 .= ", ".$tablename.".".$columnName. " '" .$fieldlabel."'";
			}
		}
		if ($numRows>0) {
			$sql3=$sql3.',';
		}
		return $sql3;
	}

	/**
	 * Track the viewing of a detail record.  This leverages get_summary_text() which is object specific
	 * params $user_id - The user that is viewing the record.
	 */
	function track_view($user_id, $current_module, $id='')
	{
		global $table_prefix;
		$this->log->debug("About to call ".$table_prefix."_tracker (user_id, module_name, item_id)($user_id, $current_module, $this->id)");

		$tracker = new Tracker();
		$tracker->track_view($user_id, $current_module, $id, '');

		//crmv@35105
		$modnotification = ModNotifications::getInstance(); // crmv@164122
		$modnotification->setSeenForRecord($user_id, $current_module, $id);
		//crmv@35105e
	}

	/**
	* Function to get the column value of a field
	* @param $columnname -- Column name for the field
	* @param $fldvalue -- Input value for the field taken from the User
	* @param $fieldname -- Name of the Field
	* @param $uitype -- UI type of the field
	* @return Column value of the field.
	*/
	function get_column_value($columnname, $fldvalue, $fieldname, $uitype, $datatype='') {
		global $log;
		$log->debug("Entering function get_column_value ($columnname, $fldvalue, $fieldname, $uitype, $datatype='')");

		if (is_uitype($uitype, "_date_") && $fldvalue == '') {
			return null;
		}
		if ($datatype == 'I' || $datatype == 'N' || $datatype == 'NN'){
			return 0;
		}
		$log->debug("Exiting function get_column_value");
		return $fldvalue;
	}

	/**
	* Function to make change to column fields, depending on the current user's accessibility for the fields
	*/
	function apply_field_security() {
		global $current_user, $currentModule;

		require_once('include/utils/UserInfoUtil.php');
		foreach($this->column_fields as $fieldname=>$fieldvalue) {
		$reset_value = false;
			if (getFieldVisibilityPermission($currentModule, $current_user->id, $fieldname) != '0')
				$reset_value = true;

			if ($fieldname == "record_id" || $fieldname == "record_module")
				$reset_value = false;

			/*
				if (isset($this->additional_column_fields) && in_array($fieldname, $this->additional_column_fields) == true)
					$reset_value = false;
			 */

			if ($reset_value == true)
				$this->column_fields[$fieldname] = "";
		}
	}
	
	// crmv@126906
	function create_export_query($where,$oCustomView,$viewId)	//crmv@31775
	{
		global $current_user,$table_prefix;
		$thismodule = $_REQUEST['module'];

		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery($thismodule, "detail_view");

		$fields_list = getFieldsListFromQuery($sql);

		$query = 
			"SELECT $fields_list, {$table_prefix}_users.user_name AS user_name
			FROM {$table_prefix}_crmentity 
			INNER JOIN $this->table_name ON {$table_prefix}_crmentity.crmid=$this->table_name.$this->table_index";
		
		// crmv@96636
		foreach ($this->tab_name as $tab) {
			if ($tab == "{$table_prefix}_crmentity" || $tab == $this->table_name) continue;
			if ($this->customFieldTable && $tab == $this->customFieldTable[0]) continue;
			$index = $this->tab_name_index[$tab];
			if ($index) {
				$query .= " INNER JOIN {$tab} ON {$tab}.{$index} = {$this->table_name}.{$this->table_index}";
			}
		}
		// crmv@96636e

		if(!empty($this->customFieldTable)) {
			$query .= " INNER JOIN ".$this->customFieldTable[0]." ON ".$this->customFieldTable[0].'.'.$this->customFieldTable[1] .
				      " = $this->table_name.$this->table_index";
		}

		$query .= " LEFT JOIN {$table_prefix}_groups ON {$table_prefix}_groups.groupid = {$table_prefix}_crmentity.smownerid";
		$query .= " LEFT JOIN {$table_prefix}_users ON {$table_prefix}_crmentity.smownerid = {$table_prefix}_users.id and {$table_prefix}_users.status='Active'";

		$linkedModulesQuery = $this->db->pquery("SELECT distinct fieldname, columnname, relmodule, tablename FROM {$table_prefix}_field" . // crmv@160361
				" INNER JOIN {$table_prefix}_fieldmodulerel ON {$table_prefix}_fieldmodulerel.fieldid = {$table_prefix}_field.fieldid" .
				" WHERE uitype='10' AND {$table_prefix}_fieldmodulerel.module=?", array($thismodule));
		$linkedFieldsCount = $this->db->num_rows($linkedModulesQuery);

		for($i=0; $i<$linkedFieldsCount; $i++) {
			$related_module = $this->db->query_result($linkedModulesQuery, $i, 'relmodule');
			$fieldname = $this->db->query_result($linkedModulesQuery, $i, 'fieldname');
			$columnname = $this->db->query_result($linkedModulesQuery, $i, 'columnname');
			$tablename = $this->db->query_result($linkedModulesQuery, $i, 'tablename'); // crmv@160361
			
			if ($related_module == $this->modulename) continue; // uitype 10 with same module is not supported

			$other = CRMEntity::getInstance($related_module);
			vtlib_setup_modulevars($related_module, $other);

			$query .= " LEFT JOIN $other->table_name ON $other->table_name.$other->table_index = $tablename.$columnname"; // crmv@160361
		}

		//crmv@31775
		$reportFilter = $oCustomView->getReportFilter($viewId);
		if ($reportFilter) {
			$tableNameTmp = $oCustomView->getReportFilterTableName($reportFilter,$current_user->id);
			$query .= " INNER JOIN $tableNameTmp ON $tableNameTmp.id = {$table_prefix}_crmentity.crmid";
		}
		//crmv@31775e

		//crmv@58099
		$query .= $this->getNonAdminAccessControlQuery($thismodule,$current_user);
		$where_auto = " {$table_prefix}_crmentity.deleted = 0 ";

		if($where != '') $query .= " WHERE ($where) AND $where_auto";
		else $query .= " WHERE $where_auto";
		
		$query = $this->listQueryNonAdminChange($query, $thismodule);
		//crmv@58099e
		
		return $query;
	}
	
	/**
	 * Function which will give the basic query to find duplicates
	 */
	function getDuplicatesQuery($module,$table_cols,$field_values,$ui_type_arr,$select_cols='') {
		global $table_prefix;
		
		$select_clause = "SELECT ". $this->table_name .".".$this->table_index ." AS recordid, {$table_prefix}_users_last_import.deleted,".$table_cols;

		// Select Custom Field Table Columns if present
		if(isset($this->customFieldTable)) $query .= ", " . $this->customFieldTable[0] . ".* ";

		$from_clause = " FROM $this->table_name";

		$from_clause .= "	INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = $this->table_name.$this->table_index";

		// Consider custom table join as well.
		if(isset($this->customFieldTable)) {
			$from_clause .= " INNER JOIN ".$this->customFieldTable[0]." ON ".$this->customFieldTable[0].'.'.$this->customFieldTable[1] .
				      " = $this->table_name.$this->table_index";
		}
		$from_clause .= " LEFT JOIN {$table_prefix}_users ON {$table_prefix}_users.id = {$table_prefix}_crmentity.smownerid
						LEFT JOIN {$table_prefix}_groups ON {$table_prefix}_groups.groupid = {$table_prefix}_crmentity.smownerid";

		$where_clause = "	WHERE {$table_prefix}_crmentity.deleted = 0";
		$where_clause .= $this->getListViewSecurityParameter($module);

		if (isset($select_cols) && trim($select_cols) != '') {
			$sub_query = "SELECT $select_cols FROM  $this->table_name AS t " .
				" INNER JOIN {$table_prefix}_crmentity AS crm ON crm.crmid = t.".$this->table_index;
			// Consider custom table join as well.
			if(isset($this->customFieldTable)) {
				$sub_query .= " INNER JOIN ".$this->customFieldTable[0]." tcf ON tcf.".$this->customFieldTable[1]." = t.$this->table_index";
			}
			$sub_query .= " WHERE crm.deleted=0 GROUP BY $select_cols HAVING COUNT(*)>1";
		} else {
			$sub_query = "SELECT $table_cols $from_clause $where_clause GROUP BY $table_cols HAVING COUNT(*)>1";
		}

		$query = $select_clause . $from_clause .
					" LEFT JOIN {$table_prefix}_users_last_import ON {$table_prefix}_users_last_import.bean_id=" . $this->table_name .".".$this->table_index .
					" INNER JOIN (" . $sub_query . ") temp ON ".get_on_clause($field_values,$ui_type_arr,$module) .
					$where_clause .
					" ORDER BY $table_cols,". $this->table_name .".".$this->table_index ." ASC";

		return $query;
	}
	// crmv@126906e
	
	/**
	 * Function invoked during export of module record value.
	 */
	function transform_export_value($key, $value) {
		// NOTE: The sub-class can override this function as required.
		return $value;
	}

	//crmv@7231
	 function crmv_compare_column_fields($fieldsEXT,$fieldsCRM){
		foreach(array_keys($fieldsEXT) as $key)
		{
			if ( ($fieldsEXT[$key]!=$fieldsCRM[$key]) &&
				((($fieldsEXT[$key]=="") || ($fieldsEXT[$key]=="--None--") ) ||
				($key=="annual_revenue" && ($fieldsEXT[$key]=="0")))
			) {
				$fieldsJDE[$key]=$fieldsCRM[$key];
				$this->column_fields[$key] = $fieldsEXT[$key];
			}

		}
		return true;
	}
	 function crmv_save_ajax_code($key){
		$this->column_fields['external_code']=$key;
		return true;
	}
	//crmv@7231e
	//ds@28 workflow
	//crmv@8716
	function check_workflow_event($module_name,$old_column_fields)
	{
		if (array_search_recursive($module_name,getWorkflowModulesList()))
		{
			return fire_workflow_event_check($module_name,$old_column_fields,$this->id,false);
		}
		else
		{
			return false;
		}
	}
	//crmv@8716e
	//ds@28e

	//crmv@8719
	/** Function to initialize the required fields array for that particular module */
	function initRequiredFields($module) {
		global $adb,$table_prefix;

		$tabid = getTabId($module);
		$sql = "select * from ".$table_prefix."_field where tabid= ? and typeofdata like '%M%' and uitype not in ('53','70') and ".$table_prefix."_field.presence in (0,2)";
		$result = $adb->pquery($sql,array($tabid));
        $numRows = $adb->num_rows($result);
        for($i=0; $i < $numRows;$i++)
        {
        	$fieldName = $adb->query_result($result,$i,"fieldname");
			$this->required_fields[$fieldName] = 1;
		}
	}
	/**
	 * Move the related records of the specified list of id's to the given record.
	 * @param String This module name
	 * @param Array List of Entity Id's from which related records need to be transfered
	 * @param Integer Id of the the Record to which the related records are to be moved
	 */
	function transferRelatedRecords($module, $transferEntityIds, $entityId) {
		global $adb,$log,$table_prefix;
		$log->debug("Entering function transferRelatedRecords ($module, $transferEntityIds, $entityId)");
		foreach($transferEntityIds as $transferId){

			// Pick the records related to the entity to be transfered, but do not pick the once which are already related to the current entity.
			$relatedRecords =  $adb->pquery("SELECT relcrmid, relmodule FROM ".$table_prefix."_crmentityrel WHERE crmid=? AND module=?" .
					" AND relcrmid NOT IN (SELECT relcrmid FROM ".$table_prefix."_crmentityrel WHERE crmid=? AND module=?)",
					 array($transferId, $module, $entityId, $module));
			$numOfRecords = $adb->num_rows($relatedRecords);
			for($i=0;$i<$numOfRecords;$i++) {
				$relcrmid = $adb->query_result($relatedRecords,$i,'relcrmid');
				$relmodule = $adb->query_result($relatedRecords,$i,'relmodule');
				$adb->pquery("UPDATE ".$table_prefix."_crmentityrel SET crmid=? WHERE relcrmid=? AND relmodule=? AND crmid=? AND module=?",
								array($entityId, $relcrmid, $relmodule, $transferId, $module));
			}

			// Pick the records to which the entity to be transfered is related, but do not pick the once to which current entity is already related.
			$parentRecords =  $adb->pquery("SELECT crmid, module FROM ".$table_prefix."_crmentityrel WHERE relcrmid=? AND relmodule=?" .
					" AND crmid NOT IN (SELECT crmid FROM ".$table_prefix."_crmentityrel WHERE relcrmid=? AND relmodule=?)",
					 array($transferId, $module, $entityId, $module));
			$numOfRecords = $adb->num_rows($parentRecords);
			for($i=0;$i<$numOfRecords;$i++) {
				$parcrmid = $adb->query_result($parentRecords,$i,'crmid');
				$parmodule = $adb->query_result($parentRecords,$i,'module');
				$adb->pquery("UPDATE ".$table_prefix."_crmentityrel SET relcrmid=? WHERE crmid=? AND module=? AND relcrmid=? AND relmodule=?",
								array($entityId, $parcrmid, $parmodule, $transferId, $module));
			}
			
			// crmv@125816 - the same with the ordered table
			// Pick the records related to the entity to be transfered, but do not pick the once which are already related to the current entity.
			$relatedRecords =  $adb->pquery("SELECT relcrmid, relmodule FROM ".$table_prefix."_crmentityrel_ord WHERE crmid=? AND module=?" .
					" AND relcrmid NOT IN (SELECT relcrmid FROM ".$table_prefix."_crmentityrel_ord WHERE crmid=? AND module=?)",
					 array($transferId, $module, $entityId, $module));
			$numOfRecords = $adb->num_rows($relatedRecords);
			for($i=0;$i<$numOfRecords;$i++) {
				$relcrmid = $adb->query_result($relatedRecords,$i,'relcrmid');
				$relmodule = $adb->query_result($relatedRecords,$i,'relmodule');
				$adb->pquery("UPDATE ".$table_prefix."_crmentityrel_ord SET crmid=? WHERE relcrmid=? AND relmodule=? AND crmid=? AND module=?",
								array($entityId, $relcrmid, $relmodule, $transferId, $module));
			}

			// Pick the records to which the entity to be transfered is related, but do not pick the once to which current entity is already related.
			$parentRecords =  $adb->pquery("SELECT crmid, module FROM ".$table_prefix."_crmentityrel_ord WHERE relcrmid=? AND relmodule=?" .
					" AND crmid NOT IN (SELECT crmid FROM ".$table_prefix."_crmentityrel_ord WHERE relcrmid=? AND relmodule=?)",
					 array($transferId, $module, $entityId, $module));
			$numOfRecords = $adb->num_rows($parentRecords);
			for($i=0;$i<$numOfRecords;$i++) {
				$parcrmid = $adb->query_result($parentRecords,$i,'crmid');
				$parmodule = $adb->query_result($parentRecords,$i,'module');
				$adb->pquery("UPDATE ".$table_prefix."_crmentityrel_ord SET relcrmid=? WHERE crmid=? AND module=? AND relcrmid=? AND relmodule=?",
								array($entityId, $parcrmid, $parmodule, $transferId, $module));
			}			
			// crmv@125816e
		}
		//crmv@15526
		if (count($transferEntityIds)>0){
			//modify 1-n relations of entities related to the one to be deleted
			$uitype10_fields_res =  $adb->pquery("SELECT ".$table_prefix."_fieldmodulerel.module,".$table_prefix."_field.columnname,".$table_prefix."_field.tablename FROM ".$table_prefix."_field
				INNER JOIN ".$table_prefix."_fieldmodulerel ON ".$table_prefix."_fieldmodulerel.fieldid = ".$table_prefix."_field.fieldid
				AND ".$table_prefix."_fieldmodulerel.relmodule = ?",array($module));
			if ($uitype10_fields_res && $adb->num_rows($uitype10_fields_res)>0){
				while ($row = $adb->fetchByAssoc($uitype10_fields_res,-1,false)){
					$sql = "update {$row['tablename']} set {$row['columnname']} = ? where {$row['columnname']} in (".generateQuestionMarks($transferEntityIds).")"; // crmv@172864
					$params = Array($entityId,$transferEntityIds);
					$adb->pquery($sql,$params);
				}
			}
		}
		//crmv@15526 end
		//crmv@76864
		$rel_table_arr = Array('Messages'=>"{$table_prefix}_messagesrel",'MessagesRecipients'=>"{$table_prefix}_messages_recipients");
		$tbl_field_arr = Array("{$table_prefix}_messagesrel"=>"messagehash","{$table_prefix}_messages_recipients"=>'messagesid');
		$entity_tbl_field_arr = Array("{$table_prefix}_messagesrel"=>"crmid","{$table_prefix}_messages_recipients"=>'id');
		foreach($transferEntityIds as $transferId) {
			foreach($rel_table_arr as $rel_module=>$rel_table) {
				$id_field = $tbl_field_arr[$rel_table];
				$entity_id_field = $entity_tbl_field_arr[$rel_table];
				// IN clause to avoid duplicate entries
				$sel_result =  $adb->pquery("select $id_field from $rel_table where $entity_id_field=? " .
						" and $id_field not in (select $id_field from $rel_table where $entity_id_field=?)",
						array($transferId,$entityId));
				$res_cnt = $adb->num_rows($sel_result);
				if($res_cnt > 0) {
					for($i=0;$i<$res_cnt;$i++) {
						$id_field_value = $adb->query_result($sel_result,$i,$id_field);
						$adb->pquery("update $rel_table set $entity_id_field=? where $entity_id_field=? and $id_field=?",
							array($entityId,$transferId,$id_field_value));
					}
				}
			}
		}
		//crmv@76864e
		$log->debug("Exiting transferRelatedRecords...");
	}

	//crmv@8719
	/**
	* Function to initialize the sortby fields array
	*/
	function initSortByField($module) {
		global $adb, $log,$table_prefix;
		$log->debug("Entering function initSortByField ($module)");
		// Define the columnname's and uitype's which needs to be excluded
		$exclude_columns = Array (); // crmv@172647
		$exclude_uitypes = Array ();

		$tabid = getTabId($module);
		if($module == 'Calendar') {
			$tabid = array('9','16');
		}
		// crmv@172647
		$sql = "SELECT columnname FROM ".$table_prefix."_field ".
				" WHERE tabid in (". generateQuestionMarks($tabid) .") and ".$table_prefix."_field.presence in (0,2)";
		// crmv@172647e
		$params = array($tabid);
		if (count($exclude_columns) > 0) {
			$sql .= " AND columnname NOT IN (". generateQuestionMarks($exclude_columns) .")";
			array_push($params, $exclude_columns);
		}
		if (count($exclude_uitypes) > 0) {
			$sql .= " AND uitype NOT IN (". generateQuestionMarks($exclude_uitypes) . ")";
			array_push($params, $exclude_uitypes);
		}
		$result = $adb->pquery($sql,$params);
		$num_rows = $adb->num_rows($result);
		for($i=0; $i<$num_rows; $i++) {
			$columnname = $adb->query_result($result,$i,'columnname');
			if(in_array($columnname, $this->sortby_fields)) continue;
			else $this->sortby_fields[] = $columnname;
		}
		if($tabid == 21 or $tabid == 22)
			$this->sortby_fields[] = 'crmid';
		$log->debug("Exiting initSortByField");
	}

	function setMaxModuleSeqNumber($module,$req_str){
		global $adb,$table_prefix;
		//select max number of prefix in existing records
		vtlib_setup_modulevars($module, $this);
		$tabid = getTabid($module);
		$fieldinfo = $adb->pquery("SELECT * FROM ".$table_prefix."_field WHERE tabid = ? AND uitype = 4", Array($tabid));
		if ($fieldinfo){
			$row = $adb->fetchByAssoc($fieldinfo);
			$table = $row['tablename'];
			$field = $row['fieldname'];
			if ($req_str == '')
				$sql = "SELECT max($field) AS number
					FROM $table,".$table_prefix."_modentity_num
					WHERE ".$table_prefix."_modentity_num.semodule = ? and prefix = ?";
			else
				$sql = "SELECT max(".$adb->database->substr."($field,(".$adb->database->length."(prefix)+1),".$adb->database->length."($field))) AS num
						FROM $table,".$table_prefix."_modentity_num
						WHERE ".$table_prefix."_modentity_num.semodule = ? and prefix = ?";
			$params = Array($module,$req_str);
			$res = $adb->pquery($sql,$params);
			if ($res){
					$number = $adb->query_result($res,0,'num');
					if (!is_numeric($number))
						$number = 0;
					$sql = "update ".$table_prefix."_modentity_num set cur_id = ? where semodule = ? and prefix = ?";
					$adb->pquery($sql,Array($number,$module,$req_str));
			}
		}
	}

	/* Function to set the Sequence string and sequence number starting value */
	function setModuleSeqNumber($mode, $module, $req_str='', $req_no='', $business_unit='', $refer_bu_mc ='')	//crmv@151614
	{
		global $adb,$table_prefix;
		//when we configure the invoice number in Settings this will be used
		if ($mode == "configure" && $req_no != '') {
			$check = $adb->pquery("select cur_id from ".$table_prefix."_modentity_num where semodule=? and prefix = ? and active = 1", array($module, $req_str));
			if($adb->num_rows($check)== 0) {
				$numid = $adb->getUniqueId($table_prefix."_modentity_num");
				$active = $adb->pquery("select num_id from ".$table_prefix."_modentity_num where semodule=? and active=1", array($module));
				$adb->pquery("UPDATE ".$table_prefix."_modentity_num SET active=0 where num_id=?", array($adb->query_result($active,0,'num_id')));

				$adb->pquery("INSERT into ".$table_prefix."_modentity_num values(?,?,?,?,?,?)", array($numid,$module,$req_str,$req_no,$req_no,1));
				return true;
			}
			else if($adb->num_rows($check)!=0) {
				$this->setMaxModuleSeqNumber($module,$req_str);
				$num_check = $adb->query_result($check,0,'cur_id');
				if($req_no < $num_check) {
					return false;
				}
				else {
					$adb->pquery("UPDATE ".$table_prefix."_modentity_num SET active=0 where active=1 and semodule=?", array($module));
					$adb->pquery("UPDATE ".$table_prefix."_modentity_num SET cur_id=?, active = 1 where prefix=? and semodule=?", array($req_no,$req_str,$module));
					return true;
				}
			}
		}
		else if ($mode == "increment") {
			//when we save new invoice we will increment the invoice id and write
			// crmv@153702
			// mysql driver has no transactions, thus here there is a clear race condition
			// which requires an explicit lock on the table
			if ($adb->dbType == 'mysql' || $adb->dbType == 'mysqli') { //crmv@182434
				$adb->query("LOCK TABLES {$table_prefix}_modentity_num WRITE");
			}
			// crmv@153702e
			$check = $adb->pquery("select cur_id,prefix from ".$table_prefix."_modentity_num where semodule=? and active = 1", array($module));
			$prefix = $adb->query_result($check,0,'prefix');
			$curid = $adb->query_result($check,0,'cur_id');
			$prev_inv_no=$prefix.$curid;
			$strip=strlen($curid)-strlen($curid+1);
			if($strip<0)$strip=0;
			$temp = str_repeat("0",$strip);
			$req_no.= $temp.($curid+1);
			$adb->pquery("UPDATE ".$table_prefix."_modentity_num SET cur_id=? where cur_id=? and active=1 AND semodule=?", array($req_no,$curid,$module));
			// crmv@153702
			if ($adb->dbType == 'mysql' || $adb->dbType == 'mysqli') { //crmv@182434
				$adb->query("UNLOCK TABLES");
			}
			// crmv@153702e
			return decode_html($prev_inv_no);
		}
	}
	// END

	/* Function to get the next module sequence number for a given module */
	function getModuleSeqInfo($module) {
		global $adb,$table_prefix;
		$check = $adb->pquery("select cur_id,prefix from ".$table_prefix."_modentity_num where semodule=? and active = 1", array($module));
		if ($check){
			$prefix = $adb->query_result($check,0,'prefix');
			$curid = $adb->query_result($check,0,'cur_id');
		}
		return array($prefix, $curid);
	}
	// END

	/* Function to check if the mod number already exits */
	function checkModuleSeqNumber($table, $column, $no)
	{
		global $adb;
		$result=$adb->pquery("select ".$adb->sql_escape_string($column).
			" from ".$adb->sql_escape_string($table).
			" where ".$adb->sql_escape_string($column)." = ?", array($no));

		$num_rows = $adb->num_rows($result);

		if($num_rows > 0)
			return true;
		else
			return false;
	}
	// END

	function updateMissingSeqNumber($module) {
		global $log, $adb, $table_prefix;
		$log->debug("Entered updateMissingSeqNumber function");

		vtlib_setup_modulevars($module, $this);

		$tabid = getTabid($module);
		$fieldinfo = $adb->pquery("SELECT * FROM ".$table_prefix."_field WHERE tabid = ? AND uitype = 4", Array($tabid));

		$returninfo = Array();

		if($fieldinfo && $adb->num_rows($fieldinfo)) {
			// TODO: We assume the following for module sequencing field
			// 1. There will be only field per module
			// 2. This field is linked to module base table column
			$fld_table = $adb->query_result($fieldinfo, 0, 'tablename');
			$fld_column = $adb->query_result($fieldinfo, 0, 'columnname');

			if($fld_table == $this->table_name) {
				$records = $adb->query("SELECT $this->table_index AS recordid FROM $this->table_name " .
					"inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$this->table_name.".".$this->table_index." WHERE $fld_column = '' OR $fld_column is NULL and deleted = 0");

				if($records && $adb->num_rows($records)) {
					$returninfo['totalrecords'] = $adb->num_rows($records);
					$returninfo['updatedrecords'] = 0;

					$modseqinfo = $this->getModuleSeqInfo($module);
					$prefix = $modseqinfo[0];
					$cur_id = $modseqinfo[1];

					$old_cur_id = $cur_id;
					while($recordinfo = $adb->fetch_array($records)) {
						$value = "$prefix"."$cur_id";
						$adb->pquery("UPDATE $fld_table SET $fld_column = ? WHERE $this->table_index = ?", Array($value, $recordinfo['recordid']));
						// crmv@172653
						$strip= strlen($cur_id) - strlen($cur_id+1);
						if ($strip<0) $strip=0;
						$temp = str_repeat("0",$strip);
						$cur_id = $temp.($cur_id+1);
						// crmv@172653e
						$returninfo['updatedrecords'] = $returninfo['updatedrecords'] + 1;
					}
					if($old_cur_id != $cur_id) {
						$adb->pquery("UPDATE ".$table_prefix."_modentity_num set cur_id=? where semodule=? and active=1", Array($cur_id, $module));
					}
				}
			} else {
				$log->fatal("Updating Missing Sequence Number FAILED! REASON: Field table and module table mismatching.");
			}
		}
		return $returninfo;
	}

	//crmv43864		crmv@54245
	function get_related_buttons($this_module, $this_crmid, $for_module, $actions, $depField = '') {
		global $current_user;

		$parenttab = getParentTab();
		$singular_modname = vtlib_toSingular($for_module);

		if (is_string($actions)) $actions = array_map('trim', explode(',', strtoupper($actions)));
		$relationId = intval($_REQUEST['relation_id']); // crmv@56603

		$buttons = '';

		// for dependents list
		$fieldPerm = true;
		if (!empty($depField)) $fieldPerm = (getFieldVisibilityPermission($for_module,$current_user->id,$depField) == '0');

		// crmv@56603
		if ($for_module == 'Calendar') {
		    //crmv@62447
			if (in_array('ADD', $actions) && isPermitted($for_module,'EditView', '') == 'yes' && $fieldPerm) { // crmv@189154
				if (PerformancePrefs::getBoolean('ADD_RELATION_IN_FULL_PAGE')) { // crmv@115378
					$onclickTask = "onclick='this.form.action.value=\"EditView\";this.form.module.value=\"$for_module\";this.form.return_module.value=\"$this_module\";this.form.activity_mode.value=\"Task\";' type='submit' name='button'";
					$urlOnClick = 'index.php?module=Calendar&action=index&skip_footer=true&hide_menus=true&related_add=true&from_module='.$this_module.'&activity_mode=Events&from_crmid='.$this_crmid;
					$onclickEvents = "onclick='openPopup(\"$urlOnClick\")' type='button' name='button'";
					//$onclickEvents = "onclick='this.form.action.value=\"EditView\";this.form.module.value=\"$for_module\";this.form.return_module.value=\"$this_module\";this.form.activity_mode.value=\"Events\";' type='submit' name='button'";
				} else {
					$onclickTask = "onclick=\"LPOP.openEventCreate('$this_module', '$this_crmid', 'Task', {'relation_id':'$relationId'})\" type='button' name='button'";
					$onclickEvents = "onclick=\"LPOP.openEventCreate('$this_module', '$this_crmid', 'Events', {'relation_id':'$relationId'})\" type='button' name='button'";
				}
				$buttons .= "<input title='".getTranslatedString('LBL_ADD_NEW'). " ". getTranslatedString('LBL_TODO',$for_module) ."' class='crmbutton small create' $onclickTask value='". getTranslatedString('LBL_ADD_NEW'). " " . getTranslatedString('LBL_TODO',$for_module) ."'>&nbsp;";
				$buttons .= "<input title='".getTranslatedString('LBL_ADD_NEW'). " ". getTranslatedString('LBL_EVENT', $for_module) ."' class='crmbutton small create' $onclickEvents value='". getTranslatedString('LBL_ADD_NEW'). " " . getTranslatedString('LBL_EVENT',$for_module) ."'>&nbsp;";
			}
			//crmv@62447e
		} else {
			if (in_array('SELECT', $actions) && isPermitted($for_module,'DetailView', '') == 'yes' && empty($depField)) { // crmv@189154
				$buttons .= "<input title='".getTranslatedString('LBL_SELECT')." ". getTranslatedString($for_module). "' class='crmbutton small edit' type='button' ".
		 			"onclick=\"LPOP.openPopup('$this_module', '$this_crmid', '', {'show_module':'$for_module','relation_id':'$relationId'})\" ".
		 			"value='". getTranslatedString('LBL_SELECT'). " " . getTranslatedString($for_module,$for_module) ."'>&nbsp;"; //crmv@21048m
			}
			if (in_array('ADD', $actions) && isPermitted($for_module,'EditView', '') == 'yes' && $fieldPerm) { // crmv@189154
				if (PerformancePrefs::getBoolean('ADD_RELATION_IN_FULL_PAGE')) { // crmv@115378
					$onclick = "onclick='this.form.action.value=\"EditView\";this.form.module.value=\"$for_module\";this.form.record.value=\"\"' type='submit' name='button'"; // crmv@186446
				} else {
					$onclick = "onclick=\"LPOP.openPopup('$this_module', '$this_crmid', 'onlycreate', {'show_module':'$for_module','relation_id':'$relationId'})\" type='button' name='button'";
				}
				$buttons .= "<input title='".getTranslatedString('LBL_ADD_NEW'). " ". getTranslatedString($singular_modname,$for_module) ."' class='crmbutton small create' $onclick value='". getTranslatedString('LBL_ADD_NEW'). " " . getTranslatedString($singular_modname,$for_module) ."'>&nbsp;";
			}
		}
		// crmv@56603e

		return $buttons;
	}
	//crmv@43864e	crmv@54245e

	/* Generic function to get attachments in the related list of a given module */
	function get_attachments($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $currentModule, $app_strings,$table_prefix;//crmv@203484 removed global singlepane
		$this_module = $currentModule;
		$parenttab = getParentTab();

		$related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance($related_module);

		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e

		// Some standard module class doesn't have required variables
		// that are used in the query, they are defined in this generic API
		vtlib_setup_modulevars($related_module, $other);

		if($actions) {
			$button = $this->get_related_buttons($this_module, $id, $related_module, $actions); // crmv@43864
		}

		// To make the edit or del link actions to return back to same view.
		if($singlepane_view == true) $returnset = "&return_module=$this_module&return_action=DetailView&return_id=$id";//crmv@203484 changed to normal bool true, not string 'true'
		else $returnset = "&return_module=$this_module&return_action=CallRelatedList&return_id=$id";

		//crmv@185647
		$currentModuleInstance = CRMEntity::getInstance($this_module);
		$query = "select case when (".$table_prefix."_users.user_name is not null) then ".$table_prefix."_users.user_name else ".$table_prefix."_groups.groupname end as user_name," .
				"'Documents' ActivityType,".$table_prefix."_attachments.type  FileType,crm2.modifiedtime lastmodified,
			".$table_prefix."_seattachmentsrel.attachmentsid attachmentsid,
			".$table_prefix."_notes.notesid crmid,
			".$table_prefix."_notes.notecontent description,
			".$table_prefix."_notes.note_no,
			".$table_prefix."_notes.title,
			".$table_prefix."_notes.filename,
			".$table_prefix."_notes.folderid,
			".$table_prefix."_notes.filestatus,
			".$table_prefix."_notes.filesize,
			".$table_prefix."_notes.fileversion
			from ".$table_prefix."_notes
			inner join ".$table_prefix."_senotesrel on ".$table_prefix."_senotesrel.notesid= ".$table_prefix."_notes.notesid
			inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid= ".$table_prefix."_notes.notesid and ".$table_prefix."_crmentity.deleted=0
			inner join ".$table_prefix."_notescf on ".$table_prefix."_notescf.notesid = ".$table_prefix."_notes.notesid
			inner join {$currentModuleInstance->getEntityTableInfo('table')} crm2 on crm2.{$currentModuleInstance->getEntityTableInfo('index')} = ".$table_prefix."_senotesrel.crmid
			left join ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
			left join ".$table_prefix."_seattachmentsrel  on ".$table_prefix."_seattachmentsrel.crmid =".$table_prefix."_notes.notesid
			left join ".$table_prefix."_attachments on ".$table_prefix."_seattachmentsrel.attachmentsid = ".$table_prefix."_attachments.attachmentsid
			left join ".$table_prefix."_users on ".$table_prefix."_crmentity.smownerid= ".$table_prefix."_users.id
			where crm2.{$currentModuleInstance->getEntityTableInfo('index')} = ".$id;
		//crmv@185647e

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;
		return $return_value;
	}
	
	// crmv@125816
	/**
	 * Default (generic) function to handle the related list (N-N) for the module.
	 * @param int $id The relation_id of the relation
	 * @param int $cur_tab_id The tabid of the source module
	 * @param int $rel_tab_id The tabid of the related module
	 * @param String $actions List of actions for the related
	 * @param String $direction if non empty, can be "direct" or "inverse" and in this case
	 *   the ordered relation table is used
	 * NOTE: Vtecrm_Module::setRelatedList sets reference to this function in vte_relatedlists table
	 * if function name is not explicitly specified.
	 */
	function get_related_list($id, $cur_tab_id, $rel_tab_id, $actions=false, $direction = null) { // crmv@125816

		global $currentModule, $app_strings, $table_prefix, $current_user;//crmv@203484 removed global singlepane

		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e

		$parenttab = getParentTab();

		$related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance($related_module);

		// Some standard module class doesn't have required variables
		// that are used in the query, they are defined in this generic API
		vtlib_setup_modulevars($currentModule, $this);
		vtlib_setup_modulevars($related_module, $other);

		$button = '';
		if($actions) {
			$button .= $this->get_related_buttons($currentModule, $id, $related_module, $actions); // crmv@43864
		}

		// To make the edit or del link actions to return back to same view.
		if($singlepane_view == true) $returnset = "&return_module=$currentModule&return_action=DetailView&return_id=$id";//crmv@203484 changed to normal bool true, not string 'true'
		else $returnset = "&return_module=$currentModule&return_action=CallRelatedList&return_id=$id";

		$query = "SELECT ".$table_prefix."_crmentity.crmid";
		//crmv@fix query
		foreach ($other->list_fields as $label=>$arr){
			foreach ($arr as $table=>$field){
				if ($table != 'crmentity' && !is_numeric($table) && $field){
					if (strpos($table,$table_prefix.'_') !== false)
						$query.=",$table.$field";
					else
						$query.=",{$table_prefix}_{$table}.{$field}";
				}
			}
		}
		//crmv@fix query end
		$query .= ", CASE WHEN (".$table_prefix."_users.user_name is not null) THEN ".$table_prefix."_users.user_name ELSE ".$table_prefix."_groups.groupname END AS user_name";

		$more_relation = '';
		if(!empty($other->related_tables)) {
			foreach($other->related_tables as $tname=>$relmap) {
				// Setup the default JOIN conditions if not specified
				if(empty($relmap[1])) $relmap[1] = $other->table_name;
				if(empty($relmap[2])) $relmap[2] = $relmap[0];
				$more_relation .= " LEFT JOIN $tname ON $tname.$relmap[0] = $relmap[1].$relmap[2]";
			}
		}

		$query .= " FROM $other->table_name";
		$query .= " INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = $other->table_name.$other->table_index";
		//crmv@24527
		if (!empty($other->customFieldTable)) {
			$query .= " INNER JOIN ".$other->customFieldTable[0]." ON $other->table_name.$other->table_index = ".$other->customFieldTable[0].".".$other->customFieldTable[1];
		}
		//crmv@24527e
		
		// crmv@161254 crmv@161515
		if (is_array($other->tab_name)) {
			foreach($other->tab_name as $tab_name){
				if (stripos($query,"join ".$tab_name) === false && stripos($query,"from ".$tab_name) === false && stripos($more_relation,"join ".$tab_name) === false) { // crmv@167298 crmv@171367
					$query .= ' '.$other->getJoinClause($tab_name)." ".$tab_name." ON ".$tab_name.".".$other->tab_name_index[$tab_name]." = $other->table_name.$other->table_index"; //crmv@173237
				}
			}
		}
		// crmv@161254e crmv@161515e
		
		//crmv@43864
		if ($related_module == 'Products' || $currentModule == 'Products'){
			$query .= " INNER JOIN ".$table_prefix."_seproductsrel ON (".$table_prefix."_seproductsrel.crmid = ".$table_prefix."_crmentity.crmid OR ".$table_prefix."_seproductsrel.productid = ".$table_prefix."_crmentity.crmid)";
		}
		elseif ($related_module == 'Documents'){
			$query .= " INNER JOIN ".$table_prefix."_senotesrel ON (".$table_prefix."_senotesrel.notesid = ".$table_prefix."_crmentity.crmid OR ".$table_prefix."_senotesrel.crmid = ".$table_prefix."_crmentity.crmid)";
		// crmv@44187
		} elseif (!empty($this->extra_relation_tables[$related_module])) {
			$reltab = $this->extra_relation_tables[$related_module]['relation_table'];
			$relidx = $this->extra_relation_tables[$related_module]['relation_table_id'];
			$relidx2 = $this->extra_relation_tables[$related_module]['relation_table_otherid'];
			$relmod1 = $this->extra_relation_tables[$related_module]['relation_table_module'];
			$relmod2 = $this->extra_relation_tables[$related_module]['relation_table_othermodule'];
			$query .= " INNER JOIN $reltab ON $reltab.$relidx2 = {$table_prefix}_crmentity.crmid ";
		//crmv@46974 crmv@63349
		} else{
			// crmv@125816
			$relation = null;
			if ($direction == 'direct' || $direction == 'inverse') {
				// get the children or parents
				$relation['reltab'] = $this->relation_table_ord;
				$relation['direction'] = $direction;
			}
			$tmodreltables = TmpUserModRelTables::getInstance();
			$tabname = $this->setupTemporaryRelTable($currentModule,$related_module,$id, $relation);
			// crmv@125816e
			if ($tabname == $tmodreltables->tmpTable) {
				$join = $tmodreltables->getJoinCondition($currentModule, $related_module, $current_user->id, $id, '', $table_prefix."_crmentity.crmid", $table_prefix."_crmentityrel");
				$query .= " INNER JOIN {$tabname} {$table_prefix}_crmentityrel ON $join";
			} else {
				$query .= " INNER JOIN {$tabname} ".$table_prefix."_crmentityrel ON (".$table_prefix."_crmentityrel.relcrmid = ".$table_prefix."_crmentity.crmid)";
			}
		}
		//crmv@46974e crmv@63349e
		// crmv@44187e

		if ($related_module == $currentModule) {
			$aliasTable = $this->table_name."2";
		} else {
			$aliasTable = $this->table_name;
		}
		$query .= " LEFT  JOIN $this->table_name $aliasTable ON $aliasTable.$this->table_index = $other->table_name.$other->table_index";

		$query .= $more_relation;
		$query .= " LEFT  JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid";
		$query .= " LEFT  JOIN ".$table_prefix."_groups       ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid";

		// crmv@64325
		$setypeCond = '';
		if (PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) {
			$setypeCond = "AND {$table_prefix}_crmentity.setype = '$related_module'";
		}
		if ($related_module == 'Products' || $currentModule == 'Products'){
			$query .= " WHERE ".$table_prefix."_crmentity.deleted = 0 $setypeCond AND (".$table_prefix."_seproductsrel.crmid = $id OR ".$table_prefix."_seproductsrel.productid = $id)";
		}
		elseif ($related_module == 'Documents'){
			$query .= " WHERE ".$table_prefix."_crmentity.deleted = 0 $setypeCond AND (".$table_prefix."_senotesrel.crmid = $id OR ".$table_prefix."_senotesrel.notesid = $id)";
		// crmv@44187
		} elseif (!empty($this->extra_relation_tables[$related_module])) {
			$query .= " WHERE ".$table_prefix."_crmentity.deleted = 0 $setypeCond AND $reltab.$relidx = $id";
			if (!empty($relmod1)) {
				$query .= " AND $reltab.$relmod1 = '$currentModule'";
			}
			if (!empty($relmod2)) {
				$query .= " AND $reltab.$relmod2 = '$related_module'";
			}
		//crmv@46974	
		}else{
			$query .= " WHERE ".$table_prefix."_crmentity.deleted = 0 $setypeCond AND (".$table_prefix."_crmentityrel.crmid = $id)";
		}
		//crmv@46974 e	
		// crmv@44187e crmv@43864e crmv@64325e

		$return_value = GetRelatedList($currentModule, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		return $return_value;
	}
	
	/**
	 * Retrieve the parent records in a ordered N-N relation
	 */
	function get_parents_list($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		return $this->get_related_list($id, $cur_tab_id, $rel_tab_id, $actions, 'inverse');
	}
	
	/**
	 * Retrieve the children records in a ordered N-N relation
	 */	
	function get_children_list($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		return $this->get_related_list($id, $cur_tab_id, $rel_tab_id, $actions, 'direct');
	}
	// crmv@125816e
	
	/**
	 * Default (generic) function to handle the dependents list for the module.
	 * NOTE: UI type '10' is used to stored the references to other modules for a given record.
	 * These dependent records can be retrieved through this function.
	 * For eg: A trouble ticket can be related to an Account or a Contact.
	 * From a given Contact/Account if we need to fetch all such dependent trouble tickets, get_dependents_list function can be used.
	 */
	function get_dependents_list($id, $cur_tab_id, $rel_tab_id, $actions=false) {

		global $currentModule, $app_strings, $current_user, $adb, $table_prefix;//crmv@203484 removed global singlepane

		$parenttab = getParentTab();

		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e

		$related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance($related_module);

		// Some standard module class doesn't have required variables
		// that are used in the query, they are defined in this generic API
		vtlib_setup_modulevars($currentModule, $this);
		vtlib_setup_modulevars($related_module, $other);

		$button = '';

		// To make the edit or del link actions to return back to same view.
		if($singlepane_view == true) $returnset = "&return_module=$currentModule&return_action=DetailView&return_id=$id";//crmv@203484 changed to normal bool true, not string 'true'
		else $returnset = "&return_module=$currentModule&return_action=CallRelatedList&return_id=$id";

		$return_value = null;
		//crmv@47905 crmv@129050
		$dependentFieldSql = $this->db->pquery("SELECT f.tabid,f.fieldname,f.columnname,f.tablename
			FROM {$table_prefix}_field f
			INNER JOIN {$table_prefix}_fieldmodulerel fr ON fr.fieldid = f.fieldid
			WHERE f.uitype = '10' AND fr.relmodule = ? AND fr.module = ?", array($currentModule, $related_module));
		$numOfFields = $this->db->num_rows($dependentFieldSql);
		//crmv@47905e crmv@129050e
		(isInventoryModule($related_module) && isProductModule($currentModule)) ? $inventoryModule = true : $inventoryModule = false;	//crmv@47104 crmv@64542
		if($numOfFields > 0 || $inventoryModule) {	
			$dependentColumn = $this->db->query_result_no_html($dependentFieldSql, 0, 'columnname');
			$dependentField = $this->db->query_result_no_html($dependentFieldSql, 0, 'fieldname');
			$dependentTable = $this->db->query_result_no_html($dependentFieldSql, 0, 'tablename');	// crmv@129050

			$button .= '<input type="hidden" name="'.$dependentColumn.'" id="'.$dependentColumn.'" value="'.$id.'">';
			$button .= '<input type="hidden" name="'.$dependentColumn.'_type" id="'.$dependentColumn.'_type" value="'.$currentModule.'">';
			if($actions) {
				$button .= $this->get_related_buttons($currentModule, $id, $related_module, $actions, $dependentField); // crmv@43864
			}

			if ($adb->isOracle()) {
				$query = "SELECT *"; //crmv@36534
			} else {
				$query = "SELECT ".$table_prefix."_crmentity.*, $other->table_name.*";
				$query .= ", CASE WHEN (".$table_prefix."_users.user_name is not null) THEN ".$table_prefix."_users.user_name ELSE ".$table_prefix."_groups.groupname END AS user_name";
			}

			$more_relation = '';
			if(!empty($other->related_tables)) {
				foreach($other->related_tables as $tname=>$relmap) {
					if ($tname == $this->table_name) continue; // crmv@43864
					$query .= ", $tname.*";

					// Setup the default JOIN conditions if not specified
					if(empty($relmap[1])) $relmap[1] = $other->table_name;
					if(empty($relmap[2])) $relmap[2] = $relmap[0];
					$more_relation .= " LEFT JOIN $tname ON $tname.$relmap[0] = $relmap[1].$relmap[2]";
				}
			}
			
			//crmv@185647
			$use_crmentity = true;
			$entity_table = $table_prefix.'_crmentity';
			if (!in_array($table_prefix.'_crmentity',$other->tab_name)) {
				$use_crmentity = false;
				$entity_table = $other->table_name;
			}
			//crmv@185647e

			$query .= " FROM $other->table_name";
			if ($use_crmentity) $query .= " INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = $other->table_name.$other->table_index"; //crmv@185647
			//crmv@129050
			if(!$inventoryModule && $dependentTable != $other->table_name){	//crmv@147716
				$query .= " INNER JOIN ".$dependentTable." ON $other->table_name.$other->table_index = ".$dependentTable.".".$other->tab_name_index[$dependentTable];
			}
			//crmv@24527
			if (!empty($other->customFieldTable) && $other->customFieldTable[0] != $dependentTable) {
				$query .= " INNER JOIN ".$other->customFieldTable[0]." ON $other->table_name.$other->table_index = ".$other->customFieldTable[0].".".$other->customFieldTable[1];
			}
			//crmv@24527e crmv@129050e
			
			// crmv@171613
			if (is_array($other->tab_name)) {
				foreach($other->tab_name as $tab_name){
					if (stripos($query,"join ".$tab_name) === false && stripos($query,"from ".$tab_name) === false && stripos($more_relation,"join ".$tab_name) === false) { // crmv@167298 crmv@171367
						$query .= ' '.$other->getJoinClause($tab_name)." ".$tab_name." ON ".$tab_name.".".$other->tab_name_index[$tab_name]." = $other->table_name.$other->table_index"; //crmv@175762
					}
				}
			}
			// crmv@171613e
			
			if ($inventoryModule) {
				$aliasTable = $this->table_name; // crmv@104465
				$query .= " INNER  JOIN {$table_prefix}_inventoryproductrel ON {$table_prefix}_inventoryproductrel.id = $other->table_name.$other->table_index";
				$query .= " INNER  JOIN $this->table_name ON $this->table_name.$this->table_index = {$table_prefix}_inventoryproductrel.productid";
			} else {
				//crmv@102767
				$aliasTable = ($this->table_name == $other->table_name) ? $this->table_name.'2' : $this->table_name;
				$query .= " INNER  JOIN $this->table_name $aliasTable ON $aliasTable.$this->table_index = $dependentTable.$dependentColumn"; // crmv@129050
				//crmv@102767e
			}
			$query .= $more_relation;
			$query .= " LEFT  JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$entity_table.".smownerid"; //crmv@185647
			if (!empty($other->groupTable) ){
				$query .= "	LEFT JOIN ".$other->groupTable[0]."
					ON ".$other->groupTable[0].".".$other->groupTable[1]." = $other->table_name.$other->table_index ";
				$query .= "	LEFT JOIN ".$table_prefix."_groups
					ON ".$other->groupTable[0].".groupname = ".$table_prefix."_groups.groupname ";
			}
			else {
				$query .= " LEFT  JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$entity_table.".smownerid"; //crmv@185647
			}
			// crmv@64325 crmv@185647
			$setypeCond = '';
			if (PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED') && $use_crmentity) {
				$setypeCond = "AND {$table_prefix}_crmentity.setype = '$related_module'";
			}
			$query .= " WHERE ".$entity_table.".deleted = 0 $setypeCond AND $aliasTable.$this->table_index = $id";	//crmv@102767
			// crmv@64325e crmv@185647e
			// crmv@164120 - removed code

			$return_value = GetRelatedList($currentModule, $related_module, $other, $query, $button, $returnset);
		}
		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		return $return_value;
	}
	function get_documents_dependents_list($id, $cur_tab_id, $rel_tab_id, $actions=false) {

		global $currentModule, $app_strings, $current_user,$table_prefix;//crmv@203484 removed global singlepane

		$parenttab = getParentTab();

		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e

		$related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance($related_module);

		// Some standard module class doesn't have required variables
		// that are used in the query, they are defined in this generic API
		vtlib_setup_modulevars($currentModule, $this);
		vtlib_setup_modulevars($related_module, $other);

		$button = '';

		// To make the edit or del link actions to return back to same view.
		if($singlepane_view == true) $returnset = "&return_module=$currentModule&return_action=DetailView&return_id=$id";//crmv@203484 changed to normal bool true, not string 'true'
		else $returnset = "&return_module=$currentModule&return_action=CallRelatedList&return_id=$id";

		$return_value = null;
		if($actions) {
			$button .= $this->get_related_buttons($currentModule, $id, $related_module, $actions, $dependentField); // crmv@43864
		}

		$query = "SELECT ".$table_prefix."_crmentity.*, $other->table_name.*";

		$query .= ", CASE WHEN (".$table_prefix."_users.user_name is not null) THEN ".$table_prefix."_users.user_name ELSE ".$table_prefix."_groups.groupname END AS user_name";

		$more_relation = '';
		if(!empty($other->related_tables)) {
			foreach($other->related_tables as $tname=>$relmap) {
				$query .= ", $tname.*";

				// Setup the default JOIN conditions if not specified
				if(empty($relmap[1])) $relmap[1] = $other->table_name;
				if(empty($relmap[2])) $relmap[2] = $relmap[0];
				$more_relation .= " LEFT JOIN $tname ON $tname.$relmap[0] = $relmap[1].$relmap[2]";
			}
		}

		$query .= " FROM $other->table_name";
		$query .= " INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = $other->table_name.$other->table_index";
		//crmv@24527
		if (!empty($other->customFieldTable)) {
			$query .= " INNER JOIN ".$other->customFieldTable[0]." ON $other->table_name.$other->table_index = ".$other->customFieldTable[0].".".$other->customFieldTable[1];
		}
		//crmv@24527e
		$query .= " INNER JOIN ".$table_prefix."_senotesrel ON ".$table_prefix."_senotesrel.crmid = $other->table_name.$other->table_index";
		$query .= " INNER JOIN $this->table_name ON $this->table_name.$this->table_index = ".$table_prefix."_senotesrel.notesid";
		$query .= $more_relation;
		$query .= " LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid";
		if (!empty($other->groupTable) ){
			$query .= "	LEFT JOIN ".$other->groupTable[0]."
				ON ".$other->groupTable[0].".".$other->groupTable[1]." = $other->table_name.$other->table_index ";
			$query .= "	LEFT JOIN ".$table_prefix."_groups
				ON ".$other->groupTable[0].".groupname = ".$table_prefix."_groups.groupname ";
		}
		else {
			$query .= " LEFT  JOIN ".$table_prefix."_groups       ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid";
		}

		// crmv@64325
		$setypeCond = '';
		if (PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) {
			$setypeCond = "AND {$table_prefix}_crmentity.setype = '$related_module'";
		}
		$query .= " WHERE ".$table_prefix."_crmentity.deleted = 0 $setypeCond AND $this->table_name.$this->table_index = $id";
		// crmv@64325e


		$return_value = GetRelatedList($currentModule, $related_module, $other, $query, $button, $returnset);
		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		return $return_value;
	}

	// crmv@97237
	/*
	 * Function to get the primary query part of a report for which generateReportsQuery Doesnt exist in module
	 * @param - $module Primary module name
	 * returns the query string formed on fetching the related data for report for primary module
	 */
	function generateReportsQuery($module, $reportid = 0, $joinProducts = false, $joinUitype10 = true) { // crmv@63349	crmv@131239
		global $adb,$table_prefix;
		$primary = CRMEntity::getInstance($module);

		vtlib_setup_modulevars($module, $primary);
		$moduletable = $primary->table_name;
		$moduleindex = $primary->table_index;
		
		$modTables = $totalTables = $prodTables = "";
		if (is_array($primary->tab_name_index)) {
			$noJoin = array(
				$moduletable, $table_prefix.'_crmentity', 
				$table_prefix.'_ticketcomments', $table_prefix.'_faqcomments',
				$table_prefix.'_seproductsrel',  $table_prefix.'_salesmanactivityrel', $table_prefix.'_recurringevents'
			);
			$leftJoin = array(
				$table_prefix.'_invoice_recurring_info', $table_prefix.'_producttaxrel', 
				$table_prefix.'_seactivityrel', $table_prefix.'_cntactivityrel', $table_prefix.'_activity_reminder'
			);
			foreach ($primary->tab_name_index as $table => $index) {
				if (!in_array($table, $noJoin)) {
					$joinType = 'INNER';
					if (in_array($table, $leftJoin)) $joinType = 'LEFT';
					$modTables .= "$joinType JOIN $table ON $table.$index = $moduletable.$moduleindex ";
				}
			}
		}
		
		/*if ($joinProducts && isInventoryModule($module)) {
			$prodTables = $this->getInventoryRelQuery($module);
		}*/
		
		if (isInventoryModule($module)) {
			$totalAlias = substr($table_prefix.'_inventorytotals'.$module,0,29);
			$totalTables = 
				"LEFT JOIN {$table_prefix}_inventoryshippingrel on {$table_prefix}_inventoryshippingrel.id = $moduletable.$moduleindex
				LEFT JOIN {$table_prefix}_inventorytotals $totalAlias on $totalAlias.id = $moduletable.$moduleindex "; // crmv@67929	crmv@73751
		}
		
		$name = substr($table_prefix."_groups$module",0,29);	//crmv@16818
		$name2 = substr($table_prefix."_users$module",0,29);	//crmv@16818
		$query = "from $moduletable
			$modTables
	        inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=$moduletable.$moduleindex
			left join ".$table_prefix."_groups $name on $name.groupid = ".$table_prefix."_crmentity.smownerid
            left join ".$table_prefix."_users $name2 on $name2.id = ".$table_prefix."_crmentity.smownerid
			left join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
            left join ".$table_prefix."_users on ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
			$totalTables
            $prodTables";

        // join with uitype10 fields
		if ($joinUitype10) {	//crmv@131239
			$fields_query = $adb->pquery("SELECT ".$table_prefix."_field.columnname,".$table_prefix."_field.tablename,".$table_prefix."_field.fieldid from ".$table_prefix."_field INNER JOIN ".$table_prefix."_tab on ".$table_prefix."_tab.name = ? WHERE ".$table_prefix."_tab.tabid=".$table_prefix."_field.tabid AND ".$table_prefix."_field.uitype IN (10) and ".$table_prefix."_field.presence in (0,2)",array($module));
			if($adb->num_rows($fields_query)>0){
				for($i=0;$i<$adb->num_rows($fields_query);$i++){
					$field_name = $adb->query_result($fields_query,$i,'columnname');
					$field_id = $adb->query_result($fields_query,$i,'fieldid');
					$tab_name = $adb->query_result($fields_query,$i,'tablename');
					$ui10_modules_query = $adb->pquery("SELECT relmodule FROM ".$table_prefix."_fieldmodulerel WHERE fieldid=?",array($field_id));
	
				if($adb->num_rows($ui10_modules_query)>0){
						//crmv@16312	//crmv@16818
						$name3 = substr($table_prefix."_crmentityRel".$module[0]."$field_id",0,29);
						//crmv@16312 end	//crmv@16818e
						$query.= " left join ".$table_prefix."_crmentity $name3 on $name3.crmid = $tab_name.$field_name and $name3.deleted=0";
						for($j=0;$j<$adb->num_rows($ui10_modules_query);$j++){
							$rel_mod = $adb->query_result($ui10_modules_query,$j,'relmodule');
							$rel_obj = CRMEntity::getInstance($rel_mod);
							vtlib_setup_modulevars($rel_mod, $rel_obj);
	
							$rel_tab_name = $rel_obj->table_name;
							$rel_tab_index = $rel_obj->table_index;
							//crmv@57653
							$tablealias = self::createRelAlias($rel_tab_name, $module, $field_id);
	
							//crmv@57653e
							$query.= " left join $rel_tab_name $tablealias on $tablealias.$rel_tab_index = $name3.crmid";
						}
					}
				}
			}
		}	//crmv@131239
		
		// join with other owner fields
		$res = $adb->pquery(
			"SELECT ".$table_prefix."_field.columnname,".$table_prefix."_field.tablename,".$table_prefix."_field.fieldid 
			from ".$table_prefix."_field 
			INNER JOIN ".$table_prefix."_tab on ".$table_prefix."_tab.name = ? 
			WHERE ".$table_prefix."_tab.tabid=".$table_prefix."_field.tabid AND ".$table_prefix."_field.uitype IN (52,53,77) and ".$table_prefix."_field.presence in (0,2)
			and fieldname NOT IN (?)",
			array($module, 'assigned_user_id')
		);
		if($adb->num_rows($res)>0){
			$rel_mod = 'Users';
			$tabs = array(
				$table_prefix.'_users' => 'id',
				$table_prefix.'_groups' => 'groupid',
			);
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$tab_name = $row['tablename'];
				$col_name = $row['columnname'];
				
				foreach ($tabs as $tab => $tabidx) {
					$tablealias = self::createRelAlias($tab, $module, $row['fieldid']);
					$query .= " left join $tab $tablealias on $tablealias.$tabidx = $tab_name.$col_name";
				}
			}
		}
		return $query;
	}
	
	/**
	 * Create an alias for a relation table, ensuring that the lenght does not exceet 30 chars
	 */
	static function createRelAlias($table, $secmod = null, $fieldid = null) {
		global $adb, $table_prefix;
		
		($adb->isMysql()) ? $maxlen = 60 : $maxlen = 30;	//crmv@126096
		$glue = "Rel";
		
		$needed = strlen($glue)+($fieldid ? strlen($fieldid) : 0);
		$maxtlen = (int)(($maxlen-$needed)/2);
		
		$alias = $table;
		
		// remove table prefix
		if (substr($table, 0, strlen($table_prefix)) == $table_prefix) {
			$alias = substr($table, strlen($table_prefix)+1);
		}
		if ($secmod) {
			$alias = substr($alias, 0, $maxtlen);
			$alias .= $glue.substr($secmod, 0, $maxtlen);
			if ($fieldid) {
				$alias .= $fieldid;
			}
		} else {
			if (strlen($alias) > $maxlen) {
				$alias = substr($alias, 0, $maxlen);
			}
		}
		return $alias;
	}
	// crmv@97237e

	/*
	 * Function to get the secondary query part of a report for which generateReportsSecQuery Doesnt exist in module
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	function generateReportsSecQuery($module,$secmodule,$reporttype='',$useProductJoin=true,$joinUitype10=true){	//crmv@131239
		global $adb, $table_prefix;
		$secondary = CRMEntity::getInstance($secmodule);

		vtlib_setup_modulevars($secmodule, $secondary);

		$tablename = $secondary->table_name;
		$tableindex = $secondary->table_index;
		$modulecftable = $secondary->customFieldTable[0];
		$modulecfindex = $secondary->customFieldTable[1];
		
		// crmv@198362
		$modTables = "";
		if (is_array($secondary->tab_name_index)) {
			$noJoin = array(
				$tablename, $table_prefix.'_crmentity', $modulecftable,
				$table_prefix.'_ticketcomments', $table_prefix.'_faqcomments',
				$table_prefix.'_seproductsrel',  $table_prefix.'_salesmanactivityrel', $table_prefix.'_recurringevents'
			);
			$leftJoin = array(
				$table_prefix.'_invoice_recurring_info', $table_prefix.'_producttaxrel', 
				$table_prefix.'_seactivityrel', $table_prefix.'_cntactivityrel', $table_prefix.'_activity_reminder'
			);
			foreach ($secondary->tab_name_index as $table => $index) {
				if (!in_array($table, $noJoin)) {
					$joinType = 'INNER';
					if (in_array($table, $leftJoin)) $joinType = 'LEFT';
					$modTables .= "$joinType JOIN $table ON $table.$index = $tablename.$tableindex ";
				}
			}
		}
		// crmv@198362e

		if(isset($modulecftable)){
			$cfquery = "left join $modulecftable $modulecftable on $modulecftable.$modulecfindex=$tablename.$tableindex";
		} else {
			$cfquery = '';
		}
		$query = $this->getRelationQuery($module,$secmodule,"$tablename","$tableindex");
		$name = substr($table_prefix."_crmentity$secmodule",0,29);
		$name2 = substr($table_prefix."_groups$secmodule",0,29);
		$name3 = substr($table_prefix."_users$secmodule",0,29);

		// crmv@38798
		// join con crmentity (compatibility fix)
		if (stripos($query, "left join {$table_prefix}_crmentity $name") === false) {
			$query .= "	left join {$table_prefix}_crmentity $name on $name.crmid = $tablename.$tableindex AND $name.deleted=0";
		}
		$query .= "	$cfquery
					left join ".$table_prefix."_groups $name2 on $name2.groupid = $name.smownerid
		            left join ".$table_prefix."_users $name3 on $name3.id = $name.smownerid";
		// crmv@38798e

		if ($joinUitype10) {	//crmv@131239
			$fields_query = $adb->pquery("SELECT ".$table_prefix."_field.columnname,".$table_prefix."_field.tablename,".$table_prefix."_field.fieldid from ".$table_prefix."_field INNER JOIN ".$table_prefix."_tab on ".$table_prefix."_tab.name = ? WHERE ".$table_prefix."_tab.tabid=".$table_prefix."_field.tabid AND ".$table_prefix."_field.uitype IN (10) and ".$table_prefix."_field.presence in (0,2)",array($secmodule));
	       if($adb->num_rows($fields_query)>0){
		        for($i=0;$i<$adb->num_rows($fields_query);$i++){
		        	$field_name = $adb->query_result($fields_query,$i,'columnname');
		        	$field_id = $adb->query_result($fields_query,$i,'fieldid');
		        	$tab_name = $adb->query_result($fields_query,$i,'tablename');
			        $ui10_modules_query = $adb->pquery("SELECT relmodule FROM ".$table_prefix."_fieldmodulerel WHERE fieldid=?",array($field_id));
	
					if($adb->num_rows($ui10_modules_query)>0){
			       		$name = substr($table_prefix."_crmentityRel$secmodule",0,29-strlen($i)).$i;	// crmv@30385 crmv@121000
				        $query.= " left join ".$table_prefix."_crmentity $name on $name.crmid = $tab_name.$field_name and $name.deleted=0";
				        for($j=0;$j<$adb->num_rows($ui10_modules_query);$j++){
				        	$rel_mod = $adb->query_result($ui10_modules_query,$j,'relmodule');
				        	$rel_obj = CRMEntity::getInstance($rel_mod);
				        	vtlib_setup_modulevars($rel_mod, $rel_obj);
	
							$rel_tab_name = $rel_obj->table_name;
							$rel_tab_index = $rel_obj->table_index;
							$name2 = substr($rel_tab_name."Rel$secmodule",0,40) . $i; // crmv@203132
					        $query.= " left join $rel_tab_name $name2 on $name2.$rel_tab_index = $name.crmid";
				        }
			       }
		        }
	        }
		}	//crmv@131239
		return $query;
	}

	/*
	 * Function to get the security query part of a report
	 * @param - $module primary module name
	 * returns the query string formed on fetching the related data for report for security of the module
	 */
	function getListViewSecurityParameter($module){
		$tabid=getTabid($module);
		global $current_user,$table_prefix;
		if($current_user) {
	        require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	        require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
		}
		$sec_query .= " and (".$table_prefix."_crmentity.smownerid in($current_user->id) or ".$table_prefix."_crmentity.smownerid in(select ".$table_prefix."_user2role.userid from ".$table_prefix."_user2role inner join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_user2role.userid inner join ".$table_prefix."_role on ".$table_prefix."_role.roleid=".$table_prefix."_user2role.roleid where ".$table_prefix."_role.parentrole like '".$current_user_parent_role_seq."::%') or ".$table_prefix."_crmentity.smownerid in(select shareduserid from ".$table_prefix."_tmp_read_u_per where userid=".$current_user->id." and tabid=".$tabid.") or (";

        if(is_array($current_user_groups) && sizeof($current_user_groups) > 0) // crmv@206281
        {
              $sec_query .= $table_prefix."_groups.groupid in (". implode(",", $current_user_groups) .") or ";
        }
        $sec_query .= $table_prefix."_groups.groupid in(select ".$table_prefix."_tmp_read_g_per.sharedgroupid from ".$table_prefix."_tmp_read_g_per where userid=".$current_user->id." and tabid=".$tabid."))) ";

        $sec_query.= $this->getListViewAdvSecurityParameter_list($module);
	}

	/*
	 * Function to get the security query part of a report
	 * @param - $module primary module name
	 * returns the query string formed on fetching the related data for report for security of the module
	 */
	function getSecListViewSecurityParameter($module){
		$tabid=getTabid($module);
		global $current_user,$table_prefix;
		if($current_user) {
	        require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	        require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
		}
		$sec_query .= " and (".$table_prefix."_crmentity$module.smownerid in($current_user->id) or ".$table_prefix."_crmentity$module.smownerid in(select ".$table_prefix."_user2role.userid from ".$table_prefix."_user2role inner join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_user2role.userid inner join ".$table_prefix."_role on ".$table_prefix."_role.roleid=".$table_prefix."_user2role.roleid where ".$table_prefix."_role.parentrole like '".$current_user_parent_role_seq."::%') or ".$table_prefix."_crmentity$module.smownerid in(select shareduserid from ".$table_prefix."_tmp_read_u_per where userid=".$current_user->id." and tabid=".$tabid.") or (";

        if(is_array($current_user_groups) && sizeof($current_user_groups) > 0) // crmv@206281
        {
              $sec_query .= $table_prefix."_groups$module.groupid in (". implode(",", $current_user_groups) .") or ";
        }
        $sec_query .= $table_prefix."_groups$module.groupid in(select ".$table_prefix."_tmp_read_g_per.sharedgroupid from ".$table_prefix."_tmp_read_g_per where userid=".$current_user->id." and tabid=".$tabid.")) ";
	}

	/*
	 * Function to get the relation query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on relating the primary module and secondary module
	 */
	function getRelationQuery($module,$secmodule,$table_name,$column_name){
		global $table_prefix, $current_user;
		$tab = getRelationTables($module,$secmodule);
		foreach($tab as $key=>$value){
			$tables[]=$key;
			$fields[] = $value;
		}
		//crmv@38798
		$tabname = $tables[0];
		$prifieldname = $fields[0][0];
		$secfieldname = $fields[0][1];
		$primodname = $fields[0][2];
		$secmodname = $fields[0][3];

		$tmpname = substr($tabname."tmp".$secmodule, 0, 29); //crmv@oracle fix object name > 30 characters
		$crmentitySec = substr("{$table_prefix}_crmentity{$secmodule}", 0, 29);

		$condition = "";
		if(!empty($tables[1]) && !empty($fields[1])){
			$condvalue = $tables[1].".".$fields[1];
		} else {
			$condvalue = $tabname.".".$prifieldname;
		}

		if (empty($secmodname)) {
			$condrelmodname = '';
			$condrelmodname_rev = '';
		} else {
			$condrelmodname = " AND {$tmpname}.{$secmodname} = '$secmodule'";
			$condrelmodname_rev = " AND {$tmpname}.{$secmodname} = '$module'";
			if (!empty($primodname)) {
				$condrelmodname .= " AND {$tmpname}.{$primodname} = '$module'";
				$condrelmodname_rev .= " AND {$tmpname}.{$primodname} = '$secmodule'";
			}
		}
		$condition = "{$tmpname}.{$prifieldname} = {$condvalue} {$condrelmodname}";
		if ($condrelmodname_rev) {
			$condition_rev = "{$tmpname}.{$secfieldname} = {$condvalue} {$condrelmodname_rev}";
		} else {
			$condition_rev = '';
		}

		// 1. join with relation table
		// 2. join with crmentity (to filter out deleted records)
		// 3. join with main table

		if ($tabname == $table_prefix.'_crmentityrel' ) { //crmv@18829
			if ($condition_rev) {
				//crmv@46974 crmv@63349
				$tmodreltables = TmpUserModRelTables::getInstance();
				$tabname = $this->setupTemporaryRelTable($module,$secmodule);
				if ($tabname == $tmodreltables->tmpTable) {
					$condition = $tmodreltables->getJoinCondition($module, $secmodule, $current_user->id, 0, $condvalue, null, $tmpname);
				} else {
					$condition = "{$tmpname}.{$prifieldname} = {$condvalue}";
				}
				//crmv@46974e crmv@63349e
			} else {
				$condition = "($condition)";
			}

			$condition_secmod_table_pri = "{$crmentitySec}.crmid = {$tmpname}.{$secfieldname} {$condrelmodname}";

			if ($condrelmodname_rev) {
				//crmv@46974				
				$condition_crmentity = "{$crmentitySec}.crmid = {$tmpname}.{$secfieldname}";
				//crmv@46974 e
			} else {
				$condition_crmentity = "({$condition_secmod_table_pri})";
			}
		} else {
			$condition_crmentity = "{$crmentitySec}.crmid = {$tmpname}.{$secfieldname}";
		}
		
		// crmv@64325
		$setypeCond = '';
		if (PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) {
			$setypeCond = "AND {$crmentitySec}.setype = '$secmodule'";
		}

		$query = " LEFT JOIN {$tabname} {$tmpname} ON {$condition}";
		$query .= " LEFT JOIN {$table_prefix}_crmentity {$crmentitySec} ON {$condition_crmentity} AND {$crmentitySec}.deleted = 0 $setypeCond";
		$query .= " LEFT JOIN {$table_name} ON {$crmentitySec}.crmid = {$table_name}.{$column_name}";
		// crmv@64325e

		//crmv@38798e
		return $query;
	}
	/** END **/

	/**
	 * This function handles the import for uitype 10 fieldtype
	 * @param string $module - the current module name
	 * @param string fieldname - the related to field name
	 */
	function add_related_to($module, $fieldname){
		global $adb, $imported_ids, $current_user,$table_prefix;

		$related_to = $this->column_fields[$fieldname];

		if(empty($related_to)){
			return false;
		}

		//check if the field has module information; if not get the first module
		if(!strpos($related_to, "::::")){
			$module = getFirstModule($module, $fieldname);
			$value = $related_to;
		}else{
			//check the module of the field
			$arr = array();
			$arr = explode("::::", $related_to);
			$module = $arr[0];
			$value = $arr[1];
		}

		$focus1 = CRMEntity::getInstance($module);

		// crmv@144125
		$ENU = EntityNameUtils::getInstance();
		$entityNameArr = $ENU->getEntityField($module);
		// crmv@144125e
		
		$entityName = $entityNameArr['fieldname'];
		$query = "SELECT ".$table_prefix."_crmentity.deleted, $focus1->table_name.*
					FROM $focus1->table_name
					INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=$focus1->table_name.$focus1->table_index
						where $entityName=? and ".$table_prefix."_crmentity.deleted=0";
		$result = $adb->pquery($query, array($value));

		if(!isset($this->checkFlagArr[$module])){
			$this->checkFlagArr[$module] = (isPermitted($module,'EditView','') == 'yes');
		}

		if($adb->num_rows($result)>0){
			//record found
			$focus1->id = $adb->query_result($result, 0, $focus1->table_index);
		}elseif($this->checkFlagArr[$module]){
			//record not found; create it
	        $focus1->column_fields[$focus1->list_link_field] = $value;
	        $focus1->column_fields['assigned_user_id'] = $current_user->id;
	        $focus1->column_fields['modified_user_id'] = $current_user->id;
			$focus1->save($module);

    		$last_import = new UsersLastImport();
    		$last_import->assigned_user_id = $current_user->id;
    		$last_import->bean_type = $module;
    		$last_import->bean_id = $focus1->id;
    		$last_import->save();
		}else{
			//record not found and cannot create
			$this->column_fields[$fieldname] = "";
			return false;
		}
		if(!empty($focus1->id)){
			$this->column_fields[$fieldname] = $focus1->id;
			return true;
		}else{
			$this->column_fields[$fieldname] = "";
			return false;
		}
    }
	/**
	 * To keep track of action of field filtering and avoiding doing more than once.
	 *
	 * @var Array
	 */
	protected $__inactive_fields_filtered = false;

	/**
	 * Filter in-active fields based on type
	 *
	 * @param String $module
	 */
	function filterInactiveFields($module) {
		if($this->__inactive_fields_filtered) {
			return;
		}

		global $adb, $mod_strings;

		// Look for fields that has presence value NOT IN (0,2)
		$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module, array('1'));
		if($cachedModuleFields === false) {
			// Initialize the fields calling suitable API
			getColumnFields($module);
			$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module, array('1'));
		}

		$hiddenFields = array();

		if($cachedModuleFields) {
			foreach($cachedModuleFields as $fieldinfo) {
				$fieldLabel= $fieldinfo['fieldlabel'];
				// NOTE: We should not translate the label to enable field diff based on it down
				$fieldName = $fieldinfo['fieldname'];
				$tableName = str_replace($table_prefix."_","",$fieldinfo['tablename']);
				$hiddenFields[$fieldLabel] = array($tableName=>$fieldName);
			}
		}

		if(isset($this->list_fields)) {
			$this->list_fields   = array_diff_assoc($this->list_fields, $hiddenFields);
		}

		if(isset($this->search_fields)) {
			$this->search_fields = array_diff_assoc($this->search_fields, $hiddenFields);
		}

		// To avoid re-initializing everytime.
		$this->__inactive_fields_filtered = true;
	}
	/** END **/

	//crmv@18744
	/**
	 * For Record View Notification
	 */
	function isViewed($crmid=false) {
		if(!$crmid) { $crmid = $this->id; }
		if($crmid) {
			global $adb,$table_prefix;
			$result = $adb->pquery("SELECT viewedtime,modifiedtime,smcreatorid,smownerid,modifiedby FROM ".$table_prefix."_crmentity WHERE crmid=?", Array($crmid));
			$resinfo = $adb->fetch_array($result);

			$lastviewed = $resinfo['viewedtime'];
			$modifiedon = $resinfo['modifiedtime'];
			$smownerid   = $resinfo['smownerid'];
			$smcreatorid = $resinfo['smcreatorid'];
			$modifiedby = $resinfo['modifiedby'];

			if($modifiedby == '0' && ($smownerid == $smcreatorid)) {
				/** When module record is created **/
				return true;
			} else if($smownerid == $modifiedby) {
				/** Owner and Modifier as same. **/
				return true;
			} else if($lastviewed && $modifiedon) {
				/** Lastviewed and Modified time is available. */
				if($this->__timediff($modifiedon, $lastviewed) > 0) return true;
			}
		}
		return false;
	}

	function __timediff($d1, $d2) {
		list($t1_1, $t1_2) = explode(' ', $d1);
		list($t1_y, $t1_m, $t1_d) = explode('-', $t1_1);
		list($t1_h, $t1_i, $t1_s) = explode(':', $t1_2);

		$t1 = mktime($t1_h, $t1_i, $t1_s, $t1_m, $t1_d, $t1_y);

		list($t2_1, $t2_2) = explode(' ', $d2);
		list($t2_y, $t2_m, $t2_d) = explode('-', $t2_1);
		list($t2_h, $t2_i, $t2_s) = explode(':', $t2_2);

		$t2 = mktime($t2_h, $t2_i, $t2_s, $t2_m, $t2_d, $t2_y);

		if( $t1 == $t2 ) return 0;
		return $t2 - $t1;
	}
	//crmv@18744e

	function markAsViewed($userid) {
		global $adb,$table_prefix;
		$adb->pquery("UPDATE ".$table_prefix."_crmentity set viewedtime=? WHERE crmid=? AND smownerid=? and setype=?",
			Array( date('Y-m-d H:i:s'), (int)$this->id, $userid,$this->modulename));
	}

	/**
	 * Save the related module record information. Triggered from CRMEntity->saveentity method or updateRelations.php
	 * @param String This module name
	 * @param Integer This module record number
	 * @param String Related module name
	 * @param mixed Integer or Array of related module record number
	 */
	//crmv@38798 crmv@44187
	function save_related_module($module, $crmid, $with_module, $with_crmid, $skip_check=false) {
		global $adb, $table_prefix;

		//crmv@35074
		if (!$skip_check) {
			$tabId = getTabid($module);
			$relTabId = getTabid($with_module);

			$result = $adb->pquery("SELECT relation_id FROM {$table_prefix}_relatedlists WHERE (tabid = ? AND related_tabid = ?) OR (tabid = ? AND related_tabid = ?)",array($tabId,$relTabId,$relTabId,$tabId)); // crmv@125816
			//if there are two related lists -> N -> N relation (or 1 list if same module)
			if ($result && ($adb->num_rows($result) <= 1) && ($tabId != $relTabId))  return false; // crmv@43611
		}
		//crmv@35074e

		// Documents and Products have different tables
		if ($with_module == 'Documents' || $module == 'Documents') {
			$relatedInstance = CRMEntity::getInstance('Documents');
		} elseif ($with_module == 'Products' || $module == 'Products') {
			$relatedInstance = CRMEntity::getInstance('Products');
		} else {
			$relatedInstance = null;
		}

		$reltab = $table_prefix.'_crmentityrel';
		$relidx = 'crmid';
		$relidx2 = 'relcrmid';
		$relmod1 = 'module';
		$relmod2 = 'relmodule';

		if (!empty($relatedInstance)) {
			$relationTab = $relatedInstance->relation_table;
			$relationId = $relatedInstance->relation_table_id;
			$relationIdOther = $relatedInstance->relation_table_otherid;
			$relationModule = $relatedInstance->relation_table_othermodule;

			if ($relatedInstance->modulename == $this->modulename) {
				$revertedIds = false;
			} else {
				$revertedIds = true;
			}
		} elseif (!empty($this->extra_relation_tables[$with_module])) {
			$reltab = $this->extra_relation_tables[$with_module]['relation_table'];
			$relidx = $this->extra_relation_tables[$with_module]['relation_table_id'];
			$relidx2 = $this->extra_relation_tables[$with_module]['relation_table_otherid'];
			$relmod1 = $this->extra_relation_tables[$with_module]['relation_table_module'];
			$relmod2 = $this->extra_relation_tables[$with_module]['relation_table_othermodule'];
		}
		
		// crmv@125816
		// ordered N-N for targets - link only children
		// TODO: generalize for other modules
		if ($with_module == $module && $module == 'Targets') {
			$revertedIds = false;
			$reltab = $this->relation_table_ord;
		}
		// crmv@125816e

		if(!is_array($with_crmid)) $with_crmid = Array($with_crmid);
		foreach($with_crmid as $relcrmid) {
			if (empty($relcrmid) || $crmid == $relcrmid) continue;	//crmv@24862
			//crmv@29617 crmv@33465
			if (isModuleInstalled('ModNotifications')) {
				$obj = ModNotifications::getInstance(); // crmv@164122
				$obj->saveRelatedModuleNotification($crmid, $module, $relcrmid, $with_module);
			}
			//crmv@29617e crmv@33465e
			// use the new relation variables
			// TODO: use new system with extra_relation_tables
			if (!empty($relatedInstance)) {
				if ($revertedIds) {
					$thisId = $relcrmid;
					$relatedId = $crmid;
					$relatedModule = $module;
				} else {
					$thisId = $crmid;
					$relatedId = $relcrmid;
					$relatedModule = $with_module;
				}

				$checkpresence = $adb->pquery("SELECT $relationId FROM $relationTab WHERE $relationId = ? AND $relationIdOther = ?", Array($thisId, $relatedId));
				if ($checkpresence && $adb->num_rows($checkpresence) == 0) {
					$adb->pquery("INSERT INTO $relationTab ($relationId, $relationIdOther, $relationModule) VALUES(?,?,?)", array($thisId, $relatedId, $relatedModule));
					$adb->pquery("UPDATE {$this->getEntityTableInfo('table')} SET modifiedtime = ? WHERE {$this->getEntityTableInfo('index')} IN (?,?)", array($adb->formatDate(date('Y-m-d H:i:s'), true), intval($thisId), intval($relatedId))); // crmv@49398 crmv@56655 crmv@69690 crmv@185647
				}
			// use standard crmentityrel
			} else {
				$presenceQuery = "SELECT $relidx FROM $reltab WHERE $relidx = ? AND $relidx2 = ?";
				$presenceParams = array($crmid, $relcrmid);
				if (!empty($relmod1)) {
					$presenceQuery .= " AND $relmod1 = ?";
					$presenceParams[] = $module;
				}
				if (!empty($relmod2)) {
					$presenceQuery .= " AND $relmod2 = ?";
					$presenceParams[] = $with_module;
				}
				$checkpresence = $adb->pquery($presenceQuery, $presenceParams);
				// Relation already exists? No need to add again
				if ($checkpresence && $adb->num_rows($checkpresence) == 0) {
					$insertCols = array($relidx, $relidx2);
					$insertParams = array($crmid, $relcrmid);
					if (!empty($relmod1)) {
						$insertCols[] = $relmod1;
						$insertParams[] = $module;
					}
					if (!empty($relmod2)) {
						$insertCols[] = $relmod2;
						$insertParams[] = $with_module;
					}
					$insertQuery = "INSERT INTO $reltab (".implode(',', $insertCols).") VALUES (".generateQuestionMarks($insertCols).")";
					$adb->pquery($insertQuery, $insertParams);
					$adb->pquery("UPDATE {$this->getEntityTableInfo('table')} SET modifiedtime = ? WHERE {$this->getEntityTableInfo('index')} IN (?,?)", array($adb->formatDate(date('Y-m-d H:i:s'), true), intval($crmid), intval($relcrmid))); // crmv@49398 crmv@56655 crmv@69690 crmv@185647
				}
			}
		}
		
		// crmv@200009
		$em = new VTEventsManager($adb);
		$entityData = VTEntityData::fromEntityId($adb, $crmid);
		foreach($with_crmid as $oneid) {
			// first record -> second record
			$entityData->relatedRecord = ['module' => $with_module, 'crmid' => $oneid];
			$em->triggerEvent("vte.entity.relate", $entityData);//crmv@207852
			
			// second record -> first record
			$entityDataRev = VTEntityData::fromEntityId($adb, $oneid);
			$entityDataRev->relatedRecord = ['module' => $module, 'crmid' => $crmid];
			$em->triggerEvent("vte.entity.relate", $entityDataRev);//crmv@207852
		}
		// crmv@200009e
		
	}
	//crmv@38798e crmv@44187e

	/**
	 * Delete the related module record information. Triggered from updateRelations.php
	 * @param String This module name
	 * @param Integer This module record number
	 * @param String Related module name
	 * @param mixed Integer or Array of related module record number
	 */
	//crmv@37004 crmv@43611
	function delete_related_module($module, $crmid, $with_module, $with_crmid, $reverse = true) {
		global $adb,$table_prefix;
		$withInstance = CRMEntity::getInstance($with_module);
		if(!is_array($with_crmid)) $with_crmid = Array($with_crmid);
		foreach($with_crmid as $relcrmid) {
			if ($with_module == 'Messages') {
				$relcrmid = getSingleFieldValue($withInstance->table_name, 'messagehash', $withInstance->table_index, $relcrmid);
				$reverse = false;
			}

			$query = "DELETE FROM {$withInstance->relation_table} WHERE {$withInstance->relation_table_id} = ? AND {$withInstance->relation_table_otherid} = ?";
			$params = array($relcrmid, $crmid);

			if (!empty($withInstance->relation_table_module)) {
				$query .= " AND {$withInstance->relation_table_module} = ?";
				$params[] = $with_module;
			}
			if (!empty($withInstance->relation_table_othermodule)) {
				$query .= " AND {$withInstance->relation_table_othermodule} = ?";
				$params[] = $module;
			}
			$res = $adb->pquery($query, $params);

			// just to be sure, delete also from standard table
			if ($withInstance->relation_table != $table_prefix."_crmentityrel") {
				$adb->pquery("DELETE FROM ".$table_prefix."_crmentityrel WHERE crmid=? AND module=? AND relcrmid=? AND relmodule=?", array($crmid, $module, $relcrmid, $with_module));
			}
			
			$adb->pquery("DELETE FROM ".$table_prefix."_crmentityrel_ord WHERE crmid=? AND module=? AND relcrmid=? AND relmodule=?", array($crmid, $module, $relcrmid, $with_module)); // crmv@125816
			
			$adb->pquery("UPDATE {$table_prefix}_crmentity SET modifiedtime = ? WHERE crmid = ?", array($adb->formatDate(date('Y-m-d H:i:s'), true), $crmid)); // crmv@49398 crmv@69690

			// delete also from the other direction, because it's never clear what's the relations direction
			if ($reverse) {
				$withInstance->delete_related_module($with_module, $relcrmid, $module, $crmid, false);
			}

		}
	}
	//crmv@37004e crmv@43611e

	/** Function to delete an entity with given Id */
	function trash($module, $id) {
		global $log, $current_user, $adb, $table_prefix;
/*
		require_once("include/events/include.inc");
		$em = new VTEventsManager($adb);

		// Initialize Event trigger cache
		$em->initTriggerCache();

		$entityData = VTEntityData::fromEntityId($adb, $id);

		$em->triggerEvent("vte.entity.beforedelete", $entityData);
*/
		$this->mark_deleted($id);
		$this->unlinkDependencies($module, $id);

		$sql_recentviewed = 'DELETE FROM '.$table_prefix.'_tracker WHERE user_id = ? AND item_id = ?';
		$this->db->pquery($sql_recentviewed, array($current_user->id, $id));
/*
		$em->triggerEvent("vte.entity.afterdelete", $entityData);
*/
		//crmv@41883
  		require_once('include/utils/EmailDirectory.php');
		$emailDirectory = new EmailDirectory();
		//TODO reload EmailDirectory for old email, intanto cancello solo la riga per crmid
  		$emailDirectory->deleteById($id);
	  	//crmv@41883e
	}


	/** Function to unlink all the dependent entities of the given Entity by Id */
	function unlinkDependencies($module, $id) {
		global $log,$table_prefix;

		$fieldRes = $this->db->pquery('SELECT tabid, tablename, columnname FROM '.$table_prefix.'_field WHERE fieldid IN (
			SELECT fieldid FROM '.$table_prefix.'_fieldmodulerel WHERE relmodule=?)', array($module));
		$numOfFields = $this->db->num_rows($fieldRes);
		for ($i=0; $i<$numOfFields; $i++) {
			$tabId = $this->db->query_result($fieldRes, $i, 'tabid');
			$tableName = $this->db->query_result($fieldRes, $i, 'tablename');
			$columnName = $this->db->query_result($fieldRes, $i, 'columnname');

			$relatedModule = vtlib_getModuleNameById($tabId);
			$focusObj = CRMEntity::getInstance($relatedModule);

			//Backup Field Relations for the deleted entity
			$relQuery = "SELECT $focusObj->table_index FROM $tableName WHERE $columnName=?";
			$relResult = $this->db->pquery($relQuery, array($id));
			$numOfRelRecords = $this->db->num_rows($relResult);
			if ($numOfRelRecords > 0) {
				$recordIdsList = array();
				for($k=0;$k < $numOfRelRecords;$k++)
				{
					$recordIdsList[] = $this->db->query_result($relResult,$k,$focusObj->table_index);
				}
				//crmv@42329
				$params = array($id, RB_RECORD_UPDATED, $tableName, $columnName, $focusObj->table_index,$this->db->getEmptyClob(true));
				$this->db->pquery('INSERT INTO '.$table_prefix.'_relatedlists_rb VALUES (?,?,?,?,?,?)', $params);
  				$this->db->updateClob("{$table_prefix}_relatedlists_rb","related_crm_ids","entityid=".$this->db->sql_escape_string($id)." and action=".$this->db->sql_escape_string(RB_RECORD_UPDATED)." and rel_table=".$this->db->sql_escape_string($tableName)." and rel_column=".$this->db->sql_escape_string($columnName)." and ref_column=".$this->db->sql_escape_string($focusObj->table_index)." and related_crm_ids=".$this->db->sql_escape_string($this->db->getEmptyClob(true)),implode(",", $recordIdsList));
  				//crmv@42329 e
			}

		}
		//crmv@23515
		$CalendarRelatedTo = getCalendarRelatedToModules();
		if (in_array($module,$CalendarRelatedTo)) {
			$sql = 'DELETE FROM '.$table_prefix.'_seactivityrel WHERE crmid=?';
			$this->db->pquery($sql, array($id));
		}
		//crmv@23515e
	}

	// crmv@97130
	/** Function to unlink an entity with given Id from another entity */
	function unlinkRelationship($id, $return_module, $return_id) {
		global $log, $currentModule,$table_prefix;

		// delete N-N relation
		
		$query = 'DELETE FROM '.$table_prefix.'_crmentityrel WHERE (crmid=? AND relmodule=? AND relcrmid=?) OR (relcrmid=? AND module=? AND crmid=?)';
		$params = array($id, $return_module, $return_id, $id, $return_module, $return_id);
		$this->db->pquery($query, $params);
		
		$this->db->pquery("DELETE FROM ".$table_prefix."_crmentityrel_ord WHERE crmid=? AND relcrmid=?", array($id, $return_id)); // crmv@125816
		
		// direct custom table
		if ($this->relation_table && $this->relation_table != $table_prefix.'_crmentityrel') {
			$query = "DELETE FROM {$this->relation_table} WHERE {$this->relation_table_id} = ? AND {$this->relation_table_otherid} = ?";
			$params = array($id, $return_id);
			if ($this->modulename && $this->relation_table_module) {
				$query .= " AND {$this->relation_table_module} = ?";
				$params[] = $this->modulename;
			}
			if ($this->relation_table_othermodule) {
				$query .= " AND {$this->relation_table_othermodule} = ?";
				$params[] = $return_module;
			}
			$this->db->pquery($query, $params);
		}
		
		// reverse custom table
		$relFocus = CRMEntity::getInstance($return_module);
		if ($relFocus->relation_table && $relFocus->relation_table != $table_prefix.'_crmentityrel') {
			$query = "DELETE FROM {$relFocus->relation_table} WHERE {$relFocus->relation_table_id} = ? AND {$relFocus->relation_table_otherid} = ?";
			$params = array($return_id, $id);
			if ($relFocus->relation_table_module) {
				$query .= " AND {$relFocus->relation_table_module} = ?";
				$params[] = $return_module;
			}
			if ($this->modulename && $relFocus->relation_table_othermodule) {
				$query .= " AND {$relFocus->relation_table_othermodule} = ?";
				$params[] = $this->modulename;
			}
			$this->db->pquery($query, $params);
		}

		
		// delete 1-N or N-1 relation
		
		$fieldRes = $this->db->pquery(
			"SELECT tabid, tablename, columnname
			FROM {$table_prefix}_field 
			WHERE fieldid IN (
				SELECT fieldid FROM {$table_prefix}_fieldmodulerel WHERE module=? AND relmodule=?
			)", 
			array($currentModule, $return_module)
		);
		$numOfFields = $this->db->num_rows($fieldRes);
		for ($i=0; $i<$numOfFields; $i++) {
			$tabId = $this->db->query_result_no_html($fieldRes, $i, 'tabid');
			$tableName = $this->db->query_result_no_html($fieldRes, $i, 'tablename');
			$columnName = $this->db->query_result_no_html($fieldRes, $i, 'columnname');

			$relatedModule = vtlib_getModuleNameById($tabId);
			$focusObj = CRMEntity::getInstance($relatedModule);

			$updateQuery = "UPDATE $tableName SET $columnName = 0 WHERE $columnName = ? AND {$focusObj->table_index} = ?";
			$updateParams = array($return_id, $id);
			$this->db->pquery($updateQuery, $updateParams);
		}
		
		// update the timestamps
		$this->db->pquery("UPDATE {$table_prefix}_crmentity SET modifiedtime = ? WHERE crmid = ?", array($this->db->formatDate(date('Y-m-d H:i:s'), true), $id)); // crmv@49398 crmv@69690
		$this->db->pquery("UPDATE {$table_prefix}_crmentity SET modifiedtime = ? WHERE crmid = ?", array($this->db->formatDate(date('Y-m-d H:i:s'), true), $return_id)); // crmv@49398 crmv@69690
	}
	// crmv@97130e

	/** Function to restore a deleted record of specified module with given crmid
  	  * @param $module -- module name:: Type varchar
  	  * @param $entity_ids -- list of crmids :: Array
 	 */
	function restore($module, $id) {
		global $current_user, $adb, $table_prefix;

		$this->db->println("TRANS restore starts $module");
		$this->db->startTransaction();

		$date_var = date('Y-m-d H:i:s');
		$query = 'UPDATE '.$table_prefix.'_crmentity SET deleted=0,modifiedtime=?,modifiedby=? WHERE crmid = ?';
		$this->db->pquery($query, array($this->db->formatDate($date_var, true), $current_user->id, $id), true, "Error restoring records :");
		//Restore related entities/records
		$this->restoreRelatedRecords($module, $id);
/*
		//Event triggering code
		require_once("include/events/include.inc");
		global $adb;
		$em = new VTEventsManager($adb);

		// Initialize Event trigger cache
		$em->initTriggerCache();

		$this->id = $id;
		$entityData = VTEntityData::fromCRMEntity($this);
		//Event triggering code
		$em->triggerEvent("vte.entity.afterrestore", $entityData);
		//Event triggering code ends
*/
		$this->db->completeTransaction();
		$this->db->println("TRANS restore ends");
	}

	/** Function to restore all the related records of a given record by id */
	function restoreRelatedRecords($module,$record) {
		global $table_prefix;
		$result = $this->db->pquery('SELECT * FROM '.$table_prefix.'_relatedlists_rb WHERE entityid = ?', array($record));
		$numRows = $this->db->num_rows($result);
		for($i=0; $i < $numRows;$i++)
		{
			$action = $this->db->query_result($result,$i,"action");
			$rel_table = $this->db->query_result($result,$i,"rel_table");
			$rel_column = $this->db->query_result($result,$i,"rel_column");
			$ref_column = $this->db->query_result($result,$i,"ref_column");
			$related_crm_ids = $this->db->query_result($result,$i,"related_crm_ids");

			if($action == RB_RECORD_UPDATED && trim($related_crm_ids)!='') {
				$related_ids = explode(",", $related_crm_ids);
				if($rel_table == $table_prefix.'_crmentity' && $rel_column == 'deleted') {
					$sql = "UPDATE $rel_table set $rel_column = 0 WHERE $ref_column IN (". generateQuestionMarks($related_ids) . ")";
					$this->db->pquery($sql, array($related_ids));
				} else {
					$sql = "UPDATE $rel_table set $rel_column = ? WHERE $rel_column = 0 AND $ref_column IN (". generateQuestionMarks($related_ids) . ")";
					$this->db->pquery($sql, array($record, $related_ids));
				}
			} elseif ($action == RB_RECORD_DELETED) {
				if ($rel_table == $table_prefix.'_seproductrel') {
					$sql = "INSERT INTO $rel_table($rel_column, $ref_column, 'setype') VALUES (?,?,?)";
					$this->db->pquery($sql, array($record, $related_crm_ids, $module));
				} else {
					$sql = "INSERT INTO $rel_table($rel_column, $ref_column) VALUES (?,?)";
					$this->db->pquery($sql, array($record, $related_crm_ids));
				}
			}
		}
		//Clean up the the backup data also after restoring
		$this->db->pquery('DELETE FROM '.$table_prefix.'_relatedlists_rb WHERE entityid = ?', array($record));
	}

	function getListViewAdvSecurityParameter($module,$scope=''){
	    global $current_user;
	    require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	    require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
	    $tabid = getTabid($module);
    	if($is_admin==false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1
			&& $defaultOrgSharingPermission[$tabid] == 3) {
			$query = getAdvancedresList($module,'listview');
			$query .= SDK::getAdvancedQuery($module);	//crmv@sdk-18507
			//crmv@100731
			require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
			$PMUtils = ProcessMakerUtils::getInstance();
			// crmv@193432
			if (!in_array($module,$PMUtils->modules_not_supported)) {
				$query .= $PMUtils->getAdvancedPermissions('sql');
			}
			// crmv@100731e crmv@193432e
			//TODO: extend related module permissions
//			if (is_array($related_module_adv_share[$tabid])){
//				foreach ($related_module_adv_share[$tabid] as $rel_tabid)
//				$query .= getParentAdvancedresList($module,getTabname($rel_tabid),'listview');
//			}
		}
		if ($scope != ''){
			$query = str_ireplace($table_prefix.'_crmentity', substr($table_prefix."_crmentity$scope",0,29),$query);
		}
		return $query;
	}

	function getListViewAdvSecurityParameter_list($module){
		global $current_user;
	    require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	    require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
	    $tabid = getTabid($module);
    	if($is_admin==false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1
			&& $defaultOrgSharingPermission[$tabid] == 3) {
			$query = getAdvancedresList($module,'listview');
			$query .= SDK::getAdvancedQuery($module);	//crmv@sdk-18507
			//crmv@100731
			require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
			$PMUtils = ProcessMakerUtils::getInstance();
			// crmv@193432
			if (!in_array($module,$PMUtils->modules_not_supported)) {
				$query .= $PMUtils->getAdvancedPermissions('sql');
			}
			// crmv@100731e crmv@193432e
			//crmv@100731e
			//TODO: extend related module permissions
			//crmv@13979
//			if($rel_tabid==6 && $tabid==4)
//				$tabname='Accounts_Contacts';
//			else
//				$tabname=getTabname($rel_tabid);
//			if (is_array($related_module_adv_share[$tabid])){
//				foreach ($related_module_adv_share[$tabid] as $rel_tabid)
//				$query .= getParentAdvancedresList($module,$tabname,'listview');
//			}
			//crmv@13979 end
			$query .= ")";
		}
		return $query;
	}

	function getListViewAdvSecurityParameter_fields($module){
		global $current_user,$adb;
	    require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	    require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
	    $tabid = getTabid($module);
	    $cols = Array();
    	if($is_admin==false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1
			&& $defaultOrgSharingPermission[$tabid] == 3) {
			$cols = Zend_Json::decode(getAdvancedresList($module,'columns'));
		}
		return $cols;
	}

	function getListViewAdvSecurityParameter_check($module){
	    if ($this->getListViewAdvSecurityParameter($module))
	    	return true;
	    else
	    	return false;
	}

	function buildSearchQueryForFieldTypes($uitypes, $value) {
		global $adb, $table_prefix;

		if(!is_array($uitypes)) $uitypes = array($uitypes);
		$module = SDK::getParentModule(get_class($this));	//crmv@26936

		$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module);
		if($cachedModuleFields === false) {
			getColumnFields($module); // This API will initialize the cache as well
			// We will succeed now due to above function call
			$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module);
		}

		$lookuptables = array();
		$lookupcolumns = array();
		foreach($cachedModuleFields as $fieldinfo) {
			if (in_array($fieldinfo['uitype'], $uitypes)) {
				$lookuptables[] = $fieldinfo['tablename'];
				$lookupcolumns[] = $fieldinfo['columnname'];
			}
		}

		// crmv@144125
		$ENU = EntityNameUtils::getInstance();
		$entityfields = $ENU->getEntityField($module);
		// crmv@144125e
		
		$querycolumnnames = implode(',', $lookupcolumns);
		$entitycolumnnames = $entityfields['fieldname'];
		$query = "select crmid as id, $querycolumnnames, $entitycolumnnames as name ";
		$query .= " FROM $this->table_name ";
		$query .=" INNER JOIN ".$table_prefix."_crmentity ON $this->table_name.$this->table_index = ".$table_prefix."_crmentity.crmid AND deleted = 0 ";

		//remove the base table
		$LookupTable = array_unique($lookuptables);
		$indexes = array_keys($LookupTable, $this->table_name);
		if(!empty($indexes)) {
			foreach($indexes as $index) {
				unset($LookupTable[$index]);
			}
		}
		foreach($LookupTable as $tablename) {
			$query .= " INNER JOIN $tablename
						on $this->table_name.$this->table_index = $tablename.".$this->tab_name_index[$tablename];
		}
		if(!empty($lookupcolumns)) {
			$query .=" WHERE ";
			$i=0;
			$columnCount = count($lookupcolumns);
			foreach($lookupcolumns as $columnname) {
				if(!empty($columnname)) {
					if($i == 0 || $i == ($columnCount))
						$query .= sprintf("%s = '%s'", $columnname, $value);
					else
						$query .= sprintf(" OR %s = '%s'", $columnname, $value);
					$i++;
				}
			}
		}
		return $query;
	}

	/**
	 *
	 * @param String $tableName
	 * @return String
	 */
	public function getJoinClause($tableName) {
		if(strripos($tableName, 'rel') === (strlen($tableName) - 3)) {
			return 'LEFT JOIN';
		} else {
			return 'INNER JOIN';
		}
	}

	/**
	 *
	 * @param <type> $module
	 * @param <type> $user
	 * @param <type> $parentRole
	 * @param <type> $userGroups
	 */
	function getNonAdminAccessQuery($module,$user,$parentRole,$userGroups){
		$query = $this->getNonAdminUserAccessQuery($user, $parentRole, $userGroups);
		if(!empty($module)) {
			$moduleAccessQuery = $this->getNonAdminModuleAccessQuery($module, $user);
			if(!empty($moduleAccessQuery)) {
				$query .= " UNION $moduleAccessQuery";
			}
		}
		$query.=" ) un_table ";
		return $query;
	}

	/**
	 *
	 * @param <type> $user
	 * @param <type> $parentRole
	 * @param <type> $userGroups
	 */
	function getNonAdminUserAccessQuery($user,$parentRole,$userGroups){
		global $table_prefix;
		$query = "select id from ((SELECT id from ".$table_prefix."_users where id = '$user->id') UNION (SELECT ".$table_prefix."_user2role.userid AS id FROM ".
		"".$table_prefix."_user2role INNER JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id=".$table_prefix."_user2role.userid ".
		"INNER JOIN ".$table_prefix."_role ON ".$table_prefix."_role.roleid=".$table_prefix."_user2role.roleid WHERE ".
		"".$table_prefix."_role.parentrole like '$parentRole::%')";
		if(count($userGroups) > 0 ) {
			$query .= " UNION (SELECT groupid as id FROM ".$table_prefix."_groups where".
				" groupid in (".implode(",", $userGroups)."))";
		}
		return $query;
	}

	/**
	 *
	 * @param <type> $module
	 * @param <type> $user
	 */
	function getNonAdminModuleAccessQuery($module,$user){
		global $table_prefix;
		require('user_privileges/sharing_privileges_'.$user->id.'.php');
		$tabId = getTabid($module);
		$sharingRuleInfoVariable = $module.'_share_read_permission';
		$sharingRuleInfo = $$sharingRuleInfoVariable;
		$sharedTabId = null;
		$query = '';
		if(!empty($sharingRuleInfo) && (count($sharingRuleInfo['ROLE']) > 0 ||
				count($sharingRuleInfo['GROUP']) > 0 ||
				count($sharingRuleInfo['USR']) > 0)) {
			$query = " (SELECT shareduserid FROM ".$table_prefix."_tmp_read_u_per ".
			"WHERE userid=$user->id AND tabid=$tabId) UNION (SELECT ".
			"".$table_prefix."_tmp_read_g_per.sharedgroupid FROM ".
			"".$table_prefix."_tmp_read_g_per WHERE userid=$user->id AND tabid=$tabId)";
		}
		return $query;
	}

	// crmv@63349
	/**
	 *
	 * @param <type> $module
	 * @param <type> $user
	 * @param <type> $parentRole
	 * @param <type> $userGroups
	 */
	protected function setupTemporaryTable($tableName,$tabId, $user,$parentRole,$userGroups) {
		if (PerformancePrefs::getBoolean('USE_TEMP_TABLES', true)) {
			return $this->setupTemporaryTable_tmp($tableName,$tabId, $user,$parentRole,$userGroups);
		} else {
			return $this->setupTemporaryTable_notmp($tableName,$tabId, $user,$parentRole,$userGroups);
		}
	}

	protected function setupTemporaryTable_notmp($tableName,$tabId, $user,$parentRole,$userGroups) {
		// nothing to do
	}
	// crmv@63349e

	protected function setupTemporaryTable_tmp($tableName,$tabId, $user,$parentRole,$userGroups) { // crmv@63349
		$module = null;
		if (!empty($tabId)) {
			$module = getTabModuleName($tabId);
		}
		$query = $this->getNonAdminAccessQuery($module, $user, $parentRole, $userGroups);
		$db = PearDatabase::getInstance();
		if ($db->isMysql()){
			$query = "create temporary table IF NOT EXISTS $tableName(id int(11) primary key) ignore ".
			$query;
			$result = $db->pquery($query, array());
		}
		else {
			if (!$db->table_exist($tableName,true)){
				Vtecrm_Utils::CreateTable($tableName,"id I(11) NOTNULL PRIMARY",true,true);//crmv@198038
			}
			$tableName = $db->datadict->changeTableName($tableName);
			$query = "insert into $tableName ".
			$query.
			"where not exists (select * from $tableName where $tableName.id = un_table.id)";
			$result = $db->pquery($query, array());
		}
		return $result;
	}

	// crmv@63349
	/**
	 *
	 * @param String $module - module name for which query needs to be generated.
	 * @param Users $user - user for which query needs to be generated.
	 * @return String Access control Query for the user.
	 */
	function getNonAdminAccessControlQuery($module,$user,$scope='',$join_cond=''){	//crmv@31775
		if (PerformancePrefs::getBoolean('USE_TEMP_TABLES', true)) {
			return $this->getNonAdminAccessControlQuery_tmp($module,$user, $scope,$join_cond);
		} else {
			return $this->getNonAdminAccessControlQuery_notmp($module,$user, $scope,$join_cond);
		}
	}

	function getNonAdminAccessControlQuery_notmp($module,$user,$scope='',$join_cond=''){	//crmv@31775
		global $table_prefix;
		$userid = $user->id;
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		require('user_privileges/sharing_privileges_'.$user->id.'.php');
		$query = ' ';
		$tabId = getTabid($module);
		if($is_admin==false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2]
				== 1 && $defaultOrgSharingPermission[$tabId] == 3) {
			
			$tableName = $table_prefix.'_tmp_users';	
			$sharingRuleInfoVariable = $module.'_share_read_permission';
			$sharingRuleInfo = $$sharingRuleInfoVariable;
			$sharedTabId = null;
			if(!empty($sharingRuleInfo) && (count($sharingRuleInfo['ROLE']) > 0 ||
					count($sharingRuleInfo['GROUP']) > 0
					 || count($sharingRuleInfo['USR']) > 0)) {
				$tableName = $table_prefix.'_tmp_users_mod';
				$sharedTabId = $tabId;
			}elseif($module == 'Calendar') { // crmv@105149 - remove scope condition
				$tableName = $table_prefix.'_tmp_users_cal';
			}
			if (!$this->getListViewAdvSecurityParameter_check($module)){
				$name = substr($tableName.$scope,0,29);
				$name2 = substr($table_prefix."_crmentity$scope",0,29);
				$db = PearDatabase::getInstance();
				//crmv@129940 - removed change table name
				//crmv@26650
				if ($module == 'Calendar') {
					$name3 = substr($table_prefix."_activity$scope",0,29);
					$query = " INNER JOIN $tableName $name ON ($name.userid = $userid AND $name.tabid = $tabId AND $name.subuserid = $name2.smownerid and $name.shared = 0) ";

					$sharedIds = getSharedCalendarId($user->id);
					if(!empty($sharedIds)){
						$query .= "or ($name.userid = $userid AND $name.tabid = $tabId AND $name.subuserid = $name2.smownerid AND $name.shared=1) "; // crmv@70053
					}
					//crmv@17001
					$query .= " or ($name.userid = $userid AND $name.tabid = $tabId AND $name.subuserid = $name2.smownerid AND {$table_prefix}_activity.activityid in (SELECT activityid FROM {$table_prefix}_invitees WHERE inviteeid = $userid))";
					//crmv@17001e
				} else { //crmv@26650e
					//crmv@31775
					//crmv@46974
					if ($db->isMysql()){
						if ($join_cond == '') {
							$join_cond = 'STRAIGHT_JOIN';
						}
						else{
							$join_cond = "{$join_cond} join";
						}
					}
					else{
						if ($join_cond == '') {
							$join_cond = "INNER JOIN";
						}
						else{
							$join_cond = "{$join_cond} JOIN";
						}
					}					
					if ($sharedTabId > 0) {
						$query = " $join_cond $tableName $name ON $name.userid = $userid AND $name.tabid = $tabId AND $name.subuserid = $name2.smownerid ";
					} else {
						$query = " $join_cond $tableName $name ON $name.userid = $userid AND $name.subuserid = $name2.smownerid ";
					}
					//crmv@46974 e
					//crmv@31775e
				}
			}
		}
		return $query;
	}
	// crmv@63349e
	
	function getNonAdminAccessControlQuery_tmp($module,$user,$scope='',$join_cond=''){	//crmv@31775 crmv@63349
		global $table_prefix;
		$userid = $user->id;
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		require('user_privileges/sharing_privileges_'.$user->id.'.php');
		$query = ' ';
		$tabId = getTabid($module);
		if($is_admin==false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2]
				== 1 && $defaultOrgSharingPermission[$tabId] == 3) {
			$tableName = 'vt_tmp_u'.$user->id;
			$sharingRuleInfoVariable = $module.'_share_read_permission';
			$sharingRuleInfo = $$sharingRuleInfoVariable;
			$sharedTabId = null;
			if(!empty($sharingRuleInfo) && (count($sharingRuleInfo['ROLE']) > 0 ||
					count($sharingRuleInfo['GROUP']) > 0
					 || count($sharingRuleInfo['USR']) > 0)) {
				$tableName = $tableName.'_t'.$tabId;
				$sharedTabId = $tabId;
			}elseif($module == 'Calendar' || !empty($scope)) {
				$tableName .= '_t'.$tabId;
			}
			$this->setupTemporaryTable_tmp($tableName, $sharedTabId, $user, $current_user_parent_role_seq, $current_user_groups); // crmv@63349
			if (!$this->getListViewAdvSecurityParameter_check($module)){
				$name = substr($tableName.$scope,0,29);
				$name2 = substr($table_prefix."_crmentity$scope",0,29);
				$db = PearDatabase::getInstance();
				$tableName = $db->datadict->changeTableName($tableName);
				//crmv@26650
				if ($module == 'Calendar') {
					$name3 = substr($table_prefix."_activity$scope",0,29);
					$query = " INNER JOIN $tableName $name ON ($name.id = $name2.smownerid and $name.shared=0) ";

					$sharedIds = getSharedCalendarId($user->id);
					if(!empty($sharedIds)){
						$query .= "or ($name.id = $name2.smownerid AND $name.shared=1) "; // crmv@70053
					}
					//crmv@17001
					$query .= " or ($name.id = $name2.smownerid AND ".$table_prefix."_activity.activityid in(SELECT activityid FROM ".$table_prefix."_invitees WHERE inviteeid = $user->id))";
					//crmv@17001e
				} else { //crmv@26650e
					//crmv@31775
					//crmv@46974
					if ($db->isMysql()){
						if ($join_cond == '') {
							$join_cond = 'STRAIGHT_JOIN';
						}
						else{
							$join_cond = "{$join_cond} join";
						}
					}
					else{
						if ($join_cond == '') {
							$join_cond = "INNER JOIN";
						}
						else{
							$join_cond = "{$join_cond} JOIN";
						}
					}					
					$query = " $join_cond $tableName $name ON $name.id = $name2.smownerid ";
					//crmv@46974 e
					//crmv@31775e
				}
			}
		}
		return $query;
	}

	// crmv@63349
	function getNonAdminAccessControlQuery_onlyquery($module,$user,$scope='') {
		if (PerformancePrefs::getBoolean('USE_TEMP_TABLES', true)) {
			return $this->getNonAdminAccessControlQuery_onlyquery_tmp($module,$user, $scope);
		} else {
			return $this->getNonAdminAccessControlQuery_onlyquery_notmp($module,$user, $scope);
		}
	}

	function getNonAdminAccessControlQuery_onlyquery_notmp($module,$user,$scope='') {
		global $table_prefix;
		$userid = $user->id;
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		require('user_privileges/sharing_privileges_'.$user->id.'.php');
		$query = ' ';
		$tabId = getTabid($module);
		if($is_admin==false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2]
				== 1 && $defaultOrgSharingPermission[$tabId] == 3) {
			$tableName = $table_prefix.'_tmp_users';	
			$sharingRuleInfoVariable = $module.'_share_read_permission';
			$sharingRuleInfo = $$sharingRuleInfoVariable;
			$sharedTabId = null;
			if(!empty($sharingRuleInfo) && (count($sharingRuleInfo['ROLE']) > 0 ||
					count($sharingRuleInfo['GROUP']) > 0
					 || count($sharingRuleInfo['USR']) > 0)) {
				$tableName = $table_prefix.'_tmp_users_mod';
				$sharedTabId = $tabId;
			}elseif($module == 'Calendar') { // crmv@105149 - remove scope condition
				$tableName = $table_prefix.'_tmp_users_cal';
			}
			$name = substr($tableName.$scope,0,29);
			$name2 = substr($table_prefix."_crmentity$scope",0,29);
			if (!in_array($table_prefix.'_crmentity',$this->tab_name)) $name2 = $this->getEntityTableInfo('table'); // crmv@185647
			$db = PearDatabase::getInstance();
			//crmv@129940 - remove change table name
			//crmv@26650
		   	if ($module == 'Calendar') {
		    	$query = "select *
		    			from $tableName
		    			where ($name.userid = $userid AND $name.tabid = $tabId AND $name.subuserid = $name2.smownerid and $name.shared = 0)
		    			OR (
							$name.userid = $userid AND $name.tabid = $tabId AND $name.subuserid = $name2.smownerid AND {$table_prefix}_activity.activityid in (
								SELECT activityid 
								FROM {$table_prefix}_invitees 
								WHERE inviteeid = $userid
							)
		    			)";
		   	} else {
	   		//crmv@26650e
		   		if ($sharedTabId > 0) {
					$query = " select * from $tableName $name WHERE $name.userid = $userid AND $name.tabid = $tabId AND $name.subuserid = $name2.smownerid ";
				} else {
					$query = " select * from $tableName $name WHERE $name.userid = $userid AND $name.subuserid = $name2.smownerid ";
				}
		   	}
		}
		return $query;
	}
	// crmv@63349e

	function getNonAdminAccessControlQuery_onlyquery_tmp($module,$user,$scope='') { // crmv@63349
		global $table_prefix;
		$userid = $user->id;
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		require('user_privileges/sharing_privileges_'.$user->id.'.php');
		$query = ' ';
		$tabId = getTabid($module);
		if($is_admin==false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2]
				== 1 && $defaultOrgSharingPermission[$tabId] == 3) {
			$tableName = 'vt_tmp_u'.$user->id;
			$sharingRuleInfoVariable = $module.'_share_read_permission';
			$sharingRuleInfo = $$sharingRuleInfoVariable;
			$sharedTabId = null;
			if(!empty($sharingRuleInfo) && (count($sharingRuleInfo['ROLE']) > 0 ||
					count($sharingRuleInfo['GROUP']) > 0
					 || count($sharingRuleInfo['USR']) > 0)) {
				$tableName = $tableName.'_t'.$tabId;
				$sharedTabId = $tabId;
			}elseif($module == 'Calendar' || !empty($scope)) {
				$tableName .= '_t'.$tabId;
			}
			$name = substr($tableName.$scope,0,29);
			$name2 = substr($table_prefix."_crmentity$scope",0,29);
			if (!in_array($table_prefix.'_crmentity',$this->tab_name)) $name2 = $this->getEntityTableInfo('table'); // crmv@185647
			$db = PearDatabase::getInstance();
			$tableName = $db->datadict->changeTableName($tableName);
			//crmv@26650
		   	if ($module == 'Calendar') {
		    	$query = "select *
		    			from $tableName
		    			where ($tableName.id = $name2.smownerid AND $tableName.shared = 0)
		    			OR ($tableName.id = $name2.smownerid AND ".$table_prefix."_activity.activityid IN (
			    				SELECT activityid
							    FROM ".$table_prefix."_invitees
							    WHERE ".$table_prefix."_activity.activityid > 0 AND ".$table_prefix."_invitees.inviteeid = {$user->id}
		    				)
		    			)";
		   	} else {
	   		//crmv@26650e
		   		$query = " select * from  $tableName $name where $name.id = $name2.smownerid ";
		   	}
		}
		return $query;
	}

	public function listQueryNonAdminChange( $query,$module,$scope='' ) {
		//make the module base table as left hand side table for the joins,
		//as mysql query optimizer puts crmentity on the left side and considerably slow down
		$query = preg_replace("/[\n\r\t]+/"," ",$query); //crmv@20049
		if(strripos($query, ' WHERE ') !== false) {
			vtlib_setup_modulevars($module, $this);
			//crmv@7221
			if ($this->getListViewAdvSecurityParameter_check($module)){
				global $current_user;
				$userid_conditions = $this->getNonAdminAccessControlQuery_onlyquery($module,$current_user,$scope);
				$adv_filter_conditions = $this->getListViewAdvSecurityParameter($module,$scope);
				if (!$userid_conditions)
					$add = $adv_filter_conditions;
				else {
					// crmv@104600
					// this should improve performance, by removing the adv_conditions from the subquery
					$add = " and (exists ($userid_conditions) $adv_filter_conditions)";
					// old query, restore in case of problems
					//$add = " and exists ($userid_conditions $adv_filter_conditions)";
					// crmv@104600e
				}
				//crmv@24715
				fix_query_advanced_filters($module,$query,false,$scope); // crmv@129114
				//crmv@24715e
			}
			//crmv@7221e
			$query = str_ireplace(' where ', " WHERE $this->table_name.$this->table_index > 0 AND ", $query);
			if ($add) {
				$query .=  $add;
			}
		}
		return $query;
	}

	public function listQueryNonAdminChange_parent( $query,$module,$scope='', $onlyCondition = false) { // crmv@57608
		//make the module base table as left hand side table for the joins,
		//as mysql query optimizer puts crmentity on the left side and considerably slow down
		$query = preg_replace("/[\n\r\t]+/"," ",$query); //crmv@20049
		if(strripos($query, ' WHERE ') !== false) {
			vtlib_setup_modulevars($module, $this);
			//crmv@7221
			if ($this->getListViewAdvSecurityParameter_check($module)){
				global $current_user;
				$userid_conditions = $this->getNonAdminAccessControlQuery_onlyquery($module,$current_user,$scope);
				$adv_filter_conditions = $this->getListViewAdvSecurityParameter($module,$scope);
				if (!$userid_conditions)
					$add = $adv_filter_conditions;
				else
					$add = " exists ($userid_conditions $adv_filter_conditions)";
			}
			//crmv@7221e
			if ($onlyCondition) return $add; // crmv@57608
			if ($add) $query .= " or ". $add;
		}
		return $query;
	}

	//crmv@9434
	function get_transitions_history($id, $cur_tab_id, $rel_tab_id, $actions=false){
		global $currentModule, $app_strings, $current_user;//crmv@203484 removed global singlepane
		$parenttab = getParentTab();
		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e
		//crmv@31357
		$trans_obj = CRMEntity::getInstance('Transitions');
		$trans_obj->Initialize(getTabName($cur_tab_id));
		//crmv@31357e
		$return_value = $trans_obj->get_transitions_history($id);
		return $return_value;
	}
	//crmv@9434e

	//crmv@25403
	public function getFixedOrderBy($module,$order_by,$sorder){
		global $adb, $table_prefix, $current_user, $showfullusername; //crmv@168690

		$webservice_field = WebserviceField::fromQueryResult($adb,$adb->pquery('select * from '.$table_prefix.'_field where tabid = ? and columnname = ?',array(getTabid($module),$order_by)),0);
		$reference_modules=$webservice_field->getReferenceList();
		$type = $webservice_field->getFieldDataType(); //crmv@74933
		if(!empty($reference_modules) && is_array($reference_modules)){
			// crmv@155560 crmv@168690
			$fieldid = $webservice_field->getFieldId();
			if (in_array('DocumentFolders', $reference_modules)) {
				return " ORDER BY {$table_prefix}_crmentityfolder.foldername $sorder";
			} elseif (in_array('Users', $reference_modules)) {
				$queryGeneratorInstance = QueryGenerator::getInstance($module,$current_user);
				$usersInstance = CRMEntity::getInstance('Users');
				$referenceTable = $usersInstance->table_name;
				// use the aliased table if there is already an owner field or if we are in the quotes/vendors (WTF!!)
				$ownerFields = $queryGeneratorInstance->getOwnerFieldList();
				if(count($ownerFields) > 0 || in_array($module, array('Quotes', 'Vendors'))) { // crmv@146069
					//crmv@121417
					$appendIndex = '_fld_'.$fieldid;
					$referenceTable = substr($referenceTable.$appendIndex, 0, 29);
					//crmv@121417e
				}
				$columnSql = $usersInstance->formatUserNameSql($adb, $referenceTable, $showfullusername);
				return " ORDER BY {$columnSql} $sorder";
			} else {
				// it's a reference module, sort using the entityname
				return " ORDER BY entityname_fld_{$fieldid}.displayname $sorder";
			}
			// crmv@155560e crmv@168690e
		//crmv@74933
		}elseif($type == 'picklistmultilanguage'){
			$curField = $webservice_field->getFieldName();
		    $tablename = 'tbl_pick_lang'.$curField;
		    return  " ORDER BY {$tablename}.value  $sorder";
		}
		//crmv@74933e
		//crmv@127820
		elseif($type == 'owner'){
			$return=array();
			$query = "select fieldname,tablename,entityidfield from ".$table_prefix."_entityname where modulename = ?";
			$result = $adb->pquery($query, array('Users'));
			$order_by = $adb->query_result($result,0,'fieldname');
			$tablename = $adb->query_result($result,0,'tablename');
			$order_by = explode(',', $order_by);
			if (is_array($order_by) && !empty($order_by)) {
				foreach ($order_by as $oby) {
					$res = $adb->pquery("SELECT columnname FROM {$table_prefix}_field WHERE tabid = ? AND fieldname = ?", array(getTabId('Users'), $oby));
					if ($res && $adb->num_rows($res) > 0) {
						$colname = $adb->query_result_no_html($res, 0, 'columnname');
						$return[]=$tablename .'.'. $colname . ' ' . $sorder;
					}
				}
			}
			return  ' ORDER BY ' . implode(' , ',$return);
		}
		//crmv@127820e
		else{
			$tablename = getTableNameForField($module, $order_by);
			if ($tablename == '' && $order_by == 'crmid') {
				// crmv@185647
				$tablename = $this->getEntityTableInfo('table');
				$order_by = $this->getEntityTableInfo('index');
				// crmv@185647e
			}
			$tablename = ($tablename != '')? ($tablename . '.') : '';
			//crmv@37823	crmv@52437
			if($adb->isMssql() && $order_by != 'crmid'){
				if ($type == 'text' || $type == 'multipicklist') {
					$order_by = "cast({$tablename}{$order_by} as varchar(5000))";
					return  ' ORDER BY ' . $order_by. ' ' . $sorder;
				}
			}
			//crmv@37823e	crmv@52437e
			return  ' ORDER BY ' . $tablename . $order_by . ' ' . $sorder;
		}
	}
	//crmv@25403e

	/**
	 * Get list view query (send more WHERE clause condition if required)
	 */
	//crmv@171021
	function getListQuery($module, $where='') {
		global $current_user, $table_prefix;
		
		$use_crmentity = true;
		$entity_table = $table_prefix.'_crmentity';
		if (!in_array($table_prefix.'_crmentity',$this->tab_name)) {
			$use_crmentity = false;
			$entity_table = $this->table_name;
		}
		
		$query = "SELECT $this->table_name.*";
		if ($use_crmentity) $query .= ", {$table_prefix}_crmentity.*";
		
		// Select Custom Field Table Columns if present
		if(!empty($this->customFieldTable)) $query .= ", " . $this->customFieldTable[0] . ".* ";
		
		$query .= " FROM $this->table_name";
		
		if ($use_crmentity) $query .= " INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = $this->table_name.$this->table_index";
		
		// Consider custom table join as well.
		if(!empty($this->customFieldTable)) {
			$query .= " INNER JOIN ".$this->customFieldTable[0]." ON ".$this->customFieldTable[0].'.'.$this->customFieldTable[1] .
			" = $this->table_name.$this->table_index";
		}
		
		// crmv@110987
		$usedTables = array();
		if (is_array($this->tab_name_index)) {
			$usedTables = array($this->table_name, $table_prefix."_crmentity", $this->customFieldTable[0]);
			foreach ($this->tab_name_index as $table => $index) {
				if (!in_array($table, $usedTables)) {
					$query .= " LEFT JOIN {$table} ON {$table}.{$index} = {$this->table_name}.{$this->table_index}";
				}
			}
		}
		// crmv@110987e
		
		$query .= " LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$entity_table.".smownerid";
		$query .= " LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$entity_table.".smownerid";
		
		// crmv@139482
		$linkedModulesQuery = $this->db->pquery("SELECT distinct fieldname, {$table_prefix}_field.fieldid, columnname, tablename, relmodule FROM ".$table_prefix."_field" . // crmv@184929
			" INNER JOIN ".$table_prefix."_fieldmodulerel ON ".$table_prefix."_fieldmodulerel.fieldid = ".$table_prefix."_field.fieldid" .
			" WHERE uitype='10' AND ".$table_prefix."_fieldmodulerel.module=?", array($module));
		// crmv@139482e
		$linkedFieldsCount = $this->db->num_rows($linkedModulesQuery);
		
		$hasAdvRuleUitype10 = (strpos($where, 'entityname_fld_') !== false); // crmv@184929
		
		$idx = 2; // crmv@170666
		for($i=0; $i<$linkedFieldsCount; $i++) {
			$related_module = $this->db->query_result($linkedModulesQuery, $i, 'relmodule');
			$fieldname = $this->db->query_result($linkedModulesQuery, $i, 'fieldname');
			$columnname = $this->db->query_result($linkedModulesQuery, $i, 'columnname');
			$tablename = $this->db->query_result($linkedModulesQuery, $i, 'tablename'); // crmv@139482
			$fieldid = $this->db->query_result_no_html($linkedModulesQuery, $i, 'fieldid'); // crmv@184929
			
			$other = CRMEntity::getInstance($related_module);
			vtlib_setup_modulevars($related_module, $other);
			
			//crmv@26198 crmv@107669 crmv@139482: fix for uitype 10, parentid
			if (in_array($other->table_name,$usedTables)) {
				// crmv@170666
				$query .= " LEFT JOIN $other->table_name AS {$other->table_name}$idx ON {$other->table_name}$idx.$other->table_index = $tablename.$columnname";
				++$idx;
				// crmv@170666e
			} else {
				$query .= " LEFT JOIN $other->table_name ON $other->table_name.$other->table_index = $tablename.$columnname";
				$usedTables[] = $other->table_name;
			}
			//crmv@26198e crmv@107669e crmv@139482e
			
			// crmv@184929
			if ($hasAdvRuleUitype10) {
				$alias = "entityname_fld_$fieldid";
				if (!in_array($alias,$usedTables)) {
					$query .= " LEFT JOIN {$table_prefix}_entity_displayname $alias ON $alias.crmid = $tablename.$columnname";
					$usedTables[] = $alias;
				}
			}
			// crmv@184929e
		}
		//crmv@31775
		$reportFilterJoin = '';
		$viewId = getLVS($module,'viewname');
		if (isset($_REQUEST['viewname']) && $_REQUEST['viewname'] != '') {
			$viewId = $_REQUEST['viewname'];
		}
		if ($viewId != '') {
			$oCustomView = CRMEntity::getInstance('CustomView', $module); // crmv@115329
			$reportFilter = $oCustomView->getReportFilter($viewId);
			if ($reportFilter) {
				$query .= $oCustomView->getReportFilterJoin(($use_crmentity)?"{$table_prefix}_crmentity.crmid":$this->table_name.$this->table_index, $reportFilter,$current_user->id); // crmv@122906
			}
		}
		//crmv@31775e
		// crmv@30014
		if (method_exists($this, 'getQueryExtraJoin')) {
			$extraJoin = $this->getQueryExtraJoin();
			$query .= " $extraJoin";
		}
		if (method_exists($this, 'getQueryExtraWhere')) {
			$where .= " ".$this->getQueryExtraWhere();
		}
		// crmv@30014e
		
		// crmv@64325
		$setypeCond = '';
		if (PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED') && $use_crmentity) {
			$setypeCond = "AND {$table_prefix}_crmentity.setype = '$module'";
		}
		
		$query .= $this->getNonAdminAccessControlQuery($module,$current_user);
		$query .= " WHERE $entity_table.deleted = 0 $setypeCond".$where;
		$query = $this->listQueryNonAdminChange($query, $module);
		// crmv@64325e
		
		return $query;
	}
	//crmv@171021e

	//crmv@26631	//crmv@29506
	function getQuickCreateDefault($module, $qcreate_array, $search_field, $search_text) {
		global $table_prefix;
		if ($search_field != '' && $search_text != '') {
			$col_fields[$search_field] = strtolower($search_text);
		}
		$email = '';
		if (!empty($qcreate_array)) {
			foreach($qcreate_array['form'] as $row) {
				foreach($row as $field) {
					if ($field[2][0] == $search_field && $field[0][0] == 13) {
						$email = strtolower($search_text);
						break;
					}
				}
			}
		}
		if ($email != '') {
			$tmp = explode('@',$email);
			if ($tmp[1] != '') {
				$company = ucfirst($tmp[1]);
				$pos = strpos($company, '.');
				if ($pos !== false) {
					$company = substr($company,0,$pos);
				}
				$website = 'www.'.$tmp[1];
			}
			if ($tmp[0] != '') {
				$tmp[0] = preg_replace('/[0-9-]+/','',$tmp[0]);
				$lastname = ucfirst($tmp[0]);
				$firstname = '';
				$separator = '.';
				$pos = strpos($tmp[0], $separator);
				if ($pos === false) {
					$separator = '_';
					$pos = strpos($tmp[0], $separator);
				}
				if ($pos !== false) {
					$firstname = trim(ucfirst(substr($tmp[0],0,$pos)));
					$lastname = trim(ucwords(str_replace($separator,' ',substr($tmp[0],$pos))));
				}
			}
			switch ($module) {
				case 'Accounts':
					$col_fields['accountname'] = $company;
					$col_fields['website'] = $website;
					break;
				case 'Leads':
					$col_fields['lastname'] = $lastname;
					$col_fields['firstname'] = $firstname;
					$col_fields['company'] = $company;
					$col_fields['website'] = $website;
					break;
				case 'Contacts':
					$col_fields['lastname'] = $lastname;
					$col_fields['firstname'] = $firstname;
					break;
				case 'Vendors':
					$col_fields['vendorname'] = trim($firstname.' '.$lastname);
					$col_fields['website'] = $website;
					break;
			}
		}
		return $col_fields;
	}
	//crmv@26631e	//crmv@29506e

	//crmv@29579 crmv@164120
	/**
	 * @deprecated
	 */
	function get_changelog_list($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		// do nothing, changelog is not an entitymodule anymore
	}
	//crmv@29579e crmv@164120e

	//crmv@37004 crmv@171021
	// retrieves the list of fields to show in the message popup boxes
	// override this method to use other fields than the entityname
	function getMessagePopupFields($module) {
		$namefields = vtws_getEntityNameFields($module);
		return $namefields;
	}

	// in relatedIds: first crmid is the main one
	function getMessagePopupLinkQuery(&$queryGenerator, $module, $relatedIds = array(), $searchstr = '', $excludeIds = array()) {
		global $adb, $table_prefix, $current_user;

		$tabid = getTabid($module);
		$listFields = $this->getMessagePopupFields($module);

		$queries = array();

		$meta = $queryGenerator->getMeta($module);
		$baseTable = $meta->getEntityBaseTable();
		$moduleTableIndexList = $meta->getEntityTableIndexList();
		$baseTableIndex = $moduleTableIndexList[$baseTable];

		// first search with primary id of these modules, then the others
		$linkModules = array('Contacts', 'Accounts', 'Leads', 'Vendors');	//crmv@128228

		$i = 0;
		// FIRST QUERY SET (first record only)
		foreach ($linkModules as $linkModule) {
			if (is_array($relatedIds[$linkModule]) && $relatedIds[$linkModule][0] > 0) {
				$queryGenerator->resetAll();
				$queryGenerator->setFields($listFields);
				$relations = array($linkModule=>array($relatedIds[$linkModule][0]));
				$r = $this->getMessagePopupConditions($queryGenerator, $module, $relations, $searchstr);
				if ($r) {
					$query = $queryGenerator->getQuery();
					$query = replaceSelectQuery($query, "distinct {$table_prefix}_crmentity.crmid");
					//$query = preg_replace('/^select /i', "select $baseTable.$baseTableIndex,", $query);
					$orderby = $this->getMessagePopupOrderBy($queryGenerator, $module, $relations, $searchstr);
					$queries[$i] = $query." ".$orderby;
				}
			}
			++$i;
		}
		
		// SECOND QUERY SET (next records)
		foreach ($linkModules as $linkModule) {
			if (is_array($relatedIds[$linkModule]) && count($relatedIds[$linkModule]) > 1) {
				$queryGenerator->resetAll();
				$queryGenerator->setFields($listFields);
				$relations = array($linkModule=>array_slice($relatedIds[$linkModule], 1));
				$r = $this->getMessagePopupConditions($queryGenerator, $module, $relations, $searchstr);
				if ($r) {
					$query = $queryGenerator->getQuery();
					$query = replaceSelectQuery($query, "distinct {$table_prefix}_crmentity.crmid");
					//$query = preg_replace('/^select /i', "select $baseTable.$baseTableIndex,", $query);
					$orderby = $this->getMessagePopupOrderBy($queryGenerator, $module, $relations, $searchstr);
					$queries[$i] = $query." ".$orderby;
				}
			}
			++$i;
		}

		if (count($queries) == 0) return '';

		// crmv@63349
		if (PerformancePrefs::getBoolean('USE_TEMP_TABLES', true)) {
			// create temp table, (use sequence to keep order)
			// TODO: limitare le query parziali
			$tableName = 'tmp_popuprel_'.$current_user->id."_".$tabid;
			if (!$adb->table_exist($tableName,true)){
				//crmv@64361
				if ($adb->isMssql()){
					Vtecrm_Utils::CreateTable($tableName,"id I(11) NOTNULL, sequence I(11) AUTOINCREMENT NOTNULL PRIMARY",true,true);//crmv@198038
				} else {
					Vtecrm_Utils::CreateTable($tableName,"id I(11) NOTNULL UNIQUE INDEX, sequence I(11) AUTOINCREMENT NOTNULL PRIMARY",true,true);//crmv@198038
				}
				//crmv@64361 e
			}
			$tableName = $adb->datadict->changeTableName($tableName);
			
			// clear it
			$adb->query("truncate table $tableName");
			
			// join condition
			$appendJoin = " INNER JOIN $tableName ON $tableName.id = {$table_prefix}_crmentity.crmid";
		} else {
			$tabid = intval($tabid);
			$tableName = $table_prefix.'_messages_tmp_prel';
			//crmv@129940 - removed change table name
			
			// clear it
			$adb->pquery("DELETE FROM $tableName WHERE userid = ? AND tabid = ?", array($current_user->id, $tabid));
			
			// join con
			$appendJoin = " INNER JOIN $tableName ON $tableName.userid = {$current_user->id} AND $tableName.tabid = $tabid AND $tableName.id = {$table_prefix}_crmentity.crmid";
		}

		// write data (avoid duplicates)
		foreach ($queries as $q) {
			if ($adb->isMysql()) {
				if (PerformancePrefs::getBoolean('USE_TEMP_TABLES', true)) {
					$result = $adb->query("insert ignore into $tableName (id) $q");
				} else {
					$q = preg_replace('/^SELECT\s+DISTINCT\s+(.*?)\s+FROM/i', "SELECT DISTINCT {$current_user->id} as userid, $tabid as tabid, \\1 FROM", trim($q));
					$result = $adb->query("insert ignore into $tableName (userid, tabid, id) $q");
				}
			} else {
				// TODO: altri database
				//$result = $adb->query("insert into $tableName (id) $q where ...");
			}
		}

		// generate global query
		$queryGenerator->resetAll();

		// crmv@42752 - show default filter info instead of the specified ones
		//$queryGenerator->setFields($lisFields);
		$queryGenerator->initForAllCustomView(); //crmv@95082
		// crmv@42752e

		$queryGenerator->appendToFromClause($appendJoin);
		// crmv@63349e

		// and remove already linked messages
//		$queryGenerator->appendToFromClause(" LEFT JOIN {$table_prefix}_messagesrel ON {$table_prefix}_messagesrel.crmid = {$table_prefix}_crmentity.crmid AND {$table_prefix}_messagesrel.module = '$module'");
//		$queryGenerator->appendToWhereClause(" AND {$table_prefix}_messagesrel.messagehash IS NULL");
		if (!empty($excludeIds)) {
			$queryGenerator->appendToWhereClause(" AND {$table_prefix}_crmentity.crmid NOT IN (".implode(',',$excludeIds).")");
		}
		$query = $queryGenerator->getQuery();
		// add id field
		$query = preg_replace('/^select /i', "select $baseTable.$baseTableIndex,", $query);
		$query .= " ORDER BY $tableName.sequence ASC";

		return $query;
	}

	// aggiunge condizioni per prendere i record collegati ai relatedids
	// se ho pi� moduli, il record deve essere collegato ad almeno un id per ogni modulo
	function getMessagePopupConditions(&$queryGenerator, $module, $relatedIds = array(), $searchstr = '') {
		global $adb, $table_prefix;

		$rm = RelationManager::getInstance();

		$moduleInstance = CRMEntity::getInstance($module);
		$moduleFieldList = $queryGenerator->getModuleFields();
		$appendWhere = '';


		// relation conditions
		foreach ($relatedIds as $relmod=>$rellist) {
			if (empty($rellist)) continue;

			$relModInstance = CRMEntity::getInstance($relmod);

			$joinConditions = array();
			$fieldConditions = array();
			$relations = $rm->getRelations($module, ModuleRelation::$TYPE_1TON | ModuleRelation::$TYPE_NTO1 | ModuleRelation::$TYPE_NTON, $relmod);

			if (count($relations) > 0) {
				$fieldUsed = array();
				$joinUsed = array();
				foreach ($relations as $rel) {

					if ($rel->getType() == ModuleRelation::$TYPE_NTO1) {

						$fieldname = $rel->fieldname;
						if (!$fieldUsed[$fieldname] && $fieldname && $moduleFieldList[$fieldname]) {
							$field = $moduleFieldList[$fieldname];
							if (count($rellist) == 1) {
								$newsql = $field->getTableName().'.'.$field->getColumnName()." = ".intval($rellist[0]);
							} else {
								// take only first 50 crmid
								$newsql = $field->getTableName().'.'.$field->getColumnName()." in (".implode(',', array_slice($rellist, 0, 50)).")";
							}
							$queryGenerator->addField($fieldname);
							$fieldConditions[] = $newsql;
							$fieldUsed[$fieldname] = true;
						}

					// crmv@42752
					} elseif (false && $rel->getType() == ModuleRelation::$TYPE_1TON) {

						$fieldname = $rel->fieldname;
						$reltable = $rel->fieldtable;
						$column = $rel->fieldcolumn;
						$relidx = $relModInstance->tab_name_index[$reltable];

						if (!$fieldUsed[$fieldname] && $fieldname && !$joinUsed[$reltable] && $relidx) {

							$join = " LEFT JOIN $reltable ON $reltable.$column = {$table_prefix}_crmentity.crmid";
							$queryGenerator->appendToFromClause($join);

							if (count($rellist) == 1) {
								$joinConditions[] = "$reltable.$relidx = ".intval($rellist[0]);
							} else {
								$joinConditions[] = "$reltable.$relidx in (".implode(',', array_slice($rellist, 0, 50)).")";
							}

							$fieldUsed[$fieldname] = true;
							$joinUsed[$reltable] = true;
						}
					// crmv@42752e

					} elseif ($rel->getType() == ModuleRelation::$TYPE_NTON) {

						$reltable = $moduleInstance->relation_table;
						$relidx = $moduleInstance->relation_table_id;
						$relidx2 = $moduleInstance->relation_table_otherid;
						if (!$joinUsed[$reltable] && $reltable && $relidx && $relidx2) {
							$join = " LEFT JOIN $reltable ON $reltable.$relidx = {$moduleInstance->table_name}.{$moduleInstance->table_index} AND $reltable.$relidx2";
							if (count($rellist) == 1) {
								$join .= " = ".intval($rellist[0]);
							} else {
								$join .= " IN (".implode(',', array_slice($rellist, 0, 50)).")";
							}
							// add special join
							$queryGenerator->appendToFromClause($join);
							$joinConditions[] = "$reltable.$relidx IS NOT NULL";
						}
						//return false;
						$joinUsed[$reltable] = true;
					}
				}
			}

			// direct relation (module is in relatedids)
			if (count($rellist) == 1) {
				$fieldConditions[] = " {$table_prefix}_crmentity.crmid = ".intval($rellist[0]);
			} else {
				$fieldConditions[] = " {$table_prefix}_crmentity.crmid in (".implode(',', array_slice($rellist, 0, 50)).")";
			}

			$extraCond = implode(' OR ', array_filter(array(implode(' OR ', $fieldConditions), implode(' OR ', $joinConditions))));
			if ($extraCond) $appendWhere .= " AND ($extraCond)";
		}

		$appendWhere = trim($appendWhere);
		if ($appendWhere) {
			$queryGenerator->appendToWhereClause($appendWhere);
		} else {
			return false;
		}

		if (empty($searchstr)) {
			// standard condition (no search)
			$this->getMessagePopupLimitedCond($queryGenerator, $module, $relatedIds, $searchstr);
		} else {
			// search condition (no extra conditions)
			$meta = $queryGenerator->getMeta($module);
			$sfields = $queryGenerator->getFields();	// crmv@42752 - search in visible fields
			// add extra fields
			if (!in_array('assigned_user_id', $sfields)) $sfields[] = 'assigned_user_id';
			// TODO: remove relation, checkbox fields
			if (count($sfields) > 0) {
				$input = array('search_fields' => array_flip($sfields), 'search_text'=>$searchstr);
				$queryGenerator->addUserSearchConditions($input);
			}
		}

		return true;
	}

	// by default do nothing
	function getMessagePopupLimitedCond(&$queryGenerator, $module, $relatedIds = array(), $searchstr = '') {
		return '';
	}

	function getMessagePopupOrderBy(&$queryGenerator, $module, $relatedIds = array(), $searchstr = '') {
		global $table_prefix;
		return " ORDER BY {$table_prefix}_crmentity.modifiedtime DESC"; //crmv@174101
	}

	function get_messages_list($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log,$currentModule,$current_user,$adb,$table_prefix;//crmv@203484 removed global singlepane

		//crmv@61173
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
		//crmv@61173e

		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e
		
		// if private mode I see Public, Links and Shared by comments else I see all
		$defOrgSharingPermission = getAllDefaultSharingAction();

		$log->debug("Entering get_messages_list(".$id.") method ...");
		$this_module = $currentModule;

		$related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance($related_module);

		vtlib_setup_modulevars($related_module, $other);
		$singular_modname = vtlib_toSingular($related_module);

		$parenttab = getParentTab();

		if($singlepane_view == true)//crmv@203484 changed to normal bool true, not string 'true'
			$returnset = '&return_module='.$this_module.'&return_action=DetailView&return_id='.$id;
		else
			$returnset = '&return_module='.$this_module.'&return_action=CallRelatedList&return_id='.$id;

		$button = '';
		$button .= '<input type="hidden" name="email_directing_module"><input type="hidden" name="record">';

		if($actions) {
			if(is_string($actions)) $actions = explode(',', strtoupper($actions));
			if(in_array('ADD', $actions) && isPermitted($related_module,1, '') == 'yes') {
				$button .= "<input title='". getTranslatedString('LBL_NEW')." ". getTranslatedString($singular_modname)."' accessyKey='F' class='crmbutton small create' onclick='fnvshobj(this,\"sendmail_cont\");sendmail(\"$this_module\",$id);' type='button' name='button' value='". getTranslatedString('LBL_NEW')." ". getTranslatedString($singular_modname)."'>";
			}
		}

		// select only list fields
		$listfields = $other->list_fields;
		$selfields = '';
		foreach ($listfields as $label=>$finfo) {
			$selfields .= $finfo[0].".".$finfo[1].", ";
		}

		// crmv@38592	crmv@46434	crmv@47243	crmv@49146	crmv@54924	crmv@61173	crmv@63050 crmv@63349	crmv@77877
		if (PerformancePrefs::getBoolean('USE_TEMP_TABLES')) {
			$tableName = substr('vt_msg'.$id,0,29);
			$tmpSelects = "t.messagesid AS id";
		} else {
			$id = intval($id);
			$tableName = $table_prefix.'_messages_tmp_rlist';
			$tmpSelects = "{$current_user->id} as userid, $id as parentid, t.messagesid AS id";
		}

		$query = "SELECT $tmpSelects
			FROM (
				SELECT {$table_prefix}_messages.messagesid
				FROM {$table_prefix}_messagesrel
				INNER JOIN {$table_prefix}_messages ON {$table_prefix}_messages.messagehash = {$table_prefix}_messagesrel.messagehash
				WHERE {$table_prefix}_messagesrel.crmid = ?";
		//crmv@86301
		if ($other->view_related_messages_recipients) {
			$query .= " UNION 
				SELECT messagesid
				FROM {$table_prefix}_messages_recipients 
				WHERE {$table_prefix}_messages_recipients.id = ? AND fieldid > 0";
		}
		//crmv@86301e
		$query .= ") t
			INNER JOIN {$table_prefix}_messages ON {$table_prefix}_messages.messagesid = t.messagesid
			WHERE {$table_prefix}_messages.deleted = 0";

		if (PerformancePrefs::getBoolean('USE_TEMP_TABLES')) {
			if ($adb->isMysql()){
				$adb->query("drop table if exists $tableName",false,'',true);	//crmv@70475
				$query = "create temporary table IF NOT EXISTS $tableName(id int(11) primary key) ignore ".$query;
			} else {
				if (!$adb->table_exist($tableName,true)){
					Vtecrm_Utils::CreateTable($tableName,"id I(11) NOTNULL PRIMARY",true,true);//crmv@198038
				} else {
					//crmv@57238
					($adb->isMssql()) ? $tabName = $adb->datadict->changeTableName($tableName) : $tabName = $tableName; 
					$adb->query("delete from $tabName");
					//crmv@57238e
				}
				$tableName = $adb->datadict->changeTableName($tableName);
				//crmv@57238
				if ($adb->isMssql() || $adb->isOracle()) {
					$query = "insert into $tableName ".$query;
				} else {
					$query = "insert into $tableName ".$query." where not exists (select * from $tableName where $tableName.id = un_table.id)";
				}
				//crmv@57238e
			}
			$result = $adb->pquery($query, array($id,$id));
			$joinCondition = "INNER JOIN {$tableName} on {$tableName}.id = {$table_prefix}_messages.messagesid";
		} else {
			//crmv@129940 - removed change table name
			$adb->pquery("DELETE FROM $tableName WHERE userid = ? AND parentid = ?", array($current_user->id, $id));
			if ($adb->isMysql()){
				$query = "INSERT IGNORE INTO $tableName (userid, parentid, id) ".$query;
			} elseif($adb->isMssql()) {
				$query = "INSERT INTO $tableName (userid, parentid, id) ".$query;
			} else {
				$query = "INSERT INTO $tableName (userid, parentid, id) SELECT * FROM ($query) tt where not exists (
					select * from $tableName 
					where $tableName.userid = {$current_user->id} AND $tableName.parentid = $id AND $tableName.id = tt.id
				)";
			}
			$result = $adb->pquery($query, array($id,$id));
			$joinCondition = "INNER JOIN {$tableName} ON $tableName.userid = {$current_user->id} AND $tableName.parentid = $id AND {$tableName}.id = {$table_prefix}_messages.messagesid";
		}
		
		// search distinct messages (by hash) appling permissions
		if (PerformancePrefs::getBoolean('USE_TEMP_TABLES')) {
			$tmpSelects = "MAX({$table_prefix}_messages.messagesid) AS id";
		} else {
			$id = intval($id);
			$tmpSelects = "{$current_user->id} as userid, $id as parentid, MAX({$table_prefix}_messages.messagesid) AS id";
		}
		// crmv@113604
		$query = "SELECT $tmpSelects
			FROM {$table_prefix}_messages
			LEFT JOIN {$table_prefix}_messages_account ON {$table_prefix}_messages_account.id = {$table_prefix}_messages.account
			$joinCondition
			LEFT JOIN {$table_prefix}_users ON {$table_prefix}_users.id = {$table_prefix}_messages.messagesid
			LEFT JOIN {$table_prefix}_groups ON {$table_prefix}_groups.groupid = {$table_prefix}_messages.messagesid";
		// crmv@113604e
		if($defOrgSharingPermission[$rel_tab_id] != 3) {
			$query .= " LEFT JOIN {$table_prefix}_messages_sfolders 
							ON {$table_prefix}_messages_sfolders.userid = {$table_prefix}_messages.smownerid 
							AND {$table_prefix}_messages_sfolders.accountid = {$table_prefix}_messages.account 
							AND {$table_prefix}_messages_sfolders.folder = {$table_prefix}_messages.folder";
		}
		$query .= " WHERE {$table_prefix}_messages.deleted = 0";
		// crmv@63349e
		
		if (!$other->view_related_messages_drafts) $query .= " AND {$table_prefix}_messages.draft <> 1";	//crmv@84628 crmv@141429

		/*
		 * $defOrgSharingPermission[$rel_tab_id]
		 * 0 Public : all
		 * 3 Private : mine and subordinates
		 * 8 Assigned : only mine
		 */
		if($defOrgSharingPermission[$rel_tab_id] == 3 || $defOrgSharingPermission[$rel_tab_id] == 8) {
			$query .= " AND ({$table_prefix}_messages.mvisibility = 'Public' ";
			$specialFolders = $other->getAllSpecialFolders('Trash');
			if (!empty($specialFolders)) {
				$related_ids = $other->getRelatedModComments();
				if (!empty($related_ids)) {
					//TODO usare una tabella temporanea o filtrare il numero di $related_ids
					$query .= " OR {$table_prefix}_messages.messagesid IN (".implode(',',$related_ids).")";
				}
			}
			// apply $Messages_share_read_permission and $Messages_share_write_permission
			//crmv@91433
			if (is_admin($current_user)) {
				$tutables = TmpUserTables::getInstance();
				$tutables->cleanTmpForUser($current_user->id);
				$tutables->generateTmpForUser($current_user->id);
			}
			//crmv@91433e
			// crmv@63349
			$sharingRuleInfoVariable = $related_module.'_share_read_permission';
			$sharingRuleInfo = $$sharingRuleInfoVariable;
			if (PerformancePrefs::getBoolean('USE_TEMP_TABLES', true)) {
				$tableName = 'vt_tmp_u'.$current_user->id;
				if(!empty($sharingRuleInfo) && (count($sharingRuleInfo['ROLE']) > 0 ||
					count($sharingRuleInfo['GROUP']) > 0
					 || count($sharingRuleInfo['USR']) > 0)) {
					$tableName = $tableName.'_t'.$rel_tab_id;
				}elseif(!empty($scope)) {
					$tableName .= '_t'.$rel_tab_id;
				}
				if (empty($current_user_parent_role_seq)) {
					$user_role = $current_user->column_fields['roleid'];
					$user_role_info = getRoleInformation($user_role);
					$current_user_parent_role_seq = $user_role_info[$user_role][1];
				}
				if (empty($current_user_groups)) {
					$userGroupFocus = new GetUserGroups();
					$userGroupFocus->getAllUserGroups($current_user->id);
					$current_user_groups = $userGroupFocus->user_groups;
				}
				$other->setupTemporaryTable($tableName, $rel_tab_id, $current_user, $current_user_parent_role_seq, $current_user_groups);
				if ($adb->isMssql()) $tableName = $adb->datadict->changeTableName($tableName);	//crmv@57238 
				$query .= " OR {$table_prefix}_messages.smownerid IN (select id from $tableName)";
			} else {
				$tableName = $table_prefix.'_tmp_users';
				$sharedTabId = null;
				if(!empty($sharingRuleInfo) && (count($sharingRuleInfo['ROLE']) > 0 ||
						count($sharingRuleInfo['GROUP']) > 0
						|| count($sharingRuleInfo['USR']) > 0)) {
					$tableName = $table_prefix.'_tmp_users_mod';
					$sharedTabId = $rel_tab_id;
				}elseif($module == 'Calendar') { // crmv@105149 - remove scope condition
					$tableName = $table_prefix.'_tmp_users_cal';
				}

				$query .= " OR {$table_prefix}_messages.smownerid = {$current_user->id}";
				//crmv@160797
				if ($defOrgSharingPermission[$rel_tab_id] == 3) {
					if ($sharedTabId > 0) {
						$query .= " OR {$table_prefix}_messages.smownerid IN (select subuserid from $tableName WHERE userid = {$current_user->id} AND tabid = $sharedTabId)";
					} else {
						$query .= " OR {$table_prefix}_messages.smownerid IN (select subuserid from $tableName WHERE userid = {$current_user->id})";
					}
				} elseif ($defOrgSharingPermission[$rel_tab_id] == 8) {
					if ($sharedTabId > 0) {
						$query .= " OR {$table_prefix}_messages.smownerid IN (select subuserid from $tableName WHERE userid = {$current_user->id} AND tabid = $sharedTabId)";
					}
				}
				//crmv@160797e
			}
			// crmv@63349e
			$query .= ")";	//crmv@93981
		}
		$query .= " GROUP BY {$table_prefix}_messages.messagehash";	//crmv@93981
		
		// select finally the messages
		if (PerformancePrefs::getBoolean('USE_TEMP_TABLES')) {
			$tableName = substr('vt_msg_def'.$id,0,29);
		} else {
			$tableName = $table_prefix.'_messages_deflist';
		}
		if (PerformancePrefs::getBoolean('USE_TEMP_TABLES')) {
			if ($adb->isMysql()){
				$adb->query("drop table if exists $tableName",false,'',true);	//crmv@70475
				$query = "create temporary table IF NOT EXISTS $tableName(id int(11) primary key) ignore ".$query;
			} else {
				if (!$adb->table_exist($tableName,true)){
					Vtecrm_Utils::CreateTable($tableName,"id I(11) NOTNULL PRIMARY",true,true);//crmv@198038
				} else {
					//crmv@57238
					($adb->isMssql()) ? $tabName = $adb->datadict->changeTableName($tableName) : $tabName = $tableName; 
					$adb->query("delete from $tabName");
					//crmv@57238e
				}
				$tableName = $adb->datadict->changeTableName($tableName);
				//crmv@57238
				if ($adb->isMssql() || $adb->isOracle()) {
					$query = "insert into $tableName ".$query;
				} else {
					$query = "insert into $tableName ".$query." where not exists (select * from $tableName where $tableName.id = un_table.id)";
				}
				//crmv@57238e
			}
			$result = $adb->pquery($query, array($id,$id));
			$joinCondition = "INNER JOIN {$tableName} on {$tableName}.id = {$table_prefix}_messages.messagesid";
		} else {
			//crmv@129940 - removed change table name
			$adb->pquery("DELETE FROM $tableName WHERE userid = ? AND parentid = ?", array($current_user->id, $id));
			if ($adb->isMysql()){
				$query = "INSERT IGNORE INTO $tableName (userid, parentid, id) ".$query;
			} elseif($adb->isMssql()) {
				$query = "INSERT INTO $tableName (userid, parentid, id) ".$query;
			} else {
				$query = "INSERT INTO $tableName (userid, parentid, id) SELECT * FROM ($query) tt where not exists (
					select * from $tableName 
					where $tableName.userid = {$current_user->id} AND $tableName.parentid = $id AND $tableName.id = tt.id
				)";
			}
			$result = $adb->pquery($query, array($id,$id));
			$joinCondition = "INNER JOIN {$tableName} ON $tableName.userid = {$current_user->id} AND $tableName.parentid = $id AND {$tableName}.id = {$table_prefix}_messages.messagesid";
		}
		$query = "SELECT
			case when ({$table_prefix}_users.user_name not like '') then {$table_prefix}_users.user_name else {$table_prefix}_groups.groupname end as user_name,
			$selfields
			{$table_prefix}_messages.modifiedtime,
			{$table_prefix}_messages.messagesid as \"crmid\", {$table_prefix}_messages.smownerid
			FROM {$table_prefix}_messages
			{$joinCondition}
			LEFT JOIN {$table_prefix}_users ON {$table_prefix}_users.id = {$table_prefix}_messages.smownerid
			LEFT JOIN {$table_prefix}_groups ON {$table_prefix}_groups.groupid = {$table_prefix}_messages.smownerid";
		if($defOrgSharingPermission[$rel_tab_id] != 3) {
			$query .= " LEFT JOIN {$table_prefix}_messages_sfolders 
							ON {$table_prefix}_messages_sfolders.userid = {$table_prefix}_messages.smownerid 
							AND {$table_prefix}_messages_sfolders.accountid = {$table_prefix}_messages.account 
							AND {$table_prefix}_messages_sfolders.folder = {$table_prefix}_messages.folder";
		}
		$query .= " WHERE {$table_prefix}_messages.deleted = 0";
		// crmv@38592e	crmv@46434e	crmv@47243e	crmv@49146e	crmv@54924e	crmv@61173e	crmv@63050e	crmv@77877e
		
		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_messages_list method ...");
		return $return_value;
	}
	//crmv@37004e //crmv@171021e

	// crmv@38592
	function get_newsletter_emails($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $currentModule, $current_user;//crmv@203484 removed global singlepane
		global $adb, $table_prefix;

		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e

		$campaignsInst = CRMEntity::getInstance('Campaigns');

		if($singlepane_view == true) {//crmv@203484 changed to normal bool true, not string 'true'
			$returnset = '&return_module='.$currentModule.'&return_action=DetailView&return_id='.$id;
		} else {
			$returnset = '&return_module='.$currentModule.'&return_action=CallRelatedList&return_id='.$id;
		}
		$button = '';
		$title = 'Newsletter Emails';	//crmv@49823
		VteSession::set(strtolower($title)."_listquery", '');
		$query =
			"SELECT tbl_s_newsletter_queue.*
			FROM {$table_prefix}_newsletter
			INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = {$table_prefix}_newsletter.newsletterid
			INNER JOIN tbl_s_newsletter_queue ON tbl_s_newsletter_queue.newsletterid = {$table_prefix}_newsletter.newsletterid
			LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
			LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
			WHERE ".$table_prefix."_crmentity.deleted = 0 AND tbl_s_newsletter_queue.status = 'Sent' AND tbl_s_newsletter_queue.crmid = {$id}";

		$return_value = $campaignsInst->GetStatisticList($currentModule, $title, $query, $button, $returnset, false);

		if($return_value == null) $return_value = Array();

		$return_value['CUSTOM_BUTTON'] = $button;
		return $return_value;
	}
	function get_newsletter_emails_count($id, $cur_tab_id, $rel_tab_id) {
		global $adb, $onlyquery;
		$onlyquery = true;
		$this->get_newsletter_emails($id, $cur_tab_id, $rel_tab_id);
		$title = 'Newsletter Emails';
		$query = VteSession::get(strtolower($title)."_listquery");
		if (!empty($query)) {
			$count_query = mkCountQuery($query);
			$count_result = $adb->query($count_query);
			$count = $adb->query_result($count_result,0,"count");
		}
		return $count;
	}
	// crmv@38592e

	// crmv@38798
	function countAllRecordsInFolder($module, $folderid) {
		global $adb, $table_prefix;

		// find columnname
		//crmv@66897
		//$res = $adb->pquery("select tabid,columnname,tablename from {$table_prefix}_field where uitype = ?", array(26));
		$res = $adb->pquery("SELECT columnname,tablename FROM {$table_prefix}_field INNER JOIN {$table_prefix}_tab ON {$table_prefix}_tab.tabid = {$table_prefix}_field.tabid WHERE uitype = ? AND name = ?", array(26,$module));
		$fieldinfo = $adb->FetchByAssoc($res, -1, false);
		
		$focus = CRMEntity::getInstance($module);
		$join = " INNER JOIN {$table_prefix}_crmentity ON {$fieldinfo['tablename']}.$focus->table_index = {$table_prefix}_crmentity.crmid";
		$res = $adb->pquery("select count(*) as cnt from {$fieldinfo['tablename']} $join where {$table_prefix}_crmentity.deleted = 0 and {$fieldinfo['columnname']} = ?", array($folderid));
		//crmv@66897 e
		if ($res) {
			return $adb->query_result_no_html($res, 0, 'cnt');
		}
		return false;
	}
	// crmv@38798e

	//crmv@3085m
	function getFieldsNoCard() { return array(); }

	function getEntityPreview($id,$module='') {
		global $adb, $table_prefix, $current_user;
		//crmv@158871
		if (empty($module)) $module = getSalesEntityType($id,true);
		$name = getEntityName($module,$id,true);
		//crmv@158871e
		$details = array();
		$focus = CRMEntity::getInstance($module);
		if(!isRecordExists($id)) return '';
		$focus->id = $id;
		if ($module == 'Calendar') {
			$activitytype = getActivityType($id);
			($activitytype == 'Task') ? $mod = $module : $mod = 'Events';
			($activitytype == 'Task') ? $img_check = 'themes/images/modulesimg/Tasks.png' : $img_check = 'themes/images/modulesimg/Calendar.png';
		} else {
			$mod = $module;
			$img_check = 'themes/images/modulesimg/'.$mod.'.png';
		}
		($module == 'Events') ? $link_module = 'Calendar' : $link_module = $module; //crmv@162866
		// crmv@140887
		if (file_exists($img_check)) {
			$RV = ResourceVersion::getInstance();
			$img = $RV->getResource($img_check);
		}
		// crmv@140887e
		$preview = array(
			'id'=>$id,
			'module'=>$module,
			'modulelbl'=>getSingleModuleName($mod),
			'name'=>$name,
			'img'=>$img,
			//crmv@162866
			'link_module'=>$link_module,
			'link'=>"index.php?module=$link_module&action=DetailView&record=$id",
			//crmv@162866e
		);
		if ($focus->retrieve_entity_info($id,$mod, false)) return ''; // crmv@38592
		//crmv@77702 crmv@158871
		$private_event = ($module == 'Calendar' && $focus->column_fields['visibility'] == 'Private' && !is_admin($current_user) && $focus->column_fields['assigned_user_id'] != $current_user->id && isCalendarInvited($current_user->id,$id,true) == 'no');
		if ($private_event) {
			$preview['name'] = getTranslatedString('Private Event','Calendar');
		}
		//crmv@77702e crmv@158871e
		$qcreate_array = QuickCreate($mod);
		$query = "select fieldname from {$table_prefix}_entityname where modulename = ?";
		$result = $adb->pquery($query, array($module));
		if ($result && $adb->num_rows($result) > 0) {
			if(strpos($adb->query_result($result,0,'fieldname'),',') !== false) {
				$fieldlists = explode(',',$adb->query_result($result,0,'fieldname'));
			} else {
				$fieldlists = array($adb->query_result($result,0,'fieldname'));
			}
			foreach($fieldlists as $field) {
				unset($qcreate_array['data'][$field]);
			}
		}
		$fieldnames = array_keys($qcreate_array['data']);
		if (!empty($fieldnames)) {
			$tabid = getTabid($mod);
			$result = $adb->pquery('select * from '.$table_prefix.'_field where tabid = ? and fieldname in ('.generateQuestionMarks($fieldnames).') order by quickcreatesequence',array($tabid,$fieldnames));
			if ($result && $adb->num_rows($result) > 0) {
				$fields_no_card = $this->getFieldsNoCard();
				while($row=$adb->fetchByAssoc($result)) {
					if (!empty($fields_no_card) && in_array($row['fieldname'],$fields_no_card)) continue;
					$info = getDetailViewOutputHtml($row['uitype'],$row['fieldname'],$row['fieldlabel'],$focus->column_fields,$row['generatedtype'],$row['tabid'],$module);
					if ($info[1] != '' && !is_array($info[1])) {
						//crmv@77702 crmv@158871
						if ($private_event && !in_array($row['fieldname'],array('assigned_user_id','date_start','time_start','time_end','due_date','activitytype','visibility','duration_hours','duration_minutes'))) {
							$info[1] = "<font color='red'>".getTranslatedString('LBL_NOT_ACCESSIBLE')."</font>";
						}
						//crmv@77702e crmv@158871e
						if ($row['uitype'] == 19) {	//TODO: controllo pi� accurato
							$value = $info[1];
							$value = textlength_check($value);
							$details[$row['fieldname']] = array('label'=>$info[0],'value'=>$value);
						} else {
							$details[$row['fieldname']] = array('label'=>$info[0],'value'=>strip_tags($info[1]));
						}
					}
				}
			}
		}
		$preview['details'] = $details;
		return $preview;
	}
	//crmv@3085me

	function get_activities($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log,$currentModule,$current_user,$table_prefix;
		$log->debug("Entering get_activities(".$id.") method ...");
		$this_module = $currentModule;

		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e

        $related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance('Activity');
        vtlib_setup_modulevars($related_module, $other);
		$singular_modname = vtlib_toSingular($related_module);

		$parenttab = getParentTab();

		if($singlepane_view == true)//crmv@203484 changed to normal bool true, not string 'true'
			$returnset = '&return_module='.$this_module.'&return_action=DetailView&return_id='.$id;
		else
			$returnset = '&return_module='.$this_module.'&return_action=CallRelatedList&return_id='.$id;

		$button = '';
		$button .= '<input type="hidden" name="activity_mode">';
		if($actions) {
			$button .= $this->get_related_buttons($currentModule, $id, $related_module, $actions); // crmv@43864
		}

		// crmv@64325
		$setypeCond = '';
		if (PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) {
			$setypeCond = "AND {$table_prefix}_crmentity.setype = 'Calendar'";
		}

		$query = "SELECT {$table_prefix}_activity.*,
			{$table_prefix}_contactdetails.lastname,
			{$table_prefix}_contactdetails.firstname,
			{$table_prefix}_crmentity.crmid,
			{$table_prefix}_crmentity.smownerid,
			{$table_prefix}_crmentity.modifiedtime,
			case when ({$table_prefix}_users.user_name is not null) then {$table_prefix}_users.user_name else {$table_prefix}_groups.groupname end as user_name
			FROM {$table_prefix}_activity
			INNER JOIN {$table_prefix}_activitycf
				ON {$table_prefix}_activitycf.activityid = {$table_prefix}_activity.activityid
			INNER JOIN {$table_prefix}_seactivityrel
				ON {$table_prefix}_seactivityrel.activityid = {$table_prefix}_activity.activityid
			INNER JOIN {$table_prefix}_crmentity
				ON {$table_prefix}_crmentity.crmid = {$table_prefix}_activity.activityid
			LEFT JOIN {$table_prefix}_cntactivityrel
				ON {$table_prefix}_cntactivityrel.activityid = {$table_prefix}_activity.activityid
			LEFT JOIN {$table_prefix}_contactdetails
	       		ON {$table_prefix}_contactdetails.contactid = {$table_prefix}_cntactivityrel.contactid
			LEFT JOIN {$table_prefix}_users
				ON {$table_prefix}_users.id = {$table_prefix}_crmentity.smownerid
			LEFT OUTER JOIN {$table_prefix}_recurringevents
				ON {$table_prefix}_recurringevents.activityid = {$table_prefix}_activity.activityid
			LEFT JOIN {$table_prefix}_groups
				ON {$table_prefix}_groups.groupid = {$table_prefix}_crmentity.smownerid
			WHERE {$table_prefix}_seactivityrel.crmid = ".$id."
				AND {$table_prefix}_crmentity.deleted = 0 $setypeCond
				AND (".$table_prefix."_activity.activitytype = 'Task' OR ".$table_prefix."_activity.activitytype IN ".getActivityTypeValues('all','format_sql').")";
		
		// crmv@64325e
		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_activities method ...");
		return $return_value;
	}

	function getFriendlyDate($date) {

		$date = strtotime($date);

		//calculating diff (today - date) in days
		$diff = (strtotime(date('Y-m-d')) - strtotime(date('Y-m-d',$date))) / 3600 / 24;

		$friendlydate = '';
		if ($diff == 0) {
			$friendlydate = date('H:i',$date);
		} elseif($diff == 1) {
			$friendlydate = getTranslatedString('Yesterday','CustomView').' '.date('H:i',$date);
		} elseif($diff <= 6) {
			$friendlydate = getTranslatedString('LBL_DAY'.date('w', $date), 'Calendar')." ".date('H:i',$date);
		} else {
			$friendlydate = getDisplayDate(date('Y-m-d H:i',$date));
		}

		return $friendlydate;
	}

	function getFullDate($date) {
		return getTranslatedString('LBL_DAY'.date('w',strtotime($date)),'Calendar').' '.getDisplayDate($date);
	}
	//crmv@46974

	//crmv@46974 crmv@63349
	function generateRelTempName($module,$secmodule,$id=''){
		if (PerformancePrefs::getBoolean('USE_TEMP_TABLES')) {
			return $this->generateRelTempName_tmp($module,$secmodule,$id);
		} else {
			return $this->generateRelTempName_notmp($module,$secmodule,$id);
		}
	}
	
	function generateRelTempName_notmp($module,$secmodule,$id=''){
		global $table_prefix;
		return $table_prefix.'_tmp_users_mod_rel';
	}
	
	function setupTemporaryRelTable($module,$secmodule,$id='',$relationinfo=Array()) {
		if (PerformancePrefs::getBoolean('USE_TEMP_TABLES')) {
			return $this->setupTemporaryRelTable_tmp($module,$secmodule,$id,$relationinfo);
		} else {
			return $this->setupTemporaryRelTable_notmp($module,$secmodule,$id,$relationinfo);
		}
	}
	
	function setupTemporaryRelTable_notmp($module,$secmodule,$id=0,$relationinfo=Array()) {
		global $current_user, $table_prefix;
		$db = PearDatabase::getInstance();
		
		$id = intval($id);
		$tabid = intval(getTabId($module));
		$reltabid = intval(getTabId($secmodule));
		
		// crmv@125816
		if (is_array($relationinfo) && $relationinfo['reltab'] == $this->relation_table_ord) {
			if ($id > 0) {
				if ($relationinfo['direction'] == 'inverse') {
					$query = "SELECT {$current_user->id} as userid, $tabid as tabid, $reltabid as reltabid, $id as parentid, relcrmid, crmid
					FROM {$this->relation_table_ord} WHERE relmodule = ? AND module = ? and relcrmid = ?";
				} else {
					$query = "SELECT {$current_user->id} as userid, $tabid as tabid, $reltabid as reltabid, $id as parentid, crmid,relcrmid 
					FROM {$this->relation_table_ord} WHERE module = ? AND relmodule = ? and crmid = ?";
				}
				$params = Array($module,$secmodule,$id);
			} else {
				if ($relationinfo['direction'] == 'inverse') {
					$query = "SELECT {$current_user->id} as userid, $tabid as tabid, $reltabid as reltabid, $id as parentid, relcrmid,crmid
					FROM {$this->relation_table_ord} WHERE relmodule = ? AND module = ?";
				} else {
					$query = "SELECT {$current_user->id} as userid, $tabid as tabid, $reltabid as reltabid, $id as parentid, crmid,relcrmid 
					FROM {$this->relation_table_ord} WHERE module = ? AND relmodule = ?";
				}
				$params = Array($module,$secmodule);
			}
		} elseif (empty($relationinfo) || $relationinfo['reltab'] == $table_prefix.'_crmentityrel') {
		// crmv@125816e
			if ($id > 0) {
				$query = "SELECT {$current_user->id} as userid, $tabid as tabid, $reltabid as reltabid, $id as parentid, crmid,relcrmid 
				FROM {$table_prefix}_crmentityrel WHERE module = ? AND relmodule = ? and crmid = ?
				UNION ALL
				SELECT {$current_user->id} as userid, $tabid as tabid, $reltabid as reltabid, $id as parentid, relcrmid,crmid 
				FROM {$table_prefix}_crmentityrel WHERE relmodule = ? AND module = ? and relcrmid = ?";
				$params = Array($module,$secmodule,$id,$module,$secmodule,$id);
			} else {
				$query = "SELECT {$current_user->id} as userid, $tabid as tabid, $reltabid as reltabid, $id as parentid, crmid,relcrmid 
				FROM {$table_prefix}_crmentityrel WHERE module = ? AND relmodule = ?
				UNION ALL
				SELECT {$current_user->id} as userid, $tabid as tabid, $reltabid as reltabid, $id as parentid, relcrmid, crmid
				FROM {$table_prefix}_crmentityrel WHERE relmodule = ? AND module = ?";
				$params = Array($module,$secmodule,$module,$secmodule);
			}
		} else{
			return $relationinfo['reltab'];
		}
		
		$tmodreltables = TmpUserModRelTables::getInstance();
		
		// clear old data
		$tmodreltables->cleanTmpForModuleUserId($module, $secmodule, $current_user->id, $id);
		
		$tableName = $tmodreltables->tmpTable;
		//crmv@129940 - removed change table name
		
		// insert		
		if ($db->isMysql()){
			$query = "INSERT IGNORE INTO $tableName (userid, tabid, reltabid, parentid, crmid, relcrmid) ".$query;
		} elseif ($db->isMssql()) {
			$query = "INSERT INTO $tableName (userid, tabid, reltabid, parentid, crmid, relcrmid) ".$query;
		} else {
			$cond = $tmodreltables->getJoinCondition($module, $secmodule, $current_user->id, $id, 'tt.crmid');
			$query = "INSERT INTO $tableName (userid, tabid, reltabid, parentid, crmid, relcrmid) SELECT * FROM (".$query.
				") tt WHERE NOT EXISTS (select * from $tableName where $cond)";
		}

		$result = $db->pquery($query,$params);
		
		return $tableName;
	}	
	// crmv@63349e

	function generateRelTempName_tmp($module,$secmodule,$id=''){ // crmv@63349
		global $current_user;		
		$tablename =  'vt_tmp_u'.$current_user->id.'rt'.getTabId($module)."rt2".getTabId($secmodule)."_r".$id;			
		return substr($tablename,0,29);	
	}

	function setupTemporaryRelTable_tmp($module,$secmodule,$id='',$relationinfo=Array()){ // crmv@63349
		global $table_prefix;
		$db = PearDatabase::getInstance();
		// crmv@125816
		if (is_array($relationinfo) && $relationinfo['reltab'] == $this->relation_table_ord) {
			if ($id != ''){
				if ($relationinfo['direction'] == 'inverse') {
					$query = "SELECT relcrmid, crmid FROM {$this->relation_table_ord} WHERE relmodule = ? AND module = ? and relcrmid = ?";
				} else {
					$query = "SELECT crmid,relcrmid FROM {$this->relation_table_ord} WHERE module = ? AND relmodule = ? and crmid = ?";
				}
				$params = Array($module,$secmodule,$id);
			}
			else{
				if ($relationinfo['direction'] == 'inverse') {
					$query = "SELECT relcrmid, relcrmid FROM {$this->relation_table_ord} WHERE relmodule = ? AND module = ?";
				} else {
					$query = "SELECT crmid,relcrmid FROM {$this->relation_table_ord} WHERE module = ? AND relmodule = ?";
				}
				$params = Array($module,$secmodule);
			}
		} elseif (empty($relationinfo) || $relationinfo['reltab'] == $table_prefix.'_crmentityrel'){
		// crmv@125816e
			if ($id != ''){
				$query = "SELECT crmid,relcrmid FROM {$table_prefix}_crmentityrel WHERE module = ? AND relmodule = ? and crmid = ?".
				" UNION".
				" SELECT relcrmid,crmid FROM {$table_prefix}_crmentityrel WHERE relmodule = ? AND module = ? and relcrmid = ?";
				$params = Array($module,$secmodule,$id,$module,$secmodule,$id);
			}
			else{
				$query = "SELECT crmid,relcrmid FROM {$table_prefix}_crmentityrel WHERE module = ? AND relmodule = ?".
				" UNION".
				" SELECT relcrmid,crmid FROM {$table_prefix}_crmentityrel WHERE relmodule = ? AND module = ?";
				$params = Array($module,$secmodule,$module,$secmodule);
			}
		}
		else{
			return $relationinfo['reltab'];
		}
		$tableName = $this->generateRelTempName($module,$secmodule,$id);
		if ($db->isMysql()){
			$db->query("drop table if exists $tableName",false,'',true);	//crmv@54900 crmv@70475
			$query = "create temporary table IF NOT EXISTS $tableName(crmid int(19),relcrmid int (19),PRIMARY KEY (crmid,relcrmid)) ignore ".$query;
			$result = $db->pquery($query,$params);
		}
		//crmv@57238
		elseif($db->isMssql()){
			if (!$db->table_exist($tableName,true)){
				Vtecrm_Utils::CreateTable($tableName,"crmid I(19) key,relcrmid I(19) key",true,true);//crmv@198038
			} else {
				$tabName = $db->datadict->changeTableName($tableName);
				$db->query("delete from $tabName");
			}
			$tableName = $db->datadict->changeTableName($tableName);
			$query = "insert into $tableName ".$query;
			$result = $db->pquery($query,$params);
		}
		//crmv@57238e
		else {
			if (!$db->table_exist($tableName,true)){
				Vtecrm_Utils::CreateTable($tableName,"crmid I(19) key,relcrmid I(19) key",true,true);
			} else {
				$db->query("delete from $tableName");	//crmv@54900
			}
			$tableName = $db->datadict->changeTableName($tableName);
			$query = "insert into $tableName ".
			$query."where not exists (select * from $tableName where $tableName.id = un_table.id)";
			$result = $db->pquery($query,$params);
		}
		return $tableName;
	}
	//crmv@46974 e
	
	//crmv@3086m
	function relatedlist_preview_link($module, $entity_id, $current_module, $header, $relation_id) {
		return ""; // crmv@104568 - removed
		//return "loadSummary('".getTranslatedString('LBL_SHOW_DETAILS')."','{$module}','{$entity_id}','tbl_{$current_module}_".str_replace(' ','',$header)."','{$relation_id}')";
	}
	//crmv@3086me
	
	// crmv@49398
	function hasFolders() {
		// very stupid way to check it
		return array_key_exists('folderid', $this->column_fields);
	}
	// crmv@49398e
	
	function customTemporaryTable($tableName, $query) {
		$db = PearDatabase::getInstance();
		if ($db->isMysql()){
			$db->query("drop table if exists $tableName",false,'',true);	//crmv@70475
			$result = $db->query("create temporary table IF NOT EXISTS $tableName(id int(11) primary key) ignore ".$query);
		} else {
			if (!$db->table_exist($tableName,true)){
				Vtecrm_Utils::CreateTable($tableName,"id I(11) NOTNULL PRIMARY",true,true);
			} else {
				//$db->query("delete from $tableName"); //crmv@61278 - commented out because we do the same below
			}
			$tableName = $db->datadict->changeTableName($tableName);
			$db->query("delete from $tableName");
			$result = $db->query("insert into $tableName ".$query);
		}
		return $result;
	}

	// crmv@104568
	function getStdDetailTabs() {
		$stdTabs = array();
		// crmv@107341 - shitty calendar!!!! :(
		if ($this->modulename == 'Activity') {
			$moduleName = ($this->column_fields['activitytype'] == 'Task' ? 'Calendar' : 'Events');
		} else {
			$moduleName	= $this->modulename;
		}
		$mod = Vtecrm_Module::getInstance($moduleName);
		// crmv@107341e
		$tabs = Vtecrm_Panel::getAllForModule($mod, $this->id); //crmv@150751
		if (is_array($tabs)) {
			foreach ($tabs as $tabInst) {
				$stdTabs[] = array(
					'panelid' => $tabInst->id,
					'label' => getTranslatedString($tabInst->label),
					'href' => '',
					'onclick' => "changeDetailTab('{$moduleName}', '{$this->id}', {$tabInst->id}, this)",
				);
			}
		}
		return $stdTabs;
	}
	
	function getDetailTabs($extraTabs = true) {
		$stdTabs = $this->getStdDetailTabs() ?: array();
		if ($extraTabs) {
			$extraTabs = $this->getExtraDetailTabs() ?: array();
		} else {
			$extraTabs = array();
		}
		
		$alltabs = array_merge($stdTabs, $extraTabs);
		return $alltabs;
	}
	
	function getEditTabs() {
		$tabs = $this->getStdDetailTabs();
		foreach ($tabs as &$tab) {
			$tab['onclick'] = "changeEditTab('{$this->modulename}', '{$this->id}', {$tab['panelid']}, this)";
		}
		return $tabs;
	}
	// crmv@104568e

	// crmv@83228	crmv@101506 crmv@107341
	function getExtraDetailTabs() {
		global $adb, $table_prefix, $app_strings, $currentModule;
		
		if ($this->modulename == 'Activity') {
			$moduleName = ($this->column_fields['activitytype'] == 'Task' ? 'Calendar' : 'Events');
		} else {
			$moduleName	= $this->modulename;
		}
		
		$return = array();
		if ($this->has_detail_charts && vtlib_isModuleActive('Charts')) {
			$return[] = array('label'=>getTranslatedString('Charts','Charts'),'href'=>'','onclick'=>"changeDetailTab('{$moduleName}', '{$this->id}', 'detailCharts', this)");
		}
		if (vtlib_isModuleActive('Processes')) { //crmv@176621
			$return[] = array('label'=>getTranslatedString('Process Graph','Processes'),'href'=>'','onclick'=>"changeDetailTab('{$moduleName}', '{$this->id}', 'ProcessGraph', this)");
			$return[] = array('label'=>getTranslatedString('Process history','Processes'),'href'=>'','onclick'=>"changeDetailTab('{$moduleName}', '{$this->id}', 'ProcessHistory', this)"); // crmv@188364
		}
		//crmv@104566 crmv@164120
		if (vtlib_isModuleActive('ChangeLog')) {
			// if module ChangeLog is active and linked to the current add tab History
			$clog = ChangeLog::getInstance();
			if ($clog->isEnabled($this->modulename)) {
				$return[] = array('label'=>getTranslatedString('LBL_HISTORY'),'href'=>'','onclick'=>"changeDetailTab('{$moduleName}', '{$this->id}', 'HistoryTab', this)");
			}
		}
		//crmv@104566e crmv@164120
		return $return;
	}
	// crmv@107341e
	
	function getExtraDetailBlock() {
		/*
		 * put the content in a div like this: <div id="ProcessGraph" class="detailTabsMainDiv" style="display:none">...</div>
		 */
		global $mod_strings, $app_strings, $currentModule, $current_user, $theme;
		
		$extraBlock = '';

		if ($this->has_detail_charts && vtlib_isModuleActive('Charts')) {
			$smarty = new VteSmarty();
	
			$smarty->assign('APP', $app_strings);
			$smarty->assign('MOD', $mod_strings);
			$smarty->assign('MODULE', $currentModule);
			$smarty->assign('THEME', $theme);
			$smarty->assign('ID', $this->id);
			
			// charts
			$charts = $this->generateCharts();
			$smarty->assign('CHARTS', $charts);
	
			$extraBlock .= $smarty->fetch('DetailViewCharts.tpl');
		}
		
		//crmv@176621 removed crmv@149529 generateProcessGraph
		
		return $extraBlock;
	}
	
	function generateCharts() {
		// if the charts are enabled, you must implement this function in the extended class in order to draw the charts
	}
	
	// crmv@83228e	crmv@101506e
	
	//crmv@149529 crmv@176621 crmv@188364
	function getModulesRelatedToProcesses() {
		global $adb, $table_prefix;
		$cache = Cache::getInstance('relatedToProcesses');
		$relatedToProcesses = $cache->get();
		if ($relatedToProcesses === false) {
			$relatedToProcesses = array();
			$processesInstance = Vtecrm_Module::getInstance('Processes');
			$fieldInstance = Vtecrm_Field::getInstance('related_to',$processesInstance);
			$result = $adb->pquery("SELECT relmodule FROM {$table_prefix}_fieldmodulerel WHERE fieldid=?", Array($fieldInstance->id));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					$relatedToProcesses[] = $row['relmodule'];
				}
			}
			$cache->set($relatedToProcesses);
		}
		return $relatedToProcesses;
	}
	function generateProcessTab($module, $record, $mode) {
		global $app_strings;
		$return = "{$app_strings['LBL_NO_M']} {$app_strings['LBL_RECORDS']} {$app_strings['LBL_FOUND']}";
		$relatedToProcesses = $this->getModulesRelatedToProcesses();
		if (in_array($module, $relatedToProcesses)) {
			$rm = RelationManager::getInstance();
			$ids = $rm->getRelatedIds($module, $record, 'Processes');	//TODO order by createdtime desc
			if (!empty($ids)) {
				require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
				$PMUtils = ProcessMakerUtils::getInstance();
				$relatedTo = $PMUtils->getProcessRelatedTo($record,'processesid');	//crmv@93990
				$selectionProcesses = array();
				$processesid = $ids[0];
				//crmv@150751
				$focus = CRMEntity::getInstance('Processes');
				foreach($ids as $id) {
					if (!empty($relatedTo) && $relatedTo == $id) {
						$chk_val = 'selected';
						$processesid = $id;
					} else {
						$chk_val = '';
					}
					$version = ' ('.strtolower(substr(getTranslatedString('VTLIB_LBL_PACKAGE_VERSION','Settings'),0,1)).'. '.$focus->getProcessVersion($id).')';
					$selectionProcesses[] = array(getEntityName('Processes',$id,true).$version, $id, $chk_val);
				}
				//crmv@150751e
				if (!empty($processesid)) $focus->retrieve_entity_info($processesid,'Processes');
				$return = $focus->getProcessBlock($mode, $selectionProcesses);
			}
		}
		return $return;
	}
	//crmv@149529e crmv@176621e crmv@188364e
	
	//crmv@104310
	function getRecordName() {
		$recordName = getEntityName($this->modulename, $this->id, true); // crmv@104435
		$recordName = str_replace(array("\n", "\r"), ' ', $recordName);
		return $recordName;
	}
	//crmv@104310e
	
	//crmv@154715
	function isBUMCInstalled($module='') {
		global $adb, $table_prefix;
		$query = "SELECT * FROM {$table_prefix}_field WHERE fieldname = ?";
		$params = array('bu_mc');
		if (!empty($module)) {
			$moduleInstance = Vtecrm_Module::getInstance($module);
			$query .= ' and tabid = ?';
			$params[] = $moduleInstance->id;
		}
		$result = $adb->pquery($query,$params);
		return ($result && $adb->num_rows($result) > 0);
	}
	//crmv@154715e
	
	//crmv@171832
	function calculateWritableFields() {
		$this->writable_fields = Array();
		$presave_data = Array();
		$aftersave_data = $this->column_fields;
		$double_check = false;
		if (isset($this->editview_etag) && !empty($this->editview_presavedata) && !empty($this->column_fields_presave)){
			$presave_data = $this->editview_presavedata;
			if (!empty($this->editview_aftersavedata)){
				$aftersave_data = $this->editview_aftersavedata;
			}
			// this is to diff also fields between handlers, in case they changed
			$double_check = true; // crmv@180741 - no, keep it active!
			$presave_data2 = $this->column_fields_presave;
			$aftersave_data2 = $this->column_fields;
		}
		if (!empty($presave_data)){
			$this->enable_partial_write = true;
			//unset useless fields
			unset($aftersave_data['createdtime']);
			unset($aftersave_data['modifiedtime']);
			unset($aftersave_data['record_id']);
			unset($aftersave_data['record_module']);
			unset($presave_data['createdtime']);
			unset($presave_data['modifiedtime']);
			unset($presave_data['record_id']);
			unset($presave_data['record_module']);
			//shirnk arrays
			$presave_data = array_intersect_key($presave_data, $aftersave_data); // crmv@177395 - clean code
			//compare new column fields with presave one
			//crmv@178347
			//$fields_to_save = array_diff_assoc($presave_data, $aftersave_data);
			$fields_to_save = array_map('unserialize', array_diff_assoc(array_map('serialize', $presave_data), array_map('serialize', $aftersave_data)));
			//crmv@178347e
			foreach ($fields_to_save as $field_name=>$val){
				$this->writable_fields[$field_name] = $field_name;
			}
			if ($double_check){
				if (empty($presave_data2)){
					$this->enable_partial_write = false;
				}
				else{
					//unset useless fields
					unset($aftersave_data2['createdtime']);
					unset($aftersave_data2['modifiedtime']);
					unset($aftersave_data2['record_id']);
					unset($aftersave_data2['record_module']);
					unset($presave_data2['createdtime']);
					unset($presave_data2['modifiedtime']);
					unset($presave_data2['record_id']);
					unset($presave_data2['record_module']);
					//shirnk arrays
					$presave_data2 = array_intersect_key($presave_data2, $aftersave_data2); // crmv@177395 - clean code
					//compare new column fields with presave one
					$fields_to_save = array_map('unserialize', array_diff_assoc(array_map('serialize', $presave_data2), array_map('serialize', $aftersave_data2)));  // crmv@180741
					foreach ($fields_to_save as $field_name=>$val){
						$this->writable_fields[$field_name] = $field_name;
					}
				}
			}
		}
		unset($aftersave_data);
		unset($presave_data);
		$editview_tag = $this->editview_etag;
		unset($this->editview_etag);
		unset($this->editview_presavedata);
		unset($this->editview_aftersavedata);
		unset($this->column_fields_presave);
		return $editview_tag;
	}
	//crmv@171832e
	
	//crmv@185647
	function getEntityTableInfo($info) {
		static $cache = array();
		if (empty($cache[$this->modulename])) {
			require_once('include/utils/VTEProperties.php');
			$VP = VTEProperties::getInstance();
			$modules_without_crmentity = $VP->get('performance.modules_without_crmentity');
			if (in_array($this->modulename,$modules_without_crmentity)) {
				$cache[$this->modulename] = array('table'=>$this->table_name,'index'=>$this->table_index);
			} else {
				global $table_prefix;
				$cache[$this->modulename] = array('table'=>"{$table_prefix}_crmentity",'index'=>'crmid');
			}
		}
		return $cache[$this->modulename][$info];
	}
	//crmv@185647e
}

// enable the override of standard CRMEntity methods
if (file_exists('modules/SDK/src/VTEEntity2.php')) {
	require_once('modules/SDK/src/VTEEntity2.php');
}
// if not extended, create an empty class
if (!class_exists('CRMEntity')) {
	class CRMEntity extends CRMEntityBase {}
}
?>
