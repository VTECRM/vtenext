<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@127526 */

/**
 * Provides utilities function to manage "fake modules", which are non-existant modules
 * used in reports and relations to have a unified way of extracting data.
 * At the moment the only modules handled are ProductsBlock and NewsletterStats
 */
class FakeModules {
	
	public static $maxRealTabid = 200;			// maximum tabid + 1 for real modules
	
	public static $baseFakeRelationId = 10000;	// minimum relationid for fake relations
	
	public static $baseFakeBlockId = 10000;		// minimum blockid for fake blocks
	public static $baseFakeFieldId = 10000;		// minimum fieldid for fake fields
	
	public static $baseTaxBlockId = 20000;		// min blockid for taxes in inventory modules
	public static $baseTaxFieldId = 20000;		// min fieldid for taxes in inventory modules
	
	public static $baseConfProdFieldId = 100000; // crmv@198024
	public static $maxConfProdFieldId = 200000; // crmv@198024
	
	public static $fakeModules = array(
		'ProductsBlock' => array(
			'tabid' => 200,				// fake tabid for the module
			'fields' => 200,			// number of reserved fields for this module
			'tax_fields' => 100,		// number of reserved tax fields
			'blocks' => 10,				// number of reserved blocks for this module
			'field_offset' => 1,		// start fieldids with this offset
			'tax_offset' => 100,		// offset for tax fields
			'block_offset' => 1,		// start of blockid
		),
		'NewsletterStats' => array(
			'tabid' => 250,
			'fields' => 20,
			'blocks' => 10,
			'field_offset' => 201,
			'block_offset' => 11
		),
		'NewsletterTLinks' => array(
			'tabid' => 260,
			'fields' => 20,
			'blocks' => 10,
			'field_offset' => 221,
			'block_offset' => 21
		),
		// crmv@150533
		'PriceBooksPrices' => array(
			'tabid' => 270,
			'fields' => 10,
			'blocks' => 10,
			'field_offset' => 241,
			'block_offset' => 31
		)
		// crmv@150533e
	);
	
	//crmv@181281
	public static function getNewsletterModules() {
		$focus = CRMEntity::getInstance('Newsletter');
		return array_keys($focus->email_fields);
	}
	//crmv@181281e
	
	public static function getModules() {
		return array_keys(self::$fakeModules);
	}
	
	public static function isFakeModule($module) {
		return array_key_exists($module, self::$fakeModules);
	}
	
	public static function getModuleLabel($module) {
		if ($module == 'ProductsBlock') {
			return getTranslatedString('LBL_RELATED_PRODUCTS', 'Settings');
		} elseif ($module == 'NewsletterStats') {
			return getTranslatedString('LBL_STATISTICS', 'Newsletter');
		} elseif ($module == 'NewsletterTLinks') {
			return getTranslatedString('Tracked Link', 'Campaigns');
		// crmv@150533
		} elseif ($module == 'PriceBooksPrices') {
			return getTranslatedString('PriceBooksPrices', 'PriceBooks');
		}
		// crmv@150533e
 		return $module;
	}
	
	public static function getFieldLabel($module, $fieldname) {
		$label = '';
		$fields = self::getFields($module);
		if (array_key_exists($fieldname, $fields)) {
			$label = $fields[$fieldname]['label'];
		}
		return $label;
	}
	
	protected static function getTabId($module) {
		global $adb, $table_prefix;
		static $tabidCache = array();
		if (!isset($tabidCache[$module])) {
			$res = $adb->pquery("SELECT tabid FROM {$table_prefix}_tab WHERE name = ?", array($module));
			$tabid = intval($adb->query_result_no_html($res, 0, 'tabid'));
			$tabidCache[$module] = $tabid;
		}
		return $tabidCache[$module];
	}
	
	public static function isBlock($blockid, $module) {
		$base = self::$baseFakeBlockId + self::$fakeModules[$module]['block_offset'];
		return ($blockid >= $base && $blockid < $base + self::$fakeModules[$module]['blocks']);
	}
	
	public static function getBlocks($module) {
		$base = self::$baseFakeBlockId + self::$fakeModules[$module]['block_offset'];
		
		if ($module == 'ProductsBlock') {
			$infos = array(
				array(
					'blockid' => $base,
					'module' => $module,
					'blocklabel' => 'LBL_RELATED_PRODUCTS',
					'label' => getTranslatedString('LBL_RELATED_PRODUCTS', 'Settings'),
					'sequence' => 100,	// if merged into other modules, make sure it's the last
				),
			);
		} elseif ($module == 'NewsletterStats') {
			$infos = array(
				array(
					'blockid' => $base,
					'module' => $module,
					'blocklabel' => 'LBL_STATISTICS',
					'label' => getTranslatedString('LBL_STATISTICS', 'Newsletter'),
					'sequence' => 1,
				),
			);
		} elseif ($module == 'NewsletterTLinks') {
			$infos = array(
				array(
					'blockid' => $base,
					'module' => $module,
					'blocklabel' => 'Tracked Link',
					'label' => getTranslatedString('Tracked Link', 'Campaigns'),
					'sequence' => 1,
				),
			);
		// crmv@150533
		} elseif ($module == 'PriceBooksPrices') {
			$infos = array(
				array(
					'blockid' => $base,
					'module' => $module,
					'blocklabel' => 'PriceBooksPrices',
					'label' => getTranslatedString('PriceBooksPrices', 'PriceBooks'),
					'sequence' => 1,
				),
			);
		}
		// crmv@150533e
		
		return $infos;
	}
	
	public static function getBlockInfoById($blockid, $module) {
		// crmv@159491
		if (self::isInventoryTaxBlock($blockid)) {
			return self::getInventoryTaxBlockInfoById($blockid);
		}
		// crmv@159491e
		$infos = self::getBlocks($module);
		foreach ($infos as $info) {
			if ($info['blockid'] == $blockid) {
				return $info;
			}
		}
		return false;
	}
	
	public static function isField($fieldid, $module) {
		$base = self::$baseFakeFieldId + self::$fakeModules[$module]['field_offset'];
		return ($fieldid >= $base && $fieldid < $base + self::$fakeModules[$module]['fields']);
	}
	
	/**
	 * Check if the passed fieldid is a fake id
	 */
	public static function isFakeFieldId($fieldid, &$module) {
		if (self::isInventoryTaxField($fieldid)) return true; // crmv@159491
		foreach (self::$fakeModules as $mod => $modinfo) {
			if (self::isField($fieldid, $mod)) {
				$module = $mod;
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Check if the passed blockid is a fake id
	 */
	public static function isFakeBlockId($blockid, &$module) {
		if (self::isInventoryTaxBlock($blockid)) return true; // crmv@159491
		foreach (self::$fakeModules as $mod => $modinfo) {
			if (self::isBlock($blockid, $mod)) {
				$module = $mod;
				return true;
			}
		}
		return false;
	}
	
	public static function getFields($module) {
		
		if ($module == 'ProductsBlock') {
			return self::getPBFields();
		} elseif ($module == 'NewsletterStats') {
			return self::getNLFields();
		} elseif ($module == 'NewsletterTLinks') {
			return self::getTLinkFields();
		// crmv@150533
		} elseif ($module == 'PriceBooksPrices') {
			return self::getPBPricesFields();
		}
		// crmv@150533e
	}
	
	/**
	 * get the fields in objects similar to WebserviceFields
	 */
	public static function getWSFields($module) {
		$wsfields = array();
		$fields = self::getFields($module);
		foreach ($fields as $fieldname => $finfo) {
			$wsfields[$fieldname] = self::createWSField($module, $finfo);
		}
		return $wsfields;
	}
	
	protected static function createWSField($module, $fieldinfo) {
		global $adb;
		$row = $fieldinfo;
		$row['displaytype'] = '1';
		$row['masseditable'] = '1';
		$row['typeofdata'] = 'V~O'; // TODO!
		$row['presence'] = '2';
		$wsfield = WebserviceField::fromArray($adb, $row);
		$wsfield->setFieldDataType($row['wstype']);
		return $wsfield;
	}

	public static function getFieldInfoById($fieldid, $module) {
		if (self::isInventoryTaxField($fieldid)) return self::getTaxFieldInfoById($fieldid); // crmv@159491
		$fields = self::getFields($module);
		foreach ($fields as $finfo) {
			if ($finfo['fieldid'] == $fieldid) {
				return $finfo;
			}
		}
		return false;
	}
	
	public static function getFieldInfo($fieldname, $module) {
		$fields = self::getFields($module);
		foreach ($fields as $finfo) {
			if ($finfo['fieldname'] == $fieldname) {
				return $finfo;
			}
		}
		return false;
	}
	
	protected static function getPBFields() {
		global $adb, $table_prefix;
		
		$module = 'ProductsBlock';
		
		$tabid = self::$fakeModules[$module]['tabid'];
		$baseFieldid = self::$baseFakeFieldId + self::$fakeModules[$module]['field_offset'] - 1; // later is incremented
		
		$blockinfo = self::getBlocks($module);
		$blockinfo = $blockinfo[0];
		
		$infos = array(
			'id' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'id',
				'fieldlabel' => 'Id',
				'typeofdata' => 'I',
				'uitype' => 10,
				'wstype' => 'reference',
				'relmodules' => getInventoryModules(),
			),
			'productid' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'productid',
				'fieldlabel' => 'Product Name',
				'typeofdata' => 'I',
				'uitype' => 10,
				'wstype' => 'reference',
				'relmodules' => getProductModules(),
			),
			'quantity' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'quantity',
				'fieldlabel' => 'Quantity',
				'typeofdata' => 'N',
				'uitype' => 7,
				'wstype' => 'double',
			),
			'listprice' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'listprice',
				'fieldlabel' => 'List Price',
				'typeofdata' => 'N',
				'uitype' => 71,
				'wstype' => 'double',
			),
			'discount' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'discount',
				'fieldlabel' => 'Discount',
				'typeofdata' => 'N',
				'uitype' => 7,
				'wstype' => 'double',
			),
			'total_notaxes' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'total_notaxes',
				'fieldlabel' => 'LBL_TOTAL_AFTER_DISCOUNT',
				'typeofdata' => 'N',
				'uitype' => 71,
				'wstype' => 'double',
			),
			'comment' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'comment',
				'fieldlabel' => 'Comments',
				'typeofdata' => 'V',
				'uitype' => 1,
				'wstype' => 'string',
			),
			'description' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'description',
				'fieldlabel' => 'Description',
				'typeofdata' => 'V',
				'uitype' => 19,
				'wstype' => 'text',
			),
			'linetotal' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'linetotal',
				'fieldlabel' => 'Net Price',
				'typeofdata' => 'N',
				'uitype' => 71,
				'wstype' => 'double',
			),
		);
		
		$taxFields = self::getTaxFields($module);
		$infos = array_merge($infos, $taxFields);
		
		$seq = 0;
		foreach ($infos as &$info) {
			$info['tabid'] = $tabid;
			$info['module'] = $module;
			if (!$info['fieldname']) $info['fieldname'] = $info['columnname']; // crmv@142671
			$info['label'] = getTranslatedString($info['fieldlabel'], 'Quotes');
			$info['tablename'] = $table_prefix.'_inventoryproductrel';
			$info['block'] = $blockinfo['blockid'];
			$info['sequence'] = ++$seq;
		}
		
		return $infos;
	}
	
	/* functions for tax block */
	
	public static function isInventoryTaxBlock($blockid) {
		// crmv@159491
		$maxblockid = self::$baseTaxBlockId + self::$maxRealTabid * self::$maxRealTabid;
		return ($blockid >= self::$baseTaxBlockId && $blockid < $maxblockid);
		// crmv@159491e
	}
	
	public static function getInventoryTaxBlocks($module) {
		$tabid = self::getTabid($module);
		$baseBlockid = self::$baseTaxBlockId + self::$maxRealTabid * $tabid;
		
		++$baseBlockid;
		$infos = array(
			array(
				'module' => $module,
				'blockid' => $baseBlockid,
				'blocklabel' => 'LBL_TAX',
				'label' => getTranslatedString('LBL_TAX', 'APP_STRINGS'),
				'sequence' => 100,	// after all other blocks
			),
		);
		
		return $infos;
	}
	
	public static function getInventoryTaxBlockInfoById($blockid) {
		$tabid = (int)(($blockid - self::$baseTaxBlockId)/self::$maxRealTabid); // crmv@159491
		$module = getTabname($tabid);
		$infos = self::getInventoryTaxBlocks($module);
		foreach ($infos as $info) {
			if ($info['blockid'] == $blockid) {
				return $info;
			}
		}
		return false;
	}
	
	/* functions for tax fields (for PB or inventory modules */
	
	public static function isInventoryTaxField($fieldid) {
		$maxtaxId = self::$baseTaxFieldId + self::$maxRealTabid * self::$maxRealTabid;
		return ($fieldid >= self::$baseTaxFieldId && $fieldid < $maxtaxId);
	}
	
	public static function getTaxFields($module) {
		global $table_prefix;
		
		$IUtils = InventoryUtils::getInstance();
		
		$infos = array();
		
		if ($module == 'ProductsBlock') {
			$tabid = self::$fakeModules[$module]['tabid'];
			$table = $table_prefix.'_inventoryproductrel';
			$prodLabel = ' '.getTranslatedString('LBL_PRODUCT', 'APP_STRINGS'); // crmv@195745
			$baseTaxFieldid = self::$baseFakeBlockId + self::$fakeModules[$module]['tax_offset'];
			$blockid = 0;	// overwritten later
		} else {
			$tabid = self::getTabid($module);
			$table = $table_prefix.'_inventorytotals';
			$prodLabel = '';
			$baseTaxFieldid = self::$baseTaxFieldId + self::$maxRealTabid * $tabid;
			
			$blockinfo = self::getInventoryTaxBlocks($module);
			$blockinfo = $blockinfo[0];
			$blockid = $blockinfo['blockid'];
		}
		
		// taxes for the single product line
		$allTaxes = $IUtils->getAllTaxes('all');
		// add total:
		$allTaxes[] = array(
			'taxid' => self::$fakeModules['ProductsBlock']['tax_fields'],
			'taxname' => 'tax_total',
			'taxlabel' => getTranslatedString('LBL_TOTAL', 'APP_STRINGS') // crmv@195745
		);
		$seq = 100;
		foreach ($allTaxes as $tax) {
			$taxname = $tax['taxname'];
			$infos[$taxname] = array(
				'tabid' => $tabid,
				'block' => $blockid,
				'module' => $module,
				'trans_module' => $module == 'ProductsBlock' ? '' : getTranslatedString($module,$module), // crmv@195745
				'fieldid' => $baseTaxFieldid + $tax['taxid'],
				'tablename' => $table,
				'columnname' => $taxname,
				'fieldname' => $taxname,
				// crmv@195745
				'fieldlabel' => getTranslatedString('LBL_TAX', 'APP_STRINGS').$prodLabel.' ('.$tax['taxlabel'].')',
				'label' => getTranslatedString('LBL_TAX', 'APP_STRINGS').$prodLabel.' ('.$tax['taxlabel'].')',
				// crmv@195745e
				'typeofdata' => 'N',
				'uitype' => 71,
				'wstype' => 'double',
				'sequence' => $seq++,
			);
		}
		
		return $infos;
	}
	
	public static function getTaxFieldInfo($module, $fieldname) {
		$infos = self::getTaxFields($module);
		$info = $infos[$fieldname];
		return $info;
	}
	
	public static function getTaxFieldInfoById($fieldid) {
		$tabid = (int)(($fieldid - self::$baseTaxFieldId)/self::$maxRealTabid);
		$module = getTabname($tabid);
		$infos = self::getTaxFields($module);
		foreach ($infos as $info) {
			if ($info['fieldid'] == $fieldid) {
				return $info;
			}
		}
		return false;
	}
	
	protected static function getNLFields() {
		
		$module = 'NewsletterStats';
		
		$tabid = self::$fakeModules[$module]['tabid'];
		$baseFieldid = self::$baseFakeFieldId + self::$fakeModules[$module]['field_offset'] - 1; // later is incremented
		
		$blockinfo = self::getBlocks($module);
		$blockinfo = $blockinfo[0];
		
		$infos = array(
			'newsletterid' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'newsletterid',
				'fieldlabel' => 'Newsletter',
				'typeofdata' => 'I',
				'uitype' => 10,
				'wstype' => 'reference',
				'relmodules' => array('Newsletter'),
			),
			'crmid' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'crmid',
				'fieldlabel' => 'Recipient Name',
				'typeofdata' => 'I',
				'uitype' => 10,
				'wstype' => 'reference',
				'relmodules' => self::getNewsletterModules(), //crmv@181281
			),
			'status' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'status',
				'fieldlabel' => 'Status',
				'typeofdata' => 'V',
				'uitype' => 15,
				'wstype' => 'picklist',
				'allowed_values' => array(
					'Scheduled' => getTranslatedString('Scheduled', 'Newsletter'),
					'Sent' => getTranslatedString('Sent', 'Newsletter'),
					'Failed' => getTranslatedString('Failed', 'Newsletter'),
				),
			),
			'date_sent' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'date_sent',
				'fieldlabel' => 'Sent Date',
				'typeofdata' => 'T',
				'uitype' => 70,
				'wstype' => 'datetime',
			),
			'first_view' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'first_view',
				'fieldlabel' => 'First View',
				'typeofdata' => 'T',
				'uitype' => 70,
				'wstype' => 'datetime',
			),
			'last_view' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'last_view',
				'fieldlabel' => 'Last View',
				'typeofdata' => 'T',
				'uitype' => 70,
				'wstype' => 'datetime',
			),
			'num_views' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'num_views',
				'fieldlabel' => 'No Views',
				'typeofdata' => 'I',
				'uitype' => 7,
				'wstype' => 'integer',
			),
		);
		
		$seq = 0;
		foreach ($infos as &$info) {
			$info['tabid'] = $tabid;
			$info['module'] = $module;
			$info['fieldname'] = $info['columnname'];
			$info['label'] = getTranslatedString($info['fieldlabel'], 'Newsletter');
			$info['tablename'] = 'tbl_s_newsletter_queue';
			$info['block'] = $blockinfo['blockid'];
			$info['sequence'] = ++$seq;
		}

		return $infos;
	}
	
	protected static function getTLinkFields() {
		
		$module = 'NewsletterTLinks';
		
		$tabid = self::$fakeModules[$module]['tabid'];
		$baseFieldid = self::$baseFakeFieldId + self::$fakeModules[$module]['field_offset'] - 1; // later is incremented
		
		$blockinfo = self::getBlocks($module);
		$blockinfo = $blockinfo[0];
		
		$infos = array(
			'newsletterid' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'newsletterid',
				'fieldlabel' => 'Newsletter',
				'typeofdata' => 'I',
				'uitype' => 10,
				'wstype' => 'reference',
				'relmodules' => array('Newsletter'),
			),
			'crmid' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'crmid',
				'fieldlabel' => 'Recipient Name',
				'typeofdata' => 'I',
				'uitype' => 10,
				'wstype' => 'reference',
				'relmodules' => self::getNewsletterModules(), //crmv@181281
			),
			'url' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'url',
				'tablename' => 'tbl_s_newsletter_links',
				'fieldlabel' => 'Link',
				'typeofdata' => 'V',
				'uitype' => 1,
				'wstype' => 'url',
			),
			'firstclick' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'firstclick',
				'fieldlabel' => 'First View',
				'typeofdata' => 'T',
				'uitype' => 70,
				'wstype' => 'datetime',
			),
			'latestclick' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'latestclick',
				'fieldlabel' => 'Last View',
				'typeofdata' => 'T',
				'uitype' => 70,
				'wstype' => 'datetime',
			),
			'clicked' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'clicked',
				'fieldlabel' => 'No Click',
				'typeofdata' => 'I',
				'uitype' => 7,
				'wstype' => 'integer',
			),
		);
		
		$seq = 0;
		foreach ($infos as &$info) {
			$info['tabid'] = $tabid;
			$info['module'] = $module;
			$info['fieldname'] = $info['columnname'];
			$info['label'] = getTranslatedString($info['fieldlabel'], 'Newsletter');
			if (!$info['tablename']) $info['tablename'] = 'tbl_s_newsletter_tl';
			$info['block'] = $blockinfo['blockid'];
			$info['sequence'] = ++$seq;
		}

		return $infos;
	}
	
	// crmv@150533
	protected static function getPBPricesFields() {
		global $table_prefix;
		
		$module = 'PriceBooksPrices';
		
		$tabid = self::$fakeModules[$module]['tabid'];
		$baseFieldid = self::$baseFakeFieldId + self::$fakeModules[$module]['field_offset'] - 1; // later is incremented
		
		$blockinfo = self::getBlocks($module);
		$blockinfo = $blockinfo[0];
		
		$infos = array(
			'pricebookid' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'pricebookid',
				'fieldlabel' => 'SINGLE_PriceBooks',
				'label' => getTranslatedString('SINGLE_PriceBooks', 'APP_STRINGS'),
				'typeofdata' => 'I',
				'uitype' => 10,
				'wstype' => 'reference',
				'relmodules' => array('PriceBooks'),
			),
			'productid' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'productid',
				'fieldlabel' => 'Product Name',
				'typeofdata' => 'I',
				'uitype' => 10,
				'wstype' => 'reference',
				'relmodules' => getProductModules(),
			),
			'listprice' => array(
				'fieldid' => ++$baseFieldid,
				'columnname' => 'listprice',
				'fieldlabel' => 'LBL_PB_LIST_PRICE',
				'typeofdata' => 'N',
				'uitype' => 71,
				'wstype' => 'double',
			),
		);
		
		$seq = 0;
		foreach ($infos as &$info) {
			$info['tabid'] = $tabid;
			$info['module'] = $module;
			$info['fieldname'] = $info['columnname'];
			if (!$info['label']) $info['label'] = getTranslatedString($info['fieldlabel'], 'PriceBooks');
			if (!$info['tablename']) $info['tablename'] = $table_prefix.'_pricebookproductrel';
			$info['block'] = $blockinfo['blockid'];
			$info['sequence'] = ++$seq;
		}

		return $infos;
	}
	// crmv@150533e
}