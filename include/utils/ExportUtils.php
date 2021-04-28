<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@151308 - clean up files and classes */

/**
 * This class will provide utility functions to process the export data.
 * This is to make sure that the data is sanitized before sending for export
 */
class ExportUtils extends SDKExtendableClass {

	protected $module;
	protected $filename;
	protected $outputMode = 'download'; // valid values: 'download', 'file', 'null', 'stdout'
	
	protected $fieldsArr = array();
	protected $picklistValues = array();
	
	protected $fhandle;

	public function __construct($module) {
		$this->module = $module;
		$this->filename = $this->generateFilename();
	}
	
	public function getModule($filename) {
		$this->filename = $filename;
	}
	
	public function getFilename($filename) {
		return $this->filename;
	}
	
	public function setFilename($filename) {
		$this->filename = $filename;
	}
	
	public function setOutputMode($mode) {
		$this->outputMode = $mode;
	}
	
	public function getOutputMode($mode) {
		return $this->outputMode;
	}
	
	/**
	 * This function exports all the data for a given module
	 * @param string $search_type The type of export: "withoutsearch" or "includesearch"
	 * @param string $export_data The data to export: "" (all records), "selecteddata" or "currentpage"
	 * Return type text
	 */
	public function doExport($search_type, $export_data = '', $ids = array()) {
		global $log, $list_max_entries_per_page;
		global $adb, $table_prefix;
		
		$log->debug("Entering export(".$type.") method ...");
		
		// old names
		$type = $this->module;

		$focus = CRMEntity::getInstance($type);

		$oldlog = $log; // prevent side effects after the export
		$log = LoggerManager::getLogger('export_'.$type);

		$oCustomView = CRMEntity::getInstance('CustomView', $type); // crmv@115329
		$viewid = $oCustomView->getViewId($type);
		
		//crmv@14086
		list($focus->customview_order_by,$focus->customview_sort_order) = $oCustomView->getOrderByFilterSQL($viewid);
		$sorder = $focus->getSortOrder();
		$order_by = $focus->getOrderBy();
		//crmv@14086e

		if(VteSession::hasKey('export_where') && VteSession::get('export_where')!='' && $search_type == 'includesearch'){
			$where =VteSession::get('export_where');
			$where = ltrim($where,' and');	//crmv@21448
		}

		// crmv@137410 use new query generation
		global $current_user;
		
		$qgen = QueryGenerator::getInstance($type, $current_user);
		//$qgen->initForCustomViewById($viewid);
		if ($where) $qgen->appendToWhereClause('and '.$where);
		
		// crmv@174382
		if ($viewid > 0) {
			$qgen->setReportFilter($oCustomView->getReportFilter($viewid), $type);
		}
		// crmv@174382e
		
		// set the fields
		$sql = getPermittedFieldsQuery($type, "detail_view");
		$res = $adb->query($sql);
		while ($row = $adb->fetchByAssoc($res, -1, false)) {
			if (in_array($type,array('HelpDesk','Faq')) && $row['fieldname'] == 'comments') continue; //crmv@168421
			$qgen->addField($row['fieldname']);
			$qgen->addWhereField($row['fieldname']);
			if($row['fieldname'] != 'newsletter_unsubscrpt') $qgen->addFieldAlias($row['fieldname'], $row['fieldlabel']); // crmv@180588
		}
		$query = $qgen->getQuery();
		// crmv@137410e
		
		$params = array();

		if(($search_type == 'withoutsearch' || $search_type == 'includesearch') && $export_data == 'selecteddata'){
			
			$list_max_entries_per_page=count($ids);
			if(count($ids) > 0) {
				$query .= " and {$focus->table_name}.{$focus->table_index} in (" . generateQuestionMarks($ids) . ')';
				array_push($params, $ids);
			}
		}

		if(isset($order_by) && $order_by != ''){
			if($order_by == 'lastname' && $type == 'Documents'){
				$query .= ' ORDER BY '.$table_prefix.'_contactdetails.lastname  '. $sorder;
			}elseif($order_by == 'crmid' && $type == 'HelpDesk'){
				$query .= ' ORDER BY '.$table_prefix.'_troubletickets.ticketid  '. $sorder;
			}else{
				$query .= $focus->getFixedOrderBy($type,$order_by,$sorder);	//crmv@38949 crmv@127820
			}
		}

		if($export_data == 'currentpage'){
			$current_page = ListViewSession::getCurrentPage($type,$viewid);
			$limit_start_rec = ($current_page - 1) * $list_max_entries_per_page;
			if ($limit_start_rec < 0) $limit_start_rec = 0;
			$result = $adb->limitpQuerySlave('Export',$query,$limit_start_rec,$list_max_entries_per_page,$params,true); // crmv@185894
		} else {
			$result = $adb->pquerySlave('Export',$query,$params,true); // crmv@185894
		}
			
		// ------ start output ------
		$this->startOutput();

		$fields_array = $adb->getFieldsArray($result);
		$fields_array = array_diff($fields_array,array("user_name"));

		$this->populateFieldsInfo($fields_array);

		$header = $this->generateHeader($fields_array);
		$this->outputLine($header);

		while ($sqlrow = $adb->fetchByAssoc($result, -1, false)) {
			$new_arr = $this->generateRow($sqlrow, $focus);
			$line = "\"" .implode("\";\"",$new_arr)."\"\r\n";
			$this->outputLine($line);
		}
		
		$this->completeOutput();
		
		$log = $oldlog;
		$log->debug("Exiting export method ...");
		
		return true;
	}
	
	protected function generateHeader($fields_array) {
		// Translated the field names based on the language used.
		$translated_fields_array = array();
		foreach ($fields_array as $field) {
			if ($field == 'newsletter_unsubscrpt') $field = 'Receive newsletter'; // crmv@180588
			$translated_fields_array[] = getTranslatedString($field,$this->module);
		}
		
		$header = implode("\";\"",array_values($translated_fields_array));	//crmv@27473
		$header = "\"" .$header;
		$header .= "\"\r\n";
		
		return $header;
	}
	
	protected function generateRow($sqlrow, &$focus) {
		$new_arr = array();
		$sqlrow = $this->sanitizeValues($sqlrow);
		$focusNewsletter = CRMEntity::getInstance('Newsletter'); //crmv@181281
		foreach ($sqlrow as $key => $value) {
			if ($this->module == 'Documents' && $key == 'description') {
				$value = strip_tags($value);
				$value = str_replace('&nbsp;','',$value);
				array_push($new_arr,$value);
			//crmv@181281
			}elseif (array_key_exists($this->module,$focusNewsletter->email_fields) && $key == 'receive newsletter') {
				$value = intval($focusNewsletter->receivingNewsletter($value));
				array_push($new_arr, preg_replace("/\"/","\"\"",$value));
			//crmv@181281e
			}elseif ($key != "user_name") {
				// Let us provide the module to transform the value before we save it to CSV file
				$value = $focus->transform_export_value($key, $value);
				array_push($new_arr, preg_replace("/\"/","\"\"",$value));
			}
		}
		return $new_arr;
	}
	
	protected function generateFilename() {
		return "{$this->module}.csv";
	}
	
	protected function outputHTTPHeader() {
		header("Content-Disposition:attachment;filename={$this->filename}");
		header("Content-Type:text/csv;charset=UTF-8");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); // to disable cache
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
		header("Cache-Control: post-check=0, pre-check=0", false);
	}
	
	protected function outputLine($line) {
		if ($this->outputMode == 'download' || $this->outputMode == 'stdout') {
			echo $line;
		} elseif ($this->outputMode == 'file') {
			fwrite($this->fhandle, $line);
		} elseif ($this->outputMode == 'null') {
			// do nothing
		} else {
			throw new Exception('Unknown output mode');
		}
	}
	
	protected function startOutput() {
		if ($this->outputMode == 'download') {
			$this->outputHTTPHeader();
		} elseif ($this->outputMode == 'file') {
			if (file_exists($this->filename)) unlink($this->filename);
			$this->fhandle = fopen($this->filename,'a');
			if (!$this->fhandle) throw new Exception('Unable to open output file');
		}
	}
	
	protected function completeOutput() {
		if ($this->outputMode == 'file') {
			if ($this->fhandle) fclose($this->fhandle);
		}
	}
	
	protected function populateFieldsInfo($fields_array) {
		$infoArr = self::getInformationArray($this->module);

		//attach extra fields related information to the fields_array; this will be useful for processing the export data
		foreach($infoArr as $fieldname=>$fieldinfo){
			if(in_array($fieldinfo["fieldlabel"], $fields_array)){
				$this->fieldsArr[$fieldname] = $fieldinfo;
			}
		}
	}

	/**
	 * this function takes in an array of values for an user and sanitizes it for export
	 * @param array $arr - the array of values
	 */
	public function sanitizeValues($arr){
		global $current_user, $adb;
		$roleid = fetchUserRole($current_user->id);

		foreach($arr as $fieldlabel=>&$value){
			$fieldInfo = $this->fieldsArr[$fieldlabel];

			$uitype = $fieldInfo['uitype'];
			$fieldname = $fieldInfo['fieldname'];
			if($uitype == 15 || $uitype == 16){	//crmv@22632
				//picklists
				if(empty($this->picklistValues[$fieldname])){
					$this->picklistValues[$fieldname] = getAssignedPicklistValues($fieldname, $roleid, $adb);
				}
				$value = trim($value);
				if(!empty($this->picklistValues[$fieldname]) && !in_array($value, array_keys($this->picklistValues[$fieldname])) && !empty($value)){
					$value = getTranslatedString("LBL_NOT_ACCESSIBLE");
				}
			// crmv@166082
			}elseif(in_array($uitype, array(50, 51, 52, 53, 54, 77))) {
				if (is_numeric($value)) {
					global $showfullusername;
					$value = getOwnerName($value, $showfullusername);
				}
			// crmv@166082e
			//crmv@22632
			}elseif($uitype == 33){
				if(empty($this->picklistValues[$fieldname])){
					$this->picklistValues[$fieldname] = getAssignedPicklistValues($fieldname, $roleid, $adb);
				}
				$tmp = explode(' |##| ',trim($value));
				$value = array();
				foreach($tmp as $val) {
					if(!empty($this->picklistValues[$fieldname]) && in_array($val, array_keys($this->picklistValues[$fieldname])) && !empty($val)){
						$value[] = $val;
					}
				}
				$value = implode(' |##| ',$value);
			//crmv@22632e
			//crmv@183699
			}elseif ($uitype == 208) {
				$module = $this->module;
				$sdk_file = SDK::getUitypeFile('php','export',208);
				if ($sdk_file != '') {
					include($sdk_file);
				}
			//crmv@183699e
			}elseif($uitype == 10){
				//have to handle uitype 10
				/* crmv@55239 */
				$value = trim($value);
				if (!empty($value) && is_numeric($value)) {
					$parent_module = getSalesEntityType($value);
					$displayValueArray = getEntityName($parent_module, $value);
					if(!empty($displayValueArray)){
						foreach($displayValueArray as $k=>$v){
							$displayValue = $v;
						}
					}
					if(!empty($parent_module) && !empty($displayValue)){
						$value = $parent_module."::::".$displayValue;
					}else{
						$value = "";
					}
				} elseif (empty($value)) {
					$value = '';
				}
			}
		}
		return $arr;
	}

	/**
	 * Return information about fields in the specified module.
	 * The resulting array has lowercase labels as keys
	 *
	 * @param string $module The module name
	 * @return array List of fields
	 */
	static protected function getInformationArray($module) {
		global $adb, $table_prefix;
		
		$data = array();
		$tabid = getTabid($module);
		$result = $adb->pquery("SELECT uitype, fieldname, columnname, tablename, fieldlabel FROM {$table_prefix}_field WHERE tabid = ?", array($tabid));
		while ($row = $adb->fetchByAssoc($result)) {
			$key = strtolower($row['fieldlabel']);
			$data[$key] = $row;
		}

		return $data;
	}
	
}

// if you are looking for getFieldsListFromQuery and getPermittedFieldsQuery functions,
// they have been moved (for now) in CommonUtils