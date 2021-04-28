<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@190016 */

namespace VteSyncLib\Connector\Jira\Model;

use VteSyncLib\Model\MetaInterface;
use VteSyncLib\Model\CommonMeta;

class Meta implements MetaInterface {

	protected $module;
	protected $recordClass;
	protected $fields;
	
	public $priorities = ['Highest', 'High', 'Medium', 'Low', 'Lowest'];
	public $statuses = ['In Progress', 'Backlog', 'Selected for Development', 'Done', 'To Do'];
	
	public function setRecordClass($class) {
		$this->recordClass = $class;
	}

	public static function fromRawData($data) {
		$meta = new static($data['name']);
		$meta->fields = $data['fields']?$data['fields']:$data;
		return $meta;
	}
	
	public static function fromCommonMeta(CommonMeta $cmeta) {
		// TODO
	}
	
	public function toCommonMeta() {
		$cmeta = new CommonMeta($this->module);
		
		$plist = $this->extractPicklists();
		
		$i = 0;
		
		foreach ($plist as $pname => $values) {
			$cname = $this->recordClass::getCommonFieldName($pname);
			
			if ($cname == null && in_array($values[$i]['label'], $this->priorities)) {
				$cname = 'priority';
			}
			
			if ($cname == null && in_array($values[$i]['label'], $this->statuses)) {
				$cname = 'status';
			}
			if (!$cname) continue;

			$cmeta->setPicklist($cname, $values[$i]);
			
			$i++;
		}

		return $cmeta;
	}
	
	protected function extractPicklists() {
		
		$list = array();
		$i = 0;
	
		foreach ($this->fields as $finfo) {
			
			$fname = $finfo->name;
			
			if (in_array($fname, $this->priorities)) {
				$list[$fname][$i] = array('value' => $fname, 'label' => $fname);
			}
			
			if (in_array($fname, $this->statuses)) {
				$list[$fname][$i] = array('value' => $fname, 'label' => $fname);
			}
			
			$i++;
			if(is_array($finfo)) {
				$type = $finfo['type'];
			}
			
			if ($type == 'picklist') {
				foreach ($finfo['picklistValues'] as $pval) {
					$list[$fname][] = array('value' => $pval['value'], 'label' => $pval['label']);
				}
			}
		}
		
		return $list;
	}
	
}