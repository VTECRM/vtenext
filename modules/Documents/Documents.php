<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@95157 */
 
require('config.php');
require_once('include/logging.php');
require_once('include/database/PearDatabase.php');
require_once('data/CRMEntity.php');

require_once('modules/Documents/storage/StorageBackendUtils.php');

// Note is used to store customer information.
class Documents extends CRMEntity {

	var $log;
	var $db;
	var $table_name;
	var $table_index= 'notesid';
	var $default_note_name_dom = array();

	var $tab_name = Array();
	var $tab_name_index = Array();

	var $column_fields = Array();

    var $sortby_fields = Array('title','modifiedtime','filename','createdtime','lastname','filedownloadcount','smownerid');

	// This is used to retrieve related vte_fields from form posts.
	var $additional_column_fields = Array('', '', '', '');

	// This is the list of vte_fields that are in the lists.
	var $list_fields = Array(
		'Title'=>Array('notes'=>'title'),
		'File Name'=>Array('notes'=>'filename'),
		'Assigned To' => Array('crmentity'=>'smownerid'),
		'Folder Name' => Array('crmentityfolder'=>'foldername'),	//crmv@30967
	);
	var $list_fields_name = Array(
		'Title'=>'notes_title',
		'File Name'=>'filename',
		'Assigned To'=>'assigned_user_id',
		'Folder Name' => 'folderid'
	);
	var $search_fields = Array(
		'Title' => Array('notes'=>'title'),							//crmv@31979
		'File Name' => Array('notes'=>'filename'),
		'Assigned To' => Array('crmentity'=>'smownerid'),
		'Folder Name' => Array('crmentityfolder'=>'foldername'),	//crmv@30967
	);
	var $search_fields_name = Array(
		'Title' => 'notes_title',
		'File Name' => 'filename',
		'Assigned To' => 'assigned_user_id',
		'Folder Name' => 'folderid'
	);
	var $list_link_field= 'notes_title';
	var $old_filename = '';
	var $customFieldTable = Array(); //crmv@13997

	var $mandatory_fields = Array('notes_title','createdtime' ,'modifiedtime','filename','filesize','filetype','filedownloadcount','assigned_user_id');

	// crmv@43147
	var $sharedFields = array('notes_title', 'notecontent', 'filename');
	// crmv@43147e

	//Added these variables which are used as default order by and sortorder in ListView
	var $default_order_by = 'title';
	var $default_sort_order = 'ASC';
	
	//crmv@62414
	var $view_image_supported_extensions = array('png','bmp','gif','jpeg','jpg','tiff','tif'); // crmv@198701
	var $viewerJS_supported_extensions = array('pdf','odt','ods','ots','ott','otp');
	var $action_view_JSfunction_array = array(
		'eml'=>'ViewEML',
		'pdf'=>'ViewDocument',
		'odt'=>'ViewDocument',
		'ods'=>'ViewDocument',
		'ots'=>'ViewDocument',
		'ott'=>'ViewDocument',
		'otp'=>'ViewDocument',
		'png'=>'ViewImage',
		'bmp'=>'ViewImage',
		'gif'=>'ViewImage',
		'jpeg'=>'ViewImage',
		'jpg'=>'ViewImage',
		// crmv@198701
		'tiff'=>'ViewImage',
		'tif'=>'ViewImage',
		// crmv@198701e
	);
	//crmv@62414e
	
	/**
	 * Set this variable to a path before saving, to have the document uploaded somewhere else
	 * (Only when the backend is 'file')
	 */
	public $alternativeFileStorage = null;
	
	function __construct() {
		global $table_prefix;

		$this->log = LoggerManager::getLogger('notes');
		$this->log->debug("Entering Documents() method ...");

		// crmv@37004
		parent::__construct();
		$this->relation_table = $table_prefix.'_senotesrel';
		$this->relation_table_id = 'notesid';
		$this->relation_table_otherid = 'crmid';
		$this->relation_table_module = '';
		$this->relation_table_othermodule = 'relmodule'; //crmv@38798
		// crmv@37004e

		$this->table_name = $table_prefix."_notes";
		$this->default_note_name_dom = array('Meeting vte_notes', 'Reminder');
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_notes',$table_prefix.'_notescf');
		$this->tab_name_index = Array($table_prefix.'_crmentity'=>'crmid',$table_prefix.'_notes'=>'notesid',$table_prefix.'_notescf'=>'notesid',$table_prefix.'_senotesrel'=>'notesid');
		$this->customFieldTable = Array($table_prefix.'_notescf', 'notesid'); //crmv@13997

		$this->db = PearDatabase::getInstance();
		$this->column_fields = getColumnFields('Documents');
		$this->log->debug("Exiting Documents method ...");
	}

	function save_module($module) {
		global $log,$adb,$table_prefix, $upload_badext;

		if(isset($this->parentid) && $this->parentid != '') {
			$relid =  $this->parentid;
		}
		
		//inserting into vte_senotesrel
		if(isset($relid) && $relid != '') {
			$this->insertintonotesrel($relid,$this->id);
		}
		
		$SBU = StorageBackendUtils::getInstance();
		
		$filetype_fieldname = $this->getFileTypeFieldName();
		$filename_fieldname = $this->getFile_FieldName();
		$backend_fieldname = $this->getBackendFieldName();
		
		$filetype = $this->column_fields[$filetype_fieldname];
		$filelocationtype = $filetype;
		
		$backend = null;
		
		$uploadFile = true; // crmv@123481
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
				$uploadFile = false; // crmv@123481
			
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
			$query = "UPDATE ".$table_prefix."_notes SET filename = ? ,filesize = ?, filetype = ? , filelocationtype = ? , filedownloadcount = ? WHERE notesid = ?";
			$re=$adb->pquery($query,array($filename,intval($filesize),$filetype,$filelocationtype,$filedownloadcount,intval($this->id))); // crmv@85055
		}
		
		// crmv@123481
		//Inserting into attachments table
		if ($uploadFile) {
			if($filelocationtype == 'I') {
				// deprecated code!
				$this->insertIntoAttachment($this->id,'Documents');
			} elseif($filelocationtype == 'B') {
				$opts = array();
				if ($backend == 'file' && $this->alternativeFileStorage) {
					$opts['storage_path'] = $this->alternativeFileStorage;
				}
				$SBU->uploadFile($backend, 'Documents', $this, $opts);
			}else{
				$query = "delete from ".$table_prefix."_seattachmentsrel where crmid = ?";
				$qparams = array($this->id);
				$adb->pquery($query, $qparams);
			}
		}
		// crmv@123481e
	}
	
	function uploadRevision($record, $userEmail) {
		global $adb, $table_prefix, $current_user, $upload_badext;
		
		$errCode = $_FILES[$filename_fieldname]['error'];
		if ($errCode != 0) return false;
		
		if ($this->id != $record) {
			$this->retrieve_entity_info($record, 'Documents');
			$this->id = $record;
		}
		
		$SBU = StorageBackendUtils::getInstance();
		$FS = FileStorage::getInstance();
		
		$info = $FS->getFileInfoByCrmid($record);
		
		$backend = $info['backend_name'] ?: $SBU->defaultBackend;
		$filename_fieldname = $this->getFile_FieldName();
		
		foreach($_FILES as $fileindex => $files) {
			if ($files['name'] != '' && $files['size'] > 0) {
				$filename = $_FILES[$filename_fieldname]['name'];
				$filename = from_html(preg_replace('/\s+/', '_', $filename));
				$filetype = $_FILES[$filename_fieldname]['type'];
				$filesize = $_FILES[$filename_fieldname]['size'];
				$binFile = sanitizeUploadFileName($filename, $upload_badext);
				$filename = ltrim(basename(" ".$binFile)); //allowed filename like UTF-8 characters
			}
		}
		
		$now = date('Y-m-d H:i:s');
		$filelocationtype = 'B';

		// upload metadata on the document record
		$query = "UPDATE {$table_prefix}_notes SET filename = ?, filesize = ?, filetype = ? , filelocationtype = ? , filedownloadcount = ?, backend_name = ? WHERE notesid = ?";
		$adb->pquery($query,array($filename,$filesize,$filetype,$filelocationtype,0,$backend,$record));
		
		//crmv@53745
		$query = "UPDATE {$table_prefix}_crmentity SET modifiedtime = ? WHERE crmid = ?";
		$adb->pquery($query,array($now,$record));
		//crmv@53745e
		
		//Inserting into attachments table
		$r = $SBU->uploadRevision($backend, 'Documents', $this, $userEmail);
		
		if (!$r) return false;
		
		//crmv@63483 - save the notification
		$focus = ModNotifications::getInstance(); // crmv@164122
		$users = $focus->getFollowingUsers($record);
		$owner = getSingleFieldValue("{$table_prefix}_crmentity", 'smownerid', 'crmid', $record);
		$interested_users = $focus->getInterestedToModuleUsers('edit','Documents');
		if (in_array($owner,$interested_users)) $users[] = $owner;
		if (!empty($users)) {
			$already_notified_users = array();
			foreach($users as $user) {
				if (in_array($user,$already_notified_users) && $user != $current_user->id) {
					continue;
				}
				$notified_users = $focus->saveFastNotification(
					array(
						'assigned_user_id' => $user,
						'related_to' => $record,
						'mod_not_type' => 'Revisioned document',
						'createdtime' => $now,
						'modifiedtime' => $now,
					)
				);
				if(!empty($notified_users)) {
					foreach($notified_users as $notified_user) {
						$already_notified_users[] = $notified_user;
					}
				}
			}
		}
		//crmv@63483e
		
		return true;
	}


	//crmv@16312

	/**
	 * Function used to get the sort order for Documents listview
	 * @return String $sorder - sort order for a given folder.
	 */
	function getSortOrderForFolder($folderId) {
		if(isset($_REQUEST['sorder']) && $_REQUEST['folderid'] == $folderId) {
			$sorder = $this->db->sql_escape_string($_REQUEST['sorder']);
		} elseif(is_array(VteSession::get('NOTES_FOLDER_SORT_ORDER')) &&
					!VteSession::isEmptyArray(array('NOTES_FOLDER_SORT_ORDER', $folderId))) {
				$sorder = VteSession::getArray(array('NOTES_FOLDER_SORT_ORDER', $folderId));
		} else {
			$sorder = $this->default_sort_order;
		}
		return $sorder;
	}

	/**
	 * Function used to get the order by value for Documents listview
	 * @return String order by column for a given folder.
	 */
	function getOrderByForFolder($folderId) {
		$use_default_order_by = '';
		if(PerformancePrefs::getBoolean('LISTVIEW_DEFAULT_SORTING', true)) {
			$use_default_order_by = $this->default_order_by;
		}
		if (isset($_REQUEST['order_by'])  && $_REQUEST['folderid'] == $folderId) {
			$order_by = $this->db->sql_escape_string($_REQUEST['order_by']);
		} elseif(is_array(VteSession::get('NOTES_FOLDER_ORDER_BY')) &&
				!VteSession::isEmptyArray(array('NOTES_FOLDER_ORDER_BY', $folderId))) {
			$order_by = VteSession::getArray(array('NOTES_FOLDER_ORDER_BY', $folderId));
		} else {
			$order_by = ($use_default_order_by);
		}
		return $order_by;
	}
	//crmv@16312 end

	/** Function to export the notes in CSV Format
	* @param reference variable - where condition is passed when the query is executed
	* Returns Export Documents Query.
	*/
	function create_export_query($where,$oCustomView,$viewId)	//crmv@31775
	{
		global $log,$current_user;
		global $table_prefix;
		$log->debug("Entering create_export_query(". $where.") method ...");

		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery("Documents", "detail_view");
		$fields_list = getFieldsListFromQuery($sql);

		// crmv@30967
		$query = "SELECT $fields_list, case when (".$table_prefix."_users.user_name is not null) then ".$table_prefix."_users.user_name else ".$table_prefix."_groups.groupname end as user_name" .
			" FROM ".$table_prefix."_notes
			inner join ".$table_prefix."_notescf
				on ".$table_prefix."_notescf.notesid = ".$table_prefix."_notes.notesid
			inner join ".$table_prefix."_crmentity
				on ".$table_prefix."_crmentity.crmid=".$table_prefix."_notes.notesid
			LEFT JOIN ".$table_prefix."_crmentityfolder on ".$table_prefix."_notes.folderid=".$table_prefix."_crmentityfolder.folderid
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

		$query .= getNonAdminAccessControlQuery('Documents',$current_user);
		$where_auto=" ".$table_prefix."_crmentity.deleted=0";
		if($where != "")
			$query .= "  WHERE ($where) AND ".$where_auto;
		else
			$query .= "  WHERE ".$where_auto;
		$query = $this->listQueryNonAdminChange($query, 'Documents');
		$log->debug("Exiting create_export_query method ...");
		return $query;
	}

	// crmv@38798
	function insertintonotesrel($relid,$id)	{
		global $adb, $table_prefix;
		$dbQuery = "insert into {$table_prefix}_senotesrel (crmid, notesid, relmodule) values (?, ?, ?)";
		$relmod = getSalesEntityType($relid);
		if ($relmod) $dbresult = $adb->pquery($dbQuery,array($relid,$id, $relmod));
	}
	// crmv@38798e

	/*function save_related_module($module, $crmid, $with_module, $with_crmid){
		global $log;
		$log->debug("indocument".$module.$crmid.$with_module.$with_crmid);
		if(isset($this->parentid) && $this->parentid != '')
			$relid =  $this->parentid;
		//inserting into vte_senotesrel
		if(isset($relid) && $relid != '')
		{
			$this->insertintonotesrel($relid,$this->id);
		}
	}*/


	/*
	 * Function to get the primary query part of a report
	 * @param - $module Primary module name
	 * returns the query string formed on fetching the related data for report for primary module
	 */
	function generateReportsQuery($module, $reportid = 0, $joinProducts = false, $joinUitype10 = true){ // crmv@146653
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
		$query = $this->getRelationQuery($module,$secmodule,$table_prefix."_notes","notesid");
		// crmv@30967
		$query .=" left join ".$table_prefix."_crmentityfolder on ".$table_prefix."_crmentityfolder.folderid=".$table_prefix."_notes.folderid
		left join ".$table_prefix."_groups ".$table_prefix."_groupsDocuments on ".$table_prefix."_groupsDocuments.groupid = ".$table_prefix."_crmentityDocuments.smownerid
		left join ".$table_prefix."_users ".$table_prefix."_usersDocuments on ".$table_prefix."_usersDocuments.id = ".$table_prefix."_crmentityDocuments.smownerid";
		// crmv@30967e
		return $query;
	}
	//crmv@38798e

	// crmv@104568
	function relatedlist_preview_link($module, $entity_id, $current_module, $header, $relation_id) {
		return "window.open('index.php?module=$module&action=DetailView&record=$entity_id', '_blank')";
	}
	// crmv@104568e

	/*
	 * Function to get the relation tables for related modules
	 * @param - $secmodule secondary module name
	 * returns the array with table names and fieldnames storing relations between module and this module
	 */
	function setRelationTables($secmodule){
		$rel_tables = array();
		return $rel_tables[$secmodule];
	}

	// Function to unlink all the dependent entities of the given Entity by Id
	function unlinkDependencies($module, $id) {
		global $table_prefix, $log;
		
		// crmv@126877 - restored
		//Backup Documents Related Records
		$se_q = "SELECT crmid FROM {$table_prefix}_senotesrel WHERE notesid = ?";
		$se_res = $this->db->pquery($se_q, array($id));
		if ($this->db->num_rows($se_res) > 0) {
			for($k=0;$k < $this->db->num_rows($se_res);$k++)
			{
				$se_id = $this->db->query_result($se_res,$k,"crmid");
				$params = array($id, RB_RECORD_DELETED, $table_prefix.'_senotesrel', 'notesid', 'crmid', $se_id);
				$this->db->pquery("INSERT INTO {$table_prefix}_relatedlists_rb VALUES (?,?,?,?,?,?)", $params);
			}
		}
		$sql = "DELETE FROM {$table_prefix}_senotesrel WHERE notesid = ?";
		$this->db->pquery($sql, array($id));
		// crmv@126877e

		parent::unlinkDependencies($module, $id);
	}

	// Function to unlink an entity with given Id from another entity
	function unlinkRelationship($id, $return_module, $return_id) {
		global $log;
		global $table_prefix;
		if(empty($return_module) || empty($return_id)) return;

		$sql = 'DELETE FROM '.$table_prefix.'_senotesrel WHERE notesid = ? AND crmid = ?';
		$this->db->pquery($sql, array($id, $return_id));

		$sql = 'DELETE FROM '.$table_prefix.'_crmentityrel WHERE (crmid=? AND relmodule=? AND relcrmid=?) OR (relcrmid=? AND module=? AND crmid=?)';
		$params = array($id, $return_module, $return_id, $id, $return_module, $return_id);
		$this->db->pquery($sql, $params);

		$this->db->pquery("UPDATE {$table_prefix}_crmentity SET modifiedtime = ? WHERE crmid IN (?,?)", array($this->db->formatDate(date('Y-m-d H:i:s'), true), $id, $return_id)); // crmv@49398 crmv@69690
	}


// Function to get fieldname for uitype 27 assuming that documents have only one file type field

	function getFileTypeFieldName(){
		global $adb, $table_prefix;
		$query = 'SELECT fieldname from '.$table_prefix.'_field where tabid = ? and uitype = ?';
		$tabid = getTabid('Documents');
		$res = $adb->pquery($query,array($tabid,27));
		$fieldname = null;
		if($res && $adb->num_rows($res) > 0) {
			$fieldname = $adb->query_result_no_html($res,0,'fieldname');
		}
		return $fieldname;

	}
	
	function getBackendFieldName(){
		global $adb, $table_prefix;
		$query = 'SELECT fieldname from '.$table_prefix.'_field where tabid = ? and uitype = ?';
		$tabid = getTabid('Documents');
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
		$tabid = getTabid('Documents');
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
		$result = getEntityFolder($folderid);

		if(!empty($result) && $result['folderid'] == $folderid) return true;
		return false;
	}

	/*
	// you can override this method!
	function getFolderList() {
		return getEntityFoldersByName(null, 'Documents');
	}
	*/

	// return only the count
	function getFolderContent($folderid) {
		global $adb, $table_prefix, $current_user, $app_strings, $mod_strings;

		$folderinfo = getEntityFolder($folderid);

		$queryGenerator = QueryGenerator::getInstance('Documents', $current_user);
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
		$html = $smarty->fetch('modules/Documents/FolderTooltip.tpl');

		return array('count'=>$count, 'html'=>$html);
	}

	// crmv@98500
	function getQueryExtraWhere() {
		global $table_prefix, $currentModule;
		$fldid = intval($_REQUEST['folderid']);

		if ($fldid > 0 && $currentModule == $this->modulename) {
			return " and {$this->table_name}.folderid = '$fldid'";
		} else {
			return '';
		}
	}
	// crmv@30967e crmv@98500e

	/**
	 * Customizing the restore procedure.
	 */
	function restore($modulename, $id) {
		parent::restore($modulename, $id);

		global $adb;
		global $table_prefix;
		$fresult = $adb->pquery("SELECT folderid FROM ".$table_prefix."_notes WHERE notesid = ?", array($id));
		if(!empty($fresult) && $adb->num_rows($fresult)) {
			$folderid = $adb->query_result($fresult, 0, 'folderid');
			if(!$this->isFolderPresent($folderid)) {
				// Re-link to default folder
				$adb->pquery("UPDATE ".$table_prefix."_notes set folderid = 1 WHERE notesid = ?", array($id));
			}
		}
	}
	
	//crmv@115268
	// $file has the same structure of $_FILES for a single file : array(name,type,tmp_name,error,size)
	function createDocumentFromArrayFile($file, $folderid=1, $parentid='', $userid='') {
		global $current_user;
		if (empty($userid)) $userid = $current_user->id;
		if (empty($folderid)) $folderid = 1; //crmv@166972
		
		$document = CRMEntity::getInstance('Documents');
		$document->column_fields['notes_title']      = $file['name'];
		$document->column_fields['filestatus']    	 = 1;
		$document->column_fields['filelocationtype'] = 'I';
		$document->column_fields['folderid']         = $folderid;
		$document->column_fields['assigned_user_id'] = $userid;
		if (!empty($parentid))$document->parentid = $parentid;
		if (method_exists($document,'autoSetBUMC')) $document->autoSetBUMC('Documents',$userid);	//crmv@93302
		
		$fieldname = $document->getFile_FieldName();
		$_FILES = array($fieldname => $file);
		
		$document->save('Documents');
		
		return $document->id;
	}
	// $file fullpath
	function createDocumentFromPathFile($file, $folderid=1, $parentid='', $userid='') {
		global $current_user;
		if (empty($userid)) $userid = $current_user->id;
		if (empty($folderid)) $folderid = 1; //crmv@166972
		$filename = basename($file);
		
		$SBU = StorageBackendUtils::getInstance();
		
		$document = CRMEntity::getInstance('Documents');
		$document->column_fields['notes_title']      = $filename;
		$document->column_fields['filename']         = $filename;
		$document->column_fields['filestatus']       = 1;
		$document->column_fields['filelocationtype'] = 'B';
		$document->column_fields['backend_name'] = $SBU->defaultBackend;
		$document->column_fields['folderid']         = $folderid;
		$document->column_fields['assigned_user_id'] = $userid;
		$document->column_fields['filesize'] = 0;
		if (!empty($parentid)) $document->parentid = $parentid;
		if (method_exists($document,'autoSetBUMC')) $document->autoSetBUMC('Documents',$userid);	//crmv@93302
		
		$fieldname = $document->getFile_FieldName();
		
		// populate a fake files request
		require_once('modules/Settings/MailScanner/core/MailAttachmentMIME.php');
		$_FILES = array();
		$_FILES[$fieldname] = array(
			'name' => $filename,
			'size' => filesize($file),
			'type' => MailAttachmentMIME::detect($file),
			'tmp_name' => $file,
		);
		$_POST['copy_not_move'] = true;
		
		// save the record
		$document->save('Documents');
		
		return $document->id;
	}
	//crmv@115268e
}