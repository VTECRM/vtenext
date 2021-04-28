<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@197423 */

namespace VteSyncLib\Connector\Vtiger\Model;

use VteSyncLib\Model\MetaInterface;
use VteSyncLib\Model\CommonMeta;

class Meta implements MetaInterface {

	public $module;
	protected $recordClass;
	protected $fields;
	
	public function setRecordClass($class) {
		$this->recordClass = $class;
	}

	public static function fromRawData($data) {
		$meta = new static($data);
		$meta->fields = $data;
		return $meta;
	}
	
	public static function fromCommonMeta(CommonMeta $cmeta) {
		// TODO
	}
	
	public function toCommonMeta() {
		
		
		
		$cmeta = new CommonMeta($this->fields["module"]);
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
			if(isset($finfo['name']))
			{
				$fname = $finfo['name'];	
			}	
			if(isset($finfo['type']['name']))
			{				
				$type = $finfo['type']['name'];	
			}
			if ($type == 'picklist' || $type == 'multipicklist' ||  $type == 'metricpicklist') {
				
			
				foreach ($finfo['type']['picklistValues'] as $pval) {

				   $val = $pval['value'];
					//crmv@197423
					$list[$fname][] = array('value' => ucfirst($val), 'label' => ucfirst($pval['label']));
					//crmv@197423e
				}
			}
		}	
		return $list;
	}
	
}