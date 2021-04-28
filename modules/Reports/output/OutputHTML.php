<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@96742 */

require_once('modules/Reports/output/OutputBase.php');

class ReportOutputHTML extends ReportOutputBase {

	// basic templates
	public $table_tpl = "<table align=\"center\" class=\"{class}\" id=\"{id}\">\n";
	public $thead_tpl = "<thead class=\"{class}\">\n";
	public $tfoot_tpl = "<tfoot class=\"{class}\">\n";
	public $tbody_tpl = "<tbody class=\"{class}\">\n";
	
	// generated templates
	protected $table_html = '';
	protected $thead_html = '';
	protected $tfoot_html = '';
	protected $tbody_html = '';
	protected $tr_html = '<tr>';
	
	public $properties = array(
		'table' => array(
			'class' => 'rptTable table table-striped table-bordered',
			'id' => 'tableContentMain',
		)
	);
	
	public function __construct() {
		parent::__construct();
		$this->buildTableHtml();
		// build other templates
		$this->thead_html = $this->parseTemplate($this->thead_tpl, $this->properties['thead']);
		$this->tfoot_html = $this->parseTemplate($this->tfoot_tpl, $this->properties['tfoot']);
		$this->tbody_html = $this->parseTemplate($this->tbody_tpl, $this->properties['tbody']);
	}
	
	public function setTableId($id) {
		$this->properties['table']['id'] = $id;
		$this->buildTableHtml();
	}
	
	protected function buildTableHtml() {
		$this->table_html = $this->parseTemplate($this->table_tpl, $this->properties['table']);
		return $this->table_html;
	}

	public function buildHtmlHeader() {
		$html = $this->thead_html.$this->tr_html;
		foreach ($this->header as $hdr) {
			$html .= $this->formatHeaderCell($hdr);
		}
		$html .= "</tr></thead>\n";
		return $html;
	}
	
	public function buildHtmlFooter() {
		if (empty($this->footer)) return "";
		
		$html = $this->tfoot_html;
		foreach ($this->footer as $hdr) {
			$html .= $this->formatFooterCell($hdr);
		}
		$html .= "</tfoot>\n";
		return $html;
	}
	
	public function buildHtmlData() {
		if (empty($this->data)) return;
		
		$html = $this->tbody_html;
		foreach ($this->data as $row) {
			$html .= $this->formatDataRow($row);
		}
		$html .= "</tbody>\n";
		return $html;
	}
	
	public function output($return = false) {
		if ($return) {
			$h = $this->table_html;
			$h .= $this->buildHtmlHeader();
			$h .= $this->buildHtmlFooter();
			$h .= $this->buildHtmlData();
			$h .= "</table>";
			return $h;
		} else {
			$this->rawOutput($this->table_html);
			
			$h = $this->buildHtmlHeader();
			$this->rawOutput($h);
			$h = $this->buildHtmlFooter();
			$this->rawOutput($h);
			$h = $this->buildHtmlData();
			$this->rawOutput($h);
			
			$this->rawOutput('</table>');
		}
	}

	protected function formatDataRow($row) {
		$html = $this->tr_html;
		foreach ($row as $field => $cell) {
			$html .= $this->formatDataCell($cell);
		}
		$html .= "</tr>\n";
		return $html;
	}
	
	protected function formatHeaderCell($cell) {
		return "<th class=\"rptCellLabel\">".$cell['label']."</th>";
	}
	
	protected function formatFooterCell($cell) {
		return "<td>".$cell['label']."</td>";
	}
	
	protected function formatDataCell($cell) {
		$tag = "<td";
		$styles = array();
		
		// custom format for cells
		if ($cell['align']) {
			//$tag .= " align=\"{$cell['align']}\"";
			$styles[] = "text-align:{$cell['align']}";
		}
		if ($cell['class']) {
			$tag .= " class=\"{$cell['class']}\"";
		}
		if ($cell['style']) {
			$styles[] = $cell['style'];
		}
		
		if (count($styles) > 0) {
			$tag .= " style=".implode(';', $styles)."";
		}
		
		// crmv@177381
		if (!empty($cell['data'])) {
			foreach ($cell['data'] as $key => $dataval) {
				$tag .= " data-$key=\"".htmlspecialchars($dataval)."\"";
			}
		}
		// crmv@177381e
		
		$tag .= ">";
		return $tag.$cell['value']."</td>";
	}
	
	protected function rawOutput($html) {
		echo $html;
	}
	
	protected function parseTemplate($tpl, $params = null) {

		// replace template params
		if (!empty($params) && is_array($params)) {
			$search = array_map(function($item) {
				return "{".$item."}";
			}, array_keys($params));
			$replace = array_values($params);
			$tpl = str_replace($search, $replace, $tpl);
		}
		
		// remove all non-replaced vars
		$tpl = preg_replace('/\{.*?\}/', '', $tpl);
		
		return $tpl;
	}
	
}