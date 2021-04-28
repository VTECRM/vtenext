<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@193096 */

require_once('modules/Reports/ReportRun.php');

class ActiveProcessesReportRun extends ReportRun {

	// standard SDK variables, controlling export visibility
	public $enableExportPdf = true;
	public $enableExportXls = true;
	public $enablePrint = true;
	
	// true to hide the standard time interval search
	public $hideParamsBlock = true;

	function __construct($reportid) {
		global $table_prefix;
		// not passing reportid to avoid loading from tables
		parent::__construct();		
		
		// no generated subqueries, but create the empty array to avoid warnings
		$this->subQueries = array(); 
		
		// the primary module of this report, if applicable
		$this->primaryModule = 'Settings';
		$this->reporttype = '';
		$this->reportname = getTranslatedString('Active Processes','Reports');
		$this->reportlabel = $this->reportname;
		
		// fields definition
		// the key is the column name and the array describes each "field"
		// for real fields, you can also use the result of a vtws_describe and add the missing keys
		$this->columns = array(
			'Name' => array(
				'fieldid' => 1, 			// any number, can also be the real fieldid
				'module' => 'Settings',		// module for this field, can be a real module, or any string 
				'fieldname' => 'name',		// fieldname, real or fake
				'column' => 'name',			// the column in the query
				'wstype' => 'string',		// ws type of the field
				'uitype' => 1,				// uitype of the field
				'label' => getTranslatedString('LBL_PROCESS_MAKER_RECORD_NAME','Settings'), // label, should be translated
				'table' => 'pm', 			// name of the table for this field, used when searching, see the query below
				'alias' => 'name', 			// same as column, used when searching
				'global_alias' => 'name',	// same as column
			),
			'Description' => array(
				'fieldid' => 2, 
				'module' => 'Settings',
				'fieldname' => 'description',
				'column' => 'description',
				'wstype' => 'string',
				'uitype' => 1,
				'label' => getTranslatedString('LBL_PROCESS_MAKER_RECORD_DESC','Settings'),
				'table' => 'pm',
				'alias' => 'description',
				'global_alias' => 'description',
			),
			'Module' => array(
				'fieldid' => 3, 
				'module' => 'Settings',
				'fieldname' => 'module',
				'column' => 'module',
				'wstype' => 'string',
				'uitype' => 1,
				'label' => getTranslatedString('LBL_MODULE'),
				'table' => 'pm',
				'alias' => 'module',
				'global_alias' => 'module',
			),
			'Version' => array(
				'fieldid' => 4, 
				'module' => 'Settings',
				'fieldname' => 'version',
				'column' => 'version',
				'wstype' => 'string',
				'uitype' => 1,
				'label' => getTranslatedString('VTLIB_LBL_PACKAGE_VERSION','Settings'),
				'table' => 'pm',
				'alias' => 'version',
				'global_alias' => 'version',
			),
			'Creator' => array(
				'fieldid' => 5, 
				'module' => 'Settings',
				'fieldname' => 'creator',
				'column' => 'creator',
				'wstype' => 'owner',
				'uitype' => 53,
				'label' => getTranslatedString('Creator','Accounts'),
				'table' => 'u',
				'alias' => 'creator',
				'global_alias' => 'creator',
			),
			'Created Time' => array(
				'fieldid' => 6, 
				'module' => 'Settings',
				'fieldname' => 'createdtime',
				'column' => 'createdtime',
				'wstype' => 'datetime',
				'uitype' => 70,
				'label' => getTranslatedString('Created Time'),
				'table' => 'pm',
				'alias' => 'createdtime',
				'global_alias' => 'createdtime',
			),
		);
	}
	
	// this function is mandatory for all SDK reports
	function getSDKBlock() {
		return '';
	}
	
	public function sGetSQLforReport() {
		global $adb, $table_prefix, $current_user, $showfullusername;

		$userColumnSql = $current_user->formatUserNameSql($adb, 'u', $showfullusername);
		
		$sql = "select pm.id AS 'id@settings', pm.name, pm.description, metarec.module as \"module\", pm.version, {$userColumnSql} as \"creator\", pm.createdtime
		from {$table_prefix}_processmaker pm
		left join {$table_prefix}_users u on pm.creatorid = u.id
		left join {$table_prefix}_processmaker_metarec metarec on pm.id = metarec.processid and metarec.start = 1
		where active = 1";
		
		// use the standard function for the were, to have the UI search
		$where = $this->getWhereSql() ?: '';
		$where = str_replace('u.creator',$userColumnSql,$where);
		
		// use the standard function for the order, to have the UI ordering
		$orderby = $this->getOrderSql();
		
		// add them to the query
		if ($where) $sql .= " AND\n $where\n";
		if ($orderby) $sql .= " ORDER BY\n $orderby";

		return $sql;
	}

	function generateCell($colalias, $value, $custom_field_values, $options = array()) {
		if ($colalias == 'module') $value = getTranslatedString($value,$value);
		return parent::generateCell($colalias, $value, $custom_field_values, $options);
	}
	
	function generateActionCell($custom_field_values, $options = array()) {
		global $site_URL;
		
		$module = $this->primaryModule;
		$mainid = strtolower('id@'.$module);
		
		$action = parent::generateActionCell($custom_field_values, $options);
		if ($action !== false) {
			$recordid = $custom_field_values[$mainid];
			($options['abs_url'] === true) ? $root_url = $site_URL.'/' : $root_url = '';
			$action['value'] = "<a href='{$root_url}index.php?module=Processes&action=ProcessesAjax&file=ReportDetailProcess&id={$recordid}' target='_blank'>".getTranslatedString('LBL_VIEW_DETAILS')."</a>";
		}
		return $action;
	}
}