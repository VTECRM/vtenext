<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Connector\VTE\Model;

use VteSyncLib\Model\MetaInterface;
use VteSyncLib\Model\CommonMeta;

class Meta implements MetaInterface {

	protected $module;
	protected $recordClass;
	protected $fields;
	
	public function setRecordClass($class) {
		$this->recordClass = $class;
	}

	public static function fromRawData($data) {
		$meta = new static($data['name']);
		$meta->fields = $data['fields'];
		return $meta;
	}
	
	public static function fromCommonMeta(CommonMeta $cmeta) {
		// TODO
	}
	
	public function toCommonMeta() {
		$cmeta = new CommonMeta($this->module);
		
		$plist = $this->extractPicklists();
		foreach ($plist as $pname => $values) {
			$cname = $this->recordClass::getCommonFieldName($pname);
			if (!$cname) continue;
			$cmeta->setPicklist($cname, $values);
		}
		
		return $cmeta;
	}
	
	protected function extractPicklists() {
		$list = array();
		foreach ($this->fields as $finfo) {
			$fname = $finfo['name'];
			$type = $finfo['type']['name'];
			if ($type == 'picklist') {
				foreach ($finfo['type']['picklistValues'] as $pval) {
					$list[$fname][] = array('value' => $pval['value'], 'label' => $pval['label']);
				}
			}
		}
		
		return $list;
	}
	
}