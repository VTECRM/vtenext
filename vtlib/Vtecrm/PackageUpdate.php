<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('vtlib/Vtecrm/PackageImport.php');

class Vtecrm_PackageUpdate extends Vtecrm_PackageImport {

	var $_migrationinfo = false;
	
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}
	
	/**
	 * Initialize Update
	 * @access private
	 */
	function initUpdate($moduleInstance, $zipfile, $overwrite) {
		$module = $this->getModuleNameFromZip($zipfile);
	
		if(!$moduleInstance || $moduleInstance->name != $module) {
			self::log('Module name mismatch!');
			return false;
		}
	
		if($module != null) {
			$unzip = new Vtecrm_Unzip($zipfile, $overwrite);
	
			// Unzip selectively
			$unzip->unzipAllEx( ".",
					Array(
							//crmv@sdk
							'include' => Array('templates', "modules/$module", 'sdk'),	// We don't need manifest.xml
							//crmv@sdk e
							//'exclude' => Array('manifest.xml')                // DEFAULT: excludes all not in include
					),
					Array(
							// Templates folder to be renamed while copying
							'templates' => "Smarty/templates/modules/$module",
							'sdk' => 'modules/SDK/tmp'	//crmv@sdk
					),
					// Cron folder to be renamed while copying
					Array('cron' => "cron/modules/$module")
			);
	
			// If data is not yet available
			if(empty($this->_modulexml)) {
				$this->__parseManifestFile($unzip);
			}
	
			if($unzip) $unzip->close();
		}
		return $module;
	}
	
	/**
	 * Update Module from zip file
	 * @param Vtecrm_Module Instance of the module to update
	 * @param String Zip file name
	 * @param Boolean True for overwriting existing module
	 */
	function update($moduleInstance, $zipfile, $overwrite=true) {
		$module = $this->initUpdate($moduleInstance, $zipfile, $overwrite);
	
		if($module) {
			// Call module update function
			$this->update_Module($moduleInstance);
		}
	}
	
	/**
	 * Update Module
	 * @access private
	 */
	function update_Module($moduleInstance) {
		$tabname = $this->_modulexml->name;
		$tablabel= $this->_modulexml->label;
		$parenttab=$this->_modulexml->parent;
		$tabversion=$this->_modulexml->version;
	
		$isextension= false;
		if(!empty($this->_modulexml->type)) {
			$type = strtolower($this->_modulexml->type);
			if($type == 'extension' || $type == 'language')
				$isextension = true;
		}
	
		Vtecrm_Module::fireEvent($moduleInstance->name,
		Vtecrm_Module::EVENT_MODULE_PREUPDATE);
	
		// TODO Handle module property changes like menu, label etc...
		/*if(!empty($parenttab) && $parenttab != '') {
		 $menuInstance = Vtecrm_Menu::getInstance($parenttab);
		$menuInstance->addModule($moduleInstance);
		}*/
	
		$this->handle_Migration($this->_modulexml, $moduleInstance);
	
		//$this->update_Tables($this->_modulexml);	//crmv@fix
		$this->update_Blocks($this->_modulexml, $moduleInstance);
		$this->update_CustomViews($this->_modulexml, $moduleInstance);
		$this->update_SharingAccess($this->_modulexml, $moduleInstance);
		$this->update_Events($this->_modulexml, $moduleInstance);
		$this->update_Actions($this->_modulexml, $moduleInstance);
		$this->update_RelatedLists($this->_modulexml, $moduleInstance);
	
		SDK::importPackage($this->_modulexml, $moduleInstance);	//crmv@sdk
	
		$moduleInstance->__updateVersion($tabversion);
	
		Vtecrm_Module::fireEvent($moduleInstance->name,
		Vtecrm_Module::EVENT_MODULE_POSTUPDATE,$tabversion);	//crmv@fix
	}
	
	/**
	 * Parse migration information from manifest
	 * @access private
	 */
	function parse_Migration($modulenode) {
		if(!$this->_migrations) {
			$this->_migrations = Array();
			if(!empty($modulenode->migrations) &&
					!empty($modulenode->migrations->migration)) {
						foreach($modulenode->migrations->migration as $migrationnode) {
							$migrationattrs = $migrationnode->attributes();
							$migrationversion = $migrationattrs['version'];
							$this->_migrations["$migrationversion"] = $migrationnode;
						}
					}
					// Sort the migration details based on version
					if(count($this->_migrations) > 1) {
						uksort($this->_migrations, 'version_compare');
					}
		}
	}
	
	/**
	 * Handle migration of the module
	 * @access private
	 */
	function handle_Migration($modulenode, $moduleInstance) {
		// TODO Handle module migration SQL
		$this->parse_Migration($modulenode);
		$cur_version = $moduleInstance->version;
		foreach($this->_migrations as $migversion=>$migrationnode) {
			// Perform migration only for higher version than current
			if(version_compare($cur_version, $migversion, '<')) {
				self::log("Migrating to $migversion ... STARTED");
				if(!empty($migrationnode->tables) && !empty($migrationnode->tables->table)) {
					foreach($migrationnode->tables->table as $tablenode) {
						$tablename = $tablenode->name;
						$tablesql  = "$tablenode->sql"; // Convert to string
	
						// Skip SQL which are destructive
						if(Vtecrm_Utils::IsDestructiveSql($tablesql)) {
							self::log("SQL: $tablesql ... SKIPPED");
						} else {
							// Supress any SQL query failures
							self::log("SQL: $tablesql ... ", false);
							Vtecrm_Utils::ExecuteQuery($tablesql, true);
							self::log("DONE");
						}
					}
				}
				self::log("Migrating to $migversion ... DONE");
			}
		}
	}
	
	/**
	 * Update Tables of the module
	 * @access private
	 */
	function update_Tables($modulenode) {
		$this->import_Tables($modulenode);
	}
	
	/**
	 * Update Blocks of the module
	 * @access private
	 */
	function update_Blocks($modulenode, $moduleInstance) {
		if(empty($modulenode->blocks) || empty($modulenode->blocks->block)) return;
	
		foreach($modulenode->blocks->block as $blocknode) {
			$blockInstance = Vtecrm_Block::getInstance($blocknode->label, $moduleInstance);
			if(!$blockInstance) {
				$blockInstance = $this->import_Block($modulenode, $moduleInstance, $blocknode);
			} else {
				$this->update_Block($modulenode, $moduleInstance, $blocknode, $blockInstance);
			}
	
			$this->update_Fields($blocknode, $blockInstance, $moduleInstance);
		}
	}
	
	/**
	 * Update Block of the module
	 * @access private
	 */
	function update_Block($modulenode, $moduleInstance, $blocknode, $blockInstance) {
		// TODO Handle block property update
	}
	
	/**
	 * Update Fields of the module
	 * @access private
	 */
	function update_Fields($blocknode, $blockInstance, $moduleInstance) {
		if(empty($blocknode->fields) || empty($blocknode->fields->field)) return;
	
		foreach($blocknode->fields->field as $fieldnode) {
			$fieldInstance = Vtecrm_Field::getInstance($fieldnode->fieldname, $moduleInstance);
			if(!$fieldInstance) {
				$fieldInstance = $this->import_Field($blocknode, $blockInstance, $moduleInstance, $fieldnode);
			} else {
				$this->update_Field($blocknode, $blockInstance, $moduleInstance, $fieldnode, $fieldInstance);
			}
			$this->__AddModuleFieldToCache($moduleInstance, $fieldInstance->name, $fieldInstance);
		}
	}
	
	/**
	 * Update Field of the module
	 * @access private
	 */
	function update_Field($blocknode, $blockInstance, $moduleInstance, $fieldnode, $fieldInstance) {
		// TODO Handle field property update
	
		if(!empty($fieldnode->helpinfo)) $fieldInstance->setHelpInfo($fieldnode->helpinfo);
		if(!empty($fieldnode->masseditable)) $fieldInstance->setMassEditable($fieldnode->masseditable);
	}
	
	/**
	 * Import Custom views of the module
	 * @access private
	 */
	function update_CustomViews($modulenode, $moduleInstance) {
		if(empty($modulenode->customviews) || empty($modulenode->customviews->customview)) return;
		foreach($modulenode->customviews->customview as $customviewnode) {
			$filterInstance = Vtecrm_Filter::getInstance($customviewnode->viewname, $moduleInstance);
			if(!$filterInstance) {
				$filterInstance = $this->import_CustomView($modulenode, $moduleInstance, $customviewnode);
			} else {
				$this->update_CustomView($modulenode, $moduleInstance, $customviewnode, $filterInstance);
			}
		}
	}
	
	/**
	 * Update Custom View of the module
	 * @access private
	 */
	function update_CustomView($modulenode, $moduleInstance, $customviewnode, $filterInstance) {
		// TODO Handle filter property update
	}
	
	/**
	 * Update Sharing Access of the module
	 * @access private
	 */
	function update_SharingAccess($modulenode, $moduleInstance) {
		if(empty($modulenode->sharingaccess)) return;
	
		// TODO Handle sharing access property update
	}
	
	/**
	 * Update Events of the module
	 * @access private
	 */
	function update_Events($modulenode, $moduleInstance) {
		if(empty($modulenode->events) || empty($modulenode->events->event))	return;
	
		if(Vtecrm_Event::hasSupport()) {
			foreach($modulenode->events->event as $eventnode) {
				$this->update_Event($modulenode, $moduleInstance, $eventnode);
			}
		}
	}
	
	/**
	 * Update Event of the module
	 * @access private
	 */
	function update_Event($modulenode, $moduleInstance, $eventnode) {
		//Vtecrm_Event::register($moduleInstance, $eventnode->eventname, $eventnode->classname, $eventnode->filename);
		// TODO Handle event property update
	}
	
	/**
	 * Update actions of the module
	 * @access private
	 */
	function update_Actions($modulenode, $moduleInstance) {
		if(empty($modulenode->actions) || empty($modulenode->actions->action)) return;
		foreach($modulenode->actions->action as $actionnode) {
			$this->update_Action($modulenode, $moduleInstance, $actionnode);
		}
	}
	
	/**
	 * Update action of the module
	 * @access private
	 */
	function update_Action($modulenode, $moduleInstance, $actionnode) {
		// TODO Handle action property update
	}
	
	/**
	 * Update related lists of the module
	 * @access private
	 */
	function update_RelatedLists($modulenode, $moduleInstance) {
		if(empty($modulenode->relatedlists) || empty($modulenode->relatedlists->relatedlist)) return;
		//$moduleInstance->deleteRelatedLists();	//crmv@27882
		foreach($modulenode->relatedlists->relatedlist as $relatedlistnode) {
			$relModuleInstance = $this->update_Relatedlist($modulenode, $moduleInstance, $relatedlistnode);
		}
	}
	
	/**
	 * Import related list of the module.
	 * @access private
	 */
	function update_Relatedlist($modulenode, $moduleInstance, $relatedlistnode) {
		$relModuleInstance = Vtecrm_Module::getInstance($relatedlistnode->relatedmodule);
		$label = $relatedlistnode->label;
		$actions = false;
		if(!empty($relatedlistnode->actions) && !empty($relatedlistnode->actions->action)) {
			$actions = Array();
			foreach($relatedlistnode->actions->action as $actionnode) {
				$actions[] = "$actionnode";
			}
		}
		if($relModuleInstance) {
			$moduleInstance->unsetRelatedList($relModuleInstance, "$label", "$relatedlistnode->function");
			$moduleInstance->setRelatedList($relModuleInstance, "$label", $actions, "$relatedlistnode->function");
		}
		return $relModuleInstance;
	}
	}
	
