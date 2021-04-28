<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@174812 */

class ModNotifications_CommentsModel {
	
	protected $data;
	static $ownerNamesCache = array();
	static $ownerPhotoCache = array();
	static $customerPortalUserId = null;
	
	function __construct($datarow) {
		$this->data = $datarow;
		
		if (empty(self::$customerPortalUserId) && isModuleInstalled('CustomerPortal')) { // crmv@189136
			require_once('modules/CustomerPortal/PortalUtils.php');
			self::$customerPortalUserId = cp_getCurrentUser();
		}
	}
	
	function author() {
		if ($this->data['mod_not_type'] == 'Generic') return ''; //crmv@183346
		
		$authorid = $this->data['smcreatorid'];
		if (!empty($this->data['from_email_name']) && ($authorid == self::$customerPortalUserId || in_array($this->data['mod_not_type'],array('Ticket portal replied','Ticket portal created','Calendar invitation answer yes contact','Calendar invitation answer no contact')))) {
			return $this->data['from_email_name'];
		}
		if(!isset(self::$ownerNamesCache[$authorid])) {
			self::$ownerNamesCache[$authorid] = trim(getUserFullName($authorid));
		}
		return self::$ownerNamesCache[$authorid];
	}
	
	function authorPhoto() {
		$authorid = $this->data['smcreatorid'];
		if ($authorid == self::$customerPortalUserId || in_array($this->data['mod_not_type'],array('Ticket portal replied','Ticket portal created','Calendar invitation answer yes contact','Calendar invitation answer no contact'))) {
			return getPortalAvatar();
		}
		if(!isset(self::$ownerPhotoCache[$authorid])) {
			self::$ownerPhotoCache[$authorid] = getUserAvatar($authorid);
		}
		return self::$ownerPhotoCache[$authorid];
	}

	function timestamp(){
		return CRMVUtils::timestamp($this->data['createdtime']); // crmv@164654
	}

	function timestampAgo(){
		return CRMVUtils::timestampAgo($this->data['createdtime']); // crmv@164654
	}

	function content() {

		global $adb, $current_user,$table_prefix,$site_URL; // crmv@178322

		$focus = ModNotifications::getInstance(); // crmv@164122
		
		//crmv@183346
		if ($this->data['mod_not_type'] == 'Generic') {
			$html = $this->data['description'];
		} else {
			$html = $focus->translateNotificationType($this->data['mod_not_type'],'action');
		}
		//crmv@183346e

		//crmv@31126
		if ($this->data['mod_not_type'] == 'Import Completed') {
			$html .= ' <a href="index.php?module='.$this->data['description'].'&action=index">'.$this->data['description'].'</a>';
		}
		//crmv@31126e

		if ($this->data['mod_not_type'] == 'Relation') {
			$parent_id = $this->data['description'];
			$parent_module = getSalesEntityType($parent_id);
			$entityType = getSingleModuleName($parent_module,$parent_id);
			$displayValueArray = getEntityName($parent_module, $parent_id);
			if(!empty($displayValueArray)){
				foreach($displayValueArray as $key=>$value){
					$displayValue = $value;
				}
			}
			$html .= " <a href='index.php?module=$parent_module&action=DetailView&record=$parent_id' title='$entityType' target='_parent'>$displayValue</a> ($entityType) ";
			$html .= getTranslatedString('LBL_TO','ModComments');
		}
		$html .= ' '.$this->relatedToString();

		if ($this->data['mod_not_type'] == 'ListView changed') {
			$html .= '&nbsp;:<br />';
			$changes = array_filter(explode(',',$this->data['description']));
			$html_changes = array(); // crmv@177137
			if (!empty($changes)) {
				//crmv@58625
				global $list_max_entries_per_page;
				$cnt_changes = 1;
				$show_other = '';
				if (count($changes) > $list_max_entries_per_page){
					$show_other = ", ...".getTranslatedString('LBL_OTHERS')." ".(count($changes)-$list_max_entries_per_page);
				}
				foreach($changes as $id) {
					$module = getSalesEntityType($id);	
					$displayValueArray = getEntityName($module,$id);
					if(!empty($displayValueArray)){
						foreach($displayValueArray as $key=>$value){
							$displayValue = $value;
						}
					}
					$html_changes[] = "<a href='index.php?module=$module&action=DetailView&record=$id' target='_parent'>$displayValue</a>";
					$cnt_changes++;
					if ($cnt_changes > $list_max_entries_per_page){
						break;
					}
				}
				$html .= implode(', ',$html_changes).$show_other;
				//crmv@58625 e
			}
		}

		if ($this->parent_module != '' && in_array($this->data['mod_not_type'],array('Changed followed record','Changed record'))) {

			// crmv@164120
			$q = "SELECT changelogid, description FROM {$table_prefix}_changelog WHERE parent_id = ? AND user_name <> ? AND hide = 0 ORDER BY changelogid DESC";	//crmv@135193
			$ress = $adb->limitpQuery($q,0,1,array($this->parent_id, $current_user->user_name));
			$changelogid = $adb->query_result_no_html($ress,0,"changelogid");
			$description = $adb->query_result_no_html($ress,0,"description");
			$description_elements = Zend_Json::decode($description);

			$ChangeLogFocus = ChangeLog::getInstance();
			// crmv@164120e

			$html .= '<br /><a style="text-decoration:none;" href="javascript:void(0);" onClick="ModNotificationsCommon.toggleChangeLog(\''.$changelogid.'\');" ><i class="vteicon" id="img_'.$changelogid.'">keyboard_arrow_right</i><span style="position: relative; bottom: 7px;">'.getTranslatedString('LBL_DETAILS','ModNotifications').'</span></a>';	//crmv@104566
			$html .= '<div style="display:none;" id="div_'.$changelogid.'" name="div_'.$changelogid.'">';
			$html .= $ChangeLogFocus->getFieldsTable($description, $this->parent_module);
			$html .= '</div>';
		}

		// crmv@43194	crmv@54917
		if ($this->data['related_to'] > 0 && in_array($this->data['mod_not_type'], array('Calendar invitation', 'Calendar invitation edit'))) {
			$rowid = $this->data['modnotificationsid'];
			$checkedYes = $checkedNo = '';
			$res = $adb->pquery("select partecipation from {$table_prefix}_invitees where activityid = ? and inviteeid = ?",array($this->data['related_to'],$this->data['smownerid']));
			if ($res) $invitationAnswer = $adb->query_result($res,0,'partecipation');
			if ($invitationAnswer == 1) {
				$checkedNo = 'checked="checked"';
			} elseif ($invitationAnswer == 2) {
				$checkedYes = 'checked="checked"';
			}
			$html .= '<br> '.getTranslatedString('LBL_INVITATION_QUESTION', 'ModNotifications').'? ';
			$html .= '<a><input id="notifInvitiationAnswerYes_'.$rowid.'" name="notifInvitiationAnswer_'.$rowid.'" '.$checkedYes.' type="radio" name="" style="vertical-align:bottom" onclick="ModNotificationsCommon.acceptInvitation('.$this->data['related_to'].', '.$current_user->id.')" /><label for="notifInvitiationAnswerYes_'.$rowid.'">'.getTranslatedString('LBL_YES').'</label></a>';
			$html .= '<a><input id="notifInvitiationAnswerNo_'.$rowid.'" name="notifInvitiationAnswer_'.$rowid.'" '.$checkedNo.' type="radio" name="" style="vertical-align:bottom" onclick="ModNotificationsCommon.declineInvitation('.$this->data['related_to'].', '.$current_user->id.')" /><label for="notifInvitiationAnswerNo_'.$rowid.'">'.getTranslatedString('LBL_NO').'</label></a>';
		}
		// crmv@43194e	crmv@54917e

		//crmv@65455
		if ($this->data['mod_not_type'] == 'Import Error') {
			// crmv@178322
			$dataimporter_url = $site_URL.'/index.php?module=Settings&action=DataImporter';
			$desc = sprintf(getTranslatedString('LBL_IMPORT_ERROR_NOTIF_DESC', 'Settings'),$dataimporter_url);
			// crmv@178322e
			$html = "<b>$html</b> ".$desc;
		}
		//crmv@65455e

		//crmv@91571
		if ($this->data['mod_not_type'] == 'MassEdit' || $this->data['mod_not_type'] == 'MassEditError') {
			$MUtils = MassEditUtils::getInstance();
			$html = $MUtils->getNotificationHtml($this->data['related_to'], $html);
		}
		//crmv@91571e

		// crmv@202577
		if ($this->data['mod_not_type'] == 'MassCreate' || $this->data['mod_not_type'] == 'MassCreateError') {
			$MUtils = MassCreateUtils::getInstance();
			$html = $MUtils->getNotificationHtml($this->data['related_to'], $html);
		}
		// crmv@202577e

		return $html;
	}

	// crmv@31780 - restituisce un array, no html
	// TODO: listview
	function content_no_html() {
		global $adb, $current_user,$table_prefix;
		$ret = array();

		$focus = ModNotifications::getInstance(); // crmv@164122
		//crmv@183346
		if ($this->data['mod_not_type'] == 'Generic') {
			$ret['action'] = $this->data['description'];
		} else {
			$ret['action'] = $focus->translateNotificationType($this->data['mod_not_type'],'action');
		}
		//crmv@183346e
		$ret['notification_type'] = $this->data['mod_not_type'];

		//crmv@31126
		/*if ($this->data['mod_not_type'] == 'Import Completed') {
			$html .= ' <a href="index.php?module='.$this->data['description'].'&action=index">'.$this->data['description'].'</a>';
		}*/
		//crmv@31126e

		if ($this->data['mod_not_type'] == 'Relation') {
			$parent_id = $this->data['description'];
			$parent_module = getSalesEntityType($parent_id);
			$entityType = getSingleModuleName($parent_module,$parent_id);
			$displayValueArray = getEntityName($parent_module, $parent_id);
			if(!empty($displayValueArray)){
				foreach($displayValueArray as $key=>$value){
					$displayValue = $value;
				}
			}
			$html .= " <a href='index.php?module=$parent_module&action=DetailView&record=$parent_id' title='$entityType' target='_parent'>$displayValue</a> ($entityType) ";
			$html .= getTranslatedString('LBL_TO','ModComments');

			$ret['related'] = array(
				'module' => $parent_module,
				'record' => $parent_id,
				'value' => $displayValue,
				'type' => $entityType
			);
		}

		$ret['item'] = $this->relatedToString(true);
		
		if (!empty($ret['item']) && !empty($ret['action']) && $this->data['mod_not_type'] == 'Generic') $ret['action'] .= ' '.getTranslatedString('LBL_ABOUT','ModComments'); //crmv@183346

		$ret['haslist'] = false;
		if ($this->data['mod_not_type'] == 'ListView changed') {
			$changes = array_filter(explode(',',$this->data['description']));
			$html_changes = '';
			if (!empty($changes)) {
				foreach($changes as $id) {
					$module = getSalesEntityType($id);
					$displayValueArray = getEntityName($module,$id);
					if(!empty($displayValueArray)){
						foreach($displayValueArray as $key=>$value){
							$displayValue = $value;
						}
					}
					$ret['list'][] = array( // crmv@175507
						'module' =>  $module,
						'record' => $id,
						'value' => $displayValue
					);
				}
				$ret['haslist'] = true;
			}
		}

		$ret['hasdetails'] = false;
		if ($this->parent_module != '' && in_array($this->data['mod_not_type'],array('Changed followed record','Changed record'))) {

			// crmv@164120
			$q = "SELECT changelogid, description FROM {$table_prefix}_changelog WHERE parent_id = ? AND user_name <> ? AND hide = 0 ORDER BY changelogid DESC";	//crmv@135193
			$ress = $adb->limitpQuery($q,0,1,array($this->parent_id, $current_user->user_name));
			$changelogid = $adb->query_result_no_html($ress,0,"changelogid");
			$description = $adb->query_result_no_html($ress,0,"description");

			$ChangeLogFocus = ChangeLog::getInstance();
			// crmv@164120e

			$ret['details'] = $ChangeLogFocus->getFieldsTable($description, $this->parent_module, true);
			if (is_array($ret['details'])) {
				foreach ($ret['details'] as $k=>$v) if (is_array($ret['details'][$k])) $ret['details'][$k]['changelogid'] = $changelogid;
				if (count($ret['details']) == 0) {
					unset($ret['details']);
				} else {
					$ret['hasdetails'] = true;
				}
			}

			$ret['changelogid'] = $changelogid;
		}

		//crmv@91571
		if ($this->data['mod_not_type'] == 'MassEdit' || $this->data['mod_not_type'] == 'MassEditError') {
			$MUtils = MassEditUtils::getInstance();
			$ret['massedit'] = $MUtils->getNotificationInfo($this->data['related_to']);
		}
		//crmv@91571e

		// crmv@202577
		if ($this->data['mod_not_type'] == 'MassCreate' || $this->data['mod_not_type'] == 'MassCreateError') {
			$MUtils = MassCreateUtils::getInstance();
			$ret['masscreate'] = $MUtils->getNotificationInfo($this->data['related_to']);
		}
		// crmv@202577e

		return $ret;
	}
	// crmv@31780e

	function id() {
		return $this->data['modnotificationsid']; // crmv@164122
	}

	function relatedTo() {
		return $this->data['related_to'];
	}

	// crmv@31780
	function relatedToString($nohtml = false) {
		global $table_prefix;
		$this->parent_id = $this->relatedTo();
		if (!in_array($this->parent_id,array('',0))) {
			if ($this->data['mod_not_type'] == 'ListView changed') {
				global $adb, $app_strings;
				$result = $adb->query('SELECT * FROM '.$table_prefix.'_customview WHERE cvid = '.$this->parent_id);
				if ($result) {
					$this->parent_module = $adb->query_result($result,0,'entitytype');
					$entityType = getTranslatedString($this->parent_module,$this->parent_module);
					$viewname = $adb->query_result($result,0,'viewname');
					if ($viewname == 'All') {
						$viewname = $app_strings['COMBO_ALL'];
					} elseif($this->parent_module == 'Calendar' && in_array($viewname,array('Events','Tasks'))) {
						$viewname = $app_strings[$viewname];
					}
					$displayValue = $viewname;
				}
				if (empty($displayValue)) {
					$displayValue = $entityType;
				}
				if ($nohtml) {
					return array(
						'module' => $this->parent_module,
						'action' => 'index',
						'viewname' => $this->parent_id,
						'value' => $displayValue,
						'type' => $entityType
					);
				} else {
					return " <a href='index.php?module=$this->parent_module&action=ListView&viewname=$this->parent_id' title='$entityType' target='_parent'>$displayValue</a> ($entityType)"; // crmv@157081
				}
			} else {
				//crmv@157603
				$this->parent_module = $real_parent_module = getSalesEntityType($this->parent_id);
				if ($this->parent_module == 'Calendar') $real_parent_module = getSalesEntityType($this->parent_id,true);
				$entityType = getSingleModuleName($real_parent_module,$this->parent_id);
				$displayValueArray = getEntityName($real_parent_module, $this->parent_id);
				//crmv@157603e
				$displayValue = $displayValueArray[$this->parent_id];
				if (empty($displayValue)) {
					$displayValue = $entityType;
				}
				if ($nohtml) {
					return array(
						'module' => $this->parent_module,
						'action' => 'DetailView',
						'record' => $this->parent_id,
						'value' => $displayValue,
						'type' => $entityType
					);
				} else {
					// crmv@43050
					if ($this->parent_module == 'ModComments') {
						return " <a href='javascript:;' onclick=\"jQuery('#ModNotifications .closebutton').click(); top.jQuery('#ModCommentsCheckChangesImg').click();\" title='$entityType' target='_parent'>$displayValue</a> ($entityType) "; // crmv@43194
					} else {
						//crmv@183346
						$html = '';
						if ($this->data['mod_not_type'] == 'Generic') $html = '<br>'.getTranslatedString('LBL_ABOUT','ModComments');
						$html .= " <a href='index.php?module=$this->parent_module&action=DetailView&record=$this->parent_id' title='$entityType' target='_parent'>$displayValue</a> ($entityType)";
						// crmv@191501
						if ($real_parent_module == 'Leads') {
							// check if converted
							$leadFocus = CRMEntity::getInstance('Leads');
							if ($leadFocus->isConverted($this->parent_id)) {
								$contid = $leadFocus->getConvertedContact($this->parent_id);
								if ($contid > 0) {
									$convModule = 'Contacts';
									$entityType2 = getSingleModuleName($convModule,$contid);
									$displayValue2 = getEntityName($convModule, $contid, true);
									$html .= ' '.getTranslatedString('LBL_CONVERTED_IN', 'ModNotifications');
									$html .= " <a href='index.php?module=$convModule&action=DetailView&record=$contid' title='$entityType2' target='_parent'>$displayValue2</a> ($entityType2)";
								}
							}
						}
						// crmv@191501e
						return $html;
						//crmv@183346e
					}
					// crmv@43050e
				}
			}
		}
	}
	// crmv@31780e

	function isUnseen() {
		if ($this->data['seen'] == 1) {
			return false;
		} else {
			return true;
		}
	}
}