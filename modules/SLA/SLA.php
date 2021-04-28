<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@123658 */

class SLA {

	public static $config_file = 'modules/SLA/SLA.config.php';
	public static $config_file_inst = 'modules/SLA/SLA.config.install.php';
	//crmv@135193
	public static $skip_notifications = true;	// skip notifications
	public static $hide_changelog = true;		// hide changelog made by sla
	//crmv@135193e

	/**
	 * List of modules where the SLA is activated during install
	 */
	public static $install_for_modules = array('HelpDesk');
 	
 	/**
 	 * Get the config for all the modules
 	 */ 
	public static function get_config() {
		include(self::$config_file);
		$allcfg = array();
		if (is_array($sla_config['default'])) {
			foreach ($sla_config as $module => $cfg) {
				if ($module != 'default') {
					$allcfg[$module] = array_merge($sla_config['default'], $cfg);
				}
			}
		} else {
			$allcfg = $sla_config;
		}
		return $allcfg;
	}
	
	/**
 	 * Get the config for a specific module (default = HelpDesk)
 	 */ 
	public static function get_module_config($module = 'HelpDesk') {
		include(self::$config_file);
		if (!array_key_exists($module, $sla_config)) {
			$cfg = $sla_config['default'];
		} elseif (is_array($sla_config['default'])) {
			// merge the defaults (1 level only)
			$cfg = array_merge($sla_config['default'], $sla_config[$module]);
		} else {
			$cfg = $sla_config[$module];
		}
		return $cfg;
	}
	
	
 	/**
	* Invoked when special actions are performed on the module.
	* @param String Module name
	* @param String Event Type
	*/
 	function vtlib_handler($moduleName, $eventType) {
 					
		require_once('include/utils/utils.php');			
		global $adb;
		global $table_prefix;
		
 		if($eventType == 'module.postinstall') {	
 			/*	TODO: settings
			$fieldid = $adb->getUniqueID('vte_settings_field');
			$blockid = getSettingsBlockId('LBL_MODULE_MANAGER');
			
			$seq_res = $adb->query("SELECT max(sequence) AS max_seq FROM vte_settings_field");
			$seq = 1;
			if ($adb->num_rows($seq_res) > 0) {
				$cur_seq = $adb->query_result($seq_res, 0, 'max_seq');
				if ($cur_seq != null)	$seq = $cur_seq + 1;
			}
			
			$adb->pquery('INSERT INTO vte_settings_field(fieldid, blockid, name, iconpath, description, linkto, sequence)
				VALUES (?,?,?,?,?,?,?)', array($fieldid, $blockid, 'LBL_SLA', 'modules/SLA/resources/SLA.png', 'LBL_SLA_DESCRIPTION', 'index.php?module=SLA&action=index&parenttab=Settings', $seq));
			*/
			$tabid = getTabid('SLA');
			if(isset($tabid) && $tabid!='') {
				$adb->pquery('DELETE FROM '.$table_prefix.'_profile2tab WHERE tabid = ?', array($tabid));
			}
			
			// Mark the module as Standard module
			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($moduleName));
			
			// install SLA languages
			SDK::file2DbLanguages('SLA');
			
			$this->installConfig();
			
			$slacfg = SLA::get_config();
			if (is_array($slacfg) && count($slacfg) > 0){
				foreach ($slacfg as $module => $config){
					if (in_array($module, self::$install_for_modules)) {
						$this->installForModule($module);
					}
				}
			}
			
			// crmv@47611
			if (Vtecrm_Utils::CheckTable($table_prefix.'_cronjobs')) {
				require_once('include/utils/CronUtils.php');
				$CU = CronUtils::getInstance();

				$cj = new CronJob();
				$cj->name = 'SLA';
				$cj->active = 0;
				$cj->singleRun = false;
				$cj->fileName = 'cron/modules/SLA/SLA.service.php';
				$cj->timeout = 300;		// 5min timeout
				$cj->repeat = 300;		// run every 5 min
				$CU->insertCronJob($cj);
			}
			// crmv@47611e
			
		} else if($eventType == 'module.disabled') {
			$em = new VTEventsManager($adb);
			$em->setHandlerInActive('SLAHandler');
			
		} else if($eventType == 'module.enabled') {
			$em = new VTEventsManager($adb);
			$em->setHandlerActive('SLAHandler');

		} else if($eventType == 'module.preuninstall') {
		// TODO Handle actions when this module is about to be deleted.
		} else if($eventType == 'module.preupdate') {
		// TODO Handle actions before this module is updated.
		} else if($eventType == 'module.postupdate') {
			$tmp_dir = 'packages/vte/mandatory/tmp1';
			mkdir($tmp_dir);
			$unzip = new Vtecrm_Unzip("packages/vte/mandatory/$moduleName.zip");
			$unzip->unzipAllEx($tmp_dir);
			if($unzip) $unzip->close();
			copy("$tmp_dir/cron/$moduleName.service.php","cron/modules/$moduleName/$moduleName.service.php");
			if ($handle = opendir($tmp_dir)) {
				FSUtils::deleteFolder($tmp_dir);
			}
		}
 	}
 	
 	 	
 	/**
 	 * Install the config file
 	 */
 	public function installConfig() {
		if (is_readable(self::$config_file_inst)) {
			copy(self::$config_file_inst, self::$config_file);
		}
 	}
 	
 	/**
 	 * Migrate the existing old config to the new format
 	 */
 	public function migrateConfig() {
		if (is_readable(self::$config_file) && is_readable(self::$config_file_inst)) {
			include(self::$config_file);
			// check if already migrated (has the default section)
			if (!array_key_exists('default', $sla_config)) {
				$oldConfig = $sla_config;
				include(self::$config_file_inst);
				// now remove from the old config the parts equal to the default
				$defaults = $sla_config['default'];
				foreach ($oldConfig as $module => $modcfg) {
					foreach ($modcfg as $key => $oldcfg) {
						if (array_key_exists($key, $defaults)) {
							// serialize to json to check for equality (also subarrays)
							if (Zend_Json::encode($oldcfg) === Zend_Json::encode($defaults[$key])) {
								unset($modcfg[$key]);
							}
						}
					}
					$sla_config[$module] = $modcfg;
				}
				$content = file_get_contents(self::$config_file_inst);
				// remove last part
				$p = strpos($content, '/* --- UPDATE MARKER --- */');
				if ($p !== false) {
					$content = substr($content, 0, $p);
					$content .= "\n/* Migrated configuration */\n\n";
					// add the specific module config
					foreach ($sla_config as $module => $cfg) {
						if ($module != 'default') {
							$content .= "\n\$sla_config['$module'] = ".var_export($cfg, true).";\n";
						}
					}
				}
				$content .= "\n";
				file_put_contents(self::$config_file, $content);
			}
		}
 	}

 	/**
 	 * Add some configuration for the specified module
 	 */
 	public function setModuleConfig($module, $config) {
		if (is_writeable(self::$config_file)) {
			$content = file_get_contents(self::$config_file);
			// remove any old config for that module
			$content = preg_replace('/\$sla_config\[\''.$module.'\'\].*?\);/s', '', $content);
			$content .= "\$sla_config['$module'] = ".var_export($config, true).";\n";
			file_put_contents(self::$config_file, $content);
		}
 	}
 	
 	public function installForModule($module) {
		
		require_once('vtlib/Vtecrm/Utils.php');
		require_once('vtlib/Vtecrm/Menu.php');
		require_once('vtlib/Vtecrm/Module.php');

		if (!isModuleInstalled($module)) {
			echo "Unable to install SLA, module $module is not installed.";
			return false;
		}
		
		$langs = array_keys(vtlib_getToggleLanguageInfo());
		
		$template = Array(
			'blocks_to_create'=>Array(
				'LBL_SLA'=>Array(
					'label'=>'LBL_SLA',
					'langs'=>Array('it_it'=>'Temipistiche SLA','en_us'=>'SLA timings'),
				),
			),
			'fields_to_hide'=>Array(
			),
			'fields_to_modify'=>Array(
			),
			'fields_to_create'=>Array(
				'time_elapsed'=>Array(
					'label'=>'Time Elapsed',
					'uitype'=>1020,
					'langs'=>Array('it_it'=>'Tempo trascorso','en_us'=>'Time Elapsed'),
					'block'=>'LBL_SLA',
					'readonly'=>99,
					'typeofdata'=>'I~O',
				),
				'time_remaining'=>Array(
					'label'=>'Time remaining',
					'uitype'=>1020,
					'langs'=>Array('it_it'=>'Tempo rimanente','en_us'=>'Time remaining'),
					'block'=>'LBL_SLA',
					'readonly'=>99,
					'typeofdata'=>'I~O',
				),
				'start_sla'=>Array(
					'label'=>'SLA start date',
					'uitype'=>1021,
					'langs'=>Array('it_it'=>'Data partenza SLA','en_us'=>'SLA start date'),
					'block'=>'LBL_SLA',
					'readonly'=>99,
				),
				'end_sla'=>Array(
					'label'=>'SLA end date',
					'uitype'=>1021,
					'langs'=>Array('it_it'=>'Data fine SLA','en_us'=>'SLA end date'),
					'block'=>'LBL_SLA',
					'readonly'=>99,
				),
				'time_refresh'=>Array(
					'label'=>'Update Time',
					'uitype'=>1021,
					'langs'=>Array('it_it'=>'Orario di aggiornamento','en_us'=>'Update Time'),
					'block'=>'LBL_SLA',
					'readonly'=>99,
				),
				'sla_time'=>Array(
					'label'=>'SLA Estimated Time',
					'uitype'=>1020,
					'langs'=>Array('it_it'=>'Tempo SLA previsto','en_us'=>'SLA Estimated Time'),
					'block'=>'LBL_SLA',
					'readonly'=>1,
				),
				'due_date'=>Array(
					'label'=>'Due Date',
					'uitype'=>5,
					'generatedtype'=>2,
					'langs'=>Array('it_it'=>'Data di chiusura','en_us'=>'Due Date'),
					'block'=>'LBL_SLA',
				),
				'due_time'=>Array(
					'label'=>'Due Time',
					'uitype'=>1,
					'langs'=>Array('it_it'=>'Ora chiusura (hh:mm)','en_us'=>'Due time (hh:mm)'),
					'block'=>'LBL_SLA',
					'columntype'=>'C(5)',
					'typeofdata'=>'T~O',
				),
				'time_change_status'=>Array(
					'label'=>'Time Last Status Change',
					'uitype'=>1021,
					'langs'=>Array('it_it'=>'Data ultimo cambio di stato','en_us'=>'Time Last Status Change'),
					'block'=>'LBL_SLA',
					'typeofdata'=>'V~O',
					'readonly'=>100,
				),
				'time_elapsed_change_status'=>Array(
					'label'=>'Time Elapsed Last Status Change',
					'uitype'=>1020,
					'langs'=>Array('it_it'=>'Tempo trascorso da ultimo cambio di stato','en_us'=>'Time Elapsed Last Status Change'),
					'block'=>'LBL_SLA',
					'readonly'=>100,		
				),
				'reset_sla'=>Array(
					'label'=>'Reset SLA',
					'uitype'=>56,
					'langs'=>Array('it_it'=>'Resetta SLA','en_us'=>'Reset SLA'),
					'block'=>'LBL_SLA',
				),
				'ended_sla'=>Array(
					'label'=>'End SLA',
					'uitype'=>56,
					'langs'=>Array('it_it'=>'Fine SLA','en_us'=>'End SLA'),
					'block'=>'LBL_SLA',
					'readonly'=>99,
				),
				'time_elapsed_idle'=>Array(
					'label'=>'Idle Time Elapsed',
					'uitype'=>1020,
					'langs'=>Array('it_it'=>'Tempo trascorso in idle','en_us'=>'Idle Time Elapsed'),
					'block'=>'LBL_SLA',
					'readonly'=>99,
				),		
				'time_elapsed_out_sla'=>Array(
					'label'=>'Out SLA Time Elapsed',
					'uitype'=>1020,
					'langs'=>Array('it_it'=>'Tempo trascorso fuori SLA','en_us'=>'Out SLA Time Elapsed'),
					'block'=>'LBL_SLA',
					'readonly'=>99,
				),		
			),
		);
				
		$moduleInstance = Vtecrm_Module::getInstance($module);
		
		//creazione blocchi
		if ($template['blocks_to_create']){
			$this->createBlocks($module, $template['blocks_to_create'], $langs);
		}
		//creazione campi
		if ($template['fields_to_create']){
			$this->createFields($module, $template['fields_to_create'], $langs);
		}
		//nascondere campi
		if ($template['fields_to_hide']){
			$this->hideFields($module, $template['fields_to_hide']);
		}
		//rinominazione campi
		if ($template['fields_to_rename']){
			$this->renameFields($module, $template['fields_to_rename'], $langs);
		}
		//modifica campi
		if ($template['fields_to_modify']){
			$this->renameFields($module, $template['fields_to_modify']);
		}
		
		// gets all the SLA languages
		if (!empty($langs)) {
			foreach ($langs as $lang) {
				$slalang = get_lang_strings('SLA', $lang);
				// now install languages in the module
				if (is_array($slalang)) {
					foreach ($slalang as $key => $trans) {
						SDK::setLanguageEntry($module, $lang, $key, $trans);
					}
				}
			}
		}
		
		return true;
 	}
 	
 	protected function createBlocks($module, $blocks, $langs) {
		global $adb, $table_prefix;
		
		$moduleInstance = Vtecrm_Module::getInstance($module);
		
		foreach ($blocks as $blockname=>$arr){
			// check if existing
			$block = @Vtecrm_Block::getInstance($arr['label'], $moduleInstance);
			if ($block) continue;
			
			$block = new Vtecrm_Block();
			$block->label = $arr['label'];
			if ($arr['sequence'])
				$block->sequence = $arr['sequence'];
			if ($arr['showtitle'])
				$block->showtitle = $arr['showtitle'];
			if ($arr['visible'])
				$block->visible = $arr['visible'];
			if ($arr['increateview'])
				$block->increateview = $arr['increateview'];
			if ($arr['ineditview'])
				$block->ineditview = $arr['ineditview'];
			if ($arr['indetailview'])
				$block->indetailview = $arr['indetailview'];
			$block->save($moduleInstance);
			foreach ($langs as $lang_str){
				$file = "modules/$module/language/$lang_str.lang.php";
				include($file);
				$old = "/\);\s*.?>\s*/";
				$new = "'".$arr['label']."' => '".$arr['langs'][$lang_str]."',\n);\n?>";
				$contents = preg_replace($old,$new,file_get_contents($file));
				file_put_contents($file,$contents);
			}			
		}
	}
 	
 	protected function createFields($module, $fields, $langs) {
		global $adb, $table_prefix;
		
		$moduleInstance = Vtecrm_Module::getInstance($module);
		
		foreach ($fields as $fieldname=>$arr){
		
			// check if existing
			$field = @Vtecrm_Field::getInstance($fieldname, $moduleInstance);
			if ($field) continue;
		
			$field = new Vtecrm_Field();
			$field->name = $fieldname;
			if ($arr['columnn'])
				$field->columnn = $arr['columnn'];
			if ($arr['table'])
				$field->table = $arr['table'];
			else
				$field->table = $moduleInstance->basetable;
			if ($arr['label'])
				$field->label = $arr['label'];
			if (!$arr['block']){
				$q = "SELECT blockid FROM ".$table_prefix."_blocks where tabid  = (select tabid from ".$table_prefix."_tab where name = ?)  order by sequence asc";
				$res = $adb->limitpQuery($q,0,1,Array($module));
				if ($res && $adb->num_rows($res) == 1){
					$arr['block'] = $adb->query_result($res,0,'blockid');
				}
			}
			$block_instance = Vtecrm_Block::getInstance($arr['block'],$moduleInstance);
			if ($arr['readonly'])
				$field->readonly = $arr['readonly'];
			else	
				$field->readonly = 1;
			if (!$arr['uitype'])
				$arr['uitype'] = 1;
			switch($arr['uitype']){
				case 9:
					$field->columntype = 'C(3)';
					$field->typeofdata = 'N~O';
					break;
				case 7:
				case 71:
					$field->columntype = 'N(20,2)';
					$field->typeofdata = 'N~O';			
					break;
				case 5:
					$field->columntype = 'D';
					$field->typeofdata = 'D~O';			
					break;
				case 15:
					$field->columntype = 'C(255)';
					$field->typeofdata = 'V~O';			
					break;
				case 56:
					$field->columntype = 'I(1)';
					$field->typeofdata = 'C~O';
					break;
				case 70:
					$field->columntype = 'T';
					$field->typeofdata = 'T~O';			
					break;
				case 1020:
					$field->columntype = 'N(20,0)';
					$field->typeofdata = 'N~O';			
					break;
				case 1021:
					$field->columntype = 'DT';
					$field->typeofdata = 'V~O';			
					break;
				default:
					$field->columntype = 'C(255)';
					$field->typeofdata = 'V~O';	
					break;	
			}
			if ($arr['generatedtype'])
				$field->generatedtype = $arr['generatedtype'];
			if ($arr['columntype'])
				$field->columntype = $arr['columntype'];
			if ($arr['typeofdata'])
				$field->typeofdata = $arr['typeofdata'];
			$field->uitype = $arr['uitype'];
			if ($arr['masseditable'])
				$field->masseditable = $arr['masseditable'];
			else
				$field->masseditable = 0;	
			if ($arr['quickcreate']){
				$field->quickcreate = $arr['quickcreate'];
				$q = "select max(quickcreatesequence)+1 as seq from ".$table_prefix."_field where tabid  = (select tabid from ".$table_prefix."_tab where name = ?) and block = ?";
				$res = $adb->pquery($q,Array($module,$arr['block']));
				if ($res && $adb->num_rows($res)==1)
					$field->quicksequence = $adb->query_result($res,0,'seq');
			}	
			else
				$field->quickcreate = 1;	//crmv@22583
					
			//se picklist aggiungo i valori
			if ($arr['picklistvalues']){
				$field->setPicklistValues( $arr['picklistvalues'] );
			}
			if ($arr['helpinfo']){
				$field->helpinfo = $arr['helpinfo'];
			}
			$block_instance->addField($field);
			foreach ($langs as $lang_str){
				$file = "modules/$module/language/$lang_str.lang.php";
				include($file);
				$old = "/\);\s*.?>\s*/";
				$new = "'".$arr['label']."' => '".$arr['langs'][$lang_str]."',\n);\n?>";
				$contents = preg_replace($old,$new,file_get_contents($file));
				file_put_contents($file,$contents);
			}
		}
	}
 	
 	protected function hideFields($module, $fields) {
		global $adb, $table_prefix;
		
		$q = "update ".$table_prefix."_def_org_field set visible = 1
			where tabid  = (select tabid from ".$table_prefix."_tab where name = ?)
			and fieldid in (select fieldid from ".$table_prefix."_field where tabid = ".$table_prefix."_def_org_field.tabid 
			and fieldname in (".generateQuestionMarks($fields)."))";
			
		$params = Array($module,$fields);
		$adb->pquery($q,$params);
		$q = "update ".$table_prefix."_field set presence = 1
			where tabid  = (select tabid from ".$table_prefix."_tab where name = ?)
			and fieldname in (".generateQuestionMarks($fields).")";
		$params = Array($module,$fields);
		$adb->pquery($q,$params);
 	}
 	
 	protected function renameFields($module, $fields, $langs) {
		global $adb, $table_prefix, $mod_strings;
		
		foreach ($fields as $fieldname=>$arr){
			$q = "update ".$table_prefix."_field set fieldlabel=? where fieldname = ? and tabid in (select tabid from ".$table_prefix."_tab where name = ?)";
			$params = Array($arr['new'],$fieldname,$module);
			$adb->pquery($q,$params);
		}
		foreach ($langs as $lang_str){
			$file = "modules/$module/language/$lang_str.lang.php";
			include($file);
			$old_trad = $mod_strings[$arr['old']];
			$old = "/'".$arr['old']."'\s*=>\s*'".$old_trad."'\s*,/";
			$new = "'".$arr['new']."' => '".$arr['langs'][$lang_str]."',";
			$contents = preg_replace($old,$new,file_get_contents($file));
			file_put_contents($file,$contents);	
		}
	}
	
	protected function editFields($module, $fields) {
		global $adb, $table_prefix;
		
		foreach ($fields as $field=>$arr){
			if (!$arr)
				continue;
			$q = "update ".$table_prefix."_field set ";
			$tot = count($arr);
			$cnt = 1;
			$params = Array();	
			foreach ($arr as $column=>$value){		
				$q.= $column." = ?";
				$params[] = $value;
				if ($cnt < $tot)
					$q.=",";
			}
			$q .= " where fieldname = ? and tabid in (select tabid from ".$table_prefix."_tab where name = ?)";
			$params[] = $field;
			$params[] = $module;
			$adb->convert2Sql($q,$adb->flatten_array($params));
			$adb->pquery($q,$params);
		}
	}
 	
}