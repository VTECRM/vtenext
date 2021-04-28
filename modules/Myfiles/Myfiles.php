<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@95157 */

require_once('modules/Documents/storage/StorageBackendUtils.php');

class Myfiles extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	var $table_name;
	var $table_index= 'myfilesid';
	var $column_fields = Array();

	/** Indicator if this is a custom module or standard module */
	var $IsCustomModule = true;

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array();

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array();

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array();

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array ();
	var $list_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Myfiles No'=> 'myfiles_no',
		'Assigned To' => 'assigned_user_id'
	);

	// Make the field link to detail view from list view (Fieldname)
	var $list_link_field = 'myfiles_no';

	// For Popup listview and UI type support
	var $search_fields = Array();
	var $search_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Myfiles No'=> 'myfiles_no'
	);

	// For Popup window record selection
	var $popup_fields = Array('myfiles_no');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array();

	// For Alphabetical search
	var $def_basicsearch_col = 'myfiles_no';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'myfiles_no';

	// Required Information for enabling Import feature
	var $required_fields = Array('myfiles_no'=>1);

	var $default_order_by = 'myfiles_no';
	var $default_sort_order='ASC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('createdtime', 'modifiedtime', 'myfiles_no');
	//crmv@10759
	var $search_base_field = 'myfiles_no';
	//crmv@10759 e

	function __construct() {
		global $log, $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix.'_myfiles';
		$this->customFieldTable = Array($table_prefix.'_myfilescf', 'myfilesid');
		$this->entity_table = $table_prefix."_crmentity";
		$this->tab_name = Array($table_prefix.'_crmentity', $table_prefix.'_myfiles', $table_prefix.'_myfilescf');
		$this->tab_name_index = Array(
			$table_prefix.'_crmentity' => 'crmid',
			$table_prefix.'_myfiles'   => 'myfilesid',
			$table_prefix.'_myfilescf' => 'myfilesid'
		);
		$this->list_fields = Array(
			/* Format: Field Label => Array(tablename, columnname) */
			// tablename should not have prefix 'vte_'
			'Myfiles No'=> Array($table_prefix.'_myfiles', 'myfiles_no'),
			'Assigned To' => Array($table_prefix.'_crmentity','smownerid')
		);
		$this->search_fields = Array(
			/* Format: Field Label => Array(tablename, columnname) */
			// tablename should not have prefix 'vte_'
			'Myfiles No'=> Array($table_prefix.'_myfiles', 'myfiles_no')
		);
		$this->column_fields = getColumnFields(get_class()); //crmv@146187
		$this->db = PearDatabase::getInstance();
		$this->log = $log;
	}

	function save_module($module) {
		global $log,$upload_badext, $current_user;
		global $adb,$table_prefix;

		$SBU = StorageBackendUtils::getInstance();
		
		$filetype_fieldname = $this->getFileTypeFieldName();
		$filename_fieldname = $this->getFile_FieldName();
		$backend_fieldname = $this->getBackendFieldName();
		
		$filetype = $this->column_fields[$filetype_fieldname] ?: $SBU->defaultBackend;
		$filelocationtype = $filetype;
		
		$backend = null;
		
		$uploadMetadata = false;
		// check if internal or external
		if ($filetype == 'I' || $filetype == 'B'){
		
			if ($filetype == 'B') {
				$backend = $this->column_fields[$backend_fieldname];
			}
			
			// uploading a file
			if($_FILES[$filename_fieldname]['name'] != ''){
				$uploadMetadata = true;
				$errCode=$_FILES[$filename_fieldname]['error'];
				if($errCode == 0) {
					foreach($_FILES as $fileindex => $files) {
						if($files['name'] != '' && $files['size'] > 0){
							$filename = $_FILES[$filename_fieldname]['name'];
							$filename = from_html(preg_replace('/\s+/', '_', $filename));
							$filetype = $_FILES[$filename_fieldname]['type'];
							$filesize = $_FILES[$filename_fieldname]['size'];
							$binFile = sanitizeUploadFileName($filename, $upload_badext);
							$filename = ltrim(basename(" ".$binFile)); //allowed filename like UTF-8 characters
						}
					}
				}
				
			// not uploading, but in edit, don't change anything
			}elseif($this->mode == 'edit') {
				// do nothing
			
			// not uploading, create -> clear fields
			}elseif($this->column_fields[$filename_fieldname]) {
				$uploadMetadata = true;
				$filename = $this->column_fields[$filename_fieldname];
				$filesize = $this->column_fields['filesize'];
				$filetype = $this->column_fields['filetype'];
				$filedownloadcount = 0;
			}
			
		// external, clear fields
		} elseif($filetype == 'E' ){
			$uploadMetadata = true;
			$filename = $this->column_fields[$filename_fieldname];
			// If filename does not has the protocol prefix, default it to http://
			// Protocol prefix could be like (https://, smb://, file://, \\, smb:\\,...)
			if(!empty($filename) && !preg_match('/^\w{1,5}:\/\/|^\w{0,3}:?\\\\\\\\/', trim($filename), $match)) {
				$filename = "http://$filename";
			}
			$filetype = '';
			$filesize = 0;
			$filedownloadcount = null;
		}
		
		if ($uploadMetadata) {
			$query = "UPDATE ".$table_prefix."_myfiles SET filename = ? ,filesize = ?, filetype = ? , filelocationtype = ? , filedownloadcount = ? WHERE myfilesid = ?";
			$re=$adb->pquery($query,array($filename,intval($filesize),$filetype,$filelocationtype,$filedownloadcount,intval($this->id))); // crmv@85055
		}
		
		//Inserting into attachments table
		if($filelocationtype == 'I') {
			// deprecated code!
			$this->insertIntoAttachment($this->id,'Myfiles');
		} elseif($filelocationtype == 'B') {
			$opts = array();
			if ($backend == 'file') {
				$opts['storage_path'] = 'storage/home/'.$current_user->id.'/';
			}
			$SBU->uploadFile($backend, 'Myfiles', $this, $opts);
		}else{
			$query = "delete from ".$table_prefix."_seattachmentsrel where crmid = ?";
			$qparams = array($this->id);
			$adb->pquery($query, $qparams);
		}
		
	}
	
	// crmv@95157 - moved code to crmentity

	/**
	 * Return query to use based on given modulename, fieldname
	 * Useful to handle specific case handling for Popup
	 */
	function getQueryByModuleField($module, $fieldname, $srcrecord) {
		// $srcrecord could be empty
	}

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		global $adb,$table_prefix;
		if($event_type == 'module.postinstall') {
		
			$moduleInstance = Vtecrm_Module::getInstance($modulename);
			$adb->pquery("UPDATE {$table_prefix}_def_org_share SET editstatus = ? WHERE tabid = ?",array(2,$moduleInstance->id));
			
			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($modulename));

			// crmv@164120 - removed code
			
			SDK::setHomeGlobalIframe(2,'index.php?module=Myfiles&action=MyfilesAjax&file=HomeBlock','My files',true);
			SDK::setLanguageEntries('Home','Myfiles',Array(
				'it_it'=>'File personali',
				'en_us'=>'My files',
				'br_br'=>'Meus arquivos',
				'de_de'=>'Meine Dateien',
				'nl_nl'=>'Mijn bestanden',
			));
			$this->setModuleSeqNumber('configure', 'Myfiles', 'FILE-', 1);

		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($event_type == 'module.preuninstall') {
			SDK::unsetHomeGlobalIframe('My files');
			SDK::deleteLanguageEntry('Home',NULL,'Myfiles');			
			// TODO Handle actions when this module is about to be deleted.
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
	}

	/**
	 * Handle saving related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	/*
	function save_related_module($module, $crmid, $with_module, $with_crmid) {
		parent::save_related_module($module, $crmid, $with_module, $with_crmid);
		//...
	}
	*/

	/**
	 * Handle deleting related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function delete_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle getting related list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function get_related_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }

	/**
	 * Handle getting dependents list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function get_dependents_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }
	/**
	 * Function used to get the sort order for Myfiles listview
	 * @return String $sorder - sort order for a given folder.
	 */
	function getSortOrderForFolder($folderId) {
		if(isset($_REQUEST['sorder']) && $_REQUEST['folderid'] == $folderId) {
			$sorder = $this->db->sql_escape_string($_REQUEST['sorder']);
		} elseif(is_array(VteSession::get('MYFILES_FOLDER_SORT_ORDER')) &&
					!VteSession::isEmptyArray(array('MYFILES_FOLDER_SORT_ORDER', $folderId))) {
				$sorder = VteSession::getArray(array('MYFILES_FOLDER_SORT_ORDER', $folderId));
		} else {
			$sorder = $this->default_sort_order;
		}
		return $sorder;
	}

	/**
	 * Function used to get the order by value for Myfiles listview
	 * @return String order by column for a given folder.
	 */
	function getOrderByForFolder($folderId) {
		$use_default_order_by = '';
		if(PerformancePrefs::getBoolean('LISTVIEW_DEFAULT_SORTING', true)) {
			$use_default_order_by = $this->default_order_by;
		}
		if (isset($_REQUEST['order_by'])  && $_REQUEST['folderid'] == $folderId) {
			$order_by = $this->db->sql_escape_string($_REQUEST['order_by']);
		} elseif(is_array(VteSession::get('MYFILES_FOLDER_ORDER_BY')) &&
				!VteSession::isEmptyArray(array('MYFILES_FOLDER_ORDER_BY', $folderId))) {
			$order_by = VteSession::getArray(array('MYFILES_FOLDER_ORDER_BY', $folderId));
		} else {
			$order_by = ($use_default_order_by);
		}
		return $order_by;
	}
	//crmv@16312 end

	/** Function to export the Myfiles in CSV Format
	* @param reference variable - where condition is passed when the query is executed
	* Returns Export Myfiles Query.
	*/
	function create_export_query($where,$oCustomView,$viewId)	//crmv@31775
	{
		global $log,$current_user;
		global $table_prefix;
		$log->debug("Entering create_export_query(". $where.") method ...");

		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery("Myfiles", "detail_view");
		$fields_list = getFieldsListFromQuery($sql);

		// crmv@30967
		$query = "SELECT $fields_list, case when (".$table_prefix."_users.user_name is not null) then ".$table_prefix."_users.user_name else ".$table_prefix."_groups.groupname end as user_name" .
			" FROM ".$table_prefix."_myfiles
			inner join ".$table_prefix."_myfilescf
				on ".$table_prefix."_myfilescf.myfilesid = ".$table_prefix."_myfiles.myfilesid
			inner join ".$table_prefix."_crmentity
				on ".$table_prefix."_crmentity.crmid=".$table_prefix."_myfiles.myfilesid
			LEFT JOIN ".$table_prefix."_crmentityfolder on ".$table_prefix."_myfiles.folderid=".$table_prefix."_crmentityfolder.folderid
			LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_crmentity.smownerid=".$table_prefix."_users.id " .
			" LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_crmentity.smownerid=".$table_prefix."_groups.groupid ";
		// crmv@30967e

		//crmv@31775
		$reportFilter = $oCustomView->getReportFilter($viewId);
		if ($reportFilter) {
			$tableNameTmp = $oCustomView->getReportFilterTableName($reportFilter,$current_user->id);
			$query .= " INNER JOIN $tableNameTmp ON $tableNameTmp.id = {$table_prefix}_crmentity.crmid";
		}
		//crmv@31775e

		$query .= getNonAdminAccessControlQuery('Myfiles',$current_user);
		$where_auto=" ".$table_prefix."_crmentity.deleted=0";
		if($where != "")
			$query .= "  WHERE ($where) AND ".$where_auto;
		else
			$query .= "  WHERE ".$where_auto;
		$query = $this->listQueryNonAdminChange($query, 'Myfiles');
		$log->debug("Exiting create_export_query method ...");
		return $query;
	}


	/*
	 * Function to get the primary query part of a report
	 * @param - $module Primary module name
	 * returns the query string formed on fetching the related data for report for primary module
	 */
	function generateReportsQuery($module, $reportid = 0, $joinProducts = false, $joinUitype10 = true) { // crmv@146653
		global $table_prefix;
	 			$moduletable = $this->table_name;
	 			$moduleindex = $this->tab_name_index[$moduletable];
		// crmv@30967
 		$query = "from $moduletable
			inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=$moduletable.$moduleindex
			inner join ".$table_prefix."_crmentityfolder on ".$table_prefix."_crmentityfolder.folderid=$moduletable.folderid
			left join ".$table_prefix."_groups ".$table_prefix."_groups".$module." on ".$table_prefix."_groups".$module.".groupid = ".$table_prefix."_crmentity.smownerid
			left join ".$table_prefix."_users ".$table_prefix."_users".$module." on ".$table_prefix."_users".$module.".id = ".$table_prefix."_crmentity.smownerid
			left join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
			left join ".$table_prefix."_users on ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid";
		// crmv@30967e
		return $query;
	}

	/*
	 * Function to get the secondary query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	//crmv@38798
	function generateReportsSecQuery($module,$secmodule,$reporttype='',$useProductJoin=true,$joinUitype10=true){ // crmv@146653
		global $table_prefix;
		$query = $this->getRelationQuery($module,$secmodule,$table_prefix."_myfiles","myfilesid");
		// crmv@30967
		$query .=" left join ".$table_prefix."_crmentityfolder on ".$table_prefix."_crmentityfolder.folderid=".$table_prefix."_myfiles.folderid
		left join ".$table_prefix."_groups ".$table_prefix."_groupsMyfiles on ".$table_prefix."_groupsMyfiles.groupid = ".$table_prefix."_crmentityMyfiles.smownerid
		left join ".$table_prefix."_users ".$table_prefix."_usersMyfiles on ".$table_prefix."_usersMyfiles.id = ".$table_prefix."_crmentityMyfiles.smownerid";
		// crmv@30967e
		return $query;
	}
	//crmv@38798e

	/*
	 * Function to get the relation tables for related modules
	 * @param - $secmodule secondary module name
	 * returns the array with table names and fieldnames storing relations between module and this module
	 */
	function setRelationTables($secmodule){
		$rel_tables = array();
		return $rel_tables[$secmodule];
	}


// Function to get fieldname for uitype 27 assuming that documents have only one file type field

	function getFileTypeFieldName(){
		global $adb,$log;
		global $table_prefix;
		$query = 'SELECT fieldname from '.$table_prefix.'_field where tabid = ? and uitype = ?';
		$tabid = getTabid('Myfiles');
		$filetype_uitype = 27;
		$res = $adb->pquery($query,array($tabid,$filetype_uitype));
		$fieldname = null;
		if(isset($res)){
			$rowCount = $adb->num_rows($res);
			if($rowCount > 0){
				$fieldname = $adb->query_result($res,0,'fieldname');
			}
		}
		return $fieldname;

	}
	
	function getBackendFieldName(){
		global $adb, $table_prefix;
		$query = 'SELECT fieldname from '.$table_prefix.'_field where tabid = ? and uitype = ?';
		$tabid = getTabid('Myfiles');
		$res = $adb->pquery($query,array($tabid,212));
		$fieldname = null;
		if($res && $adb->num_rows($res) > 0) {
			$fieldname = $adb->query_result_no_html($res,0,'fieldname');
		}
		return $fieldname;
	}

//	Function to get fieldname for uitype 28 assuming that doc has only one file upload type

	function getFile_FieldName(){
		global $adb,$log;
		global $table_prefix;
		$query = 'SELECT fieldname from '.$table_prefix.'_field where tabid = ? and uitype = ?';
		$tabid = getTabid('Myfiles');
		$filename_uitype = 28;
		$res = $adb->pquery($query,array($tabid,$filename_uitype));
		$fieldname = null;
		if(isset($res)){
			$rowCount = $adb->num_rows($res);
			if($rowCount > 0){
				$fieldname = $adb->query_result($res,0,'fieldname');
			}
		}
		return $fieldname;
	}

	/**
	 * Check the existence of folder by folderid
	 */
	// crmv@30967
	function isFolderPresent($folderid) {
		$result = $this->getEntityFolder($folderid);

		if(!empty($result) && $result['folderid'] == $folderid) return true;
		return false;
	}

	// you can override this method!
	function getFolderList($foldername=null) {
		return $this->getEntityFoldersByName($foldername, 'Myfiles');
	}
	function getEntityFolder($folderid) {
		global $adb, $table_prefix;
	
		$res = $adb->pquery("select * from {$table_prefix}_crmentityfolder where folderid = ?", array($folderid));
	
		if ($res !== false) {
			return $adb->fetchByAssoc($res);
		} else {
			return false;
		}
	}
	
	function getEntityFoldersByName($foldername = null, $module = null) {
		global $adb, $table_prefix,$current_user;
	
		$params = array();
		$conds = array();
		$sql = "select * from {$table_prefix}_crmentityfolder";
	
		if (!is_null($foldername) || !is_null($module)) {
			$sql .= ' where ';
		}
		if (!is_null($foldername)) {
			if (is_numeric($foldername)){
				$conds[] = ' folderid = ? ';
			}
			else{
				$conds[] = ' foldername = ? ';
			}
			$params[] = $foldername;
		}
	
		if (!is_null($module)) {
			$conds[] = ' tabid = ? ';
			$params[] = getTabId($module);
		}
		if (isset($current_user)) {
			$conds[] = ' createdby = ? ';
			$params[] = $current_user->id;
		}
	
		$sql .= implode(' and ', $conds);
		$sql .= ' order by foldername';
	
		$res = $adb->pquery($sql, $params);

		if ($res !== false) {
			$ret = array();
			while ($row = $adb->fetchByAssoc($res)) $ret[] = $row;
			return $ret;
		} else {
			return false;
		}
	}
	// return only the count
	function getFolderContent($folderid) {
		global $adb, $table_prefix, $current_user, $app_strings, $mod_strings;

		$folderinfo = $this->getEntityFolder($folderid);

		$queryGenerator = QueryGenerator::getInstance('Myfiles', $current_user);
		$queryGenerator->initForDefaultCustomView();
		$list_query = $queryGenerator->getQuery();
		// only in selected folder
		$list_query .= " AND {$this->table_name}.folderid = '$folderid'";
		// order by most recent first
		$list_query .= " ORDER BY {$table_prefix}_crmentity.modifiedtime DESC";

		$count = 0;
		$res = $adb->query(replaceSelectQuery($list_query,'count(*) as cnt'));
		if ($res) $count = $adb->query_result($res,0,'cnt');

		$smarty = new VteSmarty();
		$smarty->assign('FOLDERINFO', $folderinfo);
		$smarty->assign('APP', $app_strings);
		$smarty->assign('MOD', $mod_strings);
		$smarty->assign('TOTALCOUNT', $count);
		/*
		// retrieve the first documents as a preview
		$html = '';
		$res = $adb->limitQuery($list_query, 0, 5); // crmv@30976
		if ($res) {
			$arr = array();
			while ($row = $adb->fetchByAssoc($res)) {
				$arr[] = $row;
			}
			$smarty->assign('FOLDERDATA', $arr);
		}
		$html = $smarty->fetch('modules/Myfiles/FolderTooltip.tpl');
		*/
		return array('count'=>$count, 'html'=>$html);
	}
	function getFolderCount($folderid) {
		global $adb, $table_prefix, $current_user, $app_strings, $mod_strings;
		$queryGenerator = QueryGenerator::getInstance('Myfiles', $current_user);
		$queryGenerator->initForDefaultCustomView();
		$list_query = $queryGenerator->getQuery();
		// only in selected folder
		$list_query .= " AND {$this->table_name}.folderid = '$folderid'";
		$count = 0;
		$res = $adb->query(replaceSelectQuery($list_query,'count(*) as cnt'));
		if ($res) $count = $adb->query_result($res,0,'cnt');
		return $count;
	}

	function getMiniDetailView($myfilesid){
		global $adb,$table_prefix;
		$sql = "select ";
	}
	
	// return only the count
	function getFolderFullContent($folderid) {
		global $adb, $table_prefix, $current_user, $app_strings, $mod_strings,$theme,$listview_max_textlength;

        $folderid = intval($folderid);//crmv@208173


		$folderinfo = $this->getEntityFolder($folderid);

		$queryGenerator = QueryGenerator::getInstance('Myfiles', $current_user);
		$queryGenerator->initForDefaultCustomView();
		$list_query = $queryGenerator->getQuery();
		// only in selected folder
		$list_query .= " AND {$this->table_name}.folderid = '$folderid'";
		// order by most recent first
		$list_query .= " ORDER BY {$table_prefix}_crmentity.modifiedtime DESC";

		$count = 0;
		$tablename = $table_prefix.'_seattachmentsrel';
		$tabindex = 'crmid';
		$join_check = " left join {$tablename} on {$tablename}.{$tabindex} = {$table_prefix}_crmentity.crmid ";
		$list_query = preg_replace('/\swhere\s/i', " $join_check where ", $list_query, 1);
		$tablename = $table_prefix.'_attachments';
		$tabindex = 'attachmentsid';
		$join_check = " left join {$tablename} on {$tablename}.{$tabindex} = {$table_prefix}_seattachmentsrel.attachmentsid ";
		$list_query = preg_replace('/\swhere\s/i', " $join_check where ", $list_query, 1);
		$join_check = "select {$table_prefix}_attachments.attachmentsid,";
		$list_query = preg_replace("/[\n\r\t]+/"," ",$list_query);
		
		$fields_select = Array(
			"{$table_prefix}_myfiles.myfilesid",
			"{$table_prefix}_myfiles.title",
			"{$table_prefix}_attachments.attachmentsid",
			"{$table_prefix}_myfiles.filename",
			"{$table_prefix}_myfiles.filetype",
			"{$table_prefix}_myfiles.filesize",
			"{$table_prefix}_crmentity.createdtime",
			"{$table_prefix}_myfiles.title",
			"{$table_prefix}_crmentity.modifiedtime",
		);
		
		$list_query = replaceSelectQuery($list_query,implode(",",$fields_select));
		$res = $adb->query(replaceSelectQuery($list_query,'count(*) as cnt'));
		if ($res) $count = $adb->query_result($res,0,'cnt');

		$smarty = new VteSmarty();
		$smarty->assign('FOLDERINFO', $folderinfo);
		$smarty->assign('APP', $app_strings);
		$smarty->assign('MOD', $mod_strings);
		$smarty->assign('TOTALCOUNT', $count);
		// retrieve the first documents as a preview
		$html = '';
		$res = $adb->query($list_query);
		if ($res) {
			$listview_max_textlength_backup = $listview_max_textlength;
			$listview_max_textlength = 20;
			$arr = array();
			while ($row = $adb->fetchByAssoc($res)) {
				$smarty->assign('FILEDATA', $row);
				$row['link'] = "<a href='index.php?module=uploads&action=downloadfile&entityid={$row['myfilesid']}&fileid={$row['attachmentsid']}' title='".getTranslatedString("LBL_DOWNLOAD_FILE",'Documents')."' onclick='javascript:dldCntIncrease({$row['attachmentsid']},\"Myfiles\");' title='".$row['filename']."'>".textlength_check($row['filename'])."</a>";
				$row['fastmenu'] = Array(
					'delete'=>'<a class="delete_link" fileid="'.$row['myfilesid'].'" title="'.getTranslatedString('LBL_DELETE').'"><i class="vteicon valign-bottom">delete</i></a>',
					'download'=>'<a href="index.php?module=uploads&action=downloadfile&entityid='.$row['myfilesid'].'&fileid='.$row['attachmentsid'].'" title="'.getTranslatedString("LBL_DOWNLOAD_FILE",'Documents').'" onclick="javascript:dldCntIncrease('.$row['attachmentsid'].',\'Myfiles\');"><i class="vteicon valign-bottom">file_download</i></a>',
					'view'=>'<a class="show_link" folderid="'.$folderid.'" fileid="'.$row['myfilesid'].'" file_name="'.$row['filename'].'" title="'.getTranslatedString('LBL_SHOW_DETAILS','Myfiles').'"><i class="vteicon valign-bottom">remove_red_eye</i></a>',
					'convert'=>'<a class="convert_link" folderid="'.$folderid.'" fileid="'.$row['myfilesid'].'" file_name="'.$row['filename'].'" file_title="'.$row['title'].'" title="'.getTranslatedString('LBL_CONVERT','Myfiles').'"><i class="vteicon valign-bottom">open_in_new</i></a>',
				);
				$row['detailview'] = $smarty->fetch('modules/Myfiles/FileDetails.tpl');
				$row['id']=$row['myfilesid'];
				$arr[] = $row;
			}
			$listview_max_textlength = $listview_max_textlength_backup;
		}
		return array('count'=>$count, 'files'=>$arr);
	}	

	function getQueryExtraWhere() {
		global $table_prefix,$current_user;
		$fldid = intval($_REQUEST['folderid']);
		$filter = '';
		if ($fldid > 0) {
			$filter = " and {$this->table_name}.folderid = '$fldid'";
		}
		$filter .= " and {$this->entity_table}.smownerid = $current_user->id";
		return $filter;	
	}
	// crmv@30967e

	/**
	 * Customizing the restore procedure.
	 */
	function restore($modulename, $id) {
		parent::restore($modulename, $id);

		global $adb;
		global $table_prefix;
		$fresult = $adb->pquery("SELECT folderid FROM ".$table_prefix."_myfiles WHERE myfilesid = ?", array($id));
		if(!empty($fresult) && $adb->num_rows($fresult)) {
			$folderid = $adb->query_result($fresult, 0, 'folderid');
			if(!$this->isFolderPresent($folderid)) {
				// Re-link to default folder
				$adb->pquery("UPDATE ".$table_prefix."_myfiles set folderid = 1 WHERE myfilesid = ?", array($id));
			}
		}
	}
	
}