<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
class VTSimpleTemplate{

	function __construct($templateString, $prepareValue=true){ //crmv@177561
		$this->template = $templateString;
		$this->prepareValue = $prepareValue; //crmv@177561
	}

	function render($entityCache, $entityId){
		$this->cache = $entityCache;
		$this->parent = $this->cache->forId($entityId);
		return $this->parseTemplate();
	}

	private function matchHandler($match){
		preg_match('/\((\w+) : \(([_\w]+)\) (\w+)\)/', $match[1], $matches);
		if(count($matches)==0){
			$fieldname = $match[1];
			$module = $this->parent->getModuleName(); // crmv@112144 - main module
			$data = $this->parent->getData();
			//crmv@92272
			if ($fieldname == 'crmid') {
				$result = $this->parent->getId();
			//crmv@92272e
			//crmv@126830
			}elseif($fieldname == 'record') {
				list($wsModId, $result) = vtws_getIdComponents($this->parent->getId());
			//crmv@126830e
			//crmv@203747
			} elseif (substr($fieldname, -8) === 'prodattr') {
				global $table_prefix;
				$fieldname = str_replace('prodattr', '', $fieldname);
				//getting recordid
				$record = explode('x', $this->parent->getId());
				$record = $record[1];

				$entConfProducts = CRMEntity::getInstance('ConfProducts');

				$confprodinfo = getSingleFieldValue($table_prefix . '_products', 'confprodinfo', 'productid', $record, false);
				$confprodinfo = Zend_Json::decode($confprodinfo) ?: [];
				$result = '';
				foreach($confprodinfo as $k => $v) {
					$arr = $entConfProducts->getAttributeFromName($k);
					if ($arr['fieldlabel'] == $fieldname) {
						$result = $confprodinfo[$arr['fieldname']];
						break;
					}
				}
			//crmv@203747e
			}elseif($this->useValue($data, $fieldname, $module)){ // crmv@195977
				$result = $this->prepareValue($data[$fieldname], $fieldname, $module, $data); // crmv@112144
			}else{
				$result ='';
			}
		}else{
			list($full, $referenceField, $referenceModule, $fieldname) = $matches;
			if($referenceModule === '__VtigerMeta__' || $referenceModule === '__VteMeta__'){ //crmv@162158
				$result = $this->getMetaValue($fieldname);
			}else{
				$referenceId = $this->parent->get($referenceField);
				if($referenceId==null){
					$result="";
				}else{
					$entity = $this->cache->forId($referenceId);
					if($referenceModule==="Users" && $entity->getModuleName()=="Groups"){
						list($groupEntityId, $groupId) = vtws_getIdComponents($referenceId);

						require_once('include/utils/GetGroupUsers.php');
						$ggu = new GetGroupUsers();
						$ggu->getAllUsersInGroup($groupId,true);	//crmv@46552

						$users = $ggu->group_users;
						$parts = Array();
						foreach($users as $userId){
							$refId = vtws_getWebserviceEntityId("Users", $userId);
							$entity = $this->cache->forId($refId);
							$data = $entity->getData();
							if($this->useValue($data, $fieldname, $referenceModule)){ // crmv@195977
								$parts[] = $data[$fieldname];
							}
						}
						$result = implode(",", $parts);
					}
					//crmv@24350
					elseif($entity->getModuleName()===$referenceModule){
					//crmv@24350e
						$data = $entity->getData();
						if($this->useValue($data, $fieldname, $referenceModule)){ // crmv@195977
							$result = $this->prepareValue($data[$fieldname], $fieldname, $referenceModule, $data); // crmv@112144
						}else{
							$result = '';
						}
					}else{
						$result = '';
					}
				}
			}
		}
		return $result;

	}
	
	// crmv@112144 crmv@177561
	public static $formatUitypeList = array(15, 16, 300, 56, 7, 9, 71, 72, 73); // crmv@192235
	
	public static function preparedValue($value, $fieldname, $module, $data) {
		$st = new VTSimpleTemplate('',true);
		$field_uitype = $st->getUITypeByName($module,$fieldname);
		return in_array($field_uitype, self::$formatUitypeList);
	}
	
	public function prepareValue($value, $fieldname, $module, $data, $field_uitype='') {
		if ($this->prepareValue) {
			if (empty($field_uitype)) $field_uitype = $this->getUITypeByName($module,$fieldname);
			$currencyFields = array('hdnGrandTotal', 'hdnSubTotal', 'hdnS_H_Amount', 'hdnDiscountAmount', 'txtAdjustment'); // crmv@184005
			if (in_array($field_uitype, array(15, 16, 300))) {
				$value = getTranslatedString($value, $module);
			// crmv@176020
			} elseif ($field_uitype == 56) {
				$value = getTranslatedString(($value === 1 || $value === '1') ? 'LBL_YES' : 'LBL_NO', 'APP_STRINGS');
			// crmv@176020e
			// crmv@184005
			} elseif (in_array($field_uitype,array(7, 9, 71, 72)) || in_array($fieldname, $currencyFields)) { // crmv@192235
				global $current_user;
				if ($current_user->id == '') $current_user->id = 1;
				$value = formatUserNumber($value);
			// crmv@184005e
			} elseif ($field_uitype == 73) {
				require_once('modules/SDK/src/73/73Utils.php');
				$uitypeTimeUtils = UitypeTimeUtils::getInstance();
				$value = $uitypeTimeUtils->seconds2Time($value);
			}
		}
		return $value;
	}
	
	protected function getUITypeByName($module, $fieldname) {
		global $adb, $table_prefix;
		$result = $adb->pquery("select uitype from {$table_prefix}_field where tabid=? and fieldname=?", array(getTabid($module), $fieldname));
		$uitype = $adb->query_result_no_html($result,0,"uitype");
		return $uitype;
	}
	// crmv@112144e crmv@177561e
	
	// crmv@195977
	protected function useValue($data, $fieldname, $module) {
		// TODO use ws type ex. string (pre il momento le patch di questo file non caricarle in svn)
		$field_uitype = $this->getUITypeByName($module,$fieldname);
		if ($field_uitype == 1 && $data[$fieldname] === '0') return true;
		return ($data[$fieldname] != ''); // crmv@176020
	}
	// crmv@195977e

	function parseTemplate(){
		return preg_replace_callback('/\\$(\w+|\((\w+) : \(([_\w]+)\) (\w+)\))/', array($this,"matchHandler"), $this->template); // crmv@73654
	}
	//crmv@36510 crmv@162158
	function getMetaValue($fieldname){
		require_once 'config.inc.php';
		global $site_URL, $PORTAL_URL, $current_user;
		switch($fieldname){
			case 'date'					:	return getNewDisplayDate();
			case 'date_Y_m_d'			:	return date('Y-m-d');
			case 'date_d_m_Y'			:	return date('d-m-Y');
			case 'date_m_d_Y'			:	return date('m-d-Y');
			case 'time'					:	return date('H:i:s');
			case 'dbtimezone'			:	return DateTimeField::getDBTimeZone();
			case 'crmdetailviewurl'		:	$wsId = $this->parent->getId();
											$parts = explode('x', $wsId);
											$recordId = $parts[1];
											$moduleName = $this->parent->getModuleName();
											if($moduleName == 'Events') $moduleName = 'Calendar'; // crmv@175568
											return $site_URL.'/index.php?action=DetailView&module='.$moduleName.'&record='.$recordId;
			case 'portaldetailviewurl'	: 	$wsId = $this->parent->getId();
											$parts = explode('x', $wsId);
											$recordId = $parts[1];
											$moduleName = $this->parent->getModuleName();
											$recorIdName='id';
											if($moduleName == 'HelpDesk') $recorIdName = 'ticketid';
											if($moduleName == 'Faq') $recorIdName = 'faqid';
											if($moduleName == 'Products') $recorIdName = 'productid';
											return $PORTAL_URL.'/index.php?module='.$moduleName.'&action=index&'.$recorIdName.'='.$recordId.'&fun=detail';
			case 'siteurl'				:	return $site_URL;
			case 'portalurl'			:	return $PORTAL_URL;
			case 'clientip'				:	return getIP();
			default: '';
		}
	}
	//crmv@36510e crmv@162158e
}
//crmv@15309
class VTSimpleTemplate2{

	function __construct($templateString,$newsletterid,$templateid=''){	//crmv@22700
		$this->template = $templateString;
		$this->newsletterid = $newsletterid;	//crmv@22700
		$this->id = $templateid;	//crmv@22700
	}

	//crmv@43581	crmv@22700	crmv@24644
	function render($entityCache, $entityId){
		$this->cache = $entityCache;
		$this->parent = $this->cache->forId($entityId);
    	$id = explode('x',$entityId);
    	//crmv@64475
		static $field_definition_cache = array();
		$entityName = getSalesEntityType($id[1]);
		if (!isset($field_definition_cache[$entityName])) {
			$this->field_definition = $field_definition_cache[$entityName] = getEmailTemplateFields(false,$entityName);
		} else {
			$this->field_definition = $field_definition_cache[$entityName];
		}
		//crmv@64475e
		$this->replacements = array(); // crmv@38592
		return $this->parseTemplate();
	}

	private function matchHandler($match){
		$real_value = $match[0];	//crmv@22700
		$match = $match[1];
	    $fields = Array();
        $data = explode('|',$match);
        $module=$data[0];
        $related=$data[1];
        //crmv@31358
        if (strpos($related,'#') !== false){
        	list($related,$related_field) = explode('#',$related);
        }
        //crmv@31358 e
        $fieldname=$data[2];
        if ($module=='custom'){
			$result = $this->getMetaValue($fieldname);

        }else{
        	if (!$related){
        		//crmv@92272
        		if ($fieldname == 'crmid') {
        			$result = $this->parent->getId();
        			$result = explode('|',$result);
        			$result = $result[0];
        		//crmv@92272e
        		//crmv@126830
        		} elseif ($fieldname == 'record') {
        			$result = $this->parent->getId();
        			$result = explode('|',$result);
        			$result = $result[0];
        			list($wsModId, $result) = vtws_getIdComponents($result);
        		//crmv@126830e
        		} else {
	        		if ($module == 'Newsletter') {
	        			if (strpos($fieldname,'tracklink#') !== false) {
	        				$result = $this->getTrackLink(str_replace('tracklink#','',$fieldname));
	        				$data[$fieldname] = $result;
	        			} elseif (strpos($fieldname,'target_') !== false) {
	        				$result = $this->getNewsletterValue($fieldname);
	        				$data[$fieldname] = $result;
	        			} else {	//campo del modulo Newsletter
	        				$wsid = construct_ws_id($this->newsletterid,'Newsletter');
	        				$ent = $this->cache->forId($wsid);
	        				$data = $ent->getData();
	        			}
	        		} elseif ($module == 'Users'){
	        			global $current_user;
	        			$wsid = construct_ws_id(VteSession::get('authenticated_user_id'),$module); //crmv@28425
	        			$ent = $this->cache->forId($wsid);
	        			$data = $ent->getData();
	        		} else {
		        		$data = $this->parent->getData();
	        		}
					if($this->useValue($data, $fieldname)){
						//crmv@59215 crmv@59737 crmv@71678
						// added parsing numbers TODO picklists
						// added parsing uitype 19
						$field_uitype = $this->getUITypeByName($module,$fieldname);
						$currencyFields = array('hdnGrandTotal', 'hdnSubTotal', 'hdnS_H_Amount', 'hdnDiscountAmount', 'txtAdjustment');
						if ($field_uitype == 71 || in_array($fieldname, $currencyFields)) {
							global $current_user;
							if ($current_user->id == '') $current_user->id = 1;
							$result = formatUserNumber($data[$fieldname]);
						} elseif ($field_uitype == 19) {
							$result = nl2br($data[$fieldname]);
						// crmv@109388
						} elseif ($field_uitype == 33) {
							$list = explode(' |##| ', $data[$fieldname]);
							foreach ($list as &$listval) {
								$listval = getTranslatedString($listval, $module);
							}
							$result = implode(', ', $list);
						// crmv@109388e
						// crmv@127671
						} elseif($field_uitype == 55 && $fieldname == 'salutationtype'){
							$result = getTranslatedString($data[$fieldname],$module);
						// crmv@127671e
						} else {
							$result = $data[$fieldname];
						}
						//crmv@59215e crmv@59737e crmv@71678e
					}else{
						$result ='';
					}
        		}
	        } else{
        		//crmv@31358
        		if ($related_field != ''){
                	if (is_array($this->field_definition[$module]['related'][$related_field][$related])){ // crmv@184006
	        			$found = false;
	        			foreach ($this->field_definition[$module]['related'][$related_field][$related] as $f){ // crmv@184006
	        				if ($f['name'] == $fieldname){ // crmv@184006
	        					$found = $this->field_definition[$module]['master'][$f['id_reference']]['name']; // crmv@184006
	        					if ($found)
	        						break;
	        				}
	        			}
	        			if ($found){
	        				$referenceId = $this->parent->get($found);
	        				if($referenceId==null){
								$result="";
							}else{
								$entity = $this->cache->forId($referenceId);
								if($related==="Users" && $entity->getModuleName()=="Groups"){
									list($groupEntityId, $groupId) = vtws_getIdComponents($referenceId);

									require_once('include/utils/GetGroupUsers.php');
									$ggu = new GetGroupUsers();
									$ggu->getAllUsersInGroup($groupId,true);	//crmv@46552

									$users = $ggu->group_users;
									$parts = Array();
									foreach($users as $userId){
										$refId = vtws_getWebserviceEntityId("Users", $userId);
										$entity = $this->cache->forId($refId);
										$data = $entity->getData();
										if($this->useValue($data, $fieldname)){
											$parts[] = $data[$fieldname];
										}
									}
									$result = implode(",", $parts);
								//crmv@24350
								}elseif($entity->getModuleName()===$related){
								//crmv@24350e
									$data = $entity->getData();
									if($this->useValue($data, $fieldname)){
										$result = $data[$fieldname];
									}else{
										$result = '';
									}
								}else{
									$result = '';
								}
							}
	        			}
	        			else{
	        				$result = '';
	        			}
	        		}
	        		else{
	        			$result = '';
	        		}
        		}
        		else{
        			$found_ = false;
        			foreach ($this->field_definition[$module]['related'] as $field_=>$module_arr_){ // crmv@184006
        				foreach ($module_arr_ as $module_=>$arrs_){
        					if ($related == $module_){
        						$found_ = $field_;
        						break;
        					}
        				}
        				if ($found_){
        					break;
        				}
        			}
	        		if (is_array($this->field_definition[$module]['related'][$found_][$related])){ // crmv@184006
	        			$found = false;
	        			foreach ($this->field_definition[$module]['related'][$found_][$related] as $f){ // crmv@184006
	        				if ($f['name'] == $fieldname){ // crmv@184006
	        					$found = $this->field_definition[$module]['master'][$f['id_reference']]['name']; // crmv@184006
	        					if ($found)
	        						break;
	        				}
	        			}
	        			if ($found){
	        				$referenceId = $this->parent->get($found);
	        				if($referenceId==null){
								$result="";
							}else{
								$entity = $this->cache->forId($referenceId);
								if($related==="Users" && $entity->getModuleName()=="Groups"){
									list($groupEntityId, $groupId) = vtws_getIdComponents($referenceId);

									require_once('include/utils/GetGroupUsers.php');
									$ggu = new GetGroupUsers();
									$ggu->getAllUsersInGroup($groupId,true);	//crmv@46552

									$users = $ggu->group_users;
									$parts = Array();
									foreach($users as $userId){
										$refId = vtws_getWebserviceEntityId("Users", $userId);
										$entity = $this->cache->forId($refId);
										$data = $entity->getData();
										if($this->useValue($data, $fieldname)){
											$parts[] = $data[$fieldname];
										}
									}
									$result = implode(",", $parts);
								//crmv@24350
								}elseif($entity->getModuleName()===$related){
								//crmv@24350e
									$data = $entity->getData();
									if($this->useValue($data, $fieldname)){
										$result = $data[$fieldname];
									}else{
										$result = '';
									}
								}else{
									$result = '';
								}
							}
	        			}
	        			else{
	        				$result = '';
	        			}
	        		}
	        		else{
	        			$result = '';
	        		}
        		}
        		//crmv@31358
        	}
        }
        $this->replacements[$match] = $result; // crmv@38592
        return $result;
	}
	
	// crmv@71678
	protected function getUITypeByName($module, $fieldname) {
		global $adb, $table_prefix;
		$result = $adb->pquery("select uitype from {$table_prefix}_field where tabid=? and fieldname=?", array(getTabid($module), $fieldname));
		$uitype = $adb->query_result_no_html($result,0,"uitype");
		return $uitype;
	}
	// crmv@71678e

	protected function useValue($data, $fieldname) {
		return !empty($data[$fieldname]);
	}

	function parseTemplate(){
		return preg_replace_callback('/\$(.*?)\$/', array($this,"matchHandler"), $this->template);
	}

	function getMetaValue($fieldname){
		global $adb, $table_prefix, $site_URL;
		
		switch($fieldname){
			case 'date': return getNewDisplayDate();
			case 'time': return date('h-i-s');
			// crmv@161554
			case 'organization_name':
			case 'organization_address':
			case 'organization_city':
			case 'organization_state':
			case 'organization_country':
			case 'organization_code':
			case 'organization_phone':
			case 'organization_fax':
			case 'organization_website':
			case 'organization_logo':
			case 'organization_crmv_banking':
			case 'organization_crmv_vat_registration_number':
			case 'organization_crmv_rea':
			case 'organization_crmv_issued_capital':
				
				$fieldData = '';
				
				$infoFields = getCompanyTemplateEmailFields($fieldname);
				$realField = $infoFields['realfield'];
				
				$companyDetailsResult = $adb->limitQuery("SELECT * FROM {$table_prefix}_organizationdetails", 0, 1);
				if ($companyDetailsResult && $adb->num_rows($companyDetailsResult)) {
					$companyDetails = $adb->fetchByAssoc($companyDetailsResult, -1, false);
					$fieldData = $companyDetails[$realField];
				}
				
				if (!empty($fieldData) && $fieldname === 'organization_logo') {
					$fieldData = $site_URL.'/storage/logo/'.$fieldData;
				}

				return $fieldData;
			
			// crmv@161554e
			default: '';
		}
	}

	// crmv@38592
	function getReplacements() {
		return $this->replacements;
	}
	// crmv@38592e

	//crmv@22700
	function getNewsletterValue($fieldname) {
		$moduleName = $this->parent->getModuleName();
		//crmv@181281
		$focusNewsletter = CRMEntity::getInstance('Newsletter');
		if (!array_key_exists($moduleName,$focusNewsletter->email_fields)) {
			return '';
		}
		//crmv@181281e
		$infoFields = getNewsletterTemplateEmailInfoFields($fieldname);
		$realfield = $infoFields['realfield'][$moduleName];
		$data = $this->parent->getData();
		return $data[$realfield];
	}
	function getTrackLink($fieldname) {
		//crmv@2285m
		if(isModuleInstalled('Fairs')) {
			switch($fieldname){
				case 'unsubscription_data_processing':
					$focus = CRMEntity::getInstance('Newsletter');
					return "<a href='$focus->url_unsubscription_data_processing_file'>".getTranslatedString('LBL_HERE')."</a>";
				case 'unsubscription_fair_comunications':
					$focus = CRMEntity::getInstance('Newsletter');
					return "<a href='$focus->url_unsubscription_fair_comunications_file'>".getTranslatedString('LBL_HERE')."</a>";
				case 'unsubscription_third_party_comunications':
					$focus = CRMEntity::getInstance('Newsletter');
					return "<a href='$focus->url_unsubscription_third_party_comunications_file'>".getTranslatedString('LBL_HERE')."</a>";
				case 'unsubscription_fair':
					$focus = CRMEntity::getInstance('Newsletter');
					return "<a href='$focus->url_unsubscription_fair_file'>".getTranslatedString('LBL_HERE')."</a>";
			}
		} // crmv@146622 - removed else
		//crmv@2285me

		switch($fieldname){
			case 'unsubscription':
				$focus = CRMEntity::getInstance('Newsletter');
				return "<a href='$focus->url_unsubscription_file'>".getTranslatedString('LBL_HERE')."</a>";
		}

	}
	//crmv@22700e
}
//crmv@15309 end
?>