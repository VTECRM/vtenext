<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/utils/utils.php');

global $currentModule,$upload_badext,$table_prefix,$adb;

$return_arr = Array(
	'success'=>false,
	'rename'=>false,
	'message'=>getTranslatedString('LBL_UPLOAD_ERROR','Contacts'),
);

$finalize_upload = vtlib_purify($_REQUEST['finalize_upload']);
$uniqueid = vtlib_purify($_REQUEST['uniqueid']);
$folderid = vtlib_purify($_REQUEST['folderid']);

$tmpdir = '/tmp/vte_myfiles_upload_'.$uniqueid.'/';

$LVU = ListViewUtils::getInstance();
$SBU = StorageBackendUtils::getInstance();

//check if a file is already existing
if (!empty($_FILES)) {
	$realfiles = $_FILES;
	//check if a file is already existing
	$files_to_upload = Array();
	foreach ($realfiles as $index=>$file_arr){
		$filename = $file_arr['name'];
		$filename = from_html(preg_replace('/\s+/', '_', $filename));
		$binFile = sanitizeUploadFileName($filename, $upload_badext);
		$filename = ltrim(basename(" ".$binFile)); //allowed filename like UTF-8 characters
		$files_to_upload[$index] = strtolower($filename);
	}
	$where = " and {$table_prefix}_myfiles.folderid = ? and {$table_prefix}_myfiles.filename in (".generateQuestionMarks($files_to_upload).")";
	$sql = replaceSelectQuery($LVU->getListQuery($currentModule,$where),$table_prefix.'_crmentity.crmid,'.$table_prefix.'_myfiles.filename,'.$table_prefix.'_myfiles.title');
	$res = $adb->pquery($sql,Array($folderid,$files_to_upload));
	if ($res && $adb->num_rows($res)>0){ //find duplicates and let user choose what to do (replace,rename,abort)
		$files_to_upload_reverse = array_flip($files_to_upload);
		@mkdir($tmpdir);
		$files_to_rename = Array();
		while($row = $adb->fetchByAssoc($res,-1,false)){
			//save all tempfiles to another place
			$fext = pathinfo($row['filename'], PATHINFO_EXTENSION);
			$fname = pathinfo($row['filename'], PATHINFO_FILENAME);
			$result = move_uploaded_file($realfiles[$files_to_upload_reverse[strtolower($row['filename'])]]['tmp_name'],$tmpdir.$row['crmid']);
			$files_to_rename[$row['crmid']] = Array(
				'fullname'=>$row['filename'],					
				'filename'=>$fname,					
				'extension'=>$fext,					
				'filedescription'=>$row['title'],				
			);
			unset($realfiles[$files_to_upload_reverse[strtolower($row['filename'])]]);						
		}
		$smarty = new VteSmarty();
		$smarty->assign('MODULE', $currentModule);
		$smarty->assign('FILES_TO_RENAME', $files_to_rename);
		$smarty->assign('FILEIDS', Zend_Json::encode(array_keys($files_to_rename)));
		$smarty->assign('UNIQUEID', $uniqueid);
		$smarty->assign('FOLDERID', $folderid);
		$return_arr['rename'] = true;
		$return_arr['rename_panel'] = $smarty->fetch("modules/{$currentModule}/FileRename.tpl");
	}
	//upload all files directly
	$obj = CRMEntity::getInstance('Myfiles');
	$folderid = $_REQUEST['folderid'];
	foreach ($realfiles as $index=>$file_arr){
		$_REQUEST['filename_hidden'] = $file_arr['name'];
		$_FILES = Array();
		$_FILES['filename'] = $file_arr;
		$_REQUEST['mode'] = '';
		$obj->column_fields['assigntype'] = 'U';
		$obj->column_fields['folderid'] = $folderid;
		$obj->column_fields['assigned_user_id'] = $current_user->id;
		// crmv@95157
		$obj->column_fields['filelocationtype'] = 'B'; 
		$obj->column_fields['backend_name'] = $SBU->defaultBackend;
		// crmv@95157e
		$obj->column_fields['filestatus'] = 'on';
		$obj->column_fields['filename_hidden'] = $file_arr['name'];
		try{
			$obj->save('Myfiles');
			$return_arr['success'] = true;
			$objects_created[] = $obj->id;
		}
		catch (Exception $e){
			$return_arr['message']=getTranslatedString('LBL_ERR_CREATE','Myfiles');
			$return_arr['success'] = false;;
		}
	}
}
elseif($finalize_upload == 1){
	$files = Zend_Json::decode($_REQUEST['fileids']);
	if (!empty($files)){
		foreach ($files as $file){
			$filepath = $tmpdir.$file;
			$action = $_REQUEST['action_'.$file];
			if ($action == 'jump'){
				@unlink($tmpdir.$file);
				continue;
			}
			$update_title = false;
			try{
				switch ($action){
					case 'replace':
						$title = $_REQUEST['descbackup_'.$file];
						$filename = $_REQUEST['filebackup_'.$file].".".$_REQUEST['ext_'.$file];
						break;
					case 'rename':
						$update_title = true;
						$title = $_REQUEST['desc_'.$file];
						$filename = $_REQUEST['file_'.$file].".".$_REQUEST['ext_'.$file];
						break;
				}
				$filename_backup = $filename;
				$filename = from_html(preg_replace('/\s+/', '_', $filename));
				$binFile = sanitizeUploadFileName($filename, $upload_badext);
				$filename = ltrim(basename(" ".$binFile)); //allowed filename like UTF-8 characters			
				$_REQUEST['filename_hidden'] = $filename_backup;
				$_FILES = Array();
				$sql = "select filetype from {$table_prefix}_myfiles where myfilesid = ?";
				$params = Array($file);
				$res = $adb->pquery($sql,$params);
				if ($res){
					$filetype = $adb->query_result_no_html($res,0,'filetype');
				}
				$file_details = array(
					'original_name' => $filename_backup,
		         	'type' => $filetype,
		         	'size' => filesize($filepath), 
		         	'tmp_name' => $filepath,
				);
				// crmv@95157
				$obj = CRMEntity::getInstance('Myfiles');
				$obj->retrieve_entity_info($file, 'Myfiles');
				$obj->id = $file;
				
				$success = $SBU->uploadFile($SBU->defaultBackend, 'Myfiles', $obj);

				if($success){
					//update file information
					if ($update_title){
						$sql = "UPDATE {$table_prefix}_myfiles SET filelocationtype = ?, filetype = ?, filesize = ?,title = ? WHERE myfilesid = ? ";
						$params = Array('B', $file_details['type'], $file_details['size'],$title, $file);
					}
					else{
						$sql = "UPDATE {$table_prefix}_myfiles SET filelocationtype = ?, filetype = ?, filesize = ? WHERE myfilesid = ? ";
						$params = Array('B', $file_details['type'], $file_details['size'], $file);						
					}
					$adb->pquery($sql,$params);
					$return_arr['success'] = true;
					$objects_created[] = $fileid;
					@unlink($tmpdir.$file);
				}
				else{
					throw new Exception('Error uploading file');
				}
				// crmv@95157e
			}
			catch (Exception $e){
				$return_arr['message']=getTranslatedString('LBL_ERR_CREATE','Myfiles');
				$return_arr['success'] = false;
			}			
		}
		if ($return_arr['success']){
			@rmdir($tmpdir);
		}
	}
}
echo Zend_Json::encode($return_arr);
exit;
?>