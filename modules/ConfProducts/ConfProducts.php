<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@198024 */

class ConfProducts extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	var $table_name;
	var $table_index= 'confproductid';
	var $column_fields = Array();

	/** Indicator if this is a custom module or standard module */
	var $IsCustomModule = true;

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
		'Product Name'=> 'productname',
		'Assigned To' => 'assigned_user_id'
	);

	// Make the field link to detail view from list view (Fieldname)
	var $list_link_field = 'productname';

	// For Popup listview and UI type support
	var $search_fields = Array();
	var $search_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Product Name'=> 'productname'
	);

	// For Popup window record selection
	var $popup_fields = Array('productname');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array();

	// For Alphabetical search
	var $def_basicsearch_col = 'productname';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'productname';

	// Required Information for enabling Import feature
	var $required_fields = Array('productname'=>1);

	var $default_order_by = 'productname';
	var $default_sort_order='ASC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('assigned_user_id', 'createdtime', 'modifiedtime', 'productname'); // crmv@177975
	//crmv@10759
	var $search_base_field = 'productname';
	//crmv@10759 e

	function __construct() {
		global $log, $table_prefix; // crmv@64542
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix.'_confproducts';
		$this->customFieldTable = Array($table_prefix.'_confproductscf', 'confproductid');
		$this->entity_table = $table_prefix."_crmentity";
		$this->tab_name = Array($table_prefix.'_crmentity', $table_prefix.'_confproducts', $table_prefix.'_confproductscf');
		$this->tab_name_index = Array(
			$table_prefix.'_crmentity' => 'crmid',
			$table_prefix.'_confproducts'   => 'confproductid',
			$table_prefix.'_confproductscf' => 'confproductid'
		);
		$this->list_fields = Array(
			/* Format: Field Label => Array(tablename, columnname) */
			// tablename should not have prefix 'vte_'
			'Product Name'=> Array($table_prefix.'_confproducts', 'productname'),
			'Assigned To' => Array($table_prefix.'_crmentity','smownerid')
		);
		$this->search_fields = Array(
			/* Format: Field Label => Array(tablename, columnname) */
			// tablename should not have prefix 'vte_'
			'Product Name'=> Array($table_prefix.'_confproducts', 'productname')
		);
		$this->column_fields = getColumnFields(get_class()); // crmv@64542
		$this->db = PearDatabase::getInstance();
		$this->log = $log;
	}

	// crmv@64542
	function save_module($module) {
		$this->updateFieldIds();
	}
	// crmv@64542e

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
		
		if ($event_type == 'module.postinstall') {
			$moduleInstance = Vtecrm_Module::getInstance($modulename);
			
			$ModCommentsModuleInstance = Vtecrm_Module::getInstance('ModComments');
			if ($ModCommentsModuleInstance) {
				$ModCommentsFocus = CRMEntity::getInstance('ModComments');
				$ModCommentsFocus->addWidgetTo($modulename);
			}
	
			$MyNotesModuleInstance = Vtecrm_Module::getInstance('MyNotes');
			if ($MyNotesModuleInstance) {
				$MyNotesCommonFocus = CRMEntity::getInstance('MyNotes');
				$MyNotesCommonFocus->addWidgetTo($modulename);
			}
			
			//crmv@92272
			$ProcessesFocus = CRMEntity::getInstance('Processes');
			$ProcessesFocus->enable($modulename);
			//crmv@92272e
			
			//crmv@105882 - initialize home for all users
			require_once('include/utils/ModuleHomeView.php');
			$MHW = ModuleHomeView::install($modulename);
			//crmv@105882e
			
			// add field to products
			require_once('modules/Update/Update.php');
			$fields = [
				'confproductid' => ['module' => 'Products', 'block' => 'LBL_PRODUCT_INFORMATION', 'name' => 'confproductid', 'label' => 'Variant of', 'uitype' => 10, 'columntype'=>'I(19)', 'typeofdata'=>'I~O', 'relatedModules' => ['ConfProducts']],
			];
			Update::create_fields($fields);
			
			// add column to all filter in products
			require_once('vtlib/Vtecrm/Module.php');
			require_once('vtlib/Vtecrm/Filter.php');
			require_once('vtlib/Vtecrm/Field.php');
			$prodModule = Vtecrm_Module::getInstance('Products');
			$prodFilter = Vtecrm_Filter::getInstance('All', $prodModule);
			if ($prodFilter) {
				$confField = Vtecrm_Field::getInstance('confproductid', $prodModule);
				if ($confField) {
					// check if already existing
					$res = $adb->pquery("SELECT * FROM {$table_prefix}_cvcolumnlist WHERE cvid = ? AND columnname LIKE '{$table_prefix}_products:confproductid:%'", array($prodFilter->id));
					if ($res && $adb->num_rows($res) == 0) {
						$prodFilter->addField($confField, 2);
					}
				}
			}
			
			// create the table field
			$this->createAttributesTable($modulename);
			
			// add the sdk return function
			SDK::setPopupReturnFunction('Products', 'confproductid', 'modules/SDK/src/ReturnFunct/ReturnConfProd.php');
			
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
	
	/**
	 * Create the table field to define products attributes
	 */
	protected function createAttributesTable($modulename) {
		global $adb, $table_prefix;
		
		$mlpa = getTabid('ModLightProdAttr');
		if (empty($mlpa)) {
			$myself = Vtecrm_Module::getInstance($modulename);
			$block = Vtecrm_Block::getInstance('LBL_ATTRIBUTES_INFORMATION', $myself);
			$blockid = $block->id;
			
			$properties = array(
				'label' => 'Attributes',
				'columns' => Zend_Json::encode(array(
					array(
						'fieldname' => 'attr_name',
						'label' => 'Name',
						'uitype' => 1,
						'readonly' => 1,
						'mandatory' => true,
						'newline' => false,
					),
					array(
						'fieldname' => 'attr_values',
						'label' => 'Attribute values',
						'uitype' => 21,
						'readonly' => 1,
						'mandatory' => true,
						'newline' => false,
						'helpinfo' => 'LBL_ATTR_VALUES_HELPINFO', // crmv@199115
					),
					// crmv@203591
					array(
						'fieldname' => 'external_code',
						'label' => 'External Code',
						'uitype' => 1112,
						'columntype' => 'C(50)',
						'displaytype' => 3,
						'readonly' => 1,
						'mandatory' => false,
						'newline' => false,
					),
					// crmv@203591e
				)),
			);
			
			$MLUtils = ModLightUtils::getInstance();
			$MLUtils->addTableField($blockid, null, $properties, 'ProdAttr');
			
			// add a special column to keep track of the fieldid
			$adb->addColumnToTable($table_prefix.'_modlightprodattr', 'fieldid', 'INT(11) AFTER parent_id');
			
			// with an index
			$sql = $adb->datadict->CreateIndexSQL('mlprodattr_fieldid_idx', $table_prefix.'_modlightprodattr', 'fieldid');
			if ($sql) $adb->datadict->ExecuteSQLArray($sql);
			
			// crmv@203591
			// index on external code as well
			$sql = $adb->datadict->CreateIndexSQL('mlprodattr_extcode_idx', $table_prefix.'_modlightprodattr', 'external_code');
			if ($sql) $adb->datadict->ExecuteSQLArray($sql);
			// crmv@203591e
			
			// and additional translation
			$trans = array(
				'ModLightProdAttr' => array(
					'it_it' => array(
						'Name' => 'Nome',
						'Attribute values' => 'Valori possibili',
						'External Code' => 'Codice esterno',
						'LBL_ATTR_VALUES_HELPINFO' => 'Inserire i valori andando a capo', // crmv@199115
					),
					'en_us' => array(
						'Name' => 'Name',
						'Attribute values' => 'Possible values',
						'External Code' => 'External code',
						'LBL_ATTR_VALUES_HELPINFO' => 'Enter the values by going in a new line', // crmv@199115
					),

				),
			);
			$languages = vtlib_getToggleLanguageInfo();
			foreach ($trans as $module=>$modlang) {
				foreach ($modlang as $lang=>$translist) {
					if (array_key_exists($lang,$languages)) {
						foreach ($translist as $label=>$translabel) {
							SDK::setLanguageEntry($module, $lang, $label, $translabel);
						}
					}
				}
			}

		}
	}
	
	/**
	 * Fill the missing fieldids in the attribute table
	 */
	public function updateFieldIds() {
		global $adb, $table_prefix;
		
		$res = $adb->query("SELECT modlightprodattrid FROM {$table_prefix}_modlightprodattr WHERE fieldid IS NULL OR fieldid = 0");
		$count = $adb->num_rows($res);
		
		if ($count > 0) {
			// take ids from the main field table
			$ids = $adb->getMultiUniqueID($table_prefix.'_field', $count);
			$i = 0;
			while ($row = $adb->fetchByAssoc($res, -1, false)) {
				$fieldid = FakeModules::$baseConfProdFieldId + $ids[$i++];
				if ($fieldid > FakeModules::$maxConfProdFieldId) {
					throw new Exception("Maximum number of attributes reached");
				}
				$adb->pquery("UPDATE {$table_prefix}_modlightprodattr SET fieldid = ? WHERE modlightprodattrid = ?", array($fieldid, $row['modlightprodattrid']));
			}
		}
	}
	
	public function getAttributes($id) {
		$MLUtils = ModLightUtils::getInstance();
		$columns = $MLUtils->getColumns($this->modulename,'mlProdAttr');
		$values = $MLUtils->getValues($this->modulename,$id,'mlProdAttr',$columns);
		
		$return = [];
		if (is_array($values)) {
			foreach ($values as $row) {
				$name = "prodattr_{$id}_{$row['id']}";
				$label = $row['row']['attr_name'];
				$uitype = 15;
				$wstype = 'picklist';
				$values = explode("\n", trim(preg_replace("/\r*\n+/", "\n", $row['row']['attr_values'])));
				if (count($values) > 0) {
					$return[] = [
						'fieldid' => $row['fieldid'],
						'fieldname' => $name,
						'fieldlabel' => $label,
						'uitype' => $uitype,
						'wstype' => $wstype,
						'values' => $values,
						'rowid' => $row['id'],
					];
				}
			}
		}
		
		return $return;
	}
	
	public function getAttributeFromName($fieldname) {
		$attribute = null;
		$matches = [];
		if (preg_match('/^prodattr_(\d+)_(\d+)$/', $fieldname, $matches)) {
			$confid = $matches[1];
			$rowid = $matches[2];
			if ($confid > 0 && $rowid > 0) {
				$attrs = $this->getAttributes($confid);
				foreach ($attrs as $attr) {
					if ($attr['rowid'] == $rowid) {
						$attribute = $attr;
						break;
					}
				}
			}
		}
		
		return $attribute;
	}
	
	public function getAttributeFromFieldid($fieldid) {
		global $adb, $table_prefix;
		
		$attribute = null;
		
		$res = $adb->pquery("SELECT modlightprodattrid, parent_id FROM {$table_prefix}_modlightprodattr WHERE fieldid = ?", array($fieldid));
		$confid = $adb->query_result_no_html($res, 0, 'parent_id');
		$rowid = $adb->query_result_no_html($res, 0, 'modlightprodattrid');
		
		if ($confid > 0 && $rowid > 0) {
			$attrs = $this->getAttributes($confid);
			foreach ($attrs as $attr) {
				if ($attr['rowid'] == $rowid) {
					$attribute = $attr;
					break;
				}
			}
		}
		
		return $attribute;
	}
	
	/**
	 * Get all possible attributes
	 */
	public function getAllAttributes($usePermissions = false) {
		global $adb, $table_prefix;
		
		// use an hardcoded query for better performance
		
		$sql = 
			"SELECT mlpa.* , cp.productname
			FROM {$table_prefix}_modlightprodattr mlpa
			INNER JOIN {$table_prefix}_crmentity c ON c.crmid = mlpa.modlightprodattrid AND c.deleted = 0
			INNER JOIN {$this->table_name} cp ON cp.{$this->table_index} = mlpa.parent_id
			INNER JOIN {$table_prefix}_crmentity c2 ON c2.crmid = cp.{$this->table_index} AND c2.deleted = 0";
		// TODO: permissions
		$res = $adb->query($sql);
		
		$return = [];
		while ($row = $adb->FetchByAssoc($res, -1, false)) {
			
			$confid = $row['parent_id'];
			$rowid = $row['modlightprodattrid'];
			$name = "prodattr_{$confid}_{$rowid}";
			$label = $row['attr_name'];
			$uitype = 15;
			$wstype = 'picklist';
			$values = explode("\n", trim(preg_replace("/\r*\n+/", "\n", $row['attr_values'])));
			$return[] = [
				'fieldid' => $row['fieldid'],
				'fieldname' => $name,
				'fieldlabel' => $label,
				'productname' => $row['productname'],
				'uitype' => $uitype,
				'wstype' => $wstype,
				'values' => $values,
			];
		}
		
		return $return;
	}
	
	public function getHtmlBlock($forModule, $forField, $confid) {
		global $app_strings, $mod_strings, $theme;
		
		$prodInst = CRMEntity::getInstance('Products');
		
		$smarty = new VteSmarty();
		$smarty->assign('MODE','');
		$smarty->assign('MODULE',$forModule);
		$smarty->assign('APP',$app_strings);
		$smarty->assign('MOD',$mod_strings);
		$smarty->assign("THEME", $theme);
		$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
		$smarty->assign('ID', '');
		
		$return_fields = $blockdata = $aBlockStatus = array();
		$col_fields = array('confproductid' => $confid);
		$prodInst->addAttributesBlock($col_fields, 'edit', $return_fields, $blockdata, $aBlockStatus);
		
		$smarty->assign('data',$return_fields);
		$output = $smarty->fetch('DisplayFields.tpl');
		
		return $output;
	}
	
}