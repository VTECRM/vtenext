<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@96742 */

class ReportOutputBase extends SDKExtendableClass {

	public $direct = false;
	
	public $countTotal = 0;
	public $countFiltered = 0;
	
	protected $header = array();
	protected $footer = array();
	protected $data = array();
	
	protected $currentRow = array();
	
	public function __construct() {
		// nothing special
	}
	
	public function isDirect() {
		return !!$this->direct;
	}
	
	public function getData() {
		return $this->data;
	}
	
	public function getHeader() {
		return $this->header;
	}
	
	public function getFooter() {
		return $this->footer;
	}
	
	public function getHeaderCell($column) {
		foreach ($this->header as $cell) {
			if ($cell['column'] == $column) return $cell;
		}
		return false;
	}
	
	public function getFooterCell($column) {
		foreach ($this->footer as $cell) {
			if ($cell['column'] == $column) return $cell;
		}
		return false;
	}

	public function addHeader($headerField) {
		if (is_string($headerField)) {
			$headerField = array(
				'label' => $headerField,
			);
		}
		$this->header[] = $headerField;
		return $headerField;
	}
	
	public function addFooter($footerField) {
		if (is_string($footerField)) {
			$footerField = array(
				'label' => $footerField,
			);
		}
		$this->footer[] = $footerField;
		return $footerField;
	}
	
	public function addRow($row) {
		$dataRow = array();
		foreach ($row as $cell) {
			if (is_string($cell)) {
				$cell = array(
					'value' => $cell,
				);
			}
			$dataRow[] = $cell;
		}
		$this->data[] = $dataRow;
		return $dataRow;
	}
	
	public function addCell($cell) {
		$this->currentRow[] = $cell;
	}
	
	public function endCurrentRow() {
		if (!empty($this->currentRow)) {
			$this->addRow($this->currentRow);
			$this->currentRow = array();
		}
	}
	
	public function clearCurrentRow() {
		$this->currentRow = array();
	}
	
	public function clearData() {
		$this->data = array();
		$this->currentRow = array();
	}
	
	public function clearHeader() {
		$this->header = array();
	}
	
	public function clearFooter() {
		$this->footer = array();
	}
	
	public function clearAll() {
		$this->clearData();
		$this->clearHeader();
		$this->clearFooter();
	}
	
	public function getHeaderForCell($cell) {
		$col = $cell['column'];
		if (!$col) return false;
		foreach ($this->header as $hcell) {
			if ($hcell['column'] == $col) return $hcell;
		}
		return false;
	}
	
	// crmv@157509
	public function getCellByIndex($row, $index) {
		if (array_key_exists($row, $this->data)) {
			return $this->data[$row][$index];
		}
		return false;
	}
	// crmv@157509e
	
	// crmv@118320
	public function getNextCellIndex() {
		return count($this->currentRow);
	}
	
	public function getHeaderByIndex($idx) {
		if (isset($this->header[$idx])) {
			return $this->header[$idx];
		}
		return false;
	}
	// crmv@118320e
	
	public function outputHeader() {
	
	}
	
	public function outputFooter() {
	
	}
	
	public function outputData() {
	
	}
	
	public function output($return = false) {
		$this->outputHeader();
		$this->outputData();
		$this->outputFooter();
	}
	
}