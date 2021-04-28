<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

	/**
	 * A wrapper around CRMEntity instances
	 */
	class VTEntityData{
		private $isNew = false;
		
		/**
		 * Get an entity data object.
		 *
		 * @param $adb Pear database instance.
		 * @param $entityId The id of the entity to load.
		 * @return The new entity data object.
		 */
		static function fromEntityId($adb, $entityId){
			$obj = new VTEntityData();
			$obj->entityId = $entityId;
			$setype =  getSalesEntityType($entityId); //crmv@171021
			$obj->moduleName = $setype;
			
			require_once('data/CRMEntity.php');
			$focus = CRMEntity::getInstance($setype);
			
			$focus->retrieve_entity_info($entityId, $setype);
			$focus->id = $entityId;
			$obj->isNew = false;
			$obj->focus = $focus;
			return $obj;
		}
		
		
		/**
		 * Get an entity data object from a crmentity object
		 *
		 * @param $crmEntity The CRMEntity instance.
		 * @return The new entity data object.
		 */
		static function fromCRMEntity($crmEntity){
			$obj = new VTEntityData();
			$obj->focus = $crmEntity;
			$obj->isNew = !(isset($crmEntity->id) && $crmEntity->id != null);
			//crmv@fix
			if(isset($_REQUEST['convert_from']) && $_REQUEST['convert_from'] !='') {
				$obj->isNew = true;
			}
			//crmv@fix e
			return $obj;
		}
		
		/**
		 * Get the data from the entity object as an array.
		 *
		 * @return An array representation of the module data.
		 */
		function getData(){
			return $this->focus->column_fields;
		}
		
		/**
		 * Get the entity id.
		 *
		 * @return The entity id.
		 */
		function getId(){
			return $this->focus->id;
		}
		
		/**
		 * Get the name of the module represented by the entity data object.
		 *
		 * @return The module name.
		 */
		function getModuleName(){
			// crmv@129138 - performance patch
			if ($this->focus && $this->focus->modulename) {
				$className = $this->focus->modulename;
			} else {
				$className = SDK::getParentModule(get_class($this->focus));	//crmv@26936
			}
			// crmv@129138e
			$importModuleMapping = Array(
				"ImportLead"=>"Leads",
				"ImportAccount"=>"Accounts",
				"ImportContact"=>"Contacts",
				"ImportOpportunity"=>"Potentials",
				"ImportProduct"=>"Products",
				"ImportTicket"=>"HelpDesk",
				"ImportVendors"=>"Vendors"
			);
			$moduleName = $className;
			if(array_key_exists($className, $importModuleMapping)){
				$moduleName = $importModuleMapping[$className];
			}
			return $moduleName;
		}
		
		function get($fieldName){
			return $this->focus->column_fields[$fieldName];
		}
		
		function set($fieldName, $value){
			$data = $this->focus->column_fields[$fieldName] = $value;
		}
		
		/**
		 * Check whether the object is stored on the database.
		 * 
		 * @return True if the object is saved false otherwiser.
		 */
		function isSaved(){
			return isset($this->focus->id);
		}
		
		
		/**
		* Check wether the obkect is new.
		* 
		* @return True if the object is new, false otherwise.
		*/
		function isNew(){
			return $this->isNew;
		}
		
		//crmv@117355	crmv@136410
		function setNew($isNew){
			$this->isNew = $isNew;
		}
		//crmv@117355e	crmv@136410e
	}

?>