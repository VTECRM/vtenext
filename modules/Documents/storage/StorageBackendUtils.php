<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@95157 */

require_once('BackendBase.php');

class StorageBackendUtils extends SDKExtendableUniqueClass {

	// list of supported backends
	// !!! IF YOU CHANGE THIS ARRAY YOU HAVE TO EXECUTE THE METHOD syncDB()
	public $storageBackends = array(
		'file' => array('class' => 'BackendFile'),
		//'alfresco' => array('class' => 'BackendAlfresco'),
	);
	
	// the default backend to use
	public $defaultBackend = 'file';
	
	/**
	 * Insert into the picklist table _backend_name the list of supported backends
	 */
	public function syncDB() {
		global $adb, $table_prefix;
		$picklist_table = $table_prefix.'_backend_name';
		if (Vtecrm_Utils::CheckTable($picklist_table)) {
			$adb->query("delete from $picklist_table");
		}
		$values = array_keys($this->storageBackends);
		if (!empty($values)) {
			$fieldInstance = Vtecrm_Field::getInstance('backend_name',Vtecrm_Module::getInstance('Documents'));
			$fieldInstance->setNoRolePicklistValues($values);
		}
	}
	
	/**
	 * If module is specified, check backends restricted to the specified module
	 */
	public function getAvailableBackends($module = null) {
		$list = array();
		
		$backends = $this->storageBackends;
		
		if ($module && isModuleInstalled($module)) {
			$focus = CRMEntity::getInstance($module);
			if ($focus && !empty($focus->storageBackends)) {
				$backends = array_intersect_key($backends, $focus->storageBackends);
			}
		}
		
		foreach ($backends as $name => $info) {
			$label = $this->getBackendLabel($name);
			if ($label) {
				$list[$name] = $label;
			}
		}
		return $list;
	}
	
	/**
	 * Return the label for the specified backend
	 */
	public function getBackendLabel($name) {
		$inst = $this->getBackendClass($name);
		if ($inst) {
			return $inst->getLabel();
		}
		return '';
	}
	
	/**
	 * Return an instance to the backend class specified
	 */
	public function getBackendClass($name) {
		$class = $this->storageBackends[$name]['class'];
		$file = $this->storageBackends[$name]['file'] ?: $class.'.php';
		$inst = null;
		if (!empty($class)) {
			require_once($file);
			$inst = $class::getInstance();
		}
		return $inst;
	}
	
	/**
	 * Return the backend used by the attachment of a record
	 */
	public function getBackendForCrmid($module, $crmid) {
		global $adb, $table_prefix;
		
		$FS = FileStorage::getInstance();
		$attid = $FS->getAttachmentId($crmid);
		if ($attid) {
			$sql = "SELECT backend_name FROM ".$table_prefix."_attachments WHERE attachmentsid = ?" ;
			$res = $adb->pquery($sql, array($attid));
			if ($res) {
				return $adb->query_result_no_html($res, 0, 'backend_name');
			}
		}
		return null;
	}
	
	/**
	 * Upload an attachment
	 */
	public function uploadFile($backend, $module, &$focus = null, $backendOptions = null) {
		global $adb, $table_prefix;
		
		$bclass = $this->getBackendClass($backend);
		
		$attid = null;
		$ekey = $bclass->saveFile($focus, $backendOptions, $attid);
		
		if ($ekey && $attid) {
			// update the key info
			$adb->pquery("UPDATE {$table_prefix}_attachments SET backend_name = ?, backend_key = ? WHERE attachmentsid = ?", array($backend, $ekey, $attid));
			unset($_REQUEST['upload_error']);
			return true;
		}
		
		// error occurred!
		$_REQUEST['upload_error'] = true;
		
		return false;
	}
	
	/**
	 * Upload a new revision for an existing attachment
	 */
	public function uploadRevision($backend, $module, &$focus = null, $userEmail = null) {
		global $adb, $table_prefix, $current_user;
		
		$record = $focus->id;
		$bclass = $this->getBackendClass($backend);
		$now = date('Y-m-d H:i:s'); //crmv@125395
		
		$FS = FileStorage::getInstance();
		$info = $FS->getFileInfoByCrmid($record);
		
		$oldkey = $info['backend_key'];
		$doc_precedente = intval($info['attachmentsid']);
		
		$attid = null;
		$ekey = $bclass->saveFileRevision($oldkey, $focus, null, $attid);
		
		if ($ekey && $attid) {
			// update the key info
			$adb->pquery("UPDATE {$table_prefix}_attachments SET backend_name = ?, backend_key = ? WHERE attachmentsid = ?", array($backend, $ekey, $attid));
		} else {
			return false;
		}
		
		// Update revision table
		if ($doc_precedente > 0){

			$q = "SELECT MAX(revision)+1 as rev FROM crmv_docs_revisioned WHERE crmid = ?";
			$res = $adb->pquery($q, array($record));
			if ($res) {
				$revision = $adb->query_result_no_html($res,0,'rev') ?: '1';
			}

			$ins = "INSERT INTO crmv_docs_revisioned (crmid, attachmentid, revision, userid, revisiondate, user_email) VALUES (?,?,?,?,?,?)";
			$params = Array($record, $doc_precedente, $revision,$current_user->id,$adb->formatDate($now, true),$userEmail); //crmv@125395
			$adb->pquery($ins,$params);
		}
		
		return true;
	}
	
	/**
	 * Get all the revisions for an attachment
	 */
	public function getRevisions($module, $crmid) {
		global $adb, $table_prefix;
		
		$q = "SELECT attachmentid,revision,userid,revisiondate,NAME,path,user_email
			FROM crmv_docs_revisioned c
			LEFT JOIN {$table_prefix}_attachments a ON c.attachmentid = a.attachmentsid
			WHERE crmid = ?
			ORDER BY revision desc";
		$res = $adb->pquery($q, array($crmid));

		$revisions = array();
		if ($res && $adb->num_rows($res) > 0) {
			while($row = $adb->fetchByAssoc($res)){
				$revisions[] = $row;
			}
		}
		
		return $revisions;
	}
	
	/**
	 * Check if the provided record supports metadata
	 */
	public function checkMetadata($module, $crmid) {
		$backend = $this->getBackendForCrmid($module, $crmid);
		if ($backend) {
			$bclass = $this->getBackendClass($backend);
			return $bclass->hasMetadata;
		}
		return false;
	}
	
	/**
	 * Reads the metadata
	 */
	public function readMetadata($module, $crmid) {
		$FS = FileStorage::getInstance();
		$info = $FS->getFileInfoByCrmid($crmid);
		
		$attid = $info['attachmentsid'];
		$backend = $info['backend_name'];
		$key = $info['backend_key'];
		
		$bclass = $this->getBackendClass($backend);
		
		return $bclass->readMetadata($attid, $key);
	}
	
	/**
	 * Updates the metadata
	 */
	public function updateMetadata($module, $crmid, $data) {
		$FS = FileStorage::getInstance();
		$info = $FS->getFileInfoByCrmid($crmid);
		
		$attid = $info['attachmentsid'];
		$backend = $info['backend_name'];
		$key = $info['backend_key'];
		
		$bclass = $this->getBackendClass($backend);
		
		return $bclass->updateMetadata($attid, $key, $data);
	}
	
	/**
	 * Retrieve a list of all the attachments (usually limited to 1) of a record
	 */
	public function getAttachments($module, $crmid) {
		global $adb, $table_prefix;
		
		$list = array();
		$res = $adb->pquery(
			"SELECT a.* FROM {$table_prefix}_attachments a
			INNER JOIN {$table_prefix}_crmentity c ON c.crmid = a.attachmentsid
			INNER JOIN {$table_prefix}_seattachmentsrel sa ON sa.attachmentsid = a.attachmentsid
			WHERE c.deleted = 0 AND sa.crmid = $crmid",
			array($crmid)
		);
		
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->fetchByAssoc($res, -1, false)) {
				$list[] = $row;
			}
		}
		
		return $list;
	}
	
	/**
	 * Delete permanently all the file attachments for the given record
	 */
	public function deleteAttachments($module, $crmid) {
		global $adb, $table_prefix;
		
		// first get the attachments
		$list = $this->getAttachments($module, $crmid);
		if (!is_array($list)) return false;
		
		foreach ($list as $attinfo) {
			$attid = $attinfo['attachmentsid'];
			$backend = $attinfo['backend_name'];
			$bkey = $attinfo['backend_key'];
			
			$bclass = $this->getBackendClass($backend);
			if (!$bclass) continue;
			
			$r = $bclass->deleteFile($attid, $bkey);
			if (!$r) return false;
			
			// delete the attachment entry
			$adb->pquery("DELETE FROM {$table_prefix}_attachments WHERE attachmentsid = ?", array($attid));
			$adb->pquery("DELETE FROM {$table_prefix}_crmentity WHERE crmid = ?", array($attid));
		}
		
		// delete all relations
		$adb->pquery("DELETE FROM {$table_prefix}_seattachmentsrel WHERE crmid = ?", array($crmid));
		
		return true;
	}
	
	/**
	 * Delete all attachments not attached to anything
	 */
	public function deleteOrphanAttachments() {
		global $adb, $table_prefix;
		
		$list = array();
		$res = $adb->query(
			"SELECT a.* FROM {$table_prefix}_attachments a
			INNER JOIN {$table_prefix}_seattachmentsrel sa ON sa.attachmentsid = a.attachmentsid
			LEFT JOIN {$table_prefix}_crmentity c ON c.crmid = sa.crmid
			WHERE c.crmid IS NULL"
		);
		
		if ($res && $adb->num_rows($res) > 0) {
			while ($attinfo = $adb->fetchByAssoc($res, -1, false)) {
				$attid = $attinfo['attachmentsid'];
				$backend = $attinfo['backend_name'];
				$bkey = $attinfo['backend_key'];
			
				$bclass = $this->getBackendClass($backend);
				if (!$bclass) continue;
			
				$r = $bclass->deleteFile($attid, $bkey);
				if (!$r) return false;
			
				// delete the attachment entry
				$adb->pquery("DELETE FROM {$table_prefix}_attachments WHERE attachmentsid = ?", array($attid));
				$adb->pquery("DELETE FROM {$table_prefix}_crmentity WHERE crmid = ?", array($attid));
				$adb->pquery("DELETE FROM {$table_prefix}_seattachmentsrel WHERE attachmentsid = ?", array($attid));
				
			}
		}
		
		return $list;
	}
	
	/**
	 * Download a file using the appropriate backend
	 */
	public function downloadFile($module, $crmid, $attid, $raw=false) { // crmv@189246
		global $adb, $table_prefix;
		
		$dbQuery = "SELECT backend_name, backend_key, path, name FROM ".$table_prefix."_attachments WHERE attachmentsid = ?" ;
		$result = $adb->pquery($dbQuery, array($attid)) or die("Couldn't get file list");
		if ($adb->num_rows($result) == 1) {
			$backend = $adb->query_result_no_html($result, 0, "backend_name");
			$ekey = $adb->query_result_no_html($result, 0, "backend_key");
			
			if (!$backend) {
				// fallback on the file backend
				$backend = 'file';
				$path = $adb->query_result_no_html($result, 0, "path");
				$name = $adb->query_result_no_html($result, 0, "name");
				$bclass = $this->getBackendClass($backend);
				if ($bclass && method_exists($bclass, 'generateKey')) {
					$ekey = $bclass->generateKey($attid);
				}
			} else {
				$bclass = $this->getBackendClass($backend);
			}
			
			if (!$ekey) {
				echo "Record is not saved correctly.";
				return false;
			}
			
			$bclass->retrieveFile($attid, $ekey, array('raw'=>$raw)); // crmv@189246
			
		} else {
			echo "Record doesn't exist.";
			return false;
		}
		
		return true;
	}
	
	/**
	 * Increment by 1 the download counter
	 */
	public function incrementDownloadCount($module, $crmid, $attid = null) {
		global $adb, $table_prefix;
		
		$FS = FileStorage::getInstance();
		$info = $FS->getFileInfoByCrmid($crmid);
		
		if (empty($attid)) {
			$attid = $info['attachmentsid'];
		}
		
		$backend = $info['backend_name'];
		$key = $info['backend_key'];
		
		if ($backend && $key) {
			$bclass = $this->getBackendClass($backend);
			$bclass->incrementDownloadCount($key); // crmv@167234
		}
		
		// now increment it locally
		if ($module == 'Documents') {
			$sql = "UPDATE {$table_prefix}_notes SET filedownloadcount = COALESCE(filedownloadcount,0)+1 WHERE notesid= ?"; // crmv@152087
			$res = $adb->pquery($sql,array($crmid));
		} elseif ($module == 'Myfiles') {
			$sql = "UPDATE {$table_prefix}_myfiles SET filedownloadcount = COALESCE(filedownloadcount,0)+1 WHERE myfilesid= ?"; // crmv@152087
			$res = $adb->pquery($sql,array($crmid));
		}
		
	}
	
	// crmv@152087
	public function saveDownloadChangelog($module, $crmid) {
		global $adb, $table_prefix, $current_user;
		
		if ($current_user && vtlib_isModuleActive('ChangeLog')) {
			// crmv@164120
			$obj = ChangeLog::getInstance();
			$obj->column_fields['modified_date'] = date('Y-m-d H:i:s');
			$obj->column_fields['audit_no'] = $obj->get_revision_id($crmid);
			$obj->column_fields['user_id'] = $current_user->id;
			$obj->column_fields['parent_id'] = $crmid;
			$obj->column_fields['user_name'] = $current_user->column_fields['user_name'];
			$obj->column_fields['description'] = Zend_Json::encode(array('DownloadFile',$module,$crmid));
			$obj->save();
			// crmv@164120e
		}
	}
	// crmv@152087e
	
	/**
	 * Return values: 0 = ok, 1 = corrupted, 2 = not found, 3 = other error
	 */
	public function checkIntegrity($module, $crmid, $attid = null) {
		
		$FS = FileStorage::getInstance();
		$info = $FS->getFileInfoByCrmid($crmid);
		
		if (empty($attid)) {
			$attid = $info['attachmentsid'];
		}
		
		if (empty($attid)) return 2;
		
		$backend = $info['backend_name'];
		$key = $info['backend_key'];
		
		$bclass = $this->getBackendClass($backend);
		if (!$bclass) return 3;
		
		return $bclass->checkIntegrity($attid, $key, null);
	}
	
	/**
	 * Convert all legacy backends (ie: 'I') to the new ones
	 */
	public function convertLegacyBackends() {
		global $adb, $table_prefix;
		
		$oldLocation = 'I';
		$newBackend = 'file';
		
		$bclass = $this->getBackendClass($newBackend);
		
		$modules = array('Documents', 'Myfiles');
		
		foreach ($modules as $module) {
			$currentModule = $module;
			
			$focus = CRMEntity::getInstance($module);
			$table = $focus->table_name;
			$index = $focus->table_index;
			
			$res = $adb->pquery(
				"SELECT c.crmid, a.attachmentsid
				FROM $table t
				INNER JOIN {$table_prefix}_crmentity c ON c.crmid = t.{$index}
				INNER JOIN {$table_prefix}_seattachmentsrel sa ON sa.crmid = c.crmid
				INNER JOIN {$table_prefix}_attachments a ON a.attachmentsid = sa.attachmentsid
				WHERE t.filelocationtype = ?",
				array($oldLocation)
			);
		
			if ($res && $adb->num_rows($res) > 0) {
				while ($row = $adb->fetchByAssoc($res, -1, false)) {
					$crmid = $row['crmid'];
					$attid = $row['attachmentsid'];
					
					// generate and update the backend key
					$ekey = $bclass->generateKey($attid);
					if ($ekey) {
						$adb->pquery("UPDATE {$table_prefix}_attachments set backend_key = ?, backend_name = ? WHERE attachmentsid = ?", array($ekey, $newBackend, $attid));
					}
					
					// update the info
					$adb->pquery("UPDATE {$table} set filelocationtype = ?, backend_name = ? WHERE {$index} = ?", array('B', $newBackend, $crmid));
				}
				
			}
			
			
		}
		
	}
		
}

function checkMetadata($instance) {
	// extract the record
	if (preg_match('#\([\'"]?([0-9]+)[\'"]?\)#', $instance->linkurl, $matches)) {
		$module = $instance->module();
		$crmid = intval($matches[1]);
		if ($module && $crmid) {
			$SBU = StorageBackendUtils::getInstance();
			return $SBU->checkMetadata($module, $crmid);
		}
	}
	return false;
}