<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/*
 * Funzioni di utilità per Touch
 */

// check if these are really needed
require_once('modules/CustomView/CustomView.php');
require_once('modules/SDK/src/Favorites/Utils.php');
require_once('modules/Popup/Popup.php');

// stuff for the wsRequest Function
require_once("include/HTTP_Session/Session.php");
require_once 'include/Webservices/Utils.php';
require_once("include/Webservices/State.php");
require_once("include/Webservices/OperationManager.php");
require_once("include/Webservices/SessionManager.php");


class TouchUtils extends SDKExtendableUniqueClass {

	public $entityNameCacheSize = 500;	// caches max 500 names

	protected $cachedModuleFields = array();
	protected $cachedInstances = array();
	protected $cachedEntityColumns = array();
	protected $cachedEntityNames = array();
	protected $cachedUserNames = array();
	protected $cachedLinksModules = array();
	protected $cachedPdfAvail = array();
	protected $cachedPdfDetails = array();
	protected $cachedFieldTypebyUIType = array(); // crmv@100158

	// crmv@54449
	public $related_blockids = array(
		'dynablocks' => 100000, // crmv@100158
		'products' => 1000000,
		'ticketcomments' => 1500000,
		'comments' => 2000000,
		'notes' => 2300000,
		'pdfmaker' => 2500000,
		'invitees' => 2700000,
		'related' => 3000000,
		'related_events' => 3500000,
	);
	// crmv@54449e
	
	public function isProductsRelated($relationId) {
		return ($relationId >= $this->related_blockids['products'] && $relationId < $this->related_blockids['products']+100000);
	}
	
	public function isInviteesRelated($relationId) {
		return ($relationId >= $this->related_blockids['invitees'] && $relationId < $this->related_blockids['invitees']+100000);
	}
	
	public function isNotesRelated($relationId) {
		return ($relationId >= $this->related_blockids['notes'] && $relationId < $this->related_blockids['notes']+100000);
	}
	
	public function clearWSCache($wsname) {
		global $touchInst;
		
		$class = $touchInst->getWSClassInstance($wsname, $touchInst->version);
		if ($class && method_exists($class, 'clearCache')) {
			$class->clearCache();
		}
	}

	public function wsRequest($userid, $operation, $operationInput, $format='json') {
		global $default_language, $adb;
		
		require_once "include/language/$default_language.lang.php";

		$operation = strtolower($operation);
		$sessionId = vtws_getParameter($_REQUEST,"sessionName");

		$sessionManager = new SessionManager();
		$operationManager = new OperationManager($adb,$operation,$format,$sessionManager);

		try {
			if ($userid) {
				$seed_user = new Users();
				$wsuser = $seed_user->retrieveCurrentUserInfoFromFile($userid);
				$wsuser->id = $userid;
			} else {
				$wsuser = null;
			}
			$includes = $operationManager->getOperationIncludes();
			foreach($includes as $ind=>$path){
				require_once($path);
			}
			$rawOutput = $operationManager->runOperation($operationInput,$wsuser);
			return array('success'=>true,'result'=>$rawOutput);

		} catch (Exception $e) {
			return array('success'=>false,'error'=>$e);
		}
	}

	public function updateTimestamp($module, $crmid) {
		global $adb, $table_prefix;

		$date_var = date('Y-m-d H:i:s'); //crmv@69690
		
		// crmv@174249
		if ($module == 'Messages' || $module == 'ModNotifications' || $module == 'ChangeLog') {
			$focus = $this->getModuleInstance($module);
			$query = "UPDATE {$focus->table_name} SET modifiedtime = ? WHERE {$focus->table_index} = ?";
		} else {
			$query = "UPDATE {$table_prefix}_crmentity SET modifiedtime = ? WHERE crmid = ?";
		}
		$params = array($adb->formatDate($date_var, true), $crmid);
		
		$adb->pquery($query, $params);
		// crmv@174249e
	}

	// cerca in un array usando una funzione di callback
	public function arraySearch($arr, $callback) {
		foreach ($arr as $key => $item) {
			if ($callback($item)) {
				return $key;
			}
		}
		return false;
	}

	public function arrayPluck($key, $data) {
		return array_reduce($data, function($result, $array) use($key) {
			isset($array[$key]) && $result[] = $array[$key];
			return $result;
		}, array());
	}

	/**
	 * Similar to getSalesEntityType, but return 'Events' in case of an activity
	 */
	public function getTouchModuleNameFromId($crmid) {
		global $adb,$table_prefix;

		$sql = "select c.setype, a.activitytype from {$table_prefix}_crmentity c left join {$table_prefix}_activity a on a.activityid = c.crmid where c.crmid = ?";
		$result = $adb->pquery($sql, array($crmid));
		// crmv@177095
		if ($adb->num_rows($result) == 0) {
			// try messages
			$sql = "select 'Messages' as setype from {$table_prefix}_messages where messagesid = ?";
			$result = $adb->pquery($sql, array($crmid));
		}
		// crmv@177095e
		$parent_module = $adb->query_result_no_html($result,0,"setype");
		if ($parent_module == 'Calendar') {
			$activitytype = $adb->query_result_no_html($result,0,"activitytype");
			if (!in_array($activitytype, array('Task', 'Emails'))) $parent_module = 'Events'; // crmv@152701
		}

		return $parent_module;
	}
	
	/**
	 * Returns a new instance of a module, but cache the result to avoid calls to the slow GetColumnFields
	 * Be careful, since the content of column_fields can be relative to another record
	 */
	public function getModuleInstance($module) {
		if (!array_key_exists($module, $this->cachedInstances)) {
			$crmModule = $module;
			if ($module == 'Events') $crmModule = 'Calendar';
			//crmv@164120
			if ($module == 'ChangeLog') {
				require_once('modules/ChangeLog/ChangeLog.php');
				$this->cachedInstances[$module] = ChangeLog::getInstance();
			} else {
				$this->cachedInstances[$module] = CRMEntity::getInstance($crmModule);
				vtlib_setup_modulevars($module, $this->cachedInstances[$module]);
			}
			// crmv@164120e
		}
		
		// reset some internal values
		if ($this->cachedInstances[$module]) {
			$this->cachedInstances[$module]->id = null;
			$this->cachedInstances[$module]->mode = null;
			$this->cachedInstances[$module]->parentid = null;
		}
		
		return $this->cachedInstances[$module];
	}
	
	/**
	 * Wrapper for getUserName with cache
	 */
	public function getUserName($userid) {
		if (!array_key_exists($userid, $this->cachedUserNames)) {
			$this->cachedUserNames[$userid] = getUserName($userid);
		}
		return $this->cachedUserNames[$userid];
	}
	
	// crmv@148861
	public function getOwnerName($userid) {
		if (!array_key_exists($userid, $this->cachedUserNames)) {
			$this->cachedUserNames[$userid] = getOwnerName($userid, false);
		}
		return $this->cachedUserNames[$userid];
	}
	// crmv@148861e
	
	/**
	 * Call the webservice Describe to get the fields in a module and cache them for future requests
	 */ 
	public function getModuleFields($module, $userid) {
		
		if (!array_key_exists($module, $this->cachedModuleFields)) {
			$response = $this->wsRequest($userid, 'describe', array('elementType'=> $module));
			$fields = $response['result']['fields'];
			if (is_array($fields)) {
				$fnames = array_map(function($f) {
					return $f["name"];
				}, $fields);
				$this->cachedModuleFields[$module] = array_combine($fnames, $fields);
			}
		}
		return $this->cachedModuleFields[$module];
	}
	
	/**
	 * This is an optimized version of getEntityName that uses the data already retrieved
	 * to calculate the record's name. If $record is not provided or empty , it will act as the 
	 * standard getEntityName
	 */
	public function getEntityNameFromFields($module, $crmid, $record = null) {
		global $adb, $table_prefix, $current_user;
		
		if (array_key_exists($crmid, $this->cachedEntityNames)) {
			return $this->cachedEntityNames[$crmid];
		}
		
		if (!array_key_exists($module, $this->cachedEntityColumns)) {
			// retrieve the info from DB
			if ($current_user && $current_user->id > 0) {
				// get profile (only the first one)
				require('user_privileges/requireUserPrivileges.php'); // crmv@39110
				$userProfile = $current_user_profiles[0];
				// override default field with the profile one
				$query = "SELECT COALESCE(p2en.fieldname, en.fieldname) as fieldname, tablename, entityidcolumn
					FROM {$table_prefix}_entityname en
					LEFT JOIN {$table_prefix}_profile2entityname p2en ON p2en.profileid = ? AND p2en.tabid = en.tabid
					WHERE en.modulename = ?";
				$params = array($userProfile, $module);
			} else {
				$query = "select fieldname, tablename, entityidcolumn from {$table_prefix}_entityname where modulename = ?";
				$params = array($module);
			}
			$result = $adb->pquery($query, $params);
			$row = $adb->FetchByAssoc($result, -1, false);
			$fieldnames = explode(',',$row['fieldname']);
			
			$modInstance = $this->getModuleInstance($module);

			$fields = array();
			// now retrieve field table and columns
			$query = "SELECT uitype, fieldname, tablename, columnname 
				FROM {$table_prefix}_field
				WHERE tabid = ? AND fieldname IN (".generateQuestionMarks($fieldnames).")";
			$res2 = $adb->pquery($query, array(getTabid($module), $fieldnames));
			while ($row2 = $adb->fetchByAssoc($res2, -1, false)) {
				$fields[$row2['fieldname']] = array(
					'name' => $row2['fieldname'],
					'uitype' => $row2['uitype'],
					'table' => $row2['tablename'],
					'column' => $row2['columnname'],
					'index' => $modInstance->tab_name_index[$row2['tablename']],
				);
			}
			$this->cachedEntityColumns[$module]['fields'] = $fields;
		}
		
		if (empty($this->cachedEntityColumns[$module]['fields'])) return '';
		
		$nameParts = array();
		foreach ($this->cachedEntityColumns[$module]['fields'] as $fname => $field) {
			if ($record && isset($record[$fname])) {
				// crmv@136175
				$value = $record[$fname];
				if ($field['uitype'] == 1015 && class_exists('PickListMulti')) {
					$value = PickListMulti::getTranslatedPicklist($value,$fname);
				}
				$nameParts[] = $value;
				// crmv@136175e
			} else {
				// retrieve from DB
				$value = getSingleFieldValue($field['table'], $field['column'],$field['index'], $crmid);
				if ($field['uitype'] == 1015 && class_exists('PickListMulti')) {
					$value = PickListMulti::getTranslatedPicklist($value,$fname);
				}
				$nameParts[] = $value;
			}
		}
		
		$entityname = implode(' ', $nameParts);
		
		// crmv@71388
		if ($module == 'Myfiles' && !empty($entityname)) {
			// use only the basename
			$entityname = basename($entityname);
		}
		// crmv@71388e
		
		$entityname = html_entity_decode($entityname, ENT_QUOTES, 'UTF-8'); // crmv@153161
		
		// save in name cache
		if (count($this->cachedEntityNames) < $this->entityNameCacheSize) {
			$this->cachedEntityNames[$crmid] = $entityname;
		}
		
		return $entityname;
	}

	// restituisce il linkid se il modulo ha il blocco per i commenti, falso altrimenti
	public function hasCommentsBlock($module) {
		global $adb, $table_prefix;

		$query = "select linkid from {$table_prefix}_links where tabid = ? and linktype = ? and linklabel = ?";
		$params = array(getTabid($module), 'DETAILVIEWWIDGET', 'DetailViewBlockCommentWidget');
		$res = $adb->pquery($query, $params);
		if ($res && $adb->num_rows($res) > 0) {
			return $adb->query_result($res, 0, 'linkid');
		}
		return false;
	}

	public function hasPDFMaker($module) {
		global $adb, $table_prefix, $current_user;
		
		if (!array_key_exists($module, $this->cachedPdfAvail)) {
			$this->cachedPdfAvail[$module] = false;
			if (vtlib_isModuleActive('PDFMaker') && isPermitted('PDFMaker', 'DetailView') == 'yes') {
				$res = $adb->pquery(
					"select p.templateid
					from {$table_prefix}_pdfmaker p
					left join {$table_prefix}_pdfmaker_userstatus u on u.templateid = p.templateid and u.userid = ?
					where p.module = ? and (u.is_active is null or u.is_active = 1)",
					array($current_user->id, $module)
				);
				$this->cachedPdfAvail[$module] = ($res && $adb->num_rows($res) > 0);
			}
		}
		
		return $this->cachedPdfAvail[$module];
	}
	
	public function hasNotes($module) {
		global $adb, $table_prefix;
		
		if (empty($this->cachedLinksModules)) {
			$res = $adb->pquery(
				"select t.name
				from {$table_prefix}_links l
				inner join {$table_prefix}_tab t on l.tabid = t.tabid
				where t.presence = 0 and l.linklabel = ?",
				array('DetailViewMyNotesWidget')
			);
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$this->cachedLinksModules[$row['name']] = true;
			}
		}
		
		return ($this->cachedLinksModules[$module] == true);
	}

	// restituisce la lista dei campi presenti in una related
	// TODO: rimuovere dipendenza da recordid
	public function getRelatedFields($userid, $module, $relmodule, $relationId, $recordid) {
		
		$focus = $this->getModuleInstance($module);
		$focusRel = $this->getModuleInstance($relmodule);
		
		if (empty($focus) || empty($focusRel)) return array();

		/*$relationInfo = getRelatedListInfoById($relationId);
		 $function_name = $relationInfo['functionName'];
		if (empty($function_name)) return array();
		$relatedListData = $instanceCache[$module]->$function_name(1, getTabid($module), $relationInfo['relatedTabId'], $relationInfo['actions']);
		*/

		$LVU = ListViewUtils::getInstance();

		// prendo tutti i campi del modulo
		$relfields = $this->getModuleFields($relmodule, $userid);

		// prendo i campi related
		$relatedListData = $LVU->getListViewHeader($focusRel,$relmodule,'',$focusRel->default_sort_order,$focusRel->default_order_by,$recordid,'',$module,true,true);

		// unisco le informazioni
		$outfields = array();
		foreach ($relatedListData as $k=>$v) {
			$fname = $v['fieldname'];
			if (array_key_exists($fname, $relfields)) {
				$relfields[$fname]['editable'] = false; // forzo in sola lettura
				$outfields[] = $relfields[$fname];
			}
		}

		return $outfields;
	}

	// details for the pdf maker
	// list of templates, list of available email addresses
	// $record parameter is not used
	public function getPDFMakerDetails($module, $record) {
		global $adb, $table_prefix, $current_user;
		
		if (!array_key_exists($module, $this->cachedPdfDetails)) {
			$ret = array();
			$this->cachedPdfDetails[$module] = $ret;
			
			$res = $adb->pquery(
				"select p.templateid, p.filename as templatename, p.module
				from {$table_prefix}_pdfmaker p
				left join {$table_prefix}_pdfmaker_userstatus u on u.templateid = p.templateid and u.userid = ?
				where (u.is_active is null or u.is_active = 1) ".($module ? " and p.module = ?" : '')."
				order by p.module, p.filename",
				array($current_user->id, $module)
			);
			if ($res) {
				// templates list
				$pdflist = array();
				while ($row = $adb->FetchByAssoc($res, -1, false)) {
					$pdflist[] = $row;
				}	
				$ret['templates'] = $pdflist;
				$ret['actions'] = array('sendemail');
				if (isPermitted('Documents', 'EditView') == 'yes') $ret['actions'][] = 'savedoc';
				$this->cachedPdfDetails[$module] = $ret;
			}
		}

		return $this->cachedPdfDetails[$module];
	}
	
	// crmv@100158
	function getFieldTypebyUIType($uitype) {
		global $adb, $table_prefix;
	
		if (empty($this->cachedFieldTypebyUIType)) {
			$query = "SELECT uitype, fieldtype FROM {$table_prefix}_ws_fieldtype";
			$result = $adb->query($query);
			if (!!$result && $adb->num_rows($result) > 0) {
				while ($row = $adb->fetchByAssoc($result, -1, false)) {
					$type = intval($row['uitype']);
					$fieldtype = $row['fieldtype'];
					$this->cachedFieldTypebyUIType[$type] = $fieldtype;
				}
			}
		}
		return $this->cachedFieldTypebyUIType[$uitype];
	}
	// crmv@100158 end

} // end class
