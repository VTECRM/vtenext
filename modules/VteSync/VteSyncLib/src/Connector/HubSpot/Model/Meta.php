<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@195073
namespace VteSyncLib\Connector\HubSpot\Model;

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
		$meta = new static($data['module']);
		$meta->fields = $data['data'];
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
			$type = $finfo['type'];	
			if ($type == 'enumeration') {
				foreach ($finfo['options'] as $pval) {
				//options to match picklist values in potentials module
					if($pval['stageId'] == 'closedwon'){
						$pval['stageId'] = 'Closed Won';
					}	
					if($pval['stageId'] == 'closedlost'){
						$pval['stageId'] = 'Closed Lost';
					}
					// set ticket priority values
					if($pval['value'] == 'LOW'){
						$pval['value'] = 'Low';
					}	
					if($pval['value'] == 'MEDIUM'){
						$pval['value'] = 'Normal';
					}
					if($pval['value'] == 'HIGH'){
						$pval['value'] = 'High';
					}	
				    //set ticket status values
					if($pval['stageId'] == '1'){
						$pval['stageId'] = 'new';
					}
					if($pval['stageId'] == '2'){
						$pval['stageId'] = 'Waiting on contact';
					}
					if($pval['stageId'] == '3'){
						$pval['stageId'] = 'Waiting on us';
					}
					if($pval['stageId'] == '4'){
						$pval['stageId'] = 'Closed';
					}
					// other changes
   				    if($pval['value'] == 'newbusiness'){
						$pval['value'] = 'New Business';
					}					
					if($pval['value'] == 'existingbusiness'){
						$pval['value'] = 'Existing Business';
					}
				
				 if(isset($pval['stageId'])){
				    $val = $pval['stageId'];
				 }              			
				else{
				   $val = $pval['value'];
				}
					$list[$fname][] = array('value' => $val, 'label' => $pval['label']);
				}
			}
		}	
		return $list;
	}
	
}