<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/Reports/ReportRun.php');

/* crmv@172034 */

/**
 * This is an example SDK report with a complete custom query, but the same interface as 
 * standard reports, with pagination, column searching and sorting, generic search and standard filters
 * Read the comments in this class, for a description of the various variables
 */
class ReportRunAccQuotes extends ReportRun {

	// standard SDK variables, controlling export visibility
	public $enableExportPdf = true;
	public $enableExportXls = true;
	public $enablePrint = true;
	
	// true to hide the standard time interval search
	public $hideParamsBlock = false;

	function __construct($reportid) {
		// not passing reportid to avoid loading from tables
		parent::__construct();
		
		// no generated subqueries, but create the empty array to avoid warnings
		$this->subQueries = array(); 
		
		// the primary module of this report, if applicable
		$this->primaryModule = 'Accounts';
		
		// fields definition
		// the key is the column name and the array describes each "field"
		// for real fields, you can also use the result of a vtws_describe and add the missing keys
		$this->columns = array(
			'accountid' => array(
				'fieldid' => 1, 				// any number, can also be the real fieldid
				'module' => 'Accounts',			// module for this field, can be a real module, or any string 
				'fieldname' => 'accountid',		// fieldname, real or fake
				'column' => 'accountid',		// the column in the query
				'wstype' => 'int',				// ws type of the field
				'uitype' => 7,					// uitype of the field
				'label' => 'Account ID',		// label, should be translated
				'table' => 'tt', 				// name of the table for this field, used when searching, see the query below
				'alias' => 'accountid', 		// same as column, used when searching
				'global_alias' => 'accountid',	// same as column
				'visible' => false, 			// visibility of the field in the result
			),
			'accountname' => array(
				'fieldid' => 2, 
				'module' => 'Accounts',
				'fieldname' => 'accountname',
				'column' => 'accountname',
				'wstype' => 'string',
				'uitype' => 1,
				'label' => getTranslatedString('Account Name', 'Accounts'),
				'table' => 'tt',
				'alias' => 'accountname',
				'global_alias' => 'accountname',
			),
			'quote_statuses' => array(
				'fieldid' => 3, 
				'module' => 'Quotes',
				'fieldname' => 'quote_statuses',
				'column' => 'quote_statuses',
				'wstype' => 'string',
				'uitype' => 1,
				'label' => 'Stati dei preventivi',
				'table' => 'tt',
				'alias' => 'quote_statuses',
				'global_alias' => 'quote_statuses',
			),
			'quote_count' => array(
				'fieldid' => 4, 
				'module' => 'Quotes',
				'fieldname' => 'quote_count',
				'column' => 'quote_count',
				'wstype' => 'int',
				'uitype' => 7,
				'label' => 'Conteggio preventivi',
				'table' => 'tt',
				'alias' => 'quote_count',
				'global_alias' => 'quote_count',
			),
			'last_quotename' => array(
				'fieldid' => 5, 
				'module' => 'Quotes',
				'fieldname' => 'last_quotename',
				'column' => 'last_quotename',
				'wstype' => 'string',
				'uitype' => 1,
				'label' => 'Ultimo preventivo',
				'table' => 'tt',
				'alias' => 'last_quotename',
				'global_alias' => 'last_quotename',
			),
		);
	}
	
	// this function is mandatory for all SDK reports
	function getSDKBlock() {
		return '';
	}
	
	// if you need standard fields, extend this method
	public function getStdFilterFields($reportid) {
	
		$stdfields = array(
			// fields are divided in blocks
			array(
				'label' => 'Campi azienda', // label for the block, not used
				'fields' => array(
					array(
						// same format as above
						'fieldid' => 20,		// any id
						'module' => 'Accounts',
						'fieldname' => 'modifiedtime',
						'label' => 'Ora di modifica',
						'table' => 'tt',
						'alias' => 'modifiedtime',
						'value' => Zend_Json::encode(array('fieldid' => 20, 'chain' => array('Accounts'))),
					)
				)
			),
			// other blocks...
		);
		
		return $stdfields;
	}
	
	// this is needed to have standard filters working
	public function setStdFilterFromRequest(&$request) {
		$r = parent::setStdFilterFromRequest($request);
		
		$allfields = $this->getStdFilterFields(0);
		
		// add the module to the array
		if (is_array($allfields) && count($allfields) > 0 && is_array($this->stdfilters) && count($this->stdfilters) > 0) {
			foreach ($this->stdfilters as &$flt) {
				// search for a valid field
				foreach ($allfields as $block) {
					foreach ($block['fields'] as $field) {
						if ($field['fieldid'] == $flt['fieldid']) {
							unset($field['value']);
							$flt = array_replace($flt, $field);
							break 2;
						}
					}
				}
			}
			$r = $this->stdfilters;
		}
		return $r;
	}
	
	// if you want to make some columns not searchable or not sortable, do it here
	function generateHeader($result, $output, $options = array()) {
		$r = parent::generateHeader($result, $output, $options);
		
		// get all headers and then remove them from the output
		$headers = $output->getHeader();
		$output->clearHeader();
		
		// alter them and re-add them
		foreach ($headers as $idx=>$header) {
			if ($header['column'] == 'last_quotename') {
				$header['orderable'] = false;
				//$header['searchable'] = false;
			}
			$output->addHeader($header);
		}
		
		// return original value (if any)
		return $r;
	}
	
	
	// Here I use a complete custom query (mysql style).
	// In this case, I extract the accounts with some additional statistics about quotes
	// A global tt table is necessary otherwise searching by column would be difficult with
	// those subqueries. If you don't have them, you can use a single query, specifying the 
	// correct tables in the fields definition.
	public function sGetSQLforReport() {
		global $table_prefix;

		$thisYear = date('Y');
		$sql = "
			SELECT * FROM (
			
				SELECT
					a.accountid,
					a.accountid as \"id@accounts\", /* this is necessary to display the action column, it's id@this->primaryModule */
					a.accountname, 
					crmeAcc.modifiedtime, /* used only for the standard filter */
					(
						SELECT GROUP_CONCAT(quotestage SEPARATOR ', ')
						FROM {$table_prefix}_quotes q
						INNER JOIN {$table_prefix}_crmentity crmeQuot ON q.quoteid = crmeQuot.crmid  AND crmeQuot.deleted = 0
						WHERE q.accountid = a.accountid
					) AS quote_statuses,
					(
						SELECT COUNT(*)
						FROM {$table_prefix}_quotes q
						INNER JOIN {$table_prefix}_crmentity crmeQuot ON q.quoteid = crmeQuot.crmid  AND crmeQuot.deleted = 0
						WHERE q.accountid = a.accountid
					) AS quote_count,
					(
						SELECT q.quoteid
						FROM {$table_prefix}_quotes q
						INNER JOIN {$table_prefix}_crmentity crmeQuot ON q.quoteid = crmeQuot.crmid  AND crmeQuot.deleted = 0
						WHERE q.accountid = a.accountid
						ORDER BY quoteid DESC
						LIMIT 1
					) AS last_quoteid, 
					(
						SELECT q.subject
						FROM {$table_prefix}_quotes q
						INNER JOIN {$table_prefix}_crmentity crmeQuot ON q.quoteid = crmeQuot.crmid  AND crmeQuot.deleted = 0
						WHERE q.accountid = a.accountid
						ORDER BY quoteid DESC
						LIMIT 1
					) AS last_quotename
				FROM {$table_prefix}_account a
				INNER JOIN {$table_prefix}_crmentity crmeAcc ON a.accountid = crmeAcc.crmid AND crmeAcc.deleted = 0
				WHERE crmeAcc.createdtime > '$thisYear-01-01 00:00:00'
				
			) tt
		";
		
		// use the standard function for the were, to have the UI search
		$where = $this->getWhereSql();
		// use the standard function for the order, to have the UI ordering
		$orderby = $this->getOrderSql();
		
		// add them to the query
		if ($where) $sql .= " WHERE\n $where\n";
		if ($orderby) $sql .= " ORDER BY\n $orderby";

		return $sql;
	}
	
	// simple version to count the total records (can be avoided if the main query doesn't have subqueries)
	public function sGetCountSQLforReport($sql) {
		
		$sql = preg_replace('/^\s*SELECT \* FROM/i', 'SELECT COUNT(*) as count FROM', $sql);

		return $sql;
	}
	
	// yes, that's all! :)
	
}