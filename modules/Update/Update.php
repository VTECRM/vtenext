<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@181161 - handle automatic updates */
 
class Update {

	public $from_version;
	public $to_version;
	public $use_script = false;

	static public $logPrefix = ''; // crmv@116306

	function __construct($server='',$username='',$password='',$from_version='',$to_version='') {
		if ($from_version != '') $this->from_version = $from_version;
		if ($to_version != '') $this->to_version = $to_version;
	}
	
 	/**
	* Invoked when special actions are performed on the module.
	* @param String Module name
	* @param String Event Type
	*/
	public function vtlib_handler($moduleName, $eventType) {
		global $adb,$table_prefix;

 		if($eventType == 'module.postinstall') {
			// Mark the module as Standard module
			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($moduleName));
			
			SDKUtils::file2DbLanguages($moduleName); // crmv@182073
			
			$em = new VTEventsManager($adb);
			$em->registerHandler('user.postlogin.web','modules/Update/UpdatePopupHandler.php','UpdatePopupHandler');
			$this->disableHandler(); // always disabled!
			
			$VP = VTEProperties::getInstance();
			$VP->set('update.check_updates', 1);
			
			$this->installCronjob();
			
			require_once('AutoUpdater.php');
			
			$AutoUpdate = new AutoUpdater();
			$AutoUpdate->createTables();

//			$fieldid = $adb->getUniqueID('vte_settings_field');
//			$blockid = getSettingsBlockId('LBL_STUDIO');
//			$seq_res = $adb->pquery("SELECT max(sequence) AS max_seq FROM vte_settings_field WHERE blockid = ?", array($blockid));
//			if ($adb->num_rows($seq_res) > 0) {
//				$cur_seq = $adb->query_result($seq_res, 0, 'max_seq');
//				if ($cur_seq != null)	$seq = $cur_seq + 1;
//			}

//			$adb->pquery('INSERT INTO vte_settings_field(fieldid, blockid, name, iconpath, description, linkto, sequence)
//				VALUES (?,?,?,?,?,?,?)', array($fieldid, $blockid, 'LBL_ST_MANAGER', 'workflow.gif', 'LBL_ST_MANAGER_DESCRIPTION', 'index.php?module=Update&action=index&parenttab=Settings', $seq));


		} else if($eventType == 'module.disabled') {
			$this->disableCronjob();
			$this->disableHandler();
		} else if($eventType == 'module.enabled') {
			$this->enableCronjob();
			//$this->enableHandler(); // always disabled!
		} else if($eventType == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($eventType == 'module.preupdate') {
			
			// vtlib are broken in update, since they don't copy the cron folder!
			$this->moveLocalCronFiles();
			
		} else if($eventType == 'module.postupdate') {
			
			SDKUtils::file2DbLanguages($moduleName); // crmv@182073
			$this->installJSLangs(); // crmv@182174
			
			$em = new VTEventsManager($adb);
			$em->registerHandler('user.postlogin.web','modules/Update/UpdatePopupHandler.php','UpdatePopupHandler');
			$this->disableHandler(); // always disabled!
			
			$VP = VTEProperties::getInstance();
			$VP->set('update.check_updates', 1);
			
			$this->installCronjob();
			
			require_once('AutoUpdater.php');
			
			$AutoUpdate = new AutoUpdater();
			$AutoUpdate->createTables();
			$AutoUpdate->createFileHashes(); // crmv@183486
			
			// crmv@182174
			// and activate the module!
			if (!vtlib_isModuleActive($moduleName)) {
				vtlib_toggleModuleAccess($moduleName, true);
			}
			// crmv@182174e
		}
 	}
 	
 	protected function installJSLangs() {
		$trans = array(
			'ALERT_ARR' => array(
				'it_it' => array(
					'update_ignored' => 'Aggiornamento ignorato',
					'update_postponed' => 'Aggiornamento posticipato',
					'update_canceled' => 'Aggiornamento annullato',
					'LBL_YOU_MUST_SELECT_USERS' => 'Devi selezionare degli utenti',
					'LBL_YOU_MUST_TYPE_A_MESSAGE' => 'Devi scrivere un messaggio',
				),
				'en_us' => array(
					'update_ignored' => 'Update ignored',
					'update_postponed' => 'Update postponed',
					'update_canceled' => 'Update canceled',
					'LBL_YOU_MUST_SELECT_USERS' => 'You should select some users',
					'LBL_YOU_MUST_TYPE_A_MESSAGE' => 'You should write a message',
				),
			)
		);

		$languages = vtlib_getToggleLanguageInfo();
		foreach ($trans as $module => $modlang) {
			foreach ($modlang as $lang => $translist) {
				if (array_key_exists($lang, $languages)) {
					foreach ($translist as $label => $translabel) {
						SDK::setLanguageEntry($module, $lang, $label, $translabel);
					}
				}
			}
		}
 	}
 	
 	protected function moveLocalCronFiles() {
		$srcDir = 'modules/Update/cron';
		if (is_dir($srcDir)) {
			$destDir = 'cron/modules/Update';
			if (!is_dir($destDir)) {
				mkdir($destDir, 0755, true);
			}
			$list = glob($srcDir.'/*.service.php');
			if ($list) {
				foreach ($list as $file) {
					$name = basename($file);
					rename($file, $destDir.'/'.$name);
				}
			}
			rmdir($srcDir);
		}
 	}
 	
 	protected function installCronjob() {
		require_once('include/utils/CronUtils.php');
		$cj = CronJob::getByName('CheckUpdates');
		if (empty($cj)) {
			$CU = CronUtils::getInstance();
			$cj = new CronJob();
			$cj->name = 'CheckUpdates';
			$cj->active = 1;
			$cj->singleRun = false;
			$cj->maxAttempts = 0;	// disable attempts check
			$cj->timeout = 1200;	// 20 minutes timeout
			$cj->repeat = 21600;	// repeat every 6 hours
			$cj->fileName = 'cron/modules/Update/CheckUpdates.service.php';
			$CU->insertCronJob($cj);
		}
		
		$cj = CronJob::getByName('DoUpdate');
		if (empty($cj)) {
			$CU = CronUtils::getInstance();
			$cj = new CronJob();
			$cj->name = 'DoUpdate';
			$cj->active = 1;
			$cj->singleRun = false;
			$cj->maxAttempts = 0;	// disable attempts check
			$cj->timeout = 21600;	// 6 hours timeout
			$cj->repeat = 300;		// repeat every 5 min
			$cj->fileName = 'cron/modules/Update/DoUpdate.service.php';
			$CU->insertCronJob($cj);
		}
	}
	
	public function enableCronjob() {
		require_once('include/utils/CronUtils.php');
		$cj = CronJob::getByName('CheckUpdates');
		if ($cj) {
			$cj->activate();
		}
		$cj = CronJob::getByName('DoUpdate');
		if ($cj) {
			$cj->activate();
		}
	}
	
	public function disableCronjob() {
		require_once('include/utils/CronUtils.php');
		$cj = CronJob::getByName('CheckUpdates');
		if ($cj) {
			$cj->deactivate();
		}
		$cj = CronJob::getByName('DoUpdate');
		if ($cj) {
			$cj->deactivate();
		}
	}
	
	public function enableHandler() {
		global $adb;
		$VTEM = new VTEventsManager($adb);
		$VTEM->setHandlerActive('UpdatePopupHandler');
	}
	
	public function disableHandler() {
		global $adb;
		$VTEM = new VTEventsManager($adb);
		$VTEM->setHandlerInActive('UpdatePopupHandler');
	}

	/**
 	 * Execute the update scripts from $this->from_version to $this->to_version
 	 * This function exptects to already have the new files
 	 */
	public function update_changes() {
		global $adb, $current_language, $table_prefix, $metaLogs; // crmv@49398
 		
 		if ($this->from_version == '' || $this->to_version == '') return false;
 		
 		if($this->use_script && !isFreeVersion()) {
 			$smarty = new VteSmarty();
 			$smarty->assign('CURRENT_LANGUAGE',$current_language);
 			$description = getTranslatedString('LBL_UPDATE_PACK_INVALID','Update');
 			if ($description == 'LBL_UPDATE_PACK_INVALID') {
 				$description = 'This update package is not applicable on your VTE version.<br />Please contact CRMVillage.BIZ or your Partner in order to obtain the correct version.';
 			}
 			$smarty->assign('BODY','<br />'.utf8_decode($description).'<br /><br />');
 			$smarty->display('NoLoginMsg.tpl');
 			exit;
 		}
 		set_time_limit(600);

		// clear cache also before starting
 		if (function_exists('apc_clear_cache')) {
 			@apc_clear_cache();
 		}
 		
 		// crmv@140903 - there might be the new tabdata cache
 		create_tab_data_file();
		create_parenttab_data_file();
 		// crmv@140903e
		
 		//crmv@18160
 		VteSession::set('skip_recalculate', true);
 		VteSession::set('modules_to_install', Array());
 		VteSession::set('modules_to_update', Array());
 		//crmv@18160 end
 		for ($i_version = $this->from_version; $i_version < $this->to_version; $i_version++) {
 			$change = $i_version.'_'.($i_version+1);
 			if(file_exists("modules/Update/changes/$change.php") && (filesize("modules/Update/changes/$change.php") != 0)) {
 				echo "<br />\n$i_version -> ".($i_version+1)."... ";
 				flush(); // crmv@183486
 				include("modules/Update/changes/$change.php");
 				echo "DONE";
 				flush(); // crmv@183486
 			}
 		}
 		//crmv@18160
 		//installo/aggiorno i moduli di VTE...
 		require_once('vtlib/Vtecrm/Package.php');//crmv@207871
		require_once('vtlib/Vtecrm/Language.php');//crmv@207871

 		if (is_array(VteSession::get('modules_to_install'))){
 			foreach (VteSession::get('modules_to_install') as $module=>$arr){
	 			$res = $adb->query("SELECT tabid FROM ".$table_prefix."_tab WHERE name = '$module'");
				if ($res && $adb->num_rows($res)>0) {
					VteSession::removeArray(array('modules_to_install', $module));
					VteSession::setArray(array('modules_to_update', $module), $arr);
				}
				else {
	 				$package = new Vtecrm_Package();
					$package->import($arr);
					flush(); // crmv@183486
				}
 			}
 		}
 		if (is_array(VteSession::get('modules_to_update'))){
 			foreach (VteSession::get('modules_to_update') as $module=>$arr){
 				if (in_array($module,array_keys(VteSession::get('modules_to_install'))))
 					continue;
 				if (is_array($arr)){
 					if (is_array($arr[modules])){
						$tmp_dir = "packages/vte/mandatory/tmp";
						mkdir($tmp_dir);
 						foreach ($arr['modules'] as $submodule){
 							$unzip = new Vtecrm_Unzip($arr['location']);
							$unzip->unzipAllEx($tmp_dir);
							if($unzip) $unzip->close();
	 						//installo il modulo presente nella cartella temporanea
							$uploadfilename = "packages/vte/mandatory/tmp/$submodule.zip";
							$package = new Vtecrm_Package();
							$moduleInstance = Vtecrm_Module::getInstance($submodule);
							$package->update($moduleInstance, $uploadfilename);
							flush(); // crmv@183486
 						}
 						//cancello la cartella temporanea
						if ($handle = opendir($tmp_dir)) {
							while (false !== ($file = readdir($handle)))
								if(is_file($tmp_dir.'/'.$file))	unlink($tmp_dir.'/'.$file);
							closedir($handle);
							rmdir($tmp_dir);
						}
 					}
 				}
 				else{
 					$package = new Vtecrm_Package();
 					$moduleInstance = Vtecrm_Module::getInstance($module);
					$package->update($moduleInstance,$arr);
					flush(); // crmv@183486
 				}
 			}
 		}
 		VteSession::remove('modules_to_install');
 		VteSession::remove('modules_to_update');
 		VteSession::remove('skip_recalculate');
 		include_once('vtlib/Vtecrm/Access.php');//crmv@207871
 		include_once('vtlib/Vtecrm/ModuleBasic.php');//crmv@207871
 		include_once('vtlib/Vtecrm/Menu.php');//crmv@207871
 		Vtecrm_Access::syncSharingAccess();
 		Vtecrm_Menu::syncfile();
 		Vtecrm_Module::syncfile();
 		//crmv@18160 end

 		//clear cache
 		if (function_exists('apc_clear_cache')) {
 			@apc_clear_cache();
 		}
		$smarty = new VteSmarty();
		@$smarty->clearAllCache();
		@$smarty->clearCompiledTemplate();
		if (is_dir('smartoptimizer/cache') && is_writable('smartoptimizer/cache')) {
			$files = @glob('smartoptimizer/cache/*', GLOB_NOSORT) ?: array();
			foreach($files as $file) {
				if (is_file($file)) @unlink($file);
			}
		}
		//clear cache end

		if (is_file('install.php')) {
			@unlink('install.php');
		}
		if (is_dir('install')) {
			@FSUtils::deleteFolder('install');
		}
		
		// crmv@94125
		// check changed resources
		require_once('include/utils/ResourceVersion.php');
		$RV = ResourceVersion::getInstance();
		$RV->enableCacheWrite(); // crmv@144893
		$RV->updateResources();
		// crmv@94125e
		
		//crmv@93043
		global $recalculateJsLanguage;
		if (!empty($recalculateJsLanguage)) {
			foreach($recalculateJsLanguage as $lang) {
				$adb->pquery("DELETE FROM sdk_language where language = ? AND module = ?", array($lang, 'ALERT_ARR'));
			}
			SDK::clearSessionValue('sdk_js_lang');
		}
		//crmv@93043e
		
		// crmv@181161
		require_once('AutoUpdater.php');
		$AU = new AutoUpdater();
		$austatus = $AU->getStatus();
		if ($austatus != AutoUpdater::STATUS_UPDATING) {
			// reset the status in case of manual update
			$AU->resetStatus();
		}
		// crmv@181161e
		
		if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_UPDATED, 0, array('from_revision'=>$this->from_version, 'to_revision'=>$this->to_version)); // crmv@49398
 	}

 	public function change_field($tablename,$field,$datatype,$precision,$other_params='',$is_primary_key=false) {
 		//per cambiare il tipo di dato di una colonna che contiene valori
 		global $adb;

 		//passo0: elimino evenutuali indici presenti sul campo
 		$idx_table = $adb->database->MetaIndexes($tablename);
 		$idx_to_recreate = array();
 		if (is_array($idx_table)){
 			$found = false;
 			foreach ($idx_table as $name=>$arr){
 				if (in_array($field,$arr['columns'])) {
 					$adb->datadict->ExecuteSQLArray($adb->datadict->DropIndexSQL($name,$tablename));
 					$idx_to_recreate[$name] = $arr;
 				}
 			}
 		}
		//passo1: creo il nuovo campo
		$field_backup = $field."_backup";
		if ($precision != '') $precision = "($precision)";
		$criteria = "$field_backup $datatype"."$precision $other_params";
//		$adb->startTransaction();
		$sql = $adb->datadict->ChangeTableSQL($tablename,$criteria);
  		if ($sql) {
   			$adb->datadict->ExecuteSQLArray($sql);
   			//passo2: copio i valori nel nuovo campo
   			$adb->query("update $tablename set $field_backup = $field");
   			//passo3: cancello il vecchio campo
   			$sql = $adb->datadict->DropColumnSQL($tablename,$field);
			if ($sql) {
    			$adb->datadict->ExecuteSQLArray($sql);
    			//passo4: rinomino il nuovo campo
    			$sql = $adb->datadict->RenameColumnSQL($tablename,$field_backup,$field,$criteria);
	    		if ($sql) {
	     			$adb->datadict->ExecuteSQLArray($sql);
	     			//passo5: se il campo � primary key
//	     			if ($is_primary_key) {
//		     			$sql = $adb->datadict->ChangeTableSQL($tablename,"$field PRIMARY");
//  					$adb->datadict->ExecuteSQLArray($sql);
//	     			}
	    		}
   			}
  		}
  		//passo6: ripristino gli indici eliminati
  		if (!empty($idx_to_recreate)) {
  			foreach ($idx_to_recreate as $name=>$arr){
  				$options = array();
  				if ($arr['unique']) {
  					$options[] = 'unique';
  				}
  				$adb->datadict->ExecuteSQLArray((Array)$adb->datadict->CreateIndexSQL($name, $tablename, $arr['columns'], $options));
  			}
  		}
 	}

 	//crmv@44187 crmv@64542
 	public static function create_fields($list) {
 		global $adb, $table_prefix;

 		if (!is_array($list)) return;
 		
 		$ret = array();
 		foreach ($list as $k=>$arr) {

 			$modulo = Vtecrm_Module::getInstance($arr['module']);
 			
 			if (!$modulo) {
				self::warn("The field {$arr['name']} has been skipped because the module $modulo was not found.");
				continue;
 			}

 			if (empty($arr['blockid'])) {
 				$block = Vtecrm_Block::getInstance($arr['block'], $modulo);
 			} else {
 				$block = Vtecrm_Block::getInstance($arr['blockid']);
 			}
 			
 			if (!$block) {
				self::warn("The field {$arr['name']} has been skipped because the block {$arr['block']} was not found.");
				continue;
			}

 			$field = @Vtecrm_Field::getInstance($arr['name'], $modulo);

 			if ($field != NULL) {
 				$ret[$k] = $field;
 				continue;
 			} else {
 				$field = new Vtecrm_Field();
 				$ret[$k] = $field;
 			}

 			// default values
 			$field->name = $arr['name'];
 			$field->column = $arr['name'];
 			$field->label= $arr['label'];
 			$field->columntype = 'C(255)';
 			$field->typeofdata = 'V~O';
 			$field->uitype = 1;
 			$field->readonly = 1;
 			$field->displaytype = 1;
 			$field->masseditable = 0;
 			$field->quickcreate = 2;
 			$field->table = $modulo->basetable;

 			if (isset($arr['table']) && !empty($arr['table']))
 				$field->table = $arr['table'];

 			if (isset($arr['column']) && !empty($arr['column']))
 				$field->column = $arr['column'];

 			if (isset($arr['readonly']) && !empty($arr['readonly']))
 				$field->readonly = $arr['readonly'];

 			if (isset($arr['presence']))
 				$field->presence = $arr['presence'];

 			if (isset($arr['columntype']) && !empty($arr['columntype']))
 				$field->columntype = $arr['columntype'];

 			if (isset($arr['typeofdata']) && !empty($arr['typeofdata']))
 				$field->typeofdata = $arr['typeofdata'];

 			if (isset($arr['uitype']) && !empty($arr['uitype']))
 				$field->uitype = $arr['uitype'];

 			if (isset($arr['displaytype']))
 				$field->displaytype = $arr['displaytype'];

 			if (isset($arr['quickcreate']))
 				$field->quickcreate = $arr['quickcreate'];

 			if (isset($arr['masseditable']))
 				$field->masseditable = $arr['masseditable'];

			if (isset($arr['helpinfo']))
				$field->helpinfo = $arr['helpinfo'];

 			//se picklist aggiungo i valori
 			if (isset($arr['picklist']) && !empty($arr['picklist'])){
 				$field->setPicklistValues($arr['picklist']);
 			}

 			$block->addField($field);

 			// related modules
 			if (isset($arr['relatedModules']) && !empty($arr['relatedModules'])){
 				$field->setRelatedModules($arr['relatedModules']);
 				foreach ($arr['relatedModules'] as $relmod) {
					if (!isset($arr['relatedModulesAction'][$relmod])) {
						$arr['relatedModulesAction'][$relmod] = array("ADD");
					}
 					$relinst = Vtecrm_Module::getInstance($relmod);
 					if ($relinst) {	//crmv@83576
 						$relinst->setRelatedList($modulo, $arr['module'], $arr['relatedModulesAction'][$relmod], 'get_dependents_list');
 					}
 				}
 			}

 			// sdk:uitype, we need to change the uitype by hand
 			if (isset($arr['sdk_uitype']) && !empty($arr['sdk_uitype'])) {
 				$newtype = intval($arr['sdk_uitype']);
 				$adb->pquery("update {$table_prefix}_field set uitype = ? where columnname = ? and tabid = ?", array($newtype, $arr['name'], $modulo->id));
 			}

 		}
 		return $ret;
 	}

	// crmv@104975
 	public static function create_blocks($blocklist) {
 		global $adb;

 		if (!is_array($blocklist)) return;

 		$ret = array();
 		foreach ($blocklist as $k=>$arr) {
 			$modulo = Vtecrm_Module::getInstance($arr['module']);
 			
 			if (!$modulo) {
				self::warn("The block {$arr['label']} has been skipped because the module $modulo was not found.");
				continue;
 			}

 			$block = @Vtecrm_Block::getInstance($arr['label'], $modulo);

 			if ($block != NULL) {
 				$ret[$k] = $block;
 				continue;
 			}

			if (!empty($arr['panelid'])) {
				$panel = Vtecrm_Panel::getInstance($arr['panelid']);
 			} elseif (!empty($arr['panel'])) {
 				$panel = Vtecrm_Panel::getInstance($arr['panel'], $modulo);
 			} else {
				// get the first for the module
				$panel = Vtecrm_Panel::getFirstForModule($modulo);
				if (!$panel) {
					// create an empty one
					$panel = new Vtecrm_Panel();
					$panel->label = 'LBL_TAB_MAIN';
					$modulo->addPanel($panel);
				}
 			}
 			
 			if (!$panel) {
				self::warn("The block {$arr['label']} has been skipped because the parent panel was not found.");
				continue;
			}

			$block = new Vtecrm_Block();
			$ret[$k] = $block;
			
			$block->panel = $panel;
 			$block->label= $arr['label'];

 			if (isset($arr['sequence']) && !empty($arr['sequence']))
 				$block->sequence = $arr['sequence'];

 			if (isset($arr['showtitle']))
 				$block->showtitle = $arr['showtitle'];

 			if (isset($arr['visible']))
 				$block->visible = $arr['visible'];

 			if (isset($arr['increateview']))
 				$block->increateview = $arr['increateview'];

 			if (isset($arr['ineditview']))
 				$block->ineditview = $arr['ineditview'];

 			if (isset($arr['indetailview']))
 				$block->indetailview = $arr['indetailview'];

 			$modulo->addBlock($block);
 		}
 		return $ret;
 	}

 	public static function create_panels($panelslist) {

 		if (!is_array($panelslist)) return;

 		$ret = array();
 		foreach ($panelslist as $k=>$arr) {
 			$modulo = Vtecrm_Module::getInstance($arr['module']);
 			
 			if (!$modulo) {
				self::warn("The panel {$arr['label']} has been skipped because the module $modulo was not found.");
				continue;
 			}

 			$panel = @Vtecrm_Panel::getInstance($arr['label'], $modulo);

 			if ($panel != NULL) {
 				$ret[$k] = $panel;
 				continue;
 			} else {
 				$panel = new Vtecrm_Panel();
 				$ret[$k] = $panel;
 			}

 			$panel->label= $arr['label'];

 			if (isset($arr['sequence']) && !empty($arr['sequence']))
 				$panel->sequence = $arr['sequence'];

 			if (isset($arr['visible']))
 				$panel->visible = $arr['visible'];

 			$modulo->addPanel($panel);
 		}
 		return $ret;
 	}
	// crmv@104975e

 	public static function create_filters($filterlist) {
 		global $adb;

 		if (!is_array($filterlist)) return;

 		$ret = array();
 		foreach ($filterlist as $k=>$arr) {

 			$modulo = Vtecrm_Module::getInstance($arr['module']);
 			
 			if (!$modulo) {
				self::warn("The filter {$arr['name']} has been skipped because the module $modulo was not found.");
				continue;
 			}

 			$filter = @Vtecrm_Filter::getInstance($arr['name'], $modulo);

 			if ($filter != NULL) {
 				$ret[$k] = $filter;
 				continue;
 			} else {
 				$filter = new Vtecrm_Filter();
 				$ret[$k] = $filter;
 			}

 			$filter->name = $arr['name'];
 			$filter->isdefault = false;
 			
 			if (isset($arr['isdefault']) && !empty($arr['isdefault']))
 				$filter->isdefault = $arr['isdefault'];
 				
			// crmv@174922
 			if (isset($arr['inmobile'])) {
				$filter->inmobile = $arr['inmobile'];
			}
			// crmv@174922e

 			$modulo->addFilter($filter);

 			if (isset($arr['fields']) && is_array($arr['fields'])) {
 				$seq = 1;
 				foreach ($arr['fields'] as $fieldname) {
 					$field = Vtecrm_Field::getInstance($fieldname, $modulo);
 					if ($field) {
						$filter->addField($field, $seq++);
					} else {
						self::warn("Unable to find the field $fieldname for the filter");
					}
 				}
 			}

 			if (isset($arr['stdrule']) && is_array($arr['stdrule'])) {
 				$rule = $arr['stdrule'];
 				$field = Vtecrm_Field::getInstance($rule['fieldname'], $modulo);
 				if ($field) {
					$filter->addStandardRule($field, $rule['duration'], $rule['datestart'], $rule['dateend'], intval($rule['onlymonth']));
				} else {
					self::warn("Unable to find the field {$rule['fieldname']} for the standard rule");
				}
 			}


 			if (isset($arr['rules']) && is_array($arr['rules'])) {
 				$seq = 1;
 				foreach ($arr['rules'] as $rule) {
 					$field = Vtecrm_Field::getInstance($rule['fieldname'], $modulo);
 					if ($field) {
						$filter->addRule($field, $rule['comparator'], $rule['value'], $seq++);
					} else {
						self::warn("Unable to find the field {$rule['fieldname']} for the rule");
					}
 				}
 			}
 			
 		}
 		return $ret;
 	}
 	
 	// crmv@116306
 	public static function info($message, $delim = true) {
		return self::log('[INFO] '.$message, $delim);
	}
 	
 	public static function warn($message, $delim = true) {
		return self::log('[WARNING] '.$message, $delim);
	}
	
 	static function log($message, $delimit = true) {
		echo self::$logPrefix . $message;
		if($delimit) {
			if (php_sapi_name() == 'cli') echo "\n"; else echo "<BR>\n";
		}
	}
 	//crmv@44187e crmv@64542e crmv@116306e

}