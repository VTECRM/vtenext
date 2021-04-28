<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@47905bis crmv@171009 */

include_once('modules/SDK/LangUtils.php');
include_once('modules/SDK/SDKUtils.php');

class SDK extends SDKUtils {

	protected static $sdk_session_keys = array('sdk_uitype', 'sdk_utils', 'sdk_popup_return_funct', 'sdk_smarty', 'sdk_presave', 'sdk_popup_query', 'sdk_adv_query', 'sdk_adv_permission', 'sdk_class_all', 'sdk_class', 'sdk_class_parent', 'sdk_view', 'sdk_file', 'sdk_home_iframe', 'sdk_reportfolders', 'sdk_reports', 'sdk_js_lang', 'sdk_transitions', 'vte_languages', 'sdk_pdf_cfunctions');	//crmv@2539m crmv@208472
	protected static $other_session_keys = array('installed_modules');
	public static $cacheFolder = 'cache/sys/SDK/';

	/**
	* Invoked when special actions are performed on the module.
	* @param String Module name
	* @param String Event Type
	*/
 	function vtlib_handler($moduleName, $eventType) {
		global $adb,$table_prefix;

		if($eventType == 'module.postinstall') {

 			require_once('modules/SDK/InstallTables.php');

 			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($moduleName));

 			$moduleInstance = Vtecrm_Module::getInstance('SDK');
			Vtecrm_Link::addLink($moduleInstance->id,'HEADERSCRIPT','SDKScript','modules/SDK/SDK.js');
			self::setUtil('modules/SDK/LangUtils.php');
			self::setUtil('modules/SDK/src/Utils.php');
			self::setUtil('modules/SDK/src/Favorites/Utils.php');

			$langinfo = vtlib_getToggleLanguageInfo();
			$languages = array_keys($langinfo);
 			if (empty($languages)) {
				$languages = array('en_us','it_it');
			}
			foreach ($languages as $language){
				self::importPhpLanguage($language);
				//l'import della lingua js viene fatto in Header.tpl
			}

			$adb->pquery('DELETE FROM '.$table_prefix.'_profile2tab WHERE tabid = ?',array($moduleInstance->id));
			$adb->pquery('DELETE FROM '.$table_prefix.'_profile2standardperm WHERE tabid = ?',array($moduleInstance->id));
			$adb->pquery('DELETE FROM '.$table_prefix.'_profile2utility WHERE tabid = ?',array($moduleInstance->id));
			$adb->pquery('DELETE FROM '.$table_prefix.'_profile2field WHERE tabid = ?',array($moduleInstance->id));

			$moduleInstance->hide(array('hide_module_manager'=>1,'hide_profile'=>0,'hide_report'=>1));
			
			self::setUitype('201','modules/SDK/src/201/201.php','modules/SDK/src/201/201.tpl','modules/SDK/src/201/201.js');	//crmv@26523
			self::setUitype('202','modules/SDK/src/202/202.php','modules/SDK/src/202/202.tpl','modules/SDK/src/202/202.js');	//crmv@26809
			self::setUitype('203','modules/SDK/src/203/203.php','modules/SDK/src/203/203.tpl','modules/SDK/src/203/203.js');	//crmv@26809

			self::setMenuButton('fixed','LBL_FAVORITES',"showFloatingDiv('favorites',this);getFavoriteList();",'grade');	//crmv@26986
			
			//crmv@56233
			SDK::setUitype(204,'modules/SDK/src/204/204.php','modules/SDK/src/204/204.tpl','');
			
			$moduleInstance = Vtecrm_Module::getInstance('HelpDesk');
			Vtecrm_Link::addLink($moduleInstance->id, 'DETAILVIEWBASIC', 'LBL_DO_NOT_IMPORT_ANYMORE', "javascript:doNotImportAnymore('\$MODULE\$',\$RECORD\$,'DetailView');", 'vteicon:whatshot',0,'checkMailScannerInfoRule:include/utils/crmv_utils.php');
			Vtecrm_Link::addLink($moduleInstance->id, 'LISTVIEWBASIC', 'LBL_DO_NOT_IMPORT_ANYMORE', "javascript:doNotImportAnymore('\$MODULE\$','','MassListView');",'',0,'checkMailScannerInfoRule:include/utils/crmv_utils.php');
			//crmv@56233e

    		//crmv@29079
    		self::setUitype(205,'modules/SDK/src/205/205.php','modules/SDK/src/205/205.tpl','');

    		//crmv@30014
    		self::setUitype(206, 'modules/SDK/src/206/206.php', 'modules/SDK/src/206/206.tpl', 'modules/SDK/src/206/206.js', 'integer');

    		if (Vtecrm_Utils::CheckTable($table_prefix.'_home_iframe')) {
    			$adb->pquery('insert into '.$table_prefix.'_home_iframe (hometype,url) values (?,?)',array('MODCOMMENTS','index.php?module=ModComments&action=ModCommentsAjax&file=ModCommentsWidgetHandler&ajax=true&widget=DetailViewBlockCommentWidget'));
    		}

    		$homeModule = Vtecrm_Module::getInstance('SDK');
			$homeModule->addLink('HEADERSCRIPT', 'NotificationsScript', 'modules/SDK/src/Notifications/NotificationsCommon.js');
			$homeModule->addLink('HEADERCSS', 'NotificationsScript', 'modules/SDK/src/Notifications/NotificationsCommon.css');
			//crmv@29079e

			self::addView('Users', 'modules/SDK/src/modules/Users/UsersView.php', 'constrain', 'continue');	//crmv@29506

			$result = $adb->pquery("select * from {$table_prefix}_field where tabid = 13 and fieldname in (?,?)",array('projecttaskid','projectplanid'));
			if ($result && $adb->num_rows($result) == 2) {
				self::setPopupQuery('field', 'HelpDesk', 'projecttaskid', 'modules/SDK/src/modules/HelpDesk/ProjectTaskQuery.php', array('projectplanid'=>'getObj("projectplanid").value'));
			}

			SDK::setUitype(207,'modules/SDK/src/207/207.php','modules/SDK/src/207/207.tpl','modules/SDK/src/207/207.js');

			//crmv@2539m
			SDK::setPDFCustomFunction('if-else','its4you_if',array('param1','comparator','param2','return1','return2'));
			SDK::setPDFCustomFunction('Contact Image','its4you_getContactImage',array('contactid','width','height'));
			SDK::setPDFCustomFunction('Net Prices Total','getTotalNetPrice',array('$CRMID$'));
			SDK::setPDFCustomFunction('Discount Prices Total','getTotalDiscountPrice','$CRMID$');
			//crmv@2539me

			SDK::setUitype(210, 'modules/SDK/src/210/210.php', 'modules/SDK/src/210/210.tpl', 'modules/SDK/src/210/210.js', 'text');
			// crmv@95157
			SDK::setUitype(212, 'modules/SDK/src/212/212.php', 'modules/SDK/src/212/212.tpl', 'modules/SDK/src/212/212.js', 'picklist');
			require_once('modules/Documents/storage/StorageBackendUtils.php');
			$SBU = StorageBackendUtils::getInstance();
			$SBU->syncDB();
			// crmv@95157e
			SDK::setUitype(213, 'modules/SDK/src/213/213.php', 'modules/SDK/src/213/213.tpl', '');
			SDK::setUitype(214, 'modules/SDK/src/214/214.php', 'modules/SDK/src/214/214.tpl', '', 'datetime'); // crmv@101930
			
			SDK::setUitype(215,'modules/SDK/src/215/215.php','modules/SDK/src/215/215.tpl','modules/SDK/src/215/215.js'); // crmv@150808

			SDK::setUitype(220, 'modules/SDK/src/220/220.php', 'modules/SDK/src/220/220.tpl', 'modules/SDK/src/220/220.js', 'table'); // crmv@102879
			SDK::setUitype(221, 'modules/SDK/src/221/221.php', 'modules/SDK/src/221/221.tpl', 'modules/SDK/src/221/221.js', 'string'); //crmv@174986
			
			// crmv@201442
			SDK::setUitype(310, 'modules/SDK/src/310/310.php','modules/SDK/src/310/310.tpl', '', 'picklist');
			SDK::setUitype(311, 'modules/SDK/src/311/311.php','modules/SDK/src/311/311.tpl', '', 'multipicklist');
			// crmv@201442e

			// migrate old reference uitypes to uitype 10 - i
			$acc = 'modules/SDK/src/ReturnFunct/ReturnAccountAddress.php';
			$cont = 'modules/SDK/src/ReturnFunct/ReturnContactAddress.php';
			$pot = 'modules/SDK/src/ReturnFunct/ReturnPotentialAddress.php';
			$prod = 'modules/SDK/src/ReturnFunct/ReturnProductLines.php';
			$vend = 'modules/SDK/src/ReturnFunct/ReturnVendorAddress.php';
			$user = 'modules/SDK/src/ReturnFunct/ReturnUserLastname.php';
			SDK::setPopupReturnFunction('Accounts', 'account_id', $acc);
			SDK::setPopupReturnFunction('Contacts', 'account_id', $acc);
			SDK::setPopupReturnFunction('Contacts', 'contact_id', $cont);
			SDK::setPopupReturnFunction('Calendar', 'contact_id', $cont);
			SDK::setPopupReturnFunction('Events', 'contact_id', $cont);
			SDK::setPopupReturnFunction('Quotes', 'contact_id', $cont);
			SDK::setPopupReturnFunction('PurchaseOrder', 'contact_id', $cont);
			SDK::setPopupReturnFunction('SalesOrder', 'contact_id', $cont);
			SDK::setPopupReturnFunction('Invoice', 'contact_id', $cont);
			SDK::setPopupReturnFunction('Quotes', 'account_id', $acc);
			SDK::setPopupReturnFunction('SalesOrder', 'account_id', $acc);
			SDK::setPopupReturnFunction('Invoice', 'account_id', $acc);
			SDK::setPopupReturnFunction('Quotes', 'potential_id', $pot);
			SDK::setPopupReturnFunction('SalesOrder', 'potential_id', $pot);
			SDK::setPopupReturnFunction('SalesOrder', 'quote_id', $prod);
			SDK::setPopupReturnFunction('Invoice', 'salesorder_id', $prod);
			SDK::setPopupReturnFunction('PurchaseOrder', 'vendor_id', $vend);
			SDK::setPopupReturnFunction('Users', 'reports_to_id', $user);
			SDK::setPopupReturnFunction('Timecards', 'newresp', $user);
			SDK::setPopupReturnFunction('Projects', 'reports_to_id', $user);
			$popup_query_file = 'modules/SDK/src/PopupQuery/ExcludeCurrentUser.php';
			SDK::setPopupQuery('field', 'Users', 'reports_to_id', $popup_query_file);
			SDK::setPopupQuery('field', 'Timecards', 'newresp', $popup_query_file);
			SDK::setPopupQuery('field', 'Projects', 'reports_to_id', $popup_query_file);
			// migrate old reference uitypes to uitype 10 - e

			//crmv@3078m
			$result = $adb->query("SELECT * FROM sdk_menu_fixed WHERE title = 'Events'");
			if ($result) {
				if ($adb->num_rows($result)>0){
					$adb->query("delete FROM sdk_menu_fixed WHERE title = 'Events'");
				}
				SDK::setMenuButton('fixed','Events',"showFloatingDiv('events',this);getEventList(this);",'event');
			}
			$homeModule->addLink('HEADERSCRIPT','EventUtils','modules/SDK/src/Events/js/Utils.js');
			//crmv@3078me

			// crmv@44323 - sharkpanel report :(
			SDK::setReportFolder('Budget', '');
			SDK::setReport('Budget by Product Line', '', 'Budget', 'modules/Potentials/BudgetReportRun.php', 'BudgetReportRun', 'budgetParams');
			
			$result = $adb->pquery("SELECT {$table_prefix}_report.reportid, {$table_prefix}_report.folderid
									FROM sdk_reports
									INNER JOIN {$table_prefix}_report ON sdk_reports.reportid = {$table_prefix}_report.reportid
									WHERE runclass = ?", array('BudgetReportRun'));
			if ($result && $adb->num_rows($result) > 0) {
				$sharkReportId = $adb->query_result($result,0,'reportid');
				$sharkReportFolder = $adb->query_result($result,0,'folderid');
				SDK::setMenuButton('contestual', 'Budget', "window.location='index.php?module=Reports&action=SaveAndRun&record={$sharkReportId}&folderid={$sharkReportFolder}';", 'euro_symbol', 'Potentials', 'index');
			}
			// crmv@44323e
			
			//crmv@52306	crmv@54900
			$result = $adb->pquery("SELECT relation_id, name FROM {$table_prefix}_relatedlists WHERE tabid = 14 AND related_tabid = 14 AND label = ?",array('Product Bundles'));
			if ($result && $adb->num_rows($result) > 0) {
				$relation_id = $adb->query_result($result,0,'relation_id');
				$method = $adb->query_result($result,0,'name');
				SDK::setTurboliftCount($relation_id, $method);
			}
			$result = $adb->query("SELECT relation_id, name FROM {$table_prefix}_relatedlists WHERE tabid = 14 AND related_tabid = 14 AND label LIKE 'Parent Product%'");
			if ($result && $adb->num_rows($result) > 0) {
				$relation_id = $adb->query_result($result,0,'relation_id');
				$method = $adb->query_result($result,0,'name');
				SDK::setTurboliftCount($relation_id, $method);
			}
			$result = $adb->query("SELECT relation_id, name FROM {$table_prefix}_relatedlists WHERE tabid = 7 AND related_tabid = 26");
			if ($result && $adb->num_rows($result) > 0) {
				$relation_id = $adb->query_result($result,0,'relation_id');
				$method = $adb->query_result($result,0,'name');
				SDK::setTurboliftCount($relation_id, $method);
			}
			//crmv@52306e	crmv@54900e
			// crmv@142678
			$campInst = Vtecrm_Module::getInstance('Campaigns');
			$result = $adb->pquery("SELECT relation_id, name FROM {$table_prefix}_relatedlists WHERE tabid = ? AND related_tabid = ? AND name LIKE 'get_statistics_%'", array($campInst->id, 0));
			if ($result) {
				while ($row = $adb->fetchByAssoc($result, -1, false)) {
					SDK::setTurboliftCount($row['relation_id'], $row['name']);
				}
			}
			// crmv@142678e
			
			//crmv@62414
			$documents_instance = Vtecrm_Module::getInstance('Documents');
			Vtecrm_Link::addLink($documents_instance->id, 'DETAILVIEWWIDGET', 'DOC_PREVIEW', "module=Documents&action=DocumentsAjax&file=PreviewFile&mode=button&record=$"."RECORD$");
			//crmv@62414e
			
			//crmv@64516
			$configPBFile = 'modules/Campaigns/ProcessBounces.config.php';
			if (!file_exists($configPBFile)) {
				$configPBTemplate = file_get_contents('modules/Campaigns/ProcessBounces.config.template.php');
				file_put_contents($configPBFile, $configPBTemplate);
			}
			//crmv@64516e
			
			//crmv@OPER5904
			$moduleInstance = Vtecrm_Module::getInstance('SDK');
			Vtecrm_Link::addLink($moduleInstance->id,'HEADERSCRIPT','VTELocalStorageScript','modules/SDK/src/VTELocalStorage.js');
			//crmv@OPER5904e

			// crmv@OPER6317
			Vtecrm_Link::addLink($moduleInstance->id, 'HEADERSCRIPT', 'Wizard', 'include/js/Wizard.js');
			// crmv@OPER6317e
			
			/* TODO@bosch
			SDK::setUitype(213, 'modules/SDK/src/213/213.php', 'modules/SDK/src/213/213.tpl', '');
			*/
			
			SDK::setUitype(49,'modules/SDK/src/49/49.php','modules/SDK/src/49/49.tpl','modules/SDK/src/49/49.js'); // crmv@187823
			
			//crmv@101683
			$uitype = 51;
			SDK::setUitype($uitype,"modules/SDK/src/$uitype/$uitype.php","modules/SDK/src/$uitype/$uitype.tpl","modules/SDK/src/$uitype/$uitype.js",'reference');
			$result = $adb->pquery("select fieldtypeid from {$table_prefix}_ws_fieldtype where uitype=?", array($uitype));
			if ($result && $adb->num_rows($result) > 0) {
				$check = $adb->pquery("select fieldtypeid from {$table_prefix}_ws_referencetype where fieldtypeid=?", array($adb->query_result($result,0,'fieldtypeid')));
				if ($check && $adb->num_rows($check) == 0) {
					if ($adb->isMysql()) {
						$adb->pquery("insert ignore into {$table_prefix}_ws_referencetype(fieldtypeid,type) values(?,?)",array($adb->query_result($result,0,'fieldtypeid'),'Users'));
					} else {
						$adb->pquery("insert into {$table_prefix}_ws_referencetype(fieldtypeid,type) values(?,?)",array($adb->query_result($result,0,'fieldtypeid'),'Users'));
					}
				}
			}
			$uitype = 50;
			SDK::setUitype($uitype,"modules/SDK/src/$uitype/$uitype.php","modules/SDK/src/$uitype/$uitype.tpl","modules/SDK/src/$uitype/$uitype.js",'reference');
			$result = $adb->pquery("select fieldtypeid from {$table_prefix}_ws_fieldtype where uitype=?", array($uitype));
			if ($result && $adb->num_rows($result) > 0) {
				$check = $adb->pquery("select fieldtypeid from {$table_prefix}_ws_referencetype where fieldtypeid=?", array($adb->query_result($result,0,'fieldtypeid')));
				if ($check && $adb->num_rows($check) == 0) {
					if ($adb->isMysql()) {
						$adb->pquery("insert ignore into {$table_prefix}_ws_referencetype(fieldtypeid,type) values(?,?)",array($adb->query_result($result,0,'fieldtypeid'),'Users'));
					} else {
						$adb->pquery("insert into {$table_prefix}_ws_referencetype(fieldtypeid,type) values(?,?)",array($adb->query_result($result,0,'fieldtypeid'),'Users'));
					}
				}
			}
			//crmv@101683e
			
			SDK::setProcessMakerFieldAction('vte_sum','modules/SDK/src/ProcessMaker/Utils.php','Sum (number1,number2,...)');
			SDK::setProcessMakerFieldAction("date_now", "modules/SDK/src/ProcessMaker/Utils.php", "Now Date (format)", '', 'LBL_PM_SDK_DATE_FUNCTIONS');
			SDK::setProcessMakerFieldAction("formatDate", "modules/SDK/src/ProcessMaker/Utils.php", "Format Date/Time (date, format)", '', 'LBL_PM_SDK_DATE_FUNCTIONS');
			SDK::setProcessMakerFieldAction("diffDate", "modules/SDK/src/ProcessMaker/Utils.php", "DiffDays (date1, date2)", '', 'LBL_PM_SDK_DATE_FUNCTIONS');
			SDK::setProcessMakerTaskCondition('get_running_process_current_user', 'modules/SDK/src/ProcessMaker/Utils.php', 'LBL_RUNNING_PROCESS_CURRENT_USER');
			
			$homeModule->addLink('HEADERSCRIPT', 'HistoryScript', 'include/js/HistoryTab.js');	//crmv@104566
			
			SDK::setUitype(1016, 'modules/SDK/src/1016/1016.php', 'modules/SDK/src/1016/1016.tpl', 'modules/SDK/src/1016/1016.js', 'signature'); // crmv@104567
			
			SDK::setUitype(29,'modules/SDK/src/29/29.php','modules/SDK/src/29/29.tpl','','file');	//crmv@115268
			SDK::setUitype(73, 'modules/SDK/src/73/73.php', 'modules/SDK/src/73/73.tpl', '', 'time');	//crmv@128159
			
			SDK::addView('Events', 'modules/SDK/src/modules/Calendar/EventsView.php', 'constrain', 'continue'); //crmv@158871
			
			SDK::setUitype(209,'modules/SDK/src/209/209.php','modules/SDK/src/209/209.tpl','','url');
			SDK::setUitype(86,'modules/SDK/src/86/86.php','modules/SDK/src/86/86.tpl','modules/SDK/src/86/86.js','whatsapp');
			
			//crmv@174813
			$uitype = 54;
			$result = $adb->pquery("select fieldtypeid from {$table_prefix}_ws_fieldtype where uitype=?", array($uitype));
			if ($result && $adb->num_rows($result) == 0) {
				$fieldtypeid = $adb->getUniqueID($table_prefix."_ws_fieldtype");
				$adb->pquery("insert into {$table_prefix}_ws_fieldtype(fieldtypeid,uitype,fieldtype) values(?,?,?)",array($fieldtypeid,$uitype,'reference'));
				$adb->pquery("insert into {$table_prefix}_ws_referencetype(fieldtypeid,type) values(?,?)",array($fieldtypeid,'Groups'));
			}
			//crmv@174813e

		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($eventType == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($eventType == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($eventType == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
 	}

	static function log($message, $delimit=true) {
		Vtecrm_Utils::Log($message, $delimit);
	}

	static function getSessionKeys($add_other_session_keys=false) {
		return SDK::$sdk_session_keys;
	}
	
	static function clearSessionValue($key) {
		global $table_prefix;
		$dependentSessions = array(
			'sdk_uitype' => array('sdk_js_uitype'),
			'sdk_home_iframe' => array('sdk_home_global_iframe','sdk_home_default_iframes_plain','sdk_home_default_iframes_complex','sdk_home_global_iframes','sdk_home_fixed_iframes'),
			'sdk_home_global_iframe' => array('sdk_home_iframe','sdk_home_default_iframes_plain','sdk_home_default_iframes_complex','sdk_home_global_iframes','sdk_home_fixed_iframes'),
			'sdk_home_default_iframes_plain' => array('sdk_home_iframe','sdk_home_global_iframe','sdk_home_default_iframes_complex','sdk_home_global_iframes','sdk_home_fixed_iframes'),
			'sdk_home_default_iframes_complex' => array('sdk_home_iframe','sdk_home_global_iframe','sdk_home_default_iframes_plain','sdk_home_global_iframes','sdk_home_fixed_iframes'),
			'sdk_home_global_iframes' => array('sdk_home_iframe','sdk_home_global_iframe','sdk_home_default_iframes_plain','sdk_home_default_iframes_complex','sdk_home_fixed_iframes'),
			'sdk_home_fixed_iframes' => array('sdk_home_iframe','sdk_home_global_iframe','sdk_home_default_iframes_plain','sdk_home_default_iframes_complex','sdk_home_global_iframes'),
		);
		$cache = Cache::getInstance($key,null,self::getCacheFolder());
		$cache->clear();
		if (in_array($key,array_keys($dependentSessions)) && !empty($dependentSessions[$key])) {
			foreach($dependentSessions[$key] as $dependentSession) {
				$cache = Cache::getInstance($dependentSession,null,self::getCacheFolder());
				$cache->clear();
			}
		}
	}

	static function clearSessionValues() {
		$keys = self::getSessionKeys(true);
		foreach ($keys as $k) {
			self::clearSessionValue($k);
		}
		foreach (SDK::$other_session_keys as $other_session_key) {
			$cache = Cache::getInstance($other_session_key);
			$cache->clear();
		}
	}
	
	/**
 	 * Updates (or create a new one if it doesn't exist) an entry in the language table
 	 */
	static function setLanguageEntry($module, $langid, $label, $newlabel) {
 		global $adb;

 		$languages = vtlib_getToggleLanguageInfo();
		if (!array_key_exists($langid,$languages)) {
			self::log("Adding SDK Language Entry ($module $langid $label) ... FAILED: language $langid not installed");
			return;
		}

 		// delete old row
 		self::deleteLanguageEntry($module, $langid, $label);

 		// insert new
 		$newid = $adb->getUniqueID("sdk_language");
 		$qparam = array($newid, $module, $langid, correctEncoding(html_entity_decode($label)), correctEncoding(html_entity_decode($newlabel)));
 		$query = 'insert into sdk_language (languageid, module, language, label, trans_label) values ('.generateQuestionMarks($qparam).')';
 		$res = $adb->pquery($query, $qparam);
 		self::log("Adding SDK Language Entry ($module $langid $label) ... DONE");
		if ($module == 'ALERT_ARR') {
 			self::clearSessionValue('sdk_js_lang');
 		} else {
 			self::clearSessionValue("SDK/vte_languages/{$module}-{$langid}"); // crmv@187020
 		}
 	}

 	/**
 	 * Same as previous, but accepts multiple languages
 	 */
 	static function setLanguageEntries($module, $label, $strings) {
 		foreach ($strings as $langid=>$newlabel) {
 			self::setLanguageEntry($module, $langid, $label, $newlabel);
 		}
 	}

 	/**
 	 * Deletes a string in the language table
 	 */
 	static function deleteLanguageEntry($module, $langid = NULL, $label = NULL) {
 		global $adb;
 	 	$query = 'delete from sdk_language where module = ?';
 		$qpar = array($module);
 		if (!empty($langid)) {
 			$query .= ' and language = ?';
 			$qpar[] = $langid;
 		}
 		if (!empty($label)) {
 			($adb->isMysql()) ? $query .= ' and binary label like ?' : $query .= ' and label like ?';
 			$qpar[] = $label;
 		}
 		$res = $adb->pquery($query, $qpar);
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Deleting SDK Language Entry ($module $langid) ... DONE");
 		} else {
 			//self::log("Deleting SDK Language Entry ($module $langid) ... FAILED");
 		}
 		if ($module == 'ALERT_ARR') {
 			self::clearSessionValue('sdk_js_lang');
 		} else {
 			self::clearSessionValue("SDK/vte_languages/{$module}-{$langid}"); // crmv@187020
 		}
 	}
	
	static function getUitypes() {
 		global $adb;
 		$cache = Cache::getInstance('sdk_uitype',null,self::getCacheFolder());
 		$tmp = $cache->get();
 		if ($tmp === false) {
	 		$tmp = array();
	 		$result = $adb->query('select * from sdk_uitype');
	 		if ($result && $adb->num_rows($result)>0) {
	 			while($row=$adb->fetchByAssoc($result)) {
	 				$tmp[$row['uitype']] = $row;
	 			}
	 		}
 			$cache->set($tmp);
 		}
 		return $tmp;
 	}

 	static function isUitype($uitype) {
 		$uitypes = self::getUitypes();
 		if (in_array($uitype,array_keys($uitypes))) {
 			return true;
 		}
 		return false;
 	}

 	static function isOldUitype($uitype) {
 		$uitypes = self::getUitypes();
 		if (!empty($uitypes[$uitype]['old_style'])) {
 			return true;
 		}
 		return false;
 	}

 	static function getUitypeInfo($uitype) {
 		$uitypes = self::getUitypes();
 		return $uitypes[$uitype];
 	}

	static function getUitypeFile($src,$mode,$uitype) {
 		global $sdk_mode;
 		$sdk_mode = $mode;
 		$info = self::getUitypeInfo($uitype);
		$checkFileAccess = $info['src_'.$src];
		if ($src == 'tpl') {
			$checkFileAccess = "Smarty/templates/$checkFileAccess";
		}
 		if ($info['src_'.$src] != '' && Vtecrm_Utils::checkFileAccess($checkFileAccess,false)) {
 			return $info['src_'.$src];
 		}
 	}

	static function getJsUitypes() {
 		global $adb;
 		$cache = Cache::getInstance('sdk_js_uitype',null,self::getCacheFolder());
		$tmp = $cache->get();
 		if ($tmp === false) {
	 		$tmp = array();
	 		$result = $adb->query("SELECT uitype,src_js FROM sdk_uitype WHERE src_js <> '' OR src_js IS NOT NULL");
	 		if ($result && $adb->num_rows($result)>0) {
	 			while($row=$adb->fetchByAssoc($result)) {
	 				$tmp[$row['uitype']] = $row['src_js'];
	 			}
	 		}
	 		$tmp = Zend_Json::encode($tmp);
	 		$cache->set($tmp);
 		}
 		return $tmp;
 	}

 	/**
 	 * Register new Uitype
 	 * $uitype	: numeric value
 	 * $src_php	: path of the php file source code
 	 * $src_tpl	: path of the tpl file source code
 	 * $src_js	: path of the js file source code
 	 * $type	: webservice format (ex. text, boolean, datetime, reference, ...)
 	 * $params	: array width other params (ex. modules per reference field)
 	 */
 	static function setUitype($uitype,$src_php,$src_tpl,$src_js,$type='',$params='') {
 		global $adb,$table_prefix;
 		$result = $adb->query('select * from sdk_uitype where uitype = '.$uitype);
 		if ($result && $adb->num_rows($result)>0) {
 			self::log("Adding SDK Uitype ($uitype) ... FAILED ($uitype already exists!)");
 			return;
 		}
 		$uitypeid = $adb->getUniqueID("sdk_uitype");
 		$params = array($uitypeid,$uitype,$src_php,$src_tpl,$src_js);
 		$adb->pquery('insert into sdk_uitype (uitypeid,uitype,src_php,src_tpl,src_js) values ('.generateQuestionMarks($params).')',array($params));
 		if ($type != '') {
 			$fieldtypeid = $adb->getUniqueId($table_prefix.'_ws_fieldtype');
			$result = $adb->pquery("insert into ".$table_prefix."_ws_fieldtype(fieldtypeid,uitype,fieldtype) values(?,?,?)",array($fieldtypeid,$uitype,$type));
			if ($type == 'reference') {
				//TODO : insert into vte_ws_referencetype
				self::log("<b>TODO</b> : insert into vte_ws_referencetype");
			}
 		}
 		self::log("Adding SDK Uitype ($uitype) ... DONE");
 		// put it in the current session
 		self::clearSessionValue('sdk_uitype');

 		//TODO@old_style
 		$columns = array_keys($adb->datadict->MetaColumns('sdk_uitype'));
 		if (in_array(strtoupper('old_style'),$columns) && in_array($uitype,array(170,171,172,173,174,175,176,177,206,1115))) { // crmv@70304 crmv@80653
 			$adb->pquery('update sdk_uitype set old_style = 1 where uitypeid = ?',array($uitypeid));
 		}
 	}

 	/**
 	 * Unregister a uitype
 	 * $uitype : the uitype to be unregistered. its files won't be deleted
 	 * TODO: cancellare da tabelle vte_ws*
 	 */
 	static function unsetUitype($uitype) {
 		global $adb,$table_prefix;
 		$res = $adb->pquery('delete from sdk_uitype where uitype = ?',array($uitype));
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			$adb->pquery('delete from '.$table_prefix.'_ws_fieldtype where uitype = ?',array($uitype));
 			self::log("Deleting SDK Uitype ($uitype) ... DONE");
 			self::clearSessionValue('sdk_uitype');
 		} else {
 			self::log("Deleting SDK Uitype ($uitype) ... FAILED");
 		}
 	}

	static function getUtilsList() {
 		global $adb;
 		if (empty($adb->database) || !$adb->table_exist('sdk_utils') || !isModuleInstalled('SDK')) {
 			return;
 		}
 		$cache = Cache::getInstance('sdk_utils',null,self::getCacheFolder());
		$tmp = $cache->get();
 		if ($tmp === false) {
	 		$tmp = array();
	 		$result = $adb->query('select * from sdk_utils');
	 		if ($result && $adb->num_rows($result)>0) {
	 			while($row=$adb->fetchByAssoc($result)) {
	 				$tmp[$row['utilid']] = $row['src'];
	 			}
	 		}
	 		$cache->set($tmp);
 		}
 		return $tmp;
 	}

 	static function getUtils() {
 		$sdk_utils = self::getUtilsList();
 		if (!empty($sdk_utils)) {
	 		foreach ($sdk_utils as $sdk_util) {
		 		if ($sdk_util != '' && Vtecrm_Utils::checkFileAccess($sdk_util,false)) {
		 			require_once($sdk_util);
		 		}
	 		}
 		}
 	}

 	/**
 	 * Register new Util
 	 * $src	: path of the php file source code
 	 * Note: there is no control if the same file is included twice
 	 */
	static function setUtil($src) {
		global $adb;
		if ($src == '') {
			self::log("Adding SDK Util ($src) ... FAILED (src empty!)");
			return;
		}
		// check if it already exists
		$utils = self::getUtilsList();
		if (!empty($utils) && in_array($src, array_values($utils))) {
			self::log("Adding SDK Util ($src) ... FAILED (File already in utils list)");
			return;
		}
		$utilid = $adb->getUniqueID("sdk_utils");
		$params = array($utilid,$src);
		$adb->pquery('insert into sdk_utils (utilid,src) values ('.generateQuestionMarks($params).')',array($params));
		self::log("Adding SDK Util ($src) ... DONE");
		self::clearSessionValue('sdk_utils');
	}

 	/**
 	 * Delete a registered util
 	 * $src: path to the php file to be unregistered (the file itself won't be deleted)
 	 */
 	static function unsetUtil($src) {
 		global $adb;
 		$res = $adb->pquery('delete from sdk_utils where src = ?',array($src));
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Deleting SDK Util ($src) ... DONE");
 			self::clearSessionValue('sdk_utils');
 		} else {
 			self::log("Deleting SDK Util ($src) ... FAILED");
 		}
 	}

	static function getPopupReturnFunctions() {
 		global $adb;
 		$cache = Cache::getInstance('sdk_popup_return_funct',null,self::getCacheFolder());
		$tmp = $cache->get();
 		if ($tmp === false) {
	 		$tmp = array();
	 		$result = $adb->query('select * from sdk_popup_return_funct');
	 		if ($result && $adb->num_rows($result)>0) {
	 			while($row=$adb->fetchByAssoc($result)) {
	 				$tmp[$row['id']] = array('module'=>$row['module'],'fieldname'=>$row['fieldname'],'src'=>$row['src']);
	 			}
	 		}
	 		$cache->set($tmp);
 		}
 		return $tmp;
 	}

 	static function isPopupReturnFunction($module,$fieldname) {
 		if ($module != '' && $fieldname != '') {
	 		$popupReturnFunctions = self::getPopupReturnFunctions();
	 		foreach($popupReturnFunctions as $id => $info) {
				if ($module == $info['module'] && $fieldname == $info['fieldname']) {
					return true;
				}
	 		}
	 		//crmv@131239 check for table fields
	 		if (substr_count($fieldname,'_') == 2) {
	 			list($t,$f,$o) = explode('_',$fieldname);
	 			return self::isPopupReturnFunction($module,"{$t}_{$f}");
	 		}
	 		//crmv@131239e
 		}
 		return false;
 	}

 	static function getPopupReturnFunctionFile($module,$fieldname) {
 		$popupReturnFunctions = self::getPopupReturnFunctions();
 		foreach($popupReturnFunctions as $id => $info) {
			if ($module == $info['module'] && $fieldname == $info['fieldname']) {
				return $info['src'];
			}
 		}
 		//crmv@131239 check for table fields
 		if (substr_count($fieldname,'_') == 2) {
 			list($t,$f,$o) = explode('_',$fieldname);
 			return self::getPopupReturnFunctionFile($module,"{$t}_{$f}");
 		}
 		//crmv@131239e
 	}

 	static function setPopupReturnFunction($module,$fieldname,$src) {
 		global $adb;
 		if ($module == '' || $fieldname == '' || $src == '') {
 			self::log("Adding SDK Popup Return Function ($module,$fieldname,$src) ... FAILED (empty value)");
 			return;
 		}
 		// check duplicates
 		$file = self::getPopupReturnFunctionFile($module, $fieldname);
 		if (isset($file) && !empty($file)) {
 			self::log("Adding SDK Popup Return Function ($src) ... FAILED (duplicate)");
 			return;
 		}
 		$id = $adb->getUniqueID("sdk_popup_return_funct");
 		$params = array($id,$module,$fieldname,$src);
 		$adb->pquery('insert into sdk_popup_return_funct (id,module,fieldname,src) values ('.generateQuestionMarks($params).')',array($params));
 		self::log("Adding SDK Popup Return Function ($module,$fieldname,$src) ... DONE");
 		self::clearSessionValue('sdk_popup_return_funct');
 	}

 	static function unsetPopupReturnFunction($module, $fieldname = NULL, $src = NULL) {
		global $adb;

 		$query = 'delete from sdk_popup_return_funct where module = ?';
 		$qpar = array($module);
 		if (!empty($fieldname)) {
 			$query .= 'and fieldname = ?';
 			$qpar[] = $fieldname;
 		}
 		if (!empty($src)) {
 			$query .= 'and src = ?';
 			$qpar[] = $src;
 		}
 		$res = $adb->pquery($query, $qpar);
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Deleting SDK Popup Return Function ($src) ... DONE");
 			self::clearSessionValue('sdk_popup_return_funct');
 		} else {
 			self::log("Deleting SDK Popup Return Function ($src) ... FAILED");
 		}
 	}

	static function getSmartyTemplates() {
 		global $adb;
 		$cache = Cache::getInstance('sdk_smarty',null,self::getCacheFolder());
		$tmp = $cache->get();
 		if ($tmp === false) {
	 		$tmp = array();
	 		$result = $adb->query('select * from sdk_smarty');
	 		if ($result && $adb->num_rows($result)>0) {
	 			while($row=$adb->fetchByAssoc($result,-1,false)) {
	 				$tmp[$row['smartyid']] = array('params'=>$row['params'],'src'=>$row['src']);
	 			}
	 		}
	 		$cache->set($tmp);
 		}
 		return $tmp;
 	}

 	static function getSmartyTemplate($request) {
 		$smartyTemplates = self::getSmartyTemplates();
 		$src = array();
 		foreach($smartyTemplates as $smartyTemplate) {
 			$params = Zend_Json::decode($smartyTemplate['params']);
			// controllo se la request matcha con i parametri
			require_once('SDKParams.php');
 			if (SDKParams::paramsMatch($request, $params)) {
 				$src[] = array($smartyTemplate['src'], $params);
 			}
 		}
 		// choose best option (most specific/minimum)
 		if (!empty($src)) {
 			return SDKParams::paramsMin($src);
 		}
 		return '';
 	}

 	/**
 	 * Register a custom template
 	 * Check if the new template params are compatible with the existing ones
 	 */
 	static function setSmartyTemplate($params,$src) {
 		global $adb;

 		// check parameters
 		require_once('SDKParams.php');
 		$plist = self::getSmartyTemplates();
 		foreach ($plist as $k=>$t) $plist[$k] = Zend_Json::decode($t['params']);

 		$compcheck = SDKParams::paramsValidate($plist, $params);

 		if (!empty($compcheck)) {
 			self::log("Adding SDK Smarty Template ($src) ... FAIL");
 			$failstr = '';
 			foreach ($compcheck as $v) {
 				$failstr .= ($v[0]==1)?'Duplicated ':'Incompatible ';
 				$failstr .= "params $v[1]\n";
 			}
 			self::log(nl2br($failstr));
 			return;
 		}

 		$smartyid = $adb->getUniqueID("sdk_smarty");
 		$params = array($smartyid,Zend_Json::encode($params),$src);
 		$adb->pquery('insert into sdk_smarty (smartyid,params,src) values ('.generateQuestionMarks($params).')',array($params));
 		self::log("Adding SDK Smarty Template ($src) ... DONE");
 		self::clearSessionValue('sdk_smarty');
 	}

 	/**
 	 * Delete a registered template
 	 */
 	static function unsetSmartyTemplate($params, $src = NULL) {
 		global $adb;
 		$query = 'delete from sdk_smarty where params = ?';
 		$qpar = array(Zend_Json::encode($params));
 		if (!empty($src)) {
 			$query .= 'and src = ?';
 			$qpar[] = $src;
 		}
 		$res = $adb->pquery($query, $qpar);
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Deleting SDK Smarty Template ($src) ... DONE");
 			self::clearSessionValue('sdk_smarty');
 		} else {
 			self::log("Deleting SDK Smarty Template ($src) ... FAILED");
 		}
 	}

 	static function getNotRewritableSmartyTemplates() {
 		$return = array(
 			'Header.tpl',
 			'modules/ModComments/widgets/DetailViewBlockComment.tpl',
 			'Buttons_List.tpl',
 			'Buttons_List1.tpl',
 			'Buttons_List4.tpl',
 			'Buttons_List_Detail.tpl',
 			'Buttons_List_Edit.tpl',
 			'loginheader.tpl',
 		);
 		return $return;
 	}

	static function setPreSave($module, $src) {
 		global $adb;

 		// check if module already has a presave file
 		$presave = self::getPreSave($module);
 		if (isset($presave) && !empty($presave)) {
 			self::log("Adding SDK PreSave ($module) ... FAILED (PreSave already defined)");
 			return;
 		}

 		$presaveid = $adb->getUniqueID("sdk_presave");
 		$params = array($presaveid,$module,$src);
 		$adb->pquery('insert into sdk_presave (presaveid,module,src) values ('.generateQuestionMarks($params).')',array($params));
 		self::log("Adding SDK PreSave ($src) ... DONE");
 		self::clearSessionValue('sdk_presave');
 	}

 	static function unsetPreSave($module, $src = NULL) {
 		global $adb;

 		$query = 'delete from sdk_presave where module = ?';
 		$qpar = array($module);
 		if (!empty($src)) {
 			$query .= 'and src = ?';
 			$qpar[] = $src;
 		}
 		$res = $adb->pquery($query, $qpar);
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Deleting SDK PreSave ($src) ... DONE");
 			self::clearSessionValue('sdk_presave');
 		} else {
 			self::log("Deleting SDK PreSave ($src) ... FAILED");
 		}
 	}

	static function getPreSaveList() {
 		global $adb;
		$cache = Cache::getInstance('sdk_presave',null,self::getCacheFolder());
		$tmp = $cache->get();
 		if ($tmp === false && $adb->table_exist('sdk_presave')) {
	 		$tmp = array();
	 		$result = $adb->query('select * from sdk_presave');
	 		if ($result && $adb->num_rows($result)>0) {
	 			while($row=$adb->fetchByAssoc($result)) {
	 				$tmp[$row['presaveid']] = array('module'=>$row['module'],'src'=>$row['src']);
	 			}
	 		}
	 		$cache->set($tmp);
 		}
 		return $tmp;
 	}

	static function getPreSave($module) {
		$preSave = self::getPreSaveList();
 		foreach($preSave as $id => $info) {
			if ($module == $info['module']) {
				return $info['src'];
			}
 		}
 	}

	static function getPopupQueries($type) {
 		global $adb;
 		$cache = Cache::getInstance('sdk_popup_query',null,self::getCacheFolder());
		$tmp = $cache->get();
 		if ($tmp === false) {
	 		$tmp = array();
	 		$result = $adb->query('select * from sdk_popup_query');
	 		if ($result && $adb->num_rows($result)>0) {
	 			while($row=$adb->fetchByAssoc($result)) {
	 				$tmp[$row['type']][$row['id']] = array('module'=>$row['module'],'param'=>$row['param'],'src'=>$row['src'],'hidden_rel_fields'=>$row['hidden_rel_fields']);	//crmv@26920
	 			}
	 		}
 			$cache->set($tmp);
 		}
 		return $tmp[$type];
 	}

	static function getJSPreSaveLis() {
		$tmp = self::getPreSaveList();
		if (!empty($tmp)) {
 			return Zend_Json::encode($tmp);
		} else {
			return '';
		}
 	}

 	/**
 	 * $type: field/related -> popup open by field / popup open by field
 	 * $module : module from which popup is open
 	 * $param : if $type is field $param is the fieldname else il the destination module
 	 */
 	static function getPopupQuery($type,$module,$param) {
 		$popupQueries = self::getPopupQueries($type);
 		if (!empty($popupQueries)) {
 			foreach($popupQueries as $info) {
 				if ($module == $info['module'] && $param == $info['param']) {
 					return $info['src'];
 				}
 			}
 			//crmv@131239 crmv@180275 check for table fields
 			if ($type == 'field' && substr_count($param,'_') == 2) {
 				list($t,$f,$o) = explode('_',$param);
 				return self::getPopupQuery($type,$module,"{$t}_{$f}");
 			}
 			//crmv@131239e crmv@180275e
 		}
 	}

 	//crmv@26920
 	static function getPopupHiddenElements($module,$param,$only_fields=false){
 		require_once('include/Zend/Json.php');
 		$popupQueries = self::getPopupQueries('field');
 		if (!empty($popupQueries)) {
 			foreach($popupQueries as $info) {
 				if ($module == $info['module'] && $param == $info['param'] && $info['hidden_rel_fields'] != '') {
 					if (empty($hidden_fields)) {
 						$hidden_fields = Zend_Json::decode(html_entity_decode($info['hidden_rel_fields']));
 					}
 					if($only_fields === true){
 						return array_keys($hidden_fields);
 					}
 					if($only_fields == 'autocomplete'){
 						return html_entity_decode($info['hidden_rel_fields']);
 					}
 					$js_string = '';
 					$index = 0;
 					if (!empty($hidden_fields)) {
 						foreach($hidden_fields as $field =>$value ){
 							$js_string .= '&'.$field.'="+'.str_replace("\\","",$value);
 							if($index < sizeof($hidden_fields)-1){
 								$js_string .= '+"';
 							}
 							$index++;
 						}
 					}
 					if($index > 0){
 						$js_string .= '+"';
 					}
 					return $js_string;
 				}
 			}
 			//crmv@131239 crmv@180275 check for table fields
 			if (substr_count($param,'_') == 2) {
 				list($t,$f,$o) = explode('_',$param);
 				return self::getPopupHiddenElements($module,"{$t}_{$f}",$only_fields);
 			}
 			//crmv@131239e crmv@180275e
 		}
 	}
 	//crmv@26920e

	static function setPopupQuery($type, $module, $param, $src, $hidden_rel_fields='') {	//crmv@26920
 		global $adb;
 		// check duplicates
 		$file = self::getPopupQuery($type, $module, $param);
 		if (isset($file) && !empty($file)) {
 			self::log("Adding SDK Popup Query ($src) ... FAILED (duplicate)");
 			return;
 		}
 		$popupid = $adb->getUniqueID("sdk_popup_query");
 		//crmv@26920
 		$columns = 'id,type,module,param,src';
 		$params = array($popupid,$type,$module,$param,$src);
 		if ($hidden_rel_fields != '') {
 			$columns .= ',hidden_rel_fields';
 			$params[] =  Zend_Json::encode($hidden_rel_fields);
 		}
 		$adb->pquery('insert into sdk_popup_query ('.$columns.') values ('.generateQuestionMarks($params).')',array($params));
 		//crmv@26920e
 		self::log("Adding SDK Popup Query ($src) ... DONE");
 		self::clearSessionValue('sdk_popup_query');
 	}

 	static function unsetPopupQuery($type, $module, $param, $src) {
 		global $adb;

 		$query = 'delete from sdk_popup_query where type = ? and module = ? and param = ? and src = ?';
 		$qpar = array($type, $module, $param, $src);

 		$res = $adb->pquery($query, $qpar);
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Deleting SDK Popup Query ($src) ... DONE");
 			self::clearSessionValue('sdk_popup_query');
 		} else {
 			self::log("Deleting SDK Popup Query ($src) ... FAILED");
 		}
 	}

 	static function getAdvancedQueries() {
 		global $adb;
 		$cache = Cache::getInstance('sdk_adv_query',null,self::getCacheFolder());
		$tmp = $cache->get();
 		if ($tmp === false) {
	 		$tmp = array();
	 		$result = $adb->query('select * from sdk_adv_query');
	 		if ($result && $adb->num_rows($result)>0) {
	 			while($row=$adb->fetchByAssoc($result)) {
	 				$tmp[$row['module']] = array('src'=>$row['src'],'function'=>$row['function']);
	 			}
	 		}
	 		$cache->set($tmp);
 		}
 		return $tmp;
 	}

 	static function getAdvancedQuery($module) {
 		$filter = '';
 		$advancedQuery = self::getAdvancedQueries();
 		if ($advancedQuery[$module] != '') {
 			$src = $advancedQuery[$module]['src'];
 			if ($src != '' && Vtecrm_Utils::checkFileAccess($src,false)) {
 				require_once($src);
 				$filter = $advancedQuery[$module]['function']($module);
 			}
 		}
		return $filter;
 	}

	static function setAdvancedQuery($module, $func, $src) {
 		global $adb;
 		$qs = self::getAdvancedQueries();
 		if (array_key_exists($module, $qs)) {
 			self::log("Adding SDK Advanced Query ($module) ... FAILED");
 			return;
 		}
 		$adqueryid = $adb->getUniqueID("sdk_adv_query");
 		$params = array($adqueryid,$module,$func, $src);
 		$column = array('id','module','function','src');
 		$adb->format_columns($column);
 		$adb->pquery('insert into sdk_adv_query ('.implode(',',$column).') values ('.generateQuestionMarks($params).')',array($params));
 		self::log("Adding SDK Advanced Query ($module) ... DONE");
 		self::clearSessionValue('sdk_adv_query');
 	}

 	static function unsetAdvancedQuery($module) {
 		global $adb;

 		$query = 'delete from sdk_adv_query where module = ?';
 		$qpar = array($module);

 		$res = $adb->pquery($query, $qpar);
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Deleting SDK Advanced Query ($module) ... DONE");
 			self::clearSessionValue('sdk_adv_query');
 		} else {
 			self::log("Deleting SDK Advanced Query ($module) ... FAILED");
 		}
 	}

	static function getAdvancedPermissions() {
 		global $adb;
 		$cache = Cache::getInstance('sdk_adv_permission',null,self::getCacheFolder());
		$tmp = $cache->get();
 		if ($tmp === false) {
	 		$tmp = array();
	 		$result = $adb->query('select * from sdk_adv_permission');
	 		if ($result && $adb->num_rows($result)>0) {
	 			while($row=$adb->fetchByAssoc($result)) {
	 				$tmp[$row['module']] = array('src'=>$row['src'],'function'=>$row['function']);
	 			}
	 		}
	 		$cache->set($tmp);
		}
		return $tmp;
 	}

	static function getAdvancedPermissionFunction($module) {
 		$advancedPermission = self::getAdvancedPermissions();
 		if ($advancedPermission[$module] != '') {
 			$src = $advancedPermission[$module]['src'];
 			if ($src != '' && Vtecrm_Utils::checkFileAccess($src,false)) {
 				require_once($src);
 				return $advancedPermission[$module]['function'];
 			}
 		}
 	}

	static function setAdvancedPermissionFunction($module, $func, $src) {
 		global $adb;
 		$qs = self::getAdvancedPermissions();
 		if (array_key_exists($module, $qs)) {
 			self::log("Adding SDK Advanced Permission Function ($module) ... FAILED");
 			return;
 		}
 		$adqueryid = $adb->getUniqueID("sdk_adv_permission");
 		$params = array($adqueryid,$module,$func,$src);
 		$column = array('id','module','function','src');
 		$adb->format_columns($column);
 		$res = $adb->pquery('insert into sdk_adv_permission ('.implode(',',$column).') values ('.generateQuestionMarks($params).')',array($params));
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Adding SDK Advanced Permission Function ($module) ... DONE");
 			self::clearSessionValue('sdk_adv_permission');
 		} else {
 			self::log("Adding SDK Advanced Permission Function ($module) ... FAILED");
 		}
 	}

 	static function unsetAdvancedPermissionFunction($module) {
 		global $adb;

 		$query = 'delete from sdk_adv_permission where module = ?';
 		$qpar = array($module);

 		$res = $adb->pquery($query, $qpar);
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Deleting SDK Advanced Permission Function ($module) ... DONE");
 			self::clearSessionValue('sdk_adv_permission');
 		} else {
 			self::log("Deleting SDK Advanced Permission Function ($module) ... FAILED");
 		}
 	}

 	static function getClasses($all='') {
 		global $adb;
 		if (empty($adb->database) || !isModuleInstalled('SDK')) {
 			return;
 		}
 		$cache = Cache::getInstance('sdk_class'.$all,null,self::getCacheFolder());
		$tmp = $cache->get();
 		if ($tmp === false) {
	 		$tmp = array();
	 		$result = $adb->query('select * from sdk_class');
	 		if ($result && $adb->num_rows($result)>0) {
	 			while($row=$adb->fetchByAssoc($result)) {
	 				if ($all == '_all') {
	 					$tmp[$row['extends']] = array('module'=>$row['module'],'src'=>$row['src']);
	 				} elseif ($all == '_parent') {
	 					$tmp[$row['module']] = self::getParentModule($row['extends']);
	 				} else {
	 					$module = self::getSonModule($row['module']);
	 					$result1 = $adb->pquery('select * from sdk_class where module = ?',array($module));
	 					if ($result1 && $adb->num_rows($result1)>0) {
	 						$tmp[$row['extends']] = array('module'=>$module,'src'=>$adb->query_result($result1,0,'src'));
	 					}
	 				}
	 			}
	 		}
	 		$cache->set($tmp);
 		}
 		return $tmp;
 	}

	static function getSonModule($extends) {
 		$classes = self::getClasses('_all');
 		if ($classes[$extends] != '') {
 			return self::getSonModule($classes[$extends]['module']);
 		} else {
 			return $extends;
 		}
 	}

	static function getDirectSonModule($extends) {
 		$classes = self::getClasses('_all');
 		if ($classes[$extends] != '') {
 			return $classes[$extends]['module'];
 		} else {
 			return '';
 		}
 	}

 	static function getParentModule($module) {
		global $adb;
		$result = $adb->pquery('select extends from sdk_class where module = ?',array($module));
		if ($result && $adb->num_rows($result)>0) {
			$extends = $adb->query_result($result,0,'extends');
			$return = self::getParentModule($extends);
			if ($return != '') {
				$module = $return;
	 		}
		}
		return $module;
 	}

 	static function getClass($extends) {
 		$classes_all = self::getClasses('_all');
 		$classes = self::getClasses();
 		if ($classes[$extends] != '') {
 			return $classes[$extends];
 		}
 	}

	/**
 	 * Extends the class $extends with the class $module (which is in $src)
 	 * Some classes are not allowed to be extended and it's not permitted to
 	 * derive a class more than once
 	 */
 	static function setClass($extends, $module, $src) {
 	 	global $adb;
 	 	// check for blacklisted classes
 	 	$badclasses = array('Conditionals', 'Rss', 'VteRSS'); //crmv@31357+31355
 	 	if (in_array($extends, $badclasses)) {
 			self::log("Adding SDK Class ($module) ... FAILED (Class is blacklisted)");
 			return;
 	 	}

 	 	// check if class has already been extended
 		$classes = self::getClasses('_all');
 		if (in_array($extends, array_keys($classes))) {
 			self::log("Adding SDK Class ($module) ... FAILED (Class already extended)");
 			return;
 		}

 		// update the database
 		$classid = $adb->getUniqueID("sdk_class");
 		$params = array($classid,$extends,$module,$src);
 		$res = $adb->pquery('insert into sdk_class (id,extends,module,src) values ('.generateQuestionMarks($params).')',array($params));
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Adding SDK Class ($module) ... DONE");
 			self::clearSessionValue('sdk_class_all');
 			self::clearSessionValue('sdk_class_parent');
			self::clearSessionValue('sdk_class');
 		} else {
 			self::log("Adding SDK Class ($module) ... FAILED");
 		}
 	}

 	/**
 	 * Notes: deletes also all derived classes
 	 */
 	static function unsetClass($extends) {
		global $adb;

		// create array with all the sons
		$deletelist = array();
		$ds = $extends;
		while (($ds = self::getDirectSonModule($ds)) != '')
			$deletelist[] = $ds;
		array_pop($deletelist);
		$deletelist = array_reverse($deletelist);
		$deletelist[] = $extends;

		// do the deletion
		// TODO: join all the queries in a combined one to speed up things
		foreach ($deletelist as $ext) {
	 		$query = 'delete from sdk_class where extends = ?';
	 		$qpar = array($ext);
 			$res = $adb->pquery($query, $qpar);
 			if ($res && $adb->getAffectedRowCount($res) > 0) {
	 			self::log("Deleting SDK Class ($ext) ... DONE");
 				self::clearSessionValue('sdk_class_all');
 				self::clearSessionValue('sdk_class_parent');
				self::clearSessionValue('sdk_class');
	 		} else {
 				self::log("Deleting SDK Class ($ext) ... FAILED");
 			}
		}
 	}
 	
	static function getViews($module,$mode) {
		global $adb, $sdk_mode;
		 
		$skipView = $_REQUEST['skip_sdk_view'] ?? '';
		if ($skipView == '1') return false;
		
 		$sdk_mode = $mode;
 		$cache = Cache::getInstance('sdk_view',null,self::getCacheFolder());
		$tmp = $cache->get();
 		if ($tmp === false) {
	 		$tmp = array();
	 		$result = $adb->query('select * from sdk_view order by module, sequence');
	 		if ($result && $adb->num_rows($result)>0) {
	 			while($row=$adb->fetchByAssoc($result)) {
	 				$tmp[$row['module']][$row['sequence']] = array('src'=>$row['src'],'mode'=>$row['mode'],'on_success'=>$row['on_success']);
	 			}
	 		}
	 		$cache->set($tmp);
 		}
 		return $tmp[$module];
 	}

 	static function checkReadonly($readonly_old,$readonly,$mode) {
 		if ($mode == 'restrict') {
 			$readonly = max($readonly,$readonly_old);
 		} elseif ($mode == 'constrain') {
			//do nothing
 		}
 	}

 	/**
 	 * Retrieves the last sequence number for the specified module
 	 */
 	private	static function getLastViewSequence($module) {
 		global $adb;

 		$res = $adb->pquery('select max(sequence) from sdk_view where module = ?', array($module));
 		if ($res && $adb->num_rows($res) > 0) {
 			$row = $adb->fetch_array($res);
 			return intval($row[0]);
 		} else {
 			return 0;
 		}
 	}

 	/**
 	 * Adds a new View at the end of the list (for that module)
 	 */
	static function addView($module, $src, $mode, $success) {
 		global $adb;

 		$valid_modes = array('default'=>'restrict', 'constrain');
 		$valid_success = array('default'=>'continue', 'stop');

 		if (!in_array($mode, $valid_modes)) $mode = $valid_modes['default'];
 		if (!in_array($success, $valid_success)) $success = $valid_success['default'];

 		// check duplicates
 		$query = 'select module from sdk_view where module = ? and src = ?';
 		$qparam = array($module, $src);
 		$res = $adb->pquery($query, $qparam);
 		if ($res && $adb->num_rows($res) > 0) {
 			self::log("Adding SDK View ($module - $src) ... FAILED (duplicate)");
 			return;
 		}

 		$seq = self::getLastViewSequence($module) + 1;
 		$viewid = $adb->getUniqueID("sdk_view");

 		$qparam = array($viewid, $module, $src, $seq, $mode, $success);

 		$column=array("viewid","module","src","sequence","mode","on_success");
 		$adb->format_columns($column);
 		$query = 'insert into sdk_view ('.implode(',',$column).') values ('.generateQuestionMarks($qparam).')';

 		$res = $adb->pquery($query, $qparam);
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Adding SDK View ($module - $src) ... DONE");
 			self::clearSessionValue('sdk_view');
 		} else {
 			self::log("Adding SDK View ($module - $src) ... FAILED");
 		}
 	}

 	/**
 	 * Deletes a view
 	 */
 	static function deleteView($module, $src) {
 		global $adb;

 		$query = 'delete from sdk_view where module = ? and src = ?';
 		$qparam = array($module, $src);

 		$res = $adb->pquery($query, $qparam);
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Deleting SDK View ($module - $src) ... DONE");
 			self::clearSessionValue('sdk_view');
 		} else {
 			self::log("Deleting SDK View ($module - $src) ... FAILED");
 		}
 	}

 	/**
 	 * Returns an array of files/dirs associated with the module
 	 */
	static function getExtraSrc($module) {
 		global $adb;
 		$ret = array();

 		$query = 'select src from sdk_extra_src where module = ?';
 		$qparam = array($module);
 		$res = $adb->pquery($query, $qparam);
 		if ($res && $adb->num_rows($res) > 0) {
 			while ($row = $adb->fetch_array_no_html($res)) {
 				$ret[] = $row[0];
 			}
 		}
 		return $ret;
 	}

 	/**
 	 * Adds a file/dir association
 	 */
 	static function setExtraSrc($module, $src) {
 		global $adb;

 		// check duplicates
 		$query = 'select id from sdk_extra_src where module = ? and src = ?';
 		$qparam = array($module, $src);
 		$res = $adb->pquery($query, $qparam);
 		if ($res && $adb->num_rows($res) > 0) {
 			self::log("Adding SDK Extra Src ($module - $src) ... FAILED (duplicate)");
 			return;
 		}

 		$srcid = $adb->getUniqueID("sdk_extra_src");
 		$qparam = array($srcid, $module, $src);
 		$query = 'insert into sdk_extra_src (id, module, src) values ('.generateQuestionMarks($qparam).')';
 		$res = $adb->pquery($query, $qparam);
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Adding SDK Extra Src ($module - $src) ... DONE");
 		} else {
 			self::log("Adding SDK Extra Src ($module - $src) ... FAILED");
 		}
 	}

 	/**
 	 * Deletes a file/dir association
 	 */
 	static function unsetExtraSrc($module, $src) {
 		global $adb;

 		$query = 'delete from sdk_extra_src where module = ? and src = ?';
 		$qparam = array($module, $src);

 		$res = $adb->pquery($query, $qparam);
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Deleting SDK Extra Src ($module - $src) ... DONE");
 		} else {
 			self::log("Deleting SDK Extra Src ($module - $src) ... FAILED");
 		}
 	}

	static function getFiles($module) {
 		global $adb;
 		$cache = Cache::getInstance('sdk_file',null,self::getCacheFolder());
		$tmp = $cache->get();
 		if ($tmp === false) {
 			$tmp = array();
	 		$result = $adb->query('select * from sdk_file order by module');
	 		if ($result && $adb->num_rows($result)>0) {
	 			while($row=$adb->fetchByAssoc($result)) {
	 				$tmp[$row['module']][$row['file']] = $row['new_file'];
	 			}
	 		}
	 		$cache->set($tmp);
 		}
 		return $tmp[$module];
 	}

 	static function getFile($module,$file) {
 		$files = self::getFiles($module);
 		return $files[$file];
 	}

	static function setFile($module,$file,$new_file) {
		global $adb;
		$not_permitted_modules = array('Home','Calendar','Events');
		if ($module == '' || $file == '' || $new_file == '') {
			self::log("Adding SDK File ($new_file) ... FAILED (module, file or new_file empty!)");
			return;
		}
		if (self::getFile($module,$file) != '') {
			self::log("Adding SDK File ($new_file) ... FAILED (new_file already registered for module $module and file $file)");
			return;
		}
		$fileid = $adb->getUniqueID("sdk_file");
		$params = array($fileid,$module,$file,$new_file);
		$column = array('fileid','module','file','new_file');
		$adb->format_columns($column);
		$adb->pquery('insert into sdk_file ('.implode(',',$column).') values ('.generateQuestionMarks($params).')',array($params));
		self::log("Adding SDK File ($new_file) ... DONE");
		self::clearSessionValue('sdk_file');
	}

	static function unsetFile($module,$file) {
		global $adb;
		$column = 'file';
		$adb->format_columns($column);
		$res = $adb->pquery('delete from sdk_file where module = ? and '.$column.' = ?',array($module,$file));
		if ($res && $adb->getAffectedRowCount($res) > 0) {
			self::log("Deleting SDK File ($module,$file) ... DONE");
			self::clearSessionValue('sdk_file');
		} else {
			self::log("Deleting SDK File ($module,$file) ... FAILED");
		}
	}

 	static function getHomeIframes() {
 		global $adb,$table_prefix;
 		$cache = Cache::getInstance('sdk_home_iframe',null,self::getCacheFolder());
		$tmp = $cache->get();
 		if ($tmp === false) {
 			$tmp = array();
 			$sizeCol = 'size';
 			$adb->format_columns($sizeCol);
 			$sql = "SELECT sdk_home_iframe.stuffid, sdk_home_iframe.$sizeCol, sdk_home_iframe.iframe, sdk_home_iframe.url 
				FROM sdk_home_iframe 
				INNER JOIN {$table_prefix}_homestuff ON sdk_home_iframe.stuffid = {$table_prefix}_homestuff.stuffid
				UNION
				SELECT {$table_prefix}_homestuff.stuffid, sdk_home_global_iframe.$sizeCol, sdk_home_global_iframe.iframe, sdk_home_global_iframe.url 
				FROM sdk_home_global_iframe 
				INNER JOIN {$table_prefix}_homestuff ON {$table_prefix}_homestuff.stufftitle = sdk_home_global_iframe.name";
 			$result = $adb->query($sql);
 			if ($result && $adb->num_rows($result)>0) {
 				while ($row = $adb->fetchByAssoc($result)) {
 					$tmp[$row['stuffid']] = $row;
 				}
 			}
 			$cache->set($tmp);
 		}
 		return $tmp;
 	}

	static function getGlobalIframes() {
 		global $adb,$table_prefix;
 		$cache = Cache::getInstance('sdk_home_global_iframes',null,self::getCacheFolder());
		$tmp = $cache->get();
		if ($tmp === false) {
			$tmp = array();
 			if (Vtecrm_Utils::CheckTable('sdk_home_global_iframe')) {
	 			$result = $adb->query('select * from sdk_home_global_iframe');
	 			if ($result && $adb->num_rows($result)>0) {
	 				while ($row = $adb->fetchByAssoc($result)) {
	 					$tmp[$row['name']] = $row;
	 				}
	 			}
 			}
 			$cache->set($tmp);
 		}
 		return $tmp;
 	}

	static function getGlobalFixedIframes() {
 		global $adb,$table_prefix;
 		$cache = Cache::getInstance('sdk_home_fixed_iframes',null,self::getCacheFolder());
		$tmp = $cache->get();
		if ($tmp === false) {
			$tmp = array();
 			$result = $adb->query('select * from '.$table_prefix.'_home_iframe');
 			if ($result && $adb->num_rows($result)>0) {
 				while ($row = $adb->fetchByAssoc($result)) {
 					$tmp[] = $row;
 				}
 			}
 			$cache->set($tmp);
 		}
 		return $tmp;
 	}

 	static function getDefaultIframes($mode='plain'){
 		$session_name = 'sdk_home_default_iframes_'.$mode;
 		$cache = Cache::getInstance($session_name,null,self::getCacheFolder());
		$tmp = $cache->get();
		if ($tmp === false) {
 			$tmp = array();
 			$iframes = SDK::getGlobalFixedIframes();
 			foreach ($iframes as $iframe){
 				if ($mode != 'plain'){
 					$tmp['Iframe'][] = $iframe['hometype'];
 				}
 				else{
 					$tmp[] = $iframe['hometype'];
 				}
 			}
 			$iframes = SDK::getGlobalIframes();
 			foreach ($iframes as $iframe){
 				if ($mode != 'plain'){
 					$tmp['SDKIframe'][] = $iframe['name'];
 				}
 				else{
 					$tmp[] = $iframe['name'];
 				}
 			}
 			$cache->set($tmp);
 		}
 		return $tmp;
 	}

 	static function getHomeIframe($stuffid) {
 		$iframes = self::getHomeIframes();
 		return $iframes[$stuffid];
 	}

	static function setHomeIframe($size, $url, $title, $userid = null, $useframe = true) {
 		global $adb,$table_prefix;
 		if (empty($url)) {
 			self::log("Adding SDK Home Iframe ($url) ... FAILED (url empty)");
 			return;
 		}
 		// users
 		if (is_null($userid)) {
 			// all users
 			$userid = array_keys(get_user_array(false));
 		} elseif (!is_array($userid)) {
 			$userid = array($userid);
 		}
 	 	//duplicate
 		$iframes = self::getHomeIframes();
 		if (!empty($iframes)) {
 			foreach ($iframes as $id=>$idata) {
 				if ($idata['url'] == htmlspecialchars($url) && in_array($idata['userid'], $userid)) {
 					self::log("Adding SDK Home Iframe ($url) ... FAILED (url already registered)");
 					return;
 				}
 			}
 		}
 		// restrict size
 		$size = max(1, min($size, 4));
 		$useframe = intval($useframe);
 		foreach ($userid as $uid) {
 			$iframeid = $adb->getUniqueID($table_prefix."_homestuff");

 			$column = array('stuffid','stuffsequence','stufftype','userid','visible','size','stufftitle');
			$adb->format_columns($column);
 			$params = array($iframeid,0,'SDKIframe', $uid, 0, $size, $title);
		    $adb->pquery('insert into '.$table_prefix.'_homestuff ('.implode(',',$column).')  values ('.generateQuestionMarks($params).')',array($params));

 			$params = array($iframeid,$size, $useframe, $url);
 			$column = array('stuffid','size','iframe','url');
 			$adb->format_columns($column);
 			$adb->pquery('insert into sdk_home_iframe ('.implode(',',$column).') values ('.generateQuestionMarks($params).')',array($params));
 		}
 		self::log("Adding SDK Home Iframe ($url) ... DONE");
 		self::clearSessionValue('sdk_home_iframe');
 	}

 	// todo: come parametro passare un array
 	static function unsetHomeIframe($stuffid) {
 		global $adb,$table_prefix;
 		$res = $adb->pquery('delete from sdk_home_iframe where stuffid = ?', array($stuffid));
 		$res2 = $adb->pquery('delete from '.$table_prefix.'_homestuff where stuffid = ?', array($stuffid));
 		if ($res && $res2 && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Deleting SDK Home Iframe ($stuffid) ... DONE");
 			self::clearSessionValue('sdk_home_iframe');
 		} else {
 			self::log("Deleting SDK Home Iframe ($stuffid) ... FAILED");
 		}
 	}

	static function setHomeGlobalIframe($size, $url, $title,$useframe = true) {
 		global $adb,$table_prefix;
 		if (empty($url)) {
 			self::log("Adding SDK Home Global Iframe ($url) ... FAILED (url empty)");
 			return;
 		}
 	 	//duplicate
 		$iframes = self::getHomeIframes();
 		if (!empty($iframes)) {
 			foreach ($iframes as $id=>$idata) {
 				if ($idata['name'] == $title) {
 					self::log("Adding SDK Home Iframe ($title) ... FAILED (title already registered)");
 					return;
 				}
 			}
 		}
		$userid = array_keys(get_user_array(false));
 		// restrict size
 		$size = max(1, min($size, 4));
 		$useframe = intval($useframe);
 		$params = array($title,$size, $useframe, $url);
 		$column = array('name','size','iframe','url');
 		$adb->format_columns($column);
 		$adb->pquery('insert into sdk_home_global_iframe ('.implode(',',$column).') values ('.generateQuestionMarks($params).')',array($params));
 		//add homestuff and homedefault for every user
 		foreach ($userid as $uid) {
 			$result = $adb->pquery("SELECT * FROM {$table_prefix}_homestuff WHERE userid = ? AND stufftype = ? AND stufftitle = ?",array($uid,'SDKIframe',$title));
 			if ($result && $adb->num_rows($result) > 0) {
 				//skip
 			} else {
 				$iframeid = $adb->getUniqueID($table_prefix."_homestuff");
	 			$column = array('stuffid','stuffsequence','stufftype','userid','visible','size','stufftitle');
				$adb->format_columns($column);
	 			$params = array($iframeid,0,'SDKIframe', $uid, 0, $size, $title);
			    $adb->pquery('insert into '.$table_prefix.'_homestuff ('.implode(',',$column).') values ('.generateQuestionMarks($params).')',array($params));

			    $params_insert = Array(
					'stuffid'=>$iframeid,
					'hometype'=>$title,
					'maxentries'=>0,
					'setype'=>'NULL',
				);
				$sql="insert into ".$table_prefix."_homedefault (".implode(",",array_keys($params_insert)).") values(".generateQuestionMarks($params_insert).")";
				$adb->pquery($sql,$params_insert);
 			}
 		}
 		self::log("Adding SDK Home Global Iframe ($url) ... DONE");
 		self::clearSessionValue('sdk_home_global_iframe');
 	}

 	// todo: come parametro passare un array
 	static function unsetHomeGlobalIframe($title) {
 		global $adb,$table_prefix;
 		$res = $adb->pquery('delete from sdk_home_global_iframe where name = ?', array($title));
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			//delete homestuff and homedefault for every user
 			$params = array($title);
 			$adb->pquery('delete from '.$table_prefix.'_homestuff where stufftitle = ?',$params);
 			$adb->pquery('delete from '.$table_prefix.'_homedefault where hometype = ?',$params);
 			self::log("Deleting SDK Home Global Iframe ($stuffid) ... DONE");
 			self::clearSessionValue('sdk_home_global_iframe');
 		} else {
 			self::log("Deleting SDK Home Global Iframe ($stuffid) ... FAILED");
 		}
 	}

 	private static function getHomeIframeByUrl($url) {
 		global $adb;
 		$url = strtolower($url);

 		$params = array($url);
 		$query = 'select stuffid from sdk_home_iframe where lower(url) = ?';

 		$res = $adb->pquery($query, $params);
 		$ret = array();
 		if ($res && $adb->num_rows($res) > 0) {
 			while ($row = $adb->fetch_array($res)) {
 				$ret[] = $row[0];
 			}
 		}
 		return $ret;
 	}

 	static function unsetHomeIframeByUrl($url) {
 		global $adb;

 		$ids = self::getHomeIframeByUrl($url);

 		foreach ($ids as $id) {
 			self::unsetHomeIframe($id);
 		}
 	}

static function getMenuButton($type, $module='', $action='') {
 		global $adb, $theme;
 		$buttons = '';
 		$minImg = '';
 		$classLg = '';
 		if ($_COOKIE['crmvWinMaxStatus'] == 'close') {
	 		$minImg = '_min';
 		} else {
 			$classLg = 'md-lg';
 		}
 		if ($type == 'fixed') {
 			$res = $adb->query('select * from sdk_menu_fixed order by id');
 			if ($res && $adb->num_rows($res) > 0) {
 				while($row=$adb->fetchByAssoc($res,-1,false)) {
	 				//crmv@37303
	 				$check = true;
	 				if (!empty($row['cond'])) {
	 					$cond = explode(':',$row['cond']);
	 					if (count($cond) == 2) {
	 						// crmv@OPER8071
	 						if (file_exists($cond[1])){
	 							require_once($cond[1]);
	 							$check = $cond[0]($row);
	 						} else {
								$check = false;
	 						}
	 						// crmv@OPER8071e
	 					}
	 				}
	 				if ($check) {
		 				$image = explode('.',$row['image']);
		 				// check if it has an extension, and use it as an image
		 				if ($image[1]) {
							$image = $image[0].$minImg.'.'.$image[1];
							$buttons .= '<td><img class="" data-toggle="tooltip" data-placement="top" src="'.vtecrm_imageurl($image,$theme).'" onClick="'.$row['onclick'].'" alt="'.getTranslatedString($row['title'],$module).'" title="'.getTranslatedString($row['title'],$module).'" style="cursor:pointer;"/></td>'; // crmv@82419
		 				} else {
							$buttons .= '<td><i class="vteicon md-link '.$classLg.'" data-toggle="tooltip" data-placement="top" onClick="'.$row['onclick'].'" title="'.getTranslatedString($row['title'],$module).'">'.$image[0].'</i></td>'; // crmv@82419
		 				}
	 				}
	 				//crmv@37303e
	 			}
 			}
 		} elseif ($type == 'contestual' && $module != '') {
 			$query = 'select * from sdk_menu_contestual where module = ?';
			if ($action == '') {
				$query .= ' and (action = ? or action is null)';
			} else {
				$query .= ' and action = ?';
			}
			$query.=" order by id";
 			$res = $adb->pquery($query,array($module,$action));
 			if ($res && $adb->num_rows($res) > 0) {
 				while($row=$adb->fetchByAssoc($res,-1,false)) {
	 				//crmv@37303
	 				$check = true;
	 				if (!empty($row['cond'])) {
	 					$cond = explode(':',$row['cond']);
	 					if (count($cond) == 2) {
							// crmv@OPER8071
	 						if (file_exists($cond[1])){
								require_once($cond[1]);
								$check = $cond[0]($row);
							} else {
								$check = false;
							}
							// crmv@OPER8071e
	 					}
	 				}
	 				if ($check) {
		 				$image = explode('.',$row['image']);
		 				// check if it has an extension, and use it as an image
		 				if ($image[1]) {
							$image = $image[0].$minImg.'.'.$image[1];
							$buttons .= '<td><img class="" data-toggle="tooltip" data-placement="top" src="'.vtecrm_imageurl($image,$theme).'" onClick="'.$row['onclick'].'" alt="'.getTranslatedString($row['title'],$module).'" title="'.getTranslatedString($row['title'],$module).'" style="cursor:pointer;"/></td>'; // crmv@82419
		 				} else {
							$buttons .= '<td><i class="vteicon md-link '.$classLg.'" data-toggle="tooltip" data-placement="top" onClick="'.$row['onclick'].'" title="'.getTranslatedString($row['title'],$module).'">'.$image[0].'</i></td>'; // crmv@82419
		 				}
		 			}
	 				//crmv@37303e
	 			}
 			}
 		}
 		return $buttons;
 	}
 	
 	// crmv@140887
 	static function getMenuRawButton($type, $module = '', $action = '') {
 		global $adb, $theme;
 		if ($action == $module.'Ajax' && isset($_REQUEST['file'])) $action = strip_tags(vtlib_purify($_REQUEST['file'])); // crmv@176360 VERY SHIT! crmv@208173
 		
 		$buttons = array();
 		
 		if ($type == 'fixed') {
 			$res = $adb->query('select * from sdk_menu_fixed order by id');
 		} elseif ($type == 'contestual' && $module != '') {
 			$query = 'select * from sdk_menu_contestual where module = ?';
 			if ($action == 'ListView') {
 				$action = 'index';
 			}
 			if ($action == '') {
 				$query .= ' and (action = ? or action is null)';
 			} else {
 				$query .= ' and action = ?';
 			}
 			$query .= " order by id";
 			$res = $adb->pquery($query, array($module, $action));
 		}
 		
 		if ($res && $adb->num_rows($res) > 0) {
 			while ($row = $adb->fetchByAssoc($res, -1, false)) {
 				$check = true;
 				if (!empty($row['cond'])) {
 					$cond = explode(':', $row['cond']);
 					if (count($cond) == 2) {
 						require_once ($cond[1]);
 						$check = $cond[0]($row);
 					}
 				}
 				if ($check) {
 					$row['module'] = $module;
 					$row['is_image'] = false;
 					
 					//crmv@155337
 					require_once('include/utils/ResourceVersion.php');
 					$resourceVersion = ResourceVersion::getInstance();
 					if ($resourceVersion->isImageFile($row['image'])) {
 						$row['image'] = resourcever($row['image']);
 						$row['is_image'] = true;
 					}
 					//crmv@155337e
 					
 					unset($row['cond']);
 					$buttons[] = $row;
 				}
 			}
 		}
 		
 		return $buttons;
 	}
 	// crmv@140887e

 	static function setMenuButton($type, $title, $onclick, $image='', $module='', $action='', $condition='') {	//crmv@37303
 		global $adb;
 		if ($title == '' || $onclick == '' || ($type == 'contestual' && $module == '')) {
 			self::log("Adding SDK Menu Button ... FAILED (one or more params omitted)");
 			return;
 		}
 		if ($type == 'fixed') {
 			$res = $adb->pquery('select * from sdk_menu_fixed where title = ? and onclick like ?',array($title,$onclick));
 			if ($res && $adb->num_rows($res) > 0) {
	 			self::log("Adding SDK Menu Button Fixed ... FAILED (button already registered)");
	 			return;
 			}
 			$id = $adb->getUniqueID("sdk_menu_fixed");
	 		$params = array($id,$title,$onclick,$image);
	 		$column = array('id','title','onclick','image');
	 		//crmv@37303
	 		if (!empty($condition)) {
	 			$params[] = $condition;
	 			$column[] = 'cond';
	 		}
	 		//crmv@37303e
	 		$adb->format_columns($column);
	 		$adb->pquery('insert into sdk_menu_fixed ('.implode(',',$column).') values ('.generateQuestionMarks($params).')',array($params));
	 		self::log("Adding SDK Menu Button Fixed ($id) ... DONE");
 		} elseif ($type == 'contestual') {
 			/*	TODO: check duplicates
 			$col_title = 'title';
 			$col_onclick = 'onclick';
 			$col_module = 'module';
 			$col_action = 'action';
 			$adb->format_columns($col_title);
 			$adb->format_columns($col_onclick);
 			$adb->format_columns($col_module);
 			$adb->format_columns($col_action);
 			$res = $adb->pquery('select * from sdk_menu_contestual where '.$col_title.' = ? and '.$col_onclick.' = ? and '.$col_module.' = ? and '.$col_action.' = ?',array($title,$onclick,$module,$action));
 			if ($res && $adb->num_rows($res) > 0) {
	 			self::log("Adding SDK Menu Button Contestual ... FAILED (button already registered)");
	 			return;
 			}*/
 			$id = $adb->getUniqueID("sdk_menu_contestual");
	 		$params = array($id,$module,$action,$title,$onclick,$image);
	 		$column = array('id','module','action','title','onclick','image');
	 		//crmv@37303
	 		if (!empty($condition)) {
	 			$params[] = $condition;
	 			$column[] = 'cond';
	 		}
	 		//crmv@37303e
	 		$adb->format_columns($column);
	 		$adb->pquery('insert into sdk_menu_contestual ('.implode(',',$column).') values ('.generateQuestionMarks($params).')',array($params));
	 		self::log("Adding SDK Menu Button Contestual ($id) ... DONE");
 		}
 	}

 	static function unsetMenuButton($type, $id) {
 		global $adb;
 		$res = $adb->pquery('delete from sdk_menu_'.$type.' where id = ?',array($id));
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Deleting SDK Menu Button ... DONE");
 		} else {
 			self::log("Deleting SDK Menu Button ... FAILED");
 		}
 	}

	static function getReportFolders($read_reports = true, $folderid = false) { // crmv@163922
 		global $adb,$table_prefix;
 		$cache = Cache::getInstance('sdk_reportfolders',null,self::getCacheFolder());
		$tmp = $cache->get();
 		if ($tmp === false) {
 			$tmp = array();
 			// crmv@163922
 			if ($folderid > 0){
  				$result = $adb->pquery('select * from '.$table_prefix.'_crmentityfolder where tabid = ? and folderid = ? and state = ?',array(getTabid('Reports'), $folderid, 'SDK')); // crmv@30967 crmv@170747
 			} else {
 				$result = $adb->pquery('select * from '.$table_prefix.'_crmentityfolder where tabid = ? and state = ?',array(getTabid('Reports'), 'SDK')); // crmv@30967
 			}
 			// crmv@163922e
 			if ($result && $adb->num_rows($result)>0) {
 				while($row=$adb->fetchByAssoc($result)) {
					$row['name'] = getTranslatedString($row['foldername'],'Reports'); //crmv@65492 - 25
 					$row['id'] = $row['folderid'];
 					if ($read_reports) {
 						$row['details'] = self::getReports($row['folderid']);
 					}
 					$tmp[$row['folderid']] = $row;
 				}
 			}
 			$cache->set($tmp);
 		}
 		return $tmp;
 	}

 	static function getReportFolderIdByName($foldername) {
 		$folders = self::getReportFolders();

 		foreach ($folders as $id=>$fldr) {
 			if ($fldr['foldername'] == $foldername) {
 				return $id;
 			}
 		}
 		return null;
 	}

	static function setReport($name, $description, $foldername, $reportrun, $class, $jsfunction = '') {
 		global $adb,$table_prefix;

 		// check duplicates
 		if (!is_null(self::getReportIdByName($name))) {
 			self::log("Adding SDK Report ($name) ... FAILED (duplicate report)");
 			return false;
 		}

 		// folderid
 		$folderid = self::getReportFolderIdByName($foldername);
 		if (is_null($folderid)) {
 			self::log("Adding SDK Report ($name) ... FAILED (folder doesn't exist)");
 			return false;
 		}

 		// insert
 		$reportid = $adb->getUniqueID($table_prefix.'_report');

 		$params = array($reportid, $reportrun, $class, $jsfunction);
 		$res = $adb->pquery('insert into sdk_reports (reportid, reportrun, runclass, jsfunction) values ('.generateQuestionMarks($params).')', $params);

 		$params = array($reportid, $folderid, $name, $description, 'tabular', $reportid, 'SDK', 0, 1, 'Public');
 		$res2 = $adb->pquery('insert into '.$table_prefix.'_report (reportid, folderid, reportname, description, reporttype, queryid, state, customizable, owner, sharingtype) values ('.generateQuestionMarks($params).')', $params);

 		if ($res && $res2 && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Adding SDK Report ($name) ... DONE");
 			self::clearSessionValue('sdk_reports');
 			self::clearSessionValue('sdk_reportfolders');
 		} else {
 			self::log("Adding SDK Report ($name) ... FAILED");
 		}
 	}

	static function setReportFolder($name, $description) {
 		global $adb,$table_prefix;

 		// check duplicates
 		$folders = self::getReportFolders();
 		foreach ($folders as $fld) {
 			if ($fld['foldername'] == $name) {
 				self::log("Adding SDK Report Folder ($name) ... FAILED (folder exists)");
 				return false;
 			}
 		}

 		$folderid = $adb->getUniqueID($table_prefix.'_reportfolder');
 		$params = array($folderid, $name, $description, 'SDK');

 		// crmv@30967
 		$res = addEntityFolder('Reports', $name, $description, 1, 'SDK');
 		if ($res) {
 		// crmv@30967e
 			self::log("Adding SDK Report Folder ($name) ... DONE");
 			self::clearSessionValue('sdk_reportfolders');
 		} else {
 			self::log("Adding SDK Report Folder ($name) ... FAILED");
 		}
 	}

 	static function unsetReportFolder($name, $delreports = true) {
 		global $adb;

 		$folderid = self::getReportFolderIdByName($name);
 		if (is_null($folderid)) {
 			self::log("Deleting SDK Report Folder ($name) ... FAILED (folder not found)");
 			return;
 		}

 		if ($delreports) {
 			// delete all reports in folder
 			$reps = self::getReports($folderid);
 			foreach ($reps as $rep) {
 				self::unsetReport($rep['reportname']);
 			}
 		}

 		// crmv@30967
 		$res = deleteEntityFolder($folderid);
 		if ($res) {
		// crmv@30967e
 			self::log("Deleting SDK Report Folder ($name) ... DONE");
 			self::clearSessionValue('sdk_reportfolders');
 		} else {
 			self::log("Deleting SDK Report Folder ($name) ... FAILED");
 		}
 	}

	static function getReports($folderid) {
 		global $adb,$table_prefix;
 		$cache = Cache::getInstance('sdk_reports',null,self::getCacheFolder());
		$tmp = $cache->get();
 		if ($tmp === false) {
 			$tmp = array();
 			$result = $adb->query('select * from sdk_reports inner join '.$table_prefix.'_report on '.$table_prefix.'_report.reportid = sdk_reports.reportid order by folderid');
 			if ($result && $adb->num_rows($result)>0) {
 				while($row=$adb->fetchByAssoc($result)) {
 					$tmp[$row['folderid']][$row['reportid']] = $row;
 				}
 			}
			$cache->set($tmp);
 		}
 		return $tmp[$folderid];
 	}

 	static function getReportIdByName($reportname) {
 		$folders = self::getReportFolders();
 		if (!empty($folders)) {
	 		foreach ($folders as $fld) {
	 			if (!empty($fld['details'])) {
		 			foreach ($fld['details'] as $repid=>$report) {
		 				if ($report['reportname'] == $reportname) return $repid;
		 			}
	 			}
	 		}
 		}
 		return null;
 	}

	static function getReport($reportid, $folderid) {
 		$reports = self::getReports($folderid);
 		if (!empty($reports) && array_key_exists($reportid, $reports)) return $reports[$reportid];
 		return null;
 	}
 	
 	static function unsetReport($name) {
 		global $adb,$table_prefix;

 		$repid = self::getReportIdByName($name);

 		if (is_null($repid)) {
 			self::log("Deleting SDK Report ($name) ... FAILED (report not found)");
 			return;
 		}

 		$query = 'delete from sdk_reports where reportid = ?';
 		$res = $adb->pquery($query, array($repid));

 		$query = 'delete from '.$table_prefix.'_report where reportid = ? and state = ?';
 		$res2 = $adb->pquery($query, array($repid, 'SDK'));

 		if ($res && $res2 && $adb->getAffectedRowCount($res) > 0 && $adb->getAffectedRowCount($res2) > 0) {
 			self::log("Deleting Report ($name) ... DONE");
 			self::clearSessionValue('sdk_reports');
 			self::clearSessionValue('sdk_reportfolders');
 		} else {
 			self::log("Deleting SDK Report ($name) ... FAILED");
 		}
 	}

 	//crmv@sdk-27926
	static function getTransitions($module) {
 		global $adb;
 		$cache = Cache::getInstance('sdk_transitions',null,self::getCacheFolder());
		$tmp = $cache->get();
 		if ($tmp === false) {
 			$tmp = array();
 			$result = $adb->query('select * from sdk_transitions order by module');
 			if ($result && $adb->num_rows($result)>0) {
 				while($row=$adb->fetchByAssoc($result)) {
 					$tmp[$row['module']][$row['fieldname']] = $row;
 				}
 			}
 			$cache->set($tmp);
 		}
 		return $tmp[$module];
 	}

 	static function getTransition($module, $fieldname) {
 		$trans = self::getTransitions($module);
 		return $trans[$fieldname];
 	}

 	static function setTransition($module, $fieldname, $file, $function) {
 		global $adb;

 		if ($module == '' || $fieldname == '' || $file == '') {
 			self::log("Adding SDK Transition ($file) ... FAILED (module, fieldname or file or empty!)");
 			return;
 		}
 		if (self::getTransition($module,$fieldname) != '') {
 			self::log("Adding SDK Transition ($file) ... FAILED (file already registered for module $module and fieldname $fieldname)");
 			return;
 		}
 		$transid = $adb->getUniqueID("sdk_transitions");
 		$params = array($transid,$module,$fieldname,$file,$function);
 		$column = array('transitionid','module','fieldname','file', 'function');
 		$adb->format_columns($column);
 		$adb->pquery('insert into sdk_transitions ('.implode(',',$column).') values ('.generateQuestionMarks($params).')',array($params));
 		self::log("Adding SDK Transition ($file) ... DONE");
 		self::clearSessionValue('sdk_transitions');
 	}

 	static function unsetTransition($module, $fieldname, $file, $function) {
 		global $adb;
 		$column = 'fieldname';
 		$adb->format_columns($column);
 		$res = $adb->pquery('delete from sdk_transitions where module = ? and '.$column.' = ?',array($module,$fieldname));
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Deleting SDK Transition ($module,$fieldname) ... DONE");
 			self::clearSessionValue('sdk_transitions');
 		} else {
 			self::log("Deleting SDK Transition ($module,$fieldname) ... FAILED");
 		}
 	}
 	//crmv@sdk-27926e

 	//crmv@sdk-28873
    /* crmv@208472 */
	static function getDashboards() {
        throw new \Exception("Function has been removed");
	}

	static function getDashboard($dashname) {
        throw new \Exception("Function has been removed");
	}

	static function setDashboard($name,$file) {
        throw new \Exception("Function has been removed");
	}

	static function unsetDashboard($name) {
        throw new \Exception("Function has been removed");
	}
	//crmv@sdk-28873e
    /* crmv@208472e */

	//crmv@2539m
	static function getPDFCustomFunctions() {
 		global $adb;
 		$cache = Cache::getInstance('sdk_pdf_cfunctions',null,self::getCacheFolder());
		$tmp = $cache->get();
 		if ($tmp === false) {
 			$tmp = array();
 			$result = $adb->query('select * from sdk_pdf_cfunctions');
			if ($result && $adb->num_rows($result)>0) {
				while($row=$adb->fetchByAssoc($result)) {
					$tmp[$row['name']] = $row;
				}
			}
			$cache->set($tmp);
		}
		return $tmp;
	}

	static function getPDFCustomFunction($name) {
		$functions = self::getPDFCustomFunctions();
		if (is_array($functions) && array_key_exists($name, $functions)) return $functions[$name];
		return null;
	}

	static function setPDFCustomFunction($label,$name,$params) {
		global $adb;
		if ($label == '' || $name == '' || $params == '') {
			self::log("Adding SDK PDF Custom Function ($name) ... FAILED (label, name or params empty!)");
			return;
		}
		if (self::getPDFCustomFunction($name) != '') {
			self::log("Adding SDK PDF Custom Function ($name) ... FAILED ($name already registered)");
			return;
		}
		if (!is_array($params)) {
			$params = array($params);
		}
		$id = $adb->getUniqueID("sdk_pdf_cfunctions");
		$params = array($id, $label, $name, implode('|',$params));
		$column = array('id','label','name','params');
		$adb->format_columns($column);
		$adb->pquery('insert into sdk_pdf_cfunctions ('.implode(',',$column).') values ('.generateQuestionMarks($params).')',array($params));
		self::log("Adding SDK PDF Custom Function ($name) ... DONE");
		self::clearSessionValue('sdk_pdf_cfunctions');
	}

	static function unsetPDFCustomFunction($name) {
		global $adb;
		$column = 'name';
		$adb->format_columns($column);
		$res = $adb->pquery('delete from sdk_pdf_cfunctions where '.$column.' = ?',array($name));
		if ($res && $adb->getAffectedRowCount($res) > 0) {
			self::log("Deleting SDK PDF Custom Function ($name) ... DONE");
			self::clearSessionValue('sdk_pdf_cfunctions');
		} else {
			self::log("Deleting SDK PDF Custom Function ($name) ... FAILED");
		}
	}
	//crmv@2539me
	
	//crmv@51605
	static function getTurboliftCountInfo() {
 		global $adb, $table_prefix;
 		$cache = Cache::getInstance('sdk_turbolift_count',null,self::getCacheFolder());
		$tmp = $cache->get();
		if ($tmp === false) {
	 		$tmp = array();
	 		$result = $adb->query("SELECT sdk_turbolift_count.*, {$table_prefix}_relatedlists.tabid, {$table_prefix}_relatedlists.related_tabid
	 								FROM sdk_turbolift_count
	 								INNER JOIN {$table_prefix}_relatedlists ON {$table_prefix}_relatedlists.relation_id = sdk_turbolift_count.relation_id");
	 		if ($result && $adb->num_rows($result)>0) {
	 			while($row=$adb->fetchByAssoc($result)) {
	 				$row['module'] = getTabname($row['tabid']);
	 				$row['related_module'] = getTabname($row['related_tabid']);
					// crmv@140929
	 				if ($row['module'] == 'Campaigns') {
						switch ($row['method']) {
							case 'get_statistics_message_queue':
								$row['related_module'] = 'Message Queue';
								break;
							case 'get_statistics_sent_messages':
								$row['related_module'] = 'Sent Messages';
								break;
							case 'get_statistics_viewed_messages':
								$row['related_module'] = 'Viewed Messages';
								break;
							case 'get_statistics_tracked_link':
								$row['related_module'] = 'Tracked Link';
								break;
							case 'get_statistics_unsubscriptions':
								$row['related_module'] = 'Unsubscriptions';
								break;
							case 'get_statistics_bounced_messages':
								$row['related_module'] = 'Bounced Messages';
								break;
							case 'get_statistics_suppression_list':
								$row['related_module'] = 'Suppression list';
								break;
							case 'get_statistics_failed_messages':
								$row['related_module'] = 'Failed Messages';
								break;
						}
	 				}
	 				// crmv@140929e
	 				$tmp[$row['relation_id']] = $row;
	 			}
	 		}
	 		$cache->set($tmp);
 		}
 		return $tmp;
 	}

	static function getTurboliftCount($relation_id, $crmid) {
		global $adb;
		
		$count = '';
 		$info = self::getTurboliftCountInfo();
 		$module = $info[$relation_id]['module'];
 		$method = $info[$relation_id]['method'];
 		
 		$focus = CRMEntity::getInstance($module);
 		
 		global $onlyquery;
		$onlyquery = true;
		$_SESSION[strtolower($info[$relation_id]['related_module'])."_listquery"] = '';	//crmv@54900
		$res = $focus->$method($crmid, $info[$relation_id]['tabid'], $info[$relation_id]['related_tabid']);
		if (is_array($res) && !empty($res)) {	// standard relatedlist method //crmv@66870
			$query = $_SESSION[strtolower($info[$relation_id]['related_module'])."_listquery"];
			if(empty($query) && $info[$relation_id]['related_module'] == 'Calendar') $query = $_SESSION["activity_listquery"];	//crmv@60963
			if (!empty($query)) {
				$count_query = mkCountQuery($query);
				$count_result = $adb->querySlave('TurboliftCount',$count_query); // crmv@185894
				$count = $adb->query_result($count_result,0,"count");
			}
		} elseif (is_numeric($res)) {
			$count = $res;
		}
		if ($count == 0) $count = '';
		return $count;
 	}

	static function setTurboliftCount($relation_id, $method) {
 		global $adb;
 		$qs = self::getTurboliftCountInfo();
 		if (array_key_exists($relation_id, $qs)) {
 			self::log("Adding SDK Turbolift Count ($relation_id) ... FAILED");
 			return;
 		}
 		$params = array($relation_id, $method);
 		$column = array('relation_id', 'method');
 		$adb->format_columns($column);
 		$res = $adb->pquery('insert into sdk_turbolift_count ('.implode(',',$column).') values ('.generateQuestionMarks($params).')',array($params));
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Adding SDK Turbolift Count ($relation_id) ... DONE");
 			self::clearSessionValue('sdk_turbolift_count');
 		} else {
 			self::log("Adding SDK Turbolift Count ($relation_id) ... FAILED");
 		}
 	}

 	static function unsetTurboliftCount($relation_id) {
 		global $adb;

 		$query = 'delete from sdk_turbolift_count where relation_id = ?';
 		$qpar = array($relation_id);

 		$res = $adb->pquery($query, $qpar);
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Deleting SDK Turbolift Count ($relation_id) ... DONE");
 			self::clearSessionValue('sdk_turbolift_count');
 		} else {
 			self::log("Deleting SDK Turbolift Count ($relation_id) ... FAILED");
 		}
 	}
 	//crmv@51605e
 	
 	//crmv@92272
 	// custom functions for the fields in the actions of create, edit and email
	static function getProcessMakerFieldActions() {
 		global $adb;
 		$cache = Cache::getInstance('sdk_processmaker_factions',null,self::getCacheFolder());
		$tmp = $cache->get();
 		if ($tmp === false) {
	 		$tmp = array();
	 		$result = $adb->query('select * from sdk_processmaker_factions');
	 		if ($result && $adb->num_rows($result)>0) {
	 			while($row=$adb->fetchByAssoc($result)) {
	 				$tmp[$row['funct']] = $row;
	 			}
	 		}
	 		$cache->set($tmp);
 		}
 		return $tmp;
 	}
 	static function getFormattedProcessMakerFieldActions() {
 		$tmp = self::getProcessMakerFieldActions();
 		$return = array();
 		if (!empty($tmp)) {
 			foreach($tmp as $t) {
 				(empty($t['params'])) ? $params = '' : $params = $t['params'];
 				(empty($t['block'])) ? $block = getTranslatedString('LBL_PM_SDK_CUSTOM_FUNCTIONS','Settings') : $block = getTranslatedString($t['block'],'Settings');
 				$return[$block]["\$sdk:{$t['funct']}($params)"] = $t['label'];
 			}
 		}
 		return $return;
 	}
 	/*
 	 * func : static function name
 	 * src  : path of file
 	 * label : description of function
 	 * parametes (optional) : parameters of static function separated by commas, text parametes must be included in single quotes instead tag (ex. $59-contact_no) without quotes.
 	 */
 	static function setProcessMakerFieldAction($func, $src, $label, $parameters='', $block='') {
 		global $adb;
 		$tmp = self::getProcessMakerFieldActions();
 		if (!empty($tmp)) {
 			foreach($tmp as $t) {
 				if ($func == $t['funct']) {
 					self::log("Adding SDK Process Maker Field Condition Custom Function ($func) ... FAILED: static function already exists");
 					return;
 				}
 				if ($label == $t['label']) {
		 			self::log("Adding SDK Process Maker Field Condition Custom Function ($func) ... FAILED: label already exists");
		 			return;
		 		}
 			}
 		}
 		$id = $adb->getUniqueID("sdk_processmaker_factions");
 		$params = array($id,$func,$src,$label);
 		$column = array('id','funct','src','label');
 		if (!empty($parameters)) {
 			$params[] = $parameters;
 			$column[] = 'params';
 		}
 		if (!empty($block)) {
 			$params[] = $block;
 			$column[] = 'block';
 		}
 		$adb->format_columns($column);
 		$res = $adb->pquery('insert into sdk_processmaker_factions ('.implode(',',$column).') values ('.generateQuestionMarks($params).')',array($params));
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Adding SDK Process Maker Field Condition Custom Function ($func) ... DONE");
 			self::clearSessionValue('sdk_processmaker_factions');
 		} else {
 			self::log("Adding SDK Process Maker Field Condition Custom Function ($func) ... FAILED");
 		}
 	}
	static function unsetProcessMakerFieldAction($func) {
 		global $adb;
 		$res = $adb->pquery('delete from sdk_processmaker_factions where funct = ?', array($func));
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Deleting SDK Process Maker Field Condition Custom Function ($func) ... DONE");
 			self::clearSessionValue('sdk_processmaker_factions');
 		} else {
 			self::log("Deleting SDK Process Maker Field Condition Custom Function ($func) ... FAILED");
 		}
 	}
 	// custom functions for the conditions in the BPMN-Tasks
	static function getProcessMakerTaskConditions() {
 		global $adb;
 		$cache = Cache::getInstance('sdk_processmaker_tcond',null,self::getCacheFolder());
		$tmp = $cache->get();
 		if ($tmp === false) {
	 		$tmp = array();
	 		$result = $adb->query('select * from sdk_processmaker_tcond');
	 		if ($result && $adb->num_rows($result)>0) {
	 			while($row=$adb->fetchByAssoc($result)) {
	 				$tmp[$row['id']] = $row;	// TODO use funct as key (cos� se uso esporto in un altro vte funziona ancora)
	 			}
	 		}
	 		$cache->set($tmp);
 		}
 		return $tmp;
 	}
	static function getFormattedProcessMakerTaskConditions() {
		$tmp = self::getProcessMakerTaskConditions();
		$return = '<option value="">'.getTranslatedString('LBL_SELECT_OPTION_DOTDOTDOT','com_workflow').'</option>';//crmv@207901
		if (!empty($tmp)) {
			$return .= '<optgroup label="'.getTranslatedString('LBL_PM_SDK_CUSTOM_FUNCTIONS','Settings').'">';
			foreach($tmp as $t) {
 				$return .= '<option value="sdk:'.$t['id'].'">'.getTranslatedString($t['label'],'Settings').'</option>'; //crmv@169519
 			}
 			$return .= '</optgroup>';
		}
		return $return;
	}
	static function setProcessMakerTaskCondition($func, $src, $label) {
 		global $adb;
 		$tmp = self::getProcessMakerTaskConditions();
 		if (!empty($tmp)) {
 			foreach($tmp as $t) {
 				if ($func == $t['funct']) {
 					self::log("Adding SDK Process Maker Task Condition Custom Function ($func) ... FAILED: static function already exists");
 					return;
 				}
 				if ($label == $t['label']) {
		 			self::log("Adding SDK Process Maker Task Condition Custom Function ($func) ... FAILED: label already exists");
		 			return;
		 		}
 			}
 		}
 		$id = $adb->getUniqueID("sdk_processmaker_tcond");
 		$params = array($id,$func,$src,$label);
 		$column = array('id','funct','src','label');
 		$adb->format_columns($column);
 		$res = $adb->pquery('insert into sdk_processmaker_tcond ('.implode(',',$column).') values ('.generateQuestionMarks($params).')',array($params));
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Adding SDK Process Maker Task Condition Custom Function ($func) ... DONE");
 			self::clearSessionValue('sdk_processmaker_tcond');
 		} else {
 			self::log("Adding SDK Process Maker Task Condition Custom Function ($func) ... FAILED");
 		}
 	}
	static function unsetProcessMakerTaskCondition($func) {
 		global $adb;
 		$res = $adb->pquery('delete from sdk_processmaker_tcond where funct = ?', array($func));
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Deleting SDK Process Maker Task Condition Custom Function ($func) ... DONE");
 			self::clearSessionValue('sdk_processmaker_tcond');
 		} else {
 			self::log("Deleting SDK Process Maker Task Condition Custom Function ($func) ... FAILED");
 		}
 	}
 	// custom actions
 	static function getProcessMakerActions() {
 		global $adb;
 		$cache = Cache::getInstance('sdk_processmaker_actions',null,self::getCacheFolder());
		$tmp = $cache->get();
 		if ($tmp === false) {
	 		$tmp = array();
	 		$result = $adb->query('select * from sdk_processmaker_actions');
	 		if ($result && $adb->num_rows($result)>0) {
	 			while($row=$adb->fetchByAssoc($result)) {
	 				$row['label'] = 'SDK: '.$row['label'];
	 				$tmp[$row['funct']] = $row;
	 			}
	 		}
	 		$cache->set($tmp);
 		}
 		return $tmp;
 	}
	static function setProcessMakerAction($func, $src, $label) {
		global $adb;
 		$tmp = self::getProcessMakerActions();
 		if (!empty($tmp)) {
 			foreach($tmp as $t) {
 				if ($func == $t['funct']) {
 					self::log("Adding SDK Process Maker Custom Action ($func) ... FAILED: static function already exists");
 					return;
 				}
 				if ($label == $t['label']) {
		 			self::log("Adding SDK Process Maker Custom Action ($func) ... FAILED: label already exists");
		 			return;
		 		}
 			}
 		}
 		$id = $adb->getUniqueID("sdk_processmaker_actions");
 		$params = array($id,$func,$src,$label);
 		$column = array('id','funct','src','label');
 		$adb->format_columns($column);
 		$res = $adb->pquery('insert into sdk_processmaker_actions ('.implode(',',$column).') values ('.generateQuestionMarks($params).')',array($params));
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Adding SDK Process Maker Custom Action ($func) ... DONE");
 			self::clearSessionValue('sdk_processmaker_actions');
 		} else {
 			self::log("Adding SDK Process Maker Custom Action ($func) ... FAILED");
 		}
 	}
	static function unsetProcessMakerAction($func) {
		global $adb;
 		$res = $adb->pquery('delete from sdk_processmaker_actions where funct = ?', array($func));
 		if ($res && $adb->getAffectedRowCount($res) > 0) {
 			self::log("Deleting SDK Process Maker Custom Action ($func) ... DONE");
 			self::clearSessionValue('sdk_processmaker_actions');
 		} else {
 			self::log("Deleting SDK Process Maker Custom Action ($func) ... FAILED");
 		}
 	}
 	//crmv@92272e
 	
 	//crmv@170283
 	static function setRestOperation($name, $handlerFilePath, $handlerMethodName, $params=array()) {
 		global $adb, $table_prefix;
 		require_once('include/Webservices/Utils.php');
 		$res = $adb->pquery("SELECT operationid FROM {$table_prefix}_ws_operation WHERE name = ?", array($name));
 		if ($res && $adb->num_rows($res) == 0) {
 			$operationId = vtws_addWebserviceOperation($name, $handlerFilePath, $handlerMethodName, 'post', 0, $name);
 			if (!empty($params)) {
 				$seq = 1;
 				foreach($params as $pname => $ptype) {
 					vtws_addWebserviceOperationParam($operationId,$pname,$ptype,$seq);
 					$seq++;
 				}
 			}
 			self::log("Adding SDK Rest Operation ($name) ... DONE");
 		} else {
 			self::log("Adding SDK Rest Operation ($name) ... FAILED");
 		}
 	}
 	//crmv@170283e

 	/**
 	 * returns an array of all files/dirs which has been loaded by SDK
 	 * TODO: optional parameter to pretty print the array, by module or customization type
 	 * TODO: check if files are missing
 	 * TODO: add SDK API to query without module
 	 */
 	static function getAllCustomizations($readLinks = false) {
 		global $adb,$table_prefix;

 		$advPerm = self::getAdvancedPermissions();
 		$advQueries = self::getAdvancedQueries();
 		$classes = self::getClasses();
 		//$extraSrc = self::getExtraSrc();
 		//$files = self::getFiles();
 		$iframes = self::getHomeIframes();
 		//$buttons = self::getMenuButton();
 		//$popQueries = self::getPopupQueries();
 		$popFuncs = self::getPopupReturnFunctions();
 		$preSave = self::getPreSaveList();
 		$repFolders = self::getReportFolders();
 		$smarty = self::getSmartyTemplates();
 		$uitypes = self::getUitypes();
 		//$views = self::getViews();

 		$files = array();
 		if (is_array($advPerm)) {
 			foreach ($advPerm as $perm) $files[] = $perm['src'];
 		}
 		if (is_array($advQueries)) {
 			foreach ($advQueries as $query) $files[] = $query['src'];
 		}
 		if (is_array($classes)) {
 			foreach ($classes as $class) $files[] = $class['src'];
 		}
 	 	if (is_array($iframes)) {
 	 		$frs = array();
 			foreach ($iframes as $frame) $frs[] = $frame['url'];
 			$frs = array_unique($frs);
 			$files = array_merge($files, array_values($frs));
 		}
 	 	if (is_array($popFuncs)) {
 			foreach ($popFuncs as $func) $files[] = $func['src'];
 		}
 		if (is_array($preSave)) {
 			foreach ($preSave as $ps) $files[] = $ps['src'];
 		}
 		if (is_array($repFolders)) {
 			foreach ($repFolders as $folder) {
 				$reports = $folder['details'];
 				if (is_array($reports)) {
 					foreach ($reports as $rep) $files[] = $rep['reportrun'];
 				}
 			}
 		}
 		if (is_array($smarty)) {
 			foreach ($smarty as $templ) $files[] = 'Smarty/templates/'.$templ['src'];
 		}
 		if (is_array($uitypes)) {
 			foreach ($uitypes as $type) {
 				$files[] = $type['src_php'];
 				$files[] = $type['src_tpl'];
 				if (!empty($type['src_js'])) $files[] = $type['src_js'];
 			}
 		}

 		// for the next ones we need to directly query the db
 		$res = $adb->query('select src from sdk_extra_src');
 		if ($res && $adb->num_rows($res) > 0) {
 			while ($row = $adb->fetchByAssoc($res)) $files[] = $row['src'];
 		}
 		$res = $adb->query('select module,new_file from sdk_file');
 		if ($res && $adb->num_rows($res) > 0) {
 			while ($row = $adb->fetchByAssoc($res)) $files[] = 'modules/'.$row['module'].'/'.$row['new_file'].'.php';
 		}
 		/* menu buttons: javascript code, should we parse it? */
 		$res = $adb->query('select src from sdk_popup_query');
 		if ($res && $adb->num_rows($res) > 0) {
 			while ($row = $adb->fetchByAssoc($res)) $files[] = $row['src'];
 		}
 		$res = $adb->query('select src from sdk_view');
 		if ($res && $adb->num_rows($res) > 0) {
 			while ($row = $adb->fetchByAssoc($res)) $files[] = $row['src'];
 		}

 		// read stuff loaded in vte_links
 		if ($readLinks) {
 			$res = $adb->query('select linkurl,linkicon from '.$table_prefix.'_links where linktype in ("HEADERSCRIPT", "HEADERCSS") or linklabel like "%sdk%"');
 			if ($res && $adb->num_rows($res) > 0) {
 				$stuff = array();
 				while ($row = $adb->fetchByAssoc($res)) {
 					$stuff[] = $row['linkurl'];
 					if (!empty($row['linkicon'])) $stuff[] = $row['linkicon'];
 				}
 				$stuff = array_unique($stuff);
 				$files = array_merge($files, array_values($stuff));
 			}
 		}

 		$files = array_unique($files);
 		sort($files, SORT_STRING);
 		return $files;
 	}

 	//crmv@154170
 	static function exportXml($moduleInstance,&$PackageExportInstance) {
 		global $adb,$table_prefix;

		$module = $moduleInstance->name;
		$PackageExportInstance->openNode('sdk');

		//sdk_adv_permission - i
 		$advancedPermission = self::getAdvancedPermissions();
 		if ($advancedPermission[$module] != '') {
 			$PackageExportInstance->openNode('adv_permission');
			$PackageExportInstance->outputNode($advancedPermission[$module]['src'], 'src');
 			$PackageExportInstance->outputNode($advancedPermission[$module]['function'], 'function');
 			$PackageExportInstance->closeNode('adv_permission');
 		}
 		//sdk_adv_permission - e

 		//sdk_adv_query - i
 		$advancedQuery = self::getAdvancedQueries();
 		if ($advancedQuery[$module] != '') {
			$PackageExportInstance->openNode('adv_query');
			$PackageExportInstance->outputNode($advancedQuery[$module]['src'], 'src');
 			$PackageExportInstance->outputNode($advancedQuery[$module]['function'], 'function');
 			$PackageExportInstance->closeNode('adv_query');
 		}
 		//sdk_adv_query - e

 		//sdk_class - i
 		$sdk_class = self::getClass($module);
 		if (!empty($sdk_class)) {
 			$PackageExportInstance->openNode('classes');

 			$result = $adb->pquery('select * from sdk_class where extends = ?',array($module));
 			if ($result && $adb->num_rows($result)>0) {
				$PackageExportInstance->openNode('class');
				$PackageExportInstance->outputNode($adb->query_result($result,0,'extends'), 'extends');
				$PackageExportInstance->outputNode($adb->query_result($result,0,'module'), 'module');
				$PackageExportInstance->outputNode($adb->query_result($result,0,'src'), 'src');
				$PackageExportInstance->closeNode('class');
 			}
 			$ds = $adb->query_result($result,0,'module');
	 		while(($ds = self::getDirectSonModule($ds)) != '') {
	 			$result = $adb->pquery('select * from sdk_class where module = ?',array($ds));
 				if ($result && $adb->num_rows($result)>0) {
					$PackageExportInstance->openNode('class');
					$PackageExportInstance->outputNode($adb->query_result($result,0,'extends'), 'extends');
					$PackageExportInstance->outputNode($adb->query_result($result,0,'module'), 'module');
					$PackageExportInstance->outputNode($adb->query_result($result,0,'src'), 'src');
					$PackageExportInstance->closeNode('class');
 				}
			}

			$PackageExportInstance->closeNode('classes');
 		}
		//sdk_class - e

		//sdk_extra_src - i
		$sdk_extra_src = self::getExtraSrc($module);
		if (!empty($sdk_extra_src)) {
			$PackageExportInstance->openNode('extra_sources');
			foreach($sdk_extra_src as $extra_src) {
				$PackageExportInstance->outputNode($extra_src, 'extra_src');
			}
			$PackageExportInstance->closeNode('extra_sources');
		}
		//sdk_extra_src - e

 		//sdk_popup_query - i
 		$PackageExportInstance->openNode('popup_queries');
 		$sdk_popup_query_types = array('field','related');
 		foreach($sdk_popup_query_types as $sdk_popup_query_type) {
	 		$popupQueries = self::getPopupQueries($sdk_popup_query_type);
	 		if (!empty($popupQueries)) {
		 		foreach($popupQueries as $info) {
		 			if ($module == $info['module']) {
						$PackageExportInstance->openNode('popup_query');
						$PackageExportInstance->outputNode($sdk_popup_query_type, 'type');
						$PackageExportInstance->outputNode($info['param'], 'param');
						$PackageExportInstance->outputNode($info['src'], 'src');
						$PackageExportInstance->outputNode($info['hidden_rel_fields'], 'hidden_rel_fields');	//crmv@26920
						$PackageExportInstance->closeNode('popup_query');
		 			}
		 		}
	 		}
 		}
 		$PackageExportInstance->closeNode('popup_queries');
 		//sdk_popup_query - e

 		//sdk_popup_return_funct - i
 		$popupReturnFunctions = self::getPopupReturnFunctions();
 		if (!empty($popupReturnFunctions)) {
 			$PackageExportInstance->openNode('popup_return_functs');
	 		foreach($popupReturnFunctions as $id => $info) {
				if ($module == $info['module']) {
					$PackageExportInstance->openNode('popup_return_funct');
					$PackageExportInstance->outputNode($info['fieldname'], 'fieldname');
					$PackageExportInstance->outputNode($info['src'], 'src');
					$PackageExportInstance->closeNode('popup_return_funct');
				}
	 		}
	 		$PackageExportInstance->closeNode('popup_return_functs');
 		}
 		//sdk_popup_return_funct - e

 		//sdk_presave - i
 		$preSave = self::getPreSaveList();
 		if (!empty($popupReturnFunctions)) {
	 		foreach($preSave as $id => $info) {
	 			if ($module == $info['module']) {
	 				$PackageExportInstance->openNode('presave');
					$PackageExportInstance->outputNode($info['src'], 'src');
					$PackageExportInstance->closeNode('presave');
					break;
	 			}
	 		}
 		}
		//sdk_presave - e

 		//sdk_smarty - i
 		$smartyTemplates = self::getSmartyTemplates();
 		if (!empty($smartyTemplates)) {
 			$PackageExportInstance->openNode('smarty_templates');
	 		$src = array();
	 		foreach($smartyTemplates as $smartyTemplate) {
	 			$params = Zend_Json::decode($smartyTemplate['params']);
	 			if (!empty($params['module']) && $params['module'] == $module) {
					$PackageExportInstance->openNode('smarty_template');
					$PackageExportInstance->outputNode($smartyTemplate['params'], 'params');
					$PackageExportInstance->outputNode($smartyTemplate['src'], 'src');
					$PackageExportInstance->closeNode('smarty_template');
	 			}
	 		}
	 		$PackageExportInstance->closeNode('smarty_templates');
 		}
 		//sdk_smarty - e

 		//sdk_uitype - i
 		$sdkUitype = self::getUitypes();
 		if (!empty($sdkUitype)) {
 			$PackageExportInstance->openNode('uitypes');
 			$result = $adb->pquery("SELECT distinct uitype FROM ".$table_prefix."_field WHERE tabid = ? AND uitype IN (".generateQuestionMarks(array_keys($sdkUitype)).")",array(getTabid($module),array_keys($sdkUitype)));
 			if ($result && $adb->num_rows($result)>0) {
	 			while($row=$adb->fetchByAssoc($result)) {
	 				$PackageExportInstance->openNode('uitype');
	 				$PackageExportInstance->outputNode($row['uitype'], 'uitype');
	 				$PackageExportInstance->outputNode($sdkUitype[$row['uitype']]['src_php'], 'src_php');
	 				$PackageExportInstance->outputNode($sdkUitype[$row['uitype']]['src_tpl'], 'src_tpl');
	 				$PackageExportInstance->outputNode($sdkUitype[$row['uitype']]['src_js'], 'src_js');
	 				$PackageExportInstance->closeNode('uitype');
	 			}
 			}
 			$PackageExportInstance->closeNode('uitypes');
 		}
 		//sdk_uitype - e

 		//sdk_view - i
 		$sdkView = self::getViews($module,'');
 		if (!empty($sdkView)) {
 			$PackageExportInstance->openNode('views');
 			foreach ($sdkView as $sequence => $info) {
				$PackageExportInstance->openNode('view');
 				$PackageExportInstance->outputNode($info['src'], 'src');
 				$PackageExportInstance->outputNode($sequence, 'sequence');
 				$PackageExportInstance->outputNode($info['mode'], 'mode');
 				$PackageExportInstance->outputNode($info['on_success'], 'on_success');
 				$PackageExportInstance->closeNode('view');
 			}
 			$PackageExportInstance->closeNode('views');
 		}
 		//sdk_view - e

 		//sdk_file - i
 		$sdkFile = self::getFiles($module);
 		if (!empty($sdkFile)) {
 			$PackageExportInstance->openNode('files');
 			foreach ($sdkFile as $file => $new_file) {
				$PackageExportInstance->openNode('file');
 				$PackageExportInstance->outputNode($file, 'file');
 				$PackageExportInstance->outputNode($new_file, 'new_file');
 				$PackageExportInstance->closeNode('file');
 			}
 			$PackageExportInstance->closeNode('files');
 		}
 		//sdk_file - e
 		
		$PackageExportInstance->closeNode('sdk');
 	}
	//crmv@154170e

 	static function exportPackage($module,$zip) {
 		global $adb,$table_prefix;

 		//sdk_adv_permission - i
 		$advancedPermission = self::getAdvancedPermissions();
 		if ($advancedPermission[$module] != '') {
 			$src = $advancedPermission[$module]['src'];
 			$dir = substr($src, 0, strripos($src,'/'));
			$file = substr($src, strripos($src,'/')+1, strlen($src));
			$zip->copyFileFromDisk($dir,'sdk/adv_permission',$file);
 		}
 		//sdk_adv_permission - e

 		//sdk_adv_query - i
 		$advancedQuery = self::getAdvancedQueries();
 		if ($advancedQuery[$module] != '') {
 			$src = $advancedQuery[$module]['src'];
 			$dir = substr($src, 0, strripos($src,'/'));
			$file = substr($src, strripos($src,'/')+1, strlen($src));
			$zip->copyFileFromDisk($dir,'sdk/adv_query',$file);
 		}
 		//sdk_adv_query - e

 		//sdk_class - i
 		$sdk_class = self::getClass($module);
 		$sdk_class_list = array();
 		if (!empty($sdk_class)) {
 			$result = $adb->pquery('select * from sdk_class where extends = ?',array($module));
 			if ($result && $adb->num_rows($result)>0) {
				$sdk_class_list[$adb->query_result($result,0,'module')] = $adb->query_result($result,0,'src');
 			}
 			$ds = $adb->query_result($result,0,'module');
	 		while(($ds = self::getDirectSonModule($ds)) != '') {
	 			$result = $adb->pquery('select * from sdk_class where module = ?',array($ds));
 				if ($result && $adb->num_rows($result)>0) {
					$sdk_class_list[$ds] = $adb->query_result($result,0,'src');
 				}
			}
			if (!empty($sdk_class_list)) {
				$sdk_class_list = array_unique($sdk_class_list);
				foreach($sdk_class_list as $src) {
		 			$dir = substr($src, 0, strripos($src,'/'));
					$file = substr($src, strripos($src,'/')+1, strlen($src));
					$zip->copyFileFromDisk($dir,'sdk/class',$file);
				}
			}
 		}
		//sdk_class - e

 		//sdk_extra_src - i
		$sdk_extra_src = self::getExtraSrc($module);
		if (!empty($sdk_extra_src)) {
			foreach($sdk_extra_src as $extra_src) {
				$src = $extra_src;
				if (is_file($src)) {
					$dir = substr($src, 0, strripos($src,'/'));
					$file = substr($src, strripos($src,'/')+1, strlen($src));
					$zip->copyFileFromDisk($dir,'sdk/extra_src',$file);
				} elseif (is_dir($src)) {
					$dir = substr($src, strripos($src,'/')+1, strlen($src));
					$zip->copyDirectoryFromDisk($src,"sdk/extra_src/$dir");
				}
			}
		}
		//sdk_extra_src - e

 		//sdk_popup_query - i
 		$sdk_popup_query_types = array('field','related');
 		foreach($sdk_popup_query_types as $sdk_popup_query_type) {
	 		$popupQueries = self::getPopupQueries($sdk_popup_query_type);
	 		if (!empty($popupQueries)) {
		 		foreach($popupQueries as $info) {
		 			if ($module == $info['module']) {
		 				$src = $info['src'];
		 				$dir = substr($src, 0, strripos($src,'/'));
						$file = substr($src, strripos($src,'/')+1, strlen($src));
						$zip->copyFileFromDisk($dir,'sdk/popup_query',$file);
		 			}
		 		}
	 		}
 		}
 		//sdk_popup_query - e

 		//sdk_popup_return_funct - i
 		$popupReturnFunctions = self::getPopupReturnFunctions();
 		foreach($popupReturnFunctions as $id => $info) {
			if ($module == $info['module']) {
				$src = $info['src'];
				$dir = substr($src, 0, strripos($src,'/'));
				$file = substr($src, strripos($src,'/')+1, strlen($src));
				$zip->copyFileFromDisk($dir,'sdk/popup_return_funct',$file);
			}
 		}
 		//sdk_popup_return_funct - e

		//sdk_presave - i
 		$preSave = self::getPreSaveList();
 		foreach($preSave as $id => $info) {
 			if ($module == $info['module']) {
 				$src = $info['src'];
				$dir = substr($src, 0, strripos($src,'/'));
				$file = substr($src, strripos($src,'/')+1, strlen($src));
				$zip->copyFileFromDisk($dir,'sdk/presave',$file);
 			}
 		}
		//sdk_presave - e

		//sdk_smarty - i
 		$smartyTemplates = self::getSmartyTemplates();
 		if (!empty($smartyTemplates)) {
	 		$src = array();
	 		foreach($smartyTemplates as $smartyTemplate) {
	 			$params = Zend_Json::decode($smartyTemplate['params']);
	 			if (!empty($params['module']) && $params['module'] == $module) {
	 				$src = $smartyTemplate['src'];
	 				$dir = substr($src, 0, strripos($src,'/'));
					$file = substr($src, strripos($src,'/')+1, strlen($src));
					$zip->copyFileFromDisk('Smarty/templates/'.$dir,'sdk/smarty',$file);
	 			}
	 		}
 		}
 		//sdk_smarty - e

 		//sdk_uitype - i
 		$sdkUitype = self::getUitypes();
 		if (!empty($sdkUitype)) {
 			$result = $adb->pquery("SELECT distinct uitype FROM ".$table_prefix."_field WHERE tabid = ? AND uitype IN (".generateQuestionMarks(array_keys($sdkUitype)).")",array(getTabid($module),array_keys($sdkUitype)));
 			if ($result && $adb->num_rows($result)>0) {
	 			while($row=$adb->fetchByAssoc($result)) {
	 				$src_php = $sdkUitype[$row['uitype']]['src_php'];
	 				$dir = substr($src_php, 0, strripos($src_php,'/'));
	 				$file = substr($src_php, strripos($src_php,'/')+1, strlen($src_php));
	 				$zip->copyFileFromDisk($dir,'sdk/uitype/php',$file);

	 				$src_tpl = $sdkUitype[$row['uitype']]['src_tpl'];
	 				$dir = substr($src_tpl, 0, strripos($src_tpl,'/'));
	 				$file = substr($src_tpl, strripos($src_tpl,'/')+1, strlen($src_tpl));
	 				$zip->copyFileFromDisk('Smarty/templates/'.$dir,'sdk/uitype/tpl',$file);

	 				$src_js = $sdkUitype[$row['uitype']]['src_js'];
	 				$dir = substr($src_js, 0, strripos($src_js,'/'));
	 				$file = substr($src_js, strripos($src_js,'/')+1, strlen($src_js));
	 				$zip->copyFileFromDisk($dir,'sdk/uitype/js',$file);
	 			}
 			}
 		}
 		//sdk_uitype - e

 		//sdk_view - i
 		$sdkView = self::getViews($module,'');
 		if (!empty($sdkView)) {
 			foreach ($sdkView as $info) {
 				$src = $info['src'];
				$dir = substr($src, 0, strripos($src,'/'));
				$file = substr($src, strripos($src,'/')+1, strlen($src));
				$zip->copyFileFromDisk($dir,'sdk/view',$file);
 			}
 		}
 		//sdk_view - e

 		//sdk_file - i
 		/*
 		$sdkFile = self::getFiles($module);
 		if (!empty($sdkFile)) {
 			foreach ($sdkFile as $new_file) {
				$zip->copyFileFromDisk("modules/$module",'sdk/file',"$new_file.php");
 			}
 		}
		*/
 		//sdk_file - e
 	}

 	static function importPackage($modulenode, $moduleInstance) {

 		if (empty($modulenode->sdk)) return;
 		$module = strval($moduleInstance->name);
 		$tmp_dir = 'modules/SDK/tmp';

		if (!empty($modulenode->sdk->adv_permission)) {
			self::setAdvancedPermissionFunction($module, $modulenode->sdk->adv_permission->function, $modulenode->sdk->adv_permission->src);

			$dest = $modulenode->sdk->adv_permission->src;
 			$file = basename($dest);
 			@mkdir(dirname($dest),0775,true);
			if (file_exists("$tmp_dir/adv_permission/$file")) copy("$tmp_dir/adv_permission/$file", $dest);
		}
		if (!empty($modulenode->sdk->adv_query)) {
			self::setAdvancedQuery($module, $modulenode->sdk->adv_query->function, $modulenode->sdk->adv_query->src);

			$dest = $modulenode->sdk->adv_query->src;
 			$file = basename($dest);
 			@mkdir(dirname($dest),0775,true);
			if (file_exists("$tmp_dir/adv_query/$file")) copy("$tmp_dir/adv_query/$file", $dest);
		}
		if (!empty($modulenode->sdk->classes)) {
			foreach($modulenode->sdk->classes->class as $class) {
				self::setClass($class->extends, $class->module, $class->src);

				$dest = $class->src;
 				$file = basename($dest);
 				@mkdir(dirname($dest),0775,true);
				if (file_exists("$tmp_dir/class/$file")) copy("$tmp_dir/class/$file", $dest);
			}
		}
		if (!empty($modulenode->sdk->extra_sources)) {
			foreach($modulenode->sdk->extra_sources->extra_src as $extra_src) {
				self::setExtraSrc($module, $extra_src);

				$dest = $extra_src;
				$file = basename($dest);
				@mkdir(dirname($dest),0775,true);
				$src = "$tmp_dir/extra_src/$file";
				FSUtils::rcopy($src, $dest);
			}
		}
		if (!empty($modulenode->sdk->popup_queries)) {
			foreach($modulenode->sdk->popup_queries->popup_query as $popup_query) {
				self::setPopupQuery(strval($popup_query->type), $module, $popup_query->param, $popup_query->src, $popup_query->hidden_rel_fields);	//crmv@26920

				$dest = $popup_query->src;
 				$file = basename($dest);
 				@mkdir(dirname($dest),0775,true);
				if (file_exists("$tmp_dir/popup_query/$file")) copy("$tmp_dir/popup_query/$file", $dest);
			}
		}
		if (!empty($modulenode->sdk->popup_return_functs)) {
			foreach($modulenode->sdk->popup_return_functs->popup_return_funct as $popup_return_funct) {
				self::setPopupReturnFunction($module, $popup_return_funct->fieldname, $popup_return_funct->src);

				$dest = $popup_return_funct->src;
 				$file = basename($dest);
 				@mkdir(dirname($dest),0775,true);
				if (file_exists("$tmp_dir/popup_return_funct/$file")) copy("$tmp_dir/popup_return_funct/$file", $dest);
			}
		}
		if (!empty($modulenode->sdk->presave)) {
			self::setPreSave($module, $modulenode->sdk->presave->src);

			$dest = $modulenode->sdk->presave->src;
 			$file = basename($dest);
 			@mkdir(dirname($dest),0775,true);
			if (file_exists("$tmp_dir/presave/$file")) copy("$tmp_dir/presave/$file", $dest);
		}
		if (!empty($modulenode->sdk->smarty_templates)) {
			foreach($modulenode->sdk->smarty_templates->smarty_template as $smarty_template) {
				self::setSmartyTemplate(Zend_Json::decode($smarty_template->params), $smarty_template->src);

				$dest = "Smarty/templates/$smarty_template->src";
 				$file = basename($dest);
 				@mkdir(dirname($dest),0775,true);
				if (file_exists("$tmp_dir/smarty/$file")) copy("$tmp_dir/smarty/$file", $dest);
			}
		}
		if (!empty($modulenode->sdk->uitypes)) {
			foreach($modulenode->sdk->uitypes->uitype as $uitype) {
				self::setUitype($uitype->uitype,$uitype->src_php,$uitype->src_tpl,$uitype->src_js);

				if ($uitype->src_php != '') {
					$dest = $uitype->src_php;
	 				$file = basename($dest);
	 				@mkdir(dirname($dest),0775,true);
					if (file_exists("$tmp_dir/uitype/php/$file")) copy("$tmp_dir/uitype/php/$file", $dest);
				}

				if ($uitype->src_tpl != '') {
					$dest = "Smarty/templates/$uitype->src_tpl";
	 				$file = basename($dest);
	 				@mkdir(dirname($dest),0775,true);
					if (file_exists("$tmp_dir/uitype/tpl/$file")) copy("$tmp_dir/uitype/tpl/$file", $dest);
				}

				if ($uitype->src_js != '') {
					$dest = $uitype->src_js;
	 				$file = basename($dest);
	 				@mkdir(dirname($dest),0775,true);
					if (file_exists("$tmp_dir/uitype/js/$file")) copy("$tmp_dir/uitype/js/$file", $dest);
				}
			}
		}
 		if (!empty($modulenode->sdk->views)) {
			foreach($modulenode->sdk->views->view as $view) {
				self::addView($module, $view->src, $view->mode, $view->on_success);

				$dest = $view->src;
 				$file = basename($dest);
 				@mkdir(dirname($dest),0775,true);
				if (file_exists("$tmp_dir/view/$file")) copy("$tmp_dir/view/$file", $dest);
			}
		}
 		if (!empty($modulenode->sdk->files)) {
			foreach($modulenode->sdk->files->file as $file) {
				self::setFile($module, $file->file, $file->new_file);
			}
		}
		//cancello la cartella temporanea
		if (is_dir($tmp_dir)) {
			FSUtils::deleteFolder($tmp_dir);
		}
 	}
}