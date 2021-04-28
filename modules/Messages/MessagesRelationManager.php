<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@37004
require_once('modules/Messages/MessagesUtilities.php');

class MessagesRelationManager extends MessagesUtilities {

	var $peopleModules = array('Leads', 'Contacts', 'Accounts', 'Vendors');

	function __construct() {
		parent::__construct();
		global $table_prefix;
		$this->relation_table = "{$table_prefix}_messagesrel";
		$this->relation_table_id = 'messagehash';
		$this->relation_table_otherid = 'crmid';
		$this->relation_table_module = '';
		$this->relation_table_othermodule = 'module';
	}

	// insert into the relation table
	function save_related_module($module, $crmid, $with_module, $with_crmid, $skip_check=false) { // crmv@146653
		global $adb, $table_prefix;

		$thistabid = getTabId('Messages');
		$othertabid = getTabId($with_module);

		$hasrelation = false;
		$result = $adb->pquery("SELECT * FROM {$table_prefix}_relatedlists WHERE (tabid = ? AND related_tabid = ?)", array($othertabid,$thistabid));
		if ($result && $adb->num_rows($result) > 0) $hasrelation = true;

		if ($hasrelation) {
			if(!is_array($with_crmid)) $with_crmid = Array($with_crmid);
			//crmv@82688
			$hash = getSingleFieldValue($this->table_name, 'messagehash', $this->table_index, $crmid);
			if (empty($hash)) {
				$messageid = html_entity_decode(getSingleFieldValue($this->table_name, 'messageid', $this->table_index, $crmid), ENT_COMPAT, 'UTF-8');
				$subject = html_entity_decode(getSingleFieldValue($this->table_name, 'subject', $this->table_index, $crmid), ENT_COMPAT, 'UTF-8'); // crmv@81338
				$hash = $this->getMessageHash($messageid, $subject);
			}
			//crmv@82688e
			if ($hash) {
				foreach ($with_crmid as $relcrmid) {
					$checkpresence = $adb->pquery("SELECT crmid FROM {$this->relation_table} WHERE {$this->relation_table_id} = ? AND crmid = ? AND module = ?", Array($hash, $relcrmid, $with_module));
					if ($checkpresence && $adb->num_rows($checkpresence) > 0) continue;
					$adb->pquery("INSERT INTO {$this->relation_table} ({$this->relation_table_id}, crmid, module) VALUES (?,?,?)", array($hash, $relcrmid, $with_module));
					$adb->pquery("UPDATE {$this->table_name} SET modifiedtime = ? WHERE {$this->table_index} IN (?,?)", array($this->db->formatDate(date('Y-m-d H:i:s'), true), intval($crmid), intval($relcrmid))); // crmv@54449 crmv@56655 crmv@69690 crmv@171021
				}
			}
		}
	}

	// crmv@81338
	function save_related_module_small($messageid, $with_module, $with_crmid, $subject = null) {
		global $adb, $table_prefix;

		if(!is_array($with_crmid)) $with_crmid = Array($with_crmid);
		//crmv@82688
		$hash = getSingleFieldValue($this->table_name, 'messagehash', $this->table_index, $this->id);
		if (empty($hash)) {
			$hash = $this->getMessageHash($messageid, $subject);
		}
		//crmv@82688e
		if ($hash) {
			foreach ($with_crmid as $relcrmid) {
				$checkpresence = $adb->pquery("SELECT crmid FROM {$this->relation_table} WHERE {$this->relation_table_id} = ? AND crmid = ? AND module = ?", Array($hash, $relcrmid, $with_module));
				if ($checkpresence && $adb->num_rows($checkpresence) > 0) continue;
				$adb->pquery("INSERT INTO {$this->relation_table} ({$this->relation_table_id}, crmid, module) VALUES (?,?,?)", array($hash, $relcrmid, $with_module));
				$adb->pquery("UPDATE {$this->table_name} SET modifiedtime = ? WHERE {$this->table_index} IN (?,?)", array($this->db->formatDate(date('Y-m-d H:i:s'), true), intval($this->id), intval($relcrmid))); // crmv@54449 crmv@56655 crmv@69690 crmv@82688 crmv@170775 crmv@171021
			}
		}
	}
	// crmv@81338e

	/** Function to unlink an entity with given Id from another entity */
	function unlinkRelationship($id, $return_module, $return_id) {
		global $adb, $table_prefix;

		$mhash = getSingleFieldValue($this->table_name, 'messagehash', $this->table_index, $id);
		if ($mhash) {
			$query = "DELETE FROM {$this->relation_table} WHERE ({$this->relation_table_id} = ? AND crmid=?)";
			$params = array($mhash, $return_id);
			$r = $this->db->pquery($query, $params);
			//crmv@56829
			$query2 = "DELETE FROM {$table_prefix}_messages_recipients WHERE messagesid = ? AND id = ?";
			$params2 = array($id, $return_id);
			$r2 = $this->db->pquery($query2, $params2);
			//crmv@56829e
			$this->db->pquery("UPDATE {$this->table_name} SET modifiedtime = ? WHERE {$this->table_index} IN (?,?)", array($this->db->formatDate(date('Y-m-d H:i:s'), true), intval($id), intval($return_id))); // crmv@49398 crmv@56655 crmv@69690 crmv@171021
		}
	}

	function haveRelations($id,$modules='',$skip_modules='',$thread='') {
		//TODO do a unique query and cache values
		global $adb,$table_prefix;
		if (empty($skip_modules)) {
			$skip_modules = 'ModComments';
		}
		//crmv@171021
		$query = "select {$this->relation_table}.{$this->relation_table_otherid}
			from {$this->relation_table}
			inner join {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = {$this->relation_table}.{$this->relation_table_otherid}
			where deleted = 0";
		if (!empty($thread)) {
			$children = $this->getChildren($thread,'',false,'distinct messageSon.messagehash');
			$query .= " and {$this->relation_table}.{$this->relation_table_id} in (".generateQuestionMarks($children).")";
			$params = array($children);
		} else {
			$mhash = getSingleFieldValue($this->table_name, 'messagehash', $this->table_index, $id);
			$query .= " and {$this->relation_table}.{$this->relation_table_id} = ?";
			$params = array($mhash);
		}
		//crmv@171021e
		if (!empty($modules)) {
			if (!is_array($modules)) {
				$modules = array($modules);
			}
			$query .= " and $this->relation_table_othermodule in (".generateQuestionMarks($modules).")";
			$params[] = $modules;
			$skip_modules = '';
		}
		if (!empty($skip_modules)) {
			if (!is_array($skip_modules)) {
				$skip_modules = array($skip_modules);
			}
			$query .= " and $this->relation_table_othermodule not in (".generateQuestionMarks($skip_modules).")";
			$params[] = $skip_modules;
		}
		$result = $adb->pquery($query,$params);
		if ($result && $adb->num_rows($result) > 0) {
			return true;
		}
		return false;
	}

	// calculate hash for the current message
	// this hash must be unique for every message
	// crmv@81338 - Since some Exchange servers are stupid, and send emails with duplicate message-id,
	//	I have to add some other fields to the hash
	function getMessageHash($messageid = null, $subject = null) {
		$mid = ($messageid ? $messageid : $this->column_fields['messageid']);
		// subject must be raw, without html entities
		if ($subject === null) $subject = $this->column_fields['subject'] ?: '';
		if (!empty($mid))
			return sha1($mid . $subject);
		else
			return '';
	}
	
	function getCrmidFromMessageId($messageid) {
		global $adb;
		$res = $adb->pquery("select {$this->table_index} from {$this->table_name} where deleted = 0 and messageid = ?", array($messageid)); //crmv@171021 crmv@181551
		if ($res && $adb->num_rows($res) > 0) {
			return $adb->query_result_no_html($res, 0, $this->table_index);
		}
		return null;
	}

	// rebuild hashes for all messages
	function rebuildAllHashes() {
		global $adb, $table_prefix;
		if ($adb->isMysql()) {
			// optimized query for mysql
			$res = $adb->query("update {$this->table_name} set messagehash = SHA1(CONCAT(messageid, subject)) where deleted = 0"); //crmv@171021
		} else {
			// general query
			$res = $adb->query("select {$this->table_index}, messageid, subject from {$this->table_name} where deleted = 0"); //crmv@171021
			if ($res) {
				while ($row = $adb->FetchByAssoc($res, -1, false)) {
					$adb->pquery("update {$this->table_name} set messagehash = ? where {$this->table_index} = ?", array($this->getMessageHash($row['messageid'], $row['subject']), $row[$this->table_index]));
				}
			}
		}
	}
	// crmv@81338e
	
	/*
	 * email domains excluded by search by domain, regexp
	 */
	function getExcludedDomainsBySearch() {
		return array(
			'google', 'gmail', 'yahoo', 'hotmail', 'aol', 'msn', 'hotmail', 'icloud', 'aim\.', 'zoho\.com', 'outlook\.com', 'live\.com',
			'alice\.it', 'vodafone\.it', 'tiscali\.it', 'infostrada\.it', 'libero\.it', 'wind\.it', 'tele2\.it', 'fastweb', 'virgilio\.it',
			'aruba\.it', 'mail\.it', 'pec\.it', 'email\.it', 'outlook\.it', 'tin\.it', 'inwind',
		);
	}

	// parse an email address and retrieve all entities with the specified email
	// returns: array("module"=>array(id1, id2, ...), ... )
	// TODO: order records by relevance, or put second level stuff in a different array
	function getEntitiesFromEmail($email, $searchDomain=true, $deepSearch=true, $modules=null, $return_first_result=false) {
		global $current_user, $adb, $table_prefix;
		$email = trim($email);

		//crmv@41883
		require_once('include/utils/EmailDirectory.php');
		$emailDirectory = new EmailDirectory();
		if ($return_first_result) {
			$record = $emailDirectory->getRecord($email);
			if (!empty($record)) { //crmv@80298 reverted TT-62068
				return $record;
			}
		}
		//crmv@41883e

		// modules to search for email address
		if (empty($modules)) {
			$modules = array('Leads', 'Accounts', 'Contacts', 'Vendors', 'Users');
		}
		// modules to search by email domain
		$modulesByDomain = array('Accounts');
		// email domains
		$excludedDomains = $this->getExcludedDomainsBySearch();

		// email uitypes
		$emailUitype = $emailDirectory->getUItypes();

		$emailDomain = preg_replace('/^.*@/', '', $email);
		$emailSearchDomain = $emailDomain;

		// check for valid search domain
		foreach ($excludedDomains as $ed) {
			if (preg_match("/$ed/i", $emailSearchDomain)) {
				$emailSearchDomain = '';
				break;
			}
		}

		// get all email fields by module
		$emailFields = array();
		$query = "select fieldid, tablename, columnname, fieldname, {$table_prefix}_tab.name
					from {$table_prefix}_field
					inner join {$table_prefix}_tab ON {$table_prefix}_tab.tabid = {$table_prefix}_field.tabid
					where {$table_prefix}_tab.name in (".generateQuestionMarks($modules).") and uitype in (".generateQuestionMarks($emailUitype).")";
		$res = $adb->pquery($query, array($modules, $emailUitype));
		if ($res) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$emailFields[$row['name']][] = $row;
			}
		}

		// generate query to retrieve ids
		$relatedIds = array();
		foreach ($modules as $module) {
			$moduleFields = $emailFields[$module];
			if (empty($moduleFields)) continue;
			if (isPermitted($module,'index') == 'no') continue;	//crmv@56780
			if (isPermitted($module,'index') == 'no' || !vtlib_isModuleActive($module)) continue;	//crmv@56780 crmv@105172

			$qg = QueryGenerator::getInstance($module, $current_user);
			$accessibleFieldList = array_keys($qg->getModuleFields());

			$fields = array();
			foreach ($moduleFields as $i => $fieldinfo) {
				if ($module == 'Users' || in_array($fieldinfo['fieldname'],$accessibleFieldList)) {
					$fields[] = $fieldinfo['fieldname'];
				} else {
					unset($moduleFields[$i]);
				}
			}
			if (empty($moduleFields)) continue; //crmv@105529
			$qg->setFields($fields);

			if ($module == 'Users') {
				$sql = $qg->getQuery();
				// add condition
				$conditions = array();
				foreach ($moduleFields as $fieldinfo) {
					$conditions[] = $fieldinfo['tablename'].".".$fieldinfo['columnname']." = ".$adb->quote($email);	//crmv@53738
					// search also by domain
					if ($searchDomain && !empty($emailSearchDomain) && in_array($module, $modulesByDomain)) {
						$conditions[] = $fieldinfo['tablename'].".".$fieldinfo['columnname']." like '%".$adb->sql_escape_string("@$emailSearchDomain")."'";	//crmv@53738
					}
				}
				$sql .= ' AND ('.implode(' OR ', $conditions).')';
			} else {
				$qg->startGroup('');
				//crmv@92808 - rimosso index ed aggiunto flag nel caso il campo non fosse attivo per l'utente
				$return_flag = false;
				foreach ($moduleFields as $fieldinfo) {
					if($return_flag) {
						$qg->addConditionGlue(QueryGenerator::$OR);
					}
					$return_flag = $qg->addCondition($fieldinfo['fieldname'],$email,'e');
					// search also by domain
					if ($searchDomain && !empty($emailSearchDomain) && in_array($module, $modulesByDomain)) {
						if($return_flag){
							$qg->addConditionGlue(QueryGenerator::$OR);
						}
						$return_flag = $qg->addCondition($fieldinfo['fieldname'],"@$emailSearchDomain",'ew');
					}
				}
				//crmv@92808e
				$qg->endGroup();
				$sql = $qg->getQuery();
			}

			// add crmid
			if ($module == 'Users') {
				$sql = preg_replace('/^select.*?from/i', "select {$table_prefix}_users.id as crmid from", $sql);
			} else {
				$sql = preg_replace('/^select.*?from/i', "select {$table_prefix}_crmentity.crmid from", $sql);
			}

			// get the result
			if ($return_first_result) {
				$res = $adb->limitQuery($sql,0,1);
			} else {
				$res = $adb->query($sql);
			}
			if ($res) {
				while ($row = $adb->FetchByAssoc($res, -1, false)) {
					if ($return_first_result) {
						$emailDirectory->save($email,$row['crmid'],$module);	//crmv@41883
						return array('module'=>$module,'crmid'=>$row['crmid']);
					}
					$relatedIds[$module][] = $row['crmid'];
				}
			}
			if (isset($relatedIds[$module]) && count($relatedIds[$module]) > 0) { // crmv@167234
				$relatedIds[$module] = array_unique($relatedIds[$module]);
			}
		}

		$rm = RelationManager::getInstance();

		// crmv@42752
		// retrieve accounts for contacts found
		if ($deepSearch && is_array($relatedIds['Contacts']) && count($relatedIds['Contacts']) > 0) {
			foreach ($relatedIds['Contacts'] as $contid) {
				$linkedacc = $rm->getRelatedIds('Contacts', $contid, 'Accounts');
				if (!empty($linkedacc)) {
					if (!empty($relatedIds['Accounts'])) {
						$relatedIds['Accounts'] = array_merge($relatedIds['Accounts'], $linkedacc);
					} else {
						$relatedIds['Accounts'] = $linkedacc;
					}
				}
			}
			if (!empty($relatedIds['Accounts'])) {
				$relatedIds['Accounts'] = array_unique($relatedIds['Accounts']);
			}
		}
		// crmv@42752e

		// now for every account, retrieve other contacts
		if ($deepSearch && is_array($relatedIds['Accounts']) && count($relatedIds['Accounts']) > 0) {
			foreach ($relatedIds['Accounts'] as $accid) {
				$linkedcont = $rm->getRelatedIds('Accounts', $accid, 'Contacts');
				if (!empty($linkedcont)) {
					if (!empty($relatedIds['Contacts'])) {
						$relatedIds['Contacts'] = array_merge($relatedIds['Contacts'], $linkedcont);
					} else {
						$relatedIds['Contacts'] = $linkedcont;
					}
				}
			}
			if (!empty($relatedIds['Contacts'])) {
				$relatedIds['Contacts'] = array_unique($relatedIds['Contacts']);
			}
		}

		//crmv@41883 : salvo anche se non ho trovato niente cos� la volta prossima so che non c'� nulla
		if ($return_first_result && empty($relatedIds)) {
			$emailDirectory->save($email);
		}
		//crmv@41883e

		return $relatedIds;
	}

	// get modules for linking
	function getPopupLinkModules($from_module, $from_crmid = '', $mode='') { // crmv@43864
		global $adb, $table_prefix;

		$rm = RelationManager::getInstance();

		$excludeModules = array(
			'Documents', 'ModComments', 'Myfiles', 'MyNotes'	//crmv@42752
			, 'Events'	//crmv@58436
		);

		$mods = $rm->getRelatedModules('Messages');
		$mods = array_diff($mods, $excludeModules);
		if ($mode == 'linkdocument') {
			$mods_docs = $rm->getRelatedModules('Documents');
			$mods = array_intersect($mods, $mods_docs);
		}
		// permission cycle
		foreach ($mods as $k=>$mod) {
			if (!vtlib_isModuleActive($mod) || isPermitted($mod, 'EditView') != 'yes') unset($mods[$k]); // crmv@205756
		}

		// sort by module label
		usort($mods, function($m1, $m2) {
			return strcasecmp(getTranslatedString($m1, $m1), getTranslatedString($m2, $m2));
		});

		return $mods;
	}

	// get modules for creation
	function getPopupCreateModules($from_module, $from_crmid = '', $mode='') { // crmv@43864

		$excludeModules = array_merge(array('Calendar'), getInventoryModules()); // crmv@42752

		$mods = $this->getPopupLinkModules($mode);
		$mods = array_diff($mods, $excludeModules);

		return $mods;
	}

	// returns an array of fields populated with relevant values for the related module
	function getPopupQCreateValues($module, $relatedIds, $email = '', $name = '') {
		global $adb, $table_prefix;

		$ret = array();

		// calculate name fields
		if (!empty($name)) {
			$name_arr = explode(' ',$name);
			$lenght = count($name_arr);
			if ($lenght == 1) {
				$first_name = '';
				$last_name = $name_arr[0];
			} else {
				$first_name = $name_arr[0];
				array_shift($name_arr);
				$last_name = implode(' ',$name_arr);
			}
		}

		// get relation info (for id types)
		$rm = RelationManager::getInstance();
		$relmods = $rm->getRelations($module, ModuleRelation::$TYPE_NTO1);

		// filter based on related mods
		foreach ($relmods as $k=>$rel) {
			$destmod = $rel->getSecondModule();
			if (!array_key_exists($destmod, $relatedIds)) {
				//unset($relmods[$k]);
			}
		}

		// crmv@81136 crmv@91980 - extract phone numbers
		$allPhoneNumbers = $this->getPhoneNumbers($this->id) ?: array();
		$subPhoneNumbers = array();
		// group them by type also
		foreach ($allPhoneNumbers as $entry) {
			$subPhoneNumbers[$entry['type']][] = $entry['number'];
		}
		// crmv@81136e crmv@91980e

		$tabid = getTabid($module);
		// take all the quickcreate fields, regardless of the user permission (i will be checked in the QuicCreate function)
		$res = $adb->pquery("select fieldname, fieldlabel, uitype from {$table_prefix}_field where quickcreate in (0,2) and tabid = ? and {$table_prefix}_field.presence in (0,2) and displaytype != 2", array($tabid)); // crmv@81136

		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$value = '';
				$fieldname = $row['fieldname'];
				// check if it's a relation field (take the first one)
				foreach ($relmods as $k=>$rel) {
					if ($rel->fieldname == $fieldname) {
						$value = $relatedIds[$rel->getSecondModule()][0];
						break;
					}
				}
				// crmv@81136
				$phoneType = null;
				
				// check email and phone fields
				switch ($row['uitype']) {
					case '13':
						$value = $email;
						break;
					case '11':
						// should be a normal phone, but check the label, just to be sure
						if (stripos($row['fieldlabel'], 'mobile') !== false || stripos($row['fieldlabel'], 'cell') !== false) {
							$phoneType = 'mobile';
						} else {
							$phoneType = 'phone';
						}
						break;
					case '1014':
						$phoneType = 'mobile';
						break;
				}

				// populate special fields
				if (in_array($module,$this->peopleModules)) {
					
					// set the name
					if (!empty($name)) {
						switch ($module) {
							case 'Leads':
							case 'Contacts':
								if (!empty($first_name) && $fieldname == 'firstname') {
									$value = $first_name;
								}
								if (!empty($last_name) && $fieldname == 'lastname') {
									$value = $last_name;
								}
								break;
							case 'Accounts':
								if ($fieldname == 'accountname') {
									$value = $name;
								}
								break;
							case 'Vendors':
								if ($fieldname == 'vendorname') {
									$value = $name;
								}
								break;
						}
					}
					
					//set the phone numbers
					if ($phoneType && count($allPhoneNumbers) > 0) {
						if (count($subPhoneNumbers[$phoneType]) > 0) {
							// use the proper type
							$value = array_shift($subPhoneNumbers[$phoneType]);
						} else {
							// use the other type
							$otherType = ($phoneType == 'mobile' ? 'phone' : 'mobile');
							if (count($subPhoneNumbers[$otherType]) > 0) {
								$value = array_shift($subPhoneNumbers[$otherType]);
							}
						}
					}
				}
				// crmv@81136e

				// set the value
				if (!empty($value)) $ret[$fieldname] = $value;
			}
		}
		return $ret;
	}
	
	//crmv@44610
	function getMessagesWithSameHash($id) {
		global $adb, $table_prefix;
		$query = "select {$table_prefix}_messages.messagesid from {$table_prefix}_messages where deleted = 0"; //crmv@171021
		$mhash = getSingleFieldValue($this->table_name, 'messagehash', $this->table_index, $id);
		$query .= " and messagehash = ?";
		$params = array($mhash);
		$return = array();
		$result = $adb->pquery($query,$params);
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByASsoc($result)) {
				$return[] = $row['messagesid'];
			}
		}
		return $return;
	}
	//crmv@44610e
	
	function getSuggestedRelIds() {
		
		//crmv@168655
		try {
			$idlist = Zend_Json::decode($_REQUEST['relevant_ids']);
			if (empty($idlist)) $idlist = array();
		} catch (Exception $e) {
			$idlist = array();
		}
		if (!empty($idlist)) return $idlist;
		//crmv@168655e

		// retrieve name and email
		$folder = $this->column_fields['folder'];
		$specialFolders = $this->getSpecialFolders();
		if (in_array($folder,array($specialFolders['Sent'],$specialFolders['Drafts']))) {
			$emails = array_filter(array_map('trim', explode(',', $this->column_fields['mto'])));
			$emails = array_merge($emails, array_filter(array_map('trim', explode(',', $this->column_fields['mcc']))));
			$name = trim(trim($this->column_fields['mto_n']),'"');
		} else {
			$emails = array_filter(array_map('trim', explode(',', $this->column_fields['mfrom'])));
			$emails = array_merge($emails, array_filter(array_map('trim', explode(',', $this->column_fields['mcc']))));
			$name = trim(trim($this->column_fields['mfrom_n']),'"');
		}
	
		// remove myself from emails - REMOVED
		/*
		$my_addresses[] = getSingleFieldValue($table_prefix.'_users', 'email1', 'id', $current_user->id);
		$my_addresses[] = getSingleFieldValue($table_prefix.'_users', 'email2', 'id', $current_user->id);
		$emails = array_diff($emails, $my_addresses);
		*/
	
		$email = $emails[0];
	
		if (empty($email) || $email == 'undisclosed-recipients:;') {
			die('Empty Email');
		}
	
		// use all possible emails to retrieve entitities, and also use a deep search
		$idlist = array();
		foreach ($emails as $em) {
			$list = $this->getEntitiesFromEmail($em);
			foreach ($list as $mod => $ids) {
				if (!is_array($idlist[$mod])) {
					$idlist[$mod] = $ids;
				} else {
					$idlist[$mod] = array_unique(array_merge($idlist[$mod], $ids));
				}
				$idlist[$mod] = array_values($idlist[$mod]); //crmv@168655
			}
		}
		//crmv@168655
		return array(
			'idlist' => $idlist,
			'email' => $email,
			'name' => $name,
		);
		//crmv@168655e
	}
}
?>