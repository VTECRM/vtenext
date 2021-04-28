<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once("include/utils/EntityColorUtils.php"); // crmv@105538

/**
 * Description of ListViewController
 *
 * @author MAK
 */
class ListViewController extends SDKExtendableClass {
	/**
	 *
	 * @var QueryGenerator
	 */
	protected $queryGenerator;
	/**
	 *
	 * @var PearDatabase
	 */
	protected $db;
	protected $nameList;
	protected $typeList;
	protected $ownerNameList;
	protected $user;
	protected $picklistValueMap;
	protected $picklistRoleMap;
	protected $headerSortingEnabled;

	public function __construct($db, $user, $generator) {
		$this->queryGenerator = $generator;
		$this->db = $db;
		$this->user = $user;
		$this->nameList = array();
		$this->typeList = array();
		$this->ownerNameList = array();
		$this->picklistValueMap = array();
		$this->picklistRoleMap = array();
		$this->headerSortingEnabled = true;
	}
	
	public function isHeaderSortingEnabled() {
		return $this->headerSortingEnabled;
	}

	public function setHeaderSorting($enabled) {
		$this->headerSortingEnabled = $enabled;
	}

	public function setupAccessiblePicklistValueList($name) {
		$isRoleBased = vtws_isRoleBasedPicklist($name);
		$this->picklistRoleMap[$name] = $isRoleBased;
		if ($this->picklistRoleMap[$name]) {
			$this->picklistValueMap[$name] = getAssignedPicklistValues($name, $this->user->roleid, $this->db, '', '', false, false);	//crmv@27889
			//crmv@29102
			if($name == 'activitytype'){
				$this->picklistValueMap[$name]['Task'] = getTranslatedString('Task','Calendar');
			}
			//crmv@29102e
		}
	}
	
	//crmv@42931
	public function setQueryGenerator($generator) {
		$this->queryGenerator = $generator;
	}
	//crmv@42931e

	public function fetchNameList($field, $result) {
		$referenceFieldInfoList = $this->queryGenerator->getReferenceFieldInfoList();
		$fieldName = $field->getFieldName();
		$rowCount = $this->db->num_rows($result);

		$idList = array();
		for ($i = 0; $i < $rowCount; $i++) {
			$id = $this->db->query_result($result, $i, $field->getColumnName());
			if (!isset($this->nameList[$fieldName][$id])) {
				$idList[$id] = $id;
			}
		}
		//crmv@fix empty array
		$idList = array_values(array_filter(array_keys($idList)));
		//crmv@fix empty array end
		if(count($idList) == 0) {
			return;
		}
		$moduleList = $referenceFieldInfoList[$fieldName];
		foreach ($moduleList as $module) {
			$meta = $this->queryGenerator->getMeta($module);
			if ($meta->isModuleEntity()) {
				if($module == 'Users') {
					$nameList = getOwnerNameList($idList);
				} else {
					//TODO handle multiple module names overriding each other.
					$nameList = getEntityName($module, $idList);
				}
			} else {
				$nameList = vtws_getActorEntityName($module, $idList);
			}
			//crmv@fix empty array
			if(count($nameList) == 0) {
				continue;
			}
			$entityTypeList = array_intersect(array_keys($nameList), $idList);
			foreach ($entityTypeList as $id) {
				$this->typeList[$id] = $module;
			}
			if(empty($this->nameList[$fieldName])) {
				$this->nameList[$fieldName] = array();
			}
			foreach ($entityTypeList as $id) {
				$this->typeList[$id] = $module;
				$this->nameList[$fieldName][$id] = $nameList[$id];
			}
		}
	}

	/**This function generates the List view entries in a list view
	 * Param $focus - module object
	 * Param $result - resultset of a listview query
	 * Param $navigation_array - navigation values in an array
	 * Param $relatedlist - check for related list flag
	 * Param $returnset - list query parameters in url string
	 * Param $edit_action - Edit action value
	 * Param $del_action - delete action value
	 * Param $oCv - vte_customview object
	 * Returns an array type
	 */
	function getListViewEntries($focus,$module,$result,$navigationInfo,$skipActions=false,$listview_entries_other='',$otherActions=array()) {	//crmv@34627	//crmv@OPER6288 // crmv@167234
		VteSession::set('query_show', $result->sql);	//crmv@show_query
		// crmv@39110
		$userid = $this->user->id;
		require('user_privileges/requireUserPrivileges.php');
		// crmv@39110e
		global $listview_max_textlength,$theme,$default_charset,$current_user,$table_prefix,$showfullusername;
		if (!isset($otherActions['moreInformation'])) $otherActions['moreInformation'] = true; //crmv@161440

		$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024
		$ECU = EntityColorUtils::getInstance(); // crmv@105538
		$TU = ThemeUtils::getInstance($theme);
		
		$fields = $this->queryGenerator->getFields();
		$whereFields = $this->queryGenerator->getWhereFields();
		$meta = $this->queryGenerator->getMeta($this->queryGenerator->getModule());
		
		$tabid = getTabid($module); //crmv@7230

		//crmv@62929
		//$moduleFields = $meta->getModuleFields();
		$moduleFields = $this->queryGenerator->getModuleFields();
		//crmv@62929e
		$accessibleFieldList = array_keys($moduleFields);
		$listViewFields = array_intersect($fields, $accessibleFieldList);
		$referenceFieldList = $this->queryGenerator->getReferenceFieldList();
		foreach ($referenceFieldList as $fieldName) {
			if (in_array($fieldName, $listViewFields)) {
				$field = $moduleFields[$fieldName];
				$this->fetchNameList($field, $result);
			}
		}

		$db = PearDatabase::getInstance();
		$rowCount = $db->num_rows($result);
		
		$idList = array();
		$ownerFieldList = $this->queryGenerator->getOwnerFieldList();
		if (!empty($ownerFieldList)) {
			foreach ($ownerFieldList as $fieldName) {
				if (in_array($fieldName, $listViewFields)) {
					$field = $moduleFields[$fieldName];
					for ($i = 0; $i < $rowCount; $i++) {
						$id = $this->db->query_result($result, $i, $field->getColumnName());
						if (!isset($this->ownerNameList[$id])) $idList[] = $id;
					}
				}
			}
		}
		$referenceFieldInfoList = $this->queryGenerator->getReferenceFieldInfoList();
		if (!empty($referenceFieldInfoList)) {
			foreach ($referenceFieldInfoList as $fieldName => $moduleList) {
				if (count($moduleList) == 1 && in_array($moduleList[0],array('Users','Groups'))) {
					if (in_array($fieldName, $listViewFields)) {
						$field = $moduleFields[$fieldName];
						for ($i = 0; $i < $rowCount; $i++) {
							$id = $this->db->query_result($result, $i, $field->getColumnName());
							if (!isset($this->ownerNameList[$id])) $idList[] = $id;
						}
					}
				}
			}
		}
		if (!empty($idList)) {
			$groupNameList = array();
			$this->ownerNameList = getOwnerNameList($idList, $showfullusername, $groupNameList);
		}

		foreach ($listViewFields as $fieldName) {
			$field = $moduleFields[$fieldName];
			//crmv@9433
			$conditional_fieldid[] = $field->getFieldId();
			//crmv@9433 end
			if(!$is_admin && ($field->getFieldDataType() == 'picklist' ||
					$field->getFieldDataType() == 'multipicklist')) {
				$this->setupAccessiblePicklistValueList($fieldName);
			}
		}
	   	//crmv@9433
	    if (vtlib_isModuleActive('Conditionals') && !is_admin($current_user)){
	    	//crmv@36505
	    	$conditionals_obj = CRMEntity::getInstance('Conditionals');
	    	$conditional_fields_arr = $conditionals_obj->getConditionalFields($module);
	    	//crmv@36505 e
		}
		//crmv@9433 end
		//crmv@34627
		$secondary_fields = $this->queryGenerator->getSecondaryFields();
		if (!empty($secondary_fields)) {
			foreach($secondary_fields as $secondary_module => $sec_fields) {
				$meta_sec = $this->queryGenerator->getMeta($secondary_module);
				$secModuleFields[$secondary_module] = $meta_sec->getModuleFields();
				$i = 0;
				foreach($sec_fields as $sec_field) {
					if (isset($secModuleFields[$secondary_module][$sec_field])) {	//crmv@145724
						$sec_field_instance = $secModuleFields[$secondary_module][$sec_field];
						$listViewFields[] = $secondary_module.'::'.$sec_field.'::'.$i++;
					}
				}
			}
		}
		//crmv@34627e
		$data = array();
		for ($i = 0; $i < $rowCount; ++$i) {
			//Getting the recordId
			if($module != 'Users') {
				$baseTable = $meta->getEntityBaseTable();
				$moduleTableIndexList = $meta->getEntityTableIndexList();
				$baseTableIndex = $moduleTableIndexList[$baseTable];

				$recordId = $db->query_result($result,$i,$baseTableIndex);
				$ownerId = $db->query_result($result,$i,"smownerid");
			} else {
				$recordId = $db->query_result($result,$i,"id");
			}
			//crmv@17001 : Private Permissions
			if($module == 'Calendar') {
				$visibility = $db->query_result($result,$i,"visibility");
				$activityType = $this->db->query_result($result, $i, 'activitytype');	//crmv@21618
			}
			//crmv@17001e
			$row = array();
            //crmv@9433
			if (vtlib_isModuleActive('Conditionals') && !is_admin($current_user) && is_array($conditional_fields_arr)){
				foreach ($conditional_fields_arr as $arr_cond){
					$conditional_column_fields[$arr_cond['fieldname']] = $this->db->query_result($result, $i, $arr_cond['columnname']); // crmv@178440
				}
		    	//crmv@36505
		    	$conditionals_obj = CRMEntity::getInstance('Conditionals');
		    	$conditionals_obj->Initialize($module,$tabid,$conditional_column_fields);
		    	//crmv@36505 e
				$conditional_rules = $conditionals_obj->permissions;
			}
			//crmv@9433 end
			if ($module != 'Messages') {
				//crmv@18744
				//Added for Actions ie., edit and delete links in listview
				$actionLinkInfo = "";
				$editPermission = isPermitted($module,"EditView","");
				$deletePermission = isPermitted($module,"Delete",""); //crmv@179057
				if($editPermission == 'yes' && $module != 'Sms'){	//crmv@16703
				//crmv@fix Calendar
					$edit_link = $this->getListViewEditLink($module,$recordId,$activityType);
				//crmv@fix Calendar end
					if (isset($navigationInfo['start']) && $navigationInfo['start'] > 1) {
						$actionLinkInfo .= "<a href=\"$edit_link&start=".$navigationInfo['start']."\"><i class='vteicon' title='".getTranslatedString("LBL_EDIT",$module)."'>create</i></a> ";
					} else {
						$actionLinkInfo .= "<a href=\"$edit_link\"><i class='vteicon' title='".getTranslatedString("LBL_EDIT",$module)."'>create</i></a> ";
					}
				}
				if($deletePermission == 'yes'){ //crmv@179057
					$del_link = $this->getListViewDeleteLink($module,$recordId);
					if($actionLinkInfo != "" && $del_link != "")
						$actionLinkInfo .=  "&nbsp;";
					if($del_link != "")
						$actionLinkInfo .=	"<a href='javascript:confirmdelete(\"".addslashes(urlencode($del_link))."\",\"".$module."\")'><i class='vteicon' title='".getTranslatedString("LBL_DELETE",$module)."' >clear</i></a>";	//crmv@144123
				}
				// Record Change Notification
				//crmv@23685
				$change_indic = PerformancePrefs::getBoolean('LISTVIEW_RECORD_CHANGE_INDICATOR', true);
				if(method_exists($focus, 'isViewed') && $change_indic) {
				//crmv@23685e
					if(!$focus->isViewed($recordId)) {
						$actionLinkInfo .= "&nbsp;<img src='" . resourcever('important1.gif') . "' border=0>";
					}
				}
				// END
				//crmv@56233
				if ($editPermission == 'yes' && $deletePermission == 'yes' && $module == 'HelpDesk') { //crmv@179057
					$mailscanner_action = $this->db->query_result($result, $i, 'mailscanner_action');
					if (!empty($mailscanner_action)) {
						$actionLinkInfo .= "&nbsp;<a href=\"javascript:doNotImportAnymore('$module',$recordId,'ListView');\"><i class=\"vteicon\" title='".getTranslatedString("LBL_DO_NOT_IMPORT_ANYMORE",$module)."'>whatshot</i></a> ";
					}
				}
				//crmv@56233e
				if(!$skipActions && ($change_indic || $actionLinkInfo != "")) { //crmv@23685
					$row[] = $actionLinkInfo;
				}
				//crmv@18744e
			}
			foreach ($listViewFields as $fieldName) {
				//crmv@34627
				if(strpos($fieldName,'::') !== false) {
					$rawValue = $this->db->query_result($result, $i, strtolower($fieldName));
					$tmp = explode('::',$fieldName);
					$tmp_value = $listview_entries_other[$tmp[0]][$rawValue][$tmp[2]];	//N->1
					if (empty($tmp_value) && !empty($listview_entries_other[$tmp[0]])) {
						$tmp_value = getRelationFields($this->queryGenerator->getModule(), $tmp[0], $rawValue, array_keys($listview_entries_other[$tmp[0]]));
						if (!empty($fieldinfo['ids']) && is_array($fieldinfo['ids']) && count($fieldinfo['ids']) > 1) { // crmv@198985
							foreach($tmp_value['fields']['indirect'] as $fieldid=>$fieldinfo) {
								if (!empty($fieldinfo['ids']) && count($fieldinfo['ids']) > 1) {
									$tmp_value = '<a href="index.php?module='.$this->queryGenerator->getModule().'&action=DetailView&record='.$rawValue.'">'.getTranslatedString('LBL_MULTIPLE').'</a>';
								} elseif (!empty($fieldinfo['ids']) && is_array($fieldinfo['ids']) && count($fieldinfo['ids']) == 1) { // crmv@198985
									$tmp_value = $listview_entries_other[$tmp[0]][$fieldinfo['ids'][0]][$tmp[2]];
								} else {
									$tmp_value = '';
								}
								break;
							}
						} elseif (is_array($tmp_value['related']) && !empty($tmp_value['related'])) {	//N->N
							foreach($tmp_value['related'] as $rel_module=>$rel_ids) {
								if (!empty($rel_ids) && is_array($rel_ids) && count($rel_ids) > 1) { // crmv@198985
									$tmp_value = '<a href="index.php?module='.$this->queryGenerator->getModule().'&action=DetailView&record='.$rawValue.'">'.getTranslatedString('LBL_MULTIPLE').'</a>';
								} elseif (!empty($rel_ids) && is_array($rel_ids) && count($rel_ids) == 1) { // crmv@198985
									$tmp_value = $listview_entries_other[$tmp[0]][$rel_ids[0]][$tmp[2]];
								} else {
									$tmp_value = '';
								}
								break;
							}
						} else {
							$tmp_value = '';
						}
					}
					$value = '';
					if (!empty($tmp_value)) {
						$value = $tmp_value;
					}
					$row[] = $value;
					continue;
				}
				//crmv@34627e
				$field = $moduleFields[$fieldName];
				$uitype = $field->getUIType();
				$rawValue = $this->db->query_result($result, $i, $field->getColumnName());
				//crmv@fix Calendar
				if($module == 'Calendar' && ($fieldName=='status' || $fieldName=='taskstatus')){ //crmv@33466
					if($activityType == 'Task'){
						$fieldName='taskstatus';
					} else {
						$fieldName='eventstatus';
						$rawValue = $this->db->query_result($result, $i, $fieldName);
					}
				}
				//crmv@fix Calendar end
				if(stristr(html_entity_decode($rawValue), "<a href") === false &&
						$field->getUIType() != 8 && $field->getUIType() != 207 && !($field->getFieldDataType() == 'picklist' || $field->getFieldDataType() == 'multipicklist')){ //crmv@29102 // crmv@206419
					$value = textlength_check($rawValue);
				// crmv@126043
				}elseif($uitype == 8 || $uitype == 300){
					$value = $rawValue;
				}else{
					$value = html_entity_decode($rawValue,ENT_QUOTES);
				}
				// crmv@126043e
				//crmv@9433		crmv@sdk-18508
				if (vtlib_isModuleActive('Conditionals')){
					$conditional_permissions = null;
		            if(!is_admin($current_user) && $fieldName != "") {
	         			$conditional_permissions = $conditional_rules[$field->getFieldId()];
	            	}
				}
				$readonly = $field->getReadOnly();
            	if(vtlib_isModuleActive('Conditionals') && $conditional_permissions != null && $conditional_permissions['f2fp_visible'] == "0") {
            		$readonly = 100;
            	}
				$sdk_files = SDK::getViews($module,'list');
				if (!empty($sdk_files)) {
					foreach($sdk_files as $sdk_file) {
						$success = false;
						$readonly_old = $readonly;
						$fieldname = $fieldName;
						include($sdk_file['src']);
						SDK::checkReadonly($readonly_old,$readonly,$sdk_file['mode']);
						if ($success && $sdk_file['on_success'] == 'stop') {
							break;
						}
					}
				}
            	if ($readonly == 100) {
            		$value = "<font color='red'>".getTranslatedString('LBL_NOT_ACCESSIBLE')."</font>";
            	}
            	//crmv@9433 end	   crmv@sdk-18508e
            	// Private Permissions: crmv@17001 crmv@158871
            	elseif ($module == 'Calendar' && $focus->isFieldMasked($recordId, $fieldName, array('assigned_user_id' => $ownerId, 'visibility' => $visibility))) { // crmv@187823
            		if ($fieldName == 'subject')
            			$value = getTranslatedString('Private Event','Calendar');
            		else
            			$value = "<font color='red'>".getTranslatedString('LBL_NOT_ACCESSIBLE')."</font>";
            	}
            	//crmv@17001e crmv@158871e
				elseif($module == 'Documents' && $fieldName == 'filename') {
					$downloadtype = $db->query_result($result,$i,'filelocationtype');
					if($downloadtype == 'I' || $downloadtype == 'B') { // crmv@95157
						$ext =substr($value, strrpos($value, ".") + 1);
						$ext = strtolower($ext);
						if($value != ''){
							if($ext == 'bin' || $ext == 'exe' || $ext == 'rpm') {
								$fileicon = "<img src='" . resourcever('fExeBin.gif').
										"' hspace='3' align='absmiddle' border='0'>";
							} elseif($ext == 'jpg' || $ext == 'gif' || $ext == 'bmp') {
								$fileicon = "<img src='".resourcever('fbImageFile.gif').
										"' hspace='3' align='absmiddle' border='0'>";
							} elseif($ext == 'txt' || $ext == 'doc' || $ext == 'xls') {
								$fileicon = "<img src='".resourcever('fbTextFile.gif').
										"' hspace='3' align='absmiddle' border='0'>";
							} elseif($ext == 'zip' || $ext == 'gz' || $ext == 'rar') {
								$fileicon = "<img src='".resourcever('fbZipFile.gif').
										"' hspace='3' align='absmiddle'	border='0'>";
							} else {
								$fileicon = "<img src='".resourcever('fbUnknownFile.gif')
										. "' hspace='3' align='absmiddle' border='0'>";
							}
						}
					} elseif($downloadtype == 'E') {
						if(trim($value) != '' ) {
							$fileicon = "<img src='" . resourcever('fbLink.gif') .
									"' alt='".getTranslatedString('LBL_EXTERNAL_LNK',$module).
									"' title='".getTranslatedString('LBL_EXTERNAL_LNK',$module).
									"' hspace='3' align='absmiddle' border='0'>";
						} else {
							$value = '--';
							$fileicon = '';
						}
					} else {
						$value = ' --';
						$fileicon = '';
					}

					$fileName = $db->query_result($result,$i,'filename');
					$downloadType = $db->query_result($result,$i,'filelocationtype');
					$status = $db->query_result($result,$i,'filestatus');
					$fileIdQuery = "select attachmentsid from ".$table_prefix."_seattachmentsrel where crmid=?";
					$fileIdRes = $db->pquery($fileIdQuery,array($recordId));
					$fileId = $db->query_result($fileIdRes,0,'attachmentsid');
					if($fileName != '' && $status == 1) {
						if($downloadType == 'I' || $downloadtype == 'B') { // crmv@95157
							$value = html_entity_decode($value,ENT_QUOTES,$default_charset); //crmv@131416
							$value = "<a href='index.php?module=uploads&action=downloadfile&return_module=Documents&".
									"entityid=$recordId&fileid=$fileId' title='".
									getTranslatedString("LBL_DOWNLOAD_FILE",$module).
									"' onclick='javascript:dldCntIncrease($recordId);'>".$value.
									"</a>";
						} elseif($downloadType == 'E') {
							$value = "<a target='_blank' href='$fileName' onclick='javascript:".
									"dldCntIncrease($recordId);' title='".
									getTranslatedString("LBL_DOWNLOAD_FILE",$module)."'>".$value.
									"</a>";
						} else {
							$value = ' --';
						}
					}
					$value = $fileicon.$value;
				} elseif($module == 'Documents' && $fieldName == 'filesize') {
					$downloadType = $db->query_result($result,$i,'filelocationtype');
					if($downloadType == 'I' || $downloadType == 'B') { // crmv@95157
						$filesize = $value;
						if($filesize < 1024)
							$value=$filesize.' B';
						elseif($filesize > 1024 && $filesize < 1048576)
							$value=round($filesize/1024,2).' KB';
						else if($filesize > 1048576)
							$value=round($filesize/(1024*1024),2).' MB';
					} else {
						$value = ' --';
					}
				} elseif( $module == 'Documents' && $fieldName == 'filestatus') {
					if($value == 1)
						$value=getTranslatedString('yes',$module);
					elseif($value == 0)
						$value=getTranslatedString('no',$module);
					else
						$value='--';
				} elseif( $module == 'Documents' && $fieldName == 'filetype') {
					// crmv@95157
					$downloadType = $db->query_result($result,$i,'filelocationtype');
					if($downloadType != 'I' && $downloadType != 'B') {
						$value = '--';
					}
					// crmv@95157e
				//crmv@sdk-18509
				} elseif(SDK::isUitype($field->getUIType())) {
					$sdk_file = SDK::getUitypeFile('php','list',$field->getUIType());
					$sdk_value = $value;
					if ($sdk_file != '') {
						include($sdk_file);
					}
				//crmv@sdk-18509 e
				} elseif ($field->getUIType() == '27') {
					// crmv@95157
					if ($value == 'I') {
						$value = getTranslatedString('LBL_INTERNAL',$module);
					}elseif ($value == 'E') {
						$value = getTranslatedString('LBL_EXTERNAL',$module);
					}elseif ($value == 'B') {
						$SBU = StorageBackendUtils::getInstance();
						$backend = $db->query_result_no_html($result,$i,'backend_name');
						if (empty($backend)) {
							$backend = $SBU->getBackendForCrmid($module, $recordId);
						}
						$value = $SBU->getBackendLabel($backend);
					}else {
						$value = ' --';
					}
					// crmv@95157e
				}elseif ($field->getFieldDataType() == 'picklist') {
					//crmv@27889
					$value = correctEncoding($value);
					if ($value != '' && !$is_admin && $this->picklistRoleMap[$fieldName] &&
							!in_array($value, array_keys($this->picklistValueMap[$fieldName]))) {
					//crmv@27889e
						$value = "<font color='red'>".getTranslatedString('LBL_NOT_ACCESSIBLE',
								$module)."</font>";
					} else {
					//crmv@fix translate
						$value = textlength_check(getTranslatedString($value,$module));
					//crmv@fix translate end
					}
				//crmv@picklistmultilanguage
				}elseif ($field->getFieldDataType() == 'picklistmultilanguage') {
					$value = textlength_check(PickListMulti::getTranslatedPicklist($value,$fieldName));
				//crmv@picklistmultilanguage end
				}elseif($field->getFieldDataType() == 'date' ||
						$field->getFieldDataType() == 'datetime') {
					//crmv@fix date
					if ($field->getFieldDataType() == 'date'){
						$value = substr($value,0,10);
						//crmv@calendar fix
						if ($module == 'Calendar' && $fieldName == 'date_start'){
							$time_start = $this->db->query_result($result, $i, 'time_start');
							$value .= " $time_start";
						}
						//crmv@16703
						if ($module == 'Sms' && $fieldName == 'date_start'){
							$sql="select sms_flag from ".$table_prefix."_smsdetails where smsid=?";
							$tmp_res=$db->pquery($sql, array($recordId));
							$sms_flag=$db->query_result($tmp_res,0,"sms_flag");
							if($sms_flag != 'SENT') $value = '';
						}
						//crmv@16703e
					}
					// crmv@25610 crmv@50039
					$removetime = false;
					if ($module == 'Calendar' && $fieldName == 'due_date'){
						$value .= ' '.$this->db->query_result($result, $i, 'time_end');
						$removetime = true;
					}
					$value = adjustTimezone($value, 0, null, false);
					if ($module == 'Calendar' && $fieldName == 'date_start'){
						//remove seconds
						$value = substr($value, 0, 16);
					}
					if ($removetime) $value = substr($value, 0, 10);
					// crmv@25610e
					//crmv@fix date	end
					//crmv@2963m	//crmv@3083m
					if(($module == 'Messages' && $fieldName == 'mdate') || $module == 'MyNotes') {
						$value = $focus->getFriendlyDate($value);
					//crmv@2963me	//crmv@3083me
					} elseif($value != '' && $value != '0000-00-00') {
						$value = getDisplayDate($value);
					} elseif ($value == '0000-00-00') {
						$value = '';
					}
				} elseif(in_array($fieldName,array('time_start','time_end')) && $module == 'Calendar' && !empty($value)) {
					$value = adjustTimezone($value, 0, null, false); // crmv@50039
					// strip the date (if the date is different, there's a problem)
					if (strlen($value) > 5) {
						$value = substr($value, -8, 5);
					}
				} elseif($field->getUIType() == 71 || $field->getUIType() == 72) {
					if($value != '') {
						if(in_array($fieldName, array('unit_price', 'unit_cost'))) { // crmv@92112
							$currencyId = $InventoryUtils->getProductBaseCurrency($recordId,$module); // crmv@42024
							$cursym_convrate = getCurrencySymbolandCRate($currencyId);
							$value = "<font style='color:grey;'>".$cursym_convrate['symbol'].
								"</font> ". formatUserNumber(floatval($value)); // crmv@42024

						} else {
							//changes made to remove vte_currency symbol infront of each
							//vte_potential amount
							if ($value != 0) {
								$value = convertFromMasterCurrency($value, $user_info['conv_rate']); // crmv@92519
							}
							$value = formatUserNumber(floatval($value)); // crmv@83877
						}
					}
				// crmv@83877 crmv@92112
				} elseif($field->getUIType() == 7 || $field->getUIType() == 9) { 
					if ($value !== '') {
						$value = formatUserNumber(floatval($value), true);
					}
				// crmv@83877e crmv@92112e
				} elseif($field->getFieldDataType() == 'url') {
					// crmv@159970
					$url_scheme = parse_url($rawValue, PHP_URL_SCHEME);
					$scheme = empty($url_scheme) ? 'http://' : '';
					$value = '<a href="'.$scheme.$rawValue.'" target="_blank">'.$value.'</a>';
					// crmv@159970e
				//crmv@28670
				}elseif($fieldName == 'salutation') { //crmv@60730 avoid translating name
					$value = getTranslatedString($value,$module);
				//crmv@28670e
				} elseif ($field->getFieldDataType() == 'email') {
					if(VteSession::get('internal_mailer') == 1) {
						//check added for email link in user detailview
						$fieldId = $field->getFieldId();
						$value = "<a href=\"javascript:InternalMailer($recordId,$fieldId,".
						"'$fieldName','$module','record_id');\">$value</a>";
					}else {
						$value = '<a href="mailto:'.$rawValue.'">'.$value.'</a>';
					}
				} elseif($field->getFieldDataType() == 'boolean') {
					// crmv@67339 crmv@94838
					if ($field->getFieldName() == 'newsletter_unsubscrpt') {
						$focusnl = CRMEntity::getInstance('Newsletter');
						if (array_key_exists($module, $focusnl->email_fields)) {
							$mailTable = $focusnl->email_fields[$module]['tablename'];
							$mailField = $focusnl->email_fields[$module]['columnname'];
							$sql = "SELECT {$mailField} as email FROM {$mailTable} WHERE {$baseTableIndex} = ?";
							$nlres = $this->db->pquery($sql, array($recordId));
							if ($nlres && $this->db->num_rows($nlres) > 0) {
								$email_check = $this->db->query_result($nlres,0,'email');
								$value = intval($focusnl->receivingNewsletter($email_check));
							}
						}
					}
                    // crmv@67339e crmv@94838e
					if($value == 1) {
						$value = getTranslatedString('yes',$module);
					} elseif($value == 0) {
						$value = getTranslatedString('no',$module);
					} else {
						$value = '--';
					}
				} elseif($field->getUIType() == 98) {
					$value = '<a href="index.php?action=RoleDetailView&module=Settings&parenttab='.
						'Settings&roleid='.$value.'">'.textlength_check(getRoleName($value)).'</a>';
				} elseif($field->getFieldDataType() == 'multipicklist') {
					$value = correctEncoding($value);
					$valueArray = ($value != "") ? explode(' |##| ',$value) : array();
					$notaccess = '<font color="red">'.getTranslatedString('LBL_NOT_ACCESSIBLE',	$module)."</font>";
					$tmp = '';
					$tmpArray = array();
					foreach($valueArray as $index => $val) {
						if(!$listview_max_textlength ||
								!(strlen(preg_replace("/(<\/?)(\w+)([^>]*>)/i","",$tmp)) >
										$listview_max_textlength)) {
							if (!$is_admin && $this->picklistRoleMap[$fieldName] &&
									!in_array(trim($val), array_keys($this->picklistValueMap[$fieldName]))) {	//crmv@27889
								$tmpArray[] = $notaccess;
								$tmp .= ', '.$notaccess;
							} else {
								$tmpArray[] = getTranslatedString($val,$module);
								$tmp .= ', '.getTranslatedString($val,$module);
							}
						} else {
							$tmpArray[] = '...';
							$tmp .= '...';
						}
					}
					$value = implode(', ', $tmpArray);
				} elseif ($field->getFieldDataType() == 'skype') {
					$value = ($value != "") ? "<a href='skype:$value?call'>$value</a>" : "";
				//crmv@17471
				} elseif ($field->getFieldDataType() == 'phone' && get_use_asterisk($current_user->id) == 'true') {
					$value = "<a href='javascript:;' onclick='startCall(&quot;$value&quot;, ".
						"&quot;$recordId&quot;)'>$value</a>";
				//crmv@17471 end
				} elseif($field->getFieldDataType() == 'reference') {
					$referenceFieldInfoList = $this->queryGenerator->getReferenceFieldInfoList();
					$moduleList = $referenceFieldInfoList[$fieldName];
					if(count($moduleList) == 1) {
						$parentModule = $moduleList[0];
					} else {
						$parentModule = $this->typeList[$value];
					}
					if(!empty($value) && !empty($this->nameList[$fieldName]) && !empty($parentModule)) {
						$tmp_reference_value = $value;
						if ($parentModule == 'Users') {
							$value = textlength_check($this->ownerNameList[$value]);
							if (isset($groupNameList[$tmp_reference_value]) && $otherActions['moreInformation']) { //crmv@161440
								$value .= $this->getMoreInformationsDiv($recordId,$fieldName,implode('<br>',$groupNameList[$tmp_reference_value]));
							}
						} else {
							$parentMeta = $this->queryGenerator->getMeta($parentModule);
							$value = textlength_check($this->nameList[$fieldName][$value]);
							if ($parentModule == 'Groups') {
								if (isset($groupNameList[$tmp_reference_value]) && $otherActions['moreInformation']) { //crmv@161440
									$value .= $this->getMoreInformationsDiv($recordId,$fieldName,implode('<br>',$groupNameList[$tmp_reference_value]));
								}
							} elseif ($parentMeta->isModuleEntity()) {
								$value = "<a href='index.php?module=$parentModule&action=DetailView&".
									"record=$rawValue' title='$parentModule'>$value</a>";
							}
						}
					// crmv@132073
					} elseif(!empty($value)) {
						$parentModule = getSalesEntityType($value);
						$rawValue = $value;
						$name = getEntityName($parentModule,$value);

						$value = $name[$rawValue];
						$value = textlength_check($value);
					
						if ($parentModule != "Users") {
							$value = "<a href='index.php?module=$parentModule&action=DetailView&".
									"record=$rawValue' title='$parentModule'>$value</a>";
						}
					// crmv@132073e
					} else {
						$value = '--';
					}
				} elseif($field->getFieldDataType() == 'owner') {
					//crmv@OPER6288
					if (isset($otherActions['doNotEvaluate']) && in_array($fieldName,$otherActions['doNotEvaluate'])) {
						// do nothing
					} else	{
						$tmp_userid = $value;
						$value = textlength_check($this->ownerNameList[$value]);
						if (isset($groupNameList[$tmp_userid]) && $otherActions['moreInformation']) { //crmv@161440
							$value .= $this->getMoreInformationsDiv($recordId,$fieldName,implode('<br>',$groupNameList[$tmp_userid]));
						}
					}
					//crmv@OPER6288e
				} elseif ($field->getUIType() == 25) {
					//TODO clean request object reference.
					$contactId=$_REQUEST['record'];
					$emailId=$this->db->query_result($result,$i,"activityid");
					$result1 = $this->db->pquery("SELECT access_count FROM ".$table_prefix."_email_track WHERE ".
							"crmid=? AND mailid=?", array($contactId,$emailId));
					$value=$this->db->query_result($result1,0,"access_count");
					if(!$value) {
						$value = 0;
					}
				} elseif($field->getUIType() == 8){
					if(!empty($value)){
						$temp_val = html_entity_decode($value,ENT_QUOTES,$default_charset);
						$json = new Zend_Json();
						$value = vt_suppressHTMLTags(implode(',',$json->decode($temp_val)));
					}
				}
				//crmv@18338
				elseif($field->getUIType() == 1020){
					$temp_val = $value;
					$value=time_duration(abs($temp_val));
					if (strpos($fieldName,"remaining")!==false || strpos($fieldName,"_out_")!==false){
						if (strpos($fieldName,"remaining")!==false){
							if ($temp_val<=0)
								$color = "red";
							else
								$color = "green";
						}
						if (strpos($fieldName,"_out_")!==false){
							if ($temp_val>0)
								$color = "red";
							else
								$color = "green";
						}
						$value = "<font color=$color>$value</font>";
					}
				}
				//crmv@18338 end
				elseif ($fieldName == 'expectedroi' || $fieldName == 'actualroi' ||
						$fieldName == 'actualcost' || $fieldName == 'budgetcost' ||
						$fieldName == 'expectedrevenue') {
					$rate = $user_info['conv_rate'];
					$value = convertFromDollar($value,$rate);
				// crmv@42024
				} elseif (isInventoryModule($module) && (in_array($fieldName, array('hdnGrandTotal', 'hdnSubTotal', 'txtAdjustment', 'hdnDiscountAmount', 'hdnS_H_Amount')))) {
					$currencyInfo = $InventoryUtils->getInventoryCurrencyInfo($module, $recordId);
					$currencyId = $currencyInfo['currency_id'];
					$currencySymbol = $currencyInfo['currency_symbol'];
					$value = $currencySymbol." ".formatUserNumber(floatval($value));
				// crmv@42024e
				//crmv@21092	crmv@23734
				} elseif ($field->getFieldDataType() == 'text') {
					//crmv@2963m
					if ($module == 'Messages' && ($fieldName == 'description' || $fieldName == 'cleaned_body')) { // crmv@93095
						$layout = $focus->getLayoutSettings();
						if (empty($layout['list_descr_preview'])) {
							$value = '';
						} else {
							$value = $focus->getPreviewBody($rawValue);
						}
					} else {
					//crmv@2963me
						$temp_val = preg_replace("/(<\/?)(\w+)([^>]*>)/i","",$rawValue);
						$temp_val = trim(html_entity_decode($temp_val, ENT_QUOTES, $default_charset));
						$temp_val = nl2br($temp_val);
						if ($value != '' && strlen($temp_val) > $listview_max_textlength && $otherActions['moreInformation']) { //crmv@161440
							$value .= $this->getMoreInformationsDiv($recordId,$fieldName,$temp_val);	//crmv@59091	crmv@68641
						}
					}
				//crmv@21092e	crmv@23734e
				}
				if ( in_array($uitype,array(71,72,7,9,90)) ) {
					$value = "<span align='right'>$value</span>";
				}
				//crmv@16312
				$nameFields = $this->queryGenerator->getModuleNameFields($module);
				$nameFieldList = explode(',',$nameFields);
				if(in_array($fieldName, $nameFieldList) && $module != 'Messages') {	//crmv@2963m
					$parenttab = getParentTab(); // performance fix
					$value = "<a href='index.php?module=$module&parenttab=$parenttab&action=DetailView&record=".
					"$recordId' title='$module' data-module='$module' data-panelview='true' data-panelview-mode='detail'>$value</a>";
				} elseif($fieldName == $focus->list_link_field && $module != 'Messages') {	//crmv@2963m
					$parenttab = getParentTab(); // performance fix
					$value = "<a href='index.php?module=$module&parenttab=$parenttab&action=DetailView&record=".
					"$recordId' title='$module' data-module='$module' data-panelview='true' data-panelview-mode='detail'>$value</a>";
				}
				//crmv@16312 end
				// vtlib customization: For listview javascript triggers
				$value = "$value <span type='vtlib_metainfo' vtrecordid='{$recordId}' vtfieldname=".
					"'{$fieldName}' vtmodule='$module' style='display:none;'></span>";
				// END
				//crmv@2963m
				if ($module == 'Messages') {
					$row[$fieldName] = $value;
				} else {
					$row[] = $value;
				}
				//crmv@2963me
			}
			//crmv@2963m
			if ($module == 'Messages') {
				$row['thread'] = $db->query_result($result,$i,"thread");
			}
			//crmv@2963me

			//crmv@7230 crmv@10445 crmv@105538
			$clvValue = "";
			$blend = !(isset($otherActions['KanbanView']) && isset($otherActions['KanbanView']) === true);
			$clvColor = $ECU->getEntityColor($module, $recordId, $clvValue, $blend);
			if ($clvColor) {
				$row['clv_color'] = $clvColor;
				$row['clv_status'] = getTranslatedString($clvValue);
				// crmv@187406
				$row['clv_foreground'] = '';
				if ($TU->isDarkModePermitted($current_user)) {
					$clvForeground = $ECU->getForegroundColor($clvColor);
					$row['clv_foreground'] = $clvForeground;
				}
				// crmv@187406e
			}
			//crmv@7230e crmv@10445e crmv@105538e
			$data[$recordId] = $row;
		}

		//crmv@94282
		if ($module == 'Messages' && count($data) == $navigationInfo['list_max_entries_per_query']) {
			$this->override_params['has_next_page'] = true;
			array_pop($data);
		}
		//crmv@94282e

		return $data;
	}

	//crmv@94282 crmv@OPER8279
	function getListViewEntriesLight($focus,$module,$result,$navigationInfo,$skipActions=false,$listview_entries_other='') {
		global $adb, $table_prefix;

		$ret_arr = Array();

		switch ($module){
			case 'Messages':
				if ($result){
					$numrows = $adb->num_rows($result);
					if ($numrows == $navigationInfo['list_max_entries_per_query']){
						$limit_cnt = $numrows-1;
						$this->override_params['has_next_page'] = true;
					} else {
						$limit_cnt = $numrows;
					}
					$cnt = 0;
					while($row=$adb->fetchByAssoc($result,-1,false)){
						($focus->haveAttachments($row['messagesid'])) ? $row['has_attachments'] = 1 : $row['has_attachments'] = 0;
						$ret_arr[$row['messagesid']] = $this->getListViewEntryLight($focus,$module,$row);
						if (++$cnt == $limit_cnt){
							break;
						}
					}
				}
				break;
			default:
				$ret_arr = $this->getListViewEntries($focus,$module,$result,$navigationInfo,$skipActions,$listview_entries_other);
				break;	
		}

		return $ret_arr;
	}
	function getListViewEntryLight($focus,$module,$row) {
		$ret_arr = array();
		switch ($module){
			case 'Messages':
				$layout = $focus->getLayoutSettings();
				if (empty($layout['list_descr_preview'])) {
					$row['cleaned_body'] = '';
				} else {
					$row['cleaned_body'] = $focus->getPreviewBody($row['cleaned_body']);
				}
				// crmv@136430
				$mdate = $row['mdate'];
				if (empty($mdate) || $mdate == '0000-00-00 00:00:00') {
					$mdate = '';
				} else {
					$mdate = $focus->getFriendlyDate($mdate);
				}
				// crmv@136430e
				$ret_arr = Array(
					'subject'=>textlength_check(to_html($row['subject'])),
					'mdate'=>$mdate, // crmv@136430
					'mfrom'=>to_html($row['mfrom']),
					'mfrom_n'=>to_html($row['mfrom_n']),
					'mfrom_f'=>to_html($row['mfrom_f']),
					'mto'=>to_html($row['mto']),
					'mto_n'=>to_html($row['mto_n']),
					'mto_f'=>textlength_check(to_html($row['mto_f'])),
					'cleaned_body'=>$row['cleaned_body'],
					'seen'=>($row['seen']=='1')?'yes':'no',
					'answered'=>($row['answered']=='1')?'yes':'no',
					'forwarded'=>($row['forwarded']=='1')?'yes':'no',
					'flagged'=>($row['flagged']=='1')?'yes':'no',
					'has_attachments'=>($row['has_attachments']=='1')?'yes':'no',
					'thread'=>to_html($row['thread']),
					'xuid'=>$row['xuid'],
					'folder'=>$row['folder'],
					'account'=>$row['account'],
				);
				break;
		}
		return $ret_arr;
	}
	//crmv@94282e crmv@OPER8279e

	public function getListViewEditLink($module,$recordId, $activityType='') {
		if($module != 'Calendar') {
			$return_action = "index";
		} else {
			$return_action = 'ListView';
		}
		//Added to fix 4600
		$url = getBasic_Advance_SearchURL();
		$parent = getParentTab();
		//Appending view name while editing from ListView
		$link = "index.php?module=$module&action=EditView&record=$recordId&return_module=$module".
			"&return_action=$return_action&parenttab=$parent".$url."&return_viewname=".
			getLVS($module,"viewname");

		if($module == 'Calendar') {
			if($activityType == 'Task') {
				$link .= '&activity_mode=Task';
			} else {
				$link .= '&activity_mode=Events';
			}
		}
		return $link;
	}

	// modified to use a template for the non changing part of the url
	public function getListViewDeleteLink($module,$recordId) {
		static $linkTplCache = array();
		
		$requestRecord = intval($_REQUEST['record']);
		$requestModule = vtlib_purify($_REQUEST['module']);
		
		if (!isset($linkTplCache[$module.'_'.$requestModule])) {
			//crmv@16312
			$parenttab = getParentTab();
			$viewname = getLVS($module,'viewname');
			//Added to fix 4600
			$url = getBasic_Advance_SearchURL();
			if($module == "Calendar")
				$return_action = "ListView";
			else
				$return_action = "index";
			//This is added to avoid the del link in Product related list for the following modules
			$link = "index.php?module=$module&action=Delete&record={RECORDID}".
				"&return_module=$module&return_action=$return_action".
				"&parenttab=$parenttab&return_viewname=".$viewname.$url;
			//crmv@16312 end
			// vtlib customization: override default delete link for custom modules
			
			$requestAction = vtlib_purify($_REQUEST['action']);
			//crmv@39135
			if ($requestAction == $requestModule.'Ajax') {
				$requestAction = vtlib_purify($_REQUEST['file']);
			}
			//crmv@39135e
			$parenttab = vtlib_purify($_REQUEST['parenttab']);
			$isCustomModule = vtlib_isCustomModule($requestModule);

			if($isCustomModule && !in_array($requestAction, Array('index','ListView'))) {
				$link = "index.php?module=$requestModule&action=updateRelations&parentid={REQRECORDID}";
				$link .= "&destination_module=$module&idlist=$entity_id&mode=delete&parenttab=$parenttab";
			}
			// END
			$linkTplCache[$module.'_'.$requestModule] = $link;
		}
	
		$link = $linkTplCache[$module.'_'.$requestModule];
		$link = str_replace(array("{RECORDID}", "{REQRECORDID}"), array($recordId, $requestRecord), $link);
		
		return $link;
	}

	public function getListViewHeader($focus, $module,$sort_qry='',$sorder='',$orderBy='',
			$skipActions=false,$folderid='') { //crmv@83034
		global $log;//crmv@203484 removed global singlepane
        global $theme;

        //crmv@203484
        $VTEP = VTEProperties::getInstance();
        $singlepane_view = $VTEP->getProperty('layout.singlepane_view');
        //crmv@203484e

		$arrow='';
		$qry = getURLstring($focus);
		$theme_path="themes/".$theme."/";
		$image_path=$theme_path."images/";
		$header = Array();

		//Get the vte_tabid of the module
		$tabid = getTabid($module);
		$tabname = getParentTab();
		global $current_user;

		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		$fields = $this->queryGenerator->getFields();
		$whereFields = $this->queryGenerator->getWhereFields();
		$meta = $this->queryGenerator->getMeta($this->queryGenerator->getModule());

		//crmv@62929
		//$moduleFields = $meta->getModuleFields();
		$moduleFields = $this->queryGenerator->getModuleFields();
		//crmv@62929 e
		$accessibleFieldList = array_keys($moduleFields);
		$listViewFields = array_intersect($fields, $accessibleFieldList);

		//crmv@18744 crmv@23685
		//Added for Action - edit and delete link header in listview
		$change_indic = PerformancePrefs::getBoolean('LISTVIEW_RECORD_CHANGE_INDICATOR', true);
		if(!$skipActions && ($change_indic || isPermitted($module,"EditView","") == 'yes' || isPermitted($module,"Delete","") == 'yes')) {
			$header[] = getTranslatedString("LBL_ACTION", $module);
		}
		//crmv@18744e crmv@23685e

		//Added on 14-12-2005 to avoid if and else check for every list
		//vte_field for arrow image and change order
		$change_sorder = array('ASC'=>'DESC','DESC'=>'ASC');
		$arrow_gif = array('ASC'=>'arrow_down.gif','DESC'=>'arrow_up.gif');
		$arrow_cls = array('ASC'=>'down','DESC'=>'up');
		(!empty($folderid)) ? $folder_string = '&folderid='.$folderid : $folder_string = '';	//crmv@83034
		foreach($listViewFields as $fieldName) {
			$field = $moduleFields[$fieldName];
			if(in_array($field->getColumnName(),$focus->sortby_fields)) {
				if($orderBy == $field->getColumnName()) {
					$temp_sorder = $change_sorder[$sorder];
					$arrow = "&nbsp;<span class=\"vteicon vtesorticon md-text\">arrow_drop_{$arrow_cls[$sorder]}</span>";
				} else {
					$temp_sorder = 'ASC';
				}
				$label = getTranslatedString($field->getFieldLabelKey(), $module);
				//added to display vte_currency symbol in listview header
				if($label =='Amount') {
					$label .=' ('.getTranslatedString('LBL_IN', $module).' '.
							$user_info['currency_symbol'].')';
				}
				if($module == 'Users' && $fieldName == 'User Name') {
					$name = "<a href='javascript:;' onClick='getListViewEntries_js(\"".$module.
						"\",\"parenttab=".$tabname."&order_by=".$field->getColumnName()."&sorder=".
						$temp_sorder.$sort_qry."\");' class='listFormHeaderLinks'>".
						getTranslatedString('LBL_LIST_USER_NAME_ROLE',$module)."".$arrow."</a>";
				} else {
					if($this->isHeaderSortingEnabled()) {
						//crmv@16312
						$name = "<a href='javascript:;' onClick='getListViewEntries_js(\"".$module.
							"\",\"parenttab=".$tabname."&foldername=Default{$folder_string}&order_by=".$field->getColumnName()."&start=".	//crmv@83034
							getLVS($module,"start")."&sorder=".$temp_sorder."".
						$sort_qry."\");' class='listFormHeaderLinks'>".$label."".$arrow."</a>";
						//crmv@16312 end
					} else {
						$name = $label;
					}
				}
				$arrow = '';
			} else {
				$name = getTranslatedString($field->getFieldLabelKey(), $module);
			}
			//added to display vte_currency symbol in related listview header
			if($name =='Amount') {
				$name .=' ('.getTranslatedString('LBL_IN').' '.$user_info['currency_symbol'].')';
			}

			$header[]=$name;
		}

		//crmv@34627
		$secondary_fields = $this->queryGenerator->getSecondaryFields();
		if (!empty($secondary_fields)) {
			foreach($secondary_fields as $module => $sec_fields) {
				$meta = $this->queryGenerator->getMeta($module);
				$secModuleFields = $meta->getModuleFields();
				foreach($sec_fields as $sec_field) {
					if (isset($secModuleFields[$sec_field])) {	//crmv@145724
						$sec_field_instance = $secModuleFields[$sec_field];
						$header[] = getTranslatedString($sec_field_instance->getFieldLabelKey(), $module);
					}
				}
			}
		}
		//crmv@34627e

		return $header;
	}
	
	public function getBasicSearchFieldInfoList() {
		$fields = $this->queryGenerator->getFields();
		$whereFields = $this->queryGenerator->getWhereFields();
		$meta = $this->queryGenerator->getMeta($this->queryGenerator->getModule());

		//crmv@62929
		//$moduleFields = $meta->getModuleFields();
		$moduleFields = $this->queryGenerator->getModuleFields();
		//crmv@62929e
		$accessibleFieldList = array_keys($moduleFields);
		$listViewFields = array_intersect($fields, $accessibleFieldList);
		$basicSearchFieldInfoList = array();
		foreach ($listViewFields as $fieldName) {
			$field = $moduleFields[$fieldName];
			$basicSearchFieldInfoList[$fieldName] = getTranslatedString($field->getFieldLabelKey(),
					$this->queryGenerator->getModule());
		}
		return $basicSearchFieldInfoList;
	}
	
	//crmv@17997
	public function getAdvancedSearchOptionString($old_mode=false) {
		$module = $this->queryGenerator->getModule();
		//crmv@48693
		$focus = CRMEntity::getInstance($module);
		if(method_exists($focus, 'getAdvancedSearchOptionString')) {
			return $focus->getAdvancedSearchOptionString($old_mode,$this,$this->queryGenerator);
		}
		//crmv@48693e
		$meta = $this->queryGenerator->getMeta($module);
		//crmv@62929
		//$moduleFields = $meta->getModuleFields();
		$moduleFields = $this->queryGenerator->getModuleFields();
		//crmv@62929e
		$i =0;
		foreach ($moduleFields as $fieldName=>$field) {
			//crmv@32955
			if(!in_array($field->getPresence(), array('0','2'))){
				continue;
			}
			//crmv@32955e
			if($field->getFieldDataType() == 'reference' || $field->getFieldDataType() == 'owner') {
				$typeOfData = 'V';
			} else if($field->getFieldDataType() == 'boolean') {
				$typeOfData = 'C';
			} else {
				$typeOfData = $field->getTypeOfData();
				$typeOfData = explode("~",$typeOfData);
				$typeOfData = $typeOfData[0];
			}
			$label = getTranslatedString($field->getFieldLabelKey(), $module);
			if(empty($label)) {
				$label = $field->getFieldLabelKey();
			}
			if($label == "Start Date & Time") {
				$fieldlabel = "Start Date";
			}
			$selected = '';
			if($i++ == 0) {
				$selected = "selected";
			}
			//crmv@16312
			// place option in array for sorting later
			$blockName = getTranslatedString($field->getBlockName(), $module);
			if ($old_mode){
				$tableName = $field->getTableName();
				//crmv@31979
				$columnName = $field->getColumnName();
				$OPTION_SET[$blockName][$label] = "<option value='$tableName.$columnName::::$typeOfData:{$field->getUIType()}' $selected>$label</option>";	//crmv@128159 crmv@159559
				//crmv@31979e
			}
			else
				$OPTION_SET[$blockName][$label] = "<option value='$fieldName::::$typeOfData:{$field->getUIType()}' $selected>$label</option>";	//crmv@128159 crmv@159559
		}
		if (!is_array($OPTION_SET)) return '';	//crmv@18917

	   	// sort array on block label
	    ksort($OPTION_SET, SORT_STRING);

		foreach ($OPTION_SET as $key=>$value) {
	  		$shtml .= "<optgroup label='$key' class='select' style='border:none'>";
	   		// sort array on field labels
	   		ksort($value, SORT_STRING);
	  		$shtml .= implode('',$value);
	  	}

	    return $shtml;
	    //crmv@16312 end
	}
	//crmv@17997 end

	//crmv@3084m
	public function getGridSearch($focus,$module,$sort_qry='',$sorder='',$orderBy='',$skipActions=false,&$grid_search_js_array) {
		$header = $grid_search_js_array = array();

		//Get the vte_tabid of the module
		$tabid = getTabid($module);
		$tabname = getParentTab();
		global $theme, $current_user, $default_charset;

		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		$fields = $this->queryGenerator->getFields();
		$whereFields = $this->queryGenerator->getWhereFields();
		$meta = $this->queryGenerator->getMeta($this->queryGenerator->getModule());

		//crmv@62929
		//$moduleFields = $meta->getModuleFields();
		$moduleFields = $this->queryGenerator->getModuleFields();
		//crmv@62929e
		$accessibleFieldList = array_keys($moduleFields);
		$listViewFields = array_intersect($fields, $accessibleFieldList);

		$change_indic = PerformancePrefs::getBoolean('LISTVIEW_RECORD_CHANGE_INDICATOR', true);
		if(!$skipActions && ($change_indic || isPermitted($module,"EditView","") == 'yes' || isPermitted($module,"Delete","") == 'yes')) {
			$header[] = '';
		}

		// crmv@99908
		//TODO 33
		$enable_uitypes = array(15,16,1015,/*54,*/56,300);
		$picklist_uitypes = array(
			300 => '',
			15 => '',
			16 => '',
			1015 => '',
			//54 => '',
			56 => array(
				array('value'=>'yes','label'=>getTranslatedString('yes',$module)),
				array('value'=>'no','label'=>getTranslatedString('no',$module)),
			),
			27 => array(
				array('value'=>'I','label'=>getTranslatedString('LBL_INTERNAL',$module)),
				array('value'=>'E','label'=>getTranslatedString('LBL_EXTERNAL',$module)),
			),
			33 => '',
		);
		//crmv@99908e
		
		if(!empty($_REQUEST['GridSearchCnt'])) {
			$grid_search_fields = array();
			for($i=0;$i<$_REQUEST['GridSearchCnt'];$i++) {
				$value = urldecode($_REQUEST['GridSrch_value'.$i]);
				$value = function_exists('iconv') ? @iconv("UTF-8",$default_charset,$value) : $searchValue; // crmv@167702
				$grid_search_fields[$_REQUEST['GridFields'.$i]] = $value;
			}
		}
		
		foreach($listViewFields as $fieldName) {
			$value = '';
			if (isset($grid_search_fields[$fieldName])) {
				$value = $grid_search_fields[$fieldName];
			}
			$field = $moduleFields[$fieldName];
			if ($field->isReadOnly()) {
				$divclass = 'dvtCellInfoOff';
			} else {
				$divclass = 'dvtCellInfo';
			}
			$uitype = $field->getUIType();
			if (isset($picklist_uitypes[$uitype])) {
				$select_value = array_filter(explode('|##|',$value));
				$value = array();
				if (!empty($picklist_uitypes[$uitype])) {
					$tmp_value = $picklist_uitypes[$uitype];
				} else {
					$tmp_value = $field->getPickListOptions();
				}
				if ($fieldName == 'activitytype' && $module == 'Calendar') {
					$tmp_value[] = array('value'=>'Task','label'=>getTranslatedString('Task',$module));
				}
				//crmv@91678
				if ($fieldName == 'taskstatus' && $module == 'Calendar') {
					$tocheck = array();
					foreach($tmp_value as $check_value){
						$tocheck[]=$check_value['value'];
					}
					$tmp = getPickListValues('eventstatus',$current_user->roleid);
					for($i=0;$i<sizeof($tmp);++$i){
						$picklistValue = decode_html($tmp[$i]);
						if(!in_array($picklistValue,$tocheck)){
							$picklistLabel = getTranslatedString($picklistValue,$module);
							$tmp_value[] = array('value'=>$picklistValue,'label'=>$picklistLabel);
						}
					}
					unset($tocheck);
				}
				//crmv@91678e
				foreach($tmp_value as $tmp) {
					$selected = '';
					if (in_array($tmp['value'],$select_value)) {
						$selected = 'selected';
					}
					$value[] = array($tmp['label'],$tmp['value'],$selected);
				}
			}
			if (isset($picklist_uitypes[$uitype])) {
				$uitype = 33;
			} elseif (!in_array($uitype,$enable_uitypes)) {
				$uitype = 1;
			}
			$header[] = array(
				'module'=>$module,
				'divclass'=>$divclass,
				'uitype'=>$uitype,
				'mandatory'=>$field->isMandatory(),
				'label'=>$field->getFieldLabelKey(),
				'name'=>'gs_'.$field->getFieldName(),
				'value'=>$value,
			);
			$grid_search_js_array[] = "'{$field->getFieldName()}'";
		}

		//TODO
		$secondary_fields = $this->queryGenerator->getSecondaryFields();
		if (!empty($secondary_fields)) {
			foreach($secondary_fields as $module => $sec_fields) {
				$meta = $this->queryGenerator->getMeta($module);
				$secModuleFields = $meta->getModuleFields();
				foreach($sec_fields as $sec_field) {
					if (isset($secModuleFields[$sec_field])) { //crmv@145724
						$sec_field_instance = $secModuleFields[$sec_field];
						$header[] = Array(); //crmv@55007
					}
				}
			}
		}
		
		$grid_search_js_array = implode(',',$grid_search_js_array);
		return $header;
	}
	//crmv@3084me
	//crmv@47905
	function getListViewEntriesOther($list_result_sql,$queryGenerator,$customView,$viewid,$navigation_array) {
		global $currentModule, $current_user, $list_max_entries_per_page, $adb, $table_prefix;
		$focus = CRMEntity::getInstance($currentModule);
		$listview_entries_other = array();
		$module_width_fields = $queryGenerator->getModuleWidthFields();
		$secondary_fields = $queryGenerator->getSecondaryFields();
		if (!empty($secondary_fields) && !empty($module_width_fields)) {
			$reportid = $customView->getReportId($viewid);
			$reportmodules = $customView->getReportModules();
			foreach($module_width_fields as $module_width_field) {
				if ($reportmodules !== false && isset($secondary_fields[$module_width_field]) && in_array($module_width_field,$reportmodules)) {
					$focus_other = CRMEntity::getInstance($module_width_field);
					$instance_other = Vtecrm_Module::getInstance($module_width_field);
					$queryGeneratorOther = QueryGenerator::getInstance($module_width_field, $current_user);
					$queryGeneratorOther->setFields(array_merge(array('id'),$secondary_fields[$module_width_field]));
					$queryGeneratorOther->setReportFilter($reportid,$module_width_field,$instance_other->id);
					$list_query_other = $queryGeneratorOther->getQuery();
					$list_result_other = $adb->query($list_query_other);
					if ($adb->num_rows($list_result_other) > $list_max_entries_per_page) {
						$relation_manager = RelationManager::getInstance();
						$relations = $relation_manager->getRelations($currentModule,ModuleRelation::$TYPE_ALL,$module_width_field);
						if (!empty($relations)) {
							$relation = $relations[0];
							$relation_type = $relation->getType();
							if ($relation_type == 2) {
								$relation_type = '1TON';
							} elseif ($relation_type == 4) {
								$relation_type = 'NTO1';
							} elseif ($relation_type == 8) {
								$relation_type = 'NTON';
							}
						}
		
						if ($relation_type == '1TON') {		//es. Contacts, Accounts
							$related_fields2 = getRelationFields($module_width_field,$currentModule,null,null,$reportid);
							$field_join = '';
							if (!empty($related_fields2['fields']['direct'])){
								foreach ($related_fields2['fields']['direct'] as $fieldid => $rel_info) {
									$field_join = "{$rel_info['tablename']}.{$rel_info['columnname']}";
									$field_join_short = "{$rel_info['columnname']}";
									break;
								}
							}
							elseif (!empty($related_fields2['fields']['indirect']) && $field_join == ''){
								foreach ($related_fields2['fields']['indirect'] as $fieldid => $rel_info) {
									//$field_join = "{$table_prefix}_crmentity.crmid";
									//$field_join_short = "crmid";
									$field_join = "{$rel_info['tablename']}.{$rel_info['columnname']}";
									$field_join_short = "{$rel_info['columnname']}";
									break;
								}
							}
							elseif (!empty($related_fields2['related'])  && $field_join == ''){
								foreach ($related_fields2['related'] as $rel_module) {
									$field_join = "{$table_prefix}_crmentity.crmid";
									$field_join_short = "crmid";
								}
							}
							if ($field_join != ''){
								$query = preg_replace("/[\n\r\t]+/"," ",$list_result_sql); //crmv@20049
								$query = "SELECT {$table_prefix}_crmentity.crmid ".substr($query, stripos($query,' FROM '),strlen($query));
								$query_restrict = "select * from ({$query}) t group by t.crmid";
								$list_query_other.=" and {$field_join} in ($query_restrict)";
								$list_result_other = $adb->query($list_query_other);
							}
						} elseif ($relation_type == 'NTO1') {	//es. Accounts, Contacts
							$related_fields = getRelationFields($currentModule,$module_width_field,null,null,$reportid);
							$field_join = '';
							if (!empty($related_fields['fields']['direct'])){
								foreach ($related_fields['fields']['direct'] as $fieldid => $rel_info) {
									$field_join = "{$rel_info['tablename']}.{$rel_info['columnname']}";
									$field_join_short = "{$rel_info['columnname']}";
									break;
								}
							}
							if (!empty($related_fields['fields']['indirect']) && $field_join == ''){
								foreach ($related_fields['fields']['indirect'] as $fieldid => $rel_info) {
									$field_join = "{$table_prefix}_crmentity.crmid";
									$field_join_short = "crmid";
									break;
								}
							}
							if (!empty($related_fields['related'])  && $field_join == '') {
								foreach ($related_fields['related'] as $rel_module) {
									$field_join = "{$table_prefix}_crmentity.crmid";
									$field_join_short = "crmid";
								}
							}
							if ($field_join != '') {
								$query_restrict = "select t.{$field_join_short} from ({$list_result_sql}) t group by t.{$field_join_short}";
								$list_query_other.=" and {$table_prefix}_crmentity.crmid in ($query_restrict)";
								$list_result_other = $adb->query($list_query_other);
							}
						} elseif ($relation_type == 'NTON') {	//es. Accounts, Products
							$reltable = $relation->relationinfo['reltab'];
							$field_join = 'related';
							$list_res_mod = preg_replace("/[\n\r\t]+/"," ",$list_result_sql);
							
							// crmv@63349
							$tmodreltables = TmpUserModRelTables::getInstance();
							$tabname = $focus->setupTemporaryRelTable($currentModule,$module_width_field,'',$relation->relationinfo);
							$replace = "{$table_prefix}_crmentity.crmid";
							$list_res_mod = "SELECT $replace ".substr($list_res_mod, stripos($list_res_mod,' FROM '),strlen($list_res_mod));
							$reltable_tmp = $this->createtempresultq($list_res_mod,$viewid);
							
							if ($tabname == $tmodreltables->tmpTable) {
								$viewid = intval($viewid);
								$join = $tmodreltables->getJoinCondition($currentModule, $module_width_field, $current_user->id, 0, '', '', 'c');
							} else {
								$join = '';
							}
							if (PerformancePrefs::getBoolean('USE_TEMP_TABLES')) {
								$query_restrict = "select {$relation->relationinfo['relidx2']} 
									from $tabname c
									inner join {$reltable_tmp} t on t.crmid = c.crmid";
							} else {
								$query_restrict = "select {$relation->relationinfo['relidx2']} 
									from $tabname c
									INNER join {$reltable_tmp} t ON t.userid = {$current_user->id} AND t.viewid = $viewid AND t.id = c.crmid";
								if ($join) $query_restrict .= " WHERE $join";
							}

							$list_query_other .= " and {$table_prefix}_crmentity.crmid in ($query_restrict)";
							// crmv@63349e
							
							$list_result_other = $adb->query($list_query_other);
						}
					}
					$controller_other = ListViewController::getInstance($adb, $current_user, $queryGeneratorOther);
					$fields = $controller_other->queryGenerator->getFields();
					$listview_entries_other[$module_width_field] = $controller_other->getListViewEntries($focus_other,$module_width_field,$list_result_other,$navigation_array,true);
				}
			}
		}
		return $listview_entries_other;
	}

	// crmv@63349
	function createtempresultq($query,$viewid) {
		if (PerformancePrefs::getBoolean('USE_TEMP_TABLES')) {
			return $this->createtempresultq_tmp($query, $viewid);
		} else {
			return $this->createtempresultq_notmp($query, $viewid);
		}
	}
	
	function createtempresultq_notmp($query,$viewid) {
		global $adb, $table_prefix;
		global $current_user;
		
		$viewid = intval($viewid);
		$reltable_tmp = $table_prefix.'_tmp_rst';
		$sqlTableName = $adb->datadict->changeTableName($reltable_tmp);

		// clean
		$adb->pquery("DELETE FROM $sqlTableName WHERE userid = ? AND viewid = ?", array($current_user->id, $viewid));
		
		$query = preg_replace('/^SELECT\s+(.*?)\s+FROM/i', "SELECT {$current_user->id} as userid, $viewid as viewid, \\1 FROM", $query);
		$query = "INSERT INTO $sqlTableName (userid, viewid, id) ".$query;
		$result = $adb->query($query);

		return $reltable_tmp;
	}
	// crmv@63349e
	
	function createtempresultq_tmp($query,$viewid){ // crmv@63349
		global $table_prefix;
		global $current_user;
		$db = PearDatabase::getInstance();
		$tableName =  'vt_tmp_u'.$current_user->id.'rst'.$viewid;			
		$tableName = substr($tableName,0,29);
		$table_exist = 	$db->table_exist($tableName);
		if($table_exist){
			$sql = "truncate table {$tableName}";
			$db->query($sql);
		}
		else{
			Vtecrm_Utils::CreateTable($tableName,"crmid I(19) key");
		}			
		if ($db->isMysql()){
			$query = "insert ignore into {$tableName} ".$query;
			$result = $db->pquery($query,$params);
		}
		else {
			$tableName_ = $db->datadict->changeTableName($tableName);
			$query = "insert into $tableName ".
			$query."where not exists (select * from $tableName_ where $tableName_.crmid = un_table.crmid)";
			$result = $db->pquery($query,$params);
		}
		return $tableName;
	}	
	//crmv@47905e

	// crmv@197996 crmv@203081
	function getMoreInformationsDiv($recordId,$fieldName,$moreInformations) {
		$value = '&nbsp;<i id="toggle_'.$fieldName.'_'.$recordId.'" class="vteicon md-sm valign-bottom md-link" onmouseout="jQuery(\'#content_'.$fieldName.'_'.$recordId.'\').hide();" onmouseover="checkDivPosition(jQuery(\'#content_'.$fieldName.'_'.$recordId.'\').get(0),jQuery(\'#toggle_'.$fieldName.'_'.$recordId.'\').get(0),jQuery(\'#ListViewContents\').get(0));">chat</i>
			<div id="content_'.$fieldName.'_'.$recordId.'" class="layerPopup" style="width:300px;z-index:10000001;display:none;position:absolute;max-height:300px;overflow-y:auto;" onmouseout="jQuery(\'#content_'.$fieldName.'_'.$recordId.'\').hide();" onmouseover="jQuery(\'#content_'.$fieldName.'_'.$recordId.'\').show();">
			<table align="center" border="0" cellpadding="5" cellspacing="0" width="100%">
			<tr><td class="small">'.$moreInformations.'</td></tr>
			</table></div>';
		return $value;
	}
	// crmv@197996e crmv@203081e
}
