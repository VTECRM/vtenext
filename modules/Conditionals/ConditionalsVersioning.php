<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@155145 crmv@150751 */

class ConditionalsVersioning extends SDKExtendableUniqueClass {
	
	protected $temp_script_dir = 'cache/vtlib';
	
	function versionOperation($metaLogId='') {
		global $adb, $table_prefix, $current_user;
		$date_var = date('Y-m-d H:i:s');
		$pending_version = $this->getPendingVersion();
		if ($pending_version === false) {
			// new version
			$versionid = $adb->getUniqueID($table_prefix."_conditionals_versions");
			$version = $this->getNewVersionNumber();
			$adb->pquery("insert into {$table_prefix}_conditionals_versions(id,version,createdtime,createdby,modifiedtime,modifiedby,closed) values(?,?,?,?,?,?,?)",
			array($versionid,$version,$adb->formatDate($date_var, true),$current_user->id,$adb->formatDate($date_var, true),$current_user->id,0));
		} else {
			// append to pending version
			$versionid = $pending_version['id'];
			$adb->pquery("update {$table_prefix}_conditionals_versions set modifiedtime=?, modifiedby=? where id=?", array($adb->formatDate($date_var, true),$current_user->id,$versionid));
		}
		if (!empty($metaLogId)) $adb->pquery("insert into {$table_prefix}_conditionals_versions_rel(id,metalogid) values(?,?)",array($versionid,$metaLogId));
	}
	
	function getPendingVersion() {
		global $adb, $table_prefix;
		$result = $adb->query("select * from {$table_prefix}_conditionals_versions where closed = 0");
		if ($result && $adb->num_rows($result) > 0) {
			return $adb->fetchByAssoc($result);
		}
		return false;
	}
	
	function getCurrentVersionId() {
		global $adb, $table_prefix;
		$result = $adb->limitQuery("select id from {$table_prefix}_conditionals_versions where closed = 1 order by id desc",0,1);
		if ($result && $adb->num_rows($result) > 0) {
			return $adb->query_result($result,0,'id');
		}
		return '0';
	}
	
	function getCurrentVersionNumber() {
		global $adb, $table_prefix;
		$result = $adb->limitQuery("select version from {$table_prefix}_conditionals_versions where closed = 1 order by id desc",0,1);
		if ($result && $adb->num_rows($result) > 0) {
			return $adb->query_result($result,0,'version');
		}
		return '0';
	}
	
	function getNewVersionNumber() {
		$current_version = $this->getCurrentVersionNumber();
		if (empty($current_version)) {
			$version = '1.0';
		} else {
			$v = explode('.', $current_version);
			$v[count($v)-1]++;
			$version = implode('.', $v);
		}
		return $version;
	}
	
	function closeVersion() {
		global $adb, $table_prefix, $metaLogs;
		
		$pending_version = $this->getPendingVersion();
		
		//crmv@155375
		require_once('include/utils/UserInfoUtil.php');
		$userInfoUtils = UserInfoUtils::getInstance();
		$userInfoUtils->historicizeVersionTables($pending_version['id'], array(
			array('table'=>'tbl_s_conditionals'),
			array('table'=>'tbl_s_conditionals_rules'),
		));
		//crmv@155375e
		
		// info
		$result = $adb->pquery("select version, createdtime, createdby, modifiedtime, modifiedby from {$table_prefix}_conditionals_versions where id = ?", array($pending_version['id']));
		$info = array();
		if ($result && $adb->num_rows($result)) {
			while($row=$adb->fetchByAssoc($result,-1,false)) {
				$info = $row;
			}
		}
		
		// select delete operations
		$result = $adb->pquery("select log.operation, log.objectid, log.data, log.timestamp
				from {$table_prefix}_conditionals_versions v
				inner join {$table_prefix}_conditionals_versions_rel rel on rel.id = v.id
				inner join {$table_prefix}_meta_logs log on log.logid = rel.metalogid
				where v.id = ? and log.operation in (?,?)
				order by log.timestamp", array($pending_version['id'],$metaLogs::OPERATION_DELCONDITIONAL,$metaLogs::OPERATION_RENAMECONDITIONAL));
		$version_changes = array();
		if ($result && $adb->num_rows($result)) {
			while($row=$adb->fetchByAssoc($result,-1,false)) {
				if (!empty($row['data'])) $data = Zend_Json::decode($row['data']); else $data = array();
				$version_changes[] = array_merge(array('operation'=>$row['operation'],'timestamp'=>$row['timestamp']),$data);
			}
		}
		
		// get conditionals structure
		$structure = array();
		$conditionals_mapping = array();
		$result = $adb->query("select tbl_s_conditionals.*, {$table_prefix}_field.fieldname, {$table_prefix}_tab.name as \"module\"
			from tbl_s_conditionals
			inner join {$table_prefix}_field on tbl_s_conditionals.fieldid = {$table_prefix}_field.fieldid
			inner join {$table_prefix}_tab on {$table_prefix}_field.tabid = {$table_prefix}_tab.tabid");
		if ($result && $adb->num_rows($result)) {
			while ( $row = $adb->fetchByAssoc ($result,-1,false)) {
				$conditionalrule = $conditionals_mapping[$row['ruleid']] = $row['description'];
				$structure[$conditionalrule]['conditionals'][] = array(
					'fieldname'=>$row['fieldname'],
					'module'=>$row['module'],
					'active'=>$row['active'], 
					'read_perm'=>$row['read_perm'], 
					'write_perm'=>$row['write_perm'], 
					'mandatory'=>$row['mandatory'], 
					'role_grp_check'=>$row['role_grp_check'], 
				);
			}
		}
		$result = $adb->query("select * from tbl_s_conditionals_rules");
		if ($result && $adb->num_rows($result)) {
			while($row=$adb->fetchByAssoc($result,-1,false)) {
				$conditionalrule = $conditionals_mapping[$row['ruleid']];
				if (!empty($conditionalrule)) {
					$structure[$conditionalrule]['rules'][] = array(
						'chk_fieldname'=>$row['chk_fieldname'],
						'chk_criteria_id'=>$row['chk_criteria_id'],
						'chk_field_value'=>$row['chk_field_value'],					
						'module'=>$row['module'],					
					);
				}
			}
		}

		$json = array(
			'info'=>$info,
			'structure'=>$structure,
			'version_changes'=>$version_changes
		);

		$adb->updateClob("{$table_prefix}_conditionals_versions",'json',"id={$pending_version['id']}",Zend_Json::encode($json));
		
		// close version
		$adb->pquery("update {$table_prefix}_conditionals_versions set closed = 1 where id = ?", array($pending_version['id']));
		
		return true;
	}
	
	function isExportPermitted() {
		global $adb, $table_prefix;
		$result = $adb->query("select id from {$table_prefix}_conditionals_versions");
		if ($result && $adb->num_rows($result) > 0) {
			return true;
		}
		return false;
	}
	
	function checkExportVersion(&$err_string='') {
		if (!is_writable($this->temp_script_dir)) {
			$err_string = $this->temp_script_dir.' '.getTranslatedString('VTLIB_LBL_NOT_WRITEABLE','Settings');
			return false;
		}
		$pending_version = $this->getPendingVersion();
		if ($pending_version !== false) {
			$err_string = getTranslatedString('LBL_ERR_VERSION_PENDING_CHANGES','Settings');
			return false;
		}
		return true;
	}
	
	function exportVersion() {
		global $adb, $table_prefix;
		$result = $adb->pquery("select version, json from {$table_prefix}_conditionals_versions where closed = ? and json is not null and json <> '' order by id", array(1));
		if ($result && $adb->num_rows($result)) {
			$zipfilename = "$this->temp_script_dir/conditionals_".$this->getCurrentVersionNumber().'.zip';
			$zip = new Vtecrm_Zip($zipfilename);
			if (!file_exists($zipfilename)) {
				require_once('include/utils/UserInfoUtil.php');
				$userInfoUtils = UserInfoUtils::getInstance();
				$zip->addFile(false, 'manifest.xml', '', $userInfoUtils->getVersionManifest('conditionals',$this->getCurrentVersionNumber()));
				while($row=$adb->fetchByAssoc($result,-1,false)) {
					$zip->addFile(false, $row['version'].'.json', '', $row['json']);
				}
				$zip->save();
			}
			$zip->forceDownload($zipfilename);
			@unlink($zipfilename);
		}
	}
	
	function isImportPermitted() {
		return true;
	}
	
	function checkImportVersion() {
		if (!is_writable($this->temp_script_dir)) {
			return $this->temp_script_dir.' '.getTranslatedString('VTLIB_LBL_NOT_WRITEABLE','Settings');
		}
		$pending_version = $this->getPendingVersion();
		if ($pending_version !== false) {
			return getTranslatedString('LBL_ERR_VERSION_PENDING_CHANGES','Settings');
		}
	}
	
	function importVersion(&$err='') {
		global $upload_maxsize, $adb, $table_prefix, $current_user, $metaLogs, $php_max_execution_time;
		$date_var = date('Y-m-d H:i:s');
		
		$ext = pathinfo($_FILES['versionfile']['name'], PATHINFO_EXTENSION);
		if (!in_array($ext,array('zip'))) {
			$err = getTranslatedString('LBL_INVALID_FILE_EXTENSION', 'Settings');
			return false;
		}
		if(!is_uploaded_file($_FILES['versionfile']['tmp_name'])) {
			$err = getTranslatedString('LBL_FILE_UPLOAD_FAILED', 'Import');
			return false;
		}
		if ($_FILES['versionfile']['size'] > $upload_maxsize) {
			$err = getTranslatedString('LBL_IMPORT_ERROR_LARGE_FILE', 'Import').' $uploadMaxSize.'.getTranslatedString('LBL_IMPORT_CHANGE_UPLOAD_SIZE', 'Import');
			return false;
		}
		if (!is_writable($this->temp_script_dir)) {
			$err = $this->temp_script_dir.' '.getTranslatedString('VTLIB_LBL_NOT_WRITEABLE','Settings');
			return false;
		}
		
		$filename = $_FILES['versionfile']['tmp_name'];
		
		// unzip all in the table _conditionals_versions_import
		$adb->query("delete from {$table_prefix}_conditionals_versions_import");
		$unzip = new Vtecrm_Unzip($filename);
		$list = $unzip->getList();
		if (!empty($list)) {
			$sequence = 0;
			foreach($list as $file) {
				$ext = pathinfo($file['file_name'], PATHINFO_EXTENSION);
				if ($ext == 'json') {
					$version = str_replace('.json','',$file['file_name']);
					$json = $unzip->unzip($file['file_name']);
					$adb->pquery("insert into {$table_prefix}_conditionals_versions_import(version,sequence) values(?,?)", array($version,$sequence));
					$adb->updateClob("{$table_prefix}_conditionals_versions_import",'json',"version = '$version'",$json);
					$sequence++;
				} elseif ($ext == 'xml') {
					$manifeststring = $unzip->unzip($file['file_name']);
					$version_package_xml = @simplexml_load_string($manifeststring);
					if (!$version_package_xml || $version_package_xml->type != 'conditionals') {
						$err = getTranslatedString('VTLIB_LBL_INVALID_FILE', 'Settings');
						return false;
					}
				}
			}
		}
		if($unzip) $unzip->close();
		
		$cur_version = $this->getCurrentVersionNumber($tabid);
		$result = $adb->query("select version, json from {$table_prefix}_conditionals_versions_import order by sequence");
		if ($result && $adb->num_rows($result)) {
			$i = 1;
			while($row=$adb->fetchByAssoc($result,-1,false)) {
				
				// check if the imported version is major than the current version
				if(version_compare($row['version'], $cur_version, '>')) {
					
					// check if aready exists the version in _tab_versions
					$resultCheck = $adb->pquery("select id from {$table_prefix}_conditionals_versions where version = ?", array($row['version']));
					if ($resultCheck && $adb->num_rows($resultCheck) > 0) {
						$adb->pquery("update {$table_prefix}_conditionals_versions_import set status = ? where version = ?", array('SKIPPED',$row['version']));
					} else {
						$data = Zend_Json::decode($row['json']);
						
						if (!isset($data['info']['version']) || $row['version'] != $data['info']['version']) {
							// simple check if json is valid
							$adb->pquery("update {$table_prefix}_conditionals_versions_import set status = ? where version = ?", array('SKIPPED',$row['version']));
						} else {
							
							// foreach version apply "version_changes" and only for the last one apply the structure
							if (!empty($data['version_changes'])) {
								foreach($data['version_changes'] as $version_changes) {
									switch($version_changes['operation']) {
										case $metaLogs::OPERATION_DELCONDITIONAL:
											$resultTmp = $adb->pquery("select ruleid from tbl_s_conditionals where description = ?", array($version_changes['rulename']));
											($resultTmp && $adb->num_rows($resultTmp) > 0) ? $ruleid = $adb->query_result($resultTmp,0,'ruleid') : $ruleid = '';
											if (!empty($ruleid)) {
												$adb->pquery("DELETE FROM tbl_s_conditionals WHERE ruleid = ?", array($ruleid));
												$adb->pquery("DELETE FROM tbl_s_conditionals_rules WHERE ruleid = ?", array($ruleid));
												$metaLogs->log($metaLogs::OPERATION_DELCONDITIONAL, $ruleid, array('rulename'=>$version_changes['rulename']));
											}
										break;
										case $metaLogs::OPERATION_RENAMECONDITIONAL:
											$resultTmp = $adb->pquery("select ruleid from tbl_s_conditionals where description = ?", array($version_changes['rulename']));
											($resultTmp && $adb->num_rows($resultTmp) > 0) ? $ruleid = $adb->query_result($resultTmp,0,'ruleid') : $ruleid = '';
											if (!empty($ruleid)) {
												$adb->pquery("update tbl_s_conditionals set description = ? where ruleid = ?", array($version_changes['new_rulename'],$ruleid));
												$metaLogs->log($metaLogs::OPERATION_RENAMECONDITIONAL, $ruleid, array('rulename'=>$version_changes['new_rulename'],'new_rulename'=>$version_changes['new_rulename']));
											}
										break;
									}
								}
							}

							// apply last structure (_profile, _profile2tab, _profile2field, etc.)
							if ($i == $adb->num_rows($result)) {
								if (!empty($data['structure'])) {
									$bulkInserts = array();
									foreach($data['structure'] as $rulename => $structure) {
										$resultTmp = $adb->pquery("select ruleid from tbl_s_conditionals where description = ?", array($rulename));
										if ($resultTmp && $adb->num_rows($resultTmp) > 0) {
											$mode = 'edit';
											$ruleid = $adb->query_result($resultTmp,0,'ruleid');
										} else {
											$mode = 'create';
											$ruleid = $adb->getUniqueID('tbl_s_conditionals');
										}
										foreach($structure as $table => $info) {
											switch($table) {
												case 'conditionals':
													$table = 'tbl_s_conditionals';
													if ($mode == 'edit') $adb->query("delete from $table where ruleid = ".$ruleid);

													if (!empty($info)) {
														foreach($info as $info_) {
															$field_result = $adb->pquery("select fieldid from {$table_prefix}_field
																inner join {$table_prefix}_tab on {$table_prefix}_field.tabid = {$table_prefix}_tab.tabid
																where {$table_prefix}_tab.name = ? and fieldname = ?", array($info_['module'],$info_['fieldname']));
															if ($field_result && $adb->num_rows($field_result) > 0) {
																$fieldid = $adb->query_result($field_result,0,'fieldid');
																
																$max_result = $adb->query("select max(sequence)+1 as sequence from tbl_s_conditionals where fieldid = ".$fieldid);
																$sequence = (int)$adb->query_result($max_result,0,'sequence');
																
																$values = array(
																	'ruleid'=>$ruleid,
																	'fieldid'=>$fieldid,
																	'sequence'=>$sequence,
																	'active'=>$info_['active'],
																	'description'=>$rulename,
																	'read_perm'=>$info_['read_perm'],
																	'write_perm'=>$info_['write_perm'],
																	'mandatory'=>$info_['mandatory'],
																	'role_grp_check'=>$info_['role_grp_check'],
																);
																if (!isset($bulkInserts[$table]['columns'])) $bulkInserts[$table]['columns'] = array_keys($values);
																$bulkInserts[$table]['rows'][] = $values;
															}
														}
													}
													break;
												case 'rules':
													$table = 'tbl_s_conditionals_rules';
													if ($mode == 'edit') $adb->query("delete from $table where ruleid = ".$ruleid);
													
													if (!empty($info)) {
														foreach($info as $info_) {
															$values = array(
																'id'=>$adb->getUniqueID('tbl_s_conditionals_rules'),
																'ruleid'=>$ruleid,
																'chk_fieldname'=>$info_['chk_fieldname'],
																'chk_criteria_id'=>$info_['chk_criteria_id'],
																'chk_field_value'=>$info_['chk_field_value'],
																'module'=>$info_['module'],
															);
															if (!isset($bulkInserts[$table]['columns'])) $bulkInserts[$table]['columns'] = array_keys($values);
															$bulkInserts[$table]['rows'][] = $values;
														}
													}
													break;
											}
										}
										if ($metaLogs) {
											if ($mode == 'edit') $metaLogs->log($metaLogs::OPERATION_EDITCONDITIONAL, $ruleid, array('rulename'=>$rulename));
											elseif ($mode == 'create') $metaLogs->log($metaLogs::OPERATION_ADDCONDITIONAL, $ruleid, array('rulename'=>$rulename));
										}
									}
									if (!empty($bulkInserts)) {
										foreach($bulkInserts as $table => $bi) {
											$adb->bulkInsert($table, $bi['columns'], $bi['rows']);
										}
									}
								}
							}
							
							$versionid = $adb->getUniqueID($table_prefix."_conditionals_versions");
							$adb->pquery("insert into {$table_prefix}_conditionals_versions(id,version,createdtime,createdby,modifiedtime,modifiedby,closed) values(?,?,?,?,?,?,?)",
							array($versionid,$row['version'],$adb->formatDate($date_var, true),$current_user->id,$adb->formatDate($date_var, true),$current_user->id,1));
							$adb->updateClob("{$table_prefix}_conditionals_versions",'json',"id=$versionid",$row['json']);
							
							$adb->pquery("update {$table_prefix}_conditionals_versions_import set status = ? where version = ?", array('DONE',$row['version']));
						}
					}
				} else {
					$adb->pquery("update {$table_prefix}_conditionals_versions_import set status = ? where version = ?", array('SKIPPED',$row['version']));
				}
				$i++;
			}
		}
		
		// check if there is some DONE
		$result = $adb->pquery("select version from {$table_prefix}_conditionals_versions_import where status = ?", array('DONE'));
		if ($result && $adb->num_rows($result) == 0) {
			$err = getTranslatedString('LBL_NO_IMPORT_DONE','Settings');
			return false;
		}
		
		return true;
	}
}