<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@96742 */

require_once('modules/Reports/output/OutputHTML.php');

class ReportOutputHTMLDirect extends ReportOutputHTML {

	public $direct = true;
	
	private $outputStarted = false;
	private $headerStarted = false;
	private $footerStarted = false;
	private $dataStarted = false;
	
	public function __construct() {
		parent::__construct();
	}
	
	public function addHeader($headerField) {
		$this->startOutput();
		$this->startHeader();
		$headerField = parent::addHeader($headerField);
		$html = $this->formatHeaderCell($headerField);
		$this->rawOutput($html);
	}
	
	public function addFooter($footerField) {
		// not implemented
	}
	
	public function addRow($row) {
		$this->endHeader();
		$this->startData();
		$row = parent::addRow($row);
		$this->clearData();
		$html = $this->formatDataRow($row);
		$this->rawOutput($html);
	}
	
	public function output($return = false) {
		$this->endOutput();
	}
	
	protected function startOutput() {
		if (!$this->outputStarted) $this->rawOutput($this->table_html);
		$this->outputStarted = true;
	}
	
	protected function startHeader() {
		if (!$this->headerStarted) $this->rawOutput($this->thead_html.$this->tr_html);
		$this->headerStarted = true;
	}
	
	protected function startFooter() {
		if (!$this->footerStarted) $this->rawOutput($this->tfoot_html.$this->tr_html);
		$this->footerStarted = true;
	}
	
	protected function startData() {
		if (!$this->dataStarted) $this->rawOutput($this->tbody_html);
		$this->dataStarted = true;
	}
	
	protected function endOutput() {
		$this->endData();
		if ($this->outputStarted) {
			$this->rawOutput("</table>\n");
			$this->outputStarted = false;
		}
	}
	
	protected function endHeader() {
		if ($this->headerStarted) {
			$this->rawOutput("</tr></thead>\n");
			$this->headerStarted = false;
		}
	}
	
	protected function endFooter() {
		if ($this->footerStarted) {
			$this->rawOutput("</tr></tfoot>\n");
			$this->footerStarted = false;
		}
	}
	
	protected function endData() {
		$this->endHeader();
		if ($this->dataStarted) {
			$this->rawOutput("</tbody>\n");
			$this->dataStarted = true;
		}
	}
	
	public function outputHeader() {
		throw new Exception("You can't output the header all at once while in direct mode");
	}
	
	public function outputFooter() {
		throw new Exception("You can't output the footer all at once while in direct mode");
	}
	
	public function outputData() {
		throw new Exception("You can't output the data all at once while in direct mode");
	}
	
}