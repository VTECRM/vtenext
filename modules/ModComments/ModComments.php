<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
include_once dirname(__FILE__) . '/ModCommentsCore.php';
include_once dirname(__FILE__) . '/models/Comments.php';

class ModComments extends ModCommentsCore {

	public $enableDeletion = true; // crmv@101967
	
	// If true, everybody can see conversations attached to visible records
	public $visibilityInherited = false; // crmv@101978
	
	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		parent::vtlib_handler($modulename, $event_type);
		if ($event_type == 'module.postinstall') {
			global $adb,$table_prefix;
		
			SDK::addView('ModComments', 'modules/SDK/src/modules/ModComments/ModCommentsView.php', 'constrain', 'continue');
			
			$modCommentsInstance = Vtecrm_Module::getInstance('ModComments');
			$modCommentsInstance->setRelatedList($modCommentsInstance, 'LBL_MODCOMMENTS_REPLIES', Array('ADD'), 'get_replies');
			$modCommentsInstance->hide(array('hide_module_manager'=>1,'hide_profile'=>1,'hide_report'=>1));
			$adb->pquery("UPDATE {$table_prefix}_def_org_share SET editstatus = ? WHERE tabid = ?",array(2,$modCommentsInstance->id));
			
			// Mark the module as Standard module
			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($modulename));
			if ($adb->isMysql()) {
				$adb->query('ALTER TABLE '.$table_prefix.'_modcomments ADD INDEX NewIndex3 (commentcontent (255))');
			}
		}
	}

	// crmv@101967
	static public function isDeletionEnabled() {
		static $focus;
		if (!$focus) {
			$focus = CRMEntity::getInstance('ModComments');
		}
		return $focus->enableDeletion;
	}
	// crmv@101967e
	
	//crmv@29463
	/**
	 * Transfer the comment records from one parent record to another.
	 * @param CRMID Source parent record id
	 * @param CRMID Target parent record id
	 */
	static function transferRecords($currentParentId, $targetParentId) {
		global $adb,$table_prefix;
		$adb->pquery("UPDATE ".$table_prefix."_modcomments SET related_to=? WHERE related_to=?", array($targetParentId, $currentParentId));
	}
	//crmv@29463e
	
	/**
	 * Get widget instance by name
	 */
	static function getWidget($name) {
		if ($name == 'DetailViewBlockCommentWidget' &&
				isPermitted('ModComments', 'DetailView') == 'yes') {
			require_once dirname(__FILE__) . '/widgets/DetailViewBlockComment.php';
			return (new ModComments_DetailViewBlockCommentWidget());
		}
		return false;
	}
	
	/**
	 * Add widget to other module.
	 * @param unknown_type $moduleNames
	 * @return unknown_type
	 */
	static function addWidgetTo($moduleNames, $widgetType='DETAILVIEWWIDGET', $widgetName='DetailViewBlockCommentWidget') {
		if (empty($moduleNames)) return;
		
		include_once 'vtlib/Vtecrm/Module.php';//crmv@207871
		
		if (is_string($moduleNames)) $moduleNames = array($moduleNames);
		
		$commentWidgetCount = 0; 
		foreach($moduleNames as $moduleName) {
			$module = Vtecrm_Module::getInstance($moduleName);
			if($module) {
				$module->addLink($widgetType, $widgetName, "block://ModComments:modules/ModComments/ModComments.php");
				++$commentWidgetCount;
			}
		}
		if ($commentWidgetCount) {
			$modCommentsModule = Vtecrm_Module::getInstance('ModComments');
			if (is_object($modCommentsModule)) {
				$modCommentsModule->addLink('HEADERSCRIPT', 'ModCommentsCommonHeaderScript', 'modules/ModComments/ModCommentsCommon.js');
				$modCommentsRelatedToField = Vtecrm_Field::getInstance('related_to', $modCommentsModule);
				$modCommentsRelatedToField->setRelatedModules($moduleNames);
			}
		}
	}
	
	/**
	 * Remove widget from other modules.
	 * @param unknown_type $moduleNames
	 * @param unknown_type $widgetType
	 * @param unknown_type $widgetName
	 * @return unknown_type
	 */
	static function removeWidgetFrom($moduleNames, $widgetType='DETAILVIEWWIDGET', $widgetName='DetailViewBlockCommentWidget') {
		if (empty($moduleNames)) return;
		
		include_once 'vtlib/Vtecrm/Module.php';//crmv@207871
		
		if (is_string($moduleNames)) $moduleNames = array($moduleNames);
		
		$commentWidgetCount = 0; 
		foreach($moduleNames as $moduleName) {
			$module = Vtecrm_Module::getInstance($moduleName);
			if($module) {
				$module->deleteLink($widgetType, $widgetName, "block://ModComments:modules/ModComments/ModComments.php");
				++$commentWidgetCount;
			}
		}
		if ($commentWidgetCount) {
			$modCommentsModule = Vtecrm_Module::getInstance('ModComments');
			$modCommentsRelatedToField = Vtecrm_Field::getInstance('related_to', $modCommentsModule);
			$modCommentsRelatedToField->unsetRelatedModules($moduleNames);
		}
	}
	
	/**
	 * Wrap this instance as a model
	 */
	function getAsCommentModel() {
		return new ModComments_CommentsModel($this->column_fields);
	}
	
	function addWidgetToAll() {
		global $adb,$table_prefix;
		$skip_modcomm_module = array('Emails','Fax','Sms','Events','ModComments','Charts','MyFiles','MyNotes'); // crmv@164120 crmv@164122
		$result = $adb->pquery('SELECT name FROM '.$table_prefix.'_tab WHERE isentitytype = 1 AND name NOT IN ('.generateQuestionMarks($skip_modcomm_module).')',$skip_modcomm_module);
		if ($result && $adb->num_rows($result) > 0) {
			$modcomm_module = array();
			while($row=$adb->fetchByAssoc($result)) {
				$modcomm_module[] = $row['name'];
			}
			self::addWidgetTo($modcomm_module);
		}
	}
}
?>
