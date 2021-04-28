<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@71388 */


class TouchUploadFile extends TouchWSClass {

	public $basepath = 'storage/touch_uploads/';
	
	public function process(&$request) {
		
		$r = $this->checkUploadsTable();
		if (!$r) return array('success' => false, 'error' => "Unable to create tables");
		
		$r = $this->checkPath();
		if (!$r) return array('success' => false, 'error' => "Unable to create upload directory");
		
		$r = $this->cleanOldFiles();
		// no checks here
		
		$newfile = '';
		$uploadid = $this->saveUploadedFile($_FILES, $newfile);
		
		if ($uploadid > 0) {
			return array('success' => true, 'error' => '', 'uploadid' => $uploadid, 'filename' => $newfile);
		} else {
			return array('success' => false, 'error' => 'Unable to save the file');
		}
	}

	public function checkUploadsTable() {
		global $adb, $table_prefix;
	
		$table = $table_prefix.'_touch_uploads';
	
		$schema_table =
			'<schema version="0.3">
				<table name="'.$table.'">
					<opt platform="mysql">ENGINE=InnoDB</opt>
					<field name="uploadid" type="R" size="19">
						<KEY/>
					</field>
					<field name="userid" type="I" size="11" />
					<field name="uploadtime" type="T">
						<DEFAULT value="0000-00-00 00:00:00"/>
					</field>
					<field name="filetype" type="C" size="63" />
					<field name="path" type="C" size="1000" />
				</table>
			</schema>';
		if(!Vtecrm_Utils::CheckTable($table)) {
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
		}
		return true;
	}
	
	public function checkPath() {
		
		if (!is_dir($this->basepath)) {
			mkdir($this->basepath, 0755);
		}
		
		if (!is_writable($this->basepath)) return false;
		return true;
	}

	public function cleanOldFiles() {
		global $adb, $table_prefix;
		
		$table = $table_prefix.'_touch_uploads';
		
		$oldTime = date('Y-m-d H:i:s', time() - 3600*24*2);	// 2 days
		$res = $adb->pquery("SELECT uploadid, path FROM {$table} WHERE uploadtime < ?", array($oldTime));
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$path = $this->basepath . $row['path'];
				if (file_exists($path) && is_writable($path)) @unlink($path);
				$adb->pquery("DELETE FROM {$table} WHERE uploadid = ?", array($row['uploadid']));
			}
		}
		
		// now remove old files without database entry (something bad happened?)
		$list = glob($this->basepath. '*');
		if (is_array($list) && count($list) > 0) {
			foreach ($list as $file) {
				if (is_file($file) && is_writable($file) && filemtime($file) < (time() - 3600*24*2)) {
					@unlink($file);
				}
			}
		}
		
		return true;
	}

	public function saveUploadedFile($files, &$newfile) {
		global $adb, $table_prefix;
		global $upload_badext, $current_user;
		
		$table = $table_prefix.'_touch_uploads';
		
		$extMapping = array(
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
		);
		
		$uploadid = false;
		foreach($files as $fileindex => $fileinfo) {
			if ($fileinfo['name'] != '' && $fileinfo['size'] > 0){
				$binFile = sanitizeUploadFileName($fileinfo['name'], $upload_badext);
				$binFile = $this->sanitizeFileName($binFile);
				
				$uploadid = $adb->getUniqueID($table);
				$binFile = $uploadid . '_' . $current_user->id . '_' .$binFile;
				
				// add extension if missing
				$ext = pathinfo($binFile, PATHINFO_EXTENSION);
				if (empty($ext) && !empty($fileinfo['type'])) {
					if (array_key_exists($fileinfo['type'], $extMapping)) {
						if (substr($binFile, -1, 1) == '.') $binFile = substr($binFile, 0, -1);
						$binFile .= '.'.$extMapping[$fileinfo['type']];
					}
				}
				
				$upload_status = move_uploaded_file($fileinfo['tmp_name'], $this->basepath.$binFile);
				if (!$upload_status) $uploadid = false;
				
				if ($uploadid > 0) {
					$adb->pquery("INSERT INTO {$table} (uploadid, userid, uploadtime, filetype, path) VALUES (?,?,?,?,?)", array($uploadid, $current_user->id, date('Y-m-d H:i:s'), $fileinfo['type'], $binFile));
					$newfile = $binFile;
				}
				
				break; // save only one file
			}
		}
		return $uploadid;
	}
	
	public function sanitizeFileName($filename) {
		return preg_replace('/[^a-zA-Z0-9_.)(-]/', '', $filename);
	}
	
	public function getTouchUploadList($ids) {
		global $adb, $table_prefix, $current_user;
	
		if (!is_array($ids)) $ids = array($ids);
		$ids = array_map('intval', $ids);
		$list = array();
		$res = $adb->pquery("SELECT * FROM {$table_prefix}_touch_uploads WHERE userid = ? AND uploadid IN (".generateQuestionMarks($ids).")", array($current_user->id, $ids));
		if ($res) {
			while ($row = $adb->fetchByAssoc($res, -1, false)) {
				$prefix = $row['uploadid'].'_'.$row['userid'].'_';
				if (substr($row['path'], 0, strlen($prefix)) == $prefix) {
					$row['realname'] = substr($row['path'], strlen($prefix));
				} else {
					$row['realname'] = $row['path'];
				}
				$list[$row['uploadid']] = $row;
			}
		}
	
		return $list;
	}
	
	public function removeUploads($ids) {
		global $adb, $table_prefix, $current_user;
		
		if (!is_array($ids)) $ids = array($ids);
		if (empty($ids)) return true;
		
		$files = $this->getTouchUploadList($ids);
		
		// from db
		$res = $adb->pquery("DELETE FROM {$table_prefix}_touch_uploads WHERE userid = ? AND uploadid IN (".generateQuestionMarks($ids).")", array($current_user->id, $ids));
		
		// and files
		foreach ($files as $finfo) {
			$file = $this->basepath . $finfo['path'];
			if (is_file($file) && is_writable($file)) {
				@unlink($file);
			}
		}
		
		return true;
	}

}
