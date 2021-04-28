<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@95157 */

require_once('include/BaseClasses.php');

class FileStorage extends SDKExtendableUniqueClass {

	/**
	 * The default base folder for the storage of files.
	 * You can change it with the changeStorageFolder function
	 */
	protected $storageFolder = 'storage/';
	
	/**
	 * The last inserted attachmentid.
	 */
	protected $lastInsertedId = null;
	
	
	/**
	 * Change the base folder used to save the files.
	 * Can be called several times, provided that the corrispondant restoreStorageFolder() is called
	 */
	public function changeStorageFolder($folder) {
		if (!is_array($this->_oldStorageFolder)) $this->_oldStorageFolder = array();
		array_push($this->_oldStorageFolder, $folder);
		$this->storageFolder = $folder;
	}
	
	/**
	 * Revert the base storage folder to the previous value (if present)
	 */
	public function restoreStorageFolder() {
		if (!is_array($this->_oldStorageFolder) || count($this->_oldStorageFolder) == 0) return false;
		$this->storageFolder = array_pop($this->_oldStorageFolder);
		return true;
	}

	/**
	* This function is used to add the vte_attachments. This will call the function uploadAndSaveFile
	* which will upload the attachment into the server and save that attachment information in the database.
	* @param int $id  - entity id to which the vte_files to be uploaded
	* @param string $module  - the current module name
	*/
	function insertIntoAttachment($id, $module, &$focus = null, $options = array()) {
		global $log;
		
		$log->debug("Entering into insertIntoAttachment($id,$module) method.");

		$file_saved = false;
		$this->lastInsertedId = null;

		foreach($_FILES as $fileindex => $files) {
			if($files['name'] != '' && $files['size'] > 0) {
				$files['original_name'] = vtlib_purify($_REQUEST[$fileindex.'_hidden']);
				
				$file_saved = $this->uploadAndSaveFile($id,$module,$files,isset($_POST['copy_not_move']), $focus, $options); //crmv@62340

				//crmv@45561
				if (!$file_saved) {
					$_REQUEST['upload_error'] = true;
				}
				//crmv@45561e
			}
		}

		$log->debug("Exiting from insertIntoAttachment($id,$module) method.");
		
		return $file_saved;
	}

	/**
	* This function is used to upload the attachment in the server and save that attachment information in db.
	* @param int $id  - entity id to which the file to be uploaded
	* @param string $module  - the current module name
	* @param array $file_details  - array which contains the file information(name, type, size, tmp_name and error)
	*   return void
	*/
	function uploadAndSaveFile($id,$module,$file_details,$copy=false, &$focus = null, $options = array()) {		//crmv@22123
		global $log, $adb, $current_user,$table_prefix;
		
		$log->debug("Entering into uploadAndSaveFile($id,$module) method.");
		
		// clean up the file and validate it
		$binFile = $this->prepareUpload($module, $file_details);
		if (!$binFile) {
			$log->warn("Skip the save attachment process.");
			return false;
		}

		// copy the uploaded file in the local storage
		$result = $this->uploadFile($file_details, $binFile, $copy, $focus, $options);
		
		if (!$result) {
			//crmv@45561 - clear the filename
			if ($module == 'Documents') {
				$adb->pquery("update {$table_prefix}_notes set filename = null where notesid = ?", array($id));
			}
			//crmv@45561e
			$log->error("File not uploaded correctly.");
			return false;
		} else {
			$upload_file_path = $result['path'];
			$current_id = $result['crmid'];
		}

		// insert the info in the attachment table
		$r = $this->insertAttachment($current_id, $upload_file_path, $module, $file_details, $binFile, $focus);
		
		if (!$r) {
			$log->error("Unable to insert the attachment in the table.");
			return false;
		}
		
		// create the relation with the parent module
		$r = $this->updateRelations($module, $current_id, $focus);
		if (!$r) {
			$log->error("Unable to update the attachment's relations.");
			return false;
		}
		
		$log->debug("Exiting from uploadAndSaveFile($id,$module) method.");
		return true;
	}
	
	// crmv@111124
	/**
	 * Rename the file in order to be safely saved in the server
	 */
	public function sanitizeFilename($filename) {
		global $upload_badext;
		
		$filename = preg_replace('/\s+/', '_', $filename); // replace space with _ in filename
		$filename = str_replace(':', '', $filename); // crmv@95157 - remove colons (Windows doesn't like them!)
		$filename = str_replace('/', '', $filename); // crmv@132704
		
		// file extension check
		$ext_pos = strrpos($filename, ".");
		if ($ext_pos !== false) {
			$ext = strtolower(substr($filename, $ext_pos + 1));
			if (in_array($ext, $upload_badext)) {
				$filename .= ".txt";
			}
		}
		
		$filename = correctEncoding($filename);	//crmv@25554
		
		return $filename;
	}
	
	protected function prepareUpload($module,&$file_details) {
		
		if(isset($file_details['original_name']) && $file_details['original_name'] != null) {
			$file_name = $file_details['original_name'];
		} else {
			$file_name = $file_details['name'];
		}

		if (empty($file_name)) return false;

		$binFile = $this->sanitizeFilename($file_name);

		//only images are allowed for these modules
		if($module == 'Contacts' || $module == 'Products') {
			if ('true' != validateImageFile($file_details)) {
				return false;
			}
		}
		
		return $binFile;		
	}
	// crmv@111124e
		
	protected function uploadFile(&$file_details, $binFile, $copy = false, &$focus = null, $options = array()) {
		global $adb, $table_prefix;
		
		$filetmp_name = $file_details['tmp_name'];

		//get the file path inwhich folder we want to upload the file
		if ($options['file_path'] && is_dir($options['file_path'])) {
			$upload_file_path = $options['file_path'];
		} else {
			$upload_file_path = $this->decideFilePath();
		}
		
		if (substr($upload_file_path, -1) != '/') $upload_file_path .= '/';
		
		$current_id = $adb->getUniqueID($table_prefix."_crmentity");

		//upload the file in server
		//crmv@22123
		if ($copy) {
			$upload_status = copy($filetmp_name,$upload_file_path.$current_id."_".$binFile);
		} else {
			$upload_status = move_uploaded_file($filetmp_name,$upload_file_path.$current_id."_".$binFile);
		}
		//crmv@22123e
		
		// crmv@205309
		// save it into the database
		if (!$upload_status) {
			$upload_status = $this->uploadFileToDB($filetmp_name, $upload_file_path.$current_id."_".$binFile, $current_id);
		}
		// crmv@205309e
		
		if ($upload_status) return array('crmid'=>$current_id, 'path' => $upload_file_path);
		return false;
	}
	
	// crmv@205309
	protected function uploadFileToDB($tempfile, $destfile, $fileid) {
		$FSDB = FileStorageDB::getInstance();
		
		$ok = $FSDB->saveFile($tempfile, $destfile, $fileid);
		
		return $ok;
	}
	// crmv@205309e
	
	public function insertAttachment($attid, $path, $module, &$file_details, $binFile, &$focus = null) {
		global $adb, $table_prefix, $current_user;
		
		$id = $focus->id;
		
		// prepare some variables
		$date_var = date('Y-m-d H:i:s'); //crmv@15369
		$filetype = $file_details['type'];
		$filename = ltrim(basename(" ".$binFile)); //allowed filename like UTF-8 characters
		$ownerid = $focus->column_fields['assigned_user_id'] ?: $current_user->id;
		
		//This is only to update the attached filename in the vte_notes vte_table for the Documents module
		if ($module=='Documents') {
			$sql="update ".$table_prefix."_notes set filename=? where notesid = ?";
			$params = array($filename, $id);
			$adb->pquery($sql, $params);
		} elseif ($module=='Myfiles') {
			$sql="update ".$table_prefix."_myfiles set filename=? where myfilesid = ?";
			$params = array($filename, $id);
			$adb->pquery($sql, $params);
		}
			
		if($module == 'Contacts' || $module == 'Products') {
			$setype = $module." Image";
		} else {
			$setype = $module." Attachment";
		}
		
		// crmv@150773
		$sql1 = "insert into ".$table_prefix."_crmentity (crmid,smcreatorid,smownerid,setype,createdtime,modifiedtime) values (?, ?, ?, ?, ?, ?)";
		$params1 = array($attid, $current_user->id, $ownerid, $setype, $adb->formatDate($date_var, true), $adb->formatDate($date_var, true));
		// crmv@150773e
		$r = $adb->pquery($sql1, $params1);
		if (!$r) return false;

		$sql2 = "insert into ".$table_prefix."_attachments (attachmentsid, name, description, type, path) values(?, ?, ?, ?, ?)";
		$params2 = array($attid, $filename, $focus->column_fields['description'], $filetype, $path);
		$r = $adb->pquery($sql2, $params2);
		if (!$r) return false;
			
		$this->lastInsertedId = $attid;
		
		return true;
	}
	
	public function updateRelations($module, $attid, &$focus = null) {
		global $adb, $table_prefix;
		
		$id = $focus->id;
		
		// TODO: get rid of these parameters from request
		if($_REQUEST['mode'] == 'edit') {
			if($id != '' && $_REQUEST['fileid'] != '') {
				$delquery = 'delete from '.$table_prefix.'_seattachmentsrel where crmid = ? and attachmentsid = ?';
				$delparams = array($id, intval($_REQUEST['fileid'])); // crmv@37463
				$adb->pquery($delquery, $delparams);
			}
		}
		
		// clear any old relations (only for documents)
		if($module == 'Documents') {
			$query = "delete from ".$table_prefix."_seattachmentsrel where crmid = ?";
			$qparams = array($id);
			$adb->pquery($query, $qparams);
		}
			
		if($module == 'Contacts') {
			$att_sql = "select ".$table_prefix."_seattachmentsrel.attachmentsid 
			from ".$table_prefix."_seattachmentsrel 
			inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_seattachmentsrel.attachmentsid 
			where ".$table_prefix."_crmentity.setype='Contacts Image' and ".$table_prefix."_seattachmentsrel.crmid=?";
			$res=$adb->pquery($att_sql, array($id));
			$attachmentsid= $adb->query_result($res,0,'attachmentsid');
			if($attachmentsid !='' ) {
				$delquery='delete from '.$table_prefix.'_seattachmentsrel where crmid=? && attachmentsid=?';
				$adb->pquery($delquery, array($id, $attachmentsid));
				$crm_delquery="delete from ".$table_prefix."_crmentity where crmid=?";
				$adb->pquery($crm_delquery, array($attachmentsid));
				$sql5='insert into '.$table_prefix.'_seattachmentsrel values(?,?)';
				$adb->pquery($sql5, array($id, $attid));
			} else {
				$sql3='insert into '.$table_prefix.'_seattachmentsrel values(?,?)';
				$adb->pquery($sql3, array($id, $attid));
			}
		} else {
			$sql3='insert into '.$table_prefix.'_seattachmentsrel values(?,?)';
			$adb->pquery($sql3, array($id, $attid));
		}
		
		return true;
	}
	
	public function getLastInsertedId() {
		return $this->lastInsertedId;
	}
	
	public function getFileInfo($attid) {
		global $adb, $table_prefix;
		
		$sql = "SELECT * FROM {$table_prefix}_attachments WHERE attachmentsid = ?";
		$res = $adb->pquery($sql, array($attid));
		if ($res && $adb->num_rows($res) > 0) {
			$info = $adb->fetchByAssoc($res, -1, false);
			$info['local_path'] = $info['path'].$info['attachmentsid'].'_'.$info['name']; // crmv@53658
			return $info;
		}
		return null;
	}
	
	public function getAttachmentId($crmid) {
		global $adb, $table_prefix;

		$sql = "SELECT attachmentsid FROM {$table_prefix}_seattachmentsrel WHERE crmid = ?";
		$res = $adb->pquery($sql, array($crmid));
		if ($res && $adb->num_rows($res) > 0) {
			$attid = $adb->query_result_no_html($res, 0, 'attachmentsid');
			return $attid;
		}
		return null;
	}
	
	
	public function getFileInfoByCrmid($crmid) {
		$attid = $this->getAttachmentId($crmid);
		if ($attid) {
			return $this->getFileInfo($attid);
		}
		return null;
	}
	
	public function getParentId($attid) {
		global $adb, $table_prefix;
		
		$sql = "SELECT crmid FROM {$table_prefix}_seattachmentsrel WHERE attachmentsid = ?";
		$res = $adb->pquery($sql, array($attid));
		if ($res && $adb->num_rows($res) > 0) {
			$crmid = $adb->query_result_no_html($res, 0, 'crmid');
			return $crmid;
		}
		return null;
	}
	
	public function checkIntegrity($attid) {
		global $adb, $table_prefix;
		
		$crmid = $this->getParentId($attid);
		$info = $this->getFileInfo($attid);
		
		$dbQuery = "SELECT filestatus, filelocationtype, filename FROM ".$table_prefix."_notes where notesid= ?";
		$result = $adb->pquery($dbQuery,array($crmid));
		
		$file_status = $adb->query_result_no_html($result,0,"filestatus");
		$download_type = $adb->query_result_no_html($result,0,"filelocationtype");
		$name = $info['name'];
		
		if($download_type == 'I' || $download_type == 'B') {
			$saved_filename = $attid."_".$name;
			$filepath = $info['path'];
		} elseif($download_type == 'E') {
			$saved_filename = $name;
		} else {
			return 3;
		}
			
		if (!is_readable($filepath.$saved_filename)) {
			if($file_status == 1) {
				$dbQuery1 = "update ".$table_prefix."_notes set filestatus = 0 where notesid= ?";
				$result1 = $adb->pquery($dbQuery1,array($crmid));
				return 1;
			} else {
				return 2;
			}
		}
		
		return 0;
	}
	
	public function downloadFile($attid, $options = array()) { // crmv@189246
		global $adb, $table_prefix, $default_charset;
		
		$dbQuery = "SELECT type, path, name FROM {$table_prefix}_attachments WHERE attachmentsid = ?" ;
		$result = $adb->pquery($dbQuery, array($attid));
		if ($adb->num_rows($result) == 1) {
			$fileType = @$adb->query_result_no_html($result, 0, "type");
			$filepath = @$adb->query_result_no_html($result, 0, "path");
			$dlname = @$adb->query_result($result, 0, "name");
			$dlname = html_entity_decode($dlname, ENT_QUOTES, $default_charset);
			
			$path = $filepath.$attid."_".$dlname;

			// crmv@205309
			if (!is_readable($path)) {
				$FSDB = FileStorageDB::getInstance();
				if ($FSDB->isFilePresent($attid)) {
					$tmppath = $FSDB->createTempFile($attid);
					if ($tmppath) $path = $tmppath;
				}
				// try to read from db storage
			}
			
			if ($options['return_content']) {
				return file_get_contents($path);
			} else {
				$this->rawFileOutput($path, $dlname, $fileType, (isset($options['raw'])) ? $options['raw'] : false); // crmv@189246
			}
			// crmv@205309e
			
		} else {
			echo "Attachment does not exist";
		}
	}
	
	public function rawFileOutput($path, $downloadName = null, $fileType = null, $raw = false) { // crmv@189246
		
		// name for the download
		if (empty($downloadName)) {
			$downloadName = basename($path);
		}
		$downloadName = str_replace('"', '', $downloadName);
		
		// the mime type
		if (empty($fileType)) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$fileType = finfo_file($finfo, $path);
			finfo_close($finfo);
		}
		
		$filesize = filesize($path);
		$fp = fopen($path, "rb");
		
		// terminate ob and session, to allow concurrent requests
		if (!$raw) { // crmv@189246
			@ob_end_clean();
			VteSession::close(); // crmv@152087
		}
		
		// headers
		if (!$raw) { // crmv@189246
			header("Content-type: $fileType");
			header("Content-length: $filesize");
			header("Cache-Control: private");
			header("Content-Disposition: attachment; filename=\"$downloadName\""); //crmv@3079m
			header("Content-Description: PHP Generated Data");
		}
		
		// output and terminate script
		fpassthru($fp);
		if (!$raw) exit; // crmv@189246
		
	}

	/**
	 * 	This function is used to decide the File Storage Path in where we will upload the file in the server.
	 * 	return string $filepath  - filepath where the file should be stored in the server
	 */
	public function decideFilePath() {
		global $log, $new_folder_storage_owner;	//crmv@98116
		
		if ($log) $log->debug("Entering into decideFilePath() method ...");
		
		$base = $this->storageFolder;
		
		if (substr($base, -1) != '/') $base .= '/';
		
		$year  = date('Y');
		$month = date('F');
		$day  = date('j');

		if($day > 0 && $day <= 7)
			$week = 'week1';
		elseif($day > 7 && $day <= 14)
			$week = 'week2';
		elseif($day > 14 && $day <= 21)
			$week = 'week3';
		elseif($day > 21 && $day <= 28)
			$week = 'week4';
		else
			$week = 'week5';
			
		$path = $base.$year.'/'.$month.'/'.$week.'/';

		$this->createStorageDir($path); // crmv@205309 - moved below

		if ($log) {
			$log->debug("Year=$year & Month=$month & week=$week && filepath=\"$base\"");
			$log->debug("Exiting from decideFilePath() method ...");
		}
		
		return $path;
	}
	
	// crmv@205309
	/**
	 * Create a folder in the storage hierarchy with the proper permissions
	 * @return bool False in case of write error
	 */
	public function createStorageDir($path) {
		global $new_folder_storage_owner;
		
		if (substr($path, -1) != '/') $path	 .= '/';
		
		if(!is_dir($path)) {
			$r = mkdir($path, 0777, true);
			if ($r === false) return false;
			
			// crmv@195947
			if (!is_file($path."index.html")) {
				@file_put_contents($path."index.html", "<html></html>\n"); // crmv@195947
			}
			if (!is_file(dirname($path)."/index.html")) {
				@file_put_contents(dirname($path)."/index.html", "<html></html>\n");
			}
			// crmv@195947e
			//crmv@98116 crmv@201366
			if (!empty($new_folder_storage_owner)) {
				$objs = explode('/',$path);
				if (!empty($objs)) {
					$tmp = '';
					foreach($objs as $obj) {
						$_path = $tmp.$obj;
						$tmp .= "$obj/";
						if (is_dir($_path)) {
							if ($new_folder_storage_owner['user'] != '') @chown($_path, $new_folder_storage_owner['user']);
							if ($new_folder_storage_owner['group'] != '') @chgrp($_path, $new_folder_storage_owner['group']);
						}
					}
				}
			}
			//crmv@98116e crmv@201366e
		}
		
		return true;
	}
	// crmv@205309e
	
}