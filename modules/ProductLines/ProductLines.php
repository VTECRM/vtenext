<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@44323 */

class ProductLines extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	var $table_name;
	var $table_index= 'productlineid';
	var $column_fields = Array();

	/** Indicator if this is a custom module or standard module */
	var $IsCustomModule = false;

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array();

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array();

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array();

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array ();
	var $list_fields_name = Array(
		/* Format: Field Label => fieldname */
		'ProductLineName'=> 'productlinename',
		'Assigned To' => 'assigned_user_id'
	);

	// Make the field link to detail view from list view (Fieldname)
	var $list_link_field = 'productlinename';

	// For Popup listview and UI type support
	var $search_fields = Array();
	var $search_fields_name = Array(
		/* Format: Field Label => fieldname */
		'ProductLineName'=> 'productlinename'
	);

	// For Popup window record selection
	var $popup_fields = Array('productlinename');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array();

	// For Alphabetical search
	var $def_basicsearch_col = 'productlinename';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'productlinename';

	// Required Information for enabling Import feature
	var $required_fields = Array('productlinename'=>1);

	var $default_order_by = 'productlinename';
	var $default_sort_order='ASC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('createdtime', 'modifiedtime', 'productlinename');
	//crmv@10759
	var $search_base_field = 'productlinename';
	//crmv@10759 e

	function __construct() {
		global $log, $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix.'_productlines';
		$this->customFieldTable = Array($table_prefix.'_productlinescf', 'productlineid');
		$this->entity_table = $table_prefix."_crmentity";
		$this->tab_name = Array($table_prefix.'_crmentity', $table_prefix.'_productlines', $table_prefix.'_productlinescf');
		$this->tab_name_index = Array(
			$table_prefix.'_crmentity' => 'crmid',
			$table_prefix.'_productlines'   => 'productlineid',
			$table_prefix.'_productlinescf' => 'productlineid'
		);
		$this->list_fields = Array(
			/* Format: Field Label => Array(tablename, columnname) */
			// tablename should not have prefix 'vte_'
			'ProductLineName'=> Array($table_prefix.'_productlines', 'productlinename'),
			'Assigned To' => Array($table_prefix.'_crmentity','smownerid')
		);
		$this->search_fields = Array(
			/* Format: Field Label => Array(tablename, columnname) */
			// tablename should not have prefix 'vte_'
			'ProductLineName'=> Array($table_prefix.'_productlines', 'productlinename')
		);
		$this->column_fields = getColumnFields(get_class()); //crmv@146187
		$this->db = PearDatabase::getInstance();
		$this->log = $log;
	}


	function save_module($module) {
	}

	/**
	 * Return query to use based on given modulename, fieldname
	 * Useful to handle specific case handling for Popup
	 */
	function getQueryByModuleField($module, $fieldname, $srcrecord) {
		// $srcrecord could be empty
	}

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		global $adb,$table_prefix;
		require_once('modules/Update/Update.php');

		if($event_type == 'module.postinstall') {

			$adb->pquery("UPDATE {$table_prefix}_tab SET customized=0 WHERE name=?", array($modulename));

			if(!Vtecrm_Utils::CheckTable($this->table_name)) {
				Vtecrm_Utils::CreateTable(
					$this->table_name,
					"{$this->table_index} I(19) PRIMARY",
					true);
			}
			if(!Vtecrm_Utils::CheckTable($this->customFieldTable[0])) {
			Vtecrm_Utils::CreateTable(
				$this->customFieldTable[0],
				"{$this->customFieldTable[1]} I(19) PRIMARY",
				true);
			}

			$module = $modulename;
			$Mod = Vtecrm_Module::getInstance($module);
			$Mod->entityidcolumn = $this->table_index;
			$Mod->entityidfield = $this->table_index;
			$Mod->disableTools(Array('Import', 'Export', 'DuplicatesHandling'));
			$Mod->initWebservice();
			//$Mod->hide(array('hide_module_manager'=>1, 'hide_profile'=>1));

			// check and add related
			$res = $adb->pquery("select * from {$table_prefix}_relatedlists where tabid = ? and related_tabid = ?", array($Mod->id, getTabid('Products')));
			if ($res && $adb->num_rows($res) == 0) {
				$prod = Vtecrm_Module::getInstance('Products');
				$Mod->setRelatedList($prod, 'Products', Array('ADD'), 'get_dependents_list');
			}

			SDK::file2DbLanguages($module);

			$blocks = array(
				'LBL_PRODUCTLINES_INFORMATION'		=> array('module'=>$module, 'label'=>'LBL_PRODUCTLINES_INFORMATION'),
				'LBL_DESCRIPTION_INFORMATION'		=> array('module'=>$module, 'label'=>'LBL_DESCRIPTION_INFORMATION'),
			);
			$blockRet = Update::create_blocks($blocks);

			$fields = array(
				'productlinename'		=> array('module'=>$module, 'block'=>'LBL_PRODUCTLINES_INFORMATION', 'name'=>'productlinename', 'label'=>'ProductLineName',   'table'=>"{$table_prefix}_productlines",	'columntype'=>'C(255)', 'typeofdata'=>'V~O',    'uitype'=>1, 'quickcreate'=>0),
				'assigned_user_id'		=> array('module'=>$module, 'block'=>'LBL_PRODUCTLINES_INFORMATION', 'name'=>'assigned_user_id', 'label'=>'Assigned To',   'table'=>"{$table_prefix}_crmentity",	'column'=>'smownerid',	'columntype'=>'I(19)', 'typeofdata'=>'I~M',    'uitype'=>53, 'quickcreate'=>0 ),
				'description'			=> array('module'=>$module, 'block'=>'LBL_DESCRIPTION_INFORMATION', 'name'=>'description', 'label'=>'Description',   'table'=>"{$table_prefix}_productlines",	'columntype'=>'X', 'typeofdata'=>'V~O',    'uitype'=>19, 'quickcreate'=>0 ),
				'yearly_budget'			=> array('module'=>$module, 'block'=>'LBL_PRODUCTLINES_INFORMATION', 'name'=>'yearly_budget', 'label'=>'YearlyBudget',   'table'=>"{$table_prefix}_productlines",	'columntype'=>'N(25.2)', 'typeofdata'=>'N~O',    'uitype'=>71),

				'productlineid'			=> array('module'=>'Products', 'block'=>'LBL_PRODUCT_INFORMATION', 'name'=>'productlineid', 'label'=>'ProductLine',   'table'=>"{$table_prefix}_products",	'columntype'=>'I(19)', 'typeofdata'=>'I~O',    'uitype'=>10, 'quickcreate'=>0, 'relatedModules'=>array('ProductLines') ),
			);
			$fieldRet = Update::create_fields($fields);
			
			$adb->pquery("update {$table_prefix}_field set masseditable = ? where tabid = ? and fieldname in (?,?,?)", array(1,$Mod->id,'assigned_user_id','description','yearly_budget'));	//crmv@122075

			$Mod->setEntityIdentifier($fieldRet['productlinename']);

			$filters = array(
				'All'			=> array('module'=>$module, 'name'=>'All',	'isdefault'=>true,		'fields'=>array('productlinename', 'assigned_user_id')),
			);
			$filtRet = Update::create_filters($filters);

			//crmv@29617
			$result = $adb->pquery('SELECT isentitytype FROM '.$table_prefix.'_tab WHERE name = ?',array($modulename));
			if ($result && $adb->num_rows($result) > 0 && $adb->query_result($result,0,'isentitytype') == '1') {
				$ModCommentsModuleInstance = Vtecrm_Module::getInstance('ModComments');
				if ($ModCommentsModuleInstance) {
					$ModCommentsFocus = CRMEntity::getInstance('ModComments');
					$ModCommentsFocus->addWidgetTo($modulename);
				}

				// crmv@164120 - removed code
				// crmv@164122 - removed code

			}
			//crmv@29617e

		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
	}

}
?>