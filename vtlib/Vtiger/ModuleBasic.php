<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
include_once('vtlib/Vtiger/Access.php');
include_once('vtlib/Vtiger/Panel.php'); // crmv@104568
include_once('vtlib/Vtiger/Block.php');
include_once('vtlib/Vtiger/Field.php');
include_once('vtlib/Vtiger/Filter.php');
include_once('vtlib/Vtiger/Profile.php');
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Link.php');
include_once('vtlib/Vtiger/Event.php');
include_once('vtlib/Vtiger/Webservice.php');
include_once('vtlib/Vtiger/Version.php');

/**
 * Provides API to work with vtiger CRM Module
 * @package vtlib
 */
class Vtiger_ModuleBasic {
	/** ID of this instance */
	var $id = false;
	var $name = false;
	var $label = false;
	var $version= 0;
	var $minversion = false;
	var $maxversion = false;

	var $presence = 0;
	var $ownedby = 0; // 0 - Sharing Access Enabled, 1 - Sharing Access Disabled
	var $tabsequence = false;

	var $isentitytype = true; // Real module or an extension?
	var $isinventory = false; // crmv@64542
	var $isproduct = false; // crmv@64542
	var $is_mod_light = false; //crmv@106857

	var $entityidcolumn = false;
	var $entityidfield = false;

	var $basetable = false;
	var $basetableid=false;
	var $customtable=false;
	var $grouptable = false;

	const EVENT_MODULE_ENABLED     = 'module.enabled';
	const EVENT_MODULE_DISABLED    = 'module.disabled';
	const EVENT_MODULE_POSTINSTALL = 'module.postinstall';
	const EVENT_MODULE_PREUNINSTALL= 'module.preuninstall';
	const EVENT_MODULE_PREUPDATE   = 'module.preupdate';
	const EVENT_MODULE_POSTUPDATE  = 'module.postupdate';


	/**
	 * Constructor
	 */
	function __construct() {
	}

	/**
	 * Initialize this instance
	 * @access private
	 */
	function initialize($valuemap) {
		$this->id = $valuemap['tabid'];
		$this->name=$valuemap['name'];
		$this->label=$valuemap['tablabel'];
		$this->version=$valuemap['version'];

		$this->presence = $valuemap['presence'];
		$this->ownedby = $valuemap['ownedby'];
		$this->tabsequence = $valuemap['tabsequence'];
		
		$this->isentitytype = $valuemap['isentitytype'];
		
		if($this->isentitytype || $this->name == 'Users') {
			// Initialize other details too
			$this->initialize2();
		}
		$this->initializeProperties(); // crmv@64542
	}

	// crmv@105600
	/**
	 * Initialize more information of this instance
	 * @access private
	 */
	function initialize2() {
		global $adb,$table_prefix;
		
		$cache = RCache::getInstance();
		$key = "entity_table_name_".$this->id;
		$names = $cache->get($key);
		
		if (!$names) {
			$names = array();
			$result = $adb->pquery("SELECT tablename,entityidfield FROM ".$table_prefix."_entityname WHERE tabid=?", 
				Array($this->id));
			if($adb->num_rows($result)) {
				$names['tablename'] = $adb->query_result_no_html($result, 0, 'tablename');
				$names['entityidfield'] = $adb->query_result_no_html($result, 0, 'entityidfield');
			}
			$cache->set($key, $names);
		}
		
		if ($names) {
			$this->basetable = $names['tablename'];
			$this->basetableid= $names['entityidfield'];
		}
	}
	
	// crmv@64542
	/**
	 * Read the module properties
	 */
	function initializeProperties() {
		global $adb,$table_prefix;
		
		$cache = RCache::getInstance();
		$key = "module_prefs_".$this->id;
		$prefs = $cache->get($key);
		
		if (!is_array($prefs)) {
			$prefs = array();
			$result = $adb->pquery("SELECT prefname, prefvalue FROM {$table_prefix}_tab_info WHERE tabid = ?", array($this->id));
			if ($result && $adb->num_rows($result) > 0) {
				while ($row = $adb->FetchByAssoc($result, -1, false)) {
					$prefs[$row['prefname']] = $row['prefvalue'];
				}
			}
			$cache->set($key, $prefs);
		}
		
		foreach ($prefs as $pname => $value) {
			switch ($pname) {
				case 'vtiger_min_version':
					$this->minversion = $value;
					break;
				case 'vtiger_max_version':
					$this->maxversion = $value;
					break;
				case 'is_inventory':
					$this->isinventory = ($value == '1');
					break;
				case 'is_product':
					$this->isproduct = ($value == '1');
					break;
				//crmv@106857
				case 'is_mod_light':
					$this->is_mod_light = ($value == '1');
					break;
				//crmv@106857e
				default:
					// do nothing for unknown preferencies
					break;
			}
		}
		
	}
	// crmv@64542e
	// crmv@105600e

	/**
	 * Get unique id for this instance
	 * @access private
	 */
	function __getUniqueId() {
		global $adb,$table_prefix;
		$result = $adb->query("SELECT MAX(tabid) AS max_seq FROM ".$table_prefix."_tab");
		$maxseq = $adb->query_result($result, 0, 'max_seq');
		return ++$maxseq;
	}

	/**
	 * Get next sequence to use for this instance
	 * @access private
	 */
	function __getNextSequence() {
		global $adb,$table_prefix;
		$result = $adb->query("SELECT MAX(tabsequence) AS max_tabseq FROM ".$table_prefix."_tab");
		$maxtabseq = $adb->query_result($result, 0, 'max_tabseq');
		return ++$maxtabseq;
	}

	/**
	 * Initialize vtiger schema changes.
	 * @access private
	 */
	function __handleVtigerCoreSchemaChanges() {
		// Add version column to the table first
		global $table_prefix;
		Vtiger_Utils::AlterTable($table_prefix.'_tab', 'version C(10)');
	}

	/**
	 * Create this module instance
	 * @access private
	 */
	function __create($tabidtouse=false) { //crmv@36557
		global $adb,$table_prefix, $metaLogs; // crmv@49398

		self::log("Creating Module $this->name ... STARTED");
		//crmv@36557
		if ($tabidtouse !== false){
			$this->id = $tabidtouse;
			$customized = 0; // To indicate this is a Standard Module			
		}
		else{
			$this->id = $this->__getUniqueId();
			$customized = 1; // To indicate this is a Custom Module
		}
		//crmv@36557 e
		if(!$this->tabsequence) $this->tabsequence = $this->__getNextSequence();
		if(!$this->label) $this->label = $this->name;

		$customized = 1; // To indicate this is a Custom Module

		$this->__handleVtigerCoreSchemaChanges();

		$adb->pquery("INSERT INTO ".$table_prefix."_tab (tabid,name,presence,tabsequence,tablabel,modifiedby,
			modifiedtime,customized,ownedby,version) VALUES (?,?,?,?,?,?,?,?,?,?)", 
			Array($this->id, $this->name, $this->presence, $this->tabsequence, $this->label, NULL, NULL, $customized, $this->ownedby, $this->version));

		$useisentitytype = $this->isentitytype? 1 : 0;
		$adb->pquery('UPDATE '.$table_prefix.'_tab set isentitytype=? WHERE tabid=?',Array($useisentitytype, $this->id));

		if(!Vtiger_Utils::CheckTable($table_prefix.'_tab_info')) {
			Vtiger_Utils::CreateTable(
				$table_prefix.'_tab_info',
				'tabid I(19) PRIMARY, 
				prefname C(256), 
				prefvalue C(256)',
				true);
				//TODO aggiungere la fk!
				//FOREIGN KEY fk_1_vtiger_tab_info(tabid) REFERENCES vtiger_tab(tabid) ON DELETE CASCADE ON UPDATE CASCADE)',
		}
		if($this->minversion) {
			$tabResult = $adb->pquery("SELECT 1 FROM ".$table_prefix."_tab_info WHERE tabid=? AND prefname='vtiger_min_version'", array($this->id));
			if ($adb->num_rows($tabResult) > 0) {
				$adb->pquery("UPDATE ".$table_prefix."_tab_info SET prefvalue=? WHERE tabid=? AND prefname='vtiger_min_version'", array($this->minversion,$this->id));
			} else {
				$adb->pquery('INSERT INTO '.$table_prefix.'_tab_info(tabid, prefname, prefvalue) VALUES (?,?,?)', array($this->id, 'vtiger_min_version', $this->minversion));
			}
		}
		if($this->maxversion) {
			$tabResult = $adb->pquery("SELECT 1 FROM ".$table_prefix."_tab_info WHERE tabid=? AND prefname='vtiger_max_version'", array($this->id));
			if ($adb->num_rows($tabResult) > 0) {
				$adb->pquery("UPDATE ".$table_prefix."_tab_info SET prefvalue=? WHERE tabid=? AND prefname='vtiger_max_version'", array($this->maxversion,$this->id));
			} else {
				$adb->pquery('INSERT INTO '.$table_prefix.'_tab_info(tabid, prefname, prefvalue) VALUES (?,?,?)', array($this->id, 'vtiger_max_version', $this->maxversion));
			}
		}
		
		// crmv@64542 - add the inventory and product flag
		if ($this->isinventory) {
			$tabResult = $adb->pquery("SELECT 1 FROM ".$table_prefix."_tab_info WHERE tabid=? AND prefname='is_inventory'", array($this->id));
			if ($adb->num_rows($tabResult) > 0) {
				$adb->pquery("UPDATE ".$table_prefix."_tab_info SET prefvalue=? WHERE tabid=? AND prefname='is_inventory'", array($this->isinventory ? 1 : 0,$this->id));
			} else {
				$adb->pquery('INSERT INTO '.$table_prefix.'_tab_info(tabid, prefname, prefvalue) VALUES (?,?,?)', array($this->id, 'is_inventory', $this->isinventory ? 1 : 0));
			}
		}
		
		if ($this->isproduct) {
			$tabResult = $adb->pquery("SELECT 1 FROM ".$table_prefix."_tab_info WHERE tabid=? AND prefname='is_product'", array($this->id));
			if ($adb->num_rows($tabResult) > 0) {
				$adb->pquery("UPDATE ".$table_prefix."_tab_info SET prefvalue=? WHERE tabid=? AND prefname='is_product'", array($this->isproduct ? 1 : 0,$this->id));
			} else {
				$adb->pquery('INSERT INTO '.$table_prefix.'_tab_info(tabid, prefname, prefvalue) VALUES (?,?,?)', array($this->id, 'is_product', $this->isproduct ? 1 : 0));
			}
		}
		// crmv@64542e
		//crmv@106857
		if ($this->is_mod_light) {
			$result = $adb->pquery("SELECT 1 FROM {$table_prefix}_tab_info WHERE tabid=? AND prefname=?", array($this->id, 'is_mod_light'));
			if ($adb->num_rows($result) > 0) {
				$adb->pquery("UPDATE {$table_prefix}_tab_info SET prefvalue=? WHERE tabid=? AND prefname=?", array($this->is_mod_light ? 1 : 0, $this->id, 'is_mod_light'));
			} else {
				$adb->pquery("INSERT INTO {$table_prefix}_tab_info(tabid, prefname, prefvalue) VALUES (?,?,?)", array($this->id, 'is_mod_light', $this->is_mod_light ? 1 : 0));
			}
		}
		//crmv@106857e
		
		Vtiger_Profile::initForModule($this);
		
		self::syncfile();

		if($this->isentitytype) {
			Vtiger_Access::initSharing($this);
		}

		SDK::file2DbLanguages($this->name);	//crmv@sdk-18430

		// crmv@90924
		// add the partition if needed
		if (PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) {
			if (!$adb->hasPartition("{$table_prefix}_crmentity", "p_{$this->id}")) {
				// crmv@197204
				$checkPartitionValue = $adb->checkPartitionValue("{$table_prefix}_crmentity", $this->name);
				if (!$checkPartitionValue) {
					$sql = "ALTER TABLE {$table_prefix}_crmentity ADD PARTITION (PARTITION p_{$this->id} VALUES IN (?))";
					$adb->pquery($sql, array($this->name));
				}
				// crmv@197204e
			}
		}
		// crmv@90924e
		
		//crmv@47905bis
		$cache = Cache::getInstance('installed_modules');
		$cache->clear();
		//crmv@47905bise
		
		if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_ADDMODULE, $this->id, array('module'=>$this->name)); // crmv@49398
		self::log("Creating Module $this->name ... DONE");
	}

	/**
	 * Update this instance
	 * @access private
	 */
	function __update() {
		self::log("Updating Module $this->name ... DONE");
	}

	/**
	 * Delete this instance
	 * @access private
	 */
	function __delete() {
		Vtiger_Module::fireEvent($this->name, 
			Vtiger_Module::EVENT_MODULE_PREUNINSTALL);

		global $adb,$table_prefix, $metaLogs; // crmv@49398
		if($this->isentitytype) {		
			$this->unsetEntityIdentifier();
			$this->deleteRelatedLists();
		}

		// crmv@90924
		// drop the partition if needed
		if (PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) {
			if ($adb->hasPartition("{$table_prefix}_crmentity", "p_{$this->id}")) {
				$adb->query("ALTER TABLE {$table_prefix}_crmentity DROP PARTITION p_{$this->id}");
			}
		}
		// crmv@90924e

		$adb->pquery("DELETE FROM ".$table_prefix."_tab WHERE tabid=?", Array($this->id));
		$adb->pquery("DELETE FROM ".$table_prefix."_tab_info WHERE tabid=?", Array($this->id));	//crmv@113771
		
		//crmv@27624	crmv@47905bis
		$cache = Cache::getInstance('installed_modules');
		$cache->clear();
		//crmv@27624	crmv@47905bise
		
		FieldUtils::invalidateCache($this->id); // crmv@193294
		
		if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_DELMODULE, $this->id, array('module'=>$this->name)); // crmv@49398
		self::log("Deleting Module $this->name ... DONE");
	}

	/**
	 * Update module version information
	 * @access private
	 */
	function __updateVersion($newversion) {
		$this->__handleVtigerCoreSchemaChanges();
		global $adb,$table_prefix;
		$adb->pquery("UPDATE ".$table_prefix."_tab SET version=? WHERE tabid=?", Array($newversion, $this->id));
		$this->version = $newversion;		
		//crmv@146434
		$cache = RCache::getInstance();
		$cache->clear("vtmodule_instances_".$this->id);
		$cache->clear("vtmodule_instances_".$this->name);
		//crmv@146434e
		self::log("Updating version to $newversion ... DONE");
	}

	/**
	 * Save this instance
	 */
	function save($tabidtouse=false) { //crmv@36557
		if($this->id) $this->__update();
		else $this->__create($tabidtouse); //crmv@36557
		return $this->id;
	}

	/**
	 * Delete this instance
	 */
	function delete() {
		if($this->isentitytype) {
			Vtiger_Access::deleteSharing($this);
			Vtiger_Access::deleteTools($this);
			Vtiger_Filter::deleteForModule($this);
			Vtiger_Block::deleteForModule($this);
			Vtiger_Panel::deleteForModule($this);	//crmv@146434
		}
		$this->__delete();
		Vtiger_Profile::deleteForModule($this);
		Vtiger_Link::deleteAll($this->id);
		Vtiger_Menu::detachModule($this);
		SDK::deleteLanguage($this->name);	//crmv@sdk-18430
		self::syncfile();
	}

	/**
	 * Initialize table required for the module
	 * @param String Base table name (default modulename in lowercase)
	 * @param String Base table column (default modulenameid in lowercase)
	 *
	 * Creates basetable, customtable, grouptable <br>
	 * customtable name is basetable + 'cf'<br>
	 * grouptable name is basetable + 'grouprel'<br>
	 */
	function initTables($basetable=false, $basetableid=false) {
		global $table_prefix;
		$this->basetable = $basetable;
		$this->basetableid=$basetableid;

		// Initialize tablename and index column names
		$lcasemodname = strtolower($this->name);
		if(!$this->basetable) $this->basetable = $table_prefix."_$lcasemodname";
		if(!$this->basetableid)$this->basetableid=$lcasemodname . "id";

		if(!$this->customtable)$this->customtable = $this->basetable . "cf";
		if(!$this->grouptable)$this->grouptable = $this->basetable."grouprel";

		Vtiger_Utils::CreateTable($this->basetable,"$this->basetableid I(19) PRIMARY",true);
		Vtiger_Utils::CreateTable($this->customtable,"$this->basetableid I(19) PRIMARY", true);
	}

	/**
	 * Set entity identifier field for this module
	 * @param Vtiger_Field Instance of field to use
	 */
	function setEntityIdentifier($fieldInstance) {
		global $adb,$table_prefix;

		if($this->basetableid) {
			if(!$this->entityidfield) $this->entityidfield = $this->basetableid;
			if(!$this->entityidcolumn)$this->entityidcolumn= $this->basetableid;
		}
		//crmv@30456
		if(strpos($fieldInstance->table, 'TABLEPREFIX') !== false){
			$fieldInstance->table=str_replace('TABLEPREFIX', $table_prefix, $fieldInstance->table);
		}
		//crmv@30456e
		if($this->entityidfield && $this->entityidcolumn) {
			$adb->pquery("INSERT INTO ".$table_prefix."_entityname(tabid, modulename, tablename, fieldname, entityidfield, entityidcolumn) VALUES(?,?,?,?,?,?)",
				Array($this->id, $this->name, $fieldInstance->table, $fieldInstance->name, $this->entityidfield, $this->entityidcolumn));
			self::log("Setting entity identifier ... DONE");
		}
	}

	/**
	 * Unset entity identifier information
	 */
	function unsetEntityIdentifier() {
		global $adb,$table_prefix;
		$adb->pquery("DELETE FROM ".$table_prefix."_entityname WHERE tabid=?", Array($this->id));
		self::log("Unsetting entity identifier ... DONE");
	}

	/**
	 * Delete related lists information
	 */
	function deleteRelatedLists() {
		global $adb,$table_prefix;
		$adb->pquery("DELETE FROM ".$table_prefix."_relatedlists WHERE tabid=?", Array($this->id));
		self::log("Deleting related lists ... DONE");
	}

	/**
	 * Configure default sharing access for the module
	 * @param String Permission text should be one of ['Public_ReadWriteDelete', 'Public_ReadOnly', 'Public_ReadWrite', 'Private']
	 */
	// crmv@193317
	function setDefaultSharing($permission_text='Public_ReadWriteDelete',$editstatus=0) {
		Vtiger_Access::setDefaultSharing($this, $permission_text, $editstatus);
	}
	// crmv@193317e

	/**
	 * Allow module sharing control
	 */
	function allowSharing() {
		Vtiger_Access::allowSharing($this, true);
	}
	/**
	 * Disallow module sharing control
	 */
	function disallowSharing() {
		Vtiger_Access::allowSharing($this, false);
	}

	/**
	 * Enable tools for this module
	 * @param mixed String or Array with value ['Import', 'Export', 'Merge']
	 */
	function enableTools($tools) {
		if(is_string($tools)) {
			$tools = Array(0 => $tools);
		}

		foreach($tools as $tool) {
			Vtiger_Access::updateTool($this, $tool, true);
		}
	}

	/**
	 * Disable tools for this module
	 * @param mixed String or Array with value ['Import', 'Export', 'Merge']
	 */
	function disableTools($tools) {
		if(is_string($tools)) {
			$tools = Array(0 => $tools);
		}
		foreach($tools as $tool) {
			Vtiger_Access::updateTool($this, $tool, false);
		}
	}

	/**
	 * Add block to this module
	 * @param Vtiger_Block Instance of block to add
	 */
	function addBlock($blockInstance) {
		$blockInstance->save($this);
		return $this;
	}
	
	// crmv@104975
	/**
	 * Add panel to this module
	 * @param Vtiger_Panel Instance of panel to add
	 */
	function addPanel($panelInstance) {
		$panelInstance->save($this);
		return $this;
	}
	// crmv@104975e

	/**
	 * Add filter to this module
	 * @param Vtiger_Filter Instance of filter to add
	 */
	function addFilter($filterInstance) {
		$filterInstance->save($this);
		return $this;
	}

	/**
	 * Get all the fields of the module or block
	 * @param Vtiger_Block Instance of block to use to get fields, false to get all the block fields
	 */
	function getFields($blockInstance=false) {
		$fields = false;
		if($blockInstance) $fields = Vtiger_Field::getAllForBlock($blockInstance, $this);
		else $fields = Vtiger_Field::getAllForModule($this);
		return $fields;
	}

	/**
	 * Helper function to log messages
	 * @param String Message to log
	 * @param Boolean true appends linebreak, false to avoid it
	 * @access private
	 */
	static function log($message, $delimit=true) {
		Vtiger_Utils::Log($message, $delimit);
	}

	/**
	 * Synchronize the menu information to flat file
	 * @access private
	 */
	static function syncfile() {
		self::log("Updating tabdata file ... ", false);
		//crmv@18160
		if (VteSession::get('skip_recalculate'))
			return;	
		//crmv@18160 end
		create_tab_data_file();
		self::log("DONE");
	}
}